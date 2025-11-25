<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      设备相关二维码
 */
namespace app\community\model\service\Device;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\db\Device\HouseDeviceQrcode;
use app\community\model\db\Device\HouseDeviceVisitorAuth;
use app\community\model\db\FaceUserBindDevice;
use app\community\model\db\HouseFaceDevice;
use app\community\model\db\HouseFaceDeviceCmd;
use app\community\model\db\HouseFaceSyncLog;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageConfig;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillagePublicArea;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageVisitor;
use app\community\model\service\AockpitService;
use app\community\model\service\ConfigDataService;
use app\community\model\service\HouseFaceService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\VisitorInviteService;
use app\consts\DeviceConst;
use app\consts\DeviceQrcodeConst;
use app\traits\device\HikGateWayTraits;

class HouseDeviceQrcodeService
{
    use HikGateWayTraits;
    
    public function addDeviceQrCode($pigcms_id) {
        try {
            return $this->addDeviceQrCodeBox($pigcms_id);
        } catch (\Exception $e){
            fdump_api([ 'msg' => '获取到期时间异常错误', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errAddDeviceQrCode', 1);
        }
        return true;
    }

    /**
     * 获取开门二维码.
     */
    public function addDeviceQrCodeBox($pigcms_id) {
        $houseVillageUserBindService = new HouseVillageUserBindService();
        $whereUserBind = [];
        $whereUserBind[] = ['status', '=', 1];
        $whereUserBind[] = ['pigcms_id', '=', $pigcms_id];
        $field = true;
        $userBinds = $houseVillageUserBindService->getBindInfo($whereUserBind,$field);
        if ($userBinds && !is_array($userBinds)) {
            $userBinds = $userBinds->toArray();
        }
        try {
            $userTimes = (new VisitorInviteService())->getUserEndTime($pigcms_id, ['isDelay' => 1]);
        } catch (\Exception $e){
            fdump_api([ 'msg' => '获取到期时间异常错误', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errDeviceAllToCloudLog', 1);
            $userTimes = [];
        }
        fdump_api(['userTimes' => $userTimes,'userBinds' => $userBinds, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
        $nowTime = time();
        $todayStartTime = strtotime(date('Y-m-d 00:00:00', $nowTime));
        $todayEndTime = strtotime(date('Y-m-d 23:59:59', $nowTime));
        
        $owner_uid = isset($userTimes['owner_uid']) ? intval($userTimes['owner_uid']) : 0;
        $propertyStartTime = isset($userTimes['propertyStartTime']) ? intval($userTimes['propertyStartTime']) : 0;
        $propertyEndTime = isset($userTimes['propertyEndTime']) ? intval($userTimes['propertyEndTime']) : 0;
        if ($propertyEndTime > 2145887999) {
            $propertyEndTime = 2145887999;
        }
        fdump_api(['userTimes' => $userTimes, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);

        if ($userBinds['type'] == 4) {
            $judgeFloorId = 0;
            $propertyStartTime = $todayStartTime;
            $propertyEndTime = 2145887999;
        } elseif (!in_array($userBinds['type'], [5, 6]) && $propertyEndTime == 0) {
            $judgeFloorId = $userBinds['floor_id'] && $userBinds['floor_id'] > 0 ? $userBinds['floor_id'] : 0;
            $propertyEndTime = 2145887999;
        } else {
            $judgeFloorId = $userBinds['floor_id'] && $userBinds['floor_id'] > 0 ? $userBinds['floor_id'] : 0;
        }
        fdump_api(['userTimes' => $userTimes, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
        $village_id = isset($userBinds['village_id']) ? intval($userBinds['village_id']) : 0;
        $db_house_village = new HouseVillage();
        $villageInfo = $db_house_village->getOne($village_id, 'village_id, village_name');
        $village_name = isset($villageInfo['village_name']) ? trim($villageInfo['village_name']) : '';

        fdump_api(['village_id' => $village_id, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
        if ($propertyStartTime >= $todayStartTime) {
            $todayStartTime = strtotime(date('Y-m-d 00:00:00', $propertyStartTime));
            $todayEndTime = strtotime(date('Y-m-d 23:59:59', $propertyStartTime));
            $cardNoInt = date('ymd', $propertyEndTime) . $pigcms_id;
        } else {
            $cardNoInt = date('ymd', $todayStartTime) . $pigcms_id;
        }
        $cardNo = 10000000000000 + intval($cardNoInt);
        $errTipArr = [];
        fdump_api(['village_id' => $village_id, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
        if ($propertyEndTime < $todayStartTime) {
            return [
                'title' => "您暂无相关开门权限，请联系物业管理人员！", 'tip' => "点击图片可刷新", 'qrCodeInfos' => [], 
                'errTipArr' => [], 'village_name' => $village_name
            ];
        }
        if ($propertyStartTime > 0 && $propertyStartTime > $todayStartTime) {
            return [
                'title' => "您暂无相关开门权限，请联系物业管理人员！", 'tip' => "点击图片可刷新", 'qrCodeInfos' => [],
                'errTipArr' => [], 'village_name' => $village_name
            ];
        }

        fdump_api(['village_id' => $village_id, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
        $visit_owner_switch = 0;
        $hasDeviceIds = [];
        if ($userBinds['type'] == 6) {
            fdump_api(['village_id' => $village_id, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
            // 判断类型为访客
            $db_house_village_visitor = new HouseVillageVisitor();
            $whereVisitor = [];
            $whereVisitor[] = ['visitor_bind_id', '=', $pigcms_id];
            $visitorInfo = $db_house_village_visitor->get_one($whereVisitor, 'id, visit_owner_switch, visit_to_devices');
            // 是否访问业主 0-默认 1-不访问业主 2-访问业主
            $visit_owner_switch = isset($visitorInfo['visit_owner_switch']) ? intval($visitorInfo['visit_owner_switch']) : 0;
            // 访客同步对应设备 设备id集合
            fdump_api(['whereVisitor' => $whereVisitor, 'visit_owner_switch' => $visit_owner_switch], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
            if ($visit_owner_switch == 1) {
                $visit_to_devices = isset($visitorInfo['visit_to_devices']) ? trim($visitorInfo['visit_to_devices']) : '';
                $hasDeviceIds = explode(',', $visit_to_devices);
                if (empty($hasDeviceIds)) {
                    $hasDeviceIds = [];
                }
            }
            fdump_api(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'visit_owner_switch' => $visit_owner_switch, 'hasDeviceIds' => $hasDeviceIds], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
        }

        fdump_api(['village_id' => $village_id, 'pigcms_id' => $pigcms_id, 'visit_owner_switch' => $visit_owner_switch, 'hasDeviceIds' => $hasDeviceIds], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
        $whereFace = [];
        $whereFace[] = ['village_id', '=', $village_id];
        $whereFace[] = ['device_type', '=', 5];
        $whereFace[] = ['thirdProtocol', 'in', [DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM, DeviceConst::HIK_YUNMO_WAIBU, 0]];
        $whereFace[] = ['is_del', '=', 0];
        $db_house_face_device = new HouseFaceDevice();//人脸门禁表
        fdump_api(['whereFace' => $whereFace, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
        $deviceList = $db_house_face_device->getList($whereFace, true, 1);
        if ($deviceList && !is_array($deviceList)) {
            $deviceList = $deviceList->toArray();
        }
        fdump_api(['deviceList' => $deviceList, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
        $dbFaceUserBindDevice = new FaceUserBindDevice();
        $whereAbout = [];
        $whereAbout[] = ['bind_type', '=', DeviceConst::Hk_GATEWAY_USER_BIND_PIGCMSID];
        $whereAbout[] = ['bind_id', '=', $pigcms_id];
        $aboutFaceArr = $dbFaceUserBindDevice->getColumn($whereAbout, 'id, bind_type, bind_id, device_id', 'device_id');

        $where1About = [];
        $where1About[] = ['bind_type', '=', 0];
        $where1About[] = ['bind_id', '=', $pigcms_id];
        $waiBuFaceObj = $dbFaceUserBindDevice->getOne($where1About, 'id, bind_type, bind_id, device_id');
        $waiBuFaceArr='';
        if($waiBuFaceObj && !$waiBuFaceObj->isEmpty()){
            $waiBuFaceArr=$waiBuFaceObj->toArray();
        }
        fdump_api(['aboutFaceArr' => $aboutFaceArr,'waiBuFaceArr'=>$waiBuFaceArr, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
        $db_house_device_qrcode = new HouseDeviceQrcode();
        $device_equipment_type = DeviceConst::DEVICE_TYPE_FACE;
        // 查询以设备id为 键值 的数组
        $whereQrcode = [];
        $whereQrcode[] = ['device_equipment_type', '=', $device_equipment_type];
        $whereQrcode[] = ['bind_type', '=', DeviceQrcodeConst::BIND_TYPE_VILLAGE_USER];
        $whereQrcode[] = ['bind_from', '=', DeviceQrcodeConst::BIND_FROM_HOUSE_VILLAGE_USER_BIND];
        $whereQrcode[] = ['bind_id', '=', $pigcms_id];
        $whereQrcode[] = ['device_id', '>', 0];
        $whereQrcode[] = ['delete_time', '=', 0];
        $whereQrcode[] = ['device_type', '=', DeviceConst::HIK_DEVICE_TYPE];
        $qrcodeField = 'id, device_equipment_type, third_protocol, device_id, device_sn, bind_type, bind_from, bind_id, qr_content_txt, qr_content_url, qr_syn_status, effective_start_time, effective_end_time, open_times, opened_times';
        $qrcodeArr = $db_house_device_qrcode->getColumn($whereQrcode, $qrcodeField, 'device_id');
        fdump_api(['whereQrcode' => $whereQrcode, 'qrcodeArr' => $qrcodeArr, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
        // 直接查询 海康云眸外部应用人员二维码同步记录
        $whereHikWai = [];
        $whereHikWai[] = ['device_equipment_type', '=', $device_equipment_type];
        $whereHikWai[] = ['third_protocol', '=', DeviceConst::HIK_YUNMO_WAIBU];
        $whereHikWai[] = ['bind_type', '=', DeviceQrcodeConst::BIND_TYPE_VILLAGE_USER];
        $whereHikWai[] = ['bind_from', '=', DeviceQrcodeConst::BIND_FROM_HOUSE_VILLAGE_USER_BIND];
        $whereHikWai[] = ['bind_id', '=', $pigcms_id];
        $whereHikWai[] = ['delete_time', '=', 0];
        $hikYunMoWaiBuInfo = $db_house_device_qrcode->getOne($whereHikWai, $qrcodeField);
        if ($hikYunMoWaiBuInfo && !is_array($hikYunMoWaiBuInfo)) {
            $hikYunMoWaiBuInfo = $hikYunMoWaiBuInfo->toArray();
        }
        fdump_api(['whereHikWai' => $whereHikWai, 'hikYunMoWaiBuInfo' => $hikYunMoWaiBuInfo, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);

        $hasYunMoWaiBu = false;
        $yunMoWaiBuQrCodeUrl = '';
        $gateWayUrl = '';
        $qrCodeInfos = [];
        foreach ($deviceList as $device) {
            fdump_api(['msg' => '设备', 'device' => $device], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
            $device_id = isset($device['device_id']) ? intval($device['device_id']) : 0;
            if ($visit_owner_switch == 1 && !in_array($device_id, $hasDeviceIds)) {
                fdump_api(['msg' => '访客归属设备', 'hasDeviceIds' => $hasDeviceIds, 'device_id' => $device_id, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
                continue;
           }
           if ($visit_owner_switch != 1 && $judgeFloorId && isset($device['floor_id']) && $device['floor_id'] != -1 && $device['floor_id'] != 0) {
                $floorIdArr = explode(',', $device['floor_id']);
                if (!in_array($judgeFloorId, $floorIdArr)) {
                    // 不是同一个单元的 同步不进行下发处理
                    fdump_api(['msg' => '不是同一个单元的 同步不进行下发处理', 'judgeFloorId' => $judgeFloorId, 'floorIdArr' => $floorIdArr, 'device_id' => $device_id, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
                    continue;
                }
            }
            $thirdProtocol = isset($device['thirdProtocol']) ? intval($device['thirdProtocol']) : 0;
            $device_type = isset($device['device_type']) ? intval($device['device_type']) : 0;
            $device_name = isset($device['device_name']) ? trim($device['device_name']) : '';
            $device_sn = isset($device['device_sn']) ? trim($device['device_sn']) : '';
            $cloud_device_id = isset($device['cloud_device_id']) ? trim($device['cloud_device_id']) : '';
            
            if ($device_type == 5 && (!$thirdProtocol || $thirdProtocol == DeviceConst::HIK_YUNMO_WAIBU)) {
                $protocolType = 1;
            } elseif (in_array($thirdProtocol, [DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM])) {
                $protocolType = 2;
            } else {
                fdump_api(['msg' => '不在服务范围内', 'device_id' => $device_id, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
                continue;
            }
            if (!$cloud_device_id) {
                $errText = $device_name ?? $device_sn;
                $message = $protocolType == 1 ? "设备未同步到海康云眸" : "设备未同步到海康网关";
                $errTipArr[] = [
                    'title' => "设备：{$errText}",
                    'message' => $message,
                ];
                fdump_api(['errTipArr' => $errTipArr, 'device' => $device, 'device_id' => $device_id, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
                continue;
            }
            $aboutFace = isset($aboutFaceArr[$device_id]) ? $aboutFaceArr[$device_id] : [];
            fdump_api(['aboutFace' => $aboutFace, 'device_id' => $device_id, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
            if ($waiBuFaceArr && $protocolType == 1) {
                // todo 海康云眸[外部独立应用开发]
                
            } 
            elseif (!isset($aboutFace['bind_id']) || !$aboutFace['bind_id']) {
                // todo 人员未同步给设备云-触发同步
                if ($protocolType == 1) {
                    // todo 海康云眸[外部独立应用开发]
                    $errText = $device_name ?? $device_sn;
                    $errTipArr[] = [
                        'title' => "设备：{$errText}",
                        'message' => "人员未同步到海康云眸",
                    ];
                    invoke_cms_model('Face_door_a5_service/synDevice', [$pigcms_id, $device_id]);
                    fdump_api(['message' => '触发同步到海康云眸外部应用', 'device_id' => $device_id, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
                }
                if ($protocolType == 2) {
                    // todo 海康网关
                    $param = [
                        'pigcms_id' => $pigcms_id, 'device_id' => $device_id, 'room_id' => $userBinds['vacancy_id'], 'village_id' => $device['village_id'],
                        'uid' => isset($userBinds['uid']) ? $userBinds['uid'] : 0, 'thirdProtocol' => $thirdProtocol, 
                        'device_sn' => $device_sn, 'deviceType' => $device_type, 'qr_content_card' => trim($cardNo),
                    ];
                    $errText = $device_name ?? $device_sn;
                    $errTipArr[] = [
                        'title' => "设备：{$errText}",
                        'message' => "人员未同步到海康网关",
                    ];
//                    (new HikGateWayDeviceService())->userToFaceDevice($device, $param);
                    // todo 走队列执行
                    $param['handleType'] = 'userToFaceDeviceBox';
                    $job_id = $this->traitCommonHikDeviceGateWay($param);
                    fdump_api(['msg' => '走队列执行', 'param' => $param, 'job_id' => $job_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
                }
                continue;
            }
            $qrcodeInfo = isset($qrcodeArr[$device_id]) ? $qrcodeArr[$device_id] : [];
            fdump_api(['aboutFace' => $aboutFace, 'qrcodeInfo' => $qrcodeInfo, 'device_id' => $device_id, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
            $qr_content_txt = isset($qrcodeInfo['qr_content_txt']) && trim($qrcodeInfo['qr_content_txt']) ? trim($qrcodeInfo['qr_content_txt']) : '';
            fdump_api(['qr_content_txt' => $qr_content_txt, 'protocolType' => $protocolType, 'cardNo' => $cardNo, 'device_id' => $device_id, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
            if ($protocolType == 2 && $qr_content_txt && $qr_content_txt == trim($cardNo)) {
                // todo 二维码有效期未过期
                $qr_content_url = isset($qrcodeInfo['qr_content_url']) && trim($qrcodeInfo['qr_content_url']) ? trim($qrcodeInfo['qr_content_url']) : '';
                !$qr_content_url && $qr_content_url = cfg('site_url') . '/index.php?c=Recognition&a=get_own_qrcode&qrCon=' . $qr_content_txt;
                $effectTimeTip = "起效：" . date('Y-m-d', $todayStartTime);
                $expireTimeTip = "失效：" . date('Y-m-d', $todayEndTime);
                $qrCodeInfo = isset($qrCodeInfos[$protocolType]) ? $qrCodeInfos[$protocolType] : [];
                $includeDevices = isset($qrCodeInfo['includeDevices']) ? $qrCodeInfo['includeDevices'] : [];
                $deviceInfo = [
                    'device_id' => $device_id, 'device_sn' => $device_sn, 'device_name' => $device_name, 'cloud_device_id' => $cloud_device_id,
                ];
                $includeDevices[] = $deviceInfo;
                $qrCodeInfo['includeDevices'] = $includeDevices;
                $qrCodeInfo['qrcodeUrl'] = $qr_content_url;
                $qrCodeInfo['effectTimeTip'] = $effectTimeTip;
                $qrCodeInfo['expireTimeTip'] = $expireTimeTip;
                $qrCodeInfos[$protocolType] = $qrCodeInfo;
                continue;
            }
            if ($protocolType == 1) {
                $effective_end_time = isset($hikYunMoWaiBuInfo['effective_end_time']) && intval($hikYunMoWaiBuInfo['effective_end_time']) > 0 ? intval($hikYunMoWaiBuInfo['effective_end_time']) : 0;
                $qr_content_url = isset($hikYunMoWaiBuInfo['qr_content_url']) && trim($hikYunMoWaiBuInfo['qr_content_url']) ? trim($hikYunMoWaiBuInfo['qr_content_url']) : '';
                $qr_content_txt = isset($hikYunMoWaiBuInfo['qr_content_txt']) && trim($hikYunMoWaiBuInfo['qr_content_txt']) ? trim($hikYunMoWaiBuInfo['qr_content_txt']) : '';
                !$qr_content_url && $qr_content_url = cfg('site_url') . '/index.php?c=Recognition&a=get_own_qrcode&qrCon=' . $qr_content_txt;
                if ($effective_end_time > $todayEndTime && $qr_content_url) {
                    // todo 二维码有效期未过期
                    
                    $effectTimeTip = "起效：" . date('Y-m-d', $todayStartTime);
                    $expireTimeTip = "失效：" . date('Y-m-d', $todayEndTime);
                    $qrCodeInfo = isset($qrCodeInfos[$protocolType]) ? $qrCodeInfos[$protocolType] : [];
                    $includeDevices = isset($qrCodeInfo['includeDevices']) ? $qrCodeInfo['includeDevices'] : [];
                    $deviceInfo = [
                        'device_id' => $device_id, 'device_sn' => $device_sn, 'device_name' => $device_name, 'cloud_device_id' => $cloud_device_id,
                    ];
                    $includeDevices[] = $deviceInfo;
                    $qrCodeInfo['includeDevices'] = $includeDevices;
                    $qrCodeInfo['qrcodeUrl'] = $qr_content_url;
                    $qrCodeInfo['effectTimeTip'] = $effectTimeTip;
                    $qrCodeInfo['expireTimeTip'] = $expireTimeTip;
                    $qrCodeInfos[$protocolType] = $qrCodeInfo;
                    continue;
                }
            }
            $visitor_open_number_limit = $this->getOpenNumberMaxLimit($village_id);
            if ($protocolType == 1) {
                $hasYunMoWaiBu = true;
            }
            if ($protocolType == 2) {
                // todo 海康网关(平台)
                $param = [
                    'pigcms_id' => $pigcms_id, 'device_id' => $device_id, 'room_id' => $userBinds['vacancy_id'], 
                    'village_id' => $device['village_id'], 'uid' => isset($userBinds['uid']) ? $userBinds['uid'] : 0, 'thirdProtocol' => $thirdProtocol,
                    'device_sn' => $device_sn, 'deviceType' => $device_type, 'qr_content_card' => trim($cardNo),
                ];
                (new HikGateWayDeviceService())->userToFaceDevice($device, $param);
//                // todo 走队列执行
//                $param['handleType'] = 'userToFaceDeviceBox';
//                $job_id = $this->traitCommonHikDeviceGateWay($param);
//                fdump_api(['param' => $param, 'job_id' => $job_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
                // todo 二维码卡号
                $cardParams = [
                    'deviceType' => $device_equipment_type, 'device_id' => $device_id, 'device_sn' => $device_sn,
                    'device_type' => $device_type, 'thirdProtocol' => $thirdProtocol, 'nowTime' => $nowTime,
                    'employeeNo' => $pigcms_id, 'qr_content_card' => trim($cardNo),
                ];
                $cardInfo = ['employeeNo' => $pigcms_id, 'cardNo' => trim($cardNo), 'cardType' => 'normalCard'];
                fdump_api(['cardParams' => $cardParams,'cardInfo' => $cardInfo, 'device_id' => $device_id, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
                $resultCard = (new HikGateWayDeviceService())->addCard($device, $cardInfo, $cardParams);
                fdump_api(['resultCard' => $resultCard, 'device_id' => $device_id, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
                if ((isset($resultCard['statusCode']) && intval($resultCard['statusCode']) == 1) || (isset($result['subStatusCode']) && trim($result['subStatusCode']) == 'ok')) {
                    $device_qrcode_id = isset($qrcodeInfo['id']) ? intval($qrcodeInfo['id']) : 0;
                    $gateWayUrl = cfg('site_url') . '/index.php?c=Recognition&a=get_own_qrcode&qrCon=' . $cardNo;
                    
                    $bind_arr = [
                        'device_equipment_type' => $device_equipment_type, 'third_protocol' => $thirdProtocol,
                        'device_id' => $device_id, 'bind_type' => DeviceQrcodeConst::BIND_TYPE_VILLAGE_USER, 'bind_id' => $pigcms_id,
                        'bind_from' => DeviceQrcodeConst::BIND_FROM_HOUSE_VILLAGE_USER_BIND, 'qr_content_txt' => trim($cardNo),
                        'qr_content_url' => trim($gateWayUrl), 'qr_syn_time' => $nowTime, 'qr_syn_status' => 1, 
                        'open_times' => $visitor_open_number_limit, 'opened_times' => 0, 'effective_start_time' => $todayStartTime, 
                        'effective_end_time' => $todayEndTime, 'village_id' => $village_id,
                    ];
                    fdump_api(['bind_arr' => $bind_arr, 'device_id' => $device_id, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
                    if ($device_qrcode_id <= 0) {
                        $bind_arr['add_time'] = $nowTime;
                        $db_house_device_qrcode->add($bind_arr);
                    } else {
                        $db_house_device_qrcode->updateThis(['id' => $device_qrcode_id], $bind_arr);
                    }
                    $effectTimeTip = "起效：" . date('Y-m-d', $todayStartTime);
                    $expireTimeTip = "失效：" . date('Y-m-d', $todayEndTime);
                    $qrCodeInfo = isset($qrCodeInfos[$protocolType]) ? $qrCodeInfos[$protocolType] : [];
                    $includeDevices = isset($qrCodeInfo['includeDevices']) ? $qrCodeInfo['includeDevices'] : [];
                    $deviceInfo = [
                        'device_id' => $device_id, 'device_sn' => $device_sn, 'device_name' => $device_name, 'cloud_device_id' => $cloud_device_id,
                    ];
                    $includeDevices[] = $deviceInfo;
                    $qrCodeInfo['includeDevices'] = $includeDevices;
                    $qrCodeInfo['qrcodeUrl'] = $gateWayUrl;
                    $qrCodeInfo['effectTimeTip'] = $effectTimeTip;
                    $qrCodeInfo['expireTimeTip'] = $expireTimeTip;
                    $qrCodeInfos[$protocolType] = $qrCodeInfo;
                } else {
                    $statusCode = isset($resultCard['statusCode']) ? $resultCard['statusCode'] : '';
                    $subStatusCode = isset($resultCard['subStatusCode']) ? $resultCard['subStatusCode'] : '';
                    $error_message = isset($resultCard['error_message']) ? trim($resultCard['error_message']) : '';
                    !$error_message && $error_message = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
                    $error_message .= '[二维码获取失败]';
                    $errText = $device_name ?? $device_sn;
                    $errTipArr[] = [
                        'title' => "设备：{$errText}",
                        'message' => $error_message
                    ];
                }
            }
        }
        fdump_api(['qrCodeInfos' => $qrCodeInfos, 'pigcms_id' => $pigcms_id,'hasYunMoWaiBu'=>$hasYunMoWaiBu,'protocolType'=>$protocolType], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
        if ($hasYunMoWaiBu)
        {
            // todo 海康云眸[外部独立应用开发]
            // 海康云眸[外部独立应用开发] 二维码所用卡号固定
            $cardNo = 1230000000000 + intval($pigcms_id);
            $addCardResult = invoke_cms_model('Face_door_a5_service/singleAddCard', ['ic_card' => $cardNo, 'employeeNo' => $pigcms_id]);
            $addCardResult = isset($addCardResult['retval']) ? $addCardResult['retval'] : [];
			fdump_api(['addCardResult' => $addCardResult, 'cardNo' => $cardNo,'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
            if (isset($addCardResult['code']) && $addCardResult['code'] == 200) {
                // 添加成功或者已经存在了
                $visitor_param = [
                    'cardNo'     => trim($cardNo), 'effectTime' => date('ymdHis', $todayStartTime),
                    'expireTime' => date('ymdHis', $todayEndTime), 'openTimes'  => $visitor_open_number_limit,
                ];
                $visitor_device_result = invoke_cms_model('Face_door_a5_service/getVisitorQrcode', ['param' => $visitor_param]);
                $visitor_device_result = isset($visitor_device_result['retval']) ? $visitor_device_result['retval'] : [];
                
                $qrCodeUrl = isset($visitor_device_result['data']['qrCodeUrl']) && $visitor_device_result['data']['qrCodeUrl'] ? trim($visitor_device_result['data']['qrCodeUrl']) : '';
                if ($qrCodeUrl) {
                    $device_qrcode_id = isset($hikYunMoWaiBuInfo['id']) ? intval($hikYunMoWaiBuInfo['id']) : 0;
                    $bind_arr = [
                        'device_equipment_type' => $device_equipment_type, 'third_protocol' => $thirdProtocol,
                        'device_id' => 0, 'bind_type' => DeviceQrcodeConst::BIND_TYPE_VILLAGE_USER, 'bind_id' => $pigcms_id,
                        'bind_from' => DeviceQrcodeConst::BIND_FROM_HOUSE_VILLAGE_USER_BIND, 'qr_content_txt' => trim($cardNo),
                        'qr_content_url' => trim($qrCodeUrl), 'qr_syn_time' => $nowTime, 'qr_syn_status' => 1, 'open_times' => $visitor_open_number_limit,
                        'opened_times' => 0, 'effective_start_time' => $todayStartTime, 'effective_end_time' => $todayEndTime, 'village_id' => $village_id,
                    ];
                    fdump_api(['bind_arr' => $bind_arr, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
                    if ($device_qrcode_id <= 0) {
                        $bind_arr['add_time'] = $nowTime;
                        $db_house_device_qrcode->add($bind_arr);
                    } else {
                        $db_house_device_qrcode->updateThis(['id' => $device_qrcode_id], $bind_arr);
                    }
                    $effectTimeTip = "起效：" . date('Y-m-d', $todayStartTime);
                    $expireTimeTip = "失效：" . date('Y-m-d', $todayEndTime);
                    $qrCodeInfo = isset($qrCodeInfos[$protocolType]) ? $qrCodeInfos[$protocolType] : [];
                    $includeDevices = isset($qrCodeInfo['includeDevices']) ? $qrCodeInfo['includeDevices'] : [];
                    $deviceInfo = [
                        'device_id' => $device_id, 'device_sn' => $device_sn, 'device_name' => $device_name, 'cloud_device_id' => $cloud_device_id,
                    ];
                    $includeDevices[] = $deviceInfo;
                    $qrCodeInfo['includeDevices'] = $includeDevices;
                    $qrCodeInfo['qrcodeUrl'] = $qrCodeUrl;
                    $qrCodeInfo['effectTimeTip'] = $effectTimeTip;
                    $qrCodeInfo['expireTimeTip'] = $expireTimeTip;
                    $qrCodeInfos[$protocolType] = $qrCodeInfo;
                } else {
                    $message = isset($visitor_device_result['message']) && $visitor_device_result['message'] ? trim($visitor_device_result['message']) : '';
                    $errTipArr[] = [
                        'title' => "海康云眸：外部独立应用开发",
                        'message' => $message ?? '获取二维码失败'
                    ];
                }
            } elseif ($addCardResult['code'] == 40014001) {
                // 已经添加过
                $effective_start_time = isset($hikYunMoWaiBuInfo['effective_start_time']) && intval($hikYunMoWaiBuInfo['effective_start_time']) > 0 ? intval($hikYunMoWaiBuInfo['effective_start_time']) : 0;
                $effective_end_time = isset($hikYunMoWaiBuInfo['effective_end_time']) && intval($hikYunMoWaiBuInfo['effective_end_time']) > 0 ? intval($hikYunMoWaiBuInfo['effective_end_time']) : 0;
                $qr_content_url = isset($hikYunMoWaiBuInfo['qr_content_url']) && trim($hikYunMoWaiBuInfo['qr_content_url']) ? trim($hikYunMoWaiBuInfo['qr_content_url']) : '';
                if ($effective_end_time > $todayEndTime && $qr_content_url) {
                    $effectTimeTip = "起效：" . date('Y-m-d', $effective_start_time);
                    $expireTimeTip = "失效：" . date('Y-m-d', $effective_end_time);
                    $qrCodeInfo = isset($qrCodeInfos[$protocolType]) ? $qrCodeInfos[$protocolType] : [];
                    $includeDevices = isset($qrCodeInfo['includeDevices']) ? $qrCodeInfo['includeDevices'] : [];
                    $deviceInfo = [
                        'device_id' => $device_id, 'device_sn' => $device_sn, 'device_name' => $device_name, 'cloud_device_id' => $cloud_device_id,
                    ];
                    $includeDevices[] = $deviceInfo;
                    $qrCodeInfo['includeDevices'] = $includeDevices;
                    $qrCodeInfo['qrcodeUrl'] = $qr_content_url;
                    $qrCodeInfo['effectTimeTip'] = $effectTimeTip;
                    $qrCodeInfo['expireTimeTip'] = $expireTimeTip;
                    $qrCodeInfos[$protocolType] = $qrCodeInfo;
                } else {
                    $visitor_param = [
                        'cardNo'     => trim($cardNo), 'effectTime' => date('ymdHis', $todayStartTime),
                        'expireTime' => date('ymdHis', $todayEndTime), 'openTimes'  => $visitor_open_number_limit,
                    ];
                    $visitor_device_result = invoke_cms_model('Face_door_a5_service/getVisitorQrcode', ['param' => $visitor_param]);
                    fdump_api(['visitor_device_result' => $visitor_device_result, 'cardNo' => $cardNo,'pigcms_id' => $pigcms_id,'visitor_param'=>$visitor_param], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
                    $visitor_device_result = isset($visitor_device_result['retval']) ? $visitor_device_result['retval'] : [];
                    
                    $qrCodeUrl = isset($visitor_device_result['data']['qrCodeUrl']) && $visitor_device_result['data']['qrCodeUrl'] ? trim($visitor_device_result['data']['qrCodeUrl']) : '';
                    if ($qrCodeUrl) {
                        $device_qrcode_id = isset($hikYunMoWaiBuInfo['id']) ? intval($hikYunMoWaiBuInfo['id']) : 0;
                        $bind_arr = [
                            'device_equipment_type' => $device_equipment_type, 'third_protocol' => $thirdProtocol,
                            'device_id' => 0, 'bind_type' => DeviceQrcodeConst::BIND_TYPE_VILLAGE_USER, 'bind_id' => $pigcms_id,
                            'bind_from' => DeviceQrcodeConst::BIND_FROM_HOUSE_VILLAGE_USER_BIND, 'qr_content_txt' => trim($cardNo),
                            'qr_content_url' => trim($qrCodeUrl), 'qr_syn_time' => $nowTime, 'qr_syn_status' => 1, 'open_times' => $visitor_open_number_limit,
                            'opened_times' => 0, 'effective_start_time' => $todayStartTime, 'effective_end_time' => $todayEndTime, 'village_id' => $village_id,
                        ];
                        fdump_api(['bind_arr' => $bind_arr, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
                        if ($device_qrcode_id <= 0) {
                            $bind_arr['add_time'] = $nowTime;
                            $db_house_device_qrcode->add($bind_arr);
                        } else {
                            $db_house_device_qrcode->updateThis(['id' => $device_qrcode_id], $bind_arr);
                        }
                        $effectTimeTip = "起效：" . date('Y-m-d', $todayStartTime);
                        $expireTimeTip = "失效：" . date('Y-m-d', $todayEndTime);
                        $qrCodeInfo = isset($qrCodeInfos[$protocolType]) ? $qrCodeInfos[$protocolType] : [];
                        $includeDevices = isset($qrCodeInfo['includeDevices']) ? $qrCodeInfo['includeDevices'] : [];
                        $deviceInfo = [
                            'device_id' => $device_id, 'device_sn' => $device_sn, 'device_name' => $device_name, 'cloud_device_id' => $cloud_device_id,
                        ];
                        $includeDevices[] = $deviceInfo;
                        $qrCodeInfo['includeDevices'] = $includeDevices;
                        $qrCodeInfo['qrcodeUrl'] = $qrCodeUrl;
                        $qrCodeInfo['effectTimeTip'] = $effectTimeTip;
                        $qrCodeInfo['expireTimeTip'] = $expireTimeTip;
                        $qrCodeInfos[$protocolType] = $qrCodeInfo;
                    } else {
                        $message = isset($visitor_device_result['message']) && $visitor_device_result['message'] ? trim($visitor_device_result['message']) : '';
                        $errTipArr[] = [
                            'title' => "海康云眸：外部独立应用开发",
                            'message' => $message ?? '获取二维码失败'
                        ];
                    }
                }
            } else {
                $errTipArr[] = [
                    'title' => "海康云眸：外部独立应用开发",
                    'message' => isset($addCardResult['message']) ? trim($addCardResult['message']) : '获取二维码失败'
                ];
            }
        }
        fdump_api(['qrCodeInfos' => $qrCodeInfos, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
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
        fdump_api(['qrCodeInfos' => $qrCodeInfos, 'pigcms_id' => $pigcms_id], 'hikGateWay/addDeviceQrCodeBoxLog', 1);
        return [
            'title' => "请将对应设备二维码对准设备识别位置", 'tip' => "点击图片可刷新", 'qrCodeInfos' => $qrCodeInfos, 
            'errTipArr' => $errTipArr, 'village_name' => $village_name
        ];
    }

    /**
     * 获取开门次数.
     */
    public function getOpenNumberMaxLimit($village_id)
    {
        $village_vistor_open_number_max_limit = cfg('village_vistor_open_number_max_limit') ? cfg('village_vistor_open_number_max_limit') : 9;
        $getFaceCloud260Judge = (new ConfigCustomizationService())->getFaceCloud260Judge();
        $getFaceXiHanDoorJudge = (new ConfigCustomizationService())->getFaceXiHanDoorSwitch();
        if (intval($village_vistor_open_number_max_limit) < 1) {
            $village_vistor_open_number_max_limit = ($getFaceCloud260Judge || $getFaceXiHanDoorJudge) ? 24 : 9;
        }
        $db_house_village_config = new HouseVillageConfig();
        $whereVillageConfig = [];
        $whereVillageConfig[] = ['village_id', '=', $village_id];
        $visitor_open_number_limit = $db_house_village_config->getColumn($whereVillageConfig, 'vistor_open_number_limit');
        if (!$visitor_open_number_limit || $visitor_open_number_limit < 1) {
            $visitor_open_number_limit = ($getFaceCloud260Judge || $getFaceXiHanDoorJudge) ? 24 : 1;
        } elseif ($visitor_open_number_limit > $village_vistor_open_number_max_limit) {
            $visitor_open_number_limit = $village_vistor_open_number_max_limit;
        }
        return $visitor_open_number_limit;
    }

    public function inParkVisitorToDevice($visitor_info) 
    {
        try {
            $id = isset($visitor_info['id']) ? intval($visitor_info['id']) : 0;
            if ($id <= 0) {
                return false;
            }
            $usersParams = $visitor_info;
            $usersParams['handleType'] = 'inParkVisitorToDeviceBox';
            return $this->traitCommonHikDeviceGateWay($usersParams);
        } catch (\Exception $e){
            fdump_api([ 'msg' => '入场自动添加访客身份-异常错误', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errInParkVisitorToDevice', 1);
        } catch (\Exception $e) {
            fdump_api([ 'msg' => '入场自动添加访客身份-异常错误', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errInParkVisitorToDevice', 1);
        } catch (\Throwable $e) {
            fdump_api([ 'msg' => '入场自动添加访客身份-异常错误', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errInParkVisitorToDevice', 1);
        } catch (\RuntimeException $e) {
            fdump_api([ 'msg' => '入场自动添加访客身份-异常错误', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errInParkVisitorToDevice', 1);
        } catch (\InvalidArgumentException $e) {
            fdump_api([ 'msg' => '入场自动添加访客身份-异常错误', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errInParkVisitorToDevice', 1);
        }
        return true;
    }
    
    /**
     * 入场自动添加访客身份.
     */
    public function inParkVisitorToDeviceBox($visitor_info)
    {
        $id = isset($visitor_info['id']) ? intval($visitor_info['id']) : 0;
        if ($id <= 0) {
            return false;
        }
        $db_house_visitor = new HouseVillageVisitor();
        $whereVisitor = [];
        $whereVisitor[] = ['id', '=', $id];
        $visitorField = "id, village_id, visitor_bind_id, visit_owner_switch, visitor_name, visitor_phone, single_id, floor_id, layer_id, vacancy_id";
        $visitorInfo = $db_house_visitor->getOne($whereVisitor, $visitorField);
        $visit_owner_switch = isset($visitorInfo['visit_owner_switch']) ? intval($visitorInfo['visit_owner_switch']) : 0;
        if ($visit_owner_switch < 1) {
            return false;
        }
        $nowTime = time();
        $visitor_bind_id = isset($visitorInfo['visitor_bind_id']) ? intval($visitorInfo['visitor_bind_id']) : 0;
        $db_house_village_user_bind = new HouseVillageUserBind();
        if ($visitor_bind_id > 0) {
            $whereUserBind = [];
            $whereUserBind[] = ['pigcms_id', '=', $visitor_bind_id];
            $property_starttime = strtotime(date('Y-m-d 00:00:00', $nowTime));
            $property_endtime = 2145887999;
            $update = [];
            $update['property_starttime'] = $property_starttime;
            $update['property_endtime'] = $property_endtime;
            $db_house_village_user_bind->saveOne($whereUserBind, $update);
            $village_id = isset($visitorInfo['village_id']) ? intval($visitorInfo['village_id']) : 0;
            $updateVisitor = [];
            if ($visit_owner_switch == 1) {
                $authParams = ['village_id' => $village_id];
                $visitToDevices = (new HouseDeviceQrcodeService())->getAllOpenDeviceAuth($authParams);
                $visitToDeviceString = implode(',', $visitToDevices);
                $updateVisitor['visit_to_devices'] = $visitToDeviceString;
                $db_house_visitor->saveOne($whereVisitor, $updateVisitor);
            }
        } else {
            $village_id = isset($visitorInfo['village_id']) ? intval($visitorInfo['village_id']) : 0;
            $single_id = isset($visitorInfo['single_id']) ? intval($visitorInfo['single_id']) : 0;
            $floor_id = isset($visitorInfo['floor_id']) ? intval($visitorInfo['floor_id']) : 0;
            $layer_id = isset($visitorInfo['layer_id']) ? intval($visitorInfo['layer_id']) : 0;
            $vacancy_id = isset($visitorInfo['vacancy_id']) ? intval($visitorInfo['vacancy_id']) : 0;
            
            $name = isset($visitorInfo['visitor_name']) ? trim($visitorInfo['visitor_name']) : '';
            $phone = isset($visitorInfo['visitor_phone']) ? trim($visitorInfo['visitor_phone']) : '';
            $sHouseFaceService = new HouseFaceService();
            $uid = $sHouseFaceService->getFaceToRegUserUid($name, $phone);
            $nowTimeText = date('YmdHis', $nowTime);
            $usernum = md5(uniqid() . $nowTime) . $nowTimeText;
            $arr = [
                'village_id' => $village_id, 'uid' => $uid, 'phone' => $phone, 'name' => $name, 'type' => 6,
                'usernum' => $usernum, 'single_id' => $single_id, 'floor_id' => $floor_id, 'layer_id' => $layer_id,
                'vacancy_id' => $vacancy_id, 'add_time' => $nowTime
            ];
            $property_starttime = strtotime(date('Y-m-d 00:00:00', $nowTime));
            $property_endtime = 2145887999;
            $arr['property_starttime'] = $property_starttime;
            $arr['property_endtime'] = $property_endtime;
            $visitor_bind_id = $db_house_village_user_bind->addOne($arr);
            if ($visitor_bind_id) {
                $updateVisitor = [];
                $updateVisitor['visitor_bind_id'] = $visitor_bind_id;
                if ($visit_owner_switch == 1) {
                    $authParams = ['village_id' => $village_id];
                    $visitToDevices = (new HouseDeviceQrcodeService())->getAllOpenDeviceAuth($authParams);
                    $visitToDeviceString = implode(',', $visitToDevices);
                    $updateVisitor['visit_to_devices'] = $visitToDeviceString;
                }
                $db_house_visitor->saveOne($whereVisitor, $updateVisitor);
            }
        }
        if ($visitor_bind_id > 0) {
            // todo 如果走的是队列 执行下添加二维码
            (new HouseDeviceQrcodeService())->addDeviceQrCode($visitor_bind_id);
        }
        return true;
    }

    public function outParkVisitorToDevice($visitor_info)
    {
        try {
            $id = isset($visitor_info['id']) ? intval($visitor_info['id']) : 0;
            if ($id <= 0) {
                return false;
            }
            $usersParams = $visitor_info;
            $usersParams['handleType'] = 'outParkVisitorToDeviceBox';
            return $this->traitCommonHikDeviceGateWay($usersParams);
        } catch (\Exception $e){
            fdump_api([ 'msg' => '出场自动移除设备二维码-异常错误', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errOutParkVisitorToDevice', 1);
        } catch (\Exception $e) {
            fdump_api([ 'msg' => '出场自动移除设备二维码-异常错误', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errOutParkVisitorToDevice', 1);
        } catch (\Throwable $e) {
            fdump_api([ 'msg' => '出场自动移除设备二维码-异常错误', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errOutParkVisitorToDevice', 1);
        } catch (\RuntimeException $e) {
            fdump_api([ 'msg' => '出场自动移除设备二维码-异常错误', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errOutParkVisitorToDevice', 1);
        } catch (\InvalidArgumentException $e) {
            fdump_api([ 'msg' => '出场自动移除设备二维码-异常错误', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errOutParkVisitorToDevice', 1);
        }
        return true;
    }

    /**
     * 出场自动移除设备二维码.
     */
    public function outParkVisitorToDeviceBox($visitor_info)
    {
        $id = isset($visitor_info['id']) ? intval($visitor_info['id']) : 0;
        if ($id <= 0) {
            return false;
        }
        $db_house_visitor = new HouseVillageVisitor();
        $whereVisitor = [];
        $whereVisitor[] = ['id', '=', $id];
        $visitorInfo = $db_house_visitor->getOne($whereVisitor, 'id, village_id, visitor_bind_id, visit_owner_switch');
        $visit_owner_switch = isset($visitorInfo['visit_owner_switch']) ? intval($visitorInfo['visit_owner_switch']) : 0;
        $visitor_bind_id = isset($visitorInfo['visitor_bind_id']) ? intval($visitorInfo['visitor_bind_id']) : 0;
        if ($visit_owner_switch < 1 || $visitor_bind_id <= 0) {
            return false;
        }
        $nowTime = time();
        $db_house_village_user_bind = new HouseVillageUserBind();
        if ($visitor_bind_id > 0) {
            $whereUserBind = [];
            $whereUserBind[] = ['pigcms_id', '=', $visitor_bind_id];
            $property_starttime = strtotime(date('Y-m-d 00:00:00', $nowTime));
            $property_endtime = $nowTime;
            $update = [];
            $update['property_starttime'] = $property_starttime;
            $update['property_endtime'] = $property_endtime;
            $db_house_village_user_bind->saveOne($whereUserBind, $update);
            
            $db_house_device_qrcode = new HouseDeviceQrcode();
            $device_equipment_type = DeviceConst::DEVICE_TYPE_FACE;
            $whereQrcode = [];
            $whereQrcode[] = ['device_equipment_type', '=', $device_equipment_type];
            $whereQrcode[] = ['bind_type', '=', DeviceQrcodeConst::BIND_TYPE_VILLAGE_USER];
            $whereQrcode[] = ['bind_from', '=', DeviceQrcodeConst::BIND_FROM_HOUSE_VILLAGE_USER_BIND];
            $whereQrcode[] = ['bind_id', '=', $visitor_bind_id];
            $whereQrcode[] = ['delete_time', '=', 0];
            $update = [];
            $update['effective_start_time'] = $property_starttime;
            $update['effective_end_time'] = $property_starttime;
            $db_house_device_qrcode->updateThis($whereQrcode, $update);
            // todo 如果走的是队列 执行下移除二维码
            $overTime = $nowTime - 1;
            $this->over_time_delete_device_qrcode($overTime);
        }
        return true;
    }

    /**
     * 获取访客访问非业主设备权限.
     */
    public function getVisitorAuthList($params) 
    {
        $page = isset($params['page']) ? intval($params['page']) : 1;
        $pageSize = isset($params['pageSize']) ? intval($params['pageSize']) : 15;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        
        $db_house_face_device = new HouseFaceDevice();
        $thirdProtocolArr = [0, DeviceConst::HIK_YUNMO_WAIBU, DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM];
        $whereFace = [];
        $whereFace[] = ['village_id', '=', $village_id];
        $whereFace[] = ['device_type', '=', 5];
        $whereFace[] = ['thirdProtocol', 'in', $thirdProtocolArr];
        $whereFace[] = ['is_del', '=', 0];
        $deviceField = 'device_id, device_name, device_type, device_sn, village_id, device_direction, public_area_id, floor_id';
        $list = $db_house_face_device->getWhereList($whereFace, $deviceField, 'device_id DESC', $page, $pageSize);
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        if (!empty($list)) {
            $deviceIds = array_unique(array_column($list, 'device_id'));
            $publicAreaIds = array_unique(array_column($list, 'public_area_id'));
            
            $db_House_village_public_area = new HouseVillagePublicArea();
            $wherePublic = [];
            $wherePublic[] = ['public_area_id', 'in', $publicAreaIds];
            $publicAreaInfoArr = $db_House_village_public_area->getColumn($wherePublic, 'public_area_id, public_area_name', 'public_area_id');
            
            $dbHouseDeviceVisitorAuth = new HouseDeviceVisitorAuth();
            $whereAuth = [];
            $whereAuth[] = ['village_id', '=', $village_id];
            $whereAuth[] = ['device_id', 'in', $deviceIds];
            $authColumnList = $dbHouseDeviceVisitorAuth->getColumn($whereAuth, 'id, village_id, device_id, has_open_auth', 'device_id');
            
            $db_house_village_floor = new HouseVillageFloor();
            $whereFloor = [];
            $whereFloor[] = ['village_id', '=', $village_id];
            $whereFloor[] = ['status', '<>', 4];
            $floorInfoArr = $db_house_village_floor->getOneColumn($whereFloor, 'floor_id, village_id, floor_name, single_id', 'floor_id');

            $singleIds = array_unique(array_column($floorInfoArr, 'single_id'));
            $db_house_village_single = new HouseVillageSingle();
            $whereSingle = [];
            $whereSingle[] = ['village_id', '=', $village_id];
            $whereSingle[] = ['id', 'in', $singleIds];
            $singleInfoArr = $db_house_village_single->getOneColumn($whereSingle, 'id, village_id, single_name', 'id');

            // $this->face_device_type[$face_info['device_type']];
            $sAockpitService = new AockpitService();
            $face_device_type = $sAockpitService->face_device_type;
            foreach ($list as &$item) {
                $device_id = isset($item['device_id']) ? intval($item['device_id']) : 0;
                $authColumnInfo = isset($authColumnList[$device_id]) ? $authColumnList[$device_id] : [];
                $item['has_open_auth'] = isset($authColumnInfo['has_open_auth']) ? intval($authColumnInfo['has_open_auth']): 0;
                $public_area_id = isset($item['public_area_id']) ? intval($item['public_area_id']) : 0;
                $floor_id = isset($item['floor_id']) ? intval($item['floor_id']) : 0;
                if ($public_area_id && isset($publicAreaInfoArr[$public_area_id])) {
                    $item['address_text'] = trim($publicAreaInfoArr[$public_area_id]['public_area_name']);
                } elseif ($floor_id == -1 || $floor_id == '-1' || $floor_id == 0 || $floor_id == '0') {
                    $house_name = trim(cfg('house_name'));
                    $item['address_text'] = $house_name . '大门';
                } else {
                    $floor_arr = explode(',', $floor_id);
                    if (count($floor_arr) == 1) {
                        $floorId0 = reset($floor_arr);
                        $floorInfo = isset($floorInfoArr[$floorId0]) ? $floorInfoArr[$floorId0] : [];
                        $single_id = isset($floorInfo['single_id']) ? intval($floorInfo['single_id']) : 0;
                        $singleInfo = isset($singleInfoArr[$single_id]) ? $singleInfoArr[$single_id] : [];
                        $floor_name = isset($floorInfo['floor_name']) ? trim($floorInfo['floor_name']) : '';
                        $single_name = isset($singleInfo['single_name']) ? trim($singleInfo['single_name']) : '';
                        $item['address_text'] = $single_name . $floor_name;
                    } else {
                        $floorId0 = reset($floor_arr);
                        $floorInfo = isset($floorInfoArr[$floorId0]) ? $floorInfoArr[$floorId0] : [];
                        $single_id = isset($floorInfo['single_id']) ? intval($floorInfo['single_id']) : 0;
                        $singleInfo = isset($singleInfoArr[$single_id]) ? $singleInfoArr[$single_id] : [];
                        $floor_name = isset($floorInfo['floor_name']) ? trim($floorInfo['floor_name']) : '';
                        $single_name = isset($singleInfo['single_name']) ? trim($singleInfo['single_name']) : '';
                        $item['address_text'] = $single_name . $floor_name . '等多单元门禁';
                    }
                }
                $device_direction = isset($item['device_direction']) ? intval($item['device_direction']) : 0;
                $item['device_direction_text'] = $device_direction ? '出' : '进';
                $device_type = isset($item['device_type']) ? intval($item['device_type']) : 0;
                $item['device_type_txt'] = isset($face_device_type[$device_type]) ? trim($face_device_type[$device_type]) : '';
            }
        }
        return $list;
    }

    /**
     * 获取访客访问非业主设备权限数量.
     */
    public function getVisitorAuthCount($params) 
    {
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;

        $db_house_face_device = new HouseFaceDevice();
        $thirdProtocolArr = [DeviceConst::HIK_YUNMO_WAIBU, DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM];
        $whereFace = [];
        $whereFace[] = ['village_id', '=', $village_id];
        $whereFace[] = ['thirdProtocol', 'in', $thirdProtocolArr];
        $whereFace[] = ['is_del', '=', 0];
        return $db_house_face_device->getCount($whereFace);
    }

    /**
     * 更换开关.
     */
    public function switchChangeOpenAuth($params)
    {
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $device_id = isset($params['device_id']) ? intval($params['device_id']) : 0;

        $db_house_face_device = new HouseFaceDevice();
        $whereFace = [];
        $whereFace[] = ['village_id', '=', $village_id];
        $whereFace[] = ['device_id', '=', $device_id];
        $deviceInfo = $db_house_face_device->getOne($whereFace, 'device_id, village_id, device_sn');
        $device_sn = isset($deviceInfo['device_sn']) ? trim($deviceInfo['device_sn']) : '';
        
        $dbHouseDeviceVisitorAuth = new HouseDeviceVisitorAuth();
        $whereOpenAuth = [];
        $whereOpenAuth[] = ['village_id', '=', $village_id];
        $whereOpenAuth[] = ['device_id', '=', $device_id];
        $openAuthInfo = $dbHouseDeviceVisitorAuth->getOne($whereOpenAuth, 'id, village_id, device_id, has_open_auth');
        $has_open_auth = isset($openAuthInfo['has_open_auth']) ? intval($openAuthInfo['has_open_auth']) : 0;
        $openAuthInfoId = isset($openAuthInfo['id']) ? intval($openAuthInfo['id']) : 0;
        $changeHasOpenAuth = $has_open_auth == 1 ? 0 : 1;
        $nowTime = time();
        if ($openAuthInfoId <= 0) {
            $data = [
                'village_id' => $village_id, 'device_id' => $device_id, 'device_sn' => $device_sn,
                'has_open_auth' => $changeHasOpenAuth, 'add_time' => $nowTime
            ];
            $openAuthInfoId = $dbHouseDeviceVisitorAuth->add($data);
        } else {
            $data = [
                'village_id' => $village_id, 'device_id' => $device_id, 'device_sn' => $device_sn,
                'has_open_auth' => $changeHasOpenAuth, 'update_time' => $nowTime
            ];
            $dbHouseDeviceVisitorAuth->updateThis(['id' => $openAuthInfoId], $data);
        }
        return $openAuthInfoId;
    }

    /**
     * 获取到开启设备权限.
     */
    public function getAllOpenDeviceAuth($params) 
    {
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $dbHouseDeviceVisitorAuth = new HouseDeviceVisitorAuth();
        $whereOpenAuth = [];
        $whereOpenAuth[] = ['village_id', '=', $village_id];
        $whereOpenAuth[] = ['has_open_auth', '=', 1];
        $deviceIds = $dbHouseDeviceVisitorAuth->getColumn($whereOpenAuth, 'device_id');
        return $deviceIds;
    }
    
    /**
     * 过期的二维码进行删除.
     */
    public function over_time_delete_device_qrcode($nowTime = 0)
    {
        $db_house_device_qrcode = new HouseDeviceQrcode();
        !$nowTime && $nowTime = time();
        $whereOver = [];
        $whereOver[] = ['effective_end_time', '<=', $nowTime];
        $whereOver[] = ['delete_time', '=', 0];
        $overQrcodeList = $db_house_device_qrcode->getList($whereOver);
        if ($overQrcodeList && !is_array($overQrcodeList)) {
            $overQrcodeList = $overQrcodeList->toArray();
        }
        fdump_api(['$whereOver' => $whereOver, '$overQrcodeList' => $overQrcodeList], '$overQrcodeList');
        // 目前只有人脸设备 直接查询设备相关信息
        $db_house_face_device = new HouseFaceDevice();
        $deviceIds = array_unique(array_column($overQrcodeList, 'device_id'));
        fdump_api(['$deviceIds' => $deviceIds], '$overQrcodeList', 1);
        $deviceColumnList = [];
        if (!empty($deviceIds)) {
            $whereDevice = [];
            $whereDevice[] = ['device_id', 'in', $deviceIds];
            $deviceField = "device_id, device_name, device_type, device_sn, floor_id, device_status, cloud_device_id, thirdProtocol, village_id, thirdAddress";
            $deviceColumnList = $db_house_face_device->getColumn($whereDevice, $deviceField, 'device_id');
            if ($deviceColumnList && !is_array($deviceColumnList)) {
                $deviceColumnList = $deviceColumnList->toArray();
            }
        }
        fdump_api(['$overQrcodeList' => $overQrcodeList, '$deviceColumnList' => $deviceColumnList], '$deviceColumnList', 1);
        $db_house_village_user_bind = new HouseVillageUserBind();
        
        $sHikGateWayDeviceService = new HikGateWayDeviceService();
        foreach ($overQrcodeList as $overQrcode) {
            $device_equipment_type = isset($overQrcode['device_equipment_type']) ? trim($overQrcode['device_equipment_type']) : '';
            $third_protocol = isset($overQrcode['third_protocol']) ? intval($overQrcode['third_protocol']) : 0;
            $qr_content_txt = isset($overQrcode['qr_content_txt']) ? trim($overQrcode['qr_content_txt']) : '';
            switch ($third_protocol) {
                case DeviceConst::HIK_YUNMO_WAIBU:
                    // 海康云眸外部应用
                    $cardNos = [$qr_content_txt];
                    $qr_content_txt && invoke_cms_model('Face_door_a5_service/delCard', ['cardNos' => $cardNos]);
                    break;
                case DeviceConst::HIK_DEVICE_GATEWAY:
                    // Hik Device Gateway 海康网关(平台)
                case DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM:
                    try {
                        // Hik Device Gateway 海康网关(小区)
                        $device_id = isset($overQrcode['device_id']) ? intval($overQrcode['device_id']) : 0;
                        $deviceInfo = isset($deviceColumnList[$device_id]) ? $deviceColumnList[$device_id] : [];
                        $cardInfoDelCondChange = [
                            'CardNoList' => [
                                ['cardNo' => $qr_content_txt]
                            ]
                        ];
                        $qr_content_txt && $sHikGateWayDeviceService->deleteCard($deviceInfo, $cardInfoDelCondChange);
                        $bind_from = isset($overQrcode['bind_from']) && $overQrcode['bind_from'] ? trim($overQrcode['bind_from']) : '';
                        $bind_id = isset($overQrcode['bind_id']) ? intval($overQrcode['bind_id']) : 0;
                        if ($bind_from == DeviceQrcodeConst::BIND_FROM_HOUSE_VILLAGE_USER_BIND && $bind_id) {
                            $whereUserBind = [];
                            $whereUserBind[] = ['pigcms_id', '=', $bind_id];
                            $userBindInfo = $db_house_village_user_bind->getOne($whereUserBind, 'pigcms_id, type, property_endtime');
                            $typeUser = isset($userBindInfo['type']) ? intval($userBindInfo['type']) : 0;
                            $property_endtime = isset($userBindInfo['property_endtime']) ? intval($userBindInfo['property_endtime']) : 0;
                            if ($typeUser == 6 && $property_endtime <= $nowTime) {
                                $employeeNo = [];
                                $employeeNo[] = ['employeeNo' => trim($bind_id)];
                                $sHikGateWayDeviceService->deleteUser($deviceInfo, $employeeNo);
                            }
                        }
                    } catch (\Exception $e) {
                        fdump_api(['执行错误' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(),], 'errOverTimeDeleteDeviceQrcodeLog', 1);
                    } catch (\Throwable $e) {
                        fdump_api(['执行错误' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(),], 'errOverTimeDeleteDeviceQrcodeLog', 1);
                    }
                    break;
                case 0:
                    $device_type = isset($overQrcode['device_type']) ? intval($overQrcode['device_type']) : 0;
                    if ($device_type == DeviceConst::XI_HAN_DEVICE_TYPE) {
                        $house_face_device_cmdDb = new HouseFaceDeviceCmd();
                        $village_id = isset($overQrcode['village_id']) && $overQrcode['village_id'] ? intval($overQrcode['village_id']) : 0;
                        $device_id = isset($overQrcode['device_id']) && $overQrcode['device_id'] ? intval($overQrcode['device_id']) : 0;
                        $device_sn = isset($overQrcode['device_sn']) && $overQrcode['device_sn'] ? trim($overQrcode['device_sn']) : '';
                        $id = isset($overQrcode['id']) && $overQrcode['id'] ? intval($overQrcode['id']) : 0;
                        $bind_id = isset($overQrcode['bind_id']) && $overQrcode['bind_id'] ? intval($overQrcode['bind_id']) : 0;
                        $cmdSynData = ['village_id' => $village_id, 'relate_id' => $id, 'bind_id' => $bind_id, 'cmd_name' => 'cardManager.removeCard'];
                        $cmd_data = [
                            'method' => 'cardManager.removeCard', 
                            'params' => ['ID' => $qr_content_txt]
                        ];
                        $cmdSynData['cmd_data'] = json_encode($cmd_data, JSON_UNESCAPED_UNICODE);
                        $cmdSynData['add_time'] = $nowTime;
                        $cmdSynData['device_id'] = $device_id;
                        $cmdSynData['device_sn'] = $device_sn;
                        $cmdSynData['device_type'] = DeviceConst::XI_HAN_DEVICE_TYPE;
                        $cmdSynData['device_type_str'] = 'xihan';
                        $cmdSynData['relate_type'] = 'house_device_qrcode';
                        $house_face_device_cmdDb->addOneData($cmdSynData);
                        fdump_api(['cmdSynData' => $cmdSynData], '$cmdSynData', 1);
                    }
                    break;
            }
            $id = isset($overQrcode['id']) ? intval($overQrcode['id']) : 0;
            $updateQrCode = ['delete_time' => $nowTime];
            $db_house_device_qrcode->updateThis(['id' => $id], $updateQrCode);
        }
        return true;
    }
    
    public function over_visitor_delete($nowTime = 0) {
        fdump_api(['msg' => '过期访客同步删除', 'nowTime' => $nowTime], 'hikGateWay/over_visitor_delete_log');
        !$nowTime && $nowTime = time();
        $db_house_face_device = new HouseFaceDevice();
        $whereDevice = [];
        $whereDevice[] = ['is_del', '=', 0];
        $whereDevice[] = ['thirdProtocol', 'in', [DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM]];
        $deviceField = "device_id, device_name, device_type, device_sn, floor_id, device_status, cloud_device_id, thirdProtocol, village_id, thirdAddress";
        $deviceColumnList = $db_house_face_device->getColumn($whereDevice, $deviceField, 'device_id');
        if ($deviceColumnList && !is_array($deviceColumnList)) {
            $deviceColumnList = $deviceColumnList->toArray();
        }
        $villageIds = array_unique(array_column($deviceColumnList, 'village_id'));
        if (empty($villageIds)) {
            fdump_api(['msg' => '过期访客同步删除-小区ID集合', 'villageIds' => $villageIds], 'hikGateWay/over_visitor_delete_log', 1);
            return true;
        }
        $db_house_village_user_bind = new HouseVillageUserBind();
        $whereVisitor = [];
        $whereVisitor[] = ['type', '=', 6];
        $whereVisitor[] = ['village_id', 'in', $villageIds];

        $service_config_data = new ConfigDataService();
        $hik_gate_info = $service_config_data->get_one(array('name' => 'hik_device_gateway_visitor_time'));
        $isAdd = true;
        if (isset($hik_gate_info['value']) && intval($hik_gate_info['value']) > 0) {
            $isAdd = false;
            $whereVisitor[] = ['property_endtime', '>', intval($hik_gate_info['value'])];
        }
        $whereVisitor[] = ['property_endtime', '<=', $nowTime];
        $userBindIds = $db_house_village_user_bind->getColumn($whereVisitor, 'pigcms_id');
        if (empty($userBindIds)) {
            $this->recordOverVisitorDelete($nowTime, $isAdd);
            fdump_api(['msg' => '过期访客同步删除-人员集合ID集合', 'userBindIds' => $userBindIds], 'hikGateWay/over_visitor_delete_log', 1);
            return true;
        }
        $db_house_face_sync_log = new HouseFaceSyncLog();
        $whereSyncLog = [];
        $whereSyncLog[] = ['pigcms_id', 'in', $userBindIds];
        $whereSyncLog[] = ['status', '=', 1];
        $whereSyncLog[] = ['sync_delete_time', '=', 0];
        $syncLogList = $db_house_face_sync_log->getList($whereSyncLog, 'pigcms_id, device_id');
        if ($syncLogList && !is_array($syncLogList)) {
            $syncLogList = $syncLogList->toArray();
        }
        $sHikGateWayDeviceService = new HikGateWayDeviceService();
        $deleteArr = [];
        fdump_api(['msg' => '过期访客同步删除-同步记录', 'syncLogList' => $syncLogList], 'hikGateWay/over_visitor_delete_log', 1);
        foreach ($syncLogList as $syncLog) {
            $device_id = isset($syncLog['device_id']) ? intval($syncLog['device_id']) : 0;
            $deviceInfo = isset($deviceColumnList[$device_id]) ? $deviceColumnList[$device_id] : [];
            if (!isset($deleteArr[$device_id])) {
                $deleteArr[$device_id] = [
                    'device_id' => $device_id, 'deviceInfo' => $deviceInfo, 'employeeNos' => []
                ];
            }
            $pigcms_id = isset($syncLog['pigcms_id']) ? intval($syncLog['pigcms_id']) : 0;
            $deleteArr[$device_id]['employeeNos'][] = ['employeeNo' => trim($pigcms_id)];
        }

        fdump_api(['msg' => '过期访客同步删除-待删除', 'deleteArr' => $deleteArr], 'hikGateWay/over_visitor_delete_log', 1);
        foreach ($deleteArr as $deleteItem) {
            $device_id = isset($deleteItem['device_id']) ? intval($deleteItem['device_id']) : 0;
            $deviceInfo = isset($deleteItem['deviceInfo']) ? $deleteItem['deviceInfo'] : [];
            !$deviceInfo && $deviceInfo = isset($deviceColumnList[$device_id]) ? $deviceColumnList[$device_id] : [];
            $employeeNos = isset($deleteItem['employeeNos']) ? array_values(array_unique($deleteItem['employeeNos'])) : [];
            $result = $sHikGateWayDeviceService->deleteUser($deviceInfo, $employeeNos);
            fdump_api(['msg' => '过期访客同步删除-待删除', 'result' => $result], 'hikGateWay/over_visitor_delete_log', 1);
        }
        $this->recordOverVisitorDelete($nowTime, $isAdd);
    }

    protected function recordOverVisitorDelete($nowTime, $isAdd)
    {
        $service_config_data = new ConfigDataService();
        if (!$isAdd) {
            $config_data = array('value' => $nowTime);
            $where = [];
            $where[] = ['name', '=', 'hik_device_gateway_visitor_time'];
            $service_config_data->updateConfig($where, $config_data);
        } else {
            $config_data = array('name' => 'hik_device_gateway_visitor_time', 'value' => $nowTime);
            $service_config_data->addConfig($config_data);
        }
        return true;
    }
}