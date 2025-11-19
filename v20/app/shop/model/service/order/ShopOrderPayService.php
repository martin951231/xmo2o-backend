<?php
/**
 * 外卖订单支付服务
 * @author: 张涛
 * @date: 2020/8/21
 */

namespace app\shop\model\service\order;

use app\common\model\db\ShopOrder;
use app\common\model\service\coupon\MerchantCouponService;
use app\common\model\service\coupon\SystemCouponService;
use app\common\model\service\DeliverSetService;
use app\common\model\service\order\SystemOrderService;
use app\common\model\service\percent_rate\PercentRateService;
use app\common\model\service\UserService;
use app\employee\model\db\EmployeeCardUser;
use app\merchant\model\service\MerchantStoreService;
use app\shop\model\db\MerchantStoreShop;
use think\Exception;

class ShopOrderPayService
{
    public $shopOrderMod = null;

    public function __construct()
    {
        $this->shopOrderMod = new ShopOrder();

    }

    /**
     * 获取支付订单详情
     * @author: 张涛
     * @date: 2020/8/22
     */
    public function getOrderPayInfo($orderId)
    {
        if (empty($orderId)) {
            throw new Exception(L_("参数错误"), 1001);
        }
        $shopOrder = $this->shopOrderMod->getOne(['order_id' => $orderId]);
        $timeRemain = 900;
        if (empty($shopOrder)) {
            throw new Exception(L_("当前订单不存在！"), 1003);
        }
        if ($shopOrder['paid']) {
            throw new Exception(L_("订单已支付"), 1003);
        }
        if ($shopOrder['status'] == 4 || $shopOrder['status'] == 5) {
            throw new Exception(L_("您的订单已取消，不能付款了！"), 1003);
        }
        //判断订单已过期
        if ($shopOrder['is_pick_in_store'] < 2 && $shopOrder['expect_use_time'] && $shopOrder['expect_use_time'] < time()) {
            $this->shopOrderMod->updateOrder(['order_id' => $orderId], ['status' => 5]);
            (new SystemOrderService())->cancelOrder('shop', $orderId);
            $log = [
                'order_id' => $orderId,
                'status' => 10,
                'name' => L_('自动取消'),
                'phone' => '',
                'note' => L_('超时未付款，自动取消订单')
            ];
            invoke_cms_model('Shop_order_log/add_log', [$log]);
            throw new Exception(L_('超时未付款，自动取消订单'), 1003);
        }

        // 店铺信息
        $store = (new MerchantStoreService())->getStoreByStoreId($shopOrder->store_id);

        $payInfact = $shopOrder->price - $shopOrder->card_price - $shopOrder->coupon_price;
        $returnArr['order_money'] = get_format_number($payInfact);
        $returnArr['paid'] = $shopOrder->paid;
        $returnArr['order_no'] = $shopOrder->real_orderid;
        $returnArr['store_id'] = $shopOrder->store_id;
        $returnArr['city_id'] = $store['city_id'];
        $returnArr['mer_id'] = $store['mer_id'];
        $returnArr['is_cancel'] = 0;
        $returnArr['time_remaining'] = $timeRemain;//秒
        $returnArr['uid'] = $shopOrder->uid;
        $returnArr['title'] = $store['name'];
        $returnArr['business_order_sn'] = $shopOrder['real_orderid'];
        return $returnArr;
    }


    /**
     * 支付成功后跳转地址
     * @author: 张涛
     * @date: 2020/8/22
     */
    public function getPayResultUrl($orderId)
    {
        $redirctUrl = cfg('site_url') . '/wap.php?c=Shop&a=status&order_id=' . $orderId;
        return $redirctUrl;
    }

