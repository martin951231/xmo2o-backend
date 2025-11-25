<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/7/26 17:49
 */
class QywxAction extends BaseAction{

    public function qyRegister() {
        $site_url = I('site_url');
        $domainName = I('domainName');
        $siteIp = I('siteIp');
        $ip = I('ip');
        $qyRegisterId = I('qyRegisterId');
        $property_id = I('property_id');
        $arr = [
            'errcode' => 0,
            'errmsg' => '',
        ];
        fdump(['line:'.__LINE__,$_POST,$site_url,$domainName,$siteIp,$ip,$qyRegisterId,$property_id], 'qyRegister',1);
        if (empty($site_url) || empty($siteIp)) {
            $arr = [
                'errcode' => 1001,
                'errmsg' => '请传递正确参数',
            ];
            exit(json_encode($arr));
        }
        if (!$domainName) {
            $domainName = str_replace('http://','',$site_url);
            $domainName = str_replace('https://','',$domainName);
        }
        $provider_secret = C('config.service_provider_secret');
        $corpid = C('config.enterprise_wx_corpid');
        $template_id = C('config.service_template_id');
        if (empty($corpid)) {
            $arr = [
                'errcode' => 1001,
                'errmsg' => '请联系提供服务方配置企业微信ID',
            ];
            exit(json_encode($arr));
        }
        if (empty($provider_secret)) {
            $arr = [
                'errcode' => 1001,
                'errmsg' => '请联系提供服务方 前往“服务商管理端-应用管理-通用开发参数中复制ProviderSecret配置到企业微信配置中',
            ];
            exit(json_encode($arr));
        }
        if (empty($template_id)) {
            $arr = [
                'errcode' => 1001,
                'errmsg' => '请联系提供服务方 前往“服务商管理端-应用管理-推广二维码”，创建的推广码详情复制到企业微信配置中',
            ];
        }
        $provider_access_msg = D('Access_token_common_expires')->get_provider_token($corpid, $provider_secret);
        if (!$provider_access_msg['errcode']) {
            $provider_access_token = $provider_access_msg['access_token'];
            $db_village_qywx_guide_about = M('Village_qywx_guide_about');
            if ($property_id) {
                $where = [
                    'is_del' => 0,
                    'from' => 1,
                    'business_type' => 'property',
                    'business_id' => $property_id,
                    'type' => 'qyRegisterPost',
                    'domainName' => $domainName,
                    'wx_bind_id' => 0,
                ];
            } elseif ($qyRegisterId) {
                $property_id = 0;
                $where = [
                    'is_del' => 0,
                    'from' => 1,
                    'business_type' => 'property',
                    'randomNumber' => $qyRegisterId,
                    'type' => 'qyRegisterPost',
                    'domainName' => $domainName,
                    'wx_bind_id' => 0,
                ];
            }
            if (empty($where)) {
                $qywx_guide_about = [];
            } else {
                $qywx_guide_about = $db_village_qywx_guide_about->where($where)->order('id DESC')->find();
            }
            if ($qywx_guide_about) {
                $guide_about_data = [];
                $type = $qywx_guide_about['type'];
                if (!$qyRegisterId && !$qywx_guide_about['randomNumber']) {
                    $qyRegisterId = $type . date('YmdHi') . createRandomStr(5).$qywx_guide_about['from'];
                    $guide_about_data['randomNumber'] = $qyRegisterId;
                } elseif ($qyRegisterId && !$qywx_guide_about['randomNumber']) {
                    $guide_about_data['randomNumber'] = $qyRegisterId;
                }
                if (!$qyRegisterId && $qywx_guide_about['randomNumber']) {
                    $qyRegisterId = $qywx_guide_about['randomNumber'];
                }
                if ($property_id && !$qywx_guide_about['business_id']) {
                    $guide_about_data['business_id'] = $property_id;
                }
                if ($site_url && $site_url!=$qywx_guide_about['site_url']) {
                    $guide_about_data['site_url'] = $site_url;
                }
                if ($siteIp && $siteIp!=$qywx_guide_about['siteIp']) {
                    $guide_about_data['siteIp'] = $siteIp;
                }
                if ($ip && $ip!=$qywx_guide_about['ip']) {
                    $guide_about_data['ip'] = $ip;
                }
                if ($domainName && $domainName!=$qywx_guide_about['domainName']) {
                    $guide_about_data['domainName'] = $domainName;
                }
                if ($corpid && $corpid!=$qywx_guide_about['ServiceCorpId']) {
                    $guide_about_data['ServiceCorpId'] = $corpid;
                }
                if (!empty($guide_about_data)) {
                    $guide_about_data['update_time'] = time();
                    $db_village_qywx_guide_about->where($where)->save($guide_about_data);
                }
            } else {
                $type = 'qyRegisterPost';
                $guide_about_data = [
                    'is_del' => 0,
                    'business_type' => 'property',
                    'type' => $type,
                    'from' => 1,
                    'business_id' => $property_id,
                    'site_url' => $site_url?$site_url:'',
                    'siteIp' => $siteIp?$siteIp:'',
                    'ip' => $ip?$ip:'',
                    'domainName' => $domainName?$domainName:'',
                    'add_time' => time()
                ];
                if (!$qyRegisterId) {
                    // 长串保持为20位
                    $qyRegisterId = $type . date('YmdHi') . createRandomStr(5).$guide_about_data['from'];
                }
                if ($qyRegisterId) {
                    $guide_about_data['randomNumber'] = $qyRegisterId;
                }
                if ($property_id) {
                    $guide_about_data['business_id'] = $property_id;
                } else {
                    $property_id = 0;
                }
                if ($corpid) {
                    $guide_about_data['ServiceCorpId'] = $corpid;
                }
                $db_village_qywx_guide_about->add($guide_about_data);
            }
            $state = "{$qyRegisterId}";
            fdump(['line:'.__LINE__,$state], 'qyRegister',1);
            $register_code_msg = D('Access_token_common_expires')->get_register_code($provider_access_token, $template_id,$state);
            if ($register_code_msg['errcode']) {
                exit(json_encode($register_code_msg));
            }
            $register_code = $register_code_msg['register_code'];
        } else {
            exit(json_encode($provider_access_msg));
        }
        $url = "https://open.work.weixin.qq.com/3rdservice/wework/register?register_code=$register_code";
        $arr['url'] = $url;
        $arr['qyRegisterId'] = $qyRegisterId;
        $SuiteID = C('config.enterprise_wx_provider_suiteid');
        $provider_config = [
            'corpid' => $corpid,
            'SuiteID' => $SuiteID,
            'provider_secret' => $provider_secret,
            'template_id' => $template_id,
            'provider_access_token' => $provider_access_token,
        ];
        $arr['provider_config'] = $provider_config;
        exit(json_encode($arr));
    }

