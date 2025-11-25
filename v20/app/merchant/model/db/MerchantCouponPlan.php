<?php


namespace app\merchant\model\db;


use think\Model;

class MerchantCouponPlan extends Model
{
	const ALL_USER = 1;//所有用户
	const CONDITION_TYPE_2 = 2;//部分用户
	
	const SEND_DATE_TYPE = 1;//固定日期发放
	
	public function setCouponInfoAttr($value)
	{
		if(is_array($value)) {
			$value = json_encode($value, JSON_UNESCAPED_UNICODE);
		}
		return $value;
	}

	public function getCouponInfoAttr($value)
	{
		if($value) {
			$value = json_decode($value, true);
		}
		return $value;
	}
	
	public function searchPlanNameAttr($query, $value)
	{
		if($value){
			$query->where('plan_name', 'like', "%{$value}%");
		}
	}

	public function searchStartDateAttr($query, $value)
	{
		if($value){
			$query->where('create_time', '>=', $value. ' 00:00:00');
		}
	}

	public function searchEndDateAttr($query, $value)
	{
		if($value){
			$query->where('create_time', '<=', $value. ' 23:59:59');
		}
	}
}