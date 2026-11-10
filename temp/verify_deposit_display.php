<?php
require __DIR__ . '/../v20/vendor/autoload.php';
$app = new \think\App(__DIR__ . '/../v20/');
$app->initialize();
$service = new \app\community\model\service\HouseNewCashierService();
$method = new ReflectionMethod($service, 'fillDepositCategoryInfo');
$method->setAccessible(true);
$rows = \think\facade\Db::name('house_new_pay_order')->where('order_type', 'deposit_bill')->order('order_id DESC')->limit(3)->select()->toArray();
if (!$rows) throw new Exception('No deposit bills for verification');
foreach ($rows as $row) {
    $result = $method->invoke($service, $row);
    if ($result['project_name'] !== '押金账单' || empty($result['deposit_category_name']) || $result['charge_name'] !== $result['deposit_category_name'] || $result['rule_name'] !== $result['deposit_category_name']) throw new Exception('Incorrect deposit display');
    echo $row['order_id'] . ': ' . $result['project_name'] . ' / ' . $result['charge_name'] . PHP_EOL;
}
$orderId = intval($rows[0]['order_id']);
$where = [['o.order_id', '=', $orderId]];
foreach (['getNewPayOrders', 'getCancelOrder1'] as $name) {
    if ($name === 'getNewPayOrders') {
        $result = $service->$name($where, 'o.*,o.modify_money as total_money,r.charge_name,p.name as project_name,n.charge_number_name', 1, 10);
    } else {
        $result = $service->$name($where, '', 'o.*,o.modify_money as total_money,p.name as project_name,n.charge_number_name', 1, 10);
    }
    $list = $result['list'] ?? $result;
    $first = reset($list);
    if (($first['project_name'] ?? '') !== '押金账单' || ($first['charge_name'] ?? '') !== $rows[0]['order_name']) throw new Exception($name . ': incorrect display');
    echo $name . ': ' . $first['project_name'] . ' / ' . $first['charge_name'] . PHP_EOL;
}
