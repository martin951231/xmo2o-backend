<?php
/**
 * 【【12.15交付】www.yilvbao.cn 环球数科（交付：12.15）- 对接服务机构支付接口，解决跨区域支付结算问题】 https://www.tapd.cn/47145533/prong/stories/view/1147145533001009799
 */
class Hqpay {

    protected $order_info;
    protected $pay_money;
    protected $pay_type;
    protected $is_mobile;
    protected $pay_config;
    protected $user_info;
    protected $base_url;
    protected $redi_url;
    protected $pcRe_url;
    protected $appid;
    protected $secretkey;
    protected $priKey;
    protected $pubKey;
    protected $return_url;
    protected $notify_url;
    protected $refund_url;

    public function __construct($order_info = [], $pay_money = 0, $pay_type = 'hqpay', $pay_config = [], $user_info = [], $is_mobile = 0){

        $this->base_url   = 'https://jicheng.upaypal.cn/business-platform.web.services.merchant-api-v3/rest/api/v3/';
        $this->redi_url   = 'https://jicheng.upaypal.cn/business-platform.payment-channel.web/hqpay/apay/';
        $this->order_info = $order_info;
        $this->pay_money  = $pay_money;
        $this->pay_type   = $pay_type;
        $this->is_mobile  = $is_mobile;
        $this->pay_config = $pay_config;
        $this->user_info  = $user_info;
        $this->appid      = C('config.pay_hqpay_appid');
        $this->secretkey  = C('config.pay_hqpay_secretkey');
        $this->priKey     = C('config.pay_hqpay_prikey');//私钥
        $this->pubKey     = C('config.pay_hqpay_pubkey');//公钥
        $this->return_url = C('config.site_url') . '/source/hqpay_return.php?order_no=' . $order_info['order_type'] . '_' . $order_info['order_id'];//返回通知地址
        $this->pcRe_url   = C('config.site_url') . '/index.php?g=Index&c=Pay&a=hqpay_return&order_no=' . $order_info['order_type'] . '_' . $order_info['order_id'];
        $this->notify_url = C('config.site_url') . '/source/hqpay_notify.php';//支付异步通知地址
        $this->refund_url = C('config.site_url') . '/source/hqpay_refund.php';//退款异步通知地址
    }

