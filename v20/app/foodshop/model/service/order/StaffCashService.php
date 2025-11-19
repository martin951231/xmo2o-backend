<?php
/**
 * 店员结算详情service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/08/31 11:39
 */

namespace app\foodshop\model\service\order;
use app\common\model\service\UserService as UserService;
use app\foodshop\model\db\DiningOrder;
use app\foodshop\model\db\DiningOrderDetail as DiningOrderDetailModel;
use app\foodshop\model\service\store\FoodshopTableService as FoodshopTableService;
use app\foodshop\model\service\store\FoodshopTableTypeService as FoodshopTableTypeService;
use app\foodshop\model\service\store\MerchantStoreFoodshopService as MerchantStoreFoodshopService;
use app\internal_card\model\db\InternalMembershipCard;
use app\internal_card\model\service\InternalCardService;
use app\merchant\model\service\card\CardNewService as CardNewService;
use app\merchant\model\service\MerchantService as MerchantService;
use app\merchant\model\service\MerchantStoreService as MerchantStoreService;
use app\merchant\model\service\ShopDiscountService as ShopDiscountService;
use app\merchant\model\service\sms\MerchantSmsRecordService;
use app\merchant\model\service\store\StorePayService;
use app\pay\model\service\PayService;
use app\shop\model\service\goods\ShopGoodsService as ShopGoodsService;
use app\foodshop\model\service\order_print\PrintHaddleService;
use think\Exception;
use think\facade\Db;
class StaffCashService {
    public $diningOrderDetailModel = null;
    public $statusArray =[];
    public function __construct()
    {
        $this->diningOrderDetailModel = new DiningOrderDetailModel();
        // 0-未确认，1-已确认，2-正在付款（已锁定），3-已付款，4-已退款
        $this->statusArray = [
            '0' => '待下厨',
            '1' => '已下厨待支付',
            '2' => '已支付',
            '3' => '已付款',
            '4' => '已退款',
        ];
    }