    public function qyInstall(){
        $site_url = I('site_url');
        $domainName = I('domainName');
        $siteIp = I('siteIp');
        $ip = I('ip');
        $qyInstallId = I('qyInstallId');
        $property_id = I('property_id');
        $arr = [
            'errcode' => 0,
            'errmsg' => '',
        ];
        fdump(['line:'.__LINE__,$_POST,$site_url,$domainName,$siteIp,$ip,$qyInstallId,$property_id], 'qyInstall',1);
        if (empty($site_url) || empty($siteIp)) {
            $arr = [
                'errcode' => 1001,
                'errmsg' => '请传递正确参数',
            ];
            exit(json_encode($arr));
        }
        if (!$domainName) {
            $domainName = str_replace('http://','',$site_url);
            $domainName = str_replace('https://','',$domainName);
        }
        $corpid = C('config.enterprise_wx_corpid');
        $Secret = C('config.enterprise_wx_provider_secret');
        $SuiteID = C('config.enterprise_wx_provider_suiteid');
        $Token = C('config.enterprise_wx_provider_token');
        $where_suite_ticket = [
            'name' => 'enterprise_wx_suite_ticket'
        ];
        $SuiteTicket = M('Config')->where($where_suite_ticket)->getField('value');
        $SuiteTicket = trim($SuiteTicket);
        if (empty($corpid)) {
            $arr = [
                'errcode' => 1001,
                'errmsg' => '请联系提供服务方配置企业微信ID',
            ];
            exit(json_encode($arr));
        }
        if (empty($Secret)) {
            $arr = [
                'errcode' => 1001,
                'errmsg' => '请联系提供服务方 前往“服务商管理端-应用管理-网页应用-对应创建的普通应用”中复制Secret到企业微信配置中',
            ];
            exit(json_encode($arr));
        }
        if (empty($SuiteID)) {
            $arr = [
                'errcode' => 1001,
                'errmsg' => '请联系提供服务方 前往“服务商管理端-应用管理-网页应用-对应创建的普通应用”中复制SuiteID到企业微信配置中',
            ];
        }
        if (empty($Token)) {
            $arr = [
                'errcode' => 1001,
                'errmsg' => '请联系提供服务方 前往“服务商管理端-应用管理-网页应用-对应创建的普通应用”中复制Token到企业微信配置中',
            ];
        }
        if (empty($SuiteTicket)) {
            $arr = [
                'errcode' => 1001,
                'errmsg' => '请联系提供服务方 前往“服务商管理端-应用管理-网页应用-对应创建的普通应用”中正确完成回调配置',
            ];
        }

        $provider_config = [
            'corpid' => $corpid,
            'Secret' => $Secret,
            'SuiteID' => $SuiteID,
            'Token' => $Token,
            'SuiteTicket' => $SuiteTicket,
        ];
        $now = time();
       // 获取 获取第三方应用凭证
        $suite_access_token_msg = D('Access_token_common_expires')->get_suite_access_token($property_id,$provider_config,$now);
        if (!$suite_access_token_msg['errcode']) {
            $suite_access_token = $suite_access_token_msg['access_token'];
            // 获取预授权码
            $pre_auth_code_msg = D('Access_token_common_expires')->get_pre_auth_code($property_id,$suite_access_token,$provider_config,$now);
            if (!$pre_auth_code_msg['errcode']){
                $pre_auth_code = $pre_auth_code_msg['access_token'];
                $where_pre_auth_code = [
                    'type' =>'pre_auth_code',
                    'access_id' =>$corpid.'_preauthcode_'.$SuiteID,
                ];
                $db_access_token_common_expires=D('Access_token_common_expires');
                $pre_auth_code_data = $db_access_token_common_expires->where($where_pre_auth_code)->order('id DESC')->find();
                $set = [
                    'access_token_expire'=> $now
                ];
                $db_access_token_common_expires->where(['id'=> $pre_auth_code_data['id']])->save($set);
                if (!$this->http) {
                    import('ORG.Net.Http');
                    $this->http = new Http();
                }
                $set_session_info = $db_access_token_common_expires->set_session_info($suite_access_token, $pre_auth_code);
                if (!$set_session_info['errcode']) {
                    $db_village_qywx_guide_about = M('Village_qywx_guide_about');
                    if ($property_id) {
                        $where = [
                            'is_del' => 0,
                            'from' => 1,
                            'business_type' => 'property',
                            'business_id' => $property_id,
                            'type' => 'qyInstallPost',
                            'domainName' => $domainName,
                        ];
                    } elseif ($qyInstallId) {
                        $property_id = 0;
                        $where = [
                            'is_del' => 0,
                            'from' => 1,
                            'business_type' => 'property',
                            'randomNumber' => $qyInstallId,
                            'type' => 'qyInstallPost',
                            'domainName' => $domainName,
                        ];
                    }
                    if (empty($where)) {
                        $qywx_guide_about = [];
                    } else {
                        $qywx_guide_about = $db_village_qywx_guide_about->where($where)->order('id DESC')->find();
                    }
                    fdump(['line:'.__LINE__,$qywx_guide_about, $where],'$qywx_guide_about');
                    if ($qywx_guide_about) {
                        $guide_about_data = [];
                        $type = $qywx_guide_about['type'];
                        if (!$qyInstallId && !$qywx_guide_about['randomNumber']) {
                            $qyInstallId = $type . date('YmdHi') . createRandomStr(5).$qywx_guide_about['from'];
                            $guide_about_data['randomNumber'] = $qyInstallId;
                        } elseif ($qyInstallId && !$qywx_guide_about['randomNumber']) {
                            $guide_about_data['randomNumber'] = $qyInstallId;
                        }
                        if (!$qyInstallId && $qywx_guide_about['randomNumber']) {
                            $qyInstallId = $qywx_guide_about['randomNumber'];
                        }
                        if ($property_id && !$qywx_guide_about['business_id']) {
                            $guide_about_data['business_id'] = $property_id;
                        }
                        if ($corpid && $corpid!=$qywx_guide_about['ServiceCorpId']) {
                            $guide_about_data['ServiceCorpId'] = $corpid;
                        }
                        if (!empty($guide_about_data)) {
                            $guide_about_data['update_time'] = time();
                             $db_village_qywx_guide_about->where($where)->save($guide_about_data);
                        }
                    } else {
                        $type = 'qyInstallPost';
                        $guide_about_data = [
                            'is_del' => 0,
                            'business_type' => 'property',
                            'type' => $type,
                            'from' => 1,
                            'business_id' => $property_id,
                            'site_url' => $site_url?$site_url:'',
                            'siteIp' => $siteIp?$siteIp:'',
                            'ip' => $ip?$ip:'',
                            'domainName' => $domainName?$domainName:'',
                            'add_time' => time()
                        ];
                        if (!$qyInstallId) {
                            // 长串保持为20位
                            $qyInstallId = $type . date('YmdHi') . createRandomStr(5).$guide_about_data['from'];
                        }
                        if ($qyInstallId) {
                            $guide_about_data['randomNumber'] = $qyInstallId;
                        }
                        if ($property_id) {
                            $guide_about_data['business_id'] = $property_id;
                        }
                        if ($corpid) {
                            $guide_about_data['ServiceCorpId'] = $corpid;
                        }
                        $db_village_qywx_guide_about->add($guide_about_data);
                    }
                    $state = "{$qyInstallId}";
                    $redirect_url = C('config.site_url') . "/index.php?g=Index&c=Qywx&a=auth_back&suite_id={$SuiteID}";
                    $redirect_url = urlencode($redirect_url);
                }
            }
        } else {
            exit(json_encode($suite_access_token_msg));
        }
        // https://open.work.weixin.qq.com/3rdapp/install?suite_id=SUITE_ID&pre_auth_code=PRE_AUTH_CODE&redirect_uri=REDIRECT_URI&state=STATE
        $url ="https://open.work.weixin.qq.com/3rdapp/install?suite_id={$SuiteID}&pre_auth_code={$pre_auth_code}&redirect_uri={$redirect_url}&state={$state}";
        $arr['url'] = $url;
        $arr['qyInstallId'] = $qyInstallId;
        $arr['provider_config'] = $provider_config;
        exit(json_encode($arr));
    }

