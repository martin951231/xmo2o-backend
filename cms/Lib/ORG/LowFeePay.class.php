<?php
include_once 'LowFeePay/Config.class.php';

interface PayMethod{
    public function pay($order,$pay_info);
    public function refund($order);
    public function check($order);
    public function notice($sign_data);
    public function scan_pay($order);
}

 class LowFeePay implements PayMethod {
    private $adaptee;
    function __construct($pay_type='juhepay') {
        if($pay_type=='juhepay'){
            $pay = new JuHePay();
        }else if($pay_type=='pufapay'){
            $pay = new PuFaPay();
        }else if($pay_type=='wftpay'){
            $pay = new Wftpay();
        }else if($pay_type == 'paylinx'){
            $pay = new Paylinx();
        }else if($pay_type == 'wepayez'){
            $pay = new Wepayez();
        }else if($pay_type == 'weikebaba'){
            $pay = new Weikebaba();
        }else if($pay_type == 'fubei'){
            $pay = new FubeiPay();
        }else if($pay_type == 'xiaoma'){
            $pay = new Xiaoma();
        }else if($pay_type == 'nong'){
            $pay = new Nong();
        }else if($pay_type == 'xkypay'){
            $pay = new Xkypay();
        }

        $this->adaptee = $pay;
    }

    public function __set($property, $value) {
        $this->adaptee->$property=$value;
    }

	public function scan_pay($order){
        return $this->adaptee->scan_pay($order);
    }
	
    public function pay($order,$pay_info=''){
        return $this->adaptee->pay($order,$pay_info);
    }

    public function set_config($param){
        return $this->adaptee->set_config($param);
    }
    public function refund($order){
        return $this->adaptee->refund($order);
    }
    public function check($order){
        return $this->adaptee->check($order);
    }
    public function notice($sign_data){
       return  $this->adaptee->notice($sign_data);
    }

}


?>
