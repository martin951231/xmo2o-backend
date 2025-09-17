<?php


namespace app\community\controller\manage_api\v1;

use app\common\model\service\config\ConfigCustomizationService;
use app\common\model\service\send_message\SmsService;
use app\community\controller\manage_api\BaseController;
use app\community\model\db\FaceUserBindDevice;
use app\community\model\service\Device\HouseDeviceQrcodeService;
use app\community\model\service\Device\HouseVillageDeviceCmdService;
use app\community\model\service\Device\XiHanEventService;
use app\community\model\service\HouseFaceDeviceService;
use app\community\model\service\HouseVillageConfigService;
use app\community\model\service\HouseVillageUserService;
use app\community\model\service\HouseVillageVisitorService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\UserService;
use app\common\model\service\weixin\TemplateNewsService;
use app\consts\Cloud260Const;
use app\consts\DeviceConst;

class VisitorController extends BaseController
{

    /**
     * 获取已通行和未通行访客列表
     * @author lijie
     * @date_time 2020/07/13
     * @return \json|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function visitorLists()
    {
        $village_id = $this->request->post('village_id','');
        $status = $this->request->post('status','');
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',15);
        $con = $this->request->post('con','');
        if(empty($status) || !$village_id) {
            return api_output_error(1001,'必传参数缺失');
        }
        $field = 'id,visitor_name,visitor_phone,owner_name,owner_phone,owner_address,add_time,status,pass_time,visitor_type,time';
        $order = 'id DESC';
        $where[] = ['village_id','=',$village_id];
        if($status == 1) {
            $where[] = ['status','in','0,3'];
        } else {
            $where[] = ['status','in','1,2,4,5'];
        }
        if(!empty($con)) {
            $where[] = ['visitor_phone|visitor_name','like','%'.$con.'%'];
        }
        $house_village_visitor = new HouseVillageVisitorService();
        $data = $house_village_visitor->getVisitorLists($where,$field,$page,$limit,$order);
        return api_output(0,$data,'获取成功');
    }

    /**
     * 添加访客记录
     * @author lijie
     * @date_time 2020/07/13
     * @return \json
     */
    public function addVisitor()
    {
        $info = $this->getLoginInfo();
        $village_id = $this->login_info['village_id'];
        $post_params = $this->request->post();
        if(empty($post_params['visitor_name']) || empty($post_params['visitor_phone']) || empty($post_params['owner_phone']) || empty($post_params['visitor_type']) || empty($post_params['village_id'])){
            return api_output_error(1001,'请将信息填写完整');
        }
        $house_village = new HouseVillageUserService();
        //start
        $houseVillageService = new HouseVillageService();
        $address= '';
        if($post_params['village_id']){
            $village_id = $post_params['village_id'];
        }
        $where[] = ['village_id','=',$village_id];
        $where[] = ['type','in', [0,1,2,3]];
        $where[] = ['status','=',1];
        if(isset($post_params['uid'])){
            $data['owner_bind_id'] = $post_params['pigcms_id'];
            $where[] = ['pigcms_id','=',$post_params['pigcms_id']];
        }else {
            $where[] = ['phone','=',$post_params['owner_phone']];
        }
        //end
        $field = 'pigcms_id,village_id,name,uid,phone,address,single_id,floor_id,layer_id,vacancy_id';
        $village_info = $house_village->getHouseUserBindWhere($where, $field);
        if( empty($village_info)  || $post_params['village_id'] != $village_info['village_id']) {
            return api_output_error(1001,'业主不存在，请重新填写业主手机号');
        }
        if(empty($village_info['uid'])) {
            return api_output_error(1003,'未找到该业主');
        }
        //start
        $data['single_id'] = isset($post_params['single_id']) ? $post_params['single_id'] : $village_info['single_id'];
        $data['floor_id'] = isset($post_params['floor_id']) ? $post_params['floor_id'] : $village_info['floor_id'];
        $data['layer_id'] = isset($post_params['layer_id']) ? $post_params['layer_id'] : $village_info['layer_id'];
        $data['vacancy_id'] = isset($post_params['vacancy_id']) ? $post_params['vacancy_id'] : $village_info['vacancy_id'];
        $address = $houseVillageService->getSingleFloorRoom($data['single_id'], $data['floor_id'], $data['layer_id'], $data['vacancy_id'], $post_params['village_id']);
        //end
        $data['village_id'] = $village_info['village_id'];
        $data['owner_uid'] = $village_info['uid'];
        $data['owner_name'] = $village_info['name'];
        $data['owner_address'] = $address ? $address : $village_info['address'];
        $data['owner_bind_id'] = $village_info['pigcms_id'];
        $data['add_time'] = time();
        $data['visitor_name'] = $post_params['visitor_name'];
        $data['visitor_phone'] = $post_params['visitor_phone'];
        $data['owner_phone'] = $post_params['owner_phone'];
        $data['visitor_type'] = $post_params['visitor_type'];
        $data['remarks'] = $post_params['remarks'];
        $house_village_visitor = new HouseVillageVisitorService();

        if ($data['single_id'] && $data['floor_id'] && $data['layer_id'] && $data['vacancy_id']) {
            $address_arr = [
                'single_id'=>$data['single_id'],
                'floor_id'=>$data['floor_id'],
                'layer_id'=>$data['layer_id'],
                'vacancy_id'=>$data['vacancy_id'],
            ];
            $visitTime = isset($data['add_time']) && $data['add_time'] ? intval($data['add_time']) : time();
            $visitParams = ['visitTime' => $visitTime];
            $user_bind = $house_village->visitor_bind_user($village_id,$data['visitor_phone'],$data['visitor_name'],$address_arr,$visitParams);
            if ($user_bind['bind_id']) {
                $data['visitor_bind_id'] = $user_bind['bind_id'];
            }
        }
        $res = $house_village_visitor->addVisitor($data);
        if($res){
            $getHikFaceDeviceVisitorQRCode = (new ConfigCustomizationService())->getHikFaceDeviceVisitorQRCode();
            fdump_api(['msg' => '移动管理员是否添加A5访客二维码','$params' => $getHikFaceDeviceVisitorQRCode], 'visitor/manageAddVisitorLog',1);
            $add_time = isset($data['add_time']) && $data['add_time'] ? date('Y-m-d', $data['add_time']) : date('Y-m-d');
            $effectTimeStamp = strtotime($add_time);
            $expireTimeStamp = strtotime($add_time.' 23:59:59');
            if ($getHikFaceDeviceVisitorQRCode) {
                // 下发访客二维码
                $visitor_open_number_limit = (new HouseDeviceQrcodeService())->getOpenNumberMaxLimit($village_id);
                $cardNo = 100000000000 + $res;
                $visitor_param = [
                    'cardNo'     => $cardNo,
                    'effectTime' => date('ymdHis', $effectTimeStamp),
                    'expireTime' => date('ymdHis', $expireTimeStamp),
                    'openTimes'  => $visitor_open_number_limit,
                ];
                $params = [
                    'param' => $visitor_param,
                ];
                fdump_api(['msg' => '获取A5设备二维码参数','$params' => $params, 'visitor_open_number_limit' => $visitor_open_number_limit], 'visitor/manageAddVisitorLog',1);
                $visitor_device_result = invoke_cms_model('Face_door_a5_service/getVisitorQrcode', $params);
                if ($visitor_device_result['error_no'] == 0) {
                    $visitor_device_result = $visitor_device_result['retval'];
                }
                fdump_api(['msg' => '获取A5设备二维码','$params' => $params, '$visitor_device_result' => $visitor_device_result], 'visitor/manageAddVisitorLog',1);
                $qrCodeUrl = isset($visitor_device_result['qrCodeUrl']) && $visitor_device_result['qrCodeUrl'] ? $visitor_device_result['qrCodeUrl'] : '';
                $db_face_user_bind_device = new FaceUserBindDevice();
                $bind_arr = [
                    'bind_type'   => 40,
                    'bind_id'     => $res,
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
                fdump_api(['msg' => '访客二维码关联记录', 'bind_arr' => $bind_arr], 'visitor/manageAddVisitorLog',1);
                $id = $db_face_user_bind_device->addData($bind_arr);
                if ($id && $qrCodeUrl) {
                    $params1 = ['uid' => 0, 'pigcms_id' => $user_bind['bind_id']];
                    fdump_api(['msg' => '访客二维码添加成功下发', 'params1' => $params1], 'visitor/manageAddVisitorLog',1);
                    $plans = invoke_cms_model('House_face_img/house_user_face_device_info', $params1);
                    fdump_api(['msg' => '下发任务反馈结果', 'plans' => $plans], 'visitor/manageAddVisitorLog',1);
                }
            }

            $getFaceCloud260Judge = (new ConfigCustomizationService())->getFaceCloud260Judge();
            fdump_api(['msg' => '移动管理端-是否添加260云访客二维码','$params' => $getFaceCloud260Judge, 'data' => $data], '260Cloud/ownerAddVisitorLog',1);
            if ($getFaceCloud260Judge) {
                $visitor_open_number_limit = (new HouseDeviceQrcodeService())->getOpenNumberMaxLimit($village_id);
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
                    'surplusOpenTimes'  => $visitor_open_number_limit, // 剩余次数
                ];
                $db_face_user_bind_device = new FaceUserBindDevice();
                $bind_arr = [
                    'bind_type'   => Cloud260Const::CLOUD_260_DEVICE_TYPE,
                    'bind_id'     => $data['visitor_bind_id'],// 访客对应小区身份id
                    'device_type' => Cloud260Const::CLOUD_260_FACE_USER_BIND_DEVICE_BIND_TYPE,
                    'open_key'    => 'visitor',
                    'open_key_time' => $expireTimeStamp,
                    'code'        => $employeeQRCode,
                    'person_id'   => $res,// 访客id
                    'personID'    => isset($data['owner_uid']) ? $data['owner_uid'] : '',// 访客对应业主uid
                    'group_id'    => isset($data['vacancy_id']) ? $data['vacancy_id'] : '',// 访客要拜访房间id
                    'groupID'     => isset($data['village_id']) ? $data['village_id'] : '',// 访客要拜访小区
                    'person_text' => json_encode($visitor_param,JSON_UNESCAPED_UNICODE), // 获取访客二维码时候的传参
                    'face_txt'    => json_encode($employeeQRCodeArr,JSON_UNESCAPED_UNICODE), // 反馈结果
                ];
                $bind_arr['face_time'] = time();
                $bind_arr['add_time'] = $effectTimeStamp;
                fdump_api(['msg' => '移动管理端-访客二维码关联记录', 'bind_arr' => $bind_arr], '260Cloud/ownerAddVisitorLog',1);
                $id = $db_face_user_bind_device->addData($bind_arr);
                if ($id) {
                    $params1 = ['uid' => 0, 'pigcms_id' => $user_bind['bind_id']];
                    fdump_api(['msg' => '移动管理端-访客二维码添加成功下发', 'params1' => $params1], '260Cloud/ownerAddVisitorLog',1);
                    $plans = invoke_cms_model('House_face_img/house_user_face_device_info', $params1);
                    fdump_api(['msg' => '移动管理端-下发任务反馈结果', 'plans' => $plans], '260Cloud/ownerAddVisitorLog',1);
                }
            }
            $hasXiHan = 0;
            $getXiHanJudge = (new ConfigCustomizationService())->getFaceXiHanDoorSwitch();
            if ($getXiHanJudge) {
                $sHouseFaceDeviceService = new HouseFaceDeviceService();
                $whereFace = [];
                $whereFace[] = ['village_id', '=', $village_id];
                $whereFace[] = ['device_type', '=', DeviceConst::XI_HAN_DEVICE_TYPE];
                $whereFace[] = ['is_del', '=', 0];
                $deviceInfo = $sHouseFaceDeviceService->getHouseFaceDeviceInfo($whereFace);
                if (isset($deviceInfo['device_id']) && $deviceInfo['device_id']) {
                    $hasXiHan = 1;
                }
            }
            if ($hasXiHan == 1) {
                $xiHanParams = ['village_id' => $village_id, 'pigcms_id' => $data['visitor_bind_id']];
                $xiHanParams['hasXiHan'] = $hasXiHan;
                (new XiHanEventService())->getUserOpenCode($xiHanParams);
            }
            $userService =new UserService();
            $whereArr=array();
            if($village_info['uid']>0){
                $whereArr=array('uid'=>$village_info['uid']);
            }else if(!empty($village_info['phone'])){
                $whereArr=array('phone'=>$village_info['phone']);
            }
            if(!empty($whereArr)){
                $userInfo = $userService->getUserOne($whereArr);
                $village_info_extend = $houseVillageService->getHouseVillageInfoExtend(['village_id' => $village_info['village_id']]);
                $urge_notice_type = 0;
                if ($village_info_extend && isset($village_info_extend['urge_notice_type'])) {
                    //1短信通知2微信模板通知3短信和微信模板通知
                    $urge_notice_type = $village_info_extend['urge_notice_type'];
                }
                $village_info_data = $houseVillageService->getHouseVillageInfo(array('village_id' => $village_info['village_id']), 'village_name,property_id,now_sms_number');
                if ($urge_notice_type != 1 && $userInfo && !empty($userInfo['openid'])) {
                    $templateNewsService = new TemplateNewsService();
                    $param = [
                        'pageName' => 'visitorRegistration',
                        'isAll' => true,
                        'urlParam' => 'village_id=' . $village_info['village_id'],
                    ];
                    $visitorRegistrationUrl = $houseVillageService->villagePagePath('', '', $param);
                    $href = $visitorRegistrationUrl;
                    $href .= '&pigcms_id=' . $village_info['pigcms_id'];
                    $datamsg = [
                        'tempKey' => 'OPENTM408101810',//todo 类目模板OPENTM408101810
                        'dataArr' => [
                            'href' => $href,
                            'wecha_id' => $userInfo['openid'],
                            'first' => '您好，' . $village_info_data['village_name'] . '业主，您有新访客到访',
                            'keynote1' => $data['visitor_name'],
                            'keynote2' => $data['visitor_phone'],
                            'keyword3' => date('Y年m月d日 H时i分'),
                            'remark' => '\n访客正在等候，需要您的确认！',
                            'new_info' => [//新版本发送需要的信息
                                'tempKey'=>'43728',//新模板号
                                'thing2'=>$data['visitor_name'],//来访人员
                                'phone_number10'=>$data['visitor_phone'],//联系电话
                                'time9'=>date('Y年m月d日 H:i'),//拜访时间
                            ],
                        ]
                    ];

                    $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr'], 0, $village_info_data['property_id'], 1);
                }
                if ($urge_notice_type != 2 && cfg('village_sms') == 1 && $data['owner_phone'] && $village_info_data['now_sms_number'] > 0) {
                    $sms_data = array('type' => 'village_vistor');
                    $sms_data['uid'] = $data['owner_uid'];
                    $sms_data['village_id'] = $data['village_id'];
                    $sms_data['mobile'] = $data['owner_phone'];
                    $sms_data['sendto'] = 'user';
                    $sms_data['mer_id'] = 0;
                    $sms_data['store_id'] = 0;
                    if ($data['visitor_name']) {
                        $sms_data['content'] = '您好，' . $village_info_data['village_name'] . '业主，姓名为' . $data['visitor_name'] . '，手机号为' . $data['visitor_phone'] . '的访客正在等候，需要您的确认！';
                    } else {
                        $sms_data['content'] = '您好，' . $village_info_data['village_name'] . '业主，手机号为' . $data['visitor_phone'] . '的访客正在等候，需要您的确认！';
                    }
                    $sms = (new SmsService())->sendSms($sms_data);
                    /*
                        $whereArr = array('village_id' => $data['village_id']);
                        $houseVillageService->updateFieldMinusNum($whereArr, 1, 'now_sms_number');
                    */

                }

            }
            return api_output(0,'','添加成功');
            //发送模板消息和短信
        }else{
            return api_output_error(1005,'服务异常');
        }
    }
    

