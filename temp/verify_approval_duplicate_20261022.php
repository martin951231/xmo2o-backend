<?php
require __DIR__.'/../v20/vendor/autoload.php';
$app=new \think\App(__DIR__.'/../v20/'); $app->initialize();
use think\facade\Db;
set_exception_handler(function ($e) { echo $e->getMessage().PHP_EOL.$e->getTraceAsString(); exit(1); });
$config=config('database'); $config['connections']['mysql']=array_merge($config['connections']['mysql'],['hostname'=>'127.0.0.1','database'=>'xmo2o','username'=>'root','password'=>'admin123','hostport'=>'3306','deploy'=>0,'rw_separate'=>false,'params'=>[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY=>true]]);
config($config,'database');
function check($ok,$label){if(!$ok)throw new \RuntimeException($label); echo "PASS ".$label.PHP_EOL;}
$source=Db::name('community_approval_instance')->where('id',1930)->find();
foreach(['community_approval_instance','community_approval_instance_node','community_approval_record'] as $name){
 $table='pigcms_'.$name;
 $ddl=Db::query("SHOW CREATE TABLE ".$table)[0]['Create Table'];
 // Copy data before shadowing the real table.
 $rows=Db::name($name)->select()->toArray();
 Db::execute(preg_replace('/^CREATE TABLE/','CREATE TEMPORARY TABLE',$ddl));
 foreach(array_chunk($rows,100) as $chunk)Db::name($name)->insertAll($chunk);
}
Db::name('community_approval_instance')->where('business_id',22142)->delete();
$svc=new \app\community\model\service\ApprovalFlowService();
$admin=['property_id'=>$source['property_id'],'village_id'=>$source['village_id'],'wid'=>480,'name'=>'Local verification'];
$params=['business_type'=>'order_discard','business_id'=>22142,'change_data'=>json_decode($source['change_data'],true)];
$id=$svc->createInstance($admin,$params)['id'];
check(Db::name('community_approval_instance_node')->where('instance_id',$id)->count()>0,'create approval with node');
foreach([true,false] as $action){
 if(!$action)unset($params['change_data']['action']);
 try{$svc->createInstance($admin,$params);throw new \RuntimeException('duplicate accepted');}
 catch(\Exception $e){check(strpos($e->getMessage(),'请勿重复提交')!==false,'reject duplicate '.($action?'with action':'without action'));}
}
check(Db::name('community_approval_instance')->where('business_id',22142)->count()==1,'only one pending instance');
Db::name('community_approval_instance')->where('id',$id)->update(['status'=>2]);
$svc->createInstance($admin,$params);
check(Db::name('community_approval_instance')->where('business_id',22142)->where('status',0)->count()==1,'new application after rejection');
$a=new PDO('mysql:host=127.0.0.1;dbname=xmo2o','root','admin123',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$b=new PDO('mysql:host=127.0.0.1;dbname=xmo2o','root','admin123',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$a->beginTransaction(); $a->query('SELECT order_id FROM pigcms_house_new_pay_order WHERE order_id=22142 FOR UPDATE');
$b->exec('SET SESSION innodb_lock_wait_timeout=1'); $b->beginTransaction();
try{$b->query('SELECT order_id FROM pigcms_house_new_pay_order WHERE order_id=22142 FOR UPDATE');throw new RuntimeException('lock failed');}
catch(PDOException $e){check($e->errorInfo[1]==1205,'second connection blocked by order lock');}
$b->rollBack();$a->rollBack();
echo "All checks complete; approval writes used TEMPORARY tables only.".PHP_EOL;