    /**
     * 获得店员结算页详情
     * @param $param
     * @param $staff array 店员
     * @return array
     */
    public function cashDetail($param, $staff){

        // 订单id
        $orderId = isset($param['orderId']) ? $param['orderId'] : 0;
        // 商家优惠券id
        $merchantCouponId = isset($param['merchantCouponId']) ? $param['merchantCouponId'] : 0;
        // 平台优惠券id
        $systemCouponId = isset($param['systemCouponId']) ? $param['systemCouponId'] : 0;
        // 是否使用平台优惠券
        $useSysCoupon = isset($param['useSysCoupon']) ? $param['useSysCoupon'] : 0;
        // 是否使用商家优惠券
        $useMerCoupon = isset($param['useMerCoupon']) ? $param['useMerCoupon'] : 0;

        // 是否使用店员自己输入的会员卡金额
        $useCardMoney = isset($param['use_card_money']) ? $param['use_card_money'] : 0;
        // 不参与优惠金额
        $noDiscountMoney = $param['no_discount_money'] ?? 0;
        // 会员卡使用金额
        $cardMoney = $param['card_money'] ?? 0;

        $order = (new DiningOrderService())->getOrderByOrderId($orderId);
        if(!$order) {
            throw new \think\Exception(L_("订单不存在"), 1003);
        }

        $user = [];
        if($param['uid']){
            $user = (new UserService())->getUser($param['uid']);
        }

        $returnArr = [];
        $result = $this->checkCart($param,$user);

        // 返回数组
        $returnArr['store_id'] = $result['store']['store_id'];
        $returnArr['order_id'] = $orderId;
        $returnArr['real_orderid'] = $order['real_orderid'];
        $returnArr['mer_id'] = $result['store']['mer_id'];
        $returnArr['name'] = $result['store']['name'];
        // $returnArr['images'] = $result['store']['images'];
        $returnArr['cn_name'] = $result['store']['name'];
        $returnArr['order_time'] = date('Y-m-d H:i:s',$order['create_time']);
        $returnArr['userphone'] = $result['userphone'];

        // 取餐方式 堂食就餐方式 1-堂食 2-自取  3-堂食或自取
        $returnArr['dining_type'] = 1; // 店员都是堂食

        // 桌台名称
        $returnArr['table_info'] = [];
        if(isset($order['table_id']) && $order['table_id']){
            $table = (new FoodshopTableService())->geTableById($order['table_id']);
            if($table){
                $returnArr['table_info']['table_id'] = $table['id'];
                $returnArr['table_info']['table_name'] = $table['name'];
            }
        }

        // 桌台类型
        if(isset($order['table_type']) && $order['table_type']){
            $tableType = (new FoodshopTableTypeService())->geTableTypeById($order['table_type']);
            $returnArr['table_info']['table_type_id'] = $tableType['id'];
            $returnArr['table_info']['table_type_name'] = $tableType['name'];
        }

        // 订单金额
        $returnArr['price'] = get_format_number($result['price']);

        $returnArr['old_total_price'] = get_format_number($result['goods_old_total_price']);

        // 应付金额 = 订单金额-平台优惠-商家优惠
        $returnArr['pay_price'] =  $returnArr['price']-$result['sys_first_reduce']-$result['sys_full_reduce']-$result['sto_first_reduce']-$result['sto_full_reduce'];

        // 优惠金额
        $returnArr['discount_price'] = get_format_number($returnArr['old_total_price'] - $returnArr['pay_price']);

        // 满减优惠信息
        $returnArr['discount_list'] =  (new ShopDiscountService())->discountFormart($result['discount_list']);

        //分类折扣
        if($result['sort_discount_price'] > 0){
            $returnArr['discount_list'][] = [
                "type" => "plat_discount",
                "time_select" => 1,
                "value" => "分类折扣",
                "minus" => get_format_number($result['sort_discount_price'])
            ];
        }
        // 可参与优惠的金额
        // $canDiscountMoney = $returnArr['pay_price'] - $noDiscountMoney;
        $canDiscountMoney = $result['can_discounts_price'] - $noDiscountMoney;
        $canDiscountMoney < 0 && $canDiscountMoney = 0;
        if(!$canDiscountMoney){
           // $canDiscountMoney = $returnArr['pay_price'] - $noDiscountMoney;
        }
        
        // 会员卡折扣金额
        $returnArr['merchant_discount_money'] = '0';
        $returnArr['merchant_discount'] = '0';
        $returnArr['has_merchant_discount'] = '0';
        $cardDiscount = (new CardNewService())->getCardDiscountMoney($canDiscountMoney,$result['mer_id'], $param['uid']);
        if($cardDiscount['card_discount_money'] >0) {
            $returnArr['merchant_discount_money'] = $cardDiscount['card_discount_money'];
            $returnArr['merchant_discount'] = $cardDiscount['card_discount'];
            $returnArr['has_merchant_discount'] = '1';
            $returnArr['discount_price'] += $cardDiscount['card_discount_money'];
            $canDiscountMoney -= $returnArr['merchant_discount_money'];
            $canDiscountMoney = get_format_number($canDiscountMoney);
            $returnArr['pay_price'] -= $returnArr['merchant_discount_money'];
            $returnArr['pay_price'] = get_format_number($returnArr['pay_price']);
            if ($canDiscountMoney<0) {
                $canDiscountMoney = 0;
            }
        } 
        // 优惠券
        $returnArr['system_coupon'] = (object)[];
        $returnArr['mer_coupon'] = (object)[];
        $returnArr['has_merchant_coupon'] = 0;
        $returnArr['has_system_coupon'] = 0;
        if($user){//用户登录的情况下才有优惠券
            $cardInfo = (new CardNewService())->getCardByUidAndMerId($result['mer_id'], $user['uid']);
            $tmpOrder['can_coupon_money'] = $canDiscountMoney;
            $tmpOrder['mer_id'] = $returnArr['mer_id'];
            $tmpOrder['store_id'] = $returnArr['store_id'];
            $tmpOrder['business'] = 'meal';
            $tmpOrder['platform'] = 'wap';

            //商家优惠券
            $tmpCoupon = [];
            $merchantCouponService = new \app\common\model\service\coupon\MerchantCouponService();
            if (!empty($merchantCouponId) && isset($useMerCoupon) && $useMerCoupon) {
                $tmpCoupon = $merchantCouponService->getCouponByHadpullId($merchantCouponId);
                $returnArr['has_merchant_coupon'] = 1;
            } else {
                // 会员卡于优惠不同享
                if($cardDiscount['card_discount_money'] >0){
                    $tmpOrder['merchant_card'] = true;
                }
                $cardCouponList = $merchantCouponService->getAvailableCoupon($user['uid'], $returnArr['mer_id'], $tmpOrder);
                $returnArr['has_merchant_coupon'] = $cardCouponList ? 1 : 0;
                $use_mer_coupon = request()->param('use_mer_coupon');
                if ( !isset($use_mer_coupon) && $cardCouponList ) {
                    // 初次默认使用优惠券
                    $tmpCoupon = reset($cardCouponList);
                }
            }
            if (!empty($tmpCoupon)) {
                $merCoupon['had_id'] = $tmpCoupon['id'];
                $merCoupon['order_money'] = get_format_number($tmpCoupon['order_money']);//优惠条件
                $merCoupon['discount'] = get_format_number($tmpCoupon['discount']);//优惠金额
                $merCoupon['discount_desc'] = $merchantCouponService->formatDiscount([$tmpCoupon],true)[0]['discount_des'];//描述

                $merCoupon['discount'] = $merCoupon['discount'] > $tmpOrder['can_coupon_money'] ? $tmpOrder['can_coupon_money'] : $merCoupon['discount'];
                    // 优惠后金额
                $tmpOrder['can_coupon_money'] -= empty($merCoupon['discount']) ? 0 : $merCoupon['discount'];
                $returnArr['pay_price'] -= empty($merCoupon['discount']) ? 0 : $merCoupon['discount'];

                $returnArr['discount_price'] += $merCoupon['discount'];
            } else {
                $merCoupon = [];
            } 
            // //平台优惠券
            $tmpCoupon = array();
            $systemCouponService = new \app\common\model\service\coupon\SystemCouponService();
            if(!empty($systemCouponId) && isset($useSysCoupon) && $useSysCoupon){//选择了优惠券
                $tmpCoupon = $systemCouponService->getCouponByHadpullId($systemCouponId);

                // 获得优惠金额
                if($tmpCoupon){
                    $discountMoney = $systemCouponService->computeDiscount($tmpCoupon['coupon_id'],$tmpOrder['can_coupon_money']);
                    $tmpCoupon['discount_money'] = $discountMoney['order_money_discount'];
                }
                $returnArr['has_system_coupon'] = 1;
            }else{
                $systemCouponList = $systemCouponService->getAvailableCoupon($user['uid'],$tmpOrder);
                $returnArr['has_system_coupon'] = $systemCouponList ? 1 : 0;
                $use_sys_coupon = request()->param('use_sys_coupon');
                if ( !isset($use_sys_coupon) && $systemCouponList ) {
                    // 初次默认使用优惠券
                    $tmpCoupon = $systemCouponList ? $systemCouponList[0] : [];
                }
            }

            if($tmpCoupon){
                $systemCoupon['had_id'] = $tmpCoupon['id'];
                $systemCoupon['order_money'] = get_format_number($tmpCoupon['order_money']);//优惠条件
                $systemCoupon['discount'] = get_format_number($tmpCoupon['discount_money']);//优惠金额
                $systemCoupon['is_discount'] = $tmpCoupon['is_discount'];//是否折扣
                $systemCoupon['discount_value'] = $tmpCoupon['discount_value'];//折扣值
                $systemCoupon['discount_type'] = $tmpCoupon['discount_type'];//减免类型（目前仅支持外卖业务）：0不限，1减免运费，2减免餐费
                $systemCoupon['discount_desc'] = $systemCouponService->formatDiscount([$tmpCoupon],true)[0]['discount_des'];//描述

                $systemCoupon['discount'] = $systemCoupon['discount'] > $tmpOrder['can_coupon_money'] ? $tmpOrder['can_coupon_money'] : $systemCoupon['discount'];

                // 优惠后金额
                $tmpOrder['can_coupon_money'] -= $systemCoupon['discount'];
                $returnArr['pay_price'] -= $systemCoupon['discount'];

                $returnArr['discount_price'] += $systemCoupon['discount'];

            }else{
                $systemCoupon = [];
            } 

            $canDiscountMoney = $tmpOrder['can_coupon_money'];
            $returnArr['system_coupon'] = $systemCoupon;
            $returnArr['mer_coupon'] = $merCoupon;
        }

        // 待支付金额
        // $returnArr['pay_price'] = get_format_number($canDiscountMoney + $noDiscountMoney);

        // 定金抵扣
        $returnArr['book_money'] = '0';
        $returnArr['has_book_pay'] = '0';
        if($result['order_num'] == '1'){
            $bookMoney = (new DiningOrderPayService())->getBookMoney($orderId);
            if($bookMoney){
                $returnArr['has_book_pay'] = 1;
                $returnArr['book_money'] = $bookMoney;
                $returnArr['pay_price'] -= $bookMoney;
            }
        }

        $returnArr['pay_price'] = get_format_number($returnArr['pay_price']); 
        if ($returnArr['pay_price']<0) {
            $returnArr['pay_price'] = 0;
        }

        // 积分
        $returnArr['system_score'] = 0;// 积分使用数量
        $returnArr['system_score_money'] = 0;// 平台积分抵扣金额
        $systemScore=0;
        $systemScoreMoney=0;
        $orderMoney = $returnArr['pay_price'];
        if(isset($user['uid']) && $user['uid']){
            //使用积分
            $score_display = false;//显示积分抵扣功能
            $userScoreUseCondition = cfg('user_score_use_condition');//积分使用条件（必须设置）
            $userScoreUsePercent = cfg('user_score_use_percent');//抵扣1元所需积分量（必须设置）
            if($userScoreUseCondition > 0 && $orderMoney > $userScoreUseCondition && $userScoreUsePercent > 0 && $user && $user['score_count'] > 0){
                $score_display = true;
                $user_score_max_use = (new UserService())->checkScoreCanUse($user['uid'], $orderMoney, 'meal', 0, $order['mer_id'])['score'];//获取设置的积分最大使用量
                $scoreUse = min($user_score_max_use, $user['score_count']);
                $scoreDeducteMoney = sprintf('%.2f',$scoreUse/$userScoreUsePercent);

                if($scoreDeducteMoney >= $orderMoney){
                    $show_pay_money = 0;
                    $online_pay_money = 0;
                    
                    $real_score_use = ceil($orderMoney*$userScoreUsePercent);
                    $systemScore = $real_score_use;
                    $systemScoreMoney = $orderMoney;
                }
                else{
                    $systemScore = $scoreUse;
                    $systemScoreMoney = $scoreDeducteMoney;
                }
            }
            $returnArr['system_score'] = $systemScore;// 积分使用数量
            $returnArr['system_score_money'] = $systemScoreMoney;// 平台积分抵扣金额
            $returnArr['pay_price'] -= $systemScoreMoney;
            $returnArr['discount_price'] += $returnArr['system_score_money'];
/*
            // 会员卡使用金额
            $cardMoneyTotal = $cardInfo['card_money'] + $cardInfo['card_money_give'];
            $returnArr['card_money'] = 0;
            if($useCardMoney){
                // 店员自己输入使用金额
                if($cardMoneyTotal <= $cardMoney){
                    $cardMoney = $cardMoneyTotal;
                }
                if($returnArr['pay_price'] <= $cardMoney){
                    $returnArr['card_money'] = $returnArr['pay_price'];
                }else{
                    $returnArr['card_money'] = $cardMoney;
                }
                $returnArr['pay_price'] -= $returnArr['card_money'];
            }else{
                // 自动计算使用金额
                if($cardMoneyTotal <= $returnArr['pay_price']){
                    $returnArr['card_money'] = $cardMoneyTotal;
                }else{
                    $returnArr['card_money'] = $returnArr['pay_price'];
                }
                $returnArr['pay_price'] -= $returnArr['card_money'];
            }*/
        }

        $returnArr['pay_price'] = get_format_number(max(0,$returnArr['pay_price']));

        // 支付方式
        $returnArr['pay_type'] = [
            'offline' => 1,// 线下支付1开启0关闭
            'balance' => 1,//平台余额支付
            'online' => 1,//在线支付
        ];
        $returnArr['offline_pay_type'] = [];

        // 获取是否存在线下支付方式
        $where = [
            'store_id' => $staff['store_id']
        ];
        // 线下支付方式
        $storePay = (new StorePayService())->getSome($where);
        if(!$storePay){
            $returnArr['pay_type']['offline'] = 0;
        }else{
            $returnArr['offline_pay_type'] = $storePay;
        }
        //套餐核销信息
        $verificResult = (new DiningOrderDetailService())->checktVerific($orderId);
        if (empty($verificResult)) {
            $returnArr['has_package'] = false;//本次是否购买了套餐
            $returnArr['verificStatus'] = false;
            $returnArr['verificPackageList'] = [];
        } else {
            $returnArr['has_package'] = true;//本次是否购买了套餐
            $returnArr['verificStatus'] = $verificResult['verificStatus'];
            $returnArr['verificMoney'] = $verificResult['verificMoney'];
            $returnArr['verificPackageList'] = $verificResult['verificPackageList'];
        }
        $returnArr['goods_total_price'] = $returnArr['price'];
        $returnArr['price'] = get_format_number($result['goods_old_total_price']);

        return $returnArr;
    }

