<?php
/**
 * 餐饮订单商品详情service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/30 17:46
 */

namespace app\foodshop\model\service\order;
use app\foodshop\model\db\DiningOrderDetail as DiningOrderDetailModel;
use app\foodshop\model\service\goods\FoodshopGoodsLibraryService;
use app\shop\model\service\goods\GoodsImageService as GoodsImageService;
use app\shop\model\service\goods\ShopGoodsService as ShopGoodsService;
use app\group\model\service\GroupFoodshopPackageDataService;
use think\facade\Db;
class DiningOrderDetailService {
    public $diningOrderDetailModel = null;
    public $statusArray =[];
    public function __construct()
    {
        $this->diningOrderDetailModel = new DiningOrderDetailModel();
        // 0-未确认，1-已确认，2-正在付款（已锁定），3-已付款，4-已退款
        $this->statusArray = [
            '0' => L_('待下厨'),
            '1' => L_('已下厨待支付'),
            '2' => L_('正在支付'),
            '3' => L_('已支付'),
            '4' => L_('已退款'),
        ];
    }

    /**
     * 获取客户端IP地址
     * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @return mixed
     */
    function get_client_ip($type = 0) {
        $type       =  $type ? 1 : 0;
        static $ip  =   NULL;
        if ($ip !== NULL) return $ip[$type];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos    =   array_search('unknown',$arr);
            if(false !== $pos) unset($arr[$pos]);
            $ip     =   trim($arr[0]);
        }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip     =   $_SERVER['HTTP_CLIENT_IP'];
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u",ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }

    /**
     *根据订单id获取套餐核销记录
     * @param $orderId int
     * @return array
     */
    public function checktVerific($orderId)
    {
        if (empty($orderId)) {
            return [];
        }
        $where = [
            ['order_id', '=', $orderId],
            ['package_id', '>', '0'],
        ];
        $detail = $this->getOrderDetailByCondition($where);
        if (empty($detail)) {
            return [];
        }
        $detail = array_column($detail, NULL, 'uniqueness_number');
        $groupFoodshopPackageDataService = new GroupFoodshopPackageDataService();
        $verificMoney = 0;//已核销金额
        $verificPackageList = [];//核销套餐记录
        $verificStatus = false;
        foreach ($detail as $good)
        {
            //套餐数量-退款数量 != 核销数，说明可以核销
            if (($good['package_num']-$good['refundNum']) != $good['verificNum']) {
                $verificStatus = true;
            }
            if($good['verificNum'] > 0) {//说明有核销记录
                $where = [
                    ['order_id', '=', $good['order_id']],
                    ['package_id', '=', $good['package_id']],
                    ['uniqueness_number', '=', $good['uniqueness_number']],
                ];
                $result = $groupFoodshopPackageDataService->getPackageVerificList($where);
                foreach ($result as $value)
                {
                    $verificMoney += $value['price'];
                    $arr['package_name'] = $value['name'];
                    $verificPackageList[] = $arr;
                }
            }
        }
        $returnArr = [];
        $returnArr['verificMoney'] = $verificMoney;
        $returnArr['verificStatus'] = $verificStatus;
        $returnArr['verificPackageList'] = $verificPackageList;
        return $returnArr;
    }

