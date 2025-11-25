<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      海康网关-监控+门禁
 */

namespace app\community\model\service\Device;

use app\community\model\db\CameraDeviceBind;
use app\community\model\db\Device\HouseDeviceGateway;
use app\community\model\db\Device\HouseDeviceNetwork;
use app\community\model\db\Device\HouseDeviceQrcode;
use app\community\model\db\Device\HouseFaceDeviceAbout;
use app\community\model\db\FaceUserBindDevice;
use app\community\model\db\HouseCameraDevice;
use app\community\model\db\HouseDeviceChannel;
use app\community\model\db\HouseFaceDevice;
use app\community\model\service\HouseFaceDeviceService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\OwnerService;
use app\community\model\service\VisitorInviteService;
use app\consts\DeviceConst;
use app\consts\DeviceQrcodeConst;
use app\consts\HikConst;
use app\consts\SynOverviewConst;
use app\traits\device\HikGateWayTraits;
use app\traits\house\DeviceTraits;
use app\traits\house\DeviceUserTraits;
use Fastwhal\HikDeviceGateway\BuiltInConstants;
use Fastwhal\HikDeviceGateway\Gateway;
use think\facade\Cache;
use think\facade\Db;

class HikGateWayDeviceService
{
    use DeviceTraits,DeviceUserTraits,HikGateWayTraits;
    public $hikGate;

