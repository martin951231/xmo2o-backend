<?php

namespace app\internal_card\model\db;

use Carbon\Carbon;
use think\Model;

class InternalMembershipCardLog extends Model
{
    const MONEY_OUT = 2;//消费
    const MONEY_REFUND = 3;//退款
    public static function businessType(): array
    {
        return [
            'shop' => cfg('shop_alias_name') ?: '外卖',
            'shop_offline' => L_('线下零售'),
            'offline' => L_('线下支付'),
            'group' => cfg('group_alias_name') ?: '团购',
            'dining' => cfg('dining_alias_name') ?: '餐饮',
            'mall' => cfg('mall_alias_name') ?: '商城',
            'appoint' => cfg('appoint_alias_name') ?: '预约',
            'store' => L_('优惠买单'),
            'cash' => L_('店内收银'),
        ];
    }
    public function getCreateTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    public function getBusinessTypeTextAttr($value, $data): string
    {
        $orderType = self::businessType();
        return $orderType[$data['business_type']] ?? $data['business_type'];
    }

    public function searchMerIdAttr($query, $value, $data)
    {
        $prefix = $data['log_prefix'] ?? '';
        $value && $query->where($prefix.'mer_id', '=', $value);
    }

    public function searchBillDateAttr($query, $value, $data)
    {
        if ($value) {
            $now = Carbon::parse($value . '-01');
            $startTime = $now->startOfMonth()->timestamp;
            $endTime = $now->lastOfMonth()->timestamp + 24 * 3600;
            $prefix = $data['log_prefix'] ?? '';
            $query->where($prefix . 'create_time', 'between', "{$startTime},{$endTime}");
        }
    }
}