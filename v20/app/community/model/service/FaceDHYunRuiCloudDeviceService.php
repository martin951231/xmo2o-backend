<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      大华云睿开放平台协议 内部设备应用  这一层只是处理接参整合传参设备云 然后组合返回下  不做业务处理
 */

namespace app\community\model\service;

use app\community\model\db\FaceBindAboutInfo;
use file_handle\FileHandle;
use Imactool\DahuaCloud\Cloud;
use app\consts\DahuaConst;
use app\consts\DeviceConst;

class FaceDHYunRuiCloudDeviceService
{

    public $config;
    public $cloud = NULL;
	public function __construct(){
        if (!$this->cloud) {
            if (cfg('DHYunRuiCloudClientId') && cfg('DHYunRuiCloudClientSecret')) {
                $this->config = [
                    'client_id'     => cfg('DHYunRuiCloudClientId'),
                    'client_secret' => cfg('DHYunRuiCloudClientSecret')
                ];
                $this->cloud = new Cloud($this->config);
            }
        }
    }
    
    /**
     * 判断大华云睿是否配置了
     * @return bool
     */
    public function judgeConfig() {
	    if (!isset($this->config['client_id']) || !isset($this->config['client_secret']) || !$this->config['client_id'] || !$this->config['client_secret']) {
	        return false;
        } else {
	        return true;
        }
    }
	
