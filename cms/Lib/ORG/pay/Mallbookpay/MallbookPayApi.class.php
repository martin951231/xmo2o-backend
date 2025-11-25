<?php

class MallbookPayApi {

    //api接口地址
    protected $apiUrl;
    //平台商户编号
    protected $merchantNo;
    //渠道类型
    protected $channelType;
    //接口版本号
    protected $version;

    public static $errorMsg = "";

    public $config;

    public function __construct()
    {

        $this->apiUrl = "https://cloudpay.mallbook.cn/api";
        $this->merchantNo = C('config.mallbook_merchant_no');
        $this->channelType = "HF";
        $this->version = "1.0.0";
    }

    
    /**
     * 支付接口
     * @param $merOrderId
     * @param $params
     * @param string $serverCode
     * @return bool|array
     */
    public function paymentOrder($merOrderId,$params,$serverCode = "paymentOrder") {
        if (empty($merOrderId) || empty($params)) {
            throw_exception('支付参数缺失');
        }

        $mustParams = [
            'bizOrderId', 'amount', 'orderName', 'payType', 'terminalIp',
            'goodsDetail', 'frontUrl', 'notifyUrl', 'appid', 'openid'
        ];

        $params['transferType']  = '0';//担保交易
        $params['asynSplitFlag'] = '1';//异步分账
        fdump_api([
            'params'     => $params,
            'msg'       => 'paymentOrder支付参数'
        ], 'mallbookOldPay/wechatPay', true);
        
        foreach ($mustParams as $val) {
            if (!isset($params[$val])) {
                throw_exception('业务参数' . $val . '必填');
            }
            if ('goodsDetail' == $val) {
                $goodsDetail = $params[$val];
                foreach ($goodsDetail as $item) {
                    if (empty($item['goodsName'])) {
                        throw_exception('业务参数goodsName必填');
                    }
                }
            }
        }
        if (!in_array($params['payType'], ['WX_JSAPI', 'WX_MINI'])) {
            throw_exception('支付类型目前仅支持微信公众号/微信小程序');
        }

        $date = time();
        $data = $this->getSignData($merOrderId,$params,$date);

        $data['serverCode'] = $serverCode;
        $sign = Mallbook::getSign($data);
        
        if (empty($sign)) {
            throw_exception('签名失败');
        }
        $data['sign'] = $sign;
        $result = Mallbook::curlPost($this->apiUrl,$data);
        if (!is_array($result)) {
            $result = json_decode($result,true);
        }
        $veritySign = $result['sign'] ?? '';
        if (!Mallbook::verify($result,$veritySign)) {
            throw_exception('返回值验签失败');
        }
        if ('0000' == $result['code']) {
            $result['result']            = json_decode($result['result'], true);
            $result['result']['payCode'] = json_decode($result['result']['payCode'], true);
        } else {
            throw_exception($result['msg'], Exception::class, $result['code']);
        }
        return $result['result'];
    }
    
    /**
     * 退款
     * @param $merOrderId
     * @param $params
     * @param string $serverCode
     * @return bool|string
     */
    public function refund($merOrderId,$params,$serverCode = "refund") {
        if (empty($merOrderId) || empty($params)) {
            throw_exception('退款参数缺失');
        }

        $mustParams = [
            'originalMerOrderId',
            'refundAmount',
            'notifyUrl',
        ];

        foreach ($mustParams as $val) {
            if (empty($params[$val])) {
                throw_exception('业务参数'.$val.'不能为空');
            }
        }

        $date = time();
        $data = $this->getSignData($merOrderId,$params,$date);

        $data['serverCode'] = $serverCode;
        $sign = Mallbook::getSign($data);

        if (empty($sign)) {
            throw_exception('签名失败');
        }
        $data['sign'] = $sign;
        $result = Mallbook::curlPost($this->apiUrl,$data);
        if (!is_array($result)) {
            $result = json_decode($result,true);
        }
        $veritySign = $result['sign'] ?? '';
        if (!Mallbook::verify($result,$veritySign)) {
            throw_exception('返回值验签失败');
        }
        if ('0000' == $result['code']) {
            $result['result'] = json_decode($result['result'], true);
        } else {
            fdump_api([
                'mer_order_id' => $merOrderId,
                'params'       => $params,
                'response'     => $result
            ], 'mallbook/refund_error', true);
            throw_exception($result['msg'], Exception::class, $result['code']);
        }
        return $result;
    }
    
