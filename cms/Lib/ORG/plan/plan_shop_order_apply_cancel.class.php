<?php
//商城订单：用户申请取消订单，超过三天店员未同意，自动取消订单
class plan_shop_order_apply_cancel extends plan_base
{
	
	public function runTask()
	{
		
		$time = time();
		$cancle_time = 60*60*24*3;//天
		$apply_cancle_time = $time-$cancle_time;
		$sql = "SELECT o.* FROM " . C('DB_PREFIX') . "shop_order AS o INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop AS s ON s.store_id=o.store_id WHERE o.paid=1 AND o.status=11 AND o.order_from=1 AND apply_cancle_time < {$apply_cancle_time} AND apply_cancle_time >0";
		$res = D()->query($sql);	

		$goods_db = D('Shop_goods');
		$order_detail_db = D('Shop_order_detail');
		$order_db = D('Shop_order');
		$order_log_db = D('Shop_order_log');

		fdump($sql,'shop_order_apply_cancel_log',1);

		foreach ($res as $row) {
			$this->keepThread();
			$order_db->where(array('order_id' => $row['order_id']))->save(array('status' => 5, 'cancel_type' => 7, 'is_rollback' => 1));//取消类型（0:pc店员，1:wap店员，2:andriod店员,3:ios店员，4：打包app店员，5：用户，6：配送员, 7:超时取消）
			$order_log_db->add_log(array('order_id' => $row['order_id'], 'status' => 10, 'name' => '用户申请取消订单超过3天自动同意', 'phone' => '', 'note' => '用户申请取消订单超过3天自动同意'));
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
			//echo '订单'.$row['order_id'].'超过'.$cancle_time.'s自动取消</br>';
		}
		
		return true;
	}
}
?>