<?php
// Pure calculations first; optional --database exercises read-only local queries.
require __DIR__ . '/../../app/community/model/service/ChargeStatusService.php';
date_default_timezone_set('Asia/Shanghai');
$service = new \app\community\model\service\ChargeStatusService();
function binding($changes = []) {
    return array_replace(['id'=>1,'project_id'=>10,'rule_id'=>1,'bind_type'=>1,'vacancy_id'=>100,
        'room_id'=>100,'position_id'=>0,'house_type'=>1,'charge_type'=>'property','charge_valid_time'=>'2020-01-01',
        'order_add_time'=>0,'charge_price'=>2,'bill_create_set'=>2,'fees_type'=>2,'unit_gage_type'=>1,
        'housesize'=>100,'position_area'=>10,'custom_value'=>100], $changes);
}
function bill($changes = []) {
    $changes += ['total_money' => $changes['pay_money'] ?? 2400, 'modify_money' => $changes['pay_money'] ?? 2400];
    return array_replace(['order_id'=>1,'project_id'=>10,'rule_id'=>1,'room_id'=>100,'position_id'=>0,
        'is_paid'=>1,'is_discard'=>1,'service_start_time'=>'2026-01-01','service_end_time'=>'2026-12-31','pay_money'=>2400,'refund_money'=>0], $changes);
}
function check($actual, $expected, $label) {
    if (is_numeric($actual) && is_numeric($expected) ? abs($actual-$expected)>0.00001 : $actual !== $expected) {
        throw new \RuntimeException($label . ': expected ' . json_encode($expected) . ', got ' . json_encode($actual));
    }
    echo 'PASS ' . $label . PHP_EOL;
}
$bindings = [binding(), binding(['id'=>2,'rule_id'=>2,'charge_valid_time'=>'2026-07-01','charge_price'=>3]),
    binding(['id'=>3,'vacancy_id'=>200,'room_id'=>200,'house_type'=>2,'housesize'=>50,'charge_price'=>4]),
    binding(['id'=>4,'vacancy_id'=>300,'room_id'=>300]), binding(['id'=>5,'vacancy_id'=>400,'room_id'=>400])];
$bills = [bill(['pay_money'=>1200,'service_end_time'=>'2026-06-30']),
    bill(['order_id'=>2,'rule_id'=>2,'pay_money'=>1800,'service_start_time'=>'2026-07-01']),
    bill(['order_id'=>3,'room_id'=>200,'pay_money'=>2400]),
    bill(['order_id'=>4,'room_id'=>999,'pay_money'=>999999]),
    bill(['order_id'=>5,'project_id'=>99,'pay_money'=>999999]),
    bill(['order_id'=>6,'room_id'=>300,'is_paid'=>2,'pay_money'=>0,'modify_money'=>2400])];
