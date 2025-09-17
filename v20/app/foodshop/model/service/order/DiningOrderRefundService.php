<?php
/**
 * 餐饮订单退款service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/8/25 11:17
 */

namespace app\foodshop\model\service\order;
use app\foodshop\model\db\DiningOrderRefund as DiningOrderRefundModel;
use app\foodshop\model\service\goods\FoodshopGoodsLibraryService;
use app\foodshop\model\service\message\SmsSendService;
use app\foodshop\model\service\order_print\PrintHaddleService;
use app\foodshop\model\service\store\MerchantStoreFoodshopService as MerchantStoreFoodshopService;
use app\internal_card\model\service\InternalCardService;
use app\merchant\model\service\card\CardNewService;
use app\merchant\model\service\MerchantMoneyListService;
use app\pay\model\service\PayService;
use app\common\model\service\UserService;
use app\shop\model\service\goods\ShopGoodsService as ShopGoodsService;
use app\foodshop\model\service\package\FoodshopGoodsPackageService;
use think\facade\Db;
class DiningOrderRefundService {
    public $diningOrderModel = null;
    public $diningOrderLogService = null;
    public $orderStatusArr = null;
    public $staff = null;
    public function __construct()
    {
        $this->diningOrderRefundModel = new DiningOrderRefundModel();
        $this->diningOrderLogService = new DiningOrderLogService();
        $this->orderStatusArr = [
            "0" => "订单生成",
            "1" => "待付款",
            "2" => "待落座",
            "3" => "就餐中",
            "4" => "已完成",
            "5" => "已取消",
        ];
        $this->orderStatusArrForUser = [
            "0" => "订单生成",
            "1" => "待支付",
            "2" => "预定成功",
            "3" => "就餐中",
            "4" => "已完成",
            "5" => "已取消",
        ];
    }

    /**
     * 预订单取消订单
     * @param $param
     * @param $staff array 店员
     * @return array
     */
    public function cancelOrder($param, $staff=[])
    {
        // 操作人
        $userType = $param['user_type'] ?? '';
        if(!in_array($userType,['user','staff','system'])){
            throw new \think\Exception(L_('非法操作'), 1003);
        }

        $orderId = isset($param['order_id']) ? intval($param['order_id']) : 0;
        if(empty($orderId)){
            throw new \think\Exception(L_('缺少参数'), 1003);
        }

        // 订单详情
        $order =(new DiningOrderService())->getSimpleOrderDetail($orderId);
        if(empty($order)){
            throw new \think\Exception(L_('订单不存在'), 1003);
        }

        // 手机号
        $phone = (new DiningOrderService())->getOrderPhone($order);

        // 发送短信 数据
        $sendData = [
            'type' => 'order_cancel',
            'order' => $order,
            'phone' => $phone,
        ];

        // 取消原因
        $cancelReason = L_('店员取消订单');

        // 用户取消订单
        if($param['user_type'] == 'user'){
            $user = $staff;
            $cancelReason = L_('用户取消订单');
            if($order['user_type'] != $user['user_type'] || $order['user_id'] != $user['user_id']){
                throw new \think\Exception(L_("您没有权限取消当前订单"), 1003);
            }
        }elseif($param['user_type'] == 'system'){
            $cancelReason = L_('支付超时取消订单');
        }

        //预定中和点餐中可以取消
        if($order['status'] >= 20 && $order['status'] != 21){
            throw new \think\Exception(L_('订单不能取消了'), 1003);
        }

        // 查看预定金支付订单
        $where = [
            "order_id" => $orderId,
            "paid" => 1,
            "is_cancel" => 0,
            "order_type" => 0,
            "is_refund" => 0
        ];
        $payOrder = (new DiningOrderPayService())->getOne($where);

        $foodshop = (new MerchantStoreFoodshopService())->getStoreByStoreId($order['store_id']);

        // 至少提前多久取消订单才可退订金，单位：分钟
        $cancelTime = $foodshop['cancel_time']*60;

        // 未支付或不需退定金 直接取消
        if(empty($payOrder) || ($cancelTime > 0 && $order['book_time'] - time() < $cancelTime) ){
            if($payOrder && isset($param['can_refund']) && $param['can_refund']==1){
                throw new \think\Exception(L_('当前订单已过订金可退款时间'), 1003);
            }

            $data['status'] = 60;
            $data['cancel_reason'] = isset($param['cancel_reason']) && $param['cancel_reason'] ? $param['cancel_reason'] : $cancelReason;
            $data['cancel_time'] = time();
            $result = (new DiningOrderService())->updateByOrderId($orderId, $data);
            if($result){
                (new DiningOrderLogService())->addOrderLog($orderId, '23', '取消订单');

                // 发送短信
                if(!in_array($userType,['staff','system'])) {
                    (new SmsSendService())->sendSms($sendData);
                }
                return true;
            }else{
                throw new \think\Exception(L_('订单更新失败，请稍后重试'), 1003);
            }
        }

        // 添加退款记录
        $refundData = [];
        $refundData['order_id'] = $orderId;
        $refundData['staff_id'] = $staff['id'] ?? 0;
        $refundData['third_id'] = $payOrder['pay_order_id'];
        $refundData['reason'] = $cancelReason;
        $refundData['system_score_money'] = $payOrder['system_score_money'];
        $refundData['system_score'] = $payOrder['system_score'];
        $refundData['merchant_balance_give'] = $payOrder['merchant_balance_give'];
        $refundData['merchant_balance_pay'] = $payOrder['merchant_balance_pay'];
        $refundData['internal_card_pay_money'] = $payOrder['internal_card_pay_money'];
        $refundData['system_balance'] = $payOrder['system_balance'];
        $refundData['payment_money'] = $payOrder['pay_money'];
        $refundData['price'] = round(
            $refundData['system_score_money'] + 
            $refundData['merchant_balance_give']+
            $refundData['merchant_balance_pay']+
            $refundData['system_balance']+
            $refundData['payment_money']+
            $refundData['internal_card_pay_money']
            ,2);
        $refundId = $this->add($refundData);

        // 退款
        $refund = $this->getOne(['id' => $refundId]);
        $refundRes = $this->refundMoney($order, $refund, $staff);

        // 修改支付订单的状态
        $data = [
            'is_refund' => 1
        ];
        $where = [
            'pay_order_id' => $payOrder['pay_order_id']
        ];
        (new DiningOrderPayService())->updateByCondition($where, $data);

        // 修改订单状态
        $data = [];
        $data['status'] = 51;
        $data['cancel_reason'] = isset($param['cancel_reason']) && $param['cancel_reason'] ? $param['cancel_reason'] : $cancelReason;
        $data['cancel_time'] = time();
        $result = (new DiningOrderService())->updateByOrderId($orderId, $data);

        // 添加日志
        if($result){
            (new DiningOrderLogService())->addOrderLog($orderId, '23', $cancelReason);

            // 发送短信
            if(!in_array($userType,['staff','system'])) {
                (new SmsSendService())->sendSms($sendData);
            }
            return true;
        }else{
            throw new \think\Exception(L_('订单更新失败，请稍后重试'), 1003);
        }
    }