	//支付
	public function pay($env) {
        $offer = [
            'product_id'   => '"t00123"',
            'product_name' => '"订单号:' . $this->order_info['order_id'] . '"',//'"西溪湿地门票"',
            'quantity'     => 1,
            'unit_price'   => $this->pay_money
        ];
        $arr = [[
            'order_type'   => 'trade',
            'out_trade_no' => $this->order_info['order_id'],
            'amount'       => $this->pay_money,
            'subject'      => '订单号:' . $this->order_info['order_id'],
            'time_expire'  => date('Y-m-d H:i:s', time() + 600),
            'offer'        => $offer,//json_encode($offer,true),
            'notify_url'   => $this->notify_url,
        ]];
//        $arr['signature'] = $this->getSign($arr);
        $res = $this->doRequest('createTrade', $arr);
        fdump($res, 'hqpay_pay', 1);
        $res = json_decode($res,true);
        if (empty($res['return_code']) || $res['return_code'] != 'SUCCESS' || empty($res['data']['trade_no'])) {
            return ['error' => 1, 'msg' => $res['return_msg'] ?? '请求失败'];
        }
        M('Payment_money_order')->where(['orderid' => $this->order_info['order_id']])->setField('env', $env);
        $url = '';
        switch ($env) {
            case 'wxApp':
//            case 'alApp': //支付宝app支付暂时走H5支付
                if ($env == 'alApp') {
                    $url = $this->redi_url . 'app?channel_pay_type=ALIPAY_APP&trade_no=' . $res['data']['trade_no'] . '&return_url=' . urlencode($this->return_url);
                } else {
                    $url = $this->redi_url . 'app?channel_pay_type=WX_APP&trade_no=' . $res['data']['trade_no'] . '&return_url=' . urlencode($this->return_url);
                }
                $urlRes = file_get_contents($url);
                $urlRes = json_decode($urlRes, true);
                if (!empty($urlRes['return_code']) && $urlRes['return_code'] == 'SUCCESS' && !empty($urlRes['data']['get_appstring_url'])) {
                    $qrRes = file_get_contents($urlRes['data']['get_appstring_url']);
                    $qrRes = json_decode($qrRes, true);
                    if (!empty($qrRes['return_code']) && $qrRes['return_code'] == 'SUCCESS' && !empty($qrRes['content'])) {
                        if ($env == 'alApp') {
                            return array('error' => 0, 'alipay_param' => $qrRes['content']['app_string']);
                        } else {
                            return array('error' => 0, 'weixin_param' => $qrRes['content']['app_string']);
                        }
                    }
                }
                break;
            case 'wxH5':
                $url = $this->redi_url . 'pub?channel_pay_type=WX_PUB&trade_no=' . $res['data']['trade_no'] . '&return_url=' . urlencode($this->return_url) . '&openid=' . $this->user_info['openid'] ?? '';
		$urlRes = file_get_contents($url);
		fdump($urlRes, 'hqpay_pay1', 1);
		return array('error' => 0, 'redirectH5Pay' => $url);
		break;
            case 'wxMini':
                $url = $this->redi_url . 'lite?channel_pay_type=WX_LITE&trade_no=' . $res['data']['trade_no'] . '&return_url=' . urlencode($this->return_url) . '&openid=' . $this->user_info['wxapp_openid'] ?? '';
                $urlRes = file_get_contents($url);
		$urlRes = json_decode($urlRes, true);
		return array('error' => 0, 'weixin_param' => $urlRes);
		break;
            case 'alApp':
            case 'alH5':
                $url = $this->redi_url . 'wap?channel_pay_type=ALIPAY_WAP&trade_no=' . $res['data']['trade_no'] . '&return_url=' . urlencode($this->return_url);
                break;
            case 'alWap':
            case 'wxWap':
                if ($env == 'alWap') {
                    $url = $this->redi_url . 'wap?channel_pay_type=ALIPAY_WAP&trade_no=' . $res['data']['trade_no'] . '&return_url=' . urlencode($this->return_url);
                } else {
                    $url = $this->redi_url . 'wap?channel_pay_type=WX_WAP&trade_no=' . $res['data']['trade_no'] . '&return_url=' . urlencode($this->return_url) . '&openid=' . $this->user_info['openid'] ?? '';
                }
                break;
            case 'alPc':
            case 'wxPc':
                if ($env == 'alPc') {
                    $urlRes = file_get_contents($this->redi_url . 'qr?channel_pay_type=ALIPAY_QR&trade_no=' . $res['data']['trade_no'] . '&return_url=' . urlencode($this->pcRe_url));
                } else {
                    $urlRes = file_get_contents($this->redi_url . 'qr?channel_pay_type=WX_QR&trade_no=' . $res['data']['trade_no'] . '&return_url=' . urlencode($this->pcRe_url));
                }
                $urlRes = json_decode($urlRes, true);
                if (!empty($urlRes['return_code']) && $urlRes['return_code'] == 'SUCCESS' && !empty($urlRes['data']['get_qrcode_url'])) {
                    $qrRes = file_get_contents($urlRes['data']['get_qrcode_url']);
                    $qrRes = json_decode($qrRes, true);
                    if (!empty($qrRes['resultCode']) && $qrRes['resultCode'] == 'SUCCESS' && !empty($qrRes['qrCode'])) {
                        return array('error' => 0, 'qrcode' => $qrRes['qrCode']);
                    }
                }
                return ['error' => 1, 'msg' => '二维码生成失败，请稍后重试'];
                break;
        }
        redirect($url);
	}