    public function qyLogin() {
        $site_url = I('site_url');
        $domainName = I('domainName');
        $siteIp = I('siteIp');
        $ip = I('ip');
        $qyLoginId = I('qyLoginId');
        $property_id = I('property_id');
        $arr = [
            'errcode' => 0,
            'errmsg' => '',
        ];
        fdump(['line:'.__LINE__,$_POST,$site_url,$domainName,$siteIp,$ip,$qyLoginId,$property_id], 'qyLogin',1);
        if (empty($site_url) || empty($siteIp)) {
            $arr = [
                'errcode' => 1001,
                'errmsg' => '请传递正确参数',
            ];
            exit(json_encode($arr));
        }
        if (!$domainName) {
            $domainName = str_replace('http://','',$site_url);
            $domainName = str_replace('https://','',$domainName);
        }
        $corpid = C('config.enterprise_wx_corpid');
        if (empty($corpid)) {
            $arr = [
                'errcode' => 1001,
                'errmsg' => '请联系提供服务方配置企业微信ID',
            ];
            exit(json_encode($arr));
        }
        $db_village_qywx_guide_about = M('Village_qywx_guide_about');
        if ($property_id) {
            $where = [
                'is_del' => 0,
                'from' => 1,
                'business_type' => 'property',
                'business_id' => $property_id,
                'type' => 'qyLoginPost',
                'domainName' => $domainName,
            ];
        } elseif ($qyLoginId) {
            $property_id = 0;
            $where = [
                'is_del' => 0,
                'from' => 1,
                'business_type' => 'property',
                'randomNumber' => $qyLoginId,
                'type' => 'qyLoginPost',
                'domainName' => $domainName,
            ];
        }
        if (empty($where)) {
            $qywx_guide_about = [];
        } else {
            $qywx_guide_about = $db_village_qywx_guide_about->where($where)->order('id DESC')->find();
        }
        if ($qywx_guide_about) {
            $guide_about_data = [];
            $type = $qywx_guide_about['type'];
            if (!$qyLoginId && !$qywx_guide_about['randomNumber']) {
                $qyLoginId = $type . date('YmdHi') . createRandomStr(5).$qywx_guide_about['from'];
                $guide_about_data['randomNumber'] = $qyLoginId;
            } elseif ($qyLoginId && !$qywx_guide_about['randomNumber']) {
                $guide_about_data['randomNumber'] = $qyLoginId;
            }
            if (!$qyLoginId && $qywx_guide_about['randomNumber']) {
                $qyLoginId = $qywx_guide_about['randomNumber'];
            }
            if ($property_id && !$qywx_guide_about['business_id']) {
                $guide_about_data['business_id'] = $property_id;
            }
            if ($corpid && $corpid!=$qywx_guide_about['ServiceCorpId']) {
                $guide_about_data['ServiceCorpId'] = $corpid;
            }
            if (!empty($guide_about_data)) {
                $guide_about_data['update_time'] = time();
                $db_village_qywx_guide_about->where($where)->save($guide_about_data);
            }
        } else {
            $type = 'qyLoginPost';
            $guide_about_data = [
                'is_del' => 0,
                'business_type' => 'property',
                'type' => $type,
                'from' => 1,
                'business_id' => $property_id,
                'site_url' => $site_url?$site_url:'',
                'siteIp' => $siteIp?$siteIp:'',
                'ip' => $ip?$ip:'',
                'domainName' => $domainName?$domainName:'',
                'add_time' => time()
            ];
            if (!$qyLoginId) {
                // 长串保持为20位
                $qyLoginId = $type . date('YmdHi') . createRandomStr(5).$guide_about_data['from'];
            }
            if ($qyLoginId) {
                $guide_about_data['randomNumber'] = $qyLoginId;
            }
            if ($property_id) {
                $guide_about_data['business_id'] = $property_id;
            }
            if ($corpid) {
                $guide_about_data['ServiceCorpId'] = $corpid;
            }
            $db_village_qywx_guide_about->add($guide_about_data);
        }
        $state = "{$qyLoginId}";
        $redirect_url = C('config.site_url') . "/index.php?g=Index&c=Qywx&a=auth_login";
        $redirect_url = urlencode($redirect_url);
        //https://open.work.weixin.qq.com/wwopen/sso/3rd_qrConnect?appid=ww100000a5f2191&redirect_uri=http%3A%2F%2Fwww.oa.com&state=web_login@gyoss9&usertype=admin
        $bind_url ="https://open.work.weixin.qq.com/wwopen/sso/3rd_qrConnect?appid={$corpid}&redirect_uri={$redirect_url}&state={$state}&usertype=admin";
        $arr['url'] = $bind_url;
        $arr['qyLoginId'] = $qyLoginId;
        $arr['provider_config'] = [
            'corpid' => $corpid
        ];
        exit(json_encode($arr));
    }

