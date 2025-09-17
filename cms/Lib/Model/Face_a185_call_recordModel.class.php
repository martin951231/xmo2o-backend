<?php
/**
 * @author liukezhu
 */

class Face_a185_call_recordModel extends Model {


    protected $TencentCall=[];
    public function __construct(){
        parent::__construct();
        import("@.ORG.TencentCall");
        $this->TencentCall = new TencentCall($this->getTCloudSdk());
    }


    //todo 获取腾讯云sdk参数
    public function getTCloudSdk(){
        $get_config_all=D("Config")->get_config_all([
            'name'=>['in',['key_facedoor_a185','sdkappid_facedoor_a185','secretid_facedoor_a185','secretkey_facedoor_a185']]
        ],'info,value');
        return $get_config_all;
    }

    //todo 获取腾讯云sdkappid + 签名
    public function getUserSig(){
        $mark=$this->getRandNumber();
        $result=$this->TencentCall->getUserSig($mark);
        $result['user_mark']=$mark;
        return $result;
    }

    public function a185_call_add($add_data){
        return $this->data($add_data)->add();
    }


    /**
     * 生成不重复的随机数字(不能超过10位数，否则while循环陷入死循环)
     * @param  int $start 需要生成的数字开始范围
     * @param  int $end 结束范围
     * @param  int $length 需要生成的随机数个数
     * @return number      生成的随机数
     */
    function getRandNumber($start = 1, $end = 9, $length = 8)
    {
        //初始化变量为0
        $count = 0;
        //建一个新数组
        $temp = array();
        while ($count < $length) {
            //在一定范围内随机生成一个数放入数组中
            $temp[] = mt_rand($start, $end);
            //$data = array_unique($temp);
            //去除数组中的重复值用了“翻翻法”，就是用array_flip()把数组的key和value交换两次。这种做法比用 array_unique() 快得多。
            $data = array_flip(array_flip($temp));
            //将数组的数量存入变量count中
            $count = count($data);
        }
        //为数组赋予新的键名
        shuffle($data);
        //数组转字符串
        $str = implode(",", $data);
        //替换掉逗号
        $number = str_replace(',', '', $str);
        return $number;
    }

    //todo 返回设备地址信息
    public function get_address($face_device){
        $House_village=D('House_village');
        $single_name=$floor_name='';
        if ($face_device['floor_id'] != "-1") {
            /**
             * 单元机
             */
            $floor_info = M('House_village_floor')
                ->join(' as f LEFT JOIN '.C('DB_PREFIX').'house_village_single as s on s.id=f.single_id')
                ->where(array('f.floor_id' => $face_device['floor_id']))->field('s.single_name,f.floor_name')->find();
            $address = $floor_info['single_name'].(D('House_village')->getHouseAlias('single_name')?:'栋').$floor_info['floor_name'].(D('House_village')->getHouseAlias('floor_name')?:'单元');
            $device_type=1;
            $single_name=$floor_info['single_name'];
            $floor_name=$floor_info['floor_name'];
        }
        else{
            /**
             * 围墙机
             */
            $address = D('House_village_public_area')->where(['public_area_id'=>$face_device['public_area_id']])->getField('public_area_name');
            $device_type=2;
        }
        $village_name=$House_village->where(['village_id'=>$face_device['village_id']])->getField('village_name');
        $data=[
            'village_name'      =>  $village_name ? $village_name : '',
            'single_name'       =>  $single_name,
            'floor_name'        =>  $floor_name,
            'device_address'    =>  $address,
            'device_type'       =>  $device_type
        ];
        return $data;
    }


    /**
     * A185 发起推送
     * @author: liukezhu
     * @date : 2021/9/1
     * @param $face_device  设备数据
     * @param $room        房间数据
     * @param $face_img    抓拍人脸
     * @return bool
     */
    public function a185_push($face_device,$room,$face_img){
        $Face_a185_request_record=D('Face_a185_request_record');
        $address=$this->get_address($face_device);
        $param=array(
            'vacancy_id'    =>  $room['called_room_id'],
            'name'          =>  $address['device_address'],
            'device_type'   =>  $address['device_type'], //门禁机类型 1 单元机  2 围墙机
            'device_id'     =>  $face_device['device_id'],
            'device_sn'     =>  $face_device['device_sn'],
            'single_name'   =>  $address['single_name'],
            'floor_name'    =>  $address['floor_name'],
            'village_id'    =>  $face_device['village_id'],
            'village_name'  =>  $address['village_name'],
            'room_num'      =>  $room['id'],
            'room_data'     =>  $room,      //房间数据
            'face_img'      =>  $face_img
        );
        fdump_api([__LINE__,$param],'a185/a185_push',1);

        //todo 生成子计划任务
        $plan_time=$room['plan_time'] + $Face_a185_request_record->call_countdown;
        $this->a185_task($face_device,$room['id'],$plan_time);
        return $this->push($param);
    }

