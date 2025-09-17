<?php
/*社区工作人员微信通知提醒

 * */
class plan_send_house_worker_message extends plan_base{
    public function runTask(){
        $send_wisdom_qrcode_msg= $this->send_wisdom_qrcode();
        //fdump($send_wisdom_qrcode_msg,'debug_guojie1',1);
        $base_url = D('House_village')->base_url;
        $time=time();
        $jumpUrl=C('config.site_url').'/packapp/community/pages/Community/smartQRcode/taskList?gotoIndex=tem_msg&village_id=';
        if($send_wisdom_qrcode_msg){
            $token_info = array();
            $sendByjigangVillage=array();
            foreach($send_wisdom_qrcode_msg as $k2=>$v2){
                $this->keepThread();
                if($v2['record_type'] == 1){//每天
                    $now_time = date('Y-m-d',$time);
                    $start_time = $time-30;
                    $end_time = $time+30;
                    $key=md5('wisdom_qrcode_'.$v2['qid'].$v2['wid']);
                    $lockDate = S($key);
                    $currentTime=date('mdHi',$time);
                    $currentTime=intval($currentTime);
                    $tmpTime=date('md',$time).str_replace(":","",$v2['remind_time']);
                    $tmpTime=intval($tmpTime);
                    $tmp2Time=$tmpTime+2;  //计划任务间隔 80s
                    fdump_api(['触发=='.__LINE__,
                        $v2,
                        'currentTime'=>$currentTime,
                        'tmpTime'=>$tmpTime,
                        'tmp2Time'=>$tmp2Time,
                        'lockDate'=>$lockDate,
                    ],'work/day_log',1);
                    if(($currentTime>=$tmpTime && $currentTime<=$tmp2Time) && !$lockDate){
                        //缓存一分钟 避免重复发送
                        S($key,($v2['qid'].$v2['wid']),100);
                        //fdump("record_type1================",'debug_guojie2',1);
                        $href = $jumpUrl.$v2['village_id'];//智慧二维码
                        if(!isset($sendByjigangVillage[$v2['village_id']])){
                            $sendByjigangVillage[$v2['village_id']]=array($v2['wid']);
                        }else {
                            $sendByjigangVillage[$v2['village_id']][]=$v2['wid'];
                        }
                        if(empty($v2['openid'])){
                            continue;
                        }
                        $data = array(
                            'tempKey' => 'OPENTM405462911',//todo 类目模板OPENTM405462911
                            'dataArr' => array(
                                'href' => $href,
                                'wecha_id' => $v2['openid'],
                                'first' => '您有一项工作需处理',
                                'keyword1' => '智慧二维码的工作',
                                'keyword2' =>'已发送',
                                'keyword3' => date('H时i分',$time),
                                'keyword4' => '',
                                'remark' => '\n请点击查看详细信息！',
                                'new_info' => [//新版本发送需要的信息
                                    'tempKey'=>'44716',//新模板号
                                    'thing12'=>'智慧二维码的工作',//服务项目
                                    'phrase2'=>'已发送',//订单类型
                                    'time13'=>date('H:i',$time),//服务时间
                                    'thing11'=>'无',//服务地址
                                ],
                            )
                        );
                        
                        //巡检总次数增加1
                        $add_patrol_inspection_num = D('Wisdom_qrcode_statistics')->addPatrolInspectionNum(['village_id'=>$v2['village_id'],'cate_id'=>$v2['cate_id'],'uid'=>$v2['uid']]);
                        fdump_api(['每天=='.__LINE__,$href,$data,$v2,$add_patrol_inspection_num],'work/day_log',1);

                        D('Property_group')->send_msg('work', $v2['wid'], $data, $token_info);
                    }
                }elseif($v2['record_type'] == 2){//每周
                    if($v2['record_time']){
                        $record_time = explode(',',$v2['record_time']);
                        $dates = date('N',$time);
                        fdump_api(['触发=='.__LINE__,
                            $v2,
                            $dates,$record_time,
                        ],'work/week_log',1);
                        if(in_array($dates,$record_time)){
                            if((string)date('YmdHi',$time) == (string)(date('Ymd',$time).str_replace(":","",$v2['remind_time']))){
                                $href = $jumpUrl.$v2['village_id'];//智慧二维码地址
                                if(!isset($sendByjigangVillage[$v2['village_id']])){
                                    $sendByjigangVillage[$v2['village_id']]=array($v2['wid']);
                                }else {
                                    $sendByjigangVillage[$v2['village_id']][]=$v2['wid'];
                                }
                                if(empty($v2['openid'])){
                                    continue;
                                }
                                $data = array(
                                    'tempKey' => 'OPENTM405462911',//todo 类目模板OPENTM405462911
                                    'dataArr' => array(
                                        'href' => $href,
                                        'wecha_id' => $v2['openid'],
                                        'first' => '您有一项工作需处理',
                                        'keyword1' => '智慧二维码的工作',
                                        'keyword2' =>'已发送',
                                        'keyword3' => date('H时i分',$time),
                                        'keyword4' => '',
                                        'remark' => '\n请点击查看详细信息！',
                                        'new_info' => [//新版本发送需要的信息
                                            'tempKey'=>'44716',//新模板号
                                            'thing12'=>'智慧二维码的工作',//服务项目
                                            'phrase2'=>'已发送',//订单类型
                                            'time13'=>date('H:i',$time),//服务时间
                                            'thing11'=>'无',//服务地址
                                        ],
                                    )
                                );
                                //巡检总次数增加1
                                $add_patrol_inspection_num = D('Wisdom_qrcode_statistics')->addPatrolInspectionNum(['village_id'=>$v2['village_id'],'cate_id'=>$v2['cate_id'],'uid'=>$v2['uid']]);
                                D('Property_group')->send_msg('work', $v2['wid'], $data, $token_info);
                                fdump_api(['每周=='.__LINE__,
                                    $v2,
                                    $data,$add_patrol_inspection_num
                                ],'work/week_log',1);
                            }
                        }

                    }

                }elseif($v2['record_type'] == 3){//每月
                    //fdump("record_type3",'debug_guojie2',1);
                    if($v2['record_time']){
                        $record_time = explode(',',$v2['record_time']);
                        //fdump($record_time,'debug_guojie2',1);
                        $dates = date('d',$time);
                        //fdump($dates,'debug_guojie2',1);
                        fdump_api(['触发=='.__LINE__,
                            $v2,
                            $dates,$record_time,
                        ],'work/month_log',1);
                        if(in_array($dates,$record_time)){

//                            if($time == strtotime(date('Y-m-d',$time).' '.$v2['remind_time']))
                            if((string)date('YmdHi',$time) == (string)(date('Ym',$time).$dates.str_replace(":","",$v2['remind_time'])))
                            {
                                $href = $jumpUrl.$v2['village_id'];//智慧二维码地址
                                if(!isset($sendByjigangVillage[$v2['village_id']])){
                                    $sendByjigangVillage[$v2['village_id']]=array($v2['wid']);
                                }else {
                                    $sendByjigangVillage[$v2['village_id']][]=$v2['wid'];
                                }
                                if(empty($v2['openid'])){
                                    continue;
                                }
                                $data = array(
                                    'tempKey' => 'OPENTM405462911',//todo 类目模板OPENTM405462911
                                    'dataArr' => array(
                                        'href' => $href,
                                        'wecha_id' => $v2['openid'],
                                        'first' => '您有一项工作需处理',
                                        'keyword1' => '智慧二维码的工作',
                                        'keyword2' =>'已发送',
                                        'keyword3' => date('H时i分',$time),
                                        'keyword4' => '',
                                        'remark' => '\n请点击查看详细信息！',
                                        'new_info' => [//新版本发送需要的信息
                                            'tempKey'=>'44716',//新模板号
                                            'thing12'=>'智慧二维码的工作',//服务项目
                                            'phrase2'=>'已发送',//订单类型
                                            'time13'=>date('H:i',$time),//服务时间
                                            'thing11'=>'无',//服务地址
                                        ],
                                    )
                                );
                                //巡检总次数增加1
                                $add_patrol_inspection_num = D('Wisdom_qrcode_statistics')->addPatrolInspectionNum(['village_id'=>$v2['village_id'],'cate_id'=>$v2['cate_id'],'uid'=>$v2['uid']]);
                                D('Property_group')->send_msg('work', $v2['wid'], $data, $token_info);
                                fdump_api(['每月=='.__LINE__,
                                    $v2,
                                    $data,$add_patrol_inspection_num
                                ],'work/month_log',1);
                            }
                        }
                    }
                }
                
            }
            $community_jpush_appkey = C('config.community_jpush_appkey');
            $community_jpush_appkey = trim($community_jpush_appkey);
            $community_jpush_secret = C('config.community_jpush_secret');
            $community_jpush_secret = trim($community_jpush_secret);
            if(!empty($sendByjigangVillage) && !empty($community_jpush_appkey) && !empty($community_jpush_secret)){
                $house_app_jpush_logDb=D('House_app_jpush_log');
                foreach ($sendByjigangVillage as $v_id=>$wids){
                    $wids=array_unique($wids);
                    $dataJpush=array('village_id'=>$v_id,'wids'=>$wids);
                    $house_app_jpush_logDb->handleJpushInfoSend($dataJpush,'wisdom_qrcode_task_tips');
                    sleep(1); //睡1秒
                }
            }
            $sendByjigangVillage=array();
        }


        //移动抄表
        $meter_read_msg = $this->send_meter_read();
        $base_url = D('House_village')->base_url;
        if($meter_read_msg){
            $token_info = array();
            foreach($meter_read_msg as $k1=>$v1){
                 if($v1['cate_id'] && $v1['village_id']){
                     $read_cate = M('Meter_read_cate')->where(array('id'=>$v1['cate_id'],'village_id'=>$v1['village_id']))->find();
                     if($read_cate['cycle_time']){
                         $dates = date('d',time());
                         if(time() == strtotime(date('Y-m-d',time()).' '.$v1['remind_time']) && $dates == $read_cate['cycle_time']){

                             $href = $jumpUrl.$v1['village_id'];
                             $data = array(
                                 'tempKey' => 'OPENTM405462911',//todo 类目模板OPENTM405462911
                                 'dataArr' => array(
                                     'href' => $href,
                                     'wecha_id' => $v1['openid'],
                                     'first' => '您有一项待处理的工作需要完成',
                                     'keyword1' => '移动抄表',
                                     'keyword2' =>'已发送',
                                     'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']),
                                     'keyword4' => '',
                                     'remark' => '\n请点击查看详细信息！',
                                     'new_info' => [//新版本发送需要的信息
                                         'tempKey'=>'44716',//新模板号
                                         'thing12'=>'移动抄表',//服务项目
                                         'phrase2'=>'已发送',//订单类型
                                         'time13'=>date('H:i',$_SERVER['REQUEST_TIME']),//服务时间
                                         'thing11'=>'无',//服务地址
                                     ],
                                 )
                             );
                             D('Property_group')->send_msg('work', $v1['wid'], $data, $token_info);
                         }
                     }
                 }
            }
        }
        //超时后 分配指定工作人员
        $this->keepThread();
        D('House_worker')->village_bind_work();
        //新版工单超时后 分配指定工作人员
        D('House_new_repair_cate')->new_repair_bind_work();
        //巡检轨迹定位超时 自动完成
        D('Wisdom_qrcode_cate')->monitor_position_record();
        return true;
    }

    public function send_wisdom_qrcode()
    {
        $qrcode_person = M('Wisdom_qrcode_person')->where(array('status'=>1))->select();
        $this->keepThread();
        // fdump($qrcode_person,'debug_guojie',1);
        $dataList = [];
        $community_jpush_appkey = C('config.community_jpush_appkey');
        $community_jpush_appkey = trim($community_jpush_appkey);
        $community_jpush_secret = C('config.community_jpush_secret');
        $community_jpush_secret = trim($community_jpush_secret);
        foreach($qrcode_person as $k=>$v){
            $house_worker = D('House_worker')->where(array('status'=>1,'is_del'=>0,'wid'=>$v['uid']))->find();

            if($house_worker['openid'] || (!empty($community_jpush_appkey) && !empty($community_jpush_secret))){
                $list['village_id'] = $house_worker['village_id'];
                $list['wid'] = $house_worker['wid'];
                $list['openid'] = $house_worker['openid'] ? $house_worker['openid']:'';
                $list['uid'] = $v['uid'];
                $list['record_type'] = $v['record_type'];//周期类型
                $list['record_time'] = $v['record_time'];//周期时间
                $list['remind_time'] = $v['remind_time'];//提醒时间
                $list['qid'] = $v['id'];
                $list['cate_id'] = $v['cate_id'];
                $dataList[] = $list;
            }
        }
        $this->keepThread();
        return $dataList;
    }


    public function send_meter_read()
    {
        $meter_read_person = M('meter_read_person')->where(array('status'=>1))->select();
        $this->keepThread();
        // fdump($qrcode_person,'debug_guojie',1);
        $dataList = [];
        foreach($meter_read_person as $k=>$v){
            $house_worker = D('House_worker')->where(array('status'=>1,'is_del'=>0,'wid'=>$v['uid']))->find();
            if($house_worker['openid']){
                $list['village_id'] = $house_worker['village_id'];
                $list['wid'] = $house_worker['wid'];

                $list['openid'] = $house_worker['openid'];
                $list['uid'] = $v['uid'];
                $list['remind_time'] = $v['remind_time'];//提醒时间
                $list['cate_id'] = $v['cate_id'];
                $dataList[] = $list;
            }
        }
        $this->keepThread();
        return $dataList;
    }




}
?>