<?php
class House_village_face_doorModel extends Model{

    public $client_id = 'ceshi_yndx_id';
    public $client_secret = 'ceshi_yndx_secret';
    public $face_host = 'http://nlife.uicp.net:21423'; // 正式站 https://openapi.vooct.com

    // 固定数据
    public $device2MqttUsername = 'kuaijing';
    public $device2MqttPassword = '123456';
    public $http_net = '';
    public $a3_face_err = [
        89999 => '其他错误',
        89998 => '人员已存在',
        89997 => '无人脸',
        89996 => '人员不存在',
        89995 => '库存人员已满',
        89994 => '照片格式不支持',
        89993 => '照片未检测出人脸',
        89992 => '照片有多个人脸',
        89991 => '照片人脸占比过小',
        89990 => '照片人脸模糊',
        89989 => '人脸角度不正',
    ];
    public $a3_face_class = '';
    public $time_out_time = 20; // 接口响应时间



    public function __construct(){
        if(C('config.house_a3_face_host')){
            $house_a3_face_host = C('config.house_a3_face_host');
            if (strpos($house_a3_face_host,'http://') !== false) {
                $this->base_face_a3_url = $house_a3_face_host;
            } else if (strpos($house_a3_face_host,'https://') !== false) {
                $this->base_face_a3_url = $house_a3_face_host;
            } else {
                $this->base_face_a3_url = 'https://' . $house_a3_face_host;
            }
        } else {
            if (empty($this->a3_face_class)) {
                import('@.ORG.face.a3_face');
                $this->a3_face_class = new a3_face();
            }
            $this->base_face_a3_url = $this->a3_face_class->base_face_a3_url;
        }
        if(C('config.house_a3_face_token')){
            $house_a3_face_token = C('config.house_a3_face_token');
            $this->a3_token = $house_a3_face_token;
        } else {
            if (empty($this->a3_face_class)) {
                import('@.ORG.face.a3_face');
                $this->a3_face_class = new a3_face();
            }
            $this->a3_token = $this->a3_face_class->a3_token;
        }
        if(C('config.house_a3_face_key')){
            $house_a3_face_key = C('config.house_a3_face_key');
            $this->a3_key = $house_a3_face_key;
        } else {
            if (empty($this->a3_face_class)) {
                import('@.ORG.face.a3_face');
                $this->a3_face_class = new a3_face();
            }
            $this->a3_key = $this->a3_face_class->a3_key;
        }
        parent::__construct();
    }

    /**
     * 生成计划任务
     * @param integer $village_id  社区id
     * @param string $type   同步社区相关信息 sync_house_village_info; 上传同步信息到人脸设备上 house_user_face_device_info; 同步小区门禁钥匙 sync_user
     * @param integer $uid
     * @param string $other_type 群应用操作id
     * @param integer $other_id 操作者id 或者额外的id 比如是floor_id
     */
    public function create_village_plan($village_id, $type = 'sync_house_village_info', $uid=0, $other_type = 'all', $other_id = 0) {
        $param = array();
        $param['type'] = $type;
        if ($uid>0) { // 需要操作的用户
            $param['uid'] = $uid;
        }
        if ($village_id) { // 需要对应操作的社区id
            $param['village_id'] = $village_id;
        }
        if ($other_type) { // 其他相关类型  比如 同步所有  同步单元
            $param['other_type'] = $other_type;
        }
        if ($other_id) { //  操作者id 或者额外的id 比如是floor_id
            $param['other_id'] = $other_id;
        }
        if ($param) {
            $this->add_plan($param);
        }
    }

    public function add_plan($param){
        $now_time = time();
        $arr= array();
        $arr['param'] = serialize($param);
        $arr['plan_time'] = 0;
        $arr['space_time'] = 0;
        $arr['add_time'] = $now_time;
        $arr['file'] = 'sub_house_village';
        $arr['time_type'] = 1;
        if (isset($param['unique_id']) && $param['unique_id']) {
            $unique_id= $param['unique_id'];
        } else {
            $unique_id = 'house_village';
            if (isset($param['village_id']) && $param['village_id']) {
                $unique_id .= '_'.$param['village_id'];
            }
            if (isset($param['uid']) && $param['uid']) {
                $unique_id .= '_'.$param['uid'];
            }
            if (isset($param['other_type']) && $param['other_type']) {
                $unique_id .= '_'.$param['other_type'];
            }
            if (isset($param['other_id']) && $param['other_id']) {
                $unique_id .= '_'.$param['other_id'];
            }
        }
        $arr['unique_id'] = $unique_id;
		$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
        $task_id = M('Process_sub_plan')->data($arr)->add();
        return array('task_id'=>$task_id);
    }


    public function village_runtask($param){
        import('@.ORG.plan');
        $plan_class = new plan();
        switch ($param['type']) {
            case 'sync_house_village_info': //生成群頭像
                $village_id = $param['village_id'];
                $other_type = $param['other_type'];
                if (!$other_type) {
                    $other_type = 'all';
                }
                $other_id = $param['other_id'];
                if (!$other_id) {
                    $other_id = 0;
                }
                $this->sync_house_village_info($village_id,$other_type,$other_id);
                return 0;
            case 'house_user_face_device_info': //生成群相册上传图片合并图片
                $uid = $param['uid'] ? $param['uid'] : 0;
                $pigcms_id = $param['other_id'] ? $param['other_id'] : 0;
                if ($uid) {
                    D('House_face_img')->house_user_face_device_info($uid, $pigcms_id);
                }
                return 0;
            case 'sync_user': //生成群相册上传图片合并图片
                $village_id = $param['village_id'];
                if ($village_id) {
                    $this->sync_user($village_id);
                }
                return 0;
            case 'sync_a3_face_device_door': //同步人脸设备
                $device_id = $param['device_id'];
                if ($device_id) {
                    D('Face_door_service')->sync_a3_face_device_door($device_id);
                }
                return 0;
            case 'mark_user': //企业微信给用户打标签
                $bind_id = $param['bind_id'];
                $property_id = $param['property_id'];
                $customer_id = $param['customer_id'];
                if ($bind_id) {
                    D('Property_group')->mark_user($bind_id, $customer_id, $property_id);
                }
                return 0;
            case 'code_mark_user': //企业微信给渠道活码用户打标签
                $code_id = $param['code_id'];
                $customer_id = $param['customer_id'];
                $property_id = $param['property_id'];
                if ($code_id && $customer_id) {
                    $param_word = [
                        'userid' => $param['userid'] ? $param['userid'] : '',
                        'external_userid' => $param['external_userid'] ? $param['external_userid'] : '',
                    ];
                    D('Property_group')->code_mark_user($code_id, $customer_id, $property_id, $param_word);
                }
                return 0;
        }

    }



    /**
     * 获取授权token
     * @return array
     */
    public function get_token(){
        $d_house_village_a2_face_door_code = M('House_village_a2_face_door_code');
        $face_door = $d_house_village_a2_face_door_code->find();
        if (!$face_door) {
            return array('error'=>1,'msg'=>'门禁未授权');
        }
        if ($face_door['expires_in']+$face_door['token_time'] < time()) {
            // 重新获取
            $params = array();
            $params['client_id'] = $this->client_id;
            $params['client_secret'] = $this->client_secret;
            $params['grant_type'] = 'refresh_token';
            $params['refresh_token'] = $face_door['refresh_token'];
            $params['redirect_uri'] = '';
            $url = $this->face_host . '/openapi/oauth/token';
            $result = $this->curl_post($url,$params);
            if ($result['error']) {
                return array('error'=>1,'msg'=>'刷新token获取失败');
            }elseif($result){
                $data['pigcms_id'] = $face_door['pigcms_id'];
                $data['access_token'] = $result['access_token'];
                $data['refresh_token'] = $result['refresh_token'];
                $data['expires_in'] = $result['expires_in'];
                $data['token_time'] = time();
                $d_house_village_a2_face_door_code->save($data);
                return array('error'=>0,'access_token'=>$result['access_token']);
            } else {
                return array('error'=>1,'msg'=>'同步参数失败');
            }
        }else{
            return array('error'=>0,'access_token'=>$face_door['access_token']);
        }
    }


    // 添加门禁设备

    /**
     * 【A2智能门禁机】添加门禁/编辑门禁
     * @param $device_id
     * @param array $door_info
     * @return mixed
     */
    public function face_door_add($device_id, $door_info=array()) {
        fdump_api(['A2添加门禁/编辑门禁'.__LINE__,$device_id,$door_info],'a2/face_door_add_log',1);
        if ($device_id && empty($door_info)) {
            $door_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
            if (empty($door_info)) {
                $info['error'] = 1;
                $info['msg'] = '参数错误，对应设备不存在！';
                fdump_api(['对应设备不存在'.__LINE__,$device_id,$door_info],'a2/face_door_add_err_log',1);
                return $info;
            }
        }
        $village_info = M('House_village')->where(array('village_id' => $door_info['village_id']))->find();
        if (empty($village_info)) {
            $info['error'] = 1;
            $info['msg'] = '参数错误，对应社区不存在！';
            fdump_api(['对应社区不存在'.__LINE__,$device_id,$door_info],'a2/face_door_add_err_log',1);
            return $info;
        } elseif ($village_info['a2_face_door_communityId']<=0) {
            // 小区对接id
            // 计划任务
//            $this->create_village_plan($door_info['village_id'],'sync_house_village_info');
            $sync_info = $this->sync_house_village_info($door_info['village_id']);
            fdump_api(['A2计划任务'.__LINE__,$device_id,$sync_info],'a2/face_door_add_log',1);
            if ($sync_info['code']==1 && $sync_info['communityId']>0) {
                $communityId = $sync_info['communityId'];
            } elseif (!$sync_info['code']) {
                $info['error'] = 1;
                $info['msg'] = $sync_info['msg'];
                return $info;
            }
        } else {
            $communityId = $village_info['a2_face_door_communityId'];
        }
        if ($door_info['floor_id']>0) {
            $floor_info = M('House_village_floor')->where(array('floor_id' => $door_info['floor_id']))->find();
            if (empty($floor_info)) {
                fdump_api(['对应单元不存在'.__LINE__,$device_id,$door_info],'a2/face_door_add_err_log',1);
                $info['error'] = 1;
                $info['msg'] = '参数错误，对应单元不存在！';
                return $info;
            } elseif ($floor_info['a2_face_door_buildId']<=0) {
                // 小区对接id
                $sync_floor_info = $this->sync_house_village_info($door_info['village_id'],'floor', $door_info['floor_id']);
                fdump_api(['小区对接id'.__LINE__,$device_id,$sync_floor_info],'a2/face_door_add_log',1);
                if ($sync_floor_info['code']==1 && $sync_floor_info['buildId']>0) {
                    $buildId = $sync_floor_info['buildId'];
                } elseif (!$sync_floor_info['code']) {
                    $info['error'] = 1;
                    $info['msg'] = $sync_floor_info['msg'];
                    return $info;
                }
            } else {
                $buildId = $floor_info['a2_face_door_buildId'];
            }
            $type = 2;
        } else {
            $type = 1;
        }
        if ($door_info['lat'] && $door_info['lng']) {
            $lat = $door_info['lat'];
            $lng = $door_info['lng'];
        } else {
            // 默认取北京天安门
            $lat = 39.915526;
            $lng = 116.403847;
        }


        $token_info = $this->get_token();

        if ($token_info['error']==0) {
            $params = array();
            $params['deviceName'] = $door_info['device_name'];
            $params['sn'] = $door_info['device_sn'];
            $params['communityId'] = $communityId;
            $params['communityName'] = $village_info['village_name'];
            if ($buildId) {
                $params['buildId'] = intval($buildId);
            } else {
                $params['buildId'] = 0;
            }
            $params['type'] = $type;
            $params['x'] = $lat;
            $params['y'] = $lng;
            $params['remark'] = '';
            $params['communityName'] = $village_info['village_name'];

            if ($door_info['a2_face_door_doorkeeperId']>0) {
                // 如果同步过信息， 修改信息
                $params['doorKeeperId'] = $door_info['a2_face_door_doorkeeperId'];
                $url = $this->face_host . '/openapi/v1/doorkeeper/modifyDKDevice';
            } else {
                $url = $this->face_host . '/openapi/v1/doorkeeper/addDKDevice';
            }
            //   broadbandId  宽带账号     broadbandPwd  宽带密码     networkMethod  联⽹⽅式     networkOperator ⽹络运营商
            $result = $this->curl_post($url,$params,$token_info['access_token']);
            fdump_api(['a2调取接口'.__LINE__,$url,$token_info,$params,$result],'a2/face_door_add_log',1);
            if($result['code'] == 1){
                $info['data'] = $result['data'];
                if ($door_info['a2_face_door_doorkeeperId']<=0) {
                    $data['device_id'] = $device_id;
                    $data['login_time'] = time();
                    $data['last_time'] = time();
                    $data['a2_face_door_doorkeeperId'] = $result['data'][0]['doorkeeperId'];
                    // $data['doorkeeperNPwd'] = $result['data']['doorkeeperNPwd'];
                    fdump_api(['a2结果记录'.__LINE__,$data],'a2/face_door_add_log',1);
                    M('House_face_device')->save($data);
                } else {
                    if ($result['data'] && $result['data'][0]['doorkeeperId']) {
                        $data['a2_face_door_doorkeeperId'] = $result['data'][0]['doorkeeperId'];
                    }
                    $data['device_id'] = $device_id;
                    $data['login_time'] = time();
                    $data['last_time'] = time();
                    fdump_api(['a2结果记录'.__LINE__,$data],'a2/face_door_add_log',1);
                    M('House_face_device')->save($data);
                }
                $info['error'] = 0;
                $info['msg'] = '同步成功';
            }else{
                $info['error'] = 1;
                if ($result['msg']) {
                    $info['msg'] = $result['msg'];
                }else{
                    $info['msg'] = '同步失败，请重试';
                }
            }
        }else{
            fdump_api(['a2token失败'.__LINE__,$token_info],'a2/face_door_add_log',1);
            $info['error'] = 1;
            $info['msg'] = '同步失败，请重试';
        }
        return $info;
    }


    /**
     * Notes:A2同步删除设备
     * @param $device_id
     * @param int $doorKeeperId
     * @param array $door_info
     * @return mixed
     * @author: wanzy
     * @date_time: 2020/10/13 15:47
     */
    public function face_door_del($device_id, $doorKeeperId=0, $door_info=[]) {
        if ($device_id && empty($door_info)) {
            $door_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
            if (empty($door_info)) {
                fdump_api(['A2同步删除设备'.__LINE__,$device_id,$doorKeeperId],'a2/face_door_del_err_log',1);
                $info['error'] = 1;
                $info['msg'] = '参数错误，对应设备不存在！';
                return $info;
            }
        }
        if (!$doorKeeperId) {
            $doorKeeperId = $door_info['a2_face_door_doorkeeperId'];
        }
        if (empty($doorKeeperId)) {
            fdump_api(['对应设备不存在'.__LINE__,$device_id,$doorKeeperId,$door_info],'a2/face_door_del_err_log',1);
            $info['error'] = 1;
            $info['msg'] = '参数错误，对应设备不存在！';
            return $info;
        }
        $token_info = $this->get_token();

        if ($token_info['error']==0) {
            $params = array();
            $params['doorKeeperId'] = $doorKeeperId;

            $url = $this->face_host . '/openapi/v1/doorkeeper/delDevice';

            $result = $this->curl_post($url,$params,$token_info['access_token']);
            fdump_api(['对应设备不存在'.__LINE__,$url,$params,$token_info,$result],'a2/face_door_del_log',1);
            if($result['code'] == 1){
                $info['data'] = $result['data'];
                $info['error'] = 0;
                $info['msg'] = '删除成功';
            }else{
                $info['error'] = 1;
                if ($result['msg']) {
                    $info['msg'] = $result['msg'];
                }else{
                    $info['msg'] = '同步删除失败，请重试';
                }
            }
            return $info;
        }else{
            fdump_api(['同步删除失败'.__LINE__,$token_info],'a2/face_door_del_err_log',1);
            $info['error'] = 1;
            $info['msg'] = '同步删除失败，请重试';
            return $info;
        }
    }

    /**
     * 同步社区相关信息
     * @param $village_id
     * @param string $type
     * @param int $type_id
     * @return array|mixed
     */
    public function sync_house_village_info($village_id,$type='all',$type_id=0){
        switch ($type) {
            case 'all':
                $sync_code = $this->sync_community($village_id);
                if (!$sync_code['code']) {
                    return $sync_code;
                }
                $sync_floor_code = $this->sync_floor($village_id);
                if (!$sync_floor_code['code']) {
                    return $sync_floor_code;
                }
                $sync_house_code = $this->sync_house($village_id);
                if (!$sync_house_code['code']) {
                    return $sync_house_code;
                }
                $result = array('code'=>1,'msg'=>'同步成功', 'communityId' => $sync_code['communityId'], 'sync_floor_code' => $sync_floor_code, 'sync_house_code' => $sync_house_code);
                break;
            case 'community':
                $sync_code = $this->sync_community($village_id);
                if (!$sync_code['code']) {
                    return $sync_code;
                }
                $result = array('code'=>1,'msg'=>'同步成功', 'communityId' => $sync_code['communityId']);
                break;
            case 'floor':
                // 小区信息
                $village_info = D('House_village')->field(true)->where(array('village_id'=>$village_id))->find();
                if (empty($village_info)) {
                    return array('code'=>0,'msg'=>'对应小区不存在');
                }
                if (!$village_info['a2_face_door_communityId']) {
                    // 先检查小区可进行同步了，没有，先同步小区
                    $sync_code = $this->sync_community($village_id);
                    if (!$sync_code['code']) {
                        return $sync_code;
                    }
                }
                if ($type_id<=0) {
                    return array('code'=>0,'msg'=>'参数传递不对');
                }
                $sync_floor_code = $this->sync_floor($village_id,$type_id);
                if (!$sync_floor_code['code']) {
                    return $sync_floor_code;
                }
                $result = array('code'=>1,'msg'=>'同步成功','buildId' => $sync_floor_code['buildId']);
                break;
            case 'house':
                // 小区信息
                $village_info = D('House_village')->field(true)->where(array('village_id'=>$village_id))->find();
                if (empty($village_info)) {
                    return array('code'=>0,'msg'=>'对应小区不存在');
                }
                if (!$village_info['a2_face_door_communityId']) {
                    // 先检查小区可进行同步了，没有，先同步小区
                    $sync_code = $this->sync_community($village_id);
                    if (!$sync_code['code']) {
                        return $sync_code;
                    }
                }
                if ($type_id<=0) {
                    return array('code'=>0,'msg'=>'参数传递不对');
                }
                // 小区信息
                $house_info = D('House_village_user_vacancy')->field(true)->where(array('pigcms_id'=>$type_id))->find();
                if (empty($house_info)) {
                    return array('code'=>0,'msg'=>'对应房屋不存在');
                }
                $floor_info = D('House_village_floor')->field(true)->where(array('floor_id'=>$house_info['floor_id']))->find();
                if (empty($floor_info)) {
                    return array('code'=>0,'msg'=>'对应单元不存在');
                }
                if ($floor_info && !$floor_info['a2_face_door_buildId']) {
                    // 先检查单元可进行同步了，没有，先同步单元
                    $sync_floor_code = $this->sync_floor($village_id);
                    if (!$sync_floor_code['code']) {
                        return $sync_floor_code;
                    }
                }
                $sync_house_code = $this->sync_house($village_id,$type_id);
                if (!$sync_house_code['code']) {
                    return $sync_house_code;
                }
                $result = array('code'=>1,'msg'=>'同步成功','houseId' => $sync_house_code['houseId']);
                break;
            default:
                $result = array('code'=>0,'msg'=>'参数传递不对');
                return $result;
                break;
        }

        return $result;
    }

    // 人脸门禁同步社区
    private function sync_community($village_id){
        if(!$village_id){
            return array('code'=>0,'msg'=>'小区id不存在');
        }
        $village_info = D('House_village')->field(true)->where(array('village_id'=>$village_id))->find();
        $village_info['long'] = floatval($village_info['long']);
        $village_info['lat'] = floatval($village_info['lat']);
        $property_phone = explode(' ', $village_info['property_phone']);
        $village_info['property_phone'] = $property_phone[0];

        $token_info = $this->get_token();
        if (!$token_info['error']) {
            $params = array();
            $params['gridCode'] = '110105'; // 固定写成 北京行政编号
            $params['communityName'] = $village_info['village_name'];
            $params['communityCoordinate'] = $village_info['long'].','.$village_info['lat'];
            $params['communityContact'] = $village_info['property_name'];
            $params['communityContactPhone'] = $village_info['property_phone'];
            if ($village_info['a2_face_door_communityId']>0) {
                // 如果已经同步就进行修改
                $params['communityId'] = $village_info['a2_face_door_communityId'];
                $url = $this->face_host . '/openapi/v1/doorkeeper/modifyCommunity';
                fdump('---------params-set---------','api/log/'.date('Ymd').'/sync_community_log',true);
                fdump($params,'api/log/'.date('Ymd').'/sync_community_log',true);
                $result = $this->curl_post($url,$params,$token_info['access_token']);
                fdump('---------result--set--------','api/log/'.date('Ymd').'/sync_community_log',true);
                fdump($result,'api/log/'.date('Ymd').'/sync_community_log',true);
                if ($result['code']==1) {
                    return array('code'=>1,'msg'=>'同步成功', 'communityId' => $village_info['a2_face_door_communityId']);
                }else{
                    return $result;
                }
            }else{
                $url = $this->face_host . '/openapi/v1/doorkeeper/addCommunity';
                fdump('---------params----------','api/log/'.date('Ymd').'/sync_community_log',true);
                fdump($params,'api/log/'.date('Ymd').'/sync_community_log',true);
                $result = $this->curl_post($url,$params,$token_info['access_token']);
                fdump('---------result----------','api/log/'.date('Ymd').'/sync_community_log',true);
                fdump($result,'api/log/'.date('Ymd').'/sync_community_log',true);
                if ($result['code']==1) {
                    $communityId = $result['data'][0]['communityId'];
                    $update = D('House_village')->where(array('village_id'=>$village_id))->save(array('a2_face_door_communityId'=>$communityId));
                    if ($update) {
                        return array('code'=>1,'msg'=>'同步成功', 'communityId' => $communityId);
                    }else{
                        return array('code'=>0,'msg'=>'同步失败,请重试');
                    }
                }else{
                    return $result;
                }
            }
        }else{
            fdump('---------token_info----------','api/log/'.date('Ymd').'/sync_community_log',true);
            fdump($token_info,'api/log/'.date('Ymd').'/sync_community_log',true);
            return array('code'=>0,'msg'=>$token_info['error']);
        }
    }

