<?php


namespace app\life_tools\model\service;
use app\common\model\service\coupon\SystemCouponService;
use app\common\model\service\order\SystemOrderService;
use app\common\model\service\UserService;
use app\life_tools\model\db\LifeToolsCompetition;
use app\life_tools\model\db\LifeToolsCompetitionAudit;
use app\life_tools\model\db\LifeToolsCompetitionJoinOrder;
use think\Exception;

class LifeToolsCompetitionJoinOrderService
{
    /**
     * 获取支付订单详情
     */
    public function getOrderPayInfo($orderId)
    {
        if (empty($orderId)) {
            throw new Exception(L_("参数错误"), 1001);
        }
        $nowOrder = (new LifeToolsCompetitionJoinOrder())->orderDetail(['j.pigcms_id'=>$orderId],'j.uid,j.status,j.paid,j.price,j.coupon_price,j.coupon_id,c.city_id,c.title,j.real_orderid');
        $timeRemain = 900;
        if (empty($nowOrder)) {
            throw new Exception(L_("当前订单不存在！"), 1003);
        }
        if ($nowOrder['paid']) {
            throw new Exception(L_("订单已支付"), 1003);
        }
        if ($nowOrder['status'] == 1) {
            throw new Exception(L_("您的订单已报名成功，不用付款了！"), 1003);
        }
        if ($nowOrder['status'] == 2) {
            throw new Exception(L_("您的订单已报名失败，不能付款了！"), 1003);
        }

        // 店铺信息
        if(empty($nowOrder['coupon_id'])){
            $payInfact = $nowOrder['price'];
        }else{
            $payInfact = $nowOrder['price'] - $nowOrder['coupon_price'];
        }

        $returnArr['order_money'] = get_format_number($payInfact);
        $returnArr['paid'] = $nowOrder['paid'];
        $returnArr['order_no'] =  $orderId;
        $returnArr['store_id'] = 0;
        $returnArr['city_id'] = $nowOrder['city_id'];
        $returnArr['mer_id'] = 0;
        $returnArr['is_cancel'] = 0;
        $returnArr['time_remaining'] = $timeRemain;//秒
        $returnArr['uid'] = $nowOrder['uid'];
        $returnArr['title'] = $nowOrder['title'];
        $returnArr['business_order_sn'] = $nowOrder['real_orderid'];
        return $returnArr;
    }

    /**
     * 获取支付结果页地址
     * @param  [type]  $combine_id  [description]
     * @param  integer $is_cancel 1=已取消  0=未取消
     * @return string             返回跳转链接
     */

    public function getPayResultUrl($orderId,$cancel = 0)
    {
        if(!$orderId) {
            throw new \think\Exception(L_("参数错误"), 1001);
        }

        $nowOrder =  (new LifeToolsCompetitionJoinOrder())->getOneDetail(['pigcms_id'=>$orderId],true);
        if(!$nowOrder) {
            throw new \think\Exception(L_("当前订单不存在！"), 1003);
        }

       /* if($cancel== 1){*/
            // 订单详情
            $url = get_base_url('pages/lifeTools/match/myMatch/detail?order_id='.$orderId);
            return $url;
        /*}else{
            // 支付成功页
            $url = get_base_url('pages/lifeTools/pocket/message');
            return ['redirect_url' => $url, 'direct' => 1];
        }*/
    }

