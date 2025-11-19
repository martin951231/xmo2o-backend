<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      海康云眸-企业内部应用开发-社区
 */

namespace app\community\model\service\Device;

use app\community\model\db\CameraDeviceBind;
use app\community\model\db\Device\DeviceHikCloudCommunities;
use app\community\model\db\HouseCameraDevice;
use app\community\model\db\HouseDeviceChannel;
use app\community\model\db\HouseVillage;
use app\consts\DeviceConst;
use app\consts\HikConst;
use Imactool\Hikcloud\HikCloud;
use app\traits\FaceDeviceHikCloudTraits;

class Hik6000CCameraService
{
    use FaceDeviceHikCloudTraits;
    
    public $config;
    public $hikCloud;
    protected $dbDeviceHikCloudCommunities;
    protected $dbHouseVillage;
    protected $dbHouseCameraDevice;
    protected $dbCameraDeviceBind;
    protected $dbHouseDeviceChannel;
    protected $nowTime;
    
    public function __construct(){
        if (!$this->hikCloud) {
            if (cfg('HikCloudClientId') && cfg('HikCloudClientSecret')) {
                $this->config = [
                    'client_id' => cfg('HikCloudClientId'),
                    'client_secret' => cfg('HikCloudClientSecret')
                ];
                $this->hikCloud = new HikCloud($this->config);
            }
        }
        if (!$this->nowTime) {
            $this->nowTime = time();
        }
    }
    // 判断海康内部应用是否配置了
    public function judgeConfig() {
        $hikCloud6000CSwitch = cfg('HikCloud6000CSwitch');
        if (!$hikCloud6000CSwitch || !isset($this->config['client_id']) || !isset($this->config['client_secret']) || !$this->config['client_id'] || !$this->config['client_secret']) {
            return false;
        } else {
            return true;
        }
    }
    
    public function getVillageJudgeConfig(int $village_id) {
        if (!$this->judgeConfig()) {
            return [];
        }
        $hikCloudInfo = $this->getHouseBindCommunity($village_id);
        if (empty($hikCloudInfo)) {
            return [];
        }
        return $hikCloudInfo;
    }

    /**
     * 获取对应社区并保存
     * @param array $param
     * @return array|mixed
     * @throws \think\Exception
     */
    public function getSystemCommunities($param = []) {
        if (!$this->judgeConfig()) {
            return [
                'code'    => HikConst::ERR_MESSAGE_NOT_CONFIGURED_CODE,
                'message' => HikConst::ERR_MESSAGE_NOT_CONFIGURED,
            ];
        }
        /*** 当前页数*/
        $pageNo    = isset($param['pageNo']) ? $param['pageNo'] : 1;
        /*** 每页条数*/
        $pageSize  = isset($param['pageSize']) ? $param['pageSize'] : 100;
        /** @var string unique_one_voucher 同一次获取凭证 */
        $unique_one_voucher  = isset($param['unique_one_voucher']) ? $param['unique_one_voucher'] : date('ymdHis') . md5(uniqid());

        $params = [
            'pageNo'   => $pageNo,
            'pageSize' => $pageSize,
        ];
        $result = $this->hikCloud->Communit->getCommunities($params);
        $data = isset($result['data']) && $result['data'] ? $result['data'] : [];
        if (!empty($data)) {
            // 取出数据处理
            $rows = isset($data['rows']) ? $data['rows'] : [];
            if (!empty($rows)) {
                $this->saveSystemCommunities($rows, $unique_one_voucher);
            }
            $hasNextPage = isset($data['hasNextPage']) ? $data['hasNextPage'] : false;
            // 是否有下一页（true：是）
            if ($hasNextPage) {
                $param = [
                    'pageNo'             => $pageNo + 1,
                    'pageSize'           => $pageSize,
                    'unique_one_voucher' => $unique_one_voucher,
                ];
                return $this->getSystemCommunities($param);
            }
        } elseif (isset($result['code']) && $result['code'] != '200' && isset($result['message']) && $result['message']) {
            throw new \think\Exception($result['message']);
        }
        return $data;
    }

