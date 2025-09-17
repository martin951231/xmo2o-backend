<?php
class Allinpay_mer{
	protected $order_info;
	protected $pay_money;
	protected $pay_type;
	protected $is_mobile;
	protected $pay_config;
	protected $user_info;
	
	public function __construct($order_info,$pay_money,$pay_type,$pay_config,$user_info,$is_mobile=0){
		$this->order_info = $order_info;
		$this->pay_money  = $pay_money;
		$this->pay_type   = $pay_type;
		$this->is_mobile   = $is_mobile;
		$this->pay_config = $pay_config;
		$this->user_info  = $user_info;
	}
	public function pay(){
        if(empty($this->pay_config['pay_allinpay_mer_id']) || empty($this->pay_config['pay_allinpay_mer_key']) || $this->pay_config['open']==0){
            return array('error'=>1,'msg'=>'通联支付关闭或缺少配置信息！请联系管理员处理或选择其他支付方式。');
        }
		if($this->is_mobile){
			return $this->mobile_pay();
		}else{
			return $this->web_pay();
		}
	}
	public function mobile_pay(){
		$data['uid'] = $this->user_info['uid'];
		$data['order_id'] = $this->order_info['order_id'];
		$data['order_type'] = $this->order_info['order_type'];
		$data['order_name'] = $this->order_info['order_name'];
		$data['order_price'] = $this->pay_money;
		$data['mer_id'] = $this->order_info['mer_id'];
		$data['store_id'] = $this->order_info['store_id'];
		$data['create_time'] = time();
        $data['is_own'] = intval($this->pay_config['is_own']);
		
		$nowtime = date("ymdHis");
		$data['allinpay_rand_id'] = $nowtime.rand(10,99).sprintf("%08d",$this->user_info['uid']);
		
		if($allinpay_order_id = M('Allinpay_order')->data($data)->add()){
			redirect(C('config.site_url').'/wap.php?g=Wap&c=Allinpay&a=index&order_id='.$data['allinpay_rand_id'].'&mer_id='.$this->order_info['mer_id']);
		}else{
			exit('银行卡支付生成订单失败');
		}
		
	}
	public function web_pay(){
		$param = base64_encode($this->order_info['order_type'].'_'.$this->order_info['order_id']);
		if($this->pay_config['is_own'] && $this->order_info['mer_id']){
			$_SESSION['own_mer_id'] = $this->order_info['mer_id'];
		}
		header('Location:' . C('config.site_url').'/index.php?c=Pay&a=return_url&pay_type=offline&param=' . $param);
		exit();
	}
	
	public function notice_url(){
		if($this->is_mobile){
			return $this->mobile_notice();
		}else{
			return $this->web_notice();
		}
	}
	public function mobile_notice(){
		exit('success');
	}
	public function web_notice(){
		exit('success');
	}
	public function return_url(){
        $result = $_POST;
        $result_orderid = explode('_',$result['cusorderid']);
        $now_order = M('Allinpay_order')->where(array('order_id'=>$result_orderid[1]))->find();
        $info = M('Merchant_ownpay')->where(array('mer_id'=>$now_order['mer_id']))->find();
        $info = unserialize($info['allinpay_mer']);
        //验签
        $check = $this->ValidSign($result,$info['pay_allinpay_mer_key']);
        if(!$check){
            fdump_sql($result['cusorderid'].'验签失败！','allinpay_ownpay');
            return array('error'=>1,'msg'=>'验签失败！');
        }
        if($result['trxstatus']=='0000'){
            $data['fee'] = $result['fee'];
            $data['pay_time'] = time();
            $data['paid_time'] = $result['paytime'];
            $data['trxid'] = $result['trxid']; //收银宝平台流水
            $data['paid_money'] = $result['trxamt'] / 100;
            $data['is_paid'] = 1;
            if($now_order['is_paid']==1) exit;
            if(!M('Allinpay_order')->where(array('order_id'=>$result_orderid[1]))->save($data)){
                fdump_sql($result['cusorderid'].'支付保存失败！','allinpay_ownpay');
                return array('error'=>1,'msg'=>'支付保存失败！');
            }
        }
        $now_order = M('Allinpay_order')->where(array('order_id'=>$result_orderid[1]))->find();
		if(empty($now_order)){
            return array('error'=>1,'msg'=>'该订单不存在');
        }
		if(empty($now_order['is_paid'])){
            return array('error'=>1,'msg'=>'该订单暂未付款');
		}
		
		$order_param['pay_type'] = 'allinpay_mer';
		$order_param['is_mobile'] = $result['trxreserved'] ? $result['trxreserved'] : $this->is_mobile; //交易时传递的支付端
		$order_param['order_type'] = $now_order['order_type'];
        $order_param['is_own'] = intval($result_orderid[2]);
		$order_param['order_id'] = $now_order['order_id'];
		$order_param['third_id'] = $now_order['trxid'];
		$order_param['pay_money'] = $now_order['paid_money'];
		
		return array('error' => 0,'order_param' => $order_param);
	}