    /**
     * 视频通话 消息推送
     * @author: liukezhu
     * @date : 2021/5/20
     */
    public function push($param){
        $House_village_user_bind=D('House_village_user_bind');
        $Face_a185_request_record=D('Face_a185_request_record');
        $Face_a185_call_record=D('Face_a185_call_record');
        $user_bind_where=array(
            'vacancy_id'=>$param['vacancy_id'],
            'status'=>1,
            'uid'=>['neq',0],
            'name'=>['neq',''],
            'phone'=>['neq',''],
            'type'=>['in', [0,1,2,3]]
        );
        $user_list = $House_village_user_bind->where($user_bind_where)->field('uid,pigcms_id,village_id')->group('uid')->order('type ASC')->select();
        fdump_api([__LINE__.'推送的用户',$user_list],'a185/push_log',1);
        fdump_api([__LINE__.'$param---',$param],'a185/a185_push_user',1);

        $app_tips='';
        $send_user=[];
        $call_device=[];
        //todo a185可视对讲使用小程序或APP
        $a185_switch= D("Config")->get_config_by_name("house_facedoor_a185_switch");
        //todo a185推送提示音
        $a185_app_tips = D('House_village_about_config')->where(['village_id'=>$param['village_id']])->getField('a185_app_tips');
        if(empty($a185_app_tips)){
            $a185_app_tips='您有一条推送消息';
        }
        $voice_return = json_decode($Face_a185_request_record->voice_baidu(), true);
        if(isset($voice_return['access_token']) && !empty($voice_return['access_token'])){
            $voice_access_token = $voice_return['access_token'];
            $app_tips=$Face_a185_request_record->voice_base_url . urlencode($a185_app_tips) . '&lan=zh&tok=' . $voice_access_token . $Face_a185_request_record->voice_cuid.'&fail.mp3';
        }
        $param['app_tips']=$app_tips;
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        if($user_list){
            foreach($user_list as $v){
                $user_info = D('User')->where(['uid'=>$v['uid']])->field('device_id,client,jpush_registrationId,openid')->find();
                fdump_api([__LINE__.'user_info---',$user_info],'a185/push_log',1);
                if(!$user_info){
                    fdump_api([__LINE__."$user_info 为空---",$user_info],'a185/push_log',1);
                    continue;
                }
                $login_log = D("Appapi_app_login_log")->where([
                    'uid' => $v['uid'],
                    'client'=>['neq',0],
                    'device_id'=>['neq','packapp'],
                ])->field('device_id,client,jpush_registrationId')->order('pigcms_id desc')->find();
                fdump_api([__LINE__.'device_id---',$login_log],'a185/push_log',1);
                if($user_info){
                    //极光推送给业主用户
                    $getUserSig=$this->getUserSig();
                    $new_arr = [
                        'device_id'=>$param['device_id'], //数据库自增设备id
                        'device_sn'=>$param['device_sn'], //设备唯一id
                        'uid'=>$v['uid'],
                        'push_source'=>'A185',
                        'url'=>'',
                        'title'=>'智慧社区',
                        'msg'=>'您的好友正在通过'.$param['name'].'的门禁机邀请您视频，请点击进行视频沟通',
                        'unitType'=>$param['device_type'],
                        'channelNo'=>'',
                        'device_type'=>$param['device_type'],
                        'single_name'=>$param['single_name'],
                        'floor_name'=>$param['floor_name'],
                        'village_name'=>$param['village_name'],
                        'parent_mark'=>$param['room_data']['user_mark'],
                        'user_mark'=>$getUserSig['user_mark'],
                        'key'=>$getUserSig['key'],
                        'sig'=>$getUserSig['sig'],
                        'room_num'=>$param['room_num'],
                        'face_img'=>$param['face_img'],
                        'pigcms_id'=>$v['pigcms_id'],
                        'device_address'=>$param['village_name'].$param['name'],
                        'app_tips'=>$app_tips
                    ];
                    if(!empty($login_log['jpush_registrationId'])){
                        $audience   =   array('registration_id'=>array($login_log['jpush_registrationId']));
                    }else{
                        $audience   =   array('tag'=>array(str_replace('-','',$login_log['device_id'])));
                    }
                    fdump_api([__LINE__.'推送的数据',$login_log['client'], $new_arr['title'], $new_arr['msg'], $new_arr,$audience],'a185/push_log',1);
                    //todo 极光推送app
                    if($login_log){
                        $face_a185_call_device_record=array(
                            'device_id'=>$param['device_id'],
                            'call_id'=>$param['room_num'],
                            'user_mark'=>$getUserSig['user_mark'],
                            'add_time'=>time()
                        );
                        $rel= D('Face_a185_call_device_record')->data($face_a185_call_device_record)->add();
                        $jpush= D('Group_order')->AuroraMass($login_log['client'], $new_arr['title'], $new_arr['msg'], $new_arr,$audience);
                        fdump_api([__LINE__,'极光推送app---',$jpush,$rel],'a185/jpush_log',1);
                    }
                    //todo 公众号推送
                    if($user_info['openid']){
                        //todo 跳转app
                        $href = C('config.site_url') . '/topic/app_wap_platform.html';
                        $tempKey='TM0001801';
                        $getUserSig=$this->getUserSig();
                        if($a185_switch && $a185_switch['value'] == '0'){
                            //todo 跳转小程序
                            $rr= http_build_query([
                                'pigcms_id'=>$v['pigcms_id'],
                                'room_num'=>$param['room_num'],
                                'user_mark'=>$getUserSig['user_mark'],
                            ]);
                            $base_url = PD('House_village')->base_url;
                            $href = C('config.site_url').$base_url . 'pages/houseMeter/facedoor/facedoor?'.$rr;
                            $tempKey='OPENTM400166399';
                        }
                        $call_device[]=array(
                            'device_id'=>$param['device_id'],
                            'call_id'=>$param['room_num'],
                            'user_mark'=>$getUserSig['user_mark'],
                            'add_time'=>time()
                        );
                        $send= $model->sendTempMsg($tempKey,
                            array(
                                'href' => $href,
                                'wecha_id' => $user_info['openid'],
                                'first' => $new_arr['msg'],
                                'keyword1' => '可视对讲',
                                'keyword2' => '待接听',
                                'keyword3' => date('H时i分', $_SERVER['REQUEST_TIME']),
                                'remark' => '请使用APP接听！'
                            )
                        );
                        fdump_api([__LINE__,$send,$a185_switch,$href,[
                            'wechat_appid'=>C('config.wechat_appid'),
                            'wechat_appsecret'=>C('config.wechat_appsecret'),
                            'href' => $href,
                            'wecha_id' => $user_info['openid'],
                            'first' => $new_arr['msg'],
                            'keyword1' => '可视对讲',
                            'keyword2' => '待接听',
                            'keyword3' => date('H时i分', $_SERVER['REQUEST_TIME']),
                            'remark' => '请使用APP接听！'
                        ]],'a185/wx_send',1);
                    }
                    //todo 抓拍人脸记录下
                    $send_user[$v['pigcms_id']]=empty($param['face_img']) ? '' : $param['face_img'];
                }
            }
            if(!empty($call_device)){
                fdump_api([__LINE__.'批量记录---',$call_device],'a185/A_call',1);
                $this->call_device_record($call_device);
            }
            if(!empty($send_user)){
                $data=unserialize($param['room_data']['data']);
                $data['send_user']=$send_user;
                fdump_api([__LINE__.'用户数据---',$data],'a185/a185_push_user',1);
                $Face_a185_call_record->where([
                    'id'=>$param['room_num'],
                ])->save(['data'=>serialize($data)]);
            }
        }
        //极光推送室内机
        $this->push_indoor($param);
        return true;
    }