    /**
     * 保存获取到的三方社区信息
     * @param array $rows
     * 文档地址：https://pic.hik-cloud.com/opencustom/apidoc/online/neptune/ad8f6acacc2243a1a1c749fb7e8a7105.html?timestamp=1664366358662#%E6%9F%A5%E8%AF%A2%E7%A4%BE%E5%8C%BA71JqUO
     * 属性名称	             属性描述	                       类型	    长度	    是否必填
     * communityId	         云眸平台的社区id	                    String	32	    是
     * structName	         楼栋结构	                        String	512	    是
     * communityName         社区名称	                        String	10	    是
     * provinceCode	         省代号	                            String	6	    是
     * cityCode	             市代号	                            String	6	    否
     * countyCode	         区代号	                            String	6	    否
     * communityAddress	     地址（省/市/区）	                    String	50	    是
     * addressDetail	     街道详细地址	                        String	50	    是
     * communitySquareMeter	 社区面积(万㎡) 面积最多8位整数, 2位小数	String	10	    否
     * longitude	         经度坐标值	                        String	16	    否
     * latitude	             维度坐标值	                        String	16	    否
     * chargePersonId	     负责人ID（该社区的物业负责人）	        String	32	    否
     * chargePersonName	     负责人名字（该社区的物业负责人）	        String	25	    否
     * phoneNumber	         联系方式	                        String	20	    否
     * communityRemark	     备注	                            String	100	    否
     * @param string $unique_one_voucher
     * @return bool
     */
    protected function saveSystemCommunities(array $rows, $unique_one_voucher = '') {
        foreach ($rows as $item) {
            $hikCloudCommunitiesData = [];
            $community_id           = isset($item['communityId'])          && $item['communityId']          ? $item['communityId']          : '';
            if (!$community_id) {
                continue;
            }
            $struct_name            = isset($item['structName'])           && $item['structName']           ? $item['structName']           : '';
            $community_name         = isset($item['communityName'])        && $item['communityName']        ? $item['communityName']        : '';
            $province_code          = isset($item['provinceCode'])         && $item['provinceCode']         ? $item['provinceCode']         : '';
            $city_code              = isset($item['cityCode'])             && $item['cityCode']             ? $item['cityCode']             : '';
            $county_code            = isset($item['countyCode'])           && $item['countyCode']           ? $item['countyCode']           : '';
            $community_address      = isset($item['communityAddress'])     && $item['communityAddress']     ? $item['communityAddress']     : '';
            $address_detail         = isset($item['addressDetail'])        && $item['addressDetail']        ? $item['addressDetail']        : '';
            $community_square_meter = isset($item['communitySquareMeter']) && $item['communitySquareMeter'] ? $item['communitySquareMeter'] : '';
            $longitude              = isset($item['longitude'])            && $item['longitude']            ? $item['longitude']            : '';
            $latitude               = isset($item['latitude'])             && $item['latitude']             ? $item['latitude']             : '';
            $charge_person_id       = isset($item['chargePersonId'])       && $item['chargePersonId']       ? $item['chargePersonId']       : '';
            $charge_person_name     = isset($item['chargePersonName'])     && $item['chargePersonName']     ? $item['chargePersonName']     : '';
            $phone_number           = isset($item['phoneNumber'])          && $item['phoneNumber']          ? $item['phoneNumber']          : '';
            $community_remark       = isset($item['communityRemark'])      && $item['communityRemark']      ? $item['communityRemark']      : '';
            $hikCloudCommunitiesData = [
                'community_id'           => $community_id,
                'struct_name'            => $struct_name,
                'community_name'         => $community_name,
                'province_code'          => $province_code,
                'city_code'              => $city_code,
                'county_code'            => $county_code,
                'community_address'      => $community_address,
                'address_detail'         => $address_detail,
                'community_square_meter' => $community_square_meter,
                'longitude'              => $longitude,
                'latitude'               => $latitude,
                'charge_person_id'       => $charge_person_id,
                'charge_person_name'     => $charge_person_name,
                'phone_number'           => $phone_number,
                'community_remark'       => $community_remark,
            ];
            $whereOnly = [];
            $whereOnly[] = ['del_time',     '=', 0];
            $whereOnly[] = ['community_id', '=', $community_id];
            if (!$this->dbDeviceHikCloudCommunities) {
                $this->dbDeviceHikCloudCommunities = new DeviceHikCloudCommunities();
            }
            $onlyInfo = $this->dbDeviceHikCloudCommunities->getOne($whereOnly, 'hik_cloud_id');
            if ($onlyInfo && !is_array($onlyInfo)) {
                $onlyInfo = $onlyInfo->toArray();
            }
            if ($unique_one_voucher) {
                $hikCloudCommunitiesData['unique_one_voucher'] = $unique_one_voucher;
            }
            if ($onlyInfo && isset($onlyInfo['hik_cloud_id']) && $onlyInfo['hik_cloud_id']) {
                $hikCloudCommunitiesData['update_time'] = $this->nowTime;
                $this->dbDeviceHikCloudCommunities->updateThis($whereOnly, $hikCloudCommunitiesData);
            } else {
                $hikCloudCommunitiesData['add_time'] = $this->nowTime;
                $this->dbDeviceHikCloudCommunities->add($hikCloudCommunitiesData);
            }
        }
        return true;
    }

    /**
     * 绑定小区和云社区
     * @param int $village_id
     * @param string $community_id
     * @param array $param
     * @return array
     * @throws \think\Exception
     */
    public function bindHouseToSystemCommunity(int $village_id, string $community_id, array $param = []) {
        $whereOnly = [];
        $whereOnly[] = ['del_time',     '=', 0];
        $whereOnly[] = ['community_id', '=', $community_id];
        if (!$this->dbDeviceHikCloudCommunities) {
            $this->dbDeviceHikCloudCommunities = new DeviceHikCloudCommunities();
        }
        $hikCloudInfo = $this->dbDeviceHikCloudCommunities->getOne($whereOnly, 'hik_cloud_id, village_id');
        if ($hikCloudInfo && !is_array($hikCloudInfo)) {
            $hikCloudInfo = $hikCloudInfo->toArray();
        }
        if (empty($hikCloudInfo) || !isset($hikCloudInfo['hik_cloud_id']) || !$hikCloudInfo['hik_cloud_id']) {
            throw new \think\Exception("绑定的云社区不存在或者已经被删除");
        }
        if ($hikCloudInfo['village_id'] > 0 && $hikCloudInfo['village_id'] != $village_id) {
            throw new \think\Exception("对应云社区已经绑定了其他小区");
        }
        if (!$this->dbHouseVillage) {
            $this->dbHouseVillage = new HouseVillage();
        }
        $villageInfo = $this->dbHouseVillage->getOne($village_id, 'village_id, status');
        if ($villageInfo && !is_array($villageInfo)) {
            $villageInfo = $villageInfo->toArray();
        }
        if (empty($villageInfo) || !isset($villageInfo['village_id']) || !$villageInfo['village_id']) {
            throw new \think\Exception("要进行绑定的小区不存在或者已经被删除");
        }
        if ($villageInfo['status'] != 1) {
            throw new \think\Exception("要进行绑定的小区状态异常");
        }
        $bindData = [
            'village_id' => $village_id,
            'bind_time'  => $this->nowTime,
        ];
        $update = $this->dbDeviceHikCloudCommunities->updateThis($whereOnly, $bindData);
        if ($update !== false) {
            return [
                'village_id'   => $village_id,
                'community_id' => $community_id,
            ];
        } else {
            throw new \think\Exception("绑定失败");
        }
    }

