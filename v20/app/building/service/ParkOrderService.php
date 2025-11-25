<?php

namespace app\building\service;

use app\api\model\service\building\BuildingApiService;
use app\building\model\ShoppingBuildingOrder;
use app\building\model\ShoppingBuildingOrderRefund;
use app\building\model\ShoppingMallPark;
use app\building\model\ShoppingMallParkOrder;
use app\building\model\ShoppingMallParkOrderRefund;
use app\common\model\service\UserService;
use app\pay\model\service\PayService;
use Exception;
use think\facade\Db;

//停车缴费订单
class ParkOrderService
{
    private $parkModel;

    private $orderModel;

    private $refundModel;

    public function __construct()
    {
        $this->parkModel = new ShoppingMallPark();
        $this->orderModel = new ShoppingMallParkOrder();
        $this->refundModel = new ShoppingMallParkOrderRefund();
    }

    //创建订单
    public function createOrder($params)
    {
        $uid = $params['uid'] ?? 0;
        $mallId = $params['mall_id'] ?? 0;
        $parkId = $params['park_id'] ?? 0;
        $carNumber = $params['car_number'] ?? '';

        if ($uid < 1 || $mallId < 1 || $parkId < 1 || empty($carNumber)) {
            throw new \Exception('参数错误');
        }

        $park = $this->parkModel->where(['id' => $parkId, 'mall_id' => $mallId])->find();
        if (!$park) {
            throw new \Exception('停车场不存在');
        }

        //获取停车记录
        $record = (new ParkService())->getPayInfo($parkId, $carNumber);
        $record = $record['info'];

        //订单数据
        $order = [
            'order_no' => (new \app\pay\model\service\PayService())->createOrderNo(),
            'uid' =>  $uid,
            'mall_id' => $mallId,
            'record_id' => $record['id'],
            'park_id' => $parkId,
            'building_park_id' => $park->park_id,
            'merchant_id' => $park->merchant_id,
            'api_key' => $park->api_key,
            'park_name' => $park->park_name,
            'car_number' => $carNumber,
            'enter_time' => $record['enter_time'],
            'fee_original' => $record['fee_info']['fee_original'],
            'fee_coupon' => $record['fee_info']['fee_coupon'],
            'fee_real' => $record['fee_info']['fee_coupon'],
            'order_name' => sprintf("%s停车费", $carNumber),
            'create_time' => time(),
            'status' => 0,
        ];

        //楼宇下单
        $path = '/work_park/api/park/order/create';
        $businessData = [
            'merchant_id' => $order['merchant_id'],
            'api_key' => $order['api_key'],
            'id' => $order['record_id'],
            'park_id' => $order['building_park_id'],
        ];
        $data = (new BuildingApiService())->invoke($path, $businessData);
        $order['building_order_id'] = $data['id'];
        $orderId = $this->orderModel->insertGetId($order);

        return [
            'order_id' => $orderId,
            'pay_url' => get_base_url("pages/pay/check?order_id={$orderId}&order_type=shopping_mall_park")
        ];
    }


    //提供获取订单信息
    public function getOrderPayInfo($orderId)
    {
        if (empty($orderId)) {
            throw new Exception(L_("参数错误"), 1001);
        }
        $order = $this->orderModel->getOne(['order_id' => $orderId]);
        $timeRemain = 900;
        if (empty($order)) {
            throw new Exception(L_("当前订单不存在！"), 1003);
        }
        if ($order->status != ShoppingMallParkOrder::STATUS_WAIT) {
            throw new Exception(L_("订单状态已经变更，请重新下单"), 1003);
        }

        $returnArr['order_money'] = getFormatNumber($order->fee_real);
        $returnArr['paid'] = 0;
        $returnArr['order_no'] = $order->order_no;
        $returnArr['is_cancel'] = 0;
        $returnArr['time_remaining'] = $timeRemain;
        $returnArr['uid'] = $order->uid;
        $returnArr['title'] = $order->order_name;
        $returnArr['business_order_sn'] = $order->order_no;
        $returnArr['city_id'] = 0;
        $returnArr['mer_id'] = 0;
        $returnArr['store_id'] = 0;
        return $returnArr;
    }

    //获取支付成功后跳转到业务方的url
    public function getPayResultUrl($orderId)
    {
        $order = $this->orderModel->getOne(['order_id' => $orderId]);
        if (empty($order)) {
            throw new Exception(L_("当前订单不存在！"), 1003);
        }
        return ['redirect_url' => get_base_url('pages/shoppingMall/parking/paymentRecord'), 'direct' => 0];
    }

