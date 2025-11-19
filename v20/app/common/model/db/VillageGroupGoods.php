<?php


namespace app\common\model\db;


use think\Model;

class VillageGroupGoods extends Model
{
    public function searchNameAttr($query, $value, $data)
    {
        $value && $query->where('name','like', '%'. $value . '%');
    }
    
    public function liveGoods()
    {
        return $this->hasOne(LiveGoods::class, 'business_id', 'goods_id');
    }
}