<?php
/**
 * @desc Created by PhpStorm.
 * @author: lumin
 * since: 2020/7/8 10:34 上午
 */

namespace app\pay\model\service\channel;
use net\Http;
use think\Exception;

class WftpayService{
    private $agent = '';
    private $config = [];//配置参数

    public function __construct($agent, $config = []){
        $this->agent = $agent;
        $this->config = $config;
    }

    public function pay($order_no, $money, $extra_cfg){
        if(empty($order_no) || empty($money)){
            throw new \think\Exception("订单ID或支付金额必填！");
        }
        switch ($this->agent) {
            case 'wechat_h5'://微信端
                if($this->config['pay_type'] == 'wechat'){
                    return $this->Wechatpay($order_no, $money, $extra_cfg);
                }
                if($this->config['pay_type'] == 'alipay'){
                    return $this->Alipay($order_no, $money, $extra_cfg);
                }
                break;
            case 'wechat_mini'://微信小程序
                return $this->WechatMinipay($order_no, $money, $extra_cfg);
                break;
            case 'h5'://普通手机浏览器
                return $this->H5pay($order_no, $money, $extra_cfg);
                break;
            default:
                throw new \think\Exception("当前端口未对接，不能使用");
                break;
        }
    }

    private function WechatMinipay($order_no, $money, $extra_cfg)
    {
        if (empty($this->config['wxapp_openid'])) {
            throw new \think\Exception("未获取到openid，无法调起支付");
        }
        $pay_post_data = array(
            'service' => 'pay.weixin.jspay',
            'version' => '2.0',
            'charset' => 'UTF-8',
            'sign_type' => 'MD5',
            'mch_id' => $this->config['wft_store_id'],
            'is_raw' => '1',
            'is_minipg' => '1',
            'out_trade_no' => $order_no,
            'body' => $this->config['order_name'] ?? '订单号：'.$extra_cfg['business_order_sn'],
            'sub_openid' => $this->config['wxapp_openid'],
            'sub_appid' => $this->config['wft_pay_wxapp_appid'],
            'total_fee' => $money * 100,
            'mch_create_ip' => request()->ip(),
            'notify_url' => (String) url('wftpay_notify', [], false, true),
            'nonce_str' => create_random_str(32),
            'limit_credit_pay' => '0',
        );
        $pay_post_data['sign'] = $this->getSign($pay_post_data);

        $xml = $this->toXml($pay_post_data);
        $gateUrl = 'https://pay.hstypay.com/v2/pay/gateway';
        $return = (new Http())->curlPostXml($gateUrl, $xml);

        if(isset($return['status']) && $return['status'] !== '0'){
            throw new \think\Exception('调用低费率通道支付失败，原因：'.$return['message']);
        }else if(isset($return['result_code']) && $return['result_code'] > 0){
            throw new \think\Exception('调用低费率通道支付失败，原因：'.$return['err_msg']);
        }else if(isset($return['pay_info'])){
            return [
                'type' => 'jssdk',
                'info' => $return['pay_info']
            ];
        }else{
            throw new \think\Exception('调用低费率通道出现未知错误！', 1004);
        }

    }

    private function Wechatpay($order_no, $money, $extra_cfg)
    {
        if (empty($this->config['openid'])) {
            throw new \think\Exception("未获取到openid，无法调起支付");
        }
        $pay_post_data = array(
            'service' => 'pay.weixin.jspay',
            'version' => '2.0',
            'charset' => 'UTF-8',
            'sign_type' => 'MD5',
            'mch_id' => $this->config['wft_store_id'],
            'is_raw' => '1',
            'is_minipg' => '0',
            'out_trade_no' => $order_no,
            'body' => $this->config['order_name'] ?? '订单号：'.$extra_cfg['business_order_sn'],
            'sub_openid' => $this->config['openid'],
            'sub_appid' => $this->config['wft_pay_weixin_appid'],
            'total_fee' => $money * 100,
            'mch_create_ip' => request()->ip(),
            'notify_url' => (String) url('wftpay_notify', [], false, true),
            'nonce_str' => create_random_str(32),
            'limit_credit_pay' => '0',
        );
        $pay_post_data['sign'] = $this->getSign($pay_post_data);

        $xml = $this->toXml($pay_post_data);
        $gateUrl = 'https://pay.hstypay.com/v2/pay/gateway';
        $return = (new Http())->curlPostXml($gateUrl, $xml);

        if(isset($return['status']) && $return['status'] !== '0'){
            throw new \think\Exception('调用低费率通道支付失败，原因：'.$return['message']);
        }else if(isset($return['result_code']) && $return['result_code'] > 0){
            throw new \think\Exception('调用低费率通道支付失败，原因：'.$return['err_msg']);
        }else if(isset($return['pay_info'])){
            return [
                'type' => 'jssdk',
                'info' => $return['pay_info']
            ];
        }else{
            throw new \think\Exception('调用低费率通道出现未知错误！', 1004);
        }

    }