    /**
     * 解绑小区和云社区
     * @param int $village_id
     * @param string $community_id
     * @param array $param
     * @return array
     * @throws \think\Exception
     */
    public function unBindHouseToSystemCommunity(int $village_id, string $community_id, array $param = []) {
        $whereOnly = [];
        $whereOnly[] = ['del_time',     '=', 0];
        $whereOnly[] = ['community_id', '=', $community_id];
        if (!$this->dbDeviceHikCloudCommunities) {
            $this->dbDeviceHikCloudCommunities = new DeviceHikCloudCommunities();
        }
        $hikCloudInfo = $this->dbDeviceHikCloudCommunities->getOne($whereOnly, 'hik_cloud_id, village_id');
        if ($hikCloudInfo && !is_array($hikCloudInfo)) {
            $hikCloudInfo = $hikCloudInfo->toArray();
        }
        if (empty($hikCloudInfo) || !isset($hikCloudInfo['hik_cloud_id']) || !$hikCloudInfo['hik_cloud_id']) {
            throw new \think\Exception("绑定的云社区不存在或者已经被删除");
        }
        if ($hikCloudInfo['village_id'] > 0 && $hikCloudInfo['village_id'] != $village_id) {
            throw new \think\Exception("对应云社区已经绑定了其他小区");
        }
        $whereBind = [];
        $whereBind[] = ['del_time',     '=', 0];
        $whereBind[] = ['community_id', '=', $community_id];
        $whereBind[] = ['village_id',   '=', $village_id];
        $hikCloudInfo = $this->dbDeviceHikCloudCommunities->getOne($whereBind, 'hik_cloud_id, village_id');
        if ($hikCloudInfo && !is_array($hikCloudInfo)) {
            $hikCloudInfo = $hikCloudInfo->toArray();
        }
        if (empty($hikCloudInfo) || !isset($hikCloudInfo['hik_cloud_id']) || !$hikCloudInfo['hik_cloud_id']) {
            return [
                'village_id'   => $village_id,
                'community_id' => $community_id,
            ];
        }
        $bindData = [
            'village_id' => 0,
            'bind_time'  => 0,
        ];
        $update = $this->dbDeviceHikCloudCommunities->updateThis($whereBind, $bindData);
        if ($update !== false) {
            return [
                'village_id'   => $village_id,
                'community_id' => $community_id,
            ];
        } else {
            throw new \think\Exception("解绑绑定失败");
        }
    }

    /**
     * 删除记录的云社区
     * @param string $community_id
     * @param array $param
     * @return string[]
     * @throws \think\Exception
     */
    public function deleteSystemCommunities(string $community_id, array $param = []) {
        $whereOnly = [];
        $whereOnly[] = ['del_time',     '=', 0];
        $whereOnly[] = ['community_id', '=', $community_id];
        if (!$this->dbDeviceHikCloudCommunities) {
            $this->dbDeviceHikCloudCommunities = new DeviceHikCloudCommunities();
        }
        $hikCloudInfo = $this->dbDeviceHikCloudCommunities->getOne($whereOnly, 'hik_cloud_id, village_id');
        if ($hikCloudInfo && !is_array($hikCloudInfo)) {
            $hikCloudInfo = $hikCloudInfo->toArray();
        }
        if (empty($hikCloudInfo) || !isset($hikCloudInfo['hik_cloud_id']) || !$hikCloudInfo['hik_cloud_id']) {
            throw new \think\Exception("删除对应不存在");
        }
        $bindData = [
            'del_time'  => $this->nowTime,
        ];
        $update = $this->dbDeviceHikCloudCommunities->updateThis($whereOnly, $bindData);
        if ($update !== false) {
            return [
                'community_id' => $community_id,
            ];
        } else {
            throw new \think\Exception("删除失败");
        }
    }

    /**
     * 获取对应小区绑定的云社区
     * @param int $village_id
     * @param array $param
     * @return array|\think\Model
     */
    public function getHouseBindCommunity(int $village_id,  array $param = []) {
        $whereOnly = [];
        $whereOnly[] = ['del_time',     '=', 0];
        $whereOnly[] = ['community_id', '<>', ''];
        $whereOnly[] = ['village_id',   '=', $village_id];
        if (!$this->dbDeviceHikCloudCommunities) {
            $this->dbDeviceHikCloudCommunities = new DeviceHikCloudCommunities();
        }
        $hikCloudInfo = $this->dbDeviceHikCloudCommunities->getOne($whereOnly, true);
        if ($hikCloudInfo && !is_array($hikCloudInfo)) {
            $hikCloudInfo = $hikCloudInfo->toArray();
        }
        if (empty($hikCloudInfo) || !isset($hikCloudInfo['hik_cloud_id']) || !$hikCloudInfo['hik_cloud_id']) {
            return [];
        }
        if (isset($hikCloudInfo['unique_one_voucher'])) {
            unset($hikCloudInfo['unique_one_voucher']);
        }
        if (isset($hikCloudInfo['del_time'])) {
            unset($hikCloudInfo['del_time']);
        }
        return $hikCloudInfo;
    }


    /**
     * 获取对应小区绑定的云社区
     * @param string $community_id
     * @param array $param
     * @return array|\think\Model
     */
    public function getCommunity(string $community_id,  array $param = []) {
        $whereOnly = [];
        $whereOnly[] = ['del_time',     '=', 0];
        $whereOnly[] = ['community_id',   '=', $community_id];
        if (!$this->dbDeviceHikCloudCommunities) {
            $this->dbDeviceHikCloudCommunities = new DeviceHikCloudCommunities();
        }
        $hikCloudInfo = $this->dbDeviceHikCloudCommunities->getOne($whereOnly, true);
        if ($hikCloudInfo && !is_array($hikCloudInfo)) {
            $hikCloudInfo = $hikCloudInfo->toArray();
        }
        if (empty($hikCloudInfo) || !isset($hikCloudInfo['hik_cloud_id']) || !$hikCloudInfo['hik_cloud_id']) {
            return [];
        }
        if (isset($hikCloudInfo['unique_one_voucher'])) {
            unset($hikCloudInfo['unique_one_voucher']);
        }
        if (isset($hikCloudInfo['del_time'])) {
            unset($hikCloudInfo['del_time']);
        }
        if (isset($hikCloudInfo['update_time']) && $hikCloudInfo['update_time']) {
            $hikCloudInfo['new_time_txt'] = date('Y-m-d H:i:s', $hikCloudInfo['update_time']);
        }elseif (isset($hikCloudInfo['add_time']) && $hikCloudInfo['add_time']) {
            $hikCloudInfo['new_time_txt'] = date('Y-m-d H:i:s', $hikCloudInfo['add_time']);
        }
        if (isset($hikCloudInfo['community_square_meter']) && (!$hikCloudInfo['community_square_meter'] || $hikCloudInfo['community_square_meter'] == 'null')) {
            $hikCloudInfo['community_square_meter'] = '-';
        }
        if (isset($hikCloudInfo['charge_person_name']) && (!$hikCloudInfo['charge_person_name'] || $hikCloudInfo['charge_person_name'] == 'null')) {
            $hikCloudInfo['charge_person_name'] = '-';
        }
        return $hikCloudInfo;
    }

