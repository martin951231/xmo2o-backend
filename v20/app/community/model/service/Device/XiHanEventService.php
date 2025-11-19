<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      西函门禁事件
 */

namespace app\community\model\service\Device;

use app\common\model\service\config\ConfigCustomizationService;
use app\common\model\service\image\ImageService;
use app\community\model\db\Device\HouseDeviceQrcode;
use app\community\model\db\Device\HouseVillageUserBindIccard;
use app\community\model\db\HouseCameraTip;
use app\community\model\db\HouseFaceDevice;
use app\community\model\db\HouseFaceImg;
use app\community\model\db\HouseUserLog;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\FaceUserBindDevice;
use app\community\model\db\HouseFaceDeviceCmd;
use app\community\model\db\HouseVillageVisitor;
use app\community\model\db\HouseVillageVisitorPass;
use app\community\model\service\HouseFaceDeviceService;
use app\community\model\service\HouseVillageUserVacancyService;
use app\community\model\service\OwnerService;
use app\community\model\service\VisitorInviteService;
use app\consts\DeviceConst;
use app\consts\DeviceQrcodeConst;
use app\traits\XiHanFaceDeviceTraits;
use app\traits\house\DeviceTraits;
use face\wordencryption;
use file_handle\FileHandle;

class XiHanEventService
{
    use XiHanFaceDeviceTraits,DeviceTraits;

    public $passResultArr = [
        0 => '未知错误', 1 => '通行成功', 2 => '访客时段不符', 3 => '未配置权限组', 4 => '未配置时段', 5 => '时段检验失败', 8 => '通行操作超时', 
        9 => '黑名单', 10 => '无效卡', 11 => '通行时段不符', 12 => '通行方式不符', 14 => '门处常开状态', 15 => '门处常闭状态', 16 => '陌生人', 
        17 => '通行成功（未佩戴安全帽）', 18 => '通行失败（未佩戴安全帽）', 19 => '无匹配卡', 202 => '刷卡次数已达上限',
    ];

    public function handlePassResultMsg($passResult)
    {
        $passResult = intval($passResult) ?? 0;
        return $this->passResultArr[$passResult] ?? '';
    }

    public function handleUserLogFrom($code)
    {
        // 1-人脸识别门禁 2-用户操作开门 3-IC卡开门 6-摄像机抓拍  21-蓝牙开门记录  31-二维码开门   51-高空抛物
        $log_from = 1;
        switch ($code) {
            case 'EventFaceRecognizeCutout':
                // 事件名称：人脸识别抠图
            case 'EventFaceAndIDCardsRecognize':
                // 事件名称：人证比对事件
            case 'EventFaceRecognizeWhole':
                // 事件名称：人脸识别背景大图
            case 'EventFaceSnapWhole':
                // 事件名称：人脸抓拍背景大图
            case 'EventFaceSnapCutout':
                // 事件名称：人脸抓拍抠图
                $log_from = 1;
                break;
            case 'EventCardDetectSnapshot':
                // 事件名称：卡检测快照
                $log_from = 3;
                break;
        }
        return $log_from;
    }

