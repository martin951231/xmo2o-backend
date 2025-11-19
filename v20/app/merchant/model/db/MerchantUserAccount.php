<?php

namespace app\merchant\model\db;

use think\Model;

class MerchantUserAccount extends Model
{
    public function setMenusAttr($value)
    {
        return implode(',', $value);
    }
    
    public function getMenusAttr($value)
    {
        return explode(',', $value);
    }
}