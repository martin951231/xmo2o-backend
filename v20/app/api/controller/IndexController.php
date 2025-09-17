<?php
namespace app\api\controller;

use mallbook\Mallbook;
use mallbook\MallbookApi;
use think\Config;
use think\Request;

class IndexController
{
    public function __construct()
    {
        dd('禁止请求');
    }
    
    public function register(Request $request)
    {
        $merOrderId = 'TEST'.time().rand(0,9999);
        $params = $request->input();
        if (empty($params)) {
            $params = [
                'merUserId' => '972205056694',
                'phone' => '18159801234',
                'userType' => '0',
                'userName' => 'test',
                'certId' => '111',
                'notifyUrl' => 'http://mallbook.com/callback',
            ];
        }
        $mallbookApi = new MallbookApi();
        $result = $mallbookApi->register($merOrderId,$params);
        $this->message($result);
    }

    /**
     * 统一回调接收方法
     * @param Request $request
     * @return string
     */
    public function callback(Request $request) {
        $params = file_get_contents('php://input');
        $logInfo = $params;
        if (!is_array($params)) {
            $params = json_decode($params,true);
        }
        $sign = $params['sign'];


        /*$code = $request->param('code');
        $msg = $request->param('msg');
        $date = $request->param('date');
        $result = $request->param('result');
        $sign = $request->param('sign');
        $params = [
            'code' => $code,
            'msg' => $msg,
            'date' => $date,
            'result' => $result,
        ];*/
        $filePath = ROOT_PATH . 'public' . DS . 'log';
        if (!file_exists($filePath)) {
            mkdir($filePath,0777);
        }

        if (!Mallbook::verify($params,$sign)) {
            $logInfo = '返回值验签失败';
        }
        file_put_contents($filePath . DS . 'log.txt',$logInfo);
        // TODO 正常业务逻辑处理
        return json(['code'=>200,'msg' => 'success']);
    }

    public function bindAccount(Request $request) {
        $merOrderId = 'TEST'.time().rand(0,9999);
        $params = $request->input();
        if (empty($params)) {
            $params = [
                'userId' => '1',
                'certId' => '11',
                'cardName' => '111',
                'cardNo' => '1111',
                'bankAcctType' => '11',
                'phone' => '18159801234',
            ];
        }

        $mallbookApi = new MallbookApi();
        $result = $mallbookApi->bindAccount($merOrderId,$params);
        $this->message($result);
    }


    public function unBindAccount(Request $request) {
        $merOrderId = 'TEST'.time().rand(0,9999);
        $params = $request->input();
        if (empty($params)) {
            $params = [
                'userId' => '1',
            ];
        }

        $mallbookApi = new MallbookApi();
        $result = $mallbookApi->unBindAccount($merOrderId,$params);
        
        $this->message($result);
    }


    public function fileUpload() {
        $merOrderId = 'TEST'.time().rand(0,9999);
        $files = request()->file('file');
        if (!empty($files)) {
            $params = [
                'merUserId' => '1',
                'file' => $files,
                'uploadPath' => ROOT_PATH . 'public' . DS . 'images',//文件保存绝对路径 可选
                'zipPath' => ''//压缩文件保存目录 可选
            ];
            $mallbookApi = new MallbookApi();
            $result = $mallbookApi->fileUpload($merOrderId,$params);
            echo MallbookApi::$errorMsg;
            $this->message($result);
        }
        $this->message(['code'=>100,'message'=>'fail','data'=>[]]);
    }

    //子商户前台注册在index模块index控制器下
    public function registerFront(Request $request) {
        $merOrderId = 'TEST'.time().rand(0,9999);
        $params = $request->input();
        if (empty($params)) {
            $params = [
                'merUserId' => '1',
                'userType' => '1',
                'frontUrl' => 'http://mallbook.com/callback',
                'notifyUrl' => 'http://mallbook.com/callback',
            ];
        }

        $mallbookApi = new MallbookApi();
        $result = $mallbookApi->registerFront($merOrderId,$params);
        
        $this->message($result);
    }