    /**
     * 未支付尾款超时取消订单
     * @param $param
     * @return bool
     */
    public function cancelOrderAndGoods($param)
    {
        $orderId = isset($param['order_id']) ? intval($param['order_id']) : 0;
        if(empty($orderId)){
            throw new \think\Exception(L_('缺少参数'), 1003);
        }

        // 订单详情
        $order =(new DiningOrderService())->getSimpleOrderDetail($orderId);
        if(empty($order)){
            throw new \think\Exception(L_('订单不存在'), 1003);
        }

        if($order['status'] > 50){
            throw new \think\Exception(L_('订单已取消'), 1005);
        }

        $cancelReason = $param['cancel_reason'] ?? '';
        $cancelReason = $cancelReason ? $cancelReason : L_("支付超时取消订单");

        // 查看支付订单
        $where = [
            "order_id" => $orderId,
            "paid" => 1,
            "is_cancel" => 0,
            "is_refund" => 0
        ];
        $payOrder = (new DiningOrderPayService())->getOrderListByCondition($where);
        if($payOrder){
            throw new \think\Exception(L_('订单已支付，不能取消'), 1005);
        }

        // 未支付 直接取消
        $data['status'] = 60;
        $data['cancel_reason'] = $cancelReason;
        $data['cancel_time'] = time();
        $result = (new DiningOrderService())->updateByOrderId($orderId, $data);
        if(!$result){
            throw new \think\Exception(L_('订单更新失败，请稍后重试'), 1005);
        }

        // 添加日志
        (new DiningOrderLogService())->addOrderLog($orderId, '23', $cancelReason);

        // 回滚库存
        if($order['goods_detail']){
            foreach ($order['goods_detail'] as $index => $_goods) {
                $_goods['num'] = $_goods['num']-$_goods['refundNum'];
                //更新库存
                (new FoodshopGoodsLibraryService())->updateStock($_goods,1);
            }
        }

        // 手机号
        $phone = (new DiningOrderService())->getOrderPhone($order);

        // 发送短信 数据
        $sendData = [
            'type' => 'order_cancel',
            'order' => $order,
            'phone' => $phone,
        ];

        // 发送短信
        (new SmsSendService())->sendSms($sendData);
        return true;
    }

    /**
     * 验证定金是否可以取消
     * @param $param
     * @return array
     */
    public function checkBookMoneyRefund($param)
    {
        $orderId = isset($param['order_id']) ? intval($param['order_id']) : 0;
        if(empty($orderId)){
            throw new \think\Exception(L_('缺少参数'), 1003);
        }

        // 订单详情
        $order =(new DiningOrderService())->getOrderByOrderId($orderId);
        if(empty($order)){
            throw new \think\Exception(L_('订单不存在'), 1003);
        }

        $foodshop = (new MerchantStoreFoodshopService())->getStoreByStoreId($order['store_id']);

        // 至少提前多久取消订单才可退订金，单位：分钟
        $cancelTime = $foodshop['cancel_time']*60;

        $returnArr = [];
        // 未支付或不需退定金 直接取消
        $returnArr['can_refund'] = 1;

        $returnArr['desc'] = L_('取消后将不会为您保留桌位，已支付订金将原路返回至您支付账户');
        if($cancelTime > 0 && $order['book_time'] - time() < $cancelTime){
            $returnArr['can_refund'] = 0;
            $returnArr['desc'] = L_('取消后将不会为您保留桌位，因为您已逾期且未到店就餐，已支付订金不予退还，将由商家收取');
        }

        if($order['order_from'] == 0 && $order['book_price']<=0){
            $returnArr['desc'] = L_('取消后将不会为您保留桌位');
        }

        if($order['order_from'] == 3){
            // 提前选菜
            $returnArr['desc'] = L_('是否确认取消订单，取消后不可恢复');
        }
        return $returnArr;
    }

    /**
     * 修改取消订单原因
     * @param $param
     * @param $staff array 店员
     * @return array
     */
    public function editCancelReason($param, $staff)
    {
        // 操作人
        $userType = $param['user_type'] ?? '';
        if(!in_array($userType,['user','staff'])){
            throw new \think\Exception(L_('非法操作'), 1003);
        }

        $orderId = isset($param['order_id']) ? intval($param['order_id']) : 0;
        if(empty($orderId)){
            throw new \think\Exception(L_('缺少参数'), 1003);
        }

        // 订单详情
        $order =(new DiningOrderService())->getSimpleOrderDetail($orderId);
        if(empty($order)){
            throw new \think\Exception(L_('订单不存在'), 1003);
        }

        if($order['status'] < 50){
            throw new \think\Exception(L_('当前状态不可修改取消原因'), 1003);
        }

        // 修改订单状态
        $data = [];
        $reason = $param['user_type'] == 'staff' ? L_("店员取消订单") : L_('用户取消订单');
        $data['cancel_reason'] = $param['cancel_reason'] ? $param['cancel_reason'] : $reason;
        $result = (new DiningOrderService())->updateByOrderId($orderId, $data);
        // 添加日志
        if($result !== false){
            return true;
        }else{
            throw new \think\Exception(L_('修改失败，请稍后重试'), 1003);
        }
    }

