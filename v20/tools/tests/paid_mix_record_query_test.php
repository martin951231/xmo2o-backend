<?php
// Read-only integration check against the local database; PHP 7.3+.
$config = require dirname(__DIR__, 3) . '/conf/db.php';
if (!in_array($config['DB_HOST'], ['localhost', '127.0.0.1'], true)) {
    throw new RuntimeException('This check requires a local database');
}
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = new \think\App(dirname(__DIR__, 2) . '/');
$app->initialize();

use think\facade\Db;
use app\community\model\service\HousePaidOrderRecordService;

$record = Db::name('paid_order_record')->alias('r')
    ->join('house_new_pay_order_summary s', 's.summary_id=r.order_id AND s.village_id=r.village_id')
    ->where('r.table_name', 'house_new_pay_order_summary')
    ->where('r.source_from', 1)->where('s.pay_type', 22)
    ->field('r.id,r.village_id,s.pay_detail')->find();
if (!$record) {
    throw new RuntimeException('No local mixed-payment record available');
}
$service = new HousePaidOrderRecordService();
$where = [['id', '=', $record['id']], ['village_id', '=', $record['village_id']]];
$list = $service->getPaidOrderRecordList($record['village_id'], $where, '*', 1, 15);
if (count($list['lists']) !== 1) {
    throw new RuntimeException('Expected one list row');
}
$export = $service->getPaidOrderRecordList($record['village_id'], $where, '*', 0, 15, true);
$summaryId = $list['lists'][0]['order_id'];
$eligible = Db::name('house_new_pay_order')->where('village_id', $record['village_id'])
    ->where('summary_id', $summaryId)->where('is_paid', 1)->where('is_discard', 1)
    ->where('order_type', '<>', 'non_motor_vehicle')
    ->whereRaw('(is_refund=1 OR refund_money<pay_money)')->select();
if ($eligible->isEmpty()) {
    if ($export['lists']) {
        throw new RuntimeException('Historical record without eligible bills was exported');
    }
} else {
    $expected = 0;
    foreach ($eligible as $bill) {
        $expected += max(round($bill['pay_money'] + $bill['discount_money'] - $bill['refund_money']
            - $bill['score_deducte'] - $bill['deposit_money'], 2), 0);
    }
    if (count($export['lists']) !== 1 || abs($export['lists'][0][5] - $expected) > 0.001) {
        throw new RuntimeException('Export amount does not match eligible paid bills');
    }
}
$empty = $service->getPaidOrderRecordList($record['village_id'], [['id', '=', -1]], '*', 1, 15);
if ($empty['lists']) {
    throw new RuntimeException('Expected empty result');
}
$cashier = new \app\community\model\service\HouseNewCashierService();
foreach ([
    [800, 200, 0, 0, 0, 1000],
    [800, 200, 100, 0, 0, 900],
    [800, 200, 1000, 0, 0, 0],
    [800, 200, 0, 50, 100, 850],
] as $case) {
    $actual = $cashier->getOrderPaidExportMoney(['pay_money'=>$case[0], 'discount_money'=>$case[1],
        'refund_money'=>$case[2], 'score_deducte'=>$case[3], 'deposit_money'=>$case[4]]);
    if (abs($actual - $case[5]) > 0.001) throw new RuntimeException('Net paid amount regression');
}
echo "PASS: mixed-payment list, eligible paid-bill export, empty result, partial/full refunds and deductions (read only)\n";
