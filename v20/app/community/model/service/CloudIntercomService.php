<?php
/**
 *======================================================
 * Created by : PhpStorm
 * User: zhanghan
 * Date: 2021/11/30
 * Time: 15:10
 *======================================================
 */

namespace app\community\model\service;


use app\common\model\db\ProcessPlan;
use app\common\model\service\image\ImageService;
use app\common\model\service\config\ConfigCustomizationService;
use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\FaceUserBindDevice;
use app\community\model\db\HouseFaceDevice;
use app\community\model\db\HouseFaceSyncLog;
use app\community\model\db\HouseNewOfflinePay;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseUserLog;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageAboutConfig;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageNmvCard;
use app\community\model\db\HouseVillageNmvCharge;
use app\community\model\db\HouseVillageNmvChargeLog;
use app\community\model\db\HouseVillagePublicArea;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\HouseVillageThirdConfig;
use app\community\model\db\HouseVillageThirdUserInfo;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\User;
use app\common\model\service\UserService as CommonUserService;
use app\community\model\service\Device\HouseVillageDeviceCmdService;
use app\consts\device\DuoPuConst;
use app\traits\device\DuoPuTraits;
use app\traits\house\DeviceTraits;
use customization\customization;
use face\dopu\dopuapi;
use face\wordencryption;
use file_handle\FileHandle;
use think\Exception;
use app\community\model\db\HouseFaceImg;

class CloudIntercomService
{
    use customization;
    use DuoPuTraits, DeviceTraits;