    /**
     * 查询社区下的设备列表
     * @param int $village_id
     * @param string $community_id
     * @param array $param
     * @return array|mixed
     * @throws \think\Exception
     */
    public function getDeviceByCommunityId(int $village_id, string $community_id = '', array $param = []) {
        if (!$this->judgeConfig()) {
            return [
                'code'    => HikConst::ERR_MESSAGE_NOT_CONFIGURED_CODE,
                'message' => HikConst::ERR_MESSAGE_NOT_CONFIGURED,
            ];
        }
        if ($village_id && !$community_id) {
            $hikCloudInfo = $this->getHouseBindCommunity($village_id);
            if (!empty($hikCloudInfo)) {
                $community_id = $hikCloudInfo['community_id'];
            }
        }
        if (!$community_id || !$village_id) {
            throw new \think\Exception('参数异常[communityId]不能为空');
        }
        
        /*** 当前页数*/
        $pageNo    = isset($param['pageNo']) ? $param['pageNo'] : 1;
        /*** 每页条数*/
        $pageSize  = isset($param['pageSize']) ? $param['pageSize'] : 100;
        
        $params = [
            'communityId'  => $community_id,
            'pageNo'       => $pageNo,
            'pageSize'     => $pageSize
        ];
        
        $result = $this->hikCloud->Device->getDeviceByCommunityId($params);
        $data  = isset($result['data']) && $result['data'] ? $result['data'] : [];
        $isJob = isset($param['isJob']) && $param['isJob'] ? $param['isJob'] : 0;
        $no_del_opt=isset($param['no_del_opt']) && $param['no_del_opt'] ? $param['no_del_opt'] : 0;
        if (!empty($data)) {
            // 取出数据处理
            $rows = isset($data['rows']) ? $data['rows'] : [];
            if (!empty($rows)) {
                $this->saveCommunityDevice($rows, $village_id,$no_del_opt);
            }
            $hasNextPage = isset($data['hasNextPage']) ? $data['hasNextPage'] : false;
            // 是否有下一页（true：是）
            if ($hasNextPage) {
                $param = [
                    'pageNo'             => $pageNo + 1,
                    'pageSize'           => $pageSize,
                    'no_del_opt'=>1,
                ];
                return $this->getDeviceByCommunityId($village_id, $community_id, $param);
            }
            if ($isJob) {
                $queueData = [
                    'thirdProtocol' => DeviceConst::HIK_YUNMO_NEIBU_6000C,
                    'jobType'       => 'hik6000CGetDeviceChannelByCommunityId',
                    'village_id'    => $village_id,
                    'community_id'  => $community_id,
                ];
                $job_id = $this->traitCommonHikCloud($queueData);
                $data['job_id'] = $job_id;
            } else {
                $this->getDeviceChannelByCommunityId($village_id, $community_id);
            }
        } elseif (isset($result['code']) && $result['code'] != '200' && isset($result['message']) && $result['message']) {
            throw new \think\Exception($result['message']);
        }
        return $data;
    }

