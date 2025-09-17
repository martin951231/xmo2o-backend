<?php

class Merchant_store_kefuModel extends Model
{
    /**
     * 分配客服
     */
    public function assignKefu($fromUser, $toUser, $type, $body)
    {
        $rs = array('code' => 0, 'errorMsg' => '');
        list($fPrefix, $uid) = Im_userModel::getUidAndPrefix($fromUser);
        list($tPrefix, $storeId) = Im_userModel::getUidAndPrefix($toUser);
        $fromUUID = $fPrefix . '_' . $uid;
        $toUUID = $tPrefix . '_' . $storeId;
        $findOneOnlineKf = $this->where(array('store_id' => $storeId, 'status' => 1))->getField('id');
        if (empty($findOneOnlineKf)) {
            $rs['code'] = 1001;
            $offlineTip = M('Store_kefu_params')->where(array('store_id' => $storeId))->getField('off_duty_notice');
            $rs['errorMsg'] = $offlineTip ?: '你好，我们现在都不在呢，等明天早上看到消息会第一时间回复的。';
        } else {
            //先判断用户是否和客服断开连接
            $conversation = D('Im_conversation')->where(array('from_uuid' => $fromUUID, 'to_uuid' => $toUUID))->find();
            if (!$conversation) {
                //新用户，第一次咨询客户按照饱和度分配
                $increReceiveNum = true;
                $toUser = $this->saturationAssign($uid, $storeId);
            } else {
                //非新用户，按照优先级分配
                $priorityUser = $conversation['to_user'];
                if ($conversation['status'] == 1 && $this->where(array('username' => $priorityUser))->find()) {
                    $increReceiveNum = false;
                    $toUser = $priorityUser;
                } else {
                    $increReceiveNum = true;
                    $config = M('Store_kefu_params')
                        ->where(array('store_id' => $storeId))
                        ->field('saturation_assign_kefu,priority_assign_kefu,appoint_assign_kefu')
                        ->find();

                    $toUser = '';
                    //1、绑定客服分配
                    if (empty($config) || $config['appoint_assign_kefu'] == 1) {
                        $toUser = $this->bindAssign($uid, $storeId, $priorityUser);
                    }
                    if (!$toUser && (empty($config) || $config['priority_assign_kefu'] == 1)) {
                        //2、上次客服优先分配
                        $toUser = $this->lastFirstAssign($priorityUser);
                    }
                    if (!$toUser) {
                        //饱和度
                        $toUser = $this->saturationAssign($uid, $storeId);
                    }
                }
            }
            if(empty($toUser)){
                //未分配到客服则丢到待分配队列
                $queueMod = M('Im_queue');
                $record = $queueMod->where(array('uid' => $uid, 'store_id' => $storeId))->find();
                if ($record) {
                    $count = $queueMod->where(array('store_id' => $storeId, 'id' => array('elt', $record['id'])))->count();
                } else {
                    $count = $queueMod->where(array('store_id' => $storeId))->count();
                    $count += 1;
                    $queueData = array(
                        'uid' => $uid,
                        'store_id' => $storeId,
                        'msg_type' => $type,
                        'msg_body' => json_encode($body, JSON_UNESCAPED_UNICODE),
                        'create_time' => time()
                    );
                    $body && $queueMod->add($queueData);
                }
                //记录聊天信息
                if($body){
                    $fromUsername = Im_userModel::PREFIX_USER . '_' . $uid;
                    $toUsername = Im_userModel::PREFIX_STORE . '_' . $storeId;
                    $data = array(
                        'index_key' => Im_chat_recordModel::buildChatIndexKey($fromUsername, $toUsername),
                        'msg_id' => 0,
                        'from_id' => $fromUsername,
                        'target_id' => $toUsername,
                        'target_type' => 'single',
                        'from_platform' => 'a',
                        'version' => 1,
                        'msg_type' => $type,
                        'create_time' => time(),
                        'msg_body' => json_encode($body, JSON_UNESCAPED_UNICODE),
                        'show_rule' => 1);
                    D('Im_chat_record')->add($data);
                }
                $rs['code'] = 1002;
                $rs['errorMsg'] = '您好，您前面还有' . $count . '人正在排队，请耐心等待，关闭窗口将退出排队。';
            } else {
                $rs['retval'] = array('username' => $toUser);
                if ($increReceiveNum) {
                    //增加客服接待数
                    $rs['retval']['welcome'] = $this->getWelcome($toUser);
                    $this->where(array('username' => $toUser))->setInc('cur_receive');
                }
            }
        }
        return $rs;
    }

