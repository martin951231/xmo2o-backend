<?php
/**
 * 餐饮订单购物车详情model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/06/03 10:43
 */

namespace app\foodshop\model\db;
use think\Model;
class DiningOrderTemp extends Model {
    /**
     * 根据订单id返回购物车列表
     * @param $orderId int 条件
     * @return array|bool|Model|null
     */
    public function getGoodsListByOrderId($orderId,$order=[]) {
        if(!$orderId){
            return null;
        }

        $where = [
            'order_id' => $orderId
        ];

        $result = $this->where($where)->order($order)->select();
        return $result;
    }

    /**
     * 获取单个购物车商品信息
     * @param $where array 条件
     * @return array|bool|Model|null
     */
    public function getGoodsByCondition($where) {
        if(!$where){
            return null;
        }
        $result = $this->where($where)->find();
        return $result;
    }

    /**
     * 获取购物车商品列表
     * @param $where array 条件
     * @return array|bool|Model|null
     */
    public function getGoodsListByCondition($where) {
        if(!$where){
            return null;
        }
        $result = $this->where($where)->select();
        return $result;
    }
    
}