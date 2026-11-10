<?php
// The existing harness supplies in-memory query rows: no database writes or real bill creation.
require __DIR__ . '/manual_bill_rule_scope_test.php';
function rejectsBatchDate($fn, $label) {
    try { $fn(); } catch (\Exception $e) { echo 'PASS ' . $label . "\n"; return; }
    throw new \Exception('Expected rejection: ' . $label);
}
foreach ([['', '2026-01-01'], ['2026-01-01', ''], ['2026-01-02', '2026-01-01'], ['2026-02-30', '2026-03-01'], ['2026-01-01', '2040-01-01']] as $dates) {
    rejectsBatchDate(function () use ($dates) {
        invokeBillMethod('getBatchRequestedDates', [['service_start_time' => $dates[0], 'service_end_time' => $dates[1]]]);
    }, 'missing, invalid, reversed or excessive date range: ' . implode(' / ', $dates));
}
expect(invokeBillMethod('getBatchRequestedDates', [['service_start_time' => '2024-02-29', 'service_end_time' => '2024-02-29']]), ['2024-02-29', '2024-02-29'], 'same-day leap date is accepted');
\think\facade\Db::$tables['house_new_pay_order'] = [];
$room = ['pigcms_id' => 10, 'village_id' => 1, 'housesize' => 100, 'delivery_time' => strtotime('2020-01-01'), 'property_service_start_time' => strtotime('2020-01-01'), 'property_service_end_time' => strtotime('2030-12-31')];
$position = ['position_id' => 20, 'position_area' => 10];
$fixedRule = ['bill_create_set' => 2, 'fees_type_status' => 1, 'charge_price' => 310];
$areaRule = ['bill_create_set' => 2, 'fees_type_status' => 2, 'charge_price' => 290];
$preview = invokeBillMethod('getBatchDatePreview', [1, $room, 'room', 100, $fixedRule, '2026-01-31', '2026-02-01']);
expect($preview['days'], 2, 'cross-month range includes both endpoints');
expect((float)$preview['estimated_amount'], 21.1, 'room fixed fee uses actual days in each month');
expect([$preview['bill_start_date'], $preview['bill_end_date']], ['2026-01-31', '2026-02-01'], 'explicit dates are not replaced by latest bill dates');
$preview = invokeBillMethod('getBatchDatePreview', [1, $room, 'room', 100, $areaRule, '2024-02-28', '2024-02-29']);
expect((float)$preview['estimated_amount'], 2000.0, 'leap-year area pricing');
$preview = invokeBillMethod('getBatchDatePreview', [1, $position, 'position', 100, $fixedRule, '2026-01-15', '2026-01-15']);
expect((float)$preview['estimated_amount'], 10.0, 'single-day parking fixed price');
$preview = invokeBillMethod('getBatchDatePreview', [1, $position, 'position', 100, $areaRule, '2024-02-01', '2024-02-29']);
expect((float)$preview['estimated_amount'], 2900.0, 'parking area fee uses existing monthly unit logic');
foreach (['room', 'position'] as $type) {
    \think\facade\Db::$tables['house_new_pay_order'] = [billRow()];
    rejectsBatchDate(function () use ($type, $room, $position, $fixedRule) {
        invokeBillMethod('getBatchDatePreview', [1, $type === 'room' ? $room : $position, $type, 100, $fixedRule, '2026-01-01', '2026-01-31']);
    }, $type . ' preview rejects overlapping dates');
}
\think\facade\Db::$tables['house_new_pay_order'] = [];
$room['property_service_end_time'] = strtotime('2026-01-20');
rejectsBatchDate(function () use ($room, $fixedRule) { invokeBillMethod('getBatchDatePreview', [1, $room, 'room', 100, $fixedRule, '2026-01-01', '2026-01-31']); }, 'service limit rejects rather than silently changing chosen dates');
$room['property_service_end_time'] = strtotime('2030-12-31');
foreach (['room', 'position'] as $type) {
    foreach ([1, 2] as $paid) {
        foreach ([['2026-09-01', '2026-09-21'], ['2026-09-22', '2026-09-30'], ['2026-09-21', '2026-09-21']] as $range) {
            \think\facade\Db::$tables['house_new_pay_order'] = [billRow([
                'is_paid' => $paid, 'rule_id' => 999,
                'service_start_time' => strtotime($range[0]),
                'service_end_time' => strtotime($range[1] . ' 23:59:59'),
            ])];
            $message = '';
            try {
                invokeBillMethod('getBatchDatePreview', [1, $type === 'room' ? $room : $position, $type, 100, $fixedRule, '2026-09-21', '2026-09-22']);
            } catch (\Exception $e) { $message = $e->getMessage(); }
            expect(strpos($message, '当前时间段已有重复账单') === 0, true, $type . ' blocks paid state ' . $paid . ' with one-day overlap: ' . implode('/', $range));
        }
    }
    foreach ([['2026-09-01', '2026-09-20'], ['2026-09-23', '2026-09-30']] as $range) {
        \think\facade\Db::$tables['house_new_pay_order'] = [billRow([
            'service_start_time' => strtotime($range[0]),
            'service_end_time' => strtotime($range[1] . ' 23:59:59'),
        ])];
        $preview = invokeBillMethod('getBatchDatePreview', [1, $type === 'room' ? $room : $position, $type, 100, $fixedRule, '2026-09-21', '2026-09-22']);
        expect($preview['days'], 2, $type . ' permits adjacent non-overlapping dates');
    }
}
echo "Batch explicit-date billing checks passed.\n";