    /**
     * 分配客服
     */
    public function assignVillageKefu($fromUser, $toUser, $type, $body)
    {
        $rs = array('code' => 0, 'errorMsg' => '');
        list($fPrefix, $uid) = Im_userModel::getUidAndPrefix($fromUser);
        list($tPrefix, $storeId) = Im_userModel::getUidAndPrefix($toUser);
        $fromUUID = $fPrefix . '_' . $uid;
        $toUUID = $tPrefix . '_' . $storeId;
        $findOneOnlineKf = $this->where(array('store_id' => $storeId, 'status' => 1))->getField('id');
        if (!$findOneOnlineKf) {
            $rs['code'] = 1001;
            $offlineTip = M('Store_kefu_params')->where(array('store_id' => $storeId))->getField('off_duty_notice');
            $rs['errorMsg'] = $offlineTip ?: '你好，我们现在都不在呢，等明天早上看到消息会第一时间回复的。';
        } else {
            //先判断用户是否和客服断开连接
            $conversation = D('Im_conversation')->where(array('from_uuid' => $fromUUID, 'to_uuid' => $toUUID))->find();
            if (!$conversation) {
                //新用户
                $increReceiveNum = true;
                //获取专属客服
                $toUser = $this->getVillageKefu($uid, $storeId);
                if(empty($toUser)){
                    //按饱和度分配
                    $toUser = $this->saturationAssign($uid, $storeId);
                }
                if($toUser){
                    $info = M('Merchant_store_kefu')->where(['username'=>$toUser])->find();
                    if($info['max_receive'] <= $info['cur_receive']){
                        $toUser = '';
                    }
                }
            } else {
                //非新用户，按照优先级分配
                $priorityUser = $conversation['to_user'];
                if ($conversation['status'] == 1 && $this->where(array('username' => $priorityUser))->find()) {
                    $increReceiveNum = false;
                    $toUser = $priorityUser;
                    $info = M('Merchant_store_kefu')->where(['username'=>$toUser])->find();
                    if($info['max_receive'] <= $info['cur_receive']){
                        $conversation1 = D('Im_conversation')->where(array('from_user' => $toUser, 'to_user' => $fromUser))->find();
                        if(empty($conversation1) || $conversation1['status'] == 0){
                            $toUser = '';
                        }
                    }
                } else {
                    $increReceiveNum = true;
                    $config = M('Store_kefu_params')
                        ->where(array('store_id' => $storeId))
                        ->field('saturation_assign_kefu,priority_assign_kefu,appoint_assign_kefu')
                        ->find();

                    $toUser = '';
                    //1、绑定客服分配
                    if (empty($config) || $config['appoint_assign_kefu'] == 1) {
                        $toUser = $this->getVillageKefu($uid, $storeId);
                    }
                    if($toUser){
                        $info = M('Merchant_store_kefu')->where(['username'=>$toUser])->find();
                        if($info['max_receive'] <= $info['cur_receive']){
                            $toUser = '';
                        }
                    }else{
                        if (!$toUser && (empty($config) || $config['priority_assign_kefu'] == 1)) {
                            //2、上次客服优先分配
                            $toUser = $this->lastFirstAssign($priorityUser);
                        }
                        if (!$toUser) {
                            //饱和度
                            $toUser = $this->saturationAssign($uid, $storeId);
                        }
                    }
                }
            }
            if(empty($toUser)){
                //未分配到客服则丢到待分配队列
                $queueMod = M('Im_queue');
                $record = $queueMod->where(array('uid' => $uid, 'store_id' => $storeId))->find();
                if ($record) {
                    $count = $queueMod->where(array('store_id' => $storeId, 'id' => array('elt', $record['id'])))->count();
                } else {
                    $count = $queueMod->where(array('store_id' => $storeId))->count();
                    $count += 1;
                    $queueData = array(
                        'uid' => $uid,
                        'store_id' => $storeId,
                        'msg_type' => $type,
                        'msg_body' => json_encode($body, JSON_UNESCAPED_UNICODE),
                        'create_time' => time()
                    );
                    $body && $queueMod->add($queueData);
                }
                //记录聊天信息
                if($body){
                    $fromUsername = Im_userModel::PREFIX_VILLAGE_USER . '_' . $uid;
                    $toUsername = Im_userModel::PREFIX_VILLAGE . '_' . $storeId;
                    $data = array(
                        'index_key' => Im_chat_recordModel::buildChatIndexKey($fromUsername, $toUsername),
                        'msg_id' => 0,
                        'from_id' => $fromUsername,
                        'target_id' => $toUsername,
                        'target_type' => 'single',
                        'from_platform' => 'a',
                        'version' => 1,
                        'msg_type' => $type,
                        'create_time' => time(),
                        'msg_body' => json_encode($body, JSON_UNESCAPED_UNICODE),
                        'show_rule' => 1);
                    D('Im_chat_record')->add($data);
                }
                $rs['code'] = 1002;
                $rs['errorMsg'] = '您好，您前面还有' . $count . '人正在排队，请耐心等待，关闭窗口将退出排队。';
            } else {
                M('Im_queue')->where(['uid'=>$uid])->delete();
                $rs['retval'] = array('username' => $toUser);
                if ($increReceiveNum) {
                    //增加客服接待数
                    $rs['retval']['welcome'] = $this->getWelcome($toUser);
                    $this->where(array('username' => $toUser))->setInc('cur_receive');
                }
            }
        }
        return $rs;
    }

