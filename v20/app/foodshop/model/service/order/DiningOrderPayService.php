<?php
/**
 * 餐饮支付订单service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/08 18:09
 */

namespace app\foodshop\model\service\order;
use app\foodshop\model\db\DiningOrderPay as DiningOrderPayModel;
use app\common\model\service\coupon\MerchantCouponService;
use app\common\model\service\coupon\SystemCouponService;
use app\common\model\service\UserService;
use app\internal_card\model\service\InternalCardService;
use app\merchant\model\service\card\CardNewService as CardNewService;
use app\merchant\model\service\ShopDiscountService as ShopDiscountService;
use app\pay\model\service\PayService;
use app\foodshop\model\service\order_print\PrintHaddleService;

class DiningOrderPayService {
    public $diningOrderPayModel = null;
    public function __construct()
    {
        $this->diningOrderPayModel = new DiningOrderPayModel();
       
    }

    /**
     * 获得支付订单信息（供支付调用）
     * @param $param 
     * @param $user array 用户
     * @return array 
     */
    public function getOrderPayInfo($orderId)
    {
        if(!$orderId) {
            throw new \think\Exception(L_("参数错误"), 1001);
        }

        $order = $this->getOrderByOrderId($orderId);
        if(!$order) {
            throw new \think\Exception(L_("订单不存在"), 1003);
        }
        
        // if($order['paid']){
        //     throw new \think\Exception(L_("订单已支付"), 1003);
        // }
        
        // 店铺信息
        $store = (new \app\merchant\model\service\MerchantStoreService())->getStoreByStoreId($order['store_id']);

        // 商品信息
        $goodsDetail = [];
//        if($order['status'] == 3){
//            try {
//                $goodsDetail = (new DiningOrderDetailService())->getGoodsByPayOrderId($orderId);
//            } catch (\Exception $e) {
//                throw new \think\Exception($e->getMessage());
//            }
//        }

        $param['goodsDetail'] = $goodsDetail;

        $returnArr['order_money'] = $order['price'];
        $returnArr['paid'] = $order['paid'];
        $returnArr['order_no'] = $order['orderid'];
        $returnArr['store_id'] = $order['store_id'];
        $returnArr['city_id'] = $store['city_id'];
        $returnArr['mer_id'] = $store['mer_id'];
        $returnArr['is_cancel'] = $order['is_cancel'];
        $returnArr['time_remaining'] = 900 - (time() - $order['add_time']);//秒
        $returnArr['business_order_sn'] = $order['orderid'];

        $isCancel = 0;
        if($returnArr['time_remaining']<=0){
            //取消订单
            $nowOrder = (new DiningOrderService())->getOrderByOrderId($order['order_id']);
            $isCancel = (new DiningOrderService())->overTimeCancelOrder($nowOrder);
        }

        if($order['user_type'] == 'uid'){
            $returnArr['uid'] = $order['user_id'];
        }
        
        $returnArr['title'] = $store['name'];

        if($returnArr['time_remaining']<=0 && $isCancel){
            //取消订单
            $data['is_cancel'] = 1;
            $where = [
                'pay_order_id' => $orderId
            ];
            $this->updateByCondition($where, $data);
        }
        return $returnArr;
        
    }
    