    /**
     * 获取访客类型
     * @author lijie
     * @date_time 2020/7/20 14:55
     */
    public function visitorType()
    {
        $data[0]['type'] = 1;
        $data[0]['name'] = '亲属';
        $data[1]['type'] = 2;
        $data[1]['name'] = '朋友';
        $data[2]['type'] = 3;
        $data[2]['name'] = '同事';
        $data[3]['type'] = 255;
        $data[3]['name'] = '其他';
        return api_output(0,$data,'获取成功');
    }

    /**
     * 确认通行
     * @author lijie
     * @date_time 2020/07/13
     */
    public function confirmPass()
    {
        $id = $this->request->post('id', 0);
        if (empty($id)) {
            return api_output_error(1001, '必传参数缺失');
        }
        $where['id'] = $id;
        $save['status'] = 2;
        $save['pass_time'] = time();
        $house_village_visitor = new HouseVillageVisitorService();
        $res = $house_village_visitor->updateVisitorStatus($where, $save);
        if ($res) {
            return api_output(0, '', '通行成功');
        } else {
            return api_output_error(1005, '服务异常');
        }
    }

    /**
     * 获取访客和主人信息
     * @author lijie
     * @date_time 2020/09/02 9:47
     */
    public function visitorInfo()
    {
        $visitor_id = $this->request->post('visitor_id',0);
        if(!$visitor_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $house_village_visitor = new HouseVillageVisitorService();
        $where['v.id'] = $visitor_id;
        $field = 'v.visitor_name,v.visitor_phone,v.owner_name,v.owner_phone,v.owner_address,hvb.type,v.add_time';
        $data = $house_village_visitor->getVisitorInfo($where,$field);
        return api_output(0,$data,'获取成功');
    }

    /**
     * 删除访客
     * @author lijie
     * @date_time 2020/11/16
     */
    public function visitorDel()
    {
        $visitor_id = $this->request->post('id',0);
        if(!$visitor_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $house_village_visitor = new HouseVillageVisitorService();
        $res = $house_village_visitor->delVisitor(['id'=>$visitor_id]);
        return api_output(0,[],'删除成功');
    }

    /**
     * Notes: 获取楼栋
     * @return \json
     * @datetime: 2020/12/5 16:17
     */
    public function villageSingleList() {
        // 获取登录信息
//        $arr = $this->getLoginInfo();
//        if (isset($arr['status']) && $arr['status']!=1000) {
//            return api_output($arr['status'],[],$arr['msg']);
//        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        $service_house_village = new HouseVillageService();

        $phone = $this->request->param('owner_phone','','trim');
        if($phone) {
            $serviceHouseVillageUser = new HouseVillageUserService();
            $map[] = ['phone', '=', $phone];
            $map[] = ['status', '=', 1];
            $map[] = ['village_id', '=', $village_id];
            $map[] = ['type', 'in', [0,1,2,3]];
            $user_bind = $serviceHouseVillageUser->getLimitUserList($map, 0, 'single_id');
            if (empty($user_bind)) {
                return api_output(1001, [], '该用户不存在！');
            }
            $single_arr = [];
            foreach ($user_bind as $k => $value) {
                $single_arr[] = $value['single_id'];
            }
            $where[] = ['id','in',$single_arr];
        }

        $where[] = ['village_id', '=', $village_id];
        $where[] = ['status', '=', 1];

        $list = $service_house_village->getSingleList($where,true,'sort DESC,id desc');
        $dataList = [];
        foreach($list as $k=>$v){
            $item = [];
            $item['village_id'] = $v['village_id'];
            $item['single_name'] = $v['single_name'];
            $item['single_id'] = $v['id'];
            $dataList[] = $item;
        }
        $arr['list'] = $dataList;
        return api_output(0,$arr);
    }

    /**
     * Notes: 获取单元
     * @return \json
     * @datetime: 2020/12/5 16:17
     */
    public function villageFloorList() {
        // 获取登录信息
//        $arr = $this->getLoginInfo();
//        if (isset($arr['status']) && $arr['status']!=1000) {
//            return api_output($arr['status'],[],$arr['msg']);
//        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        $single_id = $this->request->param('single_id','','intval');
        if (empty($single_id)) {
            return api_output(1001,[],'缺少对应楼栋！');
        }
        $service_house_village = new HouseVillageService();

        $phone = $this->request->param('owner_phone','','trim');
        if($phone) {
            $serviceHouseVillageUser = new HouseVillageUserService();
            $map[] = ['phone', '=', $phone];
            $map[] = ['status', '=', 1];
            $map[] = ['village_id', '=', $village_id];
            $map[] = ['type', 'in', [0,1,2,3]];
            $map[] = ['single_id','=',$single_id];
//            $map[] = ['floor_id','>',0];
//            $map[] = ['layer_id','>',0];
//            $map[] = ['vacancy_id','>',0];
            $user_bind = $serviceHouseVillageUser->getLimitUserList($map, 0, 'floor_id');
            if (empty($user_bind)) {
                return api_output(1001, [], '该用户不存在！');
            }
            $floor_arr = [];
            foreach ($user_bind as $k => $value) {
                $floor_arr[] = $value['floor_id'];
            }
            $where[] = ['floor_id','in',$floor_arr];
        }

        $where[] = ['village_id', '=', $village_id];
        $where[] = ['single_id', '=', $single_id];
        $where[] = ['status', '=', 1];

        $list = $service_house_village->getFloorList($where,true,'sort DESC,floor_id desc');
        $dataList = [];
        foreach($list as $k=>$v){
            $item = [];
            $item['village_id'] = $v['village_id'];
            $item['floor_id'] = $v['floor_id'];
            $item['floor_name'] = $v['floor_name'];
            $item['floor_layer'] = $v['floor_layer'];
            $dataList[] = $item;
        }
        $arr['list'] = $dataList;
        $app_type = $this->request->param('app_type','','trim');
        if ($app_type=='android' && !$dataList) {
            return api_output(1001,[],'该楼栋下没有单元，请联系物业添加或选择其他楼栋！');
        }
        return api_output(0,$arr);
    }

    /**
     * Notes: 获取楼层
     * @return \json
     * @datetime: 2020/12/5 16:16
     */
    public function villageLayerList() {
        // 获取登录信息
//        $arr = $this->getLoginInfo();
//        if (isset($arr['status']) && $arr['status']!=1000) {
//            return api_output($arr['status'],[],$arr['msg']);
//        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        $floor_id = $this->request->param('floor_id','','intval');
        if (empty($floor_id)) {
            return api_output(1001,[],'缺少对应单元！');
        }
        $service_house_village = new HouseVillageService();

        $phone = $this->request->param('owner_phone','','trim');
        if($phone) {
            $serviceHouseVillageUser = new HouseVillageUserService();
            $map[] = ['phone', '=', $phone];
            $map[] = ['status', '=', 1];
            $map[] = ['village_id', '=', $village_id];
            $map[] = ['type', 'in', [0,1,2,3]];
//            $map[] = ['single_id','>',0];
            $map[] = ['floor_id','=',$floor_id];
//            $map[] = ['layer_id','>',0];
//            $map[] = ['vacancy_id','>',0];
            $user_bind = $serviceHouseVillageUser->getLimitUserList($map, 0, 'layer_id');
            if (empty($user_bind)) {
                return api_output(1001, [], '该用户不存在！');
            }
            $layer_arr = [];
            foreach ($user_bind as $k => $value) {
                $layer_arr[] = $value['layer_id'];
            }
            $where[] = ['id','in',$layer_arr];
        }

        $where[] = ['village_id', '=', $village_id];
        $where[] = ['floor_id', '=', $floor_id];
//        $where[] = ['status', '=', 1];

        $list = $service_house_village->getHouseVillageLayerList($where,true,'sort DESC,id desc');
        $dataList = [];
        foreach($list as $k=>$v){
            $item = [];
            $item['layer_id'] = $v['id'];
            $item['single_id'] = $v['single_id'];
            $item['floor_id'] = $v['floor_id'];
            $item['village_id'] = $v['village_id'];
            $item['layer'] = $v['layer_name'];
            $dataList[] = $item;
        }
        $arr['list'] = $dataList;
        $app_type = $this->request->param('app_type','','trim');
        if ($app_type=='android' && !$dataList) {
            return api_output(1001,[],'该单元下没有楼层，请联系物业添加或选择其他单元！');
        }
        return api_output(0,$arr);
    }

    /**
     * Notes: 获取房间
     * @return \json
     * @datetime: 2020/12/5 16:16
     */
    public function villageRoomList() {
        // 获取登录信息
//        $arr = $this->getLoginInfo();
//        if (isset($arr['status']) && $arr['status']!=1000) {
//            return api_output($arr['status'],[],$arr['msg']);
//        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        $layer_id= $this->request->param('layer_id','','intval');
        if (empty($layer_id)) {
            return api_output(1001,[],'缺少对应楼层！');
        }
        $service_house_village = new HouseVillageService();

        $phone = $this->request->param('owner_phone','');
        if($phone) {
            $serviceHouseVillageUser = new HouseVillageUserService();
            $map[] = ['phone', '=', $phone];
            $map[] = ['status', '=', 1];
            $map[] = ['village_id', '=', $village_id];
            $map[] = ['type', 'in', [0,1,2,3]];
//            $map[] = ['single_id','>',0];
//            $map[] = ['floor_id','>',0];
            $map[] = ['layer_id','=',$layer_id];
//            $map[] = ['vacancy_id','>',0];
            $user_bind = $serviceHouseVillageUser->getLimitUserList($map, 0, 'vacancy_id');
            if (empty($user_bind)) {
                return api_output(1001, [], '该用户不存在！');
            }
            $vacancy_arr = [];
            foreach ($user_bind as $k => $value) {
                $vacancy_arr[] = $value['vacancy_id'];
            }
            $where[] = ['pigcms_id','in',$vacancy_arr];
        }

        $where[] = ['village_id', '=', $village_id];
        $where[] = ['layer_id', '=', $layer_id];
        $where[] = ['is_del', '=', 0];
        $list = $service_house_village->getUserVacancyList($where,true,'sort desc, pigcms_id asc');
        $dataList = [];
        foreach($list as $k=>$v){
            $item = [];
            $item['pigcms_id'] = $v['pigcms_id'];
            $item['room_id'] = $v['pigcms_id'];
            $item['floor_id'] = $v['floor_id'];
            $item['village_id'] = $v['village_id'];
            $item['layer'] = $v['layer'];
            $item['room'] = $v['room'];
            $dataList[] = $item;
        }
        $arr['list'] = $dataList;
        $app_type = $this->request->param('app_type','','trim');
        if ($app_type=='android' && !$dataList) {
            return api_output(1001,[],'该楼层下没有房屋，请联系物业添加或选择其他房屋！');
        }
        return api_output(0,$arr);
    }
    /**
     * Notes:根据业主地址获取业主信息
     * @return \json
     * @author: weili
     * @datetime: 2020/12/5 17:09
     */
    public function getUserInfo(){
//        $arr = $this->getLoginInfo();
//        if (isset($arr['status']) && $arr['status']!=1000) {
//            return api_output($arr['status'],[],$arr['msg']);
//        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        $type = $this->request->param('type','','int');
        $phone = $this->request->param('owner_phone','','trim');
        $where[] = ['village_id','=',$village_id];
        $where[] = ['type','in', [0,1,2,3]];
        $where[] = ['status','=',1];
        if($type == 1) {
//            $single_id = $this->request->param('single_id', '', 'int');
//            $floor_id = $this->request->param('floor_id', '', 'int');
//            $layer_id = $this->request->param('layer_id', '', 'int');
            $vacancy_id = $this->request->param('vacancy_id', '', 'int');
//            if (!$single_id || !$floor_id || !$layer_id || !$vacancy_id) {
            if(!$vacancy_id) {
                return api_output(1001, [], '地址传参异常！');
            }
//            $where[] = ['single_id','=',$single_id];
//            $where[] = ['floor_id','=',$floor_id];
//            $where[] = ['layer_id','=',$layer_id];
            $where[] = ['vacancy_id','=',$vacancy_id];
        }else{
            if(!$phone){
                return api_output(1001, [], '请传有效手机号！');
            }
            $where[] = ['phone','=',$phone];
//            $where[] = ['single_id','>',0];
//            $where[] = ['floor_id','>',0];
//            $where[] = ['layer_id','>',0];
//            $where[] = ['vacancy_id','>',0];
        }
        $serviceHouseVillageUser = new HouseVillageUserService();
        $serviceHouseVillage = new HouseVillageService();
        $user_bind =$serviceHouseVillageUser->getUserInfos($where,'pigcms_id,uid,name,phone,single_id,floor_id,layer_id,vacancy_id');
        if($user_bind){
            $user_bind['address'] = $serviceHouseVillage->getSingleFloorRoom($user_bind['single_id'],$user_bind['floor_id'],$user_bind['layer_id'],$user_bind['vacancy_id'],$village_id);
        }
        $data['user_bind'] = $user_bind;
        $app_type = $this->request->param('app_type','','trim');
        if ($app_type=='android' && !$user_bind) {
            return api_output(1001,[],'无住户信息！');
        }elseif(!$user_bind){
            return api_output(1001,[],'无住户信息！');
        }
        return api_output(0,$data);

    }
}