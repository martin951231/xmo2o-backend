<?php

namespace app\building\service;

use app\building\model\ShoppingBuildingOrder;
use app\building\model\ShoppingBuildingOrderRefund;
use app\common\model\service\UserService;
use app\pay\model\service\PayService;
use Exception;
use think\facade\Db;

//楼宇订单（已废弃）
class OrderService
{
    private $orderModel;

    private $refundModel;

    public function __construct()
    {
        $this->orderModel = new ShoppingBuildingOrder();
        $this->refundModel = new ShoppingBuildingOrderRefund();
    }

    //创建订单
    public function createOrder($params)
    {
        $rules = [
            'phone' => ['mobile'],
            'order_name' => ['require'],
            'order_no' => ['require'],
            'money_total' => ['require', 'gt' => 0],
            'use_system_balance' => ['require', 'in' => '0,1'],
            'use_user_score' => ['require', 'in' => '0,1'],
            'return_url' => ['require', 'url'],
            'notify_url' => ['require', 'url'],
        ];

        if (validate($rules)->check($params)) {
            $phone = $params['phone'] ?? '';
            $orderName = $params['order_name'] ?? '';
            $orderNo = $params['order_no'] ?? '';
            $moneyTotal = $params['money_total'] ?? 0;
            $useSystemBalance = intval($params['use_system_balance'] ?? 0);
            $useUserScore = intval($params['use_user_score'] ?? 0);
            $getScoreSetting = (isset($params['get_score_setting']) && !empty($params['get_score_setting'])) ? $params['get_score_setting'] : [];
            $useScoreSetting = (isset($params['use_score_setting']) && !empty($params['use_score_setting'])) ? $params['use_score_setting'] : [];

            $oldOrder = $this->orderModel->where('order_no', $orderNo)->find();
            if ($oldOrder) {
                throw new \Exception('订单已存在，请勿重复生成');
            }

            $order = [
                'uid' =>  0,
                'phone' => $phone,
                'order_name' => $orderName,
                'order_no' =>   $orderNo,
                'money_total' => $moneyTotal,
                'use_system_balance' => $useSystemBalance,
                'use_user_score' => $useUserScore,
                'get_score_setting' => json_encode($getScoreSetting, JSON_UNESCAPED_UNICODE),
                'use_score_setting' => json_encode($useScoreSetting, JSON_UNESCAPED_UNICODE),
                'create_time' => time(),
                'status' => 0,
                'return_url' => $params['return_url'],
                'notify_url' => $params['notify_url'],
                'attach' => $params['attach'] ?? '',
            ];
            $orderId = $this->orderModel->insertGetId($order);
            return [
                'order_id' => $orderId,
                'pay_url' => get_base_url("pages/pay/check?order_id={$orderId}&order_type=building")
            ];
        } else {
            throw new \Exception('参数错误');
        }
    }

    //获取积分使用设置
    public function getScoreSetting($orderId)
    {
        $order = $this->orderModel->getOne(['order_id' => $orderId]);
        if (!$order) {
            throw new Exception(L_("当前订单不存在！"), 1003);
        }

        $useScoreSetting = json_decode($order->use_score_setting, true);

        $rs = [
            'max_use_score' => $useScoreSetting['max_use_score'] ?? 0,   //最大使用量
            'count_per_yuan' => $useScoreSetting['count_per_yuan'] ?? 0   //多少积分抵扣1元
        ];
        return $rs;
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
        if ($order->status != ShoppingBuildingOrder::STATUS_WAIT) {
            throw new Exception(L_("订单状态已经变更，请重新下单"), 1003);
        }

        $returnArr['order_money'] = getFormatNumber($order->money_total);
        $returnArr['paid'] = 0;
        $returnArr['order_no'] = $order->order_no;
        $returnArr['is_cancel'] = 0;
        $returnArr['time_remaining'] = $timeRemain;
        $returnArr['uid'] = $order->uid;
        $returnArr['title'] = $order->order_name;
        $returnArr['business_order_sn'] = $order->order_no;
        $returnArr['use_system_balance'] = $order->use_system_balance;
        $returnArr['use_score'] = $order->use_user_score;
        $returnArr['use_score_rule'] = json_decode($order->use_score_rule, true);
        $returnArr['store_id'] = 0;
        $returnArr['city_id'] = 0;
        $returnArr['mer_id'] = 0;
        return $returnArr;
    }