    //支付
    public function searchPay($out_trade_no) {
        $arr = [
            'out_trade_no' => $out_trade_no
        ];
        $res = $this->doRequest('getTrade', $arr);
        fdump($res, 'hqpay_searchPay', 1);
        $res = json_decode($res,true);
        if ((!empty($res['return_code']) && $res['return_code'] == 'SUCCESS') && (!empty($res['data']['trade_status']) && $res['data']['trade_status'] == 'PAID'))  {
            return [
                'pay_money' => $res['data']['amount'],
                'trade_no'  => $res['data']['trade_no']
            ];
        }
        return false;
    }

    public function refund() {
        $out_refund_no = $this->order_info['order_id'] . rand(1000,9999);
        $arr = [
            'out_refund_no' => $out_refund_no,
            'out_trade_no'  => $this->order_info['order_id'],
            'refund_amount' => $this->pay_money,
            'refund_reason' => '退款单号:' . $out_refund_no,
//            'refund_notify_url' => $this->refund_url
        ];
        $res = $this->doRequest('createRefundBill', $arr);
        fdump($res, 'hqpay_refund', 1);
        $res = json_decode($res,true);
        if (empty($res['return_code']) || $res['return_code'] != 'SUCCESS') {
            return ['error' => 1, 'msg' => $res['return_msg'] ?? '请求失败'];
        }
        M('Payment_money_order')->where(['orderid' => $this->order_info['order_id']])->setField('orderNo', $out_refund_no);
        return ['error' => 0,'type' => 'ok','msg' => '退款申请成功！','refund_param'=>$res];
    }

    //退款单查询,未使用
    public function searchRefundPay($out_refund_no) {
        $arr = [
            'out_refund_no' => $out_refund_no
        ];
        $res = $this->doRequest('getRefundBill', $arr);
        fdump($res, 'hqpay_searchRefundPay', 1);
        $res = json_decode($res,true);
        if ((!empty($res['return_code']) && $res['return_code'] == 'SUCCESS') && (!empty($res['content']['refund_status']) && $res['content']['refund_status'] == 'SUCCESS'))  {
            return true;
        }
        return false;
    }

    //异步回调
    public function notice_url() {
        $data = file_get_contents("php://input");
        $data = json_decode($data, true);
        fdump($data, 'hqpay_notice', 1);
        if (empty($data['outTradeNo'])) {
            return array('error' => 1,'msg' => '回调数据有误');
        }
        if ($this->searchPay($data['outTradeNo'])) {
            $orderInfo = M('Payment_money_order')->where(['orderid' => $data['outTradeNo']])->find();
            M('Payment_money_order')->where(['orderid' => $data['outTradeNo']])->save(['pay_method' => $data['fundBills'], 'paid_money' => $data['totalFee']]);
            $res = [
                'order_id'   => $data['outTradeNo'],
                'pay_type'   => $orderInfo['pay_type'],
                'is_mobile'  => $this->is_mobile,
                'order_from' => 1,
                'order_type' => $orderInfo['order_type'] ?? 'shop',
                'is_own'     => 0,
                'third_id'   => 0,
                'pay_money'  => $data['totalFee'],
                'env'        => $orderInfo['env']
            ];
            if ($res) {
//                $pay_info = $this->after_pay(['order_param' => $res]);
                $pay_info = D('Pay')->after_pay($res['order_type'], ['order_param' => $res], false);
                fdump(json_encode($pay_info, true), 'hqpay_notice', 1);
                exit("success");
            }
        }
        return ['error' => 1];
    }

    //未使用
    public function refund_url() {
        $data = file_get_contents("php://input");
        fdump($data, 'hqpay_refund_return', 1);
        $data = json_decode($data, true);
        if (empty($data['outRefundNo'])) {
            return array('error' => 1,'msg' => '回调数据有误');
        }
        if ($this->searchRefundPay($data['outRefundNo'])) {
            return ['error' => 0];
        }
        return ['error' => 1];
    }