    /**
     * 确认收货接口
     * @param $merOrderId
     * @param $params
     * @param string $serverCode
     * @return bool|string
     */
    public function receive($merOrderId,$params,$serverCode = "receive") {
        if (empty($merOrderId) || empty($params)) {
            throw_exception('收货参数缺失');
        }

        $mustParams = [
            'originalMerOrderId',
            'rcvAmount',
            'splitList',
        ];

        foreach ($mustParams as $val) {
            if (empty($params[$val])) {
                throw_exception('业务参数'.$val.'必填');
            }
        }

        $date = time();
        $data = $this->getSignData($merOrderId,$params,$date);

        $data['serverCode'] = $serverCode;
        $sign = Mallbook::getSign($data);

        if (empty($sign)) {
            throw_exception('签名失败');
        }
        $data['sign'] = $sign;
        $result = Mallbook::curlPost($this->apiUrl,$data);
        if (!is_array($result)) {
            $result = json_decode($result,true);
        }
        $veritySign = $result['sign'] ?? '';
        if (!Mallbook::verify($result,$veritySign)) {
            throw_exception('返回值验签失败');
        }
        if ('0000' == $result['code']) {
            $result['result'] = json_decode($result['result'], true);
        } else {
            throw_exception($result['msg'], Exception::class, $result['code']);
        }
        return $result['result'];
    }
    
    /**
     * 结算接口
     * @param $merOrderId
     * @param $params
     * @param string $serverCode
     * @return bool|string
     */
    public function withdraw($merOrderId,$params,$serverCode = "withdraw") {
        if (empty($merOrderId) || empty($params)) {
            return false;
        }

        $mustParams = [
            'userId',
            'amount',
            'notifyUrl',
        ];

        foreach ($mustParams as $val) {
            if (empty($params[$val])) {
                self::$errorMsg = '业务参数'.$val.'必填';
                return false;
            }
        }

        $date = time();
        $data = $this->getSignData($merOrderId,$params,$date);

        $data['serverCode'] = $serverCode;
        $sign = Mallbook::getSign($data);

        if (empty($sign)) {
            return false;
        }
        $data['sign'] = $sign;
        $result = Mallbook::curlPost($this->apiUrl,$data);
        if (!is_array($result)) {
            $result = json_decode($result,true);
        }
        $veritySign = $result['sign'] ?? '';
        if (!Mallbook::verify($result,$veritySign)) {
            self::$errorMsg = '返回值验签失败';
            return false;
        }
        return $result;
    }
    
    /**
     * 异步分账接口
     * @param $merOrderId
     * @param $params
     * @param string $serverCode
     * @return bool|string
     */
    public function complete($merOrderId,$params,$serverCode = "complete") {
        if (empty($merOrderId) || empty($params)) {
            throw_exception('分账参数缺失');
        }
        fdump_api([
            'msg' => '异步分账',
            'params' => $params
        ], 'mallbook/separateMoney', true);
        $mustParams = [
            'originalMerOrderId',
            'splitList',
            'notifyUrl',
        ];

        foreach ($mustParams as $val) {
            if (empty($params[$val])) {
                throw_exception('业务参数'.$val.'必填');
            }
        }

        $date = time();
        $data = $this->getSignData($merOrderId,$params,$date);

        $data['serverCode'] = $serverCode;
        $sign = Mallbook::getSign($data);

        if (empty($sign)) {
            throw_exception('签名失败');
        }
        $data['sign'] = $sign;
        $result = Mallbook::curlPost($this->apiUrl,$data);
        if (!is_array($result)) {
            $result = json_decode($result,true);
        }
        $veritySign = $result['sign'] ?? '';
        if (!Mallbook::verify($result,$veritySign)) {
            throw_exception('返回值验签失败');
        }
        if ('0000' == $result['code']) {
            $result['result'] = json_decode($result['result'], true);
        } else {
            throw_exception($result['msg'], Exception::class, $result['code']);
        }
        return $result['result'];
    }