    /**
     * @param int $property_id 物业id
     * @param array $property_info 物业信息
     * @param array $param
     * @return array
     * [
     *      "code" => "0",  // 返回编码
     *      "data" => [
     *                  "id"       => "747135767048220672", // 组织的分布式id
     *                  "orgCode"  => "001100101", // 组织编码
     *                  "orgName"  => "组织", // 组织名称
     *                  "orgType"  => 1,  // 组织类型，1-普通组织，2-场所、10-楼栋、11-单元、12-房屋
     *                  "pOrgCode" => "001100" // 父组织编码
     *       ],
     *       "errMsg"      =>  "",   // 错误信息
     *       "success"     => true,  // 返回结果
     *       "id"          =>  "", // 组织的分布式id
     *       "bind_number" =>  "", // 组织编码
     *       "pOrgCode"    =>  "", // 父组织编码
     *       "bind_name"   =>  "", // 组织名称
     * ]
     */
    public function propertyToDeviceCloud(int $property_id,array $property_info = [], $param = []) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        /*** 组织名称（只允许输入汉字，大小写字母，数字_-@#()（），最多支持70个字符）*/
        $orgName           = isset($property_info['property_name']) ? $property_info['property_name'] : '物业_' . $property_id;
        /*** 父组织编码*/
        $pOrgCode          = isset($param['pOrgCode']) ? $param['pOrgCode'] : '';
        if (!$pOrgCode) {
            // todo 对应查询下父组织编码
            $result = $this->getOrgList();
            if (isset($result['orgGroup']['orgCode'])) {
                $pOrgCode = $result['orgGroup']['orgCode'];
            } else {
                return [
                    'code'    => DahuaConst::ERR_DH_NOT_PORGCODE_CODE,
                    'message' => DahuaConst::ERR_DH_NOT_PORGCODE_MESSAGE,
                ];
            }
        }
        $params = [
            'orgName'   => $orgName,
            'pOrgCode'  => $pOrgCode,
        ];
        $result = $this->cloud->Org->addOrg($params);
        if (isset($result['data']['id'])) {
            $id       = isset($result['data']['id']) ? $result['data']['id']:'';
            $orgCode  = isset($result['data']['orgCode']) ? $result['data']['orgCode'] : '';
            $orgName  = isset($result['data']['orgName']) ? $result['data']['orgName'] : $orgName;
            $pOrgCode = isset($result['data']['pOrgCode']) ? $result['data']['pOrgCode'] : $pOrgCode;
            $result['third_id']    = $orgCode;
            $result['pOrgCode']    = $pOrgCode;
            $result['bind_name']   = $orgName;
            $result['bind_number'] = $id;
            return $result;
        } else {
            // todo 匹配  "errMsg": "相同节点下，组织名称不能重复","success": false 进行查询 重新记录处理
            return $result;
        }
    }
    
    /**
     * 小区同步至社区 自行匹配是新增还是修改
     * @param int $village_id 小区id 必传
     * @param array $village_info 小区信息 必传
     * @param array $param 额外参数 暂时未用
     * [
     *    'communityId' => '海康云眸内部应用社区id 编辑时候必传'
     * ]
     * @return array
     */
    public function villageToDeviceCloud(int $village_id, array $village_info = [],array $param = []) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        /*** 场所名称（只允许输入汉字，大小写字母，数字_-@#()（），最多支持70个字符）*/
        $storeName        = isset($village_info['village_name']) ? $village_info['village_name'] : '小区_' . $village_id;
        /*** 场所所属上级*/
        $orgCode         = isset($param['orgCode']) ? $param['orgCode'] : '';
        if (!$orgCode) {
            // todo 如果没有对应传参 可以自行查询下
            return [
                'code'    => DahuaConst::ERR_DH_NOT_ORG_CODE_CODE,
                'message' => DahuaConst::ERR_DH_NOT_ORG_CODE_MESSAGE,
            ];
        }
        /*** 负责人姓名（只允许为汉字，大小写字母，数字_-@#()），最多25个字符*/
        $managerName             = isset($village_info['managerName']) ? strval($village_info['managerName']) : '';
        /*** 系统里有效的账号userId*/
        $managerId               = isset($village_info['managerId']) ? trim($village_info['managerId']) : 0;
        /*** 负责人电话，号码要是正常手机号格式*/
        $managerMobile           = isset($village_info['managerMobile']) ? strval($village_info['managerMobile']) : '';
        /*** 场所固定电话，号码要是正常手机号或电话号*/
        $telephone               = isset($village_info['telephone']) ? strval($village_info['telephone']) : '';
        /*** 场所地址，长度限制（150），没有规则限制*/
        $address                 = isset($village_info['village_address']) ? strval($village_info['village_address']) : $storeName;
        /*** 维度坐标值*/
        $lat                     = isset($village_info['lat']) ? strval($village_info['lat']) : '';
        /*** 经度坐标值*/
        $lng                     = isset($village_info['long']) ? strval($village_info['long']) : '';
        /*** 场所面积（输入0-100000的数字）*/
        $storeArea               = isset($village_info['storeArea']) ? strval($village_info['storeArea']) : '';
        /*** 场所图片（最多3张,建议大小尺寸为：750*422）*/
        $storePic1               = isset($village_info['storePic1']) ? strval($village_info['storePic1']) : '';
        /*** 场所图片（最多3张,建议大小尺寸为：750*422）*/
        $storePic2               = isset($village_info['storePic2']) ? strval($village_info['storePic2']) : '';
        /*** 场所图片（最多3张,建议大小尺寸为：750*422）*/
        $storePic3               = isset($village_info['storePic3']) ? strval($village_info['storePic3']) : '';
        /*** 描述（最多支持150个字符）*/
        $description             = isset($village_info['description']) ? strval($village_info['description']) : '';
        /*** 工地类型，0-建筑工地，1-道路铁路工地，2-隧道桥梁工地，3-交通枢纽工地，4-水利枢纽工地，5-港口工地。注：工地行业为必填项。*/
        $buildingSiteType        = isset($village_info['description']) ? intval($village_info['description']) : -1;
        /*** 场所ID，工地、零售专用。*/
        $businesscode             = isset($village_info['businesscode']) ? strval($village_info['businesscode']) : '';
        /*** 场所状态 0:异常 1：正常 */
        $storeStatus              = isset($village_info['storeStatus']) ? intval($village_info['storeStatus']) : -1;

        $params = [
            'storeName'       => strval($storeName),
            'orgCode'         => strval($orgCode),
        ];
        if ($managerName) {
            $params['managerName'] = $managerName;
        }
        if ($managerId) {
            $params['managerId'] = $managerId;
        }
        if ($managerMobile) {
            $params['managerMobile'] = $managerMobile;
        }
        if ($telephone) {
            $params['telephone'] = $telephone;
        }
        if ($address) {
            $params['address'] = $address;
        }
        if ($lat) {
            $params['lat'] = $lat;
        }
        if ($lng) {
            $params['lng'] = $lng;
        }
        if ($storeArea) {
            $params['storeArea'] = $storeArea;
        }
        if ($storePic1) {
            $params['storePic1'] = $storePic1;
        }
        if ($storePic2) {
            $params['storePic2'] = $storePic2;
        }
        if ($storePic3) {
            $params['storePic3'] = $storePic3;
        }
        if ($description) {
            $params['description'] = $description;
        }
        if ($buildingSiteType!=-1) {
            $params['buildingSiteType'] = $buildingSiteType;
        }
        if ($businesscode) {
            $params['businesscode'] = $businesscode;
        }
        if ($storeStatus!=-1) {
            $params['storeStatus'] = $storeStatus;
        }

        if (isset($param['storeId']) && $param['storeId']) {
            // 携带了大华的 场所ID 走编辑路线 先去除不更新字段
            $params = [
                'storeName' => $storeName,
                'id' => $param['storeId'],
            ];
            $storeId = $param['storeId'];
            if ($storeStatus!=-1) {
                $params['storeStatus'] = $storeStatus;
            }
            $result = $this->cloud->Org->updatePlace($params);
        } else {
            $result = $this->cloud->Org->addPlace($params);
        }

        if (isset($result['data']['storeId'])) {
            if (!isset($storeId)) {
                /** 场所id */
                $storeId = isset($result['data']['storeId'])?$result['data']['storeId']:'';
            }
            /** 当前场所的orgCode */
            $thisOrgCode = isset($result['data']['thisOrgCode'])?$result['data']['thisOrgCode']:'';
            $result['third_id']    = $thisOrgCode;
            $result['bind_number'] = $storeId;
            $result['bind_name']   = $storeName;
            return $result;
        } else {
            return $result;
        }
    }

    /**
     * 楼栋单元楼层房屋同步设备方
     * @param int $single_id
     * @param array $single_info
     * @param array $param
     * @return array
     */
    public function buildingToDeviceCloud(int $single_id, array $single_info = [],array $param = []) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        /** @var  string buildingName 楼栋名称 */
        $buildingName     = isset($single_info['single_name']) && $single_info['single_name']     ? trim($single_info['single_name'])   : '楼栋_'.$single_id;
        /** @var  string buildingNames 楼栋名称数组集合 */
        $buildingNames     = isset($single_info['buildingNames']) && $single_info['buildingNames'] ? $single_info['buildingNames']       : [];
        if (empty($buildingNames)) {
            $buildingNames    = [$buildingName];
        }
        /** @var  string buildingNumber 楼栋序号 */
        $buildingNumber   = isset($single_info['single_number']) && $single_info['single_number']     ? trim($single_info['single_number'])   : '';
        /** @var  string buildingNumbers 楼栋序号数组集合 */
        $buildingNumbers  = isset($single_info['buildingNumbers']) && $single_info['buildingNumbers'] ? $single_info['buildingNumbers']       : [];
        if (empty($buildingNumbers)) {
            $buildingNumbers  = [$buildingNumber];
        }
        foreach ($buildingNumbers as $buildingNumber)  {
            if ($buildingNumber < 1 && $buildingNumber > 999) {
                return [
                    'code'    => DahuaConst::ERR_DH_NOT_RANGE_CODE,
                    'message' => DahuaConst::ERR_DH_NOT_RANGE_MESSAGE,
                ];
            }
        }
        /*** 场所的组织编码 */
        $pOrgCode         = isset($param['pOrgCode'])                                             ? $param['pOrgCode']                  : '';
        /** @var string unitNum 单元数,取值范围[1,9]  因为无法变更 所以 取最大值9*/
        $unitNum          = isset($param['unitNum'])                                              ? $param['unitNum']                   : '9';
        /** @var string floorNum 层数，取值范围[1,99]  因为无法变更 所以 取30*/
        $floorNum         = isset($param['floorNum'])                                             ? $param['floorNum']                  : '30';
        /** @var string houseNum 每层房屋数，取值范围[1,99]  因为无法变更 所以 取5*/
        $houseNum         = isset($param['houseNum'])                                             ? $param['houseNum']                  : '5';
        $params = [
            'buildingNames'       => $buildingNames,
            'buildingNumbers'     => $buildingNumbers,
            'unitNum'             => strval($unitNum),
            'floorNum'            => strval($floorNum),
            'houseNum'            => strval($houseNum),
            'pOrgCode'            => $pOrgCode,
        ];
        $result = $this->cloud->Building->addBuilding($params);
        return $result;
    }

    /**
     * 楼栋删除（单元房屋一并删除）
     * @param string $singleId
     * @param string $orgCode
     * @return array
     */
    public function deleteDHBuildUnitRoom(string $singleId, $orgCode = '') {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        if (!$orgCode && $singleId) {
            $face_bind_about_info = new FaceBindAboutInfo();
            $bind_type            = DahuaConst::DH_TO_CLOUD_BUILD;
            $third_protocol       = DahuaConst::DH_YUNRUI;
            $whereAbout = [
                'bind_type'        => $bind_type,
                'bind_id'          => $singleId,
                'third_protocol'   => $third_protocol,
            ];
            $aboutInfo = $face_bind_about_info->getOne($whereAbout, 'third_id');
            if ($aboutInfo && isset($aboutInfo['third_id'])) {
                $orgCode = $aboutInfo['third_id'];
            }
        }
        if (!$orgCode) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_ORG_CODE_CODE,
                'message' => DahuaConst::ERR_DH_NOT_ORG_CODE_MESSAGE,
            ];
        }
        $params = [
            $orgCode
        ];
        $result = $this->cloud->Building->deleteBuilding($params);
        return $result;
    }
    
    /**
     * 同步 添加更新设备 目前支持人脸和监控
     * @param string $deviceId 对应设备序列号
     * @param string $deviceType 对应设备类型 目前 face 人脸，camera 监控
     * @param array $param 同步参数 参考方法中具体注释
     * @param array $deviceInfo 设备信息
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addDeviceToCloud(string $deviceId, string $deviceType, array $param = [], array $deviceInfo = []) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        if (!$deviceId || !$deviceType) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_DEVICE_ID_CODE,
                'message' => DahuaConst::ERR_DH_NOT_DEVICE_ID_MESSAGE,
            ];
        }
        switch ($deviceType) {
            case DeviceConst::DEVICE_TYPE_FACE:
                $deviceTitle = '人脸';
                if (empty($deviceInfo)) {
                    $whereFace = [];
                    $whereFace[] = ['is_del', '=', 0];
                    $whereFace[] = ['device_sn', '=', $deviceId];
                    $faceField = 'device_id,device_name,device_type,device_alive,device_score,device_sn,
                village_id,floor_id,device_status,a3_device_id,public_area_id,thirdProtocol,thirdLoginName,thirdLoginPassword';
                    $deviceInfo = (new HouseFaceDeviceService())->getHouseFaceDeivceInfo($whereFace, $faceField);
                }
                if (!isset($deviceInfo['device_id'])) {
                    return [
                        'code'    => DahuaConst::ERR_DH_NOT_DEVICE_CODE,
                        'message' => DahuaConst::ERR_DH_NOT_DEVICE_MESSAGE,
                    ];
                }
                if (isset($deviceInfo['thirdLoginName']) && $deviceInfo['thirdLoginName']) {
                    $param['devUsername'] = $deviceInfo['thirdLoginName'];
                    $param['devPassword'] = $deviceInfo['thirdLoginPassword'];
                } else {
//                    $param['devUsername'] = 'admin';
//                    $param['devPassword'] = 'admin123';
                    // todo 测试临时写死 以上面为准
                    $param['devUsername'] = 'admin';
                    $param['devPassword'] = 'pigcms123';
                }
                if (isset($deviceInfo['cloud_device_id']) && $deviceInfo['cloud_device_id']) {
                    $param['isModify'] = 1;
                }
                break;
            case DeviceConst::DEVICE_TYPE_CAMERA:
                $deviceTitle = '监控';
                if (empty($deviceInfo)) {
                    $whereCamera = [];
                    $whereCamera[] = ['camera_status', '<>', 4];
                    $whereCamera[] = ['camera_sn', '=', $deviceId];
                    $cameraField = 'camera_id,camera_name as device_name,camera_sn,thirdLoginName,thirdLoginName';
                    $deviceInfo = (new HouseFaceDeviceService())->getCameraInfo($whereCamera, $cameraField);
                }
                if (isset($deviceInfo['thirdLoginName']) && $deviceInfo['thirdLoginName']) {
                    $param['devUsername'] = $deviceInfo['thirdLoginName'];
                    $param['devPassword'] = $deviceInfo['thirdLoginPassword'];
                } else {
//                    $param['devUsername'] = 'admin';
//                    $param['devPassword'] = 'admin123';
                    // todo 测试临时写死 以上面为准
                    $param['devUsername'] = 'admin';
                    $param['devPassword'] = 'lc888888';
                }
                break;
            default:
                return [
                    'code'    => DahuaConst::ERR_DH_NOT_DEVICE_TYPE_CODE,
                    'message' => DahuaConst::ERR_DH_NOT_DEVICE_TYPE_MESSAGE,
                ];
        }
        /*** 设备名称*/
        $name            = isset($deviceInfo['device_name']) ? $deviceInfo['device_name'] : $deviceTitle . '_' . $deviceId;

        /*** 要添加的组织场所id*/
        $storeId         = isset($param['storeId']) ? $param['storeId'] : '';
        if (!$storeId) {
            // todo 如果没有对应传参 可以自行查询下
            return [
                'code'    => DahuaConst::ERR_DH_NOT_STORE_ID_CODE,
                'message' => DahuaConst::ERR_DH_NOT_STORE_ID_MESSAGE,
            ];
        }
        /** @var string  devUsername 设备用户名(使用Base64编码传入,默认为admin) */
        $devUsername     = isset($param['devUsername']) ? strval($param['devUsername']) : 'admin';
        /** @var number  devPassword 设备密码(使用Base64编码传入，默认为admin123) */
        $devPassword     = isset($param['devPassword']) ? strval($param['devPassword']) : 'admin123';
        /** @var string devPassword 若为空开网关设备，则此字段填为10，非空开网关设备则不填 */
        $devType         = isset($param['devType']) ? intval($param['devType']) : 0;
        /** @var integer isEnableConfig 是否启用通道配置 0不启用（默认） 1启用 */
        $isEnableConfig  = isset($param['isEnableConfig']) ? intval($param['isEnableConfig']) : -1;
        /** @var integer channelNumber 配置通道数 1到256之间 */
        $channelNumber   = isset($param['channelNumber']) ? intval($param['channelNumber']) : 0;

        $params = [
            'storeId'      => $storeId,
            'name'         => $name,
            'deviceId'     => $deviceId,
            'devUsername'  => $devUsername,
            'devPassword'  => $devPassword,
        ];
        /** @var integer isModify 1 编辑 0添加 */
        $isModify        = isset($param['isModify']) ? intval($param['isModify']) : 0;
        if ($isModify>0) {
            // 如果编辑设备独立处理传参
            /** @var integer isEnableTransfer 是否开启设备名称下发 0-不启用，1-启用，默认不下发 */
            $isEnableTransfer  =  isset($param['isEnableTransfer']) ? intval($param['isEnableTransfer']) : -1;
            /** 
             * @var array channelList 通道数组 item 类型: object
             * [
             *     'channelName'     : '通道名称',
             *     'channelId'       : '通道号',
             *     'isEnableTransfer': '是否开启通道名称下发 0-不启用，1-启用，默认不下发',
             * ]
             */
            $channelList  =  isset($param['channelList']) ? $param['channelList'] : [];
            unset($params['devUsername'],$params['devPassword']);
            if ($isEnableTransfer!=-1) {
                $params['isEnableTransfer'] = $isEnableTransfer;
            }
            if (!empty($channelList)) {
                $params['channelList'] = $channelList;
            }
            $result = $this->cloud->Device->updateDevice($params);
        } else {
            // 如果添加设备独立处理传参
            if ($devType) {
                $params['devType'] = $devType;
            }
            if ($isEnableConfig!=-1) {
                $params['isEnableConfig'] = $isEnableConfig;
            }
            if ($isEnableConfig!=-1) {
                $params['isEnableConfig'] = $isEnableConfig;
            }
            if ($channelNumber) {
                $params['channelNumber'] = $channelNumber;
            }
            $result = $this->cloud->Device->addDevice($params);
        }
        $result['devUsername'] = $devUsername;
        $result['devPassword'] = $devPassword;
        $result['third_name']  = $name;
        return $result;
    }

    /**
     * 创建直播流  目前支持 flv（默认） 和  rtmp
     * @param string $deviceId 设备序列号
     * @param string $channelId 通道  默认'0'
     * @param array $param 其他参数
     * [
     *   'LiveType' => 'flv', // 监控流类型 flv（默认） 或者rtmp
     * ]
     * @return array
     */
    public function createDeviceLive(string $deviceId, string $channelId = '0', array $param = []) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        if (!$deviceId) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_DEVICE_ID_CODE,
                'message' => DahuaConst::ERR_DH_NOT_DEVICE_ID_MESSAGE,
            ];
        }
        /** @var string LiveType 监控流类型 */
        $LiveType   = isset($param['LiveType']) ? trim($param['LiveType']) : 'flv';
        if (!$LiveType) {
            $LiveType = 'flv';
        }
        $params = [
            'deviceId' => $deviceId,
            'channelId' => $channelId,
        ];
        switch ($LiveType) {
            case 'rtmp':
                $result = $this->cloud->Live->createDeviceRtmp($params);
                if (isset($result['code']) && in_array($result['code'], DahuaConst::DH_IS_EXISTENT_LIVE)) {
                    // 如果创建已经存在走查询
                    $result = $this->getDeviceLive($deviceId, $channelId, $param);
                }
                if (isset($result['data']['rtmpHD'])) {
                    $result['liveUrl'] = $result['data']['rtmpHD'];
                    $result['liveType'] = 'rtmp';
                } elseif (isset($result['data']['rtmp'])) {
                    $result['liveUrl'] = $result['data']['rtmp'];
                    $result['liveType'] = 'rtmp';
                }
                break;
            case 'flv':
            default:
                $result = $this->cloud->Live->createFlvLive($params);
                if (isset($result['code']) && in_array($result['code'], DahuaConst::DH_IS_EXISTENT_LIVE)) {
                    // 如果创建已经存在走查询
                    $result = $this->getDeviceLive($deviceId, $channelId, $param);
                }
                if (isset($result['data']['flvHD'])) {
                    $result['liveUrl'] = $result['data']['flvHD'];
                    $result['liveType'] = 'flv';
                } elseif (isset($result['data']['flv'])) {
                    $result['liveUrl'] = $result['data']['flv'];
                    $result['liveType'] = 'flv';
                }
                break;
        }
        return  $result;
    }

    /**
     * 图片上传
     * @param $photoUrl
     * @return array|string
     */
    public function uploadFileOSS($photoUrl) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        $fileHandle   = new FileHandle();
        $pathFile = explode('?', $photoUrl)[0];
        if($fileHandle->check_open_oss()) {
            try {
                $fileHandle->download($pathFile);
            } catch (\Exception $e) {
                
            }
        }
        $filePath     = $fileHandle->get_path($pathFile);
        if(strpos($filePath, '/') === 0){
            $dirFilePath = '.' . $filePath;
        } else {
            $dirFilePath = $filePath;
        }
        $pathinfo = pathinfo($dirFilePath);
        $fullFilePath = app()->getRootPath() . '../' . $filePath;
        $basename = $pathinfo['basename'];
        $ossParam = $this->getStoreMap();
        if (isset($ossParam['success']) && isset($ossParam['data']['accessId'])) {
            $ossParamData = $ossParam['data'];
        }
        $accessId        = isset($ossParamData['accessId'])    ? $ossParamData['accessId']    : '';
        $host            = isset($ossParamData['host'])        ? $ossParamData['host']        : '';
        $policy          = isset($ossParamData['policy'])      ? $ossParamData['policy']      : '';
        $signature       = isset($ossParamData['signature'])   ? $ossParamData['signature']   : '';
        $dir             = isset($ossParamData['dir'])         ? $ossParamData['dir']         : '';
        $key = $dir . '/' .$basename;
        $url = $host;
        $data_arr = array(
            'name'                  => $basename,
            'key'                   => $key,
            'OSSAccessKeyId'        => $accessId,
            'policy'                => $policy,
            'success_action_status' => '200',
            'signature'             => $signature,
        );
        $header = [];
        $header[] = "Content-Disposition: attachment;filename={$basename}";
        try {
            $rs = $this->curlUploadFile($url, $fullFilePath, false, $header, $data_arr);
        } catch (\Exception $e) {
            $key = '';
        }
        return $key;
    }

    public function curlUploadFile($url, $file, $https = false, $header = [], $otherData = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($https) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
        }
        if ($header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }

        $data = array('file' => curl_file_create($file, 'image/jpeg', 'file'));

        if ($otherData) {
            $data = array_merge($otherData, $data);
        }

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($ch);
//        $errorno = curl_errno($ch);
//        fdump_api($errorno, '$errorno');
        curl_close($ch);
        return $response;
    }
    
    /**
     * 获取oss图片上传配置项
     * @return array
     */
    public function getStoreMap() {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        $result = $this->cloud->Mixed->getStoreMap();
        return $result;
    }

    /***
     * 刷新OSS图片有效期
     * @param string $photoUrl
     * @return array
     */
    public function refreshOssImg(string $photoUrl) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        $result = $this->cloud->Mixed->refreshOssImg($photoUrl);
        return $result;
    }
    
    /**
     * 大华云睿-新增人员基础信息
     * @param array $param 添加的参数 看方法中具体备注 看需求传参
     * @return array
     */
    public function addPersonProfile(array $param=[]) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        /** @var string id
         * 更新标志，唯一id （参数非法）
         */
        $id              = isset($param['id'])                        ? strval($param['id'])                : '';
        /** @var string uid 
         * 平台用户id 
         */
        $uid             = isset($param['uid'])                       ? intval($param['uid'])             : 0;
        /** @var string name                                          
         * 必须 姓名 最大长度20（汉字，大小写字母，数字_-@#()）                            
         */                                                           
        $name            = isset($param['name'])                      ? strval($param['name'])              : '用户_'.$uid;
 
        /** @var string memo                                          
         * 备注，最大长度150，后端未对长度做限制，数据库长度为（65535）                         
         */                                                           
        $memo            = isset($param['memo'])                      ? strval($param['memo'])              : '';
        /** @var integer sex                                          
         * 性别:1-男，2-女 （未作限制）                                          
         */                                                           
        $sex             = isset($param['sex'])                       ? intval($param['sex'])             : '';
        /** @var integer type                                         
         * 人员类型:1-黑名单，2-白名单（后端未做限制）                                   
         */                                                           
        $type            = isset($param['type'])                      ? intval($param['type'])            : '';
        /** @var string orgCode 
         * 必须  组织编码，分页查询组织列表中的字段，只做了非空判断，无其他参数校验 
         */
        $orgCode         = isset($param['orgCode'])                   ? strval($param['orgCode'])           : '';
        /** @var string telephone                                     
         * 电话号码，场所内校验重复 （后端格式未做校验，做了重复性校验）                            
         */                                                           
        $telephone       = isset($param['telephone'])                 ? strval($param['telephone'])         : '';
        /** @var string email                                         
         * 邮箱（后端未做校验，数据库长度为50位）                                       
         */                                                           
        $email           = isset($param['email'])                     ? strval($param['email'])             : '';
        /** @var string facePhotoPath 
         * 人脸头像路径，20200427/7ec54895-df0f-4f78-9bf3-b5112c15b2ce.jpg(必须是文件夹开头的路径)  
         */
        $facePhotoPath   = isset($param['facePhotoPath'])             ? strval($param['facePhotoPath'])     : '';
        /** @var string nickName 
         * 昵称（后端未做校验，数据库长度160位）  
         */
        $nickName        = isset($param['nickName'])                  ? strval($param['nickName'])          : '';
        /** @var string certificateType
         * 0-身份证 1-护照 2-军官证 4-学生证 5-驾驶证 6-港澳通行证 7-其他证件（后端未做校验）
         */
        $certificateType = isset($param['certificateType'])          ? intval($param['certificateType'])  : '';
        /** @var string certificateNum
         * 证件号码（后端未做校验，数据库长度为255）
         */
        $certificateNum  = isset($param['certificateNum'])           ? strval($param['certificateNum'])   : '';
        /** @var string maritalStatus
         * 婚姻状况 0-未婚,1-已婚 （后端未做校验）
         */
        $maritalStatus   = isset($param['maritalStatus'])            ? intval($param['maritalStatus'])    : '';
        /** @var string birthday
         * 出生年月，”2020-03-17” （后端未做校验）
         */
        $birthday        = isset($param['birthday'])                 ? strval($param['birthday'])         : '';
        /** @var string education
         * 学历 0-初中、1-高中、2-专科、3-本科、4-硕士、5-博士 6-其他（后端未做校验）
         */
        $education       = isset($param['education'])                ? strval($param['education'])        : '';
        /** @var string workPlace
         * 工作单位 （后端未做限制，数据库长度为300个字符）
         */
        $workPlace       = isset($param['workPlace'])                ? strval($param['workPlace'])        : '';
        /** @var string address
         * 地址 （后端未做限制）
         */
        $address         = isset($param['address'])                  ? strval($param['address'])          : '';
        /** @var string storeId
         *  必须 地址 （后端未做限制）
         */
        $storeId         = isset($param['storeId'])                  ? strval($param['storeId'])          : '';
        /** @var string personCode
         *  必须 地址 （后端未做限制）
         */
        $personCode      = isset($param['personCode'])               ? strval($param['personCode'])       : '';
        /** @var string roomNo
         *  必须 地址 （后端未做限制）
         */
        $roomNo          = isset($param['roomNo'])                   ? strval($param['roomNo'])           : '';
        if (!$orgCode) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_ORG_CODE_CODE,
                'message' => DahuaConst::ERR_DH_NOT_ORG_CODE_MESSAGE,
            ];
        }
        if (!$storeId) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_STORE_ID_CODE,
                'message' => DahuaConst::ERR_DH_NOT_STORE_ID_MESSAGE,
            ];
        }
        $params = [];
        if ($id) {
            $params['id'] = $id;
        }
        $name = $this->replaceWord($uid, $name, '');
        $params['name']    = $name;
        $params['orgCode'] = $orgCode;
        $params['storeId'] = $storeId;
        if ($memo) {
            $params['memo']                 = $memo;
        }                                   
        if ($sex) {                         
            $params['sex']                  = $sex;
        }                                   
        if ($type) {                        
            $params['type']                 = $type;
        }                                   
        if ($telephone) {                   
            $params['telephone']            = $telephone;
        }                                   
        if ($email) {                       
            $params['email']                = $email;
        }                                   
        if ($facePhotoPath) {               
            $params['facePhotoPath']        = $facePhotoPath;
        }                                   
        if ($nickName) {                    
            $params['nickName']             = $nickName;
        }
        if ($certificateType) {
            $params['certificateType']      = $certificateType;
        }
        if ($certificateNum) {
            $params['certificateNum']       = $certificateNum;
        }
        if ($maritalStatus) {
            $params['maritalStatus']        = $maritalStatus;
        }
        if ($maritalStatus) {
            $params['maritalStatus']        = $maritalStatus;
        }
        if ($birthday) {
            $params['birthday']             = $birthday;
        }
        if ($education) {
            $params['education']            = $education;
        }
        if ($workPlace) {
            $params['workPlace']            = $workPlace;
        }
        if ($address) {
            $params['address']              = $address;
        }
        if ($personCode) {
            $params['personCode']           = $personCode;
        }
        if ($roomNo) {
            $params['roomNo']               = $roomNo;
        }
        
        if ($id) {
            $result = $this->cloud->Person->updatePersonProfile($params);
            if (isset($result['success']) && $result['success']) {
                $result['personFileId']  = $id;
                $result['operation']     = 'update';
            }
        } else {
            $result = $this->cloud->Person->addPersonProfile($params);
            if (isset($result['data']['personFileId'])) {
                $result['personFileId']  = $result['data']['personFileId'];
                $result['third_id']      = isset($result['data']['fileFaceId']) && $result['data']['fileFaceId'] ? $result['data']['fileFaceId'] : '';
                $result['operation']     = 'add';
            }
        }
        return $result;
    }

    /**
     * 特殊字符过滤
     * @param $pigcms_id
     * @param $word
     * @param $phone
     * @return string|string[]
     */
    public function replaceWord($pigcms_id,$word,$phone) {
        $pregTitleSave = "/[\x{4e00}-\x{9fa5}\·\.\",\?|A-Za-z\.\。\d？！!(（)）：:+-{}\[\]【】《》<>-_· “”：，]+/iu";
        $word1 = trim($word);
        preg_match_all($pregTitleSave, $word1, $titleArray);
        $newWord = implode('', $titleArray[0]);
        $search = array(
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
        $txt = str_replace($search, '', $newWord);
        if (empty($phone)) {
            //手机号为空
            if ($word1 != $txt) {
                //存在特殊字符 取组合昵称
                $str = '用户' . $pigcms_id;
            } else {
                //不存在特殊字符 取用户姓名
                $str = $txt;
            }
        } else {
            //手机号不为空
            if ($word1 != $txt) {
                //存在特殊字符，取手机号
                $str = $phone;
            } else {
                $str = $txt;
            }
        }
        if (empty($str) || strlen($str) > 16) {
            $str = '用户' . $pigcms_id;
        }
        fdump_api(['replaceWord_' . __LINE__, $pigcms_id, $word, $phone, $word1, $titleArray, $newWord, $txt, $str], 'replaceWordLog', 1);
        return $str;
    }

    public function addHouse(array $param=[]) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        /** @var string personFileId 必传 新增人员之后返回的字段或者分页查询中的id字段（不能为空） */
        $personFileId       = isset($param['personFileId'])              ? $param['personFileId']          : '';
        /** 
         * 父组织编码  
         * @var string houseInfo 必传  
         * [
         *    'orgCode'=> '', // 组织信息,这边显示的是房屋 例如:001279001001002 只能添加房屋节点
         * ]
         */
        $houseInfo          = isset($param['houseInfo'])                 ? $param['houseInfo']             : [];

        $params = [
            'personFileId' => $personFileId,
            'houseInfo'    => $houseInfo,
        ];
        $result = $this->cloud->Person->addHouse($params);
        return $result;
    }
    
    /**
     * 大华云睿-新增人证信息
     * @param array $param 添加的参数 看方法中具体备注 看需求传参
     * @return array
     */
    public function addPersonIdentity(array $param=[]) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        /** @var string id
         * 更新标志，唯一id （参数非法）
         */
        $id                 = isset($param['id'])                        ? $param['id']                    : '';
        /** @var string doorPassword
         * 开门密码，6位数字
         */
        $doorPassword       = isset($param['doorPassword'])              ? trim($param['doorPassword'])    : '';
        /** @var array personCards
         * 卡数据是覆盖的形式，场所下不允许重复。最多5张卡
         * [
         *     {
         *        cardNumber	    string	必须		卡号，8位16进制 （卡号必须是8位16进制，后端做了重复性校验）
         *        cardType	        number	必须		卡类型 0=普通卡 1=VIP 2=来宾卡 3=巡逻卡 5=胁迫卡 6=巡检卡 11=管理员卡 13=残疾卡
         *        masterSubCardType	number	必须		有且允许仅有一张主卡 0=主卡 1=副卡
         *     }
         * ]
         */
        $personCards        = isset($param['personCards'])               ? $param['personCards']           : [];
        /** @var array personFingerprints 指纹数据是覆盖的形式。最多3个指纹 */
        $personFingerprints = isset($param['personFingerprints'])        ? $param['personFingerprints']    : [];
        /** @var int validStartT 必传（包含卡片时候） 有效期开始时间。如果添加卡，必传。（例：1586275200000 ）（未传为空）  时间戳带毫秒的 */
        $validStartT        = isset($param['validStartT'])               ? $param['validStartT']           : '';
        /** @var int validEndT 必传（包含卡片时候） 有效期结束时间。如果添加卡，必传。（例：1586275200000 ）（未传为空）  时间戳带毫秒的 */
        $validEndT          = isset($param['validEndT'])                 ? $param['validEndT']             : '';
        /** @var string personFileId 必传 新增人员之后返回的字段或者分页查询中的id字段（不能为空） */
        $personFileId       = isset($param['personFileId'])              ? $param['personFileId']          : '';
        if (!$personFileId) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_PERSON_FILED_ID_CODE,
                'message' => DahuaConst::ERR_DH_NOT_PERSON_FILED_ID_MESSAGE,
            ];
        }
        /** @var string orgCode 必传 组织编码，分页查询组织列表中的字段（未做校验） */
        $orgCode            = isset($param['orgCode'])                   ? $param['orgCode']               : '';
        if (!$orgCode) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_ORG_CODE_CODE,
                'message' => DahuaConst::ERR_DH_NOT_ORG_CODE_MESSAGE,
            ];
        }
        /** @var string storeId 必传 场所编码，人员挂在楼栋单元房屋时，使用最近一个父节点场所编码（不能为空） */
        $storeId            = isset($param['storeId'])                   ? $param['storeId']               : '';
        if (!$storeId) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_STORE_ID_CODE,
                'message' => DahuaConst::ERR_DH_NOT_STORE_ID_MESSAGE,
            ];
        }
        $params = [
            'personFileId'    => $personFileId,
            'orgCode'         => $orgCode,
            'storeId'         => $storeId,
        ];
        if (!empty($doorPassword)) {
            $params['doorPassword'] = $doorPassword;
        }
        $nowTime = time();
        if (!empty($personCards)) {
            $params['personCards'] = $personCards;
            if (!$validStartT) {
                // 开始时间没有 默认去当前  结束时间延后10年
                $validStartT = $nowTime * 1000;
                $validEndT   = $validStartT + 315360000000;
            } elseif ($validStartT && $validEndT && $validEndT<$validStartT) {
                // 如果结束时间小区开始时间  重置开始时间为结束时间前2秒
                $validStartT = $validEndT - 2000;
            }
            $params['validStartT'] = $validStartT;
            $params['validEndT'] = $validEndT;
        }
        if ($personFingerprints) {
            $params['personFingerprints'] = $personFingerprints;
        }

        if ($id) {
            $params['id'] = $id;
            $result = $this->cloud->Person->updatePersonIdentity($params);
            if (isset($result['success']) && $result['success']) {
                $result['personFileId']  = $id;
                $result['operation']     = 'update';
            }
        } else {
            $result = $this->cloud->Person->addPersonIdentity($params);
            if (isset($result['success']) && $result['success']) {
                $result['personFileId']  = $personFileId;
                $result['operation']     = 'add';
            }
        }
        return $result;
    }

    /**
     * 新增开门计划
     * @param array $param 看代码备注
     * @return array
     */
    public function addTimePlan(array $param=[]) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        /** @var array monday 周一开门计划  当前默认 */
        $monday        = isset($param['monday']) && $param['monday'] ? $param['monday'] : [];
        if (empty($monday)) {
            $monday    = ["00:00:00-23:59:59","00:00:00-00:00:00","00:00:00-00:00:00","00:00:00-00:00:00"];
        }
        /** @var array tuesday 周二开门计划  当前默认 */
        $tuesday       = isset($param['tuesday']) && $param['tuesday'] ? $param['tuesday'] : [];
        if (empty($tuesday)) {
            $tuesday   = ["00:00:00-23:59:59","00:00:00-00:00:00","00:00:00-00:00:00","00:00:00-00:00:00"];
        }
        /** @var array wednesday 周三开门计划  当前默认 */
        $wednesday     = isset($param['wednesday']) && $param['wednesday'] ? $param['wednesday'] : [];
        if (empty($wednesday)) {
            $wednesday = ["00:00:00-23:59:59","00:00:00-00:00:00","00:00:00-00:00:00","00:00:00-00:00:00"];
        }
        /** @var array thursday 周四开门计划  当前默认 */
        $thursday      = isset($param['thursday']) && $param['thursday'] ? $param['thursday'] : [];
        if (empty($thursday)) {
            $thursday  = ["00:00:00-23:59:59","00:00:00-00:00:00","00:00:00-00:00:00","00:00:00-00:00:00"];
        }
        /** @var array friday 周五开门计划  当前默认 */
        $friday        = isset($param['friday']) && $param['friday'] ? $param['friday'] : [];
        if (empty($friday)) {
            $friday    = ["00:00:00-23:59:59","00:00:00-00:00:00","00:00:00-00:00:00","00:00:00-00:00:00"];
        }
        /** @var array saturday 周六开门计划  当前默认 */
        $saturday      = isset($param['saturday']) && $param['saturday'] ? $param['saturday'] : [];
        if (empty($saturday)) {
            $saturday  = ["00:00:00-23:59:59","00:00:00-00:00:00","00:00:00-00:00:00","00:00:00-00:00:00"];
        }
        /** @var array sunday 周日开门计划  当前默认 */
        $sunday        = isset($param['sunday']) && $param['sunday'] ? $param['sunday'] : [];
        if (empty($sunday)) {
            $sunday    = ["00:00:00-23:59:59","00:00:00-00:00:00","00:00:00-00:00:00","00:00:00-00:00:00"];
        }
        /** @var array name 设备id */
        $device_id     = isset($param['device_id']) && $param['device_id'] ? $param['device_id'] : 0;
        if (!$device_id) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_DEVICE_CODE,
                'message' => DahuaConst::ERR_DH_NOT_DEVICE_MESSAGE,
            ];
        }
        /** @var array name 开门计划名称 */
        $name          = isset($param['name']) && $param['name'] ? $param['name'] : '开门计划'.$device_id;
        $detail = [
            'monday'    => $monday,
            'tuesday'   => $tuesday,
            'wednesday' => $wednesday,
            'thursday'  => $thursday,
            'friday'    => $friday,
            'saturday'  => $saturday,
            'sunday'    => $sunday,
        ];
        $params = [
            'detail' => $detail,
            'name'   => $name,
        ];
        $result = $this->cloud->Asc->addDoorTimePlan($params);
        return $result;
    }

    /**
     * 同步人员授权
     * @param array $param
     * @return array
     * 此接口下发该人员到设备上。
     * 接口是实时返回。
     */
    public function addAuth(array $param=[]) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        /** @var string channelId 通道号，传0 */
        $channelId        = isset($param['channelId']) && $param['channelId'] ? $param['channelId'] : 0;
        /** @var string deviceId 设备序列号（设备分页查询接口返回的deviceId） */
        $deviceId         = isset($param['deviceId'])  && $param['deviceId']  ? $param['deviceId']  : 0;
        /** @var integer operateType 操作类型，1-新增，2-修改  3-删除*/
        $operateType      = isset($param['operateType'])  && $param['operateType']  ? intval($param['operateType'])  : 1;
        /** @var string personFileId 人员档案id（人员档案分页查询返回参数中的id） */
        $personFileId     = isset($param['personFileId'])  && $param['personFileId']  ? strval($param['personFileId'])  : '';
        /** @var string timePlanIndex 开门计划时间段索引,根据获取开门计划的接口而来,传null表示不启用开门计划 */
        $timePlanIndex     = isset($param['timePlanIndex'])  && $param['timePlanIndex']  ? strval($param['timePlanIndex'])  : '';
        $params = [
            'channelId'     => $channelId,
            'deviceId'      => $deviceId,
            'operateType'   => $operateType,
            'personFileId'  => $personFileId,
            'timePlanIndex' => $timePlanIndex,
        ];
        $result = $this->cloud->Asc->syncAuthPersonToDevice($params);
        return $result;
    }

    /**
     * 删除  注释参考添加
     * @param array $param
     * @return array
     */
    public function deleteAuth(array $param=[]) {
        $param['operateType'] = 3;
        return $this->addAuth($param);
    }

    /**
     * 远程开门
     * @param array $param 看下面代码注释
     * @return array
     */
    public function remoteOpenDoor(array $param=[]) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        /** @var integer userType 用户类型，0-管理员，1-业主 */
        $userType        = isset($param['userType'])     && $param['userType']     ? intval($param['userType']) : 0;
        /** @var string accessSource 设备来源，传0 */
        $accessSource    = isset($param['accessSource']) && $param['accessSource'] ? $param['accessSource']     : 0;
        /** @var string type 开门方式，传remote */
        $type            = isset($param['type'])         && $param['type']         ? $param['type']             : 'remote';
        /** @var string deviceId 设备序列号 */
        $deviceId        = isset($param['deviceId'])     && $param['deviceId']     ? $param['deviceId']         : '';
        /** @var string channelId 通道号，默认0 */
        $channelId       = isset($param['channelId'])    && $param['channelId']    ? $param['channelId']        : 0;
        /** @var string personFileId userType传0，该参数不传值。userType传1，该参数必传 */
        $personFileId    = isset($param['personFileId']) && $param['personFileId'] ? $param['personFileId']     : '';
        $params = [
            'userType'      => $userType,
            'accessSource'  => $accessSource,
            'type'          => $type,
            'deviceId'      => $deviceId,
            'channelId'     => $channelId,
        ];
        if ($userType==1) {
            $params['personFileId'] = $personFileId;
        }
        $result = $this->cloud->Asc->remoteOpenDoor($params);
        return $result;
    }

    /**
     * @param array $param
     * @return array
     *     {
     *     "code": "200",
     *     "data": {
     *            "5L0B757PAZ79B18": [ // 设备序列号
     *                         {
     *                            "channelId": "0", // 通道
     *                            "deviceId": "5L0B757PAZ79B18", // 设备序号
     *                            "status": "Open" // 当前开/关状态（开-Open、关-Close、未知null）
     *                        }
     *             ],
     *            "5L009C1AAZ0608A": [
     *                       {
     *                           "channelId": "0",
     *                           "deviceId": "5L009C1AAZ0608A",
     *                           "status": "Close"
     *                       },
     *                      {
     *                           "channelId": "1",
     *                           "deviceId": "5L009C1AAZ0608A",
     *                           "status": null
     *                        }
     *              ]
     *      },
     *     "errMsg": "success",
     *     "success": true
     *     }
     */
    public function batchGetDoorStatus(array $param=[]) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        /** @var string[] deviceIdList 设备ID，最多传100个 */
        $deviceIdList = isset($param['deviceIdList']) && $param['deviceIdList'] ? intval($param['deviceIdList']) : [];
        $result = $this->cloud->Asc->batchGetDoorStatus($deviceIdList);
        return $result;
    }

    /**
     * 获取单个组织（场所）详情
     * @param string $key 组织编码或场所ID
     * @return mixed
     */
    public function getOrgByNumberOrPlaceId($key) {
        $result = $this->cloud->Org->getOrgByNumberOrPlaceId($key);
        return $result;
    }

    /**
     * 获取业主开门记录
     * @param array $param
     * @return array
     */
    public function openDoorRecord(array $param=[]) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        /** @var string 必传 communityCode 场所编码 */
        $communityCode     = isset($param['communityCode'])     && $param['communityCode']     ? strval($param['communityCode']) : '';
        /** @var string startTime 开始时间 */
        $startTime         = isset($param['startTime'])         && $param['startTime']         ? $param['startTime']             : '';
        /** @var string endTime 结束时间 */
        $endTime           = isset($param['endTime'])           && $param['endTime']           ? $param['endTime']               : '';
        /*** 当前页数*/
        $pageNum           = isset($param['pageNum'])                                          ? $param['pageNum']               : 1;
        /*** 每页条数*/
        $pageSize          = isset($param['pageSize'])                                         ? $param['pageSize']              : 50;
        /** @var string[] communityCodes 场所编码集合 */                                             
        $communityCodes    = isset($param['communityCodes'])    && $param['communityCodes']    ? $param['communityCodes']        : [];
        /** @var string communityName 场所名称 */                                                  
        $communityName     = isset($param['communityName'])     && $param['communityName']     ? $param['communityName']         : '';
        /** @var string[] storeIds 场所ids */                                                    
        $storeIds          = isset($param['storeIds'])          && $param['storeIds']          ? $param['storeIds']              : [];
        /** @var string channelName 通道名称 */                                                    
        $channelName       = isset($param['channelName'])       && $param['channelName']       ? $param['channelName']           : '';
        /** @var string personName 人员姓名 */                                                     
        $personName        = isset($param['personName'])        && $param['personName']        ? $param['personName']            : '';
        /** @var string[] healthCodeStatus 健康码状态 */                                            
        $healthCodeStatus  = isset($param['healthCodeStatus'])  && $param['healthCodeStatus']  ? $param['healthCodeStatus']      : [];
        /** @var string[] wearMask 口罩佩戴 */                                                     
        $wearMask          = isset($param['wearMask'])          && $param['wearMask']          ? $param['wearMask']              : [];
        /** @var string[] vaccineStatus 疫苗接种 */                                                
        $vaccineStatus     = isset($param['vaccineStatus'])     && $param['vaccineStatus']     ? $param['vaccineStatus']         : [];
        /** @var string[] checkResult 核酸检测 */                                                  
        $checkResult       = isset($param['checkResult'])       && $param['checkResult']       ? $param['checkResult']           : [];
        /** @var string[] temperatureStatus 温度 */
        $temperatureStatus = isset($param['temperatureStatus']) && $param['temperatureStatus'] ? $param['temperatureStatus']     : [];
        /** @var string eventTime 来访时间 */
        $eventTime         = isset($param['eventTime'])         && $param['eventTime']         ? $param['eventTime']             : '';
        /** @var string existPicture 是否有图片 */
        $existPicture      = isset($param['existPicture'])      && $param['existPicture']      ? $param['existPicture']          : '';
        /** @var string personCode 人员编码 */
        $personCode        = isset($param['personCode'])        && $param['personCode']        ? $param['personCode']            : '';
        /** @var string cardNumber 卡号 */
        $cardNumber        = isset($param['cardNumber'])        && $param['cardNumber']        ? $param['cardNumber']            : '';
        /** @var number[] enterOrExits 进出标志（1进门，2出门） */
        $enterOrExits      = isset($param['enterOrExits'])      && $param['enterOrExits']      ? $param['enterOrExits']          : '';
        /** @var number[] types   开门类型编号数组 */
        $types             = isset($param['types'])             && $param['types']             ? $param['types']                 : '';
        /** @var number[] openResults   开门结果（1成功，2失败） */
        $openResults       = isset($param['openResults'])       && $param['openResults']       ? $param['openResults']           : '';
        /** @var string keyWord   模糊搜索关键字 */
        $keyWord           = isset($param['keyWord'])           && $param['keyWord']           ? $param['keyWord']               : '';
        /** 
         * @var string devices   设备信息
         * [
         *     'deviceId'  => '设备序列号',
         *     'channelId' => '通道号',
         * ]
         */
        $devices           = isset($param['devices'])           && $param['devices']           ? $param['devices']               : '';

        $params = [
            'pageNum'       => intval($pageNum),
            'pageSize'      => intval($pageSize),
            'communityCode' => strval($communityCode),
        ];
        if (!empty($communityCodes)) {
            $params['communityCodes']    = $communityCodes;
        }                                
        if ($communityName) {            
            $params['communityName']     = $communityName;
        }                                
        if ($storeIds) {                 
            $params['storeIds']          = $storeIds;
        }                                
        if ($channelName) {              
            $params['channelName']       = $channelName;
        }                                
        if ($personName) {               
            $params['personName']        = $personName;
        }                                
        if ($healthCodeStatus) {         
            $params['healthCodeStatus']  = $healthCodeStatus;
        }                                
        if ($wearMask) {                 
            $params['wearMask']          = $wearMask;
        }                                
        if ($vaccineStatus) {            
            $params['vaccineStatus']     = $vaccineStatus;
        }                                
        if ($checkResult) {              
            $params['checkResult']       = $checkResult;
        }
        if ($temperatureStatus) {
            $params['temperatureStatus'] = $temperatureStatus;
        }
        if ($eventTime) {
            $params['eventTime']         = $eventTime;
        }
        if ($existPicture) {
            $params['existPicture']      = $existPicture;
        }
        if ($personCode) {
            $params['personCode']        = $personCode;
        }
        if ($cardNumber) {
            $params['cardNumber']        = $cardNumber;
        }
        if ($startTime) {
            $params['startTime']         = $startTime;
        }
        if ($endTime) {
            $params['endTime']           = $endTime;
        }
        if ($enterOrExits) {
            $params['enterOrExits']      = $enterOrExits;
        }
        if ($types) {
            $params['types']             = $types;
        }
        if ($openResults) {
            $params['openResults']       = $openResults;
        }
        if ($keyWord) {
            $params['keyWord']           = $keyWord;
        }
        if ($devices) {
            $params['devices']           = $devices;
        }
        $result = $this->cloud->Asc->getWonerDoorOpenRecord($params);