    /**
     * 取消订单(目前已付尾款或加菜的订单不能取消订单)
     * @param $param
     * @param $staff array 店员
     * @return array
     */
    public function cancelOrderOld($param, $staff)
    {
        $orderId = isset($param['order_id']) ? intval($param['order_id']) : 0;
        if(empty($orderId)){
            throw new \think\Exception(L_('缺少参数'), 1003);
        }

        // 订单详情
        $order =(new DiningOrderService())->getSimpleOrderDetail($orderId);
        if(empty($order)){
            throw new \think\Exception(L_('订单不存在'), 1003);
        }

        $order =(new DiningOrderService())->getSimpleOrderDetail($orderId);
        if($order['status'] > 50){
            throw new \think\Exception(L_('订单已取消'), 1005);
        }

        // 查看支付订单
        $where = [
            "order_id" => $orderId,
            "paid" => 1,
            "is_cancel" => 0,
            "is_refund" => 0
        ];
        $payOrder = (new DiningOrderPayService())->getOrderListByCondition($where);

        if(empty($payOrder)){
            // 未支付 直接取消
            $data['status'] = 60;
            $data['cancel_reason'] = L_("店员取消订单");
            $data['cancel_time'] = time();
            $result = (new DiningOrderService())->updateByOrderId($orderId, $data);
            if($result){
                (new DiningOrderLogService())->addOrderLog($orderId, '23', '店员取消订单');
                return true;
            }else{
                throw new \think\Exception(L_('订单更新失败，请稍后重试'), 1005);
            }
        }

        // 支付订单退款
        // TODO 区别定金
        foreach ($payOrder as $_order){
            if($_order['refund_money'] >= $_order['price']){// 已退完
                continue;
            }
            $refundData = [];
            $refundData['order_id'] = $orderId;
            $refundData['staff_id'] = $staff['id'];
            $refundData['third_id'] = $_order['pay_order_id'];
            $refundData['reason'] = L_('店员取消订单');
            $refundData['price'] = $_order['price'];

            // 获得某个订单可退款的金额详情
            $whereRefund = [
                'third_id' => $_order['pay_order_id'],
                'status' => 1
            ];
            $_order = $this->getCanRefundMoney($whereRefund);

            $refundData['system_score_money'] = $_order['system_score_money'];
            $refundData['system_score'] = $_order['system_score'];
            $refundData['merchant_balance_give'] = $_order['merchant_balance_give'];
            $refundData['merchant_balance_pay'] = $_order['merchant_balance_pay'];
            $refundData['system_balance'] = $_order['system_balance'];
            $refundData['payment_money'] = $_order['pay_money'];
            $refundData['internal_card_pay_money'] = $_order['internal_card_pay_money'];
            $refundData['price'] = round(
                $refundData['system_score_money'] + 
                $refundData['merchant_balance_give']+
                $refundData['merchant_balance_pay']+
                $refundData['system_balance']+
                $refundData['payment_money']+
                $refundData['internal_card_pay_money']
                ,2);
            $refundId = $this->add($refundData);
            $refund = $this->getOne(['id' => $refundId]);
            $refundRes = $this->refundMoney($order, $refund, $staff);
            try{
                // 修改订单状态
                $data = [
                    'is_refund' => 1
                ];
                $where = [
                    'pay_order_id' => $_order['pay_order_id']
                ];
                (new DiningOrderPayService())->updateByCondition($where, $data);

            }catch (\Exception $e){
                throw new \think\Exception($e->getMessage(), 1005);
            }
        }

        try{
            // 修改订单状态
            $data = [];
            $data['status'] = 51;
            $data['cancel_reason'] = L_("店员取消");
            $data['cancel_time'] = time();
            $result = (new DiningOrderService())->updateByOrderId($orderId, $data);
            if($result){
                (new DiningOrderLogService())->addOrderLog($orderId, '23', '店员取消订单');
                return true;
            }else{
                throw new \think\Exception(L_('订单更新失败，请稍后重试'), 1005);
            }
        }catch (\Exception $e){
            throw new \think\Exception($e->getMessage(), 1005);
        }
    }

    /**
     * 已完成的订单取消订单
     * @param $param
     * @param $user array 用户
     * @return array
     */
    public function refundOrderAll($param, $staff)
    {
        $this->staff = $staff;

        $orderId = isset($param['order_id']) ? intval($param['order_id']) : 0;
        $note = $param['note'] ?? '';


        // 订单商品详情
        $order =(new DiningOrderService())->getSimpleOrderDetail($orderId);

        if($order['store_id'] != $staff['store_id']){
            throw new \think\Exception(L_('无权操作此订单'), 1003);
        }

        if($staff['can_refund_dinging_order'] == 0){
            throw new \think\Exception(L_('没有权限'), 1003);
        }

        if(empty($order['goods_detail'])){
            throw new \think\Exception(L_('该订单没有可退商品'), 1003);
        }

        // 应退商家余额
        $refundMerchantMoney = 0;
        foreach ($order['goods_detail'] as $_goods){
            if($_goods['host_goods_id'] == 0){ // 主菜
                // 获得退菜的商品详情和退款金额
                $refundData = $this->getGoodsRefundInfo($order,[$_goods], 1);

                // 执行退款
                $refundResult = $this->goodsRefund($order, $refundData, $staff);

                // 获得应退商家余额
                $refundMerchantMoneyInfo = $this->getRefundMerchentMoney($order,$refundResult);
                $refundMerchantMoney += $refundMerchantMoneyInfo['money'];
            }
        }

        // 修改订单状态为已退款
        $saveData = [
            'status' => 51,
            'cancel_reason' => L_('店员取消订单'),
            'cancel_time' => time(),
        ];
        (new DiningOrderService())->updateByOrderId($order['order_id'],$saveData);

        // 扣除商家余额
        // 退款描述
        $desc = L_("X1商品退款",cfg('meal_alias_name'));
        (new MerchantMoneyListService())->useMoney(
            $order['mer_id'],
            $refundMerchantMoney,
            'dining',
            $desc, 
            $order['real_orderid'],
            0,
            0,
            [],
            $order['store_id']);
        return true;
    }

    /**
     * 退菜
     * @param $param 
     * @param $user array 用户
     * @return array
     */
    public function refundGoods($param, $staff)
    {
        $this->staff = $staff;

        $orderId = isset($param['order_id']) ? intval($param['order_id']) : 0;
        $detaiId = isset($param['id']) ? trim($param['id']) : 0;
        $num = $param['num'] ?? 0;

        // 订单商品详情
        $order =(new DiningOrderService())->getSimpleOrderDetail($orderId);

        $refundGoods[] = [
            'id' => $detaiId,
            'num' => $num,
        ];
        // 获得退菜的商品详情和退款金额
        $refundData = $this->getGoodsRefundInfo($order,$refundGoods);
        $refundResult = $this->goodsRefund($order, $refundData, $staff);

        if($order['status'] == 40){
            // 已完成的订单退款需要退商家余额以及更改订单状态
            // 获得应退商家余额
            $refundMerchantMoneyInfo = $this->getRefundMerchentMoney($order,$refundResult);
            $refundMerchantMoney = $refundMerchantMoneyInfo['money'];

            $order = (new DiningOrderService())->getOrderByOrderId($order['order_id']);
            if($order['goods_num'] == $order['refund_num']){
                // 商品全部退完 修改订单状态为已退款
                $saveData = [
                    'status' => 51,
                    'cancel_reason' => L_('店员取消订单'),
                    'cancel_time' => time(),
                ];
                (new DiningOrderService())->updateByOrderId($order['order_id'],$saveData);
            }

            // 扣除商家余额
            // 退款描述
            $desc = L_("X1商品退款",cfg('meal_alias_name'));
            (new MerchantMoneyListService())->useMoney(
                $order['mer_id'],
                $refundMerchantMoney,
                'dining',
                $desc, 
                $order['real_orderid'],
                0,
                0,
                [],
                $order['store_id']);
        }

        return true;
    }