    /**
     * 支付成功后调用（供支付调用）
     * @param $param 
     * @param $payParam array 支付后的支付数据
     * @return bool
     */
    public function afterPay($orderId, $payParam)
    {
        fdump_sql([$orderId,$payParam],'payOrder',1);
        if(!$orderId) {
            throw new \think\Exception(L_("参数错误"), 1001);
        }
        
        $payOrder = $this->getOrderByOrderId($orderId);
        if(!$payOrder) {
            throw new \think\Exception(L_("当前订单不存在！"), 1003);
        }
        // 验证餐饮订单状态
        $diningOrderService = new DiningOrderService();
        $diningOrder = $diningOrderService->getOrderByOrderId($payOrder['order_id']);
        if ($diningOrder && $diningOrder['status'] >= 40) {
            // 该订单已付款
            return false;
        }

        if(!empty($payParam['current_internal_card_balance']) && !empty($payParam['current_internal_card_ids'])){
            $ecardInfo = (new InternalCardService)->beforePay($diningOrder['store_id'], $payParam['current_internal_card_ids'], $payParam['current_internal_card_balance']);
            $payParam['internal_card_pay'] = $ecardInfo['internal_card_pay'];
        }
        fdump('payOrder2','payOrder',1);
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
        $offlineMoney = isset($payParam['offline_money']) ? $payParam['offline_money'] : '';
        $isOwn = isset($payParam['is_own']) ? $payParam['is_own'] : '0';
        $uid = isset($payParam['uid']) ? $payParam['uid'] : '0';
        //一卡通
        $internalCardPay = (isset($payParam['internal_card_pay']) &&  $payParam['internal_card_pay']) ? json_decode($payParam['internal_card_pay'], true) : [];
        $payOrder['internal_card_pay_money'] = $internalCardPay ? array_sum($internalCardPay) : 0;//一卡通使用金额
        
        fdump($payParam,'payOrder',1);
        //判断会员卡余额
        if($currentMerchantBalance>0){
            if ($payOrder['union_mer_id']) {
                $user_merchant_balance = (new CardNewService())->getCardByUidAndMerId($payOrder['union_mer_id'],$payOrder['uid']);
            }else{
                $user_merchant_balance = (new CardNewService())->getCardByUidAndMerId($payOrder['mer_id'],$payOrder['uid']);
            }

            // 您的会员卡余额不够此次支付
            if($user_merchant_balance['card_money'] < $currentMerchantBalance){
                return false;
            }
        }

        fdump('payOrder4','payOrder',1);
        if($currentMerchantGiveBalance>0){
            if ($payOrder['union_mer_id']) {
                $user_merchant_balance = (new CardNewService())->getCardByUidAndMerId($payOrder['union_mer_id'],$payOrder['uid']);
            }else{
                $user_merchant_balance = (new CardNewService())->getCardByUidAndMerId($payOrder['mer_id'],$payOrder['uid']);
            }
            //您的会员卡余额不够此次支付
            if($user_merchant_balance['card_money_give'] < $currentMerchantGiveBalance){
                return false;
            }
        }

        fdump('payOrder5','payOrder',1);

        $nowUser = [];
        if($uid){
            $nowUser = (new UserService())->getUser($uid);
        }elseif($payOrder['uid']){
            $nowUser = (new UserService())->getUser($payOrder['uid']);
        }

        //判断帐户余额
        if($currentSystemBalance>0){
            // 您的帐户余额不够此次支付
            if($nowUser['now_money'] < $currentSystemBalance){
                return false;
            }
        }

        // 平台积分
        if($currentScoreUse>0) {
            //判断积分数量是否正确
            if ($nowUser['score_count'] < $currentScoreUse) {
                return false;
            }
        }

        if($payOrder['merchant_coupon_id']){
            try {
                $result = (new MerchantCouponService())->useCoupon($payOrder['merchant_coupon_id'],$payOrder['pay_order_id'],'dining',$payOrder['mer_id'],$nowUser['uid']);
            } catch (\Exception $e) {
                return false;
            }
        }

        //如果使用了平台优惠券
        if($payOrder['system_coupon_id']){
            try {
                $result = (new SystemCouponService())->useCoupon($payOrder['system_coupon_id'],$payOrder['pay_order_id'],'dining',$payOrder['mer_id'],$nowUser['uid']);
            } catch (\Exception $e) {
                return false;
            }
        }

        fdump('payOrder6','payOrder',1);
        //如果用户使用了积分抵扣，则扣除相应的积分
        fdump('$currentScoreUse','diningAfterPay',1);
        fdump($currentScoreUse,'diningAfterPay',1);
        if($currentScoreUse>0){
            fdump('$nowUser','diningAfterPay',1);
            fdump($nowUser,'diningAfterPay',1);
            $desc = L_("购买 X1商品 扣除X2",array("X1" => $payOrder['order_name'],"X2" => cfg('score_name')));
            $desc .= L_('，订单编号') . $diningOrder['real_orderid'];
            $use_result = (new UserService())->userScore($nowUser['uid'],$currentScoreUse,$desc);
            if($use_result['error_code']){
                return false;
            }
        }

        //如果使用会员卡余额
        if($currentMerchantBalance>0){
            $desc = L_("购买 X1商品 扣除会员卡余额",array("X1" => $payOrder['order_name']));
            $desc .= L_('，订单编号') . $diningOrder['real_orderid'];
            try {
                if ($payOrder['union_mer_id']) {
                    $use_result = (new CardNewService())->useMoney($payOrder['union_mer_id'],$nowUser['uid'],$currentMerchantBalance,$desc);
                }else{
                    $use_result = (new CardNewService())->useMoney($payOrder['mer_id'],$nowUser['uid'],$currentMerchantBalance,$desc);
                }
            } catch (\Exception $e) {
                return false;
            }
        }

        fdump('payOrder7','payOrder',1);
        if($currentMerchantGiveBalance>0){
            $desc = L_("购买 X1商品 扣除会员卡赠送余额，订单编号X2",array("X1" => $payOrder['order_name'],'X2'=>$diningOrder['real_orderid']));

            if ($payOrder['union_mer_id']) {
                $use_result = (new CardNewService())->useGiveMoney($payOrder['union_mer_id'],$nowUser['uid'],$currentMerchantGiveBalance,$desc);
            }else{
                $use_result = (new CardNewService())->useGiveMoney($payOrder['mer_id'],$nowUser['uid'],$currentMerchantGiveBalance,$desc);
            }
            fdump($use_result,'payOrder',1);
        }

        //如果用户使用了余额支付，则扣除相应的金额。
        if($currentSystemBalance > 0){
            $desc = L_("购买 X1商品 扣除余额，订单编号X2",array("X1" => $payOrder['order_name'],'X2'=>$diningOrder['real_orderid']));

            $use_result = (new UserService())->userMoney($nowUser['uid'],$currentSystemBalance,$desc);
            if($use_result['error_code']){
                return false;
            }
        }
        //一卡通付款
        if($payOrder['internal_card_pay_money'] > 0){
            try{
                (new InternalCardService)->cardPay([
                    'internal_card_pay' => $payParam['internal_card_pay'],
                    'order_no' => $payOrder['orderid'],
                    'business_type' => 'dining',
                    'mer_id' => $payOrder['mer_id'],
                    'store_id' => $payOrder['store_id'],
                ]);
            } catch (\Exception $e) {
                fdump_api([$e->getMessage(),$e->getTraceAsString()], 'pay/internal_card_pay',1);
                return false;
            }
        }
        // 保存支付订单信息
        $saveData = [];
        $saveData['pay_time'] = $paidTime ? $paidTime : time();
        $saveData['pay_money'] = $paidMoney;//在线支付的钱
        $saveData['pay_type'] = $paidType;
        $saveData['third_id'] = $paidOrderid;
        $saveData['paid'] = 1;
        $saveData['system_score'] = $currentScoreUse;//积分使用数量
        $saveData['system_score_money'] = $currentScoreDeducte;//积分抵扣金额
        $saveData['system_balance'] = $currentSystemBalance;//平台余额使用金额
        $saveData['merchant_balance_pay'] = $currentMerchantBalance;//商家会员卡余额使用金额
        $saveData['merchant_balance_give'] = $currentMerchantGiveBalance;//商家会员卡赠送余额使用金额
        $saveData['internal_card_pay'] = $payParam['internal_card_pay']??'';//一卡通支付信息
        $saveData['internal_card_pay_money'] = $payOrder['internal_card_pay_money'];//一卡通支付金额
        $saveData['qiye_pay'] = $currentQiyeBalance;//企业预存款使用金额
        $saveData['offline_money'] = $offlineMoney;//线下支付金额
        $saveData['is_own'] = $isOwn;//是否自有支付
        if($uid){
            $saveData['uid'] = $uid;
        }
        $where = [
            'pay_order_id' => $orderId,
        ];
        if($this->updateByCondition($where ,$saveData)){
            $dining_order = $diningOrderService->getOrderByOrderId($payOrder['order_id']);
            //订单支付完成后打印标签
            (new PrintHaddleService())->printOrderLabel($dining_order);
            $payOrder = array_merge($payOrder, $saveData);
           
            $diningOrderService->afterPay($payOrder['order_id'],$payOrder);
        }else{
            throw new \think\Exception(L_("修改订单状态失败，请联系系统管理员！"), 1003);
        }
        return true;
    }

