<?php
/**
 * 公用token处理 分业务
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/4/27 17:20
 */

class Access_token_common_expiresModel extends Model{

    public $api_start_push_ticket_url = "https://api.weixin.qq.com/cgi-bin/component/api_start_push_ticket";
    public function api_start_push_ticket($component_appid, $component_secret) {
        if (!$this->http) {
            import('ORG.Net.Http');
            $this->http = new Http();
        }
        // 设置授权配置
        // appid 允许进行授权的应用id，如1、2、3， 不填或者填空数组都表示允许授权套件内所有应用（仅旧的多应用套件可传此参数，新开发者可忽略）
        //auth_type 授权类型：0 正式授权， 1 测试授权。 默认值为0。注意，请确保应用在正式发布后的授权类型为“正式授权”
        $data = array(
            'component_appid' => $component_appid,
            'component_secret' => $component_secret,
        );
        $api_start_push_ticket_url = $this->api_start_push_ticket_url;
        $api_start_push_ticket_info = $this->http->curlQyWxPost($api_start_push_ticket_url, json_encode($data));
        if (!empty($api_start_push_ticket_info['errcode'])) {
            fdump_api(['设置授权配置line:'.__LINE__,$data,$component_appid, $component_secret,$api_start_push_ticket_url,$api_start_push_ticket_info],'weixin/api_start_push_ticket_err_log',1);
            return $api_start_push_ticket_info;
        }
        fdump_api(['设置授权配置line:'.__LINE__,$data,$component_appid, $component_secret,$api_start_push_ticket_url,$api_start_push_ticket_info],'weixin/api_start_push_ticket_log',1);
        return $api_start_push_ticket_info;
    }
    // 获取 获取第三方应用凭证
    public function get_suite_access_token($property_id, $param=[], $now_time=0) {
        if (empty($param)) {
            $where_suite_ticket = [
                'name' => 'enterprise_wx_suite_ticket'
            ];
            $SuiteTicket = M('Config')->where($where_suite_ticket)->getField('value');
            $SuiteTicket = trim($SuiteTicket);
            $corpid = trim(C('config.enterprise_wx_corpid'));
            $Secret = trim(C('config.enterprise_wx_provider_secret'));
            $SuiteID = trim(C('config.enterprise_wx_provider_suiteid'));
            $Token = trim(C('config.enterprise_wx_provider_token'));
            $EncodingAESKey = trim(C('config.enterprise_wx_provider_encodingaeskey'));
            $param = [
                'corpid' => $corpid,
                'Secret' => $Secret,
                'SuiteID' => $SuiteID,
                'Token' => $Token,
                'SuiteTicket' => $SuiteTicket,
                'EncodingAESKey' => $EncodingAESKey,
            ];
        }
        if (empty($param) || !$param['SuiteID'] || !$param['corpid'] || !$param['SuiteTicket'] || !$param['Secret']) {
            fdump_api(['line:'.__LINE__,$property_id, $param, $now_time],'qyweixin/Enterprise/get_suite_access_token_log',1);
            return array('errcode' => 0, 'errmsg' => '参数不齐请先配置相关参数');
        }
        if (!$now_time) {
            $now_time = time();
        }
        $suiteid = $param['SuiteID'];
        $corpid = $param['corpid'];
        $suite_ticket = $param['SuiteTicket'];
        $suite_secret = $param['Secret'];
        // 获取 获取第三方应用凭证
        $where_suite_token = [
            'type' =>'suite_access_token',
            'access_id' =>$corpid.'_suiteid_'.$suiteid,
        ];
        $suite_token_data = $this->where($where_suite_token)->find();
        if ($suite_token_data && intval($suite_token_data['access_token_expire']) > intval($now_time + 1800)) {
            $suite_access_token = $suite_token_data['access_token'];
        } else {
            if (!$this->http) {
                import('ORG.Net.Http');
                $this->http = new Http();
            }
            // 获取 获取第三方应用凭证
            $suite_access_token_url = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_suite_token';
            $data = array('suite_id' => $suiteid, 'suite_secret' => $suite_secret, 'suite_ticket' => $suite_ticket);
            $suite_access_token_msg = $this->http->curlQyWxPost($suite_access_token_url, json_encode($data));
            if (!empty($suite_access_token_msg['errcode'])) {
                fdump_api(['line:'.__LINE__,$data, $suite_access_token_msg, $suite_token_data],'qyweixin/Enterprise/get_suite_access_token_log',1);
                return $suite_access_token_msg;
            }
            $suite_access_token = $suite_access_token_msg['suite_access_token'];
            $set = array(
                'access_token' => $suite_access_token,
                'access_token_expire' => $suite_access_token_msg['expires_in'] + $now_time,
            );
            if ($suite_token_data) {
                $this->where($where_suite_token)->data($set)->save();
            } else {
                $set['type'] = 'suite_access_token';
                $set['access_id'] = $corpid.'_suiteid_'.$suiteid;
                $this->add($set);
            }
        }
        return array('errcode' => 0, 'access_token' =>$suite_access_token);
    }

