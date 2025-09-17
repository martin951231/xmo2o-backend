<?php

class Merchant_spread_userModel extends Model
{

    public function isVipSpread($uid)
    {
        $rs = $this->where(['uid' => $uid])->find();
        return boolval($rs['is_vip_spread']);
    }

    public function isCitySpread($uid)
    {
        $rs = $this->where(['uid' => $uid])->find();
        return boolval($rs['is_city_spread']);
    }
}