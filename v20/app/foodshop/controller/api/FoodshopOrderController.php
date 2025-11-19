<?php
/**
 * 餐饮订单控制器
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/30 10:16
 */

namespace app\foodshop\controller\api;
use app\common\model\service\UploadFileService;
use app\foodshop\model\db\FoodshopOrder;
use app\foodshop\model\db\MerchantStoreFoodshop;
use app\common\model\db\Reply;
use app\common\model\db\UserBehaviorReply;
use app\foodshop\model\db\ReplyPic;
use app\foodshop\controller\api\ApiBaseController;
use app\foodshop\model\service\order\DiningOrderDetailService;
use app\foodshop\model\service\order\DiningOrderRefundService;
use app\foodshop\model\service\store\MerchantStoreFoodshopService;
use app\foodshop\model\service\order\DiningOrderService;
use app\foodshop\model\service\order\FoodshopCartService;
use app\foodshop\model\service\order\DiningOrderLogService;

class FoodshopOrderController extends ApiBaseController
{    
    /**
     * 获得店铺详情
     */
    public function storeList()
    {
        // 区域id
        $areaId = $this->request->param("area_id", "", "intval");
        // 美食分类ID
        $catID = $this->request->param("cat_id", "", "intval");
        // 排序值
        $sort = $this->request->param("sort", "", "trim");
        // 筛选
        $recommend = $this->request->param("recommend", "", "trim");
        // 关键字
        $keyword = $this->request->param("keyword", "", "trim");
        // 经度
        $long = $this->request->param("user_long", "", "trim");
        // 纬度
        $lat = $this->request->param("user_lat", "", "trim");
        // 当前页数
        $page = $this->request->param("page", "1", "intval");
        // 商圈搜索
        $areaKeyword = $this->request->param("areaKeyword", "", "trim");
        
        $param['area_id'] = $areaId;
        $param['cat_id'] = $catID;
        $param['sort'] = $sort;
        $param['recommend'] = $recommend;
        $param['keyword'] = $keyword;
        $param['long'] = $long;
        $param['lat'] = $lat;
        $param['page'] = $page;
        $param['areaKeyword'] = $areaKeyword;
        $param['merchant_wxapp'] = $this->request->param("merchant_wxapp", "0", "intval");//是否商家小程序
        $param['mer_id'] = $this->request->param("mer_id", "0", "intval");// 商家id
        $param['dining_type'] = $this->request->param("dining_type", "", "trim");// 就餐方式 selftake - 自取 inhouse - 堂食 book-预定

        try {
            // 获得店铺列表
            $storeList = (new MerchantStoreFoodshopService())->getStoreList($param, $this->userInfo);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0, $storeList);
    }

    /**
     * 保存预订单
     */
    public function bookSave()
    {
        // 店铺ID
        $storeId = $this->request->param("store_id", "", "intval");
        // 预订人数
        $bookNum = $this->request->param("book_num", "", "intval");
        // 预订日期
        $date = $this->request->param("date", "", "trim");
        // 预定时间
        $bookTime = $this->request->param("book_time", "", "trim");
        // 桌台类型
        $tableType = $this->request->param("table_type", "", "trim");
        // 姓名
        $name = $this->request->param("name", "", "trim");
        // 手机号
        $phone = $this->request->param("phone", "", "trim");
        // 手机区号
        $phoneCountryType = $this->request->param("phone_country_type", "", "trim");
        // 性别
        $sex = $this->request->param("sex", "", "trim");
        // 备注
        $note = $this->request->param("note", "", "trim");
        
        $param['storeId'] = $storeId;
        $param['bookNum'] = $bookNum;
        $param['date'] = $date;
        $param['bookTime'] = $bookTime;
        $param['tableType'] = $tableType;
        $param['name'] = $name;
        $param['phone'] = $phone;
        $param['phoneCountryType'] = $phoneCountryType;
        $param['sex'] = $sex;
        $param['note'] = $note;
        // 验证是否登陆
        if(!$this->_uid){
            return api_output_error(1002);
        }
        $param['uid'] = $this->_uid;

        $user = (new DiningOrderService())->getFormatUser($this->userInfo);
        try {
            $timeList = (new DiningOrderService())->bookSave($param,$user);
         } catch (\Exception $e) {
             return api_output_error(1003, $e->getMessage());
         }
        return api_output(0, $timeList);
    }

