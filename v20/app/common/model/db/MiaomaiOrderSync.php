<?php

namespace app\common\model\db;

use think\Model;

class MiaomaiOrderSync extends Model
{
    public $json = ['goods', 'pays', 'discounts'];
    public $jsonAssoc = true;

    public static function saveInfo($params)
    {
        $order = self::where(['stores_id' => $params['stores_id'], 'order_code' => $params['order_code']])->find();
        if(empty($order)){
            return self::create($params);
        }
        return self::update($params, ['stores_id' => $params['stores_id'], 'order_code' => $params['order_code']]);
    }
    
    public function searchStoresNameAttr($query, $value)
    {
        $value && $query->where('stores_name','LIKE', $value . '%');
    }

    public function searchOrderCodeAttr($query, $value)
    {
        $value && $query->where('order_code','LIKE', $value . '%');
    }

    public function searchCasNameAttr($query, $value)
    {
        $value && $query->where('cas_name','LIKE', $value . '%');
    }
    
}