<?php
/**
 * superpay通道
 * add by lumin
 * 2020-05-25
 */

namespace app\pay\model\service\channel;

class SuperpayService{
	private $env = '';
	private $config = [];//配置参数

	public function __construct($env, $config = []){
		$this->env = $env;
		$this->config = $config;
	}

	public function pay($order_no, $money){
		$return = [
			'error' => 0,
			'msg' => '',
			'data' => []
		];
		if(empty($order_no) || empty($money)){
			$return['error'] = 1;
			$return['msg'] = '订单ID或支付金额必填！';
			return $return;
		}
		
	}
}