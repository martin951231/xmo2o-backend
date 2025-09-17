<?php


namespace app\community\model\service;
use app\community\model\db\HouseEnterpriseWxBind;
use app\community\model\db\AccessTokenCommonExpires;
use app\community\model\db\HouseServicesHousekeeper;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseWorker;
use app\community\model\db\VillageQywxAgent;
use app\common\model\db\Config as ConfigModel;

use app\community\model\service\workweixin\WorkWeiXinNewService;
use app\community\model\service\workweixin\WorkWeiXinRequestService;
use file_handle\FileHandle;
use net\Http as Http;
use error_msg\GetErrorMsg;

class QywxService
{
    private $db_house_enterprise_wx_bind = '';
    private $db_access_token_common_expires = '';
    private $qy_type = 'contact_way';
    private $base_url = 'https://qyapi.weixin.qq.com/cgi-bin';

    public function __construct()
    {
        $this->db_house_enterprise_wx_bind = new HouseEnterpriseWxBind();
        $this->db_access_token_common_expires = new AccessTokenCommonExpires();
    }

    /**
     * 获取服务商凭证.
     */
    public function cgiBinServiceGetProviderToken($corpid, $provider_secret, $now_time=0) {
        $where_provider_token = [
            'type' => 'service_provider_secret',
            'access_id' => $corpid . '_provider_secret_' . $provider_secret,
        ];
        $provider_token_data = $this->db_access_token_common_expires->getOne($where_provider_token);
        if (!$now_time) {
            $now_time = time();
        }
        if ($provider_token_data && intval($provider_token_data['access_token_expire']) > intval($now_time + 1800)) {
            $provider_access_token = $provider_token_data['access_token'];
        } else {
            $data = array(
                'corpid' => $corpid,
                'provider_secret' => $provider_secret,
            );
            $url = $this->base_url . '/service/get_provider_token';
            $provider_access_msg = Http::curlQyWxPost($url, json_encode($data));
            fdump_api(['获取服务商凭证>>>'.__LINE__, '$url' => $url, '$data' => $data, '$provider_access_msg' => $provider_access_msg],'qiYeWeiXin/cgiBinServiceGetProviderTokenLog',1);
            if (!empty($provider_access_msg['errcode'])) {
                fdump_api([
                    '获取服务商凭证错误》》' => __LINE__, 'corpid' => $corpid,
                    'provider_secret' => $provider_secret, 'now_time' => $now_time
                ],'qiYeWeiXin/get_suite_token_err_log',true);
                return $provider_access_msg;
            }
            $provider_access_token = $provider_access_msg['provider_access_token'];
            $set = array(
                'access_token' => $provider_access_token,
                'access_token_expire' => $provider_access_msg['expires_in'] + $now_time,
            );
            if ($provider_token_data) {
                $this->db_access_token_common_expires->saveOne($where_provider_token, $set);
            } else {
                $set['type'] = 'service_provider_secret';
                $set['access_id'] = $corpid.'_provider_secret_'.$provider_secret;
                $this->db_access_token_common_expires->addOne($set);
            }
        }
        return array('errcode' => 0, 'access_token' =>$provider_access_token);
    }

    /**
     * 仅限第三方服务商，转换已获授权企业的corpid.
     */
    public function cgiBinServiceCorpidToOpencorpid($corpid, $provider_access_token = '') {
        if (! $provider_access_token) {
            $provider_secret = cfg('service_provider_secret');
            $corpid = cfg('enterprise_wx_corpid');
            $provider_access_token_arr = $this->cgiBinServiceGetProviderToken($corpid, $provider_secret);
            $provider_access_token = isset($provider_access_token_arr['access_token']) && $provider_access_token_arr['access_token'] ? $provider_access_token_arr['access_token'] : '';
        }
        $url = $this->base_url . "/service/corpid_to_opencorpid?provider_access_token={$provider_access_token}";
        $data = array(
            'corpid' => $corpid,
        );
        $provider_access_msg = Http::curlQyWxPost($url, json_encode($data));
        fdump_api(['转换已获授权企业的corpid>>>'.__LINE__, '$url' => $url, '$data' => $data, '$provider_access_msg' => $provider_access_msg],'qiYeWeiXin/cgiBinServiceCorpidToOpencorpidLog',1);
        $open_corpid = isset($provider_access_msg['open_corpid']) && $provider_access_msg['open_corpid'] ? $provider_access_msg['open_corpid'] : '';
        return $open_corpid;
    }
    