    //获取支付成功后跳转到业务方的url
    public function getPayResultUrl($orderId)
    {
        $order = $this->orderModel->getOne(['order_id' => $orderId]);
        if (empty($order)) {
            throw new Exception(L_("当前订单不存在！"), 1003);
        }
        return $order->return_url;
    }

    //提供支付成功后调用的业务
    public function afterPay($orderId, $extra)
    {
        fdump_api([$orderId, $extra], 'building/orderAfterPay', true);
        if (!$orderId) {
            throw new Exception(L_("参数错误"), 1001);
        }
        $order = $this->orderModel->getOne(['order_id' => $orderId]);
        if (!$order) {
            throw new Exception(L_("当前订单不存在！"), 1003);
        }
        if ($order->status != ShoppingBuildingOrder::STATUS_WAIT) {
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
        ];
        $this->orderModel->updateThis(['order_id' => $orderId], $update);


        //给用户赠送积分
        $getScoreSetting = $order['get_score_setting'] ? json_decode($order['get_score_setting'], true) : [];
        $scoreGetCount = 0;
        if ($getScoreSetting) {
            $scoreGetSettingPerYuan = $getScoreSetting['count_per_yuan'] ?? 0;
            $baseOn = $getScoreSetting['base_on'] ?? '';
            if ($scoreGetSettingPerYuan > 0 && $baseOn) {
                switch ($baseOn) {
                    case 'total':  //订单总价（在线支付+积分抵扣+平台余额抵扣）
                        $scoreGetCount = $order['money_total'] * $scoreGetSettingPerYuan;
                        break;
                    case 'total_without_score': //在线支付+平台余额抵扣
                        $scoreGetCount = ($paidMoney + $systemBalance) * $scoreGetSettingPerYuan;
                        break;
                    default:
                        break;
                }

                if ($scoreGetCount > 0) {
                    $remark = L_("X1，支付获得X2，订单编号X3", array("X1" => $order['order_name'], "X2" => cfg('score_name'), "X3" => $order['order_no']));
                    (new UserService())->addScore($order['uid'], $scoreGetCount, $remark);
                }
            }
        }

        //TODO  异步通知楼宇订单状态
        if ($order['notify_url']) {
            $pushData = [
                'order_id' => $orderId,
                'order_no' => $order['order_no'],
                'money_online_pay' => $update['money_online_pay'],
                'money_system_balance' => $update['money_system_balance'],
                'money_score_reduce' => $update['money_score_reduce'],
                'score_use_count' => $update['score_use_count'],
                'score_get_count' => $scoreGetCount,
                'pay_time' => $update['pay_time'],
                'pay_type' => $update['pay_type'],
                'paid_extra' => $update['paid_extra'],
            ];
            $order['attach'] && $pushData['attach'] = $order['attach'];
            building_request_post($order['notify_url'], $pushData);
        }
        return true;
    }


    //查询订单
    public function queryOrder($params)
    {
        $orderId = $params['order_id'] ?? '';
        $orderNo = $params['order_no'] ?? '';

        if ($orderId) {
            $where = [['order_id', '=', $orderId]];
        } else if ($orderNo) {
            $where = [['order_no', '=', $orderNo]];
        } else {
            throw new \Exception('参数错误');
        }

        $order = $this->orderModel->where($where)->withOutField('uid')->findOrEmpty()->toArray();
        if (empty($order)) {
            throw new \Exception('订单不存在');
        }

        $order['use_score_setting'] = $order['use_score_setting'] ? json_decode($order['use_score_setting'], true) : [];
        $order['get_score_setting'] = $order['get_score_setting'] ? json_decode($order['get_score_setting'], true) : [];
        $order['refund_list'] = $this->queryRefundByOrderid($order['order_id']);
        return $order;
    }

