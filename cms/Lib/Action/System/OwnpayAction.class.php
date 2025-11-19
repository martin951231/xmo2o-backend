<?php
class OwnpayAction extends BaseAction{
	public function index(){

		$ownpay = D('City_ownpay')->field(true)->where(array('city_id' =>$_GET['city_id']))->find();
		if(empty($ownpay)){
			D('City_ownpay')->data(array('city_id' => $_GET['city_id']))->add();
		}else{
			foreach($ownpay as $key=>$value){
				if($key != 'city_id'){
					$ownpay[$key] = unserialize($value);
				}
			}
			$this->assign('ownpay',$ownpay);
		}
		$this->display();
	}
	public function save(){
		$_POST['weixin']['pay_weixin_client_cert']=$_POST['pay_weixin_client_cert'];
		$_POST['weixin']['pay_weixin_client_key']=$_POST['pay_weixin_client_key'];
		$_POST['yzfpay']['pay_yzf_p12']=$_POST['pay_yzf_p12'];
		$_POST['yzfpay']['pay_yzf_public_key']=$_POST['pay_yzf_public_key'];

		$_POST['yzfpay']['pay_yzf_offline_p12']=$_POST['pay_yzf_offline_p12'];
		$_POST['yzfpay']['pay_yzf_offline_public_key']=$_POST['pay_yzf_offline_public_key'];

		unset($_POST['pay_weixin_client_cert']);
		unset($_POST['pay_weixin_client_key']);
		unset($_POST['pay_yzf_p12']);
		unset($_POST['pay_yzf_public_key']);
		unset($_POST['pay_yzf_offline_p12']);
		unset($_POST['pay_yzf_offline_public_key']);
		$data_ownpay = array();
		foreach($_POST as $key=>$value){
			$data_ownpay[$key] = serialize($value);
			if(!empty($value['open'])){
				foreach($value as $vv){
					if(empty($vv)&&$vv!=0){
						$this->error('您开启了'.$this->getPayName($key).'，请完善'.$this->getPayName($key).'需要的信息');
					}
				}
			}
		}
		$where_ownpay = array('city_id' => $_POST['city_id']);
//		if(!M('City_ownpay')->where($where_ownpay)->find()){
//			M('City_ownpay')->add(array('city_id'=>$_POST['city_id']));
//		}
		$data_ownpay['city_id']=$_POST['city_id'];
		if(M('City_ownpay')->where($where_ownpay)->data($data_ownpay)->save()){
			$this->success('保存成功');
		}else{
			$this->error('保存失败，请重试');
		}
	}
	protected function getPayName($label){
		$payName = array(
			'weixin' => '微信支付',
			'tenpay' => '财付通支付',
			'yeepay' => '银行卡支付(易宝支付)',
			'allinpay' => '银行卡支付(通联支付)',
			'chinabank' => '银行卡支付(网银在线)',
		);
		return $payName[$label];
	}
}


?>