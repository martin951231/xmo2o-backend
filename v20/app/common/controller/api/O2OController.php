<?php

namespace app\common\controller\api;

use app\common\model\db\MiaomaiOrderRefundSync;
use app\common\model\db\MiaomaiOrderSync;
use app\common\model\db\OrderPayInfo;
use app\common\model\db\OrderPayRefundInfo;
use app\common\model\db\SystemOrder;
use app\common\model\service\CardNewCouponService;
use app\common\model\service\MerchantStoreService;
use app\common\model\service\SystemCouponHadpullService;
use app\common\model\service\UserMoneyListService;
use app\common\model\service\UserService;
use app\internal_card\model\service\InternalCardService;
use app\mall\model\service\MallOrderService;
use app\merchant\model\service\card\CardNewService;
use app\merchant\model\service\card\CardUserlistService;
use app\merchant\model\service\storestaff\MerchantStoreStaffService;
use app\pay\model\db\PayOrderInfo;
use app\pay\model\service\PayService;
use app\shop\model\service\goods\ShopGoodsService;
use app\shop\model\service\order\ShopOrderService;
use Exception;
use miaomai\Miaomaiyun;
use think\facade\Db;
use think\response\Json;

class O2OController
{
    public $merchantStore;
    public $user;
    public $miaomaiStaffId;
    public $storeStaff;
    private $secret = 'aB3kD7h89ER0mN16eS3tU4vW5xY6Q2zd';

    public function __construct()
    {
        fdump_api(['request_url' => request()->url(), 'request_data' => input('post.')], 'miaomai_api/request', 1);
        $noCheckAction = ['orderSync', 'refundOrderSync', 'goodsInventorySync', 'miaomaiOrderIndex'];
        if (!in_array(request()->action(), $noCheckAction)) {
            empty(input('store_id')) && throw_exception('store_id is required');//淼迈店铺id
            empty(input('op_staff_id')) && throw_exception('op_staff_id is required');
            empty(input('erp_appid')) && throw_exception('erp_appid is required');
            checkSign($this->secret, input('post.'));
            if(!in_array(request()->action(), ['changeOrderStatus'])){
                empty(input('phone')) && throw_exception('phone is required');
                $this->user = (new UserService)->getUserInfoByPhone(input('phone'));//获取用户信息
            }
            
            $this->merchantStore = (new MerchantStoreService)->checkMiaomaiStore(input('store_id'), input('erp_appid'));//获取店铺信息
            $this->storeStaff = MerchantStoreStaffService::getStaff(input('op_staff_id'), $this->merchantStore->store_id, $this->merchantStore->mer_id);
            fdump_api(['签名验证通过'], 'miaomai_api/request', 1);
        }
    }

    /*
     * 用户信息
     */
    public function userInfo(): Json
    {
        $phone = input('phone');
        $money = input('money', 0);//支付金额

        $storeId = $this->merchantStore['store_id'];//店铺id
        //用户信息
        $user = $this->user;
        //平台优惠券
        $user['system_coupon'] = (new SystemCouponHadpullService)->canUseSystemCoupon($user['uid'], $money);
        //商家优惠券
        $user['merchant_coupon'] = (new CardNewCouponService())->canUseMerchantCoupon($user['uid'], $money);
        //商家会员卡
        $user['merchant_card'] = (new CardNewService)->getUserCardList($user['uid'], $this->merchantStore['mer_id']);
        //商家一卡通
        try {
            $user['internal_card'] = (new InternalCardService())->internalCard($storeId, $phone, '', ['no_check_password' => true]);
        } catch (Exception $e) {
            $user['internal_card'] = [
                'ecard_total_money' => 0,
                'internal_card_ids' => []
            ];
        }
        fdump_api([
            'request_url' => request()->url(),
            'request_data' => input('post.'),
            'result' => $user
        ], 'miaomai/userInfo', 1);
        
        return api_output(0, $user);
    }

