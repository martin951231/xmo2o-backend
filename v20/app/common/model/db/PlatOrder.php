<?php
/**
 * 平台订单 Model
 * Created by PhpStorm.
 * User: hengtingmei
 * Date: 2020/06/01 14:12
 */

namespace app\common\model\db;

use think\Model;

class PlatOrder extends Model
{
    /**
     * 根据订单id获取订单
     * @param int $orderId
     * @return array|Model|null
     */
    public function getOrderByOrderId($orderId) {
        if(!$orderId){
            return null;
        }

        $where = [
            'order_id' => $orderId
        ];
        $result = $this->where($where)->find();
        return $result;
    }
}