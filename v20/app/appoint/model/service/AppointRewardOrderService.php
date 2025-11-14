<?php


namespace app\appoint\model\service;


use app\appoint\model\db\AppointRewardMoney;
use app\appoint\model\db\MerchantWorkers;
use app\appoint\model\db\MerchantWorkersMoney;
use app\appoint\model\db\RewardOrder;
use app\common\model\db\AppointOrder;
use app\common\model\service\UserService;

class AppointRewardOrderService
{
    /**
     * 获取打赏技师信息
     * @param $params
     */
    public function getWorkerInfo($params){
        $order_id = $params['order_id'];
        $detail = (new AppointOrder())->where(['order_id'=>$order_id])->findOrEmpty()->toArray();
        if(!$detail){
            throw new \think\Exception(L_('订单不存在!'));
        }
        if(!$detail['merchant_worker_id']){
            throw new \think\Exception(L_('当前订单未分配技师!'));
        }
        if($detail['service_status']<1){
            throw new \think\Exception(L_('未服务无法打赏!'));
        }
        $worker = (new MerchantWorkers())->where(['merchant_worker_id'=>$detail['merchant_worker_id']])->findOrEmpty()->toArray();
        if(!$worker){
            throw new \think\Exception(L_('技师不存在!'));
        }
        $arr['worker'] = [
            'merchant_worker_id' => $detail['merchant_worker_id'],
            'worker_name' => $worker['name'],
            'mobile' => $worker['mobile'],
            'avatar_path' => replace_file_domain('/upload/appoint/'.str_replace(',', '/s_', $worker['avatar_path'])),
        ];
        $arr['rewardMoneyList'] = (new AppointRewardMoney())->field('id,money,name,pic')->where(['status'=>1])->order('sort desc,id asc')->limit(6)->select()->toArray();
        
        foreach ($arr['rewardMoneyList'] as $key=>$item){
            $arr['rewardMoneyList'][$key]['pic'] = $item['pic']?replace_file_domain($item['pic']):'';
        }
        $arr['order_id'] = $order_id;
        return $arr;
    }

