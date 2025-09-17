<?php


class nmgpay{
	protected $order_info;
	protected $pay_money;
	protected $pay_type;
	protected $is_mobile;
	protected $pay_config;
	protected $user_info;
	protected $pay_url;
	protected $URL;
	protected $merchant;
	private  $appsecret;
	private  $appid;
	private  $shop_openid ;

	public function __construct($order_info = array(),$pay_money = 0,$pay_type = 'nmgpay',$pay_config = array(),$user_info = array(),$is_mobile=1){
		$this->order_info = $order_info;
		$this->merchant = $order_info['merchant'];
		$this->is_mobile = $is_mobile;
		$this->appid = C('config.nmgzhcs_appid');
		$this->appsecret = C('config.nmgzhcs_appsecret');
		$this->user_info = D('User')->get_user($order_info['uid']);
	}

	public function createSign($paramData )
	{
		return strtoupper(md5($paramData.$this->appsecret));
	}



	public function pay(){
		if($this->is_mobile == 2){
			return $this->app_pay();
		}elseif($this->is_mobile){
			return $this->mobile_pay();
		}else{
			return $this->web_pay();
		}
	}


	public function web_pay(){

	}

	public function mobile_pay($pay_money){
		$pay_order = $this->get_pay_order($pay_money);
		return $pay_order;
		return array('error'=>0,'url'=>'');
	}

	public function get_user_info(){
		$openid = $this->user_info['nmgzhcs_openid'];

		$appid = C('config.nmgzhcs_appid');
		$appsecret = C('config.nmgzhcs_appsecret');

		$url = C('config.nmgzhcs_base_url') . '/api/member/memberinfo';

		$params=array(
			'appid'=>$appid,
			'openid'=>$openid,
		);
		ksort($params);
		$paramsJoined = array();
		foreach($params as $param => $value) {
			$paramsJoined[] = "$param=$value";
		}
		$paramData = implode('&', $paramsJoined);

		$sign = strtoupper(md5($paramData.$appsecret));

		$params['sign'] = $sign;

		$res = httpRequest($url,'POST',$params,$this->header);
		$res = json_decode($res[1],true);
		return $res;
	}


	public function get_pay_order($pay_money){
		$order = $this->order_info;
		$time = date("Y-m-d H:i:s");
		$nmg_user_info = $this->get_user_info();

		$params=array(
			"appid" => $this->appid,
			"shop_openid" => $this->merchant['shop_openid'],
			'order_money' => $pay_money,
			'out_trade_no' => $this->order_info['order_type'].'_'.$this->order_info['order_id'],
			'assign_type' => $this->order_info['order_type'],
			'member_openid' => $this->user_info['nmgzhcs_openid'],
			'if_discount' => 0,
			'callback_url' =>  C('config.site_url').'/source/nmgpay_notice.php',
			"jump_url" => C('config.site_url').'/index.php?c=LowFeePayNotice&a=nmgpay_order_back&out_trade_no='.$this->order_info['order_type'].'_'.$this->order_info['order_id'],
			"no_discount" => 0,
			//"card_no" => $nmg_user_info['data']['card_code'],
		);
		fdump($this->order_info,'sssss',true);



		ksort($params);
		$paramsJoined = array();
		foreach($params as $param => $value) {
			$paramsJoined[] = "$param=$value";
		}
		$paramData = implode('&', $paramsJoined);
		$sign = $this->createSign($paramData);
		$params['sign'] = $sign;
		fdump($params,'params');
		$res = httpRequest(C('config.nmgzhcs_base_url')."/api/pay/pre_order",'POST',$params);
		fdump($res,'sssss',true);
		$res = json_decode($res[1],true);
		
		fdump($res,'sssss',true);
    if($res['status']=='y'){
		redirect(C('config.nmgzhcs_base_url').'/sk/q/h5_pay?order_code='.$res['data']['order_code']);
      	
    }
		return array('error'=>1,'msg'=>'支付错误信息：'.($res['info'] ? $res['info'] : '无信息返回'));

	}

	/*查找微信订单信息*/
	public function query_order($out_trade_no){
		$appid = C('config.nmgzhcs_appid');
		$request=array(
			'appid'=>$appid,
			'out_trade_no'=>$out_trade_no,
		);

		ksort($request);
		$paramsJoined = array();
		foreach($request as $param => $value) {
			$paramsJoined[] = "$param=$value";
		}
		$paramData = implode('&', $paramsJoined);

		$request['sign'] =$this->createSign($paramData);

		$res = httpRequest(C('config.nmgzhcs_base_url').'/api/pay/order_query','POST',$request);

		$res = json_decode($res[1],true);


		if($res['status']='y' && $res['data']['pay_status']==2){
			$result = $res['data'];
			$order_id = explode('_',$result['out_trade_no']);
			$query_pay_param = array(
					'third_id'=>$result['order_code'],
					'pay_money'=>$result['pay_money'],
					'order_id'=>$order_id[1],
					'order_type'=>$order_id[0],
					'pay_type'=>'nmgpay',
					'error'=>0,
					'is_mobile'=>1,
					'errorMsg'=>'',
					'pay_time'=>$result['pay_time'],
					'is_own'=>0,
			);
			

			return $query_pay_param;
		}else if($res['errorCode']){

			return array('error'=>1,'msg'=>'订单状态：'.$res['errorMsg']);
		}else{
			return array('error'=>1,'msg'=>'查询失败');
		}

	}
	
	
    /* 退款 */
	public function refund()
	{
		$request['appid'] =$this->appid;
        $out_trade_no=$this->order_info['order_type'].'_'.$this->order_info['order_id'];
        $request['out_trade_no'] =$out_trade_no;
        $request['revoke_money'] = $this->order_info['payment_refund_money'] ? $this->order_info['payment_refund_money'] : $this->order_info['payment_money'];
		$request['note'] = '1';

		ksort($request);
		$paramsJoined = array();
		foreach($request as $param => $value) {
			$paramsJoined[] = "$param=$value";
		}
		$paramData = implode('&', $paramsJoined);

		$sign = $this->createSign($paramData);

		$request['sign'] = $sign;
		
		$this->refund_url = C('config.nmgzhcs_base_url').'/api/pay/revoke_order';
		//dump($request);
		$res = httpRequest($this->refund_url,'POST',$request);
		$res = json_decode($res[1],true);
		fdump($request,'nmgpay_refund',true);
		fdump($res,'nmgpay_refund',true);
		if($res['status'] == 'n'){
			return array('error'=>1,'type'=>'fail','msg'=>'退款申请失败！如果重试多次还是失败请联系系统管理员。'.$res['info'],'refund_param'=>array());
		}else{
			return array('error'=>0,'type'=>'ok','msg'=>'退款申请成功！','refund_param'=>$res);
		}
	}
}
?>