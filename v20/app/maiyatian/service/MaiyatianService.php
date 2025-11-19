<?php

namespace app\maiyatian\service;

use app\common\model\db\Area;
use app\common\model\db\Merchant;
use app\common\model\db\PlatOrder;
use app\deliver\model\db\DeliverSupply;
use app\maiyatian\model\MaiyatianDelivery;
use app\maiyatian\model\MaiyatianMerMoneyList;
use app\maiyatian\model\MaiyatianOrder;
use app\maiyatian\model\MaiyatianOrderGoods;
use app\maiyatian\model\MaiyatianToken;
use app\maiyatian\model\MaiyatianUser;
use app\paotui\model\db\ServiceUserPublish;
use app\paotui\model\db\ServiceUserPublishGive;
use app\paotui\model\db\ServiceUserPublishLog;
use Carbon\Carbon;
use map\longLat;
use think\facade\Db;

class MaiyatianService
{
	//region 麦芽田授权
	public static $requestDomain;

	public static $merId;

	public function __construct()
	{
		self::$requestDomain = cfg('maiyatian_request_domain');
		empty(self::$requestDomain) && throw_exception('缺失麦芽田请求域名');
	}

	public function signatureData(string $data, string $command, string $token, int $timestamp = 0): array
	{
		empty(cfg('maiyatian_appkey')) && throw_exception('缺失麦芽田appkey');
		empty(cfg('maiyatian_appsecret')) && throw_exception('缺失麦芽田appsecret');
		empty($timestamp) && $timestamp = (int)bcmul(microtime(true), 1000);
		$signData = [
			'app_key' => cfg('maiyatian_appkey'),
			'command' => $command,
			'data' => $data,
			'timestamp' => $timestamp,
			'token' => $token,
		];
		ksort($signData);
		$dataToSign = '';
		foreach ($signData as $k => $v) {
			$dataToSign .= $k . '=' . $v . ',';
		}

		$dataToSign = substr($dataToSign, 0, -1);
		$hash = hash_hmac('sha256', $dataToSign, cfg('maiyatian_appsecret'), true);
		$signData['signature'] = strtr(base64_encode($hash), '+/', '-_');
		return $signData;
	}

	public function requestPost(string $url, string $command, array $postData)
	{
		$token = $command == 'access_token' ? '' : $this->refreshAccessToken();

		$postDataString = json_encode($postData, JSON_UNESCAPED_UNICODE);
		$signData = $this->signatureData($postDataString, $command, $token);
		$signData['request_id'] = time() . uniqid();
		$data = curlPost($url, json_encode($signData, JSON_UNESCAPED_UNICODE), 45, "Content-type: application/json;charset=utf-8");
		fdump_api(['url' => $url, 'postData' => $signData, 'response' => $data], 'maiyatian/command_' . $command . date('H'), 1);
		return json_decode($data, true);
	}

	public function getAccessToken(array $params)
	{
		$merchant = Merchant::where('mer_id', $params['mer_id'])->find();
		if (!$merchant) {
			return '';
		}
		self::$merId = $merchant['mer_id'];
		$areaCode = Area::where('area_id', $merchant['city_id'])->find();
		empty($areaCode) && throw_exception('商家城市信息不存在');
		$postData = [
			"grant_type" => "2", //授权级别 1:门店 2:商户 3:三方门店直联授权
			"code" => $params['code'], //授权码
			"mobile" => $merchant['phone'], //三方平台账号注册手机号
			"store_id" => $params['mer_id'], //三方平台最小维度的唯一ID（如果平台方是门店维度的返回门店ID）
			"city" => $areaCode['area_name'], //城市名称（例：北京市）
			"city_code" => (string)$areaCode['adcode'], //城市code
		];
		$url = self::$requestDomain . '/access_token';
		$data = $this->requestPost($url, 'access_token', $postData);
		if ($data['code'] === 200) {
			$tokenInfo = json_decode($data['data'], true);
			$this->saveToken($tokenInfo);
		}
		return $data;
	}

	public function saveToken(array $params)
	{
		$token = MaiyatianToken::where(['mer_id' => self::$merId])->find();
		!$token && $token = new MaiyatianToken();

		$data = [
			'mer_id' => self::$merId,
			'token' => $params['token'],
			'expire_time' => $params['expire_time'],
			'refresh_token' => $params['refresh_token'],
			'refresh_expire_time' => $params['refresh_expire_time'],
		];
		if (isset($params['shop_id'])) {
			$data['shop_id'] = $params['shop_id'];
		}
		$token->save($data);
	}