    /**
     * 获得退菜的商品详情和退款金额
     * @param $param
     * @param $user array 用户
     * @return array
     */
    public function getGoodsRefundInfo($order,$goods=array(),$refundAll=false){
        if(empty($order)){
            throw new \think\Exception(L_("退款失败，订单信息不存在"), 1005);
        }

        $orderId = $order['order_id'];

        // 需要退款的金额
        $totalMoney = 0;

        // 退款的商品金额
        $goodsTotalMoney = 0;

        // 需要退款的商品数量
        $refund_nums = 0;

        // 退款的商品包含退款数量数组
        $newData = array();

        $diningOrderDetailService = new DiningOrderDetailService();
        foreach ($goods as $key => $_goods) {
            if ($refundAll) {
                // 全部退款
                if (strpos( $_goods['id'],'_') !== false) {//退套餐
                    $_goods['num'] = $_goods['package_num'] - $_goods['refundNum'];
                } else {
                    $_goods['num'] = $_goods['num'] - $_goods['refundNum'];
                }
            }

            if ($_goods['num']<=0) {
                continue;
            }


            if (strpos( $_goods['id'],'_') !== false) {//退套餐
                // 套餐
                $condition_where = [];
                $condition_where['store_id'] = $order['store_id'];
                $condition_where['order_id'] = $orderId;
                $condition_where['uniqueness_number'] = $_goods['id'];
                $detailList = $diningOrderDetailService->getSome($condition_where);
                $dataList = [];
                foreach ($detailList as $value)
                {
                    $dataList[$value['uniqueness_number']]['package_id'] = $value['package_id'];
                    $dataList[$value['uniqueness_number']]['num'] = $value['package_num'];
                    $dataList[$value['uniqueness_number']]['refundNum'] = $value['refundNum'];
                    $dataList[$value['uniqueness_number']]['status'] = $value['status'];
                    $dataList[$value['uniqueness_number']]['packages'][] = $value;
                }
                // 需要退款的商品
                $refundGoods = $dataList;
                foreach ($refundGoods as $val) {
                    $refund_num = $_goods['num'];
                    if($refund_num > $val['num']-$val['refundNum']){
                        throw new \think\Exception(L_("退菜套餐数量超出或无可退套餐"), 1005);
                    }
                    $package_detail = (new FoodshopGoodsPackageService())->getOne($param = ['id' => $val['package_id']]);
                    if (empty($package_detail)) {
                        throw new \think\Exception(L_("退菜套餐不存在"), 1005);
                    }
                    $package_price = $package_detail['price'];
                    $goodsTotalMoney += $package_price * $refund_num;

                    if($val['status'] == 3){//已支付
                        $totalMoney += $package_price * $refund_num;
                    }

                    $refund_nums += $refund_num;

                    foreach ($val['packages'] as $_val) {
                        $newData[] = array(
                            'detail_id' => $_val['id'],
                            'order_id' => $orderId,
                            'store_id' => $_val['store_id'],
                            'third_id' => $_val['third_id'],
                            'price' => $_val['price'],
                            'name' => $_val['name'],
                            'goods_id' => $_val['goods_id'],
                            'unit' => $_val['unit'],
                            'spec' => $_val['spec'],
                            'spec_id' => $_val['spec_id'],
                            'num' => $_val['num']/$value['package_num']*$refund_num,
                            'number' => $_val['number'],
                            'create_time' => time(),
                            'host_goods_id' => $_val['host_goods_id'],
                            'uniqueness_number' => $_val['uniqueness_number'],
                            'package_id' => $_val['package_id'],
                            'package_num' => $refund_num,
                        );
                    }
                }
            } else {//退菜
                // 主菜
                $condition_where = [];
                $condition_where['store_id'] = $order['store_id'];
                $condition_where['order_id'] = $orderId;
                $condition_where['id'] = $_goods['id'];
                $detail = $diningOrderDetailService->getOne($condition_where);

                // 附属菜
                $condition_where = [];
                $condition_where['store_id'] = $order['store_id'];
                $condition_where['order_id'] = $orderId;
                $condition_where['host_goods_id'] = $detail['goods_id'];
                $condition_where['uniqueness_number'] = $detail['uniqueness_number'];
                $condition_where['order_num'] = $detail['order_num'];
                $condition_where['host_id'] = $_goods['id'];
                $detailList = $diningOrderDetailService->getSome($condition_where);

                // 需要退款的商品
                if ($detailList) {
                    $refundGoods = array_merge([$detail],$detailList);
                }else{
                    $refundGoods = [$detail];
                }

                $nowTime = time();
                foreach ($refundGoods as $val) {
                    if ($val['host_goods_id']==0) {
                        $refund_num = $_goods['num'];
                    }else{
                        $refund_num = ($_goods['num']/$detail['num'])*$val['num'];
                    }

                    if($refund_num > $val['num']-$val['refundNum']){
                        throw new \think\Exception(L_("退菜数量超出或无可退商品"), 1005);
                    }

                    $goodsTotalMoney += $val['price'] * $refund_num;

                    if($val['status'] == 3){//已支付
                        $totalMoney += $val['price'] * $refund_num;
                    }

                    $refund_nums += $refund_num;

                    $newData[] = array(
                        'detail_id' => $val['id'],
                        'order_id' => $orderId,
                        'store_id' => $val['store_id'],
                        'third_id' => $val['third_id'],
                        'price' => $val['price'],
                        'name' => $val['name'],
                        'goods_id' => $val['goods_id'],
                        'unit' => $val['unit'],
                        'spec' => $val['spec'],
                        'spec_id' => $val['spec_id'],
                        'num' => $refund_num,
                        'number' => $val['number'],
                        'create_time' => time(),
                        'host_goods_id' => $val['host_goods_id'],
                        'uniqueness_number' => $val['uniqueness_number'],
                        'package_id' => $val['package_id'],
                        'package_num' => 0,
                    );
                }
            }
        }

        return ['newData'=>$newData,'totalMoney'=>$totalMoney,'goodsTotalMoney'=>$goodsTotalMoney,'refund_nums'=>$refund_nums];
    }

