<?php


namespace app\merchant\model\db;


use think\Model;

class MerchantCouponPlanSendRecords extends Model
{
	const STATUS_UN_SEND = 0;
	const STATUS_SEND_SUCCESS = 1;
	const STATUS_SEND_FAILED = 2;
	public function getStatusTextAttr($value, $data): string
	{
		$status = [
			self::STATUS_UN_SEND => '未发放', 
			self::STATUS_SEND_SUCCESS => '已发放', 
			self::STATUS_SEND_FAILED => '发放失败'
		];
		return $status[$data['status']] ?? '';
	}
}