    /**
     * 存储查询社区下的设备列表
     * @param  array $rows
     * @param  int   $village_id
     * @return bool
     */
    protected function saveCommunityDevice(array $rows, int $village_id,$no_del_opt=0) {
        $thirdProtocol = DeviceConst::HIK_YUNMO_NEIBU_6000C;
        if (!$this->dbHouseCameraDevice) {
            $this->dbHouseCameraDevice = new HouseCameraDevice();
        }
        if($no_del_opt<1){
            $saveDelArr=array('last_time'=>time(),'camera_status'=>4);
            $saveDelWhere=array(['village_id','=',$village_id],['camera_status','<',4]);
            $this->dbHouseCameraDevice->saveOne($saveDelWhere, $saveDelArr);
        }
        foreach ($rows as $item) {
            $deviceId           = isset($item['deviceId'])          && $item['deviceId']          ? $item['deviceId']          : '';
            if (!$deviceId) {
                continue;
            }
            $device_name   = isset($item['deviceName'])     && $item['deviceName']    ? $item['deviceName']    : '';
            $device_model  = isset($item['deviceModel'])    && $item['deviceModel']   ? $item['deviceModel']   : '';
            $device_serial = isset($item['deviceSerial'])   && $item['deviceSerial']  ? $item['deviceSerial']  : '';
            $deviceStatus  = isset($item['deviceStatus'])   && $item['deviceStatus']  ? $item['deviceStatus']  : '';
            $devicePath    = isset($item['devicePath'])     && $item['devicePath']    ? $item['devicePath']    : '';
            $deviceIp      = isset($item['deviceIp'])       && $item['deviceIp']      ? $item['deviceIp']      : '';
            $devicePort    = isset($item['devicePort'])     && $item['devicePort']    ? $item['devicePort']    : '';
            $deviceVersion = isset($item['deviceVersion'])  && $item['deviceVersion'] ? $item['deviceVersion'] : '';
            $edgeDevice    = isset($item['edgeDevice'])     && $item['edgeDevice']    ? $item['edgeDevice']    : '';
            $indexCodeDac  = isset($item['indexCodeDac'])   && $item['indexCodeDac']  ? $item['indexCodeDac']  : '';
            $communityId   = isset($item['communityId'])    && $item['communityId']   ? $item['communityId']   : '';
            $stageId       = isset($item['stageId'])        && $item['stageId']       ? $item['stageId']       : '';
            $buildingId    = isset($item['buildingId'])     && $item['buildingId']    ? $item['buildingId']    : '';
            $unitId        = isset($item['unitId'])         && $item['unitId']        ? $item['unitId']        : '';
            
            $houseCameraDeviceData = [
                'camera_name'       => $device_name,
                'camera_sn'         => $device_serial,
                'village_id'        => $village_id,
                'brand_id'          => 1,
                'brand_name'        => '海康',
                'device_model'      => $device_model,
                'device_ip'         => $deviceIp,
                'cloud_device_id'   => $deviceId,
                'login_time'        => $this->nowTime,
                'cloud_group_id'    => $communityId,
                'thirdProtocol'     => $thirdProtocol,
                'cloud_device_name' => $device_name,
            ];
            // 设备状态，0：离线，1：在线	
            if ($deviceStatus == 1) {
                $camera_status = 0;
            } else {
                $camera_status = 1;
            }
            $houseCameraDeviceData['camera_status'] = $camera_status;
            $cloud_txt = [
                'devicePath'    => $devicePath,
                'deviceIp'      => $deviceIp,
                'devicePort'    => $devicePort,
                'deviceVersion' => $deviceVersion,
                'edgeDevice'    => $edgeDevice,
                'indexCodeDac'  => $indexCodeDac,
                'communityId'   => $communityId,
                'stageId'       => $stageId,
                'buildingId'    => $buildingId,
                'unitId'        => $unitId,
            ];
            $cloud_txt_json = json_encode($cloud_txt, JSON_UNESCAPED_UNICODE);
            $houseCameraDeviceData['cloud_txt'] = $cloud_txt_json;
            
            $whereOnlyCamera = [];
            $whereOnlyCamera[] = ['camera_sn',     '=', $device_serial];
            //$whereOnlyCamera[] = ['camera_status', '<>', 4];
            $whereOnlyCamera[] = ['village_id',    '=', $village_id];

            $cameraDeviceInfo = $this->dbHouseCameraDevice->getOne($whereOnlyCamera, 'camera_id');
            if ($cameraDeviceInfo && !is_array($cameraDeviceInfo)) {
                $cameraDeviceInfo = $cameraDeviceInfo->toArray();
            }
            if ($cameraDeviceInfo && isset($cameraDeviceInfo['camera_id']) && $cameraDeviceInfo['camera_id']) {
                $houseCameraDeviceData['last_time'] = $this->nowTime;
                unset($houseCameraDeviceData['camera_name']);
                $this->dbHouseCameraDevice->saveOne($whereOnlyCamera, $houseCameraDeviceData);
                $camera_id = $cameraDeviceInfo['camera_id'];
            } else {
                $houseCameraDeviceData['add_time'] = $this->nowTime;
                $camera_id = $this->dbHouseCameraDevice->addOne($houseCameraDeviceData);
            }
            if (!$this->dbCameraDeviceBind) {
                $this->dbCameraDeviceBind = new CameraDeviceBind();
            }
            $whereDeviceBind = [
                'bind_type'      => DeviceConst::BIND_CAMERA_DEVICE,
                'bind_id'        => $camera_id,
                'device_sn'      => $device_serial,
                'device_id'      => $camera_id,
                'third_protocol' => $thirdProtocol,
            ];
            $bindInfo = $this->dbCameraDeviceBind->getOne($whereDeviceBind, 'id');
            if ($bindInfo && !is_array($bindInfo)) {
                $bindInfo = $bindInfo->toArray();
            }
            $bindData = [
                'bind_type'              => DeviceConst::BIND_CAMERA_DEVICE,
                'bind_id'                => $camera_id,
                'device_sn'              => $device_serial,
                'device_id'              => $camera_id,
                'third_protocol'         => $thirdProtocol,
                'third_deviceId'         => $deviceId,
                'third_name'             => $device_name,
                'third_parent_id'        => $unitId,
                'third_second_parent_id' => $buildingId,
                'third_three_parentId'   => $communityId,
            ];
            if ($bindInfo && isset($bindInfo['id']) && $bindInfo['id']) {
                $bindData['update_time'] = $this->nowTime;
                $this->dbCameraDeviceBind->updateThis($whereDeviceBind, $bindData);
            } else {
                $bindData['add_time']    = $this->nowTime;
                $this->dbCameraDeviceBind->add($bindData);
            }
        }
        return true;
    }

    /**
     * 查询社区下设备通道列表
     * @param int $village_id
     * @param string $community_id
     * @param array $param
     * @return array|mixed
     * @throws \think\Exception
     */
    public function getDeviceChannelByCommunityId(int $village_id, string $community_id = '', array $param = []) {
        if (!$this->judgeConfig()) {
            return [
                'code'    => HikConst::ERR_MESSAGE_NOT_CONFIGURED_CODE,
                'message' => HikConst::ERR_MESSAGE_NOT_CONFIGURED,
            ];
        }
        if ($village_id && !$community_id) {
            $hikCloudInfo = $this->getHouseBindCommunity($village_id);
            if (!empty($hikCloudInfo)) {
                $community_id = $hikCloudInfo['community_id'];
            }
        }
        if (!$community_id || !$village_id) {
            throw new \think\Exception('参数异常[communityId]不能为空');
        }

        /*** 当前页数*/
        $pageNo    = isset($param['pageNo']) ? $param['pageNo'] : 1;
        /*** 每页条数*/
        $pageSize  = isset($param['pageSize']) ? $param['pageSize'] : 50;

        $isJob = isset($param['isJob']) && $param['isJob'] ? $param['isJob'] : 1;
        $no_del_opt=isset($param['no_del_opt']) && $param['no_del_opt'] ? $param['no_del_opt'] : 0;
        $params = [
            'communityId'  => $community_id,
            'pageNo'       => $pageNo,
            'pageSize'     => $pageSize
        ];

        ini_set('memory_limit', '1024M');
        set_time_limit(0);
        $result = $this->hikCloud->Device->getDeviceChannelByCommunityId($params);
        $data = isset($result['data']) && $result['data'] ? $result['data'] : [];
        if (!empty($data)) {
            // 取出数据处理
            $rows = isset($data['rows']) ? $data['rows'] : [];
            if (!empty($rows)) {
                $this->saveCommunityChannel($rows, $village_id,$no_del_opt);
            }
            $hasNextPage = isset($data['hasNextPage']) ? $data['hasNextPage'] : false;
            // 是否有下一页（true：是）
            if ($hasNextPage) {
                if ($isJob) {
                    $queueData = [
                        'thirdProtocol' => DeviceConst::HIK_YUNMO_NEIBU_6000C,
                        'jobType'       => 'hik6000CGetDeviceChannelByCommunityId',
                        'village_id'    => $village_id,
                        'community_id'  => $community_id,
                        'pageNo'        => $pageNo + 1,
                        'pageSize'      => $pageSize,
                        'no_del_opt' =>1,
                    ];
                    $job_id = $this->traitCommonHikCloud($queueData);
                    $data['job_id'] = $job_id;
                } else {
                    $param['no_del_opt']=1;
                    return $this->getDeviceChannelByCommunityId($village_id, $community_id, $param);
                }
            }
        } elseif (isset($result['code']) && $result['code'] != '200' && isset($result['message']) && $result['message']) {
            throw new \think\Exception($result['message']);
        }
        return $data;
    }