    /**
     * 小区客服
     * @param int $pigcms_id
     * @param int $village_id
     * @return mixed
     */
    public function getVillageKefu($pigcms_id=0,$village_id=0)
    {
        // 获取客服联系路径
        $customer_info = M('Merchant_store_kefu_customer')->where(['uid'=>$pigcms_id])->find();
        if($customer_info){
            $kf = M('Merchant_store_kefu')->where(array('id' => $customer_info['kefu_id']))->find();
            if($kf){
                $toUser = $kf['username'];
            }else{
                $toUser = '';
            }
        }else{
            $user_bind_info = M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->field('single_id')->find();
            if($user_bind_info['single_id']){
                $house_keeper_info = M('House_services_housekeeper')->where(['single_id'=>$user_bind_info['single_id']])->find();
                if($house_keeper_info['work_arr']){
                    $con['bind_uid'] = array('in',$house_keeper_info['work_arr']);
                    $kf = M('Merchant_store_kefu')->where($con)->find();
                    if($kf){
                        $toUser = $kf['username'];
                    }else{
                        $toUser = '';
                    }
                }else{
                    $toUser = '';
                }
            }else{
                $toUser = '';
            }
        }
        return $toUser;
    }

    /**
     * 获取欢迎语，优先级：客服 > 店铺
     * @param $username
     * @return string
     */
    public function getWelcome($username)
    {
        list($prefix, $storeId) = Im_userModel::getUidAndPrefix($username);
        $kf = $this->where(array('username' => $username))->field('welcome,name,username')->find();
        if (empty($kf)) {
            return '';
        }
        if ($kf['welcome']) {
            return $kf['welcome'];
        }
        $welecome = M('Store_kefu_params')->where(array('store_id' => $storeId))->getField('welcome');
        if ($welecome) {
            return str_replace('{nickname}', $kf['name'], $welecome);
        }
        return '你好，欢迎光临。';
    }

    /**
     * 绑定分配
     */
    public function bindAssign($uid, $storeId, $priorityUser)
    {
        $fields = "kf.id,kf.`name`,kf.username,kf.welcome,kf.not_working_notice,kf.max_receive,kf.cur_receive,(kf.cur_receive / kf.max_receive) AS baohedu,";
        $fields .= "CASE kf.`username` WHEN '" . $priorityUser . "' THEN 1 ELSE 0 END  AS priority";
        $kefu = $this->alias('kf')->join(C('DB_PREFIX') . 'merchant_store_kefu_customer AS c ON kf.id = c.kefu_id')
            ->where(array('kf.store_id' => $storeId, 'kf.status' => 1,'c.uid' => $uid, 'kf.max_receive' => array('gt', 0), '_string' => 'kf.`cur_receive` < kf.`max_receive`'))
            ->field($fields)
            ->order('baohedu ASC,priority DESC')
            ->find();
        return $kefu ? $kefu['username'] : '';
    }

