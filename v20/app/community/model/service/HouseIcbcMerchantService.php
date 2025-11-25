<?php

namespace app\community\model\service;


/**
 * 小区绑定工行支付子商户表
 *
 * @author: zt
 * @date: 2022/08/31
 */
class HouseIcbcMerchantService
{
    public function getByVillageId($villageId)
    {
        $result = \think\facade\Db::name('house_icbc_merchant')->where('village_id', $villageId)->select()->toArray();
        return $result;
    }
}
