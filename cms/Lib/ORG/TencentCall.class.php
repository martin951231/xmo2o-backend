<?php
/*
 * 腾讯云直播
 *
 * 	文档地址 https://cloud.tencent.com/document/product/267/30148
 */
include_once 'tencent_sdk/TCloudAutoLoader.php';
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Trtc\V20190722\TrtcClient;
use TencentCloud\Trtc\V20190722\Models\DismissRoomRequest;

include_once 'tencentIM/TLSSigAPIv2.php';
class TencentCall{
    private $domain = 'trtc.tencentcloudapi.com';
    private $secretid = null;
    private $secretkey = null;
    private $sdkappid = null;
    private $key = null;
    public function __construct($config){
        if($config){
            foreach ($config as $k=>$v){
               $this->set($k,$v);
            }
        }
        if(empty($this->domain) || empty($this->key) || empty($this->sdkappid) || empty($this->secretid) || empty($this->secretkey)){
            $output = [
                'status' => 1003,
                'msg' 	 => '请在系统后台配置A185人脸识别门禁参数',
                'data' 	 => []
            ];
            $http_json = json_encode($output,JSON_UNESCAPED_UNICODE);
            echo $http_json;exit();
        }
    }

    private function set($key, $val)
    {
        $key = strtolower($key);
        if (array_key_exists($key, get_class_vars(get_class($this)))) {
            $this->setOption($key, $val);
        }
    }

    private function setOption($key, $val){
        $this->$key = $val;
        return $val;
    }

    public function getUserSig($account=''){
        if(empty($account)){
            $account = trim(D("Config")->get_config_by_name("tencent_administrator")['value']);
        }
        $api = new \Tencent\TLSSigAPIv2($this->sdkappid, $this->key);
        $sig = $api->genSig($account);
        return ['key'=>$this->sdkappid,'sig'=>$sig];
    }

    /**
     * 解散房间
     * @author liukezhu
     * @date 2021/4/28
     */
    public function DismissRoom($RoomId){
        try {
            $cred = new Credential($this->secretid, $this->secretkey);
            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint($this->domain);
            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new TrtcClient($cred, "ap-guangzhou", $clientProfile);
            $req = new DismissRoomRequest();
            $params = array(
                "SdkAppId" => (int)$this->sdkappid,
                "RoomId" => (int)$RoomId
            );
            $req->fromJsonString(json_encode($params));
            $resp = $client->DismissRoom($req);
            return true;
        }
        catch(TencentCloudSDKException $e) {
//            echo $e;
            fdump_api([$this->sdkappid,$RoomId,$e],'a185/dismiss_room',1);
            return false;
        }
    }



}