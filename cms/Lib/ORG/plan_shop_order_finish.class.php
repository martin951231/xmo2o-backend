<?php
class plan_shop_order_finish extends plan_base
{
	public function runTask()
	{
	    $config = D('Config')->field('value')->where(array('name' => 'shop_order_finish'))->find();
	    $finishTime = isset($config['value']) ? intval($config['value']) : 0;//60 * C('config.shop_order_cancel_time');

	    //商城订单
	    $mall_order_finish=D('Config')->where(array('name'=>'mall_order_finish'))->getfield('value');
	    $mall_finish_time=intval($mall_order_finish);
	    //商城或快店订单设置了自动收货时间
	    if ($finishTime == 0 && $mall_finish_time==0) return true;
	    $where=array('is_pick_in_store' => 3, 'status' => 1);
	    $nowTime = time();
	    $where_str='';
	    if($finishTime>0){
	    	$finishTime = $finishTime * 86400;
	    	$time = $nowTime - $finishTime;
	    	$where_str.='(order_from!=1 and last_time<'.$time.')';
	    }
	    if($mall_finish_time>0){
	    	$mall_finish_time=$mall_finish_time*86400;
	    	$mall_time=$nowTime-$mall_finish_time;
	    	if(!empty($where_str)){
	    		$where_str.=' or ';
	    	}
	    	$where_str.='(order_from=1 and last_time<'.$mall_time.')';
	    }
	    if(empty($where_str)){
	    	return false;
	    }
	    $where['_string']=$where_str;
	    $res = D('Shop_order')->field(true)->where($where)->order('last_time ASC')->limit(10)->select();

	    $order_db = D('Shop_order');
		$order_log_db = D('Shop_order_log');
		foreach ($res as $row) {
			$this->keepThread();
			$order_db->where(array('order_id' => $row['order_id']))->save(array('status' => 2, 'use_time' => $nowTime, 'last_time' => $nowTime));
			$order_db->shop_notice($row);
			if($res['order_from']==1){
				$order_log_db->add_log(array('order_id' => $row['order_id'], 'status' => 7, 'name' => '超时未确认收货系统自动确认收货并消费', 'phone' => '', 'note' => '超时未确认收货，系统自动收货 (' . $mall_order_finish. '天)'));
			}else{
				$order_log_db->add_log(array('order_id' => $row['order_id'], 'status' => 7, 'name' => '超时未确认收货系统自动确认收货并消费', 'phone' => '', 'note' => '超时未确认收货，系统自动收货 (' . $config['value']. '天)'));
			}
		}
		return true;
	}
}
?>