    /**
     * 支付成功更新订单状态
     * @author: 张涛
     * @date: 2020/8/22
     */
    public function afterPay($orderId, $payParam)
    {
        if (!$orderId) {
            throw new Exception(L_("参数错误"), 1001);
        }
        $shopOrder = $this->shopOrderMod->getOne(['order_id' => $orderId]);
        if (!$shopOrder) {
            throw new Exception(L_("当前订单不存在！"), 1003);
        }
        if ($shopOrder['paid'] == 1) {
            throw new Exception(L_("订单已支付"), 1003);
        }

        $shopOrder = $shopOrder->toArray();
        $aliasName = cfg('shop_alias_name');

        //获取购买用户信息
        $userService = new UserService();
        $buyer = $userService->getUser($shopOrder['uid'], 'uid');
        if (empty($buyer)) {
            throw new Exception(L_("购买用户不存在"), 1003);
        }

        //获取店铺信息
        $store = (new MerchantStoreService())->getStoreByStoreId($shopOrder['store_id']);
        $shop = (new MerchantStoreShop())->where('store_id', $shopOrder['store_id'])->findOrEmpty();
        if (empty($store) || empty($shop)) {
            throw new Exception(L_("店铺不存在"), 1003);
        }

        //判断帐户余额,扣除余额
        $balancePay = floatval($shopOrder['balance_pay']);
        if ($balancePay && $buyer['now_money'] < $balancePay) {
            throw new Exception(L_("您的帐户余额不够此次支付"), 1003);
        }
        if ($balancePay > 0) {
            $useResult = (new UserService())->userMoney($shopOrder['uid'], $balancePay, L_("购买X1商品，扣除余额，订单编号X2", array("X1" => $aliasName, "X2" => $shopOrder['real_orderid'])));
            if ($useResult['error_code']) {
                throw new Exception($useResult['msg'], 1003);
            }
        }

        //积分抵扣，扣除积分
        $scoreUsedCount = $shopOrder['score_used_count'];
        if ($scoreUsedCount && $buyer['score_count'] < $scoreUsedCount) {
            throw new Exception(L_("您的积分余额不够此次支付"), 1003);
        }
        if ($scoreUsedCount > 0) {
            $use_result = (new UserService())->userScore($shopOrder['uid'], $scoreUsedCount, L_("购买X1商品 ，扣除X2 ，订单编号X3", array("X1" => $aliasName, "X2" => cfg('score_name'), "X3" => $shopOrder['real_orderid'])));
            if ($use_result['error_code']) {
                throw new Exception($use_result['msg'], 1003);
            }
        }

        if(customization('life_tools') && $payParam['employee_card_user_id']){
            $employeeCardUserModel = new EmployeeCardUser();
            $cardUser = $employeeCardUserModel->where('user_id', $payParam['employee_card_user_id'])->find();
            if($cardUser){
                //使用员工卡余额支付
                if($payParam['current_employee_balance'] > 0){
                    if($cardUser->card_money < $payParam['current_employee_balance']){
                        throw new \think\Exception("员工卡余额不足！");
                    }
                    $employeeCardUserModel->where('user_id', $payParam['employee_card_user_id'])->update([
                        'card_money'    =>  $cardUser->card_money - $payParam['current_employee_balance'],
                        'last_time'     =>  time()
                    ]);
                }
                //使用员工卡积分支付
                if($payParam['current_employee_score_deducte'] > 0){
                    if($cardUser->card_score < $payParam['current_employee_score_deducte']){
                        throw new \think\Exception("员工卡积分不足！");
                    }
                    $employeeCardUserModel->where('user_id', $payParam['employee_card_user_id'])->update([
                        'card_score'    =>  $cardUser->card_score - $payParam['current_employee_score_deducte'],
                        'last_time'     =>  time()
                    ]);
                }
            }else{
                throw new \think\Exception('员工卡不存在！');
            }
           
        }


        //TODO  商家联盟会员卡判断/如果使用了平台E卡,但是我看代码好像废弃了

        //使用商家优惠
        if ($shopOrder['card_id']) {
            $useResult = (new MerchantCouponService())->useCoupon($shopOrder['card_id'], $shopOrder['order_id'], 'shop', $shopOrder['mer_id'], $shopOrder['uid']);
            if (!$useResult) {
                throw new Exception(L_("商家优惠券使用失败"), 1003);
            }
        }

        //使用平台优惠券
        if ($shopOrder['coupon_id']) {
            $useResult = (new SystemCouponService())->useCoupon($shopOrder['coupon_id'], $shopOrder['order_id'], 'shop', $shopOrder['mer_id'], $shopOrder['uid']);
            if (!$useResult) {
                throw new Exception(L_("平台优惠券使用失败"), 1003);
            }
        }

        $paidTime = isset($payParam['pay_time']) ? $payParam['pay_time'] : '';
        $paidMoney = isset($payParam['paid_money']) ? $payParam['paid_money'] : '';
        $paidType = isset($payParam['paid_type']) ? $payParam['paid_type'] : '';
        $paidOrderid = isset($payParam['paid_orderid']) ? $payParam['paid_orderid'] : '';
        $currentScoreUse = isset($payParam['current_score_use']) ? $payParam['current_score_use'] : '';
        $currentScoreDeducte = isset($payParam['current_score_deducte']) ? $payParam['current_score_deducte'] : '';
        $currentSystemBalance = isset($payParam['current_system_balance']) ? $payParam['current_system_balance'] : '';
        $currentMerchantBalance = isset($payParam['current_merchant_balance']) ? $payParam['current_merchant_balance'] : '';
        $currentMerchantGiveBalance = isset($payParam['current_merchant_give_balance']) ? $payParam['current_merchant_give_balance'] : '';
        $currentQiyeBalance = isset($payParam['current_qiye_balance']) ? $payParam['current_qiye_balance'] : '';
        $isOwn = isset($payParam['is_own']) ? $payParam['is_own'] : '0';

        $dataShopOrder = array();
        $dataShopOrder['pay_time'] = $paidTime;
        $dataShopOrder['payment_money'] = $paidMoney;
        $dataShopOrder['pay_type'] = $paidType;
        $dataShopOrder['third_id'] = $paidOrderid;
        if (($shopOrder['order_from'] == 2 || $shopOrder['order_from'] == 3) && empty($dataShopOrder['pay_type'])) {
            $dataShopOrder['is_mobile_pay'] = 2;
        } else {
            $dataShopOrder['is_mobile_pay'] = 3;
        }
        $dataShopOrder['is_own'] = $isOwn;
        $dataShopOrder['paid'] = 1;
        $dataShopOrder['price'] = $shopOrder['price'];
        if ($shopOrder['card_discount']) {
            $dataShopOrder['price'] = sprintf("%.2f", ($shopOrder['price'] - $shopOrder['freight_charge']) * $shopOrder['card_discount'] / 10) + $shopOrder['freight_charge'];
        }
        //期望送达时间的修改
        $dataShopOrder['expect_use_time'] = $shopOrder['expect_use_time'] + strtotime(date("Y-m-d H:i", $dataShopOrder['pay_time'] - $shopOrder['create_time']));

        //期望送达时间自动更改
        if (cfg('expect_time_auto') == 1) {
            $store = (new \app\shop\model\service\store\MerchantStoreShopService())->getStoreShopDetailByStoreId($shopOrder['store_id']);

            if ($shopOrder['is_pick_in_store'] == 0) {
                $send_time = $store['s_send_time'] * 60;

                //如果设置了配送时速，则重新计算
                $deliverReturn = (new DeliverSetService())->getDeliverInfo($store);
                $order_distance_km = $shopOrder['distance'] / 1000;
                if (getFormatNumber($deliverReturn['deliver_speed_hour']) && isset($order_distance_km)) {    //按时速计算
                    $send_time = $order_distance_km / $deliverReturn['deliver_speed_hour'] * 3600 + ($deliverReturn['deliver_delay_time'] * 60);
                }
                $dataShopOrder['expect_pick_time'] = $shopOrder['expect_use_time'] - $send_time;

            } elseif ($shopOrder['is_pick_in_store'] == 1) {
                $send_time = $store['send_time'] * 60;
            } elseif ($shopOrder['is_pick_in_store'] == 2) {
                $dataShopOrder['expect_pick_time'] = $shopOrder['expect_use_time'];
            }

            $diffTime = 60;
            if ($store['send_time_type'] == 1) {
                $diffTime = 3600;
            } elseif ($store['send_time_type'] == 2) {
                $diffTime = 86400;
            } elseif ($store['send_time_type'] == 3) {
                $diffTime = 86400 * 7;
            } elseif ($store['send_time_type'] == 4) {
                $diffTime = 86400 * 30;
            }
            $work_time = $store['work_time'] * $diffTime;
            if (($dataShopOrder['expect_use_time'] - time()) < ($send_time + $work_time)) {
                $dataShopOrder['expect_use_time'] = $dataShopOrder['pay_time'] + $send_time + $work_time;
                if ($shopOrder['is_pick_in_store'] == 0) {
                    $dataShopOrder['expect_pick_time'] = $dataShopOrder['expect_use_time'] - $send_time;
                } else if ($shopOrder['is_pick_in_store'] == 2) {
                    $dataShopOrder['expect_pick_time'] = $dataShopOrder['expect_use_time'];
                }
            }

            if (cfg('shop_order_booking_time') && $dataShopOrder['expect_pick_time'] && $dataShopOrder['expect_pick_time'] > time() + $work_time + 3600 * cfg('shop_order_booking_time')) {
                $dataShopOrder['is_booking'] = '1';
            }
        }

        //保存订单
        $saveRs = $this->shopOrderMod->updateOrder(['order_id' => $orderId], $dataShopOrder);
        if ($saveRs) {
            //订单支付成功，清空购物车
            \think\facade\Db::name('Cart')->where(['store_id' => $shopOrder['store_id'], 'uid' => $shopOrder['uid'], 'is_choose' => 1])->delete();

            //更新店铺订单销量
            $shopMod = new MerchantStoreShop();
            $shopMod->where('store_id', $shopOrder['store_id'])->inc('sale_count', 1);

            //更新平台订单信息
            invoke_cms_model('System_order/save_order_info', ['shop', $orderId, $dataShopOrder]);

            //增加取单号
            $orderFetchNumber = $this->shopOrderMod->where(['order_id' => $orderId])->field('fetch_number')->find();//已经有单号就不再生成了
            if ($shopOrder['order_from'] != 6 && $shopOrder['is_pick_in_store'] <= 2 && intval($orderFetchNumber['fetch_number']) <= 0) {
                $nowDay = date('Ymd');
                $fetchNumber = 0;
                if ($shop) {
                    if ($shop['fetch_day'] == $nowDay) {
                        $fetchNumber = $shop['fetch_number'] + 1;
                        $shopMod->where('store_id', $shopOrder['store_id'])->inc('fetch_number', 1);
                    } else {
                        $fetchNumber = 1;
                        $shopMod->where('store_id', $shopOrder['store_id'])->update(['fetch_number' => 1, 'fetch_day' => $nowDay]);
                    }
                }
                $this->shopOrderMod->updateOrder(['order_id' => $orderId], ['fetch_number' => $fetchNumber]);
            }

            //自动接单
            if ($shopOrder['is_pick_in_store'] != 3 && $shopOrder['order_from'] <> 1) {
                if ($shop && $shop['is_auto_order'] && $shopOrder['group_id'] == 0 && $shopOrder['is_weight'] == 0) {
                    $result = invoke_cms_model('Deliver_supply/saveOrder', [$orderId, $store]);
                    if (isset($result['retval']['error_code']) && $result['retval']['error_code']) {
                        throw new Exception($result['retval']['msg'], 1003);
                    }
                    return array('error' => 1, 'msg' => $result['msg']);
                }
                $this->shopOrderMod->updateOrder(['order_id' => $orderId], ['status' => 1, 'order_status' => 1, 'last_time' => time()]);

                (new SystemOrderService())->sendOrder('shop', $orderId);

                $log = [
                    'order_id' => $orderId,
                    'status' => 2,
                    'name' => L_('自动接单'),
                    'phone' => '',
                    'note' => L_('店铺设置下单后自动接单')
                ];
                invoke_cms_model('Shop_order_log/add_log', [$log]);
            }
        }

        //添加到滚动
        invoke_cms_model('Scroll_msg/add_msg', ['shop', $shopOrder['uid'], L_('用户x1于x2购买x3成功', array('x1' => str_replace_name($buyer['nickname']), 'x2' => date('Y-m-d H:i', $_SERVER['REQUEST_TIME']), 'x3' => $aliasName))]);

        //推广分佣
        if (cfg('open_score_pay_back') && cfg('system_take_spread_percent') > 0) {
            $spreadTotalMoney = ($dataShopOrder['balance_pay'] + $dataShopOrder['payment_money'] + $dataShopOrder['score_deducte'] + $dataShopOrder['coupon_price'] + $dataShopOrder['balance_reduce'] - $shopOrder['no_bill_money'] + $dataShopOrder['merchant_balance']);
            if ($shopOrder['order_from'] == 1) {
                $percentType = 'mall';
            } else {
                $percentType = 'shop';
            }
            $percent = (new PercentRateService())->getPercentRate($shopOrder['mer_id'], $percentType, $spreadTotalMoney, '', $shopOrder['store_id'], true);
            $spreadTotalMoney = $spreadTotalMoney * $percent / 100;
        } else {
            $spreadTotalMoney = $balancePay + $dataShopOrder['payment_money'] - $shopOrder['freight_charge'];
        }
        (new UserService())->handleSpreadAfterPay($shopOrder, $buyer, 'shop', $spreadTotalMoney);
        return true;
    }
}