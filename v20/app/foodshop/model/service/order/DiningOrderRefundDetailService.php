<?php
/**
 * 餐饮订单退款详情service
 * Author: hengtingmei
 * Date Time: 2020/8/25 11:25
 */

namespace app\foodshop\model\service\order;
use app\foodshop\model\db\DiningOrderRefundDetail as DiningOrderRefundDetailModel;
class DiningOrderRefundDetailService {
    public $diningOrderRefundDetailModel = null;
    public function __construct()
    {
        $this->diningOrderRefundDetailModel = new DiningOrderRefundDetailModel();

    }

    /**退菜商品详情
     * @param $where
     * @param $field
     * @return array
     *
     */
    public function getRefundGoodList($where,$field)
    {
        $rs = $this->diningOrderRefundDetailModel->getRefundGoodList($where, $field);
        if (!empty($rs)) {
            return $rs->toArray();
        } else {
            return [];
        }
    }

    /**
     *批量插入数据
     * @param $data array
     * @return array
     */
    public function add($data){
        if(empty($data)){
            return false;
        }

        $data['create_time'] = time();

        $id = $this->diningOrderRefundDetailModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where){
        if(empty($where)){
            return false;
        }

        try {
            $result = $this->diningOrderRefundDetailModel->getSome($where);
        } catch (\Exception $e) {
            return false;
        }
        return $result->toArray();
    }

    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->diningOrderRefundModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }
    
}