    /**
     * 获取企业微信access_token
     * @author lijie
     * @date_time 2021/03/19
     * @param int $property_id
     * @param string $type
     * @return bool|mixed
     */
    public function getQywxAccessToken($property_id=0,$type='contact_way',$param=[]) {
        if(!$property_id) {
            return false;
        }
        $whereEnterpriseWxBind = [];
        $whereEnterpriseWxBind[] = ['bind_type','=',0];
        $whereEnterpriseWxBind[] = ['bind_id','=',$property_id];
        $qywxBind = $this->db_house_enterprise_wx_bind->getOne($whereEnterpriseWxBind,'corpid,wx_provider_suiteid,permanent_code,contact_way_secret,PlaintextCorpId');
        if (!empty($qywxBind)) {
            $qywxBind = $qywxBind->toArray();
        } else {
            fdump_api(['msg' => '获取企业微信access_token错误 对应对象未绑定企业微信》》', 'line' => __LINE__, 'property_id' => $property_id, 'type' => $type, 'qywxBind' => $qywxBind],'qiYeWeiXin/get_qy_token_err_log',1);
            return false;
        }
        $corpid = $qywxBind['corpid'];
        $now_time = intval(time());
        $service_config = new ConfigService();
        if($type == 'enterprise_wx_provider'){
            $enterprise_wx_corpid_info = $service_config->get_config('enterprise_wx_corpid','value');
            $enterprise_wx_provider_suiteid_info = $service_config->get_config('enterprise_wx_provider_suiteid','value');
            $enterprise_wx_provider_secret_info = $service_config->get_config('enterprise_wx_provider_secret','value');
            $wx_provider_corpid = $enterprise_wx_corpid_info['value'];
            $wx_provider_suiteid = $enterprise_wx_provider_suiteid_info['value'];
            $enterprise_wx_provider_secret = $enterprise_wx_provider_secret_info['value'];
            $qy_type = 'enterprise_wx_provider';
            $where_access_token = [
                'type' => $qy_type,
                'access_id' =>$corpid.'_suiteid_'.$qywxBind['wx_provider_suiteid'],
            ];
            $access_token_info = $this->db_access_token_common_expires->getOne($where_access_token);
            $now = time();
            // 过期时间  向前1800秒 提前刷新
            if ($access_token_info && intval($access_token_info['access_token_expire']) > intval($now + 1800)) {
                return $access_token_info['access_token'];
            }
            // 获取 获取第三方应用凭证
            $where_suite_token = [
                'type' =>'suite_access_token',
                'access_id' =>$wx_provider_corpid.'_suiteid_'.$wx_provider_corpid,
            ];
            $suite_token_data = $this->db_access_token_common_expires->getOne($where_suite_token);
            $now = time();
            if ($suite_token_data && intval($suite_token_data['access_token_expire']) > intval($now + 1800)) {
                $suite_access_token = $suite_token_data['access_token'];
            } else {
                $suite_access_token_url = $this->base_url . '/service/get_suite_token';
                $enterprise_wx_suite_ticket_info = $service_config->get_config('enterprise_wx_suite_ticket','value');
                $enterprise_wx_suite_ticket = $enterprise_wx_suite_ticket_info['value'];
                $data = [
                    'suite_id' => $wx_provider_suiteid,
                    'suite_secret' => $enterprise_wx_provider_secret,
                    'suite_ticket' => $enterprise_wx_suite_ticket
                ];
                $suite_token = Http::curlQyWxPost($suite_access_token_url,json_encode($data));
                if (!empty($suite_token['errcode'])) {
                    fdump_api('获取第三方应用凭证错误》》'.__LINE__,'qyweixin/get_suite_token_err_log',true);
                    fdump_api($suite_access_token_url,'qyweixin/get_suite_token_err_log',true);
                    fdump_api($property_id,'qyweixin/get_suite_token_err_log',true);
                    fdump_api($type,'qyweixin/get_suite_token_err_log',true);
                    fdump_api(json_encode($data),'qyweixin/get_suite_token_err_log',true);
                    fdump_api($suite_token,'qyweixin/get_suite_token_err_log',true);
                    return false;
                }
                fdump_api('获取suite_access_token>>>'.__LINE__,'qyweixin/suite_token_log',1);
                fdump_api($suite_access_token_url,'qyweixin/suite_token_log',1);
                fdump_api($data,'qyweixin/suite_token_log',1);
                fdump_api($suite_token,'qyweixin/suite_token_log',1);
                $suite_access_token = $suite_token['suite_access_token'];
                if ($suite_access_token) {
                    $set = array(
                        'access_token' => $suite_access_token,
                        'access_token_expire' => $suite_token['expires_in'] + $now_time,
                    );
                    if ($suite_token_data) {
                        $this->db_access_token_common_expires->saveOne($where_suite_token,$set);
                    } else {
                        $set['type'] = 'suite_access_token';
                        $set['access_id'] = $corpid.'_suiteid_'.$qywxBind['wx_provider_suiteid'];
                        $this->db_access_token_common_expires->addOne($set);
                    }
                }
            }
            if ($suite_access_token) {
                // 获取企业凭证
                $get_corp_token_url = $this->base_url . '/service/get_corp_token?suite_access_token='.$suite_access_token;
//                $open_corpid = $this->cgiBinServiceCorpidToOpencorpid($corpid);
                $data = [
                    'auth_corpid' => $corpid,
                    'permanent_code' => $qywxBind['permanent_code']
                ];
                $corp_token_data = Http::curlQyWxPost($get_corp_token_url,json_encode($data));
                fdump_api('获取企业凭证>>>'.__LINE__,'qyweixin/corp_token_data_log',1);
                fdump_api($get_corp_token_url,'qyweixin/corp_token_data_log',1);
                fdump_api($data,'qyweixin/corp_token_data_log',1);
                fdump_api($corp_token_data,'qyweixin/corp_token_data_log',1);
                if (empty($corp_token_data['errcode'])) {
                    $set = array(
                        'access_token' => $corp_token_data['access_token'],
                        'expires_in' => $corp_token_data['expires_in'] + $now_time,
                    );
                    $this->db_house_enterprise_wx_bind->updateThis($whereEnterpriseWxBind,$set);
                    // 公用表格中记录下
                    $set = array(
                        'access_token' => $corp_token_data['access_token'],
                        'access_token_expire' => $corp_token_data['expires_in'] + $now_time,
                    );
                    if ($access_token_info) {
                        $this->db_access_token_common_expires->saveOne($where_access_token,$set);
                    } else {
                        $set['type'] = $qy_type;
                        $set['access_id'] = $corpid.'_suiteid_'.$qywxBind['wx_provider_suiteid'];
                        $this->db_access_token_common_expires->addOne($set);
                    }
                    return $corp_token_data['access_token'];
                } else {
                    fdump_api('获取获取企业凭证错误》》'.__LINE__,'qyweixin/get_corp_token_err_log',true);
                    fdump_api($property_id,'qyweixin/get_corp_token_err_log',true);
                    fdump_api($type,'qyweixin/get_corp_token_err_log',true);
                    fdump_api($qywxBind,'qyweixin/get_corp_token_err_log',true);
                    fdump_api($data,'qyweixin/get_corp_token_err_log',true);
                    fdump_api($corp_token_data,'qyweixin/get_corp_token_err_log',true);
                    return false;
                }
            } else{
                fdump_api('获取第三方应用凭证错误》》'.__LINE__,'qyweixin/get_suite_token_err_log',true);
                fdump_api($property_id,'qyweixin/get_suite_token_err_log',true);
                fdump_api($type,'qyweixin/get_suite_token_err_log',true);
                fdump_api($qywxBind,'qyweixin/get_suite_token_err_log',true);
                fdump_api($data,'qyweixin/get_suite_token_err_log',true);
                fdump_api($suite_token_data,'qyweixin/get_suite_token_err_log',true);
                return false;
            }

        }
        elseif ($type == 'contact_way'){
            if (isset($qywxBind['contact_way_secret']) && $qywxBind['contact_way_secret']) {
                $corpid = trim($qywxBind['corpid']);
                $corpsecret = trim($qywxBind['contact_way_secret']);
            } else {
                $enterprise_wx_corpid_info = $service_config->get_config('enterprise_wx_corpid','value');
                $corpid = $enterprise_wx_corpid_info['value'];
                $corpsecret = cfg('contact_way_secret');
            }
            $qy_type = $this->qy_type;
        }
        elseif ($type == 'content_engine') {
            $qy_type = 'content_engine';
            isset($qywxBind['PlaintextCorpId']) && $qywxBind['PlaintextCorpId'] && $corpid = $qywxBind['PlaintextCorpId'];
            if (isset($param['secret']) && $param['secret']) {
                $corpsecret = $param['secret'];
            } else {
                $where_qywx_agent = [];
                $dbVillageQywxAgent = new VillageQywxAgent();
                $where_qywx_agent[] = ['property_id', '=', $property_id];
                $where_qywx_agent[] = ['type', '=', 0];
                $where_qywx_agent[] = ['agentid', '<>', ''];
                $where_qywx_agent[] = ['secret', '<>', ''];
                $where_qywx_agent[] = ['is_close', '=', 0];
                $agent_info = $dbVillageQywxAgent->getOne($where_qywx_agent);
                if (!empty($agent_info)) {
                    $agent_info = $agent_info->toArray();
                    $corpsecret = $agent_info['secret'];
                } else {
                    return false;
                }
            }
        }
        $where_access_token = [
            'type' =>$qy_type,
            'access_id' =>$corpid.'_'.$corpsecret,
        ];
        $access_token_info = $this->db_access_token_common_expires->getOne($where_access_token);
        if ($access_token_info && !is_array($access_token_info)) {
            $access_token_info = $access_token_info->toArray();
        }
        $now = time();
        // 过期时间  向前1800秒 提前刷新
        if ($access_token_info && isset($access_token_info['access_token_expire']) && intval($access_token_info['access_token_expire']) > intval($now + 1800)) {
            return $access_token_info['access_token'];
        } else {
            if (!$corpid || !$corpsecret) {
                return [
                    'errmsg' => '请检查对应物业配置，缺少相关配置信息'
                ];
            }
            // 如果不存在或者过期  重新获取
            $url = $this->base_url."/gettoken?corpid={$corpid}&corpsecret={$corpsecret}";
            $result = Http::curlGet($url);
            if (!is_array($result)) {
                $result = json_decode($result,true);
            }
            if (empty($result['errcode'])) {
                $set = array(
                    'access_token' => $result['access_token'],
                    'access_token_expire' => $result['expires_in'] + $now,
                );
                if ($access_token_info) {
                    $this->db_access_token_common_expires->saveOne($where_access_token,$set);
                } else {
                    $set['type'] = $qy_type;
                    $set['access_id'] = $corpid.'_'.$corpsecret;
                    $this->db_access_token_common_expires->addOne($set);
                }
                return $result['access_token'];
            }
            //return array('errcode' => $result['errcode'], 'errmsg' => $result['errmsg']);
            return false;
        }

    }