    public function paymentOrder(Request $request) {
       
        $merOrderId = 'TEST16922664501953';//'TEST'.time().rand(0,9999);
        $params = $request->input();
        if (empty($params)) {
            $params = [
                'bizOrderId' => '653955072287',
                'amount' => '2',
                'orderName' => '测试订单',
                'payType' => 'WX_JSAPI',
                'appid' => 'wx3beb6fd1c2fa0fa8',//微信小程序必填
                'openid' => 'ocvyb5gdU6yRgO17M-TuXbx2whmk',//微信公众号/微信小程序必填
//                'transferType' => '0',
//                'asynSplitFlag' => '1',
                'terminalIp' => '127.0.0.1',
                //'payeeUserId' => 'MBY23012B',
//                'splitList' => [
////                    ['splitUserId' => '62544486411','splitAmount'=>'1','splitType'=>"1"],
//                    ['splitUserId' => 'MBY23012B','splitAmount'=>'1','splitType'=>"1"]
//                ],
                'goodsDetail' => [['goodsId' => '001','goodsName'=>"测试商品1"]],
                'frontUrl' => 'https://6y764e9057.zicp.fun/callback',
                'notifyUrl' => 'https://6y764e9057.zicp.fun/callback',
            ];
        }
        $mallbookApi = new MallbookApi();
        $result = $mallbookApi->paymentOrder($merOrderId,$params);
        
        $this->message(['code' => '0000','result' => $result]);
    }

    public function refund(Request $request) {
        $merOrderId = 'TEST'.time().rand(0,9999);
        $params = input();
        if (empty($params)) {
            $params = [
                'originalMerOrderId' => '1',
                'refundAmount' => '1',
                'notifyUrl' => '1',
            ];
        }

        $mallbookApi = new MallbookApi();
        $result = $mallbookApi->refund($merOrderId,$params);
        
        $this->message($result);
    }

    public function receive(Request $request) {
        $merOrderId = 'TEST'.time().rand(0,9999);
        $params = $request->input();
        if (empty($params)) {
            $params = [
                'originalMerOrderId' => '1',
                'rcvAmount' => '100',
                'splitList' => ['splitUserId' => '1','rcvSplitAmount' => '1'],
            ];
        }

        $mallbookApi = new MallbookApi();
        $result = $mallbookApi->receive($merOrderId,$params);
        
        $this->message($result);
    }

    public function withdraw(Request $request) {
        $merOrderId = 'TEST'.time().rand(0,9999);
        $params = $request->input();
        if (empty($params)) {
            $params = [
                'userId' => '1',
                'amount' => '200',
                'notifyUrl' => '11',
            ];
        }

        $mallbookApi = new MallbookApi();
        $result = $mallbookApi->withdraw($merOrderId,$params);
        
        $this->message($result);
    }


    public function deposit(Request $request) {
        $merOrderId = 'TEST'.time().rand(0,9999);
        $params = $request->input();
        if (empty($params)) {
            $params = [
                'amount' => '100',
                'orderName' => 'test',
                'payType' => 'WX_JSAPI',
                'terminalIp' => '1',
                'frontUrl' => '1',
                'notifyUrl' => '1',
            ];
        }

        $mallbookApi = new MallbookApi();
        $result = $mallbookApi->deposit($merOrderId,$params);
        
        $this->message($result);
    }


    public function transfer(Request $request) {
        $merOrderId = 'TEST'.time().rand(0,9999);
        $params = $request->input();
        if (empty($params)) {
            $params = [
                'toUserId' => '1',
                'amount' => '100',
                'orderName' => '1',
                'notifyUrl' => '1',
            ];
        }

        $mallbookApi = new MallbookApi();
        $result = $mallbookApi->transfer($merOrderId,$params);
        
        $this->message($result);
    }


    public function complete(Request $request) {
        $merOrderId = 'TEST'.time().rand(0,9999);
        $params = $request->input();
        if (empty($params)) {
            $params = [
                'originalMerOrderId' => '1',
                'splitList' => ['splitUserId' => '1','rcvSplitAmount' => '1'],
                'notifyUrl' => '11',
            ];
        }

        $mallbookApi = new MallbookApi();
        $result = $mallbookApi->complete($merOrderId,$params);
        
        $this->message($result);
    }


    public function fastBindApply(Request $request) {
        $merOrderId = 'TEST'.time().rand(0,9999);
        $params = $request->input();
        if (empty($params)) {
            $params = [
                'userId' => '1',
                'bankCardNo' => '11',
                'telNo' => '11',
            ];
        }

        $mallbookApi = new MallbookApi();
        $result = $mallbookApi->fastBindApply($merOrderId,$params);
        
        $this->message($result);
    }


    public function fastBindConfirm(Request $request) {
        $merOrderId = 'TEST'.time().rand(0,9999);
        $params = $request->input();
        if (empty($params)) {
            $params = [
                'originalMerOrderId' => '11',
                'smsCode' => '111111',
                'notifyUrl' => '11',
            ];
        }

        $mallbookApi = new MallbookApi();
        $result = $mallbookApi->fastBindConfirm($merOrderId,$params);
        
        $this->message($result);
    }