    /**
     *根据订单id获取商品列表
     * @param $orderId int  
     * @return array
     */
    public function getFormartGoodsDetailByOrderId($order,$foodshopStore=[], $user = [], $staff= []){
        $orderId = $order['order_id'];
        if(empty($orderId)){
            return [];
        }

        $orderDetail = $this->getOrderDetailByOrderId($orderId);

        //订单套餐商品详情
        $orderPackageDetail = [];
        foreach ($orderDetail as $key => $value)
        {
            if ($value['package_id'] > 0) {
                $orderPackageDetail[] = $value;
                unset($orderDetail[$key]);
            }
        }

        if(!$orderDetail && empty($orderPackageDetail)){
            return [];
        }

        // 备注
        $goodsNote = $order['goods_note'] ? unserialize($order['goods_note']) : [];

        $returnArr = [];
        // 组合附属商品
        $orderDetail = (new ShopGoodsService())->combinationData($orderDetail, 'spec', 0);

        // 商品总数
        $goodsGount = '0';

        //商品原总价
        $goodsOldTotalPrice = 0;

        // 商品总价
        $goodsTotalPrice = '0';

        //商品核销总价
        $verificTotalPrice = '0';


        // 优惠总价
        $discountPrice = 0;

        // 商品待支付总价
        $goPayMoney = '0';

        // 商品待支付树龄
        $goPayNum = '0';

        // 获得商品信息
        $goodsId = array_column($orderDetail,'goods_id');

        $where = [
            'goods_ids' => implode(',',$goodsId),
            'store_id' => $order['store_id'],
        ];
        $goodsArr = (new FoodshopGoodsLibraryService())->getGoodsListByStoreId($order['store_id'], $where, 'merchant');
        $image = array_column($goodsArr,'image', 'goods_id');
        $minNum = array_column($goodsArr,'min_num', 'goods_id');
        $goodsImageService = new GoodsImageService();
        $orderDetailArr = []; // 合并后的商品列表
        // 组合套餐商品
        if (!empty($orderPackageDetail)) {
            $orderPackageDetail = (new ShopGoodsService())->combinationPackageData($orderPackageDetail, 'spec', 0);
            $orderDetail = array_merge($orderDetail, $orderPackageDetail);
        } 
        foreach($orderDetail as $_detail){
            $tempDetail = [];
            $tempRefundDetail = [];//已退商品
            $tempDetail['goods_id'] = $_detail['goods_id'];
            $tempDetail['id'] = $_detail['id'];
            $tempDetail['sort_id'] = $_detail['sort_id'];
            $tempDetail['name'] = $_detail['name'];
            $tempDetail['spec'] = isset($_detail['spec_sub']) && isset($_detail['spec']) ? trim(str_replace($_detail['spec_sub'],'',$_detail['spec'])) : '';
            $tempDetail['spec_arr'] = $tempDetail['spec'] ? explode('、', $tempDetail['spec'] ) : [];
            $tempDetail['spec_sub'] = isset($_detail['spec_sub']) ? $_detail['spec_sub'] : '';
            $tempDetail['sub_list'] = isset($_detail['sub_list']) ? $_detail['sub_list'] : [];
            $tempDetail['price'] = $_detail['discount_price'];
            $tempDetail['old_price'] = $_detail['price'];
            $tempDetail['discount_price'] = $_detail['discount_price'];
            $tempDetail['unit'] = $_detail['unit'] ?? '';
            $tempDetail['num'] = $_detail['num']-$_detail['refundNum'];
            $tempDetail['package_id'] = $_detail['package_id'];
            $tempDetail['status'] = $_detail['status'];
            $tempDetail['order_num'] = $_detail['order_num'];
            $tempDetail['verificNum'] = $_detail['verificNum'];
            $tempDetail['third_id'] = $_detail['third_id'];
            $tempDetail['label'] = $_detail['discount_price'] != $_detail['price'] ? "折" : '';
            
            $tempDetail['uniqueness_number'] = $_detail['uniqueness_number'];
            $tempDetail['is_must'] = $_detail['is_must'];//是否必点1是2否
            $tempDetail['is_staff'] = isset($_detail['is_staff']) ? $_detail['is_staff'] : 2; //是否店员下单1是2否
            $tempDetail['is_package_goods'] = isset($_detail['is_package_goods']) ? $_detail['is_package_goods'] : false; //是否是套餐1是0否
            $oldGoodsPrice = get_format_number($_detail['price'] * $tempDetail['num']);
            $goodsPrice = get_format_number($_detail['discount_price'] * $tempDetail['num']);
            $tempDetail['total_price'] = $goodsPrice;//商品总价
            $tempDetail['old_total_price'] = $oldGoodsPrice;//商品总价
            $tempDetail['verific_num'] = isset($_detail['verific_num']) ? $_detail['verific_num'] : 0;
            $tempDetail['isRefundPackageGoods'] = isset($_detail['isRefundPackageGoods']) ? $_detail['isRefundPackageGoods'] : true;
            $tempDetail['sort_discount'] = $_detail['sort_discount'];

            if($_detail['sort_discount'] != 0){
                $tempDetail['discount_total_price'] = get_format_number($_detail['discount_price'] * $tempDetail['num']);
            }else{
                $tempDetail['discount_total_price'] = get_format_number($_detail['price'] * $tempDetail['num']);
            }

            if ($_detail['package_id'] > 0 && $_detail['verificNum'] > 0) {
                $verificTotalPrice += get_format_number($_detail['price'] * $_detail['verificNum']);
            }

            if ($_detail['package_id'] == 0) {
                // 商品图片
                $productImage = '';
                $tmpPicArr = isset($image[$_detail['goods_id']]) ? $goodsImageService->getAllImageByPath($image[$_detail['goods_id']], 's') : [];
                $tempDetail['product_image'] = $tmpPicArr ? $tmpPicArr[0] : '';
                if(!$tempDetail['product_image']){
                    if (cfg('is_open_goods_default_image')&&cfg('goods_default_image')) {
                        $tempDetail['product_image'] = cfg('goods_default_image');
                    }else{
                        $tempDetail['product_image'] = cfg('site_url').'/tpl/Merchant/default/static/images/default_img.png';
                    }
                }
                $tempDetail['product_image'] = thumb_img( $tempDetail['product_image'],180,180,'fill');
            } else {
                $tempDetail['product_image'] = isset($_detail['product_image']) ? $_detail['product_image'] : '';
            }



            // 合并后的商品列表
            $tempDetail['num']>0 && $orderDetailArr[] = $tempDetail;

            // 商品总数
            if ($_detail['package_id'] == 0) {
                $goodsGount += $_detail['num']-$_detail['refundNum'];
            } else {
                $goodsGount += $_detail['package_num']-$_detail['refundNum'];
            }


            // 商品总价
            $goodsTotalPrice += $goodsPrice;
            $goodsOldTotalPrice += $oldGoodsPrice;

            if(in_array($_detail['status'],[0,1]) && ($_detail['is_lock']==0 || $staff)){//商品待支付总价
                $goPayNum += ($tempDetail['num']-$_detail['verificNum']);
                if($_detail['sort_discount'] != 0){
                    $goPayMoney += get_format_number($_detail['price'] * ($_detail['sort_discount'] / 10) * (($tempDetail['num']-$_detail['verificNum']))); 
                }else{
                    $goPayMoney += get_format_number($_detail['price'] * ($tempDetail['num']-$_detail['verificNum']));
                }
            }

            // 下单次数
            if(!$_detail['order_num']){
                $_detail['order_num'] = '1';
            }
            if(isset($_detail['refundNum']) && $_detail['refundNum'] > 0){
                $tempRefundDetail = $tempDetail;
                $tempRefundDetail['num'] = $_detail['refundNum'];
                $tempRefundDetail['total_price'] = get_format_number($tempRefundDetail['price'] * $tempRefundDetail['num']);
            }
            if(isset($returnArr[$_detail['order_num']])){
                $tempDetail['num'] >0 && $returnArr[$_detail['order_num']]['status_str'] =  $this->statusArray[$_detail['status']];
                if($order['settle_accounts_type'] == 2){//先付后吃
                    if($_detail['status'] == 0 && $tempDetail['num']>0){
                        $returnArr[$_detail['order_num']]['status_str'] = L_('待支付');
                    }elseif ($_detail['status'] == 3 && $tempDetail['num']>0){
                        $returnArr[$_detail['order_num']]['status_str'] = L_('已下厨');
                    }
                }
                
                //是否显示解锁按钮
                if($returnArr[$_detail['order_num']]['show_unlock']==0 && $_detail['is_lock'] == 1 && $_detail['status']<3){
                    $returnArr[$_detail['order_num']]['status_str'] = L_('正在支付');
                    if((isset($user['user_type']) && $user['user_type'] == $_detail['user_type'] && $user['user_id'] == $_detail['user_id']) || $staff){
                        //锁定者和店员可以解锁
                        $returnArr[$_detail['order_num']]['show_unlock'] = '1';
                    }
                }

                if($_detail['status']==3){
                    $returnArr[$_detail['order_num']]['old_total_price'] += $_detail['price']*($tempDetail['num']-$_detail['verificNum']);
                    if($_detail['sort_discount'] != 0){
                        $returnArr[$_detail['order_num']]['total_price'] += $_detail['price'] * ($_detail['sort_discount'] / 10) * (($tempDetail['num']-$_detail['verificNum'])); 
                    }else{
                        $returnArr[$_detail['order_num']]['total_price'] += $_detail['price']*($tempDetail['num']-$_detail['verificNum']);
                    }
                    
                }else{
                    $returnArr[$_detail['order_num']]['old_total_price'] += $_detail['price']*($tempDetail['num']-$_detail['verificNum']);
                    if($_detail['sort_discount'] != 0){
                        $returnArr[$_detail['order_num']]['total_price'] += $_detail['price'] * ($_detail['sort_discount'] / 10) * (($tempDetail['num']-$_detail['verificNum'])); 
                    }else{
                        $returnArr[$_detail['order_num']]['total_price'] += $_detail['price']*($tempDetail['num']-$_detail['verificNum']);
                    }
                    
                }
                $returnArr[$_detail['order_num']]['total_price'] = get_format_number($returnArr[$_detail['order_num']]['total_price']);
                $returnArr[$_detail['order_num']]['count'] += $tempDetail['num'];
                if($tempDetail['num'] > 0){
                    $returnArr[$_detail['order_num']]['goods'][] = $tempDetail;
                }
                $tempRefundDetail && $returnArr[$_detail['order_num']]['refund_goods'][] = $tempRefundDetail;
            }else{
                if($_detail['status']==3){
                    $returnArr[$_detail['order_num']]['total_price'] = get_format_number($_detail['price']*($tempDetail['num']-$_detail['verificNum']));
                    $returnArr[$_detail['order_num']]['old_total_price'] = get_format_number($_detail['price']*($tempDetail['num']-$_detail['verificNum']));

                    // 订单优惠信息
                    if($_detail['third_id']){
                        $payOrder = (new DiningOrderPayService())->getOrderDetail($_detail['third_id']);
                        if($payOrder){
                            $discountPrice += $payOrder['discount_price'];
                            $returnArr[$_detail['order_num']]['third_id'] = $_detail['third_id'];//支付订单号
                            $returnArr[$_detail['order_num']]['discount_detail'] = $payOrder['discount_detail'];//满减优惠详情
                            $returnArr[$_detail['order_num']]['discount_money'] = $payOrder['merchant_reduce'] + $payOrder['balance_reduce'] ;//满减优惠金额
                            $returnArr[$_detail['order_num']]['discount_price'] = $payOrder['discount_price'];//优惠总额
                            $returnArr[$_detail['order_num']]['coupon_list'] = $payOrder['coupon_list'];//优惠券列表
                            $returnArr[$_detail['order_num']]['merchant_discount'] = $payOrder['merchant_discount'];//会员卡折扣
                            $returnArr[$_detail['order_num']]['merchant_discount_money'] = $payOrder['merchant_discount_money'];//商家会员卡折扣金额
                            $returnArr[$_detail['order_num']]['system_balance'] = $payOrder['system_balance'];//平台余额
                            $returnArr[$_detail['order_num']]['system_coupon_price'] = $payOrder['system_coupon_price'];//平台优惠券金额
                            $returnArr[$_detail['order_num']]['system_score_money'] = $payOrder['system_score_money'];//平台积分抵扣金额
                            $returnArr[$_detail['order_num']]['merchant_balance_pay'] = $payOrder['merchant_balance_pay'] + $payOrder['merchant_balance_give'] ;//商家余额
                            $returnArr[$_detail['order_num']]['merchant_coupon_price'] = $payOrder['merchant_coupon_price'];//商家优惠券金额
                            $returnArr[$_detail['order_num']]['book_money'] = $payOrder['book_money'];//定金抵扣金额
                            $returnArr[$_detail['order_num']]['pay_money'] = $payOrder['pay_money'];//在线支付金额
                            $returnArr[$_detail['order_num']]['pay_type'] = $payOrder['pay_type'];//支付方式
                            $returnArr[$_detail['order_num']]['pay_price'] = $payOrder['pay_price'];
                            $returnArr[$_detail['order_num']]['pay_time'] = date('Y-m-d H:i:s', $payOrder['pay_time']);
                            $returnArr[$_detail['order_num']]['refund_money'] = $payOrder['refund_money'];//退款金额
                            $returnArr[$_detail['order_num']]['pay_info'] = $payOrder['pay_info'];//支付单详情
                            $returnArr[$_detail['order_num']]['username'] = $payOrder['username'];
                            $returnArr[$_detail['order_num']]['phone'] = $payOrder['phone'];
                            if($returnArr[$_detail['order_num']]['pay_time']){

                            }
                        }
                    }
                }else{
                    $returnArr[$_detail['order_num']]['old_total_price'] = get_format_number($_detail['price']*($tempDetail['num']-$_detail['verificNum']));
                    if($_detail['sort_discount'] != 0){
                        $returnArr[$_detail['order_num']]['total_price'] = get_format_number($_detail['price'] * ($_detail['sort_discount'] / 10) * (($tempDetail['num']-$_detail['verificNum']))); 
                    }else{
                        $returnArr[$_detail['order_num']]['total_price'] = get_format_number($_detail['price']*($tempDetail['num']-$_detail['verificNum']));
                    }
                }
                $returnArr[$_detail['order_num']]['number'] = $_detail['order_num'];
                $returnArr[$_detail['order_num']]['number_str'] = L_('#第X1次下单',['X1'=>$_detail['order_num']]);
                $returnArr[$_detail['order_num']]['order_num'] = $_detail['order_num'];
                $returnArr[$_detail['order_num']]['count'] = $tempDetail['num'];
                $returnArr[$_detail['order_num']]['status'] = $_detail['status'];
                $tempDetail['num'] >0 && $returnArr[$_detail['order_num']]['status_str'] =  $this->statusArray[$_detail['status']];
                $returnArr[$_detail['order_num']]['goods_note'] =  isset($goodsNote[($_detail['order_num']-1)]) ? $goodsNote[($_detail['order_num']-1)] : '';

                if($order['settle_accounts_type'] == 2){//先付后吃
                    if($_detail['status'] == 0 && $tempDetail['num']>0){
                        $returnArr[$_detail['order_num']]['status_str'] = L_('待支付');
                    }elseif ($_detail['status'] == 3 && $tempDetail['num']>0){
                        $returnArr[$_detail['order_num']]['status_str'] = L_('已下厨');
                    }
                }

                $returnArr[$_detail['order_num']]['show_unlock'] = '0';//是否显示解锁按钮
                if($_detail['is_lock'] == 1 && $_detail['status']<3){
                    $returnArr[$_detail['order_num']]['status_str'] = L_('正在支付');
                    if((isset($user['user_type']) && $user['user_type'] == $_detail['user_type'] && $user['user_id'] == $_detail['user_id']) || $staff){
                        //锁定者和店员可以解锁
                        $returnArr[$_detail['order_num']]['show_unlock'] = '1';
                    }
                }
                $tempDetail['num']>0 && $returnArr[$_detail['order_num']]['goods'][] = $tempDetail;

                $returnArr[$_detail['order_num']]['refund_goods'] = [];
                $tempRefundDetail && $returnArr[$_detail['order_num']]['refund_goods'][] = $tempRefundDetail;
                

            }
        }

        //分类优惠
        foreach($returnArr as $key => $_goods){
            if(!isset($_goods['discount_detail']) && $_goods['old_total_price'] != $_goods['total_price']){
                $returnArr[$key]['discount_detail'][] = [
                    'value' => L_('分类折扣'),
                    'minus' => get_format_number($_goods['old_total_price'] - $_goods['total_price']),
                    'type'  =>  'plat_discount'
                ];
            }
        }

        // die;
        // 合并同一次支付的
        $orderDetailFormat = [];
        foreach($returnArr as $_goods){
            if(isset($_goods['third_id']) && $_goods['third_id']){
                $index = $_goods['third_id'] + 1000;
                if(isset($orderDetailFormat[$index])){

                    $orderDetailFormat[$index]['goods_combine'][] = $_goods;
                    $orderDetailFormat[$index]['count'] += $_goods['count'];
                    $orderDetailFormat[$index]['total_price'] += $_goods['total_price'];
                }else{
                    $orderDetailFormat[$index] = $_goods;
                    $orderDetailFormat[$index]['goods_combine'][] = $_goods;
                }
            }else{
                $orderDetailFormat[$_goods['order_num']] = $_goods;
                $orderDetailFormat[$_goods['order_num']]['goods_combine'][] = $_goods;
            }
        }

//        foreach ($orderDetail as $key => $value){
//            unset($orderDetailFormat[$key]['goods']);
//            unset($orderDetailFormat[$key]['goods_note']);
//            unset($orderDetailFormat[$key]['number_str']);
//            unset($orderDetailFormat[$key]['refund_goods']);
//        }
//        var_dump($orderDetailFormat);die;
        $returnArr['goods_list'] = array_values($orderDetailFormat);
        $returnArr['goods_detail'] = (new DiningOrderService())->combinationGoods($orderDetailArr);
        $returnArr['goods_detail_old'] = $orderDetailArr;
        $returnArr['goods_count'] = $returnArr['num'] = $goodsGount;
        $returnArr['total_price'] = $returnArr['goods_total_price'] = get_format_number($goodsTotalPrice-$verificTotalPrice);
        $returnArr['old_total_price'] = get_format_number($goodsOldTotalPrice - $verificTotalPrice);
        $returnArr['go_pay_money'] = get_format_number($goPayMoney);
        $returnArr['discount_price'] = get_format_number($discountPrice);
        $returnArr['go_pay_num'] = max(0,$goPayNum);
        return $returnArr;
    }
    
