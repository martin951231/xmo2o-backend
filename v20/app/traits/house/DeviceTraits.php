<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      设备一些公用方法
 */


namespace app\traits\house;


use app\community\model\db\DeviceAuth;
use app\community\model\db\DeviceBindInfo;
use app\community\model\db\FaceBindAboutInfo;
use app\community\model\db\FaceUserBindDevice;
use app\community\model\db\HouseFaceDevice;

use app\community\model\db\HouseFaceImg;
use app\community\model\db\HouseWorker;
use app\community\model\db\Device\FingerprintDevice;
use app\community\model\service\Device\AlarmDeviceService;
use app\community\model\service\Device\DeviceFingerprintService;

use app\community\model\service\Device\FaceDHYunRuiCloudDeviceService;
use app\community\model\service\Device\FaceHikCloudNeiBuDeviceService;
use app\community\model\service\Device\HikGateWayDeviceService;
use app\community\model\service\HouseFaceDeviceService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageSingleService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\OwnerService;
use app\consts\DahuaConst;
use app\consts\DeviceConst;
use app\consts\HikConst;
use face\wordencryption;
use file_handle\FileHandle;

use app\traits\ImageHandleTraits;

trait DeviceTraits
{
    use ImageHandleTraits;
    /**
     * 数据返回整合下
     * @param string $msg 返回信息
     * @param array $data 返回数据
     * @param int $code 返回码  0 标识无错误
     * @param int $backType 返回方式  为2时候有错误以抛出异常方式返回错误
     * @return array
     * [
     *  'code'  => '错误码  0标识无错误',
     *             104  无对应相关后续操作
     *             1001 设备信息不全[缺少设备id或者序列号]
     *             1002 小区信息不全[缺少小区id]
     *             1003 协议信息不全[缺少协议类型]
     *             1004 同步小区失败[同步设备云小区信息报错]
     *             1005 同步物业失败[同步设备云物业信息报错]
     *             1006 缺少相关配置项[一般是设备云相关配置项缺失]
     *             1007 缺少三方社区id[一般是设备云相关社区id缺失]
     *             1008 缺少物业id[缺少物业id]
     *             1009 新增组织失败
     *             1010 缺少单元数/层数/房屋数
     *             1011 新增楼栋单元房屋
     *             1012 错误的绑定对象
     *             1013 绑定失败
     *             1014 数值返回错误
     *             1015 小区未同步第三方
     *             1016 房屋绑定第三方
     *             1017 人员未绑定第三方
     *             1018 参数错误
     *             1019 三方返回错误
     *             1020 远程开门失败
     *             1021 缺少设备云id
     *             1022 缺少设备序号
     *             1023 楼栋未同步第三方
     *             1024 单元未同步第三方
     *             1025 房屋未同步第三方
     *             1044 设备不存在
     *             1041 人员不存在或者缺少人员ID
     *  'msg'   => '对应返回信息',
     *  'data'  => '返回数据',
     * ]
     * @throws \think\Exception
     */
    protected function backData($data = [], $msg = '成功', $code = 0, $backType = 1)
    {
        if ($code > 0 && $backType == 2) {
            throw new \think\Exception($msg, $code);
        }
        return [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ];
    }
    public $protocolDeviceTraits = [
        DeviceConst::HIK_DEVICE_TYPE, DeviceConst::DH_DEVICE_TYPE
    ];

    /** @var  integer thirdProtocol        协议 */
    protected $thirdProtocol;
    /** @var  integer property_id          物业id */
    protected $property_id;
    /** @var  integer village_id           小区id */
    protected $village_id;
    /** @var  integer single_id            楼栋id */
    protected $single_id;
    /** @var  integer floor_id             单元id */
    protected $floor_id;
    /** @var  integer layer_id             楼层id */
    protected $layer_id;
    /** @var  integer device_id            设备id(对应人脸或者摄像机设备id) */
    protected $device_id;
    /** @var  string device_sn             设备序列号(对应人脸或者摄像机设备序列号) */
    protected $device_sn;
    /** @var  string parent_third_id       同步的父级id或者编号 */
    protected $parent_third_id;
    /** @var  string village_user_bind_id  小区住户表pigcms_id 即表 house_village_user_bind */
    protected $village_user_bind_id;
    /** @var  string user_id               平台用户表uid   即表 user */
    protected $user_id;
    /** @var  string device_equipment_type 设备类型 目前支持 人脸 + 监控 + 指纹锁  即 face + camera + fingerprint */
    protected $device_equipment_type;
    /** @var  string property_third_id     物业 同步到第三方的云对应id */
    protected $property_third_id;
    /** @var  string property_third_code   物业 同步到第三方的云对应编号 */
    protected $property_third_code;
    /** @var  string village_third_id      小区 同步到第三方的云对应id */
    protected $village_third_id;
    /** @var  string village_third_code    小区 同步到第三方的云对应编号 */
    protected $village_third_code;
    /** @var  string third_community_code  小区 同步到第三方的云对应编码 */
    protected $third_community_code;
    /** @var  string build_third_id        楼栋 同步到第三方的云对应id */
    protected $build_third_id;
    /** @var  string build_third_code      楼栋 同步到第三方的云对应编号 */
    protected $build_third_code;
    /** @var  string unit_third_id         单元 同步到第三方的云对应id */
    protected $unit_third_id;
    /** @var  string unit_third_code       单元 同步到第三方的云对应编号 */
    protected $unit_third_code;
    /** @var  string layer_third_id        楼层 同步到第三方的云对应id */
    protected $layer_third_id;
    /** @var  string layer_third_code      楼层 同步到第三方的云对应编号 */
    protected $layer_third_code;
    /** @var  string room_third_id         房屋 同步到第三方的云对应id */
    protected $room_third_id;
    /** @var  string room_third_code       房屋 同步到第三方的云对应编号 */
    protected $room_third_code;
    /** @var  string user_third_id         人员 同步到第三方的云对应id */
    protected $user_third_id;
    /** @var  string user_third_code       人员 同步到第三方的云对应编号 */
    protected $user_third_code;
    /** @var  string room_id               小区房屋id */
    protected $room_id;
    /** @var  int    face_device_type      人脸设备类型 */
    protected $face_device_type;
    /** @var  int    device_operate_type   操作类型 */
    protected $device_operate_type;
    /** @var  string order_group_type        操作归属 */
    protected $order_group_type;
    /** @var  string order_group_id          同一次操作标记 */
    protected $order_group_id;
    /** @var int  开门计划  */
    protected $time_plan_index;
    /** @var string 三方设备id 云设备id */
    protected $device_third_id;
    /** @var string 人脸门禁同步人脸日志记录id */
    protected $log_id;

    protected function filterCommonParam(array $param) {
        $nowTime = $this->nowTime ? $this->nowTime : time();
        if (!isset($param['orderGroupId']) || !$param['orderGroupId']) {
            $orderGroupId   = md5(uniqid().$nowTime);// 标记统一执行命令
            $param['orderGroupId']   = $orderGroupId;
        }
        if (!isset($param['orderGroupType']) || !$param['orderGroupType']) {
            if (!$this->order_group_type) {
                $param['orderGroupType'] = 'handle_device';
            } else {
                $param['orderGroupType'] = $this->order_group_type;
            }
        }
        if (!isset($param['step_num']) || !$param['step_num']) {
            $param['step_num'] = 1;
        }
        $this->thirdProtocol         = isset($param['thirdProtocol'])       && $param['thirdProtocol']       ? $param['thirdProtocol']       : 0;
        $this->property_id           = isset($param['property_id'])         && $param['property_id']         ? $param['property_id']         : '';
        $this->village_id            = isset($param['village_id'])          && $param['village_id']          ? $param['village_id']          : 0;
        $this->single_id             = isset($param['single_id'])           && $param['single_id']           ? $param['single_id']           : 0;
        $this->floor_id              = isset($param['floor_id'])            && $param['floor_id']            ? $param['floor_id']            : 0;
        $this->layer_id              = isset($param['layer_id'])            && $param['layer_id']            ? $param['layer_id']            : 0;
        $this->device_id             = isset($param['device_id'])           && $param['device_id']           ? $param['device_id']           : 0;
        if (isset($param['device_serial']) && !isset($param['device_sn'])) {
            $this->device_sn = isset($param['device_serial']) && $param['device_serial'] ? $param['device_serial'] : '';
        } else {
            $this->device_sn = isset($param['device_sn']) && $param['device_sn'] ? $param['device_sn'] : '';
        }
        $this->parent_third_id       = isset($param['parent_third_id'])     && $param['parent_third_id']     ? $param['parent_third_id']     : '';
        $this->village_user_bind_id  = isset($param['pigcms_id'])           && $param['pigcms_id']           ? $param['pigcms_id']           : 0;
        $this->user_id               = isset($param['uid'])                 && $param['uid']                 ? $param['uid']                 : 0;
        $this->device_equipment_type = isset($param['deviceType'])          && $param['deviceType']          ? $param['deviceType']          : 'face';
        $this->property_third_id     = isset($param['property_third_id'])   && $param['property_third_id']   ? $param['property_third_id']   : '';
        $this->property_third_code   = isset($param['property_third_code']) && $param['property_third_code'] ? $param['property_third_code'] : '';
        $this->village_third_id      = isset($param['village_third_id'])    && $param['village_third_id']    ? $param['village_third_id']    : '';
        $this->village_third_code    = isset($param['village_third_code'])  && $param['village_third_code']  ? $param['village_third_code']  : '';
        $this->build_third_id        = isset($param['build_third_id'])      && $param['build_third_id']      ? $param['build_third_id']      : '';
        $this->build_third_code      = isset($param['build_third_code'])    && $param['build_third_code']    ? $param['build_third_code']    : '';
        $this->unit_third_id         = isset($param['unit_third_id'])       && $param['unit_third_id']       ? $param['unit_third_id']       : '';
        $this->unit_third_code       = isset($param['unit_third_code'])     && $param['unit_third_code']     ? $param['unit_third_code']     : '';
        $this->layer_third_id        = isset($param['layer_third_id'])      && $param['layer_third_id']      ? $param['layer_third_id']      : '';
        $this->layer_third_code      = isset($param['layer_third_code'])    && $param['layer_third_code']    ? $param['layer_third_code']    : '';
        $this->room_third_id         = isset($param['room_third_id'])       && $param['room_third_id']       ? $param['room_third_id']       : '';
        $this->room_third_code       = isset($param['room_third_code'])     && $param['room_third_code']     ? $param['room_third_code']     : '';
        $this->user_third_id         = isset($param['user_third_id'])       && $param['user_third_id']       ? $param['user_third_id']       : '';
        $this->user_third_code       = isset($param['user_third_code'])     && $param['user_third_code']     ? $param['user_third_code']     : '';
        $this->room_id               = isset($param['vacancy_id'])          && $param['vacancy_id']          ? $param['vacancy_id']          : '';
        $this->face_device_type      = isset($param['device_type'])         && $param['device_type']         ? $param['device_type']         : 0;
        if (!$this->room_id) {
            $this->room_id           = isset($param['room_id'])             && $param['room_id']             ? $param['room_id']             : '';
        }
        $this->device_operate_type   = isset($param['device_operate_type']) && $param['device_operate_type'] ? $param['device_operate_type'] : 0;
        $this->order_group_id        = isset($param['orderGroupId'])        && $param['orderGroupId']        ? $param['orderGroupId']        : 0;
        $this->order_group_type      = isset($param['orderGroupType'])      && $param['orderGroupType']      ? $param['orderGroupType']      : 0;
        $this->step_num              = isset($param['step_num'])            && $param['step_num']            ? $param['step_num']            : 1;
        $this->device_third_id       = isset($param['cloud_device_id'])     && $param['cloud_device_id']     ? $param['cloud_device_id']     : '';
        $this->log_id       = isset($param['log_id'])     && $param['log_id']     ? $param['log_id']     : 0;
        return $param;
    }
    
    /************* 表device_bind_info ***************/
    /** @var int 绑定类型 */
    public $record_bind_type;
    /** @var int 绑定类型对应id */
    public $record_bind_id;
    /** @var int 步骤数 */
    public $step_num;
    /** @var array 记录额外参数 */
    public $line_func_txt_arr = [];
    /** @var string 同步状态 */
    public $syn_status           = '';
    /** @var string 同步状态描述 */
    public $syn_status_txt       = '';
    /** @var string 同步失败原因 */
    public $err_reason           = '';
    /** @var string 第二个绑定类型 */
    public $second_bind_type     = '';
    /** @var string 第二个绑定类型对应id */
    public $second_bind_id       = '';
    /** @var string 同步到三方的名称 */
    public $third_name           = '';
    /** @var string 三方对应返回的设备id */
    public $third_deviceId       = '';
    /** @var string 三方对应ID1 */
    public $third_bind_id        = '';
    /** @var string 三方对应ID2 */
    public $third_second_bind_id = '';
    /** @var string 三方对应ID3 */
    public $third_three_bind_id  = '';
    /** @var string 第三方相关码1 */
    public $third_code  = '';
    /** @var string 第三方相关码2 */
    public $third_second_code  = '';
    /************* 表device_bind_info ***************/

