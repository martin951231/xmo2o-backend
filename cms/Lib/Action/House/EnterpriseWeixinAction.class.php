<?php
/**
 * Created by PhpStorm.
 * User: wanziyang
 * Date: 2019/12/26
 * Time: 13:46
 */
//物业后台企业微信
class EnterpriseWeixinAction extends BaseAction{

    protected $village_id;
    protected $property_id;

    public function _initialize(){
        parent::_initialize();
        $this->village_id = $this->house_session['village_id'];
        $property = session('property');
        $this->property_id = $property['id'];
    }

//        1. 企业; 2. 政府以及事业单位; 3. 其他组织, 4.团队号
    private $subject_type_arr = array(
        1=> '企业',
        2=> '政府以及事业单位',
        3=> '其他组织',
        4=> '团队号',
    );

    // 微信公众号

    public function index()
    {
        $application_id = I('application_id');
        if ($application_id>0) {
            $property_id = $this->property_id;
            $m_application_bind = M('Application_bind');
            $where = array(
                'application_id' => $application_id,
                'from' => 1,
                'use_id' => $property_id,
                'status' => 0,
            );
            $bind_info = $m_application_bind->where($where)->field('bind_id')->find();
            if (empty($bind_info)) {
                $this->page_public();
                if (!$_GET['iframe']) {
                    $page_magin = 160;
                } else {
                    $page_magin = 0;
                }
                $this->assign('page_magin',$page_magin);
                $this->display('PropertyApplication/detail');
            }
        } else {
            $enterprise_wx_bind = array();
            if ($enterprise_wx_bind = D('House_enterprise_wx_bind')->where(array('bind_id' => $this->property_id, 'bind_type' => 0))->find()) {
                if (!$enterprise_wx_bind['last_time'] || $enterprise_wx_bind['last_time']+(8600*6)<time()) {
                    // 超过了6天自动获取最新信息
                    import('@.ORG.qywx.config');
                    $config = new config();
                    $data_config = $config->qyGetAuthInfo($this->property_id);
                    $enterprise_wx_bind = D('House_enterprise_wx_bind')->where(array('bind_id' => $this->property_id, 'bind_type' => 0))->find();
                }
                $subject_type_arr = $this->subject_type_arr;
                if ($enterprise_wx_bind['corp_type'] == 'verified') {
                    $enterprise_wx_bind['type_info'] = '认证的' . $subject_type_arr[$enterprise_wx_bind['subject_type']];
                } elseif ($enterprise_wx_bind['corp_type'] == 'unverified') {
                    $enterprise_wx_bind['type_info'] = '未认证的' . $subject_type_arr[$enterprise_wx_bind['subject_type']];
                }
                if($enterprise_wx_bind){
                    $enterprise_wx_bind['qrcode_url'] = $this->config['site_url'].'/index.php?c=Image&a=wx_image_download&url='.urlencode($enterprise_wx_bind['corp_wxqrcode']).'&path='.$this->property_id;
                }
                // 触发下队列
                invoke_v20_service('\app\community\model\service\workweixin\WeiXinUnionIdToExternalUserIdService/handleTraitsUserToExternalUserId',['property_id' => $this->property_id]);
               $this->assign('bind', $enterprise_wx_bind);
            } else {
//                $service_provider_common_set = C('config.service_provider_common_set');
                $service_provider_common_set = 1;
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
                    $where_enterprise_wx_provider_encodingaeskey = [
                        'name' => 'enterprise_wx_provider_encodingaeskey'
                    ];
                    $EncodingAESKey = M('Config')->where($where_enterprise_wx_provider_encodingaeskey)->getField('value');
                    $EncodingAESKey = trim($EncodingAESKey);
                    if (empty($SuiteTicket) || empty($corpid) || empty($Secret) || empty($SuiteID) || empty($Token) || empty($EncodingAESKey)) {
                        $qy_err = [
                            'errmsg' => '请联系平台正确配置企业微信相关参数'
                        ];
                        $this->assign('url', '');
                        $this->assign('qy_err', $qy_err);
                    }
                }
                $this->assign('is_new', true);
            }
            $is_add = 0;
            $book_permanent_code = isset($enterprise_wx_bind['book_permanent_code']) ? trim($enterprise_wx_bind['book_permanent_code']) : '';
            $plaintextCorpId = isset($enterprise_wx_bind['PlaintextCorpId']) ? trim($enterprise_wx_bind['PlaintextCorpId']) : '';
            if (!$book_permanent_code || !$plaintextCorpId) {
                $is_add = 1;
            }
            $this->assign('is_add', $is_add);
            $addUrl = $this->config['site_url'].'/shequ.php?g=House&c=EnterpriseWeixin&a=addInfo';
            $this->assign('addUrl', $addUrl);
            $this->display();
        }
    }

    public function addInfo() {
        $enterprise_wx_bind = D('House_enterprise_wx_bind')->where(array('bind_id' => $this->property_id, 'bind_type' => 0))->find();
        if (IS_POST) {
            $qy_address_book_secret     = trim($_POST['qy_address_book_secret']);
            $contact_way_secret         = trim($_POST['contact_way_secret']);
            $contact_way_token          = trim($_POST['contact_way_token']);
            $contact_way_encodingaeskey = trim($_POST['contact_way_encodingaeskey']);
            $PlaintextCorpId = trim($_POST['PlaintextCorpId']);
            if ($qy_address_book_secret) {
                $data['qy_address_book_secret'] = $qy_address_book_secret;
            }
            if ($contact_way_secret) {
                $data['contact_way_secret'] = $contact_way_secret;
            }
            if ($contact_way_token) {
                $data['contact_way_token'] = $contact_way_token;
            }
            if ($contact_way_encodingaeskey) {
                $data['contact_way_encodingaeskey'] = $contact_way_encodingaeskey;
            }
            if ($PlaintextCorpId) {
                $data['PlaintextCorpId'] = $PlaintextCorpId;
            }
            if ($data) {
                D('House_enterprise_wx_bind')->where(array('bind_id' => $this->property_id, 'bind_type' => 0))->data($data)->save();
            }
            // 触发下队列
            invoke_v20_service('\app\community\model\service\workweixin\WeiXinUnionIdToExternalUserIdService/handleTraitsUserToExternalUserId',['property_id' => $this->property_id]);
            $this->success('操作成功');
        } else {
            $corpid                  = $enterprise_wx_bind['corpid'];
            $plaintextCorpId         = $enterprise_wx_bind['PlaintextCorpId'];
            $enterprise_wx_corpid    = $this->config['enterprise_wx_corpid'];
            $oneJudge = false;
            if ($corpid == $enterprise_wx_corpid || $plaintextCorpId == $enterprise_wx_corpid) {
                $oneJudge = true;
            }
            $this->assign('oneJudge', $oneJudge);
            
            $db_house_worker = M('House_worker');
            $where_worker['qy_id']     = ['neq', ''];
            $where_worker['qy_status'] = 1;
            $where_worker['is_del']    = 0;
            $where_worker['property_id'] = $this->property_id;
            $house_worker_count = $db_house_worker->where($where_worker)->count();
            if (!$house_worker_count) {
                $house_worker_count = 0;
            }
            $this->assign('house_worker_count', $house_worker_count);

            $contact_way_secret = isset($enterprise_wx_bind['contact_way_secret']) ? trim($enterprise_wx_bind['contact_way_secret']) : '';
            $contact_way_token = isset($enterprise_wx_bind['contact_way_token']) ? trim($enterprise_wx_bind['contact_way_token']) : '';
            $contact_way_encodingaeskey = isset($enterprise_wx_bind['contact_way_encodingaeskey']) ? trim($enterprise_wx_bind['contact_way_encodingaeskey']) : '';
            
            $hasAlreadyConfig = 0;
            if ($contact_way_secret) {
                $hasAlreadyConfig = 1;
            }
            if ($contact_way_token) {
                $hasAlreadyConfig = 1;
            }
            if ($contact_way_encodingaeskey) {
                $hasAlreadyConfig = 1;
            }
            $this->assign('hasAlreadyConfig', $hasAlreadyConfig);

            $this->assign('enterprise_wx_bind', $enterprise_wx_bind);
            $this->assign('receiveUrl', $this->config['site_url']."/wap.php?g=Wap&c=HouseEnterpriseWeixin&a=contact_way_receive&id=".$this->property_id);
            $this->display();
        }
    }

    public function auth_back()
    {
        fdump_api(['line:'.__LINE__,$_POST, $_GET],'qyweixin/Enterprise/auth_back_log',1);
        if (isset($_GET['auth_code']) && isset($_GET['expires_in'])){
            import('ORG.Net.Http');
            $property_id = $_GET['state'];
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
            $suite_access_token_msg = $db_access_token_common_expires->get_suite_access_token($property_id,$provider_config,$now);
            fdump_api(['line:'.__LINE__,$property_id, $provider_config, $suite_access_token_msg],'qyweixin/Enterprise/auth_back_log',1);
            if (!empty($suite_access_token_msg['errcode'])) {
                $this->assign('errmsg', $suite_access_token_msg['errmsg']?$suite_access_token_msg['errmsg']:'获取凭证失败');
                $this->display('fail');
                exit();
            }
            $suite_access_token = $suite_access_token_msg['access_token'];

//            $wx_provider_suiteid = $_GET['suite_id'];
//            if ($provider_config['SuiteID']!=$wx_provider_suiteid) {
//                $this->assign('errmsg', '服务商应用id不一致');
//                $this->display('fail');
//                exit();
//            }
            //获取企业永久授权码
            $url = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_permanent_code?suite_access_token=' . $suite_access_token;
            $data = array(
                'auth_code' => $_GET['auth_code']
            );
            $result1 = Http::curlPost($url, json_encode($data));
            fdump_api(['获取企业微信永久授权line:'.__LINE__,$url, $data,$result1],'qyweixin/Enterprise/auth_back_log',1);
            if ($result1['errcode']) {
                $this->assign('errmsg', $result1['errmsg']);
                $this->display('fail');
                exit();
            } else {
                $data = $result1;
                $now_time = time();
                unset($data['errcode']);
                unset($data['errmsg']);
                unset($data['errmsg']);
                $data['expires_in'] = $data['expires_in'] + $now_time;
                $data['bind_type'] = 0;
                $data['bind_id'] = $property_id;
                $data['wx_provider_suiteid'] = $provider_config['SuiteID'];
                $auth_corp_info = $data['auth_corp_info'];
                $auth_info = $data['auth_info'];
                if ($auth_info && !empty($auth_info['agent'])) {
                    $agent = reset($auth_info['agent']);
                    $data['agentid'] = $agent['agentid'];
                    $data['corp_square_logo_url'] = $agent['square_logo_url']?$agent['square_logo_url']:'';
                }
                if ($auth_corp_info) {
                    if ($auth_corp_info['corpid'])  {
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

                $data['userid'] = $data['auth_user_info']['userid'];
                $data['name'] = $data['auth_user_info']['name'];
                $data['avatar'] = $data['auth_user_info']['avatar'];
                $data['auth_user_info'] = serialize($data['auth_user_info']);
                $data['add_time'] = time();
                $data['last_time'] = time();


                if ($is_bind = D('House_enterprise_wx_bind')->where(array('corpid' => $data['corpid'], 'bind_type' => 0))->find()) {
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
                fdump_api(['获取企业微信永久授权line:'.__LINE__,D('House_enterprise_wx_bind')->_sql(),D('House_enterprise_wx_bind')->getDbError()],'qyweixin/Enterprise/auth_back_log',1);
                $success_url = $this->config['site_url']."/v20/public/platform/#/property/property.iframe/property_enterprise_weixin";
                $this->assign('success_url', $success_url);

                $this->display('success');
            }
        } else {
            $this->assign('errmsg', '不合法的请求授权');
            $this->display('fail');
        }
    }

    public function successTip() {
        $this->display('success');
    }

    public function get_auth_info() {
        $property_id = I('property_id');
        if (!$property_id) {
            $property_id = $this->property_id;
        }
        $bind_info = D('House_enterprise_wx_bind')->where(array('bind_id' => $property_id, 'bind_type' => 0))->find();
        if ($bind_info['permanent_code']) {
            $permanent_code = $bind_info['permanent_code'];
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
                $this->assign('errmsg', '对应物业服务商相关配置缺失');
                $this->display('fail');
                exit();
            }
            // 获取 获取第三方应用凭证
            $now = time();
            $suite_access_token_msg = $db_access_token_common_expires->get_suite_access_token($property_id,$provider_config,$now);
            if (!empty($suite_access_token_msg['errcode'])) {
                $this->ajaxReturn(array('code'=>2,'msg'=>$suite_access_token_msg['errmsg']?$suite_access_token_msg['errmsg']:'获取凭证失败'));
            }
            $suite_access_token = $suite_access_token_msg['access_token'];
            $url = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_auth_info?suite_access_token=' . $suite_access_token;
            $data = array(
                'auth_corpid' => $bind_info['corpid'],
                'permanent_code' => $permanent_code,
            );
            import('ORG.Net.Http');
            $result = Http::curlPost($url, json_encode($data));
            fdump_api(['获取企业微信信息:'.__LINE__,$url, $data,$result],'qyweixin/Enterprise/get_auth_info_log',1);
            if ($result['errcode']) {
                $this->assign('errmsg', $result['errmsg']?$result['errmsg']:'获取信息失败');
                $this->display('fail');
                exit();
            } else {
                $now_time = time();
                $auth_corp_info = $result['auth_corp_info'];
                if ($auth_corp_info) {
                    if ($auth_corp_info['corpid'])  {
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
                fdump_api(['获取企业微信信息:'.__LINE__,$data],'qyweixin/Enterprise/get_auth_info_log',1);
                $id = D('House_enterprise_wx_bind')->where(array('pigcms_id' => $bind_info['pigcms_id']))->data($data)->save();
                return $id;
            }
        }
    }

    public function page_public() {
        $application_id = I('application_id');
        $condition_application = array(
            'application_id' => $application_id
        );
        $is_add = false;
        if (37==$application_id) {
            $is_add = true;
        }
        $application = M('Application_list')->where($condition_application)->find();
        if ($application['application_detail']) {
            $application['application_detail'] = replace_file_domain_content($application['application_detail']);
        }
        if ($application['application_img']) {
            $application['application_img'] = replace_file_domain($application['application_img']);
        } else {
            if (!$application['application_label']) {
                $application['application_label'] = 'default';
            }
            $application['application_img'] = $this->config['site']."/static/application/img/{$application['application_label']}_detail.png?t=001";
        }
        if ($application['application_property']) {
            $url = $this->config['site_url'] . $application['application_property'] .'&iframe='.$_GET['iframe'];
            $this->assign('url',$url);
        }
        $this->assign('is_add',$is_add);

        $property_id = $this->property_id;
        $packageData = D('PrivilegePackage')->getPackageContent($property_id);
        if (in_array($application_id, $packageData['application_id'])) {
            $application['is_has'] = true;
        } else {
            $application['is_has'] = false;
        }

        $this->assign('application_detail',$application);
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

    public function qy_guide_about() {
        $bind_info = D('House_enterprise_wx_bind')->where(array('bind_id' => $this->property_id, 'bind_type' => 0))->find();
        if (!empty($bind_info)) {
            $this->error('您的物业已经绑定了企业微信');
        }
        import('@.ORG.qywx.config');
        $config = new config();
        $qyRegisterInfo = $config->qyRegister($this->property_id);
        $qyInstallInfo = $config->qyInstall($this->property_id);
        if ($qyRegisterInfo['errcode']) {
            $this->error($qyRegisterInfo['errmsg']);
        }
        if ($qyInstallInfo['errcode']) {
            $this->error($qyInstallInfo['errmsg']);
        }
        $this->assign('qyRegisterInfo', $qyRegisterInfo);
        $this->assign('qyInstallInfo', $qyInstallInfo);
        $this->display();
    }

    public function ajaxGyGuideAbout(){
        $randomNumber = trim($_POST['randomNumber']);
        $type = trim($_POST['type']);
        $property_id = $this->property_id;
        import('@.ORG.qywx.config');
        $config = new config();
        fdump_api(['ajaxGyGuideAbout-line:' . __LINE__, 'time' => microtime(true), 'randomNumber' => $randomNumber, 'type' => $type, 'property_id' => $property_id], 'qyweixin/qyGuideAboutLog', 1);
        $msg = $config->qyGuideAbout($randomNumber,$type,$property_id);
        fdump_api(['获取企业微信用户信息line:'.__LINE__,'time' => microtime(true), 'randomNumber' => $randomNumber,],'qyweixin/qyGuideAboutLog',1);
        if($msg){
            exit(json_encode(array('status'=>1,'list'=>$msg)));
        }else{
            exit(json_encode(array('status'=>0,'list'=>[])));
        }
    }
}