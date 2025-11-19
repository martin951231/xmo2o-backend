<?php
class House_village_visitorModel extends Model{
    protected function _initialize() {
        parent::_initialize();

        $this->visitor_type = array(
             0=>'访客',
             1=>'亲属',
             2=>'朋友',
             3=>'同事',
             255=>'其他（需备注）',
        );
    }

    protected $_validate = array(
            array('visitor_type','require','访客类型不能为空！'),
            array('visitor_phone','require','访客手机号不能为空！'),
            array('owner_phone','require','业主手机号不能为空！'),
            array('owner_phone','chkownerPhone','业主手机号码不正确',0,'callback'),
            array('visitor_phone','visitorphonePhone','访客手机号码不正确',0,'callback'),
    );

    protected function chkownerPhone(){
        $owner_phone = $_POST['owner_phone'] + 0;
        $phone_match='/^(0|86|17951)?(13[0-9]|15[012356789]|17[678]|18[0-9]|14[57])[0-9]{8}$/';
        $mobile_match='/^((\(\d{2,3}\))|(\d{3}\-))?(\(0\d{2,3}\)|0\d{2,3}-)?[1-9]\d{6,7}(\-\d{1,4})?$/';
        if(!C('config.international_phone') && (!preg_match($phone_match, $owner_phone)) && (!preg_match($mobile_match, $owner_phone))){
            return false;
        }else{
            return true;
        }
    }

    protected function visitorphonePhone(){
        $visitor_phone = $_POST['visitor_phone'] + 0;
        $phone_match='/^(0|86|17951)?(13[0-9]|15[012356789]|17[678]|18[0-9]|14[57])[0-9]{8}$/';
        $mobile_match='/^((\(\d{2,3}\))|(\d{3}\-))?(\(0\d{2,3}\)|0\d{2,3}-)?[1-9]\d{6,7}(\-\d{1,4})?$/';
        if(!C('config.international_phone') && (!preg_match($phone_match, $visitor_phone)) && (!preg_match($mobile_match, $visitor_phone))){
            return false;
        }else{
            return true;
        }
    }

    public $status_desc = array('未通行','已通行（业主）','已通行（小区）','未到访','已到访','已通行（人脸门禁）');
    public $status_desc_color = array('red','green','green','red','green','green');
    public $status_desc_img = array(
        '/static/images/house/visitor/passStatus0.png',
        '/static/images/house/visitor/passStatus1.png',
        '/static/images/house/visitor/passStatus2.png',
        '/static/images/house/visitor/passStatus3.png',
        '/static/images/house/visitor/passStatus4.png',
        '/static/images/house/visitor/passStatus5.png',
    );

    protected $_auto= array(
         array('add_time','time',1,'function'),
         array('village_id','get_village_id',1,'callback'),
         array('owner_uid','get_uid',3,'callback'),
         array('pass_time','time',2,'function'),

    );

    protected function get_uid(){
        $phone = $_POST['owner_phone'] + 0;
        $database_user = D('User');
        if($phone){
            $where['phone'] = $phone;
            $uid = $database_user->where($where)->getField('uid');
            return $uid ? $uid :0;
        }else{
            return 0;
        }
    }

    protected function get_village_id(){
        return $_SESSION['house']['village_id'];
    }


