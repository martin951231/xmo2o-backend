<?php
// Isolated regression checks: no database or print side effects.
class HouseVillageUserBind {
    public static $rows = [];
    public function getOne($where, $field) {
        foreach (self::$rows as $row) {
            $match = true;
            foreach ($where as $key => $value) {
                if (is_array($value)) {
                    list($key, $op, $expected) = $value;
                    $match = $match && ($op === 'in' ? in_array($row[$key] ?? null, $expected) : ($row[$key] ?? null) == $expected);
                } else {
                    $match = $match && ($row[$key] ?? null) == $value;
                }
            }
            if ($match) return new BindRow($row);
        }
        return null;
    }
}
class BindRow extends ArrayObject {
    public function isEmpty() { return !$this->count(); }
    public function toArray() { return $this->getArrayCopy(); }
}
class UserService {
    public function getUser($uid, $field) { return ['nickname' => 'External payer', 'phone' => '333']; }
}
$source = file_get_contents(__DIR__ . '/../v20/app/community/model/service/HouseNewCashierService.php');
$start = strpos($source, '    private function resolveCashierPayer(');
$end = strpos($source, '    public function getCancelOrder(', $start);
$method = str_replace('private function', 'public function', substr($source, $start, $end - $start));
eval('class PayerHarness {' . $method . ' public function getRoomUserBindByPosition($p,$v) { return []; }}');
HouseVillageUserBind::$rows = [
    ['village_id'=>2,'pigcms_id'=>10,'uid'=>100,'status'=>1,'vacancy_id'=>20,'type'=>0,'name'=>'Owner','phone'=>'111'],
    ['village_id'=>2,'pigcms_id'=>11,'uid'=>101,'status'=>1,'vacancy_id'=>20,'type'=>1,'name'=>'Actual payer','phone'=>'222'],
];
$service = new PayerHarness();
$cases = [
    [['pigcms_id'=>10,'pay_bind_name'=>'无','pay_bind_phone'=>' '],2,false,['name'=>'Owner','phone'=>'111','bind_id'=>10]],
    [['pigcms_id'=>10,'pay_bind_name'=>'Saved payer','pay_bind_phone'=>''],2,false,['name'=>'Saved payer','phone'=>'111','bind_id'=>0]],
    [['pigcms_id'=>10],3,false,['name'=>'','phone'=>'','bind_id'=>0]],
    [['room_id'=>20],2,false,['name'=>'Owner','phone'=>'111','bind_id'=>10]],
    [['pigcms_id'=>10,'payer_uid'=>101,'pay_bind_name'=>'Owner'],2,true,['name'=>'Actual payer','phone'=>'222','bind_id'=>11]],
    [['pigcms_id'=>10,'payer_uid'=>102],2,true,['name'=>'External payer','phone'=>'333','bind_id'=>0]],
    [['pigcms_id'=>10],2,true,['name'=>'','phone'=>'','bind_id'=>0]],
    [['pigcms_id'=>10,'pay_bind_id'=>11],2,false,['name'=>'Actual payer','phone'=>'222','bind_id'=>11]],
    [['pigcms_id'=>10,'pay_bind_name'=>'Owner','pay_bind_phone'=>'111'],2,false,['name'=>'Owner','phone'=>'111','bind_id'=>10]],
    [['pigcms_id'=>10,'pay_bind_id'=>11,'pay_bind_name'=>'Saved payer','pay_bind_phone'=>'444'],2,false,['name'=>'Saved payer','phone'=>'444','bind_id'=>11]],
];
foreach ($cases as $i => $case) {
    $actual = $service->resolveCashierPayer($case[0], $case[1], $case[2]);
    if ($actual !== $case[3]) throw new Exception('Case ' . $i . ': ' . json_encode($actual));
}
echo count($cases) . " payer regression checks passed\n";
