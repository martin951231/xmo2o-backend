<?php

namespace app\internal_card\model\db;

use think\Model;

class InternalMembershipCardBill extends Model
{
    public function searchMerIdAttr($query, $value, $data)
    {
        $prefix = $data['bill_prefix'] ?? '';
        $value && $query->where($prefix.'mer_id','=', $value);
    }

    public function searchBillDateAttr($query, $value, $data)
    {
        $prefix = $data['bill_prefix'] ?? '';
        $value && $query->where($prefix.'bill_date','=', $value);
    }
}