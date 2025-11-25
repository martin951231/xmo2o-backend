<?php

class Farmersbankpay
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
    protected $userInfo;
    protected $orderInfo;
    protected $payMoney;
    protected $isMobile;
    

    public function __construct($orderInfo,$payMoney,$pay_type,$payConfig,$user_info,$isMobile=0)
    {
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
        $this->userInfo     = $user_info;
        $this->orderInfo    = $orderInfo;
        $this->payMoney     = $payMoney;
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
        fdump_sql([$postData, $url, $data], 'yizheng_farmers_bankl_sdk');
        fdump_api([$_SERVER['REQUEST_URI'],$_POST, $url, $postData, $data], 'yizheng_farmers_bank/pay_log'.date('Y-m-d', time()),true);
        return json_decode($data, true);
    }

    /**
     * 微信订单退款
     * @return array
     */
    public function refund()
    {
        $order = M('Payment_money_order')
            ->where(['orderid'=>$this->orderInfo['orderid'],'pay_type'=>'farmersbankpay'])
            ->find();
        $tpOrderId =  date("dHis").str_pad(substr(sprintf("%04d", $this->userInfo['uid']),-4),4,'0',STR_PAD_LEFT);
        $postParams = [
            'instId'          => $this->instId,
            'chanMchId'       => $this->chanMchId,
            'subMchId'        => $this->subMchId,
            'merNum'          => $this->merNum,
            'merName'         => $this->merName,
            'appChannelId'    => $this->appChannelId,
            "refundAmount"    => intval(bcmul($this->payMoney,100)), //"500"退款金额(单位分)
            "tpOrderId"       => $tpOrderId, //"970064966912"平台订单号(12位以内)
            "orOrderId"       => $this->orderInfo['orderid'], //"370064966912"原平台订单号
            "orOutTradeNo"    => $order['orderNo'], //"V20200609121212"原商户订单
            "orderCreateDate" => date('YmdHis', $order['addtime']), //订单创建时间 格式20220607182856
            "appId"           => $this->appId, //公众账号ID
            "orderRefundDate" => date('YmdHis', time()), //订单退款时间 格式20220607182856
        ];

        $result = $this->requestPost('api/refund', $postParams);
        if (!empty($result['code'])) {
           return ['error'=>1,'msg'=>L_($result['message'])];
        }
        $refund_param = [
            'refund_id' => $result['data']['orOrderId'],
            'refund_time' => $result['data']['transDateTime']
        ];
        return array('error'=>0,'type'=>'ok','msg'=>L_('退款申请成功！请注意查收“微信支付”给您发的退款通知。'),'refund_param'=>$refund_param);
    }

    /**
     * 小程序预下单
     * @param array $params
     * @return array
     */
    public function pay()
    {
        $postParams = [
            "body"      => $this->orderInfo['order_name'] . '-小程序预下单订单', //小程序预下单订单描述
            "totalFee"  => floatval($this->payMoney * 100), //100订单金额（单位分）
            'instId'    => $this->instId,
            'merNum'    => $this->merNum,
            'merName'   => $this->merName,
            "tpOrderId" => $this->orderInfo['orderid'], //"509151971840"平台订单号(12位以内)
            "subAppId"  => $this->appId, //"wxc9654cc47000c248"子商户公众号
            "subMchId"  => $this->subMchId,
            "shopNo"    => $this->orderInfo["mer_id"] . '-' . $this->orderInfo["store_id"], //商户门店编号
            "shopName"  => $params["shopName"] ?? '', //"收银台1"商户门店名称
            "tradeType" => 'JSAPI', //"JSAPI"交易类型 传JSAPI
            "sellerId"  => $this->userInfo["uid"], //"shouyin333"管理员账号（方便收银宝APP端查账）
            "subOpenid" => $this->userInfo["wxapp_openid"], //"oeaIj4z0Jo__tmHe2hSCHRlWMGiM"用户标识 用户在主商户appid下的唯一标识。
            "remark"    => $this->orderInfo['order_name'] ?? '小程序预下单', //订单备注
        ];

        $result = $this->requestPost('api/jspay', array_filter($postParams));
        if (!empty($result['code'])) {
            return ['error'=>1,'msg'=>'支付错误返回：'.$result['message']];
        }
        
        $result['data']['package'] = $result['data']['bodyDesc'];
        M('Payment_money_order')
            ->where(['orderid'=>$this->orderInfo['orderid'],'pay_type'=>'farmersbankpay'])
            ->data(['pay_method'=>'farmersbankpay','orderNo'=>$result['data']['outTradeNo']])
            ->save();
        
        return ['error'=>0,'weixin_param'=> $result['data']];
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
    public function query()
    {
        $postParams = [
            'instId'          => $this->instId,
            'merNum'          => $this->merNum,
            'merName'         => $this->merName,
            'appChannelId'    => $this->appChannelId,
            'chanMchId'       => $this->chanMchId,
            'subMchId'        => $this->subMchId,
            "tpOrderId"       => $this->orderInfo['orderid'], //"370064966912"平台订单号(12位以内)
            "orderCreateDate" => date('YmdHis', $this->orderInfo['pay_time']), //订单创建时间 格式20220607182856
            "appId"           => $this->appId, //"wxc9654cc47000c248"公众账号ID
        ];

        $result =  $this->requestPost('api/query', $postParams);
        if (!empty($result['code'])) {
            return ['error'=>1,'msg'=>'支付错误返回：'.$result['message']];
        }
        $order_param['pay_type']   = 'farmersbankpay';
        $order_param['is_mobile']  = $this->isMobile;
        $order_param['order_type'] = $this->orderInfo['order_type'];
        $order_param['order_id']   = $this->orderInfo['order_id'];
        $order_param['pay_money']  = $result['data']['actualPay'] / 100;
        $order_param['sub_mch_id'] = $this->subMchId;
        $order_param['is_own']     = 1;
        $order_param['third_id']   = $result['data']['outTradeNo'];
        
        M('Payment_money_order')
            ->where(['orderid'=>$this->orderInfo['orderid'],'pay_type'=>'farmersbankpay'])
            ->data([
                'pay_method'=>'farmersbankpay',
                'paid_money' => $order_param['pay_money'],
                'orderNo'=>$result['data']['outTradeNo']
            ])
            ->save();

        return ['error'=>0,'order_param'=> $order_param];

    }

    /**
     * 
     */
    public function notice_url()
    {
        
    }

    /**
     * @return array    
     * {
    //    "instId": "YC201908121572",
    //    "merNum": "3210810152R0000958",
    //    "tpOrderId": "370064966912",
    //    "totalFee": 1000,
    //    "actualPay": 1000,
    //    "discount": 0,
    //    "status": "00"
    // }
     */
    public function return_url()
    {
        $farmersOrder = M('Payment_money_order')->where([
            'orderid' => $_POST['tpOrderId'],
            'pay_type' => 'farmersbankpay'
        ])->find();
        
        if(empty($farmersOrder)){
            return array('error'=>1,'msg'=>'当前订单不存在！');
        }
        $order_param['pay_type']   = 'farmersbankpay';
        $order_param['is_mobile']  = '';
        $order_param['order_type'] = $farmersOrder['order_type'];
        $order_param['order_id']   = $farmersOrder['orderid'];
        $order_param['is_own']     = '';
        $order_param['openid']     = $this->userInfo['wxapp_openid'];
        $order_param['third_id']   = '';
        $order_param['pay_money']  = $_POST['totalFee'] / 100;
        $order_param['sub_mch_id'] = $this->subMchId;
        $order_param['mch_id']     = $this->chanMchId;
        $order_param['appid']      = $this->appId;
        $order_param['attach']     = '';
        
        return array('error'=>0,'order_param'=>$order_param);
    }
}
