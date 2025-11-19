<?php
class Sms_buy_orderModel extends Model{

	public function after_pay($order_param){

        fdump_api(['msg' => '支付后回调',  'order_param' => $order_param], 'smsBuy/Sms_buy_order_after_pay_log', 1);
        
		if($order_param['pay_type']!=''){
			$where['orderid'] = $order_param['order_id'];
		}else{
			$where['order_id'] = $order_param['order_id'];
		}

		$now_order = $this->field(true)->where($where)->find();
        fdump_api(['msg' => '订单信息',  'now_order' => $now_order], 'smsBuy/Sms_buy_order_after_pay_log', 1);
		if(empty($now_order)){
			return array('error'=>1,'msg'=>L_('当前订单不存在'));
		}else if($now_order['paid'] == 1){
			return array('error'=>1,'msg'=>L_('该订单已付款！'),'url'=>$this->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay'],$now_order));
		}else{
			//得到当前社区信息，不将session作为调用值，因为可能会失效或错误。
			$now_village = D('House_village')->get_one($now_order['type_id']);
            fdump_api(['msg' => '小区信息',  'now_village' => $now_village], 'smsBuy/Sms_buy_order_after_pay_log', 1);
			if(empty($now_village)){
                fdump_api(['msg' => '没有查找到此订单归属的社区，请联系管理员'], 'smsBuy/Sms_buy_order_after_pay_log', 1);
				return array('error'=>1,'msg'=>L_('没有查找到此订单归属的社区，请联系管理员！'));
			}

			$data_user_recharge_order = array();
			$data_user_recharge_order['pay_time'] = $_SERVER['REQUEST_TIME'];
			$data_user_recharge_order['pay_type'] = $order_param['pay_type'];
			$data_user_recharge_order['paid'] = 1;
			if(isset($now_order['extra_data'])){
                $data_user_recharge_order['extra_data']=json_encode($order_param,JSON_UNESCAPED_UNICODE);
            }
            fdump_api(['msg' => '更新订单信息',  'data_user_recharge_order' => $data_user_recharge_order], 'smsBuy/Sms_buy_order_after_pay_log', 1);
			if($this->where($where)->save($data_user_recharge_order)){
                if($now_order['operation_type']==0 && $now_order['payment_money']>0 ){
                    $save_paid_order_record=array();
                    if($now_order['type']==1){
                        $save_paid_order_record['source_from']=1;
                        $save_paid_order_record['village_id']=$now_order['type_id'];
                    }
                    $pay_type=$order_param['pay_type'];
                    if($order_param['pay_type']=='yue'){
                        $pay_type='balance';
                    }
                    $save_paid_order_record['house_type']='sms_buy_order';
                    $save_paid_order_record['business_type']='sms_buy_order';
                    $save_paid_order_record['business_name']='小区购买短信';
                    $save_paid_order_record['order_id']=$now_order['order_id'];
                    $save_paid_order_record['order_no']=$now_order['orderid'] ? $now_order['orderid']:'';
                    $save_paid_order_record['table_name']='sms_buy_order';
                    $save_paid_order_record['order_money']= $now_order['payment_money'];
                    $save_paid_order_record['pay_type']=$pay_type;
                    $save_paid_order_record['pay_order_no']=$now_order['orderid'] ? $now_order['orderid']:'';
                    $save_paid_order_record['third_transaction_no']=isset($order_param['third_id']) ? $order_param['third_id']:'';
                    $save_paid_order_record['is_online']=1;
                    $save_paid_order_record['pay_time']=time();
                    if($order_param['pay_type']=='yue'){
                        $save_paid_order_record['balance_money']= $now_order['payment_money'];
                        $save_paid_order_record['pay_type_from']='system_balance';
                    }else{
                        $save_paid_order_record['pay_money']= $now_order['payment_money'];
                    }
                    $save_paid_order_record['extra_data']=json_encode($order_param,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                    D('Paid_order_record')->data($save_paid_order_record)->add();
                }
                
                fdump_api(['msg' => '增加小区短信数',  'sms_number' => $now_order['sms_number']], 'smsBuy/Sms_buy_order_after_pay_log', 1);
				M('House_village')->where(array('village_id'=>$now_order['type_id']))->setInc('now_sms_number',$now_order['sms_number']);

				$order_param['village_id'] = $now_order['type_id'];
				$order_param['desc'] =L_('小区购买短信');
                fdump_api(['msg' => '小区购买短信',  'order_param' => $order_param], 'smsBuy/village_recharge_order_after_pay_log', 1);
				$return  = D('Village_money_list')->add_money($order_param);
                fdump_api(['msg' => '小区购买短信',  'return' => $return], 'smsBuy/village_recharge_order_after_pay_log', 1);
	
				return array('error'=>0,'msg'=>L_('短信充值成功！'),'url'=>$this->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay'],$now_order));

			}else{
				return array('error'=>1,'msg'=>L_('修改订单状态失败，请联系系统管理员！'));
			}
		}
	}

	public function get_pay_after_url($label,$is_mobile = false,$now_order){
		if($label){
			$labelArr = explode('_',$label);

			if($labelArr[0] == 'wap'){
				switch($labelArr[1]){
				}
			}else{


			}
		}else{
			if($is_mobile){
				return U('Village_money/sms_note');
			}else{
				return  U('Village_money/sms_note');
			}
		}
	}


}
?>