    //校验用户下单信息
    public function checkCart($param, $user)
    {
        // 订单id
        $orderId = isset($param['orderId']) ? $param['orderId'] : 0;
        // 不参与优惠金额
        $noDiscountMoney = $param['no_discount_money'] ?? 0;
        $uid = $user['uid'] ?? 0;

        // 商品信息
        $goodsDetail = isset($param['goodsDetail']) ? $param['goodsDetail'] : [];

        // 店铺id
        $storeId = isset($param['store_id']) ? $param['store_id'] : 0;

        if(!$goodsDetail){
            $order = (new DiningOrderService())->getOrderByOrderId($orderId);
            if(!$order) {
                throw new \think\Exception(L_("订单不存在"), 1003);
            }

            if($order['is_temp'] == 1){
                // 快速点餐 直接结算购物车商品
                $goodsDetail = (new DiningOrderTempService())->getGoPayGoods($orderId,1);
            }else{
                // 获得商品信息
                $goodsDetail = (new DiningOrderDetailService())->getGoPayGoods($orderId,$user,1);
            }

            // 店铺ID
            $storeId = $order['store_id'];
        }

        // 店铺信息
        $store = (new MerchantStoreService())->getStoreByStoreId($storeId);

        if ($store['have_meal'] == 0 || $store['status'] != 1) {
            throw new \think\Exception(L_("商家已经关闭了该业务,不能下单了!"), 1003);
        }

        $foodshopStore = (new MerchantStoreFoodshopService())->getStoreByStoreId($storeId);
        if (empty($store) || empty($foodshopStore)) {
            throw new \think\Exception(L_("店铺信息有误"), 1003);
        }

        $store = array_merge($store, $foodshopStore);


        // 商家信息
        $merchant = (new MerchantService())->getMerchantByMerId($store['mer_id']);


        // 订单总价
        $totalPrice = $goodsDetail['goods_total_price'];

        // 待支付金额
        $price = $goodsDetail['go_pay_money'];

        //参加满减优惠的金额
        $canDiscountMoney = round($price, 2);
        if($noDiscountMoney){
            $canDiscountMoney = get_format_number($canDiscountMoney - $noDiscountMoney);
        }

        $isDiscount = 0;

        // 满减service
        $shopDiscountService = new ShopDiscountService();

        // 获得所有满减优惠
        $discounts = $shopDiscountService->getDiscounts($store['mer_id'], $storeId, '',0,1);

        $storeOrderCount = 0;
        $systemOrderCount = 0;
        if($user){
            // 查询用户在该店铺下单数
            $where = [
                'uid' => $user['uid'],
                'store_id' => $storeId,
            ];
            $storeOrderCount = (new DiningOrderService())->getOrderCountByCondition($where);

            //查询用户在平台下单数
            $where = [
                ['uid','=', $user['uid']],
                ['status', 'not in', '0,60']
            ];
            $systemOrderCount = (new DiningOrderService())->getOrderCountByCondition($where);
        }

        // 获得符合条件的满减优惠
        $discountResult = $shopDiscountService->getDiscountList($discounts, $canDiscountMoney, $storeId,  $uid, $storeOrderCount, $systemOrderCount, 0);
        $data = [];
        // $data['total'] = $total;
        $data['price'] = $price;//商品实际总价
        $data['total_price'] = $totalPrice;//商品原价总价
        $data['discount_price'] = $price;//折扣后的总价
        $data['goods'] = $goodsDetail['goods_list'];
        $data['order_num'] = $goodsDetail['order_num'];
        $data['store_id'] = $storeId;
        $data['mer_id'] = $store['mer_id'];
        $data['store'] = $store;
        $data['merchant'] = $merchant;
        $data['discount_list'] = $discountResult['discountList'];
        $data['sys_first_reduce'] = $discountResult['systemFirstReduce'];//平台新单优惠的金额
        $data['sys_full_reduce'] = $discountResult['systemFullReduce'];//平台满减优惠的金额
        $data['sto_first_reduce'] = $discountResult['storeFirstReduce'];//店铺新单优惠的金额
        $data['sto_full_reduce'] = $discountResult['storeFullReduce'];//店铺满减优惠的金额
        $data['platform_merchant'] = $discountResult['platformMerchant'];//平台优惠中商家补贴的总和统计
        $data['platform_plat'] = $discountResult['platformPlat'];//平台优惠中平台补贴的总和统计
        $data['can_discount_money'] = $canDiscountMoney;//可用商家优惠券的总价
        $data['vip_discount_money'] = $price;//VIP折扣后的总价
        $data['userphone'] = isset($user['phone']) && $user['phone'] ? $user['phone'] : '';
        $data['can_discounts_price'] = $goodsDetail['can_discounts_price'] ?? $price;
        $data['sort_discount_price'] = $goodsDetail['goods_old_total_price'] - $price;
        $data['goods_old_total_price'] = $goodsDetail['goods_old_total_price'];
        return $data;

    }