    /**
     *根据支付订单获得商品信息(属于当前订单的商品)
     * @param $payOrderId int  
     * @return array
     */
    public function getGoodsByPayOrderId($payOrderId){
        if(empty($payOrderId)){
            throw new \think\Exception(L_("参数错误"), 1001);
        }

        // 获得当前用户待支付商品
        $where = [
            ['status', 'in', '0,1,2'],
            ['third_id', '=', $payOrderId],
        ];
        $orderDetail = $this->getOrderDetailByCondition($where);
        if(!$orderDetail){
            throw new \think\Exception(L_("抱歉！您当前没有需要结算的商品，暂不可支付哦~"), 1003);
        }

        $returnArr = [];
        // 组合附属商品
        $orderDetail = (new ShopGoodsService())->combinationData($orderDetail, 'spec', 0);

        // 第几次下单
        $orderNum = '0';

        // 商品总数
        $goodsGount = '0';

        // 商品总价
        $goodsTotalPrice = '0';

        // 商品待支付总价
        $goPayMoney = '0';

        $goodsList = [];

        foreach($orderDetail as $_detail){
            $tempDetail = [];
            $goodsPrice = get_format_number($_detail['discount_price'] * $_detail['num']);
            $tempDetail['goods_id'] = $_detail['goods_id'];
            $tempDetail['sort_id'] = $_detail['sort_id'];
            $tempDetail['name'] = $_detail['name'];
            $tempDetail['spec'] = trim(str_replace($_detail['spec_sub'],'',$_detail['spec']));
            $tempDetail['spec_sub'] = $_detail['spec_sub'];
            $tempDetail['sub_list'] = $_detail['sub_list'];
            $tempDetail['price'] = $_detail['price'];
            $tempDetail['discount_price'] = $_detail['discount_price'];
            $tempDetail['unit'] = $_detail['unit'];
            $tempDetail['num'] = $_detail['num'];
            $tempDetail['uniqueness_number'] = $_detail['uniqueness_number'];
            $tempDetail['total_price'] = $goodsPrice;//商品总价
            $tempDetail['is_must'] = $_detail['is_must'];//是否必点1是2否
            $tempDetail['is_staff'] = $_detail['is_staff']; //是否店员下单1是2否	

            if($_detail['order_num']<$orderNum || $orderNum == '0'){
                $orderNum = $_detail['order_num'];
            }
            // 商品总数
            $goodsGount += $_detail['num'];

            // 商品总价
            $goodsTotalPrice += $goodsPrice;

            if(isset($goodsList[$_detail['goods_id']])){
                $goodsList[$_detail['goods_id']]['total_price'] += $_detail['price'];
                $goodsList[$_detail['goods_id']]['num'] += $_detail['num'];
            }else{
                $goodsList[$_detail['goods_id']] = $tempDetail;
            }
        }

        $returnArr['goods_list'] = array_values($goodsList);
        $returnArr['goods_count'] = $goodsGount;
        $returnArr['order_num'] = $orderNum;
        $returnArr['goods_total_price'] = get_format_number($goodsTotalPrice);
        $returnArr['go_pay_money'] = get_format_number($goodsTotalPrice);
        return $returnArr;
    }



