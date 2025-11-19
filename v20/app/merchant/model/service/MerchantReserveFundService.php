<?php
/**
 * 商家平台采购备用金service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/12 10:54
 */

namespace app\merchant\model\service;
use app\merchant\model\db\MerchantReserveFund as MerchantReserveFundModel;
class MerchantReserveFundService {
    public $merchantReserveFundModel = null;
    public function __construct()
    {
        $this->merchantReserveFundModel = new MerchantReserveFundModel();
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $result = $this->merchantReserveFundModel->save($data);
        if(!$result) {
            return false;
        }
        return $this->merchantReserveFundModel->id;
        
    }
    
    /**
     * 根据条件返回一条数据
     * @param $where array 条件
     * @return array
     */
    public function getOne($where){
        $order = $this->merchantReserveFundModel->getOne($where);
        if(!$order) {
            return [];
        }
        return $order->toArray();
    }
}