    /**
     * 获得支付后跳转链接供支付调用）
     * @param $param 
     * @param $payParam array 支付后的支付数据
     * @return array 
    */
    public function getPayResultUrl($orderId,$cancel = 0)
    {
        if(!$orderId) {
            throw new \think\Exception(L_("参数错误"), 1001);
        }
        
        $payOrder = $this->getOrderByOrderId($orderId);
        if(!$payOrder) {
            throw new \think\Exception(L_("当前订单不存在！"), 1003);
        }
        $url = '';
        // 验证餐饮订单状态
		$diningOrderService = new DiningOrderService();
        $diningOrder = $diningOrderService->getOrderByOrderId($payOrder['order_id']);
        if(!$diningOrder) {
            throw new \think\Exception(L_("当前订单不存在！"), 1003);
        }

        if($cancel== 1){
            // 订单详情
            $url = cfg('site_url').'/packapp/plat/pages/foodshop/order/orderDetail?order_id='.$payOrder['order_id'].'&order_from='.$diningOrder['order_from'];
            return $url;
        }else{
            if ($diningOrder['status'] == 1) {//预订单
                // 订单详情
                $url = cfg('site_url').'/packapp/plat/pages/foodshop/order/orderDetail?order_id='.$payOrder['order_id'].'&order_from='.$diningOrder['order_from'];
                return $url;
            }else{
                // 支付成功页
                $url=cfg('site_url').'/packapp/plat/pages/foodshop/order/paySuccess?order_id='.$payOrder['order_id'];
                return ['redirect_url' => $url, 'direct' => 1];
            }
        }
    }