    //提供支付成功后调用的业务
    public function afterPay($orderId, $extra)
    {
        fdump_api([$orderId, $extra], 'ParkOrder/orderAfterPay', true);
        if (!$orderId) {
            throw new Exception(L_("参数错误"), 1001);
        }
        $order = $this->orderModel->getOne(['order_id' => $orderId]);
        if (!$order) {
            throw new Exception(L_("当前订单不存在！"), 1003);
        }
        if ($order->status != ShoppingMallParkOrder::STATUS_WAIT) {
            throw new Exception(L_("当前订单不是未支付状态"), 1003);
        }
        $order = $order->toArray();

        $systemBalance = $extra['current_system_balance'] ?? 0;
        $paidExtra = $extra['paid_extra'] ?? '';
        $paidMoney = $extra['paid_money'] ?? 0;
        $paidType = $extra['paid_type'] ?? '';
        $paidTime = $extra['paid_time'] ?: time();
        $scoreUseCount = $extra['current_score_use'] ?? 0;
        $scoreReduceMoney = $extra['current_score_deducte'] ?? 0;
        $payOrderno = $extra['paid_orderid'] ?? '';

        //获取购买用户信息
        $userService = new UserService();
        $buyer = $userService->getUser($order['uid'], 'uid');
        if (empty($buyer)) {
            throw new Exception(L_("购买用户不存在"), 1003);
        }

        //扣除平台余额和积分
        if ($systemBalance > 0 && $buyer['now_money'] < $systemBalance) {
            throw new Exception(L_("您的帐户余额不够此次支付"), 1003);
        }
        if ($scoreUseCount > 0 && $buyer['score_count'] < $scoreUseCount) {
            throw new \Exception('用户积分不足,当前剩余积分数量：' . $buyer['score_count']);
        }

        if ($systemBalance > 0) {
            $useResult = (new UserService())->userMoney($order['uid'], $systemBalance, L_("X1，扣除余额，订单编号X2", array("X1" => $order['order_name'], "X2" => $order['order_no'])));
            if ($useResult['error_code']) {
                throw new Exception($useResult['msg'], 1003);
            }
        }

        if ($scoreUseCount > 0) {
            $useResult = (new UserService())->userScore($order['uid'], $scoreUseCount, L_("X1，扣除X2，订单编号X3", array("X1" => $order['order_name'], "X2" => cfg('score_name'), "X3" => $order['order_no'])));
            if ($useResult['error_code']) {
                throw new \think\Exception($useResult['msg']);
            }
        }

        $scorePaidsetting = (new ShoppingMallService())->getScorePaidSetting($order['uid'], $order['mall_id'], ShoppingMallParkOrder::COST_TYPE, bcsub($order['fee_real'], $scoreReduceMoney, 2));
        $update = [
            'pay_time' => $paidTime,
            'pay_type' => $paidType,
            'status' => $this->orderModel::STATUS_PAID,
            'paid_extra' => $paidExtra,
            'money_online_pay' => $paidMoney,
            'money_system_balance' => $systemBalance,
            'score_use_count' => $scoreUseCount,
            'money_score_reduce' => $scoreReduceMoney,
            'pay_orderno' => $payOrderno,
            'score_get_count' => $scorePaidsetting['get_score_count'] ?? 0,
        ];
        //更新订单信息
        $this->orderModel->updateThis(['order_id' => $orderId], $update);
        //给用户赠送积分
        if ($scorePaidsetting['get_score_count'] > 0) {
            $remark = L_("X1，赠送X2，订单编号X3", array("X1" => $order['order_name'], "X2" => cfg('score_name'), "X3" => $order['order_no']));
            (new \app\common\model\service\UserService())->addScore($order['uid'], $scorePaidsetting['get_score_count'], $remark);
        }

        //车辆出场
        $path = '/work_park/api/park/order/pay_back';
        $businessData = [
            'merchant_id' => $order['merchant_id'],
            'api_key' => $order['api_key'],
            'order_id' => $order['building_order_id'],
            'pay_money' => $order['fee_real'],
            'pay_time' => date('Y-m-d H:i:s', $paidTime),
            'third_order_id' => $order['order_no'],
        ];
        (new BuildingApiService())->invoke($path, $businessData);
        return true;
    }


