<?php
/**
 * 活动预约订单过期自动核销/退款，
 * 结算
 */

namespace app\common\model\service\plan\file;

use app\life_tools\model\db\LifeToolsAppointJoinOrder;
use app\life_tools\model\service\appoint\LifeToolsAppointJoinOrderService;
use app\life_tools\model\service\appoint\LifeToolsAppointListService;
use app\life_tools\model\service\LifeToolsAppointService;
use app\merchant\model\service\MerchantMoneyListService;
use think\facade\Db;

class LifeToolsAppointOrderAutoCancelService
{

    public function runTask()
    {	
        $lifeToolsAppointJoinOrderModel = new LifeToolsAppointJoinOrder(); 
        $LifeToolsAppointJoinOrderService = new LifeToolsAppointJoinOrderService(); 
        
        $nowTime = time();
        $condition = [
            ['a.end_time', '<', $nowTime],
            ['o.paid', '=', 1],
            ['o.is_apply_refund', '=', 0],
            ['o.status', '=', 1],
        ];
        $data = $lifeToolsAppointJoinOrderModel->getOrderList($condition);
        //0自动退款，1自动核销
        $overdueConfig = cfg('life_tools_appoint_time_out');
        foreach ($data as $key => $val) {
            if($overdueConfig == 1){
                if($val['need_verify'] == 1){
                    (new LifeToolsAppointService)->doVerify($val['order_id'], 0, '过期自动核销'); //自动核销
                }
            }else{
                $LifeToolsAppointJoinOrderService->refundingOrder('auto',['pigcms_id' => $val['order_id'], 'apply_refund_reason' => '超时未核销订单自动退款']); //模拟用户申请退款
                $LifeToolsAppointJoinOrderService->auditRefund(['pigcms_id'=>$val['pigcms_id'], 'type'=>1]); //同意退款
            }
        } 


        $condition = [
            ['a.end_time', '<', $nowTime],
            ['o.paid', '=', 1],
            ['o.is_apply_refund', '=', 0],
            ['o.status', 'in', [1, 3]]
        ];
        $data = $lifeToolsAppointJoinOrderModel->getOrderList($condition);
         
        //结算
        foreach ($data as $key => $val) {

            Db::startTrans();
            try { 
                $orderInfo = []; 
                $orderInfo['pay_order_id'] = $val['real_orderid'];
                $orderInfo['total_money'] = $val['system_balance'] + $val['system_score_money'];
                $orderInfo['bill_money'] = $val['system_balance'] + $val['system_score_money'];
                $orderInfo['balance_pay'] = $val['system_balance'];
                $orderInfo['merchant_balance'] = $val['merchant_balance_pay'];
                $orderInfo['card_give_money'] = $val['merchant_balance_give'];
                $orderInfo['payment_money'] = $val['pay_money'];
                $orderInfo['score_deducte'] = $val['system_score_money'];
                $orderInfo['order_type'] = 'LifeToolsAppoint';
                $orderInfo['num'] = 1;
                $orderInfo['mer_id'] = $val['mer_id'];
                $orderInfo['order_id'] = $val['order_id'];
                $orderInfo['uid'] = $val['uid'];
                $orderInfo['desc'] = "活动预约结算"; 

                (new MerchantMoneyListService())->addMoney($orderInfo);
                $addData = [];
                $addData['appoint_id'] = $val['appoint_id'];
                $addData['mer_id'] = $val['mer_id'];
                $addData['order_id'] = $val['order_id'];
                $addData['pay_money'] = $val['pay_money'];
                $addData['system_balance'] = $val['system_balance'];
                $addData['system_score_money'] = $val['system_score_money'];
                $addData['system_score'] = $val['system_score'];
                $addData['coupon_id'] = $val['coupon_id'];
                $addData['coupon_price'] = $val['coupon_price'];
                $addData['card_id'] = $val['card_id'];
                $addData['card_price'] = $val['card_price'];
                $addData['merchant_balance_pay'] = $val['merchant_balance_pay'];
                $addData['merchant_balance_give'] = $val['merchant_balance_give'];
                $addData['add_time'] = $nowTime;
                (new LifeToolsAppointListService())->addMerchantMoney($addData);
 
                $condition = [];
                $condition[] = ['pigcms_id', '=', $val['pigcms_id']];
                $lifeToolsAppointJoinOrderModel->where($condition)->save(['is_bill'=>1]);
                Db::commit(); 
            } catch (\Exception $e) { 
                Db::rollback(); 
            } 
        }
  

        return true;
    }

}