    public function fastUnbind(Request $request) {
        $merOrderId = 'TEST'.time().rand(0,9999);
        $params = $request->input();
        if (empty($params)) {
            $params = [
                'userId' => '1',
                'tokenNo' => '11',
                'notifyUrl' => '11',
            ];
        }

        $mallbookApi = new MallbookApi();
        $result = $mallbookApi->fastUnbind($merOrderId,$params);
        
        $this->message($result);
    }


    public function fastpayConfirm(Request $request) {
        $merOrderId = 'TEST'.time().rand(0,9999);
        $params = $request->input();
        if (empty($params)) {
            $params = [
                'originalMerOrderId' => '11',
                'smsCode' => '111122',
            ];
        }

        $mallbookApi = new MallbookApi();
        $result = $mallbookApi->fastpayConfirm($merOrderId,$params);
        
        $this->message($result);
    }


    public function fastpaySendSms(Request $request) {
        $merOrderId = 'TEST'.time().rand(0,9999);
        $params = $request->input();
        if (empty($params)) {
            $params = [
                'originalMerOrderId' => '11',
            ];
        }

        $mallbookApi = new MallbookApi();
        $result = $mallbookApi->fastpaySendSms($merOrderId,$params);
        
        $this->message($result);
    }


    public function payOrderClose(Request $request) {
        $merOrderId = 'TEST'.time().rand(0,9999);
        $params = $request->input();
        if (empty($params)) {
            $params = [
                'originalMerOrderId' => '1',
            ];
        }

        $mallbookApi = new MallbookApi();
        $result = $mallbookApi->payOrderClose($merOrderId,$params);
        
        $this->message($result);
    }


    public function queryUser(Request $request) {
        $merOrderId = 'TEST'.time().rand(0,9999);
        $params = $request->input();
        if (empty($params)) {
            $params = [
                'merUserId' => '1',
            ];
        }

        $mallbookApi = new MallbookApi();
        $result = $mallbookApi->queryUser($merOrderId,$params);
        
        $this->message($result);
    }


    public function queryOrder(Request $request) {
        $merOrderId = 'TEST'.time().rand(0,9999);
        $params = $request->input();
        if (empty($params)) {
            $params = [
                'originalMerOrderId' => '1',
                'queryType' => '1',
            ];
        }

        $mallbookApi = new MallbookApi();
        $result = $mallbookApi->queryOrder($merOrderId,$params);
        
        $this->message($result);
    }


    public function queryBalance(Request $request) {
        $merOrderId = 'TEST'.time().rand(0,9999);
        $params = $request->input();
        if (empty($params)) {
            $params = [
                'userId' => '1',
            ];
        }

        $mallbookApi = new MallbookApi();
        $result = $mallbookApi->queryBalance($merOrderId,$params);
        
        $this->message($result);
    }


    public function queryFastCard(Request $request) {
        $merOrderId = 'TEST'.time().rand(0,9999);
        $params = $request->input();
        if (empty($params)) {
            $params = [
                'userId' => '1',
            ];
        }

        $mallbookApi = new MallbookApi();
        $result = $mallbookApi->queryFastCard($merOrderId,$params);
        
        $this->message($result);
    }


    /**
     * 生成ssl公私钥方法
     * @return array|bool
     */
    public function createKey() {

        $config = [
            'config' => 'D:/php_env/phpstudy_pro/Extensions/php/php7.4.3nts/extras/ssl/openssl.cnf',
            'digest_alg' => 'SHA1WithRSA',
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA
        ];
        $res = openssl_pkey_new($config);

        if($res == false) return false;

        openssl_pkey_export($res, $private_key,null,$config);

        $public_key = openssl_pkey_get_details($res);

        // 写入公钥文件
        $publicKeyFile = cfg('mallbook_my_public_rsa_key');
        if (!empty($public_key['key']) && !file_exists($publicKeyFile)) {
            file_put_contents($publicKeyFile,$public_key['key']);
        }

        // 写入私钥文件
        $privateKeyFile = Config::get('mallbook.private_key_file');
        if (!empty($private_key) && !file_exists($privateKeyFile)) {
            file_put_contents($privateKeyFile,$private_key);
        }

        halt(array('public_key'=>$public_key["key"],'private_key'=>$private_key));die;
        return array('public_key'=>$public_key["key"],'private_key'=>$private_key);

    }

    public function message($data) {
        header('Content-type: application/json');
        echo json_encode($data,320);die();
    }
}