    /**
     * 创建订单
     */
//    public function createOrder()
//    {
//        // 店铺ID
//        $storeId = $this->request->param("store_id", "", "intval");
//        // 订单来源 0-在线预订选桌，1-桌台二维码，2-店员下单，3-在线预订选菜，4-直接选菜
//        $orderFrom = $this->request->param("order_from", "", "intval");
//        // 用餐人数
//        $bookNum = $this->request->param("book_num", "", "intval");
//        // 桌台id
//        $tableId = $this->request->param("table_id", "", "intval");
//
//
//        $param['storeId'] = $storeId;
//        $param['orderFrom'] = $orderFrom;
//        $param['bookNum'] = $bookNum;
//        $param['tableId'] = $tableId;
//
//        try {
//            $storeList = (new DiningOrderService())->createOrder($param);
//        } catch (\Exception $e) {
//            return api_output_error(1005, $e->getMessage());
//        }
//        return api_output(0, $storeList);
//    }

    /**
     * 选菜页获取订单信息没有则创建
     */
    public function getOrderInfo()
    {
        // 店铺ID
        $storeId = $this->request->param("store_id", "", "intval");
        // 订单来源 0-在线预订选桌，1-桌台二维码，2-店员下单，3-在线预订选菜，4-直接选菜
        $orderFrom = $this->request->param("order_from", "", "intval");
        // 桌台id
        $tableId = $this->request->param("table_id", "", "intval");
        // 订单id
        $orderId = $this->request->param("order_id", "", "intval");
        // 临时订单id
        $tempId = $this->request->param("temp_id", "", "intval");
        // 是否扫码
        $scan = $this->request->param("scan", "", "intval");
        
        $param['storeId'] = $storeId;
        $param['orderFrom'] = $orderFrom;
        $param['orderId'] = $orderId;
        $param['tempId'] = $tempId;
        $param['scan'] = $scan;
        $param['tableId'] = $tableId;

        if($this->userInfo){
            $user = $this->userInfo;
        }else{
            $user = $this->userTemp;
        }

        // 未获取到用户信息
        $user = (new DiningOrderService())->getFormatUser($user); 
        if(empty($user)){
            return api_output_error(1006);
        }

        $result = (new DiningOrderService())->getOrderInfo($param, $user);
           
        return api_output(0, $result);
    }