    protected $tip = "[请隔一段时间再试2次，如还不行请联系朵普设备方排查]";
    /**
     * 获取小区三方对接配置项
     * @param $village_id
     * @return array|string[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCloudIntercomConfig($village_id){
        $where = [];
        $where[] = ['village_id','=',$village_id];
        $where[] = ['type','=','dopu_cloudintercom'];
        $field = 'clientId,clientSecret,village_num as villageCode,status as isOpen,village_id';
        $house_village_third_config = new HouseVillageThirdConfig();
        // 获取小区三方对接配置项
        $data = $house_village_third_config->getCloudIntercomConfig($where,$field);
        if(empty($data)){
            $data = [
                'isOpen' => '0',
                'clientId' => '',
                'clientSecret' => '',
                'villageCode' => '',
                'village_id'=>$village_id,
            ];
        }
        // 人脸数据接收接口
        $data['receiveUserInfo'] = cfg('site_url') . '/v20/public/index.php/community/village_api.CloudIntercom/receiveUserInfo?village_id='.$village_id;
        // 设备信息接收接口
        $data['receiveDeviceInfo'] = cfg('site_url') . '/v20/public/index.php/community/village_api.CloudIntercom/receiveDeviceInfo?village_id='.$village_id;
        // 设备心跳接收接口
        $data['receiveDeviceHeartbeat'] = cfg('site_url') . '/v20/public/index.php/community/village_api.CloudIntercom/receiveDeviceHeartbeat?village_id='.$village_id;
        // 权限信息反馈接口
        $data['receiveDeviceAuth'] = cfg('site_url') . '/v20/public/index.php/community/village_api.CloudIntercom/receiveDeviceAuth?village_id='.$village_id;
        // 流水数据接收接口
        $data['receiveFlowData'] = cfg('site_url') . '/v20/public/index.php/community/village_api.CloudIntercom/receiveFlowData?village_id='.$village_id;

        $huizhisq = false;
        if ($this->hasCloudIntercom()){
            $huizhisq = true;
        }
        $data['huizhisq'] = $huizhisq;
        return $data;
    }

    /**
     * 存储小区三方对接配置信息
     * @param $village_id
     * @param $param
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveCloudIntercomConfig($village_id,$param){
        $house_village_third_config = new HouseVillageThirdConfig();
        $where = [];
        $where[] = ['village_id','=',$village_id];
        $where[] = ['type','=','dopu_cloudintercom'];
        $param['last_time'] = time();
        // 获取配置信息
        $res = $house_village_third_config->getCloudIntercomConfig($where,'third_id');
        // 保存小区三方对接配置项
        if(!empty($res)){
            // 更新
            $data = $house_village_third_config->saveCloudIntercomConfig($param,'save',$where);
        }else{
            $param['village_id'] = $village_id;
            $param['type'] = 'dopu_cloudintercom';
            $param['add_time'] = time();
            // 保存
            $data = $house_village_third_config->saveCloudIntercomConfig($param);
        }
        return $data;
    }

    /**
     * 对讲平台推送信息数据
     * @param $village_id
     * @param $data
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function receiveUserInfo($village_id,$data){
        $db_house_village_third_user_info = new HouseVillageThirdUserInfo();

        if(empty($data)){
            return [
                'code' => 1,
                'message' => '推送数据为空'
            ];
        }
        $adddata = [];
        if(!isset($data['RYID'])) { // 批量
            foreach ($data as $val) {
                $adddata = $this->receiveUserInfoDeal($adddata, $village_id, $val);
            }
        }else{ // 单个
            $adddata = $this->receiveUserInfoDeal($adddata,$village_id,$data);
        }
        $adddata = array_values($adddata);
        // 批量插入数据
        $res = $db_house_village_third_user_info->addDataAll($adddata);
        return [
            'code' => 0,
            'message' => '处理完成',
            'data' => $res
        ];
    }

    /**
     * 对讲平台推送人脸信息数据处理
     * @param array $adddata
     * @param $village_id
     * @param $val
     * @return array|mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function receiveUserInfoDeal($adddata = array(),$village_id,$val){
        $db_house_village_third_user_info = new HouseVillageThirdUserInfo();

        $where = [];
        $whereOr = [];
        $where[] = ['village_id','=', $village_id];
        $where[] = ['third_type','=', 'dopu_cloudintercom'];
        $whereOr[] = ['village_id','=', $village_id];
        $whereOr[] = ['third_type','=', 'dopu_cloudintercom'];
        // 新增去重
        if(isset($adddata[$val['RYID']])){
            return $adddata;
        }
        // 判断推送人员是否已存在
        $where[] = ['third_ryid','=',$val['RYID']];
        $whereOr[] = ['third_zjhm','=',$val['ZJHM']];
        $whereOr[] = ['third_xm','=',$val['XM']];
        $whereOr[] = ['third_jzd_dzxz','=',$val['JZDZ']];
        $third_user_info = $db_house_village_third_user_info->getOne($where,'third_user_id, img_url',$whereOr);

        // 截取地址信息
        $address_arr = explode('号', $val['JZDZ']);
        if (count($address_arr)>1 && $address_arr[count($address_arr)-2]) {
            $address = $address_arr[count($address_arr)-2];
        } else {
            $address = '';
        }
        // 照片base64转图片
        if ($val['ZP']) {
            $photo = str_replace('data:image/jpeg;base64,','',$val['ZP']);
            $img_url = $this->base64_to_img($photo,'houseThird',$val['RYID']);
            $img_url = replace_file_domain($img_url);
            fdump_api(['$img_url' => $img_url],'dopu/zp',1);
        } else {
            $img_url = '';
        }
        // 转存数据
        $data = [
            'village_id' => $village_id,
            'source' => '02',
            'photo' => $val['ZP'],
            'phone' => $val['SJHM'],
            'from' => 1,
            'third_type' => 'dopu_cloudintercom',
            'type' => '01',
            'third_ryid' => $val['RYID'],
            'third_xm' => $val['XM'],
            'third_xbdm' => isset($val['XBDM']) ? (($val['XBDM'] == 1 || $val['XBDM'] == '女') ? 1 : 2) : 0, // 数据库存储 1是女 2是男
            'third_zjhm' => $val['ZJHM'],
            'third_jzd_dzxz' => $val['JZDZ'],
            'address' => $address,
            'img_url' => $img_url,
            'status' => 0,
        ];
        if ($third_user_info) {
            $data['last_time'] = time();
            // 更新
            $valApi = $val;
            unset($valApi['ZP']);
            $dataApi = $data;
            unset($dataApi['photo']);
            fdump_api(['$village_id' => $village_id,'$val' => $valApi,'$data' => $dataApi,'$third_user_info' => $third_user_info],'dopu/receiveUserInfoDealLog',1);
            $db_house_village_third_user_info->saveData(['third_user_id' => $third_user_info['third_user_id']],$data);
            return $adddata;
        } else {
            $data['add_time'] = time();
            // 批量新增
            $adddata[$val['RYID']] = $data;
            return $adddata;
        }
    }

    /**
     * base64转图片
     * @param $base_img
     * @param string $path
     * @param $fileName
     * @return false|string
     * @throws \think\Exception
     */
    private function base64_to_img($base_img, $path='face_log',$fileName) {
        $rand_num = date('Ymd');// 换成日期存储
        $up_dir = "/upload/{$path}/".$rand_num.'/';
        if (!is_dir($_SERVER['DOCUMENT_ROOT'].$up_dir)) {
            mkdir($_SERVER['DOCUMENT_ROOT'].$up_dir, 0777, true);
        }
        if ($base_img) {
            $type = 'jpg';
            $new_file = $up_dir.$fileName.'.'.$type;
            if (file_put_contents($_SERVER['DOCUMENT_ROOT'] . $new_file, base64_decode($base_img))) {
                //判断是否需要上传至云存储
                // 压缩图片不超过 1920*1080
                try {
                    $sImageService = new ImageService();
                    $thumbImageUrl = $sImageService->thumbImage(cfg('config_site_url') . $new_file, 1920, 1080, '');
                } catch (\Exception $e) {
                    $thumbImageUrl = '';
                    fdump_api(['执行错误' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'new_file' => $new_file], 'thumb/errThumbImageLog', 1);
                }
                if (!$thumbImageUrl) {
                    $thumbImg = invoke_cms_model('Image/thumb', [cfg('config_site_url') . $new_file, 1920, 1080, '']);
                    $thumbImageUrl = isset($thumbImg['retval']) ? trim($thumbImg['retval']) : '';
                }
                fdump_api(['thumbImageUrl' => $thumbImageUrl, 'url' => cfg('config_site_url') . $new_file], 'dupu/base64_to_img', 1);

                $file_handle = new FileHandle();
                if($file_handle->check_open_oss()) {
                    invoke_cms_model('Image/oss_upload_image', [$thumbImageUrl]);
                }
                $img_path = explode(cfg('config_site_url'), $thumbImageUrl)[1];
                return $img_path;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 获取小区三方推送用户列表
     * @param $where
     * @param bool $field
     * @param $page
     * @param $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getFaceDataList($where,$field = true,$page,$limit){
        $third_user_info = new HouseVillageThirdUserInfo();
        $data = $third_user_info->getThirdUserList($where,$field,$page,$limit);
        foreach ($data['list'] as $key => &$value){
            $value['third_xbdm'] = $value['third_xbdm'] == 1 ? '女' : '男';
            $value['check_status_text'] = $value['check_status'] ? ($value['check_status'] == 1 ? '审核通过' : '审核拒绝') : '未审核';
            $value['device_status_text'] = $value['device_status'] ? ($value['device_status'] == 1 ? '下发成功' : '下发失败') : '未下发';
            if($value['device_status'] == 3){
                $value['device_status_text'] = '下发中';
            }
        }
        $data['total_limit'] = $limit;
        return $data;
    }

    /**
     * 获取第三方用户详情 并进行用户匹配 地址
     * @param $where
     * @param $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getFaceDataInfo($where,$field){
        $third_user_info = new HouseVillageThirdUserInfo();
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $db_house_village_user_bind = new HouseVillageUserBind();
        $thirdUserInfo = $third_user_info->getOne($where,$field);
        // 匹配当前小区业主信息 默认不匹配
        $thirdUserInfo['is_matching'] = false;
        $thirdUserInfo['matching_uid'] = 0;
        $thirdUserInfo['addressInfo'] = [];

        // 首先查询下房间
        $vacancy_info = $this->dealThirdAddress($thirdUserInfo);
        fdump_api(['$vacancy_info' => $vacancy_info],'dopu/cloud',1);

        if ($thirdUserInfo['from'] == 1) {
            // 身份证首6位和身份证尾3位进行匹配
            $third_zjhm = $thirdUserInfo['third_zjhm'];
            $first_zjhm = mb_substr($third_zjhm,0,6);
            $tail_zjhm = mb_substr($third_zjhm,-3,3);
            // 姓名
            $surname = $thirdUserInfo['third_xm'];
            $where_bind1 = [];
            $where_bind1[] = ['village_id','=',$thirdUserInfo['village_id']];
            $where_bind1[] = ['status','in', [0,1,2]];
            // 是否已存在业主
            $thirdUserInfo['is_owner'] = false;
            $thirdUserInfo['type'] = 0;
            // 是否锁死住房地址
            $thirdUserInfo['is_lock_address'] = false;
            $thirdUserInfo['vacancy_id'] = 0;
            // 如果存在房间  按照 房间
            if (!empty($vacancy_info) && !empty($vacancy_info['pigcms_id']) && $vacancy_info['pigcms_id']) {
                $where_bind1[] = ['vacancy_id','=',$vacancy_info['pigcms_id']];

                // 判断住址下是否有业主
                $where_user_bind = $where_bind1;
                $where_user_bind[] = ['type','in',[0,3]];
                $res = $db_house_village_user_bind->getOne($where_user_bind,'pigcms_id');
                if($res && !$res->isEmpty()){
                    $thirdUserInfo['is_owner'] = true;
                    $thirdUserInfo['type'] = 1;
                }
                // 设置默认地址
                $thirdUserInfo['addressInfo'] = [
                    'room' => $vacancy_info['room'],
                    'single_name' => $vacancy_info['single_name'],
                    'floor_name' => $vacancy_info['floor_name'],
                    'layer_name' => $vacancy_info['layer_name'],
                ];
                // 是否锁死住房地址
                $thirdUserInfo['is_lock_address'] = true;
            }
            $where_bind1[] = ['name','=',$surname];
            /*$where_bind2 = [];
            // 身份证首6位和身份证尾3位进行匹配  营山公安推送数据出现脱敏后的身份证号一样的情况
            if (strpos($third_zjhm, '*') !== false) {
                $where_bind2[] = ['id_card','like',$first_zjhm.'%'];
                $where_bind2[] = ['id_card','like','%'.$tail_zjhm];
            } else {
                $where_bind2[] = ['id_card','=',$third_zjhm];
            }
            $where_bind2[] = ['village_id','=',$thirdUserInfo['village_id']];
            $where_bind2[] = ['status','in', [0,1,2]];*/


            $info = $db_house_village_user_bind->getOne($where_bind1,'pigcms_id,vacancy_id,relatives_type,type,memo');
            if(empty($info) && !empty($thirdUserInfo['phone'])){
                $where_bind3 = [];
                $where_bind3[] = ['village_id','=',$thirdUserInfo['village_id']];
                $where_bind3[] = ['status','in', [0,1,2]];
                $where_bind3[] = ['phone','=', $thirdUserInfo['phone']];
                $where_bind3[] = ['name','=', $surname];
                $info = $db_house_village_user_bind->getOneWhereOr([$where_bind1,$where_bind3],'pigcms_id,vacancy_id,relatives_type,type,memo');
            }
            $thirdUserInfo['relatives_type'] = 1;
            if(!empty($info)){
                // 匹配到用户
                $thirdUserInfo['is_matching'] = true;
                // 是否锁死住房地址
                $thirdUserInfo['is_lock_address'] = false;

                $where_address = [];
                $where_address[] = ['a.pigcms_id','=',$info['vacancy_id']];
                // 获取当前用户地址
                $thirdUserInfo['addressInfo'] = $db_house_village_user_vacancy->getInfo($where_address,'','a.room,b.single_name,c.floor_name,d.layer_name');
                $thirdUserInfo['relatives_type'] = $info['relatives_type'];
                $thirdUserInfo['type'] = $info['type'];
                $thirdUserInfo['memo'] = $info['memo'];
                $thirdUserInfo['matching_uid'] = $info['pigcms_id'];
                // 工作人员不锁定住房地址
                if($info['type'] > 3 && empty($thirdUserInfo['addressInfo'])){
                    $thirdUserInfo['is_lock_address'] = false;
                }
            }else{
                // 是否锁死住房地址
                $thirdUserInfo['is_lock_address'] = false;
            }
            $thirdUserInfo['relativesTypeList'] = [
                ['name' => '配偶','id' => 1],
                ['name' => '父母','id' => 2],
                ['name' => '子女','id' => 3],
                ['name' => '亲朋好友','id' => 4]
            ];
        }
        return $thirdUserInfo;
    }

    /**
     * 人脸信息审核
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function editThirdUserInfo($param){
        $third_user_info = new HouseVillageThirdUserInfo();
        $db_house_village_user_bind = new HouseVillageUserBind();
        $db_user = new User();
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $where = [];
        $where[] = ['third_user_id','=',$param['third_user_id']];
        // 人脸信息详情
        $thirdUserInfo = $third_user_info->getOne($where);
        if($param['check_status'] == 2){
            // 拒绝
            $save = [
                'check_status' => 2,
                'status' => 3,
                'check_reason' => $param['check_reason'],
                'last_time' => time()
            ];
        }else{
            // 审核通过
            $save = [
                'check_status' => 1,
                'last_time' => time()
            ];
            $single_id = $param['singleName'];
            $floor_id = $param['floorName'];
            $layer_id = $param['layerName'];
            $room_id = $param['roomName'];

            $vacancy = $this->dealThirdAddress($thirdUserInfo);

            if(!empty($vacancy) || $thirdUserInfo['check_status'] == 1){
                // 使用名称匹配获取小区楼栋单元楼层房间ID
                $roomField = 'a.pigcms_id,a.floor_id,a.single_id,a.layer_id';
                // 通过小区楼栋单元楼层房间名称获取参数
                $vacancy_info = $this->roomDeal($param['singleName'],$param['floorName'],$param['layerName'],$param['roomName'],$param['village_id'],$roomField);
                if (empty($vacancy_info)) {
                    $vacancyById = $this->roomByIds($param['singleName'],$param['floorName'],$param['layerName'],$param['roomName'],$param['village_id'],$roomField);
                    $vacancy_info = $vacancyById;
                }
                fdump_api([$param['singleName'],$param['floorName'],$param['layerName'],$param['roomName'],$param['village_id'],$roomField,'$vacancyById' => $vacancyById,'$vacancy_info' => $vacancy_info],'dopu/roomDeal',1);
                if(empty($vacancy_info)){
                    // user_bind id和用户房间不匹配
                    return [
                        'code' => 1,
                        'msg' => '房间参数错误'
                    ];
                }
                $single_id = $vacancy_info['single_id'];
                $floor_id = $vacancy_info['floor_id'];
                $layer_id = $vacancy_info['layer_id'];
                $room_id = $vacancy_info['pigcms_id'];
            }
            if(empty($room_id)){
                throw new Exception('房间参数错误');
            }
            // 房间信息
            $where_address = [];
            $where_address[] = ['a.pigcms_id','=',$room_id];
            // 获取当前房间信息
            $userRoomInfo = $db_house_village_user_vacancy->getInfo($where_address,'','a.pigcms_id,a.floor_id,a.single_id,a.housesize,a.layer,a.room,a.layer_id,a.usernum');
            if(empty($userRoomInfo)){
                throw new Exception('未查询到相应房间');
            }
            // 是否已有匹配
            if($param['isMatching']){
                $set_bind = [];
                // 已有匹配，则不进行添加用户操作
                $save['status'] = 1;
                $bind_id = $param['matchingUid'];
                // 工作人员
                if($param['type'] != 4){
                    $whereUserBind = [];
                    $whereUserBind[] = ['pigcms_id','=',$bind_id];
//                    $whereUserBind[] = ['vacancy_id','=',$room_id];
                    $info = $db_house_village_user_bind->getOne($whereUserBind,'vacancy_id,uid,authentication_field,phone,name');
                    if(!$info || $info->isEmpty()){
                        // user_bind id和用户房间不匹配
                        return [
                            'code' => 1,
                            'msg' => '业主和房间不匹配'
                        ];
                    }
                    if (isset($info['vacancy_id'])&&$info['vacancy_id']!=$room_id) {
                        $set_bind['floor_id'] = $floor_id;
                        $set_bind['single_id'] = $single_id;
                        $set_bind['layer_id'] = $layer_id;
                        $set_bind['vacancy_id'] = $room_id;
                    }
                    if ($info['type']!=0 || $info['type']!=3) {
                        // 是否存在业主
                        $whereUserParent = [];
                        $whereUserParent[] = ['vacancy_id','=',$room_id];
                        $whereUserParent[] = ['type', 'in', [0,3]];
                        $whereUserParent[] = ['status', '=', 1];
                        $room_pigcms_info = $db_house_village_user_bind->getOne($whereUserParent,'pigcms_id');
                        if ($room_pigcms_info && isset($room_pigcms_info['pigcms_id'])) {
                            $set_bind['parent_id'] = $room_pigcms_info['pigcms_id'];
                        }
                    }
                }else{
                    $set_bind['floor_id'] = $floor_id;
                    $set_bind['single_id'] = $single_id;
                    $set_bind['layer_id'] = $layer_id;
                    $set_bind['vacancy_id'] = $room_id;
                    // 是否存在业主
                    $whereUserParent = [];
                    $whereUserParent[] = ['vacancy_id','=',$room_id];
                    $whereUserParent[] = ['type', 'in', [0,3]];
                    $whereUserParent[] = ['status', '=', 1];
                    $room_pigcms_info = $db_house_village_user_bind->getOne($whereUserParent,'pigcms_id');
                    if ($room_pigcms_info && isset($room_pigcms_info['pigcms_id'])) {
                        $set_bind['parent_id'] = $room_pigcms_info['pigcms_id'];
                    }
                }

                // 同步记录下信息
                if ($thirdUserInfo['third_xbdm']==1) {
                    $sex='女';
                }elseif ($thirdUserInfo['third_xbdm']==2) {
                    $sex='男';
                }
                if ($info && $info['authentication_field'] && $sex) {
                    $authentication_field = unserialize($info['authentication_field']);
                    if ($authentication_field['sex']) {
                        // 性别同步更新下
                        $authentication_field['sex']['value'] = $sex;
                        $set_bind['authentication_field'] = serialize($authentication_field);
                    }
                }
                if ($thirdUserInfo['third_ryid']) {
                    $set_bind['third_id'] = $thirdUserInfo['third_ryid'];
                }
                if ($thirdUserInfo['third_xqryid']) {
                    $set_bind['third_xqryid'] = $thirdUserInfo['third_xqryid'];
                }
                $set_bind['memo'] = $param['memo'];
                if ($info && isset($info['uid']) && !$info['uid'] && isset($info['phone']) && $info['phone']) {
                    $whereUser = [];
                    $whereUser[] = ['phone','=', $info['phone']];
                    $userInfo = $db_user->getOne($whereUser,'uid');
                    if ($userInfo && isset($userInfo['uid'])) {
                        $uid = $userInfo['uid'];
                    } else {
                        // 添加手机号为主的平台用户
                        $add_user = [
                            'phone' => $info['phone'],
                            'nickname' => $info['name']?$info['name']:substr($info['phone'],0,3).'****'.substr($info['phone'],7,4),
                            'sex' => ($thirdUserInfo['third_xbdm'] == 1) ? 2 : 1,
                            'avatar' => $thirdUserInfo['img_url'],
                            'add_time' => time(),
                            'source' 	=> 'houseautoreg_duopu',
                        ];
                        $server_common_user = new CommonUserService();
                        $reg_result = $server_common_user->autoReg($add_user,false,true);
                        if (isset($reg_result['uid']) && intval($reg_result['uid']) > 0) {
                            $uid = $reg_result['uid'];
                        } else {
                            $uid = 0;
                        }
                    }
                    if (isset($uid)) {
                        $set_bind['uid'] = $uid;
                    }
                }
                if ($set_bind) {
                    // 更新
                    $db_house_village_user_bind->saveOne([['pigcms_id','=',$bind_id]],$set_bind);
                }
            }else{
                $user_name = str_replace('*', '', $thirdUserInfo['third_xm']);
                if($thirdUserInfo['phone']){
                    // 如果 手机号 重复在不同人员处出现  后面的人员手机号置空
                    $where_bind3 = [];
                    $where_bind3[] = ['village_id','=',$thirdUserInfo['village_id']];
                    $where_bind3[] = ['status', 'in', [0,1,2]];
                    $where_bind3[] = ['phone', '=', $thirdUserInfo['phone']];
                    $info = $db_house_village_user_bind->getOne($where_bind3,'pigcms_id,name,vacancy_id');
                    if ($info && isset($info['name']) && $info['name']!=$user_name) {
                        $thirdUserInfo['phone'] = '';
                    }
                }
                // 没有匹配信息
                // 查询去重
                $whereRepeat = [];
                $whereRepeat[] = ['vacancy_id','=',$room_id];
                $whereRepeat[] = ['status','in',[1,2]];
                $whereRepeat[] = ['name','=',$user_name];
                $whereRepeat[] = ['id_card','=',$thirdUserInfo['third_zjhm']];
                if($thirdUserInfo['phone']){
                    $whereRepeat[] = ['phone','=',$thirdUserInfo['phone']];
                }
                $other_pigcms_info = $db_house_village_user_bind->getOne($whereRepeat,'pigcms_id');

                // 先添加用户，然后添加user_bind
                $save['status'] = 2;
                if ($other_pigcms_info && isset($other_pigcms_info['pigcms_id']) && $other_pigcms_info['pigcms_id']) {
                    $save['status'] = 1;
                    $uid = 0;
                } elseif ($thirdUserInfo['phone']) {
                    $whereUser = [];
                    $whereUser[] = ['phone','=', $thirdUserInfo['phone']];
                    $userInfo = $db_user->getOne($whereUser,'uid');
                    if ($userInfo && isset($userInfo['uid'])) {
                        $uid = $userInfo['uid'];
                    } else {
                        // 添加手机号为主的平台用户
                        $add_user = [
                            'phone' => $thirdUserInfo['phone'],
                            'nickname' => $user_name,
                            'sex' => ($thirdUserInfo['third_xbdm'] == 1) ? 2 : 1,
                            'avatar' => $thirdUserInfo['img_url'],
                            'add_time' => time(),
                            'source' 	=> 'houseautoreg_duopu',
                        ];
                        $server_common_user = new CommonUserService();
                        $reg_result = $server_common_user->autoReg($add_user,false,true);
                        if (isset($reg_result['uid']) && intval($reg_result['uid']) > 0) {
                            $uid = $reg_result['uid'];
                        } else {
                            $uid = 0;
                        }
                    }
                } else {
                    $add_user = [
                        'nickname' => $user_name,
                        'sex' => ($thirdUserInfo['third_xbdm'] == 1) ? 2 : 1,
                        'avatar' => $thirdUserInfo['img_url'],
                        'add_time' => time(),
                        'source' 	=> 'houseautoreg_duopu',
                    ];
                    $server_common_user = new CommonUserService();
                    $reg_result = $server_common_user->autoReg($add_user,false,true);
                    if (isset($reg_result['uid']) && intval($reg_result['uid']) > 0) {
                        $uid = $reg_result['uid'];
                    } else {
                        $uid = 0;
                    }
                }
                // 然后添加user_bind
                $add_user_data = [];
                $add_user_data['status'] = 1;

                $vacancy_id = $room_id;
                $add_user_data['village_id'] = $param['village_id'];
                $add_user_data['floor_id'] = $floor_id;
                $add_user_data['single_id'] = $single_id;
                $add_user_data['layer_id'] = $layer_id;
                $add_user_data['vacancy_id'] = $vacancy_id;
                $add_user_data['name'] = $user_name;
                $add_user_data['address'] = $thirdUserInfo['address'];
                $add_user_data['housesize'] = $userRoomInfo['housesize'];
                $add_user_data['layer_num'] = $userRoomInfo['layer'];
                $add_user_data['room_addrss'] = $userRoomInfo['room'];
                $add_user_data['usernum'] = rand(0,99999) . '-' . time().'-t1';
                $add_user_data['id_card'] = $thirdUserInfo['third_zjhm'];
                // 是否存在业主
                $whereUserBind = [];
                $whereUserBind[] = ['vacancy_id','=',$vacancy_id];
                $whereUserBind[] = ['type','in',[0,3]];
                $whereUserBind[] = ['status','=',1];
                $room_pigcms_info = $db_house_village_user_bind->getOne($whereUserBind,'pigcms_id');
                if ($room_pigcms_info && isset($room_pigcms_info['pigcms_id']) && $room_pigcms_info['pigcms_id']) {
                    if($param['type'] == 0){
                        return [
                            'code' => 1,
                            'msg' => '业主已存在，不能添加业主'
                        ];
                    }
                    $add_user_data['parent_id'] = $room_pigcms_info['pigcms_id'];
                }

                $add_user_data['type'] = $param['type'];
                $add_user_data['relatives_type'] = $param['relatives_type'];

                $add_user_data['add_time'] = time();
                if ($thirdUserInfo['third_ryid']) {
                    $add_user_data['third_id'] = $thirdUserInfo['third_ryid'];
                }
                if ($thirdUserInfo['third_xqryid']) {
                    $add_user_data['third_xqryid'] = $thirdUserInfo['third_xqryid'];
                }
                $add_user_data['uid'] = $uid;
                $add_user_data['memo'] = $param['memo'];
                if($thirdUserInfo['phone']){
                    $add_user_data['phone'] = $thirdUserInfo['phone'];
                }
                $chinapopinOpen  = (new ConfigCustomizationService())->getChinapopinOpen();
                if($chinapopinOpen){
                    $add_user_data['third_type'] = $thirdUserInfo['third_type'];
                }
                if ($other_pigcms_info && isset($other_pigcms_info['pigcms_id']) && $other_pigcms_info['pigcms_id']) {
                    $save['status'] = 1;
                    unset($add_user_data['add_time']);
                    unset($add_user_data['usernum']);
                    unset($add_user_data['uid']);
                    $whereRepeat = [];
                    $whereRepeat[] = ['pigcms_id','=',$other_pigcms_info['pigcms_id']];
                    $db_house_village_user_bind->saveOne($whereRepeat,$add_user_data);
                } else {
                    $db_house_village_user_bind->addOne($add_user_data);
                }
            }
        }
        // 发送审核上传
        $audit = $this->auditFeedback($param['village_id'],$param['third_user_id'],$param['check_status'],$param['check_reason']);
        $response = json_decode($audit[1],true);
        if($audit[0] == 200 && empty($response['code'])){
            // 更新第三方人脸数据
            $third_user_info->saveData($where,$save);
            return [
                'code' => 0,
                'msg' => '操作成功',
                'data' => $response
            ];
        }else{
            if($audit[0] == 404){
                $response['code'] = 1003;
                $response['msg'] = '第三方设备提示：请求404';
            }
            return [
                'code' => $response['code'],
                'msg' => '第三方设备提示：'.$response['msg']. $this->tip,
                'data' => $response
            ];
        }
    }

    /**
     * 人员审核上传
     * @param $village_id
     * @param $bind_id
     * @param $status
     * @param $reason
     * @param $ywlsh
     * @param $errorcode
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function auditFeedback($village_id,$third_user_id,$check_status,$check_reason = ''){
        $third_user_info = new HouseVillageThirdUserInfo();
        $where = [];
        $where[] = ['third_user_id','=',$third_user_id];
        // 人脸信息详情
        $thirdUserInfo = $third_user_info->getOne($where);
        $oauth_arr = [];
        $oauth_arr['RYID'] = $thirdUserInfo['third_ryid'];
        $oauth_arr['result'] = ($check_status == 2) ? -1 : 1;
        $oauth_arr['msg'] = $check_reason;
        // 发送人员出入审核结果反馈
        $dopu = new dopuapi($village_id);
        return $dopu->auditFeedback($oauth_arr);
    }

    /**
     * 通过小区楼栋单元楼层房间名称获取参数
     * @param $single_name
     * @param $floor_name
     * @param $layer_name
     * @param $room
     * @param $village_id
     * @param $field
     * @return array
     */
    public function roomDeal($single_name,$floor_name,$layer_name,$room,$village_id,$field){
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        if (intval($layer_name)<10) {
            $layer_name_num = intval($layer_name) * 100;
            $room_num_other = '0'.$room;
            $room_other = '00'.$room;
        } elseif (intval($layer_name)<100) {
            $layer_name_num = intval($layer_name) * 10;
            $room_num_other = '0'.$room;
            $room_other = '00'.$room;
        } else {
            $layer_name_num = intval($layer_name);
            $room_num_other = '0'.$room;
            $room_other = '00'.$room;
        }
        $room_num = $layer_name_num + intval($room);
        // 查询下房间
        $roomInt = intval($room);
        $where_room = "a.is_del=0 AND a.village_id=".$village_id." AND (a.room='{$roomInt}' OR a.room='{$room}' OR a.room='{$room_num}' OR a.room='{$room_num_other}' OR a.room='{$room_other}') AND (b.single_name='{$single_name}' OR b.single_name='{$single_name}栋' OR b.single_name='{$single_name}楼' OR b.single_name='{$single_name}号楼') ";
        $where_room .= " AND (c.floor_name='{$floor_name}' OR c.floor_name='{$floor_name}单元') AND (d.layer_name='{$layer_name}' OR d.layer_name='0{$layer_name}' OR d.layer_name='{$layer_name}层')";
        $roomInfo =  $db_house_village_user_vacancy->getInfo([],$where_room,$field);
        fdump_api(['$where_room' => $where_room,'$roomInfo' => $roomInfo, '$db_house_village_user_vacancy' => $db_house_village_user_vacancy->getLastSql()],'dopu/roomDeal',1);
        return $roomInfo;
    }

    public function roomByIds($single_id,$floor_id,$layer_id,$room_id,$village_id,$field=true) {
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $where_room = "a.is_del=0 AND a.village_id=".$village_id." AND a.single_id={$single_id} AND a.floor_id={$floor_id} AND a.layer_id={$layer_id} AND a.pigcms_id={$room_id}";
        return $db_house_village_user_vacancy->getInfo([],$where_room,$field);
    }

    /**
     * 保存平台下发设备信息
     * @param $village_id
     * @param $param
     * @return array
     */
    public function receiveDeviceInfo($village_id,$param){
        if(empty($param) || empty($village_id)){
            return [
                'code' => 1,
                'message' => '下发设备为空'
            ];
        }

        if(!isset($param['code'])){ // 批量
            foreach ($param as $kay => $value){
                $this->receiveDeviceInfoDeal($value,$village_id);
            }
        }else{
            $this->receiveDeviceInfoDeal($param,$village_id);
        }

        return [
            'code' => 0,
            'message' => '数据接收成功'
        ];
    }

    /**
     * 下发设备信息处理
     * @param $value
     * @param $village_id
     */
    private function receiveDeviceInfoDeal($value,$village_id){
        $house_face_device = new HouseFaceDevice();
        $where = [];
        $where[] = ['device_sn','=',$value['code']];
        $where[] = ['village_id','=',$village_id];
        // 查询是否已存在设备信息
        $res = $house_face_device->getOne($where,'device_id');
        if(!$res || $res->isEmpty()){
            $data = [
                'device_sn' => $value['code'],
                'device_name' => $value['code'],
                'device_type' => 29,
                'village_id' => $village_id,
                'add_time' => time(),
            ];
            $house_face_device->addData($data);
        }
    }

    /**
     * 接收平台下发设备心跳
     * @param $village_id
     * @param $param
     * @return array
     */
    public function receiveDeviceHeartbeat($village_id,$param){
        // 获取小区所有设备
        $house_face_device = new HouseFaceDevice();

        if(!isset($param['code'])){ // 批量
            // 在线设备
            $code_line = array_column($param,'code');
        }else{ // 单一
            $code_line = [$param['code']];
        }


        $where_save = [];
        $where_save[] = ['device_sn','in',$code_line];
        $where_save[] = ['is_del','=',0];
        $where_save[] = ['village_id','=',$village_id];
        // 更新设备回馈时间
        $house_face_device->saveData($where_save,['device_status' => 1,'notify_time' => time(),'last_time' => time()]);
        // 设备下线
        $where_offline = [];
        $where_offline[] = ['device_sn','not in',$code_line];
        $where_offline[] = ['notify_time','<',time()-1800];
        $where_offline[] = ['is_del','=',0];
        $where_offline[] = ['device_type','=',29];
        $where_offline[] = ['village_id','=',$village_id];
        $house_face_device->saveData($where_offline,['device_status' => 2,'last_time' => time()]);
        return ['code' => 0,'message' => '数据接收成功'];
    }

    /**
     * 获取设备列表
     * @param $where
     * @param $page
     * @param $limit
     * @param $order
     * @param $field
     * @return array
     */
    public function getDeviceDataList($where,$page,$limit,$order,$field){
        $db_house_face_device = new HouseFaceDevice();
        $village_public_area = new HouseVillagePublicArea();
        $house_village_floor = new HouseVillageFloor();
        $house_village_single = new HouseVillageSingle();

        // 设备总数
        $count = $db_house_face_device->getCounts($where);
        // 设备列表
        $list = $db_house_face_device->getLists($where,$field,$order,$page,$limit);
        if(!$list || $list->isEmpty()){
            return [
                'code' => 1,
                'message' => '暂无数据'
            ];
        }
        $list = $list->toArray();
        foreach ($list as &$value){
            // 公共区域
            if (isset($value['public_area_id']) && $value['public_area_id']>0) {
                $public_area_name = $village_public_area->getOne([['public_area_id','=',$value['public_area_id']]],'public_area_name');
                $value['device_position'] = $value['village_name'].'-'.$public_area_name['public_area_name'];
            } else if ( $value['floor_id'] == -1) {
                $value['device_position'] = $value['village_name'].'-大门';
            }else{
                // 单元楼层房间
                $aFloor	=	$house_village_floor->getOne([['floor_id','=',$value['floor_id']]],'floor_name,floor_layer,single_id');
                $single_name	=	$house_village_single->getOne([['id','=',$aFloor['single_id']]],'single_name');
                $floor_name	=	$aFloor['floor_name'] ? '-'.$aFloor['floor_name'] : '';
                $floor_layer	=	$single_name['single_name'] ? $single_name['single_name'] : $aFloor['floor_layer'];
                $value['device_position'] = $floor_layer.$floor_name;
                $value['single_id'] = $aFloor['single_id'];
            }
            // 标识
            $value['device_direction_text'] = $value['device_direction'] ? '出' : '进';
            $value['add_time'] = date("Y-m-d H:i:s",$value['add_time']);
            // 状态
            $value['device_status'] = ($value['device_status'] == 1) ? '在线' : '离线';
            $value['thirdDeviceTypeStr'] = $value['thirdDeviceTypeStr'] ? $value['thirdDeviceTypeStr'] : 1;
        }
        return [
            'list' => $list,
            'count' => $count
        ];
    }

    /**
     * 编辑设备信息
     * @param $param
     * @return array
     */
    public function editDeviceInfo($param){
        $db_house_face_device = new HouseFaceDevice();

        if(empty($param)){
            return [];
        }
        $where = [];
        $where[] = ['device_sn','=',$param['deviceSn']];
        unset($param['deviceSn']);
        $param['last_time'] = time();
        $singleArr = explode('-',$param['single_id']);
        if($singleArr[0] == 1){
            $param['public_area_id'] = $singleArr[1];
            $param['floor_id'] = 0;
        }
        unset($param['single_id']);
        $db_house_face_device->saveData($where,$param);
    }

    /**
     * 下发人脸到设备
     * @param $village_id
     * @param $deviceSn
     * @param $thirdUser
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sendThirdUserToDevice($village_id,$deviceSn,$thirdUser,$operation='add'){
        $db_process_plan=new ProcessPlan();
        $face_device = new HouseFaceDevice();

        if(empty($thirdUser)){
            return ['code' => 1001,'msg' => '请选择下发人员'];
        }
        // 获取所有的人行道设备
        $where_device = [];
        $where_device[] = ['village_id','=',$village_id];
        $where_device[] = ['thirdDeviceTypeStr','=',1];
        $where_device[] = ['device_type','=',29];
        $where_device[] = ['is_del','=',0];
        // 获取非机动车通道列表
        $deviceList = $face_device->getList($where_device,'device_sn,device_id',1);
        if($deviceList && !$deviceList->isEmpty()){
            $deviceSn = $deviceList->toArray();
        }else{
            return ['code' => 1001,'msg' => '没有相关设备'];
        }
        $deviceSnArr = array_column($deviceSn,'device_sn');
        $data = [];
        if(count($deviceSnArr)*count($thirdUser) > 100){
            // 走计划任务
            foreach ($deviceSnArr as $k){
                $param = [
                    'village_id' => $village_id,
                    'deviceSn' => $k,
                    'thirdUser' => $thirdUser,
                    'operation' => $operation,
                ];
                $data_call = [];
                $data_call['param'] = serialize($param);
                $data_call['add_time'] = time();
                $data_call['plan_time'] = time();
                $data_call['space_time'] = 0;
                $data_call['error_count'] = 0;
                $data_call['file'] = 'auto_send_user_to_device_dopu';
                $data_call['time_type'] = 0;
                $data_call['sub_process_num'] = 0;
                $data_call['plan_desc'] = '朵普批量下发人脸信息至设备';
                $data_call['unique_id'] = 'dopu_face_device_'.$village_id.'_'.$k;
                $data[$k] = $db_process_plan->addOne($data_call);
            }
            return ['code' => 0, 'msg' => '操作成功,已加入计划任务自动下发至设备','data' => $data];
        }else{
            foreach ($deviceSnArr as $v){
                $data[$v][] = $this->sendThirdUserToDeviceDeal($village_id,$v,$thirdUser,$operation);
            }
            return ['code' => 0, 'msg' => '操作成功', 'data' => $data];
        }
    }

    /**
     * 下发人脸到设备 数据处理
     * @param $village_id
     * @param $deviceSn
     * @param $thirdUser
     * @return array
     * 
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sendThirdUserToDeviceDeal($village_id, $deviceSn, $thirdUser, $operation = 'add', $source = 'gonan', $otherData = [])
    {
        $third_user = new HouseVillageThirdUserInfo();
        $villageConfig = new HouseVillageThirdConfig();
        $face_user_bind_device = new FaceUserBindDevice();
        $house_face_device = new HouseFaceDevice();
        $user_bind = new HouseVillageUserBind();
        $db_house_face_img = new HouseFaceImg();
        $dopu = new dopuapi($village_id);
        $wordencryption=new wordencryption();

        $whereConfig = [];
        $whereConfig[] = ['village_id','=',$village_id];
        // 获取配置信息
        $thirdInfo = $villageConfig->getCloudIntercomConfig($whereConfig,'village_num');

        $whereDevice = [];
        $whereDevice[] = ['village_id','=',$village_id];
        if(is_array($deviceSn)){
            $whereDevice[] = ['device_sn','in',$deviceSn];
        }else{
            $whereDevice[] = ['device_sn','=',$deviceSn];
        }
        // 设备信息
        $deviceInfo = $house_face_device->getOne($whereDevice,'device_id,thirdDeviceTypeStr');
        if($deviceInfo && !$deviceInfo->isEmpty()){
            $deviceInfo = $deviceInfo->toArray();
        }else{
            return ['code' => 1001,'msg' => '设备参数错误'];
        }

        $logId = $otherData['log_id']??0;
//        if(!$logId){
//            //添加日志
//            $logId = (new HouseFaceSyncLog())->insertGetId([
//                'pigcms_id'=>implode(',',$thirdUser),
//                'face_id'=>0,
//                'device_id'=>$deviceInfo['device_id'],
//                'device_sn'=>$deviceSn,
//                'log_time'=>time(),
//                'status'=>0,
//                'device_info'=>json_encode($deviceInfo),
//            ]);
//        }
        
        $where = [];
        $list=[];
        $persontype=1;
        if($source == 'gonan'){
            $where[] = ['t.third_user_id','in',$thirdUser];
            $where[] = ['b.pigcms_id', '>', 0];
            $list = $third_user->getThirdUserLists($where,'t.third_user_id,t.third_ryid,t.third_xm,t.img_url,b.pigcms_id,b.uid,b.type,b.phone,b.name');
            if ($list && !$list->isEmpty()) {
                $list= $list->toArray();
            }
        }
        elseif ($source == 'house_village'){
            $where[] = ['pigcms_id','in',$thirdUser];
            $list = $user_bind->getList($where,'pigcms_id,uid,name,phone,type');
            if ($list && !$list->isEmpty()) {
                $list= $list->toArray();
            }
            $persontype=2;
        }

        if(!empty($list)){
            $msg = '操作成功';
            $code = 0;
            // 下发人员到设备
            list($msec, $sec) = explode(' ', microtime());
            $msectime = (int)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
            // 人脸默认有效期10年 转毫秒级
            $end_time = strtotime('+10 year')*1000;
            $nmv_card = new HouseVillageNmvCard();
            //fdump_api(['$deviceInfo' => $deviceInfo, '$list' => $list,'$village_id' => $village_id,'$deviceSn' => $deviceSn,'$thirdUser' => $thirdUser,],'duopu/sendThirdUserToDeviceDealLog');
            foreach ($list as $value){
                $RYID = $third_user_id = $img_url = $authid=$name = '';
                $whereSource = [];
                $whereSource[] = ['bind_id', '=', $value['pigcms_id']];
                $whereSource[] = ['device_type', '=', 29];
                $whereSource[] = ['personID', '<>', ''];
                $whereSource[] = ['device_id', '<>', $deviceInfo['device_id']];
                $infoSource = $face_user_bind_device->getOneOrder($whereSource, 'bind_id,device_id,personID', ['last_time' => 'desc', 'id' => 'desc']);
                if ($infoSource && isset($infoSource['personID'])) {
                    $authidSource = $infoSource['personID'];
                } else {
                    $authidSource = '';
                }
                if($source == 'gonan'){
                    $RYID          = $value['third_ryid'];
                    $third_user_id = $value['third_user_id'];
                    $img_url       = $value['img_url'];
                    if ($authidSource) {
                        $authid =  $authidSource;
                    } else {
                        $authid = '1' . (100000 + $deviceInfo['device_id']) . $third_user_id;
                    }
                    $name=isset($value['third_xm']) && $value['third_xm'] ? $value['third_xm']:$value['name'];
                    // 保存图片
                    invoke_cms_model('House_village_pass_bind_member/add_face', [$img_url, $value['uid']])['retval'];
                }
                elseif ($source == 'house_village'){
                    $third_user_id = $value['pigcms_id'];
                    if ($authidSource) {
                        $authid =  $authidSource;
                    } else {
                        $authid1       = '2' . (100000 + $deviceInfo['device_id']) . $third_user_id;
                        // 获取用户设备绑定信息
                        $where = [];
                        $where[] = ['personID', '=', $authid1];
                        $where[] = ['device_type', '=', 29];
                        $info = $face_user_bind_device->getOneOrder($where, 'bind_id,device_id', ['id' => 'desc']);
                        if ($info && isset($info['bind_id'])) {
                            $authid = $authid1;
                        } else {
                            $authid = '3' . (100000 + $deviceInfo['device_id']) . $third_user_id;
                        }
                    }
                    $RYID          = isset($value['third_ryid']) && $value['third_ryid'] ? $value['third_ryid'] : $authid;
                    $face_img=$db_house_face_img->getOne([
                        ['uid', '=', $value['uid']],
                        ['status','in',[0,3]],
                        ['img_url', '<>', '']
                    ],'id,img_url,status');
                    if ($face_img && !$face_img->isEmpty()) {
                        $img_url=$face_img['img_url'];
                        if (3==$face_img['status']) {
                            $img_url = $wordencryption->text_decrypt($img_url);
                        }
                        try {
                            $config_site_url = cfg('site_url');
                            $sImageService = new ImageService();
                            $equalThumbArr = $sImageService->equalThumb($img_url, $config_site_url);
                        } catch (\Exception $e) {
                            $equalThumbArr = [];
                            fdump_api(['执行错误' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'img_url' => $img_url], 'duopu/errThumbImageLog');
                        }
                        $thumb_url = isset($equalThumbArr['thumb_url']) && trim($equalThumbArr['thumb_url']) ? trim($equalThumbArr['thumb_url']) : '';
                        fdump_api(['msg' => '图片拼接', 'img_url' => $img_url, 'thumb_url' => $thumb_url,'equalThumbArr'=>$equalThumbArr], 'duopu/authidSourceLog');
                        if ($thumb_url && isset($equalThumbArr['savePath']) && file_exists($equalThumbArr['savePath'])) {
                            $img_url = $thumb_url;
                        } else {
                            //$img_url = thumb_img($img_url,1920,1080,'', true);
                        }
                    }
                    if(!empty($value['name'])){
                        $name=$value['name'];
                    }elseif (!empty($value['phone'])){
                        $name=$value['phone'];
                    }else{
                        $name=$value['pigcms_id'];
                    }
                }
                fdump_api(['authidSource' => $authidSource, '$whereSource' => $whereSource, '$value' => $value, '$authid' => $authid], 'duopu/authidSourceLog');
                $img_url= $img_url ? replace_file_domain($img_url) : '';
                
                $where = [];
                $where[] = ['bind_id','=',$value['pigcms_id']];
                $where[] = ['personID','=',$authid];
                $where[] = ['device_id','=',$deviceInfo['device_id']];
                $where[] = ['group_id','=',$village_id];
                $where[] = ['device_type','=',29];
                $faceUserBindDeviceInfo = $face_user_bind_device->getOneOrder($where,'id', ['id'=>'desc']);
                if ($faceUserBindDeviceInfo && isset($faceUserBindDeviceInfo['id']) && $faceUserBindDeviceInfo['id']) {
                    // 添加过的重复数据变更信息人脸绑定设备信息表
                    $saveData = [
                        'last_time' => time(),
                    ];
                    $face_user_bind_device->saveData($where,$saveData);
                    $idFace = $faceUserBindDeviceInfo['id'];
                } else {
                    // 保存人脸绑定设备信息表
                    $face_user_bind = [
                        'bind_id' => $value['pigcms_id'],
                        'person_id' => $third_user_id,
                        'personID' => $authid,
                        'group_id' => $village_id,
                        'device_id' => $deviceInfo['device_id'],
                        'device_type' => 29,
                        'add_time' => time(),
                    ];
                    $idFace = $face_user_bind_device->addData($face_user_bind);
                }
                $nmv_card_no=false;
                if (isset($deviceInfo['thirdDeviceTypeStr'])&&$deviceInfo['thirdDeviceTypeStr']==2) {
                    if($value['type']==4 ){
                        $valiend = $end_time;
                    }else{
                        $nmv_card_no='';
                        $where = [];
                        $where[] = ['bind_id','=',$value['pigcms_id']];
                        // 获取非机动车卡号
                        $nmvCardInfo = $nmv_card->getCardInfo($where,'id,expiration_time,nmv_card');
                        if (!isset($nmvCardInfo['expiration_time']) || !$nmvCardInfo['expiration_time'] || $nmvCardInfo['expiration_time']<0) {
                            // 没有有效期就取当前时间
                            $expiration_time = time()+5;
                        } else {
                            $expiration_time = intval($nmvCardInfo['expiration_time']); // 秒
                        }
                        if ($nmvCardInfo&&isset($nmvCardInfo['id'])&&$nmvCardInfo['id']) {
                            $nmv_card_no=$nmvCardInfo['nmv_card'];
                            $nmv_card_no =$nmv_card_no ? $nmv_card_no:'';
                            //$this->sendNmvToDevice($nmvCardInfo['id']);
                        }
                        $valiend = intval($expiration_time*1000); // 毫秒
                    }
                } else {
                    $valiend = $end_time;
                }

                // 1:下发,-1:移除
                $opreatype = 1;
                if($operation == 'del'){
                    $opreatype = -1;
                }
                $temp = [
                    'authid' => $authid,
                    'code' => $deviceSn,
                    'RYID' => $RYID,
                    'xqcode' => $thirdInfo['village_num'],
                    'opreatype' => $opreatype,
                    'faceurl' => $img_url,
                    'valiendfrom' => $msectime,
                    'valiend' => $valiend,
                    'persontype' => $persontype,// 人员类型 1:韦根卡/省厅录入人员 2:手动录入人员
                ];
                if($nmv_card_no!==false){
                    $temp['wgcard'] = $this->handleNmvCard($nmv_card_no);
                }
                if($name){
                    $temp['name']=$name;
                }
                // 发送人员出入审核结果反馈
                $retrun = $dopu->gateauth($temp);
                // code等于1是成功
                if($retrun[0] == 200 && json_decode($retrun[1],true)['code'] == 0){
                    // 保存状态为3  已发送下发请求，等待回馈
                    if($source == 'gonan'){
                        $whereTh = [];
                        $whereTh[] = ['third_user_id','=',$third_user_id];
                        $third_user->saveData($whereTh,['device_status' => 3,'last_time' => time()]);
                    }
                    elseif ($source == 'house_village'){
                        $user_bind->saveOne([
                            ['pigcms_id', '=', $value['pigcms_id']],
                        ],[
                            'face_img_status' => 1,
                            'face_img_reason' => '',
                        ]);
                    }
                    if ($idFace) {
                        $whereBindFace = [];
                        $whereBindFace[] = ['id', '=', $idFace];
                        $saveDataFace = [
                            'person_text' => json_encode($temp, JSON_UNESCAPED_UNICODE),
                        ];
                        $face_user_bind_device->saveData($whereBindFace,$saveDataFace);
                    }
                }else{
                    $code = 1003;
                    if($retrun[0] == 400){
                        $msg = '第三方设备提示：请求404' . $this->tip;
                    }else{
                        $thirdMsg = json_decode($retrun[1],true)['msg'];
                        if ('收到请求' != $thirdMsg) {
                            $thirdMsg .= $this->tip;
                        }
                        $msg = '第三方设备提示：'.$thirdMsg;
                    }
                }
                if (!$logId) {
                    // 下发状态 0 正在上传  1 成功  2失败
                    $status = $code ? 2 : 1;
                    $logMsg = $code ? $msg : '';
                    $syncLogParams = [
                        'pigcms_id' => $value['pigcms_id'], 'device_id' => $deviceInfo['device_id'], 'nowTime' => time(), 'status' => $status, 'msg' => $logMsg,
                    ];
                    (new OwnerService())->handleHouseFaceSyncLog($syncLogParams);
                }
            }
            if ($logId) {
                (new OwnerService())->updateLog(['log_id' => $logId], [
                    'status' => $code ? 2 : 1,
                    'msg' => $code ? $msg : '',
                    'last_time' => time()
                ]);
            }
            return ['code' => $code,'msg' => $msg];
        } else {
            if ($logId) {
                (new OwnerService())->updateLog(['log_id' => $logId], [
                    'status' => 2,
                    'msg' => L_('未获取到用户信息'),
                    'last_time' => time()
                ]);
            }
            return ['code' => 0, 'msg' => '暂无数据'];
        }
    }

    /**
     * 权限接口信息下发
     * @param $village_id
     * @param $param
     * @return array
     */
    public function receiveDeviceAuth($village_id,$param){
        $face_user_bind_device = new FaceUserBindDevice();
        $face_device = new HouseFaceDevice();
        $third_user = new HouseVillageThirdUserInfo();

        if($param['opresult'] && $param['opresult'] !== 'false'){
            // 下发成功
            $data = [
                'info' => json_encode($param,JSON_UNESCAPED_UNICODE),
                'face_status' => 2,
                'last_time' => time(),
            ];
            $device_status = 1;
            if(isset($param['opreatype']) && $param['opreatype'] == -1){
                $device_status = 0;
            }
            $device_data = [
                'device_status' => $device_status,
                'last_time' => time()
            ];
            $userBindData = [
                'face_img_status' => 1,
                'face_img_reason' => '',
            ];
        }else{
            // 下发失败
            $data = [
                'info' => json_encode($param,JSON_UNESCAPED_UNICODE),
                'face_status' => 3,
                'face_reason' => $param['msg'],
                'last_time' => time(),
            ];
            $device_data = [
                'device_status' => 2,
                'last_time' => time()
            ];
            $userBindData = [
                'face_img_status' => 2,
                'face_img_reason' => $param['msg'],
            ];
        }

        $authid_first = substr($param['authid'],0,1);
        if($authid_first == 1){ // 人脸上传反馈
            $third_user_id = substr($param['authid'],7);
            // 获取用户设备绑定信息
            $where = [];
            $where[] = ['personID','=',$param['authid']];
            $where[] = ['device_type','=',29];
            $info = $face_user_bind_device->getOneOrder($where,'bind_id,device_id', ['id'=>'desc']);
            if ($info && !is_array($info)) {
                $info = $info->toArray();
            }
            if (!$info || !isset($info['bind_id'])) {
                $where = [];
                $where[] = ['person_id','=',$third_user_id];
                $where[] = ['device_type','=',29];
                $info = $face_user_bind_device->getOneOrder($where,'bind_id,device_id', ['id'=>'desc']);
                if ($info && !is_array($info)) {
                    $info = $info->toArray();
                }
            }
            $user_bind = new HouseVillageUserBind();
            if ($info && isset($info['bind_id'])) {
                $userBindInfo = $user_bind->getOne([['pigcms_id', '=', $info['bind_id']]], 'third_id');
                if ($userBindInfo && !is_array($userBindInfo)) {
                    $userBindInfo = $userBindInfo->toArray();
                }
            }
            // 更新小区三方对接用户信息
            $where_third = [];
            if (isset($userBindInfo) && isset($userBindInfo['third_id']) && $userBindInfo['third_id']) {
                $where_third[] = ['third_ryid','=',$userBindInfo['third_id']];
            } else {
                $where_third[] = ['third_user_id','=',$third_user_id];
            }
            $third_user->saveData($where_third,$device_data);

            $where_device = [];
            $where_device[] = ['village_id','=',$village_id];
            $where_device[] = ['device_id','=',$info['device_id']];
            $device_info = $face_device->getOne($where_device,'device_id');
            if(!$device_info){
                fdump_api(['msg'=> '参数错误', '$village_id' => $village_id,'$param' => $param,'$info' => $info,'$device_info' => $device_info],'dopu/errReceiveDeviceAuthLog',1);
//                return ['code' => 1,'message' => '参数错误'];
            }
            if (isset($info['bind_id'])) {
                // 更新人员绑定设备信息
                $face_user_bind_device->saveData($where, $data);
                $user_bind->saveOne([['pigcms_id', '=', $info['bind_id']]], $userBindData);
            }
        } else {
            // 获取用户设备绑定信息
            $where = [];
            $where[] = ['personID','=',$param['authid']];
            $where[] = ['device_type','=',29];
            $info = $face_user_bind_device->getOneOrder($where,'bind_id,device_id,person_id', ['id'=>'desc']);
            if ($info && !is_array($info)) {
                $info = $info->toArray();
            }
            if ($info && isset($info['bind_id'])) {
                // 更新人员绑定设备信息
                $face_user_bind_device->saveData($where, $data);
                $user_bind = new HouseVillageUserBind();
                $user_bind->saveOne([['pigcms_id', '=', $info['bind_id']]], $userBindData);
            }
            fdump_api(['msg'=> '更改記錄', '$village_id' => $village_id,'$param' => $param,'$info' => $info,'$device_data' => $device_data],'dopu/1receiveDeviceAuthLog',1);
            if ($info && isset($info['bind_id']) && isset($info['person_id'])) {
                $user_bind = new HouseVillageUserBind();
                $userBindInfo = $user_bind->getOne([['pigcms_id', '=', $info['bind_id']]], 'third_id');
                if ($userBindInfo && !is_array($userBindInfo)) {
                    $userBindInfo = $userBindInfo->toArray();
                }
                fdump_api(['msg'=> '更改記錄', '$village_id' => $village_id,'$param' => $param,'$userBindInfo' => $userBindInfo,'$device_data' => $device_data],'dopu/1receiveDeviceAuthLog',1);
                if ($userBindInfo && isset($userBindInfo['third_id'])) {
                    $where_third = [];
                    $where_third[] = ['third_ryid','=',$userBindInfo['third_id']];
                    $third_user->saveData($where_third,$device_data);
                } elseif ($info['bind_id'] != $info['person_id']) {
                    $where_third = [];
                    $where_third[] = ['third_user_id','=',$info['person_id']];
                    $third_user->saveData($where_third,$device_data);
                }
            }
        }

        return ['code' => 0,'message' => '数据接收成功'];
    }

    /**
     * 接收流水数据下发
     * @param $village_id
     * @param $param
     * @return array
     */
    public function receiveFlowData($village_id,$param){
        $house_village = new HouseVillage();
        $house_village_user_bind = new HouseVillageUserBind();
        $face_device = new HouseFaceDevice();
        $house_user_log = new HouseUserLog();

        $log_status = 0;
        if($param['passmode'] == '人脸识别'){
            $log_from = 1;
        }elseif ($param['passmode'] == '刷卡通行'){
            $log_from = 3;
        }else{
            // 远程开门
            $log_from = 2;
            $log_status = 1;
        }

        // 小区信息
        $village_info = $house_village->getOne($village_id,'village_name');
        // 人员信息
        $where_user = [];
        $where_user[] = ['village_id', '=', $village_id];
        if (strstr($param['RYID'], 'village') !== false) {
            $pigcms_id = str_replace('village', '', $param['RYID']);
            $where_user[] = ['pigcms_id', '=', $pigcms_id];
        } else {
            $authid = $param['RYID'];
            $where = [];
            $where[] = ['personID', '=', $authid];
            $where[] = ['device_type', '=', 29];
            $face_user_bind_device = new FaceUserBindDevice();
            $info = $face_user_bind_device->getOneOrder($where, 'bind_id,device_id', ['id' => 'desc']);
            if ($info && !is_array($info)) {
                $info = $info->toArray();
            }
            $pigcms_id = isset($info['bind_id']) && $info['bind_id'] ? $info['bind_id'] : 0;
            if ($pigcms_id) {
                $where_user[] = ['pigcms_id', '=', $pigcms_id];
            } else {
                $where_user[] = ['third_id', '=', $param['RYID']];
            }
        }
        $user_info = $house_village_user_bind->getOne($where_user,'pigcms_id,uid');

        if(!$user_info || $user_info->isEmpty()){
//            fdump_api(['$village_id' => $village_id,'$param' => $param],'dopu/receiveFlowData_not_user',1);
            if(isset($param['authid']) && $param['authid']){
                $bind_device=(new FaceUserBindDevice())->getOne([
                    ['personID','=',$param['authid']]
                ],'bind_id');
                if ($bind_device && !$bind_device->isEmpty()){
                    $user_info = $house_village_user_bind->getOne([
                        ['pigcms_id','=',$bind_device['bind_id']]
                    ],'pigcms_id,uid');
                }
            }
        }
        // 设备信息
        $where_device = [];
        $where_device[] = ['device_sn','=',$param['code']];
        $where_device[] = ['village_id','=',$village_id];
        $device_info = $face_device->getOne($where_device,'device_id');

        // 通行数据 是否存在记录
        $where_log = [];
        $where_log[] = ['eventId','=',$param['accessid']];
        $res = $house_user_log->getFind($where_log,'log_id');

        if ($param['face']) {
            $photo = str_replace('data:image/jpeg;base64,','',$param['face']);
            $fileName = trim($param['accessid']) . trim($param['authid']) . trim($param['accesstime']);
            $img_url = $this->base64_to_img($photo,'face_log',$fileName);
            $img_url = replace_file_domain($img_url);
            $param['face_img'] = $img_url;
            unset($param['face']);
        }
        if(!$res || $res->isEmpty()){
            $data = [
                'uid' => $user_info['uid'],
                'log_bind_id' => $user_info['pigcms_id'],
                'log_name' => $village_info['village_name'],
                'log_from' => $log_from,
                'log_business_id' => $village_id,
                'device_id' => $device_info['device_id'],
                'log_status' => $log_status,
                'log_detail' => json_encode($param),
                'log_time' => floor($param['accesstime']/1000),
                'eventId' => $param['accessid'],
            ];
            if (isset($param['face_img']) && trim($param['face_img'])) {
                $data['cut_image'] = trim($param['face_img']);
            }
            $house_user_log->addData($data);
        }
        return ['code' => 0,'message' => '数据接收成功'];
    }

    /**
     * 获取非机动车卡号列表
     * @param $where
     * @param $page
     * @param $limit
     * @param $field
     * @param $order
     * @return array
     */
    public function getNmvCardList($where,$page,$limit,$field,$order,$cfromtype=''){
        $nmv_card = new HouseVillageNmvCard();
        $list = $nmv_card->getNmvList($where,$page,$limit,$field,$order);
        $huizhisq = 0;
        if ($this->hasCloudIntercom()){
            $huizhisq = 1;
        }
        if($cfromtype=='search'){
            $tmpArr=$list;
            if(isset($list['list']) && is_array($list['list'])){
                $tmpArr=$list['list'];
            }
            $rets=array('list'=>array(),'is_huizhisq'=>$huizhisq);
            $listArr=array();
            if($tmpArr){
                foreach ($tmpArr as $kk=>$vv){
                    if($vv['expiration_time']>100){
                        $vv['expiration_time']=date('Y-m-d H:i:s',$vv['expiration_time']);
                    }else{
                        $vv['expiration_time']='暂无';
                    }
                    $tmpData=array('pigcms_id'=>$vv['pigcms_id'],'name'=>$vv['name'],'expiration_time'=>$vv['expiration_time']);
                    if(isset($listArr[$vv['pigcms_id']])){
                        $listArr[$vv['pigcms_id']]['card_list'][]=array('id'=>$vv['id'],'nmv_card'=>$vv['nmv_card']);
                    }else{
                        $tmpData['card_list']=array();
                        $tmpData['card_list'][]=array('id'=>$vv['id'],'nmv_card'=>$vv['nmv_card']);
                        $listArr[$vv['pigcms_id']]=$tmpData;
                    }
                    
                }
               $rets['list']=array_values($listArr);
            }
            return $rets;
        }else{
            foreach ($list['list'] as &$value){
                $expiration_time = $value['expiration_time'];
                // 到期时间
                $value['expiration_time'] = empty($expiration_time) ? '暂无' : date("Y-m-d H:i:s",$expiration_time);
                // 状态
                $value['status'] = '未到期';
                // 剩余天数
                if($expiration_time < time()){
                    $value['status'] = '已过期';
                    if(empty($expiration_time)){
                        $value['status'] = '未缴费';
                    }
                    $expiration_time = time();
                }
                $value['surplus_days'] = round(($expiration_time-time())/24/3600);
            }
            $list['is_huizhisq']=$huizhisq;
        }
        return $list;
    }

    /**
     * 发送消息模板
     * @param $send
     * @param $property_name
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sendNmvMessage($send,$property_name)
    {
        $templateNewsService = new TemplateNewsService();
        $nmv_card = new HouseVillageNmvCard();
        $db_user = new User();

        $data = [];

        // 非机动车卡列表
        $where = [];
        $where[] = ['c.id','in',$send];
        $list = $nmv_card->getNmvList($where,0,10,'b.uid,b.name,c.*');
        foreach ($list as $value){
            if(!empty($value['expiration_time'])){
                $status = '于'.date('Y-m-d',$value['expiration_time']).'到期';
                if($value['expiration_time'] < time()){
                    $status = '于'.date('Y-m-d',$value['expiration_time']).'已到期';
                }
            }else{
                $status = '暂未缴费';
            }

            $user_info = $db_user->getOne(['uid' => $value['uid']]);
            if (!empty($user_info) && $user_info['openid']) {
                $href = get_base_url('pages/houseMeter/BicycleLane/BicycleLanePay?select_pigcms_id='.$value['bind_id']);
                $str = '尊敬的业主'.$value['name'].'，您的非机动车卡['. $value['nmv_card'] .']'. $status .'，请您尽快续费避免影响您的正常出行。';
                $datamsg = [
                    'tempKey' => 'TM01008',
                    'dataArr' => [
                        'href' => $href,
                        'wecha_id' => $user_info['openid'],
                        'first' => ' 尊敬的业主，您有新的消息！',
                        'keynote1' => $property_name,
                        'keynote2' => $value['nmv_card'],
                        'remark' => $str,
                        'new_info' => [//新版本发送需要的信息
                            'type'=>0,//缴费提醒类型（0：提醒缴费，1：提醒缴费成功）
                            'thing2'=>$value['name'],//业主姓名
                            'thing3'=>'非机动车卡['. $value['nmv_card'] .']'. $status,//地址
                            'thing9'=>'非机动车卡续费',//缴费类型
                            'thing10'=>$property_name,//账单名称
                            'amount4'=>'无',//账单金额
                        ],
                    ]
                ];
                //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
            }
        }
        return $data;
    }

    /**
     * 非机动车卡号的缴费记录
     * @param $id
     * @param $fieldOrder
     * @param $page
     * @param $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getNmvChargeOrderList($id,$fieldOrder,$page,$limit){
        $new_pay_order = new HouseNewPayOrder();
        $nmv_card = new HouseVillageNmvCard();
        $village_nmv_charge = new HouseVillageNmvCharge();

        // 获取用户小区绑定ID
        $nmv_info = $nmv_card->getCardInfo([['id','=',$id]],'bind_id');

        $whereOrder = [];
        $whereOrder[] = ['pay_bind_id','=',$nmv_info['bind_id']];
        $whereOrder[] = ['is_paid','=',1];
        $whereOrder[] = ['order_type','=','non_motor_vehicle'];
        $list = $new_pay_order->getOrderSingleList($whereOrder, $fieldOrder, $page, $limit);
        if(!empty($list['list'])){
            foreach ($list['list'] as &$value){
                // 缴费规则类型
                $cardInfo = $village_nmv_charge->getChargeInfo([['id','=',$value['rule_id']]],'type');
                if($value['extra_data'] && !empty($value['extra_data'])){
                    $extra_data=json_decode($value['extra_data'],1);
                    if(isset($extra_data['nmv_charge']) && !empty($extra_data['nmv_charge'])){
                        $cardInfo=$extra_data['nmv_charge'];
                    }
                }
                $value['type_name'] = ($cardInfo['type'] == 1) ? '每日' : (($cardInfo['type'] == 2) ? '每月' : '每年');
                // 支付时间
                $value['pay_time'] = empty($value['pay_time']) ? '' : date("Y-m-d H:i:s",$value['pay_time']);
                // 支付方式
                switch ($value['pay_type']){
                    case 0:
                        $value['pay_type'] = '余额支付';
                        break;
                    case 1:
                        $value['pay_type'] = '扫码支付';
                        break;
                    case 2:
                        $value['pay_type'] = '线下支付';
                        break;
                    case 3:
                        $value['pay_type'] = '收款码支付';
                        break;
                    case 4:
                        $value['pay_type'] = '线上支付';
                        break;
                }
                $value['cycle_num']=1;
                if($cardInfo['type']==3){
                    $yer=$value['prepaid_cycle']/12;
                    $yer=round($yer,1);
                    $yer=$yer*1;
                    $value['cycle_num']=$yer;
                }else if($cardInfo['type']==2){
                    $value['cycle_num']=$value['prepaid_cycle'];
                }else if($cardInfo['type']==1){
                    $value['cycle_num']='1日';
                    if($value['prepaid_cycle']>0 ){
                        $value['cycle_num']=$value['prepaid_cycle'];
                    }
                }
                
                // 支付状态
                $value['is_paid'] = ($value['is_paid'] == 1) ? '支付成功' : '支付失败';
            }
        }
        return $list;
    }
    /**
     * 获取非机动车收费规则
     * @param $where
     * @param $page
     * @param $limit
     * @param $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getNmvChargeList($where,$page,$limit,$field){
        $village_nmv_charge = new HouseVillageNmvCharge();
        $count = $village_nmv_charge->getChargeCount($where);
        $list = $village_nmv_charge->getChargeList($where,$field,$page,$limit);
        foreach ($list as &$value){
            $value['type_text'] = ($value['type']==1) ? '每天' : (($value['type']==2) ? '每月' : '每年');
            $value['status_text'] = ($value['status']==1) ? '启用' : '禁用';
        }
        $data['list'] = $list;
        $data['count'] = $count;
        return $data;
    }

    /**
     * 编辑非机动车卡号信息
     * @param $type
     * @param $id
     * @param $info
     * @param $village_id
     * @param string $account
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function editNmvChargeInfo($type,$id,$info,$village_id,$account = ''){
        $village_nmv_charge = new HouseVillageNmvCharge();

        $message = '操作成功';
        if($type == 'add' || $type == 'edit'){ // 新增或编辑

            $where = [];
            $where[] = ['type','=',$info['type']];
            $where[] = ['status','=',1];
            $where[] = ['is_del','=',0];
            $where[] = ['village_id','=',$village_id];
            if($type == 'edit'){
                if($id < 1){
                    return ['code' => 1003,'message' => '参数错误'];
                }
                $where[] = ['id','<>',$id];
            }
            // 判断该类型是否存在其他已开启收费标准
            $nmv_charge = $village_nmv_charge->getChargeInfo($where,'id');
            if(!empty($nmv_charge)){
                $message = '操作成功！注意:每个收费类型只能启用一种标准，已自动禁用该类型的其他标准';
            }

            if($info['price'] <= 0){
                return ['code' => 1003,'message' => '请填写收费金额'];
            }

            $data = [
                'nmv_charge_name' => $info['nmvChargeName'],
                'type' => $info['type'],
                'price' => $info['price'],
                'status' => $info['status'],
                'update_at' => time()
            ];
            if($type == 'add'){ // 新增
                $data['village_id'] = $village_id;
                $data['create_at'] = time();
                $res = $village_nmv_charge->addCharge($data);
                $id = $res;
            }else{ // 编辑
                $res = $village_nmv_charge->updateCharge([['id','=',$id]],$data);
            }
            // 每个收费类型只能启用一种标准，自动禁用该类型的其他标准
            if($res && !empty($nmv_charge)){
                $saveData = [];
                $saveData['status'] = 0;
                $saveData['update_at'] = time();
                $village_nmv_charge->updateCharge([['id','=',$nmv_charge['id']]],$saveData);
                $this->addChargeLog($id,$info,$type,$account);
            }
        }else{ // 删除
            if($id < 1){
                return ['code' => 1003,'message' => '参数错误'];
            }
            $save = [
                'is_del' => 1,
                'update_at' => time()
            ];
            $res = $village_nmv_charge->updateCharge([['id','=',$id]],$save);
        }
        if($res){
            $this->addChargeLog($id,$info,$type,$account);
            return ['code' => 0,'message' => $message];
        }else{
            return ['code' => 1003,'message' => '操作失败'];
        }
    }

    /**
     * 添加缴费规则变更记录
     * @param $id
     * @param $info
     * @param $type
     * @param $account
     */
    private function addChargeLog($id,$info,$type,$account){
        $village_nmv_charge_log = new HouseVillageNmvChargeLog();
        $log = [
            'nmv_charge_id' => $id,
            'content' => json_encode(['$info' => $info,'$type' => $type,'$id' => $id]),
            'operation' => $account,
            'create_at' => time(),
            'update_at' => time(),
        ];
        // 添加修改记录
        $village_nmv_charge_log->addLog($log);
    }

    /**
     * 获取业主非机动车卡号列表
     * @param $where
     * @param $page
     * @param $limit
     * @param $order
     * @param $field
     * @return array
     */
    public function getUserCardList($where,$page,$limit,$whereOr,$field){
        $village_user_bind = new HouseVillageUserBind();

        $data = [];
        // 获取 user_bind 列表
        $count = $village_user_bind->getVillageUserNum($whereOr[1]);
        $list = $village_user_bind->getUserLists($where,$whereOr,$field,$page,$limit);
        if($list && !$list->isEmpty()){
            $list = $list->toArray();
            foreach ($list as &$value){
                $value['type_text'] = ($value['type'] == 0 || $value['type'] == 3) ? '业主' : (($value['type'] == 1) ? '家属' : (($value['type'] == 2) ? '租客' : '其他'));
            }
        }
        $data['list'] = $list;
        $data['count'] = $count+1;
        return $data;
    }

    /**
     * 保存非机动车卡号
     * @param $where
     * @param $nmv_card
     * @return array
     */
    public function saveUsernmvCard($pigcms_id,$nmv_card){
        $village_user_bind = new HouseVillageUserBind();
        $village_nmv_card = new HouseVillageNmvCard();
        $new_pay_order = new HouseNewPayOrder();

        $where = [];
        $where[] = ['pigcms_id','=',$pigcms_id];
        // 获取 user_bind 信息
        $info = $village_user_bind->getOne($where,'pigcms_id,nmv_card,uid');
        if(!$info || $info->isEmpty()){
            return ['code' => 1003,'msg' => '参数错误'];
        }
        // 卡号相同 返回保存成功
        /*
        if($info['nmv_card'] == $nmv_card){
            return ['code' => 0,'msg' => '保存成功'];
        }
        */
        // 判断卡号有没有人在使用
        if($nmv_card){
            $info_card = $village_user_bind->getOne([['nmv_card','=',$nmv_card],['pigcms_id','<>',$pigcms_id],['status','<',4]],'pigcms_id');
            if($info_card && !$info_card->isEmpty()){
                return ['code' => 1003,'msg' => '该卡号已有人使用'];
            }
        }
        // 更新绑定关系
        $res = $village_user_bind->saveOne($where,['nmv_card' => $nmv_card,'message_time' => time()]);
        if($res){
            $expiration_time = 0;
            if(!empty($info['nmv_card'])){
                // 业主原来卡号信息
                $card_expiration_time = $village_nmv_card->getCardInfo(['nmv_card' => $info['nmv_card']],'id,expiration_time');
                if(!empty($card_expiration_time)){
                    $expiration_time = $card_expiration_time['expiration_time'];
                }
            }
            // 新增或编辑非机动车卡 卡号从未有人使用过
            if($info['nmv_card'] && empty($nmv_card)){
                $card = $village_nmv_card->getCardInfo(['nmv_card' => $info['nmv_card']],'id');
            }else{
                $card = $village_nmv_card->getCardInfo(['nmv_card' => $nmv_card],'id');  
            }
            if(empty($card)){
                $card_data = [
                    'nmv_card' => $nmv_card,
                    'bind_id' => $pigcms_id,
                    'expiration_time' => $expiration_time,
                    'create_at' => time(),
                    'update_at' => time(),
                ];
                $card_id = $village_nmv_card->addCard($card_data);
            }else{
                // 新增或编辑 卡号有人使用过
                $card_data = [
                    'bind_id' => $pigcms_id,
                    'expiration_time' => $expiration_time,
                    'create_at' => time(),
                    'update_at' => time(),
                ];
                $village_nmv_card->updateCardInfo(['nmv_card' => $nmv_card],$card_data);
                $card_id = $card['id'];
            }

            // 新卡同步设备
            if($expiration_time > time()){
                $this->sendNmvToDevice($card_id);
            }

            // 原来的卡号使失效
            if(!empty($info['nmv_card']) && $info['nmv_card']!=$nmv_card && !empty($card_expiration_time)){
                // 原来的卡号移除设备 同步设备
                $this->sendNmvToDevice($card_expiration_time['id'],-1);
                $card_data = [];
                $card_data['bind_id'] = 0;
                $card_data['expiration_time'] = 0;
                $village_nmv_card->updateCardInfo(['nmv_card' => $info['nmv_card']],$card_data);
            }

            return ['code' => 0,'msg' => '保存成功'];
        }else{
            return ['code' => 1003,'msg' => '保存失败'];
        }
    }

    /**
     * 获取非机动车收费规则详情
     * @param $pigcms_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getNMVChargeInfo($pigcms_id){
        $village_user_bind = new HouseVillageUserBind();
        $village_user_vacancy = new HouseVillageUserVacancy();

        $where = [];
        $where[] = ['a.pigcms_id','=',$pigcms_id];
        $field = 'v.village_name,a.vacancy_id,a.nmv_card,a.village_id';
        // 获取小区
        $villageInfo = $village_user_bind->getVillageUserBind($where,$field);
        if(!empty($villageInfo)){
            if(empty($villageInfo['nmv_card'])){
                return ['code' => 1003,'msg' => '该用户暂未绑定非机动车卡','data' => []];
            }
            $where_vacancy = [];
            $where_vacancy[] = ['a.pigcms_id','=',$villageInfo['vacancy_id']];
            // 获取房间数据
            $vacancyInfo = $village_user_vacancy->getInfo($where_vacancy,'','a.room,b.single_name,c.floor_name,d.layer_name');
            $roomName = $vacancyInfo['single_name'].(cfg('single_name')?:'栋').$vacancyInfo['floor_name'].(cfg('floor_name')?:'单元').$vacancyInfo['layer_name'].'层'.$vacancyInfo['room'];
            $villageInfo['roomName'] = $roomName;

            // 计算非机动车收费规则到期时间及收费金额
            $culCost = $this->calculationNmvCost($villageInfo['nmv_card'],$pigcms_id,$villageInfo['village_id']);
            if(!empty($culCost['code'])){
                return $culCost;
            }
            $cardInfo = $culCost['cardInfo'];
            $chargeList = $culCost['chargeInfo'];

            $data['villageInfo'] = $villageInfo;
            $data['cardInfo'] = $cardInfo;
            $data['roomInfo'] = [
                ['title' => '当前小区','value' => $villageInfo['village_name']],
                ['title' => '房间','value' => $roomName],
                ['title' => '非机动车卡号','value' => $cardInfo['nmv_card']],
                ['title' => '到期时间','value' => $cardInfo['expiration_time']],
            ];
            $data['chargeInfo'] = $chargeList;
            $data['notice'] = '提示：关注微信公众号后，了解缴费信息';
            return ['code' => 0,'msg' => '查询成功','data' => $data];
        }else{
            return ['code' => 1003,'msg' => '参数错误，未查询到相关数据','data' => []];
        }
    }

    /**
     * 非机动车去支付
     * @param $pigcms_id
     * @param $rule_id
     * @param $app_type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function nmvGoPay($pigcms_id,$rule_id,$app_type){
        $village_user_bind = new HouseVillageUserBind();
        $db_house_new_pay_order = new HouseNewPayOrder();
        $service_new_pay = new NewPayService();

        $where = [];
        $where[] = ['a.pigcms_id','=',$pigcms_id];
        $field = 'v.village_name,v.property_id,a.uid,a.name,a.phone,a.vacancy_id,a.nmv_card,a.village_id';
        // 获取小区用户信息
        $villageInfo = $village_user_bind->getVillageUserBind($where,$field);
        if(empty($villageInfo)){
            return ['code' => 1003,'msg' => '参数错误，未查询到相关数据','data' => []];
        }

        // 计算非机动车收费规则到期时间及收费金额
        $culCost = $this->calculationNmvCost($villageInfo['nmv_card'],$pigcms_id,$villageInfo['village_id'],$rule_id);
        if(!empty($culCost['code'])){
            return $culCost;
        }
        $cardInfo = $culCost['cardInfo'];
        $chargeInfo = $culCost['chargeInfo'][0];

        $pay_order = [];
        $pay_order['uid'] = $villageInfo['uid'];
        $pay_order['pigcms_id'] = $pigcms_id;
        $pay_order['name'] = $villageInfo['name'];
        $pay_order['phone'] = $villageInfo['phone'];
        $pay_order['pay_bind_id'] = $pigcms_id;
        $pay_order['pay_bind_name'] = $villageInfo['name'];
        $pay_order['pay_bind_phone'] = $villageInfo['phone'];
        $pay_order['order_type'] = 'non_motor_vehicle';  // 缴费类型
        $pay_order['order_name'] = '非机动车停车费';  // 缴费名称
        $pay_order['room_id'] = $villageInfo['vacancy_id']; // 房间id
        $pay_order['position_id'] = $cardInfo['id']; // 车位id 非机动车卡号ID
        $pay_order['property_id'] = $villageInfo['property_id']; // 物业id
        $pay_order['village_id'] = $villageInfo['village_id'];  // 小区id
        $pay_order['total_money'] = $chargeInfo['price']; // 应支付金额
        $pay_order['modify_money'] = $chargeInfo['price'];    // 修改后的金额
        $pay_order['prepare_pay_money'] = $chargeInfo['price'];    // 预交费金额
        $pay_order['is_paid'] = 2;
        $pay_order['is_prepare'] = 1;
        $pay_order['prepaid_cycle'] = ($chargeInfo['type'] == 1) ? 0 : ($chargeInfo['type'] == 2) ? 1 : 12;    // 预缴周期
        $pay_order['service_month_num'] = ($chargeInfo['type'] == 1) ? 0 : ($chargeInfo['type'] == 2) ? 1 : 12;    // 缴费周期
        $pay_order['service_start_time'] = ($cardInfo['expiration_time'] == '暂无') ? time() : strtotime($cardInfo['expiration_time'].'+1 day');    // 服务开始时间
        $pay_order['service_end_time'] = strtotime($chargeInfo['expect_expiration_time']);    // 服务结束时间
        $pay_order['diy_type'] = 4;    // 缴费周期
        $pay_order['rule_id'] = $chargeInfo['id'];    // 消费标准
        $pay_order['order_no'] = '';    // 订单编号
        $pay_order['add_time'] = time();
        $pay_order['update_time'] = time();

        $id = $db_house_new_pay_order->addOne($pay_order);
        if($id){
            $pay_order['order_id'] = $id;
            $data = $service_new_pay->CashierPrepaidGoPay($pay_order,$villageInfo['village_id'],$pigcms_id,$app_type);
            return ['code' => 0,'msg' => '查询成功','data' => $data];
        }
    }

    /**
     * 计算非机动车收费规则到期时间及收费金额
     * @param $nmv_card
     * @param $pigcms_id
     * @param $village_id
     * @param int $rule_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function calculationNmvCost($nmv_card,$pigcms_id,$village_id,$rule_id=0){
        if(empty($nmv_card) || empty($pigcms_id) || empty($village_id)){
            return ['code' => 1003,'msg' => '参数错误','data' => []];
        }
        $village_nmv_card = new HouseVillageNmvCard();
        $village_nmv_charge = new HouseVillageNmvCharge();
        $where_card = [];
        $where_card[] = ['nmv_card','=',$nmv_card];
        $where_card[] = ['bind_id','=',$pigcms_id];
        // 获取非机动车卡号信息
        $cardInfo = $village_nmv_card->getCardInfo($where_card,'id,nmv_card,expiration_time');
        if(empty($cardInfo)){
            $cardInfo = [
                'id' => 0,
                'nmv_card' => $nmv_card,
                'expiration_time' => '暂无'
            ];
        }else{
            $cardInfo['expiration_time'] = !empty($cardInfo['expiration_time']) ? date("Y-m-d",$cardInfo['expiration_time']) : '暂无';
        }

        $where_charge = [];
        $where_charge[] = ['is_del','=',0];
        $where_charge[] = ['status','=',1];
        $where_charge[] = ['village_id','=',$village_id];
        $where_charge_single = $where_charge;
        if(!empty($rule_id)){
            $where_charge[] = ['id','=',$rule_id];
        }
        $field_charge = 'id,nmv_charge_name,type,price';
        // 获取收费规则
        $chargeList = $village_nmv_charge->getChargeList($where_charge,$field_charge);
        // 获取每日收费标准金额
        $where_charge_single[] = ['type','=',1];
        $chargeInfo = $village_nmv_charge->getChargeInfo($where_charge_single,$field_charge);
        foreach ($chargeList as &$value){
            if($cardInfo['expiration_time'] == '暂无' || strtotime($cardInfo['expiration_time'].' 23:59:59') < time()){
                $expiration_time = strtotime('-1 day');
            }else{
                $expiration_time = strtotime($cardInfo['expiration_time']);
            }
            if($value['type'] == 1){ // 每天
                $value['expect_expiration_time'] = date("Y-m-d 23:59:59", strtotime("+1 day",$expiration_time));
            }elseif ($value['type'] == 2){ // 每月
                if(cfg('open_natural_month') == 1){ // 自然月
                    $value['expect_expiration_time'] = date("Y-m-t 23:59:59", strtotime('+1 month',$expiration_time));
                    // 如果过期时间不是月底（最后一天）
                    if(date("d",$expiration_time) != date('t',$expiration_time)){
                        // 收费金额按每日收费标准计算差额
                        $value['price'] = round(((date('t',$expiration_time)-date("d",$expiration_time))*$chargeInfo['price'])+$value['price'],2);
                    }
                }else{
                    $value['expect_expiration_time'] = date("Y-m-d 23:59:59", strtotime("+1 month",$expiration_time));
                }
            }else{ // 每年
                $value['expect_expiration_time'] = date("Y-m-d 23:59:59", strtotime("+1 year",$expiration_time));
            }
        }
        return ['code' => 0,'cardInfo' => $cardInfo,'chargeInfo' => $chargeList];
    }

    /**
     * 房间下的所有缴费记录列表
     * @param $pigcms_id
     * @param $fieldOrder
     * @param $page
     * @param $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function nmvPaymentRecord($pigcms_id,$fieldOrder,$page,$limit){
        $village_user_bind = new HouseVillageUserBind();
        $village_nmv_card = new HouseVillageNmvCard();
        $village_nmv_charge = new HouseVillageNmvCharge();

        $where_village = [];
        $where_village[] = ['a.pigcms_id','=',$pigcms_id];
        $fieldVillage = 'v.village_name,a.village_id';
        // 获取小区
        $villageInfo = $village_user_bind->getVillageUserBind($where_village,$fieldVillage);

        $where = [];
        $where[] = ['parent_id','=',$pigcms_id];
        $field = 'pigcms_id';
        // 获取 user_bind id
        $list = $village_user_bind->getList($where,$field);
        $bind = [];
        if($list && !$list->isEmpty()){
            $bind = array_column($list->toArray(),'pigcms_id');
        }
        $bind[] = $pigcms_id;

        $whereOrder = [];
        $whereOrder[] = ['pay_bind_id','in',$bind];
        $whereOrder[] = ['is_paid','=',1];
        $whereOrder[] = ['order_type','=','non_motor_vehicle'];
        // 获取缴费订单列表
        $new_pay_order = new HouseNewPayOrder();
        $list = $new_pay_order->getOrderSingleList($whereOrder, $fieldOrder, $page, $limit);
        if(!empty($list['list'])){
            foreach ($list['list'] as $key => &$value){
                // 小区
                $value['village_name'] = $villageInfo['village_name'];
                // 非机动车卡号
                $cardInfo = $village_nmv_card->getCardInfo([['id','=',$value['position_id']]],'nmv_card');
                if(empty($cardInfo)){
                    unset($list['list'][$key]);
                    continue;
                }
                $value['nmv_card'] = $cardInfo['nmv_card'];
                // 缴费规则类型
                $cardInfo = $village_nmv_charge->getChargeInfo([['id','=',$value['rule_id']],['village_id','=',$villageInfo['village_id']]],'type');
                $value['type'] = $cardInfo['type'];
                $value['log'] = [
                    ['title' => 'IC卡号','value' => $value['nmv_card']],
                    ['title' => '支付类型','value' => ($value['type'] == 1) ? '每日' : (($value['type'] == 2) ? '每月' : '每年')],
                    ['title' => '支付金额','value' => $value['pay_money']],
                    ['title' => '支付时间','value' => date("Y-m-d H:i:s",$value['pay_time'])],
                    ['title' => '支付方式','value' => ($value['pay_type'] == 4) ? '线上支付' : '其他'],
                ];
            }
            return ['code' => 0,'msg' => '缴费记录','data' => $list];
        }else{
            return ['code' => 0,'msg' => '暂无缴费记录','data' => $list];
        }
    }

    /**
     * 下发非机动车卡号到设备
     * @param $nmv_card_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sendNmvToDevice($nmv_card_id,$opreatype = 1){
        $nmv_card = new HouseVillageNmvCard();
        $villageConfig = new HouseVillageThirdConfig();
        $face_device = new HouseFaceDevice();

        $data = [];
        $where = [];
        $where[] = ['c.id','=',$nmv_card_id];
        // 获取非机动车卡号
        $info = $nmv_card->getNmvInfo($where,'c.id,c.expiration_time,c.bind_id,c.nmv_card,b.third_id,b.village_id,b.uid,b.type,b.name,b.phone,b.pigcms_id');
        if(empty($info) || !isset($info['id'])){
            fdump_api(['msg'=>'非机动车下发错误','$nmv_card_id'=>$nmv_card_id,'$opreatype' => $opreatype, '$info'=> $info],'duopu/sendNmvToDevicelog',1);
            return false;
        }
        $db_house_face_img = new HouseFaceImg();
        $wordencryption=new wordencryption();
        $whereConfig = [];
        $whereConfig[] = ['village_id','=',$info['village_id']];
        // 获取配置信息
        $thirdInfo = $villageConfig->getCloudIntercomConfig($whereConfig,'village_num');

        list($msec, $sec) = explode(' ', microtime());
        $msectime = (int)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        $face_img=$db_house_face_img->getOne([
            ['uid', '=', $info['uid']],
            ['status','in',[0,3]],
            ['img_url', '<>', '']
        ],'id,img_url,status');
        $img_url='';
        if ($face_img && !$face_img->isEmpty()) {
            $img_url=$face_img['img_url'];
            if (3==$face_img['status']) {
                $img_url = $wordencryption->text_decrypt($img_url);
            }
            try {
                $config_site_url = cfg('site_url');
                $sImageService = new ImageService();
                $equalThumbArr = $sImageService->equalThumb($img_url, $config_site_url);
            } catch (\Exception $e) {
                $equalThumbArr = [];
                fdump_api(['执行错误' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'img_url' => $img_url], 'duopu/errThumbImageLog', 1);
            }
            $thumb_url = isset($equalThumbArr['thumb_url']) && trim($equalThumbArr['thumb_url']) ? trim($equalThumbArr['thumb_url']) : '';
            fdump_api(['msg' => '图片拼接', 'img_url' => $img_url, 'thumb_url' => $thumb_url], 'duopu/authidSourceLog', 1);
            if ($thumb_url) {
                $img_url = $thumb_url;
            } else {
                $img_url = thumb_img($img_url,1920,1080,'', true);
            }
        }
        $xname='';
        if(!empty($info['name'])){
            $xname=$info['name'];
        }elseif (!empty($info['phone'])){
            $xname=$info['phone'];
        }else{
            $xname=$info['pigcms_id'];
        }
        $where_device = [];
        $where_device[] = ['village_id','=',$info['village_id']];
        $where_device[] = ['thirdDeviceTypeStr','=',2];
        $where_device[] = ['device_type','=',29];
        $where_device[] = ['is_del','=',0];
        $end_time = strtotime('+10 year')*1000;
        // 获取非机动车通道列表
        $deviceList = $face_device->getList($where_device,'device_sn,device_id',1);
        if($deviceList && !$deviceList->isEmpty()){
            if (!$info['expiration_time'] || $info['expiration_time']<0) {
                // 没有有效期就取当前时间
                $info['expiration_time'] = time()+5;
            }
            if($info['type']==4){
                $info['expiration_time']=$end_time;
            }
            $face_user_bind_device = new FaceUserBindDevice();
            foreach ($deviceList->toArray() as $value){
                $whereSource = [];
                $whereSource[] = ['bind_id', '=', $info['bind_id']];
                $whereSource[] = ['device_type', '=', 29];
                $whereSource[] = ['personID', '<>', ''];
                $whereSource[] = ['device_id', '<>', $value['device_id']];
                $infoSource = $face_user_bind_device->getOneOrder($whereSource, 'bind_id,device_id,personID', ['last_time' => 'desc', 'id' => 'desc']);
               // fdump_api(['$infoSource' => $infoSource, '$whereSource' => $whereSource, '$value' => $value], 'duopu/authidSourceLog',1);
                if ($infoSource && isset($infoSource['personID'])) {
                    $authid = $infoSource['personID'];
                } else {
                    $authid = '4'.(100000+$value['device_id']).$info['id'];
                }
                
                $temp = [
                    'authid' => $authid,
                    'code' => $value['device_sn'],
                    'RYID' => isset($info['third_id'])&&$info['third_id']?$info['third_id']:$authid,
                    'xqcode' => $thirdInfo['village_num'],
                    'opreatype' => $opreatype,
                    'wgcard' => $this->handleNmvCard($info['nmv_card']),
                    'valiendfrom' => $msectime,
                    'valiend' => intval($info['expiration_time']*1000) // 毫秒
                ];
                $temp['persontype']=2;
                $temp['name']=$xname;
                $temp['faceurl'] = $img_url;
                try {
                    $whereBind = [];
                    $whereBind[] = ['bind_id','=',$info['bind_id']];
                    $whereBind[] = ['personID','=',$authid];
                    $whereBind[] = ['device_id','=',$value['device_id']];
                    $whereBind[] = ['group_id','=',$info['village_id']];
                    $whereBind[] = ['device_type','=',29];
                    $faceUserBindDeviceInfo = $face_user_bind_device->getOneOrder($whereBind,'id', ['id'=>'desc']);
                    if ($faceUserBindDeviceInfo && isset($faceUserBindDeviceInfo['id']) && $faceUserBindDeviceInfo['id']) {
                        // 添加过的重复数据变更信息人脸绑定设备信息表
                        $saveData = [
                            'last_time' => time(),
                        ];
                        $face_user_bind_device->saveData($whereBind,$saveData);
                        $idFace = $faceUserBindDeviceInfo['id'];
                    } else {
                        // 保存人脸绑定设备信息表
                        $face_user_bind = [
                            'bind_id' => $info['bind_id'],
                            'person_id' => $info['id'],
                            'personID' => $authid,
                            'group_id' => $info['village_id'],
                            'device_id' => $value['device_id'],
                            'device_type' => 29,
                            'add_time' => time(),
                        ];
                        $idFace = $face_user_bind_device->addData($face_user_bind);
                    }
                } catch (\Exception $e) {
                    fdump_api(['msg'=>'错误','$nmv_card_id'=>$nmv_card_id,'$temp' => $temp, 'message'=> $e->getMessage()],'duopu/sendNmvToDevicelog',1);
                }
                fdump_api(['msg'=>'非机动车卡号下发','$nmv_card_id'=>$nmv_card_id,'$temp' => $temp, '$info'=> $info],'duopu/sendNmvToDevicelog',1);
                // 发送人员出入审核结果反馈
                $dopu = new dopuapi($info['village_id']);
                $data[] = $dopu->gateauth($temp);

                if (isset($idFace) && $idFace) {
                    $whereBindFace = [
                        'id' => $idFace,
                    ];
                    $saveDataFace = [
                        'last_time'   => time(),
                        'person_text' => json_encode($temp, JSON_UNESCAPED_UNICODE),
                    ];
                    $face_user_bind_device->saveData($whereBindFace,$saveDataFace);
                }
            }
        }
        return $data;
    }

    /**
     * 下发门禁卡号到设备
     * @param $nmv_card_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sendIcCardToDevice($ic_card,$opreatype = 1){

        $villageConfig = new HouseVillageThirdConfig();
        $face_device = new HouseFaceDevice();
        $village_user_bind = new HouseVillageUserBind();

        $where = [];
        $where[] = ['ic_card','=',$ic_card];
        // 获取 user_bind 信息
        $info = $village_user_bind->getOne($where,'pigcms_id as id,ic_card,nmv_card,uid,third_id,village_id');
        if(!$info || $info->isEmpty()){
            return false;
        }else{
            $info = $info->toArray();
        }
        fdump_api(['$info' => $info],'dopu/sendIcCardToDevice',1);
        $info['expiration_time'] = strtotime('+10 year');
        $data = [];
        $whereConfig = [];
        $whereConfig[] = ['village_id','=',$info['village_id']];
        // 获取配置信息
        $thirdInfo = $villageConfig->getCloudIntercomConfig($whereConfig,'village_num');

        list($msec, $sec) = explode(' ', microtime());
        $msectime = (int)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);

        $where_device = [];
        $where_device[] = ['village_id','=',$info['village_id']];
        $where_device[] = ['thirdDeviceTypeStr','=',1];
        $where_device[] = ['device_type','=',29];
        $where_device[] = ['is_del','=',0];
        // 获取非机动车通道列表
        $deviceList = $face_device->getList($where_device,'device_sn,device_id',1);
        fdump_api(['$deviceList' => $deviceList->toArray()],'dopu/sendIcCardToDevice',1);
        if($deviceList && !$deviceList->isEmpty()){
            $face_user_bind_device = new FaceUserBindDevice();
            foreach ($deviceList->toArray() as $value){
                $whereSource = [];
                $whereSource[] = ['bind_id', '=', $info['id']];
                $whereSource[] = ['device_type', '=', 29];
                $whereSource[] = ['personID', '<>', ''];
                $whereSource[] = ['device_id', '<>', $value['device_id']];
                $infoSource = $face_user_bind_device->getOneOrder($whereSource, 'bind_id,device_id,personID', ['last_time' => 'desc', 'id' => 'desc']);
                fdump_api(['$infoSource' => $infoSource, '$whereSource' => $whereSource, '$value' => $value], 'duopu/authidSourceLog');
                if ($infoSource && isset($infoSource['personID'])) {
                    $authid = $infoSource['personID'];
                } else {
                    $authid = '2'.(100000+$value['device_id']).$info['id'];
                }
                $temp = [
                    'authid' => $authid,
                    'code' => $value['device_sn'],
                    'RYID' => $info['third_id'],
                    'xqcode' => $thirdInfo['village_num'],
                    'opreatype' => $opreatype,
                    'wgcard' => $info['ic_card'],
                    'valiendfrom' => $msectime,
                    'valiend' => $info['expiration_time']*1000 // 毫秒
                ];
                // 发送人员出入审核结果反馈
                $dopu = new dopuapi($info['village_id']);
                $data[] = $dopu->gateauth($temp);
            }
        }
        return $data;
    }
    
    /**
     * 远程开门
     * @param $village_id
     * @param $code
     * @return array|mixed
     */
    public function openDoor($village_id,$code){
        if(empty($code)){
            return ['code' => 1001,'msg' => '缺少参数'];
        }
        $param = [];
        $param['code'] = $code;
        // 发送人员出入审核结果反馈
        $dopu = new dopuapi($village_id);
        $result = $dopu->openDoor($param);
        $data = json_decode($result[1],true);
        return ['code' => (($data['code'] != 0) ? 1003 : 0),'msg' => '第三方设备提示：'.$data['msg'].$this->tip];
    }

    /**
     * 处理第三方推送人员数据地址问题
     * User: zhanghan
     * Date: 2022/1/21
     * Time: 9:12
     * @param $thirdUserInfo
     * @return array
     */
    public function dealThirdAddress($thirdUserInfo){
        $vacancy_info = [];
        // 首先查询下房间
        $address = str_replace('号','',$thirdUserInfo['address']);
        if (!$address && (strpos($thirdUserInfo['third_jzd_dzxz'], (cfg('single_name')?:'栋')) !== false || strpos($thirdUserInfo['third_jzd_dzxz'], '楼') !== false)) {
            $address_arr = explode('号', $thirdUserInfo['third_jzd_dzxz']);
            if ($address_arr && $address_arr[count($address_arr)-2]) {
                $address = $address_arr[count($address_arr)-2];
            }else{
                $address = '';
            }
        }
        if(!empty($address) && (strpos($address, (cfg('single_name')?:'栋')) !== false || strpos($address, '楼') !== false)){
            // 1栋1单元2楼1号
            $address_txt = str_replace((cfg('single_name')?:'栋'), '-', $address);
            $address_txt = str_replace('单元', '-', $address_txt);
            $address_txt = str_replace('楼', '-', $address_txt);
            $address_txt = str_replace('层', '-', $address_txt);
            $address_txt = str_replace('号', '-', $address_txt);
            $address_arr = explode('-', $address_txt);
            $address_arr = array_values(array_filter($address_arr));
            $single_name = trim($address_arr[0],'/');
            $floor_name = $address_arr[1];
            $layer_name = $address_arr[2];
            $room = $address_arr[3];
            $roomField = 'a.pigcms_id,a.floor_id,a.single_id,a.housesize,a.layer,a.room,a.layer_id,a.usernum';
            // 通过小区楼栋单元楼层房间名称获取参数
            $vacancy_info = $this->roomDeal($single_name,$floor_name,$layer_name,$room,$thirdUserInfo['village_id'],$roomField);
            $vacancy_info['single_name'] = $single_name;
            $vacancy_info['floor_name'] = $floor_name;
            $vacancy_info['layer_name'] = $layer_name;
            $vacancy_info['room'] = $room;
        }
        return $vacancy_info;
    }

    public function handleNmvCard($nmv_card) {
        if (!$nmv_card) {
            return '';
        }
        if (strlen($nmv_card)==9) {
            $nmv_card = '0'.$nmv_card;
        } elseif (strlen($nmv_card)==8) {
           // 8位长度不处理
        } elseif (strlen($nmv_card)==10) {
            // 10位长度不处理
        }
        return $nmv_card;
    }
    
    public function getNmvChargePayInfo($village_id=0){
      
        $returnRes=array('charge_rule'=>array(),'offline_pay'=>array());
        if($village_id<1){
            return $returnRes;
        }
        $where = [];
        $where[] = ['village_id','=',$village_id];
        $where[] = ['is_del','=',0];
        $where[] = ['status','=',1];
        $field = 'id,village_id,nmv_charge_name,type,price';
        $village_nmv_charge = new HouseVillageNmvCharge();
        $list = $village_nmv_charge->getChargeList($where,$field,0,0);
        $charge_rule=array();
        foreach ($list as $value){
            $type_text =$value['nmv_charge_name'];
            $type_text .= '【'.(($value['type']==1) ? '按天' : (($value['type']==2) ? '按月' : '按年'));
            $type_text .='收取'.$value['price'].'元】';
            $tmpArr=array('rule_id'=>$value['id'],'type_text'=>$type_text,'price'=>$value['price']);
            $charge_rule[]=$tmpArr;
        }
        $returnRes['charge_rule']=$charge_rule;
        $serviceHouseVillage = new HouseVillage();
        $whereTmp=array('village_id'=>$village_id);
        $village_info = $serviceHouseVillage->getInfo($whereTmp,'property_id');
        if($village_info && !$village_info->isEmpty()){
            $whereArr=array();
            $whereArr['property_id'] = $village_info['property_id'];
            $whereArr['status'] = 1;
            $db_house_new_offline = new HouseNewOfflinePay();
            $offlinePayObj = $db_house_new_offline->getList($whereArr,'id,name');
            if($offlinePayObj && !$offlinePayObj->isEmpty()){
                $returnRes['offline_pay']=$offlinePayObj->toArray();
            }
        }
        return $returnRes;
    }
    
    //后台线下支付
    public function nmvPcOfflinePay($pdata=array()){
        if(empty($pdata)){
            return false;
        }
        $village_id=$pdata['village_id'];
        $village_user_bind = new HouseVillageUserBind();
        $db_house_new_pay_order = new HouseNewPayOrder();
        $service_new_pay = new NewPayService();
        $role_id=$pdata['role_id'];
        $where = [];
        $where[] = ['a.pigcms_id','=',$pdata['pigcms_id']];
        $where[] = ['a.village_id','=',$village_id];
        $field = 'v.village_name,v.property_id,a.uid,a.name,a.phone,a.vacancy_id,a.nmv_card,a.village_id';
        // 获取小区用户信息
        $villageInfo = $village_user_bind->getVillageUserBind($where,$field);
        if(empty($villageInfo)){
            return ['code' => 1003,'msg' => '参数错误，未查询到用户数据','data' => []];
        }
        $village_nmv_card = new HouseVillageNmvCard();
        $where_card = [];
        $where_card[] = ['nmv_card','=',$pdata['card_no']];
        $where_card[] = ['bind_id','=',$pdata['pigcms_id']];
        // 获取非机动车卡号信息
        $cardInfo = $village_nmv_card->getCardInfo($where_card,'*');
        if(empty($cardInfo)){
            return ['code' => 1003,'msg' => '非机动车卡号错误','data' => []];
        }
        $where_charge = [];
        $where_charge[] = ['is_del','=',0];
        $where_charge[] = ['status','=',1];
        $where_charge[] = ['village_id','=',$village_id];
        $where_charge[] = ['id','=',$pdata['rule_id']];
        
        $village_nmv_charge = new HouseVillageNmvCharge();
        // 获取收费规则
        $chargeInfo = $village_nmv_charge->getChargeInfo($where_charge,'*');
        if(empty($chargeInfo)){
            return ['code' => 1003,'msg' => '电动车收费规则错误','data' => []];
        }
        $pay_momey=0;
        $cycle_num=1;
        if(isset($pdata['cycle_num']) && $pdata['cycle_num']>0){
            $cycle_num=$pdata['cycle_num'];
        }
        $chargeInfo['pay_cycle_num']=$cycle_num;
        $extra_data=array('nmv_charge'=>$chargeInfo);
        $service_start_time=0;
        $service_end_time=0;
        $nowtime=time();
        $service_month_num=0;
        $prepaid_cycle=$cycle_num;
        if($chargeInfo['type']==3){
            //按年
            $service_month_num=$cycle_num*12;
            if($cardInfo['expiration_time']<=$nowtime){
                $start_time= date('Y-m-d').' 00:00:00';
                $service_start_time=strtotime($start_time);
            }else{
                $start_time= date('Y-m-d',$cardInfo['expiration_time']).' 23:59:59';
                $service_start_time=strtotime($start_time)+1;
            }
            $service_start_time=strtotime($start_time);
            $service_end_time=strtotime("+{$cycle_num} year",$service_start_time)-1;
            $prepaid_cycle=$service_month_num; //记录成月 兼容移动端的记录
         }elseif($chargeInfo['type']==2){
            //按月
            $service_month_num=$cycle_num;
            if($cardInfo['expiration_time']<=$nowtime){
                $start_time= date('Y-m-d').' 00:00:00';
                $service_start_time=strtotime($start_time);
            }else{
                $start_time= date('Y-m-d',$cardInfo['expiration_time']).' 23:59:59';
                $service_start_time=strtotime($start_time)+1;
            }
            $service_start_time=strtotime($start_time);
            $service_end_time=strtotime("+{$cycle_num} month",$service_start_time)-1;
        }else{
            $service_month_num=0;
            if($cardInfo['expiration_time']<=$nowtime){
                $start_time= date('Y-m-d').' 00:00:00';
                $service_start_time=strtotime($start_time);
            }else{
                $start_time= date('Y-m-d',$cardInfo['expiration_time']).' 23:59:59';
                $service_start_time=strtotime($start_time)+1;
            }
            $service_end_time=strtotime("+{$cycle_num} day",$service_start_time)-1;
        }

        $pay_momey=$chargeInfo['price']*$cycle_num;
        $pay_momey=round($pay_momey,2);
        $pay_order = [];
        $pay_order['uid'] = $villageInfo['uid'];
        $pay_order['pigcms_id'] = $pdata['pigcms_id'];
        $pay_order['name'] = $villageInfo['name'];
        $pay_order['phone'] = $villageInfo['phone'];
        $pay_order['pay_bind_id'] = $pdata['pigcms_id'];
        $pay_order['pay_type']=2;
        $pay_order['offline_pay_type']=$pdata['offline_pay_type'];
        $pay_order['pay_bind_name'] = $villageInfo['name'];
        $pay_order['pay_bind_phone'] = $villageInfo['phone'];
        $pay_order['order_type'] = 'non_motor_vehicle';  // 缴费类型
        $pay_order['order_name'] = '非机动车停车费';  // 缴费名称
        $pay_order['room_id'] = $villageInfo['vacancy_id']; // 房间id
        $pay_order['position_id'] = $cardInfo['id']; // 车位id 非机动车卡号ID
        $pay_order['property_id'] = $villageInfo['property_id']; // 物业id
        $pay_order['village_id'] = $village_id;  // 小区id
        $pay_order['total_money'] = $pay_momey; // 应支付金额
        $pay_order['modify_money'] = $pay_momey;    // 修改后的金额
        $pay_order['prepare_pay_money'] = $pay_momey;    // 预交费金额
        $pay_order['is_paid'] = 2;
        $pay_order['is_prepare'] = 1;
        $pay_order['prepaid_cycle'] =$prepaid_cycle;    // 预缴周期
        $pay_order['service_month_num'] =$service_month_num;    // 缴费周期
        $pay_order['service_start_time'] = $service_start_time;    // 服务开始时间
        $pay_order['service_end_time'] = $service_end_time;    // 服务结束时间
        $pay_order['diy_type'] = 4;    // 缴费周期
        $pay_order['rule_id'] = $chargeInfo['id'];    // 消费标准
        $pay_order['project_id'] = 0;
        $pay_order['order_no'] = '';    // 订单编号
        $pay_order['late_payment_money']=0;
        $pay_order['add_time'] = time();
        $pay_order['update_time'] = time();
        $pay_order['extra_data']=json_encode($extra_data,JSON_UNESCAPED_UNICODE);
        $id = $db_house_new_pay_order->addOne($pay_order);
        if($id){
            $pay_order['order_id'] = $id;
            $param = [];
            $param['pay_money'] = $pay_momey;//待支付金额
            $param['deposit_money'] = '';//押金抵扣金额
            $param['deposit_type'] = '';//是否开启押金抵扣
            $data=$service_new_pay->goPay([$pay_order],$village_id,2,$pdata['offline_pay_type'],'','',array(),$role_id,$param);
            return $data;
        }else{
            return ['code' => 1003,'msg' => '支付失败请重试！','data' => []];
        }
    }
    
    /**
     * 一键同步所有.
     */
    public function deviceAllSyncPer($params)
    {
        fdump_api(['msg' => '发起一键同步', 'params' => $params], 'duopu/deviceAllSyncPerLog');
        $device_id = isset($params['device_id']) && $params['device_id'] ? intval($params['device_id']) : 0;
        $device_sn = isset($params['device_sn']) && $params['device_sn'] ? trim($params['device_sn']) : '';
        if (!$device_id && !$device_sn) {
            fdump_api(['msg' => '发起一键同步-少参', 'device_sn' => $device_sn, 'device_id' => $device_id], 'duopu/deviceAllSyncPerLog', 1);
            return ['code' => 1001, 'message' => '缺少必要参数'];
        }
        $params['handleType'] = 'handleDeviceAllSyncPer';
        $job_id = $this->traitCommonDuoPu($params);
        fdump_api(['msg' => '发起一键同步-队列', 'job_id' => $job_id, 'params' => $params], 'duopu/deviceAllSyncPerLog', 1);
        if ($job_id == -1) {
            return ['code' => 1001, 'message' => '任务正在等候执行'];
        }
        if ($job_id) {
            $data = ['one_click_sync_time' => time()];
            (new HouseVillageDeviceCmdService())->updateSyncTime($device_id, $data);
        }
        return $job_id;
    }

    public function jobToMethod($params)
    {
        fdump_api(['msg' => '发起一键同步-jobToMethod', 'params' => $params], 'duopu/deviceAllSyncPerLog', 1);
        $handleType = isset($params['handleType']) ? trim($params['handleType']) : '';
        switch ($handleType) {
            case 'handleDeviceAllSyncPer':
                $this->handleDeviceAllSyncPer($params);
                break;
            case 'sendThirdUserToDeviceDeal':
                $village_id = isset($params['village_id']) && $params['village_id'] ? intval($params['village_id']) : 0;
                $device_sn = isset($params['device_sn']) && $params['device_sn'] ? trim($params['device_sn']) : '';
                $thirdUser = isset($params['thirdUser']) && $params['thirdUser'] ? $params['thirdUser'] : [];
                $operation = isset($params['operation']) && $params['operation'] ? $params['operation'] : 'add';
                $source = isset($params['source']) && $params['source'] ? $params['source'] : '';
                $this->sendThirdUserToDeviceDeal($village_id, $device_sn, $thirdUser, $operation, $source);
                break;
        }
        return true;
    }

    /**
     * 设备触发一键同步所有.
     */
    public function handleDeviceAllSyncPer($params)
    {
        $device_id = isset($params['device_id']) && $params['device_id'] ? intval($params['device_id']) : 0;
        $device_sn = isset($params['device_sn']) && $params['device_sn'] ? trim($params['device_sn']) : '';
        $village_id = isset($params['village_id']) && $params['village_id'] ? intval($params['village_id']) : 0;
        $device_type = DuoPuConst::DUO_PU_DEVICE_TYPE;
        fdump_api(['msg' => '一键同步', 'params' => $params, 'device_type' => $device_type], 'duopu/handleDeviceAllSyncPerLog');
        if (!$device_id || !$device_sn) {
            return ['code' => 1001, 'message' => '缺少必要参数'];
        }
        $whereDevice = ['is_del' => 0];
        $device_id && $whereDevice['device_id'] = $device_id;
        $device_sn && $whereDevice['device_sn'] = $device_sn;
        $village_id && $whereDevice['village_id'] = $village_id;
        $whereDevice['device_type'] = $device_type;
        $field = 'device_id, device_sn, village_id, floor_id, public_area_id, device_type';
        $deviceInfo = (new HouseFaceDevice())->getOne($whereDevice, $field);
        if ($deviceInfo && !is_array($deviceInfo)) {
            $deviceInfo = $deviceInfo->toArray();
        }
        fdump_api(['msg' => '一键同步-设备信息', 'whereDevice' => $whereDevice, 'deviceInfo' => $deviceInfo], 'duopu/handleDeviceAllSyncPerLog', 1);
        if (empty($deviceInfo)) {
            return ['code'    => 1001, 'message' => '对应设备不存在'];
        }
        !$device_id && $device_id = $deviceInfo['device_id'];
        !$device_sn && $device_sn = $deviceInfo['device_sn'];
        !$village_id && $village_id = $deviceInfo['village_id'];
        $whereUserBind = [];
        $village_id && $whereUserBind[] = ['village_id', '=', $village_id];
        $whereUserBind[] = ['status', '=', 1];
        $whereUserBind[] = ['type', '<', 5];//只同步 0 1 2 3 4 
        
        $floor_id = isset($deviceInfo['floor_id']) ? $deviceInfo['floor_id'] : 0;
        if ($floor_id != -1 && $floor_id != 0 && $floor_id != '0' && $floor_id) {
            $floorIds = explode(',', strval($floor_id));
            $whereUserBind[] = ['floor_id', 'in', $floorIds];
        }
        $userColumn = (new HouseVillageUserBind())->getColumn($whereUserBind, 'pigcms_id');
        fdump_api(['msg' => '一键同步-设备信息', 'whereUserBind' => $whereUserBind, 'userColumn' => $userColumn], 'duopu/handleDeviceAllSyncPerLog', 1);
        if (empty($userColumn)) {
            return ['code' => 1001, 'message' => '缺少同步对象'];
        }
        $jobArray = [];
        $userColumnLength = count($userColumn);
        $sliceOffset = 0;
        $sliceLength = 10;
        $time = 0;
        do {
            $thirdUser = array_slice($userColumn, $sliceOffset, $sliceLength);
            $jobParam = [
                'handleType' => 'sendThirdUserToDeviceDeal', 'village_id' => $village_id, 'device_sn' => $device_sn,
                'thirdUser' => $thirdUser, 'operation' => 'add', 'source' => 'house_village',
            ];
            $job_id = $this->traitCommonDuoPu($jobParam, $time);
            $time += 1;
            $jobArray[] = ['job_id' => $job_id, 'jobParam' => $jobParam];
            $sliceOffset += 10;
        } while ($userColumnLength >= $sliceOffset);
        fdump_api(['msg' => '一键同步-队列信息', 'userColumn' => $userColumn, 'jobArray' => $jobArray], 'duopu/handleDeviceAllSyncPerLog', 1);
        return $jobArray;
    }
}