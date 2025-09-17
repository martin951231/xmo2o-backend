<?php


namespace app\mall\model\db;

use think\Model;
class MerchantStoreKefu extends Model
{
    //查询数据
    public function getOne($store_id,$belong=''){
        $where = [
            ['store_id', '=', $store_id],
        ];
        if ($belong) {
            $where[] = ['belong', '=', $belong];
        }
        $info = $this->where($where)->find();
        return empty($info)?[]:$info->toArray();
    }
}