    /**
     * Notes: 获取 客户联系「联系我」 二维码相关信息
     * @param $property_id
     * @param $work_arr
     * @param array $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/3/24 15:36
     */
    public function contactWay($property_id, $work_arr, $param=[]) {
        fdump_api(['msg' => '企业微信同步', 'line' => __LINE__, 'property_id' => $property_id, 'work_arr' => $work_arr, 'param' => $param], 'qiYeWeiXin/contactWayLog', 1);
        if (empty($work_arr) || !$property_id) {
            return [
                'config_id' => '',
                'qr_code' => '',
                'errcode' => '-1003',
                'errmsg' =>'参数缺失',
            ];
        }
        $dbHouseWorker = new HouseWorker();
        $where_work = [];
        $where_work[] = ['wid','in',$work_arr];
        $where_work[] = ['status','=',1];
        $where_work[] = ['qy_id','<>',''];
        $where_work[] = ['qy_status','=',1];
        $work_field = 'wid,name,qy_id';
        $work_list = $dbHouseWorker->getWorkList($where_work,'', $work_field);
        if (empty($work_list)) {
            return [
                'config_id' => '',
                'qr_code' => '',
                'errcode' => '-1002',
                'errmsg' =>'工作人员信息获取失败',
            ];
        }
        $access_token_info = (new WorkWeiXinNewService())->getCgiBinServiceGetCorpToken($property_id, true);
//        $access_token = $this->getQywxAccessToken($property_id);
        $access_token = isset($access_token_info['access_token']) ? $access_token_info['access_token'] : '';
        if (!$access_token) {
            return [
                'config_id' => '',
                'qr_code' => '',
                'errcode' => '-1001',
                'errmsg' =>'token获取失败',
            ];
        }
        fdump_api(['msg' => '企业微信同步access_token', 'line' => __LINE__, 'property_id' => $property_id, 'access_token' => $access_token], 'qiYeWeiXin/contactWayLog', 1);

        $work_list = $work_list->toArray();
        $user = [];
        foreach ($work_list as $val) {
            $user[] = $val['qy_id'];
        }
        $openUserids = (new WorkWeiXinNewService())->getCgiBinBatchUseridToOpenuserid($property_id, $user, $where_work);
        fdump_api(['msg' => '企微工作人员userid转换', 'line' => __LINE__, 'property_id' => $property_id, 'user' => $user, 'where_work' => $where_work, 'openUserids' => $openUserids], 'qiYeWeiXin/contactWayLog', 1);
        if (! empty($openUserids)) {
            $user = $openUserids;
        }
        if (isset($param['remark']) && $param['remark']) {
            $remark = $param['remark'];
        } else {
            $remark = '';
        }
        if (isset($param['state']) && $param['state']) {
            $state = $param['state'];
        } else {
            $state = '';
        }
        if (isset($param['skip_verify']) && !$param['skip_verify']) {
            $skip_verify = false;
        } else {
            $skip_verify = true;
        }

        // type 联系方式类型,1-单人, 2-多人
        // scene 场景，1-在小程序中联系，2-通过二维码联系
        // remark 联系方式的备注信息，用于助记，不超过30个字符
        // skip_verify 外部客户添加时是否无需验证，默认为true
        // state 企业自定义的state参数，用于区分不同的添加渠道，在调用“获取外部联系人详情”时会返回该参数值，不超过30个字符
        // user 使用该联系方式的用户userID列表，在type为1时为必填，且只能有一个
        // is_temp 是否临时会话模式，true表示使用临时会话模式，默认为false
        $type = 2;
        if (count($user) == 1) {
            $type = 1;
        }
        $data = [
            'type' => $type,
            'scene' => 2,
            'remark' => $remark,
            'skip_verify' => $skip_verify,
            'state' => $state,
            'user' => $user,
            'is_temp'=> false
        ];
        if (isset($param['type']) && $param['type']=='village_qywx_channel_code') {
            $contact_way_json = md5(json_encode($data,true));
        } else {
            $contact_way_json = '';
        }
        if (isset($param['config_id']) && $param['config_id']) {
            unset($data['type']);
            unset($data['scene']);
            $data['config_id'] = $param['config_id'];
            $contact_way_url = $this->base_url.'/externalcontact/update_contact_way?access_token='.$access_token;
        } else {
            $contact_way_url = $this->base_url.'/externalcontact/add_contact_way?access_token='.$access_token;
        }
        $contact_way_data = Http::curlQyWxPost($contact_way_url,json_encode($data));
        fdump_api([
            'title' => '客户联系「联系我」管理', 'contact_way_url' => $contact_way_url,
            'data' => $data, 'contact_way_data' => $contact_way_data,
        ],'qiYeWeiXin/contactWayLog', 1);
        if (!is_array($contact_way_data)) {
            $contact_way_data = json_decode($contact_way_data,true);
        }
        if (isset($contact_way_data['errcode']) && intval($contact_way_data['errcode']) == 84074) {
            $followResult = (new WorkWeiXinRequestService())->cgiBinExternalContactGetFollowUserList($access_token);
            fdump_api([
                'title' => '获取配置了客户联系功能的成员列表', 'access_token' => $access_token, 'followResult' => $followResult,
            ],'qiYeWeiXin/contactWayLog', 1);
        }
        
        if (!$contact_way_data['errcode']) {
            if (isset($contact_way_data['config_id']) && $contact_way_data['config_id']) {
                $config_id = $contact_way_data['config_id'];
                $qr_code = $contact_way_data['qr_code'];
                $qr_info = [
                    'config_id' => $config_id,
                    'qr_code' => $qr_code,
                    'errcode' => 0,
                    'errmsg' => '',
                    'contact_way_json' => $contact_way_json
                ];
            } elseif (isset($data['config_id']) && $data['config_id'] && isset($data['code_url']) && $data['code_url']) {
                $config_id = $data['config_id'];
                $qr_code = $data['code_url'];
                $qr_info = [
                    'config_id' => $config_id,
                    'qr_code' => $qr_code,
                    'errcode' => 0,
                    'errmsg' => '',
                    'contact_way_json' => $contact_way_json
                ];
            } elseif (isset($data['config_id']) && $data['config_id']) {
                $config_id = $data['config_id'];
                $qr_info = [
                    'config_id' => $config_id,
                    'qr_code' => '',
                    'errcode' => 0,
                    'errmsg' => '',
                    'contact_way_json' => $contact_way_json
                ];
            } else {
                $qr_info = [
                    'config_id' => '',
                    'qr_code' => '',
                    'errcode' => 0,
                    'errmsg' => '',
                    'contact_way_json' => $contact_way_json
                ];
            }
            return $qr_info;
        } else {
            $errmsg = GetErrorMsg::qiyeErrorCode($contact_way_data['errcode']);
            if (!$errmsg) {
                $errmsg = isset($contact_way_data['errmsg']) ? $contact_way_data['errmsg'] :'';
            }
            $qr_info = [
                'config_id' => '',
                'qr_code' => '',
                'errcode' => $contact_way_data['errcode'],
                'errmsg' => $errmsg,
                'real_errmsg' => isset($contact_way_data['real_errmsg']) ? $contact_way_data['real_errmsg'] :'',
            ];
            return $qr_info;
        }
    }