    public function house_village_visitor_add($data, $status = 1)
    {
        if (!$data) {
            return false;
        }

        $database_user = D('User');
        $base_url = D('House_village')->base_url;
        if ($status == 1) {
            if($data['user_id']) {
                $userInfo = M('user')->where(array('phone' => $data['owner_phone']))->find();
                if ($userInfo) {
                    $data['owner_uid'] = $userInfo['uid'];
                }
            }else{
                $data['owner_uid'] = $data['user_id'];
            }
            $village_id = $data['village_id'];
            $now_time = time();
            if ($data['single_id'] && $data['floor_id'] && $data['layer_id'] && $data['vacancy_id']) {
                $address_arr = [
                    'single_id'=>$data['single_id'],
                    'floor_id'=>$data['floor_id'],
                    'layer_id'=>$data['layer_id'],
                    'vacancy_id'=>$data['vacancy_id'],
                ];
                $visitTime = isset($data['add_time']) && $data['add_time'] ? intval($data['add_time']) : $now_time;
                $visitParams = ['visitTime' => $visitTime];
                $user_bind = $this->visitor_bind_user($village_id,$data['visitor_phone'],$data['visitor_name'],$address_arr,$now_time,$visitParams);
                if ($user_bind['bind_id']) {
                    $data['visitor_bind_id'] = $user_bind['bind_id'];
                }
            }
            $data['time'] = $now_time;
            $insert_id = $this->data($data)->add();
            if ($insert_id) {
                if (isset($data['car_id']) && trim($data['car_id']) && $village_id > 0) {
                    invoke_v20_service('\app\community\model\service\Car\CarHandleGetInfoService/clearVisitorInfo', ['village_id' => $village_id]);
                }
                $add_time = isset($data['add_time']) && $data['add_time'] ? date('Y-m-d', $data['add_time']) : date('Y-m-d', $now_time);
                $effectTimeStamp = strtotime($add_time);
                $expireTimeStamp = strtotime($add_time.' 23:59:59');
                $getHikFaceDeviceVisitorQRCode = D('Config_customization')->getHikFaceDeviceVisitorQRCode();
                fdump_api(['msg' => 'pc后台管理是否添加A5访客二维码','$params' => $getHikFaceDeviceVisitorQRCode], 'visitor/adminAddVisitorLog',1);
                if ($getHikFaceDeviceVisitorQRCode) {
                    // 下发访客二维码
                    $visitor_open_number_limit = D('House_face_device')->getQrCodeNumber($village_id);
                    $cardNo = 100000000000 + $insert_id;
                    $visitor_param = [
                        'cardNo'     => $cardNo,
                        'effectTime' => date('ymdHis', $effectTimeStamp),
                        'expireTime' => date('ymdHis', $expireTimeStamp),
                        'openTimes'  => $visitor_open_number_limit,
                    ];
                    fdump_api(['msg' => '获取A5设备二维码参数','visitor_param' => $visitor_param, 'vistor_open_number_limit' => $visitor_open_number_limit], 'visitor/adminAddVisitorLog',1);
                    $visitor_device_result = D('Face_door_a5_service')->getVisitorQrcode($visitor_param);
                    fdump_api(['msg' => '获取A5设备二维码结果', 'visitor_device_result' => $visitor_device_result], 'visitor/adminAddVisitorLog',1);
                    $qrCodeUrl = isset($visitor_device_result['qrCodeUrl']) && $visitor_device_result['qrCodeUrl'] ? $visitor_device_result['qrCodeUrl'] : '';
                    $db_face_user_bind_device = D('Face_user_bind_device');
                    $bind_arr = [
                        'bind_type'   => 40,
                        'bind_id'     => $insert_id,
                        'device_type' => 5,
                        'code'        => $cardNo,
                        'person_id'   => $data['visitor_bind_id'],// 访客对应小区身份id
                        'personID'    => $data['owner_uid'],// 访客对应业主uid
                        'group_id'    => $data['vacancy_id'],// 访客要拜访房间id
                        'groupID'     => $data['village_id'],// 访客要拜访小区
                        'person_text' => json_encode($visitor_param,JSON_UNESCAPED_UNICODE), // 获取访客二维码时候的传参
                        'face_txt'    => json_encode($visitor_device_result,JSON_UNESCAPED_UNICODE), // 反馈结果
                    ];
                    if ($qrCodeUrl) {
                        $bind_arr['face_img'] = $qrCodeUrl; // 对应访客开门用二维码
                        $bind_arr['face_status'] = 2;
                    } else {
                        $bind_arr['face_status'] = 3;
                        $bind_arr['face_reason'] = isset($visitor_device_result['message']) && $visitor_device_result['message'] ? $visitor_device_result['message'] : '获取失败';
                    }
                    $bind_arr['face_time'] = time();
                    $bind_arr['add_time'] = $effectTimeStamp;
                    fdump_api(['msg' => '获取A5设备二维码关联记录', 'bind_arr' => $bind_arr], 'visitor/adminAddVisitorLog',1);
                    $id = $db_face_user_bind_device->add($bind_arr);
                    if ($id && $qrCodeUrl) {
                        fdump_api(['msg' => '下发', 'user_bind' => $user_bind], 'visitor/adminAddVisitorLog',1);
                        $face_device = D('House_face_img')->house_user_face_device_info(0, $user_bind['bind_id']);
                        fdump_api(['msg' => '下发结果', 'face_device' => $face_device], 'visitor/adminAddVisitorLog',1);
                    }
                }
                $getFaceCloud260Judge = D('Config_customization')->getFaceCloud260Judge();
                fdump_api(['msg' => '小区后台-是否添加260云访客二维码','$params' => $getFaceCloud260Judge, 'add_time' => $add_time, 'effectTimeStamp' => $effectTimeStamp, 'expireTimeStamp' => $expireTimeStamp, 'data' => $data], '260Cloud/ownerAddVisitorLog',1);
                if ($getFaceCloud260Judge) {
                    $visitor_open_number_limit = D('House_face_device')->getQrCodeNumber($village_id);
                    $employeeQRCodeArr = [
                        'cmd_id' => $user_bind['bind_id'],
                        'cmd' => 'selectPassInfo',
                        'device_text' => 'cloud260',
                        'device_type' => 30,
                        'type' => 'face',
                    ];
                    $employeeQRCode = md5(json_encode($employeeQRCodeArr,JSON_UNESCAPED_UNICODE));
                    $visitor_param = [
                        'cardNo'     => $employeeQRCode,
                        'effectTime' => date('Y-m-d H:i:s', $effectTimeStamp),
                        'expireTime' => date('Y-m-d H:i:s', $expireTimeStamp),
                        'openTimes'  => $visitor_open_number_limit, // 开门总次数
                        'surplusOpenTimes' => $visitor_open_number_limit, // 剩余次数
                    ];
                    $db_face_user_bind_device = D('Face_user_bind_device');
                    $bind_arr = [
                        'bind_type'   => 30,
                        'bind_id'     => $data['visitor_bind_id'],// 访客对应小区身份id
                        'device_type' => 30,
                        'open_key'    => 'visitor',
                        'open_key_time' => $expireTimeStamp,
                        'code'        => $employeeQRCode,
                        'person_id'   => $insert_id,// 访客id
                        'personID'    => $data['owner_uid'],// 访客对应业主uid
                        'group_id'    => $data['vacancy_id'],// 访客要拜访房间id
                        'groupID'     => $data['village_id'],// 访客要拜访小区
                        'person_text' => json_encode($visitor_param,JSON_UNESCAPED_UNICODE), // 获取访客二维码时候的传参
                        'face_txt'    => json_encode($employeeQRCodeArr,JSON_UNESCAPED_UNICODE), // 反馈结果
                    ];
                    $bind_arr['face_time'] = time();
                    $bind_arr['add_time'] = $effectTimeStamp;
                    fdump_api(['msg' => '小区后台-获取260云设备二维码关联记录', 'bind_arr' => $bind_arr], '260Cloud/ownerAddVisitorLog',1);
                    $id = $db_face_user_bind_device->add($bind_arr);
                    D('House_face_img')->house_user_face_device_info(0, $user_bind['bind_id']);
                }

                invoke_v20_service('\app\community\model\service\Device\HouseDeviceQrcodeService/addDeviceQrCode', ['pigcms_id' => $data['visitor_bind_id']]);
                //发送微信模板消息start
//                if ($data['visitor_name']) {
//                    $work_info = '\n姓名为：' . $data['visitor_name'] . '  手机号为' . $data['visitor_phone'] . '的访客正在等候，需要您的确认！';
//                } else {
//                    $work_info = '\n手机号为' . $data['visitor_phone'] . '的访客正在等候，需要您的确认！';
//                }
                if ($userInfo && $userInfo['openid'] && !$data['status']) {
                    $property_id = M('House_village')->where(['village_id'=>$_SESSION['house']['village_id']])->getField('property_id');

                    $db_house_village = D('House_village');
                    $app_version = intval($_POST['app_version']);
                    $deviceId = trim($_POST['Device-Id']);
                    $param = [
                        'pageName' => 'visitorRegistration',
                        'isAll' => true,
                        'urlParam' => 'village_id=' . $data['village_id'],
                    ];
                    $visitorRegistrationUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
                    $href = $visitorRegistrationUrl;
                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                    if ($data['owner_bind_id'] && $data['owner_bind_id']>0) {
                        $href .= '&pigcms_id='.$data['owner_bind_id'];
                    } elseif($_SESSION['house']['village_id'] && $data['owner_phone']) {
                        $database_house_village_user_bind = D('House_village_user_bind');
                        $where['village_id'] = $_SESSION['house']['village_id'];
                        $where['phone'] = $data['owner_phone'];
                        $where['parent_id'] = 0;
                        $where['status'] = 1;
                        $where['type'] = array('in', array(0, 3));

                        $field = array('pigcms_id' , 'name' , 'address', 'floor_id', 'single_id', 'layer_num', 'room_addrss');
                        $owner_detail = $database_house_village_user_bind->house_village_user_bind_detail($where , $field);
                        if ($owner_detail && $owner_detail['pigcms_id']) {
                            $href .= '&pigcms_id='.$owner_detail['pigcms_id'];
                        }
                    }
                    fdump([__LINE__,$href],'app_push_url_log', true);
                    //信息要修改
                    //如果有访客姓名，则同时提示访客姓名
                    $model->sendTempMsg('OPENTM408101810',//todo 类目模板OPENTM408101810 
                        array(
                            'href' => $href, 
                            'wecha_id' => $userInfo['openid'], 
                            'first' => '您好，'.$_SESSION['house']['village_name'].'业主，您有新访客到访', 
                            'keyword1' => $data['visitor_name'],
                            'keyword2' => $data['visitor_phone'], 
                            'keyword3' => date('Y年m月d日 H时i分'),
                            'remark' => '\n访客正在等候，需要您的确认！',
                            'new_info' => [//新版本发送需要的信息
                                'tempKey'=>'43728',//新模板号
                                'thing2'=>$data['visitor_name'],//来访人员
                                'phone_number10'=>$data['visitor_phone'],//联系电话
                                'time9'=>date('Y年m月d日 H:i'),//拜访时间
                            ],
                        ),0,$property_id,1);
                    //发送微信模板消息end
                    //todo 极光推送
                    D('House_village_repair_log')->send_app(['uid'=>$userInfo['uid']],'您好，'.$_SESSION['house']['village_name'].'业主，您有新访客到访','访客正在等候，需要您的确认！',$href);
                }
                //发送微信模板消息end
                $village_info = M('House_village')->where(array('village_id' => $data['village_id']))->find();
                if (!$data['status'] && C('config.village_sms') == 1 && $village_info['now_sms_number'] > 0) {
                    $sms_data['uid'] = $data['owner_uid'];
                    $sms_data['mobile'] = $data['owner_phone'];
                    $sms_data['sendto'] = 'user';
                    $sms_data['type'] = 'village_vistor';
                    if ($data['visitor_name']) {
                        $sms_data['content'] = '您好，' . $_SESSION['house']['village_name'] . '业主，姓名为' . $data['visitor_name'] . '，手机号为' . $data['visitor_phone'] . '的访客正在等候，需要您的确认！';
                    } else {
                        $sms_data['content'] = '您好，' . $_SESSION['house']['village_name'] . '业主，手机号为' . $data['visitor_phone'] . '的访客正在等候，需要您的确认！';
                    }
                    Sms::sendSms(($sms_data));
                    M('House_village')->where(array('village_id' => $data['village_id']))->setDec('now_sms_number');
                }
                return array('status' => 1, 'msg' => '添加成功！');
            } else {
                return array('status' => 0, 'msg' => '添加失败！');
            }
        } else {
            if (!$this->create()) {
                return array('status' => 0, 'msg' => $this->getError());
            } else {
                //发送微信模板消息start
                $insert_info = $this->data;
                if ($insert_id = $this->add()) {
                    $village_info = M('House_village')->where(array('village_id' => $data['village_id']))->find();
                    $property_id = M('House_village')->where(['village_id'=>$data['village_id']])->getField('property_id');
                    $userInfo = $database_user->get_user($insert_info['owner_uid']);
//                    if ($insert_info['visitor_name']) {
//                        $work_info = '\n姓名为：' . $insert_info['visitor_name'] . '  手机号为' . $insert_info['visitor_phone'] . '的访客正在等候，需要您的确认！';
//                    } else {
//                        $work_info = '\n手机号为' . $insert_info['visitor_phone'] . '的访客正在等候，需要您的确认！';
//                    }
                    if ($userInfo['openid'] && !$insert_info['status']) {
                        $db_house_village = D('House_village');
                        $app_version = intval($_POST['app_version']);
                        $deviceId = trim($_POST['Device-Id']);
                        $param = [
                            'pageName' => 'visitorRegistration',
                            'isAll' => true,
                            'urlParam' => 'village_id=' . $insert_info['village_id'],
                        ];
                        $visitorRegistrationUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
                        $href = $visitorRegistrationUrl;
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        //信息要修改
                        //如果有访客姓名，则同时提示访客姓名
                        $model->sendTempMsg('OPENTM408101810',//todo 类目模板OPENTM408101810
                            array(
                                'href' => $href, 
                                'wecha_id' => $userInfo['openid'], 
                                'first' => '您好，'.$_SESSION['house']['village_name'].'业主，您有新访客到访', 
                                'keyword1' => $insert_info['visitor_name'],
                                'keyword2' => $insert_info['visitor_phone'], 
                                'keyword3' => date('Y年m月d日 H时i分'),
                                'remark' => '\n访客正在等候，需要您的确认！',
                                'new_info' => [//新版本发送需要的信息
                                    'tempKey'=>'43728',//新模板号
                                    'thing2'=>$insert_info['visitor_name'],//来访人员
                                    'phone_number10'=>$insert_info['visitor_phone'],//联系电话
                                    'time9'=>date('Y年m月d日 H:i'),//拜访时间
                                ],
                            ),0,$property_id,1);
                    }
                    if (!$insert_info['status'] && C('config.village_sms') == 1 && $village_info['now_sms_number'] > 0) {
                        $sms_data['uid'] = $insert_info['owner_uid'];
                        $sms_data['mobile'] = $insert_info['owner_phone'];
                        $sms_data['sendto'] = 'user';
                        $sms_data['village_id'] = $data['village_id'];
                        $sms_data['type'] = 'village_vistor';
                        if ($insert_info['visitor_name']) {
                            $sms_data['content'] = '您好，' . $_SESSION['house']['village_name'] . '业主，姓名为' . $insert_info['visitor_name'] . '，手机号为' . $insert_info['visitor_phone'] . '的访客正在等候，需要您的确认！';
                        } else {
                            $sms_data['content'] = '您好，' . $_SESSION['house']['village_name'] . '业主，手机号为' . $insert_info['visitor_phone'] . '的访客正在等候，需要您的确认！';
                        }
                        Sms::sendSms(($sms_data));
                        M('House_village')->where(array('village_id' => $data['village_id']))->setDec('now_sms_number');
                    }
                    return array('status' => 1, 'msg' => '添加成功！');
                    //发送微信模板消息end
                } else {
                    return array('status' => 0, 'msg' => '添加失败！');
                }
            }
        }
    }


