<?php
namespace app\deliver\controller;

use app\common\model\service\AppPushMsgService;
use app\deliver\model\service\DeliverUserService;
use app\deliver\model\service\DeliverSupplyService;
use app\deliver\model\db\DeliverSupply;
use app\shop\model\service\order\ShopOrderService;
use app\deliver\model\service\DeliverRewardOrderService;
use think\Request;

/**
 * 打赏骑手控制器
 * @author: 汪晨
 * @date: 2021/1/27
 */
class RewardController extends ApiBaseController
{

    /**
     * 获取骑手信息
     * @author: 汪晨
     * @date: 2021/1/27
     */
    public function rewardDiliverUser(){
        // 获取参数
        $orderId = $this->request->param('order_id', 0, 'intval');
        if (empty($orderId)) {
            return api_output(1001, [], '参数有误');
        }

        // 获取骑手配送信息
        $whereOrder = array('order_id' => $orderId);
        $deliverOrder = (new DeliverSupplyService())->getOneOrder($whereOrder);
        $arr['deliverOrder'] = $deliverOrder;
        if (empty($deliverOrder)) {
            return api_output(1001, [], '配送信息不存在');
        }
        // 获取骑手信息
        $whereUser = array('uid' => $deliverOrder['uid']);
        $deliverUser = (new DeliverUserService())->getOneUser($whereUser);
        $deliverUser['satisfaction'] = $deliverUser['average_score'] ? ($deliverUser['average_score'] * 20).'%' : 0;
        $deliverUser['satisfaction'] = $deliverUser['satisfaction']=='0%' ? '100%' : $deliverUser['satisfaction'];
        if (empty($deliverUser)) {
            return api_output(1001, [], '骑手不存在');
        }
        if(empty($deliverUser['photo'])){
            $deliverUser['photo'] = cfg('site_url') . '/static/images/reward/photo.png';
        }

        // 计算里程
        $whereDistance = array('uid' => $deliverOrder['uid'],'type'=>0);
        $distance = (new DeliverSupply())->where($whereDistance)->where('status','>','0')->sum('distance');
        $deliverUser['mileage'] = sprintf("%.2f",$distance);
        
        $arr['deliverUser'] = $deliverUser;

        // 打赏金额列表
        $arr['moneyList'] = array(
            [
                'img' => cfg('site_url') . '/static/images/reward/cola.png',
                'money' => 2
            ],
            [
                'img' => cfg('site_url') . '/static/images/reward/drumsticks.png',
                'money' => 5
            ],
            [
                'img' => cfg('site_url') . '/static/images/reward/hamburger.png',
                'money' => 10
            ],
        );

        // 根据外卖订单信息获取用户ID
        $whereOrder = array('order_id' => $orderId);
        $shopOrder = (new ShopOrderService())->getOneOrder($whereOrder,'uid');
        $shopOrderUid = $shopOrder['uid'];

         // 生成长订单号
         $order_no = date('YmdHis').rand(100000,999999);

         // 写入打赏骑手订单表
         $reward = array(
             'order_no'       => $order_no,
             'takeout_id'     => $orderId,
             'user_id'        => $deliverOrder['uid'],
             'title'          => '打赏骑手订单',
         );
         $arr['rewardOrder'] = $reward;
         
         $isSaveOrder = (new DeliverRewardOrderService())->getOneOrder(['user_id'=>$reward['user_id'],'takeout_id'=>$reward['takeout_id']],'order_id');
         
         if($isSaveOrder){
             $saveOrder = (new DeliverRewardOrderService())->upOrder($reward);
         }else{
             $saveOrder = (new DeliverRewardOrderService())->saveOrder($reward);
         }

        return api_output(0, $arr?: new \stdClass());
    }

    /**
     * 生成订单详情
     * @author: 汪晨
     * @date: 2021/1/27
     */
    public function saveRewardOrder(){
        // 获取参数
        $orderId = $this->request->param('order_id', 0, 'intval');
        $orderMoney = $this->request->param('order_money', 0, 'float');
        if (empty($orderId) || empty($orderMoney)) {
            return api_output(1001, [], '参数有误');
        }

        // 根据外卖订单信息获取用户ID
        $whereOrder = array('order_id' => $orderId);
        $shopOrder = (new ShopOrderService())->getOneOrder($whereOrder,'uid');
        $shopOrderUid = $shopOrder['uid'];

        // 获取骑手配送信息
        $deliverOrder = (new DeliverSupplyService())->getOneOrder($whereOrder,'uid,mer_id,city_id,store_id');
        $arr['deliverOrder'] = $deliverOrder;

        // 更新打赏骑手订单表
        $reward = array(
            'takeout_id'     => $orderId,
            'user_id'        => $deliverOrder['uid'],
            'order_money'    => $orderMoney,
            'userid'         => $shopOrderUid,
            'uid'            => $deliverOrder['uid'],
            'mer_id'         => $deliverOrder['mer_id'],
            'city_id'        => $deliverOrder['city_id'],
            'store_id'       => $deliverOrder['store_id']
        );
        $arr['rewardOrder'] = $reward;
        
        $isSaveOrder = (new DeliverRewardOrderService())->getOneOrder(['user_id'=>$reward['user_id'],'takeout_id'=>$reward['takeout_id']],'order_id');
        
        if($isSaveOrder){
            $saveOrder = (new DeliverRewardOrderService())->upOrder($reward);
        }else{
            $saveOrder = (new DeliverRewardOrderService())->saveOrder($reward);
        }
        
        if($saveOrder){
            $arr['rewardOrder']['order_id'] = intval($isSaveOrder['order_id']);
        }else{
            return api_output(1002, [], '写入操作失败');
        }
       
        return api_output(0, $arr?: new \stdClass());
    }

}