    /*
     * 使用优惠券
     */
    public function useCoupon(): Json
    {
        $couponReceiveId = input('coupon_receive_id');
        $couponType = input('coupon_type');
        $orderNo = input('order_no');
        if (empty($couponReceiveId) || empty($couponType) || empty($orderNo)) {
            return api_output_error(1001, '缺少参数');
        }
        $uid = $this->user['uid'];
        $money = input('money', 0);//支付金额
        empty($money) && throw_exception('支付金额不能为空');
        $order = [
            'order_no' => $orderNo,
            'money' => $money,
        ];
        try {
            if ($couponType == 1) {
                //平台优惠券
                $data = (new SystemCouponHadpullService)->useCoupon($couponReceiveId, $order, $this->merchantStore['mer_id'], $uid);
                $payInfo = [
                    'pay_type' => 'system_coupon_money',
                    'system_coupon_receive_id' => $couponReceiveId,
                ];
            } else {
                //商家优惠券
                $data = (new CardNewCouponService)->useCoupon($couponReceiveId, $order, $this->merchantStore['mer_id'], $uid);
                $payInfo = [
                    'pay_type' => 'merchant_coupon_money',
                    'mer_coupon_receive_id' => $couponReceiveId,
                ];
            }
            if ($data['error_code']) {
                return api_output_error(400, $data['msg']);
            }
            OrderPayInfo::saveInfo(array_merge([//记录订单支付信息
                'uid' => $uid,
                'order_no' => $orderNo,
                'order_type' => 'miaomai',
                'pay_money' => $data['discount_money'],
                'mer_id' => $this->merchantStore['mer_id'],
                'store_id' => $this->merchantStore['store_id'],
                'extra_info' => $payInfo,
            ], $payInfo));
        }catch (\Exception $e){
            fdump_api(['error_msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 'miaomai_error/useCoupon', 1);
            return api_output_error(400, $e->getMessage());
        }
        fdump_api([
            'request_url' => request()->url(), 
            'request_data' => input('post.'),
            'result' => $data
        ], 'miaomai/useCoupon', 1);
        
        return api_output(0, $data, '核销成功');
    }

    /*
     * 退款优惠券
     */
    public function refundUseCoupon(): Json
    {
        $couponType = input('coupon_type');
        $orderNo = input('order_no');
        try{
            if ($couponType == 1) {
                //平台优惠券
                $data = (new SystemCouponHadpullService)->refundUseCoupon($orderNo);
            } else {
                //商家优惠券
                $data = (new CardNewCouponService)->refundUseCoupon($orderNo);
            }
        }catch (\Exception $e){
            fdump_api(['error_msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 'miaomai_error/refundUseCoupon', 1);
            return api_output_error(400, $e->getMessage());
        }
        fdump_api([
            'request_url' => request()->url(),
            'request_data' => input('post.'),
            'result' => $data
        ], 'miaomai/refundUseCoupon', 1);
        return api_output(0, $data, '退券成功');
    }
    /*
     * 变动用户平台余额
     */
    public function changeUserPlatformMoney(): Json
    {
        $uid = $this->user['uid'];
        $platMoney = (float) $this->user['now_money'];
        $money = (float) input('money', 0);
        $type = input('type'); //1 充值  2 消费
        $orderNo = input('order_no');
        if (empty($money) || empty($orderNo) || empty($type)) {
            return api_output_error(1001, '缺少参数');
        }
        if (!in_array($type, [1, 2])) {//1 充值  2 消费
            return api_output_error(1001, 'type参数不正确');
        }
        if($type == 2) {
            if($platMoney <= 0) {
                return api_output_error(1001, '用户平台余额不足');
            }
            if($platMoney < $money){//余额不足，有多少扣多少
                $money = $platMoney;
            }
        }
        
        $params = [
            'uid' => $uid,
            'money' => $money,
            'order_no' => $orderNo,
            'type' => $type,
            'mer_store' => $this->merchantStore,
            'staff' => $this->storeStaff,
        ];
        try {
            $data = (new UserService())->miaomaiChangeMoney($params);
            fdump_api([
                'request_url' => request()->url(),
                'request_data' => input('post.'),
                'result' => $data
            ], 'miaomai/changeUserPlatformMoney', 1);
            return api_output(0, $data, '请求成功');
        } catch (\Exception $e) {
            fdump_api(['error_msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 'miaomai_error/changeUserPlatformMoney', 1);
            return api_output_error(400, $e->getMessage());
        }
    }
    /*
     * 变动商家会员卡余额
     */
    public function changeCardMoney(): Json
    {
        $uid = $this->user['uid'];
        $money = input('money', 0, 'floatval');
        $type = input('type');
        $orderNo = input('order_no');
        if (empty($money) || empty($orderNo) || empty($type)) {
            return api_output_error(1001, '缺少参数');
        }
        try {
            // 用户会员卡信息
            $cardUser = (new CardUserlistService())->getCardByUidAndMerId($this->merchantStore['mer_id'], $uid);
            if (empty($cardUser)) {
                return api_output_error(1001, '用户会员卡信息不存在');
            }
            if ($type == 1) {//减少
                $discountMoney = (new CardNewService)->miaomaiUseCardMoney($uid, $money, $type, $orderNo, $this->merchantStore);
                $data = ['discount_money' => $discountMoney];
                fdump_api([
                    'request_url' => request()->url(),
                    'request_data' => input('post.'),
                    'result' => $data
                ], 'miaomai/changeCardMoney', 1);
                
                return api_output(0, $data, $discountMoney > 0 ? '请求成功' : '请求失败');
            } else {//增加
                $data = (new CardNewService)->miaomaiAddCardMoney($uid, $money, $type, $orderNo, $this->merchantStore);
                fdump_api([
                    'request_url' => request()->url(),
                    'request_data' => input('post.'),
                    'result' => $data
                ], 'miaomai/changeCardMoney', 1);
                return api_output(0, $data, $data ? '请求成功' : '请求失败');
            }
        } catch (\Exception $e) {
            fdump_api(['error_msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 'miaomai_error/changeCardMoney', 1);
            return api_output_error(400, $e->getMessage());
        }
    }

    /*
     * 变动用户积分
     */
    public function changeUserScore(): Json
    {
        $uid = $this->user['uid'];
        $money = input('money');
        $orderNo = input('order_no');
        $type = input('type');
        if (empty($money) || empty($orderNo) || empty($type)) {
            return api_output_error(1001, '缺少参数');
        }
        if (!$this->user['user_score_discount_money']) {
            throw_exception('积分不可抵扣金额');
        }
        try{
            $score = $money * cfg('user_score_use_percent');
            if ($type == 1) {//减少
                if ($this->user['score_count'] < $score) {
                    $score = $this->user['score_count'];
                }
                if(cfg('user_score_max_use') && cfg('user_score_max_use') < $score){//平台每单最大使用积分
                    $score = cfg('user_score_max_use');
                }
                (new UserService())->userScore($uid, $score, L_("淼迈操作（{ $this->miaomaiStaffId }）减少积分，订单号（{$orderNo}）"));
                OrderPayInfo::saveInfo([//记录订单支付信息
                    'uid' => $uid,
                    'order_no' => $orderNo,
                    'order_type' => 'miaomai',
                    'pay_type' => 'score_money',
                    'mer_id' => $this->merchantStore['mer_id'],
                    'store_id' => $this->merchantStore['store_id'],
                    'pay_money' => $score,
                ]);
                $scoreMoney = $score / cfg('user_score_use_percent');
                $data = ['discount_money' => $scoreMoney];
                fdump_api([
                    'request_url' => request()->url(),
                    'request_data' => input('post.'),
                    'result' => $data
                ], 'miaomai/changeUserScore', 1);
                return api_output(0, $data, '积分已抵扣金额');
            } else {//增加
                $data = (new UserService)->addScore($uid, $score, L_("淼迈操作（{ $this->miaomaiStaffId }）增加积分，订单号（{$orderNo}）"));
                OrderPayRefundInfo::saveInfo([//记录订单退款信息
                    'order_no' => $orderNo,
                    'order_type' => 'miaomai',
                    'refund_type' => 'score_money',
                    'refund_money' => $score,
                ]);
                fdump_api([
                    'request_url' => request()->url(),
                    'request_data' => input('post.'),
                    'result' => $data
                ], 'miaomai/changeUserScore', 1);
                if ($data['error_code']) {
                    return api_output_error(400, $data['msg']);
                }
                return api_output(0, true, $data['msg']);
            }
        }catch (\Exception $e){
            fdump_api(['error_msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 'miaomai_error/changeUserScore', 1);
            return api_output_error(400, $e->getMessage());
        }
    }

    /*
     * 变动用户一卡通余额
     */
    public function changeUserEcardMoney(): Json
    {
        $uid = $this->user['uid'];
        $money = input('money');
        $orderNo = input('order_no');
        $type = input('type');
        if (empty($money) || empty($orderNo) || empty($type)) {
            return api_output_error(1001, '缺少参数');
        }
        try{
            if ($type == 1) {//减少
                $password = input('password');
                if(empty($password)){
                    return api_output_error(1001, '一卡通密码不能为空');
                }
                (new InternalCardService())->internalCard($this->merchantStore['store_id'], '', $password, [
                    'where' => [
                        ['uid', '=', $uid],
                        ['balance_money', '>', 0]
                    ]
                ]);
                $ecardPayInfo = (new InternalCardService)->beforePay($this->merchantStore['store_id'], $this->user['phone'], $money);
                $order = [
                    'internal_card_pay' => $ecardPayInfo['internal_card_pay'],
                    'order_no' => $orderNo,
                    'business_type' => 'miaomai',
                    'mer_id' => $this->merchantStore['mer_id'],
                    'store_id' => $this->merchantStore['store_id'],
                ];
                (new InternalCardService())->cardPay($order);
                OrderPayInfo::saveInfo([//记录订单支付信息
                    'uid' => $uid,
                    'order_no' => $orderNo,
                    'order_type' => 'miaomai',
                    'pay_type' => 'ecard_pay_money',
                    'mer_id' => $this->merchantStore['mer_id'],
                    'store_id' => $this->merchantStore['store_id'],
                    'pay_money' => $money,
                    'extra_info' => $ecardPayInfo['internal_card_pay']
                ]);
                $cardPayInfo = json_decode($order['internal_card_pay'], true);
                $payMoney = bcmul(array_sum($cardPayInfo), 1, 2);
                $data = ['discount_money' => $payMoney];
                fdump_api([
                    'request_url' => request()->url(),
                    'request_data' => input('post.'),
                    'result' => $data
                ], 'miaomai/changeUserEcardMoney', 1);
                return api_output(0, $data, '请求成功');
            } else {//增加
                $payInfo = OrderPayInfo::where(['order_no' => $orderNo, 'order_type' => 'miaomai'])->find();
                $order = [
                    'order_no' => $orderNo,
                    'business_type' => 'miaomai',
                    'business_type_text' => '淼迈订单',
                    'uid' => $this->user['uid'],
                    'mer_id' => $this->merchantStore['mer_id'],
                    'store_id' => $this->merchantStore['store_id'],
                    'internal_card_pay' => json_encode($payInfo['extra_info'],JSON_UNESCAPED_UNICODE),
                    'pay_time' => $payInfo['create_time'],
                ];
                (new InternalCardService)->refund($order, $money);
                OrderPayRefundInfo::saveInfo([//记录订单退款信息
                    'order_no' => $orderNo,
                    'order_type' => 'miaomai',
                    'refund_type' => 'ecard_pay_money',
                    'refund_money' => $money,
                ]);
                fdump_api([
                    'request_url' => request()->url(),
                    'request_data' => input('post.'),
                    'result' => true
                ], 'miaomai/changeUserEcardMoney', 1);
                return api_output(0, true, '请求成功');
            }
        }catch (\Exception $e){
            fdump_api(['error_msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 'miaomai_error/changeUserEcardMoney', 1);
            return api_output_error(400, $e->getMessage());
        } 
    }

    /*
     * 扫码支付
     */
    public function scanPay(): Json
    {
        $uid = $this->user['uid'];
        $authCode = input('auth_code');
        $orderNo = input('order_no');
        $payMoney = input('pay_money');
        if (empty($authCode) || empty($orderNo) || empty($payMoney)) {
            return api_output_error(1001, '缺少参数');
        }
        // 调用在线支付
        $payService = new PayService($this->merchantStore['city_id'], $this->merchantStore['mer_id'], $this->merchantStore['store_id']);
        try {
            $payResult = $payService->scanPay($authCode, $payMoney);
            fdump_api(['request' => [$authCode, $payMoney], 'result' => $payResult], 'pay/scanPay', 1);
            if ($payResult['status'] == 1) {
                OrderPayInfo::saveInfo([//记录订单支付信息
                    'uid' => $uid,
                    'order_no' => $orderNo,
                    'order_type' => 'miaomai',
                    'pay_type' => $payResult['pay_type'],
                    'mer_id' => $this->merchantStore['mer_id'],
                    'store_id' => $this->merchantStore['store_id'],
                    'pay_money' => $payMoney,
                    'extra_info' => [
                        'pay_order_no' => $payResult['order_no'],
                        'pay_type' => $payResult['pay_type'],
                    ]
                ]);
            }
        } catch (Exception $e) {
            fdump_api(['request' => [$authCode, $payMoney], 'scanPay' => $e->getMessage()], 'miaomai_error/scanPay', 1);
            return api_output_error(1001, $e->getMessage());
        }
        fdump_api([
            'request_url' => request()->url(),
            'request_data' => input('post.'),
            'result' => $payResult
        ], 'miaomai/scanPay', 1);
        return api_output(0, $payResult, '请求成功');
    }

    /**
     * 扫码支付结果查询
     * @throws Exception
     */
    public function checkScanPay(): Json
    {
        $uid = $this->user['uid'];
        $payOrderNo = input('pay_order_no');
        $orderNo = input('order_no');
        $payType = input('pay_type');

        if (empty($orderNo) || empty($payType) || empty($payOrderNo)) {
            return api_output_error(1001, '缺少参数');
        }
        try {
            $res = (new PayService())->queryScanPay($orderNo, $payType);
            fdump_api(['order_no' => $orderNo, 'pay_type' => $payType, 'res' => $res], 'miaomai/checkScanPay', 1);
            if ($res['status'] == 1) {
                $payOrderInfo = (new PayOrderInfo)->where(['business' => 'scanpay', 'orderid' => $payOrderNo])->find();
                if (empty($payOrderInfo)) {
                    throw_exception('支付信息不存在');
                }
                $orderPay = OrderPayInfo::where([
                    'uid' => $uid,
                    'order_no' => $orderNo,
                    'order_type' => 'miaomai',
                    'pay_type' => $payType,
                ])->find();
                if (empty($orderPay)) {
                    OrderPayInfo::saveInfo([//记录订单支付信息
                        'uid' => $uid,
                        'order_no' => $orderNo,
                        'order_type' => 'miaomai',
                        'pay_type' => $payType,
                        'mer_id' => $this->merchantStore['mer_id'],
                        'store_id' => $this->merchantStore['store_id'],
                        'pay_money' => $payOrderInfo['money'] / 100,
                        'extra_info' => [
                            'pay_order_no' => $payOrderNo,
                            'pay_type' => $payType,
                        ]
                    ]);
                }
                $msg = '支付成功';
                $data = ['status' => 1];
            } elseif ($res['status'] == 2) {
                $msg = '正在支付';
                $data = ['status' => 2];
            } else {
                $msg = '支付失败';
                $data = ['status' => 0];
            }
            $data['pay_type'] = $payType;
            $data['order_no'] = $payOrderNo;
        } catch (Exception $e) {
            fdump_api(['checkScanPay' => $e->getMessage()], 'miaomai_error/checkScanPay', 1);
            return api_output_error(1001, '支付失败');
        }
        fdump_api([
            'request_url' => request()->url(),
            'request_data' => input('post.'),
            'result' => $data
        ], 'miaomai/checkScanPay', 1);
        return api_output(0, $data, $msg);
    }

    public function refundScanPay(): Json
    {
        $uid = $this->user['uid'];
        $orderNo = input('order_no');
        $refundMoney = input('refund_money');
        $payType = input('pay_type');
        if (empty($orderNo) || empty($refundMoney) || empty($payType)) {
            return api_output_error(1001, '缺少参数');
        }
        $payInfo = OrderPayInfo::where([
            'uid' => $uid,
            'order_no' => $orderNo,
            'pay_type' => $payType,
        ])->value('extra_info');
        if (empty($payInfo)) {
            return api_output_error(1001, '支付信息不存在');
        }
        try {
            $data = (new PayService())->refund($payInfo['pay_order_no'], $refundMoney);
            OrderPayRefundInfo::saveInfo([//记录订单退款信息
                'order_no' => $orderNo,
                'order_type' => 'miaomai',
                'refund_type' => $payType,
                'refund_money' => $refundMoney,
                'extra_info' => [
                    'pay_order_no' => $payInfo['pay_order_no'],
                    'refund_order_no' => $data['refund_order_no'],
                ]
            ]);
        } catch (Exception $e) {
            fdump_api(['request' => [$payInfo['pay_order_no'], $refundMoney], 'refundScanPay' => $e->getMessage()], 'miaomai/refundScanPay', 1);
            return api_output_error(1001, $e->getMessage());
        }
        fdump_api([
            'request_url' => request()->url(),
            'request_data' => input('post.'),
            'result' => $data
        ], 'miaomai/refundScanPay', 1);
        return api_output(0, $data, '退款成功');
    }
    public function changeOrderStatus(): Json
    {
        fdump_api(['request' => input('')], 'miaomai_api/changeOrderStatus', 1);
        $orderNo = input('order_no');
        $status = input('status');
        try {
            $order = SystemOrder::where(['real_orderid' => $orderNo])->find();
            if (empty($order)) {
                throw_exception('订单不存在');
            }
            $order['refund_code'] = input('refund_code');
            $order['refund_status'] = input('refund_status');
            $data = Db::transaction(function ()use($order, $status){
                switch ($order['type']){
                    case 'mall':
                    case 'mall3':
                        $data = (new MallOrderService)->miaomaiChangeOrderStatus($order, $status, $this->storeStaff);
                        break;
                    case 'shop':
                        $data = (new ShopOrderService)->miaomaiChangeOrderStatus($order, $status, $this->storeStaff);
                        break;
                    default:
                        throw_exception('订单类型错误');
                }
                return $data;
            });
        } catch (Exception $e) {
            fdump_api(['request' => $orderNo, 'changeOrderStatus' => $e->getMessage()], 'miaomai_error/changeOrderStatus', 1);
            return api_output_error(1001, $e->getMessage());
        }
        fdump_api([
            'request_url' => request()->url(),
            'request_data' => input('post.'),
            'result' => $data
        ], 'miaomai/changeOrderStatus', 1);
        return api_output(0, $data, '修改成功');
    }

    /**
     * 零售商品库存同步
     * @return Json
     */
    public function goodsInventorySync(): Json
    {
        $params = input('post.');
        fdump_api($params, 'miaomai/goodsSync', 1);
        $goods = ShopGoodsService::miaomaiGoodsSync($params);
        $result = $goods ? 'success' : 'fail';
        echo $result;
        die();
    }
    
    /**
     * 零售订单同步
     * @return Json
     */
    public function orderSync(): Json
    {
        $params = input('post.');
        fdump_api($params, 'miaomai/orderSync', 1);
        $order = MiaomaiOrderSync::saveInfo($params);
        $result = $order ? 'success' : 'fail';
        echo $result;
        die();
    }

    /**
     * 零售退款订单同步
     * @return Json
     */
    public function refundOrderSync(): Json
    {
        $params = input('post.');
        fdump_api($params, 'miaomai/refundOrderSync', 1);
        $order = MiaomaiOrderRefundSync::saveInfo($params);
        $result = $order ? 'success' : 'fail';
        echo $result;
        die();
    }

    public function miaomaiOrderIndex()
    {
        //dd((new ShopOrderService())->uploadMiaomaiOrder(45));
        //dd((new MallOrderService())->uploadMiaomaiOrder(23));
        //dd((new MallOrderRefundService())->miaomaiOrderRefund(723));
        
        $data = (new Miaomaiyun)
            ->setConfig(cfg('miaomaiyun_erp_appid'), cfg('miaomaiyun_erp_appsecret'))
            ->run(Miaomaiyun::ORDER_UPD_INDEX);
        return api_output(0, $data);
    }

}