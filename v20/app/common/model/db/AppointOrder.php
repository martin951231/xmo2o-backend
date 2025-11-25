<?php

namespace app\common\model\db;
use app\appoint\model\db\AppointSupply;
use think\Model;
class AppointOrder extends Model {
    
    protected $pk = 'order_id';
    const PAY_REFUND = 2;
    const PAY_CANCEL = 3;
    
    public function getGoodsDetail($orderId)
    {
        return self::where('order_id', $orderId)
            ->field('appoint_id goods_id, order_name as goods_name, product_price as price, 1 as goods_num')
            ->select();
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, 'uid', 'uid');
    }

	public function supply()
	{
		return $this->hasOne(AppointSupply::class, 'order_id', 'order_id');
	}
    
	public function category()
	{
		return $this->belongsTo(AppointCategory::class, 'cat_id', 'cat_id');
	}
    public function refund()
    {
        return $this->hasOne(AppointOrderRefund::class, 'order_id', 'order_id');
    }
    
    public function getCueFieldAttr($value)
    {
        if($value){
            $value = unserialize($value);
        }
        return $value;
    }

    public function getOrderTimeAttr($value)
    {
        if($value){
            $value = date('Y-m-d H:i:s', $value);
        }
        return $value;
    }

    public function getServiceStatusTextAttr($value, $data)
    {
        $text = [
            0 => '未服务',
            1 => '已服务', 
            2 => '已评价'
        ];
        if(isset($data['service_status'])){
            return $text[$data['service_status']];
        }
        return '';
    }

	public function getAppointTypeTextAttr($value, $data): string
	{
		$text = [
			0 => '到店',
			1 => '上门',
		];
		if(isset($data['appoint_type'])){
			return $text[$data['appoint_type']];
		}
		return '';
	}
}