    public function qyGuideAbout() {
        $site_url = I('site_url');
        $domainName = I('domainName');
        $siteIp = I('siteIp');
        $ip = I('ip');
        $randomNumber = I('randomNumber');
        $property_id = I('property_id');
        if (empty($randomNumber) || empty($site_url)) {
            $arr = [
                'errcode' => 1001,
                'errmsg' => '请传递正确参数',
            ];
            exit(json_encode($arr));
        }
        if (!$domainName) {
            $domainName = str_replace('http://','',$site_url);
            $domainName = str_replace('https://','',$domainName);
        }
        $db_village_qywx_guide_about = M('Village_qywx_guide_about');
        $where = [
            'is_del' => 0,
            'from' => 1,
            'business_type' => 'property',
            'domainName' => $domainName,
            'randomNumber' => $randomNumber,
        ];
        if ($property_id) {
            $where['property_id'] = $property_id;
        }
        if ($domainName) {
            $where['domainName'] = $domainName;
        }
        $qywx_guide_about = $db_village_qywx_guide_about->where($where)->order('qyResult DESC,wx_bind_id DESC, id DESC')->find();
        if (empty($qywx_guide_about)) {
            $qywx_guide_about = [];
        }
        $arr = [
            'errcode' => 0,
            'errmsg' => '',
            'info' => $qywx_guide_about
        ];
        exit(json_encode($arr));
    }

    public function qyGuideSet() {
        $site_url = I('site_url');
        $domainName = I('domainName');
        $siteIp = I('siteIp');
        $ip = I('ip');
        $randomNumber = I('randomNumber');
        $property_id = I('property_id');
        $wx_bind_id = I('wx_bind_id');
        if (empty($randomNumber) || empty($site_url) || empty($wx_bind_id)) {
            $arr = [
                'errcode' => 1001,
                'errmsg' => '请传递正确参数',
            ];
            exit(json_encode($arr));
        }
        if (!$domainName) {
            $domainName = str_replace('http://','',$site_url);
            $domainName = str_replace('https://','',$domainName);
        }
        $db_village_qywx_guide_about = M('Village_qywx_guide_about');
        $where = [
            'is_del' => 0,
            'from' => 1,
            'business_type' => 'property',
            'domainName' => $domainName,
            'randomNumber' => $randomNumber,
        ];
        if ($property_id) {
            $where['property_id'] = $property_id;
        }
        if ($domainName) {
            $where['domainName'] = $domainName;
        }
        $data = [
            'wx_bind_id' => $wx_bind_id,
            'update_time' => time()
        ];
        $db_village_qywx_guide_about->where($where)->save($data);
        $arr = [
            'errcode' => 0,
            'errmsg' => '',
        ];
        exit(json_encode($arr));

    }

    public function getDataConfig() {
        $data_config = [];
        $data_config['enterprise_wx_corpid'] = C('config.enterprise_wx_corpid');
        $data_config['enterprise_wx_provider_encodingaeskey'] = C('config.enterprise_wx_provider_encodingaeskey');
        $data_config['enterprise_wx_provider_secret'] = C('config.enterprise_wx_provider_secret');
        $data_config['enterprise_wx_provider_suiteid'] = C('config.enterprise_wx_provider_suiteid');
        $data_config['enterprise_wx_provider_token'] = C('config.enterprise_wx_provider_token');
        $data_config['service_provider_secret'] = C('config.service_provider_secret');
        $data_config['service_provider_token'] = C('config.service_provider_token');
        $data_config['service_provider_encodingaeskey'] = C('config.service_provider_encodingaeskey');
        $data_config['service_template_id'] = C('config.service_template_id');
        $where_suite_ticket = [
            'name' => 'enterprise_wx_suite_ticket'
        ];
        $SuiteTicket = M('Config')->where($where_suite_ticket)->getField('value');
        $SuiteTicket = trim($SuiteTicket);
        $data_config['enterprise_wx_suite_ticket'] = $SuiteTicket;
        exit(json_encode($data_config));
    }

