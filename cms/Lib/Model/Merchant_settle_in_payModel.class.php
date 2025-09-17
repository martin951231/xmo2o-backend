<?php
//商家入驻缴费

class Merchant_settle_in_payModel extends Model
{

    public function get_pay_order($mer_id, $order_id, $is_web = false)
    {
        $now_order = $this->get_order_by_id($mer_id,$order_id); 
		if(empty($now_order)){
			return array('error'=>1,'msg'=>L_('当前订单不存在！'));
		}

		if($is_web){
			$order_info = array(
				'order_id'			=>	$now_order['order_id'],
				'order_type'		=> 'settlein',
				'recharge_money'	=>	floatval($now_order['money']),
				'order_name'		=>	L_('入驻缴费'),
				'order_num'			=>	1,
				'num'   		=> 1,
				'price' 		=> floatval($now_order['money']),
				'money' 	=> floatval($now_order['money']),
				'order_total_money' 	=> floatval($now_order['money']),


			);
		}
		return ['error' => 0, 'order_info' => $order_info];
    }

    public function get_order_by_id($mer_id, $order_id)
    {
		$condition_user_recharge_order['uid'] = $mer_id;
		$condition_user_recharge_order['order_id'] = $order_id;
		return $this->field(true)->where($condition_user_recharge_order)->find();
	}

    //电脑站支付前订单处理
	public function web_befor_pay($order_info,$now_merchant){

		$data['submit_time'] = $_SERVER['REQUEST_TIME'];
		$condition_user_recharge_order['order_id'] = $order_info['order_id'];
		if(!$this->where($condition_user_recharge_order)->data($data)->save()){
			return array('error_code'=>true,'msg'=>L_('保存订单失败！请重试或联系管理员。'));
		}
		return array('error_code'=>false,'pay_money'=>$order_info['money']);

	}

    public function get_pay_after_url()
    {
        return C('config.site_url').'/merchant.php?g=Merchant&c=SettleIn&a=index';
    }

    public function after_pay($order_param){

		if($order_param['pay_type']!=''){
			$where['orderid'] = $order_param['order_id'];
		}else{
			$where['order_id'] = $order_param['order_id'];
		}

		$now_order = $this->field(true)->where($where)->find();

		if(empty($now_order)){
			return array('error'=>1,'msg'=>L_('当前订单不存在'));
		}else if($now_order['paid'] == 1){
			return array('error'=>1,'msg'=>L_('该订单已付款！'),'url'=>$this->get_pay_after_url());
		}else{
			//得到当前商家信息，不将session作为调用值，因为可能会失效或错误。
			$now_merchant = D('Merchant')->get_info($now_order['mer_id']);

			if(empty($now_merchant)){
				return array('error'=>1,'msg'=>L_('没有查找到此订单归属的商家，请联系管理员！'));
			}

			$data_user_recharge_order = array();
			$data_user_recharge_order['pay_time'] = $_SERVER['REQUEST_TIME'];
			$data_user_recharge_order['pay_type'] = $order_param['pay_type'];
			$data_user_recharge_order['status'] = 1;
			$data_user_recharge_order['paid'] = 1;
			if($this->where($where)->save($data_user_recharge_order)){

               	$this->calcMoneyChangeMoney($now_merchant['mer_id']); 

				D('Scroll_msg')->add_msg('settlein',$now_merchant['mer_id'],L_('商家【x1】于x2缴费成功！',array('x1'=>str_replace_name($now_merchant['name']),'x2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']))));

				return array('error'=>0,'msg'=>L_('缴费成功'),'url'=>$this->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay'],$now_order));

			}else{
				return array('error'=>1,'msg'=>L_('修改订单状态失败，请联系系统管理员！'));
			}
		}
	}


    public function calcMoney($mer_id)
    {
        $where = [];
        $where['mer_id'] = $mer_id;
        $where['paid'] = 1;
        $where['type'] = 1;
        $where['status'] = 1;
        $recharge_money = $this->where($where)->sum('money'); 
        $recharge_money = is_null($recharge_money) ? 0 : $recharge_money;
        $where = [];
        $where['mer_id'] = $mer_id; 
        $where['type'] = 2;
        $where['status'] = ['in', [20, 21]];
        $refund_money = $this->where($where)->sum('money');
        $refund_money = is_null($refund_money) ? 0 : $refund_money;
        $money = $recharge_money - $refund_money;
        return $money;
    }

	public function calcMoneyChangeMoney($mer_id)
	{
		$money = $this->calcMoney($mer_id);
		return M('Merchant')->where(['mer_id'=>$mer_id])->save(['settle_in_money'=>$money]);
	}

    
}