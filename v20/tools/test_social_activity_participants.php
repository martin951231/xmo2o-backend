<?php
// All writes target connection-local TEMPORARY tables, discarded when the process exits.
require dirname(__DIR__) . '/vendor/autoload.php';
$app = new \think\App(dirname(__DIR__) . DIRECTORY_SEPARATOR);
$app->initialize();
request()->agent = 'wechat_mini';
set_exception_handler(function ($e) { echo get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n"; exit(1); });
use think\facade\Db;
use app\social_activity\model\service\SocialActivityOrderService;
use app\social_activity\model\service\SocialActivityParticipantService;
use app\social_activity\model\service\SocialActivityService;
use app\social_activity\model\service\SocialActivityRefundPolicy;

function ok($value, $label) { if (!$value) { throw new RuntimeException('FAIL: ' . $label); } echo 'PASS: ' . $label . "\n"; }
function rejects($fn, $label) { try { $fn(); } catch (Exception $e) { echo 'PASS: ' . $label . "\n"; return; } throw new RuntimeException('FAIL expected rejection: ' . $label); }
$prefix = config('database.connections.' . config('database.default') . '.prefix');
if (in_array('--schema', $argv, true)) {
    foreach (Db::query("SHOW COLUMNS FROM `{$prefix}social_activity_order`") as $column) {
        if ($column['Null'] === 'NO' && $column['Default'] === null) echo $column['Field'] . ':' . $column['Type'] . "\n";
    }
    exit;
}
if (!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) { throw new RuntimeException('Invalid prefix'); }
$templates = [];
$tables = ['user','house_village_user_bind','social_activity','social_activity_category','social_activity_session','social_activity_session_ticket','social_activity_order','social_activity_participant','social_activity_views','social_activity_refund'];
foreach ($tables as $name) {
    echo 'Preparing temporary ' . $name . "\n";
    $templates[$name] = Db::name($name)->limit(1)->select()->toArray()[0] ?? [];
    $ddl = Db::query("SHOW CREATE TABLE `{$prefix}{$name}`")[0]['Create Table'];
    Db::execute(preg_replace('/^CREATE TABLE/', 'CREATE TEMPORARY TABLE', $ddl));
}
function fixture($table, array $data) {
    global $templates;
    $row = array_merge($templates[$table], $data);
    return Db::name($table)->insertGetId($row);
}
$village = Db::name('house_village')->where('status', 1)->value('village_id');
if (!$village) { throw new RuntimeException('No active village available'); }
$payer=910001; $family=910002; $tenant=910003; $stranger=910004; $later=910005;
foreach ([$payer,$family,$tenant,$stranger,$later] as $uid) {
 fixture('user',['uid'=>$uid,'phone'=>'','nickname'=>'Test participant '.$uid,'birthday'=>'1990-01-01']);
}
$bindIds=[920001,920002,920003,920004];
foreach ([$payer,$family,$tenant,0] as $i=>$uid) {
 fixture('house_village_user_bind',['pigcms_id'=>$bindIds[$i],'uid'=>$uid,'village_id'=>$village,'vacancy_id'=>990001,
    'status'=>1,'type'=>[$i===0?0:1,1,2,1][$i], 'name'=>'Test person '.$i,'phone'=>'',
    'single_id'=>0,'floor_id'=>0,'layer_id'=>0,'address'=>'Test room',
    'id_card'=>'11010119900101'.sprintf('%03d', $i+1).'X']);
}
fixture('social_activity_category',['id'=>930001,'name'=>'Test category']);
fixture('social_activity',['id'=>940001,'name'=>'Test activity','status'=>10,'category_id'=>930001,
    'host_village_id'=>$village,'form_id'=>0,'start_time'=>date('Y-m-d'),'end_time'=>date('Y-m-d',time()+86400*3),
    'poster'=>'','position_poster'=>'','content'=>'','rule_id'=>0]);
fixture('social_activity_session',['id'=>950001,'activity_id'=>940001,'name'=>'Test session',
    'village_id'=>(string)$village,'start_time'=>date('Y-m-d H:i:s',time()+3600),'end_time'=>date('Y-m-d H:i:s',time()+7200),
    'ticket_start_time'=>date('Y-m-d H:i:s',time()-3600),'ticket_end_time'=>date('Y-m-d H:i:s',time()+3600),
    'identity'=>1,'gender'=>1,'age_min'=>0,'age_max'=>0,'location_id'=>0,'location'=>'Test venue']);
fixture('social_activity_session_ticket',['id'=>960001,'activity_id'=>940001,'session_id'=>950001,'name'=>'Free','price'=>0,'num'=>5]);
fixture('social_activity_session_ticket',['id'=>960002,'activity_id'=>940001,'session_id'=>950001,'name'=>'Paid','price'=>12.50,'num'=>2]);
$service=new SocialActivityOrderService(); $people=new SocialActivityParticipantService();
$params=['uid'=>$payer,'activity_id'=>940001,'session_id'=>950001,'ticket_id'=>960001,'village_id'=>$village,
    'pigcms_id'=>920001,'is_save'=>0,'participants'=>[['bind_id'=>920001],['bind_id'=>920002]]];
$options=$people->options($params);
ok(array_column($options['groups'],'name')===['业主','家属','租客'],'owner/family/tenant grouping');
$staleRoom = $params; $staleRoom['uid'] = $stranger;
$staleOptions = $people->options($staleRoom);
ok(!$staleOptions['has_bound_room'] && !$staleOptions['groups'] && $staleOptions['allow_external'], 'stale room cannot expose members or block unrestricted signup');
rejects(function()use($people,$staleRoom){$people->resolve($staleRoom);},'stale room cannot submit bound participants');
$preview=$service->saveOrder($params);
ok($preview['buy_num']===2 && count($preview['participants'])===2,'preview derives quantity from participants');
$invalid=$params; $invalid['participants'][]=['bind_id'=>920001];
rejects(function()use($service,$invalid){$service->saveOrder($invalid);},'duplicate person in request');
$invalid=$params; $invalid['participants'][]=['bind_id'=>999999];
rejects(function()use($service,$invalid){$service->saveOrder($invalid);},'forged participant ID');
$params['is_save']=1; $params['buy_num']=999;
$created=$service->saveOrder($params); $orderId=$created['order_id'];
$order=Db::name('social_activity_order')->where('id',$orderId)->find();
ok((int)$order['num']===2 && (int)$order['paid']===1 && (int)$order['uid']===$payer,'free group signup and payer retained, client quantity ignored');
ok(Db::name('social_activity_participant')->where('order_id',$orderId)->count()===2,'two participant records committed');
$member=$params; $member['uid']=$family; $member['pigcms_id']=920002; $member['participants']=[['bind_id'=>920002]]; $member['ticket_id']=960002;
rejects(function()use($service,$member){$service->saveOrder($member);},'self signup after proxy signup, even with a different ticket');
$list=$service->getMyOrderList(['uid'=>$family,'page'=>1,'pageSize'=>20])->toArray();
ok(count($list['data'])===1,'participant can see proxy order in my activities');
ok(count($list['data'][0]['participants'])===2 && $list['data'][0]['is_payer']===false,'my activities contains the full participant roster');
ok(!$people->scope(Db::name('social_activity_order'),$stranger)->count(),'unrelated account cannot see order');
$detail=(new SocialActivityService())->getActivityDetail(['id'=>940001,'uid'=>$family,'order_id'=>$orderId,'session_date'=>'']);
ok($detail['order_info']['is_payer']===false && count($detail['order_info']['participants'])===2,'participant detail includes all attendees');
ok(!isset($detail['order_info']['id_number']) && !isset($detail['order_info']['paid_extra']),'payer and primary attendee private fields removed');
rejects(function()use($order,$family){SocialActivityRefundPolicy::validate($order,$family,time());},'participant cannot refund payer order');
$paid=$params; $paid['ticket_id']=960002; $paid['participants']=[['bind_id'=>920003],['bind_id'=>920004]];
$paidResult=$service->saveOrder($paid); $paidId=$paidResult['order_id'];
$paidOrder=Db::name('social_activity_order')->where('id',$paidId)->find();
ok((float)$paidOrder['amount']===25.0 && (int)$paidOrder['num']===2 && !(int)$paidOrder['paid'],'paid group total and pending state');
ok(!$people->scope(Db::name('social_activity_order'),0)->count(),'anonymous user cannot inherit unregistered members');
rejects(function()use($paidId){(new SocialActivityService())->getActivityDetail(['id'=>940001,'uid'=>0,'order_id'=>$paidId,'session_date'=>'']);},'anonymous detail access rejected');
rejects(function()use($service,$paid){$service->saveOrder($paid);},'pending order prevents duplicate');
Db::name('house_village_user_bind')->where('pigcms_id',920004)->update(['uid'=>$later]);
ok($people->scope(Db::name('social_activity_order'),$later)->where('id',$paidId)->count()===1,'member linked to account after signup can see order');
Db::name('social_activity_order')->where('id',$paidId)->update(['status'=>20]);
$paid['is_save']=0; ok($service->saveOrder($paid)['buy_num']===2,'cancelled order releases people and inventory');
Db::name('social_activity_session_ticket')->where('id',960002)->update(['num'=>1]);
rejects(function()use($service,$paid){$service->saveOrder($paid);},'insufficient stock rejects the entire group');
ok(Db::transaction(function()use($people,$family){return $people->sign(940001,950001,$family);})===1,'participant signs only their seat');
ok((int)Db::name('social_activity_participant')->where(['order_id'=>$orderId,'uid'=>$payer])->value('is_sign')===0,'other person remains unsigned');
ok((int)Db::name('social_activity_order')->where('id',$orderId)->value('is_sign')===1,'partial usage blocks whole-order refund');
$rosterParams=['page'=>1,'pageSize'=>20,'session_id'=>950001,'start_date'=>'','end_date'=>'','check_in_status'=>-1];
$roster=(new SocialActivityService())->getSignUpList($rosterParams);
ok((int)$roster['signUpNum']===2 && (int)$roster['checkInNum']===1,'admin statistics count people, including partial group attendance');
ok(count($roster['data'][0]['participants'])===2 && (int)$roster['data'][0]['is_sign']===0,'admin roster retains all people and unfinished sign action');
$rosterParams['check_in_status']=0;
ok(count((new SocialActivityService())->getSignUpList($rosterParams)['data'])===1,'unsigned filter includes partially signed groups');
ok(Db::transaction(function()use($people,$payer){return $people->sign(940001,950001,$payer);})===1,'second participant signs independently');
ok(Db::transaction(function()use($people,$payer){return $people->sign(940001,950001,$payer);})===0,'repeat sign is idempotent');
$familyList=$service->getMyOrderList(['uid'=>$family,'page'=>1,'pageSize'=>20])->toArray();
ok((int)$familyList['data'][0]['is_sign']===1,'my activity list reflects personal sign status');
// Old orders have no participant rows but must still prevent duplicate participation.
$legacy=$paidOrder; unset($legacy['id']);
$legacy['uid']=$tenant; $legacy['num']=1; $legacy['amount']=12.5; $legacy['status']=0;
$legacy['order_sn']='TEST-LEGACY'; $legacy['create_time']=date('Y-m-d H:i:s',time()-1800);
$legacyId=Db::name('social_activity_order')->insertGetId($legacy);
ok((bool)$people->duplicate(940001,['uid'=>$tenant,'pigcms_id'=>920003,'id_card'=>'']),'legacy pending order prevents duplicate');
$service->autoCancelOrder();
ok((int)Db::name('social_activity_order')->where('id',$legacyId)->value('status')===20,'expired unpaid order is cancelled');
ok((int)Db::name('social_activity_order')->where('id',$orderId)->value('status')===10,'paid order remains active during timeout cleanup');
ok(!$people->duplicate(940001,['uid'=>$tenant,'pigcms_id'=>920003,'id_card'=>'']),'expired legacy reservation releases participant');
Db::name('social_activity_order')->where('id',$paidId)->update(['status'=>10,'paid'=>1]);
Db::name('social_activity_session')->where('id',950001)->update(['start_time'=>date('Y-m-d H:i:s',time()-60)]);
Db::transaction(function()use($people,$tenant){return $people->sign(940001,950001,$tenant);});
(new SocialActivityService())->signOrder(['order_id'=>$paidId]);
ok(Db::name('social_activity_participant')->where(['order_id'=>$paidId,'is_sign'=>0])->count()===0,'admin whole-order sign completes remaining participants');
// External participants: unrestricted sessions only, complete identity fields, multiple people per order.
Db::name('social_activity_order')->where('activity_id',940001)->update(['status'=>20]);
Db::name('social_activity_session_ticket')->where('id',960002)->update(['num'=>10]);
Db::name('user')->where('uid',$stranger)->update(['phone'=>'13800001001']);
$external=$params;
$external['ticket_id']=960002;
$external['pigcms_id']=0;
$external['village_id']=0;
Db::name('house_village_user_bind')->where('uid', $payer)->delete();
$external['participants']=[
    ['is_external'=>true,'user_name'=>'External A','phone'=>'13800001001','id_number'=>'11010119900101101X'],
    ['is_external'=>true,'user_name'=>'External B','phone'=>'13800001002','id_number'=>'11010119900101102X'],
];
ok($people->options($external)['allow_external']===true,'unrestricted session exposes external entry without a room');
foreach (['user_name','phone','id_number'] as $required) {
    $missing=$external;$missing['participants'][0][$required]=' ';
    rejects(function()use($service,$missing){$service->saveOrder($missing);},'external required field '.$required);
}
Db::name('social_activity_session')->where('id',950001)->update(['identity'=>2]);
$roomOptions=$external;$roomOptions['uid']=$family;$roomOptions['village_id']=$village;$roomOptions['pigcms_id']=920002;
ok($people->options($roomOptions)['allow_external']===false,'owner-only activity hides external entry');
rejects(function()use($people,$external){$people->options($external);},'owner-only activity requires an authenticated room');
$boundOnly = $roomOptions; $boundOnly['is_save'] = 0;
$boundOnly['participants'] = [['bind_id'=>920002], ['bind_id'=>920003]];
ok(count($service->checkOrder($boundOnly)['participants']) === 2, 'owner-only session allows family and tenant of the current room');
rejects(function()use($service,$external){$service->saveOrder($external);},'forged external submission to owner-only session rejected');
Db::name('social_activity_session')->where('id',950001)->update(['identity'=>1]);
$externalResult=$service->saveOrder($external);$externalId=$externalResult['order_id'];
ok(Db::name('social_activity_participant')->where(['order_id'=>$externalId,'bind_id'=>0])->count()===2,'two external attendees saved on one order');
ok((float)Db::name('social_activity_order')->where('id',$externalId)->value('amount')===25.0,'external group total matches ticket count');
rejects(function()use($service,$external){$service->saveOrder($external);},'external pending signup prevents duplicate');
ok($people->scope(Db::name('social_activity_order'),$stranger)->where('id',$externalId)->count()===1,'external attendee account is linked by phone');
Db::name('user')->where('uid',$later)->update(['phone'=>'13800001002']);
ok($people->scope(Db::name('social_activity_order'),$later)->where('id',$externalId)->count()===1,'external attendee registering later can see the order');
$service->assertCreator($externalId,$payer);
ok(true,'creator may enter payment');
rejects(function()use($service,$externalId,$stranger){$service->assertCreator($externalId,$stranger);},'external attendee cannot pay or request refund');
rejects(function()use($service,$externalId){$service->assertCreator($externalId,0);},'anonymous user cannot pay');
// Exercise the actual shared payment entry; the rejection must happen before gateway or balance access.
request()->log_uid=$stranger;
request()->withGet(['order_type'=>'social_activity','order_id'=>$externalId]);
$merged=new ReflectionProperty(request(),'mergeParam');$merged->setAccessible(true);$merged->setValue(request(),false);
$payController=(new ReflectionClass(\app\pay\controller\PayController::class))->newInstanceWithoutConstructor();
$compute=new ReflectionMethod($payController,'compute');$compute->setAccessible(true);
$rejected=$compute->invoke($payController)->getData();
ok((int)$rejected['status']===1005 && strpos($rejected['msg'],'只有创建订单的人')!==false,'payment API rejects another attendee');
Db::name('social_activity_order')->where('id',$externalId)->update(['paid'=>1,'status'=>10]);
$creatorDetail=(new SocialActivityService())->getActivityDetail(['id'=>940001,'uid'=>$payer,'order_id'=>$externalId,'session_date'=>'']);
$attendeeDetail=(new SocialActivityService())->getActivityDetail(['id'=>940001,'uid'=>$stranger,'order_id'=>$externalId,'session_date'=>'']);
ok($creatorDetail['order_info']['participants']===$attendeeDetail['order_info']['participants'],'creator and external attendee see identical full attendee list');
foreach (['ticket_name','session_name','num','amount'] as $field) {
    ok($creatorDetail['order_info'][$field]===$attendeeDetail['order_info'][$field],'shared ticket field '.$field);
}
ok((int)$attendeeDetail['can_refund']===0,'non-creator has no refund permission');
$laterList=$service->getMyOrderList(['uid'=>$later,'page'=>1,'pageSize'=>100])->toArray();
$shared=array_values(array_filter($laterList['data'],function($o)use($externalId){return $o['id']==$externalId;}));
ok(count($shared)===1 && count($shared[0]['participants'])===2,'later account sees the entire group in my activities');
ok(Db::transaction(function()use($people,$stranger){return $people->sign(940001,950001,$stranger);})===1,'external attendee signs only their own ticket');
ok(Db::name('social_activity_participant')->where(['order_id'=>$externalId,'is_sign'=>0])->count()===1,'other external ticket remains unsigned');

echo "All integration checks passed; temporary tables disappear on disconnect.\n";
