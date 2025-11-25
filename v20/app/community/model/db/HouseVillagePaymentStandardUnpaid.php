<?php


namespace app\community\model\db;

use think\Model;

class HouseVillagePaymentStandardUnpaid extends Model
{
    public function saveOne($where,$data)
    {
        $res = $this->where($where)->save($data);
        return $res;
    }
}