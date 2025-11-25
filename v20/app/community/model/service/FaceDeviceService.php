<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      人脸/监控等云相关设备服务 主要处理业务逻辑
 */

namespace app\community\model\service;


use app\community\model\db\HouseFaceBindDevice;
use app\community\model\db\HouseFaceSyncLog;
use app\community\model\service\Device\AlarmDeviceService;
use app\community\model\service\Device\DeviceAlarmEventService;
use app\community\model\service\Device\DeviceHkNeiBuHandleService;
use app\community\model\db\Device\FingerprintDevice;
use app\community\model\db\Device\FingerprintUser;

use app\community\model\db\FaceBindAboutInfo;
use app\community\model\db\HouseProperty;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\CameraDeviceBind;
use app\community\model\db\HouseDeviceChannel;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageLayer;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\FaceUserBindDevice;
use app\community\model\db\HouseFaceDevice;
use app\community\model\db\HouseCameraDevice;
use app\community\model\db\DeviceAuth;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseWorker;
use app\community\model\db\HouseUserLog;
use app\community\model\service\Device\FaceDHYunRuiCloudDeviceService;
use app\community\model\service\Device\FaceHikCloudNeiBuDeviceService;
use app\community\model\service\Device\HikGateWayDeviceService;
use app\community\model\service\Device\HikGateWayEventService;
use app\consts\HikConst;
use app\consts\DahuaConst;
use app\consts\DeviceConst;
use app\traits\device\HikGateWayTraits;
use app\traits\FaceDeviceDHCloudTraits;
use app\traits\FaceDeviceHikCloudTraits;
use app\traits\house\HouseTraits;
use app\traits\house\DeviceTraits;
use app\traits\house\DeviceUserTraits;
use Fastwhal\HikDeviceGateway\BuiltInConstants;
use think\facade\Cache;

use file_handle\FileHandle;

class FaceDeviceService
{
    use FaceDeviceDHCloudTraits;
    use FaceDeviceHikCloudTraits;
    use HouseTraits;
    use DeviceTraits;
    use DeviceUserTraits;
    use HikGateWayTraits;

    /**
     * @var array 人脸设备已经添加过的默认协议 也是判断新的添加和编辑时候必传协议的依据
     */
    public $defaultProtocol = [
        DeviceConst::HIK_DEVICE_TYPE  => DeviceConst::HIK_YUNMO_WAIBU,
        DeviceConst::DH_DEVICE_TYPE => DeviceConst::DH_H8900,
    ];

    public $protocolDevice = [
        DeviceConst::HIK_DEVICE_TYPE, DeviceConst::DH_DEVICE_TYPE
    ];
    
    /**
     * 对应设备协议相关信息数组
     * @var array[][]
     */
    public $thirdProtocolArr = [];

    /**
     * 对应设备协议相关信息数组
     * @var array[][]
     */
    public $thirdProtocolShowArr = [];
    
    /** @var string[] 大华匹配楼栋单元房屋方式 */
    public $operateModeTypeArr = [
        DahuaConst::OPERATE_AUTO_SYN_DATA => '自动匹配同步',
        DahuaConst::OPERATE_HAND_SYN_DATA => '手动匹配同步',
    ];

    /** @var int 当前时间 */
    public $nowTime = 0;

    public function __construct(){
        $this->nowTime              = time();
        $this->thirdProtocolArr     = $this->getThirdProtocolArr();
        $this->thirdProtocolShowArr = $this->getThirdProtocolArr(true);
    }

    //海康网关 视频 支持的设备协议
    public function hikDeviceGatewayVideoProtocolType()
    {
        return [
            BuiltInConstants::PROTOCOL_TYPE_ISUPV5 => 'ISUP5.0',
            BuiltInConstants::PROTOCOL_TYPE_ISUP =>'ISUP2.0',
        ];
    }

    //海康网关 门禁 支持的设备协议
    public function hikDeviceGatewayAcProtocolType()
    {
        return [
            BuiltInConstants::PROTOCOL_TYPE_ISUPV5 => 'ISUP5.0',
            BuiltInConstants::PROTOCOL_TYPE_ISAPI =>'ISAPI'
        ];
    }
    
    /***
     * 获取设备协议
     * @param bool $showTxt
     * @return \array[][]
     */
    public function getThirdProtocolArr($showTxt = false) {
        if (!$showTxt) {
            $thirdProtocolArr = [
                DeviceConst::HIK_BRAND_KEY => [
//                DeviceConst::HIK_YUNMO_WAIBU => [
//                    'brand_key'     => DeviceConst::HIK_BRAND_KEY,
//                    'thirdProtocol' => DeviceConst::HIK_YUNMO_WAIBU,
//                    'thirdTitle'    => DeviceConst::HIK_YUNMO_WAIBU_TITLE,
//                ],
                    DeviceConst::HIK_YUNMO_NEIBU_SHEQU => [
                        'brand_key'     => DeviceConst::HIK_BRAND_KEY,
                        'thirdProtocol' => DeviceConst::HIK_YUNMO_NEIBU_SHEQU,
                        'thirdTitle'    => DeviceConst::HIK_YUNMO_NEIBU_SHEQU_TITLE,
                    ],
                    DeviceConst::HIK_DEVICE_GATEWAY => [
                        'brand_key'     => DeviceConst::HIK_BRAND_KEY,
                        'thirdProtocol' => DeviceConst::HIK_DEVICE_GATEWAY,
                        'thirdTitle'    => DeviceConst::HIK_DEVICE_GATEWAY_TITLE,
                    ],
                    DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM => [
                        'brand_key'     => DeviceConst::HIK_BRAND_KEY,
                        'thirdProtocol' => DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM,
                        'thirdTitle'    => DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM_TITLE
                    ],
//                DeviceConst::HIK_ISC_V151 => [
//                    'brand_key'     => DeviceConst::HIK_BRAND_KEY,
//                    'thirdProtocol' => DeviceConst::HIK_ISC_V151,
//                    'thirdTitle'    => DeviceConst::HIK_ISC_V151_TITLE,
//                ],
                ],
                DeviceConst::DH_BRAND_KEY => [
                    DeviceConst::DH_YUNRUI => [
                        'brand_key'     => DeviceConst::DH_BRAND_KEY,
                        'thirdProtocol' => DeviceConst::DH_YUNRUI,
                        'thirdTitle'    => DeviceConst::DH_YUNRUI_TITLE,
                    ],
//            DeviceConst::DH_H8900 => [
//                'brand_key'     => DeviceConst::DH_BRAND_KEY,
//                'thirdProtocol' => DeviceConst::DH_H8900,
//                'thirdTitle'    => DeviceConst::DH_H8900_TITLE,
//            ],
                ],
            ];
        } else {
            // 查询对应信息展示
            $thirdProtocolArr = [
                DeviceConst::HIK_BRAND_KEY => [
                    DeviceConst::HIK_YUNMO_WAIBU => [
                        'brand_key'     => DeviceConst::HIK_BRAND_KEY,
                        'thirdProtocol' => DeviceConst::HIK_YUNMO_WAIBU,
                        'thirdTitle'    => DeviceConst::HIK_YUNMO_WAIBU_TITLE,
                    ],
                    DeviceConst::HIK_YUNMO_NEIBU_SHEQU => [
                        'brand_key'     => DeviceConst::HIK_BRAND_KEY,
                        'thirdProtocol' => DeviceConst::HIK_YUNMO_NEIBU_SHEQU,
                        'thirdTitle'    => DeviceConst::HIK_YUNMO_NEIBU_SHEQU_TITLE,
                    ],
                    DeviceConst::HIK_ISC_V151 => [
                        'brand_key'     => DeviceConst::HIK_BRAND_KEY,
                        'thirdProtocol' => DeviceConst::HIK_ISC_V151,
                        'thirdTitle'    => DeviceConst::HIK_ISC_V151_TITLE,
                    ],
                    DeviceConst::HIK_YUNMO_NEIBU_6000C => [
                        'brand_key'     => DeviceConst::HIK_BRAND_KEY,
                        'thirdProtocol' => DeviceConst::HIK_YUNMO_NEIBU_6000C,
                        'thirdTitle'    => DeviceConst::HIK_YUNMO_NEIBU_6000C_TITLE,
                    ],
                    DeviceConst::HIK_DEVICE_GATEWAY => [
                        'brand_key'     => DeviceConst::HIK_BRAND_KEY,
                        'thirdProtocol' => DeviceConst::HIK_DEVICE_GATEWAY,
                        'thirdTitle'    => DeviceConst::HIK_DEVICE_GATEWAY_TITLE,
                        ],
                    DeviceConst::HIK_INFO_VISION_I_COMMUNITY => [
                        'brand_key'     => DeviceConst::HIK_BRAND_KEY,
                        'thirdProtocol' => DeviceConst::HIK_INFO_VISION_I_COMMUNITY,
                        'thirdTitle'    => DeviceConst::HIK_INFO_VISION_I_COMMUNITY_TITLE,
                    ],
                ],
                DeviceConst::DH_BRAND_KEY => [
                    DeviceConst::DH_YUNRUI => [
                        'brand_key'     => DeviceConst::DH_BRAND_KEY,
                        'thirdProtocol' => DeviceConst::DH_YUNRUI,
                        'thirdTitle'    => DeviceConst::DH_YUNRUI_TITLE,
                    ],
                    DeviceConst::DH_H8900 => [
                        'brand_key'     => DeviceConst::DH_BRAND_KEY,
                        'thirdProtocol' => DeviceConst::DH_H8900,
                        'thirdTitle'    => DeviceConst::DH_H8900_TITLE,
                    ],
                ],
            ];
        }
        return $thirdProtocolArr;
    }
    
    /**
     * @param string $device_sn
     * @param array $param
     * [
     *      'device_id' => '设备id',
     *      'village_id' => '小区id 必传',
     *      'operation' => 'addDevice 必传', addDevice 标识添加设备操作传参 updateDevice 标识更新设备操作
     *      'thirdProtocol' => '设备协议 存在的必传',
     *      'orderGroupId' => '标识同一次操作的标识',
     * ]
     * @return array
     * @throws \think\Exception
     */
    public function handleDevice(string $device_sn = '', array $param = [])
    {
        if (!$device_sn && (!isset($param['device_id']) || !$param['device_id'])) {
            return $this->backData($param, '设备信息不全', 1001);
        }
        $param['orderGroupType'] = 'handle_device';
        $param = $this->filterCommonParam($param);

        // 查询下相关设备信息
        // todo 如果不进行后续楼栋 同步只是单纯同步小区 可以不传设备id和设备序号
        if (!$this->device_id || !$this->device_sn || !$this->face_device_type || !$this->device_equipment_type || !$this->thirdProtocol || !$this->village_id) {
            // 查询下相关设备信息
            $infoArr = $this->handleParamDeviceInfo($param);
            if (isset($infoArr['code']) && $infoArr['code']) {
                return $infoArr;
            }
            $param   = $infoArr['param'];
        }
        // 记录
        $this->clearRecordDeviceBindFilter();
        $this->syn_status        = DeviceConst::BINDS_SYN_START;
        $this->syn_status_txt    = "同步开始";
        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
        $this->recordDeviceBindFilterBox($param);
        $this->step_num++;
        
        if (!$this->village_id) {
            $this->syn_status        = DeviceConst::BINDS_SYN_ERR;
            $this->syn_status_txt    = "同步失败";
            $this->err_reason        = "同步失败(小区信息不全[缺少小区id])";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
            $this->recordDeviceBindFilterBox($param);
            return $this->backData($param, '小区信息不全', 1002);
        }
        if ($this->thirdProtocol) {
            $queueData = [
                'thirdProtocol' => $this->thirdProtocol,
                'village_id'    => $this->village_id,
                'device_id'     => $this->device_id,
                'device_sn'     => $this->device_sn,
                'deviceType'    => $this->device_equipment_type,
                'device_type'   => $this->face_device_type,
                'step_num'      => $this->step_num + 1,
            ];
            if (isset($param['orderGroupId']) && $param['orderGroupType']) {
                $queueData['orderGroupId']   = $param['orderGroupId'];
                $queueData['orderGroupType'] = $param['orderGroupType'];
            }
            switch ($this->thirdProtocol) {
                case DeviceConst::HIK_YUNMO_WAIBU:
                    // todo 云眸外部协议 暂时不做处理
                    break;
                case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                    // 云眸内部协议
                    $job_id = $this->traitCommonHikCloudVillages($queueData);
                    // 记录下队列下发
                    if (isset($job_id)) {
                        $param['job_id']     = $job_id;
                    }
                    $this->syn_status        = DeviceConst::BINDS_SYN_VILLAGE_QUEUE;
                    $this->syn_status_txt    = "下发执行同步小区队列";
                    $this->err_reason        = "";
                    $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                    $this->recordDeviceBindFilterBox($param);
                    // todo 临时调试 直接执行  要还原回 调取队列执行
//                    $this->villageToDeviceCloud($queueData);
                    break;
                case DeviceConst::HIK_ISC_V151:
                    // todo 海康综合安防管理平台 v1.5.100 版本  暂时不做处理
                    break;
                case DeviceConst::DH_YUNRUI:
                    // 大华云睿开放平台协议
                    // 获取下物业id
                    $this->property_id = isset($param['property_id']) && $param['property_id'] ? $param['property_id'] : 0;
                    if (!$this->property_id) {
                        $whereVillage = [];
                        $whereVillage[] = ['village_id', '=', $this->village_id];
                        $property_id = (new HouseVillage())->getColumn($whereVillage, 'property_id');
                        if (is_array($property_id)) {
                            $this->property_id = reset($property_id);
                        } else {
                            $this->property_id = $property_id;
                        }
                    }
                    $queueData['property_id'] = $this->property_id;
                    $job_id = $this->traitCommonDHProperty($queueData);
                    if (isset($job_id)) {
                        $param['job_id']     = $job_id;
                    }
                    $this->syn_status        = DeviceConst::BINDS_SYN_PROPERTY_QUEUE;
                    $this->syn_status_txt    = "下发执行同步物业队列";
                    $this->err_reason        = "";
                    $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                    $this->recordDeviceBindFilterBox($param);
                    
                    // todo 临时调试 直接执行  要还原回 调取队列执行
//                    $this->groupToDeviceCloud($queueData);
                    break;
                case DeviceConst::DH_H8900:
                    // todo 大华h8900平台 暂时不做处理
                    break;
                case DeviceConst::HIK_DEVICE_GATEWAY:
                case DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM:
                    // 使用海康网关 平台网关或者小区网关都走这里
//                    $queueData['handleType'] = 'addDeviceBox';
//                    $job_id = $this->traitCommonHikDeviceGateWay($queueData);
//                    if (isset($job_id)) {
//                        $queueData['job_id']     = $job_id;
//                    }
                    fdump_api([ 'msg' => '添加更新设备-同步', 'line' => __LINE__, 'queueData' => $queueData], 'hikGateWay/traitCommonHikDeviceGateWayLog');
                    // todo 临时调试 直接执行  要还原回 调取队列执行
                    (new HikGateWayDeviceService())->addDeviceBox($queueData);
                    break;
            }
        }
        return $this->backData($param, '处理完成');
    }
    
    /**
     * 物业信息同步至设备云  大华物业当 组织下发
     */
    public function groupToDeviceCloud(array $param)
    {
        if (!isset($param['orderGroupId']) || $param['orderGroupType']) {
            $param['orderGroupType'] = 'group_to_device_cloud';
        }
        $param = $this->filterCommonParam($param);
        $nowTime = $this->nowTime ? $this->nowTime : time();

        $this->clearRecordDeviceBindFilter();
        if (!$this->device_id || !$this->device_sn || !$this->face_device_type || !$this->device_equipment_type || !$this->thirdProtocol || !$this->village_id) {
            // 查询下相关设备信息
            $infoArr = $this->handleParamDeviceInfo($param);
            if (isset($infoArr['code']) && $infoArr['code']) {
                $this->syn_status        = DeviceConst::BINDS_SYN_PROPERTY_ERR;
                $this->syn_status_txt    = "同步物业失败";
                $this->err_reason        = "同步物业失败({$infoArr['msg']})";
                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                $this->recordDeviceBindFilterBox($param);
                return $infoArr;
            }
            $param   = $infoArr['param'];
        }
        if (!$this->village_id) {
            $this->syn_status        = DeviceConst::BINDS_SYN_PROPERTY_ERR;
            $this->syn_status_txt    = "同步物业失败";
            $this->err_reason        = "同步物业失败(小区信息不全[缺少小区id])";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
            $this->recordDeviceBindFilterBox($param);
            return $this->backData($param, '小区信息不全', 1002);
        }
        // 记录
        $this->syn_status        = DeviceConst::BINDS_SYN_PROPERTY_START;
        $this->syn_status_txt    = "同步物业开始";
        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
        $this->recordDeviceBindFilterBox($param);
        $this->step_num++;
        
        if (!$this->property_id) {
            $whereVillage = [];
            $whereVillage[] = ['village_id', '=', $this->village_id];
            // todo 注意根据需要变更查询出来的字段数据
            $fieldVillage = 'village_id, village_name, property_id';
            $villageInfo = (new HouseVillageService())->getHouseVillageInfo($whereVillage, $fieldVillage);
            $this->property_id = isset($villageInfo['property_id']) && $villageInfo['property_id'] ? $villageInfo['property_id'] : 0;
        }
        // 有协议
        switch ($this->thirdProtocol) {
            case DeviceConst::DH_YUNRUI:
                $bind_type = DeviceConst::DH_TO_CLOUD_PROPERTY;
                $bind_id   =  $this->property_id;
                if (!$bind_id) {
                    $this->syn_status        = DeviceConst::BINDS_SYN_PROPERTY_ERR;
                    $this->syn_status_txt    = "同步物业失败";
                    $this->err_reason        = "同步物业失败(小区信息不全[缺少物业id])";
                    $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                    $this->recordDeviceBindFilterBox($param);
                    return $this->backData($param, '缺少物业id', 1008);
                }
                break;
            default:
                $this->syn_status        = DeviceConst::BINDS_SYN_PROPERTY_ERR;
                $this->syn_status_txt    = "同步物业失败";
                $this->err_reason        = "同步物业失败(协议类型不存在)";
                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                $this->recordDeviceBindFilterBox($param);
                return $this->backData($param, '协议类型不存在', 104);
        }
        $aboutInfo = $this->commonFilterAboutToData($bind_type, $bind_id, $this->thirdProtocol);
        $backParam = $param;
        $backParam['bind_type']      = $bind_type;
        $backParam['bind_id']        = $bind_id;
        $backParam['third_protocol'] = $this->thirdProtocol;
        if (isset($aboutInfo['third_id'])) {
            $backParam['third_id']    = $aboutInfo['third_id'];
            $backParam['bind_number'] = $aboutInfo['bind_number'];
            $backParam['bind_name']   = $aboutInfo['bind_name'];
            $third_id                 = $aboutInfo['third_id'];
            $third_code               = $aboutInfo['bind_number'];
            
            $this->syn_status        = DeviceConst::BINDS_SYN_PROPERTY_SUCCESS;
            $this->syn_status_txt    = "同步物业成功[获取组织成功]";
            $this->err_reason        = "";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
            $this->recordDeviceBindFilterBox($param);
            $this->step_num++;
        } else {
            switch ($this->thirdProtocol) {
                case DeviceConst::DH_YUNRUI:
                    $whereProperty = [];
                    $whereProperty[] = ['id', '=', $this->property_id];
                    $fieldProperty = 'id, property_name';
                    $property_info = (new HouseProperty())->get_one($whereProperty, $fieldProperty);
                    if ($property_info) {
                        $property_info = $property_info->toArray();
                    }
                    $addResult = (new FaceDHYunRuiCloudDeviceService())->propertyToDeviceCloud($this->property_id, $property_info);
                    $third_info = $addResult;
                    if (isset($addResult['third_id']) && $addResult['third_id']) {
                        $backParam['third_id'] = $addResult['third_id'];
                        $third_id              = $addResult['third_id'];
                        $third_code            = $addResult['bind_number'];
                        unset($third_info['third_id']);
                        
                        $this->syn_status        = DeviceConst::BINDS_SYN_PROPERTY_SUCCESS;
                        $this->syn_status_txt    = "同步物业成功[添加组织成功]";
                        $this->err_reason        = "";
                        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                        $this->recordDeviceBindFilterBox($param);
                    } else {
                        $errMsg = '新增组织失败';
                        $code = 1009;
                        $message=isset($addResult['errMsg']) ? $addResult['errMsg']:$addResult['message'];
                        $this->syn_status        = DeviceConst::BINDS_SYN_PROPERTY_FAIL;
                        $this->syn_status_txt    = "新增组织失败";
                        $this->err_reason        = "新增组织失败({$message})";
                        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                        $this->recordDeviceBindFilterBox($param);
                        return $this->backData($addResult, $errMsg, $code);
                    }
                    break;
                default:
                    $this->syn_status        = DeviceConst::BINDS_SYN_PROPERTY_ERR;
                    $this->syn_status_txt    = "同步物业失败";
                    $this->err_reason        = "同步物业失败(协议类型不存在)";
                    $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                    $this->recordDeviceBindFilterBox($param);
                    return $this->backData($param, '协议类型不存在', 104);
            }
            // 添加关联记录
            $bindData = [
                'bind_type'      => $bind_type,
                'bind_id'        => $bind_id,
                'third_id'       => $third_id,
                'device_type'    => $this->face_device_type,
                'third_protocol' => $this->thirdProtocol,
                'add_time'       => $nowTime,
            ];
            if (isset($third_info['bind_number'])) {
                $bindData['bind_number']  = $third_info['bind_number'];
                $backParam['bind_number'] = $bindData['bind_number'];
                unset($third_info['bind_number']);
            }
            if (isset($third_info['bind_name'])) {
                $bindData['bind_name']  = $third_info['bind_name'];
                $backParam['bind_name'] = $bindData['bind_name'];
                unset($third_info['bind_name']);
                $bindData['third_info'] = json_encode($third_info, JSON_UNESCAPED_UNICODE);
            }
            $this->commonSaveAboutInfo($bind_type, $bind_id, $bindData);
        }
        $queueData = [
            'thirdProtocol'       => $this->thirdProtocol,
            'village_id'          => $this->village_id,
            'device_id'           => $this->device_id,
            'device_sn'           => $this->device_sn,
            'device_type'         => $this->face_device_type,
            'parent_third_id'     => isset($third_id) ? $third_id : '',
            'deviceType'          => $this->device_equipment_type,
            'property_third_id'   => isset($third_id) ? $third_id : '',
            'property_third_code' => isset($third_code) ? $third_code : '',
            'step_num'            => $this->step_num + 1,
        ];
        if (isset($param['orderGroupId']) && $param['orderGroupType']) {
            $queueData['orderGroupId']   = $param['orderGroupId'];
            $queueData['orderGroupType'] = $param['orderGroupType'];
        }
        switch ($this->thirdProtocol) {
            case DeviceConst::DH_YUNRUI:
                // 默认会同步下小区信息至 大华云睿 云
                if (isset($param['syn']) && $param['syn'] == 'direct') {
                   $this->villageToDeviceCloud($queueData);
                } else {
                    $job_id = $this->traitCommonDHCloudVillages($queueData);
                    
                    if (isset($job_id)) {
                        $param['job_id']     = $job_id;
                    }
                    $this->syn_status        = DeviceConst::BINDS_SYN_VILLAGE_QUEUE;
                    $this->syn_status_txt    = '下发执行同步小区队列';
                    $this->err_reason        = "";
                    $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                    $this->recordDeviceBindFilterBox($param);
                    
                    // todo 临时调试 直接执行  要还原回 调取队列执行
//                    $this->villageToDeviceCloud($queueData);
                }
                break;
        }

        return $this->backData($backParam);
    }

    /**
     * 小区信息同步至设备云
     * @param $param
     * [
     *    'thirdProtocol' => '协议类型', 注意支持协议的设备必传
     *    'village_id' => '小区id', 必传
     *    'device_type' => '设备类型', 必传
     *    'device_id' => '设备id', 和下面一个任意必传
     *    'device_sn' => '设备序列号', 和上面一个任意必传
     * ]
     * @return array
     * @throws \think\Exception
     */
    public function villageToDeviceCloud(array $param)
    {
        if (!isset($param['orderGroupId']) || $param['orderGroupType']) {
            $param['orderGroupType'] = 'village_to_device_cloud';
        }
        $param = $this->filterCommonParam($param);

        /** 同步的父级id或者编号  */
        $parent_third_id  = isset($param['parent_third_id']) && $param['parent_third_id']   ? $param['parent_third_id'] : '';
        if (!$parent_third_id && $this->property_third_id) {
            $parent_third_id = $this->property_third_id;
        }
        // todo ↑ 注意上面的是 这个接口能接受到的所有数据 但是 需要进行判断 因为不是一定存在  使用时候注意
        $this->clearRecordDeviceBindFilter();
        if ($this->device_id || $this->device_sn) {
            // 查询下相关设备信息
            $infoArr = $this->handleParamDeviceInfo($param);
            if (isset($infoArr['code']) && $infoArr['code']) {
                $this->syn_status        = DeviceConst::BINDS_SYN_VILLAGE_ERR;
                $this->syn_status_txt    = "同步小区失败";
                $this->err_reason        = "同步小区失败({$infoArr['msg']})";
                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                $this->recordDeviceBindFilterBox($param);
                if ($this->device_equipment_type == DeviceConst::DEVICE_TYPE_ALARM) {
                    $alarmDeviceService = new AlarmDeviceService();
                    $whereAlarmUpdate = [];
                    $whereAlarmUpdate[] = ['village_id', '=', $this->village_id];
                    if ($this->device_id) {
                        $whereAlarmUpdate[] = ['device_id', '=', $this->device_id];
                    } elseif ($this->device_sn) {
                        $whereAlarmUpdate[] = ['device_serial', '=', $this->device_sn];
                    }
                    $whereAlarmUpdate[] = ['is_del', '=', 0];
                    $param1s = ['cloud_status' => 3, 'cloud_reason' => "同步小区失败({$infoArr['msg']})", 'update_time' => time()];
                    $alarmDeviceService->updateAlarmDevice($whereAlarmUpdate, $param1s);
                }
                return $infoArr;
            }
            $param   = $infoArr['param'];
        }
        
        // 记录
        $this->syn_status        = DeviceConst::BINDS_SYN_VILLAGE_START;
        $this->syn_status_txt    = '同步小区开始';
        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
        $this->recordDeviceBindFilterBox($param);
        $this->step_num++;
        
        if (!$this->village_id) {
            $this->syn_status        = DeviceConst::BINDS_SYN_VILLAGE_ERR;
            $this->syn_status_txt    = "同步小区失败";
            $this->err_reason        = "同步小区失败(小区信息不全[缺少小区id])";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
            $this->recordDeviceBindFilterBox($param);
            return $this->backData($param, '小区信息不全', 1002);
        }
        if (isset($infoArr) && isset($infoArr['deviceInfo']) && $infoArr['deviceInfo']) {
            $deviceInfo       = $infoArr['deviceInfo'];
        } else {              
            $deviceInfo       = [];
        }
        $whereVillage = [];
        $whereVillage[] = ['village_id', '=', $this->village_id];
        // todo 注意根据需要变更查询出来的字段数据
        $fieldVillage = 'village_id, village_name, property_id, province_id, city_id, area_id, village_address, long, lat, property_phone';
        $villageInfo = (new HouseVillageService())->getHouseVillageInfo($whereVillage, $fieldVillage);
        if ($villageInfo && !is_array($villageInfo)) {
            $villageInfo = $villageInfo->toArray();
            if (isset($villageInfo['property_id']) && $villageInfo['property_id']) {
                $this->property_id = $villageInfo['property_id'];
            }
        }
        $nowTime = time();
        // 有协议
        $dbFaceBindAboutInfo = new FaceBindAboutInfo();
        switch ($this->thirdProtocol) {
            case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                $bind_type = DeviceConst::HK_TO_CLOUD_VILLAGE;
                $bind_id   = $this->village_id;
                break;
            case DeviceConst::DH_YUNRUI:
                $bind_type = DeviceConst::DH_TO_CLOUD_VILLAGE;
                $bind_id   = $this->village_id;
                if (!$parent_third_id && isset($villageInfo['property_id']) && $villageInfo['property_id']) {
                    // 没有父级查询下
                    $parent_bind_type = DeviceConst::DH_TO_CLOUD_PROPERTY;
                    $parent_bind_id   = $this->property_id;
                    $parentAboutInfo  = $this->commonFilterAboutToData($parent_bind_type, $parent_bind_id, $this->thirdProtocol);
                    $parent_third_id  = isset($parentAboutInfo['third_id']) && $parentAboutInfo['third_id'] ? $parentAboutInfo['third_id'] : '';
                }
                break;
        }
        $paramResult = [];
        if (isset($bind_type) && isset($bind_id)) {
            $third_id = '';
            $aboutInfo = $this->commonFilterAboutToData($bind_type, $bind_id, $this->thirdProtocol);

	        $backParam = $param;
            $backParam['bind_type'] = $bind_type;
            $backParam['bind_id']   = $bind_id;
            if (isset($aboutInfo['third_id']) && $aboutInfo['third_id']) {
                $updateParam = [
                    'nowTime'         => $nowTime,
                    'bind_type'       => $bind_type,
                    'bind_id'         => $bind_id,
                    'thirdProtocol'   => $this->thirdProtocol,
                    'village_id'      => $this->village_id,
                    'villageInfo'     => $villageInfo,
                    'device_type'     => $this->face_device_type,
                    'third_id'        => $aboutInfo['third_id'],
                    'bind_number'     => $aboutInfo['bind_number'],
                    'param'           => $param,
                    'aboutInfo'       => $aboutInfo,
                    'parent_third_id' => $parent_third_id,
                    'deviceType'      => $this->device_equipment_type,
                ];
                $third_id = $aboutInfo['third_id'];
                if (isset($villageInfo['village_name']) && isset($aboutInfo['bind_name']) && $villageInfo['village_name'] && $villageInfo['village_name'] == $aboutInfo['bind_name']) {
                    // todo 名称相同  不调取 三方接口
                    $updateResult = [];
                    $updateResult['third_id'] = $aboutInfo['third_id'];
                } else {
                    // 更新同步
                    $updateResult = $this->commonVillageToDeviceCloud($updateParam);
                }
                if (isset($updateResult['third_id']) && $updateResult['third_id']) {
                    $backParam['third_id'] = $updateResult['third_id'];
                    $third_id = $updateResult['third_id'];
                    
                    $this->syn_status        = DeviceConst::BINDS_SYN_VILLAGE_SUCCESS;
                    $this->syn_status_txt    = "同步小区成功[更新成功]";
                    $this->err_reason        = "";
                    $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                    $this->recordDeviceBindFilterBox($param);
                    $this->step_num++;
                } elseif (isset($updateResult['code']) && $updateResult['code'] > 0) {
                    // todo 暂时以打印记录错误 后期整合至 专门的表格中 记录相关同步错误
                    $this->syn_status        = DeviceConst::BINDS_SYN_VILLAGE_FAIL;
                    $this->syn_status_txt    = "同步小区失败";
                    $this->err_reason        = $updateResult['msg']."[更新]";
                    $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                    $this->recordDeviceBindFilterBox($param);
                    if ($this->device_equipment_type == DeviceConst::DEVICE_TYPE_ALARM) {
                        $alarmDeviceService = new AlarmDeviceService();
                        $whereAlarmUpdate = [];
                        $whereAlarmUpdate[] = ['village_id', '=', $this->village_id];
                        if ($this->device_id) {
                            $whereAlarmUpdate[] = ['device_id', '=', $this->device_id];
                        } else {
                            $whereAlarmUpdate[] = ['device_serial', '=', $this->device_sn];
                        }
                        $whereAlarmUpdate[] = ['is_del', '=', 0];
                        $param1s = ['cloud_status' => 3, 'cloud_reason' => "同步小区失败:".$updateResult['msg']."[更新]", 'update_time' => time()];
                        $alarmDeviceService->updateAlarmDevice($whereAlarmUpdate, $param1s);
                    }
                    return $updateResult;
                } else {
                    // todo 其他情况根据各自设备方情况进行处理
                    switch ($this->thirdProtocol) {
                        case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                            $code = HikConst::HIK_NON_EXISTENT_COMMUNITIES;
                            if (isset($updateResult['thirdData']['code']) && $updateResult['thirdData']['code'] == $code) {
                                unset($updateParam['third_id'], $updateParam['bind_number'], $updateParam['aboutInfo']);
                                // 需要重新添加同步
                                $addParam = $updateParam;
                                
                                $this->syn_status        = DeviceConst::BINDS_SYN_VILLAGE_FAIL;
                                $this->syn_status_txt    = "同步更新小区失败[待重新添加]";
                                $this->err_reason        = $updateResult['thirdData']['message']."[重新添加]";
                                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                                $this->recordDeviceBindFilterBox($param);
                                $this->step_num++;
                            }
                            break;
                        case DeviceConst::DH_YUNRUI:
                            $code = DahuaConst::DH_NON_EXISTENT_STORE;
                            if (isset($updateResult['thirdData']['code']) && $updateResult['thirdData']['code'] == $code) {
                                unset($updateParam['third_id'], $updateParam['bind_number'], $updateParam['aboutInfo']);
                                // 需要重新添加同步
                                $addParam = $updateParam;
                                
                                $this->syn_status        = DeviceConst::BINDS_SYN_VILLAGE_FAIL;
                                $this->syn_status_txt    = "同步更新小区失败[待重新添加]";
                                $this->err_reason        = $updateResult['thirdData']['errMsg']."[重新添加]";
                                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                                $this->recordDeviceBindFilterBox($param);
                                $this->step_num++;
                            }
                            break;
                    }
                }
                $paramResult = $updateResult;
            } else {
                $addParam = [
                    'nowTime'         => $nowTime,
                    'bind_type'       => $bind_type,
                    'bind_id'         => $bind_id,
                    'thirdProtocol'   => $this->thirdProtocol,
                    'village_id'      => $this->village_id,
                    'villageInfo'     => $villageInfo,
                    'device_type'     => $this->face_device_type,
                    'param'           => $param,
                    'parent_third_id' => $parent_third_id,
                    'deviceType'      => $this->device_equipment_type,
                ];
            }
            if (isset($addParam) && $addParam) {
                if (isset($aboutInfo['third_id']) && $aboutInfo['third_id']) {
                    // 更新报错 更新对象不存在  走下添加逻辑
                    // 先删除下关联
                    $updateBindAbout = [
                        'last_time'      => $nowTime,
                        'device_type'    => -$this->face_device_type,
                        'third_protocol' => -$this->thirdProtocol,
                        'bind_type'      => -$bind_type,
                        'bind_id'        => -$bind_id,
                    ];
                    $whereAbout = [];
                    $whereAbout['bind_type']      = $bind_type;
                    $whereAbout['bind_id']        = $bind_id;
                    $whereAbout['third_protocol'] = $this->thirdProtocol;
                    $dbFaceBindAboutInfo->updateThis($whereAbout, $updateBindAbout);
					
                }
                // 添加同步
                $addResult = $this->commonVillageToDeviceCloud($addParam);

	            if (isset($addResult['third_id']) && $addResult['third_id']) {
                    $backParam['third_id'] = $addResult['third_id'];
                    $third_id = $addResult['third_id'];
                    
                    $this->syn_status        = DeviceConst::BINDS_SYN_VILLAGE_SUCCESS;
                    $this->syn_status_txt    = "同步小区成功[添加成功]";
                    $this->err_reason        = "";
                    $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                    $this->recordDeviceBindFilterBox($param);
                    $this->step_num++;
                } elseif (isset($addResult['code']) && $addResult['code'] > 0) {
                    // 有错进行拦截
                    $this->syn_status        = DeviceConst::BINDS_SYN_VILLAGE_FAIL;
                    $this->syn_status_txt    = "同步小区失败";
                    $this->err_reason        = $addResult['msg']."[添加]";
                    $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                    $this->recordDeviceBindFilterBox($param);
                    if ($this->device_equipment_type == DeviceConst::DEVICE_TYPE_ALARM) {
                        $alarmDeviceService = new AlarmDeviceService();
                        $whereAlarmUpdate = [];
                        $whereAlarmUpdate[] = ['village_id', '=', $this->village_id];
                        if ($this->device_id) {
                            $whereAlarmUpdate[] = ['device_id', '=', $this->device_id];
                        } else {
                            $whereAlarmUpdate[] = ['device_serial', '=', $this->device_sn];
                        }
                        $whereAlarmUpdate[] = ['is_del', '=', 0];
                        $param1s = ['cloud_status' => 3, 'cloud_reason' => "同步小区失败:".$addResult['msg']."[添加]", 'update_time' => time()];
                        $alarmDeviceService->updateAlarmDevice($whereAlarmUpdate, $param1s);
                    }
                    return $addResult;
                }
                $paramResult = $addResult;
            }
            if (!isset($third_id) || !$third_id) {
                // 无符合条件不进行下级同步
                return $this->backData($backParam);
            }
            switch ($this->device_equipment_type) {
                case DeviceConst::DEVICE_TYPE_CAMERA:
                    // todo 设备如果是监控去添加监控
                    $this->deviceToDeviceFiler($this->thirdProtocol, $third_id, $aboutInfo, $param, $paramResult);
                    break;
                case DeviceConst::DEVICE_TYPE_ALARM:
                    if ($this->thirdProtocol != DeviceConst::DH_YUNRUI) {
                        // 大华云睿 咱不走楼栋单元同步
                        // todo 走下级 待执行队列  比如楼栋 目前仅支持人脸 同步下级 先手动生成
                        $param['paramResult'] = $paramResult;
                        $this->singleToDeviceFilter($deviceInfo, $this->village_id, $this->thirdProtocol, $param);
                        if (!isset($deviceInfo['single_id']) || !$deviceInfo['single_id']) {
                            $this->deviceToDeviceFiler($this->thirdProtocol, $third_id, $aboutInfo, $param, $paramResult);
                        }
                    }
                    break;
                case DeviceConst::DEVICE_TYPE_FACE:
                    if ($this->thirdProtocol != DeviceConst::DH_YUNRUI) {
                        // 大华云睿 咱不走楼栋单元同步
                        // todo 走下级 待执行队列  比如楼栋 目前仅支持人脸 同步下级 先手动生成
                        $param['paramResult'] = $paramResult;
                        $this->singleToDeviceFilter($deviceInfo, $this->village_id, $this->thirdProtocol, $param);
                        if (isset($deviceInfo['floor_id']) && ($deviceInfo['floor_id']==-1 || !$deviceInfo['floor_id'])) {
                            $this->deviceToDeviceFiler($this->thirdProtocol, $third_id, $aboutInfo, $param, $paramResult);
                        }
                    } else {
                        // todo 设备如果是人脸去添加人脸
                        $this->deviceToDeviceFiler($this->thirdProtocol, $third_id, $aboutInfo, $param, $paramResult);
                    }
                    break;
                    
            }
        }
        return $param;
    }

