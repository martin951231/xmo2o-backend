<?php
/**
 * 团购支付临时订单service
 * Author: hengtingmei
 * Date Time: 2021/05/27 09:18
 */

namespace app\group\model\service\order;
use app\group\model\db\GroupOrderTemp;

class GroupOrderTempService {
    public $groupOrderTempModel = null;
    public function __construct()
    {
        $this->groupOrderTempModel = new GroupOrderTemp();
       
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $detail = [];
        if($data['temp_id']){
            $detail = $this->getOne(['id' => $data['temp_id']]);
        }

        if($detail){
            $tempId = $data['temp_id'];
            unset($data['temp_id']);
            $result = $this->groupOrderTempModel->where(['id' => $tempId])->save($data);
            return $tempId;
        }else{
            $result = $this->groupOrderTempModel->save($data);
            if(!$result) {
                return false;
            }
            return $this->groupOrderTempModel->id;
        }
    }

    /**
     * 根据条件返回订单
     * @param $where array 条件
     * @return array
     */
    public function getOne($where){
        $order = $this->groupOrderTempModel->getOne($where);
        if(!$order) {
            return [];
        }
        return $order->toArray();
    }
    
}