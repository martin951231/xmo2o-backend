<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2020/9/5 10:23
 *
 */

class Face_d4_request_recordModel extends Model {

    public $deviceJdk='';
    public function __construct()
    {
        parent::__construct();
        import('@.ORG.face.d4_face');
        $this->deviceJdk = new d4_face();
    }


    /**
     *设备识别记录
     * @author: liukezhu
     * @date : 2021/7/26
     * @param $device_sn  设备号
     * @param $type       1：人脸抓拍 2：刷卡
     * @param $post
     * @return bool
     */
    public function d4_notify($device_sn,$type,$post) {
        fdump(['参数写入--',$device_sn,$type,$post],'api/log/'.date('Ymd').'/d4/d4_notify',true);
        $db_house_face_device = M('House_face_device');
        $d_house_user_log = M('House_user_log');
        $d_house_village_floor = M('House_village_floor');
        $d_house_village = M('House_village');
        $d_house_village_user_bind = D('House_village_user_bind');
        $d_house_camera_tip = M('House_camera_tip');
        $device_info =$db_house_face_device->where(array('device_sn' => $device_sn,'is_del'=>0,'device_type'=>24))->find();
        if(!$device_info){
            fdump(['设备不存在--',$post],'api/log/'.date('Ymd').'/d4/d4_notify',true);
            return false;
        }
        if(!isset($post['data']['guid']) || empty($post['data']['guid']) || !(intval($post['data']['guid']) > 0)){
            $guid=0;
            fdump(['guid参数--',$post],'api/log/'.date('Ymd').'/d4/d4_notify',true);
//            return false;
        }else{
            $guid=$post['data']['guid'];
        }
        $face_status=1; //识别失败
        $user_image=$post['data']['photoUrl'];

        if($user_image){
            fdump(['图片--',$user_image,$device_sn,$type,$post],'api/log/'.date('Ymd').'/d4/d4_notify',true);
            $user_image = D('Face_door_service')->face_to_img($user_image,1);
        }

        $house_village_info = $d_house_village->where(array('village_id' => $device_info['village_id']))->find();
        if(intval($guid) > 0){
            $where_bind = [
                'pigcms_id' => $guid
            ];
            $house_village_bind = $d_house_village_user_bind->where($where_bind)->find();
            if(!$house_village_bind){
                fdump(['village_user_bind不存在--',$post],'api/log/'.date('Ymd').'/d4/d4_notify',true);
                return false;
            }
            $face_status=0; //识别成功
            $uid = $house_village_bind['uid'];
        }else{
            $uid=0;
        }
        $city_id = $house_village_info['city_id'];
        $log_name = '';
        $log_name .= $house_village_info['village_name'];
        if ($device_info['floor_id'] != -1) {
            $floor_arr = explode(",",$device_info['floor_id']);
            $house_village_floor_info = $d_house_village_floor
                ->join('as a LEFT JOIN '.C('DB_PREFIX')."house_village_single as b ON a.single_id = b.id")
                ->field('a.*, b.single_name')
                ->where(array('floor_id' => ['in', $floor_arr]))
                ->select();
            if ($house_village_floor_info) {
                $data_msg = [];
                foreach ($house_village_floor_info as $val) {
                    if ($val && $val['floor_name']) {
                        $data_msg[] = ($val['single_name'] ? $val['single_name'] : $val['floor_layer']) . $val['floor_name'];
                    }
                }
                if ($data_msg) {
                    $msg = implode(';',$data_msg);
                    $log_name .= ' ' . $msg;
                }
            }
        }
        elseif ( $device_info['public_area_id'] >0 ) {
            $public_area_name = M('House_village_public_area')->where(['public_area_id' => $device_info['public_area_id']])->getField('public_area_name');
            $log_name .= ' ' . $public_area_name;
        }
        elseif ( $device_info['floor_id'] == -1) {
            $floor_layer	= C('config.house_name');
            $floor_name = '大门';
            $log_name .= ' ' . $floor_layer.$floor_name;
        }
        $log = array();
        $arrData['last_result'] = $post;
        $arrData['face_img'] = $user_image;
        $log['uid'] = $uid ? $uid : 0;
        $log['log_bind_id'] = $guid;
        $log['log_name'] = $log_name;
        $log['log_business_id'] = $device_info['village_id']; // 社区id
        $log['log_more_id'] = $device_info['floor_id']; // 单元id
        $log['device_id'] = $device_info['device_id']; // 设备id
        if($type == 1){
            $log['log_from'] = 1; // 人脸识别门禁
        }elseif ($type == 2){
            $log['log_from'] = 3; // 刷卡开门禁
        }
        $log['log_time'] = time(); // 记录时间
        // 0 比对通过，1 比对失败， 2 无模版， 3 无效卡 4 无现场照，5 非活体， 6 证件过期 7 超过时间限制， 8 超过此数限制 100 未知错误
        $log['log_status']=$face_status;

        $log['temperature'] ='0.0';
        if (isset($arrData['face_img']) && trim($arrData['face_img'])) {
            $log['cut_image'] = trim($arrData['face_img']);
        }
        $log['log_detail'] = serialize($arrData);
        fdump(['组装数据--',$log],'api/log/'.date('Ymd').'/d4/d4_notify',true);
        $log['add_time']=time();
        $rel=$d_house_user_log->data($log)->add();
        if(!$rel){
            fdump(['记录写入失败--',$d_house_user_log->_sql()],'api/log/'.date('Ymd').'/d4/d4_notify',true);
            return false;
        }
        $db_house_face_device->where(array('device_id' => $device_info['device_id']))->setField('last_open_time', time());
        // 同步记录到消息列表
        $tip = array(
            'bind_id' => $uid,
            'uid' => $uid,
            'type' => 1,
            'village_id' => $device_info['village_id'],
            'camera_sn' => $device_info['device_sn'],
            'city_id' => $city_id,
            'camera_time' => $log['log_time'],
            'camera_img' => $user_image,
            'tip_time' => time(),
            'status' => $face_status,
            'tip_txt' => serialize($arrData)
        );
        return $d_house_camera_tip->data($tip)->add();
    }


