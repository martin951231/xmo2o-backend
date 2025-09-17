<?php
/**
 * 利楚扫呗支付接口对接
 * 利楚扫呗接口文档：https://lcsw.yuque.com/docs/share/f8d585bb-e0b4-46d0-a83a-dfaedc6b55af?#
 */

class LichuPay{
    public $merchantNo;//商户号
    public $terminalId;//终端号
    public $lichuToken;//密钥
    public $url;//当前环境域名
    public function __construct()
    {
        //判断当前环境用测试环境还是正式环境
        $this->url = C('config.lichu_url_type') ? 'https://pay.lcsw.cn/lcsw' : 'http://test.lcsw.cn:8045/lcsw';
    }
    /**
     * 获取支付系统key_sign
     */
    public function getKey($param)
    {
        $signPars = "";
        ksort($param);
        foreach($param as $k => $v) {
            if("" != $v) {
                if($k=='notify_url'){
                    $signPars .= "notify_url" . "=" . $v . "&";
                }else{
                    $signPars .= $k . "=" . $v . "&";
                }
            }
        }
        $signPars .= "access_token=" . $this->lichuToken;fdump('str:'.$signPars,'getLichuKey',1);
        $key = md5($signPars);
        return $key;
    }
    
    public function pay($config,$order_info)
    {
        $this->merchantNo = $config['merchant_no'];
        $this->terminalId = $config['terminal_id'];
        $this->lichuToken = $config['token'];
        $order_info['pay_money'] = bcmul($config['pay_money'],100,0);
        $order_info['openid'] = $config['openid'];
        $order_info['wxapp_openid'] = $config['wxapp_openid'];
        $order_info['notify_url'] = $config['notify_url'];
        if($_POST['Device-Id'] == 'wxapp'){//小程序下单
            $result = $this->miniPay($order_info);
        }else{//统一下单(h5、app)
            $result = $this->jspay($order_info);
        }
        return $result;
    }
    
    /**
     * 统一下单
     */
    public function jspay($order_info)
    {
        $url = '/pay/open/jspay';
        $post = [
            'pay_ver'=>'201',
            'pay_type'=>'010',//010微信，020支付宝，060qq钱包，100翼支付,110银联云闪付  
            'service_id'=>'012',
            'merchant_no'=>strval($this->merchantNo),
            'terminal_id'=>strval($this->terminalId),
            'terminal_ip'=>strval(get_client_ip()),
            'terminal_trace'=>strval($order_info['real_orderid']),
            'terminal_time'=>strval(date('YmdHis',time())),
            'total_fee'=>strval($order_info['pay_money']),
//            'sub_appid'=>'wxe568c727d466aef9',
//            'open_id'=>'obnG9jqv11Nggbn-6YxYCrYKSOBQ',
            'sub_appid'=>strval(C('config.pay_weixin_appid')),
            'open_id'=>strval($order_info['openid']),
            'notify_url'=>strval($order_info['notify_url']),
        ];
        //获取key_sign
        $key_sign = $this->getKey($post);
//        $post['notify_url'] = C('config.site_url').'/wap.php?c=Lichu&a=index';
        $post['key_sign'] = $key_sign;
        import('ORG.Net.Http');
        $result = Http::curlPostJson($this->url.$url, json_encode($post));
//        $result = json_decode($result, true);
        fdump_sql([$this->url.$url,$post,$result], "lichu_pay");
        if($result['return_code'] === "01" && $result['result_code'] === "01"){
            $result['package'] = $result['package_str'];
            return [
                'error' => '0',
                'result' => $result
            ];
        }else{
            return [
                'error' => '1',
                'msg' => $result['return_msg'] ? $result['return_msg'] : L_('支付失败')
            ];
        }
    }
    
    /**
     * 小程序支付
     */
    public function miniPay($order_info)
    {
        $url = '/pay/open/minipay';
        $post = [
            'pay_ver'=>'201',
            'pay_type'=>'010',//010微信，020支付宝
            'service_id'=>'015',
            'merchant_no'=>$this->merchantNo,
            'terminal_id'=>$this->terminalId,
            'terminal_ip'=>get_client_ip(),
            'terminal_trace'=>$order_info['real_orderid'],
            'terminal_time'=>date('YmdHis',time()),
            'total_fee'=>$order_info['pay_money'],
//            'sub_appid'=>'wxe568c727d466aef9',
//            'open_id'=>'obnG9jqv11Nggbn-6YxYCrYKSOBQ',
            'sub_appid'=>C("config.pay_wxapp_appid"),
            'open_id'=>$order_info['openid']?:$order_info['wxapp_openid'],
            'notify_url'=>strval($order_info['notify_url']),
        ];
        //获取key_sign
        $key_sign = $this->getKey($post);
        $post['key_sign'] = $key_sign;
        import('ORG.Net.Http');
        $result = Http::curlPostJson($this->url.$url, json_encode($post));
//        $result = json_decode($result, true);
        fdump_sql([$this->url.$url,$post,$result], "lichu_pay");
        if($result['return_code'] === "01" && $result['result_code'] === "01" ){
            $result['package'] = $result['package_str'];
            return [
                'error' => '0',
                'result' => $result
            ];
        }else{
            return [
                'error' => '1',
                'msg' => $result['return_msg'] ? $result['return_msg'] : L_('支付失败')
            ];
        }
    }
    
    /**
     * 退款
     */
    public function refund($orderInfo,$config)
    {
        $this->lichuToken = $config['token'];
        $url = '/pay/open/refund';
        $post = [
            'pay_ver'=>'201',
            'pay_type'=>'010',//010微信，020支付宝，060qq钱包，100翼支付,110银联云闪付  
            'service_id'=>'030',
            'merchant_no'=>strval($config['merchant_no']??''),
            'terminal_id'=>strval($config['terminal_id']??''),
//            'terminal_ip'=>strval(get_client_ip()),
            'terminal_trace'=>strval($orderInfo['real_orderid']),
            'terminal_time'=>strval(date('YmdHis',time())),
            'refund_fee'=>strval($orderInfo['refund_fee']),
            'out_trade_no'=>strval($config['out_trade_no'])
        ];
        //获取key_sign
        $key_sign = $this->getKey($post);
        $post['key_sign'] = $key_sign;
        import('ORG.Net.Http');
        $result = Http::curlPostJson($this->url.$url, json_encode($post));
        fdump_sql([$this->url.$url,$post,$result], "lichu_back");
        if($result['return_code'] === "01" && $result['result_code'] === "01"){
            return [
                'error' => '0',
                'type' => 'ok',
                'result' => $result
            ];
        }else{
            return [
                'error' => '1',
                'msg' => $result['return_msg'] ? $result['return_msg'] : L_('支付失败')
            ];
        }
    }
}