    public function clearRecordDeviceBindFilter() {
        $this->syn_status           = '';
        $this->syn_status_txt       = '';
        $this->err_reason           = '';
        $this->line_func_txt_arr    = '';
        $this->second_bind_type     = '';
        $this->second_bind_id       = '';
        $this->third_name           = '';
        $this->third_deviceId       = '';
        $this->third_bind_id        = '';
        $this->third_second_bind_id = '';
        $this->third_three_bind_id  = '';
        $this->third_code           = '';
        $this->third_second_code    = '';
        return true;
    }
    
    public function recordDeviceBindFilterBox($param) {
        try{
            // 记录下同步过程
            if (!isset($this->record_bind_type) || !$this->record_bind_type) {
                switch ($this->device_equipment_type) {
                    case DeviceConst::DEVICE_TYPE_FACE:
                        $this->record_bind_type = DeviceConst::BIND_FACE_DEVICE;
                        break;
                    case DeviceConst::DEVICE_TYPE_CAMERA:
                        $this->record_bind_type = DeviceConst::BIND_CAMERA_DEVICE;
                        break;
                    case DeviceConst::DEVICE_TYPE_ALARM:
                        $this->record_bind_type = DeviceConst::BIND_ALARM_DEVICE;
                        break;
                    case DeviceConst::DEVICE_TYPE_FINGERPRINT:
                        $this->record_bind_type = DeviceConst::BIND_FINGERPRINT_DEVICE;
                        break;
                }
            }
            if (isset($this->record_bind_type)) {
                if (!$this->record_bind_id) {
                    $this->record_bind_id      = $this->device_id;
                }
                $recordParam                   = $param;
                $recordParam['syn_status']     = $this->syn_status;
                $recordParam['syn_status_txt'] = $this->syn_status_txt;
                $recordParam['err_reason']     = $this->err_reason;
                $recordParam['line_func_txt']  = json_encode($this->line_func_txt_arr, JSON_UNESCAPED_UNICODE);
                $recordParam['step_num']       = $this->step_num;
                if ($this->second_bind_type && $this->second_bind_id) {
                    $recordParam['second_bind_type']     = $this->second_bind_type;
                    $recordParam['second_bind_id']       = $this->second_bind_id;
                }
                if ($this->third_name) {                 
                    $recordParam['third_name']           = $this->third_name;
                }                                        
                if ($this->third_deviceId) {             
                    $recordParam['third_deviceId']       = $this->third_deviceId;
                }                                        
                if ($this->third_bind_id) {              
                    $recordParam['third_bind_id']        = $this->third_bind_id;
                }
                if ($this->third_second_bind_id) {
                    $recordParam['third_second_bind_id'] = $this->third_second_bind_id;
                }
                if ($this->third_three_bind_id) {
                    $recordParam['third_three_bind_id']  = $this->third_three_bind_id;
                }
                if ($this->third_code) {
                    $recordParam['third_code']  = $this->third_code;
                }
                if ($this->third_second_code) {
                    $recordParam['third_second_code']  = $this->third_second_code;
                }
                $this->recordDeviceBindInfo($this->record_bind_type, $this->record_bind_id, $recordParam);
            }
        }catch (\Exception $e){
            fdump_api($e->getMessage(),'$msg',1);
        }
        if (isset($param['job_id'])) {
            unset($param['job_id']);
        }
        return $param;
    }


    /**
     * 处理设备相关信息 并返回
     * @param string $deviceType 设备类型 人来  face  监控 camera
     * @param int $device_id 对应设备id
     * @param string $device_sn 设备序列号
     * @param int $face_device_type 人脸设备类型
     * @param int $thirdProtocol 设备协议
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function handleDeviceParams($deviceType = 'face', $device_id = 0, $device_sn = '', $face_device_type = 0, $thirdProtocol = 0, $village_id = 0)
    {
        switch ($deviceType) {
            case DeviceConst::DEVICE_TYPE_CAMERA:
                if ($device_id || $device_sn) {
                    $whereCamera = [];
                    $whereCamera[] = ['camera_status', '<>', 4];
                    if ($device_sn) {
                        $whereCamera[] = ['camera_sn', '=', $device_sn];
                    } else {
                        $whereCamera[] = ['camera_id', '=', $device_id];
                    }
                    $cameraField = true;
                    $deviceInfo = (new HouseFaceDeviceService())->getCameraInfo($whereCamera, $cameraField);
                    if ($deviceInfo && !is_array($deviceInfo)) {
                        $deviceInfo = $deviceInfo->toArray();
                    }
                    if (!$thirdProtocol && isset($deviceInfo['thirdProtocol'])) {
                        // 未传设备协议以获取的设备协议为主
                        $thirdProtocol = $deviceInfo['thirdProtocol'];
                    }
                    if (!$device_id && isset($deviceInfo['camera_id'])) {
                        // 设备id 不存在 赋值
                        $device_id     = $deviceInfo['camera_id'];
                    } elseif (!$device_sn && isset($deviceInfo['camera_sn'])) {
                        // 设备序列号 不存在 赋值
                        $device_sn     = $deviceInfo['camera_sn'];
                    }
                    if (!$village_id && isset($deviceInfo['village_id'])) {
                        $village_id    = $deviceInfo['village_id'];
                    }
                }
                if (!$thirdProtocol) {
                    // 必须要有协议确没有传参  返回报错
                    $param = [
                        'deviceType'       => $deviceType,
                        'device_id'        => $device_id,
                        'device_sn'        => $device_sn,
                        'face_device_type' => $face_device_type,
                        'thirdProtocol'    => $thirdProtocol,
                        'village_id'       => $village_id,
                        'deviceInfo'       => isset($deviceInfo) ? $deviceInfo : '',
                    ];
                    return $this->backData($param, '协议信息不全', 1003);
                }
                break;
            case DeviceConst::DEVICE_TYPE_FINGERPRINT:
                if ($device_id || $device_sn) {
                    $whereFingerprint = [];
                    $whereFingerprint[] = ['delete_time', '=', 0];
                    if ($device_sn) {
                        $whereFingerprint[] = ['device_sn', '=', $device_sn];
                    } elseif ($device_id) {
                        $whereFingerprint[] = ['device_id', '=', $device_id];
                    }
                    $fingerprintField = true;
                    $deviceInfo = (new DeviceFingerprintService())->getFingerprintDeviceInfo($whereFingerprint, $fingerprintField);
                    if ($deviceInfo && !is_array($deviceInfo)) {
                        $deviceInfo = $deviceInfo->toArray();
                    }
                    if (!$thirdProtocol && isset($deviceInfo['third_protocol'])) {
                        // 未传设备协议以获取的设备协议为主
                        $thirdProtocol    = $deviceInfo['third_protocol'];
                    }
                    if (!$device_id && isset($deviceInfo['device_id'])) {
                        // 设备id 不存在 赋值
                        $device_id        = $deviceInfo['device_id'];
                    } elseif (!$device_sn && isset($deviceInfo['device_sn'])) {
                        // 设备序列号 不存在 赋值
                        $device_sn        = $deviceInfo['device_sn'];
                    }
                    if (!$village_id && isset($deviceInfo['village_id'])) {
                        $village_id       = $deviceInfo['village_id'];
                    }
                }
                break;
            case DeviceConst::DEVICE_TYPE_ALARM:
                if ($device_id || $device_sn) {
                    $whereDevice = [];
                    $whereDevice[] = ['is_del', '=', 0];
                    if ($device_sn) {
                        $whereDevice[] = ['device_serial', '=', $device_sn];
                    } else {
                        $whereDevice[] = ['device_id', '=', $device_id];
                    }
                    $alarmDeviceService = new AlarmDeviceService();
                    $deviceField = true;
                    $deviceInfo = $alarmDeviceService->getAlarmDevice($whereDevice, $deviceField);
                    if ($deviceInfo && !is_array($deviceInfo)) {
                        $deviceInfo = $deviceInfo->toArray();
                    }
                    $face_device_type = 0;
                    if (!$thirdProtocol && isset($deviceInfo['third_protocol'])) {
                        // 未传设备协议以获取的设备协议为主
                        $thirdProtocol = $deviceInfo['third_protocol'];
                    }
                    if (!$device_id && isset($deviceInfo['device_id'])) {
                        // 设备id 不存在 赋值
                        $device_id = $deviceInfo['device_id'];
                    } elseif (!$device_sn && isset($deviceInfo['device_serial'])) {
                        // 设备序列号 不存在 赋值
                        $device_sn = $deviceInfo['device_serial'];
                    }
                    if (!$village_id && isset($deviceInfo['village_id'])) {
                        $village_id = $deviceInfo['village_id'];
                    }
                }
                if (!$thirdProtocol) {
                    // 必须要有协议确没有传参  返回报错
                    $param = [
                        'deviceType'       => $deviceType,
                        'device_id'        => $device_id,
                        'device_sn'        => $device_sn,
                        'face_device_type' => $face_device_type,
                        'thirdProtocol'    => $thirdProtocol,
                        'village_id'       => $village_id,
                        'deviceInfo'       => isset($deviceInfo) ? $deviceInfo : '',
                    ];
                    return $this->backData($param, '协议信息不全', 1003);
                }
                break;
            case DeviceConst::DEVICE_TYPE_FACE:
            default:
                if ($device_id || $device_sn) {
                    $whereFace = [];
                    $whereFace[] = ['is_del', '=', 0];
                    if ($device_sn) {
                        $whereFace[] = ['device_sn', '=', $device_sn];
                    } elseif ($device_id) {
                        $whereFace[] = ['device_id', '=', $device_id];
                    }
                    $faceField = true;
                    $deviceInfo = (new HouseFaceDeviceService())->getHouseFaceDeviceInfo($whereFace, $faceField);
                    if ($deviceInfo && !is_array($deviceInfo)) {
                        $deviceInfo = $deviceInfo->toArray();
                    }
                    if (!$face_device_type && isset($deviceInfo['device_type'])) {
                        // 未传设备类型以获取的设备类型为主
                        $face_device_type = $deviceInfo['device_type'];
                    }
                    if (!$thirdProtocol && isset($deviceInfo['thirdProtocol'])) {
                        // 未传设备协议以获取的设备协议为主
                        $thirdProtocol    = $deviceInfo['thirdProtocol'];
                    }
                    if (!$device_id && isset($deviceInfo['device_id'])) {
                        // 设备id 不存在 赋值
                        $device_id        = $deviceInfo['device_id'];
                    } elseif (!$device_sn && isset($deviceInfo['device_sn'])) {
                        // 设备序列号 不存在 赋值
                        $device_sn        = $deviceInfo['device_sn'];
                    }
                    if (!$village_id && isset($deviceInfo['village_id'])) {
                        $village_id       = $deviceInfo['village_id'];
                    }
                }
                if (in_array($face_device_type, $this->protocolDeviceTraits) && !$thirdProtocol) {
                    // 必须要有协议确没有传参  返回报错
                    $param = [
                        'deviceType'       => $deviceType,
                        'device_id'        => $device_id,
                        'device_sn'        => $device_sn,
                        'face_device_type' => $face_device_type,
                        'thirdProtocol'    => $thirdProtocol,
                        'village_id'       => $village_id,
                        'deviceInfo'       => isset($deviceInfo) ? $deviceInfo : '',
                    ];
                    return $this->backData($param, '协议信息不全', 1003);
                }
                break;
        }
        if ($deviceType) {
            $this->device_equipment_type = $param['deviceType'] = $deviceType;
        }
        return [
            'deviceType'       => $deviceType,
            'device_id'        => $device_id,
            'device_sn'        => $device_sn,
            'face_device_type' => $face_device_type,
            'thirdProtocol'    => $thirdProtocol,
            'village_id'       => $village_id,
            'deviceInfo'       => isset($deviceInfo) ? $deviceInfo : '',
        ];
    }

    /**
     * 设备相关2级处理
     * @param $param
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function handleParamDeviceInfo($param) {
        // todo 如果不进行后续楼栋 同步只是单纯同步小区 可以不传设备id和设备序号
        $device = $this->handleDeviceParams($this->device_equipment_type, $this->device_id, $this->device_sn, $this->face_device_type, $this->thirdProtocol, $this->village_id);
        if (isset($device['code']) && $device['code']) {
            return $device;
        }
        if (isset($device['deviceType']) && $device['deviceType']) {
            $this->device_equipment_type = $param['deviceType'] = $device['deviceType'];
        }
        if (isset($device['thirdProtocol']) && $device['thirdProtocol']) {
            $this->thirdProtocol         = $param['thirdProtocol'] = $device['thirdProtocol'];
        }
        if (isset($device['device_id']) && $device['device_id']) {
            $this->device_id             = $param['device_id'] = $device['device_id'];
        }
        if (isset($device['device_sn']) && $device['device_sn']) {
            $this->device_sn             = $param['device_sn'] = $device['device_sn'];
        }
        if (isset($device['face_device_type']) && $device['face_device_type']) {
            $this->face_device_type      = $param['device_type'] = $device['face_device_type'];
        }
        if (isset($device['village_id']) && $device['village_id']) {
            $this->village_id            = $param['village_id'] = $device['village_id'];
        }
        if (isset($device['deviceInfo']) && $device['deviceInfo']) {
            $deviceInfo = $device['deviceInfo'];
        } else {
            $deviceInfo = [];
        }
        return [
            'param'      => $param,
            'deviceInfo' => $deviceInfo,
        ];
    }



    /**
     * 获取三方关联信息
     * @param string $bind_type
     * @param string $bind_id
     * @param string $thirdProtocol
     * @param string $third_id
     * @return array|\think\Model|null
     */
    protected function commonFilterAboutToData(string $bind_type,string $bind_id = '',string $thirdProtocol = '',string $third_id = '') {
        $dbFaceBindAboutInfo = new FaceBindAboutInfo();
        $whereAbout = [];
        if (!$thirdProtocol) {
            $thirdProtocol = $this->thirdProtocol;
        }
        $whereAbout[] = ['bind_type', '=', $bind_type];
        $whereAbout[] = ['third_protocol', '=', $thirdProtocol];
        if ($bind_id) {
            $whereAbout[] = ['bind_id',   '=', $bind_id];
            $whereAbout[] = ['third_id',  '<>', ''];
        } else {
            $whereAbout[] = ['third_id',   '=', $third_id];
            $whereAbout[] = ['bind_id',  '<>', ''];
        }
        $aboutInfo = $dbFaceBindAboutInfo->getOne($whereAbout, true, 'id DESC');
        if ($aboutInfo && !is_array($aboutInfo)) {
            $aboutInfo = $aboutInfo->toArray();
        } elseif (!$aboutInfo) {
            $aboutInfo = [];
        }
        if (isset($aboutInfo['bind_id'])) {
            switch ($aboutInfo['bind_type']) {
                case DeviceConst::DH_TO_CLOUD_BUILD:
                case DeviceConst::HK_TO_CLOUD_SINGLE:
                    $houseVillageSignService = new HouseVillageSingleService();
                    $whereSingle = [
                        'id'     => $aboutInfo['bind_id'],
                        'status' => 1
                    ];
                    $bindInfo = $houseVillageSignService->getSingleInfo($whereSingle, 'id');
                    if ($bindInfo && !is_array($bindInfo)) {
                        $bindInfo = $bindInfo->toArray();
                    }
                    if (empty($bindInfo) || !isset($bindInfo['id'])) {
                        $aboutInfo = [];
                    }
                    break;
                case DeviceConst::DH_TO_CLOUD_UNIT:
                case DeviceConst::HK_TO_CLOUD_FLOOR:
                    $houseVillageSignService = new HouseVillageSingleService();
                    $whereFloor = [
                        'floor_id' => $aboutInfo['bind_id'],
                        'status'   => 1
                    ];
                    $bindInfo = $houseVillageSignService->getFloorInfo($whereFloor, 'floor_id');
                    if ($bindInfo && !is_array($bindInfo)) {
                        $bindInfo = $bindInfo->toArray();
                    }
                    if (empty($bindInfo) || !isset($bindInfo['floor_id'])) {
                        $aboutInfo = [];
                    }
                    break;
                case DeviceConst::DH_TO_CLOUD_ROOM:
                case DeviceConst::HK_TO_CLOUD_ROOM:
                    $houseVillageSignService = new HouseVillageSingleService();
                    $field = 'pigcms_id';
                    $whereRoom = [
                        'pigcms_id' => $aboutInfo['bind_id'],
                        'is_del'   => 0
                    ];
                    $bindInfo = $houseVillageSignService->getOneRoom($whereRoom, $field);
                    if ($bindInfo && !is_array($bindInfo)) {
                        $bindInfo = $bindInfo->toArray();
                    }
                    if (empty($bindInfo) || !isset($bindInfo['pigcms_id'])) {
                        $aboutInfo = [];
                    }
                    break;
            }
        }
        return $aboutInfo;
    }