    //获取积分使用设置
    public function getScoreSetting($orderId)
    {
        $order = $this->orderModel->getOne(['order_id' => $orderId]);
        if (!$order) {
            throw new Exception(L_("当前订单不存在！"), 1003);
        }

        $scoreSetting = (new ShoppingMallService())->getScoreUseSetting($order['uid'],$order['mall_id'],ShoppingMallParkOrder::COST_TYPE,$order['fee_real']);
        $rs = [
            'max_use_score' => $scoreSetting['score_use_count'] ?? 0,   //最大使用量
            'count_per_yuan' => cfg('user_score_use_percent')   //多少积分抵扣1元
        ];
        return $rs;
    }

    //获取停车记录
    public function getOrderList($uid, $page, $pageSize)
    {
        $where = [
            ['uid', '=', $uid],
            ['status', 'in', [ShoppingMallParkOrder::STATUS_PAID, ShoppingMallParkOrder::STATUS_REFUND]],
        ];
        $orderList = $this->orderModel
            ->where($where)
            ->field('order_id,order_name,park_name,fee_original,fee_real,pay_time,create_time,enter_time,status')
            ->order('order_id desc')
            ->paginate(['list_rows' => $pageSize, 'page' => $page])
            ->each(function ($item) {
                $item['park_duration'] = '停车时长' . formate_park_duration($item->create_time - strtotime($item->enter_time));
                $item['pay_time'] = date('Y-m-d H:i:s', $item->pay_time);
                return $item;
            });
        return $orderList;
    }

    //停车订单详情
    public function getOrderDetail($uid, $orderId)
    {
        $where = [
            ['uid', '=', $uid],
            ['order_id', '=', $orderId],
            ['status', 'in', [ShoppingMallParkOrder::STATUS_PAID, ShoppingMallParkOrder::STATUS_REFUND]],
        ];
        $order = $this->orderModel->where($where)->findOrEmpty()->toArray();
        if (!$order) {
            throw new Exception(L_("当前订单不存在！"), 1003);
        }
        $order['park_duration'] = formate_park_duration($order['create_time'] - strtotime($order['enter_time']));
        $order['pay_time'] = date('Y-m-d H:i:s', $order['pay_time']);
        return $order;
    }

