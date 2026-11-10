<?php
require dirname(__DIR__, 2) . '/app/community/model/service/WaterAlarmTransport.php';
require dirname(__DIR__, 2) . '/app/community/model/service/WaterAlarmGatewayTransport.php';
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = new \think\App(dirname(__DIR__, 2) . '/'); $app->initialize();
$config = ['mode'=>'gateway','timeout'=>0.3,'gateway'=>['control_port'=>(int)$argv[1], 'secret'=>str_repeat('test-only-',5),'device_key'=>'primary']];
\think\facade\Config::set($config, 'water_alarm');
class GatewayFactoryProbe extends \app\community\model\service\WaterAlarmService {
    public function connect() { return $this->transport(); }
}
$client = (new GatewayFactoryProbe())->connect();
if (!($client instanceof \app\community\model\service\WaterAlarmGatewayTransport)) { throw new RuntimeException('Service did not select gateway transport'); }
try {
    $client->stopTts();
    $client->writeRegisters(1,[1,1,0xff01]);
    $state=$client->readState(true);
    if (!$state['alarm_active'] || !$state['flash_active'] || $state['volume']!==1) { throw new RuntimeException('Alarm through gateway failed'); }
    $client->writeRegisters(2,[1,0]);
    $client->speak('水浸，请及时处理。',1);
    $state=$client->readState(true);
    if (!$state['tts_active']) { throw new RuntimeException('TTS through gateway failed'); }
    $client->writeSingle(0x22,2);
    $client->setVolume(2);
    $client->stopTts();
    $client->writeRegisters(2,[0,0]);
    $state=$client->readState(true);
    if ($state['alarm_active'] || $state['flash_active']) { throw new RuntimeException('Stop through gateway failed'); }
    echo 'PASS complete alarm / flash / TTS / volume / stop over persistent ingress'.PHP_EOL;
} finally { $client->close(); }