    public function visitor_bind_user($village_id,$phone,$name,$address_arr,$now_time=0,$visitParams = [])
    {
        if (!$now_time) {
            $now_time = time();
        }
        $visitTime = isset($visitParams['visitTime']) && $visitParams['visitTime'] ? $visitParams['visitTime'] : '';

        $userInfo = D('User')->where(['phone'=>$phone])->field('uid,phone')->find();
        if(!$userInfo)
        {
            $data_user = array(
                'nickname' 	=> $name,
                'phone' 	=> $phone,
                'source' 	=> 'houseautoreg',
            );
            $reg_result = D('User')->autoreg($data_user);
            $uid = $reg_result['uid'];
        }else{
            $uid = $userInfo['uid'];
        }
        $arr = [
            'village_id'=>$village_id,
            'uid'=>$uid,
            'phone'=>$phone,
            'name'=>$name,
            'type'=>6,
            'usernum'=>rand(1000,9999).'-'.$now_time,
            'single_id'=>$address_arr['single_id'],
            'floor_id'=>$address_arr['floor_id'],
            'layer_id'=>$address_arr['layer_id'],
            'vacancy_id'=>$address_arr['vacancy_id'],
            'add_time' => $now_time
        ];
        if ($visitTime > 0) {
            $arr['property_starttime'] = strtotime(date('Y-m-d 00:00:00', $visitTime));
            $arr['property_endtime'] = strtotime(date('Y-m-d 23:59:59', $visitTime));
        }
        
        $pigcms_id = D('House_village_user_bind')->add($arr);
        $data['uid'] = $uid;
        $data['bind_id'] = $pigcms_id;

        return $data;
    }
    