    /**
     * 商品退款
     * @param $order array 订单详情
     * @param $refundData array 退款商品信息
     * @param $staff array 店员信息
     * @return bool
     */
    public function goodsRefund($order,$refundData,$staff){
        if(empty($order)){
            throw new \think\Exception(L_("退款失败，订单信息不存在"), 1005);
        }
        $returnArr = [];

        $orderId = $order['order_id'];

        // 待退款信息
        $totalMoney = $refundData['totalMoney'];
        $goodsTotalMoney = $refundData['goodsTotalMoney'];
        $refund_nums = $refundData['refund_nums'];
        $newData = $refundData['newData'];
        $payOrderId = $newData[0]['third_id'] ?? 0;

        // 表前缀
        $prefix = config('database.connections.mysql.prefix');

        Db::startTrans();

        // 修改商品退款数量
        foreach ($newData as $key => $value) {
            $where = [
                'id' => $value['detail_id']
            ];
            if ($value['package_id'] == 0) {
                Db::table($prefix.'dining_order_detail')->where($where)->inc('refundNum',$value['num'])->update();
            } else {
                Db::table($prefix.'dining_order_detail')->where($where)->inc('refundNum',$value['package_num'])->update();
            }

        }

        // 修改商品退款金额
        $refundData = [
            'refund_goods_price' => get_format_number($order['refund_goods_price'] + $goodsTotalMoney)
        ];
        $refundId = Db::table($prefix.'dining_order')->where(['order_id' =>$orderId])->save($refundData);
        if($totalMoney<=0){ // 无需退款
            try {
                // 退款主表 数据
                $refundData = [];
                $refundData['order_id'] = $orderId;
                $refundData['staff_id'] = $staff['id'];
                $refundData['store_id'] = $order['store_id'];
                $refundData['third_id'] = $payOrderId;
                $refundData['reason'] = L_('店员退菜');
                $refundData['price'] = $totalMoney;
                $refundData['refund_num'] = $refund_nums;
                $refundData['status'] = 1;

                // 写入退款信息
                $refundId = Db::table($prefix.'dining_order_refund')->insertGetId($refundData);

                // 写入退款商品信息
                foreach ($newData as $tdata) {
                    $tdata['refund_id'] = $refundId;
                    Db::table($prefix.'dining_order_refund_detail')->insertGetId($tdata);
                }

                // 更新支付订单的退款金额
                $payOrder = (new DiningOrderPayService())->getOrderByOrderId($payOrderId);
                if($payOrder){
                    $where = [
                        'pay_order_id' => $payOrder['pay_order_id']
                    ];
                    $updateOrder = [];
                    $updateOrder['refund_num'] = $payOrder['refund_num'] + $refund_nums;
                    (new DiningOrderPayService())->updateByCondition($where,$updateOrder);
                }

                $diningOrder = (new DiningOrderService())->getOrderByOrderId($orderId);
                // 更新主订单退款金额
                $update_order['refund_time'] = time();
                $update_order['refund_num'] = $diningOrder['refund_num']+$refund_nums;
                (new DiningOrderService())->updateByOrderId($orderId,$update_order);

                // 打印 更新库存
                $this->afterRefund($orderId, $refundId);

                Db::commit();

                $refundData['id'] = $refundId;

                $returnArr[] = $refundData;
                return $returnArr;

            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();

                throw new \think\Exception($e->getMessage(), 1005);
            }
        }else{
            // 支付订单详情
            $payOrder = [];
            if($payOrderId){
                $payOrder = (new DiningOrderPayService())->getOrderByOrderId($payOrderId);

                //用户实际支付的钱 = 在线支付
                $customerMoney = $payOrder['total_pay_money'];
                $customerMoney = $customerMoney <= 0 ? 0 : $customerMoney;

                // 真正需要退款总额（去除优惠） 退款金额/订单总价*实际支付的钱
                $totalMoney = $payOrder['total_price']>0 ? ($totalMoney/$payOrder['total_price'])*$customerMoney : 0;

                // 退款金额大于可退余额
                if($totalMoney > ($customerMoney - $payOrder['refund_money'])){
                    $totalMoney = round($customerMoney - $payOrder['refund_money'],2);
                }
            }

            $totalMoney = get_format_number($totalMoney);

            // 退款主表 数据
            $refundData = [];
            $refundData['order_id'] = $orderId;
            $refundData['staff_id'] = $staff['id'];
            $refundData['store_id'] = $order['store_id'];
            $refundData['third_id'] = $payOrder['pay_order_id'] ?? 0;
            $refundData['reason'] = L_('店员退菜');
            $refundData['price'] = $totalMoney;
            $refundData['refund_num'] = $refund_nums;
            $refundData['status'] = 0;

            // 获得某个订单可退款的金额详情
            $whereRefund = [
                'third_id' => $payOrder['pay_order_id'] ?? 0,
                'status' => 1
            ];
            $payOrder = $this->getCanRefundMoney($whereRefund);

            // 退款顺序 平台积分抵扣 > 平台余额 > 商家会员卡在线充值余额 > 商家会员卡赠送余额 > 在线支付的其中一种（微信支付、支付宝支付、银行卡支等）。
            $payTypeMoney = $this->getPayTypeCanRefundMoney($payOrder, $totalMoney);
            $refundData = array_merge($refundData,$payTypeMoney);
            try {
                // 写入退款信息
                $refundId = Db::table($prefix.'dining_order_refund')->insertGetId($refundData);
                // 写入退款商品信息
                foreach ($newData as $tdata) {
                    $tdata['refund_id'] = $refundId;
                    Db::table($prefix.'dining_order_refund_detail')->insertGetId($tdata);
                }

                $refund = Db::table($prefix.'dining_order_refund')->find($refundId);

                // 退款
                $result = $this->refundMoney($payOrder,$refund);
                $returnArr[] = $result['refund_data'];
                // 退还订金
                if($refundData['book_money']){
                    $where = [
                        'order_id' =>$orderId,
                        'paid' => 1,
                        'order_type' => 0,
                    ];
                    $bookOrder = (new DiningOrderPayService())->getOne($where);
                    // 获得某个订单可退款的金额详情
                    $whereRefund = [
                        'third_id' => $bookOrder['pay_order_id'],
                        'status' => 1
                    ];
                    $bookOrder = $this->getCanRefundMoney($whereRefund);

                    // 退款主表 数据
                    $refundData = [];
                    $refundData['order_id'] = $orderId;
                    $refundData['staff_id'] = $staff['id'];
                    $refundData['third_id'] = $bookOrder['pay_order_id'];
                    $refundData['reason'] = L_('店员退菜');
                    $refundData['price'] = $refundData['book_money'];
                    $refundData['refund_num'] = $refund_nums;
                    $refundData['status'] = 0;
                    $payTypeMoney = $this->getPayTypeCanRefundMoney($bookOrder, $refundData['book_money']);
                    $refundData = array_merge($refundData,$payTypeMoney);
                    // 写入退款信息
                    $refundId = Db::table($prefix.'dining_order_refund')->insertGetId($refundData);
                    $bookRefund = Db::table($prefix.'dining_order_refund')->find($refundId);
                    $result = $this->refundMoney($bookOrder,$bookRefund);
                    $returnArr[] = $result['refund_data'];
                }

                Db::commit();

                return $returnArr;

            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();

                throw new \think\Exception($e->getMessage(), 1005);
            }
        }
    }

