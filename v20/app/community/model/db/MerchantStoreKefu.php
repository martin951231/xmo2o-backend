<?php


namespace app\community\model\db;

use think\Model;

class MerchantStoreKefu extends Model
{
    public function getOne($where=[],$field=true)
    {
        $data = $this->alias('k')
            ->leftJoin('house_worker w','k.bind_uid = w.wid')
            ->where($where)
            ->field($field)
            ->find();
        return $data;
    }
}