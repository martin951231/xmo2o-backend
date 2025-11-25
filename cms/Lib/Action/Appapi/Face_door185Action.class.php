<?php
/**
 * 人脸门禁-A185接口
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/4/19 9:51
 *
 */

class Face_door185Action extends BaseAction{

    protected $face_device=[];
    protected $TencentCall=[];
    public function __construct(){
        parent::__construct();
        $action_name=strtolower(ACTION_NAME);
        if(!in_array($action_name,['get_room_parameter','get_indoor_device'])){
            $device_sn=isset($_POST['device_id']) ? trim($_POST['device_id']) : '';
            if(empty($device_sn)){
                $this->api_output(1003, [],'缺少设备ID参数');
            }
            $device_type = 61;
            $device = S($device_sn.'/device_a'.$device_type);
            if (! $device) {
                $where_device = [
                    'device_sn' => $device_sn,
                    'device_type'=>$device_type,
                    'is_del'=>0
                ];
                $device = D('House_face_device')->where($where_device)->find();
                $options = [];
                $options['expire'] = 86400;
                S($device_sn.'/device_a'.$device_type, $device, $options);
            }
            if(!$device){
                $this->api_output(1003, ['status'=>0],'当前设备未添加，请先在后台添加设备：'.$device_sn);
            }
            $this->face_device=$device;
        }
    }

    /**
     * 前端接口输出公共方法（json格式）
     * @param  integer $status    状态码
     *                            1000 		= 正常
     *                            1001		= 必填参数缺失错误
     *                            1002      = 暂无数据
     *                            1003		= 业务逻辑错误
    ...
     * @param  array   $data      需要返回给调用端的数据
     * @param  string  $msg       错误消息描述
     * @param  integer $http_code http状态码
     * @return json
     */
    private function api_output($status = 0, $data = [], $msg = '', $http_code = 200){
        if(!$data){
            $data = [];
        }
        $output = [
            'status' => $status  == '0' ? 1000 : $status,
            'msg' 	 => $msg,
            'data' 	 => $data
        ];
        fdump_api([__LINE__,$_POST,$this->is_app_browser,$this->DEVICE_ID],'a185/api_output',1);
        if (isset($_POST['Device-Id']) && in_array($_POST['Device-Id'],['packapp','wxapp'])){
            //todo 表示来源小程序或h5
            if($output['status'] == 1000){
                //正常状态
                $this->returnCode(0, $output['data']);
            }
            else{
                //提示状态
                $this->returnCode(1001,[],$output['msg']);
            }
        }
        else{
            $http_json = json_encode($output,JSON_UNESCAPED_UNICODE);
            echo $http_json;exit();
        }

    }

    /**
     * Notes:获取广告
     * @author: wanzy
     * @date_time: 2021/4/19 9:53
     */
    public function getAdvertList() {
        if(IS_POST){
            fdump_api(['获取广告>>'.__LINE__,$_POST],'a185/getAdvertList',1);
            $db_face_185_request_record = D('Face_a185_request_record');
            $rel=$db_face_185_request_record->getAdvert($this->face_device);
            $this->api_output(1000, $rel,'ok');
        }
    }

    /**
     * 获取设备配置信息
     * @author: liukezhu
     * @date : 2021/7/20
     *
     */
    public function getDeviceConfig(){
        if(IS_POST){
            fdump_api(['获取设备配置信息'.__LINE__,$_POST],'a185/getDeviceConfig',1);
            $Face_a185_request_record=D('Face_a185_request_record');
            $rel= $Face_a185_request_record->get_a185_config($this->face_device['device_sn'],$this->face_device,$this->config);
            $this->api_output(1000,$rel,'ok');
        }
    }