    /**
     * 保存相关信息
     * @param string $bind_type
     * @param string $bind_id
     * @param array $param 传参 具体接参看方法中
     * @return bool
     */
    protected function commonSaveAboutInfo(string $bind_type,string $bind_id, array $param = []) {
        $third_protocol = isset($param['third_protocol'])  ? $param['third_protocol'] : $this->thirdProtocol;
        $aboutInfo = $this->commonFilterAboutToData($bind_type, $bind_id, $third_protocol);
        $dbFaceBindAboutInfo = new FaceBindAboutInfo();
        $nowTime        = $this->nowTime                   ? $this->nowTime           : time();
        $bind_name      = isset($param['bind_name'])       ? $param['bind_name']      : '';
        $third_id       = isset($param['third_id'])        ? $param['third_id']       : '';
        $third_info     = isset($param['third_info'])      ? $param['third_info']     : '';
        $device_type    = isset($param['device_type'])     ? $param['device_type']    : $this->face_device_type;
        $device_id      = isset($param['device_id'])       ? $param['device_id']      : $this->device_id;
        $group_id       = isset($param['group_id'])        ? $param['group_id']       : '';
        $saveParam = [
            'bind_type'   => $bind_type,
            'bind_id'     => $bind_id,
            'device_type' => $device_type,
        ];
        if ($bind_name) {
            $saveParam['bind_name']      = $bind_name;
        }
        if ($third_id) {
            $saveParam['third_id']       = $third_id;
        }
        if ($third_info) {
            $saveParam['third_info']     = $third_info;
        }
        if ($device_id) {
            $saveParam['device_id']      = $device_id;
        }
        if ($group_id) {
            $saveParam['group_id']       = $group_id;
        }
        if ($third_protocol) {
            $saveParam['third_protocol'] = $third_protocol;
        }
        if ($aboutInfo && isset($aboutInfo['id'])) {
            $saveParam['last_time']        = $nowTime;
            $whereUpdate = [];
            $whereUpdate[] = ['id', '=', $aboutInfo['id']];
            $dbFaceBindAboutInfo->updateThis($whereUpdate, $saveParam);
        } else {
            $saveParam['add_time']   = $nowTime;
            $dbFaceBindAboutInfo->add($saveParam);
        }
        return true;
    }

    /** @var object 设备相关同步 进展和状态和错误 表初始化  */
    protected $dbDeviceBindInfo;
    /** @var int 同步记录的时间限制 超出限制的 在下一次相同命令来的时候进行删除 默认超过3天进行删除 */
    protected $recordDeviceBindLimitTime = 259200;

    /**
     * 设备相关同步 进展和状态和错误 里面的盒子
     * @param string $bind_type
     * @param int $bind_id
     * @param array $param
     * @return array|false
     * @throws \Exception
     */
    private function recordDeviceBindInfo(string $bind_type,int $bind_id, array $param = []) {
        if (!$bind_type || !$bind_id) {
            return false;
        }
        if (!$this->thirdProtocol) {
            $param = $this->filterCommonParam($param);
            if (!$this->thirdProtocol) {
                return false;
            }
        }
        // todo 添加编辑成功记录下来
        if (!$this->dbDeviceBindInfo) {
            $this->dbDeviceBindInfo = new DeviceBindInfo();
        }
        $whereDeviceBind = [];
        $whereDeviceBind[] = ['bind_type', '=', $bind_type];
        $whereDeviceBind[] = ['bind_id', '=', $bind_id];
        $whereDeviceBind[] = ['third_protocol', '=', $this->thirdProtocol];
        if ($this->village_id) {
            $whereDeviceBind[] = ['village_id', '=', $this->village_id];
        } elseif (isset($param['village_id']) && $param['village_id']) {
            $whereDeviceBind[] = ['village_id', '=', $param['village_id']];
        }
        if ($this->device_sn) {
            $whereDeviceBind[] = ['device_sn', '=', $this->device_sn];
        } elseif (isset($param['device_sn']) && $param['device_sn']) {
            $whereDeviceBind[] = ['device_sn', '=', $param['device_sn']];
        }
        $limitWhere = $whereDeviceBind;
        $limit = false;
        if ($this->order_group_id) {
            $limit = true;
            $whereDeviceBind[] = ['order_group_id', '=', $this->order_group_id];
            $limitWhere[] = ['order_group_id', '<>', $this->order_group_id];
        } elseif (isset($param['orderGroupId']) && $param['orderGroupId']) {
            $limit = true;
            $whereDeviceBind[] = ['order_group_id', '=', $param['orderGroupId']];
            $limitWhere[] = ['order_group_id', '<>', $param['orderGroupId']];
        }
        $nowTime = $this->nowTime ? $this->nowTime : time();
        $bindInfo = $this->dbDeviceBindInfo->getOne($whereDeviceBind, true);
        $bindData = [
            'bind_type'      => $bind_type,
            'bind_id'        => $bind_id,
            'third_protocol' => $this->thirdProtocol,
            'add_time'       => $nowTime,
        ];
        if (isset($param['job_id'])) {
            $bindData['job_id']                = $param['job_id'];
        }
        if (isset($param['third_name']) && $param['third_name']) {
            $bindData['third_name']            = $param['third_name'];
        }
        if (isset($param['third_deviceId']) && $param['third_deviceId']) {
            $bindData['third_deviceId']        = $param['third_deviceId'];
        }
        if (isset($param['third_bind_id']) && $param['third_bind_id']) {
            $bindData['third_bind_id']         = $param['third_bind_id'];
        }
        if (isset($param['third_second_bind_id']) && $param['third_second_bind_id']) {
            $bindData['third_second_bind_id']  = $param['third_second_bind_id'];
        }
        if (isset($param['third_three_bind_id']) && $param['third_three_bind_id']) {
            $bindData['third_three_bind_id']   = $param['third_three_bind_id'];
        }
        if (isset($param['second_bind_type']) && $param['second_bind_type']) {
            $bindData['second_bind_type']      = $param['second_bind_type'];
        }
        if (isset($param['second_bind_id']) && $param['second_bind_id']) {
            $bindData['second_bind_id']        = $param['second_bind_id'];
        }
        if (isset($param['third_code']) && $param['third_code']) {
            $bindData['third_code']            = $param['third_code'];
        }
        if (isset($param['third_second_code']) && $param['third_second_code']) {
            $bindData['third_second_code']     = $param['third_second_code'];
        }
        if (isset($param['syn_status']) && $param['syn_status']) {
            $bindData['syn_status']            = $param['syn_status'];
        }
        if (isset($param['syn_status_txt']) && $param['syn_status_txt']) {
            $bindData['syn_status_txt']        = $param['syn_status_txt'];
        }
        if (isset($param['syn_reason']) && $param['syn_reason']) {
            $bindData['syn_reason']            = $param['syn_reason'];
        }
        if (isset($param['err_reason']) && $param['err_reason']) {
            $bindData['err_reason']            = $param['err_reason'];
            if (!isset($param['err_hint']) || $param['err_hint']) {
                $param['err_hint'] = $bindData['err_hint'] = md5($bindData['err_reason'] . 'errhint'.uniqid().$nowTime);
            }
        }
        if (isset($param['line_func_txt']) && $param['line_func_txt']) {
            $bindData['line_func_txt']         = $param['line_func_txt'];
        }
        if (isset($param['step_num']) && $param['step_num']) {
            $bindData['step_num']              = $param['step_num'];
        }

        if (!$bindInfo || !isset($bindInfo['id'])) {
            if ($this->village_id) {
                $bindData['village_id'] = $this->village_id;
            } elseif (isset($param['village_id']) && $param['village_id']) {
                $bindData['village_id'] = $param['village_id'];
            }
            if ($this->device_sn) {
                $bindData['device_sn']             = $this->device_sn;
            } elseif (isset($param['device_sn']) && $param['device_sn']) {
                $bindData['device_sn']             = $param['device_sn'];
            }
            if ($this->order_group_id) {
                $bindData['order_group_id']        = $this->order_group_id;
            } elseif (isset($param['orderGroupId']) && $param['orderGroupId']) {
                $bindData['order_group_id']        = $param['orderGroupId'];
            }
            if ($this->order_group_type) {
                $bindData['order_group_type']      = $this->order_group_type;
            } elseif (isset($param['orderGroupType']) && $param['orderGroupType']) {
                $bindData['order_group_type']      = $param['orderGroupType'];
            }
            $bindData['add_time'] = $nowTime;
            $this->dbDeviceBindInfo->add($bindData);
            // 记录步骤
            $bindData['bind_type'] = DeviceConst::BIND_DEVICE_STEP;
            $this->dbDeviceBindInfo->add($bindData);
            if ($limit && $limitWhere) {
                // 超出时间限制的 清空2条添加和更新最靠前的 同批次执行命令
                $limitTime = $nowTime - $this->recordDeviceBindLimitTime;
                $limitAddTimeWhere = $limitWhere;
                $limitAddTimeWhere[] = ['add_time','<',$limitTime];
                $limitAddTimeWhere[] = ['order_group_id','<>',''];
                $limitOne = $this->dbDeviceBindInfo->getOne($limitAddTimeWhere, 'id, order_group_id','id ASC');
                
                if (isset($limitOne['order_group_id'])) {
                    $this->dbDeviceBindInfo->deleteInfo(['order_group_id' => $limitOne['order_group_id']]);
                }
                $limitWhere[] = ['update_time','>',0];
                $limitWhere[] = ['update_time','<',$limitTime];
                $limitWhere[] = ['order_group_id','<>',''];
                $limitOne = $this->dbDeviceBindInfo->getOne($limitWhere, 'id, order_group_id','id ASC');
                
                if (isset($limitOne['order_group_id'])) {
                    $this->dbDeviceBindInfo->deleteInfo(['order_group_id' => $limitOne['order_group_id']]);
                }
            }
        } else {
            if (!isset($param['err_reason']) || !$param['err_reason']) {
                $bindData['err_reason']            = '';
                $bindData['err_hint']              = '';
                $param['err_hint']                 = '';
            }
            $bindDataUpdate = $bindData;
            $bindDataUpdate['update_time'] = $nowTime;
            unset($bindDataUpdate['add_time']);
            $this->dbDeviceBindInfo->updateThis($whereDeviceBind, $bindDataUpdate);
            if (!is_array($bindInfo)) {
                $bindInfo = $bindInfo->toArray();
            }
            unset($bindInfo['id'],$bindInfo['add_time'],$bindInfo['update_time'],$bindInfo['delete_time']);
            // 可以继承的字段 其他的需要使用当前传参
            $stepKeyField = ['bind_id', 'village_id', 'device_sn', 'third_protocol', 'order_group_type', 'order_group_id'];
            foreach ($bindInfo as $key=>$item) {
                if (!isset($bindData[$key]) && in_array($key, $stepKeyField)) {
                    $bindData[$key] = $item;
                }
            }
            // 记录步骤
            $bindData['bind_type'] = DeviceConst::BIND_DEVICE_STEP;
            unset($bindData['update_time']);
            $this->dbDeviceBindInfo->add($bindData);
        }
        return $param;
    }

