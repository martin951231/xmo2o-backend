<?php
// Execute the detail's collector-selection block without database access.
$source = file_get_contents(__DIR__ . '/../v20/app/community/model/service/HouseNewCashierService.php');
$start = strpos($source, '            $role_name =', strpos($source, '$import_role_name ='));
$end = strpos($source, "$" . "data['role_name']=$" . 'role_name;', $start);
$block = substr($source, $start, $end - $start);
$db_admin = new class {
    public function getOne($where, $fields) { throw new Exception('Unexpected administrator lookup'); }
};
$cases = [
    [4, 4, 12, 401, '刘欢欢', '通联支付'],
    [4, 4, 13, 0, '崔嘉欣', '通联支付'],
    [4, 2, 12, 401, '旧操作人', '通联支付'],
    [0, 4, 12, 0, '旧操作人', '通联支付'],
    [2, 2, 12, 401, '线下收银员', '线下收银员'],
    [9, 9, 12, 401, '混合支付收银员', '混合支付收银员'],
];
foreach ($cases as $i => $case) {
    $summary_info = ['pay_type' => $case[0]];
    $data = ['pay_type_way'=>$case[1], 'role_id'=>$case[2], 'login_role'=>$case[3]];
    $import_role_name = $case[4];
    eval($block);
    if ($role_name !== $case[5]) throw new Exception('Failed case ' . $i);
}
echo count($cases) . " collector regression checks passed\n";