    /**
     * Notes: 下行  心跳 返回执行命令
     * @author: wanzy
     * @date_time: 2021/4/19 10:48
     */
    public function download() {
        $Face_a185_request_record=D('Face_a185_request_record');
        if(IS_POST){
            fdump_api(['心跳'.__LINE__,$_POST],'a185/download',1);
			if(isset($_POST['app_version']) && !empty($_POST['app_version'])){
				$data = [];
                $data['device_status'] = 1;
                $data['notify_time'] = time();
                $data['login_time'] = time();
				$data['sdk_version'] = $_POST['app_version'];
				M('House_face_device')->where(array('device_sn' => $this->face_device['device_sn']))->data($data)->save();
			} elseif (!empty($this->face_device)) {
                $data = [];
                $data['device_status'] = 1;
                $data['notify_time'] = time();
                M('House_face_device')->where(array('device_sn' => $this->face_device['device_sn']))->data($data)->save();
            }
            // 超过30分钟未交互的设备离线处理
            if (!empty($this->face_device)) {
                $where_other = [
                    'login_time' => ['elt', time()-1800],
                    'device_status' => 1,
                    'device_type' => 61
                ];
                $where_other['device_sn'] = ['neq', $this->face_device['device_sn']];
                $set_device = [
                    'device_status' => 2
                ];
                M('House_face_device')->where($where_other)->save($set_device);
            }
            $i = 0;
            while(true){
                if($i > 20){
                    fdump_api('心跳>>'.__FILE__,'a185/download',1);
                    // 暂无操作
                    $data=array(
                        'cmd'=>'',
                        'version'=>$Face_a185_request_record->version,
                        'index'=>0,
                        'data'=>[]
                    );
                    $this->api_output(1000, $data);
                }
                $Face_a185_request_record->a185_request_body($this->face_device,$_POST);
                $i++;
                sleep(1);
            }
        }
    }

    /**
     * Notes: 上行 上报人脸
     * @author: wanzy
     * @date_time: 2021/4/19 10:48
     */
    public function face_notify() {
        if(IS_POST){
            fdump_api(['上报人脸'.__LINE__,$_POST],'a185/face_notify',1);
            $db_face_185_request_record = D('Face_a185_request_record');
            $db_face_185_request_record->a185_face_notify($this->face_device,$_POST);
            $this->api_output(1000,[],'ok');
        }
    }

    /**
     * 人脸数据上报
     * @author: liukezhu
     * @date : 2021/12/20
     */
    public function face_report(){
        if(IS_POST){
            fdump_api(['上报人脸'.__LINE__,$_POST],'a185/face_report',1);
            $db_face_185_request_record = D('Face_a185_request_record');
            if(!isset($_POST['data']['pigcms_id']) || empty($_POST['data']['pigcms_id'])){
                $this->api_output(1003, ['status'=>0],'缺少必要参数');
            }
            if(!isset($_POST['data']['face_id']) || empty($_POST['data']['face_id'])){
                $this->api_output(1003, ['status'=>0],'缺少人脸图片ID');
            }
            $device=$this->face_device;
            $face_id=intval($_POST['data']['face_id']);
            $pigcms_id=intval($_POST['data']['pigcms_id']);
            $msg_info=trim($_POST['data']['msg_info']) ? trim($_POST['data']['msg_info']) : '';
            $msg_time=trim($_POST['data']['msg_time']) ? trim($_POST['data']['msg_time']) : '';
            $db_face_185_request_record->a185_face_report($pigcms_id,2,($msg_time.' '.$msg_info));
            $db_face_185_request_record->a185_create_face_record($device['device_id'],$pigcms_id,$device['village_id'],[],[],$device,$face_id,$msg_info);
            $this->api_output(1000,[],'ok');
        }
    }

    //todo 校验用户进房间
    private function check_user($device_id,$call_id,$user_mark){
        $check=D('Face_a185_call_device_record')->where([
            'device_id'=>$device_id,
            'call_id'=>$call_id,
            'user_mark'=>$user_mark,
        ])->getField('user_mark');
        fdump_api(['sql--'.D('Face_a185_call_device_record')->_sql(),$check,!$check],'a185/check_user_2',1);
        if(!$check){
            $this->api_output(1003, ['status'=>0],'该呼叫您没有权限进入');
        }
    }