	public function refreshAccessToken()
	{
		$token = MaiyatianToken::where(['mer_id' => self::$merId])->find();
		empty($token) && throw_exception('当前商家未授权！');
		if (empty($token['expire_time']) || $token['expire_time'] > Carbon::now()->subDay()->timestamp) {
			return $token['token'];
		}
		if (!empty($token['refresh_expire_time']) && $token['refresh_expire_time'] <= time()) {
			throw_exception('授权已过期，请重新授权！');
		}
		$url = self::$requestDomain . '/refresh_token';
		$data = $this->requestPost($url, 'refresh_token', [
			'token' => $token['token'],
			'refresh_token' => $token['refresh_token'],
		]);
		if ($data['code'] !== 200) {
			throw_exception('授权已过期，请重新授权！');
		}
		$tokenInfo = json_decode($data['data'], true);
		$this->saveToken($tokenInfo);
		return $tokenInfo['token'];
	}

	public function tokenUnbind(array $params): bool
	{
		return MaiyatianToken::where(['shop_id' => $params['shop_id']])->save(['is_unbind' => 1]);
	}
	//endregion

	//region 麦芽田配送
	public function saveSender(array $params, MaiyatianDelivery $delivery): MaiyatianUser
	{
		[$params['longitude'], $params['latitude']] = (new longLat)->gcj02ToBd09($params['longitude'], $params['latitude']);
		$data = [
			'name' => $params['name'],
			'phone' => $params['phone'],
			'address' => $params['address'],
			'address_detail' => $params['address_detail'],
			'longitude' => $params['longitude'],
			'latitude' => $params['latitude'],
			'province_code' => $params['province_code'],
			'city_code' => $params['city_code'],
			'district_code' => $params['district_code'],
			'type' => MaiyatianUser::SENDER_TYPE_USER,
		];
		if (!$delivery->isEmpty()) {
			$delivery->sender->save($data);
			return $delivery->sender;
		} else {
			return MaiyatianUser::create($data);
		}
	}

	public function saveReceiver(array $params, MaiyatianDelivery $delivery): MaiyatianUser
	{
		[$params['longitude'], $params['latitude']] = (new longLat)->gcj02ToBd09($params['longitude'], $params['latitude']);
		$data = [
			'name' => $params['name'],
			'phone' => $params['phone'],
			'address' => $params['address'],
			'address_detail' => $params['address_detail'],
			'longitude' => $params['longitude'],
			'latitude' => $params['latitude'],
			'province_code' => $params['province_code'],
			'city_code' => $params['city_code'],
			'district_code' => $params['district_code'],
			'type' => MaiyatianUser::RECEIVER_TYPE_USER,
		];
		if (!$delivery->isEmpty()) {
			$delivery->receiver->save($data);
			return $delivery->receiver;
		} else {
			return MaiyatianUser::create($data);
		}
	}

	public function saveUserPublishOrder(array $params, MaiyatianDelivery $delivery)
	{
		if ($delivery->isEmpty()) {
			throw_exception('配送单不存在！');
		}
		[$delivery, $supply] = Db::transaction(function () use ($params, $delivery) {
			$publishOrder = $this->savePublishOrder($delivery);
			$this->savePublishGiveOrder($publishOrder, $delivery, $params);
			$supply = $this->saveSupplyDelivery($publishOrder, $delivery);
			$this->minusMerMoneyList($delivery, '配送费用');
			$this->savePlatOrder($publishOrder, $delivery);
			return [$delivery, $supply];
		});
		invoke_cms_model('Deliver_supply/sendMsg', ['supply' => $supply->toArray()]);

		return $delivery;
	}

