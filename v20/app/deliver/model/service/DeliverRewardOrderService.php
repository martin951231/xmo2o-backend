<?php
/*
 * @Descripttion: 打赏订单服务
 * @Author: wangchen
 * @Date: 2021-01-27 16:54:40
 * @LastEditors: wangchen
 * @LastEditTime: 2021-02-03 17:09:24
 */

namespace app\deliver\model\service;


use app\deliver\Code;
use app\deliver\model\db\DeliverRewardOrder;
use think\Exception;

class DeliverRewardOrderService
{
    public $DeliverRewardOrderMod;

    public function __construct()
    {
        $this->DeliverRewardOrderMod = new DeliverRewardOrder();
    }

    /**
     * 写入打赏订单表
     * @author: 汪晨
     * @date: 2021/1/27
     */
    public function saveOrder($rewardData)
    {
        $rewardData['create_time'] = time();
        $id = $this->DeliverRewardOrderMod->insertGetId($rewardData);
        if ($id < 1) {
            return false;
        } else {
            return $id;
        }
    }

    /**
     * 修改打赏订单表
     * @author: 汪晨
     * @date: 2021/2/3
     */
    public function upOrder($rewardData)
    {
        $rewardData['create_time'] = time();
        return $this->DeliverRewardOrderMod->where(['user_id'=>$rewardData['user_id'],'takeout_id'=>$rewardData['takeout_id']])->update($rewardData);
    }

    /**
     * 查询一条打赏订单表
     * @author: 汪晨
     * @date: 2021/2/3
     */
    public function getOneOrder($where, $fields = '*')
    {
        return $this->DeliverRewardOrderMod->where($where)->field($fields)->find();
    }

}
