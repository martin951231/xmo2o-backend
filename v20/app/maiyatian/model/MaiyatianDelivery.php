<?php

namespace app\maiyatian\model;

use app\common\model\db\ServiceUserPublishLog;
use think\Model;
use think\model\relation\HasOne;

class MaiyatianDelivery extends Model
{
	protected $json = ['tags', 'calculate_fee'];
	protected $jsonAssoc = true;

	public function orderInfo(): HasOne
	{
		return $this->hasOne(MaiyatianOrder::class, 'id', 'order_id');
	}

	public function sender(): HasOne
	{
		return $this->hasOne(MaiyatianUser::class, 'id', 'sender_id');
	}

	public function receiver(): HasOne
	{
		return $this->hasOne(MaiyatianUser::class, 'id', 'receiver_id');
	}

	public function setDelayDeliveryTimeAttr($value)
	{
		if(intval($value) <= 0) {
			return null;
		}
		return date('Y-m-d H:i:s', $value);
	}

	public function setExpectFinishTimeAttr($value)
	{
		if(intval($value) <= 0) {
			return null;
		}
		return date('Y-m-d H:i:s', $value);
	}

	public function setPickupStartTimeAttr($value)
	{
		if(intval($value) <= 0) {
			return null;
		}
		return date('Y-m-d H:i:s', $value);
	}

	public function setPickupEndTimeAttr($value)
	{
		if(intval($value) <= 0) {
			return null;
		}
		return date('Y-m-d H:i:s', $value);
	}

	public function getStatusNameAttr($value, $data): string
	{
		$status = [
			'UNPROGRESS' => '未处理',
			'CREATED' => '待确认',
			'CONFIRM' => '已确认',
			'DELIVERY' => '待抢单',
			'PICKUP' => '待取货',
			'DELIVERING' => '配送中',
			'DONE' => '已完成',
			'CANCEL' => '已取消',
			'DELETE' => '已删除',
			'EXPECT' => '配送异常',
		];
		return $status[$data['status']] ?? '';
	}
	
	public function getLocationsAttr($value, $data): array
	{
		return ServiceUserPublishLog::where('publish_id', $data['publish_id'])
			->order('add_time', 'desc')
			->select()
			->toArray();
	}

}