    /**
     * 获得每种支付方式应该退还的金额
     * @param $payOrder array 支付订单详情
     * @param $totalMoney float 退款金额
     * @return array
     */
    public function getPayTypeCanRefundMoney($payOrder,$totalMoney)
    {
        $refundData  = [
            'system_score_money' => 0,
            'system_score' => 0,
            'system_balance' => 0,
            'merchant_balance_pay' => 0,
            'merchant_balance_give' => 0,
            'payment_money' => 0,
            'book_money' => 0,
        ];
        // 退款顺序 平台积分抵扣 > 平台余额 > 商家会员卡在线充值余额 > 商家会员卡赠送余额 > 在线支付的其中一种（微信支付、支付宝支付、银行卡支等）。
        // 平台积分抵扣
        if($payOrder){
            if($payOrder['system_score_money'] > 0){
                if ($totalMoney > 0 && $totalMoney > $payOrder['system_score_money'] && $payOrder['system_score_money']) {
                    $refundData['system_score_money'] = $payOrder['system_score_money'];
                    $refundData['system_score'] = $payOrder['system_score'];
                    $totalMoney = $totalMoney - $payOrder['system_score_money'];
                } else {
                    $refundData['system_score_money'] = $totalMoney;
                    $refundData['system_score'] = $payOrder['system_score_money'] == 0 ? 0 : $totalMoney/$payOrder['system_score_money']*$payOrder['system_score'];
                    $totalMoney = 0;
                }
            }

            // 平台余额
            if($payOrder['system_balance'] > 0) {
                if ($totalMoney > 0 && $totalMoney > $payOrder['system_balance'] && $payOrder['system_balance']) {
                    $refundData['system_balance'] = $payOrder['system_balance'];
                    $totalMoney = $totalMoney - $payOrder['system_balance'];
                } else {
                    $refundData['system_balance'] = $totalMoney;
                    $totalMoney = 0;
                }
            }

            // 商家会员卡在线充值余额
            if($payOrder['merchant_balance_pay'] > 0) {
                if ($totalMoney > 0 && $totalMoney > $payOrder['merchant_balance_pay'] && $payOrder['merchant_balance_pay']) {
                    $refundData['merchant_balance_pay'] = $payOrder['merchant_balance_pay'];
                    $totalMoney = $totalMoney - $payOrder['merchant_balance_pay'];//减去平台余额支付的钱
                } else {
                    $refundData['merchant_balance_pay'] = $totalMoney;
                    $totalMoney = 0;
                }
            }

            // 商家会员卡赠送余额
            if($payOrder['merchant_balance_give'] > 0) {
                if ($totalMoney > 0 && $totalMoney > $payOrder['merchant_balance_give']) {
                    $refundData['merchant_balance_give'] = $payOrder['merchant_balance_give'];
                    $totalMoney = $totalMoney - $payOrder['merchant_balance_give'];
                } else {
                    $refundData['merchant_balance_give'] = $totalMoney;
                    $totalMoney = 0;
                }
            }
            // 一卡通金额
            if($payOrder['internal_card_pay_money'] > 0) {
                if ($totalMoney > 0 && $totalMoney > $payOrder['internal_card_pay_money']) {
                    $refundData['internal_card_pay_money'] = $payOrder['internal_card_pay_money'];
                    $totalMoney = $totalMoney - $payOrder['internal_card_pay_money'];
                } else {
                    $refundData['internal_card_pay_money'] = $totalMoney;
                    $totalMoney = 0;
                }
            }
            // 在线支付
            if($payOrder['pay_money'] > 0) {
                if ($totalMoney > 0 && $totalMoney > $payOrder['pay_money']) {
                    $refundData['payment_money'] = $payOrder['pay_money'];
                    $totalMoney = $totalMoney - $payOrder['pay_money'];
                } else {
                    $refundData['payment_money'] = $totalMoney;
                    $totalMoney = 0;
                }
            }

            // 订金抵扣
            if($payOrder['book_money'] > 0) {
                if ($totalMoney > 0 && $totalMoney > $payOrder['book_money']) {
                    $refundData['book_money'] = $payOrder['book_money'];
                    $totalMoney = $totalMoney - $payOrder['book_money'];
                } else {
                    $refundData['book_money'] = $totalMoney;
                    $totalMoney = 0;
                }
            }
        }
        return $refundData;
    }
    /**
     * 根据退款信息退还金额
     * @param $order array 订单详情
     * @param $refund array 退款详情
     * @return bool
     */
    public function refundMoney($payOrder,$refund = [])
    {
        // 餐饮订单
        if (empty($payOrder)) {
            throw new \think\Exception(L_('订单信息错误'), 1005);
        }
        $orderId = $payOrder['order_id'];

        // 退款信息
        if (empty($refund)) {
            throw new \think\Exception(L_('订单的退款信息错误'), 1005);
        }

        // 退款状态
        if ($refund['status'] != 0) {
            throw new \think\Exception(L_('当前的商品已退货'), 1005);
        }

        // 主订单信息
        $diningOrder = (new DiningOrderService())->getOrderByOrderId($payOrder['order_id']);

        // 退款订单
        $where = [
            'pay_order_id' => $refund['third_id']
        ];
        $payOrder = (new DiningOrderPayService())->getOne($where);
        if (empty($payOrder)) {
            throw new \think\Exception(L_('订单的退款信息错误'), 1005);
        }

        // 退款信息
        $refundMoney = $refund;

        // 积分抵扣金额
        $systemScore=0;
        $systemScoreMoney=0;
        if ($refundMoney['system_score_money']>0 ) {
            $systemScore = $refundMoney['system_score'];
            $systemScoreMoney = $refundMoney['system_score_money'];
        }

        $merchantBalancePay = 0;
        $merchantBalanceGive = 0;
        // 商家赠送余额支付
        if ($refundMoney['merchant_balance_give']>0 ) {
            $merchantBalanceGive = $refundMoney['merchant_balance_give'];
        }

        // 商家余额支付
        if ($refundMoney['merchant_balance_pay']>0 ) {
            $merchantBalancePay = $refundMoney['merchant_balance_pay'];
        }

        // 平台余额支付
        $systemBalance = 0;
        if ($refundMoney['system_balance'] > 0) {
            $systemBalance = $refundMoney['system_balance'];
        }
        //一卡通金额
        $internalCardPayMoney = $refundMoney['internal_card_pay_money'] ?: 0;
        // 在线支付金额
        $paymentMoney = $refundMoney['payment_money'];

        if ($paymentMoney > 0) {
            $payRes = (new PayService())->refund($payOrder['third_id'], $paymentMoney);
            
        }

        $alias_name = cfg('meal_alias_name');
        //平台积分退款
        if ($systemScore > 0) {
            $result = (new UserService())->addScore($payOrder['uid'], $systemScore, $alias_name . '订单退款,增加积分,订单编号' . $diningOrder['real_orderid']);
            if($result['error_code']){
                // 积分退款失败
                $systemScore = 0;
                $systemScoreMoney = 0;
            }
        }

        //商家赠送余额退款
        if ($merchantBalanceGive > 0) {
            $result = (new CardNewService())->addUserMoney($diningOrder['mer_id'], $payOrder['uid'],0, $merchantBalanceGive, 0,'',$alias_name . '订单退款,增加余额,订单编号' . $diningOrder['real_orderid']);
            if($result['error_code']){
                $merchantBalanceGive = 0;
            }
        }

        //商家余额退款
        if ($merchantBalancePay > 0) {
            $result = (new CardNewService())->addUserMoney($diningOrder['mer_id'], $payOrder['uid'],$merchantBalancePay, 0, 0,$alias_name . '订单退款,增加余额,订单编号' . $diningOrder['real_orderid']);
            if($result['error_code']){
                $merchantBalancePay = 0;
            }
        }

        //平台余额退款
        if ($systemBalance > 0) {
            $result = (new UserService())->addMoney($payOrder['uid'], $systemBalance, $alias_name . '订单退款,增加余额,订单编号' . $diningOrder['real_orderid']);
            if($result['error_code']){
                $systemBalance = 0;
            }
        }
        //一卡通退款
        if ($internalCardPayMoney) {
            try{
                $payOrder['order_type_text'] = '餐饮';
                $payOrder['order_type'] = 'dining';
                (new InternalCardService)->refund($payOrder, $internalCardPayMoney);
            }catch(\Exception $e){
                $internalCardPayMoney = 0;
            }
        }
        // 修改退款记录的信息
        $data = [];
        $data['refund_time'] = time();
        $data['status'] = 1;
        $data['system_score'] = $systemScore;
        $data['system_score_money'] = $systemScoreMoney;
        $data['merchant_balance_pay'] = $merchantBalancePay;
        $data['merchant_balance_give'] = $merchantBalanceGive;
        $data['internal_card_pay_money'] = $internalCardPayMoney;
        $data['system_balance'] = $systemBalance;
        $data['payment_money'] = $paymentMoney;
        $where = [
            'id' => $refund['id']
        ];
        $this->updateThis($where, $data);

        // 更新库存 打印退菜
        $this->afterRefund($orderId, $refund['id']);

        // 更新支付订单的退款金额
        $where = [
            'pay_order_id' => $payOrder['pay_order_id']
        ];
        $updateOrder = [];
        $updateOrder['refund_money'] = $payOrder['refund_money'] + $refundMoney['price'];
        $updateOrder['refund_num'] = $payOrder['refund_num'] + $refundMoney['refund_num'];
        (new DiningOrderPayService())->updateByCondition($where,$updateOrder);

        // 更新主订单退款金额
        $update_order['refund_time'] = time();
        $update_order['refund_money'] = $diningOrder['refund_money']+$refundMoney['price'];
        $update_order['refund_num'] = $diningOrder['refund_num']+$refundMoney['refund_num'];
        (new DiningOrderService())->updateByOrderId($orderId,$update_order);

        $returnArr['refund_data'] = array_merge($refund,$data);
        return $returnArr;
    }