    public function house_village_visitor_edit($where , $data){
        if(!$where || !$data){
            return false;
        }

        if(!$this->create()){
            return array('status'=>0,'msg'=>$this->getError());
        }else{
            if($this->where($where)->save()){
                return array('status'=>1,'msg'=>'放行成功！');
            }else{
                return array('status'=>0,'msg'=>'放行失败！');
            }
        }
    }


    public function house_village_visitor_page_list($where , $field = '*' , $order = 'id desc',$pageSize = 20){
        if(!$where){
            return false;
        }

        import('@.ORG.merchant_page');
        $count = $this->where($where)->count();
        $p = new Page($count,$pageSize,'page');

        $house_village_visitor_list = $this->where($where)->field($field)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
       // print_r($house_village_visitor_list);exit;
        if ($house_village_visitor_list) {
            $status_desc = $this->status_desc;
            $d_house_village=D('House_village');
            $village_info = $d_house_village->field('*')->where(array('village_id'=>$house_village_visitor_list['0']['village_id']))->find();
            foreach($house_village_visitor_list as &$val) {
                if ($status_desc[$val['status']]) {
                    $val['statusDesc'] = $status_desc[$val['status']];
                } else {
                    $val['statusDesc'] = '未知状态';
                }
                $status_desc_color = isset($this->status_desc_color[$val['status']]) ? $this->status_desc_color[$val['status']] : '';
                $val['status_desc_color'] = $status_desc_color;
                $val['add_time_str']='--';
                if($val['add_time']>100){
                    $val['add_time_str']=date('Y-m-d H:i:s',$val['add_time']);
                }
                $val['pass_time_str']='--';
                if($val['pass_time']>100){
                    $val['pass_time_str']=date('Y-m-d H:i:s',$val['pass_time']);
                }

             //    if (!$val['owner_address'] && $val['owner_name'] && $val['owner_phone']) {
                    $where_bind = [
                        'name'=>$val['owner_name'],
                        'phone' => $val['owner_phone'],
                        'type' => ['in', [0,1,2,3]],
                        'status' => 1,
                        'village_id' => $val['village_id'],
                    ];
                    if ($val['vacancy_id']) {
                        $where_bind['vacancy_id'] = $val['vacancy_id'];
                    }
                    $user_bind = M('House_village_user_bind')->where($where_bind)->field('village_id,single_id,floor_id,layer_id,vacancy_id,layer_num,room_addrss')->find();
                    if ($user_bind['single_id']&&$user_bind['floor_id']&&$user_bind['layer_id']&&$user_bind['vacancy_id']&&$user_bind['village_id']) {
                        $room_diy_name = D('House_village')->get_single_floor_room($user_bind['single_id'],$user_bind['floor_id'],$user_bind['layer_id'],$user_bind['vacancy_id'],$user_bind['village_id']);
                        $val['owner_address'] = $room_diy_name;
                    } elseif ($user_bind) {
                        $floor_info = M('House_village_floor')->where(array('floor_id' => $user_bind['floor_id']))->field('single_id,floor_name,floor_layer')->find();
                        $word_data = array();
                        $single_id = $user_bind['single_id'] ? $user_bind['single_id'] : ($floor_info['single_id']?$floor_info['single_id']:0);
                        if ($single_id>0) {
                            $single_name =  M('House_village_single')->where(array('id' => $single_id))->getField('single_name');;
                        } else {
                            $single_name = '';
                        }
                        $word_data['layer'] = $user_bind['layer_num'];
                        $word_data['room'] = $user_bind['room_addrss'];
                        $word_data['floor_name'] = $floor_info['floor_name'];
                        if (!$single_name) {
                            $single_name = $floor_info['floor_layer'];
                        }
                        $word_data['single_name'] = $single_name;

                        if (!empty($word_data['single_name']) || !empty($word_data['floor_name']) || !empty($word_data['room']) || !empty($word_data['layer'])) {
                            $room_diy_name = D('House_village')->word_replce_msg($word_data,$where['village_id']);
                            $val['owner_address'] = $room_diy_name;
                        }
                    }

                if(isset($val['owner_phone']) && !empty($val['owner_phone'])){
                    $val['owner_phone']=$d_house_village->phone_desensitization_v($val['owner_phone'],$val['village_id'],$village_info);
                }

                if(isset($val['visitor_phone']) && !empty($val['visitor_phone'])){
                    $val['visitor_phone']=$d_house_village->phone_desensitization_v($val['visitor_phone'],$val['village_id'],$village_info);
                }
            }
        }

     //  print_r($house_village_visitor_list);exit;
        $list['list'] = $house_village_visitor_list;
        $list['pagebar'] = $p->show();
        if($list){
            return array('status'=>1,'list'=>$list);
        }else{
            return array('status'=>0,'list'=>$list);
        }
    }