    //订单退款
    public function orderRefund($params)
    {
        $orderId = $params['order_id'];
        $amount = $params['amount'];
        $reason = $params['reason'];

        if ($amount <= 0) {
            throw new Exception('退款金额必须大于0');
        }
        if (!$reason) {
            throw new Exception('请输入退款原因');
        }

        $order = $this->orderModel->getOne([['order_id', '=', $orderId], ['status', 'in', [ShoppingMallParkOrder::STATUS_PAID, ShoppingMallParkOrder::STATUS_REFUND]]]);
        if (!$order) {
            throw new Exception(L_("订单支付记录不存在！"), 1003);
        }
        if ($order->status == ShoppingMallParkOrder::STATUS_REFUND) {
            throw new Exception('当前订单已全额退款');
        }
        $order = $order->toArray();

        $leftMoney = bcsub($order['fee_real'], $order['refund_money'], 2);
        if (bccomp($amount, $leftMoney, 2) == 1) {
            throw new Exception('退款金额不足，当前未退款金额为' . $leftMoney);
        }

        //计算已退款金额
        $refundFields = '
                SUM(amount) AS refund_amount,
                SUM(refund_balance_money) AS refund_balance_money_total,
                SUM(refund_score_money) AS refund_score_money_total,
                SUM(refund_online_money) AS refund_online_money_total,
                SUM(refund_score_count) AS refund_score_count_total,
                SUM(back_score_get_count) AS back_score_get_count_total
                ';
        $refundTotal = $this->refundModel->where('order_id', $order['order_id'])->field($refundFields)->findOrEmpty()->toArray();

        $leftBalanceMoney = bcsub($order['money_system_balance'], $refundTotal['refund_balance_money_total'], 2);
        $leftScoreMoney = bcsub($order['money_score_reduce'], $refundTotal['refund_score_money_total'], 2);
        $leftOnlineMoney = bcsub($order['money_online_pay'], $refundTotal['refund_online_money_total'], 2);
        $leftScoreCount = bcsub($order['score_use_count'], $refundTotal['refund_score_count_total'], 2);
        $leftBackScoreCount = bcsub($order['score_get_count'], $refundTotal['back_score_get_count_total'], 2);

        $backOnlineBalance = 0;
        $backBalanceMoney = 0;
        $backScoreMoney  = 0;
        $backScoreCount = 0;
        $isAllRefund = false;
        //计算在线、平台余额、积分各自退款金额,优先级：在线支付 > 平台余额 > 积分
        if (bccomp($leftMoney, $amount, 2) == 0) {
            $backOnlineBalance = $leftOnlineMoney;  //在线支付退还部分
            $backBalanceMoney = $leftBalanceMoney;  //平台余额退还部分
            $backScoreMoney = $leftScoreMoney;  //积分金额退还部分
            $backScoreCount = $leftScoreCount;   //积分退还部分
            $isAllRefund = true;
        } else {

            $leftApplyRefundAmount = $amount;
            //1.先计算在线支付金额
            if (bccomp($leftApplyRefundAmount, $leftOnlineMoney, 2) == 1) {
                $backOnlineBalance = $leftOnlineMoney;
                $leftApplyRefundAmount = bcsub($leftApplyRefundAmount, $backOnlineBalance, 2);
            } else {
                $backOnlineBalance = $leftApplyRefundAmount;
                $leftApplyRefundAmount = 0;
            }
            //2.再计算平台余额金额
            if ($leftApplyRefundAmount > 0 && bccomp($leftApplyRefundAmount, $leftBalanceMoney, 2) == 1) {
                $backBalanceMoney = $leftBalanceMoney;
                $leftApplyRefundAmount = bcsub($leftApplyRefundAmount, $backBalanceMoney, 2);
            } else {
                $backBalanceMoney = $leftApplyRefundAmount;
                $leftApplyRefundAmount = 0;
            }
            //3.最后计算积分金额
            if ($leftApplyRefundAmount > 0) {
                $backScoreMoney = $leftApplyRefundAmount;
                $backScoreCount = bcmul($leftScoreCount, $leftApplyRefundAmount / $leftScoreMoney, 2);
            }
        }

        //扣除赠送的积分
        $giveScoreBack = 0;
        if ($order['score_get_count'] > 0) {
            $giveScoreRemark = $order['order_name'] . "退款,扣除赠送积分。订单号：" . $order['order_no'];
            if ($isAllRefund) {
                $giveScoreBack = $leftBackScoreCount;
            } else {
                $giveScoreBackPercent = bcdiv(($backOnlineBalance + $backBalanceMoney), ($order['fee_real'] - $order['money_score_reduce']), 2);
                $giveScoreBack = bcmul($order['score_get_count'], $giveScoreBackPercent, 2);
            }
        }

        Db::startTrans();
        try {
            //记录退款记录
            $refundOrderData = [
                'order_id' => $orderId,
                'amount' => $amount,
                'reason' => $reason,
                'refund_balance_money' => $backBalanceMoney,
                'refund_score_money' => $backScoreMoney,
                'refund_score_count' => $backScoreCount,
                'refund_online_money' => $backOnlineBalance,
                'back_score_get_count' => $giveScoreBack,
                'refund_time' => time(),
            ];
            $refundId = $this->refundModel->insertGetId($refundOrderData);

            //更新订单退款金额
            $updateData = [
                'refund_money' => $order['refund_money'] + $amount,
                'refund_time' => time(),
            ];
            $isAllRefund && $updateData['status'] = ShoppingMallParkOrder::STATUS_REFUND;
            $this->orderModel->where('order_id', $orderId)->update($updateData);

            //执行退款
            $remark = $order['order_name'] . "退款。订单号：" . $order['order_no'];
            if ($backOnlineBalance > 0) {
                (new PayService())->refund($order['pay_orderno'], $backOnlineBalance);
            }
            if ($backBalanceMoney > 0) {
                (new UserService())->addMoney($order['uid'], $backBalanceMoney, $remark);
            }
            if ($backScoreMoney > 0) {
                (new UserService())->addScore($order['uid'], $backScoreCount, $remark);
            }

            //退还支付成功赠送的积分
            if ($giveScoreBack > 0) {
                (new UserService())->userScore($order['uid'], $giveScoreBack, $giveScoreRemark);
            }

            //推送结果给楼宇系统
            (new BuildingApiService())->invoke('/work_park/api/park/order/refund', [
                'order_id' => $order['building_order_id'],
                'refund_money' => $amount,
                'refund_reason' => $reason,
                'merchant_id' => $order['merchant_id'],
                'api_key' => $order['api_key'],
            ]);
            Db::commit();

            return ['refund_id' => $refundId];
        } catch (\Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }
    }
}