    // 人脸门禁同步社区
    private function sync_floor($village_id,$floor_id=0){
        set_time_limit(0);
        if(!$village_id){
            return array('code'=>0,'msg'=>'小区id不存在');
        }
        // 小区信息
        $village_info = D('House_village')->field(true)->where(array('village_id'=>$village_id))->find();
        if (!$village_info['a2_face_door_communityId']) {
            fdump('---------$village_info----------'.__LINE__,'api/log/'.date('Ymd').'/sync_floor_log',true);
            fdump($village_info,'api/log/'.date('Ymd').'/sync_floor_log',true);
            return array('code'=>0,'msg'=>'小区未同步，请先同步小区');
        }

        $token_info = $this->get_token();
        if (!$token_info['error']) {
            if ($floor_id>0) {
                // 如果传值了单元id 单独处理 楼栋（单元）信息
                $floor_info = D('House_village_floor')->field('floor_id,floor_name,floor_layer,a2_face_door_buildId')->where(array('village_id'=>$village_id, 'floor_id'=>$floor_id))->find();
                $params = array();
                $params['buildName'] = $floor_info['floor_name'].$floor_info['floor_layer'];
                $params['communityId'] = $village_info['a2_face_door_communityId'];
                $params['buildCode'] = $floor_info['floor_id'];
                if ($floor_info['a2_face_door_buildId']>0) {
                    // 如果已经同步过进行修改
                    $url = $this->face_host . '/openapi/v1/doorkeeper/modifyBuilding';
                    $params['buildId'] = $floor_info['a2_face_door_buildId'];
                } else {
                    $url = $this->face_host . '/openapi/v1/doorkeeper/addBuilding';
                }
                $result = $this->curl_post($url,$params,$token_info['access_token']);
                if ($result['code']==1) {
                    if ($floor_info['a2_face_door_buildId']<=0) {
                        $buildId = $result['data'][0]['buildId'];
                        $update = D('House_village_floor')->where(array('floor_id'=>$floor_info['floor_id']))->save(array('a2_face_door_buildId'=>$buildId));
                        if ($update===false) {
                            fdump('---------$update----------','api/log/'.date('Ymd').'/sync_floor_log',true);
                            fdump(D('House_village_floor')->getLastSql(),'api/log/'.date('Ymd').'/sync_floor_log',true);
                            return array('code'=>0,'msg'=>'同步失败,请重试');
                        }
                    } else {
                        $buildId = $floor_info['a2_face_door_buildId'];
                    }
                    return array('code'=>1,'msg'=>'同步成功', 'buildId' => $buildId);
                }else{
                    fdump('---------result----------'.__LINE__,'api/log/'.date('Ymd').'/sync_floor_log',true);
                    fdump($village_id.'----------'.$floor_id,'api/log/'.date('Ymd').'/sync_floor_log',true);
                    fdump($floor_info,'api/log/'.date('Ymd').'/sync_floor_log',true);
                    fdump($url,'api/log/'.date('Ymd').'/sync_floor_log',true);
                    fdump($params,'api/log/'.date('Ymd').'/sync_floor_log',true);
                    fdump($token_info,'api/log/'.date('Ymd').'/sync_floor_log',true);
                    fdump($result,'api/log/'.date('Ymd').'/sync_floor_log',true);
                    return array('code'=>0,'msg'=>'同步失败,请重试');
                }
            } else {
                // 楼栋（单元）信息
                $floor_list = D('House_village_floor')->field('floor_id,floor_name,floor_layer,a2_face_door_buildId')->where(array('village_id'=>$village_id))->select();

                foreach ($floor_list as $key => $value) {
                    $params = array();
                    $params['buildName'] = $value['floor_name'].$value['floor_layer'];
                    $params['communityId'] = $village_info['a2_face_door_communityId'];
                    $params['buildCode'] = $value['floor_id'];
                    if ($value['a2_face_door_buildId']>0) {
                        // 如果已经同步过进行修改
                        $url = $this->face_host . '/openapi/v1/doorkeeper/modifyBuilding';
                        $params['buildId'] = $value['a2_face_door_buildId'];
                    } else {
                        $url = $this->face_host . '/openapi/v1/doorkeeper/addBuilding';
                    }
                    $result = $this->curl_post($url,$params,$token_info['access_token']);
                    if ($result['code']==1) {
                        if ($value['a2_face_door_buildId']<=0) {
                            $buildId = $result['data'][0]['buildId'];
                            $update = D('House_village_floor')->where(array('floor_id'=>$value['floor_id']))->save(array('a2_face_door_buildId'=>$buildId));
                            if ($update===false) {
                                fdump('---------$update----------'.__LINE__,'api/log/'.date('Ymd').'/sync_floor_log',true);
                                fdump(D('House_village_floor'),'api/log/'.date('Ymd').'/sync_floor_log',true);
                                return array('code'=>0,'msg'=>'同步失败,请重试');
                            }
                        }
                    }else{
                        fdump('---------result----------'.__LINE__,'api/log/'.date('Ymd').'/sync_floor_log',true);
                        fdump($url,'api/log/'.date('Ymd').'/sync_floor_log',true);
                        fdump($params,'api/log/'.date('Ymd').'/sync_floor_log',true);
                        fdump($token_info,'api/log/'.date('Ymd').'/sync_floor_log',true);
                        fdump($result,'api/log/'.date('Ymd').'/sync_floor_log',true);
                        return array('code'=>0,'msg'=>'同步失败,请重试');
                    }
                }
            }
            return array('code'=>1,'msg'=>'同步成功');
        } else {
            return array('code'=>0,'msg'=>$token_info['error']);
        }
    }

    // 人脸门禁同步房间
    private function sync_house($village_id,$pigcms_id=0){
        set_time_limit(0);
        if(!$village_id){
            return array('code'=>0,'msg'=>'小区id不存在');
        }
        // 小区信息
        $village_info = D('House_village')->field(true)->where(array('village_id'=>$village_id))->find();
        if (!$village_info['a2_face_door_communityId']) {
            fdump('---------$village_info----------'.__LINE__,'api/log/'.date('Ymd').'/sync_house_log',true);
            fdump($village_info,'api/log/'.date('Ymd').'/sync_house_log',true);
            return array('code'=>0,'msg'=>'小区未同步，请先同步小区');
        }
        $token_info = $this->get_token();
        if (!$token_info['error']) {
            if ($pigcms_id>0) {
                // 上传了 房屋id  单独处理房屋信息
                $house_info = D('House_village_user_vacancy')->join(' as h LEFT JOIN '.C('DB_PREFIX').'house_village_floor as f on f.floor_id=h.floor_id')->field('f.a2_face_door_buildId,f.floor_name,f.floor_layer,h.layer,h.room,h.pigcms_id,h.a2_face_door_houseId')->where(array('h.village_id'=>$village_id,'h.pigcms_id'=>$pigcms_id))->order('h.pigcms_id desc')->find();

                $params = array();
                $params['houseName'] = $house_info['floor_name'].$house_info['floor_layer'].$house_info['layer'].$house_info['room'];
                $params['houseCode'] = $house_info['pigcms_id'];
                $params['communityId'] = $village_info['a2_face_door_communityId'];
                $params['buildId'] = $house_info['a2_face_door_buildId'];
                if ($house_info['a2_face_door_houseId']>0) {
                    // 如果已经同步过进行修改
                    $url = $this->face_host . '/openapi/v1/doorkeeper/modifyApartment';
                    $params['houseId'] = $house_info['a2_face_door_houseId'];
                } else {
                    $url = $this->face_host . '/openapi/v1/doorkeeper/addApartment';
                }
                $result = $this->curl_post($url,$params,$token_info['access_token']);
                if ($result['code']==1) {
                    if ($house_info['a2_face_door_houseId']<=0) {
                        $houseId = $result['data'][0]['houseId'];
                        $update = D('House_village_user_vacancy')->where(array('pigcms_id' => $house_info['pigcms_id']))->save(array('a2_face_door_houseId' => $houseId));
                        if ($update === false) {
                            fdump('---------$update----------'.__LINE__,'api/log/'.date('Ymd').'/sync_house_log',true);
                            fdump(D('House_village_user_vacancy')->getLastSql(),'api/log/'.date('Ymd').'/sync_house_log',true);
                            return array('code' => 0, 'msg' => '同步失败,请重试');
                        }
                    } else {
                        $houseId = $house_info['a2_face_door_houseId'];
                    }
                    return array('code' => 0, 'msg' => '同步成功', 'houseId' => $houseId);
                }else{
                    fdump('---------$result----------'.__LINE__,'api/log/'.date('Ymd').'/sync_house_log',true);
                    fdump($village_id.'---------'.$pigcms_id,'api/log/'.date('Ymd').'/sync_house_log',true);
                    fdump($url,'api/log/'.date('Ymd').'/sync_house_log',true);
                    fdump($params,'api/log/'.date('Ymd').'/sync_house_log',true);
                    fdump($token_info,'api/log/'.date('Ymd').'/sync_house_log',true);
                    fdump($result,'api/log/'.date('Ymd').'/sync_house_log',true);
                    return array('code'=>0,'msg'=>'同步失败,请重试');
                }
            } else {
                // 楼栋（单元）信息
                $limit = 0;
                $house_list = D('House_village_user_vacancy')->join(' as h LEFT JOIN '.C('DB_PREFIX').'house_village_floor as f on f.floor_id=h.floor_id')->field('f.a2_face_door_buildId,f.floor_name,f.floor_layer,h.layer,h.room,h.pigcms_id,h.a2_face_door_houseId')->where(array('h.village_id'=>$village_id,'f.a2_face_door_buildId'=>array('gt',0)))->limit($limit,1000)->order('h.pigcms_id desc')->select();
                while ($house_list) {
                    $limit += 1000;
                    foreach ($house_list as $key => $value) {
                        $params = array();
                        $params['houseName'] = $value['floor_name'].$value['floor_layer'].$value['layer'].$value['room'];
                        $params['houseCode'] = $value['pigcms_id'];
                        $params['communityId'] = $village_info['a2_face_door_communityId'];
                        $params['buildId'] = $value['a2_face_door_buildId'];
                        if ($value['a2_face_door_houseId']>0) {
                            // 如果已经同步过进行修改
                            $url = $this->face_host . '/openapi/v1/doorkeeper/modifyApartment';
                            $params['houseId'] = $value['a2_face_door_houseId'];
                        } else {
                            $url = $this->face_host . '/openapi/v1/doorkeeper/addApartment';
                        }
                        $result = $this->curl_post($url,$params,$token_info['access_token']);
                        if ($result['code']==1) {
                            if ($value['a2_face_door_houseId']<=0) {
                                $houseId = $result['data'][0]['houseId'];
                                $update = D('House_village_user_vacancy')->where(array('pigcms_id' => $value['pigcms_id']))->save(array('a2_face_door_houseId' => $houseId));
                                if ($update === false) {
                                    fdump('---------$update----------'.__LINE__,'api/log/'.date('Ymd').'/sync_house_log',true);
                                    fdump(D('House_village_user_vacancy')->getLastSql(),'api/log/'.date('Ymd').'/sync_house_log',true);
                                    return array('code' => 0, 'msg' => '同步失败,请重试');
                                }
                            }
                        }else{
                            fdump('---------$result----------'.__LINE__,'api/log/'.date('Ymd').'/sync_house_log',true);
                            fdump($village_id.'---------'.$pigcms_id,'api/log/'.date('Ymd').'/sync_house_log',true);
                            fdump($url,'api/log/'.date('Ymd').'/sync_house_log',true);
                            fdump($params,'api/log/'.date('Ymd').'/sync_house_log',true);
                            fdump($token_info,'api/log/'.date('Ymd').'/sync_house_log',true);
                            fdump($result,'api/log/'.date('Ymd').'/sync_house_log',true);
                            return array('code'=>0,'msg'=>'同步失败,请重试');
                        }
                    }
                    $house_list = D('House_village_user_vacancy')->join(' as h LEFT JOIN '.C('DB_PREFIX').'house_village_floor as f on f.floor_id=h.floor_id')->field('f.a2_face_door_buildId,f.floor_name,f.floor_layer,h.layer,h.room,h.pigcms_id,h.a2_face_door_houseId')->where(array('h.village_id'=>$village_id,'f.a2_face_door_buildId'=>array('gt',0)))->limit($limit,1000)->order('h.pigcms_id desc')->select();
                }
            }

            return array('code'=>1,'msg'=>'同步成功');
        } else {
            return array('code'=>0,'msg'=>$token_info['error']);
        }
    }

    // 人脸门禁同步业主
    public function sync_user($village_id){
        set_time_limit(0);
        if(!$village_id){
            return array('code'=>0,'msg'=>'小区id不存在');
        }
        // 小区信息
        $village_info = D('House_village')->field(true)->where(array('village_id'=>$village_id))->find();
        if (!$village_info['a2_face_door_communityId']) {
            return array('code'=>0,'msg'=>'小区未同步，请先同步小区');
        }

        $token_info = $this->get_token();
        if (!$token_info['error']) {
            // 楼栋（单元）信息
            $limit = 0;
            $site_url = $this->config['site_url'];
            $user_list = D('House_village_user_bind')->join(' as u LEFT JOIN '.C('DB_PREFIX').'house_village_user_vacancy as h on u.vacancy_id=h.pigcms_id')->field('u.id_card,u.ic_card,u.pigcms_id,u.uid,u.name,u.phone,u.open_door_day,u.property_endtime,u.type,u.parent_id,h.a2_face_door_houseId,u.a2_face_door_keyId')->where(array('h.village_id'=>$village_id,'u.uid'=>array('gt',0),'h.a2_face_door_houseId'=>array('gt',0)))->limit($limit,1000)->order('u.pigcms_id desc')->select();
            while ($user_list) {
                $limit += 1000;
                foreach ($user_list as $key => $value) {
                    // 如果是工作人员 先过滤 后期处理
                    if (4==$value['type']) {
                        continue;
                    }
                    $owe_property_open_door_day=$village_info['owe_property_open_door_day'];
                    if(isset($value['open_door_day']) && $value['open_door_day']>0){
                        $owe_property_open_door_day=$value['open_door_day'];
                    }
                    // 添加钥匙
                    $params = array();
                    $params['houseId'] = $value['a2_face_door_houseId'];
                    $params['userName'] = $value['name'];
                    $params['userPhone'] = $value['phone'];
                    $params['userIdCardNo'] = $value['id_card']?$value['id_card']:$value['phone'];
                    $params['roleType'] = $value['type']==3?1:$value['type']+1;

                    if ($value['a2_face_door_keyId']>0) {
                        $url = $this->face_host . '/openapi/v1/doorkeeper/modifyDKKey';
                        $params['id'] = $value['a2_face_door_keyId'];
                        $params['userFaceSource'] = 1;
                    } else {
                        $url = $this->face_host . '/openapi/v1/doorkeeper/addDKKey';
                    }

                    if (!in_array($value['type'],[0,3]) && $value['parent_id']) {
                        // 查询业主
                        $parent_user = D('House_village_user_bind')->where(array('pigcms_id'=>$value['parent_id']))->find();
                        if ($parent_user['property_endtime']>0) {
                            if ($village_info['owe_property_open_door'] && $owe_property_open_door_day>0) {
                                $value['userEndTime'] = $parent_user['property_endtime']+($owe_property_open_door_day * 86400);
                            } else {
                                $value['userEndTime'] = $parent_user['property_endtime'];
                            }
                        }
                    }
                    if ($value['userEndTime']) {
                        $params['userEndTime'] = date('Y-m-d H:i:s',$value['userEndTime']);
                    }else{
                        $params['userEndTime'] = date('Y-m-d H:i:s',strtotime("+1 year"));
                    }
                    // 获取下关联图片
                    $image_url = M('House_face_img')->where(array('uid'=>$value['uid'],'status'=>['in', [0,3]],'img_url'=>array('neq','')))->order('add_time ASC')->find();
                    if ($image_url && !$image_url['a2_face_door_img_name']) {
                        $img_url = $this->face_host . '/openapi/v1/doorkeeper/uploadImg';
                        if (3==$image_url['status']) {
                            import('@.ORG.face.word_encryption');
                            $word_encryption = new word_encryption();
                            // 加密图片解密
                            $image_url['img_url'] = $word_encryption->text_decrypt($image_url['img_url']);
                        }
                        $img_filename = replace_file_domain($image_url['img_url']);
                        $file_handle = new file_handle();
                        if($file_handle->check_open_oss()) {
                            $file_handle->download($img_filename);
                            $img_filename = $file_handle->get_path($img_filename);
                        }else{
                            $img_filename = $_SERVER['DOCUMENT_ROOT'].str_replace(C('config.site_url'),'',$img_filename);
                        }
                        $img_info = $this->img_curl_post($img_url,$img_filename,$token_info['access_token']);
                        if ($img_info['code']==1) {
                            $save = array(
                                'a2_face_door_img_name' => $img_info['msg'],
                                'a2_face_door_img' => $img_info['imgUrl'],
                                'last_time' => time(),
                            );
                            $a2_face_door_img_name = $img_info['msg'];
                            M('House_face_img')->where(array('id'=>$image_url['id']))->data($save)->save();
                        }
                    } elseif ($image_url) {
                        $a2_face_door_img_name = $image_url['a2_face_door_img_name'];
                    }
                    if ($a2_face_door_img_name) {
                        $params['faceImgNames'] = $a2_face_door_img_name;
                    }

                    $result = $this->curl_post($url,$params,$token_info['access_token']);
                    if ($result['code']==1) {
                        if ($value['a2_face_door_keyId']<=0) {
                            $keyId = $result['data'][0]['keyId'];
                            $update = D('House_village_user_bind')->where(array('pigcms_id'=>$value['pigcms_id']))->save(array('a2_face_door_keyId'=>$keyId));
                            if ($update===false) {
                                fdump('---------$update---------'.__LINE__,'api/log/'.date('Ymd').'/sync_user_log',true);
                                fdump(D('House_village_user_bind')->getLastSql(),'api/log/'.date('Ymd').'/sync_user_log',true);
                                return array('code'=>0,'msg'=>'同步失败,请重试');
                            }
                        }
                        fdump('---------$result---------'.__LINE__,'api/log/'.date('Ymd').'/sync_user_log',true);
                        fdump($result,'api/log/'.date('Ymd').'/sync_user_log',true);
                    }else{
                        fdump('---------$result---------'.__LINE__,'api/log/'.date('Ymd').'/sync_user_log',true);
                        fdump($url,'api/log/'.date('Ymd').'/sync_user_log',true);
                        fdump($params,'api/log/'.date('Ymd').'/sync_user_log',true);
                        fdump($token_info,'api/log/'.date('Ymd').'/sync_user_log',true);
                        fdump($result,'api/log/'.date('Ymd').'/sync_user_log',true);
                        return array('code'=>0,'msg'=>'同步失败,请重试');
                    }
                }
                $user_list = D('House_village_user_bind')->join(' as u LEFT JOIN '.C('DB_PREFIX').'house_village_user_vacancy as h on u.vacancy_id=h.pigcms_id')->field('u.pigcms_id,u.uid,u.name,u.phone,u.property_endtime,u.type,u.parent_id,h.a2_face_door_houseId,u.a2_face_door_keyId')->where(array('h.village_id'=>$village_id,'u.uid'=>array('gt',0),'h.a2_face_door_houseId'=>array('gt',0)))->limit($limit,1000)->order('u.pigcms_id desc')->select();
            }
            return array('code'=>1,'msg'=>'同步成功');
        } else {
            return array('code'=>0,'msg'=>$token_info['error']);
        }
    }

    /**
     * Notes:
     * @param $bind_arr_id
     * @return bool
     * @author: wanzy
     * @date_time: 2020/10/13 15:47
     */
    public function modify_arr($bind_arr_id) {
        if ($bind_arr_id) {
            foreach ($bind_arr_id as $item) {
                $this->modify_user($item);
            }
        }
        return true;
    }