    public function house_village_visitor_page_list_api($where , $field = true , $order = 'id desc',$pageSize = 20,$page_index=1){
        if(!$where){
            return false;
        }
        $house_village_visitor_list = $this->where($where)->field($field)->order($order)->page($page_index,$pageSize)->select();
        //var_dump(D('')->_sql());
        if(empty($house_village_visitor_list)){
            return [];
        }else{
            return $house_village_visitor_list;
        }


    }


    public function house_village_visitor_list($where , $field = true , $order = 'id desc',$pageSize = 20){
        if(!$where){
            return false;
        }

        $house_village_visitor_list = $this->where($where)->field($field)->order($order)->select();
        $list = $house_village_visitor_list;
        if($list){
            return array('status'=>1,'list'=>$list);
        }else{
            return array('status'=>0,'list'=>$list);
        }
    }


    public function house_village_visitor_del($where){
        if(!$where){
            return false;
        }
        $visitorInfo = $this->where($where)->find();
        if ($visitorInfo) {
            $insert_id = $this->where($where)->delete();
            if ($insert_id !== false && isset($visitorInfo['car_id']) && trim($visitorInfo['car_id']) && isset($visitorInfo['village_id']) && intval($visitorInfo['village_id']) > 0) {
                invoke_v20_service('\app\community\model\service\Car\CarHandleGetInfoService/clearVisitorInfo', ['village_id' => $visitorInfo['village_id']]);
            }
            //删除对应的同步人员信息
            $d_house_village_user_bind = D('House_village_user_bind');
            $condition_user = [];
            $condition_user['pigcms_id'] = $visitorInfo['visitor_bind_id'];
            $house_village_bind = $d_house_village_user_bind->field(true)->where($condition_user)->find();
            if ($house_village_bind) {
                $whereBindType = [
                    'bind_type'   => 40,
                    'device_type' => 5,
                    'person_id'   => $house_village_bind['pigcms_id'],// 访客对应小区身份id
                    'groupID'     => $house_village_bind['village_id'],// 访客要拜访小区
                ];
                $db_face_user_bind_device = D('Face_user_bind_device');
                $aboutFaceUser = $db_face_user_bind_device->where($whereBindType)->find();
                $cardNo = isset($aboutFaceUser['code']) && $aboutFaceUser['code'] ? $aboutFaceUser['code'] : '';
                if ($cardNo) {
                    $cardNos = [];
                    $cardNos[] = $cardNo;
                } else {
                    $cardNos = [];
                }
                try {
                    D('House_village_face_door')->del_a3_user_bind($house_village_bind['pigcms_id'], $house_village_bind['village_id'],$house_village_bind, $cardNos);
                }catch (Exception $e) {
                    fdump_api(['删除时同步设备出错'.__LINE__,$house_village_bind['pigcms_id'],$house_village_bind,$e->getMessage()],'house_user_bind/del_a3_user_bind_log_err',1);
                }
            }
        } else {
            $insert_id = 1;
        }
        
        if($insert_id){
            return array('status'=>1,'msg'=>'删除数据成功！');
        }else{
            return array('status'=>0,'msg'=>'删除数据失败！');
        }
    }