    public function auth_back()
    {
        fdump_api(['line:'.__LINE__,$_POST, $_GET],'qyweixin/QywxAction/auth_back_log',1);
        $randomNumber = trim($_GET['randomNumber']);
        if ($randomNumber) {
            $where = [
                'randomNumber' => $randomNumber,
            ];
            $db_village_qywx_guide_about = M('Village_qywx_guide_about');
            $qywx_guide_about = $db_village_qywx_guide_about->where($where)->order('qyResult DESC,wx_bind_id DESC, id DESC')->find();
            if (empty($qywx_guide_about)) {
                fdump_api(['line:'.__LINE__,$_POST, $_GET,$qywx_guide_about,$randomNumber],'qyweixin/QywxAction/auth_back_log',1);
                $this->assign('errmsg', '未获取到相关信息');
                $this->display('fail');
                exit();
            }
            if (!$qywx_guide_about['business_id'] || 'property'!=$qywx_guide_about['business_type']) {
                fdump_api(['line:'.__LINE__,$_POST, $_GET,$qywx_guide_about,$randomNumber],'qyweixin/QywxAction/auth_back_log',1);
                $this->assign('errmsg', '非物业后台响应信息');
                $this->display('fail');
                exit();
            }
            import('@.ORG.qywx.config');
            $config = new config();
            fdump_api(['ajaxGyGuideAbout-line:' . __LINE__, 'time' => microtime(true), 'randomNumber' => $randomNumber, 'type' => 'install', 'business_id' => $qywx_guide_about['business_id']], 'qyweixin/qyGuideAboutLog', 1);
            $msg = $config->qyGuideAbout($randomNumber,'install',$qywx_guide_about['business_id']);
            fdump_api(['ajaxGyGuideAbout-line:' . __LINE__, 'time' => microtime(true), 'randomNumber' => $randomNumber, 'msg' => $msg], 'qyweixin/qyGuideAboutLog', 1);
            if ($msg['install']) {
                if ($msg['jump_url']) {
                    $success_url = $msg['jump_url'];
                } else {
                    $success_url = $this->config['site_url']."/v20/public/platform/#/property/property.iframe/property_enterprise_weixin";
                }
                if ($msg['ticket']) {
                    $ticket = $msg['ticket'];
                } else {
                    // 在原页面处理登录
                    $database_property = D('House_property');
                    $condition_village['id'] = $qywx_guide_about['business_id'];
                    $now_property = $database_property->field(true)->where($condition_village)->find();
                    $now_property['login_identify'] = 1;
                    session('property', $now_property);
                    // 给予物业管理员身份
                    $ticket = Token::createToken($_GET['property_id'], 8);
                    setcookie('property_access_token', $ticket, -1, '/');
                }
                $this->assign('success_ticket', $ticket);
                $this->assign('success_url', $success_url);
                $this->display('success');
            }
        } elseif (isset($_GET['auth_code']) && isset($_GET['expires_in'])){
            import('ORG.Net.Http');
            $randomNumber = trim($_GET['state']);
            $where = [
                'randomNumber' => $randomNumber,
            ];
            $db_village_qywx_guide_about = M('Village_qywx_guide_about');
            $qywx_guide_about = $db_village_qywx_guide_about->where($where)->order('qyResult DESC,wx_bind_id DESC, id DESC')->find();
            if (empty($qywx_guide_about)) {
                fdump_api(['line:'.__LINE__,$_POST, $_GET,$qywx_guide_about,$randomNumber],'qyweixin/QywxAction/auth_back_log',1);
                $this->assign('errmsg', '未获取到相关信息');
                $this->display('fail');
                exit();
            }
            $provider_config = [
                'corpid' => C('config.enterprise_wx_corpid'),
                'Secret' => C('config.enterprise_wx_provider_secret'),
                'SuiteID' => C('config.enterprise_wx_provider_suiteid'),
                'Token' => C('config.enterprise_wx_provider_token'),
                'SuiteTicket' => C('config.enterprise_wx_suite_ticket'),
                'EncodingAESKey' => C('config.enterprise_wx_provider_secret'),
            ];
            if (empty($provider_config)) {
                $this->assign('errmsg', '对应物业服务商相关配置缺失');
                $this->display('fail');
                exit();
            }
            $now = time();
            $db_access_token_common_expires = D('Access_token_common_expires');
            // 获取 获取第三方应用凭证
            $suite_access_token_msg = $db_access_token_common_expires->get_suite_access_token(0,$provider_config,$now);
            fdump_api(['line:'.__LINE__,$provider_config,$now, $suite_access_token_msg],'qyweixin/QywxAction/auth_back_log',1);
            if (!empty($suite_access_token_msg['errcode'])) {
                $this->assign('errmsg', $suite_access_token_msg['errmsg']?$suite_access_token_msg['errmsg']:'获取凭证失败');
                $this->display('fail');
                exit();
            }
            $suite_access_token = $suite_access_token_msg['access_token'];
            //获取企业永久授权码
            $auth_code = trim($_GET['auth_code']);
            $result1 = $db_access_token_common_expires->get_permanent_code($suite_access_token, $auth_code);
            fdump_api(['获取企业微信永久授权line:'.__LINE__,$suite_access_token, $auth_code,$result1],'qyweixin/QywxAction/auth_back_log',1);
            if ($result1['errcode']) {
                $this->assign('errmsg', $result1['errmsg']);
                $this->display('fail');
                exit();
            } else {
                $auth_corp_info = $result1['auth_corp_info'];
                $dataSet = [
                    'qyResult' => serialize($result1),
                    'update_time' => time(),
                ];
                if ($auth_corp_info['corpid']) {
                    $corpid = $auth_corp_info['corpid'];
                    $dataSet['AuthCorpId'] = $corpid;
                }
                $set = $db_village_qywx_guide_about->where($where)->save($dataSet);
                if (!$set) {
                    fdump_api(['获取企业微信永久授权line:'.__LINE__,$suite_access_token, $auth_code,$result1],'qyweixin/QywxAction/auth_back_err_log',1);
                    $this->assign('errmsg', '记录信息失败');
                    $this->display('fail');
                    exit();
                }
                if ($qywx_guide_about['type']=='qyInstallSelf' && $qywx_guide_about['business_id'] && 'property'==$qywx_guide_about['business_type']) {
                    // 如果是自身 直接处理然后跳转
                    $property_id = $qywx_guide_about['business_id'];
                    $data = $result1;
                    $now_time = time();
                    unset($data['errcode']);
                    unset($data['errmsg']);
                    unset($data['errmsg']);
                    $data['expires_in'] = $data['expires_in'] + $now_time;
                    $data['bind_type'] = 0;
                    $data['bind_id'] = $property_id;
                    $data['wx_provider_suiteid'] = $provider_config['SuiteID'];
                    $data['corpid'] = $corpid;
                    $auth_info = $data['auth_info'];
                    if ($auth_info && !empty($auth_info['agent'])) {
                        $agent = reset($auth_info['agent']);
                        $data['agentid'] = $agent['agentid'];
                        $data['corp_square_logo_url'] = $agent['square_logo_url']?$agent['square_logo_url']:'';
                    }
                    if ($auth_corp_info) {
                        $data['corp_name'] = $auth_corp_info['corp_name']? $auth_corp_info['corp_name']:'';
                        $data['corp_type'] = $auth_corp_info['corp_type']?$auth_corp_info['corp_type']:'';
                        $data['subject_type'] = $auth_corp_info['subject_type']?$auth_corp_info['subject_type']:'';
                        $data['verified_end_time'] = $auth_corp_info['verified_end_time']?$auth_corp_info['verified_end_time']:'';
                        $data['corp_wxqrcode'] = $auth_corp_info['corp_wxqrcode']?$auth_corp_info['corp_wxqrcode']:'';
                        $data['auth_corp_info'] = serialize($auth_corp_info);

                        $data['corp_user_max'] = $auth_corp_info['corp_user_max']?$auth_corp_info['corp_user_max']:0;
                        $data['corp_agent_max'] = $auth_corp_info['corp_agent_max']?$auth_corp_info['corp_agent_max']:0;
                        $data['corp_full_name'] = $auth_corp_info['corp_full_name']?$auth_corp_info['corp_full_name']:0;
                    }
                    $data['auth_info'] = serialize($auth_info);
                    $data['userid'] = $data['auth_user_info']['userid'];
                    $data['name'] = $data['auth_user_info']['name'];
                    $data['avatar'] = $data['auth_user_info']['avatar'];
                    $data['auth_user_info'] = serialize($data['auth_user_info']);
                    $data['add_time'] = time();
                    if ($is_bind = D('House_enterprise_wx_bind')->where(array('corpid' => $data['corpid'], 'bind_type' => 0))->find()) {
                        fdump(['line:'.__LINE__,$is_bind,$property_id],'$fail');
                        if ($is_bind['bind_id'] != $property_id) {
                            $this->assign('errmsg', '该企业公众号已在其他物业完成绑定，无法绑定到当前物业！');
                            $this->display('fail');
                            exit();
                        }
                    }
                    fdump_api(['获取企业微信永久授权line:'.__LINE__,$data],'qyweixin/Enterprise/auth_back_log',1);
                    if ($weixin_bind = D('House_enterprise_wx_bind')->where(array('bind_id' => $this->property_id, 'bind_type' => 0,'wx_provider_suiteid'=>$data['wx_provider_suiteid']))->find()) {
                        D('House_enterprise_wx_bind')->where(array('bind_id' => $this->property_id, 'bind_type' => 0,'wx_provider_suiteid'=>$data['wx_provider_suiteid']))->data($data)->save();
                    } else {
                        D('House_enterprise_wx_bind')->data($data)->add();
                    }
                    // 在原页面处理登录
                    $success_url = $this->config['site_url']."/v20/public/platform/#/property/property.iframe/property_enterprise_weixin";

                    fdump_api(['获取企业微信永久授权line:'.__LINE__,$success_url],'qyweixin/Enterprise/auth_back_log',1);

                    $this->assign('success_url', $success_url);
                    $this->display('success');
                } elseif ($qywx_guide_about['type']=='qyInstallPost' && $qywx_guide_about['from']==1) {
                    $url = $qywx_guide_about['site_url'] ."/index.php?g=Index&c=Qywx&a=auth_back&randomNumber={$randomNumber}";
                    redirect($url);
                } else {
                    $this->assign('success_tip', '授权成功请前往跳转前页面填写注册参数');
                    $this->display('success');
                }
            }
        } else {
            $this->assign('errmsg', '不合法的请求授权');
            $this->display('fail');
        }
    }

