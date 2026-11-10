<?php
// Isolated MySQL only. No physical device is used.
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = new \think\App(dirname(__DIR__, 2) . '/'); $app->initialize();
use think\facade\Db;
use think\facade\Config;
use app\community\model\service\WaterAlarmService;

$database = Config::get('database');
$database['connections']['mysql'] = array_merge($database['connections']['mysql'], [
    'hostname'=>'127.0.0.1','hostport'=>'13308','database'=>'visitor_register_test',
    'username'=>'root','password'=>'','prefix'=>'pigcms_','deploy'=>0,'rw_separate'=>false,
]);
Config::set($database, 'database');
$config = Config::get('water_alarm');
$config['host'] = '127.0.0.1'; $config['port'] = 19999;
Config::set($config, 'water_alarm');
function check($condition, $label) { if (!$condition) { throw new RuntimeException('FAIL '.$label); } echo 'PASS '.$label.PHP_EOL; }
function rejects($callback, $label) { try { $callback(); } catch (InvalidArgumentException $e) { check(true,$label); return; } throw new RuntimeException('FAIL '.$label); }
class FakeWaterTransport {
    public $volume=0, $led=0, $track=0, $fail=false, $calls=[];
    public $ttsStatus=1, $ttsVolume=1, $ttsLed=0, $spoken='', $stopFailure=false;
    public function readState($includeTts = false) {
        if ($this->fail) { throw new RuntimeException('模拟回复超时'); }
        $active=$this->ttsStatus===2;
        return ['volume'=>$active?$this->ttsVolume:$this->volume,'normal_volume'=>$this->volume,'led'=>$this->led,'track'=>$this->track,'alarm_active'=>($this->track & 255)>0 || $active,'flash_active'=>$this->led>0 || ($active && $this->ttsLed>0), 'tts_status'=>$this->ttsStatus,'tts_active'=>$active,'tts_volume'=>$this->ttsVolume,'tts_led'=>$this->ttsLed];
    }
    public function writeRegisters($address, $values) {
        $this->calls[] = [$address,$values];
        foreach ($values as $index=>$value) { $key = [1=>'volume',2=>'led',3=>'track'][$address+$index]; $this->$key=$value; }
    }
    public function setVolume($value) { $this->volume = min(7,$value); }
    public function writeSingle($address,$value) { if($address===0x22){$this->ttsVolume=min(7,$value);}if($address===0x23){$this->ttsLed=$value;} }
    public function stopTts() { if($this->stopFailure){throw new RuntimeException('TTS停止失败');}$this->ttsStatus=1; }
    public function speak($text,$volume) {$this->spoken=$text;$this->ttsVolume=$volume;$this->ttsLed=1;$this->ttsStatus=2;}
    public function close() {}
}
class FakeWaterService extends WaterAlarmService {
    public $fake;
    public function __construct() { parent::__construct(); $this->fake = new FakeWaterTransport(); }
    protected function transport() { return $this->fake; }
}
$sql = file_get_contents(dirname(__DIR__,3).'/sqls/package/release/2026-10-15_water_alarm.sql');
preg_match_all('/CREATE TABLE IF NOT EXISTS `([^`]+)`\s*\(.*?ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;/s', $sql, $matches);
check(count($matches[0])===2,'two dedicated tables');
foreach ($matches[0] as $statement) { Db::execute(str_replace('CREATE TABLE IF NOT EXISTS','CREATE TEMPORARY TABLE',$statement)); }
$ttsSql=file_get_contents(dirname(__DIR__,3).'/sqls/package/release/2026-10-15_water_alarm_tts.sql');
$ttsSql=preg_replace('/^--.*$/m','',$ttsSql);
Db::execute(str_replace('CREATE TABLE IF NOT EXISTS','CREATE TEMPORARY TABLE',$ttsSql));
$service = new FakeWaterService();
$list = $service->save(987651,['name'=>'地下车库报警器','host'=>'attacker.invalid','volume'=>31]);
$id = $list['list'][0]['id'];
check($list['list'][0]['volume']==1 && !$list['can_add'],'default minimum volume and unique endpoint');
check($list['list'][0]['host']==='127.0.0.1','HTTP host override ignored');
check(count($service->fake->calls)===0,'adding device does not activate hardware');
rejects(function()use($service){$service->save(987652,['name'=>'重复设备']);},'physical endpoint cannot be bound twice');
rejects(function()use($service,$id){$service->operate(987652,$id,'stop',null,1,6);},'cross-village control denied');
rejects(function()use($service,$id){$service->logs(987652,$id);},'cross-village logs denied');
rejects(function()use($service,$id){$service->save(987652,['id'=>$id,'name'=>'越权']);},'cross-village rename denied');
$operate = function($action,$volume=null)use($service,$id){return $service->operate(987651,$id,$action,$volume,17,6);};
$service->fake->track=0xff01;
check($service->syncLeakFlash(true)['ok'] && $service->fake->led===1 && $service->fake->track===0xff01, 'wet report starts light without changing audio');
$before=count($service->fake->calls);
check($service->syncLeakFlash(true)['ok'] && count($service->fake->calls)===$before, 'repeated wet report does not resend light command');
check($service->syncLeakFlash(false)['ok'] && $service->fake->led===0 && $service->fake->track===0xff01, 'dry report stops light without changing audio');
$service->fake->fail=true;
check(!$service->syncLeakFlash(true)['ok'], 'offline linkage failure is reported');
$service->fake->fail=false;
check($service->syncLeakFlash(true)['ok'] && $service->fake->led===1, 'next report retries failed linkage');
$service->syncLeakFlash(false);
$service->fake->track=0;
$service->fake->calls=[];
$operate('refresh');
check($service->devices(987651)['list'][0]['volume']==1,'passive read preserves configured nonmute default');
$result=$operate('alarm_start');
check($result['ok'] && $service->fake->calls[0]===[1,[1,1,0xff01]],'alarm has lowest nonmute volume and infinite first track');
$result=$operate('flash_start');
check($result['ok'] && !$result['device']['state']['alarm_active'] && $result['device']['state']['flash_active'],'flash mode stops audio');
$result=$operate('flash_stop'); check($result['ok'] && !$result['device']['state']['flash_active'],'flash stop');
$result=$operate('volume',31); check($result['ok'] && $result['device']['volume']==7,'device volume clamp persisted');
$operate('alarm_start'); check($service->fake->volume===7,'alarm uses adjusted volume');
$result=$operate('stop'); check($result['ok'] && !$result['device']['state']['alarm_active'] && !$result['device']['state']['flash_active'],'stop clears sound and light');
$service->fake->fail=true;
$result=$operate('alarm_start'); check(!$result['ok'] && !$result['device']['state_known'] && $result['device']['state']===null,'lost acknowledgement is never presented as stopped');
$logs=$service->logs(987651,$id)['list']; check($logs[0]['result']==='unknown' && $logs[0]['operator_id']==17,'uncertain command audited with operator');
$service->fake->fail=false;
check($operate('stop')['ok'],'stop remains possible after uncertain command');
rejects(function()use($operate){$operate('volume',0);},'mute forbidden');
rejects(function()use($operate){$operate('reboot');},'unsupported action forbidden');
$lock='water_alarm_'.sha1('127.0.0.1:19999:1');
$pdo=new PDO('mysql:host=127.0.0.1;port=13308;dbname=visitor_register_test','root','');
$stmt=$pdo->prepare('SELECT GET_LOCK(?,0)'); $stmt->execute([$lock]);
rejects(function()use($operate){$operate('alarm_start');},'concurrent request rejected before sending');
$stmt=$pdo->prepare('SELECT RELEASE_LOCK(?)'); $stmt->execute([$lock]);
check($operate('refresh')['ok'],'lock released after completed request');
$texts=$service->addText(987651,$id,'地下车库发生水浸，请及时处理。');
$textId=$texts['list'][0]['id'];
check(count($service->texts(987651,$id)['list'])===1,'text persisted and listed');
rejects(function()use($service,$id){$service->addText(987651,$id,'地下车库发生水浸，请及时处理。');},'duplicate text denied');
rejects(function()use($service,$id){$service->addText(987651,$id,str_repeat('水',67));},'UTF8 byte length enforced');
rejects(function()use($service,$id){$service->addText(987651,$id,'[v10]恶意控制');},'inline synthesizer overrides rejected');
rejects(function()use($service,$id){$service->texts(987652,$id);},'cross-village text list denied');
rejects(function()use($service,$id){$service->addText(987652,$id,'越权');},'cross-village text write denied');
Db::name('water_alarm_text')->insert(['village_id'=>987652,'device_id'=>$id,'content'=>'其他小区','create_time'=>time()]);
$otherText=Db::name('water_alarm_text')->where('village_id',987652)->value('id');
rejects(function()use($service,$id,$otherText){$service->operate(987651,$id,'tts_start',null,17,6,$otherText);},'foreign text cannot be played on owned device');
$speak=function()use($service,$id,$textId){return $service->operate(987651,$id,'tts_start',null,17,6,$textId);};
$result=$speak();
check($result['ok'] && $result['device']['state']['tts_active'] && $service->fake->track===0 && $service->fake->spoken==='地下车库发生水浸，请及时处理。','saved text starts TTS and stops ordinary track');
check(strpos($service->logs(987651,$id)['list'][0]['message'],'地下车库')!==false,'spoken content included in audit');
$result=$operate('volume',2);check($result['ok'] && $service->fake->ttsVolume===2,'active TTS volume adjusted');
$result=$operate('flash_stop');check($result['ok'] && $result['device']['state']['tts_active'] && !$result['device']['state']['flash_active'],'TTS light stops independently');
check($operate('stop')['ok'] && $service->fake->ttsStatus===1,'stop also stops TTS');
$speak();check($operate('alarm_start')['ok'] && $service->fake->ttsStatus===1,'ordinary alarm replaces TTS');
$speak();check($operate('flash_start')['ok'] && $service->fake->ttsStatus===1,'flash only replaces TTS');
$service->fake->stopFailure=true;$service->fake->track=0xff01;$service->fake->led=1;
$result=$operate('stop');check(!$result['ok'] && $service->fake->track===0 && $service->fake->led===0,'ordinary stop still attempted after TTS stop failure');
$service->fake->stopFailure=false;
rejects(function()use($service,$id,$textId){$service->deleteText(987652,$id,$textId);},'cross-village delete denied');
rejects(function()use($service,$id,$otherText){$service->deleteText(987651,$id,$otherText);},'foreign text delete denied on owned device');
$speak();
$remaining=$service->deleteText(987651,$id,$textId);
check(count($remaining['list'])===0 && $service->fake->ttsStatus===2,'delete removes saved text without stopping hardware');
rejects(function()use($service,$id,$textId){$service->deleteText(987651,$id,$textId);},'repeated deletion reports missing text');
rejects($speak,'deleted text cannot be played again');
$menuTable='water_alarm_menu_test_'.getmypid();
try {
    $columns=['name','module','action','select_module','select_action','choose_page','icon','jump_url','application_txt','component','path'];
    $definitions=['id int AUTO_INCREMENT PRIMARY KEY','fid int','sort int','`show` int','status int','type int','jump_type int','application_id int','is_hide int'];
    foreach($columns as $column){$definitions[]='`'.$column.'` varchar(255) NOT NULL DEFAULT ""';}
    Db::execute('CREATE TABLE `'.$menuTable.'` ('.implode(',',$definitions).') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    Db::execute("INSERT INTO `".$menuTable."` (id,fid,name,status) VALUES(284,0,'智能硬件',1)");
    $insert=substr($sql,strpos($sql,'INSERT INTO `pigcms_house_menu_new`'));
    $insert=str_replace('`pigcms_house_menu_new`','`'.$menuTable.'`',$insert);
    Db::execute($insert); Db::execute($insert);
    $menus=Db::query('SELECT fid,component,path FROM `'.$menuTable.'` WHERE component=?',['waterAlarm']);
    check(count($menus)===1 && $menus[0]['fid']==284 && $menus[0]['path']==='/village/village.waterAlarm/index','menu migration is idempotent under smart hardware');
} finally { Db::execute('DROP TABLE IF EXISTS `'.$menuTable.'`'); }
echo 'All service checks passed.'.PHP_EOL;
