<?php
/**
 * 优惠券发放
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/6/1 19:40
 */

namespace app\common\model\service;

use app\common\model\db\MiaomaiOrder;
use app\common\model\db\OrderPayInfo;
use app\common\model\db\OrderPayRefundInfo;
use app\common\model\db\SystemCouponHadpull;
use app\common\model\db\SystemCouponUseList;
use think\facade\Db;
use think\helper\Arr;

class SystemCouponHadpullService
{
    public $systemCouponHadpullObj = null;
    public function __construct()
    {
        $this->systemCouponHadpullObj = new SystemCouponHadpull();
    }

    /**
     * 优惠券发放计数
     * User: chenxiang
     * Date: 2020/6/1 19:45
     * @param array $where
     * @return mixed
     */
    public function countNum($where = []) {
        $result = $this->systemCouponHadpullObj->countNum($where);
        return $result;
    }

    /**
     * 新增
     * User: chenxiang
     * Date: 2020/6/1 20:06
     * @param $data
     * @return int
     */
    public function addAll($data) {
        $result = $this->systemCouponHadpullObj->addAll($data);
        return $result;
    }
    
    public function canUseSystemCoupon($uid, $money): array
    {
        $couponList = invoke_cms_model('System_coupon/get_user_coupon_list', [$uid, 1]);
        $couponList = $couponList['retval'];
        $systemCoupon = [];
        foreach ($couponList as $coupon){
            if($coupon['is_use']){
                continue;
            }
            $coupon = Arr::only($coupon, [
                'id', 'receive_time', 'coupon_id', 'name', 'img', 'cate_name', 'cate_id', 
                'discount', 'order_money', 'discount_max_money', 'is_discount', 'discount_value',
            ]);
            $coupon['receive_time'] = date('Y-m-d H:i:s', $coupon['receive_time']);
            $coupon['discount_text'] = $coupon['is_discount'] ? '折扣券' : '抵扣券';
            if ($coupon['order_money'] <= $money) {
                $systemCoupon[] = $coupon;
            }
        }
        return $systemCoupon;
    }
    
    public function useCoupon($couponReceiveId, $order, $merchantId, $uid)
    {
        $systemCoupon = SystemCouponHadpull::alias('p')
            ->join('system_coupon c', 'p.coupon_id = c.coupon_id')
            ->where(['p.id' => $couponReceiveId])
            ->find();
        $useResult = invoke_cms_model('System_coupon/user_coupon', [$couponReceiveId, $order['order_no'], 'miaomai',$merchantId, $uid]);
        fdump_api(['params' => [$couponReceiveId, $order, 'miaomai',$merchantId, $uid],'useSystemCoupon' => $useResult], 'miaomai/useSystemCoupon', 1);
        
        if($useResult['retval']['error_code']){
            return $useResult['retval'];
        }
        return [
            'discount_money' => $systemCoupon['discount'],
        ];
    }
    
    public function refundUseCoupon($orderNo)
    {
       return Db::transaction(function () use ($orderNo) {
            $order = MiaomaiOrder::where(['order_no' => $orderNo])->find();
            $receive = SystemCouponHadpull::where(['id' => $order->system_coupon_receive_id])->find();
            $receive->is_use = 0;
            $receive->use_time = 0;
            $receive->save();

            SystemCouponUseList::create([
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
                'pay_type' => 'system_coupon_money'
            ])->value('pay_money');

            OrderPayRefundInfo::saveInfo([
                'uid' => $order['uid'],
                'order_no' => $orderNo,
                'order_type' => 'miaomai',
                'refund_type' => 'system_coupon_money',
                'refund_money' => $payMoney,
            ]);
            return ['refund_coupon_money' => $payMoney];
       });
    }
}