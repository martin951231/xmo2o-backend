<?php
/*
 * 企业微信消息
 */
class HouseEnterpriseWeixinAction extends CommonAction
{
    public function index()
    {
        $wechat = new Chat(array('wx_appid' => $this->config['wx_house_appid'], 'wx_token' => $this->config['wx_house_token'], 'wx_encodingaeskey' => $this->config['wx_house_encodingaeskey']));
        $data = $wechat->request();
        list($content, $type) = $this->reply($data);
        if ($content) {
            $wechat->response($content, $type);
        } else {
            exit();
        }
    }


    public function receive() {
        $suite_corpid         = $this->config['enterprise_wx_corpid'];
        $suite_token          = $this->config['enterprise_wx_provider_token'];
        $suite_encodingaeskey = $this->config['enterprise_wx_provider_encodingaeskey'];
        $config = array('wx_corpid' => $suite_corpid, 'wx_token' => $suite_token, 'wx_encodingaeskey' => $suite_encodingaeskey);
        $wechat = new EnterpriseChat($config);

        $data = $wechat->request();
        fdump_api(['msg' => '企业微信消息回调', 'line' => __LINE__, '_POST' => $_POST, '_REQUEST' => $_REQUEST, 'data' => $data, 'config' => $config], 'qyweixin/HouseEnterpriseWeixinActionLog', 1);
        if (isset($data['InfoType'])) {
            if ($data['InfoType'] == 'suite_ticket') {
                if (isset($data['SuiteTicket']) && $data['SuiteTicket']) {
                    $config_suiteid = D('Config')->where("`name`='enterprise_wx_provider_suiteid'")->getField('value');
                    $config = D('Config')->where("`name`='enterprise_wx_suite_ticket'")->find();
                    if ($config_suiteid && $config_suiteid == $data['SuiteId']) {
                        if ($config) {
                            D('Config')->where("`name`='enterprise_wx_suite_ticket'")->data(array('value' => $data['SuiteTicket']))->save();
                        } else {
                            $data_set = array(
                                'name' => 'enterprise_wx_suite_ticket',
                                'value' => $data['SuiteTicket'],
                                'type' => 'type=text',
                                'info' => '服务商应用 suite_ticket',
                                'desc' => '服务商应用 安全凭证suite_ticket',
                                'gid' => 22,
                                'tab_id' => 'enterprise',
                                'tab_name' => '企业微信',
                                'sort' => '-6',
                                'status' => '0'
                            );
                            D('Config')->data($data_set)->add();
                        }
                    }
                    S(C('now_city') . 'config', null);
                    exit('success');
                }
            }
            elseif ($data['InfoType'] == 'cancel_auth') {
                // 取消授权通知  服务商收到取消授权事件后，应当确保删除该企业所有相关的数据
                $where_bind = [
                    'corpid' => $data['AuthCorpId'],
                    'wx_provider_suiteid' => $data['SuiteId'],
                ];
                $bind_info = M('House_enterprise_wx_bind')->where($where_bind)->find();
                fdump_api(['企业微信删除应用消息回调' . __LINE__, $where_bind, $bind_info, M('House_enterprise_wx_bind')->_sql()], 'qyweixin/cancelAuthLog', 1);
                if (!empty($bind_info)) {
                    $set = [
                        'corpid' => $data['AuthCorpId'] . '_' . date('YmdHis'),
                        'wx_provider_suiteid' => $data['SuiteId'] . '_' . date('YmdHis'),
                        'last_time' => time(),
                        'bind_id' => 0
                    ];
                    M('House_enterprise_wx_bind')->where($where_bind)->save($set);
                    //  清空所有关联
                    if (0 == $bind_info['bind_type']) {
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
                } else {
                    // 如果当前取不到 尝试去对应表格中获取
                    $where_about = [
                        'AuthCorpId' => $data['AuthCorpId'],
                        'ServiceCorpId' => $data['SuiteId'],
                        'is_del' => 0,
                        'wx_bind_id' => ['gt', 0],
                    ];
                    $info = M('Village_qywx_guide_about')->where($where_about)->find();
                    if (!empty($info) && ($info['site_url'] || $info['domainName'])) {
                        if ($info['site_url']) {
                            $url = $info['site_url'];
                        } else {
                            $url = 'https://' . $info['domainName'];
                        }
                        import('@.ORG.qywx.config');
                        $config = new config();
                        $msg = $config->qyCancelAuth($data['AuthCorpId'], $data['SuiteId'], $info['randomNumber'], $url);
                        if (!$msg['errcode']) {
                            // 正确返回 该条信息记录删除
                            $aboutSet = [
                                'is_del' => 1,
                                'update_time' => time()
                            ];
                            M('Village_qywx_guide_about')->where($where_about)->save($aboutSet);
                        }
                    }
                }
                exit('success');
            }
            elseif ($data['InfoType'] == 'create_auth') {
                // 授权
                if ($data['SuiteId'] && $data['AuthCode']) {
                    import('@.ORG.qywx.config');
                    $config = new config();
                    $config->qyRegisterInfo($data['AuthCode'], $data['SuiteId']);
                }
                exit('success');
            }
            elseif ($data['InfoType'] == 'reset_permanent_code') {
                // 重置永久授权码通知
                if ($data['SuiteId'] && $data['AuthCode']) {
                    import('@.ORG.qywx.config');
                    $config = new config();
                    $config->qyRegisterInfo($data['AuthCode'], $data['SuiteId']);
                }
                exit('success');
            }
            elseif ($data['InfoType'] == 'change_contact') {
                // 成员通知事件
                $whereUnique = [];
                $param = [
                    'suite_corpid' => $suite_corpid,
                    'info_type' => $data['InfoType'],
                ];
                $whereUnique['suite_corpid'] = $param['suite_corpid'];
                if (isset($data['SuiteId']) && $data['SuiteId']) {
                    $param['suite_id'] = $data['SuiteId'];
                }
                if (isset($data['AuthCorpId']) && $data['AuthCorpId']) {
                    $param['auth_corpid'] = $data['AuthCorpId'];
                }
                if (isset($data['TimeStamp']) && $data['TimeStamp']) {
                    $param['time_stamp'] = $data['TimeStamp'];
                }
                if (isset($data['ChangeType']) && $data['ChangeType']) {
                    $param['change_type'] = $data['ChangeType'];
                }
                if (isset($data['UserID']) && $data['UserID']) {
                    $param['user_id'] = $data['UserID'];
                    $whereUnique['user_id'] = $param['user_id'];
                }
                if (isset($data['OpenUserID']) && $data['OpenUserID']) {
                    $param['open_user_id'] = $data['OpenUserID'];
                    $whereUnique['open_user_id'] = $param['open_user_id'];
                }
                $db_work_weixin_user = M('Work_weixin_user');
                // 如果删除 查询下是否有记录 标记删除
                $id = $db_work_weixin_user->where($whereUnique)->getField('id');
                if (isset($data['ChangeType']) && $data['ChangeType'] == 'delete_user') {
                    if ($id) {
                        $param['delete_time'] = time();
                        $db_work_weixin_user->where($whereUnique)->save($param);
                    }
                    exit('success');
                }
                if (isset($data['Name']) && $data['Name']) {
                    $param['name'] = $data['Name'];
                }
                if (isset($data['Department']) && $data['Department']) {
                    $param['department'] = json_encode($data['Department'], JSON_UNESCAPED_UNICODE);
                }
                if (isset($data['MainDepartment']) && $data['MainDepartment']) {
                    $param['main_department'] = $data['MainDepartment'];
                }
                if (isset($data['IsLeaderInDept']) && $data['IsLeaderInDept']) {
                    $param['is_leader_in_dept'] = json_encode($data['IsLeaderInDept'], JSON_UNESCAPED_UNICODE);
                }
                if (isset($data['DirectLeader']) && $data['DirectLeader']) {
                    $param['direct_leader'] = json_encode($data['DirectLeader'], JSON_UNESCAPED_UNICODE);
                }
                if (isset($data['Mobile']) && $data['Mobile']) {
                    $param['mobile'] = $data['Mobile'];
                }
                if (isset($data['Position']) && $data['Position']) {
                    $param['position'] = $data['Position'];
                }
                if (isset($data['Gender']) && $data['Gender']) {
                    $param['gender'] = $data['Gender'];
                }
                if (isset($data['Email']) && $data['Email']) {
                    $param['email'] = $data['Email'];
                }
                if (isset($data['BizMail']) && $data['BizMail']) {
                    $param['biz_mail'] = $data['BizMail'];
                }
                if (isset($data['Status']) && $data['Status']) {
                    $param['status'] = $data['Status'];
                }
                if (isset($data['Avatar']) && $data['Avatar']) {
                    $param['avatar'] = $data['Avatar'];
                }
                if (isset($data['Alias']) && $data['Alias']) {
                    $param['alias'] = $data['Alias'];
                }
                if (isset($data['Telephone']) && $data['Telephone']) {
                    $param['telephone'] = $data['Telephone'];
                }
                if (isset($data['ExtAttr']) && $data['ExtAttr']) {
                    $param['ext_attr'] = json_encode($data['ExtAttr'], JSON_UNESCAPED_UNICODE);
                }
                if ($id) {
                    $param['update_time'] = time();
                    $db_work_weixin_user->where($whereUnique)->save($param);
                } else {
                    $param['add_time'] = time();
                    $db_work_weixin_user->where($whereUnique)->save($param);
                }
            }
            elseif ($data['InfoType'] == 'change_external_contact') {
                $eventBoxParams = ['param' => $data];
                fdump_api(['eventBoxParams' => $eventBoxParams], 'qyweixin/HouseEnterpriseWeixinActionLog',true);
                $result = invoke_v20_service('\app\community\model\service\workweixin\WorkWeiXinNewService/contactWayEventBox', $eventBoxParams);
                $resultVal = isset($result['data']['retval']) && $result['data']['retval'] ? $result['data']['retval'] : [];
                fdump_api(['resultVal' => $resultVal], 'qyweixin/HouseEnterpriseWeixinActionLog',true);
            }
        } elseif ($data) {
            exit($data);
        }
        exit('success');
    }

    
    
    public function receive_putong() {
        $wechat = new EnterpriseChat(array('wx_corpid' => 'ww21001813f8f04566', 'wx_token' => 'J2NuWCX9jqm5mPP6Rm', 'wx_encodingaeskey' => 'fz25EdB8KQOfHRESfS0wOpuX9P9fvqIbxpxchhctPAn'));
        $data = $wechat->request();
        fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/enterprise_weixin_receive_log',true);
        fdump($_GET,'api/log/'.date('Ymd').'/enterprise_weixin_receive_log',true);
        fdump($_POST,'api/log/'.date('Ymd').'/enterprise_weixin_receive_log',true);
        fdump($data,'api/log/'.date('Ymd').'/enterprise_weixin_receive_log',true);
        exit;
    }

    public function get_ticket()
    {
        //获取 component_verify_ticket 每十分钟微信服务本服务器请求一次
		$wechat = new Chat(array('wx_appid' => $this->config['wx_house_appid'], 'wx_token' => $this->config['wx_house_token'], 'wx_encodingaeskey' => $this->config['wx_house_encodingaeskey']));
		$data = $wechat->request();
        if(isset($data['InfoType'])) {
            if ($data['InfoType'] == 'component_verify_ticket') {
                if (isset($data['ComponentVerifyTicket']) && $data['ComponentVerifyTicket']) {
                    if ($config = D('Config')->where("`name`='wx_house_componentverifyticket'")->find()) {
                        D('Config')->where("`name`='wx_house_componentverifyticket'")->data(array('value' => $data['ComponentVerifyTicket']))->save();
                    } else {
                        D('Config')->data(array('name' => 'wx_house_componentverifyticket', 'value' => $data['ComponentVerifyTicket'], 'type' => 'type=text', 'gid' => 0, 'tab_id' => 0))->add();
                    }
                    S(C('now_city') . 'config', null);
                    exit('success');
                }
            } elseif ($data['InfoType'] == 'unauthorized') {
                if (isset($data['AuthorizerAppid']) && $data['AuthorizerAppid']) {
                    D('Weixin_bind')->where("`authorizer_appid`='{$data['AuthorizerAppid']}' AND type=1")->delete();
                    exit('success');
                }
            }
        }
    }


    private function reply($data)
    {
        $keyword = isset($data['Content']) ? $data['Content'] : (isset($data['EventKey']) ? $data['EventKey'] : '');
        if($data['ToUserName'] == 'gh_3c884a361561'){
            if ($data['MsgType'] == 'event') {
                return array($data['Event'] . 'from_callback', 'text');
            }

            if ($keyword == 'TESTCOMPONENT_MSG_TYPE_TEXT') {
                return array('TESTCOMPONENT_MSG_TYPE_TEXT_callback', 'text');
            }
            if (strstr($keyword, 'QUERY_AUTH_CODE:')) {
                $t = explode(':', $keyword);
                $query_auth_code = $t[1];
                $access_token = $this->get_access_token($query_auth_code);
                $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $access_token;
                $str = '{"touser":"'. $data['FromUserName'] .'", "msgtype":"text", "text":{"content":"' . $query_auth_code . '_from_api"}}';
                import('ORG.Net.Http');
                Http::curlPost($url, $str);
            }
        }

        $mer_id = 0;
        if ($bind = D('Weixin_bind')->where(array('user_name' => $data['ToUserName'], 'type' => 1))->find()) {
            $mer_id = $bind['mer_id'];
        } else {
            return false;
        }

        if ($data['MsgType'] == 'event') {
            $id = $data['EventKey'];
            switch (strtoupper($data['Event'])) {
                case 'SCAN':
// 		    		return $this->scan($id, $data['FromUserName'], $mer_id);
                    break;
                case 'CLICK':
                    $return = $this->special_keyword($id, $data, $mer_id);
                    return $return;
                    break;
                case 'SUBSCRIBE':
                    $this->route();
                    if (isset($data['Ticket'])) {
                        $id = substr($data['EventKey'], 8);
                        return $this->scan($id, $data['FromUserName'], $mer_id);
                    }
                    if ($mer_id) {
                        if ($other = D('House_reply_other')->where(array('type' => 0, 'mer_id' => $mer_id))->find()) {
                            if ($other['reply_type'] == 0) {
                                return array($other['content'], 'text');
                            } else {
                                return $this->txt_img($other['from_id'], $mer_id);
                            }
                        } else {
                            return array("感谢您的关注，我们将竭诚为您服务！", 'text');
                        }
                    }
                    return array("感谢您的关注，我们将竭诚为您服务！", 'text');
                    break;
                case 'UNSUBSCRIBE':
                    $this->route();
                    return array("BYE-BYE", 'text');
                    break;
                case 'LOCATION':
                    break;
                default:
                    //return array("亲，此号暂停测试，请搜索【pigcms】进行关注测试", 'text');
            }
        } elseif ($data['MsgType'] == 'text') {
            $content = $data['Content'];
            if (strtolower($content) == 'go') {
                $t_data = $this->route();
// 				header("Content-type: text/xml");
// 				exit($t_data);
            }
            $return = $this->special_keyword($content, $data, $mer_id);
            return $return;
        } elseif ($data['MsgType'] == 'image' && $mer_id) {
            if ($other = D('House_reply_other')->where(array('type' => 2, 'mer_id' => $mer_id))->find()) {
                if ($other['reply_type'] == 0) {
                    return array($other['content'], 'text');
                } else {
                    return $this->txt_img($other['from_id'], $mer_id);
                }
            } else {
                return false;
            }
        }
        return false;
    }


    private function txt_img($pigcms_id, $mer_id)
    {
        if ($data = D('House_source_material')->where(array('pigcms_id' => $pigcms_id, 'mer_id' => $mer_id))->find()) {
            $it_ids = unserialize($data['it_ids']);
            if ($data['type'] == 0) {
                $id = isset($it_ids[0]) ? intval($it_ids[0]) : 0;
                $image_text = D('House_image_text')->where(array('pigcms_id' => $id))->find();
                $url = $this->config['site_url'] . '/wap.php?g=Wap&c=Article&a=hdetail&imid=' . $image_text['pigcms_id'];
                $return[] = array($image_text['title'], $image_text['digest'], $this->config['site_url'] . $image_text['cover_pic'], $url);
            } else {
                $image_texts = D('House_image_text')->where(array('pigcms_id' => array('in', $it_ids)))->select();
                foreach ($image_texts as $image) {
                    $url = $this->config['site_url'] . '/wap.php?g=Wap&c=Article&a=hdetail&imid=' . $image['pigcms_id'];
                    $return[] = array($image['title'], $image['digest'], $this->config['site_url'] . $image['cover_pic'], $url);
                }
            }
            return array($return, 'news');
        } else {
            return array("没有找到相关的应答", 'text');
        }
    }


    private function special_keyword($key, $data = array(), $mer_id = 0)
    {
        $return = array();
        //关键字回复
        if ($mer_id) {
            if ($keyobj = D('House_keyword')->where(array('mer_id' => $mer_id, 'content' => $key))->find()) {
                if ($keyobj['table'] == 'House_text') {
                    $text = D('House_text')->where(array('pigcms_id' => $keyobj['from_id']))->find();
                    return array($text['content'], 'text');
                } else {
                    return $this->txt_img($keyobj['from_id'], $mer_id);
                }
            } elseif ($other = D('House_reply_other')->where(array('type' => 1, 'mer_id' => $mer_id, 'is_open' => 1))->find()) {
                if ($other['reply_type'] == 0) {
                    return array($other['content'], 'text');
                } else {
                    return $this->txt_img($other['from_id'], $mer_id);
                }
            }
        }
        return array('亲，暂时没有找到与“' . $key . '”相关的内容！请更换内容。', 'text');

    }

    //连接路由操作
    private function route()
    {
        $nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : '';
        $sTimeStamp = isset($_REQUEST['timestamp']) ? $_REQUEST['timestamp'] : time();
        $msg_signature = isset($_REQUEST['msg_signature']) ? $_REQUEST['msg_signature'] : '';
        $xml = file_get_contents("php://input");


        import("@.ORG.aes.WXBizMsgCrypt");
        $pc = new WXBizMsgCrypt($this->config['wx_house_token'], $this->config['wx_house_encodingaeskey'], $this->config['wx_house_appid']);
        $sMsg = "";
        $pc->decryptMsg($msg_signature, $sTimeStamp, $nonce, $xml, $sMsg);

        $data = $this->api_notice_increment('http://we-cdn.net', $sMsg);
        $data = str_replace('<?xml version="1.0"?>', '', $data);
        $encryptMsg = "";
        $pc->encryptMsg($data, $sTimeStamp, $nonce, $encryptMsg);
        return $encryptMsg;
    }


    private function api_notice_increment($url, $data)
    {
        $ch = curl_init();
        $headers = array(
            "User-Agent: Mozilla/5.0 (Windows NT 5.1; rv:14.0) Gecko/20100101 Firefox/14.0.1",
            "Accept-Language: en-us,en;q=0.5",
            "Referer:http://mp.weixin.qq.com/",
            'Content-type: text/xml'
        );
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        curl_close($ch);
        if (curl_errno($ch)) {
            return false;
        } else {
            return $tmpInfo;
        }
    }

    /**
     * Notes: 【联系我】 客户加入时候消息通知
     * @author: wanzy
     * @date_time: 2021/1/29 11:42
     */
    public function contact_way_receive() {
        fdump_api(['$_REQUEST' => $_REQUEST, 'tip' => '微信消息通知'], 'qyweixin/contact_way_receive_log',true);

        $id = $_REQUEST['id'];
        if ($id) {
            $db_house_enterprise_wx_bind = M('House_enterprise_wx_bind');
            $where_config = [
                'bind_type' => 0,
                'bind_id' => $id
            ];
            $bind_config = $db_house_enterprise_wx_bind->where($where_config)->find();
            empty($bind_config) && $id = 0;
        }
        if ($id) {
            if (!isset($bind_config) || empty($bind_config)) {
                $db_house_enterprise_wx_bind = M('House_enterprise_wx_bind');
                $where_config = [
                    'bind_type' => 0,
                    'bind_id' => $id
                ];
                $bind_config = $db_house_enterprise_wx_bind->where($where_config)->find();
            }
            $corpid =  isset($bind_config['PlaintextCorpId'])&&trim($bind_config['PlaintextCorpId'])?trim($bind_config['PlaintextCorpId']):trim($bind_config['corpid']);
            $contact_way_token =  trim($bind_config['contact_way_token']);
            $contact_way_encodingaeskey =  trim($bind_config['contact_way_encodingaeskey']);
            fdump_api(['$bind_config' => $bind_config, '$corpid' => $corpid, '$contact_way_token' => $contact_way_token, '$contact_way_encodingaeskey' => $contact_way_encodingaeskey, ], 'qyweixin/contact_way_receive_log',true);
            $wechat = new EnterpriseChat(array('wx_corpid' => $corpid, 'wx_token' => $contact_way_token, 'wx_encodingaeskey' => $contact_way_encodingaeskey));
        } else {
            $wx_corpid = $this->config['enterprise_wx_corpid'];
            $wx_token = $this->config['contact_way_token'];
            $wx_encodingaeskey = $this->config['contact_way_encodingaeskey'];
            fdump_api(['$wx_corpid' => $wx_corpid, '$wx_token' => $wx_token, '$wx_encodingaeskey' => $wx_encodingaeskey, ], 'qyweixin/contact_way_receive_log',true);
            $wechat = new EnterpriseChat(array('wx_corpid' => $wx_corpid, 'wx_token' => $wx_token, 'wx_encodingaeskey' => $wx_encodingaeskey));
        }
        $data = $wechat->request();
        fdump_api(['data' => $data], 'qyweixin/contact_way_receive_log',true);
        if(isset($data['MsgType'])) {
            if ('event'==$data['MsgType']) {
                if ($id) {
                    $data['property_id'] = $id;
                }
                if (isset($data['SuiteId']) && $data['SuiteId']) {
                    $eventBoxParams = ['param' => $data];
                    $result = invoke_v20_service('\app\community\model\service\workweixin\WorkWeiXinNewService/contactWayEventBox', $eventBoxParams);
                    $resultVal = isset($result['data']['retval']) && $result['data']['retval'] ? $result['data']['retval'] : [];
                    fdump_api(['resultVal' => $resultVal], 'qyweixin/contact_way_receive_log',true);
                } else {
                    $this->contact_way_event($data);
                }
            }
            exit('success');
        } elseif ($data) {
            if ($id && $bind_config) {
                $where_config = [
                    'bind_type' => 0,
                    'bind_id' => $id
                ];
                $set_data = [
                    'contact_way_time' => time()
                ];
                $db_house_enterprise_wx_bind->where($where_config)->save($set_data);
            }
            exit($data);
        }
        exit('success');
    }

    public function contact_way_event($data) {
        $db_house_worker = M('House_worker');
        $db_village_qywx_data_center = M('Village_qywx_data_center');
        $db_village_qywx_action_log = M('Village_qywx_action_log');
        $db_house_village = M('House_village');
        switch ($data['ChangeType']) {
            case 'add_external_contact':
                $ExternalUserID = $data['ExternalUserID'];
                $UserID = $data['UserID'];
                // 客户加入反馈消息
                $add_data = [];
                $third_suite_id = '';
                if (isset($data['SuiteId']) && $data['SuiteId']) {
                    // 第三方应用ID
                    $add_data['third_suite_id'] = $data['SuiteId'];
                    $third_suite_id = $data['SuiteId'];
                } else {
                    $add_data['third_suite_id'] = $this->config['enterprise_wx_provider_suiteid'];
                }
                if (isset($data['AuthCorpId']) && $data['AuthCorpId']) {
                    // 授权企业的CorpID
                    $add_data['corpid'] = $data['AuthCorpId'];
                }
                if (isset($data['ToUserName']) && $data['ToUserName']) {
                    $add_data['corpid'] = $data['ToUserName'];
                }
                if (isset($data['FromUserName']) && $data['FromUserName']) {
                    $add_data['from'] = $data['FromUserName'];
                }
                if (isset($data['TimeStamp']) && $data['TimeStamp']) {
                    $add_data['CreateTime'] = $data['TimeStamp'];
                }
                if (isset($data['CreateTime']) && $data['CreateTime']) {
                    $add_data['CreateTime'] = $data['CreateTime'];
                }
                if (isset($data['MsgType']) && $data['MsgType']) {
                    $add_data['MsgType'] = $data['MsgType'];
                }
                if (isset($data['InfoType']) && $data['InfoType']) {
                    $add_data['Event'] = $data['InfoType'];
                }
                if (isset($data['Event']) && $data['Event']) {
                    $add_data['Event'] = $data['Event'];
                }
                if ($data['ChangeType']) {
                    $add_data['ChangeType'] = $data['ChangeType'];
                }
                if ($data['UserID']) {
                    $add_data['UserID'] = $data['UserID'];
                }
                $externalUserID = '';
                if ($data['ExternalUserID']) {
                    $externalUserID = $data['ExternalUserID'];
                    $add_data['ExternalUserID'] = $data['ExternalUserID'];
                }
                // 是否发送欢迎语
                $is_send = true;
                fdump_api(['title' => '接收数据初步整理','data' => $data, 'add_data' => $add_data], 'qyweixin/contact_way_receive_log',true);
                if ($data['State']) {
                    $add_data['State'] = $data['State'];
                    $state_arr = explode('|', $data['State']);
                    if ($state_arr[0]){
                        $key_type = explode('#', $state_arr[0]);
                        $code_id = 0;
                        $housekeeper_id = 0;
                        if ($key_type[0]=='housekeeper' && $key_type[1]) {
                            // 来源于 楼栋单元管家
                            $housekeeper_id = $key_type[1];
                            $add_data['housekeeper_id'] = $housekeeper_id;
                            $add_data['business_id']    = $housekeeper_id;
                            $add_data['business_type']  = 'house_services_housekeeper';
                        } elseif ($key_type[0]=='channelCode' && $key_type[1]) {
                            $code_id = $key_type[1];
                            // 来源于渠道活码
                            $add_data['business_id']   = $code_id;
                            $add_data['business_type'] = 'village_qywx_channel_code';
                        } elseif($key_type[0] && !$key_type[1]) {
                            // 默认来源于 楼栋单元管家
                            $housekeeper_id = $key_type[0];
                            $add_data['housekeeper_id'] = $housekeeper_id;
                            $add_data['business_id']    = $housekeeper_id;
                            $add_data['business_type']  = 'house_services_housekeeper';
                        }

                        fdump_api(['title' => '接收数据2次整理','data' => $data, 'housekeeper_id' => $housekeeper_id, '$code_id' => $code_id, '$state_arr' => $state_arr], 'qyweixin/contact_way_receive_log',true);
                        if ($housekeeper_id>0) {
                            $db_house_services_housekeeper = M('House_services_housekeeper');
                            $where_housekeeper = [
                                'housekeeper_id' => $add_data['housekeeper_id']
                            ];
                            $housekeeper_info = $db_house_services_housekeeper->where($where_housekeeper)->find();
                            if ($housekeeper_info) {
                                $add_data['village_id'] = $housekeeper_info['village_id']?$housekeeper_info['village_id']:0;
                                $add_data['single_id'] = $housekeeper_info['single_id']?$housekeeper_info['single_id']:0;
                                $add_data['floor_id'] = $housekeeper_info['floor_id']?$housekeeper_info['floor_id']:0;
                            }
                            $property_id = $housekeeper_info['property_id'] ? $housekeeper_info['property_id'] : 0;
                            $village_id = $housekeeper_info['village_id'] ? $housekeeper_info['village_id'] : 0;
                        } elseif ($code_id) {
                            $db_village_qywx_channel_code = M('Village_qywx_channel_code');
                            $where_channel_code = [
                                'code_id' => $code_id
                            ];
                            $code_info = $db_village_qywx_channel_code->where($where_channel_code)->field('code_id, village_id, property_id, is_send, label_txt')->find();
                            $add_data['village_id'] = $code_info['village_id'] ? $code_info['village_id'] : 0;
                            $add_data['property_id'] = $code_info['property_id'] ? $code_info['property_id'] : 0;

                            if (2==$code_info['is_send']) {
                                $is_send = false;
                            }
                            // 客户数累计加1
                            $db_village_qywx_channel_code->where($where_channel_code)->setInc('customer_num', 1);
                            $property_id = $code_info['property_id'] ? $code_info['property_id'] : 0;
                            $village_id = $code_info['village_id'] ? $code_info['village_id'] : 0;
                            fdump_api(['title' => '渠道码','code_info' => $code_info], 'qyweixin/contact_way_receive_log',true);
                        }
                    }
                    fdump_api(['title' => '接收数据3次整理','data' => $data,'$add_data' => $add_data], 'qyweixin/contact_way_receive_log',true);
                    if ($state_arr[1]){
                        $key_value = explode('#', $state_arr[1]);
                        $key = $key_value[0];
                        if ($key && !$add_data[$key] && $key_value[1]) {
                            $add_data[$key] = $key_value[1];
                        }
                    }
                }
                if ($data['WelcomeCode']) {
                    $add_data['WelcomeCode'] = $data['WelcomeCode'];
                }
                $add_data['status'] = 1;
                $add_data['add_time'] = time();
                $db_house_contact_way_user = M('House_contact_way_user');
                if (!$property_id && isset($data['property_id']) && $data['property_id']) {
                    $property_id = $data['property_id'];
                }
                if (!$property_id && isset($data['AuthCorpId']) && $data['AuthCorpId'] && $third_suite_id) {
                    $property_id = D('House_enterprise_wx_bind')->where(array('corpid' => $data['AuthCorpId'], 'bind_type' => 0,'wx_provider_suiteid'=>$third_suite_id))->getField('bind_id');
                }
                if (! $add_data['property_id'] && $property_id) {
                    $add_data['property_id'] = $property_id;
                }

                if ($third_suite_id && $externalUserID) {
                    fdump_api([
                        'title' => '服务商【联系我】客户扫码反馈-获取客户详情','data' => $data,'add_data' => $add_data,'property_id' => $property_id,'village_id' => $village_id, 'externalUserID' => $externalUserID,
                    ], 'qyweixin/contact_way_receive_log',true);
                    // 服务商【联系我】客户扫码反馈
                    $param = [
                        'external_userid' => $externalUserID,
                        'property_id' => $property_id,
                        'village_id' => $village_id,
                    ];
                    $result = invoke_v20_service('\app\community\model\service\QywxService/serviceCgiBinExternalContactGet', $param);
                    $user_msg = isset($result['data']['retval']) && $result['data']['retval'] ? $result['data']['retval'] : [];
                    fdump_api([
                        'title' => '获取客户详情-结果','result' => $result,'user_msg' => $user_msg,
                    ], 'qyweixin/contact_way_receive_log',true);
                }
                if (! $third_suite_id && $externalUserID) {
                    fdump_api([
                        'data' => $data,'$add_data' => $add_data,'$property_id' => $property_id,'$village_id' => $village_id, 'externalUserID' => $externalUserID,
                    ], 'qyweixin/contact_way_receive_old_log',true);
                    $user_msg = D('Property_group')->externalContactUser($externalUserID,$property_id,$village_id);
                    fdump_api([
                        'title' => '获取客户详情-结果','user_msg' => $user_msg
                    ], 'qyweixin/contact_way_receive_old_log',true);
                }
                if (isset($user_msg) && !empty($user_msg)) {
                    if ($user_msg['name']) {
                        $add_data['name'] = $user_msg['name'];
                    }
                    if ($user_msg['type']) {
                        $add_data['type'] = $user_msg['type'];
                    }
                    if ($user_msg['avatar']) {
                        $add_data['avatar'] = $user_msg['avatar'];
                    }
                    if ($user_msg['gender']) {
                        $add_data['gender'] = $user_msg['gender'];
                    }
                }
                fdump_api(['title' => '记录前整理结果','add_data' => $add_data], 'qyweixin/contact_way_receive_log',true);
                $customer_id = $db_house_contact_way_user->data($add_data)->add();
                fdump_api(['title' => '记录后', 'customer_id' => $customer_id, 'is_send' => $is_send], 'qyweixin/contact_way_receive_log',true);
                if ($customer_id) {
                    // 发送的话去调用 发送欢迎语
                    if ($is_send && $add_data['WelcomeCode'] && 'village_qywx_channel_code'==$add_data['business_type']) {
                        $add_data['customer_id'] = $customer_id;
                        D('Property_group')->send_welcome_msg($add_data);
                    } elseif ($is_send && $add_data['WelcomeCode'] && ($add_data['housekeeper_id'] || $add_data['village_id'] || $add_data['single_id'] || $add_data['floor_id'])) {
                        $where_housekeeper = [];
                        if (intval($add_data['housekeeper_id'])) {
                            $housekeeper_id = intval($add_data['housekeeper_id']);
                        } elseif (!$housekeeper_info && intval($add_data['village_id'])) {
                            $where_housekeeper=[
                                'village_id' => intval($add_data['village_id']),
                                'single_id' => 0,
                                'floor_id' => 0,
                            ];
                        } elseif (!$housekeeper_info && intval($add_data['single_id'])) {
                            $where_housekeeper=[
                                'single_id' => intval($add_data['single_id']),
                                'floor_id' => 0,
                            ];
                        } elseif (!$housekeeper_info && intval($add_data['floor_id'])) {
                            $where_housekeeper=[
                                'floor_id' => intval($add_data['floor_id']),
                            ];
                        }
                        $add_data['customer_id'] = $customer_id;
                        D('Property_group')->send_welcome_msg($add_data,$housekeeper_id,$where_housekeeper,$housekeeper_info);
                    }
                    if ($code_info && !empty($code_info['label_txt'])) {
                        // 渠道活码存在标签
                        $param = [
                            'type' => 'code_mark_user',
                            'code_id' => $code_info['code_id'],
                            'userid' => $add_data['UserID'],
                            'external_userid' => $add_data['ExternalUserID'],
                            'property_id' => $add_data['property_id'],
                            'customer_id' => $customer_id,
                            'unique_id' =>  'code_mark_user_'.$add_data['property_id'].'_'.$customer_id.'_'.$code_info['code_id'],
                            'plan_time' => -10,
                        ];
                        fdump_api(['tip' => '渠道活码存在标签', '$code_info' => $code_info, '$param' => $param], 'qyweixin/contact_way_receive_log',true);
                        D('Property_group')->add_plan($param);
                    }
                } else {
                    fdump_api(['tip' => '微信消息记录错误', '$add_data' => $add_data, '$data' => $data, 'getDbError' => $db_house_contact_way_user->getDbError()], 'qyweixin/contact_way_receive_log',true);
                }
                $worker_info = $db_house_worker->where(['is_del'=>0,'qy_id'=>$UserID])->find();
                fdump_api(['tip' => '获取工作人员信息', '$UserID' => $UserID, '$worker_info' => $worker_info], 'qyweixin/contact_way_receive_log',true);
                if ($worker_info) {
                    $year = date('Y', time());
                    $month = date('Y-m', time());
                    $day = date('Y-m-d', time());
                    $data_center_info = $db_village_qywx_data_center->where(['wid' => $worker_info['wid'], 'Y' => $year, 'M' => $month, 'D' => $day])->find();
                    if ($data_center_info) {
                        $db_village_qywx_data_center->where(['wid' => $worker_info['wid'], 'Y' => $year, 'M' => $month, 'D' => $day])->setInc('new_non_residents_num');
                        $db_village_qywx_data_center->where(['wid' => $worker_info['wid'], 'Y' => $year, 'M' => $month, 'D' => $day])->setInc('apply_friends_num');
                        $db_village_qywx_action_log->add([
                            'center_id' => $data_center_info['id'], 'add_type' => $data_center_info['add_type'], 'wid' => $data_center_info['wid'],
                            'user_id' => $UserID, 'external_user_id' => $ExternalUserID, 'action_name' => 'NewNonResidents',
                            'property_id' => !empty($data_center_info['property_id']) ? $data_center_info['property_id'] : 0,
                            'village_id' => !empty($data_center_info['village_id']) ? $data_center_info['village_id'] : 0,
                            'create_time' => time()
                        ]);
                        $db_village_qywx_action_log->add([
                            'center_id' => $data_center_info['id'], 'add_type' => $data_center_info['add_type'], 'wid' => $data_center_info['wid'],
                            'user_id' => $UserID, 'external_user_id' => $ExternalUserID, 'action_name' => 'ApplyFriends',
                            'property_id' => !empty($data_center_info['property_id']) ? $data_center_info['property_id'] : 0,
                            'village_id' => !empty($data_center_info['village_id']) ? $data_center_info['village_id'] : 0,
                            'create_time' => time()
                        ]);
                    } else {
                        $property_id = 0;
                        if (!empty($worker_info['village_id'])) {
                            $property_id = $db_house_village->where(['village_id' => $worker_info['village_id']])->getField('property_id');
                        }
                        $id = $db_village_qywx_data_center->add([
                            'add_type' => !empty($worker_info['village_id']) ? 1 : 2, 'wid' => $worker_info['wid'],
                            'user_id' => $UserID, 'Y' => $year, 'M' => $month, 'D' => $day,
                            'new_non_residents_num' => 1, 'apply_friends_num' => 1,
                            'property_id' => !empty($worker_info['property_id']) ? $worker_info['property_id'] : $property_id,
                            'village_id' => !empty($worker_info['village_id']) ? $worker_info['village_id'] : 0
                        ]);
                        $db_village_qywx_action_log->add([
                            'center_id' => $id, 'add_type' => !empty($worker_info['village_id']) ? 1 : 2, 'wid' => $worker_info['wid'],
                            'user_id' => $UserID, 'external_user_id' => $ExternalUserID, 'action_name' => 'NewNonResidents',
                            'property_id' => !empty($worker_info['property_id']) ? $worker_info['property_id'] : $property_id,
                            'village_id' => !empty($worker_info['village_id']) ? $worker_info['village_id'] : 0,
                            'create_time' => time()
                        ]);
                        $db_village_qywx_action_log->add([
                            'center_id' => $id, 'add_type' => !empty($worker_info['village_id']) ? 1 : 2, 'wid' => $worker_info['wid'],
                            'user_id' => $UserID, 'external_user_id' => $ExternalUserID, 'action_name' => 'ApplyFriends',
                            'property_id' => !empty($worker_info['property_id']) ? $worker_info['property_id'] : $property_id,
                            'village_id' => !empty($worker_info['village_id']) ? $worker_info['village_id'] : 0,
                            'create_time' => time()
                        ]);
                    }
                }
                break;
            case 'del_follow_user':
                $ExternalUserID = $data['ExternalUserID'];
                $UserID = $data['UserID'];
                $ToUserName = $data['ToUserName'];
                $db_house_contact_way_user = M('House_contact_way_user');
                $where = [
                    'corpid' => $ToUserName,
                    'ExternalUserID' => $ExternalUserID,
                    'UserID' => $UserID,
                    'status' => ['neq', 2]
                ];
                $contact_way_user = $db_house_contact_way_user->where($where)->find();
                if ($contact_way_user) {
                    $set = [
                        'status' => 2,
                        'last_time' => time(),
                    ];
                    $db_house_contact_way_user->where($where)->data($set)->save();
                }
                $worker_info = $db_house_worker->where(['is_del'=>0,'qy_id'=>$UserID])->find();
                if($worker_info){
                    $year = date('Y',time());
                    $month = date('Y-m',time());
                    $day = date('Y-m-d',time());
                    $data_center_info = $db_village_qywx_data_center->where(['wid'=>$worker_info['wid'],'Y'=>$year,'M'=>$month,'D'=>$day])->find();
                    if($data_center_info){
                        $db_village_qywx_data_center->where(['wid'=>$worker_info['wid'],'Y'=>$year,'M'=>$month,'D'=>$day])->setInc('block_num');
                        $db_village_qywx_action_log->add([
                            'center_id'=>$data_center_info['id'],
                            'add_type'=>$data_center_info['add_type'],
                            'wid'=>$data_center_info['wid'],
                            'user_id'=>$UserID,
                            'external_user_id'=>$ExternalUserID,
                            'action_name'=>'Block',
                            'property_id'=>!empty($data_center_info['property_id'])?$data_center_info['property_id']:0,
                            'village_id'=>!empty($data_center_info['village_id'])?$data_center_info['village_id']:0,
                            'create_time'=>time()
                        ]);
                    }else{
                        $property_id = 0;
                        if(!empty($worker_info['village_id'])){
                            $property_id = $db_house_village->where(['village_id'=>$worker_info['village_id']])->getField('property_id');
                        }
                        $id = $db_village_qywx_data_center->add([
                            'add_type'=>!empty($worker_info['village_id'])?1:2,
                            'wid'=>$worker_info['wid'],
                            'user_id'=>$UserID,
                            'Y'=>$year,
                            'M'=>$month,
                            'D'=>$day,
                            'block_num'=>1,
                            'property_id'=>!empty($worker_info['property_id'])?$worker_info['property_id']:$property_id,
                            'village_id'=>!empty($worker_info['village_id'])?$worker_info['village_id']:0
                        ]);
                        $db_village_qywx_action_log->add([
                            'center_id'=>$id,
                            'add_type'=>!empty($worker_info['village_id'])?1:2,
                            'wid'=>$worker_info['wid'],
                            'user_id'=>$UserID,
                            'external_user_id'=>$ExternalUserID,
                            'action_name'=>'Block',
                            'property_id'=>!empty($worker_info['property_id'])?$worker_info['property_id']:$property_id,
                            'village_id'=>!empty($worker_info['village_id'])?$worker_info['village_id']:0,
                            'create_time'=>time()
                        ]);
                    }
                }
                break;
            default:
        }
    }

    public function contact_way_invitation() {
        if(empty($this->user_session)){
            if($this->is_app_browser){
                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                $this->error_tips('请先进行登录！',U('Login/index',$location_param));
            }else{
                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                redirect(U('Login/index',$location_param));
            }
        }
        $customer_id = I('customer_id',0,'intval');
        $welcome_code = I('welcome_code','','strval');
        $pigcms_id = I('pigcms_id','','intval');
        $uid = $this->user_session['uid'];
        $db_house_contact_way_user = M('House_contact_way_user');
        $db_house_village_user_bind = M('House_village_user_bind');
        $db_house_services_housekeeper = M('House_services_housekeeper');
        $where = [];
        if ($customer_id) {
            $where['customer_id'] = $customer_id;
        }
        if ($welcome_code) {
            $where['WelcomeCode'] = $welcome_code;
        }
        $contact_way_user = $db_house_contact_way_user->where($where)->order('customer_id ASC')->find();
        $where_housekeeper = [
            'housekeeper_id' => $contact_way_user['housekeeper_id']
        ];
        $housekeeper_info = $db_house_services_housekeeper->where($where_housekeeper)->find();
        if ($pigcms_id) {
            $where_bind['pigcms_id'] = $pigcms_id;
        } elseif ($contact_way_user['room_id']) {
            $room_id = intval($contact_way_user['room_id']);
            $where_bind = [
                'vacancy_id' => intval($contact_way_user['room_id']),
                'uid' => $uid,
                'type' => ['in', [0,1,2,3]],
                'status' => ['in', [1,2]],
            ];
        } else {
            $where_bind = [
                'vacancy_id' => ['gt', 0],
                'uid' => $uid,
                'type' => ['in', [0,1,2,3]],
                'status' => ['in', [1,2]],
            ];
            if ($housekeeper_info['single_id']) {
                $where_bind['single_id'] = $housekeeper_info['single_id'];
            }
            if ($housekeeper_info['floor_id']) {
                $where_bind['floor_id'] = $housekeeper_info['floor_id'];
            }
        }
        if ($contact_way_user['village_id']) {
            $where_bind['village_id'] = $contact_way_user['village_id'];
        }
        $field = 'pigcms_id, uid, village_id, type, single_id, floor_id, layer_id, vacancy_id,address';
        $user_bind = $db_house_village_user_bind->where($where_bind)->field($field)->order('status ASC,pigcms_id ASC')->find();
        if ($user_bind) {
            $room_id = $user_bind['vacancy_id'];
        }
        if ($user_bind) {
            $set = [
                'bind_id' => $user_bind['pigcms_id'],
                'uid' => $uid,
                'last_time' => time(),
            ];
            if ($this->user_session['phone']) {
                $set['phone'] = $this->user_session['phone'];
            }
            $db_house_contact_way_user->where($where)->data($set)->save();
            if (0==$housekeeper_info['template_type']) {
                $housekeeper_info['housekeeper_url'] = replace_file_domain($housekeeper_info['qy_qrcode']);
            } else {
                if ($housekeeper_info['effect_img']) {
                    $housekeeper_info['housekeeper_url'] = replace_file_domain($housekeeper_info['effect_img']);
                } else {
                    $housekeeper_info['housekeeper_url'] = replace_file_domain($housekeeper_info['qy_qrcode']);
                }
            }
            if ($housekeeper_info['floor_id']>0) {
                $where_floor = [
                    'floor_id' => $housekeeper_info['floor_id']
                ];
                $floor_name = M('House_village_floor')->where($where_floor)->getField('floor_name');
            }
            if ($housekeeper_info['single_id']>0) {
                $where_floor = [
                    'id' => $housekeeper_info['single_id']
                ];
                $single_name = M('House_village_single')->where($where_floor)->getField('single_name');
            }
            $title = "加入";
            if ($single_name) {
                $title .= $single_name;
            }
            if ($floor_name) {
                $title .= $floor_name;
            }
            if (!$single_name && !$floor_name) {
                $title .= '物业';
            }
            $title .= '管家服务群';

            $is_choose = 1;
            $this->assign('is_choose',$is_choose);
            $this->assign('user_bind',$user_bind);


            // 添加打标签
            set_time_limit(0);
            ini_set('memory_limit', '2048M');
            D('Property_group')->mark_contact_way_tag($user_bind, $contact_way_user,$housekeeper_info);

            $this->assign('housekeeper_info',$housekeeper_info);
            if($contact_way_user['UserId']){
                $ExternalUserID = $contact_way_user['ExternalUserID'];
                $db_house_worker = M('House_worker');
                $db_village_qywx_data_center = M('Village_qywx_data_center');
                $db_village_qywx_action_log = M('Village_qywx_action_log');
                $db_house_village = M('House_village');
                $UserID = $contact_way_user['UserId'];
                $worker_info = $db_house_worker->where(['is_del'=>0,'qy_id'=>$UserID])->find();
                if($worker_info){
                    $year = date('Y',time());
                    $month = date('Y-m',time());
                    $day = date('Y-m-d',time());
                    $data_center_info = $db_village_qywx_data_center->where(['wid'=>$worker_info['wid'],'Y'=>$year,'M'=>$month,'D'=>$day])->find();
                    if($data_center_info){
                        $db_village_qywx_data_center->where(['wid'=>$worker_info['wid'],'Y'=>$year,'M'=>$month,'D'=>$day])->setInc('new_house_holds_num');
                        $db_village_qywx_action_log->add([
                            'center_id'=>$data_center_info['id'],
                            'add_type'=>$data_center_info['add_type'],
                            'wid'=>$data_center_info['wid'],
                            'user_id'=>$UserID,
                            'external_user_id'=>$ExternalUserID,
                            'action_name'=>'NewHouseholds',
                            'property_id'=>!empty($data_center_info['property_id'])?$data_center_info['property_id']:0,
                            'village_id'=>!empty($data_center_info['village_id'])?$data_center_info['village_id']:0,
                            'create_time'=>time()
                        ]);
                    }else{
                        $property_id = 0;
                        if(!empty($worker_info['village_id'])){
                            $property_id = $db_house_village->where(['village_id'=>$worker_info['village_id']])->getField('property_id');
                        }
                        $id = $db_village_qywx_data_center->add([
                            'add_type'=>!empty($worker_info['village_id'])?1:2,
                            'wid'=>$worker_info['wid'],
                            'user_id'=>$UserID,
                            'Y'=>$year,
                            'M'=>$month,
                            'D'=>$day,
                            'new_house_holds_num'=>1,
                            'property_id'=>!empty($worker_info['property_id'])?$worker_info['property_id']:$property_id,
                            'village_id'=>!empty($worker_info['village_id'])?$worker_info['village_id']:0
                        ]);
                        $db_village_qywx_action_log->add([
                            'center_id'=>$id,
                            'add_type'=>!empty($worker_info['village_id'])?1:2,
                            'wid'=>$worker_info['wid'],
                            'user_id'=>$UserID,
                            'external_user_id'=>$ExternalUserID,
                            'action_name'=>'NewHouseholds',
                            'property_id'=>!empty($worker_info['property_id'])?$worker_info['property_id']:$property_id,
                            'village_id'=>!empty($worker_info['village_id'])?$worker_info['village_id']:0,
                            'create_time'=>time()
                        ]);
                    }
                }
            }
//            $params = [
//                'pigcms_id' => $pigcms_id,
//                'customer_id' => $customer_id,
//                'housekeeper_id' => $housekeeper_info['housekeeper_id'],
//            ];
//            $result = invoke_v20_service('\app\community\model\service\workweixin\WorkWeiXinNewService/markHousekeeperTagJob', $params);
//            $resultVal = isset($result['data']['retval']) && $result['data']['retval'] ? $result['data']['retval'] : [];
        } else {
            $set = [
                'uid' => $uid,
                'last_time' => time(),
            ];
            if ($this->user_session['phone']) {
                $set['phone'] = $this->user_session['phone'];
            }
            $db_house_contact_way_user->where($where)->data($set)->save();

            $db_house_village = D('House_village');
            $app_version = intval($_POST['app_version']);
            $deviceId = trim($_POST['Device-Id']);
            $param = [
                'pageName' => 'joinHouse',
                'isAll' => true,
                'urlParam' => "village_id={$contact_way_user['village_id']}",
            ];
            $joinHouseUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);

            if (0 && $room_id) {
                $joinHouseUrl .= "pigcms_id={$room_id}";
            }
            $this->assign('redirect_url',$joinHouseUrl);
        }
        $this->assign('title',$title);
        $this->display();
    }

