<?php

namespace app\maiyatian\service;

use app\deliver\model\db\DeliverSupply;
use app\deliver\model\db\DeliverUser;
use app\maiyatian\model\MaiyatianDelivery;
use app\paotui\model\db\ServiceUserPublish;

class MaiyatianPushService
{
	public function saveDeliveryChange($publishId, array $params): bool
	{
		$publish = ServiceUserPublish::where('publish_id', $publishId)->find();
		if($publish->third_type != 'maiyatian') {
			return false;
		}
		$delivery = MaiyatianDelivery::where('publish_id', $publishId)->find();
		if (empty($delivery)) {
			return false;
		}
		$data = [];
		$deliveryUid = DeliverSupply::where(['order_id' => $publishId, 'item' => DeliverSupply::ITEM_USER_PUBLISH])->value('uid');
		$user = DeliverUser::where('uid', $deliveryUid)->find();
		switch ($params['status']) {
			case 'GRABBED':
				$data = [
					'status' => 'GRABBED',
					'accept_time' => date('Y-m-d H:i:s'),
					'rider_name' => $user['name'],
					'rider_phone' => $user['phone'],
					'longitude' => (string)$user['now_lng'],
					'latitude' => (string)$user['now_lat'],
					'fetch_time' => date('Y-m-d H:i:s'),
				];
				break;
			case 'PICKUP':
				$data = [
					'status' => 'PICKUP',
					'longitude' => (string)$user['now_lng'],
					'latitude' => (string)$user['now_lat'],
				];
				break;
			case 'DELIVERING':
				$data = [
					'status' => 'DELIVERING',
					'fetch_time' => date('Y-m-d H:i:s'),
					'longitude' => (string)$user['now_lng'],
					'latitude' => (string)$user['now_lat'],
				];
				break;
			case 'DONE':
				$data = [
					'status' => 'DONE',
					'finish_time' => date('Y-m-d H:i:s'),
					'longitude' => (string)$user['now_lng'],
					'latitude' => (string)$user['now_lat'],
				];
				break;
			case 'CANCEL':
				$data = [
					'status' => 'CANCEL',
					'cancel_time' => date('Y-m-d H:i:s'),
					'longitude' => (string)$user['now_lng'],
					'latitude' => (string)$user['now_lat'],
				];
				break;
			case 'EXPECT':
				$data = [
					'status' => 'EXPECT',
					'longitude' => (string)$user['now_lng'],
					'latitude' => (string)$user['now_lat'],
				];
				break;
		}
		
		try {
			$data && $delivery->save($data);
			MaiyatianService::$merId = $delivery->mer_id;
			$this->pushStatusChange($delivery);
			//$this->pushLocationChange($delivery);
		} catch (\Exception $e) {
			fdump_api('pushStatusChange error:' . $e->getMessage(), 'maiyatian/push_error', true);
			return false;
		}
		return true;
	}

	//推送配送状态
	public function pushStatusChange(MaiyatianDelivery $delivery)
	{
		$postData = [
			'order_no' => $delivery->paotui_order_no,
			'source_order_no' => $delivery->source_order_no,
			'shop_id' => (string)$delivery->shop_id,
			'status' => $delivery->status,
			'rider_name' => $delivery->rider_name,
			'rider_phone' => $delivery->rider_phone,
			'longitude' => $delivery->longitude,
			'latitude' => $delivery->latitude,
			'cancel_dedit_amount' => 0,
			'at_time' => time(),
		];

		return (new MaiyatianService())->requestPost(MaiyatianService::$requestDomain . '/delivery_change', 'delivery_change', $postData);
	}

	//推送配送轨迹
	public function pushLocationChange(MaiyatianDelivery $delivery)
	{
		$deliveryLogs = $delivery->locations;
		$locations = [];
		//0 用户下单 1 用户支付订单待服务 2 配送员已经接单，待取货  3.配送员取货，配送中  4.配送员送达，订单完成 5.用户申请退款
		// 6.退款成功订单关闭 7.用户已取消 8.已服务待确认 9.服务过期 10.服务已完成评价成功 11.过期退款 12.退款失败
		$status = [
			0 => 'DELIVERY',
			1 => 'DELIVERY',
			2 => 'PICKUP',
			3 => 'DELIVERING',
			4 => 'DONE',
			6 => 'CANCEL',
		];
		foreach ($deliveryLogs as $location) {
			if(!empty($status[$location['status']])) {
				$locations[] = [
					"longitude" => $location['lng'],
					"latitude" => $location['lat'],
					"status" => $status[$location['status']],
					"description" => $location['remark'],
					"update_time" => $location['add_time'],
				];
			}
		}
		$postData = [
			"order_no" => $delivery->paotui_order_no,
			"source_order_no" => $delivery->source_order_no,
			"shop_id" => (string)$delivery->shop_id,
			"locations" => $locations,
		];
		
		return (new MaiyatianService())->requestPost(MaiyatianService::$requestDomain . '/location_change', 'location_change', $postData);
	}
}