    /**
     * 支付成功更新订单状态
     */
    public function afterPay($orderId, $payParam = []){
        $order = (new LifeToolsCompetitionJoinOrder())->getOneDetail(['pigcms_id'=>$orderId],true);

        if(empty($order)) throw new \think\Exception("没有找到订单");

        if ($order['paid'] == 1) {
            throw new Exception(L_("订单已支付"), 1003);
        }

        $UserService = new UserService();
        $nowUser = $UserService->getUser($order['uid']);
        if(empty($nowUser)){
            throw new Exception(L_("购买用户不存在"), 1003);
        }

        $paidTime = isset($payParam['pay_time']) ? $payParam['pay_time'] : '';
        $paidMoney = isset($payParam['paid_money']) ? $payParam['paid_money'] : '';
        $paidType = isset($payParam['paid_type']) ? $payParam['paid_type'] : '';
        $currentScoreUse = isset($payParam['current_score_use']) ? $payParam['current_score_use'] : '';
        $currentScoreDeducte = isset($payParam['current_score_deducte']) ? $payParam['current_score_deducte'] : '';
        $currentSystemBalance = isset($payParam['current_system_balance']) ? $payParam['current_system_balance'] : '';

        if ($order['paid'] == 1) {
            // 该订单已付款
            return false;
        }

        if ($order['status'] == 1) {//已报名
            return false;
        }

        //判断帐户余额
        if ($currentSystemBalance > 0) {
            // 您的帐户余额不够此次支付
            if ($nowUser['now_money'] < $currentSystemBalance) {
                return false;
            }
        }

        // 平台积分
        if ($currentScoreUse > 0) {
            //判断积分数量是否正确
            if ($nowUser['score_count'] < $currentScoreUse) {
                return false;
            }
        }

        //如果使用了平台优惠券
        if ($order['coupon_id']) {
            try {
                $result = (new SystemCouponService())->useCoupon($order['coupon_id'], $order['pigcms_id'], 'life_tools_competition_join', 0, $nowUser['uid']);
            } catch (\Exception $e) {
                return false;
            }
        }

        //如果用户使用了积分抵扣，则扣除相应的积分
        if ($currentScoreUse > 0) {
            $desc = L_("购买 X1商品 扣除X2", array("X1" => $order['name'], "X2" => cfg('score_name')));
            $desc .= L_('，订单编号') . $order['pigcms_id'];
            $use_result = (new UserService())->userScore($nowUser['uid'], $currentScoreUse, $desc);
            if ($use_result['error_code']) {
                return false;
            }
        }

        //如果用户使用了余额支付，则扣除相应的金额。
        if ($currentSystemBalance > 0) {
            $desc = L_("购买 X1商品 扣除余额，订单编号X2", array("X1" => $order['name'], 'X2' => $order['pigcms_id']));
            $use_result = (new UserService())->userMoney($nowUser['uid'], $currentSystemBalance, $desc);
            if ($use_result['error_code']) {
                return false;
            }
        }

        // 保存支付订单信息
        $saveData = [];
        $saveData['pay_time'] = $paidTime ? $paidTime : time();
        $saveData['pay_money'] = $paidMoney;//在线支付的钱
        $saveData['pay_type'] = $paidType;
        //$saveData['third_id'] = $paidOrderid;
        $saveData['paid'] = 1;
        $saveData['status'] = 1;
        $saveData['system_score'] = $currentScoreUse;//积分使用数量
        $saveData['system_score_money'] = $currentScoreDeducte;//积分抵扣金额
        $saveData['system_balance'] = $currentSystemBalance;//平台余额使用金额
        $competition=(new LifeToolsCompetition())->getToolCompetitionMsg(['competition_id'=>$order['competition_id']]);
        // 保存订单信息
        if (!(new LifeToolsCompetitionJoinOrder())->updateThis(['pigcms_id'=>$orderId], $saveData)) {
            return false;
        }elseif(!empty($competition)){
            (new LifeToolsCompetition())->setInc(['competition_id'=>$order['competition_id']],'join_num');
        }
        $systemOrderService = new SystemOrderService();
        // 更新系统订单
        $data=['paid'=>1,'status'=>1,'pay_type'=>$paidType,'coupon_price'=>$order['coupon_price'],
            'payment_money'=>$paidMoney,'balance_pay'=>$currentSystemBalance,'score_used_count'=>$currentScoreUse,
            'score_deducte'=>$currentScoreDeducte,'pay_time'=>time()];
        $systemOrderService->editOrder('life_tools_competition_join',$orderId,$data);
        $lifeToolsCompetitionAudit = new LifeToolsCompetitionAudit();
        $lifeToolsCompetitionAudit->where('order_id', $orderId)->order('sort DESC')->limit(1)->update(['is_show'=>1]);
        return true;
    }
}