    /**
     * 存储查询社区下设备通道列表
     * @param array $rows
     * @param int $village_id
     * @return bool
     */
    protected function saveCommunityChannel(array $rows, int $village_id,$no_del_opt=0) {
        $device_type = 'house_camera_device';
        foreach ($rows as $item) {
            usleep(200000);//休眠200毫秒
            $deviceId      = isset($item['deviceId'])          && $item['deviceId']          ? $item['deviceId']          : '';
            $device_name   = isset($item['deviceName'])     && $item['deviceName']    ? $item['deviceName']    : '';
            $device_model  = isset($item['deviceModel'])    && $item['deviceModel']   ? $item['deviceModel']   : '';
            $device_serial = isset($item['deviceSerial'])   && $item['deviceSerial']  ? $item['deviceSerial']  : '';
            $channelId     = isset($item['channelId'])      && $item['channelId']     ? $item['channelId']     : '';
            $channelName   = isset($item['channelName'])    && $item['channelName']   ? $item['channelName']   : '';
            $channelNo     = isset($item['channelNo'])      && $item['channelNo']     ? $item['channelNo']     : '';
            $channelStatus = isset($item['channelStatus'])  && $item['channelStatus'] ? $item['channelStatus'] : '';
            $channelPicUrl = isset($item['channelPicUrl'])  && $item['channelPicUrl'] ? $item['channelPicUrl'] : '';
            $deviceStatus  = isset($item['deviceStatus'])   && $item['deviceStatus']  ? $item['deviceStatus']  : '';
            $indexCodeDac  = isset($item['indexCodeDac'])   && $item['indexCodeDac']  ? $item['indexCodeDac']  : '';
            if (!$channelId) {
                continue;
            }
            $whereOnlyCamera = [];
            if ($device_serial) {
                $whereOnlyCamera[] = ['camera_sn', '=', $device_serial];
            } elseif ($deviceId) {
                $whereOnlyCamera[] = ['cloud_device_id', '=', $deviceId];
            }
            $whereOnlyCamera[] = ['camera_status', '<>', 4];
            $whereOnlyCamera[] = ['village_id',    '=', $village_id];
            if (!$this->dbHouseCameraDevice) {
                $this->dbHouseCameraDevice = new HouseCameraDevice();
            }
            $cameraDeviceInfo = $this->dbHouseCameraDevice->getOne($whereOnlyCamera, 'camera_id');
            if ($cameraDeviceInfo && !is_array($cameraDeviceInfo)) {
                $cameraDeviceInfo = $cameraDeviceInfo->toArray();
            }
            
            $houseCameraChannelData = [
                'device_type'       => $device_type,
                'village_id'        => $village_id,
                'device_id'         => isset($cameraDeviceInfo['camera_id']) && $cameraDeviceInfo['camera_id'] ? $cameraDeviceInfo['camera_id'] : 0,
                'deviceSerial'      => $device_serial,
                'channelId'         => $channelId,
                'channelName'       => $channelName,
                'channelNo'         => $channelNo,
                'channelStatus'     => $channelStatus,
                'channelPicUrl'     => $channelPicUrl,
            ];
            
            $channel_txt = [
                'deviceName'    => $device_name,
                'deviceModel'   => $device_model,
                'deviceStatus'  => $deviceStatus,
                'indexCodeDac'  => $indexCodeDac,
            ];
            $channel_txt_json = json_encode($channel_txt, JSON_UNESCAPED_UNICODE);
            $houseCameraChannelData['channel_txt'] = $channel_txt_json;

            if (!$this->dbHouseDeviceChannel) {
                $this->dbHouseDeviceChannel = new HouseDeviceChannel();
            }
            
            $whereOnlyChannel = [];
            $whereOnlyChannel[] = ['device_type',  '=', $device_type];
            $whereOnlyChannel[] = ['deviceSerial', '=', $device_serial];
            $whereOnlyChannel[] = ['isDel',        '=', 0];
            $whereOnlyChannel[] = ['village_id',   '=', $village_id];
            $whereOnlyChannel[] = ['channelId',    '=' , $channelId];

            $cameraChannelInfo = $this->dbHouseDeviceChannel->getOne($whereOnlyChannel, 'channel_id');
            if ($cameraChannelInfo && !is_array($cameraChannelInfo)) {
                $cameraChannelInfo = $cameraChannelInfo->toArray();
            }
            if ($cameraChannelInfo && isset($cameraChannelInfo['channel_id']) && $cameraChannelInfo['channel_id']) {
                $houseCameraChannelData['update_time'] = $this->nowTime;
                $this->dbHouseDeviceChannel->updateThis($whereOnlyChannel, $houseCameraChannelData);
                $channel_id = $cameraChannelInfo['channel_id'];
            } else {
                $houseCameraChannelData['add_time'] = $this->nowTime;
                $channel_id = $this->dbHouseDeviceChannel->add($houseCameraChannelData);
            }
            if ($channel_id) {
                $this->getLiveAddressNew($channelId, $channel_id);
            }
        }
        return true;
    }