    /**
     * 选择人数页创建订单或者更新订单
     */
    public function saveBookNum()
    {
        // 店铺ID
        $storeId = $this->request->param("store_id", "", "intval");
        // 订单来源 0-在线预订选桌，1-桌台二维码，2-店员下单，3-在线预订选菜，4-直接选菜
        $orderFrom = $this->request->param("order_from", "", "intval");
        // 桌台id
        $tableId = $this->request->param("table_id", "", "intval");
        // 订单id
        $orderId = $this->request->param("order_id", "", "intval");
        // 就餐人数
        $bookNum = $this->request->param("book_num", "", "intval");
        
        $param['storeId'] = $storeId;
        $param['orderFrom'] = $orderFrom;
        $param['orderId'] = $orderId;
        $param['tableId'] = $tableId;
        $param['bookNum'] = $bookNum;

        if($this->userInfo){
            $user = $this->userInfo;
        }else{
            $user = $this->userTemp;
        }

        // 未获取到用户信息
        $user = (new DiningOrderService())->getFormatUser($user); 
        if(empty($user)){
            return api_output_error(1006);
        }

        try {
            $result = (new DiningOrderService())->saveBookNum($param, $user); 
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
           
        return api_output(0, $result);
  
    }


    /**
     * 落座接口
     */
    public function takeSeat()
    {
        // 订单id
        $orderId = $this->request->param("order_id", "", "intval");
        
        // 桌台id
        $tableId = $this->request->param("table_id", "", "intval");
        $param['orderId'] = $orderId;
        $param['tableId'] = $tableId;

        if($this->userInfo){
            $user = $this->userInfo;
        }else{
            $user = $this->userTemp;
        }

        // 未获取到用户信息
        $user = (new DiningOrderService())->getFormatUser($user); 
        if(empty($user)){
            return api_output_error(1006);
        }

        try {
            $result = (new DiningOrderService())->takeSeat($param, $user); 
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
           
        return api_output(0, $result);
  
    }


    /**
     * 购物车详情
     */
    public function cartDetail()
    {
        // 订单id
        $orderId = $this->request->param("order_id", "", "intval");
        
        // 临时订单id
        $tempId = $this->request->param("temp_id", "", "intval");
        
        $param['orderId'] = $orderId;
        $param['tempId'] = $tempId;

        if($this->userInfo){
            $user = $this->userInfo;
        }else{
            $user = $this->userTemp;
        }

        // 未获取到用户信息
        $user = (new DiningOrderService())->getFormatUser($user); 
        if(empty($user)){
            return api_output_error(1006);
        }

        $result = (new FoodshopCartService())->getCartDetail($param, $user);
        try {
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
           
        return api_output(0, $result);
  
    }

    /**
     * 购物车详情
     */
    public function addCart()
    {
        // 店铺id
        $storeId = $this->request->param("store_id", "", "intval");

        // 订单id
        $orderId = $this->request->param("order_id", "", "intval");
        
        // 临时订单id
        $tempId = $this->request->param("temp_id", "", "intval");

        // 添加的商品信息
        $product = $this->request->param("product", "", "");

        
        // 1-减菜0-加菜
        $operateType = $this->request->param("operate_type", "0", "intval");

        // 商品的唯一标识（购物车里点加号或者减号的时候传值）
        $uniquenessNumber = $this->request->param("uniqueness_number", "", "trim");

        // 修改的商品数量（购物车里点加号或者减号的时候传值）
        $number = $this->request->param("number", "", "intval");
        
        $param['storeId'] = $storeId;
        $param['orderId'] = $orderId;
        $param['tempId'] = $tempId;
        $param['product'] = $product;
        $param['uniquenessNumber'] = $uniquenessNumber;
        $param['number'] = $number;
        $param['operate_type'] = $operateType;

        if($this->userInfo){
            $user = $this->userInfo;
        }else{
            $user = $this->userTemp;
        }

        // 未获取到用户信息
        $user = (new DiningOrderService())->getFormatUser($user); 
        if(empty($user)){
            return api_output_error(1006);
        }

        $result = (new FoodshopCartService())->addCart($param, $user);
        try {
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
           
        return api_output(0,$result);
  
    }

    /**
     * 清空购物车
     */
    public function clearCart()
    {
        // 订单id
        $orderId = $this->request->param("order_id", "", "intval");
        
        // 临时订单id
        $tempId = $this->request->param("temp_id", "", "intval");

        $param['orderId'] = $orderId;
        $param['tempId'] = $tempId;

        if($this->userInfo){
            $user = $this->userInfo;
        }else{
            $user = $this->userTemp;
        }

        // 未获取到用户信息
        $user = (new DiningOrderService())->getFormatUser($user); 
        if(empty($user)){
            return api_output_error(1006);
        }

        try {
            $result = (new FoodshopCartService())->clearCart($param, $user);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
           
        return api_output(0);
  
    }
    

    

    /**
     * 菜品选好了的接口
     */
    public function changeOrder()
    {
        // 订单id
        $orderId = $this->request->param("order_id", "", "intval");
        
        // 临时订单id
        $tempId = $this->request->param("temp_id", "", "intval");

        $param['orderId'] = $orderId;
        $param['tempId'] = $tempId;
        $param['scan'] = $this->request->param("scan", "", "intval");//是否扫码落座
        $param['table_id'] = $this->request->param("table_id", "", "intval");//桌台id

        if($this->userInfo){
            $user = $this->userInfo;
        }else{
            $user = $this->userTemp;
        }

        // 未获取到用户信息
        $user = (new DiningOrderService())->getFormatUser($user); 
        if(empty($user)){
            return api_output_error(1006);
        }
        $result = (new FoodshopCartService())->confirmGoods($param, $user);

        try {
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
           
        return api_output(0, $result);
  
    }
    
    /**
    * 确认菜品（保存购物车中的菜）
    */
   public function saveCart()
   {
       
        // 订单id
        $param['order_id'] = $this->request->param("order_id", "", "intval");

        // 店铺id
        $param['store_id'] = $this->request->param("store_id", "", "intval");

        // 备注
        $param['goods_note'] = $this->request->param("goods_note", "", "trim");


        if($this->userInfo){
            $user = $this->userInfo;
        }else{
            $user = $this->userTemp;
        }
        // 未获取到用户信息
        $user = (new DiningOrderService())->getFormatUser($user); 
        if(empty($user)){
            return api_output_error(1006);
        }

       // 获得商品列表
       $result = (new FoodshopCartService())->saveCart($param, $user);

       return api_output(0);
   }

    /**
     * 获取订单日志
     */
    public function log()
    {
        // 订单id
        $orderId = $this->request->param("order_id", "", "intval");
        
        // 临时订单id
        $tempId = $this->request->param("temp_id", "", "intval");

        
        // 时间戳
        $time = $this->request->param("time", "", "intval");

        $param['orderId'] = $orderId;
        $param['tempId'] = $tempId;

        if($this->userInfo){
            $user = $this->userInfo;
        }else{
            $user = $this->userTemp;
        }

        // 未获取到用户信息
        $user = (new DiningOrderService())->getFormatUser($user); 
        if(empty($user)){
            return api_output_error(1006);
        }

        try {
            $result = (new DiningOrderLogService())->getOrderLog($param, $time, $user);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
           
        return api_output(0,$result);
  
    }

    /**
     * 验证定金是否可以取消
     */
    public function checkBookMoneyRefund()
    {
        // 订单id
        $orderId = $this->request->param("order_id", "", "intval");

        $param['order_id'] = $orderId;

        try {
            $result = (new DiningOrderRefundService())->checkBookMoneyRefund($param);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }

        return api_output(0,$result);
    }

    /**
     * 取消订单
     */
    public function cancelOrder()
    {
        // 订单id
        $orderId = $this->request->param("order_id", "", "intval");

        $param['order_id'] = $orderId;
        $param['cancel_reason'] = $this->request->param("cancel_reason", "", "trim");
        $param['can_refund'] = $this->request->param("can_refund", "", "intval");
        $param['user_type'] = 'user';

        if($this->userInfo){
            $user = $this->userInfo;
        }else{
            $user = $this->userTemp;
        }

        // 未获取到用户信息
        $user = (new DiningOrderService())->getFormatUser($user); 
        if(empty($user)){
            return api_output_error(1006);
        }

        try {
            $result = (new DiningOrderRefundService())->cancelOrder($param, $user);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
           
        return api_output(0,[],L_('取消成功'));
  
    }


    /**
     * 取消订单原因
     */
    public function editCancelReason()
    {
        // 订单id
        $orderId = $this->request->param("order_id", "", "intval");

        $param['order_id'] = $orderId;
        $param['cancel_reason'] = $this->request->param("cancel_reason", "", "trim");
        $param['user_type'] = 'user';

        if($this->userInfo){
            $user = $this->userInfo;
        }else{
            $user = $this->userTemp;
        }

        // 未获取到用户信息
        $user = (new DiningOrderService())->getFormatUser($user);
        if(empty($user)){
            return api_output_error(1006);
        }

        try {
            $result = (new DiningOrderRefundService())->editCancelReason($param, $user);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }

        return api_output(0,['msg' => L_('修改成功')]);

    }

    /**
     * 订单详情
     */
    public function detail()
    {
        // 订单id
        $orderId = $this->request->param("order_id", "", "intval");


        if($this->userInfo){
            $user = $this->userInfo;
        }else{
            $user = $this->userTemp;
        }

        // 未获取到用户信息
        $user = (new DiningOrderService())->getFormatUser($user); 
        if(empty($user)){
            return api_output_error(1006);
        }
      
        try {
            $result = (new DiningOrderService())->getOrderDetail($orderId, $user);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
           
        return api_output(0,$result);
  
    }

    /**
     * 去结算
     */
    public function goCash()
    {
        // 订单id
        $orderId = $this->request->param("order_id", "", "intval");

        $param['orderId'] = $orderId;

        if($this->userInfo){
            $user = $this->userInfo;
        }else{
            $user = $this->userTemp;
        }

        // 未获取到用户信息
        $user = (new DiningOrderService())->getFormatUser($user); 
        if(empty($user)){
            return api_output_error(1006);
        }

        try {
            $result = (new DiningOrderService())->goCash($orderId, $user); 
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
           
        return api_output(0,['msg'=>L_('去支付时会锁定该订单，锁定期间内您的好友不可支付，如需解锁可到订单详情或者联系店员解锁哦~')]);
  
    }

    /**
     * 解锁商品
     */
    public function deblocking()
    {
        // 订单id
        $orderId = $this->request->param("order_id", "", "intval");

        $param['orderId'] = $orderId;

        if($this->userInfo){
            $user = $this->userInfo;
        }else{
            $user = $this->userTemp;
        }

        // 未获取到用户信息
        $user = (new DiningOrderService())->getFormatUser($user); 
        if(empty($user)){
            return api_output_error(1006);
        }

        try {
            $result = (new DiningOrderService())->deblocking($orderId, $user); 
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
           
        return api_output(0,[],L_('解锁成功'));
  
    }

    /**
     * 结算详情
     */
    public function cashDetail()
    {
        // 订单id
        $orderId = $this->request->param("order_id", "", "intval");
        // 临时订单id
        $tempId = $this->request->param("temp_id", "", "intval");
        
        // 是否自取1-是0-否
        $isSelfTake = $this->request->param("is_self_take", "", "intval");
        // 自取时间
        $selfTakeTime = $this->request->param("self_take_time", "", "intval");
        // 商家优惠券id
        $merchantCouponId = $this->request->param("merchant_coupon_id", "", "intval");
        // 平台优惠券id
        $systemCouponId = $this->request->param("system_coupon_id", "", "intval");
        // 是否使用平台优惠券
        $useSysCoupon = $this->request->param("use_sys_coupon", "", "intval");
        // 是否使用商家优惠券
        $useMerCoupon = $this->request->param("use_mer_coupon", "", "intval");

        $param = [
            'tempId' => $tempId,
            'orderId' => $orderId,
            'isSelfTake' => $isSelfTake,
            'selfTakeTime' => $selfTakeTime,
            'merchantCouponId' => $merchantCouponId,
            'systemCouponId' => $systemCouponId,
            'useSysCoupon' => $useSysCoupon,
            'useMerCoupon' => $useMerCoupon,
        ];

        if($this->userInfo){
            $user = $this->userInfo;
        }else{
            $user = $this->userTemp;
        }

        // 未获取到用户信息
        $user = (new DiningOrderService())->getFormatUser($user); 
        if(empty($user)){
            return api_output_error(1006);
        }

        $result = (new DiningOrderService())->cashDetail($param, $user); 
        try {
            // $result = (new DiningOrderService())->cashDetail($param, $user); 
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
           
        return api_output(0,$result);
  
    }

    
    /**
     * 去支付（生成支付订跳转支付）
     */
    public function cash()
    {
        // 订单id
        $orderId = $this->request->param("order_id", "", "intval");
        // 临时订单id
        $tempId = $this->request->param("temp_id", "", "intval");
        
        // 是否自取1-是0-否
        $isSelfTake = $this->request->param("is_self_take", "", "intval");
        // 自取时间
        $selfTakeTime = $this->request->param("self_take_time", "", "");
        // 商家优惠券id
        $merchantCouponId = $this->request->param("merchant_coupon_id", "", "intval");
        // 平台优惠券id
        $systemCouponId = $this->request->param("system_coupon_id", "", "intval");
        // 是否使用平台优惠券
        $useSysCoupon = $this->request->param("use_sys_coupon", "", "intval");
        // 是否使用商家优惠券
        $useMerCoupon = $this->request->param("use_mer_coupon", "", "intval");

        $param = [
            'tempId' => $tempId,
            'orderId' => $orderId,
            'isSelfTake' => $isSelfTake,
            'selfTakeTime' => $selfTakeTime,
            'merchantCouponId' => $merchantCouponId,
            'systemCouponId' => $systemCouponId,
            'useSysCoupon' => $useSysCoupon,
            'useMerCoupon' => $useMerCoupon,
        ];

        if($this->userInfo){
            $user = $this->userInfo;
        }else{
            $user = $this->userTemp;
        }

        // 未获取到用户信息
        $user = (new DiningOrderService())->getFormatUser($user); 
        if(empty($user)){
            return api_output_error(1006);
        }

        try {
            $result = (new DiningOrderService())->cash($param, $user);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
           
        return api_output(0,$result);
  
    }

    /**
     * 支付成功页
     */
    public function takeMealsDetail()
    {
        // 订单id
        $orderId = $this->request->param("order_id", "", "intval");

        try {
            $result = (new DiningOrderService())->takeMealsDetail($orderId); 
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0,$result);
    }


    /**
     * 多人点餐订单列表
     */
    public function orderList()
    {
        if($this->userInfo){
            $user = $this->userInfo;
        }else{
            $user = $this->userTemp;
        }

        // 未获取到用户信息
        $user = (new DiningOrderService())->getFormatUser($user);
        if(empty($user)){
            return api_output_error(1006);
        }
        $param['page'] = $this->request->param("page", "", "intval");
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        try {
            $result = (new DiningOrderService())->getOrderListLimit($param, [], [], $user);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0,$result);
    }

    /**
     *删除订单
     */
    public function orderDel(){
        // 订单id
        $orderId = $this->request->param("order_id", "", "intval");
        try {
            $result = (new DiningOrderService())->orderDel($orderId);
            if($result){
                return api_output(0,[]);
            }else{
                return api_output_error(1003, "删除失败");
            }
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
    }
    /**
     * 小程序订阅消息
     */
    public function getWxappTemplate()
    {
        if($this->userInfo){
            $user = $this->userInfo;
        }else{
            $user = $this->userTemp;
        }

        // 未获取到用户信息
        $user = (new DiningOrderService())->getFormatUser($user);
        if(empty($user)){
            return api_output_error(1006);
        }

        $param['type'] = $this->request->param("type", "", "trim");
        try {
            $result = (new DiningOrderService())->getWxappTemplate($param, [], [], $user);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0,$result);
    }

     /**
     * 去结算（生成支付订跳转支付）
     */
    public function test()
    {
        // 订单id
        $orderId = $this->request->param("order_id", "", "intval");
        $payParam = [
            'pay_time' => time(),
            'paid_money' => '1',
            'paid_type' => '',
            'paid_orderid' => '123454365465656',
            'current_score_use' => '0',
            'current_score_deducte' => '0',
            'current_score_deducte' => '0',
            'current_system_balance' => '1',
            'current_merchant_balance' => '0',
            'current_merchant_give_balance' => '0',
            'current_qiye_balance' => '0',
            'is_own' => '0',
        ];

        try {
            $result = (new \app\foodshop\model\service\order\DiningOrderPayService())->afterPay(17, $payParam); 
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
           
        return api_output(0,$result);
  
    }



    /**
     * 上传图片
     * @author 张涛
     * @date 2020/06/16
     */

    public function uploadPic(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        $out['url'] =(new UploadFileService())->uploadPictures($file,'dining');
        $out['url'] = replace_file_domain($out['url']);
        $url=$url1="";
        if(!empty($out['url'])){
            $arr=explode("/", $out['url']);
            $len=count($arr)-1;
            $url=$arr[$len];
            $url1=$arr[$len-1];
        }
        $out['title'] ="/upload/dining/".$url1.'/'.$url;
        return api_output(0,$out);
    }

    /**
     * 餐饮订单评价
     * @author 张涛
     * @date 2020/06/16
     */
    public function shopFeedback()
    {
        //dump(cfg('site_url'));
        if($this->userInfo){
            $user = $this->userInfo;
        }else{
            $user = $this->userTemp;
        }
        if (empty($user)) {
            $data['msg']='请先进行登录';
            return api_output(-1,$data);
        }
        $order_id = $this->request->param("order_id", "", "intval");
        $now_order=(new DiningOrderService())->getOrderByOrderId($order_id);
        $goods_list=(new DiningOrderDetailService())->getOrderDetailByOrderId($order_id);
        if (empty($now_order)) {
            $data['msg']='当前订单不存在';
            return api_output(-1,$data);
        }
        if ($now_order['status']<30) {
            $data['msg']='当前订单未付款！无法评论';
            return api_output(-1,$data);
        }
        if ($now_order['status'] < 40) {
            $data['msg']='当前订单未消费！无法评论';
            return api_output(-1,$data);
        }
        if ($now_order['status'] == 41) {
            $data['msg']='当前订单已评论';
            return api_output(-1,$data);
        }

        $goods = [];
            $list = array();
            $goods_ids = array();
            foreach ($goods_list as $row) {
                if (!in_array($row['goods_id'], $goods_ids)) {
                    $goods_ids[] = $row['goods_id'];
                    $list[] = $row;
                    if($row['host_goods_id']==0){
                        $goods[] = [
                            'goods_id' => $row['goods_id'],
                            'name' => $row['name']
                        ];
                    }
                }
            }
            $now_order['info'] = $list;
        $arr = [
            'order_id' => $order_id,
            'info' => $goods
        ];
        return api_output(0,$arr);
    }

    /**
     * 提交评价
     * @author 张涛
     * @date 2020/06/16
     */
    public $is_weixin_browser = false;
    public $is_wxapp_browser = false;
    public $is_alipay_browser = false;
    public $is_alipayapp_browser = false;
    public $is_app_browser =   false;
    public function addComment()
    {
        if (empty($this->_uid)) {
            $this->returnCode(1, array(), L_('请先进行登录！'));
        }
        $order_id =  $this->request->param("order_id", "", "intval");
        $goods_ids =  $this->request->param("goods_ids");
        $score =  $this->request->param("store_score");
        $comment =$this->request->param("store_comment");
        //$dscore = isset($_POST['deliver_score']) ? $_POST['deliver_score'] : 5;
        //$dcomment = isset($_POST['deliver_comment']) ? htmlspecialchars(trim($_POST['deliver_comment'])) : '';
        $reply_pic = $this->request->param("reply_pics");
        $now_order=(new DiningOrderService())->getOrderByOrderId($order_id);
        $goods_list=(new DiningOrderDetailService())->getOrderDetailByOrderId($order_id);
        if (empty($now_order)) {
            $arr['msg']=L_('当前订单不存在！');
            return api_output(0,$arr);
        }
        if ($now_order['status']<30) {
            $data['msg']='当前订单未付款！无法评论';
            return api_output(-1,$data);
        }
        if ($now_order['status'] < 40) {
            $data['msg']='当前订单未消费！无法评论';
            return api_output(-1,$data);
        }
        if ($now_order['status'] == 41) {
            $data['msg']='当前订单已评论';
            return api_output(-1,$data);
        }

       // $goodsids = array();
        $goods = '';
        $pre = '';
        if (isset($now_order['info'])) {
            foreach ($now_order['info'] as $row) {
                /*if (!in_array($row['goods_id'], $goodsids)) {
                    $goodsids[] = $row['goods_id'];*/
                    if (in_array($row['goods_id'], $goods_ids)) {
                        $goods .= $pre . $row['name'];
                        $pre = '#@#';
                    }
               /* }*/
            }
        }
        $goods = '';
        $pre = '';
        //$goods_ids = array();
        foreach ($goods_list as $row) {
            /*if (!in_array($row['goods_id'], $goods_ids)) {
                $goods_ids[] = $row['goods_id'];*/
                if (in_array($row['goods_id'], $goods_ids)) {
                    $goods .= $pre . $row['name'];
                    $pre = '#@#';
                }
           /* }*/
        }
        $data_reply = [
            'parent_id' => $now_order['store_id'],
            'store_id' => $now_order['store_id'],
            'mer_id' => $now_order['mer_id'],
            'score' => $score,
            'order_type' => 4,
            'order_id' => $order_id,
            'anonymous' => 1,
            'comment' => $comment,
            'uid' => $this->_uid,
            'add_time' => $_SERVER['REQUEST_TIME'],
            'add_ip' => (new DiningOrderDetailService())->get_client_ip(0),
            'goods' => $goods,
            /*'deliver_score' => $dscore,*/
            /*'pic'=>$reply_pic ? implode(';', $reply_pic) : '',*/
            'reply_pic' => $reply_pic ? implode(';', $reply_pic) : '',
        ];
        /// $login_user=(new UserService())->getUser('',$this->_uid);
        if ($res=(new Reply())->insert_record($data_reply)) {
            if ($this->is_weixin_browser) {
                $from_type = 0;
            } elseif ($this->is_wxapp_browser) {
                $from_type = 3;
            } elseif ($this->is_app_browser && strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'android') !== false) {
                $from_type = 2;
            } elseif ($this->is_app_browser) {
                $from_type = 1;
            } else {
                $from_type = 0;
            }
            //记录下用户评论行为分析$reply_id,$uid,$content,$business_type,$comment_time,$from_type,$add_ip
            $data_be['reply_id']=$res;
            $data_be1['uid']=$data_be['uid']=$this->_uid;
            $data_be['content']=$comment;
            $data_be['business_type']=4;
            $data_be['comment_time']=time();
            $data_be['from_type']=$from_type;
            $data_be['add_ip']= (new DiningOrderDetailService())->get_client_ip(0);
            $data_be['add_time']= time();
            $where1['status']=41;
            $data_be1['order_type']=2;
            $data_be1['order_id']=$order_id;
            $data_be1['add_time']=time();
           /* (new ShopOrder())->updateOrder();*/
            (new DiningOrderService())->updateByOrderId($now_order['order_id'],$where1);
            (new FoodshopOrder())->updateByOrderId($now_order['order_id'],$where1);
            (new UserBehaviorReply())->insert_record($data_be);
            (new MerchantStoreFoodshop())->setInc_shop_reply($now_order['store_id'], $score,null);
            $pics=array();
            if(!empty($reply_pic)){
                foreach ($reply_pic as $key=>$val){
                    $data_be1['pic']=$val;
                    $pics[$key]=(new ReplyPic())->insert_record($data_be1);
                }
            }

            if(!empty($pics)){
                $data_be2['pic']=implode(',', $pics);
                (new Reply())->updateByPigcmsId($res,$data_be2);
            }

            $data['url']="";
            $data['msg']='评论成功';
            return api_output(0,$data);
        } else {
            $data['msg']='评论失败';
            return api_output(-1,$data);
        }
    }


}