    // 暂时不行 不允许服务商操作非服务商应用
    public function getAgentDetail($property_id, $agentid, $secret='') {
        try {
            $access_token_info = (new WorkWeiXinNewService())->getCgiBinServiceGetCorpToken($property_id, true);
        }catch (\Exception $e){
            fdump_api(['line' => $e->getLine(),'$property_id' => $e->getMessage(),'code' => $e->getCode()], '$getAgentDetail', true);
        }
        $access_token = isset($access_token_info['access_token']) ? $access_token_info['access_token'] : '';
        if ($access_token) {
            $agent_get_url = $this->base_url.'/agent/get?access_token='.$access_token.'&agentid='.$agentid;
            $agent_get_data = Http::curlGet($agent_get_url);
            if (!is_array($agent_get_data)) {
                $agent_get_data = json_decode($agent_get_data,true);
            }
            return $agent_get_data;
        } else {
            return $access_token;
        }
    }

    /**
     * Notes: 获取线上企业微信应用 并予以记录
     * @param int $property_id 物业id
     * @param int $is_save 存储对象
     * 1 全部存储并实时变更 2 单独存储更新自建应用  3 单独存储更新三方应用  4 单独存储更新系统应用
     * @return array|mixed
     * @throws \think\Exception
     * @author: wanzy
     * @date_time: 2021/4/2 16:45
     */
    public function getQywxAgentList($property_id, $is_save=1) {
        $type = 'enterprise_wx_provider';
        $access_token = $this->getQywxAccessToken($property_id,$type);
        if ($access_token) {
            $agent_list_url = $this->base_url.'/agent/list?access_token='.$access_token;
            $agent_list_data = Http::curlGet($agent_list_url);
            if (!is_array($agent_list_data)) {
                $agent_list_data = json_decode($agent_list_data,true);
            }
            if (!$agent_list_data['errcode']) {
                // 系统应用
                $systemApply = [];
                // 三方应用
                $threeApply = [];
                // 自建应用
                $buildApply = [];
                $agentlist = $agent_list_data['agentlist'];
                // 记录可能写入数据
                $add_data = [];
                $save_data = [];
                //如果需要记录 查询下当前现有应用
                $dbVillageQywxAgent = new VillageQywxAgent();
                $agent_list = $dbVillageQywxAgent->getSome([],'id,agentid');
                $agent_arr = [];
                if (!empty($agent_list)) {
                    $agent_list = $agent_list->toArray();
                    foreach ($agent_list as $v) {
                        $agent_arr[$v['agentid']] = $v['id'];
                    }
                }
                $now_time = time();

                foreach ($agentlist as $val) {
                    $msg = [];
                    if (isset($val['type']) && 2==$val['type']) {
                        $systemApply[] = $val;
                        // 4 单独存更新储系统应用
                        if (4==$is_save) {
                            if (isset($val['agentid']) && $val['agentid']) {
                                $msg['agentid'] = $val['agentid'];
                            }
                            if (isset($val['name']) && $val['name']) {
                                $msg['name'] = $val['name'];
                            }
                            if (isset($val['square_logo_url']) && $val['square_logo_url']) {
                                $msg['square_logo_url'] = $val['square_logo_url'];
                            }
                            if (isset($val['description']) && $val['description']) {
                                $msg['description'] = $val['description'];
                            } else {
                                $msg['description'] = '';
                            }
                            if (isset($val['groupid']) && $val['groupid']) {
                                $msg['groupid'] = $val['groupid'];
                            } else {
                                $msg['groupid'] = 0;
                            }
                            if (isset($val['is_close'])) {
                                $msg['is_close'] = $val['is_close'] ? 1 : 0;
                            }
                            if (isset($val['suiteid']) && $val['suiteid']) {
                                $msg['suiteid'] = $val['suiteid'];
                            }
                            if (isset($val['type'])) {
                                $msg['type'] = $val['type'];
                            }
                            if (isset($val['order'])) {
                                $msg['order'] = $val['order'];
                            }
                            if (!empty($agent_arr) && isset($val['agentid']) && $val['agentid'] && $agent_arr[$val['agentid']]) {
                                $msg['id'] = $agent_arr[$val['agentid']];
                                $msg['last_time'] = $now_time;
                                $save_data[] = $msg;
                            } else {
                                $msg['bind_time'] = $now_time;
                                $add_data[] = $msg;
                            }
                        }
                    } elseif (isset($val['type']) && 1==$val['type']) {
                        $threeApply[] = $val;
                        // 3 单独存储更新三方应用
                        if (3==$is_save) {
                            if (isset($val['agentid']) && $val['agentid']) {
                                $msg['agentid'] = $val['agentid'];
                            }
                            if (isset($val['name']) && $val['name']) {
                                $msg['name'] = $val['name'];
                            }
                            if (isset($val['square_logo_url']) && $val['square_logo_url']) {
                                $msg['square_logo_url'] = $val['square_logo_url'];
                            }
                            if (isset($val['description']) && $val['description']) {
                                $msg['description'] = $val['description'];
                            } else {
                                $msg['description'] = '';
                            }
                            if (isset($val['groupid']) && $val['groupid']) {
                                $msg['groupid'] = $val['groupid'];
                            } else {
                                $msg['groupid'] = 0;
                            }
                            if (isset($val['is_close'])) {
                                $msg['is_close'] = $val['is_close'] ? 1 : 0;
                            }
                            if (isset($val['suiteid']) && $val['suiteid']) {
                                $msg['suiteid'] = $val['suiteid'];
                            }
                            if (isset($val['type'])) {
                                $msg['type'] = $val['type'];
                            }
                            if (isset($val['order'])) {
                                $msg['order'] = $val['order'];
                            }
                            if (!empty($agent_arr) && isset($val['agentid']) && $val['agentid'] && $agent_arr[$val['agentid']]) {
                                $msg['id'] = $agent_arr[$val['agentid']];
                                $msg['last_time'] = $now_time;
                                $save_data[] = $msg;
                            } else {
                                $msg['bind_time'] = $now_time;
                                $add_data[] = $msg;
                            }
                        }
                    } elseif (isset($val['type']) && 0==$val['type']) {
                        $buildApply[] = $val;
                        // 2 单独存储更新自建应用
                        if (2==$is_save) {
                            if (isset($val['agentid']) && $val['agentid']) {
                                $msg['agentid'] = $val['agentid'];
                            }
                            if (isset($val['name']) && $val['name']) {
                                $msg['name'] = $val['name'];
                            }
                            if (isset($val['square_logo_url']) && $val['square_logo_url']) {
                                $msg['square_logo_url'] = $val['square_logo_url'];
                            }
                            if (isset($val['description']) && $val['description']) {
                                $msg['description'] = $val['description'];
                            } else {
                                $msg['description'] = '';
                            }
                            if (isset($val['groupid']) && $val['groupid']) {
                                $msg['groupid'] = $val['groupid'];
                            } else {
                                $msg['groupid'] = 0;
                            }
                            if (isset($val['is_close'])) {
                                $msg['is_close'] = $val['is_close'] ? 1 : 0;
                            }
                            if (isset($val['suiteid']) && $val['suiteid']) {
                                $msg['suiteid'] = $val['suiteid'];
                            }
                            if (isset($val['type'])) {
                                $msg['type'] = $val['type'];
                            }
                            if (isset($val['order'])) {
                                $msg['order'] = $val['order'];
                            }
                            if (!empty($agent_arr) && isset($val['agentid']) && $val['agentid'] && $agent_arr[$val['agentid']]) {
                                $msg['id'] = $agent_arr[$val['agentid']];
                                $msg['last_time'] = $now_time;
                                $save_data[] = $msg;
                            } else {
                                $msg['bind_time'] = $now_time;
                                $add_data[] = $msg;
                            }
                        }
                    }
                    //  1 全部存储并实时变更
                    if (1==$is_save) {
                        if (isset($val['agentid']) && $val['agentid']) {
                            $msg['agentid'] = $val['agentid'];
                        }
                        if (isset($val['name']) && $val['name']) {
                            $msg['name'] = $val['name'];
                        }
                        if (isset($val['square_logo_url']) && $val['square_logo_url']) {
                            $msg['square_logo_url'] = $val['square_logo_url'];
                        }
                        if (isset($val['description']) && $val['description']) {
                            $msg['description'] = $val['description'];
                        } else {
                            $msg['description'] = '';
                        }
                        if (isset($val['groupid']) && $val['groupid']) {
                            $msg['groupid'] = $val['groupid'];
                        } else {
                            $msg['groupid'] = 0;
                        }
                        if (isset($val['is_close'])) {
                            $msg['is_close'] = $val['is_close'] ? 1 : 0;
                        }
                        if (isset($val['suiteid']) && $val['suiteid']) {
                            $msg['suiteid'] = $val['suiteid'];
                        }
                        if (isset($val['type'])) {
                            $msg['type'] = $val['type'];
                        }
                        if (isset($val['type'])) {
                            $msg['type'] = $val['type'];
                        }
                        if (isset($val['order'])) {
                            $msg['order'] = $val['order'];
                        }
                        if (!empty($agent_arr) && isset($val['agentid']) && $val['agentid'] && $agent_arr[$val['agentid']]) {
                            $msg['id'] = $agent_arr[$val['agentid']];
                            $msg['last_time'] = $now_time;
                            $save_data[] = $msg;
                        } else {
                            $msg['bind_time'] = $now_time;
                            $add_data[] = $msg;
                        }
                    }
                }
                $data = [
                    'systemApply' => $systemApply,
                    'threeApply' => $threeApply,
                    'buildApply' => $buildApply,
                ];
                if (!empty($add_data)) {
                    foreach ($add_data as $item1) {
                        $dbVillageQywxAgent->add($item1);
                    }
                };
                if (!empty($save_data)) {
                    foreach ($save_data as $item2) {
                        $dbVillageQywxAgent->dataUpdate($item2);
                    }
                }
                return $data;
            } else {
                fdump_api('请求应用获取失败>>>'.__LINE__,'qyweixin/getAgentListErrLog',1);
                fdump_api($property_id,'qyweixin/getAgentListErrLog',1);
                fdump_api($agent_list_url,'qyweixin/getAgentListErrLog',1);
                fdump_api($agent_list_data,'qyweixin/getAgentListErrLog',1);
                throw new \think\Exception($agent_list_data['errmsg']);
            }
        } else {
            throw new \think\Exception('获取失败');
        }
    }