    /**
     * 同步楼栋信息整理
     * @param array   $deviceInfo    设备信息
     * @param string $village_id    小区id
     * @param string  $thirdProtocol 协议
     * @param array  $param
     */
    public function singleToDeviceFilter(array $deviceInfo,string $village_id,string $thirdProtocol, $param = []) {
        $db_floor  = new HouseVillageFloor();
        $db_single = new HouseVillageSingle();
        if (isset($deviceInfo['floor_id']) && $deviceInfo['floor_id'] && $deviceInfo['floor_id'] != -1) {
            $floorIds = explode(',', $deviceInfo['floor_id']);
            $whereFloor    = [];
            $whereFloor[]  = ['floor_id', 'in', $floorIds];
            $whereFloor[]  = ['status', 'in', [0,1]];
            $whereFloor[]  = ['village_id', '=', $village_id];
            $singleIds = $db_floor->getOneColumn($whereFloor, 'single_id');
            $whereSingle    = [];
            $whereSingle[]  = ['id', 'in', $singleIds];
            $whereSingle[]  = ['status', 'in', [0,1]];
            $whereSingle[]  = ['village_id', '=', $village_id];
            $whereSingle[]  = ['single_number', '<>', ''];
            $singleInfoArrId = $db_single->getList($whereSingle, 'id,single_name,single_number');
        } else {
            $whereSingle    = [];
            $whereSingle[]  = ['status', 'in', [0,1]];
            $whereSingle[]  = ['village_id', '=', $village_id];
            $whereSingle[]  = ['single_number', '<>', ''];
            $singleInfoArrId = $db_single->getList($whereSingle, 'id,single_name,single_number');
        }
        if (!empty($singleInfoArrId) && !is_array($singleInfoArrId)) {
            $singleInfoArrId = $singleInfoArrId->toArray();
        }
        $this->clearRecordDeviceBindFilter();
        $this->syn_status        = DeviceConst::DEVICE_ALL_SYN_BUILD_START;
        $this->syn_status_txt    = "同步设备楼栋开始";
        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__,'param' =>$param];
        $this->recordDeviceBindFilterBox($param);
        $this->step_num++;
        if (!empty($singleInfoArrId)) {
            $step_num = $this->step_num + 1;
            switch ($thirdProtocol) {
                case DeviceConst::DH_YUNRUI:
                    // todo 目前由于改为手动列表匹配绑定 这里不再自动进行处理
                    return true;
//                    foreach ($singleInfoArrId as $single) {
//                        $this->DhToBuildBox($single);
//                    }
                    break;
                case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                    foreach ($singleInfoArrId as $single) {
                        $this->HikToBuildBox($single, $deviceInfo, $param, $step_num, $thirdProtocol);
                    }
                    break;
            }
        } else {
            $this->syn_status        = DeviceConst::BINDS_SYN_BUILD_ERR;
            $this->syn_status_txt    = "同步楼栋失败(没有可以同步的楼栋信息)";
            $this->err_reason        = "同步楼栋失败(没有可以同步的楼栋信息)";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__,'param' =>$param,'whereSingle' =>$whereSingle];
            $this->recordDeviceBindFilterBox($param);
            $this->step_num++;
        }
        return true;
    }
    
    /**
     * 设置批量生成 楼栋单元楼层房屋
     * @param array $param 具体直接代码中注释 很全
     * @return array
     * @throws \think\Exception
     */
    public function singleDHBuildingToDeviceCloud(array $param) {
        /** @var int village_id 小区id */
        $village_id       = isset($param['village_id'])      && $param['village_id']                    ? $param['village_id']             : 0;
        if (!$village_id) {
            return $this->backData($param, '小区信息不全', 1002);
        }
        /** @var  string buildingName 楼栋名称 */
        $buildingName       = isset($param['buildingName'])  && $param['buildingName']                  ? trim($param['buildingName'])     : '';
        /** @var  string buildingNames 楼栋名称数组集合 */
        $buildingNames      = isset($param['buildingNames']) && $param['buildingNames']                 ? $param['buildingNames']          : [];
        if (empty($buildingNames)) {
            $buildingNames    = [$buildingName];
        }
        /** @var  string buildingNumber 楼栋序号 限制[1-999] */
        $buildingNumber     = isset($param['buildingNumber']) && $param['buildingNumber']               ? trim($param['buildingNumber'])   : '';
        /** @var  string buildingNumbers 楼栋序号数组集合 */
        $buildingNumbers    = isset($param['buildingNumbers']) && $param['buildingNumbers']             ? $param['buildingNumbers']  : [];
        if (empty($buildingNumbers)) {
            $buildingNumbers  = [$buildingNumber];
        }
        foreach ($buildingNumbers as $buildingNumber)  {
            if ($buildingNumber < 1 || $buildingNumber > 999) {
                return $this->backData($param, '请注意【楼栋序号】限定1-999', 1014);
            }
        }
        /***@var string pOrgCode 支持按父组织编码过滤，返回该组织及子组织全部*/
        $pOrgCode           = isset($param['pOrgCode'])                                                 ? $param['pOrgCode']               : '';
        
        if (!$pOrgCode) {
            $aboutInfo = $this->commonFilterAboutToData(DeviceConst::DH_TO_CLOUD_VILLAGE, $village_id, DeviceConst::DH_YUNRUI);
            if (isset($aboutInfo['third_id']) && $aboutInfo['third_id']) {
                $pOrgCode = $param['pOrgCode'] = $aboutInfo['third_id'];
            }
        }
        if (!$pOrgCode) {
            $queueData = [
                'thirdProtocol' => DeviceConst::DH_YUNRUI,
                'village_id'    => $village_id,
                'syn'           => 'direct',
            ];
            $this->groupToDeviceCloud($queueData);
            return $this->backData($param, '缺失父组织编码', 1007);
        }
        /** @var string unitNum 单元数,取值范围[1,9]*/
        $unitNum          = isset($param['unitNum'])                                              ? $param['unitNum']                   : '';
        /** @var string floorNum 层数，取值范围[1,99]*/
        $floorNum         = isset($param['floorNum'])                                             ? $param['floorNum']                  : '';
        /** @var string houseNum 每层房屋数，取值范围[1,99]*/
        $houseNum         = isset($param['houseNum'])                                             ? $param['houseNum']                  : '';
        if (intval($unitNum) < 1 || intval($unitNum) > 9) {
            return $this->backData($param, '缺少必要数量', 1010);
        }
        if (intval($floorNum) < 1 || intval($floorNum) > 99) {
            return $this->backData($param, '缺少必要数量', 1010);
        }
        if (intval($houseNum) < 1 || intval($houseNum) > 99) {
            return $this->backData($param, '缺少必要数量', 1010);
        }
        
        $single_info = [
            'buildingNames'   => $buildingNames,
            'buildingNumbers' => $buildingNumbers,
        ];
        $params = [
            'pOrgCode'    => $pOrgCode,
            'unitNum'     => $unitNum,
            'floorNum'    => $floorNum,
            'houseNum'    => $houseNum,
        ];
        $result = (new FaceDHYunRuiCloudDeviceService())->buildingToDeviceCloud(0, $single_info, $params);
        if (isset($result['success']) && $result['success']) {
            // todo 如果自动同步匹配 走下匹配逻辑
            if (isset($param['auto_syn']) && intval($param['auto_syn'])==1) {
                
            }
            return $this->backData($result);
        } else {
            $errMsg = isset($result['errMsg']) && $result['errMsg'] ? $result['errMsg'] : '新增楼栋单元房屋失败';
            return $this->backData($param, $errMsg, 1011);
        }
    }

    public $dHYROrgTypeArr = [
        '1'  => '组织', '2'  => '场所',
        '10' => '楼栋', '11' => '单元',
        '12' => '房屋',
    ];
    
    /**
     * @param array $param 相关传参
     * [
     *    'pageNum'     => 1, // 页数 默认1
     *    'pageSize'    => 50, // 每页条数 默认50
     *    'orgType'     => 100, // 10-楼栋、11-单元、12-房屋  默认10
     *    'pOrgCode'    => 100, // 父组织编码过滤
     *    'village_id'  => 50, // 小区ID
     * ]
     * @return array
     * @throws \think\Exception
     */
    public function getDhBuidingUnitRoomList(array $param) {
        /** @var int thirdProtocol 协议 */
        $thirdProtocol    = isset($param['thirdProtocol'])   && $param['thirdProtocol']     ? $param['thirdProtocol']   : 0;
        /** @var int village_id 小区id */
        $village_id       = isset($param['village_id'])      && $param['village_id']        ? $param['village_id']      : 0;

        switch ($thirdProtocol) {
            case DeviceConst::DH_YUNRUI:
                /***@var string pOrgCode 支持按父组织编码过滤，返回该组织及子组织全部*/
                $pOrgCode         = isset($param['pOrgCode'])                                       ? $param['pOrgCode']        : '';
                if (!$pOrgCode && $village_id) {
                    $aboutInfo = $this->commonFilterAboutToData(DeviceConst::DH_TO_CLOUD_VILLAGE, $village_id, $thirdProtocol);
                    if (isset($aboutInfo['third_id']) && $aboutInfo['third_id']) {
                        $pOrgCode = $param['pOrgCode'] = $aboutInfo['third_id'];
                    }else{
                        return [];
                    }
                }
                if (isset($param['orgType']) && $param['orgType']==10) {
                    $result = (new FaceDHYunRuiCloudDeviceService())->getBuildingForPage($param);
                } else {
                    $result = (new FaceDHYunRuiCloudDeviceService())->getBuidingUnitRoomList($param);
                }
                if (isset($result['data']['pageData']) && $result['data']['pageData']) {
                    foreach ($result['data']['pageData'] as &$item) {
                        if (isset($this->dHYROrgTypeArr[$item['orgType']])) {
                            $item['orgTypeTxt'] = $this->dHYROrgTypeArr[$item['orgType']];
                        } else {
                            $item['orgTypeTxt'] = '未知';
                        }
                        $item['sysModel']       = '未匹配同步';
                        $item['sysModelName']   = '暂无';
                        $item['auto_syn']       = '0';
                        $item['isSyn']          = 0;
                        if (isset($item['orgType']) && isset($item['orgCode'])) {
                            $bind_id   = '';
                            $third_id  = $item['orgCode'];
                            switch ($item['orgType']) {
                                case 10:
                                    $bind_type = DeviceConst::DH_TO_CLOUD_BUILD;
                                    break;
                                case 11:
                                    $bind_type = DeviceConst::DH_TO_CLOUD_UNIT;
                                    break;
                                case 12:
                                    $bind_type = DeviceConst::DH_TO_CLOUD_ROOM;
                                    break;
                            }
                            if (isset($bind_type)) {
                                $aboutInfo  = $this->commonFilterAboutToData($bind_type, $bind_id, $thirdProtocol, $third_id);
                                if (isset($aboutInfo['bind_id'])) {
                                    switch ($aboutInfo['bind_type']) {
                                        case DeviceConst::DH_TO_CLOUD_BUILD:
                                            $houseVillageSignService = new HouseVillageSingleService();
                                            $whereSingle = [
                                                'id'     => $aboutInfo['bind_id'],
                                                'status' => 1
                                            ];
                                            $bindInfo = $houseVillageSignService->getSingleInfo($whereSingle, 'single_name as bind_name');
                                            if ($bindInfo && !is_array($bindInfo)) {
                                                $bindInfo = $bindInfo->toArray();
                                            }
                                            break;
                                        case DeviceConst::DH_TO_CLOUD_UNIT:
                                            $houseVillageSignService = new HouseVillageSingleService();
                                            $whereFloor = [
                                                'floor_id' => $aboutInfo['bind_id'],
                                                'status'   => 1
                                            ];
                                            $bindInfo = $houseVillageSignService->getFloorInfo($whereFloor, 'floor_name as bind_name');
                                            if ($bindInfo && !is_array($bindInfo)) {
                                                $bindInfo = $bindInfo->toArray();
                                            }
                                            break;
                                        case DeviceConst::DH_TO_CLOUD_ROOM:
                                            $houseVillageSignService = new HouseVillageSingleService();
                                            $field = 'room as bind_name,village_id,single_id, layer_id,pigcms_id,floor_id';
                                            $whereRoom = [
                                                'pigcms_id' => $aboutInfo['bind_id'],
                                                'is_del'   => 0
                                            ];
                                            $bindInfo = $houseVillageSignService->getOneRoom($whereRoom, $field);
                                            if ($bindInfo && !is_array($bindInfo)) {
                                                $bindInfo = $bindInfo->toArray();
                                            }
                                            if (isset($bindInfo['pigcms_id'])) {
                                                $houseVillageService = new HouseVillageService();
                                                $address = $houseVillageService->getSingleFloorRoom($bindInfo['single_id'], $bindInfo['floor_id'], $bindInfo['layer_id'], $bindInfo['pigcms_id'], $bindInfo['village_id']);
                                                if ($address) {
                                                    $bindInfo['bind_name'] = $address;
                                                }
                                            }
                                            break;
                                    }
                                    $item['sysModelName'] = isset($bindInfo['bind_name']) ? $bindInfo['bind_name'] : $aboutInfo['bind_name'];
                                    $item['sysModel']     = isset($this->operateModeTypeArr[$aboutInfo['operateModeType']]) ? $this->operateModeTypeArr[$aboutInfo['operateModeType']] : '自动匹配同步';
                                    if (isset($aboutInfo['operateModeType']) && $aboutInfo['operateModeType'] == DahuaConst::OPERATE_AUTO_SYN_DATA) {
                                        $item['auto_syn'] = '1';
                                    }
                                    $item['isSyn']          = 1;
                                    $item['bind_id']         = $aboutInfo['bind_id'];
                                }
                            }
                        }
                    }
                }
                break;
            default:
                return $this->backData($param, '协议类型不存在', 104);
        }
        return $result;
    }

    /**
     * 获取平台楼栋
     * @param string $village_id
     * @param string $orgType
     * @param string|integer $parent_bind_id
     * @return array|\think\Model|null
     */
    public function bindDHVillageSingleList(string $village_id, string $orgType, $parent_bind_id=0) {
        $houseVillageSignService = new HouseVillageSingleService();

        switch ($orgType) {
            case 10:
                $bind_type = DeviceConst::DH_TO_CLOUD_BUILD;
                $where = [];
                $where[] = ['village_id','=',$village_id];
                $where[] = ['is_public_rental','=',0];
                $where[] = ['status','<>',4];
                $field = "id,single_name as name,single_name, status,single_number,village_id";
                $oderBy = ['sort'=>'DESC','id'=>'DESC'];
                $list = $houseVillageSignService->getList($where,$field,$oderBy);
                if ($list && !is_array($list)) {
                    $list = $list->toArray();
                }
                break;
            case 11:
                $bind_type = DeviceConst::DH_TO_CLOUD_UNIT;
                $where[] = ['village_id','=',$village_id];
                $where[] = ['single_id','=',$parent_bind_id];
                $where[] = ['is_public_rental','=',0];
                $where[] = ['status','<>',4];
                $field = "floor_id as id,floor_id, floor_name as name,floor_name, status,floor_number,village_id";
                $houseVillageSignService = new HouseVillageSingleService();
                $list = $houseVillageSignService->get_floor_list($where, $field);
                if ($list && !is_array($list)) {
                    $list = $list->toArray();
                }
                break;
            case 12:
                $bind_type = DeviceConst::DH_TO_CLOUD_ROOM;
                $db_layer = new HouseVillageLayer();
                $whereLayer = [];
                $whereLayer[] = ['village_id', '=', $village_id];
                $whereLayer[] = ['floor_id', '=', $parent_bind_id];
                $whereLayer[] = ['status','<>',4];
                $layerInfoArrId = $db_layer->getOneColumn($whereLayer,'id,layer_name','id');
                
                $where[] = ['village_id','=',$village_id];
                $where[] = ['floor_id','=',$parent_bind_id];
                $where[] = ['is_public_rental','=',0];
                $where[] = ['status','<>',4];
                $field = "pigcms_id as id,pigcms_id, room as name,room, status,room_number,village_id,layer_id";
                $house_village_user_vacancy = new HouseVillageUserVacancy();
                $oderBy = ['sort'=>'DESC','pigcms_id'=>'DESC'];
                $list = $house_village_user_vacancy->getRoomList($where,$field, $oderBy);
                if ($list && !is_array($list)) {
                    $list = $list->toArray();
                }
                break;
        }
        if (!empty($list) && isset($bind_type)) {
            foreach ($list as $key=>&$item) {
                $aboutInfo  = $this->commonFilterAboutToData($bind_type, $item['id'], DeviceConst::DH_YUNRUI);
                if (isset($item['layer_id']) && isset($layerInfoArrId[$item['layer_id']])) {
                    $layer_name = $this->traitAutoFixLoucengTips($layerInfoArrId[$item['layer_id']]['layer_name'],true);
                    $item['name'] = $layer_name . $item['name'];
                }
                if ($orgType && isset($aboutInfo['bind_name'])) {
                    unset($list[$key]);
                }
            }
            $list = array_values($list);
        } else {
            $list = [];
        }
        return $list;
    }

    /**
     * 手动选中绑定楼栋单元楼层房屋关系
     * @param array $bindId 对应绑定的id 本地的
     * @param array $bindParam 相关一些绑定信息 额外参数
     * @param array $orgParam 大华平台获取的单个信息的所有均传过来
     * @return array
     * @throws \think\Exception
     */
    public function bindDHBuildUnitRoom(string $bindId, array $bindParam = [],array $orgParam = [])
    {
        if (!$bindId) {
            return $this->backData($orgParam, '绑定对象不存在', 1018);
        }
        /** @var int village_id 小区id */
        $village_id  = isset($bindParam['village_id']) && $bindParam['village_id']        ? $bindParam['village_id']      : 0;
        
        $relatedType = isset($bindParam['relatedType']) && $bindParam['relatedType']      ? $bindParam['relatedType'] : '';
        if (!$relatedType && isset($bindParam['room_id']) && $bindParam['room_id']) {
            $relatedType = 'room';
            $bind_id     = $bindParam['room_id'];
        } elseif (!$relatedType && isset($bindParam['layer_id']) && $bindParam['layer_id']) {
            $relatedType = 'layer';
            $bind_id     = $bindParam['layer_id'];
        } elseif (!$relatedType && isset($bindParam['floor_id']) && $bindParam['floor_id']) {
            $relatedType = 'unit';
            $bind_id     = $bindParam['floor_id'];
        } elseif (!$relatedType && isset($bindParam['single_id']) && $bindParam['single_id']) {
            $relatedType = 'build';
            $bind_id     = $bindParam['single_id'];
        }
        /** @var integer third_protocol 协议 */
        $third_protocol = isset($orgParam['third_protocol'])&&$orgParam['third_protocol'] ? $orgParam['third_protocol']: DeviceConst::DH_YUNRUI;
        /** @var string communityCode 云睿平台场所唯一编码 */
        $communityCode  = isset($orgParam['communityCode']) && $orgParam['communityCode'] ? $orgParam['communityCode'] : '';
        /** @var string buildingCode 楼栋编码 */
        $buildingCode   = isset($orgParam['buildingCode']) && $orgParam['buildingCode']   ? $orgParam['buildingCode']  : '';
        /** @var string unitCode 单元编码 */
        $unitCode       = isset($orgParam['unitCode']) && $orgParam['unitCode']           ? $orgParam['unitCode']      : '';
        /** @var string villageCode 场所的组织编码 */
        $villageCode    = isset($orgParam['villageCode']) && $orgParam['villageCode']     ? $orgParam['villageCode']   : '';
        /** @var string id 楼栋/单元/房屋的分布式id */
        $id             = isset($orgParam['id']) && $orgParam['id']                       ? $orgParam['id']            : '';
        /** @var string orgCode 当前节点编码，如查的是房屋，就是房屋的编码 */
        $orgCode        = isset($orgParam['orgCode']) && $orgParam['orgCode']             ? $orgParam['orgCode']       : '';
        /** @var string orgName 当前节点名称，如查的是房屋，就是房屋的名称 */
        $orgName        = isset($orgParam['orgName']) && $orgParam['orgName']             ? $orgParam['orgName']       : '';
        /** @var integer orgType 1-组织、2-场所、10-楼栋、11-单元、12-房屋 */
        $orgType        = isset($orgParam['orgType']) && $orgParam['orgType']             ? $orgParam['orgType']       : '';
        /** @var integer pOrgCode 上一级编码，如查的是房间，这个就是单元的编码 */
        $pOrgCode       = isset($orgParam['pOrgCode']) && $orgParam['pOrgCode']           ? $orgParam['pOrgCode']      : '';
        
        $face_bind_about_info = new FaceBindAboutInfo();
        $nowTime = time();
        if (!$relatedType) {
            switch ($relatedType) {
                case 'room':
                    if (!isset($bind_id)) {
                        $bind_id = $bindId;
                    }
                    $bind_type = DeviceConst::DH_TO_CLOUD_ROOM;
                    break;
                case 'layer':
                    if (!isset($bind_id)) {
                        $bind_id = $bindId;
                    }
                    $bind_type = DeviceConst::DH_TO_CLOUD_FLOOR;
                    break;
                case 'unit':
                    if (!isset($bind_id)) {
                        $bind_id = $bindId;
                    }
                    $bind_type = DeviceConst::DH_TO_CLOUD_UNIT;
                    break;
                case 'build':
                    if (!isset($bind_id)) {
                        $bind_id = $bindId;
                    }
                    $bind_type = DeviceConst::DH_TO_CLOUD_BUILD;
                    break;
                default:
                    return $this->backData($orgParam, '绑定类型不存在', 1018);
            }
        } else {
            switch ($orgType) {
                case 12:
                    if (!isset($bind_id)) {
                        $bind_id = $bindId;
                    }
                    $bind_type = DeviceConst::DH_TO_CLOUD_ROOM;
                    break;
                case 11:
                    if (!isset($bind_id)) {
                        $bind_id = $bindId;
                    }
                    $bind_type = DeviceConst::DH_TO_CLOUD_UNIT;
                    break;
                case 10:
                    if (!isset($bind_id)) {
                        $bind_id = $bindId;
                    }
                    $bind_type = DeviceConst::DH_TO_CLOUD_BUILD;
                    break;
                default:
                    return $this->backData($orgParam, '绑定类型不存在', 1018);
            }
        }
        $aboutParam = [
            'bind_type'        => $bind_type,
            'bind_id'          => $bind_id,
            'third_protocol'   => $third_protocol,
        ];
        $whereAbout = [
            'bind_type'        => $bind_type,
            'bind_id'          => $bind_id,
            'third_protocol'   => $third_protocol,
        ];
        if ($orgName) {
            $aboutParam['bind_name'] = $orgName;
        }
        if ($orgCode) {
            $aboutParam['third_id'] = $orgCode;
        }
        if ($pOrgCode) {
            $aboutParam['group_id'] = $pOrgCode;
        }
        if ($orgParam) {
            $aboutParam['third_info'] = json_encode($orgParam, JSON_UNESCAPED_UNICODE);
        }
        if ($id) {
            $aboutParam['bind_number'] = $id;
        }
        if (isset($bindParam['operateModeType']) && $bindParam['operateModeType']) {
            $aboutParam['operateModeType'] = $bindParam['operateModeType'];
        } else {
            $aboutParam['operateModeType'] = DahuaConst::OPERATE_HAND_SYN_DATA;
        }
        $aboutInfo = $face_bind_about_info->getOne($whereAbout, 'id');
        if ($aboutInfo && !is_array($aboutInfo)) {
            $aboutInfo = $aboutInfo->toArray();
        } elseif (!$aboutInfo) {
            $aboutInfo = [];
        }
        if ($aboutInfo && isset($aboutInfo['id'])) {
            if (isset($orgParam['auto_syn']) && intval($orgParam['auto_syn'])==1) {
                if (!isset($orgParam['bind_id']) && !$orgParam['bind_id']) {
                    $orgParam['bind_id'] = $bind_id;
                }
                $orgParam['village_id']  = $village_id;
                $auto = $this->filterAutoDHBuildDataBind($orgParam);
                if (isset($auto['code']) && $auto['code']>0 && $auto['msg']) {
                    return $this->backData($orgParam, $auto['msg'], $auto['code']);
                }
                return $orgParam;
            } else {
                return $this->backData($orgParam, '已经绑定过了', 1013);
            }
        }
        if ($aboutInfo && isset($aboutInfo['id'])) {
            $aboutParam['last_time'] = $nowTime;
            $save = $face_bind_about_info->updateThis($whereAbout, $aboutParam);
            if ($save === false) {
                return $this->backData($orgParam, '绑定信息更新失败', 1013);
            }
        } else {
            $aboutParam['add_time'] = $nowTime;
            $aboutId = $face_bind_about_info->add($aboutParam);
            if (!$aboutId) {
                return $this->backData($orgParam, '绑定信息失败', 1013);
            }
        }
        if (isset($orgParam['auto_syn']) && intval($orgParam['auto_syn'])==1) {
            $orgParam['bind_id']    = $bind_id;
            $orgParam['village_id'] = $village_id;
            $auto = $this->filterAutoDHBuildDataBind($orgParam);
            if (isset($auto['code']) && $auto['code']>0 && $auto['msg']) {
                return $this->backData($orgParam, $auto['msg'], $auto['code']);
            }
        }
        return $orgParam;
    }

    /**
     * 大华云睿自动按照编号匹配楼栋单元楼层房屋
     * @param array $orgParam
     * @return array|bool
     * @throws \think\Exception
     */
    public function autoDHBuildDataBind(array $orgParam) {
        if (!isset($orgParam['auto_syn']) || intval($orgParam['auto_syn']) != 1) {
            return false;
        }
        $bind_id    = isset($orgParam['bind_id'])    && $orgParam['bind_id']    ? $orgParam['bind_id']    : 0;
        $orgType    = isset($orgParam['orgType'])    && $orgParam['orgType']    ? $orgParam['orgType']    : 0;
        $pOrgCode   = isset($orgParam['orgCode'])    && $orgParam['orgCode']    ? $orgParam['orgCode']    : '';
        $village_id = isset($orgParam['village_id']) && $orgParam['village_id'] ? $orgParam['village_id'] : 0;
        $pageNum    = isset($orgParam['pageNum'])    && $orgParam['pageNum']    ? $orgParam['pageNum']    : 1;
        $pageSize   = isset($orgParam['pageSize'])   && $orgParam['pageSize']   ? $orgParam['pageSize']   : 100;
        if (!$bind_id || !$orgType || !$pOrgCode || !$village_id) {
            return false;
        }
        $face_bind_about_info = new FaceBindAboutInfo();
        switch ($orgType) {
            case 10:
                $bind_type = DeviceConst::DH_TO_CLOUD_UNIT;
                $orgTypeChild   = 11;
                $whereFloor   = [];
                $whereFloor[] = ['village_id','=',$village_id];
                $whereFloor[] = ['single_id','=',$bind_id];
                $whereFloor[] = ['is_public_rental','=',0];
                $whereFloor[] = ['status','<>',4];
                $db_floor  = new HouseVillageFloor();
                $floorIds = $db_floor->getOneColumn($whereFloor, 'floor_id');
                $whereAbout   = [];
                $whereAbout[] = ['bind_type', '=', $bind_type];
                $whereAbout[] = ['bind_id', 'in', $floorIds];
                $whereAbout[] = ['third_protocol', '=', DeviceConst::DH_YUNRUI];
                $aboutInfoArr = $face_bind_about_info->getOneColumn($whereAbout, 'bind_type, bind_id, third_id, bind_number','third_id');
                break;
            case 11:
                $bind_type = DeviceConst::DH_TO_CLOUD_ROOM;
                $orgTypeChild   = 12;
                $whereRoom   = [];
                $whereRoom[] = ['village_id','=',$village_id];
                $whereRoom[] = ['floor_id','=',$bind_id];
                $whereRoom[] = ['is_public_rental','=',0];
                $whereRoom[] = ['status','<>',4];
                $db_room   = new HouseVillageUserVacancy();
                $roomIds = $db_room->getColumn($whereRoom, 'pigcms_id');
                $whereAbout   = [];
                $whereAbout[] = ['bind_type', '=', $bind_type];
                $whereAbout[] = ['bind_id', 'in', $roomIds];
                $whereAbout[] = ['third_protocol', '=', DeviceConst::DH_YUNRUI];
                $aboutInfoArr = $face_bind_about_info->getOneColumn($whereAbout, 'bind_type, bind_id, third_id, bind_number','third_id');

                $roomInfos = $db_room->getColumn($whereRoom, 'pigcms_id,village_id,single_id,floor_id,layer_id,room_number,room','pigcms_id');
                
                $db_layer  = new HouseVillageLayer();
                $whereLayer   = [];
                $whereLayer[] = ['village_id','=',$village_id];
                $whereLayer[] = ['floor_id','=',$bind_id];
                $whereLayer[] = ['is_public_rental','=',0];
                $whereLayer[] = ['status','<>',4];
                $layerInfos = $db_layer->getOneColumn($whereRoom, 'id,layer_name,layer_number','id');
                $roomLayers = [];
                if (!empty($roomInfos)) {
                    foreach ($roomInfos as $room) {
                        if (isset($layerInfos[$room['layer_id']]) && isset($room['room_number']) && $room['room_number'] && strlen($room['room_number']) < 3) {
                            $room_number = $layerInfos[$room['layer_id']] . $room['room_number'];
                        } elseif ($room['room_number']) {
                            $room_number = $room['room_number'];
                        } else {
                            continue;
                        }
                        $room_number = str_pad($room_number, 4, "0", STR_PAD_LEFT);
                        $roomLayers[$room_number] = $room;
                    }
                }
                break;
            case 12:
                return $this->backData($orgParam, '房屋下暂时无同步动作', 1010);
            default:
                return $this->backData($orgParam, '类型不存在', 1018);
        }

        $params = [
            'pageNum'       => $pageNum,
            'pageSize'      => $pageSize,
            'orgType'       => $orgTypeChild,
            'pOrgCode'      => $pOrgCode,
            'village_id'    => $village_id,
            'thirdProtocol' => DeviceConst::DH_YUNRUI,
        ];
        $arr = $this->getDhBuidingUnitRoomList($params);
        if (isset($arr['data']['pageData']) && $arr['data']['pageData']) {
            foreach ($arr['data']['pageData'] as $val) {
                if (isset($aboutInfoArr) && isset($aboutInfoArr[$val['orgCode']]) && $aboutInfoArr[$val['orgCode']]) {
                    $orgParam = $val;
                    $orgParam['bind_id']    = $aboutInfoArr[$val['orgCode']]['bind_id'];
                    $orgParam['village_id'] = $village_id;
                    $orgParam['auto_syn']   = '1';
                    $this->filterAutoDHBuildDataBind($orgParam);
                    continue;
                }
                switch ($val['orgType']) {
                    case 11:
                        $orgName    = $val['orgName'];
                        $floorName0 = $orgName;
                        $floorName1 = str_replace('单元', '', $val['orgName']);
                        $floorName2 = str_pad($floorName1, 2, "0", STR_PAD_LEFT);
                        $floorName3 = $floorName2.'单元';
                        $where   = [];
                        $where[] = ['village_id','=',$village_id];
                        $where[] = ['single_id','=',$bind_id];
                        $where[] = ['is_public_rental','=',0];
                        $where[] = ['status','<>',4];
                        $where[] = ['floor_name','in',[$floorName0,$floorName1,$floorName2,$floorName3]];
                        $houseVillageSignService = new HouseVillageSingleService();
                        $floorInfo = $houseVillageSignService->getFloorInfo($where,'floor_id');
                        if ($floorInfo && !is_array($floorInfo)) {
                            $floorInfo = $floorInfo->toArray();
                        }
                        if (isset($floorInfo['floor_id'])) {
                            $orgParam = $val;
                            $orgParam['auto_syn'] = '1';
                            $bindParam = [
                                'village_id'      => $village_id,
                                'relatedType'     => 'unit',
                                'operateModeType' => DahuaConst::OPERATE_AUTO_SYN_DATA,
                            ];
                            $bindId = $floorInfo['floor_id'];
                            $this->filterBindDHBuildUnitRoom($bindId, $bindParam, $orgParam);
                        }
                        break;
                    case 12:
                        $orgName    = $val['orgName'];
                        if (isset($roomLayers) && isset($roomLayers[$orgName]) && isset($roomLayers[$orgName]['pigcms_id'])) {
                            $orgParam = $val;
                            $orgParam['auto_syn'] = '1';
                            $bindParam = [
                                'village_id'      => $village_id,
                                'relatedType'     => 'room',
                                'operateModeType' => DahuaConst::OPERATE_AUTO_SYN_DATA,
                            ];
                            $bindId = $roomLayers[$orgName]['pigcms_id'];
                            $this->filterBindDHBuildUnitRoom($bindId, $bindParam, $orgParam);
                        }
                        break;
                }
            }
        }
        if (isset($arr['data']['totalPage']) && $arr['data']['totalPage'] && intval($arr['data']['totalPage']) > $pageNum) {
            $orgParam['pageNum']       = $pageNum + 1;
            $orgParam['thirdProtocol'] = DeviceConst::DH_YUNRUI;
            $orgParam['jobType']       = 'autoDHBuildDataBind';
            $this->traitCommonDHCloudBuildings($orgParam);
        }
        return true;
    }

    /**
     * 楼栋删除（单元房屋一并删除）
     * @param string $singleId 楼栋id
     * @param string $orgCode 楼栋编号
     */
    public function deleteDHBuildUnitRoom(string $singleId, $orgCode = '') {
        
    }
    
    /**
     * 添加设备同步到对应设备云
     * @param array $param 传参数组 具体字段释义看代码注释
     * @return array|\think\Model
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addDeviceToCloud(array $param)
    {
        if (!isset($param['orderGroupId']) || $param['orderGroupType']) {
            $param['orderGroupType'] = 'add_device_to_cloud';
        }
        $param = $this->filterCommonParam($param);
        $nowTime = $this->nowTime ? $this->nowTime : time();
        $this->clearRecordDeviceBindFilter();
        
        if ($this->device_id || $this->device_sn) {
            $infoArr = $this->handleParamDeviceInfo($param);
            if (isset($infoArr['code']) && $infoArr['code']) {
                $this->clearRecordDeviceBindFilter();
                $this->syn_status        = DeviceConst::BINDS_SYN_DEVICE_ERR;
                $this->syn_status_txt    = "同步设备失败";
                $this->err_reason        = "同步设备失败({$infoArr['msg']})";
                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                $this->recordDeviceBindFilterBox($param);
                return $infoArr;
            }
        }
        if (isset($infoArr) && isset($infoArr['deviceInfo']) && $infoArr['deviceInfo']) {
            $deviceInfo       = $infoArr['deviceInfo'];
        } else {
            $deviceInfo       = [];
        }
        if (empty($deviceInfo)) {
            $this->clearRecordDeviceBindFilter();
            $this->syn_status        = DeviceConst::BINDS_SYN_DEVICE_ERR;
            $this->syn_status_txt    = "同步设备失败(设备信息不存在)";
            $this->err_reason        = "同步设备失败(设备信息不存在)[缺少设备id或者序列号]";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
            $this->recordDeviceBindFilterBox($param);
            return $this->backData($param, '设备信息不存在', 1001);
        }
        
        // 记录
        $this->syn_status        = DeviceConst::BINDS_SYN_DEVICE_START;
        $this->syn_status_txt    = "同步设备开始";
        $this->err_reason        = "";
        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param, 'nowTime' => $nowTime];
        $this->recordDeviceBindFilterBox($param);
        $this->step_num++;
        
        /** @var string parent_third_id 同步的父级id或者编号 */
        $parent_third_id = isset($param['parent_third_id']) && $param['parent_third_id'] ? $param['parent_third_id'] : '';
        
        if (!$this->thirdProtocol) {
            // todo 可以考虑依据查询设备 然后取值
            // 必须要有协议确没有传参  返回报错
            $this->syn_status        = DeviceConst::BINDS_SYN_DEVICE_ERR;
            $this->syn_status_txt    = "同步设备失败";
            $this->err_reason        = "同步设备失败(协议信息不全)";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
            $this->recordDeviceBindFilterBox($param);
            if ($this->device_equipment_type == DeviceConst::DEVICE_TYPE_ALARM) {
                $alarmDeviceService = new AlarmDeviceService();
                $whereAlarmUpdate = [];
                $whereAlarmUpdate[] = ['village_id', '=', $this->village_id];
                if ($this->device_id) {
                    $whereAlarmUpdate[] = ['device_id', '=', $this->device_id];
                } elseif ($this->device_sn) {
                    $whereAlarmUpdate[] = ['device_serial', '=', $this->device_sn];
                }
                $whereAlarmUpdate[] = ['is_del', '=', 0];
                $param1s = ['cloud_status' => 3, 'cloud_reason' => "同步设备失败(协议信息不全)", 'update_time' => time()];
                $alarmDeviceService->updateAlarmDevice($whereAlarmUpdate, $param1s);
            }
            return $this->backData($param, '协议信息不全', 1003);
        } else {
            // 有协议
            $getLive = false;
            switch ($this->thirdProtocol) {
                case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                    // todo 海康
                    /** @var string deviceId 设备序列号 */
                    $deviceId = isset($param['deviceId']) && $param['deviceId'] ? $param['deviceId'] : '';
                    if (!$deviceId && $this->device_sn) {
                        $deviceId = $this->device_sn;
                    }
                    if (!isset($param['deviceId']) || !$param['deviceId']) {
                        $param['deviceId'] = $deviceId;
                    }
                    if (isset($param['village_third_id']) && $param['village_third_id'] && (!isset($param['communityId']) || !$param['communityId'])) {
                        $param['communityId'] = $param['village_third_id'];
                    }
                    if (!isset($param['communityId']) || !$param['communityId']) {
                        $this->filterVillageToData();
                        $param['communityId'] = $this->village_third_id;
                    }
                    if ((!isset($param['communityId']) || !$param['communityId']) && $parent_third_id) {
                        $param['communityId'] = $parent_third_id;
                    } elseif (isset($param['third_id']) && $param['third_id']) {
                        $param['communityId'] = $param['third_id'];
                    }
                    if (isset($param['village_third_id']) && $param['village_third_id'] && $param['village_third_id'] != $param['communityId']) {
                        $param['communityId'] = $param['village_third_id'];
                    }
                    if ($this->device_equipment_type == DeviceConst::DEVICE_TYPE_ALARM) {
                        if (isset($deviceInfo['single_id']) && intval($deviceInfo['single_id']) > 0) {
                            $this->filterBuildToData($deviceInfo['single_id']);
                            if ($this->build_third_id) {
                                $param['buildingId'] = $this->build_third_id;
                            }
                        }
                        if (isset($deviceInfo['floor_id']) && intval($deviceInfo['floor_id']) > 0) {
                            $this->filterUnitToData($deviceInfo['floor_id']);
                            if ($this->unit_third_id) {
                                $param['unitId'] = $this->unit_third_id;
                            }
                        }
                    } elseif (isset($deviceInfo['floor_id']) && $deviceInfo['floor_id']!=-1 && $deviceInfo['floor_id']) {
                        $floorIds = explode(',', $deviceInfo['floor_id']);
                        if (count($floorIds)==1 && $floorIds[0]) {
                            $db_floor  = new HouseVillageFloor();
                            $whereFloor = [];
                            $whereFloor[] = ['floor_id', '=', $floorIds[0]];
                            $floor_info = $db_floor->getOne($whereFloor,'floor_id,single_id');
                            if (isset($floor_info['single_id'])) {
                                $this->filterBuildToData($floor_info['single_id']);
                                if ($this->build_third_id) {
                                    $param['buildingId'] = $this->build_third_id;
                                }
                                $this->filterUnitToData($floor_info['floor_id']);
                                if ($this->unit_third_id) {
                                    $param['unitId'] = $this->unit_third_id;
                                }
                            }
                        }
                    }
                    $param['isSave'] = true;
                    $addResult = (new FaceHikCloudNeiBuDeviceService())->addDeviceToCloud($deviceId, $this->device_equipment_type, $param, $deviceInfo);
                    fdump_api(['addResult'=>$addResult,$deviceId, $this->device_equipment_type, $param, $deviceInfo], '00addResult',1);
                    $success = true;
                    if (isset($addResult['data'])&& isset($addResult['data']['deviceId'])) {
                        if ($this->device_equipment_type == DeviceConst::DEVICE_TYPE_CAMERA) {
                            // todo 添加人脸编辑成功记录下来
                            $this->recordCameraInfo($addResult['data']['deviceId'], $this->device_id, $this->device_sn, $this->thirdProtocol, $param, $addResult);
                        } else {
                            $addResult['cloud_device_id']   = $addResult['data']['deviceId'];
                            $addResult['cloud_device_name'] = isset($addResult['data']['third_name']) ? $addResult['data']['third_name'] : '';
                            $this->recordFaceDevice($param, $addResult);
                        }
                        $this->third_deviceId    = $addResult['data']['deviceId'];
                    } elseif (isset($addResult['code']) &&  $addResult['code']==HikConst::HIK_EXISTENT_DEVICE) {
                        if ($deviceInfo && isset($deviceInfo['cloud_device_id']) && $deviceInfo['cloud_device_id']) {
                            $addResult['data']['deviceId'] = $addResult['deviceId'] = $deviceInfo['cloud_device_id'];
                        } else {
                            $success = false;
                            $this->err_reason        = "同步设备失败【{$addResult['message']}】[{$addResult['code']}]";
                        }
                    } elseif (isset($addResult['code']) &&  $addResult['code']==HikConst::HIK_HIGH_RISK_TO_CONFIRMATION) {
                        // 设备存在高风险需要确权
                        // todo 走确权逻辑
                        $this->syn_status        = DeviceConst::DEVICE_AUTO_CONFIRM_START;
                        $this->syn_status_txt    = "设备自动确权开始";
                        $this->err_reason        = "";
                        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'addResult' => $addResult];
                        $this->recordDeviceBindFilterBox($param);
                        $this->step_num++;
                        switch ($this->device_equipment_type) {
                            case DeviceConst::DEVICE_TYPE_FACE:
                                $updateParam = ['device_status' => 5,];
                                break;
                            case DeviceConst::DEVICE_TYPE_CAMERA:
                                $updateParam = ['camera_status' => 5,];
                                break;
                        }
                        if (isset($updateParam)) {
                            $this->updateDeviceInfo($updateParam);
                        }
                        $this->hikAutoConfirm($deviceId, $param);
                        return $param;
                    } elseif (isset($addResult['code']) &&  $addResult['code']!='200' && $addResult['code'] && isset($addResult['message'])) {
                        $success = false;
                        $this->err_reason        = "同步设备失败【{$addResult['message']}】[{$addResult['code']}]";
                    }
                    if ($success) {
                        $this->syn_status        = DeviceConst::BINDS_SYN_DEVICE_SUCCESS;
                        $this->syn_status_txt    = "同步设备成功";
                        $this->err_reason        = "";
                        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'addResult' => $addResult];
                        $this->recordDeviceBindFilterBox($param);
                        $this->step_num++;
                        
                        /** @var   string validateCode 设备验证码 */
                        $validateCode = isset($addResult['validateCode']) ? trim($addResult['validateCode']) : '';
                        if (!isset($param['validateCode']) || !$param['validateCode']) {
                            $param['validateCode'] = $validateCode;
                        }
                        /** @var   string hkDeviceId 海康设备id */
                        $hkDeviceId = isset($addResult['deviceId']) ? $addResult['deviceId'] : '';
                        if (!isset($param['hkDeviceId']) || !$param['hkDeviceId']) {
                            $param['hkDeviceId'] = $hkDeviceId;
                        }
                        // todo 直接获取通道相关信息进行处理
                        if ($param['hkDeviceId']) {
                            $channelIdArr = $this->pullDeviceChannels($hkDeviceId, $param);
                            $param['channelIdArr'] = $channelIdArr;
                            $getLive = true;
                        }
                    } else {
                        if ($this->device_equipment_type == DeviceConst::DEVICE_TYPE_ALARM) {
                            $alarmDeviceService = new AlarmDeviceService();
                            $whereAlarmUpdate = [];
                            $whereAlarmUpdate[] = ['village_id', '=', $this->village_id];
                            if ($this->device_id) {
                                $whereAlarmUpdate[] = ['device_id', '=', $this->device_id];
                            } elseif ($this->device_sn) {
                                $whereAlarmUpdate[] = ['device_serial', '=', $this->device_sn];
                            }
                            $whereAlarmUpdate[] = ['is_del', '=', 0];
                            $param1s = ['cloud_status' => 3, 'cloud_reason' => $this->err_reason, 'update_time' => time()];
                            $alarmDeviceService->updateAlarmDevice($whereAlarmUpdate, $param1s);
                        }
                        $this->syn_status        = DeviceConst::BINDS_SYN_DEVICE_FAIL;
                        $this->syn_status_txt    = "同步设备失败";
                        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param, 'deviceInfo' => $deviceInfo,  'deviceId' => $deviceId, 'device_equipment_type' => $this->device_equipment_type];
                        $this->recordDeviceBindFilterBox($param);
                        $this->step_num++;
                    }
                    break;
                case DeviceConst::DH_YUNRUI:
                    /** @var string deviceId 设备序列号 */
                    $deviceId = isset($param['deviceId']) && $param['deviceId'] ? $param['deviceId'] : '';
                    if (!$deviceId && $this->device_sn) {
                        $deviceId = $this->device_sn;
                    }
                    if (!isset($param['storeId']) || !$param['storeId']) {
                        $param['storeId'] = $parent_third_id;
                    }
                    $addResult = (new FaceDHYunRuiCloudDeviceService())->addDeviceToCloud($deviceId, $this->device_equipment_type, $param, $deviceInfo);
                    if (isset($addResult['success']) && $addResult['success']) {
                        $this->syn_status        = DeviceConst::BINDS_SYN_DEVICE_SUCCESS;
                        $this->syn_status_txt    = "同步设备成功";
                        $this->err_reason        = "";
                        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'addResult' => $addResult];
                        $this->recordDeviceBindFilterBox($param);
                        $this->step_num++;
                        if ($this->device_equipment_type == DeviceConst::DEVICE_TYPE_CAMERA) {
                            $addResult['storeId'] = $param['storeId'];
                            // todo 监控添加编辑成功记录下来
                            $this->recordCameraInfo($deviceId, $this->device_id, $this->device_sn, $this->thirdProtocol, $param, $addResult);
                            // todo 直接获取通道相关信息进行处理
                            if ($deviceId) {
                                $channelIdArr = $this->pullDeviceChannels($deviceId, $param);
                                $param['channelIdArr'] = $channelIdArr;
                            }
                            $getLive = true;
                        }
                        if ($this->device_equipment_type == DeviceConst::DEVICE_TYPE_FACE || $this->device_equipment_type == DeviceConst::DEVICE_TYPE_FINGERPRINT) {
                            $timePlanResult = $this->addDhTimePlan($this->device_id, $param);
                            if (isset($timePlanResult['timePlanIndex']) && $timePlanResult['timePlanIndex'] == -1) {
                                if (isset($timePlanResult['planTimes']['errMsg'])) {
                                    $errMsg = $timePlanResult['planTimes']['errMsg'];
                                } else {
                                    $errMsg = '';
                                }
                                $this->syn_status        = DeviceConst::BINDS_SYN_PLAN_FAIL;
                                $this->syn_status_txt    = "同步开门计划失败";
                                $this->err_reason        = "同步开门计划失败({$errMsg})";
                                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'device_id' => $this->device_id, 'timePlanResult' => $timePlanResult];
                                $this->recordDeviceBindFilterBox($param);
                                $this->step_num++;
                            } else {
                                $this->syn_status        = DeviceConst::BINDS_SYN_PLAN_SUCCESS;
                                $this->syn_status_txt    = "同步开门计划成功";
                                $this->err_reason        = "";
                                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'device_id' => $this->device_id, 'timePlanResult' => $timePlanResult];
                                $this->recordDeviceBindFilterBox($param);
                                $this->step_num++;
                            }
                            $addResult['cloud_device_id'] = $deviceId;
                            $addResult['cloud_group_id']  = isset($timePlanResult['timePlanIndex']) ? $timePlanResult['timePlanIndex'] : -1;
                            $this->recordFaceDevice($param, $addResult);
                        }
                        if ($this->room_id && $this->device_equipment_type == DeviceConst::DEVICE_TYPE_FINGERPRINT) {
                            // todo 如果是指纹设备 且存在房间 查询下房间下所有人员指纹 存在指纹 处理同步
                            $queueData = $param;
                            $queueData['jobType']   = 'getDhPersonFingerprintByRoom';
                            $queueData['room_id']   = $this->room_id;
                            $queueData['isAddAuth'] = 1;
                            $this->traitCommonDHToJob($queueData);
                        }
                    } else {
                        $errMsg = '';
                        $code = '';
                        if (isset($addResult['errMsg']) && $addResult['errMsg']) {
                            $errMsg = $addResult['errMsg'];
                        } elseif(isset($addResult['message']) && $addResult['message']) {
                            $errMsg = $addResult['message'];
                        }
                        if (isset($addResult['code']) && $addResult['code']) {
                            $code = $addResult['code'];
                        }
                        $this->syn_status        = DeviceConst::BINDS_SYN_DEVICE_FAIL;
                        $this->syn_status_txt    = "同步设备失败";
                        $this->err_reason        = "同步设备失败【{$errMsg}】[{$code}]";
                        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                        $this->recordDeviceBindFilterBox($param);
                        $this->step_num++;
//                        fdump_api(['errTip' => '同步设备至云错误', 'addResult' => $addResult, 'deviceId' => $deviceId, 'device_equipment_type' => $this->device_equipment_type, 'param' => $param], "deviceThirdProtocol/{$this->thirdProtocol}/errAddDeviceToCloudLog");
                    }
                    break;
            }
            if ($this->device_equipment_type == DeviceConst::DEVICE_TYPE_CAMERA && $getLive) {
                /**  执行获取下视频流 */
                $liveParam = $this->createDeviceLive($param);
                return $liveParam;
            } else {
                return $param;
            }
        }
    }


    /**
     * 拉取设备通道
     * @param string $deviceId
     * @param array $param
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function pullDeviceChannels(string $deviceId, array $param = [])
    {
        $param = $this->filterCommonParam($param);
        /** @var int nowTime 当前时间 */
        $nowTime = $this->nowTime ? $this->nowTime : time();
        // 查询下相关设备信息
        // todo 如果不进行后续楼栋 同步只是单纯同步小区 可以不传设备id和设备序号
        $device = $this->handleDeviceParams($this->device_equipment_type, $this->device_id, $this->device_sn, $this->face_device_type, $this->thirdProtocol, $this->village_id);
        if (isset($device['code']) && $device['code']) {
            return $device;
        }
        if (isset($device['device_id']) && $device['device_id']) {
            $this->device_id = $param['device_id'] = $device['device_id'];
        }
        if (isset($device['village_id']) && $device['village_id']) {
            $this->village_id = $param['village_id'] = $device['village_id'];
        }
        $channelIdArr = [];
        $step_num = $this->step_num;
        switch ($this->thirdProtocol) {
            case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                $communityId = isset($param['communityId']) ? $param['communityId'] : '';
                $channelRecord = $this->getHikDeviceChannels($communityId, $deviceId, $param);
                if (!empty($channelRecord)) {
                    $dbHouseDeviceChannel = new HouseDeviceChannel();
                    if ($this->device_equipment_type == DeviceConst::DEVICE_TYPE_ALARM) {
                        $device_type = 'house_device';
                    } else {
                        $device_type = 'house_camera_device';
                    }
                    foreach ($channelRecord as $channel) {
                        $whereRepeat = [
                            'device_type' => $device_type,
                            'channelNo'   => $channel['channelNo'],
                            'channelId'   => $channel['channelId'],
                            'isDel'       => 0,
                        ];
                        if (isset($channel['deviceId'])) {
                            $whereRepeat['deviceId'] = $channel['deviceId'];
                        }
                        $repeatChannel = $dbHouseDeviceChannel->getOne($whereRepeat);
                        $channelId     = $channel['channelId'];
                        $channelName   = $channel['channelName'];
                        $channelNo     = $channel['channelNo'];
                        $channelStatus = $channel['channelStatus'];
                        $deviceModel   = isset($channel['deviceModel']) ? $channel['deviceModel'] : '';
                        $channelType   = isset($channel['channelType']) ? $channel['channelType'] : '';
                        $isUse         = isset($channel['isUse']) ? $channel['isUse'] : 1;
                        $ipcSerial     = isset($channel['ipcSerial']) ? $channel['ipcSerial'] : '';
                        $deviceId      = isset($channel['deviceId']) ? $channel['deviceId'] : '';
                        $deviceSerial  = isset($channel['deviceSerial']) ? $channel['deviceSerial'] : '';
                        $channelPicUrl = isset($channel['channelPicUrl']) ? $channel['channelPicUrl'] : '';
                        $deviceStatus  = isset($channel['deviceStatus']) ? $channel['deviceStatus'] : '';
                        $updateParam = [
                            'device_type'   => 'house_camera_device',
                            'village_id'    => $this->village_id,
                            'device_id'     => $this->device_id,
                            'deviceSerial'  => $deviceSerial,
                            'channelId'     => $channelId,
                            'channelName'   => $channelName,
                            'channelNo'     => $channelNo,
                            'channelStatus' => $channelStatus,
                            'channelType'   => $deviceModel ? $deviceModel : $channelType,
                            'isUse'         => $isUse,
                            'ipcSerial'     => $ipcSerial,
                            'deviceId'      => $deviceId,
                            'channelPicUrl' => $channelPicUrl,
                            'channel_txt'   => json_encode($channel, JSON_UNESCAPED_UNICODE),
                        ];
                        if (isset($repeatChannel['channel_id']) && $repeatChannel['channel_id']) {
                            $updateParam['update_time'] = $nowTime;
                            $updateChannel = $dbHouseDeviceChannel->updateThis($whereRepeat, $updateParam);
                            if ($updateChannel !== false) {
                                $channel_id = $repeatChannel['channel_id'];
                            } else {
                                $channel_id = 0;
                            }
                            $channelIdArr[] = $channelId;
                        } else {
                            $addParam = $updateParam;
                            $addParam['add_time'] = $nowTime;
                            $channel_id = $dbHouseDeviceChannel->add($addParam);
                            if ($channel_id) {
                                $channelIdArr[] = $channelId;
                            }
                        }
                    }
                }
                if (!empty($channelIdArr)) {
                    // 获取通道成功
                    $this->syn_status        = DeviceConst::BINDS_SYN_DEVICE_CHANNEL_SUCCESS;
                    $this->syn_status_txt    = "同步设备通道成功";
                    $this->err_reason        = "";
                    $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                    $this->recordDeviceBindFilterBox($param);
                    $this->step_num++;
                } elseif($step_num>=$this->step_num) {
                    // 获取通道失败
                    $this->syn_status        = DeviceConst::BINDS_SYN_DEVICE_CHANNEL_FAIL;
                    $this->syn_status_txt    = "同步设备通道失败";
                    $this->err_reason        = "同步设备通道失败";
                    $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                    $this->recordDeviceBindFilterBox($param);
                    $this->step_num++;
                }
                break;
            case DeviceConst::DH_YUNRUI:
                break;
        }
        return $channelIdArr;
    }

    /**
     * 获取设备流地址
     * @param int $camera_id 监控id
     * @return array|\think\Model
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCameraUrl(int $camera_id) {
        $whereCamera       = [];
        $whereCamera[]     = ['camera_status', '<>', 4];
        $whereCamera[] = ['camera_id', '=', $camera_id];
        $service_house_face_device = new HouseFaceDeviceService();
        $cameraInfo = $service_house_face_device->getCameraInfo($whereCamera, true);
        if ($cameraInfo && !is_array($cameraInfo)) {
            $cameraInfo = $cameraInfo->toArray();
        }
        if (empty($cameraInfo)) {
            return [];
        }
        $param = [
            'deviceId'      => $cameraInfo['camera_sn'],
            'thirdProtocol' => $cameraInfo['thirdProtocol'],
            'device_sn'     => $cameraInfo['camera_sn'],
            'village_id'    => $cameraInfo['village_id'],
            'device_id'     => $cameraInfo['camera_id'],
            'hkDeviceId'    => $cameraInfo['cloud_device_id'],
            'validateCode'    => trim($cameraInfo['device_code']),
        ];
        $liveData =  $this->createDeviceLive($param, false);
        return $liveData;
    }

    /**
     * 获取设备直播流 前置判断是否为监控（或者说支持拉取视频流）
     * @param array $param
     * @param bool $cameraReturn 是否返回本地设备信息 
     * @return array|\think\Model
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createDeviceLive(array $param, bool $cameraReturn = true)
    {
        $param         = $this->filterCommonParam($param);
        /** @var int nowTime 当前时间 */
        $nowTime = $this->nowTime ? $this->nowTime : time();
        /** @var string deviceId 设备序列号 */
        $deviceId = isset($param['deviceId']) && $param['deviceId'] ? $param['deviceId'] : '';
        if (!$deviceId && $this->device_sn) {
            $deviceId = $this->device_sn;
        }
        /** @var string LiveType 监控流类型 */
        $LiveType = isset($param['LiveType']) ? trim($param['LiveType']) : 'flv';
        /** @var string validateCode 设备验证码 */
        $validateCode = isset($param['validateCode']) ? trim($param['validateCode']) : '';
