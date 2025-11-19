<?php
/**
 * 餐饮临时订单详情model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/30 17:28
 */

namespace app\foodshop\model\db;
use think\Model;
class DiningOrderDetailTemporary extends Model {
    /**
     * 根据订单id返回购物车列表
     * @param $tempId array 条件
     * @return array|bool|Model|null
     */
    public function getGoodsListByTempId($tempId,$order=[]) {
        if(!$tempId){
            return null;
        }

        $where = [
            'temp_id' => $tempId
        ];

        $result = $this->where($where)->order($order)->select();
        return $result;
    }

    /**
     * 根据订单id返回购物车列表
     * @param $orderId array 条件
     * @return array|bool|Model|null
     */
    public function getGoodsListByOrderId($orderId) {
        if(!$orderId){
            return null;
        }

        $where = [
            'temp_id' => $orderId
        ];

        $result = $this->where($where)->select();
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