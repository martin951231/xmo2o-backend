<?php

namespace app\common\model\db;

use think\Model;

class MiaomaiOrderRefundUpload extends Model
{
    public static function saveInfo($params)
    {
        $order = self::where(['refund_id' => $params['refund_id'], 'order_type' => $params['order_type']])->find();
        if(empty($order)){
            return self::create($params);
        }
        return self::update($params, ['refund_id' => $params['refund_id'], 'order_type' => $params['order_type']]);
    }
    
    public function setDataAttr($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function setResultAttr($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}