    /**
     * 查询设备信息 人脸和监控
     * @return array|mixed|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDeviceInfo() {
        $deviceInfo = [];
        switch ($this->device_equipment_type) {
            case DeviceConst::DEVICE_TYPE_FACE:
                $whereFace = [];
                $whereFace[] = ['thirdProtocol','>',0];
                $whereFace[] = ['is_del','=',0];
                if ($this->device_id) {
                    $whereFace[] = ['device_id', '=', $this->device_id];
                } else {
                    $whereFace[] = ['device_sn', '=', $this->device_sn];
                }
                $deviceInfo = (new HouseFaceDeviceService())->getHouseFaceDeviceInfo($whereFace, true);
                if ($deviceInfo && !is_array($deviceInfo)) {
                    $deviceInfo = $deviceInfo->toArray();
                }
                if (isset($deviceInfo['cloud_device_id']) && $deviceInfo['cloud_device_id']) {
                    $this->device_third_id = $deviceInfo['cloud_device_id'];
                }
                if (!$this->device_id && isset($deviceInfo['device_id']) && $deviceInfo['device_id']) {
                    $this->device_id = $deviceInfo['device_id'];
                }
                if (!$this->device_sn && isset($deviceInfo['device_sn']) && $deviceInfo['device_sn']) {
                    $this->device_sn = $deviceInfo['device_sn'];
                }
                break;
            case DeviceConst::DEVICE_TYPE_ALARM:
                $whereDevice = [];
                $whereDevice[] = ['third_protocol','>',0];
                $whereDevice[] = ['is_del','=',0];
                if ($this->device_id) {
                    $whereDevice[] = ['device_id', '=', $this->device_id];
                } else {
                    $whereDevice[] = ['device_serial', '=', $this->device_sn];
                }
                $alarmDeviceService = new AlarmDeviceService();
                $deviceField = true;
                $deviceInfo = $alarmDeviceService->getAlarmDevice($whereDevice, $deviceField);
                if ($deviceInfo && !is_array($deviceInfo)) {
                    $deviceInfo = $deviceInfo->toArray();
                }
                if (isset($deviceInfo['cloud_device_id']) && $deviceInfo['cloud_device_id']) {
                    $this->device_third_id = $deviceInfo['cloud_device_id'];
                }
                if (!$this->device_id && isset($deviceInfo['device_id']) && $deviceInfo['device_id']) {
                    $this->device_id = $deviceInfo['device_id'];
                }
                if (!$this->device_sn && isset($deviceInfo['device_serial']) && $deviceInfo['device_serial']) {
                    $this->device_sn = $deviceInfo['device_serial'];
                }
                break;
            case DeviceConst::DEVICE_TYPE_CAMERA:
                $whereCamera = [];
                $whereCamera[] = ['thirdProtocol','>',0];
                $whereCamera[] = ['camera_status','<>',4];
                if ($this->device_id) {
                    $whereCamera[] = ['camera_id', '=', $this->device_id];
                } elseif ($this->device_sn) {
                    $whereCamera[] = ['camera_sn', '=', $this->device_sn];
                }
                $deviceInfo = (new HouseFaceDeviceService())->getCameraInfo($whereCamera, true);
                if ($deviceInfo && !is_array($deviceInfo)) {
                    $deviceInfo = $deviceInfo->toArray();
                }
                if (isset($deviceInfo['cloud_device_id']) && $deviceInfo['cloud_device_id']) {
                    $this->device_third_id = $deviceInfo['cloud_device_id'];
                }
                if (!$this->device_id && isset($deviceInfo['camera_id']) && $deviceInfo['camera_id']) {
                    $this->device_id = $deviceInfo['camera_id'];
                }
                if (!$this->device_sn && isset($deviceInfo['camera_sn']) && $deviceInfo['camera_sn']) {
                    $this->device_sn = $deviceInfo['camera_sn'];
                }
                break;
            case DeviceConst::DEVICE_TYPE_FINGERPRINT:
                $whereFingerprint = [];
                $whereFingerprint[] = ['delete_time', '=', 0];
                $whereFingerprint[] = ['device_status', '<>', 4];
                if ($this->device_sn) {
                    $whereFingerprint[] = ['device_sn', '=', $this->device_sn];
                } elseif ($this->device_id) {
                    $whereFingerprint[] = ['device_id', '=', $this->device_id];
                }
                $fingerprintField = true;
                $deviceInfo = (new DeviceFingerprintService())->getFingerprintDeviceInfo($whereFingerprint, $fingerprintField);
                if ($deviceInfo && !is_array($deviceInfo)) {
                    $deviceInfo = $deviceInfo->toArray();
                }
                if (isset($deviceInfo['cloud_device_id']) && $deviceInfo['cloud_device_id']) {
                    $this->device_third_id = $deviceInfo['cloud_device_id'];
                }
                if (!$this->device_id && isset($deviceInfo['device_id']) && $deviceInfo['device_id']) {
                    $this->device_id = $deviceInfo['device_id'];
                }
                if (!$this->device_sn && isset($deviceInfo['device_sn']) && $deviceInfo['device_sn']) {
                    $this->device_sn = $deviceInfo['device_sn'];
                }
        }
        return $deviceInfo;
    }

    /**
     * 获取三方设备信息和可以更新数据
     * @return array
     */
    public function getDeviceThirdInfo($param) {
        $deviceThirdInfo = [];
        $updateParam = [];
        $device_status_key = '';
        $device_status_judge_online  = 1;
        $device_status_judge_offline = 0;
        $device_status_judge_update  = 3;
        $device_ip_key               = 'device_ip';
        $device_model_key            = 'device_model';
        if (!$this->device_equipment_type && isset($param['deviceType']) && trim($param['deviceType'])) {
            $this->device_equipment_type = trim($param['deviceType']);
        }
        if (!$this->thirdProtocol && isset($param['thirdProtocol']) && intval($param['thirdProtocol'])) {
            $this->thirdProtocol = intval($param['thirdProtocol']);
        }
        switch ($this->device_equipment_type) {
            case DeviceConst::DEVICE_TYPE_FACE:
                // 2-离线，1-在线，3-升级中
                $device_status_key           = 'device_status';
                $device_ip_key               = 'device_ip';
                $device_model_key            = 'cloud_device_model';
                $device_status_judge_online  = 1;
                $device_status_judge_offline = 2;
                $device_status_judge_update  = 3;
                break;
            case DeviceConst::DEVICE_TYPE_ALARM:
                // 2-离线，1-在线，3-升级中
                $device_status_key           = 'device_status';
                $device_ip_key               = 'device_ip';
                $device_model_key            = 'device_model';
                $device_status_judge_online  = 1;
                $device_status_judge_offline = 2;
                $device_status_judge_update  = 3;
                break;
            case DeviceConst::DEVICE_TYPE_CAMERA:
                // 0-在线，1-离线，3-升级中
                $device_status_key           = 'camera_status';
                $device_ip_key               = 'device_ip';
                $device_model_key            = 'device_model';
                $device_status_judge_online  = 0;
                $device_status_judge_offline = 1;
                $device_status_judge_update  = 3;
                break;
            case DeviceConst::DEVICE_TYPE_FINGERPRINT:
                // 0 待同步（需要同步云的） 1正常（已同步） 2同步失败 3离线 4删除
                $device_status_key           = 'device_status';
                $device_ip_key               = 'device_ip';
                $device_model_key            = 'device_model';
                $device_status_judge_online  = 1;
                $device_status_judge_offline = 3;
                $device_status_judge_update  = 5;
                break;
        }
        $deviceInfo = isset($param['deviceInfo']) ? $param['deviceInfo'] : [];
        switch ($this->thirdProtocol) {
            case DeviceConst::HIK_DEVICE_GATEWAY:
            case DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM:
                $device_sn = isset($param['device_sn']) && trim($param['device_sn']) ? trim($param['device_sn']) : '';
                $deviceThirdInfo = (new HikGateWayDeviceService())->getGateWayDevice($device_sn, [], $param);
                break;
            case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                if (isset($param['cloud_device_id']) && $param['cloud_device_id']) {
                    $cloud_device_id = $param['cloud_device_id'];
                } elseif ($this->device_third_id) {
                    $cloud_device_id = $this->device_third_id;
                } elseif (isset($deviceInfo['cloud_device_id'])) {
                    $cloud_device_id = $deviceInfo['cloud_device_id'];
                }
                if (isset($cloud_device_id)) {
                    $deviceThirdInfo = (new FaceHikCloudNeiBuDeviceService())->getDeviceDetail($cloud_device_id);
                    if (isset($deviceThirdInfo['data']['deviceStatus'])) {
                        $deviceStatus = $deviceThirdInfo['data']['deviceStatus'];
                        if ($deviceStatus == 1) {
                            $updateParam[$device_status_key] = $device_status_judge_online;
                            $deviceThirdInfo['online']     = 1;
                            $deviceThirdInfo['online_txt'] = '在线';
                        } else {
                            $updateParam[$device_status_key] = $device_status_judge_offline;
                            $deviceThirdInfo['online']     = 0;
                            $deviceThirdInfo['online_txt'] = '离线';
                        }
                    }
                    if (isset($deviceThirdInfo['data']['deviceIp'])) {
                        $deviceIp = $deviceThirdInfo['data']['deviceIp'];
                        if (trim($deviceIp)) {
                            $updateParam[$device_ip_key] = trim($deviceIp);
                        }
                    }
                    if (isset($deviceThirdInfo['data']['deviceModel'])) {
                        $deviceModel = $deviceThirdInfo['data']['deviceModel'];
                        if (trim($deviceModel)) {
                            $updateParam[$device_model_key] = trim($deviceModel);
                        }
                    }
                    if ($this->device_equipment_type == DeviceConst::DEVICE_TYPE_ALARM) {
                        if (isset($deviceThirdInfo['data']['communityId'])) {
                            $communityId = $deviceThirdInfo['data']['communityId'];
                            if (trim($communityId)) {
                                $updateParam['cloud_community_id'] = trim($communityId);
                            }
                        }
                        if (isset($deviceThirdInfo['data']['buildingId'])) {
                            $buildingId = $deviceThirdInfo['data']['buildingId'];
                            if (trim($buildingId)) {
                                $updateParam['cloud_building_id'] = trim($buildingId);
                            }
                        }
                        if (isset($deviceThirdInfo['data']['unitId'])) {
                            $unitId = $deviceThirdInfo['data']['unitId'];
                            if (trim($unitId)) {
                                $updateParam['cloud_unit_id'] = trim($unitId);
                            }
                        }
                    }
                }
                break;
            case DeviceConst::DH_YUNRUI:
                if ($this->device_sn) {
                    $deviceId = $this->device_sn;
                } elseif (isset($deviceInfo['device_sn'])) {
                    $deviceId = $deviceInfo['device_sn'];
                }
                if (isset($deviceId)) {
                    $deviceThirdInfo = (new FaceDHYunRuiCloudDeviceService())->getDeviceInfo($deviceId);
                    if ($deviceThirdInfo && isset($deviceThirdInfo['data']['status'])) {
                        // 当前设备状态：0-离线，1-在线，3-升级中
                        $status = $deviceThirdInfo['data']['status'];
                        if ($status == 1) {
                            $updateParam[$device_status_key] = $device_status_judge_online;
                            $deviceThirdInfo['online']     = 1;
                            $deviceThirdInfo['online_txt'] = '在线';
                        } elseif ($status ==3) {
                            $updateParam[$device_status_key] = $device_status_judge_update;
                            $deviceThirdInfo['online']     = 0;
                            $deviceThirdInfo['online_txt'] = '升级中';
                        } else {
                            $updateParam[$device_status_key] = $device_status_judge_offline;
                            $deviceThirdInfo['online']     = 0;
                            $deviceThirdInfo['online_txt'] = '离线';
                        }
                    }
                    if ($deviceThirdInfo && isset($deviceThirdInfo['data']['deviceModel'])) {
                        $deviceModel = $deviceThirdInfo['data']['deviceModel'];
                        if (trim($deviceModel)) {
                            $updateParam[$device_model_key] = trim($deviceModel);
                        }
                    }
                }
                break;
        }
        
        return [
            'deviceThirdInfo' => $deviceThirdInfo,
            'updateParam'     => $updateParam,
        ];
    }
    

