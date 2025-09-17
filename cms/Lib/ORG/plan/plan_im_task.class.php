<?php

/**
 * IM计划任务
 */
class plan_im_task extends plan_base
{

    public function runTask()
    {
        $conversations = M('im_conversation')->alias('c')
            ->join(C('DB_PREFIX') . 'im_chat_record AS r ON c.last_msg_id = r.msg_id')
            ->where(array('c.status' => 1))
            ->field('c.id,c.from_user,c.from_uuid,c.to_user,c.to_uuid,c.last_msg_time,c.last_msg_id,r.from_id,r.target_id,r.index_key')
            ->group('r.index_key')
            ->limit(500)
            ->select();
        foreach ($conversations as $c) {
            list($fPrefix, $fuuid) = self::getUidAndPrefix($c['from_id'] ?: $c['from_user']);
            list($tPrefix, $tuuid) = self::getUidAndPrefix($c['target_id'] ?: $c['to_user']);
            if (in_array($fPrefix, ['store', 'plat', 'village'])) {
                //最后一条店铺客服发送，则判断客户未响应超时
                $storeId = intval($fuuid);
                $this->customerTimeout($storeId, $c['from_user'], $c['to_user'], $c['last_msg_time'],$fPrefix);
            } else if (in_array($fPrefix, ['user', 'wechat', 'villageBind'])) {
                //最后一条为客户发送，则判断客服未接待响应超时
                $storeId = intval($tuuid);
                $this->kfTimeout($storeId, $c['from_user'], $c['to_user'], $c['last_msg_time'],$tPrefix);
            } else {
                continue;
            }
        }
        unset($conversations);
        return true;
    }

    /**
     * 客户咨询超时
     */
    public function customerTimeout($storeId, $from, $to, $lastTime, $prefix)
    {
        $tm = time();
        $config = M('store_kefu_params')->where(array('store_id' => $storeId, 'belong' => $prefix))->find();
        if (!$config) {
            //默认配置
            $config = array(
                'customer_unrespon_switch' => 1,
                'customer_unrespon_min' => 3,
                'customer_unrespon_tips' => '您好，由于我们长时间没有收到您的回复，稍后将自动结束本次对话。'
            );
        }
        $config['customer_unrespon_notify_min'] = $config['customer_unrespon_min'] - 2;
        if ($config['customer_unrespon_switch'] == 1) {
            if ($tm >= $lastTime + $config['customer_unrespon_min'] * 60) {
                //断开会话
                M('im_conversation')->where(array('from_user' => $from, 'to_user' => $to))->save(array('status' => 0));
                M('im_conversation')->where(array('to_user' => $from, 'from_user' => $to))->save(array('status' => 0));
                $kfUsername = (strpos($from, 'store') !== false || strpos($from, 'plat') !== false || strpos($from, 'village_') !== false) ? $from : $to;
                M('Merchant_store_kefu')->where(array('username' => $kfUsername, 'cur_receive' => array('gt', 0)))->setDec('cur_receive');
                $body = array(
                    'type' => 'system_notify',
                    'show_rule' => 0,
                    'body' => array('text' => '系统结束会话')
                );
                $this->jSendCustom($from, $to, $body);
                if(strpos($from, 'village') !== false){
                    $is_village = 1;
                }else{
                    $is_village = 0;
                }
                hook::hook_exec('assign_kefu',array('store_id'=>$storeId,'is_village'=>$is_village));
            } else if ($config['customer_unrespon_notify_min'] > 0 && $tm >= $lastTime + $config['customer_unrespon_notify_min'] * 60 && $tm <= $lastTime + $config['customer_unrespon_notify_min'] * 60 + 30) {
                //断开提前2分钟通知
                $body = array(
                    'type' => 'system_notify',
                    'show_rule' => 0,
                    'body' => array('text' => $config['customer_unrespon_tips'])
                );
                $this->jSendCustom($from, $to, $body);
            }
        }
    }