    /**
     * Notes:条件获取企业应用信息
     * @param $where
     * @return array
     * @author: wanzy
     * @date_time: 2021/4/7 17:49
     */
    public function getAgentInfo($where) {
        $dbVillageQywxAgent = new VillageQywxAgent();
        $agentInfo = $dbVillageQywxAgent->getOne($where);
        if (!empty($agentInfo)) {
            $agentInfo = $agentInfo->toArray();
        } else {
            $agentInfo = [];
        }
        return $agentInfo;
    }

    /**
     * Notes: 获取当前物业绑定正常的应用
     * @param $property_id
     * @return array
     * @author: wanzy
     * @date_time: 2021/4/7 17:31
     */
    public function getAgentByProperty($property_id) {
        $dbVillageQywxAgent = new VillageQywxAgent();
        $where = [];
        $where[] = ['property_id','=',$property_id];
        $where[] = ['type', '=', 1];
        $where[] = ['agentid', '<>', ''];
        $where[] = ['secret', '<>', ''];
        $where[] = ['is_close', '=', 0];
        $agent_list = $dbVillageQywxAgent->getOne($where);
        if (!empty($agent_list)) {
            $agent_list = $agent_list->toArray();
        } else {
            $agent_list = [];
        }
        return $agent_list;
    }

    /**
     * Notes: 获取企业微信应用记录
     * @author: wanzy
     * @date_time: 2021/4/7 17:50
     */
    public function getAgentList($where,$type,$param_id) {
        $dbVillageQywxAgent = new VillageQywxAgent();
        $agent_list = $dbVillageQywxAgent->getSome($where,true,'property_id DESC,order DESC, agentid ASC');
        if (!empty($agent_list)) {
            $agent_list = $agent_list->toArray();
            foreach ($agent_list as &$item) {
                if (2==$type && $item['property_id'] == $param_id) {
                    $item['is_choose'] = true;
                } elseif (1==$type && $item['village_id'] == $param_id) {
                    $item['is_choose'] = true;
                } else {
                    $item['is_choose'] = false;
                }
                if (isset($item['secret']) && $item['secret']) {
                    $item['is_secret'] = true;
                    unset($item['secret']);
                } else {
                    $item['is_secret'] = false;
                }
            }
        } else {
            $agent_list = [];
        }
        return $agent_list;
    }
    
