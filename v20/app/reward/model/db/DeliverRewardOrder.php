<?php
/*
 * @Descripttion: 打赏订单表
 * @Author: wangchen
 * @Date: 2021-01-27 14:12:48
 * @LastEditors: wangchen
 * @LastEditTime: 2021-01-28 11:40:11
 */

namespace app\reward\model\db;

use think\Model;

class DeliverRewardOrder extends Model
{

    /**
     * 获取一条打赏记录
     * @author: 汪晨
     * @date: 2021/1/28
     */
    public function getOne($where, $fields = '*'){
        return $this->where($where)->field($fields)->find();
    }

}