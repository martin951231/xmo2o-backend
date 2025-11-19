<?php

class AlipayApp
{
    protected $order_info;

    protected $pay_money;

    protected $pay_type;

    protected $is_mobile;

    protected $user_info;

    protected $config;

    public function __construct($order_info=array(),$pay_money=0,$pay_type='alipay',$pay_config=array(),$user_info=array(),$is_mobile=0)
    {
        $this->order_info = $order_info;
        
        $this->pay_money = $pay_money;
        
        $this->pay_type = $pay_type;
        
        $this->is_mobile = $is_mobile;
        
        $this->user_info = $user_info;
        
        $this->config = array(
            // 应用ID,您的APPID。
            'app_id' => $pay_config['new_pay_alipay_app_appid'],
            // 商户私钥，您的原始格式RSA私钥
            'alipay_private_key' => $pay_config['new_pay_alipay_app_private_key'],
            // 异步通知地址
            'notify_url' => C('config.site_url') . '/source/appapi_alipay_notice.php',
            // 异步通知地址
            'h5_notify_url' => C('config.site_url') . '/source/appapi_alipayh5_notice.php',
            // 同步跳转
            'return_url' => C('config.site_url') . '/source/appapi_alipay_return.php',
            // 编码格式
            'charset' => "UTF-8",
            // 签名方式
            'sign_type' => 'RSA2',
            // 支付宝网关
            'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
            // 支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
            'alipay_public_key' => $pay_config['new_pay_alipay_app_public_key']
        );
    }

    public function pay()
    {
        if ($this->is_mobile==2 && $_POST['app_type'] != 'packapp') {
            return $this->app_pay();
        } else if($this->is_mobile) {
            return $this->mobile_pay();
        } else {
            return $this->web_pay();
        }
    }

