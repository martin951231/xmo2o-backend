<?php
/**
 * 商家客服service
 * Author: hengtingmei
 * Date Time: 2021/5/11 
 */

namespace app\merchant\model\service;
use app\merchant\model\db\CustomerService;
class CustomerServiceService {
    public $customerServiceModel = null;
    public function __construct()
    {
        $this->customerServiceModel = new CustomerService();
    }

    /**
     * 根据条件获取其数量
     * @param $where array $where
     * @return array
     */
    public function getCount($where) {
        if(empty($where)){
            return false;
        }

        $count = $this->customerServiceModel->getCount($where);
        if(!$count) {
            return 0;
        }

        return $count;
    }

    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getOne($where){
        $detail = $this->customerServiceModel->getOne($where);
        if(!$detail) {
            return [];
        }
        return $detail->toArray();
    }

    /**
     * 根据条件返回多条数据
     * @param $where array 条件
     * @return array
     */
    public function getSome($where, $field = true,$order=true,$page=0,$limit=0){
        $detail = $this->customerServiceModel->getSome($where,$field,$order,$page,$limit);
        if(!$detail) {
            return [];
        }
        return $detail->toArray();
    }

    /**
     * 添加数据
     * @param $where array 条件
     * @return array
     */
    public function add($data) {
        if( empty($data)){
            return false;
        }

        $result = $this->customerServiceModel->save($data);
        if(!$result) {
            return false;
        }

        return $this->customerServiceModel->id;
    }
}