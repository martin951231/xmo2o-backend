<?php

namespace app\common\model\service;

use app\common\model\db\CardNewCoupon;
use app\common\model\db\CardNewCouponHadpull;
use app\common\model\db\CardNewCouponUseList;
use app\common\model\db\MiaomaiOrder;
use app\common\model\db\OrderPayInfo;
use app\common\model\db\OrderPayRefundInfo;
use think\facade\Db;
use think\helper\Arr;

class CardNewCouponService
{
    public function canUseMerchantCoupon($uid, $money)
    {
        $couponList =  invoke_cms_model('Card_new_coupon/get_user_all_coupon_list', [$uid, 1]);
        $couponList = $couponList['retval'];
        $merchantCoupon = [];
        foreach ($couponList as $coupon){
            if($coupon['is_use']){
                continue;
            }
            $coupon = Arr::only($coupon, [
                'id', 'receive_time', 'is_use', 'coupon_id', 'name', 'img', 'cate_name', 'cate_id',
                'discount', 'order_money', 'discount_max_money', 'is_discount', 'discount_value',
            ]);
            $coupon['receive_time'] = date('Y-m-d H:i:s', $coupon['receive_time']);
            $coupon['discount_text'] = $coupon['is_discount'] ? '折扣券' : '抵扣券';
            if ($coupon['order_money'] <= $money) {
                $merchantCoupon[] = $coupon;
            }
        }
        return $merchantCoupon;
    }
    
    public function useCoupon($couponReceiveId, $orderId, $merchantId, $uid)
    {
        $merchantCoupon = CardNewCouponHadpull::alias('p')
            ->join('card_new_coupon c', 'p.coupon_id = c.coupon_id')
            ->where(['p.id' => $couponReceiveId])
            ->find();
        $useResult = invoke_cms_model('Card_new_coupon/user_coupon', [$couponReceiveId, $orderId, 'miaomai',$merchantId, $uid]);
        fdump_api(['params' => [$couponReceiveId, $orderId, 'miaomai',$merchantId, $uid],'useMerchantCoupon' => $useResult], 'miaomai/useMerchantCoupon', 1);
        if($useResult['retval']['error_code']){
            return $useResult['retval'];
        }
        return [
            'discount_money' => $merchantCoupon['discount'],
        ];
    }
    public function refundUseCoupon($orderNo)
    {
        return Db::transaction(function () use ($orderNo) {
            $order = MiaomaiOrder::where(['order_no' => $orderNo])->find();
            if(empty($order->mer_coupon_receive_id)){
                throw_exception('订单未使用商家券');
            }
            $receive = CardNewCouponHadpull::where(['id' => $order->mer_coupon_receive_id])->find();
            $receive->is_use = 0;
            $receive->use_time = 0;
            $receive->save();

            CardNewCouponUseList::create([
                'order_type' => 'miaomai',
                'order_id' => $order->order_no,
                'type' => 1,
                'hadpull_id' => $receive->id,
                'coupon_id' => $receive->coupon_id,
                'uid' => $order['uid'],
                'num' => 1,
                'use_time' => time(),
            ]);
            $payMoney = OrderPayInfo::where([
                'order_no' => $orderNo,
                'order_type' => 'miaomai',
                'pay_type' => 'merchant_coupon_money'
            ])->value('pay_money');

            OrderPayRefundInfo::saveInfo([
                'uid' => $order['uid'],
                'order_no' => $orderNo,
                'order_type' => 'miaomai',
                'refund_type' => 'merchant_coupon_money',
                'refund_money' => $payMoney,
            ]);
            return ['refund_coupon_money' => $payMoney];
        });
    }
}