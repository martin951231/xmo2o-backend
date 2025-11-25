<?php
//企业微信

namespace app\community\model\service;

use app\community\model\db\AccessTokenCommonExpires;
use app\community\model\db\HouseContactWayUser;
use app\community\model\db\HouseEnterpriseWxBind;
use app\community\model\db\HouseWorker;
use app\community\model\db\WorkMsgAudit;
use app\community\model\db\WorkMsgAuditInfo;
use app\community\model\service\workweixin\WorkWeiXinNewService;
use app\community\model\service\workweixin\WorkWeiXinRequestService;
use app\community\model\service\workweixin\WorkWeiXinSuiteService;
use file_handle\FileHandle;
use net\Http as Http;
use token\Token;

class EnterpriseWeChatService
{
    public function getWxConfig($from_id,$from_type,$wx_api_type,$timestamp,$nonceStr)
    {
        $sHouse_village = new HouseVillageService();
        $dbQywxService = new QywxService();
        if($from_type == 1){
            $village_info = $sHouse_village->getHouseVillageInfo(['village_id'=>$from_id],'property_id');
            $property_id = $village_info['property_id'];
        }else{
            $from_type = 2;
            $property_id =$from_id;
        }
        fdump_api(['$wx_api_type' => $wx_api_type,'from_type' => $from_type,'$property_id' => $property_id], '$getWxConfig1', true);
//        if ($wx_api_type == 1) {
//            try {
//                $access_token_info = (new WorkWeiXinNewService())->getCgiBinServiceGetCorpToken($property_id, true);
//            }catch (\Exception $e){
//                fdump_api(['line' => $e->getLine(),'$property_id' => $e->getMessage(),'code' => $e->getCode()], '$getWxConfig1', true);
//            }
//            $access_token = isset($access_token_info['access_token']) ? $access_token_info['access_token'] : '';
//        } else {
//            $tokenResult = (new WorkWeiXinSuiteService())->getToken($property_id);
//            if (isset($tokenResult['access_token']) && $tokenResult['access_token']) {
//                $access_token = $tokenResult['access_token'];
//            } elseif($wx_api_type == 1) {
//                $access_token = $dbQywxService->getQywxAccessToken($property_id,'enterprise_wx_provider');//企业的
//            }else{
//                $access_token = $dbQywxService->getQywxAccessToken($property_id, 'content_engine');//应用
//            }
//        }
        try {
            $access_token_info = (new WorkWeiXinNewService())->getCgiBinServiceGetCorpToken($property_id, true);
        }catch (\Exception $e){
            fdump_api(['line' => $e->getLine(),'$property_id' => $e->getMessage(),'code' => $e->getCode()], '$getWxConfig1', true);
        }
        $access_token = isset($access_token_info['access_token']) ? $access_token_info['access_token'] : '';
        fdump_api(['$access_token' => $access_token], '$getWxConfig1', true);
//        if($wx_api_type == 1)
//        {
//            $res = $this->getQyWxTicket($access_token);
//        }else{
//            $res = $this->getQyWxAppTicket($access_token);
//        }
        $res =$this->disposeTicket($access_token,$wx_api_type,$property_id);
        fdump(['1授权登录信息'.__LINE__,$access_token,$wx_api_type,$property_id,$res],'$getWxConfig1',1);
        if($res['errcode'] == 0){
            $jsapi_ticket = $res['jsapi_ticket'];
        }else{
            throw new \Exception($res['errmsg']);
        }

        $db_house_enterprise_wx_bind = new HouseEnterpriseWxBind();
        $data = $db_house_enterprise_wx_bind->getOne(['bind_id'=>$property_id],'corpid, agentid, PlaintextCorpId');
        if (!$timestamp) {
            $timestamp = time();
        }
        if (!$nonceStr) {
            $nonceStr  = rand(100000,999999);
        }
        $content_url = request()->param('url');
        if (!$content_url) {
            $content_url = cfg('site_url') .'/packapp/workweixin/DevelopmentStation.html?from_id='.$from_id.'&from_type='.$from_type;
        }
        $signature_url = 'jsapi_ticket='.$jsapi_ticket.'&noncestr='.$nonceStr.'&timestamp='.$timestamp.'&url='.$content_url;
        //$signature = sha1("jsapi_ticket=$jsapi_ticket&noncestr=$nonceStr&timestamp=$timestamp&url=$content_url");
        $signature = sha1($signature_url);
        $return['appId'] = $data['corpid'];//企业微信的corpID
        $return['noncestr'] = $nonceStr;//生成签名的随机串
        $return['timestamp'] = $timestamp;//生成签名的时间戳
        $return['signature'] = $signature; //签名
        if($wx_api_type == 2){//应用的id
            $serviceQywx = new QywxService();
            $agent_info = $serviceQywx->getAgentByProperty($property_id);
            if($agent_info && $agent_info['agentid']){
                $return['agentid'] =$agent_info['agentid'];
            }else{
                $return['agentid'] = $data['agentid'];
            }
        }
        fdump(['授权登录信息'.__LINE__,$_POST,$res,$signature_url,$return],'$getWxConfig1',1);
        return $return;
    }
    public function disposeTicket($access_token,$type,$property_id)
    {
        $dbAccessTokenCommonExpires = new AccessTokenCommonExpires();
        if($type == 1){
            $type = 'qy_wx_jsapi_ticket_'.$property_id;
            $map[] = ['type','=',$type];
            $map[] = ['access_id','=',$type];
            $expires_info = $dbAccessTokenCommonExpires->getOne($map,'id,access_token,access_token_expire');
            $time = time();
            if($expires_info && intval($expires_info['access_token_expire']) > intval($time + 1800)){
                $jsapi_ticket = $expires_info['access_token'];
            }else{
                $res = $this->getQyWxTicket($access_token);
                if($res['errcode'] == 0){
                    $jsapi_ticket = $res['ticket'];
                    $data_ticket['access_token'] = $jsapi_ticket;
                    $expires_in = $res['expires_in'] ? $res['expires_in'] : 0;
                    $data_ticket['access_token_expire'] = time() + $expires_in;
                    $data_ticket['type'] = $type;
                    $data_ticket['access_id'] = $type;
                    if($expires_info){
                        $dbAccessTokenCommonExpires->saveOne(['id'=>$expires_info['id']],$data_ticket);
                    }else{
                        $dbAccessTokenCommonExpires->addOne($data_ticket);
                    }
                }else{
                    $ret['errcode'] = $res['errcode'];
                    $ret['errmsg'] = $res['errmsg'];
                    return $ret;
                }
            }
            $ret['errcode'] = $res['errcode'];
            $ret['errmsg'] = $res['errmsg'];
            $ret['jsapi_ticket'] = $jsapi_ticket;
            return $ret;
        }else{
            $type = 'qy_wx_jsapi_ticket_agent_config_'.$property_id;
            $map[] = ['type','=',$type];
            $map[] = ['access_id','=',$type];
            $time = time();
            $expires_info = $dbAccessTokenCommonExpires->getOne($map,'id,access_token,access_token_expire');
            if($expires_info && intval($expires_info['access_token_expire']) > intval($time + 1800)){
                $jsapi_ticket = $expires_info['access_token'];
            }else{
                $res = $this->getQyWxAppTicket($access_token);
                if($res['errcode'] == 0){
                    $jsapi_ticket = $res['ticket'];
                    $data_ticket['access_token'] = $jsapi_ticket;
                    $expires_in = $res['expires_in'] ? $res['expires_in'] : 0;
                    $data_ticket['access_token_expire'] = time() + $expires_in;
                    $data_ticket['type'] = $type;
                    $data_ticket['access_id'] = $type;
                    if($expires_info){
                        $dbAccessTokenCommonExpires->saveOne(['id'=>$expires_info['id']],$data_ticket);
                    }else{
                        $dbAccessTokenCommonExpires->addOne($data_ticket);
                    }
                }else{
                    $ret['errcode'] = $res['errcode'];
                    $ret['errmsg'] = $res['errmsg'];
                    return $ret;
                }
            }
            $ret['errcode'] = $res['errcode'];
            $ret['errmsg'] = $res['errmsg'];
            $ret['jsapi_ticket'] = $jsapi_ticket;
            return $ret;
        }
    }
    public function qyloginApi($property_id,$from_type,$code){
        $dbQywxService = new QywxService();
        $dbHouseWorker = new HouseWorker();
        $access_token = $dbQywxService->getQywxAccessToken($property_id,'content_engine');
        $url ="https://qyapi.weixin.qq.com/cgi-bin/miniprogram/jscode2session?access_token=$access_token&js_code=$code&grant_type=authorization_code";
        $res = $this->curlGet($url);
        if($res['errcode'] == 0){
            $user_id = $res['UserId'];
        }else{
            throw new \Exception($res['errmsg']);
        }
        if(!$user_id){
            throw new \Exception('您不是内部工作人员');
        }
        $user_id = $res['userid'];
        $where=array();
        $where[] = ['status','<',4];
        $where[] = ['is_del','<',1];
        $where[] = ['qy_id','=',$user_id];
        $worker_info = $dbHouseWorker->get_one($where,'wid,village_id,property_id,people_type');
        if(!$worker_info){
            throw new \Exception('您不是内部工作人员');
        }
        $data['corpid'] = $res['corpid'];
        $data['userid'] = $res['userid'];
        $data['session_key'] = $res['session_key'];
        $data['from_type'] = $from_type;
        if( $worker_info['people_type']==1){
            $data['from_id'] = $worker_info['village_id'];
        }else{
            $data['from_id'] = $worker_info['property_id'];
        }
        return $data;
    }
    /**
     * Notes:获取企业微信 企业的Ticket
     * @datetime: 2021/3/25 17:06
     */
    public function getQyWxTicket($access_token)
    {
        $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$access_token";
        $res = $this->curlGet($url);
        return $res;
    }

