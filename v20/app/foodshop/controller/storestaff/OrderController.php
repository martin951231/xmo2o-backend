<?php
/**
 * 店员后台餐饮订单控制器
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/08/25 16:34
 */

namespace app\foodshop\controller\storestaff;
use app\foodshop\model\service\order\FoodshopCartService;
use app\foodshop\model\service\order\StaffCashService;
use app\storestaff\controller\storestaff\AuthBaseController;
use app\foodshop\model\service\order\DiningOrderService;
use app\foodshop\model\service\export\ExportService;
use app\foodshop\model\service\order\DiningOrderRefundService;
use app\group\model\service\GroupOrderService;
class OrderController extends AuthBaseController
{
    /**
     * 取消订单
     */
    public function cancelOrder()
    {
        $diningOrderRefundService = new DiningOrderRefundService();
        $param['order_id'] = $this->request->param("order_id", "", "intval");
        $param['cancel_reason'] = $this->request->param("cancel_reason", "", "trim");
        $param['can_refund'] = $this->request->param("can_refund", "", "intval");
        $param['user_type'] = 'staff';

        $result = $diningOrderRefundService->cancelOrder($param, $this->staffUser);
        try {
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }

        return api_output(0, ['msg'=>L_('取消成功')]);
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
     * 取消订单原因
     */
    public function editCancelReason()
    {
        // 订单id
        $orderId = $this->request->param("order_id", "", "intval");

        $param['order_id'] = $orderId;
        $param['cancel_reason'] = $this->request->param("cancel_reason", "", "trim");
        $param['user_type'] = 'staff';

        try {
            $result = (new DiningOrderRefundService())->editCancelReason($param,  $this->staffUser);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }

        return api_output(0,['msg' => L_('修改成功')]);

    }

    /**
     * 获得订单详情
     */
    public function OrderDetail()
    {
        $diningOrderService = new DiningOrderService();

        $param['order_id'] = $this->request->param("order_id", "0", "intval");

        try {
            $detail = $diningOrderService->getOrderDetail($param['order_id'], [], [], $this->staffUser);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        unset($detail['store'],$detail['button']);
        return api_output(0, $detail);
    }


    /**
     * 所有订单列表
     */
    public function orderList()
    {
        $diningOrderService = new DiningOrderService();
        
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        $param['page'] = $this->request->param("page", "1", "intval");
        $param['start_time'] = $this->request->param("start_time", "", "trim");
        $param['end_time'] = $this->request->param("end_time", "", "trim");
        $param['order_status'] = $this->request->param("order_status", "", "intval");

        // 订单状态：0-全部，1-进行中，2-已完成， 3-已取消
        $param['history_order_status'] = $this->request->param("history_order_status", "", "intval");

        $param['searchtype'] = $this->request->param("searchtype", "", "trim");
        $param['keyword'] = $this->request->param("keyword", "", "trim");
        $param['keywords'] = $this->request->param("keywords", "", "trim");
        $param['show_goods_detail'] = $this->request->param("show_goods_detail", "", "intval");
        $param['has_btn'] = 1;
        $param['store_id'] = $this->staffUser['store_id'];
        $param['app_type'] = $this->request->param("app_type", "", "");
        $param['staff'] = $this->staffUser;
        $param['payType'] = $this->request->param("payType", "all", "trim");
        $param['order_id'] = $this->request->param("order_id", 0, "intval");
        $list = $diningOrderService->getOrderListLimit($param);
        try {
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }

        return api_output(0, $list);
    }


    /**
     * 解锁商品
     */
    public function deblocking()
    {
        // 订单id
        $orderId = $this->request->param("order_id", "", "intval");
        $orderNum = $this->request->param("order_num", "", "intval");

        $param['orderId'] = $orderId;

        try {
            $result = (new DiningOrderService())->deblocking($orderId, [], $orderNum, $this->staffUser);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }

        return api_output(0,['msg' => L_('解锁成功')]);

    }

    /**
     * 所有订单列表
     */
    public function operateOrderList()
    {
        $diningOrderService = new DiningOrderService();

        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        $param['page'] = $this->request->param("page", "1", "intval");

        // 订单类型：1-预订单，2-堂食单，3-自取单
        $param['type'] = $this->request->param("type", "", "trim");
        // 订单状态：0-全部，1-点餐中，2-就餐中
        $param['order_status'] = $this->request->param("order_status", "", "intval");
        // 预定时间
        $param['order_time'] = $this->request->param("order_time", "", "trim");
        // 订单来源：0-全部，1-扫桌台码，2-线下开台，3-通用码
        $param['order_source'] = $this->request->param("order_source", "", "trim");
        // 搜索关键词
        $param['keywords'] = $this->request->param("keywords", "", "trim");
        $param['store_id'] = $this->staffUser['store_id'];
        $param['show_goods_detail'] = $this->request->param("show_goods_detail", "", "intval");
        $param['app_type'] = $this->request->param("app_type", "", "");

        // 获得列表
        $list = $diningOrderService->getOrderOperateOrderList($param, $this->staffUser);

        return api_output(0, $list);
    }

    /**
     * 待操作订单筛选条件
     */
    public function searchCondition()
    {
        $diningOrderService = new DiningOrderService();

        $param['store_id'] = $this->staffUser['store_id'];
        try {
            $list = $diningOrderService->getSearchCondition($param,$this->staffUser);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }

        return api_output(0, $list);
    }

    /**
     * 新订单提醒（PC端轮询）
     */
    public function orderMessage()
    {
        $diningOrderService = new DiningOrderService();

        $param['type'] = $this->request->param("type", "", "intval");
        $param['time'] = $this->request->param("time", "", "intval");
        $param['store_id'] = $this->staffUser['store_id'];

        try {
            $list = $diningOrderService->orderMessageCount($param);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }

        return api_output(0, $list);
    }

    /**
     * 修改订单状态
     */
    public function editOrderStatus()
    {
        $diningOrderService = new DiningOrderService();
        $param['order_id'] = $this->request->param("order_id", "0", "intval");
        $param['table_id'] = $this->request->param("table_id", "0", "intval");
        // 4-接单，21-确认到店
        $param['status'] = $this->request->param("status", "0", "intval");
        $param['store_id'] = $this->staffUser['store_id'];

        try {
            $res = $diningOrderService->editOrderStatus($param);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        if( $param['status'] == 4 ){
            $returnArr = [
                'msg'=>L_('接单成功')
            ];
        }else{
            $returnArr = [
                'msg'=>L_('落座成功')
            ];
        }
        return api_output(0, $returnArr);
    }

    /**
     * 店员开台
     */
    public function createOrder()
    {
        $diningOrderService = new DiningOrderService();

        $param['table_id'] = $this->request->param("table_id", "0", "intval");
        $param['book_num'] = $this->request->param("book_num", "0", "intval");

        try {
            $res = $diningOrderService->createOrderByStaff($param, $this->staffUser);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }

        return api_output(0, $res);
    }

    /**
     * 快速点餐
     */
    public function quickOrder()
    {
        $diningOrderService = new DiningOrderService();
        $param['is_temp'] = 1;
        $param['order_from'] = 5;//店员快速点餐
        $res = $diningOrderService->createOrderByStaff($param, $this->staffUser);

        return api_output(0, $res);
    }

    /**
     * 修改桌台
     */
    public function chanegeTable()
    {
        $diningOrderService = new DiningOrderService();

        $param['order_id'] = $this->request->param("order_id", "0", "intval");
        $param['table_id'] = $this->request->param("table_id", "0", "intval");

        try {
            $res = $diningOrderService->chanegeTable($param, $this->staffUser);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }

        return api_output(0, ['msg'=>L_('换台成功')]);
    }

    /**
     * 修改备注
     */
    public function editNote()
    {
        $diningOrderService = new DiningOrderService();

        $param['order_id'] = $this->request->param("order_id", "0", "intval");
        // 1-整单备注，2-每次提交订单备注，3-商品备注
        $param['type'] = $this->request->param("type", "0", "intval");
        // 第几次下单： type为2时，必传
        $param['order_num'] = $this->request->param("order_num", "0", "intval");
        // 商品ID： type为3时，必传
        $param['id'] = $this->request->param("id", "0", "intval");
        // 备注内容
        $param['note'] = $this->request->param("note", "", "trim");

        try {
            $res = $diningOrderService->editNote($param, $this->staffUser);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }

        return api_output(0, ['msg'=>L_('修改成功')]);
    }

    /**
     * 修改就餐人数
     */
    public function changePeopleNum()
    {
        $diningOrderService = new DiningOrderService();

        $param['order_id'] = $this->request->param("order_id", "0", "intval");
        $param['number'] = $this->request->param("number", "0", "intval");

        try {
            $res = $diningOrderService->changePeopleNum($param, $this->staffUser);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }

        return api_output(0, ['msg'=>L_('修改成功')]);
    }

    /**
     * 退菜
     */
    public function refundGoods()
    {
        $diningOrderRefundService = new DiningOrderRefundService();

        $param['order_id'] = $this->request->param("order_id", "0", "intval");
        $param['id'] = $this->request->param("id", "0", "trim");
        $param['num'] = $this->request->param("num", "0", "intval");
        $param['note'] = $this->request->param("note", "", "trim");

        $res = $diningOrderRefundService->refundGoods($param, $this->staffUser);


        return api_output(0, ['msg'=>L_('退菜成功')]);
    }

    /**
     * 已完成的订单取消
     */
    public function refundOrderAll()
    {
        $diningOrderRefundService = new DiningOrderRefundService();

        $param['order_id'] = $this->request->param("order_id", "0", "intval");
        $res = $diningOrderRefundService->refundOrderAll($param, $this->staffUser);

        return api_output(0, ['msg'=>L_('取消成功')]);
    }


    /**
     * 加减购物车
     */
    public function addCart()
    {
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

        $param['storeId'] = $this->staffUser['store_id'];//店铺id
        $param['orderId'] = $orderId;
        $param['tempId'] = $tempId;
        $param['product'] = $product;
        $param['uniquenessNumber'] = $uniquenessNumber;
        $param['number'] = $number;
        $param['operate_type'] = $operateType;

        try {
            $result = (new FoodshopCartService())->addCart($param, [], $this->staffUser);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }

        return api_output(0,$result);
    }

    /**
     * 确认菜品（保存购物车中的菜）
     */
    public function saveCart()
    {

        // 订单id
        $param['order_id'] = $this->request->param("order_id", "", "intval");

        // 店铺id
        $param['store_id'] = $this->staffUser['store_id'];

        // 备注
        $param['goods_note'] = $this->request->param("note", "", "trim");

        // 获得商品列表
        $result = (new FoodshopCartService())->saveCart($param, [],$this->staffUser);
        return api_output(0);
    }

    /**
     * 购物车详情
     */
    public function cartDetail()
    {
        // 订单id
        $orderId = $this->request->param("order_id", "", "intval");
        $param['orderId'] = $orderId;

        $result = (new FoodshopCartService())->getCartDetail($param, []);
        try {
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }

        return api_output(0, $result);

    }

    /**
     * 结算详情
     */
    public function cashDetail()
    {
        // 订单id
        $orderId = $this->request->param("order_id", "0", "intval");

        // 是否自取1-是0-否
        $isSelfTake = $this->request->param("is_self_take", "0", "intval");
        // 自取时间
        $selfTakeTime = $this->request->param("self_take_time", "", "intval");
        // 商家优惠券id
        $merchantCouponId = $this->request->param("merchant_coupon_id", "0", "intval");
        // 平台优惠券id
        $systemCouponId = $this->request->param("system_coupon_id", "0", "intval");
        // 是否使用平台优惠券
        $useSysCoupon = $this->request->param("use_sys_coupon", "0", "intval");
        // 是否使用商家优惠券
        $useMerCoupon = $this->request->param("use_mer_coupon", "0", "intval");

        $param = [
            'orderId' => $orderId,
            'isSelfTake' => $isSelfTake,
            'selfTakeTime' => $selfTakeTime,
            'merchantCouponId' => $merchantCouponId,
            'systemCouponId' => $systemCouponId,
            'useSysCoupon' => $useSysCoupon,
            'useMerCoupon' => $useMerCoupon,
        ];
        $param['uid'] = $this->request->param("uid", "0", "intval");
        // 不可优惠金额
        $param['no_discount_money'] = $this->request->param("no_discount_money", "0", "float");
        // 会员卡使用金额
        $param['card_money'] = $this->request->param("card_money", "0", "intval");
        // 是否使用自定义金额
        $param['use_card_money'] = $this->request->param("use_card_money", "0", "intval");
        // 店铺id
        $param['store_id'] = $this->staffUser['store_id'];

        try {
        $result = (new StaffCashService())->cashDetail($param, $this->staffUser);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }

        return api_output(0, $result);

    }

    /**
     * 去支付
     */
    public function goPay()
    {
        // 订单id
        $orderId = $this->request->param("order_id", "", "intval");
        // 商家优惠券id
        $merchantCouponId = $this->request->param("merchant_coupon_id", "", "intval");
        // 平台优惠券id
        $systemCouponId = $this->request->param("system_coupon_id", "", "intval");
        // 是否使用平台优惠券
        $useSysCoupon = $this->request->param("use_sys_coupon", "", "intval");
        // 是否使用商家优惠券
        $useMerCoupon = $this->request->param("use_mer_coupon", "", "intval");

        $param = [
            'orderId' => $orderId,
            'merchantCouponId' => $merchantCouponId,
            'systemCouponId' => $systemCouponId,
            'useSysCoupon' => $useSysCoupon,
            'useMerCoupon' => $useMerCoupon,
        ];
        $param['uid'] = $this->request->param("uid", "0", "intval");
        // 不可优惠金额
        $param['no_discount_money'] = $this->request->param("no_discount_money", "0", "float");
        // 会员卡使用金额
        $param['card_money'] = $this->request->param("card_money", "0", "float");
        $param['system_score'] = $this->request->param("system_score", "0", "float");
        $param['system_score_money'] = $this->request->param("system_score_money", "0", "float");
        $param['change_money'] = $this->request->param("change_money", "0", "float");
        $param['use_change_money'] = $this->request->param("use_change_money", "0", "intval");

        // 支付方式
        $param['pay_type'] = $this->request->param("pay_type", "", "trim");
        // 扫付款码支付的值
        $param['auth_code'] = $this->request->param("auth_code", "", "trim");
        // 还需支付金额
        $param['pay_money'] = $this->request->param("pay_money", "0", "float");
        // 线下支付方式id
        $param['offline_pay_type'] = $this->request->param("offline_pay_type", "0", "intval");
        // 余额支付的手机验证码
        $param['sms_code'] = $this->request->param("sms_code", "0", "trim");
        $param['phone'] = $this->request->param("phone", "0", "trim");
        // 店铺id
        $param['store_id'] = $this->staffUser['store_id'];
        //一卡通支付
        $param['ecard_phone'] = input('ecard_phone');
        $param['ecard_pay_money'] = input('ecard_pay_money');

        $result = (new StaffCashService())->goPay($param, $this->staffUser);

        return api_output(0, $result);

    }

    /**
     * 去支付订单轮询状态
     */
    public function orderPayLog()
    {
        $param['pay_order_id'] = $this->request->param("pay_order_id", "0", "intval");
        $param['pay_type'] = $this->request->param("pay_type", "", "trim");
        $param['order_no'] = $this->request->param("order_no", "", "trim");

        try {
            $result = (new StaffCashService())->queryScanPay($param);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
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
        $param['orderId'] = $orderId;
        try {
            $result = (new FoodshopCartService())->clearCart($param, [], $this->staffUser);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }

        return api_output(0);

    }

    /**
     * 清台即完成订单
     */
    public function completeOrder()
    {
        $diningOrderService = new DiningOrderService();

        $param['order_id'] = $this->request->param("order_id", "0", "intval");

        $diningOrderService->completeOrder($param['order_id']);

        return api_output(0, ['msg'=>L_('清台成功')]);
    }

    /**
     * 支付成功页
     */
    public function afterPay()
    {
        $param['order_id'] = $this->request->param("order_id", "0", "intval");

        try {
            $result = (new DiningOrderService())->getOrderByOrderId($param['order_id']);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        $showDetail = $result['status'] >= 40 ? 0 : 1;
        return api_output(0, ['show_detail'=>$showDetail]);

    }

    // 导出
    public function export(){

        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        $param['page'] = $this->request->param("page", "1", "intval");
        $param['start_time'] = $this->request->param("start_time", "", "trim");
        $param['end_time'] = $this->request->param("end_time", "", "trim");
        $param['order_status'] = $this->request->param("order_status", "", "intval");
        $param['searchtype'] = $this->request->param("searchtype", "", "trim");
        $param['keyword'] = $this->request->param("keyword", "", "trim");


        try {
            $result = (new ExportService())->addDiningOrderExport($param, [], [], $this->staffUser);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        return api_output(0, $result);
    }

    //核销餐饮套餐
    public function  verificationPackage()
    {
        $param['order_id'] = $this->request->param("order_id", "0", "intval");
        $param['group_pass'] = $this->request->param("group_pass", "", "trim");

        try {
            $rs = (new GroupOrderService())->packageVerification($param);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        return api_output(0, $rs);
    }
}
