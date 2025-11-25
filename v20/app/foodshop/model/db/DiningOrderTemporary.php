<?php
/**
 * 餐饮临时订单model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/30 17:28
 */

namespace app\foodshop\model\db;
use think\Model;
class DiningOrderTemporary extends Model {

    /**
     * 根据条件返回订单列表
     * @param $where array 条件
     * @param $order array 排序
     * @return array|bool|Model|null
     */
    public function getOrderListByCondition($where,$order=['sort_id']) {
        if(!$where){
            return null;
        }

        $result = $this->where($where)->order($order)->select();
        return $result;
    }
    
    /**
     * 根据订单id条件返回订单
     * @param $tempId int 条件
     * @return array|bool|Model|null
     */
    public function getOrderByTempId($tempId,$order=[]) {
        if(!$tempId){
            return null;
        }

        $where = [
            'temp_id' => $tempId
        ];

        $result = $this->where($where)->order($order)->find();
        return $result;
    }
}