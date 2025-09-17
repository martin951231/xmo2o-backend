<?php
namespace mallbook;

use think\Exception;
use think\facade\Config;

class MallbookApi {

    //api接口地址
    protected $apiUrl;
    //平台商户编号
    protected $merchantNo;
    //渠道类型
    protected $channelType;
    //接口版本号
    protected $version;

    public static $errorMsg = "";

    public function __construct($merchantNo = '')
    {
        $this->apiUrl      = Config::get('mallbook.apiUrl') ?? "https://uat.mallbook.cn/api";
        $this->merchantNo  = $merchantNo ?: cfg('mallbook_merchant_no');
        $this->channelType = Config::get('mallbook.channelType') ?? "HF";
        $this->version     = Config::get('mallbook.version') ?? "1.0.0";
    }

    /**
     * 子商户注册
     * @param $merOrderId
     * @param $params
     * @param string $serverCode
     * @return bool|string
     */
    public function register($merOrderId,$params,$serverCode = "register") {
        if (empty($merOrderId) || empty($params)) {
            return false;
        }

        $mustParams = [
            'merUserId',
            'phone',
            'userType',
            'userName',
            'certId',
            'notifyUrl',
        ];

        foreach ($mustParams as $val) {
            if (!isset($params[$val]) || $params[$val] == '') {
                throw new \think\Exception(L_('业务参数'.$val.'必填'));
            }
        }

        $date = time();
        $data = $this->getSignData($merOrderId,$params,$date);
        $data['serverCode'] = $serverCode;
        $sign = Mallbook::getSign($data);

        if (empty($sign)) {
            throw new \think\Exception(L_('签名失败'));
        }
        $data['sign'] = $sign;
        $result = Mallbook::curlPost($this->apiUrl,$data);
        fdump_api($result,'yibao/regist_result',1);
        if (!is_array($result)) {
            $result = json_decode($result,true);
        }
        $veritySign = $result['sign'] ?? '';
        if (!Mallbook::verify($result,$veritySign)) {
            throw new \think\Exception(L_('返回值验签失败'));
        }
        return $result;
    }

    /**
     * 子商户绑定结算账户
     * @param $merOrderId
     * @param $params
     * @param string $serverCode
     * @return bool|string
     */
    public function bindAccount($merOrderId,$params,$serverCode = "bindAccount") {
        if (empty($merOrderId) || empty($params)) {
            return false;
        }

        $mustParams = [
            'userId',
            'certId',
            'cardName',
            'cardNo',
            'bankAcctType',
            'phone',
        ];

        foreach ($mustParams as $val) {
            if (empty($params[$val])) {
                throw new \think\Exception(L_('业务参数'.$val.'必填'));
            }
        }

        $date = time();
        $data = $this->getSignData($merOrderId,$params,$date);

        $data['serverCode'] = $serverCode;
        $sign = Mallbook::getSign($data);

        if (empty($sign)) {
            throw new \think\Exception(L_('签名失败'));
        }
        $data['sign'] = $sign;
        $result = Mallbook::curlPost($this->apiUrl,$data);
        if (!is_array($result)) {
            $result = json_decode($result,true);
        }
        $veritySign = $result['sign'] ?? '';
        if (!Mallbook::verify($result,$veritySign)) {
            throw new \think\Exception(L_('返回值验签失败'));
        }
        return $result;
    }