    /**
     * 支付关单接口
     * @param $merOrderId
     * @param $params
     * @param string $serverCode
     * @return bool|string
     */
    public function payOrderClose($merOrderId,$params,$serverCode = "payOrderClose") {
        if (empty($merOrderId) || empty($params)) {
            return false;
        }

        $mustParams = ['originalMerOrderId'];

        foreach ($mustParams as $val) {
            if (empty($params[$val])) {
                self::$errorMsg = '业务参数'.$val.'必填';
                return false;
            }
        }

        $date = time();
        $data = $this->getSignData($merOrderId,$params,$date);

        $data['serverCode'] = $serverCode;
        $sign = Mallbook::getSign($data);

        if (empty($sign)) {
            return false;
        }
        $data['sign'] = $sign;
        $result = Mallbook::curlPost($this->apiUrl,$data);
        if (!is_array($result)) {
            $result = json_decode($result,true);
        }
        $veritySign = $result['sign'] ?? '';
        if (!Mallbook::verify($result,$veritySign)) {
            self::$errorMsg = '返回值验签失败';
            return false;
        }
        return $result;
    }

    /**
     * 交易查询接口
     * @param $merOrderId
     * @param $params
     * @param string $serverCode
     * @return bool|string
     */
    public function queryOrder($merOrderId,$params,$serverCode = "queryOrder") {
        if (empty($merOrderId) || empty($params)) {
            throw_exception('查询参数缺失');
        }

        $mustParams = ['originalMerOrderId','queryType'];

        foreach ($mustParams as $val) {
            if (empty($params[$val])) {
                throw_exception('业务参数'.$val.'必填');
            }
        }

        $date = time();
        $data = $this->getSignData($merOrderId,$params,$date);

        $data['serverCode'] = $serverCode;
        $sign = Mallbook::getSign($data);

        if (empty($sign)) {
            throw_exception('签名生成失败');
        }
        $data['sign'] = $sign;
        $result = Mallbook::curlPost($this->apiUrl,$data);
        if (!is_array($result)) {
            $result = json_decode($result,true);
        }
        $veritySign = $result['sign'] ?? '';
        if (!Mallbook::verify($result,$veritySign)) {
            throw_exception('返回值验签失败');
        }
        
        if ('0000' == $result['code']) {
            $result['result'] = json_decode($result['result'], true);
        } else {
            throw_exception($result['msg'], Exception::class, $result['code']);
        }

        return $result['result'];
    }


    /**
     * 账户资金查询接口
     * @param $merOrderId
     * @param $params
     * @param string $serverCode
     * @return bool|string
     */
    public function queryBalance($merOrderId,$params,$serverCode = "queryBalance") {
        if (empty($merOrderId) || empty($params)) {
            return false;
        }

        $mustParams = ['userId'];

        foreach ($mustParams as $val) {
            if (empty($params[$val])) {
                self::$errorMsg = '业务参数'.$val.'必填';
                return false;
            }
        }

        $date = time();
        $data = $this->getSignData($merOrderId,$params,$date);

        $data['serverCode'] = $serverCode;
        $sign = Mallbook::getSign($data);

        if (empty($sign)) {
            return false;
        }
        $data['sign'] = $sign;
        $result = Mallbook::curlPost($this->apiUrl,$data);
        if (!is_array($result)) {
            $result = json_decode($result,true);
        }
        $veritySign = $result['sign'] ?? '';
        if (!Mallbook::verify($result,$veritySign)) {
            self::$errorMsg = '返回值验签失败';
            return false;
        }
        return $result;
    }

    /**
     * 获取主要签名参数通用方法
     * @param $merOrderId
     * @param $params
     * @param $date
     * @return array
     */
    public function getSignData($merOrderId,$params,$date) {
        return [
            'merOrderId' => $merOrderId,
            'params' => json_encode($params),
            'date' => $date,
            'merchantNo' => $this->merchantNo,
            'channelType' => $this->channelType,
            'version' => $this->version
        ];
    }
}