    public function addDeviceBox($params) {
        try {
            return $this->addDevice($params);
        } catch (\Exception $e){
            fdump_api([ 'msg' => '同步设备异常捕捉盒子', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errAddDeviceBoxLog', 1);
        }
        return false;
    }
    
    public function addDevice($params)
    {
        fdump_api([ 'msg' => '添加更新设备-同步', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/addDeviceLog');
        $device_equipment_type = isset($params['deviceType']) ? trim($params['deviceType']) : '';
        $device_id = isset($params['device_id']) ? intval($params['device_id']) : 0;
        $device_sn = isset($params['device_sn']) ? trim($params['device_sn']) : '';
        $face_device_type = isset($params['device_type']) ? trim($params['device_type']) : '';
        $thirdProtocol = isset($params['thirdProtocol']) ? intval($params['thirdProtocol']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $device = $this->handleDeviceParams($device_equipment_type, $device_id, $device_sn, $face_device_type, $thirdProtocol, $village_id);
        if (isset($device['code']) && $device['code']) {
            fdump_api([ 'msg' => '添加更新设备-错误', 'line' => __LINE__, 'device' => $device], 'hikGateWay/addDeviceLog', 1);
            return $device;
        }
        $deviceInfo = isset($device['deviceInfo']) ? $device['deviceInfo'] : [];
        $syn_type = '';
        switch ($device_equipment_type) {
            case DeviceConst::DEVICE_TYPE_CAMERA:
                $devType = BuiltInConstants::DEVICE_TYPE_ENCODEING;
                $syn_type = SynOverviewConst::SYN_CAMERA_DEVICE;
                $EhomeID = isset($deviceInfo['camera_sn']) && trim($deviceInfo['camera_sn']) ? trim($deviceInfo['camera_sn']) : '';
                $EhomeKey = isset($deviceInfo['thirdLoginPassword']) && trim($deviceInfo['thirdLoginPassword']) ? trim($deviceInfo['thirdLoginPassword']) : '';
                $devName = isset($deviceInfo['camera_name']) && trim($deviceInfo['camera_name']) ? trim($deviceInfo['camera_name']) : '';
                $gatewayinfo = isset($deviceInfo['gatewayinfo']) && $deviceInfo['gatewayinfo'] ? json_decode($deviceInfo['gatewayinfo'], 1) : [];
                break;
            case DeviceConst::DEVICE_TYPE_FACE:
                $devType = BuiltInConstants::DEVICE_TYPE_ACCESSCONTROL;
                $syn_type = SynOverviewConst::SYN_FACE_DEVICE;
                $EhomeID = isset($deviceInfo['device_sn']) && trim($deviceInfo['device_sn']) ? trim($deviceInfo['device_sn']) : '';
                $EhomeKey = isset($deviceInfo['thirdLoginPassword']) && trim($deviceInfo['thirdLoginPassword']) ? trim($deviceInfo['thirdLoginPassword']) : '';
                $devName = isset($deviceInfo['device_name']) && trim($deviceInfo['device_name']) ? trim($deviceInfo['device_name']) : '';
                $dbHouseFaceDeviceAbout = new HouseFaceDeviceAbout();
                $aboutInfo = $dbHouseFaceDeviceAbout->getOne(['device_id' => $device_id], 'device_id, gatewayinfo');
                $deviceInfo['gatewayinfo'] = isset($aboutInfo['gatewayinfo']) && $aboutInfo['gatewayinfo'] ? $aboutInfo['gatewayinfo'] : '';
                $gatewayinfo = isset($aboutInfo['gatewayinfo']) && $aboutInfo['gatewayinfo'] ? json_decode($aboutInfo['gatewayinfo'], 1) : [];
                empty($gatewayinfo) && $gatewayinfo = isset($deviceInfo['thirdAddress']) && $deviceInfo['thirdAddress'] ? json_decode($deviceInfo['thirdAddress'], 1) : [];
                break;
        }
        $handleParams = $params;
        $handleParams['syn_type'] = $syn_type;
        $handleParams['syn_id'] = $device_id;
        $handleParams['to_id'] = 0;
        $handleParams['nowTime'] = time();
        $handleParams['funs'] = __FUNCTION__;
        if (empty($deviceInfo)) {
            $handleParams['line'] = __LINE__;
            $handleParams['syn_status'] = SynOverviewConst::SYN_NON_EXISTENT;
            $handleParams['syn_msg'] = "同步失败(设备信息不存在)";
            (new SynOverviewService())->handleSynOverview($handleParams);
            return $this->backData($params, '设备信息不存在', 1044);
        }
        $config = $this->getConfig($deviceInfo);
        if (!$config['protocol'] || !$config['host'] || !$config['port'] || !$config['username'] || !$config['password']) {
            $handleParams['line'] = __LINE__;
            $handleParams['syn_status'] = SynOverviewConst::SYN_NON_CONFIG;
            $handleParams['syn_msg'] = "同步失败(缺少相关网关配置)";
            $handleParams['config'] = $config;
            $handleParams['gatewayinfo'] = $gatewayinfo;
            (new SynOverviewService())->handleSynOverview($handleParams);
            return $this->backData($params, '缺少相关网关配置', 1006);
        }
        fdump_api([ 'msg' => '添加更新设备-配置', 'line' => __LINE__, 'gatewayinfo' => $gatewayinfo, 'config' => $config, 'deviceInfo' => $deviceInfo], 'hikGateWay/addDeviceLog', 1);
        $protocolType = isset($gatewayinfo['isup_version']) && $gatewayinfo['isup_version'] ? $gatewayinfo['isup_version'] : '';
        if (!$protocolType || !$EhomeID || !$EhomeKey || !$devName || !$devType) {
            $handleParams['line'] = __LINE__;
            $handleParams['syn_status'] = SynOverviewConst::SYN_NON_PARAM;
            $handleParams['syn_msg'] = "同步失败(当前设备缺少必要参数)";
            $handleParams['deviceInfo'] = $deviceInfo;
            (new SynOverviewService())->handleSynOverview($handleParams);
            return $this->backData($params, '当前设备缺少必要参数', 1022);
        }
        if (!$this->hikGate) {
            $this->hikGate = new Gateway($config);
        }
        $nowTime = time();
        if (isset($deviceInfo['cloud_device_id']) && $deviceInfo['cloud_device_id']) {
            // 已经存在了云设备id  走编辑
            $deviceParams = [
                'devIndex' => trim($deviceInfo['cloud_device_id']), 'protocolType' => $protocolType,
                'EhomeParams' => ['EhomeID' => $EhomeID, 'EhomeKey' => $EhomeKey],
                'devName' => $devName, 'devType' => $devType
            ];
            $resultParams = $deviceParams;
            $resultParams['DeviceMgmt'] = 'updateDevice';
            $resultParams['config'] = $config;
            $result = $this->requestResult($resultParams);
            fdump_api([ 'msg' => '添加更新设备-更新', 'line' => __LINE__, 'deviceParams' => $deviceParams, 'resultParams' => $resultParams, 'result' => $result], 'hikGateWay/addDeviceLog', 1);
            $redis = true;
            $fail = true;
            $tryCatchErr = false;
            if (empty($result)) {
                $redis = false;
                try {
                    $result = $this->hikGate->DeviceMgmt->updateDevice($deviceParams);
                    fdump_api([ 'msg' => '添加更新设备-更新接口', 'line' => __LINE__, 'result' => $result], 'hikGateWay/addDeviceLog', 1);
                } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
                    $resoponse = \json_decode( $exception->getResponse()->getBody()->getContents(),true);
                    $tryCatchErr = true;
                    fdump_api([ 'msg' => '添加更新设备-异常捕捉', 'line' => __LINE__, 'resoponse' => $resoponse], 'hikGateWay/addDeviceLog', 1);
                    $statusCode = $resoponse['statusCode'];
                    $subStatusCode = $resoponse['subStatusCode'];
                    if ($subStatusCode != 'ok') {
                        $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
                        $handleParams['line'] = __LINE__;
                        $handleParams['syn_status'] = SynOverviewConst::SYN_ERR_FROM_DEVICE_CLOUD;
                        if ($subStatusCode == 'badParameters') {
                            // 参数错误 提供解题思路
                            $errmsg .= "[检查协议类型是否和设备实际协议类型对应或者查看是否单独在海康网关(平台)删除了对应设备]";
                        }
                        $handleParams['syn_msg'] = "同步失败({$errmsg})";
                        $handleParams['deviceInfo'] = $deviceInfo;
                        $handleParams['interfaceName'] = 'ContentMgmt/DeviceMgmt/modDevice';
                        $handleParams['statusCode'] = $statusCode;
                        $handleParams['subStatusCode'] = $subStatusCode;
                        (new SynOverviewService())->handleSynOverview($handleParams);
                        return $this->backData($params, $errmsg, 1019);
                    }
                }
                $result = $this->requestResult($resultParams, $result);
            }
            $result['redis'] = true;
            $result['fail'] = $fail;
            if (!$tryCatchErr) {
                $statusCode = isset($result['statusCode']) ? $result['statusCode'] : 0;
                $subStatusCode = isset($result['subStatusCode']) ? trim($result['subStatusCode']) : '';
            }
            if (intval($statusCode) == 1 && trim($subStatusCode) == 'ok') {
                // 同步更新下 监控表中数据
                $fail = false;
                fdump_api([ 'msg' => '添加更新设备-记录', 'line' => __LINE__, 'device_equipment_type' => $device_equipment_type], 'hikGateWay/addDeviceLog', 1);
                switch ($device_equipment_type) {
                    case DeviceConst::DEVICE_TYPE_CAMERA:
                        $houseFaceDeviceService = new HouseFaceDeviceService();
                        $whereCamera = [
                            'camera_id' => $device_id
                        ];
                        $updateParam = [
                            'login_time' => $nowTime,
                        ];
                        $gatewayinfo = array_merge($gatewayinfo, $config);
                        if (!empty($gatewayinfo)) {
                            $updateParam['gatewayinfo'] = json_encode($gatewayinfo, JSON_UNESCAPED_UNICODE);
                        }
                        $houseFaceDeviceService->saveCamera($whereCamera, $updateParam);
                        fdump_api([ 'msg' => '记录', 'line' => __LINE__, 'whereCamera' => $whereCamera, 'updateParam' => $updateParam], 'hikGateWay/addDeviceLog', 1);
                        break;
                    case DeviceConst::DEVICE_TYPE_FACE:
                        $dbHouseFaceDevice = new HouseFaceDevice();
                        $whereFace = [
                            'device_id' => $device_id
                        ];
                        $updateParam = [
                            'last_time'  => $nowTime,
                            'login_time' => $nowTime,
                            'notify_time' => $nowTime,
                        ];
                        $device_param = isset($deviceInfo['thirdAddress']) && $deviceInfo['thirdAddress'] ? json_decode($deviceInfo['thirdAddress'], 1) : [];
                        $device_param = array_merge($device_param, $config);
                        $updateParam['thirdAddress'] = json_encode($device_param, JSON_UNESCAPED_UNICODE);
                        $dbHouseFaceDevice->saveData($whereFace, $updateParam);
                        fdump_api([ 'msg' => '记录', 'line' => __LINE__, 'whereFace' => $whereFace, 'updateParam' => $updateParam], 'hikGateWay/addDeviceLog', 1);
                        $dbHouseFaceDeviceAbout = new HouseFaceDeviceAbout();
                        $whereAbout = ['device_id' => $device_id];
                        $aboutInfo = $dbHouseFaceDeviceAbout->getOne(['device_id' => $device_id], 'device_id, gatewayinfo');
                        $gatewayinfo = isset($aboutInfo['gatewayinfo']) && $aboutInfo['gatewayinfo'] ? json_decode($aboutInfo['gatewayinfo'], 1) : [];
                        $gatewayinfo = array_merge($gatewayinfo, $config);
                        $updateParam = [];
                        $updateParam['last_dev_sync_time'] = $nowTime;
                        if (!empty($gatewayinfo)) {
                            $updateParam['gatewayinfo'] = json_encode($gatewayinfo, JSON_UNESCAPED_UNICODE);
                        }
                        $dbHouseFaceDeviceAbout->updateThis($whereAbout, $updateParam);
                        fdump_api([ 'msg' => '记录', 'line' => __LINE__, 'whereAbout' => $whereAbout, 'updateParam' => $updateParam], 'hikGateWay/addDeviceLog', 1);
                        break;
                }
                $handleParams['line'] = __LINE__;
                $handleParams['syn_status'] = SynOverviewConst::SYN_SUCCESS;
                $handleParams['syn_msg'] = "同步成功[更新]";
                $handleParams['deviceInfo'] = $deviceInfo;
                $handleParams['interfaceName'] = 'ContentMgmt/DeviceMgmt/modDevice';
                $handleParams['statusCode'] = $statusCode;
                $handleParams['subStatusCode'] = $subStatusCode;
                (new SynOverviewService())->handleSynOverview($handleParams);
                // todo gateway 要走队列执行 获取通道信息.
                $params = [
                    'device_equipment_type' => $device_equipment_type, 'village_id' => $village_id, 'device_id' => $device_id,
                    'device_sn' => $device_sn, 'nowTime' => $nowTime, 'thirdProtocol' => $thirdProtocol,
                ];

                $subscribeDeviceMgmtParams = $params;
                $subscribeDeviceMgmtParams['deviceInfo'] = $deviceInfo;
                $subscribeDeviceMgmtParams['handleType'] = 'subscribeDeviceMgmt';
                $this->traitCommonHikDeviceGateWay($subscribeDeviceMgmtParams);
                
                if ($device_equipment_type == DeviceConst::DEVICE_TYPE_CAMERA) {
//            (new HikGateWayDeviceService())->getChannelsBox($device, $params);
                    $this->getChannelsBox($deviceInfo, $params);
                }
                if ($device_equipment_type == DeviceConst::DEVICE_TYPE_FACE) {
//            (new HikGateWayDeviceService())->getHikGatewayDeviceInfoBox($device, $params);
                    $this->getHikGatewayDeviceInfoBox($deviceInfo, $params);
                    $this->getHttpHosts($deviceInfo, $params);
                }
                // todo gateway 要走队列执行 获取通道信息.
//            (new HikGateWayDeviceService())->getDeviceNetworkBox($deviceInfo, $params);
                $this->getDeviceNetworkBox($deviceInfo, $params);
                return $result;
            } else {
                $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
                $handleParams['line'] = __LINE__;
                $handleParams['syn_status'] = SynOverviewConst::SYN_ERR_FROM_DEVICE_CLOUD;
                $handleParams['syn_msg'] = "同步失败({$errmsg})";
                $handleParams['deviceInfo'] = $deviceInfo;
                $handleParams['interfaceName'] = 'ContentMgmt/DeviceMgmt/modDevice';
                $handleParams['statusCode'] = $statusCode;
                $handleParams['subStatusCode'] = $subStatusCode;
                (new SynOverviewService())->handleSynOverview($handleParams);
                return $this->backData($params, $errmsg, 1019);
            }
        } else {
            $deviceParams = [
                [
                    'Device' => [
                        'protocolType' => $protocolType,
                        'EhomeParams' => ['EhomeID' => $EhomeID, 'EhomeKey' => $EhomeKey],
                        'devName' => $devName, 'devType' => $devType
                    ]
                ]
            ];
            $resultParams = $deviceParams;
            $resultParams['DeviceMgmt'] = 'addDevice';
            $resultParams['config'] = $config;
            $result = $this->requestResult($resultParams);
            fdump_api([ 'msg' => '添加更新设备-添加', 'line' => __LINE__, 'deviceParams' => $deviceParams, 'resultParams' => $resultParams, 'result' => $result], 'hikGateWay/addDeviceLog', 1);
            $redis = true;
            $fail = true;
            $tryCatchErr = false;
            $devIndex = '';
            $devName = '';
            $statusCode = '';
            $subStatusCode = '';
            if (empty($result)) {
                $redis = false;
                try {
                    $result = $this->hikGate->DeviceMgmt->addDevice($deviceParams);
                    fdump_api([ 'msg' => '添加更新设备-添加接口', 'line' => __LINE__, 'result' => $result], 'hikGateWay/addDeviceLog', 1);
                } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
                    $resoponse = \json_decode( $exception->getResponse()->getBody()->getContents(),true);
                    $statusCode = $resoponse['statusCode'];
                    if (isset($resoponse['DeviceOutList']) && $resoponse['DeviceOutList']) {
                        // todo gateway 注意目前这里的写法只考虑同步批量添加一台设备 如果改成多台请单独自行处理
                        $deviceOutList = $resoponse['DeviceOutList'];
                        $deviceOutInfo = reset($deviceOutList);
                        $device = isset($deviceOutInfo['Device']) ? $deviceOutInfo['Device'] : [];
                        $devIndex = isset($device['devIndex']) ? trim($device['devIndex']) : '';
                        $device = isset($deviceOutInfo['Device']) ? $deviceOutInfo['Device'] : [];
                        $subStatusCode = isset($device['subStatusCode']) ? trim($device['subStatusCode']) : '';
                        $ehomeID = isset($device['EhomeParams']['EhomeID']) ? trim($device['EhomeParams']['EhomeID']) : '';
                        $devName = isset($device['devName']) ? trim($device['devName']) : '';
                        $addResult = [
                            'cloud_device_id' => $devIndex, 'ehomeID' => $ehomeID, 'login_time' => $nowTime
                        ];
                    } else {
                        $subStatusCode = $resoponse['subStatusCode'];
                    }
                    $tryCatchErr = true;
                }
                $result = $this->requestResult($resultParams, $result);
            }
            $result['redis'] = true;
            $result['fail'] = $fail;
            fdump_api([ 'msg' => '添加更新设备', 'line' => __LINE__, 'tryCatchErr' => $tryCatchErr, 'statusCode' => $statusCode, 'subStatusCode' => $subStatusCode], 'hikGateWay/addDeviceLog', 1);
            if ($tryCatchErr && $subStatusCode != 'deviceExist' && $subStatusCode != 'ok' && !$devIndex) {
                // 捕捉到异常且不是设备已存在报错 直接反馈错误
                $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
                $handleParams['line'] = __LINE__;
                $handleParams['syn_status'] = SynOverviewConst::SYN_ERR_FROM_DEVICE_CLOUD;
                if ($subStatusCode == 'badParameters') {
                    // 参数错误 提供解题思路
                    $errmsg .= "[检查协议类型是否和设备实际协议类型对应或者查看是否单独在海康网关(平台)删除了对应设备]";
                }
                $handleParams['syn_msg'] = "同步失败({$errmsg})";
                $handleParams['deviceInfo'] = $deviceInfo;
                $handleParams['interfaceName'] = 'ContentMgmt/DeviceMgmt/addDevice';
                $handleParams['statusCode'] = $statusCode;
                $handleParams['subStatusCode'] = $subStatusCode;
                (new SynOverviewService())->handleSynOverview($handleParams);
                return $this->backData($params, $errmsg, 1019);
            }
            if (!$tryCatchErr) {
                $statusCode = isset($result['statusCode']) ? $result['statusCode'] : 0;
                if (isset($result['DeviceOutList']) && $result['DeviceOutList']) {
                    // todo gateway 注意目前这里的写法只考虑同步批量添加一台设备 如果改成多台请单独自行处理
                    $deviceOutList = $result['DeviceOutList'];
                    $deviceOutInfo = reset($deviceOutList);
                    $device = isset($deviceOutInfo['Device']) ? $deviceOutInfo['Device'] : [];
                    $devIndex = isset($device['devIndex']) ? trim($device['devIndex']) : '';
                    $subStatusCode = isset($device['subStatusCode']) ? trim($device['subStatusCode']) : '';
                    $ehomeID = isset($device['EhomeParams']['EhomeID']) ? trim($device['EhomeParams']['EhomeID']) : '';
                    $devName = isset($device['devName']) ? trim($device['devName']) : '';
                    $addResult = [
                        'cloud_device_id' => $devIndex, 'ehomeID' => $ehomeID, 'login_time' => $nowTime, 'notify_time' => $nowTime,
                    ];
                }
            } elseif (!isset($addResult) || empty($addResult)) {
                $addResult = ['login_time' => $nowTime, 'notify_time' => $nowTime];
            }
            fdump_api([ 'msg' => '添加更新设备', 'line' => __LINE__, 'addResult' => $addResult], 'hikGateWay/addDeviceLog', 1);
            if ($subStatusCode == 'deviceExist') {
                // todo gateway 报错设备已经存在了 走下查询逻辑
                $params['deviceInfo'] = $deviceInfo;
                $params['addDevice'] = 1;
                $searchResult = $this->getGateWayDevice($device_sn, $config, $params);
                unsetParam($params, ['deviceInfo', 'addDevice']);
                fdump_api([ 'msg' => '添加更新设备-查询已存在设备信息', 'line' => __LINE__, 'device_sn' => $device_sn, 'config' => $config, 'params' => $params, 'searchResult' => $searchResult], 'hikGateWay/addDeviceLog', 1);
            }
            if (isset($searchResult) && !empty($searchResult)) {
                $devIndex = isset($searchResult['devIndex']) ? trim($searchResult['devIndex']) : '';
                $devMode = isset($searchResult['devMode']) ? trim($searchResult['devMode']) : '';
                $devName = isset($searchResult['devName']) ? trim($searchResult['devName']) : '';
                $devStatus = isset($searchResult['devStatus']) ? trim($searchResult['devStatus']) : '';
                $devVersion = isset($searchResult['devVersion']) ? trim($searchResult['devVersion']) : '';
                $ISAPIUserBound = isset($searchResult['ISAPIUserBound']) ? $searchResult['ISAPIUserBound'] : '';
                $activeStatus = isset($searchResult['activeStatus']) ? $searchResult['activeStatus'] : '';
                $protocolType = isset($searchResult['protocolType']) ? $searchResult['protocolType'] : '';
                $videoChannelNum = isset($searchResult['videoChannelNum']) ? $searchResult['videoChannelNum'] : '';
                $devType = isset($searchResult['devType']) ? $searchResult['devType'] : '';
                $offlineHint = isset($searchResult['offlineHint']) ? $searchResult['offlineHint'] : '';
                $devSerial = isset($searchResult['devSerial']) ? $searchResult['devSerial'] : '';

                $devMode && $addResult['devMode'] = $devMode;
                $devStatus && $addResult['devStatus'] = $devStatus;
                $devVersion && $addResult['devVersion'] = $devVersion;
                $ISAPIUserBound && $addResult['ISAPIUserBound'] = $ISAPIUserBound;
                $activeStatus && $addResult['activeStatus'] = $activeStatus;
                $protocolType && $addResult['protocolType'] = $protocolType;
                $videoChannelNum && $addResult['videoChannelNum'] = $videoChannelNum;
                $devType && $addResult['devType'] = $devType;
                $offlineHint && $addResult['offlineHint'] = $offlineHint;
                $devSerial && $addResult['devSerial'] = $devSerial;
            }
            $devIndex && $addResult['devIndex'] = $devIndex;
            $devName && $addResult['devName'] = $devName;
            fdump_api([ 'msg' => '添加更新设备-处理参数', 'line' => __LINE__, 'addResult' => $addResult, 'statusCode' => $statusCode, 'subStatusCode' => $subStatusCode], 'hikGateWay/addDeviceLog', 1);
            if (!$devIndex) {
                $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
                $handleParams['line'] = __LINE__;
                $handleParams['syn_status'] = SynOverviewConst::SYN_ERR_FROM_DEVICE_CLOUD;
                $handleParams['syn_msg'] = "同步失败({$errmsg})";
                $handleParams['deviceInfo'] = $deviceInfo;
                $handleParams['interfaceName'] = 'ContentMgmt/DeviceMgmt/addDevice';
                $handleParams['statusCode'] = $statusCode;
                $handleParams['subStatusCode'] = $subStatusCode;
                (new SynOverviewService())->handleSynOverview($handleParams);
                return $this->backData($params, $errmsg, 1019);
            }
            $fail = true;
            // 记录下对应配置config
            $gatewayinfo = array_merge($gatewayinfo, $config);
            $addResult['gatewayinfo'] = $gatewayinfo;
            $device_param = isset($deviceInfo['thirdAddress']) && $deviceInfo['thirdAddress'] ? json_decode($deviceInfo['thirdAddress'], 1) : [];
            $device_param = array_merge($device_param, $config);
            $addResult['thirdAddress'] = $device_param;
            $this->recordFaceDevice($params, $addResult);
            
            $handleParams['line'] = __LINE__;
            $handleParams['syn_status'] = SynOverviewConst::SYN_SUCCESS;
            $handleParams['syn_msg'] = "同步成功[添加]";
            $handleParams['deviceInfo'] = $deviceInfo;
            $handleParams['interfaceName'] = 'ContentMgmt/DeviceMgmt/addDevice';
            $handleParams['statusCode'] = $statusCode;
            $handleParams['subStatusCode'] = $subStatusCode;
            fdump_api([ 'msg' => '添加更新设备-处理参数', 'line' => __LINE__, 'handleParams' => $handleParams], 'hikGateWay/addDeviceLog', 1);
            (new SynOverviewService())->handleSynOverview($handleParams);
            // todo gateway 要走队列执行 获取通道信息.
            $params = [
                'device_equipment_type' => $device_equipment_type, 'village_id' => $village_id, 'device_id' => $device_id,
                'device_sn' => $device_sn, 'nowTime' => $nowTime, 'thirdProtocol' => $thirdProtocol,
            ];
            if ($device_equipment_type == DeviceConst::DEVICE_TYPE_CAMERA) {
//            (new HikGateWayDeviceService())->getChannelsBox($device, $params);
                $this->getChannelsBox($deviceInfo, $params);
            }
            if ($device_equipment_type == DeviceConst::DEVICE_TYPE_FACE) {
//            (new HikGateWayDeviceService())->getHikGatewayDeviceInfoBox($device, $params);
                $this->getHikGatewayDeviceInfoBox($deviceInfo, $params);
                $this->getHttpHosts($deviceInfo, $params);
            }
            // todo gateway 要走队列执行 获取通道信息.
//            (new HikGateWayDeviceService())->getDeviceNetworkBox($deviceInfo, $params);
            $this->getDeviceNetworkBox($deviceInfo, $params);
        }
        return $result;
    }

    /**
     * 获取通道信息-盒子.
     */
    public function getChannelsBox($deviceInfo, $params = []) {
        try {
            return $this->getChannels($deviceInfo, $params);
        } catch (\Exception $e){
            fdump_api([ 'msg' => '获取通道信息异常捕捉盒子', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errGetChannelsLog', 1);
        }
        return [];
    }
    
    /**
     * 获取通道信息.
     */
    public function getChannels($deviceInfo, $params = []) {
        fdump_api([ 'msg' => '获取设备通道-同步', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/getChannelsLog');
        $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
        $config = $this->getConfig($deviceInfo);
        $resultParams = [];
        $resultParams['Stream'] = 'getChannels';
        $resultParams['config'] = $config;
        $resultParams['cloud_device_id'] = $cloud_device_id;
        $redis = true;
        $result = $this->requestResult($resultParams);
        if (empty($result)) {
            $redis = false;
            try {
                if (!$this->hikGate) {
                    $this->hikGate = new Gateway($config);
                }
                $result = $this->hikGate->Stream->getChannels($cloud_device_id);
            } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
                $resoponse = \json_decode( $exception->getResponse()->getBody()->getContents(),true);
                fdump_api([ 'msg' => '获取设备通道-处理参数', 'line' => __LINE__, 'resoponse' => $resoponse], 'hikGateWay/getChannelsLog', 1);
                $statusCode = $resoponse['statusCode'];
                $subStatusCode = $resoponse['subStatusCode'];
                if (intval($statusCode) == 1 && trim($subStatusCode) == 'ok') {
                    $resoponse['code'] = 200;
                    $VideoInputChannelList = isset($resoponse['VideoInputChannelList']) && $resoponse['VideoInputChannelList'] ? $resoponse['VideoInputChannelList'] : [];
                    if (!empty($VideoInputChannelList)) {
                        $params['deviceInfo'] = $deviceInfo;
                        $resultChannels =$this->handleVideoInputChannelList($VideoInputChannelList, $params);
                        fdump_api([ 'msg' => '处理通道返回数据', 'line' => __LINE__, 'resultChannels' => $resultChannels], 'hikGateWay/getChannelsLog', 1);
                        foreach ($resultChannels as &$resultChannel) {
                            // todo gateway 根据来源进行是否走队列执行
                            $resultChannel['preview'] = $this->getPreview($deviceInfo, $resultChannel);
                        }
                        return $resultChannels;
                    }
                    return $resoponse;
                }
                $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
                $resoponse['code'] = 1019;
                $resoponse['message'] = $errmsg;
                return $resoponse;
            }
            $result = $this->requestResult($resultParams, $result);
            fdump_api([ 'msg' => '获取设备通道-处理参数', 'line' => __LINE__, 'result' => $result], 'hikGateWay/getChannelsLog', 1);
        }
        $result['redis'] = $redis;
        if ((isset($result['statusCode']) && intval($result['statusCode']) != 1) || (isset($result['subStatusCode']) && trim($result['subStatusCode']) != 'ok')) {
            $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($result['statusCode'],$result['subStatusCode']);
            $result['code'] = 1019;
            $result['message'] = $errmsg;
            return $result;
        }
        $VideoInputChannelList = isset($result['VideoInputChannelList']) && $result['VideoInputChannelList'] ? $result['VideoInputChannelList'] : [];
        if (!empty($VideoInputChannelList)) {
            $params['deviceInfo'] = $deviceInfo;
            $resultChannels = $this->handleVideoInputChannelList($VideoInputChannelList, $params);
            fdump_api([ 'msg' => '处理通道返回数据', 'line' => __LINE__, 'resultChannels' => $resultChannels], 'hikGateWay/getChannelsLog', 1);
            foreach ($resultChannels as &$resultChannel) {
                // todo gateway 根据来源进行是否走队列执行
                $resultChannel['preview'] = $this->getPreview($deviceInfo, $resultChannel);
            }
            return $resultChannels;
        }
        $result['code'] = 200;
        return $result;
    }

    /**
     * 获取预览地址.
     */
    public function getPreview($deviceInfo, $resultChannel) {
        fdump_api([ 'msg' => '获取预览流', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/getPreviewLog');
        $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
        $config = $this->getConfig($deviceInfo);
        $channelId = isset($resultChannel['channelId']) ? trim($resultChannel['channelId']) : '';
        $channelParams = [
            'id' => $channelId, //通道号
            'streamType' => \Fastwhal\HikDeviceGateway\BuiltInConstants::STREAM_TYPE_SUB,
            'method' => \Fastwhal\HikDeviceGateway\BuiltInConstants::STREAM_METHOD_PREVIEW
        ];
        $resultParams = $channelParams;
        $resultParams['Stream'] = 'getPreview';
        $resultParams['config'] = $config;
        $resultParams['cloud_device_id'] = $cloud_device_id;
        $redis = true;
        $result = $this->requestResult($resultParams);
        $nowTime = time();
        fdump_api([ 'msg' => '获取预览流', 'line' => __LINE__, 'resultParams' => $resultParams, 'result' => $result], 'hikGateWay/getPreviewLog',1);
        if (empty($result)) {
            $redis = false;
            try {
                if (!$this->hikGate) {
                    $this->hikGate = new Gateway($config);
                }
                $result = $this->hikGate->Stream->getPreview($cloud_device_id, $channelParams);
            } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
                $resoponse = \json_decode( $exception->getResponse()->getBody()->getContents(),true);
                fdump_api([ 'msg' => '获取预览流', 'line' => __LINE__, 'cloud_device_id' => $cloud_device_id,'channelParams' => $channelParams, 'resoponse' => $resoponse], 'hikGateWay/getPreviewLog',1);
                $statusCode = $resoponse['statusCode'];
                $subStatusCode = $resoponse['subStatusCode'];
                if (intval($statusCode) == 1 && trim($subStatusCode) == 'ok') {
                    $resoponse['code'] = 200;
                    $mediaAccessInfo = isset($resoponse['MediaAccessInfo']) ? $resoponse['MediaAccessInfo'] : [];
                    $URL = isset($mediaAccessInfo['URL']) ? trim($mediaAccessInfo['URL']) : '';
                    if ($URL) {
                        $update = [
                            'url' => $URL, 'nowTime' => $nowTime, 'lookUrlType' => 'rtsp'
                        ];
                        $this->handleChannelUrl($resultChannel, $update);
                        $resoponse['lookUrlType'] = 'rtsp';
                        $resoponse['look_url'] = $URL;
                    }
                    return $resoponse;
                }
                $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
                $resoponse['code'] = 1019;
                $resoponse['message'] = $errmsg;
                return $resoponse;
            }
            $result = $this->requestResult($resultParams, $result);
            fdump_api([ 'msg' => '获取预览流', 'line' => __LINE__, 'cloud_device_id' => $cloud_device_id,'channelParams' => $channelParams, 'result' => $result], 'hikGateWay/getPreviewLog',1);
        }
        $result['redis'] = $redis;
        if ((isset($result['statusCode']) && intval($result['statusCode']) != 1) || (isset($result['subStatusCode']) && trim($result['subStatusCode']) != 'ok')) {
            $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($result['statusCode'],$result['subStatusCode']);
            $result['code'] = 1019;
            $result['message'] = $errmsg;
            return $result;
        }
        $result['code'] = 200;
        $result['config'] = $config;
        $result['channelId'] = $channelId;
        $mediaAccessInfo = isset($result['MediaAccessInfo']) ? $result['MediaAccessInfo'] : [];
        $URL = isset($mediaAccessInfo['URL']) ? trim($mediaAccessInfo['URL']) : '';
        if ($URL) {
            $update = [
                'url' => $URL, 'nowTime' => $nowTime, 'lookUrlType' => 'rtsp', 'channelUrlKey' => 'rtsp'
            ];
            $this->handleChannelUrl($resultChannel, $update);
            $result['lookUrlType'] = 'rtsp';
            $result['look_url'] = $URL;
        }
        return $result;
    }
    
    /**
     * 获取设备详情-盒子.
     */
    public function getHikGatewayDeviceInfoBox($deviceInfo, $params = []) {
        try {
            if (empty($deviceInfo) && !empty($params)) {
                $device_equipment_type = isset($params['deviceType']) ? trim($params['deviceType']) : '';
                $device_id = isset($params['device_id']) ? intval($params['device_id']) : 0;
                $device_sn = isset($params['device_sn']) ? trim($params['device_sn']) : '';
                $face_device_type = isset($params['device_type']) ? trim($params['device_type']) : '';
                $thirdProtocol = isset($params['thirdProtocol']) ? intval($params['thirdProtocol']) : 0;
                $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
                $device = $this->handleDeviceParams($device_equipment_type, $device_id, $device_sn, $face_device_type, $thirdProtocol, $village_id);
                if (isset($device['code']) && $device['code']) {
                    fdump_api([ 'msg' => '获取访问控制能力-错误', 'line' => __LINE__, 'device' => $device], 'hikGateWay/errGetHikGatewayDeviceInfoBoxLog', 1);
                    return $device;
                }
                $deviceInfo = isset($device['deviceInfo']) ? $device['deviceInfo'] : [];
            }
            return $this->getHikGatewayDeviceInfo($deviceInfo, $params);
        } catch (\Exception $e){
            fdump_api([ 'msg' => '获取设备详情异常捕捉盒子', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errGetHikGatewayDeviceInfoBoxLog', 1);
        }
        return false;
    }
    
    /**
     * 获取设备详情.
     */
    public function getHikGatewayDeviceInfo($deviceInfo, $params = []) {
        fdump_api(['msg' => '获取设备详情', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/getHikGatewayDeviceInfoLog');
        $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
        $saveHikDeviceInfo = isset($params['saveHikDeviceInfo']) ? intval($params['saveHikDeviceInfo']) : 1;
        $device_equipment_type = isset($params['deviceType']) ? trim($params['deviceType']) : '';
        $config = $this->getConfig($deviceInfo);
        $resultParams = [];
        $resultParams['DeviceMgmt'] = 'getDeviceInfo';
        $resultParams['config'] = $config;
        $resultParams['cloud_device_id'] = $cloud_device_id;
        $redis = true;
        $tryCatch = false;
        $result = $this->requestResult($resultParams);
        fdump_api(['msg' => '获取设备详情', 'line' => __LINE__, 'resultParams' => $resultParams], 'hikGateWay/getHikGatewayDeviceInfoLog', 1);
        if (empty($result)) {
            $redis = false;
            try {
                if (!$this->hikGate) {
                    $this->hikGate = new Gateway($config);
                }
                $result = $this->hikGate->DeviceMgmt->getDeviceInfo($cloud_device_id);
            } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
                $resoponse = \json_decode( $exception->getResponse()->getBody()->getContents(),true);
                $tryCatch = true;
                $statusCode = $resoponse['statusCode'];
                $subStatusCode = $resoponse['subStatusCode'];
                if (intval($statusCode) != 1 && trim($subStatusCode) != 'ok') {
                    $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
                    $resoponse['code'] = 1019;
                    $resoponse['message'] = $errmsg;
                    fdump_api(['msg' => '获取设备详情', 'line' => __LINE__, 'resoponse' => $resoponse], 'hikGateWay/getHikGatewayDeviceInfoLog', 1);
                    return $resoponse;
                }
            }
            if (!$tryCatch) {
                $result = $this->requestResult($resultParams, $result);
            }
        }
        $result['redis'] = $redis;
        if (!$tryCatch) {
            if ((isset($result['statusCode']) && intval($result['statusCode']) != 1) || (isset($result['subStatusCode']) && trim($result['subStatusCode']) != 'ok')) {
                $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($result['statusCode'],$result['subStatusCode']);
                $result['code'] = 1019;
                $result['message'] = $errmsg;
                fdump_api(['msg' => '获取设备详情', 'line' => __LINE__, 'result' => $result], 'hikGateWay/getHikGatewayDeviceInfoLog', 1);
                return $result;
            } else {
                $result['code'] = 200;
                $hikDeviceInfo = $result;
            }
        } else {
            $resoponse['code'] = 200;
            $hikDeviceInfo = $resoponse;
        }
        fdump_api(['msg' => '获取设备详情', 'line' => __LINE__, 'saveHikDeviceInfo' => $saveHikDeviceInfo, 'hikDeviceInfo' => $hikDeviceInfo], 'hikGateWay/getHikGatewayDeviceInfoLog', 1);
        if ($saveHikDeviceInfo == 1) {
            // 缓存记录下数据
            $thirdDeviceInfo = isset($hikDeviceInfo['DeviceInfo']) ? $hikDeviceInfo['DeviceInfo'] : [];
            fdump_api([ 'msg' => '存记录下数据', 'line' => __LINE__, 'device_equipment_type' => $device_equipment_type, 'thirdDeviceInfo' => $thirdDeviceInfo], 'hikGateWay/getHikGatewayDeviceInfoLog', 1);
            switch ($device_equipment_type) {
                case DeviceConst::DEVICE_TYPE_CAMERA:
                    fdump_api([ 'msg' => '获取设备详情', 'line' => __LINE__, 'hikDeviceInfo' => $hikDeviceInfo], 'hikGateWay/getHikGatewayDeviceInfoLog', 1);
                    break;
                case DeviceConst::DEVICE_TYPE_FACE:
                    $dbHouseFaceDevice = new HouseFaceDevice();
                    $device_id = isset($deviceInfo['device_id']) ? intval($deviceInfo['device_id']) : 0;
                    $whereFace = [
                        'device_id' => $device_id
                    ];
                    $cloud_device_model = isset($thirdDeviceInfo['model']) ? $thirdDeviceInfo['model'] : '';
                    $cloud_device_name = isset($thirdDeviceInfo['deviceName']) ? $thirdDeviceInfo['deviceName'] : '';
                    $thirdDeviceTypeStr = isset($thirdDeviceInfo['deviceType']) ? $thirdDeviceInfo['deviceType'] : '';
                    $telecontrolId = isset($thirdDeviceInfo['telecontrolID']) ? $thirdDeviceInfo['telecontrolID'] : '';
                    $updateParam = [
                        'last_time'  => time(),
                    ];
                    if ($telecontrolId) {
                        $updateParam['cloud_group_id'] = $telecontrolId;
                    }
                    if ($cloud_device_model) {
                        $updateParam['cloud_device_model'] = $cloud_device_model;
                    }
                    if ($cloud_device_name) {
                        $updateParam['cloud_device_name'] = $cloud_device_name;
                    }
                    if ($thirdDeviceTypeStr) {
                        $updateParam['thirdDeviceTypeStr'] = $thirdDeviceTypeStr;
                    }
                    $dbHouseFaceDevice->saveData($whereFace, $updateParam);
                    fdump_api([ 'msg' => '记录', 'line' => __LINE__, 'whereFace' => $whereFace, 'updateParam' => $updateParam], 'hikGateWay/getHikGatewayDeviceInfoLog', 1);
                    $dbHouseFaceDeviceAbout = new HouseFaceDeviceAbout();
                    $whereAbout = ['device_id' => $device_id];
                    $mac_address = isset($thirdDeviceInfo['macAddress']) ? $thirdDeviceInfo['macAddress'] : '';
                    $cloudDeviceId = isset($thirdDeviceInfo['deviceID']) ? $thirdDeviceInfo['deviceID'] : '';
                    $serialNumber = isset($thirdDeviceInfo['serialNumber']) ? $thirdDeviceInfo['serialNumber'] : '';
                    $parameter_json = $thirdDeviceInfo;
                    $keyArr = ['model', 'deviceName', 'deviceType', 'macAddress', 'deviceID', 'serialNumber', 'telecontrolId'];
                    $parameter_json = unsetParam($parameter_json, $keyArr);
                    $updateParam = [
                        'last_time'  => time(),
                    ];
                    if ($mac_address) {
                        $updateParam['mac_address'] = $mac_address;
                    }
                    if ($cloudDeviceId) {
                        $updateParam['cloudDeviceId'] = $cloudDeviceId;
                    }
                    if ($serialNumber) {
                        $updateParam['serialNumber'] = $serialNumber;
                    }
                    if ($telecontrolId) {
                        $updateParam['telecontrolId'] = $telecontrolId;
                    }
                    if (!empty($parameter_json)) {
                        $updateParam['parameter_json'] = json_encode($parameter_json, JSON_UNESCAPED_UNICODE);
                    }
                    $dbHouseFaceDeviceAbout->updateThis($whereAbout, $updateParam);
                    fdump_api([ 'msg' => '记录', 'line' => __LINE__, 'whereAbout' => $whereAbout, 'updateParam' => $updateParam], 'hikGateWay/getHikGatewayDeviceInfoLog', 1);
                    break;
            }
        }
        return $hikDeviceInfo;
    }

    /**
     * 获取设备是否在线信息.
     */
    public function getGateWayDevice($device_sn, $config, $params = []) {
        try {
            return $this->getGateWayDeviceInfo($device_sn, $config, $params);
        } catch (\Exception $e){
            fdump_api([ 'msg' => '获取设备是否在线信息-异常捕捉盒子', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errGetGateWayDeviceLog', 1);
        }
        return [];
    }
    
    /**
     * 获取设备是否在线信息.
     */
    public function getGateWayDeviceInfo($device_sn, $config, $params = []) {
        fdump_api([ 'msg' => '获取设备是否在线信息', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/getGateWayDeviceLog', 1);
        $deviceInfo = isset($params['deviceInfo']) ? $params['deviceInfo'] : [];
        if (!$this->hikGate && empty($config)) {
            $config = $this->getConfig($deviceInfo);
        }
        fdump_api([ 'msg' => '获取设备是否在线信息-配置信息', 'line' => __LINE__, 'config' => $config], 'hikGateWay/getGateWayDeviceLog', 1);
        !$device_sn && $device_sn = isset($deviceInfo['device_sn']) && trim($deviceInfo['device_sn']) ? trim($deviceInfo['device_sn']) : '';
        !$device_sn && $device_sn = isset($deviceInfo['device_serial']) && trim($deviceInfo['device_serial']) ? trim($deviceInfo['device_serial']) : '';
        !$device_sn && $device_sn = isset($deviceInfo['camera_sn']) && trim($deviceInfo['camera_sn']) ? trim($deviceInfo['camera_sn']) : '';
        $addDevice = isset($params['addDevice']) ? intval($params['addDevice']) : 0;
        $deviceParams = [
            "position" => 0, "maxResult" => 100,
            "Filter" => [
                "EhomeParams" => [
                    "EhomeID" => trim($device_sn)
                ]
            ]
        ];
        $resultParams = $deviceParams;
        $resultParams['DeviceMgmt'] = 'getDeviceList';
        $resultParams['config'] = $config;
        $result = $this->requestResult($resultParams);
        $redis = true;
        fdump_api([ 'msg' => '获取设备是否在线信息', 'line' => __LINE__, 'resultParams' => $resultParams, 'result' => $result], 'hikGateWay/getGateWayDeviceLog', 1);
        if (empty($result)) {
            $redis = false;
            try {
                if (!$this->hikGate) {
                    $this->hikGate = new Gateway($config);
                }
                $result = $this->hikGate->DeviceMgmt->getDeviceList($deviceParams);
            } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
                $resoponse = \json_decode( $exception->getResponse()->getBody()->getContents(),true);
                $statusCode = $resoponse['statusCode'];
                $subStatusCode = $resoponse['subStatusCode'];
                $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
                fdump_api(['line' => __LINE__, 'parameter' => func_get_args(), 'msg' => '获取设备信息出错', 'errmsg' => $errmsg, 'statusCode' => $statusCode, 'subStatusCode' => $subStatusCode, 'deviceParams' => $deviceParams], 'hikGateWay/getGateWayDeviceLog', 1);
                $searchResult = isset($resoponse['SearchResult']) ? $resoponse['SearchResult'] : [];
                $matchList = isset($searchResult['MatchList']) ? $searchResult['MatchList'] : [];
                if (empty($matchList)) {
                    return false;
                }
            }
            $result = $this->requestResult($resultParams, $result);
            fdump_api([ 'msg' => '获取设备是否在线信息', 'line' => __LINE__, 'deviceParams' => $deviceParams, 'result' => $result], 'hikGateWay/getGateWayDeviceLog', 1);
        }
        $result['redis'] = $redis;
        empty($searchResult) && $searchResult = isset($result['SearchResult']) ? $result['SearchResult'] : [];
        empty($matchList) && $matchList = isset($searchResult['MatchList']) ? $searchResult['MatchList'] : [];
        if (!empty($matchList)) {
            $matchInfo = reset($matchList);
            $device = isset($matchInfo['Device']) ? $matchInfo['Device'] : [];
            if (isset($device['devStatus']) && $device['devStatus']) {
                $device['online'] = $device['devStatus'] == 'online' ? 1 : 0;
            }
            $device['code'] = 200;
            if ($addDevice == 1) {
                return $device;
            }
            $params['device_sn'] = $device_sn;
            // 更新下对应设备信息
            $addResult = $device;
            $addResult['ehomeID'] = $device_sn;
            $addResult['login_time'] = time();
            $addResult['notify_time'] = $addResult['login_time'];
            fdump_api([ 'msg' => '更新下对应设备信息', 'line' => __LINE__, 'params' => $params, 'addResult' => $addResult], 'hikGateWay/getGateWayDeviceLog', 1);
            $this->recordFaceDevice($params, $addResult);
            return $device;
        } else {
            if ($addDevice == 1) {
                return [];
            }
            $result['redis'] = true;
            fdump_api(['line' => __LINE__, 'parameter' => func_get_args(), 'msg' => '获取设备信息没有返回', 'result' => $result, 'deviceParams' => $deviceParams], 'hikGateWay/getGateWayDeviceLog', 1);
            return $result;
        }
    }

    /**
     * 同步删除设备-盒子.
     */
    public function deleteDeviceBox($deviceInfo, $params = []) {
        try {
            if (empty($deviceInfo) && !empty($params)) {
                $device_equipment_type = isset($params['deviceType']) ? trim($params['deviceType']) : '';
                $device_id = isset($params['device_id']) ? intval($params['device_id']) : 0;
                $device_sn = isset($params['device_sn']) ? trim($params['device_sn']) : '';
                $face_device_type = isset($params['device_type']) ? trim($params['device_type']) : '';
                $thirdProtocol = isset($params['thirdProtocol']) ? intval($params['thirdProtocol']) : 0;
                $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
                $device = $this->handleDeviceParams($device_equipment_type, $device_id, $device_sn, $face_device_type, $thirdProtocol, $village_id);
                if (isset($device['code']) && $device['code']) {
                    fdump_api([ 'msg' => '同步删除设备-错误', 'line' => __LINE__, 'device' => $device], 'hikGateWay/errDeleteDeviceLog', 1);
                    return $device;
                }
                $deviceInfo = isset($device['deviceInfo']) ? $device['deviceInfo'] : [];
            }
            // 删除设备 同步优先调用删除设备上数据 todo 要不要调用
//            $this->deleteUserBox($deviceInfo, $params, [], 'all');
            return $this->deleteDevice($deviceInfo);
        } catch (\Exception $e){
            fdump_api([ 'msg' => '同步删除设备异常捕捉盒子', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errDeleteDeviceLog', 1);
        }
        return [];
    }
    
    /**
     * 同步删除设备.
     */
    public function deleteDevice($deviceInfo) {
        fdump_api(['msg' => '同步删除设备', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/deleteDeviceLog', 1);
        $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
        $config = $this->getConfig($deviceInfo);
        $resultParams = [];
        $resultParams['DeviceMgmt'] = 'deleteDevice';
        $resultParams['config'] = $config;
        $resultParams['cloud_device_id'] = $cloud_device_id;
        $redis = true;
        $result = $this->requestResult($resultParams);
        if (empty($result)) {
            $redis = false;
            try {
                if (!$this->hikGate) {
                    $this->hikGate = new Gateway($config);
                }
                $result = $this->hikGate->DeviceMgmt->deleteDevice($cloud_device_id);
            } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
                $resoponse = \json_decode( $exception->getResponse()->getBody()->getContents(),true);
                $statusCode = $resoponse['statusCode'];
                $subStatusCode = $resoponse['subStatusCode'];
                if (intval($statusCode) == 1 && trim($subStatusCode) == 'ok') {
                    fdump_api(['msg' => '删除成功', 'line' => __LINE__, 'resoponse' => $resoponse, 'cloud_device_id' => $cloud_device_id], 'hikGateWay/deleteDeviceLog', 1);
                    return $resoponse;
                }
                $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
                $resoponse['code'] = 1019;
                $resoponse['message'] = $errmsg;
                fdump_api(['msg' => '删除失败', 'line' => __LINE__, 'resoponse' => $resoponse,  'cloud_device_id' => $cloud_device_id, 'statusCode' => $statusCode, 'subStatusCode' => $subStatusCode], 'hikGateWay/deleteDeviceLog', 1);
                return $resoponse;
            }
            $result = $this->requestResult($resultParams, $result);
        }
        if ((isset($result['statusCode']) && intval($result['statusCode']) != 1) || (isset($result['subStatusCode']) && trim($result['subStatusCode']) != 'ok')) {
            $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($result['statusCode'],$result['subStatusCode']);
            $result['code'] = 1019;
            $result['message'] = $errmsg;
            fdump_api(['msg' => '删除失败', 'line' => __LINE__, 'result' => $result], 'hikGateWay/deleteDeviceLog', 1);
            return $result;
        }
        fdump_api(['msg' => '删除成功', 'line' => __LINE__, 'result' => $result, 'cloud_device_id' => $cloud_device_id], 'hikGateWay/deleteDeviceLog', 1);
        return $result;
    }

    /**
     * 获取通道信息-盒子.
     */
    public function getDeviceNetworkBox($deviceInfo, $params = []) {
        try {
            return $this->getDeviceNetwork($deviceInfo, $params);
        } catch (\Exception $e){
            fdump_api([ 'msg' => '获取通道信息异常捕捉盒子', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errGetDeviceNetworkLog', 1);
        }
        return [];
    }

    /**
     * 获取设备的所有网络接口信息.
     */
    public function getDeviceNetwork($deviceInfo, $params)
    {
        fdump_api(['msg' => '获取设备的所有网络接口信息', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/getDeviceNetworkLog');
        $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
        $config = $this->getConfig($deviceInfo);
        $resultParams = [];
        $resultParams['DeviceMgmt'] = 'getDeviceNetwork';
        $resultParams['config'] = $config;
        $resultParams['cloud_device_id'] = $cloud_device_id;
        $redis = true;
        $result = $this->requestResult($resultParams);
        fdump_api(['msg' => '获取设备的所有网络接口信息', 'line' => __LINE__, 'resultParams' => $resultParams, 'result' => $result], 'hikGateWay/getDeviceNetworkLog', 1);
        
        if (empty($result)) {
            $redis = false;
            try {
                if (!$this->hikGate) {
                    $this->hikGate = new Gateway($config);
                }
                $result = $this->hikGate->DeviceMgmt->getDeviceNetwork($cloud_device_id);
            } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
                $resoponse = \json_decode( $exception->getResponse()->getBody()->getContents(),true);
                
                $networkInterfaceList = isset($resoponse['NetworkInterfaceList']) && $resoponse['NetworkInterfaceList'] ? $resoponse['NetworkInterfaceList'] : [];
                if (!empty($networkInterfaceList)) {
                    $params['deviceInfo'] = $deviceInfo;
                    $resoponse['newWorkArr'] = $this->handleHouseDeviceNetwork($networkInterfaceList, $params);
                }
                if ((isset($resoponse['statusCode']) && intval($resoponse['statusCode']) != 1) || (isset($resoponse['subStatusCode']) && trim($resoponse['subStatusCode']) != 'ok')) {
                    $statusCode = $resoponse['statusCode'];
                    $subStatusCode = $resoponse['subStatusCode'];
                    $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
                    $resoponse['code'] = 1019;
                    $resoponse['message'] = $errmsg;
                    fdump_api(['msg' => '获取设备的所有网络接口信息', 'line' => __LINE__, 'resoponse' => $resoponse], 'hikGateWay/getDeviceNetworkLog', 1);
                }
                return $resoponse;
            }
            $result = $this->requestResult($resultParams, $result);
        }
        fdump_api(['msg' => '获取设备的所有网络接口信息-拿到结果', 'line' => __LINE__, 'params' => $params, 'result' => $result], 'hikGateWay/getDeviceNetworkLog', 1);
        $result['redis'] = $redis;
        if ((isset($result['statusCode']) && intval($result['statusCode']) != 1) || (isset($result['subStatusCode']) && trim($result['subStatusCode']) != 'ok')) {
            $statusCode = $result['statusCode'];
            $subStatusCode = $result['subStatusCode'];
            $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
            $result['code'] = 1019;
            $result['message'] = $errmsg;
            fdump_api(['msg' => '获取设备的所有网络接口信息', 'line' => __LINE__, 'result' => $result], 'hikGateWay/getDeviceNetworkLog', 1);
            return $result;
        }
        $networkInterfaceList = isset($result['NetworkInterfaceList']) && $result['NetworkInterfaceList'] ? $result['NetworkInterfaceList'] : [];
        if (!empty($networkInterfaceList)) {
            $params['deviceInfo'] = $deviceInfo;
            $result['newWorkArr'] = $this->handleHouseDeviceNetwork($networkInterfaceList, $params);
        }
        return $result;
    }

    /**
     * 重启设备-盒子.
     */
    public function rebootDeviceBox($params) {
        try {
            return $this->rebootDevice($params);
        } catch (\Exception $e){
            fdump_api([ 'msg' => '获取设备的所有网络接口信息-异常捕捉盒子', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errRebootDeviceBoxLog', 1);
        }
        return [];
    }

    /**
     * 重启设备.
     */
    public function rebootDevice($params) {
        fdump_api(['msg' => '重启设备', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/rebootDeviceLog');
        $device_equipment_type = isset($params['deviceType']) ? trim($params['deviceType']) : '';
        $device_id = isset($params['device_id']) ? intval($params['device_id']) : 0;
        $device_sn = isset($params['device_sn']) ? trim($params['device_sn']) : '';
        $face_device_type = isset($params['device_type']) ? trim($params['device_type']) : '';
        $thirdProtocol = isset($params['thirdProtocol']) ? intval($params['thirdProtocol']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $device = $this->handleDeviceParams($device_equipment_type, $device_id, $device_sn, $face_device_type, $thirdProtocol, $village_id);
        if (isset($device['code']) && $device['code']) {
            fdump_api([ 'msg' => '重启设备-错误', 'line' => __LINE__, 'device' => $device], 'hikGateWay/rebootDeviceLog', 1);
            return $device;
        }
        $deviceInfo = isset($device['deviceInfo']) ? $device['deviceInfo'] : [];
        $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
        $config = $this->getConfig($deviceInfo);
        $resultParams = [];
        $resultParams['DeviceMgmt'] = 'rebootDevice';
        $resultParams['config'] = $config;
        $resultParams['cloud_device_id'] = $cloud_device_id;
        $redis = true;
        $result = $this->requestResult($resultParams);
        fdump_api([ 'msg' => '重启设备', 'line' => __LINE__, 'cloud_device_id' => $cloud_device_id, 'resultParams' => $resultParams, 'config' => $config], 'hikGateWay/rebootDeviceLog', 1);
        if (empty($result)) {
            $redis = false;
            try {
                if (!$this->hikGate) {
                    $this->hikGate = new Gateway($config);
                }
                $result = $this->hikGate->DeviceMgmt->rebootDevice($cloud_device_id);
            } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
                $resoponse = \json_decode( $exception->getResponse()->getBody()->getContents(),true);
                if ((isset($resoponse['statusCode']) && intval($resoponse['statusCode']) != 1) || (isset($resoponse['subStatusCode']) && trim($resoponse['subStatusCode']) != 'ok')) {
                    $statusCode = $resoponse['statusCode'];
                    $subStatusCode = $resoponse['subStatusCode'];
                    $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
                    $resoponse['code'] = 1019;
                    $resoponse['message'] = $errmsg;
                    fdump_api(['msg' => '重启设备', 'line' => __LINE__, 'resoponse' => $resoponse], 'hikGateWay/rebootDeviceLog', 1);
                    return $resoponse;
                }
                $resoponse['code'] = 200;
                if (DeviceConst::DEVICE_TYPE_FACE == $device_equipment_type) {
                    $dbHouseFaceDeviceAbout = new HouseFaceDeviceAbout();
                    $whereAbout = ['device_id' => $device_id];
                    $updateParam = [];
                    $updateParam['restart_record_time'] = time();
                    $dbHouseFaceDeviceAbout->updateThis($whereAbout, $updateParam);
                }
                fdump_api(['msg' => '重启设备', 'line' => __LINE__, 'resoponse' => $resoponse], 'hikGateWay/rebootDeviceLog', 1);
                return $resoponse;
            }
            $result = $this->requestResult($resultParams, $result);
        }
        $result['redis'] = $redis;
        if ((isset($result['statusCode']) && intval($result['statusCode']) != 1) || (isset($result['subStatusCode']) && trim($result['subStatusCode']) != 'ok')) {
            $statusCode = $result['statusCode'];
            $subStatusCode = $result['subStatusCode'];
            $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
            $result['code'] = 1019;
            $result['message'] = $errmsg;
            fdump_api(['msg' => '重启设备', 'line' => __LINE__, 'result' => $result], 'hikGateWay/rebootDeviceLog', 1);
            return $result;
        }
        $result['code'] = 200;
        if (DeviceConst::DEVICE_TYPE_FACE == $device_equipment_type) {
            $dbHouseFaceDeviceAbout = new HouseFaceDeviceAbout();
            $whereAbout = ['device_id' => $device_id];
            $updateParam = [];
            $updateParam['restart_record_time'] = time();
            $dbHouseFaceDeviceAbout->updateThis($whereAbout, $updateParam);
        }
        fdump_api(['msg' => '重启设备', 'line' => __LINE__, 'result' => $result], 'hikGateWay/rebootDeviceLog', 1);
        return $result;
    }
    
    /**
     * 设备一键同步.
     */
    public function deviceAllToCloud($deviceInfo, $params = []) 
    {
        fdump_api(['msg' => '设备一键同步-参数', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/deviceAllToCloudLog');
        $device_equipment_type = isset($params['deviceType']) ? trim($params['deviceType']) : '';
        $device_id = isset($params['device_id']) ? intval($params['device_id']) : 0;
        $device_sn = isset($params['device_sn']) ? trim($params['device_sn']) : '';
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        if (empty($deviceInfo)) {
            $whereFace = [];
            $whereFace[] = ['thirdProtocol', 'in', [DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM]];
            $village_id && $whereFace[] = ['village_id', '=', $village_id];
            if ($device_id) {
                $whereFace[] = ['device_id', '=', $device_id];
            } else {
                $whereFace[] = ['device_sn', '=', $device_sn];
                $whereFace[] = ['is_del', '=', 0];
            }
            $db_house_face_device = new HouseFaceDevice();//人脸门禁表
            $deviceInfo = $db_house_face_device->getOne($whereFace);
            if ($deviceInfo && !is_array($deviceInfo)) {
                $deviceInfo = $deviceInfo->toArray();
            }
        }
        if (empty($deviceInfo)) {
            return [];
        }
        $houseVillageUserBindService = new HouseVillageUserBindService();
        !$device_id && $device_id = isset($deviceInfo['device_id']) ? intval($deviceInfo['device_id']) : 0;
        !$device_sn && $device_sn = isset($deviceInfo['device_sn']) ? trim($deviceInfo['device_sn']) : '';
        !$village_id && $village_id = isset($deviceInfo['village_id']) ? intval($deviceInfo['village_id']) : 0;
        $device_floor_id = isset($deviceInfo['floor_id']) ? $deviceInfo['floor_id'] : '0';
        $deviceFloorIdArr = explode(',', $device_floor_id);
        $whereUserBind = [];
        $whereUserBind[] = ['status', '=', 1];
        $whereUserBind[] = ['village_id', '=', $village_id];
        if (!empty($deviceFloorIdArr) && $device_floor_id != -1 && $device_floor_id != '0') {
            $whereUserBind[] = ['floor_id', 'in', $deviceFloorIdArr];
        }
        $filed = 'pigcms_id,village_id,uid,name,phone,type,vacancy_id,layer_id,floor_id,single_id';
        $userBinds = $houseVillageUserBindService->getHouseUserBindList($whereUserBind,$filed,0);
        if ($userBinds && !is_array($userBinds)) {
            $userBinds = $userBinds->toArray();
        } elseif (!$userBinds) {
            $userBinds = [];
        }
        fdump_api(['msg' => '设备一键同步1-对应住户', 'line' => __LINE__, 'whereUserBind' => $whereUserBind,'deviceFloorIdArr' => $deviceFloorIdArr, 'userBinds' => $userBinds], 'hikGateWay/deviceAllToCloudLog', 1);
        $whereUserBind   = [];
        $whereUserBind[] = ['status', '=', 1];
        $whereUserBind[] = ['village_id', '=', $village_id];
        $whereUserBind[] = ['type', 'in', [4,5]];
        $userWorkBinds = $houseVillageUserBindService->getHouseUserBindList($whereUserBind,$filed,0);
        if ($userWorkBinds && !is_array($userWorkBinds)) {
            $userWorkBinds = $userWorkBinds->toArray();
        } elseif (!$userWorkBinds) {
            $userWorkBinds = [];
        }
        $users = array_merge($userBinds, $userWorkBinds);
        fdump_api(['msg' => '设备一键同步-对应住户', 'line' => __LINE__, 'deviceInfo' => $deviceInfo, 'users' => $users], 'hikGateWay/deviceAllToCloudLog', 1);
        if (!empty($users)) {
            $face_device_type = isset($deviceInfo['device_type']) ? trim($deviceInfo['device_type']) : '';
            $nowTime = isset($params['nowTime']) && $params['nowTime'] ? intval($params['nowTime']) : time();
            $usersParams = [
                'deviceType' => DeviceConst::DEVICE_AUTH_TYPE_FACE, 'device_id' => $device_id, 'device_sn' => $device_sn,
                'device_type' => $face_device_type, 'nowTime' => $nowTime,
            ];
            // 处理好同步结构
            $addUserParams = [];
            $cardImageKeys = [];
            $userCountKey = count($users) - 1;
            foreach ($users as $keyUser => $user) {
                $pigcms_id = isset($user['pigcms_id']) && intval($user['pigcms_id']) > 0 ? intval($user['pigcms_id']) : 0;
                $uid = isset($user['uid']) && intval($user['uid']) > 0 ? intval($user['uid']) : 0;
                $name = isset($user['name']) && trim($user['name']) ? trim($user['name']) : '';
                $ic_card = isset($user['ic_card']) && trim($user['ic_card']) ? trim($user['ic_card']) : '';
                $name = (new FaceDHYunRuiCloudDeviceService())->replaceWord($uid, $name, '');
                try {
                    $userTimes = (new VisitorInviteService())->getUserEndTime($pigcms_id, ['isDelay' => 1]);
                } catch (\Exception $e){
                    fdump_api([ 'msg' => '获取到期时间异常错误', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errDeviceAllToCloudLog', 1);
                    $userTimes = [];
                }
                $propertyStartTime = isset($userTimes['propertyStartTime']) ? intval($userTimes['propertyStartTime']) : 0;
                $propertyEndTime = isset($userTimes['propertyEndTime']) ? intval($userTimes['propertyEndTime']) : 0;
                if ($propertyEndTime > 2145887999) {
                    $propertyEndTime = 2145887999;
                }
                $todayTime=date('Y-m-d');
                $todayTime=strtotime($todayTime);
                if($propertyStartTime>$todayTime){
                    $propertyStartTime=$todayTime;
                }
                $beginTimeString = $propertyStartTime > 0 ? date('Y-m-d H:i:s', $propertyStartTime) : date('Y-m-d H:i:s');
                
                $endTimeString = $propertyEndTime > 0 ? date('Y-m-d H:i:s', $propertyEndTime) : '2037-12-31 23:59:59';
                $beginTimeDay = new \DateTime($beginTimeString);
                $beginTime = $beginTimeDay->setTime(0,0,0)->format('Y-m-d\TH:i:s');
                $endTimeDay = new \DateTime($endTimeString);
                $endTime = $endTimeDay->setTime(23,59,59)->format('Y-m-d\TH:i:s');
                $valid = [
                    'beginTime' => $beginTime,  'endTime' => $endTime,
                ];
                $addUserParam = [
                    'employeeNo' => trim($pigcms_id), 'name' => trim($name), 'Valid' => $valid, 'userType' => 'normal', 'timeType' => 'UTC',
                ];
                $addUserParams[$pigcms_id] = $addUserParam;
                $cardImageKeys[$pigcms_id] = [
                    'employeeNo' => $pigcms_id, 'uid' => $uid, 'ic_card' => $ic_card,
                ];
                if (count($addUserParams) >= 25 || $keyUser >= $userCountKey) {
                    // todo 积攒超过25个人员或者人员数量已经到了最后一个.
                    $usersParams['addUserParams'] = $addUserParams;
                    $usersParams['cardImageKeys'] = $cardImageKeys;
                    $addUserParams = [];
                    $cardImageKeys = [];
                    // todo gateway 直接今天添加或者更新人员应该走队列执行.

                    $usersParams['handleType'] = 'addUsersBox';
                    $job_id = $this->traitCommonHikDeviceGateWay($usersParams);
                    fdump_api(['msg' => '设备一键同步-直接今天添加或者更新人员', 'line' => __LINE__, 'usersParams' => $usersParams, 'job_id' => $job_id], 'hikGateWay/deviceAllToCloudLog', 1);
//                    $this->addUsers($deviceInfo, $usersParams);
                }
            }
        }
        return $params;
    }

    /**
     * 用户向所有符合条件的设备同步.
     */
    public function userToAllBelongFaceDevice($pigcms_id, $village_id = 0, $params = []) 
    {
        fdump_api(['msg' => '用户向所有符合条件的设备同步', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/userToAllBelongFaceDeviceLog');
        $userBinds = isset($params['userBinds']) && !empty($params['userBinds']) ? $params['userBinds'] : [];
        $nowTime = isset($params['nowTime']) && $params['nowTime'] ? intval($params['nowTime']) : time();
        $no_job=$params && isset($params['no_job']) ? $params['no_job']:0; //不走队列
        if (empty($userBinds)) {
            $userBinds = $this->getDeviceBindUser($pigcms_id, $village_id);
        }
        $houseVillageUserBindService = new HouseVillageUserBindService();
        if (isset($userBinds['village_id']) && $userBinds['village_id']) {
            $village_id = $userBinds['village_id'];
        }
        if ($userBinds['type'] == 4) {
            $judgeFloorId = 0;
        } else {
            $judgeFloorId = $userBinds['floor_id'] && $userBinds['floor_id'] > 0 ? $userBinds['floor_id'] : 0;
        }
        $whereFace = [];
        $whereFace[] = ['village_id', '=', $village_id];
        $whereFace[] = ['thirdProtocol', 'in', [DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM]];
        $whereFace[] = ['is_del', '=', 0];
        $db_house_face_device = new HouseFaceDevice();//人脸门禁表
        $deviceList = $db_house_face_device->getList($whereFace, true, 1);
        if ($deviceList && !is_array($deviceList)) {
            $deviceList = $deviceList->toArray();
        }
        $device_type = DeviceConst::DEVICE_TYPE_FACE;
        fdump_api(['msg' => '设备信息', 'line' => __LINE__, 'pigcms_id' => $pigcms_id, 'deviceList' => $deviceList, 'judgeFloorId' => $judgeFloorId], 'hikGateWay/userToAllBelongFaceDeviceLog', 1);
        if (!empty($deviceList)) {
            foreach ($deviceList as $device) {
                if ($judgeFloorId && isset($device['floor_id']) && $device['floor_id'] != -1 && $device['floor_id'] != 0) {
                    $floorIdArr = explode(',', $device['floor_id']);
                    if (!in_array($judgeFloorId, $floorIdArr)) {
                        // 不是同一个单元的 同步不进行下发处理
                        continue;
                    }
                }
                //增加下发记录
                $syncLogParams = [
                    'pigcms_id' => $pigcms_id, 'face_id' => 0, 'device_id' => $device['device_id'], 'device_sn' => $device['device_sn'],
                    'log_time' => $nowTime, 'status' => 0, 'device_info'=> $device
                ];
                $logId = (new OwnerService())->handleHouseFaceSyncLog($syncLogParams);
                $thirdProtocol = isset($device['thirdProtocol']) && $device['thirdProtocol'] ? $device['thirdProtocol'] : 0;
                $param = [
                    'pigcms_id' => $pigcms_id, 'device_id' => $device['device_id'], 'room_id' => $userBinds['vacancy_id'],
                    'uid' => isset($userBinds['uid']) ? $userBinds['uid'] : 0, 'village_id' => $device['village_id'], 'log_id' => $logId,
                    'thirdProtocol' => $thirdProtocol, 'device_sn' => $device['device_sn'], 'deviceType' => $device_type,
                ];
                // todo gateway 要走队列执行 单个人员同步对应设备.
                if($no_job>0){
                    // todo 临时直接调用
                    $this->userToFaceDevice($device, $param,$no_job);
                }else{
                    $param['handleType'] = 'userToFaceDeviceBox';
                    $job_id = $this->traitCommonHikDeviceGateWay($param);
                }
            }
        }
        else {
            $whereFaceCount = [];
            $whereFaceCount[] = ['village_id', '=', $village_id];
            $whereFaceCount[] = ['is_del', '=', 0];
            $deviceList = $db_house_face_device->getList($whereFaceCount, 'device_id, village_id, floor_id', 1);
            if ($deviceList && !is_array($deviceList)) {
                $deviceList = $deviceList->toArray();
            }
            $deviceCount = 0;
            foreach ($deviceList as $device) {
                if ($judgeFloorId && isset($device['floor_id']) && $device['floor_id'] != -1 && $device['floor_id'] != 0) {
                    $floorIdArr = explode(',', $device['floor_id']);
                    if (!in_array($judgeFloorId, $floorIdArr)) {
                        // 不是同一个单元的 同步不进行下发处理
                        continue;
                    }
                }
                ++$deviceCount;
            }
            fdump_api(['msg' => '判断下是否是没有符合的设备', 'line' => __LINE__, 'deviceList' => $deviceList, 'deviceCount' => $deviceCount], 'hikGateWay/userToAllBelongFaceDeviceLog', 1);
            if ($deviceCount <= 0) {
                if(isset($params['log_id']) && $params['log_id']){
                    (new OwnerService())->updateLog(['log_id'=>$params['log_id']],[
                        'status'=>2,
                        'msg'=>'当前小区无对应可同步设备',
                        'last_time'=>time()
                    ]);
                } else {
                    $syncLogParams = [
                        'pigcms_id' => $pigcms_id, 'device_id' => -1, 'nowTime' => time(), 'status' => 2, 'msg' => '当前住户无对应可同步设备',
                    ];
                    (new OwnerService())->handleHouseFaceSyncLog($syncLogParams);
                }
                $updateBindParam = [
                    'face_img_status' => 2,
                    'face_img_reason' => '当前住户无对应可同步设备',
                ];
                $houseVillageUserBindService->saveUserBind(['pigcms_id' => $pigcms_id], $updateBindParam);
                return false;
            }
        }
        return true;
    }

    /**
     * 用户向单个设备同步-盒子.
     */
    public function userToFaceDeviceBox($params) {
        try {
            $device_id = isset($params['device_id']) ? intval($params['device_id']) : 0;
            $device_sn = isset($params['device_sn']) ? trim($params['device_sn']) : '';
            $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
            $whereFace = [];
            $whereFace[] = ['thirdProtocol', 'in', [DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM]];
            $village_id && $whereFace[] = ['village_id', '=', $village_id];
            if ($device_id) {
                $whereFace[] = ['device_id', '=', $device_id];
            } else {
                $whereFace[] = ['device_sn', '=', $device_sn];
                $whereFace[] = ['is_del', '=', 0];
            }
            $db_house_face_device = new HouseFaceDevice();//人脸门禁表
            $deviceInfo = $db_house_face_device->getOne($whereFace);
            if ($deviceInfo && !is_array($deviceInfo)) {
                $deviceInfo = $deviceInfo->toArray();
            }
            if (empty($deviceInfo)) {
                fdump_api([ 'msg' => '删除人员和关联的权限-错误', 'line' => __LINE__, 'deviceInfo' => $deviceInfo], 'hikGateWay/errAddUsersBoxLog', 1);
                return false;
            }
            return $this->userToFaceDevice($deviceInfo, $params);
        } catch (\Exception $e){
            fdump_api([ 'msg' => '添加人员异常捕捉盒子', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errUserToFaceDeviceBoxLog', 1);
        }
        return false;
    }
    
    /**
     * 用户向单个设备同步.
     */
    public function userToFaceDevice($deviceInfo, $params,$no_job=0) 
    {
        fdump_api(['msg' => '用户向单个设备同步', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/userToFaceDeviceLog');
        $device_equipment_type = isset($params['deviceType']) ? trim($params['deviceType']) : '';
        $device_id = isset($params['device_id']) ? intval($params['device_id']) : 0;
        $device_sn = isset($params['device_sn']) ? trim($params['device_sn']) : '';
        $face_device_type = isset($params['device_type']) ? trim($params['device_type']) : '';
        $thirdProtocol = isset($params['thirdProtocol']) ? intval($params['thirdProtocol']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $nowTime = isset($params['nowTime']) && $params['nowTime'] ? intval($params['nowTime']) : time();
        $pigcms_id = isset($params['pigcms_id']) && $params['pigcms_id'] ? intval($params['pigcms_id']) : 0;
        
        if (empty($deviceInfo)) {
            $whereFace = [];
            $whereFace[] = ['thirdProtocol', 'in', [DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM]];
            $village_id && $whereFace[] = ['village_id', '=', $village_id];
            if ($device_id) {
                $whereFace[] = ['device_id', '=', $device_id];
            } else {
                $whereFace[] = ['device_sn', '=', $device_sn];
                $whereFace[] = ['is_del', '=', 0];
            }
            $db_house_face_device = new HouseFaceDevice();//人脸门禁表
            $deviceInfo = $db_house_face_device->getOne($whereFace);
            if ($deviceInfo && !is_array($deviceInfo)) {
                $deviceInfo = $deviceInfo->toArray();
            }
        }
        $userBinds = $this->getDeviceBindUser($pigcms_id, $village_id);
        fdump_api(['msg' => '用户信息', 'line' => __LINE__, 'userBinds' => $userBinds], 'hikGateWay/userToFaceDeviceLog', 1);
        $houseVillageUserBindService = new HouseVillageUserBindService();
        if (empty($deviceInfo)) {
            $msg = "当前设备不存在";
            $device_sn && $msg .= "[设备序号：{$device_sn}]";
            !$device_sn && $device_id && $msg .= "[设备ID：{$device_id}]";
            if (isset($params['log_id']) && $params['log_id']) {
                (new OwnerService())->updateLog(['log_id' => $params['log_id']], ['status' => 2, 'msg' => $msg, 'last_time' => time()]);
            } else {
                $syncLogParams = [
                    'pigcms_id' => $pigcms_id, 'device_id' => -1, 'nowTime' => time(), 'status' => 2, 'msg' => $msg,
                ];
                (new OwnerService())->handleHouseFaceSyncLog($syncLogParams);
            }
            $updateBindParam = [
                'face_img_status' => 2,
                'face_img_reason' => $msg,
            ];
            $houseVillageUserBindService->saveUserBind(['pigcms_id' => $pigcms_id], $updateBindParam);
            return false;
        }
        $uid = isset($userBinds['uid']) && intval($userBinds['uid']) > 0 ? intval($userBinds['uid']) : 0;
        $name = isset($userBinds['name']) && trim($userBinds['name']) ? trim($userBinds['name']) : '';
        $ic_card = isset($userBinds['ic_card']) && trim($userBinds['ic_card']) ? trim($userBinds['ic_card']) : '';
        // 处理好同步结构
        $addUserParams = [];
        $name = (new FaceDHYunRuiCloudDeviceService())->replaceWord($uid, $name, '');
        try {
            $userTimes = (new VisitorInviteService())->getUserEndTime($pigcms_id, ['isDelay' => 1]);
        } catch (\Exception $e){
            fdump_api([ 'msg' => '获取到期时间', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errUserToFaceDeviceLog', 1);
            $userTimes = [];
        }
        $propertyStartTime = isset($userTimes['propertyStartTime']) ? intval($userTimes['propertyStartTime']) : 0;
        $propertyEndTime = isset($userTimes['propertyEndTime']) ? intval($userTimes['propertyEndTime']) : 0;
        if ($propertyEndTime > 2145887999) {
            $propertyEndTime = 2145887999;
        }
        $todayTime=date('Y-m-d');
        $todayTime=strtotime($todayTime);
        if($propertyStartTime>$todayTime){
            $propertyStartTime=$todayTime;
        }
        $beginTimeString = $propertyStartTime > 0 ? date('Y-m-d H:i:s', $propertyStartTime) : date('Y-m-d H:i:s');
        $endTimeString = $propertyEndTime > 0 ? date('Y-m-d H:i:s', $propertyEndTime) : '2037-12-31 23:59:59';
        $beginTimeDay = new \DateTime($beginTimeString);
        $beginTime = $beginTimeDay->setTime(0,0,0)->format('Y-m-d\TH:i:s');
        $endTimeDay = new \DateTime($endTimeString);
        $endTime = $endTimeDay->setTime(23,59,59)->format('Y-m-d\TH:i:s');
        $valid = [
            'beginTime' => $beginTime,  'endTime' => $endTime,
        ];
        $addUserParam = [
            'employeeNo' => trim($pigcms_id), 'name' => trim($name), 'Valid' => $valid, 'userType' => 'normal', 'timeType' => 'UTC',
        ];
        $addUserParams[$pigcms_id] = $addUserParam;
        $cardImageKeys = [];
        $cardImageKeys[$pigcms_id] = [
            'employeeNo' => $pigcms_id, 'uid' => $uid, 'ic_card' => $ic_card,
        ];
        // todo gateway 直接今天添加或者更新人员.
        $params['addUserParams'] = $addUserParams;
        $params['cardImageKeys'] = $cardImageKeys;
        fdump_api(['msg' => '添加人员。(一次最多可添加 30 个人员。)', 'line' => __LINE__, 'deviceInfo' => $deviceInfo, 'params' => $params], 'hikGateWay/userToFaceDeviceLog', 1);
        return $this->addUsers($deviceInfo, $params,$no_job);
    }
    
    /**
     * 添加人员-盒子。(一次最多可添加 30 个人员。)
     */
    public function addUsersBox($params)
    {
        try {
            $device_id = isset($params['device_id']) ? intval($params['device_id']) : 0;
            $device_sn = isset($params['device_sn']) ? trim($params['device_sn']) : '';
            $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
            $whereFace = [];
            $whereFace[] = ['thirdProtocol', 'in', [DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM]];
            $village_id && $whereFace[] = ['village_id', '=', $village_id];
            if ($device_id) {
                $whereFace[] = ['device_id', '=', $device_id];
            } else {
                $whereFace[] = ['device_sn', '=', $device_sn];
                $whereFace[] = ['is_del', '=', 0];
            }
            $db_house_face_device = new HouseFaceDevice();//人脸门禁表
            $deviceInfo = $db_house_face_device->getOne($whereFace);
            if ($deviceInfo && !is_array($deviceInfo)) {
                $deviceInfo = $deviceInfo->toArray();
            }
            if (empty($deviceInfo)) {
                fdump_api([ 'msg' => '删除人员和关联的权限-错误', 'line' => __LINE__, 'deviceInfo' => $deviceInfo], 'hikGateWay/errAddUsersBoxLog', 1);
                return false;
            }
            return $this->addUsers($deviceInfo, $params);
        } catch (\Exception $e){
            fdump_api([ 'msg' => '添加人员异常捕捉盒子', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errAddUsersBoxLog', 1);
        }
        return false;
    }
    
    /**
     * 添加人员。(一次最多可添加 30 个人员。)
     */
    public function addUsers($deviceInfo, $params,$no_job=0) 
    {
        fdump_api(['msg' => '添加人员。(一次最多可添加 30 个人员。)', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/addUsersLog');
        $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
        $device_id = isset($deviceInfo['device_id']) ? intval($params['device_id']) : 0;
        $qr_content_card = isset($params['qr_content_card']) && trim($params['qr_content_card']) ? trim($params['qr_content_card']) : '';
        $addUserParamKeys = isset($params['addUserParams']) ? $params['addUserParams'] : [];
        $cardImageKeys = isset($params['cardImageKeys']) ? $params['cardImageKeys'] : [];
        $addUserParams = array_values($addUserParamKeys);
        $nowTime = isset($params['nowTime']) && $params['nowTime'] ? intval($params['nowTime']) : time();
        $config = $this->getConfig($deviceInfo);
        $resultParams = $addUserParams;
        $resultParams['Ac'] = 'addUsers';
        $resultParams['config'] = $config;
        $resultParams['cloud_device_id'] = $cloud_device_id;
        $redis = true;
        $tryCatch = false;
        $result = $this->requestResult($resultParams);
        $userInfoOutList = [];
        fdump_api(['msg' => '添加人员-参数', 'line' => __LINE__, 'cloud_device_id' => $cloud_device_id, 'resultParams' => $resultParams], 'hikGateWay/addUsersLog',1);
        // 处理下记录
        $employeeNoArr = [];
        foreach ($addUserParams as $users) {
            $employeeNo = isset($users['employeeNo']) ? trim($users['employeeNo']) : '';
            $employeeNoArr[$employeeNo] = $employeeNo;
        }
        $houseVillageUserBindService = new HouseVillageUserBindService();
        if (empty($result)) {
            $redis = false;
            try {
                if (!$this->hikGate) {
                    $this->hikGate = new Gateway($config);
                }
                $result = $this->hikGate->Ac->addUsers($cloud_device_id, $addUserParams);
            } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
                $resoponse = \json_decode( $exception->getResponse()->getBody()->getContents(),true);
                fdump_api(['msg' => '添加人员', 'line' => __LINE__, 'resoponse' => $resoponse], 'hikGateWay/addUsersLog', 1);
                $tryCatch = true;
                if ((isset($resoponse['statusCode']) && intval($resoponse['statusCode']) != 1) || (isset($resoponse['subStatusCode']) && trim($resoponse['subStatusCode']) != 'ok')) {
                    $statusCode = $resoponse['statusCode'];
                    $subStatusCode = $resoponse['subStatusCode'];
                    $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
                    $resoponse['code'] = 1019;
                    $resoponse['message'] = $errmsg;
                    foreach ($employeeNoArr as $employeeNoId) {
                        $updateBindParam = [
                            'face_img_status' => 2, 'face_img_reason' => $errmsg . "[添加人员]",
                        ];
                        $houseVillageUserBindService->saveUserBind(['pigcms_id' => $employeeNoId], $updateBindParam);
                        if (isset($params['log_id']) && $params['log_id']) {
                            (new OwnerService())->updateLog(['log_id' => $params['log_id']], ['status' => 2, 'msg' => $errmsg, 'last_time' => time()]);
                        } else {
                            $syncLogParams = [
                                'pigcms_id' => $employeeNoId, 'nowTime' => time(), 'status' => 2, 'msg' => $errmsg,
                                'device_id' => $deviceInfo['device_id'],  'device_sn' => $deviceInfo['device_sn'],
                            ];
                            (new OwnerService())->handleHouseFaceSyncLog($syncLogParams);
                        }
                    }
                    fdump_api(['msg' => '添加人员-接口报错', 'line' => __LINE__, 'employeeNoArr' => $employeeNoArr, 'resoponse' => $resoponse], 'hikGateWay/addUsersLog', 1);
                    return $resoponse;
                }
            }
            if (!$tryCatch) {
                $result = $this->requestResult($resultParams, $result);
            } else {
                $result = $resoponse;
            }
        }
        $dbFaceUserBindDevice = new FaceUserBindDevice();
        $userInfoOutList = isset($result['UserInfoOutList']) ? $result['UserInfoOutList'] : [];
        $userInfoOut = isset($userInfoOutList['UserInfoOut']) ? $userInfoOutList['UserInfoOut'] : [];
        fdump_api(['msg' => '添加结果', 'line' => __LINE__, 'result' => $result, 'userInfoOut' => $userInfoOut], 'hikGateWay/addUsersLog', 1);
        if (!empty($userInfoOut)) {
            $village_id = isset($deviceInfo['village_id']) ? intval($deviceInfo['village_id']) : '';
            $thirdProtocol = isset($deviceInfo['thirdProtocol']) ? trim($deviceInfo['thirdProtocol']) : '';
            $aboutArr = [];
            
            $device_equipment_type = isset($params['deviceType']) ? trim($params['deviceType']) : '';
            $device_id = isset($params['device_id']) ? intval($params['device_id']) : 0;
            $device_sn = isset($params['device_sn']) ? trim($params['device_sn']) : '';
            $face_device_type = isset($params['device_type']) ? trim($params['device_type']) : '';
            $nowTime = isset($params['nowTime']) && $params['nowTime'] ? intval($params['nowTime']) : time();
            
            foreach ($userInfoOut as $item) {
                $employeeNo = isset($item['employeeNo']) ? trim($item['employeeNo']) : '';
                if (isset($employeeNoArr[$employeeNo])) {
                    unset($employeeNoArr[$employeeNo]);
                }
                $errorCode = isset($item['errorCode']) ? trim($item['errorCode']) : '';
                $errorMsg = isset($item['errorMsg']) ? trim($item['errorMsg']) : '';
                $statusString = isset($item['statusString']) ? trim($item['statusString']) : '';
                $statusCode = isset($item['statusCode']) ? $item['statusCode'] : 0;
                $subStatusCode = isset($item['subStatusCode']) ? trim($item['subStatusCode']) : '';
                $errCode = 0;
                $errMessage = '';
                $userInfo = isset($addUserParamKeys[$employeeNo]) && $addUserParamKeys[$employeeNo] ? $addUserParamKeys[$employeeNo] : [];
                $updateResult = [];
                if ((isset($item['statusCode']) && $statusCode != 1) || (isset($item['subStatusCode']) && $subStatusCode != 'ok')) {
                    if (in_array($subStatusCode,array('employeeNoAlreadyExist','deviceUserAlreadyExist')) && !empty($userInfo)) {
                        $updateResult = $this->updateUser($deviceInfo, $userInfo);
                        $updateStatusCode = isset($updateResult['statusCode']) ? $updateResult['statusCode'] : 0;
                        $updateSubStatusCode = isset($updateResult['subStatusCode']) ? trim($updateResult['subStatusCode']) : '';
                        if (intval($updateStatusCode) == 1 && $updateSubStatusCode == 'ok') {
                            $errMessage = '';
                            $errCode = 0;
                        } else {
                            if ($statusCode && $subStatusCode) {
                                $errMessage = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
                            } else {
                                $errMessage = $errorMsg;
                            }
                            !$errMessage && $errMessage = '同步失败';
                            $errCode = 1;
                            $errMessage .= "[更新人员]";
                        }
                    } else {
                        $errMessage = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
                        !$errMessage && $errMessage = '同步失败';
                        $errCode = 1;
                        $errMessage .= "[添加人员]";
                    }
                }
                if ($errMessage) {
                    // 同步错误。
                    $updateBindParam = [
                        'face_img_status' => 2, 'face_img_reason' => $errMessage,
                    ];
                    $houseVillageUserBindService->saveUserBind(['pigcms_id' => $employeeNo], $updateBindParam);
                    fdump_api(['msg' => '记录', 'line' => __LINE__, 'pigcms_id' => $employeeNo, 'updateBindParam' => $updateBindParam], 'hikGateWay/addUsersLog', 1);
                    if (isset($params['log_id']) && $params['log_id']) {
                        (new OwnerService())->updateLog(['log_id' => $params['log_id']], ['status' => 2, 'msg' => $errMessage, 'last_time' => time()]);
                    } else {
                        $syncLogParams = [
                            'pigcms_id' => $employeeNo, 'nowTime' => time(), 'status' => 2, 'msg' => $errMessage,
                            'device_id' => $deviceInfo['device_id'],  'device_sn' => $deviceInfo['device_sn'],
                        ];
                        fdump_api(['msg' => '记录', 'line' => __LINE__, 'syncLogParams' => $syncLogParams], 'hikGateWay/addUsersLog', 1);
                        (new OwnerService())->handleHouseFaceSyncLog($syncLogParams);
                    }
                }
                if ($errCode == 0 && !$errMessage) {
                    $whereAbout = [
                        'bind_type' => DeviceConst::Hk_GATEWAY_USER_BIND_PIGCMSID, 'third_protocol' => $thirdProtocol,
                        'device_id' => $device_id, 'bind_id' => $employeeNo,
                    ];
                    $data = [
                        'bind_type' => DeviceConst::Hk_GATEWAY_USER_BIND_PIGCMSID, 'third_protocol' => $thirdProtocol,
                        'bind_id' => $employeeNo, 'person_id' => $employeeNo, 'device_id' => $device_id, 'group_id' => $village_id,
                    ];
                    // 同步成功。
                    $updateBindParam = [
                        'face_img_status' => 1, 'face_img_reason' => '',
                    ];
                    $houseVillageUserBindService->saveUserBind(['pigcms_id' => $employeeNo], $updateBindParam);
                    fdump_api(['msg' => '记录', 'line' => __LINE__, 'pigcms_id' => $employeeNo, 'updateBindParam' => $updateBindParam], 'hikGateWay/addUsersLog', 1);
                    if (isset($params['log_id']) && $params['log_id']) {
                        (new OwnerService())->updateLog(['log_id' => $params['log_id']], ['status' => 1, 'msg' => '', 'last_time' => time()]);
                    } else {
                        $syncLogParams = [
                            'pigcms_id' => $employeeNo, 'nowTime' => time(), 'status' => 1, 'msg' => '',
                            'device_id' => $deviceInfo['device_id'], 'device_sn' => $deviceInfo['device_sn'],
                        ];
                        fdump_api(['msg' => '记录', 'line' => __LINE__, 'syncLogParams' => $syncLogParams], 'hikGateWay/addUsersLog', 1);
                        (new OwnerService())->handleHouseFaceSyncLog($syncLogParams);
                    }
                    $aboutInfo = $dbFaceUserBindDevice->getOne($whereAbout, 'id', 'id DESC');
                    $aboutInfoId = isset($aboutInfo['id']) && $aboutInfo['id'] ? intval($aboutInfo['id']) : 0;
                    fdump_api(['msg' => '记录', 'line' => __LINE__, 'whereAbout' => $whereAbout, 'aboutInfoId' => $aboutInfoId, 'data' => $data], 'hikGateWay/addUsersLog', 1);
                    if (!empty($userInfo)) {
                        $data['person_text'] = json_encode($userInfo, JSON_UNESCAPED_UNICODE);
                    }
                    if (!empty($updateResult) && is_array($item)) {
                        $item['updateResult'] = $updateResult;
                    }
                    $data['info'] = json_encode($item, JSON_UNESCAPED_UNICODE);
                    if ($aboutInfoId > 0) {
                        $data['last_time'] = $nowTime;
                        $dbFaceUserBindDevice->saveData(['id' => $aboutInfoId], $data);
                    } else {
                        $data['add_time'] = $nowTime;
                        $aboutInfoId = $dbFaceUserBindDevice->addData($data);
                    }
                    // 同步人员成功后 同步卡片或者人脸
                    $cardImage = isset($cardImageKeys[$employeeNo]) && $cardImageKeys[$employeeNo] ? $cardImageKeys[$employeeNo] : [];
                    $uid = isset($cardImage['uid']) && $cardImage['uid'] ? intval($cardImage['uid']) : 0;
                    $ic_card = isset($cardImage['ic_card']) && $cardImage['ic_card'] ? trim($cardImage['ic_card']) : '';
                    fdump_api(['msg' => 'IC卡', 'line' => __LINE__, 'cardImageKeys' => $cardImageKeys, 'cardImage' => $cardImage, 'ic_card' => $ic_card], 'hikGateWay/addUsersLog', 1);
                    if ($ic_card) {
                        // 存在卡片 todo 走队列执行卡片添加
                        $cardParams = [
                            'deviceType' => $device_equipment_type, 'device_id' => $device_id, 'device_sn' => $device_sn,
                            'device_type' => $face_device_type, 'thirdProtocol' => $thirdProtocol, 'nowTime' => $nowTime,
                            'employeeNo' => $employeeNo, 'cardNo' => $ic_card,
                        ];
                        $cardInfo = [
                            'employeeNo' => $employeeNo, 'cardNo' => $ic_card, 'cardType' => 'normalCard',
                        ];
                        if($no_job>0){
                            $this->addCard($deviceInfo, $cardInfo, $cardParams);
                        }else{
                            $cardParams['cardInfo'] = $cardInfo;
                            $cardParams['handleType'] = 'addCardBox';
                            $job_id = $this->traitCommonHikDeviceGateWay($cardParams);
                        }

                    } else {
                        // 查询下是否同步过卡片 然后确认下卡片没有被其他人使用 直接删除
                        $whereAbout = [
                            'bind_type' => DeviceConst::Hk_GATEWAY_USER_BIND_IC_CARD, 'third_protocol' => $thirdProtocol,
                            'device_id' => $device_id, 'bind_id' => $employeeNo,
                        ];
                        $aboutInfo = $dbFaceUserBindDevice->getOne($whereAbout, 'id, cardid', 'id DESC');
                        $cardid = isset($aboutInfo['cardid']) && $aboutInfo['cardid'] ? trim($aboutInfo['cardid']) : '';
                        if ($cardid) {
                            $village_id = isset($deviceInfo['village_id']) ? intval($deviceInfo['village_id']) : '';
                            $whereUserBind = [];
                            $whereUserBind[] = ['status', '=', 1];
                            $whereUserBind[] = ['village_id', '=', $village_id];
                            $whereUserBind[] = ['ic_card', '=', $cardid];
                            $otherIcCardUser = $houseVillageUserBindService->getBindInfo($whereUserBind, 'pigcms_id, ic_card');
                            if (!isset($otherIcCardUser['pigcms_id']) || (isset($otherIcCardUser['pigcms_id']) && intval($otherIcCardUser['pigcms_id']) != intval($employeeNo))) {
                                $cardInfoDelCondChange = [
                                    'CardNoList' => [
                                        ['cardNo' => $cardid]
                                    ]
                                ];
                                $changeDeleteResult = $this->deleteCard($deviceInfo, $cardInfoDelCondChange);
                                fdump_api(['msg' => 'IC卡移除了-删除卡片-结果', 'line' => __LINE__, 'cardInfoDelCondChange' => $cardInfoDelCondChange, 'changeDeleteResult' => $changeDeleteResult], 'hikGateWay/addUsersLog', 1);
                            }
                        }
                    }
                    if ($qr_content_card) {
                        // 存在卡片 todo 走队列执行卡片添加
                        $cardParams = [
                            'deviceType' => $device_equipment_type, 'device_id' => $device_id, 'device_sn' => $device_sn,
                            'device_type' => $face_device_type, 'thirdProtocol' => $thirdProtocol, 'nowTime' => $nowTime,
                            'employeeNo' => $employeeNo, 'qr_content_card' => $qr_content_card,
                        ];
                        $cardInfo = [
                            'employeeNo' => $employeeNo, 'cardNo' => $qr_content_card, 'cardType' => 'normalCard',
                        ];
                        if($no_job>0) {
                            $this->addCard($deviceInfo, $cardInfo, $cardParams);
                        }else{
                            $cardParams['cardInfo'] = $cardInfo;
                            $cardParams['handleType'] = 'addCardBox';
                            $job_id = $this->traitCommonHikDeviceGateWay($cardParams);
                        }

                    }
                    if ($uid > 0) {
                        // 存在人脸图片 todo 走队列执行卡片添加
                        $faceParams = [
                            'deviceType' => $device_equipment_type, 'device_id' => $device_id, 'device_sn' => $device_sn,
                            'device_type' => $face_device_type, 'thirdProtocol' => $thirdProtocol, 'nowTime' => $nowTime,
                            'employeeNo' => $employeeNo, 'cardNo' => $ic_card, 'uid' => $uid,
                        ];
                        if($no_job>0) {
                             // todo 直接走添加人脸
                             $this->addFaceDataBox($faceParams);
                        }else{
                            $faceParams['handleType'] = 'addFaceDataBox';
                            $job_id = $this->traitCommonHikDeviceGateWay($faceParams);  
                        }

                    }
                    $aboutArr[] = $aboutInfoId;
                }
            }
            $errmsg = '同步失败';
        } else {
            $statusCode = isset($result['statusCode']) ? $result['statusCode'] : 0;
            $subStatusCode = isset($result['subStatusCode']) ? trim($result['subStatusCode']) : '';
            $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
        }
        foreach ($employeeNoArr as $employeeNoId) {
            $updateBindParam = [
                'face_img_status' => 2, 'face_img_reason' => $errmsg,
            ];
            $houseVillageUserBindService->saveUserBind(['pigcms_id' => $employeeNoId], $updateBindParam);
            if (isset($params['log_id']) && $params['log_id']) {
                (new OwnerService())->updateLog(['log_id' => $params['log_id']], ['status' => 2, 'msg' => $errmsg, 'last_time' => time()]);
            } else {
                $syncLogParams = [
                    'pigcms_id' => $employeeNoId, 'nowTime' => time(), 'status' => 2, 'msg' => $errmsg,
                    'device_id' => $deviceInfo['device_id'],  'device_sn' => $deviceInfo['device_sn'],
                ];
                (new OwnerService())->handleHouseFaceSyncLog($syncLogParams);
            }
        }
        $result['redis'] = $redis;
        fdump_api(['msg' => '添加人员-拿到结果', 'line' => __LINE__, 'params' => $params, 'employeeNoArr' => $employeeNoArr, 'aboutArr' => $aboutArr, 'result' => $result, 'userInfoOut' => $userInfoOut], 'hikGateWay/addUsersLog', 1);
        return $result;
    }

    /**
     * 添加人脸-盒子.
     */
    public function addFaceDataBox($params) 
    {
        try {
            $device_id = isset($params['device_id']) ? intval($params['device_id']) : 0;
            $device_sn = isset($params['device_sn']) ? trim($params['device_sn']) : '';
            $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
            $whereFace = [];
            $whereFace[] = ['thirdProtocol', 'in', [DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM]];
            $village_id && $whereFace[] = ['village_id', '=', $village_id];
            if ($device_id) {
                $whereFace[] = ['device_id', '=', $device_id];
            } else {
                $whereFace[] = ['device_sn', '=', $device_sn];
                $whereFace[] = ['is_del', '=', 0];
            }
            $db_house_face_device = new HouseFaceDevice();//人脸门禁表
            $deviceInfo = $db_house_face_device->getOne($whereFace);
            if ($deviceInfo && !is_array($deviceInfo)) {
                $deviceInfo = $deviceInfo->toArray();
            }
            if (empty($deviceInfo)) {
                fdump_api([ 'msg' => '删除人员和关联的权限-错误', 'line' => __LINE__, 'deviceInfo' => $deviceInfo], 'hikGateWay/errAddUsersBoxLog', 1);
                return false;
            }
            if (!isset($params['imageUrl']) || !$params['imageUrl']) {
                $uid = isset($params['uid']) ? intval($params['uid']) : 0;
                $imageUrl = $this->getUserFaceImg($uid);
                $params['imageUrl'] = $imageUrl;
            }
            return $this->addFaceData($deviceInfo, $params);
        } catch (\Exception $e){
            fdump_api([ 'msg' => '添加人员异常捕捉盒子', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/addFaceDataBoxErrLog', 1);
        }
        return false;
    }

    /**
     * 添加单张卡片-盒子。
     */
    public function addCardBox($params)
    {
        try {
            $cardInfo = isset($params['cardInfo']) ? $params['cardInfo'] : [];
            $device_id = isset($params['device_id']) ? intval($params['device_id']) : 0;
            $device_sn = isset($params['device_sn']) ? trim($params['device_sn']) : '';
            $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
            $whereFace = [];
            $whereFace[] = ['thirdProtocol', 'in', [DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM]];
            $village_id && $whereFace[] = ['village_id', '=', $village_id];
            if ($device_id) {
                $whereFace[] = ['device_id', '=', $device_id];
            } else {
                $whereFace[] = ['device_sn', '=', $device_sn];
                $whereFace[] = ['is_del', '=', 0];
            }
            $db_house_face_device = new HouseFaceDevice();//人脸门禁表
            $deviceInfo = $db_house_face_device->getOne($whereFace);
            if ($deviceInfo && !is_array($deviceInfo)) {
                $deviceInfo = $deviceInfo->toArray();
            }
            if (empty($deviceInfo)) {
                fdump_api([ 'msg' => '删除人员和关联的权限-错误', 'line' => __LINE__, 'deviceInfo' => $deviceInfo], 'hikGateWay/errAddUsersBoxLog', 1);
                return false;
            }
            $params = unsetParam($params, 'cardInfo');
            return $this->addCard($deviceInfo, $cardInfo, $params);
        } catch (\Exception $e){
            fdump_api([ 'msg' => '添加单张卡片-异常捕捉盒子', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/addCardBoxErrLog', 1);
        }
        return false;
        
    }

    /**
     * 更新人员。(单个人员)
     */
    public function updateUser($deviceInfo, $userInfo) {
        fdump_api(['msg' => '修改人员信息', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/updateUserLog');
        $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
        $config = $this->getConfig($deviceInfo);
        $resultParams = $userInfo;
        $resultParams['Ac'] = 'updateUser';
        $resultParams['config'] = $config;
        $resultParams['cloud_device_id'] = $cloud_device_id;
        $redis = true;
        $tryCatch = false;
        $result = $this->requestResult($resultParams);
        if (empty($result)) {
            $redis = false;
            try {
                if (!$this->hikGate) {
                    $this->hikGate = new Gateway($config);
                }
                $result = $this->hikGate->Ac->updateUser($cloud_device_id, $userInfo);
            } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
                $resoponse = \json_decode( $exception->getResponse()->getBody()->getContents(),true);
                $tryCatch = true;
                if ((isset($resoponse['statusCode']) && intval($resoponse['statusCode']) != 1) || (isset($resoponse['subStatusCode']) && trim($resoponse['subStatusCode']) != 'ok')) {
                    $statusCode = $resoponse['statusCode'];
                    $subStatusCode = $resoponse['subStatusCode'];
                    $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
                    $resoponse['code'] = 1019;
                    $resoponse['message'] = $errmsg;
                    fdump_api(['msg' => '修改人员信息', 'line' => __LINE__, 'resoponse' => $resoponse], 'hikGateWay/updateUserLog', 1);
                    return $resoponse;
                }
                fdump_api(['msg' => '修改人员信息', 'line' => __LINE__, 'resoponse' => $resoponse], 'hikGateWay/updateUserLog', 1);
            }
            if (!$tryCatch) {
                $result = $this->requestResult($resultParams, $result);
            } else {
                $result = $resoponse;
            }
        }
        $result['redis'] = $redis;
        fdump_api(['msg' => '修改人员信息-拿到结果', 'line' => __LINE__, 'userInfo' => $userInfo, 'result' => $result, 'deviceInfo' => $deviceInfo], 'hikGateWay/updateUserLog', 1);
        return $result;
    }

    /**
     * 通过上传二进制数据的方式添加单个人脸记.
     */
    public function addFaceData($deviceInfo, $params) {
        fdump_api(['msg' => '通过上传二进制数据的方式添加单个人脸记-参数', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/addFaceDataLog');
        $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
        $imageUrl = isset($params['imageUrl']) && $params['imageUrl'] ? trim($params['imageUrl']) : '';
        $employeeNo = isset($params['employeeNo']) && $params['employeeNo'] ? trim($params['employeeNo']) : '';
        $device_id = isset($deviceInfo['device_id']) ? intval($deviceInfo['device_id']) : 0;
        $device_sn = isset($deviceInfo['device_sn']) ? trim($deviceInfo['device_sn']) : 0;
        !$device_id && $device_id = isset($params['device_id']) ? intval($params['device_id']) : 0;
        !$device_sn && $device_sn = isset($params['device_sn']) ? trim($params['device_sn']) : 0;
        $is_repeat = isset($params['is_repeat']) && intval($params['is_repeat']) > 0 ? intval($params['is_repeat']) : 0;
        $nowTime = isset($params['nowTime']) && intval($params['nowTime']) > 0 ? intval($params['nowTime']) : time();
 
        $config = $this->getConfig($deviceInfo);
        $faceInfo = [
            'employeeNo' => $employeeNo,
        ];
        $resultParams = [];
        $resultParams['FaceInfo'] = $faceInfo;
        $resultParams['imageUrl'] = $imageUrl;
        $resultParams['Ac'] = 'addFaceData';
        $resultParams['config'] = $config;
        $resultParams['cloud_device_id'] = $cloud_device_id;
        $redis = true;
        $tryCatch = false;
        $result = $this->requestResult($resultParams);
        if (empty($result)) {
            $redis = false;
            try {
                if (!$this->hikGate) {
                    $this->hikGate = new Gateway($config);
                }
                fdump_api(['msg' => '添加人脸-传参', 'line' => __LINE__, 'employeeNo' => $employeeNo, 'is_repeat' => $is_repeat,'devIndex' => $cloud_device_id, 'params' => $faceInfo, 'imageUrl' => $imageUrl], 'hikGateWay/addFaceDataLog', 1);
                $result = $this->hikGate->Ac->addFaceData($cloud_device_id, $faceInfo, $imageUrl);
            } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
                $resoponse = \json_decode( $exception->getResponse()->getBody()->getContents(),true);
                $tryCatch = true;
                fdump_api(['msg' => '通过上传二进制数据的方式添加单个人脸记-错误', 'line' => __LINE__, 'resoponse' => $resoponse], 'hikGateWay/addFaceDataLog', 1);
            }
            if (!$tryCatch) {
                $result = $this->requestResult($resultParams, $result);
            } else {
                $result = $resoponse;
            }
        }
        $imageHasExist = false;
        $imageHasErr = false;
        $houseVillageUserBindService = new HouseVillageUserBindService();
        $dbFaceUserBindDevice = new FaceUserBindDevice();
        if ((isset($result['statusCode']) && intval($result['statusCode']) != 1) || (isset($result['subStatusCode']) && trim($result['subStatusCode']) != 'ok')) {
            $statusCode = $result['statusCode'];
            $subStatusCode = $result['subStatusCode'];
            if ('deviceUserAlreadyExistFace' == $subStatusCode) {
                // 判断获取下人脸图片是否相同
                $face_json = $faceInfo;
                $face_json['imageUrl'] = $imageUrl;
                $face_json['cloud_device_id'] = $cloud_device_id;
                $face_json = json_encode($face_json, JSON_UNESCAPED_UNICODE);
                $face_grant_json = md5($face_json);
                $village_id = isset($deviceInfo['village_id']) ? intval($deviceInfo['village_id']) : '';
                $thirdProtocol = isset($deviceInfo['thirdProtocol']) ? trim($deviceInfo['thirdProtocol']) : '';
                $whereAbout = [
                    'bind_type' => DeviceConst::Hk_GATEWAY_USER_BIND_FACE_IMAGE, 'third_protocol' => $thirdProtocol,
                    'device_id' => $device_id, 'bind_id' => $employeeNo,
                ];
                $aboutInfo = $dbFaceUserBindDevice->getOne($whereAbout, 'id, face_grant_json', 'id DESC');
                // todo gateway 查询下记录的人脸是否已经成功上传过 上传过就直接当成成功
                $about_face_grant_json = isset($aboutInfo['face_grant_json']) && trim($aboutInfo['face_grant_json']) ? trim($aboutInfo['face_grant_json']) : '';
                fdump_api(['msg' => '判断是否确实重复上传', 'line' => __LINE__, 'employeeNo' => $employeeNo, 'is_repeat' => $is_repeat, 'face_json' => $face_json, 'face_grant_json' => $face_grant_json, 'about_face_grant_json' => $about_face_grant_json], 'hikGateWay/addFaceDataLog', 1);
                if ($about_face_grant_json == $face_grant_json) {
                    $imageHasExist = true;
                } elseif ($is_repeat != 1){
                    // 走下删除
                    $deleteResult = $this->deleteFaceData($deviceInfo, $params);
                    if ((isset($deleteResult['statusCode']) && intval($deleteResult['statusCode']) != 1) || (isset($deleteResult['subStatusCode']) && trim($deleteResult['subStatusCode']) != 'ok')) {
                        $statusCode = $deleteResult['statusCode'];
                        $subStatusCode = $deleteResult['subStatusCode'];
                        $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
                        $errmsg .= "[删除-更新人脸]";
                        $deleteResult['code'] = 512105;
                        $deleteResult['message'] = $errmsg;
                        $imageHasErr = true;
                        fdump_api(['msg' => '删除人脸记录-错误', 'line' => __LINE__, 'employeeNo' => $employeeNo, 'is_repeat' => $is_repeat, 'deleteResult' => $deleteResult, 'errmsg' => $errmsg], 'hikGateWay/addFaceDataLog', 1);
                    } elseif($is_repeat != 1) {
                        // 删除成功 回调添加人脸方法
                        $params['is_repeat'] = 1;
                        return $this->addFaceData($deviceInfo, $params);
                    }
                } else {
                    $imageHasExist = true;
                }
            } else {
                $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
                $errmsg .= "[添加人脸]";
            }
            if (!$imageHasExist) {
                $imageHasErr = true;
                $updateBindParam = [
                    'face_img_status' => 2, 'face_img_reason' => $errmsg,
                ];
                $houseVillageUserBindService->saveUserBind(['pigcms_id' => $employeeNo], $updateBindParam);
                $syncLogParams = [
                    'pigcms_id' => $employeeNo, 'nowTime' => time(), 'status' => 2, 'msg' => $errmsg,
                    'device_id' => $deviceInfo['device_id'],  'device_sn' => $deviceInfo['device_sn'],
                ];
                (new OwnerService())->handleHouseFaceSyncLog($syncLogParams);
            }
        }
        fdump_api(['msg' => '判断结果', 'line' => __LINE__, 'employeeNo' => $employeeNo, 'is_repeat' => $is_repeat, 'imageHasErr' => $imageHasErr, 'result' => $result], 'hikGateWay/addFaceDataLog', 1);
        if (!$imageHasErr) {
            // 同步人脸没有错误记录下
            $face_json = $faceInfo;
            $face_json['imageUrl'] = $imageUrl;
            $face_json['cloud_device_id'] = $cloud_device_id;
            $face_json = json_encode($face_json, JSON_UNESCAPED_UNICODE);
            $face_grant_json = md5($face_json);
            $village_id = isset($deviceInfo['village_id']) ? intval($deviceInfo['village_id']) : '';
            $thirdProtocol = isset($deviceInfo['thirdProtocol']) ? trim($deviceInfo['thirdProtocol']) : '';
            $whereAbout = [
                'bind_type' => DeviceConst::Hk_GATEWAY_USER_BIND_FACE_IMAGE, 'third_protocol' => $thirdProtocol,
                'device_id' => $device_id, 'bind_id' => $employeeNo,
            ];
            $bindAbout = [
                'bind_type' => DeviceConst::Hk_GATEWAY_USER_BIND_FACE_IMAGE, 'third_protocol' => $thirdProtocol,
                'device_id' => $device_id, 'bind_id' => $employeeNo, 'face_grant_json' => $face_grant_json,
                'face_img' => $imageUrl, 'group_id' => $village_id, 'face_text' => $face_json,
            ];
            $bindAbout['info'] = json_encode($result, JSON_UNESCAPED_UNICODE);
            $aboutInfo = $dbFaceUserBindDevice->getOne($whereAbout, 'id', 'id DESC');
            
            $aboutInfoId = isset($aboutInfo['id']) && $aboutInfo['id'] ? intval($aboutInfo['id']) : 0;
            fdump_api(['msg' => '记录', 'line' => __LINE__, 'employeeNo' => $employeeNo, 'is_repeat' => $is_repeat, 'aboutInfoId' => $aboutInfoId, 'bindAbout' => $bindAbout], 'hikGateWay/addFaceDataLog', 1);
            if ($aboutInfoId > 0) {
                $bindAbout['last_time'] = $nowTime;
                $dbFaceUserBindDevice->saveData(['id' => $aboutInfoId], $bindAbout);
            } else {
                $bindAbout['add_time'] = $nowTime;
                $aboutInfoId = $dbFaceUserBindDevice->addData($bindAbout);
            }
        }
        $result['redis'] = $redis;
        fdump_api(['msg' => '通过上传二进制数据的方式添加单个人脸记-结果', 'line' => __LINE__, 'employeeNo' => $employeeNo, 'is_repeat' => $is_repeat, 'cloud_device_id' => $cloud_device_id, 'faceInfo' => $faceInfo, 'imageUrl' => $imageUrl, 'result' => $result, 'deviceInfo' => $deviceInfo], 'hikGateWay/addFaceDataLog', 1);
        return $result;
    }

    /**
     * 删除人脸记录.
     */
    public function deleteFaceData($deviceInfo, $params) {
        fdump_api(['msg' => '删除人脸记录-参数', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/deleteFaceDataLog', 1);
        $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
        $imageUrl = isset($params['imageUrl']) && $params['imageUrl'] ? trim($params['imageUrl']) : '';
        $employeeNo = isset($params['employeeNo']) && $params['employeeNo'] ? trim($params['employeeNo']) : '';
        $faceLibType = isset($params['faceLibType']) && $params['faceLibType'] ? trim($params['faceLibType']) : 'staticFD';
        $device_id = isset($deviceInfo['device_id']) ? intval($deviceInfo['device_id']) : 0;
        $device_sn = isset($deviceInfo['device_sn']) ? trim($deviceInfo['device_sn']) : 0;
        !$device_id && $device_id = isset($params['device_id']) ? intval($params['device_id']) : 0;
        !$device_sn && $device_sn = isset($params['device_sn']) ? trim($params['device_sn']) : 0;
        $config = $this->getConfig($deviceInfo);
        $faceInfoDelCond = [
            'EmployeeNoList' => [
                ['employeeNo' => $employeeNo]
            ]
        ];
        $resultParams = [];
        $resultParams['FaceInfoDelCond'] = $faceInfoDelCond;
        $resultParams['Ac'] = 'deleteFaceData';
        $resultParams['config'] = $config;
        $resultParams['cloud_device_id'] = $cloud_device_id;
        $redis = true;
        $tryCatch = false;
        $result = $this->requestResult($resultParams);
        if (empty($result)) {
            $redis = false;
            try {
                if (!$this->hikGate) {
                    $this->hikGate = new Gateway($config);
                }
                $result = $this->hikGate->Ac->deleteFaceData($cloud_device_id, $faceInfoDelCond);
            } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
                $resoponse = \json_decode( $exception->getResponse()->getBody()->getContents(),true);
                $tryCatch = true;
                fdump_api(['msg' => '删除人脸记录-错误', 'line' => __LINE__, 'resoponse' => $resoponse], 'hikGateWay/deleteFaceDataLog', 1);
            }
            if (!$tryCatch) {
                $result = $this->requestResult($resultParams, $result);
            } else {
                $result = $resoponse;
            }
        }
        $result['redis'] = $redis;
        fdump_api(['msg' => '删除人脸记录-结果', 'line' => __LINE__, 'result' => $result], 'hikGateWay/deleteFaceDataLog', 1);
        return $result;
    }
    
    /**
     * 添加单张卡片。
     */
    public function addCard($deviceInfo, $cardInfo, $params = []) 
    {
        fdump_api(['msg' => '添加单张卡片', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/addCardLog', 1);
        $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
        $employeeNo = isset($deviceInfo['employeeNo']) ? intval($deviceInfo['employeeNo']) : '';
        !$employeeNo && $employeeNo = isset($cardInfo['employeeNo']) ? intval($cardInfo['employeeNo']) : '';
        $cardNo = isset($deviceInfo['cardNo']) ? trim($deviceInfo['cardNo']) : '';
        !$cardNo && $cardNo = isset($cardInfo['cardNo']) ? trim($cardInfo['cardNo']) : '';
        $device_id = isset($deviceInfo['device_id']) ? intval($deviceInfo['device_id']) : 0;
        $device_sn = isset($deviceInfo['device_sn']) ? trim($deviceInfo['device_sn']) : 0;
        !$device_id && $device_id = isset($params['device_id']) ? intval($params['device_id']) : 0;
        !$device_sn && $device_sn = isset($params['device_sn']) ? trim($params['device_sn']) : 0;
        $qr_content_card = isset($params['qr_content_card']) ? trim($params['qr_content_card']) : '';
        !$cardNo && $qr_content_card && $cardNo = $qr_content_card;
        
        $is_repeat = isset($params['is_repeat']) ? intval($params['is_repeat']) : 0;
        $nowTime = isset($params['nowTime']) ? intval($params['nowTime']) : 0;

        $village_id = isset($deviceInfo['village_id']) ? intval($deviceInfo['village_id']) : '';
        $thirdProtocol = isset($deviceInfo['thirdProtocol']) ? trim($deviceInfo['thirdProtocol']) : '';
        $dbFaceUserBindDevice = new FaceUserBindDevice();
        $bind_type = DeviceConst::Hk_GATEWAY_USER_BIND_IC_CARD;
        if ($qr_content_card) {
            $bind_type = DeviceConst::Hk_GATEWAY_USER_BIND_QRCODE_CARD;
        }
        $whereAbout = [
            'bind_type' => $bind_type, 'third_protocol' => $thirdProtocol, 'device_id' => $device_id, 'bind_id' => $employeeNo,
        ];
        $aboutInfo = $dbFaceUserBindDevice->getOne($whereAbout, 'id, cardid', 'id DESC');
        $aboutInfoId = isset($aboutInfo['id']) && $aboutInfo['id'] ? intval($aboutInfo['id']) : 0;
        $cardid = isset($aboutInfo['cardid']) && $aboutInfo['cardid'] ? trim($aboutInfo['cardid']) : '';
        if ($bind_type == DeviceConst::Hk_GATEWAY_USER_BIND_IC_CARD && $cardid && $cardNo && $cardid != $cardNo) {
            $houseVillageUserBindService = new HouseVillageUserBindService();
            $whereUserBind = [];
            $whereUserBind[] = ['status', '=', 1];
            $whereUserBind[] = ['village_id', '=', $village_id];
            $whereUserBind[] = ['ic_card', '=', $cardid];
            $otherIcCardUser = $houseVillageUserBindService->getBindInfo($whereUserBind, 'pigcms_id, ic_card');
            if (!isset($otherIcCardUser['pigcms_id']) || (isset($otherIcCardUser['pigcms_id']) && intval($otherIcCardUser['pigcms_id']) != intval($employeeNo))) {
                $cardInfoDelCondChange = [
                    'CardNoList' => [
                        ['cardNo' => $cardid]
                    ]
                ];
                $changeDeleteResult = $this->deleteCard($deviceInfo, $cardInfoDelCondChange);
                fdump_api(['msg' => 'IC卡移除了-删除卡片-结果', 'line' => __LINE__, 'cardInfoDelCondChange' => $cardInfoDelCondChange, 'changeDeleteResult' => $changeDeleteResult], 'hikGateWay/addCardLog', 1);
            }
        }
        $config = $this->getConfig($deviceInfo);
        $resultParams = [];
        $resultParams['Ac'] = 'addCard';
        $resultParams['cardInfo'] = $cardInfo;
        $resultParams['config'] = $config;
        $resultParams['cloud_device_id'] = $cloud_device_id;
        $redis = true;
        $tryCatch = false;
        $result = $this->requestResult($resultParams);
        $houseVillageUserBindService = new HouseVillageUserBindService();
        if (empty($result)) {
            $redis = false;
            try {
                if (!$this->hikGate) {
                    $this->hikGate = new Gateway($config);
                }
                $result = $this->hikGate->Ac->addCard($cloud_device_id, $cardInfo);
            } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
                $resoponse = \json_decode( $exception->getResponse()->getBody()->getContents(),true);
                $tryCatch = true;
            }
            if (!$tryCatch) {
                $result = $this->requestResult($resultParams, $result);
            } else {
                $result = $resoponse;
            }
        }
        fdump_api(['msg' => '添加单张卡片-结果', 'line' => __LINE__, 'cloud_device_id' => $cloud_device_id, 'cardInfo' => $cardInfo, 'result' => $result], 'hikGateWay/addCardLog', 1);
        $isSuccess = false;
        if ((isset($result['statusCode']) && intval($result['statusCode']) != 1) || (isset($result['subStatusCode']) && trim($result['subStatusCode']) != 'ok')) {
            $statusCode = $result['statusCode'];
            $subStatusCode = $result['subStatusCode'];
            $userHasExist = false;
            $otherHasExist = false;
            if ('cardNoAlreadyExist' == $subStatusCode) {
                $searchID = md5(json_encode($cardInfo, JSON_UNESCAPED_UNICODE));
                $cardInfoSearchCond = [
                    'searchID' => $searchID, 'searchResultPosition' => 0, 'maxResults' => 100,
                    'CardNoList' => [
                        ['cardNo' => $cardNo]
                    ],
                ];
                $cardResult = $this->searchCard($deviceInfo, $cardInfoSearchCond);
                $cardInfoSearch = isset($cardResult['CardInfoSearch']) ? $cardResult['CardInfoSearch'] : [];
                $cardInfoList = isset($cardInfoSearch['CardInfo']) ? $cardInfoSearch['CardInfo'] : [];
                foreach ($cardInfoList as $itemCard) {
                    $itemEmployeeNo = isset($itemCard['employeeNo']) && $itemCard['employeeNo'] ? trim($itemCard['employeeNo']) : '';
                    if ($itemEmployeeNo == trim($employeeNo)) {
                        $userHasExist = true;
                    } else {
                        $otherHasExist = true;
                    }
                }
            }
            if (!$userHasExist && $otherHasExist && $is_repeat != 1) {
                // 因为卡号绑定到别的地方了 先删除再走添加卡片
                $cardInfoDelCond = [
                    'CardNoList' => [
                        ['cardNo' => $cardNo]
                    ]
                ];
                $deleteResult = $this->deleteCard($deviceInfo, $cardInfoDelCond);
                fdump_api(['msg' => '删除卡片-结果', 'line' => __LINE__, 'cardInfoDelCond' => $cardInfoDelCond, 'deleteResult' => $deleteResult], 'hikGateWay/addCardLog', 1);
                $deleteStatusCode = $deleteResult['statusCode'];
                $deleteSubStatusCode = $deleteResult['subStatusCode'];
                if ($deleteStatusCode == 1 && $deleteSubStatusCode == 'ok') {
                    // 删除成功重新走添加
                    $params['is_repeat'] = 1;
                    return $this->addCard($deviceInfo, $cardInfo, $params);
                }
            }
            if (!$userHasExist) {
                $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
                $updateBindParam = [
                    'face_img_status' => 2, 'face_img_reason' => $errmsg . '[添加IC卡]',
                ];
                $houseVillageUserBindService->saveUserBind(['pigcms_id' => $employeeNo], $updateBindParam);
                $syncLogParams = [
                    'pigcms_id' => $employeeNo, 'nowTime' => time(), 'status' => 2, 'msg' => $errmsg,
                    'device_id' => $deviceInfo['device_id'],  'device_sn' => $deviceInfo['device_sn'],
                ];
                (new OwnerService())->handleHouseFaceSyncLog($syncLogParams);
                $result['error_message'] = $errmsg;
            } else {
                $isSuccess = true;
            }
        } else {
            // 添加卡片成功
            $isSuccess = true;
        }
        if ($isSuccess) {
            $data = [
                'bind_type' => $bind_type, 'third_protocol' => $thirdProtocol, 'bind_id' => $employeeNo, 
                'person_id' => $employeeNo, 'device_id' => $device_id, 'group_id' => $village_id, 
                'cardid' => $cardNo, 'card_grant_json' => json_encode($cardInfo, JSON_UNESCAPED_UNICODE),
            ];
            if ($aboutInfoId > 0) {
                $data['last_time'] = $nowTime;
                $dbFaceUserBindDevice->saveData(['id' => $aboutInfoId], $data);
            } else {
                $data['add_time'] = $nowTime;
                $aboutInfoId = $dbFaceUserBindDevice->addData($data);
            }
            if ($bind_type == DeviceConst::Hk_GATEWAY_USER_BIND_QRCODE_CARD) {
                $db_house_device_qrcode = new HouseDeviceQrcode();
                $device_equipment_type = DeviceConst::DEVICE_TYPE_FACE;
                $whereQrcode = [];
                $whereQrcode[] = ['device_equipment_type', '=', $device_equipment_type];
                $whereQrcode[] = ['bind_type', '=', DeviceQrcodeConst::BIND_TYPE_VILLAGE_USER];
                $whereQrcode[] = ['bind_from', '=', DeviceQrcodeConst::BIND_FROM_HOUSE_VILLAGE_USER_BIND];
                $whereQrcode[] = ['bind_id', '=', $employeeNo];
                $whereQrcode[] = ['device_id', '=', $device_id];
                $whereQrcode[] = ['delete_time', '=', 0];
                $qrcodeField = 'id';
                $qrcodeInfo = $db_house_device_qrcode->getColumn($whereQrcode, $qrcodeField, 'device_id');
                $device_qrcode_id = isset($qrcodeInfo['id']) ? intval($qrcodeInfo['id']) : 0;
                $gateWayUrl = cfg('site_url') . '/index.php?c=Recognition&a=get_own_qrcode&qrCon=' . $cardNo;
                $visitor_open_number_limit = (new HouseDeviceQrcodeService())->getOpenNumberMaxLimit($village_id);

                try {
                    $userTimes = (new VisitorInviteService())->getUserEndTime($employeeNo, ['isDelay' => 1]);
                } catch (\Exception $e){
                    fdump_api([ 'msg' => '获取到期时间异常错误', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errDeviceAllToCloudLog', 1);
                    $userTimes = [];
                }
                $todayStartTime = strtotime(date('Y-m-d 00:00:00', $nowTime));
                $todayEndTime = strtotime(date('Y-m-d 23:59:59', $nowTime));
                
                $propertyStartTime = isset($userTimes['propertyStartTime']) ? intval($userTimes['propertyStartTime']) : 0;
                
                $whereUserBind = [];
                $whereUserBind[] = ['pigcms_id', '=', $employeeNo];
                $field = 'pigcms_id, type';
                $userBinds = $houseVillageUserBindService->getBindInfo($whereUserBind,$field);
                if ($userBinds && !is_array($userBinds)) {
                    $userBinds = $userBinds->toArray();
                }
                $type = isset($userBinds['type']) ? intval($userBinds['type']) : 0;
                if ($type == 4) {
                    $propertyStartTime = $todayStartTime;
                }
                if ($propertyStartTime >= $todayStartTime) {
                    $todayStartTime = strtotime(date('Y-m-d 00:00:00', $propertyStartTime));
                    $todayEndTime = strtotime(date('Y-m-d 23:59:59', $propertyStartTime));
                }
                $bind_arr = [
                    'device_equipment_type' => $device_equipment_type, 'third_protocol' => $thirdProtocol,
                    'device_id' => $device_id, 'bind_type' => DeviceQrcodeConst::BIND_TYPE_VILLAGE_USER, 'bind_id' => $employeeNo,
                    'bind_from' => DeviceQrcodeConst::BIND_FROM_HOUSE_VILLAGE_USER_BIND, 'qr_content_txt' => trim($cardNo),
                    'qr_content_url' => trim($gateWayUrl), 'qr_syn_time' => $nowTime, 'qr_syn_status' => 1, 'open_times' => $visitor_open_number_limit,
                    'opened_times' => 0, 'effective_start_time' => $todayStartTime, 'effective_end_time' => $todayEndTime,
                ];
                if ($device_qrcode_id <= 0) {
                    $bind_arr['add_time'] = $nowTime;
                    $db_house_device_qrcode->add($bind_arr);
                } else {
                    $db_house_device_qrcode->updateThis(['id' => $device_qrcode_id], $bind_arr);
                }
            }
        }
        $result['redis'] = $redis;
        fdump_api(['msg' => '添加单张卡片-拿到结果', 'line' => __LINE__, 'cardInfo' => $cardInfo, 'result' => $result, 'deviceInfo' => $deviceInfo], 'hikGateWay/addCardLog', 1);
        return $result;
    }

    /**
     * 查询卡片信息.
     */
    public function searchCard($deviceInfo, $cardInfoSearchCond) {
        fdump_api(['msg' => '查询卡片信息', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/searchCardLog', 1);
        $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
        $config = $this->getConfig($deviceInfo);
        $resultParams = [];
        $resultParams['CardInfoSearchCond'] = $cardInfoSearchCond;
        $resultParams['Ac'] = 'searchCard';
        $resultParams['config'] = $config;
        $resultParams['cloud_device_id'] = $cloud_device_id;
        $redis = true;
        $tryCatch = false;
        $result = $this->requestResult($resultParams);
        if (empty($result)) {
            $redis = false;
            try {
                if (!$this->hikGate) {
                    $this->hikGate = new Gateway($config);
                }
                $result = $this->hikGate->Ac->searchCard($cloud_device_id, $cardInfoSearchCond);
            } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
                $resoponse = \json_decode( $exception->getResponse()->getBody()->getContents(),true);
                $tryCatch = true;
                fdump_api(['msg' => '修改人员信息', 'line' => __LINE__, 'resoponse' => $resoponse], 'hikGateWay/searchCardLog', 1);
            }
            if (!$tryCatch) {
                $result = $this->requestResult($resultParams, $result);
            } else {
                $result = $resoponse;
            }
        }
        $result['redis'] = $redis;
        fdump_api(['msg' => '查询卡片信息-结果', 'line' => __LINE__, 'cloud_device_id' => $cloud_device_id, 'cardInfoSearchCond' => $cardInfoSearchCond, 'result' => $result, 'deviceInfo' => $deviceInfo], 'hikGateWay/searchCardLog', 1);
        return $result;
    }

    /**
     * 删除卡片.
     */
    public function deleteCard($deviceInfo, $cardInfoDelCond) {
        fdump_api(['msg' => '删除卡片', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/deleteCardLog', 1);
        $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
        $config = $this->getConfig($deviceInfo);
        $resultParams = [];
        $resultParams['CardInfoDelCond'] = $cardInfoDelCond;
        $resultParams['Ac'] = 'deleteCard';
        $resultParams['config'] = $config;
        $resultParams['cloud_device_id'] = $cloud_device_id;
        $redis = true;
        $tryCatch = false;
        $result = $this->requestResult($resultParams);
        if (empty($result)) {
            $redis = false;
            try {
                if (!$this->hikGate) {
                    $this->hikGate = new Gateway($config);
                }
                $result = $this->hikGate->Ac->deleteCard($cloud_device_id, $cardInfoDelCond);
            } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
                $resoponse = \json_decode( $exception->getResponse()->getBody()->getContents(),true);
                $tryCatch = true;
                fdump_api(['msg' => '删除卡片', 'line' => __LINE__, 'resoponse' => $resoponse], 'hikGateWay/deleteCardLog', 1);
            }
            if (!$tryCatch) {
                $result = $this->requestResult($resultParams, $result);
            } else {
                $result = $resoponse;
            }
        }
        $result['redis'] = $redis;
        fdump_api(['msg' => '删除卡片-结果', 'line' => __LINE__, 'cloud_device_id' => $cloud_device_id, 'cardInfoDelCond' => $cardInfoDelCond, 'result' => $result, 'deviceInfo' => $deviceInfo], 'hikGateWay/deleteCardLog', 1);
        return $result;
    }
    
    /**
     * 删除人员和关联的权限-盒子
     * @param array $deviceInfo 设备信息
     * @param array $params 额外信息
     * @param array $employeeNo 人员数组 [102, 602, 502]
     * @param string $mode 删除模式："all"（全部删除）、"byEmployeeNo"（按工号或人员 ID 删除
     */
    public function deleteUserBox($deviceInfo, $params = [], $employeeNo = [], $mode = 'byEmployeeNo') {
        try {
            if (empty($deviceInfo) && !empty($params)) {
                $device_equipment_type = isset($params['deviceType']) ? trim($params['deviceType']) : '';
                $device_id = isset($params['device_id']) ? intval($params['device_id']) : 0;
                $device_sn = isset($params['device_sn']) ? trim($params['device_sn']) : '';
                $face_device_type = isset($params['device_type']) ? trim($params['device_type']) : '';
                $thirdProtocol = isset($params['thirdProtocol']) ? intval($params['thirdProtocol']) : 0;
                $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
                $whereFace = [];
                $whereFace[] = ['thirdProtocol', 'in', [DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM]];
                $village_id && $whereFace[] = ['village_id', '=', $village_id];
                if ($device_id) {
                    $whereFace[] = ['device_id', '=', $device_id];
                } else {
                    $whereFace[] = ['device_sn', '=', $device_sn];
                    $whereFace[] = ['is_del', '=', 0];
                }
                $db_house_face_device = new HouseFaceDevice();//人脸门禁表
                $deviceInfo = $db_house_face_device->getOne($whereFace);
                if ($deviceInfo && !is_array($deviceInfo)) {
                    $deviceInfo = $deviceInfo->toArray();
                }
                if (empty($deviceInfo)) {
                    fdump_api([ 'msg' => '删除人员和关联的权限-错误', 'line' => __LINE__, 'deviceInfo' => $deviceInfo], 'hikGateWay/errDeleteUserBoxLog', 1);
                    return ['code' => 201, 'message' => '设备不存在'];
                }
            }
            return $this->deleteUser($deviceInfo, $employeeNo, $mode);
        } catch (\Exception $e){
            fdump_api([ 'msg' => '删除人员和关联的权限-异常捕捉盒子', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errDeleteUserBoxLog', 1);
        }
        return [];
    }
    
    /**
     * 删除人员和关联的权限
     * @param array $deviceInfo 设备信息
     * @param string $mode 删除模式："all"（全部删除）、"byEmployeeNo"（按工号或人员 ID 删除
     */
    public function deleteUser($deviceInfo, $employeeNo = [], $mode = 'byEmployeeNo') {
        fdump_api(['msg' => '删除人员和关联的权限', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/deleteUserLog', 1);
        $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
        $config = $this->getConfig($deviceInfo);
        $resultParams = [];
        $userInfoDetail = [
            'mode' => $mode, 'EmployeeNoList' => $employeeNo
        ];
        $resultParams['UserInfoDetail'] = $userInfoDetail;
        $resultParams['Ac'] = 'deleteUser';
        $resultParams['config'] = $config;
        $resultParams['cloud_device_id'] = $cloud_device_id;
        $redis = true;
        $tryCatch = false;
        $result = $this->requestResult($resultParams);
        fdump_api(['msg' => '删除人员和关联的权限', 'line' => __LINE__, 'cloud_device_id' => $cloud_device_id, 'userInfoDetail' => $userInfoDetail], 'hikGateWay/deleteUserLog', 1);
        if (empty($result)) {
            $redis = false;
            try {
                if (!$this->hikGate) {
                    $this->hikGate = new Gateway($config);
                }
                $result = $this->hikGate->Ac->deleteUser($cloud_device_id, $userInfoDetail);
            } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
                $resoponse = \json_decode( $exception->getResponse()->getBody()->getContents(),true);
                $tryCatch = true;
                if ((isset($resoponse['statusCode']) && intval($resoponse['statusCode']) != 1) || (isset($resoponse['subStatusCode']) && trim($resoponse['subStatusCode']) != 'ok')) {
                    $statusCode = $resoponse['statusCode'];
                    $subStatusCode = $resoponse['subStatusCode'];
                    $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
                    $resoponse['code'] = 1019;
                    $resoponse['message'] = $errmsg;
                    fdump_api(['msg' => '删除人员和关联的权限', 'line' => __LINE__,'resultParams' => $resultParams, 'resoponse' => $resoponse], 'hikGateWay/deleteUserLog', 1);
                    return $resoponse;
                }
                fdump_api(['msg' => '删除人员和关联的权限', 'line' => __LINE__, 'resoponse' => $resoponse], 'hikGateWay/deleteUserLog', 1);
            }
            if (!$tryCatch) {
                $result = $this->requestResult($resultParams, $result);
            } else {
                $result = $resoponse;
            }
        }
        $statusCode = $result['statusCode'];
        $subStatusCode = $result['subStatusCode'];
        if (intval($statusCode) == 1 && trim($subStatusCode) == 'ok') {
            $result['code'] = 200;
            $result['message'] = '删除成功';
        } else {
            $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
            $result['code'] = 1019;
            $result['message'] = $errmsg;
        }
        $result['redis'] = $redis;
        fdump_api(['msg' => '删除人员和关联的权限-拿到结果', 'line' => __LINE__, 'resultParams' => $resultParams, 'result' => $result, 'deviceInfo' => $deviceInfo], 'hikGateWay/deleteUserLog', 1);
        return $result;
    }
    
    /**
     * 获取访问控制能力-盒子.
     */
    public function getCapabilityBox($deviceInfo, $params = []) {
        try {
            if (empty($deviceInfo) && !empty($params)) {
                $device_equipment_type = isset($params['deviceType']) ? trim($params['deviceType']) : '';
                $device_id = isset($params['device_id']) ? intval($params['device_id']) : 0;
                $device_sn = isset($params['device_sn']) ? trim($params['device_sn']) : '';
                $face_device_type = isset($params['device_type']) ? trim($params['device_type']) : '';
                $thirdProtocol = isset($params['thirdProtocol']) ? intval($params['thirdProtocol']) : 0;
                $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
                $device = $this->handleDeviceParams($device_equipment_type, $device_id, $device_sn, $face_device_type, $thirdProtocol, $village_id);
                if (isset($device['code']) && $device['code']) {
                    fdump_api([ 'msg' => '获取访问控制能力-错误', 'line' => __LINE__, 'device' => $device], 'hikGateWay/errGetCapabilityBoxLog', 1);
                    return $device;
                }
                $deviceInfo = isset($device['deviceInfo']) ? $device['deviceInfo'] : [];
            }
            return $this->getCapabilities($deviceInfo, $params);
        } catch (\Exception $e){
            fdump_api([ 'msg' => '获取访问控制能力-异常捕捉盒子', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'hikGateWay/errGetCapabilityBoxLog', 1);
        }
        return [];
    }
    
    /**
     * 获取访问控制能力.
     */
    public function getCapabilities($deviceInfo, $params = []) {
        fdump_api(['msg' => '获取访问控制能力', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/getCapabilitiesLog');
        $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
        $config = $this->getConfig($deviceInfo);
        $resultParams = [];
        $resultParams['Ac'] = 'getCapabilities';
        $resultParams['config'] = $config;
        $resultParams['cloud_device_id'] = $cloud_device_id;
        $redis = true;
        $result = $this->requestResult($resultParams);
        if (empty($result)) {
            $redis = false;
            try {
                if (!$this->hikGate) {
                    $this->hikGate = new Gateway($config);
                }
                $result = $this->hikGate->Ac->getCapabilities($cloud_device_id);
            } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
                $resoponse = \json_decode( $exception->getResponse()->getBody()->getContents(),true);

                if ((isset($resoponse['statusCode']) && intval($resoponse['statusCode']) != 1) || (isset($resoponse['subStatusCode']) && trim($resoponse['subStatusCode']) != 'ok')) {
                    $statusCode = $resoponse['statusCode'];
                    $subStatusCode = $resoponse['subStatusCode'];
                    $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
                    $resoponse['code'] = 1019;
                    $resoponse['message'] = $errmsg;
                    fdump_api(['msg' => '获取访问控制能力', 'line' => __LINE__, 'resoponse' => $resoponse], 'hikGateWay/getCapabilitiesLog', 1);
                }
                fdump_api(['msg' => '获取访问控制能力', 'line' => __LINE__, 'resoponse' => $resoponse], 'hikGateWay/getCapabilitiesLog', 1);
                return $resoponse;
            }
            $result = $this->requestResult($resultParams, $result);
        }
        fdump_api(['msg' => '获取访问控制能力-拿到结果', 'line' => __LINE__, 'params' => $params, 'result' => $result], 'hikGateWay/getCapabilitiesLog', 1);
        $result['redis'] = $redis;
        return $result;
    }

    /**
     * 远程控制门.
     */
    public function remoteControl($deviceInfo, $params = []) {
        fdump_api(['msg' => '远程控制门', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/remoteControlLog');
        // 命令："open"（开），"close"（关），"alwaysOpen"（常开），"alwaysClose"（常关/禁用），"visitorCallLadder" （访客呼梯），"householdCallLadder"（住户呼梯）
        $cmd = isset($params['cmd']) && trim($params['cmd']) ? trim($params['cmd']) : 'open';
        $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
//        $telecontrolId = isset($deviceInfo['cloud_group_id']) ? intval($deviceInfo['cloud_group_id']) : '';
        $telecontrolId = 1;
        $config = $this->getConfig($deviceInfo);
        $resultParams = [];
        $resultParams['Ac'] = 'remoteControl';
        $resultParams['config'] = $config;
        $resultParams['telecontrolId'] = $telecontrolId;
        $resultParams['cloud_device_id'] = $cloud_device_id;
        $resultParams['cmd'] = $cmd;
        $redis = true;
        fdump_api(['msg' => '远程控制门', 'line' => __LINE__, 'resultParams' => $resultParams], 'hikGateWay/remoteControlLog', 1);
        $result = $this->requestResult($resultParams);
        if (empty($result)) {
            $redis = false;
            try {
                if (!$this->hikGate) {
                    $this->hikGate = new Gateway($config);
                }
                $result = $this->hikGate->Ac->remoteControl($telecontrolId, $cloud_device_id, $cmd);
            } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
                $resoponse = \json_decode( $exception->getResponse()->getBody()->getContents(),true);

                if ((isset($resoponse['statusCode']) && intval($resoponse['statusCode']) != 1) || (isset($resoponse['subStatusCode']) && trim($resoponse['subStatusCode']) != 'ok')) {
                    $statusCode = $resoponse['statusCode'];
                    $subStatusCode = $resoponse['subStatusCode'];
                    $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
                    $resoponse['code'] = 1019;
                    $resoponse['message'] = $errmsg;
                    fdump_api(['msg' => '远程控制门', 'line' => __LINE__, 'resoponse' => $resoponse], 'hikGateWay/remoteControlLog', 1);
                }
                fdump_api(['msg' => '远程控制门', 'line' => __LINE__, 'resoponse' => $resoponse], 'hikGateWay/remoteControlLog', 1);
                return $resoponse;
            }
            $result = $this->requestResult($resultParams, $result, 10);
        }
        $result['redis'] = $redis;
        fdump_api(['msg' => '远程控制门-拿到结果', 'line' => __LINE__, 'params' => $params, 'result' => $result], 'hikGateWay/remoteControlLog', 1);
        if ((isset($result['statusCode']) && intval($result['statusCode']) != 1) || (isset($result['subStatusCode']) && trim($result['subStatusCode']) != 'ok')) {
            $statusCode = $result['statusCode'];
            $subStatusCode = $result['subStatusCode'];
            $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
            $result['code'] = 1019;
            $result['message'] = $errmsg;
            fdump_api(['msg' => '远程控制门', 'line' => __LINE__, 'result' => $result], 'hikGateWay/remoteControlLog', 1);
        }
        return $result;
    }

    /**
     * 获取设备所有监听服务器参数[并根据返回添加监听服务器].
     */
    public function getHttpHosts($deviceInfo, $params) {
        fdump_api(['msg' => '获取所有监听服务器参数', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/getHttpHostsLog', 1);
        $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
        if (!$cloud_device_id) {
            $device_equipment_type = isset($params['device_equipment_type']) ? trim($params['device_equipment_type']) : '';
            $village_id = isset($deviceInfo['village_id']) ? intval($deviceInfo['village_id']) : '';
            !$village_id && $village_id = isset($params['village_id']) ? intval($params['village_id']) : '';
            switch ($device_equipment_type) {
                case DeviceConst::DEVICE_TYPE_CAMERA:
                    $device_id = isset($deviceInfo['camera_id']) ? intval($deviceInfo['camera_id']) : '';
                    $device_sn = isset($deviceInfo['camera_sn']) ? trim($deviceInfo['camera_sn']) : '';
                    !$device_id && $device_id = isset($params['device_id']) ? intval($params['device_id']) : '';
                    !$device_sn && $device_sn = isset($params['device_sn']) ? trim($params['device_sn']) : '';
                    $whereCamera = [];
                    $whereCamera[] = ['thirdProtocol', 'in', [DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM]];
                    $village_id && $whereCamera[] = ['village_id', '=', $village_id];
                    if ($device_id) {
                        $whereCamera[] = ['camera_id', '=', $device_id];
                    } else {
                        $whereCamera[] = ['camera_sn', '=', $device_sn];
                        $whereCamera[] = ['camera_status', '<>', 4];
                    }
                    $db_house_camera_device = new HouseCameraDevice();
                    $deviceInfo = $db_house_camera_device->getOne($whereCamera);
                    if ($deviceInfo && !is_array($deviceInfo)) {
                        $deviceInfo = $deviceInfo->toArray();
                    }
                    $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
                    break;
                case DeviceConst::DEVICE_TYPE_FACE:
                    $device_id = isset($deviceInfo['device_id']) ? intval($deviceInfo['device_id']) : '';
                    $device_sn = isset($deviceInfo['device_sn']) ? trim($deviceInfo['device_sn']) : '';
                    !$device_id && $device_id = isset($params['device_id']) ? intval($params['device_id']) : '';
                    !$device_sn && $device_sn = isset($params['device_sn']) ? trim($params['device_sn']) : '';
                    $whereFace = [];
                    $whereFace[] = ['thirdProtocol', 'in', [DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM]];
                    $village_id && $whereFace[] = ['village_id', '=', $village_id];
                    if ($device_id) {
                        $whereFace[] = ['device_id', '=', $device_id];
                    } else {
                        $whereFace[] = ['device_sn', '=', $device_sn];
                        $whereFace[] = ['is_del', '=', 0];
                    }
                    $db_house_face_device = new HouseFaceDevice();//人脸门禁表
                    $deviceInfo = $db_house_face_device->getOne($whereFace);
                    if ($deviceInfo && !is_array($deviceInfo)) {
                        $deviceInfo = $deviceInfo->toArray();
                    }
                    $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
                    break;
            }
        }
        $config = $this->getConfig($deviceInfo);
        $resultParams = [];
        $resultParams['Event'] = 'gethttpHosts';
        $resultParams['config'] = $config;
        $resultParams['cloud_device_id'] = $cloud_device_id;
        $redis = true;
        $result = $this->requestResult($resultParams);
        fdump_api(['msg' => '获取所有监听服务器参数', 'line' => __LINE__, 'deviceInfo' => $deviceInfo, 'resultParams' => $resultParams], 'hikGateWay/getHttpHostsLog', 1);
        $tryCatch = false;
        if (empty($result)) {
            $redis = false;
            try {
                if (!$this->hikGate) {
                    $this->hikGate = new Gateway($config);
                }
                $result = $this->hikGate->Event->gethttpHosts($cloud_device_id);
            } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
                $resoponse = \json_decode( $exception->getResponse()->getBody()->getContents(),true);
                $tryCatch = true;
                if ((isset($resoponse['statusCode']) && intval($resoponse['statusCode']) != 1) || (isset($resoponse['subStatusCode']) && trim($resoponse['subStatusCode']) != 'ok')) {
                    $statusCode = $resoponse['statusCode'];
                    $subStatusCode = $resoponse['subStatusCode'];
                    $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
                    $resoponse['code'] = 1019;
                    $resoponse['message'] = $errmsg;
                    fdump_api(['msg' => '获取所有监听服务器参数', 'line' => __LINE__,'resultParams' => $resultParams, 'resoponse' => $resoponse], 'hikGateWay/getHttpHostsLog', 1);
//                    return $resoponse;
                }
            }
            if (!$tryCatch) {
                $result = $this->requestResult($resultParams, $result);
            } else {
                $result = $resoponse;
            }
            fdump_api(['msg' => '获取所有监听服务器参数', 'line' => __LINE__, 'cloud_device_id' => $cloud_device_id, 'result' => $result], 'hikGateWay/getHttpHostsLog', 1);
        }
        $eventNotificationUrl = "/v20/public/index.php/community/common.HikGateWay/eventNotification";
        $addressingFormatType = 'hostname';
        $site_url = cfg('site_url');
        $protocolType = 'HTTP';
        $protocol = isset($config['protocol']) && trim($config['protocol']) ? trim($config['protocol']) : '';
        if ($protocol && strtoupper($protocol) == 'HTTPS') {
            $hostName = str_replace('http://', '', $site_url);
            $hostName = str_replace('https://', '', $hostName);
            $protocolType = 'HTTPS';
            $addressingFormatType = 'hostname';
        } else {
            $hostName = str_replace('http://', '', $site_url);
            $hostName = str_replace('https://', '', $hostName);
            $addressingFormatType = 'hostname';
            $protocolType = 'HTTP';
        }
        $portNo = 80;
        $httpHostNotificationList = isset($result['HttpHostNotificationList']) ? $result['HttpHostNotificationList'] : [];
        $device_equipment_type = isset($params['device_equipment_type']) ? trim($params['device_equipment_type']) : '';
        $httpId = '2';
        if ($device_equipment_type == DeviceConst::DEVICE_TYPE_CAMERA) {
            $httpId = '1';
        }
        $ipAddress = '';
        // 接下来的操作  1-添加监听服务器 2-设置所有监听服务器参数 3-不做处理
        $isOperate = 1;
        $httpHostNotificationCount = count($httpHostNotificationList);
        foreach ($httpHostNotificationList as $httpHost) {
            $httpHostNotification = isset($httpHost['HttpHostNotification']) ? $httpHost['HttpHostNotification'] : [];
            $httpHostUrl = isset($httpHostNotification['url']) && $httpHostNotification['url'] ? trim($httpHostNotification['url']) : '';
            $httpHostId = isset($httpHostNotification['id']) && $httpHostNotification['id'] ? trim($httpHostNotification['id']) : '';
            $httpHostAddressingFormatType = isset($httpHostNotification['addressingFormatType']) && $httpHostNotification['addressingFormatType'] ? trim($httpHostNotification['addressingFormatType']) : '';
            $httpHostProtocolType = isset($httpHostNotification['protocolType']) && $httpHostNotification['protocolType'] ? trim($httpHostNotification['protocolType']) : '';
            $httpHostPortNo = isset($httpHostNotification['portNo']) && $httpHostNotification['portNo'] ? intval($httpHostNotification['portNo']) : '';
            $httpHostIpAddress = isset($httpHostNotification['ipAddress']) && $httpHostNotification['ipAddress'] ? trim($httpHostNotification['ipAddress']) : '';
            $httpHostHostName = isset($httpHostNotification['hostName']) && $httpHostNotification['hostName'] ? trim($httpHostNotification['hostName']) : '';
            if ($httpHostId == 2 || $httpHostUrl == $eventNotificationUrl) {
                if ($addressingFormatType != $httpHostAddressingFormatType || $protocolType != $httpHostProtocolType || $portNo != $httpHostPortNo || $ipAddress != $httpHostIpAddress || $hostName != $httpHostHostName) {
                    $isOperate = 2;
                    $httpId = $httpHostId;
                }
            } elseif ($httpHostNotificationCount == 1 && $httpHostId && $httpHostProtocolType != 'EHome') {
                $httpId = $httpHostId;
            }
        }
        fdump_api(['isOperate' => $isOperate, 'httpId' => $httpId, 'protocolType' => $protocolType, 'config' => $config], 'hikGateWay/getHttpHostsLog', 1);

        if (in_array($isOperate, [1, 2])) {
            $httpHostParams = [
                'id' => trim($httpId), 'url' => $eventNotificationUrl, 'protocolType' => $protocolType,
                'SubscribeEvent' => [
                    'heartbeat' => 30, //可选项，int，心跳间隔时间；单位：秒；默认值：30 秒
                    'eventMode' => 'all', //可选项，string，事件上报模式："all" （所有事件均需上报）、 list"（指定的 minorEvent 事件类型才需上报）
                ]
            ];
            if ($portNo) {
                $httpHostParams['portNo'] = $portNo;
            }
            if ($hostName) {
                $addressingFormatType = 'hostname';
                $httpHostParams['hostName'] = $hostName;
            } else {
                $addressingFormatType = 'ipaddress';
                $httpHostParams['ipAddress'] = $ipAddress;
            }
            $httpHostParams['addressingFormatType'] = $addressingFormatType;
            $params['httpHostParams'] = $httpHostParams;
            $setHttpHostResult = $this->setHttpHosts($deviceInfo, $params);
            $result['setHttpHostResult'] = $setHttpHostResult;
        }
        
        return $result;
    }

    /**
     * 获取设备报警输入输出信息.
     */
    public function getDeviceAlarmIOInfo($deviceInfo, $params) 
    {
        fdump_api(['msg' => '获取设备报警输入输出信息', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/getDeviceAlarmIOInfoLog');
        $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
        if (!$cloud_device_id) {
            $village_id = isset($deviceInfo['village_id']) ? intval($deviceInfo['village_id']) : '';
            $device_id = isset($deviceInfo['device_id']) ? intval($deviceInfo['device_id']) : '';
            $device_sn = isset($deviceInfo['device_sn']) ? trim($params['device_sn']) : '';
            !$device_id && $device_id = isset($params['device_id']) ? intval($params['device_id']) : '';
            !$device_sn && $device_sn = isset($params['device_sn']) ? trim($params['device_sn']) : '';
            !$village_id && $village_id = isset($params['village_id']) ? intval($params['village_id']) : '';
            $whereFace = [];
            $whereFace[] = ['thirdProtocol', 'in', [DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM]];
            $village_id && $whereFace[] = ['village_id', '=', $village_id];
            if ($device_id) {
                $whereFace[] = ['device_id', '=', $device_id];
            } else {
                $whereFace[] = ['device_sn', '=', $device_sn];
                $whereFace[] = ['is_del', '=', 0];
            }
            $db_house_face_device = new HouseFaceDevice();//人脸门禁表
            $deviceInfo = $db_house_face_device->getOne($whereFace);
            if ($deviceInfo && !is_array($deviceInfo)) {
                $deviceInfo = $deviceInfo->toArray();
            }
            $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
        }
        $config = $this->getConfig($deviceInfo);
        $resultParams['DeviceMgmt'] = 'getDeviceAlarmIOInfo';
        $resultParams['config'] = $config;
        $resultParams['cloud_device_id'] = $cloud_device_id;
        $redis = true;
        $result = $this->requestResult($resultParams);
        fdump_api(['msg' => '获取设备报警输入输出信息', 'line' => __LINE__, 'deviceInfo' => $deviceInfo, 'resultParams' => $resultParams], 'hikGateWay/getDeviceAlarmIOInfoLog', 1);
        if (empty($result)) {
            $redis = false;
            try {
                if (!$this->hikGate) {
                    $this->hikGate = new Gateway($config);
                }
                $result = $this->hikGate->DeviceMgmt->getDeviceAlarmIOInfo($cloud_device_id);
            } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
                $resoponse = \json_decode( $exception->getResponse()->getBody()->getContents(),true);

                $statusCode = $resoponse['statusCode'];
                $subStatusCode = $resoponse['subStatusCode'];
                fdump_api(['msg' => '获取所有监听服务器参数', 'line' => __LINE__, 'cloud_device_id' => $cloud_device_id, 'resoponse' => $resoponse], 'hikGateWay/getDeviceAlarmIOInfoLog', 1);
                return $resoponse;
            }
            $result = $this->requestResult($resultParams, $result);
            fdump_api(['msg' => '获取设备报警输入输出信息', 'line' => __LINE__, 'cloud_device_id' => $cloud_device_id, 'result' => $result], 'hikGateWay/getDeviceAlarmIOInfoLog', 1);
        }
        return $result;
    }
    
    public function subscribeDeviceMgmt($deviceInfo, $params) 
    {
        fdump_api(['msg' => '开启事件/报警订阅', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/subscribeDeviceMgmtLog');
        $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
        if (!$cloud_device_id) {
            $village_id = isset($deviceInfo['village_id']) ? intval($deviceInfo['village_id']) : '';
            $device_id = isset($deviceInfo['device_id']) ? intval($deviceInfo['device_id']) : '';
            $device_sn = isset($deviceInfo['device_sn']) ? trim($params['device_sn']) : '';
            !$device_id && $device_id = isset($params['device_id']) ? intval($params['device_id']) : '';
            !$device_sn && $device_sn = isset($params['device_sn']) ? trim($params['device_sn']) : '';
            !$village_id && $village_id = isset($params['village_id']) ? intval($params['village_id']) : '';
            $whereFace = [];
            $whereFace[] = ['thirdProtocol', 'in', [DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM]];
            $village_id && $whereFace[] = ['village_id', '=', $village_id];
            if ($device_id) {
                $whereFace[] = ['device_id', '=', $device_id];
            } else {
                $whereFace[] = ['device_sn', '=', $device_sn];
                $whereFace[] = ['is_del', '=', 0];
            }
            $db_house_face_device = new HouseFaceDevice();//人脸门禁表
            $deviceInfo = $db_house_face_device->getOne($whereFace);
            if ($deviceInfo && !is_array($deviceInfo)) {
                $deviceInfo = $deviceInfo->toArray();
            }
            $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
        }
        $config = $this->getConfig($deviceInfo);
        $subscribeParams = [
            'eventMode' => 'all', 'defenceMode' => 'all'
        ];
        $resultParams = $subscribeParams;
        $resultParams['Event'] = 'subscribeDeviceMgmt';
        $resultParams['config'] = $config;
        $resultParams['cloud_device_id'] = $cloud_device_id;
        $redis = true;
        $result = $this->requestResult($resultParams);
        fdump_api(['msg' => '开启事件/报警订阅', 'line' => __LINE__, 'resultParams' => $resultParams, 'subscribeParams' => $subscribeParams], 'hikGateWay/subscribeDeviceMgmtLog', 1);
        if (empty($result)) {
            $redis = false;
            try {
                if (!$this->hikGate) {
                    $this->hikGate = new Gateway($config);
                }
                $result = $this->hikGate->Event->subscribeDeviceMgmt($subscribeParams);
            } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
                $resoponse = \json_decode( $exception->getResponse()->getBody()->getContents(),true);

                $statusCode = $resoponse['statusCode'];
                $subStatusCode = $resoponse['subStatusCode'];
                fdump_api(['msg' => '开启事件/报警订阅', 'line' => __LINE__, 'cloud_device_id' => $cloud_device_id, 'resoponse' => $resoponse], 'hikGateWay/subscribeDeviceMgmtLog', 1);
                return $resoponse;
            }
            $result = $this->requestResult($resultParams, $result);
            fdump_api(['msg' => '开启事件/报警订阅', 'line' => __LINE__, 'cloud_device_id' => $cloud_device_id, 'result' => $result], 'hikGateWay/subscribeDeviceMgmtLog', 1);
        }
        return $result;
    }

    /**
     * 指定设备添加订阅的报警或事件类型。
     */
    public function setSingleSubscribeDeviceMgmt($deviceInfo, $params) {
        fdump_api(['msg' => '指定设备添加订阅的报警或事件类型', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/setSingleSubscribeDeviceMgmtLog');
        $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
        
        $config = $this->getConfig($deviceInfo);
        $setParam = [
            'devIndex' => $cloud_device_id, 'uploadMode' => 'all'
        ];
        $resultParams = $setParam;
        $resultParams['Event'] = 'getSubscribeDeviceMgmtV2';
        $resultParams['config'] = $config;
        $resultParams['cloud_device_id'] = $cloud_device_id;
        $redis = true;
        $result = $this->requestResult($resultParams);
        fdump_api(['msg' => '指定设备添加订阅的报警或事件类型', 'line' => __LINE__, 'resultParams' => $resultParams], 'hikGateWay/setSingleSubscribeDeviceMgmtLog', 1);
        if (empty($result)) {
            $redis = false;
            try {
                if (!$this->hikGate) {
                    $this->hikGate = new Gateway($config);
                }
                $result = $this->hikGate->Event->getSubscribeDeviceMgmtV2(1, $cloud_device_id, $setParam);
            } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
                $resoponse = \json_decode( $exception->getResponse()->getBody()->getContents(),true);

                $statusCode = $resoponse['statusCode'];
                $subStatusCode = $resoponse['subStatusCode'];
                fdump_api(['msg' => '指定设备添加订阅的报警或事件类型', 'line' => __LINE__, 'cloud_device_id' => $cloud_device_id, 'setParam' => $setParam, 'resoponse' => $resoponse], 'hikGateWay/setSingleSubscribeDeviceMgmtLog', 1);
                return $resoponse;
            }
            $result = $this->requestResult($resultParams, $result);
            fdump_api(['msg' => '指定设备添加订阅的报警或事件类型', 'line' => __LINE__, 'cloud_device_id' => $cloud_device_id, 'setParam' => $setParam, 'result' => $result], 'hikGateWay/setSingleSubscribeDeviceMgmtLog', 1);
        }
        return $result;
    }

    /**
     * 添加监听服务器.
     */
    public function setHttpHosts($deviceInfo, $params) {
        fdump_api(['msg' => '添加监听服务器', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/setHttpHostsLog', 1);
        $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
        $httpHostParams = isset($params['httpHostParams']) ? $params['httpHostParams'] : [];
        $repeat = isset($params['repeat']) ? intval($params['repeat']) : 0;
        $config = $this->getConfig($deviceInfo);
        $resultParams = $httpHostParams;
        $resultParams['Event'] = 'sethttpHosts';
        $resultParams['config'] = $config;
        $resultParams['cloud_device_id'] = $cloud_device_id;
        $redis = true;
        $tryErr = false;
        $result = $this->requestResult($resultParams);
        fdump_api(['msg' => '添加监听服务器', 'line' => __LINE__, 'httpHostParams' => $httpHostParams, 'resultParams' => $resultParams], 'hikGateWay/setHttpHostsLog', 1);
        if (empty($result)) {
            $redis = false;
            try {
                if (!$this->hikGate) {
                    $this->hikGate = new Gateway($config);
                }
                $result = $this->hikGate->Event->sethttpHosts($cloud_device_id, $httpHostParams);
            } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
                $resoponse = \json_decode( $exception->getResponse()->getBody()->getContents(),true);
                $tryErr = true;
                $result = $resoponse;
            }
            if (!$tryErr) {
                $result = $this->requestResult($resultParams, $result);
            }
        }
        $handleParams = [];
        $statusCode = isset($result['statusCode']) ? $result['statusCode'] : 0;
        $subStatusCode = isset($result['subStatusCode']) ? $result['subStatusCode'] : '';
        $statusString = isset($result['statusString']) ? $result['statusString'] : '';
        if ($repeat != 1 && $statusString === 'Device Busy' && isset($httpHostParams['id']) && $httpHostParams['id'] == '2') {
            $httpHostParams['id'] = 1;
            $params['httpHostParams'] = $httpHostParams;
            $params['repeat'] = 1;
            return $this->setHttpHosts($deviceInfo, $params);
        }
        if ($statusCode != 1 || $subStatusCode != 'ok') {
            $errmsg = \Fastwhal\HikDeviceGateway\StatusCodeErrMsg::getStatusCodeMsg($statusCode,$subStatusCode);
            $handleParams['syn_status'] = SynOverviewConst::SYN_ERR_FROM_DEVICE_CLOUD;
            $handleParams['syn_msg'] = "监听服务设置失败({$errmsg})";
        } else {
            $handleParams['syn_status'] = SynOverviewConst::SYN_SUCCESS;
            $handleParams['syn_msg'] = "监听服务设置成功";
        }
        $device_equipment_type = isset($params['device_equipment_type']) ? trim($params['device_equipment_type']) : '';
        switch ($device_equipment_type) {
            case DeviceConst::DEVICE_TYPE_CAMERA:
                $syn_type = SynOverviewConst::SET_CAMERA_HTTP_HOSTS;
                $device_id = isset($deviceInfo['camera_id']) ? intval($deviceInfo['camera_id']) : '';
                !$device_id && $device_id = isset($params['device_id']) ? intval($params['device_id']) : '';
                break;
            case DeviceConst::DEVICE_TYPE_FACE:
                $syn_type = SynOverviewConst::SET_FACE_HTTP_HOSTS;
                $device_id = isset($deviceInfo['device_id']) ? intval($deviceInfo['device_id']) : '';
                !$device_id && $device_id = isset($params['device_id']) ? intval($params['device_id']) : '';
                break;
        }
        $handleParams['cloud_device_id'] = $cloud_device_id;
        $handleParams['httpHostParams'] = $httpHostParams;
        $handleParams['syn_type'] = $syn_type;
        $handleParams['syn_id'] = $device_id;
        $handleParams['to_id'] = 0;
        $handleParams['nowTime'] = time();
        $handleParams['funs'] = __FUNCTION__;
        $handleParams['line'] = __LINE__;
        $handleParams['deviceInfo'] = $deviceInfo;
        $handleParams['interfaceName'] = 'Event/notification/httpHosts';
        $handleParams['statusCode'] = $statusCode;
        $handleParams['subStatusCode'] = $subStatusCode;
        (new SynOverviewService())->handleSynOverview($handleParams);
        fdump_api(['msg' => '添加监听服务器-结果', 'line' => __LINE__, 'cloud_device_id' => $cloud_device_id, 'result' => $result, 'httpHostParams' => $httpHostParams], 'hikGateWay/setHttpHostsLog', 1);
        return $result;
    }
    
    /**
     * 记录设备网络信息.
     */
    protected function handleHouseDeviceNetwork($networkInterfaceList, $params)
    {
        fdump_api(['msg' => '记录设备网络信息', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/handleHouseDeviceNetworkLog');
        $device_equipment_type = isset($params['device_equipment_type']) ? trim($params['device_equipment_type']) : '';
        $device_id = isset($params['device_id']) ? intval($params['device_id']) : 0;
        $deviceSerial = isset($params['device_sn']) ? trim($params['device_sn']) : '';
        $face_device_type = isset($params['device_type']) ? trim($params['device_type']) : '';
        $thirdProtocol = isset($params['thirdProtocol']) ? intval($params['thirdProtocol']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $nowTime = isset($params['nowTime']) ? intval($params['nowTime']) : time();
        $deviceInfo = isset($params['deviceInfo']) ? $params['deviceInfo'] : [];
        !$village_id && $village_id = isset($deviceInfo['village_id']) ? intval($deviceInfo['village_id']) : 0;
        $dbHouseDeviceNetwork = new HouseDeviceNetwork();
        switch ($device_equipment_type) {
            case DeviceConst::DEVICE_TYPE_CAMERA:
                $device_type = 'house_camera_device';
                !$device_id && $device_id = isset($deviceInfo['camera_id']) ? intval($deviceInfo['camera_id']) : 0;
                !$deviceSerial && $deviceSerial = isset($deviceInfo['camera_sn']) ? trim($deviceInfo['camera_sn']) : '';
                break;
            case DeviceConst::DEVICE_TYPE_FACE:
                $device_type = 'house_face_device';
                !$device_id && $device_id = isset($deviceInfo['device_id']) ? intval($deviceInfo['device_id']) : 0;
                !$deviceSerial && $deviceSerial = isset($deviceInfo['device_sn']) ? trim($deviceInfo['device_sn']) : '';
                break;
        }
        $newWorkArr = [];
        if (isset($networkInterfaceList['NetworkInterface']) && $networkInterfaceList['NetworkInterface']) {
            $networkInterfaceList = $networkInterfaceList['NetworkInterface'];
        }
        foreach ($networkInterfaceList as $networkInterface) {
            if (isset($networkInterface['NetworkInterface'])) {
                $item = $networkInterface['NetworkInterface'];
            } else {
                $item = $networkInterface;
            }
            $IPAddress = isset($item['IPAddress']) && $item['IPAddress'] ? $item['IPAddress'] : [];
            $default_gateway_address = isset($IPAddress['DefaultGateway']['ipAddress']) && trim($IPAddress['DefaultGateway']['ipAddress']) ? trim($IPAddress['DefaultGateway']['ipAddress']) : '';
            $primary_dns_address = isset($IPAddress['PrimaryDNS']['ipAddress']) && trim($IPAddress['PrimaryDNS']['ipAddress']) ? trim($IPAddress['PrimaryDNS']['ipAddress']) : '';
            $secondary_dns_address = isset($IPAddress['SecondaryDNS']['ipAddress']) && trim($IPAddress['SecondaryDNS']['ipAddress']) ? trim($IPAddress['SecondaryDNS']['ipAddress']) : '';
            $addressing_type = isset($IPAddress['addressingType']) && trim($IPAddress['addressingType']) ? trim($IPAddress['addressingType']) : '';
            $ip_address = isset($IPAddress['ipAddress']) && trim($IPAddress['ipAddress']) ? trim($IPAddress['ipAddress']) : '';
            $ip_version = isset($IPAddress['ipVersion']) && trim($IPAddress['ipVersion']) ? trim($IPAddress['ipVersion']) : '';
            $subnet_mask = isset($IPAddress['subnetMask']) && trim($IPAddress['subnetMask']) ? trim($IPAddress['subnetMask']) : '';
            $network_id = isset($item['id']) && $item['id'] ? $item['id'] : '';
            $mac_address = isset($item['macAddress']) && $item['macAddress'] ? $item['macAddress'] : '';
            $where = [];
            $where[] = ['device_type', '=', $device_type];
            if ($device_id) {
                $where[] = ['device_id', '=', $device_id];
            } else {
                $where[] = ['deviceSerial', '=', $deviceSerial];
            }
            $where[] = ['delete_time', '=', 0];
            $where[] = ['network_id', '=', $network_id];
            $netWorkInfo = $dbHouseDeviceNetwork->getOne($where, 'id');
            $netWorkInfoId = isset($netWorkInfo['id']) && $netWorkInfo['id'] ? intval($netWorkInfo['id']) : 0;
            $dataParams = [
                'device_type' => $device_type, 'village_id' => $village_id, 'device_id' => $device_id, 'deviceSerial' => $deviceSerial,
                'default_gateway_address' => $default_gateway_address, 'primary_dns_address' => $primary_dns_address,
                'secondary_dns_address' => $secondary_dns_address, 'addressing_type' => $addressing_type,
                'ip_address' => $ip_address, 'ip_version' => $ip_version, 'subnet_mask' => $subnet_mask,
                'network_id' => $network_id, 'mac_address' => $mac_address,
            ];
            fdump_api(['msg' => '记录设备网络信息', 'line' => __LINE__, 'dataParams' => $dataParams], 'hikGateWay/handleHouseDeviceNetworkLog', 1);
            if ($netWorkInfoId > 0) {
                $dataParams['update_time'] = $nowTime;
                $dbHouseDeviceNetwork->updateThis($where, $dataParams);
            } else {
                $dataParams['add_time'] = $nowTime;
                $netWorkInfoId = $dbHouseDeviceNetwork->add($dataParams);
            }
            fdump_api(['msg' => '记录设备网络信息', 'line' => __LINE__, 'netWorkInfoId' => $netWorkInfoId], 'hikGateWay/handleHouseDeviceNetworkLog',1);
            $newWorkArr[] = $dataParams;
        }
        return $newWorkArr;
    }
    
    /**
     * 获取小区网关.
     */
    public function getHouseDeviceGatewayInfo($where = [], $field = true, $order = []) {
        return (new HouseDeviceGateway())->getOne($where, $field, $order);
    }

    /**
     * 获取设备类型文本描述.
     */
    public function getDeviceTypeName($deviceType) {
        $deviceTypeArr = [
            'IPCamera' => '网络摄像机', 'IPDome' => '网络球机', 'DVR' => '数字录像机', 'HybirdNVR' => '混合网络录像机', 'NVR' => '网络录像机',
            'DVS' => '云台', 'IPZoom' => '云台', 'CVR' => '网关', 'Gateway' => '网关', 'SecurityCP' => '报警主机', 'ACS' => '门禁设备',
        ];
        return isset($deviceTypeArr[$deviceType]) ? $deviceTypeArr[$deviceType] : '';
    }

    /**
     * 获取并存储设备通道
     */
    protected function handleVideoInputChannelList($list, $params) {
        $device_equipment_type = isset($params['device_equipment_type']) ? trim($params['device_equipment_type']) : '';
        $device_id = isset($params['device_id']) ? intval($params['device_id']) : 0;
        $device_sn = isset($params['device_sn']) ? trim($params['device_sn']) : '';
        $face_device_type = isset($params['device_type']) ? trim($params['device_type']) : '';
        $thirdProtocol = isset($params['thirdProtocol']) ? intval($params['thirdProtocol']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $deviceInfo = isset($params['deviceInfo']) ? $params['deviceInfo'] : [];
        !$village_id && $deviceInfo && $village_id = isset($deviceInfo['village_id']) ? intval($deviceInfo['village_id']) : 0;
        $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
        $device_type = 'house_camera_device';
        switch ($device_equipment_type) {
            case DeviceConst::DEVICE_TYPE_FACE:
                $device_type = 'house_face_device';
                !$device_id && $deviceInfo && $device_id = isset($deviceInfo['device_id']) ? intval($deviceInfo['device_id']) : 0;
                !$device_sn && $deviceInfo && $device_sn = isset($deviceInfo['device_sn']) ? trim($deviceInfo['device_sn']) : '';
                break;
            case DeviceConst::DEVICE_TYPE_CAMERA:
                $device_type = 'house_camera_device';
                !$device_id && $deviceInfo && $device_id = isset($deviceInfo['camera_id']) ? intval($deviceInfo['camera_id']) : 0;
                !$device_sn && $deviceInfo && $device_sn = isset($deviceInfo['camera_sn']) ? trim($deviceInfo['camera_sn']) : '';
                break;
        }
        $dbHouseDeviceChannel = new HouseDeviceChannel();
        $nowTime = time();
        $channelIdArr = [];
        foreach ($list as $item) {
            $videoInputChannel = isset($item['VideoInputChannel']) ? $item['VideoInputChannel'] : [];
            // 通道号
            $id = isset($videoInputChannel['id']) ? $videoInputChannel['id'] : '';
            if (empty($videoInputChannel) || !$id) {
                continue;
            }
            $name = isset($videoInputChannel['name']) ? $videoInputChannel['name'] : '';
//            $inputPort = isset($videoInputChannel['inputPort']) ? $videoInputChannel['inputPort'] : '';
//            $videoInputEnabled = isset($videoInputChannel['videoInputEnabled']) ? $videoInputChannel['videoInputEnabled'] : '';
//            $videoFormat = isset($videoInputChannel['videoFormat']) ? $videoInputChannel['videoFormat'] : '';
//            $portType = isset($videoInputChannel['portType']) ? $videoInputChannel['portType'] : '';

            $whereRepeat = [
                'device_type' => $device_type,
                'channelNo'   => $id,
                'channelId'   => $id,
                'isDel'       => 0,
            ];
            $whereRepeat['device_id'] = $device_id;
            $repeatChannel = $dbHouseDeviceChannel->getOne($whereRepeat);
            $channel_txt = unsetParam($videoInputChannel, ['id', 'name']);
            $updateParam = [
                'device_type' => $device_type,
                'village_id' => $village_id,
                'device_id' => $device_id,
                'deviceSerial' => $device_sn,
                'channelId' => $id,
                'channelName' => $name,
                'channelNo' => $id,
                'deviceId' => $cloud_device_id
            ];
            if (!empty($channel_txt)) {
                $updateParam['channel_txt'] = json_encode($channel_txt, JSON_UNESCAPED_UNICODE);
            }
            if (isset($repeatChannel['channel_id']) && $repeatChannel['channel_id']) {
                $updateParam['update_time'] = $nowTime;
                $dbHouseDeviceChannel->updateThis($whereRepeat, $updateParam);
                $channel_id = $repeatChannel['channel_id'];
                $channelIdArr[] = [
                    'device_equipment_type' => $device_equipment_type,
                    'device_type' => $device_type,
                    'device_id' => $device_id,
                    'channel_id' => $channel_id,
                    'channelId' => $id,
                    'channelName' => $name,
                ];
            } else {
                $addParam = $updateParam;
                $addParam['add_time'] = $nowTime;
                $channel_id = $dbHouseDeviceChannel->add($addParam);
                if ($channel_id) {
                    $channelIdArr[] = [
                        'device_equipment_type' => $device_equipment_type,
                        'device_type' => $device_type,
                        'device_id' => $device_id,
                        'channel_id' => $channel_id,
                        'channelId' => $id,
                        'channelName' => $name,
                    ];
                }
            }
        }
        return $channelIdArr;
    }

    /**
     * 处理记录流地址.
     */
    protected function handleChannelUrl($resultChannel, $update) {
        fdump_api([ 'msg' => '处理记录流地址', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/handleChannelUrlLog');
        $dbHouseDeviceChannel = new HouseDeviceChannel();
        $device_equipment_type = isset($resultChannel['device_equipment_type']) ? trim($resultChannel['device_equipment_type']) : '';
        $device_type = isset($resultChannel['device_type']) ? trim($resultChannel['device_type']) : '';
        $device_id = isset($resultChannel['device_id']) ? trim($resultChannel['device_id']) : '';
        $channel_id = isset($resultChannel['channel_id']) ? intval($resultChannel['channel_id']) : 0;

        $url = isset($update['url']) ? trim($update['url']) : '';
        $nowTime = isset($update['nowTime']) ? intval($update['nowTime']) : time();
        $lookUrlType = isset($update['lookUrlType']) ? trim($update['lookUrlType']) : '';
        $channelUrlKey = isset($update['channelUrlKey']) ? trim($update['channelUrlKey']) : '';

        $whereChannel = [];
        $whereChannel[] = ['channel_id', '=', $channel_id];
        $updateData = ['update_time' => $nowTime];
        $channelUrlKey && $updateData[$channelUrlKey] = $url;
        fdump_api([ 'msg' => '存储预览流', 'line' => __LINE__, 'updateData' => $updateData], 'hikGateWay/handleChannelUrlLog', 1);
        $dbHouseDeviceChannel->updateThis($whereChannel, $updateData);
        if ($device_equipment_type) {
            switch ($device_equipment_type) {
                case DeviceConst::DEVICE_TYPE_FACE:

                    break;
                case DeviceConst::DEVICE_TYPE_CAMERA:
                    $camera = [
                        'look_url' => $url, 'lookUrlType' => $lookUrlType, 'last_time' => $nowTime
                    ];
                    fdump_api([ 'msg' => '存储预览流', 'line' => __LINE__, 'camera_id' => $device_id, 'camera' => $camera], 'hikGateWay/handleChannelUrlLog', 1);
                    (new HouseCameraDevice())->saveOne(['camera_id' => $device_id], $camera);
                    break;
            }
        } elseif ($device_type) {
            switch ($device_equipment_type) {
                case 'house_face_device':

                    break;
                case 'house_camera_device':
                    $camera = [
                        'look_url' => $url, 'lookUrlType' => $lookUrlType, 'last_time' => $nowTime
                    ];
                    fdump_api([ 'msg' => '存储预览流', 'line' => __LINE__, 'camera_id' => $device_id, 'camera' => $camera], 'hikGateWay/handleChannelUrlLog', 1);
                    (new HouseCameraDevice())->saveOne(['camera_id' => $device_id], $camera);
                    break;
            }
        }
        return true;
    }

    /**
     * 获取配置信息.
     */
    protected function getConfig($deviceInfo) {
        $thirdProtocol = isset($deviceInfo['thirdProtocol']) ? intval($deviceInfo['thirdProtocol']) : 0;
        $village_id = isset($deviceInfo['village_id']) ? intval($deviceInfo['village_id']) : 0;
        $gatewayinfo = isset($deviceInfo['gatewayinfo']) && $deviceInfo['gatewayinfo'] ? json_decode($deviceInfo['gatewayinfo'], 1) : [];
        if (empty($gatewayinfo) && !isset($deviceInfo['camera_id']) && isset($deviceInfo['device_id']) && $deviceInfo['device_id']) {
            $dbHouseFaceDeviceAbout = new HouseFaceDeviceAbout();
            $aboutInfo = $dbHouseFaceDeviceAbout->getOne(['device_id' => $deviceInfo['device_id']], 'device_id, gatewayinfo');
            $deviceInfo['gatewayinfo'] = isset($aboutInfo['gatewayinfo']) && $aboutInfo['gatewayinfo'] ? $aboutInfo['gatewayinfo'] : '';
            $gatewayinfo = isset($aboutInfo['gatewayinfo']) && $aboutInfo['gatewayinfo'] ? json_decode($aboutInfo['gatewayinfo'], 1) : [];
        }
        if (empty($gatewayinfo)) {
            $gatewayinfo = isset($deviceInfo['thirdAddress']) && $deviceInfo['thirdAddress'] ? json_decode($deviceInfo['thirdAddress'], 1) : [];
        }
        fdump_api([ 'msg' => '获取配置信息', 'line' => __LINE__, 'gatewayinfo' => $gatewayinfo, 'deviceInfo' => $deviceInfo], 'hikGateWay/getConfigLog');
        $protocol = isset($gatewayinfo['protocol']) && $gatewayinfo['protocol'] ? $gatewayinfo['protocol'] : '';
        $host = isset($gatewayinfo['host']) && $gatewayinfo['host'] ? $gatewayinfo['host'] : '';
        $port = isset($gatewayinfo['port']) && $gatewayinfo['port'] ? $gatewayinfo['port'] : '';
        $username = isset($gatewayinfo['username']) && $gatewayinfo['username'] ? $gatewayinfo['username'] : '';
        $password = isset($gatewayinfo['password']) && $gatewayinfo['password'] ? $gatewayinfo['password'] : '';
        $networkHost = isset($gatewayinfo['networkHost']) && $gatewayinfo['networkHost'] ? $gatewayinfo['networkHost'] : '';
        $rtspPort = isset($gatewayinfo['rtspPort']) && $gatewayinfo['rtspPort'] ? $gatewayinfo['rtspPort'] : '';
        $httpPort = isset($gatewayinfo['httpPort']) && $gatewayinfo['httpPort'] ? $gatewayinfo['httpPort'] : '';
        if (!$protocol || !$host || !$port || !$username || !$password) {
            switch ($thirdProtocol) {
                case DeviceConst::HIK_DEVICE_GATEWAY:
                    // 平台网关
                    $protocol = cfg('hikGatewayProtocol');
                    $host = cfg('hikGatewayHost');
                    $port = cfg('hikGatewayPort');
                    $username = cfg('hikGatewayUserName');
                    $password = cfg('hikGatewayPassword');
                    $networkHost = cfg('hikGatewayNetworkHost');
                    $rtspPort = cfg('hikGatewayRtspPort');
                    $httpPort = cfg('hikGatewayHttpPort');
                    break;
                case DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM:
                    // 小区网关
                    $villageGateway = $this->getHouseDeviceGatewayInfo(['village_id'=>$village_id,'is_open'=>1]);
                    $protocol = isset($villageGateway['protocol']) && $villageGateway['protocol'] ? $villageGateway['protocol'] : '';
                    $host = isset($villageGateway['host']) && $villageGateway['host'] ? $villageGateway['host'] : '';
                    $port = isset($villageGateway['port']) && $villageGateway['port'] ? $villageGateway['port'] : '';
                    $username = isset($villageGateway['username']) && $villageGateway['username'] ? $villageGateway['username'] : '';
                    $password = isset($villageGateway['password']) && $villageGateway['password'] ? $villageGateway['password'] : '';
                    $networkHost = isset($villageGateway['network_host']) && $villageGateway['network_host'] ? $villageGateway['network_host'] : '';
                    $rtspPort = isset($villageGateway['rtsp_port']) && $villageGateway['rtsp_port'] ? $villageGateway['rtsp_port'] : '';
                    $httpPort = isset($villageGateway['http_port']) && $villageGateway['http_port'] ? $villageGateway['http_port'] : '';
                    break;
            }
        }
        return [
            'protocol' => $protocol, // 协议类型，例如： HTTP（V1.1）和 RTSP（V1.0）。
            'host' => $host, // 网络设备的主机名、IP 地址或域名全称
            'port' => $port, // 若该字段未配置，会使用默认端口号。HTTP 的默  认端口号为 80，RTSP 的默认端口号为 554，HTTPS 的默认端口号为 443。
            'username' => $username, // 网关账号
            'password' => $password, // 网关账号密码
            'networkHost' => $networkHost, // 平台端口映射外部地址
            'rtspPort' => $rtspPort, // 平台端口映射RTSP外部端口
            'httpPort' => $httpPort, // 平台端口映射HTTP外部端口
        ];
    }

    /**
     * 同步后记录设备相关信息
     * @param array $param
     * @param array $addResult
     * @return bool
     */
    protected function recordFaceDevice(array $params = [], array $addResult = []) {
        fdump_api([ 'msg' => '同步后记录设备相关信息-参数', 'line' => __LINE__, 'parameter' => func_get_args()], 'hikGateWay/recordFaceDeviceLog', 1);
        $device_equipment_type = isset($params['deviceType']) ? trim($params['deviceType']) : '';
        $device_id = isset($params['device_id']) ? intval($params['device_id']) : 0;
        $device_sn = isset($params['device_sn']) ? trim($params['device_sn']) : '';
        $face_device_type = isset($params['device_type']) ? trim($params['device_type']) : '';
        $thirdProtocol = isset($params['thirdProtocol']) ? intval($params['thirdProtocol']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;

        $cloud_device_id = isset($addResult['cloud_device_id']) ? trim($addResult['cloud_device_id']) : '';
        $devIndex = isset($addResult['devIndex']) ? trim($addResult['devIndex']) : '';
        !$cloud_device_id && $devIndex && $cloud_device_id = $devIndex;
        $ehomeID = isset($addResult['ehomeID']) ? trim($addResult['ehomeID']) : '';
        $devName = isset($addResult['devName']) ? trim($addResult['devName']) : '';
        $login_time = isset($addResult['login_time']) ? intval($addResult['login_time']) : time();
        $notify_time = isset($addResult['notify_time']) ? intval($addResult['notify_time']) : time();
        $gatewayinfo = isset($addResult['gatewayinfo']) ? $addResult['gatewayinfo'] : [];
        switch ($device_equipment_type) {
            case DeviceConst::DEVICE_TYPE_CAMERA:
                $dbCameraDeviceBind = new CameraDeviceBind();
                $whereDeviceBind = [
                    'bind_type'      => DeviceConst::BIND_CAMERA_DEVICE,
                    'third_protocol' => $thirdProtocol,
                ];
                $device_id && $whereDeviceBind['bind_id'] = $device_id;
                $device_id && $whereDeviceBind['device_id'] = $device_id;
                $device_sn && $whereDeviceBind['device_sn'] = $device_sn;
                !$device_sn && $ehomeID && $whereDeviceBind['device_sn'] = $ehomeID;
                $bindInfo = $dbCameraDeviceBind->getOne($whereDeviceBind, 'id');
                fdump_api([ 'msg' => '同步后记录设备相关信息', 'line' => __LINE__, 'whereDeviceBind' => $whereDeviceBind, 'bindInfo' => $bindInfo], 'hikGateWay/recordFaceDeviceLog', 1);
                $bindData = [
                    'bind_type'      => DeviceConst::BIND_CAMERA_DEVICE,
                    'bind_id'        => $device_id,
                    'device_sn'      => $device_sn,
                    'device_id'      => $device_id,
                    'third_protocol' => $thirdProtocol,
                    'third_deviceId' => $cloud_device_id,
                    'add_time'       => $login_time,
                ];
                $updateParam = [
                    'last_time'  => $login_time,
                    'login_time' => $login_time,
                ];
                if ($cloud_device_id) {
                    $updateParam['cloud_device_id'] = $cloud_device_id;
                }
                if ($devName) {
                    $bindData['third_name']           = $devName;
                    $updateParam['cloud_device_name'] = $devName;
                }
                if (isset($addResult['devMode']) && $addResult['devMode']) {
                    $updateParam['device_model'] = $addResult['devMode'];
                }
                if (isset($addResult['devStatus']) && $addResult['devStatus']) {
                    $updateParam['camera_status'] = $addResult['devStatus'] == 'online' ? 0 : 1;
                }
                $keys = [
                    'cloud_device_id', 'ehomeID', 'devName', 'login_time', 'devMode'
                ];
                $cloud_txt = unsetParam($addResult, $keys);
                if (!empty($cloud_txt)) {
                    $updateParam['cloud_txt'] = json_encode($cloud_txt, JSON_UNESCAPED_UNICODE);
                }
                if (!empty($gatewayinfo)) {
                    $updateParam['gatewayinfo'] = json_encode($gatewayinfo, JSON_UNESCAPED_UNICODE);
                }
                fdump_api([ 'msg' => '同步后记录设备相关信息', 'line' => __LINE__, 'bindData' => $bindData, 'updateParam' => $updateParam], 'hikGateWay/recordFaceDeviceLog', 1);
                if (!$bindInfo || !isset($bindInfo['id'])) {
                    $dbCameraDeviceBind->add($bindData);
                    // 同步更新下 监控表中数据
                    $houseFaceDeviceService = new HouseFaceDeviceService();
                    $whereCamera = [
                        'camera_id' => $device_id
                    ];
                    $houseFaceDeviceService->saveCamera($whereCamera, $updateParam);
                } else {
                    // 同步更新下 监控表中数据
                    $houseFaceDeviceService = new HouseFaceDeviceService();
                    $whereCamera = [
                        'camera_id' => $device_id
                    ];
                    $houseFaceDeviceService->saveCamera($whereCamera, $updateParam);
                }
                if (isset($updateParam['camera_status']) && intval($updateParam['camera_status']) != 0) {
                    // 离线
                    $reason = '监控设备离线';
                    (new DeviceAlarmEventService())->faultNotice($device_id, $reason, true, $device_equipment_type);
                }
                if (isset($updateParam['camera_status']) && intval($updateParam['camera_status']) == 0) {
                    // 在线
                    (new DeviceAlarmEventService())->normalLog($device_id, $device_equipment_type);
                }
                return $device_id;
                break;
            case DeviceConst::DEVICE_TYPE_FACE:
                $dbHouseFaceDevice = new HouseFaceDevice();
                $whereFace = [
                    'device_id' => $device_id
                ];
                $updateParam = [
                    'last_time'  => $login_time,
                    'login_time' => $login_time,
                ];
                $notify_time && $updateParam['notify_time'] = $notify_time;
                if ($cloud_device_id) {
                    $updateParam['cloud_device_id'] = $cloud_device_id;
                }
                if ($devName) {
                    $updateParam['cloud_device_name'] = $devName;
                }
                if (isset($addResult['devMode']) && $addResult['devMode']) {
                    $updateParam['device_model'] = $addResult['devMode'];
                }
                if (isset($addResult['devStatus']) && $addResult['devStatus']) {
                    $updateParam['device_status'] = $addResult['devStatus'] == 'online' ? 1 : 2;
                }
                if (isset($addResult['thirdAddress']) && $addResult['thirdAddress']) {
                    $updateParam['thirdAddress'] = json_encode($addResult['thirdAddress'], JSON_UNESCAPED_UNICODE);
                }
                fdump_api([ 'msg' => '同步后记录设备相关信息', 'line' => __LINE__, 'whereFace' => $whereFace, 'updateParam' => $updateParam], 'hikGateWay/recordFaceDeviceLog', 1);
                $dbHouseFaceDevice->saveData($whereFace, $updateParam);

                if (isset($updateParam['device_status']) && intval($updateParam['device_status']) != 1) {
                    // 离线
                    $reason = '人脸设备离线';
                    (new DeviceAlarmEventService())->faultNotice($device_id, $reason, true, $device_equipment_type);
                }
                if (isset($updateParam['device_status']) && intval($updateParam['device_status']) == 1) {
                    // 在线
                    (new DeviceAlarmEventService())->normalLog($device_id, $device_equipment_type);
                }

                $dbHouseFaceDeviceAbout = new HouseFaceDeviceAbout();
                $whereAbout = ['device_id' => $device_id];
                $updateParamAbout = [];
                $updateParamAbout['last_dev_sync_time'] = $login_time;
                if (!empty($gatewayinfo)) {
                    $updateParamAbout['gatewayinfo'] = json_encode($gatewayinfo, JSON_UNESCAPED_UNICODE);
                }
                $dbHouseFaceDeviceAbout->updateThis($whereAbout, $updateParamAbout);
                return $device_id;
                break;
        }
    }

    /**
     * 缓存操作结果
     * @param $param
     * @param array $result
     * @param integer $time
     * @return array|mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function requestResult($param, $result = [], $time = 0) {
        $cacheTag  = HikConst::HIK_JOB_REDIS_TAG;
        $cacheKey  = HikConst::HIK_REQUEST_REDIS_KEY . md5(\json_encode($param));
        if ($time <= 0) {
            $time = HikConst::HIK_REQUEST_REDIS_TIMES;
        }
        if (empty($result)) {
            $resultJson  = Cache::store('redis')->get($cacheKey);
            if ($resultJson) {
                $result  = json_decode($resultJson, true);
                return $result;
            } else {
                return [];
            }
        } else {
            $resultJson = json_encode($result, JSON_UNESCAPED_UNICODE);
            Cache::store('redis')->tag($cacheTag)->set($cacheKey,$resultJson, $time);
        }
        return $result;
    }
}