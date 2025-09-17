<?php

namespace app\pay\model\service\channel;


use app\pay\model\db\PayOrderInfo;

class FarmersbankpayService
{

    protected $instId;         //机构编号
    protected $appCode;        //接入商编号

    protected $appKey;         //签名秘钥
    protected $sign;           //签名值
    protected $appId;          //公众账号ID

    protected $merNum;         //商户编号
    protected $merName;        //商户名称
    protected $appChannelId;   //微信渠道商户号
    protected $chanMchId;      //微信服务商户号
    protected $subMchId;       //微信子商户号

    protected $baseUrl;        //请求的根地址
    protected $agent;

    public function __construct($agent, $payConfig = [])
    {
        $this->agent = $agent;

//        $this->baseUrl      = 'http://wxpay-cs.yzbank.com:8889';//测试环境
        $this->baseUrl      = 'http://wxpay-sc.yzbank.com:8080';//正式环境
        $this->appKey       = $payConfig['pay_farmersbankpay_appKey'];
        $this->instId       = $payConfig['pay_farmersbankpay_instId'];
        $this->appCode      = $payConfig['pay_farmersbankpay_appCode'];
        $this->appId        = $payConfig['pay_farmersbankpay_appId'];
        $this->merNum       = $payConfig['pay_farmersbankpay_merNum'];         //渠道商户号
        $this->merName      = $payConfig['pay_farmersbankpay_merName'];        //渠道商户名称
        $this->appChannelId = $payConfig['pay_farmersbankpay_appChannelId'];   //渠道商商户号
        $this->chanMchId    = $payConfig['pay_farmersbankpay_chanMchId'];      //商户号
        $this->subMchId     = $payConfig['pay_farmersbankpay_subMchId'];       //微信子商户号
        $this->instId       = $payConfig['pay_farmersbankpay_instId'];         //机构号（固定）
    }

    public function pay($order_no, $money, $config)
    {

        if (empty($order_no) || empty($money)) {
            fdump_api(['param' => $_POST, 'order_no' => $order_no, 'money' => $money, 'msg' => '订单ID或支付金额必填'], 'errPay/FarmersbankpayService', 1);
            throw_exception("订单ID或支付金额必填！");
        }
        $this->agent = 'wechat_mini';//当前默认场景
        switch ($this->agent) {
            case 'wechat_mini':
                $params = [
                    "body"      => $config["title"] ?? '小程序预下单', //小程序预下单订单描述
                    "totalFee"  => floatval($money * 100), //100订单金额（单位分）
                    "tpOrderId" => $order_no, //"509151971840"平台订单号(12位以内)
                    "tradeType" => 'JSAPI', //"JSAPI"交易类型 传JSAPI
                    "openid"    => $config["wxapp_openid"], //'oeaIj4z0Jo__tmHe2hSCHRlWMGiM'用户标识 用户在主商户appid下的唯一标识。
                    "remark"    => $config["title"] ?? '小程序预下单',
                ];
                return $this->preOrder($params);
            default:
                fdump_api(['param' => $_POST, 'agent' => $this->agent, 'order_no' => $order_no, 'money' => $money, 'msg' => '当前端口未对接，不能使用'], 'errPay/FarmersbankpayService', 1);
                throw_exception("当前端口未对接，不能使用");
        }
    }

    private function getSign($data)
    {
        ksort($data);
        $sign = '';//密钥
        foreach ($data as $key => $value) {
            if ($value) {
                $sign .= $key . '=' . $value . '&';
            }
        }
        $sign .= 'key=' . $this->appKey;
        $sign = strtoupper(md5($sign));
        return $sign;
    }

    private function requestPost($url, $params)
    {
        $requestUrl     = "{$this->baseUrl}/{$url}/{$this->instId}/{$this->appCode}";
        $params['sign'] = $this->getSign($params);
        return $this->curlPost($requestUrl, $params);
    }