    /**
     *根据支付订单获得商品信息(属于当前订单的商品)
     * @param $payOrderId int
     * @return array
     */
    public function getGoodsPrintDetailByPayOrderId($payOrderId){
        if(empty($payOrderId)){
            throw new \think\Exception(L_("参数错误"), 1001);
        }

        // 获得当前用户待支付商品
        $where = [
            ['third_id', '=', $payOrderId],
        ];
        $orderDetail = $this->getOrderDetailByCondition($where);

        //订单套餐商品详情
        $orderPackageDetail = [];
        foreach ($orderDetail as $key => $value)
        {
            if ($value['package_id'] > 0) {
                $orderPackageDetail[] = $value;
                unset($orderDetail[$key]);
            }
        }

        $returnArr = [];
        // 组合附属商品
        $orderDetail = (new ShopGoodsService())->combinationData($orderDetail, 'spec', 0);
        // 组合套餐商品
        if (!empty($orderPackageDetail)) {
            $orderPackageDetail = (new ShopGoodsService())->combinationPackageData($orderPackageDetail, 'spec', 0);
            $orderDetail = array_merge($orderDetail, $orderPackageDetail);
        }

        // 第几次下单
        $orderNum = '0';

        // 商品总数
        $goodsGount = '0';

        // 商品总价
        $goodsTotalPrice = '0';

        // 商品待支付总价
        $goPayMoney = '0';

        $goPayNum = '0';

        $goodsList = [];


        $orderDetailArr = []; // 合并后的商品列表
        foreach($orderDetail as $_detail){
            $tempDetail = [];
            $tempRefundDetail = [];//已退商品
            $tempDetail['goods_id'] = $_detail['goods_id'];
            $tempDetail['id'] = $_detail['id'];
            $tempDetail['sort_id'] = $_detail['sort_id'];
            $tempDetail['name'] = $_detail['name'];
            $tempDetail['spec'] = trim(str_replace($_detail['spec_sub'],'',$_detail['spec']));
            $tempDetail['spec_arr'] = $tempDetail['spec'] ? explode('、', $tempDetail['spec'] ) : [];
            $tempDetail['spec_sub'] = $_detail['spec_sub'];
            $tempDetail['sub_list'] = $_detail['sub_list'];
            $tempDetail['price'] = $_detail['price'];
            $tempDetail['discount_price'] = $_detail['discount_price'];
            $tempDetail['unit'] = $_detail['unit'] ?? '';
            $tempDetail['num'] = $_detail['num']-$_detail['refundNum'];
            $tempDetail['uniqueness_number'] = $_detail['uniqueness_number'];
            $tempDetail['is_must'] = $_detail['is_must'];//是否必点1是2否
            $tempDetail['is_staff'] = $_detail['is_staff']; //是否店员下单1是2否
            $goodsPrice = get_format_number($_detail['price'] * $tempDetail['num']);
            $tempDetail['package_id'] = $_detail['package_id'];
            $tempDetail['total_price'] = $goodsPrice;//商品总价
            $tempDetail['status'] = $_detail['status'];
            $tempDetail['is_lock'] = $_detail['is_lock'];
            $tempDetail['verificNum'] = $_detail['verificNum'];
            $tempDetail['order_num'] = $_detail['order_num'];
            $tempDetail['third_id'] = $_detail['third_id'];

            $orderNum = $_detail['order_num'];

            // 合并后的商品列表
            $tempDetail['num']>0 && $orderDetailArr[] = $tempDetail;

            // 商品总数
            $goodsGount += $tempDetail['num'];

            // 商品总价
            $goodsTotalPrice += $goodsPrice;

            if(in_array($_detail['status'],[0,1]) && $_detail['is_lock']==0){//商品待支付总价
                $goPayMoney += get_format_number($_detail['price'] * $tempDetail['num']);
                $goPayNum += $tempDetail['num'];
            }

        }

        $returnArr['goods_detail'] = (new DiningOrderService())->combinationGoods(($orderDetailArr));
        $returnArr['goods_list'] = $orderDetailArr;
        $returnArr['goods_count'] = $goodsGount;
        $returnArr['order_num'] = $orderNum;
        $returnArr['goods_total_price'] = get_format_number($goodsTotalPrice);
        $returnArr['go_pay_money'] = get_format_number($goPayMoney);
        $returnArr['go_pay_num'] = $goPayNum;
        return $returnArr;
    }