    /**
     * 结算页提交订单支付
     * @param $param
     * @param $user array 用户
     * @return array
     */
    public function goPay($param, $staff)
    {
        $param['store_id'] = $staff['store_id'];
        // 订单id
        $orderId = isset($param['orderId']) ? $param['orderId'] : 0;
        // 商家优惠券id
        $merchantCouponId = isset($param['merchantCouponId']) ? $param['merchantCouponId'] : 0;
        // 平台优惠券id
        $systemCouponId = isset($param['systemCouponId']) ? $param['systemCouponId'] : 0;
        // 是否使用平台优惠券
        $useSysCoupon = isset($param['useSysCoupon']) ? $param['useSysCoupon'] : 0;
        // 是否使用商家优惠券
        $useMerCoupon = isset($param['useMerCoupon']) ? $param['useMerCoupon'] : 0;
        // 不参与优惠金额
        $noDiscountMoney = $param['no_discount_money'] ?? 0;
        // 会员卡使用金额
        $cardMoney = $param['card_money'] ?? 0;
        // 修改后的金额
        $changeMoney = $param['change_money'] ?? 0;
        // 是否使用修改后的价格
        $useChangeMoney = $param['use_change_money'] ?? 0;
        // 支付方式
        $payType = $param['pay_type'] ?? 0;
        // 扫码支付的值
        $authCode = $param['auth_code'] ?? 0;
        //一卡通
        $ecardPhone = $param['ecard_phone'] ?? '';
        $ecardPayMoney = $param['ecard_pay_money']  ?? 0;

        // 用户id
        $uid = $param['uid'] ?? 0;

        $order = (new DiningOrderService())->getOrderByOrderId($orderId);
        if(!$order) {
            throw new \think\Exception(L_("订单不存在"), 1003);
        }

        // 用户信息
        $user = [];
        if($uid){
            $user = (new UserService())->getUser($uid);
        }

        $returnArr = [];
        /***************************************计算支付金额 start******************************/

        $result = $this->checkCart($param,$user);

        $store =  $result['store'];

        // 商品信息
        $goods = $result['goods'];

        // 备注
        $note = isset($_POST['note']) ? htmlspecialchars($_POST['note']) : '';

        $nowTime = time();

        // 支付订单信息
        $payOrderParam = [
            'order_id' => $orderId,
            'order_name' => L_('餐饮订单'),
            'store_id' => $store['store_id'],
            'mer_id' => $store['mer_id'],
            'wx_cheap' => 0,
            'order_type' => '1',//尾款
            'add_time' => $nowTime,
            'offline_pay_type' => $param['offline_pay_type'] ?? '0',
        ];
        if ($payOrderParam['offline_pay_type'] > 0) {
            $payOrderParam['offline_pay_name'] = (new \app\merchant\model\db\StorePay())->where('id', $payOrderParam['offline_pay_type'])->value('name');
        }

        // 店员信息
        $payOrderParam['is_staff'] = 1;
        $payOrderParam['staff_id'] = $staff['id'];

        // 用户
        if($user){
            $payOrderParam['uid'] = $uid;
            $payOrderParam['user_type'] = 'uid';
            $payOrderParam['user_id'] = $user['uid'];
        }

        // 长id
        if ($user) {
            $orderid = build_real_orderid($user['uid']);//real_orderid
        }else{
            $id = mt_rand(1000,9999);
            $orderid = build_real_orderid($id);//real_orderid
        }

        $payOrderParam['total_price'] = $result['total_price'];
        $payOrderParam['orderid'] = $orderid;
        $payOrderParam['last_time'] = $nowTime;
        $payOrderParam['merchant_reduce'] = $result['sto_first_reduce'] + $result['sto_full_reduce'];//店铺优惠
        $payOrderParam['balance_reduce'] = $result['sys_first_reduce'] + $result['sys_full_reduce'];//平台优惠
        $payOrderParam['platform_merchant'] = $result['platform_merchant'];//平台优惠中商家补贴的总和统计
        $payOrderParam['platform_plat'] = $result['platform_plat'];//平台优惠中平台补贴的总和统计
        $payOrderParam['discount_detail'] = $result['discount_list'] ? serialize($result['discount_list']) : '';//优惠详情

        // 订单总价 优惠后价格
        $price = $result['price'] - $payOrderParam['merchant_reduce'] - $payOrderParam['balance_reduce'];//实际要支付的价格

        //可用优惠券的总价
        // $canDiscountMoney = get_format_number($price-$noDiscountMoney);
        $canDiscountMoney = get_format_number($result['can_discounts_price'] - $noDiscountMoney); 
        if(!$canDiscountMoney){
            $canDiscountMoney = get_format_number($price-$noDiscountMoney);
        }

        $payOrderParam['can_discount_money'] = $canDiscountMoney;
        $payOrderParam['no_discount_money'] = $noDiscountMoney;

        if($user){
            $payOrderParam['merchant_coupon_id'] = 0;
            $payOrderParam['merchant_coupon_price'] = 0;
            $payOrderParam['systemCoupon_id'] = 0;
            $payOrderParam['systemCoupon_price'] = 0;

            // 会员卡折扣金额

            $cardDiscount = (new CardNewService())->getCardDiscountMoney($canDiscountMoney,$store['mer_id'], $user['uid']);
            if($cardDiscount['card_discount_money'] >0) {
                $payOrderParam['merchant_discount_money'] = $cardDiscount['card_discount_money'];
                $payOrderParam['merchant_discount'] = $cardDiscount['card_discount'];
                // 抵扣金额
                $canDiscountMoney -= $payOrderParam['merchant_discount_money'];
                $price -= $payOrderParam['merchant_discount_money'];
            }

            // 用于优惠券
            $tmpOrder['can_coupon_money'] = $canDiscountMoney;

            //商家优惠券
            $cardInfo = (new CardNewService())->getCardByUidAndMerId($result['mer_id'], $user['uid']);
            // 商家优惠券
            if ($useMerCoupon) {
                if (!empty($merchantCouponId)) {
                    $tmpCoupon = (new \app\common\model\service\coupon\MerchantCouponService())->getCouponByHadpullId($merchantCouponId);
                    if ($tmpCoupon && $tmpCoupon['discount']>$tmpOrder['can_coupon_money']) {
                        $tmpCoupon['discount'] = $tmpOrder['can_coupon_money'];
                    }
                }

                if (!empty($tmpCoupon)) {
                    $payOrderParam['merchant_coupon_id'] = $tmpCoupon['id'];
                    $payOrderParam['merchant_coupon_price'] = max(0,$tmpCoupon['discount']);

                    if($payOrderParam['merchant_coupon_price'] > $tmpOrder['can_coupon_money']){
                        // 优惠金额不能大于可优惠金额
                        $payOrderParam['merchant_coupon_price'] = $tmpOrder['can_coupon_money'];
                    }
                    $tmpOrder['can_coupon_money'] = get_format_number($tmpOrder['can_coupon_money'] - $payOrderParam['merchant_coupon_price']);
                    $price = get_format_number($price - $payOrderParam['merchant_coupon_price']);
                }
            }

            //平台优惠券
            if ($useSysCoupon) {
                $tmpCoupon = [];
                $payOrderParam['system_coupon_price'] = 0;
                if(!empty($systemCouponId) && $tmpOrder['can_coupon_money']>0){
                    $tmpCoupon = (new \app\common\model\service\coupon\SystemCouponService())->getCouponByHadpullId($systemCouponId);
                }

                if($tmpCoupon){
                    // 获得优惠金额
                    $discountMoney = (new \app\common\model\service\coupon\SystemCouponService())->computeDiscount($tmpCoupon['coupon_id'],$tmpOrder['can_coupon_money']);
                    if ($discountMoney['order_money_discount']>$tmpOrder['can_coupon_money']) {
                        $discountMoney['order_money_discount'] = $tmpOrder['can_coupon_money'];
                    }
                    $payOrderParam['system_coupon_price'] = $discountMoney['order_money_discount'];

                    if($payOrderParam['system_coupon_price'] > $tmpOrder['can_coupon_money']){
                        // 优惠金额不能大于可优惠金额
                        $payOrderParam['system_coupon_price'] = $tmpOrder['can_coupon_money'];
                    }

                    $tmpOrder['can_coupon_money'] = get_format_number($tmpOrder['can_coupon_money'] - $payOrderParam['system_coupon_price']);
                    $price = get_format_number($price - $payOrderParam['system_coupon_price']);
                }
            }

            // 抵扣金额
            $canDiscountMoney = $tmpOrder['can_coupon_money'];
        }

        // 待支付金额
        // $price = get_format_number($canDiscountMoney + $noDiscountMoney);

        // 定金抵扣
        if($result['order_num'] == '1'){
            $bookMoney = (new DiningOrderPayService())->getBookMoney($orderId);
            if($bookMoney){
                $payOrderParam['book_money'] = $bookMoney<=$price ? $bookMoney : $price;
                $price -= $payOrderParam['book_money'];
                $price = get_format_number($price);

            }
        }

        $price = get_format_number($price);
        // 金额不能小于0
        $price = max(0,$price);

        // 需要支付金额 折扣后金额
        $payOrderParam['price'] = $price;

        // 店员修改价格
        if($useChangeMoney){
            $payOrderParam['price'] = $changeMoney;
            $payOrderParam['old_price'] = $price;
        }
        /***************************************计算支付金额 end******************************/

        /***************************************保存订单基本信息 start******************************/

        // 添加支付订单
        $payOrderId = (new DiningOrderPayService())->addPayOrder($payOrderParam);

        // 更新商品信息
        $data = [
            'third_id' => $payOrderId
        ];
        $where = [
            ['order_id', '=', $orderId],
            ['status', 'in', '0,1'],
            ['num', 'exp', Db::raw(' > refundNum')],
        ];
        (new DiningOrderDetailService())->updateByCondition($where,$data);

        // 添加日志
        (new DiningOrderLogService())->addOrderLog($orderId, '17', L_('店员去结算'), $user);

        /***************************************保存订单基本信息 end******************************/

        /***************************************验证支付信息 start******************************/
        // 待支付金额
        $payMoney = $payOrderParam['price'];

        // 积分可抵扣金额
        $payOrderParam['system_score'] = 0;// 积分使用数量
        $payOrderParam['system_score_money'] = 0;// 平台积分抵扣金额
        if($param['system_score'] && $param['system_score_money']){
            $payOrderParam['system_score'] = $param['system_score'];// 积分使用数量
            $payOrderParam['system_score_money'] = $param['system_score_money'];// 平台积分抵扣金额
            // 待支付金额
            $payMoney = $payMoney - $payOrderParam['system_score_money'];
        }

        //会员卡余额支付
        $payOrderParam['merchant_balance_pay'] = 0;
        $payOrderParam['merchant_balance_give'] = 0;
        if($param['card_money'] > 0) {
            if (empty($cardInfo)) {
                throw new \think\Exception(L_('会员卡不存在'));
            }

            // 会员卡余额
            $merchantBalance['card_money'] = $cardInfo['card_money'];
            $merchantBalance['card_give_money'] = $cardInfo['card_money_give'];

            if ($param['card_money'] >= $payMoney) {
                // 会员卡余额使用金额不能大于待支付金额
                $param['card_money'] = $payMoney;
            }

            $cardPay = 0;
            if ($merchantBalance['card_money'] > 0) {
                if ($merchantBalance['card_money'] >= $param['card_money']) {
                    $payOrderParam['merchant_balance_pay'] = $param['card_money'];
                } else {
                    $payOrderParam['merchant_balance_pay'] = $merchantBalance['card_money'];
                }
                $param['card_money'] -= $merchantBalance['card_money'];
                $cardPay += $payOrderParam['merchant_balance_pay'];
            }

            if ($merchantBalance['card_give_money'] > 0 && $param['card_money'] > 0) {
                if ($merchantBalance['card_give_money'] >= $param['card_money']) {
                    $payOrderParam['merchant_balance_give'] = $param['card_money'];
                } else {
                    $payOrderParam['merchant_balance_give'] = $merchantBalance['card_give_money'];
                }
                $param['card_money'] -= $merchantBalance['card_give_money'];

                $cardPay += $payOrderParam['merchant_balance_give'];
            }

            if ($cardPay < $param['card_money']) {
                throw new \think\Exception(L_('会员卡余额不足'));
            }

            // 待支付金额
            $payMoney = $payMoney - $cardPay;
        }

        //================================使用一卡通支付金额================================
        if($ecardPhone && $ecardPayMoney){
            if($ecardPayMoney > $payMoney){
                throw_exception(L_('一卡通支付应付金额错误'));
            }
            $ecardInfo = (new InternalCardService)->beforePay($order['store_id'], $ecardPhone, $ecardPayMoney);
            $payOrderParam['internal_card_pay'] = $ecardInfo['internal_card_pay'];
            $payMoney = bcsub($payMoney, $ecardPayMoney, 2); 
        }
        
        $payMoney = get_format_number($payMoney);

        // 支付信息
        $payOrderParam['pay_type'] = '';
        $payOrderParam['paid'] = '1';
        $payOrderParam['pay_time'] = time();
        $payOrderParam['is_own'] = 0;
        $payOrderParam['offline_money'] = 0;//线下支付金额
        $payOrderParam['pay_money'] = 0;//在线支付金额

        $returnArr['status'] = 1;
        $returnArr['msg'] = L_('支付成功');
        if($payMoney <= 0){
            $res = $this->afterPay($payOrderId, $payOrderParam);
        }else{
            if(!in_array($param['pay_type'],['offline','balance','online'])){
                throw new \think\Exception(L_('请选择正确的支付方式'));
            }

            switch ($param['pay_type']){
                case 'offline':// 线下支付
                    $payOrderParam['pay_type'] = 'offline';
                    $payOrderParam['offline_money'] = $payMoney;

                    if(!isset($param['offline_pay_type']) || empty($param['offline_pay_type'])){
                        throw new \think\Exception(L_('请选择线下支付方式'));
                    }
                    $payOrderParam['offline_pay_type'] = $param['offline_pay_type'];
                    $res = $this->afterPay($payOrderId, $payOrderParam);
                    break;
                case 'balance':// 余额支付
                    $smsCode = $param['sms_code'] ?? '';
                    $phone = $param['phone'] ?? '';
                    if(is_null(cfg('balance_pay_use_sms')) || cfg('balance_pay_use_sms') == 1){
                        // 验证码
                        if(!$smsCode){
                            throw new \think\Exception(L_("请输入验证码"), 1003);
                        }

                        // 获得最后一次短信验证码
                        $where = [
                            'phone' => $phone
                        ];
                        $lastSms = (new MerchantSmsRecordService())->getLastOne($where);
                        if(empty($lastSms)){
                            throw new \think\Exception(L_("验证码错误"), 1003);
                        }
                        if($lastSms['status']==1){
                            throw new \think\Exception(L_("该短信验证码已失效，请重新获取！"), 1003);
                        }
                        if(time() - $lastSms['send_time'] > 1200){
                            throw new \think\Exception(L_("短信验证码已超过20分钟！"), 1003);
                        }
                        if($smsCode != $lastSms['extra']){
                            throw new \think\Exception(L_("短信验证码不正确！"), 1003);
                        }
                    }
                    

                    // 验证用户信息
                    $nowUser = (new UserService())->getUser($phone,'phone');
                    if(empty($nowUser)){
                        throw new \think\Exception(L_("用户不存在！"), 1003);
                    }
                    if($nowUser['now_money'] < $payMoney){
                        throw new \think\Exception(L_("余额不足！"), 1003);
                    }

                    $payOrderParam['system_balance'] = $payMoney;
                    $payOrderParam['uid'] = $nowUser['uid'];
                    $res = $this->afterPay($payOrderId, $payOrderParam);
                    break;
                case 'online': // 在线支付
                    if(empty($authCode)){
                        throw new \think\Exception(L_('请输入付款码'));
                    }

                    // 调用在线支付
                    $payService = new PayService($store['city_id'], $store['mer_id'], $store['store_id']);
                    try {
                        $payResult = $payService->scanPay($authCode, $payMoney);
                    } catch (\Exception $e) {
                        throw new \think\Exception($e->getMessage(), $e->getCode());
                    }

                    if($payResult['status'] == 1){
                        $payOrderParam['pay_type'] = $payResult['pay_type'];
                        $payOrderParam['paid_orderid'] = $payResult['order_no'];
                        $payOrderParam['is_own'] = $payResult['is_own'];
                        $payOrderParam['pay_money'] = $payMoney;
                        //支付成功
                        $res = $this->afterPay($payOrderId, $payOrderParam);
                    }else{
                        // 正在支付
                        $payOrderParam['pay_type'] = $payResult['pay_type'];
                        $payOrderParam['paid_orderid'] = $payResult['order_no'];
                        $returnArr['msg'] = L_('正在支付');

                        //保存信息
                        $saveData = [
                            'system_score' => $payOrderParam['system_score'] ?? '0',
                            'system_score_money' => $payOrderParam['system_score_money'] ?? '0',
                            'merchant_balance_pay' => $payOrderParam['merchant_balance_pay'] ?? '0',
                            'merchant_balance_give' => $payOrderParam['merchant_balance_give'] ?? '0',
                            'pay_money' => $payMoney,
                            'pay_time' => time(),
                        ];
                        $where = [
                            'pay_order_id' => $payOrderId
                        ];
                        $res = (new DiningOrderPayService())->updateByCondition($where,$saveData);
                    }
                    $returnArr['status'] = $payResult['status'];
                    $returnArr['pay_type'] = $payResult['pay_type'];
                    $returnArr['order_no'] = $payResult['order_no'];
                    break;
            }
        }

        /***************************************验证支付信息 end******************************/

        if(!$res){
            throw new \think\Exception(L_('支付失败，请稍后重试'));
        }

        //订单支付完成后打印标签
        (new PrintHaddleService())->printOrderLabel($order);

        $returnArr['pay_order_id'] = $payOrderId;
        return $returnArr;

    }

