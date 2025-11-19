<?php
/**
 * 商户店铺
 * Created by PhpStorm.
 * User: wangchen
 * Date: 2021/9/1
 */

namespace app\common\model\service;

use app\common\model\db\MerchantStore;

class MerchantStoreService
{
    public $merchantObj = null;

    /**
     * User: wangchen
     * Date: 2021/9/1
     */
    public function getCount($mer_id) {
        if(empty($mer_id)){
            return [];
        }
        $result = (new MerchantStore())->where(['mer_id'=>$mer_id])->count();
        return $result;
    }

    public function getThirdConfigByStoreId($storeId)
    {
        $where = [
            's.store_id' => $storeId,
            't.status' => 1
        ];
        $config = (new MerchantStore())->alias('s')
            ->join('third_party t', 's.third_party_alias = t.alias')
            ->where($where)
            ->field('t.*')
            ->findOrEmpty()
            ->toArray();
        return $config;
    }
    
    public function checkMiaomaiStore($miaomaiStoreId, $merchantMiaomaiAppid)
    {
        $store = MerchantStore::alias('s')
            ->join('merchant m', 's.mer_id = m.mer_id')
            ->where([
                'm.miaomaiyun_switch' => 1,
                'm.miaomaiyun_erp_appid' => $merchantMiaomaiAppid,
                's.miaomaiyun_erp_storeid' => $miaomaiStoreId
            ])->find();
        if(empty($store)){
            throw_exception('当前淼迈店铺未绑定');
        }
        return $store;
    }
}