    /**
     *根据下单次数获得商品信息
     * @param $payOrderId int
     * @return array
     */
    public function getGoodsPrintDetailByOrderNum($orderId, $orderNum){
        if(empty($orderId) || empty($orderNum)){
            throw new \think\Exception(L_("参数错误"), 1001);
        }

        // 获得当前用户待支付商品
        $where = [
            ['order_num', '=', $orderNum],
            ['order_id', '=', $orderId],
        ];
        $orderDetail = $this->getOrderDetailByCondition($where);
        //订单套餐商品详情
        $orderPackageDetail = [];
        foreach ($orderDetail as $key => $value)
        {
            if ($value['package_id'] > 0) {
                $orderPackageDetail[] = $value;
                unset($orderDetail[$key]);
            }
        }

        $returnArr = [];
        // 组合附属商品
        $orderDetail = (new ShopGoodsService())->combinationData($orderDetail, 'spec', 0);
        // 组合套餐商品
        if (!empty($orderPackageDetail)) {
            $orderPackageDetail = (new ShopGoodsService())->combinationPackageData($orderPackageDetail, 'spec', 0);
            $orderDetail = array_merge($orderDetail, $orderPackageDetail);
        }


        // 商品总数
        $goodsGount = '0';

        // 商品总价
        $goodsTotalPrice = '0';

        // 商品待支付总价
        $goPayMoney = '0';

        $goPayNum = '0';

        $orderDetailArr = []; // 合并后的商品列表
        foreach($orderDetail as $_detail){
            $tempDetail = [];
            $tempRefundDetail = [];//已退商品
            $tempDetail['goods_id'] = $_detail['goods_id'];
            $tempDetail['id'] = $_detail['id'];
            $tempDetail['sort_id'] = $_detail['sort_id'];
            $tempDetail['name'] = $_detail['name'];
            $tempDetail['spec'] = trim(str_replace($_detail['spec_sub'],'',$_detail['spec']));
            $tempDetail['spec_arr'] = $tempDetail['spec'] ? explode('、', $tempDetail['spec'] ) : [];
            $tempDetail['spec_sub'] = $_detail['spec_sub'];
            $tempDetail['sub_list'] = $_detail['sub_list'];
            $tempDetail['price'] = $_detail['price'];
            $tempDetail['discount_price'] = $_detail['discount_price'];
            $tempDetail['unit'] = $_detail['unit'] ?? '';
            $tempDetail['num'] = $_detail['num']-$_detail['refundNum'];
            $tempDetail['uniqueness_number'] = $_detail['uniqueness_number'];
            $tempDetail['is_must'] = $_detail['is_must'];//是否必点1是2否
            $tempDetail['is_staff'] = $_detail['is_staff']; //是否店员下单1是2否
            $goodsPrice = get_format_number($_detail['price'] * $tempDetail['num']);
            $tempDetail['total_price'] = $goodsPrice;//商品总价
            $tempDetail['package_id'] = $_detail['package_id'];
            $tempDetail['status'] = $_detail['status'];
            $tempDetail['is_lock'] = $_detail['is_lock'];
            $tempDetail['verificNum'] = $_detail['verificNum'];
            $tempDetail['order_num'] = $_detail['order_num'];
            $tempDetail['third_id'] = $_detail['third_id'];

            // 合并后的商品列表
            $tempDetail['num']>0 && $orderDetailArr[] = $tempDetail;

            // 商品总数
            $goodsGount += $tempDetail['num'];

            // 商品总价
            $goodsTotalPrice += $goodsPrice;

            if(in_array($_detail['status'],[0,1]) && $_detail['is_lock']==0){//商品待支付总价
                $goPayMoney += get_format_number($_detail['price'] * $tempDetail['num']);
                $goPayNum += $tempDetail['num'];
            }

        }

        $returnArr['goods_detail'] = (new DiningOrderService())->combinationGoods(($orderDetailArr));
        $returnArr['goods_list'] = $orderDetailArr;
        $returnArr['goods_count'] = $goodsGount;
        $returnArr['order_num'] = $orderNum;
        $returnArr['goods_total_price'] = get_format_number($goodsTotalPrice);
        $returnArr['go_pay_money'] = get_format_number($goPayMoney);
        $returnArr['go_pay_num'] = $goPayNum;
        return $returnArr;
    }