    /**
     * Notes:呼叫接口
     * @author: wanzy
     * @date_time: 2021/4/19 11:11
     */
    public function call_user() {
        if(IS_POST){
            fdump_api(['呼叫接口>>'.__LINE__,$_POST],'a185/call_user',1);
            $Face_a185_call_record=D('Face_a185_call_record');
            $House_village_user_bind=D('House_village_user_bind');
            $face_device=$this->face_device;
            if(!isset($_POST['data']) || empty($_POST['data'])){
                $this->api_output(1003, [],'数据为空');
            }
            $d=$_POST['data'];
            if(empty($d['user_mark'])){
                $this->api_output(1003, [],'缺少用户标识');
            }
            if ($face_device['floor_id'] != "-1") {
                //单元机
                if(!$d['room_id']){
                    $house_village_user_vacancy =  M('House_village_layer')
                        ->join('as a LEFT JOIN '.C('DB_PREFIX')."house_village_user_vacancy as b ON a.id = b.layer_id")
                        ->field('a.single_id,a.floor_id,a.id as layer_id,b.pigcms_id as room_id')
                        ->where([
                            'a.floor_id'=>$face_device['floor_id'],
                            'a.status'=>1,
                            'a.layer_number'=>$d['layer_number'],
                            'b.room_number'=>$d['room_number'],
                            'b.status'=>array('in',[1,3]),
                            'b.is_del'=>0
                        ])->find();
                }
                else{
                    $house_village_user_vacancy =  M('House_village_user_vacancy')
                        ->where([
                            'floor_id'=>$face_device['floor_id'],
                            'status'=>array('in',[1,3]),
                            'is_del'=>0,
                            'pigcms_id'=>$d['room_id']
                        ])->field('single_id,floor_id,layer_id,pigcms_id as room_id')->find();
                }
                if(!$house_village_user_vacancy){
                    $this->api_output(1003, [],'房间信息未找到');
                }
                $single_id=$house_village_user_vacancy['single_id'];
                $floor_id=$house_village_user_vacancy['floor_id'];
                $layer_id=$house_village_user_vacancy['layer_id'];
                $room_id=$house_village_user_vacancy['room_id'];
            }
            else{
                //围墙机
                if(!$d['room_id']){
                    //楼栋
                    $single_info = M('House_village_single')->where(['single_number' => $d['single_number'],'status'=>1,'village_id'=>$face_device['village_id']])->find();
                    if(!$single_info){
                        $this->api_output(1003, [],'楼栋不存在');
                    }
                    //单元
                    $floor_info = M('House_village_floor')->where(['single_id'=>$single_info['id'],'floor_number' => $d['floor_number'],'status'=>1,'village_id'=>$face_device['village_id']])->find();
                    if(!$floor_info){
                        $this->api_output(1003, [],'单元不存在');
                    }
                    //楼层
                    $layer_info = M('House_village_layer')->where(['layer_number' => $d['layer_number'],'status'=>1,'single_id'=>$single_info['id'],'floor_id'=>$floor_info['floor_id'],'village_id'=>$face_device['village_id']])->find();
                    if(!$layer_info){
                        $this->api_output(1003, [],'楼层不存在');
                    }
                    //房间
                    $vacancy_info = M('House_village_user_vacancy')->where(['village_id'=>$face_device['village_id'],'single_id'=>$single_info['id'],'floor_id'=>$floor_info['floor_id'],'layer_id'=>$layer_info['id'],'room_number' => $d['room_number'],'status'=>array('in',[1,3])])->find();
                    if(!$vacancy_info){
                        $this->api_output(1003, [],'房间不存在');
                    }
                    $single_id=$single_info['id'];
                    $floor_id=$floor_info['floor_id'];
                    $layer_id=$layer_info['id'];
                    $room_id=$vacancy_info['pigcms_id'];
                }
                else{
                    $house_village_user_vacancy =  M('House_village_user_vacancy')
                        ->where([
                            'village_id'=>$face_device['village_id'],
                            'status'=>array('in',[1,3]),
                            'is_del'=>0,
                            'pigcms_id'=>$d['room_id']
                        ])->field('single_id,floor_id,layer_id,pigcms_id as room_id')->find();
                    if(!$house_village_user_vacancy){
                        $this->api_output(1003, [],'房间信息未找到');
                    }
                    $single_id=$house_village_user_vacancy['single_id'];
                    $floor_id=$house_village_user_vacancy['floor_id'];
                    $layer_id=$house_village_user_vacancy['layer_id'];
                    $room_id=$house_village_user_vacancy['room_id'];
                }
            }
            $where_bind=array(
                'village_id'=>$face_device['village_id'],
                'single_id'=>$single_id,
                'floor_id'=>$floor_id,
                'layer_id'=>$layer_id,
                'vacancy_id'=>$room_id,
                'status'=>1,
                'uid'=>['neq',0],
                'name'=>['neq',''],
                'phone'=>['neq',''],
                'type'=>['in', [0,1,2,3]]
            );
            $house_village_bind =  $House_village_user_bind->where($where_bind)->field('pigcms_id,uid,name,phone,type')->order('type asc')->find();
            if(!$house_village_bind){
                fdump_api('房间暂无人员录入>>'.__LINE__.$House_village_user_bind->_sql(),'a185/call_user',1);
                $this->api_output(1003, [],'房间暂无人员录入');
            }
            $result=(new TencentCall($Face_a185_call_record->getTCloudSdk()))->getUserSig($d['user_mark']);
            $record=array(
                'device_sn'=>$face_device['device_sn'],
                'device_id'=>$face_device['device_id'],
                'village_id'=>$face_device['village_id'],
                'person_id'=>(empty($d['person_id']) ? 0 : $d['person_id']),
                'called_room_id'=>$room_id,
                'data'=>serialize($_POST),
                'status'=>0,
                'add_time'=>time()
            );
            $rr= $Face_a185_call_record->a185_call_add($record);
            import("@.ORG.TencentCall");
            $result['room_num']=$rr;
            $result['user_mark']=$d['user_mark'];
           if(!$rr){
               $this->api_output(1003, [],'呼叫失败');
           }else{
               $this->api_output(1000,$result,'ok');
           }
        }
    }