    // 人脸门禁修改钥匙、单个业主修改添加
    public function modify_user($pigcms_id,$field='pigcms_id'){
        fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/modify_user_log',true);
        if(!$pigcms_id){
            fdump('---------pigcms_id---------'.__LINE__,'api/log/'.date('Ymd').'/modify_user_log',true);
            fdump($pigcms_id,'api/log/'.date('Ymd').'/modify_user_log',true);
            return array('code'=>0,'msg'=>'参数不对');
        }

        // 查询业主
        $bind_user = D('House_village_user_bind')->where(array($field=>$pigcms_id))->find();
        if(!$bind_user){
            fdump('---------bind_user---------'.__LINE__,'api/log/'.date('Ymd').'/modify_user_log',true);
            fdump($pigcms_id,'api/log/'.date('Ymd').'/modify_user_log',true);
            return array('code'=>0,'msg'=>'当前成员不存在');
        }
        if (1!=$bind_user['status']) {
            fdump('---------bind_user---------'.__LINE__,'api/log/'.date('Ymd').'/modify_user_log',true);
            fdump($bind_user,'api/log/'.date('Ymd').'/modify_user_log',true);
            return array('code'=>0,'msg'=>'当前成员不能同步');
        }

        // 小区信息
        $village_info = D('House_village')->field(true)->where(array('village_id'=>$bind_user['village_id']))->find();
        if (!$village_info['a2_face_door_communityId']) {
            fdump('---------village_info---------'.__LINE__,'api/log/'.date('Ymd').'/modify_user_log',true);
            fdump($village_info,'api/log/'.date('Ymd').'/modify_user_log',true);
            return array('code'=>0,'msg'=>'小区未同步，请先同步小区');
        }
        if(isset($bind_user['open_door_day']) && $bind_user['open_door_day']>0){
            $village_info['owe_property_open_door_day']=$bind_user['open_door_day'];
        }
        $token_info = $this->get_token();
        if (!$token_info['error']) {
            // 添修改钥匙
            $params = array();
            $params['userName'] = $bind_user['name'];
            $params['userPhone'] = $bind_user['phone'];
            $params['userIdCardNo'] = $bind_user['id_card']?$bind_user['id_card']:'110000199111111111';
            $params['roleType'] = $bind_user['type']==3?1:$bind_user['type']+1;

            if ($bind_user['a2_face_door_keyId']>0) {
                $url = $this->face_host . '/openapi/v1/doorkeeper/modifyDKKey';
                $params['id'] = $bind_user['a2_face_door_keyId'];
                $params['userFaceSource'] = 2;
            } else {
                $url = $this->face_host . '/openapi/v1/doorkeeper/addDKKey';
            }

            // 有效期结束时间
            if ($bind_user['type']!=0) {
                // 查询业主
                $parent_user = D('House_village_user_bind')->where(array('pigcms_id'=>$bind_user['parent_id']))->find();
                if ($parent_user['property_endtime']>0) {
                    if ($village_info['owe_property_open_door'] && $village_info['owe_property_open_door_day']>0) {
                        $bind_user['userEndTime'] = $parent_user['property_endtime']+($village_info['owe_property_open_door_day'] * 86400);
                    } else {
                        $bind_user['userEndTime'] = $parent_user['property_endtime'];
                    }
                }
            }
            if ($bind_user['userEndTime']) {
                $params['userEndTime'] = date('Y-m-d H:i:s',$bind_user['userEndTime']);
            }else{
                $params['userEndTime'] = date('Y-m-d H:i:s',strtotime("+1 year"));
            }

            // 获取下关联图片
            $image_url = M('House_face_img')->where(array('uid'=>$bind_user['uid'],'status'=>['in', [0,3]],'img_url'=>array('neq','')))->order('add_time ASC')->find();

            if ($image_url && !$image_url['a2_face_door_img_name']) {
                $img_url = $this->face_host . '/openapi/v1/doorkeeper/uploadImg';
                if (3==$image_url['status']) {
                    import('@.ORG.face.word_encryption');
                    $word_encryption = new word_encryption();
                    $image_url['img_url'] = $word_encryption->text_decrypt($image_url['img_url']);
                }
                $img_filename = replace_file_domain($image_url['img_url']);
                $file_handle = new file_handle();
                if($file_handle->check_open_oss()) {
                    $file_handle->download($img_filename);
                    $img_filename = $file_handle->get_path($img_filename);
                }else{
                    $img_filename = $_SERVER['DOCUMENT_ROOT'].str_replace(C('config.site_url'),'',$img_filename);
                }
                $img_info = $this->img_curl_post($img_url,$img_filename,$token_info['access_token']);
                if ($img_info['code']==1) {
                    $save = array(
                        'a2_face_door_img_name' => $img_info['msg'],
                        'a2_face_door_img' => $img_info['imgUrl'],
                        'last_time' => time(),
                    );
                    $a2_face_door_img_name = $img_info['msg'];
                    M('House_face_img')->where(array('id'=>$image_url['id']))->data($save)->save();
                } else {
                    fdump('---------img_info---------'.__LINE__,'api/log/'.date('Ymd').'/modify_user_log',true);
                    fdump($img_info,'api/log/'.date('Ymd').'/modify_user_log',true);
                }
            } elseif ($image_url) {
                $a2_face_door_img_name = $image_url['a2_face_door_img_name'];
            }
            if ($a2_face_door_img_name) {
                $params['faceImgNames'] = $a2_face_door_img_name;
            }

            // 房间信息
            $house_info = D('House_village_user_vacancy')->where(array('pigcms_id'=>$bind_user['vacancy_id']))->find();
            $params['houseId'] = $house_info['a2_face_door_houseId'];
            fdump('---------$url---------'.__LINE__,'api/log/'.date('Ymd').'/modify_user_log',true);
            fdump($url,'api/log/'.date('Ymd').'/modify_user_log',true);
            fdump($params,'api/log/'.date('Ymd').'/modify_user_log',true);
            fdump($token_info,'api/log/'.date('Ymd').'/modify_user_log',true);

            $result = $this->curl_post($url,$params,$token_info['access_token']);
            if ($result['code']==1) {
                if ($bind_user['a2_face_door_keyId']<=0) {
                    $keyId = $result['data'][0]['keyId'];
                    $update = D('House_village_user_bind')->where(array('pigcms_id'=>$bind_user['pigcms_id']))->save(array('a2_face_door_keyId'=>$keyId, 'face_img_status'=>1));
                    if ($update===false) {
                        fdump('---------update---------'.__LINE__,'api/log/'.date('Ymd').'/modify_user_log',true);
                        fdump(D('House_village_user_bind')->getLastSql(),'api/log/'.date('Ymd').'/modify_user_log',true);
                        return array('code'=>0,'msg'=>'同步失败,请重试');
                    }
                } else {
                    D('House_village_user_bind')->where(array('pigcms_id' => $bind_user['pigcms_id']))->save(array('face_img_status' => 1, 'face_img_reason' => ''));
                }
                return array('code'=>1,'msg'=>'同步成功');
            }else{
                fdump('---------bind_user---------'.__LINE__,'api/log/'.date('Ymd').'/modify_user_log',true);
                fdump($bind_user,'api/log/'.date('Ymd').'/modify_user_log',true);
                fdump('---------house_info---------'.__LINE__,'api/log/'.date('Ymd').'/modify_user_log',true);
                fdump($house_info,'api/log/'.date('Ymd').'/modify_user_log',true);
                D('House_village_user_bind')->where(array('pigcms_id' => $bind_user['pigcms_id']))->save(array('face_img_status' => 2, 'face_img_reason' => $result['msg']));
                fdump('---------result---------'.__LINE__,'api/log/'.date('Ymd').'/modify_user_log',true);
                fdump($result,'api/log/'.date('Ymd').'/modify_user_log',true);
                return array('code'=>0,'msg'=>'同步失败,请重试');
            }
        } else {
            fdump('---------token_info---------'.__LINE__,'api/log/'.date('Ymd').'/modify_user_log',true);
            fdump($token_info,'api/log/'.date('Ymd').'/modify_user_log',true);
            return array('code'=>0,'msg'=>'同步失败,请重试');
        }
    }

    // 人脸门禁删除钥匙
    public function delete_user($pigcms_id,$field='pigcms_id'){
        if(!$pigcms_id){
            return array('code'=>0,'msg'=>'参数不对');
        }

        // 查询业主
        $bind_user = D('House_village_user_bind')->where(array($field=>$pigcms_id))->find();
        if(!$bind_user['a2_face_door_keyId'] || $bind_user['a2_face_door_keyId']<=0){
            return array('code'=>0,'msg'=>'未同步不删除');
        }
        if(!$bind_user){
            return array('code'=>0,'msg'=>'业主不存在');
        }

        if (!$bind_user['a2_face_door_keyId']) {
            return array('code'=>0,'msg'=>'业主未同步，不能删除');
        }

        $token_info = $this->get_token();
        if (!$token_info['error']) {
            // 删除钥匙
            $url = $this->face_host . '/openapi/v1/doorkeeper/removeDKKey';
            $params = array();
            $params['id'] = $bind_user['a2_face_door_keyId'];

            $result = $this->curl_post($url,$params,$token_info['access_token']);
            if ($result['code']==1) {
                D('House_village_user_bind')->where(array('pigcms_id'=>$bind_user['pigcms_id']))->save(array('a2_face_door_keyId'=>''));
                return array('code'=>1,'msg'=>'删除成功');
            }else{
                return array('code'=>0,'msg'=>'删除失败,请重试');
            }
        }
    }

    // 人脸门禁远程开门
    public function sendOpendoor($pigcms_id,$field='pigcms_id', $device_sn){
        if(!$pigcms_id){
            return array('code'=>0,'msg'=>'参数不对');
        }
        // 查询业主
        $bind_user = D('House_village_user_bind')->where(array($field=>$pigcms_id))->find();
        if(!$bind_user['a2_face_door_keyId'] || $bind_user['a2_face_door_keyId']<=0){
            return array('code'=>0,'msg'=>'未同步不能开门');
        }
        if(!$bind_user){
            return array('code'=>0,'msg'=>'业主不存在');
        }

        if (!$bind_user['a2_face_door_keyId']) {
            return array('code'=>0,'msg'=>'业主未同步，不能开门');
        }
        $code_msg = array(
            '-1' => '设备不在线',
            '-2' => '权限未审核',
            '-3' => '审核未通过',
            '-4' => '密码错误',
            '-5' => '密码不在有效期',

        );
        $data = $this->getTempNumDKKey($pigcms_id,$field);
        if ($data['code']!=1) {
            if (!$data['msg']) {
                $data['msg'] = $code_msg[$data['code']];
            }
            return array('code'=>0,'msg'=>$data['msg']);
        }
        $passwd = $data['data']['passwd'];

        $token_info = $this->get_token();
        if (!$token_info['error']) {
            // 删除钥匙
            $url = $this->face_host . '/openapi/v1/doorkeeper/sendOpendoor';
            $village_info = D('House_village')->field('a2_face_door_communityId')->where(array('village_id'=>$bind_user['village_id']))->find();
            $params = array();
            $params['userPhone'] = $bind_user['phone'];
            $params['communityId'] = $village_info['a2_face_door_communityId'];
            $params['key'] = $passwd;
            $params['sn'] = $device_sn;

            $result = $this->curl_post($url,$params,$token_info['access_token']);
            if ($result['code']==1) {
                return array('code'=>1,'msg'=>'开门成功');
            }else{
                fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/sendOpendoor_log',true);
                fdump($pigcms_id,'api/log/'.date('Ymd').'/sendOpendoor_log',true);
                fdump($params,'api/log/'.date('Ymd').'/sendOpendoor_log',true);
                fdump($result,'api/log/'.date('Ymd').'/sendOpendoor_log',true);
                fdump('------------'.__LINE__,'api/log/'.date('Ymd').'/sendOpendoor_log',true);
                return array('code'=>0,'msg'=>'删除失败,请重试');
            }
        }
    }
    // 人脸门禁远程开门获取临时密码
    public function  getTempNumDKKey($pigcms_id,$field='pigcms_id') {
        if(!$pigcms_id){
            return array('code'=>0,'msg'=>'参数不对');
        }
        // 查询业主
        $bind_user = D('House_village_user_bind')->where(array($field=>$pigcms_id))->find();
        if(!$bind_user['a2_face_door_keyId'] || $bind_user['a2_face_door_keyId']<=0){
            return array('code'=>0,'msg'=>'未同步不能获取密码');
        }
        if(!$bind_user){
            return array('code'=>0,'msg'=>'业主不存在');
        }
        if ($bind_user['type']!=0 && $bind_user['type']!=3) {
            $auth_bind_user = D('House_village_user_bind')->where(array('pigcms_id'=>$bind_user['parent_id']))->find();
        } else {
            $auth_bind_user = $bind_user;
        }
        $house_info = D('House_village_user_vacancy')->where(array('pigcms_id'=>$bind_user['vacancy_id']))->field('a2_face_door_houseId')->find();
        $token_info = $this->get_token();
        if (!$token_info['error']) {
            // 获取密码
            $url = $this->face_host . '/openapi/v1/doorkeeper/getTempNumDKKey';
            $params = array();
            $params['houseId'] = $house_info['a2_face_door_houseId'];
            $params['userName'] = $bind_user['name'];
            $params['userPhone'] = $bind_user['phone'];
            $params['authUserPhone'] = $auth_bind_user['phone'];
            $params['remark'] = '用户临时开门使用';

            $result = $this->curl_post($url,$params,$token_info['access_token']);
            if ($result['code']==1) {
                return $result;
            }else{
                fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/getTempNumDKKey_log',true);
                fdump($pigcms_id,'api/log/'.date('Ymd').'/getTempNumDKKey_log',true);
                fdump($params,'api/log/'.date('Ymd').'/getTempNumDKKey_log',true);
                fdump($result,'api/log/'.date('Ymd').'/getTempNumDKKey_log',true);
                fdump('------------'.__LINE__,'api/log/'.date('Ymd').'/getTempNumDKKey_log',true);
                return $result;
            }
        }

    }

    /*查询开门记录*/
    /**
     * @param $params array
     * @param $communityId integer 社区ID
     * {
     *  $buildId integer 楼栋ID
     *  $houseCode integer 房屋编号
     *  $userPhone string ⼿机号
     *  $roleType integer 住户类型 1:业主 2:业主成员 3:租户
     *  $eventType integer 开门⽅式  1. ⼈脸开门  2. 钥匙开门  3.  密码开门 4. 远程开门  5. 扫码开门    6. 门卡开门 7. 临时密码开门
     *  $doorKeeperName string  设备名称
     *  $startTime
     *  $endTime
     * }
     * @param $start integer 分页参数
     * @param $length integer 分页参数
     * @return array
     */
    public function face_door_log($start=0,$length=20, $communityId=0, $params=array()) {
        $token_info = $this->get_token();
        if (!$token_info['error']) {
            // 获取对应钥匙开门记录
            $url = $this->face_host . '/openapi/v1/doorkeeper/dkRecord';
            $params['start'] = $start;
            $params['length'] = $length;
            $params['communityId'] = $communityId;

            $result = $this->curl_post($url,$params,$token_info['access_token']);
            if ($result['data']) {
                $data = $result['data'] = json_decode($result['data'],true);
                if ($data) {
                    foreach ($data as $item) {
                        $this->face_door_open_log($item);
                    }
                }

            }
            return $result;
        }

    }

    private function face_door_open_log($data) {
        $d_house_user_log = M('House_user_log');
        $msg = $d_house_user_log->where(array('eventId' => $data['eventId']))->find();
        if ($msg) {
            // 添加过直接返回
            return true;
        }
        $d_house_face_device = M('House_face_device');
        $d_house_village = M('House_village');
        $d_house_village_user_bind = D('House_village_user_bind');

        $log = array();
        $type = $data['roleType'] - 1;
        $house_face_device_info = $d_house_face_device->where(array('device_sn' => $data['sn']))->find();

        $house_village_bind = $d_house_village_user_bind->where(array('type' => $type, 'vacancy_id' => $data['houseCode'], 'name' => $data['userName'],'phone'=>$data['eventUser']))->find();

        $house_village_info = $d_house_village->where(array('village_id' => $house_village_bind['village_id']))->find();
        $village_name = $house_village_info['village_name'];

        $d_house_village_floor = M('House_village_floor');
        $house_village_floor_info = $d_house_village_floor->where(array('floor_id' => $house_village_bind['floor_id']))->find();
        if ( $house_village_bind['floor_id'] == -1) {
            $floor_name	=	C('config.house_name');
            $floor_layer	=	'大门';
        }else{
            $floor_layer = $house_village_floor_info['floor_layer'];
            $floor_name = $house_village_floor_info['floor_name'];
        }

        $uid = $house_village_bind['uid'];
        $log['uid'] = $uid;
        $log['log_bind_id'] = $house_village_bind['pigcms_id'];
        $log['log_name'] = $village_name . ' ' . $floor_layer . ' ' . $floor_name;
        $log['log_from'] = 1; // 人脸识别门禁
        $log['log_business_id'] = $house_village_bind['village_id']; // 社区id
        $log['log_more_id'] = $house_village_bind['floor_id']; // 单元id
        $log['device_id'] = $house_face_device_info['device_id']; // 设备id
        $log['eventId'] = $data['eventId']; // 设备id
        if ($data['eventTime']) {
            $time = strtotime($data['eventTime']);
        } else {
            $time = time();
        }

        $log['log_time'] = $time; // 记录时间
        $detail=$data;
        if ($data['imgUrl']) {
            $detail['face_img'] = $this->face_to_img($data['imgUrl'],$house_face_device_info['device_id']);
        }
        $log['log_detail'] = serialize($detail);
        if (isset($detail['face_img']) && trim($detail['face_img'])) {
            $log['cut_image'] = trim($detail['face_img']);
        }
        $log['add_time']=time();
        $d_house_user_log->data($log)->add();
    }