    //未使用
    public function getSign($signData) {
        $signstr = http_build_query(ksort($signData));
        $private_key = $this->priKey;
        if (strpos($private_key, 'PRIVATE KEY') === false) {
            $private_key = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($private_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
        }
        $private_key = openssl_pkey_get_private($private_key);
        openssl_sign($signstr, $signature, $private_key,OPENSSL_ALGO_SHA256);//对所有请求参数用商户私钥进行签名 sha256WithRSAEncryption SHA256
        $signature = base64_encode($signature);
        return $signature;
    }

    public function doRequest($api, $params) {
        $url  = $this->base_url . $api;
        $curl = curl_init($url);
        $payload = json_encode($params, true);
        $headers = array(
            "Content-Type: application/json",
            "Accept: application/json",
            "AppId: " . $this->appid,
            "Authorization: " . $this->secretkey
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $curl_return = curl_exec($curl);
        curl_close($curl);
        return $curl_return;
    }

    //支付成功更新订单
    public function after_pay($get_pay_param = []) {
        if($get_pay_param['order_param']['order_type'] == 'group'){
            $pay_info = D('Group_order')->after_pay($get_pay_param['order_param']);
        }
        else if($get_pay_param['order_param']['order_type'] == 'meal' || $get_pay_param['order_param']['order_type'] == 'takeout' || $get_pay_param['order_param']['order_type'] == 'food' || $get_pay_param['order_param']['order_type'] == 'foodPad'){
            $pay_info = D('Meal_order')->after_pay($get_pay_param['order_param'], $get_pay_param['order_param']['order_type']);
        }else if($get_pay_param['order_param']['order_type'] == 'weidian'){
            $pay_info = D('Weidian_order')->after_pay($get_pay_param['order_param']);
        }
        else if($get_pay_param['order_param']['order_type'] == 'recharge'){
            $pay_info = D('User_recharge_order')->after_pay($get_pay_param['order_param']);
        }
        else if($get_pay_param['order_param']['order_type'] == 'appoint'){
            $pay_info = D('Appoint_order')->after_pay($get_pay_param['order_param']);
        }
        else if($get_pay_param['order_param']['order_type'] == 'waimai'){
            $pay_info = D('Waimai_order')->after_pay($get_pay_param['order_param']);
        }
        else if($get_pay_param['order_param']['order_type'] == 'wxapp'){
            $pay_info = D('Wxapp_order')->after_pay($get_pay_param['order_param']);
        }
        else if($get_pay_param['order_param']['order_type'] == 'store'){
            $pay_info = D('Store_order')->after_pay($get_pay_param['order_param']);
        }
        else if($get_pay_param['order_param']['order_type'] == 'shop' || $get_pay_param['order_param']['order_type'] == 'mall'){
            $pay_info = D('Shop_order')->after_pay($get_pay_param['order_param']);
        }else if($get_pay_param['order_param']['order_type'] == 'plat'){
            $pay_info = D('Plat_order')->after_pay($get_pay_param['order_param']);
        }
        else if($get_pay_param['order_param']['order_type'] == 'balance-appoint'){
            $pay_info = D('Appoint_order')->balance_after_pay($get_pay_param['order_param']);
        }
        else if($get_pay_param['order_param']['order_type'] == 'integral_mall'){
            $pay_info = D('Merchant_integral_mall_goods_order')->after_pay($get_pay_param['order_param']);
        }
        else if($get_pay_param['order_param']['order_type'] == 'market'){
            $pay_info = D('Market_order')->market_after_pay($get_pay_param['order_param']);
        }
        else if($get_pay_param['order_param']['order_type'] == 'markets'){
            $pay_info = D('Market_order')->market_total_after_pay($get_pay_param['order_param']);
        }
        else if($get_pay_param['order_param']['order_type'] == 'mcard'){
            $pay_info = D('Membership_card_order')->after_pay($get_pay_param['order_param']);
        }
        else{
            $pay_info = [];
        }
        return $pay_info;
    }

}
?>