    /**
     *进房间前校验
     * @author: liukezhu
     * @date : 2021/5/13
     */
    public function check_call_room(){
        if(IS_POST){
            fdump_api(['进房间校验'.__LINE__,$_POST],'a185/check_call_room',1);
            if(!isset($_POST['data']['user_mark']) || !isset($_POST['data']['room_num']) || !isset($_POST['data']['user_type'])){
                $this->api_output(1003, ['status'=>0],'缺少必要参数');
            }
            $user_mark=trim($_POST['data']['user_mark']);
            $room_num=trim($_POST['data']['room_num']);
            $user_type=trim($_POST['data']['user_type']);
            $Face_a185_call_record=D('Face_a185_call_record');
            $Face_a185_call_room_record=D('Face_a185_call_room_record');
            $Face_a185_request_record=D('Face_a185_request_record');
            $face_device=$this->face_device;
            $time=time();
            $rr=$Face_a185_call_record->where([
                'device_id'=>$face_device['device_id'],
                'id'=>$room_num
            ])->find();
            if(!$rr || intval($rr['status']) > 1){
                $this->api_output(1003, ['status'=>0],'该房间不存在或已过期');
            }
            if($user_type == 0){
                fdump_api([$user_type,$this->face_device['device_id'],$rr['id'],$user_mark],'a185/check_user',1);
                $this->check_user($this->face_device['device_id'],$rr['id'],$user_mark);
            }
            $face_img='';
            if(isset($_POST['data']['face_img']) && !empty($_POST['data']['face_img'])){
                $face_img=replace_file_domain($Face_a185_request_record->base64_to_img($_POST['data']['face_img']));
                fdump_api([__LINE__,$face_img],'a185/zp_face_img',1);
            }
            $add_data=array(
                'device_sn'=>$face_device['device_sn'],
                'device_id'=>$face_device['device_id'],
                'call_id'=>$rr['id'],
                'user_mark'=>$user_mark,
                'user_type'=>$user_type,
                'add_time'=>$time
            );
            $add_id =$Face_a185_call_room_record->data($add_data)->add();
            if(!$add_id){
                $this->api_output(1003, ['status'=>0],'校验失败');
            }
            if($user_type == 1 && $rr['status'] == 0){
                //todo 初次进入，视频开始中 更改呼叫中
                $face_a185_call_device_record=array(
                    'device_id'=>$face_device['device_id'],
                    'call_id'=>$rr['id'],
                    'user_mark'=>$user_mark,
                    'add_time'=>$time
                );
                D('Face_a185_call_device_record')->data($face_a185_call_device_record)->add();
                $Face_a185_call_record->where(['id' => $rr['id']])->data([
                    'status'=>1,
                    'add_time'=>$time
                ])->save();
                //消息推送
                fdump_api([__LINE__,$face_device,$rr['id'],$rr['called_room_id'],$face_img],'a185/check_call_room',1);
                $rr['user_mark']=$user_mark;
                $rr['plan_time']=$time;
                $Face_a185_call_record->a185_push($face_device,$rr,$face_img);
            }else{
                //todo 二次进入，接听成功
                if(isset($_POST['self_device_id']) && !empty($_POST['self_device_id']) && empty($rr['answer_device_sn'])){
                    //todo 仅针对室内机接听 记录接听设备id
                    $Face_a185_call_record->where(['id' => $rr['id']])->data(['answer_device_sn'=>trim($_POST['self_device_id'])])->save();
                }
            }
            $this->api_output(1000,['status'=>1],'ok');
        }
    }