    // 获取预授权码
    public function get_pre_auth_code($property_id, $suite_access_token, $param=[], $now_time=0) {
        if (empty($param)) {
            $corpid = trim(C('config.enterprise_wx_corpid'));
            $Secret = trim(C('config.enterprise_wx_provider_secret'));
            $SuiteID = trim(C('config.enterprise_wx_provider_suiteid'));
            $Token = trim(C('config.enterprise_wx_provider_token'));
            $EncodingAESKey = trim(C('config.enterprise_wx_provider_encodingaeskey'));
            $param = [
                'corpid' => $corpid,
                'Secret' => $Secret,
                'SuiteID' => $SuiteID,
                'Token' => $Token,
                'EncodingAESKey' => $EncodingAESKey,
            ];
        }
        if (!$suite_access_token && $param['SuiteID'] && $param['corpid'] && $param['SuiteTicket'] && $param['Secret']) {
            $suite_access_data = $this->get_suite_access_token($property_id, $param, $now_time);
            if ($suite_access_data['errcode']) {
                return $suite_access_data;
            }
            $suite_access_token = $suite_access_data['access_token'];
        }
        if (empty($param) || !$param['SuiteID'] || !$param['corpid'] || !$suite_access_token) {
            fdump_api(['line:'.__LINE__,$property_id, $suite_access_token, $param, $now_time],'qyweixin/Enterprise/get_pre_auth_code_log',1);
            return array('errcode' => 0, 'errmsg' => '参数不齐请先配置相关参数');
        }
        if (!$now_time) {
            $now_time = time();
        }
        $suiteid = $param['SuiteID'];
        $corpid = $param['corpid'];
        // 获取 获取预授权码
        $where_pre_auth_code = [
            'type' =>'pre_auth_code',
            'access_id' =>$corpid.'_preauthcode_'.$suiteid,
        ];
        $pre_auth_code_data = $this->where($where_pre_auth_code)->find();
        if ($pre_auth_code_data && intval($pre_auth_code_data['access_token_expire']) > intval($now_time + 1800)) {
            $pre_auth_code = $pre_auth_code_data['access_token'];
        } else {
            // 获取 获取预授权码
            $pre_auth_code_url = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_pre_auth_code?suite_access_token=' . $suite_access_token;
            $pre_auth_code_msg = json_decode($this->curlGet($pre_auth_code_url));
            if (!empty($pre_auth_code_msg->errcode)) {
                $pre_auth_code_arr = array(
                    'errcode' => $pre_auth_code_msg->errcode,
                    'errmsg' => $pre_auth_code_msg->errmsg,
                );
                fdump_api(['line:'.__LINE__,$suite_access_token,$pre_auth_code_msg],'qyweixin/Enterprise/get_pre_auth_code_log',1);
                return $pre_auth_code_arr;
            }
            $pre_auth_code = $pre_auth_code_msg->pre_auth_code;
            $set = array(
                'access_token' => $suite_access_token,
                'access_token_expire' => $pre_auth_code_msg->expires_in + $now_time,
            );
            if ($pre_auth_code_data) {
                $this->where($where_pre_auth_code)->data($set)->save();
            } else {
                $set['type'] = 'pre_auth_code';
                $set['access_id'] = $corpid.'_preauthcode_'.$suiteid;
                $this->add($set);
            }
        }
        return array('errcode' => 0, 'access_token' =>$pre_auth_code);
    }

