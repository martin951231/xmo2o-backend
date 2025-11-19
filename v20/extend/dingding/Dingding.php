<?php
/**
 * Created by PhpStorm.
 * User: GuoXianSen
 * Date: 2019/4/26
 * Time: 11:46
 */
/**
 * 叮叮开放平台
 * 发单接口
 * Demo
 */
namespace dingding;
class Dingding{

    public $ServiceUrl         =   'http://api.juheps.com/open/'; //叮叮开放平台正式环境URL
    public $fromplatform       =   ''; //后台右上角接口参数中的fromplatform
    public $FormAppKey         =   ''; //后台右上角接口参数中的FormAppKey
    public $appid              =   ''; //后台右上角接口参数中的appid
    public $shop_no            =   ''; //门店编号
    public $username           =   ''; //用户名
    public $callback           =   ''; //回调URL
    public $token              =   ''; //接口获取的token
    public $mobile              =   ''; //手机号
    public $perUserid              =   ''; //第三方标识

    /**
     * 页面编码
     * Demo constructor.
     * Date: 2019-04-16
     * Time: 下午 5:34
     */
    public function __construct($fromplatform ='',$FormAppKey='',$appid='',$shop_no='',$perUserid='',$username='',$password='',$mobile='')
    {
        header('Content-Type:text/html;charset=utf8');

        $this->fromplatform = $fromplatform;
        $this->FormAppKey = $FormAppKey;
        $this->appid = $appid;
        $this->shop_no = $shop_no;
        $this->perUserid = $perUserid;
        $this->username = $username;
        $this->password = $password;
        $this->mobile = $mobile;


    }

    /**
     * 获取token
     * @return mixed
     * Date: 2019-04-16
     * Time: 下午 4:59
     */
    public function getToken(){
        $mobile             =   $this->mobile;
        $username           =   $this->username;
        $password           =   $this->password;
        $per_userid         =   $this->perUserid; //第三方用户标识(如果是多门店对接，是再创建门店的时候进行传输的)
        $timestamp          =   time();
        $postData = [
            'mobile'        =>  $mobile,
            'username'      =>  $username,
            'password'      =>  $password,
            'per_userid'    =>  $per_userid,
            'timestamp'     =>  $timestamp,
        ];
        $result = $this->curl_Data('token/getToken',$postData);
        return                  $result;
    }

    /**
     * 添加或修改单店用户信息
     * @return Mixed
     * Date: 2019-05-10
     * Time: 9.13
     */
    public function createUpdateN($postData)
    {
//        $postData = json_encode($postData);
        $result = $this->curl_Data('shop/createUpdateN',$postData);
        return                  $result;
    }

    /**
     * 查询已创建的商户信息
     * @return Mixed
     * Date 2019-05-10
     * Time 11:07
     */
    public function getShopInfo($postData)
    {
//        $postData = json_encode($postData);
        $result = $this->curl_Data('shop/getShopInfo',$postData);
        return                  $result;
    }

    /**
     * 新增配送订单
     * @return mixed
     * Date: 2019-04-16
     * Time: 下午 4:59
     */
    public function createOrder($postData){
        $getToken = $this->getToken();//2019.5前获取的token可以使用同一个

        $tokenaes = json_decode($getToken,true);
        $tokenaes = $tokenaes['result'];
        $postData['source'] = 5; //

        $postData['tokenaes'] = $tokenaes; //
        $postData['fromplatform'] = $this->fromplatform;
        $postData['username'] = $this->username;
        $postData['shop_no'] = $this->shop_no; //门店编号

        $postData['sign']   =   $this->Sign($postData);
        $postData = json_encode($postData);
        $result = $this->curl_Data('order/createOrder',$postData);
        return                  $result;
    }

    /**
     * 订单列表
     * Time:2019.04.20
     * Auth 郭先森
     * @return json
     */
    public function orderList($postData){
        //types 订单类型 （1=待接单 2=以接单 3=以完成 4=以取消 其他任意值或者不填写=全部订单）
        $getToken = $this->getToken();//2019.5前获取的token可以使用同一个

        $tokenaes = json_decode($getToken,true);
        $tokenaes = $tokenaes['result'];
        $postData['source'] = 5; //
        $postData['callback'] = $this->callback; //回调
        $postData['tokenaes'] = $tokenaes; //
        $postData['fromplatform'] = $this->fromplatform;
        $postData['username'] = $this->username;
        $postData['shop_no'] = $this->shop_no; //门店编号

        $postData['sign']   =   $this->Sign($postData);
        $result = $this->curl_Data('order/list',$postData);
        return                  $result;
    }