    public function house_village_visitor_detail($where,$field=true){
        if(!$where){
            return false;
        }
        $visitor_type = $this->visitor_type;

        $detail = $this->where($where)->field($field)->find();
        $detail['visitor_type'] = $visitor_type[$detail['visitor_type']];
        if ($this->status_desc[$detail['status']]) {
            $detail['statusDesc'] = $this->status_desc[$detail['status']];
        } else {
            $detail['statusDesc'] = '未知状态';
        }
        $detail['status_desc_color'] = isset($this->status_desc_color[$detail['status']]) ? $this->status_desc_color[$detail['status']] : '';

            $where_bind = [
                'name'=>$detail['owner_name'],
                'phone' => $detail['owner_phone'],
                'type' => ['in', [0,1,2,3]],
                'status' => 1,
            ];
            $user_bind = M('House_village_user_bind')->where($where_bind)->field('single_id,floor_id,layer_num,room_addrss')->find();

            if ($user_bind) {
                $floor_info = M('House_village_floor')->where(array('floor_id' => $user_bind['floor_id']))->field('single_id,floor_name,floor_layer')->find();
                $word_data = array();
                $single_id = $user_bind['single_id'];
                if ($floor_info['single_id']>0 && !$single_id) {
                    $single_id = $floor_info['single_id'];
                }
                $single_name = M('House_village_single')->where(array('id' =>$single_id))->getField('single_name');
                $word_data['layer'] = $user_bind['layer_num'];
                $word_data['room'] = $user_bind['room_addrss'];
                $word_data['floor_name'] = $floor_info['floor_name'];
                if (!$single_name) {
                    $single_name = $floor_info['floor_layer'];
                }
                $word_data['single_name'] = $single_name;
                if (!empty($word_data['single_name']) || !empty($word_data['floor_name']) || !empty($word_data['room']) || !empty($word_data['layer'])) {
                    $room_diy_name = D('House_village')->word_replce_msg($word_data,$detail['village_id']);
                    $detail['owner_address'] = $room_diy_name;
                  //  print_r($room_diy_name);exit;
                }
            }
      //  }

        if($detail){
            return array('status'=>1,'detail'=>$detail);
        }else{
            return array('status'=>0,'detail'=>$detail);
        }
    }


    public function ajax_house_village_visitor_search($where,$page=0,$page_coun=0){
        if(!$where){
            return false;
        }

        $where['village_id'] = $_SESSION['house']['village_id'];
        $count	= $this->where($where)->count();
        if($page && $page_coun){
			$list = $this->where($where)->page($page,$page_coun)->select();
        }else{
			$list = $this->where($where)->select();
			foreach($list as $k=>$v){
		    	$list[$k]['visitor_type'] = $this->visitor_type[$v['visitor_type']];
		    }
        }
        if($list){
        	if($page && $page_coun){
        		$arr	=	array(
					'totalPage' => ceil($count/$page_coun),
					'page' => intval($page),
					'list_count' => count($list),
					'list'	=>	isset($list)?$list:array(),
        		);
				return array('status'=>1,'list'=>$arr);
        	}else{
				return array('status'=>1,'list'=>$list);
        	}
        }else{
            return array('status'=>0,'list'=>$list);
        }
    }
}
?>

