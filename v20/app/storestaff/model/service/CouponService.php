<?php
/**
 * 店员优惠券service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/08/29 17:35
 */

namespace app\storestaff\model\service;

use app\common\model\service\coupon\MerchantCouponService;
use app\common\model\service\coupon\SystemCouponService;
use app\merchant\model\service\MerchantStoreService;

class CouponService {
    public $store = null;
    public function __construct($staff)
    {
        $this->store = (new MerchantStoreService())->getStoreByStoreId($staff['store_id']);
    }

    /**
     * 发送短信验证吗
     * @param $param array 登录信息
     * @return array
     */
    public function getCouponList($param){

        if(empty($param['uid']) || empty($param['type'])  || empty($param['business'])){
            throw new \think\Exception(L_("缺少参数!"), 1001);
        }
        $couponList = [];
        $orderInfo = [
            'can_coupon_money' => $param['money'],
            'business' => $param['business'],
            'store_id' => $this->store['store_id'],
        ];
        if($param['type'] == 'system'){
            $couponList = (new SystemCouponService())->getAvailableCoupon( $param['uid'], $orderInfo);
        }elseif($param['type'] == 'merchant'){ // 会员卡于优惠不同享
            if($param['has_merchant_discount'] >0){
                $orderInfo['merchant_card'] = true;
            }
            $couponList = (new MerchantCouponService())->getAvailableCoupon($param['uid'], $this->store['mer_id'],$orderInfo);
        }

        $returnArr = [];
        $couponList = (new SystemCouponService())->formatDiscount($couponList);
        $returnArr = [];
        foreach ($couponList as $_coupon){
            $temp = [];
            $temp['had_id'] = $_coupon['id'];
            $temp['discount'] = $_coupon['discount'];
            $temp['order_money'] = $_coupon['order_money'];
            $temp['end_time'] = date('Y-m-d',$_coupon['end_time']);
            $temp['discount_title'] = $_coupon['discount_title'];
            $temp['discount_des'] = $_coupon['discount_des'];
            $temp['is_discount'] = $_coupon['is_discount'];
            $returnArr[] = $temp;
        }
        return $returnArr;
    }


}