    /**
     *获得打印分单商品列表
     * @param  array $order 订单详情
     * @param  array $orderDetail 商品列表
     * @return array
     */
    public function getFormartGoodsDetailPrint($order, $orderDetail = []){
        // 备注
        $goodsNote = $order['goods_note'] ? unserialize($order['goods_note']) : [];

        // 输出的数组
        $returnArr = [];

        // 商品总数
        $goodsGount = '0';

        // 商品总价
        $goodsTotalPrice = '0';

        //商品核销总价
        $verificTotalPrice = '0';

        $goPayMoney = 0;

        // 优惠总价
        $discountPrice = 0;

        // 获得商品信息
        $goodsId = array_column($orderDetail,'goods_id');

        $where = [
            'goods_ids' => implode(',',$goodsId),
            'store_id' => $order['store_id'],
        ];

        $orderDetailArr = []; // 合并后的商品列表
        foreach($orderDetail as $_detail){
            $tempDetail = $_detail;

            if ($_detail['package_id'] > 0 && $_detail['verificNum'] > 0) {
                $verificTotalPrice += get_format_number($_detail['price'] * $_detail['verificNum']);
            }
            
            // 合并后的商品列表
            $tempDetail['num']>0 && $orderDetailArr[] = $tempDetail;

            // 商品总数
            $goodsGount += $tempDetail['num'];

            // 商品总价
            $goodsTotalPrice += $_detail['total_price'];

            if(in_array($_detail['status'],[0,1])){//商品待支付总价
                $goPayMoney += get_format_number($_detail['price'] * ($tempDetail['num']-$_detail['verificNum']));
            }
            // 下单次数
            if(!$_detail['order_num']){
                $_detail['order_num'] = '1';
            }
           
            if(isset($returnArr[$_detail['order_num']])){
                $returnArr[$_detail['order_num']]['total_price'] = get_format_number($returnArr[$_detail['order_num']]['total_price']);
                $returnArr[$_detail['order_num']]['count'] += $tempDetail['num'];
                if($tempDetail['num'] > 0){
                    $returnArr[$_detail['order_num']]['goods'][] = $tempDetail;
                }
            }else{
                if($_detail['status']==3){
                    $returnArr[$_detail['order_num']]['total_price'] = get_format_number($_detail['price']*($tempDetail['num']-$_detail['verificNum']));

                    // 订单优惠信息
                    if($_detail['third_id']){
                        $payOrder = (new DiningOrderPayService())->getOrderDetail($_detail['third_id']);
                        if($payOrder){
                            $discountPrice += $payOrder['discount_price'];
                        }
                    }
                }else{
                    $returnArr[$_detail['order_num']]['total_price'] = get_format_number($_detail['price']*($tempDetail['num']-$_detail['verificNum']));
                }
                $returnArr[$_detail['order_num']]['number'] = $_detail['order_num'];
                $returnArr[$_detail['order_num']]['number_str'] = L_('#第X1次下单',['X1'=>$_detail['order_num']]);
                $returnArr[$_detail['order_num']]['order_num'] = $_detail['order_num'];
                $returnArr[$_detail['order_num']]['count'] = $tempDetail['num'];
                $returnArr[$_detail['order_num']]['status'] = $_detail['status'];
                $returnArr[$_detail['order_num']]['goods_note'] =  isset($goodsNote[($_detail['order_num']-1)]) ? $goodsNote[($_detail['order_num']-1)] : '';
                
                $tempDetail['num']>0 && $returnArr[$_detail['order_num']]['goods'][] = $tempDetail;
            }
        }
        
        // 合并同一次支付的
        $orderDetailFormat = [];
        foreach($returnArr as $_goods){
            if(isset($_goods['third_id']) && $_goods['third_id']){
                $index = $_goods['third_id'] + 1000;
                if(isset($orderDetailFormat[$index])){

                    $orderDetailFormat[$index]['goods_combine'][] = $_goods;
                    $orderDetailFormat[$index]['count'] += $_goods['count'];
                }else{
                    $orderDetailFormat[$index] = $_goods;
                    $orderDetailFormat[$index]['goods_combine'][] = $_goods;
                }
            }else{
                $orderDetailFormat[$_goods['order_num']] = $_goods;
                $orderDetailFormat[$_goods['order_num']]['goods_combine'][] = $_goods;
            }
        }

        $returnArr['goods_list'] = array_values($orderDetailFormat);
        $returnArr['goods_count'] = $returnArr['num'] = $goodsGount;
        $returnArr['go_pay_money'] = $goPayMoney;
        $returnArr['total_price'] = $returnArr['goods_total_price'] = get_format_number($goodsTotalPrice-$verificTotalPrice);
        $returnArr['discount_price'] = get_format_number($discountPrice);
        return $returnArr;
    }