    /**
     * Notes:获取企业微信 应用的Ticket
     * @datetime: 2021/3/25 17:09
     * @param $access_token
     * @return
     */
    public function getQyWxAppTicket($access_token){

        $url = "https://qyapi.weixin.qq.com/cgi-bin/ticket/get?access_token=$access_token&type=agent_config";
        $res = $this->curlGet($url);
        return $res;
    }
    //获取用户信息
    public function getUserInfo($code,$property_id,$from_type='')
    {
        $dbHouseContactWayUser = new HouseContactWayUser();
        $dbHouseWorker = new HouseWorker();
        $sHouse_village = new HouseVillageService();
        $userResult = (new WorkWeiXinSuiteService())->getServiceAuthGetUserInfo3rd($code);
        fdump_api(['$code' => $code, '$userResult' => $userResult], '$userResult');
        if (isset($userResult['userid']) && $userResult['userid']) {
            $user_id = $userResult['userid'];
        } else {
            $res = $this->getAccessInfo($code,$property_id);
            if($res['errcode'] == 0){
                $user_id = $res['UserId'];
            }else{
                throw new \Exception($res['errmsg']);
            }
        }
        if(!$user_id){
            throw new \Exception('您不是内部工作人员');
        }
//        $wheres[] = ['UserID','=',$user_id];
//        $info = $dbHouseContactWayUser->getFind($wheres);
        $where = [];
        $where[] = ['status','<',4];
        $where[] = ['is_del','<',1];
        $where[] = ['property_id','=',$property_id];
        $where[] = ['qy_id|qy_open_userid','=',$user_id];
        $worker_info = $dbHouseWorker->get_one($where,'wid,village_id,property_id,people_type');
        if(!$worker_info){
            throw new \Exception('您不是内部工作人员');
        }
        $ticket = Token::createToken($worker_info['wid']);
        $data['ticket'] = $ticket;
        $data['worker_info'] = $worker_info;
        $base_url = $sHouse_village->base_url;
        if (!$from_type) {
            $from_type = $worker_info['people_type'];
        }
        if($from_type == 1){
            $part_url = '&from_id='.$worker_info['village_id'];
        }else{
            $from_type = 2;
            $part_url = '&from_id='.$worker_info['property_id'];
        }
        fdump_api(['ticket' => $ticket, 'from_type' => $from_type, 'part_url' => $part_url], '$qylogin');
        $back_url = cfg('site_url') . "/packapp/workweixin/DevelopmentStation.html?ticket=$ticket&from_type=$from_type".$part_url.'&from=qylogin';
        fdump_api(['$back_url' => $back_url], '$back_url');
        if($back_url)
        {
            header("Location: $back_url");
            die;
        }
    }
    //用户登录
    public function getUserCode($from_id,$from_type)
    {
        $sHouse_village = new HouseVillageService();
        if($from_type == 1){
            $map[] = ['village_id','=',$from_id];
            $villageInfo = $sHouse_village->getHouseVillageInfo($map,'property_id');
            $from_id = $villageInfo['property_id'];
        }
        $db_house_enterprise_wx_bind = new HouseEnterpriseWxBind();
        $data = $db_house_enterprise_wx_bind->getOne(['bind_id'=>$from_id], 'corpid');
        $content_url = cfg('site_url') . "/v20/public/index.php/community/manage_api.v1.ContentEngine/getUserData?property_id=" . $from_id.'&from_type='.$from_type;
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $data['corpid'] . '&redirect_uri=' . urlencode($content_url) . '&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect';
        if($url)
        {
            header("Location: $url");
            die;
        }
    }