    /**
     * @return array|string[]
     */
    public function queryScanPay($param)
    {
        $orderNo = $param['order_no'] ?? '';
        $payType = $param['pay_type'] ?? '';
        $orderId = $param['pay_order_id'] ?? '';

        //订单信息
        $payOrder = (new DiningOrderPayService())->getOrderByOrderId($orderId);
        if(empty($payOrder)){
            throw new \think\Exception(L_('订单不存在'));
        }


        try {
            $payResult =  (new PayService())->queryScanPay($orderNo,$payType);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage(), $e->getCode());
        }

        $returnArr = [];
        if($payResult['status'] == 1){
            //支付成功
            $payOrder = (new DiningOrderPayService())->getOrderByOrderId($orderId);

            $payOrder['pay_type'] = $payResult['pay_type'];
            $payOrder['paid_orderid'] = $payResult['order_no'];
            $payOrder['is_own'] = $payResult['is_own'];

            $res = $this->afterPay($orderId, $payOrder);

            $returnArr['msg'] = L_('支付成功');
        }elseif($payResult['status'] == 0){
            throw new \think\Exception(L_('支付失败，请稍后重试'));
        }else{
            // 正在支付
            $returnArr['msg'] = L_('正在支付');
        }
        $returnArr['status'] = $payResult['status'];
        $returnArr['pay_type'] = $payResult['pay_type'];
        $returnArr['order_no'] = $payResult['order_no'];
        $returnArr['pay_order_id'] = $orderId;
        return $returnArr;
    }

    /**
     * @return true
     */
    public function afterPay($payOrderId, $payOrderParam)
    {
        $extra = [
            'paid' => 1,
            'paid_money'  => $payOrderParam['pay_money'] ?? 0,
            'paid_time' => $payOrderParam['pay_time'] ?? time(),
            'paid_type' => $payOrderParam['pay_type'] ?? '',
            'paid_orderid' => $payOrderParam['paid_orderid'] ?? '',
            'is_own' => $payOrderParam['is_own'] ?? 0,
            'offline_money'		=> $payOrderParam['offline_money'] ?? 0,
            'current_score_use'		=> $payOrderParam['system_score'] ?? 0,
            'current_score_deducte'	=> $payOrderParam['system_score_money'] ?? 0,
            'current_system_balance'=> $payOrderParam['system_balance'] ?? 0,
            'current_merchant_balance'	=> $payOrderParam['merchant_balance_pay'] ?? 0,
            'current_merchant_give_balance'	=> $payOrderParam['merchant_balance_give'] ?? 0,
            'uid'  => $payOrderParam['uid'] ?? 0,
            'current_qiye_balance'	=> 0,
            'internal_card_pay'	=> $payOrderParam['internal_card_pay'] ?? '',
        ];

        $res = (new DiningOrderPayService())->afterPay($payOrderId,$extra);
        if(!$res){
            throw_exception('支付失败，请稍后重试');
        }

        return true;
    }
}