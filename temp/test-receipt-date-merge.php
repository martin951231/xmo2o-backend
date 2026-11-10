<?php
// Execute only the real grouping-key block; never invoke getPrintInfo, which
// writes print records and invoice status even during an ordinary preview.
$source = file_get_contents(__DIR__ . '/../v20/app/community/model/service/HouseNewCashierService.php');
$start = strpos($source, '$merge_project_key = $v["project_id"];');
$end = strpos($source, 'if (isset($order_body_merge[$merge_project_key]))', $start);
if ($start === false || $end === false) { throw new Exception('Merge block not found'); }
$code = substr($source, $start, $end - $start);
function receiptKey($code, $type, $date, $special = false) {
    $v = ['project_id' => 12];
    $templateInfo = ['type' => $type];
    $body_item = ['pay_time' => $date, 'start_time' => '-', 'merge_order_id' => 99, 'is_prepare' => 0];
    $merge_project_key_arr = [];
    $yuyanAihorsenetSpecial = $special;
    eval($code);
    return $merge_project_key;
}
foreach ([false, true] as $special) {
    $first = receiptKey($code, 4, '2026-10-14', $special);
    $second = receiptKey($code, 4, '2026-10-21', $special);
    if ($first === $second) { throw new Exception('Different receipt dates merged'); }
    if ($first !== receiptKey($code, 4, '2026-10-14', $special)) { throw new Exception('Same date cannot merge'); }
    if (receiptKey($code, 1, '2026-10-14', $special) !== receiptKey($code, 1, '2026-10-21', $special)) {
        throw new Exception('Other template merge semantics changed');
    }
}
echo "PASS: independent receipts separate dates; same-date and other template grouping preserved\n";