    // 	https://shequ-demo.fastwhale.com.cn/wap.php?g=Wap&c=HouseEnterpriseWeixin&a=service_provider_receive
    public function service_provider_receive() {
        fdump_api(['msg' => '服务商微信消息通知>', 'line' => __LINE__,'_REQUEST' => $_REQUEST],'qyweixin/service_provider_receive_log',1);
        $enterprise_wx_corpid = $this->config['enterprise_wx_corpid'];
        $wx_token = $this->config['service_provider_token'];
        $wx_encodingaeskey = $this->config['service_provider_encodingaeskey'];
        fdump_api(['msg' => '服务商微信消息通知>', 'line' => __LINE__, 'enterprise_wx_corpid' => $enterprise_wx_corpid, 'wx_token' => $wx_token, 'wx_encodingaeskey' => $wx_encodingaeskey], 'qyweixin/service_provider_receive_log', 1);
        $wechat = new EnterpriseChat(array('wx_corpid' => $enterprise_wx_corpid, 'wx_token' => $wx_token, 'wx_encodingaeskey' => $wx_encodingaeskey));
        $data = $wechat->request();
        fdump_api(['msg' => '服务商微信消息通知>', 'line' => __LINE__, 'data' => $data],'qyweixin/service_provider_receive_log',1);
//        $data = array (
//            'ServiceCorpId' => 'ww21001813f8f04566',
//            'InfoType' => 'register_corp',
//            'TimeStamp' => '1628164780',
//            'RegisterCode' => '3H5cc8YTFgZANnSIQ0wOJt9PavwBMC7ymm8fEDBJzWf272FcJOKo3WI_5pfoATcZ',
//            'AuthCorpId' => 'wwf3b7b4364a7a8d26',
//            'ContactSync' => '',
//            'AuthUserInfo' => '',
//            'State' => 'qyRegisterPost202108051922jd1bN1',
//            'TemplateId' => 'tplb92dc7af6e457e37',
//        );
        if(isset($data['InfoType'])) {
            if (isset($data['State'])&&!empty($data['State'])){
                $State=trim($data['State']);
                $qywx_info=D('Village_qywx_guide_about')->where(['randomNumber'=>$State])->find();
                if (!empty($qywx_info)){
                    $data_qywx['qyResult']=serialize($data);
                    if (isset($data['ServiceCorpId']) && $data['ServiceCorpId']) {
                        $data_qywx['ServiceCorpId']=$data['ServiceCorpId'];
                    }
                    if (isset($data['AuthCorpId']) && $data['AuthCorpId']) {
                        $data_qywx['AuthCorpId']=$data['AuthCorpId'];
                    }
                    $data_qywx['update_time'] = time();
                    D('Village_qywx_guide_about')->where(['id'=>$qywx_info['id']])->save($data_qywx);
                }
            }
            switch ($data['InfoType']) {
                case 'register_corp':
                    // 注册返回处理
                default:
            }
        } elseif ($data) {
            exit($data);
        }
        exit('success');
    }