    /**
     * 更新设备信息
     * @param $updateParam
     * @param int $nowTime
     * @return bool
     */
    public function updateDeviceInfo($updateParam) {
        $nowTime = $this->nowTime ? $this->nowTime : time();
        if (!empty($updateParam)) {
            switch ($this->device_equipment_type) {
                case DeviceConst::DEVICE_TYPE_FACE:
                    $whereFace = [];
                    $whereFace[] = ['thirdProtocol','>',0];
                    $whereFace[] = ['is_del','=',0];
                    if ($this->device_id) {
                        $whereFace[] = ['device_id', '=', $this->device_id];
                    } else {
                        $whereFace[] = ['device_sn', '=', $this->device_sn];
                    }
                    // 如果有需要更新的更新数据
                    $updateParam['last_time']   = $nowTime;
                    $updateParam['notify_time'] = $nowTime;
                    $dbHouseFaceDevice = new HouseFaceDevice();
                    $dbHouseFaceDevice->saveData($whereFace, $updateParam);
                    break;
                case DeviceConst::DEVICE_TYPE_ALARM:
                    $whereDevice = [];
                    $whereDevice[] = ['third_protocol','>',0];
                    $whereDevice[] = ['is_del','=',0];
                    if ($this->device_id) {
                        $whereDevice[] = ['device_id', '=', $this->device_id];
                    } else {
                        $whereDevice[] = ['device_serial', '=', $this->device_sn];
                    }
                    $alarmDeviceService = new AlarmDeviceService();
                    // 如果有需要更新的更新数据
                    $updateParam['update_time']   = $nowTime;
                    $updateParam['interaction_time'] = $nowTime;
                    $alarmDeviceService->updateAlarmDevice($whereDevice, $updateParam);
                    break;
                case DeviceConst::DEVICE_TYPE_CAMERA:
                    $whereCamera = [];
                    $whereCamera[] = ['thirdProtocol','>',0];
                    $whereCamera[] = ['camera_status','<>',4];
                    if ($this->device_id) {
                        $whereCamera[] = ['camera_id', '=', $this->device_id];
                    } elseif ($this->device_sn) {
                        $whereCamera[] = ['camera_sn', '=', $this->device_sn];
                    }
                    // 如果有需要更新的更新数据
                    $updateParam['last_time']   = $nowTime;
                    $updateParam['login_time']  = $nowTime;
                    (new HouseFaceDeviceService())->saveCamera($whereCamera, $updateParam);
                    break;
                case DeviceConst::DEVICE_TYPE_FINGERPRINT:
                    $whereDevice = [];
                    $whereDevice[] = ['third_protocol','>',0];
                    $whereDevice[] = ['device_status','<>',4];
                    $whereDevice[] = ['delete_time','=',4];
                    if ($this->device_id) {
                        $whereDevice[] = ['device_id', '=', $this->device_id];
                    } elseif ($this->device_sn) {
                        $whereDevice[] = ['device_sn', '=', $this->device_sn];
                    }
                    // 如果有需要更新的更新数据
                    $updateParam['update_time']   = $nowTime;
                    (new HouseFaceDeviceService())->saveCamera($whereDevice, $updateParam);
                    break;
            }
        }
        return true;
    }
    
    public function commonDeviceSysStepInfo(array $param) {
        $param           = $this->filterCommonParam($param);
        $deviceInfo      = $this->getDeviceInfo();
        if (!isset($this->record_bind_type) || !$this->record_bind_type) {
            switch ($this->device_equipment_type) {
                case DeviceConst::DEVICE_TYPE_FACE:
                    $this->record_bind_type = DeviceConst::BIND_FACE_DEVICE;
                    break;
                case DeviceConst::DEVICE_TYPE_CAMERA:
                    $this->record_bind_type = DeviceConst::BIND_CAMERA_DEVICE;
                    break;
            }
        }
        if (!$this->dbDeviceBindInfo) {
            $this->dbDeviceBindInfo = new DeviceBindInfo();
        }

        $whereDeviceBind = [];
        $whereDeviceBind[] = ['bind_type', '=', $this->record_bind_type];
        $whereDeviceBind[] = ['bind_id', '=', $this->device_id];
        $whereDeviceBind[] = ['third_protocol', '=', $this->thirdProtocol];
        if ($this->village_id) {
            $whereDeviceBind[] = ['village_id', '=', $this->village_id];
        } elseif (isset($param['village_id']) && $param['village_id']) {
            $whereDeviceBind[] = ['village_id', '=', $param['village_id']];
        }
        if ($this->device_sn) {
            $whereDeviceBind[] = ['device_sn', '=', $this->device_sn];
        } elseif (isset($param['device_sn']) && $param['device_sn']) {
            $whereDeviceBind[] = ['device_sn', '=', $param['device_sn']];
        }
        $nowTime = $this->nowTime ? $this->nowTime : time();
        $bindInfo = $this->dbDeviceBindInfo->getOne($whereDeviceBind, true);
        
        
        return $bindInfo;
    }

    /**
     * 整合后 小区同步添加/更新到设备云 直接传参进行同步 不做2次查询确认 所以外部需要优先确认是添加还是编辑
     * @param array $commonParam
     * @return array
     */
    protected function commonVillageToDeviceCloud(array $commonParam)
    {
        /** @var int face_bind_type 人脸设备类型 */
        $face_device_type = isset($commonParam['device_type']) ? $commonParam['device_type'] : 0;
        /** @var int nowTime 当前时间 */
        $nowTime = $this->nowTime ? $this->nowTime : time();
        /** @var int bind_type 绑定类型 */
        $bind_type = isset($commonParam['bind_type']) ? $commonParam['bind_type'] : 0;
        $bind_id = isset($commonParam['bind_id']) ? $commonParam['bind_id'] : 0;
        $thirdProtocol = isset($commonParam['thirdProtocol']) ? $commonParam['thirdProtocol'] : 0;
        /** @var string village_id 小区id */
        $village_id = isset($commonParam['village_id']) ? $commonParam['village_id'] : 0;
        /** 同步的父级id或者编号  */
        $parent_third_id = isset($commonParam['parent_third_id']) && $commonParam['parent_third_id'] ? $commonParam['parent_third_id'] : '';
        // ↓ 传下面参数$third_id 会对对应业务三方数据进行更新
        /** @var string third_id 传下面参数bind_number 会对对应业务三方数据进行更新 */
        $third_id = isset($commonParam['third_id']) ? $commonParam['third_id'] : '';
        // ↓ 传下面参数$bind_number 会对对应业务三方数据进行更新
        /** @var string bind_number 传下面参数bind_number 会对对应业务三方数据进行更新 */
        $bind_number = isset($commonParam['bind_number']) ? $commonParam['bind_number'] : '';
        /** @var int deviceType 区分人脸和监控设备类型 */
        $deviceType = isset($param['deviceType']) && $param['deviceType'] ? $param['deviceType'] : 0;

        /** @var int villageInfo 小区信息 */
        $villageInfo = isset($commonParam['villageInfo']) ? $commonParam['villageInfo'] : [];
        /** @var int param 额外参数 */
        $param = isset($commonParam['param']) ? $commonParam['param'] : [];
        /** @var int aboutInfo 待处理同步信息本地绑定信息 */
        $aboutInfo = isset($commonParam['aboutInfo']) ? $commonParam['aboutInfo'] : [];
        $dbFaceBindAboutInfo = new FaceBindAboutInfo();
        $errMsg = '';
        $code = 0;
        $thirdData = [];
        $whereAbout = [];
        $whereAbout['bind_type'] = $bind_type;
        $whereAbout['bind_id'] = $bind_id;
        $whereAbout['third_protocol'] = $thirdProtocol;
        switch ($thirdProtocol) {
            case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                $errMsg = '同步小区失败';
                $code = 1004;
                if ($third_id) {
                    $param['communityId'] = $third_id;
                }
                $thirdData = (new FaceHikCloudNeiBuDeviceService())->villageToDeviceCloud($village_id, $villageInfo, $param);
                if (isset($thirdData['message']) && $thirdData['message']) {
                    $thirdMsg = $thirdData['message'];
                }
                break;
            case DeviceConst::DH_YUNRUI:
                $errMsg = '同步小区失败';
                $code = 1004;
                if ($bind_number) {
                    $param['storeId'] = $bind_number;
                }
                if ($parent_third_id) {
                    $param['orgCode'] = $parent_third_id;
                }
                $thirdData = (new FaceDHYunRuiCloudDeviceService())->villageToDeviceCloud($village_id, $villageInfo, $param);
                if (isset($thirdData['errMsg']) && $thirdData['errMsg']) {
                    $thirdMsg = $thirdData['errMsg'];
                }
                // todo 应该以物业进行组添加 记得查询根组
                break;
        }
        $third_info = $thirdData;
        $third_id   = '';
        if (isset($thirdData['communityId']) && $thirdData['communityId']) {
            // 这里是海康云眸 返回 请注意各自返回信息要存储的字段进行处理
            $third_id = $thirdData['communityId'];
            $errMsg   = '';
            $code     = '';
            unset($third_info['communityId']);
        } elseif (isset($thirdData['third_id']) && $thirdData['third_id']) {
            // 这是 大华云睿 返回 请注意各自返回信息要存储的字段进行处理
            $third_id = $thirdData['third_id'];
            $errMsg   = '';
            $code     = '';
            unset($third_info['storeId']);
        } elseif (isset($thirdData['success']) && $thirdData['success']) {
            $errMsg = '';
            $code   = '';
        } else {
            switch ($this->thirdProtocol) {
                case DeviceConst::DH_YUNRUI:
                    $code = DahuaConst::DH_IS_EXISTENT_STORE;
                    if (isset($thirdData['code']) && $thirdData['code'] == $code) {
                        // todo 添加的时候已经存在了 这里去进行查询
                        $storeName = isset($village_info['village_name']) ? $village_info['village_name'] : '小区_' . $village_id;
                        $storeInfo = (new FaceDHYunRuiCloudDeviceService())->getStoreByStoreName($storeName);
                        if ($storeInfo && isset($storeInfo['data']['storeId'])) {
                            $thirdData = $storeInfo;
                            $errMsg   = '';
                            $code     = '';
                            $thirdMsg = '';
                        }
                    }
                    break;
            }
            // todo 其他三方类型返回赋予 $third_id 然后下面统一处理
            if (isset($thirdMsg) && $thirdMsg && $errMsg && $code) {
                $errMsg .= "($thirdMsg)";
            }
        }
        if (isset($third_id) && $third_id && isset($aboutInfo['id'])) {
            // 更新关联记录
            $updateData = [
                'third_id'  => $third_id,
                'last_time' => $nowTime,
            ];
            $dbFaceBindAboutInfo->updateThis($whereAbout, $updateData);
        } elseif (isset($third_id) && $third_id) {
            // 添加关联记录
            $bindData = [
                'bind_type'      => $bind_type,
                'bind_id'        => $bind_id,
                'third_id'       => $third_id,
                'device_type'    => $face_device_type,
                'third_protocol' => $thirdProtocol,
                'add_time'       => $nowTime,
            ];
            if (isset($third_info['bind_number'])) {
                $bindData['bind_number'] = $third_info['bind_number'];
                unset($third_info['bind_number']);
            }
            if (isset($third_info['bind_name'])) {
                $bindData['bind_name'] = $third_info['bind_name'];
                unset($third_info['bind_name']);
                $bindData['third_info'] = json_encode($third_info, JSON_UNESCAPED_UNICODE);
            }
            $dbFaceBindAboutInfo->add($bindData);
        }
        return [
            'third_id'  => $third_id,
            'thirdData' => $thirdData,
            'code'      => $code,
            'msg'       => $errMsg,
        ];
    }