    // 获取企业凭证
    public function get_corp_token($property_id, $suite_access_token='', $param=[], $now_time=0) {
        if (empty($param)) {
            $where_suite_ticket = [
                'name' => 'enterprise_wx_suite_ticket'
            ];
            $SuiteTicket = M('Config')->where($where_suite_ticket)->getField('value');
            $SuiteTicket = trim($SuiteTicket);
            $corpid = trim(C('config.enterprise_wx_corpid'));
            $Secret = trim(C('config.enterprise_wx_provider_secret'));
            $SuiteID = trim(C('config.enterprise_wx_provider_suiteid'));
            $Token = trim(C('config.enterprise_wx_provider_token'));
            $EncodingAESKey = trim(C('config.enterprise_wx_provider_encodingaeskey'));
            $param = [
                'corpid' => $corpid,
                'Secret' => $Secret,
                'SuiteID' => $SuiteID,
                'Token' => $Token,
                'SuiteTicket' => $SuiteTicket,
                'EncodingAESKey' => $EncodingAESKey,
            ];
            $where = array(
                'wx_provider_suiteid' => $param['SuiteID'],
                'bind_type' => 0,
            );
            $access_obj = M('House_enterprise_wx_bind')->where($where)->field('access_token, expires_in, corpid, permanent_code')->find();
            if ($access_obj) {
                $param['permanent_code'] = $access_obj['permanent_code'];
            }
        }
        if ($param['SuiteID'] && !$param['permanent_code']) {
            $where = array(
                'wx_provider_suiteid' => $param['SuiteID'],
                'bind_type' => 0,
            );
            $access_obj = M('House_enterprise_wx_bind')->where($where)->field('access_token, expires_in, corpid, permanent_code')->find();
            if ($access_obj) {
                $param['permanent_code'] = $access_obj['permanent_code'];
            }
        }
        if (!$suite_access_token && $param['SuiteID'] && $param['corpid'] && $param['SuiteTicket'] && $param['Secret'] && $param['permanent_code']) {
            $suite_access_data = $this->get_suite_access_token($property_id, $param, $now_time);
            if ($suite_access_data['errcode']) {
                return $suite_access_data;
            }
            $suite_access_token = $suite_access_data['access_token'];
        }
        if (empty($param) || !$param['SuiteID'] || !$param['corpid'] || !$suite_access_token || !$param['permanent_code']) {
            fdump_api(['line:'.__LINE__,$property_id,$suite_access_token,$param,$now_time],'qyweixin/Enterprise/get_pre_auth_code_log',1);
            return array('errcode' => 0, 'errmsg' => '参数不齐请先配置相关参数');
        }
        $suiteid = $param['SuiteID'];
        $corpid = $param['corpid'];
        // 获取 获取第三方应用凭证
        $where_corp_token = [
            'type' =>'enterprise_wx_provider',
            'access_id' =>$corpid.'_suiteid_'.$suiteid,
        ];
        $corp_token_data = $this->where($where_corp_token)->find();
        if ($corp_token_data && intval($corp_token_data['access_token_expire']) > intval($now_time + 1800)) {
            $corp_token = $corp_token_data['access_token'];
        } else {
            if (!$this->http) {
                import('ORG.Net.Http');
                $this->http = new Http();
            }
            // 获取 获取预授权码
            $corp_token_url = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_corp_token?suite_access_token='.$suite_access_token;
            $data = array('auth_corpid' => $corpid, 'permanent_code' => $param['permanent_code']);
            $corp_token_msg = $this->http->curlQyWxPost($corp_token_url, json_encode($data));
            if (!empty($corp_token_msg['errcode'])) {
                fdump_api(['line:'.__LINE__,$data,$suite_access_token,$corp_token_msg],'qyweixin/Enterprise/get_pre_auth_code_log',1);
                return $corp_token_msg;
            }
            $corp_token = $corp_token_msg['access_token'];
            $set = array(
                'access_token' => $corp_token,
                'access_token_expire' => $corp_token_msg['expires_in'] + $now_time,
            );
            if ($corp_token_data) {
                $this->where($where_corp_token)->data($set)->save();
            } else {
                $set['type'] = 'enterprise_wx_provider';
                $set['access_id'] = $corpid.'_preauthcode_'.$suiteid;
                $this->add($set);
            }
        }
        return array('errcode' => 0, 'access_token' =>$corp_token);
    }

