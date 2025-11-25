<?php
/**
 * 体育健身订单超时取消订单，已付款过期自动核销
 * 约战订单-到期未成团的自动退款
 * 景区次卡订单未支付超时取消订单-已支付超时过期
 */

namespace app\common\model\service\plan\file;

use app\life_tools\model\db\LifeScenicLimitedSku;
use app\life_tools\model\db\LifeToolsCardOrder;
use app\life_tools\model\db\LifeToolsOrder;
use app\life_tools\model\db\LifeToolsOrderBindSportsActivity;
use app\life_tools\model\db\LifeToolsOrderDetail;
use app\life_tools\model\db\LifeToolsSportsSecondsKillTicketDetail;
use app\life_tools\model\db\LifeToolsSportsSecondsKillTicketSku;
use app\life_tools\model\db\LifeToolsTicket;
use app\life_tools\model\service\group\LifeToolsGroupOrderService;
use app\life_tools\model\service\LifeToolsCardOrderService;
use app\life_tools\model\service\LifeToolsOrderService;
use think\facade\Db;

class LifeToolsOrderAutoCancelService
{

    public function runTask()
    {	
    	$LifeToolsOrderService = new LifeToolsOrderService();
        $LifeToolsOrder        = new LifeToolsOrder();
        $LifeToolsCardOrder    = new LifeToolsCardOrder();
        $LifeToolsCardOrderService = new LifeToolsCardOrderService();
        $LifeToolsOrderBindSportsActivity = new LifeToolsOrderBindSportsActivity();
        $timeout = time() - cfg('life_tools_sports_order_auto_cancel') * 60;
    	$where   = [
    		['order_status', '<=', 10],
    		['add_time', '<', $timeout]
    	];
        $data = $LifeToolsOrderService->getSome($where, 'order_id,tools_id,ticket_id,act_id,num', 'order_id asc', 1, 20); //超时未支付自动取消
    	if (!empty($data)) {
    		foreach ($data as $value) {
	    		try {
                    $LifeToolsOrderService->changeOrderStatus($value['order_id'], 60, '超时未支付取消订单（计划任务）');
                    // if($value['ticket_id'] && $value['act_id']){
                    //     $detail=(new LifeToolsTicket())->getDetail($value['ticket_id']);
                    //     if(!empty($detail) && $detail['type']=='scenic'){
                    //         (new LifeScenicLimitedSku())->setInc(['act_id'=>$value['act_id'],'ticket_id'=>$value['ticket_id']],'act_stock_num',$value['num']);
                    //     }
                    //     if($detail['type']=='course' || $detail['type']=='stadium'){
                    //         $where_act = [
                    //             ['lt.tools_id', '=', $data['base_info']['tools_id']], ['lt.status', '=', 1], ['lt.is_del', '=', 0], ['sku.ticket_id', '=', $value['ticket_id']],
                    //             ['la.start_time', '<', time()], ['la.end_time', '>', time()], ['la.type', '=', 'limited'], ['la.is_del', '=', 0]
                    //         ];
                    //         $ret = (new LifeToolsSportsSecondsKillTicketDetail())->getActDetail($where_act, 'la.start_time,la.end_time,act.*,sku.act_stock_num,sku.act_price,l.tools_id,sku.ticket_id,sku.day_stock_num');
                    //         if (!empty($ret)) {
                    //             if($ret['stock_type']==1){//总库存
                    //                 (new LifeToolsSportsSecondsKillTicketSku())->setInc(['act_id'=>$ret['id'],'ticket_id'=>$value['ticket_id']],'act_stock_num',$value['num']);
                    //             }elseif($ret['stock_type']==2){//每日库存
                    //                 (new LifeToolsSportsSecondsKillTicketSku())->setInc(['act_id'=>$ret['id'],'ticket_id'=>$value['ticket_id']],'day_stock_num',$value['num']);
                    //             }
                    //         }
                    //     }
                    // }
                } catch (\Exception $e) {
                    fdump("订单ID：" . $value['order_id'] . $e->getMessage(), "LifeToolsOrderAutoCancelService", 1);
                }
	    	}
    	}

        $where1 = [
            ['o.order_status', '=', 20],
            ['a.type', '<>', 'course'],
            ['o.ticket_time', '<', date('Y-m-d')],
            ['o.activity_type', '<>', 'sports_activity'] //非运动约战订单
        ];
        $data1 = $LifeToolsOrder->getList($where1, [], 'o.order_id,o.ticket_time,a.type,o.is_group', 'o.order_id asc')['data']; //已付款过期自动核销或退款
        if (!empty($data1)) {
            foreach ($data1 as $value1) {                
                if($value1['is_group']){// 团购票 只有在过期未提交审核的情况下自动退款
                    // $groupOrder = (new LifeToolsGroupOrderService())->getOne(['order_id'=>$value1['order_id']]);
                    // if($groupOrder['group_status']>=10 || $groupOrder['submit_audit_time'] == 0 || ($groupOrder['submit_audit_time'] && $groupOrder['submit_audit_time'] > time())){// 已提交审核、没有过期时间、未到过期时间的排除
                    //     continue;
                    // }
                    (new LifeToolsOrder())->where('order_id', $value1['order_id'])->update([
                        'order_status'    => 70,
                        'last_time' => time(),
                    ]);

                }else{

                    try {
                        $this->paidOutAuto($value1['order_id'], $value1['type']);
                    } catch (\Exception $e) {
                        fdump("订单ID：" . $value1['order_id'] . $e->getMessage(), "LifeToolsOrderAutoCancelService", 1);
                    }
                }

            }
        }

        $where2 = [
            ['r.group_status', '<=', 10],
            ['o.ticket_time', '<', date('Y-m-d')],
            ['o.order_status', '<=', 20]
        ];
        $data2 = $LifeToolsOrderBindSportsActivity->getList($where2); //运动约战已付款过期自动核销
        if (!empty($data2)) {
            foreach ($data2 as $value2) {
                try {
                    $LifeToolsOrderBindSportsActivity->updateThis(['pigcms_id' => $value2['pigcms_id']], ['group_status' => 30]);
                    if ($value2['order_status'] == 20) {
                        $LifeToolsOrderService->supplyRefund(['order_id' => $value2['order_id'], 'reason' => '到期未成团自动退款'], '到期未成团自动退款'); //模拟用户申请退款
                        $LifeToolsOrderService->agreeRefund([$value2['order_id']], '到期未成团自动退款'); //同意退款
                    } else {
                        $LifeToolsOrderService->changeOrderStatus($value2['order_id'], 60, '超时未支付取消订单（计划任务）');
                    }
                } catch (\Exception $e) {
                    fdump("订单ID：" . $value2['order_id'] . $e->getMessage(), "LifeToolsOrderAutoCancelService", 1);
                }
            }
        }

        $where3 = [['', 'exp', Db::raw('(order_status < 20 AND add_time < ' . $timeout . ') OR (order_status = 20 AND out_time <> 0 AND out_time < ' . time() . ')')]];
        $data3 = $LifeToolsCardOrder->getSome($where3, 'order_id,order_status', 'order_id asc', 1, 20); //次卡超时未支付自动取消
        if (!empty($data3)) {
            foreach ($data3 as $value3) {
                try {
                    $status = $value3['order_status'] > 10 ? 70 : 60;
                    $LifeToolsCardOrderService->changeOrderStatus($value3['order_id'], $status, '超时更新订单（计划任务）');
                } catch (\Exception $e) {
                    fdump("订单ID：" . $value3['order_id'] . $e->getMessage(), "LifeToolsOrderAutoCancelService", 1);
                }
            }
        }

        $where4 = [ //课程订单过期处理
            ['o.order_status', '=', 20],
            ['a.type', '=', 'course'],
            ['t.course_end_time', '<=', time()],
            ['o.activity_type', '<>', 'sports_activity'] //非运动约战订单
        ];
        $data4 = $LifeToolsOrder->getList($where4, [], 'o.order_id,o.ticket_time', 'o.order_id asc')['data']; //已付款过期自动核销或退款
        if (!empty($data4)) {
            foreach ($data4 as $value4) {
                try {
                    $this->paidOutAuto($value4['order_id'], 'course');
                } catch (\Exception $e) {
                    fdump("订单ID：" . $value4['order_id'] . $e->getMessage(), "LifeToolsOrderAutoCancelService", 1);
                }
            }
        }
        return true;
    }

