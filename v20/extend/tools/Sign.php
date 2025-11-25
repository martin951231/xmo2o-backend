<?php
/**
 * 接口签名类
 * 
 * sign生成规则：
 * 1、非空参数（sign除外）按照字典序排列拼接得到参数串 “参数名1=值1&参数名2=值2&参数3=值3...”
 * 2、参数串后面拼接上“参数名1=值1&参数名2=值2&参数3=值3...&key=密钥”
 * 3、对参数串进行加密得到sign，加密规则默认md5
 * 4、将sign字母转成大写
 */
namespace tools;

class Sign
{
    //加密规则 1 = md5
    private $encryptionRules = 1;

    private $checkStatus = false;

    //消息
    private $errorMsg = '';

    //根据提交的参数生成的参数串
    private $signStr = '';

    //根据提交的参数生成的sign
    private $nowSign = '';

    //密钥
    private $secretKey = '';

    //提交的参数
    private $checkParams = [];
    private $checkSignStr = '';

    private $logId = '';

    /**
     * 实例化
     * @param integer encryptionRules 加密方式，1=md5
     */
    public function __construct($encryptionRules = 1)
    {
        $this->encryptionRules = $encryptionRules;
    }

    /**
     * 签名验签
     * @param array params 参数列表 
     * @param string sign 需要验证的sign
     * @param string secret 密钥
     * @return bool true | false
     * 
     */
    public function check($params, $sign, $secret)
    {
        try {
            //生成唯一验签ID
            $this->getLogId();
            //验证参数
            $this->checkParams($params, $sign, $secret);
            //生成签名串
            $this->createSignStr();
            //加密
            $this->encrypt();
            //验签
            $this->checkSign();

        } catch (\Exception $e) {
            $this->errorMsg = $e->getMessage();
            return $this->checkStatus = false;
        }
       
        return $this->checkStatus;
    }


    /**
     * 获取上次验签的详细信息
     * 
     * @return  array['log_id'] 验签ID
     *          array['check_status'] 验签状态
     *          array['error_msg'] 报错信息
     *          array['now_sign_str'] 根据提交的参数生成的签名串
     *          array['now_sign'] 根据提交的参数生成的sign
     *          array['request_params'] 提交的参数
     */
    public function getInfo()
    {
        $info = [];
        $info['log_id'] = $this->logId;
        $info['check_status'] = $this->checkStatus;
        $info['error_msg'] = $this->errorMsg;
        $info['now_sign_str'] = $this->signStr;
        $info['now_sign'] = $this->nowSign;
        $info['request_params'] = $this->checkParams;
        $info['request_params']['sign'] = $this->checkSignStr;
        return $info;
    }

    /**
     * 验证参数
     */
    private function checkParams($params, $sign, $secret,$is_check=1)
    {
        if(!is_array($params) || !count($params) || ($is_check&&!$sign) || !$secret){
            throw new \think\Exception("缺少必要参数");
        }
        //ASCLL码顺序排列
        ksort($params);

        foreach ($params as $key => $value) {
            if($value == ''){
                unset($params[$key]);
            }else{
                if(is_array($value)){
                    $params[$key] = json_encode($value,JSON_UNESCAPED_UNICODE);
                }
            }
        }
        
        $this->checkParams = $params;
        $this->checkSignStr = $sign;
        $this->secretKey = $secret;
    }

    
    /**
     * 生成唯一验签ID
     */
    private function getLogId()
    { 
        $chars = md5(uniqid(mt_rand(), true));  
        $log = substr ( $chars, 0, 8 ) . '-'
                . substr ( $chars, 8, 4 ) . '-' 
                . substr ( $chars, 12, 4 ) . '-'
                . substr ( $chars, 16, 4 ) . '-'
                . substr ( $chars, 20, 12 );  
        $this->logId =  $log;
    }

    /**
     * 生成签名串
     */
    private function createSignStr()
    {
        $params = $this->checkParams;
        $secret = $this->secretKey;
        $signStr = '';
        foreach ($params as $key => $value) {
            $signStr .= $key . '=' . $value . '&';
        } 
        $signStr = rtrim($signStr, '&');
        $signStr .= '&key=' . $secret;

        $this->signStr = $signStr;
    }

    /**
     * 加密
     */
    private function encrypt()
    {
        $nowSign = '';
        switch($this->encryptionRules){
            case 1:
                $nowSign = md5($this->signStr);
                break;
        }
        $this->nowSign = strtoupper($nowSign);
    }

    /**
     * 验签
     */
    private function checkSign()
    {
        if($this->checkSignStr !== $this->nowSign){
            throw new \think\Exception("签名验证失败");
        } 
        $this->checkStatus = true;
    }
    
    /**
     * 生成签名
     */
    public function getSign($params,$secret)
    {
        try {
            if(!is_array($params) || !count($params) || !$secret){
                throw new \think\Exception("缺少必要参数");
            }
            //ASCLL码顺序排列
            ksort($params);
            $signStr = '';
            foreach ($params as $key => $value) {
                if($value === '' || $value==[]){
                    unset($params[$key]);
                }else{
                    if(is_array($value)){
                        $value = json_encode($value,JSON_UNESCAPED_UNICODE);
                    }
                    $signStr .= $key . '=' . $value . '&';
                }
            }
            $signStr = rtrim($signStr, '&');
            $signStr .= '&key=' . $secret;
            
            //加密
            $nowSign = '';
            switch($this->encryptionRules){
                case 1:
                    $nowSign = md5($signStr);
                    break;
            }
            $sign = strtoupper($nowSign);
        } catch (\Exception $e) {
            $this->errorMsg = $e->getMessage();
            throw new \think\Exception($e->getMessage());
        }

        return $sign;
    }

    /**
     * 签名验签
     * @param array params 参数列表
     * @param string sign 需要验证的sign
     * @param string secret 密钥
     * @return bool true | false
     *
     */
    public function getSignInfo($params, $secret)
    {
        try {
            //生成唯一验签ID
            $this->getLogId();
            //验证参数
            $this->checkParams($params, '', $secret,0);
            //生成签名串
            $this->createSignStr();
            //加密
            $this->encrypt();

        } catch (\Exception $e) {
            $this->errorMsg = $e->getMessage();
            throw new \think\Exception($e->getMessage());
        }

        return $this->nowSign;
    }
}