    //订单退款
    public function refundOrder($params)
    {
        if (empty($params['order_no']) && empty($params['order_id'])) {
            throw new \Exception('order_no与order_id不能同时为空');
        }

        $rules = [
            'refund_no' => ['require'],
            'reason' => ['require'],
            'amount' => ['require', 'gt' => 0],
            'notify_url' => ['require', 'url'],
        ];

        if (validate($rules)->check($params)) {
            //执行退款逻辑
            $refundNo = $params['refund_no'];
            $orderNo = $params['order_no'] ?? '';
            $orderId = $params['order_id'] ?? 0;
            $reason = $params['reason'];
            $amount = $params['amount'];
            $notifyUrl = $params['notify_url'];
            $attach = $params['attach'] ?? '';
            $tm = time();

            if ($orderId) {
                $order = $this->orderModel->where('order_id', $orderId)->findOrEmpty()->toArray();
            } else {
                $order = $this->orderModel->where('order_no', $orderNo)->findOrEmpty()->toArray();
            }
            if (empty($order)) {
                throw new \Exception('订单不存在');
            }
            if ($order['status'] != $this->orderModel::STATUS_PAID) {
                throw new \Exception('订单未支付,无需退款');
            }

            //判断剩余的钱可够退款
            $leftMoney = bcsub($order['money_total'], $order['money_refund'], 2);
            if (bccomp($leftMoney, $amount, 2) == -1) {
                throw new \Exception('订单剩余金额不足,无法退款' . $leftMoney);
            }

            //判断退款单号重复
            if ($this->refundModel->where('refund_no', $refundNo)->find()) {
                throw new \Exception('退款单号重复');
            }

            //计算已退款金额
            $refundFields = 'SUM(amount) AS refund_amount,
                             SUM(money_system_balance) AS refund_money_system_balance,
                             SUM(money_score_reduce) AS refund_money_score_reduce,
                             SUM(score_back_count) AS refund_score_back_count,
                             SUM(money_online_pay) AS refund_money_online_pay';
            $refundTotal = $this->refundModel->where('order_id', $order['order_id'])->field($refundFields)->findOrEmpty()->toArray();

            $leftBalance = bcsub($order['money_system_balance'], $refundTotal['refund_money_system_balance'], 2);
            $leftScorceCount = bcsub($order['score_use_count'], $refundTotal['refund_score_back_count'], 2);
            $leftScoreReduce = bcsub($order['money_score_reduce'], $refundTotal['refund_money_score_reduce'], 2);
            $leftOnlineBalance = bcsub($order['money_online_pay'], $refundTotal['refund_money_online_pay'], 2);

            //退款优先级。平台积分-平台余额-在线支付
            $backBalance = $backScoreCount = $backScoreReduce =  $backOnlineBalance = 0;
            $isAllRefund = false;
            if (bccomp($leftMoney, $amount, 2) == 0) {
                $backBalance = $leftBalance;  //平台余额退还部分
                $backScoreCount = $leftScorceCount;   //积分退还部分
                $backScoreReduce = $leftScoreReduce;  //积分退还部分抵扣金额
                $backOnlineBalance = $leftOnlineBalance;  //在线支付退还部分
                $isAllRefund = true;
            } else {
                $leftApplyRefundAmount = $amount;

                //积分退
                if (bccomp($leftScoreReduce, 0, 2)) {
                    if (bccomp($leftScoreReduce, $leftApplyRefundAmount, 2) >= 0) {
                        //剩余平台积分抵扣大于退款金额
                        $backScoreReduce = $leftApplyRefundAmount;
                        $backScoreCount = bcmul($leftScorceCount, $leftApplyRefundAmount / $leftScoreReduce, 2);
                        $leftApplyRefundAmount = 0;
                    } else {
                        $backScoreReduce = $leftScoreReduce;
                        $backScoreCount = $leftScorceCount;
                        $leftApplyRefundAmount = bcsub($leftApplyRefundAmount, $leftScoreReduce, 2);
                    }
                }

                //平台余额退
                if (bccomp($leftApplyRefundAmount, 0, 2) > 0 && bccomp($leftBalance, 0, 2) > 0) {
                    if (bccomp($leftBalance, $leftApplyRefundAmount, 2) >= 0) {
                        //剩余平台余额大于退款金额
                        $backBalance = $leftApplyRefundAmount;
                        $leftApplyRefundAmount = 0;
                    } else {
                        $backBalance = $leftBalance;
                        $leftApplyRefundAmount = bcsub($leftApplyRefundAmount, $leftBalance, 2);
                    }
                }

                //在线支付金额退
                if (bccomp($leftApplyRefundAmount, 0, 2) > 0 && bccomp($leftOnlineBalance, 0, 2) > 0) {
                    if (bccomp($leftOnlineBalance, $leftApplyRefundAmount, 2) >= 0) {
                        //剩余余额大于退款金额
                        $backOnlineBalance = $leftApplyRefundAmount;
                        $leftApplyRefundAmount = 0;
                    } else {
                        $backOnlineBalance = $leftOnlineBalance;
                        $leftApplyRefundAmount = bcsub($leftApplyRefundAmount, $leftOnlineBalance, 2);
                    }
                }
            }

            //保存退款申请记录
            $refundData = [
                'order_id' => $order['order_id'],
                'amount' => $amount,
                'refund_no' => $refundNo,
                'reason' => $reason,
                'notify_url' => $notifyUrl,
                'attach' => $attach,
                'money_system_balance' => $backBalance,
                'money_score_reduce' => $backScoreReduce,
                'score_back_count' => $backScoreCount,
                'money_online_pay' => $backOnlineBalance,
                'refund_time' => $tm,
            ];
            $this->refundModel->insertGetId($refundData);

            //更新订单状态和已退款金额
            $orderUpdateData = [];
            $isAllRefund && $orderUpdateData['status'] = $this->orderModel::STATUS_CANCEL;
            $this->orderModel->where('order_id', $order['order_id'])->inc('money_refund', $amount)->update($orderUpdateData);

            //退积分、平台余额、在线支付金额
            $remark = "订单退款增加。订单号：" . $order['order_no'];
            if ($backScoreCount > 0) {
                (new UserService())->addScore($order['uid'], $backScoreCount, $remark);
            }
            if ($backBalance > 0) {
                (new UserService())->addMoney($order['uid'], $backBalance, $remark);
            }
            if ($backOnlineBalance > 0 && !empty($order['pay_orderno'])) {
                (new PayService())->refund($order['pay_orderno'], $backOnlineBalance);
            }

            //异步通知
            $pushData = [
                'refund_no' => $refundNo,
                'attach' => $attach,
                'amount' => $amount,
                'money_system_balance' => $backBalance,
                'money_score_reduce' => $backScoreReduce,
                'score_back_count' => $backScoreCount,
                'money_online_pay' => $backOnlineBalance,
                'refund_time' => $tm,
            ];
            building_request_post($notifyUrl, $pushData);
            return $pushData;
        }
    }

    //单笔退款申请查询
    public function queryRefund($refundNo)
    {
        if (empty($refundNo)) {
            throw new \Exception('退款单号不能为空');
        }
        $fields = "refund_no,attach,amount,money_system_balance,money_score_reduce,score_back_count,money_online_pay,refund_time";
        $record = $this->refundModel->where('refund_no', $refundNo)->field($fields)->findOrEmpty()->toArray();
        return $record;
    }

    //根据订单号查询多笔退款申请
    public function queryRefundByOrderid($orderId)
    {
        if (empty($orderId)) {
            throw new \Exception('订单号不能为空');
        }
        $fields = "refund_no,attach,amount,money_system_balance,money_score_reduce,score_back_count,money_online_pay,refund_time";
        $records = $this->refundModel->where('order_id', $orderId)->field($fields)->order('refund_id asc')->select()->toArray();
        return $records;
    }
}
