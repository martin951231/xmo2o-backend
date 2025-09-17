<?php

namespace app\common\model\db;

use think\Model;

class MiaomaiOrderRefundSync extends Model
{
    public $json = ['goods', 'pays'];
    public $jsonAssoc = true;

    public static function saveInfo($params)
    {
        $order = self::where(['stores_id' => $params['stores_id'], 'ro_code' => $params['ro_code']])->find();
        if(empty($order)){
            return self::create($params);
        }
        return self::update($params, ['stores_id' => $params['stores_id'], 'ro_code' => $params['ro_code']]);
    }

    public function searchStoresNameAttr($query, $value)
    {
        $value && $query->where('stores_name','LIKE', $value . '%');
    }

    public function searchOrderCodeAttr($query, $value)
    {
        $value && $query->where('order_code','LIKE', $value . '%');
    }
}