	private function savePlatOrder(ServiceUserPublish $publishOrder, MaiyatianDelivery $delivery)
	{
		$platOrder = PlatOrder::where([
			'business_type' => 'service',
			'business_id' => $publishOrder->publish_id,
		])->find();
		if ($platOrder) {
			return $platOrder;
		}
		$totalMoney = bcdiv((string)($delivery->pay_amount + $delivery->tips), 100, 2);
		$platOrder = PlatOrder::create([
			'business_type' => 'service',
			'business_id' => $publishOrder->publish_id,
			'add_time' => time(),
			'order_name' => '帮我送订单',
			'total_money' => $totalMoney,
			'system_balance' => $totalMoney,
			'pay_time' => time(),
			'submit_order_time' => strtotime($delivery->order_info->create_time),
			'paid' => 1,
		]);

		ServiceUserPublishLog::create([
			'publish_id' => $publishOrder->publish_id,
			'catgory_type' => ServiceUserPublishLog::CATEGORY_TYPE_USER_PUBLISH,
			'status' => 1,
			'type' => 2,
			'name' => $delivery->sender->name,
			'phone' => $delivery->sender->phone,
			'add_time' => time(),
			'deliver_status' => 2,
			'lng' => $delivery->sender->longitude,
			'lat' => $delivery->sender->latitude,
		]);

		return $platOrder;
	}

	private function saveSupplyDelivery(ServiceUserPublish $publishOrder, MaiyatianDelivery $delivery)
	{
		$publishOrder->save(['status' => ServiceUserPublish::STATUS_PAY_SUCCESS]);
		$supply = DeliverSupply::where([
			'order_id' => $publishOrder->publish_id,
			'item' => DeliverSupply::ITEM_USER_PUBLISH,
		])->find();
		if ($supply) {
			return $supply;
		} else {
			$supply = new DeliverSupply();
		}
		$goodsName = '';
		if ($delivery->order_info->goods_list) {
			$goodsList = $delivery->order_info->goods_list->toArray();
			$goodsName = implode(',',array_column($goodsList, 'name'));
		}
		
		$supply->save([
			'order_id' => $publishOrder->publish_id,
			'mer_id' => $delivery->mer_id,
			'from_site' => $delivery->sender->address."({$delivery->sender->address_detail}))",
			'from_lnt' => $delivery->sender->longitude,
			'from_lat' => $delivery->sender->latitude,
			'aim_site' => $delivery->receiver->address."({$delivery->receiver->address_detail}))",
			'aim_lnt' => $delivery->receiver->longitude,
			'aim_lat' => $delivery->receiver->latitude,
			'create_time' => time(),
			'item' => DeliverSupply::ITEM_USER_PUBLISH,
			'status' => 1,
			'deliver_status' => 1,
			'name' => $delivery->receiver->address,
			'phone' => $delivery->receiver->phone,
			'appoint_time' => strtotime($delivery->expect_finish_time),
			'order_out_time' => strtotime($delivery->expect_finish_time),
			'paid' => 1,
			'note' => $delivery->remark,
			'order_time' => time(),
			'freight_charge' => $delivery->pay_amount / 100,
			'tip_price' => $delivery->tips / 100,
			'money'=> $delivery->pay_amount / 100,
			'distance' => $delivery->distance / 1000,
			'fetch_number' => $delivery->order_info->sn,
			'goods_name' => $goodsName,
			'goods_price' => $delivery->order_info->total_fee / 100,
			'goods_weight' => $delivery->order_info->weight,
			'server_type' => 1,
			'province_id' => Area::where('adcode', $delivery->sender->province_code)->value('area_id', 0),
			'city_id' => Area::where('adcode', $delivery->sender->city_code)->value('area_id', 0),
			'area_id' => Area::where('adcode', $delivery->sender->district_code)->value('area_id', 0),
			'order_from' => DeliverSupply::ITEM_USER_PUBLISH,
			'pay_time' => time(),
		]);

		return $supply;
	}

	public function minusMerMoneyList(MaiyatianDelivery $delivery, $remark)
	{
		$record = MaiyatianMerMoneyList::where(['delivery_id' => $delivery->id, 'type' => MaiyatianMerMoneyList::TYPE_MINUS])->find();
		if (!empty($record)) {
			return true;
		}
		$money = bcadd(bcdiv($delivery->pay_amount, '100', 2), bcdiv($delivery->tips, '100', 2), 2);
		MaiyatianMerMoneyList::create([
			'mer_id' => $delivery->mer_id,
			'delivery_id' => $delivery->id,
			'type' => MaiyatianMerMoneyList::TYPE_MINUS,
			'money' => $money,
			'remark' => $remark,
		]);
		return MaiyatianToken::where(['mer_id' => $delivery->mer_id])->dec('mer_money', $money)->update();
	}