    public function auth_login()
    {
        fdump_api(['line:'.__LINE__,$_POST, $_GET],'qyweixin/QywxAction/auth_login_log',1);
        $auth_code = isset($_GET['auth_code']) && $_GET['auth_code'] ? $_GET['auth_code'] : '';
        !$auth_code && $auth_code = isset($_GET['code']) && $_GET['code'] ? $_GET['code'] : '';
        if ($auth_code&& isset($_GET['state'])){
            import('ORG.Net.Http');
            $randomNumber = trim($_GET['state']);
            $where = [
                'randomNumber' => $randomNumber,
            ];
            $db_village_qywx_guide_about = M('Village_qywx_guide_about');
            $qywx_guide_about = $db_village_qywx_guide_about->where($where)->order('qyResult DESC,wx_bind_id DESC, id DESC')->find();
            if (empty($qywx_guide_about)) {
                fdump_api(['line:'.__LINE__,$_POST, $_GET,$qywx_guide_about,$randomNumber],'qyweixin/QywxAction/auth_login_log',1);
                $this->assign('errmsg', '未获取到相关信息');
                $this->display('fail');
                exit();
            }
            // 自身
            $provider_secret = C('config.service_provider_secret');
            $corpid = C('config.enterprise_wx_corpid');
            if (empty($corpid)) {
                fdump_api(['qyRegisterSet-line请配置企业微信ID:'.__LINE__, $qywx_guide_about,$randomNumber],'qyweixin/QywxAction/auth_login_log',1);
                return array('errcode' => 1001, 'errmsg' => '请配置企业微信ID');
            }
            if (empty($provider_secret)) {
                fdump_api(['qyRegisterSet-line请配置企业ProviderSecret:'.__LINE__,$qywx_guide_about,$randomNumber,$corpid],'qyweixin/QywxAction/auth_login_log',1);
                return array('errcode' => 1001, 'errmsg' => '请前往“服务商管理端-应用管理-通用开发参数”中复制ProviderSecret配置到企业微信配置中');
            }
            $db_access_token_common_expires = D('Access_token_common_expires');
            // 获取服务商token
            $provider_access_token_msg = $db_access_token_common_expires->get_provider_token($corpid, $provider_secret);
            fdump_api(['line:'.__LINE__,$provider_secret,$corpid, $provider_access_token_msg],'qyweixin/QywxAction/auth_login_log',1);
            if (isset($provider_access_token_msg['errcode']) && intval($provider_access_token_msg['errcode']) > 0) {
                $this->assign('errmsg', $provider_access_token_msg['errmsg']?$provider_access_token_msg['errmsg']:'获取凭证失败');
                $this->display('fail');
                exit();
            }
            $suite_access_token = $provider_access_token_msg['access_token'];
            fdump_api(['line:'.__LINE__,'$suite_access_token' => $suite_access_token,'provider_access_token_msg' => $provider_access_token_msg],'qyweixin/QywxAction/auth_login_log',1);
            //获取用户信息
            $result1 = $db_access_token_common_expires->get_login_info($suite_access_token, $auth_code);
            fdump_api(['line:'.__LINE__,'$suite_access_token' => $suite_access_token,'auth_code' => $auth_code,'result1' => $result1],'qyweixin/QywxAction/auth_login_log',1);
            if (isset($result1['errcode']) && intval($result1['errcode']) > 0) {
                $this->assign('errmsg', $result1['errmsg']);
                $this->display('fail');
                exit();
            } else {
                if (isset($result1['corpid'])) {
                    $corpid = $result1['corpid'];
                } else {
                    $auth_corp_info = $result1['corp_info'];
                    $corpid = $auth_corp_info['corpid'];
                }
                $dataSet = [
                    'qyResult' => serialize($result1),
                    'AuthCorpId' => $corpid,
                    'update_time' => time(),
                ];
                if (isset($auth_corp_info) && $auth_corp_info) {
                    $dataSet['authCorpInfo'] = serialize($auth_corp_info);
                }
                $set = $db_village_qywx_guide_about->where($where)->save($dataSet);
                if (!$set) {
                    fdump_api(['记录信息失败line:'.__LINE__,$where, $dataSet,$result1],'qyweixin/QywxAction/auth_login_err_log',1);
                    $this->assign('errmsg', '记录信息失败');
                    $this->display('fail');
                    exit();
                }
                import('@.ORG.qywx.config');
                $config = new config();
                fdump_api(['ajaxGyGuideAbout-line:' . __LINE__, 'time' => microtime(true), 'randomNumber' => $randomNumber, 'type' => 'login'], 'qyweixin/qyGuideAboutLog', 1);
                $info = $config->qyGuideAbout($randomNumber,'login');
                fdump_api(['获取企业微信用户信息line:'.__LINE__,'time' => microtime(true), 'info' => $info, 'randomNumber' => $randomNumber,],'qyweixin/qyGuideAboutLog',1);
                if (!empty($info['login'])){
                    $success_url = $info['login_jump_url'];
                    $this->assign('success_tip', '登录成功，请前往上一个页面确认登录成功');
                    $this->assign('success_url', $success_url);
                    $this->display('success');
                }else{
                    $this->assign('errmsg', '登录失败');
                    $this->display('fail');
                }
            }
        } else {
            $this->assign('errmsg', '不合法的登录请求授权');
            $this->display('fail');
        }
    }