    /**
     * 获取小区同步的相关id或者编号
     * @param string $village_id
     * @param string $thirdProtocol
     * @return array
     */
    protected function filterVillageToData(string $village_id = '', string $thirdProtocol = '') {
        // todo 查询下 小区的同步场所id
        $aboutInfo = [];
        if (!$village_id && $this->village_id) {
            $village_id = $this->village_id;
        }
        if (!$thirdProtocol && $this->thirdProtocol) {
            $thirdProtocol = $this->thirdProtocol;
        }
        if (!$this->village_third_id || $this->village_third_code) {
            $dbFaceBindAboutInfo = new FaceBindAboutInfo();
            switch ($this->thirdProtocol) {
                case DeviceConst::DH_YUNRUI:
                    $whereAbout = [];
                    $whereAbout['bind_type']      = DeviceConst::DH_TO_CLOUD_VILLAGE;
                    $whereAbout['bind_id']        = $village_id;
                    $whereAbout['third_protocol'] = $thirdProtocol;
                    $aboutInfo = $dbFaceBindAboutInfo->getOne($whereAbout, true, 'id DESC');
                    if ($aboutInfo && !is_array($aboutInfo)) {
                        $aboutInfo = $aboutInfo->toArray();
                    } elseif (!$aboutInfo) {
                        $aboutInfo = [];
                    }
                    if (isset($aboutInfo['bind_number']) && $aboutInfo['bind_number']) {
                        $this->village_third_id     = $aboutInfo['bind_number'];
                    } else {
                        $this->village_third_id     = '';
                    }
                    if (isset($aboutInfo['third_id']) && $aboutInfo['third_id']) {
                        $this->village_third_code   = $aboutInfo['third_id'];
                    } else {
                        $this->village_third_code   = '';
                    }
                    if (isset($aboutInfo['account_id']) && $aboutInfo['account_id']) {
                        $this->third_community_code = $aboutInfo['account_id'];
                    } else {
                        $this->third_community_code = '';
                    }
                    break;
                case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                    $whereAbout = [];
                    $whereAbout['bind_type']      = DeviceConst::HK_TO_CLOUD_VILLAGE;
                    $whereAbout['bind_id']        = $village_id;
                    $whereAbout['third_protocol'] = $thirdProtocol;
                    $aboutInfo = $dbFaceBindAboutInfo->getOne($whereAbout, true, 'id DESC');
                    if ($aboutInfo && !is_array($aboutInfo)) {
                        $aboutInfo = $aboutInfo->toArray();
                    } elseif (!$aboutInfo) {
                        $aboutInfo = [];
                    }
                    $this->village_third_code   = '';
                    $this->third_community_code = '';
                    if (isset($aboutInfo['third_id']) && $aboutInfo['third_id']) {
                        $this->village_third_id = $aboutInfo['third_id'];
                    } else {
                        $this->village_third_id = '';
                    }
            }
        } else {
            $aboutInfo = [];
        }
        return $aboutInfo;
    }

    /**
     * 获取楼栋同步的相关id或者编号
     * @param string $bind_id
     * @return array|\think\Model
     */
    protected function filterBuildToData(string $bind_id) {
        // todo 查询下 小区的同步场所id
        $aboutInfo = [];
        if (!$bind_id && $this->single_id) {
            $bind_id = $this->single_id;
        }
        if (!$this->build_third_id || $this->build_third_code) {
            switch ($this->thirdProtocol) {
                case DeviceConst::DH_YUNRUI:
                    $aboutInfo = $this->commonFilterAboutToData(DeviceConst::DH_TO_CLOUD_BUILD, $bind_id, $this->thirdProtocol);
                    if (isset($aboutInfo['bind_number']) && $aboutInfo['bind_number']) {
                        $this->build_third_id   = $aboutInfo['bind_number'];
                    } else {
                        $this->build_third_id   = '';
                    }
                    if (isset($aboutInfo['third_id']) && $aboutInfo['third_id']) {
                        $this->build_third_code = $aboutInfo['third_id'];
                    } else {
                        $this->build_third_code = '';
                    }
                    break;
                case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                    $aboutInfo = $this->commonFilterAboutToData(DeviceConst::HK_TO_CLOUD_SINGLE, $bind_id, $this->thirdProtocol);
                    $this->build_third_code     = '';
                    if (isset($aboutInfo['third_id']) && $aboutInfo['third_id']) {
                        $this->build_third_id   = $aboutInfo['third_id'];
                    } else {
                        $this->build_third_id   = '';
                    }
            }
        } else {
            $aboutInfo = [];
        }
        return $aboutInfo;
    }
    
    /**
     * 获取单元同步的相关id或者编号
     * @param string $bind_id
     * @return array|\think\Model
     */
    protected function filterUnitToData(string $bind_id) {
        // todo 查询下 小区的同步场所id
        $aboutInfo = [];
        if (!$bind_id && $this->floor_id) {
            $bind_id = $this->floor_id;
        }
        if (!$this->unit_third_id || $this->unit_third_code) {
            switch ($this->thirdProtocol) {
                case DeviceConst::DH_YUNRUI:
                    $aboutInfo = $this->commonFilterAboutToData(DeviceConst::DH_TO_CLOUD_UNIT, $bind_id, $this->thirdProtocol);
                    if (isset($aboutInfo['bind_number']) && $aboutInfo['bind_number']) {
                        $this->unit_third_id   = $aboutInfo['bind_number'];
                    } else {
                        $this->unit_third_id   = '';
                    }
                    if (isset($aboutInfo['third_id']) && $aboutInfo['third_id']) {
                        $this->unit_third_code = $aboutInfo['third_id'];
                    } else {
                        $this->unit_third_code = '';
                    }
                    break;
                case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                    $aboutInfo = $this->commonFilterAboutToData(DeviceConst::HK_TO_CLOUD_FLOOR, $bind_id, $this->thirdProtocol);
                    $this->unit_third_code      = '';
                    if (isset($aboutInfo['third_id']) && $aboutInfo['third_id']) {
                        $this->unit_third_id = $aboutInfo['third_id'];
                    } else {
                        $this->unit_third_id = '';
                    }
            }
        } else {
            $aboutInfo = [];
        }
        return $aboutInfo;
    }

    /**
     * 获取房屋同步的相关id或者编号
     * @param string $bind_id
     * @return array|\think\Model
     */
    protected function filterRoomToData(string $bind_id = '') {
        // todo 查询下 小区的同步场所id
        $aboutInfo = [];
        if (!$bind_id && $this->room_id) {
            $bind_id = $this->room_id;
        }
        if (!$this->room_third_id || $this->room_third_code) {
            switch ($this->thirdProtocol) {
                case DeviceConst::DH_YUNRUI:
                    $aboutInfo = $this->commonFilterAboutToData(DeviceConst::DH_TO_CLOUD_ROOM, $bind_id, $this->thirdProtocol);
                    if (isset($aboutInfo['bind_number']) && $aboutInfo['bind_number']) {
                        $this->room_third_id   = $aboutInfo['bind_number'];
                    }
                    if (isset($aboutInfo['third_id']) && $aboutInfo['third_id']) {
                        $this->room_third_code = $aboutInfo['third_id'];
                    }
                    break;
                case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                    $aboutInfo = $this->commonFilterAboutToData(DeviceConst::HK_TO_CLOUD_ROOM, $bind_id, $this->thirdProtocol);
                    $this->room_third_code      = '';
                    if (isset($aboutInfo['third_id']) && $aboutInfo['third_id']) {
                        $this->room_third_id = $aboutInfo['third_id'];
                    }
            }
        } else {
            $aboutInfo = [];
        }
        return $aboutInfo;
    }
    
    /**
     * 处理批量同步人员
     * @return array|\think\Model
     */
    protected function filterDhUserBindToData() {
        $houseVillageUserBindService = new HouseVillageUserBindService();
        $thirdProtocol = intval($this->thirdProtocol);
        if ($thirdProtocol == DeviceConst::DH_YUNRUI) {
            // 一键同步 直接查询绑定权限相关
            if (!$this->deviceAuth) {
                $this->deviceAuth = new DeviceAuth();
            }
            // 查询下工作人员绑定情况
            $where = [];
            $where[] = ['device_equipment_type', '=', $this->device_equipment_type];
            $where[] = ['device_id', '=', $this->device_id];
            $where[] = ['village_id', '=', $this->village_id];
            $where[] = ['all_type', '=', 'work'];
            $where[] = ['delete_time', '=', 0];
            $workIds = $this->deviceAuth->getOneColumn($where,'work_id');
            $users = [];
            if (!empty($workIds)) {
                $where_work   = [];
                $where_work[] = ['status', '=', 1];
                $where_work[] = ['village_id', '=', $this->village_id];
                $where_work[] = ['is_del', '=', 0];
                $where_work[] = ['wid', 'in', $workIds];
                $dbHouseWorker = new HouseWorker();
                $workPhoneArr = $dbHouseWorker->getColumn($where_work,'phone');
                $whereUserBind = [];
                $whereUserBind[] = ['phone','in',$workPhoneArr];// 原手机号
                $whereUserBind[] = ['village_id','=',$this->village_id];
                $whereUserBind[] = ['type','=',4];
                $whereUserBind[] = ['status','in',[1,2]];
                $filed = 'pigcms_id,village_id,uid,name,phone,type,vacancy_id,layer_id,floor_id,single_id';
                $userWorkBinds = $houseVillageUserBindService->getHouseUserBindList($whereUserBind,$filed,0);
                if ($userWorkBinds && !is_array($userWorkBinds)) {
                    $userWorkBinds = $userWorkBinds->toArray();
                } elseif (!$userWorkBinds) {
                    $userWorkBinds = [];
                }
                $users = array_merge($users, $userWorkBinds);
            }
            // 查询下住户绑定情况
            $sourceWhere = [];
            $sourceWhere[] = ['device_equipment_type', '=', $this->device_equipment_type];
            $sourceWhere[] = ['device_id', '=', $this->device_id];
            $sourceWhere[] = ['village_id', '=', $this->village_id];
            $sourceWhere[] = ['delete_time', '=', 0];
            $whereAll = $sourceWhere;
            $whereAll[] = ['work_id', '<=', 0];
            $whereAll[] = ['all_type', '=', 'allVillages'];
            $authInfo = $this->deviceAuth->getOne($whereAll,'auth_id');
            if ($authInfo && isset($authInfo['auth_id'])) {
                $whereUserBind = [];
                $whereUserBind['status']     = 1;
                $whereUserBind['village_id'] = $this->village_id;
                $filed = 'pigcms_id,village_id,uid,name,phone,type,vacancy_id,layer_id,floor_id,single_id';
                $userBinds = $houseVillageUserBindService->getHouseUserBindList($whereUserBind,$filed,0);
                if ($userBinds && !is_array($userBinds)) {
                    $userBinds = $userBinds->toArray();
                } elseif (!$userBinds) {
                    $userBinds = [];
                }
                $users = array_merge($users, $userBinds);
                return $users;
            }
            $whereRoom = $sourceWhere;
            $whereRoom[] = ['all_type', '=', 'allRooms'];
            $roomIds = $this->deviceAuth->getOneColumn($whereRoom,'room_id');
            if (!empty($roomIds)) {
                $whereUserBind = [];
                $whereUserBind[] = ['village_id','=',$this->village_id];
                $whereUserBind[] = ['status','=',1];
                $whereUserBind[] = ['vacancy_id','in',$roomIds];
                $filed = 'pigcms_id,village_id,uid,name,phone,type,vacancy_id,layer_id,floor_id,single_id';
                $userRoomBinds = $houseVillageUserBindService->getHouseUserBindList($whereUserBind,$filed,0);
                if ($userRoomBinds && !is_array($userRoomBinds)) {
                    $userRoomBinds = $userRoomBinds->toArray();
                } elseif (!$userRoomBinds) {
                    $userRoomBinds = [];
                }
                $users = array_merge($users, $userRoomBinds);
            }
            $whereLayer = $sourceWhere;
            $whereLayer[] = ['all_type', '=', 'allLayers'];
            $layerIds = $this->deviceAuth->getOneColumn($whereLayer,'layer_id');
            if (!empty($layerIds)) {
                $whereUserBind = [];
                $whereUserBind[] = ['village_id','=',$this->village_id];
                $whereUserBind[] = ['status','=',1];
                $whereUserBind[] = ['layer_id','in',$layerIds];
                $filed = 'pigcms_id,village_id,uid,name,phone,type,vacancy_id,layer_id,floor_id,single_id';
                $userLayerBinds = $houseVillageUserBindService->getHouseUserBindList($whereUserBind,$filed,0);
                if ($userLayerBinds && !is_array($userLayerBinds)) {
                    $userLayerBinds = $userLayerBinds->toArray();
                } elseif (!$userLayerBinds) {
                    $userLayerBinds = [];
                }
                $users = array_merge($users, $userLayerBinds);
            }
            $whereFloor = $sourceWhere;
            $whereFloor[] = ['all_type', '=', 'allFloors'];
            $floorIds = $this->deviceAuth->getOneColumn($whereFloor,'floor_id');
            if (!empty($floorIds)) {
                $whereUserBind = [];
                $whereUserBind[] = ['village_id','=',$this->village_id];
                $whereUserBind[] = ['status','=',1];
                $whereUserBind[] = ['floor_id','in',$floorIds];
                $filed = 'pigcms_id,village_id,uid,name,phone,type,vacancy_id,layer_id,floor_id,single_id';
                $userFloorBinds = $houseVillageUserBindService->getHouseUserBindList($whereUserBind,$filed,0);
                if ($userFloorBinds && !is_array($userFloorBinds)) {
                    $userFloorBinds = $userFloorBinds->toArray();
                } elseif (!$userFloorBinds) {
                    $userFloorBinds = [];
                }
                $users = array_merge($users, $userFloorBinds);
            }
            $whereSingle = $sourceWhere;
            $whereSingle[] = ['all_type', '=', 'allSingles'];
            $singleIds = $this->deviceAuth->getOneColumn($whereSingle,'single_id');
            if (!empty($singleIds)) {
                $whereUserBind = [];
                $whereUserBind[] = ['village_id','=',$this->village_id];
                $whereUserBind[] = ['status','=',1];
                $whereUserBind[] = ['single_id','in',$singleIds];
                $filed = 'pigcms_id,village_id,uid,name,phone,type,vacancy_id,layer_id,floor_id,single_id';
                $userSingleBinds = $houseVillageUserBindService->getHouseUserBindList($whereUserBind,$filed,0);
                if ($userSingleBinds && !is_array($userSingleBinds)) {
                    $userSingleBinds = $userSingleBinds->toArray();
                } elseif (!$userSingleBinds) {
                    $userSingleBinds = [];
                }
                $users = array_merge($users, $userSingleBinds);
            }
            $users = array_unique($users);
            return $users;
        }
        
        $whereFace = [];
        $whereFace[] = ['is_del', '=', 0];
        if ($this->device_sn) {
            $whereFace[] = ['device_sn', '=', $this->device_sn];
        } elseif ($this->device_id) {
            $whereFace[] = ['device_id', '=', $this->device_id];
        }
        $faceField = 'device_id,device_name,device_type,device_sn,village_id,floor_id,device_status,thirdProtocol';
        $deviceInfo = (new HouseFaceDeviceService())->getHouseFaceDeviceInfo($whereFace, $faceField);
        if ($deviceInfo && !is_array($deviceInfo)) {
            $deviceInfo = $deviceInfo->toArray();
        }
        $village_id = isset($deviceInfo['village_id']) && intval($deviceInfo['village_id']) > 0 ? intval($deviceInfo['village_id']) : $this->village_id;
        $whereUserBind = [];
        $whereUserBind[] = ['status', '=', 1];
        $whereUserBind[] = ['village_id', '=', $village_id];
        if (isset($deviceInfo['floor_id']) && $deviceInfo['floor_id'] && $deviceInfo['floor_id'] != -1) {
            $floorIds = explode(',', $deviceInfo['floor_id']);
            $whereUserBind[] = ['floor_id', 'in', $floorIds];
            $get_work = true;
        } else {
            $get_work = false;
        }
        $filed = 'pigcms_id,village_id,uid,name,phone,type,vacancy_id,layer_id,floor_id,single_id';
        $userBinds = $houseVillageUserBindService->getHouseUserBindList($whereUserBind,$filed,0);
        if ($userBinds && !is_array($userBinds)) {
            $userBinds = $userBinds->toArray();
        } elseif (!$userBinds) {
            $userBinds = [];
        }
        // 查询下 工作人员+通行证
        if ($get_work) {
            $whereUserBind   = [];
            $whereUserBind[] = ['status', '=', 1];
            $whereUserBind[] = ['village_id', '=', $village_id];
            $whereUserBind[] = ['type', '=', 4];
            $userWorkBinds = $houseVillageUserBindService->getHouseUserBindList($whereUserBind,$filed,0);
            if ($userWorkBinds && !is_array($userWorkBinds)) {
                $userWorkBinds = $userWorkBinds->toArray();
            } elseif (!$userWorkBinds) {
                $userWorkBinds = [];
            }
            $users = array_merge($userBinds, $userWorkBinds);
        } else {
            $users = $userBinds;
        }
        return $users;
    }
    