//        fdump_api([$params, $result], '$getWonerDoorOpenRecord');
        return $result;
    }
    
    /*****************查询区块************************/
    /**
     * @param array $param
     * [
     *      'pageNum'   => '查询页数 默认取第一页',
     *      'pageSize' => '每页条数 默认100',
     * ]
     * @return array
     */
    public function getOrgList(array $param = []) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        /*** 当前页数*/
        $pageNum    = isset($param['pageNum']) ? $param['pageNum'] : 1;
        /*** 每页条数*/
        $pageSize  = isset($param['pageSize']) ? $param['pageSize'] : 5;
        /*** 支持按组织编码过滤*/
        $orgCode  = isset($param['orgCode']) ? $param['orgCode'] : '';
        /*** 支持按父组织编码过滤，返回该组织及子组织全部*/
        $pOrgCode  = isset($param['pOrgCode']) ? $param['pOrgCode'] : '';
        /*** 支持按组织类型过滤，1-组织、2-场所、10-楼栋、11-单元、12-房屋*/
        $orgType  = isset($param['orgType']) ? $param['orgType'] : '';
        /*** 是否获取根节点 默认获取 */
        $getOrgGroup  = isset($param['getOrgGroup']) ? $param['getOrgGroup'] : true;

        $params = [
            'pageNum'  => intval($pageNum),
            'pageSize' => intval($pageSize),
        ];
        if ($orgCode) {
            $params['orgCode'] = strval($orgCode);
        }
        if ($pOrgCode) {
            $params['pOrgCode'] = strval($pOrgCode);
        }
        if ($orgType) {
            $params['orgType'] = intval($orgType);
        }
        $result = $this->cloud->Org->getOrgList($params);
        $orgGroup = [];
        if ($getOrgGroup && isset($param) && isset($result['data']['pageData']) && $result['data']['pageData']) {
            foreach ($result['data']['pageData'] as $group) {
                if ((isset($group['orgCode']) && $group['orgCode']=='001') || (isset($group['orgName']) && $group['orgName']=='根节点')) {
                    $orgGroup = $group;
                    break;
                }
            }
        }
        $result['orgGroup'] = $orgGroup;
        return $result;
    }

    /**
     * 根据名称查询场所
     * @param string $storeName 组织名称
     * @return array
     */
    public function getStoreByStoreName(string $storeName) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        $result = $this->cloud->Org->getPlaceByName($storeName);
        return $result;
    }

    /**
     * 查询设备详情
     * @param string $deviceId 设备序列号
     * @return array 返回设备具体信息
     * code	   string	非必须
     * data	   object	非必须
     *   +ability	    string	  必须		设备能力集
     *   +baseline	    string	  必须		设备基线类型
     *   +brand	        string	  必须		设备品牌信息：lechange-乐橙设备，general-通用设备
     *   +canBeUpgrade	boolean	  必须		是否有新版本可以升级
     *   +channelList   object[]  必须		通道列表	item 类型: object
     *       ++alarmStatus	     number	  必须		报警布撤防状态，0-撤防，1-布防
     *       ++channelId	     string	  必须		通道号
     *       ++channelName	     string	  必须		通道名称
     *       ++channelPicUrl	 string	  非必须
     *       ++csStatus	         number	  必须		云存储状态：-1-未开通 0-已失效 1-使用中 2-套餐暂停
     *       ++onlineStatus	     number	  必须		通道是否在线,1表示在线,0表示离线
     *       ++shareStatus	     number	  必须		是否分享给别人的,1表示分享,0表示未分享
     *   +deviceCatalog	string	  必须		设备分类（NVR/DVR/HCVR/IPC/SD/IHG/ARC)
     *   +deviceId	    string	  必须		设备序列号
     *   +deviceModel	string	  非必须		设备型号
     *   +name	        string	  必须		设备名称
     *   +platForm	    number	  非必须
     *   +status	    number	  必须		当前设备状态：0-离线，1-在线，3-升级中
     *   +storeId	    string	  必须
     *   +storeName	    string	  非必须
     *   +version	    string	  必须
     * errMsg	string	非必须
     * success	boolean	非必须			
     */
    public function getDeviceInfo(string $deviceId) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        $params = [
            'deviceId' => $deviceId,
        ];
		
		$result = $this->cloud->Device->getDeviceInfo($params);
        return $result;
    }

    /**
     * 获取直播流 目前支持 flv（默认） 和  rtmp
     * @param string $deviceId 设备序列号
     * @param string $channelId 设备通道
     * @param array $param 其他参数
     * [
     *   'LiveType' => 'flv', // 监控流类型 flv（默认） 或者rtmp
     * ]
     * @return array
     */
    public function getDeviceLive(string $deviceId, string $channelId = '0', array $param = []) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        if (!$deviceId) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_DEVICE_ID_CODE,
                'message' => DahuaConst::ERR_DH_NOT_DEVICE_ID_MESSAGE,
            ];
        }
        /** @var integer LiveType 监控流类型 */
        $LiveType   = isset($param['LiveType']) ? intval($param['LiveType']) : 'rtmp';
        $params = [
            'deviceId' => $deviceId,
            'channelId' => $channelId,
        ];
        switch ($LiveType) {
            case 'flv':
                $result = $this->cloud->Live->getFlvLive($params);
                if (isset($result['flvHD'])) {
                    $result['liveUrl'] = $result['flvHD'];
                    $result['liveType'] = 'flv';
                } elseif (isset($result['flv'])) {
                    $result['liveUrl'] = $result['flv'];
                    $result['liveType'] = 'flv';
                }
                break;
            case 'rtmp':
            default:
                $result = $this->cloud->Live->getDeviceRtmp($params);
                if (isset($result['rtmp'])) {
                    $result['liveUrl'] = $result['rtmp'];
                    $result['liveType'] = 'rtmp';
                } elseif (isset($result['rtmpHD'])) {
                    $result['liveUrl'] = $result['rtmpHD'];
                    $result['liveType'] = 'rtmp';
                }
                break;
        }
        return  $result;
    }

    public function getBuildingRoomForPage(array $param) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        /***@var int pageNum  当前页数*/
        $pageNum    = isset($param['pageNum'])  ? $param['pageNum']  : 1;
        /***@var int pageSize 每页条数*/
        $pageSize   = isset($param['pageSize']) ? $param['pageSize'] : 5;
        /***@var int orgType 支持按组织类型过滤，10-楼栋、11-单元、12-房屋*/
        $orgType    = isset($param['orgType'])  ? $param['orgType']  : 10;
        /***@var string pOrgCode 支持按父组织编码过滤，返回该组织及子组织全部*/
        $pOrgCode   = isset($param['pOrgCode']) ? $param['pOrgCode'] : '001101100';
        /***@var string orgCode 支持按组织编码过滤*/
        $orgCode    = isset($param['orgCode'])  ? $param['orgCode']  : '';
        $params = [
            'pageNum'  => $pageNum,
            'pageSize' => $pageSize,
            'orgType'  => $orgType,
        ];
        if ($pOrgCode) {
            $params['pOrgCode'] = $pOrgCode;
        }
        if ($orgCode) {
            $params['orgCode'] = $orgCode;
        }
        $result = $this->cloud->Building->getBuildingRoomForPage($params);
        return $result;
    }
    
    /**
     * 查询楼栋
     * @param array $param  具体参数看代码注释
     * @return array
     */
    public function getBuildingForPage(array $param) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        /***@var int pageNum  当前页数*/
        $pageNum    = isset($param['pageNum'])  ? $param['pageNum']  : 1;
        /***@var int pageSize 每页条数*/
        $pageSize   = isset($param['pageSize']) ? $param['pageSize'] : 50;
        /***@var string pOrgCode 支持按父组织编码过滤，返回该组织及子组织全部*/
        $pOrgCode   = isset($param['pOrgCode']) ? $param['pOrgCode'] : '';
        $params = [
            'pageNum'  => $pageNum,
            'pageSize' => $pageSize,
        ];
        if ($pOrgCode) {
            $params['pOrgCode'] = $pOrgCode;
        }
        $result = $this->cloud->Building->getBuildingForPage($params);
        return $result;
    }

    /**
     * 查询楼栋、单元、房屋信息
     * @param array $param  具体参数看代码注释
     * @return array
     */
    public function getBuidingUnitRoomList(array $param) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        /***@var int pageNum  当前页数*/
        $pageNum    = isset($param['pageNum'])  ? $param['pageNum']  : 1;
        /***@var int pageSize 每页条数*/
        $pageSize   = isset($param['pageSize']) ? $param['pageSize'] : 50;
        /***@var int orgType 支持按组织类型过滤，10-楼栋、11-单元、12-房屋*/
        $orgType    = isset($param['orgType'])  ? $param['orgType']  : 10;
        /***@var string pOrgCode 支持按父组织编码过滤，返回该组织及子组织全部*/
        $pOrgCode   = isset($param['pOrgCode']) ? $param['pOrgCode'] : '';
        /***@var string orgCode 支持按组织编码过滤*/
        $orgCode    = isset($param['orgCode'])  ? $param['orgCode']  : '';
        $params = [
            'pageNum'  => $pageNum,
            'pageSize' => $pageSize,
            'orgType'  => $orgType,
        ];
        if ($pOrgCode) {
            $params['pOrgCode'] = $pOrgCode;
        }
        if ($orgCode) {
            $params['orgCode'] = $orgCode;
        }
        $result = $this->cloud->Building->getBuidingUnitRoomList($params);
        return $result;
    }

    /**
     * 获取token
     * @return array
     */
    public function getCloudAccessToken() {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        $result = $this->cloud->Auth->getAccessToken();
        return $result;
    }


    /**
     * 获取乐橙播放token
     * @return array
     */
    public function getLeChengUserToken()
    {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        $result = $this->cloud->Mixed->getLeChengUserToken();
        return $result;
    }

    /**
     * 纯云app注册sip
     * @param $phone
     * @return array
     */
    public function registerChunYunSip($phone) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        $result = $this->cloud->Mixed->registerChunYunSip($phone);
        return $result;
    }

    /**
     * 获取云睿平台配置
     * @return array
     */
    public function getLechangeConfig() {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        $result = $this->cloud->Auth->getLechangeConfig();
        return $result;
    }

    /**
     * 获取设置乐橙服务地址, 开发者默认填写openapi.lechange.cn:443即可
     * @return array
     */
    public function getLcOpenSDKApi($userInfo = []) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        $lc_open_sdk_api_host = "openapi.lechange.cn:443";
        $community_cloud_host = "https://www.cloud-dahua.com:443/gateway";
        $sip_server_ip_1      = "120.27.208.202";
        $sip_server_port_1    = 5080;
        $sip_server_ip        = "sip.cloud-dahua.com";
        $sip_server_port      = 5082;
        if (isset($userInfo['phone']) && $userInfo['phone']) {
            $phone_number     = $userInfo['phone'];
        } else {
            $phone_number     = '';
        }
        $data = [
            'lc_open_sdk_api_host' => $lc_open_sdk_api_host,
            'community_cloud_host' => $community_cloud_host,
            'sip_server_ip_1'      => $sip_server_ip_1,
            'sip_server_port_1'    => $sip_server_port_1,
            'sip_server_ip'        => $sip_server_ip,
            'sip_server_port'      => $sip_server_port,
            'phone_number'         => $phone_number,
        ];
        return [
          "code"=> 200,
          "message"=> "获取成功",
          "data"=> $data,
        ];
    }
    
    /*****************删除区块************************/
    /**
     * 删除组织
     * @param string $orgCode 要删除的组织编码
     * @return array
     * {
     *    "code": "0",
     *    "data": {},
     *    "errMsg": "",
     *    "success": true
     * }
     */
    public function deletePropertyToCloud(string $orgCode) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        $result = $this->cloud->Org->deleteOrg($orgCode);
        return $result;
    }

    /**
     * 删除设备
     * @param string $deviceId 设备序列号
     * @return array
     * {
     *    "code": "0",
     *    "data": {},
     *    "errMsg": "",
     *    "success": true
     * }
     */
    public function deleteDeviceToCloud(string $deviceId) {
        if (!isset($this->config['client_id'])) {
            return [
                'code'    => DahuaConst::ERR_DH_NOT_CONFIGURED_CODE,
                'message' => DahuaConst::ERR_DH_NOT_CONFIGURED_MESSAGE,
            ];
        }
        $result = $this->cloud->Device->deleteDevice($deviceId);
        return $result;
    }
}