    //获取访问用户身份
    public function getAccessInfo($code,$property_id)
    {
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo';
        $dbQywxService = new QywxService();
        $access_token = $dbQywxService->getQywxAccessToken($property_id,'content_engine');
        $path_url = $url.'?access_token='.$access_token.'&code='.$code;
        if(!$access_token){
            $data['errcode'] = -1;
            $data['errmsg'] = 'access_token异常';
            return $data;
        }
        if(!$code){
            $data['errcode'] = -1;
            $data['errmsg'] = 'code异常';
            return $data;
        }
        $res = $this->curlGet($path_url);
        if($res['errcode'] == 0){
            $data['errcode'] = $res['errcode'];
            $data['OpenId'] = $res['OpenId'];
            $data['DeviceId'] = $res['DeviceId'];
            $data['UserId'] = $res['UserId'];
            $data['external_userid'] = $res['external_userid'];
            return $data;
        }else{
            return $res;
        }
    }
    //上传临时素材（废弃）
    public function qyWxUpload($file,$property_id,$type)
    {
        if(!$file){
            throw new \Exception('请上传有效图片');
        }
        $dbQywxService = new QywxService();
        $access_token = $dbQywxService->getQywxAccessToken($property_id,'content_engine');
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/media/upload?access_token='.$access_token.'&type='.$type;

        $res = $this->curlPost($url,$file,1);
        if($res['errcode'] == 0)
        {
            $data['media_id'] = $res['media_id'];
            $data['created_at'] = $res['created_at'];
            return $data;
        }else{
            return false;
        }
    }
    public function uploadImg($imgUrl,$property_id,$type,$basename=''){
        try {
            $access_token_info = (new WorkWeiXinNewService())->getCgiBinServiceGetCorpToken($property_id, true);
        }catch (\Exception $e){
            fdump_api(['line' => $e->getLine(),'$property_id' => $e->getMessage(),'code' => $e->getCode()], '$uploadImg', true);
        }
        $access_token = isset($access_token_info['access_token']) ? $access_token_info['access_token'] : '';
        $imgUrl = dispose_url($imgUrl);
        if(cfg('static_oss_switch')==1) {
            $file_handle = new FileHandle();
            $file_handle->download($imgUrl);
            $imgUrl = $_SERVER['DOCUMENT_ROOT'].str_replace($_SERVER['REQUEST_SCHEME'] . '://' . cfg('static_oss_access_domain_names'), '', $imgUrl);
        } else if(cfg('static_obs_switch')==1) {
            $file_handle = new FileHandle();
            $file_handle->download($imgUrl);
            $imgUrl = $_SERVER['DOCUMENT_ROOT'].str_replace($_SERVER['REQUEST_SCHEME'] . '://' . cfg('static_obs_access_domain_names'), '', $imgUrl);
        } else if(cfg('static_cos_switch') && cfg('static_cos_region')) {
            $file_handle = new FileHandle();
            $file_handle->download($imgUrl);
            $imgUrl = $_SERVER['DOCUMENT_ROOT'].str_replace($_SERVER['REQUEST_SCHEME'] . '://' . cfg('static_cos_access_domain_names'), '', $imgUrl);
        } else{
            $imgUrl = $_SERVER['DOCUMENT_ROOT'].str_replace(cfg('site_url'),'',$imgUrl);
        }
        if($access_token && $imgUrl){
            $file  = $imgUrl;
            $fileInfo = pathinfo($file);
            if (!$basename && $fileInfo['basename']) {
                $basename = $fileInfo['basename'];
            }
            if ($fileInfo['extension']) {
                $extension = $fileInfo['extension'];
            } else {
                $extension = '';
            }
            if (class_exists('\CURLFile')) {
                $data['media'] = new \CURLFile(realpath($file),$extension,$basename);
            } else {
                $data['media'] = '@'.realpath($file);
            }
            $httpObj = new Http();
            $return = $httpObj->curlQyWxPost('https://qyapi.weixin.qq.com/cgi-bin/media/upload?access_token='.$access_token.'&type='.$type,$data);
            if (!is_array($return)) {
                $return = json_decode($return,true);
            }
            $data['media_id'] = $return['media_id'];
            $data['created_at'] = $return['created_at'];
            fdump_api(['$data' => $data,'$return' => $return], '$uploadImg', true);
            return $data;
        }else{
            return false;
        }
    }
    //企业微信接口 读取成员
    public function getQyWxUser($property_id)
    {
        $dbQywxService = new QywxService();
        $access_token = $dbQywxService->getQywxAccessToken($property_id,'content_engine');
        $data = $this->getPermitUserList($property_id,$access_token);
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/user/get?access_token='.$access_token;
        $user_arr = [];
        foreach ($data['ids'] as $val){
            $url = $url.'&userid='.$val;
            $userInfo =$this->curlGet($url);
            if($userInfo['errcode'] == 0){
                $user_arr[]['userid'] = $val;
                $user_arr[]['name'] = $userInfo['name'];
                $user_arr[]['mobile'] = $userInfo['mobile'];
                $user_arr[]['avatar'] = $userInfo['avatar'];
                $user_arr[]['thumb_avatar'] = $userInfo['thumb_avatar'];
                $user_arr[]['open_userid'] = $userInfo['open_userid'];
            }
        }
        return $user_arr;
    }
    