    /**
     * 带协议设备配置是否已经设置 统一判断
     * @param int $village_id
     * @param int $property_id
     * @return bool
     */
    public function judgeThirdProtocolDeviceConfig($village_id=0, $property_id=0) {
        $judgeConfig1 = $this->judgeThirdProtocolDHYunRuiCloud($village_id, $property_id);
        $judgeConfig2 = $this->judgeThirdProtocolHikNeiBuCloud($village_id, $property_id);
        if ($judgeConfig1 || $judgeConfig2) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断大华云睿配置项是否存在
     * @param int $village_id
     * @param int $property_id
     * @return bool
     */
    public function judgeThirdProtocolDHYunRuiCloud($village_id=0, $property_id=0) {
        $faceDHYunRuiCloudDeviceService = new FaceDHYunRuiCloudDeviceService();
        if ($faceDHYunRuiCloudDeviceService->judgeConfig()) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 判断海康云眸内部配置项是否存在
     * @param int $village_id
     * @param int $property_id
     * @return bool
     */
    public function judgeThirdProtocolHikNeiBuCloud($village_id=0, $property_id=0) {
        $faceHikCloudNeiBuDeviceService = new FaceHikCloudNeiBuDeviceService();
        if ($faceHikCloudNeiBuDeviceService->judgeConfig()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断下是否显示指纹 需要有添加指纹设备最少一台
     * @param $village_id
     * @return bool
     */
    public function judgeFingerprintConfig($village_id) {
        $faceDHYunRuiCloudDeviceService = new FaceDHYunRuiCloudDeviceService();
        $dbFingerprintDevice = new FingerprintDevice();
        $whereDevice = [];
        $whereDevice[] = ['village_id', '=', $village_id];
        $whereDevice[] = ['delete_time', '=', 0];
        $whereDevice[] = ['device_status', '<>', 4];
        $whereDevice[] = ['third_protocol', '=', DeviceConst::DH_YUNRUI];
        $fingerprintCount = $dbFingerprintDevice->getCount($whereDevice);
        if ($faceDHYunRuiCloudDeviceService->judgeConfig() && $fingerprintCount > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 特殊字符过滤
     * @param string $pigcms_id 过滤对象id
     * @param string $word 过滤对象
     * @param string $replace 没有名称的时候替换用
     * @param int    $limitLength 字符串限制长度
     * @return string|string[]
     */
    public function replaceWord(string $pigcms_id,string $word,string $replace = '', int $limitLength = 25) {
        $pregString = "/[\x{4e00}-\x{9fa5}\·\.\",\?|A-Za-z\.\。\d？！!(（)）：:+-{}\[\]【】《》<>-_· “”：，]+/iu";
        $trimWord = trim($word);
        preg_match_all($pregString, $trimWord, $trimArray);
        $newWord = implode('', $trimArray[0]);
        $replaceSearch = array(
            '/', '\/', '\\', '`', '~', '!', '@', '#',
            '$', '%', '^', '&', '*', '(', ')',
            '=', '|', '{', '}', ':', ';', ',', '[', ']',
            '.', '<', '>', '《', '》', ' ',
            '?', '~', '！', '#', '￥',
            '…', '…', '&', '*', '（', '）',
            '—', '|', '{', '}', '【', '】', '·',
            '‘', '‘’', '；', '：', '”', '“', "'",
            '。', '，', '、', '？', '丶', '+', '-', '_', '='
        );
        $str_replace_txt = str_replace($replaceSearch, '', $newWord);
        if (empty($replace)) {
            // 替换为空
            if ($trimWord != $str_replace_txt) {
                //存在特殊字符 取组合昵称
                $str = '用户' . $pigcms_id;
            } else {
                //不存在特殊字符 取用户姓名
                $str = $str_replace_txt;
            }
        } else {
            // 替换不为空
            if ($trimWord != $str_replace_txt) {
                //存在特殊字符，取替换
                $str = $replace;
            } else {
                $str = $str_replace_txt;
            }
        }
        if (empty($str) || strlen($str) > $limitLength) {
            $str = '用户' . $pigcms_id;
        }
        return $str;
    }

    /**
     * @param int $uid
     * 统一处理人脸图片
     * @return array|string|string[]
     */
    public function getUserFaceImg(int $uid = 0,$img_url='',$extraData=array()) {
        fdump_api(['line' => __LINE__, 'uid' => $uid, 'img_url' => $img_url, 'extraData' => $extraData], 'getUserFaceImg/faceImg');
        $db_house_face_img = new HouseFaceImg();
        $wordencryption    = new wordencryption();
        if (!$uid && $this->user_id) {
            $uid = $this->user_id;
        }
        if(empty($img_url)){
            $whereFace = [
                ['uid',     '=', $uid],
                ['status',  'in',[0,3]],
                ['img_url', '<>', '']
            ];
            $face_img = $db_house_face_img->getOne($whereFace,'img_url,status', 'id DESC');
            if ($face_img && !is_array($face_img)) {
                $face_img = $face_img->toArray();
            }
            if (isset($face_img['img_url']) && $face_img['img_url']) {
                $img_url = $face_img['img_url'];
                if (3 == $face_img['status']) {
                    $img_url = $wordencryption->text_decrypt($img_url);
                }
            }
        }
        fdump_api(['line' => __LINE__, 'uid' => $uid, 'img_url' => $img_url], 'getUserFaceImg/faceImg', 1);

        if(!empty($img_url)){

            switch ($this->thirdProtocol) {
                case DeviceConst::DH_YUNRUI:
                    $width  = 400;
                    $height = 520;
                    break;
                case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                    $width  = 295;
                    $height = 413;
                    break;
                default:
                    $width  = 400;
                    $height = 550;
                    break;
            }
          $is_not_download_upload=0;
            if(!empty($extraData) && isset($extraData['width']) && $extraData['width']>0){
                $width  =  $extraData['width'];
                $height =  $extraData['height'];
            }
            if(!empty($extraData) && isset($extraData['is_not_download_upload'])){
                $is_not_download_upload  =  $extraData['is_not_download_upload'];
            }
            $img_url1 = replace_file_domain($img_url);
            $faceImg = $img_url1;
            fdump_api(['line' => __LINE__, 'uid' => $uid, 'faceImg' => $faceImg], 'getUserFaceImg/faceImg', 1);
            $fileHandle   = new FileHandle();
            $isErr = false;
            if(!$is_not_download_upload && $fileHandle->check_open_oss()) {
                try {
                    $uploadResult = $fileHandle->download($img_url1);
                    fdump_api(['line' => __LINE__, 'msg' => 'oss云下载结果', 'uploadResult' => $uploadResult], 'getUserFaceImg/faceImg', 1);
                    $image_path = $fileHandle->get_path($faceImg);
                    $fileLocal = app()->getRootPath() . '../' .$image_path;
                    $imgPath  = root_path() . '..'.$image_path;

                    $pathinfo = pathinfo($image_path);
                    $format   = 'jpg';
                    $save_path = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '_plot.' . $format;
                } catch (\Exception $e){
                    $isErr = true;
                    fdump_api(['line' => __LINE__, 'msg' => 'oss云下载文件错误', 'err' => $e->getMessage(), 'errLine' => $e->getLine(), 'errFile' => $e->getFile(), 'errCode' => $e->getCode()], 'getUserFaceImg/faceImg', 1);
                    $save_path = $fileHandle->get_path($faceImg);
                    $image_path = $faceImg;
                    $fileLocal = $image_path;
                    $imgPath = $faceImg;
                    $format   = 'jpg';
                    $pathinfo = pathinfo($image_path);
                    if (!$save_path) {
                        $format   = 'jpg';
                        $save_path = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '_plot.' . $format;
                    } elseif (isset($pathinfo['filename']) && $pathinfo['filename']) {
                        $save_path = str_replace('/' . $pathinfo['filename'] . '.', '/' . $pathinfo['filename'] . '_plot.', $save_path);
                    }
                    fdump_api(['line' => __LINE__, 'msg' => '处理后的参数', 'pathinfo' => $pathinfo], 'getUserFaceImg/faceImg', 1);
                }
            } else {
                $save_path = $fileHandle->get_path($faceImg);
                $image_path = $faceImg;
                $fileLocal = $image_path;
                $imgPath = $faceImg;
                $format   = 'jpg';
                $pathinfo = pathinfo($image_path);
                if (!$save_path) {
                    $format = 'jpg';
                    $save_path = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '_plot.' . $format;
                } elseif (isset($pathinfo['filename']) && $pathinfo['filename']) {
                    $save_path = str_replace('/' . $pathinfo['filename'] . '.', '/' . $pathinfo['filename'] . '_plot.', $save_path);
                }
                fdump_api(['line' => __LINE__, 'msg' => '处理后的参数', 'pathinfo' => $pathinfo], 'getUserFaceImg/faceImg', 1);
            }
            fdump_api(['line' => __LINE__, 'msg' => '处理后的参数', 'save_path' => $save_path, 'image_path' => $image_path, 'fileLocal' => $fileLocal, 'imgPath' => $imgPath], 'getUserFaceImg/faceImg', 1);
            try {
                $fileArr = getimagesize($fileLocal);
                fdump_api(['line' => __LINE__, 'msg' => '图片信息', 'fileArr' => $fileArr], 'getUserFaceImg/faceImg', 1);
                if (isset($fileArr[0]) && isset($fileArr[1]) && $fileArr[0] && $fileArr[1]) {
                    $imgWidth = $fileArr[0];
                    $imgHeight = $fileArr[1];
                    if ($imgWidth >= $width) {
                        $height = floor(($width / $imgWidth) * $imgHeight);
                    } else {
                        $width  = $imgWidth;
                        $height  = $imgHeight;
                    }
                    $width = intval($width);
                    $height = intval($height);
                }
            } catch (\Exception $e){
                fdump_api(['line' => __LINE__, 'msg' => '处理图片错误异常', 'err' => $e->getMessage(), 'errLine' => $e->getLine(), 'errFile' => $e->getFile(), 'errCode' => $e->getCode()], 'getUserFaceImg/faceImg', 1);
                return $faceImg;
            }
            fdump_api(['line' => __LINE__, 'msg' => '图片处理后信息', 'width' => $width, 'height' => $height], 'getUserFaceImg/faceImg', 1);
            
            $savePath = root_path() . $save_path;
            $savePath=str_replace('v20//','',$savePath);
            $imgPath=str_replace('_plot.','.',$savePath);
            set_time_limit(0);
            ini_set('memory_limit', '2048M');
            fdump_api(['line' => __LINE__, 'msg' => '处理前所有信息','uid' => $uid, 'img_url' => $img_url, 'imgPath' => $imgPath, 'savePath' => $savePath, 'width' => $width, 'height' => $height, 'format' => $format, 'save_path' => $save_path], 'getUserFaceImg/faceImg', 1);
            
            $filterImgChange = $this->filterImg($imgPath,$savePath,$width,$height, $format);
            $faceImg = cfg('site_url') . $save_path;
            if ($filterImgChange) {
                if(!$isErr && !$is_not_download_upload && $fileHandle->check_open_oss()) {
                    try {
                        $result = $fileHandle->upload($savePath);
                    } catch (\Exception $e){
                        fdump_api(['line' => __LINE__, 'msg' => 'oss上传图片错误异常', 'err' => $e->getMessage(), 'errLine' => $e->getLine(), 'errFile' => $e->getFile(), 'errCode' => $e->getCode()], 'getUserFaceImg/faceImg', 1);
                    }
                    $faceImg = replace_file_domain($save_path);
                }else if(!file_exists($savePath)){
                    $faceImg=$img_url1;
                }
            }else if(!file_exists($savePath)){
                $faceImg=$img_url1;
            }
            fdump_api(['line' => __LINE__, 'msg' => '最后返回图片链接', 'faceImg' => $faceImg], 'getUserFaceImg/faceImg', 1);
        } else {
            $faceImg = '';
        }
        return $faceImg;
    }

    /**
     * 图片裁剪
     * @param $imgPath
     * @param $savePath
     * @param $with
     * @param $heiht
     * @param string $format
     * @param int $quality
     * @return bool
     */
    public function filterImg($imgPath,$savePath,$with,$heiht,string $format = 'data-url',int $quality=90) {
        try {
            $info = $this->traitEncodeImgFormatAndSaveAs($imgPath,$savePath,$with,$heiht, $format , $quality);
            if(!empty($info) && is_array($info) && isset($info['err_msg'])){
               // fdump_api(['imgInfo'=>$info], '00filterImg', 1);
                return false;
            }else{
                return true;
            }
        } catch (\Exception $e){
            return false;
        }
        return false;
    }
    
    /**
     * 获取房屋平台用户同步的相关id或者编号
     * @param int $uid
     * @param int $bind_type
     * @param bool $voluation
     * @return array|\think\Model|null
     */
    public function filterUserToDataFromUid($uid = 0, $bind_type = DeviceConst::DH_UID_CLOUD_USER, $voluation = true) {
        // todo 查询下 房屋的同步场所id
        if (!$uid) {
            $uid = $this->user_id;
        }
        return $this->commonDhFilterUserToData($bind_type, $uid, $voluation);
    }

    /**
     * 过滤返回事件码对照事件
     * @param $eventCode
     * @return string
     */
    public function filterHikNeiBuEventCode($eventCode) {
        $eventCodeArr = [
            '10101' => '刷卡开锁',
            '10104' => '指纹开锁',
            '10114' => '人脸开锁',
            '10118' => '人证开锁',
            '10119' => '蓝牙开锁',
            '10120' => '密码开锁',
            '10122' => '二维码开锁',
            '10124' => '远程开门',
            '10125' => '动态密码开锁',
            '10126' => '人脸认证失败',
            '10128' => '身份证认证[56A0设备身份证比对事件(非开门事件)]',
            '10129' => '身份证认证失败[56A0设备身份证比对事件(非开门事件)]',
        ];
        $eventCode = strval($eventCode);
        $eventCodeMsg = isset($eventCodeArr[$eventCode]) && $eventCodeArr[$eventCode] ? $eventCodeArr[$eventCode] : '';
        return $eventCodeMsg;
    }
    
    // 当前默认 1 人脸识别门禁 2用户操作开门 3 IC卡开门 6 摄像机抓拍  21 蓝牙开门记录  31 二维码开门   51 高空抛物
    public function filterLogFrom($eventCode) {
        $log_from = DeviceConst::DEVICE_FACE_OPEN;
        switch ($eventCode) {
            case '10101':
                $log_from = DeviceConst::DEVICE_IC_CARD_OPEN;
                break;
            case '10104':
                $log_from = DeviceConst::DEVICE_FINGERPRINT_OPEN;
                break;
            case '10114':
                $log_from = DeviceConst::DEVICE_FACE_OPEN;
                break;
            case '10118':
                $log_from = DeviceConst::DEVICE_CERTIFICATES_OPEN;
                break;
            case '10119':
                $log_from = DeviceConst::DEVICE_BLUETOOTH_OPEN;
                break;
            case '10120':
                $log_from = DeviceConst::DEVICE_PASS_WORD_OPEN;
                break;
            case '10122':
                $log_from = DeviceConst::DEVICE_QR_CODE_OPEN;
                break;
            case '10124':
                $log_from = DeviceConst::DEVICE_GATE_OPEN;
                break;
            case '10125':
                $log_from = DeviceConst::DEVICE_DYNAMIC_PASSWORD_OPEN;
                break;
            case '10126':
                $log_from = DeviceConst::DEVICE_FACE_OPEN;
                break;
        }
        return $log_from;
    }
    

    /**
     * 公用的查询
     * @param int $bind_type  绑定类型
     * @param int $bind_id  对应绑定id
     * @param bool $voluation  是否赋值 默认不赋值
     * @param array $param  其他参数
     * @return array|\think\Model|null
     */
    protected function commonDhFilterUserToData($bind_type, $bind_id, $voluation = false, $param = []) {
        $dbFaceUserBindDevice = new FaceUserBindDevice();
        $whereAbout = [];
        if ($this->time_plan_index) {
            $whereAbout[] = ['open_key', '=', $this->time_plan_index];
            if ($this->device_id) {
                $whereAbout[] = ['device_id', '=', $this->device_id];
            } else {
                $whereAbout[] = ['code', '=', $this->device_sn];
            }
        }
        $whereAbout[] = ['bind_type', '=', $bind_type];
        $whereAbout[] = ['bind_id',   '=', $bind_id];
        if (isset($param['person_id'])) {
            $whereAbout[] = ['person_id', '=', $param['person_id']];
        } else {
            $whereAbout[] = ['person_id', '<>', ''];
        }
        if (isset($param['personID'])) {
            $whereAbout[] = ['personID', '=', $param['personID']];
        }
        if (isset($param['code'])) {
            $whereAbout[] = ['code', '=', $param['code']];
        }
        $aboutInfo = $dbFaceUserBindDevice->getOneOrder($whereAbout, true, 'id DESC');
        if ($aboutInfo && !is_array($aboutInfo)) {
            $aboutInfo = $aboutInfo->toArray();
        } elseif (!$aboutInfo) {
            $aboutInfo = [];
        }
        if (isset($aboutInfo['id']) && $voluation) {
            switch ($this->thirdProtocol) {
                case DeviceConst::DH_YUNRUI:
                case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                default:
                    if (isset($aboutInfo['person_id']) && $aboutInfo['person_id']) {
                        $this->user_third_id   = $aboutInfo['person_id'];
                    }
                    if (isset($aboutInfo['personID']) && $aboutInfo['personID']) {
                        $this->user_third_code = $aboutInfo['personID'];
                    }
                    if (isset($aboutInfo['group_id']) && $aboutInfo['group_id']) {
                        $this->room_third_code = $aboutInfo['group_id'];
                    }
                    if (isset($aboutInfo['groupID']) && $aboutInfo['groupID']) {
                        $this->village_third_id = $aboutInfo['groupID'];
                    }
                    break;
            }
        }
        return $aboutInfo;
    }
    
    /**
     * 更改绑定记录
     * @param $bind_type
     * @param $bind_id
     * @param string $thirdProtocol
     * @param array $updateParam
     * @return array|\think\Model
     */
    protected function filterUpdateFaceBindAboutInfo($bind_type,  $bind_id, string $thirdProtocol = '', $updateParam = []) {
        // todo 查询下 小区的同步场所id
        if (!$thirdProtocol && $this->thirdProtocol) {
            $thirdProtocol = $this->thirdProtocol;
        }
        $dbFaceBindAboutInfo = new FaceBindAboutInfo();
        $whereAbout = [];
        $whereAbout['bind_type']      = $bind_type;
        $whereAbout['bind_id']        = $bind_id;
        $whereAbout['third_protocol'] = $thirdProtocol;
        $aboutInfo = $dbFaceBindAboutInfo->getOne($whereAbout, true, 'id DESC');
        if ($aboutInfo && !is_array($aboutInfo)) {
            $aboutInfo = $aboutInfo->toArray();
        } elseif (!$aboutInfo) {
            $aboutInfo = [];
        }
        if (!empty($updateParam)) {
            $dbFaceBindAboutInfo->updateThis($whereAbout, $updateParam);
        }
        return $aboutInfo;
    }

    /**
     * 线上图片转存本地
     * @param $base_img
     * @param string $base_file
     * @param string $file_name
     * @return string
     */
    public function imgOnLineToLocal($base_img, $base_file='',$file_name='') {
        if (!$base_file) {
            $rand_num = date('Ymd');// 换成日期存储
            $base_file = './upload/face_log/'.$rand_num.'/';
        }
        $up_dir   = $base_file;
        $imgurl   = root_path() . '.' . $base_file;
        if (!is_dir($imgurl)) {
            mkdir($imgurl, 0777, true);
        }
//        $file_info =  pathinfo($base_img);
        if ($file_name) {
            $new_file    = $up_dir.$file_name;
            $new_img_url = $imgurl.$file_name;
        }  else {
            $type = 'jpg';
            $imgBase = date('YmdHis_').uniqid();
            $new_file    = $up_dir.$imgBase.'.'.$type;
            $new_img_url = $imgurl.$imgBase.'.'.$type;
        }
        $new_file    = explode('?', $new_file)[0];
        $new_img_url = explode('?', $new_img_url)[0];
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
        fdump_api($new_img_url, '$new_img_url');
        $fp2 = @fopen($new_img_url ,'a');
        fdump_api($base_img, '$new_img_url', 1);
        fwrite($fp2,$img);
        fclose($fp2);
        unset($img,$url);
        $img_path = trim($new_file,'.');
        //判断是否需要上传至云存储
        try {
            $file_handle = new FileHandle();
            $file_handle_url = trim($new_file,'./');
            $file_handle->upload($file_handle_url);
        } catch (\Exception $e) {

        }
        return $img_path;
    }

    /**
     * 获取文件类型.
     */
    public function getContentType($url)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_URL => $url));
        curl_exec($curl);
        $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);  //get content type
        curl_close($curl);
        return $contentType;
    }

    public function traitSaveImage($imageUrl, $savePath, $extension = 'jpeg', $quality = 80)
    {
        try {
            $dirName = dirname($savePath);
            if(!file_exists($dirName)){
                mkdir($dirName,0777,true);
            }
            $arr = getimagesize($imageUrl);
            $height = $arr[1];
            $with = $arr[0];
            if ($height > 10000) {
                $height = floor($height / 20);
                $with = floor($with / 20);
            } elseif ($height > 3000) {
                $height = floor($height / 10);
                $with = floor($with / 10);
            } elseif ($height > 2000) {
                $height = floor($height / 5);
                $with = floor($with / 5);
            } elseif ($height > 1000) {
                $height = floor($height / 2);
                $with = floor($with / 2);
            }
            $height = intval($height);
            $with = intval($with);
            $imgInfo=$this->traitEncodeImgFormatAndSaveAs($imageUrl,$savePath,$with,$height, $extension , $quality);
            if(!empty($imgInfo) && is_array($imgInfo) && isset($imgInfo['err_msg'])){
                //fdump_api(['imgInfo'=>$imgInfo], '00traitSaveImage', 1);
                return '';
            }else{
                return $imgInfo;
            }
        } catch (\Exception $e){
            return false;
        }
    }
}