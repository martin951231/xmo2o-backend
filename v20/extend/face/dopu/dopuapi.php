<?php
/**
 *======================================================
 * Created by : PhpStorm
 * User: zhanghan
 * Date: 2021/11/30
 * Time: 11:16
 *======================================================
 */

namespace  face\dopu;

use app\community\model\db\AccessTokenCommonExpires;
use app\community\model\db\HouseVillageThirdConfig;

class dopuapi
{
    protected $isTest = true; //是否是测试环境，默认是 true 测试环境,false 生产环境
    protected $baseUrlTest = 'http://192.168.1.98:8083/guyee-acs'; // 测试环境
    protected $baseUrl = 'http://192.168.1.98:8083/guyee-acs'; // 生产环境
   // protected $baseUrlTest = 'http://192.168.0.187:8083/guyee-acs'; // 测试环境
   // protected $baseUrl = 'http://192.168.0.187:8083/guyee-acs'; // 生产环境
    protected $clientId = '';   // $clientId
    protected $clientSecret = '';  // $clientSecret
    protected $prefix = 'dopu_village_';    // 前缀
    protected $token_type = '_Bearer';    // 后缀
    protected $village_id;  // 小区ID
    protected $config;  // 配置信息

    public function __construct($village_id,$isTest = true)
    {
        if (empty($village_id)) {
            return api_output_error(1003,'缺少关键参数');
        }
        $house_village_third_config = new HouseVillageThirdConfig();
        $where = [];
        $where[] = ['village_id','=',$village_id];
        $where[] = ['type','=','dopu_cloudintercom'];
        // 查询小区绑定云对讲平台参数
        $configInfo = $house_village_third_config->getCloudIntercomConfig($where,'clientId,clientSecret');
        $this->village_id = $village_id;
        $this->clientId = trim($configInfo['clientId']);
        $this->clientSecret = trim($configInfo['clientSecret']);
        $this->isTest = $isTest;
        // 是否是测试环境 获取不同环境地址
        if($this->isTest){
            $this->baseUrl = $this->baseUrlTest;
        }

        $type = $this->prefix.$village_id.$this->token_type;
        $accessId = $this->clientId.'_'.$this->clientSecret;
        // 获取数据库token
        $db_token = new AccessTokenCommonExpires();
        $config = $db_token->getOne(array(array('type','=',$type),array('access_id','=',$accessId)));
        // 获取token url
        $url = $this->baseUrl."/oauth/token?grant_type=client_credentials&client_id=".$this->clientId."&client_secret=".$this->clientSecret."&scope=SCOPE";
        if(!$config || empty($config) || $config->isEmpty()){
            // 初始化时 获取token并保存
            $data = $this->getAccessToken($url,'client_credentials');
            $this->config = $data;
        }else{
            // token过期
            if(time() > $config['access_token_expire']){
                // 存在 refresh_token 并且没有过期
                if(!empty($config['refresh_token']) && time() < $config['refresh_token_expire']){
                    // refresh_tokn 刷新获取token
                    $refresh_url = $this->baseUrl."/oauth/token?grant_type=refresh_token&client_id=".$this->clientId."&client_secret=".$this->clientSecret."&refresh_token=".$this->config['refresh_token'];
                    $data = $this->getAccessToken($refresh_url,'refresh_token');
                    $this->config = $data;
                }else{
                    // 获取token
                    $data = $this->getAccessToken($url,'client_credentials');
                    $this->config = $data;
                }
            }else{
                $this->config = $config->toArray();
            }
        }
    }

    /**
     * 获取token
     */
    public function getAccessToken($url,$grant_type='client_credentials'){
        $postArr=array('client_id'=>$this->clientId,'client_secret'=>$this->clientSecret);
        $postArr['grant_type']=$grant_type;
        if($grant_type=='client_credentials'){
            $postArr['scope']='SCOPE';
        }else if($grant_type=='refresh_token'){
            $postArr['refresh_token']=$this->config['refresh_token'];
        }
        $headers=array('Content-type:application/x-www-form-urlencoded;charset=utf-8','Accept-Charset: utf-8');
        $result = http_request($url,'POST',$postArr,$headers);
        $response = json_decode($result[1],true);
        if($result[0] == 200){
            // 存储token
            $token_data = [
                'access_token' => $response['access_token'],
                'access_token_expire' => (int)(time()+$response['expires_in']),
                'type' => $this->prefix.$this->village_id.$this->token_type,
                'access_id' => $this->clientId.'_'.$this->clientSecret,
            ];
            // 刷新token凭证 refresh_token
            if(isset($response['refresh_token']) && !empty($response['refresh_token'])){
                $token_data['refresh_token'] = $response['refresh_token'];
            }
            // 刷新token凭证到期时间 refresh_token
            if(isset($response['refresh_token_expire']) && !empty($response['refresh_token_expire'])){
                $token_data['refresh_token_expire'] = (int)(time()+$response['refresh_token_expire']);
            }
            $type = $this->prefix.$this->village_id.$this->token_type;
            $accessId = $this->clientId.'_'.$this->clientSecret;
            // 获取数据库token
            $db_token = new AccessTokenCommonExpires();
            $tokenInfo = $db_token->getOne(array(array('type','=',$type),array('access_id','=',$accessId)));
            if($tokenInfo && !$tokenInfo->isEmpty()){
                $tokenInfo = $tokenInfo->toArray();
                // 更新
                $db_token->saveOne(array(array('id','=',$tokenInfo['id'])),$token_data);
            }else{
                // 新增
                $db_token->addOne($token_data);
            }

            return $token_data;
        }else{
            return array();
        }
    }

    /**
     * 审核接口上传
     */
    public function auditFeedback($param){
        $heard = [
            'Content-Type: application/json; charset=utf-8'
        ];
        $data = json_encode($param);
        $url = $this->baseUrl.'/api/community/ryresult?access_token='.$this->config['access_token'];
        $result = http_request($url,'POST',$data,$heard);
        //fdump_api(['result' => $result,'param' => $param],'dopu/auditFeedback',1);
        return $result;
    }

    /**
     * 权限接口信息上传
     * @param $param
     * @return array|string
     */
    public function gateauth($param){
        $heard = [
            'Content-Type: application/json; charset=utf-8'
        ];
        $data = json_encode($param);
        $url = $this->baseUrl.'/api/community/gateauth?access_token='.$this->config['access_token'];
        $result = http_request($url,'POST',$data,$heard);
        //fdump_api(['result' => $result,'param' => $param],'dopu/gateauth',1);
        return $result;
    }

    /**
     * 远程开门
     * @param $param
     * @return array|string
     */
    public function openDoor($param){
        $heard = [
            'Content-Type: application/json; charset=utf-8'
        ];
        $data = json_encode($param);
        $url = $this->baseUrl.'/api/community/opdoor?access_token='.$this->config['access_token'];
        $result = http_request($url,'POST',$data,$heard);
        //fdump_api(['result' => $result,'param' => $param],'dopu/openDoor',1);
        return $result;
    }
}