    /**
     *获得用户可支付商品(属于当前用户的商品)
     * @param $orderId int
     * @param $user array 用户信息
     * @param $isStaff int 是否店员操作
     * @return array
     */
    public function getGoPayGoods($orderId, $user, $isStaff = 0, $isCheck = 1){
        if(empty($orderId) || (empty($user) && !$isStaff)){
            throw new \think\Exception(L_("参数错误"), 1001);
        }

        if($isStaff == 1){
            // 店员结算 包含所有未支付商品
            $where = [
                ['order_id', '=', $orderId],
                ['status', 'in', '0,1,2'],
                ['num', 'exp', Db::raw(' > refundNum')],
            ];
        }else{
            // 获得当前用户待支付商品
            $where = [
                ['order_id', '=', $orderId],
                ['user_type', '=', $user['user_type']],
                ['user_id', '=', $user['user_id']],
                ['status', 'in', '0,1,2'],
//                ['third_id', '=', '0'],
                ['num', 'exp', Db::raw(' > refundNum')],
            ];
        }
        $orderDetail = $this->getOrderDetailByCondition($where);
        if(!$orderDetail && $isCheck){
            throw new \think\Exception(L_("抱歉！您当前没有需要结算的商品，暂不可支付哦~"), 1003);
        }
        //订单套餐商品详情
        $orderPackageDetail = [];
        foreach ($orderDetail as $key => $value)
        {
            if ($value['package_id'] > 0) {
                $orderPackageDetail[] = $value;
                unset($orderDetail[$key]);
            }
        }

        $returnArr = [];
        // 组合附属商品
        $orderDetail = (new ShopGoodsService())->combinationData($orderDetail, 'spec', 0);
        // 组合套餐商品
        if (!empty($orderPackageDetail)) {
            $orderPackageDetail = (new ShopGoodsService())->combinationPackageData($orderPackageDetail, 'spec', 0);

            $orderDetail = array_merge($orderDetail, $orderPackageDetail);
        }


        // 第几次下单
        $orderNum = '0';

        // 商品总数
        $goodsGount = '0';

        // 商品总价
        $goodsTotalPrice = '0';
        
        //支付总价
        $goodsTotalPayPrice = '0';

        //可以优惠的金额
        $canDiscountsPrice = '0';
        
        //原价
        $goodsOldTotalPrice = '0';

        // 商品待支付总价
        $goPayMoney = '0';

        $goodsList = [];
        foreach($orderDetail as $_detail){
            $tempDetail = [];
            $tempDetail['goods_id'] = $_detail['goods_id'];
            $tempDetail['sort_id'] = $_detail['sort_id'];
            $tempDetail['name'] = $_detail['name'];
            $tempDetail['spec'] = trim(str_replace($_detail['spec_sub'],'',$_detail['spec']));
            $tempDetail['spec_sub'] = $_detail['spec_sub'];
            $tempDetail['sub_list'] = $_detail['sub_list'];
            $tempDetail['price'] = $_detail['discount_price'];
            $tempDetail['old_price'] = $_detail['price'];
            $tempDetail['discount_price'] = $_detail['discount_price'];
            $tempDetail['unit'] = $_detail['unit'];
            if ($_detail['package_id'] == 0) {
                $tempDetail['num'] = $_detail['num']-$_detail['refundNum'];
                $tempDetail['is_package_goods'] = false;
            } else {
                $tempDetail['num'] = $_detail['package_num']-$_detail['refundNum']-$_detail['verificNum'];
                $tempDetail['is_package_goods'] = true;
            }
            $tempDetail['uniqueness_number'] = $_detail['uniqueness_number'];
            $tempDetail['is_must'] = $_detail['is_must'];//是否必点1是2否
            $tempDetail['is_staff'] = $_detail['is_staff']; //是否店员下单1是2否
            $goodsPrice = get_format_number($_detail['discount_price'] * $tempDetail['num']);
            $oldTotalPrice = get_format_number($_detail['price'] * $tempDetail['num']);
            $tempDetail['total_price'] = $goodsPrice;//商品总价

            $tempDetail['package_id'] = $_detail['package_id'];
            $tempDetail['status'] = $_detail['status'];
            $tempDetail['is_lock'] = $_detail['is_lock'];
            $tempDetail['verificNum'] = $_detail['verificNum'];
            $tempDetail['order_num'] = $_detail['order_num'];
            $tempDetail['third_id'] = $_detail['third_id'];

            $tempDetail['total_pay_price'] = $goodsPrice;//支付总价
            $tempDetail['old_total_price'] = $oldTotalPrice;//商品总价

            if($tempDetail['num']<=0){
                continue;
            }

            if($_detail['order_num']<$orderNum || $orderNum == '0'){
                $orderNum = $_detail['order_num'];
            }
            // 商品总数
            $goodsGount += $_detail['num'];

            // 商品总价
            $goodsTotalPrice += $goodsPrice;
            $goodsTotalPayPrice += $goodsPrice;
            $goodsOldTotalPrice += $oldTotalPrice;

            if(isset($goodsList[$_detail['uniqueness_number']]) ){
                $goodsList[$_detail['uniqueness_number']]['total_price'] += $_detail['discount_price']*$tempDetail['num'];
                $goodsList[$_detail['uniqueness_number']]['old_total_price'] += $_detail['price']*$tempDetail['num'];
                $goodsList[$_detail['uniqueness_number']]['num'] += $tempDetail['num'];
            }else{
                $goodsList[$_detail['uniqueness_number']] = $tempDetail;
            }

            //可优惠的金额
            if($_detail['can_discounts']){
                $canDiscountsPrice += $goodsPrice;
            }

            $goodsList[$_detail['uniqueness_number']]['total_price'] = get_format_number($goodsList[$_detail['uniqueness_number']]['total_price']);
        }

        $returnArr['goods_list'] = array_values($goodsList);
        $returnArr['goods_count'] = $goodsGount;
        $returnArr['order_num'] = $orderNum;
        $returnArr['goods_total_price'] = get_format_number($goodsTotalPrice);
        $returnArr['go_pay_money'] = get_format_number($goodsTotalPayPrice);
        $returnArr['can_discounts_price'] = get_format_number($canDiscountsPrice);
        $returnArr['goods_old_total_price'] = get_format_number($goodsOldTotalPrice);
        return $returnArr;
    }

    /**
     *验证当前是否有待支付商品(未锁定的商品)
     * @param $payOrderId int  
     * @return array
     */
    public function getNoPayGoods($orderId, $user){
        if(empty($orderId) || empty($user)){
            throw new \think\Exception(L_("参数错误"), 1001);
        }

        // 获得没有锁定没有支付的商品
        $where = [
            ['order_id', '=', $orderId],
            ['status', 'in', '0,1,2'],
            ['is_lock', '=', '0'],
            ['user_id', '=', '0'],
//            ['third_id', '=', '0'],
//            ['is_staff', '=', '0'],
            ['num', 'exp', Db::raw(' > refundNum')],
        ];
        $orderDetail = $this->getOrderDetailByCondition($where);
        if(!$orderDetail){
            throw new \think\Exception(L_("您当前没有需要结算的商品，如需加菜，请点击下方加菜按钮即可；如您已完成就餐，无需确认即可离店~"), 1003);
        }
        
        $returnArr['totalPrice'] = $this->getCountPrice($orderDetail);
        $returnArr['goods_list'] = $orderDetail;
        return $returnArr;
    }

    /**
     * 锁定商品
     * @param $orderId int  
     * @param $user array  
     * @return array
     */
    public function lockGoods($orderId, $user){
        if(empty($orderId) || empty($user)){
            throw new \think\Exception(L_("参数错误"), 1001);
        }

        // 获得没有锁定没有支付的商品
        $where = [
            ['order_id', '=', $orderId],
            ['status', 'in', '0,1,2'],
            ['is_lock', '=', '0'],
            ['user_id', '=', '0'],
//            ['third_id', '=', '0'],
            ['num', 'exp', Db::raw(' > refundNum')],
        ];
        $orderDetail = $this->getOrderDetailByCondition($where);
        if(!$orderDetail){
            throw new \think\Exception(L_("暂无可支付商品"), 1003);
        }
        
        // 商品id
        $ids = array_column($orderDetail,'id');

        // 更新商品信息
        $where[] = ['id', 'in', implode(',',$ids)];
        $saveData = [
            'is_lock' => 1,
            'user_type' => $user['user_type'],
            'user_id' => $user['user_id'],
            'third_id' => 0
        ];
        try{
            $this->diningOrderDetailModel->where($where)->update($saveData);
            
            // 添加日志
            (new DiningOrderLogService())->addOrderLog($orderId, '16', '锁定', $user);
        }catch (\Exception $e){
            throw new \think\Exception(L_("下单失败，请稍后重试"), 1003);
        }
        return true;
    }
    