    public function qyGetAuthInfo() {
        $corpid = I('corpid');
        $permanent_code = I('permanent_code');
        if (empty($corpid) || empty($permanent_code)) {
            $arr['errcode'] = 1001;
            $arr['errmsg'] = '参数传递不全';
            return $arr;
        }
        $db_access_token_common_expires = D('Access_token_common_expires');
        $provider_config = [
            'corpid' => C('config.enterprise_wx_corpid'),
            'Secret' => C('config.enterprise_wx_provider_secret'),
            'SuiteID' => C('config.enterprise_wx_provider_suiteid'),
            'Token' => C('config.enterprise_wx_provider_token'),
            'SuiteTicket' => C('config.enterprise_wx_suite_ticket'),
            'EncodingAESKey' => C('config.enterprise_wx_provider_secret'),
        ];
        if (empty($provider_config)) {
            $arr['errcode'] = 1001;
            $arr['errmsg'] = '对应物业服务商相关配置缺失';
            return $arr;
        }
        // 获取 获取第三方应用凭证
        $now = time();
        $suite_access_token_msg = $db_access_token_common_expires->get_suite_access_token(0,$provider_config,$now);
        if (!empty($suite_access_token_msg['errcode'])) {
            return $suite_access_token_msg;
        }
        $suite_access_token = $suite_access_token_msg['access_token'];
        $get_auth_info_info = $db_access_token_common_expires->get_auth_info($corpid, $permanent_code, $suite_access_token);
        if (!empty($get_auth_info_info['errcode'])) {
            return $get_auth_info_info;
        }
        exit(json_encode($get_auth_info_info));
    }