    /**
     * 订单详情
     * @param $orderId int 订单id
     * @param $user int 用户
     * @return array
     */
    public function getOrderDetail($orderId){
        if(empty($orderId)){
            return [];
        }
          
        $order = $this->getOrderByOrderId($orderId);
        if(!$order){
            return [];
        }

        // 优惠信息
        // 优惠总金额金额
        $order['discount_price'] = get_format_number($order['merchant_reduce']+$order['balance_reduce']+$order['merchant_discount_money']+$order['merchant_coupon_price']+$order['system_coupon_price']+$order['system_score_money']);

        // 平台商家优惠
        if (isset($order['discount_detail']) && @unserialize($order['discount_detail'])) {
            $order['discount_detail'] =  (new ShopDiscountService())->discountFormart(unserialize($order['discount_detail'])) ;
        } else {
            $order['discount_detail'] = '';
        }


        // 优惠券信息
        // "value": "店铺优惠满$20减$10",//显示内容
        // "minus": "10",满减金额
        $order['coupon_list'] = [];

        if($order['merchant_coupon_id']){
            $coupon = (new MerchantCouponService())->getCouponByHadpullId($order['merchant_coupon_id'], false);
            if($coupon){
                $couponFormat = (new MerchantCouponService())->formatDiscount([$coupon]);
                $tempCoupon = [
    //                'value' => $couponFormat[0]['discount_des'],
                    'value' => L_('商家优惠券'),
                    'minus' => $order['merchant_coupon_price']
                ];
                $order['coupon_list'][] = $tempCoupon;
            }
        }

        if($order['system_coupon_id']){
            $coupon = (new SystemCouponService())->getCouponByHadpullId($order['system_coupon_id'], false);
            if($coupon){
                $couponFormat = (new SystemCouponService())->formatDiscount([$coupon]);
                $tempCoupon = [
    //                'value' => $couponFormat[0]['discount_des'],
                    'value' => L_('平台优惠券'),
                    'minus' => $order['system_coupon_price']
                ];
                $order['coupon_list'][] = $tempCoupon;
            }
        }
        
        if($order['sort_discount'] != 0){
            $tempCoupon = [
//                'value' => $couponFormat[0]['discount_des'],
                'value' => L_('分类折扣'),
                'minus' => $order['sort_discount']
            ];
            $order['coupon_list'][] = $tempCoupon;
        }

        $order['username'] = '';
        $order['phone'] = '';
        if($order['user_type'] == 'uid'){
            $user = (new UserService())->getUser($order['user_id']);
            $order['username'] = $user['nickname'];
            $order['phone'] = $user['phone'];
        }
        $order['pay_price'] = get_format_number($order['price']-$order['system_score_money']);

        // 获得支付信息
        $payInfo = [];
        if($order['third_id']){
            $payInfo = (new PayService())->getPayOrderData([$order['third_id']]);
            $payInfo = $payInfo[$order['third_id']] ?? [];
            $payInfo['pay_type_chanel'] = '';
            if(isset($payInfo['pay_type'])){
                $payInfo['pay_type_chanel'] =  ($payInfo['pay_type'] ? $payInfo['pay_type_txt'] : '').($payInfo['channel'] ? '('.$payInfo['channel_txt'].')' : '');
            }
        }
        $order['pay_info'] = $payInfo;
        return $order;
    }
    /**
     * 返回可用的预定金金额
     * @param $orderId int 订单id
     * @return array
     */
    public function getBookMoney($orderId){
        $where[] = ['order_id' ,'=', $orderId];
        $where[] = ['order_type' ,'=', '0'];
        $where[] = ['is_book_money' ,'=', '0'];
        $order = $this->getOne($where);

        if(!$order){
            return '0';
        }

        $totalMoney = $order['price'];
        return $totalMoney;
    }

