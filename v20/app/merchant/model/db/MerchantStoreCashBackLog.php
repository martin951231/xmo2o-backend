<?php
/**
 * 现金返还model
 */
namespace app\merchant\model\db;
use think\Model;
class MerchantStoreCashBackLog extends Model 
{
    public function getAddTimeTextAttr($value, $data)
    {
        return date('Y-m-d H:i:s', $data['add_time']);
    }
}