    public function handleAccessControllerEvent($params)
    {
        $events = $params['Events'] ?? [];
        if (!empty($events) && is_array($events) && isset($events[0]) && $events[0]) {
            $event = $events[0];
        }
        if (empty($events)) {
            $event = $events;
        }
        if (empty($event)) {
            fdump_api(['msg' => '事件空置', 'line' => __LINE__, 'params' => $params], 'xiHan/errEventNotificationLog');
            return [];
        }
        $log_detail = $event;
        $houseUserLogParams = [];
        $picID = $params['PicID'] ?? '';
        $channel = $params['Channel'] ?? '';
        $sendTimes = $params['SendTimes'] ?? '';
        $transfer = $params['Transfer'] ?? '';

        $accessInfo = $event['AccessInfo'] ?? [];
        $passResult = $accessInfo['PassResult'] ?? 0;
        $passResultMsg = $this->handlePassResultMsg($passResult);
        $code = $event['Code'] ?? 0;
        if (!isset($params['Channel']) && isset($event['Channel'])) {
            $channel = $event['Channel'] ?? '';
        }
        $commInfo = $event['CommInfo'] ?? [];
        if (empty($commInfo)) {
            $commInfo = $params['CommInfo'] ?? [];
        }
        $machineName = $commInfo['MachineName'] ?? '';
        $serialNo = $commInfo['SerialNo'] ?? '';
        !$serialNo && $serialNo = $params['SerialNo'] ?? '';
        if (empty($serialNo)) {
            fdump_api(['msg' => '设备序号不能为空', 'line' => __LINE__, 'params' => $params], 'xiHan/errEventNotificationLog');
            return [];
        }
        $log_detail['PicID'] = $picID;
        $log_detail['Channel'] = $channel;
        $log_detail['SendTimes'] = $sendTimes;
        $log_detail['Transfer'] = $transfer;

        $nowTime = time();
        $db_house_face_device = new HouseFaceDevice();
        $whereFaceDevice = [];
        $whereFaceDevice[] = ['device_sn', '=', $serialNo];
        $whereFaceDevice[] = ['is_del', '=', 0];
        $faceDeviceInfo = $db_house_face_device->getOne($whereFaceDevice);
        if ($faceDeviceInfo && !is_array($faceDeviceInfo)) {
            $faceDeviceInfo = $faceDeviceInfo->toArray();
        }
        $device_id = $faceDeviceInfo['device_id'] ?? 0;
        $village_id = $faceDeviceInfo['village_id'] ?? 0;
        $direction_type = isset($faceDeviceInfo['device_direction']) && $faceDeviceInfo['device_direction'] == 0 ? 1 : 2;
        $db_house_face_device->saveData($whereFaceDevice, ['last_open_time' => $nowTime]);

        $db_house_user_log = new HouseUserLog();
        $whereUserLog = [];
        if ($device_id) {
            $whereUserLog[] = ['device_id', '=', $device_id];
        } else {
            $whereUserLog[] = ['device_sn', '=', $serialNo];
        }
        $whereUserLog[] = ['eventId', '=', $picID];
        $logInfo = $db_house_user_log->getFind($whereUserLog);
        if (isset($logInfo['log_id']) && $logInfo['log_id'] > 0) {
            fdump_api(['msg' => '时间信息重复了', 'line' => __LINE__, 'params' => $params], 'xiHan/errEventNotificationLog');
            return [];
        }
        $card = $event['Card'] ?? [];
        $cardNo = $card['CardNo'] ?? '';
        $cardCode = $card['Code'] ?? '';
        $cardNumberType = $card['Type'] ?? 0;
        $imageFile = $event['Image'] ?? '';
        $timestamp = $event['Timestamp'] ?? '';
        $timeZone = $event['TimeZone'] ?? '';
        if (!$timestamp && strpos($timeZone, '+08:00')) {
            $UTC = $event['UTC'] ?? '';
            if ($UTC) {
                $timestamp = $UTC - (8 * 3600);
            }
        }
        if (!$timestamp) {
            $timestamp = $nowTime;
        }
        $log_time = $timestamp ?? $nowTime;
        $log_from = $this->handleUserLogFrom($code);

        $object = $event['Object'] ?? [];
        $temperature = $object['Temperature'] ?? 0;

        $recognizeResults = $event['RecognizeResults'] ?? [];
        $pigcmsId = 0;
        $personID = 0;
        $person_name = '';
        if (!empty($recognizeResults) && is_array($recognizeResults) && isset($recognizeResults[0]) && $recognizeResults[0]) {
            $recognizeResult = $recognizeResults[0];
            $personInfo = $recognizeResult['PersonInfo'] ?? [];
            $pigcmsId = $personInfo['ID'] ?? 0;
            $personID = $personInfo['PersonID'] ?? 0;
            $person_name = $personInfo['Name'] ?? '';
        }
        $db_house_village_user_bind = new HouseVillageUserBind();
        $whereUserBind = [];
        if ($village_id > 0) {
            $whereUserBind[] = ['village_id', '=', $village_id];
        }
        // todo ic卡通过新的卡号表定位对应人员
        if ($pigcmsId) {
            $whereUserBind[] = ['pigcms_id', '=', $pigcmsId];
        } elseif ($cardCode) {
            $whereUserBind[] = ['pigcms_id', '=', $cardCode];
        } else {
            $whereUserBind[] = ['ic_card', '=', $cardNo];
        }
        $whereUserBind[] = ['status', '=', 1];
        $userBindInfo = $db_house_village_user_bind->getOne($whereUserBind);
        if ($userBindInfo && !is_array($userBindInfo)) {
            $userBindInfo = $userBindInfo->toArray();
        }
        $uid = $userBindInfo['uid'] ?? 0;
        $log_bind_id = $userBindInfo['pigcms_id'] ?? 0;

        $cut_image = '';
        if ($imageFile) {
            $fileHandle   = new FileHandle();
            $ymdHis = date('Ymdhis', $nowTime);
            $ymdString = date('Ymd', $nowTime);
            $rootPath = app()->getRootPath() . '..';
            $faceImgPath = '/upload/face_log/' . $ymdString . '/' . $serialNo . '/';
            $imageSavePath = $rootPath . $faceImgPath;
            $imageFileSaveFileName = $ymdHis . '_face_' . $picID;
           // $flags = FILE_USE_INCLUDE_PATH | FILE_APPEND;
            $toImg = base64_to_img($imageSavePath, $imageFileSaveFileName, $imageFile, 'jpg');
            if ($toImg) {
                $cut_image = $faceImgPath . $imageFileSaveFileName . '.jpg';
                $saveImg = $this->traitSaveImage($toImg, $toImg);
                $is_check_open_oss=$fileHandle->check_open_oss();
                fdump_api(['toImg'=>$toImg,'saveImg' => $saveImg,'cut_image'=>$cut_image,'is_check_open_oss'=>$is_check_open_oss,'imageFile'=>$imageFile], 'xiHan/eventNotificationImg', true);
                if ($is_check_open_oss) {
                    try {
                        $info = $fileHandle->upload('.'.$cut_image);
                        fdump_api(['toImg'=>$toImg,'info' => $info,'cut_image'=>$cut_image], 'xiHan/eventNotificationImg', true);
                        if(isset($info['error']) && !$info['error']){
                            @unlink('.'.$cut_image);
                        }
                    } catch (\Exception $e) {
                        fdump_api(['msg' => 'oss上传大图执行错误', 'message' => $e->getMessage(), 'toImg' => $toImg, 'cut_image' => $cut_image, 'info' => $info], 'xiHan/eventNotificationImg', true);
                    }
                }
                
            }
        }
        $log_detail = unsetParam($log_detail, ['Image']);

        $houseUserLogParams['uid'] = $uid;
        $houseUserLogParams['log_bind_id'] = $log_bind_id;
        $houseUserLogParams['log_name'] = $faceDeviceInfo['device_name'] ?? $machineName;
        $houseUserLogParams['log_from'] = $log_from;
        $houseUserLogParams['log_business_id'] = $village_id;
        $houseUserLogParams['device_id'] = $device_id;
        $houseUserLogParams['log_status'] = $passResult == 1 ? 0 : 1;
        $houseUserLogParams['log_detail'] = json_encode($log_detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $houseUserLogParams['log_time'] = $log_time;
        $houseUserLogParams['eventId'] = $picID;
        $houseUserLogParams['temperature'] = $temperature;
        $houseUserLogParams['deviceChannelId'] = $channel;
        $houseUserLogParams['device_sn'] = $serialNo;
        $houseUserLogParams['person_name'] = $person_name;
        $houseUserLogParams['person_code'] = $pigcmsId;
        $houseUserLogParams['card_number'] = $cardNo;
        $houseUserLogParams['cut_image'] = $cut_image;
        $houseUserLogParams['direction_type'] = $direction_type;
        $houseUserLogParams['card_number_type'] = $cardNumberType;
        $houseUserLogParams['pass_result_code'] = $passResult;
        $houseUserLogParams['pass_result_msg'] = $passResultMsg;
        if  ($cardNumberType == 6) {
            $houseUserLogParams['log_from'] = 31;
        }
        if (isset($userBindInfo['type']) && intval($userBindInfo['type']) == 6 && isset($userBindInfo['pigcms_id']) && $userBindInfo['pigcms_id']) {
            fdump_api(['msg' => '访客通行记录', 'line' => __LINE__, 'userBindInfo' => $userBindInfo], 'xiHan/eventNotificationLog', 1);
            // 二维码开门(访客)
            $db_house_village_visitor = new HouseVillageVisitor();
            $whereVisitor = [];
            $whereVisitor[] = ['visitor_bind_id', '=', $userBindInfo['pigcms_id']];
            $visitorInfo = $db_house_village_visitor->get_one($whereVisitor, 'id, visitor_phone, visit_owner_switch, visit_to_devices');
            $visitorInfoId = isset($visitorInfo['id']) ? intval($visitorInfo['id']) : 0;
            fdump_api(['msg' => '访客通行记录', 'line' => __LINE__, 'visitorInfoId' => $visitorInfoId, 'visitorInfo' => $visitorInfo], 'xiHan/eventNotificationLog', 1);
            if ($visitorInfoId > 0) {
                $dbHouseVillageVisitorPass = new HouseVillageVisitorPass();
                $db_house_village_visitor = new HouseVillageVisitor();
                $addArr = [
                    'visitor_id' => $visitorInfoId, 'village_id' => $village_id, 'user_bind_id' => $userBindInfo['pigcms_id'],
                    'uid' => isset($userBindInfo['uid']) && $userBindInfo['uid'] ? intval($userBindInfo['uid']) : 0,
                    'phone' => isset($visitorInfo['visitor_phone']) && trim($visitorInfo['visitor_phone']) ? trim($visitorInfo['visitor_phone']) : '',
                ];
                $device_name = isset($faceDeviceInfo['device_name']) && $faceDeviceInfo['device_name'] ? $faceDeviceInfo['device_name'] : '';
                // 1-人脸识别门禁 2-用户操作开门 3-IC卡开门 6-摄像机抓拍  21-蓝牙开门记录  31-二维码开门   51-高空抛物
                if ($log_from == 1) {
                    $addArr['uname'] = $device_name . ' 人脸门禁通行';
                } elseif ($log_from == 2) {
                    $addArr['uname'] = $device_name . ' 远程开门门禁通行';
                } elseif ($log_from == 3) {
                    $addArr['uname'] = $device_name . ' IC卡刷卡通行';
                } elseif ($log_from == 31) {
                    $addArr['uname'] = $device_name . ' 二维码门禁通行';
                } else {
                    $addArr['uname'] = $device_name . ' 门禁通行';
                }
                $addArr['addtime'] = $nowTime;
                fdump_api(['msg' => '访客通行记录', 'line' => __LINE__, 'addArr' => $addArr], 'xiHan/eventNotificationLog', 1);
                $dbHouseVillageVisitorPass->add($addArr);
                $dataSave = [
                    'status'    => 5, 'pass_time' => $nowTime,
                ];
                $whereVisitor = ['id' => $visitorInfoId];
                fdump_api(['msg' => '访客通行记录', 'line' => __LINE__, 'whereVisitor' => $whereVisitor, 'dataSave' => $dataSave], 'xiHan/eventNotificationLog', 1);
                $db_house_village_visitor->saveOne($whereVisitor, $dataSave);
            }
        }
        $log_id = $db_house_user_log->addData($houseUserLogParams);
        fdump_api(['msg' => '通行记录', 'line' => __LINE__, 'houseUserLogParams' => $houseUserLogParams, 'log_id' => $log_id], 'xiHan/eventNotificationLog', 1);

        if ($log_id && $log_bind_id) {
            $tip = array(
                'bind_id' => $log_bind_id, 'uid' => $uid, 'type' => 1, 'village_id' => $village_id,
                'camera_sn' => $serialNo, 'city_id' => 0, 'camera_time' => $log_time, 'camera_img' => $cut_image,
                'tip_time' => $nowTime, 'status' => $houseUserLogParams['log_status'],
            );
            (new HouseCameraTip())->addOne($tip);
            fdump_api(['msg' => '通信通知记录', 'line' => __LINE__, 'tip' => $tip], 'xiHan/eventNotificationLog', 1);
        }
        // 处理开门次数
        if ($cardNo && isset($userBindInfo['pigcms_id']) && $userBindInfo['pigcms_id']) {
            $db_house_device_qrcode = new HouseDeviceQrcode();
            $whereQrcode = [];
            $whereQrcode[] = ['device_equipment_type', '=', DeviceConst::DEVICE_TYPE_FACE];
            $whereQrcode[] = ['bind_type', '=', DeviceQrcodeConst::BIND_TYPE_VILLAGE_USER];
            $whereQrcode[] = ['bind_from', '=', DeviceQrcodeConst::BIND_FROM_HOUSE_VILLAGE_USER_BIND];
            $whereQrcode[] = ['bind_id', '=', $userBindInfo['pigcms_id']];
            $whereQrcode[] = ['device_id', '>', 0];
            $whereQrcode[] = ['delete_time', '=', 0];
            $whereQrcode[] = ['device_type', '=', DeviceConst::XI_HAN_DEVICE_TYPE];
            $db_house_device_qrcode->setInc($whereQrcode, 'opened_times', 1);
            fdump_api(['msg' => '处理开门次数', 'line' => __LINE__, 'whereQrcode' => $whereQrcode], 'xiHan/eventNotificationLog', 1);
        }
        return true;
    }


    public function sycBindUserToDevice($village_id = 0, $bind_pigcms_id = 0, $floor_id_str = '', $device_id = 0)
    {
        $dataParam = array('village_id' => $village_id, 'bind_pigcms_id' => $bind_pigcms_id);
        $dataParam['floor_id_str'] = $floor_id_str;
        $dataParam['device_id'] = $device_id;
        $dataParam['handle_type'] = 'sycBindUserToXiHanDevice';
        $retInfo = $this->traitXiHanFaceDevice($dataParam);
        return array('retInfo' => $retInfo);
    }

    public function pushBindUserToXiHanDevice($datas = array())
    {

        $village_id = 0;
        if ($datas && isset($datas['village_id'])) {
            $village_id = intval($datas['village_id']);
        }

        $root = dirname(dirname(dirname(__DIR__)));
        $root = dirname(dirname(dirname($root)));
        $basePath = rtrim($root, '/');
        $_SERVER['DOCUMENT_ROOT'] = $basePath;
        fdump_api(['datas' => $datas, 'basePath' => $basePath], 'xihan/queue_add_person', 1);

        if ($village_id < 1) {
            return true;
        }
        $bind_pigcms_id = 0;
        if ($datas && isset($datas['bind_pigcms_id'])) {
            $bind_pigcms_id = intval($datas['bind_pigcms_id']);
        }
        $floor_id_str = '';
        if ($datas && isset($datas['floor_id_str'])) {
            $floor_id_str = trim($datas['floor_id_str']);
        }
        $device_id = 0;
        if ($datas && isset($datas['device_id'])) {
            $device_id = trim($datas['device_id']);
        }
        $houseVillageUserBind = new HouseVillageUserBind();
        if ($bind_pigcms_id > 0) {
            $whereArr = array();
            $whereArr[] = array('pigcms_id', '=', $bind_pigcms_id);
            $whereArr[] = array('village_id', '=', $village_id);
            $userBind = $houseVillageUserBind->getOne($whereArr, '*');
            if ($userBind && !$userBind->isEmpty()) {
                $userBind = $userBind->toArray();
            }
            if (empty($userBind)) {
                fdump_api(['datas' => $datas, 'err_msg' => '房间人员信息不存在！'], 'xihan/queue_add_person', 1);
                return true;
            }
            $this->xihan_add_person($device_id, $userBind, $bind_pigcms_id, $village_id);
        } else {
            $whereArr = array();
            $whereArr[] = array('village_id', '=', $village_id);
            $whereArr[] = array('status', '<>', 4);
            $whereArr[] = array('name', '<>', '');
            if ($floor_id_str) {
                $floor_id_arr = explode(',', $floor_id_str);
                $whereArr[] = array('floor_id', 'in', $floor_id_arr);
            }
            $fieldStr = 'pigcms_id,village_id,uid,name,phone,floor_id,parent_id,type,vacancy_id,id_card,single_id,layer_id';
            $allDatas = $houseVillageUserBind->getList($whereArr, $fieldStr);
            if ($allDatas && !$allDatas->isEmpty()) {
                $allDatas = $allDatas->toArray();
                foreach ($allDatas as $vv) {
                    $this->xihan_add_person($device_id, $vv, $vv['pigcms_id'], $village_id);
                }
            }
        }
        return true;
    }

    public function xihan_add_person($device_id = 0, $userBind = array(), $bind_pigcms_id = 0, $village_id = 0)
    {
        fdump_api([__LINE__, 'device_id' => $device_id,'userBind'=>$userBind, 'pigcms_id' => $bind_pigcms_id, 'village_id' => $village_id], 'xihan/queue_add_person', 1);
        if ($device_id < 1) {
            return true;
        }
        $houseFaceDevice = new HouseFaceDevice();
        $deviceWhere = array('device_id' => $device_id, 'village_id' => $village_id);
        $infoObj = $houseFaceDevice->getOne($deviceWhere, '*');
        $info = '';
        if ($infoObj && !$infoObj->isEmpty()) {
            $info = $infoObj->toArray();
        }
        if ($info && $info['device_type'] == 31) {
            $wordEncryption = new wordencryption();
            $village_id = $info['village_id'];
            $houseVillageUserBind = new HouseVillageUserBind();
            $face_user_bind_deviceDb = new FaceUserBindDevice();
            $house_face_device_cmdDb = new HouseFaceDeviceCmd();
            $now_time = time();
            $user_bind_arr = array();
            $pigcms_id = $bind_pigcms_id;
            if (empty($userBind) && $bind_pigcms_id > 0) {
                $whereArr = array();
                $whereArr[] = array('pigcms_id', '=', $bind_pigcms_id);
                $whereArr[] = array('village_id', '=', $village_id);
                $userBind = $houseVillageUserBind->getOne($whereArr, '*');
                if ($userBind && !$userBind->isEmpty()) {
                    $userBind = $userBind->toArray();
                }
            }
            if (empty($userBind) || !isset($userBind['pigcms_id'])) {
                fdump_api([__LINE__, 'device_id' => $device_id, 'pigcms_id' => $bind_pigcms_id, 'err_msg' => '房间人员信息不存在！'], 'xihan/queue_add_person', 1);
                return true;
            }
            $pigcms_id = $userBind['pigcms_id'];
            $relatedType = 1;  //1-内部员工,2-访客, 3-黑名单
            //$userBind['type'] 0户主，1租户，3夫妻，4子女，5父母，6朋友，7其他
            if ($userBind['type'] == 0 || $userBind['type'] == 3) {
                $relatedType = 1;
            } else if ($userBind['type'] == 1) {
                $relatedType = 1;
            } else if ($userBind['type'] == 2) {
                $relatedType = 1;
            } else if ($userBind['type'] == 6 || $userBind['type'] == 7) {
                //访客
                $relatedType = 2;
            } else if ($userBind['type'] == 4) {
                //工作人员
                $relatedType = 1;
            }
            $user_bind_arr['bind_id'] = $pigcms_id;
            $user_bind_arr['device_id'] = $device_id;
            $user_bind_arr['device_type'] = 31;
            $user_bind_arr['person_id'] = $pigcms_id;
            $user_bind_arr['add_time'] = $now_time;
            $dbHouseFaceImg = new HouseFaceImg();
            $imgUrlInfo = '';
            $face_id=0;
            $whereArrTmp = array(['bind_pigcms_id', '=', $pigcms_id], ['status', 'in', array(0, 3)]);
            $oneFaceImg = $dbHouseFaceImg->getOne($whereArrTmp, '*');
            if ($oneFaceImg && !$oneFaceImg->isEmpty()) {
                $imgUrlInfo = $oneFaceImg->toArray();
            }
            if (empty($imgUrlInfo) && $userBind['uid'] > 0) {
                $whereArrTmp = array(['uid', '=', $userBind['uid']], ['status', 'in', array(0, 3)]);
                $oneFaceImg = $dbHouseFaceImg->getOne($whereArrTmp, '*');
                if ($oneFaceImg && !$oneFaceImg->isEmpty()) {
                    $imgUrlInfo = $oneFaceImg->toArray();
                }
            }

            $imgUrl = '';
            if ($imgUrlInfo) {
                $face_id=$imgUrlInfo['id'];
                fdump_api([__LINE__, 'device_id' => $device_id, 'pigcms_id' => $pigcms_id, 'imgUrlInfo' => $imgUrlInfo], 'xihan/queue_add_person', 1);
                $imgUrl = $imgUrlInfo['img_url'];
                if (3 == $imgUrlInfo['status']) {
                    // 针对加密图片解密
                    $imgUrl = $wordEncryption->text_decrypt($imgUrl);
                }
                $tmp_img_url = $this->getUserFaceImg($userBind['uid'], $imgUrl, ['width' => 800, 'height' => 650, 'is_not_download_upload' => 1]);

                if (!empty($tmp_img_url)) {
                    $imgUrl = $tmp_img_url;
                } else {
                    $imgUrl = replace_file_domain($imgUrl);
                }
            }
            fdump_api([__LINE__,$imgUrl], 'xihan/queue_add_person', 1);
            $user_bind_arr['face_img'] = $imgUrl;
            $person_text = array();
            $personName = $userBind['name'] ? $userBind['name'] : '';
            $db_house_device_qrcode = new HouseDeviceQrcode();
            $whereQrcode = [];
            $whereQrcode[] = ['device_equipment_type', '=', DeviceConst::DEVICE_TYPE_FACE];
            $whereQrcode[] = ['bind_type', '=', DeviceQrcodeConst::BIND_TYPE_VILLAGE_USER];
            $whereQrcode[] = ['bind_from', '=', DeviceQrcodeConst::BIND_FROM_HOUSE_VILLAGE_USER_BIND];
            $whereQrcode[] = ['bind_id', '=', $pigcms_id];
            $whereQrcode[] = ['device_id', '=', $device_id];
            $whereQrcode[] = ['delete_time', '=', 0];
            $whereQrcode[] = ['device_type', '=', DeviceConst::XI_HAN_DEVICE_TYPE];
            $qrcodeInfo = $db_house_device_qrcode->getOne($whereQrcode, true);
            $opened_times = isset($qrcodeInfo['opened_times']) && $qrcodeInfo['opened_times'] ? intval($qrcodeInfo['opened_times']) : 0;
            $personInfon = array('Type' => $relatedType, 'Code' => strval($pigcms_id), 'GroupName' => "默认权限组", 'Name' => $personName, 'Sex' => 'male', 'Birthday' => '2000-01-01');
            if ($userBind['id_card']) {
                $personInfon['CredentialType'] = 'IC';
                $personInfon['CredentialNo'] = $userBind['id_card'];
                $idCardInfo = idCardAnalysis($userBind['id_card']);
                if ($idCardInfo && is_array($idCardInfo)) {
                    $personInfon['Birthday'] = $idCardInfo['birthday'];
                    $personInfon['Sex'] = $idCardInfo['sex'] == '1' ? 'male' : 'female';
                }
            }
            //$personInfon['Status']=1;
            $personInfon['URL'] = array($imgUrl);
            $personInfon['AccessTimes'] = array();
            $accessTime = array("AccessType" => 1, "TimeSection" => array());
            $stat_time=$now_time-10;
            $accessTime['TimeSection'][] =$stat_time;
            if ($relatedType==2) {
                //访客
                $end_time=$now_time+24*3600;
            }else if($userBind['type'] == 4){
                $end_time=2461420800;  //2048-01-01 
            }else{
                $houseVillageUserVacancyService=new HouseVillageUserVacancyService();
                $propertyServiceTime= $houseVillageUserVacancyService->getRoomEndTime($userBind['vacancy_id'] , $village_id, 0,true,true);
                $end_time = isset($propertyServiceTime['propertyEndTime']) ? $propertyServiceTime['propertyEndTime']:0;
            }
            if ($end_time > $now_time) {
                $accessTime['TimeSection'][] = $end_time;
            } else {
                $accessTime['TimeSection'][] = $now_time;
            }
            if ($relatedType != 2) {
                $personInfon['AccessTimes'][] = $accessTime;
            } else {
                unset($personInfon['AccessTimes']);
                $visitor_bind_id = isset($userBind['pigcms_id']) && $userBind['pigcms_id'] ? $userBind['pigcms_id'] : 0;
                $db_house_village_visitor = new HouseVillageVisitor();
                $where_visitor = ['village_id' => $village_id, 'visitor_bind_id' => $visitor_bind_id];
                $temp_visitor = $db_house_village_visitor->get_one($where_visitor);
                $is_car = isset($temp_visitor['is_car']) && $temp_visitor['is_car'] ? intval($temp_visitor['is_car']) : 0;
                $car_id = isset($temp_visitor['car_id']) && $temp_visitor['car_id'] ? trim($temp_visitor['car_id']) : '';
                $owner_name = isset($temp_visitor['owner_name']) && $temp_visitor['owner_name'] ? trim($temp_visitor['owner_name']) : '';
                $carLicense = '';
                if ($is_car == 1 && $car_id) {
                    $carLicense = $car_id;
                }
                $from = $stat_time;
                if ($end_time > $now_time) {
                    $to = $end_time;
                } else {
                    $to = $now_time;
                }
                $guestInfo = [
                    'Corp' => '', 'Phone' => isset($userBind['phone']) && $userBind['phone'] ? $userBind['phone'] : '',
                    'CarLicense' => $carLicense, 'Partner' => 1, 'Host' => $owner_name,
                    'AccessTime' => [['from' => $from, 'to' => $to]],
                ];
                $personInfon['GuestInfo'] = $guestInfo;
            }
            $cards = [];
            $dbHouseVillageUserBindIccard = new HouseVillageUserBindIccard();
            $whereIcCard = [];
            $whereIcCard[] = ['village_id', '=', $village_id];
            $whereIcCard[] = ['bind_id', '=', $pigcms_id];
            $whereIcCard[] = ['del_time', '<', 1];
            $icCardList = $dbHouseVillageUserBindIccard->getSome($whereIcCard, true, 'type ASC,end_time DESC', 0, 5);
            if ($icCardList && !$icCardList->isEmpty()) {
                $icCardList = $icCardList->toArray();
            }else{
                $icCardList=array();
            }
            fdump_api(['icCardList' => $icCardList, 'whereIcCard' => $whereIcCard, 'sql' => $dbHouseVillageUserBindIccard->getLastSql()], 'xihan/queue_add_person',1);
            $onlyQrCode = 1;
            $hasQrCode = 0;
            if($icCardList){
                foreach ($icCardList as $icCard) {
                    $ic_card_no = isset($icCard['ic_card_no']) && $icCard['ic_card_no'] ? $icCard['ic_card_no'] : '';
                    // 1-普通 2-胁迫 6-二维码
                    $type = isset($icCard['type']) && $icCard['type'] ? intval($icCard['type']) : 1;
                    if($type==6 && $icCard['end_time']<$now_time){
                        //过期的 二维码
                        continue;
                    }
                    if ($type != 6) {
                        $onlyQrCode = 0;
                    } elseif ($hasQrCode == 1) {
                        // 二维码只同步最新的 其他的跳过
                        continue;
                    } else {
                        $hasQrCode = 1;
                    }
                    $effective_start_time = isset($icCard['start_time']) && $icCard['start_time'] ? $icCard['start_time'] : 0;
                    $effective_end_time = isset($icCard['end_time']) && $icCard['end_time'] ? $icCard['end_time'] : 0;
                    $open_times = isset($icCard['open_times']) && $icCard['open_times'] ? intval($icCard['open_times']) : 0;
                    if ($open_times > 0 && $opened_times > 0 && in_array($type, [5, 6])) {
                        $LeftTimes = $open_times - $opened_times;
                    } elseif ($open_times > 0 && in_array($type, [5, 6])) {
                        $LeftTimes = $open_times;
                    } else {
                        $LeftTimes = -1;
                    }
                    if($type<=2 && ($effective_start_time<100 || $effective_end_time<100)){
                        $effective_start_time=$stat_time;
                        $effective_end_time=$end_time> $now_time ? $end_time:$now_time;
                    }
                    $startTimeText = date('Y-m-d', $effective_start_time);
                    $endTimeText = date('Y-m-d', $effective_end_time);
                    $itemCard=[
                        'ID' => $ic_card_no,
                        'Type' => $type,
                        'Validity' => [$startTimeText, $endTimeText],
                        'ValidityTime'=>['00:00:01','23:59:59'],
                    ];
                    if($type == 6){
                        $itemCard['LeftTimes']=$LeftTimes;
                    }
                    $cards[] = $itemCard;
                }
            }
            $user_ic_card = isset($userBind['ic_card']) && trim($userBind['ic_card']) ? trim($userBind['ic_card']) : '';
            if ($onlyQrCode == 1 && $user_ic_card && is_numeric($user_ic_card) && strlen($user_ic_card)<11) {
                $whereIcCardTmp = array(['village_id' ,'=', $village_id]);
                $whereIcCardTmp[]=['bind_id','=',$pigcms_id];
                $whereIcCardTmp[]=['type','in',array(1,2)];
                $haveBindCardObj=$dbHouseVillageUserBindIccard->getOneData($whereIcCardTmp);
                if(empty($haveBindCardObj) || $haveBindCardObj->isEmpty()){
                    $accessStartTimeText = date('Y-m-d H:i:s', $stat_time);
                    if ($end_time > $now_time) {
                        $accessEndTimeText = date('Y-m-d H:i:s', $end_time);
                    } else {
                        $accessEndTimeText = date('Y-m-d H:i:s', $now_time);
                    }
                    $cards[] = [
                        'ID' => $user_ic_card,
                        'Type' => 1,
                        'Validity' => [$accessStartTimeText, $accessEndTimeText],
                        'ValidityTime'=>['00:00:01','23:59:59'],
                    ];
                    $card_data=array('village_id'=>$village_id,'bind_id'=>$pigcms_id,'ic_card_no'=>$user_ic_card);
                    $card_data['type']=1;
                    $card_data['last_time']=$now_time;
                    $card_data['start_time']=$stat_time;
                    $card_data['end_time']=$end_time>$now_time ? $end_time:$now_time;
                    $dbHouseVillageUserBindIccard->addOneData($card_data);
                }
            }
            $personInfon['Cards'] = $cards;
            $personTmp = array('Person' => $personInfon);
            $personInfonArr = array('params' => array($personTmp));
            $user_bind_arr['person_text'] = json_encode($personInfonArr, JSON_UNESCAPED_UNICODE);
            $id = $face_user_bind_deviceDb->addData($user_bind_arr);

            $cmdSynData = array('village_id' => $village_id, 'relate_id' => $id,'bind_id'=> $pigcms_id,'cmd_name' => 'personnelData.savePersons');
            $cmd_data = array('method' => 'personnelData.savePersons', 'params' => array($personTmp));
            $cmdSynData['cmd_data'] = json_encode($cmd_data, JSON_UNESCAPED_UNICODE);
            $cmdSynData['add_time'] =$now_time;
            $cmdSynData['device_id'] = $device_id;
            $cmdSynData['device_sn'] = $info['device_sn'];
            $cmdSynData['device_type'] = DeviceConst::XI_HAN_DEVICE_TYPE;
            $cmdSynData['device_type_str'] = 'xihan';
            $cmdSynData['relate_type'] = 'face_user_bind_device';
            $idd=$house_face_device_cmdDb->addOneData($cmdSynData);
            $faceSyncLogArr=array('pigcms_id'=>$pigcms_id,'face_id'=>$face_id,'device_id'=>$device_id,'device_sn'=>$info['device_sn'],'log_time'=>$now_time);
            $faceSyncLogArr['status']=0;
            $faceSyncLogArr['device_info']=$info;
            $faceSyncLogArr['device_cmd_id']=$idd;
            $ownerService=new OwnerService();
            $ownerService->handleHouseFaceSyncLog($faceSyncLogArr);
        }
        return true;
    }
    
    /**
     * 二维码开门.
     */
    public function getUserOpenCode($params)
    {
        $getXiHanJudge = (new ConfigCustomizationService())->getFaceXiHanDoorSwitch();
        if (!$getXiHanJudge) {
            throw new \think\Exception("不支持此功能");
        }
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $hasXiHan = isset($params['hasXiHan']) && $params['hasXiHan'] ? intval($params['hasXiHan']) : 0;
        if (! isset($params['hasXiHan'])) {
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
        if ($hasXiHan != 1) {
            throw new \think\Exception("当前小区暂时未添加相关人脸门禁设备");
        }
        $uid = isset($params['uid']) && $params['uid'] ? intval($params['uid']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $pigcms_id = isset($params['pigcms_id']) ? intval($params['pigcms_id']) : 0;
        $dbHouseVillageUserBind = (new HouseVillageUserBind());
        $whereUserBind = [
            ['pigcms_id', '=', $pigcms_id]
        ];
        $userBind = $dbHouseVillageUserBind->getOne($whereUserBind);
        $userBind = $userBind ? $userBind->toArray() : [];
        if (empty($userBind)) {
            throw new \think\Exception("对应住户不存在");
        }
        if (intval($userBind['status']) !== 1) {
            throw new \think\Exception("当前住户状态不支持使用此功能");
        }
        if ($uid && intval($userBind['uid']) !== $uid) {
            throw new \think\Exception("住户与当前登录人员身份不符");
        }
        !$uid && $uid = isset($userBind['uid']) && $userBind['uid'] ? intval($userBind['uid']) : 0;
        if ($village_id && intval($userBind['village_id']) !== $village_id) {
            throw new \think\Exception("住户小区与当前小区不符");
        }
        
        try {
            $userTimes = (new VisitorInviteService())->getUserEndTime($pigcms_id, ['isDelay' => 1]);
        } catch (\Exception $e){
            fdump_api([ 'msg' => '获取到期时间异常错误', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'xiHan/errGetUserOpenCode', 1);
            $userTimes = [];
        }
        $nowTime = time();
        $todayStartTime = strtotime(date('Y-m-d 00:00:00', $nowTime));
        $todayEndTime = strtotime(date('Y-m-d 23:59:59', $nowTime));
        
        $owner_uid = isset($userTimes['owner_uid']) ? intval($userTimes['owner_uid']) : 0;
        $propertyStartTime = isset($userTimes['propertyStartTime']) ? intval($userTimes['propertyStartTime']) : 0;
        $propertyEndTime = isset($userTimes['propertyEndTime']) ? intval($userTimes['propertyEndTime']) : 0;
        if ($propertyEndTime > 2145887999) {
            $propertyEndTime = 2145887999;
        }
        fdump_api(['line' => __LINE__, 'msg' => '二维码一些信息', 'owner_uid' => $owner_uid, 'propertyStartTime' => $propertyStartTime, 'propertyEndTime' => $propertyEndTime, 'userTimes' => $userTimes, 'pigcms_id' => $pigcms_id, 'todayStartTime' => $todayStartTime, 'todayEndTime' => $todayEndTime], 'xiHan/getUserOpenCode');

        $userBindType = $userBind['type'];
        if ($userBindType == 4) {
            $judgeFloorId = 0;
            $propertyStartTime = $todayStartTime;
            $propertyEndTime = 2145887999;
        } elseif (!in_array($userBindType, [5, 6]) && $propertyEndTime == 0) {
            $judgeFloorId = $userBind['floor_id'] && $userBind['floor_id'] > 0 ? $userBind['floor_id'] : 0;
            $propertyEndTime = 2145887999;
        } else {
            $judgeFloorId = $userBind['floor_id'] && $userBind['floor_id'] > 0 ? $userBind['floor_id'] : 0;
        }

        $db_house_village = new HouseVillage();
        $villageInfo = $db_house_village->getOne($village_id, 'village_id, village_name');
        $village_name = isset($villageInfo['village_name']) ? trim($villageInfo['village_name']) : '';
        fdump_api(['line' => __LINE__, 'msg' => '小区信息', 'village_id' => $village_id, 'village_name' => $village_name], 'xiHan/getUserOpenCode', 1);

        if ($propertyStartTime >= $todayStartTime) {
            $todayStartTime = strtotime(date('Y-m-d 00:00:00', $propertyStartTime));
            $todayEndTime = strtotime(date('Y-m-d 23:59:59', $propertyStartTime));
            $cardNoInt = date('Ymd', $propertyEndTime);
        } else {
            $cardNoInt = date('Ymd', $todayStartTime);
        }
        $dbHouseVillageUserBindIccard = new HouseVillageUserBindIccard();
        $whereIcCard = [];
        $whereIcCard[] = ['village_id', '=', $village_id];
        $whereIcCard[] = ['bind_id', '=', $pigcms_id];
        $whereIcCard[] = ['type', '=', 6];
        $whereIcCard[] = ['del_time', '=', 0];
        $icCardInfo = $dbHouseVillageUserBindIccard->getOne($whereIcCard);
        $icCardId = isset($icCardInfo['id']) && $icCardInfo['id'] ? intval($icCardInfo['id']) : 0;

        if ($propertyStartTime > 0 && $propertyStartTime > $todayStartTime) {
            return [
                'title' => "您暂无相关开门权限，请联系物业管理人员！", 'tip' => "门禁生效时间：" . date('Y-m-d H:i:s', $propertyStartTime) , 'qrCodeInfos' => [],
                'errTipArr' => [], 'village_name' => $village_name
            ];
        }
        $site_url = cfg('site_url');
        fdump_api(['line' => __LINE__, 'propertyEndTime' => $propertyEndTime, 'todayStartTime' => $todayStartTime], 'xiHan/getUserOpenCode', 1);
        if ($propertyEndTime < $todayStartTime) {
            $codeUrl = $site_url . '/static/images/house/visitor/QRCodeinvalid.png';// 默认失效图片
            if ($icCardId) {
                $dbHouseVillageUserBindIccard->updateThis(['id' => $icCardId], ['del_time' => time()]);
                invoke_cms_model('House_face_img/house_user_face_device_info', ['uid' => $uid, 'pigcms_id' => $pigcms_id]);
            }
            return [
                'codeUrl' => $codeUrl, 'effectTimeTip' => $propertyStartTime ? "起效时间：" . date('Y-m-d H:i:s', $propertyStartTime) : '',
                'expireTimeTip' => $propertyEndTime ? "失效时间：" . date('Y-m-d H:i:s', $propertyEndTime) : '',
                'openTimesTip' => '', 'tip' => '', 'title' => '',
            ];
        }
        if (in_array($userBindType, [0, 3])) {
            $cardNo = $pigcms_id . 'owner' . $cardNoInt;
        } elseif ($userBindType == 1) {
            $cardNo = $pigcms_id . 'family' . $cardNoInt;
        } elseif ($userBindType == 2) {
            $cardNo = $pigcms_id . 'tenant' . $cardNoInt;
        } elseif ($userBindType == 4) {
            $cardNo = $pigcms_id . 'worker' . $cardNoInt;
        } elseif ($userBindType == 5) {
            $cardNo = $pigcms_id . 'pass' . $cardNoInt;
        } elseif ($userBindType == 6) {
            $cardNo = $pigcms_id . 'visitor' . $cardNoInt;
        }
        fdump_api(['line' => __LINE__, 'msg' => '生成二维码信息', 'todayStartTime' => $todayStartTime, 'todayEndTime' => $todayEndTime, 'cardNoInt' => $cardNoInt, 'cardNo' => $cardNo], 'xiHan/getUserOpenCode', 1);
        $visit_owner_switch = 0;
        $hasDeviceIds = [];
        if ($userBindType == 6) {
            fdump_api(['line' => __LINE__, 'msg' => '访客', 'village_id' => $village_id, 'pigcms_id' => $pigcms_id], 'xiHan/getUserOpenCode', 1);
            // 判断类型为访客
            $db_house_village_visitor = new HouseVillageVisitor();
            $whereVisitor = [];
            $whereVisitor[] = ['visitor_bind_id', '=', $pigcms_id];
            $visitorInfo = $db_house_village_visitor->get_one($whereVisitor, 'id, visit_owner_switch, visit_to_devices');
            // 是否访问业主 0-默认 1-不访问业主 2-访问业主
            $visit_owner_switch = isset($visitorInfo['visit_owner_switch']) ? intval($visitorInfo['visit_owner_switch']) : 0;
            // 访客同步对应设备 设备id集合
            fdump_api(['line' => __LINE__, 'msg' => '访客信息', 'whereVisitor' => $whereVisitor, 'visit_owner_switch' => $visit_owner_switch], 'xiHan/getUserOpenCode', 1);
            if ($visit_owner_switch == 1) {
                $visit_to_devices = isset($visitorInfo['visit_to_devices']) ? trim($visitorInfo['visit_to_devices']) : '';
                $hasDeviceIds = explode(',', $visit_to_devices);
                !empty($hasDeviceIds) && $hasDeviceIds = array_values(array_unique($hasDeviceIds));
                if (empty($hasDeviceIds)) {
                    $hasDeviceIds = [];
                }
            }
            fdump_api(['line' => __LINE__, 'msg' => '访客相关参数', 'village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'visit_owner_switch' => $visit_owner_switch, 'hasDeviceIds' => $hasDeviceIds], 'xiHan/getUserOpenCode', 1);
        }
        
        $whereFace = [];
        $whereFace[] = ['village_id', '=', $village_id];
        $whereFace[] = ['device_type', '=', DeviceConst::XI_HAN_DEVICE_TYPE];
        $whereFace[] = ['is_del', '=', 0];
        if ($visit_owner_switch == 1) {
            $whereFace[] = ['device_id', 'in', $hasDeviceIds];
        }
        $db_house_face_device = new HouseFaceDevice();//人脸门禁表
        $deviceList = $db_house_face_device->getList($whereFace, true, 1);
        if ($deviceList && !is_array($deviceList)) {
            $deviceList = $deviceList->toArray();
        }
        fdump_api(['line' => __LINE__, 'msg' => '人脸设备信息', 'whereFace' => $whereFace, 'deviceList' => $deviceList], 'xiHan/getUserOpenCode', 1);

        $db_house_device_qrcode = new HouseDeviceQrcode();
        // 查询以设备id为 键值 的数组
        $whereQrcode = [];
        $whereQrcode[] = ['device_equipment_type', '=', DeviceConst::DEVICE_TYPE_FACE];
        $whereQrcode[] = ['bind_type', '=', DeviceQrcodeConst::BIND_TYPE_VILLAGE_USER];
        $whereQrcode[] = ['bind_from', '=', DeviceQrcodeConst::BIND_FROM_HOUSE_VILLAGE_USER_BIND];
        $whereQrcode[] = ['bind_id', '=', $pigcms_id];
        $whereQrcode[] = ['device_id', '>', 0];
        $whereQrcode[] = ['delete_time', '=', 0];
        $whereQrcode[] = ['device_type', '=', DeviceConst::XI_HAN_DEVICE_TYPE];
        fdump_api(['whereQrcode' => $whereQrcode, 'pigcms_id' => $pigcms_id], 'xiHan/getUserOpenCode', 1);
        $qrcodeField = 'id, device_equipment_type, third_protocol, device_id, device_sn, bind_type, bind_from, bind_id, qr_content_txt, qr_content_url, qr_syn_status, effective_start_time, effective_end_time, open_times, opened_times';
        $qrcodeArr = $db_house_device_qrcode->getColumn($whereQrcode, $qrcodeField, 'device_id');
        fdump_api(['whereQrcode' => $whereQrcode, 'qrcodeArr' => $qrcodeArr, 'pigcms_id' => $pigcms_id, 'judgeFloorId' => $judgeFloorId], 'xiHan/getUserOpenCode', 1);

        $qrCodeInfos = [];
        $errTipArr = [];
        $protocolType = 3;
        $sHouseDeviceQrcodeService = new HouseDeviceQrcodeService();
        $needSyn = [];
        foreach ($deviceList as $device) {
            $device_id = isset($device['device_id']) ? intval($device['device_id']) : 0;
            if ($visit_owner_switch == 1 && !in_array($device_id, $hasDeviceIds)) {
                fdump_api(['msg' => '访客归属设备', 'hasDeviceIds' => $hasDeviceIds, 'device_id' => $device_id, 'pigcms_id' => $pigcms_id], 'xiHan/getUserOpenCode', 1);
                continue;
            }
            if ($visit_owner_switch != 1 && $judgeFloorId && isset($device['floor_id']) && $device['floor_id'] != -1 && $device['floor_id'] != 0) {
                $floorIdArr = explode(',', $device['floor_id']);
                if (!in_array($judgeFloorId, $floorIdArr)) {
                    // 不是同一个单元的 同步不进行下发处理
                    fdump_api(['msg' => '不是同一个单元的 同步不进行下发处理', 'judgeFloorId' => $judgeFloorId, 'floorIdArr' => $floorIdArr, 'device_id' => $device_id, 'pigcms_id' => $pigcms_id], 'xiHan/getUserOpenCode', 1);
                    continue;
                }
            }
            $device_type = isset($device['device_type']) ? intval($device['device_type']) : 0;
            $device_name = isset($device['device_name']) ? trim($device['device_name']) : '';
            $device_sn = isset($device['device_sn']) ? trim($device['device_sn']) : '';

            $qrcodeInfo = isset($qrcodeArr[$device_id]) ? $qrcodeArr[$device_id] : [];
            $qr_content_txt = isset($qrcodeInfo['qr_content_txt']) && trim($qrcodeInfo['qr_content_txt']) ? trim($qrcodeInfo['qr_content_txt']) : '';
            $effective_start_time = isset($qrcodeInfo['effective_start_time']) ? intval($qrcodeInfo['effective_start_time']) : 0;
            $effective_end_time = isset($qrcodeInfo['effective_end_time']) ? intval($qrcodeInfo['effective_end_time']) : 0;
            fdump_api(['device_type' => $device_type, 'device_name' => $device_name, 'device_sn' => $device_sn, 'qrcodeInfo' => $qrcodeInfo, 'qr_content_txt' => $qr_content_txt, 'device_id' => $device_id, 'pigcms_id' => $pigcms_id], 'xiHan/getUserOpenCode', 1);
            if ($qr_content_txt && $qr_content_txt == trim($cardNo) && ($effective_end_time >= $todayEndTime && $effective_start_time <= $todayStartTime)) {
                $qr_content_url = isset($qrcodeInfo['qr_content_url']) && trim($qrcodeInfo['qr_content_url']) ? trim($qrcodeInfo['qr_content_url']) : '';
                !$qr_content_url && $qr_content_url = $site_url . '/index.php?c=Recognition&a=get_own_qrcode&qrCon=' . $qr_content_txt;
                $effectTimeTip = "起效：" . date('Y-m-d', $todayStartTime);
                $expireTimeTip = "失效：" . date('Y-m-d', $todayEndTime);
                $qrCodeInfo = isset($qrCodeInfos[$protocolType]) ? $qrCodeInfos[$protocolType] : [];
                $includeDevices = isset($qrCodeInfo['includeDevices']) ? $qrCodeInfo['includeDevices'] : [];
                $deviceInfo = [
                    'device_id' => $device_id, 'device_sn' => $device_sn, 'device_name' => $device_name,
                ];
                $includeDevices[] = $deviceInfo;
                $qrCodeInfo['includeDevices'] = $includeDevices;
                $qrCodeInfo['qrcodeUrl'] = $qr_content_url;
                $qrCodeInfo['effectTimeTip'] = $effectTimeTip;
                $qrCodeInfo['expireTimeTip'] = $expireTimeTip;
                $qrCodeInfos[$protocolType] = $qrCodeInfo;
                continue;
            }
            $needSyn[] = $device;
        }
        fdump_api(['$needSyn' => $needSyn], 'xiHan/getUserOpenCode', 1);
        if (! empty($needSyn)) {
            $visitor_open_number_limit = $sHouseDeviceQrcodeService->getOpenNumberMaxLimit($village_id);
            $qr_content_url = $site_url . '/index.php?c=Recognition&a=get_own_qrcode&qrCon=' . $cardNo;
            $icCardParams = [
                'village_id' => $village_id, 'bind_id' => $pigcms_id, 'ic_card_no' => $cardNo, 'type' => 6,
                'url' => $qr_content_url, 'start_time' => $todayStartTime, 'end_time' => $todayEndTime,
                'open_times' => $visitor_open_number_limit, 'last_time' => $nowTime
            ];
            if ($icCardId) {
                $dbHouseVillageUserBindIccard->updateThis(['id' => $icCardId], $icCardParams);
            } else {
                $icCardId = $dbHouseVillageUserBindIccard->add($icCardParams);
            }
            $effectTimeTip = "起效：" . date('Y-m-d', $todayStartTime);
            $expireTimeTip = "失效：" . date('Y-m-d', $todayEndTime);
            foreach ($needSyn as $need) {
                $device_id = isset($need['device_id']) ? intval($need['device_id']) : 0;
                $device_name = isset($need['device_name']) ? trim($need['device_name']) : '';
                $device_sn = isset($need['device_sn']) ? trim($need['device_sn']) : '';
//                $this->sycBindUserToDevice($village_id, $pigcms_id, $need['floor_id'], $device_id);
                $dataParam = array('village_id' => $village_id, 'bind_pigcms_id' => $pigcms_id);
                $dataParam['floor_id_str'] = $need['floor_id'];
                $dataParam['device_id'] = $device_id;
                $this->pushBindUserToXiHanDevice($dataParam);
                
                $qrCodeInfo = isset($qrCodeInfos[$protocolType]) ? $qrCodeInfos[$protocolType] : [];
                $includeDevices = isset($qrCodeInfo['includeDevices']) ? $qrCodeInfo['includeDevices'] : [];
                $deviceInfo = [
                    'device_id' => $device_id, 'device_sn' => $device_sn, 'device_name' => $device_name,
                ];
                $includeDevices[] = $deviceInfo;
                $qrCodeInfo['includeDevices'] = $includeDevices;
                $qrCodeInfo['qrcodeUrl'] = $qr_content_url;
                $qrCodeInfo['effectTimeTip'] = $effectTimeTip;
                $qrCodeInfo['expireTimeTip'] = $expireTimeTip;
                $qrCodeInfos[$protocolType] = $qrCodeInfo;
                usleep(80000); // 休眠80毫秒
            }
            
        }
        $qrCodeInfos = array_values($qrCodeInfos);
        foreach ($qrCodeInfos as &$qrCode) {
            $includeDevices = isset($qrCode['includeDevices']) ? $qrCode['includeDevices'] : [];
            $deviceMsg = [];
            foreach ($includeDevices as $device1) {
                $device_name = isset($device1['device_name']) ? trim($device1['device_name']) : '';
                $device_sn = isset($device1['device_sn']) ? trim($device1['device_name']) : '';
                if ($device_name) {
                    $deviceMsg[] = $device_name . ";";
                } else {
                    $deviceMsg[] = $device_sn . ";";
                }
            }
            $qrCode['deviceMsg'] = $deviceMsg;
        }
        fdump_api(['qrCodeInfos' => $qrCodeInfos], 'xiHan/getUserOpenCode', 1);
        return [
            'title' => "请将对应设备二维码对准设备识别位置", 'tip' => "点击图片可刷新", 'qrCodeInfos' => $qrCodeInfos,
            'errTipArr' => $errTipArr, 'village_name' => $village_name
        ];
    }
}