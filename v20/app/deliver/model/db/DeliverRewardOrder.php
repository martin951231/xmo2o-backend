<?php
/*
 * @Descripttion: 打赏订单表
 * @Author: wangchen
 * @Date: 2021-01-27 17:04:00
 * @LastEditors: wangchen
 * @LastEditTime: 2021-01-28 10:28:36
 */

namespace app\deliver\model\db;

use think\Model;

class DeliverRewardOrder extends Model
{
    /**
     * 获取一条记录
     * @param $where
     * @param string $fields
     * @author: 汪晨
     * @date: 2021/1/28
     */
    public function getOne($where, $fields = '*')
    {
        return $this->where($where)->field($fields)->find();
    }
}