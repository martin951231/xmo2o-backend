<?php
/**
 * 餐饮支付临时订单service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/09/07 11:40
 */

namespace app\foodshop\model\service\order;
use app\foodshop\model\db\DiningOrderPayTemp;

class DiningOrderPayTempService {
    public $diningOrderPayTempModel = null;
    public function __construct()
    {
        $this->diningOrderPayTempModel = new DiningOrderPayTemp();
       
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        // 保存用户uid
        if(isset($data['user_type']) && $data['user_type'] == 'uid' && $data['user_id']){
            $data['uid'] = $data['user_id'];
        }
        $result = $this->diningOrderPayTempModel->save($data);
        if(!$result) {
            return false;
        }
        return $this->diningOrderPayTempModel->id;
    }

    /**
     * 根据条件返回订单
     * @param $where array 条件
     * @return array
     */
    public function getOne($where){
        $order = $this->diningOrderPayTempModel->getOne($where);
        if(!$order) {
            return [];
        }
        return $order->toArray();
    }
    
}