    /**
     * 将参数数组签名
     */
    public function SignArray(array $array,$appkey){
        $array['key'] = $appkey;// 将key放到数组中一起进行排序和组装
        ksort($array);
        $blankStr = $this->ToUrlParams($array);
        $sign = strtoupper(md5($blankStr));
        return $sign;
    }

    public function ToUrlParams(array $array)
    {
        $buff = "";
        foreach ($array as $k => $v)
        {
            if($v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }
    //验签
    public function ValidSign(array $array,$appkey){
        $sign = $array['sign'];
        unset($array['sign']);
        $array['key'] = $appkey;
        $mySign = $this->SignArray($array, $appkey);
        return strtolower($sign) == strtolower($mySign);
    }
    //退款
    public function refund(){
        $post['cusid'] = $this->pay_config['pay_allinpay_mer_id'];
        $post['appid'] = $this->pay_config['pay_allinpay_mer_appid'];
        $post['trxamt'] = $this->pay_money * 100;
        $post['orderid'] = $this->order_info['order_type'].'_'.$this->order_info['order_id'].'_'.$_SERVER['REQUEST_TIME'];
        $post['oldtrxid'] = $this->order_info['third_id'];
        $s_data['key'] = $this->pay_config['pay_allinpay_mer_key'];
        $url =  C('config.allinpay_mer_url').'/qpay/refund';
        $return = $this->sendAllinpay($url,$s_data,$post);
        //验签
        $check = $this->ValidSign($return,$s_data['key']);
        if(!$check){
            return array('error'=>1,'type'=>'fail','msg'=>'退款申请失败！错误原因：验签失败!','refund_param'=>array());
        }
        if($return['trxstatus'] != '0000'){
            fdump_sql($return,'allinpay_refund_failed');
            return array('error'=>1,'type'=>'fail','msg'=>'退款申请失败！如果重试多次还是失败请联系系统管理员。'.$return['errmsg'],'refund_param'=>array());
        }else{
            return array('error'=>0,'type'=>'ok','msg'=>'退款申请成功！','refund_param'=>$return);
        }
    }
    private function sendAllinpay($url,$s_data,$data){

        $key = $s_data['key'];
        $nowtime = date("ymdHis");
        $randomstr = $nowtime.rand(10,99).sprintf("%08d",$this->user_session['uid']);
        $postData = $data;
        $postData['version'] = 11;
        $postData['randomstr'] = $randomstr;
        $postData['reqtime'] = date("YmdHis");
        $postData['reqip'] = get_client_ip(0);
        $sign = $this->SignArray($postData,$key);
        $postData['sign'] = $sign;
        import('ORG.Net.Http');
        $http = new Http();
        $return = $http->curlPostOwn($url, $postData);
        return json_decode($return,true);;
    }
}
?>