    private function face_to_img($base_img, $id) {
//        $img_mer_id = sprintf("%09d", $id);
//        $rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
        $rand_num = date('Ymd');// 换成日期存储
        $up_dir = './upload/face_log/'.$rand_num.'/';
        if (!is_dir($up_dir)) {
            mkdir($up_dir, 0777, true);
        }
        if ($base_img) {
            $type = 'png';
            $new_file = $up_dir.date('YmdHis_').uniqid().'.'.$type;
            # 远程文件处理
            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL,$base_img);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);  # 过期时间
            //当请求https的数据时，会要求证书，加上下面这两个参数，规避ssl的证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            $img = curl_exec($ch);
            curl_close($ch);
            $fp2 = @fopen($new_file ,'a');
            fwrite($fp2,$img);
            fclose($fp2);
            unset($img,$url);
            //判断是否需要上传至云存储
//            $file_handle = new file_handle();
//            $file_handle->upload($new_file);
            $img_path = trim($new_file,'.');
            return $img_path;
        } else {
            return false;
        }
    }


    /**
     * 接口模拟调用
     * @param $url
     * @param $params
     * @param string $access_token
     * @return mixed
     */
    public function curl_post($url,$params,$access_token=''){
        fdump('---------url---------','api/log/'.date('Ymd').'/curl_post_log',true);
        fdump($url,'api/log/'.date('Ymd').'/curl_post_log',true);
        fdump('---------params---------','api/log/'.date('Ymd').'/curl_post_log',true);
        fdump($params,'api/log/'.date('Ymd').'/curl_post_log',true);
        $ch = curl_init();
        $header =  array(
            "Accept: application/json",
        );

        if ($access_token) {
            $header[] =   "Authorization:bearer ".$access_token;
        }
        fdump('---------$header---------','api/log/'.date('Ymd').'/curl_post_log',true);
        fdump($header,'api/log/'.date('Ymd').'/curl_post_log',true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $result = curl_exec($ch);
        fdump('---------result----------','api/log/'.date('Ymd').'/curl_post_log',true);
        fdump($result,'api/log/'.date('Ymd').'/curl_post_log',true);
        $request_header = curl_getinfo( $ch, CURLINFO_HEADER_OUT);
        curl_close($ch);

        fdump('---------request_header----------','api/log/'.date('Ymd').'/curl_post_log',true);
        fdump($request_header,'api/log/'.date('Ymd').'/curl_post_log',true);

        $result = json_decode($result,true);
        return $result;
    }

    /**
     * 提交图片接口模拟调用
     * @param $url
     * @param string $encode_filename  文件路径
     * @param string $access_token
     * @return mixed
     */
    public function img_curl_post($url,$encode_filename,$access_token=''){
        if(class_exists('CURLFile')){
            $data['file'] = new CURLFile($encode_filename);
        }
        $header =  array(
            "Accept: application/json",
            "enctype：multipart/form-data",
        );
        if ($access_token) {
            $header[] =   "Authorization:bearer ".$access_token;
        }
        fdump('---------$data----------','api/log/'.date('Ymd').'/img_curl_post_log',true);
        fdump($data,'api/log/'.date('Ymd').'/img_curl_post_log',true);
        fdump('---------header----------','api/log/'.date('Ymd').'/img_curl_post_log',true);
        fdump($header,'api/log/'.date('Ymd').'/img_curl_post_log',true);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HEADER, false);	//是否返回 header头
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $return_data = curl_exec($ch);
        fdump('---------return_data----------','api/log/'.date('Ymd').'/img_curl_post_log',true);
        fdump($return_data,'api/log/'.date('Ymd').'/img_curl_post_log',true);
        curl_close($ch);
        $result = json_decode($return_data,true);
        fdump('---------result----------','api/log/'.date('Ymd').'/img_curl_post_log',true);
        fdump($result,'api/log/'.date('Ymd').'/img_curl_post_log',true);
        return $result;
    }

    /**获取钥匙列表*/
    public function ys_list() {
        // /openapi/v1/doorkeeper/ findDKKeyList
        $token_info = $this->get_token();
        if (!$token_info['error']) {
            // 获取对应钥匙开门记录
            $url = $this->face_host . '/openapi/v1/doorkeeper/findDKKeyList';
            $params['communityId'] = 616;
            $params['buildId'] = 54;
            $params['houseCode'] = '';
            $params['userPhone'] = '';
            $params['roleType'] = '';
            $params['userName'] = '';
            $params['userIdCardNo'] = '';

            $result = $this->curl_post($url,$params,$token_info['access_token']);

            dump($result) ;
        }else {

            dump($token_info) ;
        }
    }


    // 人脸门禁A3 对接
    public function face_a3_door_add($device_id, $door_info=array()) {
        if ($device_id && empty($door_info)) {
            $door_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
            if (empty($door_info)) {
                $info['error'] = 1;
                $info['msg'] = '参数错误，对应设备不存在！';
                fdump_api(['参数错误，对应设备不存在'.__LINE__,$device_id],'a3/face_a3_door_add_err_log',1);
                return $info;
            }
        }
        if ($door_info['a3_device_id'] && $door_info['a3_userId']) {
            // 如果同步过了 判断是否需要同步组
            //todo A3此处同步时间
            $this->a3synchronize_time($device_id,$door_info['a3_device_id']);
            $group_where = [
                'group_type' => 0,
                'group_type_id' => $door_info['village_id'],
                'device_id' => $door_info['device_id']
            ];
            $a3_group = M('Face_a3_group')->where($group_where)->find();
            if (empty($a3_group)) {
                $info = $this->add_a3_group($door_info['village_id'], $device_id);
                return $info;
            }
            $info['error'] = 0;
            $info['msg'] = '同步设备成功！';
            $deviceId = $door_info['a3_device_id'];
            $this->face_a3_subscription($device_id, $deviceId);
            D('Face_door_service')->face_device_check($device_id);
            return $info;
        }

        $village_info = M('House_village')->where(array('village_id' => $door_info['village_id']))->find();
        if (empty($village_info)) {
            $info['error'] = 1;
            $info['msg'] = '参数错误，对应社区不存在！';
            fdump_api(['参数错误，对应社区不存在'.__LINE__,$device_id],'a3/face_a3_door_add_err_log',1);
            return $info;
        } elseif ($door_info['a3_device_id']>0) {
            // 如果已经添加过，同步编辑  暂无编辑
            fdump_api(['已经同步过，暂无编辑功能'.__LINE__,$device_id,$door_info],'a3/face_a3_door_add_err_log',1);
            $info['error'] = 1;
            $data = [];
            $info['msg'] = '已经同步过！';
            $data['login_time'] = time();
            M('House_face_device')->where(array('device_id' => $device_id))->data($data)->save();
            return $info;
        } elseif ($door_info['device_sn']) {
            $token = $this->a3_token;
            $key = $this->a3_key;
            $sn = $door_info['device_sn'];
            $url = $this->base_face_a3_url  . "/dms/device?token=".$token;
            $pwd = base64_decode($key);
            $device2MqttUsername = $this->device2MqttUsername . '_'.$device_id;
            $device2MqttPassword = $this->device2MqttPassword;
            $device = [];
            $device['sn'] = $sn;
            $device['znlinkUsername'] = $device2MqttUsername;
            $device['znlinkPassword'] = $device2MqttPassword;

            $sign = json_encode(['device' => $device, 'signCreateTime' => date('YmdHis')]);
            $sign_pwd = openssl_encrypt ($sign, 'DES-ECB', $pwd);
            $postData = [
                'device' => $device,
                'sign' => $sign_pwd
            ];
            if (!$this->http_net) {
                import('ORG.Net.Http');
                $this->http_net = new Http();
            }
            $return =  $this->http_net->curlPostOwnWithHeader($url, json_encode($postData), ['Content-Type: application/json'],$this->time_out_time);
            if ($return) {
                $return_msg = json_decode($return,true);
                if ($return_msg['code']==0) {
                    // 记录成功在人脸门禁添加设备后返回的信息
                    $data_msg = $return_msg['data'];
                    $data = array(
                        'a3_device_id' =>  $data_msg['id'],
                        'a3_userId' =>  $data_msg['userId'],
                    );
                    if ($data_msg['deviceType']) {
                        $data['a3_deviceType'] = $data_msg['deviceType'];
                    }
                    $data['a3_info'] = serialize($data_msg);
                    $data['login_time'] = time();
                    M('House_face_device')->where(array('device_id' => $device_id))->data($data)->save();
                    $info['error'] = 0;
                    $info['msg'] = '同步添加设备成功！';
                    // 同步成功后同步添加组
                    $group_where = [
                        'group_type' => 0,
                        'group_type_id' => $door_info['village_id'],
                        'device_id' => $door_info['device_id']
                    ];
                    $a3_group = M('Face_a3_group')->where($group_where)->find();
                    if (empty($a3_group)) {
                        if ($door_info['village_id'] && $door_info['village_id']>0) {
                            $this->add_a3_group($door_info['village_id'], $device_id);
                        }
                    }
                    //todo A3此处同步时间
                    $this->a3synchronize_time($device_id,$door_info['a3_device_id']);
                    return $info;
                } else {
                    fdump_api(['同步失败'.__LINE__,$url,$sign,$sign_pwd,$postData,$return,$device_id,$door_info],'a3/face_a3_door_add_err_log',1);
                    $info['error'] = 1;
                    $info['msg'] = $return_msg['msg'];
                    if (!$info['msg']) {
                        $info['msg'] = '同步失败！';
                    }
                    return $info;
                }
            } else {
                fdump_api(['同步失败'.__LINE__,$url,$sign,$postData,$return,$device_id,$door_info],'a3/face_a3_door_add_err_log',1);
                $info['error'] = 1;
                $info['msg'] = '同步失败！';
                return $info;
            }
        } else {
            fdump_api(['同步失败'.__LINE__,$device_id,$door_info],'a3/face_a3_door_add_err_log',1);
            $info['error'] = 1;
            $info['msg'] = '同步失败！';
            return $info;
        }

    }

    // 添加A3组信息
    public function add_a3_group($village_id,$device_id, $door_info=[]) {
        if ($device_id && empty($door_info)) {
            $door_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
            if (empty($door_info)) {
                $info['error'] = 1;
                $info['msg'] = '参数错误，对应设备不存在！';
                fdump_api(['参数错误，对应设备不存在'.__LINE__,$village_id,$device_id],'a3/face_add_a3_group_err_log',1);
                return $info;
            }
        }
        $village_info = M('House_village')->where(['village_id' => $village_id])->field('village_id,village_name')->find();
        $deviceId = $door_info['a3_device_id'];
        $name = $village_info['village_name'].'_'.$device_id;
        $group_info = $this->get_a3_group($device_id, $village_id, $name, $deviceId);
        if ($group_info['error']==0) {
            // 如果查询反馈成功并添加了数据
            $info['error'] = 0;
            $info['msg'] = '同步组信成功！';
            return $info;
        }

        $token = $this->a3_token;
        $key = $this->a3_key;
        $url = $this->base_face_a3_url  . "/fa/group/device/{$deviceId}?token=".$token;
        $pwd = base64_decode($key);
        $threshold = $door_info['device_score'];
        $group = array(
            'name' => $name,
            'personNumber' => -1,
            'threshold' => $threshold,
            'channelStr' => '-1',
            'channels' => [-1],
            'maxPersonNumber' => -1,
            'editable' => false,
            'maxSaveTime' => -1,
            'deviceId' => intval($deviceId),
        );
        $sign = json_encode(['group' => $group, 'deviceId' => intval($deviceId), 'signCreateTime' => date('YmdHis')], JSON_UNESCAPED_UNICODE);
        $sign_pwd = openssl_encrypt ($sign, 'DES-ECB', $pwd);
        $postData = array(
            'group' => $group,
            'sign' => $sign_pwd
        );
        if (!$this->http_net) {
            import('ORG.Net.Http');
            $this->http_net = new Http();
        }
        $return =  $this->http_net->curlPostOwnWithHeader($url, json_encode($postData), ['Content-Type: application/json'],$this->time_out_time);

        if ($return) {
            $return_msg = json_decode($return,true);
            if ($return_msg['code']==0) {
                // 记录成功在人脸门禁添加设备后返回的信息
                $data_msg = $return_msg['data'];
                if ($data_msg['groupID']) {
                    $groupID = $data_msg['groupID'];
                } elseif ($data_msg['groupIdOnDevice']) {
                    $groupID = $data_msg['groupIdOnDevice'];
                } else {
                    $groupID = '';
                }
                if ($data_msg['personNumber']) {
                    $personNumber = $data_msg['personNumber'];
                } elseif ($data_msg['personNum']) {
                    $personNumber = $data_msg['personNum'];
                } else {
                    $personNumber = '';
                }
                if ($data_msg['maxPersonNumber']) {
                    $maxPersonNumber = $data_msg['maxPersonNumber'];
                } elseif ($data_msg['maxPersonNum']) {
                    $maxPersonNumber = $data_msg['maxPersonNum'];
                } else {
                    $maxPersonNumber = '';
                }
                $data = array(
                    'group_type' =>  0,
                    'group_type_id' =>  $village_id,
                    'group_id' =>  $data_msg['id'],
                    'groupID' =>  $groupID,
                    'name' =>  $data_msg['name'] ? $data_msg['name'] : '',
                    'personNumber' =>  $personNumber,
                    'threshold' =>  $data_msg['threshold'] ? $data_msg['threshold'] : 0,
                    'channelStr' =>  $data_msg['channelStr'] ? $data_msg['channelStr'] : '',
                    'maxPersonNumber' =>  $maxPersonNumber,
                    'editable' =>  $data_msg['editable'] ? $data_msg['editable'] : '',
                    'maxSaveTime' =>  $data_msg['maxSaveTime'] ? $data_msg['maxSaveTime'] : '',
                    'deviceId' =>  $data_msg['deviceId'] ? $data_msg['deviceId'] : '',
                    'channels' =>  $data_msg['channels'] ? serialize($data_msg['channels']) : '',
                    'device_id' =>  $device_id,
                );
                $data['a3_info'] = serialize($data_msg);
                $id = M('Face_a3_group')->where(array('device_id' => $device_id,'group_type_id'=> $village_id, 'group_id' => $data['group_id']))->getField('id');
                if ($id) {
                    $data['last_time'] = time();
                    $add_id = M('Face_a3_group')->where(array('group_id' => $data['group_id']))->data($data)->save();
                } else {
                    $data['add_time'] = time();
                    $add_id = M('Face_a3_group')->data($data)->add();
                }
                if (!$add_id) {
                    fdump_api(['添加组信息错误'.__LINE__,$url,$sign,$postData,$return,$data,M('Face_a3_group')->getDbError()],'a3/face_add_a3_group_err_log',1);
                }

                $info['error'] = 0;
                $info['msg'] = '同步组信成功！';

                $this->face_a3_subscription($device_id, $deviceId);
                D('Face_door_service')->face_device_check($device_id);
                return $info;
            } else {
                fdump_api(['添加组信息错误'.__LINE__,$url,$sign,$postData,$return,$deviceId,$door_info],'a3/face_add_a3_group_err_log',1);
                $info['error'] = 1;
                $info['msg'] = $return_msg['msg'];
                if (!$info['msg']) {
                    $info['msg'] = '添加组信息错误！';
                }
                return $info;
            }
        } else {
            fdump_api(['添加组信息错误'.__LINE__,$url,$sign,$postData,$return,$deviceId,$door_info],'a3/face_add_a3_group_err_log',1);
            $info['error'] = 1;
            $info['msg'] = '添加组信息错误！';
            return $info;
        }
    }

    // 查询A3组信息
    public function get_a3_group($device_id, $village_id, $name, $a3_device_id=0) {
        if (!$a3_device_id) {
            $a3_device_id = M('House_face_device')->where(array('device_id' => $device_id))->getField('a3_device_id');
        }
        if (!$a3_device_id) {
            fdump_api(['没有设备同步参数'.__LINE__,$device_id],'a3/get_a3_group_err_log',1);
            $info['error'] = 1;
            $info['msg'] = '没有设备同步参数';
            return $info;
        }
        $deviceId = $a3_device_id;
        $token = $this->a3_token;
        $key = $this->a3_key;
        $url = $this->base_face_a3_url  . "/fa/group/device/{$deviceId}?token=".$token;
        $pwd = base64_decode($key);

        $sign_param = [];
        $sign_param['deviceId'] = intval($deviceId);
        $sign_param['signCreateTime'] = date('YmdHis');

        $sign = json_encode($sign_param, JSON_UNESCAPED_UNICODE);
        $sign_pwd = openssl_encrypt ($sign, 'DES-ECB', $pwd);
        $headers = [
            'sign' => urlencode($sign_pwd)
        ];
        if (!$this->http_net) {
            import('ORG.Net.Http');
            $this->http_net = new Http();
        }
        $url .='&sign='.urlencode($sign_pwd);
        $return =  $this->http_net->curlGet($url,$this->time_out_time, $headers);

        if ($return) {
            $return_msg = json_decode($return,true);
            if ($return_msg['code']==0 || ($return_msg['data'] && $return_msg['data'][0])) {
                // 记录成功在人脸门禁添加设备后返回的信息
                $datas = $return_msg['data'];
                foreach ($datas as $vals) {
                    if ($vals['name'] && $vals['name']==$name) {
                        // 取名称相同的查询出来的最后一个设备组信息
                        $data_msg = $vals;
                    }
                }
                if (!$data_msg) {
                    fdump_api(['查询组信息错误'.__LINE__,$device_id,$village_id,$name,$a3_device_id,$url,$sign,$headers,$return,$deviceId],'a3/get_a3_group_err_log',1);
                    $info['error'] = 1;
                    $info['msg'] = $return['msg'];
                    if (!$info['msg']) {
                        $info['msg'] = '查询组信息错误！';
                    }
                    return $info;
                }
                if (!$data_msg['groupId']) {
                    fdump_api(['查询组信息组id不返回错误'.__LINE__,$device_id,$village_id,$name,$a3_device_id,$url,$sign,$headers,$return,$deviceId],'a3/get_a3_group_err_log',1);
                    $info['error'] = 1;
                    $info['msg'] = $return['msg'];
                    if (!$info['msg']) {
                        $info['msg'] = '查询组信息错误！';
                    }
                    return $info;
                }
                if ($data_msg['groupID']) {
                    $groupID = $data_msg['groupID'];
                } elseif ($data_msg['groupIdOnDevice']) {
                    $groupID = $data_msg['groupIdOnDevice'];
                } else {
                    $groupID = '';
                }
                if ($data_msg['personNumber']) {
                    $personNumber = $data_msg['personNumber'];
                } elseif ($data_msg['personNum']) {
                    $personNumber = $data_msg['personNum'];
                } else {
                    $personNumber = '-1';
                }
                if ($data_msg['maxPersonNumber']) {
                    $maxPersonNumber = $data_msg['maxPersonNumber'];
                } elseif ($data_msg['maxPersonNum']) {
                    $maxPersonNumber = $data_msg['maxPersonNum'];
                } else {
                    $maxPersonNumber = '-1';
                }
                $data = array(
                    'group_type' =>  0,
                    'group_type_id' =>  $village_id,
                    'group_id' =>  $data_msg['groupId'],
                    'groupID' =>  $groupID,
                    'name' =>  $data_msg['name'] ? $data_msg['name'] : '',
                    'personNumber' =>  $personNumber,
                    'threshold' =>  $data_msg['threshold'] ? $data_msg['threshold'] : 0,
                    'channelStr' =>  ($data_msg['channels'] && $data_msg['channels'][0]) ? $data_msg['channels'][0] : '',
                    'maxPersonNumber' =>  $maxPersonNumber,
                    'editable' =>  $data_msg['editable'] ? $data_msg['editable'] : '',
                    'maxSaveTime' =>  $data_msg['maxSaveTime'] ? $data_msg['maxSaveTime'] : '',
                    'deviceId' =>  $data_msg['deviceId'] ? $data_msg['deviceId'] : $a3_device_id,
                    'channels' =>  $data_msg['channels'] ? serialize($data_msg['channels']) : '',
                    'device_id' =>  $device_id,
                );
                $data['a3_info'] = serialize($data_msg);
                $id = M('Face_a3_group')->where(array('device_id' => $device_id,'group_type_id'=> $village_id, 'group_id' => $data['group_id']))->getField('id');
                if ($id) {
                    $data['last_time'] = time();
                    $add_id = M('Face_a3_group')->where(array('group_id' => $data['group_id']))->data($data)->save();
                } else {
                    $data['add_time'] = time();
                    $add_id = M('Face_a3_group')->data($data)->add();
                }
                if (!$add_id) {
                    fdump_api(['查询组信息添加错误'.__LINE__,$device_id,$village_id,$name,$a3_device_id,$url,$return,M('Face_a3_group')->getDbError()],'a3/get_a3_group_err_log',1);
                }

                $info['error'] = 0;
                $info['msg'] = '同步组信成功！';

                $this->face_a3_subscription($device_id, $deviceId);
                D('Face_door_service')->face_device_check($device_id);
                return $info;
            } else {
                fdump_api(['查询组信息添加错误'.__LINE__,$device_id,$village_id,$name,$a3_device_id,$url,$return,$deviceId],'a3/get_a3_group_err_log',1);
                $info['error'] = 1;
                $info['msg'] = $return_msg['msg'];
                if (!$info['msg']) {
                    $info['msg'] = '查询组信息错误！';
                }
                return $info;
            }
        } else {
            fdump_api(['查询组信息错误'.__LINE__,$device_id,$village_id,$name,$a3_device_id,$url,$return,$deviceId],'a3/get_a3_group_err_log',1);
            $info['error'] = 1;
            $info['msg'] = $return['msg'];
            if (!$info['msg']) {
                $info['msg'] = '查询组信息错误！';
            }
            return $info;
        }
    }

    public $plan_sub_house_face = '';
    public function keepPlanThread() {
        if (!$this->plan_sub_house_face) {
            import('@.ORG.plan.plan_sub_house_face');
            $this->plan_sub_house_face = new plan_sub_house_face();
        }
        $this->plan_sub_house_face->keepThread();
    }

    // 循环处理步人员
    public function a3_arr_person_msg($device_id,$bind_arr_id,$orderGroupId='',$plan_time=-9) {
        if ($bind_arr_id) {
            $now_time = time();
            if (intval($plan_time) > intval($now_time+100)) {
                $plan_time = $now_time;
            }
            foreach ($bind_arr_id as $item) {
                $param = array();
                $param['type'] = 'sync_a3_face';
                $param['device_id'] = $device_id;
                $param['pigcms_id'] = $item;
                if ($orderGroupId) {
                    $param['orderGroupId'] = $orderGroupId;
                }
                $arr= array();
                $arr['param'] = serialize($param);
                $arr['plan_time'] = $plan_time;
                $arr['space_time'] = 0;
                $arr['add_time'] = $now_time;
                $arr['file'] = 'sub_house_face';
                $arr['time_type'] = 1;
                $arr['unique_id'] = 'a3_face_device_'.$device_id.'_'.$item;
				$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                $task_id = M('Process_sub_plan')->data($arr)->add();
                if (!$task_id) {
                    fdump_api([$task_id, $arr, M('Process_sub_plan')->getDbError()], 'a3/a3_arr_person_msg', 1);
                }
//                $this->add_a3_person_info($device_id, $item, 'pigcms_id');
            }
        }
        return true;
    }

    // 对应同步人员,单个同步
    public function add_a3_person_info($device_id,$pigcms_id,$field='pigcms_id', $is_replace = false,$log_id=0) {
        if(!$pigcms_id){
            fdump_api(['参数不对'.__LINE__,$device_id],'a3/add_a3_person_info_err_log',1);
            $info['error'] = 1;
            $info['msg'] = '参数不对';
            return $info;
        }
        $door_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        if (empty($door_info)) {
            fdump_api(['参数错误，对应设备不存在'.__LINE__,$device_id,$pigcms_id,$field],'a3/add_a3_person_info_err_log',1);
            $info['error'] = 1;
            $info['msg'] = '参数错误，对应设备不存在！';
        }

        // 查询业主
        $bind_user = D('House_village_user_bind')
            ->where(array($field=>$pigcms_id))
            ->field('pigcms_id,name,type,parent_id,status,village_id,a3_person_id,id_card,ic_card,address,uid,property_endtime')
            ->find();
        if(!$bind_user){
            fdump_api(['当前成员不存在'.__LINE__,$device_id,$pigcms_id,$field],'a3/add_a3_person_info_err_log',1);
            $info['error'] = 1;
            $info['msg'] = '当前成员不存在';
            return $info;
        }
        if (1!=$bind_user['status'] || !$bind_user['name']) {
            fdump_api(['当前成员不能同步'.__LINE__,$device_id,$pigcms_id,$field,$bind_user],'a3/add_a3_person_info_err_log',1);
            $info['error'] = 1;
            $info['msg'] = '当前成员不能同步';
            return $info;
        }
        $group_where = [
            'group_type' => 0,
            'group_type_id' => $bind_user['village_id'],
            'device_id' => $door_info['device_id']
        ];
        $a3_group = M('Face_a3_group')->where($group_where)->find();
        if (empty($a3_group)) {
            fdump_api(['当前成员不能同步'.__LINE__,$group_where,M('Face_a3_group')->_sql()],'a3/add_a3_person_info_err_log',1);
            $info['error'] = 1;
            $info['msg'] = '组信息不存在，请先同步组';
            return $info;
        }

        $deviceId = $door_info['a3_device_id'];
        $token = $this->a3_token;
        $key = $this->a3_key;
        $url = $this->base_face_a3_url  . "/fa/person/device/{$deviceId}?token=".$token;
        $pwd = base64_decode($key);
        $group_id = $a3_group['group_id'];
        if (!$group_id) {
            fdump_api(['当前成员不能同步'.__LINE__,$device_id,$pigcms_id,$url,$a3_group,$bind_user],'a3/add_a3_person_info_err_log',1);
            $info['error'] = 1;
            $info['msg'] = '对应组不存在，请优先同步组！';
            return $info;
        }
        // 处理下物业到期时间
      /*  if (in_array($bind_user['type'],[1,2])) {
            // 家属 租客 取父级 物业时间
            if ($bind_user['parent_id']) {
                $parent_property_endtime = D('House_village_user_bind')
                    ->where(array('pigcms_id'=>$bind_user['parent_id']))
                    ->getField('property_endtime');
                if ($parent_property_endtime) {
                    $property_endtime = $parent_property_endtime;
                }
            }
        } elseif (in_array($bind_user['type'],[0,3])) {
            // 业主物业取自身时间
            $property_endtime = $bind_user['property_endtime'];
        }*/
        $userTimes =  D('House_village_user_bind')->getUserEndTime($bind_user['pigcms_id']);
        $property_endtime = intval($userTimes['propertyEndTime']);

        if ($property_endtime) {
            /*$property_endtime = date('Y-m-d 23:59:59', $property_endtime);
            $property_endtime = strtotime($property_endtime);
            $houseVillage = D('House_village')->where(['village_id' => $bind_user['village_id']])->field('owe_property_open_door,owe_property_open_door_day')->find();
            if ($houseVillage['owe_property_open_door'] == 1)//1允许延长开门时间  0不允许
            {
                if($houseVillage['owe_property_open_door_day'] > 0){
                    $end_time = $property_endtime + ($houseVillage['owe_property_open_door_day'] * 24 * 3600);
                }else{
                    $end_time = 2173017600;
                }
            } else {
                $end_time = $property_endtime;
            }
            $end_time = intval($end_time);*/
            $end_time=  D('House_village_user_bind')->getUserEndTime($bind_user['pigcms_id'],false)['propertyEndTime'];
            $advance_payment_days = M('House_village_about_config')->where(['village_id'=>$bind_user['village_id']])->getField('advance_payment_days');

            if ($advance_payment_days!=='' && $advance_payment_days!==NULL) {
                if (!$advance_payment_days) {
                    $property_tip_day = 15;
                } elseif ($advance_payment_days<=0) {
                    $property_tip_day = 0;
                } else {
                    $property_tip_day = $advance_payment_days;
                }
                $remark = [
                    'property_end_time' => intval($property_endtime),
                    'property_pay_tip' =>  "您的物业费还有%d天到期，请及时缴费",
                    'property_tip_day' =>  intval($property_tip_day),
                    'property_end_tip' => "您已欠物业费，请及时缴费",
                    'property_end_device_time' => intval($end_time),
                    'pigcms_id' => $pigcms_id,
                ];
                $remark = json_encode($remark, JSON_UNESCAPED_UNICODE);
            } else {
                $remark = [
                    'pigcms_id' => $pigcms_id,
                ];
            }
        } else {
            $remark = [
                'pigcms_id' => $pigcms_id,
            ];
        }

        $sex = 0;
        $age = 18;
        $threshold = 0;
        $person = [];
        $face_user_bind_device = M('Face_user_bind_device');
        $where_a3_face = [
            'bind_type' => 0,
            'bind_id' => $bind_user['pigcms_id'],
            'device_id' => $door_info['device_id'],
            'device_type' => 3
        ];
        $a3_info = $face_user_bind_device->where($where_a3_face)->field('id,person_id,person_json')->find();
        if ($bind_user['ic_card']) {
            $idNum = $bind_user['ic_card'];
        } elseif ($bind_user['id_card']) {
            $idNum = $bind_user['id_card'];
        } else {
            $idNum = $pigcms_id;
        }
        if ($a3_info['person_id']) {
            // 如果同步过，直接传参编辑
            $person['id'] = intval($a3_info['person_id']);
        } else {
            $param = [
                'groupId' => $group_id,
                'name' => trim($bind_user['name']) ? trim($bind_user['name']) : '住户_'.$bind_user['pigcms_id'],
                'sex' => $sex,
                'age' => $age,
                'idNum' => $idNum,
            ];
            if ($bind_user['address']) {
                $param['address'] =  $bind_user['address'];
            }
            $person_msg = $this->get_a3_person_list($device_id, $param,$deviceId,$pigcms_id,$bind_user);
            if ($person_msg['error']==0 && $person_msg['person_id']) {
                // 查询到数据  赋值由添加改为编辑
                $person['id'] = intval($person_msg['person_id']);
                $a3_info = $person_msg['a3_info'];
            }
        }

        $person['groupId'] = intval($group_id);
        $person['name'] = trim($bind_user['name']) ? trim($bind_user['name']) : '住户_'.$bind_user['pigcms_id'];
        $person['sex'] = intval($sex);
        $person['age'] = intval($age);
        $person['threshold'] = intval($threshold);
        $person['idNum'] = $idNum;
        $person['address'] = $bind_user['address'];
        $person['remarks'] = $remark;
        $person['deviceId'] = intval($deviceId);

        $person_json = $person;
        unset($person_json['id']);
        $person_json['device_id'] = $device_id;
        $person_json['pigcms_id'] = $pigcms_id;
        $person_json_md5 = md5(json_encode($person_json, JSON_UNESCAPED_UNICODE));
        if ($a3_info && $a3_info['person_json'] && $a3_info['person_json']==$person_json_md5) {

			fdump_api(['当前数据未改动不进行同步'.__LINE__,$a3_info, $person_json], 'a3/a3_info_already', 1);

            $info['error'] = 1;
            $info['msg'] = '当前数据未改动不进行同步';
            $this->add_a3_person_face($device_id, $bind_user['pigcms_id'], $person['id'],$door_info['a3_device_id'], $bind_user, $is_replace,$log_id);
            return $info;
        }
		fdump_api(['数据比对'.__LINE__,$bind_user, $face_user_bind_device->_sql(), $a3_info, serialize($person_json), $person_json_md5,$person], 'a3/a3_info', 1);

        if ($pigcms_id && $person['id']) {
            $msg = $this->del_a3_person($device_id, $pigcms_id, $person['id'], $deviceId,$bind_user, $a3_info['id']);
            if ($msg) {
                unset($person['id']);
                $a3_info = [];
            } else {
                // 删除失败 也做记录清空 避免设备实际删除了导致的问题
                $data['person_json'] = '';
                $data['last_time'] = time();
                $face_user_bind_device->where($where_a3_face)->data($data)->save();
            }
        }
        $sign = json_encode(['person' => $person, 'deviceId' => intval($deviceId), 'signCreateTime' => date('YmdHis')], JSON_UNESCAPED_UNICODE);
        $sign_pwd = openssl_encrypt ($sign, 'DES-ECB', $pwd);
        $postData = [
            'person' => $person,
            'sign' => $sign_pwd
        ];
        if (!$this->http_net) {
            import('ORG.Net.Http');
            $this->http_net = new Http();
        }
        if ($person['id']) {
            $return =  $this->http_net->curlPutOwnWithHeader($url, json_encode($postData), ['Content-Type: application/json'],$this->time_out_time);
        } else {
            $return =  $this->http_net->curlPostOwnWithHeader($url, json_encode($postData), ['Content-Type: application/json'],$this->time_out_time);
        }
        if ($return) {
            fdump_api(['记录用户同步成功信息'.__LINE__,$url,$postData,$url,$return],'a3/add_a3_person_info_log',1);
        }

        if ($return) {
            $return_msg = json_decode($return,true);
            if ($return_msg['code']==0) {
                // 记录成功在人脸门禁添加设备后返回的信息
                $face_user_bind_device = M('Face_user_bind_device');

                $data_msg = $return_msg['data'];
                $data = [
                    'bind_type' => 0,
                    'bind_id' => $bind_user['pigcms_id'],
                    'device_id' => $door_info['device_id'],
                    'device_type' => 3
                ];
                if (!$a3_info) {
                    $where_a3_face = $data;
                    $a3_info = $face_user_bind_device->where($where_a3_face)->find();
                }

                $data['person_id'] = $data_msg['id'];
                if ($data_msg['personID']) {
                    $personID = $data_msg['personID'];
                } elseif ($data_msg['personIdOnDevice']) {
                    $personID = $data_msg['personIdOnDevice'];
                } else {
                    $personID = '';
                }
                $data['personID'] = $personID;
                $data['group_id'] = $data_msg['groupId'];
                if ($data_msg['groupID']) {
                    $data['groupID'] = $data_msg['groupID'];
                } elseif ($data_msg['groupIdOnDevice']) {
                    $data['groupID'] = $data_msg['groupIdOnDevice'];
                }
                $data['info'] = serialize($data_msg);
                $data['person_json'] = $person_json_md5;
                $data['person_text'] = serialize($person_json);
                if ($a3_info) {
                    $data['last_time'] = time();
                    $set = $face_user_bind_device->where($where_a3_face)->data($data)->save();
                } else {
                    $data['add_time'] = time();
                    $set = $face_user_bind_device->data($data)->add();
                }
                if (!$set) {
                    fdump_api(['记录同步成功信息有误'.__LINE__,$a3_info,$where_a3_face,$data,$url,$postData,$return,$face_user_bind_device->getDbError()],'a3/add_a3_person_info_err_log',1);
                } else {
                    fdump_api(['记录同步成功信息'.__LINE__,$data],'a3/add_a3_person_info_log',1);
                }

                $info['error'] = 0;
                $info['msg'] = '添加成功！';
                $this->add_a3_person_face($device_id, $bind_user['pigcms_id'], $data_msg['id'],$door_info['a3_device_id'], $bind_user, $is_replace,$log_id);
                return $info;
            } else {
                fdump_api(['添加信息错误'.__LINE__,$url,$sign,$postData,$return,$deviceId,$door_info],'a3/add_a3_person_info_err_log',1);
                $info['error'] = 1;
                $info['msg'] = $return_msg['msg'];
                if (!$info['msg']) {
                    $info['msg'] = '添加信息错误！';
                }
                $data['person_json'] = '';
                $data['last_time'] = time();
                $face_user_bind_device->where($where_a3_face)->data($data)->save();
                return $info;
            }
        } else {

			// 返回 false 则 60秒后 靠后重新处理
			$this->a3_arr_person_msg($device_id, [$bind_user['pigcms_id']], '', time() + 60);

            fdump_api(['添加信息错误'.__LINE__,$url,$sign,$return,$deviceId,$device_id],'a3/add_a3_person_info_err_log',1);
            $info['error'] = 1;
            $info['msg'] = '添加信息错误！';
            $data['person_json'] = '';
            $data['last_time'] = time();
            $face_user_bind_device->where($where_a3_face)->data($data)->save();
            return $info;
        }
    }

    // 添加人脸
    public function add_a3_person_face($device_id, $pigcms_id,$person_id = 0,$a3_device_id=0,$bind_user=[], $is_replace = false,$log_id=0) {
        if (empty($bind_user)) {
            $bind_user = D('House_village_user_bind')->where(array('pigcms_id'=>$pigcms_id))->field('uid,a3_person_id,pigcms_id')->find();
        }
        $d_house_face_img = D('House_face_img');
        $uid = $bind_user['uid'];
        if ($uid<=0) {
            // 没有图片不进行人脸同步
            D('House_village_user_bind')->where(array('pigcms_id' => $bind_user['pigcms_id']))->save(array('face_img_status' => 2, 'face_img_reason' => '缺少图片'));
            if($log_id){
                M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                    'status'=>2,
                    'msg'=>L_('缺少图片'),
                    'last_time'=>time()
                ]);
            } else {
                $syncLogParams = [
                    'pigcms_id' => $pigcms_id, 'face_id' => 0, 'device_id' => $device_id, 'device_sn' => '',
                    'log_time' => time(), 'status' => 2, 'msg' => '缺少图片'
                ];
                $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
            }
            return false;
        }
        $img_info = $d_house_face_img->where(array('uid' => $uid, 'status' => ['in', [0,3]], 'img_url' => array('neq', '')))->limit(3)->field('id, img_url,status')->select();
        if (!$img_info) {
            // 没有图片不进行人脸同步
            D('House_village_user_bind')->where(array('pigcms_id' => $bind_user['pigcms_id']))->save(array('face_img_status' => 2, 'face_img_reason' => '缺少图片'));
            if($log_id){
                M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                    'status'=>2,
                    'msg'=>L_('缺少图片'),
                    'last_time'=>time()
                ]);
            } else {
                $syncLogParams = [
                    'pigcms_id' => $pigcms_id, 'face_id' => 0, 'device_id' => $device_id, 'device_sn' => '',
                    'log_time' => time(), 'status' => 2, 'msg' => '缺少图片'
                ];
                $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
            }
            return false;
        }
        $where_a3_face = [
            'bind_type' => 0,
            'bind_id' => $pigcms_id,
            'device_id' => $device_id,
            'device_type' => 3
        ];
        $a3_info = M('Face_user_bind_device')->where($where_a3_face)->field('id,person_id,face_id,face_json')->order('face_id DESC')->find();
        if ($a3_info && $a3_info['person_id']) {
            $bind_user['a3_person_id'] = $a3_info['person_id'];
        }
        $face_json = $img_info;
        $face_json['device_id'] = $device_id;
        $face_json_md5 = md5(json_encode($face_json, JSON_UNESCAPED_UNICODE));
        if ($a3_info && $a3_info['face_json'] && $a3_info['face_json']==$face_json_md5) {
            $info['error'] = 1;
            $info['msg'] = '当前数据未改动不进行同步';
            $face_result = $this->getPersonFace($a3_info['face_id'],$a3_device_id,[$device_id, $pigcms_id,$person_id]);
            if (!$face_result['code'] || (isset($face_result['code']) && $face_result['code']==0 && $face_result['person'] && $face_result['person'][0]['images'])) {
                D('House_village_user_bind')->where(array('pigcms_id' => $bind_user['pigcms_id']))->save(array('face_img_status' => 1, 'face_img_reason' => ''));
                if($log_id){
                    M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                        'status'=>1,
                        'msg'=>'',
                        'last_time'=>time()
                    ]);
                } else {
                    $face_id = isset($a3_info['face_id']) && $a3_info['face_id'] ? $a3_info['face_id'] : 0;
                    $syncLogParams = [
                        'pigcms_id' => $pigcms_id, 'face_id' => $face_id, 'device_id' => $device_id, 'device_sn' => '',
                        'log_time' => time(), 'status' => 1, 'msg' => ''
                    ];
                    $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
                }
                return $info;
            }
        }
        if (!$person_id && $bind_user['a3_person_id']) {
            $person_id = $bind_user['a3_person_id'];
        }
        if (!$a3_device_id) {
            $a3_device_id = M('House_face_device')->where(array('device_id' => $device_id))->getField('a3_device_id');
        }
        if (empty($person_id)) {
            fdump_api(['>>>>>>>>先同步人员信息>>>>>>>>>>'.__LINE__,$device_id,$pigcms_id,$person_id,$a3_device_id,$bind_user],'a3/add_a3_no_person_err_log',true);
            $info['error'] = 1;
            $info['msg'] = '先同步人员信息！';
            if (!$is_replace) {
                $this->add_a3_person_info($device_id, $pigcms_id, true);
            }
            return $info;
        }
        $token = $this->a3_token;
        $key = $this->a3_key;
        $url = $this->base_face_a3_url . "/fa/face/device/{$a3_device_id}?token=".$token;
        $pwd = base64_decode($key);

        if ($img_info) {
            set_time_limit(0);
            ini_set('memory_limit', '1024M');
            import('@.ORG.face.word_encryption');
            $word_encryption = new word_encryption();
            // 如果存在同步过图片，删除然后重置记录
            if ($a3_info['face_id']) {
                $this->del_a3_person_face($device_id,$pigcms_id,$a3_info['face_id'],$a3_device_id);
            }
            foreach ($img_info as &$val) {
                if (3==$val['status']) {
                    // 针对加密图片解密
                    $val['img_url'] = $word_encryption->text_decrypt($val['img_url']);
                }
                try {
                    $imgUrl = D('Image')->transform_image($val['img_url']);
                    $img_url = replace_file_domain($imgUrl);
                    $img_url = thumb_img($img_url, 640, 480, '', true);
                    $file = $this->imgToBase64($img_url,false,[$device_id, $pigcms_id,$person_id ,$a3_device_id]);
                } catch( \Exception $e) {
                    $file = '';
                }
                $sign = json_encode(['file' => strval($file), 'personId' => intval($person_id), 'deviceId' => intval($a3_device_id), 'signCreateTime' => date('YmdHis')]);
                $sign_pwd = openssl_encrypt ($sign, 'DES-ECB', $pwd);
                $postData = [
                    'file' => strval($file),
                    'personId' => intval($person_id),
                    'sign' => $sign_pwd
                ];
                if (!$this->http_net) {
                    import('ORG.Net.Http');
                    $this->http_net = new Http();
                }
                $return =  $this->http_net->curlPostOwnWithHeader($url, json_encode($postData), ['Content-Type: application/json'],$this->time_out_time);
                fdump_api(['添加人脸'.__LINE__, $url, $device_id, $pigcms_id,$person_id,$return],'a3/add_a3_person_face_log',1);


                if ($return) {
                    $return_msg = json_decode($return,true);
                    fdump_api(['人脸同步成功信息'.__LINE__, $return],'a3/add_a3_person_face_log',1);
                    if ($return_msg['code']==0) {
                        // 记录成功在人脸门禁添加设备后返回的信息
                        $data_msg = $return_msg['data'];
                        if ($data_msg['imageID']) {
                            $a3_fileId =  $data_msg['imageID'];
                        } elseif ($data_msg['imageId']) {
                            $a3_fileId =  $data_msg['imageId'];
                        } else {
                            $a3_fileId = '';
                        }
                        $data = array(
                            'a3_face_id' =>  $data_msg['id'],
                            'a3_fileId' =>  $a3_fileId,
                        );
                        $data['a3_info'] = serialize($data_msg);
                        $data['a3_time'] = time();
                        $d_house_face_img->where(array('id'=>$val['id']))->data($data)->save();
                        D('House_village_user_bind')->where(array('pigcms_id' => $bind_user['pigcms_id']))->save(array('face_img_status' => 1, 'face_img_reason' => ''));
                        if($log_id){
                            M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                                'status'=>1,
                                'msg'=>'',
                                'last_time'=>time()
                            ]);
                        } else {
                            $face_id = isset($val['id']) && $val['id'] ? $val['id'] : 0;
                            $syncLogParams = [
                                'pigcms_id' => $pigcms_id, 'face_id' => $face_id, 'device_id' => $device_id, 'device_sn' => '',
                                'log_time' => time(), 'status' => 1, 'msg' => ''
                            ];
                            $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
                        }

                        $a3_face = [
                            'face_id' => $data_msg['id'],
                            'face_image_id' => $a3_fileId,
                            'face_txt' => serialize($data_msg),
                            'face_img' => $img_url,
                            'face_time' => time(),
                            'face_json' => $face_json_md5,
                            'face_text' => serialize($face_json),
                        ];
                        $id = M('Face_user_bind_device')->where(['id' => $a3_info['id']])->data($a3_face)->save();
                        if (!$id) {
                            fdump_api(['记录错误'.__LINE__,$a3_face,$a3_info,$data_msg],'a3/add_a3_person_face_err_log',1);
                        }
                        break;
                    } else {

						if($return_msg['code'] == 40017){
							M('Face_user_bind_device')->where(['id' => $a3_info['id']])->delete();
							// 返回 false 则 60秒后 靠后重新处理
							$this->a3_arr_person_msg($device_id, [$bind_user['pigcms_id']], '', time() + 60);
							continue;
						}
                        fdump_api(['添加错误'.__LINE__,$url, $img_url, $person_id, $a3_device_id, $pigcms_id, $return],'a3/add_a3_person_face_err_log',1);
                        $info['error'] = 1;
                        $info['msg'] = $return_msg['msg'];
                        if (!$info['msg']) {
                            $info['msg'] = '添加错误！';
                        }
                        $face_img_reason = $this->a3_face_err[$return_msg['code']] ? $this->a3_face_err[$return_msg['code']] : $return_msg['msg'];
                        D('House_village_user_bind')->where(array('pigcms_id' => $bind_user['pigcms_id']))->save(array('face_img_status' => 2, 'face_img_reason' => $face_img_reason));
                        if($log_id){
                            M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                                'status'=>2,
                                'msg'=>$face_img_reason,
                                'last_time'=>time()
                            ]);
                        } else {
                            $face_id = isset($val['id']) && $val['id'] ? $val['id'] : 0;
                            $syncLogParams = [
                                'pigcms_id' => $pigcms_id, 'face_id' => $face_id, 'device_id' => $device_id, 'device_sn' => '',
                                'log_time' => time(), 'status' => 2, 'msg' => $face_img_reason
                            ];
                            $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
                        }
                        continue;
                    }
                } else {

					// 返回 false 则 60秒后 靠后重新处理
					$this->a3_arr_person_msg($device_id, [$bind_user['pigcms_id']], '', time() + 60);

                    fdump_api(['添加错误-人脸设备未响应'.__LINE__,$url, $img_url, $person_id, $a3_device_id, $pigcms_id, $return],'a3/add_a3_person_face_err_log',1);
                    $info['error'] = 1;
                    $info['msg'] = '添加错误！';
                    $face_img_reason = '人脸设备未响应';
                    D('House_village_user_bind')->where(array('pigcms_id' => $bind_user['pigcms_id']))->save(array('face_img_status' => 2, 'face_img_reason' => $face_img_reason));
                    if($log_id){
                        M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                            'status'=>2,
                            'msg'=>$face_img_reason,
                            'last_time'=>time()
                        ]);
                    } else {
                        $face_id = isset($val['id']) && $val['id'] ? $val['id'] : 0;
                        $syncLogParams = [
                            'pigcms_id' => $pigcms_id, 'face_id' => $face_id, 'device_id' => $device_id, 'device_sn' => '',
                            'log_time' => time(), 'status' => 2, 'msg' => $face_img_reason
                        ];
                        $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
                    }
                    continue;
                }
            }
        }
        return true;
    }

    public function getPersonFace($id,$deviceId,$params =[]) {
        $token = $this->a3_token;
        $key = $this->a3_key;
        $url = $this->base_face_a3_url  . "/fa/face/{$id}/device/{$deviceId}?token=".$token;
        $pwd = base64_decode($key);
        $sign = json_encode(['id' => intval($id),'deviceId' => intval($deviceId), 'signCreateTime' => date('YmdHis')]);
        $sign_pwd = openssl_encrypt ($sign, 'DES-ECB', $pwd);
        $headers = [
            'sign' => urlencode($sign_pwd)
        ];
        if (!$this->http_net) {
            import('ORG.Net.Http');
            $this->http_net = new Http();
        }
        $url .='&sign='.urlencode($sign_pwd);
        $return =  $this->http_net->curlGet($url, 60, $headers);
        $return_msg = json_decode($return,true);
        fdump_api(['msg' => '查询人员人脸','id' => $id, 'deviceId' => $deviceId, 'params' => $params, 'token' => $token, 'key' => $key, 'url' => $url, 'return' => $return ],'a3/getPersonFaceLog',1);
        return true;
    }

    // 查询人员

    /**
     * Notes:获取人员列表（宇泛设备暂不支持）
     * 请求接口：
     * /fa/person/device/{deviceId}/getPersonList
     * @param $device_id
     * @param array $param 用户查询人员的条件信息 person 结构是接口传参时候需要的 param 传参去除这一层
     * {
     *   person	json	N	用户查询人员的条件信息
     *   person.groupId	long	N	组ID
     *   person.personId	long	N	人员ID
     *   person.name	String	N	姓名
     *   person.sex	int	N	性别
     *   person.ageUp	int	N	年龄上限
     *   person.ageLow	int	N	年龄下限
     *   person.idType	String	N	证件类型
     *   person.ID	String	N	人员唯一标识，身份证号码，工号，或其他编号
     *   person.address	String	N	住址
     *   person.num	int	N	数量
     *   sign	String	Y	数据签名。 例如：{ "personId":15, "deviceId":"246", "signCreateTime": "20191029135600" }
     * }
     * @param int $a3_device_id
     * @param int $pigcms_id
     * @param array $bind_user
     * @return bool
     * @author: wanzy
     * @date_time: 2020/11/28 13:09
     */
    public function get_a3_person_list($device_id, $param,$a3_device_id=0,$pigcms_id=0,$bind_user=[]) {
        if (empty($param)) {
            fdump_api(['查询人员传参错误'.__LINE__,$device_id, $a3_device_id, $pigcms_id, $bind_user],'a3/get_a3_person_list_err_log',1);
            return true;
        }
        if (!$a3_device_id) {
            $a3_device_id = M('House_face_device')->where(array('device_id' => $device_id))->getField('a3_device_id');
        }
        if (!$a3_device_id) {
            fdump_api(['没有设备同步参数'.__LINE__,$device_id, $pigcms_id, $bind_user],'a3/get_a3_person_list_err_log',1);
            $info['error'] = 1;
            $info['msg'] = '没有设备同步参数';
            return $info;
        }
        if (!$param['groupId']) {
            $village_id = M('House_face_device')->where(array('device_id' => $device_id))->getField('village_id');
            $group_where = [
                'group_type' => 0,
                'group_type_id' => $village_id,
                'device_id' => $device_id
            ];
            $a3_group = M('Face_a3_group')->where($group_where)->find();
            if (empty($a3_group)) {
                fdump_api(['组信息不存在，请先同步组'.__LINE__,$group_where, M('Face_a3_group')->_sql(), M('Face_a3_group')->getDbError()],'a3/get_a3_person_list_err_log',1);
                $info['error'] = 1;
                $info['msg'] = '组信息不存在，请先同步组';
                return $info;
            }
            $param['groupId'] = $a3_group['group_id'];
        }
        $person = [];
        if (isset($param['groupId']) && $param['groupId']) {
            $person['groupId'] = intval($param['groupId']);
        }
        if (isset($param['personId']) && $param['personId']) {
            $person['personId'] = intval($param['personId']);
        }
        if (isset($param['name']) && $param['name']) {
            $person['name'] = strval($param['name']);
        }
        if (isset($param['sex']) && $param['sex']) {
            $person['sex'] = intval($param['sex']);
        }
        if (isset($param['ID']) && $param['ID']) {
            $person['ID'] = strval($param['ID']);
        }
        if (isset($param['address']) && $param['address']) {
            $person['address'] = strval($param['address']);
        }

        $deviceId = $a3_device_id;
        $token = $this->a3_token;
        $key = $this->a3_key;
        $url = $this->base_face_a3_url  . "/fa/person/device/{$deviceId}/getPersonList?token=".$token;
        $pwd = base64_decode($key);
        $sign = json_encode(['person' => $person, 'deviceId' => intval($deviceId), 'signCreateTime' => date('YmdHis')], JSON_UNESCAPED_UNICODE);
        $sign_pwd = openssl_encrypt ($sign, 'DES-ECB', $pwd);
        $postData = [
            'person' => $person,
            'sign' => $sign_pwd
        ];
        if (!$this->http_net) {
            import('ORG.Net.Http');
            $this->http_net = new Http();
        }
        $return =  $this->http_net->curlPostOwnWithHeader($url, json_encode($postData), ['Content-Type: application/json'],$this->time_out_time);
        fdump_api(['查询接口打印'.__LINE__,$url, $param, $postData,$return],'a3/get_a3_person_list_log',1);

        if ($return) {
            $return_msg = json_decode($return,true);
            if ($return_msg['code']==0 || $return_msg['data']) {
                $data = $return_msg['data'];
                if (is_array($data) && $data[0]) {
                    $person_info = reset($data);
                    $person_id = $person_info['personId'];
                    if ($person_info['personID']) {
                        $personID = $person_info['personID'];
                    } else {
                        $personID = '';
                    }
                    if ($person_info['groupID']) {
                        $groupID = $person_info['groupID'];
                    } elseif ($person_info['groupIdOnDevice']) {
                        $groupID = $person_info['groupIdOnDevice'];
                    } else {
                        $groupID = '';
                    }
                    $groupId = $person_info['groupId'];


					if(count($data) > 1 && $pigcms_id){
                        fdump_api(['人员数组列表'.__LINE__,$data],'a3/get_a3_person_list_log',1);

						for($i=1;$i<count($data);$i++){
							if($data[$i]['personId']){
								$this->del_a3_person($device_id, $pigcms_id, $data[$i]['personId'], $a3_device_id);
							}
						}
					}

                } else {
                    fdump_api(['返回了空信息'.__LINE__,$url,$postData,$return],'a3/get_a3_person_list_log',1);
                    $info['error'] = 1;
                    $info['msg'] = '没有查询到任何数据';
                    return $info;
                }
                if ($person_id && $pigcms_id) {
                    $face_user_bind_device = M('Face_user_bind_device');
                    $where_a3_face = [
                        'bind_type' => 0,
                        'person_id' => $person_id,
                        'device_id' => $device_id,
                        'device_type' => 3
                    ];
                    $a3_info = $face_user_bind_device->where($where_a3_face)->find();

                    if (!$a3_info) {
                        // 存在
                        $data = [
                            'bind_type' => 0,
                            'bind_id' => $pigcms_id,
                            'device_id' => $device_id,
                            'device_type' => 3
                        ];
                        $data['person_id'] = $person_id;
                        $data['personID'] = $personID;
                        if ($groupId) {
                            $data['group_id'] = $groupId;
                        } elseif($param['groupId']) {
                            $data['group_id'] = $param['groupId'];
                        }
                        $data['groupID'] = $groupID;
                        $data['info'] = serialize($person_info);
                        $data['add_time'] = time();
                        $add_id = $face_user_bind_device->data($data)->add();

                        if (!$add_id) {
                            fdump_api(['返回了空信息'.__LINE__,$data,$add_id],'a3/get_a3_person_list_err_log',1);
                        }
                        $data['id'] = $add_id;
                        $a3_info = $data;
                    }
                    $info['error'] = 0;
                    $info['person_id'] = $person_id;
                    $info['a3_info'] = $a3_info;
                    $info['msg'] = '获取成功';
                    return $info;
                } elseif ($person_id) {
                    $face_user_bind_device = M('Face_user_bind_device');
                    $where_a3_face = [
                        'bind_type' => 0,
                        'person_id' => $person_id,
                        'device_id' => $device_id,
                        'device_type' => 3
                    ];
                    $a3_info = $face_user_bind_device->where($where_a3_face)->find();
                    if (!$a3_info) {
                        fdump_api(['本地无法查询记录信息对应设备信息'.__LINE__,$url,$postData,$return],'a3/get_a3_person_list_err_log',1);
                        // 没有用户参数  且本地没有记录  直接删除
//                        $del = $this->del_a3_person($device_id,$pigcms_id,$person_id,$a3_device_id,$bind_user);
//                        fdump('line:---'.__LINE__,'api/log/'.date('Ymd').'/a3/get_a3_person_list_log',true);
//                        fdump($del,'api/log/'.date('Ymd').'/a3/get_a3_person_list_log',true);
                        $info['error'] = 0;
                        $info['msg'] = '获取成功';
                        return $info;
                    } else {
                        $info['error'] = 0;
                        $info['person_id'] = $person_id;
                        $info['a3_info'] = $a3_info;
                        $info['msg'] = '获取成功';
                        return $info;
                    }
                }
            } else {
                fdump_api(['查询不到信息'.__LINE__,$url,$postData,$return],'a3/get_a3_person_list_err_log',1);
                $info['error'] = 1;
                $info['msg'] = '没有查询到任何数据';
                return $info;
            }
        } else {
            fdump_api(['返回错误'.__LINE__,$url,$postData,$return],'a3/get_a3_person_list_err_log',1);
            $info['error'] = 1;
            $info['msg'] = '没有查询到任何数据';
            return $info;
        }
    }


    /**
     * 获取图片的Base64编码(不支持url) 绝对路劲
     * @date 2017-02-20 19:41:22
     *
     * @param string $img_file 传入本地图片地址
     * @param bool $base_head 是否需要base64头部，默认需要
     * @param integer $max_size 所需最大转换base64 默认 2*1024*1024
     *
     * @return string
     */
    public function imgToBase64($img_file, $base_head=true, $param=[]) {
        fdump_api(['base64图片转译前'.__LINE__,$img_file,$base_head,$param],'a3/imgtobase64',1);
        $img_content = file_get_contents($img_file);
        file_put_contents('./runtime/a3/'.md5(uniqid()).'_tmp.jpg', $img_content);
        return base64_encode($img_content);
        fdump_api(['上传图片1'.__LINE__,$img_file,$base_head,$param],'a3/new_img_file_log',1);
        $img_base64 = '';
        $img_file = str_replace('?x-oss-process=image/resize,m_fill,w_200,h_200', '', $img_file);
        $img_file = str_replace('&width=200&height=200&resize=fill&maxfilesize=0', '', $img_file);
        if(C('config.static_oss_switch')==1) {
            $file_handle = new file_handle();
            $file_handle->download($img_file);
            $img_file = $_SERVER['DOCUMENT_ROOT'].str_replace($_SERVER['REQUEST_SCHEME'] . '://' . C('config.static_oss_access_domain_names'), '', $img_file);
        }else if(C('config.static_cos_switch')==1) {
            $file_handle = new file_handle();
            $file_handle->download($img_file);
            $img_file = $_SERVER['DOCUMENT_ROOT'].str_replace($_SERVER['REQUEST_SCHEME'] . '://'.C('config.static_cos_access_domain_names'), '', $img_file);
        }else if (strpos($img_file,'http://') !== false) {
            $site_url = C('config.site_url');
            $site_url = str_replace('https://','http://',$site_url);
            $searh_val = $site_url . '/index.php?c=Image&a=thumb&url=';
            $img_file = str_replace($searh_val,'',$img_file);
            $img_file = urldecode($img_file);
            $img_file = str_replace('&width=200&height=200&resize=fill','',$img_file);
            $img_file = $_SERVER['DOCUMENT_ROOT'].str_replace($site_url,'',$img_file);
        } else if (strpos($img_file,'https://') !== false) {
            $site_url = C('config.site_url');
            $site_url = str_replace('http://','https://',$site_url);
            $searh_val = $site_url . '/index.php?c=Image&a=thumb&url=';
            $img_file = str_replace($searh_val,'',$img_file);
            $img_file = urldecode($img_file);
            $img_file = str_replace('&width=200&height=200&resize=fill','',$img_file);
            $img_file = $_SERVER['DOCUMENT_ROOT'].str_replace($site_url,'',$img_file);
        } else{
            $img_file = $_SERVER['DOCUMENT_ROOT'].str_replace(C('config.site_url'),'',$img_file);
        }
        if (file_exists($img_file)) {
            import('@.ORG.Image');
            $rand_num = date('Ymd');// 换成日期存储
            $upload_dir = "./upload/face_door/{$rand_num}/";
            if(!is_dir($upload_dir)){
                mkdir($upload_dir, 0777, true);
            }
            $upload_dir = "/upload/face_door/{$rand_num}/";
            $img_mer_id = date('YmdHis_').uniqid();
            $new_img_file = $_SERVER['DOCUMENT_ROOT'].$upload_dir ."{$img_mer_id}.jpg";
            Image::thumb($img_file,$new_img_file,'jpg',600,600);
            fdump_api(['上传图片'.__LINE__,$img_file,$new_img_file,$param],'a3/new_img_file_log',1);
            $app_img_file = file_get_contents($new_img_file);
            $file_content = base64_encode($app_img_file); // base64编码
            if ($base_head) {
                $img_base64 = 'data:image/jpeg;base64,' . $file_content;//合成图片的base64编码
            } else {
                $img_base64 = $file_content;//合成图片的base64编码
            }
        }

        return $img_base64; //返回图片的base64
    }

    public function imgToBase64New($img_file, $base_head=true) {
        $img_base64 = '';
        $img_file = str_replace('?x-oss-process=image/resize,m_fill,w_200,h_200', '', $img_file);
        $img_file = str_replace('?x-oss-process=image/resize,w_600,h_600', '', $img_file);
        if(C('config.static_oss_switch')==1) {
            $file_handle = new file_handle();
            $file_handle->download($img_file);
            $img_file = $_SERVER['DOCUMENT_ROOT'].str_replace($_SERVER['REQUEST_SCHEME'] . '://' . C('config.static_oss_access_domain_names'), '', $img_file);
        }else if(C('config.static_cos_switch')==1) {
            $file_handle = new file_handle();
            $file_handle->download($img_file);
            $img_file = $_SERVER['DOCUMENT_ROOT'].str_replace($_SERVER['REQUEST_SCHEME'] . '://'.C('config.static_cos_access_domain_names'), '', $img_file);
        }else if (strpos($img_file,'http://') !== false) {
            $site_url = C('config.site_url');
            $site_url = str_replace('https://','http://',$site_url);
            $searh_val = $site_url . '/index.php?c=Image&a=thumb&url=';
            $img_file = str_replace($searh_val,'',$img_file);
            $img_file = urldecode($img_file);
            $img_file = str_replace('&width=200&height=200&resize=fill','',$img_file);
            $img_file = $_SERVER['DOCUMENT_ROOT'].str_replace($site_url,'',$img_file);
        } else if (strpos($img_file,'https://') !== false) {
            $site_url = C('config.site_url');
            $site_url = str_replace('http://','https://',$site_url);
            $searh_val = $site_url . '/index.php?c=Image&a=thumb&url=';
            $img_file = str_replace($searh_val,'',$img_file);
            $img_file = urldecode($img_file);
            $img_file = str_replace('&width=200&height=200&resize=fill','',$img_file);
            $img_file = $_SERVER['DOCUMENT_ROOT'].str_replace($site_url,'',$img_file);
        } else{
            $img_file = $_SERVER['DOCUMENT_ROOT'].str_replace(C('config.site_url'),'',$img_file);
        }
        if (file_exists($img_file)) {
            $file_content = base64_encode($img_file); // base64编码
            if ($base_head) {
                $img_base64 = 'data:image/jpeg;base64,' . $file_content;//合成图片的base64编码
            } else {
                $img_base64 = $file_content;//合成图片的base64编码
            }
        }

        return $img_base64; //返回图片的base64
    }

    // 删除人脸
    public function del_a3_person_face($device_id,$pigcms_id,$face_id=0,$a3_device_id=0) {
        if (!$face_id) {
            $where_a3_face = [
                'bind_type' => 0,
                'bind_id' => $pigcms_id,
                'device_id' => $device_id,
                'device_type' => 3
            ];
            $face_id = M('Face_user_bind_device')->where($where_a3_face)->getField('face_id');
        }
        if (!$face_id) {
            fdump(['>>>>>>>>缺少a3人脸id>>>>>>>>>>'.__LINE__,$device_id,$pigcms_id,$face_id,$a3_device_id],'api/log/'.date('Ymd').'/a3/del_a3_face_err_log',true);
            return true;
        }
        if (!$a3_device_id) {
            $a3_device_id = M('House_face_device')->where(array('device_id' => $device_id))->getField('a3_device_id');
        }
        if (!$a3_device_id) {
            fdump(['>>>>>>>>缺少a3设备id>>>>>>>>>>'.__LINE__,$device_id,$pigcms_id,$face_id,$a3_device_id],'api/log/'.date('Ymd').'/a3/del_a3_face_err_log',true);
            return true;
        }
        $token = $this->a3_token;
        $key = $this->a3_key;
        $url = $this->base_face_a3_url  . "/fa/face/{$face_id}/device/{$a3_device_id}?token=".$token;
        $pwd = base64_decode($key);

        $sign = json_encode(['id' => intval($face_id), 'deviceId' => intval($a3_device_id), 'signCreateTime' => date('YmdHis')]);
        $sign_pwd = openssl_encrypt ($sign, 'DES-ECB', $pwd);
        $postData = [
            'sign' => $sign_pwd
        ];
        if (!$this->http_net) {
            import('ORG.Net.Http');
            $this->http_net = new Http();
        }
        $return =  $this->http_net->curlDeleteOwnWithHeader($url, json_encode($postData), ['Content-Type: application/json'],$this->time_out_time);

        if ($return) {
            $return_msg = json_decode($return,true);
            if ($return_msg['code']==0) {
                fdump(['>>>删除成功>>'.__LINE__,$return],'api/log/'.date('Ymd').'/a3/del_a3_face_log',true);
                return true;
            } else {
                if ($return_msg['code'] =='40018') {
                    // 已经不存在了  删除本地记录
                    $where_a3_face = [
                        'face_id' => $face_id
                    ];
                    M('Face_user_bind_device')->where($where_a3_face)->save(['face_id' => '']);
                }
                fdump(['>>>删除失败>>'.__LINE__,$device_id,$pigcms_id,$face_id,$a3_device_id,$url,$postData,$return],'api/log/'.date('Ymd').'/a3/del_a3_face_err_log',true);
            }
        } else {
            fdump(['>>>删除失败，接口直接false>>'.__LINE__,$device_id,$pigcms_id,$face_id,$a3_device_id,$url,$postData,$return],'api/log/'.date('Ymd').'/a3/del_a3_face_err_log',true);
        }
        return true;
    }


    /**
     * todo 仅针对删除工作人员
     * @author liukezhu
     * @date 2021/4/19
     * @param $pigcms_id
     * @param $village_id
     * @param array $bind_user
     * @return bool
     */
    public function del_work_user_bind($pigcms_id, $village_id, $bind_user=[]) {
        if (empty($bind_user)) {
            $bind_user = D('House_village_user_bind')->where(array('pigcms_id'=>$pigcms_id))->find();
        }
        $where = [
            'village_id' => $village_id,
            'is_del' => 0,
            'device_type' => ['in',[1,2,3,4,5,6,7,21,23,24,61,88]],

        ];
        $device_info = D('House_face_device')->where($where)->select();
        fdump([D('House_face_device')->_sql(),$device_info,$pigcms_id, $village_id,$bind_user],'api/log/'.date('Ymd').'/delDeviceUser/del_work_user_bind',true);
        if ($device_info) {
            foreach ($device_info as $val) {
                if (1==$val['device_type']) {
                    try {
                        D('House_face_img')->del_person($val['device_id'],[$pigcms_id], $val['device_sn']);
                    }catch (Exception $e) {
                        fdump_api(['删除与A1设备绑定用户>>'.__LINE__,$val, $e->getMessage()],'a1/del_a1_user_bind_log_err',1);
                    }
                }
                elseif (2==$val['device_type']) {
                    try {
                        D('House_village_face_door')->delete_user($pigcms_id);
                    }catch (Exception $e) {
                        fdump_api(['删除与A2设备绑定用户>>'.__LINE__,$val, $e->getMessage()],'a2/del_a2_user_bind_log_err',1);
                    }
                }
                elseif (3==$val['device_type']) {
                    try {
                        $this->del_a3_person($val['device_id'], $pigcms_id, 0, $val['a3_device_id']);
                    }catch (Exception $e) {
                        fdump_api(['删除与A3设备绑定用户>>'.__LINE__,$val, $e->getMessage()],'a3/del_a3_user_bind_log_err',1);
                    }
                }
                elseif (4==$val['device_type']) {
                    try {
                        if (4==$bind_user['type']) {
                            D('Face_door_a4_service')->delA4Property($pigcms_id,0,0, $bind_user);
                        } else {
                            D('Face_door_a4_service')->delA4Household($pigcms_id,0,0, $bind_user);
                        }
                    }catch (Exception $e) {
                        fdump_api(['删除与A4设备绑定用户>>'.__LINE__,$val, $e->getMessage()],'a4/del_a4_user_bind_log_err',1);
                    }
                }
                elseif (5==$val['device_type'] || 6==$val['device_type']) {
                    try {
                        D('Face_door_a5_service')->removeMember($pigcms_id);
                        D('Face_door_a5_service')->deleteMember($pigcms_id);
                    }catch (Exception $e) {
                        fdump_api(['删除与A5/6设备绑定用户>>'.__LINE__,$val, $e->getMessage()],'a5-6/del_a5_user_bind_log_err',1);
                    }
                }
                elseif (7==$val['device_type']) {
                    try {
                        D('Face_door_a7_service')->sync_delete_face_per($val['device_id'], $pigcms_id,$village_id, $val);
                    }catch (Exception $e) {
                        fdump_api(['删除与A7设备绑定用户>>'.__LINE__,$val, $e->getMessage()],'a7/del_a7_user_bind_log_err',1);
                    }
                }
                elseif (21==$val['device_type']) {
                    try {
                        D('Face_door_d1_service')->d1_del_user($pigcms_id);
                    }catch (Exception $e) {
                        fdump_api(['删除与D1设备绑定用户>>'.__LINE__,$val, $e->getMessage()],'D1/del_d1_user_bind_log_err',1);
                    }
                }
                elseif (23 == $val['device_type']) {
                    /**
                     * todo d3 删除设备人员
                     *
                     */
                    try {
                        D('Face_d3_request_record')->d3_delete_face($val['device_id'], $pigcms_id,$bind_user);
                    }catch (Exception $e) {
                        fdump_api(['删除与D3设备绑定用户>>'.__LINE__,$val, $e->getMessage()],'d3/del_d1_user_bind_log_err',1);
                    }
                }
                elseif (24 == $val['device_type']) {
                    /**
                     * todo d4 删除设备人员
                     *
                     */
                    try {
                        D('Face_d4_request_record')->d4_delete_face($val['device_id'], $pigcms_id,$bind_user);
                    }catch (Exception $e) {
                        fdump_api(['删除与D3设备绑定用户>>'.__LINE__,$val, $e->getMessage()],'d4/del_d4_user_bind_log_err',1);
                    }
                }

                elseif (61==$val['device_type']) {
                    /**
                     * todo A185删除设备人员
                     *
                     */
                    try {
                        D('Face_a185_request_record')->a185_delete_face($val['device_id'], $pigcms_id,$bind_user);
                    }catch (Exception $e) {
                        fdump([__FUNCTION__.'删除与D1设备绑定用户>>>>'.__LINE__,$val,$e->getMessage()],'api/log/'.date('Ymd').'/a185/del_a185_user_bind_log_err',true);
                    }
                }elseif(88==$val['device_type']){
                    /**
                     * todo 大华删除设备人员
                     *
                     */
                    try {
                        $delRes = D('Dahua_service')->returnByNumber($pigcms_id);
                        if ($delRes['status']==1) {
                            M('Face_user_bind_device')->where(['bind_id'=>$pigcms_id,'device_type'=>88])->delete();
                        } else {
                            fdump([__FUNCTION__.'删除与大华设备绑定用户>>>>'.__LINE__,$delRes],'api/log/'.date('Ymd').'/dahua/del_user_bind_log_err',true);
                        }
//                        $person_id = M('Face_user_bind_device')->where(['bind_id'=>$pigcms_id,'device_type'=>88])->getField('person_id');
//                        if($person_id){
//                            D('Dahua_service')->delPerson([$person_id]);
//                        }
//                        M('Face_user_bind_device')->where(['bind_id'=>$pigcms_id,'device_type'=>88])->delete();
                    }catch (Exception $e) {
                        fdump([__FUNCTION__.'删除与大华设备绑定用户>>>>'.__LINE__,$val,$e->getMessage()],'api/log/'.date('Ymd').'/dahua/del_user_bind_log_err',true);
                    }
                }

            }
        }
        return true;
    }

    public function del_a3_user_bind($pigcms_id, $village_id, $bind_user=[], $cardNos = []) {
        if (empty($bind_user)) {
            $bind_user = D('House_village_user_bind')->where(array('pigcms_id'=>$pigcms_id))->find();
        }
        $hik_info_vision_i_community_app_key=C('config.hik_info_vision_i_community_app_key');
        $hik_info_vision_i_community_app_secret=C('config.hik_info_vision_i_community_app_secret');
        if($hik_info_vision_i_community_app_key && $hik_info_vision_i_community_app_secret){
            $hikRet=invoke_v20_service('\app\community\model\service\Device\HikInfoVisionICommunityService/deleteBindUserOfHikCommunity', ['village_id'=>$village_id,'pigcms_id'=>$pigcms_id]);
        }
        $where = [
            'village_id' => $village_id,
            'is_del' => 0,
            'device_type' => ['in',[1,2,3,4,5,6,7,21,23,24,29,30,61,88,31]],
            '_string' => "(floor_id=-1 OR floor_id={$bind_user['floor_id']} OR FIND_IN_SET('{$bind_user['floor_id']}',floor_id))"
        ];
        $hfd = D('House_face_device');
        $device_info = $hfd->where($where)->select();
        fdump([D('House_face_device')->_sql(),$device_info,$pigcms_id, $village_id,$bind_user],'api/log/'.date('Ymd').'/delDeviceUser/del_a3_user_bind',true);
        $hasHikNeiBu = false;
        if ($device_info) {
            $hfd = D('House_face_device');
            $hikGateKeyVillage = $hfd::HIK_DEVICE_GATEWAY_CUSTOM;
            $hikGateKey = $hfd::HIK_DEVICE_GATEWAY;
            $func = '\app\community\model\service\Device\HikGateWayDeviceService/deleteUserBox';
            foreach ($device_info as $val) {
                $thirdProtocol = isset($val['thirdProtocol']) && $val['thirdProtocol'] ? intval($val['thirdProtocol']) : 0;
                if ($thirdProtocol == $hfd::HIK_YUNMO_NEIBU_SHEQU) {
                    $hasHikNeiBu = true;
                    continue;
                }
                if ($thirdProtocol && in_array($thirdProtocol, [$hikGateKey, $hikGateKeyVillage])) {
                    $param = [
                        'pigcms_id' => $pigcms_id, 'device_id' => $val['device_id'], 'village_id' => $val['village_id'],
                        'thirdProtocol' => $thirdProtocol, 'device_sn' => $val['device_sn'], 'deviceType' => 'face',
                    ];
                    $employeeNo = [];
                    $employeeNo[] = ['employeeNo' => trim($pigcms_id)];
                    $params = [
                        'deviceInfo' => [], 'param' => $param, 'employeeNo' => $employeeNo, 'mode' => 'byEmployeeNo',
                    ];
                    try {
                        invoke_v20_service($func, $params);
                    } catch (Exception $e) {
                        $errArr = [
                            'err' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(),
                            'msg' => '删除指令下发失败', 'params' => $params, 'pigcms_id' => $pigcms_id, 'village_id' => $village_id
                        ];
                        fdump_api($errArr, 'hikGateWay/errDeleteUserBoxLog', 1);
                    }
                } elseif (30 === intval($val['device_type'])) {
                    try {
                        $data = [
                            'device_id' => $val['device_id'], 'pigcms_id' => $pigcms_id, 'device_sn' => $val['device_sn'],
                        ];
                        D('House_village_device_cmd')->addOrUpdateDeleteCmd($data);
                    }catch (Exception $e) {
                        fdump_api(['msg' => '删除指令下发失败', 'err' => $e->getMessage(), 'data' => $data, 'pigcms_id' => $pigcms_id, 'village_id' => $village_id], '260Cloud/errDeleteUser', 1);
                    }
                }
                if (1==$val['device_type']) {
                    try {
                        D('House_face_img')->del_person($val['device_id'],[$pigcms_id], $val['device_sn']);
                    }catch (Exception $e) {
                        fdump('删除与A1设备绑定用户>>'.__LINE__,'api/log/'.date('Ymd').'/a1/del_a2_user_bind_log_err',true);
                        fdump($val,'api/log/'.date('Ymd').'/a1/del_a2_user_bind_log_err',true);
                        fdump($e->getMessage(),'api/log/'.date('Ymd').'/a1/del_a2_user_bind_log_err',true);
                    }
                }
                elseif (2==$val['device_type']) {
                    try {
                        D('House_village_face_door')->delete_user($pigcms_id);
                    }catch (Exception $e) {
                        fdump('删除与A2设备绑定用户>>'.__LINE__,'api/log/'.date('Ymd').'/a2/del_a2_user_bind_log_err',true);
                        fdump($val,'api/log/'.date('Ymd').'/a2/del_a2_user_bind_log_err',true);
                        fdump($e->getMessage(),'api/log/'.date('Ymd').'/a2/del_a2_user_bind_log_err',true);
                    }
                }
                elseif (3==$val['device_type']) {
                    try {
                        $this->del_a3_person($val['device_id'], $pigcms_id, 0, $val['a3_device_id']);
                    }catch (Exception $e) {
                        fdump('删除与A3设备绑定用户>>'.__LINE__,'api/log/'.date('Ymd').'/a3/del_a3_user_bind_log_err',true);
                        fdump($val,'api/log/'.date('Ymd').'/a3/del_a3_user_bind_log_err',true);
                        fdump($e->getMessage(),'api/log/'.date('Ymd').'/a3/del_a3_user_bind_log_err',true);
                    }
                }
                elseif (4==$val['device_type']) {
                    try {
                        if (4==$bind_user['type']) {
                            D('Face_door_a4_service')->delA4Property($pigcms_id,0,0, $bind_user);
                        } else {
                            D('Face_door_a4_service')->delA4Household($pigcms_id,0,0, $bind_user);
                        }
                    }catch (Exception $e) {
                        fdump('删除与A4设备绑定用户>>'.__LINE__,'api/log/'.date('Ymd').'/a4/del_a4_user_bind_log_err',true);
                        fdump($val,'api/log/'.date('Ymd').'/a4/del_a4_user_bind_log_err',true);
                        fdump($e->getMessage(),'api/log/'.date('Ymd').'/a4/del_a4_user_bind_log_err',true);
                    }
                }
                elseif (5==$val['device_type'] || 6==$val['device_type']) {
                    try {
                        D('Face_door_a5_service')->removeMember($pigcms_id);
                        D('Face_door_a5_service')->deleteMember($pigcms_id, $cardNos);
                    }catch (Exception $e) {
                        fdump('删除与A5/6设备绑定用户>>'.__LINE__,'api/log/'.date('Ymd').'/a5-6/del_a5_user_bind_log_err',true);
                        fdump($val,'api/log/'.date('Ymd').'/a5-6/del_a5_user_bind_log_err',true);
                        fdump($e->getMessage(),'api/log/'.date('Ymd').'/a5-6/del_a5_user_bind_log_err',true);
                    }
                }
                elseif (7==$val['device_type']) {
                    try {
                        D('Face_door_a7_service')->sync_delete_face_per($val['device_id'], $pigcms_id,$village_id, $val);
                    }catch (Exception $e) {
                        fdump('删除与A7设备绑定用户>>'.__LINE__,'api/log/'.date('Ymd').'/a7/del_a7_user_bind_log_err',true);
                        fdump($val,'api/log/'.date('Ymd').'/a7/del_a7_user_bind_log_err',true);
                        fdump($e->getMessage(),'api/log/'.date('Ymd').'/a7/del_a7_user_bind_log_err',true);
                    }
                }
                elseif (21==$val['device_type']) {
                    try {
                        D('Face_door_d1_service')->d1_del_user($pigcms_id);
                    }catch (Exception $e) {
                        fdump(__FUNCTION__.'删除与D1设备绑定用户>>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/del_d1_user_bind_log_err',true);
                        fdump($val,'api/log/'.date('Ymd').'/D1/del_d1_user_bind_log_err',true);
                        fdump($e->getMessage(),'api/log/'.date('Ymd').'/D1/del_d1_user_bind_log_err',true);
                    }
                }
                elseif (23==$val['device_type']) {
                    /**
                     * todo d3 删除设备人员
                     *
                     */
                    try {
                      D('Face_d3_request_record')->d3_delete_face($val['device_id'], $pigcms_id,$bind_user);
                    }catch (Exception $e) {
                        fdump(__FUNCTION__.'删除与D1设备绑定用户>>>>'.__LINE__,'api/log/'.date('Ymd').'/d3/del_d1_user_bind_log_err',true);
                        fdump($val,'api/log/'.date('Ymd').'/d3/del_d1_user_bind_log_err',true);
                        fdump($e->getMessage(),'api/log/'.date('Ymd').'/d3/del_d1_user_bind_log_err',true);
                    }
                }
                elseif (24 == $val['device_type']) {
                    /**
                     * todo d4 删除设备人员
                     *
                     */
                    try {
                        D('Face_d4_request_record')->d4_delete_face($val['device_id'], $pigcms_id,$bind_user);
                    }catch (Exception $e) {
                        fdump(__FUNCTION__ . '删除与D4设备绑定用户>>>>' . __LINE__, 'api/log/' . date('Ymd') . '/d4/del_d4_user_bind_log_err', true);
                        fdump($val, 'api/log/' . date('Ymd') . '/d4/del_d4_user_bind_log_err', true);
                        fdump($e->getMessage(), 'api/log/' . date('Ymd') . '/d3/del_d4_user_bind_log_err', true);
                    }
                }
                elseif(29==$val['device_type']){
                    /*** todo 朵普*/
                    try {
                        $models = '\app\community\model\service\CloudIntercomService/sendThirdUserToDeviceDeal';
                        $rr=invoke_v20_service($models,[
                            'village_id'=>$village_id,
                            'deviceSn'=>$val['device_sn'],
                            'thirdUser'=>[$pigcms_id],
                            'operation'=>'del',
                            'source'=>'house_village'
                        ]);
                        fdump_api(['朵普删除：'.__LINE__,$val,'pigcms_id'=>$pigcms_id,$rr],'duopu/v20_log',1);
                    } catch (Exception $e) {
                        fdump_api(['朵普删除：'.__LINE__,'pigcms_id'=>$pigcms_id,$e->getMessage()],'duopu/open_err',1);
                    }
                }elseif(31==$val['device_type']){
                    /*** todo 西涵门禁*/
                    try {
                        $face_door_xihan_service=D('Face_door_xihan_service');
                        $face_door_xihan_service->del_face_door_user($pigcms_id,$village_id,$val['device_id']);
                        fdump_api([__LINE__,$val,'pigcms_id'=>$pigcms_id],'xihan/del_user_bind',1);
                    } catch (Exception $e) {
                        fdump_api([__LINE__,'pigcms_id'=>$pigcms_id,$e->getMessage()],'xihan/del_user_bind',1);
                    }
                }
                elseif (61==$val['device_type']) {
                    /**
                     * todo A185删除设备人员
                     *
                     */
                    try {
                        D('Face_a185_request_record')->a185_delete_face($val['device_id'], $pigcms_id,$bind_user);
                    }catch (Exception $e) {
                        fdump(__FUNCTION__.'删除与D1设备绑定用户>>>>'.__LINE__,'api/log/'.date('Ymd').'/a185/del_a185_user_bind_log_err',true);
                        fdump($val,'api/log/'.date('Ymd').'/a185/del_a185_user_bind_log_err',true);
                        fdump($e->getMessage(),'api/log/'.date('Ymd').'/a185/del_a185_user_bind_log_err',true);
                    }
                }
                elseif(88==$val['device_type']){
                    /**
                     * todo 大华删除设备人员
                     *
                     */
                    try {
                        $delRes = D('Dahua_service')->returnByNumber($pigcms_id);
                        if ($delRes['status']==1) {
                            M('Face_user_bind_device')->where(['bind_id'=>$pigcms_id,'device_type'=>88])->delete();
                        } else {
                            fdump([__FUNCTION__.'删除与大华设备绑定用户>>>>'.__LINE__,$delRes],'api/log/'.date('Ymd').'/dahua/del_user_bind_log_err',true);
                        }
//                        $person_id = M('Face_user_bind_device')->where(['bind_id'=>$pigcms_id,'device_type'=>88])->getField('person_id');
//                        if($person_id){
//                            D('Dahua_service')->delPerson([$person_id]);
//                        }
//                        M('Face_user_bind_device')->where(['bind_id'=>$pigcms_id,'device_type'=>88])->delete();
                    }catch (Exception $e) {
                        fdump([__FUNCTION__.'删除与大华设备绑定用户>>>>'.__LINE__,$val,$e->getMessage()],'api/log/'.date('Ymd').'/dahua/del_user_bind_log_err',true);
                    }
                }
            }
        }
        if ($hasHikNeiBu) {
            $room_id = isset($bind_user['vacancy_id']) ? intval($bind_user['vacancy_id']) : 0;
            $village_id = isset($bind_user['village_id']) ? intval($bind_user['village_id']) : 0;
            $uid = isset($bind_user['uid']) ? intval($bind_user['uid']) : 0;
            $type = isset($bind_user['type']) ? intval($bind_user['type']) : 0;
            $phone = isset($bind_user['phone']) ? trim($bind_user['phone']) : '';
            $models = '\app\community\model\service\Device\DeviceHkNeiBuHandleService/deleteCloudUser';
            $cloudParam = [
                'room_id' => $room_id, 'village_id' => $village_id, 'uid' => $uid, 'type' => $type, 'phone' => $phone,
            ];
            $param = [
                'pigcms_id' => $pigcms_id, 'param' => $cloudParam
            ];
            invoke_v20_service($models,$param);
        }
        
        $third_robot_recognition_open = C('config.third_robot_recognition_open');
        $third_robot_recognition_appid = C('config.third_robot_recognition_appid');
        $third_robot_recognition_appsecret = C('config.third_robot_recognition_appsecret');
        if ($third_robot_recognition_open && $third_robot_recognition_appid && $third_robot_recognition_appsecret) {
            $models = '\app\thirdRobot\model\service\ThirdRobotDeviceCmdService/deleteRoboterFaceJob';
            $param = [
                'pigcms_id'     => $pigcms_id,
                'uid'    => 0,
            ];
            invoke_v20_service($models,$param);
        }
        return true;
    }

    // 删除人员
    public function del_a3_person($device_id,$pigcms_id,$person_id=0,$a3_device_id=0,$bind_user=[],$a3_id=0) {
        if (empty($bind_user)) {
            $bind_user = D('House_village_user_bind')->where(array('pigcms_id'=>$pigcms_id))->find();
        }
        $face_user_bind_device = M('Face_user_bind_device');
        if (!$person_id) {
            $where_a3_face = [
                'bind_type' => 0,
                'bind_id' => $pigcms_id,
                'device_id' => $device_id,
                'device_type' => 3
            ];
            $a3_info = $face_user_bind_device->where($where_a3_face)->field('id,person_id')->find();
            $person_id = $a3_info['person_id'];
        }
        if (!$a3_device_id) {
            $a3_device_id = M('House_face_device')->where(array('device_id' => $device_id))->getField('a3_device_id');
        }
        if (!$a3_device_id || !$person_id) {
            if ($a3_device_id && !$person_id) {
                $group_where = [
                    'group_type' => 0,
                    'group_type_id' => $bind_user['village_id'],
                    'device_id' => $device_id
                ];
                $a3_group = M('Face_a3_group')->where($group_where)->find();
                if (empty($a3_group)) {
                    fdump(['缺少a3设备id或者用户id>>'.__LINE__,$device_id,$pigcms_id,$person_id,$bind_user,$a3_device_id,$a3_group],'api/log/'.date('Ymd').'/a3/del_a3_person_err_log',true);
                    return false;
                }
                $group_id = $a3_group['group_id'];
                $sex = 0;
                $age = 18;
                if ($bind_user['ic_card']) {
                    $idNum = $bind_user['ic_card'];
                } elseif ($bind_user['id_card']) {
                    $idNum = $bind_user['id_card'];
                } else {
                    $idNum = $pigcms_id;
                }
                $param = [
                    'groupId' => $group_id,
                    'name' => trim($bind_user['name']) ? trim($bind_user['name']) : '住户_'.$bind_user['pigcms_id'],
                    'sex' => $sex,
                    'age' => $age,
                    'idNum' => $idNum,
                ];
                if ($bind_user['address']) {
                    $param['address'] = $bind_user['address'];
                }
                $person_msg = $this->get_a3_person_list($device_id, $param,$a3_device_id,$pigcms_id,$bind_user);
                if ($person_msg['error']==0 && $person_msg['person_id']) {
                    // 删除的时候没有参数查询下
                    $person_id = intval($person_msg['person_id']);
                } else {
                    fdump(['缺少a3设备id或者用户id>>'.__LINE__,$device_id,$pigcms_id,$person_id,$bind_user,$a3_device_id,$a3_group],'api/log/'.date('Ymd').'/a3/del_a3_person_err_log',true);
                    return false;
                }
            } else {
                fdump(['缺少a3设备id或者用户id>>'.__LINE__,$device_id,$pigcms_id,$person_id,$bind_user,$a3_device_id],'api/log/'.date('Ymd').'/a3/del_a3_person_err_log',true);
                return false;
            }
        }
        $token = $this->a3_token;
        $key = $this->a3_key;
        $url = $this->base_face_a3_url  . "/fa/person/device/{$a3_device_id}?token=".$token;
        $pwd = base64_decode($key);

        $sign = json_encode(['personId' => intval($person_id), 'deviceId' => intval($a3_device_id), 'signCreateTime' => date('YmdHis')]);
        $sign_pwd = openssl_encrypt ($sign, 'DES-ECB', $pwd);
        $postData = [
            'personId' => $person_id,
            'sign' => $sign_pwd
        ];
        if (!$this->http_net) {
            import('ORG.Net.Http');
            $this->http_net = new Http();
        }
        $return =  $this->http_net->curlDeleteOwnWithHeader($url, json_encode($postData), ['Content-Type: application/json'],$this->time_out_time);

        if ($return) {
            $return_msg = json_decode($return,true);
            if ($return_msg['code']==0 || 40017==$return_msg['code']) {
                // 删除成功或者设备上已经不存在的 直接同步删除本地的
                fdump(['>删除成功>'.__LINE__,$device_id,$pigcms_id,$person_id,$a3_device_id,$url,$postData,$return],'api/log/'.date('Ymd').'/a3/del_a3_person_log',true);
                if ($person_id) {
                    if ($a3_id) {
                        $where_a3_face = [
                            'id' => $a3_id,
                        ];
                    } else {
                        $where_a3_face = [
                            'bind_type' => 0,
                            'bind_id' => $pigcms_id,
                            'device_id' => $device_id,
                            'device_type' => 3
                        ];
                    }
                    $a3_info = $face_user_bind_device->where($where_a3_face)->find();
                    if ($a3_info) {
                        fdump(['>删除成功>'.__LINE__,$a3_info,$where_a3_face],'api/log/'.date('Ymd').'/a3/del_a3_person_log',true);
                        $face_user_bind_device->where($where_a3_face)->delete();
                    }
                }
                return true;
            } else {
                fdump(['>删除失败>'.__LINE__,$device_id,$pigcms_id,$person_id,$a3_device_id,$bind_user,$sign,$postData,$url,$return],'api/log/'.date('Ymd').'/a3/del_a3_person_err_log',true);
                return false;
            }
        } else {
            fdump(['>删除失败，接口直接false>'.__LINE__,$device_id,$pigcms_id,$person_id,$a3_device_id,$bind_user,$sign,$postData,$url,$return],'api/log/'.date('Ymd').'/a3/del_a3_person_err_log',true);
            return false;
        }
    }

    // 远程开门
    public function send_open_a3_door($device_id,$pigcms_id,$field='pigcms_id',$is_owner=true,$uid=0) {
        if(!$pigcms_id){
            fdump(['>参数不对>'.__LINE__,$device_id,$pigcms_id,$field],'api/log/'.date('Ymd').'/a3/send_open_a3_door_err_log',true);
            return array('code'=>0,'msg'=>'参数不对');
        }
        $door_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        if (empty($door_info)) {
            fdump(['>参数错误，对应设备不存在>'.__LINE__,$device_id,$pigcms_id,$field],'api/log/'.date('Ymd').'/a3/send_open_a3_door_err_log',true);
            $info['error'] = 1;
            $info['msg'] = '参数错误，对应设备不存在！';
            return $info;
        }

        // 查询业主
        $bind_user = D('House_village_user_bind')->where(array($field=>$pigcms_id))->field('status,village_id,a3_person_id,id_card,address,uid')->find();
        if(!$bind_user){
            fdump(['>当前成员不存在>'.__LINE__,$device_id,$pigcms_id,$field,D('House_village_user_bind')->_sql()],'api/log/'.date('Ymd').'/a3/send_open_a3_door_err_log',true);
            return array('code'=>0,'msg'=>'当前成员不存在');
        }
        if (1!=$bind_user['status']) {
            fdump(['>当前成员不能同步>'.__LINE__,$device_id,$pigcms_id,$field,D('House_village_user_bind')->_sql()],'api/log/'.date('Ymd').'/a3/send_open_a3_door_err_log',true);
            return array('code'=>0,'msg'=>'当前成员不能同步');
        }
        $a3_device_id = $door_info['a3_device_id'];
        if (empty($a3_device_id)) {
            fdump(['>A3人脸设备未同步>'.__LINE__,$device_id,$pigcms_id,$field,$bind_user,$door_info],'api/log/'.date('Ymd').'/a3/send_open_a3_door_err_log',true);
            return array('code'=>0,'msg'=>'A3人脸设备未同步');
        }
        $token = $this->a3_token;
        $key = $this->a3_key;
        $url = $this->base_face_a3_url  . "/dms/device/alarmOut/{$a3_device_id}?token=".$token;
        $pwd = base64_decode($key);

        $channel = 0;
        $status = 1;
        $sign = json_encode(['channel' => $channel, 'status' => $status, 'deviceId' => intval($a3_device_id), 'signCreateTime' => date('YmdHis')]);
        $sign_pwd = openssl_encrypt ($sign, 'DES-ECB', $pwd);
        $postData = [
            'channel' => $channel,
            'status' => $status,
            'sign' => $sign_pwd
        ];
        if (!$this->http_net) {
            import('ORG.Net.Http');
            $this->http_net = new Http();
        }
        $return =  $this->http_net->curlPostOwnWithHeader($url, json_encode($postData), ['Content-Type: application/json'],$this->time_out_time);


        $d_house_face_device = M('House_face_device');
        $d_house_user_log = M('House_user_log');
        $d_house_village_floor = M('House_village_floor');
        $d_house_village = M('House_village');
        $d_house_village_user_bind = D('House_village_user_bind');
        $d_house_village_single = D('House_village_single');

        $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
        $house_village_floor_info = $d_house_village_floor->where(array('floor_id' => $house_face_device_info['floor_id']))->field('floor_id,floor_layer,floor_name,single_id')->find();
        $house_village_single_info = $d_house_village_single->where(['id' => $house_village_floor_info['single_id']])->field('id as single_id,single_name')->find();
        $house_village_info = $d_house_village->where(array('village_id' => $house_face_device_info['village_id']))->field('village_id,village_name,city_id')->find();
        $village_name = $house_village_info['village_name'];

        if ( $house_face_device_info['public_area_id'] >0 ) {
            $public_area_name = M('House_village_public_area')->where(['public_area_id' => $house_face_device_info['public_area_id']])->getField('public_area_name');
            $floor_name = '';
            $floor_layer = $public_area_name;
        } elseif ( $house_face_device_info['floor_id'] == -1) {
            $floor_layer	= C('config.house_name');
            $floor_name = '大门';
        }else{
            $floor_layer = $house_village_single_info['single_name'] ? $house_village_single_info['single_name'] : $house_village_floor_info['floor_layer'];
            $floor_name = $house_village_floor_info['floor_name'];
        }
        $log = array();
        $house_village_bind = $d_house_village_user_bind->where(['pigcms_id' => $pigcms_id])->field('name,phone,uid,pigcms_id')->find();
        if (!$is_owner && $uid) {
            $users = D('User')->where(array('uid'=>$uid))->field('nickname,phone')->find();
            if (!$users['nickname'] && $users['phone']) {
                $user_tip = substr_replace($users['phone'],'****',3,4);
            } elseif ($users['nickname']){
                $user_tip = $users['nickname'];
            }
            $log_name = $village_name . ' ' . $floor_layer . ' ' . $floor_name."【访客{$user_tip}开门】";
        } else {
            if (!$uid) {
                $uid = $house_village_bind['uid'];
            }
            if ($house_village_bind['name']) {
                $user_tip = $house_village_bind['name'];
            } elseif ($house_village_bind['phone']) {
                $user_tip = substr_replace($house_village_bind['phone'],'****',3,4);
            } else {
                $users = D('User')->where(array('uid'=>$uid))->field('nickname,phone')->find();
                if (!$users['nickname'] && $users['phone']) {
                    $user_tip = substr_replace($users['phone'],'****',3,4);
                } elseif ($users['nickname']){
                    $user_tip = $users['nickname'];
                }
            }
            $log_name = $village_name . ' ' . $floor_layer . ' ' . $floor_name;
        }
        $log['uid'] = $uid;
        $log['log_bind_id'] = $pigcms_id;
        $log['log_name'] = $log_name;
        $log['log_from'] = 2; // 人脸识别门禁-用户操作开门
        $log['log_business_id'] = $house_face_device_info['village_id']; // 社区id
        $log['log_more_id'] = $house_face_device_info['floor_id']; // 单元id
        $log['device_id'] = $device_id; // 设备id
        $log['log_time'] = time(); // 记录时间

        if ($return) {
            $return_msg = json_decode($return,true);
            if ($return_msg['code']==0) {
                $log['log_status'] = 0;
                // 0 开门成功，1 开门失败
                $log['log_detail'] = serialize($return_msg);
                $log['add_time']=time();
                $d_house_user_log->data($log)->add();
                $d_house_face_device->where(array('device_id' => $device_id))->setField('last_open_time', time());
                // 呼叫电梯
                if (1==$house_face_device_info['device_model'] && $house_face_device_info['floor_id'] && $house_face_device_info['floor_id']!=-1) {
                    D('Face_door_a7_service')->add_send_rs485($house_face_device_info['device_id']);
                }
                // 跑计划任务
                if ($house_face_device_info['village_id']>0) {
                    $param = [];
                    $param['village_id'] = $house_face_device_info['village_id'];
                    $param['type'] = 'person_inout_info';
                    D('House_village_third_user_info')->add_plan($param, 'person_inout_info', time() + (mt_rand(5, 20)));
                }
                return array('code'=>1,'msg'=>'开门成功','data'=>$return_msg);
            } else {
                $log['log_status'] = 1;
                // 0 开门成功，1 开门失败
                $log['log_detail'] = serialize($return_msg);
                $log['add_time']=time();
                $d_house_user_log->data($log)->add();

                fdump(['>A3开门失败>'.__LINE__,$url,$sign,$postData,$return,$a3_device_id,$log],'api/log/'.date('Ymd').'/a3/send_open_a3_door_err_log',true);
                return array('code'=>0,'msg'=>'开门失败！');
            }
        } else {
            // 0 开门成功，1 开门失败
            $log['log_detail'] = serialize($return);
            $log['add_time']=time();
            $d_house_user_log->data($log)->add();

            fdump(['>A3开门失败>'.__LINE__,$url,$sign,$postData,$return,$a3_device_id,$log],'api/log/'.date('Ymd').'/a3/send_open_a3_door_err_log',true);
            return array('code'=>0,'msg'=>'开门失败！');
        }
    }

    // 重启设备
    public function device_a3_restart($device_id,$a3_device_id='') {
        $d_house_face_device = M('House_face_device');
        if (!$a3_device_id) {
            $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
            $a3_device_id = $house_face_device_info['a3_device_id'];
        }
        $token = $this->a3_token;
        $key = $this->a3_key;
        $url = $this->base_face_a3_url  . "/dms/device/{$a3_device_id}/device/reboot?token=".$token;
        $pwd = base64_decode($key);

        $sign = json_encode(['signCreateTime' => date('YmdHis'), 'deviceId' => intval($a3_device_id)]);
        $sign_pwd = openssl_encrypt ($sign, 'DES-ECB', $pwd);
        $postData = [
            'sign' => $sign_pwd
        ];
        if (!$this->http_net) {
            import('ORG.Net.Http');
            $this->http_net = new Http();
        }
        $return =  $this->http_net->curlPutOwnWithHeader($url, json_encode($postData), ['Content-Type: application/json'],$this->time_out_time);

        if ($return) {
            $return_msg = json_decode($return,true);
            if ($return_msg['code']==0) {
                // 开门成功
                $data['login_time'] = time();
                M('House_face_device')->where(array('device_id' => $device_id))->data($data)->save();
                return true;
            } else {
                fdump(['>重启失败>'.__LINE__,$url,$sign,$postData,$return,$a3_device_id],'api/log/'.date('Ymd').'/a3/device_a3_restart_err_log',true);
                return true;
            }
        } else {
            fdump(['>重启失败>'.__LINE__,$url,$sign,$postData,$return,$a3_device_id],'api/log/'.date('Ymd').'/a3/device_a3_restart_err_log',true);
            return true;
        }
        return true;
    }

    // 订阅人脸识别开门通知
    public function face_a3_subscription($device_id, $a3_device_id) {
        $token = $this->a3_token;
        $key = $this->a3_key;
        $url = $this->base_face_a3_url  . "/event/subscription?token=".$token;
        $pwd = base64_decode($key);

        $site_url = C('config.site_url');
//        $destination = urlencode($site_url . '/a3-open-door-alarm');
//        $destination = $site_url . '/a3-open-door-alarm';
        $destination = 'https://o2o-service.pigcms.com/face_door_a3_notice.php';

        $sign = json_encode(['eventType'=>'all', 'eventLevel'=>'5', 'destination'=> $destination, 'signCreateTime' => date('YmdHis')]);
        $sign_pwd = openssl_encrypt ($sign, 'DES-ECB', $pwd);
        $postData = [
            'eventType'=>'all',
            'eventLevel'=>'5',
            'destination'=> $destination,
            'sign' => $sign_pwd
        ];
        if (!$this->http_net) {
            import('ORG.Net.Http');
            $this->http_net = new Http();
        }
        $return =  $this->http_net->curlPostOwnWithHeader($url, json_encode($postData), ['Content-Type: application/json'],$this->time_out_time);
//        $return =  $this->http_net->curlPutOwnWithHeader($url, json_encode($postData), ['Content-Type: application/json']);

        if ($return) {
            $return_msg = json_decode($return,true);
            if ($return_msg['code']==0) {
                // 订阅对应人脸时间通知
                fdump(['>订阅成功>'.__LINE__,$url,$postData,$device_id,$a3_device_id],'api/log/'.date('Ymd').'/a3/face_a3_subscription_log',true);
                $this->face_a3_device_subscription($device_id,$a3_device_id);
                return true;
            } elseif($return_msg['code']==30005) {
                $return1 =  $this->http_net->curlPutOwnWithHeader($url, json_encode($postData), ['Content-Type: application/json'],$this->time_out_time);
                $return1_msg = json_decode($return1,true);
                if ($return['code']==0) {
                    // 订阅对应人脸时间通知
                    fdump(['>订阅成功>'.__LINE__,$url,$postData,$device_id,$a3_device_id],'api/log/'.date('Ymd').'/a3/face_a3_subscription_log',true);
                    $this->face_a3_device_subscription($device_id,$a3_device_id);
                    return true;
                } else {
                    fdump(['>编辑订阅失败>'.__LINE__,$url,$sign,$postData,$return1],'api/log/'.date('Ymd').'/a3/face_a3_subscription_err_log',true);
                }
            } else {
                fdump(['>订阅失败>'.__LINE__,$url,$sign,$postData,$return],'api/log/'.date('Ymd').'/a3/face_a3_subscription_err_log',true);
                return true;
            }
        } else {
            fdump(['>订阅失败>'.__LINE__,$url,$sign,$postData,$return],'api/log/'.date('Ymd').'/a3/face_a3_subscription_err_log',true);
            return true;
        }
    }

    // 订阅上报事件
    public $a3_topics = [
        'FaceDetection' => '人脸检测',
        'FaceAlignment' => '人脸比中事件',
        'FaceStranger' => '人脸未比中事件',
        'HumanoidDetection' => '人形检测',
        'IVAFaceDetect' => '人脸识别',
    ];

    // 订阅人脸事件报警通知
    public function face_a3_device_subscription($device_id,$a3_device_id='') {
        $d_house_face_device = M('House_face_device');
        if (!$a3_device_id) {
            $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
            $a3_device_id = $house_face_device_info['a3_device_id'];
        }
        $token = $this->a3_token;
        $key = $this->a3_key;
        $url = $this->base_face_a3_url  . "/dms/device/subscription/{$a3_device_id}?token=".$token;
        $pwd = base64_decode($key);

// "SSBPeopelFell": 人员倒地 "CrossRegionDetection": 区域入侵报警 "CrossFenceDetection": 围栏警戒 "BZSDensityAlarm": 区域内人数统计
// "IVAFaceDetect": 人脸识别 "appEventSafetyHat": 没戴安全帽 "appEventWorkClothes": 没穿工服 "appEventArmband": 没戴袖标 "appEventSmokefire": 火焰报警
// "appEventInsulatingGloves": 没戴手套 "appEventMeterdata": 仪表数据 "AlarmLocal":  DI报警 "AlarmAILocal":  AI报警 "AlarmSmartSensorLocal": 智能传感器报警
// "VideoBlind": 视频遮掩报警 "VideoLoss": 视频丢失报警 "VideoMotion": 视频动检报警  "FireDetection"	火警事件报警 "ObjectDetection"	对象检测报警
// "DressCodeDetection"	着装规范报警 "AnimalsDetection"	动物检测报警 "CharacterDetection"	字符检测报警 “FaceAlignment”人脸比中事件
//“FaceStranger”人脸未比中事件 “HumanoidDetection” 人形检测 “MotorDetection” 机动车 “NonMotorDetection” 非机动车 “FaceDetection” 人脸检测
        $topics = 'FaceDetection,FaceAlignment,FaceStranger,HumanoidDetection,IVAFaceDetect';

        $sign = json_encode(['signCreateTime' => date('YmdHis'), 'topics'=>$topics, 'deviceId'=> intval($a3_device_id)]);
        $sign_pwd = openssl_encrypt ($sign, 'DES-ECB', $pwd);
        $postData = [
            'topics'=>$topics,
            'sign' => $sign_pwd
        ];
        if (!$this->http_net) {
            import('ORG.Net.Http');
            $this->http_net = new Http();
        }
//        $return =  $this->http_net->curlPostOwnWithHeader($url, json_encode($postData), ['Content-Type: application/json']);
        $return =  $this->http_net->curlPostOwnWithHeader($url, json_encode($postData), ['Content-Type: application/json'],$this->time_out_time);

        if ($return) {
            $return_msg = json_decode($return,true);
            if ($return_msg['code']==0) {
                // 订阅对应人脸时间通知成功
                fdump(['>订阅成功>'.__LINE__,$url,$postData,$device_id,$a3_device_id],'api/log/'.date('Ymd').'/a3/face_a3_device_subscription_log',true);
                return true;
            } elseif($return_msg['code']==100018) {
                $return1 =  $this->http_net->curlPutOwnWithHeader($url, json_encode($postData), ['Content-Type: application/json'],$this->time_out_time);
                $return1_msg = json_decode($return1,true);
                if ($return1_msg['code']==0) {
                    fdump(['>订阅成功>'.__LINE__,$url,$postData,$device_id,$a3_device_id],'api/log/'.date('Ymd').'/a3/face_a3_device_subscription_log',true);
                    return true;
                } else {
                    fdump(['>编辑订阅失败>'.__LINE__,$url,$sign,$postData,$device_id,$a3_device_id,$return1],'api/log/'.date('Ymd').'/a3/face_a3_device_subscription_err_log',true);
                }
            } else {
                dump(['>订阅失败>'.__LINE__,$url,$sign,$postData,$device_id,$a3_device_id,$return],'api/log/'.date('Ymd').'/a3/face_a3_device_subscription_err_log',true);
                return true;
            }
        } else {
            fdump(['>订阅失败>'.__LINE__,$url,$sign,$postData,$return],'api/log/'.date('Ymd').'/a3/face_a3_device_subscription_err_log',true);
            return true;
        }
    }
    // 获取设备状态
    public function get_a3_device($device_id,$a3_device_id='') {
        // 当设备连接到平台就变成了1，但是1设备还不可用，只有变成2才是可以用的
        $d_house_face_device = M('House_face_device');
        if (!$a3_device_id) {
            $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
            $a3_device_id = $house_face_device_info['a3_device_id'];
        }
        if (!$a3_device_id) {
            $return_msg = [
                'data' => [
                    'status' => 99,
                    'msg' => '请先同步该设备',
                ]
            ];
            return $return_msg;
        }
        $token = $this->a3_token;
        $key = $this->a3_key;
        $url = $this->base_face_a3_url  . "/dms/device/{$a3_device_id}?token=".$token;
        $pwd = base64_decode($key);
        $sign = json_encode(['deviceId' => intval($a3_device_id), 'signCreateTime' => date('YmdHis')]);
        $sign_pwd = openssl_encrypt ($sign, 'DES-ECB', $pwd);
        $headers = [
            'sign' => urlencode($sign_pwd)
        ];
        if (!$this->http_net) {
            import('ORG.Net.Http');
            $this->http_net = new Http();
        }
        $url .='&sign='.urlencode($sign_pwd);
        $return =  $this->http_net->curlGet($url, 60, $headers);
        $return_msg = json_decode($return,true);
        if($return_msg['data']['online']){
            $data = ['device_status'=>1];
            //todo 故障解除 A3
            D('House_maintenan')->normalLog(1,$device_id);
        }else{
            $data = ['device_status'=>2];
            //todo 故障通知 A3
            D('House_maintenan')->faultLog(1,$device_id,'设备不在线');
        }
        D('House_face_device')->where(['device_id' =>$device_id])->data($data)->save();

        return $return_msg;
    }
    // 删除设备
    public function del_a3_device($device_id,$a3_device_id='') {
        $d_house_face_device = M('House_face_device');
        if (!$a3_device_id) {
            $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
            $a3_device_id = $house_face_device_info['a3_device_id'];
        }
        if (!$a3_device_id) {
            fdump(['>删除设备参数不齐>'.__LINE__,$device_id,$a3_device_id,$house_face_device_info],'api/log/'.date('Ymd').'/a3/del_a3_device_err_log',true);
            return false;
        }
        $token = $this->a3_token;
        $key = $this->a3_key;
        $url = $this->base_face_a3_url  . "/dms/device/{$a3_device_id}?token=".$token;
        $pwd = base64_decode($key);
        $sign = json_encode(['signCreateTime' => date('YmdHis'),  'deviceId' => intval($a3_device_id)]);
        $sign_pwd = openssl_encrypt ($sign, 'DES-ECB', $pwd);
        $postData = [
            'sign' => $sign_pwd
        ];
        import('ORG.Net.Http');
        $http = new Http();
        $return = Http::curlDeleteOwnWithHeader($url, json_encode($postData), ['Content-Type: application/json'], 100);
        $return_msg = json_decode($return,true);
        if ($return_msg['code']==0) {
            $d_house_face_device->where(array('device_id' => $device_id))->setField('device_status', 4);
            fdump(['>设备删除成功>'.__LINE__,$url,$return],'api/log/'.date('Ymd').'/a3/del_a3_device_log',true);
        } else {
            fdump(['>设备删除失败>'.__LINE__,$url,$return],'api/log/'.date('Ymd').'/a3/del_a3_device_err_log',true);
        }
        return $return_msg;
    }

    // a3同步设备时间
    public function a3synchronize_time($device_id,$a3_device_id='') {
        $d_house_face_device = M('House_face_device');
        if (!$a3_device_id) {
            $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
            $a3_device_id = $house_face_device_info['a3_device_id'];
        }
        $token = $this->a3_token;
        $key = $this->a3_key;
        $url = $this->base_face_a3_url  . "/dms/device/{$a3_device_id}/time/synchronize?token=".$token;
        $pwd = base64_decode($key);
        $time=date('Y-m-d H:i:s',time());
        $sign = json_encode(['time'=>$time,'tolerance'=>1,'signCreateTime' => date('YmdHis'), 'deviceId' => intval($a3_device_id)]);
        $sign_pwd = openssl_encrypt ($sign, 'DES-ECB', $pwd);
        $postData = [
            'time'=>$time,
            'tolerance'=>1,
            'sign' => $sign_pwd,
        ];
        if (!$this->http_net) {
            import('ORG.Net.Http');
            $this->http_net = new Http();
        }
        $return =  $this->http_net->curlPutOwnWithHeader($url, json_encode($postData), ['Content-Type: application/json'],$this->time_out_time);
        fdump(['同步数据=='.__LINE__,$url,$sign,$postData,$return,$a3_device_id],'api/log/'.date('Ymd').'/a3/a3synchronize_time_log',true);
        if ($return) {
            $return_msg = json_decode($return,true);
            if ($return_msg['code']==0) {
                return true;
            } else {
                fdump(['同步时间失败=='.__LINE__,$url,$sign,$postData,$return,$a3_device_id],'api/log/'.date('Ymd').'/a3/a3synchronize_time_err_log',true);
                return true;
            }
        } else {
            fdump(['同步时间失败=='.__LINE__,$url,$sign,$postData,$return,$a3_device_id],'api/log/'.date('Ymd').'/a3/a3synchronize_time_err_log',true);
            return true;
        }
        return true;
    }
}