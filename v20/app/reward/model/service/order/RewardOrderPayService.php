<?php
/**
 * 打赏订单支付服务
 * @author: 汪晨
 * @date: 2021/1/27
 */

namespace app\reward\model\service\order;

use app\reward\model\db\DeliverRewardOrder;
use app\deliver\model\service\DeliverSupplyService;
use app\deliver\model\service\DeliverUserService;
use app\common\model\service\UserService;
use app\reward\model\service\store\RewardOrderPushService;
use think\Exception;

class RewardOrderPayService
{
    public $DeliverRewardOrderMod = null;

    public function __construct()
    {
        $this->deliverRewardOrderMod = new DeliverRewardOrder();
    }

    /**
     * 获取打赏支付订单详情
     * @author: 汪晨
     * @date: 2021/1/27
     */
    public function getOrderPayInfo($orderId)
    {
        if (empty($orderId)) {
            throw new Exception(L_("参数错误"), 1001);
        }

        // 获取打赏订单信息
        $rewardOrder = $this->deliverRewardOrderMod->getOne(['order_id' => $orderId]);
        $timeRemain = 900 - (time() - $rewardOrder['create_time']);
        if (empty($rewardOrder)) {
            throw new Exception(L_("当前订单不存在！"), 1003);
        }
        if ($rewardOrder['paid']) {
            throw new Exception(L_("订单已支付"), 1003);
        }

        // 店铺信息
        $payInfact = $rewardOrder->order_money;
        $returnArr['order_money'] = get_format_number($payInfact);
        $returnArr['paid'] = $rewardOrder->paid;
        $returnArr['order_no'] = $rewardOrder->order_no;
        $returnArr['store_id'] = $rewardOrder->store_id;
        $returnArr['city_id'] = $rewardOrder->city_id;
        $returnArr['mer_id'] = $rewardOrder->mer_id;
        $returnArr['is_cancel'] = 0;
        $returnArr['use_platform_balance'] = true;
        $returnArr['use_merchant_balance'] = false;
        $returnArr['use_score'] = false;
        $returnArr['time_remaining'] = $timeRemain; // 秒
        $returnArr['uid'] = $rewardOrder->uid;
        $returnArr['title'] = $rewardOrder->title;

        return $returnArr;
    }


    /**
     * 支付成功后跳转地址
     * @author: 汪晨
     * @date: 2021/1/28
     */
    public function getPayResultUrl($orderId){
        // 获取打赏订单表中外卖订单ID
        $rewardOrder = $this->deliverRewardOrderMod->getOne(['order_id' => $orderId],'takeout_id');

        $redirctUrl = cfg('site_url') . '/wap.php?c=Shop&a=status&order_id=' . $rewardOrder['takeout_id'];
        return $redirctUrl;
    }

    /**
     * 支付成功更新订单状态
     * @author: 汪晨
     * @date: 2021/1/28
     */
    public function afterPay($orderId, $extra)
    {
        if (!$orderId) {
            throw new Exception(L_("参数错误"), 1001);
        }
        $rewardOrder = $this->deliverRewardOrderMod->getOne(['order_id' => $orderId]);
        if (!$rewardOrder) {
            throw new Exception(L_("当前订单不存在！"), 1003);
        }
        if ($rewardOrder['paid'] == 1) {
            throw new Exception(L_("订单已支付"), 1003);
        }
        $rewardOrder = $rewardOrder->toArray();
        // $aliasName = cfg('shop_alias_name');

        // 获取购买用户信息
        $userService = new UserService();
        $buyer = $userService->getUser($rewardOrder['userid'], 'uid');
        if (empty($buyer)) {
            throw new Exception(L_("购买用户不存在"), 1003);
        }

        // 判断帐户余额,扣除余额
        $system_balance = isset($extra['current_system_balance']) ? $extra['current_system_balance'] : '';
        $balancePay = floatval($system_balance);
        if ($balancePay && $buyer['now_money'] < $balancePay) {
            throw new Exception(L_("您的帐户余额不够此次支付"), 1003);
        }
        if ($balancePay > 0) {
            $useResult = (new UserService())->userMoney($rewardOrder['userid'], $balancePay, L_("打赏骑手，扣除余额，订单编号X2", array("X2" => $rewardOrder['order_no'])));
            if ($useResult['error_code']) {
                throw new Exception($useResult['msg'], 1003);
            }
        }

        // 修改后台小费金额
        $orderMoney =  sprintf("%.2f",round($rewardOrder['order_money'],2));
        (new DeliverSupplyService())->tipPriceUpdae($rewardOrder['takeout_id'],$orderMoney);

        // 更新骑手账户余额
        (new DeliverUserService())->addMoney($rewardOrder['user_id'],'reward',$orderId,0,0,$orderMoney,'小费收入-商家单');
        
        // 修改打赏订单表
        $data = array(
            'paid'              => isset($extra['paid']) ? $extra['paid'] : '',
            'pay_time'          => isset($extra['paid_time']) ? $extra['paid_time'] : '',
            'pay_money'         => isset($extra['paid_money']) ? $extra['paid_money'] : '',
            'pay_type'          => isset($extra['paid_type']) ? $extra['paid_type'] : '',
            'pay_orderid'       => isset($extra['paid_orderid']) ? $extra['paid_orderid'] : '',
            'is_own'            => isset($extra['is_own']) ? $extra['is_own'] : '',
            'system_balance'    => $system_balance,
            'merchant_balance'  => isset($extra['current_merchant_balance']) ? $extra['current_merchant_balance'] : '',
            'give_balance'      => isset($extra['current_merchant_give_balance']) ? $extra['current_merchant_give_balance'] : '',
            'qiye_balance'      => isset($extra['current_qiye_balance']) ? $extra['current_qiye_balance'] : '',
            'score_use'         => isset($extra['current_score_use']) ? $extra['current_score_use'] : '',
            'score_deducte'     => isset($extra['current_score_deducte']) ? $extra['current_score_deducte'] : ''
        );

        $this->deliverRewardOrderMod->where('order_id', $orderId)->update($data);
       
        // 推送消息提醒配送员 
        $nowOrder = array(
            'takeout_id'    => $rewardOrder['takeout_id'],
            'order_no'      => $rewardOrder['order_no'],
            'user_id'       => $rewardOrder['user_id'],
            'userid'       => $rewardOrder['userid'],
            'order_money'   => $rewardOrder['order_money'],
            'create_time'   => $rewardOrder['create_time']
        );
        
        (new RewardOrderPushService())->sendMsgReward($nowOrder);

        return true;
    }
}