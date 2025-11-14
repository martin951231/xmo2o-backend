<?php

namespace app\maiyatian\service;

use app\common\model\db\Area;
use app\deliver\model\db\DeliverSupply;
use app\deliver\model\db\DeliverUser;
use app\deliver\model\service\DeliverSupplyService;
use app\maiyatian\model\MaiyatianDelivery;
use app\maiyatian\model\MaiyatianToken;
use app\paotui\model\service\PriceTemplateService;
use map\longLat;
use think\facade\Db;

class MaiyatianCallBackService
{
	public static $merId;
	
	/**
	 * 订单计费接口
	 * @throws \Exception
	 */
	public function valuating(array $params): array
	{   //麦芽田是高德，系统使用百度，经纬度要转换
		$sender = $params['sender'];
		$receiver = $params['receiver'];
		[$sender['longitude'], $sender['latitude']] = (new longLat)->gcj02ToBd09($sender['longitude'], $sender['latitude']);
		[$receiver['longitude'], $receiver['latitude']] = (new longLat)->gcj02ToBd09($receiver['longitude'], $receiver['latitude']);

		$distance = (new DeliverSupplyService)->getDistance([
			'lat' => $sender['latitude'],
			'lng' => $sender['longitude'],
		], [
			'lat' => $receiver['latitude'],
			'lng' => $receiver['longitude'],
		]);
		$city = Area::where('adcode', $sender['city_code'])->field('area_id, area_name')->find();
		empty($city) && throw_exception('地址未查询到，需要配置城市信息！');
		
		$calculateFee = (new PriceTemplateService())->calculateFee(3, $distance, $params['order_info']['weight'], 0, self::$merId);
		$payAmount = bcmul(
			$calculateFee['basic_distance_price'] +
			$calculateFee['extra_distance_price'] +
			$calculateFee['basic_weight_price'] +
			$calculateFee['extra_weight_price'] +
			$calculateFee['added_fee'], '100'
		);//单位：分
		$calculateFee['maiyatian_tip_fee'] = bcdiv($params['tips'], '100', 2);
		$calculateFee['total_fee'] = bcdiv((string)((float)$payAmount + (float)$params['tips']), '100', 2);
		$data = [
			"pay_amount" => (int)$payAmount,//运费（分）
			"coupon" => 0,//优惠费用(分)
			"distance" => (int)$distance,//配送距离（米）
			"premium" => 0,//当前溢价(分)
			"weight" => $params['order_info']['weight'] * 1000,//重量（克）
			"overweight" => $calculateFee['exceed_weight'] * 1000,//超重（克）
			"tips" => $params['tips'],//小费(分)
			"at_time" => time(),
			"expect_time" => $params['expect_finish_time'] ?: 0,//期望送达时间
			"order_no" => "",
			"source_order_no" => $params['source_order_no'],//麦芽田订单号
		];
		if (isset($params['send']) && $params['send']) {
			$data['calculate_fee'] = $calculateFee;
		}
		fdump_api(['valuating', $calculateFee, $data], 'MaiyatianCallBack/valuating'.date('H'), true);
		return $data;
	}

	/**
	 * 下单接口
	 * @throws \Exception
	 */
	public function send(array $params): array
	{
		$balance = $this->balance();
		$params['send'] = true;
		$data = $this->valuating($params);
		fdump_api([$balance, $data], 'maiyatian/send', 1);
		if ($balance['balance']*1 < $data['pay_amount'] + $params['tips']) {
			throw_exception('账号余额不足，无法下单！');
		}
		$delivery = Db::transaction(function () use ($params) {
			$delivery = MaiyatianDelivery::where(['source_order_no' => $params['source_order_no']])
				->with(['sender', 'receiver', 'order_info.goods_list'])
				->find();
			empty($delivery) && $delivery = new MaiyatianDelivery();
			$maiyatianService = new MaiyatianService();
			$sender = $maiyatianService->saveSender($params['sender'], $delivery);
			$receiver = $maiyatianService->saveReceiver($params['receiver'], $delivery);
			$order = $maiyatianService->saveOrder($params['order_info'], $delivery);
			return $maiyatianService->saveDelivery($params, $sender, $receiver, $order, $delivery);
		});
		if (empty($delivery)) {
			throw_exception('配送单创建失败！');
		}
		$delivery->save([
			'pay_amount' => $data['pay_amount'],
			'distance' => $data['distance'],
			'calculate_fee' => $data['calculate_fee'] ?? [],
		]);
		$params['calculate_fee'] = $data['calculate_fee'] ?? [];
		unset($data['calculate_fee']);
		(new MaiyatianService())->saveUserPublishOrder($params, $delivery);
		$data['order_no'] = $delivery->paotui_order_no;
		return $data;
	}