    /**
     * 上次客服优先分配
     */
    public function lastFirstAssign($priorityUser)
    {
        $kefu = $this->where(array('username' => $priorityUser, 'status' => 1))->field('max_receive,cur_receive')->find();
        if ($kefu && $kefu['max_receive'] > $kefu['cur_receive']) {
            return $priorityUser;
        } else {
            return '';
        }
    }

    /**
     * 饱和度分配
     */
    public function saturationAssign($uid, $storeId)
    {
        $kefu = $this->where("`store_id` = $storeId AND status=1 AND `max_receive` > `cur_receive`")->field('username,(cur_receive / max_receive) AS baohedu')->order('baohedu ASC')->find();
        return $kefu ? $kefu['username'] : '';
    }

    /**
     * 通过店铺id获取在线客服
     */
    public function getOnlineKfListsByStoreId($storeid, $except = '', $belong = '')
    {
        $where = array('kf.store_id' => $storeid, 'kf.belong' => $belong, 'kf.status' => 1);
        if ($except) {
            $where['kf.username'] = array('NEQ', $except);
        }
        if($storeid > 0){
            //店铺客服
            $kflists = $this->alias('kf')
                ->join(C('DB_PREFIX') . 'merchant_store AS s ON s.store_id = kf.store_id')
                ->where($where)
                ->field('kf.`name`,kf.username,kf.first_letter,s.logo AS avatar')
                ->order('first_letter ASC')
                ->select();
        }else if($storeid == 0){
            //平台客服
            $avatar = M('Config')->where(array('name' => 'wechat_share_img'))->getField('value');
            $kflists = $this->alias('kf')
                ->where($where)
                ->field('kf.`name`,kf.username,kf.first_letter,"' . $avatar . '" AS avatar')
                ->order('first_letter ASC')
                ->select();
        }

        $rs = array();
        foreach ($kflists as $v) {
            if (!isset($rs[$v['first_letter']])) {
                $rs[$v['first_letter']] = array('first_letter' => $v['first_letter'], 'kf_lists' => array());
            }
            $v['avatar'] = replace_file_domain($v['avatar']);
            $rs[$v['first_letter']]['kf_lists'][] = $v;
        }
        unset($kflists);
        return array_values($rs);
    }


    /**
     * 转接客服
     */
    public function transferKf($fromKf, $toKf, $customer)
    {
        $fromKfInfo = $this->where(array('username' => $fromKf))->field('id,name,store_id,username')->find();
        $toKfInfo = $this->where(array('username' => $toKf))->field('id,name,store_id,username')->find();
        $customerInfo = M('Im_user')->where(array('username'=>$customer))->field('id,username,nickname')->find();
        $rs = array('success' => true, 'error_msg' => '');
        try {
            if (empty($fromKfInfo)) {
                throw new Exception('被转接客服不存在');
            }
            if (empty($toKfInfo)) {
                throw new Exception('转接客服不存在');
            }
            if (empty($customerInfo)) {
                throw new Exception('转接客户不存在');
            }
            if ($fromKfInfo['store_id'] != $toKfInfo['store_id']) {
                throw new Exception('不能跨店铺转接客服');
            }
            $fuuid = Im_userModel::getUUIDByUsername($fromKf);
            $tuuid = Im_userModel::getUUIDByUsername($customer);
            $conversation = D('Im_conversation')->where(array('from_uuid' => $fuuid, 'to_uuid' => $tuuid, 'status' => 1))->find();
            if (empty($conversation)) {
                throw new Exception('会话不存在或者已断开，禁止转接');
            }
            /*if($conversation['from_user'] != $fromKf){
                throw new Exception('您不是当前店铺接入客服，禁止转接');
            }*/
            //更新当前接入的店铺客服
            D('Im_conversation')->build($toKf, $customer, Im_conversationModel::TYPE_SINGLE);
            $this->where(array('username' => $toKf))->setInc('cur_receive');
            $this->where(array('username' => $fromKf, 'cur_receive' => array('gt', 0)))->setDec('cur_receive');
            $rs['error_msg'] = sprintf("%s客服转接%s客户给您",$fromKfInfo['name'],$customerInfo['nickname']);
        } catch (Exception $e) {
            $rs['success'] = false;
            $rs['error_msg'] = $e->getMessage();
        }
        return $rs;
    }
}