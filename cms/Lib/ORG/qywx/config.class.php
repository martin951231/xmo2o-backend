<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/7/26 17:02
 */

class config{

    public $post_url = 'https://shequ-demo.fastwhale.com.cn/';


    public $http;
    /**
     * 构造函数
     *
     * @param   array   配置数组 : $config = array('hostname'=>'','username'=>'','password'=>'','port'=>''...);
     */
    public function __construct($config = array()) {

    }

    public function qyRegister($property_id=0,$qyRegisterId=0,$business_type='property') {
//        $service_provider_common_set = C('config.service_provider_common_set');
        $service_provider_common_set = 1;
        if (1==$service_provider_common_set) {
            $provider_secret = C('config.service_provider_secret');
            $corpid = C('config.enterprise_wx_corpid');
            $template_id = C('config.service_template_id');
            if (empty($corpid)) {
                fdump_api(['qyRegister-line请配置企业微信ID:'.__LINE__, $property_id,$qyRegisterId,$business_type],'qyweixinConfig/qyRegisterErrLog',1);
                return array('errcode' => 1001, 'errmsg' => '请配置企业微信ID');
            }
            if (empty($provider_secret)) {
                fdump_api(['qyRegister-line请配置企业ProviderSecret:'.__LINE__, $property_id,$qyRegisterId,$business_type,$corpid],'qyweixinConfig/qyRegisterErrLog',1);
                return array('errcode' => 1001, 'errmsg' => '请前往“服务商管理端-应用管理-通用开发参数”中复制ProviderSecret配置到企业微信配置中');
            }
            if (empty($template_id)) {
                fdump_api(['qyRegister-line请配置企业推广码:'.__LINE__, $property_id,$qyRegisterId,$business_type,$provider_secret],'qyweixinConfig/qyRegisterErrLog',1);
                return array('errcode' => 0, 'errmsg' => '请前往“服务商管理端-应用管理-推广二维码”，创建的推广码详情复制到企业微信配置中');
            }
            $provider_access_msg = D('Access_token_common_expires')->get_provider_token($corpid, $provider_secret);
            if (!$provider_access_msg['errcode']) {
                $provider_access_token = $provider_access_msg['access_token'];
                $db_village_qywx_guide_about = M('Village_qywx_guide_about');
                if ($property_id) {
                    $where = [
                        'is_del' => 0,
                        'from' => 0,
                        'business_type' => $business_type,
                        'business_id' => $property_id,
                        'type' => 'qyRegisterSelf',
                        'wx_bind_id' => 0
                    ];
                } elseif ($qyRegisterId) {
                    $property_id = 0;
                    $where = [
                        'is_del' => 0,
                        'from' => 0,
                        'business_type' => $business_type,
                        'randomNumber' => $qyRegisterId,
                        'type' => 'qyRegisterSelf',
                        'wx_bind_id' => 0
                    ];
                }
                $qywx_guide_about = $db_village_qywx_guide_about->where($where)->order('id DESC')->find();
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
                    if ($corpid && !$qywx_guide_about['ServiceCorpId']) {
                        $guide_about_data['ServiceCorpId'] = $corpid;
                    }
                    if (!empty($guide_about_data)) {
                        $guide_about_data['update_time'] = time();
                        $save_id = $db_village_qywx_guide_about->where($where)->save($guide_about_data);
                        if ($save_id!==false) {
                        } else {
                            fdump_api(['qyRegister-line添加记录失败:'.__LINE__, $property_id,$qyRegisterId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixinConfig/qyRegisterErrLog',1);
                            return array('errcode' => 0, 'errmsg' => '添加记录失败');
                        }
                    }
                    $add_id = $qywx_guide_about['id'];
                } else {
                    $type = 'qyRegisterSelf';
                    $guide_about_data = [
                        'is_del' => 0,
                        'business_type' => $business_type,
                        'type' => $type,
                        'from' => 0,
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
                    }
                    if ($corpid) {
                        $guide_about_data['ServiceCorpId'] = $corpid;
                    }
                    $add_id = $db_village_qywx_guide_about->add($guide_about_data);
                    if (!$add_id) {
                        fdump_api(['qyRegister-line添加记录失败:'.__LINE__, $property_id,$qyRegisterId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixinConfig/qyRegisterErrLog',1);
                        return array('errcode' => 0, 'errmsg' => '添加记录失败');
                    }
                }
                $state = "{$qyRegisterId}";
                $register_code_msg = D('Access_token_common_expires')->get_register_code($provider_access_token, $template_id,$state);
                if ($register_code_msg['errcode']) {
                    fdump_api(['qyRegister-line错误:'.__LINE__, $property_id,$qyRegisterId,$business_type,$provider_access_token, $template_id,$state,$register_code_msg],'qyweixinConfig/qyRegisterErrLog',1);
                    return $register_code_msg;
                }
                $register_code = $register_code_msg['register_code'];
                fdump_api(['register_code-line:'.__LINE__, $property_id,$qyRegisterId,$business_type,$register_code,$register_code_msg],'qyweixinConfig/qyRegisterLog',1);
            } else {
                fdump_api(['qyRegister-line错误:'.__LINE__, $property_id,$qyRegisterId,$business_type,$corpid, $provider_secret,$provider_access_msg],'qyweixinConfig/qyRegisterErrLog',1);
                return $provider_access_msg;
            }
            $trusted_ip = $_SERVER['SERVER_ADDR'];
            $siteIp = GetHostByName($_SERVER['SERVER_NAME']);
            if ($siteIp=='127.0.0.1' && $trusted_ip) {
                $siteIp = $trusted_ip;
            }
            $site_url = C('config.site_url');
            $domainName = str_replace('http://','',$site_url);
            $domainName = str_replace('https://','',$domainName);
            $ip = get_client_ip(0);
            $data = array(
                'site_url' => C('config.site_url'),
                'siteIp' => $siteIp,
                'domainName' => $domainName,
                'ip' => $ip,
                'qyRegisterId' => $qyRegisterId,
                'property_id' => $property_id,
            );
            $qyRegisterUrl = '';
            $SuiteID = C('config.enterprise_wx_provider_suiteid');
            $provider_config = [
                'corpid' => $corpid,
                'SuiteID' => $SuiteID,
                'provider_secret' => $provider_secret,
                'template_id' => $template_id,
                'provider_access_token' => $provider_access_token,
            ];
            $arr = [
                'errcode' => 0,
                'errmsg' => '',
            ];
            $arr['provider_config'] = $provider_config;
        } else {
            if (!$this->http) {
                import('ORG.Net.Http');
                $this->http = new Http();
            }
            $trusted_ip = $_SERVER['SERVER_ADDR'];
            $siteIp = GetHostByName($_SERVER['SERVER_NAME']);
            if ($siteIp=='127.0.0.1' && $trusted_ip) {
                $siteIp = $trusted_ip;
            }
            $site_url = C('config.site_url');
            $domainName = str_replace('http://','',$site_url);
            $domainName = str_replace('https://','',$domainName);
            $ip = get_client_ip(0);
            $data = array(
                'site_url' => C('config.site_url'),
                'siteIp' => $siteIp,
                'domainName' => $domainName,
                'ip' => $ip,
                'qyRegisterId' => $qyRegisterId,
                'property_id' => $property_id,
            );
            $qyRegisterUrl = $this->post_url ."index.php?g=Index&c=Qywx&a=qyRegister";
            $register_code_msg = $this->http->curlQyWxPost($qyRegisterUrl, $data);
            if ($register_code_msg['url']) {
                $qyRegisterId = $register_code_msg['qyRegisterId'];
                $db_village_qywx_guide_about = M('Village_qywx_guide_about');
                // 成功返回了 连接 做记录
                if ($property_id) {
                    $where = [
                        'is_del' => 0,
                        'from' => 0,
                        'business_type' => $business_type,
                        'business_id' => $property_id,
                        'type' => 'qyRegisterPost',
                        'wx_bind_id' => 0
                    ];
                } elseif ($qyRegisterId) {
                    $property_id = 0;
                    $where = [
                        'is_del' => 0,
                        'from' => 0,
                        'business_type' => $business_type,
                        'randomNumber' => $qyRegisterId,
                        'type' => 'qyRegisterPost',
                        'wx_bind_id' => 0
                    ];
                }
                $qywx_guide_about = $db_village_qywx_guide_about->order('id DESC')->where($where)->find();
                fdump($qywx_guide_about,'$qywx_guide_about');
                fdump($db_village_qywx_guide_about->_sql(),'$qywx_guide_about',1);

                if ($qywx_guide_about) {
                    $guide_about_data = [];
                    if ($qyRegisterId && !$qywx_guide_about['randomNumber']) {
                        $guide_about_data['randomNumber'] = $qyRegisterId;
                    }
                    if ($property_id && !$qywx_guide_about['business_id']) {
                        $guide_about_data['business_id'] = $property_id;
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
                    $param = serialize($data);
                    if ($param && $param!=$qywx_guide_about['param']) {
                        $guide_about_data['param'] = $param;
                    }
                    if ($qyRegisterUrl && $qyRegisterUrl!=$qywx_guide_about['param_url']) {
                        $guide_about_data['param_url'] = $qyRegisterUrl;
                    }
                    $param_result = serialize($register_code_msg);
                    if ($param_result && $param_result!=$qywx_guide_about['param_result']) {
                        $guide_about_data['param_result'] = $param_result;
                    }
                    if ($register_code_msg['provider_config']['corpid'] && $register_code_msg['provider_config']['corpid']!=$qywx_guide_about['ServiceCorpId']) {
                        $guide_about_data['ServiceCorpId'] = $register_code_msg['provider_config']['corpid'];
                    }
                    if (!empty($guide_about_data)) {
                        $guide_about_data['update_time'] = time();
                        $save_id = $db_village_qywx_guide_about->where($where)->save($guide_about_data);
                        if ($save_id!==false) {
                        } else {
                            fdump_api(['qyRegister-linePost添加记录失败:'.__LINE__, $property_id,$qyRegisterId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixinConfig/qyRegisterErrLog',1);
                            return array('errcode' => 0, 'errmsg' => '添加记录失败');
                        }
                    }
                } else {
                    $type = 'qyRegisterPost';
                    $guide_about_data = [
                        'is_del' => 0,
                        'business_type' => $business_type,
                        'type' => $type,
                        'from' => 0,
                        'business_id' => $property_id,
                        'site_url' => $site_url?$site_url:'',
                        'siteIp' => $siteIp?$siteIp:'',
                        'ip' => $ip?$ip:'',
                        'domainName' => $domainName?$domainName:'',
                        'add_time' => time()
                    ];
                    if ($qyRegisterId) {
                        $guide_about_data['randomNumber'] = $qyRegisterId;
                    }
                    if ($property_id) {
                        $guide_about_data['business_id'] = $property_id;
                    }
                    if ($register_code_msg['provider_config']['corpid']) {
                        $guide_about_data['ServiceCorpId'] = $register_code_msg['provider_config']['corpid'];
                    }
                    $guide_about_data['param'] = serialize($data);
                    $guide_about_data['param_url'] = $qyRegisterUrl;
                    $guide_about_data['param_result'] = serialize($register_code_msg);
                    $add_id = $db_village_qywx_guide_about->add($guide_about_data);
                    if (!$add_id) {
                        fdump_api(['qyRegister-linePost添加记录失败:'.__LINE__, $where,$property_id,$qyRegisterId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixinConfig/qyRegisterErrLog',1);
                        return array('errcode' => 0, 'errmsg' => '添加记录失败');
                    }
                }
            } else {
                fdump_api(['qyRegister-linePost请求返回异常:'.__LINE__, $property_id,$qyRegisterId,$business_type,$qyRegisterUrl,$data,$register_code_msg],'qyweixinConfig/qyRegisterErrLog',1);
            }
            fdump_api(['qyRegister-linePost请求返回:'.__LINE__, $property_id,$qyRegisterId,$business_type,$qyRegisterUrl,$data,$register_code_msg],'qyweixinConfig/qyRegisterLog',1);
            unset($register_code_msg['provider_config']);
            return $register_code_msg;
        }
        // 注册按钮链接的生成和嵌入
        $url = "https://open.work.weixin.qq.com/3rdservice/wework/register?register_code=$register_code";
        $arr['url'] = $url;
        $arr['qyRegisterId'] = $qyRegisterId;
        $arr['register_code'] = $register_code;
        if ($add_id) {
            $guide_about_data = [];
            $guide_about_data['param'] = serialize($data);
            $guide_about_data['param_url'] = $qyRegisterUrl;
            $guide_about_data['param_result'] = serialize($arr);
            $guide_about_data['update_time'] = time();
            $where = [
                'id' => $add_id
            ];
            $save_id = $db_village_qywx_guide_about->where($where)->save($guide_about_data);
            if ($save_id!==false) {
            } else {
                fdump_api(['qyRegister-line添加记录失败:'.__LINE__, $property_id,$qyRegisterId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixinConfig/qyRegisterErrLog',1);
                return array('errcode' => 0, 'errmsg' => '添加记录失败');
            }
        }
        unset($arr['register_code']);
        unset($arr['provider_config']);
        fdump_api(['qyRegister-line:'.__LINE__, $property_id,$qyRegisterId,$business_type,$guide_about_data,$arr],'qyweixinConfig/qyRegisterLog',1);
        return $arr;
    }

    public function qyInstall($property_id=0,$qyInstallId=0,$business_type='property') {
//        $service_provider_common_set = C('config.service_provider_common_set');
        $service_provider_common_set = 1;
        $site_url = C('config.site_url');
        if (1==$service_provider_common_set) {
            $where_suite_ticket = [
                'name' => 'enterprise_wx_suite_ticket'
            ];
            $SuiteTicket = M('Config')->where($where_suite_ticket)->getField('value');
            $SuiteTicket = trim($SuiteTicket);
            $where_enterprise_wx_corpid = [
                'name' => 'enterprise_wx_corpid'
            ];
            $corpid = M('Config')->where($where_enterprise_wx_corpid)->getField('value');
            $corpid = trim($corpid);
            $where_enterprise_wx_provider_secret = [
                'name' => 'enterprise_wx_provider_secret'
            ];
            $Secret = M('Config')->where($where_enterprise_wx_provider_secret)->getField('value');
            $Secret = trim($Secret);
            $where_enterprise_wx_provider_suiteid = [
                'name' => 'enterprise_wx_provider_suiteid'
            ];
            $SuiteID = M('Config')->where($where_enterprise_wx_provider_suiteid)->getField('value');
            $SuiteID = trim($SuiteID);
            $where_enterprise_wx_provider_token = [
                'name' => 'enterprise_wx_provider_token'
            ];
            $Token = M('Config')->where($where_enterprise_wx_provider_token)->getField('value');
            $Token = trim($Token);
            if (empty($corpid)) {
                fdump_api(['qyInstall-line请配置企业微信ID:'.__LINE__, $property_id,$qyInstallId,$business_type],'qyweixinConfig/qyInstallErrLog',1);
                return array('errcode' => 1001, 'errmsg' => '请配置企业微信ID');
            }
            if (empty($Secret)) {
                fdump_api(['qyInstall-line请配置企业服务商应用Secret:'.__LINE__, $property_id,$qyInstallId,$business_type,$corpid],'qyweixinConfig/qyInstallErrLog',1);
                return array('errcode' => 1001, 'errmsg' => '请前往“服务商管理端-应用管理-网页应用-对应创建的普通应用”中复制Secret到企业微信配置中');
            }
            if (empty($SuiteID)) {
                fdump_api(['qyInstall-line请配置企业服务商应用SuiteID:'.__LINE__, $property_id,$qyInstallId,$business_type,$corpid,$Secret],'qyweixinConfig/qyInstallErrLog',1);
                return array('errcode' => 0, 'errmsg' => '请前往“服务商管理端-应用管理-网页应用-对应创建的普通应用”中复制SuiteID到企业微信配置中');
            }
            if (empty($Token)) {
                fdump_api(['qyInstall-line请配置企业服务商应用Token:'.__LINE__, $property_id,$qyInstallId,$business_type,$corpid,$SuiteID,$Secret],'qyweixinConfig/qyInstallErrLog',1);
                return array('errcode' => 0, 'errmsg' => '请前往“服务商管理端-应用管理-网页应用-对应创建的普通应用”中复制Token到企业微信配置中');
            }
            if (empty($SuiteTicket)) {
                fdump_api(['qyInstall-line请配置企业服务商应用SuiteTicket:'.__LINE__, $property_id,$qyInstallId,$business_type,$corpid,$SuiteID,$Secret,$Token],'qyweixinConfig/qyInstallErrLog',1);
                return array('errcode' => 0, 'errmsg' => '请前往“服务商管理端-应用管理-网页应用-对应创建的普通应用”中正确完成回调配置');
            }
            $provider_config = [
                'corpid' => $corpid,
                'Secret' => $Secret,
                'SuiteID' => $SuiteID,
                'Token' => $Token,
                'SuiteTicket' => $SuiteTicket,
            ];
            $now = time();
            $db_access_token_common_expires = D('Access_token_common_expires');
            // 获取 获取第三方应用凭证
            $suite_access_token_msg = $db_access_token_common_expires->get_suite_access_token($property_id,$provider_config,$now);
            if (!empty($suite_access_token_msg['errcode'])) {
                fdump_api(['qyInstall-line获取第三方应用凭证失败:'.__LINE__, $property_id,$qyInstallId,$business_type,$property_id,$provider_config,$now,$suite_access_token_msg],'qyweixinConfig/qyInstallErrLog',1);
                return $suite_access_token_msg;
            }
            $suite_access_token = $suite_access_token_msg['access_token'];
            // 获取预授权码
            $pre_auth_code_msg = $db_access_token_common_expires->get_pre_auth_code($property_id,$suite_access_token,$provider_config,$now);
            if (!empty($pre_auth_code_msg['errcode'])) {
                fdump_api(['qyInstall-line获取预授权码失败:'.__LINE__, $property_id,$qyInstallId,$business_type,$property_id,$suite_access_token,$now,$pre_auth_code_msg],'qyweixinConfig/qyInstallErrLog',1);
                return $pre_auth_code_msg;
            }
            $pre_auth_code = $pre_auth_code_msg['access_token'];
            $where_pre_auth_code = [
                'type' =>'pre_auth_code',
                'access_id' =>$corpid.'_preauthcode_'.$SuiteID,
            ];
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
            if (!empty($set_session_info['errcode'])) {
                fdump_api(['qyInstall-line设置授权配置失败:'.__LINE__, $property_id,$qyInstallId,$business_type,$suite_access_token, $pre_auth_code,$set_session_info],'qyweixinConfig/qyInstallErrLog',1);
                return $set_session_info;
            }

            $db_village_qywx_guide_about = M('Village_qywx_guide_about');
            if ($property_id) {
                $where = [
                    'is_del' => 0,
                    'from' => 0,
                    'business_type' => $business_type,
                    'business_id' => $property_id,
                    'type' => 'qyInstallSelf',
                    'wx_bind_id' => 0
                ];
            } elseif ($qyInstallId) {
                $property_id = 0;
                $where = [
                    'is_del' => 0,
                    'from' => 0,
                    'business_type' => $business_type,
                    'randomNumber' => $qyInstallId,
                    'type' => 'qyInstallSelf',
                    'wx_bind_id' => 0
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
                if ($corpid && !$qywx_guide_about['ServiceCorpId']) {
                    $guide_about_data['ServiceCorpId'] = $corpid;
                }
                if (!empty($guide_about_data)) {
                    $guide_about_data['update_time'] = time();
                    $save_id = $db_village_qywx_guide_about->where($where)->save($guide_about_data);
                    if ($save_id!==false) {
                    } else {
                        fdump_api(['qyInstall-lineSelf添加记录失败:'.__LINE__, $property_id,$qyInstallId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixinConfig/qyInstallErrLog',1);
                        return array('errcode' => 0, 'errmsg' => '添加记录失败');
                    }
                }
                $add_id = $qywx_guide_about['id'];
            } else {
                $type = 'qyInstallSelf';
                $guide_about_data = [
                    'is_del' => 0,
                    'business_type' => $business_type,
                    'type' => $type,
                    'from' => 0,
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
                $add_id = $db_village_qywx_guide_about->add($guide_about_data);
                if (empty($add_id)) {
                    fdump_api(['qyInstall-lineSelf添加记录失败:'.__LINE__, $property_id,$qyInstallId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixinConfig/qyInstallErrLog',1);
                    return array('errcode' => 0, 'errmsg' => '添加记录失败');
                }
            }
            fdump_api(['qyInstall随机数-line:'.__LINE__, $qyInstallId,$qywx_guide_about],'qyweixinConfig/qyInstallLog',1);
            $state = "{$qyInstallId}";
            $redirect_url = $site_url . "/index.php?g=Index&c=Qywx&a=auth_back&suite_id={$SuiteID}";
            $redirect_url = urlencode($redirect_url);
            // 第三方服务商在自己的网站中放置“企业微信应用授权”的入口，引导企业微信管理员进入应用授权页。授权页网址为:
            // https://open.work.weixin.qq.com/3rdapp/install?suite_id=SUITE_ID&pre_auth_code=PRE_AUTH_CODE&redirect_uri=REDIRECT_URI&state=STATE
            $bind_url ="https://open.work.weixin.qq.com/3rdapp/install?suite_id={$SuiteID}&pre_auth_code={$pre_auth_code}&redirect_uri={$redirect_url}&state={$state}";
            $arr = [
                'errcode' => 0,
                'errmsg' => '',
            ];
            $arr['url'] = $bind_url;
            $arr['qyInstallId'] = $qyInstallId;
            $arr['provider_config'] = $provider_config;
            if ($add_id) {
                $trusted_ip = $_SERVER['SERVER_ADDR'];
                $siteIp = GetHostByName($_SERVER['SERVER_NAME']);
                if ($siteIp=='127.0.0.1' && $trusted_ip) {
                    $siteIp = $trusted_ip;
                }
                $site_url = C('config.site_url');
                $domainName = str_replace('http://','',$site_url);
                $domainName = str_replace('https://','',$domainName);
                $ip = get_client_ip(0);
                $data = array(
                    'site_url' => C('config.site_url'),
                    'siteIp' => $siteIp,
                    'domainName' => $domainName,
                    'ip' => $ip,
                    'qyInstallId' => $qyInstallId,
                    'property_id' => $property_id,
                );
                $guide_about_data = [];
                $guide_about_data['param'] = serialize($data);
                $guide_about_data['param_url'] = '';
                $guide_about_data['param_result'] = serialize($arr);
                $guide_about_data['update_time'] = time();
                $where = [
                    'id' => $add_id
                ];
                $save_id = $db_village_qywx_guide_about->where($where)->save($guide_about_data);
                if ($save_id!==false) {
                } else {
                    fdump_api(['qyInstall-lineSelf添加记录失败:'.__LINE__, $property_id,$qyInstallId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixinConfig/qyRegisterErrLog',1);
                    return array('errcode' => 0, 'errmsg' => '添加记录失败');
                }
            }
            unset($arr['provider_config']);
            fdump_api(['qyInstall-line:'.__LINE__, $property_id,$qyInstallId,$business_type,$guide_about_data,$arr],'qyweixinConfig/qyInstallLog',1);
            return $arr;
        } else {
            if (!$this->http) {
                import('ORG.Net.Http');
                $this->http = new Http();
            }
            $trusted_ip = $_SERVER['SERVER_ADDR'];
            $siteIp = GetHostByName($_SERVER['SERVER_NAME']);
            if ($siteIp=='127.0.0.1' && $trusted_ip) {
                $siteIp = $trusted_ip;
            }
            $site_url = C('config.site_url');
            $domainName = str_replace('http://','',$site_url);
            $domainName = str_replace('https://','',$domainName);
            $ip = get_client_ip(0);
            $data = array(
                'site_url' => C('config.site_url'),
                'siteIp' => $siteIp,
                'domainName' => $domainName,
                'ip' => $ip,
                'qyInstallId' => $qyInstallId,
                'property_id' => $property_id,
            );
            $qyInstallUrl = $this->post_url ."index.php?g=Index&c=Qywx&a=qyInstall";
            $install_msg = $this->http->curlQyWxPost($qyInstallUrl, $data);
            if ($install_msg['url']) {
                $qyInstallId = $install_msg['qyInstallId'];
                $db_village_qywx_guide_about = M('Village_qywx_guide_about');
                // 成功返回了 连接 做记录
                if ($property_id) {
                    $where = [
                        'is_del' => 0,
                        'from' => 0,
                        'business_type' => $business_type,
                        'business_id' => $property_id,
                        'type' => 'qyInstallPost',
                        'wx_bind_id' => 0
                    ];
                } elseif ($qyInstallId) {
                    $property_id = 0;
                    $where = [
                        'is_del' => 0,
                        'from' => 0,
                        'business_type' => $business_type,
                        'randomNumber' => $qyInstallId,
                        'type' => 'qyInstallPost',
                        'wx_bind_id' => 0
                    ];
                }
                $qywx_guide_about = $db_village_qywx_guide_about->where($where)->order('id DESC')->find();

                if ($qywx_guide_about) {
                    $guide_about_data = [];
                    if ($qyInstallId && !$qywx_guide_about['randomNumber']) {
                        $guide_about_data['randomNumber'] = $qyInstallId;
                    }
                    if ($property_id && !$qywx_guide_about['business_id']) {
                        $guide_about_data['business_id'] = $property_id;
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
                    $param = serialize($data);
                    if ($param && $param!=$qywx_guide_about['param']) {
                        $guide_about_data['param'] = $param;
                    }
                    if ($qyInstallUrl && $qyInstallUrl!=$qywx_guide_about['param_url']) {
                        $guide_about_data['param_url'] = $qyInstallUrl;
                    }
                    $param_result = serialize($install_msg);
                    if ($param_result && $param_result!=$qywx_guide_about['param_result']) {
                        $guide_about_data['param_result'] = $param_result;
                    }
                    if ($install_msg['provider_config']['corpid'] && $install_msg['provider_config']['corpid']!=$qywx_guide_about['ServiceCorpId']) {
                        $guide_about_data['ServiceCorpId'] = $install_msg['provider_config']['corpid'];
                    }
                    if (!empty($guide_about_data)) {
                        $guide_about_data['update_time'] = time();
                        $save_id = $db_village_qywx_guide_about->where($where)->save($guide_about_data);
                        if ($save_id!==false) {
                        } else {
                            fdump_api(['qyRegister-linePost添加记录失败:'.__LINE__, $property_id,$qyInstallId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixinConfig/qyInstallIdErrLog',1);
                            return array('errcode' => 0, 'errmsg' => '添加记录失败');
                        }
                    }
                } else {
                    $type = 'qyInstallPost';
                    $guide_about_data = [
                        'is_del' => 0,
                        'business_type' => $business_type,
                        'type' => $type,
                        'from' => 0,
                        'business_id' => $property_id,
                        'site_url' => $site_url?$site_url:'',
                        'siteIp' => $siteIp?$siteIp:'',
                        'ip' => $ip?$ip:'',
                        'domainName' => $domainName?$domainName:'',
                        'add_time' => time()
                    ];
                    if ($qyInstallId) {
                        $guide_about_data['randomNumber'] = $qyInstallId;
                    }
                    if ($property_id) {
                        $guide_about_data['business_id'] = $property_id;
                    }
                    if ($install_msg['provider_config']['corpid']) {
                        $guide_about_data['ServiceCorpId'] = $install_msg['provider_config']['corpid'];
                    }
                    $guide_about_data['param'] = serialize($data);
                    $guide_about_data['param_url'] = $qyInstallUrl;
                    $guide_about_data['param_result'] = serialize($install_msg);
                    $add_id = $db_village_qywx_guide_about->add($guide_about_data);
                    if (!$add_id) {
                        fdump_api(['qyRegister-linePost添加记录失败:'.__LINE__, $property_id,$qyInstallId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixinConfig/qyInstallIdErrLog',1);
                        return array('errcode' => 0, 'errmsg' => '添加记录失败');
                    }
                }
            } else {
                fdump_api(['qyRegister-linePost请求返回异常:'.__LINE__, $property_id,$qyInstallId,$business_type,$qyInstallUrl,$data,$install_msg],'qyweixinConfig/qyInstallIdErrLog',1);
            }
            unset($install_msg['provider_config']);
            return $install_msg;
        }
    }

    public function qyLogin($property_id=0,$qyLoginId=0,$business_type='property') {
//        $service_provider_common_set = C('config.service_provider_common_set');
        $service_provider_common_set = 1;
        $site_url = C('config.site_url');
        if (1==$service_provider_common_set) {
            $where_enterprise_wx_corpid = [
                'name' => 'enterprise_wx_corpid'
            ];
            $corpid = M('Config')->where($where_enterprise_wx_corpid)->getField('value');
            $corpid = trim($corpid);
            if (empty($corpid)) {
                fdump_api(['qyLogin-line请配置企业微信ID:'.__LINE__, $property_id,$qyLoginId,$business_type],'qyweixinConfig/qyLoginErrLog',1);
                return array('errcode' => 1001, 'errmsg' => '请配置企业微信ID');
            }

            $db_village_qywx_guide_about = M('Village_qywx_guide_about');
            if ($property_id) {
                $where = [
                    'is_del' => 0,
                    'from' => 0,
                    'business_type' => $business_type,
                    'business_id' => $property_id,
                    'type' => 'qyLoginSelf',
                    'wx_bind_id' => 0
                ];
            } elseif ($qyLoginId) {
                $property_id = 0;
                $where = [
                    'is_del' => 0,
                    'business_type' => $business_type,
                    'randomNumber' => $qyLoginId,
                    'wx_bind_id' => 0
                ];
            }
            if (empty($where)) {
                $qywx_guide_about = [];
            } else {
                $qywx_guide_about = $db_village_qywx_guide_about->where($where)->order('id DESC')->find();
                fdump_api(['qyLogin-lineSelf添加记录失败:'.__LINE__, $property_id,$qyLoginId,$where,$db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixinConfig/qyInstallErrLog',1);

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
                    $qyLoginId = $guide_about_data['randomNumber'];
                }

                if ($property_id && !$qywx_guide_about['business_id']) {
                    $guide_about_data['business_id'] = $property_id;
                }
                if ($corpid && !$qywx_guide_about['ServiceCorpId']) {
                    $guide_about_data['ServiceCorpId'] = $corpid;
                }
                if (!empty($guide_about_data)) {
                    $guide_about_data['update_time'] = time();
                    $save_id = $db_village_qywx_guide_about->where($where)->save($guide_about_data);
                    if ($save_id!==false) {
                    } else {
                        fdump_api(['qyLogin-lineSelf添加记录失败:'.__LINE__, $property_id,$qyLoginId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixinConfig/qyInstallErrLog',1);
                        return array('errcode' => 0, 'errmsg' => '添加记录失败');
                    }
                }
                $add_id = $qywx_guide_about['id'];
            } else {
                $type = 'qyLoginSelf';
                $guide_about_data = [
                    'is_del' => 0,
                    'business_type' => $business_type,
                    'type' => $type,
                    'from' => 0,
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
                $add_id = $db_village_qywx_guide_about->add($guide_about_data);
                if (empty($add_id)) {
                    fdump_api(['qyLogin-lineSelf添加记录失败:'.__LINE__, $property_id,$qyLoginId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixinConfig/qyInstallErrLog',1);
                    return array('errcode' => 0, 'errmsg' => '添加记录失败');
                }
            }
            $state = "{$qyLoginId}";
            $redirect_url = $site_url . "/index.php?g=Index&c=Qywx&a=auth_login";
            $redirect_url = urlencode($redirect_url);
            // 第三方服务商在自己的网站中放置“企业微信应用授权”的入口，引导企业微信管理员进入应用授权页。授权页网址为:
            // https://open.work.weixin.qq.com/wwopen/sso/3rd_qrConnect?appid=ww100000a5f2191&redirect_uri=http%3A%2F%2Fwww.oa.com&state=web_login@gyoss9&usertype=admin
            $bind_url ="https://open.work.weixin.qq.com/wwopen/sso/3rd_qrConnect?appid={$corpid}&redirect_uri={$redirect_url}&state={$state}&usertype=admin";
            $arr = [
                'errcode' => 0,
                'errmsg' => '',
            ];
            $arr['url'] = $bind_url;
            $provider_config = [
                'corpid' => $corpid,
            ];
            $arr['provider_config'] = $provider_config;
            if ($add_id) {
                $trusted_ip = $_SERVER['SERVER_ADDR'];
                $siteIp = GetHostByName($_SERVER['SERVER_NAME']);
                if ($siteIp=='127.0.0.1' && $trusted_ip) {
                    $siteIp = $trusted_ip;
                }
                $site_url = C('config.site_url');
                $domainName = str_replace('http://','',$site_url);
                $domainName = str_replace('https://','',$domainName);
                $ip = get_client_ip(0);
                $data = array(
                    'site_url' => C('config.site_url'),
                    'siteIp' => $siteIp,
                    'domainName' => $domainName,
                    'ip' => $ip,
                    'qyLoginId' => $qyLoginId,
                    'property_id' => $property_id,
                );
                $guide_about_data = [];
                $guide_about_data['param'] = serialize($data);
                $guide_about_data['param_url'] = '';
                $guide_about_data['param_result'] = serialize($arr);
                $guide_about_data['update_time'] = time();
                $where = [
                    'id' => $add_id
                ];
                $save_id = $db_village_qywx_guide_about->where($where)->save($guide_about_data);
                if ($save_id!==false) {
                } else {
                    fdump_api(['qyLogin-lineSelf添加记录失败:'.__LINE__, $property_id,$qyLoginId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixinConfig/qyRegisterErrLog',1);
                    return array('errcode' => 0, 'errmsg' => '添加记录失败');
                }
            }
            unset($arr['provider_config']);
            return $arr;
        } else {
            if (!$this->http) {
                import('ORG.Net.Http');
                $this->http = new Http();
            }
            $trusted_ip = $_SERVER['SERVER_ADDR'];
            $siteIp = GetHostByName($_SERVER['SERVER_NAME']);
            if ($siteIp=='127.0.0.1' && $trusted_ip) {
                $siteIp = $trusted_ip;
            }
            $site_url = C('config.site_url');
            $domainName = str_replace('http://','',$site_url);
            $domainName = str_replace('https://','',$domainName);
            $ip = get_client_ip(0);
            $data = array(
                'site_url' => C('config.site_url'),
                'siteIp' => $siteIp,
                'domainName' => $domainName,
                'ip' => $ip,
                'qyLoginId' => $qyLoginId,
                'property_id' => $property_id,
            );
            $qyLoginUrl = $this->post_url ."index.php?g=Index&c=Qywx&a=qyLogin";
            $login_msg = $this->http->curlQyWxPost($qyLoginUrl, $data);
            if ($login_msg['url']) {
                $qyLoginId = $login_msg['qyLoginId'];
                $db_village_qywx_guide_about = M('Village_qywx_guide_about');
                // 成功返回了 连接 做记录
                if ($property_id) {
                    $where = [
                        'is_del' => 0,
                        'from' => 0,
                        'business_type' => $business_type,
                        'business_id' => $property_id,
                        'type' => 'qyLoginPost',
                        'wx_bind_id' => 0
                    ];
                } elseif ($qyLoginId) {
                    $property_id = 0;
                    $where = [
                        'is_del' => 0,
                        'from' => 0,
                        'business_type' => $business_type,
                        'randomNumber' => $qyLoginId,
                        'type' => 'qyLoginPost',
                        'wx_bind_id' => 0
                    ];
                }
                $qywx_guide_about = $db_village_qywx_guide_about->where($where)->order('id DESC')->find();

                if ($qywx_guide_about) {
                    $guide_about_data = [];
                    if ($qyLoginId && !$qywx_guide_about['randomNumber']) {
                        $guide_about_data['randomNumber'] = $qyLoginId;
                    }
                    if ($property_id && !$qywx_guide_about['business_id']) {
                        $guide_about_data['business_id'] = $property_id;
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
                    $param = serialize($data);
                    if ($param && $param!=$qywx_guide_about['param']) {
                        $guide_about_data['param'] = $param;
                    }
                    if ($qyLoginUrl && $qyLoginUrl!=$qywx_guide_about['param_url']) {
                        $guide_about_data['param_url'] = $qyLoginUrl;
                    }
                    $param_result = serialize($login_msg);
                    if ($param_result && $param_result!=$qywx_guide_about['param_result']) {
                        $guide_about_data['param_result'] = $param_result;
                    }
                    if ($login_msg['provider_config']['corpid'] && $login_msg['provider_config']['corpid']!=$qywx_guide_about['ServiceCorpId']) {
                        $guide_about_data['ServiceCorpId'] = $login_msg['provider_config']['corpid'];
                    }
                    if (!empty($guide_about_data)) {
                        $guide_about_data['update_time'] = time();
                        $save_id = $db_village_qywx_guide_about->where($where)->save($guide_about_data);
                        if ($save_id!==false) {
                        } else {
                            fdump_api(['qyLogin-linePost添加记录失败:'.__LINE__, $property_id,$qyLoginId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixinConfig/qyInstallIdErrLog',1);
                            return array('errcode' => 0, 'errmsg' => '添加记录失败');
                        }
                    }
                } else {
                    $type = 'qyLoginPost';
                    $guide_about_data = [
                        'is_del' => 0,
                        'business_type' => $business_type,
                        'type' => $type,
                        'from' => 0,
                        'business_id' => $property_id,
                        'site_url' => $site_url?$site_url:'',
                        'siteIp' => $siteIp?$siteIp:'',
                        'ip' => $ip?$ip:'',
                        'domainName' => $domainName?$domainName:'',
                        'add_time' => time()
                    ];
                    if ($qyLoginId) {
                        $guide_about_data['randomNumber'] = $qyLoginId;
                    }
                    if ($property_id) {
                        $guide_about_data['business_id'] = $property_id;
                    }
                    if ($login_msg['provider_config']['corpid']) {
                        $guide_about_data['ServiceCorpId'] = $login_msg['provider_config']['corpid'];
                    }
                    $guide_about_data['param'] = serialize($data);
                    $guide_about_data['param_url'] = $qyLoginUrl;
                    $guide_about_data['param_result'] = serialize($login_msg);
                    $add_id = $db_village_qywx_guide_about->add($guide_about_data);
                    if (!$add_id) {
                        fdump_api(['qyLogin-linePost添加记录失败:'.__LINE__, $property_id,$qyLoginId,$business_type,$guide_about_data, $db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixinConfig/qyInstallIdErrLog',1);
                        return array('errcode' => 0, 'errmsg' => '添加记录失败');
                    }
                }
            } else {
                fdump_api(['qyRegister-linePost请求返回异常:'.__LINE__, $property_id,$qyLoginId,$business_type,$qyLoginUrl,$data,$login_msg],'qyweixinConfig/qyInstallIdErrLog',1);
            }
            unset($login_msg['provider_config']);
            return $login_msg;
        }
    }

    public function qyGuideAbout($randomNumber,$type='login',$property_id=0,$business_type='property') {
        $where = [
            'business_type' => $business_type,
            'randomNumber' => $randomNumber,
        ];
        $db_village_qywx_guide_about = M('Village_qywx_guide_about');
        $qywx_guide_about = $db_village_qywx_guide_about->where($where)->order('qyResult DESC,wx_bind_id DESC, id DESC')->find();
        if (empty($qywx_guide_about)) {
            fdump_api(['qyGuideAbout-line查询对象不存在:'.__LINE__, $randomNumber,$type,$business_type,$_POST,$db_village_qywx_guide_about->_sql(),$db_village_qywx_guide_about->getDbError()],'qyweixinConfig/qyGuideAboutErrLog',1);
            return array('errcode' => 1001, 'errmsg' => '查询对象不存在');
        }
        $login_type_arr = ['qyLoginSelf', 'qyLoginPost'];
        $install_type_arr = ['qyInstallSelf', 'qyInstallPost'];
        $register_type_arr = ['qyRegisterSelf', 'qyRegisterPost'];
        $type_arr = ['login', 'install','register'];
        if ('login'==$type && !in_array($qywx_guide_about['type'], $login_type_arr)) {
            fdump_api(['qyGuideAbout-line查询数据和对应类型不符:'.__LINE__, $qywx_guide_about,$randomNumber,$type,$business_type,$_POST,$login_type_arr],'qyweixinConfig/qyGuideAboutErrLog',1);
            return array('errcode' => 1001, 'errmsg' => '查询对象不存在');
        } elseif ('install'==$type && !in_array($qywx_guide_about['type'], $install_type_arr)) {
            fdump_api(['qyGuideAbout-line查询数据和对应类型不符:'.__LINE__, $qywx_guide_about,$randomNumber,$type,$business_type,$_POST,$install_type_arr],'qyweixinConfig/qyGuideAboutErrLog',1);
            return array('errcode' => 1001, 'errmsg' => '查询对象不存在');
        } elseif ('register'==$type && !in_array($qywx_guide_about['type'], $register_type_arr)) {
            fdump_api(['qyGuideAbout-line查询数据和对应类型不符:'.__LINE__, $qywx_guide_about,$randomNumber,$type,$business_type,$_POST,$register_type_arr],'qyweixinConfig/qyGuideAboutErrLog',1);
            return array('errcode' => 1001, 'errmsg' => '查询对象不存在');
        } elseif(!in_array($type,$type_arr)) {
            fdump_api(['qyGuideAbout-line查询数据和对应类型不符:'.__LINE__, $qywx_guide_about,$randomNumber,$type,$business_type,$_POST,$register_type_arr],'qyweixinConfig/qyGuideAboutErrLog',1);
            return array('errcode' => 1001, 'errmsg' => '查询对象不存在');
        }
        if (!$property_id && $qywx_guide_about['business_id']) {
            $property_id = $qywx_guide_about['business_id'];
        }
        // 远程类型
        $post_type_arr = ['qyLoginPost', 'qyInstallPost', 'qyRegisterPost'];
        // 如果是登陆 查询下获取到的值 然后进行处理
        if ($qywx_guide_about['qyResult']) {
            $qyResult = unserialize($qywx_guide_about['qyResult']);
        }
        if ($qywx_guide_about['authCorpInfo']) {
            $authCorpInfo = unserialize($qywx_guide_about['authCorpInfo']);
        }
        if ($qywx_guide_about['param_result']) {
            $param_result = unserialize($qywx_guide_about['param_result']);
        }
        if (empty($qyResult) && in_array($qywx_guide_about['type'], $post_type_arr)) {
            if (!$this->http) {
                import('ORG.Net.Http');
                $this->http = new Http();
            }
            $trusted_ip = $_SERVER['SERVER_ADDR'];
            $siteIp = GetHostByName($_SERVER['SERVER_NAME']);
            if ($siteIp=='127.0.0.1' && $trusted_ip) {
                $siteIp = $trusted_ip;
            }
            $site_url = C('config.site_url');
            $domainName = str_replace('http://','',$site_url);
            $domainName = str_replace('https://','',$domainName);
            $ip = get_client_ip(0);
            $data = array(
                'site_url' => C('config.site_url'),
                'siteIp' => $siteIp,
                'domainName' => $domainName,
                'ip' => $ip,
                'randomNumber' => $randomNumber,
                'property_id' => $property_id,
            );
            $qyGuideAboutUrl = $this->post_url ."index.php?g=Index&c=Qywx&a=qyGuideAbout";
            $guide_about_msg = $this->http->curlQyWxPost($qyGuideAboutUrl, $data);
            if ($guide_about_msg['errcode']) {
                return $guide_about_msg;
            }
            $other_qywx_guide_about = $guide_about_msg['info'];
            if ($other_qywx_guide_about['qyResult']) {
                $qyResult = unserialize($other_qywx_guide_about['qyResult']);
            }
            if ($other_qywx_guide_about['authCorpInfo']) {
                $authCorpInfo = unserialize($other_qywx_guide_about['authCorpInfo']);
            }
            if ($other_qywx_guide_about['param_result']) {
                $param_result = unserialize($other_qywx_guide_about['param_result']);
            }
            if (!$property_id && $other_qywx_guide_about['business_id']) {
                $property_id = $other_qywx_guide_about['business_id'];
            }
        }
        $arr = array('errcode' => 0, 'errmsg' => '');
        $arr['type'] = $type;
        fdump_api(['qyGuideAbout-line:'.__LINE__, $qywx_guide_about,$randomNumber,$type,$business_type,$qyResult,$authCorpInfo,$param_result],'qyweixinConfig/qyGuideAboutLog',1);

        if (isset($qyResult['corpid'])) {
            $login_corpid = $qyResult['corpid'];
        } else {
            $auth_corp_info = $qyResult['corp_info'];
            $login_corpid = $auth_corp_info['corpid'];
        }

        if (empty($qyResult) && 'login'==$type) {
            $arr['login'] = false;
            $arr['jump_url'] = '';// 登陆成功后跳转链接
            $arr['randomNumber'] = $randomNumber;
        } elseif (empty($qyResult) && 'install'==$type) {
            // 极速安装快鲸到企业微信——用快鲸，连接客户
            $arr['install'] = false; // 返回下载信息失败
            $arr['jump_url'] = ''; // 下载成功后跳转链接（注册页面暂时不需要跳转 用户物业后台）
            $arr['randomNumber'] = $randomNumber;
        } elseif (empty($qyResult) && 'register'==$type) {
            // 注册包含快鲸的企业微信——用快鲸，连接客户
            $arr['register'] = false; // 返回注册信息失败
            $arr['jump_url'] = ''; // 注册成功后跳转链接（注册页面暂时不需要跳转 用户物业后台）
            $arr['randomNumber'] = $randomNumber;
        } else {
            $where_qywx_guide_about = [
                'id'=>$qywx_guide_about['id']
            ];
            $save_data = [];
            if (!empty($qyResult)) {
                $save_data['qyResult'] = serialize($qyResult);
//                $arr['qyResult'] = $qyResult;
            }
            if ($business_type=='property' && $property_id && $property_id != $qywx_guide_about['property_id']) {
                $save_data['business_id'] = $property_id;
            }
            $db_house_enterprise_wx_bind = M('House_enterprise_wx_bind');
            fdump([$qyResult,$authCorpInfo,$param_result,$type],'$data1');
            if (!empty($save_data) && 'login'==$type) {
                $arr['login'] = false;
                $arr['jump_url'] = '';// 登陆成功后跳转链接
                $arr['randomNumber'] = $randomNumber;

                $save_data['update_time'] = time();
                $where_qywx_guide_about = [
                    'id'=>$qywx_guide_about['id']
                ];
                fdump_api(['获取企业微信用户信息line:'.__LINE__,$qyResult, '$login_corpid' => $login_corpid, '$save_data' => $save_data],'qyweixinConfig/qyGuideAboutLog',1);
                if (isset($login_corpid) && $login_corpid) {
                    $where_bind = [
                        'bind_type' => 0,
                        'corpid' => $login_corpid
                    ];
                    $bind_info = $db_house_enterprise_wx_bind->where($where_bind)->find();
                    fdump_api(['获取企业微信用户信息line:'.__LINE__, '$bind_info' => $bind_info],'qyweixinConfig/qyGuideAboutLog',1);
                    if ($bind_info['bind_id']) {
                        $house_property = M('House_property')->where(['id' => $bind_info['bind_id']])->find();
                        $house_property['login_identify'] = 1;
                        $house_property['isQywx'] = 1;
                        session('property', $house_property);
                        // 给予物业管理员身份
                        $ticket = Token::createToken($bind_info['bind_id'], 8);
                        setcookie('property_access_token', $ticket, -1, '/');
                        $arr['login'] = true;
                        $arr['jump_url'] = C('config.site_url') . '/v20/public/platform/#/property/property.iframe/property_index';
                        $arr['ticket'] = $ticket;
                        $save_data['wx_bind_id'] = $bind_info['pigcms_id'];
                    }
                }
            }
            elseif (!empty($qyResult) && 'install'==$type) {
                $arr['install'] = true; // 返回下载信息失败
                $arr['jump_url'] = ''; // 下载成功后跳转链接（注册页面暂时不需要跳转 用户物业后台）
                $arr['randomNumber'] = $randomNumber;
                // 直接记录即可 暂无处理
                if ($business_type=='property' && $property_id) {
                    $data = $qyResult;
                    $now_time = time();
                    $providerConfig = $param_result['provider_config'];
                    if (empty($providerConfig)) {
                        $data_config = $this->getDataConfig();
                        $providerConfig['SuiteID'] = $data_config['enterprise_wx_provider_suiteid'];
                    }
                    unset($data['errcode']);
                    unset($data['errmsg']);
                    unset($data['authConfig']);
                    $data['expires_in'] = $data['expires_in'] + $now_time;
                    $data['bind_type'] = 0;
                    $data['bind_id'] = $property_id;
                    $data['wx_provider_suiteid'] = $providerConfig['SuiteID'];
                    $auth_corp_info = isset($data['auth_corp_info']) && $data['auth_corp_info'] ? $data['auth_corp_info'] : [];
                    $auth_info = $data['auth_info'];
                    if ($auth_info && !empty($auth_info['agent'])) {
                        $agent = reset($auth_info['agent']);
                        $data['agentid'] = $agent['agentid'];
                        $data['corp_square_logo_url'] = $agent['square_logo_url']?$agent['square_logo_url']:'';
                    }
                    if (isset($login_corpid) && $login_corpid) {
                        $data['corpid'] = $login_corpid;
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
                    $data['last_time'] = time();
                    $err=false;
                    if ($is_bind = D('House_enterprise_wx_bind')->where(array('corpid' => $login_corpid, 'bind_type' => 0))->find()) {
                        if ($is_bind['bind_id'] != $property_id) {
                            $arr['msg'] = '该企业公众号已在其他物业完成绑定，无法绑定到当前物业!';
                            $err=true;
                        }
                    }
                    fdump_api(['qyGuideAboutData-line:'.__LINE__, $data],'qyweixinConfig/qyGuideAboutLog',1);
                    if (!$err && $weixin_bind = D('House_enterprise_wx_bind')->where(array('bind_id' => $property_id, 'bind_type' => 0,'wx_provider_suiteid'=>$data['wx_provider_suiteid']))->find()) {
                        D('House_enterprise_wx_bind')->where(array('bind_id' => $property_id, 'bind_type' => 0,'wx_provider_suiteid'=>$data['wx_provider_suiteid']))->data($data)->save();
                        $wx_bind_id = $weixin_bind['pigcms_id'];
                    } elseif (!$err) {
                        $wx_bind_id = D('House_enterprise_wx_bind')->data($data)->add();
                    } elseif ($is_bind['bind_id'] == $property_id) {
                        D('House_enterprise_wx_bind')->where(array('pigcms_id' => $is_bind['pigcms_id']))->data($data)->save();
                        $wx_bind_id = $is_bind['pigcms_id'];
                    }
                    if ($wx_bind_id) {
                        $save_data['wx_bind_id'] = $wx_bind_id;
                        if ($qywx_guide_about['type']=='qyInstallPost') {
                            $dataSet = array(
                                'site_url' => C('config.site_url'),
                                'siteIp' => $siteIp,
                                'domainName' => $domainName,
                                'ip' => $ip,
                                'randomNumber' => $randomNumber,
                                'property_id' => $property_id,
                                'wx_bind_id' => $wx_bind_id,
                            );
                            $qyGuideSetUrl = $this->post_url ."index.php?g=Index&c=Qywx&a=qyGuideSet";
                            if (!$this->http) {
                                import('ORG.Net.Http');
                                $this->http = new Http();
                            }
                            $this->http->curlQyWxPost($qyGuideSetUrl, $dataSet);
                        }
                    }
                    $arr['install'] = true;
                    // 跳转配置首页
                    $arr['jump_url'] = C('config.site_url') . '/v20/public/platform/#/property/property.iframe/property_enterprise_weixin';
                }
            }
            elseif (!empty($qyResult) && !empty($authCorpInfo) && 'register'==$type) {
                // 直接记录即可 暂无处理
                // 注册包含快鲸的企业微信——用快鲸，连接客户
                $arr['register'] = true; // 返回注册信息失败
                $arr['jump_url'] = ''; // 注册成功后跳转链接（注册页面暂时不需要跳转 用户物业后台）
                $arr['randomNumber'] = $randomNumber;
                if ($business_type=='property' && $property_id) {
                    $now_time = time();
                    $data = $authCorpInfo;
                    $providerConfig = $param_result['provider_config'];
                    if (empty($providerConfig)) {
                        $data_config = $this->getDataConfig();
                        $providerConfig['SuiteID'] = $data_config['enterprise_wx_provider_suiteid'];
                    }
                    unset($data['errcode']);
                    unset($data['errmsg']);
                    $data['bind_type'] = 0;
                    $data['bind_id'] = $property_id;
                    if ($providerConfig && $providerConfig['SuiteID']) {
                        $data['wx_provider_suiteid'] = $providerConfig['SuiteID'];
                    }
                    if ($qyResult['TimeStamp'] && $qyResult['TimeStamp']) {
                        $data['add_time'] = $qyResult['TimeStamp'];
                    } else {
                        $data['add_time'] = time();
                    }
                    $data['expires_in'] = $authCorpInfo['expires_in'] + $now_time;
                    $data['bind_type'] = 0;
                    $data['bind_id'] = $property_id;
                    $auth_corp_info = isset($authCorpInfo['auth_corp_info']) && $authCorpInfo['auth_corp_info'] ? $authCorpInfo['auth_corp_info'] : [];
                    $auth_info = $authCorpInfo['auth_info'];
                    if ($auth_info && !empty($auth_info['agent'])) {
                        $agent = reset($auth_info['agent']);
                        $data['agentid'] = $agent['agentid'];
                        $data['corp_square_logo_url'] = $agent['square_logo_url']?$agent['square_logo_url']:'';
                    }
                    if ($login_corpid) {
                        $data['corpid'] = $login_corpid;
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
                    $data['userid'] = $authCorpInfo['auth_user_info']['userid']?$authCorpInfo['auth_user_info']['userid']:'';
                    $data['name'] = $authCorpInfo['auth_user_info']['name']?$authCorpInfo['auth_user_info']['name']:'';
                    $data['avatar'] = $authCorpInfo['auth_user_info']['avatar']?$authCorpInfo['auth_user_info']['avatar']:'';
                    $data['auth_user_info'] = serialize($authCorpInfo['auth_user_info']);
                    $data['add_time'] = time();
                    $data['last_time'] = time();
                    fdump($data,'$data');
                    $err = false;
                    if ($qyResult['AuthCorpId'] && $qyResult['AuthCorpId']) {
                        $data['corpid'] = $qyResult['AuthCorpId'];
                        if ($is_bind = D('House_enterprise_wx_bind')->where(array('corpid' => $data['corpid'], 'bind_type' => 0))->find()) {
                            if ($is_bind['bind_id'] != $property_id) {
                                fdump_api(['qyGuideAbout-line查询数据和对应类型不符:'.__LINE__, $qywx_guide_about,$randomNumber,$type,$business_type,$_POST,$data,$is_bind],'qyweixinConfig/qyGuideAboutErrLog',1);
                                $arr['msg'] = '该企业微信已在其他物业完成绑定，无法绑定到当前物业!';
                                $err = true;
                            }
                        }
                    }
                    if (!$err && $weixin_bind = D('House_enterprise_wx_bind')->where(array('bind_id' => $property_id, 'bind_type' => 0))->find()) {
                        D('House_enterprise_wx_bind')->where(array('bind_id' => $property_id, 'bind_type' => 0))->data($data)->save();
                        $wx_bind_id = $weixin_bind['pigcms_id'];
                    } elseif (!$err) {
                        $wx_bind_id = D('House_enterprise_wx_bind')->data($data)->add();
                    } elseif ($is_bind['bind_id'] == $property_id) {
                        D('House_enterprise_wx_bind')->where(array('pigcms_id' => $is_bind['pigcms_id']))->data($data)->save();
                        $wx_bind_id = $is_bind['pigcms_id'];
                    }
                    if ($wx_bind_id) {
                        $save_data['wx_bind_id'] = $wx_bind_id;
                        if ($qywx_guide_about['type']=='qyRegisterPost') {
                            $dataSet = array(
                                'site_url' => C('config.site_url'),
                                'siteIp' => $siteIp,
                                'domainName' => $domainName,
                                'ip' => $ip,
                                'randomNumber' => $randomNumber,
                                'property_id' => $property_id,
                                'wx_bind_id' => $wx_bind_id,
                            );
                            $qyGuideSetUrl = $this->post_url ."index.php?g=Index&c=Qywx&a=qyGuideSet";
                            if (!$this->http) {
                                import('ORG.Net.Http');
                                $this->http = new Http();
                            }
                            $this->http->curlQyWxPost($qyGuideSetUrl, $dataSet);
                        }
                    }
                    // 跳转配置首页
                    $arr['jump_url'] = C('config.site_url') . '/v20/public/platform/#/property/property.iframe/property_enterprise_weixin';
                }
            }
            $db_village_qywx_guide_about->where($where_qywx_guide_about)->save($save_data);
            fdump_api(['获取企业微信用户信息line:'.__LINE__, '$save_data' => $save_data, '$where_qywx_guide_about' => $where_qywx_guide_about],'qyweixinConfig/qyGuideAboutLog',1);
        }
        return $arr;
    }

    public function getDataConfig()
    {
//        $service_provider_common_set = C('config.service_provider_common_set');
        $service_provider_common_set = 1;
        $data_config = [];
        if (1==$service_provider_common_set) {
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
        } else {
            if (!$this->http) {
                import('ORG.Net.Http');
                $this->http = new Http();
            }
            $trusted_ip = $_SERVER['SERVER_ADDR'];
            $siteIp = GetHostByName($_SERVER['SERVER_NAME']);
            if ($siteIp=='127.0.0.1' && $trusted_ip) {
                $siteIp = $trusted_ip;
            }
            $site_url = C('config.site_url');
            $domainName = str_replace('http://','',$site_url);
            $domainName = str_replace('https://','',$domainName);
            $ip = get_client_ip(0);
            $data = array(
                'site_url' => C('config.site_url'),
                'siteIp' => $siteIp,
                'domainName' => $domainName,
                'ip' => $ip,
            );
            $getDataConfigUrl = $this->post_url ."index.php?g=Index&c=Qywx&a=getDataConfig";
            $getDataConfig = $this->http->curlQyWxPost($getDataConfigUrl, $data);
            if ($getDataConfig['errcode']) {
                return [];
            }
            $data_config = $getDataConfig;
        }
        return $data_config;
    }

    public function qyRegisterSet($randomNumber,$RegisterCode) {
        $db_village_qywx_guide_about = M('Village_qywx_guide_about');
        $where = [];
        $where['randomNumber'] = $randomNumber;
        $qywx_guide_about = $db_village_qywx_guide_about->where($where)->order('qyResult DESC,wx_bind_id DESC, id DESC')->find();
        if (($qywx_guide_about['from']==0 && $qywx_guide_about['type']=='qyRegisterSelf') || ($qywx_guide_about['from']==1 && $qywx_guide_about['type']=='qyRegisterPost')) {
            // 自身
            $provider_secret = C('config.service_provider_secret');
            $corpid = C('config.enterprise_wx_corpid');
            if (empty($corpid)) {
                fdump_api(['qyRegisterSet-line请配置企业微信ID:'.__LINE__, $qywx_guide_about,$randomNumber,$RegisterCode],'qyweixinConfig/qyRegisterSetErrLog',1);
                return array('errcode' => 1001, 'errmsg' => '请配置企业微信ID');
            }
            if (empty($provider_secret)) {
                fdump_api(['qyRegisterSet-line请配置企业ProviderSecret:'.__LINE__,$qywx_guide_about,$randomNumber,$RegisterCode,$corpid],'qyweixinConfig/qyRegisterSetErrLog',1);
                return array('errcode' => 1001, 'errmsg' => '请前往“服务商管理端-应用管理-通用开发参数”中复制ProviderSecret配置到企业微信配置中');
            }
            $provider_access_msg = D('Access_token_common_expires')->get_provider_token($corpid, $provider_secret);
            if (!$provider_access_msg['errcode']) {
                $provider_access_token = $provider_access_msg['access_token'];
            } else {
                fdump_api(['qyRegisterSet-line必要参数获取错误:'.__LINE__, $qywx_guide_about,$randomNumber,$RegisterCode,$provider_access_msg],'qyweixinConfig/qyRegisterSetErrLog',1);
                return $provider_access_msg;
            }
            $get_register_info = D('Access_token_common_expires')->get_register_info($provider_access_token, $RegisterCode);
            if (!$get_register_info['errcode']) {
                $contact_sync = $get_register_info['contact_sync'];
                $access_token = $contact_sync['access_token'];
            } else {
                fdump_api(['qyRegisterSet-line查询注册状态错误:'.__LINE__, $qywx_guide_about,$randomNumber,$RegisterCode,$get_register_info],'qyweixinConfig/qyRegisterSetErrLog',1);
                return $get_register_info;
            }
            if ($contact_sync && $access_token) {
                $contact_sync_success = D('Access_token_common_expires')->contact_sync_success($access_token);
                return $contact_sync_success;
            }
            return $get_register_info;
        } elseif ($qywx_guide_about['type']=='qyRegisterPost') {
            // 远程提交
            fdump_api(['qyRegisterSet-line查询数据和对应类型不符:'.__LINE__, $qywx_guide_about,$randomNumber,$RegisterCode],'qyweixinConfig/qyRegisterSetErrLog',1);
            return array('errcode' => 1001, 'errmsg' => '对应信息不存在或者类型有误');
        } else {
            fdump_api(['qyRegisterSet-line查询数据和对应类型不符:'.__LINE__, $qywx_guide_about,$randomNumber,$RegisterCode],'qyweixinConfig/qyRegisterSetErrLog',1);
            return array('errcode' => 1001, 'errmsg' => '对应信息不存在或者类型有误');
        }
    }

    public function qyGetAuthInfo($property_id) {
        $bind_info = D('House_enterprise_wx_bind')->where(array('bind_id' => $property_id, 'bind_type' => 0))->find();
        $enterprise_wx_provider_suiteid = C('config.enterprise_wx_provider_suiteid');
//        $service_provider_common_set = C('config.service_provider_common_set');
        $service_provider_common_set = 1;
        $arr = [
            'errcode' => 0,
            'errmsg' => '',
        ];
        if ($bind_info['permanent_code']) {
            if (1==$service_provider_common_set || $bind_info['wx_provider_suiteid'] == $enterprise_wx_provider_suiteid) {
                // 如果配置记录是当前的还是以当前为主
                $permanent_code = $bind_info['permanent_code'];
                $db_access_token_common_expires = D('Access_token_common_expires');
                $where_suite_ticket = [
                    'name' => 'enterprise_wx_suite_ticket'
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
                if (empty($provider_config)) {
                    $arr['errcode'] = 1001;
                    $arr['errmsg'] = '对应物业服务商相关配置缺失';
                    return $arr;
                }
                // 获取 获取第三方应用凭证
                $now = time();
                $suite_access_token_msg = $db_access_token_common_expires->get_suite_access_token($property_id,$provider_config,$now);
                if (!empty($suite_access_token_msg['errcode'])) {
                    return $suite_access_token_msg;
                }
                $suite_access_token = $suite_access_token_msg['access_token'];
                $get_auth_info_info = $db_access_token_common_expires->get_auth_info($bind_info['corpid'], $permanent_code, $suite_access_token);
                fdump_api(['获取企业微信信息:'.__LINE__,$bind_info['corpid'], $permanent_code, $suite_access_token,$get_auth_info_info],'qyweixinConfig/Enterprise/qyGetAuthInfoLog',1);
                if ($get_auth_info_info['errcode']) {
                    $arr['errcode'] = 1001;
                    $arr['errmsg'] = $get_auth_info_info['errmsg']?$get_auth_info_info['errmsg']:'获取信息失败';
                    return $arr;
                } else {
                    $now_time = time();
                    $auth_corp_info = $get_auth_info_info['auth_corp_info'];
                    if ($auth_corp_info) {
                        if ($auth_corp_info['corpid']) {
                            $data['corpid'] = $auth_corp_info['corpid'];
                        }
                        $data['corp_name'] = $auth_corp_info['corp_name']? $auth_corp_info['corp_name']:'';
                        $data['corp_type'] = $auth_corp_info['corp_type']?$auth_corp_info['corp_type']:'';
                        $data['subject_type'] = $auth_corp_info['subject_type']?$auth_corp_info['subject_type']:'';
                        $data['verified_end_time'] = $auth_corp_info['verified_end_time']?$auth_corp_info['verified_end_time']:'';
                        $data['corp_wxqrcode'] = $auth_corp_info['corp_wxqrcode']?$auth_corp_info['corp_wxqrcode']:'';
                        if ($auth_corp_info['corp_square_logo_url']) {
                            $data['corp_square_logo_url'] = $auth_corp_info['corp_square_logo_url'];
                        }
                        $data['auth_corp_info'] = serialize($auth_corp_info);
                        $data['corp_user_max'] = $auth_corp_info['corp_user_max']?$auth_corp_info['corp_user_max']:0;
                        $data['corp_agent_max'] = $auth_corp_info['corp_agent_max']?$auth_corp_info['corp_agent_max']:0;
                        $data['corp_full_name'] = $auth_corp_info['corp_full_name']?$auth_corp_info['corp_full_name']:0;
                    }
                    $auth_info = $data['auth_info'];
                    if (!empty($auth_info)) {
                        $data['auth_info'] = serialize($auth_info);
                    }
                    $data['last_time'] = $now_time;
                    $id = D('House_enterprise_wx_bind')->where(array('pigcms_id' => $bind_info['pigcms_id']))->data($data)->save();
                    fdump_api(['获取企业微信信息:'.__LINE__,$id,$data],'qyweixinConfig/Enterprise/qyGetAuthInfoLog',1);
                    return $id;
                }
            } else {
                if (!$this->http) {
                    import('ORG.Net.Http');
                    $this->http = new Http();
                }
                $corpid = $bind_info['corpid'];
                $permanent_code = $bind_info['permanent_code'];
                $data = array(
                    'corpid' => $corpid,
                    'permanent_code' => $permanent_code,
                );
                $qyLoginUrl = $this->post_url ."index.php?g=Index&c=Qywx&a=qyGetAuthInfo";
                $qyGetAuthInfo = $this->http->curlQyWxPost($qyLoginUrl, $data);
                fdump_api(['获取企业微信信息:'.__LINE__,$data, $qyLoginUrl, $qyGetAuthInfo],'qyweixinConfig/Enterprise/qyGetAuthInfoLog',1);
                if ($qyGetAuthInfo['errcode']) {
                    $arr['errcode'] = 1001;
                    $arr['errmsg'] = $qyGetAuthInfo['errmsg']?$qyGetAuthInfo['errmsg']:'获取信息失败';
                    return $arr;
                }
                $now_time = time();
                $auth_corp_info = $qyGetAuthInfo['auth_corp_info'];
                if ($auth_corp_info) {
                    if ($auth_corp_info['corpid']) {
                        $data['corpid'] = $auth_corp_info['corpid'];
                    }
                    $data['corp_name'] = $auth_corp_info['corp_name']? $auth_corp_info['corp_name']:'';
                    $data['corp_type'] = $auth_corp_info['corp_type']?$auth_corp_info['corp_type']:'';
                    $data['subject_type'] = $auth_corp_info['subject_type']?$auth_corp_info['subject_type']:'';
                    $data['verified_end_time'] = $auth_corp_info['verified_end_time']?$auth_corp_info['verified_end_time']:'';
                    $data['corp_wxqrcode'] = $auth_corp_info['corp_wxqrcode']?$auth_corp_info['corp_wxqrcode']:'';
                    if ($auth_corp_info['corp_square_logo_url']) {
                        $data['corp_square_logo_url'] = $auth_corp_info['corp_square_logo_url'];
                    }
                    $data['auth_corp_info'] = serialize($auth_corp_info);
                    $data['corp_user_max'] = $auth_corp_info['corp_user_max']?$auth_corp_info['corp_user_max']:0;
                    $data['corp_agent_max'] = $auth_corp_info['corp_agent_max']?$auth_corp_info['corp_agent_max']:0;
                    $data['corp_full_name'] = $auth_corp_info['corp_full_name']?$auth_corp_info['corp_full_name']:0;
                }
                $auth_info = $data['auth_info'];
                if (!empty($auth_info)) {
                    $data['auth_info'] = serialize($auth_info);
                }
                $data['last_time'] = $now_time;
                $id = D('House_enterprise_wx_bind')->where(array('pigcms_id' => $bind_info['pigcms_id']))->data($data)->save();
                fdump_api(['获取企业微信信息:'.__LINE__,$id,$data],'qyweixinConfig/Enterprise/qyGetAuthInfoLog',1);
                return $id;
            }
        }
    }

    //  获取企业凭证
    public function qyGetCorpToken($property_id) {
        $bind_info = D('House_enterprise_wx_bind')->where(array('bind_id' => $property_id, 'bind_type' => 0))->find();
        $enterprise_wx_provider_suiteid = C('config.enterprise_wx_provider_suiteid');
//        $service_provider_common_set = C('config.service_provider_common_set');
        $service_provider_common_set = 1;
        $arr = [
            'errcode' => 0,
            'errmsg' => '',
        ];
        if ($bind_info['permanent_code']) {
            if (1==$service_provider_common_set || $bind_info['wx_provider_suiteid'] == $enterprise_wx_provider_suiteid) {
                // 如果配置记录是当前的还是以当前为主
                $permanent_code = $bind_info['permanent_code'];
                $db_access_token_common_expires = D('Access_token_common_expires');
                $where_suite_ticket = [
                    'name' => 'enterprise_wx_suite_ticket'
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
                if (empty($provider_config)) {
                    $arr['errcode'] = 1001;
                    $arr['errmsg'] = '对应物业服务商相关配置缺失';
                    return $arr;
                }
                // 获取 获取第三方应用凭证
                $now = time();
                $suite_access_token_msg = $db_access_token_common_expires->get_suite_access_token($property_id,$provider_config,$now);
                if (!empty($suite_access_token_msg['errcode'])) {
                    return $suite_access_token_msg;
                }
                $suite_access_token = $suite_access_token_msg['access_token'];
                $provider_config['permanent_code'] = $permanent_code;
                $corp_token_msg = $db_access_token_common_expires->get_corp_token($property_id,$suite_access_token,$provider_config,$now);
                if (!empty($corp_token_msg['errcode'])) {
                    return array('errcode' => 1, 'errmsg' => $corp_token_msg['errcode']);
                }
                $corp_token = $corp_token_msg['access_token'];
                return array('errcode' => 0, 'access_token' => $corp_token);
            } else {
                if (!$this->http) {
                    import('ORG.Net.Http');
                    $this->http = new Http();
                }
                $corpid = $bind_info['corpid'];
                $permanent_code = $bind_info['permanent_code'];
                $data = array(
                    'corpid' => $corpid,
                    'permanent_code' => $permanent_code,
                );
                $qyLoginUrl = $this->post_url ."index.php?g=Index&c=Qywx&a=qyGetCorpToken";
                $qyGetCorpToken = $this->http->curlQyWxPost($qyLoginUrl, $data);
                fdump_api(['获取企业微信信息:'.__LINE__,$data, $qyLoginUrl, $qyGetCorpToken],'qyweixinConfig/Enterprise/qyGetCorpTokenLog',1);
                return $qyGetCorpToken;
            }
        }
    }

    // 处理注册返回相关信息
    public function qyRegisterInfo($AuthCode,$SuiteId='', $provider_config = [], $codeKey = 'permanent_code') {
        fdump_api([
            '处理注册返回相关信息:' => __LINE__,
            'AuthCode' => $AuthCode,
            'provider_config' => $provider_config,
            'codeKey' => $codeKey,
        ],'qyweixinConfig/qyRegisterInfoLog',1);
//        $service_provider_common_set = C('config.service_provider_common_set');
        $service_provider_common_set = 1;
        if (1==$service_provider_common_set) {
            $where_suite_ticket = [
                'name' => 'enterprise_wx_suite_ticket'
            ];
            $SuiteTicket = M('Config')->where($where_suite_ticket)->getField('value');
            $SuiteTicket = trim($SuiteTicket);
            if (empty($provider_config)) {
                $provider_config = [
                    'corpid' => C('config.enterprise_wx_corpid'),
                    'Secret' => C('config.enterprise_wx_provider_secret'),
                    'SuiteID' => C('config.enterprise_wx_provider_suiteid'),
                    'Token' => C('config.enterprise_wx_provider_token'),
                    'SuiteTicket' => $SuiteTicket,
                    'EncodingAESKey' => C('config.enterprise_wx_provider_secret'),
                ];
            }
            $now = time();
            $db_access_token_common_expires = D('Access_token_common_expires');
            // 获取 获取第三方应用凭证
            $suite_access_token_msg = $db_access_token_common_expires->get_suite_access_token(0,$provider_config,$now);
            fdump_api([
                '获取第三方应用凭证:' => __LINE__,
                'provider_config' => $provider_config,
                'suite_access_token_msg' => $suite_access_token_msg,
            ],'qyweixinConfig/qyRegisterInfoLog',1);
            if (!empty($suite_access_token_msg['errcode'])) {
                return $suite_access_token_msg;
            }
            $suite_access_token = $suite_access_token_msg['access_token'];
            $get_permanent_code_msg = $db_access_token_common_expires->get_permanent_code($suite_access_token, $AuthCode);
            fdump_api([
                '获取企业微信信息:' => __LINE__,
                'provider_config' => $provider_config,
                'suite_access_token' => $suite_access_token,
                'AuthCode' => $AuthCode,
                'get_permanent_code_msg' => $get_permanent_code_msg
            ],'qyweixinConfig/qyRegisterInfoLog',1);
            if (!empty($get_permanent_code_msg['errcode'])) {
                return $get_permanent_code_msg;
            }
            if ($codeKey != 'permanent_code') {
                $get_permanent_code_msg[$codeKey] = $get_permanent_code_msg['permanent_code'];
                $get_permanent_code_msg['book_permanent_code_time'] = $now;
                unset($get_permanent_code_msg['permanent_code']);
            }
        } else {
            if (!$this->http) {
                import('ORG.Net.Http');
                $this->http = new Http();
            }
            $data = array(
                'auth_code' => $AuthCode,
                'suite_id' => $SuiteId,
            );
            $qyRegisterInfoUrl = $this->post_url ."index.php?g=Index&c=Qywx&a=qyRegisterInfo";
            $get_permanent_code_msg = $this->http->curlQyWxPost($qyRegisterInfoUrl, $data);
            fdump_api(['获取企业微信信息:'.__LINE__,$data, $qyRegisterInfoUrl, $get_permanent_code_msg],'qyweixinConfig/qyRegisterInfoLog',1);
            if (!empty($get_permanent_code_msg['errcode'])) {
                return $get_permanent_code_msg;
            }
        }
        $data = $get_permanent_code_msg;
        $now_time = time();
        $auth_corp_info = $data['auth_corp_info'];
        $corpid = $auth_corp_info['corpid'];
        if (isset($data['register_code_info']) && $data['register_code_info']) {
            $register_code_info = $data['register_code_info'];
        } else {
            $register_code_info = [];
        }
        if ($register_code_info && $register_code_info['state']) {
            $randomNumber = trim($register_code_info['state']);
            $qywx_info = D('Village_qywx_guide_about')->where(['randomNumber'=>$randomNumber])->find();
            if (!empty($qywx_info)){
                $data_qywx['authCorpInfo']=serialize($data);
                if ($corpid) {
                    $data_qywx['AuthCorpId'] = $corpid;
                }
                $data_qywx['update_time'] = time();
                D('Village_qywx_guide_about')->where(['id'=>$qywx_info['id']])->save($data_qywx);
                if ($qywx_info['type']=='qyRegisterSelf' && $qywx_info['business_id'] && $qywx_info['business_type']=='property') {
                    $property_id = $qywx_info['business_id'];
                    $now_time = time();
                    unset($data['errcode']);
                    unset($data['errmsg']);
                    unset($data['register_code_info']);
                    $data['expires_in'] = $data['expires_in'] + $now_time;
                    $data['bind_type'] = 0;
                    $data['bind_id'] = $property_id;
                    $data['wx_provider_suiteid'] = $SuiteId?$SuiteId:'';
                    $auth_corp_info = $data['auth_corp_info'];
                    $auth_info = $data['auth_info'];
                    if ($auth_info && !empty($auth_info['agent'])) {
                        $agent = reset($auth_info['agent']);
                        $data['agentid'] = $agent['agentid'];
                        $data['corp_square_logo_url'] = $agent['square_logo_url']?$agent['square_logo_url']:'';
                    }
                    if ($auth_corp_info) {
                        if ($auth_corp_info['corpid']) {
                            $data['corpid'] = $auth_corp_info['corpid'];
                        }
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

                    $data['userid'] = $data['auth_user_info']['userid']?$data['auth_user_info']['userid']:'';
                    $data['name'] = $data['auth_user_info']['name']?$data['auth_user_info']['name']:'';
                    $data['avatar'] = $data['auth_user_info']['avatar'];
                    $data['auth_user_info'] = serialize($data['auth_user_info']);
                    $data['add_time'] = time();
                    $data['last_time'] = time();

                    if ($is_bind = D('House_enterprise_wx_bind')->where(array('corpid' => $data['corpid'], 'bind_type' => 0))->find()) {
                        if ($is_bind['bind_id'] != $property_id) {
                            return array('errcode' => 1001, 'errmsg' => '该企业公众号已在其他物业完成绑定，无法绑定到当前物业！');
                        }
                    }
                    if ($weixin_bind = D('House_enterprise_wx_bind')->where(array('bind_id' => $property_id, 'bind_type' => 0,'wx_provider_suiteid'=>$data['wx_provider_suiteid']))->find()) {
                        D('House_enterprise_wx_bind')->where(array('bind_id' => $property_id, 'bind_type' => 0,'wx_provider_suiteid'=>$data['wx_provider_suiteid']))->data($data)->save();
                    } else {
                        D('House_enterprise_wx_bind')->data($data)->add();
                    }
                }
            }
        } elseif (isset($data['auth_corp_info']['corpid'])) {
            fdump_api([
                '开始记录:' => __LINE__,
                'codeKey' => $codeKey,
                'data' => $data,
                'permanent_code' => $data['permanent_code'],
            ],'qyweixinConfig/qyRegisterInfoLog',1);
            // 目前只是变更一个数据
            $saveData = [];
            if ($get_permanent_code_msg[$codeKey]) {
                $saveData[$codeKey] = $get_permanent_code_msg[$codeKey];
                if (isset($get_permanent_code_msg['book_permanent_code_time']) && $get_permanent_code_msg['book_permanent_code_time']) {
                    $saveData['book_permanent_code_time'] = $get_permanent_code_msg['book_permanent_code_time'];
                } elseif ($codeKey == 'book_permanent_code') {
                    $saveData['book_permanent_code_time'] = time();
                }
                $enterprise_wx_provider_suiteid = C('config.enterprise_wx_provider_suiteid');
                $where = array('corpid' => $data['auth_corp_info']['corpid'], 'bind_type' => 0,'wx_provider_suiteid'=>$enterprise_wx_provider_suiteid);
                fdump_api([
                    '1记录:' => __LINE__,
                    'saveData' => $saveData,
                    'where' => $where,
                ],'qyweixinConfig/qyRegisterInfoLog',1);
                D('House_enterprise_wx_bind')->where($where)->data($saveData)->save();
            }
        }
        return $get_permanent_code_msg;
    }

    // 处理通知
    public function qyCancelAuth($AuthCorpId, $SuiteId, $randomNumber, $url) {
        if (!$this->http) {
            import('ORG.Net.Http');
            $this->http = new Http();
        }
        $data = [
            'AuthCorpId' => $AuthCorpId,
            'SuiteId' => $SuiteId,
            'randomNumber' => $randomNumber
        ];
        $cancelAuthUrl = $url ."index.php?g=Index&c=Qywx&a=qyCancelAuth";
        $cancelAuthMsg = $this->http->curlQyWxPost($cancelAuthUrl, $data);
        return $cancelAuthMsg;
    }

    /**
     * 接口请求方式
     * @author: wanziyang
     * @date_time: 2020/6/9 14:18
     * @param string $url 请求路径
     * @param array|string $data  传参值
     * @param int $timeout 接口请求过期时间
     * @return array|mixed
     */
    public function curlPost($url,$data,$timeout=45){
        $ch = curl_init();
        $headers[] = "Content-type: application/x-www-form-urlencoded;charset=utf-8";
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
}