    /**
     * 远程开门
     * @author: liukezhu
     * @date : 2021/7/26
     * @param $device_id
     * @param int $village_id
     * @param $uid
     * @return array
     */
    public function d4_gpio($device_id,$village_id=0,$pigcms_id) {
        $db_house_face_device = M('House_face_device');
        $d_house_user_log = M('House_user_log');
        $d_house_village_floor = M('House_village_floor');
        $d_house_village = M('House_village');
        $d_house_village_user_bind = D('House_village_user_bind');
        $d_house_camera_tip = M('House_camera_tip');
        fdump([$device_id,$village_id,$pigcms_id],'api/log/'.date('Ymd').'/d4/d4_gpio',true);
        $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        if (!$village_id && $device_info['village_id']) {
            $village_id = $device_info['village_id'];
        }
        // 具体请求区分名称
        $cmd_name = 'gpio_control';
        $device_sn = $device_info['device_sn'];
        $operator_type = 1;
        $operator_id = $village_id;
        $status = 1;
        $add_time = time();
        $ret_data = [
            'error' => 0,
            'msg' => ''
        ];
        //todo 远程开门
        $rr=$this->deviceJdk->openDevice($device_info['device_sn']);
        if(!$rr['error']){
            $ret_data['error'] = 1;
            $ret_data['msg'] = '开门失败';
            fdump($rr,'api/log/'.date('Ymd').'/d4/d4_gpio',true);
            return $ret_data;
        }
        // 整合需要添加的数据
        $add_data = [
            'village_id' => $village_id,
            'device_sn' => $device_sn,
            'device_id' => $device_id,
            'operator_type' => $operator_type,
            'operator_id' => $operator_id,
            'bind_id'=>$pigcms_id,
            'cmd_name' => $cmd_name,
            'status' => $status,
            'data' => serialize($rr),
            'add_time' => $add_time,
            'sort' => 100
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {
            $house_village_info = $d_house_village->where(array('village_id' => $device_info['village_id']))->find();
            $where_bind = [
                'pigcms_id' => $pigcms_id
            ];
            $house_village_bind = $d_house_village_user_bind->where($where_bind)->field('uid')->find();
            if(!$house_village_bind){
                fdump(['village_user_bind不存在--',$where_bind],'api/log/'.date('Ymd').'/d4/d4_gpio',true);
                return false;
            }
            $city_id = $house_village_info['city_id'];
            $log_name = '';
            $log_name .= $house_village_info['village_name'];
            if ($device_info['floor_id'] != -1) {
                $floor_arr = explode(",",$device_info['floor_id']);
                $house_village_floor_info = $d_house_village_floor
                    ->join('as a LEFT JOIN '.C('DB_PREFIX')."house_village_single as b ON a.single_id = b.id")
                    ->field('a.*, b.single_name')
                    ->where(array('floor_id' => ['in', $floor_arr]))
                    ->select();
                if ($house_village_floor_info) {
                    $data_msg = [];
                    foreach ($house_village_floor_info as $val) {
                        if ($val && $val['floor_name']) {
                            $data_msg[] = ($val['single_name'] ? $val['single_name'] : $val['floor_layer']) . $val['floor_name'];
                        }
                    }
                    if ($data_msg) {
                        $msg = implode($data_msg,';');
                        $log_name .= ' ' . $msg;
                    }
                }
            }
            elseif ( $device_info['public_area_id'] >0 ) {
                $public_area_name = M('House_village_public_area')->where(['public_area_id' => $device_info['public_area_id']])->getField('public_area_name');
                $log_name .= ' ' . $public_area_name;
            }
            elseif ( $device_info['floor_id'] == -1) {
                $floor_layer	= C('config.house_name');
                $floor_name = '大门';
                $log_name .= ' ' . $floor_layer.$floor_name;
            }
            $log = array();
            $uid = $house_village_bind['uid'];
            $log['uid'] = $uid ? $uid : 0;
            $log['log_bind_id'] = $pigcms_id;
            $log['log_name'] = $log_name;
            $log['log_business_id'] = $device_info['village_id']; // 社区id
            $log['log_more_id'] = $device_info['floor_id']; // 单元id
            $log['device_id'] = $device_info['device_id']; // 设备id
            $log['log_from'] = 2; // 人脸识别门禁
            $log['log_time'] = time(); // 记录时间
            // 0 比对通过，1 比对失败， 2 无模版， 3 无效卡 4 无现场照，5 非活体， 6 证件过期 7 超过时间限制， 8 超过此数限制 100 未知错误
            $log['log_status']=0;

            $log['temperature'] ='0.0';
            $log['log_detail'] = serialize($rr);
            $log['add_time']=time();
            $d_house_user_log->data($log)->add();
            $db_house_face_device->where(array('device_id' => $device_info['device_id']))->setField('last_open_time', time());
            // 同步记录到消息列表
            $tip = array(
                'bind_id' => $uid,
                'uid' => $uid,
                'type' => 1,
                'village_id' => $device_info['village_id'],
                'camera_sn' => $device_info['device_sn'],
                'city_id' => $city_id,
                'camera_time' => $log['log_time'],
                'camera_img' => '',
                'tip_time' => time(),
                'status' => 1,
                'tip_txt' => serialize($rr)
            );
            $d_house_camera_tip->data($tip)->add();
            $ret_data['data'] = $add_id;
            return $ret_data;
        } else {
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            return $ret_data;
        }
    }

    /**
     * 删除设备
     * @author: liukezhu
     * @date : 2021/7/14
     * @param $device
     * @return mixed
     */
    public function d4_delete($device) {
        // 具体请求区分名称
        $cmd_name = 'delete_device';
        $status = 1;
        $add_time = time();
        $rr=[];
        if(!empty($device['ai_device_sn'])){
            $rr=$this->deviceJdk->delDevice($device['ai_device_sn']);
        }
        fdump_api([$device,$rr],'d4/d4_delete',1);
        // 整合需要添加的数据
        $add_data = [
            'village_id' => $device['village_id'],
            'device_id' => $device['device_id'],
            'device_sn' => $device['device_sn'],
            'bind_id' => 0,
            'operator_type' => 0,
            'operator_id' => $device['village_id'],
            'cmd_name' => $cmd_name,
            'status' => $status,
            'data' => serialize($rr),
            'add_time' => $add_time,
            'sort' => 99
        ];
        $add_id = $this->data($add_data)->add();
        return $add_id;
    }

    /**
     *获取d4设备信息
     * @author: liukezhu
     * @date : 2021/7/14
     */
    public function d4_get_device($device){
        // 具体请求区分名称
        $cmd_name = 'query_device';
        $status = 1;
        $add_time = time();
        $rr=$this->deviceJdk->queryDevice($device['device_sn']);
        if($rr['error']){
            $d_house_face_device = M('House_face_device');
            $onlineState=$rr['data']['onlineState'];
            $save=array(
//                'device_name'=>$rr['data']['name'],
                'login_time'=>time()
            );
            if($onlineState == 1){
                $save['device_status']=1;
            }else{
                $save['device_status']=2;
            }
            if(empty($device['ai_device_sn'])){
                $save['ai_device_sn']=$rr['data']['devicesn'];
            }
            $d_house_face_device->where(array('device_id' => $device['device_id']))->save($save);
        }
        // 整合需要添加的数据
        $add_data = [
            'village_id' => $device['village_id'],
            'device_id' => $device['device_id'],
            'device_sn' => $device['device_sn'],
            'bind_id' => 0,
            'operator_type' => 0,
            'operator_id' => $device['village_id'],
            'cmd_name' => $cmd_name,
            'status' => $status,
            'data' => serialize($rr),
            'add_time' => $add_time,
            'sort' => 99
        ];
        $add_id = $this->data($add_data)->add();
        return $add_id;
    }


    /**
     * d4重启
     * @author: liukezhu
     * @date : 2021/7/14
     * @param $device
     */
    public function d4_restart($device){
        // 具体请求区分名称
        $cmd_name = 'restart_device';
        $status = 1;
        $add_time = time();
        $rr=$this->deviceJdk->queryDevice($device['device_sn']);
        if($rr['error']){
            $rr=$this->deviceJdk->restartDev($device['device_sn']);
        }
        // 整合需要添加的数据
        $add_data = [
            'village_id' => $device['village_id'],
            'device_id' => $device['device_id'],
            'device_sn' => $device['device_sn'],
            'bind_id' => 0,
            'operator_type' => 0,
            'operator_id' => $device['village_id'],
            'cmd_name' => $cmd_name,
            'status' => $status,
            'data' => serialize($rr),
            'add_time' => $add_time,
            'sort' => 99
        ];
        $add_id = $this->data($add_data)->add();
        return $add_id;
    }


    /**
     * 添加、编辑设备
     * @author: liukezhu
     * @date : 2021/7/14
     * @param $device_id 设备号
     * @param $type     1：添加 2：编辑
     *
     */
    public function d4_device($device_id,$type){
        $House_face_device=M('House_face_device');
        $device = $House_face_device->where(array('device_id' => $device_id))->find();
        if(!$device){
            fdump('设备不存在','api/log/'.date('Ymd').'/d4/d4_device_log',true);
            return false;
        }
        $data=[];
        $status = 1;
        $add_time = time();
        if($type == 1){
            $cmd_name = 'add_device';
            $rr=$this->deviceJdk->addDevice($device['device_sn'],$device['device_name']);
            if($rr['error']){
                $data['ai_device_sn']=$rr['data']['devicesn'];
                $data['device_status']=1;
            }else{
                $data['device_status']=2;
            }
        }else{
            $cmd_name = 'edit_device';
            $rr=$this->deviceJdk->queryDevice($device['device_sn']);
            if(empty($device['ai_device_sn'])){
                if(!$rr['error']){
                    $rr=$this->deviceJdk->addDevice($device['device_sn'],$device['device_name']);
                    if($rr['error']){
                        $data['ai_device_sn']=$rr['data']['devicesn'];
                        $data['device_status']=1;
                    }else{
                        $data['device_status']=2;
                    }
                }
            }
        }
        if(!empty($data)){
            $House_face_device->where(['device_id'=>$device['device_id']])->save($data);
        }
        // 整合需要添加的数据
        $add_data = [
            'village_id' => $device['village_id'],
            'device_id' => $device['device_id'],
            'device_sn' => $device['device_sn'],
            'bind_id' => 0,
            'operator_type' => 0,
            'operator_id' => $device['village_id'],
            'cmd_name' => $cmd_name,
            'status' => $status,
            'data' => serialize($rr),
            'add_time' => $add_time,
            'sort' => 99
        ];
        $add_id = $this->data($add_data)->add();
        return $add_id;
    }


    /**
     * Notes: 添加或者编辑人脸
     * @param int $device_id 设备id
     * @param int $pigcms_id 小区用户绑定id
     * @param int $village_id 小区id
     * @param array $data 内容数组
     * @param array $bind_info 用户绑定身份信息
     * @param array $device_info 设备信息
     * @return mixed
     * @author: wanzy
     * @date_time: 2020/9/5 14:32
     */
    public function add_create_face_record($device_id,$pigcms_id,$village_id=0,$bind_info = [],$data=[],$device_info=[]) {
        if (empty($bind_info)) {
            $bind_info = M('House_village_user_bind')->where(array('pigcms_id' => $pigcms_id))->find();
        }
        //添加同步记录
        $logId = 0;
//        $logId = M('House_face_sync_log')->add([
//            'pigcms_id'=>$pigcms_id,
//            'face_id'=>0,
//            'device_id'=>$device_id,
//            'device_sn'=>$device_info['device_sn'],
//            'log_time'=>time(),
//            'status'=>0,
//            'device_info'=>json_encode($device_info),
//        ]);
        if (!$village_id && $bind_info['village_id']) {
            $village_id = $bind_info['village_id'];
        } elseif (!$village_id && $device_info['village_id']) {
            $village_id = $device_info['village_id'];
        }
        $d_face_user_bind_device = M('Face_user_bind_device');
        $where_face_bind_about = [
            'bind_type' => 0,
            'bind_id' => $pigcms_id,
            'device_type' => 24,
            'device_id' => $device_id,
        ];
        $bind_user = $d_face_user_bind_device->where($where_face_bind_about)->find();
        if ($bind_user) {
            // 如果存在同步过数据 即为编辑
            // 具体请求区分名称
            $cmd_name = 'update_person';
        } else {
            // 未同步过进行添加
            $cmd_name = 'add_person';
        }
        $device_sn = $device_info['device_sn'];
        $operator_type = isset($data['operator_type']) ? intval($data['operator_type']) : 1;
        $operator_id = isset($data['operator_id']) ? intval($data['operator_id']) : $village_id;
        $status = 0;
        $add_time = time();
        $img_url='';
        $e_time=0;
        // 获取下图片
        if ($bind_info['uid']>0) {
            $d_house_face_img = M('House_face_img');
            $image_info = $d_house_face_img->where(array('uid'=>$bind_info['uid'],'status'=>['in', [0,3]],'img_url'=>array('neq','')))->order('add_time ASC')->find();
            $img_url = $image_info['img_url'];
            if (3==$image_info['status']) {
                import('@.ORG.face.word_encryption');
                $word_encryption = new word_encryption();
                // 加密的图片解密
                $img_url = $word_encryption->text_decrypt($img_url);
            }
//            $imgurl_=$img_url;
            $img_url = thumb_img($img_url,600,600,'', true);

            //,pathinfo($imgurl_, PATHINFO_EXTENSION)
            $img_url = Image::transform_image($img_url);

            $file_handle = new file_handle();
            if($file_handle->check_open_oss()){
                $img_url = thumb_img($img_url,600,600,'',true);
            }
            fdump($img_url,'api/log/'.date('Ymd').'/d4/face_img_log',true);
        }
        if(empty($img_url)){
            $data_set = [
                'face_img_status' => 2,
                'face_img_reason' =>'请上传图片',
            ];
            D('House_village_user_bind')->where(['pigcms_id' => $bind_info['pigcms_id']])->data($data_set)->save();
            fdump(['没有图片',$bind_info],'api/log/'.date('Ymd').'/d4/face_img_log',true);
            if($logId){
                M('House_face_sync_log')->where(['log_id'=>$logId])->save([
                    'status'=>2,
                    'msg'=>L_('没有图片'),
                    'last_time'=>time()
                ]);
            }
            return false;
        }

        $userTimes =  D('House_village_user_bind')->getUserEndTime($pigcms_id);
        if (isset($userTimes['propertyEndTime'])) {
            $e_time = intval($userTimes['propertyEndTime']);
        } else {
            $e_time = 0;
        }
        if (in_array($bind_info['type'],[5,6]) && !$e_time) {
            fdump($device_id,'api/log/'.date('Ymd').'/d4/add_create_face_record_err_log',true);
            if($logId){
                M('House_face_sync_log')->where(['log_id'=>$logId])->save([
                    'status'=>2,
                    'msg'=>L_('游客或者临时出入证无法同步'),
                    'last_time'=>time()
                ]);
            }
            return false;
        }

        if ($bind_info['ic_card']) {
            $data_msg['long_card_id'] = intval($bind_info['ic_card']); // 韦根号,智能卡号(可选)
        }
        $endTime=date('Y-m-d H:i:s',2145888000);
        if(!empty($e_time)){
            $endTime=date('Y-m-d H:i:s',$e_time);
        }

        /**
         * todo d4 添加人员
         */
        $rr=$this->deviceJdk->addPerson($device_info['device_sn'],array(
            array(
                'guid'=>$pigcms_id,
                'url'=>$img_url,
                'name'=>$bind_info['name'],
                'endTime'=>$endTime
            )
        ));
        $save_bind = [];
        $remark='';
        if($rr && isset($rr['ret'])){
            $remark=json_encode($rr['ret'],JSON_UNESCAPED_UNICODE);
        }elseif($rr){
            $remark=json_encode($rr,JSON_UNESCAPED_UNICODE);
        }
        if($rr['error']){
            $save_bind['face_img_status'] = 1;
            /**
             * todo d4新增卡片
             */
            if(intval($bind_info['ic_card']) > 0 && $endTime){
                //todo 删除卡号
                $this->deviceJdk->delCard($device_info['device_sn'],$bind_info['ic_card']);
                //todo 添加新的卡号
                $this->deviceJdk->addCard($device_info['device_sn'],$bind_info['ic_card'],$endTime);
            }
        }
        else{
            $save_bind['face_img_status'] = 2;
            $save_bind['face_img_reason'] = $rr['data'];
        }

        if($bind_user){
            $data_save = [
                'person_id' =>$pigcms_id,
                'last_time' => time()
            ];
            $d_face_user_bind_device->where(['id'=>$bind_user['id']])->save($data_save);
        }else{
            $data_face_bind_about = [
                'bind_type' => 0,
                'bind_id' => $pigcms_id,
                'device_id' => $device_id,
                'device_type' => 24,
                'person_id' =>  $pigcms_id,
                'add_time' => time()
            ];
            $d_face_user_bind_device->data($data_face_bind_about)->add();
        }


        // 整合需要添加的数据
        $add_data = [
            'village_id' => $village_id,
            'device_sn' => $device_sn,
            'device_id' => $device_id,
            'bind_id' => $pigcms_id,
            'operator_type' => $operator_type,
            'operator_id' => $operator_id,
            'cmd_name' => $cmd_name,
            'status' => $status,
            'data' => serialize($rr),
            'add_time' => $add_time,
            'remark'=>$remark
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {
            $ret_data['data'] = $add_id;
            $db_House_village_user_bind=M('House_village_user_bind');
            $db_House_village_user_bind->where(['pigcms_id' => $pigcms_id])->data($save_bind)->save();
            if($logId){
                M('House_face_sync_log')->where(['log_id'=>$logId])->save([
                    'status'=>1,
                    'msg'=>'',
                    'last_time'=>time()
                ]);
            }
            return $ret_data;
        }
        else {
            fdump('>>>>>>>>>>>>>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/d4/add_create_face_record_err_log',true);
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            if($logId){
                M('House_face_sync_log')->where(['log_id'=>$logId])->save([
                    'status'=>2,
                    'msg'=>L_('同步失败'),
                    'last_time'=>time()
                ]);
            }
            return $ret_data;
        }
    }

    /**
     * 循环处理同步人员
     * @author liukezhu
     * @date 2021/4/16
     * @param $device_id
     * @param $bind_arr_id
     * @return bool
     */
    public function d4_arr_person_msg($device_id,$bind_arr_id,$orderGroupId='') {
        fdump([$device_id,$bind_arr_id],'api/log/'.date('Ymd').'/d4/d4_arr_person_msg',true);
        if ($bind_arr_id) {
            foreach ($bind_arr_id as $item) {
                $this->sync_d4_face_per($device_id, $item,0,[],$orderGroupId);
            }
        }
        return ['err_msg'=>'操作成功'];
    }

    /**
     * Notes: 单个小区绑定用户同步
     * @param $device_id
     * @param $pigcms_id
     * @param int $village_id
     * @param array $device_info
     * @return bool
     * @author: wanzy
     * @date_time: 2020/9/8 9:31
     */
    public function sync_d4_face_per($device_id, $pigcms_id,$village_id=0, $device_info=[],$orderGroupId='') {
        if (empty($device_info)) {
            $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        if (!$pigcms_id) {
            fdump('$pigcms_id == false','api/log/'.date('Ymd').'/d4/sync_d4_face_per',true);
            return false;
        }
        if (!$village_id && $device_info['village_id']) {
            $village_id = $device_info['village_id'];
        }
        // 不论是单个还是全部转换成 数组
        $where = [
            'village_id' => $village_id,
            'pigcms_id' => $pigcms_id,
            'status' => 1
        ];
        $db_house_village_user_bind = M('House_village_user_bind');
        $user_bind = $db_house_village_user_bind->where($where)->find();
        if ($user_bind) {
            $data = [
                'operator_type' => 0,
                'operator_id' => $pigcms_id,
            ];
            $this->add_create_face_record($device_id, $pigcms_id, $village_id, $user_bind, $data, $device_info);
        }
        return true;
    }

    /**
     * 同步删除设备上人员
     * @author liukezhu
     * @date 2021/4/16
     * @param $pigcms_id
     * @return array|bool
     */
    public function d4_delete_face($device_id,$pigcms_id,$user_bind) {
        fdump([$device_id,$pigcms_id,$user_bind],'api/log/'.date('Ymd').'/d4/d4_delete_face',true);
        $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        if(!$device_info){
            return false;
        }
        //软删除图片资源
        D('House_face_img')->where(array(
            'uid'=>$user_bind['uid']
        ))->data(['status' => 1, 'del_time' => time()])->save();

        $ret_data = [
            'error' => 0,
            'msg' => ''
        ];
        // 具体请求区分名称
        $cmd_name = 'delete_person';
        $rr=$this->deviceJdk->delFace($device_info['device_sn'],$pigcms_id);
        $device_sn = $device_info['device_sn'];
        $add_time = time();
        // 整合需要添加的数据
        $add_data = [
            'village_id' => $device_info['village_id'],
            'device_sn' => $device_sn,
            'device_id' => $device_info['device_id'],
            'bind_id' => $pigcms_id,
            'operator_type' => 0,
            'operator_id' => $device_info['village_id'],
            'cmd_name' => $cmd_name,
            'status' => 1,
            'data' => serialize($rr),
            'add_time' => $add_time,
            'sort' => 99
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {

            $d_face_user_bind_device = M('Face_user_bind_device');
            $where_face_bind_about = [
                'bind_type' => 0,
                'bind_id' => $pigcms_id,
                'device_type' => 24,
                'device_id' => $device_id,
            ];
            // 删除关联关系
            $d_face_user_bind_device->where($where_face_bind_about)->delete();


            $ret_data['data'] = $add_id;
            return $ret_data;
        } else {
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            fdump($ret_data,'api/log/'.date('Ymd').'/d4/add_delete_face_record_log',true);
            return $ret_data;
        }
    }

    /**
     * Notes: 同步所有用户信息
     * @param $device_id
     * @param int $village_id
     * @param string $floor_id
     * @param array $device_info
     * @param int $is_plan 是否为计划任务
     * @return bool
     * @author: wanzy
     * @date_time: 2020/9/8 9:25
     */
    public function sync_d4_face_all_per($device_id, $village_id=0, $floor_id='', $device_info=[],$is_plan=1) {

        fdump([$device_id, $village_id, $floor_id, $device_info,$is_plan],'api/log/'.date('Ymd').'/d4/sync_d4_face_all_per',true);
        if (empty($device_info)) {
            $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        if (!$floor_id) {
            $floor_id = $device_info['floor_id'];
        }
        if (!$village_id && $device_info['village_id']) {
            $village_id = $device_info['village_id'];
        }
        // 不论是单个还是全部转换成 数组
        $where = [
            'village_id' => $village_id,
            'status' => 1,
            'type' => ['neq', 4]
        ];
        if ($floor_id!=-1 && $floor_id!=0) {
            $floor_arr =  explode(',', $floor_id);
            $where['floor_id'] = ['in', $floor_arr];
        }
        $db_house_village_user_bind = M('House_village_user_bind');
        $user_bind = $db_house_village_user_bind->where($where)->field('pigcms_id, single_id, floor_id, layer_id,vacancy_id,type, village_id, uid, property_endtime, property_starttime, name, parent_id,ic_card, id_card, user_number,bind_number')->order('parent_id desc,pigcms_id asc')->select();

        if ($user_bind) {
            $db_face_door_service = D('Face_door_service');
            foreach ($user_bind as $val) {
                if ($is_plan) {
                    $param = array();
                    $param['type'] = 'sync_d4_face_single_per';
                    $param['device_id'] = $device_id;
                    $param['village_id'] = $village_id;
                    $param['pigcms_id'] = $val['pigcms_id'];
                    $param['bind_info'] = $val;
                    $param['device_info'] = $device_info;
                    $param['unique_id'] = 'sync_d4_face_single_per_'.$param['village_id'].'_'.$device_id.'_'.$val['pigcms_id'];
                    $db_face_door_service->add_plan($param, 'sub_house_village_face_device',-10);
                } else {
                    $this->add_create_face_record($device_id, $val['pigcms_id'], $village_id, $val, [], $device_info);
                }
            }
        }
        return true;
    }




}