    public function curlGet($url){
        $ch = curl_init();
        $header = "Accept-Charset: utf-8";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $temp = curl_exec($ch);
        return $temp;
    }

    public $get_provider_token = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_provider_token';
    // 获取 服务商的token
    public function get_provider_token($corpid, $provider_secret, $now_time=0) {
        $invokeParams = ['new' => false, 'config' => []];
        $invokeResult = invoke_v20_service('\app\community\model\service\workweixin\WorkWeiXinSuiteService/getLoginSuiteAccessToken', $invokeParams);
        fdump_api([
            '获取 服务商的token-line:'.__LINE__, 'invokeParams' => $invokeParams, 'invokeResult' => $invokeResult
        ],'qyweixin/get_provider_token_log',1);
        $result = $invokeResult['data']['retval'];
        $provider_access_token = isset($result['access_token']) && $result['access_token'] ? $result['access_token'] : '';
        return array('errcode' => 0, 'access_token' =>$provider_access_token);
        $where_provider_token = [
            'type' =>'service_provider_secret',
            'access_id' =>$corpid.'_provider_secret_'.$provider_secret,
        ];
        $provider_token_data = $this->where($where_provider_token)->find();
        if (!$now_time) {
            $now_time = time();
        }
        if ($provider_token_data && intval($provider_token_data['access_token_expire']) > intval($now_time + 1800)) {
            $provider_access_token = $provider_token_data['access_token'];
        } else {
            if (!$this->http) {
                import('ORG.Net.Http');
                $this->http = new Http();
            }
            $data = array(
                'corpid' => $corpid,
                'provider_secret' => $provider_secret,
            );
            $provider_access_msg = $this->http->curlQyWxPost($this->get_provider_token, json_encode($data));
            if (!empty($provider_access_msg['errcode'])) {
                fdump_api(['获取 服务商的token-line:'.__LINE__,$data,$corpid, $provider_secret,$provider_access_msg,$this->get_provider_token],'qyweixin/Enterprise/get_provider_token_log',1);
                return $provider_access_msg;
            }
            $provider_access_token = $provider_access_msg['provider_access_token'];
            $set = array(
                'access_token' => $provider_access_token,
                'access_token_expire' => $provider_access_msg['expires_in'] + $now_time,
            );
            if ($provider_token_data) {
                $this->where($where_provider_token)->data($set)->save();
            } else {
                $set['type'] = 'service_provider_secret';
                $set['access_id'] = $corpid.'_provider_secret_'.$provider_secret;
                $this->add($set);
            }
        }
        return array('errcode' => 0, 'access_token' =>$provider_access_token);
    }
    public $get_register_code = "https://qyapi.weixin.qq.com/cgi-bin/service/get_register_code";
    // 用于根据注册推广包生成注册码（register_code）
    public function get_register_code($provider_access_token, $template_id,$state='') {
        if (!$this->http) {
            import('ORG.Net.Http');
            $this->http = new Http();
        }
        $data = array(
            'template_id' => $template_id,
        );
        if ($state) {
            $data['state'] = $state;
        }

        $get_register_code = $this->get_register_code ."?provider_access_token={$provider_access_token}";
        $register_code_msg = $this->http->curlQyWxPost($get_register_code, json_encode($data));
        if (!empty($register_code_msg['errcode'])) {
            fdump_api(['用于根据注册推广包生成注册码（register_code）line:'.__LINE__,$data,$provider_access_token, $template_id,$register_code_msg,$get_register_code],'qyweixin/Enterprise/get_register_code_log',1);
            return $register_code_msg;
        }
        return $register_code_msg;
    }