    public function qyGetCorpToken() {
        $corpid = I('corpid');
        $permanent_code = I('permanent_code');
        if (empty($corpid) || empty($permanent_code)) {
            $arr['errcode'] = 1001;
            $arr['errmsg'] = '参数传递不全';
            return $arr;
        }
        $db_access_token_common_expires = D('Access_token_common_expires');
        $provider_config = [
            'corpid' => C('config.enterprise_wx_corpid'),
            'Secret' => C('config.enterprise_wx_provider_secret'),
            'SuiteID' => C('config.enterprise_wx_provider_suiteid'),
            'Token' => C('config.enterprise_wx_provider_token'),
            'SuiteTicket' => C('config.enterprise_wx_suite_ticket'),
            'EncodingAESKey' => C('config.enterprise_wx_provider_secret'),
        ];
        if (empty($provider_config)) {
            $arr['errcode'] = 1001;
            $arr['errmsg'] = '对应物业服务商相关配置缺失';
            return $arr;
        }
        // 获取 获取第三方应用凭证
        $now = time();
        $suite_access_token_msg = $db_access_token_common_expires->get_suite_access_token(0,$provider_config,$now);
        if (!empty($suite_access_token_msg['errcode'])) {
            return $suite_access_token_msg;
        }
        $suite_access_token = $suite_access_token_msg['access_token'];
        $provider_config['permanent_code'] = $permanent_code;
        $corp_token_msg = $db_access_token_common_expires->get_corp_token(0,$suite_access_token,$provider_config,$now);
        if (!empty($corp_token_msg['errcode'])) {
            return array('errcode' => 1, 'errmsg' => $corp_token_msg['errcode']);
        }
        $corp_token = $corp_token_msg['access_token'];
        exit(json_encode(array('errcode' => 0, 'access_token' => $corp_token)));
    }

    public function qyRegisterInfo() {
        $AuthCode = I('auth_code');
        $SuiteId = I('suite_id');
        $where_suite_ticket = [
            'name' => 'enterprise_wx_provider_token'
        ];
        $SuiteTicket = M('Config')->where($where_suite_ticket)->getField('value');
        $SuiteTicket = trim($SuiteTicket);
        $provider_config = [
            'corpid' => C('config.enterprise_wx_corpid'),
            'Secret' => C('config.enterprise_wx_provider_secret'),
            'SuiteID' => C('config.enterprise_wx_provider_suiteid'),
            'Token' => C('config.enterprise_wx_provider_token'),
            'SuiteTicket' => $SuiteTicket,
            'EncodingAESKey' => C('config.enterprise_wx_provider_secret'),
        ];
        $now = time();
        $db_access_token_common_expires = D('Access_token_common_expires');
        // 获取 获取第三方应用凭证
        $suite_access_token_msg = $db_access_token_common_expires->get_suite_access_token(0,$provider_config,$now);
        if (!empty($suite_access_token_msg['errcode'])) {
            return $suite_access_token_msg;
        }
        $suite_access_token = $suite_access_token_msg['access_token'];
        $get_permanent_code_msg = $db_access_token_common_expires->get_permanent_code($suite_access_token, $AuthCode);
        if (!empty($get_permanent_code_msg['errcode'])) {
            return $get_permanent_code_msg;
        }
        exit(json_encode($get_permanent_code_msg));
    }

    public function qyCancelAuth() {
        $AuthCorpId = I('AuthCorpId');
        $SuiteId = I('SuiteId');
        $randomNumber = I('randomNumber');
        $where_about = [
            'randomNumber' => $randomNumber,
        ];
        $info = M('Village_qywx_guide_about')->where($where_about)->find();
        if ($info && (!$info['ServiceCorpId'] || $info['ServiceCorpId']==$SuiteId) && (!$info['AuthCorpId'] || $info['AuthCorpId']==$AuthCorpId)) {
            // 符合条件 清空本地绑定数据
            $where_bind = [
                'corpid' => $AuthCorpId,
                'wx_provider_suiteid' => $SuiteId,
            ];
            $bind_info = M('House_enterprise_wx_bind')->where($where_bind)->find();
            if (!empty($bind_info)) {
                $set = [
                    'corpid' => $AuthCorpId.'_'.date('YmdHis'),
                    'wx_provider_suiteid' => $SuiteId.'_'.date('YmdHis'),
                    'last_time' => time(),
                    'bind_id' => 0
                ];
                M('House_enterprise_wx_bind')->where($where_bind)->save($set);
                //  清空所有关联
                if (0==$bind_info['bind_type']) {
                    $dbVillageQywxAgent = M('Village_qywx_agent');
                    $where_agent = [
                        'property_id' => $bind_info['bind_id']
                    ];
                    $set_agent = [
                        'is_close' => 1,
                        'last_time' => time()
                    ];
                    $dbVillageQywxAgent->where($where_agent)->save($set_agent);
                }
                $aboutSet = [
                    'is_del' => 1,
                    'update_time' => time()
                ];
                M('Village_qywx_guide_about')->where($where_about)->save($aboutSet);
            }
        }
        $arr['errcode'] = 0;
        $arr['errmsg'] = '取消绑定成功';
        return $arr;
    }
}