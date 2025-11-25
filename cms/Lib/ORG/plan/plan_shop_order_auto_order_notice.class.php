<?php
//自动接单电话通知
class plan_shop_order_auto_order_notice extends plan_base
{	
	public function runTask()
	{
		if(C('config.open_shop_auto_order_notice') == 0){
			return true;
		}
		$time = time();
		$sql = "SELECT o.mer_id, o.store_id, o.order_id,s.shop_auto_order_notice_time,s.shop_auto_order_notice_phone FROM " . C('DB_PREFIX') . "shop_order AS o INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop AS s ON s.store_id=o.store_id INNER JOIN " . C('DB_PREFIX') . "merchant AS m ON m.mer_id=o.mer_id ";
		$sql .= " WHERE o.paid=1 AND o.status=1 AND o.auto_order_notice=0 AND m.open_shop_auto_order_notice=1 AND s.shop_auto_order_notice_time > 0 AND s.is_auto_order = 1 AND s.shop_auto_order_notice_phone <> '' AND (o.pay_time + s.shop_auto_order_notice_time*60) <= {$time}";
		$sql .= " AND o.create_time > ".($time-86400*7);

		$res = D()->query($sql);
		$orderDB = D('Shop_order');
		foreach ($res as $row) {
			$this->keepThread();			
		    $phoneArr = explode(' ', $row['shop_auto_order_notice_phone']);
		    foreach ($phoneArr as $phone) {
			    $sms_data = array('mer_id' => $row['mer_id'], 'store_id' => $row['store_id'], 'type' => 'shop');
			    $sms_data['uid'] = 0;
			    $sms_data['mobile'] = $phone;
			    $sms_data['sendto'] = 'merchant';
			    $sms_data['content'] = C('config.nxcloud_shop_content') ? C('config.nxcloud_shop_content') : ('您好，'.C('config.shop_notice_plat_name').'提醒您，有新的' . C('config.shop_alias_name') . '订单需要处理！'.C('config.shop_notice_plat_name').'提醒您，有新的' . C('config.shop_alias_name') . '订单需要处理！'.C('config.shop_notice_plat_name').'提醒您，有新的' . C('config.shop_alias_name') . '订单需要处理！');
			    $sms_data['is_voice'] = 1;
			    Sms::sendSms($sms_data);
		    }
			
			$orderDB->where(array('order_id' => $row['order_id']))->save(array('auto_order_notice' => 1));
		}
		return true;
	}
}
?>