    public function getThirdAgentList($property_id) {
        $data = $this->getEnterpriseWxBind($property_id);
        $wx_provider_suiteid = isset($data['wx_provider_suiteid']) && $data['wx_provider_suiteid'] ? $data['wx_provider_suiteid'] : '';
        $auth_info = isset($data['auth_info']) && $data['auth_info'] ? unserialize($data['auth_info']) : [];
        $agent = isset($auth_info['agent']) && $auth_info['agent'] ? $auth_info['agent'] : [];
        $dbVillageQywxAgent = new VillageQywxAgent();
        $nowTime = time();
        $count = count($agent);
        foreach ($agent as $item) {
            $agentid = isset($item['agentid']) && $item['agentid'] ? trim($item['agentid']) : '';
            if (!$agentid) {
                continue;
            }
            $whereSuiteAgent = [
                'agentid' => $agentid,
                'type' => 1,
                'suiteid' => $wx_provider_suiteid
            ];
            $agentInfo = $dbVillageQywxAgent->getOne($whereSuiteAgent, 'id');
            $name = isset($item['name']) && trim($item['name']) ? trim($item['name']) : '';
            $square_logo_url = isset($item['square_logo_url']) && trim($item['square_logo_url']) ? trim($item['square_logo_url']) : '';
            $agentData = [
                'agentid' => $agentid,
                'type' => 1,
                'suiteid' => $wx_provider_suiteid,
                'last_time' => $nowTime,
            ];
            $name && $agentData['name'] = $name;
            $square_logo_url && $agentData['square_logo_url'] = $square_logo_url;
            $count && $agentData['property_id'] = $property_id;
            if (isset($agentInfo['id']) && $agentInfo['id']) {
                $dbVillageQywxAgent->updateThis(['id' => $agentInfo['id']], $agentData);
            } else {
                $dbVillageQywxAgent->add($agentData);
            }
        }
        $type = 1;
        $where = [];
        $where[] = ['property_id','=',$property_id];
        $where[] = ['type','=',$type];
        $agent_list = $dbVillageQywxAgent->getSome($where, true, 'property_id DESC,order DESC, agentid ASC');
        if ($agent_list && !is_array($agent_list)) {
            $agent_list = $agent_list->toArray();
        }
        if (!empty($agent_list)) {
            foreach ($agent_list as &$item) {
                if (1 == $type && $item['property_id'] == $property_id) {
                    $item['is_choose'] = true;
                } else {
                    $item['is_choose'] = false;
                }
                $item['is_secret'] = true;
            }
        } else {
            $agent_list = [];
        }
        return $agent_list;
    }

    /**
     * 获取企业微信信息
     * @param int $property_id
     * @param bool $field
     * @return array|bool|\think\Model|null
     */
    public function getEnterpriseWxBind($property_id=0,$field=true)
    {
        if(!$property_id) {
            return false;
        }
        $whereEnterpriseWxBind = [];
        $whereEnterpriseWxBind[] = ['bind_type','=',0];
        $whereEnterpriseWxBind[] = ['bind_id','=',$property_id];
        $data = $this->db_house_enterprise_wx_bind->getOne($whereEnterpriseWxBind,$field);
        return $data;
    }

