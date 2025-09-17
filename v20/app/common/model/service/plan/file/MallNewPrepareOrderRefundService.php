<?php


namespace app\common\model\service\plan\file;


use app\mall\model\db\MallPrepareAct;
use app\mall\model\db\MallPrepareOrder;
use app\mall\model\service\MallOrderService;

class MallNewPrepareOrderRefundService
{
    /**
     * @param $order_id
     * 预售订单未支付尾款超时自动取消
     */
    public function runTask()
    {
        $where1= [['m.pay_status', '=', 1]];
        $result = (new MallPrepareAct())->getPrepare($where1);
        if(!empty($result)){
            foreach ($result as $key=>$val){
                if($val['second_pay']==0){//没点击支付尾款
                    if($val['rest_type']==0){//0=固定时间
                        if($val['rest_end_time']<time()){
                            $data['pay_status']=3;
                            $where=[['order_id','=',$val['order_id']]];
                            (new MallPrepareOrder())->updatePrepare($data,$where);
                            (new MallOrderService())->changeOrderStatus($val['order_id'],51,'预售未支付尾款超时取消(计划任务取消)');
                        }
                    }else{
                        $act_end_time=($val['pay_time']+$val['rest_start_time']+$val['rest_end_time']);//定金支付后xx天xx时xx分内支付尾款
                        if($act_end_time<time()) {
                            $data['pay_status']=3;
                            $where=[['order_id','=',$val['order_id']]];
                            (new MallPrepareOrder())->updatePrepare($data,$where);
                            (new MallOrderService())->changeOrderStatus($val['order_id'],51,'预售未支付尾款超时取消(计划任务取消)');
                        }
                    }
                }else{
                    if((time()-$val['second_pay_time'])/60>=15){
                            $data['pay_status']=3;
                            $where=[['order_id','=',$val['order_id']]];
                            (new MallPrepareOrder())->updatePrepare($data,$where);
                            (new MallOrderService())->changeOrderStatus($val['order_id'],51,'预售未支付尾款超时取消(计划任务取消)');
                    }
                }
            }
        }
    }
}