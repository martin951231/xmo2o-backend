<?php
require __DIR__ . '/../../app/community/model/service/WaterLeakProtocol.php';
use app\community\model\service\WaterLeakProtocol;

function checkLeak($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
function rejectLeak($fn) {
    try { $fn(); } catch (Exception $e) { return; }
    throw new RuntimeException('Invalid input accepted');
}
checkLeak(bin2hex(WaterLeakProtocol::readRequest()) === '0103238000018e66', 'Document request mismatch');
$state = WaterLeakProtocol::parseResponse(hex2bin('0103020000b844'));
checkLeak($state['raw_value'] === 0 && $state['state'] === 'dry', 'Document dry response mismatch');
$state = WaterLeakProtocol::parseResponse(hex2bin('01030200017984'));
checkLeak($state['raw_value'] === 1 && $state['state'] === 'unknown', 'Undocumented value must remain explicit');
rejectLeak(function () { WaterLeakProtocol::readRequest(0); });
rejectLeak(function () { WaterLeakProtocol::readRequest(248); });
rejectLeak(function () { WaterLeakProtocol::parseResponse(hex2bin('0103020000b845')); });
rejectLeak(function () { WaterLeakProtocol::parseResponse(hex2bin('0103020000b844'), 2); });
rejectLeak(function () { WaterLeakProtocol::parseResponse(hex2bin('01030200')); });
rejectLeak(function () { WaterLeakProtocol::parseResponse(hex2bin('018302c0f1')); });
echo "9 water leak protocol checks passed\n";