    //已付款过期自动核销或退款
    public function paidOutAuto($order_id, $type = '', $is_refund = false) {
        $LifeToolsOrderService = new LifeToolsOrderService();
        $LifeToolsOrder        = new LifeToolsOrder();
        $tm = time();
        if (($type == 'scenic' && cfg('life_tools_sports_order_out_refund_scenic') == 1) || ($type != 'scenic' && cfg('life_tools_sports_order_out_refund') == 1) || $is_refund) { //自动退款
            $status = 3;
            $LifeToolsOrderService->supplyRefund(['order_id' => $order_id, 'reason' => '超时未核销订单自动退款'], '超时未核销订单自动退款'); //模拟用户申请退款
            $LifeToolsOrderService->agreeRefund([$order_id], '超时未核销订单自动退款'); //同意退款
            $orderDetail = $LifeToolsOrder->getDetail(['o.order_id' => $order_id]);
            if ($orderDetail['price'] > $orderDetail['refund_money']) { //部分退款，未退款部分增加商家余额
                $LifeToolsOrderService->merchantAddMoney($order_id);
            }
        } else {
            $status = 2;
            $LifeToolsOrder->updateThis(['order_id' => $order_id], ['verify_time' => $tm]);
            $LifeToolsOrderService->changeOrderStatus($order_id, 70, '超时未核销订单自动核销（计划任务）');
        }
        (new LifeToolsOrderDetail())->updateThis(['order_id' => $order_id, 'status' => 1], [
            'status'    => $status,
            'last_time' => $tm,
        ]);
    }

}