    /**
     * 客服回复超时
     */
    public function kfTimeout($storeId, $from, $to, $lastTime,$prefix)
    {
        $tm = time();
        $config = M('store_kefu_params')->where(array('store_id' => $storeId, 'belong' => $prefix))->find();
        if (!$config) {
            //默认配置
            $config = array(
                'customer_unreceived_switch' => 1,
                'customer_unreceived_min' => 30,
                'customer_unreceived_tips' => '由于长时间没有回复客户的咨询，稍后系统将自动结束会话。'
            );
        }
        $config['customer_unreceived_notify_min'] = $config['customer_unreceived_min'] - 2;
        if ($config['customer_unreceived_switch'] == 1) {
            if ($tm >= $lastTime + $config['customer_unreceived_min'] * 60) {
                //断开会话,减去客服连接数
                M('im_conversation')->where(array('from_user' => $from, 'to_user' => $to))->save(array('status' => 0));
                M('im_conversation')->where(array('to_user' => $from, 'from_user' => $to))->save(array('status' => 0));
                $kfUsername = (strpos($from, 'store') !== false || strpos($from, 'plat') !== false || strpos($from, 'village_') !== false) ? $from : $to;
                M('Merchant_store_kefu')->where(array('username' => $kfUsername, 'cur_receive' => array('gt', 0)))->setDec('cur_receive');
                $body = array(
                    'type' => 'system_notify',
                    'show_rule' => 0,
                    'body' => array('text' => '系统结束会话')
                );
                $this->jSendCustom($from, $to, $body);
                if(strpos($from, 'village') !== false){
                    $is_village = 1;
                }else{
                    $is_village = 0;
                }
                hook::hook_exec('assign_kefu',array('store_id'=>$storeId,'is_village'=>$is_village));
            } else if ($config['customer_unreceived_notify_min'] > 0 && $tm >= $lastTime + $config['customer_unreceived_notify_min'] * 60 && $tm <= $lastTime + $config['customer_unreceived_notify_min'] * 60 + 30) {
                //断开提前2分钟通知
                $body = array(
                    'type' => 'system_notify',
                    'show_rule' => 0,
                    'body' => array('text' => $config['customer_unreceived_tips'])
                );
                $this->jSendCustom($from, $to, $body);
            }
        }
    }

    public function jSendCustom($fromUser, $toUser, array $body = [])
    {
        return ;
        plugin_import('im', 'JMessage.autoload', '.php');
        $configData = PD('Config_data')->get_jg_im_config();
        $appkey = isset($configData['im_appkey']) ? $configData['im_appkey'] : '';
        $masterKey = isset($configData['im_masterkey']) ? $configData['im_masterkey'] : '';
        if (empty($appkey) || empty($masterKey)) {
            return false;
        }
        $jm = new \JMessage\JMessage($appkey, $masterKey, array('disable_ssl' => true));
        $rep = new \JMessage\IM\Message($jm);
        $from = array('id' => $fromUser, 'type' => 'user');
        $target = array('id' => $toUser, 'type' => 'single');
        $rs = $rep->sendCustom(1, $from, $target, $body);

        if (!empty($rs['body']['msg_id'])) {
            //发送成功记录到数据库，失败则不处理
            $msgId = $rs['body']['msg_id'];
            $msgCtime = floor($rs['body']['msg_ctime'] / 1000);
            $data = array(
                'index_key' => self::buildChatIndexKey($fromUser, $toUser),
                'msg_id' => $msgId,
                'from_id' => $fromUser,
                'target_id' => $toUser,
                'target_type' => 'single',
                'from_platform' => 'a',
                'version' => 1,
                'msg_type' => $body['type'],
                'create_time' => $msgCtime,
                'msg_body' => json_encode($body['body'], JSON_UNESCAPED_UNICODE));
            M('Im_chat_record')->add($data);
        }
    }

    public static function getUidAndPrefix($username)
    {
        $split = explode('_', $username);
        return array($split[0], $split[1]);
    }

    public static function buildChatIndexKey($from, $to)
    {
        $split = explode('_', $from);
        $belong1 = $split[0] . '_' . $split[1];
        $split = explode('_', $to);
        $belong2 = $split[0] . '_' . $split[1];
        $params = array($belong1, $belong2);
        arsort($params);
        return md5(implode('_', $params));
    }
}

?>
