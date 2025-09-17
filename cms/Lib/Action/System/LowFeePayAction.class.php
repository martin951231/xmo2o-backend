<?php
/*
 * 对接低费率
 *
 */
class LowFeePayAction extends BaseAction {
    public function index(){
		if(isset($this->config['open_juhepay'])){
			redirect(U('Config/index').'&galias=Juhe&header=LowFeePay/header');
		}else if(isset($this->config['open_pufa_pay'])){
			redirect(U('Config/index').'&galias=Pufa&header=LowFeePay/header');
		}else if(isset($this->config['open_wftpay'])){
			redirect(U('Config/index').'&galias=Wftpay&header=LowFeePay/header');
		}else{
			exit('没有对接任何低费率');
		}
	}
}