    /**
     * 确认取消订单
     * Time:2019.04.27
     * Auth 郭先森
     * @return json
     */
    public function orderRecord($postData){
        //origin_id  你方平台订单号
        $getToken = $this->getToken();//2019.5前获取的token可以使用同一个

        $tokenaes = json_decode($getToken,true);
        $tokenaes = $tokenaes['result'];
        $postData['source'] = 5; //
        $postData['callback'] = $this->callback; //回调
        $postData['tokenaes'] = $tokenaes; //
        $postData['fromplatform'] = $this->fromplatform;
        $postData['username'] = $this->username;
        $postData['shop_no'] = $this->shop_no; //门店编号

        $postData['sign']   =   $this->Sign($postData);
        $result = $this->curl_Data('order/orderRecord',$postData);
        return                  $result;

    }

    /**
     * 取消订单原因列表
     * Time:2019.04.27
     * Auth 郭先森
     */
    public function Reason($postData){
        //origin_id 你方平台订单号
        $getToken = $this->getToken();//2019.5前获取的token可以使用同一个

        $tokenaes = json_decode($getToken,true);
        $tokenaes = $tokenaes['result'];
        $postData['source'] = 5; //
        $postData['callback'] = $this->callback; //回调
        $postData['tokenaes'] = $tokenaes; //
        $postData['fromplatform'] = $this->fromplatform;
        $postData['username'] = $this->username;
        $postData['shop_no'] = $this->shop_no; //门店编号

        $postData['sign']   =   $this->Sign($postData);
        $result = $this->curl_Data('order/Reason',$postData);
        return                  $result;
    }

    /**
     * 确认取消订单
     * Time:2019.04.27
     * Auth 郭先森
     * @return json
     */
    public function orderCancel($postData){
        //origin_id  是 你方平台订单号
        //cancel_id 是 取消原因id，取消原因接口里的id 也可以写死 1
        //cancelReason 否 取消原因，可以为空， 当 cancel_id 为10000时必填
        $getToken = $this->getToken();//2019.5前获取的token可以使用同一个
        $tokenaes = json_decode($getToken,true);
        $tokenaes = $tokenaes['result'];
        $postData['tokenaes'] = $tokenaes; //
        $postData['fromplatform'] = $this->fromplatform;
        $postData['username'] = $this->username;
        $postData['sign']   =   $this->Sign($postData);
        fdump($postData,"dingdingorderCancel",true);

        $postData = json_encode($postData);
        $result = $this->curl_Data('order/orderCancel',$postData);
        return                  $result;
    }

    /**
     * 生成签名
     * @param $data
     *
     * @return string
     * Date: 2019-04-16
     * Time: 下午 4:35
     */
    protected function Sign($data){
        //1、进行字典按照键名进行升序排序
        ksort($data);
        $str = '';
        //2、递归循环拼接数组数据
        foreach ($data as $key => $value) {
            //3、当遇到值是数组（数据）的时候进行转化为json
            if(is_array($value)){
                $value = json_encode($value);
            }
            //4、将循环处理进行key.value拼接
            $str .= $key.$value;
        }
        //5、进行MD5（递归循环的字符串连上FormAppKey）
        $str = md5($str.$this->FormAppKey);  //连接上FormAppKey
        //6、将MD5字符串转为大写
        $str = strtoupper($str);
        return $str;
    }

    /**
     * CURL请求数据
     * @param $url
     * @param $data
     *
     * @return mixed
     * Date: 2019-04-16
     * Time: 下午 4:34
     */
    public function curl_Data($url,$data){
        $url                =   $this->ServiceUrl.$url;
        $url                =   str_replace( "&amp;", "&", urldecode(trim($url)));
        //是否开启curl扩展
        if(!extension_loaded('curl')){
            die('对不起，请开启curl功能模块!');
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        if( $data != '' && !empty( $data ) ){
            curl_setopt($ch, CURLOPT_POST, 1);
            if (is_array($data)){
                $data   =   http_build_query($data);
            }else{
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('application/x-www-form-urlencode; charset=utf-8',
                    'Content-length:'.strlen($data)
                ));
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}

////测试demo
//$demo = new Demo();
//
////1、查询token
////$res =  $demo->getToken();
////echo $res;die;
//
////2、创建订单
//$res =  $demo->createOrder();
//echo $res;die;