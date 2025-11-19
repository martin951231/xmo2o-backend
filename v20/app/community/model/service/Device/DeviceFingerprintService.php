<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      指纹锁指纹器等相关指纹设备 相关逻辑处理
 */

namespace app\community\model\service\Device;

use app\common\model\db\User;
use app\community\model\db\Device\FingerprintDevice;
use app\community\model\db\Device\FingerprintUser;
use app\community\model\db\HouseUserLog;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\service\FaceDeviceService;
use app\community\model\service\HardwareBrandService;
use app\community\model\service\HouseVillageService;
use app\consts\DahuaConst;
use app\consts\DeviceConst;
use app\traits\FaceDeviceDHCloudTraits;
use app\traits\house\DeviceTraits;

class DeviceFingerprintService
{
    use FaceDeviceDHCloudTraits;
    use DeviceTraits;
    
    /**
     * 获取设备品牌
     * @return array
     */
    public function getFingerprintBrandList()
    {
        $brand_list = (new HardwareBrandService())->getBrand(7);
        return $brand_list;
    }

    /**
     * 获取设备对应品牌系列
     * @param int $brand_id
     * @param int $type
     * @return array
     */
    public function getFingerprintBrandSeriesList($brand_id = 0, $type = 7)
    {
        $brand_series_list = (new HardwareBrandService())->getType($brand_id, $type);
        return $brand_series_list;
    }

    /**
     * 获取设备平台以品牌id为数组格式
     * @return array
     */
    public function getFingerprintBrandArr() {
        $brand_list = $this->getFingerprintBrandList();
        $brandArr = [];
        if ($brand_list) {
            foreach ($brand_list as $brand) {
                if (isset($brand['id']) && $brand['id']) {
                    $brandArr[$brand['id']] = $brand;
                }
            }
        }
        return $brandArr;
    }

    /**
     * 获取设备平台以品牌系列id为数组格式
     * @param int $brand_id
     * @param int $type
     * @return array
     */
    public function getFingerprintBrandSeriesArr($brand_id, $type = 7) {
        $brand_series_list = $this->getFingerprintBrandSeriesList($brand_id, $type);
        $brandSeriesArr = [];
        if ($brand_series_list) {
            foreach ($brand_series_list as $brandSeries) {
                if (isset($brandSeries['id']) && $brandSeries['id']) {
                    $brandSeriesArr[$brandSeries['id']] = $brandSeries;
                }
            }
        }
        return $brandSeriesArr;
    }

    /**
     * 获取指纹器列表
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $pageSize
     * @param string $order
     * @param string $whereRaw
     * @return array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getFingerprintDeviceList($where, $field = true, $page = 0, $pageSize = 20, $order = 'device_id DESC', $whereRaw = '') {
        $dbFingerprintDevice = new FingerprintDevice();
        $list = $dbFingerprintDevice->getList($where, $field, $page, $pageSize, $order, $whereRaw);
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        $brandArr = $this->getFingerprintBrandArr();
        foreach ($list as &$item) {
            if (isset($item['add_time']) && $item['add_time']) {
                $item['add_time_txt'] = date('Y-m-d H:i:s', $item['add_time']);
            }
            if (isset($item['brand_type']) && $item['brand_type']) {
                $item['brand_txt'] = isset($brandArr[$item['brand_type']]) && isset($brandArr[$item['brand_type']]['name']) ?  $brandArr[$item['brand_type']]['name'] : '';
            }
            if (isset($item['brand_txt']) && isset($item['brand_series']) && $item['brand_series']) {
                $brandSeriesArr = $this->getFingerprintBrandSeriesArr($item['brand_type'], 7);
                $brand_series_txt = isset($brandSeriesArr[$item['brand_series']]) && isset($brandSeriesArr[$item['brand_series']]['series_title']) ?  $brandSeriesArr[$item['brand_series']]['series_title'] : '';
                if ($brand_series_txt) {
                    $item['brand_txt'] .= ' ' . $brand_series_txt;
                }
            }
        }
        return $list;
    }

    /**
     * 获取数量
     * @param array|object $where
     * @param string $whereRaw
     * @return int
     */
    public function getFingerprintDeviceCount($where, $whereRaw = '') {
        $dbFingerprintDevice = new FingerprintDevice();
        return $dbFingerprintDevice->getFingerprintDeviceCount($where, $whereRaw);
    }