    //todo 获取房间参数
    public function get_room_parameter(){
        if(IS_POST){
            fdump_api(['获取房间参数'.__LINE__,$_POST],'a185/get_room_parameter',1);
            $Face_a185_call_record=D('Face_a185_call_record');
            $Face_a185_request_record=D('Face_a185_request_record');
            if(!isset($_POST['room_num']) || !isset($_POST['pigcms_id']) || !isset($_POST['user_mark'])){
                $this->api_output(1003, [],'缺少必要参数');
            }
            $room_num=trim($_POST['room_num']);
            $pigcms_id=trim($_POST['pigcms_id']);
            $user_mark=trim($_POST['user_mark']);
            $rr=$Face_a185_call_record->where([
                'id'=>$room_num,
                'status'=>array('elt' , 1)
            ])->find();
            if(!$rr){
                $this->api_output(1003, [],'该房间不存在或已过期');
            }
            $this->check_user($rr['device_id'],$rr['id'],$user_mark);
            $data=unserialize($rr['data']);
            $face_img='';
            if(isset($data['send_user']) && !empty($data['send_user'])){
                $face_img=isset($data['send_user'][$pigcms_id]) ? $data['send_user'][$pigcms_id] : '';
            }
            import("@.ORG.TencentCall");
            $result=(new TencentCall($Face_a185_call_record->getTCloudSdk()))->getUserSig($user_mark);
            //获取设备所在信息
            $device_info = D('House_face_device')->where(['device_id'=>$rr['device_id']])->find();
            if(!$device_info){
                $this->api_output(1003, [],'设备不存在');
            }
            $address=$Face_a185_call_record->get_address($device_info);
            $param=array(
                'device_id'         =>  $rr['device_sn'],
                'village_name'      =>  $address['village_name'],
                'device_address'    =>  $address['device_address'],
                'sdkAppID'          =>  $result['key'],
                'userSig'           =>  $result['sig'],
                'room_num'          =>  $rr['id'],
                'face_img'          =>  $face_img,
                'user_mark'         =>  $user_mark,
                'user_type'         =>  0,
                'app_version'       =>  $Face_a185_request_record->version,
            );
            $this->api_output(1000, $param,'ok');
        }
    }

    //todo 呼叫拒绝
    public function call_refuse(){
        if(IS_POST){
            fdump_api(['呼叫拒绝'.__LINE__,$_POST],'a185/call_refuse_log',1);
            if(!isset($_POST['data']['room_num'])){
                $this->api_output(1003, [],'缺少必要参数');
            }
            $room_num=trim($_POST['data']['room_num']);
            $Face_a185_call_record=D('Face_a185_call_record');
            $call_record=$Face_a185_call_record->where([
                'device_sn'=>$this->face_device['device_sn'],
                'id'=>$room_num,
                'status'=>1
            ])->find();
            if(!$call_record){
                $this->api_output(1003, [],'该房间不存在或已过期');
            }
            //todo 呼叫结束
            $Face_a185_call_record->where(['id' => $call_record['id']])->data([
                'status'=>4,
                'close_msg'=>'业主已拒绝您的通话申请，可电话联系业主',
                'update_time'=>time()
            ])->save();
            import("@.ORG.TencentCall");
            $result=(new TencentCall($Face_a185_call_record->getTCloudSdk()))->DismissRoom($call_record['id']);
            fdump_api([__LINE__,$result],'a185/call_refuse_log',1);
            $this->api_output(1000,[],'ok');
        }
    }

    /**
     * 关闭呼叫
     * @author: liukezhu
     * @date : 2021/5/20
     */
    public function call_closes(){
        if(IS_POST){
            fdump_api(['关闭呼叫'.__LINE__,$_POST],'a185/call_closes_log',1);
            if(!isset($_POST['data']['room_num'])){
                $this->api_output(1003, [],'缺少必要参数');
            }
            $room_num=trim($_POST['data']['room_num']);
            $Face_a185_call_record=D('Face_a185_call_record');
            $call_record=$Face_a185_call_record->where([
                'device_sn'=>$this->face_device['device_sn'],
                'id'=>$room_num,
                'status'=>1
            ])->find();
            if(!$call_record){
                $this->api_output(1003, [],'该房间不存在或已过期');
            }
            //todo 呼叫结束
            $Face_a185_call_record->where(['id' => $call_record['id']])->data([
                'status'=>2,
                'close_msg'=>'关闭呼叫',
                'update_time'=>time()
            ])->save();
            import("@.ORG.TencentCall");
            $result=(new TencentCall($Face_a185_call_record->getTCloudSdk()))->DismissRoom($call_record['id']);
            fdump_api([__LINE__,$result],'a185/call_closes_log',1);
            $this->api_output(1000,[],'ok');
        }
    }