    /**
     * 根据条件返回订单列表
     * @param $where array 条件
     * @param $order array 排序
     * @return array
     */
    public function getOrderListByCondition($where, $order = ['order_id'=>'desc']){
        $orderList = $this->diningOrderPayModel->getOrderListByCondition($where, $order);
        if(!$orderList) {
            return [];
        }
        return $orderList->toArray();
        
    }
    

    // 创建订单
    public function addPayOrder($param){
		if(floatval($param['price']) < 0){
            throw new \think\Exception(L_("请携带订单总价"), 1003);
		}
		if(empty($param['order_name'])){
            throw new \think\Exception(L_("请携带订单名称"), 1003);
        }

        // 添加时间
        $param['add_time'] = $_SERVER['REQUEST_TIME'];

        // 保存用户uid
        if(isset($param['user_type']) && $param['user_type'] == 'uid' && $param['user_id']){
            $param['uid'] = $param['user_id'];
        }
        $param['mer_id'] = $param['mer_id'];

		if($orderId = $this->add($param)){
			return $orderId;
		}else{
            throw new \think\Exception(L_("订单创建失败，请重试"), 1003);
		}
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $result = $this->diningOrderPayModel->save($data);
        if(!$result) {
            return false;
        }
        return $this->diningOrderPayModel->id;
        
    }    

    

    /**
     * 更新数据
     * @param $data array 数据
     * @return array
     */
    public function updateByCondition($where, $data){
        if(empty($where) || empty($data)){
            return false;
        }

        try {
            $result = $this->diningOrderPayModel->where($where)->update($data);
        } catch (\Exception $e) {
            
        }
        if(!$result) {
            return false;
        }
        return $result;
        
    }

    /**
     * 根据第三方流水号返回订单
     * @param $orderId int 条件
     * @return array
     */
    public function getOrderListByThirdId($thirdId, $field = true) {
        $order = $this->diningOrderPayModel->getOrderListByThirdId($thirdId, $field);

        if(!$order) {
            return [];
        }
        return $order->toArray();
    }

    /**
    * 根据订单id条件返回订单
    * @param $orderId int 条件
    * @return array
    */
    public function getOrderByOrderId($orderId){
       $order = $this->diningOrderPayModel->getOrderByOrderId($orderId);
       if(!$order) {
           return [];
       }
       $order = $order->toArray();
       $order['total_pay_money'] = get_format_number(
           $order['pay_money'] +
           $order['system_balance'] +
           $order['system_score_money'] +
           $order['merchant_balance_pay'] +
           $order['merchant_balance_give'] + 
           $order['internal_card_pay_money']//一卡通支付金额
       , 2);

       return $order;
    } 

    /**
     * 根据条件返回订单
     * @param $where array 条件
     * @return array
     */
    public function getOne($where){
        $order = $this->diningOrderPayModel->getOne($where);
        if(!$order) {
            return [];
        }
        return $order->toArray();
    }

    /**
     * 根据条件返回订单数量
     * @param $where array 条件
     * @return array
     */
    public function getCount($where){
        $order = $this->diningOrderPayModel->getCount($where);
        if(!$order) {
            return [];
        }
        return $order;
    }

     /**
     * @param $where
     * @return int
     * 获得总量
     */
    public function getNums($where,$field){
        $count = $this->diningOrderPayModel
            ->where($where)
            ->sum($field);
        return $count;
    }
    
}