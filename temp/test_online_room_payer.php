<?php
// Isolated regression check: no application bootstrap or database connection.
class PayerBindResult
{
    private $row;
    public function __construct($row) { $this->row = $row; }
    public function isEmpty() { return !$this->row; }
    public function toArray() { return $this->row; }
}
class HouseVillageUserBindService
{
    public static $rows = [];
    public function getBindInfo($where, $field)
    {
        foreach (self::$rows as $row) {
            foreach ($where as $condition) {
                list($key, $operator, $value) = $condition;
                if ($operator === 'in' ? !in_array($row[$key], $value) : $row[$key] != $value) {
                    continue 2;
                }
            }
            return new PayerBindResult($row);
        }
        return new PayerBindResult([]);
    }
}
$source = file_get_contents(__DIR__ . '/../v20/app/community/model/service/NewPayService.php');
$start = strpos($source, '    private function findRoomPayerBind(');
$end = strpos($source, '    public function CashierApiGoPay(', $start);
if ($start === false || $end === false) {
    throw new RuntimeException('Production method not found');
}
eval('class PayerResolverTest {'
    . str_replace('private function', 'public function', substr($source, $start, $end - $start))
    . '}');
$resolver = new PayerResolverTest();
$base = ['village_id' => 10, 'vacancy_id' => 20, 'status' => 1, 'phone' => '13800000000'];
$owner = $base + ['pigcms_id' => 1, 'uid' => 100, 'name' => 'Owner', 'type' => 0];
$family = $base + ['pigcms_id' => 2, 'uid' => 200, 'name' => 'Family', 'type' => 1];
$tenant = $base + ['pigcms_id' => 3, 'uid' => 300, 'name' => 'Tenant', 'type' => 2];
HouseVillageUserBindService::$rows = [$owner, $family, $tenant];
$cases = [
    ['owner binding preserved', 100, 10, 20, 1, 1],
    ['family paying with owner cached binding', 200, 10, 20, 1, 2],
    ['tenant paying with owner cached binding', 300, 10, 20, 1, 3],
    ['missing submitted binding recovered', 200, 10, 20, 0, 2],
    ['unbound payer must not become owner', 400, 10, 20, 1, 0],
    ['other room excluded', 200, 10, 21, 2, 0],
    ['other village excluded', 200, 11, 20, 2, 0],
    ['missing login excluded', 0, 10, 20, 1, 0],
];
foreach ($cases as $case) {
    list($label, $uid, $village, $room, $preferred, $expected) = $case;
    $result = $resolver->findRoomPayerBind($uid, $village, $room, $preferred);
    if (($result['pigcms_id'] ?? 0) !== $expected) {
        throw new RuntimeException($label);
    }
    if ($expected === 2 && ($result['name'] !== 'Family' || $result['phone'] !== $base['phone'])) {
        throw new RuntimeException('Registered name and phone not preserved');
    }
}
HouseVillageUserBindService::$rows = [array_replace($family, ['status' => 0]), $owner];
if ($resolver->findRoomPayerBind(200, 10, 20, 2)) {
    throw new RuntimeException('Inactive binding accepted');
}
echo "PASS: 9 room payer scenarios; no database accessed.\n";