    // 	https://shequ-demo.fastwhale.com.cn/wap.php?g=Wap&c=HouseEnterpriseWeixin&a=service_enterprise_book
    public function service_enterprise_book() {
        fdump_api(['msg' => '通讯录编辑授权消息通知>', 'line' => __LINE__, '_REQUEST' => $_REQUEST],'qyweixin/service_enterprise_book_log',1);
        $enterprise_wx_corpid    = $this->config['enterprise_wx_corpid'];
        $wx_token                = $this->config['enterprise_book_token'];
        $wx_encodingaeskey       = $this->config['enterprise_book_encodingaeskey'];
        $enterprise_book_suiteid = $this->config['enterprise_book_suiteid'];
        $enterprise_book_secret = $this->config['enterprise_book_secret'];
        $param = array('wx_corpid' => $enterprise_wx_corpid, 'wx_token' => $wx_token, 'wx_encodingaeskey' => $wx_encodingaeskey);
        fdump_api(['msg' => '通讯录编辑授权消息通知>参数', 'param' => $param],'qyweixin/service_enterprise_book_log',1);
        $wechat = new EnterpriseChat($param);
        $data = $wechat->request();
        fdump_api(['msg' => '通讯录编辑授权消息通知>', 'line' => __LINE__, 'data' => $data],'qyweixin/service_enterprise_book_log',1);
        if(isset($data['InfoType'])) {
            if ($data['InfoType'] == 'suite_ticket') {
                if (isset($data['SuiteTicket']) && $data['SuiteTicket']) {
                    $config = D('Config')->where("`name`='enterprise_book_suite_ticket'")->find();
                    if ($enterprise_book_suiteid == $data['SuiteId']) {
                        if ($config) {
                            D('Config')->where("`name`='enterprise_book_suite_ticket'")->data(array('value' => $data['SuiteTicket']))->save();
                        } else {
                            $data_set = array(
                                'name' => 'enterprise_book_suite_ticket',
                                'value' => $data['SuiteTicket'],
                                'type' => 'type=text',
                                'info' => '通讯录编辑授权的suite_ticket',
                                'desc' => '通讯录编辑授权的安全凭证suite_ticket',
                                'gid' => 22,
                                'tab_id' => 'enterprise',
                                'tab_name' => '企业微信',
                                'sort' => '-25',
                                'status' => '0'
                            );
                            D('Config')->data($data_set)->add();
                        }
                    }
                    S(C('now_city') . 'config', null);
                    exit('success');
                }
            } elseif ($data['InfoType']=='create_auth') {
                // 授权
                fdump_api(['msg' => '授权>', 'line' => __LINE__, 'data' => $data],'qyweixin/service_enterprise_book_log',1);
                if ($data['SuiteId'] && $data['AuthCode']) {
                    import('@.ORG.qywx.config');
                    $config = new config();
                    $SuiteTicket = D('Config')->where("`name`='enterprise_book_suite_ticket'")->getField('value');
                    $SuiteTicket = trim($SuiteTicket);
                    $provider_config = [
                        'corpid' => $enterprise_wx_corpid,
                        'Secret' => $enterprise_book_secret,
                        'SuiteID' => $enterprise_book_suiteid,
                        'Token' => $wx_token,
                        'SuiteTicket' => $SuiteTicket,
                        'EncodingAESKey' => $wx_encodingaeskey,
                    ];
                    fdump_api(['msg' => '处理>', 'line' => __LINE__, 'data' => $data, 'provider_config' => $provider_config],'qyweixin/service_enterprise_book_log',1);
                    $config->qyRegisterInfo($data['AuthCode'],$data['SuiteId'], $provider_config, 'book_permanent_code');
                }
                exit('success');
            } elseif ($data['InfoType']=='cancel_auth') {
                $enterprise_wx_provider_suiteid = C('config.enterprise_wx_provider_suiteid');
                $saveData['book_permanent_code'] = '';
                $saveData['book_permanent_code_time'] = time();
                D('House_enterprise_wx_bind')->where(array('corpid' => $data['AuthCorpId'], 'bind_type' => 0,'wx_provider_suiteid'=>$enterprise_wx_provider_suiteid))->data($saveData)->save();
           
            }
        } elseif ($data) {
            exit($data);
        }
        exit('success');
    }
    
