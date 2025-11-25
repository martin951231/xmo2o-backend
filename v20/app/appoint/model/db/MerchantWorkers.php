<?php


namespace app\appoint\model\db;


use app\common\model\db\MerchantStore;
use think\Model;

class MerchantWorkers extends Model
{
    public function merchantStore()
    {
        return $this->belongsTo(MerchantStore::class, 'merchant_store_id', 'store_id');
    }
}