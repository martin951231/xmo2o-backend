<?php

/**
 * 极光IM
 */
class JMessageAction extends BaseAction
{

    /**
     * 获取极光SDK初始化参数
     */
    public function init()
    {
        $configData = PD('Config_data')->get_jg_im_config();
        $appkey = isset($configData['im_appkey']) ? $configData['im_appkey'] : '';
        $masterKey = isset($configData['im_masterkey']) ? $configData['im_masterkey'] : '';
        if (!$appkey || !$masterKey) {
            returnCodeError(L_('极光IM参数appkey和masterKey未配置'));
        }

        $timestamp = floor(microtime(true) * 1000);
        $random = 'ABCDedksdjfksfjdlsklsd';
        $signature = md5("appkey=$appkey&timestamp=$timestamp&random_str=$random&key=$masterKey");
        $init = array(
            'appkey' => $appkey,
            'random_str' => $random,
            'signature' => $signature,
            'timestamp' => $timestamp,
            'flag' => 1);
        returnCodeOk($init);
    }

    /**
     * 获取当前用户登录信息
     */
    public function loginUser()
    {
        $fromUser = $this->reqParams('from_user', '', 'trim');
        $deviceId = $this->reqParams('Device-Id', 'packapp', 'trim');
        if (empty($fromUser)) {
            returnCodeError(L_('参数有误'));
        }
        $rs = D('Im_user')->jRegisterUser($fromUser);
        if ($rs['errorCode'] != 0) {
            returnCodeError($rs['errorMsg']);
        } else {
            $userinfo = D('Im_user')->field('id,username,password,nickname,avatar,uuid')->where(array('username' => $fromUser))->find();
            $userinfo['avatar'] && $userinfo['avatar'] = replace_file_domain($userinfo['avatar']);
            $userinfo['ticket'] = ticket::create($userinfo['id'], $deviceId, true)['ticket'];
            $userinfo['role'] = Im_userModel::getUserPrefix($fromUser);
            $userinfo['role_name'] = Im_userModel::getRoleName($userinfo['role']);
            $userinfo['role_number'] = Im_chat_recordModel::getShowNumber($userinfo['role']);

            //如果是HR，则获取公司信息
            $userinfo['company_name'] = '';
            $userinfo['company_logo'] = '';
            if ($userinfo['role'] == Im_userModel::PREFIX_HR) {
                $company = D('New_recruit_hr')->alias('hr')
                    ->join(C('DB_PREFIX') . 'new_recruit_company as com ON hr.mer_id =com.mer_id')
                    ->join(C('DB_PREFIX') . 'merchant as m ON m.mer_id =com.mer_id')
                    ->field('com.name,com.short_name,m.logo')
                    ->where(['hr.uid' => $userinfo['uuid']])
                    ->find();
                $userinfo['company_name'] = $company['name'] ?? '';
                $userinfo['company_logo'] = $company['logo'] ? replace_file_domain($company['logo']) : '';
            }


            //如果是求职者上传了头像更新
            if($this->version > 0 && $userinfo['role'] == Im_userModel::PREFIX_USER){
                $resume = D('New_recruit_resume')->where(['uid'=>$userinfo['uuid'],'is_del'=>0])->find();
                !empty($resume['name']) && $userinfo['nickname'] = $resume['name'];
                !empty($resume['portrait']) && $userinfo['avatar'] = replace_file_domain($resume['portrait']);
            }
            returnCodeOk($userinfo);
        }
    }

    /**
     * 获取会话列表
     */
    public function conversations()
    {
        $fromUser = $this->reqParams('from_user', '', 'trim');
        $relation = $this->reqParams('relation', '', 'trim');
        $page = $this->reqParams('page', 1, 'intval');
        $limit = $this->reqParams('limit', 100, 'intval');
        if (empty($fromUser)) {
            returnCodeError(L_('参数有误'));
        }
        list($type, $uuid) = Im_userModel::getUidAndPrefix($fromUser);
        if (empty($type) || $uuid < 0) {
            returnCodeError(L_('用户名格式有误'));
        }
        $kefu_info = M('Merchant_store_kefu')->where(['username'=>$fromUser,'status'=>1])->find();
        $rs = array();

        //获取群聊列表
        $fuuid = $type . '_' . $uuid;
        $groupChat = M('Im_group_chat_members')->alias('m')
            ->join('INNER JOIN ' . C('DB_PREFIX') . 'im_group_chat c ON m.group_id = c.group_id')
            ->where(['m.uuid' => $fuuid, 'm.is_hide' => 0])
            ->field('c.*,m.last_msg,m.last_msg_time,m.last_msg_id,m.unread_msg_count')
            ->select();
        $conversations = [];
        foreach ($groupChat as $g) {
            if ($type == 'deliver') {
                $fetchNumber = M('Shop_order')->where(['order_id' => $g['business_order_id']])->getField('fetch_number');
                $g['name'] .= '#' . $fetchNumber;
            }

            $conversations[] = [
                'chat_type' => 'group',
                'group_id'=>$g['group_id'],
                'avatar' => thumb_img($g['avatar'], 60, 60, 'fill'),
                'last_msg' => $g['last_msg'],
                'last_msg_time' => Im_conversationModel::formateDate($g['last_msg_time'] ?: $g['create_time']),
                'nickname' => $g['name'],
                'prefix' => '',
                'relation' => '',
                'status' => 1,
                'unread_msg_count' => $g['unread_msg_count'],
                'username' => $fromUser,
                'business_type' => $g['business_type'],
                'business_orderid' => $g['business_order_id']
            ];
        }
        //会话列表
        $singleConversations = D('Im_conversation')->getConversationByUsername($fromUser, '', '', ($page - 1) * $limit . ',' . $limit);
        foreach ($singleConversations as $s) {
            $s['chat_type'] = 'single';
            $s['business_type'] = '';
            $s['business_orderid'] = 0;
            $s['group_id'] = 0;

             if(strpos($s['relation'],'villageBind') !== false){
                $bind_info = explode('_',$s['username']);
                $pigcms_id = $bind_info[1];
                if(M('Merchant_store_kefu_customer')->where(['kefu_id'=>$kefu_info['id'],'uid'=>$pigcms_id])->find()){
                    $s['is_exclusive'] = true;
                }else{
                    $s['is_exclusive'] = false;
                }
            }else{
                $s['is_exclusive'] = false;
            }
            $conversations[] = $s;
        }
        $rs['conversations'] = $conversations;
        returnCodeOk($rs);
    }

    /**
     * 统计
     */
    public function stat()
    {
        $fromUser = $this->reqParams('from_user', '', 'trim');
        $relation = $this->reqParams('relation', '', 'trim');
        if (empty($fromUser)) {
            returnCodeError(L_('参数有误'));
        }
        list($type, $uuid) = Im_userModel::getUidAndPrefix($fromUser);
        if (empty($type) || $uuid < 0) {
            returnCodeError(L_('用户名格式有误'));
        }
        $rs = array();
        //会话列表
        $rs['sum_unread_msg_count'] = D('Im_conversation')->getUnreadMsgCount($fromUser, '');
        if ($type == Im_userModel::PREFIX_STORE || $type == Im_userModel::PREFIX_PLAT || $type == Im_userModel::PREFIX_VILLAGE) {
            //店铺客服角色，获取在线状态和连接数
            $kefu = D('Merchant_store_kefu')->where(array('username' => $fromUser))->field('status,max_receive,cur_receive')->find();
            if (empty($kefu)) {
                returnCodeError(L_('店铺客服不存在'));
            }
            $rs['status'] = array('online' => $kefu['status']);
            $rs['connection'] = array('max' => $kefu['max_receive'], 'cur' => $kefu['cur_receive']);
        }
        returnCodeOk($rs);
    }

    /**
     * 会话搜索
     */
    public function searchConversations()
    {
        $fromUser = $this->reqParams('from_user', '', 'trim');
        $relation = $this->reqParams('relation', '', 'trim');
        $keyword = $this->reqParams('keyword', '', 'trim');
        if (empty($fromUser)) {
            returnCodeError(L_('参数有误'));
        }
        $conversations = D('Im_conversation')->getConversationByUsername($fromUser, $relation, $keyword);
        //搜索聊天记录
        $where = sprintf("((`show_rule` = 0 AND (`from_id` = '%s' OR `target_id` = '%s')) OR (`from_id` = '%s' AND `show_rule` = 1) OR (`target_id` = '%s' AND `show_rule` = 2))", $fromUser, $fromUser, $fromUser, $fromUser);
        if ($keyword) {
            $where .= ' AND `msg_type` = "text" AND `msg_body` LIKE "%' . $keyword . '%"';
        }
        $where .= ' AND `create_time` >=' . strtotime('-1 month');
        $chatConversation = D('Im_chat_record')
            ->where($where)
            ->field('id,msg_id,from_id,target_id,msg_type,msg_body,create_time,count(*) AS count')
            ->group('`index_key`')
            ->limit(50)
            ->select();
        $mod = D('Im_conversation');
        $chat = [];
        foreach ($chatConversation as $c) {
            $toUser = $c['from_id'] == $fromUser ? $c['target_id'] : $c['from_id'];
            $info = $mod->getConversationByUsername($fromUser, $relation, '', 1, $toUser);
            if ($info) {
                $info[0]['last_msg'] = L_('X1条相关聊天记录', ['X1' => $c['count']]);
                $info[0]['msg_id'] = $c['msg_id'];
                $info[0]['keyword'] = $keyword;
                $chat[] = $info[0];
            }
        }
        returnCodeOk(array('conversation' => $conversations, 'chat_record' => $chat));
    }

    /**
     * 删除会话
     */
    public function delConversations()
    {
        $fromUser = $this->reqParams('from_user', '', 'trim');
        $toUser = $this->reqParams('to_user', '', 'trim');
        $groupId = $this->reqParams('group_id', 0, 'intval');
        if (empty($fromUser) || empty($toUser)) {
            returnCodeError(L_('参数有误'));
        }
        if($groupId > 0){
            //退出群聊
            $uuid = Im_userModel::getUUIDByUsername($fromUser);
            M('Im_group_chat_members')->where(['group_id' => $groupId, 'uuid' => $uuid])->save(['is_hide' => 1]);
        }else{
            D('Im_conversation')->where(array('from_user' => $fromUser, 'to_user' => $toUser))->save(array('is_del' => 1, 'status' => 0));
            D('Merchant_store_kefu')->where(['username' => $fromUser, 'cur_receive' => ['gt', 0]])->setDec('cur_receive');
        }
        returnCodeOk();
    }

