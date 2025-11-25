<?php
/*获取需要通知的信息通知对应人员，如果不需要通知，也做处理*/
class plan_sub_face_notice extends plan_base{
	public function runTask($param=null){
        $d_house_camera_tip = M('House_camera_tip');
        $tips = $d_house_camera_tip->where(array('is_plant'=>0,'bind_id'=>array('gt',0),'status'=>0))->limit(100)->select();
        if ($tips) {
            fdump('>>>>'.__LINE__,'api/log/'.date('Ymd').'/face_notice/plan_sub_face_notice_log',true);
            fdump($tips,'api/log/'.date('Ymd').'/face_notice/plan_sub_face_notice_log',true);
            foreach ($tips as $item) {
                $this->keepThread();
                $this->notice_msg($item);
            }
        }
		return true;
	}

	public function notice_msg($info) {
        $d_house_village_user_bind = D('House_village_user_bind');
        $d_house_camera_tip = M('House_camera_tip');
        $d_house_camera_device = M('House_camera_device');
        $d_house_face_device = M('House_face_device');
        $d_house_village_face_notice = M('House_village_face_notice');
        $d_house_village = M('House_village');
        $d_user = M('User');

        // 记录为已经执行
        $d_house_camera_tip->where(array('tip_id' => $info['tip_id']))->setField('is_plant',1);
        //查询5分钟内摄像机拍的数据有没有发送通知，没有则发送通知。若5分钟内同一个用户多条人脸消息，只通知一次，其他只改状态视为已通知
        $time = time()-5*60;
        unset($map);
        $map['bind_id'] = $info['bind_id'];
        $map['uid'] = $info['uid'];
        $map['village_id'] = $info['village_id'];
        $map['camera_sn'] = $info['camera_sn'];
        $map['city_id'] = $info['city_id'];
        $map['is_plant'] = 1;
        $map['is_notice'] = 1;
        $map['notice_time'] = [['elt',time()], ['egt',$time]];
        $tipInfo = $d_house_camera_tip->where($map)->order("tip_id desc")->field('notice_time')->find();
        if(!$tipInfo) {
            fdump('>>>>'.__LINE__,'api/log/'.date('Ymd').'/face_notice/notice_msg_log',true);
            fdump($d_house_camera_tip->_sql(),'api/log/'.date('Ymd').'/face_notice/notice_msg_log',true);
            $user_bind = $d_house_village_user_bind->where(array('pigcms_id' => $info['bind_id']))->find();
            if (0 == $user_bind['type'] || 3 == $user_bind['type']) {
                // 业主不进行通知
                fdump('line:'.__LINE__,'api/log/'.date('Ymd').'/face_notice/notice_msg_log',true);
                fdump($user_bind,'api/log/'.date('Ymd').'/face_notice/notice_msg_log',true);
                return true;
            }
            $house_village = $d_house_village->where(array('village_id' => $user_bind['village_id']))->find();
            // 获取业主信息
            $ower_village = $d_house_village_user_bind->where(array('pigcms_id' => $user_bind['parent_id']))->find();
            if ($ower_village && !$ower_village['is_all_notice']) {
                $ower_is_notice = true;
            } else {
                $ower_is_notice = false;
            }
            if ($ower_village && !$ower_is_notice) {
                $face_notice = $d_house_village_face_notice->where(array('notice_id' => $ower_village['pigcms_id'], 'bind_id' => $user_bind['pigcms_id']))->find();
                if ($face_notice && !$face_notice['is_notice']) {
                    $ower_is_notice = true;
                } else {
                    $ower_is_notice = false;
                }
            }
            // 如果给业主发送消息 获取业主用户信息
            if ($ower_is_notice && $ower_village) {
                $ower_now_user = $d_user->where(array('uid' => $ower_village['uid']))->find();
            }
            if ($ower_village) {
                // 获取业主配偶信息
                $spouse_village = $d_house_village_user_bind->where(array('parent_id' => $ower_village['parent_id'], 'type' => 1, 'relatives_type' => 1))->find();
                if ($spouse_village && !$spouse_village['is_all_notice']) {
                    $spouse_is_notice = true;
                } else {
                    $spouse_is_notice = false;
                }
                if ($spouse_village && !$spouse_is_notice) {
                    $spouse_face_notice = $d_house_village_face_notice->where(array('notice_id' => $spouse_village['pigcms_id'], 'bind_id' => $user_bind['pigcms_id']))->find();
                    if ($spouse_face_notice && !$spouse_face_notice['is_notice']) {
                        $spouse_is_notice = true;
                    } else {
                        $spouse_is_notice = false;
                    }
                }
            }
            // 如果给业主发送消息 获取业主用户信息
            if ($spouse_is_notice && $spouse_village) {
                $spouse_now_user = $d_user->where(array('uid' => $spouse_village['uid']))->find();
            }
            if (!$ower_is_notice && !$spouse_is_notice) {
                fdump('line:'.__LINE__,'api/log/'.date('Ymd').'/face_notice/notice_msg_log',true);
                fdump($user_bind,'api/log/'.date('Ymd').'/face_notice/notice_msg_log',true);
                fdump($ower_village,'api/log/'.date('Ymd').'/face_notice/notice_msg_log',true);
                fdump($face_notice,'api/log/'.date('Ymd').'/face_notice/notice_msg_log',true);
                // 业主和配偶不进行通知直接返回
                return true;
            }
            // 记录到对应用户消息 表格
            $tip = array();
            $content = array();
            $tip1 = array('title' => '小区名称', 'content' => $house_village['village_name']);
            $content[] = $tip1;
            $tip2 = array('title' => '人员名称', 'content' => $user_bind['name']);
            $content[] = $tip2;
            $camera_time = date('Y-m-d H:i:s', $info['camera_time']);
            if (!$info['type'] || 0 == $info['type']) {
                $tip['type'] = 'camera';
                $tip_title = '人脸抓拍摄像机通知';
                $device = $d_house_camera_device->where(array('camera_sn' => $info['camera_sn']))->find();
                // 您好，您的家属{{家属名称}}于{{时间}}在{{摄像机名称}}，望知悉!
                $device_name = $device['camera_name'];
                $title = L_('摄像机捕捉');
                $msg = "您好，小区中人脸抓拍摄像机捕捉到您的家属{$user_bind['name']}的位置信息，请查看!";
                $device_direction = L_('摄像机捕捉');
                $tip3 = array('title' => '摄像机名称', 'content' => $device_name);
                $content[] = $tip3;
            } elseif (1 == $info['type']) {
                $tip['type'] = 'face_door';
                $tip_title = '门禁出入通知';
                $device = $d_house_face_device->where(array('device_sn' => $info['camera_sn']))->find();
                // 您好，您的家属{{家属名称}}于{{时间}}从{{门禁名称}}进来了/出去了，望知悉! App通知
                $device_name = $device['device_name'];
                $title = L_('门禁出入');
                $msg = "您好，小区中人脸识别门禁机捕捉到您的家属{$user_bind['name']}的位置信息，请查看!";
                $device_direction = !$device['device_direction'] ? L_('出门') : L_('进门');
                $tip3 = array('title' => '门禁名称', 'content' => $device_name . "({$device_direction})");
                $content[] = $tip3;
            } else {
                $msg = '';
            }
            $tip4 = array('title' => '时间', 'content' => $camera_time);
            $content[] = $tip4;
            // 记录到对应用户消息 表格
            $now_time = time();
            $tip['url_title'] = '设置通知权限';
            $d_user_tip = D('User_tip');
            $tip['from_id'] = $info['tip_id'];

            if ($ower_village && $ower_village['uid'] > 0) {
                $tip_uid = $ower_village['uid'];
                $tip['url'] = '/wap.php?g=Wap&c=House&a=notice_family&village_id=' . $device['village_id'].'&pigcms_id='.$ower_village['pigcms_id'];
                $d_user_tip->add_msg($tip_uid, $tip_title, $tip, $content);
            }

            if ($spouse_village && $spouse_village['uid'] > 0) {
                $tip_uid = $spouse_village['uid'];
                $tip['url'] = '/wap.php?g=Wap&c=House&a=notice_family&village_id=' . $device['village_id'].'&pigcms_id='.$spouse_village['pigcms_id'];
                $d_user_tip->add_msg($tip_uid, $tip_title, $tip, $content);
            }
            
            $user_bind_type_arr = D('House_village_user_bind')->user_type;
            $user_bind_relatives_type_arr = D('House_village_user_bind')->relatives_type_info;
            $phrase2 = isset($user_bind_type_arr[$user_bind['type']]) ? $user_bind_type_arr[$user_bind['type']]['name'] : '无';
            $relatives_type=$user_bind['relatives_type']-1;
            if($user_bind['type']==1 && isset($user_bind_relatives_type_arr[$relatives_type])){
                $phrase2 = $user_bind_relatives_type_arr[$relatives_type]['name'];
            }
            // 微信模板推送
            if ($ower_is_notice && $ower_now_user['openid']) {
                fdump('发业主消息:'.__LINE__,'api/log/'.date('Ymd').'/face_notice/notice_msg_log',true);
                fdump($ower_is_notice,'api/log/'.date('Ymd').'/face_notice/notice_msg_log',true);
                fdump($ower_now_user,'api/log/'.date('Ymd').'/face_notice/notice_msg_log',true);
                $href = C('config.site_url') . '/wap.php?g=Wap&c=My&a=my_house_tip';
                if ($ower_village['pigcms_id']) {
                    $href .= '&pigcms_id='.$ower_village['pigcms_id'];
                }
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $model->sendTempMsg('OPENTM416545460', //todo 类目模板OPENTM416545460
                    array(
                        'href' => $href, 
                        'wecha_id' => $ower_now_user['openid'], 
                        'first' => $msg, 'keyword1' => $user_bind['name'], 
                        'keyword2' => $user_bind['usernum'], 
                        'keyword3' => date('Y-m-d H:i:s'), 
                        'keyword4' => $device_name, 
                        'keyword5' => $device_direction, 
                        'remark' => '',
                        'new_info' => [//新版本发送需要的信息
                            'tempKey'=>'48508',//新模板号
                            'thing1'=>$device_name.'【'.$device_direction.'】',//门禁名称
                            'thing3'=>$user_bind['name'],//姓名
                            'phrase2'=>$phrase2,//身份
                            'thing4'=>'无',//开门方式
                            'time5'=>date('Y-m-d H:i:s'),//开门时间
                        ],
                    )
                );
            }
            if ($spouse_is_notice && $spouse_now_user['openid']) {
                fdump('发业主配偶消息:'.__LINE__,'api/log/'.date('Ymd').'/face_notice/notice_msg_log',true);
                fdump($spouse_is_notice,'api/log/'.date('Ymd').'/face_notice/notice_msg_log',true);
                fdump($spouse_now_user,'api/log/'.date('Ymd').'/face_notice/notice_msg_log',true);
                $href = C('config.site_url') . '/wap.php?g=Wap&c=My&a=my_house_tip';
                if ($spouse_village['pigcms_id']) {
                    $href .= '&pigcms_id='.$spouse_village['pigcms_id'];
                }
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $model->sendTempMsg('OPENTM416545460', //todo 类目模板OPENTM416545460
                    array(
                        'href' => $href, 
                        'wecha_id' => $ower_now_user['openid'], 
                        'first' => $msg, 
                        'keyword1' => $user_bind['name'], 
                        'keyword2' => $user_bind['usernum'], 
                        'keyword3' => date('Y-m-d H:i:s'), 
                        'keyword4' => $device_name, 
                        'keyword5' => $device_direction, 
                        'remark' => '',
                        'new_info' => [//新版本发送需要的信息
                            'tempKey'=>'48508',//新模板号
                            'thing1'=>$device_name.'【'.$device_direction.'】',//门禁名称
                            'thing3'=>$user_bind['name'],//姓名
                            'phrase2'=>$phrase2,//身份
                            'thing4'=>'无',//开门方式
                            'time5'=>date('Y-m-d H:i:s'),//开门时间
                        ],
                    )
                );
            }

            //极光推送
            if ($ower_is_notice || $spouse_is_notice) {
                import('@.ORG.Apppush');
                $apppush = new Apppush();
                if ($ower_is_notice && $ower_now_user) {
                    $now_msg['device_sn'] = $info['camera_sn'];
                    $now_msg['title'] = $title;
                    $now_msg['msg'] = $msg;
                    $now_msg['uid'] = $ower_now_user['uid'];
                    $apppush->send($now_msg, 'face_door');
                }
                if ($spouse_is_notice && $spouse_now_user) {
                    $now_msg['device_sn'] = $info['camera_sn'];
                    $now_msg['title'] = $title;
                    $now_msg['msg'] = $msg;
                    $now_msg['uid'] = $spouse_now_user['uid'];
                    $apppush->send($now_msg, 'face_door');
                }
            }
            $d_house_camera_tip->where(array('tip_id' => $info['tip_id']))->data(array('is_notice'=>1,'notice_time'=>$now_time))->save();
        }else{
            //5分钟之内，不通知。只修改状态，不存通知时间（视为已通知）
            $d_house_camera_tip->where(array('tip_id' => $info['tip_id']))->data(array('is_notice'=>1))->save();
        }

        return true;

    }


}
?>