    //todo 批量记录呼叫设备记录
    public function call_device_record($data){
        fdump_api([__LINE__.'批量记录---',$data],'a185/call_device_record',1);
        return D('Face_a185_call_device_record')->addAll($data);
    }

    //todo 推送室内机
    public function push_indoor($param){
        $Face_a185_call_record=D('Face_a185_call_record');
        $Face_a185_indoor=D('Face_a185_indoor');
        $device_sn_all=$Face_a185_call_record->where([
            'village_id'=>$param['village_id'],
            'status'=>1,
            'answer_device_sn'=>array('exp','is not null')
        ])->getField('answer_device_sn',true);
        $where=array(
            'village_id'=>$param['village_id'],
            'status'=>1
        );
        if($device_sn_all){
            $where['device_sn'] = ['not in',$device_sn_all];
        }
        $device=$Face_a185_indoor->where($where)->getField('device_sn',true);
        fdump_api([__LINE__,'室内机数据---',$device],'a185/push_indoor',1);
        if(empty($device)){
            return false;
        }
        $call_device=[];
        //todo 推送该小区房屋室内机和物业室内机
        foreach ($device as $v){
            $getUserSig=$this->getUserSig();
            $new_arr = [
                'device_id'=>$param['device_id'], //数据库自增设备id
                'device_sn'=>$param['device_sn'], //设备唯一id
                'push_source'=>'A185',
                'url'=>'',
                'title'=>'智慧社区',
                'msg'=>'您的好友正在通过'.$param['name'].'的门禁机邀请您视频，请点击进行视频沟通',
                'unitType'=>$param['device_type'],
                'channelNo'=>'',
                'device_type'=>$param['device_type'],
                'single_name'=>$param['single_name'],
                'floor_name'=>$param['floor_name'],
                'village_name'=>$param['village_name'],
                'user_mark'=>$getUserSig['user_mark'],
                'key'=>$getUserSig['key'],
                'sig'=>$getUserSig['sig'],
                'room_num'=>$param['room_num'],
                'face_img'=>$param['face_img'],
                'pigcms_id'=>0,
                'device_address'=>$param['village_name'].$param['name'],
                'app_tips'=>$param['app_tips']
            ];
            $call_device[]=array(
                'device_id'=>$param['device_id'],
                'call_id'=>$param['room_num'],
                'user_mark'=>$getUserSig['user_mark'],
                'add_time'=>time()
            );
            $audience   =   array('tag'=>array(str_replace('-','',$v)));
            fdump_api([__LINE__,'室内机标识---',$audience],'a185/push_indoor',1);
            $jpush=D('Group_order')->AuroraMass(2, $new_arr['title'], $new_arr['msg'], $new_arr,$audience);
            fdump_api([__LINE__,'极光推送室内机---',$getUserSig,$new_arr,$call_device,$jpush],'a185/push_indoor',1);
        }
        if(!empty($call_device)){
          $this->call_device_record($call_device);
        }
        return true;
    }

