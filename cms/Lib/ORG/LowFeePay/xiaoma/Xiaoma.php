<?php
/**
 * 航山百业
 * 定制小马服务商支付需求
 */
class Xiaoma{
	protected $DockingDeviceID;//小马提供的
	protected $DeviceID;//由DockingDeviceID+用户自定义字段组成，需唯一
	protected $Devicesecret;//由小马的“设备注册”接口返回的
	protected $GUID;//由小马哥服务商提供，在小马哥商家后台首页全选，最下方可以看到   (13CFjfH9w3p)
	protected $DockingSecret;//小马提供的
	protected $Mima;//退款授权密码
	public function __construct(){
		$this->DockingDeviceID 			= C('config.xiaoma_docking_device_id');
		$this->DeviceID 			= C('config.xiaoma_device_id');
		$this->Devicesecret			= C('config.xiaoma_device_secret');
		$this->GUID 				= C('config.xiaoma_guid');
		$this->DockingSecret			= C('config.xiaoma_docking_secret');
		$this->Mima =	C("config.xiaoma_mima");
	}

	private function getSign($arr){
		$signPars = "";
		ksort($arr);
		foreach($arr as $k => $v) {
			if("" != $v && "sign" != $k) {
				$signPars .= $k . "=" . $v . "&";
			}
		}
		$signPars .= $this->DockingSecret;
		$signPars = strtolower($signPars);
	
		return strtoupper(md5($signPars));
	}

	//调小马“设备注册”接口，全局只需要调起一次，然后把结果保存到config中。
	// public function reg(){
	// 	$gateway_url = 'https://docking.aduer.com/api/RegisterDevice/';
	// 	$post = [
	// 		'DockingDeviceID' => $this->DockingDeviceID,
	// 		'DeviceId' => $this->DeviceID,
	// 		'DeviceName' => 'hsby',
	// 		'GUID' => $this->GUID,
	// 		'timeStamp' => time(),
	// 		'nonceStr' => createRandomStr(32)
	// 	];
	// 	$post['Sign'] = $this->getSign($post);
	// 	dump($post);
	// }

	//调起支付(微信app支付)
	public function pay($order){
		$order_id_arr = explode('_',$order['order_id']);
		M('Payment_money_order')->where(array('orderid'=>$order_id_arr[1],'order_type'=>$order_id_arr[0]))->data(array('pay_method'=>'xiaoma','orderNo'=>$order['order_id'],'is_own'=>intval($order_id_arr[2])))->save();
		//支付网关
		$gateway_url = 'https://docking.aduer.com/api/WeiXinAppPayOrder/';
		$post = [
			'DockingDeviceID' => $this->DockingDeviceID,
			'DeviceID' => $this->DeviceID,
			'Devicesecret' => $this->Devicesecret,
			'payMoney' => $order['order_total_money'],
			'DockingOrderID' => $order['order_id'],
			'timeStamp' => time(),
			'nonceStr' => createRandomStr(32),
			'orderTitle' => "订单号".$order['order_id']
		];
		$post['sign'] = $this->getSign($post);

		import('ORG.Net.Http');
		$http = new Http();
		$return = $http->curlPostOwnWithHeader($gateway_url, $post, []);

		$returnArr = json_decode($return, true);
		if($returnArr['success'] == '1'){
			return ['error' => 0, 'weixin_param' => $returnArr['ReObj']['WeChatAppPay']];
		}
		else{
			return ['error' => 1, 'msg' => '支付返回：'.$returnArr['msg']];
		}
	}

	public function notice($sign_data){
		$order_id_arr = explode('_',$_POST['DockingOrderID']);
		$order_param['pay_type'] = 'weixin';
		$order_param['is_mobile'] = 2;
		$order_param['order_type'] = $order_id_arr[0];
		$order_param['order_id'] = $order_id_arr[1];
		$order_param['is_own'] = intval($order_id_arr[2]);
		$order_param['third_id'] = $_POST['OrderID'];
		$order_param['pay_money'] = $_POST['OrderMoney'];
		return array('error'=>0,'order_param'=>$order_param);
	}

	public function refund($order){
		$gateway_url = 'https://docking.aduer.com/api/Refund/';
		$post = [
			'dockingDeviceID' => $this->DockingDeviceID,
			'deviceID' => $this->DeviceID,
			'devicesecret' => $this->Devicesecret,
			'remoney' => $order['payment_money'],
			'dockingOrderID' => $order['orderNo'],
			'refundOrderID' => substr($order['order_type'].'_'.$order['orderid'].'_'.strrev($_SERVER['REQUEST_TIME']),0,32),
			'timeStamp' => time(),
			'operatepass' => $this->Mima,
			'nonceStr' => createRandomStr(32)
		];
		$post['sign'] = $this->getSign($post);
		fdump($post,"lumin_rrr",1);

		import('ORG.Net.Http');
		$http = new Http();
		$return = $http->curlPostOwnWithHeader($gateway_url, $post, []);

		$returnArr = json_decode($return, true);
		fdump($returnArr,"lumin_rrr",1);
		if($returnArr['success'] == '1'){
			return array(
				'error' => 0,
				'type' => 'ok',
				'refund_money'   => $order['payment_money'],
				'refund_param'   => serialize($returnArr),
			);
		}
		else{
			return ['error' => 1, 'msg' => '支付返回：'.$returnArr['msg']];
		}
	}
}