$rows = $service->calculate($bindings, $bills, '2026-01-01','2026-12-31');
check($rows[0]['account_num'],2,'residential rooms deduplicated across rules');
check($rows[0]['paid_account_num'],1,'paid rooms deduplicated across bills and standards');
check($rows[0]['remaining_paid_account_num'],1,'unpaid residential room retained');
check($rows[0]['amound'],5400,'receivables sum generated paid and unpaid bills only');
check($rows[0]['paid_amound'],3000,'unbound rooms and other projects excluded');
check($rows[0]['paid_amound_rate'],55.56,'percentage rounds after multiplication');
check($rows[0]['remaining_paid_amound_num'],2400,'remaining amount uses same room scope');
check($rows[1]['account_num'],1,'shop classified by room type within same project');
check($rows[1]['amound'],2400,'shop receivables use its actual bills');
check($rows[1]['paid_amound_rate'],100,'shop payments stay in shop category');
$duplicate = $service->calculate(array_merge($bindings,[$bindings[1]]),array_merge($bills,[$bills[0]]),'2026-01-01','2026-12-31');
check($duplicate,$rows,'duplicate bindings and bills do not inflate totals');
$unpaid = $service->calculate([binding()],[],'2026-01-01','2026-12-31');
check($unpaid[0]['remaining_paid_amound_num'],0,'no generated bills means no receivables');
check($unpaid[0]['account_num'],0,'bound room without bills is excluded');
$long = $service->calculate([binding()], [bill(['service_start_time'=>'2025-01-01','service_end_time'=>'2026-12-31','pay_money'=>4800])], '2025-01-01','2026-12-31');
check($long[0]['amound'],4800,'receivables not capped to twelve months');
check($long[0]['paid_amound'],4800,'multi-year payment included once');
$part = $service->calculate([binding()], [bill(['service_start_time'=>'2025-01-01','service_end_time'=>'2026-12-31','pay_money'=>4800])], '2026-01-01','2026-12-31');
check($part[0]['paid_amound'],2400,'payment allocated to queried service period');
$mid = $service->calculate([binding()], [bill(['service_start_time'=>'2026-01-01','service_end_time'=>'2026-01-31','pay_money'=>310])], '2026-01-16','2026-01-31');
check($mid[0]['amound'],160,'partial month receivable follows bill service days');
$leap = $service->calculate([binding()], [bill(['service_start_time'=>'2024-02-01','service_end_time'=>'2024-02-29','pay_money'=>290])], '2024-02-28','2024-02-29');
check($leap[0]['amound'],20,'leap month uses 29 days');
$daily = $service->calculate([binding(['bill_create_set'=>1])], [bill(['service_start_time'=>'2026-01-01','service_end_time'=>'2026-01-31','pay_money'=>310])], '2026-01-01','2026-01-15');
check($daily[0]['amound'],150,'daily bill prorates by days');
$annual = $service->calculate([binding(['bill_create_set'=>3])], [bill(['pay_money'=>1200])], '2026-01-01','2026-06-30');
check($annual[0]['amound'],600,'annual bill is prorated by months');
$refund = $service->calculate([binding()], [bill(['refund_money'=>2400])], '2026-01-01','2026-12-31');
check($refund[0]['paid_account_num'],0,'fully refunded bill is not a paid household');
$parking = $service->calculate([binding(['charge_type'=>'parking_management','bind_type'=>2,'position_id'=>50,'fees_type'=>1,'charge_price'=>100])], [bill(['position_id'=>50,'pay_money'=>1200])], '2026-01-01','2026-12-31');
check($parking[2]['account_num'],1,'parking counts bound positions');
check($parking[2]['paid_amound_rate'],100,'parking bill with room ID matches position first');
$historical = $service->calculate([binding(['is_del'=>4,'rule_status'=>4]),binding(['id'=>2,'rule_id'=>2,'charge_valid_time'=>'2026-07-01','charge_price'=>3]),binding(['id'=>3,'room_id'=>900,'vacancy_id'=>900,'is_del'=>4,'rule_status'=>4])], [bill(['pay_money'=>3000]), bill(['order_id'=>2,'room_id'=>900,'pay_money'=>9999])], '2026-01-01','2026-12-31');
check($historical[0]['amound'],3000,'historical standard bill retained for currently bound room');
check($historical[0]['account_num'],1,'fully unbound historic room is not counted');
$zeroPaid = $service->calculate([binding()], [bill(['pay_money'=>0])], '2026-01-01','2026-12-31');
check($zeroPaid[0]['paid_account_num'],1,'settled zero-amount bill still counts as a paid household');
// Same room owns two positions; a room-level parking binding must not enter any totals.
$parkingBindings = [
    binding(['id'=>10,'rule_id'=>10,'charge_type'=>'parking_management','bind_type'=>2,'position_id'=>50,'fees_type'=>1,'charge_price'=>100]),
    binding(['id'=>11,'rule_id'=>11,'charge_type'=>'parking_management','bind_type'=>2,'position_id'=>51,'fees_type'=>1,'charge_price'=>100]),
    binding(['id'=>12,'rule_id'=>12,'charge_type'=>'parking_management','bind_type'=>1,'position_id'=>0,'fees_type'=>1,'charge_price'=>999]),
    binding(['id'=>13,'rule_id'=>13,'charge_type'=>'parking_management','bind_type'=>2,'position_id'=>50,'fees_type'=>1,'charge_price'=>100,'charge_valid_time'=>'2026-07-01']),
];
$unbilledParking = $service->calculate($parkingBindings, [], '2026-01-01','2026-12-31');
check($unbilledParking[2]['account_num'],2,'bound positions without bills still count, duplicate rules do not');
check($unbilledParking[2]['remaining_paid_account_num'],2,'unbilled positions remain unpaid');
check($unbilledParking[2]['amound'],0,'unbilled positions do not create receivable money');
$oneBilledParking = $service->calculate($parkingBindings, [bill(['position_id'=>50,'rule_id'=>10,'pay_money'=>1200])], '2026-01-01','2026-12-31');
check($oneBilledParking[2]['account_num'],2,'one billed position does not exclude other bound positions');
check($oneBilledParking[2]['paid_account_rate'],50,'parking collection rate uses all bound positions');
check($oneBilledParking[2]['amound'],1200,'parking amount includes generated bills only');
$unboundParking = $service->calculate([binding(['charge_type'=>'parking_management','bind_type'=>2,'position_id'=>52,'is_del'=>4])], [], '2026-01-01','2026-12-31');
check($unboundParking[2]['account_num'],0,'fully unbound position excluded');
$parkingBills = [bill(['position_id'=>50,'rule_id'=>10,'pay_money'=>1200]),
    bill(['order_id'=>2,'position_id'=>0,'rule_id'=>12,'pay_money'=>9999]),
    bill(['order_id'=>4,'position_id'=>51,'rule_id'=>11,'is_paid'=>2,'pay_money'=>0,'modify_money'=>1200])];
