<?php
class LichuAction extends CommonAction{
    //支付回调
	public function notify(){
        $jsonData = file_get_contents("php://input");
        $callbackData = $_POST ?: json_decode($jsonData, true);
        fdump_sql(['order_id'=>$_GET['order_id'],'order_type'=>$_GET['order_type'],$callbackData],'lichu_notify');
        $order_type = $_GET['order_type'];
        $order_id = $_GET['order_id'];
        $notice_info = $callbackData;//获取回调信息
        if($notice_info['return_code'] != '01' || $notice_info['result_code'] != '01'){//失败
            $pay_info = [
                'return_code'=>'02',
                'return_msg'=>$notice_info['return_msg']??'error',
            ];
            echo json_encode($pay_info);die;
        }
        if($order_id && $order_type){
            M('Payment_money_order')->where(['orderid'=>$order_id])->data([
                'paid_money'=>$callbackData['total_fee']/100,
//                'third_plat_data'=>json_encode([
////                    'pay_type'=>$callbackData['pay_type'],
//                    'merchant_no'=>$callbackData['merchant_no'],
//                    'terminal_id'=>$callbackData['terminal_id'],
////                    'terminal_trace'=>$callbackData['terminal_trace'],
////                    'pay_trace'=>$callbackData['pay_trace'],
//                    'total_fee'=>$callbackData['total_fee'],
//                    'out_trade_no'=>$callbackData['out_trade_no'],
//                    'channel_trade_no'=>$callbackData['channel_trade_no'],
////                    'user_id'=>$callbackData['user_id'],
//                ])
            ])->save();
        }
        $now_order = $this->get_now_order($order_id,$order_type);
        if($now_order['paid']==1){
            $pay_info =  array(
                'return_code'=>'01',
                'return_msg'=>'success'
            );
            echo json_encode($pay_info);die;
        }else {
            $payment_where = ['orderid'=>$now_order['orderid']];
            $payment_money_order = M('Payment_money_order')->where($payment_where)->find();
            $go_pay_param ['pay_type'] = $payment_money_order['pay_type'];
            $go_pay_param ['is_mobile'] = $payment_money_order['is_mobile_pay'];
            $go_pay_param['order_type'] = $payment_money_order['order_type'];
            $go_pay_param['order_id'] = $now_order['orderid'];
            $go_pay_param['is_own'] = $payment_money_order['is_own'];
            $go_pay_param['third_id'] = "";
            $go_pay_param['pay_money'] = $payment_money_order['payment_money'];
            $go_pay_param['sub_mch_id'] = '';
            $go_pay_param['error'] = 0;//利楚不支持大量查询订单
            $this->after_notice($go_pay_param,$now_order);
        }
	}
	
    public function  get_now_order($order_id,$order_type){
        switch($order_type){
            case 'group':
                $now_order =$this->get_orderid('Group_order',$order_id);
                break;
            case 'meal':
                $now_order =$this->get_orderid('Meal_order',$order_id);
                break;
            case 'takeout':
            case 'food':
            case 'foodPad':
                $now_order =$this->get_orderid('Meal_order',$order_id);
                break;
            case 'weidian':
                $now_order = D('Weidian_order')->where(array('orderid'=>$order_id))->find();
                $now_order =$this->get_orderid('Weidian_order',$order_id);
                break;
            case 'recharge':
                $now_order =$this->get_orderid('User_recharge_order',$order_id);
                break;
            case 'appoint':
                $now_order =$this->get_orderid('Appoint_order',$order_id);
                break;
            case 'waimai':
                $now_order =$this->get_orderid('Waimai_order',$order_id);
                break;
            case 'wxapp':
                $now_order =$this->get_orderid('Wxapp_order',$order_id);
                break;
            case 'store':
                $now_order =$this->get_orderid('Store_order',$order_id);
                fdump($now_order,'ss');
                break;
            case 'shop':
            case 'mall':
                $now_order = $this->get_orderid('Shop_order', $order_id);
                break;
            case 'plat':
                $now_order = $this->get_orderid('Plat_order', $order_id);
                break;
            case 'balance-appoint':
                $now_order = $this->get_orderid('Appoint_order', $order_id);
                break;
            case 'ticket':
                $now_order =$this->get_orderid('Scenic_order',$order_id);
                break;
            case 'guide':
                $now_order =$this->get_orderid('Scenic_aguide_order',$order_id);
                break;
            case 'mcard':
                $now_order =$this->get_orderid('Membership_card_order',$order_id);
                break;
            default:
                $now_order =array();
        }
        return $now_order;

    }

