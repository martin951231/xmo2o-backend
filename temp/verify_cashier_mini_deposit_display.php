<?php
require __DIR__ . '/../v20/vendor/autoload.php';
$app = new \think\App(__DIR__ . '/../v20/');
$app->initialize();
$service = new \app\community\model\service\HouseNewCashierService();
$row = \think\facade\Db::name('house_new_pay_order')->where('order_type','deposit_bill')->where('is_paid',2)->where('is_discard',1)->order('order_id DESC')->find();
if (!$row) throw new Exception('No pending deposit');
$params = ['village_id'=>$row['village_id'], 'vacancy_id'=>$row['room_id'], 'charge_type'=>'deposit_bill', 'project_id'=>-1];
$projects=$service->getOrderProjectList($params);
if ($projects[0]['value'] !== '押金账单') throw new Exception('Project missing');
$rules=$service->getOrderRuleList($params);
if (!$rules) throw new Exception('Categories missing');
foreach ($rules as $rule) {
 $params['rule_id']=$rule['key'];
 $orders=$service->getChargeOrderList($params);
 if (!$orders) throw new Exception('No category orders');
 foreach ($orders as $order) {
  if ($order['deposit_category_name']!==$rule['value']) throw new Exception('Category mismatch');
 }
 echo 'Mini program: 押金账单 / '.$rule['value'].' / '.count($orders).' bills'.PHP_EOL;
}
$detail=$service->getOrder(['o.order_id'=>$row['order_id']], 'o.*,r.charge_name,p.name as project_name,p.type', 1);
if ($detail[0]['list'][0]['val']!==$row['order_name'] || $detail[0]['list'][1]['val']!=='押金账单') throw new Exception('Detail display missing');
echo "Mini program detail OK\n";
// Run the exact cashier output-formatting block without the billing method's existing write side effects.
$src=file_get_contents(__DIR__.'/../v20/app/community/model/service/HouseNewCashierService.php');
$start=strpos($src,'        foreach (($data ?: []) as $displayKey');
$end=strpos($src,"        $"."res['order_list'] = $".'data;', $start);
$block=substr($src,$start,$end-$start);
$format=\Closure::bind(function($data) use ($block) { eval($block); return $data; },$service,get_class($service));
$output=$format([array_merge($row,['children'=>[$row]])]);
if ($output[0]['charge_name']!=='押金账单'||$output[0]['rule_name']!==$row['order_name']||$output[0]['children'][0]['rule_name']!==$row['order_name']) throw new Exception('Cashier display mismatch');
echo "Cashier parent/child display OK\n";
$bind=\think\facade\Db::name('house_village_user_bind')->where('vacancy_id',$row['room_id'])->where('village_id',$row['village_id'])->where('status',1)->find();
if ($bind) {
 $types=$service->getOrderChargeTypeList($bind['pigcms_id']);
 if (!in_array('deposit_bill',array_column($types['charge_type'],'key'))) throw new Exception('Deposit type missing');
 echo "Mini program category entry OK\n";
}