    /**
     * 查询通道详情（本地记录）
     * @param string $channelId
     * @param string $deviceSerial
     * @param int $channel_id
     * @return array|\think\Model|null
     */
    public function getChannelDetail(string $channelId, string $deviceSerial, int $channel_id = 0) {
        if (!$this->dbHouseDeviceChannel) {
            $this->dbHouseDeviceChannel = new HouseDeviceChannel();
        }
        $device_type = 'house_camera_device';
        $whereOnlyChannel = [];
        $whereOnlyChannel[] = ['device_type',  '=', $device_type];
        $whereOnlyChannel[] = ['isDel',        '=', 0];
        if ($channel_id) {
            $whereOnlyChannel[] = ['channel_id',   '=', $channel_id];
        } elseif ($deviceSerial) {
            $whereOnlyChannel[] = ['deviceSerial', '=', $deviceSerial];
        } else {
            $whereOnlyChannel[] = ['channelId',   '=', $channelId];
        }
        $cameraChannelInfo = $this->dbHouseDeviceChannel->getOne($whereOnlyChannel, true);
        if ($cameraChannelInfo && !is_array($cameraChannelInfo)) {
            $cameraChannelInfo = $cameraChannelInfo->toArray();
        }
        return $cameraChannelInfo;
    }

    public function getDeviceDetail($camera_id) {
        if (!$this->dbHouseCameraDevice) {
            $this->dbHouseCameraDevice = new HouseCameraDevice();
        }
        $whereOnlyCamera = [];
        $whereOnlyCamera[] = ['camera_id',     '=', $camera_id];
        $cameraDeviceInfo = $this->dbHouseCameraDevice->getOne($whereOnlyCamera, 'camera_id, camera_sn');
        if ($cameraDeviceInfo && !is_array($cameraDeviceInfo)) {
            $cameraDeviceInfo = $cameraDeviceInfo->toArray();
        }
        if (empty($cameraDeviceInfo)) {
            return [];
        }
        $cameraChannelInfo = $this->getChannelDetail('',$cameraDeviceInfo['camera_sn']);
        if ($cameraChannelInfo && isset($cameraChannelInfo['channelId']) && $cameraChannelInfo['channelId']) {
            $liveResult = $this->getLiveAddressNew($cameraChannelInfo['channelId'], $cameraChannelInfo['channel_id']);
        } else {
            $liveResult = [];
        }
        return ['liveResult' => $liveResult];
    }
    
    /**
     * 获取标准流预览地址(支持6000C子设备通道。)
     * @param string $channelId
     * @param int $channel_id
     * @param array $param
     * @return false
     */
    public function getLiveAddressNew(string $channelId, int $channel_id = 0, array $param = []) {
        $cameraChannelInfo = $this->getChannelDetail($channelId,'', $channel_id);
        $channelId = isset($cameraChannelInfo['channelId']) && $cameraChannelInfo['channelId'] ? $cameraChannelInfo['channelId'] : '';
        if (!$channelId) {
            return false;
        }
        /** @var int protocol 流播放协议，1-ezopen、2-hls、3-rtmp、4-flv，默认为1*/
        $protocol = isset($param['protocol']) && $param['protocol'] ? $param['protocol'] : 4;
        $urlExpireTime = '62208000'; // 有效时间（单位：秒，最大62208000即720天，最小300即5分钟，非必选参数，为空时返回对应设备和通道的永久地址）
        $quality       = 2;   // 视频清晰度，1-高清（主码流）、2-流畅（子码流）
        $params = [
            'channelId'   => $channelId,
            'expireTime'  => $urlExpireTime,
            'protocol'    => $protocol,
            'quality'     => $quality,
        ];
        $result = $this->hikCloud->Device->getLiveAddressNew($params);
        $data = isset($result['data']) && $result['data'] ? $result['data'] : [];
        $lookUrl    = isset($data['url']) && $data['url'] ? $data['url'] : '';
        $liveResult = [];
        if ($lookUrl && $protocol == 4) {
            $flv           = $lookUrl;
            $updateParam = [
                'flv'            => $flv,
                'liveVideoOpen'  => 1,
                'urlExpireTime'  => $urlExpireTime,
                'urlEndTime'     => $this->nowTime + intval($urlExpireTime),
            ];
            $liveResult = $updateParam;
            $device_type = 'house_camera_device';
            $whereOnlyChannel = [];
            $whereOnlyChannel[] = ['device_type',  '=', $device_type];
            $whereOnlyChannel[] = ['isDel',        '=', 0];
            if ($channel_id) {
                $whereOnlyChannel[] = ['channel_id',   '=', $channel_id];
            } else {
                $whereOnlyChannel[] = ['channelId',   '=', $channelId];
            }
            //$houseCameraChannelData['update_time'] = $this->nowTime;
            if (!$this->dbHouseDeviceChannel) {
                $this->dbHouseDeviceChannel = new HouseDeviceChannel();
            }
            $this->dbHouseDeviceChannel->updateThis($whereOnlyChannel, $updateParam);
            $updateDevice = [
                'lookUrlType' => 'flv',
                'look_url'    => $flv,
            ];
            $whereOnlyCamera = [];
            $whereOnlyCamera[] = ['camera_sn',     '=', $cameraChannelInfo['deviceSerial']];
            $whereOnlyCamera[] = ['camera_status', '<>', 4];
            $updateDevice['last_time'] = $this->nowTime;
            if (!$this->dbHouseCameraDevice) {
                $this->dbHouseCameraDevice = new HouseCameraDevice();
            }
            $this->dbHouseCameraDevice->saveOne($whereOnlyCamera, $updateDevice);
        } elseif ($lookUrl && $protocol == 2) {
            $hls           = $lookUrl;
            $updateParam = [
                'hls'            => $hls,
                'liveVideoOpen'  => 1,
                'urlExpireTime'  => $urlExpireTime,
                'urlEndTime'     => $this->nowTime + intval($urlExpireTime),
            ];
            $liveResult = $updateParam;
            $device_type = 'house_camera_device';
            $whereOnlyChannel = [];
            $whereOnlyChannel[] = ['device_type',  '=', $device_type];
            $whereOnlyChannel[] = ['isDel',        '=', 0];
            if ($channel_id) {
                $whereOnlyChannel[] = ['channel_id',   '=', $channel_id];
            } else {
                $whereOnlyChannel[] = ['channelId',   '=', $channelId];
            }
            //$houseCameraChannelData['update_time'] = $this->nowTime;
            if (!$this->dbHouseDeviceChannel) {
                $this->dbHouseDeviceChannel = new HouseDeviceChannel();
            }
            $this->dbHouseDeviceChannel->updateThis($whereOnlyChannel, $updateParam);
        } elseif ($lookUrl && $protocol == 3) {
            $rtmp           = $lookUrl;
            $updateParam = [
                'rtmp'           => $rtmp,
                'liveVideoOpen'  => 1,
                'urlExpireTime'  => $urlExpireTime,
                'urlEndTime'     => $this->nowTime + intval($urlExpireTime),
            ];
            $liveResult = $updateParam;
            $device_type = 'house_camera_device';
            $whereOnlyChannel = [];
            $whereOnlyChannel[] = ['device_type',  '=', $device_type];
            $whereOnlyChannel[] = ['isDel',        '=', 0];
            if ($channel_id) {
                $whereOnlyChannel[] = ['channel_id',   '=', $channel_id];
            } else {
                $whereOnlyChannel[] = ['channelId',   '=', $channelId];
            }
            //$houseCameraChannelData['update_time'] = $this->nowTime;
            if (!$this->dbHouseDeviceChannel) {
                $this->dbHouseDeviceChannel = new HouseDeviceChannel();
            }
            $this->dbHouseDeviceChannel->updateThis($whereOnlyChannel, $updateParam);
        }
        if(!empty($liveResult)){
            $liveResult['look_url']=$lookUrl;
        }
        return $liveResult;
    }

