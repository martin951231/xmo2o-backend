<?php

class House_app_jpush_logModel extends Model
{
    public function handleJpushInfoSend($dataJpush,$opt_type='check_bind_room_user'){
        $village_id=0;
        if(empty($dataJpush) || !isset($dataJpush['village_id']) || $dataJpush['village_id']<1){
             return false;
        }
        $village_id=$dataJpush['village_id'];
        $send_wids=isset($dataJpush['wids']) && !empty($dataJpush['wids']) ? $dataJpush['wids']:array();
        $community_jpush_appkey = C('config.community_jpush_appkey');
        $community_jpush_appkey = trim($community_jpush_appkey);
        $community_jpush_secret = C('config.community_jpush_secret');
        $community_jpush_secret = trim($community_jpush_secret);
        if(empty($community_jpush_appkey) || empty($community_jpush_secret)){
            return false;
        }
        $push_tips = C('config.bind_user_room_tips');
        $push_tips = $push_tips ? $push_tips : '有业主提交审核信息，请处理';
        $jpush_infos=array();
        if($opt_type=='wisdom_qrcode_task_tips'){
            //智慧二维码巡检
            if(empty($send_wids)){
                return false;
            }
            $push_tips = C('config.wisdom_qrcode_task_tips');
            $push_tips = $push_tips ? $push_tips : '有您的巡检任务要开始了，请处理';
            $fieldStr='id,wid,jpush_registration_id,platform';
            $send_wids_str=implode(',',$send_wids);
            $whereStr='village_id='.$village_id.' AND wid in('.$send_wids_str.') AND jpush_registration_id <> "" AND del_time<100';
            $jpush_infos=M('House_app_jpush_info')->where($whereStr)->field($fieldStr)->select();
        }else if($opt_type=='checkauth_apply_tips'){
            if(!isset($dataJpush['wid']) || empty($dataJpush['wid'])){
                return false;
            }
            $wid=$dataJpush['wid'];
            $push_tips = C('config.checkauth_apply_tips');
            $push_tips = $push_tips ? $push_tips : '有您的审批流程待审核，请处理';
            $fieldStr='id,wid,jpush_registration_id,platform';
            $whereStr='village_id='.$village_id.' AND wid = '.$wid.' AND jpush_registration_id <> "" AND del_time<100';
            $jpush_infos=M('House_app_jpush_info')->where($whereStr)->field($fieldStr)->select();
        }else{
            $left_join = ' as ji left join '.C('DB_PREFIX').'house_worker as hw on ji.wid=hw.wid';
            $fieldStr='ji.id,ji.wid,ji.jpush_registration_id,ji.platform';
            $whereStr='ji.village_id='.$village_id.' AND ji.wid>0 AND ji.jpush_registration_id <> "" AND ji.del_time<100 AND hw.village_id='.$village_id.' AND hw.status<4 AND hw.is_del="0" AND hw.xtype=2 AND hw.relation_id>0';
            $jpush_infos=M('House_app_jpush_info')->join($left_join)->where($whereStr)->field($fieldStr)->select();
        }
        if(!empty($jpush_infos)){
            $nowtime = time();
            $jpush_registration_ids = array();
            $platformArr = array();
            $wids = array();
            $info_ids = array();
            foreach ($jpush_infos as $jvv){
                $jpush_registration_ids[] = $jvv['jpush_registration_id'];
                $platform = $jvv['platform'] ? strtolower($jvv['platform']) : 'all';
                if (!in_array($platform, array("android", "ios", "quickapp", "hmos", "all"))) {
                    $platform = 'all';
                }
                $platformArr[] = $platform;
                $wids[] = $jvv['wid'];
                $info_ids[] = $jvv['id'];
            }
            $platformArr=array_unique($platformArr);
            $jpush_registration_ids = array_unique($jpush_registration_ids);
            $logArr = array('village_id' => $village_id, 'jpush_registration_ids' => json_encode($jpush_registration_ids, JSON_UNESCAPED_UNICODE));
            $logArr['platform'] = json_encode($platformArr, JSON_UNESCAPED_UNICODE);
            $logArr['wids'] = json_encode($wids, JSON_UNESCAPED_UNICODE);
            $logArr['info_ids'] = json_encode($info_ids, JSON_UNESCAPED_UNICODE);
            $logArr['send_type']=$opt_type;
            $platform_arr = $platformArr;
            if (empty($platformArr) || in_array('all', $platformArr)) {
                $platform_arr = 'all';
            }
            $audience = array('registration_id' => $jpush_registration_ids);
            $notification = array('alert' => $push_tips);
            $notification['android'] = array("alert" => $push_tips, 'title' => $push_tips, 'builder_id' => 1, 'badge_add_num' => 1, "intent"=> array("url"=>"intent:#Intent;action=android.intent.action.MAIN;end") ,'extras' => array('x_type' => $opt_type));
            $notification['ios'] = array("alert" => $push_tips, 'sound' => 'default', 'badge' => "+1", 'extras' => array('x_type' => $opt_type));
            $notification['hmos'] = array("alert" => $push_tips, 'title' => $push_tips, "badge_add_num" => 1, 'extras' => array('x_type' => $opt_type));
            $message = array("msg_content" => $push_tips, "content_type" => "text", "title" => $push_tips, "extras" => array('x_type' => $opt_type));
            $jcontent = array("platform" => $platform_arr, 'audience' => $audience, 'notification' => $notification, 'message' => $message);  // 只支持 android, ios,quickapp, hmos  all
            $logArr['jcontent'] = json_encode($jcontent, JSON_UNESCAPED_UNICODE);
            $logArr['add_time'] = $nowtime;
            $houseAppJpushLogDb=M('House_app_jpush_log');
            $idd = $houseAppJpushLogDb->add($logArr);
            import('@.ORG.Jpush');
            $jpushObj = new Jpush($community_jpush_appkey, $community_jpush_secret);
            $ret = $jpushObj->send($platform_arr, $audience, $notification, $message, true);
            if ($idd > 0) {
                $status = 2;
                if ($ret && isset($ret['status']) && $ret['status']) {
                    $status = 1;
                }
                $saveArr = array('send_time' => time(), 'ret_json' => json_encode($ret, JSON_UNESCAPED_UNICODE), 'status' => $status);
                $houseAppJpushLogDb->where(array('id' => $idd))->data($saveArr)->save();
            }
            return $idd;
        }
    }
}

?>