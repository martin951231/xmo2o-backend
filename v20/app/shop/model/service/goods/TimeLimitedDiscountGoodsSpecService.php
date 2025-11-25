<?php
/**
 * 限时优惠规格价格库存表
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/20 10:24
 */

namespace app\shop\model\service\goods;
use app\shop\model\db\TimeLimitedDiscountGoodsSpec as TimeLimitedDiscountGoodsSpecModel;
class TimeLimitedDiscountGoodsSpecService{
    public $timeLimitedDiscountGoodsSpecModel = null;
    public function __construct()
    {
        $this->timeLimitedDiscountGoodsSpecModel = new TimeLimitedDiscountGoodsSpecModel();
    }
    
    /**
     * 根据限时优惠id获取规格信息
     * @param $limitId
     * @return bool
     */
    public function getSpecList($limitId,$field = '*'){
        if (!$limitId) {
            return [];
        }
        
        $result = $this->timeLimitedDiscountGoodsSpecModel->getSpecList($limitId, $field);
        if(!$result){
            return [];
        }
        
        return $result->toArray();
    }

    /**
     * 充值限时优惠规格库存
     * @param $limitId
     * @return bool
     */
    public function resetStock($limitId){
        if (!$limitId) {
            return false;
        }
        
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $sql = 'UPDATE ' . $prefix . 'time_limited_discount_goods_spec SET `stock`=`origin_stock` WHERE limit_id = ' . intval($limitId);

        try {
            $result = $this->timeLimitedDiscountGoodsSpecModel->query($sql);
        }catch (\Exception $e) {
            return false;
        }
        
        return $result;
    }

    /**
     * 更新库存
     * @param $num
     * @param $id
     * @param $type 操作类型 0：减库存，1：加库存
     * @return bool
     */
    public function updateStock($num, $id, $type){
        // 限时优惠详情
        $discount = $this->getSpecById($id);
        if(!$discount){
            return false;
        }

        // 更新数据
        $data = [];
        if ($type == 0) {
            $data['stock'] = $discount['stock'] - $num;
            $data['stock'] = max(0,$data['stock']);
        } else {
            $data['stock'] = $discount['stock'] + $num;
        }

        // 更新库存
        $result = $this->updateById($id, $data);
        $today = date('Y-m-d');
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    
    /**
     * 根据id获取
     * @param $id
     * @return array
     */
    public function getSpecById($id){
        if(!$id){
            return [];
        }
        $result = $this->timeLimitedDiscountGoodsSpecModel->getSpecById($id);
        if(empty($result)){
            return [];
        }
        return $result->toArray();
    }

    /**
     * 更新数据
     * @param $id
     * @param $data
     * @return bool
     */
    public function updateById($id,$data){
        if (!$id || !$data) {
            return false;
        }
        
        try {
            $result = $this->timeLimitedDiscountGoodsSpecModel->updateById($id,$data);
        }catch (\Exception $e) {
            return false;
        }
        
        return $result;
    }

    
    
    /**
     * 新增数据
     * @param $data
     * @return bool|intval
     */
    public function save($data){
        if (!$data) {
            return false;
        }
        
        try {
            $result = $this->timeLimitedDiscountGoodsModel->add();
        }catch (\Exception $e) {
            return false;
        }
        
        return $result->id;
    }

}