    // 退菜后更新库存
    public function afterRefund($orderId, $refundId){
        // 更新库存
        $where = [
            'order_id' => $orderId,
            'refund_id' => $refundId
        ];
        $field = 'r.*,g.spec_sort_id as sort_id';
        $goodsList = (new DiningOrderRefundDetailService())->getRefundGoodList($where,$field);
        foreach ($goodsList as $index => $_goods) {
            //更新库存
            (new FoodshopGoodsLibraryService())->updateStock($_goods,1);
        }
        //套餐商品
        $packageList = [];
        foreach ($goodsList as $key => $value)
        {
            if ($value['package_id'] > 0) {
                $packageList[] = $value;
                unset($goodsList[$key]);
            }
        }

        // 组合附属商品
        $goodsList = (new ShopGoodsService())->combinationData($goodsList, 'spec', 0);
        // 组合套餐商品
        if (!empty($packageList)) {
            $packageList = (new ShopGoodsService())->combinationPackageData($packageList, 'spec', 0);
            $goodsList = array_merge($goodsList, $packageList);
        }

        // 打印退菜
        $param['order_id'] = $orderId;
        $param['is_back'] = 1;
        $param['type'] = ['menu'];//打印类型 菜单
        $param['goods_list'] = $goodsList;
        $param['staff_name'] = $this->staff['name'];
        (new PrintHaddleService())->printOrder($param);

        return true;
    }


