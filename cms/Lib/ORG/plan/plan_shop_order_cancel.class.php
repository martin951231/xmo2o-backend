<?php
//超时自动取消订单
class plan_shop_order_cancel extends plan_base
{
	
	public function runTask()
	{
		$config = D('Config')->field('value')->where(array('name' => 'shop_order_cancel_time'))->find();
		$ctime = $cancel_time = isset($config['value']) ? intval($config['value']) : 0;//60 * C('config.shop_order_cancel_time');
		$cancel_time = $cancel_time * 60;
		if (empty($cancel_time)){
			$res=array();
		}else{
			//检查最近7天数据就行了，否则太多查询太慢
			$time = time();
			$sql = "SELECT o.* FROM " . C('DB_PREFIX') . "shop_order AS o INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop AS s ON s.store_id=o.store_id WHERE o.paid=1 AND o.status=0 AND order_status <>1 AND o.order_from<>1 AND ((s.cancel_time>0 AND {$time}-o.create_time>s.cancel_time*60) OR (s.cancel_time=0 AND o.pay_time>0 AND {$time}-o.pay_time>{$cancel_time})) AND o.create_time >".($time-7*86400);
			$res = D()->query($sql);	
		}
		//拼团超时或失败的订单需要自动退款
		$group_refund_list=D('Mall_group_team')->get_group_refund();
		$res=array_merge($res,$group_refund_list);
		$goods_db = D('Shop_goods');
		$order_detail_db = D('Shop_order_detail');
		$order_db = D('Shop_order');
		$order_log_db = D('Shop_order_log');
		fdump($sql,'shop_order_cancel_log',1);
		foreach ($res as $row) {
			$this->keepThread();
            //快递配送不需要接单直接发货，所以没有店铺超时接单
            if($row['is_pick_in_store'] == 3){
                continue;
            }
			if($row['group_id']>0||$row['order_from']=='1'){
				$order_db->where(array('order_id' => $row['order_id']))->save(array('status' => 5, 'cancel_type' => 8, 'is_rollback' => 1));//取消类型（0:pc店员，1:wap店员，2:andriod店员,3:ios店员，4：打包app店员，5：用户，6：配送员, 7:超时取消,8:拼团失败取消）
				$order_log_db->add_log(array('order_id' => $row['order_id'], 'status' => 10, 'name' => '拼团失败系统自动取消', 'phone' => '', 'note' => '拼团失败自动取消订单 '));
			}else{
				$order_db->where(array('order_id' => $row['order_id']))->save(array('status' => 5, 'cancel_type' => 7, 'is_rollback' => 1));//取消类型（0:pc店员，1:wap店员，2:andriod店员,3:ios店员，4：打包app店员，5：用户，6：配送员, 7:超时取消）
				$order_log_db->add_log(array('order_id' => $row['order_id'], 'status' => 10, 'name' => '店员接单超时系统自动取消', 'phone' => '', 'note' => '店铺超时未接单自动取消订单 (' . $ctime . '分钟)'));	
			}
            D('System_order')->save_order_status($row['order_from']=='1' ? 'mall' : 'shop' , $row['order_id'], 5);
			
			if (($row['paid'] == 1 || $row['reduce_stock_type'] == 1) && $row['is_rollback'] == 0) {
				$details = $order_detail_db->field(true)->where(array('order_id' => $row['order_id']))->select();
				foreach ($details as $menu) {
					$goods_db->update_stock($menu, 1);//修改库存
				}
			}
			$row['is_rollback'] = 1;
			$row['status'] = 5;
			$order_db->check_refund($row);
		}
		
// 		foreach ($res as $row) {
// 			$this->keepThread();
// 			D('Shop_order')->where(array('order_id' => $row['order_id']))->save(array('status' => 5));
// 			D('Shop_order_log')->add_log(array('order_id' => $row['order_id'], 'status' => 10, 'name' => '店员接单超时系统自动取消', 'phone' => ''));
// 		}
		return true;
	}
}
?>