    public $set_session_url = "https://qyapi.weixin.qq.com/cgi-bin/service/set_session_info";
    // 设置授权配置
    public function set_session_info($suite_access_token, $pre_auth_code) {
        if (!$this->http) {
            import('ORG.Net.Http');
            $this->http = new Http();
        }
        // 设置授权配置
        // appid 允许进行授权的应用id，如1、2、3， 不填或者填空数组都表示允许授权套件内所有应用（仅旧的多应用套件可传此参数，新开发者可忽略）
        //auth_type 授权类型：0 正式授权， 1 测试授权。 默认值为0。注意，请确保应用在正式发布后的授权类型为“正式授权”
        $enterprise_wx_is_online = C('config.enterprise_wx_is_online');
        if ($enterprise_wx_is_online && 1==$enterprise_wx_is_online) {
            $auth_type = 0;
        } else {
            $auth_type = 1;
        }
        $data = array(
            'pre_auth_code' => $pre_auth_code,
            'session_info' => (object)array(
                'auth_type' => $auth_type,
            ),
        );
        $set_session_url = $this->set_session_url ."?suite_access_token={$suite_access_token}";
        $set_session_info = $this->http->curlQyWxPost($set_session_url, json_encode($data));
        if (!empty($set_session_info['errcode'])) {
            fdump_api(['设置授权配置line:'.__LINE__,$data,$suite_access_token, $pre_auth_code,$set_session_url,$set_session_info],'qyweixin/Enterprise/set_session_info_err_log',1);
            return $set_session_info;
        }
        fdump_api(['设置授权配置line:'.__LINE__,$data,$suite_access_token, $pre_auth_code,$set_session_url,$set_session_info],'qyweixin/Enterprise/set_session_info_log',1);
        return $set_session_info;
    }

    // 查询注册状态
    public $get_register_url = "https://qyapi.weixin.qq.com/cgi-bin/service/get_register_info";
    public function get_register_info($provider_access_token, $register_code) {
        if (!$this->http) {
            import('ORG.Net.Http');
            $this->http = new Http();
        }
        $data = array(
            'register_code' => $register_code,
        );
        $get_register_url = $this->get_register_url ."?provider_access_token={$provider_access_token}";
        $get_register_info = $this->http->curlQyWxPost($get_register_url, json_encode($data));
        if (!empty($get_register_info['errcode'])) {
            fdump_api(['设置授权配置line:'.__LINE__,$data,$provider_access_token, $register_code,$get_register_url,$get_register_info],'qyweixin/Enterprise/get_register_info_err_log',1);
            return $get_register_info;
        }
        fdump_api(['设置授权配置line:'.__LINE__,$data,$provider_access_token, $register_code,$get_register_url,$get_register_info],'qyweixin/Enterprise/get_register_info_log',1);
        return $get_register_info;
    }

    //设置通讯录同步完成
    public $contact_sync_success_url = 'https://qyapi.weixin.qq.com/cgi-bin/sync/contact_sync_success';
    public function contact_sync_success($access_token) {
        $contact_sync_success_url = $this->contact_sync_success_url ."?contact_sync_success={$access_token}";
        $contact_sync_success = json_decode($this->curlGet($contact_sync_success_url));
        if (!empty($get_register_info['errcode'])) {
            fdump_api(['设置授权配置line:'.__LINE__,$access_token,$contact_sync_success_url, $contact_sync_success],'qyweixin/Enterprise/contact_sync_success_err_log',1);
            return $get_register_info;
        }
        fdump_api(['设置授权配置line:'.__LINE__,$access_token,$contact_sync_success_url, $contact_sync_success],'qyweixin/Enterprise/contact_sync_success_log',1);
        return $contact_sync_success;
    }