    public function curlPost($url = '', $postData = array(), $options = array())
    {
        if (is_array($postData)) {
            $postData = json_encode($postData,JSON_UNESCAPED_UNICODE);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type:application/json',
            'Accept:application/json'
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        fdump_sql([$postData, $url, $data], 'yizheng_farmers_bank_sdk');
        fdump_api([$_SERVER['REQUEST_URI'],$_POST, $postData, $url, $data], 'yizheng_farmers_bank/pay_log'.date('Y-m-d', time()),true);
        return json_decode($data, true);
    }

    /**
     * 微信订单退款
     * @param array $params
     * @return array
     */
    public function refund($order_no, $total_fee, $refund_money, $transaction_no = '')
    {
        if(empty($transaction_no)){
            $queryOrder = $this->query($order_no);
            $transaction_no = $queryOrder['transaction_no'];
        }
       
        $order      = app(PayOrderInfo::class)->getByOrderNo($order_no);
        $tpOrderId  = date("dHis") . str_pad(substr(sprintf("%04d", $order['business_order_id']), -4), 4, '0', STR_PAD_LEFT);
        $postParams = [
            'instId'          => $this->instId,
            'chanMchId'       => $this->chanMchId,
            'subMchId'        => $this->subMchId,
            'merNum'          => $this->merNum,
            'merName'         => $this->merName,
            'appChannelId'    => $this->appChannelId,
            "refundAmount"    => $refund_money, //"500"退款金额(单位分)
            "tpOrderId"       => $tpOrderId, //"970064966912"平台订单号(12位以内)
            "orOrderId"       => $order_no, //"370064966912"原平台订单号
            "orOutTradeNo"    => $transaction_no, //"V20200609121212"原商户订单
            "orderCreateDate" => date('YmdHis', $order['paid_time']), //订单创建时间 格式20220607182856
            "appId"           => $this->appId, //公众账号ID
            "orderRefundDate" => date('YmdHis', time()), //订单退款时间 格式20220607182856
        ];

        $result = $this->requestPost('api/refund', $postParams);
        if (!empty($result['code'])) {
            throw_exception($result['message']);
        }
        if($result['data']['status'] != '00'){
            throw_exception('退款失败！');
        }
        $result['refund_no'] = $result['data']['orOrderId'];
        return $result;
    }

    /**
     * 小程序预下单
     * @param array $params
     * @return array
     */
    public function preOrder(array $params)
    {
        $postParams = [
            "body"       => $params["body"] ?? '小程序预下单', //小程序预下单订单描述
            "totalFee"   => $params["totalFee"] ?? 0, //100订单金额（单位分）
            "deviceInfo" => $params["deviceInfo"] ?? '', //20200413362711设备信息
            'instId'     => $this->instId,
            'merNum'     => $this->merNum,
            'merName'    => $this->merName,
            "tpOrderId"  => $params["tpOrderId"], //"509151971840"平台订单号(12位以内)
            "subAppId"   => $this->appId, //"wxc9654cc47000c248"子商户公众号
            "subMchId"   => $this->subMchId,
            "shopNo"     => $params["shopNo"] ?? '', //"mnt0012020041320365695500000"商户门店编号
            "shopName"   => $params["shopName"] ?? '', //"收银台1"商户门店名称
            "tradeType"  => $params["tradeType"] ?? 'JSAPI', //"JSAPI"交易类型 传JSAPI
            "sellerId"   => $params["sellerId"] ?? '', //"shouyin333"管理员账号（方便收银宝APP端查账）
            "subOpenid"  => $params["openid"], //"owDxs5M8tJiAaXMr0V5Dyj_hTFuo"用户标识 用户在主商户appid下的唯一标识。
            "remark"     => $params["remark"] ?? '小程序预下单', //订单备注
        ];

        $result = $this->requestPost('api/jspay', array_filter($postParams));
        if (!empty($result['code'])) {
            throw_exception($result['message']);
        }
        $result['data']['package'] = $result['data']['bodyDesc'];
        return [
            'type' => 'jssdk',
            'info' => json_encode($result['data'], JSON_UNESCAPED_UNICODE),
            'outTradeNo' => $result['data']['outTradeNo']
        ];
    }

    /**
     * 微信退款查询
     * @param array $params
     * @return bool|string
     */
    public function refundQuery(array $params)
    {//暂未使用
        $postParams = [
            "refundAmount"    => $params["refundAmount"], //"500"退款金额(单位分)
            "tpOrderId"       => $params["tpOrderId"], //"970064966912"平台订单号(12位以内)
            "orOrderId"       => $params["orOrderId"], //"370064966912"原平台订单号(12位以内)
            "orOutTradeNo"    => $params["orOutTradeNo"], //"V20200609121212"原商户订单号
            "shopNo"          => $params["shopNo"], //"mnt1212121212122121212121"商户门店编号
            "shopName"        => $params["shopName"], //"门店1"商户门店名称
            "sellerId"        => $params["sellerId"], //"457852215"管理员账号（方便收银宝APP端查账）
            "terminalId"      => $params["terminalId"], //"20201212152545"终端号
            "orderCreateDate" => $params["orderCreateDate"], //订单创建时间 格式20220607182856
            "appId"           => $this->appId, //公众账号ID
            "orderRefundDate" => $params["orderRefundDate"] //订单退款时间 格式20220607182856
        ];

        return $this->requestPost('api/refundQuery', $postParams);
    }

    /**
     * 微信订单查询
     * @param array $params
     * @return array
     */
    public function query($order_no)
    {
        $order = app(PayOrderInfo::class)->getByOrderNo($order_no);

        $postParams = [
            'instId'          => $this->instId,
            'appChannelId'    => $this->appChannelId,
            'chanMchId'       => $this->chanMchId,
            'merNum'          => $this->merNum,
            'merName'         => $this->merName,
            'subMchId'        => $this->subMchId,
            "tpOrderId"       => $order_no, //"370064966912"平台订单号(12位以内)
            "orderCreateDate" => date('YmdHis', strtotime($order['addtime'])), //"20220607182856"订单创建时间 格式20220607182856
            "appId"           => $this->appId, //"wxc9654cc47000c248"公众账号ID
        ];

        $result = $this->requestPost('api/query', $postParams);
        if (!empty($result['code'])) {
            throw_exception($result['message']);
        }
        $data = $result['data'];
        return [
            'status'         => 1,
            'paid_money'     => $data['actualPay'],
            'paid_time'      => time(),
            'transaction_no' => $data['outTradeNo']
        ];

    }
    
    /**
     * 异步通知
     */
    public function notice()
    {
        $data  = request()->post();
        $order = app(PayOrderInfo::class)->getByOrderNo($data['tpOrderId']);
        return [
            'paid_money'     => $data['actualPay'],
            'paid_time'      => time(),
            'transaction_no' => $order['paid_extra']
        ];
    }
}