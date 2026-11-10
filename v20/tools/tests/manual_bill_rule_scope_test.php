<?php
// Standalone regression test; uses in-memory rows and never connects to a database.
namespace think\facade {
    class Db {
        public static $tables = [];
        public static function name($name) { return new \BatchRuleQuery(self::$tables[$name] ?? []); }
    }
}
namespace {
    date_default_timezone_set('Asia/Shanghai');
    class BatchRuleRows {
        private $rows;
        public function __construct($rows) { $this->rows = $rows; }
        public function toArray() { return $this->rows; }
    }
    class BatchRuleQuery {
        private $rows;
        public function __construct($rows) { $this->rows = $rows; }
        public function where($conditions, $operator = null, $value = null) {
            if (is_string($conditions)) { $conditions = [[$conditions, $operator, $value]]; }
            foreach ($conditions as $condition) {
                list($field, $op, $expected) = $condition;
                $field = preg_replace('/^\w+\./', '', $field);
                $this->rows = array_values(array_filter($this->rows, function ($row) use ($field, $op, $expected) {
                    $actual = $row[$field] ?? null;
                    switch ($op) {
                        case '=': return $actual == $expected;
                        case 'in': return in_array($actual, $expected);
                        case '>': return $actual > $expected;
                        case '<=': return $actual <= $expected;
                        case '>=': return $actual >= $expected;
                        default: throw new \Exception('Unsupported operator: ' . $op);
                    }
                }));
            }
            return $this;
        }
        public function field($fields) { return $this; }
        public function alias($alias) { return $this; }
        public function leftJoin($table, $on) { return $this; }
        public function order($order) { return $this; }
        public function lock($lock) { return $this; }
        public function find() { return $this->rows[0] ?? null; }
        public function select() { return new BatchRuleRows($this->rows); }
        public function column($field) { return array_column($this->rows, $field); }
        public function max($field) { return $this->rows ? max(array_column($this->rows, $field)) : null; }
    }
    require __DIR__ . '/../../app/community/model/service/HouseNewManualBillService.php';
    $service = new \app\community\model\service\HouseNewManualBillService();
    function invokeBillMethod($name, array $args) {
        global $service;
        $method = new \ReflectionMethod($service, $name);
        $method->setAccessible(true);
        return $method->invokeArgs($service, $args);
    }
    function expect($actual, $expected, $label) {
        if ($actual !== $expected) {
            throw new \Exception($label . ': ' . json_encode($actual) . ' != ' . json_encode($expected));
        }
        echo 'PASS ' . $label . PHP_EOL;
    }
    function billRow($overrides = []) {
        return array_merge([
            'order_id' => 1, 'village_id' => 1, 'room_id' => 10, 'position_id' => 20,
            'project_id' => 100, 'rule_id' => 1000, 'is_paid' => 2, 'is_discard' => 1, 'is_refund' => 1,
            'service_start_time' => strtotime('2026-01-01'),
            'service_end_time' => strtotime('2026-12-31 23:59:59'),
        ], $overrides);
    }
    \think\facade\Db::$tables['house_village_user_vacancy'] = [[
        'pigcms_id' => 10, 'village_id' => 1, 'is_del' => 0,
        'property_service_start_time' => strtotime('2027-01-01'),
        'property_service_end_time' => strtotime('2028-03-29 23:59:59'),
    ]];
    \think\facade\Db::$tables['house_village_parking_position'] = [['position_id' => 20, 'village_id' => 1]];
    $foreignBill = billRow(['project_id' => 200, 'rule_id' => 2000, 'service_start_time' => strtotime('2026-12-01'), 'service_end_time' => strtotime('2027-04-30 23:59:59')]);
    $otherRule = billRow(['rule_id' => 1001, 'service_end_time' => strtotime('2027-08-31 23:59:59')]);
    $baseRows = [billRow(), $foreignBill];
    foreach ([['is_discard' => 2], ['is_refund' => 2], ['is_paid' => 3], ['room_id' => 11, 'position_id' => 21], ['village_id' => 2]] as $override) {
        $baseRows[] = billRow(array_merge(['service_end_time' => strtotime('2029-12-31 23:59:59')], $override));
    }
    \think\facade\Db::$tables['house_new_pay_order'] = $baseRows;
    $expected = ['start_date' => '2027-01-01', 'end_date' => '2027-12-31'];
    expect(invokeBillMethod('getBatchBillDateRange', [1, 10, 'bill', 12, 'month', 100]), $expected, 'room: ignore other projects, targets and inactive bills');
    expect(invokeBillMethod('getBatchBillDateRange', [1, 20, 'parking_management', 12, 'month', 100]), $expected, 'position: isolate selected project');
    expect(invokeBillMethod('getBatchBillDateRange', [1, 10, 'bill', 365, 'day', 100]), $expected, 'day period remains correct');
    expect(invokeBillMethod('assertNoOverlappingBill', [1, 10, 100, strtotime('2027-01-01'), strtotime('2027-12-31 23:59:59')]), null, 'other project does not block room creation');
    expect(invokeBillMethod('assertNoOverlappingPositionBill', [1, 20, 100, strtotime('2027-01-01'), strtotime('2027-12-31 23:59:59')]), null, 'other project does not block position creation');
    $sameRule = billRow(['rule_id' => 1001, 'is_paid' => 1, 'service_start_time' => strtotime('2027-01-01'), 'service_end_time' => strtotime('2027-02-28 23:59:59')]);
    \think\facade\Db::$tables['house_new_pay_order'][] = $sameRule;
    expect(invokeBillMethod('getBatchBillDateRange', [1, 10, 'bill', 12, 'month', 100]), ['start_date' => '2027-03-01', 'end_date' => '2028-02-29'], 'continue after another rule paid bill in same project, including leap year');
    foreach ([['assertNoOverlappingBill', 10], ['assertNoOverlappingPositionBill', 20]] as $target) {
        $blocked = false;
        try { invokeBillMethod($target[0], [1, $target[1], 100, strtotime('2027-01-01'), strtotime('2027-12-31 23:59:59')]); }
        catch (\Exception $e) { $blocked = strpos($e->getMessage(), '2027-02-28') !== false; }
        expect($blocked, true, $target[0] . ': same project different rule overlap still blocked');
    }
    \think\facade\Db::$tables['house_new_pay_order'] = [$foreignBill];
    expect(invokeBillMethod('getBatchBillDateRange', [1, 10, 'bill', 12, 'month', 100]), $expected, 'first project bill uses property service start, ignoring other projects');
    \think\facade\Db::$tables['house_village_user_vacancy'][0]['property_service_end_time'] = strtotime('2027-06-30 23:59:59');
    expect(invokeBillMethod('getBatchBillDateRange', [1, 10, 'bill', 12, 'month', 100]), ['start_date' => '2027-01-01', 'end_date' => '2027-06-30'], 'property service end cap preserved');
    // Existing paid bill under a different rule must work without property service dates.
    \think\facade\Db::$tables['house_village_user_vacancy'][0]['property_service_start_time'] = 0;
    \think\facade\Db::$tables['house_village_user_vacancy'][0]['property_service_end_time'] = 0;
    \think\facade\Db::$tables['house_new_pay_order'] = [billRow(['rule_id' => 999, 'is_paid' => 1]), $foreignBill];
    expect(invokeBillMethod('getBatchBillDateRange', [1, 10, 'bill', 12, 'month', 100]), $expected, 'paid bill under old rule works without property service dates');
    \think\facade\Db::$tables['house_new_charge_standard_bind'] = [
        ['village_id' => 1, 'project_id' => 100, 'rule_id' => 999, 'bind_type' => 1, 'is_del' => 1, 'vacancy_id' => 10],
        ['village_id' => 1, 'project_id' => 200, 'rule_id' => 2000, 'bind_type' => 1, 'is_del' => 1, 'vacancy_id' => 11],
        ['village_id' => 1, 'project_id' => 100, 'rule_id' => 999, 'bind_type' => 2, 'is_del' => 1, 'position_id' => 20],
    ];
    expect(invokeBillMethod('getBatchBoundTargetIdMap', [1, 100, 'room', [10, 11]]), [10 => true], 'room eligibility uses project binding, not selected rule');
    expect(invokeBillMethod('getBatchBoundTargetIdMap', [1, 100, 'position', [20, 21]]), [20 => true], 'position eligibility uses project binding');
}