    /**
     * 保存打赏订单信息
     * @param $params
     * @throws \think\Exception
     */
    public function saveWorkerReward($params){
        $order_id = $params['order_id'];
        $uid = $params['uid'];
        $money = $params['money'];
        $detail = (new AppointOrder())->where(['order_id'=>$order_id])->findOrEmpty()->toArray();
        if(!$detail){
            throw new \think\Exception(L_('订单不存在!'));
        }
        if(!$detail['merchant_worker_id']){
            throw new \think\Exception(L_('当前订单未分配技师!'));
        }
        if($detail['service_status']<1){
            throw new \think\Exception(L_('未服务无法打赏!'));
        }
        $worker = (new MerchantWorkers())->where(['merchant_worker_id'=>$detail['merchant_worker_id']])->findOrEmpty()->toArray();
        if(!$worker){
            throw new \think\Exception(L_('技师不存在!'));
        }
        if($money<=0||!is_numeric($money)){
            throw new \think\Exception(L_('打赏金额不能小于0元'));
        }
        $saveData = [
            'reward_id' => $worker['merchant_worker_id'],
            'uid' => $uid,
            'business_order_id' => $order_id,
            'money' => $money,
            'add_time' => time(),
            'type' => 3,
        ];
        try {
            $r_id = (new RewardOrder())->insertGetId($saveData);
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
        $arr = [
            'order_id' => $r_id,
            'order_type' => 'appoint_reward',
            'use_merchant_balance' => 1,
            'use_system_balance' => 1,
        ];
        return $arr;
    }

    /**
     * 获取打赏支付订单详情
     * @author: 汪晨
     * @date: 2021/1/27
     */
    public function getOrderPayInfo($orderId)
    {
        if (empty($orderId)) {
            throw new \think\Exception(L_("参数错误"));
        }

        // 获取打赏订单信息
        $rewardOrder = (new RewardOrder())->where(['order_id' => $orderId,'is_del'=>0])->findOrEmpty()->toArray();
        $timeRemain = 900 - (time() - $rewardOrder['add_time']);
        if (empty($rewardOrder)) {
            throw new \think\Exception(L_("当前订单不存在！"));
        }
        if ($rewardOrder['status']==1) {
            throw new \think\Exception(L_("订单已支付"));
        }
        $appoint_order = (new AppointOrder())->where(['order_id'=>$rewardOrder['business_order_id']])->findOrEmpty()->toArray();
        if(!$appoint_order){
            throw new \think\Exception(L_("预约订单不存在！"));
        }
        // 店铺信息
        $payInfact = $rewardOrder['money'];
        $returnArr['order_money'] = get_format_number($payInfact);
        $returnArr['paid'] = $rewardOrder['status']==1?1:0;
        $returnArr['order_no'] = $rewardOrder['orderid'];
        $returnArr['store_id'] = $appoint_order['store_id'];
        $returnArr['city_id'] = 0;
        $returnArr['mer_id'] = $appoint_order['mer_id'];
        $returnArr['is_cancel'] = $timeRemain<=0?1:0;
        $returnArr['use_platform_balance'] = true;
        $returnArr['use_merchant_balance'] = false;
        $returnArr['use_score'] = false;
        $returnArr['time_remaining'] = $timeRemain; // 秒
        $returnArr['uid'] = $rewardOrder['uid'];
        $returnArr['title'] = '打赏金额';
        if($timeRemain<=0){
            (new RewardOrder())->where(['order_id' => $orderId,'is_del'=>0])->save(['status'=>0]);
        }

        return $returnArr;
    }


    /**
     * 支付成功后跳转地址
     * @author: 汪晨
     * @date: 2021/1/28
     */
    public function getPayResultUrl($orderId){
        // 获取打赏订单表中外卖订单ID
        $rewardOrder = (new RewardOrder())->where(['order_id' => $orderId])->findOrEmpty()->toArray();
        $appointOrder = (new AppointOrder())->where(['order_id'=>$rewardOrder['business_order_id']])->findOrEmpty()->toArray();
        $redirctUrl = cfg('site_url') . '/packapp/plat/pages/appoint/goods/submitDetail?orderid='.$appointOrder['order_id'].'&appoint_id=' . $appointOrder['appoint_id'];
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
            throw new \think\Exception(L_("参数错误"));
        }
        $rewardOrder = (new RewardOrder())->where(['order_id' => $orderId])->findOrEmpty()->toArray();
        if (!$rewardOrder) {
            throw new \think\Exception(L_("当前订单不存在！"));
        }
        if ($rewardOrder['status']==1) {
            throw new \think\Exception(L_("订单已支付"));
        }

        // 获取购买用户信息
        $userService = new UserService();
        $buyer = $userService->getUser($rewardOrder['uid'], 'uid');
        if (empty($buyer)) {
            throw new \think\Exception(L_("购买用户不存在"));
        }

        // 判断帐户余额,扣除余额
        $system_balance = isset($extra['current_system_balance']) ? $extra['current_system_balance'] : '';
        $balancePay = floatval($system_balance);
        if ($balancePay && $buyer['now_money'] < $balancePay) {
            throw new \think\Exception(L_("您的帐户余额不够此次支付"));
        }
        $order_no = build_real_orderid($rewardOrder['uid']);
        try {
            if ($balancePay > 0) {
                $useResult = (new UserService())->userMoney($rewardOrder['uid'], $balancePay, L_("打赏技师，扣除余额，订单编号X2", array("X2" => $order_no)));
                if ($useResult['error_code']) {
                    throw new \think\Exception($useResult['msg']);
                }
            }

            // 更新技师账户余额
            $work_rate = cfg('platform_reward_technician_percentage')?:0;
            $work_money = $work_rate>0?$rewardOrder['money']*(100-$work_rate)/100:$rewardOrder['money'];
            $saveData = [
                'merchant_worker_id' => $rewardOrder['reward_id'],
                'type' => 1,
                'money' => $work_money,
                'time' => time(),
                'ip' => get_client_ip(0),
                'desc' => '用户打赏您'.$work_money.'元',
                'order_id' => 0,
                'from_type' => 1
            ];
            (new MerchantWorkersMoney())->save($saveData);
            (new MerchantWorkers())->where(['merchant_worker_id'=>$rewardOrder['reward_id']])->inc('reward_money',$work_money)->inc('now_money',$work_money)->update();

            // 修改打赏订单表
            $data = array(
                'status'              => isset($extra['paid']) ? $extra['paid'] : 2,
                'pay_time'          => isset($extra['paid_time']) ? $extra['paid_time'] : '',
                'pay_money'         => isset($extra['paid_money']) ? $extra['paid_money'] : '',
                'pay_type'          => isset($extra['paid_type']) ? $extra['paid_type'] : '',
                //'pay_orderid'       => isset($extra['paid_orderid']) ? $extra['paid_orderid'] : '',
                'system_balance_money'    => $system_balance,
                'mer_balance_money'  => isset($extra['current_merchant_balance']) ? $extra['current_merchant_balance'] : '',
                'worker_money'       => $work_money,
                'orderid' => $order_no
            );

            (new RewardOrder())->where(['order_id' => $orderId])->save($data);

	        if($rewardOrder['type'] == 3 && $rewardOrder['business_order_id']) {
		        $appointOrder = AppointOrder::where(['order_id' => $rewardOrder['business_order_id']])->find();
		        (new AppointOrderService())->addOrderRecord($appointOrder->mer_id, $appointOrder->order_id, '技师打赏', ['content' => [
			        [
				        'title' => '打赏金额',
				        'value' => $work_money,
				        'type'  => 'text'
			        ]
		        ]]);
	        }
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }

        return true;
    }
}