    /**
     * 服务商获取楼栋管家【联系我】的二维码.
     */
    public function contactWayHousekeeper($housekeeper_id, $room_id=0, $is_replace = false) {
        fdump_api([
            'line' => __LINE__,'msg' => '服务商获取楼栋管家【联系我】的二维码', 'housekeeper_id' => $housekeeper_id, 'room_id' => $room_id, 'is_replace' => $is_replace,
        ],'qiYeWeiXin/v20ContactWayHousekeeperLog');
        $dbHouseServicesHousekeeper = new HouseServicesHousekeeper();
        $where_housekeeper = [];
        $where_housekeeper[] = ['housekeeper_id', '=', $housekeeper_id];
        $housekeeper_info = $dbHouseServicesHousekeeper->getOne($where_housekeeper);
        if ($housekeeper_info && ! is_array($housekeeper_info)) {
            $housekeeper_info = $housekeeper_info->toArray();
        }
        fdump_api([
            'line' => __LINE__,'msg' => '楼栋管家【联系我】的二维码', 'housekeeper_info' => $housekeeper_info, 'is_replace' => $is_replace,
        ],'qiYeWeiXin/v20ContactWayHousekeeperLog',1);
        $property_id = $housekeeper_info['property_id'] ? $housekeeper_info['property_id'] : 0;
        $village_id = $housekeeper_info['village_id'] ? $housekeeper_info['village_id'] : 0;
        if (empty($housekeeper_info) || ! $property_id) {
            fdump_api([
                '缺少参数：'.__LINE__,
                'housekeeper_id' => $housekeeper_id, 'room_id' => $room_id, 'is_replace' => $is_replace,
            ],'qiYeWeiXin/v20ContactWayHousekeeperErrLog',1);
            return [
                'config_id' => '', 'qr_code' => '', 'errcode' => '-1001', 'errmsg' =>'缺少参数',
            ];
        }
        $access_token_info = (new WorkWeiXinNewService())->getCgiBinServiceGetCorpToken($property_id, true);
        $access_token = isset($access_token_info['access_token']) ? $access_token_info['access_token'] : '';
        if ('42001' == $access_token_info['code'] && ! $is_replace) {
            $suite_access_token = isset($access_token_info['suite_access_token']) ? $access_token_info['suite_access_token'] : '';
            $access_token_info = (new WorkWeiXinNewService())->getCgiBinServiceGetCorpToken($property_id, true, $suite_access_token, true);
            if (isset($access_token_info['access_token'])) {
                $access_token = $access_token_info['access_token'];
            }
        }
        fdump_api([
            'line' => __LINE__,'msg' => 'access_token', 'access_token_info' => $access_token_info, 'access_token' => $access_token,
        ],'qiYeWeiXin/v20ContactWayHousekeeperLog',1);
        if (!$access_token) {
            fdump_api([
                'token获取失败：'.__LINE__,
                'property_id' => $property_id, 'access_token_info' => $access_token_info,
            ],'qiYeWeiXin/v20ContactWayHousekeeperErrLog',1);
            return [
                'config_id' => '', 'qr_code' => '', 'errcode' => '-1001', 'errmsg' =>'token获取失败',
            ];
        }
        $work_arr = explode(',',$housekeeper_info['work_arr']);
        $dbHouseWorker = new HouseWorker();
        $where_work = [];
        $where_work[] = ['wid','in',$work_arr];
        $where_work[] = ['status','=',1];
        $where_work[] = ['qy_id','<>',''];
        $where_work[] = ['qy_status','=',1];
        $work_field = 'wid,name,qy_id';
        $work_list = $dbHouseWorker->getWorkList($where_work,'', $work_field);
        if ($work_list && ! is_array($work_list)) {
            $work_list = $work_list->toArray();
        }
        fdump_api([
            'line' => __LINE__,'msg' => 'work_list', 'work_list' => $work_list
        ],'qiYeWeiXin/v20ContactWayHousekeeperLog',1);
        if (empty($work_list)) {
            fdump_api([
                '工作人员信息获取失败：'.__LINE__,
                'where_work' => $where_work, 'work_list' => $work_list,
            ],'qiYeWeiXin/v20ContactWayHousekeeperErrLog',1);
            return [
                'config_id' => '', 'qr_code' => '', 'errcode' => '-1002', 'errmsg' =>'工作人员信息获取失败',
            ];
        }
        $user = [];
        foreach ($work_list as $val) {
            $user[] = $val['qy_id'];
        }
        fdump_api([
            'line' => __LINE__,'msg' => 'user', 'user' => $user
        ],'qiYeWeiXin/v20ContactWayHousekeeperLog',1);
        $openUserids = (new WorkWeiXinNewService())->getCgiBinBatchUseridToOpenuserid($property_id, $user, $where_work);
        if (! empty($openUserids)) {
            $user = $openUserids;
        }
        fdump_api([
            'line' => __LINE__,'msg' => 'user', 'user' => $user
        ],'qiYeWeiXin/v20ContactWayHousekeeperLog',1);
        if ($housekeeper_info['floor_id']>0) {
            $remark = '单元管家';
            $state = "housekeeper#".strval($housekeeper_info['housekeeper_id']).'|floor_id#'.strval($housekeeper_info['floor_id']);
        } elseif ($housekeeper_info['single_id']>0) {
            $remark = '楼栋管家';
            $state = "housekeeper#".strval($housekeeper_info['housekeeper_id']).'|single_id#'.strval($housekeeper_info['single_id']);
        } else {
            $remark = '物业管家';
            $state = "housekeeper#".strval($housekeeper_info['housekeeper_id']).'|village_id#'.strval($housekeeper_info['village_id']);
        }
        if ($room_id) {
            $state = "housekeeper#".strval($housekeeper_info['housekeeper_id']).'|room_id#'.strval($room_id);
        }
        // type 联系方式类型,1-单人, 2-多人
        // scene 场景，1-在小程序中联系，2-通过二维码联系
        // remark 联系方式的备注信息，用于助记，不超过30个字符
        // skip_verify 外部客户添加时是否无需验证，默认为true
        // state 企业自定义的state参数，用于区分不同的添加渠道，在调用“获取外部联系人详情”时会返回该参数值，不超过30个字符
        // user 使用该联系方式的用户userID列表，在type为1时为必填，且只能有一个
        $type = 2;
        if (count($user) == 1) {
            $type = 1;
        }
        $data = [
            'type' => $type,
            'scene' => 2,
            'remark' => $remark,
            'skip_verify' => true,
            'state' => $state,
            'user' => $user,
            'is_temp'=> false
        ];
        $contact_way_json = md5(json_encode($data,true));
        fdump_api([
            '创建或更新二维码contact_way_json：'.__LINE__,
            'data' => $data, 'contact_way_json' => $contact_way_json, 'housekeeper_info' => $housekeeper_info, 'access_token' => $access_token,
        ],'qiYeWeiXin/v20ContactWayHousekeeperLog',1);
//        if ($housekeeper_info['qr_code'] && $housekeeper_info['contact_way_json'] && $contact_way_json == $housekeeper_info['contact_way_json']) {
//            $qr_info = [
//                'config_id' => $housekeeper_info['config_id'],
//                'qr_code' => $housekeeper_info['qr_code'].'?.png',
//            ];
//            return $qr_info;
//        }
        if (isset($housekeeper_info['config_id']) && $housekeeper_info['config_id']) {
            unset($data['type']);
            unset($data['scene']);
            $data['config_id'] = $housekeeper_info['config_id'];
            $contact_way_url = $this->base_url.'/externalcontact/update_contact_way?access_token='.$access_token;
        } else {
            $contact_way_url = $this->base_url.'/externalcontact/add_contact_way?access_token='.$access_token;
        }
        $contact_way_data = Http::curlQyWxPost($contact_way_url,json_encode($data));
        fdump_api([
            'title' => '客户联系「联系我」管理', 'contact_way_url' => $contact_way_url,
            'data' => $data, 'contact_way_data' => $contact_way_data,
        ],'qiYeWeiXin/v20ContactWayHousekeeperLog', 1);
        if (!is_array($contact_way_data)) {
            $contact_way_data = json_decode($contact_way_data,true);
        }
        if (!$contact_way_data['errcode']) {
            if (isset($contact_way_data['config_id']) && $contact_way_data['config_id']) {
                $config_id = $contact_way_data['config_id'];
                $qr_code = $contact_way_data['qr_code'];
                $qr_info = [
                    'config_id' => $config_id,
                    'qr_code' => $qr_code.'?.png',
                    'errcode' => 0,
                    'errmsg' => '',
                    'contact_way_json' => $contact_way_json
                ];
                $set = array(
                    'contact_way_json' => $contact_way_json,
                    'config_id' => $contact_way_data['config_id'],
                    'qr_code' => $contact_way_data['qr_code'],
                    'contact_way_time' => time(),
                    'contact_way_reason' => ''
                );
            } elseif (isset($data['config_id']) && $data['config_id'] && isset($data['code_url']) && $data['code_url']) {
                $config_id = $data['config_id'];
                $qr_code = $data['code_url'];
                $qr_info = [
                    'config_id' => $config_id,
                    'qr_code' => $qr_code.'?.png',
                    'errcode' => 0,
                    'errmsg' => '',
                    'contact_way_json' => $contact_way_json
                ];
                $set = array(
                    'contact_way_json' => $contact_way_json,
                    'contact_way_time' => time(),
                );
            } elseif (isset($data['config_id']) && $data['config_id']) {
                $config_id = $data['config_id'];
                $qr_info = [
                    'config_id' => $config_id,
                    'qr_code' => isset($housekeeper_info['qr_code']) && $housekeeper_info['qr_code'] ? $housekeeper_info['qr_code'].'?.png' : '',
                    'errcode' => 0,
                    'errmsg' => '',
                    'contact_way_json' => $contact_way_json
                ];
                $set = array(
                    'contact_way_reason' => serialize($contact_way_data),
                );
            } else {
                $qr_info = [
                    'config_id' => '',
                    'qr_code' => '',
                    'errcode' => 0,
                    'errmsg' => '',
                    'contact_way_json' => $contact_way_json
                ];
                $set = array(
                    'contact_way_reason' => serialize($contact_way_data),
                );
            }
            $where_housekeeper = [];
            $where_housekeeper[] = ['housekeeper_id', '=', $housekeeper_id];
            $dbHouseServicesHousekeeper->updateThis($where_housekeeper, $set);
            fdump_api([
                'title' => '更新记录', 'where_housekeeper' => $where_housekeeper, 'set' => $set,
            ],'qiYeWeiXin/v20ContactWayHousekeeperLog', 1);
            return $qr_info;
        } else {
            $errmsg = GetErrorMsg::qiyeErrorCode($contact_way_data['errcode']);
            if (!$errmsg) {
                $errmsg = isset($contact_way_data['errmsg']) ? $contact_way_data['errmsg'] :'';
            }
            fdump_api([
                '获取失败：'.__LINE__,
                'errmsg' => $errmsg, 'contact_way_data' => $contact_way_data,
            ],'qiYeWeiXin/v20ContactWayHousekeeperErrLog',1);
            $qr_info = [
                'config_id' => '',
                'qr_code' => '',
                'errcode' => $contact_way_data['errcode'],
                'errmsg' => $errmsg,
                'real_errmsg' => isset($contact_way_data['real_errmsg']) ? $contact_way_data['real_errmsg'] :'',
            ];
            return $qr_info;
        }
    }