	public function addMerMoneyList(MaiyatianDelivery $delivery, $remark)
	{
		$record = MaiyatianMerMoneyList::where(['delivery_id' => $delivery->id, 'type' => MaiyatianMerMoneyList::TYPE_ADD])->find();
		if (!empty($record)) {
			return true;
		}
		$money = bcadd(bcdiv($delivery->pay_amount, '100', 2), bcdiv($delivery->tips, '100', 2), 2);
		MaiyatianMerMoneyList::create([
			'mer_id' => $delivery->mer_id,
			'delivery_id' => $delivery->id,
			'type' => MaiyatianMerMoneyList::TYPE_ADD,
			'money' => $money,
			'remark' => $remark,
		]);
		return MaiyatianToken::where(['mer_id' => $delivery->mer_id])->inc('mer_money', $money)->update();
	}

	private function savePublishOrder(MaiyatianDelivery $delivery)
	{
		if ($delivery->paotui_order_no) {
			$publishOrder = ServiceUserPublish::where(['order_sn' => $delivery->paotui_order_no])->find();
			empty($publishOrder) && throw_exception('发布单不存在！');
		} else {
			$publishOrder = new ServiceUserPublish();
			$orderSn = date("mdHis") . rand(10, 99) . sprintf("%08d", $delivery->mer_id);
			$publishOrder->order_sn = $orderSn;
			$delivery->paotui_order_no = $orderSn;
			$delivery->save();
		}
		$province = Area::where('adcode', $delivery->sender->province_code)->field('area_id, area_name')->find();
		empty($province) && throw_exception('省份未查询到！');
		$city = Area::where('adcode', $delivery->sender->city_code)->field('area_id, area_name')->find();
		empty($city) && throw_exception('市未查询到！');
		$area = Area::where('adcode', $delivery->sender->district_code)->field('area_id, area_name')->find();
		empty($area) && throw_exception('县/区未查询到！');
		$totalMoney = bcdiv((string)($delivery->pay_amount + $delivery->tips), 100, 2);
		$publishOrder->save([
			'address' => $delivery->sender->address_detail,
			'address_lng' => $delivery->sender->longitude,
			'address_lat' => $delivery->sender->latitude,
			'add_time' => time(),
			'address_area_id' => $city['area_id'],
			'address_area_name' => $city['area_name'],
			'catgory_type' => 3,//帮我送
			'deliver_type' => 1,//1平台配送员
			'weight_tip' => $delivery->order_info->weight . 'kg',
			'destance_sum' => $delivery->distance / 1000,
			'system_balance' => $totalMoney,
			'province_id' => $province['area_id'],
			'city_id' => $city['area_id'],
			'area_id' => $area['area_id'],
			'mer_id' => $delivery->mer_id,
			'third_type' => 'maiyatian',
		]);
		$delivery->publish_id = $publishOrder->publish_id;
		$delivery->save();
		return $publishOrder;
	}

	private function savePublishGiveOrder(ServiceUserPublish $publishOrder, MaiyatianDelivery $delivery, array $params): ServiceUserPublishGive
	{
		$publishGive = ServiceUserPublishGive::where(['publish_id' => $publishOrder->publish_id])->find();
		if (empty($publishGive)) {
			$publishGive = new ServiceUserPublishGive();
			$publishGive->publish_id = $publishOrder->publish_id;
		}
		$calculateFee = $params['calculate_fee'];
		$publishGive->save([
			'address' => $delivery->sender->address_detail,
			'weight' => $delivery->order_info->weight,
			'price' => $delivery->order_info->total_fee / 100,
			'start_adress_name' => $delivery->sender->address."({$delivery->sender->address_detail}))",
			'start_adress_lng' => $delivery->sender->longitude,
			'start_adress_lat' => $delivery->sender->latitude,
			'start_adress_nickname' => $delivery->sender->address,
			'start_adress_phone' => $delivery->sender->phone,
			'end_adress_name' => $delivery->receiver->address."({$delivery->receiver->address_detail}))",
			'end_adress_lng' => $delivery->receiver->longitude,
			'end_adress_lat' => $delivery->receiver->latitude,
			'end_adress_nickname' => $delivery->receiver->address,
			'end_adress_phone' => $delivery->receiver->phone,
			'weight_price' => $calculateFee['basic_weight_price'] + $calculateFee['extra_weight_price'],
			'distance_price' => $calculateFee['extra_distance_price'],
			'basic_distance_price' => $calculateFee['basic_distance_price'],
			'tip_price' => $delivery->tips / 100,
			'remarks' => $delivery->remark,
			'total_price' => $calculateFee['total_fee'],
			'addtime' => time(),
			'mer_id' => $delivery->mer_id,
			'added_fee' => $calculateFee['added_fee'],
			'added_fee_name' => $calculateFee['added_fee_name'],
			'added_fee_list' => isset($calculateFee['added_fee_list']) && is_array($calculateFee['added_fee_list']) ? json_encode($calculateFee['added_fee_list'], JSON_UNESCAPED_UNICODE) : '[]',
			'from_mer_id' => $delivery->mer_id,
			'price_template_id' => $calculateFee['price_template_id'],
		]);

		return $publishGive;
	}

