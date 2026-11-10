<?php
require __DIR__ . '/../../app/community/model/service/WaterLeakReport.php';
use app\community\model\service\WaterLeakReport;
foreach ([0, 1, 0.0, 1.0, '1.00', '0.00'] as $value) {
    if (WaterLeakReport::parse(json_encode(['ID'=>1111,'zhuangtai'=>$value]), '1111') !== (int)$value) { throw new RuntimeException('Valid state rejected'); }
}
foreach (['{}', '{', '{"ID":2222,"zhuangtai":1}', '{"ID":1111,"zhuangtai":null}', '{"ID":1111,"zhuangtai":true}', '{"ID":1111,"zhuangtai":2}', '{"ID":1111,"zhuangtai":[]}'] as $raw) {
    try { WaterLeakReport::parse($raw, '1111'); } catch (InvalidArgumentException $e) { continue; }
    throw new RuntimeException('Invalid report accepted');
}
echo "13 report parser checks passed\n";
$id = '7F98526D6D369956C656D815';
foreach ([0, 1] as $state) {
    foreach ([$id, '"'.$id.'"'] as $token) {
        if (WaterLeakReport::parse('{"ID":'.$token.',"zhuangtai":'.$state.'.00}', $id) !== $state) { throw new RuntimeException('Quoted/bare ID failed'); }
    }
}
foreach (['00001234', '123456789012345678901234', '1E234'] as $numericId) {
    if (WaterLeakReport::decode('{"ID":'.$numericId.',"zhuangtai":1}')['ID'] !== $numericId) { throw new RuntimeException('ID precision lost'); }
}
try { WaterLeakReport::parse('{"ID":'.$id.'}', $id); throw new RuntimeException('Missing state accepted'); }
catch (InvalidArgumentException $e) { if (strpos($e->getMessage(), '缺少zhuangtai') === false) { throw $e; } }
foreach (['{"ID":ABC-DEF,"zhuangtai":1}', '{"ID":ABC,"zhuangtai":1,}', '{"ID":ABC,"zhuangtai":NaN}'] as $raw) {
    try { WaterLeakReport::parse($raw, 'ABC'); } catch (InvalidArgumentException $e) { continue; }
    throw new RuntimeException('Malformed data accepted');
}
echo "11 bare-ID compatibility checks passed\n";