    /**
     * 服务商获取客户详情.
     */
    public function serviceCgiBinExternalContactGet($external_userid, $property_id, $village_id, $is_replace = false) {
        if (! $external_userid || ! $property_id) {
            fdump_api([
                '缺少参数：'.__LINE__,
                'external_userid' => $external_userid, 'property_id' => $property_id, 'village_id' => $village_id,
            ],'qyweixin/serviceCgiBinExternalcontactGetErrLog',1);
            return false;
        }
        $access_token_info = (new WorkWeiXinNewService())->getCgiBinServiceGetCorpToken($property_id, true);
        $access_token = isset($access_token_info['access_token']) ? $access_token_info['access_token'] : '';
        if ('42001' == $access_token_info['code'] && ! $is_replace) {
            $suite_access_token = isset($access_token_info['suite_access_token']) ? $access_token_info['suite_access_token'] : '';
            $access_token_info = (new WorkWeiXinNewService())->getCgiBinServiceGetCorpToken($property_id, true, $suite_access_token, true);
            if (isset($access_token_info['access_token'])) {
                $access_token = $access_token_info['access_token'];
            }
        }
        $url = $this->base_url.'/externalcontact/get?access_token='.$access_token."&external_userid={$external_userid}";
        $data = Http::curlGet($url);
        if (! is_array($data)) {
            $data = json_decode($data,true);
        }
        if (!$data['errcode']) {
            $external_contact = $data['external_contact'];
            return $external_contact;
        } else {
            fdump_api([
                '获取失败：'.__LINE__, 'external_userid' => $external_userid, 'property_id' => $property_id,
                'village_id' => $village_id,'url' => $url,'data' => $data,
            ],'qyweixin/serviceCgiBinExternalcontactGetErrLog',1);
            return false;
        }
    }

    public function getFontUrl() {
        return app()->getRootPath() . '../static/fonts/apple_lihei_bold.otf';
    }

    /**
     * 获取楼栋管家预览图.
     */
    public function servicesImgPreview($params) {
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        // 模板类型 0 无需模板 1系统模板 2上传模板
        $template_type = isset($params['template_type']) ? intval($params['template_type']) : 0;
        // 模板链接
        $template_url = isset($params['template_url']) ? strval($params['template_url']) : '';
        // 企业微信群二维码链接
        $qy_qrcode = isset($params['qy_qrcode']) ? strval($params['qy_qrcode']) : '';
        if (!$qy_qrcode) {
            throw new \Exception("请上传企业微信群二维码!");
        }
        if (2 == $template_type && ! $template_url) {
            throw new \Exception("请上传对应模板!");
        }
        if (0 == $template_type) {
            return [
                'error' => 0,
                'data' => ['file_path' => '', 'file' => '']
            ];
        }
        $site_url = cfg('site_url');
        $fontUrl = $this->getFontUrl();
        if (1 === $template_type) {
            $villageInfo = (new HouseVillage())->getOne($village_id, 'village_name');
            $village_name = isset($villageInfo['village_name']) && $villageInfo['village_name'] ? $villageInfo['village_name'] : '';
            if (mb_strlen($village_name)<=10) {
                $background = $site_url . '/tpl/House/default/static/images/qyWeixin/qyweixin1.png';//海报最底层得背景
                $village_name = '- '.$village_name.' -';
            } elseif (mb_strlen($village_name)<=16) {
                $background = $site_url . '/tpl/House/default/static/images/qyWeixin/qyweixin2.png';//海报最底层得背景
                $village_name = '- '.$village_name.' -';
            } else {
                $background = $site_url . '/tpl/House/default/static/images/qyWeixin/qyweixin3.png';//海报最底层得背景
                $village_name = '- '.$village_name.' -';
            }
            $bbox = imagettfbbox(18, 0, $fontUrl, $village_name);
            $qy_qrcode_width = 240;
        }
        if (2 === $template_type) {
            $background = $template_url;//海报最底层得背景
            $qy_qrcode_width = 100;
        }

        //背景方法
        $backgroundInfo = getimagesize($background);
        $backgroundFun = 'imagecreatefrom' . image_type_to_extension($backgroundInfo[2], false);
        $background = $backgroundFun($background);
        $backgroundWidth = imagesx($background);  //背景宽度
        $backgroundHeight = imagesy($background);  //背景高度
        $imageRes = imageCreatetruecolor($backgroundWidth, $backgroundHeight);
        imagecopyresampled($imageRes, $background, 0, 0, 0, 0, imagesx($background), imagesy($background), imagesx($background), imagesy($background));
        $qy_qrcode = thumb($qy_qrcode, $qy_qrcode_width, $qy_qrcode_width);

        //填充背景
        $info = getimagesize($qy_qrcode);
        $function = 'imagecreatefrom' . image_type_to_extension($info[2], false);
        $res = $function($qy_qrcode);
        // 位置
        if (1 == $template_type) {
            $qr_position = array(
                'left' => (($backgroundWidth - $qy_qrcode_width) / 2), 'top' => 442, 'right' => 0,
                'bottom' => 0, 'width' => $qy_qrcode_width, 'height' => $qy_qrcode_width, 'opacity' => 100
            );
            $text = array(
                'village_name' => array(
                    'text' => $village_name, 'left' => ($backgroundWidth / 2) - ($bbox[2] / 2),
                    'top' => 340, 'right' => 0, 'bottom' => 0, 'fontSize' => 18, 'fontColor' => '49,37,214',
                )
            );
        } else {
            $qr_position = array(
                'left' => 20, 'top' => $backgroundHeight - 120, 'right' => 0,
                'bottom' => 0, 'width' => 100, 'height' => 100, 'opacity' => 100
            );
            $text = [];
        }
        $resWidth = $qy_qrcode_width;
        $resHeight = $qy_qrcode_width;

        $canvas = imagecreatetruecolor($qr_position['width'], $qr_position['height']);

        //关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
        imagecopyresampled($canvas, $res, 0, 0, 0, 0, $qr_position['width'], $qr_position['height'], $resWidth, $resHeight);

        //放置图像
        imagecopymerge($imageRes,$canvas, $qr_position['left'],$qr_position['top'],$qr_position['right'],$qr_position['bottom'],$qr_position['width'],$qr_position['height'],$qr_position['opacity']);//左，上，右，下，宽度，高度，透明度

        if ($text) {
            foreach ($text as $val) {
                $fontWidth = intval($val['fontSize']);//获取文字宽度
                $val['fontPath'] = $fontUrl;
                list($R, $G, $B) = explode(',', $val['fontColor']);
                $fontColor = imagecolorallocate($imageRes, $R, $G, $B);
                $val['left'] = $val['left'] < 0 ? $backgroundWidth - abs($val['left']) : $val['left'];
                $val['top'] = $val['top'] < 0 ? $backgroundHeight - abs($val['top']) : $val['top'];
                $angle = isset($val['angle']) ? intval($val['angle']) : 0;
                imagettftext($imageRes, $val['fontSize'], $angle, $val['left'], $val['top'], $fontColor, $val['fontPath'], $val['text']);
            }
        }
        $path = app()->getRootPath() . '../upload/qyWeixinImg';
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $file = '/upload/qyWeixinImg/services_look_img_' . $village_id . '_' . $template_type . '_' . time() . '.jpg';
        $filename = app()->getRootPath() . '..' . $file;

        imagejpeg ($imageRes,$filename,90); //保存到本地
        imagedestroy($imageRes);
        $file_handle = new FileHandle();
        $file_handle->upload($filename);
        $file_path = replace_file_domain($file);
        return ['error'=>0,'data'=>['file_path'=>$file_path,'file' => $file]];
    }

}