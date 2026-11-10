<?php
require dirname(__DIR__, 2) . '/app/community/model/service/WaterAlarmTransport.php';
require dirname(__DIR__, 2) . '/app/community/model/service/WaterAlarmService.php';
use app\community\model\service\WaterAlarmTransport;
use app\community\model\service\WaterAlarmService;

function check($condition, $label) { if (!$condition) { throw new RuntimeException($label); } }
check(bin2hex(WaterAlarmTransport::crc(hex2bin('010300000001'))) === '840a', 'reference CRC');
check(WaterAlarmService::volume(1) === 1 && WaterAlarmService::volume('31') === 31, 'valid volume');
foreach ([0, 32, -1, '1.5', 1.5, [], true, '1e1', '01'] as $invalid) {
    try { WaterAlarmService::volume($invalid); throw new RuntimeException('accepted invalid volume'); } catch (InvalidArgumentException $e) {}
}
foreach (['', ' ', '<script>', str_repeat('a', 61), []] as $invalid) {
    try { WaterAlarmService::deviceName($invalid); throw new RuntimeException('accepted invalid name'); } catch (InvalidArgumentException $e) {}
}
check(!WaterAlarmService::hasMenu(['village_id'=>1,'menus'=>''], 12, [], 123), 'empty grant denied');
check(WaterAlarmService::hasMenu(['village_id'=>1,'menus'=>'123'], 12, [], 123), 'explicit grant accepted');
check(!WaterAlarmService::hasMenu(['village_id'=>1,'normal_admin_id'=>2,'menus'=>''], 6, [], 123), 'ordinary account cannot bypass');
check(!WaterAlarmService::hasMenu(['village_id'=>1], 6, [], 0), 'missing menu denied');
foreach (['', str_repeat('水', 67), '[v10]播报', "文字\n换行", "\xff"] as $invalid) {
    try { WaterAlarmTransport::ttsPayload($invalid); throw new RuntimeException('accepted invalid TTS'); } catch (InvalidArgumentException $e) {}
}
check(strlen(WaterAlarmTransport::ttsPayload(str_repeat('a', 199))) === 200, 'TTS maximum byte count');
check(WaterAlarmTransport::ttsPayload('水浸') === "\xff水浸 ", 'odd length padded with safe space');
check(WaterAlarmTransport::ttsPayload('水浸!') === "\xff水浸!", 'even length unchanged');
$mode = $argv[2];
$client = new WaterAlarmTransport(['host'=>'127.0.0.1','port'=>(int)$argv[1],'unit'=>1,'timeout'=>0.3]);
try {
    if ($mode === 'tts') {
        foreach (['水浸', '水浸!', str_repeat('a', 199)] as $text) {
            $client->speak($text, 1);
            $state = $client->readState(true);
            check($state['tts_active'] && $state['alarm_active'] && $state['tts_volume']===1 && $state['flash_active'], 'TTS state includes active voice and light');
            $client->stopTts();
            $state = $client->readState(true);
            check(!$state['tts_active'] && !$state['alarm_active'], 'TTS stopped');
        }
    } elseif ($mode === 'happy') {
        $client->writeRegisters(1, [1, 1, 0xff01]);
        $state = $client->readState();
        check($state['track'] === 0xff01 && $state['alarm_active'] && $state['flash_active'], 'alarm state');
        $client->setVolume(3);
        $client->writeRegisters(2, [1, 0]);
        $state = $client->readState();
        check(!$state['alarm_active'] && $state['flash_active'] && $state['volume'] === 3, 'flash only');
        $client->writeRegisters(2, [0]);
        $client->writeRegisters(2, [0, 0]);
        $state = $client->readState();
        check(!$state['alarm_active'] && !$state['flash_active'], 'stop');
    } else {
        try {
            if ($mode === 'bad_ack') { $client->setVolume(1); } else { $client->readState(); }
            throw new LogicException('invalid response accepted');
        } catch (RuntimeException $e) { echo $e->getMessage() . PHP_EOL; }
    }
} finally { $client->close(); }
echo 'PASS ' . $mode . PHP_EOL;