    /**
     *远程开门
     * @author: liukezhu
     * @date : 2021/5/20
     *
     */
    public function gpioControl(){
        if(IS_POST){
            fdump_api(['远程开门'.__LINE__,$_POST],'a185/gpioControl',1);
            $pigcms_id=trim($_POST['data']['pigcms_id']);
            if(isset($_POST['data']['self_device_id']) && !empty($_POST['data']['self_device_id'])){
                $pigcms_id=0;
            }else{
                if(!$pigcms_id){
                    $this->api_output(1003, ['status'=>0],'缺少必要参数');
                }
            }
            $a185 = D('Face_a185_request_record');
            $res=$a185->a185_gpio($this->face_device['device_id'],$this->face_device['village_id'],$pigcms_id);
            if($res['error'] > 0){
                $this->api_output(1003, ['status'=>0],'开门失败');
            }
            $this->api_output(1000, ['status'=>1],'开门成功');
        }
    }

    /**
     *呼叫状态
     * @author: liukezhu
     * @date : 2021/5/21
     */
    public function call_status(){
        if(IS_POST){
            fdump_api(['呼叫状态'.__LINE__,'post---',$_POST],'a185/call_status',1);
            if(!isset($_POST['data']['room_num']) || !isset($_POST['data']['user_mark'])){
                $this->api_output(1003, [],'缺少必要参数,room_num|user_mark');
            }
            $room_num=trim($_POST['data']['room_num']);
            $user_mark=trim($_POST['data']['user_mark']);
            $Face_a185_call_record=D('Face_a185_call_record');
            $call_record=$Face_a185_call_record->where([
                'device_sn'=>$this->face_device['device_sn'],
                'id'=>$room_num,
            ])->field('id,device_id,close_msg,status')->find();
            fdump_api([__LINE__,$call_record],'a185/call_status',1);
            if(!$call_record){
                $this->api_output(1000,['status'=>0],'呼叫不存在');
            }
            $status=(int)$call_record['status'];
            if($status == 4){
                $this->api_output(1000,['status'=>4],$call_record['close_msg']);
            }elseif ($status == 1){
                $check=D('Face_a185_call_device_record')->where([
                    'device_id'=>$this->face_device['device_id'],
                    'call_id'=>$call_record['id'],
                    'user_mark'=>$user_mark,
                ])->getField('user_mark');
                fdump_api(['$check状态'.__LINE__,D('Face_a185_call_device_record')->_sql(),$check],'a185/call_status',1);
                if($check){
                    $this->api_output(1000,['status'=>1],'ok');
                }else{
                    $this->api_output(1000,['status'=>0],'呼叫不存在');
                }
            }else{
                $this->api_output(1000,['status'=>0],'呼叫已关闭');
            }
        }
    }

    //todo 获取设备新闻公告
    public function get_device_news(){
        if(IS_POST){
            $village_news=D('House_village_news')->get_news([
                'village_id'=>$this->face_device['village_id'],
                'status'=>'1',
                'is_device_show'=>1
            ],'content,add_time');
            if($village_news){
                $village_news['add_time']=date('m-d H:i:s',$village_news['add_time']);
                $village_news['content']=htmlspecialchars_decode($village_news['content']);
                $this->api_output(1000, $village_news,'ok');
            }else{
                $this->api_output(1003, [],'暂无数据');
            }
        }
    }


    //--------------------todo 室内机---

    /**
     * 获取设备A185室内机数据
     * @author: liukezhu
     * @date : 2021/8/9
     */
    public function get_indoor_device(){
        if(IS_POST){
            fdump_api(['获取室内机数据'.__LINE__,$_POST],'a185/get_indoor_device',1);
            if(!isset($_POST['device_id'])){
                $this->api_output(1003, [],'缺少必要参数');
            }
            $device_sn=trim($_POST['device_id']);
            $Face_a185_indoor= D('Face_a185_indoor');
            $device=$Face_a185_indoor->a185_indoor_query([
                'device_sn'=>$device_sn,
                'status'=>1,
            ]);
            if(!$device){
                $this->api_output(1003, [],'该设备未登记，请联系物业操作。设备ID：'.$device_sn);
            }
            if(empty($device['login_time'])){
                $Face_a185_indoor->a185_indoor_edit(['id' => $device['id']],['login_time'=>time()]);
            }
            $this->api_output(1000, [
                'device_id'=>$device['device_sn'],
                'device_type'=>$device['device_type']
            ],'ok');
        }
    }

}