    private function Alipay($order_no, $money, $extra_cfg)
    {
        if (empty($this->config['alipay_uid'])) {
            throw new \think\Exception("未获取到alipay_uid，无法调起支付");
        }
        $pay_post_data = array(
            'service' => 'pay.alipay.jspay',
            'version' => '2.0',
            'charset' => 'UTF-8',
            'sign_type' => 'MD5',
            'mch_id' => $this->config['wft_store_id'],
            'out_trade_no' => $order_no,
            'body' => $this->config['order_name'] ?? '订单号：'.$extra_cfg['business_order_sn'],
            'total_fee' => $money * 100,
            'mch_create_ip' => request()->ip(),
            'notify_url' => (String) url('wftpay_notify', [], false, true),
            'nonce_str' => create_random_str(32),
            'limit_credit_pay' => '0',
            'buyer_id' => $this->config['alipay_uid'],
        );
        $pay_post_data['sign'] = $this->getSign($pay_post_data);
        $xml = $this->toXml($pay_post_data);
        $gateUrl = 'https://pay.hstypay.com/v2/pay/gateway';
        $return = (new Http())->curlPostXml($gateUrl, $xml);

        if(isset($return['status']) && $return['status'] !== '0'){
            throw new \think\Exception('调用低费率通道支付失败，原因：'.$return['message']);
        }else if(isset($return['result_code']) && $return['result_code'] > 0){
            throw new \think\Exception('调用低费率通道支付失败，原因：'.$return['err_msg']);
        }else if(isset($return['pay_info'])){
            return [
                'type' => 'alijs',
                'info' => $return['pay_info']
            ];
        }else{
            throw new \think\Exception('调用低费率通道出现未知错误！', 1004);
        }
    }

    /**
     * 手机浏览器中的微信支付
     */
    private function H5pay($order_no, $money, $extra_cfg){
        if (empty($this->config['openid'])) {
            throw new \think\Exception("未获取到openid，无法调起支付");
        }
        $pay_post_data = array(
            'service' => 'pay.weixin.wappay',
            'version' => '2.0',
            'charset' => 'UTF-8',
            'sign_type' => 'RSA_1_256',
            'mch_id' => $this->config['wft_store_id'],
            'is_raw' => '1',
            'is_minipg' => '0',
            'out_trade_no' => $order_no,
            'body' => $this->config['order_name'] ?? '订单号：'.$extra_cfg['business_order_sn'],
            'sub_openid' => $this->config['openid'],
            'sub_appid' => $this->config['wft_pay_weixin_appid'],
            'total_fee' => $money * 100,
            'mch_create_ip' => request()->ip(),
            'notify_url' => (String) url('wftpay_notify', [], false, true),
            'nonce_str' => create_random_str(32),
            'limit_credit_pay' => '0',
        );
        $pay_post_data['sign'] = $this->createRSASign($pay_post_data);

        $xml = $this->toXml($pay_post_data);
        $gateUrl = 'https://pay.swiftpass.cn/pay/gateway';
        $return = (new Http())->curlPostXml($gateUrl, $xml);

        if(isset($return['status']) && $return['status'] !== '0'){
            throw new \think\Exception('调用低费率通道支付失败，原因：'.$return['message']);
        }else if(isset($return['result_code']) && $return['result_code'] > 0){
            throw new \think\Exception('调用低费率通道支付失败，原因：'.$return['err_msg']);
        }else if(isset($return['pay_info'])){
            return [
                'type' => 'jssdk',
                'info' => $return['pay_info']
            ];
        }else{
            throw new \think\Exception('调用低费率通道出现未知错误！', 1004);
        }
    }

