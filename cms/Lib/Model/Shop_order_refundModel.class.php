<?php

class Shop_order_refundModel extends Model
{
	public function recoverLackWeightMoney($orderId, $refundId): bool
	{
		$refundDetail = D('Shop_order_detail')
			->alias('sd')
			->join('shop_refund_detail rd ON sd.id = rd.detail_id', 'INNER')
			->where([
				'refund_id' => $refundId,
				'rd.order_id' => $orderId,
				'lack_weight_refund' => ['gt', 0],
			])
			->field('sd.lack_weight_refund, sd.num buy_num, rd.num refund_num')
			->select();
		if (empty($refundDetail)) {
			return false;
		}
		$recoverMoney = 0;
		foreach ($refundDetail as $value){
			$goodsRecoverMoney = ($value['lack_weight_refund'] / $value['buy_num'] * $value['refund_num']);
			if($goodsRecoverMoney > $value['lack_weight_refund']) {
				$goodsRecoverMoney = $value['lack_weight_refund'];
			}
			$recoverMoney = bcadd($recoverMoney, $goodsRecoverMoney, 2);
		}
		if($recoverMoney > 0) {
			$order = D('Shop_order')->where(['id' => $orderId])->field('uid, real_orderid')->find();
			D('User_money_list')->add_row($order['uid'], 2, $recoverMoney, '商品申请退款，扣除退还缺货部分金额，订单编号' . $order['real_orderid'], true, 18, $orderId);
		}
		return true;
	}
}