	public function saveOrder(array $params, MaiyatianDelivery $delivery): MaiyatianOrder
	{
		$orderData = [
			'sn' => $params['sn'],
			'full_sn' => $params['full_sn'] ?? '',
			'source' => $params['source'],
			'channel_tag' => $params['channel_tag'],
			'channel_name' => $params['channel_name'],
			'category' => $params['category'],
			'weight' => $params['weight'],
			'total_fee' => $params['total_fee'],
			'paid_fee' => $params['paid_fee'],
		];
		if (!$delivery->isEmpty()) {
			$order = $delivery->order_info;
			$order->save($orderData);
			$delivery->order_info->goods_list->delete();
		} else {
			$order = MaiyatianOrder::create($orderData);
		}
		foreach ($params['goods_list'] as $goods) {
			MaiyatianOrderGoods::create([
				'order_id' => $order->id,
				'name' => $goods['name'],
				'number' => $goods['number'],
				'price' => $goods['price'],
			]);
		}
		return $order;
	}

	public function saveDelivery(array $params, MaiyatianUser $sender, MaiyatianUser $receiver, MaiyatianOrder $order, MaiyatianDelivery $delivery): MaiyatianDelivery
	{
		$data = [
			'mer_id' => self::$merId,
			'shop_id' => $params['shop_id'],
			'is_pre_order' => $params['is_pre_order'],
			'is_transship' => intval($params['is_transship'] ?? 0),
			'delay_delivery_time' => $params['delay_delivery_time'] ?? 0,
			'expect_finish_time' => $params['expect_finish_time'] ?: time() + 3600,
			'tips' => $params['tips'],
			'source_order_no' => $params['source_order_no'],
			'remark' => $params['remark'] ?? '',
			'exp_type' => $params['exp_type'] ?? 2,//发票类型 1-公司 ；2-个人
			'pickup_start_time' => $params['pickup_start_time'] ?? 0,
			'pickup_end_time' => $params['pickup_end_time'] ?? 0,
			'custid' => $params['custid'] ?? '',
			'pay_mode' => $params['pay_mode'] ?? 0,
			'tags' => $params['tags'] ?? '',
			'order_id' => $order->id,
			'receiver_id' => $receiver->id,
			'sender_id' => $sender->id,
		];
		if (!$delivery->isEmpty()) {
			$delivery->save($data);
		} else {
			$delivery = MaiyatianDelivery::create($data);
			$delivery = MaiyatianDelivery::where('id', $delivery->id)
				->with(['sender', 'receiver', 'order_info.goods_list'])
				->find();
		}
		return $delivery;
	}
	//endregion

	//region 订单相关
	public function publishCancel(MaiyatianDelivery $delivery)
	{
		return Db::transaction(function () use ($delivery) {
			$supply = DeliverSupply::where('order_id', $delivery->publish_id)->where('item', 3)->lock(true)->find();
			$res1 = ServiceUserPublish::where('publish_id', $delivery->publish_id)->save([
				'status' => ServiceUserPublish::STATUS_CANCEL,
			]);
			$res2 = $supply->save([
				'status' => DeliverSupply::STATUS_CANCEL,
				'deliver_status' => DeliverSupply::STATUS_CANCEL,
			]);
			if (!$res1 || !$res2) {
				return false;
			}
			return $this->addMerMoneyList($delivery, '取消配送单,退回余额');
		});
	}

	//endregion
}