<?php

namespace app\internal_card\model\db;

use think\Model;

class InternalMembershipCardBillRecord extends Model
{
    public function searchMerIdAttr($query, $value, $data)
    {
        $value && $query->where('mer_id', '=', $value);
    }

    public function searchBillDateAttr($query, $value, $data)
    {
        $value && $query->where('bill_date', '=', $value);
    }

    public function getBillTypeTextAttr($value, $data)
    {
        $billType = [ 1 =>'现金', '对公转账'];
        return $billType[$data['bill_type']] ?? '';
    }

    public function getTaxTicketTypeTextAttr($value, $data)
    {
        $billType = [ 1 => '专票', '普票'];
        return $billType[$data['tax_ticket_type']] ?? '';
    }
}