    public function getMemberList($property_id, $data = []) {
        $whereEnterpriseWxBind = [];
        $whereEnterpriseWxBind[] = ['bind_type','=',0];
        $whereEnterpriseWxBind[] = ['bind_id','=',$property_id];
        $qywxBind = (new HouseEnterpriseWxBind())->getOne($whereEnterpriseWxBind,'corpid');
        $corpid = isset($qywxBind['corpid']) && $qywxBind['corpid'] ? $qywxBind['corpid'] : '';
        $workMsgAuditInfo = (new WorkMsgAudit())->getFind(['corp_id' => $corpid]);
        $id = isset($workMsgAuditInfo['id']) && $workMsgAuditInfo['id'] ? intval($workMsgAuditInfo['id']) : 0;
        if (! $id) {
            return [];
        }
        $where = [];
        $where[] = ['audit_id', '=', $id];
        $where[] = ['action', '=', 'send'];
        $where[] = ['from_type', '=', 1];
        $work_msg_audit_info = (new WorkMsgAuditInfo())->getAll($where);
        if ($work_msg_audit_info && !is_array($work_msg_audit_info)) {
            $work_msg_audit_info = $work_msg_audit_info->toArray();
        }
//        $whereOther = [];
//        $whereOther[] = ['audit_id', '=', $id];
//        $whereOther[] = ['action', '=', 'send'];
//        $whereOther[] = ['from_type', '<>', 1];
//        $whereOther[] = ['to_type', '=', 1];
//        $workOtherList = (new WorkMsgAuditInfo())->getAll($whereOther);
//        if ($workOtherList && !is_array($workOtherList)) {
//            $workOtherList = $workOtherList->toArray();
//        }
//        if (!empty($workOtherList)) {
//            $work_msg_audit_info = array_merge($work_msg_audit_info, $workOtherList);
//        }
        $qyIds = [];
        foreach ($work_msg_audit_info as $item1) {
            $qy_id = isset($item1['from_type']) && intval($item1['from_type']) == 1 ? $item1['from'] : $item1['tolist'];
            if (isset($qyIds[$qy_id])) {
                continue;
            }
            $qyIds[$qy_id] = $qy_id;
        }
        $dbHouseWorker = new HouseWorker();
        $qyIds = array_values($qyIds);
        $whereWork = [];
        $whereWork[] = ['property_id', '=', $property_id];
        $whereWork[] = ['status','<',4];
        $whereWork[] = ['is_del','<',1];
        $whereWork[] = ['qy_id', 'in', $qyIds];
        $workIdInfos = $dbHouseWorker->getColumn($whereWork, 'wid,name as nickname,avatar,phone,gender,qy_id', 'qy_id');
        if ($workIdInfos && !is_array($workIdInfos)) {
            $workIdInfos = $workIdInfos->toArray();
        }
        $user_work = [];
        $allWorks = [];
        foreach ($work_msg_audit_info as $item) {
            $qy_id = isset($item['from_type']) && intval($item['from_type']) == 1 ? $item['from'] : $item['tolist'];
            if (isset($allWorks[$qy_id])) {
                continue;
            }
            if (!$data || empty($data) || (is_array($data) && !in_array($qy_id, $data))) {
                $workInfo = isset($workIdInfos[$qy_id]) ? $workIdInfos[$qy_id] : [];
                if (isset($workInfo['wid']) && $workInfo['wid']) {
                    $user_work[] = $workInfo;
                } else {
                    $user_work[] = [
                        'wid' => 'audit_' . $qy_id,
                        'nickname' => $qy_id,
                        'avatar' => '',
                        'phone' => '',
                        'gender' => 0,
                        'qy_id' => $qy_id,
                    ];
                }
                $allWorks[$qy_id] = 1;
            }
        }
        return $user_work;
    }
    
