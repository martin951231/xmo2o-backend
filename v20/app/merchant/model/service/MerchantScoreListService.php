<?php
/**
 * 商家收入service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/12 09:18
 */

namespace app\merchant\model\service;
use app\merchant\model\db\MerchantScoreList as MerchantScoreListModel;
class MerchantScoreListService {
    public $merchantScoreListModel = null;
    public function __construct()
    {
        $this->merchantScoreListModel = new MerchantScoreListModel();
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $result = $this->merchantScoreListModel->save($data);
        if(!$result) {
            return false;
        }
        return $this->merchantScoreListModel->id;
        
    }
    
    /**
     * 根据条件返回一条数据
     * @param $where array 条件
     * @return array
     */
    public function getOne($where){
        $order = $this->merchantMoneyListModel->getOne($where);
        if(!$order) {
            return [];
        }
        return $order->toArray();
    }
}