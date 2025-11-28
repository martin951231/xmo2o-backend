<?php
class Face_doorAction extends BaseAction{
    public $check_site_url = 'https://o2o-service.pigcms.com';  // 校验门禁设备id接口的域名


    //	人脸识别门禁设备列表
    public function door_list(){
        //人脸识别门禁设备设置-查看 权限
        if (!in_array(285, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $hfd = D('House_face_device');
        $type = isset($_GET['device_type']) ? $_GET['device_type'] : '';

        $village_id          = $this->house_session['village_id'];
        $where['village_id'] = $village_id;
        if(!empty($type)){
            $where['device_type'] = $type;
        }
        $page		=	I('page',1);
        $page_count	=	I('page_count',20);
        /*
        $expireTime = 600;
        $uniqueId = 'doorList_' . md5(json_encode([
            'where' => $where,
            'page' => $page,
            'page_count' => $page_count,
        ],JSON_UNESCAPED_UNICODE));
        $aDoorS = S($uniqueId);
        */
        $aDoorS='';
        $aDoor	=	$hfd->get_face_door_list($where,$page,$page_count);
        $device_type_value = $hfd->device_type_value;
        $hasThirdChnNum = false;
        if ($aDoor['door_list']) {

            $group_where = [
                'group_type'    => 0,
                'group_type_id' => $village_id,
            ];
            $a3GroupDeviceIdArr = M('Face_a3_group')->where($group_where)->getField('device_id, device_id, group_id', true);
            
            foreach($aDoor['door_list'] as &$val) {
                if (3==$val['device_type'] && $a3GroupDeviceIdArr && isset($a3GroupDeviceIdArr[$val['device_id']])) {
                    $val['is_a3_group'] = 1;
                } else {
                    $val['is_a3_group'] = 0;
                }
                if (isset($device_type_value[$val['device_type']])) {
                    $val['device_type_txt'] = $device_type_value[$val['device_type']];
                } else {
                    $val['device_type_txt'] = '';
                }
                if (intval($val['device_type']) == 30 && $val['thirdChnNum']) {
                    $hasThirdChnNum = true;
                }
            }
        }
        $this->assign('hasThirdChnNum', $hasThirdChnNum);
        $this->assign('door_list',$aDoor['door_list']);
        $this->assign('pagebar',$aDoor['pagebar']);

        $condition_count_today['log_business_id'] = $village_id;
        // todo 过滤蓝牙开门记录
        $condition_count_today['log_from'] = array('in',[1,2,3,31,45]);
        $condition_count_today['log_time'] = array('egt',strtotime(date('Y-m-d 00:00:00')));
        /*
        $uniqueId1 = 'today_count_' . md5(json_encode([
                'condition_count_today' => $condition_count_today,
            ], JSON_UNESCAPED_UNICODE));
        $today_countS = S($uniqueId1);
        */
        $today_count = M('House_user_log')->where($condition_count_today)->count();
        $this->assign('today_count',$today_count);
        $condition_count_yesterday['log_business_id'] = $village_id;
        // todo 过滤蓝牙开门记录
        $condition_count_yesterday['log_from'] = array('in',[1,2,3,31,45]);
        $condition_count_yesterday['log_time'] = array('between',strtotime(date("Y-m-d 00:00:00",strtotime("-1 day"))).','.strtotime(date('Y-m-d 00:00:00')));
        /*
        $uniqueId2 = 'yesterday_count_' . md5(json_encode([
                'condition_count_yesterday' => $condition_count_yesterday,
            ], JSON_UNESCAPED_UNICODE));
        $yesterday_countS = S($uniqueId2);
        */
        $yesterday_count = M('House_user_log')->where($condition_count_yesterday)->count();

        $this->assign('yesterday_count',$yesterday_count);
        $this->assign('device_type_value',$device_type_value);

        $this->assign('device_type',$type);

        $thirdProtocolArr = [$hfd::HIK_YUNMO_WAIBU, $hfd::HIK_DEVICE_GATEWAY, $hfd::HIK_DEVICE_GATEWAY_CUSTOM];
        $whereHasVisitorAuth = [
            'village_id' => $village_id, 'thirdProtocol' => ['in', $thirdProtocolArr], 'is_del' => 0
        ];
        $hasDeviceInfo = $hfd->where($whereHasVisitorAuth)->field('device_id, village_id, thirdProtocol')->find();
        if ($hasDeviceInfo) {
            $deviceVisitorAuth = [
                'title' => '访客访问非业主设备权限',
                'toPage' => $this->config['site_url'] . '/v20/public/platform/#/village/village.device/visitorAuthList',
            ];
            $this->assign('deviceVisitorAuth', $deviceVisitorAuth);
        }
        $is_func_cloudmrsqfw_special=D('Config_customization')->getFuncCloudmrsqfwSpecial();
        $lilin_third_dev_api2=0;
        $have_lilin_third_dev=0;
        if($is_func_cloudmrsqfw_special){
            $whereArr=array('village_id'=>$village_id);
            $village_data = M('House_village')->where($whereArr)->find();
            if($village_data && !empty($village_data['lilin_pf_client_id']) && !empty($village_data['lilin_pf_client_secret']) && !empty($village_data['lilin_village_no'])){
                $lilin_third_dev_api2=1;
                $whereArr=array('village_id'=>$village_id,'third_dev_source'=>'lilin_pf','third_dev_id'=>array('neq',''),'is_del'=>array('lt',4));
                $lilin_third_dev=$hfd->where($whereArr)->find();
                if($lilin_third_dev){
                    $have_lilin_third_dev=1;
                }
            }
        }
        $this->assign('lilin_third_dev_api2',$lilin_third_dev_api2);
        $this->assign('have_lilin_third_dev',$have_lilin_third_dev);
		$this->display();
	}

    //	添加设备
    public function door_add(){
        $hfd = D('House_face_device');
        if($_POST){
            //门禁设置-修改 权限
            if (!in_array(497, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');exit;
            }
            $data = array();
            $data['village_id']	= $this->house_session['village_id'];
            if ($_POST['floor_id']) {
                if ($_POST['floor_arr'] && is_array($_POST['floor_arr'])) {
                    $floor_id = implode(",",$_POST['floor_arr']);
                    $data['floor_id'] = $floor_id;
                } else {
                    $data['floor_id'] = $_POST['floor_id'];
                }
            }
            if ($_POST['brand_type']) {
                $data['device_brand'] = $_POST['brand_type'];
            }
            $thirdProtocol = 0;
            if (isset($_POST['thirdProtocol']) && trim($_POST['thirdProtocol'])) {
                $data['thirdProtocol'] = trim($_POST['thirdProtocol']);
                $thirdProtocol = trim($_POST['thirdProtocol']);
            }
            $hikGateKeyVillage = $hfd::HIK_DEVICE_GATEWAY_CUSTOM;
            $hikGateKey = $hfd::HIK_DEVICE_GATEWAY;
            if (trim($_POST['cloud_code'])) {
                $data['cloud_code'] = trim($_POST['cloud_code']);
            }else{
                if($_POST['device_type'] == 5 || $_POST['device_type'] == 6)
                {
                    if (!in_array($thirdProtocol, [$hikGateKey, $hikGateKeyVillage])) {
                        $this->error('请填写设备验证码');die();
                    }
                }
            }
            if ($_POST['device_brand_type']){
                $data['brand_type'] = $_POST['device_brand_type'];
            }
            if ($_POST['remark']) {
                $data['remark'] = $_POST['remark'];
            }
            if ($_POST['device_param']) {
                $data['device_param'] = $_POST['device_param'];
            }
            if ($_POST['device_name']) {
                $data['device_name'] = $_POST['device_name'];
            }
            if (isset($_POST['device_direction'])) {
                $data['device_direction'] = $_POST['device_direction'];
            }
            if (trim($_POST['device_sn'])) {
				$data['device_sn'] = trim($_POST['device_sn']);
                $blockWhitelistDeviceTypeArr = $hfd->blockWhitelistDeviceTypeArr;
                if(in_array($_POST['device_type'],$blockWhitelistDeviceTypeArr))
                {
                    // a5和a6和d1不进行拦截
                }else {
                    $url = $this->check_site_url . '/face_door_check.php';
                    $params['device_id'] = $data['device_sn'];
                    $check_info = httpRequest($url, 'POST', $params);
                    $msg = $check_info[1];
                    if ('ok' != $msg) {
                        fdump_api(['operate' => 'add', '_POST' => $_POST, 'url' => $url, 'params' => $params, 'check_info' => $check_info, 'msg' => $msg], 'face/door_add_log', true);
                        $this->error('您添加的人脸识别门禁设备没有记录，请联系管理员');
                        exit;
                    }
                }
            }elseif (26 == $_POST['device_type']){
                $data['device_sn'] = trim($_POST['device_sn_26']);
                $device_info=M('Face_d6_device')->where(['device_sn'=>$data['device_sn']])->find();
                if (empty($device_info)){
                    $this->error('您添加的人脸识别门禁设备未配置，请联系管理员');
                    exit;
                }
                $data['device_ip']=$device_info['device_ip'];
                //  5 6 8 21 22  23 24 61  设备拦截白名单 大部分是定制 海康和自己的A185
                $blockWhitelistDeviceTypeArr = $hfd->blockWhitelistDeviceTypeArr;
                if(!in_array($_POST['device_type'],$blockWhitelistDeviceTypeArr))
                {
                    $url = $this->check_site_url . '/face_door_check.php';
                    $params['device_id'] = $data['device_sn'];
                    $check_info = httpRequest($url, 'POST', $params);
                    $msg = $check_info[1];
                    if ('ok' != $msg) {
                        $this->error('您添加的人脸识别门禁设备没有记录，请联系管理员');
                        exit;
                    }
                }
            } else {
                $this->error('请您添加设备ID');exit;
            }
            $single_id = I('single_id');
            $single_id = explode("_", $single_id);
            if ($single_id[0]=='door') {
                $data['floor_id'] = -1;
                $data['public_area_id'] = $single_id[1];
            } elseif($data['floor_id']>0) {
                $data['public_area_id'] = 0;
            }
            if (isset($_POST['device_score'])) {
                $data['device_score'] = $_POST['device_score'];
            }
            if (isset($_POST['device_alive'])) {
                $data['device_alive'] = $_POST['device_alive'];
            }
            if (isset($_POST['device_type'])) {
                $data['device_type'] = $_POST['device_type'];
            }
            if (isset($_POST['device_alive'])) {
                $data['device_alive'] = $_POST['device_alive'];
            }
            if (isset($_POST['device_score'])) {
                $data['device_score'] = $_POST['device_score'];
            }
            if (isset($_POST['device_model'])) {
                $data['device_model'] = $_POST['device_model'];
            }
            if (isset($_POST['layer_id'])) {
                $data['layer_id'] = $_POST['layer_id'];
            }
            if (isset($_POST['device_layer'])) {
                $data['device_layer'] = $_POST['device_layer'];
            }
            if (isset($_POST['network_method']) && trim($_POST['network_method'])) {
                $data['network_method'] = $_POST['network_method'];
            }
            if (isset($_POST['network_operator']) && trim($_POST['network_operator'])) {
                $data['network_operator'] = $_POST['network_operator'];
            }
            $defaultProtocol = $hfd->defaultProtocol;
            if (isset($defaultProtocol[$_POST['device_type']])&&!$data['thirdProtocol']) {
                $this->error('设备协议必须要选择');exit;
            }
            if (isset($_POST['thirdLoginName']) && trim($_POST['thirdLoginName'])) {
                $data['thirdLoginName'] = $_POST['thirdLoginName'];
            }
            if (isset($_POST['thirdLoginPassword']) && trim($_POST['thirdLoginPassword'])) {
                $data['thirdLoginPassword'] = $_POST['thirdLoginPassword'];
            }
            if ($thirdProtocol == $hfd::DH_YUNRUI && (!$data['thirdLoginName'] || !$data['thirdLoginPassword'])) {
                $data['thirdLoginName']     = 'admin';
                $data['thirdLoginPassword'] = 'admin123';
            }
            if (isset($_POST['thirdChnNum']) && trim($_POST['thirdChnNum'])) {
                $data['thirdChnNum'] = $_POST['thirdChnNum'];
            }
            $isup_version = isset($_POST['isup_version']) ? trim($_POST['isup_version']) : '';
            if (in_array($thirdProtocol, [$hikGateKey, $hikGateKeyVillage]) && $isup_version == 'ehomeV5' && (!isset($data['thirdLoginPassword']) || !$data['thirdLoginPassword'])) {
                $this->error('请填写登录密钥');exit;
            }
            if (in_array($thirdProtocol, [$hikGateKey, $hikGateKeyVillage]) && isset($data['device_name']) && strlen($data['device_name']) > 32) {
                $this->error('设备名称，最多只能包含32个字符（1个汉字3个字符）');exit;
            }
            if (1==$data['device_model'] && !$data['device_layer']) {
                $this->error('梯控类型必须选择安装楼层');exit;
            }
            if (1==$data['device_model'] && !$data['floor_id']) {
                $this->error('梯控类型必须为单元门');exit;
            }
//			if($_POST['location']){
//				$locationArr = explode(',',trim($_POST['location']));
//				$data['lng'] = $locationArr[0];
//				$data['lat'] = $locationArr[1];
//			}
            if (!$data['public_area_id'] && !$data['floor_id']) {
                $this->error('请选择设备具体对应位置，不能选择全部楼栋或者全部单元');exit;
            }
            //A8 人脸门禁 start
            if($_POST['device_type'] == 8) {
                if(isset($_POST['image_quality']))
                {
                    $data['image_quality'] = $_POST['image_quality']?$_POST['image_quality']:2;
                }
                if(isset($_POST['whitevalue'])) {
                    $data['whitevalue'] = $_POST['whitevalue']?$_POST['whitevalue']:80;
                }
                if(isset($_POST['mapvalue'])){
                    $data['mapvalue'] = $_POST['mapvalue']?$_POST['mapvalue']:60;
                }
                if(isset($_POST['recog_mode_db'])){
                    $data['recog_mode_db'] = $_POST['recog_mode_db'];
                }
                if(isset($_POST['recog_mode_ic'])){
                    $data['recog_mode_ic'] = $_POST['recog_mode_ic'];
                }
            }
            //A8 人脸门禁 end
            if (empty($data)) {
                $this->error('修改设备失败！请检查是否有添加内容');exit;
            }
            $info = $hfd->where(array('device_sn'=>$data['device_sn'],'is_del'=>array('lt',4)))->find();
            if (!empty($info)) {
                $this->error('当前编号设备已经被添加');exit;
            }
            if ($_POST['floor_id']<=0) {
                $name_info = $hfd->where(array('device_name'=>$data['device_name'],'village_id' => $data['village_id'],'is_del'=>0))->find();
                if (!empty($name_info)) {
                    $this->error('当前设备名称已经被添加，请更换名称');exit;
                }
            }
            if ((5==$data['device_type'] || 6==$data['device_type'])) {
                if(!$data['cloud_code']) {
                    if (!in_array($thirdProtocol, [$hikGateKey, $hikGateKeyVillage])) {
                        $this->error('请填写当前设备对应的设备验证码');die();
                    }
                }
                $data['is_visual'] = $_POST['is_visual'];
            }
            $data['add_time'] = time();
            $data['last_time'] = time();
            if (7==$_POST['device_type']) {
                // a7默认开启活体检测
                $data['device_alive'] = 1;
                // 不填写  脸比对确性分数 默认 80分
                if (!$data['device_score']) {
                    $data['device_score'] = 80;
                }
            }
            elseif (21==$_POST['device_type']) {
                // a21默认开启活体检测
                $data['device_alive'] = 1;
                // 不填写  脸比对确性分数 默认 80分
                if (!$data['device_score']) {
                    $data['device_score'] = 80;
                }
            }
            if(22 == $_POST['device_type']){
                if($_POST['device_domain'] || $_POST['device_port']) {
                    $data['device_domain'] = trim($_POST['device_domain']).':'.trim($_POST['device_port']);
                }
            }
            //todo A185门禁
            if(61 == $_POST['device_type']){
                if(empty(intval($_POST['a185_close_time']))){
                    $this->error('请输入门禁关门时间');exit;
                }
                if(empty($_POST['a185_super_password'])){
                    $this->error('请输入门禁管理密码');exit;
                }
                $_POST['a185_super_password']=trim($_POST['a185_super_password']);
                if(strlen($_POST['a185_super_password']) < 6){
                    $this->error('门禁管理密码最少6个字符');exit;
                }
                if(empty($_POST['a185_admin_password'])){
                    $this->error('请输入门禁开门密码');exit;
                }
                $_POST['a185_admin_password']=trim($_POST['a185_admin_password']);
                if(strlen($_POST['a185_admin_password']) < 6){
                    $this->error('门禁开门密码最少6个字符');exit;
                }
                if($_POST['a185_super_password'] == $_POST['a185_admin_password']){
                    $this->error('门禁管理密码和门禁开门密码不可相同');exit;
                }
                if($_POST['b_time_left'] || $_POST['b_time_right']){
                    if(strtotime($_POST['b_time_left']) > strtotime($_POST['b_time_right'])){
                        $this->error('白天音量时间区间不符');exit;
                    }
                }
                if($_POST['w_time_left'] || $_POST['w_time_right']){
//                    if(strtotime($_POST['w_time_left']) > strtotime($_POST['w_time_right'])){
//                        $this->error('晚上音量时间区间不符');exit;
//                    }
                }
                if($_POST['is_tele_card'] == 1){
                    if(empty($_POST['tele'])){
                        $this->error('请输入手机号');exit;
                    }
                }
                $data['a185_close_time']=intval($_POST['a185_close_time']);
                $data['a185_super_password']='*'.$_POST['a185_super_password'];
                $data['a185_admin_password']='*'.$_POST['a185_admin_password'];
                $data['a185_restart_time']=$_POST['a185_restart_time'];
                $data['a185_volume']=serialize(array(
                    'day'=>array(
                        'time'=>array($_POST['b_time_left'],$_POST['b_time_right']),
                        'size'=>$_POST['b_volume']
                    ),
                    'night'=>array(
                        'time'=>array($_POST['w_time_left'],$_POST['w_time_right']),
                        'size'=>abs($_POST['w_volume'])
                    )
                ));
                $data['a185_tele_card']=serialize(array(
                    'is_tele_card'=>$_POST['is_tele_card'],
                    'tele'=>trim($_POST['tele'])
                ));
            }
            if(isset($_POST['internet_card'])){
                $data['internet_card'] = trim($_POST['internet_card']);
            }
            if($_POST['device_ip']){
                $data['device_ip'] = $_POST['device_ip'];
            }
            if (30 == $data['device_type']) {
                $data['device_status'] = 2;// 默认离线 有通讯了再变更
            }
            $device_id = $hfd->data($data)->add();
            if($device_id){
                $db_house_face_device_about = M('House_face_device_about');
                $about = [];
                if (isset($_POST['isup_version']) && trim($_POST['isup_version'])) {
                    $gatewayinfo = ['isup_version'=>trim($_POST['isup_version'])];
                    $about['device_id'] = $device_id;
                    $about['gatewayinfo'] = json_encode($gatewayinfo, JSON_UNESCAPED_UNICODE);
                    $about['add_time'] = time();
                    $db_house_face_device_about->add($about);
                }
                // 记录日志
                $orderGroupId = md5(uniqid().$_SERVER['REQUEST_TIME']);
                $param = [
                    'log_name' => 'adminAddDevice','logType' => 'houseAdmin','log_status' => 5,'orderGroupId' => $orderGroupId,
                    'log_content' => '添加设备','device_id' => $device_id,'device_sn' => $data['device_sn']?$data['device_sn']:'',
                    'device_type' => $data['device_type']?$data['device_type']:'','village_id' => $data['village_id']?$data['village_id']:0,
                    'addTime' => $data['add_time']?$data['add_time']:time(),
                ];
                try {
                    D('House_face_device_log')->addLog($param);
                } catch( \Exception $e) {
                    // 记录日志报错
                    fdump_api(['日志记录[添加设备]出错'.__LINE__.'方法'.__FUNCTION__,$param,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
                }
                if (30 == intval($data['device_type'])) {
                    // 260云 将设备相关信息加入指令中等候
                    $data['device_id'] = $device_id;
                    D('House_village_device_cmd')->addOrUpdateDeviceCmd($data);
                }
                $otherParam = [
                    'orderGroupId' => $orderGroupId,
                    'operatorType' => 'admin',
                ];
                $data['device_id'] = $device_id;
                $defaultProtocolArr = array_values($defaultProtocol);
                if ($thirdProtocol && in_array($thirdProtocol, $hfd->newProtocol)) {
                    // 目前仅支持 海康和大华新协议逻辑
                    $models = '\app\community\model\service\FaceDeviceService/handleDevice';
                    $param = [
                        'device_sn' => $data['device_sn'],
                        'param'     => [
                            'village_id'    => $data['village_id'],
                            'device_id'     => $device_id,
                            'operation'     => 'addDevice',
                            'device_ip'     => $data['device_ip'],
                            'orderGroupId'  => $orderGroupId,
                            'thirdProtocol' => $_POST['thirdProtocol'],
                            'deviceType'    => 'face',
                        ]
                    ];
                    $thirdAdd = invoke_v20_service($models,$param);
                } elseif (2==$_POST['device_type']) {
                    D('House_village_face_door')->face_door_add($device_id, $data);
                }
                elseif (3==$_POST['device_type']) {
                    if (D('House_village_face_door')->base_face_a3_url && D('House_village_face_door')->a3_token && D('House_village_face_door')->a3_key) {
                        D('House_village_face_door')->face_a3_door_add($device_id, $data);
                    } else {
                        $this->error('同步失败，未进行配置，请前往 社区配置-A3人脸识别门禁 配置！');exit;
                    }
                }
                elseif (4==$_POST['device_type']) {
//                    D('Face_door_a4_service')->faceDeviceSynchro($device_id, $data, 0);
                    D('Face_door_a4_service')->faceDeviceSynchro($device_id, $data, 1);
                }
                elseif (5==$_POST['device_type'] || 6==$_POST['device_type']){
                    if (!$thirdProtocol || in_array($_POST['thirdProtocol'],$defaultProtocolArr)) {
                        D('Face_door_a5_service')->addEquipment($device_id, $data, $otherParam);
                    }
                }
                elseif (7==$_POST['device_type']){
                    D('Face_door_a7_service')->face_a7_door_add($device_id, $data);
                }elseif (8==$_POST['device_type']){
                    $data['imageQuality'] = $data['image_quality'];
                    $data['recogModeDB'] = $data['recog_mode_db'];
                    $data['recogModeIC'] = $data['recog_mode_ic'];
                    unset($data['image_quality']);
                    unset($data['recog_mode_db']);
                    unset($data['recog_mode_ic']);
                    D('Face_local_area_device')->addDevice($device_id, $data);
                }elseif (21==$_POST['device_type']){
                    D('Face_door_d1_service')->face_d1_door_syn($device_id, $data, 'add_device',[]);
                }
                elseif (23==$_POST['device_type']){
                    D('Face_d3_request_record')->d3_set_match_score_record($device_id, $data, 'add_device',[]);
                }

                elseif (24==$_POST['device_type']) {
                    //todo D4智能门禁 新增设备
                    D('Face_d4_request_record')->d4_device($device_id, 1);

                }elseif (61==$_POST['device_type']){
                    //todo 新增a185设备
                    //更新配置接口
                    D('Face_a185_request_record')->updateConfig($device_id, $data,$this->config);
                    //更新广设备
                    D('Face_a185_request_record')->updateAdvert($device_id, $data);
                }elseif (88==$_POST['device_type']){
                    //todo 大华人脸门禁
                    if (!$thirdProtocol || in_array($_POST['thirdProtocol'],$defaultProtocolArr)) {
                        D('Dahua_service')->face_authorization($data['device_name'], $data['device_ip'], $data['village_id'], $device_id, $data['floor_id']);
                    }
                }elseif (26==$_POST['device_type']) {
                    M('Face_d6_device')->where(['device_sn'=>$data['device_sn']])->data(['status'=>1])->save();
                    //todo D6智能门禁 新增设备
                    D('Face_d6_request_record')->d6_device($device_id, 2);
                } elseif (31 == $_POST['device_type']) {
                    // 西函门禁
                    $db_house_face_device_cmd = M('House_face_device_cmd');
                    $nowTime = time();
                    // 批量操作配置下发设备
                    $cmdParams = [
                        'village_id' => $data['village_id'], 'cmd_name' => 'configCentre.setConfig', 'add_time' => $nowTime,
                        'exec_status' => 0, 'device_id' => $device_id, 'device_sn' => $data['device_sn'], 
                        'device_type' => $_POST['device_type'], 'device_type_str' => 'xihan', 
                        'relate_id' => $device_id, 'relate_type' => 'house_face_device'
                    ];
                    $cmd_data = [
                        'method' => 'configCentre.setConfig',
                        'params' => [
                            'name' => 'MachineGlobal', 'content' => [['MachineName' => $data['device_name']]],
                        ],
                    ];
                    $dataCmd = $cmdParams;
                    $dataCmd['cmd_data'] = json_encode($cmd_data,JSON_UNESCAPED_UNICODE);
                    $db_house_face_device_cmd->add($dataCmd);
                    
                    $cmd_data = [
                        'method' => 'configCentre.setConfig',
                        'params' => [
                            'name' => 'DoorMode', 'content' => [['Mode' => 'Control']],
                        ],
                    ];
                    $dataCmd = $cmdParams;
                    $dataCmd['cmd_data'] = json_encode($cmd_data,JSON_UNESCAPED_UNICODE);
                    $db_house_face_device_cmd->add($dataCmd);
                    
                    $device_score = isset($data['device_score']) && $data['device_score'] > 0 ? intval($data['device_score']) : 80;
                    $cmd_data = [
                        'method' => 'configCentre.setConfig',
                        'params' => [
                            'name' => 'FaceRecogniseRule', 'content' => [['GroupID' => 1, 'SearchThreshold' => $device_score], 'execute' => 'delaySave'],
                        ],
                    ];
                    $dataCmd = $cmdParams;
                    $dataCmd['cmd_data'] = json_encode($cmd_data,JSON_UNESCAPED_UNICODE);
                    $db_house_face_device_cmd->add($dataCmd);
                    
                    $cmd_data = [
                        'method' => 'global.setTime',
                        'params' => [
                            'time' => date('Y-m-d H:i:s', $nowTime),
                        ],
                    ];
                    $dataCmd = $cmdParams;
                    $dataCmd['cmd_data'] = json_encode($cmd_data,JSON_UNESCAPED_UNICODE);
                    $db_house_face_device_cmd->add($dataCmd);
                }
                $this->success('新增设备成功！',U('door_list',array('device_type' => $_GET['deviceType'])));exit;
            }else{
                $this->error('新增设备失败！');exit;
            }
        }
        else{
            //门禁设置-查看 权限
            if (!in_array(285, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');exit;
            }
            $type = isset($_GET['device_type']) ? $_GET['device_type'] : '';

      
            $device_type_info = $hfd->device_type_info;
            if (1!=$this->config['show_facedoor_d1']) {
                unset($device_type_info[8]);
            }
//            unset($device_type_info[12]);

            if($type == 29){
                $device_type_info = [['device_type' => 29, 'name' => '朵普智能门禁机']];
            }

            $this->assign('device_type_info',$device_type_info);

            $brand_list = D('HardwareBrand')->getBrand(1);
            $this->assign('device_brand',$brand_list);

            $this->assign('device_type',$_GET['device_type']);

            $brandHaikangCodeTip = $hfd->brandHaikangCodeTip;
            $this->assign('brandHaikangCodeTip',$brandHaikangCodeTip);
            $thirdProtocol = $hfd->thirdProtocol;

            $village_id = $this->house_session['village_id'];
            $gatewayInfo = M('House_device_gateway')->where(['village_id' => $village_id, 'is_open'=>1])->find();
            //过滤没有启用配置的海康网关
            $hikGateKeyVillage = $hfd::HIK_DEVICE_GATEWAY_CUSTOM;
            if (empty($gatewayInfo)){
                unset($thirdProtocol['brand_haikang'][$hikGateKeyVillage]);
            }
            if (empty($this->config['hikGatewayHost']) || empty($this->config['hikGatewayProtocol']) || empty($this->config['hikGatewayPort'])|| empty($this->config['hikGatewayUserName'])|| empty($this->config['hikGatewayPassword'])){
                $hikGateKey = $hfd::HIK_DEVICE_GATEWAY;
                unset($thirdProtocol['brand_haikang'][$hikGateKey]);
            }
            $this->assign('thirdProtocol',$thirdProtocol);

            $judgeFaceXiHanDoorSwitch = D('Config_customization')->getFaceXiHanDoorSwitch();
            if ($judgeFaceXiHanDoorSwitch) {
                $siteUrl = $this->config['site_url'];
                $deviceTypeNoticeUrl = $this->config['site_url'] . "/v20/public/index.php/community/common.XiHan/eventNotification";
                $serverName = str_replace(['http://', 'https://'], '', $siteUrl);
                $serverName = str_replace('/', '', $serverName);
                $wsIp = isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] ? $_SERVER['SERVER_ADDR'] : '';
                !$wsIp && $wsIp = GetHostByName($_SERVER['SERVER_NAME']);
                !$wsIp && $wsIp = GetHostByName($serverName);
                $wsUrl = "ws://{$wsIp}:7077";
                $this->assign('deviceTypeNoticeUrl', $deviceTypeNoticeUrl);
                $this->assign('wsUrl', $wsUrl);

                $xiHanHttpNotification = $siteUrl . '/static/file/village/faceDevice/xi_han_http_notification_explain.png?t=01';
                $this->assign('xiHanHttpNotification', $xiHanHttpNotification);
                $webSocketSettingExplain = $siteUrl . '/static/file/village/faceDevice/web_socket_setting_explain.png?t=01';
                $this->assign('webSocketSettingExplain', $webSocketSettingExplain);
                $settingExplain = [
                    ['label' => '门禁管理/门禁设置', 'type' => 'door_setting', 'value' => $siteUrl . '/static/file/village/faceDevice/door_setting_explain.png?t=01'],
                    ['label' => '设备管理/智能管理/识别设置', 'type' => 'recognition_setting', 'value' => $siteUrl . '/static/file/village/faceDevice/recognition_setting_explain.png?t=01'],
                    ['label' => '设备管理/智能管理/IC卡设置', 'type' => 'ic_card_setting', 'value' => $siteUrl . '/static/file/village/faceDevice/ic_card_setting_explain.png?t=01'],
                    ['label' => '系统管理/系统维护', 'type' => 'reboot_setting', 'value' => $siteUrl . '/static/file/village/faceDevice/reboot_setting_explain.png?t=01'],
                ];
                $this->assign('settingExplain', $settingExplain);
            }
            $this->display();
        }
    }

    /**
     * 大华门禁一键同步
     */
    public function dahua_upgrade()
    {
        if (IS_POST) {
            $device_id=trim($_POST['device_id']);
            if(empty($device_id)){
                $this->error('缺少必要参数');
            }
            $device_info = M('House_face_device')->where(['device_id'=>$device_id])->find();

            $mHouse_face_device = D('House_face_device');
            $thirdProtocol = isset($device_info['thirdProtocol']) && $device_info['thirdProtocol'] ? $device_info['thirdProtocol'] : 0;
            if ($thirdProtocol && in_array($thirdProtocol, $mHouse_face_device->newProtocol)) {
                // 目前仅支持 海康和大华新协议逻辑
                $models = '\app\community\model\service\FaceDeviceService/deviceDhAllSyn';
                $param = [
                    'device_id'     => $device_info['device_id'],
                    'device_sn'     => $device_info['device_sn'],
                    'village_id'    => $device_info['village_id'],
                    'thirdProtocol' => $thirdProtocol,
                    'deviceType'    => 'face',
                ];
                $res = invoke_v20_service($models,$param);
            } else {
                $res = D('Dahua_service')->face_authorization($device_info['device_name'],$device_info['device_ip'],$device_info['village_id'],$device_id,$device_info['floor_id']);
            }
            if($res){
                $this->success('同步指令下发成功');
            }else{
                $this->error('同步指令下发失败');
            }
        }
    }
    /*
     * 查询未绑定的狄耐克设备
     * @author:zhubaodi
     * @date_time: 2022/2/10 14:05
     */
    public function get_dnake_device(){
        $where=[];
        $where['status']=0;
        $list=M('Face_d6_device')->where($where)->select();
        if (empty($list)){
            exit(json_encode(array('status'=>0,'msg'=>'暂无已配置的狄耐克设备,请到狄耐克设备后台完善mqtt相关配置项')));
        }else{
            exit(json_encode(array('status'=>1,'list'=>$list)));
        }
    }

    //	修改设备
    public function door_edit(){
        if (!in_array(498, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');exit;
        }
        $where=array('village_id'=>$this->house_session['village_id']);
        $where['device_id'] = I('device_id');
        if(empty($where['device_id'])){
            $where['device_id']	= $_GET['device_id'];
        }
        if(empty($where)){
            $this->error('设备未找到');exit;
        }
        $hfd = D('House_face_device');
        if($_POST){
            $device_info = $hfd->where($where)->field('*')->find();
            $data = array();
            if(isset($device_info['third_dev_source']) && $device_info['third_dev_source']=='lilin_pf'){

                if ($_POST['floor_id']) {
                    if ($_POST['floor_arr'] && is_array($_POST['floor_arr'])) {
                        $floor_id = implode(",",$_POST['floor_arr']);
                        $data['floor_id'] = $floor_id;
                    } else {
                        $data['floor_id'] = $_POST['floor_id'];
                    }
                }
                
                if($_POST['single_id']){
                    $data['single_id'] = $_POST['single_id'];
                    $single_id = $_POST['single_id'];
                    $single_id_arr = explode("_", $single_id);
                    if ($single_id_arr[0]=='door') {
                        $data['floor_id'] = -1;
                        $data['public_area_id'] = $single_id_arr[1];
                        $data['single_id'] = 0;
                    } elseif($data['floor_id']>0) {
                        $data['public_area_id'] = 0;
                    }
                }
                $data['device_direction']=$_POST['device_direction'];
                $data['remark'] = $_POST['remark'];
                $aSave = $hfd->save_door($where, $data);
                $this->success('修改设备成功！',U('door_list',array('device_type' => $_GET['deviceType'])));exit;
            }
            if((!isset($device_info['third_dev_source']) || $device_info['third_dev_source']!='lilin_pf') && empty(trim($_POST['device_type']))) {
                $this->error('请选择设备类型');exit;
            }
            if ($_POST['floor_id']) {
                if ($_POST['floor_arr'] && is_array($_POST['floor_arr'])) {
                    $floor_id = implode(",",$_POST['floor_arr']);
                    $data['floor_id'] = $floor_id;
                } else {
                    $data['floor_id'] = $_POST['floor_id'];
                }
            }
            if ($_POST['brand_type']) {
                $data['device_brand'] = $_POST['brand_type'];
            }
            if ($_POST['device_brand_type']){
                $data['brand_type'] = $_POST['device_brand_type'];
            }
            if ($_POST['remark']) {
                $data['remark'] = $_POST['remark'];
            }
            if ($_POST['device_param']) {
                $data['device_param'] = $_POST['device_param'];
            }
            if ($_POST['device_name']) {
                $data['device_name'] = $_POST['device_name'];
            }
            if (isset($_POST['device_direction'])) {
                $data['device_direction'] = $_POST['device_direction'];
            }
            if ($_POST['device_sn']) {
                //  5 6 8 21 22  23 24 61  设备拦截白名单 大部分是定制 海康和自己的A185
                $blockWhitelistDeviceTypeArr = $hfd->blockWhitelistDeviceTypeArr;
                if(in_array($_POST['device_type'],$blockWhitelistDeviceTypeArr))
                {
                    $data['device_sn'] = trim($_POST['device_sn']);
                }else {
                    $data['device_sn'] = trim($_POST['device_sn']);
                    $url = $this->check_site_url . '/face_door_check.php';
                    $params['device_id'] = $data['device_sn'];
                    $check_info = httpRequest($url, 'POST', $params);
                    $msg = $check_info[1];
                    if ('ok' != $msg) {
                        fdump_api(['operate' => 'edit', '_POST' => $_POST, 'url' => $url, 'params' => $params, 'check_info' => $check_info, 'msg' => $msg], 'face/door_edit_log', true);
                        $this->error('您添加的人脸识别门禁设备没有记录，请联系管理员');
                        exit;
                    }
                }
            }
            $single_id = I('single_id');
            $single_id = explode("_", $single_id);
            if ($single_id[0]=='door') {
                $data['floor_id'] = -1;
                $data['public_area_id'] = $single_id[1];
            } elseif($data['floor_id']>0) {
                $data['public_area_id'] = 0;
            }
            if (isset($_POST['device_score'])) {
                $data['device_score'] = $_POST['device_score'];
            }
            if (isset($_POST['device_alive'])) {
                $data['device_alive'] = $_POST['device_alive'];
            }
            if (isset($_POST['device_type'])) {
                $data['device_type'] = $_POST['device_type'];
            }
            if (isset($_POST['device_alive'])) {
                $data['device_alive'] = $_POST['device_alive'];
            }
            if (isset($_POST['device_score'])) {
                $data['device_score'] = $_POST['device_score'];
            }
            $thirdProtocol = 0;
            if (isset($_POST['thirdProtocol']) && trim($_POST['thirdProtocol'])) {
                $data['thirdProtocol'] = trim($_POST['thirdProtocol']);
                $thirdProtocol = trim($_POST['thirdProtocol']);
            }
            $hikGateKeyVillage = $hfd::HIK_DEVICE_GATEWAY_CUSTOM;
            $hikGateKey = $hfd::HIK_DEVICE_GATEWAY;
            if (trim($_POST['cloud_code'])) {
                $data['cloud_code'] = trim($_POST['cloud_code']);
            }else{
                if($_POST['device_type'] == 5 || $_POST['device_type'] == 6)
                {
                    if (!in_array($thirdProtocol, [$hikGateKey, $hikGateKeyVillage])) {
                        $this->error('请填写设备验证码');die();
                    }
                }
            }
            if (isset($_POST['device_model'])) {
                $data['device_model'] = $_POST['device_model'];
            }
            if (isset($_POST['layer_id'])) {
                $data['layer_id'] = $_POST['layer_id'];
            }
            if (isset($_POST['device_layer'])) {
                $data['device_layer'] = $_POST['device_layer'];
            }
            if (isset($_POST['network_method']) && trim($_POST['network_method'])) {
                $data['network_method'] = $_POST['network_method'];
            }
            if (isset($_POST['network_operator']) && trim($_POST['network_operator'])) {
                $data['network_operator'] = $_POST['network_operator'];
            }
            $defaultProtocol = $hfd->defaultProtocol;
            if (isset($defaultProtocol[$_POST['device_type']])&&!$data['thirdProtocol']) {
                $this->error('设备协议必须要选择');exit;
            }
            if (isset($_POST['thirdLoginName']) && trim($_POST['thirdLoginName'])) {
                $data['thirdLoginName'] = $_POST['thirdLoginName'];
            }
            if (isset($_POST['thirdLoginPassword']) && trim($_POST['thirdLoginPassword'])) {
                $data['thirdLoginPassword'] = $_POST['thirdLoginPassword'];
            }
            if (isset($_POST['thirdChnNum'])) {
                $data['thirdChnNum'] = $_POST['thirdChnNum'];
            }
            if ($thirdProtocol == $hfd::DH_YUNRUI && (!$data['thirdLoginName'] || !$data['thirdLoginPassword'])) {
                $data['thirdLoginName']     = 'admin';
                $data['thirdLoginPassword'] = 'admin123';
            }
            $isup_version = isset($_POST['isup_version']) ? trim($_POST['isup_version']) : '';
            if (in_array($thirdProtocol, [$hikGateKey, $hikGateKeyVillage]) && $isup_version == 'ehomeV5' && (!isset($data['thirdLoginPassword']) || !$data['thirdLoginPassword'])) {
                $this->error('请填写登录密钥');exit;
            }
            if (in_array($thirdProtocol, [$hikGateKey, $hikGateKeyVillage]) && isset($data['device_name']) && strlen($data['device_name']) > 32) {
                $this->error('设备名称，最多只能包含32个字符（1个汉字3个字符）');exit;
            }
            if (in_array($thirdProtocol, [$hikGateKey, $hikGateKeyVillage])) {
                $device_thirdProtocol = isset($device_info['thirdProtocol']) ? intval($device_info['thirdProtocol']) : 0;
                $device_cloud_device_id = isset($device_info['cloud_device_id']) ? trim($device_info['cloud_device_id']) : '';
                if ($device_cloud_device_id && $device_thirdProtocol != $thirdProtocol) {
                    $data['cloud_device_id'] = '';
                }
            } else {
                $device_thirdProtocol = isset($device_info['thirdProtocol']) ? intval($device_info['thirdProtocol']) : 0;
                $device_cloud_device_id = isset($device_info['cloud_device_id']) ? trim($device_info['cloud_device_id']) : '';
                if ($device_cloud_device_id && $device_thirdProtocol && $thirdProtocol && $device_thirdProtocol != $thirdProtocol) {
                    $this->error('设备已经同步到云端，不支持更换设备品牌和协议');exit;
                }
            }
            if (1==$data['device_model'] && !$data['device_layer']) {
                $this->error('梯控类型必须选择安装楼层');exit;
            }
            if (1==$data['device_model'] && !$data['floor_id']) {
                $this->error('梯控类型必须为单元门');exit;
            }
//			if($_POST['location']){
//				$locationArr = explode(',',trim($_POST['location']));
//				$data['lng'] = $locationArr[0];
//				$data['lat'] = $locationArr[1];
//			}
            //A8 人脸门禁 start
            if($_POST['device_type'] == 8) {
                if(isset($_POST['image_quality']))
                {
                    $data['image_quality'] = $_POST['image_quality']?$_POST['image_quality']:2;
                }
                if(isset($_POST['whitevalue'])) {
                    $data['whitevalue'] = $_POST['whitevalue']?$_POST['whitevalue']:80;
                }
                if(isset($_POST['mapvalue'])){
                    $data['mapvalue'] = isset($_POST['mapvalue'])?$_POST['mapvalue']:60;
                }
                if(isset($_POST['recog_mode_db'])){
                    $data['recog_mode_db'] = $_POST['recog_mode_db'];
                }
                if(isset($_POST['recog_mode_ic'])){
                    $data['recog_mode_ic'] = $_POST['recog_mode_ic'];
                }
            }
            // 朵普
            if($_POST['device_type'] == 29){
                // 设备类型  1人行通道/2非机动车通道
                if(isset($_POST['thirdDeviceTypeStr'])){
                    $data['thirdDeviceTypeStr'] = $_POST['thirdDeviceTypeStr'];
                }
            }
            //A8 人脸门禁 end
            if(22 == $_POST['device_type']){
                if($_POST['device_domain'] || $_POST['device_port']) {
                    $data['device_domain'] = trim($_POST['device_domain']).':'.trim($_POST['device_port']);
                }
            }
            //todo A185门禁
            if(61 == $_POST['device_type']){
                $a185_device = $hfd->where(array('device_id'=>$where['device_id']))->find();
                if(!$a185_device){
                    $this->error('设备不存在');exit;
                }
                if(empty(intval($_POST['a185_close_time']))){
                    $this->error('请输入门禁关门时间');exit;
                }
                if(!empty($_POST['a185_super_password']) && !empty($_POST['a185_admin_password'])){
                    if($_POST['a185_super_password'] == $_POST['a185_admin_password']){
                        $this->error('门禁开门密码和门禁管理密码不可相同');exit;
                    }
                    $data['a185_super_password']='*'.trim($_POST['a185_super_password']);
                    $data['a185_admin_password']='*'.trim($_POST['a185_admin_password']);
                }
                else{
                    if(!empty($_POST['a185_super_password'])){
                        $_POST['a185_super_password']=trim($_POST['a185_super_password']);
                        if(strlen($_POST['a185_super_password']) < 6){
                            $this->error('门禁管理密码最少6个字符');exit;
                        }
                        if($data['a185_super_password'] == $a185_device['a185_admin_password']){
                            $this->error('门禁管理密码和门禁开门密码不可相同');exit;
                        }
                        $data['a185_super_password']='*'.$_POST['a185_super_password'];
                    }
                    if(!empty($_POST['a185_admin_password'])){
                        $_POST['a185_admin_password']=trim($_POST['a185_admin_password']);
                        if(strlen($_POST['a185_admin_password']) < 6){
                            $this->error('门禁开门密码最少6个字符');exit;
                        }
                        if($data['a185_admin_password'] == $a185_device['a185_super_password']){
                            $this->error('门禁开门密码和门禁管理密码不可相同');exit;
                        }
                        $data['a185_admin_password']='*'.$_POST['a185_admin_password'];
                    }
                }
                if($_POST['b_time_left'] || $_POST['b_time_right']){
                    if((strtotime($_POST['b_time_left']) > strtotime($_POST['b_time_right'])) || ($_POST['b_time_left'] == $_POST['b_time_right'])){
                        $this->error('白天音量时间区间不符');exit;
                    }

                }
                if($_POST['w_time_left'] || $_POST['w_time_right']){

//                    if(strtotime($_POST['w_time_left']) > strtotime($_POST['w_time_right'])){
//                        $this->error('晚上音量时间区间不符');exit;
//                    }
                }
                if($_POST['is_tele_card'] == 1){
                    if(empty($_POST['tele'])){
                        $this->error('请输入手机号');exit;
                    }
                }
                $data['a185_close_time']=intval($_POST['a185_close_time']);
                $data['a185_restart_time']=$_POST['a185_restart_time'];
                $data['a185_volume']=serialize(array(
                    'day'=>array(
                        'time'=>array($_POST['b_time_left'],$_POST['b_time_right']),
                        'size'=>$_POST['b_volume']
                    ),
                    'night'=>array(
                        'time'=>array($_POST['w_time_left'],$_POST['w_time_right']),
                        'size'=>abs($_POST['w_volume'])
                    )
                ));
                $data['a185_tele_card']=serialize(array(
                    'is_tele_card'=>$_POST['is_tele_card'],
                    'tele'=>trim($_POST['tele'])
                ));
            }
            if($_POST['device_type'] == 88){
                $dahua_device = $hfd->where(array('device_id'=>$where['device_id']))->find();
                if(!$dahua_device){
                    $this->error('设备不存在');exit;
                }
            }
            if(isset($_POST['internet_card'])){
                $data['internet_card'] = trim($_POST['internet_card']);
            }
            if($_POST['device_ip']) {
                $data['device_ip'] = $_POST['device_ip'];
            }
            if($_POST['device_type'] == 5 || $_POST['device_type'] == 6){
                $data['is_visual'] = isset($_POST['is_visual'])?$_POST['is_visual']:1;
            }
            if (empty($data)) {
                $this->error('修改设备失败！请检查是否有修改内容');exit;
            }
            $info = $hfd->where(array('device_sn'=>$data['device_sn'],'is_del'=>array('lt',4), 'device_id' => array('neq', $where['device_id'])))->find();
            if (!empty($info)) {
                $this->error('当前编号设备已经被添加');exit;
            }
            $original_device_info = $hfd->where(array('device_id' => $where['device_id']))->find();
            //todo D4智能门禁不允许编辑
            if(24 == $_POST['device_type']){
                if(!empty($info['ai_device_sn'])){
                    $this->error('D4智能门禁不允许编辑');exit;
                }
            }

            if ($_POST['floor_id']<=0) {
                $name_info = $hfd->where(array('device_name'=>$data['device_name'], 'device_id' => array('neq', $where['device_id']),'village_id' => $data['village_id']))->find();
                if (!empty($name_info)) {
                    $this->error('当前设备名称已经被添加，请更换名称');exit;
                }
            }
            $data['last_time'] = time();
            fdump_api(['data' => $data, 'where' => $where, 'post' => $_POST], 'save_door');
            $aSave = $hfd->save_door($where, $data);
            if ($aSave) {
                $db_house_face_device_about = M('House_face_device_about');
                $about = [];
                if (isset($_POST['isup_version']) && trim($_POST['isup_version'])) {
                    $whereAbout = ['device_id' => $where['device_id']];
                    $aboutInfo = $db_house_face_device_about->where($whereAbout)->field('device_id, gatewayinfo')->find();
                    if (isset($aboutInfo['device_id']) && $aboutInfo['device_id']) {
                        $gatewayinfo = isset($aboutInfo['gatewayinfo']) && $aboutInfo['gatewayinfo'] ? json_decode($aboutInfo['gatewayinfo'], 1) : [];
                        $gatewayinfo['isup_version'] = trim($_POST['isup_version']);
                    } else {
                        $gatewayinfo = ['isup_version'=>trim($_POST['isup_version'])];
                    }
                    $about['device_id'] = $where['device_id'];
                    $about['gatewayinfo'] = json_encode($gatewayinfo, JSON_UNESCAPED_UNICODE);
                    if (!isset($aboutInfo['device_id']) || !$aboutInfo['device_id']) {
                        $about['add_time'] = time();
                        $db_house_face_device_about->add($about);
                    } else {
                        $about['last_time'] = time();
                        $db_house_face_device_about->where($whereAbout)->data($about)->save();
                    }
                }
                //更新配置接口

                // 记录日志
                $orderGroupId = md5(uniqid().$_SERVER['REQUEST_TIME']);
                $param = [
                    'log_name' => 'adminEditDevice','logType' => 'houseAdmin','log_status' => 6,'orderGroupId' => $orderGroupId,
                    'log_content' => '编辑设备','device_id' => $where['device_id'],'device_sn' => $_POST['device_sn']?$_POST['device_sn']:'',
                    'device_type' => $_POST['device_type']?$_POST['device_type']:'','village_id' => $_POST['village_id']?$_POST['village_id']:0,
                    'addTime' => time(),
                ];
                try {
                    D('House_face_device_log')->addLog($param);
                } catch( \Exception $e) {
                    // 记录日志报错
                    fdump_api(['日志记录[添加设备]出错'.__LINE__.'方法'.__FUNCTION__,$param,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
                }
                $otherParam = [
                    'orderGroupId' => $orderGroupId,
                    'operatorType' => 'admin',
                ];
                $defaultProtocolArr = array_values($defaultProtocol);

                if (30 == intval($_POST['device_type'])) {
                    // 260云 将设备相关信息加入指令中等候
                    D('House_village_device_cmd')->addOrUpdateDeviceCmd($device_info);
                }
                
                if (2==$_POST['device_type']) {
                    $device_msg = $hfd->where($where)->find();
                    $data['a2_face_door_doorkeeperId'] = $device_msg['a2_face_door_doorkeeperId'];
                    if (empty($data['device_name'])) {
                        $data['device_name'] = $device_msg['device_name'];
                    }
                    if (empty($data['device_sn'])) {
                        $data['device_sn'] = $device_msg['device_sn'];
                    }
                    if (empty($data['floor_id'])) {
                        $data['floor_id'] = $device_msg['floor_id'];
                    }
                    if (empty($data['village_id'])) {
                        $data['village_id']	= $this->house_session['village_id'];
                    }
                    D('House_village_face_door')->face_door_add($where['device_id'], $data);
                }
                elseif (7==$_POST['device_type'] && $data['device_score']>0){
                    D('Face_door_a7_service')->face_a7_door_add($where['device_id'], $data);
                }
                elseif (5==$_POST['device_type'] || 6==$_POST['device_type']){
                    if (!$thirdProtocol || in_array($_POST['thirdProtocol'],$defaultProtocolArr)) {
                        D('Face_door_a5_service')->setVisual($where['device_id'],$data,$otherParam);
                    }
                }
                elseif (4==$_POST['device_type']) {
//                    D('Face_door_a4_service')->faceDeviceSynchro($where['device_id'], [], 0);
                    D('Face_door_a4_service')->faceDeviceSynchro($where['device_id'], [], 1);
                }
                elseif (8==$_POST['device_type']){
                    $data['imageQuality'] = $data['image_quality'];
                    $data['recogModeDB'] = $data['recog_mode_db'];
                    $data['recogModeIC'] = $data['recog_mode_ic'];
                    unset($data['image_quality']);
                    unset($data['recog_mode_db']);
                    unset($data['recog_mode_ic']);
                    D('Face_local_area_device')->addDevice($where['device_id'], $data);
                }
                elseif (21==$_POST['device_type']){
                    D('Face_door_d1_service')->face_d1_door_syn($where['device_id'], [], 'edit_device',[]);
                }
                elseif (23==$_POST['device_type']){
                    D('Face_d3_request_record')->d3_set_match_score_record($where['device_id'], $data, 'edit_device',[]);
                }
                elseif (24==$_POST['device_type']) {
                    //todo D4智能门禁 新增设备
                    D('Face_d4_request_record')->d4_device($where['device_id'], 2);

                }elseif (61==$_POST['device_type']){
                    //todo 新增a185设备
                    D('Face_a185_request_record')->updateConfig($device_info['device_id'], $device_info,$this->config);
                    //更新广设备
                    D('Face_a185_request_record')->updateAdvert($device_info['device_id'], $device_info);
                }elseif (88 == $_POST['device_type']){
                    if (!$thirdProtocol || in_array($_POST['thirdProtocol'],$defaultProtocolArr)) {
                        //todo 大华人脸门禁
                        D('Dahua_service')->face_authorization($device_info['device_name'], $device_info['device_ip'], $device_info['village_id'], $device_info['device_id'], $device_info['floor_id']);
                    }
                }elseif (26==$_POST['device_type']) {
                    //todo D6智能门禁 新增设备
                    D('Face_d6_request_record')->d6_device($where['device_id'], 2);
                } elseif (31 == $_POST['device_type']) {
                    // 西函门禁
                    $db_house_face_device_cmd = M('House_face_device_cmd');
                    $nowTime = time();
                    // 批量操作配置下发设备
                    $cmdParams = [
                        'village_id' => $device_info['village_id'], 'cmd_name' => 'configCentre.setConfig', 'add_time' => $nowTime,
                        'exec_status' => 0, 'device_id' => $device_info['device_id'], 'device_sn' => $device_info['device_sn'],
                        'device_type' => $_POST['device_type'], 'device_type_str' => 'xihan',
                        'relate_id' => $device_info['device_id'], 'relate_type' => 'house_face_device'
                    ];
                    if (isset($original_device_info['device_name']) && $original_device_info['device_name'] && isset($data['device_name']) && $data['device_name'] && $original_device_info['device_name'] != $data['device_name']) {
                        $cmd_data = [
                            'method' => 'configCentre.setConfig',
                            'params' => [
                                'name' => 'MachineGlobal', 'content' => [['MachineName' => $data['device_name']]],
                            ],
                        ];
                        $dataCmd = $cmdParams;
                        $dataCmd['cmd_data'] = json_encode($cmd_data,JSON_UNESCAPED_UNICODE);
                        fdump_api(['dataCmd' => $dataCmd], '$dataCmd', true);
                        $db_house_face_device_cmd->add($dataCmd);
                    };

                    $cmd_data = [
                        'method' => 'configCentre.setConfig',
                        'params' => [
                            'name' => 'DoorMode', 'content' => [['Mode' => 'Control']],
                        ],
                    ];
                    $dataCmd = $cmdParams;
                    $dataCmd['cmd_data'] = json_encode($cmd_data,JSON_UNESCAPED_UNICODE);
                    $db_house_face_device_cmd->add($dataCmd);
                    fdump_api(['dataCmd' => $dataCmd], '$dataCmd', true);

                    if (isset($original_device_info['device_score']) && $original_device_info['device_score'] && isset($data['device_score']) && $data['device_score'] && $original_device_info['device_score'] != $data['device_score']) {
                        $device_score = isset($data['device_score']) && $data['device_score'] > 0 ? intval($data['device_score']) : 80;
                        $cmd_data = [
                            'method' => 'configCentre.setConfig',
                            'params' => [
                                'name' => 'FaceRecogniseRule', 'content' => [['GroupID' => 1, 'SearchThreshold' => $device_score], 'execute' => 'delaySave'],
                            ],
                        ];
                        $dataCmd = $cmdParams;
                        $dataCmd['cmd_data'] = json_encode($cmd_data, JSON_UNESCAPED_UNICODE);
                        $db_house_face_device_cmd->add($dataCmd);
                        fdump_api(['dataCmd' => $dataCmd], '$dataCmd', true);
                    }
                    
                    $cmd_data = [
                        'method' => 'global.setTime',
                        'params' => [
                            'time' => date('Y-m-d H:i:s', $nowTime),
                        ],
                    ];
                    $dataCmd = $cmdParams;
                    $dataCmd['cmd_name'] = 'global.setTime';
                    $dataCmd['cmd_data'] = json_encode($cmd_data,JSON_UNESCAPED_UNICODE);
                    $db_house_face_device_cmd->add($dataCmd);
                }
                fdump_api(['thirdProtocol' => $thirdProtocol, 'newProtocol' => $hfd->newProtocol], 'face/door_edit_log', true);
                if ($thirdProtocol && in_array($thirdProtocol, $hfd->newProtocol)) {
                    // 目前仅支持 海康和大华新协议逻辑
                    $models = '\app\community\model\service\FaceDeviceService/handleDevice';
                    $param = [
                        'device_sn' => $device_info['device_sn'],
                        'param'     => [
                            'village_id'    => $device_info['village_id'],
                            'device_id'     => $device_info['device_id'],
                            'operation'     => 'updateDevice',
                            'device_ip'     => $device_info['device_ip'],
                            'orderGroupId'  => $orderGroupId,
                            'thirdProtocol' => $thirdProtocol,
                            'deviceType'    => 'face',
                        ]
                    ];
                    fdump_api(['models' => $models, 'param' => $param], 'face/door_edit_log', true);
                    $thirdAdd = invoke_v20_service($models,$param);
                }
                $this->success('修改设备成功！',U('door_list',array('device_type' => $_GET['deviceType'])));exit;
            }else{
                $this->error('修改设备失败！请检查是否有修改内容');exit;
            }
        } 
        else {
            //门禁设置-查看 权限
            if (!in_array(285, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');exit;
            }

            $defaultProtocol = $hfd->defaultProtocol;
            $this->assign('defaultProtocol',$defaultProtocol);
            $aDoor = $hfd->where($where)->find();
            if($aDoor['device_type'] == 22 && $aDoor['device_domain']){
                $device_domain = explode(':',$aDoor['device_domain']);
                if(count($device_domain)==2){
                    $aDoor['device_domain'] = $device_domain[0];
                    $aDoor['device_port'] = $device_domain[1];
                }else{
                    $aDoor['device_domain'] = $device_domain[0].':'.$device_domain[1];
                    $aDoor['device_port'] = $device_domain[2];
                }
            }
            if (!$aDoor['thirdProtocol'] && isset($defaultProtocol[$aDoor['thirdProtocol']])) {
                $aDoor['thirdProtocol'] = $defaultProtocol[$aDoor['thirdProtocol']];
            }
            $floor_arr = explode(',',$aDoor['floor_id']);
            if (count($floor_arr)==1) {
                $aDoor['floor_id'] = $floor_arr[0];
            }
            if ($aDoor['public_area_id']>0) {
                $aDoor['single_id'] = 'door_'.$aDoor['public_area_id'];
            } elseif (count($floor_arr)>1) {
                $aDoor['single_id'] = 0;
                $floor_about_info = M('House_village_floor')
                    ->join(' as f LEFT JOIN '.C('DB_PREFIX').'house_village_single as s on s.id=f.single_id')
                    ->where(array('floor_id' => ['in', $floor_arr]))->field('f.floor_id, s.id as single_id, f.floor_name, s.single_name')->select();
                $this->assign('floor_about_info',$floor_about_info);
                $aDoor['floor_arr'] = $floor_arr;
            } elseif ($aDoor['floor_id']>0) {
                $aDoor['single_id'] = M('House_village_floor')->where(array('floor_id' => $aDoor['floor_id']))->getField('single_id');
            }
            /**
             * todo A185门禁
             */
            if($aDoor['device_type'] == 61){
                $aDoor['a185_volume']=unserialize($aDoor['a185_volume']);
                $aDoor['a185_tele_card']=unserialize($aDoor['a185_tele_card']);
                $aDoor['a185_super_password']=$aDoor['a185_super_password'] ? '******' : '';
                $aDoor['a185_admin_password']=$aDoor['a185_admin_password'] ? '******' : '';
            }
            if(isset($aDoor['extra_data']) && $aDoor['extra_data']){
                $aDoor['extra_data']=json_decode($aDoor['extra_data'],true);
            }
            $this->assign('aDoor',$aDoor);
            //	获取小区的楼层列表
            $arr['village_id'] = $this->house_session['village_id'];
            $arr['status'] = 1;
            $aFloor	=	M('House_village_floor')->field(array('floor_id','floor_name','floor_layer'))->where($arr)->select();
            $this->assign('aFloor',$aFloor);
            $device_type_info = $hfd->device_type_info;
            if (1!=$this->config['show_facedoor_d1']) {
                unset($device_type_info[8]);
            }

            if($aDoor['device_type'] == 29){
                $device_type_info = [['device_type' => 29, 'name' => '朵普智能门禁机']];
            }
            $this->assign('device_type_info',$device_type_info);

            $brand_list=D('HardwareBrand')->getBrand(1);
            $this->assign('device_brand',$brand_list);

            $this->assign('device_type',$aDoor['device_type']);
            $brandHaikangCodeTip = $hfd->brandHaikangCodeTip;
            $this->assign('brandHaikangCodeTip',$brandHaikangCodeTip);
            $thirdProtocol = $hfd->thirdProtocol;
            $village_id = $this->house_session['village_id'];
            $gatewayInfo = M('House_device_gateway')->where(['village_id' => $village_id, 'is_open'=>1])->find();
            //过滤没有启用配置的海康网关
            $hikGateKeyVillage = $hfd::HIK_DEVICE_GATEWAY_CUSTOM;
            if (empty($gatewayInfo)){
                unset($thirdProtocol['brand_haikang'][$hikGateKeyVillage]);
            }
            if (empty($this->config['hikGatewayHost']) || empty($this->config['hikGatewayProtocol']) || empty($this->config['hikGatewayPort'])|| empty($this->config['hikGatewayUserName'])|| empty($this->config['hikGatewayPassword'])){
                $hikGateKey = $hfd::HIK_DEVICE_GATEWAY;
                unset($thirdProtocol['brand_haikang'][$hikGateKey]);
            }
            $this->assign('thirdProtocol',$thirdProtocol);

            $db_house_face_device_about = M('House_face_device_about');
            $aboutInfo = $db_house_face_device_about->where(['device_id' => $where['device_id']])->field('device_id, gatewayinfo')->find();
            if (isset($aboutInfo['gatewayinfo']) && $aboutInfo['gatewayinfo']) {
                $gatewayinfo = json_decode($aboutInfo['gatewayinfo'], 1);
                $isup_version = isset($gatewayinfo['isup_version']) ? trim($gatewayinfo['isup_version']) : '';
                $this->assign('isup_version',$isup_version);
            }
            // 查询设备状态
            $db_syn_overview = M('Syn_overview');
            $whereSynOverview = [
                'syn_type' => 'sys_face_device', 'syn_id' => $aDoor['device_id'], 'to_id' => 0,
            ];
            $synOverView = $db_syn_overview->where($whereSynOverview)->field('id, syn_type, syn_id, to_id, syn_status, syn_msg, syn_time')->find();
            if (isset($synOverView['syn_time']) && intval($synOverView['syn_time']) > 0) {
                $synOverView['syn_time_text'] = date('Y-m-d H:i:s', $synOverView['syn_time']);
            }
            if (empty($synOverView)) {
                $db_device_bind_info = M('Device_bind_info');
                $whereDeviceBind = [
                    'bind_type' => $hfd::BIND_FACE_DEVICE, 'bind_id' => $aDoor['device_id'], 'third_protocol' => $aDoor['thirdProtocol'],
                ];
                $field = 'id, syn_status, syn_status_txt, err_reason, add_time, update_time';
                $synOverView = $db_device_bind_info->where($whereDeviceBind)->order('id desc')->field($field)->find();
                if (!empty($synOverView)) {
                    if (isset($synOverView['update_time']) && intval($synOverView['update_time']) > 0) {
                        $synOverView['syn_time_text'] = date('Y-m-d H:i:s', $synOverView['update_time']);
                    } elseif (isset($synOverView['add_time']) && intval($synOverView['add_time']) > 0) {
                        $synOverView['add_time'] = date('Y-m-d H:i:s', $synOverView['add_time']);
                    }
                    if (isset($synOverView['err_reason']) && $synOverView['err_reason']) {
                        $synOverView['syn_msg'] = trim($synOverView['err_reason']);
                    } elseif (isset($synOverView['syn_status_txt']) && $synOverView['syn_status_txt']) {
                        $synOverView['syn_msg'] = trim($synOverView['syn_status_txt']);
                    }
                    if (isset($synOverView['update_time'])) {
                        unset($synOverView['update_time']);
                    }
                    if (isset($synOverView['add_time'])) {
                        unset($synOverView['add_time']);
                    }
                    if (isset($synOverView['err_reason'])) {
                        unset($synOverView['err_reason']);
                    }
                    if (isset($synOverView['syn_status_txt'])) {
                        unset($synOverView['syn_status_txt']);
                    }
                }
            }
            $this->assign('synOverView',$synOverView);
            $judgeFaceXiHanDoorSwitch = D('Config_customization')->getFaceXiHanDoorSwitch();
            if ($judgeFaceXiHanDoorSwitch) {
                $siteUrl = $this->config['site_url'];
                $deviceTypeNoticeUrl = $this->config['site_url'] . "/v20/public/index.php/community/common.XiHan/eventNotification";
                $serverName = str_replace(['http://', 'https://'], '', $siteUrl);
                $serverName = str_replace('/', '', $serverName);
                $wsIp = isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] ? $_SERVER['SERVER_ADDR'] : '';
                !$wsIp && $wsIp = GetHostByName($_SERVER['SERVER_NAME']);
                !$wsIp && $wsIp = GetHostByName($serverName);
                $wsUrl = "ws://{$wsIp}:7077";
                $this->assign('deviceTypeNoticeUrl', $deviceTypeNoticeUrl);
                $this->assign('wsUrl', $wsUrl);

                $xiHanHttpNotification = $siteUrl . '/static/file/village/faceDevice/xi_han_http_notification_explain.png?t=01';
                $this->assign('xiHanHttpNotification', $xiHanHttpNotification);
                $webSocketSettingExplain = $siteUrl . '/static/file/village/faceDevice/web_socket_setting_explain.png?t=01';
                $this->assign('webSocketSettingExplain', $webSocketSettingExplain);
                $settingExplain = [
                    ['label' => '门禁管理/门禁设置', 'type' => 'door_setting', 'value' => $siteUrl . '/static/file/village/faceDevice/door_setting_explain.png?t=01'],
                    ['label' => '设备管理/智能管理/识别设置', 'type' => 'recognition_setting', 'value' => $siteUrl . '/static/file/village/faceDevice/recognition_setting_explain.png?t=01'],
                    ['label' => '设备管理/智能管理/IC卡设置', 'type' => 'ic_card_setting', 'value' => $siteUrl . '/static/file/village/faceDevice/ic_card_setting_explain.png?t=01'],
                    ['label' => '系统管理/系统维护', 'type' => 'reboot_setting', 'value' => $siteUrl . '/static/file/village/faceDevice/reboot_setting_explain.png?t=01'],
                ];
                $this->assign('settingExplain', $settingExplain);
            }
            if(isset($aDoor['third_dev_source']) && $aDoor['third_dev_source']=='lilin_pf'){
                
                $this->display('lilin_pf_door_edit');
            }else{
                $this->display();
            }
            
        }
    }
    //a5一键同步
    public function a5KeySyn()
    {
        if (!in_array(501, $this->house_session['menus'])) {
            $array = array(
                'errorCode'=>1001,
                'errorMsg'=>'对不起，您没有权限执行此操作',
            );
            exit(json_encode($array));
        }
        $device_id = $_POST['device_id'];
        $device_sn = $_POST['device_sn'];//设备编号
        if(empty($device_sn)){
            exit(json_encode(array('errorCode'=>1001,'errorMsg'=>'参数错误！')));
        }
        $a5Service = D('Face_door_a5_service');
        $face_device_data = M('House_face_device')->where(array('device_id' => $device_id))->find();
        $thirdProtocol = isset($face_device_data['thirdProtocol']) && $face_device_data['thirdProtocol'] ? $face_device_data['thirdProtocol'] : 0;

        $hfd = D('House_face_device');
        $hikGateKeyVillage = $hfd::HIK_DEVICE_GATEWAY_CUSTOM;
        $hikGateKey = $hfd::HIK_DEVICE_GATEWAY;
        if ($thirdProtocol && in_array($thirdProtocol, [$hikGateKey, $hikGateKeyVillage])) {
            // 添加一键同步功能
            M('House_face_device')->where(array('device_id' => $device_id))->data(['login_time'=>time()])->save();
            D('Face_door_service')->face_device_check($device_id, $face_device_data);
        } elseif ($thirdProtocol && in_array($thirdProtocol, $a5Service->newProtocol)) {
            // 目前仅支持 海康和大华新协议逻辑
            $models = '\app\community\model\service\FaceDeviceService/deviceDhAllSyn';
            $param = [
                'device_id'     => $face_device_data['device_id'],
                'device_sn'     => $face_device_data['device_sn'],
                'village_id'    => $face_device_data['village_id'],
                'thirdProtocol' => $thirdProtocol,
                'deviceType'    => 'face',
            ];
            $res = invoke_v20_service($models,$param);
        } elseif (!empty($face_device_data['cloud_device_id'])) {
            // 添加一键同步功能
            M('House_face_device')->where(array('device_id' => $device_id))->data(['login_time'=>time()])->save();
            D('Face_door_service')->face_device_check($device_id, $face_device_data);
        } else {
            $orderGroupId = md5(uniqid().$_SERVER['REQUEST_TIME']);
            $otherParam = [
                'orderGroupId' => $orderGroupId,
                'operatorType' => 'admin',
            ];
            $a5Service->addEquipment($device_id, $face_device_data,$otherParam);
        }
        exit(json_encode(array('errorCode'=>0,'errorMsg'=>'操作成功！')));
    }
    //远程开门
    public function remoteOpen()
    {
        $device_sn = $_POST['device_sn'];//设备编号
        if(empty($device_sn)){
            exit(json_encode(array('errorCode'=>1001,'errorMsg'=>'参数错误！')));
        }
        $res = D('Face_door_a5_service')->remoteOpen($device_sn);
        if($res['code'] == 200){
            exit(json_encode(array('errorCode'=>0,'errorMsg'=>'开门成功！')));
        }else{
            exit(json_encode(array('errorCode'=>1001,'errorMsg'=>'开门失败！')));
        }
    }
    //目前a5获取设备状态 type可使用与多个  @date_time 2020/07/23
    public function getEquipmentStatus()
    {
        $device_sn = $_POST['device_sn'];
        $device_id = $_POST['device_id'];
        $type = $_POST['device_type'];
        if($type == 5 || $type == 6) {
            if (empty($device_sn)) {
                exit(json_encode(array('errorCode' => 1001, 'errorMsg' => '参数错误！')));
            }
            $where['is_del'] = ['neq',4];
            $where['device_id'] = $device_id;
            $deviceInfo = D('House_face_device')->where($where)->find();
            if (isset($deviceInfo['thirdProtocol']) && $deviceInfo['thirdProtocol'] && ! in_array($deviceInfo['thirdProtocol'], [11,15])) {
                $models = '\app\community\model\service\FaceDeviceService/getDeviceStatus';
                $modelParams = [
                    'village_id'      => $deviceInfo['village_id'],
                    'device_id'       => $deviceInfo['device_id'],
                    'device_sn'       => $deviceInfo['device_sn'],
                    'cloud_device_id' => $deviceInfo['cloud_device_id'],
                    'thirdProtocol'   => $deviceInfo['thirdProtocol'],
                    'deviceType'      => 'face',
                ];
                $param = [
                    'param' => $modelParams
                ];
                $thirdDeviceInfo = invoke_v20_service($models,$param);
                $thirdDevice = $thirdDeviceInfo['data']['retval'];
                $models = '\app\community\model\service\Device\HikGateWayDeviceService/getHikGatewayDeviceInfoBox';
                $param = [
                    'deviceInfo' => [],
                    'param' => $modelParams
                ];
                $thirdDeviceInfo1 = invoke_v20_service($models,$param);
                if (isset($thirdDevice['code']) && $thirdDevice['code'] == '200') {
                    exit(json_encode(array('errorCode' => 0, 'errorMsg' => '获取状态成功！')));
                }
            } elseif (isset($deviceInfo['thirdProtocol']) && $deviceInfo['thirdProtocol'] == 15) {
                $models = '\app\community\model\service\Device\HikInfoVisionICommunityService/getDoorState';
                $param = [
                    'device_id' => $deviceInfo['device_id']
                ];
                $thirdDeviceInfo = invoke_v20_service($models,$param);
                $thirdDevice = $thirdDeviceInfo['data']['retval'];
                if (isset($thirdDevice['code']) && $thirdDevice['code'] == 0) {
                    exit(json_encode(array('errorCode' => 0,'result' => $thirdDevice, 'errorMsg' => '获取状态成功！')));
                } else {
                    exit(json_encode(array('errorCode' => 1,'result' => $thirdDevice, 'errorMsg' => $thirdDevice['msg'])));
                }
            }
            if($deviceInfo) {
                $info = D('Face_door_a5_service')->getEquipmentInfo($device_sn);
                if ($info) {
                    exit(json_encode(array('errorCode' => 0, 'errorMsg' => '获取状态成功！')));
                } else {
                    exit(json_encode(array('errorCode' => 1001, 'errorMsg' => '获取状态失败！')));
                }
            }else{
                exit(json_encode(array('errorCode' => 1001, 'errorMsg' => '设备不存在或已被删除！')));
            }
        }
    }
    //目前a5删除设备 type可使用与多个  @date_time 2020/07/24
    public function delEquipment()
    {
        $device_sn = $_POST['device_sn'];
        $device_id = $_POST['device_id'];
        $type = $_POST['device_type'];
        $orderGroupId = md5(uniqid().$_SERVER['REQUEST_TIME']);
        $otherParam = [
            'orderGroupId' => $orderGroupId,
            'operatorType' => 'admin',
        ];
        if($type == 5 || $type == 6) {
            if (empty($device_sn)) {
                exit(json_encode(array('errorCode' => 1001, 'errorMsg' => '参数错误！')));
            }
            $where['is_del'] = ['neq',4];
            $where['device_id'] = $device_id;
            $deviceInfo = D('House_face_device')->where($where)->find();
            if($deviceInfo) {
                $info = D('Face_door_a5_service')->removeDevice($device_sn,$otherParam);
                if ($info) {
                    $device_sn = $deviceInfo['device_sn'].'~no_use';
                    D('House_face_device')->where(['device_id' => $device_id])->data(['is_del' => 4, 'device_sn'=>$device_sn])->save();
                    exit(json_encode(array('errorCode' => 0, 'errorMsg' => '删除成功！')));
                } else {
                    exit(json_encode(array('errorCode' => 1001, 'errorMsg' => '删除失败！')));
                }
            }else{
                exit(json_encode(array('errorCode' => 1001, 'errorMsg' => '设备不存在或已被删除！')));
            }
        }elseif ($type == 3){
            if (empty($device_id)) {
                exit(json_encode(array('errorCode' => 1001, 'errorMsg' => '参数错误！')));
            }
            $where['is_del'] = ['neq',4];
            $where['device_id'] = $device_id;
            $deviceInfo = D('House_face_device')->where($where)->find();
            if($deviceInfo) {
                $info = D('House_village_face_door')->del_a3_device($device_id,$deviceInfo['a3_device_id']);
                if ($info) {
                    $device_sn = $deviceInfo['device_sn'].'~no_use';
                    D('House_face_device')->where(['device_id' => $device_id])->data(['is_del' => 4, 'device_sn'=>$device_sn])->save();
                    exit(json_encode(array('errorCode' => 0, 'errorMsg' => '删除成功！')));
                } else {
                    exit(json_encode(array('errorCode' => 1001, 'errorMsg' => '删除失败！')));
                }
            }else{
                exit(json_encode(array('errorCode' => 1001, 'errorMsg' => '设备不存在或已被删除！')));
            }
        }else{
            exit(json_encode(array('errorCode' => 1001, 'errorMsg' => '请传递有效的设备类型！')));
        }
    }
    public function add_ss()
    {
        D('Face_door_a5_service')->getBindAuthGroupMember(119,0);
    }

    //门禁记录
    public function door_open_list(){
        if (!in_array(499, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');exit;
        }
        $village_id = $this->house_session['village_id'];
        $nowDoor = M('House_face_device')->where(array('device_id'=>$_GET['device_id'],'village_id'=>$village_id))->find();
        if(empty($nowDoor)){
            $this->error('人脸门禁设备不存在');
        }
        if($nowDoor['floor_id'] != '-1'){
            $nowDoor['floor_info'] = M('House_village_floor')->where(array('floor_id'=>$nowDoor['floor_id']))->find();
        }
        if (in_array($nowDoor['device_type'],[5,7,21,30])) {
            $nowDoor['is_temp'] = true;
        } else {
            $nowDoor['is_temp'] = false;
        }
        $this->assign('nowDoor',$nowDoor);
        // 正常体温
        $this->assign('normal_temperature',37.3);

        $find_type = I('find_type','','trim');
        $find_value = I('find_value','','trim');
        $status = I('status','','intval');
        $start_time = I('start_time','','trim');
        $end_time = I('end_time','','trim');

        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        if (2==$nowDoor['device_type']) {
            $page = !empty($_GET['page']) ? intval($_GET['page']) : 1;
            $firstRow = 20*($page-1);

            $params = array();

            if ($nowDoor['floor_info'] && $nowDoor['floor_info']['a2_face_door_buildId']) {
                $params['buildId'] = $nowDoor['floor_info']['a2_face_door_buildId'];
            }
            $params['doorKeeperName'] = $nowDoor['device_name'];

            $village_info = M('House_village')->where(array('village_id' => $this->house_session['village_id']))->find();
            $door_info = D('House_village_face_door')->face_door_log($firstRow, 20,$village_info['a2_face_door_communityId'],$params);
            $count = $door_info['iTotalRecords'];
            if($count<=0 || !$door_info || $door_info['code']!=1) {
                // 如果查询不到对应门禁记录，查询下本地
                //人脸门禁记录列表
                $log_where = array(
                    'd.device_id' => $nowDoor['device_id'],
                    'd.log_from'=>1
                );
                if ($find_type && 'name'==$find_type && $find_value) {
                    $log_where['u.nickname|hvu.name'] =  array('like',  '%' . $find_value . '%');
                } elseif ($find_type && 'phone'==$find_type && $find_value) {
                    $log_where['u.phone|hvu.phone'] =  array('like',  '%' . $find_value . '%');
                }
                if (1==$status) {
                    // 成功开门的
                    $log_where['d.log_status'] = 0;
                } elseif (2==$status) {
                    // 开门失败的
                    $log_where['d.log_status'] = array('neq', 0);
                }
                if(!empty($start_time)&&empty($end_time)){
                    $start_time = strtotime($start_time." 00:00:00");
                    $log_where['d.log_time'] =  array('egt',$start_time);
                }
                if(empty($start_time)&&!empty($end_time)){
                    $end_time = strtotime($end_time."  23:59:59");
                    $log_where['d.log_time'] =  array('elt',$end_time);
                }
                if(!empty($start_time)&&!empty($end_time)){
                    $start_time = strtotime($start_time." 00:00:00");
                    $end_time = strtotime($end_time."  23:59:59");
                    if ($start_time>$end_time) {
                        $this->error("结束时间应大于开始时间");
                    }
                    $log_where['d.log_time'] =  array('between',$start_time.','.$end_time);
                }
                if(!empty($start_time)&&!empty($end_time)){
                    $start_time = strtotime($start_time." 00:00:00");
                    $end_time = strtotime($end_time."  23:59:59");
                    if ($start_time>$end_time) {
                        $this->error("结束时间应大于开始时间");
                    }
                    $log_where['d.log_time'] =  array('between',$start_time.','.$end_time);
                }
                $left_join = "as d LEFT JOIN ".C('DB_PREFIX')."user as u ON `d`.`uid`=`u`.`uid` LEFT JOIN ".C('DB_PREFIX')."house_village_user_bind as hvu ON d.log_bind_id=hvu.pigcms_id";
                $count = M('House_user_log')->join($left_join)->where($log_where)->count();
                import('@.ORG.merchant_page');
                $p = new Page($count,'20','page');
                $field = '`d`.*,`u`.`phone`, `u`.`nickname`,`hvu`.`name` as village_bind_name,`hvu`.`phone` as village_bind_phone,`hvu`.`address` as village_bind_address';
                $door_list = M('House_user_log')->join($left_join)->field($field)->where($log_where)->order('`d`.`log_time` DESC')->limit($p->firstRow.','.$p->listRows)->select();

                if ($door_list) {
                    foreach ($door_list as &$item) {
                        if ($item['log_detail']) {
                            $item['log_detail'] = unserialize($item['log_detail']);
                        }
                        if (!$item['village_bind_name'] && $item['nickname']) {
                            $item['village_bind_name'] = $item['nickname'];
                        }
                        if (!$item['village_bind_phone'] && $item['phone']) {
                            $item['village_bind_phone'] = $item['phone'];
                        }
                        if (!$item['village_bind_address'] && $item['log_name']) {
                            $item['village_bind_address'] = $item['log_name'];
                        }
//                        $item['village_bind'] = $d_house_village_user_bind->get_one_by_bindId($item['log_bind_id']);
                    }
                }
                fdump($door_list,'$door_list');

                $this->assign('door_list',$door_list);
                $this->assign('pagebar',$p->show());
                $this->display();
            } else {
                import('@.ORG.merchant_page');
                $p = new Page($count,'20','page');
                $door_list = $door_info['data'];

                $this->assign('door_list',$door_list);
                $this->assign('pagebar',$p->show());
                $this->display('a2_door_open_list');
            }
        } 
        elseif (6==$nowDoor['device_type']){
            $log_where['device_id']=$_GET['device_id'];
            $log_where['log_from'] = 6;
            if(!empty($start_time)&&empty($end_time)){
                $start_time = strtotime($start_time." 00:00:00");
                $log_where['d.log_time'] =  array('egt',$start_time);
            }
            if(empty($start_time)&&!empty($end_time)){
                $end_time = strtotime($end_time."  23:59:59");
                $log_where['d.log_time'] =  array('elt',$end_time);
            }
            if(!empty($start_time)&&!empty($end_time)){
                $start_time = strtotime($start_time." 00:00:00");
                $end_time = strtotime($end_time."  23:59:59");
                if ($start_time>$end_time) {
                    $this->error("结束时间应大于开始时间");
                }
                $log_where['d.log_time'] =  array('between',$start_time.','.$end_time);
            }
            $count = M('House_user_log')->where($log_where)->count();
            $p = new Page($count,'20','page');
            $door_list = M('House_user_log')->where($log_where)->order('log_time DESC')->limit($p->firstRow.','.$p->listRows)->select();
            if($door_list){
                foreach ($door_list as &$item) {
                    if ($item['log_detail']) {
                        $item['log_detail'] = unserialize($item['log_detail']);
                    }
                }
            }
            $this->assign('door_list',$door_list);
            $this->assign('pagebar',$p->show());
            $this->display();
        }
        elseif (26==$nowDoor['device_type']){
            //人脸门禁记录列表
            $log_where = array(
                'd.device_id' => $nowDoor['device_id'],
            );
            if ($find_type && 'name'==$find_type && $find_value) {
                $log_where['u.nickname|hvu.name'] =  array('like',  '%' . $find_value . '%');
            } elseif ($find_type && 'phone'==$find_type && $find_value) {
                $log_where['u.phone|hvu.phone'] =  array('like',  '%' . $find_value . '%');
            }
            if (1==$status) {
                // 成功开门的
                $log_where['d.log_status'] = 0;
            } elseif (2==$status) {
                // 开门失败的
                $log_where['d.log_status'] = array('neq', 0);
            }

            if(!empty($start_time)&&empty($end_time)){
                $start_time = strtotime($start_time." 00:00:00");
                $log_where['d.log_time'] =  array('egt',$start_time);
            }
            if(empty($start_time)&&!empty($end_time)){
                $end_time = strtotime($end_time."  23:59:59");
                $log_where['d.log_time'] =  array('elt',$end_time);
            }
            if(!empty($start_time)&&!empty($end_time)){
                $start_time = strtotime($start_time." 00:00:00");
                $end_time = strtotime($end_time."  23:59:59");
                if ($start_time>$end_time) {
                    $this->error("结束时间应大于开始时间");
                }
                $log_where['d.log_time'] =  array('between',$start_time.','.$end_time);
            }

            $left_join = "as d LEFT JOIN ".C('DB_PREFIX')."user as u ON `d`.`uid`=`u`.`uid` LEFT JOIN ".C('DB_PREFIX')."house_village_user_bind as hvu ON d.log_bind_id=hvu.pigcms_id";
            $count = M('House_user_log')->join($left_join)->where($log_where)->count();
            import('@.ORG.merchant_page');
            $p = new Page($count,'20','page');
            $field = '`d`.*,`u`.`phone`, `u`.`nickname`,`hvu`.`village_id`,`hvu`.`single_id`,`hvu`.`floor_id`,`hvu`.`layer_id`,`hvu`.`vacancy_id`,`hvu`.`name` as village_bind_name,`hvu`.`phone` as village_bind_phone,`hvu`.`address` as village_bind_address';
            $door_list = M('House_user_log')->join($left_join)->field($field)->where($log_where)->order('`d`.`log_time` DESC')->limit($p->firstRow.','.$p->listRows)->select();

            if ($door_list) {
                $db_house_face_device = D('House_face_device');
                foreach ($door_list as &$item) {
                    $log_from_txt = $db_house_face_device->getLogFromText($item['log_from'], $item['card_number']);
                    $item['log_from_txt'] = $log_from_txt;
                    if ($item['log_from']==31&&$item['log_status']==1){
                        $item['log_status']=0;
                    }
                    if ($item['log_detail']) {
                        $log_detail = $item['log_detail'];
                        $item['log_detail'] = unserialize($item['log_detail']);
                        if (!$item['log_detail']) {
                            $item['log_detail'] = json_decode($log_detail, true);
                        }
                        if ($item['log_detail'] && $item['log_detail']['face_img']) {
                            if ($item['log_detail']['imgUrl']) {
                                $item['faceImg1'] = $item['log_detail']['imgUrl'];
                            } elseif ($item['log_detail']['mediaUrls']) {
                                $item['faceImg1'] = $item['log_detail']['mediaUrls'];
                            } elseif ($item['log_detail']['pictureURL']) {
                                $item['faceImg1'] = $item['log_detail']['pictureURL'];
                            } elseif ($item['log_detail']['faceCapture'][0]['bkgUrl']) {
                                $item['faceImg1'] = $item['log_detail']['faceCapture'][0]['bkgUrl'];
                            } elseif ($item['log_detail']['face_img_url']) {
                                $item['faceImg1'] = $item['log_detail']['face_img_url'];
                            } elseif ($item['log_detail']['face_img_url']) {
                                $item['faceImg1'] = $item['log_detail']['face_img_url'];
                            } elseif ($item['log_detail']['capture_img_url']) {
                                $item['faceImg1'] = $item['log_detail']['capture_img_url'];
                            } elseif ($item['log_detail']['capture_img']) {
                                $item['faceImg1'] = $item['log_detail']['capture_img'];
                            } else {
                                $item['faceImg1'] = "/static/images/live_goods_default.png";
                            }
                        }
                    }
                    if (!$item['nickname'] && !$item['phone'] && $item['log_detail']) {
                        $info = $item['log_detail']['info'];
                        $info_arr = json_decode($info, true);
                        // 用户信息数组
                        $target_info = $info_arr['Target'];
                        $target_msg = reset($target_info);
                        $person = $target_msg['person'];
                        if ($person) {
                            $item['nickname'] = $person['name']?$person['name']:'';
                            if (!$item['village_bind_address']) {
                                $item['village_bind_address'] = $person['address']?$person['address']:'';
                            }
                        }
                    }
                    if (!$item['village_bind_name'] && $item['nickname']) {
                        $item['village_bind_name'] = $item['nickname'];
                    } elseif (isset($item['log_detail']['personName']) && $item['log_detail']['personName']) {
                        $item['village_bind_name'] = $item['log_detail']['personName'];
                    }
                    if (!$item['village_bind_phone'] && $item['phone']) {
                        $item['village_bind_phone'] = $item['phone'];
                    }
                    if (!$item['village_bind_address'] && isset($item['vacancy_id']) && $item['vacancy_id']) {
                        $village_bind_address = D('House_village')->get_single_floor_room($item['single_id'],$item['floor_id'],$item['layer_id'],$item['vacancy_id'],$item['village_id']);
                    } else {
                        $village_bind_address = '';
                    }
                    if (!$item['village_bind_address'] && $village_bind_address) {
                        $item['village_bind_address'] = $village_bind_address;
                    }
                    if (!$item['village_bind_address'] && $item['log_name']) {
                        $item['village_bind_address'] = $item['log_name'];
                    }
                }
            }
            $this->assign('door_list',$door_list);
            $this->assign('pagebar',$p->show());
            $this->display();
        }
        else {
            if (4==$nowDoor['device_type']) {
                $log_time_max = 0;
                // todo 过滤蓝牙开门记录
                $house_user_log = M('House_user_log')->where([
                    'village_id'=>$this->house_session['village_id'],
                    'log_from'=>1,
                      'device_id'=>$nowDoor['device_id'],
                ])->field('MAX(log_time) as log_time_max')->find();

                if ($house_user_log['log_time_max']) {
                    $log_time_max = $house_user_log['log_time_max'];
                }
                D('Face_door_a4_service')->getA4SearchUnlockRecordCount($this->house_session['village_id'],0,$log_time_max);
            } 
            elseif (21==$nowDoor['device_type']) {
                D('Face_door_d1_service')->d1_open_door_list($nowDoor['device_id'], $nowDoor);
            }
            //人脸门禁记录列表
            $log_where = array(
                'device_id' => $nowDoor['device_id'],
                'log_from'=>['in',[1,2,3,31,45]]
            );
            if ($find_type && 'name'==$find_type && $find_value) {
                $whereUserBind = [
                    'village_id' => $village_id,
                    'name' => $find_value,
                ];
                $ids = M('House_village_user_bind')->where($whereUserBind)->getField('pigcms_id', true);
                if (! empty($ids) && count($ids) == 1) {
                    $pigcms_id = reset($ids);
                    $log_where['log_bind_id'] = $pigcms_id;
                } else {
                    $log_where['log_bind_id'] = array('in',$ids);
                }
            } elseif ($find_type && 'phone'==$find_type && $find_value) {
                $whereUserBind = [
                    'village_id' => $village_id,
                    'phone' => $find_value,
                ];
                $ids = M('House_village_user_bind')->where($whereUserBind)->getField('pigcms_id', true);
                if (! empty($ids) && count($ids) == 1) {
                    $pigcms_id = reset($ids);
                    $log_where['log_bind_id'] = $pigcms_id;
                } else {
                    $log_where['log_bind_id'] = array('in',$ids);
                }
            }
            if (1==$status) {
                // 成功开门的
                $log_where['log_status'] = 0;
            } elseif (2==$status) {
                // 开门失败的
                $log_where['log_status'] = array('neq', 0);
            }

            if(!empty($start_time)&&empty($end_time)){
                $start_time = strtotime($start_time." 00:00:00");
                $log_where['log_time'] =  array('egt',$start_time);
            }
            if(empty($start_time)&&!empty($end_time)){
                $end_time = strtotime($end_time."  23:59:59");
                $log_where['log_time'] =  array('elt',$end_time);
            }
            if(!empty($start_time)&&!empty($end_time)){
                $start_time = strtotime($start_time." 00:00:00");
                $end_time = strtotime($end_time."  23:59:59");
                if ($start_time>$end_time) {
                    $this->error("结束时间应大于开始时间");
                }
                $log_where['log_time'] =  array('between',$start_time.','.$end_time);
            }

//            $left_join = "as d LEFT JOIN ".C('DB_PREFIX')."user as u ON `d`.`uid`=`u`.`uid` LEFT JOIN ".C('DB_PREFIX')."house_village_user_bind as hvu ON d.log_bind_id=hvu.pigcms_id";
//            $count = M('House_user_log')->join($left_join)->where($log_where)->count();
//            import('@.ORG.merchant_page');
//            $p = new Page($count,'20','page');
//            $field = '`d`.*,`u`.`phone`, `u`.`nickname`,`hvu`.`village_id`,`hvu`.`single_id`,`hvu`.`floor_id`,`hvu`.`layer_id`,`hvu`.`vacancy_id`,`hvu`.`name` as village_bind_name,`hvu`.`phone` as village_bind_phone,`hvu`.`address` as village_bind_address';
//            $door_list = M('House_user_log')->join($left_join)->field($field)->where($log_where)->order('`d`.`log_time` DESC')->limit($p->firstRow.','.$p->listRows)->select();
            
            $count = M('House_user_log')->where($log_where)->count();
            import('@.ORG.merchant_page');
            $p = new Page($count,'20','page');
            $door_list = M('House_user_log')->where($log_where)->order('`log_time` DESC')->limit($p->firstRow.','.$p->listRows)->select();
            
            if ($door_list) {
                $db_house_face_device = D('House_face_device');
                $deviceIds = array_unique(array_column($door_list, 'device_id'));
                $whereDeviceAbout = [
                    'device_id' => ['in', $deviceIds],
                ];
                $deviceInfoArr = $db_house_face_device->where($whereDeviceAbout)
                    ->getField('device_id, village_id, device_id, device_name, device_sn, device_type');

                $uids = array_unique(array_column($door_list, 'uid'));
                $whereUserAbout = [
                    'uid' => ['in', $uids],
                ];
                $userInfoArr = D('User')->where($whereUserAbout)
                    ->getField('uid, uid, nickname, phone');

                $pigcmsIds = array_unique(array_column($door_list, 'log_bind_id'));
                $whereUserBindAbout = [
                    'pigcms_id' => ['in', $pigcmsIds],
                ];
                $userBindInfoArr = D('House_village_user_bind')->where($whereUserBindAbout)
                    ->getField('pigcms_id, pigcms_id, village_id, single_id, floor_id, layer_id, vacancy_id, name, id_card, address');
                
                $configSiteUrl = $this->config['site_url'];
                $d_Face_door_xihan_service = D('Face_door_xihan_service');
                foreach ($door_list as &$item) {
                    $device_id = isset($item['device_id']) && $item['device_id'] ? intval($item['device_id']) : 0;
                    $deviceInfo = isset($deviceInfoArr[$device_id]) && $deviceInfoArr[$device_id] ? $deviceInfoArr[$device_id] : [];
                    $device_type = isset($deviceInfo['device_type']) ? $deviceInfo['device_type'] : '';
                    if (in_array($device_type,[5,7,21,30])) {
                        $is_temp = true;
                    }
                    $item['device_name'] = isset($deviceInfo['device_name']) ? $deviceInfo['device_name'] : '';
                    $item['device_sn'] = isset($deviceInfo['device_sn']) ? $deviceInfo['device_sn'] : '';
                    $item['village_id'] = isset($deviceInfo['village_id']) ? $deviceInfo['village_id'] : '';

                    $uid = isset($item['uid']) && $item['uid'] ? intval($item['uid']) : 0;
                    $userInfo = isset($userInfoArr[$uid]) && $userInfoArr[$uid] ? $userInfoArr[$uid] : [];
                    $item['phone'] = isset($userInfo['phone']) ? $userInfo['phone'] : '';
                    $item['nickname'] = isset($userInfo['nickname']) ? $userInfo['nickname'] : '';

                    $log_bind_id = isset($item['log_bind_id']) && $item['log_bind_id'] ? intval($item['log_bind_id']) : 0;
                    $userBindInfo = isset($userBindInfoArr[$log_bind_id]) && $userBindInfoArr[$log_bind_id] ? $userBindInfoArr[$log_bind_id] : [];
                    if (empty($item['village_id'])) {
                        $item['village_id'] = isset($userBindInfo['village_id']) ? $userBindInfo['village_id'] : '';
                    }
                    $item['single_id'] = isset($userBindInfo['single_id']) ? $userBindInfo['single_id'] : '';
                    $item['floor_id'] = isset($userBindInfo['floor_id']) ? $userBindInfo['floor_id'] : '';
                    $item['layer_id'] = isset($userBindInfo['layer_id']) ? $userBindInfo['layer_id'] : '';
                    $item['vacancy_id'] = isset($userBindInfo['vacancy_id']) ? $userBindInfo['vacancy_id'] : '';
                    $item['village_bind_name'] = isset($userBindInfo['name']) ? $userBindInfo['name'] : '';
                    $item['id_card'] = isset($userBindInfo['id_card']) ? $userBindInfo['id_card'] : '';
                    $item['village_bind_address'] = isset($userBindInfo['address']) ? $userBindInfo['address'] : '';
                    
                    $card_number_type = $item['card_number_type'] ? $item['card_number_type'] : 0;
                    $item['cardNumberTypeText'] = $d_Face_door_xihan_service->getCardNumberType($card_number_type);
                    $log_from_txt = $db_house_face_device->getLogFromText($item['log_from'], $item['card_number']);
                    if (isset($item['device_event_msg']) && trim($item['device_event_msg'])) {
                        $log_from_txt = $item['device_event_msg'];
                    }
                    $item['log_from_txt'] = $log_from_txt;
                    if ($item['log_detail']) {
                        $log_detail = $item['log_detail'];
                        $item['log_detail'] = unserialize($item['log_detail']);
                        if (!$item['log_detail']) {
                            $item['log_detail'] = json_decode($log_detail, true);
                        }
                        if ($item['log_detail']) {
                            if ($item['log_detail']['imgUrl']) {
                                $item['faceImg1'] = $item['log_detail']['imgUrl'];
                            } elseif ($item['log_detail']['mediaUrls']) {
                                $item['faceImg1'] = $item['log_detail']['mediaUrls'];
                            } elseif ($item['log_detail']['pictureURL']) {
                                $item['faceImg1'] = $item['log_detail']['pictureURL'];
                            } elseif ($item['log_detail']['faceCapture'][0]['bkgUrl']) {
                                $item['faceImg1'] = $item['log_detail']['faceCapture'][0]['bkgUrl'];
                            } elseif ($item['log_detail']['face_img_url']) {
                                $item['faceImg1'] = $item['log_detail']['face_img_url'];
                            } elseif ($item['log_detail']['capture_img_url']) {
                                $item['faceImg1'] = $item['log_detail']['capture_img_url'];
                            } elseif ($item['log_detail']['capture_img']) {
                                $item['faceImg1'] = $item['log_detail']['capture_img'];
                            } elseif ($item['log_detail']['face_img']) {
                                $item['faceImg1'] = $item['log_detail']['face_img'];
                            }
                        }
                    }
                    if (!$item['faceImg1'] && $item['cut_image']) {
                        $item['faceImg1'] = $item['cut_image'];
                    } elseif (!$item['faceImg1']) {
                        $item['faceImg1'] = "/static/images/live_goods_default.png";
                    }
                    $item['onerrorImg'] = "/static/images/live_goods_default.png";
                    if (isset($item['faceImg1']) && $item['faceImg1']) {
                        $stripos_result = stripos($item['faceImg1'], '/upload/face_log/');
                        if ($stripos_result !== false) {
                            if ($stripos_result === 0 && file_exists('.' . $item['faceImg1'])) {
                                $item['onerrorImg'] = $configSiteUrl . $item['faceImg1'];
                            }
                        }
                    }
                    if ($item['person_name']) {
                        $item['village_bind_name'] = $item['person_name'];
                    }
                    if (!$item['nickname'] && !$item['phone'] && $item['log_detail']) {
                        $info = $item['log_detail']['info'];
                        $info_arr = json_decode($info, true);
                        // 用户信息数组
                        $target_info = $info_arr['Target'];
                        $target_msg = reset($target_info);
                        $person = $target_msg['person'];
                        if ($person) {
                            $item['nickname'] = $person['name']?$person['name']:'';
                            if (!$item['village_bind_address']) {
                                $item['village_bind_address'] = $person['address']?$person['address']:'';
                            }
                        }
                    }
                    if (!$item['village_bind_name'] && $item['nickname']) {
                        $item['village_bind_name'] = $item['nickname'];
                    } elseif (isset($item['log_detail']['personName']) && $item['log_detail']['personName']) {
                        $item['village_bind_name'] = $item['log_detail']['personName'];
                    }
                    !$item['village_bind_name'] && $item['village_bind_name'] = '';
                    if (!$item['village_bind_phone'] && $item['phone']) {
                        $item['village_bind_phone'] = $item['phone'];
                    }
                    if (!$item['village_bind_address'] && isset($item['vacancy_id']) && $item['vacancy_id']) {
                        $village_bind_address = D('House_village')->get_single_floor_room($item['single_id'],$item['floor_id'],$item['layer_id'],$item['vacancy_id'],$item['village_id']);
                    } else {
                        $village_bind_address = '';
                    }
                    if (!$item['village_bind_address'] && $village_bind_address) {
                        $item['village_bind_address'] = $village_bind_address;
                    }
                    if (!$item['village_bind_address'] && $item['log_name']) {
                        $item['village_bind_address'] = $item['log_name'];
                    }
                }
            }
            $this->assign('door_list',$door_list);
            $this->assign('pagebar',$p->show());
            $this->display();
        }
    }


    // 重启设备
    public function device_restart() {
        if (!in_array(502, $this->house_session['menus'])) {
            $array = array(
                'errorCode'=>1001,
                'errorMsg'=>'对不起，您没有权限执行此操作',
            );
            exit(json_encode($array));
        }
        $device_id = $_POST['device_id'];
        $d_house_face_device = M('House_face_device');
        $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
        if (empty($house_face_device_info)) {
            $array = array(
                'errorCode'=>1001,
                'errorMsg'=>'对不起，当前设备不存在或者已经被删除',
            );
            exit(json_encode($array));
        }

        $device_sn = $house_face_device_info['device_sn'];


        $orderGroupId = md5(uniqid().$_SERVER['REQUEST_TIME']);
        $param = [
            'log_name' => 'adminRestartDevice','logType' => 'houseAdmin','log_status' => 8,'orderGroupId' => $orderGroupId,
            'log_content' => '重启设备','device_id' => $device_id,'device_sn' => $device_sn,
            'device_type' => $house_face_device_info['device_type']?$house_face_device_info['device_type']:'','village_id' => $house_face_device_info['village_id']?$house_face_device_info['village_id']:0,
            'addTime' => time(),
        ];
        try {
            D('House_face_device_log')->addLog($param);
        } catch( \Exception $e) {
            // 记录日志报错
            fdump_api(['日志记录[重启设备]出错'.__LINE__.'方法'.__FUNCTION__,$param,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
        }
        $otherParam = [
            'orderGroupId' => $orderGroupId,
            'operatorType' => 'admin',
        ];
        
        $thirdProtocol = isset($house_face_device_info['thirdProtocol']) && $house_face_device_info['thirdProtocol'] ? intval($house_face_device_info['thirdProtocol']) : 0;
        $hfd = D('House_face_device');
        $hikGateKeyVillage = $hfd::HIK_DEVICE_GATEWAY_CUSTOM;
        $hikGateKey = $hfd::HIK_DEVICE_GATEWAY;
        if ($thirdProtocol && in_array($thirdProtocol, [$hikGateKey, $hikGateKeyVillage])) {
            $models = '\app\community\model\service\Device\HikGateWayDeviceService/rebootDeviceBox';
            $param = [
                'param'     => [
                    'village_id'    => $house_face_device_info['village_id'],
                    'device_id'     => $device_id,
                    'operation'     => 'rebootDevice',
                    'device_ip'     => $house_face_device_info['device_ip'],
                    'orderGroupId'  => $orderGroupId,
                    'thirdProtocol' => $thirdProtocol,
                    'deviceType'    => 'face',
                ]
            ];
            $rebootDeviceInfo = invoke_v20_service($models,$param);
            $rebootDevice = $rebootDeviceInfo['data']['retval'];
            if (isset($rebootDevice['code']) && (!isset($rebootDevice['code']) || !$rebootDevice['code'] || $rebootDevice['code'] == 200)) {
                exit(json_encode(array('errorCode' => 0,'result' => $rebootDevice, 'errorMsg' => '重启成功！')));
            } elseif (isset($rebootDevice['message']) && $rebootDevice['message']) {
                exit(json_encode(array('errorCode' => 1,'result' => $rebootDevice, 'errorMsg' => $rebootDevice['message'])));
            }
        }
        if (31 == intval($house_face_device_info['device_type'])) {
            // 西函门禁
            $db_house_face_device_cmd = M('House_face_device_cmd');
            $nowTime = time();
            // 批量操作配置下发设备
            // 去重下
            $whereCmd = [
                'village_id' => $house_face_device_info['village_id'], 'device_id' => $device_id, 
                'cmd_name' => 'sysHelper.reboot', 'exec_status' => 0,
            ];
            $cmdInfo = $db_house_face_device_cmd->where($whereCmd)->find();
            if (empty($cmdInfo)) {
                $cmdParams = [
                    'village_id' => $house_face_device_info['village_id'], 'cmd_name' => 'sysHelper.reboot', 'add_time' => $nowTime,
                    'exec_status' => 0, 'device_id' => $device_id, 'device_sn' => $house_face_device_info['device_sn'],
                    'device_type' => $house_face_device_info['device_type'], 'device_type_str' => 'xihan',
                    'relate_id' => $device_id, 'relate_type' => 'house_face_device'
                ];
                $cmd_data = [
                    'method' => 'sysHelper.reboot',
                    'params' => [],
                ];
                $dataCmd = $cmdParams;
                $dataCmd['cmd_data'] = json_encode($cmd_data,JSON_UNESCAPED_UNICODE);
                $db_house_face_device_cmd->add($dataCmd);
            }
        }
        if (30 === intval($house_face_device_info['device_type'])) {
            // 260云 将设备相关信息加入指令中等候
            $login_time = isset($house_face_device_info['login_time']) ? intval($house_face_device_info['login_time']) : 0;
            $parameterPolling = D('House_village_device_cmd')::PARAMETER_POLLING;
            $spaceTimeCondition = intval($parameterPolling) * 10; // 判断离线间隔时间
            $spaceTime = time() - $login_time;
            if ($login_time === 0 || $spaceTime > $spaceTimeCondition) {
                $array = array(
                    'errorCode'=>1001,
                    'errorMsg'=>'设备不在线请先检查网络！'
                );
                exit(json_encode($array));
            }
            $data = [];
            $data['device_id'] = $device_id;
            $data['device_sn'] = $device_sn;
            $data['restart'] = 1;
            $data['device_type'] = 30;
            D('House_village_device_cmd')->addOrUpdateRestartCmd($data);
        }
        if (1==$house_face_device_info['device_type']) {
            D('House_face_img')->device_restart($device_id,$device_sn);
        } elseif (3==$house_face_device_info['device_type']) {
            $a3_device_id = $house_face_device_info['a3_device_id'];
            if (D('House_village_face_door')->base_face_a3_url && D('House_village_face_door')->a3_token && D('House_village_face_door')->a3_key) {
                D('House_village_face_door')->device_a3_restart($device_id,$a3_device_id);
            } else {
                $array = array(
                    'errorCode'=>1001,
                    'errorMsg'=>'同步失败，未进行配置，请前往 智慧社区-社区配置-A3人脸识别门禁 配置！'
                );
                exit(json_encode($array));
            }
        } elseif (7==$house_face_device_info['device_type']) {
            D('Face_door_a7_service')->face_a7_reboot_cam($device_id, $house_face_device_info['village_id'], $house_face_device_info);
        }elseif ($house_face_device_info['device_type'] == 21){
            D('Face_door_d1_service')->d1_restart_gate($device_id, $house_face_device_info['ai_device_sn'], $house_face_device_info);
        }elseif ($house_face_device_info['device_type'] == 23){
            D('Face_d3_request_record')->d3_reboot_cam($device_id, $house_face_device_info['village_id'], $house_face_device_info);
        }
        elseif ($house_face_device_info['device_type'] == 24) {
            //todo D4智能门禁 重启设备
            D('Face_d4_request_record')->d4_restart($house_face_device_info);

        }elseif ($house_face_device_info['device_type'] == 61){
            D('Face_a185_request_record')->a185_reboot_cam($device_id, $house_face_device_info['village_id'], $house_face_device_info);
        }elseif ($house_face_device_info['device_type'] == 26){
            D('Face_d6_request_record')->d6_reboot_cam($device_id, $house_face_device_info['village_id'], $house_face_device_info);
        }
        $array = array(
            'errorCode'=>0,
            'result'=>$house_face_device_info
        );
        exit(json_encode($array));
    }

    // 人脸设备一键同步-用户相关信息
    public function device_all_sync_per() {
        if (!in_array(500, $this->house_session['menus']) && !in_array(501, $this->house_session['menus'])) {
            $array = array(
                'errorCode'=>1001,
                'errorMsg'=>'对不起，您没有权限执行此操作',
            );
            exit(json_encode($array));
        }
        $device_id = $_POST['device_id'];
        $d_house_face_device = M('House_face_device');
        $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
        if (empty($house_face_device_info)) {
            $array = array(
                'errorCode'=>1001,
                'errorMsg'=>'对不起，当前设备不存在或者已经被删除',
            );
            exit(json_encode($array));
        }
        if (30 == intval($house_face_device_info['device_type'])) {
            // 260云 将设备相关信息加入指令中等候
            $login_time = isset($house_face_device_info['login_time']) ? intval($house_face_device_info['login_time']) : 0;
            $parameterPolling = D('House_village_device_cmd')::PARAMETER_POLLING;
            $spaceTimeCondition = intval($parameterPolling) * 10; // 判断离线间隔时间
            $spaceTime = time() - $login_time;
            if ($login_time === 0 || $spaceTime > $spaceTimeCondition) {
                $array = array(
                    'errorCode'=>1001,
                    'errorMsg'=>'设备不在线请先检查网络！'
                );
                exit(json_encode($array));
            }
            $models = '\app\community\model\service\Device\HouseVillageDeviceCmdService/deviceAllSyncPer';
            $param = [
                'params' => [
                    'village_id'    => $house_face_device_info['village_id'],
                    'device_id'     => $device_id,
                    'device_sn'     => $house_face_device_info['device_sn'],
                    'device_type'   => $house_face_device_info['device_type'],
                ]
            ];
            $info = invoke_v20_service($models,$param);
            fdump_api(['msg' => '发起一键同步', 'models' => $models, 'param' => $param, 'info' => $info], '260Cloud/deviceAllSyncPer');
        } elseif (29 == intval($house_face_device_info['device_type'])) {
            // 朵普 将设备相关信息加入指令中等候
            $models = '\app\community\model\service\CloudIntercomService/deviceAllSyncPer';
            $param = [
                'params' => [
                    'village_id'    => $house_face_device_info['village_id'],
                    'device_id'     => $device_id,
                    'device_sn'     => $house_face_device_info['device_sn'],
                    'device_type'   => $house_face_device_info['device_type'],
                ]
            ];
            $info = invoke_v20_service($models,$param);
            fdump_api(['msg' => '发起一键同步', 'models' => $models, 'param' => $param, 'info' => $info], 'duopu/deviceAllSyncPer');
        } elseif (3==$house_face_device_info['device_type']) {
            if($device_task_count = M('Process_sub_plan')->where(['unique_id' => ['like', 'a3_face_device_' . $device_id . '%']])->count()){
                $array = array(
                    'errorCode'=>1001,
                    'errorMsg'=>'目前设备还有' . $device_task_count . '个人员正在同步中，请耐心等待！'
                );
                exit(json_encode($array));
            }
            if (D('House_village_face_door')->base_face_a3_url && D('House_village_face_door')->a3_token && D('House_village_face_door')->a3_key) {
                D('House_village_face_door')->face_a3_door_add($device_id,$house_face_device_info);
            } else {
                $array = array(
                    'errorCode'=>1001,
                    'errorMsg'=>'同步失败，未进行配置，请前往 智慧社区-社区配置-A3人脸识别门禁 配置！'
                );
                exit(json_encode($array));
            }
        }
        elseif (7==$house_face_device_info['device_type']) {
            D('Face_door_a7_service')->face_a7_all_sync_per($device_id, $house_face_device_info);
        }
        elseif (4==$house_face_device_info['device_type']) {
            D('Face_door_service')->face_device_check($device_id, $house_face_device_info);
        }
        elseif ($house_face_device_info['device_type'] == 21){
            D('Face_door_d1_service')->face_d1_door_syn($device_id, $house_face_device_info, 'all_syn',[]);
        }
        elseif ($house_face_device_info['device_type'] == 61){
            /**
             *
             * todo A185同步 */
            D('Face_door_a7_service')->face_a7_all_sync_per($device_id, $house_face_device_info);
        }
        elseif ($house_face_device_info['device_type'] == 23){
            /**
             *
             * todo d3同步
             */
            D('Face_door_a7_service')->face_a7_all_sync_per($device_id, $house_face_device_info);
        }
        elseif ($house_face_device_info['device_type'] == 24){
            /**
             *
             * todo D4智能门禁 一键同步
             */
            D('Face_door_a7_service')->face_a7_all_sync_per($device_id, $house_face_device_info);
        }elseif ($house_face_device_info['device_type'] == 26){
            /**
             *
             * todo D4智能门禁 一键同步
             */
            D('Face_door_a7_service')->face_a7_all_sync_per($device_id, $house_face_device_info);
        }
        $array = array(
            'errorCode'=>0,
            'result'=>$house_face_device_info
        );
        exit(json_encode($array));
    }

    // A3设备同步
    public function sync_device_a3_restart() {
        if (!in_array(500, $this->house_session['menus'])) {
            $array = array(
                'errorCode'=>1001,
                'errorMsg'=>'对不起，您没有权限执行此操作',
            );
            exit(json_encode($array));
        }
        $device_id = $_POST['device_id'];
        $d_house_face_device = M('House_face_device');
        $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
        if (empty($house_face_device_info)) {
            $array = array(
                'errorCode'=>1001,
                'errorMsg'=>'对不起，当前设备不存在或者已经被删除',
            );
            exit(json_encode($array));
        }
        if (D('House_village_face_door')->base_face_a3_url && D('House_village_face_door')->a3_token && D('House_village_face_door')->a3_key) {
            D('House_village_face_door')->face_a3_door_add($device_id,$house_face_device_info);
        } else {
            $array = array(
                'errorCode'=>1001,
                'errorMsg'=>'同步失败，未进行配置，请前往 智慧社区-社区配置-A3人脸识别门禁 配置！'
            );
            exit(json_encode($array));
        }
        $array = array(
            'errorCode'=>0,
            'result'=>$house_face_device_info
        );
        exit(json_encode($array));
    }

    // A3重启设备
    public function device_a3_restart() {
        if (!in_array(502, $this->house_session['menus'])) {
            $array = array(
                'errorCode'=>1001,
                'errorMsg'=>'对不起，您没有权限执行此操作',
            );
            exit(json_encode($array));
        }
        $device_id = $_POST['device_id'];
        $d_house_face_device = M('House_face_device');
        $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
        if (empty($house_face_device_info)) {
            $array = array(
                'errorCode'=>1001,
                'errorMsg'=>'对不起，当前设备不存在或者已经被删除',
            );
            exit(json_encode($array));
        }
        $a3_device_id = $house_face_device_info['a3_device_id'];
        if (D('House_village_face_door')->base_face_a3_url && D('House_village_face_door')->a3_token && D('House_village_face_door')->a3_key) {
            D('House_village_face_door')->device_a3_restart($device_id,$a3_device_id);
        } else {
            $array = array(
                'errorCode'=>1001,
                'errorMsg'=>'同步失败，未进行配置，请前往 智慧社区-社区配置-A3人脸识别门禁 配置！'
            );
            exit(json_encode($array));
        }
        $array = array(
            'errorCode'=>0,
            'result'=>$house_face_device_info
        );
        exit(json_encode($array));
    }

    /**人脸识别门禁A2相关*/
    // 同步社区信息
    public function sync_village_data() {
        $village_id	= $this->house_session['village_id'];

        $info_face_device = D('House_face_device')->where(array('village_id'=>$village_id,'a2_face_door_doorkeeperId' => array('gt',0),'device_type'=>array('gt', 1)))->select();
        if (empty($info_face_device)) {
            $result = array('code'=>0,'msg'=>'请先添加设备或者同步设备');
            echo json_encode($result);
        }
        D('House_village_face_door')->sync_house_village_info($village_id);
    }

    // 同步钥匙
    public function sync_user_data() {
        $village_id	= $this->house_session['village_id'];

        $info_face_device = D('House_face_device')->where(array('village_id'=>$village_id,'a2_face_door_doorkeeperId' => array('gt',0),'device_type'=>array('gt', 1)))->select();
        if (empty($info_face_device)) {
            $result = array('code'=>0,'msg'=>'请先添加设备或者同步设备');
            echo json_encode($result);
        }
        D('House_village_face_door')->sync_user($village_id);
    }

    // 同步设备
    public function sync_device_restart() {
        if (!in_array(500, $this->house_session['menus'])) {
            $array = array(
                'errorCode'=>1001,
                'errorMsg'=>'对不起，您没有权限执行此操作',
            );
            exit(json_encode($array));
        }
        $device_id = $_POST['device_id'];
        $mHouse_face_device = D('House_face_device');
        $house_face_device_info = $mHouse_face_device->where(array('device_id' => $device_id))->find();
        if (empty($house_face_device_info)) {
            $array = array(
                'errorCode'=>1001,
                'errorMsg'=>'对不起，当前设备不存在或者已经被删除',
            );
            exit(json_encode($array));
        }
        $orderGroupId = md5(uniqid().$_SERVER['REQUEST_TIME']);
        $otherParam = [
            'orderGroupId' => $orderGroupId,
            'operatorType' => 'admin',
        ];
        $thirdProtocol = isset($house_face_device_info['thirdProtocol']) && $house_face_device_info['thirdProtocol'] ? $house_face_device_info['thirdProtocol'] : 0;
        if ($thirdProtocol && in_array($thirdProtocol, $mHouse_face_device->newProtocol)) {
            // 目前仅支持 海康和大华新协议逻辑
            $models = '\app\community\model\service\FaceDeviceService/handleDevice';
            $param = [
                'device_sn' => $house_face_device_info['device_sn'],
                'param'     => [
                    'village_id'    => $house_face_device_info['village_id'],
                    'device_id'     => $device_id,
                    'operation'     => 'addDevice',
                    'device_ip'     => $house_face_device_info['device_ip'],
                    'orderGroupId'  => $orderGroupId,
                    'thirdProtocol' => $thirdProtocol,
                    'deviceType'    => 'face',
                ]
            ];
            $thirdAdd = invoke_v20_service($models,$param);
        } elseif($house_face_device_info['device_type'] == 3) {
            $info = D('House_village_face_door')->face_door_add($device_id, $house_face_device_info);
        }elseif ($house_face_device_info['device_type'] == 5 || $house_face_device_info['device_type'] == 6){
            $info = D('Face_door_a5_service')->addEquipment($device_id, $house_face_device_info,$otherParam);
        }elseif ($house_face_device_info['device_type'] == 4){
            D('Face_door_a4_service')->faceDeviceSynchro($device_id, $house_face_device_info, 0);
        }elseif ($house_face_device_info['device_type'] == 21){
            $info = D('Face_door_d1_service')->face_d1_door_syn($device_id, $house_face_device_info, 'device_syn',[]);
        }
        if (!$info['error']) {
            $array = array(
                'errorCode'=>0,
                'result'=>$house_face_device_info
            );
        } else {
            $array = array(
                'errorCode'=>1,
                'errorMsg'=>$info['msg']
            );
        }
        exit(json_encode($array));
    }

    /**人脸识别摄像头*/
    public function camera_list() {
        //人脸识别门禁设备设置-查看 权限
        if (!in_array(286, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $where=array();
        $where['thirdProtocol'] = ['lt', '1'];
        $where['brand_id'] = ['lt', '1'];
        $where['device_brand'] = ['neq', 'iSecureCenter'];
        $where['village_id'] = $this->house_session['village_id'];
        $page		=	I('page',1);
        $page_count	=	I('page_count',20);
        $camera	=	D('House_face_device')->get_camera_door_list($where,$page,$page_count);
        $this->assign('list',$camera['list']);
        $this->assign('pagebar',$camera['pagebar']);

        $this->display();
    }



    public function device_channel_list() {
        $where['camera_id'] = I('camera_id');
        if(empty($where['camera_id'])){
            $where['camera_id']	= $_GET['camera_id'];
        }
        if(empty($where)){
            $this->error('设备未找到');exit;
        }
        $camera_device = D('House_camera_device')->where($where)->find();
        D('House_camera_device')->geChannelList($camera_device['camera_sn'],$camera_device['camera_id'],$camera_device['village_id']);

        $page		=	I('page',1);
        $page_count	=	I('page_count',20);
        $camera	=	D('House_camera_device')->get_house_device_channel_list($where,$page,$page_count);
        $this->assign('list',$camera['list']);
        $this->assign('pagebar',$camera['pagebar']);

        $this->display();
    }

    public function camera_add() {
        if($_POST){
            //门禁设置-修改 权限
            if (!in_array(286, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');exit;
            }
            $data = array();
            $data['village_id']	= $this->house_session['village_id'];
            if ($_POST['camera_name']) {
                $data['camera_name'] = $_POST['camera_name'];
            }
            if (!trim($_POST['camera_sn'])){
                $this->error('请您添加设备ID');exit;
            }
            $data['camera_sn'] = trim($_POST['camera_sn']);

            $blockWhitelistCameraDeviceBrandArr = D('House_face_device')->blockWhitelistCameraDeviceBrandArr;
            if (!in_array($_POST['device_brand'],$blockWhitelistCameraDeviceBrandArr)) {
                $url = $this->check_site_url . '/face_camera_check.php';
                $params['device_id'] = $data['camera_sn'];
                $check_info = httpRequest($url,'POST',$params);
                $msg = $check_info[1];
                if ('ok'!=$msg) {
                    $this->error('您添加的人脸抓拍摄像机设备没有记录，请联系管理员');exit;
                }
            }
            if (isset($_POST['is_support_look'])) {
                $data['is_support_look'] = $_POST['is_support_look'];
            }
            if (isset($_POST['look_url'])) {
                $data['look_url'] = $_POST['look_url'];
            }

            $data['floor_id']	=	$_POST['floor_id'];
            $single_id = I('single_id');
            $single_id = explode("_", $single_id);
            if ($single_id[0]=='door') {
                $data['floor_id'] = -1;
                $data['public_area_id'] = $single_id[1];
            } elseif($data['floor_id']>0) {
                $data['public_area_id'] = 0;
            }

            if($_POST['location']){
                $locationArr = explode(',',trim($_POST['location']));
                $data['lng'] = $locationArr[0];
                $data['lat'] = $locationArr[1];
            } elseif ($data['public_area_id']>0) {
                $public_area = M('House_village_public_area')->where(array('public_area_id' => $data['public_area_id']))->field('lng,lat')->find();
                $data['lng'] = $public_area['lng'];
                $data['lat'] = $public_area['lat'];
            } elseif ($data['floor_id']>0) {
                $floor_info = M('House_village_floor')->where(array('floor_id' => $data['floor_id']))->field('long,lat')->find();
                $data['lng'] = $floor_info['long'];
                $data['lat'] = $floor_info['lat'];
            }
            if (empty($data)) {
                $this->error('修改设备失败！请检查是否有添加内容');exit;
            }

            $info = D('House_camera_device')->where(array('camera_sn'=>$data['camera_sn']))->find();
            if (!empty($info)) {
                $this->error('当前编号设备已经被添加');exit;
            }
            if (isset($_POST['device_brand'])) {
                $data['device_brand'] = trim($_POST['device_brand']);
            }
            if (isset($_POST['device_model'])) {
                $data['device_model'] = trim($_POST['device_model']);
            }
            if (isset($_POST['device_code'])) {
                $data['device_code'] = trim($_POST['device_code']);
            }


            $data['add_time'] = time();
            $device_id = D('House_camera_device')->data($data)->add();
            if($device_id){
                if ('brand_haikang'==$data['device_brand']) {
                    $msg = D('House_camera_device')->addEquipment($device_id,$data);
                    if ($msg['error']&&$msg['msg']) {
                        $this->error($msg['msg']);exit;
                    }
                    D('House_camera_device')->setVideoOff($data['camera_sn'],$data['device_code']);
                    D('House_camera_device')->geChannelList($data['camera_sn'],$device_id,$data['village_id']);
                } else {
                    $this->first_sub_plant();
                }
                $this->success('新增设备成功！',U('camera_list'));exit;
            }else{
                $this->error('新增设备失败！');exit;
            }

        }else {
            //门禁设置-查看 权限
            if (!in_array(286, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');exit;
            }
            $brandHaikangCodeTip = D('House_face_device')->brandHaikangCodeTip;
            $this->assign('brandHaikangCodeTip',$brandHaikangCodeTip);
            $device_brand = [
                ['key' => 'other', 'title' => '其他设备'],
                ['key' => 'brand_haikang', 'title' => '海康设备'],
            ];
            $this->assign('device_brand',$device_brand);
            $this->display();
        }
    }

    public function camera_edit() {
        $where['camera_id'] = I('camera_id');
        if(empty($where['camera_id'])){
            $where['camera_id']	= $_GET['camera_id'];
        }
        if(empty($where)){
            $this->error('设备未找到');exit;
        }
        if($_POST){
            //门禁设置-修改 权限
            if (!in_array(286, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');exit;
            }
            $data = array();
            if ($_POST['camera_name']) {
                $data['camera_name'] = $_POST['camera_name'];
            }
            if (!trim($_POST['camera_sn'])){
                $this->error('请您添加设备ID');exit;
            }
            $data['camera_sn'] = trim($_POST['camera_sn']);
            $blockWhitelistCameraDeviceBrandArr = D('House_face_device')->blockWhitelistCameraDeviceBrandArr;
            if (!in_array($_POST['device_brand'],$blockWhitelistCameraDeviceBrandArr)) {
                $url = $this->check_site_url . '/face_camera_check.php';
                $params['device_id'] = $data['camera_sn'];
                $check_info = httpRequest($url,'POST',$params);
                $msg = $check_info[1];
                if ('ok'!=$msg) {
                    $this->error('您添加的人脸抓拍摄像机设备没有记录，请联系管理员');exit;
                }
            }
            if (isset($_POST['is_support_look'])) {
                $data['is_support_look'] = $_POST['is_support_look'];
            }
            if (isset($_POST['look_url'])) {
                $data['look_url'] = $_POST['look_url'];
            }
            $data['floor_id']	=	$_POST['floor_id'];
            $single_id = I('single_id');
            $single_id = explode("_", $single_id);
            if ($single_id[0]=='door') {
                $data['floor_id'] = -1;
                $data['public_area_id'] = $single_id[1];
            } elseif($data['floor_id']>0) {
                $data['public_area_id'] = 0;
            }
            if($_POST['location']){
                $locationArr = explode(',',trim($_POST['location']));
                $data['lng'] = $locationArr[0];
                $data['lat'] = $locationArr[1];
            } elseif ($data['public_area_id']>0) {
                $public_area = M('House_village_public_area')->where(array('public_area_id' => $data['public_area_id']))->field('lng,lat')->find();
                $data['lng'] = $public_area['lng'];
                $data['lat'] = $public_area['lat'];
            } elseif ($data['floor_id']>0) {
                $floor_info = M('House_village_floor')->where(array('floor_id' => $data['floor_id']))->field('long,lat')->find();
                $data['lng'] = $floor_info['long'];
                $data['lat'] = $floor_info['lat'];
            }
            if (empty($data)) {
                $this->error('修改设备失败！请检查是否有修改内容');exit;
            }
            $info = D('House_camera_device')->where(array('camera_sn'=>$data['camera_sn'], 'camera_id' => array('neq', $where['camera_id'])))->find();
            if (!empty($info)) {
                $this->error('当前编号设备已经被添加');exit;
            }
            if (isset($_POST['device_brand'])) {
                $data['device_brand'] = trim($_POST['device_brand']);
            }
            if (isset($_POST['device_model'])) {
                $data['device_model'] = trim($_POST['device_model']);
            }
            if (isset($_POST['device_code'])) {
                $data['device_code'] = trim($_POST['device_code']);
            }
            $data['last_time'] = time();
            $aSave	=	D('House_camera_device')->where($where)->data($data)->save();
            if($aSave){
                if ('brand_haikang'==$data['device_brand']) {
                    $msg = D('House_camera_device')->addEquipment($where['camera_id'],$data);
                    if ($msg['error']&&$msg['msg']) {
                        $this->error($msg['msg']);exit;
                    }
                    D('House_camera_device')->setVideoOff($data['camera_sn'],$data['device_code']);
                    D('House_camera_device')->geChannelList($data['camera_sn'],$where['camera_id']);
                }
                $this->success('修改设备成功！',U('camera_list'));exit;
            }else{
                $this->error('修改设备失败！请检查是否有修改内容');exit;
            }
        }else{
            //门禁设置-查看 权限
            if (!in_array(286, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');exit;
            }

            $aCamera	=	D('House_camera_device')->where($where)->find();
            if ($aCamera['public_area_id']>0) {
                $aCamera['single_id'] = 'door_'.$aCamera['public_area_id'];
            } elseif ($aCamera['floor_id']>0) {
                $aCamera['single_id'] = M('House_village_floor')->where(array('floor_id' => $aCamera['floor_id']))->getField('single_id');
            }
            $this->assign('aCamera',$aCamera);

            $brandHaikangCodeTip = D('House_face_device')->brandHaikangCodeTip;
            $this->assign('brandHaikangCodeTip',$brandHaikangCodeTip);
            $device_brand = [
                ['key' => 'other', 'title' => '其他设备'],
                ['key' => 'brand_haikang', 'title' => '海康设备'],
            ];
            $this->assign('device_brand',$device_brand);
            $this->display();
        }
    }

    public function camera_del() {
        $camera_id = I('camera_id');
        if(empty($camera_id)){
            $this->error('设备未找到');exit;
        }

        if($_POST) {
            //门禁设置-修改 权限
            if (!in_array(286, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
                exit;
            }
        }
        $where = [
            'camera_id' => $camera_id
        ];
        $info = D('House_camera_device')->where($where)->find();
        if (empty($info) || $info['camera_status'] == 4) {
            $this->error('当前编号设备已经被删除');exit;
        }
        $data['camera_status'] = 4;
        $data['camera_sn'] = $info['camera_sn'].'_del';
        $data['last_time'] = time();
        $aSave	=	D('House_camera_device')->where($where)->data($data)->save();
        if($aSave){
            if ('brand_haikang'==$info['device_brand']) {
                $orderGroupId = md5(uniqid() . $_SERVER['REQUEST_TIME']);
                $otherParam = [
                    'orderGroupId' => $orderGroupId,
                    'operatorType' => 'admin',
                ];
                $logParam = [
                    'sysType' => 'camera'
                ];
                D('Face_door_a5_service')->outMachine($info['camera_sn'], $otherParam,$logParam);
            }
            $this->success('删除设备成功！',U('camera_list'));exit;
        }else{
            $this->error('删除失败！');exit;
        }
    }

    // 摄像机获取ftp上的图片
    public function ftp_test() {
        set_time_limit(0);
        ini_set('memory_limit','-1');
        import('@.ORG.ftp');
        $config = array(
            'hostname' => $this->config['house_camera_ftp_host'],
            'username' => $this->config['house_camera_ftp_admin'],
            'password' => $this->config['house_camera_ftp_password'],
        );
        import('@.ORG.ftp');
        $ftp = new ftp();
        $conn = $ftp->connect($config);
        $data = $ftp->dir_switch();

//        $img_mer_id = sprintf("%09d", $id);
//        $rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
        $rand_num = date('Ymd');// 换成日期存储
        $up_dir = '/upload/camera_log/'.$rand_num.'/';
        if (!is_dir($up_dir)) {
            mkdir($up_dir, 0777, true);
        }

        if ($data) {
            $d_house_camera_img = M('House_camera_img');
            foreach ($data as $item) {
                $item_arr  = explode("/", $item);
                $img_arr = explode(".", $item_arr[4]);
                $type = array_pop($img_arr);
                if (!in_array($type,array('jpg,jpeg,png,gif'))) {
                    $type = 'jpg';// 如果不符合类型默认 jpg
                }
                $url_file = $up_dir.date('YmdHis_').uniqid().'.'.$type;
                $new_file = '.' . $url_file;
                $msg = $d_house_camera_img->where(array('camera_img' => $item))->find();
                if (!$msg) {
                    $tmp = $ftp->download($item, $new_file);
                    if($tmp){
                        if (!$msg) {
                            // 获取具体摄像机抓拍时间
                            $day_msg_arr = explode("[M]", $item_arr[4]);
                            $day_msg = str_replace('.',':',$day_msg_arr[0]);
                            $date_msg = $item_arr[2] . ' ' . $day_msg;

                            $house_camera_img = array(
                                'camera_sn' => $item_arr[1],
                                'camera_time' => strtotime($date_msg),
                                'camera_img' => $item,
                                'camera_name' => $item_arr[4],
                                'camera_url' => $url_file,
                                'add_time' => time()
                            );
                            $info = $d_house_camera_img->data($house_camera_img)->add();
                            if ($info) {
                                // 如果成功下载下来并进行了记录就进行删除
                                $del = $ftp->delete_file($item);
                            }
                        }
                        echo "upload $new_file -> success \n";
                    }
                } else {
                    // 如果成功下载下来就进行删除
                    $del = $ftp->delete_file($item);
                }
            }
        }

    }

    // 初始化子计划任务
    private function first_sub_plant() {
        $info = M('process_sub_plan')->where(array('file' => 'sub_camera_ftp_img'))->find();
        if (empty($info)) {
            $now_time = time();
            $arr= array();
            $arr['param'] = serialize(array('time'=>$now_time));
            $arr['plan_time'] = 0;
            $arr['space_time'] = 120;
            $arr['add_time'] = $now_time;
            $arr['file'] = 'sub_camera_ftp_img';
            $arr['time_type'] = 1;
            $arr['unique_id'] = 'camera_ftp_img_'.$now_time;
			$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
            M('process_sub_plan')->data($arr)->add();
            return true;
        } else {
            return true;
        }
    }

    // 人脸识别配置
    public function set_face_config() {
        //基本设置-查看 权限
        if (!in_array(397, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $village_id = $this->house_session['village_id'];
        $config_info = M('House_village_about_config')->where(['village_id'=>$village_id])->find();
        if (IS_POST) {
			$advance_payment_days = $_POST['advance_payment_days'] !== '' ? intval($_POST['advance_payment_days']) : '';
            $face_status = isset($_POST['face_status']) && $_POST['face_status'] ?$_POST['face_status']:0 ;
            $open_door_type = isset($_POST['open_door_type']) ? intval($_POST['open_door_type']) : 1;
            unset($_POST['advance_payment_days']);
            if ($advance_payment_days!=='' && $advance_payment_days<0) {
                $advance_payment_days = 0;
            }
            $config_id = $config_info['config_id'];
            if ($config_info && $config_id) {
                $config_data = [
                    'village_id'=>$village_id,
                    'advance_payment_days'=>$advance_payment_days,
                    'face_status'=>$face_status,
                    'last_time'=>time(),
                    'open_door_type'=>$open_door_type,
                    'a185_open_succ'=>$_POST['a185_open_succ'],
                    'a185_open_fail'=>$_POST['a185_open_fail'],
                    'a185_app_tips'=>$_POST['a185_app_tips'],
                    'a185_face_notice'=>$_POST['a185_face_notice']
                ];
                M('House_village_about_config')->where(['config_id' => $config_id])->data($config_data)->save();
            } else {
                $config_data = [
                    'village_id'=>$village_id,
                    'advance_payment_days'=>$advance_payment_days,
                    'face_status'=>$face_status,
                    'open_door_type'=>$open_door_type,
                    'a185_open_succ'=>$_POST['a185_open_succ'],
                    'a185_open_fail'=>$_POST['a185_open_fail'],
                    'a185_app_tips'=>$_POST['a185_app_tips'],
                    'a185_face_notice'=>$_POST['a185_face_notice'],
                    'add_time'=>time(),
                ];
                M('House_village_about_config')->data($config_data)->add();
            }
            //todo a185触发下发配置命令
            D('Face_a185_request_record')->a185_sync_command(true,false,$this->config);
            $this->success('修改成功！');
        } else {
            $village_info = D('House_village')->field(true)->where(array('village_id'=>$village_id))->find();
            $village_info['long'] = floatval($village_info['long']);
            $village_info['lat'] = floatval($village_info['lat']);
            $this->assign('village_info',$village_info);
            // 查询下是否存在人脸识别门禁设备A2
            $device = M('House_face_device')->where(array('village_id' => $village_id,'device_type'=>2))->find();
            if ($device) {
                $is_device = true;
            } else {
                $is_device = false;
            }
            $this->assign('is_device',$is_device);
            $a3_device = M('House_face_device')->where(array('village_id' => $village_id,'device_type'=>3))->find();
            if ($a3_device) {
                $is_a3_device = true;
            } else {
                $is_a3_device = false;
            }
            $this->assign('config_info',$config_info);
            $this->assign('is_a3_device',$is_a3_device);
            $this->display();
        }
    }

    /**恢复出厂设置*/
    public function recover() {
        if (!in_array(503, $this->house_session['menus'])) {
            $array = array(
                'errorCode'=>1001,
                'errorMsg'=>'对不起，您没有权限执行此操作',
            );
            exit(json_encode($array));
        }
        $device_id = $_POST['device_id'];
        $d_house_face_device = M('House_face_device');
        $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
        if (empty($house_face_device_info)) {
            $array = array(
                'errorCode'=>1001,
                'errorMsg'=>'对不起，当前设备不存在或者已经被删除',
            );
            exit(json_encode($array));
        }
        $device_sn = $house_face_device_info['device_sn'];
        if (30 === intval($house_face_device_info['device_type'])) {
            // 260云 将设备相关信息加入指令中等候
            $login_time = isset($house_face_device_info['login_time']) ? intval($house_face_device_info['login_time']) : 0;
            $parameterPolling = D('House_village_device_cmd')::PARAMETER_POLLING;
            $spaceTimeCondition = intval($parameterPolling) * 10; // 判断离线间隔时间
            $spaceTime = time() - $login_time;
            if ($login_time === 0 || $spaceTime > $spaceTimeCondition) {
                $array = array(
                    'errorCode'=>1001,
                    'errorMsg'=>'设备不在线请先检查网络！'
                );
                exit(json_encode($array));
            }
            $data = [];
            $data['device_id'] = $device_id;
            $data['device_sn'] = $device_sn;
            $data['recover'] = 1;
            $data['device_type'] = 30;
            D('House_village_device_cmd')->addOrUpdateRestartCmd($data);
        }
        $array = array(
            'errorCode'=>0,
            'result'=>$house_face_device_info
        );
        exit(json_encode($array));
    }
    
    /**删除设备*/
    public function del_device() {
        if (!in_array(503, $this->house_session['menus'])) {
            $array = array(
                'errorCode'=>1001,
                'errorMsg'=>'对不起，您没有权限执行此操作',
            );
            exit(json_encode($array));
        }
        $device_id = $_POST['device_id'];
        $d_house_face_device = M('House_face_device');
        $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
        if (empty($house_face_device_info)) {
            $array = array(
                'errorCode'=>1001,
                'errorMsg'=>'对不起，当前设备不存在或者已经被删除',
            );
            S($house_face_device_info['device_sn'].'/device_a'.$house_face_device_info['device_type'], null);
            exit(json_encode($array));
        }
        $orderGroupId = md5(uniqid().$_SERVER['REQUEST_TIME']);
        $village_id = $house_face_device_info['village_id']?$house_face_device_info['village_id']:0;
        $otherParam = [
            'orderGroupId' => $orderGroupId,
            'operatorType' => 'admin',
            'device_id' => $device_id,
            'village_id' => $village_id,
            'cloud_group_id' => $house_face_device_info['cloud_group_id'],
            'cloud_device_id' => $house_face_device_info['cloud_device_id'],
        ];
        $param = [
            'log_name' => 'adminDelDevice','logType' => 'houseAdmin','log_status' => 5,'orderGroupId' => $orderGroupId,
            'log_content' => '删除设备','device_id' => $device_id,'device_sn' => $house_face_device_info['device_sn']?$house_face_device_info['device_sn']:'',
            'device_type' => $house_face_device_info['device_type']?$house_face_device_info['device_type']:'','village_id' => $village_id,
            'addTime' => time(),
        ];
        try {
            D('House_face_device_log')->addLog($param);
        } catch( \Exception $e) {
            // 记录日志报错
            fdump_api(['日志记录[删除设备]出错'.__LINE__.'方法'.__FUNCTION__,$param,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
        }
        // 删除同步处理
        $info=array('error'=>0);
        $hfd = D('House_face_device');
        $thirdProtocol = isset($house_face_device_info['thirdProtocol']) && $house_face_device_info['thirdProtocol'] ? intval($house_face_device_info['thirdProtocol']) : 0;
        $hikGateKeyVillage = $hfd::HIK_DEVICE_GATEWAY_CUSTOM;
        $hikGateKey = $hfd::HIK_DEVICE_GATEWAY;
        if ($thirdProtocol && in_array($thirdProtocol, [$hikGateKey, $hikGateKeyVillage])) {
            $models = '\app\community\model\service\Device\HikGateWayDeviceService/deleteDeviceBox';
            if (isset($house_face_device_info['cloud_device_id']) && $house_face_device_info['cloud_device_id']) {
//                $deviceInfo = [
//                    'device_id' => $house_face_device_info['device_id'], 'device_sn' => $house_face_device_info['device_sn'], 'village_id' => $house_face_device_info['village_id'],
//                    'thirdProtocol' => $house_face_device_info['thirdProtocol'], 'device_param' => $house_face_device_info['device_param'],
//                    'cloud_device_id' => $house_face_device_info['cloud_device_id'],
//                ];
                $param = [
                    'deviceInfo' => [],
                    'param'     => [
                        'village_id'      => $house_face_device_info['village_id'],
                        'device_id'       => $house_face_device_info['device_id'],
                        'operation'       => 'deleteDevice',
                        'device_sn'       => $house_face_device_info['device_sn'],
                        'orderGroupId'    => $orderGroupId,
                        'cloud_device_id' => $house_face_device_info['cloud_device_id'],
                        'thirdProtocol'   => $house_face_device_info['thirdProtocol'],
                        'deviceType'      => 'face',
                    ]
                ];
                $rebootDeviceInfo = invoke_v20_service($models,$param);
                $rebootDevice = $rebootDeviceInfo['data']['retval'];
                if (!isset($rebootDevice['code']) || !$rebootDevice['code'] || (isset($rebootDevice['code']) && $rebootDevice['code'] == 200)) {
                    $info = [];
                    $info['error'] = 0;
                    $info['msg'] = '同步删除成功！';
                } elseif (isset($rebootDevice['message']) && $rebootDevice['message']) {
                    $info = [];
                    $info['error'] = 1;
                    $info['msg'] = "同步删除失败（{$rebootDevice['message']}）";
                }
            } else {
                $info = [];
                $info['error'] = 0;
                $info['msg'] = '同步删除成功！';
            }
        } elseif ($thirdProtocol && in_array($thirdProtocol, $hfd->newProtocol)) {
            // 目前仅支持 海康和大华新协议逻辑
            $models = '\app\community\model\service\FaceDeviceService/deleteDevice';
            $param = [
                'param'     => [
                    'village_id'      => $house_face_device_info['village_id'],
                    'device_id'       => $house_face_device_info['device_id'],
                    'operation'       => 'deleteDevice',
                    'device_sn'       => $house_face_device_info['device_sn'],
                    'orderGroupId'    => $orderGroupId,
                    'cloud_device_id' => $house_face_device_info['cloud_device_id'],
                    'thirdProtocol'   => $house_face_device_info['thirdProtocol'],
                    'deviceType'      => 'face',
                ]
            ];
            $thirdDel = invoke_v20_service($models,$param);
            fdump_api([$models,$param, $thirdDel],'FaceDeviceService/deleteDeviceLog',1);
        }


        if (30 === intval($house_face_device_info['device_type'])) {
            try {
                $data = [
                    'device_id' => $house_face_device_info['device_id'], 'empty' => 1, 'device_sn' => $house_face_device_info['device_sn'],
                ];
                D('House_village_device_cmd')->addOrUpdateDeleteCmd($data);
            }catch (Exception $e) {
                fdump_api(['msg' => '删除设备指令失败', 'err' => $e->getMessage(), 'data' => $data, 'device_id' => $device_id, 'village_id' => $village_id], '260Cloud/errDeleteAllUser', 1);
            }
        }
        if($house_face_device_info['device_type'] == 2) {
            $info = D('House_village_face_door')->face_door_del($device_id, $house_face_device_info);
        }
        elseif ($house_face_device_info['device_type'] == 3) {
            $info = D('House_village_face_door')->del_a3_device($device_id, $house_face_device_info['a3_device_id']);
        }
        elseif ($house_face_device_info['device_type'] == 4) {
            $info = D('Face_door_a4_service')->delA4Device($device_id, $house_face_device_info);
        }
        elseif ($house_face_device_info['device_type'] == 5 || $house_face_device_info['device_type'] == 6) {
            $device_sn = $house_face_device_info['device_sn'];
            $info = D('Face_door_a5_service')->removeDevice($device_sn,$otherParam);
        }
        elseif ($house_face_device_info['device_type'] == 7) {
            //咱不需同步直接默认同步删除成功
            $info = [];
            $info['error'] = 0;
            $info['msg'] = '同步删除成功！';
        } elseif ($house_face_device_info['device_type'] == 8) {
            //不需同步   直接默认删除成功
            $info = [];
            $info['error'] = 0;
            $info['msg'] = '同步删除成功！';
        }elseif ($house_face_device_info['device_type'] == 21 && $house_face_device_info['ai_device_sn']) {
            $info = D('Face_door_d1_service')->d1_del_gate($device_id, $house_face_device_info['ai_device_sn'], $house_face_device_info);
        }
        elseif ($house_face_device_info['device_type'] == 23) {
            //todo d3 删除设备 咱不需同步直接默认同步删除成功
            $info = [];
            $info['error'] = 0;
            $info['msg'] = '同步删除成功！';
        }
        elseif ($house_face_device_info['device_type'] == 24) {
            //todo D4智能门禁 删除设备
            D('Face_d4_request_record')->d4_delete($house_face_device_info);
            $info = [];
            $info['error'] = 0;
            $info['msg'] = '同步删除成功！';
        }elseif ($house_face_device_info['device_type'] == 88){
            //todo 大华门禁 删除设备
            ini_set('memory_limit', '1024M');
            set_time_limit(0);
            D('Dahua_service')->del_device($device_id);
            $info = [];
            $info['error'] = 0;
            $info['msg'] = '同步删除成功！';
        } elseif ($house_face_device_info['device_type'] == 31) {
            // todo 西函门禁由于是设备主动发起心跳 所以删除设备只是单独删除我们这边信息
        }

//        if($house_face_device_info['device_type'] == 3) {
//            $info = D('House_village_face_door')->face_door_add($device_id, $house_face_device_info);
//        }elseif ($house_face_device_info['device_type'] == 5 || $house_face_device_info['device_type'] == 6){
//            $info = D('Face_door_a5_service')->addEquipment($device_id, $house_face_device_info);
//        }elseif ($house_face_device_info['device_type'] == 4){
//            D('Face_door_a4_service')->faceDeviceSynchro($device_id, $house_face_device_info, 0);
//        }
        if (!$info['error']) {
            // 同步删除成功后本地设备删除
            $del_data = [
                'is_del' => 4,
                'last_time' => time(),
                'device_sn' => $house_face_device_info['device_sn'] . '~no_use',
                'is_syn_del' => 1,
            ];
            if($house_face_device_info['device_type']==31) {
                $del_data['socket_client_id']='';
            }
            $d_house_face_device->where(array('device_id' => $device_id))->save($del_data);
            $array = array(
                'errorCode'=>0,
                'result'=>$house_face_device_info
            );
            S($house_face_device_info['device_sn'].'/device_a'.$house_face_device_info['device_type'], null);
        } else {
            $d_house_face_device->where(array('device_id' => $device_id))->setField('is_syn_del',2);
            $array = array(
                'errorCode'=>1,
                'errorMsg'=>$info['msg']
            );
        }
        exit(json_encode($array));
    }

    /**获取对应单元的层列表*/
    public function device_layer_list() {
        $floor_id = $_POST['floor_id'];
        if (!$floor_id) {
            $array = array(
                'status'=>0,
                'list'=>[]
            );
            exit(json_encode($array));
        }
        $db_house_village_layer = M('House_village_layer');
        $layer_list = $db_house_village_layer->where(['floor_id' => $floor_id,'status' => 1])->select();
        if ($layer_list) {
            // 把其中汉字去除
            $pattern = "/[\x{4E00}-\x{9FFF}]+/u";
            foreach ($layer_list as $key=>&$val) {
                $layerName = $val['layer_name'];
                // 把其中汉字去除
                $layerNum = preg_replace($pattern,'',$layerName);
                if ($layerNum) {
                    $layerNum = str_pad($layerNum,2,"0",STR_PAD_LEFT);
                } else {
                    unset($layer_list[$key]);
                    continue;
                }
                $layerNum = substr($layerNum, -2,2);
                $val['layerNum'] = $layerNum;
            }
            array_values($layer_list);
        } else {
            $layer_list = [];
            $array = array(
                'status'=>0,
                'list'=>[]
            );
            exit(json_encode($array));
        }
        $array = array(
            'status'=>1,
            'list'=>$layer_list
        );
        exit(json_encode($array));
    }
    //A8同步
    public function addIssue()
    {
        $device_id = I('device_id');
        $device_info = D('House_face_device')->where(['device_id'=>$device_id])->field('device_sn')->find();
        if(!$device_info){
            echo json_encode(['errorCode'=>1,'errorMsg'=>'设备不存在']);die;
        }
        D('House_face_device')->where(['device_id'=>$device_id])->data(['login_time'=>time()])->save();
        $device_sn = $device_info['device_sn'];
        $type = I('type');
        $pigcms_id = I('pigcms_id');
        if(!$type){
            echo json_encode(['errorCode'=>1,'errorMsg'=>'参数异常']);die;
        }
        try {
            switch ($type) {
                case 1:
                    //一键同步
                    D('Face_local_area_device')->synFace($device_id);
                    break;
                case 2:
                    //单个同步
                    D('Face_local_area_device')->synUser($device_id,$pigcms_id);
                    break;
                case 3:
                    //删除成员
                    D('Face_local_area_device')->delMember($device_id,$pigcms_id);
                    break;
                case 4:
                    //远程开门
                    D('Face_local_area_device')->openDoor($device_id,$pigcms_id);
                    break;
                case 5:
                    //重启设备
                    D('Face_local_area_device')->restartDevice($device_id);
                    break;
                case 6:
                    if(!$device_sn){
                        echo json_encode(['massage'=>'error','result'=>1001,'设备sn号不存在']);die;
                    }
                    //删除设备
                    D('Face_local_area_device')->delDevice($device_sn);
                    break;
            }
        }catch (\Exception $e){
            echo json_encode(['errorCode'=>1,'errorMsg'=>$e->getMessage()]);die;
        }
        exit(json_encode(['errorCode'=>0,'errorMsg'=>'操作成功！']));
	}

    //d2获取设备状态
    public function getD2DeviceStatus()
    {
        $device_id = I('post.device_id');
        $device_info = D("House_face_device")->where(['device_id' => $device_id])
            ->field("device_sn,village_id,floor_id,public_area_id,device_domain")->find();
        if (!$device_info) {
            $this->error('设备不存在');
        }
        $res = D('Face_door_card_service')->certification($device_info['device_sn'], $device_info['device_domain']);
        if ($res['success'] == true) {
            if ($res['status'][0] == 1) {
                $device_status = 1;
            } else {
                $device_status = 2;
            }
            D('House_face_device')->where(['device_id' => $device_id])->data(['device_status' => $device_status])->save();
        }
        $this->success('获取成功');
    }

    /**
     *
     */
    public function control_materials()
    {
        $type = 'house';
        $village_id = $this->house_session['village_id'];
        set_time_limit(-1);
        $pigcms_return = D('House_village')->get_qrcode($village_id);
        if(empty($pigcms_return['qrcode_id'])){
            $qrcode_return = D('Recognition')->get_new_qrcode($type,$village_id);
        }else{
            $qrcode_return = D('Recognition')->get_qrcode($pigcms_return['qrcode_id']);
        }
        if($qrcode_return['error_code']){
            //exit($qrcode_return['msg']);
        }else if($qrcode_return['qrcode'] == 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='){
            $qrcode_return = D('Recognition')->get_new_qrcode($type,$village_id);
        }
        if(isset($qrcode_return['qrcode'])){
            $img_url = $qrcode_return['qrcode'];
        }else{
            $img_url = '';
        }
        $this->assign('img_url',$img_url);
        $this->assign('village_id',$village_id);
        $this->display();
    }

    public $font_url = './static/fonts/apple_lihei_bold.otf';

    /**
     * 合并图片并保存至本地
     */
    public function imgCombine()
    {
        $site_url = $this->config['site_url'];
        $logo = $_POST['logo'];
        $QR = $this->config['site_url'].$_POST['bgimg'];
        $village_id = $_POST['village_id'];
        $rand = rand(1000,9999);
        $new_img = 'poster_'.$rand;
        $QR = imagecreatefromstring(file_get_contents($QR));
        $logo = imagecreatefromstring(file_get_contents($logo));
        $QR_width = imagesx($QR);//二维码图片宽度
        $QR_height = imagesy($QR);//二维码图片高度
        $logo_width = imagesx($logo);//logo图片宽度
        $logo_height = imagesy($logo);//logo图片高度
        $logo_qr_width = $QR_width / 4.4;
        $logo_qr_height = $logo_qr_width;
        $from_width = ($QR_width - $logo_qr_width) /2;
        $from_height =($QR_height - $logo_qr_height) /2.79;
        //重新组合图片并调整大小
        imagecopyresampled($QR, $logo, $from_width, $from_height, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
        $village_info = M('House_village')->where(['village_id'=>$village_id])->field('village_name,property_id')->find();
        $property_info = M('House_property')->where(['id'=>$village_info['property_id']])->field('property_name,property_phone')->find();
        $text = array (
            array (
                'text' => $village_info['village_name'],
                'left'=>$QR_width,
                'top'=>43,
                'right'=>0,
                'bottom'=>0,
                'fontSize'=>14,
                'fontColor'=>'255,255,255',
            ),
            array (
                'text' => $property_info['property_name'],
                'left'=>$QR_width,
                'top'=>1060,
                'right'=>0,
                'bottom'=>0,
                'fontSize'=>18,
                'fontColor'=>'255,255,255',
            ),
            array (
                'text' => $property_info['property_phone'],
                'left'=>$QR_width,
                'top'=>1090,
                'right'=>0,
                'bottom'=>0,
                'fontSize'=>18,
                'fontColor'=>'255,255,255',
            )
        );
        if ($text) {
            foreach ($text as $key=>$val) {
                $val['fontPath'] =$this->font_url;
                $fontBox = imagettfbbox($val['fontSize'], 0, $this->font_url, $val['text']);//获取文字所需的尺寸大小
                list($R,$G,$B) = explode(',', $val['fontColor']);
                $fontColor = imagecolorallocate($QR, $R, $G, $B);
                $val['left'] = $val['left']<0?$QR_width- abs($val['left']):$val['left'];
                $val['top'] = $val['top']<0?$QR_width- abs($val['top']):$val['top'];
                imagettftext($QR,$val['fontSize'],0,ceil(($val['left']-$fontBox[2]) / 2),$val['top'],$fontColor,$val['fontPath'],$val['text']);
            }
        }
        //输出图片
        imagepng($QR, 'static/file/'.$new_img.'.png');
        echo json_encode(["info"=>$site_url.'/static/file/'.$new_img.'.png',"status"=>1]);
    }

    public function saveImage()
    {
        $filename = $_GET['url'];
        //$filename = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQG58DwAAAAAAAAAAS5odHRwOi8vd2VpeGluLnFxLmNvbS9xLzAyUWpDSFJiM1RhT18xMDAwME0wM3EAAgROHwNeAwQAAAAA&st=BA3742E2EC525512BC78646B7C217EEA80C7F80D42204C036729CC07E475EB8EB11F7B7069870C845A0FAD2FE772EEC6056C1E6186ABA888E8D634EBBFE847C7F5377E7FDEBC1CB2FAC293C73A19BD2DC67D0F37F28CCE8412E32648A02561961092C4EF1EE2A23742857C3EB71DCF47EC9427A56963BA615C6139B5C81D273B040977F994751B61DEDC3D78BC5FFFD9D35FA50A69AED42A113106223E2BE38CA74F22C1A879FA49B74C444B75A149AA&vid=1688853715231897&cst=C875986785F205129AC3F8978E5DDEA59A7B102D5E0B80ACA1DB361C91AA816506D48C94262CFA594D3123F98B64BE21&deviceid=4534619e-b4d3-41cd-97d4-1a90056b69f2&version=3.1.1.3002&platform=win';
        header('Content-Disposition:attachment;filename=erweima.png');
        header('Content-Length:' . filesize($filename));
        readfile($filename);
        echo json_encode(['status'=>0]);
    }


    /**
     * ds设备获取状态
     * @author liukezhu
     * @date 2021/4/17
     */
    public function d3GetStatus(){
        if (!in_array(502, $this->house_session['menus'])) {
            $array = array(
                'errorCode'=>1001,
                'errorMsg'=>'对不起，您没有权限执行此操作',
            );
            exit(json_encode($array));
        }
        $device_id = $_POST['device_id'];
        $d_house_face_device = M('House_face_device');
        $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
        if (empty($house_face_device_info)) {
            $array = array(
                'errorCode'=>1001,
                'errorMsg'=>'对不起，当前设备不存在或者已经被删除',
            );
            exit(json_encode($array));
        }
        if ($house_face_device_info['device_type'] == 23){

            $d_house_face_device = M('House_face_device');
            $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();

            D('Face_d3_request_record')->d3_get_status($device_id, $house_face_device_info['village_id'], $house_face_device_info);
        }elseif ($house_face_device_info['device_type'] == 24){
            //todo D4智能门禁 获取状态
            D('Face_d4_request_record')->d4_get_device($house_face_device_info);
        }
        $array = array(
            'errorCode'=>0,
            'result'=>$house_face_device_info
        );
        exit(json_encode($array));
    }


    //todo 查询室内机
    public function indoor_device(){
        $database_Face_a185_indoor = D('Face_a185_indoor');
        $where['village_id'] = $this->house_session['village_id'];
        $where['device_type'] =1;
        $where['status'] =array('neq',4);
        $list = $database_Face_a185_indoor->a185_indoor_page_list($where, 'id,device_sn,status','id desc');
        $this->assign('list',$list['list']);
        $this->display();
    }


    //todo 添加室内机
    public function indoor_device_add(){
        if (IS_POST) {
            $device_sn=trim($_POST['device_sn']);
            $status=$_POST['status'];
            if(empty($device_sn)){
                $this->error('请输入室内机设备号');
            }
            $database_Face_a185_indoor = D('Face_a185_indoor');
            $param=array(
                'device_sn'=>$device_sn,
                'village_id'=>$this->house_session['village_id'],
                'device_type'=>1,
                'status'=>$status,
                'add_time'=>time()
            );
            $check= $database_Face_a185_indoor->a185_indoor_unique($device_sn);
            if(!$check['error']){
                $this->error($check['msg']);
            }
            $rr= $database_Face_a185_indoor->a185_indoor_add($param);
            if($rr){
                $this->success('添加成功');
            }else{
                $this->error('添加失败');
            }
        }
        else{
            $this->display();
        }
    }

    //todo 编辑室内机
    public function indoor_device_edit(){
        $database_Face_a185_indoor = D('Face_a185_indoor');
        if (IS_POST){
            $device_sn=trim($_POST['device_sn']);
            $id=$_POST['id'];
            if(empty($id)){
                $this->error('缺少必要参数');
            }
            if(empty($device_sn)){
                $this->error('请输入室内机设备号');
            }
            $database_Face_a185_indoor = D('Face_a185_indoor');
            $a185_indoor_query= $database_Face_a185_indoor->a185_indoor_query(['id'=>$id,'village_id'=>$this->house_session['village_id'],'status'=>array('neq',4)]);
            if(!$a185_indoor_query){
                $this->error('数据不存在');
            }
            $param=array(
                'device_sn'=>$device_sn,
            );
            if(isset($_POST['status'])){
                $param['status']=$_POST['status'];
            }
            $check= $database_Face_a185_indoor->a185_indoor_unique($device_sn,$a185_indoor_query['id']);
            if(!$check['error']){
                $this->error($check['msg']);
            }
            $database_Face_a185_indoor->a185_indoor_edit(['id'=>$a185_indoor_query['id']],$param);
            $this->success('编辑成功');
        }else{
            $a185_indoor_query= $database_Face_a185_indoor->a185_indoor_query(['id'=>$_GET['id'],'village_id'=>$this->house_session['village_id'],'status'=>array('neq',4)]);
            $this->assign('list',$a185_indoor_query);
            $this->display();
        }
    }

    //todo 删除室内机
    public function indoor_device_del(){
        if (IS_POST) {
            $id=trim($_POST['id']);
            if(empty($id)){
                $this->error('缺少必要参数');
            }
            $database_Face_a185_indoor = D('Face_a185_indoor');
            $a185_indoor_query= $database_Face_a185_indoor->a185_indoor_query(['id'=>$id,'village_id'=>$this->house_session['village_id'],'status'=>array('neq',4)]);
            if(!$a185_indoor_query){
                $this->error('数据不存在');
            }
            $rr= $database_Face_a185_indoor->a185_indoor_edit(['id'=>$a185_indoor_query['id']],['status'=>4,'device_sn'=>$a185_indoor_query['device_sn'].'~no_use']);
            if($rr){
                $this->success('删除成功');
            }else{
                $this->error('删除失败');
            }
        }
    }

    //todo A185设备升级
    public function a185_upgrade(){
        if (IS_POST) {
            $device_id=trim($_POST['device_id']);
            if(empty($device_id)){
                $this->error('缺少必要参数');
            }
            $res=D('Face_a185_request_record')->a185_upgrade($device_id);
            if($res['error']){
                $this->success($res['msg']);
            }else{
                $this->error($res['msg']);
            }
        }
    }

    public function face_door_list(){
        $village_id = $this->house_session['village_id'];

        $find_type = I('find_type','','trim');
        $find_value = I('find_value','','trim');
        $status = I('status','','intval');
        $start_time = I('start_time','','trim');
        $end_time = I('end_time','','trim');
        $device_name = I('device_name','','trim');
        $device_sn = I('device_sn','','trim');
        //人脸门禁记录列表
        $log_where = [
            'log_business_id' => $village_id
        ];
        if ($find_type && 'name'==$find_type && $find_value) {
            $whereUserBind = [
                'village_id' => $village_id,
                'name' => $find_value,
            ];
            $ids = M('House_village_user_bind')->where($whereUserBind)->getField('pigcms_id', true);
            if (! empty($ids) && count($ids) == 1) {
                $pigcms_id = reset($ids);
                $log_where['log_bind_id'] = $pigcms_id;
            } else {
                $log_where['log_bind_id'] = array('in',$ids);
            }
        } elseif ($find_type && 'id_card'==$find_type && $find_value) {
            $whereUserBind = [
                'village_id' => $village_id,
                'id_card' => $find_value,
            ];
            $ids = M('House_village_user_bind')->where($whereUserBind)->getField('pigcms_id', true);
            if (! empty($ids) && count($ids) == 1) {
                $pigcms_id = reset($ids);
                $log_where['log_bind_id'] = $pigcms_id;
            } else {
                $log_where['log_bind_id'] = array('in',$ids);
            }
        }
        $deviceIdArr = [];
        $hasDevice = 0;
        if (!empty($device_name) && !empty($device_sn)) {
            $whereFaceDevice = [
                'village_id' => $village_id,
                'device_name' => $device_name,
                'device_sn' => $device_sn,
            ];
            $deviceIdArr  = D('House_face_device')->where($whereFaceDevice)->getField('device_id',true);
            $hasDevice = 1;
        } elseif (!empty($device_name)) {
            $whereFaceDevice = [
                'village_id' => $village_id,
                'device_name' => $device_name,
            ];
            $deviceIdArr  = D('House_face_device')->where($whereFaceDevice)->getField('device_id',true);
            $hasDevice = 1;
        } else if (!empty($device_sn)) {
            $whereFaceDevice = [
                'village_id' => $village_id,
                'device_sn' => $device_sn,
            ];
            $deviceIdArr  = D('House_face_device')->where($whereFaceDevice)->getField('device_id',true);
            $hasDevice = 1;
        }

        if (1==$status) {
            // 成功开门的
            $log_where['log_status'] = 0;
        } elseif (2==$status) {
            // 开门失败的
            $log_where['log_status'] = array('neq', 0);
        }

        if ($hasDevice == 1){
            $log_where['device_id'] =  array('in',$deviceIdArr);
        }

        if(!empty($start_time)&&empty($end_time)){
            $start_time = strtotime($start_time." 00:00:00");
            $log_where['log_time'] =  array('egt',$start_time);
        }
        if(empty($start_time)&&!empty($end_time)){
            $end_time = strtotime($end_time."  23:59:59");
            $log_where['log_time'] =  array('elt',$end_time);
        }
        if(!empty($start_time)&&!empty($end_time)){
            $start_time = strtotime($start_time." 00:00:00");
            $end_time = strtotime($end_time."  23:59:59");
            if ($start_time>$end_time) {
                $this->error("结束时间应大于开始时间");
            }
            $log_where['log_time'] =  array('between',$start_time.','.$end_time);
        }

        if (empty($start_time) && empty($end_time)){ //默认就显示 2天的记录
            $tody = date('Y-m-d', strtotime("-1 day"));
            $endData = date("Y-m-d");
            $start_time = strtotime($tody." 00:00:00");
            $end_time = strtotime($endData."  23:59:59");
            $log_where['log_time'] =  array('between',$start_time.','.$end_time);
        }

        $count = M('House_user_log')->where($log_where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count,'20','page');
        $door_list = M('House_user_log')->where($log_where)->order('`log_time` DESC')->limit($p->firstRow.','.$p->listRows)->select();

        $is_temp = false;
        if (! empty($door_list)) {
            $db_house_face_device = D('House_face_device');
            $deviceIds = array_unique(array_column($door_list, 'device_id'));
            $whereDeviceAbout = [
                'device_id' => ['in', $deviceIds],
            ];
            $deviceInfoArr = $db_house_face_device->where($whereDeviceAbout)
                ->getField('device_id, village_id, device_id, device_name, device_sn, device_type');

            $uids = array_unique(array_column($door_list, 'uid'));
            $whereUserAbout = [
                'uid' => ['in', $uids],
            ];
            $userInfoArr = D('User')->where($whereUserAbout)
                ->getField('uid, uid, nickname, phone');

            $pigcmsIds = array_unique(array_column($door_list, 'log_bind_id'));
            $whereUserBindAbout = [
                'pigcms_id' => ['in', $pigcmsIds],
            ];
            $userBindInfoArr = D('House_village_user_bind')->where($whereUserBindAbout)
                ->getField('pigcms_id, pigcms_id, village_id, single_id, floor_id, layer_id, vacancy_id, name, id_card, address');

            $configSiteUrl = $this->config['site_url'];
            foreach ($door_list as &$item) {
                $device_id = isset($item['device_id']) && $item['device_id'] ? intval($item['device_id']) : 0;
                $deviceInfo = isset($deviceInfoArr[$device_id]) && $deviceInfoArr[$device_id] ? $deviceInfoArr[$device_id] : [];
                $device_type = isset($deviceInfo['device_type']) ? $deviceInfo['device_type'] : '';
                if (in_array($device_type,[5,7,21,30])) {
                    $is_temp = true;
                }
                $item['device_name'] = isset($deviceInfo['device_name']) ? $deviceInfo['device_name'] : '';
                $item['device_sn'] = isset($deviceInfo['device_sn']) ? $deviceInfo['device_sn'] : '';
                $item['village_id'] = isset($deviceInfo['village_id']) ? $deviceInfo['village_id'] : '';

                $uid = isset($item['uid']) && $item['uid'] ? intval($item['uid']) : 0;
                $userInfo = isset($userInfoArr[$uid]) && $userInfoArr[$uid] ? $userInfoArr[$uid] : [];
                $item['phone'] = isset($userInfo['phone']) ? $userInfo['phone'] : '';
                $item['nickname'] = isset($userInfo['nickname']) ? $userInfo['nickname'] : '';

                $log_bind_id = isset($item['log_bind_id']) && $item['log_bind_id'] ? intval($item['log_bind_id']) : 0;
                $userBindInfo = isset($userBindInfoArr[$log_bind_id]) && $userBindInfoArr[$log_bind_id] ? $userBindInfoArr[$log_bind_id] : [];
                if (empty($item['village_id'])) {
                    $item['village_id'] = isset($userBindInfo['village_id']) ? $userBindInfo['village_id'] : '';
                }
                $item['single_id'] = isset($userBindInfo['single_id']) ? $userBindInfo['single_id'] : '';
                $item['floor_id'] = isset($userBindInfo['floor_id']) ? $userBindInfo['floor_id'] : '';
                $item['layer_id'] = isset($userBindInfo['layer_id']) ? $userBindInfo['layer_id'] : '';
                $item['vacancy_id'] = isset($userBindInfo['vacancy_id']) ? $userBindInfo['vacancy_id'] : '';
                $item['village_bind_name'] = isset($userBindInfo['name']) ? $userBindInfo['name'] : '';
                $item['id_card'] = isset($userBindInfo['id_card']) ? $userBindInfo['id_card'] : '';
                $item['village_bind_address'] = isset($userBindInfo['address']) ? $userBindInfo['address'] : '';

                $log_from_txt = $db_house_face_device->getLogFromText($item['log_from'], $item['card_number']);
                if (isset($item['device_event_msg']) && trim($item['device_event_msg'])) {
                    $log_from_txt = $item['device_event_msg'];
                }
                $item['log_from_txt'] = $log_from_txt;
                if ($item['log_from']==31&&$item['log_status']==1){
                    $item['log_status']=0;
                }
                if ($item['log_detail']) {
                    $log_detail = $item['log_detail'];
                    $item['log_detail'] = unserialize($item['log_detail']);
                    if (!$item['log_detail']) {
                        $item['log_detail'] = json_decode($log_detail, true);
                    }
                    if ($item['log_detail']) {
                        if($item['log_detail']['face_img']){
                            $item['faceImg1'] = $item['log_detail']['face_img'];
                        }else if ($item['log_detail']['imgUrl']) {
                            $item['faceImg1'] = $item['log_detail']['imgUrl'];
                        } elseif ($item['log_detail']['mediaUrls']) {
                            $item['faceImg1'] = $item['log_detail']['mediaUrls'];
                        } elseif ($item['log_detail']['pictureURL']) {
                            $item['faceImg1'] = $item['log_detail']['pictureURL'];
                        } elseif ($item['log_detail']['faceCapture'][0]['bkgUrl']) {
                            $item['faceImg1'] = $item['log_detail']['faceCapture'][0]['bkgUrl'];
                        } elseif ($item['log_detail']['face_img_url']) {
                            $item['faceImg1'] = $item['log_detail']['face_img_url'];
                        } elseif ($item['log_detail']['capture_img_url']) {
                            $item['faceImg1'] = $item['log_detail']['capture_img_url'];
                        } elseif ($item['log_detail']['capture_img']) {
                            $item['faceImg1'] = $item['log_detail']['capture_img'];
                        }
                    }
                }
                if (!$item['faceImg1'] && $item['cut_image']) {
                    $item['faceImg1'] = $item['cut_image'];
                } elseif (!$item['faceImg1']) {
                    $item['faceImg1'] = "/static/images/live_goods_default.png";
                }
                $item['onerrorImg'] = "/static/images/live_goods_default.png";
                if (isset($item['faceImg1']) && $item['faceImg1']) {
                    $stripos_result = stripos($item['faceImg1'], '/upload/face_log/');
                    if ($stripos_result !== false) {
                        if ($stripos_result === 0 && file_exists('.' . $item['faceImg1'])) {
                            $item['onerrorImg'] = $configSiteUrl . $item['faceImg1'];
                        }
                    }
                }
                if (!empty($item['log_time'])){
                    $item['log_time']=date('Y-m-d H:i:s',$item['log_time']);
                }
                if (!$item['nickname'] && !$item['phone'] && $item['log_detail']) {
                    $info = $item['log_detail']['info'];
                    $info_arr = json_decode($info, true);
                    // 用户信息数组
                    $target_info = $info_arr['Target'];
                    $target_msg = reset($target_info);
                    $person = $target_msg['person'];
                    if ($person) {
                        $item['nickname'] = $person['name']?$person['name']:'';
                        if (!$item['village_bind_address']) {
                            $item['village_bind_address'] = $person['address']?$person['address']:'';
                        }
                    }
                }
                if ($item['person_name']) {
                    $item['village_bind_name'] = $item['person_name'];
                }
                if (!$item['village_bind_name'] && $item['nickname']) {
                    $item['village_bind_name'] = $item['nickname'];
                } elseif (isset($item['log_detail']['personName']) && $item['log_detail']['personName']) {
                    $item['village_bind_name'] = $item['log_detail']['personName'];
                }
                if (!$item['village_bind_address'] && isset($item['vacancy_id']) && $item['vacancy_id']) {
                    $village_bind_address = D('House_village')->get_single_floor_room($item['single_id'],$item['floor_id'],$item['layer_id'],$item['vacancy_id'],$item['village_id']);
                } else {
                    $village_bind_address = '';
                }
                if (!$item['village_bind_address'] && $village_bind_address) {
                    $item['village_bind_address'] = $village_bind_address;
                }
                if (!$item['village_bind_address'] && $item['log_name']) {
                    $item['village_bind_address'] = $item['log_name'];
                }
            }
        }
        // 正常体温
        $this->assign('normal_temperature',37.3);
        $this->assign('is_temp' ,$is_temp);
        $this->assign('door_list' ,$door_list);
        $this->assign('start_time' ,$start_time);
        $this->assign('end_time' ,$end_time);
        $this->assign('pagebar' ,$p->show());
        $this->display();
    }

    // 获取设备
    public function getDoorSearch()
    {
        $models = '\app\community\model\service\Device\HikInfoVisionICommunityService/getAcsDoorSearch';
        $param = [
            'pageNo' => 1,
            'pageSize' => 100,
        ];
        $result = invoke_v20_service($models, $param);
        $result = $result['data']['retval'];
        $array = array(
            'errorCode' => 0,
            'result' => $result
        );
        exit(json_encode($array));
    }
    public function hikCommunityUserSyc(){
        $device_id =$_POST['device_id'] ? intval($_POST['device_id']):0;
        $device_sn = trim($_POST['device_sn']);//设备编号
        $device_type = trim($_POST['device_type']);
        $village_id=$this->house_session['village_id'];
        $whereArr=array('device_id' => $device_id,'village_id'=>$village_id,'thirdProtocol'=>15);
        $face_device_data = M('House_face_device')->where($whereArr)->find();
        
        if($face_device_data && !empty($face_device_data) && $village_id>0){
            $models = '\app\community\model\service\Device\HikInfoVisionICommunityService/sycBindUserToHikCommunity';
            $param=['village_id'=>$village_id,'bind_pigcms_id'=>0,'floor_id'=>$face_device_data['floor_id'],'device_id'=>$device_id];
            $result = invoke_v20_service($models, $param);
            $result = $result['data']['retval'];
            $array = array(
                'errorCode' => 0,
                'result' => $result
            );
            exit(json_encode(array('errorCode'=>0,'errorMsg'=>'操作成功！','result'=>$result)));
        }
        exit(json_encode(array('errorCode'=>0,'errorMsg'=>'操作成功！')));
    }
    public function lilinPfUserSyc(){
        $device_id =$_POST['device_id'] ? intval($_POST['device_id']):0;
        $device_no = trim($_POST['device_no']);//设备号码
        $village_id=$this->house_session['village_id'];
        $whereArr=array('device_id' => $device_id,'village_id'=>$village_id,'device_no'=>$device_no);
        $face_device_data = M('House_face_device')->where($whereArr)->find();
        if($face_device_data && !empty($face_device_data) && $village_id>0){
            $models = '\app\community\model\service\HouseVillageLilinPushRecordService/lilinPfDeviceUserSyc';
            $param=['village_id'=>$village_id,'device_id'=>$device_id,'device_no'=>$face_device_data['device_no']];
            $result = invoke_v20_service($models, $param);
            $result = $result['data']['retval'];
            $array = array(
                'errorCode' => 0,
                'result' => $result
            );
            exit(json_encode(array('errorCode'=>0,'errorMsg'=>'操作成功！','result'=>$result)));
        }
        exit(json_encode(array('errorCode'=>0,'errorMsg'=>'操作成功！')));
    }

    public function lilinPfOnlyUserSyc(){
        $device_id =$_POST['device_id'] ? intval($_POST['device_id']):0;
        $device_no = trim($_POST['device_no']);//设备号码
        $village_id=$this->house_session['village_id'];
        $whereArr=array('village_id'=>$village_id);
        $village_data = M('House_village')->where($whereArr)->find();
        if($village_data && !empty($village_data['lilin_pf_client_id']) && !empty($village_data['lilin_pf_client_secret']) && !empty($village_data['lilin_village_no'])){
            $models = '\app\community\model\service\Device\LiLinDevAccessApiService/massVillageUserSycToLilinPf';
            $param=['village_id'=>$village_id];
            $result = invoke_v20_service($models, $param);
            exit(json_encode(array('errorCode'=>0,'errorMsg'=>'操作成功！','result'=>$result)));
        }
        exit(json_encode(array('errorCode'=>0,'errorMsg'=>'操作成功！')));
    }

    public function getLilinPfDevData(){
        $village_id=$this->house_session['village_id'];
        $whereArr=array('village_id'=>$village_id);
        $village_data = M('House_village')->where($whereArr)->find();
        if($village_data && !empty($village_data['lilin_pf_client_id']) && !empty($village_data['lilin_pf_client_secret']) && !empty($village_data['lilin_village_no'])){
            $models = '\app\community\model\service\Device\LiLinDevAccessApiService/getLilinPfDevices';
            $param=['village_id'=>$village_id];
            $result = invoke_v20_service($models, $param);
            exit(json_encode(array('errorCode'=>0,'errorMsg'=>'操作成功！','result'=>$result)));
        }
        exit(json_encode(array('errorCode'=>0,'errorMsg'=>'操作成功！')));
    }

   public function xiHanUserSyc(){
        $device_id =$_POST['device_id'] ? intval($_POST['device_id']):0;
        $device_sn = trim($_POST['device_sn']);//设备编号
        $village_id=$this->house_session['village_id'];
        $whereArr=array('device_id' => $device_id,'device_type'=>31);
        $face_device_data = M('House_face_device')->where($whereArr)->find();
        if($face_device_data && !empty($face_device_data) && $village_id>0){
            if(empty($face_device_data['socket_client_id'])){
                exit(json_encode(array('errorCode'=>1,'errorMsg'=>'设备不在线，无法同步！')));
            }
            $notify_time_line=time()-600;
            if($face_device_data['notify_time']<$notify_time_line){
                exit(json_encode(array('errorCode'=>1,'errorMsg'=>'设备最后上报时间数据异常，已经很长时间没有心跳了，无法同步！')));
            }
            $models = '\app\community\model\service\Device\XiHanEventService/sycBindUserToDevice';
            $param=['village_id'=>$village_id,'bind_pigcms_id'=>0,'floor_id'=>$face_device_data['floor_id'],'device_id'=>$device_id];
            $result = invoke_v20_service($models, $param);
            $result = $result['data']['retval'];
            $array = array(
                'errorCode' => 0,
                'result' => $result
            );
            exit(json_encode(array('errorCode'=>0,'errorMsg'=>'操作成功！','result'=>$result)));
        }
        exit(json_encode(array('errorCode'=>0,'errorMsg'=>'操作成功！')));
   }
}