<?php
namespace app\foodshop\model\db;
use think\Model;
class FoodshopOrder extends Model {
    /**
     * 更新数据
     * @param $orderId int
     * @param $data array
     * @return array
     */
    public function updateByOrderId($orderId, $data) {
        if(empty($orderId) || empty($data)){
            return false;
        }

        $where = [
            'order_id' => $orderId
        ];
        $result = $this->where($where)->update($data);
        if(!$result) {
            return false;
        }
        return $result;
    }
}