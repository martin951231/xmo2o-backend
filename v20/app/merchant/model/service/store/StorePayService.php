<?php
/**
 * 店铺线下支付方式service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/09/02 14:18
 */

namespace app\merchant\model\service\store;
use app\merchant\model\db\StorePay as StorePay;
class StorePayService {
    public $storePayModel = null;
    public function __construct()
    {
        $this->storePayModel = new StorePay();
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

        $count = $this->storePayModel->getCount($where);
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
        $detail = $this->storePayModel->getOne($where);
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
    public function getSome($where){
        $detail = $this->storePayModel->getSome($where);
        if(!$detail) {
            return [];
        }
        return $detail->toArray();
    }

    /**
     * @param $where
     * 获取线下支付方式名称
     */
    public function getPayName($where,$field){
        $arr=$this->storePayModel->getOneVal($where,$field);
        $str="";
        if(!empty($arr)){
            $str=implode('/',$arr);
        }
        return $str;
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

        $result = $this->storePayModel->save($data);
        if(!$result) {
            return false;
        }

        return $this->storePayModel->id;
    }

}