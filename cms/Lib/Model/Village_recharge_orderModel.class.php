<?php
class Village_recharge_orderModel extends Model{
	public function get_pay_order($village_id,$order_id,$is_web=false){
		$now_order = $this->get_order_by_id($village_id,$order_id);
		if(empty($now_order)){
			return array('error'=>1,'msg'=>L_('当前订单不存在！'));
		}

		if($is_web){
			$order_info = array(
				'order_id'			=>	$now_order['order_id'],
				'order_type'		=> 'sqrecharge',
				'recharge_money'	=>	floatval($now_order['money']),
				'order_name'		=>	L_('在线充值'),
				'order_num'			=>	1,
				'num'   		=> 1,
				'price' 		=> floatval($now_order['money']),
				'money' 	=> floatval($now_order['money']),
				'order_total_money' 	=> floatval($now_order['money']),


			);
		}
		return array('error'=>0,'order_info'=>$order_info);
	}
	public function get_order_by_id($village_id,$order_id){
		$condition_user_recharge_order['uid'] = $village_id;
		$condition_user_recharge_order['order_id'] = $order_id;
		return $this->field(true)->where($condition_user_recharge_order)->find();
	}
	//电脑站支付前订单处理
	public function web_befor_pay($order_info,$now_village){

		$data_user_recharge_order['last_time'] = $_SERVER['REQUEST_TIME'];
		$data_user_recharge_order['submit_order_time'] = $_SERVER['REQUEST_TIME'];
		$condition_user_recharge_order['order_id'] = $order_info['order_id'];
		if(!$this->where($condition_user_recharge_order)->data($data_user_recharge_order)->save()){
			return array('error_code'=>true,'msg'=>L_('保存订单失败！请重试或联系管理员。'));
		}
		return array('error_code'=>false,'pay_money'=>$order_info['order_total_money']);

	}


	public function after_pay($order_param){

        fdump_api(['msg' => '支付后回调',  'order_param' => $order_param], 'smsBuy/village_recharge_order_after_pay_log', 1);
		if($order_param['pay_type']!=''){
			$where['orderid'] = $order_param['order_id'];
		}else{
			$where['order_id'] = $order_param['order_id'];
		}

		$now_order = $this->field(true)->where($where)->find();
        fdump_api(['msg' => '订单',  'now_order' => $now_order], 'smsBuy/village_recharge_order_after_pay_log', 1);

		if(empty($now_order)){
			return array('error'=>1,'msg'=>L_('当前订单不存在'));
		}else if($now_order['paid'] == 1){
			return array('error'=>1,'msg'=>L_('该订单已付款！'),'url'=>$this->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay'],$now_order));
		}else{
			//得到当前社区信息，不将session作为调用值，因为可能会失效或错误。
			$now_village = D('House_village')->get_one($now_order['village_id']);
            fdump_api(['msg' => '小区信息',  'now_village' => $now_village], 'smsBuy/village_recharge_order_after_pay_log', 1);

			if(empty($now_village)){
                fdump_api(['msg' => '没有查找到此订单归属的社区，请联系管理员'], 'smsBuy/village_recharge_order_after_pay_log', 1);
				return array('error'=>1,'msg'=>L_('没有查找到此订单归属的社区，请联系管理员！'));
			}

			$data_user_recharge_order = array();
			$data_user_recharge_order['pay_time'] = $_SERVER['REQUEST_TIME'];
			$data_user_recharge_order['last_time'] = $_SERVER['REQUEST_TIME'];
			$data_user_recharge_order['payment_money'] = floatval($order_param['pay_money']);
			$data_user_recharge_order['pay_type'] = $order_param['pay_type'];
			$data_user_recharge_order['third_id'] = $order_param['third_id'];
			$data_user_recharge_order['paid'] = 1;
            fdump_api(['msg' => '订单更新',  'data_user_recharge_order' => $data_user_recharge_order], 'smsBuy/village_recharge_order_after_pay_log', 1);
			if($this->where($where)->save($data_user_recharge_order)){
                
                if($data_user_recharge_order['payment_money']>0 ){
                    $save_paid_order_record=array();
                    $save_paid_order_record['source_from']=1;
                    $save_paid_order_record['village_id']=$now_order['village_id'];
                    $pay_type=$order_param['pay_type'];
                    $save_paid_order_record['house_type']='village_recharge_order';
                    $save_paid_order_record['business_type']='village_recharge_order';
                    $save_paid_order_record['business_name']='小区余额充值';
                    $save_paid_order_record['order_id']=$now_order['order_id'];
                    $save_paid_order_record['order_no']=$now_order['orderid'] ? $now_order['orderid']:'';
                    $save_paid_order_record['table_name']='village_recharge_order';
                    $save_paid_order_record['order_money']= $now_order['money'];
                    $save_paid_order_record['pay_type']=$pay_type;
                    $save_paid_order_record['pay_order_no']=$now_order['orderid'] ? $now_order['orderid']:'';
                    $save_paid_order_record['third_transaction_no']=isset($order_param['third_id']) ? $order_param['third_id']:'';
                    $save_paid_order_record['is_online']=1;
                    $save_paid_order_record['pay_time']=time();
                    $save_paid_order_record['pay_money']= $data_user_recharge_order['payment_money'];
                    $save_paid_order_record['extra_data']=json_encode($order_param,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                    D('Paid_order_record')->data($save_paid_order_record)->add();
                }
                
				$order_param['village_id'] = $now_order['village_id'];
				$order_param['desc'] =L_('社区在线充值');
                fdump_api(['msg' => '社区在线充值',  'order_param' => $order_param], 'smsBuy/village_recharge_order_after_pay_log', 1);
				$return  = D('Village_money_list')->add_money($order_param);
                fdump_api(['msg' => '社区在线充值',  'return' => $return], 'smsBuy/village_recharge_order_after_pay_log', 1);
				$now_village = D('House_village')->get_one($now_order['village_id']);
				if($now_village['money']>0 && $now_village['status']==3){
                    fdump_api(['msg' => '更新小区状态'], 'smsBuy/village_recharge_order_after_pay_log', 1);
					M('House_village')->where(array('village_id'=>$now_order['village_id']))->setField('status',1);
				}

				D('Scroll_msg')->add_msg('village_recharge',$now_village['uid'],L_('社区【').str_replace_name($now_village['name']).L_('】于').date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).L_('充值成功！'));

				return array('error'=>0,'msg'=>L_('充值成功！'),'url'=>$this->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay'],$now_order));

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
				return U('Village_money/money_list');
			}else{
				return  U('Village_money/money_list');
			}
		}
	}


}
?>