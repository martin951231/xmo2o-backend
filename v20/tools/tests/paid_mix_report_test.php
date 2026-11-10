<?php
// php v20/tools/tests/paid_mix_report_test.php [--database]
// --database runs SELECT statements only, using the local project's connection.
require dirname(__DIR__, 2) . '/app/community/model/service/HousePaidOrderRecordService.php';

$service = new \app\community\model\service\HousePaidOrderRecordService();
$method = new ReflectionMethod($service, 'getMixRecordPayMoney');
$method->setAccessible(true);
$checks = 0;
function checkMoney($actual, $expected, $label) {
    global $checks;
    if (abs(floatval($actual) - $expected) > 0.00001) {
        throw new RuntimeException($label . ': expected ' . $expected . ', got ' . $actual);
    }
    $checks++;
}
$mixed = json_encode(['list' => [
    ['paymoney' => '800.00', 'money_type' => 1],
    ['paymoney' => '200.00', 'money_type' => 2],
]]);
$cases = [
    [['pay_money' => 800], $mixed, 1000, 'cash plus deduction'],
    [['pay_money' => 1000], $mixed, 1000, 'legacy full amount must not double count'],
    [['pay_money' => 0], json_encode(['list' => [['paymoney' => 200, 'money_type' => 2]]]), 200, 'deduction only'],
    [['pay_money' => 800], json_encode(['list' => [['paymoney' => 800, 'money_type' => 1]]]), 800, 'cash only'],
    [['pay_money' => 800], '', 800, 'missing detail'],
    [['pay_money' => 800], '{invalid', 800, 'invalid detail'],
    [['pay_money' => 800], '{"list":[]}', 800, 'empty detail'],
    [['pay_money' => 800, 'refund_money' => 100], $mixed, 1000, 'refund retains separate column'],
    [['pay_money' => 700, 'balance_money' => 100, 'score_money' => 50], $mixed, 850, 'separate balance and score'],
    [['pay_money' => 0], json_encode(['list' => [['paymoney' => '0.10'], ['paymoney' => '0.20']]]), 0.30, 'decimal rounding'],
];
foreach ($cases as $case) {
    checkMoney($method->invoke($service, $case[0], $case[1]), $case[2], $case[3]);
}

if (in_array('--database', $argv, true)) {
    $config = require dirname(__DIR__, 3) . '/conf/db.php';
    if (!in_array($config['DB_HOST'], ['localhost', '127.0.0.1'], true)) {
        throw new RuntimeException('Database checks are restricted to localhost');
    }
    $db = new PDO('mysql:host=' . $config['DB_HOST'] . ';port=' . $config['DB_PORT'] . ';dbname=' . $config['DB_NAME'],
        $config['DB_USER'], $config['DB_PWD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $source = file_get_contents(dirname(__DIR__, 2) . '/app/community/model/service/HouseNewTransactionSummaryService.php');
    $methods = ['getSummaryByRoomAndRuleList', 'getSummaryByRoomAndProjectList', 'getSummaryByYearAndRuleList', 'getSummaryByYearAndProjectList'];
    // Execute the actual aggregate expressions on mixed, cash-only, deduction-only
    // and legacy (NULL deduction) fixtures without creating tables or changing data.
    $fixture = '(SELECT 800 AS pay_money,200 AS discount_money,100 AS refund_money UNION ALL SELECT 300,0,0 UNION ALL SELECT 0,50,0 UNION ALL SELECT 400,NULL,0) o';
    foreach ($methods as $name) {
        $start = strpos($source, '    public function ' . $name . '(');
        $end = strpos($source, '    public function ', $start + 1);
        $body = substr($source, $start, $end - $start);
        preg_match_all('/sum\(ROUND\((?:o\.)?pay_money[^\n\x27]*?\)\) as (?:all_pay_money|all_total_money)/i', $body, $matches);
        if (!$matches[0]) {
            throw new RuntimeException('Missing aggregate expressions in ' . $name);
        }
        foreach ($matches[0] as $expression) {
            $value = $db->query('SELECT ' . $expression . ' FROM ' . $fixture)->fetchColumn();
            checkMoney($value, 1750, $name . ' gross');
            $value = $db->query('SELECT SUM(ROUND(pay_money + COALESCE(discount_money,0),2)) - SUM(refund_money) FROM ' . $fixture)->fetchColumn();
            checkMoney($value, 1650, $name . ' net refund');
        }
    }
    $table = $config['DB_PREFIX'] . 'house_new_pay_order';
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        throw new RuntimeException('Invalid table prefix');
    }
    $row = $db->query('SELECT COUNT(*) AS orders, COALESCE(SUM(ROUND(pay_money,2)),0) AS cash_total, COALESCE(SUM(ROUND(discount_money,2)),0) AS deduction_total, COALESCE(SUM(ROUND(pay_money + COALESCE(discount_money,0),2)),0) AS corrected_total FROM ' . $table . ' WHERE is_paid=1 AND is_discard=1 AND pay_type=22')->fetch(PDO::FETCH_ASSOC);
    checkMoney($row['corrected_total'], $row['cash_total'] + $row['deduction_total'], 'local mixed order aggregation');
    echo 'Local mixed-order aggregate (read only): ' . json_encode($row) . PHP_EOL;
}
echo $checks . " checks passed\n";
