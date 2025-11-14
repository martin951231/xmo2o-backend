<?php

namespace app\maiyatian\service;

use app\common\model\db\Merchant;
use app\maiyatian\model\MaiyatianMerMoneyList;
use app\maiyatian\model\MaiyatianRechargeOrder;
use app\maiyatian\model\MaiyatianToken;
use app\pay\model\service\PayService;
use think\Exception;

class MaiyatianRechargeService
{
	public function saveRechargeOrder(array $params): array
	{
		$merId = MaiyatianToken::where('shop_id', $params['shop_id'])->value('mer_id');
		if (!$merId) {
			throw_exception('商户ID错误,请先进行授权');
		}
		if ((float)$params['money'] < 0.1) {
			throw_exception('充值金额不能小于0.1元');
		}
		$rechargeOrder = MaiyatianRechargeOrder::create([
			'order_no' => (new PayService)->createOrderNo(),
			'uid' => $params['uid'],
			'mer_id' => $merId,
			'money' => $params['money'],
		]);

		return [
			'order_type' => 'maiyatian_recharge',
			'order_id' => $rechargeOrder->id,
			'use_merchant_balance' => 0,
			'use_qiye_balance' => 0,
			'use_system_balance' => 0,
			'use_user_score' => 0,
		];
	}

	public function getOrderPayInfo($order_id): array
	{
		$rechargeOrder = MaiyatianRechargeOrder::find($order_id);
		if (!$rechargeOrder) {
			throw_exception('订单不存在');
		}
		if ($rechargeOrder['paid']) {
			throw_exception('订单已支付');
		}
		$merchant = Merchant::find($rechargeOrder['mer_id']);
		return [
			'order_money' => $rechargeOrder['money'],
			'paid' => $rechargeOrder['paid'],
			'order_no' => $rechargeOrder['order_no'],
			'mer_id' => $rechargeOrder['mer_id'],
			'uid' => $rechargeOrder['uid'],
			'is_cancel' => 0,
			'time_remaining' => 3600,
			'city_id' => $merchant['city_id'],
			'store_id' => 0,
			'title' => "商家【{$merchant['name']}】配送充值",
			'is_recharge' => 1,
			'merchant_balance_open' => 0,
			'use_merchant_balance' => 0,
			'use_qiye_balance' => 0,
			'use_system_balance' => 0,
			'use_score' => 0,
		];
	}

	public function getPayResultUrl($orderId)
	{
		return [
			'redirect_url' => get_base_url(),
		];
	}

	public function afterPay($orderId, $payParam)
	{
		if (!$orderId) {
			throw new Exception('参数错误');
		}
		$rechargeOrder = MaiyatianRechargeOrder::find($orderId);
		if (!$rechargeOrder) {
			throw_exception('订单不存在');
		}
		if ($rechargeOrder['paid']) {
			throw_exception('订单已支付');
		}
		$rechargeOrder->save([
			'paid' => 1,
			'pay_time' => $payParam['pay_time'],
			'payment_money' => $payParam['paid_money'],
			'pay_type' => $payParam['paid_type'],
			'third_id' => $payParam['paid_orderid'],
		]);
		
		MaiyatianMerMoneyList::create([
			'mer_id' => $rechargeOrder->mer_id,
			'recharge_id' => $rechargeOrder->id,
			'type' => MaiyatianMerMoneyList::TYPE_ADD,
			'money' => $rechargeOrder->money,
			'remark' => '商家充值',
		]);
		return MaiyatianToken::where(['mer_id' => $rechargeOrder->mer_id])->inc('mer_money', $rechargeOrder->money)->update();
	}
	
	public function getMerchant(int $shopId)
	{
		$merchant = MaiyatianToken::alias('mt')
			->join('merchant m','mt.mer_id = m.mer_id')
			->where(['mt.shop_id' => $shopId])
			->field('mt.mer_money, m.name, m.logo, m.mer_id')
			->find();
		empty($merchant) && throw_exception('商户不存在');
		$merchant->logo = $merchant['logo'] ? replace_file_domain($merchant['logo']) : cfg('wechat_share_img');
		
		return $merchant;
	}
}