    /**
     * 添加指纹设备
     * @param $param
     * @return int[]
     * @throws \Exception
     */
    public function addFingerprintDevice($param) {
        $device_id        = isset($param['device_id'])        && $param['device_id']        ? intval($param['device_id'])      : 0;
        $village_id       = isset($param['village_id'])       && $param['village_id']       ? intval($param['village_id'])     : 0;
        $brand_type       = isset($param['brand_type'])       && $param['brand_type']       ? intval($param['brand_type'])     : 0;
        $brand_key        = isset($param['brand_key'])        && $param['brand_key']        ? trim($param['brand_key'])        : '';
        $brand_series     = isset($param['brand_series'])     && $param['brand_series']     ? intval($param['brand_series'])   : 0;
        $brand_series_key = isset($param['brand_series_key']) && $param['brand_series_key'] ? trim($param['brand_series_key']) : '';
        $device_name      = isset($param['device_name'])      && $param['device_name']      ? trim($param['device_name'])      : '';
        $device_sn        = isset($param['device_sn'])        && $param['device_sn']        ? trim($param['device_sn'])        : '';
        $remark           = isset($param['remark'])           && $param['remark']           ? trim($param['remark'])           : '';
        $device_admin     = isset($param['device_admin'])     && $param['device_admin']     ? trim($param['device_admin'])     : '';
        $device_password  = isset($param['device_password'])  && $param['device_password']  ? trim($param['device_password'])  : '';
        $third_protocol   = isset($param['third_protocol'])   && $param['third_protocol']   ? intval($param['third_protocol']) : DeviceConst::DH_YUNRUI;
        $single_id        = isset($param['single_id'])        && $param['single_id']        ? intval($param['single_id'])      : 0;
        $floor_id         = isset($param['floor_id'])         && $param['floor_id']         ? intval($param['floor_id'])       : 0;
        $layer_id         = isset($param['layer_id'])         && $param['layer_id']         ? intval($param['layer_id'])       : 0;
        $room_id          = isset($param['room_id'])          && $param['room_id']          ? intval($param['room_id'])        : 0;
        $mustPost         = isset($param['mustPost'])         && $param['mustPost']         ? intval($param['mustPost'])       : 0;
        $nowTime          = time();
        if (!$device_name) {
            throw new \Exception("缺少设备名称");
        }
        if (!$device_sn) {
            throw new \Exception("缺少设备编号");
        }
        if (!$brand_type) {
            throw new \Exception("缺少设备品牌");
        }
        if (!$brand_series) {
            throw new \Exception("缺少设备品牌系列");
        }
        if ($third_protocol == DeviceConst::DH_YUNRUI && (!$device_admin || !$device_password)) {
            throw new \Exception("缺少设备用户名或者对应设备密码");
        }
        if ($room_id) {
            $db_room      = new HouseVillageUserVacancy();
            $whereRoom = [];
            $whereRoom[] = ['pigcms_id', '=', $room_id];
            $whereRoom[] = ['village_id', '=', $village_id];
            $whereRoom[] = ['status',    '<>', 4];
            $whereRoom[] = ['is_del',    '=', 0];
            $roomInfo      = $db_room->getOne($whereRoom, 'pigcms_id,village_id,single_id,floor_id,layer_id');
            if ($roomInfo && !is_array($roomInfo)) {
                $roomInfo = $roomInfo->toArray();
            }
            if (isset($roomInfo['pigcms_id']) && $roomInfo['pigcms_id']) {
                $single_id = $roomInfo['single_id'];
                $floor_id  = $roomInfo['floor_id'];
                $layer_id  = $roomInfo['layer_id'];
            } else {
                throw new \Exception("所选房屋不存在或者已经被删除");
            }
        }
        if (1 == $mustPost && !$room_id) {
            throw new \Exception("缺少归属房间");
        }
        $dbFingerprintDevice = new FingerprintDevice();
        if ($device_id > 0) {
            $whereDevice = [];
            $whereDevice[] = ['village_id', '=', $village_id];
            $whereDevice[] = ['device_id',  '=', $device_id];
            $deviceInfo = $dbFingerprintDevice->getOne($whereDevice);
            if ($deviceInfo && !is_array($deviceInfo)) {
                $deviceInfo = $deviceInfo->toArray();
            }
            if (!$deviceInfo || empty($deviceInfo)) {
                throw new \Exception("更新的设备不存在或者已经被删除");
            }
            if (isset($deviceInfo['delete_time']) && $deviceInfo['delete_time']>0) {
                throw new \Exception("更新的设备不存在或者已经被删除");
            }
        } else {
            // todo 如果不是更新检查下是否添加过
            $whereRepeatDevice = [];
            $whereRepeatDevice[] = ['village_id',   '=', $village_id];
            $whereRepeatDevice[] = ['brand_type',   '=', $brand_type];
            $whereRepeatDevice[] = ['brand_series', '=', $brand_series];
            $whereRepeatDevice[] = ['device_sn',    '=', $device_sn];
            $whereRepeatDevice[] = ['delete_time',  '=', 0];
            $deviceInfo = $dbFingerprintDevice->getOne($whereRepeatDevice);
            if ($deviceInfo && !is_array($deviceInfo)) {
                $deviceInfo = $deviceInfo->toArray();
            }
            if ($deviceInfo || !empty($deviceInfo)) {
                throw new \Exception("对应设备编号小区已经添加过了");
            }
        }
        
        if (!$brand_key) {
            $brandArr = $this->getFingerprintBrandArr();
            $brand_key = isset($brandArr[$brand_type]) && isset($brandArr[$brand_type]['brand_key']) ? $brandArr[$brand_type]['brand_key'] : '';
        }
        if (!$brand_series_key) {
            $brandSeriesArr = $this->getFingerprintBrandSeriesArr($brand_series);
            $brand_series_key = isset($brandSeriesArr[$brand_series]) && isset($brandSeriesArr[$brand_series]['series_key']) ? $brandSeriesArr[$brand_series]['series_key'] : '';
        }
        $deviceParam = [
            'village_id'       => $village_id,
            'brand_type'       => $brand_type,
            'brand_key'        => $brand_key,
            'brand_series'     => $brand_series,
            'brand_series_key' => $brand_series_key,
            'device_name'      => $device_name,
            'device_sn'        => $device_sn,
            'remark'           => $remark,
            'device_admin'     => $device_admin,
            'device_password'  => $device_password,
            'single_id'        => $single_id,
            'floor_id'         => $floor_id,
            'layer_id'         => $layer_id,
            'room_id'          => $room_id,
            'third_protocol'   => $third_protocol,
        ];
        if ($device_id > 0 && isset($whereDevice)) {
            $updateParam = $deviceParam;
            $updateParam['update_time'] = $nowTime;
            $dbFingerprintDevice->updateThis($whereDevice, $updateParam);
        } else {
            $addParam = $deviceParam;
            $addParam['add_time'] = $nowTime;
            $device_id = $dbFingerprintDevice->add($addParam);
        }
        $arr = [
            'device_id' => $device_id
        ];
        // todo 目前仅绑定了房屋对象同步
        if ($room_id) {
            $arr['isSyn'] = true;
            $serviceFaceDevice = new FaceDeviceService();
            $aboutInfo = $serviceFaceDevice->filterRoomToData($room_id);
            if (isset($aboutInfo['bind_number']) && $aboutInfo['bind_number']) {
                $deviceParams = [
                    'thirdProtocol'   => DeviceConst::DH_YUNRUI,
                    'village_id'      => $village_id,
                    'single_id'       => $single_id,
                    'floor_id'        => $floor_id,
                    'layer_id'        => $layer_id,
                    'device_id'       => $device_id,
                    'device_sn'       => $device_sn,
                    'parent_third_id' => $aboutInfo['bind_number'],
                    'deviceType'      => DeviceConst::DEVICE_TYPE_FINGERPRINT,
                    'room_third_id'   => $aboutInfo['bind_number'],
                    'room_third_code' => $aboutInfo['third_id'],
                    'vacancy_id'      => $room_id,
                ];
                if (isset($deviceInfo) && $deviceInfo['cloud_device_id']) {
                    $deviceParams['cloud_device_id'] = $deviceInfo['cloud_device_id'];
                }
                $deviceParams['step_num'] = 1;
                $job_id = $this->traitCommonDHCloudDevices($deviceParams);
                $arr['job_id'] = $job_id;
                $syn_status        = DeviceConst::BINDS_SYN_DEVICE_QUEUE;
                $syn_status_txt    = '下发执行同步设备队列';
                $line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $deviceParams];
                $serviceFaceDevice->recordDeviceBindFilterTip($deviceParams, $syn_status, $syn_status_txt, $line_func_txt_arr, $aboutInfo['bind_number']);
                
                // todo 调试临时直接调取
                $serviceFaceDevice->addDeviceToCloud($deviceParams);
            } else {
                $arr['synCode'] = '1001';
                $arr['synMsg']  = '对应房屋未同步或者未绑定大华云睿云数据楼栋单元房屋';
            }
        } else {
            $arr['isSyn'] = false;
        }
        return $arr;
    }

    /**
     * 获取对应条件下客户详细信息 并着手是否进行同步
     * @param $param
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDhPersonFingerprintByRoom($param) {
        $room_id    = 0;
        $village_id = 0;
        $single_id  = 0;
        $floor_id   = 0;
        $layer_id   = 0;
        $isAddAuth  = isset($param['isAddAuth']) && $param['isAddAuth'] ? intval($param['isAddAuth']) : 0;
        if (isset($param['room_id']) && $param['room_id']) {
            $room_id = $param['room_id'];
        }
        if (!$room_id && isset($param['vacancy_id']) && $param['vacancy_id']) {
            $room_id = $param['vacancy_id'];
        }
        if (isset($param['village_id']) && $param['village_id']) {
            $village_id = $param['village_id'];
        }
        if (isset($param['single_id']) && $param['single_id']) {
            $single_id = $param['single_id'];
        }
        if (isset($param['floor_id']) && $param['floor_id']) {
            $floor_id = $param['floor_id'];
        }
        if (isset($param['layer_id']) && $param['layer_id']) {
            $layer_id = $param['layer_id'];
        }
        $dbHouseVillageUserBind = new HouseVillageUserBind();
        $where = [];
        $where[] = ['status', '=', 1];
        if ($village_id) {
            $where[] = ['village_id', '=', $village_id];
        }
        if ($single_id) {
            $where[] = ['single_id', '=', $single_id];
        }
        if ($floor_id) {
            $where[] = ['floor_id', '=', $floor_id];
        }
        if ($layer_id) {
            $where[] = ['layer_id', '=', $layer_id];
        }
        if ($room_id) {
            $where[] = ['vacancy_id', '=', $room_id];
        }
        $userBindList = $dbHouseVillageUserBind->getList($where, 'pigcms_id,uid,village_id,single_id,floor_id,layer_id,vacancy_id');
        if ($userBindList && !is_array($userBindList)) {
            $userBindList = $userBindList->toArray();
        }
        $faceDeviceService = new FaceDeviceService();
        // todo 查看下条数 如果超过10条进行队列执行
        if (count($userBindList) > 10) {
            $queueData = $param;
            $queueData['jobType']   = 'getDhPersonByPrfoleId';
            foreach ($userBindList as $user) {
                $queueData['uid']       = isset($user['uid'])       && $user['uid']       ? intval($user['uid'])       : 0;
                $queueData['pigcms_id'] = isset($user['pigcms_id']) && $user['pigcms_id'] ? intval($user['pigcms_id']) : 0;
                $this->traitCommonDHToJob($queueData);
            }
        } else {
            foreach ($userBindList as $user) {
                $param['uid'] = isset($user['uid']) && $user['uid'] ? intval($user['uid']) : 0;
                $faceDeviceService->getDhPersonByPrfoleId($user['pigcms_id'],$param['uid'], $isAddAuth, $param);
            }
        }
    }

    /**
     * 获取指纹设备信息
     * @param array $where
     * @param bool|string $field
     * @param array|string $order
     * @return array|\think\Model|null
     */
    public function getFingerprintDeviceInfo($where, $field = true, $order = [])
    {
        return (new FingerprintDevice())->getOne($where, $field, $order);
    }

    /**
     * 通过设备id查询详情 预留数组传参使用
     * @param $device_id
     * @param array $param
     * @return array|\think\Model|null
     */
    public function getFingerprintDeviceDetail($device_id, $param = []) {
        $village_id = isset($param['village_id']) && $param['village_id'] ? $param['village_id'] : 0;
        $whereFingerprint = [];
        $whereFingerprint[] = ['device_id', '=', $device_id];
        if ($village_id) {
            $whereFingerprint[] = ['village_id', '=', $village_id];
        }
        $detail = $this->getFingerprintDeviceInfo($whereFingerprint);
        if ($detail && !is_array($detail)) {
            $detail = $detail->toArray();
        }
        if ($detail && isset($detail['brand_type'])) {
            $detail['brand_type'] = strval($detail['brand_type']);
        }
        if ($detail && isset($detail['brand_series'])) {
            $detail['brand_series'] = strval($detail['brand_series']);
        }
        if ($detail && isset($detail['third_protocol'])) {
            $detail['third_protocol'] = intval($detail['third_protocol']);
        }
        return $detail;
    }

    /**
     * 删除指纹设备
     * @param $device_id
     * @param array $param
     * @return array|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deleteFingerprintDevice($device_id, $param = []) {
        $village_id = isset($param['village_id']) && $param['village_id'] ? $param['village_id'] : 0;
        $whereFingerprint = [];
        $whereFingerprint[] = ['device_id', '=', $device_id];
        if ($village_id) {
            $whereFingerprint[] = ['village_id', '=', $village_id];
        }
        $detail = $this->getFingerprintDeviceDetail($device_id, $param);
        if (empty($detail) || !isset($detail['device_id']) || intval($detail['delete_time'])>0) {
            throw new \Exception("设备不存在或者已经被删除");
        }
        $updateDelete = [
            'delete_time'   => time(),
            'device_status' => 4,
            'device_sn'     => $detail['device_sn'] . '_del',
        ];
        $deleteParam = [
            'village_id'      => $detail['village_id'],
            'device_id'       => $detail['device_id'],
            'operation'       => 'deleteDevice',
            'device_sn'       => $detail['device_sn'],
            'cloud_device_id' => $detail['cloud_device_id'],
            'thirdProtocol'   => $detail['third_protocol'],
            'deviceType'      => DeviceConst::DEVICE_TYPE_FINGERPRINT,
        ];
        $serviceFaceDevice = new FaceDeviceService();
        $deleteResult = $serviceFaceDevice->deleteDevice($deleteParam);
        if (isset($deleteResult['code']) && !$deleteResult['code']) {
            $this->saveFingerprintDeviceInfo($whereFingerprint, $updateDelete);
        } elseif (isset($deleteResult['code']) && 1044 == $deleteResult['code']) {
            $this->saveFingerprintDeviceInfo($whereFingerprint, $updateDelete);
        } else {
            throw new \Exception($deleteResult['msg']);
        }
        return $detail;
    }

    /**
     * 获取指纹锁开门记录
     * @param $village_id
     * @param array $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getHouseUserLog($village_id, $param = []) {                            
        $device_id       = isset($param['device_id'])        && $param['device_id']       ? $param['device_id']       : 0;
        $page            = isset($param['page'])             && $param['page']            ? $param['page']            : 1;
        $pageSize        = isset($param['pageSize'])         && $param['pageSize']        ? $param['pageSize']        : 20;
        $getCount        = isset($param['getCount'])         && $param['getCount']        ? $param['getCount']        : 0;
        $device_name     = isset($param['device_name'])      && $param['device_name']     ? $param['device_name']     : '';
        $device_sn       = isset($param['device_sn'])        && $param['device_sn']       ? $param['device_sn']       : '';
        $open_start_time = isset($param['open_start_time'])  && $param['open_start_time'] ? $param['open_start_time'] : '';
        $open_end_time   = isset($param['open_end_time'])    && $param['open_end_time']   ? $param['open_end_time']   : '';
        $order     = 'log_id DESC';
        $dbHouseUserLog = new HouseUserLog();
        $dbFingerprintDevice = new FingerprintDevice();

        $whereFingerprintDevice   = [];
        if ($device_name) {
            $whereFingerprintDevice[] = ['device_name','LIKE','%'.$device_name.'%'];
        }
        if ($device_sn) {
            $whereFingerprintDevice[] = ['device_sn','LIKE','%'.$device_sn.'%'];
        }
        if (!empty($whereFingerprintDevice)) {
            $whereFingerprintDevice[] = ['delete_time', '=', 0];
            $deviceIdArr = $dbFingerprintDevice->getColumn($whereFingerprintDevice,'device_id');
        }
        
        $whereUserLog = [];
        $whereUserLog[] = ['log_business_id', '=', $village_id];
        if ($device_id) {
            $whereUserLog[] = ['device_id', '=', $device_id];
        }
        $whereUserLog[] = ['log_from',        '=', DeviceConst::DEVICE_FINGERPRINT_OPEN];
        if (isset($deviceIdArr)) {
            $whereUserLog[] = ['device_id', 'in', $deviceIdArr];
        }
        if ($open_start_time) {
            $whereUserLog[] = ['log_time', '>=', intval($open_start_time)];
        }
        if ($open_end_time) {
            $whereUserLog[] = ['log_time', '<=', intval($open_end_time)];
        }
        $field = 'log_id,uid,log_bind_id,log_name,log_from,log_business_id,device_id,log_status,log_time,device_sn,third_protocol,person_name,person_code,card_number,direction_type';
        $list = $dbHouseUserLog->getTableList($whereUserLog, $field, $page, $pageSize, $order);
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        if ($getCount) {
            $count = $dbHouseUserLog->getCount($whereUserLog);
        } else {
            $count = '';
        }
        $deviceIdArr = [];
        $deviceSnArr = [];
        $dbHouseVillageUserBind = new HouseVillageUserBind();
        $dbUser = new User();
        foreach ($list as &$item) {
            if (isset($item['log_time']) && $item['log_time']) {
                $item['log_time_txt'] = date('Y-m-d H:i:s', $item['log_time']);
            }
            if (isset($item['direction_type']) && $item['direction_type']) {
                $item['direction_type_txt'] = $item['direction_type'] == 1 ? '进' : '出';
            }
            if (isset($item['log_status'])) {
                $item['log_status_txt'] = $item['log_status'] == 1 ? '失败' : '成功';
            }
            if (isset($item['device_id']) && $item['device_id']) {
                $deviceIdArr[] = $item['device_id'];
            } elseif (isset($item['device_sn']) && $item['device_sn']) {
                $deviceSnArr[] = $item['device_sn'];
            }
            $name  = '-';
            $phone = '-';
            if (isset($item['uid']) && $item['uid']) {
                $whereUser = [];
                $whereUser[] = ['uid', '=', $item['uid']];
                $user = $dbUser->getUser( 'uid, phone, nickname, real_name, truename',$whereUser);
                if ($user && !is_array($user)) {
                    $user = $user->toArray();
                }
                if (isset($user['phone']) && $user['phone']) {
                    $phone = $user['phone'];
                }
                if (isset($user['real_name']) && $user['real_name']) {
                    $name = $user['real_name'];
                } elseif (isset($user['nickname']) && $user['nickname']) {
                    $name = $user['nickname'];
                } elseif (isset($user['truename']) && $user['truename']) {
                    $name = $user['truename'];
                }
            }
            if (isset($item['log_bind_id']) && $item['log_bind_id']) {
                $whereUserBind = [];
                $whereUserBind[] = ['pigcms_id', '=', $item['log_bind_id']];
                $userBind = $dbHouseVillageUserBind->getOne($whereUserBind, 'pigcms_id, name, phone');
                if ($userBind && !is_array($userBind)) {
                    $userBind = $userBind->toArray();
                }
                if (isset($userBind['phone']) && $userBind['phone']) {
                    $phone = $userBind['phone'];
                }
                if (isset($userBind['name']) && $userBind['name']) {
                    $name = $userBind['name'];
                }
            }
            if (!$name && isset($item['person_name']) && $item['person_name']) {
                $name = $item['person_name'];
            }
            $item['name']  = $name;
            $item['phone'] = $phone;
        }
        if (!empty($deviceIdArr)) {
            $whereFingerprintDevice   = [];
            $whereFingerprintDevice[] = ['device_id',   'in', $deviceIdArr];
            $whereFingerprintDevice[] = ['delete_time', '=', 0];
            $deviceIdKey = $dbFingerprintDevice->getColumn($whereFingerprintDevice,'device_id,device_sn,device_name,village_id,single_id,floor_id,layer_id,room_id', 'device_id');
        } else {
            $deviceIdKey = [];
        }
        if (!empty($deviceSnArr)) {
            $whereFingerprintDevice1   = [];
            $whereFingerprintDevice1[] = ['device_sn',   'in', $deviceSnArr];
            $whereFingerprintDevice1[]  = ['delete_time', '=', 0];
            $deviceSnKey = $dbFingerprintDevice->getColumn($whereFingerprintDevice1,'device_id,device_sn,device_name,village_id,single_id,floor_id,layer_id,room_id', 'device_sn');
        } else {
            $deviceSnKey = [];
        }
        $houseVillageService = new HouseVillageService();
        foreach ($list as &$item1) {
            if (isset($item['device_id']) && $item['device_id'] && isset($deviceIdKey[$item['device_id']])) {
                $deviceInfo = $deviceIdKey[$item['device_id']];
                $item1['log_name']    = $deviceInfo['device_name'];
                $item1['device_sn']   = $deviceInfo['device_sn'];
                $item1['address_txt'] = $houseVillageService->getSingleFloorRoom($deviceInfo['single_id'], $deviceInfo['floor_id'], $deviceInfo['layer_id'], $deviceInfo['room_id'], $deviceInfo['village_id']);
            } elseif (isset($item['device_sn']) && $item['device_sn'] && isset($deviceSnKey[$item['device_sn']])) {
                $deviceInfo = $deviceSnKey[$item['device_sn']];
                $item1['log_name']    = $deviceInfo['device_name'];
                $item1['address_txt'] = $houseVillageService->getSingleFloorRoom($deviceInfo['single_id'], $deviceInfo['floor_id'], $deviceInfo['layer_id'], $deviceInfo['room_id'], $deviceInfo['village_id']);
            } else {
                $item1['address_txt'] = '';
            }
        }
        return [
            'list'     => $list,
            'count'    => $count,
            'pageSize' => $pageSize,
        ];
    }
    
    /**
     * 更新指纹设备信息
     * @param array $where
     * @param array $data
     * @return array|\think\Model|null
     */
    public function saveFingerprintDeviceInfo($where, $data)
    {
        return (new FingerprintDevice())->updateThis($where, $data);
    }

    /**
     * 获取用户指纹详情
     * @param $pigcms_id
     * @param array $param
     * @return array
     */
    public function getPersonFingerprintDetail($pigcms_id, $param = []) {
        $village_id = isset($param['village_id']) && $param['village_id'] ? $param['village_id'] : 0;
        $dbHouseVillageUserBind = new HouseVillageUserBind();
        $whereUserBind = [];
        $whereUserBind[] = ['pigcms_id', '=', $pigcms_id];
        if ($village_id) {
            $whereUserBind[] = ['village_id', '=', $village_id];
        }
        $userBind = $dbHouseVillageUserBind->getOne($whereUserBind);
        if ($userBind && !is_array($userBind)) {
            $userBind = $userBind->toArray();
        }
        $title   = '指纹信息';
        if (isset($userBind['name']) && $userBind['name']) {
            $title   = '【'.$userBind['name'] . '】指纹信息';
        }
        if (empty($userBind)) {
            return [
                'title' => $title,
            ];
        }
        $uid = isset($userBind['uid']) && $userBind['uid'] ? $userBind['uid'] : 0;
        $fingerprint_user = new FingerprintUser();
        $whereFingerprint = [];
        $whereFingerprint[] = ['third_protocol', '=', DeviceConst::DH_YUNRUI];
        $whereFingerprint[] = ['from',           '=', 'village'];
        $whereFingerprint[] = ['from_id',        '=', $village_id];
        $whereFingerprint[] = ['bind_type',      '=', 0];
        if ($uid) {
            $whereFingerprint[] = ['uid',        '=', $uid];
        } else  {
            $whereFingerprint[] = ['bind_id',    '=', $pigcms_id];
        }
        $personFingerprints = $fingerprint_user->getList($whereFingerprint);
        if ($personFingerprints && !is_array($personFingerprints)) {
            $personFingerprints = $personFingerprints->toArray();
        }
        fdump_api($personFingerprints,'$personFingerprints');
        $single_id = isset($userBind['single_id']) && $userBind['single_id'] ? $userBind['single_id'] : 0;
        $floor_id  = isset($userBind['floor_id'])  && $userBind['floor_id']  ? $userBind['floor_id']  : 0;
        $layer_id  = isset($userBind['layer_id'])  && $userBind['layer_id']  ? $userBind['layer_id']  : 0;
        $room_id   = isset($userBind['room_id'])   && $userBind['room_id']   ? $userBind['room_id']   : 0;
        // 查询下对应房间是否存在指纹锁
        if ($room_id) {
            $dbFingerprintDevice = new FingerprintDevice();
            $whereDevice = [];
            $whereDevice[] = ['village_id', '=', $village_id];
            $whereDevice[] = ['room_id',  '=', $room_id];
            $whereDevice[] = ['delete_time',  '=', 0];
            $deviceInfo = $dbFingerprintDevice->getOne($whereDevice);
            if ($deviceInfo && !is_array($deviceInfo)) {
                $deviceInfo = $deviceInfo->toArray();
            }
            $device_id = isset($deviceInfo['device_id']) && $deviceInfo['device_id'] ? $deviceInfo['device_id'] : 0;
            $device_sn = isset($deviceInfo['device_sn']) && $deviceInfo['device_sn'] ? trim($deviceInfo['device_sn']) : 0;
        } else {
            $device_id = 0;
            $device_sn = 0;
        }
        $deviceParams = [
            'thirdProtocol'   => DeviceConst::DH_YUNRUI,
            'village_id'      => $village_id,
            'single_id'       => $single_id,
            'floor_id'        => $floor_id,
            'layer_id'        => $layer_id,
            'device_id'       => $device_id,
            'device_sn'       => $device_sn,
            'deviceType'      => DeviceConst::DEVICE_TYPE_FINGERPRINT,
            'vacancy_id'      => $room_id,
        ];
        if (!empty($personFingerprints)) {
            if ($device_id || $device_sn) {
                (new FaceDeviceService())->addDhAuthJob($deviceParams);
            }
            return [
                'personFingerprints' => $personFingerprints,
                'title'              => $title,
            ];
        }
        $isAddAuth = 0;
        if ($device_id || $device_sn) {
            $isAddAuth = 1;
        }
        $result = (new FaceDeviceService())->getDhPersonByPrfoleId($pigcms_id, $uid, $deviceParams, $isAddAuth);
        $result['title'] = $title;
        return $result;
    }

    /**
     * 判断下是否显示指纹 需要有添加指纹设备最少一台
     * @param $village_id
     * @return bool
     */
    public function judgeConfig($village_id) {
        return $this->judgeFingerprintConfig($village_id);
    }
}