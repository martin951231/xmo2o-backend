<?php

/**
 **三方开锁
 */
class aihorseUnlockApi
{

    public $apiServiceUrl = 'http://123.249.110.109:85/api'; //正式地址
    public $app_key = 'yj_241009'; 
    public $app_secret = '5E88588DDB3FCDF84668761C66BAEDFE';
    public $village_id = 0;   //系统小区id
    
    /**
     * 页面编码
     * Demo constructor.
     * Date: 2019-04-16
     * Time: 下午 5:34
     */
    public function __construct($village_id = 0,$app_key='',$app_secret='')
    {
        $this->village_id = $village_id;
        if($app_key){
            $this->app_key=$app_key;
        }
        if($app_secret){
            $this->app_secret=$app_secret;
        }
    }

    protected function getThirdDataSign($datas = array())
    {
        //签名步骤一：按字典序排序参数
        ksort($datas);
        $buffStr = "";
        foreach ($datas as $kk => $vv) {
              $upKey=strtoupper($kk);
              $buffStr .=  $upKey.'='. $vv.'&';
        }
        $pString = '';
        if (strlen($buffStr) > 0) {
            //签名步骤二：在string后加入KEY
            $buffStr =  $buffStr .'APP_SECRET='.$this->app_secret;
            fdump_api(array('dataSign' => $buffStr), 'aihorseUnlockApi', 1);
            $pString = md5($buffStr);
            //$pString=strtoupper($pString);
        }
        return $pString;
    }

    /***
     * 1.获取小区列表
    */
    public function getUserVillageList($phone='')
    {
        $phone=$phone ? strval($phone):'';
        $postArr=array('app'=>'tp','class'=>'GetUserList','app_key'=>$this->app_key);
        $postArr['phone']=trim($phone);
        $postArr['timestamp']=time();
        $signStr=$this->getThirdDataSign($postArr);
        $postArr['sign']=$signStr;
        $result = $this->curl_request($postArr);
        fdump_api(array('postArr' => $postArr, 'result' => $result), 'aihorseUnlockApi', 1);
        $returnArr=array('data_list'=>array(),'err_code'=>0,'err_msg'=>'');
        if ($result && isset($result['status']) && $result['status']<1) {
            if(isset($result['data']) && $result['data']){
                $returnArr['data_list']= $result['data'];
            }else{
                $err_msg='未获取小区数据出错了！';
                if($result && isset($result['msg']) && $result['msg'] ){
                    $err_msg=$result['msg'];
                }
                $returnArr['err_msg']=$err_msg;
            }
            
        } else {
            $err_msg='获取小区数据出错了！';
            $returnArr['err_code']=1;
            if($result && isset($result['msg']) && $result['msg'] ){
                $err_msg=$result['msg'];
            }
            $returnArr['err_msg']=$err_msg;
        }
        return $returnArr;
    }
    
    /***
     * 2.设置为默认小区
     */
    public function setUserDefaultVillage($phone='',$store_id=0)
    {
        $phone=$phone ? strval($phone):'';
        $postArr=array('app'=>'tp','class'=>'SetDefault','app_key'=>$this->app_key);
        $postArr['phone']=trim($phone);
        $postArr['store_id']=$store_id;
        $postArr['timestamp']=time();
        $signStr=$this->getThirdDataSign($postArr);
        $postArr['sign']=$signStr;
        $result = $this->curl_request($postArr);
        fdump_api(array('postArr' => $postArr, 'result' => $result), 'aihorseUnlockApi', 1);
        $returnArr=array('err_code'=>0,'err_msg'=>'');
        if ($result && isset($result['status']) && $result['status']<1) {
            $returnArr['err_msg']='设置默认小区成功！';
        } else {
            $err_msg='设置默认小区失败了！';
            $returnArr['err_code']=1;
            if($result && isset($result['msg']) && $result['msg'] ){
                $err_msg=$result['msg'];
            }
            $returnArr['err_msg']=$err_msg;
        }
        return $returnArr;
    }

    /***
     * 3.获取开锁二维码
     * 获取默认小区开锁二维码
     */
    public function getDefaultAccessEwmCode($phone='',$site_url='')
    {
        $phone=$phone ? strval($phone):'';
        $postArr=array('app'=>'tp','class'=>'getAccessCode','app_key'=>$this->app_key);
        $postArr['phone']=trim($phone);
        $postArr['timestamp']=time();
        $signStr=$this->getThirdDataSign($postArr);
        $postArr['sign']=$signStr;
        $result = $this->curl_request($postArr);
        fdump_api(array('postArr' => $postArr, 'result' => $result), 'aihorseUnlockApi', 1);
        $returnArr=array('err_code'=>0,'err_msg'=>'','datainfo'=>array('ewm_code'=>'','ewm_code_url'=>'','expired_desc'=>''));
        if ($result && isset($result['status']) && $result['status']<1) {
            if(isset($result['data']) && isset($result['data']['code'])){
                $returnArr['datainfo']['ewm_code']=$result['data']['code'];
                $returnArr['datainfo']['expired_desc']=isset($result['data']) && isset($result['data']['expiredMinutes']) ? $result['data']['expiredMinutes']:'';
                if($returnArr['datainfo']['ewm_code']){
                    $returnArr['datainfo']['ewm_code_url']=$site_url.U('HouseApi/get_own_qrcode',array('qrCon'=>$returnArr['ewm_code']));
                }
            }else{
                $err_msg='没获取到二维码信息！';
                if($result && isset($result['msg']) && $result['msg'] ){
                    $err_msg=$result['msg'];
                }
                $returnArr['err_msg']=$err_msg;
            }
        } else {
            $err_msg='获取开锁二维失败了！';
            $returnArr['err_code']=1;
            if($result &&  isset($result['msg']) && $result['msg'] ){
                $err_msg=$result['msg'];
            }
            $returnArr['err_msg']=$err_msg;
        }
        return $returnArr;
    }
    
    /**
     * CURL请求数据
     * @param $url
     * @param $param
     *
     * @return mixed
     * Date: 2019-04-16
     * Time: 下午 4:34
     */
    public function curl_request( $param = '', $url='',$methd = 'POST', $content_type = 'application/x-www-form-urlencoded')
    {
        $url = $this->apiServiceUrl . $url;
        $headers = array(
            'Content-Type:' . $content_type,
            'User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        );

        //是否开启curl扩展
        if (!extension_loaded('curl')) {
            echo '对不起，请开启curl功能模块!';
            exit();
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($param)) {
            //curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $methd);
            $paramStr = '';
            if (is_array($param)) {
                $paramStr = http_build_query($param);
            } else {
                $paramStr = $param;
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $paramStr);
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $methd);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        $result = curl_exec($ch);
        curl_close($ch);
        $jsonRes = array();
        if ($result) {
            $jsonRes = json_decode($result, true);
        }
        $jsonRes = $jsonRes ? $jsonRes : array();
        fdump_api(array('url' => $url, 'param' => $param, 'methd' => $methd, 'result' => $result, 'jsonRes' => $jsonRes), 'aihorseUnlockApi', 1);
        return $jsonRes;
    }
}