	/**
	 * 添加小费接口
	 * @param array $params
	 * @return array
	 */
	public function tips(array $params): array
	{
		$delivery = MaiyatianDelivery::Where(['source_order_no' => $params['source_order_no']])->find();
		if (empty($delivery)) {
			throw_exception('配送单不存在！');
		}
		throw_exception('暂不支持小费功能！');
		$delivery->tips = $params['tips'] + $delivery->tips;
		return [
			'status' => $delivery->save(),//处理状态: 1-成功；0-失败
		];
	}


	/**
	 * 取消配送
	 * @param array $params
	 * @return array
	 */
	public function cancel(array $params): array
	{
		$delivery = MaiyatianDelivery::Where(['source_order_no' => $params['source_order_no']])->find();
		if (empty($delivery)) {
			throw_exception('配送单不存在！');
		}
		$delivery->save([
			'cancel_reason_type' => $params['cancel_reason_type'],
			'cancel_reason' => $params['cancel_reason'],
			'status' => 'CANCEL',
			'cancel_time' => date('Y-m-d H:i:s', time()),
		]);

		$supply = DeliverSupply::where('order_id', $delivery->publish_id)->where('item', 3)->find();
		if (empty($supply) || $supply->deliver_status == 5) {
			return [
				'status' => 0,
				'cancel_amount' => 0,
			];
		}

		$result = (new MaiyatianService())->publishCancel($delivery);
		return [
			'status' => $result ? 1 : 0,//处理状态: 1-成功；0-失败
			'cancel_amount' => 0,//取消违约金（单位：分）
		];
	}


	/**
	 * 查询配送详情
	 * @param array $params
	 * @return array
	 */
	public function queryInfo(array $params): array
	{
		$delivery = MaiyatianDelivery::Where(['source_order_no' => $params['source_order_no']])->find();
		if (empty($delivery)) {
			throw_exception('配送单不存在！');
		}

		return [
			'order_no' => $delivery['paotui_order_no'],
			'source_order_no' => $delivery['source_order_no'],
			'status' => $delivery['status'],
			'status_name' => $delivery['status_name'],
			'pay_amount' => $delivery['pay_amount'],
			'coupon' => 0,
			'premium' => 0,
			'tips' => $delivery['tips'],
			'distance' => $delivery['distance'],
			'create_time' => strtotime($delivery['create_time']),
			'accept_time' => strtotime($delivery['accept_time']),
			'fetch_time' => strtotime($delivery['fetch_time']),
			'finish_time' => strtotime($delivery['finish_time']),
			'cancel_time' => strtotime($delivery['cancel_time']),
			'rider_name' => $delivery['rider_name'],
			'rider_phone' => $delivery['rider_phone'],
			'longitude' => $delivery['longitude'],
			'latitude' => $delivery['latitude'],
			'is_transship' => $delivery['is_transship'],
		];
	}

	/*
	 * 骑手位置接口
	 * @param array $params
	 * @return array
	 */
	public function riderLocation(array $params): array
	{
		$delivery = MaiyatianDelivery::Where(['source_order_no' => $params['source_order_no']])->find();
		if (empty($delivery)) {
			throw_exception('配送单不存在！');
		}
		$deliveryUser = DeliverUser::alias('du')
			->join('deliver_supply ds', 'du.uid = ds.uid')
			->where([
				'ds.order_id' => $delivery['publish_id'],
				'ds.item' => DeliverSupply::ITEM_USER_PUBLISH,
			])
			->field('du.name, du.phone, du.now_lat, du.now_lng, du.last_time')
			->find();
		$position = (new longLat())->baiduToGcj02($deliveryUser->now_lat, $deliveryUser->now_lng);
		return [
			"source_order_no" => $delivery->source_order_no,
			"order_no" => $delivery->paotui_order_no,
			"rider_name" => $deliveryUser->name,
			"rider_phone" => $deliveryUser->phone,
			"longitude" => (string)$position['lng'],
			"latitude" => (string)$position['lat'],
			"at_time" => $deliveryUser->last_time,
		];
	}

	/**
	 * 批量获取骑手当前位置
	 * @param array $params
	 * @return array
	 */
	public function multiRiderLocation(array $params): array
	{
		$data = [];
		foreach ($params['orders'] as $order) {
			$data[] = $this->riderLocation(['source_order_no' => $order['source_order_no']]);
		}
		return $data;
	}

	/**
	 * 授权解绑
	 */
	public function tokenUnbind(array $params): bool
	{
		return (new MaiyatianService())->tokenUnbind($params);
	}

	/**
	 * 查询当前账号余额
	 * @return array
	 */
	public function balance(): array
	{
		$balance = MaiyatianToken::where(['mer_id' => self::$merId])->value('mer_money') ?? 0;
		return [
			"balance" => intval($balance * 100),
			"at_time" => time(),
		];
	}

	/**
	 * 在线充值链接
	 * @param array $params
	 * @return array
	 */
	public function rechargeUrl(array $params): array
	{
		return [
			'recharge_url' => cfg('site_url') . '/index.php?c=Maiyatian&a=recharge&shop_id=' . $params['shop_id'],
			'at_time' => time(),
		];
	}
}