    /**
     * 退款扣除商家余额
     * @param $param
     * @return array
     */
    public function getRefundMerchentMoney($order, $refundList)
    {
        $returnArr = [
            'money' => 0,
            'system_take' => 0,
        ];
        // 查询商家对账记录
        $where['type'] = 'dining';
        $where['order_id'] = $order['real_orderid'];
        $where['income'] = 1;
        $merchantList = (new MerchantMoneyListService())->getOne($where);

        // 退款描述
        $desc = L_("X1商品退款",cfg('meal_alias_name'));

        if(empty($merchantList) || ($merchantList['money'] <=0 && $merchantList['system_take']<=0 )){
            // 商家对账金额为0 不扣除商家余额 直接增加一条金额为0的记录
            return $returnArr;
        }

        $merchantBillInfo = (new DiningOrderService())->getMerchantBillData($order);
        $billMoneyTotal = '0';
        fdump($refundList,'getRefundMerchentMoney');
        foreach ($refundList as $refund){
            if($refund['price'] <= 0){
                continue;
            }
            // 订单支付信息
            $where = [
                'pay_order_id' => $refund['third_id']
            ];
            $payOrder = (new DiningOrderPayService())->getOne($where);

            // 商家优惠金额
            $merchantPayMoney = $payOrder['merchant_reduce']+$payOrder['merchant_coupon_price']+$payOrder['merchant_discount_money'];

            // 平优惠金额
            $platPayMoney = $payOrder['platform_plat'];
            if ($payOrder['system_coupon_id'] > 0 && $payOrder['system_coupon_price'] > 0) {
                $systemCouponService = new \app\common\model\service\coupon\SystemCouponService();
                $useSystemCoupon = $systemCouponService->getCouponByHadpullId($payOrder['system_coupon_id'],false);

                if ($useSystemCoupon) {
                    if ($useSystemCoupon['plat_money'] == 0 && $useSystemCoupon['merchant_money'] == 0) {
                        $useSystemCoupon['plat_money'] = 1;
                    }
                }
                // 平台优惠券 平台出的金额
                $systemCouponPlatMoney = $payOrder['system_coupon_price'] * ($useSystemCoupon['plat_money'] / ($useSystemCoupon['plat_money'] + $useSystemCoupon['merchant_money']));
                $platPayMoney += $systemCouponPlatMoney;
                // 商家优惠金额
                $merchantPayMoney +=  $payOrder['system_coupon_price'] - $systemCouponPlatMoney;;
            }

            // 该订单在线支付金额
            $payMoney = '0';
            if($payOrder['is_own']==0){//自有支付
                $payMoney = $payOrder['pay_money'];
            }

            // 商家联盟定制功能，会员卡余额对账到使用的联盟商家余额
            if ($payOrder['union_mer_id']) {
                $orderBillMoney = $payOrder['system_balance'] + $payMoney + $payOrder['system_score_money'];
            }else{
                $orderBillMoney = $payOrder['system_balance'] + $payMoney + $payOrder['system_score_money'] + $payOrder['merchant_balance_pay'];
            }

            //1 平台出 不抽成   0 商家出 抽成
            // 平台优惠不抽成 商家优惠抽成
            // 平台抽成基数
            $moneySystemTake = $orderBillMoney + floatval($merchantPayMoney);

            // 平台优惠参与对账
            $orderBillMoney += $platPayMoney;

            // 商品实际退款金额
            $refundMoney = $refund['price'];

            // 该商品平台优惠的金额
            $platPayMoneyGoods = $refundMoney/$payOrder['price']*$platPayMoney;
            // 该商品商家优惠的金额
            $merchantPayMoneyGoods = $refundMoney/$payOrder['price']*$merchantPayMoney;

            // 在线支付退款金额
            if($payOrder['is_own'] <= 0){
                $payMoneyGoods = $refund['payment_money'];
            }
            // 商家应该扣除的金额
            if ($payOrder['union_mer_id']) {
                $billMoneyGoods = $refund['system_balance'] + $payMoneyGoods + $refund['system_score_money'];
            }else{
                $billMoneyGoods = $refund['system_balance'] + $payMoneyGoods + $refund['system_score_money'] + $refund['merchant_balance_pay'];
            }

            // 该商品参于抽成的金额
            $moneySystemTakeGoods = $billMoneyGoods + $merchantPayMoneyGoods;

            // 该商品参于对账的金额
            $billMoneyGoods = $billMoneyGoods + $platPayMoneyGoods;

            // 该商品抽成的金额
            $moneySystemTakeGoods = $moneySystemTakeGoods/$moneySystemTake*$merchantList['system_take'];

            // 该商品应该减去商家的余额
            $refundBillMoney = $billMoneyGoods - $moneySystemTakeGoods;


            $billMoneyTotal += $refundBillMoney;
        }

        $returnArr['money'] = get_format_number($billMoneyTotal);

        return $returnArr;
    }
    /**
     *获得某个订单可退款的金额详情
     * @param $where array
     * @return array
     */
    public function getCanRefundMoney($where){
        if(empty($where)){
            return false;
        }

        $payOrder = (new DiningOrderPayService())->getOne(['pay_order_id'=>$where['third_id']]);

        $feild = 'sum(price) as price, sum(system_score_money) as system_score_money, sum(system_score) as system_score,
         sum(merchant_balance_give) as merchant_balance_give, sum(merchant_balance_pay) as merchant_balance_pay, 
         sum(system_balance) as system_balance, sum(payment_money) as pay_money, sum(book_money) as book_money, 
         sum(internal_card_pay_money) as internal_card_pay_money';
        $refundMoney = $this->getOne($where, $feild);

        if($refundMoney){
            // 积分抵扣金额
            $payOrder['system_score_money'] -= $refundMoney['system_score_money'];
            $payOrder['system_score'] -= $refundMoney['system_score'];
            $payOrder['merchant_balance_give'] -= $refundMoney['merchant_balance_give'];
            $payOrder['merchant_balance_pay'] -= $refundMoney['merchant_balance_pay'];
            $payOrder['system_balance'] -= $refundMoney['system_balance'];
            $payOrder['internal_card_pay_money'] -= $refundMoney['internal_card_pay_money'];
            $payOrder['pay_money'] -= $refundMoney['pay_money'];
            $payOrder['book_money'] -= $refundMoney['book_money'];
        }

        return $payOrder;
    }

    /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where,$field = true ){
        if(empty($where)){
            return false;
        }

        $result = $this->diningOrderRefundModel->getOne($where,$field);
        if(empty($result)){
            return [];
        }
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
            $result = $this->diningOrderRefundModel->getSome($where);
        } catch (\Exception $e) {
            return false;
        }
        return $result->toArray();
    }

    /**
     *插入数据
     * @param $data array
     * @return array
     */
    public function add($data){
        if(empty($data)){
            return false;
        }

        $data['create_time'] = time();

        $id = $this->diningOrderRefundModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->diningOrderRefundModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }
    
}