    /**
     * 子商户解绑结算账户
     * @param $merOrderId
     * @param $params
     * @param string $serverCode
     * @return bool|string
     */
    public function unBindAccount($merOrderId,$params,$serverCode = "unbindAccount") {
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
     * 文件上传
     * @param $merOrderId
     * @param $params
     * @param string $serverCode
     * @return bool|string
     */
    public function fileUpload($merOrderId,$params,$serverCode = "fileUpload") {
        if (empty($merOrderId) || empty($params)) {
            throw new \think\Exception(L_('参数错误！'));
        }

        $files = $params['file'];
        $zipPath = $params['zipPath'];
        if (!file_exists($zipPath)) {
            mkdir($zipPath,0777);
        }
        unset($params['zipPath']);
        $uploadPath = $params['uploadPath'];
        if (!file_exists($uploadPath)) {
            mkdir($zipPath,0777);
        }
        unset($params['uploadPath']);
        // 生成压缩文件
        $date = time();
        $zip = new \ZipArchive();
        $zipName = 'fileUpload'.$date. '_' .rand(0,999999).'.zip';
        $zip->open($zipPath . '/' . $zipName,\ZipArchive::CREATE);
        foreach ($files as $file) {
            /*$info = $file->move($uploadPath,'');
            $filePath = $info->getPathname();
            $fileNames = $info->getFileName();
            $fileName = explode('.',$fileNames)[0];
            if (strlen($fileName) > 32) {
                self::$errorMsg = '文件名称长度不得超过32位';
                return false;
            }
            $suffix = $info->getExtension();
            $suffixArr = ['jpg','png','bmp','gif'];
            if (!in_array($suffix,$suffixArr)) {
                self::$errorMsg = '上传文件后缀不合法';
                return false;
            }*/
            $file_name = explode('/',$file);
            $zip->addFile($file,$file_name[count($file_name)-1]);//添加到压缩文件
        }
        $zip->close();//关闭压缩文件资源
        // 校验文件大小
        if (filesize($zipPath . '/' . $zipName) > 9 * 1024 * 1024) {
            throw new \think\Exception(L_('上传文件不得超过9M！'));
        }
        $bytes = file_get_contents($zipPath . '/' .$zipName);//压缩文件转二进制字节码
        $params['file'] = base64_encode($bytes);//转base64
        $params['name'] = $zipName;
        $params['merUserId'] = $params['mer_id'];
        fdump_api($params, "mallBook/{$serverCode}", true);
        $data = $this->getSignData($merOrderId,$params,$date);

        $data['serverCode'] = $serverCode;
        $sign = Mallbook::getSign($data);

        if (empty($sign)) {
            throw new \think\Exception(L_('签名错误！'));
        }
        $data['sign'] = $sign;
        $result = Mallbook::curlPost($this->apiUrl,$data);
        if (!is_array($result)) {
            $result = json_decode($result,true);
        }
        $veritySign = $result['sign'] ?? '';
        if (!Mallbook::verify($result,$veritySign)) {
            throw new \think\Exception(L_('返回值验签失败！'));
        }
        return $result;
    }

    /**
     * 子商户前台注册
     * @param $merOrderId
     * @param $params
     * @param string $serverCode
     * @return bool|string
     */
    public function registerFront($merOrderId,$params,$serverCode = "registerFront") {
        if (empty($merOrderId) || empty($params)) {
            return false;
        }

        $date = time();
        $data = $this->getSignData($merOrderId,$params,$date);
        $data['serverCode'] = $serverCode;
        $sign = Mallbook::getSign($data);

        if (empty($sign)) {
            return false;
        }
        $data['sign'] = $sign;
        return $data;

        $result = Mallbook::curlPost($this->apiUrl,$data);
        if (!is_array($result)) {
            $result = json_decode($result,true);
        }
        halt($result);
        $veritySign = $result['sign'] ?? '';
        if (!Mallbook::verify($result,$veritySign)) {
            self::$errorMsg = '返回值验签失败';
            return false;
        }
        return $result;
    }
    
    /**
     * 支付接口
     * @param $merOrderId
     * @param $params
     * @param string $serverCode
     * @return bool|string
     */
    public function paymentOrder($merOrderId, $params, $serverCode = "paymentOrder")
    {
        if (empty($merOrderId) || empty($params)) {
            throw_exception('支付参数缺失');
        }
        $mustParams = [
            'bizOrderId', 'amount', 'orderName', 'payType', 'terminalIp',
            'goodsDetail', 'frontUrl', 'notifyUrl', 'appid', 'openid'
        ];
        
        $params['transferType']  = '0';//担保交易
        $params['asynSplitFlag'] = '1';//异步分账
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
        $data = $this->getSignData($merOrderId, $params, $date);

        $data['serverCode'] = $serverCode;
        $sign = Mallbook::getSign($data);

        if (empty($sign)) {
            throw_exception('签名失败');
        }
        $data['sign'] = $sign;
        $result = Mallbook::curlPost($this->apiUrl, $data);
        if (!is_array($result)) {
            $result = json_decode($result, true);
        }
        $veritySign = $result['sign'] ?? '';
        if (!Mallbook::verify($result, $veritySign)) {
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
     * 充值接口
     * @param $merOrderId
     * @param $params
     * @param string $serverCode
     * @return bool|string
     */
    public function deposit($merOrderId,$params,$serverCode = "deposit") {
        if (empty($merOrderId) || empty($params)) {
            return false;
        }

        $mustParams = [
            'amount',
            'orderName',
            'payType',
            'terminalIp',
            'frontUrl',
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
     * 转账接口
     * @param $merOrderId
     * @param $params
     * @param string $serverCode
     * @return bool|string
     */
    public function transfer($merOrderId,$params,$serverCode = "transfer") {
        if (empty($merOrderId) || empty($params)) {
            return false;
        }

        $mustParams = [
            'toUserId',
            'amount',
            'orderName',
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
     * 快捷绑卡申请接口
     * @param $merOrderId
     * @param $params
     * @param string $serverCode
     * @return bool|string
     */
    public function fastBindApply($merOrderId,$params,$serverCode = "fastBindApply") {
        if (empty($merOrderId) || empty($params)) {
            return false;
        }

        $mustParams = [
            'userId',
            'bankCardNo',
            'telNo',
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
     * 快捷绑卡确认接口
     * @param $merOrderId
     * @param $params
     * @param string $serverCode
     * @return bool|string
     */
    public function fastBindConfirm($merOrderId,$params,$serverCode = "fastBindConfirm") {
        if (empty($merOrderId) || empty($params)) {
            return false;
        }

        $mustParams = [
            'originalMerOrderId',
            'smsCode',
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
     *  解绑快捷卡接口
     * @param $merOrderId
     * @param $params
     * @param string $serverCode
     * @return bool|string
     */
    public function fastUnbind($merOrderId,$params,$serverCode = "fastUnbind") {
        if (empty($merOrderId) || empty($params)) {
            return false;
        }

        $mustParams = [
            'userId',
            'tokenNo',
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
     * 快捷支付确认接口
     * @param $merOrderId
     * @param $params
     * @param string $serverCode
     * @return bool|string
     */
    public function fastpayConfirm($merOrderId,$params,$serverCode = "fastpayConfirm") {
        if (empty($merOrderId) || empty($params)) {
            return false;
        }

        $mustParams = [
            'originalMerOrderId',
            'smsCode',
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
     * 快捷支付短信重发接口
     * @param $merOrderId
     * @param $params
     * @param string $serverCode
     * @return bool|string
     */
    public function fastpaySendSms($merOrderId,$params,$serverCode = "fastpaySendSms") {
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
     * 子商户查询接口
     * @param $merOrderId
     * @param $params
     * @param string $serverCode
     * @return bool|string
     */
    public function queryUser($merOrderId,$params,$serverCode = "queryUser") {
        if (empty($merOrderId) || empty($params)) {
            return false;
        }

        $mustParams = ['merUserId'];

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
     * 子商户快捷卡查询接口
     * @param $merOrderId
     * @param $params
     * @param string $serverCode
     * @return bool|string
     */
    public function queryFastCard($merOrderId,$params,$serverCode = "queryFastCard") {
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