    public function get_orderid($table,$orderid,$offline=0){
        $order =  D($table);
        $tmp_orderid = D('Tmp_orderid');
        if($offline){
            $now_order = $order->where(array('orderid'=>$orderid))->find();
        }else{
            $now_order = $order->where(array('orderid'=>$orderid))->find();

            if(empty($now_order)){
                $res = $tmp_orderid->where(array('orderid'=>$orderid))->find();
                $now_order = $order->where(array('order_id'=>$res['order_id']))->find();
                $order->where(array('order_id'=>$res['order_id']))->setField('orderid',$orderid);
                $now_order['orderid']=$orderid;
            }
        }

        if(empty($now_order)){
            $this->error_tips('该订单不存在');
        }else{
            //            $tmp_orderid->where(array('order_id'=>$now_order['order_id']))->delete();
        }

        return $now_order;
    }
    
    public function after_notice($go_pay_param,$now_order){
        $order_type = $go_pay_param['order_type'];

        if(empty($now_order)){
            $this->error_tips('该订单不存在');
        }

        $now_order['order_type'] = $order_type;
        if($now_order['paid']==1 && $order_type!='balance-appoint'){
            $pay_info =  array(
                'return_code'=>'01',
                'return_msg'=>'success'
            );
            echo json_encode($pay_info);die;
        }

        $now_order['is_mobile_pay'] = $go_pay_param ['is_mobile'];
        switch($order_type){
            case 'group':
                $res = D('Group_order')->after_pay($go_pay_param);
                break;
            case 'meal':
            case 'takeout':
            case 'food':
            case 'foodPad':
                D('Meal_order')->after_pay($go_pay_param, $order_type);
                break;
            case 'weidian':
                $pay_info = D('Weidian_order')->after_pay($go_pay_param);
                if(($pay_info['error']==0) && isset($pay_info['url'])){  /***异步通知***/
                    $this->NoticeWDAsyn($now_order['orderid']);
                }
                break;
            case 'recharge':
                $res = D('User_recharge_order')->after_pay($go_pay_param);
                break;
            case 'waimai':
                D('Waimai_order')->after_pay($go_pay_param);
                break;
            case 'appoint':
                D('Appoint_order')->after_pay($go_pay_param);
                break;
            case 'balance-appoint':
                D('Appoint_order')->balance_after_pay($go_pay_param);
                break;
            case 'wxapp':
                D('Wxapp_order')->after_pay($go_pay_param);
                break;
            case 'store':
                D('Store_order')->after_pay($go_pay_param);
                break;
            case 'shop':
            case 'mall':
                D('Shop_order')->after_pay($go_pay_param);
                break;
            case 'plat':
                D('Plat_order')->after_pay($go_pay_param);
                break;
            case 'ticket':
                D('Scenic_order')->after_pay($go_pay_param);
                break;
            case 'guide':
                D('Scenic_aguide')->after_pay($go_pay_param);
                break;
            case 'spread':
                D('Merchant_spread_order')->after_pay($go_pay_param);
                break;
            case 'mcard':
                D('Membership_card_order')->after_pay($go_pay_param);
                break;
        }

        $pay_info =  array(
            'return_code'=>'01',
            'return_msg'=>'success'
        );
        echo json_encode($pay_info);die;
    }
}
?>