$positionsOnly = $service->calculate($parkingBindings,$parkingBills,'2026-01-01','2026-12-31');
check($positionsOnly[2]['account_num'],2,'two positions sharing a room remain two; multiple standards count one position');
check($positionsOnly[2]['paid_account_num'],1,'room-level parking payment excluded from paid position count');
check($positionsOnly[2]['remaining_paid_account_num'],1,'unpaid count uses only positions');
check($positionsOnly[2]['amound'],2400,'room binding excluded from parking receivables');
check($positionsOnly[2]['paid_amound'],1200,'room payment excluded from parking receipts');
array_pop($parkingBills);
$parkingBills[] = bill(['order_id'=>3,'position_id'=>51,'rule_id'=>11,'pay_money'=>1200]);
$bothPaid = $service->calculate($parkingBindings,$parkingBills,'2026-01-01','2026-12-31');
check($bothPaid[2]['paid_account_num'],2,'two paid positions sharing a room both count');
check($bothPaid[2]['remaining_paid_account_num'],0,'both paid positions leave zero unpaid');
$roomOnly = $service->calculate([$parkingBindings[2]],[$parkingBills[1]],'2026-01-01','2026-12-31');
check([$roomOnly[2]['account_num'],$roomOnly[2]['paid_account_num'],$roomOnly[2]['amound'],$roomOnly[2]['paid_amound']],[0,0,0.0,0.0],'room-only parking project contributes no counts or money');
// Adjustments, payment status, exclusions and service-period boundaries.
$adjusted = $service->calculate([binding()], [
    bill(['total_money'=>2400,'modify_money'=>1800,'pay_money'=>1800]),
    bill(['order_id'=>2,'is_paid'=>2,'total_money'=>2400,'modify_money'=>600,'pay_money'=>9999]),
], '2026-01-01','2026-12-31');
check($adjusted[0]['amound'],2400,'paid and unpaid bills both use adjusted amount');
check($adjusted[0]['paid_amound'],1800,'unpaid bill pay_money is not collected money');
check($adjusted[0]['remaining_paid_amound_num'],600,'remaining money follows adjusted receivables');
$zeroAdjusted = $service->calculate([binding()], [bill(['is_paid'=>2,'total_money'=>2400,'modify_money'=>0,'pay_money'=>0])], '2026-01-01','2026-12-31');
check($zeroAdjusted[0]['amound'],0,'zero adjustment must not fall back to original amount');
check($zeroAdjusted[0]['account_num'],1,'generated zero bill remains in bill-backed household scope');
$excluded = $service->calculate([binding()], [
    bill(['is_discard'=>2]), bill(['order_id'=>2,'is_paid'=>3]),
    bill(['order_id'=>3,'service_start_time'=>'2025-01-01','service_end_time'=>'2025-12-31']),
    bill(['order_id'=>4,'service_start_time'=>0]),
], '2026-01-01','2026-12-31');
check($excluded[0]['amound'],0,'discarded invalid and out-of-period bills excluded');
check($excluded[0]['account_num'],0,'excluded bills do not add households');
check($part[0]['amound'],2400,'receivables and receipts use same service-period fraction');
$partialRefund = $service->calculate([binding()], [bill(['refund_money'=>400])], '2026-01-01','2026-12-31');
check($partialRefund[0]['paid_amound'],2000,'partial refund still deducted from receipts');
check($partialRefund[0]['amound'],2400,'refund does not change adjusted receivable');
class ChargeStatusControllerTestBase {}
class_alias('ChargeStatusControllerTestBase', 'app\community\controller\CommunityBaseController');
require __DIR__ . '/../../app/community/controller/village_api/StatisticalController.php';
$controller = new \app\community\controller\village_api\StatisticalController();
$response = $controller->buildPeriodicFeeTableData($rows);
check($response['summary']['total_amount_rate'],'69.23%','response summary is recomputed from all categories');
check(count($response['tableData'] ?? $response['data'] ?? []),3,'response keeps three fee rows');
if (in_array('--database', $argv, true)) {
    require __DIR__ . '/../../vendor/autoload.php';
    $app = new \think\App(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
    $app->initialize();
    $villages = \think\facade\Db::name('house_new_charge_standard_bind')->where('is_del',1)->distinct(true)->column('village_id');
    foreach ($villages as $villageId) {
        $result = $service->getData($villageId,'2026-01-01','2026-12-31');
        foreach ($result as $row) {
            if ($row['paid_account_num'] > $row['account_num']) { throw new \RuntimeException('Paid room scope mismatch'); }
        }
        echo 'DATABASE ' . $villageId . ' ' . json_encode($result, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
}
echo 'All charge status checks passed.' . PHP_EOL;