    /**
     * 发送消息
     */
    public function sendMessage()
    {
        $fromUser = $this->reqParams('from_user','','trim');
        $toUser = $this->reqParams('to_user','','trim');
        $custom = $this->reqParams('custom',array());
        $type = isset($custom['type'])?$custom['type']:null;
        $body = isset($custom['body'])?$custom['body']:null;
        $jobId = $this->reqParams('job_id', 0, 'intval');
        $resumeId = $this->reqParams('resume_id', 0, 'intval');
        if (is_string($body)) {
            $body = json_decode(html_entity_decode($body, ENT_QUOTES), true);
        }
        $retval = array();
        if (empty($fromUser) || empty($toUser) || empty($body) || empty($type)) {
            returnCodeError(L_('参数有误'));
        }
        list($fPrefix, $fUUID) = Im_userModel::getUidAndPrefix($fromUser);
        list($tPrefix, $tUUID) = Im_userModel::getUidAndPrefix($toUser);
        if (!$fPrefix || !$tPrefix) {
            returnCodeError(L_('用户名格式有误'));
        }
        //客户自助菜单，标记仅客户可见show_rule字段
        $showRule = 0;
        if (in_array($type, array('coupon', 'search_orders', 'guess_you_like', 'customer_notify'))) {
            if (isset($body['tips'])) {
                returnCodeOk();
            }
            $showRule = 1;
        }
        //当接收者为店铺客服的时候，走分配客服策略
        if (in_array($tPrefix, array(Im_userModel::PREFIX_STORE, Im_userModel::PREFIX_PLAT))) {
            //用户发送消息给客服后在消息中心自动生成消息板块
            if($fPrefix=='user'){
                $url = '\app\common\model\service\user\UserNoticeService/addNoticeType';
                $param['type'] = $tPrefix==Im_userModel::PREFIX_STORE ? 1 : 2;
                $param['store_id'] = $tPrefix==Im_userModel::PREFIX_STORE ? $tUUID : 0;
                $param['uid'] = $fUUID;
                invoke_v20_service($url,[$param]);
            }
            //先走自动回复
            if ($type == 'text' && $body['text']) {
                $autoReplyContent = D('Short_phrases_group')->alias('g')
                    ->join(C('DB_PREFIX') . 'short_phrases s ON g.id=s.group_id')
                    ->where(['g.store_id' => $tUUID, 'g.belong' => $tPrefix, 's.title' => $body['text']])
                    ->getField('s.context');
                if ($autoReplyContent) {
                    $indexKey = Im_chat_recordModel::buildChatIndexKey($fromUser, $toUser);
                    $msgId = createRandNumber(16);
                    $msgCtime = time();

                    //记录聊天信息
                    $data = [
                        [
                            'index_key' => $indexKey,
                            'msg_id' => $msgId,
                            'from_id' => $fromUser,
                            'target_id' => $toUser,
                            'target_type' => 'single',
                            'from_platform' => 'auto_reply',
                            'version' => 1,
                            'msg_type' => $type,
                            'create_time' => $msgCtime,
                            'msg_body' => json_encode($body, JSON_UNESCAPED_UNICODE),
                            'show_rule' => 1,
                        ],
                        [
                            'index_key' => $indexKey,
                            'msg_id' => $msgId + 1,
                            'from_id' => $toUser,
                            'target_id' => $fromUser,
                            'target_type' => 'single',
                            'from_platform' => 'auto_reply',
                            'version' => 1,
                            'msg_type' => $type,
                            'create_time' => $msgCtime + 1,
                            'msg_body' => json_encode(['text' => $autoReplyContent], JSON_UNESCAPED_UNICODE),
                            'show_rule' => 2,
                        ],
                    ];
                    D('Im_chat_record')->addAll($data);
                    $fromUUID = $fPrefix . '_' . $fUUID;
                    $toUUID = $tPrefix . '_' . $tUUID;
                    D('Im_conversation')->where(array('from_uuid' => $fromUUID, 'to_uuid' => $toUUID))->save(array('last_msg' => $autoReplyContent, 'last_msg_time' => $msgCtime, 'last_msg_id' => $msgId + 1));
                    $reply = [
                        'create_time' => $msgCtime,
                        'from_id' => $toUser,
                        'msg_body' => ['text' => $autoReplyContent],
                        'text' => $autoReplyContent,
                        'msg_type' => 'text',
                        'is_from_user' => 0,
                        'target_id' => $fromUser
                    ];
                    //自动回复也增加消息记录
                    $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                    $param['type'] = $tPrefix=='store' ? 1 : 2;
                    $param['store_id'] = $tPrefix=='store' ? $fUUID : 0;
                    $param['kefu_id'] = explode('_',$toUser)[2] ?? 0;
                    $param['content'] = json_encode(array_merge(['text' => $autoReplyContent],['type'=>'text']), JSON_UNESCAPED_UNICODE);
                    $param['uid'] = $fUUID;
                    $param['is_read'] = 1;
                    invoke_v20_service($url,[$param]);
                    returnCodeOk(['is_reply' => true, 'reply' => $reply]);
                }
            }

            $assignRs = D('Merchant_store_kefu')->assignKefu($fromUser, $toUser, $type, $body);
            if ($assignRs['code'] == 0) {
                $toUser = $assignRs['retval']['username'];
                $retval['welcome'] = isset($assignRs['retval']['welcome']) ? $assignRs['retval']['welcome'] : '';
            } else {
                if (isset($assignRs['errorMsg'])) {
                    $retval['welcome'] = $assignRs['errorMsg'];
                    returnCodeOk($retval);
                }
                returnCodeError($assignRs['msg']);
            }
        }
        if (in_array($tPrefix,array(Im_userModel::PREFIX_VILLAGE))) {
            $assignRs = D('Merchant_store_kefu')->assignVillageKefu($fromUser, $toUser, $type, $body);
            if ($assignRs['code'] == 0) {
                $toUser = $assignRs['retval']['username'];
                $retval['welcome'] = isset($assignRs['retval']['welcome']) ? $assignRs['retval']['welcome'] : '';
            } else {
                if (isset($assignRs['errorMsg'])) {
                    $retval['welcome'] = $assignRs['errorMsg'];
                    if(strpos($fromUser,'villageBind') !== false){
                        if($custom['type'] == 'text'){
                            $keyword = isset($custom['body']['text'])?$custom['body']['text']:'';
                            if($keyword){
                                $split = explode('_', $fromUser);
                                if($split[1]){
                                    $bind_info = M('House_village_user_bind')->where(['pigcms_id'=>$split[1]])->field('village_id')->find();
                                    if($bind_info){
                                        $info = D('House_auto_phrases')->getKeyReply($keyword,$bind_info['village_id']);
                                        if($info){
                                            $retval['welcome'] = '';
                                            if($info['type'] == 2){
                                                if($info['content']){
                                                    $this->keyReply($toUser,$fromUser,['type'=>'text','body'=>['text'=>$info['content']],'show_rule'=>0]);
                                                }
                                                if($info['pic']){
                                                    $this->keyReply($toUser,$fromUser,['type'=>'image','body'=>['url'=>$info['pic']],'show_rule'=>0]);
                                                }
                                                if(empty($info['content']) && empty($info['pic'])){
                                                    if(empty($retval['welcome'])){
                                                        $retval['welcome'] = '请稍等，马上为您提供服务.';
                                                    }
                                                }
                                            }
                                            if($info['type'] == 1){
                                                if($info['url']){
                                                    $url = $info['url'];
                                                } elseif($info['api_url']){
                                                    $url = $info['api_url'];
                                                }else{
                                                    $url = '';
                                                }
                                                $this->keyReply($toUser,$fromUser,[
                                                    'type'=>'village_text_img',
                                                    'body'=>[
                                                        'title'=>$info['title'],
                                                        'info'=>$info['info'],
                                                        'content'=>$info['content'],
                                                        'pic'=>$info['pic'],
                                                        'url'=>$url,
                                                    ],
                                                    'show_rule'=>0
                                                ]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    returnCodeOk($retval);
                }
                returnCodeError($assignRs['msg']);
            }
        }
        D('Im_conversation')->build($fromUser, $toUser, Im_conversationModel::TYPE_SINGLE);
        D('Im_user')->jRegisterUser($toUser);
        D('Im_user')->jRegisterUser($fromUser);

        $custom['show_rule'] = $showRule;
        $sendRs = D('Im_user')->jSendCustom($fromUser, $toUser, $custom);
        if ($sendRs['success'] != true) {
            returnCodeError(L_('发送失败') + ':' . $sendRs['error_msg']);
        } else {
            $msgId = $sendRs['retval']['msg_id'];
            $msgCtime = floor($sendRs['retval']['msg_ctime'] / 1000);

            //记录聊天信息
            $data = array(
                'index_key' => Im_chat_recordModel::buildChatIndexKey($fromUser, $toUser),
                'msg_id' => $msgId,
                'from_id' => $fromUser,
                'target_id' => $toUser,
                'target_type' => 'single',
                'from_platform' => 'a',
                'version' => 1,
                'msg_type' => $type,
                'create_time' => $msgCtime,
                'msg_body' => json_encode($body, JSON_UNESCAPED_UNICODE),
                'is_read' => 0
            );
            $data['show_rule'] = $showRule;

            D('Im_chat_record')->add($data);
            //写入用户消息通知
            if($tPrefix=='user' && in_array($fPrefix,['store','plat'])){
                $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                $param['type'] = $fPrefix=='store' ? 1 : 2;
                $param['store_id'] = $fPrefix=='store' ? $fUUID : 0;
                $param['kefu_id'] = explode('_',$fromUser)[2] ?? 0;
                $param['content'] = json_encode(array_merge($body,['type'=>$data['msg_type']]), JSON_UNESCAPED_UNICODE);
                $param['uid'] = $tUUID;
                invoke_v20_service($url,[$param]);
            }
            //更新最后一条聊天信息，同时增加接收人未读消息数
            $lastMsg = Im_chat_recordModel::getSimpleText($data['msg_type'], $body);
            $lastMsgTime = $data['create_time'];
            $lastMsgId = $data['msg_id'];
            $fromUUID = $fPrefix . '_' . $fUUID;
            $toUUID = $tPrefix . '_' . $tUUID;
            $mod = D('Im_conversation');
            $tableName = $mod->getTableName();
            if ($showRule == 1 || $showRule == 0) {
                $mod->where(array('from_uuid' => $fromUUID, 'to_uuid' => $toUUID))->save(array('last_msg' => $lastMsg, 'last_msg_time' => $lastMsgTime, 'last_msg_id' => $lastMsgId));
            }
            if ($showRule == 2 || $showRule == 0) {
                $sql = "UPDATE %s SET `last_msg`='%s',`last_msg_time`=%d,`last_msg_id`='%s',`unread_msg_count`=`unread_msg_count`+1 WHERE from_uuid='%s' AND to_uuid='%s'";
                $mod->execute(sprintf($sql, $tableName, $lastMsg, $lastMsgTime, $lastMsgId, $toUUID, $fromUUID));
            }
            //双方均能看到的消息微信模板推送和极光推送
            if ($showRule == 0) {
                $pushData = [
                    'from_user' => $fromUser,
                    'from_prefix' => $fPrefix,
                    'from_uuid' => $fUUID,
                    'to_user' => $toUser,
                    'to_prefix' => $tPrefix,
                    'to_uuid' => $tUUID,
                    'message' => $lastMsg,
                    'time' => $msgCtime
                ];
                $this->push($pushData);
            }

            //如果是微信消息转接过来的会话，则发送消息
            if ($tPrefix == Im_userModel::PREFIX_WECHAT && in_array($type, ['text', 'image'])) {
                $openid = M('merchant_wechat_user')->where(['id' => $tUUID])->getField('openid');
                if ($openid) {
                    loadSystemOrg('WechatHelper');
                    $accesstoken = PD('Access_token_expires')->get_access_token();
                    $customObj = new WechatCustom(['access_token' => $accesstoken['access_token']]);
                    if ($type == 'text') {
                        $msgType = 'text';
                        $params = ['content' => $body['text']];
                    } else {
                        $mediaObj = new WechatMedia(['access_token' => $accesstoken['access_token']]);
                        $imageUrl = $body['url'];
                        $cacheMd5 = md5_file($imageUrl);
                        if (!$imageUrl || !$cacheMd5) {
                            returnCodeError(L_('缺少图片url或者计算md5失败'));
                        }
                        if (S($cacheMd5)) {
                            $mediaid = S($cacheMd5);
                        } else {
                            $filepath = TEMP_PATH . 'im/media/';
                            if (!is_dir($filepath)) {
                                mkdir($filepath, 0755, true);
                            }
                            file_put_contents($filepath . $cacheMd5 . '.jpg', file_get_contents($imageUrl));
                            $data = [
                                'media' => '@' . $filepath . $cacheMd5 . '.jpg'
                            ];
                            $uploadRs = $mediaObj->uploadMedia('image', $data);
                            if (!isset($uploadRs['media_id'])) {
                                returnCodeError($mediaObj->errMsg);
                            }
                            $mediaid = $uploadRs['media_id'];
                            S($cacheMd5, $mediaid, 86400 * 2);
                            unlink($filepath . $cacheMd5 . '.jpg');
                        }
                        $msgType = 'image';
                        $params = ['media_id' => $mediaid];
                    }
                    $customObj->send($openid, $msgType, $params);
                }
            }
            if(strpos($fromUser,'villageBind') !== false){
                if($custom['type'] == 'text'){
                    $keyword = isset($custom['body']['text'])?$custom['body']['text']:'';
                    if($keyword){
                        $split = explode('_', $fromUser);
                        if($split[1]){
                            $bind_info = M('House_village_user_bind')->where(['pigcms_id'=>$split[1]])->field('village_id')->find();
                            if($bind_info){
                                $info = D('House_auto_phrases')->getKeyReply($keyword,$bind_info['village_id']);
                                if($info){
                                    $retval['welcome'] = '';
                                    if($info['type'] == 2){
                                        if($info['content']){
                                            $this->keyReply($toUser,$fromUser,['type'=>'text','body'=>['text'=>$info['content']],'show_rule'=>0]);
                                        }
                                        if($info['pic']){
                                            $this->keyReply($toUser,$fromUser,['type'=>'image','body'=>['url'=>$info['pic']],'show_rule'=>0]);
                                        }
                                        if(empty($info['content']) && empty($info['pic'])){
                                            if(empty($retval['welcome'])){
                                                $retval['welcome'] = '请稍等，马上为您提供服务.';
                                            }
                                        }
                                    }
                                    if($info['type'] == 1){
                                        if($info['url']){
                                            $url = $info['url'];
                                        } elseif($info['api_url']){
                                            $url = $info['api_url'];
                                        }else{
                                            $url = '';
                                        }
                                        $this->keyReply($toUser,$fromUser,[
                                            'type'=>'village_text_img',
                                            'body'=>[
                                                'title'=>$info['title'],
                                                'info'=>$info['info'],
                                                'content'=>$info['content'],
                                                'pic'=>$info['pic'],
                                                'url'=>$url,
                                            ],
                                            'show_rule'=>0
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            returnCodeOk($retval);
        }
    }

    /**
     * 推送
     */
    public function push($pushData = [])
    {
        require_once APP_PATH . 'Lib/ORG/JMessage/autoload.php';
        $configData = PD('Config_data')->get_jg_im_config();
        $appkey = isset($configData['im_appkey']) ? $configData['im_appkey'] : '';
        $masterKey = isset($configData['im_masterkey']) ? $configData['im_masterkey'] : '';
        $jm = new \JMessage\JMessage($appkey, $masterKey, array('disable_ssl' => true));
        $user = new \JMessage\IM\User($jm);
        $rs = $user->stat($pushData['to_user']);

        $siteUrl = M('Config')->where(['name' => 'site_url'])->getField('value');
        $siteUrl && C('config.site_url', $siteUrl);
        if ($rs && isset($rs['body']) && !$rs['body']['login']) {
            //微信模板消息
            $first = L_('您好，您有一个新消息');
            $keyword1 = L_('智能客服');
            $keyword2 = L_('未查看');
            $keyword3 = date('Y-m-d H:i:s');
            $fromUser = M('Im_user')->where(['username' => $pushData['from_user']])->find();
            if ($pushData['to_prefix'] == Im_userModel::PREFIX_USER) {
                //接收者是用户
                $toUser = M('User')->where(['uid' => $pushData['to_uuid']])->field('uid,openid,wxapp_openid,nickname')->find();
                $openid = $toUser['openid'];
                $wxopenid = $toUser['wxapp_openid'];
                $href = build_im_chat_url($pushData['to_user'], $pushData['from_user'], $pushData['to_prefix'] . '2' . $pushData['from_prefix']);
                $remark = sprintf('%s客服于%s 给您发送了一个消息【%s】，请点击查看详情并回复消息', $fromUser['nickname'], date('H时i分', $pushData['time']), $pushData['message']);
            } else if ($pushData['to_prefix'] == Im_userModel::PREFIX_WECHAT) {
                //公众号用户
                $toUser = M('Merchant_wechat_user')->where(['id' => $pushData['to_uuid']])->find();
                $openid = $toUser['openid'];
                $href = build_im_chat_url($pushData['to_user'], $pushData['from_user'], $pushData['to_prefix'] . '2' . $pushData['from_prefix']);
                $remark = sprintf('%s客服于%s 给您发送了一个消息【%s】，请点击查看详情并回复消息', $fromUser['nickname'], date('H时i分', $pushData['time']), $pushData['message']);
            } else {
                //接收者是客服
                if(strpos($pushData['to_user'],'village') !== false){
                    $toUser = M('Merchant_store_kefu')
                        ->alias('kf')
                        ->join(C('DB_PREFIX') . 'house_worker AS u ON u.wid = kf.bind_uid')
                        ->where(['kf.username' => $pushData['to_user']])
                        ->field('u.uid,u.openid,u.wxapp_openid,u.nickname')
                        ->find();
                }else{
                    $toUser = M('Merchant_store_kefu')
                        ->alias('kf')
                        ->join(C('DB_PREFIX') . 'user AS u ON u.uid = kf.bind_uid')
                        ->where(['kf.username' => $pushData['to_user']])
                        ->field('u.openid,u.nickname')
                        ->find();
                }
                $openid = $toUser['openid'];
                $wxopenid = isset($toUser['wxapp_openid'])?$toUser['wxapp_openid']:'';
                $href = build_im_chat_url($pushData['to_user'], $pushData['from_user'], $pushData['to_prefix'] . '2' . $pushData['from_prefix']);
                $remark = sprintf('%s用户于%s 给您发送了一个消息【%s】，请点击查看详情并回复消息。', $fromUser['nickname'], date('H时i分', $pushData['time']), $pushData['message']);
            }

            if ($openid) {
                loadSystemOrg('templateNews');
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $model->sendTempMsg('OPENTM405462911',
                    array(
                        'href' => $href,
                        'wecha_id' => $openid,
                        'wxapp_openid' => $wxopenid ?? '',
                        'first' => $first,
                        'keyword1' => $keyword1,
                        'keyword2' => $keyword2,
                        'keyword3' => $keyword3,
                        'keyword4' => '',
                        'remark' => $remark
                    )
                );
            }

            //极光
            loadSystemOrg('Apppush');
            $apppush = new Apppush();
            $apppush->send(['uid' => $pushData['to_uuid'], 'title' => L_('您好，您有一个新消息'), 'message' => $pushData['message']], 'im_message');
        }
    }

    /**
     * 获取聊天记录
     */
    public function getHistoryMessage()
    {
        $fromUser = $this->reqParams('from_user', '', 'trim');
        $toUser = $this->reqParams('to_user', '', 'trim');
        $page = $this->reqParams('page', 1, 'intval');
        $limit = $this->reqParams('limit', 20, 'intval');
        $keyword = $this->reqParams('keyword', '', 'trim');
        $search = $this->reqParams('search', '', 'trim');
        $gid = $this->reqParams('gid',0,'intval');
        if ((!$fromUser || !$toUser) && $gid < 1) {
            returnCodeError(L_('参数有误'));
        } else {
            $index = Im_chat_recordModel::buildChatIndexKey($fromUser, $toUser);
            if($gid > 0){
                $where = "`msg_type` <> 'location' AND target_id=" . $gid . " AND target_type='group'";
            }else{
                $where = sprintf("target_type<>'group' AND `msg_type` <> 'location' AND `index_key` = '%s' AND (`show_rule` = 0 OR (`from_id` = '%s' AND `show_rule` = 1) OR (`target_id` = '%s' AND `show_rule` = 2))", $index, $fromUser, $fromUser);
            }

            if ($keyword) {
                $where .= ' AND `msg_type` = "text" AND `msg_body` LIKE "%' . $keyword . '%"';
                $where .= ' AND `create_time` >=' . strtotime('-1 month');
            }
            if ($search && $search == '1month') {
                $where .= ' AND `create_time` >=' . strtotime('-1 month');
                $messages = D('Im_chat_record')
                    ->where($where)
                    ->field('id,msg_id,from_id,target_id,msg_type,msg_body,create_time,show_read,is_read')
                    ->order('create_time DESC')
                    ->select();
            } else {
                $messages = D('Im_chat_record')
                    ->where($where)
                    ->field('id,msg_id,from_id,target_id,msg_type,msg_body,create_time,show_read,is_read')
                    ->order('create_time DESC')
                    ->limit(($page - 1) * $limit . ',' . $limit)
                    ->select();
            }
            $fromUUID = Im_userModel::getUUIDByUsername($fromUser);
            foreach ($messages as &$message) {
                $message['msg_body'] = json_decode($message['msg_body'], true);

                //系统消息多语言处理
                if ($message['msg_type'] == 'system_notify' && $message['msg_body']['text']) {
                    $message['msg_body']['text'] = L_($message['msg_body']['text']);
                }

                //判断登录用户
                if ($fromUUID == Im_userModel::getUUIDByUsername($message['from_id'])) {
                    $message['is_from_user'] = 1;
                } else {
                    $message['is_from_user'] = 0;
                }

                if ($message['msg_type'] == 'video') {
                    $message['msg_body']['thunm'] = replace_file_domain($message['msg_body']['thunm']);
                    $message['msg_body']['url'] = replace_file_domain($message['msg_body']['url']);
                }
            }
            if ($keyword) {
                returnCodeOk($messages);
            } else {
                $messages = array_reverse($messages);
                //添加上快捷回复
                list($fromPrefix, $fromUid) = Im_userModel::getUidAndPrefix($fromUser);
                list($targetPrefix, $targetUid) = Im_userModel::getUidAndPrefix($toUser);
                if ($fromPrefix == 'user' && $page == 1) {
                    $autoReply = D('Short_phrases_group')->alias('g')
                        ->join(C('DB_PREFIX') . 'short_phrases s ON g.id=s.group_id')
                        ->where(['g.store_id' => $targetUid, 'g.belong' => $targetPrefix, 's.title' => ['neq', '']])
                        ->order('s.sort DESC')
                        ->select();
                    if ($autoReply) {
                        $autoMessage = "您好，欢迎光临本店，有什么可以帮到您？<br/>";
                        $tm = time();
                        foreach ($autoReply as $a) {
                            $autoMessage .= "<span class='test' style='color: #1e90ff'>{$a['title']}</span><br/>";
                        }
                        $messages[] = [
                            'create_time' => $tm,
                            'from_id' => $toUser,
                            'msg_body' => ['text' => $autoMessage],
                            'text' => $autoMessage,
                            'msg_type' => 'auto_reply',
                            'is_from_user' => 0,
                            'target_id' => $fromUser
                        ];
                    }
                }
                returnCodeOk($messages);
            }
        }
    }

    /**
     * 消息已读取上报
     */
    public function readReport()
    {
        $tm = time();
        $fromUser = $this->reqParams('from_user', '', 'trim');
        $toUser = $this->reqParams('to_user', '', 'trim');
        $gid = $this->reqParams('gid', 0, 'intval');
        if ((!$fromUser || !$toUser) && $gid < 1) {
            returnCodeError(L_('参数有误'));
        } else {
            $fprefix = Im_userModel::getUserPrefix($fromUser);
            $fprefixNumer = Im_chat_recordModel::getShowNumber($fprefix);
            $fuuid = Im_userModel::getUUIDByUsername($fromUser);
            $tuuid = Im_userModel::getUUIDByUsername($toUser);

            $readRecordMod = M('Im_chat_read_record');
            $groupChatMod = M('Im_group_chat');
            $groupMemberMod = M('Im_group_chat_members');
            $recordMod = M('Im_chat_record');
            if ($gid > 0) {
                //群聊
                $thisGroup = $groupChatMod->where(['gid' => $gid])->find();
                if (empty($thisGroup)) {
                    returnCodeError(L_('聊天会话不存在'));
                }
                //该成员消息全部标记为已读
                $groupMemberMod->where(['group_id' => $thisGroup['group_id'], 'uuid' => $fuuid])->save(['unread_msg_count' => 0]);
                $members = $groupMemberMod->where(['group_id' => $thisGroup['group_id']])->select();
                $allReadNumer = $fprefixNumer;
                foreach ($members as $m) {
                    $allReadNumer |= Im_chat_recordModel::getShowNumber(Im_userModel::getUserPrefix($m['username']));
                }
                $unreadMsgIds = $recordMod->where(['index_key' => $thisGroup['group_index'], 'target_id' => $gid, '_string' => 'show_read&' . $fprefixNumer . '!=' . $fprefixNumer])->getField('msg_id', true);
                $unreadMsg = [];
                if ($unreadMsgIds) {
                    $in = '(' . implode(',', $unreadMsgIds) . ')';
                    $recordMod->execute("UPDATE " . C('DB_PREFIX') . 'im_chat_record SET show_read=show_read|' . $fprefixNumer . ' WHERE msg_id IN ' . $in);
                    $recordMod->where(['msg_id' => ['IN', $unreadMsgIds], 'show_read' => $allReadNumer])->save(['is_read' => 1]);
                    $unreadMsg = D('Im_chat_record')->where(['msg_id' => ['IN', $unreadMsgIds]])->field('msg_id,show_read,is_read')->select();

                    $readRecords = [];
                    foreach ($unreadMsgIds as $msgId) {
                        $readRecords[] = [
                            'msg_id' => $msgId,
                            'username' => $fromUser,
                            'create_time' => $tm
                        ];
                    }
                    $readRecords && $readRecordMod->addAll($readRecords, [], true);

                    $body = array(
                        'type' => 'msg_read',
                        'show_rule' => 0,
                        'body' => array('lists' => $unreadMsg)
                    );
                    (new Im_userModel())->jSendGroupMessage($gid, $fromUser, $body);
                }
                returnCodeOk(['lists' => $unreadMsg]);
            } else {
                D('Im_conversation')->where(array('from_uuid' => $fuuid, 'to_uuid' => $tuuid, 'unread_msg_count' => array('gt', 0)))->save(array('unread_msg_count' => 0));
                $index = Im_chat_recordModel::buildChatIndexKey($fromUser, $toUser);
                $unreadMsgIds = D('Im_chat_record')->where(['index_key' => $index, 'is_read' => 0, 'target_id' => $fromUser])->getField('msg_id', true);
                $fromInfo = D('Im_chat_record')->where(['index_key' => $index, 'is_read' => 0, 'target_id' => $fromUser])->getField('from_id', true);
                //消息通知中心读取
                $recordInfo = D('Im_chat_record')->where(['index_key' => $index, 'is_read' => 0, 'target_id' => $fromUser])->getField('from_id', true);
                if($fprefix == 'user' && $recordInfo[0]){
                    $url = '\app\common\model\service\user\UserNoticeService/readNotice';
                    $param['type'] = explode('_',$toUser)[0]=='store' ? 1 : 2;
                    $param['store_id'] = explode('_',$toUser)[0]=='store' ? explode('_',$toUser)[1] : 0;
                    $param['kefu_id'] = explode('_',$recordInfo[0])[2] ?? 0;
                    $param['uid'] = explode('_',$fromUser)[1] ?? 0;
                    invoke_v20_service($url,[$param]);
                }
                if ($unreadMsgIds) {
                    D('Im_chat_record')->where(['index_key' => $index, 'is_read' => 0, 'target_id' => $fromUser])->save(['is_read' => 1]);
                    $body = array(
                        'type' => 'msg_read',
                        'show_rule' => 0,
                        'body' => array('ids' => implode(',', $unreadMsgIds))
                    );
                    D('Im_user')->jSendCustom($toUser, $fromUser, $body);
                }
            }
            returnCodeOk();
        }
    }

    /**
     * 获取各角色聊天会话窗口菜单
     */
    public function operate()
    {
        $username = $this->reqParams('from_user', '', 'trim');
        $gid = $this->reqParams('gid',0,'intval');
        if (!$username) {
            returnCodeError(L_('参数有误'));
        }
        $prefix = Im_userModel::getUserPrefix($username);
        $menus = array();

        $menus[] = array(
            'action' => 'photo',
            'title' => L_('相册'),
            'icon' => $this->version > 0 ? $this->static_public . 'im/icon/new_photo.png' : $this->static_public . 'im/icon/photo.png'
        );
        $menus[] = array(
            'action' => 'camera',
            'title' => L_('拍摄'),
            'icon' => $this->version > 0 ? $this->static_public . 'im/icon/new_camera.png' : $this->static_public . 'im/icon/camera.png'
        );
        $menus[] = array(
            'action' => 'video',
            'title' => L_('视频'),
            'icon' => $this->static_public . 'im/icon/camera.png'
        );
        /*$menus[] = array(
            'action' => 'location',
            'title' => '位置',
            'icon' => $this->static_public . 'im/icon/location.png'
        );*/

        if ($prefix == Im_userModel::PREFIX_HR) {
            $menus = [];
            $menus[] = array(
                'action' => 'invite_interview',
                'title' => L_('邀面试'),
                'icon' => $this->static_public . 'im/icon/invite_interview.png'
            );
            $menus[] = array(
                'action' => 'call',
                'title' => L_('打电话'),
                'icon' => $this->static_public . 'im/icon/call.png'
            );
            $menus[] = array(
                'action' => 'photo',
                'title' => L_('相册'),
                'icon' => $this->static_public . 'im/icon/new_photo.png'
            );
            $menus[] = array(
                'action' => 'camera',
                'title' => L_('拍摄'),
                'icon' => $this->static_public . 'im/icon/new_camera.png'
            );
        }

        if ($gid == 0 && in_array($prefix, array(Im_userModel::PREFIX_STORE, Im_userModel::PREFIX_PLAT, Im_userModel::PREFIX_VILLAGE))) {
            //店铺客服特有
            $kefu = M('Merchant_store_kefu')->where(array('username' => $username, 'belong' => $prefix))->field('recommended_goods,can_send_coupon')->find();
            if (!$kefu) {
                returnCodeError(L_('客服用户不存在'));
            }
            $menus[] = array(
                'action' => 'transfer',
                'title' => L_('转接'),
                'icon' => $this->static_public . 'im/icon/transfer.png'
            );
            $menus[] = array(
                'action' => 'quick_reply',
                'title' => L_('快捷回复'),
                'icon' => $this->static_public . 'im/icon/quick_reply.png'
            );
            if ($kefu['can_send_coupon'] == 1) {
                $menus[] = array(
                    'action' => 'coupon',
                    'title' => L_('优惠券'),
                    'icon' => $this->static_public . 'im/icon/coupon.png'
                );
            }
            if ($kefu['recommended_goods'] == 1) {
                $menus[] = array(
                    'action' => 'recommend_goods',
                    'title' => L_('推荐商品'),
                    'icon' => $this->static_public . 'im/icon/recommend_goods.png'
                );
            }
        }
        returnCodeOk($menus);
    }

    /**
     * 获取快捷回复短语
     */
    public function shortPharses()
    {
        $username = $this->reqParams('from_user', '', 'trim');
        if (!$username) {
            returnCodeError(L_('参数有误'));
        }
        $kefu = M('Merchant_store_kefu')->where(array('username' => $username))->field('id,store_id,belong')->find();
        if (!$kefu) {
            returnCodeError(L_('客服不存在'));
        }
        $personalPhrases = D('Personal_short_phrases_group')->getPhrases($kefu['id']);
        $storePhrases = D('Short_phrases_group')->getPhrases($kefu['store_id'], $kefu['belong']);
        returnCodeOk(array('store_phrases' => $storePhrases, 'personal_phrases' => $personalPhrases));
    }

    /**
     * 新增个人快捷回复分组
     */
    public function addPharseGroup()
    {
        $username = $this->reqParams('from_user', '', 'trim');
        $name = $this->reqParams('name', '', 'trim');
        $sort = $this->reqParams('sort', 0, 'intval');
        if (!$username) {
            returnCodeError(L_('用户名参数有误'));
        }
        if (!$name) {
            returnCodeError(L_('分组名称不能为空'));
        }
        $kefu = M('Merchant_store_kefu')->where(array('username' => $username))->field('id,store_id')->find();
        if (!$kefu) {
            returnCodeError(L_('客服用户不存在'));
        }
        $data = array(
            'kefu_id' => $kefu['id'],
            'name' => $name,
            'sort' => $sort
        );
        if (M('Personal_short_phrases_group')->add($data)) {
            returnCodeOk();
        } else {
            returnCodeError(L_('添加失败'));
        }
    }

    /**
     * 编辑个人快捷回复分组
     */
    public function editPharseGroup()
    {
        $id = $this->reqParams('id', 0, 'intval');
        $name = $this->reqParams('name', '', 'trim');
        $sort = $this->reqParams('sort', 0, 'intval');
        if ($id < 1) {
            returnCodeError(L_('请选择一条修改分组'));
        }
        if (!$name) {
            returnCodeError(L_('分组名称不能为空'));
        }
        $group = M('Personal_short_phrases_group')->find($id);
        if (!$group) {
            returnCodeError(L_('分组记录不存在'));
        }
        $data = array(
            'name' => $name,
            'sort' => $sort
        );
        if (M('Personal_short_phrases_group')->where(array('id' => $id))->save($data) !== false) {
            returnCodeOk();
        } else {
            returnCodeError(L_('编辑失败'));
        }
    }

    /**
     * 删除个人快捷回复分组
     */
    public function delPharseGroup()
    {
        $id = $this->reqParams('id', 0, 'intval');
        if ($id < 1) {
            returnCodeError(L_('请选择要删除的分组'));
        }
        D('Personal_short_phrases_group')->delById($id);
        returnCodeOk();
    }

    /**
     * 新增个人快捷短语
     */
    public function addPharse()
    {
        $groupId = $this->reqParams('group_id', 0, 'intval');
        $context = $this->reqParams('context', '', 'trim');
        $sort = $this->reqParams('sort', 0, 'intval');
        if ($groupId < 1) {
            returnCodeError(L_('快捷短语分组参数有误'));
        }
        if (!$context) {
            returnCodeError(L_('短语内容不能为空'));
        }
        $group = M('Personal_short_phrases_group')->find($groupId);
        if (!$group) {
            returnCodeError(L_('快捷短语分组不存在'));
        }
        $data = array(
            'group_id' => $groupId,
            'context' => $context,
            'sort' => $sort
        );
        if (M('Personal_short_phrases')->add($data)) {
            returnCodeOk();
        } else {
            returnCodeError(L_('添加失败'));
        }
    }

    /**
     * 编辑个人快捷短语
     */
    public function editPharse()
    {
        $id = $this->reqParams('id', 0, 'intval');
        $context = $this->reqParams('context', '', 'trim');
        $sort = $this->reqParams('sort', 0, 'intval');
        $groupId = $this->reqParams('group_id', 0, 'intval');
        if ($id < 1) {
            returnCodeError(L_('请选择一条快捷短语'));
        }
        $phrase = M('Personal_short_phrases')->find($id);
        if (!$phrase) {
            returnCodeError(L_('快捷短语不存在'));
        }
        if ($groupId < 1 || !(M('Personal_short_phrases_group')->find($groupId))) {
            returnCodeError(L_('分组不存在'));
        }
        $data = array(
            'context' => $context,
            'sort' => $sort,
            'group_id' => $groupId
        );
        if (M('Personal_short_phrases')->where(array('id' => $id))->save($data) !== false) {
            returnCodeOk();
        } else {
            returnCodeError(L_('编辑失败'));
        }
    }

    /**
     * 删除个人快捷短语
     */
    public function delPharse()
    {
        $id = $this->reqParams('id', 0, 'intval');
        if ($id < 1) {
            returnCodeError(L_('请选择要删除的快捷短语'));
        }
        M('Personal_short_phrases')->where(array('id' => $id))->delete();
        returnCodeOk();
    }

    /**
     * 店铺客服发送优惠券
     */
    public function coupon()
    {
        $username = $this->reqParams('from_user', '', 'trim');
        if (!$username) {
            returnCodeError(L_('参数有误'));
        }
        list($prefix, $uid) = Im_userModel::getUidAndPrefix($username);
        if ($prefix == Im_userModel::PREFIX_STORE) {
            //店铺客服获取店铺优惠券
            $storeId = M('Merchant_store_kefu')->where(array('username' => $username))->getField('store_id');
            if ($storeId < 1) {
                returnCodeError(L_('客服用户不存在'));
            }
            //获取店铺信息
            $storeInfo = M(_view('Merchant_store'))->where(array('store_id' => $storeId))->find();
            if (empty($storeInfo)) {
                returnCodeError(L_('店铺信息不存在'));
            }
            //客服聊天优惠券直接取全部数量有限，不分页
            $allCoupons = PD('Card_new_coupon')->getAllImGetCoupon($storeId);
        } else if ($prefix == Im_userModel::PREFIX_PLAT) {
            //平台客服获取平台优惠券
            $mod = M('System_coupon');
            $where = [
                'end_time' => ['gt', time()],
                'status' => 1,
                'allow_im_get' => 1,
                'start_time' => ['lt', time()]
            ];
            $field = "coupon_id, name, `num`-`had_pull` AS left_num,discount,order_money,start_time,end_time,img AS image,platform";
            $allCoupons = $mod->where($where)->field($field)->select();
        }
        $coupon = array();
        foreach ($allCoupons as &$v) {
            $v['store_name'] = isset($storeInfo['name']) ? $storeInfo['name'] : '';
            $v['platform'] = unserialize($v['platform']);
            $v['image'] = replace_file_domain($v['image']);
            $v['type'] = $prefix;
            $v['start_time'] = date('Y/m/d', $v['start_time']);
            $v['end_time'] = date('Y/m/d', $v['end_time']);
            $v['discount'] = getFormatNumber($v['discount']);
            $v['order_money'] = getFormatNumber($v['order_money']);
        }
        returnCodeOk($allCoupons);
    }

    /**
     * 获取用户菜单（优惠券、猜你喜欢、订单列表等）
     */
    public function menu()
    {
        $username = $this->reqParams('username', '', 'trim');
        if (empty($username)) {
            returnCodeError('参数有误');
        }
        list($prefix, $storeId) = Im_userModel::getUidAndPrefix($username);
        if ($prefix == Im_userModel::PREFIX_STORE && $storeId > 1) {
            $menus = D('Kefu_menu')->where(array('store_id' => $storeId))->field('id,name,type')->order('sort DESC')->select();
            returnCodeOk($menus);
        } else if ($prefix == Im_userModel::PREFIX_PLAT) {
            $menus = D('Kefu_menu')->where(array('store_id' => 0))->field('id,name,type')->order('sort DESC')->select();
            returnCodeOk($menus);
        } else if ($prefix == Im_userModel::PREFIX_VILLAGE) {
            $menus = D('Kefu_menu')->where(array('store_id' => $storeId, 'belong' => $prefix))->field('id,name,type')->order('sort DESC')->select();
            returnCodeOk($menus);
        } else {
            returnCodeOk(array());
        }
    }

    /**
     * 获取自助菜单数据
     */
    public function menuData()
    {
        $id = $this->reqParams('menu_id', 0, 'intval');
        $username = $this->reqParams('username', '', 'trim');
        $goodId = $this->reqParams('goods_id', 0, 'intval');
        $from = $this->reqParams('from', '', 'trim');
        if ($id < 1) {
            returnCodeError(L_('id参数有误'));
        }
        if (empty($username)) {
            returnCodeError(L_('username参数有误'));
        }
        $menu = D('Kefu_menu')->find($id);
        if (empty($menu)) {
            returnCodeError(L_('自助菜单不存在'));
        }
        $rs = array('type' => $menu['type'] == 'system_coupon' ? 'coupon' : $menu['type']);
        if ($menu['type'] == Kefu_menuModel::COUPON) {
            $coupon = json_decode($menu['coupons'], true);
            $couponIds = array_column($coupon, 'coupon_id');
            $couponsInfo = D('Kefu_menu')->getCouponByIds($couponIds);
            if ($couponsInfo) {
                foreach ($couponsInfo as &$c) {
                    $c['type'] = 'store';
                }
                $rs['retval'] = $couponsInfo;
            } else {
                $rs['retval'] = array('tips' => L_('未找到优惠券，欢迎进店逛逛'), 'url' => get_shop_url($menu['store_id'], $from));
            }
        } else if ($menu['type'] == Kefu_menuModel::SYSTEM_COUPON) {
            $coupon = json_decode($menu['coupons'], true);
            $couponIds = array_column($coupon, 'coupon_id');
            $couponsInfo = D('Kefu_menu')->getSystemCouponByIds($couponIds);
            if ($couponsInfo) {
                foreach ($couponsInfo as &$c) {
                    $c['type'] = 'plat';
                }
                $rs['retval'] = $couponsInfo;
            } else {
                $rs['retval'] = array('tips' => L_('未找到优惠券'), 'url' => '');
            }
        } else if ($menu['type'] == Kefu_menuModel::LINK) {
            $rs['retval'] = array('url' => html_entity_decode($menu['link'], ENT_QUOTES));
        } else if ($menu['type'] == Kefu_menuModel::GUESS_YOU_LIKE) {
            $guess = D('Kefu_menu')->guessYouLike($menu['store_id'], $goodId, $from);
            if ($guess) {
                $rs['retval'] = $guess;
            } else {
                $rs['retval'] = array();
            }
        } else if ($menu['type'] == Kefu_menuModel::SEARCH_ORDERS) {
            list($prefix, $uid) = Im_userModel::getUidAndPrefix($username);
            if ($prefix != Im_userModel::PREFIX_USER || $uid < 1) {
                returnCodeError(L_('用户名有误'));
            }
            $lastOrder = D('Kefu_menu')->findLastPayOrder($menu['store_id'], $uid, $from);
            if ($lastOrder) {
                $rs['retval'] = $lastOrder;
            } else {
                $rs['retval'] = array('tips' => L_('目前尚无交易'), 'url' => get_shop_url($menu['store_id'], $from));
            }
        } else {
            returnCodeError(L_('类型不支持'));
        }
        returnCodeOk($rs);
    }


    /**
     * 获取用户信息
     */
    public function userinfo()
    {
        $username = $this->reqParams('username', '', 'trim');
        $kfUsername = $this->reqParams('kf_username', '', 'trim');
        $from = $this->reqParams('from', '', 'trim');
        if (!$username) {
            returnCodeError(L_('参数有误'));
        }
        list($kfPrefix, $storeId) = Im_userModel::getUidAndPrefix($username);
        $merId = 0;
        if ($storeId > 0) {
            $merId = M('Merchant_store')->where(['store_id' => $storeId])->getField('mer_id');
        }
        $info = D('Im_user')->getUserDetailInfo($username, $from, intval($merId));
        //如果是求职者上传了头像更新
        if($this->version > 0 && $info['role'] == Im_userModel::PREFIX_USER){
            $resume = D('New_recruit_resume')->where(['uid'=>$info['uid'],'is_del'=>0])->find();
            !empty($resume['name']) && $info['nickname'] = $resume['name'];
            !empty($resume['portrait']) && $info['avatar'] = replace_file_domain($resume['portrait']);
        }
        $from = $from?:'plat';
        $info['complaint_url'] = C('config.site_url').'/packapp/plat/pages/my/complaint/submit?type='.$from.'&mer_id='.$merId.'&store_id='.$storeId;
        returnCodeOk($info);
    }

    /**
     * 推荐商品
     */
    public function recommendGoods()
    {
        $type = $this->reqParams('type', '', 'trim');
        $username = $this->reqParams('username', '', 'trim');
        $page = $this->reqParams('page', 1, 'intval');
        $limit = $this->reqParams('limit', 10, 'intval');
        if (empty($username)) {
            returnCodeError(L_('参数有误'));
        }
        if (empty($type)) {
            returnCodeError(L_('type参数有误'));
        }
        list($prefix, $storeId) = Im_userModel::getUidAndPrefix($username);
        if ($prefix !== Im_userModel::PREFIX_STORE || $storeId < 1) {
            returnCodeError(L_('店铺信息有误'));
        }
        $goods = PD('Shop_goods')->getRecommendGoodsForKefu($storeId, $page, $limit, $type);
        foreach ($goods as &$g) {
            if ($type == 'mall') {
                $g['detail_url'] = C('config.site_url') . '/wap.php?g=Wap&c=Mall&a=detail&goods_id=' . $g['id'];
            } else {
                $g['detail_url'] = C('config.site_url') . '/wap.php?c=Shop&a=classic_good&shop_id=' . $g['store_id'] . '&good_id=' . $g['id'];
            }
        }
        returnCodeOk($goods);
    }

    /**
     * 商品详情
     */
    public function goodsDetail()
    {
        $goodId = $this->reqParams('goods_id', 0, 'intval');
        $from = $this->reqParams('from', '', 'trim');
        if ($goodId < 1 || empty($from)) {
            returnCodeError(L_('参数有误'));
        }
        $goods = M('Shop_goods')->where(array('is_del' => 0, 'goods_id' => $goodId))
            ->field('goods_id AS id,store_id,`name`,price,image,sell_count AS sale_count')
            ->order('sell_count DESC')
            ->find();
        if ($goods) {
            $goodsImage = new goods_image();
            $images = explode(';', $goods['image']);
            if ($images[0]) {
                $goods['image'] = $goodsImage->get_image_by_path($images[0], 0);
            }
            if ($from == 'mall') {
                $goods['detail_url'] = C('config.site_url') . '/wap.php?g=Wap&c=Mall&a=detail&goods_id=' . $goods['id'];
            } else {
                $goods['detail_url'] = C('config.site_url') . '/wap.php?c=Shop&a=classic_good&shop_id=' . $goods['store_id'] . '&good_id=' . $goods['id'];
            }
        }
        $goods['type'] = $from;
        returnCodeOk($goods);
    }

    /**
     * 优惠券领取
     */
    public function couponReceive()
    {
        $couponId = $this->reqParams('coupon_id', 0, 'intval');
        $username = $this->reqParams('username', '', 'trim');
        $fromType = $this->reqParams('type', '', 'trim');
        if ($couponId < 1 || empty($username) || empty($fromType)) {
            returnCodeError(L_('参数有误'));
        }
        list($type, $uuid) = Im_userModel::getUidAndPrefix($username);
        if ($type != Im_userModel::PREFIX_USER || $uuid < 1) {
            returnCodeError(L_('客服禁止领取'));
        }
        if ($fromType == 'store') {
            $rs = PD('Card_new_coupon')->had_pull($couponId, $uuid);
        } else if ($fromType == 'plat') {
            $rs = PD('System_coupon')->had_pull($couponId, $uuid);
        } else {
            returnCodeError(L_('优惠券类型错误'));
        }
        if ($rs['error_code'] == 0) {
            $array = array(
                'errorCode' => 0,
                'errorMsg' => date('H:i:s') . ' ' . L_('您已成功领取优惠券'),
                'result' => []
            );
            json($array);
        } else {
            $reason = array(1 => L_('优惠券不存在'), 2 => L_('优惠券已过期'), 3 => L_('优惠券已领完'), 4 => L_('该优惠券只允许新用户领取'), 5 => L_('优惠券不能再领取了'));
            returnCodeError(date('H:i:s') . ' ' . (isset($reason[$rs['error_code']]) ? $reason[$rs['error_code']] : L_('领取失败')));
        }
    }

    /**
     * 获取店铺客服
     */
    public function kfLists()
    {
        $username = $this->reqParams('username', '', 'trim');
        if (empty($username)) {
            returnCodeError(L_('参数有误'));
        }
        list($type, $storeId) = Im_userModel::getUidAndPrefix($username);

        $kflists = D('Merchant_store_kefu')->getOnlineKfListsByStoreId($storeId, $username, $type);
        returnCodeOk($kflists);
    }

    /**
     * 转接客服
     */
    public function transferKf()
    {
        $fromKf = $this->reqParams('from_kf', '', 'trim');
        $toKf = $this->reqParams('to_kf', '', 'trim');
        $customer = $this->reqParams('customer', '', 'trim');
        if (empty($fromKf) || empty($toKf) || empty($customer)) {
            returnCodeError(L_('参数有误'));
        }
        if ($fromKf == $toKf) {
            returnCodeError(L_('禁止转接给自己'));
        }
        $rs = D('Merchant_store_kefu')->transferKf($fromKf, $toKf, $customer);
        if ($rs['success'] == true) {
            //转接成功，记录一条消息到数据库
            $custom = array(
                'type' => 'kf_notify',
                'show_rule' => 2,
                'body' => array(
                    'action' => 'transfer_success',
                    'text' => $rs['error_msg'],
                    'from_user' => $toKf,
                    'to_user' => $customer,
                    'relation' => 'store2user'
                ));
            $sendRs = D('Im_user')->jSendCustom($toKf, $customer, $custom);
            if ($sendRs['success'] == true) {
                $msgId = $sendRs['retval']['msg_id'];
                $msgCtime = floor($sendRs['retval']['msg_ctime'] / 1000);
                //记录聊天信息
                $data = array(
                    'index_key' => Im_chat_recordModel::buildChatIndexKey($toKf, $customer),
                    'msg_id' => $msgId,
                    'from_id' => $customer,
                    'target_id' => $toKf,
                    'target_type' => 'single',
                    'from_platform' => 'a',
                    'version' => 1,
                    'show_rule' => 2,
                    'msg_type' => $custom['type'],
                    'create_time' => $msgCtime,
                    'msg_body' => json_encode($custom['body'], JSON_UNESCAPED_UNICODE));
                D('Im_chat_record')->add($data);
            }
            $array = array(
                'errorCode' => 0,
                'errorMsg' => date('H:i:s') . ' 转接成功',
                'result' => []
            );
            json($array);
        } else {
            returnCodeError(date('H:i:s') . ' ' . $rs['error_msg']);
        }
    }

    /**
     * 设置客服状态
     */
    public function setStatus()
    {
        $username = $this->reqParams('username', '', 'trim');
        $status = $this->reqParams('status', '', 'intval');
        if (empty($username)) {
            returnCodeError(L_('用户名参数有误'));
        }
        $rs = D('Merchant_store_kefu')->where(array('username' => $username))->setField('status', $status);
        if ($rs) {
            returnCodeOk();
        } else {
            returnCodeError(L_('设置状态失败'));
        }
    }

    /**
     * 退出排队
     */
    public function exitQueue()
    {
        $fromUser = $this->reqParams('from_user', '', 'trim');
        $toUser = $this->reqParams('to_user', '', 'trim');
        if (!$fromUser || !$toUser) {
            returnCodeError(L_('参数有误'));
        } else {
            list($fPrefix, $fuid) = Im_userModel::getUidAndPrefix($fromUser);
            list($tPrefix, $tuid) = Im_userModel::getUidAndPrefix($toUser);
            if ($fPrefix == Im_userModel::PREFIX_USER && $tPrefix == Im_userModel::PREFIX_STORE && $fuid > 0 && $tuid > 0) {
                M('Im_queue')->where(array('uid' => $fuid, 'store_id' => $tuid))->delete();
            }
            returnCodeOk();
        }
    }

    /**
     * 客服平台文本链接地址转换
     */
    public function parseUrl()
    {
        $url = $this->reqParams('url', '', 'trim');
        if (empty($url)) {
            returnCodeError(L_('参数有误'));
        }
        $parseInfo = parse_url($url);
        $path = $parseInfo['path'];
        $params = [];
        if ($parseInfo['query']) {
            parse_str($parseInfo['query'], $params);
        }
        empty($params['g']) && $params['g'] = 'Wap';
        if ($path == '/wap.php' && !empty($params['c']) && !empty($params['a'])) {
            if ($params['g'] == 'Wap' && $params['c'] == 'Mall' && $params['a'] == 'detail') {
                //商城链接
                if ($params['goods_id'] > 0) {
                    $goods = M('Shop_goods')->where(array('is_del' => 0, 'goods_id' => $params['goods_id']))
                        ->field('goods_id AS id,store_id,`name`,price,image,sell_count AS sale_count')
                        ->order('sell_count DESC')
                        ->find();
                }
            } else if ($params['g'] == 'Wap' && $params['c'] == 'Groupnew' && $params['a'] == 'detail') {
                //团购链接
                if ($params['group_id']) {
                    $goods = M('Group_store')->alias('s')
                        ->field('g.group_id AS id,g.`name`,g.sale_count,s.store_id,g.price,g.pic AS image')
                        ->join(C('DB_PREFIX') . 'group AS g ON g.group_id = s.group_id')
                        ->where(array('s.group_id' => $params['group_id']))
                        ->order('g.sale_count DESC')
                        ->find();
                }
            } else if ($params['g'] == 'Wap' && $params['c'] == 'Shop' && $params['a'] == 'classic_good') {
                //外卖链接
                if ($params['good_id'] > 0) {
                    $goods = M('Shop_goods')->where(array('is_del' => 0, 'goods_id' => $params['good_id']))
                        ->field('goods_id AS id,store_id,`name`,price,image,sell_count AS sale_count')
                        ->order('sell_count DESC')
                        ->find();
                }
            }
            if ($goods) {
                $goodsImage = new goods_image();
                $images = explode(';', $goods['image']);
                if ($images[0]) {
                    $goods['image'] = $goodsImage->get_image_by_path($images[0], 0);
                }
                $goods['detail_url'] = $url;
            }
        }
        if (isset($goods)) {
            returnCodeOk($goods);
        } else {
            returnCodeError(L_('解析失败'));
        }
    }

    /**
     * 订单按钮
     */
    public function orderBtns()
    {
        $btns = [];
        $username = $this->reqParams('username', '', 'trim');
        list($prefix, $uid) = Im_userModel::getUidAndPrefix($username);
        if ($prefix == Im_userModel::PREFIX_USER && $uid > 0) {
            $imTicket = ticket::create($uid, 'im', true)['ticket'];
            if (isset($this->config['meal_alias_name'])) {
                $btns['group'] = [
                    'title' => $this->config['group_alias_name'] . '订单',
                    'image' => $this->static_public . 'images/my_new/tuangou.png',
                    'url' => C('config.site_url') . '/wap.php?g=Wap&c=My&a=im_order_list&type=group&im_ticket=' . $imTicket
                ];
            }
            if (isset($this->config['store_open_shop'])) {
                $btns['shop'] = [
                    'title' => $this->config['shop_alias_name'] . '订单',
                    'image' => $this->static_public . 'images/my_new/kuaidian.png',
                    'url' => C('config.site_url') . '/wap.php?g=Wap&c=My&a=im_order_list&type=shop&im_ticket=' . $imTicket
                ];
                $btns['mall'] = [
                    'title' => $this->config['mall_alias_name'] . '订单',
                    'image' => $this->static_public . 'images/my_new/mall.png',
                    'url' => C('config.site_url') . '/wap.php?g=Wap&c=My&a=im_order_list&type=mall&im_ticket=' . $imTicket
                ];
            }
            if (isset($this->config['meal_alias_name'])) {
                $btns['canyin'] = [
                    'title' => $this->config['meal_alias_name'] . '订单',
                    'image' => $this->static_public . 'images/my_new/canyin.png',
                    'url' => C('config.site_url') . '/wap.php?g=Wap&c=My&a=im_order_list&type=foodshop&im_ticket=' . $imTicket
                ];
            }
            if (isset($this->config['appoint_alias_name'])) {
                $btns['yuyue'] = [
                    'title' => $this->config['appoint_alias_name'] . '订单',
                    'image' => $this->static_public . 'images/my_new/yuyue1.png',
                    'url' => C('config.site_url') . '/wap.php?g=Wap&c=My&a=im_order_list&type=appoint&im_ticket=' . $imTicket
                ];
            }
            if ($this->config['pay_in_store']) {
                $btns['store_order'] = [
                    'title' => '到店付订单',
                    'image' => $this->static_public . 'images/my_new/apply.png',
                    'url' => C('config.site_url') . '/wap.php?g=Wap&c=My&a=im_order_list&type=store&im_ticket=' . $imTicket
                ];
            }
            if (isset($this->config['gift_alias_name'])) {
                $btns['gift'] = [
                    'title' => $this->config['gift_alias_name'] . '订单',
                    'image' => C('config.site_url') . '/tpl/Wap/pure/static/images/new_my/gift.png',
                    'url' => C('config.site_url') . '/wap.php?g=Wap&c=My&a=im_order_list&type=gift&im_ticket=' . $imTicket
                ];
            }
            if ($this->config['mobile_recharge_APIKey'] && $this->config['mobile_recharge_openid']) {
                $btns['mobile_charge'] = [
                    'title' => '话费订单',
                    'image' => C('config.site_url') . '/tpl/Wap/pure/static/images/phone_ico1.png',
                    'url' => C('config.site_url') . '/wap.php?g=Wap&c=My&a=im_order_list&type=mobile_charge&im_ticket=' . $imTicket
                ];
            }
            if ($this->config['open_sub_card'] == 1) {
                $btns['sub_card'] = [
                    'title' => '免单订单',
                    'image' => C('config.site_url') . '/tpl/Wap/pure/static/images/sub_card.png',
                    'url' => C('config.site_url') . '/wap.php?g=Wap&c=My&a=im_order_list&type=sub_card&im_ticket=' . $imTicket
                ];
            }
            if (isset($this->config['wap_home_show_classify'])) {
                $btns['xinxi'] = [
                    'title' => $this->config['classify_name'] . '订单',
                    'image' => $this->static_public . 'images/my_new/xinxi.png',
                    'url' => C('config.site_url') . '/wap.php?g=Wap&c=My&a=im_order_list&type=xinxi&im_ticket=' . $imTicket
                ];
            }


            if (isset($this->config['scenic_alias_name'])) {
                $btns['scenic'] = [
                    'title' => $this->config['scenic_alias_name'] . '订单',
                    'image' => $this->static_public . 'images/my_new/scenic.png',
                    'url' => C('config.site_url') . '/wap.php?g=Wap&c=My&a=im_order_list&type=scenic&im_ticket=' . $imTicket
                ];
            }
        }
        returnCodeOk($btns);
    }

    /**
     * 上报极光登录异常错误码
     * @author: 张涛
     * @date: 2020/12/10
     */
    public function reportLoginErrorCode()
    {
        $username = $this->reqParams('username', '', 'trim');
        $code = $this->reqParams('code', 0, 'intval');
        if (empty($username)) {
            returnCodeOk();
        }
        require_once APP_PATH . 'Lib/ORG/JMessage/autoload.php';
        $configData = PD('Config_data')->get_jg_im_config();
        $appkey = isset($configData['im_appkey']) ? $configData['im_appkey'] : '';
        $masterKey = isset($configData['im_masterkey']) ? $configData['im_masterkey'] : '';
        $jm = new \JMessage\JMessage($appkey, $masterKey, array('disable_ssl' => true));
        $user = new \JMessage\IM\User($jm);
        if ($code === 880103) {
            //用户不存在
            D('Im_user')->jRegisterUser($username, [], true);
        } else if ($code === 880104) {
            //用户密码错误
            $passwd = createRandomStr(16);
            $user->updatePassword($username, $passwd);
            D('Im_user')->where(['username' => $username])->save(['password' => $passwd]);
        }
        returnCodeOk();
    }

    /**
     * 订单状态
     * @date: 2021/07/28
     */
    public function orderStatus()
    {
        $orderId = $_POST['order_id'] ?? 0;
        if ($orderId < 1) {
            returnCodeError(L_('参数错误'));
        }
        $supplyOrder = M('Deliver_supply')->where(['item' => 2, 'order_id' => $orderId])->find();
        if (empty($supplyOrder)) {
            returnCodeError(L_('配送单不存在'));
        }
        $statusDesc = [
            '0' => L_('配送失败'),
            '1' => L_('等待接单'),
            '2' => L_('前往商家中'),
            '3' => L_('到店等待中'),
            '4' => L_('正在送货中'),
            '5' => L_('配送已完成'),
        ];
        if (isset($statusDesc[$supplyOrder['deliver_status']])) {
            $time_info = sprintf('预计%s前送达', date('H:i', $supplyOrder['appoint_time']));
            switch ($supplyOrder['deliver_status']) {
                case 0:
                case 5:
                    $time_info = '';
                    break;
            }
            $result = [
                'status' => $supplyOrder['deliver_status'],
                'status_desc' => $statusDesc[$supplyOrder['deliver_status']],
                'time_info' => $time_info,
                'supply_id' => $supplyOrder['supply_id'],
                'order_id' => $orderId,
                'store_id' => $supplyOrder['store_id']
            ];
            returnCodeOk($result);
        } else {
            returnCodeError(L_('配送状态未知'));
        }
    }

    /**
     * 创建群聊
     * @date: 2021/07/28
     */
    public function buildGroupChat()
    {
        $orderId = $_POST['order_id'] ?? 0;
        $orderType = $_POST['order_type'] ?? 'shop';
        $username = $this->reqParams('username', '', 'trim');
        try {
            if (empty($orderId) || empty($username)) {
                throw new Exception(L_('参数有误'));
            }

            $businessOrder = $supplyOrder = [];
            $groupName = '';
            switch ($orderType) {
                case 'shop':
                    //外卖订单
                    $businessOrder = M('Shop_order')->where(['order_id' => $orderId])->find();
                    $supplyOrder = M('Deliver_supply')->where(['order_id' => $orderId, 'item' => 2])->find();
                    $groupName = $supplyOrder['store_name'];
                    break;
                case 'paotui':
                    //跑腿订单
                    $businessOrder = M('Service_user_publish')->where(['publish_id' => $orderId])->find();
                    $supplyOrder = M('Deliver_supply')->where(['order_id' => $orderId, 'item' => 3])->find();
                    $groupName = '跑腿配送';
                    break;
                default:
                    break;
            }
            if (empty($supplyOrder) || empty($businessOrder)) {
                throw new Exception(L_('订单不存在'));
            }

            //获取群聊成员
            $members = [];
            if ($supplyOrder['uid']) {
                $members[] = Im_userModel::PREFIX_DELIVER . '_' . $supplyOrder['uid'];
            }
            if ($businessOrder['uid']) {
                $members[] = Im_userModel::PREFIX_USER . '_' . $businessOrder['uid'];
            }
            if ($supplyOrder['store_id']) {
                $store = M('Merchant_store')->where(['store_id' => $supplyOrder['store_id']])->find();
                $storeKefu = M('Merchant_store_kefu')->where(['status' => 1, 'belong' => 'store', 'store_id' => $supplyOrder['store_id']])->find();
                if ($storeKefu) {
                    $members[] = $storeKefu['username'];
                }
            }

            //创建IM用户
            foreach ($members as $m) {
                D('Im_user')->jRegisterUser($m);
            }
            //创建群组
            $logo = $store['logo'] ?: '';
            $result = (new Im_group_chatModel())->buildGroupChat($username, $groupName, replace_file_domain($logo), $members, $orderType, $orderId);
            $imUsers = M('Im_user')->where(['username'=>['IN',$members]])->select();
            $otherMembers = [];
            foreach ($imUsers as $i) {
                if (Im_userModel::getUserPrefix($i['username']) == Im_userModel::getUserPrefix($username)) {
                    continue;
                }
                $otherMembers[] = [
                    'username' => $i['username'],
                    'type' => $i['type'],
                    'nickname' => $i['nickname'],
                    'avatar' => replace_file_domain($i['avatar']),
                    'show_name' => Im_userModel::getRoleName($i['type'])
                ];
            }
            $result['other_members'] = $otherMembers;
            returnCodeOk($result);
        } catch (Exception $e) {
            returnCodeError($e->getMessage());
        }
    }

    /**
     * 发送群聊消息
     * @date: 2021/07/28
     */
    public function sendGroupMessage()
    {
        $fromUser = $this->reqParams('username', '', 'trim');
        $gid = $this->reqParams('gid', 0, 'intval');
        $custom = $this->reqParams('custom', array());
        $type = isset($custom['type']) ? $custom['type'] : null;
        $body = isset($custom['body']) ? $custom['body'] : null;
        if (is_string($body)) {
            $body = json_decode(html_entity_decode($body, ENT_QUOTES), true);
        }
        if (empty($fromUser)) {
            returnCodeError(L_('用户不存在'));
        }
        if ($gid < 1) {
            returnCodeError(L_('群参数有误'));
        }
        $groupChat = M('Im_group_chat')->where(['gid' => $gid])->find();
        if (empty($groupChat)) {
            returnCodeError(L_('群聊不存在'));
        }

        $custom['show_rule'] = 0;
        $sendRs = (new Im_userModel())->jSendGroupMessage($gid, $fromUser, $custom);
        if ($sendRs['success'] != true) {
            returnCodeError(L_('发送失败') + ':' . $sendRs['error_msg']);
        } else {
            $msgId = $sendRs['retval']['msg_id'];
            $msgCtime = floor($sendRs['retval']['msg_ctime'] / 1000);

            //记录聊天信息
            $data = array(
                'index_key' => $groupChat['group_index'],
                'msg_id' => $msgId,
                'from_id' => $fromUser,
                'target_id' => $gid,
                'target_type' => 'group',
                'from_platform' => 'a',
                'version' => 1,
                'msg_type' => $type,
                'create_time' => $msgCtime,
                'is_read' => 0,
                'show_read' => Im_chat_recordModel::getShowNumber(Im_userModel::getUserPrefix($fromUser)),
                'msg_body' => json_encode($body, JSON_UNESCAPED_UNICODE));
            $data['show_rule'] = 0;
            D('Im_chat_record')->add($data);

            //更新其他成员未读消息数和最后一条消息
            $groupId = M('Im_group_chat')->where(['gid' => $gid])->getField('group_id');
            $fromUUID = Im_userModel::getUUIDByUsername($fromUser);
            $lastMsg = Im_chat_recordModel::getSimpleText($data['msg_type'], $body);
            $sql = 'UPDATE ' . C('DB_PREFIX') . 'im_group_chat_members SET last_msg="' . $lastMsg . '",last_msg_time=' . $msgCtime . ',last_msg_id=' . $msgId . ',unread_msg_count=unread_msg_count+1 WHERE group_id=' . $groupId . ' AND uuid <> "' . $fromUUID . '"';
            M('Im_group_chat_members')->execute($sql);
            returnCodeOk([]);
        }
    }

    /**
     * 社区关键字自动回复
     * @author lijie
     * @date_time 2021/10/21
     * @param string $fromUser
     * @param string $toUser
     * @param string $custom
     * @return bool
     */
    public function keyReply($fromUser='', $toUser='', $custom='')
    {
        if (empty($fromUser) || empty($toUser) || empty($custom))
            return false;
        D('Im_user')->jRegisterUser($toUser);
        D('Im_user')->jRegisterUser($fromUser);
        $type = $custom['type'];
        list($fPrefix, $fUUID) = Im_userModel::getUidAndPrefix($fromUser);
        list($tPrefix, $tUUID) = Im_userModel::getUidAndPrefix($toUser);
        if (!$fPrefix || !$tPrefix) {
            returnCodeError(L_('用户名格式有误'));
        }
        $sendRs = D('Im_user')->jSendCustom($fromUser, $toUser, $custom);
        if ($sendRs['success'] != true) {
            returnCodeError(L_('发送失败') + ':' . $sendRs['error_msg']);
        } else {
            $showRule = 0;
            $msgId = $sendRs['retval']['msg_id'];
            $msgCtime = floor($sendRs['retval']['msg_ctime'] / 1000);

            //记录聊天信息
            $data = array(
                'index_key' => Im_chat_recordModel::buildChatIndexKey($fromUser, $toUser),
                'msg_id' => $msgId,
                'from_id' => $fromUser,
                'target_id' => $toUser,
                'target_type' => 'single',
                'from_platform' => 'a',
                'version' => 1,
                'msg_type' => $type,
                'create_time' => $msgCtime + 1,
                'msg_body' => json_encode($custom['body'], JSON_UNESCAPED_UNICODE));
            $data['show_rule'] = $showRule;

            D('Im_chat_record')->add($data);
            //更新最后一条聊天信息，同时增加接收人未读消息数
            $lastMsg = Im_chat_recordModel::getSimpleText($data['msg_type'], $custom);
            $lastMsgTime = $data['create_time'];
            $lastMsgId = $data['msg_id'];
            $fromUUID = $fPrefix . '_' . $fUUID;
            $toUUID = $tPrefix . '_' . $tUUID;
            $mod = D('Im_conversation');
            $tableName = $mod->getTableName();
            if ($showRule == 1 || $showRule == 0) {
                $mod->where(array('from_uuid' => $fromUUID, 'to_uuid' => $toUUID))->save(array('last_msg' => $lastMsg, 'last_msg_time' => $lastMsgTime, 'last_msg_id' => $lastMsgId));
            }
            if ($showRule == 2 || $showRule == 0) {
                $sql = "UPDATE %s SET `last_msg`='%s',`last_msg_time`=%d,`last_msg_id`='%s',`unread_msg_count`=`unread_msg_count`+1 WHERE from_uuid='%s' AND to_uuid='%s'";
                $mod->execute(sprintf($sql, $tableName, $lastMsg, $lastMsgTime, $lastMsgId, $toUUID, $fromUUID));
            }
            //双方均能看到的消息微信模板推送和极光推送
            if ($showRule == 0) {
                $pushData = [
                    'from_user' => $fromUser,
                    'from_prefix' => $fPrefix,
                    'from_uuid' => $fUUID,
                    'to_user' => $toUser,
                    'to_prefix' => $tPrefix,
                    'to_uuid' => $tUUID,
                    'message' => $lastMsg,
                    'time' => $msgCtime
                ];
                $this->push($pushData);
            }
            return true;
        }
    }
    public function unreadCount()
    {
        $fromUser = $this->reqParams('from_user', 'user_112369180', 'trim');
        $relation = $this->reqParams('relation', 'user2hr', 'trim');
        if (empty($fromUser) || empty($relation)) {
            returnCodeError(L_('参数有误'));
        }
        list($type, $uuid) = Im_userModel::getUidAndPrefix($fromUser);
        if (empty($type) || $uuid < 0) {
            returnCodeError(L_('用户名格式有误'));
        }

        $where = array('from_user' => $fromUser, 'is_del' => 0, 'relation' => $relation);
        $count = D('Im_conversation')->where($where)->sum('unread_msg_count');
        returnCodeOk(['count' => intval($count)]);
    }

    /**
     * 获取用户信息
     */
    public function userCard()
    {
        $username = $this->reqParams('username', '', 'trim');
        $from = $this->reqParams('from', '', 'trim');
        $info = D('Im_user')->userCard($username, $from);
        returnCodeOk($info);
    }
}