    /**
     * 获取海康云眸内部应用对应社区信息数量
     * @param array $param
     * @return int
     */
    public function getDeviceHikCloudCommunitiesCount(array $param = []) {
        if (!$this->dbDeviceHikCloudCommunities) {
            $this->dbDeviceHikCloudCommunities = new DeviceHikCloudCommunities();
        }
        $where = [];
        $where[] = ['del_time', '=', 0];
        $count = $this->dbDeviceHikCloudCommunities->getCount($where);
        if (!$count) {
            $count = 0;
        }
        return $count;
    }

    /**
     * 获取海康云眸内部应用对应社区信息
     * @param array $param
     * @return array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDeviceHikCloudCommunitiesList(array $param) {
        $page     = isset($param['page']) && $param['page']     ? intval($param['page'])     : 1;
        $pageSize = isset($param['page']) && $param['pageSize'] ? intval($param['pageSize']) : 1;
        if (!$this->dbDeviceHikCloudCommunities) {
            $this->dbDeviceHikCloudCommunities = new DeviceHikCloudCommunities();
        }
        $where = [];
        $where[] = ['del_time', '=', 0];
        $list = $this->dbDeviceHikCloudCommunities->getList($where, true, $page, $pageSize);
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        foreach ($list as &$item) {
            
            if (isset($item['update_time']) && $item['update_time']) {
                $item['new_time_txt'] = date('Y-m-d H:i:s', $item['update_time']);
            }elseif (isset($item['add_time']) && $item['add_time']) {
                $item['new_time_txt'] = date('Y-m-d H:i:s', $item['add_time']);
            }
            if (isset($item['community_square_meter']) && (!$item['community_square_meter'] || $item['community_square_meter'] == 'null')) {
                $item['community_square_meter'] = '-';
            }
            if (isset($item['charge_person_name']) && (!$item['charge_person_name'] || $item['charge_person_name'] == 'null')) {
                $item['charge_person_name'] = '-';
            }
            $item['bind_txt']      = '暂无';
            $item['bind_time_txt'] = '暂无';
            if (isset($item['village_id']) && $item['village_id']) {
                if (!$this->dbHouseVillage) {
                    $this->dbHouseVillage = new HouseVillage();
                }
                $villageInfo = $this->dbHouseVillage->getOne($item['village_id'], 'village_id, village_name');
                if ($villageInfo && !is_array($villageInfo)) {
                    $villageInfo = $villageInfo->toArray();
                }
                if (!empty($villageInfo)) {
                    $item['bind_txt'] = $villageInfo['village_name'] . "【ID: {$item['village_id']}】";
                    if (isset($item['bind_time']) && $item['bind_time']) {
                        $item['bind_time_txt'] = date('Y-m-d H:i:s', $item['bind_time']);
                    }
                }
            }
        }
        return ['list' => $list];
    }


    /**
     * 查询小区列表
     * @author:zhubaodi
     * @date_time: 2021/4/25 13:27
     */
    public function getVillageList()
    {
        if (!$this->dbDeviceHikCloudCommunities) {
            $this->dbDeviceHikCloudCommunities = new DeviceHikCloudCommunities();
        }
        $whereBind = [];
        $whereBind[] = ['del_time', '=', 0];
        $whereBind[] = ['village_id', '>', 0];
        $villageIdArr = $this->dbDeviceHikCloudCommunities->getColumn($whereBind, 'village_id');
        $house_village = new HouseVillage();
        $where   = [];
        $where[] = ['status', '=', 1];
        if (!empty($villageIdArr)) {
            $where[] = ['village_id', 'not in', $villageIdArr];
        }
        $list = $house_village->getList($where, 'village_id as id,village_name as name,village_id,village_name,community_id');
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        foreach ($list as &$item) {
            $item['choose_name'] = $item['village_name'] ."【ID:{$item['village_id']}】";
        }
        return ['list' => $list];
    }
    
    
    public function getDeviceByAllCommunityId() {
        if (!$this->dbDeviceHikCloudCommunities) {
            $this->dbDeviceHikCloudCommunities = new DeviceHikCloudCommunities();
        }
        $whereBind = [];
        $whereBind[] = ['del_time', '=', 0];
        $whereBind[] = ['village_id', '>', 0];
        $villageIdArr = $this->dbDeviceHikCloudCommunities->getColumn($whereBind, 'village_id');
        $jobIds = [];
        foreach ($villageIdArr as $village_id) {
            $queueData = [
                'jobType'       => 'hik6000CGetDeviceByCommunityId',
                'thirdProtocol' => DeviceConst::HIK_YUNMO_NEIBU_6000C,
                'village_id'    => $village_id,
            ];
            $job_id = $this->traitCommonHikCloud($queueData);
            $jobIds[] = $job_id;
        }
        fdump_api([$villageIdArr, $jobIds], '$getDeviceByAllCommunityId');
        return true;
    }
    
    
}