    // 获取企业微信信息
    public $get_auth_info_url = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_auth_info';
    public function get_auth_info($corpid, $permanent_code, $suite_access_token) {
        if (!$this->http) {
            import('ORG.Net.Http');
            $this->http = new Http();
        }
        $data = array(
            'auth_corpid' => $corpid,
            'permanent_code' => $permanent_code,
        );
        $get_auth_info_url = $this->get_auth_info_url ."?suite_access_token={$suite_access_token}";
        $get_auth_info_info = $this->http->curlQyWxPost($get_auth_info_url, json_encode($data));
        if (!empty($get_auth_info_info['errcode'])) {
            fdump_api(['获取企业微信信息line:'.__LINE__,$data,$corpid, $permanent_code, $suite_access_token,$get_auth_info_url,$get_auth_info_info],'qyweixin/Enterprise/get_auth_info_err_log',1);
            return $get_auth_info_info;
        }
        fdump_api(['获取企业微信信息line:'.__LINE__,$data,$corpid, $permanent_code, $suite_access_token,$get_auth_info_url,$get_auth_info_info],'qyweixin/Enterprise/get_auth_info_log',1);
        return $get_auth_info_info;
    }

    // 获取企业微信永久授权码
    public $get_permanent_code_url = "https://qyapi.weixin.qq.com/cgi-bin/service/get_permanent_code";
    public function get_permanent_code($suite_access_token, $auth_code) {
        if (!$this->http) {
            import('ORG.Net.Http');
            $this->http = new Http();
        }
        $data = array(
            'auth_code' => $auth_code,
        );
        $get_permanent_code_url = $this->get_permanent_code_url ."?suite_access_token={$suite_access_token}";
        $get_permanent_code_info = $this->http->curlQyWxPost($get_permanent_code_url, json_encode($data));
        if (!empty($get_permanent_code_info['errcode'])) {
            fdump_api(['获取企业微信信息line:'.__LINE__,$data,$suite_access_token, $auth_code, $suite_access_token,$get_permanent_code_url,$get_permanent_code_info],'qyweixin/Enterprise/get_permanent_code_err_log',1);
            return $get_permanent_code_info;
        }
        fdump_api(['获取企业微信信息line:'.__LINE__,$data,$suite_access_token, $auth_code, $suite_access_token,$get_permanent_code_url,$get_permanent_code_info],'qyweixin/Enterprise/get_permanent_code_log',1);
        return $get_permanent_code_info;
    }

    // 第三方应用开发-获取企业微信登录用户信息
    public function get_login_info($suite_access_token, $auth_code) {
        $get_permanent_code_url = "https://qyapi.weixin.qq.com/cgi-bin/service/auth/getuserinfo3rd?suite_access_token={$suite_access_token}&code={$auth_code}";
        $get_permanent_code_info = $this->curlGet($get_permanent_code_url);
        $get_permanent_code_info = json_decode($get_permanent_code_info, true);
        fdump_api(['获取企业微信信息line:'.__LINE__,$suite_access_token, $auth_code, $suite_access_token,$get_permanent_code_url,$get_permanent_code_info],'qyweixin/Enterprise/get_login_info_log',1);
        return $get_permanent_code_info;
    }

    // 获取企业微信登录用户信息
    public $get_login_info_url = "https://qyapi.weixin.qq.com/cgi-bin/service/get_login_info";
    public function get_login_info_old($suite_access_token, $auth_code) {
        if (!$this->http) {
            import('ORG.Net.Http');
            $this->http = new Http();
        }
        $data = array(
            'auth_code' => $auth_code,
        );
        $get_permanent_code_url = $this->get_login_info_url ."?access_token={$suite_access_token}";
        $get_permanent_code_info = $this->http->curlQyWxPost($get_permanent_code_url, json_encode($data));
        if (!empty($get_permanent_code_info['errcode'])) {
            fdump_api(['获取企业微信信息line:'.__LINE__,$data,$suite_access_token, $auth_code, $suite_access_token,$this->get_login_info_url,$get_permanent_code_info],'qyweixin/Enterprise/get_login_info_err_log',1);
            return $get_permanent_code_info;
        }
        fdump_api(['获取企业微信信息line:'.__LINE__,$data,$suite_access_token, $auth_code, $suite_access_token,$this->get_login_info_url,$get_permanent_code_info],'qyweixin/Enterprise/get_login_info_log',1);
        return $get_permanent_code_info;
    }
}