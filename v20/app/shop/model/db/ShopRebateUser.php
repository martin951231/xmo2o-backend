<?php


namespace app\shop\model\db;


use think\Model;

class ShopRebateUser extends Model
{
    public function getUserList($where){
        $list = $this->field('a.id,a.create_time,b.reset_day,b.end_time')->alias('a')
            ->join('shop_rebate b','a.rid = b.id')
            ->where($where)
            ->select()->toArray();
        return $list;
    }
}