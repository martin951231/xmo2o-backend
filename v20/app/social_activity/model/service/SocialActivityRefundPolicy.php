<?php
namespace app\social_activity\model\service;

class SocialActivityRefundPolicy
{
    public static function userResult(array $refund)
    {
        $status = (int)$refund['status'];
        $display = $status === 2 ? 2 : (in_array($status, [3, 4], true) || !empty($refund['failure_reason']) ? 3 : 1);
        return ['refund_id' => $refund['id'], 'refund_status' => $display,
            'status_text' => [1 => '退款中', 2 => '已退款', 3 => '退款失败，请联系工作人员'][$display]];
    }

    public static function cents($amount)
    {
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', (string)$amount)) {
            throw new \RuntimeException('支付金额格式异常');
        }
        $parts = explode('.', (string)$amount);
        return (int)$parts[0] * 100 + (int)str_pad($parts[1] ?? '', 2, '0');
    }

    public static function validate(array $order, $uid, $now)
    {
        if (!$uid || (int)$order['uid'] !== (int)$uid) {
            throw new \RuntimeException('只能退本人订单');
        }
        if (self::cents($order['amount']) <= 0) {
            throw new \RuntimeException('免费活动不支持退款');
        }
        if ((int)$order['paid'] !== 1 || (int)$order['status'] !== 10) {
            throw new \RuntimeException('订单未支付或已退款');
        }
        if (!empty($order['refund_status'])) {
            throw new \RuntimeException('订单正在退款中，请勿重复申请');
        }
        if (!empty($order['is_sign'])) {
            throw new \RuntimeException('订单已核销或已签到，不能退款');
        }
        // 退款资格只按订单状态判断，不限制支付时间或场次时间。
    }
}