//        fdump_api($param,'$paramsss');
        if (!$this->thirdProtocol) {
            // 必须要有协议确没有传参  返回报错
            $this->syn_status        = DeviceConst::DEVICE_CREATE_LIVE_FAIL;
            $this->syn_status_txt    = "获取直播流失败";
            $this->err_reason        = "获取直播流失败(协议信息不全)";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
            $this->recordDeviceBindFilterBox($param);
            return $this->backData($param, '协议信息不全', 1003);
        } else {
            // 有协议
            $liveData = [];
            $liveResult = [];
            switch ($this->thirdProtocol) {
                case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                    // todo 海康
                    // todo 按照拉流顺序进行视频拉流
                    $dbFaceHikCloudNeiBuDeviceService = new FaceHikCloudNeiBuDeviceService();
                    /** @var array setOff 关闭设备视频加密 */
                    if (isset($param['hkDeviceId']) && $param['hkDeviceId'] && $validateCode) {
                        // 关闭设备视频加密
                        $setOff = $dbFaceHikCloudNeiBuDeviceService->offDeviceVoideEncrypt($param['hkDeviceId'], $validateCode);
                        if (isset($setOff['code']) && $setOff['code']!='200' && $setOff['code'] != HikConst::HIK_ENCRYPTION_NOT_ENABLED_NOT_CLOSE) {
                            // todo 没有设备和设备验证码
                            $this->syn_status        = DeviceConst::DEVICE_CREATE_LIVE_FAIL;
                            $this->syn_status_txt    = "获取直播流失败";
                            $this->err_reason        = "获取直播流失败-关闭设备视频加密【{$setOff['message']}】[{$setOff['code']}]";
                            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__,'deviceId' => $param['hkDeviceId'], 'validateCode' => $validateCode];
                            $this->recordDeviceBindFilterBox($param);
                            return $this->backData($param, $setOff['message'], 1019);
                        }
                    } else {
                        // todo 没有设备和设备验证码
                        $this->syn_status        = DeviceConst::DEVICE_CREATE_LIVE_ERR;
                        $this->syn_status_txt    = "获取直播流失败";
                        $this->err_reason        = "获取直播流失败-关闭设备视频加密【缺少设备Id和设备验证码】";
                        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                        $this->recordDeviceBindFilterBox($param);
                        return $this->backData($param, '缺少设备Id和设备验证码', 1018);
                    }
                    $channelIdArr = isset($param['channelIdArr']) ? $param['channelIdArr'] : [];
                    $dbHouseDeviceChannel = new HouseDeviceChannel();
                    if (empty($channelIdArr)) {
                        $whereRepeat = [
                            'device_type' => 'house_camera_device',
                            'village_id'  => $this->village_id,
                            'isDel' => 0,
                        ];
                        if ($this->device_id) {
                            $whereRepeat['device_id']    = $this->device_id;
                        }
                        if (isset($deviceId)) {
                            $whereRepeat['deviceSerial'] = $deviceId;
                        }
                        $channelIdArr = $dbHouseDeviceChannel->getColumn($whereRepeat, 'channelId');
                    }
                    $hkDeviceId = isset($param['hkDeviceId']) && trim($param['hkDeviceId']) ? trim($param['hkDeviceId']) : '';
                    if (empty($channelIdArr) && $hkDeviceId) {
                        if (!isset($param['communityId']) || !$param['communityId']) {
                            $this->filterVillageToData($this->village_id, $this->thirdProtocol);
                            $param['communityId'] = $this->village_third_id;
                        }
                        $channelIdArr = $this->pullDeviceChannels($hkDeviceId, $param);
                    }
                    if (!empty($channelIdArr)) {
                        $channelIds = implode(',', $channelIdArr);
                        // 开通标准流预览功能
                        $liveVideoOpen  = $dbFaceHikCloudNeiBuDeviceService->liveVideoOpen($channelIds, $this->village_id, true, $nowTime, $deviceId);
                        // 获取标准流预览地址
                        $liveSuccess = [];
                        $liveErr     = [];
                        $liveErrMsg  = '';
                        foreach ($channelIdArr as $channelId) {
                            $liveResult = $dbFaceHikCloudNeiBuDeviceService->liveAddress($channelId, $this->village_id, true, $nowTime);
                            if (isset($liveResult['code']) && $liveResult['code']!='200') {
                                $liveErr[]     = $liveResult;
                                $liveErrMsg   .= $liveResult['message'] . "[{$liveResult['code']}];";
                            } else {
                                $liveSuccess[] = $liveResult;
                                //break;
                            }
                        }
                    }
                    if (empty($channelIdArr)) {
                        $this->syn_status        = DeviceConst::DEVICE_CREATE_LIVE_ERR;
                        $this->syn_status_txt    = "获取直播流失败";
                        $this->err_reason        = "获取直播流失败【缺少通道信息】";
                        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
                        $this->recordDeviceBindFilterBox($param);
                        $this->step_num++;
                    } elseif (empty($liveSuccess)) {
                        $this->syn_status        = DeviceConst::DEVICE_CREATE_LIVE_FAIL;
                        $this->syn_status_txt    = "获取直播流失败";
                        $this->err_reason        = "获取直播流失败【{$liveErrMsg}】";
                        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param,'channelIdArr' => $channelIdArr, 'liveErr' => $liveErr];
                        $this->recordDeviceBindFilterBox($param);
                        $this->step_num++;
                    }
                    break;
                case DeviceConst::DH_YUNRUI:
                    $deviceId = isset($param['deviceId']) && $param['deviceId'] ? $param['deviceId'] : '';
                    if (!$deviceId && $this->device_sn) {
                        $deviceId = $this->device_sn;
                    }
                    $channelId = isset($param['channelId']) && $param['channelId'] ? $param['channelId'] : '0';
                    /** @var string LiveType 监控流类型  目前支持 flv 和 rtmp */
                    $param['LiveType'] = $LiveType;
                    $liveResult = (new FaceDHYunRuiCloudDeviceService())->createDeviceLive($deviceId, $channelId, $param);
                    if (!isset($liveResult['liveUrl']) || !$liveResult['liveUrl']) {
                        $liveErrMsg = isset($liveResult['message']) && $liveResult['message'] ? $liveResult['message'] : '';
                        if (!$liveErrMsg) {
                            $liveErrMsg = isset($liveResult['errMsg']) && $liveResult['errMsg'] ? $liveResult['errMsg'] : '';
                        }
                        $this->syn_status        = DeviceConst::DEVICE_CREATE_LIVE_FAIL;
                        $this->syn_status_txt    = "获取直播流失败";
                        $this->err_reason        = "获取直播流失败【{$liveErrMsg}】";
                        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param,'deviceId' => $deviceId, 'channelId' => $channelId, 'liveResult' => $liveResult];
                        $this->recordDeviceBindFilterBox($param);
                        $this->step_num++;
                    }
                    break;
            }
            if (isset($liveResult['liveUrl']) && $liveResult['liveUrl']) {
                // todo 记录获取的视频流
                $look_url    = $liveResult['liveUrl'];
                $lookUrlType = $liveResult['liveType'];
                $service_house_face_device = new HouseFaceDeviceService();
                $whereCamera       = [];
                $whereCamera[]     = ['camera_status', '<>', 4];
                if ($this->device_id) {
                    $whereCamera[] = ['camera_id', '=', $this->device_id];
                } elseif ($this->device_sn) {
                    $whereCamera[] = ['camera_sn', '=', $this->device_sn];
                }
                $cameraInfo = $service_house_face_device->getCameraInfo($whereCamera, true);
                if ($cameraInfo && !is_array($cameraInfo)) {
                    $cameraInfo = $cameraInfo->toArray();
                }
                if ($cameraInfo && isset($cameraInfo['camera_id'])) {
                    $updateParam = [
                        'look_url' => $look_url,
                        'lookUrlType' => $lookUrlType,
                        'last_time' => time(),
                    ];
                    $service_house_face_device->saveCamera($whereCamera, $updateParam);
                    if ($cameraReturn) {
                        $liveData = $cameraInfo;
                    }
                } else {
                    // todo 查询不到暂不做处理 可以考虑添加下
                }
                $liveData['look_url'] = $look_url;
                $liveData['lookUrlType'] = $lookUrlType;
                
                $this->syn_status        = DeviceConst::DEVICE_CREATE_LIVE_SUCCESS;
                $this->syn_status_txt    = "获取直播流成功";
                $this->err_reason        = "";
                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param,'liveData' => $liveData];
                $this->recordDeviceBindFilterBox($param);
                $this->step_num++;
            }
            if (isset($liveResult['data'])) {
                $liveData['liveResult'] = $liveResult['data'];
            } else {
                $liveData['liveResult'] = [];
            }
            return $liveData;
        }
    }

    /**
     * 大华海康人脸一键同步
     * @param $device_id
     * @param $device_sn
     * @param $village_id
     * @param $thirdProtocol
     * @param $device_type
     */
    public function deviceDhAllSyn($device_id, $device_sn, $village_id, $thirdProtocol, $device_type='face') {
        $nowTime = $this->nowTime ? $this->nowTime : time();
        $orderGroupId = md5(uniqid().$nowTime);// 标记统一执行命令
        $orderGroupType = 'device_all_Syn';
        $param = [
            'device_id'           => $device_id,
            'device_sn'           => $device_sn,
            'village_id'          => $village_id,
            'thirdProtocol'       => $thirdProtocol,
            'device_type'         => $device_type,
            'orderGroupId'        => $orderGroupId,
            'orderGroupType'      => $orderGroupType,
            'deviceType'          => DeviceConst::DEVICE_TYPE_FACE
        ];
        
        $param['jobType'] = 'addPersonsToCloud';
        fdump_api(['param' => $param, 'msg' => 'deviceDhAllSyn'], 'v20Face/userSynToDevice',1);
        switch ($thirdProtocol) {
            case DeviceConst::DH_YUNRUI:
                $param['device_operate_type'] = DeviceConst::DEVICE_ALL_SYN_USERS_TO_CLOUD_AND_DEVICE;
                $job_id = $this->traitCommonDHCloudPersons($param);
                fdump_api(['line' => __LINE__, 'job_id' => $job_id, 'msg' => 'deviceDhAllSyn'], 'v20Face/userSynToDevice',1);
                break;
            case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                $job_id = $this->traitCommonHikToJob($param);
                fdump_api(['line' => __LINE__, 'job_id' => $job_id, 'msg' => 'deviceDhAllSyn'], 'v20Face/userSynToDevice',1);
                break;
        }
        
        $param = $this->filterCommonParam($param);
        if (isset($job_id)) {
            $param['job_id']     = $job_id;
        }
        $this->syn_status        = DeviceConst::DEVICE_ALL_SYN_QUEUE;
        $this->syn_status_txt    = '下发设备一键同步队列';
        $this->err_reason        = "";
        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'job_id' => $job_id, 'param' => $param];
        $this->recordDeviceBindFilterBox($param);
        return true;
    }

    /**
     * 海康一键同步.
     */
    public function deviceHkAllSyn($device_id, $device_sn, $village_id, $thirdProtocol, $device_type='face') {
        $nowTime = $this->nowTime ? $this->nowTime : time();
        $orderGroupId = md5(uniqid().$nowTime);// 标记统一执行命令
        $orderGroupType = 'device_all_Syn';
        $param = [
            'device_id'           => $device_id,
            'device_sn'           => $device_sn,
            'village_id'          => $village_id,
            'thirdProtocol'       => $thirdProtocol,
            'device_type'         => $device_type,
            'device_operate_type' => DeviceConst::DEVICE_ALL_SYN_USERS_TO_CLOUD_AND_DEVICE,
            'orderGroupId'        => $orderGroupId,
            'orderGroupType'      => $orderGroupType,
            'deviceType'          => DeviceConst::DEVICE_TYPE_FACE
        ];

        $param['jobType'] = 'addPersonsToCloud';
        $job_id = $this->traitCommonHikToJob($param);

        $param = $this->filterCommonParam($param);
        if (isset($job_id)) {
            $param['job_id']     = $job_id;
        }
        $this->syn_status        = DeviceConst::DEVICE_ALL_SYN_QUEUE;
        $this->syn_status_txt    = '下发设备一键同步队列';
        $this->err_reason        = "";
        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
        $this->recordDeviceBindFilterBox($param);
        return true;
    }

    /**
     * 单个人员身份同步下发队列
     * @param $pigcms_id
     * @param $village_id
     * @return bool
     */
    public function userSynDevice($pigcms_id, $village_id) {
        fdump_api([$pigcms_id, $village_id], 'v20Face/userSynDevice',1);
        $nowTime = $this->nowTime ? $this->nowTime : time();
        $userBinds = $this->getDeviceBindUser($pigcms_id, $village_id);
        if ($userBinds && isset($userBinds['village_id'])) {
            $village_id = $userBinds['village_id'];
        }
        $orderGroupId = md5(uniqid().$nowTime);// 标记统一执行命令
        $orderGroupType = 'user_syn_device';
        $param = [
            'pigcms_id'      => $pigcms_id,
            'village_id'     => $village_id,
            'orderGroupId'   => $orderGroupId,
            'orderGroupType' => $orderGroupType,
        ];
        $param = $this->filterCommonParam($param);
        if(!$userBinds) {
            $this->record_bind_type  = DeviceConst::BIND_FACE_BIND_USER_BIND_CARD;
            $this->record_bind_id    = $pigcms_id;
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'pigcms_id' => $pigcms_id, 'village_id' => $village_id, 'param' => $param];
            $this->recordDeviceBindFilterBox($param);
            return false;
        }
        $judgeThirdProtocolDeviceConfig = $this->judgeThirdProtocolDeviceConfig($village_id);
        if ($judgeThirdProtocolDeviceConfig) {
            $judgeThirdProtocolDHYunRuiCloud = $this->judgeThirdProtocolDHYunRuiCloud($village_id);
            $hasDevice = false;
            if ($judgeThirdProtocolDHYunRuiCloud) {
                $whereFace = [];
                if ($village_id) {
                    $whereFace[] = ['village_id', '=', $village_id];
                }
                $whereFace[] = ['thirdProtocol',  '=', DeviceConst::DH_YUNRUI];
                $whereFace[] = ['is_del',         '=', 0];
                $deviceDHYunRuiCloudCount = (new HouseFaceDeviceService())->getFaceCount($whereFace);
                if ($deviceDHYunRuiCloudCount > 0) {
                    $hasDevice = true;
                }
            }
            $judgeThirdProtocolHikNeiBuCloud = $this->judgeThirdProtocolHikNeiBuCloud($village_id);
            if ($judgeThirdProtocolHikNeiBuCloud) {
                $whereFace = [];
                if ($village_id) {
                    $whereFace[] = ['village_id', '=', $village_id];
                }
                $whereFace[] = ['thirdProtocol',  '=', DeviceConst::HIK_YUNMO_NEIBU_SHEQU];
                $whereFace[] = ['is_del',         '=', 0];
                $deviceHikNeiBuCloudCount = (new HouseFaceDeviceService())->getFaceCount($whereFace);
                if ($deviceHikNeiBuCloudCount > 0) {
                    $hasDevice = true;
                }
            }
            if (!$hasDevice) {
                $this->record_bind_type  = DeviceConst::BIND_FACE_BIND_USER_BIND_CARD;
                $this->record_bind_id    = $pigcms_id;
                $this->syn_status        = DeviceConst::BIND_USER_ALL_SYN_QUEUE;
                $this->syn_status_txt    = '下发设备不存在';
                $this->err_reason        = "";
                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'pigcms_id' => $pigcms_id, 'village_id' => $village_id, 'param' => $param];
                $this->recordDeviceBindFilterBox($param);
                fdump_api(['msg' => '下发设备不存在', 'judgeThirdProtocolHikNeiBuCloud' => $judgeThirdProtocolHikNeiBuCloud, 'line' => __LINE__, 'funs' => __FUNCTION__, 'pigcms_id' => $pigcms_id, 'village_id' => $village_id, 'param' => $param], 'v20Face/userSynDevice',1);
            }

            if ($hasDevice && isset($deviceDHYunRuiCloudCount) && $deviceDHYunRuiCloudCount > 0) {
                $param['jobType']       = 'userSynToDevice';
                $param['thirdProtocol'] = DeviceConst::DH_YUNRUI;
                $job_id = $this->traitCommonDHCloudPersons($param);

                fdump_api(['job_id' => $job_id, 'param' => $param], 'v20Face/userSynDevice',1);
                if (isset($job_id)) {
                    $param['job_id']     = $job_id;
                }
                $this->record_bind_type  = DeviceConst::BIND_FACE_BIND_USER_BIND_CARD;
                $this->record_bind_id    = $pigcms_id;
                $this->syn_status        = DeviceConst::BIND_USER_ALL_SYN_QUEUE;
                $this->syn_status_txt    = '下发住户同步队列';
                $this->err_reason        = "";
                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
                $this->recordDeviceBindFilterBox($param);
            }

            fdump_api(['deviceHikNeiBuCloudCount' => $deviceHikNeiBuCloudCount], 'v20Face/userSynDevice',1);
            if ($hasDevice && isset($deviceHikNeiBuCloudCount) && $deviceHikNeiBuCloudCount > 0) {
                $param['jobType']       = 'userSynToDevice';
                $param['thirdProtocol'] = DeviceConst::HIK_YUNMO_NEIBU_SHEQU;
                $job_id = $this->traitCommonHikToJob($param);

                fdump_api(['job_id' => $job_id, 'param' => $param], 'v20Face/userSynDevice',1);
                if (isset($job_id)) {
                    $param['job_id']     = $job_id;
                }
                $this->record_bind_type  = DeviceConst::BIND_FACE_BIND_USER_BIND_CARD;
                $this->record_bind_id    = $pigcms_id;
                $this->syn_status        = DeviceConst::BIND_USER_ALL_SYN_QUEUE;
                $this->syn_status_txt    = '下发住户同步队列';
                $this->err_reason        = "";
                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
                $this->recordDeviceBindFilterBox($param);
            }
        }
        
        // 小区网关


        $deviceQrCodeJudge = (new HikGateWayEventService())->deviceQrCodeJudge($village_id);
        fdump_api(['deviceQrCodeJudge' => $deviceQrCodeJudge, 'village_id' => $village_id], 'v20Face/userSynDevice', 1);
        if ($deviceQrCodeJudge) {
            // todo gateway 走队列 下发人员
            $userToDevices = [
                'pigcms_id' => $pigcms_id, 'village_id' => $village_id,
            ];
            $userToDevices['handleType'] = 'userToAllBelongFaceDevice';
            fdump_api(['msg' => 'todo gateway 走队列 下发人员', '$pigcms_id' => $pigcms_id, '$village_id' => $village_id, '$userToDevices' => $userToDevices], 'v20Face/userSynDevice', 1);
           // $job_id = $this->traitCommonHikDeviceGateWay($userToDevices);
            // todo 临时直接调用方法
         (new HikGateWayDeviceService())->userToAllBelongFaceDevice($pigcms_id, $village_id,array('no_job'=>1));
        } elseif (!$judgeThirdProtocolDeviceConfig) {
            if ($userBinds['type'] == 4) {
                $judgeFloorId = 0;
            } else {
                $judgeFloorId = $userBinds['floor_id'] && $userBinds['floor_id'] > 0 ? $userBinds['floor_id'] : 0;
            }
            $db_house_face_device = new HouseFaceDevice();//人脸门禁表
            $whereFaceCount = [];
            $whereFaceCount[] = ['village_id', '=', $village_id];
            $whereFaceCount[] = ['thirdProtocol', '<=', DeviceConst::HIK_YUNMO_WAIBU];
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
            if ($deviceCount <= 0) {
                if(isset($params['log_id']) && $params['log_id']){
                    (new OwnerService())->updateLog(['log_id'=>$params['log_id']],[
                        'status'=>2,
                        'msg'=>'当前住户无对应可同步设备',
                        'last_time'=>time()
                    ]);
                } else {
                    $syncLogParams = [
                        'pigcms_id' => $pigcms_id, 'device_id' => -1, 'nowTime' => time(), 'status' => 2, 'msg' => '当前住户无对应可同步设备',
                    ];
                    (new OwnerService())->handleHouseFaceSyncLog($syncLogParams);
                }
                $houseVillageUserBindService = new HouseVillageUserBindService();
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
     * 住户同步至设备
     * @param array $param
     * @return false
     */
    public function userSynToDevice(array $param) {
        fdump_api(['param' => $param], 'v20Face/userSynToDevice',1);
        $this->filterCommonParam($param);
        $pigcms_id     = $this->village_user_bind_id;
        $village_id    = $this->village_id;
        $thirdProtocol = $this->thirdProtocol;
        
        $faceDHYunRuiCloudDeviceService = new FaceDHYunRuiCloudDeviceService();
        $faceHikCloudNeiBuDeviceService = new FaceHikCloudNeiBuDeviceService();
        $userBinds = $this->getDeviceBindUser($pigcms_id, $village_id);
        if(!$userBinds) {
            $this->record_bind_type  = DeviceConst::BIND_FACE_BIND_USER_BIND_CARD;
            $this->record_bind_id    = $pigcms_id;
            $this->syn_status        = DeviceConst::BIND_USER_ALL_SYN_ERR;
            $this->syn_status_txt    = '平台没有对应住户';
            $this->err_reason        = "平台没有对应住户";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'pigcms_id' => $pigcms_id, 'village_id' => $village_id, 'param' => $param];
            $this->recordDeviceBindFilterBox($param);
            fdump_api(['msg' => '平台没有对应住户'], 'v20Face/userSynToDevice',1);
            return false;
        }
        $houseVillageUserBindService = new HouseVillageUserBindService();
        
        if (isset($userBinds['village_id']) && $userBinds['village_id']) {
            $village_id = $userBinds['village_id'];
        }
        fdump_api(['userBinds' => $userBinds], 'v20Face/userSynToDevice',1);
        if (!$userBinds['uid']) {
            if($param['log_id']){
                (new OwnerService())->updateLog(['log_id'=>$param['log_id']],[
                    'status'=>2,
                    'msg'=>'缺少平台身份',
                    'last_time'=>time()
                ]);
            }
            $updateBindParam = [
                'face_img_status' => 2,
                'face_img_reason' => '缺少平台身份',
            ];
            $houseVillageUserBindService->saveUserBind(['pigcms_id' => $pigcms_id], $updateBindParam);
            $this->record_bind_type  = DeviceConst::BIND_FACE_BIND_USER_BIND_CARD;
            $this->record_bind_id    = $pigcms_id;
            $this->syn_status        = DeviceConst::BIND_USER_ALL_SYN_ERR;
            $this->syn_status_txt    = '住户同步失败(住户状态不正常)';
            $this->err_reason        = "同步的住户状态不正常";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
            $this->recordDeviceBindFilterBox($param);
            fdump_api(['msg' => '同步的住户状态不正常'], 'v20Face/userSynToDevice',1);
            return false;
        }
        $hasDevices = false;
        if ($thirdProtocol == DeviceConst::DH_YUNRUI || $faceDHYunRuiCloudDeviceService->judgeConfig()) {
            $thirdProtocol = $this->thirdProtocol = DeviceConst::DH_YUNRUI;
            $this->record_bind_type  = DeviceConst::BIND_FACE_BIND_USER_BIND_CARD;
            $this->record_bind_id    = $pigcms_id;
            $this->syn_status        = DeviceConst::BIND_USER_ALL_SYN_START;
            $this->syn_status_txt    = '住户同步开始';
            $this->err_reason        = "";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'pigcms_id' => $pigcms_id, 'village_id' => $village_id];
            $this->step_num++;
            
            
            $deviceIds = [];
            // 大户云睿存在 查询对应同步设备
            $deviceAuth = new DeviceAuth();
            $sourceWhereAuth = [];
            $sourceWhereAuth[] = ['device_equipment_type', '=', 'face'];
            $sourceWhereAuth[] = ['village_id', '=', $village_id];
            $sourceWhereAuth[] = ['delete_time', '=', 0];
            $sourceWhereAuth[] = ['device_id', '>', 0];
            $whereAuth = $sourceWhereAuth;
            $whereAuth[] = ['all_type', '=', 'allVillages'];
            $deviceIdAll = $deviceAuth->getOneColumn($whereAuth, 'device_id');
            if (!empty($deviceIdAll)) {
                $deviceIds = array_merge($deviceIds, $deviceIdAll);
            }
            $whereSingleAuth = $sourceWhereAuth;
            $whereSingleAuth[] = ['all_type', '=', 'allSingles'];
            $whereSingleAuth[] = ['single_id', '=', $userBinds['single_id']];
            $deviceIdSingle = $deviceAuth->getOneColumn($whereSingleAuth, 'device_id');
            if (!empty($deviceIdSingle)) {
                $deviceIds = array_merge($deviceIds, $deviceIdSingle);
            }
            $whereFloorAuth = $sourceWhereAuth;
            $whereFloorAuth[] = ['all_type', '=', 'allFloors'];
            $whereFloorAuth[] = ['floor_id', '=', $userBinds['floor_id']];
            $deviceIdFloor = $deviceAuth->getOneColumn($whereFloorAuth, 'device_id');
            if (!empty($deviceIdFloor)) {
                $deviceIds = array_merge($deviceIds, $deviceIdFloor);
            }
            $whereLayerAuth = $sourceWhereAuth;
            $whereLayerAuth[] = ['all_type', '=', 'allLayers'];
            $whereLayerAuth[] = ['layer_id', '=', $userBinds['layer_id']];
            $deviceIdLayer = $deviceAuth->getOneColumn($whereLayerAuth, 'device_id');
            if (!empty($deviceIdLayer)) {
                $deviceIds = array_merge($deviceIds, $deviceIdLayer);
            }
            $whereRoomAuth = $sourceWhereAuth;
            $whereRoomAuth[] = ['all_type', '=', 'allRooms'];
            $whereRoomAuth[] = ['room_id', '=', $userBinds['vacancy_id']];
            $deviceIdRoom = $deviceAuth->getOneColumn($whereRoomAuth, 'device_id');
            if (!empty($deviceIdRoom)) {
                $deviceIds = array_merge($deviceIds, $deviceIdRoom);
            }
            $deviceIds = array_unique($deviceIds);
            
            if (!empty($deviceIds)) {
                $hasDevices = true;
                $houseFaceDevice = new HouseFaceDevice();
                $whereFace = [];
                $whereFace[] = ['is_del',    '=', 1];
                $whereFace[] = ['device_id', 'in', $deviceIds];
                $whereFace[] = ['thirdProtocol', '=', $thirdProtocol];
                $deviceSnArr = $houseFaceDevice->getColumn($whereFace, 'device_sn', 'device_id');
                
                $device_type   = DeviceConst::DEVICE_TYPE_FACE;
                $step_num      = $this->step_num + 1;
                foreach ($deviceIds as $device_id) {
                    //增加下发记录
                    $device = (new HouseFaceDevice())->where(['pigcms_id'=>$pigcms_id])->field('device_sn')->find();
                    $logId = (new HouseFaceSyncLog())->insertGetId([
                        'pigcms_id'=>$pigcms_id,
                        'face_id'=>0,
                        'device_id'=>$device_id,
                        'device_sn'=>$device['device_sn'],
                        'log_time'=>time(),
                        'status'=>0,
                        'device_info'=>json_encode($device),
                    ]);
                    $param = [
                        'pigcms_id'     => $pigcms_id,
                        'device_id'     => $device_id,
                        'room_id'       => $userBinds['vacancy_id'],
                        'uid'           => isset($userBinds['uid']) ? $userBinds['uid'] : 0,
                        'village_id'    => $village_id,
                        'thirdProtocol' => $thirdProtocol,
                        'deviceType'    => $device_type,
                        'step_num'      => $step_num,
                        'log_id'      => $logId,
                    ];
                    if ($deviceSnArr && isset($deviceSnArr[$device_id])) {
                        $param['device_sn'] = $deviceSnArr[$device_id];
                    }
                    $param['jobType'] = 'addPersonsToCloud';
                    $job_id = $this->traitCommonDHCloudPersons($param);

                    $param = $this->filterCommonParam($param);
                    if (isset($job_id)) {
                        $param['job_id']     = $job_id;
                    }
                    $this->step_num          = $step_num;
                    $this->record_bind_type  = DeviceConst::BIND_FACE_BIND_USER_BIND_CARD;
                    $this->record_bind_id    = $pigcms_id;
                    $this->syn_status        = DeviceConst::BIND_USER_TO_DEVICE_SYN_QUEUE;
                    $this->syn_status_txt    = '下发住户同步设备队列';
                    $this->err_reason        = "";
                    $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
                    $this->recordDeviceBindFilterBox($param);
                }
            } else {
                $hasDevices = false;
            }
        }
        if ($thirdProtocol == DeviceConst::HIK_YUNMO_NEIBU_SHEQU || $faceHikCloudNeiBuDeviceService->judgeConfig()) {
            // todo 海康内部应用存在 查询对应权限
            $thirdProtocol = $this->thirdProtocol = DeviceConst::HIK_YUNMO_NEIBU_SHEQU;
            $whereFace = [];
            if ($village_id) {
                $whereFace[] = ['village_id', '=', $village_id];
            }
            $whereFace[] = ['thirdProtocol',  '=', $thirdProtocol];
            $whereFace[] = ['is_del',         '=', 0];
            $db_house_face_device = new HouseFaceDevice();//人脸门禁表
            $deviceList = $db_house_face_device->getList($whereFace, true, 1);
            if ($deviceList && !is_array($deviceList)) {
                $deviceList = $deviceList->toArray();
            }
            $device_type   = DeviceConst::DEVICE_TYPE_FACE;
            $step_num      = $this->step_num + 1;
            if (!empty($deviceList)) {
                if ($userBinds['type'] == 4) {
                    $judgeFloorId = 0;
                } else {
                    $judgeFloorId = $userBinds['floor_id'] && $userBinds['floor_id'] > 0 ? $userBinds['floor_id'] : 0;
                }
                foreach ($deviceList as $device) {
                    if ($judgeFloorId && isset($device['floor_id']) && $device['floor_id'] != -1 && $device['floor_id'] != 0) {
                        $floorIdArr = explode(',', $device['floor_id']);
                        if (!in_array($judgeFloorId, $floorIdArr)) {
                            // 不是同一个单元的 同步不进行下发处理
                            continue;
                        }
                    }
                    //增加下发记录
                    $logId = (new HouseFaceSyncLog())->insertGetId([
                        'pigcms_id'=>$pigcms_id,
                        'face_id'=>0,
                        'device_id'=>$device['device_id'],
                        'device_sn'=>$device['device_sn'],
                        'log_time'=>time(),
                        'status'=>0,
                        'device_info'=>json_encode($device),
                    ]);
                    $param = [
                        'pigcms_id' => $pigcms_id,
                        'device_id' => $device['device_id'],
                        'room_id' => $userBinds['vacancy_id'],
                        'uid' => isset($userBinds['uid']) ? $userBinds['uid'] : 0,
                        'village_id' => $device['village_id'],
                        'thirdProtocol' => $thirdProtocol,
                        'device_sn' => $device['device_sn'],
                        'deviceType' => $device_type,
                        'step_num' => $step_num,
                        'log_id' => $logId,
                    ];
                    $param['jobType'] = 'addPersonsToCloud';
                    $hasDevices = true;
                    $job_id = $this->traitCommonHikToJob($param);

                    $param = $this->filterCommonParam($param);
                    if (isset($job_id)) {
                        $param['job_id']     = $job_id;
                    }
                    $this->step_num          = $step_num;
                    $this->record_bind_type  = DeviceConst::BIND_FACE_BIND_USER_BIND_CARD;
                    $this->record_bind_id    = $pigcms_id;
                    $this->syn_status        = DeviceConst::BIND_USER_TO_DEVICE_SYN_QUEUE;
                    $this->syn_status_txt    = '下发住户同步设备队列';
                    $this->err_reason        = "";
                    $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
                    fdump_api(['param' => $param, 'job_id' => $job_id, 'msg' => '下发住户同步设备队列'], 'v20Face/userSynToDevice',1);
                    $this->recordDeviceBindFilterBox($param);
                }
            }
        }
        if (!$hasDevices) {
            if($param['log_id']){
                (new OwnerService())->updateLog(['log_id'=>$param['log_id']],[
                    'status'=>2,
                    'msg'=>'无对应可同步设备',
                    'last_time'=>time()
                ]);
            }
            $updateBindParam = [
                'face_img_status' => 2,
                'face_img_reason' => '无对应可同步设备',
            ];
            $houseVillageUserBindService->saveUserBind(['pigcms_id' => $pigcms_id], $updateBindParam);

            $this->record_bind_type  = DeviceConst::BIND_FACE_BIND_USER_BIND_CARD;
            $this->record_bind_id    = $pigcms_id;
            $this->syn_status        = DeviceConst::BIND_USER_ALL_SYN_ERR;
            $this->syn_status_txt    = '住户同步失败(无对应可同步设备)';
            $this->err_reason        = "无对应可同步设备";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
            $this->recordDeviceBindFilterBox($param);
            return false;
        }
        return true;
    }
    
    /**
     * 可能存在多人同步 提前过滤下 如果是多人 分配下队列进行执行
     * @param array $param 参数看备注
     * @return bool
     */
    public function addPersonsToCloud(array $param) {
        fdump_api(['param' => $param, 'msg' => '0下发住户同步设备队列'], 'v20Face/userSynToDevice',1);
        $param = $this->filterCommonParam($param);
        /** @var array queueData 统一初始化数据  有变动自行变更 */
        $aboutInfo = $this->filterVillageToData();
        $queueData = [
            'parent_third_id'    => $this->parent_third_id,
            'device_id'          => $this->device_id,
            'device_sn'          => $this->device_sn,
            'village_id'         => $this->village_id,
            'thirdProtocol'      => $this->thirdProtocol,
            'village_third_id'   => $this->village_third_id,
            'village_third_code' => $this->village_third_code,
            'room_id'            => $this->room_id,
            'step_num'           => $this->step_num,
            'orderGroupId'       => $this->order_group_id,
            'orderGroupType'     => $this->order_group_type,
            'log_id'     => $this->log_id,
        ];

        fdump_api(['queueData' => $queueData, 'msg' => '0-0下发住户同步设备队列'], 'v20Face/userSynToDevice',1);
        // 同步人员必须要有小区住户身份id
        
        if ($this->village_user_bind_id) {
            // todo 有单个人员信息  直接进行同步
            switch ($this->thirdProtocol) {
                case DeviceConst::DH_YUNRUI:
                    $queueData['pigcms_id'] = $this->village_user_bind_id;
                    $queueData['uid']       = $this->user_id;
                    $queueData['jobType']   = 'addPersonToDhYunRuiCloud';
                    $job_id = $this->traitCommonDHCloudPersons($queueData);
//                    // todo 临时调试 直接调用
//                    $this->addPersonToDhYunRuiCloud($queueData);
                    break;
                case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                    $queueData['pigcms_id'] = $this->village_user_bind_id;
                    $queueData['uid']       = $this->user_id;
                    $queueData['jobType']   = 'addSinglePersonToCloud';
                    $cacheTag  = HikConst::HIK_JOB_REDIS_TAG;
                    $cacheKey  = HikConst::HIK_TRAITS_REDIS_KEY . 'addSinglePersonToCloudRedisKeyTime';
                    $nowTime = time();
                    $spaceTime = $nowTime + 1;
                    $jobRedis  = Cache::store('redis')->get($cacheKey);
                    $jobRedisSpace = $jobRedis + 1;
                    if (intval($jobRedis) == $nowTime || intval($jobRedis) >= $spaceTime || ($nowTime > $jobRedis && $nowTime <= $jobRedisSpace)) {
                        $space = $jobRedisSpace - $nowTime;
                        fdump_api(['queueData' => $queueData, 'jobRedis' => $jobRedis, 'space' => $space, 'msg' => 'jobRedis-3'], 'v20Face/userSynToDevice',1);
                        $job_id = $this->traitCommonHikLaterToJob($queueData, $space);
                        Cache::store('redis')->tag($cacheTag)->set($cacheKey, $spaceTime, 10);
                    } else {
                        fdump_api(['queueData' => $queueData, 'jobRedis' => $jobRedis, 'msg' => 'jobRedis'], 'v20Face/userSynToDevice',1);
                        $job_id = $this->traitCommonHikToJob($queueData);
                        Cache::store('redis')->tag($cacheTag)->set($cacheKey, $nowTime, 10);
                    }
                    fdump_api(['queueData' => $queueData, 'job_id' => $job_id, 'msg' => '1下发住户同步设备队列'], 'v20Face/userSynToDevice',1);
                    break;
            }
        } else {
            try {
                if ($this->user_id) {
                    $users = $this->filterUsesToData($this->user_id);
                } else {
                    $users = $this->filterDhUserBindToData();
                }
            } catch (\Exception $e){
                fdump_api(['执行错误'=>$e->getMessage(),'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()],'$usersErr',1);
            }catch (\Throwable $e){
                fdump_api(['执行错误'=>$e->getMessage(),'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()],'$usersErr',1);
            }catch (\RuntimeException $e){
                fdump_api(['执行错误'=>$e->getMessage(),'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()],'$usersErr',1);
            }catch (\InvalidArgumentException $e){
                fdump_api(['执行错误'=>$e->getMessage(),'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()],'$usersErr',1);
            }
            if (!empty($users)) {
                switch ($this->thirdProtocol) {
                    case DeviceConst::DH_YUNRUI:
                        foreach ($users as $key => $user) {
                            $queueData['pigcms_id'] = $user['pigcms_id'];
                            $queueData['uid']       = $user['uid'] ? $user['uid'] : 0;
                            $queueData['room_id']   = $user['vacancy_id'];
                            $queueData['jobType']   = 'addPersonToDhYunRuiCloud';
                            if ($key == 0) {
                                $job_id = $this->traitCommonDHCloudPersons($queueData);
                            } else {
                                $time = $key * 6;
                                $job_id = $this->traitCommonDHLaterToJob($queueData, $time); 
                            }
                        }
                        break;
                    case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                        foreach ($users as $key => $user) {
                            $queueData['pigcms_id'] = $user['pigcms_id'];
                            $queueData['uid']       = $user['uid'] ? $user['uid'] : 0;
                            $queueData['room_id']   = $user['vacancy_id'];
                            $queueData['single_id'] = $user['single_id'];
                            $queueData['floor_id']  = $user['floor_id'];
                            $queueData['layer_id']  = $user['layer_id'];
                            $queueData['jobType']   = 'addSinglePersonToCloud';
                            if ($key == 0) {
                                $job_id = $this->traitCommonHikToJob($queueData);
                            } else {
                                $time = $key * 3;
                                $job_id = $this->traitCommonHikLaterToJob($queueData, $time);
                            }
                        }
                        break;
                }
            }
        }
        return true;
    }
    
    /**
     * 添加权限
     * @param array $param
     * @return bool
     */
    public function addDhAuth(array $param) {
        if (isset($param['isRepeat'])) {
            $isRepeat = isset($param['isRepeat']) && $param['isRepeat'] ? $param['isRepeat'] : 0;
            unset($param['isRepeat']);
        } else {
            $isRepeat = 0;
        }
        if (isset($param['sleep'])) {
            $sleep = isset($param['sleep']) && $param['sleep'] ? $param['sleep'] : 0;
            unset($param['sleep']);
        } else {
            $sleep = 0;
        }
        $param         = $this->filterCommonParam($param);
        if ($this->device_sn || $this->device_id) {
            $infoArr = $this->handleParamDeviceInfo($param);
            if (isset($infoArr) && isset($infoArr['deviceInfo']) && $infoArr['deviceInfo']) {
                $deviceInfo       = $infoArr['deviceInfo'];
            }
        } else {
            $deviceInfo = [];
        }
        $timePlanInfo  = $this->addDhTimePlan($this->device_id, $param);
        $timePlanIndex = $timePlanInfo['timePlanIndex'];

        $operateType         = isset($param['operateType'])       && $param['operateType']       ? $param['operateType']       : 1;
        if ($timePlanIndex == -1) {
            // 记录同步失败为 开门计划缺失
            fdump_api(['errTip' => '开门计划缺失', 'timePlanInfo' => $timePlanInfo, 'param' => $param], '$errRecordFaceDeviceLog', 1);
            return false;
        }
        $bind_type = DeviceConst::DH_PERSON_TO_DEVICE_USER;
        if ($this->user_id) {
            $this->filterUserToDataFromUid();
            $this->time_plan_index = $timePlanIndex;
            if ($this->device_equipment_type == DeviceConst::DEVICE_TYPE_FINGERPRINT) {
                $bind_type = DeviceConst::DH_PERSON_TO_FINGERPRINT_DEVICE_USER;
            }
            $authAboutInfo         = $this->filterUserToDeviceFromUid($bind_type);
            if (isset($authAboutInfo['id']) && $authAboutInfo['id'] && $isRepeat == 0) {
                $operateType = 2;
            }
        }
        $faceDHYunRuiCloudDeviceService = new FaceDHYunRuiCloudDeviceService();
        if (isset($deviceInfo['device_sn']) && $deviceInfo['device_sn']) {
            $device_sn = $deviceInfo['device_sn'];
        } else {
            $device_sn = $this->device_sn;
        }
        if (!$device_sn) {
            // 记录同步失败为 
            fdump_api(['errTip' => '门禁信息缺失', 'deviceInfo' => $deviceInfo, 'device_sn' => $this->device_sn, 'device_id' => $this->device_id, 'param' => $param], '$errRecordFaceDeviceLog', 1);
            return false;
        }
        $params = [
            'channelId'     => 0,
            'deviceId'      => $device_sn,
            'operateType'   => $operateType,
            'personFileId'  => $this->user_third_id,
            'timePlanIndex' => $timePlanIndex,
        ];
        if (!$this->user_third_id || $this->user_third_id == '') {
            if($param['log_id']){
                (new OwnerService())->updateLog(['log_id'=>$param['log_id']],[
                    'status'=>2,
                    'msg'=>'缺少三方身份，请重新同步',
                    'last_time'=>time()
                ]);
            }
            $houseVillageUserBindService = new HouseVillageUserBindService();
            $updateBindParam = [
                'face_img_status' => 1,
                'face_img_reason' => '缺少三方身份，请重新同步',
            ];
            $houseVillageUserBindService->saveUserBind(['pigcms_id' => $this->village_user_bind_id], $updateBindParam);
            // 记录同步失败为 
            fdump_api(['errTip' => '住户信息缺失', 'user_third_id' => $this->user_third_id, 'device_sn' => $this->device_sn, 'device_id' => $this->device_id, 'param' => $param], '$errRecordFaceDeviceLog', 1);
            return false;
        }
        $face_grant_json_md5 = md5(json_encode([$params, date('Y-m-d', time())], JSON_UNESCAPED_UNICODE));
        $aboutInfo = $this->commonDhFilterUserToData(DeviceConst::DH_PERSON_TO_DEVICE_USER, $this->user_id);
        if ($aboutInfo && isset($aboutInfo['face_grant_json']) && $aboutInfo['face_grant_json'] == $face_grant_json_md5) {
            if($param['log_id']){
                (new OwnerService())->updateLog(['log_id'=>$param['log_id']],[
                    'status'=>1,
                    'last_time'=>time()
                ]);
            }
            $houseVillageUserBindService = new HouseVillageUserBindService();
            $updateBindParam = [
                'face_img_status' => 1,
                'face_img_reason' => '',
            ];
            $houseVillageUserBindService->saveUserBind(['pigcms_id' => $this->village_user_bind_id], $updateBindParam);
            return true;
        }
        $cacheTag   = DahuaConst::DH_JOB_REDIS_TAG;
        $cacheKey   = DahuaConst::DH_REQUEST_REDIS_KEY . $device_sn;
        $lastUserThirdId = Cache::store('redis')->get($cacheKey);
        $hasLast = false;
        if ($lastUserThirdId) {
            $params1 = [
                'deviceId'      => $device_sn,
                'personFileId'  => $lastUserThirdId,
            ];
            $resultSearch = $faceDHYunRuiCloudDeviceService->searchAuthRecord($params1);
            if (isset($resultSearch['data']['list']) && count($resultSearch['data']['list']) > 0) {
                sleep(6);
                $hasLast = true;
            }
        } else {
            $resultSearch = [];
        }
        $result       = $faceDHYunRuiCloudDeviceService->addAuth($params);
        fdump_api(['params' => $params, 'param' => $param, 'result' => $result, 'resultSearch' => $resultSearch, 'hasLast' => $hasLast], '$addAuth', 1);
        if (isset($result['success']) && $result['success']) {
            // 成功了缓存下来 以便于下次查询
            Cache::store('redis')->tag($cacheTag)->set($cacheKey, $this->user_third_id,7200-mt_rand(10,30));
            if($param['log_id']){
                (new OwnerService())->updateLog(['log_id'=>$param['log_id']],[
                    'status'=>1,
                    'last_time'=>time()
                ]);
            }
            $houseVillageUserBindService = new HouseVillageUserBindService();
            $updateBindParam = [
                'face_img_status' => 1,
                'face_img_reason' => '',
            ];
            $houseVillageUserBindService->saveUserBind(['pigcms_id' => $this->village_user_bind_id], $updateBindParam);
            $recordParam = [
                'bind_type'       => DeviceConst::DH_PERSON_TO_DEVICE_USER,
                'bind_id'         => $this->user_id,
                'person_id'       => $this->user_third_id,
                'personID'        => $this->user_third_code,
                'group_id'        => $this->room_third_code,
                'groupID'         => $this->village_third_id,
                'open_key'        => $timePlanIndex,
                'device_id'       => $this->device_id,
                'code'            => $this->device_sn,
                'face_grant_json' => $face_grant_json_md5,
            ];
            $this->recordDhAddAuth($recordParam);
            if ($sleep) {
                sleep(5);
            }
            return true;
        } elseif (isset($result['errMsg']) && (strpos($result['errMsg'],'正在执行下发任务') !== false || strpos($result['errMsg'],'设备访问失败') !== false)) {
            // 下发受阻 走异步逻辑
            $result1       = $faceDHYunRuiCloudDeviceService->batchAuthDevice($params);
            if (isset($result1['data']['taskId']) && $result1['data']['taskId']) {
                $taskId = $result1['data']['taskId'];
                $cacheTag  = DahuaConst::DH_JOB_REDIS_TAG;
                $cacheKey  = DahuaConst::DH_BATCH_REDIS_KEY . $taskId;
                Cache::store('redis')->tag($cacheTag)->set($cacheKey, $this->village_user_bind_id,7200-mt_rand(10,30));
                return true;
            } else {
                $param['isRepeat']      = 1;
                $param['user_third_id'] = $this->user_third_id;
                $param['uid']           = $this->user_id;
                $param['jobType']       = 'addDhAuth';
                $param['sleep']         = 1;
                return $this->traitCommonDHLaterToJob($param, 6);
            }
        } elseif ($isRepeat == 0 && isset($result['errMsg']) && strpos($result['errMsg'],'正在执行下发任务') !== false) {
            // 下发受阻 休眠1秒执行下
            $param['isRepeat']      = 1;
            $param['user_third_id'] = $this->user_third_id;
            $param['uid']           = $this->user_id;
            $param['sleep']         = 1;
            if ($sleep) {
                sleep(5);
            } else {
                sleep(1);
            }
            return $this->addDhAuth($param);
        } elseif ($isRepeat == 1 && isset($result['errMsg']) && strpos($result['errMsg'],'正在执行下发任务') !== false) {
            // 下发受阻 调用队列执行
            $param['isRepeat']      = 2;
            $param['user_third_id'] = $this->user_third_id;
            $param['uid']           = $this->user_id;
            $param['jobType']       = 'addDhAuth';
            $param['sleep']         = 1;
            if ($sleep) {
                sleep(5);
            }
            return $this->traitCommonDHLaterToJob($param, 6);
        } elseif ($isRepeat == 0 && isset($result['errMsg']) && $result['errMsg'] == '方法参数错误' && $operateType == 2) {
            $param['operateType']   = 1;
            $param['isRepeat']      = 1;
            $param['user_third_id'] = $this->user_third_id;
            $param['uid']           = $this->user_id;
            if ($sleep) {
                sleep(5);
            }
            return $this->addDhAuth($param);
        } elseif ($isRepeat == 0 && isset($result['errMsg']) && $result['errMsg'] == '设备未知错误' && $operateType == 1) {
            $param['operateType']   = 2;
            $param['isRepeat']      = 1;
            $param['user_third_id'] = $this->user_third_id;
            $param['uid']           = $this->user_id;
            if ($sleep) {
                sleep(5);
            }
            return $this->addDhAuth($param);
        } elseif ($this->user_third_id && isset($result['errMsg']) && $result['errMsg'] == '人员不存在') {
            // 删除关联记录重新走下同步
            if ($isRepeat > 0) {
                $dbFaceUserBindDevice = new FaceUserBindDevice();
                $whereAbout = [];
                $whereAbout[] = ['person_id', '=', $this->user_third_id];
                $del = $dbFaceUserBindDevice->deleteInfo($whereAbout);
                $pigcms_id = $this->village_user_bind_id ? $this->village_user_bind_id : 0;
                if (!$pigcms_id && isset($param['pigcms_id'])) {
                    $pigcms_id = $param['pigcms_id'];
                }
            }
            if ($sleep) {
                sleep(5);
            }
            $this->userSynDevice($pigcms_id, $this->village_id);
            return false;
        }
//            fdump_api(['errTip' => '下发失败', 'result' => $result, 'params' => $params, 'param' => $param], '$errRecordFaceDeviceLog', 1);
        if (isset($result['errMsg'])) {
            if($param['log_id']){
                (new OwnerService())->updateLog(['log_id'=>$param['log_id']],[
                    'status'=>2,
                    'msg'=>$result['errMsg'],
                    'last_time'=>time()
                ]);
            }
            $houseVillageUserBindService = new HouseVillageUserBindService();
            $updateBindParam = [
                'face_img_status' => 2,
                'face_img_reason' => $result['errMsg'],
            ];
            $houseVillageUserBindService->saveUserBind(['pigcms_id' => $this->village_user_bind_id], $updateBindParam);
        }
        return false;
    }
    
    /**
     * 添加开门计划
     * @param string $device_id 设备id
     * @param array $param 其他参数
     * @return array
     */
    public function addDhTimePlan(string $device_id, array $param) {
        if ((!isset($param['device_id']) || !$param['device_id']) && $device_id) {
            $param['device_id'] = $device_id;
        }
        $param     = $this->filterCommonParam($param);
        $aboutInfo = $this->filterTimePlanFromDevice();
        $planTimes = [];
        if (isset($aboutInfo['third_id']) && $aboutInfo['third_id']) {
            $timePlanIndex = $aboutInfo['third_id'];
        } else {
            $faceDHYunRuiCloudDeviceService = new FaceDHYunRuiCloudDeviceService();
            $params = [
                'name'      => isset($param['planName']) && $param['planName'] ? $param['planName'] : '人脸_'.$device_id,
                'device_id' => $device_id,
            ];
            $planTimes = $faceDHYunRuiCloudDeviceService->addTimePlan($params);
            if (isset($planTimes['success']) && $planTimes['success'] && isset($planTimes['data'])) {
                $timePlanIndex = $planTimes['data'];
            }
        }
        if (isset($timePlanIndex)) {
            $saveParam = [
                'third_id'  => $timePlanIndex,
                'device_id' => $this->device_id,
                'group_id'  => $this->village_id,
            ];
            if (isset($params['name'])) {
                $saveParam['bind_name'] = $params['name'];
            }
            $this->commonSaveAboutInfo(DeviceConst::DH_TIME_PLAN_CLOUD_DEVICE,$this->device_id, $saveParam);
            return ['timePlanIndex' => $timePlanIndex,'device_id' => $device_id, 'param' => $param, 'planTimes' => $planTimes];
        } else {
            return ['timePlanIndex' => -1,'device_id' => $device_id, 'param' => $param, 'planTimes' => $planTimes];
        }
    }

    /**
     * 获取对应门禁绑定的权限
     * @param string $device_id
     * @param array $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deviceBindAuthList(string $device_id, array $param) {
        $deviceAuth = new DeviceAuth();
        /** @var string village_id 小区id */
        $village_id            = isset($param['village_id']) ? $param['village_id'] : 0;
        /** @var string page 查询页数 */
        $page                  = isset($param['page']) ? $param['page'] : 1;
        /** @var string limit 每页条数 */
        $limit                 = isset($param['limit']) ? $param['limit'] : 20;
        /** @var string device_equipment_type 设备类型 人脸face 监控camera */
        $device_equipment_type = isset($param['device_equipment_type']) ? $param['device_equipment_type'] : 'face';

        switch ($device_equipment_type) {
            case 'face':
                $whereFace  = [
                    'device_id' => $device_id
                ];
                $faceField  = 'device_id,device_name,device_type,device_sn,village_id,floor_id,device_status,thirdProtocol';
                $deviceInfo = (new HouseFaceDeviceService())->getHouseFaceDeviceInfo($whereFace, $faceField);
                if ($deviceInfo && !is_array($deviceInfo)) {
                    $deviceInfo = $deviceInfo->toArray();
                }
                if (!empty($deviceInfo)) {
                    $deviceName = $deviceInfo['device_name'] . '[人脸]';
                }
                break;
        }

        $where = [];
        $where[] = ['device_equipment_type', '=', $device_equipment_type];
        $where[] = ['device_id', '=', $device_id];
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['delete_time', '=', 0];

        $count = $deviceAuth->getCount($where);
        $list  = $deviceAuth->getPageList($where,true,'auth_id DESC', $page, $limit);
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        if (!empty($list)) {
            $singleIds   = [];
            $floorIds    = [];
            $layerIds    = [];
            $roomIds     = [];
            $userBindIds = [];
            $workIds     = [];
            foreach ($list as $item) {
                if (isset($item['single_id']) && $item['single_id']) {
                    $singleIds[]   = $item['single_id'];
                }
                if (isset($item['floor_id']) && $item['floor_id']) {
                    $floorIds[]    = $item['floor_id'];
                }
                if (isset($item['layer_id']) && $item['layer_id']) {
                    $layerIds[]    = $item['layer_id'];
                }
                if (isset($item['room_id']) && $item['room_id']) {
                    $roomIds[]     = $item['room_id'];
                }
                if (isset($item['user_bind_id']) && $item['user_bind_id']) {
                    $userBindIds[] = $item['user_bind_id'];
                }
                if (isset($item['work_id']) && $item['work_id']) {
                    $workIds[]     = $item['work_id'];
                }
            }
            if (!empty($singleIds)) {
                $db_single   = new HouseVillageSingle();
                $whereSingle    = [];
                $whereSingle[]  = ['status', 'in', [0,1]];
                $whereSingle[]  = ['village_id', '=', $village_id];
                $singleArr      = $db_single->getOneColumn($whereSingle, 'id,single_name,single_number','id');
            }
            if (!empty($floorIds)) {
                $db_floor      = new HouseVillageFloor();
                $whereFloor    = [];
                $whereFloor[]  = ['status', 'in', [0,1]];
                $whereFloor[]  = ['village_id', '=', $village_id];
                $floorArr      = $db_floor->getOneColumn($whereFloor, 'floor_id,floor_name,floor_number','floor_id');
            }
            if (!empty($layerIds)) {
                $db_layer      = new HouseVillageLayer();
                $whereLayer    = [];
                $whereLayer[]  = ['status', 'in', [0,1]];
                $whereLayer[]  = ['village_id', '=', $village_id];
                $layerArr      = $db_layer->getOneColumn($whereLayer, 'id,layer_name,layer_number','id');
            }
            if (!empty($roomIds)) {
                $db_room      = new HouseVillageUserVacancy();
                $whereRoom    = [];
                $whereRoom[]  = ['status', '<>', 4];
                $whereRoom[]  = ['village_id', '=', $village_id];
                $roomArr      = $db_room->getColumn($whereRoom, 'pigcms_id,room,room_number','pigcms_id');
            }
            if (!empty($userBindIds)) {
                $db_user_bind     = new HouseVillageUserBind();
                $whereUserBind    = [];
                $whereUserBind[]  = ['status', 'in', [0,1]];
                $whereUserBind[]  = ['village_id', '=', $village_id];
                $userBindArr      = $db_user_bind->getOneColumn($whereUserBind, 'pigcms_id,name,user_number','pigcms_id');
            }
            if (!empty($workIds)) {
                $db_work      = new HouseWorker();
                $whereWork    = [];
                $whereWork[]  = ['status', 'in', [0,1]];
                $whereWork[]  = ['village_id', '=', $village_id];
                $workArr      = $db_work->getColumn($whereWork, 'wid,name,job_number','wid');
            }
            foreach ($list as &$auth) {
                if (isset($deviceName)) {
                    $auth['deviceName'] = $deviceName;
                } else {
                    $auth['deviceName'] = '';
                }
                if (isset($auth['add_time']) && $auth['add_time']) {
                    $auth['addTimeTxt'] = date('Y-m-d H:i:s', $auth['add_time']);
                } else {
                    $auth['addTimeTxt'] = '';
                }
                if (isset($auth['update_time']) && $auth['update_time']) {
                    $auth['updateTimeTxt'] = date('Y-m-d H:i:s', $auth['update_time']);
                } else {
                    $auth['updateTimeTxt'] = '';
                }

                if (isset($auth['single_id']) && isset($singleArr[$auth['single_id']]['single_name'])) {
                    $single_name = $singleArr[$auth['single_id']]['single_name'];
                    $single_name = $this->traitAutoFixLouDongTips($single_name, true);
                } else {
                    $single_name = '';
                }
                if (isset($auth['floor_id']) && isset($floorArr[$auth['floor_id']]['floor_name'])) {
                    $floor_name = $floorArr[$auth['floor_id']]['floor_name'];
                    $floor_name = $this->traitAutoFixDanyuanTips($floor_name, true);
                } else {
                    $floor_name = '';
                }
                if (isset($auth['layer_id']) && isset($layerArr[$auth['layer_id']]['layer_name'])) {
                    $layer_name = $layerArr[$auth['layer_id']]['layer_name'];
                    $layer_name = $this->traitAutoFixLoucengTips($layer_name, true);
                } else {
                    $layer_name = '';
                }
                if (isset($auth['room_id']) && isset($roomArr[$auth['room_id']]['room'])) {
                    $room_name = $roomArr[$auth['room_id']]['room'];
                } else {
                    $room_name = '';
                }
                if (isset($auth['user_bind_id']) && isset($userBindArr[$auth['user_bind_id']]['name'])) {
                    $user_name = $userBindArr[$auth['user_bind_id']]['name'];
                } else {
                    $user_name = '';
                }
                if (isset($auth['all_type']) && $auth['all_type']) {
                    switch ($auth['all_type']) {
                        case 'work':
                            if (isset($auth['work_id']) && isset($workArr[$auth['work_id']]['name'])) {
                                $workName = $workArr[$auth['work_id']]['name'];
                            } else {
                                $workName = '';
                            }
                            if (isset($auth['work_id']) && $auth['work_id']) {
                                $auth['authObjectTxt'] = '小区员工' . "[$workName]";
                                $auth['allTypeTxt']    = '小区员工';
                            }
                            break;
                        case 'allWorks':
                            $auth['authObjectTxt']     = '小区全体工作人员';
                            $auth['allTypeTxt']        = '全体员工';
                            break;
                        case 'allVillages':
                            $auth['authObjectTxt']     = '小区全体住户';
                            $auth['allTypeTxt']        = '全体住户';
                            break;
                        case 'allSingles':
                            if (!isset($single_name) || !$single_name){
                                $single_name = "【ID：{$auth['single_id']}】楼栋";
                            }
                            $auth['authObjectTxt']     = '小区' . $single_name . '住户';
                            $auth['allTypeTxt']        = '楼栋全体';
                            break;
                        case 'allFloors':
                            if (!isset($floor_name) || !$floor_name){
                                $floor_name = "【ID：{$auth['floor_id']}】单元";
                            }
                            $auth['authObjectTxt']     = '小区' . $single_name . $floor_name . '住户';
                            $auth['allTypeTxt']        = '单元全体';
                            break;
                        case 'allLayers':
                            if (!isset($layer_name) || !$layer_name){
                                $layer_name = "【ID：{$auth['layer_id']}】楼层";
                            }
                            $auth['authObjectTxt']     = '小区' . $single_name . $floor_name . $layer_name . '住户';
                            $auth['allTypeTxt']        = '楼层全体';
                            break;
                        case 'allRooms':
                            if (!isset($room_name) || !$room_name){
                                $room_name = "【ID：{$auth['room_id']}】房屋";
                            }
                            $auth['authObjectTxt']     = '小区' . $single_name . $floor_name . $layer_name . $room_name . '住户';
                            $auth['allTypeTxt']        = '房屋住户';
                            break;
                        default:
                            if (isset($auth['user_bind_id']) && $auth['user_bind_id']) {
                                $auth['authObjectTxt'] = '小区' . $single_name . $floor_name . $layer_name . $room_name . "[$user_name]";
                                $auth['allTypeTxt']        = '住户';
                            }
                            break;
                    }
                }
            }
        }
        $res                = [];
        $res['list']        = $list;
        $res['count']       = $count;
        $res['limit']       = $limit;
        return $res;
    }

    /**
     * 获取对应设备未绑定的小区工作人员
     * @param string $device_id
     * @param array $param
     * @return array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deviceVillageWorksAuth(string $device_id, array $param) {
        /** @var string village_id 小区id */
        $village_id            = isset($param['village_id']) ? $param['village_id'] : 0;
        /** @var string device_equipment_type 设备类型 人脸face 监控camera */
        $device_equipment_type = isset($param['device_equipment_type']) ? $param['device_equipment_type'] : 'face';
        $where = [];
        $where[] = ['device_equipment_type', '=', $device_equipment_type];
        $where[] = ['device_id', '=', $device_id];
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['work_id', '>', 0];
        $where[] = ['delete_time', '=', 0];

        $deviceAuth = new DeviceAuth();
        $workIdArr  = $deviceAuth->getOneColumn($where,'work_id');
        if (!$workIdArr) {
            $workIdArr = [];
        }
        $where_work   = [];
        $where_work[] = ['status', '=', 1];
        $where_work[] = ['village_id', '=', $village_id];
        $where_work[] = ['is_del', '=', 0];
        $where_work[] = ['wid', 'not in', $workIdArr];
        $dbHouseWorker = new HouseWorker();
        $work_field = 'wid,name';
        $work_list = $dbHouseWorker->getWorkList($where_work,'', $work_field);
        if ($work_list && !is_array($work_list)) {
            $work_list = $work_list->toArray();
        }
        return ['work_list' => $work_list];
    }

    /**
     * 添加设备相关权限
     * @param string $device_id
     * @param array $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addDeviceAuth(string $device_id, array $param) {
        /** @var string village_id 小区id */
        $village_id            = isset($param['village_id']) ? $param['village_id'] : 0;
        /** @var string device_equipment_type 设备类型 人脸face 监控camera */
        $device_equipment_type = isset($param['device_equipment_type']) ? $param['device_equipment_type'] : 'face';
        /** @var string workIds 工作人员集合 */
        $workIds               = isset($param['workIds']) ? $param['workIds'] : [];
        /** @var string checkedKeys 工作人员集合 */
        $checkedKeys           = isset($param['checkedKeys']) ? $param['checkedKeys'] : [];
        $nowTime               = $this->nowTime ? $this->nowTime : time();
        switch ($device_equipment_type) {
            case 'face':
                $whereFace  = [
                    'device_id' => $device_id
                ];
                $faceField  = 'device_id,device_name,device_type,device_sn,village_id,floor_id,device_status,thirdProtocol';
                $deviceInfo = (new HouseFaceDeviceService())->getHouseFaceDeviceInfo($whereFace, $faceField);
                if ($deviceInfo && !is_array($deviceInfo)) {
                    $deviceInfo = $deviceInfo->toArray();
                }
                if (!empty($deviceInfo)) {
                    $deviceName = $deviceInfo['device_name'] . '[人脸]';
                }
                break;
        }
        if (!empty($workIds)) {
            // todo  处理工作人员记录
            $where = [];
            $where[] = ['device_equipment_type', '=', $device_equipment_type];
            $where[] = ['device_id', '=', $device_id];
            $where[] = ['village_id', '=', $village_id];
            $where[] = ['work_id', 'in', $workIds];
            $deviceAuth = new DeviceAuth();
            // 删除之前关联的
            $deviceAuth->updateThis($where, ['delete_time' => $nowTime]);

            $bindAuth = [
                'device_equipment_type' => $device_equipment_type,
                'device_id'             => $device_id,
                'device_sn'             => isset($deviceInfo['device_sn']) ? $deviceInfo['device_sn'] : '',
                'village_id'            => $village_id,
                'all_type'              => 'work',
                'all_sort'              => '0',
                'add_time'              => $nowTime,
            ];
            $addAll = [];
            foreach ($workIds as $work_id) {
                $bindAuth['work_id'] = $work_id;
                $addAll[] = $bindAuth;
            }
            if (!empty($addAll)) {
                $deviceAuth->addAll($addAll);
            }
        }

        if (!empty($checkedKeys)) {
            $singleIdArr   = [];
            $floorIdArr    = [];
            $layerIdArr    = [];
            $roomIdArr     = [];
            foreach ($checkedKeys as $val) {
                $arr = explode('|',$val);
                if (isset($arr[2]) && $arr[2]) {
                    switch ($arr[2]) {
                        case 'single':
                            $singleIdArr[] = $arr[0];
                            break;
                        case 'floor':
                            $floorIdArr[]  = $arr[0];
                            break;
                        case 'layer':
                            $layerIdArr[]  = $arr[0];
                            break;
                        case 'room':
                            $roomIdArr[]   = $arr[0];
                            break;
                    }
                }
            }
            // todo 查询下楼栋
            $db_single   = new HouseVillageSingle();
            $whereSingle    = [];
            $whereSingle[]  = ['status', '=', 1];
            $whereSingle[]  = ['village_id', '=', $village_id];
            $singleArr      = $db_single->getList($whereSingle, 'id,single_name,village_id,id as single_id','id DESC');
            if($singleArr && !is_array($singleArr)) {
                $singleArr = $singleArr->toArray();
            }
            $houseSingles = [];
            $singleIdInfo = [];
            $singleIdAll  = [];
            foreach ($singleArr as $single) {
                if (!isset($houseSingles[$single['village_id']])) {
                    $houseSingles[$single['village_id']] = [];
                }
                $houseSingles[$single['village_id']][]   = strval($single['single_id']);
                $singleIdInfo[$single['single_id']]      = $single;
                $singleIdAll[]                           = $single['single_id'];
            }
            // todo 查询下单元
            $db_floor      = new HouseVillageFloor();
            $whereFloor    = [];
            $whereFloor[]  = ['status', '=', 1];
            $whereFloor[]  = ['village_id', '=', $village_id];
            $whereFloor[]  = ['single_id', 'in', $singleIdAll];
            $floorArr      = $db_floor->getList($whereFloor, 'floor_id,floor_name,village_id,single_id');
            if($floorArr && !is_array($floorArr)) {
                $floorArr = $floorArr->toArray();
            }
            $singleFloors = [];
            $floorIdInfo  = [];
            $floorIdAll   = [];
            foreach ($floorArr as $floor) {
                if (!isset($singleFloors[$floor['single_id']])) {
                    $singleFloors[$floor['single_id']] = [];
                }
                $singleFloors[$floor['single_id']][]   = strval($floor['floor_id']);
                $floorIdInfo[$floor['floor_id']]       = $floor;
                $floorIdAll[]                          = $floor['floor_id'];
            }
            // todo 查询下楼层
            $db_layer      = new HouseVillageLayer();
            $whereLayer    = [];
            $whereLayer[]  = ['status', '=', 1];
            $whereLayer[]  = ['village_id', '=', $village_id];
            $whereLayer[]  = ['floor_id', 'in', $floorIdAll];
            $layerArr      = $db_layer->getList($whereLayer, 'id,layer_name,village_id,single_id,floor_id,id as layer_id');
            if($layerArr && !is_array($layerArr)) {
                $layerArr = $layerArr->toArray();
            }
            $floorLayers = [];
            $layerIdInfo = [];
            $layerIdAll  = [];
            foreach ($layerArr as $layer) {
                if (!isset($floorLayers[$layer['floor_id']])) {
                    $floorLayers[$layer['floor_id']] = [];
                }
                $floorLayers[$layer['floor_id']][]   = strval($layer['layer_id']);
                $layerIdInfo[$layer['layer_id']]     = $layer;
                $layerIdAll[]                        = $layer['layer_id'];
            }

            // todo 查询下房间
            $db_room      = new HouseVillageUserVacancy();
            $whereRoom    = [];
            $whereRoom[]  = ['status', 'in', [1,2,3]];
            $whereRoom[]  = ['is_del', '=', 0];
            $whereRoom[]  = ['village_id', '=', $village_id];
            $whereRoom[]  = ['layer_id', 'in', $layerIdAll];
            $roomArr      = $db_room->getRoomList($whereRoom, 'pigcms_id,room,village_id,single_id,floor_id,layer_id,pigcms_id as room_id','pigcms_id DESC');
            if($roomArr && !is_array($roomArr)) {
                $roomArr = $roomArr->toArray();
            }
            $layerRooms = [];
            $roomIdInfo = [];
            $roomIdAll  = [];
            foreach ($roomArr as $room) {
                if (!isset($layerRooms[$room['layer_id']])) {
                    $layerRooms[$room['layer_id']] = [];
                }
                $layerRooms[$room['layer_id']][]   = strval($room['room_id']);
                $roomIdInfo[$room['room_id']]      = $room;
                $roomIdAll[]                       = $room['room_id'];
            }

            $allRooms   = [];
            $allLayers  = [];
            $allFloors  = [];
            $allSingles = [];
            $deviceAuth = new DeviceAuth();

            if (count($roomIdArr) == count($roomIdAll) && count($layerIdArr) == count($layerIdAll) && count($floorIdArr) == count($floorIdAll) && count($singleIdArr) == count($singleIdAll)) {
                // todo  其他所有记录先清除
                $where = [];
                $where[] = ['device_equipment_type', '=', $device_equipment_type];
                $where[] = ['device_id', '=', $device_id];
                $where[] = ['village_id', '=', $village_id];
                $where[] = ['delete_time', '=', 0];
                $where[] = ['all_type', 'not in', ['allWorks', 'work']];
                // 删除之前关联的
                $deviceAuth->updateThis($where, ['delete_time' => $nowTime]);

                $bindAuth = [
                    'device_equipment_type' => $device_equipment_type,
                    'device_id'             => $device_id,
                    'device_sn'             => isset($deviceInfo['device_sn']) ? $deviceInfo['device_sn'] : '',
                    'village_id'            => $village_id,
                    'all_type'              => 'allVillages',
                    'all_sort'              => '100',
                    'add_time'              => $nowTime,
                ];
                $deviceAuth->add($bindAuth);
            } elseif (count($roomIdArr) == count($roomIdAll) && count($layerIdArr) == count($layerIdAll) && count($floorIdArr) == count($floorIdAll)) {
                // todo  其他所有记录先清除
                $where = [];
                $where[] = ['device_equipment_type', '=', $device_equipment_type];
                $where[] = ['device_id', '=', $device_id];
                $where[] = ['village_id', '=', $village_id];
                $where[] = ['delete_time', '=', 0];
                $where[] = ['all_type', 'not in', ['allWorks', 'work']];
                // 删除之前关联的
                $deviceAuth->updateThis($where, ['delete_time' => $nowTime]);
                // 这样的记录全部选择的楼栋
                $bindAuth = [
                    'device_equipment_type' => $device_equipment_type,
                    'device_id'             => $device_id,
                    'device_sn'             => isset($deviceInfo['device_sn']) ? $deviceInfo['device_sn'] : '',
                    'village_id'            => $village_id,
                    'all_type'              => 'allSingles',
                    'all_sort'              => '90',
                    'add_time'              => $nowTime,
                ];
                $addAll = [];
                foreach ($singleIdArr as $single_id) {
                    $bindAuth['single_id'] = $single_id;
                    $addAll[] = $bindAuth;
                }
                if (!empty($addAll)) {
                    $deviceAuth->addAll($addAll);
                }
            } elseif (count($roomIdArr) == count($roomIdAll) && count($layerIdArr) == count($layerIdAll)) {
                // todo  其他所有记录先清除
                $where = [];
                $where[] = ['device_equipment_type', '=', $device_equipment_type];
                $where[] = ['device_id', '=', $device_id];
                $where[] = ['village_id', '=', $village_id];
                $where[] = ['delete_time', '=', 0];
                $where[] = ['all_type', 'not in', ['allWorks', 'work']];
                // 删除之前关联的
                $deviceAuth->updateThis($where, ['delete_time' => $nowTime]);
                // 这样的记录全部选择的单元
                $bindAuth = [
                    'device_equipment_type' => $device_equipment_type,
                    'device_id'             => $device_id,
                    'device_sn'             => isset($deviceInfo['device_sn']) ? $deviceInfo['device_sn'] : '',
                    'village_id'            => $village_id,
                    'all_type'              => 'allFloors',
                    'all_sort'              => '80',
                    'add_time'              => $nowTime,
                ];
                $addAll = [];
                foreach ($floorIdArr as $floor_id) {
                    $bindAuth['floor_id'] = $floor_id;
                    $addAll[] = $bindAuth;
                }
                if (!empty($addAll)) {
                    $deviceAuth->addAll($addAll);
                }
            } elseif (count($roomIdArr) == count($roomIdAll)) {
                // todo  其他所有记录先清除
                $where = [];
                $where[] = ['device_equipment_type', '=', $device_equipment_type];
                $where[] = ['device_id', '=', $device_id];
                $where[] = ['village_id', '=', $village_id];
                $where[] = ['delete_time', '=', 0];
                $where[] = ['all_type', 'not in', ['allWorks', 'work']];
                // 删除之前关联的
                $deviceAuth->updateThis($where, ['delete_time' => $nowTime]);
                // 这样的记录全部选择的楼层
                $bindAuth = [
                    'device_equipment_type' => $device_equipment_type,
                    'device_id'             => $device_id,
                    'device_sn'             => isset($deviceInfo['device_sn']) ? $deviceInfo['device_sn'] : '',
                    'village_id'            => $village_id,
                    'all_type'              => 'allLayers',
                    'all_sort'              => '70',
                    'add_time'              => $nowTime,
                ];
                $addAll = [];
                foreach ($layerIdArr as $layer_id) {
                    $bindAuth['layer_id'] = $layer_id;
                    $addAll[] = $bindAuth;
                }
                if (!empty($addAll)) {
                    $deviceAuth->addAll($addAll);
                }
            } elseif (count($roomIdArr) != count($roomIdAll) && count($layerIdArr) != count($layerIdAll) && count($floorIdArr) != count($floorIdAll) && count($singleIdArr) != count($singleIdAll)) {
                // todo 如果每一级都不相等   从房间开始
                $recordLayer  = [];
                $recordFloor  = [];
                $recordSingle = [];
                foreach ($roomIdArr as $roomId) {
                    if (!isset($roomIdInfo[$roomId]) || !$roomIdInfo[$roomId]) {
                        continue;
                    }
                    $room     = $roomIdInfo[$roomId];
                    $layerId  = $room['layer_id'];
                    $floorId  = $room['floor_id'];
                    $singleId = $room['single_id'];
                    if (isset($allSingles[$singleId]) && $allSingles[$singleId]) {
                        continue;
                    }
                    if (isset($allFloors[$floorId]) && $allFloors[$floorId]) {
                        continue;
                    }
                    if (isset($allLayers[$layerId]) && $allLayers[$layerId]) {
                        continue;
                    }
                    if (isset($layerRooms[$layerId]) && isset($floorLayers[$floorId]) && isset($singleFloors[$singleId]) && empty(array_diff($layerRooms[$layerId], $roomIdArr)) && empty(array_diff($floorLayers[$floorId], $layerIdArr)) && empty(array_diff($singleFloors[$singleId], $floorIdArr))) {
                        // 如果对应楼层全员选择到了最上面  记录楼栋为全选
                        $allSingles[$singleId] = $singleId;
                    } elseif (isset($layerRooms[$layerId]) && isset($floorLayers[$floorId]) && empty(array_diff($layerRooms[$layerId], $roomIdArr)) && empty(array_diff($floorLayers[$floorId], $layerIdArr))) {
                        // 如果对应楼层全员选择到了单元  记录单元为全选
                        $allFloors[$floorId]   = $floorId;
                    } elseif (isset($layerRooms[$layerId]) && empty(array_diff($layerRooms[$layerId], $roomIdArr))) {
                        // 如果对应楼层全员选择到了楼层  记录楼层为全选
                        $allLayers[$layerId]   = $layerId;
                    } else {
                        // 没有上级全选 记录为房间
                        $allRooms[$roomId]     = $roomId;
                    }
                    // 记录处理过的层级
                    $recordLayer[$layerId]   = $layerId;
                    $recordFloor[$floorId]   = $floorId;
                    $recordSingle[$singleId] = $singleId;
                }
                if (count($layerIdArr) != count($recordLayer)) {
                    foreach ($layerIdArr as $layerId) {
                        if (!isset($layerIdInfo[$layerId]) || !$layerIdInfo[$layerId]) {
                            continue;
                        }
                        if (isset($recordLayer[$layerId]) && $recordLayer[$layerId]) {
                            continue;
                        }
                        $layer     = $layerIdInfo[$layerId];
                        $floorId   = $layer['floor_id'];
                        $singleId  = $layer['single_id'];
                        if (isset($allSingles[$singleId]) && $allSingles[$singleId]) {
                            continue;
                        }
                        if (isset($allFloors[$floorId]) && $allFloors[$floorId]) {
                            continue;
                        }
                        if (isset($allLayers[$layerId]) && $allLayers[$layerId]) {
                            continue;
                        }
                        if (isset($floorLayers[$floorId]) && isset($singleFloors[$singleId]) && empty(array_diff($floorLayers[$floorId], $layerIdArr)) && empty(array_diff($singleFloors[$singleId], $floorIdArr))) {
                            // 如果对应楼层全员选择到了最上面  记录楼栋为全选
                            $allSingles[$singleId] = $singleId;
                        } elseif (isset($floorLayers[$floorId]) && empty(array_diff($floorLayers[$floorId], $layerIdArr))) {
                            // 如果对应楼层全员选择到了单元  记录单元为全选
                            $allFloors[$floorId]   = $floorId;
                        } else {
                            // 没有上级全选 记录为楼层
                            $allLayers[$layerId]   = $layerId;
                        }
                        // 记录处理过的层级
                        $recordLayer[$layerId]   = $layerId;
                        $recordFloor[$floorId]   = $floorId;
                        $recordSingle[$singleId] = $singleId;
                    }
                }
                if (count($floorIdArr) != count($recordFloor)) {
                    foreach ($floorIdArr as $floorId) {
                        if (!isset($floorIdInfo[$floorId]) || !$floorIdInfo[$floorId]) {
                            continue;
                        }
                        if (isset($recordFloor[$floorId]) && $recordFloor[$floorId]) {
                            continue;
                        }
                        $floor     = $floorIdInfo[$floorId];
                        $singleId  = $floor['single_id'];
                        if (isset($allSingles[$singleId]) && $allSingles[$singleId]) {
                            continue;
                        }
                        if (isset($allFloors[$floorId]) && $allFloors[$floorId]) {
                            continue;
                        }
                        if (isset($singleFloors[$singleId]) && empty(array_diff($singleFloors[$singleId], $floorIdArr))) {
                            // 如果对应楼层全员选择到了最上面  记录楼栋为全选
                            $allSingles[$singleId] = $singleId;
                        } else {
                            // 记录单元为全选
                            $allFloors[$floorId]   = $floorId;
                        }
                        // 记录处理过的层级
                        $recordFloor[$floorId]   = $floorId;
                        $recordSingle[$singleId] = $singleId;
                    }
                }
                if (count($singleIdArr) != count($recordSingle)) {
                    foreach ($singleIdArr as $singleId) {
                        if (!isset($singleIdInfo[$singleId]) || !$singleIdInfo[$singleId]) {
                            continue;
                        }
                        if (isset($recordSingle[$singleId]) && $recordSingle[$singleId]) {
                            continue;
                        }
                        if (isset($allSingles[$singleId]) && $allSingles[$singleId]) {
                            continue;
                        }
                        // 如果对应楼层全员选择到了最上面  记录楼栋为全选
                        $allSingles[$singleId] = $singleId;
                    }
                }
            }

            $this->device_equipment_type = $device_equipment_type;
            $this->device_id             = $device_id;
            $this->device_sn             = isset($deviceInfo['device_sn']) ? $deviceInfo['device_sn'] : '';
            $this->village_id            = $village_id;
            if (!empty($allSingles)) {
                $this->allTypeKey        = 'allSingles';
                $allSingles = array_values($allSingles);
                $authParam = [
                    'nowTime'   => $nowTime,
                    'dataIdArr' => $allSingles,
                    'dataIdMsg' => $singleIdInfo,
                ];
                $this->filterDeviceAuthData($authParam);
            }
            if (!empty($allFloors)) {
                $this->allTypeKey        = 'allFloors';
                $allFloors = array_values($allFloors);
                $authParam = [
                    'nowTime'   => $nowTime,
                    'dataIdArr' => $allFloors,
                    'dataIdMsg' => $floorIdInfo,
                ];
                $this->filterDeviceAuthData($authParam);
            }
            if (!empty($allLayers)) {
                $this->allTypeKey        = 'allLayers';
                $allLayers = array_values($allLayers);
                $authParam = [
                    'nowTime'   => $nowTime,
                    'dataIdArr' => $allLayers,
                    'dataIdMsg' => $layerIdInfo,
                ];
                $this->filterDeviceAuthData($authParam);
            }
            if (!empty($allRooms)) {
                $this->allTypeKey        = 'allRooms';
                $allRooms = array_values($allRooms);
                $authParam = [
                    'nowTime'   => $nowTime,
                    'dataIdArr' => $allRooms,
                    'dataIdMsg' => $roomIdInfo,
                ];
                $this->filterDeviceAuthData($authParam);
            }
        }

        return [
            'allSingles' => $allSingles,
            'allFloors'  => $allFloors,
            'allLayers'  => $allLayers,
            'allRooms'   => $allRooms,
            'workIds'   => $workIds,
        ];
    }

    /**
     * 删除权限
     * @param string $device_id
     * @param array $param
     * @return bool
     * @throws \think\Exception
     */
    public function delDeviceAuth(string $device_id, array $param) {
        /** @var string village_id 小区id */
        $village_id            = isset($param['village_id']) ? $param['village_id'] : 0;
        /** @var string device_equipment_type 设备类型 人脸face 监控camera */
        $device_equipment_type = isset($param['device_equipment_type']) ? $param['device_equipment_type'] : 'face';
        /** @var string auth_id 权限id */
        $auth_id               = isset($param['auth_id']) ? $param['auth_id'] : 0;

        $nowTime               = $this->nowTime ? $this->nowTime : time();
        if (!$this->deviceAuth) {
            $this->deviceAuth = new DeviceAuth();
        }
        $where = [];
        $where[] = ['device_equipment_type', '=', $device_equipment_type];
        $where[] = ['device_id', '=', $device_id];
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['auth_id', '=', $auth_id];
        $where[] = ['delete_time', '=', 0];
        // 删除之前关联的
        $update =  $this->deviceAuth->updateThis($where, ['delete_time' => $nowTime]);
        if ($update == false) {
            throw new \think\Exception("删除失败");
        }
        return true;
    }

    /**
     * 同步大华云睿 人员相关信息
     * @param array $param
     * @return array|bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addPersonToDhYunRuiCloud(array $param) {
        $param = $this->filterCommonParam($param);
        $houseVillageUserBindService = new HouseVillageUserBindService();
        if (!$this->village_third_id) {
            // 如果没有  小区 同步到第三方的云对应id
            $villageAboutInfo = $this->filterVillageToData();
        }
        if (!$this->village_third_id) {
            if($param['log_id']){
                (new OwnerService())->updateLog(['log_id'=>$param['log_id']],[
                    'status'=>2,
                    'msg'=>'小区未同步',
                    'last_time'=>time()
                ]);
            }
            $updateBindParam = [
                'face_img_status' => 2,
                'face_img_reason' => '小区未同步',
            ];
            $houseVillageUserBindService->saveUserBind(['pigcms_id' => $this->village_user_bind_id], $updateBindParam);
            return $this->backData($param, '小区未绑定', 1005);
        }
        if (!$this->room_third_code) {
            // 如果没有 房屋 同步到第三方的云对应编号
            $roomAboutInfo = $this->filterRoomToData();
        }
        if ($this->user_id) {
            $userIdAboutInfo = $this->filterUserToDataFromUid();
            if (isset($userIdAboutInfo['person_json']) && $userIdAboutInfo['person_json']) {
                $person_json = $userIdAboutInfo['person_json'];
            }
        }
        if ($this->village_user_bind_id) {
            $userAboutInfo = $this->filterUserToDataFromHouse();
            if (isset($userAboutInfo['person_json']) && $userAboutInfo['person_json']) {
                $person_json = $userAboutInfo['person_json'];
            }
        }
        $userParam = $this->filterVillageBindUser($param);
        if(empty($userParam)) {
            $this->record_bind_type  = DeviceConst::BIND_FACE_BIND_USER_BIND_CARD;
            $this->record_bind_id    = $this->village_user_bind_id;
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
            $this->recordDeviceBindFilterBox($param);
            return false;
        }
        fdump_api($userParam,'$userParam');
        if (isset($userParam['is_work'])) {
            if ($userParam['is_work'] != 1) {
                if (!$this->room_third_code) {
                    if($param['log_id']){
                        (new OwnerService())->updateLog(['log_id'=>$param['log_id']],[
                            'status'=>2,
                            'msg'=>'房屋未绑定，前往 智能硬件>云睿数据同步 中绑定对应房间',
                            'last_time'=>time()
                        ]);
                    }
                    $updateBindParam = [
                        'face_img_status' => 2,
                        'face_img_reason' => '房屋未绑定，前往 智能硬件>云睿数据同步 中绑定对应房间',
                    ];
                    $houseVillageUserBindService->saveUserBind(['pigcms_id' => $this->village_user_bind_id], $updateBindParam);
                    return $this->backData($param, '房屋未绑定', 1006);
                }
            }
            unset($userParam['is_work']);
        }
        $personJsonParam = $userParam;
        if (isset($userParam['ic_card']) && $userParam['ic_card']) {
            $ic_card = $personJsonParam['ic_card'];
            unset($personJsonParam['ic_card']);
        }
        if (isset($personJsonParam['id']) && $personJsonParam['id']) {
            unset($personJsonParam['id']);
        }
        $person_json_md5 = md5(json_encode([$personJsonParam, date('Y-m-d H', time())], JSON_UNESCAPED_UNICODE));
        $faceDHYunRuiCloudDeviceService = new FaceDHYunRuiCloudDeviceService();
        if (isset($person_json) && $person_json && $person_json == $person_json_md5) {
            // todo 如果同步数据相同  直接进行下一步
            if (!isset($userAboutInfo) || empty($userAboutInfo)) {
                // 补充下绑定房屋
                $addHouseParam = [
                    'personFileId' => $this->user_third_id,
                    'houseInfo'    => [
                        'orgCode' => $this->room_third_code,
                    ],
                ];
                $addResult = $faceDHYunRuiCloudDeviceService->addHouse($addHouseParam);
                if (isset($addResult['success']) && $addResult['success']) {
                    $recordParam = [
                        'person_json_md5' => $person_json_md5,
                    ];
                    $this->recordDHPersonFile($this->user_third_id, $this->user_third_code,DeviceConst::DH_PIG_CMS_ID_CLOUD_USER, $this->village_user_bind_id, $recordParam);
                }
            }
        } else {
            $userParam['uid'] = $this->user_id;


            $cacheTag  = DahuaConst::DH_JOB_REDIS_TAG;
            $cacheKey  = DahuaConst::DH_JOB_REDIS_PERSON_PROFILE_KEY . md5(\json_encode($userParam));
            $personFileId  = Cache::store('redis')->get($cacheKey);
            if (isset($personFileId) && $personFileId) {
                $result['personFileId'] = $personFileId;
//                fdump_api(['cacheTag' => $cacheTag, 'cacheKey' => $cacheKey, 'personFileId' => $personFileId, 'userParam' => $userParam], '$cache1',1);
            } else {
                $result = $faceDHYunRuiCloudDeviceService->addPersonProfile($userParam);
                fdump_api(['userParam' => $userParam, 'result' => $result], '$addPersonProfileResult',1);
            }
            if (isset($result['errMsg']) && $result['errMsg'] == '所属组织可能已被删除，请更换所属组织或者刷新后重建' && isset($userParam['id'])) {
                unset($userParam['id']);
                $result = $faceDHYunRuiCloudDeviceService->addPersonProfile($userParam);
                fdump_api(['tip' => '2次同步','userParam' => $userParam, 'result' => $result], '$addPersonProfileResult',1);
            }

            if (isset($result['personFileId']) && $result['personFileId']) {
                $personFileId = $result['personFileId'];
                Cache::store('redis')->tag($cacheTag)->set($cacheKey, $personFileId, 3600);
                $this->user_third_id = $personFileId;
                $third_id     = isset($result['third_id']) && $result['third_id'] ? $result['third_id'] : '';
                $recordParam = [
                    'person_json_md5' => $person_json_md5,
                ];
                // 记录 平台用户身份同步  一个平台uid为主记录
                $this->recordDHPersonFile($personFileId, $third_id,DeviceConst::DH_UID_CLOUD_USER, $this->user_id, $recordParam);
                // 记录 小区住户身份同步  一个小区绑定住户表pigcms_id为主记录 一个人可能对应多个
                $this->recordDHPersonFile($personFileId, $third_id,DeviceConst::DH_PIG_CMS_ID_CLOUD_USER, $this->village_user_bind_id, $recordParam);
            } else {
                if($param['log_id']){
                    (new OwnerService())->updateLog(['log_id'=>$param['log_id']],[
                        'status'=>2,
                        'msg'=>'人证信息添加变更失败',
                        'last_time'=>time()
                    ]);
                }
                $updateBindParam = [
                    'face_img_status' => 2,
                    'face_img_reason' => '人证信息添加变更失败',
                ];
                $houseVillageUserBindService->saveUserBind(['pigcms_id' => $this->village_user_bind_id], $updateBindParam);
            }
        }
        if (isset($ic_card) && $ic_card) {
            // todo 处理  人证信息
            $userCardAboutInfo = $this->filterUserCardToDataFromHouse();
            $personCards = $this->filterUserCards();
            if ($personCards) {
                $card_json_md5 = md5(json_encode([$personCards, date('Y-m-d H', time())], JSON_UNESCAPED_UNICODE));
            }
            if (isset($card_json_md5) && isset($userCardAboutInfo['card_json']) && $userCardAboutInfo['card_json'] == $card_json_md5) {
                // todo 人证信息同步过 跳过
            } else {
                $identityParam = [
                    'personFileId' => $this->user_third_id,
                    'orgCode'      => $this->room_third_code,
                    'storeId'      => $this->village_third_id,
                ];
                if ($personCards) {
                    $identityParam['personCards'] = $personCards;
                }
                if (isset($userCardAboutInfo['id'])) {
                    // todo 走更新逻辑
                    $identityParam['id'] = $this->user_third_id;
                }
                $identity = $faceDHYunRuiCloudDeviceService->addPersonIdentity($identityParam);
            }
            $this->recordDHPersonCards($personCards,DeviceConst::DH_PERSON_IDENTITY_CLOUD_USER, $this->user_id);
        }
        // 判断人员是否符合同步条件
        // 同步人员授权
        if ($this->device_id || $this->device_sn) {
            $judgeAuth = $this->filterDhUserBindAuth($param);
            if (!$judgeAuth) {
                if($param['log_id']){
                    (new OwnerService())->updateLog(['log_id'=>$param['log_id']],[
                        'status'=>2,
                        'msg'=>'对应房屋无可同步设备',
                        'last_time'=>time()
                    ]);
                }
                $updateBindParam = [
                    'face_img_status' => 2,
                    'face_img_reason' => '对应房屋无可同步设备',
                ];
                $houseVillageUserBindService->saveUserBind(['pigcms_id' => $this->village_user_bind_id], $updateBindParam);
                return true;
            }
            $param['personFileId'] = $this->user_third_id;
            $param['jobType']      = 'addDhAuth';
            $this->traitCommonDHLaterToJob($param, 5);
        }
        return $param;
    }

    /**
     * 远程开门
     * @param $device_id
     * @param int $pigcms_id
     * @return array
     * @throws \think\Exception
     */
    public function openFaceDoor($device_id,$pigcms_id = 0) {
        $whereFace  = [
            'device_id' => $device_id
        ];
        $faceField  = 'device_id,device_name,device_type,device_sn,village_id,floor_id,device_status,thirdProtocol,cloud_device_id,cloud_group_id,thirdAddress';
        $deviceInfo = (new HouseFaceDeviceService())->getHouseFaceDeviceInfo($whereFace, $faceField);
        if ($deviceInfo && !is_array($deviceInfo)) {
            $deviceInfo = $deviceInfo->toArray();
        }
        if (empty($deviceInfo)) {
            return $this->backData(['device_id' => $device_id,'pigcms_id' => $pigcms_id], '设备信息不全', 1001);
        }
        if (empty($deviceInfo['thirdProtocol'])) {
            return $this->backData(['device_id' => $device_id,'pigcms_id' => $pigcms_id], '协议信息不全', 1003);
        }
        switch ($deviceInfo['thirdProtocol']) {
            case DeviceConst::HIK_DEVICE_GATEWAY:
            case DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM:
                $result = (new HikGateWayDeviceService())->remoteControl($deviceInfo);
                if (!isset($result['code']) || !$result['code'] || (isset($result['code']) && $result['code'] == '200')) {
                    return $this->backData(['device_id' => $device_id, 'pigcms_id' => $pigcms_id], '开门成功');
                } else {
                    if (isset($result['errMsg']) && $result['message']) {
                        $errMsg = "开门失败({$result['message']})";
                    } else {
                        $errMsg = "开门失败";
                    }
                    return $this->backData(['device_id' => $device_id, 'pigcms_id' => $pigcms_id], $errMsg, 1020);
                }
                break;
            case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                $result = (new DeviceHkNeiBuHandleService())->gateControl($device_id, $pigcms_id, $deviceInfo, $deviceInfo['thirdProtocol']);
                if (isset($result['code']) && $result['code']=='200') {
                    return $this->backData(['device_id' => $device_id,'pigcms_id' => $pigcms_id], '开门成功');
                } else {
                    if (isset($result['errMsg']) && $result['message']) {
                        $errMsg = "开门失败({$result['message']})";
                    } else {
                        $errMsg = "开门失败";
                    }
                    return $this->backData(['device_id' => $device_id,'pigcms_id' => $pigcms_id], $errMsg, 1020);
                }
                break;
            case DeviceConst::DH_YUNRUI:
                if  ($pigcms_id) {
                    $this->village_user_bind_id = $pigcms_id;
                    $this->filterUserToDataFromHouse();
                }
                $params = [
                    'deviceId' => $deviceInfo['device_sn'],
                ];
                if ($this->user_third_id) {
                    $params['userType']     = 1;
                    $params['personFileId'] = $this->user_third_id;
                } else {
                    $params['userType']     = 0;
                }
                $result = (new FaceDHYunRuiCloudDeviceService())->remoteOpenDoor($params);
                if (isset($result['success']) && $result['success']) {
                    return $this->backData(['device_id' => $device_id,'pigcms_id' => $pigcms_id], '开门成功');
                } else {
                    if (isset($result['errMsg']) && $result['errMsg']) {
                        $errMsg = "开门失败({$result['errMsg']})";
                    } else {
                        $errMsg = "开门失败";
                    }
                    return $this->backData(['device_id' => $device_id,'pigcms_id' => $pigcms_id], $errMsg, 1020);
                }
            default:
                return $this->backData(['device_id' => $device_id,'pigcms_id' => $pigcms_id], '协议信息不全', 1003);
                
        }
    }
    
    public function getDeviceStatusAll() {
        $judgeThirdProtocolDeviceConfig = $this->judgeThirdProtocolDeviceConfig();
        if ($judgeThirdProtocolDeviceConfig) {
            $where_device = [];
            $where_device[] = ['thirdProtocol','>',11];
            $where_device[] = ['is_del','=',0];
            $where_device[] = ['device_status','in',[1,2,3]];
            $where_device[] = ['cloud_device_id','<>',''];
            $db_house_face_device = new HouseFaceDevice();//人脸门禁表
            $face_list = $db_house_face_device->getList($where_device,'device_id,device_name,device_type,device_sn,village_id,cloud_device_id,thirdProtocol',1);
            if ($face_list && !is_array($face_list)) {
                $face_list = $face_list->toArray();
            }
            foreach ($face_list as $item) {
                $queueData = [
                    'village_id'      => $item['village_id'],
                    'device_id'       => $item['device_id'],
                    'device_sn'       => $item['device_sn'],
                    'thirdProtocol'   => $item['thirdProtocol'],
                    'cloud_device_id' => $item['cloud_device_id'],
                    'deviceType'      => DeviceConst::DEVICE_TYPE_FACE,
                    'jobType'         => 'getDeviceStatus',
                ];
                if ($item['thirdProtocol'] == DeviceConst::HIK_YUNMO_NEIBU_SHEQU) {
                    $this->traitCommonHikToJob($queueData);
                } else {
                    $this->traitCommonDHDeviceStatus($queueData);
                }
                // todo 临时调试 直接执行  要还原回 调取队列执行
//                $this->getDeviceStatus($queueData);
            }
            $where_device = [];
            $where_device[] = ['thirdProtocol','>',0];
            $where_device[] = ['is_del','=',0];
            $where_device[] = ['device_status','in',[1,2,3]];
            $alarmDeviceService = new AlarmDeviceService();
            $device_list = $alarmDeviceService->getAlarmDeviceList($where_device,true,'device_id ASC', 0, 0);
            if ($device_list && !is_array($device_list)) {
                $device_list = $device_list->toArray();
            }
            foreach ($device_list as $item1) {
                $queueData = [
                    'village_id'      => $item1['village_id'],
                    'device_id'       => $item1['device_id'],
                    'device_sn'       => $item1['device_serial'],
                    'thirdProtocol'   => $item1['third_protocol'],
                    'cloud_device_id' => $item1['cloud_device_id'],
                    'deviceType'      => DeviceConst::DEVICE_TYPE_ALARM,
                    'jobType'         => 'getDeviceStatus',
                ];
                if ($item['thirdProtocol'] == DeviceConst::HIK_YUNMO_NEIBU_SHEQU) {
                    $this->traitCommonHikToJob($queueData);
                } else {
                    $this->traitCommonDHDeviceStatus($queueData);
                }
                // todo 临时调试 直接执行  要还原回 调取队列执行
//                $this->getDeviceStatus($queueData);
            }
            
            
            $whereCamera = [];
            $whereCamera[] = ['thirdProtocol','>',0];
            $whereCamera[] = ['camera_status','in',[0,1,2,3]];
            $db_house_camera_device = new HouseCameraDevice();// 监控设备
            $camera_list = $db_house_camera_device->getList($whereCamera,true,0);
            if ($camera_list && !is_array($camera_list)) {
                $camera_list = $camera_list->toArray();
            }
            foreach ($camera_list as $camera) {
                $queueData = [
                    'village_id'      => $camera['village_id'],
                    'device_id'       => $camera['camera_id'],
                    'device_sn'       => $camera['camera_sn'],
                    'thirdProtocol'   => $camera['thirdProtocol'],
                    'cloud_device_id' => $camera['cloud_device_id'],
                    'deviceType'      => DeviceConst::DEVICE_TYPE_CAMERA,
                    'jobType'         => 'getDeviceStatus',
                ];
                $this->traitCommonDHDeviceStatus($queueData);
                // todo 临时调试 直接执行  要还原回 调取队列执行
//                $this->getDeviceStatus($queueData);
            }
            // 指纹锁
            $dbFingerprintDevice = new FingerprintDevice();
            $where_device = [];
            $where_device[] = ['third_protocol','=',DeviceConst::DH_YUNRUI];
            $where_device[] = ['delete_time','=',0];
            $fingerprint_list = $dbFingerprintDevice->getList($where_device,'device_id,device_name,device_sn,village_id,third_protocol,cloud_device_id',0);
            if ($fingerprint_list && !is_array($fingerprint_list)) {
                $fingerprint_list = $fingerprint_list->toArray();
            }
            foreach ($fingerprint_list as $fingerprint) {
                $queueData = [
                    'village_id'      => $fingerprint['village_id'],
                    'device_id'       => $fingerprint['device_id'],
                    'device_sn'       => $fingerprint['device_sn'],
                    'thirdProtocol'   => $fingerprint['third_protocol'],
                    'cloud_device_id' => $fingerprint['cloud_device_id'],
                    'deviceType'      => DeviceConst::DEVICE_TYPE_FINGERPRINT,
                    'jobType'         => 'getDeviceStatus',
                ];
                $this->traitCommonDHDeviceStatus($queueData);
                // todo 临时调试 直接执行  要还原回 调取队列执行
//                $this->getDeviceStatus($queueData);
            }
        }
        return true;
    }

    /**
     * 获取更新人脸/监控设备信息
     * @param array $param
     * @return bool
     */
    public function getDeviceStatus(array $param) {
        try {
            $param = $this->filterCommonParam($param);
            $deviceInfo = $this->getDeviceInfo();
            $param['deviceInfo'] = $deviceInfo;
            $thirdInfo = $this->getDeviceThirdInfo($param);
            fdump_api([ 'msg' => '获取更新人脸/监控设备信息', 'line' => __LINE__,'param'=>$param, 'thirdInfo' => $thirdInfo,'device_id'=>$this->device_id], 'hikGateWay/getDeviceStatusLog',1);
            $updateParam = isset($thirdInfo['updateParam']) && $thirdInfo['updateParam'] ? $thirdInfo['updateParam'] : [];
            $deviceThirdInfo = isset($thirdInfo['deviceThirdInfo']) && $thirdInfo['deviceThirdInfo'] ? $thirdInfo['deviceThirdInfo'] : [];
            if ($this->device_id && isset($deviceThirdInfo['online']) && 0 == $deviceThirdInfo['online']) {
                // 离线
                $reason = isset($deviceThirdInfo['online_txt']) ? $deviceThirdInfo['online_txt'] : '';
                (new DeviceAlarmEventService())->faultNotice($this->device_id, $reason, true, $this->device_equipment_type);
            } elseif ($this->device_id && isset($deviceThirdInfo['online']) && 1 == $deviceThirdInfo['online']) {
                // 在线
                (new DeviceAlarmEventService())->normalLog($this->device_id, $this->device_equipment_type);
            }
            $this->updateDeviceInfo($updateParam);
        } catch (\Exception $e) {
            $deviceThirdInfo = [];
        }
        return $deviceThirdInfo;
    }
    
    /**
     * 获取开门记录
     * @return bool
     * @throws \think\Exception
     */
    public function getDhVillageFaceOpenRecord() {
        $where_device = [];
        $where_device[] = ['thirdProtocol','=',DeviceConst::DH_YUNRUI];
        $where_device[] = ['is_del','=',0];
        //人脸门禁表
        $db_house_face_device = new HouseFaceDevice();
        $face_list = $db_house_face_device->getList($where_device,'device_id,device_name,device_type,device_sn,village_id,thirdProtocol',1);
        if ($face_list && !is_array($face_list)) {
            $face_list = $face_list->toArray();
        }
        foreach ($face_list as $item) {
            $queueData = [
                'village_id'     => $item['village_id'],
                'device_id'      => $item['device_id'],
                'device_sn'      => $item['device_sn'],
                'thirdProtocol'  => $item['thirdProtocol'],
                'deviceType'     => DeviceConst::DEVICE_TYPE_FACE,
            ];
            $this->traitCommonDHOpenDoorRecord($queueData);
        }
        // 指纹锁
        $dbFingerprintDevice = new FingerprintDevice();
        $where_device = [];
        $where_device[] = ['third_protocol','=',DeviceConst::DH_YUNRUI];
        $where_device[] = ['delete_time','=',0];
        $fingerprint_list = $dbFingerprintDevice->getList($where_device,'device_id,device_name,device_sn,village_id,third_protocol',0);
        if ($fingerprint_list && !is_array($fingerprint_list)) {
            $fingerprint_list = $fingerprint_list->toArray();
        }
        foreach ($fingerprint_list as $item1) {
            $queueData = [
                'village_id'     => $item1['village_id'],
                'device_id'      => $item1['device_id'],
                'device_sn'      => $item1['device_sn'],
                'thirdProtocol'  => $item1['third_protocol'],
                'deviceType'     => DeviceConst::DEVICE_TYPE_FINGERPRINT,
            ];
            $this->traitCommonDHOpenDoorRecord($queueData);
        }
        return true;
    }
    
    /**
     * 获取开门记录
     * @param $param
     * @return array
     * @throws \think\Exception
     */
    public function getDhWonerDoorOpenRecord($param) {
        $param         = $this->filterCommonParam($param);
        if (!$this->village_third_id) {
            // 如果没有  小区 同步到第三方的云对应id
            $villageAboutInfo = $this->filterVillageToData();
        }
        if (!$this->village_third_id) {
            return $this->backData($param, '小区未绑定', 1005);
        }
        $faceDHYunRuiCloudDeviceService = new FaceDHYunRuiCloudDeviceService();
        if (!$this->third_community_code) {
            $thirdVillageInfo = $faceDHYunRuiCloudDeviceService->getOrgByNumberOrPlaceId($this->village_third_id);
            if (isset($thirdVillageInfo['data']['code'])) {
                if (!isset($villageAboutInfo)) {
                    $villageAboutInfo = $this->filterVillageToData();
                }
                $communityCode = $thirdVillageInfo['data']['code'];
                $dbFaceBindAboutInfo = new FaceBindAboutInfo();
                $whereAbout   = [];
                $whereAbout[] = ['id', '=', $villageAboutInfo['id']];
                $updateParam  = [
                    'last_time'  => time(),
                    'account_id' => $communityCode,
                    'third_info' => json_encode($thirdVillageInfo['data'], JSON_UNESCAPED_UNICODE),
                ];
                $dbFaceBindAboutInfo->updateThis($whereAbout, $updateParam);
            } else {
                $communityCode = '';
            }
        } else {
            $communityCode = $this->third_community_code;
        }
        if ($this->device_id || $this->device_sn) {
            $infoArr = $this->handleParamDeviceInfo($param);
            if (isset($infoArr) && isset($infoArr['deviceInfo']) && $infoArr['deviceInfo']) {
                $deviceInfo       = $infoArr['deviceInfo'];
            } else {
                $deviceInfo       = [];
            }
            if (isset($deviceInfo['device_sn']) && $deviceInfo['device_sn']) {
                $this->device_sn = $param['device_sn'] = $deviceInfo['device_sn'];
            }
            if (isset($deviceInfo['device_id']) && $deviceInfo['device_id']) {
                $this->device_id = $param['device_id'] = $deviceInfo['device_id'];
            }
        }

        $params = [
            'communityCode' => $communityCode,
        ];
        $whereUserLog   = [];
        $third_protocol = DeviceConst::DH_YUNRUI;
        $whereUserLog[] = ['third_protocol', '=', $third_protocol];
        if ($this->device_id) {
            $whereUserLog[] = ['device_id', '=', $this->device_id];
        } elseif ($this->device_sn) {
            $whereUserLog[] = ['device_sn', '=', $this->device_sn];
        }
        $dbHouseUserLog = new HouseUserLog();
        $log_time_max = $dbHouseUserLog->getMax($whereUserLog,'log_time');
        if (intval($log_time_max)>1) {
            $params['startTime'] = date('Y-m-d H:i:s',$log_time_max+1);
            $params['endTime']   = date('Y-m-d H:i:s');;
        }
        if ($this->device_sn) {
            $devices = [
                [
                    'deviceId'  => $this->device_sn,
                    'channelId' => '0',
                ]
            ];
            $params['devices'] = $devices;
        }
        $record = $faceDHYunRuiCloudDeviceService->openDoorRecord($params);
    
        if (isset($record['data']['result']) && !empty($record['data']['result'])) {
            return $this->recordUserLog($record['data']['result'], $third_protocol, $param);
        }
        return $record;
    }

    /***
     * 删除设备
     * @param array $param
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deleteDevice(array $param) {
        $param           = $this->filterCommonParam($param);
        $deviceInfo      = $this->getDeviceInfo();
        switch ($this->thirdProtocol) {
            case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                if (isset($param['cloud_device_id']) && $param['cloud_device_id']) {
                    $cloud_device_id = $param['cloud_device_id'];
                } elseif ($this->device_third_id) {
                    $cloud_device_id = $this->device_third_id;
                } elseif (isset($deviceInfo['cloud_device_id'])) {
                    $cloud_device_id = $deviceInfo['cloud_device_id'];
                }
                if (isset($cloud_device_id)) {
                    $deleteResult = (new FaceHikCloudNeiBuDeviceService())->deleteDeviceToCloud($cloud_device_id);
                    if (isset($deleteResult['code']) && $deleteResult['code']=='200') {
                        return $this->backData($param, '删除成功');
                    } else {
                        $errMsg = '删除失败';
                        if (isset($deleteResult['message']) && $deleteResult['message']) {
                            $errMsg .= "({$deleteResult['message']})";
                        }
                        return $this->backData($param, $errMsg, 1019);
                    }
                } else {
                    return $this->backData($param, '缺少设备云id', 1021);
                }
            case DeviceConst::DH_YUNRUI:
                if ($this->device_sn) {
                    $deviceId = $this->device_sn;
                } elseif (isset($deviceInfo['device_sn'])) {
                    $deviceId = $deviceInfo['device_sn'];
                }
                if (isset($deviceId)) {
                    $deleteResult = (new FaceDHYunRuiCloudDeviceService())->deleteDeviceToCloud($deviceId);
                    fdump_api($deleteResult,'$deleteResult');
                    if (isset($deleteResult['success']) && $deleteResult['success']) {
                        return $this->backData($param, '删除成功');
                    } else {
                        $errMsg = '删除失败';
                        if (isset($deleteResult['errMsg']) && $deleteResult['errMsg']) {
                            $errMsg .= "({$deleteResult['errMsg']})";
                        }
                        if (isset($deleteResult['errMsg']) && '设备不存在' == $deleteResult['errMsg']) {
                            return $this->backData($param, $errMsg, 1044);
                        } else {
                            return $this->backData($param, $errMsg, 1019);
                        }
                    }
                } else {
                    return $this->backData($param, '缺少设备序列号', 1022);
                }
        }
        return $this->backData($param, '无对应删除对象');
    }
    
    public function deviceSysStepInfo(array $param) {
        $result = $this->commonDeviceSysStepInfo($param);
        return $result;
    }
    
    /**
     * 查询人员信息详情
     * @param int $pigcms_id
     * @param int $uid
     * @param int $isAddAuth
     * @param array $param
     * @return mixed
     */
    public function getDhPersonByPrfoleId($pigcms_id, $uid = 0, $isAddAuth = 0, $param = []) {
        if ($uid) {
            $this->filterUserToDataFromUid($uid);
        }
        if (!$this->user_third_id){
            $this->village_user_bind_id = $pigcms_id;
            $this->filterUserToDataFromHouse();
        }
        $result = (new FaceDHYunRuiCloudDeviceService())->getPersonByPrfoleId($this->user_third_id);
        if (isset($result['data']['personFingerprintList']) && $result['data']['personFingerprintList']) {
            $personFingerprints = $result['data']['personFingerprintList'];
        }
        if (!isset($personFingerprints) && isset($result['data']['personIdentify']['personFingerprints']) && $result['data']['personIdentify']['personFingerprints']) {
            $personFingerprints = $result['data']['personIdentify']['personFingerprints'];
        }
        if(isset($personFingerprints) && !empty($personFingerprints)) {
            $houseVillageUserBindService = new HouseVillageUserBindService();
            $whereUserBind = [];
            $whereUserBind[] = ['pigcms_id', '=', $pigcms_id];
            $filed = 'uid, village_id';
            $userBinds = $houseVillageUserBindService->getBindInfo($whereUserBind,$filed);
            if ($userBinds && !is_array($userBinds)) {
                $userBinds = $userBinds->toArray();
            }
            $uid        = isset($userBinds['uid'])        && $userBinds['uid']        ? $userBinds['uid']        : 0;
            $village_id = isset($userBinds['village_id']) && $userBinds['village_id'] ? $userBinds['village_id'] : 0;
            $fingerprintUserData = [
                'uid'            => $uid,
                'bind_type'      => 0,
                'bind_id'        => $pigcms_id,
                'from'           => 'village',
                'from_id'        => $village_id,
                'third_protocol' => DeviceConst::DH_YUNRUI,
            ];
            $fingerprint_user = new FingerprintUser();
            $whereFingerprint = [];
            $whereFingerprint[] = ['third_protocol', '=', DeviceConst::DH_YUNRUI];
            $whereFingerprint[] = ['from',           '=', 'village'];
            $whereFingerprint[] = ['from_id',        '=', $village_id];
            if ($uid) {
                $whereFingerprint[] = ['uid',        '=', $uid];
            } else  {
                $whereFingerprint[] = ['bind_id',    '=', $pigcms_id];
            }
            $fingerprint_user->deleteInfo($whereFingerprint);
            foreach ($personFingerprints as $item) {
                if (isset($item['companyId']) && $item['companyId']) {
                    $fingerprintUserData['company_id'] = $item['companyId'];
                }
                if (isset($item['fingerCode']) && $item['fingerCode']) {
                    $fingerprintUserData['finger_code'] = $item['fingerCode'];
                }
                if (isset($item['fingerCodePath']) && $item['fingerCodePath']) {
                    $fingerprintUserData['finger_code_path'] = $item['fingerCodePath'];
                }
                if (isset($item['fingerType']) && $item['fingerType']) {
                    $fingerprintUserData['finger_type'] = $item['fingerType'];
                }
                if (isset($item['id']) && $item['id']) {
                    $fingerprintUserData['cloud_finger_id'] = $item['id'];
                }
                if (isset($item['personFileId']) && $item['personFileId']) {
                    $fingerprintUserData['person_file_id'] = $item['personFileId'];
                }
                $fingerprintUserData['add_time'] = $this->nowTime;
                $fingerprint_user->add($fingerprintUserData);
            }
        } else {
            // 不存在存在指纹数据不进行下发
            $isAddAuth = 0;
            $personFingerprints = [];
        }
        if (1 == $isAddAuth) {
            $param['jobType']      = 'addDhAuth';
            $this->traitCommonDHLaterToJob($param, 5);
        }
        $result['personFingerprints'] = $personFingerprints;
        return $result;
    }
    
    public function addDhAuthJob($param) {
        $param['jobType']      = 'addDhAuth';
        return  $this->traitCommonDHLaterToJob($param, 5);
    }

    /**
     * 自动确权
     * @param string $deviceSerial
     * @param array $param
     */
    protected function hikAutoConfirm(string $deviceSerial, array $param) {
        $result = (new FaceHikCloudNeiBuDeviceService())->autoConfirm($deviceSerial, $param);
        if (isset($result['code']) && $result['code'] == 0) {
            $this->syn_status        = DeviceConst::DEVICE_AUTO_CONFIRM_SUCCESS;
            $this->syn_status_txt    = "设备自动确权成功";
            $this->err_reason        = "";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'result' => $result, 'deviceSerial' => $deviceSerial];
            $this->recordDeviceBindFilterBox($param);
            $this->step_num++;
            
            $deviceParams = $param;
            $deviceParams['step_num']            = $this->step_num + 1;
            $job_id = $this->traitCommonHikCloudDevices($deviceParams);
            if (isset($job_id)) {
                $param['job_id']       = $job_id;
            }
            $this->clearRecordDeviceBindFilter();
            $this->syn_status          = DeviceConst::BINDS_SYN_DEVICE_QUEUE;
            $this->syn_status_txt      = '下发执行同步设备队列';
            $this->line_func_txt_arr   = ['line' => __LINE__, 'funs' => __FUNCTION__];
            $this->recordDeviceBindFilterBox($param);

            switch ($this->device_equipment_type) {
                case DeviceConst::DEVICE_TYPE_FACE:
                    $updateParam = ['device_status' => 1,];
                    break;
                case DeviceConst::DEVICE_TYPE_CAMERA:
                    $updateParam = ['camera_status' => 0,];
                    break;
            }
            if (isset($updateParam)) {
                $this->updateDeviceInfo($updateParam);
            }
            // todo 临时调试 直接执行  要还原回 调取队列执行
//            $this->addDeviceToCloud($deviceParams);
        } else {
            $this->syn_status        = DeviceConst::DEVICE_AUTO_CONFIRM_FAIL;
            $this->syn_status_txt    = "设备自动确权失败";
            $this->err_reason        = "设备自动确权失败({$result['message']})[{$result['code']}]";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'deviceSerial' => $deviceSerial, 'result' => $result];
            $this->recordDeviceBindFilterBox($param);
            $this->step_num++;
            switch ($this->device_equipment_type) {
                case DeviceConst::DEVICE_TYPE_FACE:
                    $updateParam = [
                        'device_status' => 6,
                        'a3_info'       => $this->err_reason
                    ];
                    break;
                case DeviceConst::DEVICE_TYPE_CAMERA:
                    $updateParam = [
                        'camera_status' => 6,
                        'reason'        => $this->err_reason
                    ];
                    break;
            }
            if (isset($updateParam)) {
                $this->updateDeviceInfo($updateParam);
            }
        }
    }
    
    /**
     * 记录开门信息
     * @param $data
     * @param $third_protocol
     * @param $param
     * @return array
     */
    public function recordUserLog($data,  $third_protocol, $param) {
        $nowTime = time();
        if ($this->village_id) {
            $village_id = $this->village_id;
        } elseif (isset($param['village_id'])) {
            $village_id = $param['village_id'];
        } else {
            $village_id = 0;
        }
        $notice_type = isset($param['dHYrNotice']) && $param['dHYrNotice'] ? $param['dHYrNotice'] : '';
        $communityCode = isset($data[0]['communityCode']) && $data[0]['communityCode'] ? $data[0]['communityCode'] : '';
        if (!$village_id && $communityCode) {
            $dbFaceBindAboutInfo = new FaceBindAboutInfo();
            $whereAbout   = [];
            $whereAbout[] = ['account_id', '=', $communityCode];
            $whereAbout[] = ['bind_type', '=', DeviceConst::DH_TO_CLOUD_VILLAGE];
            $aboutInfo = $dbFaceBindAboutInfo->getOne($whereAbout, 'bind_id');
            if ($aboutInfo && !is_array($aboutInfo)) {
                $aboutInfo = $aboutInfo->toArray();
            }
            $village_id = isset($aboutInfo['bind_id']) && $aboutInfo['bind_id'] ? $aboutInfo['bind_id'] : '';
        }
        $userLogData = [
            'log_business_id' => $village_id,
            'third_protocol'  => $third_protocol,
        ];
        if ($this->device_id) {
            $userLogData['device_id'] = $this->device_id;
        }
        if ($this->device_sn) {
            $userLogData['device_sn'] = $this->device_sn;
        }
        $addAll = [];
        $this->thirdProtocol = $third_protocol;

        $dbHouseUserLog              = new HouseUserLog();
        $dbHouseFaceDevice           = new HouseFaceDevice();
        $dbFaceUserBindDevice        = new FaceUserBindDevice();
        $houseVillageUserBindService = new HouseVillageUserBindService();
        $whereUserBind = [];
        $whereUserBind[] = ['status', '=', 1];
        $whereUserBind[] = ['village_id', '=', $village_id];
        foreach ($data as $item) {
            $log_detail = $item;
            $userLog    = $userLogData;
            $whereRepeat = [];
            if (isset($item['id']) && $item['id']) {
                $userLog['eventId'] = $item['id'];
                $whereRepeat[] = ['eventId', '=', $userLog['eventId']];
                unset($log_detail['id']);
            }
            if (isset($item['enterOrExit']) && $item['enterOrExit']) {
                // 进出标志（1进门，2出门）
                $userLog['direction_type'] = $item['enterOrExit'];
                unset($log_detail['enterOrExit']);
            }
            if (isset($item['type']) && $item['type']) {
                $userLog['syn_third_status'] = $item['type'];
                unset($log_detail['type']);
            }
            if (isset($item['typeName']) && $item['typeName']) {
                $userLog['syn_third_reason'] = $item['typeName'];
                unset($log_detail['typeName']);
            }
            if (!isset($userLog['device_sn']) && isset($item['deviceId']) && $item['deviceId']) {
                $userLog['device_sn'] = $item['deviceId'];
                $whereRepeat[] = ['device_sn', '=', $userLog['device_sn']];
                unset($log_detail['deviceId']);
            } elseif (!isset($userLog['device_sn']) && isset($item['deviceCode']) && $item['deviceCode']) {
                $userLog['device_sn'] = $item['deviceCode'];
                $whereRepeat[] = ['device_sn', '=', $userLog['deviceCode']];
                unset($log_detail['deviceCode']);
            }
            if ((!isset($userLog['device_id']) || !$userLog['device_id']) && $this->device_id) {
                $userLog['device_id'] = $this->device_id;
                $whereRepeat[] = ['device_id', '=', $this->device_id];
            }
            $floor_id = 0;
            if ((!isset($userLog['device_id']) || !$userLog['device_id']) && isset($userLog['device_sn'])) {
                $whereDevice = [];
                $whereDevice[] = ['device_sn', '=', $userLog['device_sn']];
                $deviceInfo = $dbHouseFaceDevice->getOne($whereDevice, 'device_id, village_id, floor_id');
                if ($deviceInfo && !is_array($deviceInfo)) {
                    $deviceInfo = $deviceInfo->toArray();
                }
                if (!$village_id && isset($deviceInfo['village_id']) && $deviceInfo['village_id']) {
                    $userLog['log_business_id'] = $deviceInfo['village_id'];
                }
                if (isset($deviceInfo['device_id']) && $deviceInfo['device_id']) {
                    $userLog['device_id'] = $deviceInfo['device_id'];
                }
                $floor_id = isset($deviceInfo['floor_id']) && $deviceInfo['floor_id'] ? $deviceInfo['floor_id'] : 0;
            }
            $userLog['log_more_id'] = $floor_id;

            $log_name = '';
            if (isset($item['communityName']) && $item['communityName']) {
                $log_name .= $item['communityName'];
            }
            if (isset($item['channelName']) && $item['channelName']) {
                $log_name .= $item['channelName'];
            } elseif (isset($item['deviceName']) && $item['deviceName']) {
                $log_name .= $item['deviceName'];
            }
            $userLog['log_name'] = $log_name;

            if (!isset($userLog['channelCode']) && isset($item['channelCode'])) {
                $userLog['deviceChannelId'] = $item['channelCode'];
                $whereRepeat[] = ['deviceChannelId', '=', $userLog['channelCode']];
                unset($log_detail['channelCode']);
            }
            if (!isset($userLog['channelName']) && isset($item['channelName'])) {
                $userLog['channel_info']    = $item['channelName'];
                unset($log_detail['channelName']);
            }
            if (isset($item['personCode']) && $item['personCode']) {
                $userLog['person_code']    = $item['personCode'];
                unset($log_detail['personCode']);
            }

            $whereUserAbout = [];
            $whereUserAbout[] = ['bind_type', '=', DeviceConst::DH_UID_CLOUD_USER];
            if (isset($item['personId']) && $item['personId']) {
                $whereUserAbout[] = ['person_id', '=', $item['personId']];
            } elseif (isset($userLog['person_code']) && $userLog['person_code']) {
                $whereUserAbout[] = ['code', '=', $userLog['person_code']];
            } else {
                $whereUserAbout = [];
            }
            $bind_id = 0;
            if (!empty($whereUserAbout)) {
                $aboutInfo = $dbFaceUserBindDevice->getOneOrder($whereUserAbout, true, 'id DESC');
                fdump_api(['$item' => $item, '$whereUserAbout' => $whereUserAbout,'$aboutInfo' =>$aboutInfo], '$whereUserAbout',1);
                if ($aboutInfo && !is_array($aboutInfo)) {
                    $aboutInfo = $aboutInfo->toArray();
                    $bind_id = isset($aboutInfo['bind_id']) && $aboutInfo['bind_id'] ? $aboutInfo['bind_id'] : 0;
                }
            }
            
            if (isset($item['cardNumber']) && $item['cardNumber']) {
                $userLog['card_number']    = $item['cardNumber'];
                unset($log_detail['cardNumber']);
            }
            if (isset($item['personName']) && $item['personName']) {
                $userLog['person_name']    = $item['personName'];
                unset($log_detail['personName']);
            }
            
            if ($bind_id > 0) {
                $userLog['uid']    = $bind_id;
                $whereUserBind[] = ['uid', '=', $bind_id];
                $userBinds = $houseVillageUserBindService->getBindInfo($whereUserBind,'pigcms_id, name');
                if ($userBinds && !is_array($userBinds)) {
                    $userBinds = $userBinds->toArray();
                }
                $userLog['log_bind_id'] = isset($userBinds['pigcms_id']) && $userBinds['pigcms_id'] ? $userBinds['pigcms_id'] : 0;
                if ((!isset($userLog['person_name']) || !$userLog['person_name']) && (isset($userBinds['name']) && $userBinds['name'])) {
                    $userLog['person_name'] = $userBinds['name'];
                }
            }
            if (isset($item['cutImageDataVal']) && $item['cutImageDataVal']) {
                try {
                    $face_img = $this->imgOnLineToLocal($item['cutImageDataVal']);
                } catch (\Exception $e) {
                    $face_img = '';
                }
                if ($face_img) {
                    $log_detail['face_img'] = $face_img;
                } else {
                    $log_detail['face_img'] = $item['cutImageDataVal'];
                }
                $userLog['cut_image']   = $log_detail['face_img'];
            }
            if (isset($item['dataVal']) && $item['dataVal']) {
                try {
                    $face_big_img = $this->imgOnLineToLocal($item['dataVal']);
                } catch (\Exception $e) {
                    $face_big_img = '';
                }
                if ($face_big_img) {
                    $log_detail['face_big_img'] = $face_big_img;
                    $userLog['deviceImage']     = $face_big_img;
                    if (!isset($log_detail['face_img']) || !$log_detail['face_img']) {
                        $log_detail['face_img'] = $face_big_img;
                    }
                } else {
                    $userLog['deviceImage']     = $item['dataVal'];
                }
            }
            $log_from   = 1;
            $log_status = 0;
            if (isset($item['type']) && $item['type']) {
                switch ($item['type']) {
                    case 10000:
                    case 10021:
                        $log_from   = DeviceConst::DEVICE_PASS_WORD_OPEN;
                        $log_status = 0;
                        break;
                    case 10001:
                        $log_from   = DeviceConst::DEVICE_IC_CARD_OPEN;
                        $log_status = 0;
                        break;
                    case 10006:
                        $log_from   = DeviceConst::DEVICE_FINGERPRINT_OPEN;
                        $log_status = 0;
                        break;
                    case 10014:
                        $log_from   = DeviceConst::DEVICE_QR_CODE_OPEN;
                        $log_status = 0;
                        break;
                    case 10018:
                        $log_from   = DeviceConst::DEVICE_ID_CARD_PERSON_OPEN;
                        $log_status = 0;
                        break;
                    case 10017:
                        $log_from   = DeviceConst::DEVICE_CERTIFICATES_OPEN;
                        $log_status = 0;
                        break;
                    case 10019:
                        $log_from   = DeviceConst::DEVICE_BLUETOOTH_OPEN;
                        $log_status = 0;
                        break;
                    case 10015:
                        $log_from   = DeviceConst::DEVICE_FACE_OPEN;
                        $log_status = 0;
                        break;
                    case 10044:
                    case 10047:
                        $log_from   = DeviceConst::DEVICE_HEALTH_CODE_OPEN;
                        $log_status = 0;
                        break;
                    case 12044:
                    case 12047:
                        $log_from   = DeviceConst::DEVICE_ERR_HEALTH_CODE_OPEN;
                        $log_status = 0;
                        break;
                    case 12000:
                    case 12021:
                        $log_from   = DeviceConst::DEVICE_PASS_WORD_OPEN;
                        $log_status = 1;
                        break;
                    case 12001:
                        $log_from   = DeviceConst::DEVICE_IC_CARD_OPEN;
                        $log_status = 1;
                        break;
                    case 12006:
                        $log_from   = DeviceConst::DEVICE_FINGERPRINT_OPEN;
                        $log_status = 1;
                        break;
                    case 12014:
                        $log_from   = DeviceConst::DEVICE_QR_CODE_OPEN;
                        $log_status = 1;
                        break;
                    case 12015:
                        $log_from   = DeviceConst::DEVICE_FACE_OPEN;
                        $log_status = 1;
                        break;
                    case 12019:
                        $log_from   = DeviceConst::DEVICE_BLUETOOTH_OPEN;
                        $log_status = 1;
                        break;
                    case 12017:
                        $log_from   = DeviceConst::DEVICE_CERTIFICATES_OPEN;
                        $log_status = 1;
                        break;
                    case 12018:
                        $log_from   = DeviceConst::DEVICE_ID_CARD_PERSON_OPEN;
                        $log_status = 1;
                        break;
                }
                unset($log_detail['type']);
            }

            $userLog['log_from']     = $log_from;
            $userLog['log_status']   = $log_status;
            if ('dHYrNotice' == $notice_type && isset($item['eventTime']) && $item['eventTime']) {
                $log_time = $item['eventTime']/1000;
            } elseif (isset($item['eventTime']) && $item['eventTime']) {
                $log_time = strtotime($item['eventTime']);
            } else {
                $log_time = $nowTime;
            }
            if (isset($item['temperature']) && $item['temperature']) {
                $userLog['temperature']   = $item['temperature'];
                unset($log_detail['temperature']);
            }
            if (isset($log_detail['face_img']) && trim($log_detail['face_img'])) {
                $userLog['cut_image'] = trim($log_detail['face_img']);
            } elseif (isset($userLog['deviceImage']) && trim($userLog['deviceImage'])) {
                $userLog['cut_image'] = trim($userLog['deviceImage']);
            }
            $userLog['log_time']       = $log_time;
            $userLog['syn_third_time'] = $nowTime;
            $userLog['log_detail']     = json_encode($log_detail, JSON_UNESCAPED_UNICODE);
            $userLog['add_time']       = $nowTime;
            if (!empty($whereRepeat)) {
                $log_info = $dbHouseUserLog->getFind($whereRepeat, 'log_id');
                if ($log_info && !is_array($log_info)) {
                    $log_info = $log_info->toArray();
                }
            } else {
                $log_info = '';
            }
            if (!$log_info || !isset($log_info['log_id'])){
                $log_id = $dbHouseUserLog->addData($userLog);
                if (!$log_id) {
                    $addAll[] = $userLog;
                }
            }
        }
        if (!empty($addAll)) {
            $dbHouseUserLog->addAll($addAll);
        }
        return $addAll;
    }

    /**
     * 大华楼栋
     * @param $single
     * @return array|false
     */
    protected function DhToBuildBox($single) {
        $db_room   = new HouseVillageUserVacancy();
        $db_layer  = new HouseVillageLayer();
        $db_floor  = new HouseVillageFloor();
        $paramResult = isset($param['paramResult']) && $param['paramResult'] ? $param['paramResult'] : [];
        if (!isset($paramResult['pOrgCode']) && isset($paramResult['third_id'])) {
            $paramResult['pOrgCode'] = $paramResult['third_id'];
        }
        // 查下单元多少个
        $whereFloor    = [];
        $whereFloor[]  = ['single_id',  '=', $single['id']];
        $whereFloor[]  = ['status',     'in', [0,1]];
        $whereFloor[]  = ['village_id', '=', $this->village_id];
        $unitNum = $db_floor->getMax($whereFloor,'floor_number');
        if (intval($unitNum)>9) {
            $unitNum = 9;
        } else {
            $unitNum = intval($unitNum);
        }
        // 查下楼层多少个
        $whereLayer    = [];
        $whereLayer[]  = ['single_id',  '=', $single['id']];
        $whereLayer[]  = ['status',     'in', [0,1]];
        $whereLayer[]  = ['village_id', '=', $this->village_id];
        $floorNum = $db_layer->getMax($whereLayer,'layer_number');
        if (intval($floorNum)>99) {
            $floorNum = 99;
        } else {
            $floorNum = intval($floorNum);
        }
        // 查下房屋多少个
        $whereRoom    = [];
        $whereRoom[]  = ['single_id',  '=', $single['id']];
        $whereRoom[]  = ['is_del',     '=', 0];
        $whereRoom[]  = ['village_id', '=', $this->village_id];
        $houseNum = $db_room->getMax($whereRoom,'room_number');
        if (intval($houseNum)>99) {
            $houseNum = 99;
        } else {
            $houseNum = intval($houseNum);
        }
        if ($unitNum==0) {
            return false;
        }
        // todo 走队列执行 同步楼栋
        $result = (new FaceDHYunRuiCloudDeviceService)->buildingToDeviceCloud($single['id'], $single, $paramResult);
        return $result;
    }
    
    /**
     * 整理下绑定房屋
     * @param $bindId
     * @param $bindParam
     * @param $orgParam
     * @return array
     */
    protected function filterBindDHBuildUnitRoom($bindId, $bindParam, $orgParam) {
        $queueData = [
            'bindId' => $bindId, 'bindParam' => $bindParam, 'orgParam' => $orgParam
        ];
        $queueData['jobType']       = 'bindDHBuildUnitRoom';
        $queueData['thirdProtocol'] = DeviceConst::DH_YUNRUI;
        $this->traitCommonDHCloudBuildings($queueData);
        return $queueData;
    }
    
    /**
     * 海康同步楼栋处理盒子
     * @param $single
     * @param array $deviceInfo
     * @param array $param
     * @param $step_num
     * @param $thirdProtocol
     * @return bool
     */
    protected function HikToBuildBox($single, array $deviceInfo, array $param, $step_num, $thirdProtocol) {
        $bind_type    = DeviceConst::HK_TO_CLOUD_SINGLE;
        $bind_id      = $single['id'];
        $single_param = [];
        $single_param['bind_type']       = $bind_type;
        $single_param['bind_id']         = $bind_id;
        $single_param['thirdProtocol']   = $thirdProtocol;
        $single_param['single']          = $single;
        $single_param['device_type']     = isset($deviceInfo['device_type']) ? $deviceInfo['device_type'] : $this->face_device_type;
        if (!isset($param['communityId']) && isset($param['third_id'])) {
            $single_param['communityId'] = $param['third_id'];
        }
        if (isset($param['paramResult'])) {
            unset($param['paramResult']);
        }
        $param['single_param'] = $single_param;
        $param['step_num']     = $step_num;
        $param['jobType']      = 'buildToDeviceCloud';
        $job_id = $this->traitCommonHikCloudBuildings($param);
        if (isset($job_id)) {
            $param['job_id']     = $job_id;
        }
        $this->clearRecordDeviceBindFilter();
        $this->syn_status        = DeviceConst::BINDS_SYN_BUILD_QUEUE;
        $this->syn_status_txt    = '下发执行同步楼栋队列';
        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
        $this->second_bind_type  = DeviceConst::BIND_BUILD;
        $this->second_bind_id    = $bind_id;
        $this->third_name        = isset($single['single_name']) ? $single['single_name'] : '楼栋_' . $bind_id;
        $this->recordDeviceBindFilterBox($param);

        // todo 临时调试 直接执行  要还原回 调取队列执行
//        $this->buildToDeviceCloud($param);
        return true;
    }
    /**
     * 整理自动同步相关数据
     * @param $orgParam
     * @return array|false
     * @throws \think\Exception
     */
    protected function filterAutoDHBuildDataBind($orgParam) {
        if (!isset($orgParam['auto_syn']) || intval($orgParam['auto_syn']) != 1) {
            return false;
        }
        $bind_id    = isset($orgParam['bind_id'])    && $orgParam['bind_id']    ? $orgParam['bind_id']    : 0;
        $orgType    = isset($orgParam['orgType'])    && $orgParam['orgType']    ? $orgParam['orgType']    : 0;
        $pOrgCode   = isset($orgParam['orgCode'])    && $orgParam['orgCode']    ? $orgParam['orgCode']    : '';
        $village_id = isset($orgParam['village_id']) && $orgParam['village_id'] ? $orgParam['village_id'] : 0;
        if (!$bind_id || !$orgType || !$pOrgCode || !$village_id) {
            return false;
        }
        switch ($orgType) {
            case 10:
                $where = [];
                $where[] = ['village_id','=',$village_id];
                $where[] = ['single_id','=',$bind_id];
                $where[] = ['is_public_rental','=',0];
                $where[] = ['status','<>',4];
                $houseVillageSignService = new HouseVillageSingleService();
                $countSingle = $houseVillageSignService->getSingleFloorCount($where);
                if (intval($countSingle) < 1) {
                    return $this->backData($orgParam, '没有单元数据，前先前往[人车房管理>楼栋管理]中添加', 1010);
                }
                break;
            case 11:
                $where = [];
                $where[] = ['village_id','=',$village_id];
                $where[] = ['floor_id','=',$bind_id];
                $where[] = ['is_public_rental','=',0];
                $where[] = ['status','<>',4];
                $house_village_user_vacancy = new HouseVillageUserVacancy();
                $countRoom = $house_village_user_vacancy->getCount($where);
                if (intval($countRoom) < 1) {
                    return $this->backData($orgParam, '没有房屋数据，前先前往[人车房管理>房间列表]中添加', 1010);
                }
                break;
            case 12:
                $bind_type = DeviceConst::DH_TO_CLOUD_BUILD;
                // todo 同步去添加人员档案
                return $this->backData($orgParam, '房屋下暂时无同步动作', 1010);
            default:
                return $this->backData($orgParam, '类型不存在', 1018);
        }
        $orgParam['thirdProtocol'] = DeviceConst::DH_YUNRUI;
        $orgParam['jobType']       = 'autoDHBuildDataBind';
        $this->traitCommonDHCloudBuildings($orgParam);
        return $orgParam;
    }

    /**
     * 监控同步设备云整理
     * @param string $thirdProtocol 协议
     * @param string $third_id 三方id
     * @param array  $aboutInfo
     * @param array  $param
     * @param array  $paramResult
     * @return bool
     */
    public function deviceToDeviceFiler(string $thirdProtocol, string $third_id,array $aboutInfo, array $param, array $paramResult = []) {
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
        }
        switch ($thirdProtocol) {
            case DeviceConst::DH_YUNRUI:
                // todo 同步直接走添加摄像机
                $deviceParams = $param;
                if ($third_id) {
                    $deviceParams['third_id']        = $third_id;
                }
                if (isset($paramResult['thirdData']['bind_number'])) {
                    /** 场所ID传过去 */
                    $deviceParams['storeId']         = $paramResult['thirdData']['bind_number'];
                    $deviceParams['parent_third_id'] = $deviceParams['storeId'];
                } elseif ($aboutInfo && isset($aboutInfo['bind_number'])) {
                    /** 场所ID传过去 */
                    $deviceParams['storeId']         = $aboutInfo['bind_number'];
                    $deviceParams['parent_third_id'] = $deviceParams['storeId'];
                }
                $deviceParams['step_num']            = $this->step_num + 1;
                $job_id = $this->traitCommonDHCloudDevices($deviceParams);

                if (isset($job_id)) {
                    $param['job_id']       = $job_id;
                }
                $this->syn_status          = DeviceConst::BINDS_SYN_DEVICE_QUEUE;
                $this->syn_status_txt      = '下发执行同步设备队列';
                $this->line_func_txt_arr   = ['line' => __LINE__, 'funs' => __FUNCTION__];
                $this->third_three_bind_id = $third_id;
                $this->recordDeviceBindFilterBox($param);

                // todo 临时调试 直接执行  要还原回 调取队列执行
//                $this->addDeviceToCloud($deviceParams);
                break;
            case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                $deviceParams = $param;
                if ($third_id) {
                    $deviceParams['third_id']        = $third_id;
                }
                if (isset($paramResult['thirdData']['communityId'])) {
                    /** 社区ID（云眸平台的主键） 传过去 */
                    $deviceParams['communityId']     = $paramResult['thirdData']['communityId'];
                    $deviceParams['parent_third_id'] = $deviceParams['communityId'];
                }
                $deviceParams['step_num']            = $this->step_num + 1;
                $job_id = $this->traitCommonHikCloudDevices($deviceParams);

                if (isset($job_id)) {
                    $param['job_id']       = $job_id;
                }
                $this->clearRecordDeviceBindFilter();
                $this->syn_status          = DeviceConst::BINDS_SYN_DEVICE_QUEUE;
                $this->syn_status_txt      = '下发执行同步设备队列';
                $this->line_func_txt_arr   = ['line' => __LINE__, 'funs' => __FUNCTION__];
                $this->third_three_bind_id = $third_id;
                $this->recordDeviceBindFilterBox($param);

                // todo 临时调试 直接执行  要还原回 调取队列执行
                fdump_api($deviceParams, '$deviceParams');
                $this->addDeviceToCloud($deviceParams);
                break;
        }
        return true;
    }
    
    public function recordDeviceBindFilterTip($param, $syn_status, $syn_status_txt = '', $line_func_txt_arr = [], $third_bind_id = '',$third_second_bind_id = '', $third_three_bind_id = '') {
        $this->syn_status           = $syn_status;
        $this->syn_status_txt       = $syn_status_txt;
        $this->line_func_txt_arr    = $line_func_txt_arr;
        $this->third_bind_id        = $third_bind_id;
        $this->third_second_bind_id = $third_second_bind_id;
        $this->third_three_bind_id  = $third_three_bind_id;
        $this->recordDeviceBindFilterBox($param);
        return true;
    }

    /**
     * 同步后记录设备相关信息
     * @param array $param
     * @param array $addResult
     * @return bool
     */
    protected function recordFaceDevice(array $param = [], array $addResult = []) {
        $nowTime = $this->nowTime ? $this->nowTime : time();
        switch ($this->device_equipment_type) {
            case DeviceConst::DEVICE_TYPE_FINGERPRINT:
                $dbFingerprintDevice = new FingerprintDevice();
                $whereDeviceBind   = [];
                if ($this->device_id) {
                    $whereDeviceBind['device_id'] = $this->device_id;
                }
                if ($this->device_sn) {
                    $whereDeviceBind['device_sn'] = $this->device_sn;
                }
                $infoDevice = $dbFingerprintDevice->getOne($whereDeviceBind);
                if ($infoDevice && !is_array($infoDevice)) {
                    $infoDevice = $infoDevice->toArray();
                }
                if ($infoDevice && isset($infoDevice['device_id'])) {
                    $updateParam = [
                        'last_time'       => $nowTime,
                    ];
                    if (isset($addResult['cloud_device_id']) && $addResult['cloud_device_id']) {
                        $updateParam['cloud_device_id']     = $addResult['cloud_device_id'];
                    } elseif (isset($addResult['deviceId']) && $addResult['deviceId']) {
                        $updateParam['cloud_device_id']     = $addResult['deviceId'];
                    }
                    if (isset($addResult['cloud_group_id']) && $addResult['cloud_group_id']) {
                        $updateParam['cloud_group_id']      = $addResult['cloud_group_id'];
                    }
                    if (isset($addResult['third_name']) && $addResult['third_name']) {
                        $updateParam['cloud_device_name']   = $addResult['third_name'];
                    }
                    if (isset($addResult['validateCode']) && $addResult['validateCode']) {
                        $updateParam['code']   = trim($addResult['validateCode']);
                    }
                    if (isset($addResult['devUsername']) && $addResult['devUsername']) {
                        $updateParam['device_admin']      = $addResult['devUsername'];
                    }
                    if (isset($addResult['devPassword']) && $addResult['devPassword']) {
                        $updateParam['device_password']  = $addResult['devPassword'];
                    }
                    $dbFingerprintDevice->updateThis($whereDeviceBind, $updateParam);
                } else {
                    // todo 没有找到对应设备
                    fdump_api(['errTip' => '记录同步设备至云错误', 'addResult' => $addResult, 'whereDeviceBind' => $whereDeviceBind, 'param' => $param], "deviceThirdProtocol/{$this->thirdProtocol}/errRecordFaceDeviceLog");
                }
                break;
            case DeviceConst::DEVICE_TYPE_ALARM:
                $alarmDeviceService = new AlarmDeviceService();
                $whereDevice = [];
                $whereDevice[] = ['is_del', '=', 0];
                if ($this->device_id) {
                    $whereDevice[]=['device_id','=',$this->device_id];
                } elseif ($this->device_sn) {
                    $whereDevice[]=['device_serial','=', $this->device_sn];
                }
                $deviceField = '*';
                $deviceInfo = $alarmDeviceService->getAlarmDevice($whereDevice, $deviceField);
                if ($deviceInfo && !is_array($deviceInfo)) {
                    $deviceInfo = $deviceInfo->toArray();
                }
                
                if ($deviceInfo && isset($deviceInfo['device_id'])) {
                    $updateParam = [
                        'update_time' => $nowTime,
                        'cloud_status' => 2,
                        'device_status' => 1,
                        'cloud_reason' => '',
                    ];
                    if (isset($addResult['cloud_device_id']) && $addResult['cloud_device_id']) {
                        $updateParam['cloud_device_id']     = $addResult['cloud_device_id'];
                    } elseif (isset($addResult['deviceId']) && $addResult['deviceId']) {
                        $updateParam['cloud_device_id']     = $addResult['deviceId'];
                    }
                    if (isset($addResult['third_name']) && $addResult['third_name']) {
                        $updateParam['cloud_name']   = $addResult['third_name'];
                    }
                    if (isset($addResult['validateCode']) && $addResult['validateCode']) {
                        $updateParam['validate_code']   = trim($addResult['validateCode']);
                    }
                    if (isset($addResult['devUsername']) && $addResult['devUsername']) {
                        $updateParam['third_login']      = $addResult['devUsername'];
                    }
                    if (isset($addResult['devPassword']) && $addResult['devPassword']) {
                        $updateParam['third_login_password']  = $addResult['devPassword'];
                    }
                    if (isset($addResult['communityId']) && $addResult['communityId']) {
                        $updateParam['cloud_community_id']  = $addResult['communityId'];
                    }
                    if (isset($addResult['buildingId']) && $addResult['buildingId']) {
                        $updateParam['cloud_building_id']  = $addResult['buildingId'];
                    }
                    if (isset($addResult['unitId']) && $addResult['unitId']) {
                        $updateParam['cloud_unit_id']  = $addResult['unitId'];
                    }
                    $alarmDeviceService->updateAlarmDevice($whereDevice, $updateParam);
                }
                break;
            case DeviceConst::DEVICE_TYPE_FACE:
            default:
                $dbHouseFaceDevice = new HouseFaceDevice();
                $whereDeviceBind   = [];
                if ($this->device_id) {
                    $whereDeviceBind['device_id'] = $this->device_id;
                } elseif ($this->device_sn) {
                    $whereDeviceBind['device_sn'] = $this->device_sn;
                }
                $faceDevice = $dbHouseFaceDevice->getOne($whereDeviceBind);
                if ($faceDevice && !is_array($faceDevice)) {
                    $faceDevice = $faceDevice->toArray();
                }
                if ($faceDevice && isset($faceDevice['device_id'])) {
                    $updateParam = [
                        'last_time'       => $nowTime,
                    ];
                    if (isset($addResult['cloud_device_id']) && $addResult['cloud_device_id']) {
                        $updateParam['cloud_device_id']     = $addResult['cloud_device_id'];
                    } elseif (isset($addResult['deviceId']) && $addResult['deviceId']) {
                        $updateParam['cloud_device_id']     = $addResult['deviceId'];
                    }
                    if (isset($addResult['cloud_group_id']) && $addResult['cloud_group_id']) {
                        $updateParam['cloud_group_id']      = $addResult['cloud_group_id'];
                    }
                    if (isset($addResult['third_name']) && $addResult['third_name']) {
                        $updateParam['cloud_device_name']   = $addResult['third_name'];
                    }
                    if (isset($addResult['validateCode']) && $addResult['validateCode']) {
                        $updateParam['cloud_code']   = trim($addResult['validateCode']);
                    }
                    if (isset($addResult['devUsername']) && $addResult['devUsername']) {
                        $updateParam['thirdLoginName']      = $addResult['devUsername'];
                    }
                    if (isset($addResult['devPassword']) && $addResult['devPassword']) {
                        $updateParam['thirdLoginPassword']  = $addResult['devPassword'];
                    }
                    $dbHouseFaceDevice->saveData($whereDeviceBind, $updateParam);
                } else {
                    // todo 没有找到对应设备
                    fdump_api(['errTip' => '记录同步设备至云错误', 'addResult' => $addResult, 'whereDeviceBind' => $whereDeviceBind, 'param' => $param], "deviceThirdProtocol/{$this->thirdProtocol}/errRecordFaceDeviceLog");
                }
                break;
        }
        return true;
    }

    /**
     * 监控-记录返回的三方信息 传参字段不允许新增 需要额外传参 走 $param
     * @param string $deviceId
     * @param int $device_id
     * @param string $device_sn
     * @param int $thirdProtocol
     * @param array $param
     * @param array $addResult
     * @return bool|array
     */
    protected function recordCameraInfo(string $deviceId, $device_id = 0, $device_sn = '', $thirdProtocol = 0, array $param = [], array $addResult = []) {
        // todo 添加编辑成功记录下来
        $dbCameraDeviceBind = new CameraDeviceBind();
        $nowTime = $this->nowTime ? $this->nowTime : time();
        $whereDeviceBind = [
            'bind_type'      => DeviceConst::BIND_CAMERA_DEVICE,
            'bind_id'        => $device_id,
            'device_sn'      => $device_sn,
            'device_id'      => $device_id,
            'third_protocol' => $thirdProtocol,
        ];
        $bindInfo = $dbCameraDeviceBind->getOne($whereDeviceBind, 'id');
        if (!$bindInfo || !isset($bindInfo['id'])) {
            $bindData = [
                'bind_type'      => DeviceConst::BIND_CAMERA_DEVICE,
                'bind_id'        => $device_id,
                'device_sn'      => $device_sn,
                'device_id'      => $device_id,
                'third_protocol' => $thirdProtocol,
                'third_deviceId' => $deviceId,
                'add_time'       => $nowTime,
            ];
            $updateParam = [
                'cloud_device_id' => $deviceId,
                'last_time'       => $nowTime,
            ];
            if (isset($param['communityId']) && $param['communityId']) {
                $bindData['third_parent_id']      = $param['communityId'];
                $updateParam['cloud_group_id']    = $param['communityId'];
            }
            if (isset($param['storeId']) && $param['storeId']) {
                $bindData['third_parent_id']      = $param['storeId'];
                $updateParam['cloud_group_id']    = $param['storeId'];
            }
            if (isset($param['devUsername']) && $param['devUsername']) {
                $updateParam['devUsername']    = $param['devUsername'];
            }
            if (isset($param['devPassword']) && $param['devPassword']) {
                $updateParam['devPassword']    = $param['devUsername'];
            }
            if (isset($addResult['third_name']) && $addResult['third_name']) {
                $bindData['third_name']           = $addResult['third_name'];
                $updateParam['cloud_device_name'] = $addResult['third_name'];
            }
            $dbCameraDeviceBind->add($bindData);
            // 同步更新下 监控表中数据
            $houseFaceDeviceService = new HouseFaceDeviceService();
            $whereCamera = [
                'camera_id' => $device_id
            ];
            $houseFaceDeviceService->saveCamera($whereCamera, $updateParam);
        } else {
            // todo 更新后面看是否需要
        }
        return true;
    }

    /**
     * 处理同一个平台身份的 小区住户
     * @param int $uid
     * @return array|\think\Model
     */
    protected function filterUsesToData($uid=0) {
        if (!$uid) {
            $uid = $this->user_id;
        }
        $whereUserBind = [];
        $whereUserBind['status']     = 1;
        $whereUserBind['village_id'] = $this->village_id;
        $whereUserBind['uid']        = $uid;
        $houseVillageUserBindService = new HouseVillageUserBindService();
        $filed = 'pigcms_id,village_id,uid,name,phone,type,vacancy_id,layer_id,floor_id,single_id';
        $userBinds = $houseVillageUserBindService->getHouseUserBindList($whereUserBind,$filed,0);
        if ($userBinds && !is_array($userBinds)) {
            $userBinds = $userBinds->toArray();
        } elseif (!$userBinds) {
            $userBinds = [];
        }
        return $userBinds;
    }

    /**
     * 获取大华人脸图片
     * @return array|string
     */
    public function getDhFaceImg() {
        if (!$this->user_id) {
            return '';
        }
        $faceImg = $this->getUserFaceImg();
        if ($faceImg) {
            // todo 图片路径可能需要修改
            $facePhotoPath = (new FaceDHYunRuiCloudDeviceService())->uploadFileOSS($faceImg);
            fdump_api($facePhotoPath,'$facePhotoPath');
        }
        if (!isset($facePhotoPath) || !$facePhotoPath) {
            $facePhotoPath = '';
        }
        return $facePhotoPath;
    }

    /**
     * 判断下住户是否需要同步到这个设备上
     * @param $param
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function filterDhUserBindAuth($param) {
        if ($this->village_user_bind_id) {
            $pigcms_id = $this->village_user_bind_id;
        } elseif (isset($param['pigcms_id']) && $param['pigcms_id']) {
            $pigcms_id = $param['pigcms_id'];
        }
        if (isset($pigcms_id) && $pigcms_id && ($this->device_id || $this->device_sn)) {
            $whereUserBind = [];
            $whereUserBind[] = ['status', '=', 1];
            $whereUserBind[] = ['village_id', '=', $this->village_id];
            $whereUserBind[] = ['pigcms_id', '=', $pigcms_id];
            $houseVillageUserBindService = new HouseVillageUserBindService();
            $filed = 'pigcms_id,village_id,uid,name,phone,type,vacancy_id,layer_id,floor_id,single_id';
            $userBinds = $houseVillageUserBindService->getBindInfo($whereUserBind,$filed);
            if ($userBinds && !is_array($userBinds)) {
                $userBinds = $userBinds->toArray();
            }
            if (empty($userBinds)) {
                fdump_api(['errTip' => '无权限同步对应门禁,对应住户状态异常', 'pigcms_id' => $pigcms_id, 'whereUserBind' => $whereUserBind, 'param' => $param], "deviceThirdProtocol/{$this->thirdProtocol}/errRecordFaceDeviceLog");
                return false;
            }
            $deviceAuth = new DeviceAuth();
            $whereAuth = [];
            $whereAuth[] = ['device_equipment_type', '=', $this->device_equipment_type];
            $whereAuth[] = ['village_id', '=', $this->village_id];
            $whereAuth[] = ['delete_time', '=', 0];
            if ($this->device_id) {
                $whereAuth[] = ['device_id', '=', $this->device_id];
            }
            if ($this->device_sn) {
                $whereAuth[] = ['device_sn', '=', $this->device_sn];
            }
            if ($userBinds && isset($userBinds['type']) && $userBinds['type']==4) {
                $whereAuth[] = ['all_type', '=', 'work'];
                $workIds = $deviceAuth->getOneColumn($whereAuth, 'work_id');
                $where_work   = [];
                $where_work[] = ['status', '=', 1];
                $where_work[] = ['village_id', '=', $this->village_id];
                $where_work[] = ['is_del', '=', 0];
                $where_work[] = ['wid', 'in', $workIds];
                $dbHouseWorker = new HouseWorker();
                $workPhoneArr = $dbHouseWorker->getColumn($where_work,'phone');
                if (!empty($workPhoneArr) && in_array($userBinds['phone'], $workPhoneArr)) {
                    return true;
                }
            } else {
                $whereAuth[] = ['all_type', '<>', 'work'];
                $authList = $deviceAuth->getPageList($whereAuth, true, 'all_sort DESC');
                if ($authList && !is_array($authList)) {
                    $authList = $authList->toArray();
                }
                if (empty($authList)) {
                    fdump_api(['errTip' => '对应门禁未绑定任何权限', 'authList' => $authList, 'whereAuth' => $whereAuth, 'param' => $param], "deviceThirdProtocol/{$this->thirdProtocol}/errRecordFaceDeviceLog");
                    return false;
                }
                foreach ($authList as $val) {
                    if ($val['all_type'] == 'allVillages') {
                        return true;
                    } elseif ($val['all_type'] == 'allSingles' && $val['single_id'] == $userBinds['single_id']) {
                        return true;
                    } elseif ($val['all_type'] == 'allFloors' && $val['floor_id'] == $userBinds['floor_id']) {
                        return true;
                    } elseif ($val['all_type'] == 'allLayers' && $val['layer_id'] == $userBinds['layer_id']) {
                        return true;
                    } elseif ($val['all_type'] == 'allRooms' && $val['room_id'] == $userBinds['vacancy_id']) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * 过滤获取 以uid身份绑定的 人员档案记录 同步到设备记录
     * @param int $bind_type
     * @return array|\think\Model|null
     */
    protected function filterUserToDeviceFromUid($bind_type = DeviceConst::DH_PERSON_TO_DEVICE_USER) {
        // todo 查询下 房屋的同步场所id
        return $this->commonDhFilterUserToData($bind_type, $this->user_id, true);
    }

    /**
     * 记录权限下发
     * @param $recordParam
     */
    protected function recordDhAddAuth($recordParam) {
        $bind_type     = $recordParam['bind_type'];
        $authAboutInfo = $this->filterUserToDeviceFromUid($bind_type);
        $dbFaceUserBindDevice = new FaceUserBindDevice();
        if (isset($authAboutInfo['id']) && $authAboutInfo['id']) {
            $update = $dbFaceUserBindDevice->saveData(['id'=>$authAboutInfo['id']],$recordParam);
        } else {
            $id = $dbFaceUserBindDevice->addData($recordParam);
        }
    }

    protected $deviceAuth;
    protected $allTypeArr = [];
    protected $notAllTypeArr = [];
    protected $allTypeKey = '';

    /**
     * 过滤添加权限
     * @param array $param
     * @param string $all_type
     * @return bool
     */
    protected function filterDeviceAuthData(array $param,string $all_type = '') {
        if (!$this->deviceAuth) {
            $this->deviceAuth = new DeviceAuth();
        }
        $nowTime = $this->nowTime ? $this->nowTime : time();
        $where = [];
        $where[] = ['device_equipment_type', '=', $this->device_equipment_type];
        $where[] = ['device_id', '=', $this->device_id];
        $where[] = ['village_id', '=', $this->village_id];
        $where[] = ['delete_time', '=', 0];
        if ($this->allTypeKey) {
            $where[] = ['all_type', '=', $this->allTypeKey];
        } elseif ($this->notAllTypeArr) {
            $where[] = ['all_type', 'not in', $this->notAllTypeArr];
        } elseif ($this->allTypeArr) {
            $where[] = ['all_type', 'in', $this->allTypeArr];
        }
        // 删除之前关联的
        $this->deviceAuth->updateThis($where, ['delete_time' => $nowTime]);
        // 这样的记录全部选择的楼层
        $all_type = $this->allTypeKey ? $this->allTypeKey : $all_type;
        switch ($all_type) {
            case 'allVillages':
                $all_sort = 100;
                break;
            case 'allSingles':
                $all_sort = 90;
                break;
            case 'allFloors':
                $all_sort = 80;
                break;
            case 'allLayers':
                $all_sort = 70;
                break;
            case 'allRooms':
                $all_sort = 60;
                break;
            case 'allWorks':
                $all_sort = 10;
                break;
            case 'work':
            default:
                $all_sort = 0;
                break;
        }
        $bindAuth = [
            'device_equipment_type' => $this->device_equipment_type,
            'device_id'             => $this->device_id,
            'device_sn'             => $this->device_sn,
            'village_id'            => $this->village_id,
            'all_type'              => $all_type,
            'all_sort'              => $all_sort,
            'add_time'              => $nowTime,
        ];
        $addAll = [];
        $dataIdArr =  isset($param['dataIdArr']) ? $param['dataIdArr'] : [];
        $dataIdMsg =  isset($param['dataIdMsg']) ? $param['dataIdMsg'] : [];
        switch ($bindAuth['all_type']) {
            case 'allSingles':
                foreach ($dataIdArr as $single_id) {
                    $bindAuth['single_id'] = $single_id;
                    $addAll[] = $bindAuth;
                }
                break;
            case 'allFloors':
                foreach ($dataIdArr as $floor_id) {
                    if (isset($dataIdMsg[$floor_id]) && $dataIdMsg[$floor_id]) {
                        $bindAuth['single_id']  = $dataIdMsg[$floor_id]['single_id'];
                    }
                    $bindAuth['floor_id']  = $floor_id;
                    $addAll[] = $bindAuth;
                }
                break;
            case 'allLayers':
                foreach ($dataIdArr as $layer_id) {
                    if (isset($dataIdMsg[$layer_id]) && $dataIdMsg[$layer_id]) {
                        $bindAuth['single_id']  = $dataIdMsg[$layer_id]['single_id'];
                        $bindAuth['floor_id']   = $dataIdMsg[$layer_id]['floor_id'];
                    }
                    $bindAuth['layer_id']  = $layer_id;
                    $addAll[] = $bindAuth;
                }
                break;
            case 'allRooms':
                foreach ($dataIdArr as $room_id) {
                    if (isset($dataIdMsg[$room_id]) && $dataIdMsg[$room_id]) {
                        $bindAuth['single_id']  = $dataIdMsg[$room_id]['single_id'];
                        $bindAuth['floor_id']   = $dataIdMsg[$room_id]['floor_id'];
                        $bindAuth['layer_id']   = $dataIdMsg[$room_id]['layer_id'];
                    }
                    $bindAuth['room_id']  = $room_id;
                    $addAll[] = $bindAuth;
                }
                break;
        }
        if (!empty($addAll)) {
            $this->deviceAuth->addAll($addAll);
        }
        return true;
    }

    /**
     * 获取 开门计划
     * @return array
     */
    protected function filterTimePlanFromDevice() {
        // todo 查询下 设备的开门计划
        return $this->commonFilterAboutToData(DeviceConst::DH_TIME_PLAN_CLOUD_DEVICE, $this->device_id);
    }
    
    /**
     * 获取房屋用户同步的相关id或者编号
     * @return array
     */
    protected function filterUserToDataFromHouse() {
        // todo 查询下 房屋的同步场所id
        return $this->commonDhFilterUserToData(DeviceConst::DH_PIG_CMS_ID_CLOUD_USER, $this->village_user_bind_id, true);
    }

    /**
     * 获取人证信息
     * @return array
     */
    protected function filterUserCardToDataFromHouse() {
        // todo 查询下 房屋的同步场所id
        return $this->commonDhFilterUserToData(DeviceConst::DH_PERSON_IDENTITY_CLOUD_USER, $this->user_id, true);
    }

    /**
     * 获取人员的所有已经同步过的卡号
     * @return array
     */
    protected function filterUserCards() {
        // todo 查询下 房屋的同步场所id
        $dbFaceUserBindDevice = new FaceUserBindDevice();
        $whereAbout   = [];
        $whereAbout[] = ['bind_type', '=', DeviceConst::DH_PIG_CMS_ID_CLOUD_USER];
        $whereAbout[] = ['bind_id',   '=', $this->village_user_bind_id];
        $whereAbout[] = ['person_id', '<>', ''];
        $bindIds = $dbFaceUserBindDevice->getColumn($whereAbout, 'bind_id');
        $whereUserBind = [];
        $whereUserBind[] = ['status',    '=',1];
        $whereUserBind[] = ['village_id','=',$this->village_id];
        $whereUserBind[] = ['pigcms_id', 'in',$bindIds];
        $whereUserBind[] = ['ic_card',   '<>',''];
        $houseVillageUserBindService = new HouseVillageUserBindService();
        $filed = 'pigcms_id,village_id,uid,name,phone,type,vacancy_id,layer_id,floor_id,single_id,ic_card';
        $userBindList = $houseVillageUserBindService->getHouseUserBindList($whereUserBind, $filed, 0);
        if ($userBindList && !is_array($userBindList)) {
            $userBindList = $userBindList->toArray();
        }
        $personCards = [];
        if (!empty($userBindList)) {
            foreach ($userBindList as $val) {
                //cardNumber        卡号，8位16进制 （卡号必须是8位16进制，后端做了重复性校验）
                //cardType          卡类型 0=普通卡 1=VIP 2=来宾卡 3=巡逻卡 5=胁迫卡 6=巡检卡 11=管理员卡 13=残疾卡
                //masterSubCardType 有且允许仅有一张主卡 0=主卡 1=副卡
                $personCards[] = [
                    'cardNumber'        => $val['ic_card'],
                    'cardType'          => 0,
                    'masterSubCardType' => 1,
                ];
            }
        }
        return $personCards;
    }

    /**
     * @var int[] 工作人员身份类别  4 工作人员 5通行证
     */
    protected $workTypeArr = [4,5];

    /**
     * 公用处理人员信息方法
     * @param array $param
     * @return array
     */
    protected function filterVillageBindUser(array $param) {
        if (isset($param['pigcms_id']) && $param['pigcms_id']) {
            $pigcms_id = $param['pigcms_id'];
        } else {
            $pigcms_id =  $this->village_user_bind_id;
        }
        $userBind = $this->getDeviceBindUser($pigcms_id, $this->village_id);
        if (!empty($userBind)) {
            $personParam = [];
            switch ($this->thirdProtocol) {
                case DeviceConst::DH_YUNRUI:
                    $personParam = $this->filterDhBindUser($userBind);
                    break;
            }
            return $personParam;
        } else {
            return [];
        }
    }

    /**
     * 整理大华人员同步信息
     * @param $userBind
     * @return array
     */
    protected function filterDhBindUser($userBind) {
        $name          = isset($userBind['name']) && $userBind['name'] ? $userBind['name'] : '用户'.$userBind['pigcms_id'];
        $memo          = isset($userBind['memo']) && $userBind['memo'] ? $userBind['memo'] : '';
        if (in_array($userBind['type'], $this->workTypeArr)) {
            $orgCode   = $this->village_third_code;
        } else {
            $orgCode   = $this->room_third_code;
            $roomNo    = $this->room_third_code;
        }
        $storeId       = $this->village_third_id;
        $telephone     = isset($userBind['phone'])   && $userBind['phone']   ? $userBind['phone']   : '';
        $ic_card       = isset($userBind['ic_card']) && $userBind['ic_card'] ? $userBind['ic_card'] : '';
        $facePhotoPath = $this->getDhFaceImg();
        $personParam = [
            'name'       => $name,
            'orgCode'    => $orgCode,
            'storeId'    => $storeId,
        ];
        if (in_array($userBind['type'], $this->workTypeArr)) {
            $personParam['is_work']       = 1;
        } else {
            $personParam['is_work']       = 0;
        }
        if ($memo) {
            $personParam['memo']          = $memo;
        }
        if (isset($roomNo) && $roomNo) {
            $personParam['roomNo']        = $roomNo;
        }
        if ($telephone) {
            $personParam['telephone']     = $telephone;
        }
        if ($facePhotoPath) {
            $personParam['facePhotoPath'] = $facePhotoPath;
        }
        if ($this->user_third_id) {
            $personParam['id']            = $this->user_third_id;
        }
        if ($ic_card) {
            $personParam['ic_card']       = $ic_card;
        }
        return $personParam;
    }
    
    /**
     * 处理记录下 人员基础信息 平台住户和
     * @param string $personFileId
     * @param string $third_id
     * @param int $bind_type
     * @param int $bind_id
     * @param array $param
     * @return int|mixed|string
     */
    protected function recordDHPersonFile(string $personFileId,string $third_id,int $bind_type,int $bind_id,  array $param = []) {
        // todo 查询下 房屋的同步场所id
        $aboutInfo = $this->commonDhFilterUserToData($bind_type, $bind_id);
        $nowTime = $this->nowTime ? $this->nowTime : time();
        if ($aboutInfo && !is_array($aboutInfo)) {
            $aboutInfo = $aboutInfo->toArray();
        }
        try {
            $result = (new FaceDHYunRuiCloudDeviceService())->getPersonByPrfoleId($personFileId);
            if (isset($result['data']['personCode']) && $result['data']['personCode']) {
                // 人员编码 获取到了 记录下
                $code = $result['data']['personCode'];
            } else {
                $code = '';
            }
        } catch (\Exception $e){
            $code = '';
        }
        
        $dbFaceUserBindDevice = new FaceUserBindDevice();
        if ($aboutInfo && isset($aboutInfo['id'])) {
            // 已经存在了 更新信息
            $updateParam = [
                'bind_type'     => $bind_type,
                'bind_id'       => $bind_id,
                'device_type'   => 0,
                'person_id'     => $personFileId,
                'last_time'     => $nowTime,
            ];
            if ($third_id) {
                $updateParam['personID'] = $third_id;
                $this->user_third_code   = $third_id;
            }
            if (isset($param['person_json_md5']) && $param['person_json_md5']) {
                $updateParam['person_json'] = $param['person_json_md5'];
            }
            if ($this->room_third_code) {
                $updateParam['group_id'] = $this->room_third_code;
            }
            if ($this->village_third_id) {
                $updateParam['groupID'] = $this->village_third_id;
            }
            if ($code) {
                $updateParam['code'] = $code;
            }
            $update = $dbFaceUserBindDevice->saveData(['id'=>$aboutInfo['id']], $updateParam);
            $id = $aboutInfo['id'];
            $this->user_third_id = $personFileId;
        } else {
            $addParam = [
                'bind_type'    => $bind_type,
                'bind_id'      => $bind_id,
                'device_type'  => 0,
                'person_id'    => $personFileId,
                'add_time'     => $nowTime,
                'code'         => $code,
            ];
            if ($third_id) {
                $addParam['personID'] = $third_id;
                $this->user_third_code   = $third_id;
            }
            if (isset($param['person_json_md5']) && $param['person_json_md5']) {
                $addParam['person_json'] = $param['person_json_md5'];
            }
            if ($this->room_third_code) {
                $addParam['group_id'] = $this->room_third_code;
            }
            if ($this->village_third_id) {
                $addParam['groupID'] = $this->village_third_id;
            }
            $id = $dbFaceUserBindDevice->addData($addParam);
            $this->user_third_id = $personFileId;
        }
        return $id;
    }

    /**
     * 人证信息同步记录
     * @param array $personCards  卡数据是覆盖的形式，场所下不允许重复。最多5张卡
     * @param int $bind_type
     * @param int $bind_id
     * @param array $param
     * @return int|mixed|string
     */
    protected function recordDHPersonCards(array $personCards,int $bind_type,int $bind_id,  array $param = []) {
        // todo 查询下 房屋的同步场所id
        $aboutInfo = $this->commonDhFilterUserToData($bind_type, $bind_id);
        $nowTime = $this->nowTime ? $this->nowTime : time();
        if ($aboutInfo && !is_array($aboutInfo)) {
            $aboutInfo = $aboutInfo->toArray();
        }
        $dbFaceUserBindDevice = new FaceUserBindDevice();
        if ($aboutInfo && isset($aboutInfo['id'])) {
            // 已经存在了 更新信息
            $updateParam = [
                'bind_type'     => $bind_type,
                'bind_id'       => $bind_id,
                'device_type'   => 0,
                'person_id'     => $this->user_third_id,
                'last_time'     => $nowTime,
            ];
            if ($this->user_third_code) {
                $updateParam['personID'] = $this->user_third_code;
            }
            if (isset($param['card_json_md5']) && $param['card_json_md5']) {
                $updateParam['card_json'] = $param['card_json_md5'];
            }
            if ($this->room_third_code) {
                $updateParam['group_id'] = $this->room_third_code;
            }
            if ($this->village_third_id) {
                $updateParam['groupID'] = $this->village_third_id;
            }
            if ($personCards) {
                $updateParam['person_text'] = json_encode($personCards, JSON_UNESCAPED_UNICODE);
                if (!isset($updateParam['card_json'])) {
                    $updateParam['card_json'] = md5(json_encode([$personCards, date('Y-m-d H', time())], JSON_UNESCAPED_UNICODE));
                }
            }
            $update = $dbFaceUserBindDevice->saveData(['id'=>$aboutInfo['id']], $updateParam);
            $id = $aboutInfo['id'];
        } else {
            $addParam = [
                'bind_type'     => $bind_type,
                'bind_id'       => $bind_id,
                'device_type'   => 0,
                'person_id'     => $this->user_third_id,
                'add_time'     => $nowTime,
            ];
            if ($this->user_third_code) {
                $updateParam['personID'] = $this->user_third_code;
            }
            if (isset($param['card_json_md5']) && $param['card_json_md5']) {
                $updateParam['card_json'] = $param['card_json_md5'];
            }
            if ($this->room_third_code) {
                $addParam['group_id'] = $this->room_third_code;
            }
            if ($this->village_third_id) {
                $addParam['groupID'] = $this->village_third_id;
            }
            if ($personCards) {
                $addParam['person_text'] = json_encode($personCards, JSON_UNESCAPED_UNICODE);
                if (!isset($addParam['card_json'])) {
                    $addParam['card_json'] = md5(json_encode([$personCards, date('Y-m-d H', time())], JSON_UNESCAPED_UNICODE));
                }
            }
            $id = $dbFaceUserBindDevice->addData($addParam);
        }
        return $id;
    }

    /**
     * 监控-获取下海康符合当前设备的通道信息 传参字段不允许新增 需要额外传参 走 $param
     * @param string $communityId
     * @param string $deviceId
     * @param array $param
     * @param array $channelRecord
     * @return array
     */
    public function getHikDeviceChannels(string $communityId, string $deviceId, array $param = [], array $channelRecord = [])
    {
        $dbFaceHikCloudNeiBuDeviceService = new FaceHikCloudNeiBuDeviceService();
        if (!isset($param['pageNo'])) {
            $param['pageNo'] = 1;
        }
        if (!isset($param['pageSize'])) {
            $param['pageSize'] = 100;
        }
        $channelResult = $dbFaceHikCloudNeiBuDeviceService->getDeviceChannellistByCommunityId($communityId, $param);
        if (isset($channelResult['data']['rows']) && $channelResult['data']['rows']) {
            foreach ($channelResult['data']['rows'] as $val) {
                if ($deviceId && isset($val['deviceId']) && $deviceId == $val['deviceId']) {
                    $channelRecord[] = $val;
                }
            }
            if (isset($channelResult['data']['hasNextPage']) && $channelResult['data']['hasNextPage']) {
                // 还有下一页数据 走下一页
                $param['pageNo'] += 1;
                return $this->getHikDeviceChannels($communityId, $deviceId, $param, $channelRecord);
            } elseif (isset($channelResult['data']['rows']) && count($channelResult['data']['rows']) == $param['pageSize']) {
                // 当前页面满返回 走下一页
                $param['pageNo'] += 1;
                return $this->getHikDeviceChannels($communityId, $deviceId, $param, $channelRecord);
            } else {
                return $channelRecord;
            }
        } else if (isset($channelResult['code']) && $channelResult['code']!=200) {
            // 获取通道失败
            $this->syn_status        = DeviceConst::BINDS_SYN_DEVICE_CHANNEL_FAIL;
            $this->syn_status_txt    = "同步设备通道失败";
            $this->err_reason        = "同步设备通道失败";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
            $this->recordDeviceBindFilterBox($param);
            $this->step_num++;
        }
        return $channelRecord;
    }
    
    /**
     * 整理批量同步单元下的房屋
     * @param $deviceInfo
     * @param $village_id
     * @param $floor_id
     * @param $thirdProtocol
     * @param $param
     * @return bool
     */
    public function roomToDeviceFilter($deviceInfo, $village_id, $floor_id, $thirdProtocol, $param){
        $db_layer  = new HouseVillageLayer();
        $houseVillageUserVacancyService  = new HouseVillageUserVacancyService();
        if ($floor_id) {
            $whereRoom    = [];
            $whereRoom[]  = ['floor_id', '=', $floor_id];
            $whereRoom[]  = ['status', '<>', 4];
            $whereRoom[]  = ['is_del', '=', 0];
            $whereRoom[]  = ['village_id', '=', $village_id];

            $whereLayer    = [];
            $whereRoom[]   = ['floor_id', '=', $floor_id];
            $whereLayer[]  = ['status', 'in', [0,1]];
            $whereLayer[]  = ['village_id', '=', $village_id];
        } elseif (isset($deviceInfo['floor_id']) && $deviceInfo['floor_id']) {
            $floorIds = explode(',', $deviceInfo['floor_id']);
            $whereRoom    = [];
            $whereRoom[]  = ['floor_id', 'in', $floorIds];
            $whereRoom[]  = ['status', '<>', 4];
            $whereRoom[]  = ['is_del', '=', 0];
            $whereRoom[]  = ['village_id', '=', $village_id];

            $whereLayer    = [];
            $whereRoom[]   = ['floor_id', 'in', $floorIds];
            $whereLayer[]  = ['status', 'in', [0,1]];
            $whereLayer[]  = ['village_id', '=', $village_id];
        } else {
            $whereRoom = [];
        }
        if (!empty($whereRoom)) {
            $roomField = 'pigcms_id, room, room_number, layer, pigcms_id as vacancy_id, village_id, floor_id, layer_id';
            $roomInfoArr = $houseVillageUserVacancyService->getVacancyList($whereRoom, $roomField);
            if (!empty($roomInfoArr) && !is_array($roomInfoArr)) {
                $roomInfoArr = $roomInfoArr->toArray();
            }
            if (!empty($roomInfoArr) && isset($whereLayer)) {
                $layerIdArr = $db_layer->getOneColumn($whereLayer, 'id,layer_number,layer_name','id');
                foreach ($roomInfoArr as &$room1) {
                    if ($room1['layer_id'] && isset($layerIdArr[$room1['layer_id']])) {
                        $layerInfo = $layerIdArr[$room1['layer_id']];
                        $room1['layer_number'] = $layerInfo['layer_number'];
                        $room1['layer_name']   = $layerInfo['layer_name'];
                    }
                }
            }
        } else {
            $roomInfoArr = [];
        }
        if ($param['third_id']) {
            $unitId = $param['third_id'];
        } elseif ($this->unit_third_id) {
            $unitId = $this->unit_third_id;
        } else {
            $unitId = '';
        }
        if (!empty($roomInfoArr)) {
            $step_num = $this->step_num + 1;
            $this->clearRecordDeviceBindFilter();
            foreach ($roomInfoArr as $room) {
                switch ($thirdProtocol) {
                    case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                        $bind_type  = DeviceConst::HK_TO_CLOUD_ROOM;
                        $bind_id    = $room['pigcms_id'];
                        $room_param = [];
                        $room_param['bind_type']     = $bind_type;
                        $room_param['bind_id']       = $bind_id;
                        $room_param['thirdProtocol'] = $thirdProtocol;
                        $room_param['room']          = $room;
                        $room_param['floor_id']      = $room['floor_id'];
                        $room_param['device_type']   = $this->face_device_type;
                        $room_param['unitId']        = $unitId;
                        // todo 走队列执行 同步房屋
                        $param['jobType']        = 'singleRoomToDeviceCloud';
                        $param['room_param']     = $room_param;
                        $param['step_num']       = $step_num;
                        $job_id = $this->traitCommonHikCloudUnits($param);
                        if (isset($job_id)) {
                            $param['job_id']     = $job_id;
                        }
                        $this->syn_status        = DeviceConst::BINDS_SYN_ROOM_QUEUE;
                        $this->syn_status_txt    = '下发同步房屋队列';
                        $this->err_reason        = "";
                        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                        $this->second_bind_type  = DeviceConst::BIND_ROOM;
                        $this->second_bind_id    = $bind_id;
                        $this->third_name        = isset($room['room']) && $room['room'] ? $room['room'] : '户室_'.$room['pigcms_id'];
                        if ($this->unit_third_id) {
                            $this->third_second_bind_id = $this->unit_third_id;
                        }
                        if ($this->build_third_id) {
                            $this->third_three_bind_id  = $this->build_third_id;
                        }
                        $this->recordDeviceBindFilterBox($param);
                        // todo 临时调试 直接执行  要还原回 调取队列执行
//                        $re = $this->singleRoomToDeviceCloud($param);
                        break;
                }
            }
            $bind_id                 = isset($param['floor_id'])    ? $param['floor_id']    : 0;
            $third_name              = isset($param['third_name'])  ? $param['third_name']  : '';
            $this->syn_status        = DeviceConst::BINDS_SYN_UNIT_ROOMS_END;
            $this->syn_status_txt    = "下发同步单元的房屋队列结束";
            $this->err_reason        = "";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'deviceInfo' => $deviceInfo, 'village_id' => $this->village_id, 'floor_id' => $floor_id, 'param' => $param];
            $this->second_bind_type  = DeviceConst::BIND_UNIT;
            $this->second_bind_id    = $bind_id;
            $this->third_name        = $third_name;
            if ($this->unit_third_id) {
                $this->third_second_bind_id = $this->unit_third_id;
            }
            if ($this->build_third_id) {
                $this->third_three_bind_id  = $this->build_third_id;
            }
            $this->recordDeviceBindFilterBox($param);
        } else {
            $bind_id                 = isset($param['floor_id'])    ? $param['floor_id']    : 0;
            $third_name              = isset($param['third_name'])  ? $param['third_name']  : '';
            $this->syn_status        = DeviceConst::BINDS_SYN_UNIT_ROOMS_ERR;
            $this->syn_status_txt    = "下发同步单元的房屋队列失败(无可同步房屋)[单元ID：{$bind_id}]";
            $this->err_reason        = "下发同步单元的房屋队列失败(无可同步房屋)[单元ID：{$bind_id} 单元名称：{$third_name}]";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'deviceInfo' => $deviceInfo, 'village_id' => $this->village_id, 'floor_id' => $floor_id, 'param' => $param];
            $this->recordDeviceBindFilterBox($param);
        }
        return true;
    }
    
    public function thirdAuthDhRecord($param) {
        $taskId       = isset($param['taskId'])       && $param['taskId']       ? $param['taskId']       : '';
        $status       = isset($param['status'])       && $param['status']       ? $param['status']       : 0;
        $resultMsg    = isset($param['resultMsg'])    && $param['resultMsg']    ? $param['resultMsg']    : '';
        $deviceId     = isset($param['deviceId'])     && $param['deviceId']     ? $param['deviceId']     : '';
        $personFileId = isset($param['personFileId']) && $param['personFileId'] ? $param['personFileId'] : '';
        $companyId    = isset($param['companyId'])    && $param['companyId']    ? $param['companyId']    : '';
        if (!$taskId) {
            return true;
        }
        $cacheKey   = DahuaConst::DH_BATCH_REDIS_KEY . $taskId;
        $userBindId = Cache::store('redis')->get($cacheKey);
        if ($userBindId) {
            $face_img_status = $status == 1 ? 2 : 1;
            $face_img_reason = $status == 1 ? $resultMsg : '';
            $houseVillageUserBindService = new HouseVillageUserBindService();
            $updateBindParam = [
                'face_img_status' => $face_img_status,
                'face_img_reason' => $face_img_reason,
            ];
            $houseVillageUserBindService->saveUserBind(['pigcms_id' => $userBindId], $updateBindParam);
            if($param['log_id']){
                (new OwnerService())->updateLog(['log_id'=>$param['log_id']],[
                    'status'=>$face_img_status,
                    'msg'=>$face_img_reason,
                    'last_time'=>time()
                ]);
            }
        } elseif($deviceId && $personFileId) {
            $dbHouseFaceDevice = new HouseFaceDevice();
            $whereDevice = [];
            $whereDevice[] = ['device_sn', '=', $deviceId];
            $deviceInfo = $dbHouseFaceDevice->getOne($whereDevice, 'device_id, village_id');
            if ($deviceInfo && !is_array($deviceInfo)) {
                $deviceInfo = $deviceInfo->toArray();
            }
            $village_id = isset($deviceInfo['village_id']) && $deviceInfo['village_id'] ? $deviceInfo['village_id'] : '';
            $dbFaceUserBindDevice = new FaceUserBindDevice();
            $whereAbout = [];
            $whereAbout[] = ['bind_type', '=', DeviceConst::HK_UID_CLOUD_USER];
            $whereAbout[] = ['person_id', '=', $personFileId];
            $aboutInfo = $dbFaceUserBindDevice->getOneOrder($whereAbout, 'bind_id');
            if ($aboutInfo && !is_array($aboutInfo)) {
                $aboutInfo = $aboutInfo->toArray();
            }
            $uid = isset($aboutInfo['bind_id']) && $aboutInfo['bind_id'] ? $aboutInfo['bind_id'] : 0;
            $face_img_status = $status == 1 ? 2 : 1;
            $face_img_reason = $status == 1 ? $resultMsg : '';
            if ($uid) {
                $houseVillageUserBindService = new HouseVillageUserBindService();
                $updateBindParam = [
                    'face_img_status' => $face_img_status,
                    'face_img_reason' => $face_img_reason,
                ];
                $whereUserBind = [];
                $whereUserBind[] = ['village_id', '=', $village_id];
                $whereUserBind[] = ['uid',        '=', $uid];
                $whereUserBind[] = ['status',     '=', 1];
                $houseVillageUserBindService->saveUserBind($whereUserBind, $updateBindParam);
            }
        }
    }

    public function getHouseDeviceChannelInfo($where, $field = true)
    {
        $dbHouseDeviceChannel = new HouseDeviceChannel();
        return $dbHouseDeviceChannel->getOne($where, $field);
    }

    public function updateHouseDeviceChannelInfo($where, $update)
    {
        $dbHouseDeviceChannel = new HouseDeviceChannel();
        return $dbHouseDeviceChannel->updateThis($where, $update);
    }
}