    public function getPermitUserList($property_id){
        try {
            $data = $this->getQyPermitUserList($property_id);
        } catch (\Exception $e) {
            $data = [];
        }
        $dbHouseWorker = new HouseWorker();
        $list =[];
        !$data && $data = [];
        try {
            $user_work_other = $this->getMemberList($property_id, $data);
        } catch (\Exception $e) {
            fdump_api(['执行错误' => $e->getMessage(), 'property_id' => $property_id, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'errGetPermitUserListLog', true);
        } catch (\Throwable $e) {
            fdump_api(['执行错误' => $e->getMessage(), 'property_id' => $property_id, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'errGetPermitUserListLog', true);
        } catch (\RuntimeException $e) {
            fdump_api(['执行错误' => $e->getMessage(), 'property_id' => $property_id, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'errGetPermitUserListLog', true);
        } catch (\InvalidArgumentException $e) {
            fdump_api(['执行错误' => $e->getMessage(), 'property_id' => $property_id, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'errGetPermitUserListLog', true);
        }
        if(! empty($data)){
            $map=array();
            $map[] = ['status','<',4];
            $map[] = ['is_del','<',1];
            $map[] = ['qy_id','in',$data];
            $user_work = $dbHouseWorker->getAll($map,'wid,name as nickname,avatar,phone,gender,qy_id');
            if ($user_work && !is_array($user_work)) {
                $user_work = $user_work->toArray();
            }
            foreach ($user_work  as &$val){
                if(!$val['avatar']){
                    $val['avatar'] = dispose_url('/v20/public/static/community/qywx/default.png');
                }
            }
            $user_work = array_merge($user_work, $user_work_other);
            $list['user_work'] = $user_work;
            return $list;
        }else{
            $list['user_work'] = $user_work_other;
            return $list;
        }
    }
    //企业微信接口 获取会话内容存档开启成员列表
    public function getQyPermitUserList($property_id,$access_token='')
    {
        if(!$access_token) {
            $dbQywxService = new QywxService();
            $access_token = $dbQywxService->getQywxAccessToken($property_id,'content_engine');
        }
        if (!$access_token) {
            return [];
        }
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/msgaudit/get_permit_user_list?access_token='.$access_token;
        $data = $this->curlPost($url,[],0, 20);
        if(isset($data['errcode']) && $data['errcode'] == 0){
            return $data['ids'];
        }else{
            return [];
        }

    }
    public function getPermitUserInfo($property_id,$userid,$access_token='')
    {
        if(!$access_token) {
            $dbQywxService = new QywxService();
            $access_token = $dbQywxService->getQywxAccessToken($property_id,'content_engine');
        }
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/user/get?access_token='.$access_token.'&userid='.$userid;
        $userInfo =$this->curlGet($url);
        return $userInfo;
    }
    public function getExternalUserInfo($property_id,$external_userid,$access_token='')
    {
        if(!$access_token) {
            $dbQywxService = new QywxService();
            $access_token = $dbQywxService->getQywxAccessToken($property_id);
        }
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/get?access_token='.$access_token.'&external_userid='.$external_userid;
        $userInfo =$this->curlGet($url);
        return $userInfo;
    }
    
    public function getUserOpenid($userId,$property_id = 0,$access_token='') {
        if(!$access_token) {
            $dbQywxService = new QywxService();
            $access_token = $dbQywxService->getQywxAccessToken($property_id);
        }
        $result = (new WorkWeiXinRequestService())->cgiBinUserConvertToOpenid($userId, $access_token);
        return $result;
    }
    /**
     * Notes:type = 1 获取会话内容存档内部群信息 2.获取客户群详情
     * @datetime: 2021/4/7 16:40
     * @param $property_id
     * @param $group_id
     * @param int $type
     * @return array|bool|mixed|string
     */
    public function groupChat($property_id,$group_id,$type=1)
    {
        $dbQywxService = new QywxService();
        if($type == 1){
            $access_token = $dbQywxService->getQywxAccessToken($property_id,'session_file_type');
            $url = 'https://qyapi.weixin.qq.com/cgi-bin/msgaudit/groupchat/get?access_token='.$access_token;
            $data = json_encode(['roomid'=>$group_id]);
            $group_data =$this->curlPost($url,$data);
            if($group_data['errcode'] == 0){
                $group_data['group_type'] = 1;
                return $group_data;
            }else{
                if($group_data['errcode'] ==301059){
                    return $this->groupChat($property_id,$group_id,$type=2);
                }
                return [];
            }
        }
        if($type ==2){
            $access_token = $dbQywxService->getQywxAccessToken($property_id);
            $url = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/groupchat/get?access_token='.$access_token;
            $data = json_encode(['chat_id'=>$group_id]);
            $group_data =$this->curlPost($url,$data);
            if($group_data['errcode'] == 0){
                $group_data['group_type'] = 2;
                return $group_data;
            }else{
                return [];
            }
        }
    }
    /**
     * Notes:curl (post)
     * @datetime: 2021/3/22 11:54
     * @param $url
     * @param $data
     * @param int $type
     * @param int $timeout
     * @return bool|mixed|string
     */
    public function curlPost($url,$data,$type=0,$timeout=45){
        $ch = curl_init();
        if($type == 0){
            $headers[] = "Content-type: application/x-www-form-urlencoded;charset=utf-8";
        }else{
            $headers[] = "Content-type:multipart/form-data;application/octet-stream";
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.80 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch ,CURLOPT_TIMEOUT ,$timeout);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result, true);
        return $result;
    }

    /**
     * Notes:curl (get)
     * @datetime: 2021/3/22 11:54
     * @param $url
     * @return mixed
     */
    function curlGet($url){
        $ch = curl_init();
        $headers[] = "Content-type:application/json;charset=utf-8;";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $temp = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($temp, true);
        return $result;
    }

    /**
     * Notes:对应物业生成 公私钥
     * @param $property_id
     * @return array
     * @throws \think\Exception
     * @author: wanzy
     * @date_time: 2021/4/1 11:01
     */
    public function create2048($property_id) {
        if (empty($property_id)) {
            return false;
        }
        $saveDir  = "/upload/house/qyweixin/$property_id/" .date('Ymd') . "/";
        $savePath = request()->server('DOCUMENT_ROOT') . $saveDir;
        if (!is_dir($savePath)) {
            mkdir($savePath, 0777, true);
        }
        $now_time = time();
        $privateFileName = 'private_' . $now_time . '.pem';
        $privateFilePath = $savePath . $privateFileName;
        $publicFileName  = 'public_' . $now_time . '.pem';
        $publicFilePath  = $savePath . $publicFileName;

        if (!function_exists("shell_exec")) {
            throw new \think\Exception('请联系管理人员开启shell_exec');
        }
        shell_exec("openssl genrsa -out ".$privateFilePath." 2048");
        shell_exec("openssl rsa -in ".$privateFilePath." -pubout -out ".$publicFilePath);
        $priKey = file_get_contents($privateFilePath);
        $pubKey = file_get_contents($publicFilePath);
        $data = [
            'private_key'  => $priKey,
            'private_path' => $privateFilePath,
            'public_key'   => $pubKey,
            'public_path'  => $publicFilePath,
        ];
        return $data;
    }

    /**
     * 分配在职成员的客户
     * @param $property_id
     * @param $param
     * @return array|bool|mixed|string
     */
    public function transferCustomer($property_id,$param){
        $dbQywxService = new QywxService();
        $access_token = $dbQywxService->getQywxAccessToken($property_id);
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/groupchat/get?access_token='.$access_token;
        $data = json_encode($param);
        $group_data =$this->curlPost($url,$data);
        if($group_data['errcode'] == 0){
            return $group_data;
        }else{
            return [];
        }
    }
}