    /**
     *销毁房间
     * @author: liukezhu
     * @date : 2021/7/2
     */
    public function a185_closes($call_id=0){
        if(empty($call_id)){
            return false;
        }
        $Face_a185_call_record=D('Face_a185_call_record');
        $call_record=$Face_a185_call_record->where([
            'id'=>$call_id,
            'status'=>1
        ])->field('id,status,add_time')->find();
        fdump_api([__LINE__,'a185销毁房间--',$call_id,$call_record],'a185/a185_closes',1);
        if(!$call_record) return false;
        fdump_api([__LINE__,'a185房间数据--',$call_record],'a185/a185_closes',1);
        //todo 呼叫结束
        $Face_a185_call_record->where(['id' => $call_record['id']])->data([
            'status'=>2,
            'close_msg'=>'超时无人接听，自动挂断',
            'update_time'=>time()
        ])->save();
        $this->TencentCall->DismissRoom($call_record['id']);
        return true;
    }



    /**
     * a185生成子计划任务
     * @author: liukezhu
     * @date : 2021/9/1
     * @param $device   设备数据
     * @param $call_id  呼叫id
     * @param $plan_time  计划任务执行时间 时间戳
     * @return bool
     */
    public function a185_task($device,$call_id,$plan_time){
        $time=time();
        $param['param'] = serialize(['call_room_id'=>$call_id]);
        $param['plan_time'] = $plan_time;
        $param['space_time'] = 0;
        $param['add_time'] = $time;
        $param['file'] = 'sub_a185_call_record';
        $param['time_type'] = 1;
        $param['unique_id'] = 'a185_face_task_'.$device['device_id'].'_'.$device['village_id'].'_'.$call_id;
        $param['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
        fdump_api([__LINE__,'a185生成子计划任务---',$param],'a185/a185_task',1);
        $id=M('Process_sub_plan')->data($param)->add();
        fdump_api([__LINE__,'a185生成子计划任务ID：'.$id],'a185/a185_task',1);
        return true;
    }



}