    public function mobile_pay()
    {
        import('@.ORG.pay.AlipayNew.aop.AopClient','','.php');
        import('@.ORG.pay.AlipayNew.aop.SignData','','.php');
        import('@.ORG.pay.AlipayNew.aop.request.AlipayTradeCreateRequest','','.php');
        import('@.ORG.pay.AlipayNew.aop.AlipayTradeRefundRequest','','.php');
        $aop = new AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $this->config['app_id'];
        $aop->rsaPrivateKey = $this->config['alipay_private_key'];
        $aop->alipayrsaPublicKey=$this->config['alipay_public_key'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='utf-8';
        $aop->format='json';
        $request = new AlipayTradeCreateRequest ();
        $biz_content = array(
            'timeout_express' => "1d",
            'out_trade_no' => $this->order_info['order_type'].'_'. $this->order_info['order_id'],

            'product_code' => 'FACE_TO_FACE_PAYMENT',
            'total_amount' => $this->pay_money,
            'subject' => '订单编号' . $this->order_info['order_id'],
            'body' => '订单编号' . $this->order_info['order_id'],
        );
        
        session_start();
        if($_SESSION['alipay_uid']){
            $biz_content['buyer_id'] = $_SESSION['alipay_uid'];
        }
         
        $request->setBizContent(json_encode($biz_content,JSON_UNESCAPED_UNICODE));
        if($_POST['app_type'] == 'packapp'){
            $request->setNotifyUrl($this->config['h5_notify_url']);
        }
        else{
            $request->setNotifyUrl($this->config['notify_url']);
        }
        $result = $aop->execute ( $request); 

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;

        if(!empty($resultCode) && $resultCode == 10000){
            return ['error'=>0, 'tradeNO'=>$result->$responseNode->trade_no];
        } else {
            return ['error'=>1, 'msg'=>'发起支付失败。' . $result->$responseNode->msg];
            echo "失败";
        }
    }

    public function app_pay()
    {
        import('@.ORG.pay.AlipayNew.aop.AopClient','','.php');
        import('@.ORG.pay.AlipayNew.aop.SignData','','.php');
        import('@.ORG.pay.AlipayNew.aop.request.AlipayTradeAppPayRequest','','.php');
        import('@.ORG.pay.AlipayNew.aop.AlipayTradeRefundRequest','','.php');
        $aop = new AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $this->config['app_id'];
        $aop->rsaPrivateKey = $this->config['alipay_private_key'];
        $aop->alipayrsaPublicKey=$this->config['alipay_public_key'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='utf-8';
        $aop->format='json';
        $request = new AlipayTradeAppPayRequest ();
        $request->setBizContent("{" .
            "\"total_amount\":\"".$this->pay_money."\"," .
            "\"subject\":\"订单编号".$this->order_info['order_id']."\"," .
            "\"out_trade_no\":\"".$this->order_info['order_type']."_".$this->order_info['order_id']."\"" .
            "  }");
        if($_POST['app_type'] == 'packapp'){
            $request->setNotifyUrl($this->config['h5_notify_url']);
        }
        else{
            $request->setNotifyUrl($this->config['notify_url']);
        }
        // $biz_content = array(
        //     'timeout_express' => "1d",
        //     'out_trade_no' => $this->order_info['order_type'].'_'. $this->order_info['orderid'],

        //     'product_code' => 'QUICK_MSECURITY_PAY',
        //     'total_amount' => $this->pay_money,
        //     'subject' => '订单编号' . $this->order_info['order_id'],
        //     'body' => '订单编号' . $this->order_info['order_id'],
        // );
        // $request->setBizContent(json_encode($biz_content,JSON_UNESCAPED_UNICODE));
        // $request->setNotifyUrl($this->config['notify_url']);
        // $request->setReturnUrl($this->config['return_url']);
        $response = $aop->sdkExecute($request);
        // echo htmlspecialchars($response);exit;

        return ['error'=>0, 'orderStr'=>$response];
        // $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        // $resultCode = $result->$responseNode->code;
        // if(!empty($resultCode)&&$resultCode == 10000){
            
        // } else {
        //     echo "失败";
        // }
    }
    
    // 异步通知
    public function notice_url()
    {
        if ($this->is_mobile) {
            return $this->mobile_notice();
        } elseif ($this->is_mobile == 2) {
            return $this->app_notice();
        } else {
            return $this->web_notice();
        }
    }



    public function refund()
    {
        import('@.ORG.pay.Aop.AopClient');
        import('@.ORG.pay.Aop.SignData');
        import('@.ORG.pay.Aop.AlipayTradePayRequest');
        import('@.ORG.pay.Aop.AlipayTradeRefundRequest');
	
        $aop = new AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $this->config['app_id'];
        $aop->rsaPrivateKey = $this->config['alipay_private_key'];
        $aop->alipayrsaPublicKey=$this->config['alipay_public_key'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset="UTF-8";
        $aop->format='json';
        $request = new AlipayTradeRefundRequest ();
        $out_trade_no = $this->order_info['order_type'].'_'. $this->order_info['orderid'];
		$trade_no = $this->order_info['third_id'];
        $payment_money_order = M("Payment_money_order")->where(['orderid' => $this->order_info['orderid'],'order_type'=>$this->order_info['order_type']])->find();
        /*if($payment_money_order && $payment_money_order['pay_rate'] > 0){
            $this->pay_money = getFormatNumber($payment_money_order['pay_rate'] * $this->pay_money);
        }*/
        $reqData = [
            'out_trade_no' => $out_trade_no,
            'trade_no' => $trade_no,
            'refund_amount' => $this->pay_money,
            'refund_reason' => '正常退款',
            'out_request_no' => $out_trade_no . '_' . $_SERVER['REQUEST_TIME']
        ];
        $request->setBizContent((string)json_encode($reqData));
        $result = $aop->execute ( $request);


        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            return array('error'=>0,'type'=>'ok','msg'=>'退款申请成功！请注意查收“支付宝”给您发的退款通知。','refund_param'=> $result->$responseNode->msg);
        } else {
            fdump_sql(['req' => $reqData, 'resp' => $result], 'AlipayApp_refund_fail');
            return array('error'=>1,'type'=>'fail','msg'=>'退款申请失败！如果重试多次还是失败请联系系统管理员。' . $result->$responseNode->msg.($result->$responseNode->sub_msg ? '(业务错误信息:'.$result->$responseNode->sub_msg.')' : ''),'refund_param'=> $result->$responseNode->msg.($result->$responseNode->sub_msg ? '(业务错误信息:'.$result->$responseNode->sub_msg.')' : ''));
        }
    }

}
?>