    /**
     *解锁商品
     * @param $orderId int  
     * @param $user array  
     * @return array
     */
    public function deblockingGoods($orderId, $user, $orderNum = 0, $staffUser = []){
        if(empty($orderId) || (empty($user) && !$orderNum)){
            throw new \think\Exception(L_("参数错误"), 1001);
        }

        // 获得已锁定没有支付的商品
        if($orderNum){
            $where = [
                ['order_id', '=', $orderId],
                ['status', 'in', '0,1,2'],
                ['is_lock', '=', '1'],
                ['order_num', '=', $orderNum],
            ];
        }elseif($user){
            $where = [
                ['order_id', '=', $orderId],
                ['status', 'in', '0,1,2'],
                ['is_lock', '=', '1'],
                ['user_type', '=', $user['user_type']],
                ['user_id', '=', $user['user_id']],
            ];
        }
        $orderDetail = $this->getOrderDetailByCondition($where);
        if(!$orderDetail){
            throw new \think\Exception(L_("暂无锁定商品"), 1003);
        }
        

        // 商品id
        $ids = array_column($orderDetail,'id');

        // 更新商品信息
        $where = [];
        $where[] = ['id', 'in', implode(',',$ids)];
        $saveData = [
            'is_lock' => 0,
            'user_type' => '',
            'user_id' => '0',
            'third_id' => '0',
        ];
        $this->diningOrderDetailModel->where($where)->update($saveData);
        
        // 支付订单id 
        $thirdIdArr = array_column($orderDetail, 'third_id');
        if($thirdIdArr){
            // 取消支付订单
            $thirdIdArr = array_unique($thirdIdArr);
            foreach($thirdIdArr as $k => $_id){
                if(!$_id){
                    unset($thirdIdArr['$k']);
                }
            }
            $where = [];
            $where[] = ['pay_order_id', 'in', implode(',',$thirdIdArr)];
            $data = ['is_cancel' => 1];                
            (new DiningOrderPayService())->updateByCondition($where,$data);
        }
        
        $dataLog = [];
        if($staffUser){// 保存店员信息
            $dataLog = [
                'operator_type' => 3,
                'operator_name' => $staffUser['name'] ?? '',
                'operator_id' => $staffUser['id'] ?? 0,
            ];
        }

        // 添加日志
        (new DiningOrderLogService())->addOrderLog($orderId, '19', '解锁', $user, $dataLog);
       
        return true;
    }


    /**
     * 获得商品总价
     * @param $orderDetail array
     * @return array
     */
    public function getCountPrice($orderDetail){
        if(empty($orderDetail) ){
            return '0';
        }

        //订单套餐商品详情
        $orderPackageDetail = [];
        foreach ($orderDetail as $key => $value)
        {
            if ($value['package_id'] > 0) {
                $orderPackageDetail[] = $value;
                unset($orderDetail[$key]);
            }
        }

        // 组合附属商品
        $orderDetail = (new ShopGoodsService())->combinationData($orderDetail, 'spec', 0);
        // 组合套餐商品
        if (!empty($orderPackageDetail)) {
            $orderPackageDetail = (new ShopGoodsService())->combinationPackageData($orderPackageDetail, 'spec', 0);
        }
        $totalPrice = 0;
        foreach($orderDetail as $_detail){
            $goodsPrice = get_format_number($_detail['discount_price'] * ($_detail['num']-$_detail['refundNum']));
            $totalPrice += $goodsPrice;
        }
        // 组合套餐商品
        if (!empty($orderPackageDetail)) {
            foreach($orderPackageDetail as $detail){
                $goodsPrice = get_format_number($detail['price'] * ($detail['num']-$detail['refundNum']-$detail['verificNum']));
                $totalPrice += $goodsPrice;
            }
        }
        return get_format_number($totalPrice);
    }

    /**
     * 获得商品总数
     * @param $orderDetail array
     * @return array
     */
    public function getGoodsCount($orderDetail){
        if(empty($orderDetail) ){
            return '0';
        }

        // 组合附属商品
        $orderDetail = (new ShopGoodsService())->combinationData($orderDetail, 'spec', 0);
        $total = 0;
        foreach($orderDetail as $_detail){
            $num = $_detail['num']-$_detail['refundNum'];
            $total += $num;
        }
        return get_format_number($total);
    }

    /**
     *根据订单id获取商品列表
     * @param $orderId int  
     * @return array
     */
    public function getOrderDetailByOrderId($orderId){
        if(empty($orderId)){
            return false;
        }

        try {
            $result = $this->diningOrderDetailModel->getOrderDetailByOrderId($orderId);
        } catch (\Exception $e) {
            return false;
        }
        
        return $result->toArray();
    }

    /**
     *根据订单id获取商品列表
     * @param $where int  
     * @return array
     */
    public function getOrderDetailByCondition($where){
        if(empty($where)){
            return [];
        }

        try {
            $result = $this->diningOrderDetailModel->getOrderDetailByCondition($where);
        } catch (\Exception $e) {
            return [];
        }
        
        return $result->toArray();
    }

    
    /**
     *获取当前第几次下单
     * @param $data array 
     * @return array
     */
    public function getNextOrderNum($orderId){
        if(empty($orderId)){
            return false;
        }

        $orderNum = 0;

        // 条件
        $where = [
            'order_id' => $orderId
        ];

        // 排序
        $order['id'] = 'DESC';
        $order['order_num'] = 'DESC';

        // 查询最后一条商品信息
        $order = $this->getOne($where, $order);
        if($order){
            $orderNum = (int)$order['order_num'] + 1;
        }else{
            $orderNum = 1;
        }
        
        return $orderNum;
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $id = $this->diningOrderDetailModel->insertGetId($data);
//        var_dump($this->diningOrderDetailModel->getLastSql());
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     *批量插入数据
     * @param $data array 
     * @return array
     */
    public function addAll($data){
        if(empty($data)){
            return false;
        }

        try {
            // insertAll方法添加数据成功返回添加成功的条数
            $result = $this->diningOrderDetailModel->insertAll($data);
        } catch (\Exception $e) {
            return false;
        }
        
        return $result;
    }

    /**
     * 更新数据
     * @param $data array 
     * @return array
     */
    public function updateByCondition($where, $data){
        if(empty($data) || empty($where)){
            return false;
        }

        try {
            $result = $this->diningOrderDetailModel->where($where)->update($data);
        } catch (\Exception $e) {
            return false;
        }
        
        return $result;
    }



    /**
     *获取一条数据
     * @param $where array 
     * @return array
     */
    public function getOne($where, $order = []){
        if(empty($where)){
            return false;
        }

        $result = $this->diningOrderDetailModel->getOne($where, $order);
        if(empty($result)){
            return [];
        }
//        var_dump($this->diningOrderDetailModel->getLastSql());

        return $result->toArray();
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where){
        if(empty($where)){
            return false;
        }

        try {
            $result = $this->diningOrderDetailModel->getSome($where);
        } catch (\Exception $e) {
            return false;
        }

        return $result->toArray();
    }
}