    public function service_login_receive() {
        fdump_api(['msg' => '登录辑授权消息通知>', 'line' => __LINE__, '_REQUEST' => $_REQUEST],'qyweixin/service_login_receive_log',1);
        $enterprise_login_suiteId    = $this->config['enterprise_login_suiteId'];
        $enterprise_wx_corpid    = $this->config['enterprise_wx_corpid'];
        $wx_token                = $this->config['enterprise_login_token'];
        $wx_encodingaeskey       = $this->config['enterprise_login_encodingaeskey'];
        $param = array('wx_corpid' => $enterprise_wx_corpid, 'wx_token' => $wx_token, 'wx_encodingaeskey' => $wx_encodingaeskey);
        fdump_api(['msg' => '登录辑授权消息通知>参数', 'param' => $param],'qyweixin/service_login_receive_log',1);
        $wechat = new EnterpriseChat($param);
        $data = $wechat->request();
        fdump_api(['msg' => '登录辑授权消息通知>', 'line' => __LINE__, 'data' => $data],'qyweixin/service_login_receive_log',1);
        if(isset($data['InfoType'])) {
            fdump_api(['msg' => '登录辑授权消息通知-具体消息>', 'line' => __LINE__, 'data' => $data],'qyweixin/service_login_receive_log',1);
            if ($data['InfoType'] == 'suite_ticket') {
                if (isset($data['SuiteTicket']) && $data['SuiteTicket']) {
                    $config = D('Config')->where("`name`='enterprise_login_suite_ticket'")->find();
                    if ($enterprise_login_suiteId == $data['SuiteId']) {
                        if ($config) {
                            D('Config')->where("`name`='enterprise_login_suite_ticket'")->data(array('value' => $data['SuiteTicket']))->save();
                        } else {
                            $data_set = array(
                                'name' => 'enterprise_login_suite_ticket',
                                'value' => $data['SuiteTicket'],
                                'type' => 'type=text',
                                'info' => '登录授权的suite_ticket',
                                'desc' => '登录授权的安全凭证suite_ticket',
                                'gid' => 22,
                                'tab_id' => 'enterprise',
                                'tab_name' => '企业微信',
                                'sort' => '-25',
                                'status' => '0'
                            );
                            D('Config')->data($data_set)->add();
                        }
                    }
                    S(C('now_city') . 'config', null);
                    exit('success');
                }
            }
        } elseif ($data) {
            exit($data);
        }
        exit('success');
    }
}