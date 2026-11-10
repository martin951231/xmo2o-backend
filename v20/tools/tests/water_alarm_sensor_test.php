<?php
require __DIR__ . '/water_alarm_service_test.php';
use think\facade\Db;
use app\community\model\service\WaterAlarmSensorService;
use app\community\model\service\WaterLeakReport;
$sql=file_get_contents(dirname(__DIR__,3).'/sqls/package/release/2026-10-23_water_alarm_sensor.sql');
foreach (explode(';', $sql) as $statement) {
    if (trim($statement)) { Db::execute(str_replace('CREATE TABLE IF NOT EXISTS', 'CREATE TEMPORARY TABLE', $statement)); }
}
class FakeSensorService extends WaterAlarmSensorService {
    public $alarm;
    protected function alarmService() { return $this->alarm; }
}
$sensors=new FakeSensorService(); $sensors->alarm=$service;
$a=$sensors->generate(987651)['sensor_id'];
$b=$sensors->generate(987651)['sensor_id'];
check($a!==$b && preg_match('/^[A-F0-9]{24}$/', $a), 'server generates unique uppercase IDs');
rejects(function()use($sensors){$sensors->save(987651,'MANUAL','漏水');}, 'manual sensor ID rejected');
rejects(function()use($sensors,$a){$sensors->save(987652,$a,'漏水');}, 'foreign village cannot save sensor');
$sensors->save(987651,$a,'一号传感器漏水');
$sensors->save(987651,$b,'二号传感器漏水');
check(count($sensors->sensors(987651)['list'])===2 && !$sensors->sensors(987652)['list'], 'sensor lists tenant scoped');
$report=function($id,$state)use($sensors){return $sensors->report(json_encode(['ID'=>$id,'zhuangtai'=>$state]));};
rejects(function()use($sensors,$a){$sensors->report(json_encode(['ID'=>$a,'village_id'=>987652,'zhuangtai'=>1]));}, 'foreign village report rejected');
$service->fake->stopFailure=false; $service->fake->fail=false;
check($report($a,1.00)['ok'] && $service->fake->spoken==='一号传感器漏水' && $service->fake->ttsLed===1, 'wet report speaks registered words and flashes');
$logCount=Db::name('water_alarm_log')->count();
check($report($a,1)['ok'] && Db::name('water_alarm_log')->count()===$logCount, 'repeated report does not restart speech');
check($report($b,1)['ok'] && $service->fake->spoken==='一号传感器漏水', 'second wet sensor does not interrupt current alarm');
check($report($b,0)['ok'] && $service->fake->ttsStatus===2, 'another sensor dry cannot silence wet sensor');
$report($b,1);
check($report($a,0)['ok'] && $service->fake->spoken==='二号传感器漏水', 'recovery switches to remaining wet sensor');
check($report($b,0)['ok'] && $service->fake->ttsStatus===1 && $service->fake->led===0, 'all dry stops sound and light');
$service->fake->fail=true;
check(!$report($a,1)['ok'], 'offline report fails visibly');
check((int)Db::name('water_alarm_sensor')->where('sensor_id',$a)->value('last_state')===1, 'wet state persists even when speaker offline');
$service->fake->fail=false;
check($report($a,1)['ok'] && $service->fake->ttsStatus===2, 'next report retries after connection recovery');
$sensors->save(987651,$a,'新报警词');
check($report($a,1)['ok'] && $service->fake->spoken==='新报警词', 'edited words applied on next report');
echo "All sensor linkage checks passed.\n";