    private function getSign($arr){
        $signPars = "";
        ksort($arr);
        foreach($arr as $k => $v) {
            if("" != $v && "sign" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars .= "key=" . $this->config['wft_pay_key'];

        return strtoupper(md5($signPars));
    }

    private function toXml($arr){
        $xml = '<xml>';
        forEach($arr as $k=>$v){
            $xml.='<'.$k.'><![CDATA['.$v.']]></'.$k.'>';
        }
        $xml.='</xml>';

        return $xml;
    }

    function createRSASign($arr) {
        $signPars = "";
        ksort($arr);
        foreach($arr as $k => $v) {
            if("" != $v && "sign" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }

        $signPars = substr($signPars, 0, strlen($signPars) - 1);
        $priKey=$this->config['wft_pay_prikey'];
        $priKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        $res = openssl_get_privatekey($priKey);
        openssl_sign($signPars, $sign, $res, OPENSSL_ALGO_SHA256);
        openssl_free_key($res);
        $sign = base64_encode($sign);
        return $sign;
    }

    //查询订单支付状态
    public function query($order_no){


        $pay_post_data = array(
            'service' => 'unified.trade.query',
            'version' => '2.0',
            'charset' => 'UTF-8',
            'sign_type' => 'MD5',
            'mch_id' => $this->config['wft_store_id'],
            'out_trade_no' => $order_no,
            'nonce_str' => create_random_str(32),
            'limit_credit_pay' => '0',
        );
        switch ($this->agent) {
            case 'wechat_h5'://微信端
                $pay_post_data['sign'] = $this->getSign($pay_post_data);
                break;
            case 'wechat_mini'://微信小程序
                $pay_post_data['sign'] = $this->getSign($pay_post_data);//本地签名
                break;
            case 'h5'://普通H5端
                $pay_post_data['sign'] = $this->createRSASign($pay_post_data);
                break;
        }
        $xml = $this->toXml($pay_post_data);
        $gateUrl = 'https://pay.hstypay.com/v2/pay/gateway';
        $return = (new Http())->curlPostXml($gateUrl, $xml);
        if(isset($return['status']) && $return['status'] !== '0'){
            throw new \think\Exception('调用低费率通道支付失败，原因：'.$return['message']);
        }else if(isset($return['result_code']) && $return['result_code'] > 0){
            throw new \think\Exception('调用低费率通道支付失败，原因：'.$return['err_msg']);
        }else if($return['trade_state'] != 'SUCCESS'){
            switch($return['trade_state']){
                case 'REFUND':
                    $return['trade_state_desc'] = '转入退款';
                    break;
                case 'NOTPAY':
                    $return['trade_state_desc'] = '未支付';
                    break;
                case 'CLOSED':
                    $return['trade_state_desc'] = '已关闭';
                    break;
                case 'REVERSE':
                    $return['trade_state_desc'] = '已冲正';
                    break;
                case 'REVOK':
                    $return['trade_state_desc'] = '已撤销';
                    break;
                case 'USERPAYING'://付款码支付时使用
                    return ['status' => 2];
                    break;
            }
            throw new \think\Exception('交易状态：'.$return['trade_state_desc']);
        }else if($return['trade_state'] == 'SUCCESS'){
            return [
                'paid_money' => $return['total_fee'],
                'paid_time' => strtotime($return['time_end']),
                'transaction_no' => $return['out_transaction_id'],
                'status' => 1
            ];
        }else{
            throw new \think\Exception('调用低费率通道出现未知错误！', 1004);
        }
    }

    /**
     * 异步通知
     */
    public function notice(){
        $notice_data = file_get_contents("php://input");
        $array_data = json_decode(json_encode(simplexml_load_string($notice_data, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        $tmpData = $array_data;
        unset($tmpData['sign']);

        switch ($this->agent) {
            case 'wechat_h5'://微信端
                $sign = $this->getSign($tmpData);//本地签名
                break;
            case 'wechat_mini'://微信小程序
                $sign = $this->getSign($tmpData);//本地签名
                break;
            case 'h5':
                $sign = $this->createRSASign($tmpData);//本地签名
                break;
            default:
                throw new \think\Exception("当前端口未对接，不能使用");
                break;
        }  
        
        if ($array_data['sign'] == $sign) {
            return [
                'paid_money' => $array_data['total_fee'],
                'paid_time' => time(),
                'transaction_no' => $array_data['out_transaction_id']
            ];
        }
        else{
            throw new \think\Exception("wft签名验证失败");
        }
    }

    //付款码支付
    public function scanPay($auth_code, $order_no, $money, $pay_type){
        $pay_post_data = array(
            'service' => 'unified.trade.micropay',
            'version' => '2.0',
            'charset' => 'UTF-8',
            'sign_type' => 'MD5',
            'mch_id' => $this->config['wft_store_id'],
            'out_trade_no' => $order_no,
            'body' => $this->config['order_name'] ?? '订单号：'.$order_no,
            'total_fee' => $money*100,
            'auth_code' => $auth_code,
            'nonce_str' => create_random_str(32),
            'mch_create_ip' => request()->ip(),
        );
        $pay_post_data['sign'] = $this->getSign($pay_post_data);

        $xml = $this->toXml($pay_post_data);
        $gateUrl = 'https://pay.hstypay.com/v2/pay/gateway';
        $return = (new Http())->curlPostXml($gateUrl, $xml);

        if(isset($return['status']) && $return['status'] !== '0'){
            throw new \think\Exception('调用低费率通道支付失败，原因：'.$return['message']);
        }else if(isset($return['result_code']) && $return['result_code'] > 0){
            if ($return['err_code'] == 'USERPAYING') {
                return ['status'=>2];
            }
            else{
                throw new \think\Exception('调用低费率通道支付失败，原因：'.$return['err_msg'] ? $return['err_msg'] : ($return['error_msg'] ? $return['error_msg'] : $return['message']));
            }
        }else if(isset($return['pay_result']) && $return['pay_result'] == '0'){
            return ['status'=>1, 'transaction_no' => $return['out_transaction_id']];
        }else{
            throw new \think\Exception('调用低费率通道出现未知错误！', 1004);
        }
    }

    /**
     * 退款
     * @param  [type] $order_no       本系统支付单号
     * @param  [type] $total_fee      总支付金额（单位：分）
     * @param  [type] $transaction_no 第三方交易流水号（防止某些平台需要）
     * @param  [type] $refund_money   退款金额（单位：分）
     * @return [type]                 [description]
     */
    public function refund($order_no, $total_fee, $refund_money, $transaction_no = ''){
        $refund_no = "sdkphp".date("YmdHis").rand(1000,9999);
        $pay_post_data = array(
            'service' => 'unified.trade.refund',
            'version' => '2.0',
            'charset' => 'UTF-8',
            'sign_type' => 'MD5',
            'mch_id' => $this->config['wft_store_id'],
            'out_trade_no' => $order_no,
            'out_refund_no' => $refund_no,
            'total_fee' => $total_fee,
            'refund_fee' => $refund_money,
            'op_user_id' => $this->config['wft_store_id'],
            'refund_channel' => 'ORIGINAL',
            'nonce_str' => create_random_str(32),
        );
        $pay_post_data['sign'] = $this->getSign($pay_post_data);

        $xml = $this->toXml($pay_post_data);
        $gateUrl = 'https://pay.hstypay.com/v2/pay/gateway';
        $return = (new Http())->curlPostXml($gateUrl, $xml);

        if(isset($return['status']) && $return['status'] !== '0'){
            throw new \think\Exception('调用低费率通道退款失败，原因：'.$return['message']);
        }else if(isset($return['result_code']) && $return['result_code'] > 0){
            throw new \think\Exception('调用低费率通道退款失败，原因：'.$return['err_msg']);
        }else{
            return [
                'refund_no' => $refund_no
            ];
        }
    }
}
