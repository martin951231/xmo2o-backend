<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      停车获取信息
 */

namespace app\community\model\service\Car;

use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\Car\HouseVillageCarFlowCoupon;
use app\community\model\db\Car\HouseVillageCarFlowIdentify;
use app\community\model\db\Car\HouseVillageCarFlowRecord;
use app\community\model\db\Car\HouseVillageCarFlowTemp;
use app\community\model\db\HouseNewChargeRule;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageBindCar;
use app\community\model\db\HouseVillageParkBlack;
use app\community\model\db\HouseVillageParkCharge;
use app\community\model\db\HouseVillageParkChargeCartype;
use app\community\model\db\HouseVillageParkConfig;
use app\community\model\db\HouseVillageParkCoupon;
use app\community\model\db\HouseVillageParkFree;
use app\community\model\db\HouseVillageParkGarageConfig;
use app\community\model\db\HouseVillageParkingCar;
use app\community\model\db\HouseVillageParkingGarage;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\db\HouseVillageParkingTemp;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageVisitor;
use app\community\model\db\InPark;
use app\community\model\db\ParkPassage;
use app\community\model\db\ParkPlateresultLog;
use app\community\model\db\ParkScrcuRecord;
use app\community\model\db\PlatOrder;
use app\community\model\db\User;
use app\community\model\service\HouseVillageParkGarageConfigService;
use app\community\model\service\Park\A11Service;
use app\consts\Car\CarFlowConst;
use app\pay\model\db\PayOrderInfo;
use app\pay\model\service\PayService;
use app\traits\Car\CarFlowCouponTraits;
use app\traits\Car\CarFlowInTraits;
use app\traits\Car\CarFlowOutTraits;
use app\traits\Car\CarFlowTempTraits;
use think\Exception;
use think\facade\Cache;

class CarHandleGetInfoService
{
    use CarFlowInTraits, CarFlowOutTraits, CarFlowCouponTraits, CarFlowTempTraits;

    public $webhookurl = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send';
    
    public $pay_type = [0 => 'cash', 1 => 'wallet', 2 => 'sweepcode', 3 => 'escape', 4 => 'monthuser', 5 => 'free', 6 => 'scancode'];
    
    // 默认免停时长.
    public $default_free_park_time = 900;

    public $sign_advance_pay_time = 600;
    
    /**
     * 停车识别获取停车设备通道.
     */
    public function getParkPassageInfo($serialno, $car_number = '')
    {
        fdump_api(['msg' => '方法入参', 'parameter' => func_get_args(), 'line' => __LINE__], 'parkBetter/getParkPassageInfoLog');
        $cacheKey = $serialno ? 'passageInfo' . $serialno : '';
        if (empty($serialno)) {
            $car_number && $this->pushMessageQy('停车设备不存在', '无', $car_number);
            fdump_api(['msg' => '传参错误', 'line' => __LINE__], 'parkBetter/getParkPassageInfoLog', 1);
            return false;
        }
        if (!empty($this->CacheTagGet($cacheKey))) {
            $passage_info = $this->CacheTagGet($cacheKey);
            fdump_api(['msg' => '缓存获取通道信息', 'passage_info' => $passage_info, 'line' => __LINE__], 'parkBetter/getParkPassageInfoLog', 1);
        } else {
            $db_park_passage = new ParkPassage();
            $wherePassage = ['device_number' => $serialno, 'status' => 1];
            $fieldPassage = 'id,village_id,park_sys_type,passage_name,channel_number,device_number,passage_direction,status,passage_area,area_type,device_type,last_heart_time,passage_type,garage_id';
            $passage_info = $db_park_passage->getFind($wherePassage, $fieldPassage);
            if ($passage_info && !is_array($passage_info)) {
                $passage_info = $passage_info->toArray();
            }
            $village_id = isset($passage_info['village_id']) && $passage_info['village_id'] ? intval($passage_info['village_id']) : 0;
            if (!empty($village_info)) {
                $this->setCacheTag('passageInfo:' . $village_id);
                $this->CacheTagSet($cacheKey, $passage_info, CarFlowConst::CAR_FLOW_REDIS_EXPIRE);
            }
            fdump_api(['msg' => '通道信息', 'passage_info' => $passage_info, 'line' => __LINE__], 'parkBetter/getParkPassageInfoLog', 1);
        }
        return $passage_info;
    }

    /**
     * 停车识别获取停车设备通道.
     */
    public function getParkPassageInfoById($id, $village_id)
    {
        fdump_api(['msg' => '方法入参', 'parameter' => func_get_args(), 'line' => __LINE__], 'parkBetter/getParkPassageInfoByIdLog');
        $cacheKey = ($id && $village_id) ? 'passageInfo_' . $village_id . '_' . $id : '';
        if (!empty($this->CacheTagGet($cacheKey))) {
            $passage_info = $this->CacheTagGet($cacheKey);
            fdump_api(['msg' => '缓存获取通道信息', 'passage_info' => $passage_info, 'line' => __LINE__], 'parkBetter/getParkPassageInfoByIdLog', 1);
        } else {
            $db_park_passage = new ParkPassage();
            $wherePassage = ['id' => $id];
            $fieldPassage = 'id,village_id,park_sys_type,passage_name,channel_number,device_number,passage_direction,status,passage_area,area_type,device_type,last_heart_time,passage_type,garage_id';
            $passage_info = $db_park_passage->getFind($wherePassage, $fieldPassage);
            if ($passage_info && !is_array($passage_info)) {
                $passage_info = $passage_info->toArray();
            }
            $village_id = isset($passage_info['village_id']) && $passage_info['village_id'] ? intval($passage_info['village_id']) : $village_id;
            if (!empty($village_info)) {
                $this->setCacheTag('passageInfo:' . $village_id);
                $expireTime = CarFlowConst::CAR_FLOW_REDIS_EXPIRE;
                $this->CacheTagSet($cacheKey, $passage_info, $expireTime);
            }
            fdump_api(['msg' => '通道信息', 'passage_info' => $passage_info, 'line' => __LINE__], 'parkBetter/getParkPassageInfoByIdLog', 1);
        }
        return $passage_info;
    }

    /**
     * 获取小区信息.
     */
    public function getVillageInfo($village_id)
    {
        fdump_api(['msg' => '方法入参', 'parameter' => func_get_args(), 'line' => __LINE__], 'parkBetter/getVillageInfoLog');
        $cacheKey = $village_id ? 'villageInfo' . $village_id : '';
        if (!$cacheKey) {
            return [];
        }
        if (!empty($this->CacheTagGet($cacheKey))) {
            $village_info = $this->CacheTagGet($cacheKey);
            fdump_api(['msg' => '缓存获取小区信息', 'village_info' => $village_info, 'line' => __LINE__], 'parkBetter/getVillageInfoLog', 1);
        } else {
            $db_house_village = new HouseVillage();
            $village_info = $db_house_village->getOne($village_id, 'village_id, village_name, property_id');
            if ($village_info && !is_array($village_info)) {
                $village_info = $village_info->toArray();
            }
            if (!empty($village_info)) {
                $this->setCacheTag('villageInfo:' . $village_id);
                $expireTime = CarFlowConst::CAR_FLOW_REDIS_EXPIRE;
                $this->CacheTagSet($cacheKey, $village_info, $expireTime);
            }
            fdump_api(['msg' => '小区信息', 'village_info' => $village_info, 'line' => __LINE__], 'parkBetter/getVillageInfoLog', 1);
        }
        return $village_info;
    }
    
    /**
     * 判断获取车辆是否是黑名单.
     */
    public function judgeBlacklist($car_number, $village_id, $park_sys_type)
    {
        fdump_api(['msg' => '方法入参', 'parameter' => func_get_args(), 'line' => __LINE__], 'parkBetter/judgeBlacklistLog');
        $cacheKey = ($car_number && $village_id && $park_sys_type) ? 'blackList_' . $car_number . '_' . $village_id . '_' . $park_sys_type : '';
        if (!$cacheKey) {
            return [];
        }
        if (!empty($this->CacheTagGet($cacheKey))) {
            $blackInfo = $this->CacheTagGet($cacheKey);
            fdump_api(['msg' => '缓存获取车辆是否是黑名单', 'blackInfo' => $blackInfo, 'line' => __LINE__], 'parkBetter/judgeBlacklistLog', 1);
        } else {
            $db_house_village_park_black = new HouseVillageParkBlack();
            $whereBlackList = [];
            // 黑名单不区分使用的 智能停车设备
//            $whereBlackList[] = ['park_sys_type', '=', $park_sys_type];
            $whereBlackList[] = ['village_id', '=', $village_id];
            $whereBlackList[] = ['car_number', '=', $car_number];
            $blackInfo = $db_house_village_park_black->getFind($whereBlackList, 'id, village_id, car_number, park_sys_type');
            if ($blackInfo && !is_array($blackInfo)) {
                $blackInfo = $blackInfo->toArray();
            }
            if (!empty($blackInfo)) {
                $this->setCacheTag('blackInfo:' . $village_id);
                $this->CacheTagSet($cacheKey, $blackInfo, CarFlowConst::CAR_FLOW_REDIS_EXPIRE);
            }
            fdump_api(['msg' => '车辆是否是黑名单', 'blackInfo' => $blackInfo, 'line' => __LINE__], 'parkBetter/judgeBlacklistLog', 1);
        }
        return $blackInfo;
    }
    
    /**
     * 检查是否是免费车.
     */
    public function judgeFreeList($car_number, $village_id, $park_sys_type)
    {
        fdump_api(['msg' => '方法入参', 'parameter' => func_get_args(), 'line' => __LINE__], 'parkBetter/judgeFreeListLog');
        $cacheKey = ($village_id && $park_sys_type) ? 'freeList_' . $village_id . '_' . $park_sys_type : '';
        if (!$cacheKey) {
            return [];
        }
        if (!empty($this->CacheTagGet($cacheKey))) {
            $free_list = $this->CacheTagGet($cacheKey);
            fdump_api(['msg' => '缓存获取小区车辆白名单', 'freeList' => $free_list, 'line' => __LINE__], 'parkBetter/judgeFreeListLog', 1);
        } else {
            $db_house_village_park_free = new HouseVillageParkFree();
            $free_list = $db_house_village_park_free->getList(['park_sys_type' => $park_sys_type, 'village_id' => $village_id]);
            if ($free_list && !is_array($free_list)) {
                $free_list = $free_list->toArray();
            }
            if (!empty($free_list)) {
                $this->setCacheTag('freeList:' . $village_id);
                $this->CacheTagSet($cacheKey, $free_list, CarFlowConst::CAR_FLOW_REDIS_EXPIRE);
            }
            fdump_api(['msg' => '小区车辆白名单', 'free_list' => $free_list, 'line' => __LINE__], 'parkBetter/judgeFreeListLog', 1);
        }
        if (empty($freeList)) {
            return [];
        }
        $isFreePark = false;
        $feeInfo = [];
        foreach ($free_list as $fee) {
            $first_number = substr($car_number, 0, strlen($fee['first_name']));
            $last_name = substr($car_number, '-' . strlen($fee['last_name']));
            if (!empty($fee['first_name']) && $first_number == $fee['first_name']) {
                $isFreePark = true;
            } elseif (!empty($fee['last_name']) && $last_name == $fee['last_name']) {
                $isFreePark = true;
            } elseif ($car_number == $fee['free_park']) {
                $isFreePark = true;
            }
            if ($isFreePark) {
                $feeInfo = $fee;
                break;
            }
        }
        return $feeInfo;
    }

    /**
     * 获取车辆信息.
     */
    public function getCarInfo($car_number, $village_id)
    {
        fdump_api(['msg' => '方法入参', 'parameter' => func_get_args(), 'line' => __LINE__], 'parkBetter/getCarInfoLog');
        $cacheKey = ($village_id && $car_number) ? 'carInfo_' . $car_number . '_' . $village_id : '';
        if (!$cacheKey) {
            return [];
        }
        if (!empty($this->CacheTagGet($cacheKey))) {
            $car_info = $this->CacheTagGet($cacheKey);
            fdump_api(['msg' => '缓存获取车辆信息', 'car_info' => $car_info, 'line' => __LINE__], 'parkBetter/getCarInfoLog', 1);
        } else {
            $province = mb_substr($car_number, 0, 1);
            $car_no = mb_substr($car_number, 1);
            $db_house_village_parking_car = new HouseVillageParkingCar();
            $whereCar = ['province' => $province, 'car_number' => $car_no, 'village_id' => $village_id];
            $fieldCar = 'car_id, village_id,car_position_id, province, car_number, car_user_name, car_user_phone, start_time, end_time, parking_car_type, stored_balance, garage_id, bind_position_ids, binding_type, uid, car_addtime, examine_time, examine_status';
            $car_info = $db_house_village_parking_car->getFind($whereCar, $fieldCar);
            if ($car_info && !is_array($car_info)) {
                $car_info = $car_info->toArray();
            }
            if (!empty($car_info)) {
                $this->setCacheTag('carInfo:' . $village_id);
                $this->CacheTagSet($cacheKey, $car_info, CarFlowConst::CAR_FLOW_REDIS_EXPIRE);
            }
            fdump_api(['msg' => '车辆信息', 'car_info' => $car_info, 'line' => __LINE__], 'parkBetter/getCarInfoLog', 1);
        }
        return $car_info;
    }
    
    /**
     * 获取车库信息.
     */
    public function getParkingGarage($garage_id, $village_id) {
        fdump_api(['msg' => '方法入参', 'parameter' => func_get_args(), 'line' => __LINE__], 'parkBetter/getParkingGarageLog');
        $cacheKey = ($village_id && $garage_id) ? 'carParkingGarage_' . $village_id . '_' . $garage_id : '';
        if (!$cacheKey) {
            return [];
        }
        if (!empty($this->CacheTagGet($cacheKey))) {
            $garage_info = $this->CacheTagGet($cacheKey);
            fdump_api(['msg' => '缓存获取车库信息', 'garage_info' => $garage_info, 'line' => __LINE__], 'parkBetter/getParkingGarageLog', 1);
        } else {
            $db_house_village_parking_garage = new HouseVillageParkingGarage();
            $field = "garage_id, garage_num, fid, is_month_charge, is_month_access, village_id";
            $garage_info = $db_house_village_parking_garage->getOne(['garage_id' => $garage_id], $field);
            if ($garage_info && !is_array($garage_info)) {
                $garage_info = $garage_info->toArray();
            }
            if (!empty($garage_info)) {
                $this->setCacheTag('carParkingGarageInfo:' . $village_id);
                $this->CacheTagSet($cacheKey, $garage_info, CarFlowConst::CAR_FLOW_REDIS_EXPIRE);
            }
            fdump_api(['msg' => '车库信息', 'garage_info' => $garage_info, 'line' => __LINE__], 'parkBetter/getParkingGarageLog', 1);
        }
        return $garage_info;
    }

    /**
     * 获取子车库信息.
     */
    public function getSubParkingGarage($garage_id, $village_id) {
        fdump_api(['msg' => '方法入参', 'parameter' => func_get_args(), 'line' => __LINE__], 'parkBetter/getSubParkingGarageLog');
        $cacheKey = ($village_id && $garage_id) ? 'carSubParkingGarage_' . $village_id . '_' . $garage_id : '';
        if (!$cacheKey) {
            return [];
        }
        if (!empty($this->CacheTagGet($cacheKey))) {
            $garage_info = $this->CacheTagGet($cacheKey);
            fdump_api(['msg' => '缓存获取子车库信息', 'garage_info' => $garage_info, 'line' => __LINE__], 'parkBetter/getSubParkingGarageLog', 1);
        } else {
            $db_house_village_parking_garage = new HouseVillageParkingGarage();
            $field = "garage_id, garage_num, fid, is_month_charge, is_month_access, village_id";
            $garage_info = $db_house_village_parking_garage->getOne(['fid' => $garage_id], $field);
            if ($garage_info && !is_array($garage_info)) {
                $garage_info = $garage_info->toArray();
            }
            if (!empty($garage_info)) {
                $this->setCacheTag('carParkingGarageInfo:' . $village_id);
                $this->CacheTagSet($cacheKey, $garage_info, CarFlowConst::CAR_FLOW_REDIS_EXPIRE);
            }
            fdump_api(['msg' => '子车库信息', 'garage_info' => $garage_info, 'line' => __LINE__], 'parkBetter/getSubParkingGarageLog', 1);
        }
        return $garage_info;
    }
    
    /**
     * 获取临时车登记信息.
     */
    public function getCarVisitorInfo($car_number, $village_id)
    {
        fdump_api(['msg' => '方法入参', 'parameter' => func_get_args(), 'line' => __LINE__], 'parkBetter/getCarVisitorInfoLog');
        $cacheKey = ($village_id && $car_number) ? 'carVisitorInfo_' . $car_number . '_' . $village_id : '';
        if (!$cacheKey) {
            return [];
        }
        if (!empty($this->CacheTagGet($cacheKey))) {
            $car_visitor_info = $this->CacheTagGet($cacheKey);
            fdump_api(['msg' => '缓存获取车辆临时登记信息', 'car_visitor_info' => $car_visitor_info, 'line' => __LINE__], 'parkBetter/getCarVisitorInfoLog', 1);
        } else {
            $db_house_village_visitor = new HouseVillageVisitor();
            $car_visitor_info = $db_house_village_visitor->get_one(['village_id' => $village_id, 'car_id' => $car_number], 'visitor_name, visitor_phone, uid, add_time');
            if ($car_visitor_info && !is_array($car_visitor_info)) {
                $car_visitor_info = $car_visitor_info->toArray();
            }
            if (!empty($car_visitor_info)) {
                $this->setCacheTag('carVisitorInfo:' . $village_id);
                $this->CacheTagSet($cacheKey, $car_visitor_info, CarFlowConst::CAR_FLOW_REDIS_EXPIRE);
            }
            fdump_api(['msg' => '车辆临时登记信息', 'car_visitor_info' => $car_visitor_info, 'line' => __LINE__], 'parkBetter/getCarVisitorInfoLog', 1);
        }
        return $car_visitor_info;
    }
    
    /**
     * 获取车位信息.
     */
    public function getCarPosition($car_position_id, $village_id=0)
    {
            if($car_position_id<1){
                return array();
            }
            $db_house_village_parking_position = new HouseVillageParkingPosition();
            $position_info_obj = $db_house_village_parking_position->getFind(['position_id' => $car_position_id], 'position_id, garage_id, position_num, start_time, end_time, position_pattern, children_type, parent_position_id');
            $position_info=array();
            if ($position_info_obj && !$position_info_obj->isEmpty()) {
                $position_info = $position_info_obj->toArray();
            }
            fdump_api(['msg' => '车位信息', 'position_info' => $position_info, 'line' => __LINE__], 'parkBetter/getCarPositionLog', 1);
        return $position_info;
    }
    
    /**
     * 获取停车配置.
     */
    public function getParkConfig($village_id, $garage_id = 0)
    {
        fdump_api(['msg' => '方法入参', 'parameter' => func_get_args(), 'line' => __LINE__], 'parkBetter/getParkConfigLog');
        $db_house_village_park_garage_config = new HouseVillageParkGarageConfig();
        $parkConfigInfo = [];
        if ($garage_id > 0) {
            $garageCacheKey = 'garageConfig_' . $village_id . '_' . $garage_id;
            if (!empty($this->CacheTagGet($garageCacheKey))) {
                $parkConfigInfo = $this->CacheTagGet($garageCacheKey);
                fdump_api(['msg' => '缓存获取车场停车配置信息', 'config' => $parkConfigInfo, 'line' => __LINE__], 'parkBetter/getParkConfigLog', 1);
            } else {
                $garageConfigWhere = [];
                $garageConfigWhere[] = ['garage_id', '=', $garage_id];
                $garageConfigWhere[] = ['village_id', '=', $village_id];
                $garage_config_obj = $db_house_village_park_garage_config->getOneData($garageConfigWhere);
                if ($garage_config_obj && !$garage_config_obj->isEmpty()) {
                    $parkConfigInfo = $garage_config_obj->toArray();
                }
                if (!empty($parkConfigInfo)) {
                    $this->setCacheTag('garageConfig:' . $village_id);
                    $this->CacheTagSet($garageCacheKey, $parkConfigInfo, CarFlowConst::CAR_FLOW_REDIS_EXPIRE);
                }
                fdump_api(['msg' => '缓存获取车场停车配置信息', 'config' => $parkConfigInfo, 'line' => __LINE__], 'parkBetter/getParkConfigLog', 1);
            }
        }
        if (empty($parkConfigInfo)) {
            $cacheKey = 'parkConfig_' . $village_id;
            if (!empty($this->CacheTagGet($cacheKey))) {
                $parkConfigInfo = $this->CacheTagGet($cacheKey);
                fdump_api(['msg' => '缓存获取停车配置信息', 'config' => $parkConfigInfo, 'line' => __LINE__], 'parkBetter/getParkConfigLog', 1);
            } else {
                $db_house_village_park_config = new HouseVillageParkConfig();
                // todo 先行查询车场是否有对应配置 没有再获取小区配置
                $parkConfigInfo = $db_house_village_park_config->getFind(['village_id' => $village_id]);
                if ($parkConfigInfo && !is_array($parkConfigInfo)) {
                    $parkConfigInfo = $parkConfigInfo->toArray();
                }
                if (!empty($parkConfigInfo)) {
                    $this->setCacheTag('parkConfig:' . $village_id);
                    $this->CacheTagSet($cacheKey, $parkConfigInfo, CarFlowConst::CAR_FLOW_REDIS_EXPIRE);
                }
                fdump_api(['msg' => '停车配置信息', 'config' => $parkConfigInfo, 'line' => __LINE__], 'parkBetter/getParkConfigLog', 1);
            }
        }
        if (empty($parkConfigInfo) && $village_id > 0) {
            $garageCacheKey = 'garageConfig_' . $village_id;
            if (!empty($this->CacheTagGet($garageCacheKey))) {
                $parkConfigInfo = $this->CacheTagGet($garageCacheKey);
                fdump_api(['msg' => '缓存获取车场停车配置信息', 'config' => $parkConfigInfo, 'line' => __LINE__], 'parkBetter/getParkConfigLog', 1);
            } else {
                $garageConfigWhere = [];
                $garageConfigWhere[] = ['village_id', '=', $village_id];
                $garage_config_obj = $db_house_village_park_garage_config->getOneData($garageConfigWhere);
                if ($garage_config_obj && !$garage_config_obj->isEmpty()) {
                    $parkConfigInfo = $garage_config_obj->toArray();
                }
                if (!empty($parkConfigInfo)) {
                    $this->setCacheTag('garageConfig:' . $village_id);
                    $this->CacheTagSet($garageCacheKey, $parkConfigInfo, CarFlowConst::CAR_FLOW_REDIS_EXPIRE);
                }
                fdump_api(['msg' => '缓存获取车场停车配置信息', 'config' => $parkConfigInfo, 'line' => __LINE__], 'parkBetter/getParkConfigLog', 1);
            }
        }
        return $parkConfigInfo;
    }
    
    /**
     * 查询对应符合条件的优惠券.
     */
    public function getCarFlowCoupon($car_number, $village_id, $is_use = -1, $park_coupon_id = 0, $park_sys_type = '')
    {
        $dbHouseVillageCarFlowCoupon = new HouseVillageCarFlowCoupon();
        $where_coupon = [];
        $where_coupon['village_id'] = $village_id;
        $where_coupon['car_number'] = $car_number;
        if ($park_sys_type) {
            $where_coupon['park_sys_type'] = $park_sys_type;
        }
        if ($park_coupon_id > 0) {
            $where_coupon['id'] = $park_coupon_id;
        }
        if ($is_use != -1) {
            $where_coupon['is_use'] = $is_use;
        }
        $order = ['receive_time' => 'ASC', 'id' => 'ASC'];
        $couponInfo = $dbHouseVillageCarFlowCoupon->getOne($where_coupon, true, $order);
        if ($couponInfo && !is_array($couponInfo)) {
            $couponInfo = $couponInfo->toArray();
        }
        if (empty($couponInfo)) {
            $db_house_village_park_coupon = new HouseVillageParkCoupon();
            $where_coupon = [];
            $where_coupon['village_id'] = $village_id;
            $where_coupon['car_number'] = $car_number;
            if ($park_sys_type) {
                $where_coupon['park_sys_type'] = $park_sys_type;
            }
            if ($park_coupon_id > 0) {
                $where_coupon['id'] = $park_coupon_id;
            }
            if ($is_use != -1) {
                $where_coupon['is_use'] = $is_use;
            }
            $couponInfo = $db_house_village_park_coupon->getFind($where_coupon);
            if ($couponInfo && !is_array($couponInfo)) {
                $couponInfo = $couponInfo->toArray();
            }
        }
        return $couponInfo;
    }

    public function setCacheTag($type)
    {
        $this->cacheTagKey = 'carFlow:cache:' . $type;
    }

    public function CacheTagSet($cacheKey, $data, $expire = 36400)
    {
        return Cache::tag($this->cacheTagKey)->set($cacheKey, $data, $expire);
    }

    public function CacheTagGet($cacheKey)
    {
        return Cache::get($cacheKey);
    }

    public function CacheTagClear()
    {
        return Cache::tag($this->cacheTagKey)->clear();
    }
    
    /**
     * todo 对应小区车场配置变动了清除下.
     */
    public function clearGarageConfig($village_id = 0)
    {
        try {
            $this->setCacheTag('garageConfig:' . $village_id);
            $this->CacheTagClear();
        } catch (\Exception $e){
            fdump_api(['msg' => '清除车场配置信息缓存错误', 'errLine' => $e->getLine(), 'errMsg' => $e->getMessage(), 'errFile' => $e->getFile(), 'errCode' => $e->getCode(), 'line' => __LINE__], 'parkBetter/clearGarageConfigLog', 1);
        }
    }

    /**
     * todo 对应小区车辆登记变动了清除下.
     */
    public function clearVisitorInfo($village_id = 0)
    {
        try {
            $this->setCacheTag('carVisitorInfo:' . $village_id);
            $this->CacheTagClear();
        } catch (\Exception $e){
            fdump_api(['msg' => '清除临时车登记信息缓存错误', 'errLine' => $e->getLine(), 'errMsg' => $e->getMessage(), 'errFile' => $e->getFile(), 'errCode' => $e->getCode(), 'line' => __LINE__], 'parkBetter/clearVisitorInfoLog', 1);
        }
    }

    /**
     * todo 对应小区有车道变动了清除下.
     */
    public function clearPassInfo($village_id = 0)
    {
        try {
            $this->setCacheTag('passageInfo:' . $village_id);
            $this->CacheTagClear();
        } catch (\Exception $e){
            fdump_api(['msg' => '清除车辆通道信息缓存错误', 'errLine' => $e->getLine(), 'errMsg' => $e->getMessage(), 'errFile' => $e->getFile(), 'errCode' => $e->getCode(), 'line' => __LINE__], 'parkBetter/clearPassInfoLog', 1);
        }
    }

    /**
     * todo 小区信息变动清除下.
     */
    public function clearVillageInfo($village_id = 0)
    {
        try {
            $this->setCacheTag('villageInfo:' . $village_id);
            $this->CacheTagClear();
        } catch (\Exception $e){
            fdump_api(['msg' => '清除小区信息缓存错误', 'errLine' => $e->getLine(), 'errMsg' => $e->getMessage(), 'errFile' => $e->getFile(), 'errCode' => $e->getCode(), 'line' => __LINE__], 'parkBetter/clearVillageInfoLog', 1);
        }
    }

    /**
     * todo 小区白名单变动清除下.
     */
    public function clearWhiteList($village_id = 0)
    {
        try {
            $this->setCacheTag('freeList:' . $village_id);
            $this->CacheTagClear();
        } catch (\Exception $e){
            fdump_api(['msg' => '清除小区白名单信息缓存错误', 'errLine' => $e->getLine(), 'errMsg' => $e->getMessage(), 'errFile' => $e->getFile(), 'errCode' => $e->getCode(), 'line' => __LINE__], 'parkBetter/clearWhiteListLog', 1);
        }
    }

    /**
     * todo 小区黑名单变动清除下.
     */
    public function clearBlackInfo($village_id = 0)
    {
        try {
            $this->setCacheTag('blackInfo:' . $village_id);
            $this->CacheTagClear();
        } catch (\Exception $e){
            fdump_api(['msg' => '清除小区黑名单信息缓存错误', 'errLine' => $e->getLine(), 'errMsg' => $e->getMessage(), 'errFile' => $e->getFile(), 'errCode' => $e->getCode(), 'line' => __LINE__], 'parkBetter/clearBlackInfoLog', 1);
        }
    }

    /**
     * todo 小区车辆变动清除下.
     */
    public function clearCarInfo($village_id = 0)
    {
        try {
            $this->setCacheTag('carInfo:' . $village_id);
            $this->CacheTagClear();
        } catch (\Exception $e){
            fdump_api(['msg' => '清除小区车辆信息缓存错误', 'errLine' => $e->getLine(), 'errMsg' => $e->getMessage(), 'errFile' => $e->getFile(), 'errCode' => $e->getCode(), 'line' => __LINE__], 'parkBetter/clearCarInfoLog', 1);
        }
    }

    /**
     * todo 小区车位变动清除下.
     */
    public function clearCarPositionInfo($village_id = 0)
    {
        try {
            $this->setCacheTag('carPositionInfo:' . $village_id);
            $this->CacheTagClear();
        } catch (\Exception $e){
            fdump_api(['msg' => '清除小区车位信息缓存错误', 'errLine' => $e->getLine(), 'errMsg' => $e->getMessage(), 'errFile' => $e->getFile(), 'errCode' => $e->getCode(), 'line' => __LINE__], 'parkBetter/clearCarPositionInfoLog', 1);
        }
    }

    /**
     * todo 小区车库变动清除下.
     */
    public function clearCarParkingGarageInfo($village_id = 0)
    {
        try {
            $this->setCacheTag('carParkingGarageInfo:' . $village_id);
            $this->CacheTagClear();
        } catch (\Exception $e){
            fdump_api(['msg' => '清除小区车库信息缓存错误', 'errLine' => $e->getLine(), 'errMsg' => $e->getMessage(), 'errFile' => $e->getFile(), 'errCode' => $e->getCode(), 'line' => __LINE__], 'parkBetter/clearCarParkingGarageInfoLog', 1);
        }
    }

    /**
     * todo 小区车辆配置变动清除下.
     */
    public function clearParkConfig($village_id = 0)
    {
        try {
            $this->setCacheTag('parkConfig:' . $village_id);
            $this->CacheTagClear();
        } catch (\Exception $e){
            fdump_api(['msg' => '清除车辆配置信息缓存错误', 'errLine' => $e->getLine(), 'errMsg' => $e->getMessage(), 'errFile' => $e->getFile(), 'errCode' => $e->getCode(), 'line' => __LINE__], 'parkBetter/clearParkConfigLog', 1);
        }
    }
    
    /**
     * 发送停车相关消息(支持A11).
     */
    public function sendCarMessage($car_number, $village_id, $sendParam)
    {
        fdump_api(['msg' => '发送停车相关消息-方法入参', 'parameter' => func_get_args(), 'line' => __LINE__], 'parkBetter/sendCarMessageLog');
        $car_id = isset($sendParam['car_id']) ? intval($sendParam['car_id']) : 0;
        $car_user_phone = isset($sendParam['car_user_phone']) ? trim($sendParam['car_user_phone']) : '';
        $openid = $this->getUserInfo($car_number, $village_id, $car_id, $car_user_phone);
        if (! $openid) {
            fdump_api(['msg' => '发送对象无openid', 'line' => __LINE__], 'parkBetter/sendCarMessageLog', 1);
            return false;
        }

        $garage_id = isset($sendParam['garage_id']) ? intval($sendParam['garage_id']) : 0;
        $end_time = isset($sendParam['end_time']) ? intval($sendParam['end_time']) : 0;
        $car_garage_id = isset($sendParam['car_garage_id']) ? intval($sendParam['car_garage_id']) : 0;
        $park_name = isset($sendParam['park_name']) ? trim($sendParam['park_name']) : '';
        $passage_direction = isset($sendParam['passage_direction']) ? trim($sendParam['passage_direction']) : '';
        $remarkTxt = isset($sendParam['remark']) ? trim($sendParam['remark']) : '';
        $is_small = isset($sendParam['is_small']) ? $sendParam['is_small'] : false;
        $property_id = isset($sendParam['property_id']) ? intval($sendParam['property_id']) : 0;
        $nowTime = isset($sendParam['nowTime']) ? intval($sendParam['nowTime']) : time();
        $park_time = isset($sendParam['park_time']) ? trim($sendParam['park_time']) : '';
        $stored_balance = isset($sendParam['stored_balance']) ? trim($sendParam['stored_balance']) : 0;

        $garage_info = $this->getParkingGarage($garage_id, $village_id);
        if ($garage_info && isset($garage_info['garage_num']) && $garage_info['garage_num']) {
            $park_name .= $garage_info['garage_num'] . '（车场）';
        }
        $remark = '';
        if ($park_time) {
            $remark .= '停留时长：' . $park_time;
        }
        if ($is_small && $car_garage_id) {
            $park_month_garage_info = $this->getParkingGarage($car_garage_id, $village_id);
            $park_month_name = isset($park_month_garage_info['garage_num']) && $park_month_garage_info['garage_num'] ? $park_month_garage_info['garage_num'].'（车场）' : '';
        } else {
            $park_month_name = '';
        }
        if ($end_time > 1) {
            $remark .= '\n'.$park_month_name.'月租期限：'.date('Y-m-d H:i:s', $end_time);
            if ($end_time < $nowTime) {
                $remark .= '（已过期）';
            }
        }
        if (floatval($stored_balance) > 0) {
            // 车辆储值余额
            $remark .= '\n储值余额：'.$stored_balance.'元';
        }
        if ($remarkTxt) {
            $remark .= $remarkTxt;
        }
        $data = [
            'wecha_id' => $openid,
            'first' => '车辆' . $passage_direction . '成功',
            'keyword1' => $car_number,
            'keyword2' => $park_name,
            'keyword3' => date('Y-m-d H:i:s'),
            'remark' => $remark,
            'new_info' => [//新版本发送需要的信息
                'tempKey' => '43021',//新模板号
                'car_number2' => $car_number,//车牌号
                'thing3' => $park_name,//停车场
                'phrase6' => $passage_direction,//进出类型
                'time4' => date('Y-m-d H:i:s'),//进出时间
            ],
        ];
        fdump_api(['msg' => '发送停车相关消息', 'data' => $data, 'property_id' => $property_id, 'line' => __LINE__], 'parkBetter/sendCarMessageLog', 1);
        //todo 类目模板OPENTM207117419
        return (new TemplateNewsService())->sendTempMsg('OPENTM207117419', $data, 0, $property_id, ($property_id ? 1 : 0));
    }

    /**
     * 根据车牌号查询用户信息.
     */
    public function getUserInfo($car_number, $village_id, $car_id = 0, $car_user_phone = '')
    {
        if (!$car_id) {
            $car_info = $this->getCarInfo($car_number, $village_id);
            $car_id = isset($car_info['car_id']) && $car_info['car_id'] ? $car_info['car_id'] : 0;
            !$car_user_phone && $car_user_phone = isset($car_info['car_user_phone']) && $car_info['car_user_phone'] ? trim($car_info['car_user_phone']) : '';
        }
        $db_user = new User();
        if ($car_id > 0) {
            $db_house_village_bind_car = new HouseVillageBindCar();
            $bind_info = $db_house_village_bind_car->getFind(['car_id' => $car_id, 'village_id' => $village_id]);
            $bind_info_uid = isset($bind_info['uid']) && $bind_info['uid'] ? $bind_info['uid'] : 0;
            if (isset($bind_info_uid) && $bind_info_uid > 0) {
                $user_info = $db_user->getOne(['uid' => $bind_info_uid], 'openid');
                if (isset($user_info['openid']) && $user_info['openid']) {
                    return $user_info['openid'];
                }
            }
        }
        if ($car_user_phone) {
            $user_info = $db_user->getOne(['phone' => $car_user_phone], 'openid');
            if (isset($user_info['openid']) && $user_info['openid']) {
                return $user_info['openid'];
            }
        }
        $db_house_village_visitor = new HouseVillageVisitor();
        $where_visitor = [
            ['car_id', '=', $car_number],
            ['village_id', '=', $village_id],
        ];
        $temp_visitor = $db_house_village_visitor->get_one($where_visitor);
        $visitor_uid = isset($temp_visitor['visitor']) && $temp_visitor['visitor'] ? $temp_visitor['visitor'] : 0;
        if ($visitor_uid > 0) {
            $user_info = $db_user->getOne(['uid' => $visitor_uid], 'openid');
            if (isset($user_info['openid']) && $user_info['openid']) {
                return $user_info['openid'];
            }
        }

        if (isset($bind_info) && isset($bind_info['user_id']) && $bind_info['user_id'] > 0) {
            $db_house_village_user_bind = new HouseVillageUserBind();
            $whereBind = [
                'pigcms_id' => $bind_info['user_id']
            ];
            $userBind = $db_house_village_user_bind->getOne($whereBind, 'uid');
            if (isset($userBind['uid']) && intval($userBind['uid']) > 0) {
                $user_info = $db_user->getOne(['uid' => $userBind['uid']], 'openid');
                if (isset($user_info['openid']) && $user_info['openid']) {
                    return $user_info['openid'];
                }
            }
        }
        return '';
    }
    
    /**
     * 推送消息至对应配置企微群.
     */
    public function pushMessageQy($info, $village_name = '无', $car_number = '无', $device_name = '无', $flow_status = '拒绝通行', $direction = '无', $reason = '停车设备不存在', $time = 0)
    {
        fdump_api(['msg' => '方法入参', 'parameter' => func_get_args(), 'line' => __LINE__], 'parkBetter/pushMessageQyLog');
        //企业微信群提醒
        $weComKey = cfg('house_village_car_flow_we_com_key');
        $weComKey = trim($weComKey);
        if (!$weComKey) {
            return [];
        }
        !$village_name && $village_name = '无';
        !$car_number && $car_number = '无';
        !$device_name && $device_name = '无';
        !$flow_status && $flow_status = '拒绝通行';
        !$direction && $direction = '无';
        !$reason && $reason = '无';
        !$time && $time = time();
        $webhookurl = $this->webhookurl . "?key=" . $weComKey;
        $data = [
            "msgtype" => "markdown",
            "markdown" => [
                "content" => "<font color='info'>**" . $info . "**</font>
                                 >小区名称: <font color='info'>**" . $village_name . "**</font>
                                 >车牌号码: <font color='info'>**" . $car_number . "**</font>
                                 >设备名称: <font color='info'>**" . $device_name . "**</font> 
                                 >通行状态: <font color='info'>**" . $flow_status . "**</font> 
                                 >通行方向: <font color='info'>**" . $direction . "**</font> 
                                 >行为原因: <font color='info'>**" . $reason . "**</font> 
                                 >通知时间: <font color='info' >" . date('Y-m-d H:i:s', $time) . "</font>"
            ],
        ];
        fdump_api(['msg' => '企业微信群提醒', 'data' => $data, 'line' => __LINE__], 'parkBetter/pushMessageQyLog', 1);
        http_request($webhookurl, 'POST', json_encode($data));
        //停车设备不存在
        return $data;
    }
    
    /**
     * 公用的将时间转为 时分秒文本显示.
     */
    public function commonChangeTimeText($park_time) {
        $hours = intval($park_time / 3600);
        $mins = intval(($park_time - $hours * 3600) / 60);
        $second = $park_time - $hours * 3600 - $mins * 60;
        $time = '';
        if ($hours > 0) {
            $time .= $hours . '小时';
        }
        if ($mins > 0) {
            $time .= $mins . '分钟';
        }
        if ($second > 0) {
            $time .= $second . '秒';
        }
        return $time;
    }
    
    /*** ↓处理停车相关记录的方法  ***/
    /**
     * 计算A11临时车费用.
     */
    public function getA11TmpCarPay($data, $inParkRecord = [], $park_config = [], $carInfo = []) {
        fdump_api(['msg' => '计算临时车费用', 'line' => __LINE__, 'data' => $data, 'inParkRecord' => $inParkRecord, 'park_config' => $park_config, 'carInfo' => $carInfo], 'parkBetter/getA11TmpCarPay');
        $car_number = isset($data['car_number']) && $data['car_number'] ? trim($data['car_number']) : '';
        $village_id = isset($data['village_id']) && $data['village_id'] ? intval($data['village_id']) : 0;
        $park_id = isset($data['park_id']) && $data['park_id'] ? intval($data['park_id']) : $village_id;
        $garage_id = isset($data['garage_id']) && $data['garage_id'] ? intval($data['garage_id']) : 0;
        $park_sys_type = isset($data['park_sys_type']) && $data['park_sys_type'] ? trim($data['park_sys_type']) : 'D3';
        $lastTimeLimit = isset($data['lastTimeLimit']) && $data['lastTimeLimit'] ? intval($data['lastTimeLimit']) : 0;
        $nowTime = isset($data['nowTime']) && $data['nowTime'] ? $data['nowTime'] : time();
        $uid = isset($data['uid']) ? intval($data['uid']) : 0;
        $device_number = isset($data['device_number']) && $data['device_number'] ? trim($data['device_number']) : '';
        $park_name = isset($data['park_name']) && $data['park_name'] ? trim($data['park_name']) : '';
        $car_number_type = isset($data['car_number_type']) && $data['car_number_type'] ? trim($data['car_number_type']) : '';

        $in_time = isset($inParkRecord['in_time']) && $inParkRecord['in_time'] ? $inParkRecord['in_time'] : 0;
        $order_id = isset($inParkRecord['order_id']) && $inParkRecord['order_id'] ? $inParkRecord['order_id'] : '';
        $is_first = isset($inParkRecord['is_first']) && $inParkRecord['is_first'] ? $inParkRecord['is_first'] : 0;
        $park_name = isset($inParkRecord['park_name']) && $inParkRecord['park_name'] ? $inParkRecord['park_name'] : '';
        $end_time = isset($carInfo['end_time']) && $carInfo['end_time'] ? $carInfo['end_time'] : 0;
        
        $isA11 = $park_sys_type == 'A11' ? true : false;
        
        $carHandleGetInfoService = new CarHandleGetInfoService();
        if (empty($park_config)) {
            if (isset($data['garage_id']) && intval($data['garage_id']) > 0) {
                $park_config = $carHandleGetInfoService->getParkConfig($village_id, intval($data['garage_id']));
            } else {
                $park_config = $carHandleGetInfoService->getParkConfig($village_id, $garage_id);
            }
        }
        fdump_api(['msg' => '配置项+入场信息', 'line' => __LINE__, 'park_config' => $park_config, 'inParkRecord' => $inParkRecord], 'parkBetter/getA11TmpCarPay', true);
        if ($lastTimeLimit && $lastTimeLimit > $inParkRecord['in_time'] && $lastTimeLimit < $nowTime) {
            // 如果车辆上一次的 出入记录时间界限大于 当前入场时间 并小于 当前时间 替换入场入场时间
            $in_time = $lastTimeLimit;
        }
        // todo 处理停留时长
        $park_time = $nowTime - $in_time;
        fdump_api(['msg' => '车辆停留时长', 'line' => __LINE__, 'inParkRecord' => $inParkRecord, 'park_time' => $park_time], 'parkBetter/getA11TmpCarPay', true);

        if (empty($inParkRecord) || intval($park_time) <= 0) {
            $order_id = '330' . date('YmdHis') . rand(100, 999);
            $not_inPark_money = isset($park_config['not_inPark_money']) ? intval($park_config['not_inPark_money']) : 0;
            if ($not_inPark_money > 0) {
                $data_temp = [
                    'village_id' => $village_id, 'add_time' => $nowTime, 'free_out_time' => 0, 'duration' => 0,
                    'derate_money' => 0, 'derate_duration' => 0, 'order_id' => $order_id, 'query_order_no' => build_real_orderid($uid),
                    'errmsg' => '', 'price' => $not_inPark_money, 'total' => $not_inPark_money, 'in_time' => 0,
                    'car_number' => $car_number, 'out_channel_id' => $device_number, 'is_pay_scene' => 1, 'park_sys_type' => $park_sys_type,
                ];
                if (isset($data['in_record_id']) && $data['in_record_id']) {
                    $data_temp['in_record_id'] = $data['in_record_id'];
                }
                fdump_api(['msg' => '缺少入场记录-直接反馈[不在场车辆放行需交费用]', 'line' => __LINE__, 'data_temp' => $data_temp], 'parkBetter/getA11TmpCarPay', true);
                // todo 记录对应临时表中
                $tempInfo = $this->recordCarFlowTemp($data_temp);
                $flow_temp_id = isset($tempInfo['flow_temp_id']) ? intval($tempInfo['flow_temp_id']) : 0;
                $list = [
                    'time' => '--', 'park_time' => 0, 'pay_money' => $not_inPark_money, 'order_id' => $order_id,
                    'park_name' => $park_name, 'in_time' => '--', 'pay_time' => 0, 'total_money' => $not_inPark_money,
                    'coupon_money' => 0, 'coupon_id' => 0, 'parkTemp_money' => 0, 'park_temp_id' => 0, 'flow_temp_id' => $flow_temp_id,
                ];
                fdump_api(['msg' => '缺少入场记录-直接反馈[不在场车辆放行需交费用]', 'line' => __LINE__, 'list' => $list], 'parkBetter/getA11TmpCarPay', true);
                return $list;
            } else {
                $list = [
                    'time' => '--', 'park_time' => 0, 'pay_money' => 0, 'order_id' => $order_id,
                    'park_name' => $park_name, 'in_time' => '--', 'pay_time' => 0, 'total_money' => 0,
                    'coupon_money' => 0, 'coupon_id' => 0, 'parkTemp_money' => 0, 'park_temp_id' => 0,
                ];
                fdump_api(['msg' => '缺少入场记录-无需付费直接出场', 'line' => __LINE__, 'list' => $list], 'parkBetter/getA11TmpCarPay', true);
                return $list;
            }
        }
        
        if ($isA11) {
            /** @var object|array car_type_month_to_temp 月租车到期用临时车卡类标准 */
            $car_type_month_to_temp = array();
            $tmp_car_type_month_to_temp = array();
            if (isset($park_config['car_type_month_to_temp']) && !empty($park_config['car_type_month_to_temp'])) {
                $tmp_car_type_month_to_temp = json_decode($park_config['car_type_month_to_temp'], 1);
            }
            if ($tmp_car_type_month_to_temp) {
                foreach ($tmp_car_type_month_to_temp as $vv) {
                    $car_type_month_to_temp[$vv['month_car_type']] = $vv;
                }
            }
            fdump_api(['msg' => '车辆信息', 'line' => __LINE__, 'park_config' => $park_config], 'parkBetter/getA11TmpCarPay', true);
            $parking_car_type = isset($carInfo['parking_car_type']) && $carInfo['parking_car_type'] ? intval($carInfo['parking_car_type']) : 0;
            $db_house_village_park_charge_cartype = new HouseVillageParkChargeCartype();
            $serviceA11 = new A11Service();
            if (empty($carInfo)) {
                $whereCarType = ['village_id' => $village_id, 'car_number_type' => $car_number_type, 'status' => 1, 'park_sys_type' => $park_sys_type];
                $carTypeInfo = $db_house_village_park_charge_cartype->getFind($whereCarType, 'car_type');
                if (isset($carTypeInfo['car_type']) && intval($carTypeInfo['car_type']) > 0) {
                    $parking_car_type = intval($carTypeInfo['car_type']);
                } else {
                    $parking_car_type = $serviceA11::Temp_A;
                }
            } else {
                if (!in_array($parking_car_type, $serviceA11->A11_Temp) && !in_array($parking_car_type, $serviceA11->A11_Stored)) {
                    $parking_car_type = $serviceA11::Temp_A;
                    $temp_car_type = $car_type_month_to_temp && isset($car_type_month_to_temp[$parking_car_type]) && isset($car_type_month_to_temp[$parking_car_type]['temp_car_type']) ? $car_type_month_to_temp[$parking_car_type]['temp_car_type'] : '';
                    if (in_array($temp_car_type, $serviceA11->A11_Temp)) {
                        $parking_car_type = $car_type_month_to_temp[$parking_car_type]['temp_car_type'];
                    }
                } else {
                    $parking_car_type = $carInfo['parking_car_type'];
                }
            }
            $whereCarTypeRules = ['village_id' => $data['village_id'], 'car_type' => $parking_car_type, 'status' => 1, 'park_sys_type' => $park_sys_type];
            $rule_ids = $db_house_village_park_charge_cartype->getColumn($whereCarTypeRules, 'rule_id');
            if (empty($rule_ids)) {
                fdump_api(['msg' => '当前卡类未绑定收费标准', 'line' => __LINE__, 'parking_car_type' => $parking_car_type, 'data' => $data], 'parkBetter/getA11TmpCarPay', true);
                throw new Exception('当前卡类未绑定收费标准');
            }
            $db_house_new_charge_rule = new HouseNewChargeRule();
            $where_rule = [
                ['id', 'in', $rule_ids],
                ['village_id', '=', $village_id],
                ['status', '=', 1],
                ['charge_valid_time', '<=', $nowTime],
                ['fees_type', '=', 3],
            ];
            $rule_info = $db_house_new_charge_rule->getOne($where_rule, 'id, park_charge_id', 'charge_valid_time DESC');
            if ($rule_info && !is_array($rule_info)) {
                $rule_info = $rule_info->toArray();
            }
            if (empty($rule_info)) {
                fdump_api(['msg' => '该停车场未绑定收费规则', 'line' => __LINE__, 'where_rule' => $where_rule, 'data' => $data], 'parkBetter/getA11TmpCarPay', true);
                throw new Exception('该停车场未绑定收费规则');
            }
        } else {
            $db_house_new_charge_rule = new HouseNewChargeRule();
            $where_rule = [
                ['village_id', '=', $village_id], ['status', '=', 1],
                ['charge_valid_time', '<=', $nowTime], ['fees_type', '=', 3],
            ];
            $rule_info = $db_house_new_charge_rule->getOne($where_rule, 'id, park_charge_id', 'charge_valid_time DESC');
            if ($rule_info && !is_array($rule_info)) {
                $rule_info = $rule_info->toArray();
            }
            if (empty($rule_info)) {
                fdump_api(['msg' => '该停车场未绑定收费规则', 'line' => __LINE__, 'where_rule' => $where_rule, 'data' => $data], 'parkBetter/getA11TmpCarPay', true);
                throw new Exception('该停车场未绑定收费规则');
            }
        }
        $park_charge_id = isset($rule_info['park_charge_id']) && $rule_info['park_charge_id'] ? $rule_info['park_charge_id'] : 0;
        $db_house_village_park_charge = new HouseVillageParkCharge();
        $where_charge = [
            ['village_id', '=', $village_id], ['status', '=', 1], ['id', '=', $park_charge_id],
        ];
        $park_charge = $db_house_village_park_charge->getFind($where_charge);
        if ($park_charge && !is_array($park_charge)) {
            $park_charge = $park_charge->toArray();
        }
        if (empty($park_charge)) {
            fdump_api(['msg' => '该停车场未绑定收费规则', 'line' => __LINE__, 'where_charge' => $where_charge, 'data' => $data], 'parkBetter/getA11TmpCarPay', true);
            throw new Exception('该停车场未绑定收费规则');
        }
        $charge_type = isset($park_charge['charge_type']) ? intval($park_charge['charge_type']) : 0;
        if ($charge_type == 1) {
            $park_charge_info = isset($park_charge['charge_set']) ? unserialize($park_charge['charge_set']) : [];
            if (!empty($park_charge_info)) {
                $last_ages = array_column($park_charge_info, 'time');
                array_multisort($last_ages, SORT_DESC, $park_charge_info);
            }
            if (empty($park_charge_info)) {
                fdump_api(['msg' => '费用设置数据错误', 'line' => __LINE__, 'park_charge' => $park_charge], 'parkBetter/getA11TmpCarPay', true);
                throw new \Exception('费用设置数据错误！');
            }
        } elseif ($charge_type == 4) {
            // 24小时逐时收费制
            $park_charge_info = isset($park_charge['charge_set']) ? unserialize($park_charge['charge_set']) : [];
            if (empty($park_charge_info)) {
                fdump_api(['msg' => '费用设置数据错误', 'line' => __LINE__, 'park_charge' => $park_charge], 'parkBetter/getA11TmpCarPay', true);
                throw new \Exception('费用设置数据错误！');
            }
        }

        // 计算用停留时长
        $computeParkTime = $park_time;

        if ($isA11) {
            $isOutSubPark = isset($data['isOutSubPark']) ? $data['isOutSubPark'] : false;
            $isOutParentPark = isset($data['isOutParentPark']) ? $data['isOutParentPark'] : false;
            $notParentORSubPark = isset($data['notParentORSubPark']) ? $data['notParentORSubPark'] : true;
            $isOverMonth = isset($data['isOverMonth']) ? $data['isOverMonth'] : false;
            $isSameGarage = isset($data['isSameGarage']) ? $data['isSameGarage'] : false;
            $car_garage_id = isset($data['car_garage_id']) ? intval($data['car_garage_id']) : 0;
            $passage_garage_id = isset($data['passage_garage_id']) ? intval($data['passage_garage_id']) : 0;
            if ($car_garage_id > 0 && $car_garage_id == $passage_garage_id) {
                $isSameGarage = true;
            }
            if (intval($end_time) > 100 && intval($end_time) < intval($nowTime)) {
                // 是月租车 但是过期了
                $isOverMonth = true;
            }
            if (intval($end_time) > 100 && intval($end_time) > intval($nowTime)) {
                // 是月租车 没过期了
                $isMonth = true;
            }
            if ($isSameGarage) {
                // 车辆归属车场 与 当前 出场通道归属 车场相同
                if ($isOverMonth) {
                    // 是月租车 但是过期了
                    $computeParkTime = $nowTime - $end_time;
                } elseif ($isMonth) {
                    $computeParkTime = 0;
                }
            } else {
                // 车辆归属车场 与 当前 出场通道归属 车场不相同
                // 去除当前已经出场记录中 在[未出场记录]的[入场时间]到[当前时间]之间[出场记录]的时间
                $whereLastRecord = [];
                $whereLastRecord[] = ['flow_direction', '=', 2];
                $whereLastRecord[] = ['is_out', '=', 1];
                $whereLastRecord[] = ['flow_time', '<', $nowTime];
                $whereLastRecord[] = ['flow_other_time|flow_time', '>', $in_time];
                $park_where = [];
                $park_where[] = ['in_time|out_time', '>', $in_time];
                $park_where[] = ['out_time', '<', $nowTime];
                $subLastRecordList = $this->getLastCarFlowRecordList($car_number, $village_id, $park_sys_type, $whereLastRecord, $park_where);
                $tmp_park_time = 0;   //之前小场停车时间
                $in_channel_id = 0;
                foreach ($subLastRecordList as $subLastRecord) {
                    if (intval($subLastRecord['out_time']) > intval($subLastRecord['in_time']) && intval($subLastRecord['park_time']) > 0) {
                        $tmp_park_time += $subLastRecord['park_time'];
                        $in_channel_id = $subLastRecord['in_channel_id'];
                    }
                }
                if ($in_channel_id > 0 && $tmp_park_time > 0) {
                    $last_record_passage_info = $this->getParkPassageInfoById($in_channel_id, $village_id);
                    if (isset($last_record_passage_info['garage_id']) && intval($last_record_passage_info['garage_id']) > 0) {
                        $last_record_garage_id = intval($last_record_passage_info['garage_id']);
                    } elseif (isset($last_record_passage_info['area_type']) && isset($last_record_passage_info['passage_area']) && intval($last_record_passage_info['area_type']) == 2 && intval($last_record_passage_info['passage_area']) > 0) {
                        $last_record_garage_id = intval($last_record_passage_info['passage_area']);
                    } else {
                        $last_record_garage_id = 0;
                    }
                    if ($car_garage_id == $last_record_garage_id) {
                        $computeParkTime = $nowTime - $in_time - $tmp_park_time;
                    }
                }
            }
        }

        $is_advance_pay = 0;  //付过了，识别比心跳过来的快
        $parkTemp = $this->getCarFlowTemp($car_number, $village_id, 1);
        $park_pay_time = isset($parkTemp['pay_time']) && $parkTemp['pay_time'] ? intval($parkTemp['pay_time']) : 0;
        $park_pay_price = isset($parkTemp['price']) && floatval($parkTemp['price']) > 0 ? $parkTemp['price'] : 0;

        $free_park_time = isset($park_config['free_park_time']) ? intval($park_config['free_park_time']) * 60 : 0;
        $sHouseVillageCarFlowService = new HouseVillageCarFlowService();
        $free_park_time <= 0 && $free_park_time = $sHouseVillageCarFlowService->default_free_park_time;

        if ($park_pay_time > 0 && $park_pay_time > $in_time) {
            $park_temp_money = $park_pay_price;
            $next_last_park_time = time() - $park_pay_time;
            if ($next_last_park_time < 600 && isset($parkTemp['order_id']) && isset($inParkRecord['order_id']) && $parkTemp['order_id'] == $inParkRecord['order_id']) {
                //10分钟中内相同 入场记录如果有 支付记录 记下钱
                $is_advance_pay = 1;
            }
            if ($nowTime - $park_pay_time < $free_park_time) {
                // 如果当前识别时间 减去 上一次支付时间 小于缴费后停留时长 计算停留时长置零
                $computeParkTime = 0;
            }
        } else {
            $park_temp_money = 0;
        }
        $park_money = 0;
        $pay_money = 0;
        $coupon_id = 0;
        $derate_duration = 0;
        $park_coupon_money = 0;
        if ($computeParkTime > 0) {
            $charge_type5_set = isset($park_charge['charge_type5_set']) ? $park_charge['charge_type5_set'] : 0;
            $computeParkTimeToMinute = ceil($computeParkTime / 60);
            $free_time = isset($park_charge['free_time']) ? intval($park_charge['free_time']) : 0;
            $first_free_time = isset($park_charge['first_free_time']) ? intval($park_charge['first_free_time']) : 0;
            $first_charge_money = isset($park_charge['first_charge_money']) && floatval($park_charge['first_charge_money']) > 0 ? $park_charge['first_charge_money'] : 0;
            $max_charge_money = isset($park_charge['max_charge_money']) && floatval($park_charge['max_charge_money']) > 0 ? $park_charge['max_charge_money'] : 0;
            $free_time_no_count = isset($park_charge['free_time_no_count']) ? intval($park_charge['free_time_no_count']) : 0;
            // 步长（单位：分钟）
            $charge_time = isset($park_charge['charge_time']) ? intval($park_charge['charge_time']) : 0;
            // 步长对应的收费金额
            $charge_money = isset($park_charge['charge_money']) && floatval($park_charge['charge_money']) > 0 ? $park_charge['charge_money'] : 0;
            // 连续多少小时只收取一次费
            $hour_once_fee = isset($park_charge['hour_once_fee']) ? intval($park_charge['hour_once_fee']) : 0;

            if ($computeParkTimeToMinute <= $free_time) {
                // 停留分钟小于 免停时长  费用0
                $pay_money = 0;
                $derate_duration = $computeParkTimeToMinute;
            } elseif ($is_first == 1 && $first_free_time && $computeParkTimeToMinute <= $first_free_time) {
                // 首次入场停车 停留时间符合首停时长  直接使用首停金额
                $pay_money = $first_charge_money;
                $derate_duration = $computeParkTimeToMinute;
            } else {
                if ($free_time_no_count == 1 && $free_time > 0) {
                    // 从免费时间后开始计算费用
                    $computeParkTime = $computeParkTime - ($free_time * 60);
                    $computeParkTime = $computeParkTime > 0 ? $computeParkTime : 0;
                }
                /***
                 * 新按次收费
                 * 1连续多少小时只收取一次费(在原来charge_type是3 上面时间不一定是24了) 2按次收费（和 charge_type是2一样） 3当天只收取一次费(和 charge_type是3一样)
                 */
                // $charge_type 1-24小时计算步长 2-单次只收一次费用 3-零点前只收一次费用 4-24小时逐时收费 5-按次收费
                $other = true;
                if ($charge_type == 1) {
                    $other = false;
                    // 1-24小时计算步长
                    $computeParkTimeToHour = ceil($computeParkTime / 3600);// 计算用停留时间转为小时 向上取整
                    if ($max_charge_money > 0 && $computeParkTimeToHour > 24) {
                        // 每日封顶金额大于0 且 停留时长超过1天
                        // 转换停留时长为天数 取整数部分
                        $days = intval($computeParkTime / 86400);
                        // 停留金额 = 天数 * 封顶金额
                        $park_money = $days * $max_charge_money;
                        // 剩余停留计算时间 = 停留时长（秒） - 天数*每天秒数
                        $computeParkTime = $computeParkTime - $days * 86400;
                        // 剩余小时数 = 停留小时数 - 天数*24
                        $computeParkTimeToHour = $computeParkTimeToHour - 24 * $days;
                    }

                    $park_charge_info_max = isset($park_charge_info[0]) ? $park_charge_info[0] : [];
                    if (isset($park_charge_info_max['time']) && intval($park_charge_info_max['time']) < $computeParkTimeToHour) {
                        // 如果 费用设置 最大时长 小于 停留时长（小时）
                        // 金额直接赋予
                        $pay_money = $park_charge_info_max['money'];
                        // 去除 费用设置 最大时长（秒）获取 剩余计算用停留时长（秒）
                        $computeParkTime1 = $computeParkTime - $park_charge_info_max['time'] * 3600;
                        if ($max_charge_money > 0) {
                            // 存在每日封顶金额 取剩余计算停留时长/（除以）步长 【向上取整】
                            $computeParkTime2 = ceil($computeParkTime1 / ($charge_time * 60));
                            // 计算 步长 * 步长金额
                            $park_money1 = $computeParkTime2 * $charge_money;
                            if ($computeParkTimeToHour > 24 && ($pay_money + $park_money1) > $max_charge_money) {
                                // 剩余计算用停留时长（小时）大于24小时 累加 每日封顶金额
                                $park_money += $max_charge_money;
                            } elseif ($computeParkTimeToHour <= 24 && ($pay_money + $park_money1) > $max_charge_money) {
                                // 计算后金额 大于 每日封顶金额 取每日封顶金额
                                $pay_money = $max_charge_money;
                            } else {
                                $park_money += $park_money1;
                            }
                        } else {
                            // 取剩余计算停留时长/（除以）步长 【向上取整】
                            $computeParkTime2 = ceil($computeParkTime1 / ($charge_time * 60));
                            // 计算 步长 * 步长金额
                            $park_money1 = $computeParkTime2 * $charge_money;
                            // 金额进行累加下
                            $park_money += $park_money1;
                        }
                    } else {
                        foreach ($park_charge_info as $k => $v) {
                            if ($v['time'] == $computeParkTimeToHour) {
                                // 剩余计算用停留时长（小时） = 对应设置时间 金额赋予
                                $pay_money = $v['money'];
                                break;
                            } elseif ($v['time'] < $computeParkTimeToHour) {
                                // 剩余计算用停留时长（小时） > 对应设置时间 金额赋予 回退一格
                                $pay_money = $park_charge_info[$k - 1]['money'];
                                break;
                            } elseif (!isset($park_charge_info[$k + 1]) && $v['time'] > $computeParkTimeToHour) {
                                // 剩余计算用停留时长（小时） < 对应设置时间 且没有了下一个设置  取当前金额
                                $pay_money = $park_charge_info[$k]['money'];
                                break;
                            }
                        }
                    }
                }
                if ($charge_type == 4) {
                    $other = false;
                    // 4-24小时逐时收费
                    $computeParkTimeToHour = ceil($computeParkTime / 3600);// 计算用停留时间转为小时 向上取整
                    $tmp_pay_money = 0;
                    do {
                        foreach ($park_charge_info as $kk => $vv) {
                            $new_key = $kk + 1;
                            $hour = isset($vv['hour']) ? intval($vv['hour']) : 1;
                            $nextHour = isset($park_charge_info[$new_key]) && isset($park_charge_info[$new_key]['hour']) ? intval($park_charge_info[$new_key]['hour']) : 0;
                            if ($hour < 24 && ($computeParkTimeToHour >= $hour) && ($computeParkTimeToHour < $nextHour)) {
                                $tmp_pay_money += $vv['fee'];
                            } else if ($hour == 1 && $computeParkTimeToHour <= $hour) {
                                $tmp_pay_money += $vv['fee'];
                            } else if ($hour == 24 && $computeParkTimeToHour >= $hour) {
                                $tmp_pay_money += $vv['fee'];
                            }
                        }
                        $computeParkTimeToHour = $computeParkTimeToHour - 24;
                    } while ($computeParkTimeToHour > 0);
                    $pay_money = round($tmp_pay_money, 2);
                }
                if ($charge_type == 2 || ($charge_type == 5 && $charge_type5_set == 2)) {
                    $other = false;
                    // 2-单次只收一次费用  charge_type5_set 2-按次收费
                    $days = ceil($computeParkTime / 86400);// 计算用停留时间转为天数 向上取整
                    $pay_money = $max_charge_money * $days;
                }
                if ($other) {
                    $parkTimeCharge = $in_time + $computeParkTime; // 入场时间+计算停车时长
                    $oneDaySecond = 86400;  // 24小时 默认 连续24小时只收取一次费
                    if ($charge_type == 5 && $charge_type5_set == 1 && $hour_once_fee > 0) {
                        $oneDaySecond = $hour_once_fee * 3600;  // 自定义的 连续N小时收取一次费用
                    }
                    $pay_money = 0;
                    // 入场时间的 当前0点
                    $in_time_day_start_second = strtotime(date('Y-m-d 00:00:00', $in_time));
                    // 入场时间的 当天0点 + 连续单次收费时长
                    $time_charge = $in_time_day_start_second + $oneDaySecond;
                    if ($time_charge < $parkTimeCharge) {
                        // 计算连续N小时收取一次步长
                        $parkTime_charge2 = ceil($computeParkTime / $oneDaySecond);
                        $pay_money = $max_charge_money * $parkTime_charge2;
                    } elseif (empty($parkTemp) || $park_pay_price <= 0) {
                        //第一次
                        $pay_money = $max_charge_money;
                        if ($charge_type5_set == 3 || $charge_type5_set == 1) {
                            $toDayOutTimeLimit = strtotime(date('Y-m-d 00:00:00', $nowTime));
                            $whereLastRecord = [];
                            $whereLastRecord[] = ['flow_direction', '=', 2];
                            $whereLastRecord[] = ['is_out', '=', 1];
                            $whereLastRecord[] = ['flow_time', '>', $toDayOutTimeLimit];
                            $park_where = [];
                            $park_where[] = ['out_time', '>', $toDayOutTimeLimit];
                            $hasOutRecord = $this->getLastCarFlowRecord($car_number, $village_id, $park_sys_type, $whereLastRecord, $park_where);
                            if(!empty($hasOutRecord)){
                                // 当前通行当天存在已通行记录 金额置零
                                $pay_money = 0;
                            }

                        }
                    }
                }
            }

            $pay_money = $pay_money + $park_money - $park_temp_money;
            $pay_money = $pay_money > 0 ? $pay_money : 0;
            $total_money = $pay_money;
            if ($pay_money > 0) {
                $this->checkCouponUseStatus($car_number, $village_id, $park_sys_type);
                $coupon_info = $this->getCarFlowCoupon($car_number, $village_id, 0, 0, $park_sys_type);
                fdump_api(['msg' => '优惠券', 'line' => __LINE__, 'coupon_info' => $coupon_info], 'parkBetter/getA11TmpCarPay', true);
                if (!empty($coupon_info)) {
                    $coupon_id = isset($coupon_info['park_coupon_id']) && $coupon_info['park_coupon_id'] ? $coupon_info['park_coupon_id'] : 0;
                    !$coupon_id && $coupon_id = isset($coupon_info['id']) && $coupon_info['id'] ? $coupon_info['id'] : 0;
                    $pay_money = $pay_money - $coupon_info['money'];
                    if ($pay_money <= 0) {
                        $pay_money = 0;
                    }
                    $park_coupon_money = $coupon_info['money'];
                    // todo 更新优惠券为已使用
                    $this->updateCouponToUsed($car_number, $village_id, $coupon_id, $park_sys_type, $nowTime);
                }
            }
        }
        $data_temp = [];
        $data_temp['village_id'] = $village_id;
        $data_temp['add_time'] = time();
        $data_temp['free_out_time'] = $free_time;
        $data_temp['duration'] = round_number($park_time / 60, 2);
        $data_temp['derate_money'] = $park_coupon_money;
        $data_temp['derate_duration'] = $derate_duration;
        $data_temp['order_id'] = $order_id;
        $data_temp['query_order_no'] = build_real_orderid($uid);
        $data_temp['errmsg'] = '';
        $data_temp['price'] = $pay_money;
        $data_temp['total'] = $total_money;
        $data_temp['in_time'] = $in_time;
        $data_temp['car_number'] = $car_number;
        $data_temp['out_channel_id'] = $device_number;
        $data_temp['is_pay_scene'] = 1;
        $data_temp['park_sys_type'] = $park_sys_type;
        if ($pay_money == 0) {
            $data_temp['is_paid'] = 1;
            $data_temp['pay_time'] = time();
        }
        if (isset($data['in_record_id']) && $data['in_record_id']) {
            $data_temp['in_record_id'] = $data['in_record_id'];
        }
        isset($parking_car_type) && $parking_car_type && $data_temp['car_type'] = $parking_car_type;
        $data_temp['park_charge'] = json_encode($park_charge_info,JSON_UNESCAPED_UNICODE);
        fdump_api(['msg' => '记录对应临时表中', 'line' => __LINE__, 'data_temp' => $data_temp], 'parkBetter/getA11TmpCarPay', true);
        // todo 记录对应临时表中
        $tempInfo = $this->recordCarFlowTemp($data_temp);
        $flow_temp_id = isset($tempInfo['flow_temp_id']) ? intval($tempInfo['flow_temp_id']) : 0;

        $time = $this->commonChangeTimeText($park_time);
        $list = [];
        $list['time'] = $time;
        $list['park_time'] = $park_time;
        $list['pay_money'] = $pay_money;
        $list['order_id'] = $order_id;
        $list['park_name'] = $park_name;
        $list['in_time'] = date('Y-m-d H:i:s', $in_time);
        $list['pay_time'] = time();
        $list['total_money'] = $total_money;
        $list['coupon_money'] = 0;
        $list['coupon_id'] = $coupon_id;
        $list['parkTemp_money'] = $park_temp_money;
        isset($parking_car_type) && $parking_car_type && $list['car_type'] = $parking_car_type;
        $list['park_charge'] = $park_charge_info;
        $list['flow_temp_id'] = $flow_temp_id;
        $list['is_advance_pay'] = $is_advance_pay;
        fdump_api(['msg' => '计算结果', 'line' => __LINE__, 'list' => $list], 'parkBetter/getA11TmpCarPay', true);
        return $list;
    }
    
    /**
     * 计算临时车费用.
     */
    public function getTmpCarPay($data, $inParkRecord = [], $park_config = [], $carInfo = [])
    {
        fdump_api(['msg' => '计算临时车费用', 'line' => __LINE__, 'data' => $data, 'inParkRecord' => $inParkRecord, 'park_config' => $park_config, 'carInfo' => $carInfo], 'parkBetter/getTmpCarPay');
        $car_number = isset($data['car_number']) && $data['car_number'] ? trim($data['car_number']) : '';
        $village_id = isset($data['village_id']) && $data['village_id'] ? intval($data['village_id']) : 0;
        $park_id = isset($data['park_id']) && $data['park_id'] ? intval($data['park_id']) : $village_id;
        $garage_id = isset($data['garage_id']) && $data['garage_id'] ? intval($data['garage_id']) : 0;
        $park_sys_type = isset($data['park_sys_type']) && $data['park_sys_type'] ? trim($data['park_sys_type']) : 'D3';
        $lastTimeLimit = isset($data['lastTimeLimit']) && $data['lastTimeLimit'] ? intval($data['lastTimeLimit']) : 0;
        $nowTime = isset($data['nowTime']) && $data['nowTime'] ? $data['nowTime'] : time();
        $uid = isset($data['uid']) ? intval($data['uid']) : 0;
        $device_number = isset($data['device_number']) && $data['device_number'] ? trim($data['device_number']) : '';
        $park_name = isset($data['park_name']) && $data['park_name'] ? trim($data['park_name']) : '';
        $carHandleGetInfoService = new CarHandleGetInfoService();
        if (empty($park_config)) {
            if (isset($data['garage_id']) && intval($data['garage_id']) > 0) {
                $park_config = $carHandleGetInfoService->getParkConfig($village_id, intval($data['garage_id']));
            } else {
                $park_config = $carHandleGetInfoService->getParkConfig($village_id, $garage_id);
            }
        }
        $in_time = isset($inParkRecord['in_time']) && $inParkRecord['in_time'] ? $inParkRecord['in_time'] : 0;
        $park_time = $nowTime - $in_time;
        fdump_api(['msg' => '配置项+入场信息', 'line' => __LINE__, 'park_config' => $park_config, 'inParkRecord' => $inParkRecord], 'parkBetter/getTmpCarPay', true);
        if (empty($inParkRecord) || intval($park_time) <= 0) {
            $not_inPark_money = isset($park_config['not_inPark_money']) ? intval($park_config['not_inPark_money']) : 0;
            $order_id = '330' . date('YmdHis') . rand(100, 999);
            if ($not_inPark_money > 0) {
                $data_temp = [
                    'village_id' => $village_id, 'add_time' => $nowTime, 'free_out_time' => 0, 'duration' => 0,
                    'derate_money' => 0, 'derate_duration' => 0, 'order_id' => $order_id, 'query_order_no' => build_real_orderid($uid),
                    'errmsg' => '', 'price' => $not_inPark_money, 'total' => $not_inPark_money, 'in_time' => 0,
                    'car_number' => $car_number, 'out_channel_id' => $device_number, 'is_pay_scene' => 1, 'park_sys_type' => $park_sys_type,
                ];
                if (isset($data['in_record_id']) && $data['in_record_id']) {
                    $data_temp['in_record_id'] = $data['in_record_id'];
                }
                fdump_api(['msg' => '缺少入场记录-直接反馈[不在场车辆放行需交费用]', 'line' => __LINE__, 'data_temp' => $data_temp], 'parkBetter/getA11TmpCarPay', true);
                // todo 记录对应临时表中
                $tempInfo = $this->recordCarFlowTemp($data_temp);
                $flow_temp_id = isset($tempInfo['flow_temp_id']) ? intval($tempInfo['flow_temp_id']) : 0;
                $list = [
                    'time' => '--', 'park_time' => 0, 'pay_money' => $not_inPark_money, 'order_id' => $order_id,
                    'park_name' => $park_name, 'in_time' => '--', 'pay_time' => 0, 'total_money' => $not_inPark_money,
                    'coupon_money' => 0, 'coupon_id' => 0, 'parkTemp_money' => 0, 'park_temp_id' => 0, 'flow_temp_id' => $flow_temp_id,
                ];
                fdump_api(['msg' => '缺少入场记录-直接反馈[不在场车辆放行需交费用]', 'line' => __LINE__, 'list' => $list], 'parkBetter/getA11TmpCarPay', true);
                return $list;
            } else {
                $list = [
                    'time' => '--', 'park_time' => 0, 'pay_money' => 0, 'order_id' => $order_id,
                    'park_name' => $park_name, 'in_time' => '--', 'pay_time' => 0, 'total_money' => 0,
                    'coupon_money' => 0, 'coupon_id' => 0, 'parkTemp_money' => 0, 'park_temp_id' => 0,
                ];
                fdump_api(['msg' => '缺少入场记录-无需付费直接出场', 'line' => __LINE__, 'list' => $list], 'parkBetter/getA11TmpCarPay', true);
                return $list;
            }
        }
        $order_id = isset($inParkRecord['order_id']) && $inParkRecord['order_id'] ? $inParkRecord['order_id'] : '';
        $is_first = isset($inParkRecord['is_first']) && $inParkRecord['is_first'] ? $inParkRecord['is_first'] : 0;
        $park_name = isset($inParkRecord['park_name']) && $inParkRecord['park_name'] ? $inParkRecord['park_name'] : '';
        $end_time = isset($carInfo['end_time']) && $carInfo['end_time'] ? $carInfo['end_time'] : 0;
        if ($lastTimeLimit && $lastTimeLimit > $inParkRecord['in_time'] && $lastTimeLimit < $nowTime) {
            // 如果车辆上一次的 出入记录时间界限大于 当前入场时间 并小于 当前时间 替换入场入场时间
            $in_time = $lastTimeLimit;
        }
        // todo 处理停留时长
        if (!empty($carInfo) && intval($end_time) > 1 && intval($in_time) < intval($end_time)) {
            // 如果入场
            $park_time = $nowTime - $carInfo['end_time'];
        } else {
            $park_time = $nowTime - $inParkRecord['in_time'];
        }
        if ($park_time <= 0) {
            $park_time = 0;
        }
        fdump_api(['msg' => '车辆停留时长', 'line' => __LINE__, 'park_time' => $park_time], 'parkBetter/getTmpCarPay', true);
        $db_house_new_charge_rule = new HouseNewChargeRule();
        $where_rule = [
            ['village_id', '=', $village_id], ['status', '=', 1],
            ['charge_valid_time', '<=', $nowTime], ['fees_type', '=', 3],
        ];
        $rule_info = $db_house_new_charge_rule->getOne($where_rule, 'id, park_charge_id', 'charge_valid_time DESC');
        if ($rule_info && !is_array($rule_info)) {
            $rule_info = $rule_info->toArray();
        }
        if (empty($rule_info)) {
            throw new Exception('该停车场未绑定收费规则');
        }
        $park_charge_id = isset($rule_info['park_charge_id']) && $rule_info['park_charge_id'] ? $rule_info['park_charge_id'] : 0;
        $db_house_village_park_charge = new HouseVillageParkCharge();
        $where_charge = [
            ['village_id', '=', $village_id], ['status', '=', 1], ['id', '=', $park_charge_id],
        ];
        $park_charge = $db_house_village_park_charge->getFind($where_charge);
        if ($park_charge && !is_array($park_charge)) {
            $park_charge = $park_charge->toArray();
        }
        if (empty($park_charge)) {
            throw new Exception('该停车场未绑定收费规则');
        }
        $park_charge_info = isset($park_charge['charge_set']) ? unserialize($park_charge['charge_set']) : [];
        if (!$park_charge_info || empty($park_charge_info)) {
            throw new Exception('费用设置数据错误');
        }
        fdump_api(['msg' => '停车场收费规则', 'line' => __LINE__, 'carInfo' => $carInfo,'park_charge' => $park_charge, 'park_charge_info' => $park_charge_info], 'parkBetter/getTmpCarPay', true);

        $parkTemp = $this->getCarFlowTemp($car_number, $village_id);
        // 整理缴费后免停时长 
        $free_park_time = isset($park_config['free_park_time']) ? intval($park_config['free_park_time']) * 60 : 0;
        $free_park_time <= 0 && $free_park_time = $this->default_free_park_time;

        $is_advance_pay = 0;  //付过了，识别比心跳过来的快
        // 计算用停留时长
        $computeParkTime = $park_time;
        $temp_pay_time = isset($parkTemp['pay_time']) ? intval($parkTemp['pay_time']) : 0;
        $temp_order_id = isset($parkTemp['order_id']) ? trim($parkTemp['order_id']) : '';
        fdump_api(['msg' => '车辆收费中间临时表', 'line' => __LINE__, 'parkTemp' => $parkTemp], 'parkBetter/getTmpCarPay', true);
        if ($temp_pay_time > 0 && $temp_pay_time > $in_time) {
            $parkTempMoney = $parkTemp['price'];
            $next_last_park_time = $nowTime - $temp_pay_time;
            if ($next_last_park_time < $this->sign_advance_pay_time && $temp_order_id == $order_id) {
                //10分钟中内相同 入场记录如果有 支付记录 记下钱
                $is_advance_pay = 1;
            }
            if ($nowTime - $temp_pay_time < $free_park_time) {
                // 如果当前识别时间 减去 上一次支付时间 小于缴费后停留时长 计算停留时长置零
                $computeParkTime = 0;
            }
        } else {
            $parkTempMoney = 0;
        }
        if ($park_charge['charge_type'] == 1) {
            $last_ages = array_column($park_charge_info, 'time');
            array_multisort($last_ages, SORT_DESC, $park_charge_info);
            fdump_api(['msg' => '车辆收费中间临时表', 'line' => __LINE__, 'park_charge_info' => $park_charge_info, 'last_ages' => $last_ages, 'park_time' => $park_time], 'parkBetter/getTmpCarPay', true);
        } elseif ($park_charge['charge_type'] == 4) {
            //24小时逐时收费制
            fdump_api(['msg' => '车辆收费中间临时表', 'line' => __LINE__, 'park_charge_info' => $park_charge_info, 'park_time' => $park_time], 'parkBetter/getTmpCarPay', true);
            if (empty($park_charge_info)) {
                fdump_api(['err' => '费用设置数据错误', 'line' => __LINE__, 'park_charge' => $park_charge, 'park_time' => $park_time], 'parkBetter/getTmpCarPay', true);
                throw new \Exception('费用设置数据错误！');
            }
        }
        // 将设置的时间对应金额 以时间倒叙排序重新整理为数组
        $park_money = 0;
        $pay_money = 0;
        $coupon_id = 0;
        $free_time = 0;
        $park_coupon_money = 0;
        $derate_duration = 0;
        fdump_api(['msg' => '计算用停留时长', 'line' => __LINE__, 'computeParkTime' => $computeParkTime, 'park_charge_info' => $park_charge_info], 'parkBetter/getTmpCarPay', true);
        if ($computeParkTime > 0) {
            // 转分钟
            $computeParkTimeToMinute = ceil($computeParkTime / 60);
            $free_time = isset($park_charge['free_time']) ? intval($park_charge['free_time']) : 0;
            $first_free_time = isset($park_charge['first_free_time']) ? intval($park_charge['first_free_time']) : 0;
            $first_charge_money = isset($park_charge['first_charge_money']) ? floatval($park_charge['first_charge_money']) : 0;
            $charge_type = isset($park_charge['charge_type']) ? intval($park_charge['charge_type']) : 0;
            $max_charge_money = isset($park_charge['max_charge_money']) ? floatval($park_charge['max_charge_money']) : 0;
            // 步长（单位：分钟）
            $charge_time = isset($park_charge['charge_time']) ? intval($park_charge['charge_time']) : 0;
            // 步长对应的收费金额
            $charge_money = isset($park_charge['charge_money']) ? floatval($park_charge['charge_money']) : 0;
            if ($computeParkTimeToMinute <= $free_time) {
                // 停留分钟小于 免停时长  费用0
                $pay_money = 0;
                $derate_duration = $computeParkTimeToMinute;
            } elseif ($is_first == 1 && $first_free_time && $computeParkTimeToMinute <= $first_free_time) {
                // 首次入场停车 停留时间符合首停时长  直接使用首停金额
                $pay_money = $first_charge_money;
                $derate_duration = $computeParkTimeToMinute;
            } else {
                // $charge_type 1-24小时计算步长 2-单次只收一次费用 3-零点前只收一次费用 4-24小时逐时收费 5-按次收费
                if ($charge_type == 1) {
                    // 1-24小时计算步长
                    $computeParkTimeToHour = ceil($computeParkTime / 3600);// 停留时间转为小时 向上取整
                    if ($max_charge_money > 0 && $computeParkTimeToHour > 24) {
                        // 每日封顶金额大于0 且 停留时长超过1天
                        // 转换停留时长为天数 取整数部分
                        $days = intval($computeParkTime / 86400);
                        // 停留金额 = 天数 * 封顶金额
                        $park_money = $days * $max_charge_money;
                        // 剩余停留计算时间 = 停留时长（秒） - 天数*每天秒数
                        $computeParkTime = $computeParkTime - $days * 86400;
                        // 剩余小时数 = 停留小时数 - 天数*24
                        $computeParkTimeToHour = $computeParkTimeToHour - 24 * $days;
                    }
                    $park_charge_info_max = isset($park_charge_info[0]) ? $park_charge_info[0] : [];
                    if (isset($park_charge_info_max['time']) && intval($park_charge_info_max['time']) < $computeParkTimeToHour) {
                        // 如果 费用设置 最大时长 小于 停留时长（小时）
                        // 金额直接赋予
                        $pay_money = $park_charge_info_max['money'];
                        // 去除 费用设置 最大时长（秒）获取 剩余计算用停留时长（秒）
                        $computeParkTime1 = $computeParkTime - $park_charge_info_max['time'] * 3600;
                        if ($max_charge_money > 0) {
                            // 存在每日封顶金额 取剩余计算停留时长/（除以）步长 【向上取整】
                            $computeParkTime2 = ceil($computeParkTime1 / ($charge_time * 60));
                            // 计算 步长 * 步长金额
                            $park_money1 = $computeParkTime2 * $charge_money;
                            if ($computeParkTimeToHour > 24 && ($pay_money + $park_money1) > $max_charge_money) {
                                // 剩余计算用停留时长（小时）大于24小时 累加 每日封顶金额
                                $park_money += $max_charge_money;
                            } elseif ($computeParkTimeToHour <= 24 && ($pay_money + $park_money1) > $max_charge_money) {
                                // 计算后金额 大于 每日封顶金额 取每日封顶金额
                                $pay_money = $max_charge_money;
                            } else {
                                $park_money += $park_money1;
                            }
                        } else {
                            // 取剩余计算停留时长/（除以）步长 【向上取整】
                            $computeParkTime2 = ceil($computeParkTime1 / ($charge_time * 60));
                            // 计算 步长 * 步长金额
                            $park_money1 = $computeParkTime2 * $charge_money;
                            // 金额进行累加下
                            $park_money += $park_money1;
                        }
                    } else {
                        foreach ($park_charge_info as $k => $v) {
                            if ($v['time'] == $computeParkTimeToHour) {
                                // 剩余计算用停留时长（小时） = 对应设置时间 金额赋予
                                $pay_money = $v['money'];
                                break;
                            } elseif ($v['time'] < $computeParkTimeToHour) {
                                // 剩余计算用停留时长（小时） > 对应设置时间 金额赋予 回退一格
                                $pay_money = $park_charge_info[$k - 1]['money'];
                                break;
                            } elseif (!isset($park_charge_info[$k + 1]) && $v['time'] > $computeParkTimeToHour) {
                                // 剩余计算用停留时长（小时） < 对应设置时间 且没有了下一个设置  取当前金额
                                $pay_money = $park_charge_info[$k]['money'];
                                break;
                            }
                        }
                    }
                } elseif ($charge_type == 2) {
                    // 2-单次只收一次费用 计算天数（向上取整）
                    $parkTime_charge2 = ceil($computeParkTime / 86400);
                    // 金额为天数 * 每日封顶金额（单次费用）
                    $pay_money = $max_charge_money * $parkTime_charge2;
                } else {
                    // 3-零点前只收一次费用
                    $parkTime_charge3 = $in_time + $computeParkTime;
                    $time_charge = strtotime(date('Y_m-d 00:00:00', $in_time)) + 86400;
                    if ($time_charge < $parkTime_charge3) {
                        // 入场到出场为不同天 计算天数（向上取整）
                        $parkTime_charge2 = ceil($computeParkTime / 86400);
                        // 金额为天数 * 每日封顶金额（单次费用）
                        $pay_money = $max_charge_money * $parkTime_charge2;
                    } else {
                        // 金额  每日封顶金额（单次费用）
                        $pay_money = $max_charge_money;
                    }
                }
            }
            $pay_money = ($pay_money + $park_money) - $parkTempMoney;
            $total_money = $pay_money;
            fdump_api(['msg' => '费用', 'line' => __LINE__, 'pay_money' => $pay_money, 'park_money' => $park_money, 'parkTempMoney' => $parkTempMoney], 'parkBetter/getTmpCarPay', true);

            if ($pay_money > 0) {
                $this->checkCouponUseStatus($car_number, $village_id, $park_sys_type);
                $coupon_info = $this->getCarFlowCoupon($car_number, $village_id, 0, 0, $park_sys_type);
                fdump_api(['msg' => '优惠券', 'line' => __LINE__, 'coupon_info' => $coupon_info], 'parkBetter/getTmpCarPay', true);
                if (!empty($coupon_info)) {
                    $coupon_id = isset($coupon_info['park_coupon_id']) && $coupon_info['park_coupon_id'] ? $coupon_info['park_coupon_id'] : 0;
                    !$coupon_id && $coupon_id = isset($coupon_info['id']) && $coupon_info['id'] ? $coupon_info['id'] : 0;
                    $pay_money = $pay_money - $coupon_info['money'];
                    if ($pay_money <= 0) {
                        $pay_money = 0;
                    }
                    $park_coupon_money = $coupon_info['money'];
                    // todo 更新优惠券为已使用
                    $this->updateCouponToUsed($car_number, $village_id, $coupon_id, $park_sys_type, $nowTime);
                }
            }
        }
        $data_temp = [];
        $data_temp['village_id'] = $village_id;
        $data_temp['add_time'] = $nowTime;
        $data_temp['free_out_time'] = $free_time;
        $data_temp['duration'] = round_number($park_time / 60, 2);
        $data_temp['derate_money'] = $park_coupon_money;
        $data_temp['derate_duration'] = $derate_duration;
        $data_temp['order_id'] = $order_id;
        $data_temp['query_order_no'] = build_real_orderid($uid);
        $data_temp['errmsg'] = '';
        $data_temp['price'] = $pay_money;
        $data_temp['total'] = $total_money;
        $data_temp['in_time'] = $in_time;
        $data_temp['car_number'] = $car_number;
        $data_temp['out_channel_id'] = $device_number;
        $data_temp['is_pay_scene'] = 1;
        $data_temp['park_sys_type'] = $park_sys_type;
        $data_temp['coupon_id'] = $coupon_id;
        if ($pay_money == 0) {
            $data_temp['is_paid'] = 1;
            $data_temp['pay_time'] = time();
        }
        if (isset($data['in_record_id']) && $data['in_record_id']) {
            $data_temp['in_record_id'] = $data['in_record_id'];
        }
        fdump_api(['msg' => '记录对应临时表中', 'line' => __LINE__, 'data_temp' => $data_temp], 'parkBetter/getTmpCarPay', true);
        // todo 记录对应临时表中
        $tempInfo = $this->recordCarFlowTemp($data_temp);
        $flow_temp_id = isset($tempInfo['flow_temp_id']) ? intval($tempInfo['flow_temp_id']) : 0;

        $time = $this->commonChangeTimeText($park_time);
        $list = [];
        $list['time'] = $time;
        $list['park_time'] = $park_time;
        $list['pay_money'] = $pay_money;
        $list['order_id'] = $order_id;
        $list['park_name'] = $park_name;
        $list['in_time'] = date('Y-m-d H:i:s', $in_time);
        $list['pay_time'] = time();
        $list['total_money'] = $total_money;
        $list['coupon_money'] = $park_coupon_money;
        $list['coupon_id'] = $coupon_id;
        $list['parkTemp_money'] = $parkTempMoney;
        $list['is_advance_pay'] = $is_advance_pay;
        $list['flow_temp_id'] = $flow_temp_id;
        return $list;
    }
    
    /**
     * 记录车辆入场信息.
     */
    public function recordCarFlowInt($parkData, $params = [])
    {
        $printParams = $parkData;
        $printParams = unsetParam($printParams, ['imageFile', 'AlarmInfoPlate']);
        fdump_api(['msg' => '记录车辆入场信息-方法入参', 'parameter' => ['parkData' => $printParams, 'params' => $params], 'line' => __LINE__], 'parkBetter/recordCarFlowIntLog');
        $car_number = isset($params['car_number']) && $params['car_number'] ? $params['car_number'] : '';
        $is_out = isset($params['is_out']) ? intval($params['is_out']) : 0;
        $village_id = isset($params['village_id']) && $params['village_id'] ? $params['village_id'] : 0;
        $garage_id = isset($params['garage_id']) && $params['garage_id'] ? $params['garage_id'] : 0;
        $park_passage_id = isset($params['park_passage_id']) && $params['park_passage_id'] ? $params['park_passage_id'] : 0;
//        $flow_direction = isset($params['flow_direction']) && $params['flow_direction'] ? $params['flow_direction'] : 1;
        $flow_direction = 1;
        $device_number = isset($params['device_number']) && $params['device_number'] ? $params['device_number'] : '';
        $park_sys_type = isset($params['park_sys_type']) && $params['park_sys_type'] ? $params['park_sys_type'] : 'D3';
        $flow_time = isset($params['flow_time']) && $params['flow_time'] ? $params['flow_time'] : time();
        $from_id = isset($params['from_id']) && $params['from_id'] ? $params['from_id'] : 0;
        $uid = isset($params['uid']) && $params['uid'] ? $params['uid'] : 0;
        $total = isset($params['total']) && $params['total'] ? $params['total'] : 0;
        $is_paid = isset($params['is_paid']) ? intval($params['is_paid']) : 0;
        $pay_time = isset($params['pay_time']) ? intval($params['pay_time']) : 0;
        $order_id = isset($params['order_id']) ? trim($params['order_id']) : '';
        $park_name = isset($params['park_name']) ? trim($params['park_name']) : '';

        $position_id = isset($parkData['position_id']) && $parkData['position_id'] ? intval($parkData['position_id']) : 0;
        
        $uniqueArr = [
            'car_number' => $car_number, 'village_id' => $village_id, 'garage_id' => $garage_id, 'park_passage_id' => $park_passage_id,
            'flow_direction' => $flow_direction, 'park_sys_type' => $park_sys_type, 'is_out' => $is_out, 'is_paid' => $is_paid
        ];
        fdump_api(['msg' => '区分唯一记录', 'uniqueArr' => $uniqueArr, 'line' => __LINE__], 'parkBetter/recordCarFlowIntLog', 1);
        $unique_id = md5(json_encode($uniqueArr, JSON_UNESCAPED_UNICODE));
        $whereUnique = [
            'unique_id' => $unique_id,
        ];
        $db_house_village_car_flow_record = new HouseVillageCarFlowRecord();
        $flowRecord = $db_house_village_car_flow_record->getOne($whereUnique, 'id, from_id');
        $flowData = [
            'village_id' => $village_id, 'garage_id' => $garage_id, 'park_passage_id' => $park_passage_id,
            'flow_direction' => $flow_direction, 'device_number' => $device_number, 'park_sys_type' => $park_sys_type,
            'car_number' => $car_number, 'flow_time' => $flow_time, 'unique_id' => $unique_id, 'order_id' => $order_id,
            'is_out' => $is_out, 'uid' => $uid, 'park_name' => $park_name, 'total' => $total, 'is_paid' => $is_paid, 'pay_time' => $pay_time,
        ];
        $position_id && $flowData['position_id'] = $position_id;
        if (isset($flowRecord['id']) && $flowRecord['id']) {
            fdump_api(['msg' => '更新记录', 'whereUnique' => $whereUnique, 'id' => $flowRecord['id'], 'flowData' => $flowData, 'line' => __LINE__], 'parkBetter/recordCarFlowIntLog', 1);
            if (isset($flowRecord['from_id']) && !$flowRecord['from_id'] && $from_id > 0) {
                $flowData['from_id'] = $from_id;
            }
            $flowData['update_time'] = time();
            $db_house_village_car_flow_record->updateThis($whereUnique, $flowData);
            $flow_record_id = $flowRecord['id'];
        } else {
            $from_id > 0 && $flowData['from_id'] = $from_id;
            $flowData['add_time'] = time();
            $flow_record_id = $db_house_village_car_flow_record->add($flowData);
            fdump_api(['msg' => '添加记录', 'flowData' => $flowData, 'line' => __LINE__], 'parkBetter/recordCarFlowIntLog', 1);
        }
        $parkData['flow_record_id'] = $flow_record_id;
        $parkData['unique_id'] = $unique_id;
        $parkData['flow_time'] = $flow_time;
        $parkData['garage_id'] = $garage_id;
        $parkData['park_passage_id'] = $park_passage_id;
        $parkData['flow_direction'] = $flow_direction;
        if ($from_id > 0) {
            // 只记录 house_village_car_access_record
            $parkData['from_id'] = $from_id;
            $parkData['accessType'] = 1;
            $parkData['jobType'] = 'addCarAccessRecord';
        } else {
            // 记录表in_park 和 house_village_car_access_record
            $parkData['isAddAccessRecord'] = 1;
            $parkData['jobType'] = 'addInPark';
        }
        // done-走队列添加
        fdump_api(['msg' => '走队列添加', 'parkData' => $parkData, 'line' => __LINE__], 'parkBetter/recordCarFlowIntLog', 1);
        $job_id = $this->traitCommonCarFlowIn($parkData);
        if (!$job_id && isset($parkData['jobType']) && $parkData['jobType'] == 'addInPark') {
            // 队列没有写入直接执行
            (new CarFlowJobService())->addInPark($parkData);
        }
        if (!$job_id && isset($parkData['jobType']) && $parkData['jobType'] == 'addCarAccessRecord') {
            // 队列没有写入直接执行
            (new CarFlowJobService())->addCarAccessRecord($parkData);
        }
        $parkData['job_id'] = $job_id;
        fdump_api(['msg' => '返回', 'parkData' => $parkData, 'line' => __LINE__], 'parkBetter/recordCarFlowIntLog', 1);
        return $parkData;
    }
    
    /**
     * 检查是否有1分钟内进入车辆记录.
     */
    public function checkCarFlowInt($car_number, $village_id, $garage_id = 0, $nowTime = 0, $park_sys_type = 'D3')
    {
        fdump_api(['msg' => '方法入参', 'parameter' => func_get_args(), 'line' => __LINE__], 'parkBetter/checkCarFlowIntLog');
        !$nowTime && $nowTime = time();
        $startTime = $nowTime - 60;
        $endTime = $nowTime + 30;

        $db_house_village_car_flow_record = new HouseVillageCarFlowRecord();
        $whereRecord = [];
        $whereRecord[] = ['car_number', '=', $car_number];
        $whereRecord[] = ['village_id', '=', $village_id];
        $whereRecord[] = ['park_sys_type', '=', $park_sys_type];
        $whereRecord[] = ['flow_time', '>=', $startTime];
        $whereRecord[] = ['flow_time', '<=', $endTime];
        $whereRecord[] = ['flow_direction', '=', 1];
        $whereRecord[] = ['is_out', '=', 0];
        if ($garage_id > 0) {
            $whereRecord[] = ['garage_id', '=', $garage_id];
        }
        $order = ['flow_time' => 'DESC', 'id' => 'DESC'];
        $flowRecord = $db_house_village_car_flow_record->getOne($whereRecord, 'flow_direction, flow_time, from_id, order_id, position_id', $order);
        if ($flowRecord && !is_array($flowRecord)) {
            $flowRecord = $flowRecord->toArray();
        }
        fdump_api(['msg' => '车辆1分钟范围入场记录', 'flowRecord' => $flowRecord, 'whereRecord' => $whereRecord, 'line' => __LINE__], 'parkBetter/checkCarFlowIntLog', 1);
        $flow_time = isset($flowRecord['flow_time']) && $flowRecord['flow_time'] ? $flowRecord['flow_time'] : 0;
        $from_id = isset($flowRecord['from_id']) && $flowRecord['from_id'] ? $flowRecord['from_id'] : 0;
        $order_id = isset($flowRecord['order_id']) && $flowRecord['order_id'] ? $flowRecord['order_id'] : '';
        $position_id = isset($flowRecord['position_id']) && $flowRecord['position_id'] ? $flowRecord['position_id'] : '';
        if (!empty($flowRecord)) {
            // 存在记录并且 为进入记录
            $data = [
                'id' => $from_id, 'order_id' => $order_id, 'car_number' => $car_number, 'park_id' => $village_id,
                'in_time' => $flow_time, 'park_sys_type' => $park_sys_type, 'position_id' => $position_id,
            ];
            fdump_api(['msg' => '返回结果', 'data' => $data, 'line' => __LINE__], 'parkBetter/checkCarFlowIntLog', 1);
            return $data;
        }
        $whereRecordCount = [];
        $whereRecordCount[] = ['car_number', '=', $car_number];
        $whereRecordCount[] = ['village_id', '=', $village_id];
        $whereRecordCount[] = ['park_sys_type', '=', $park_sys_type];
        $flowRecordCount = $db_house_village_car_flow_record->getCount($whereRecordCount);
        if ($flowRecordCount > 0) {
            // 存在记录 但是不符合60秒内进入记录 返空
            fdump_api(['msg' => '存在记录 但是不符合60秒内进入记录 返空', 'line' => __LINE__], 'parkBetter/checkCarFlowIntLog', 1);
            return [];
        }
        $park_where = [
            ['park_sys_type', '=', $park_sys_type],
            ['car_number', '=', $car_number],
            ['park_id', '=', $village_id],
            ['in_time', '>=', $startTime],
            ['in_time', '<=', $endTime],
            ['del_time', '=', 0],
        ];
        $db_in_park = new InPark();
        $parkInfoCar = $db_in_park->getOne1($park_where);
        if ($parkInfoCar && !is_array($parkInfoCar)) {
            $parkInfoCar = $parkInfoCar->toArray();
        }
        fdump_api(['msg' => '车辆1分钟范围入场记录', 'parkInfoCar' => $parkInfoCar, 'park_where' => $park_where, 'line' => __LINE__], 'parkBetter/checkCarFlowIntLog', 1);
        return $parkInfoCar;
    }
    
    /**
     * 查询对应车辆在对应小区最后一次未出场记录.
     */
    public function getCarFlowInt($car_number, $village_id, $park_sys_type = 'D3')
    {
        fdump_api(['parameter' => func_get_args(), 'line' => __LINE__], 'parkBetter/getCarFlowIntLog');
        $db_house_village_car_flow_record = new HouseVillageCarFlowRecord();
        $whereRecord = [];
        $whereRecord[] = ['car_number', '=', $car_number];
        $whereRecord[] = ['village_id', '=', $village_id];
        $whereRecord[] = ['park_sys_type', '=', $park_sys_type];
        $whereRecord[] = ['is_out', '=', 0];
        $order = ['flow_time' => 'DESC', 'id' => 'DESC'];
        $field = 'id, flow_direction, flow_time, from_id, order_id, is_first, uid, park_name, park_passage_id, garage_id, total, park_time, pay_order_id, position_id';
        $flowRecord = $db_house_village_car_flow_record->getOne($whereRecord, $field, $order);
        if ($flowRecord && !is_array($flowRecord)) {
            $flowRecord = $flowRecord->toArray();
        }
        $flow_record_id = isset($flowRecord['id']) ? intval($flowRecord['id']) : 0;
        $flow_time = isset($flowRecord['flow_time']) ? intval($flowRecord['flow_time']) : 0;
        $from_id = isset($flowRecord['from_id']) ? intval($flowRecord['from_id']) : 0;
        $order_id = isset($flowRecord['order_id']) && $flowRecord['order_id'] ? $flowRecord['order_id'] : '';
        $is_first = isset($flowRecord['is_first']) ? intval($flowRecord['is_first']) : 0;
        $uid = isset($flowRecord['uid']) ? intval($flowRecord['uid']) : 0;
        $park_name = isset($flowRecord['park_name']) && $flowRecord['park_name'] ? trim($flowRecord['park_name']) : '';
        $park_passage_id = isset($flowRecord['park_passage_id']) ? intval($flowRecord['park_passage_id']) : 0;
        $total = isset($flowRecord['total']) && floatval($flowRecord['total']) > 0 ? $flowRecord['total'] : 0;
        $flow_other_time = isset($flowRecord['flow_other_time'])  ? intval($flowRecord['flow_other_time']) : 0;
        $park_time = isset($flowRecord['park_time']) ? intval($flowRecord['park_time']) : 0;
        $flow_direction = isset($flowRecord['flow_direction']) ? intval($flowRecord['flow_direction']) : '';
        $pay_order_id = isset($flowRecord['pay_order_id']) ? intval($flowRecord['pay_order_id']) : 0;
        $position_id = isset($flowRecord['position_id']) && $flowRecord['position_id'] ? $flowRecord['position_id'] : '';
        if (!empty($flowRecord)) {
            // 存在记录并且 为进入记录
            $data = [
                'id' => $from_id, 'order_id' => $order_id, 'car_number' => $car_number, 'park_id' => $village_id, 'in_time' => $flow_time,
                'park_sys_type' => $park_sys_type, 'is_first' => $is_first, 'uid' => $uid, 'park_name' => $park_name, 'pay_order_id' => $pay_order_id,
                'park_passage_id' => $park_passage_id, 'total' => $total, 'flow_record_id' => $flow_record_id, 'park_time' => $park_time,
            ];
            if ($flow_direction == 1) {
                $data['in_time'] = $flow_time;
                $data['out_time'] = $flow_other_time;
            }
            if ($flow_direction == 2) {
                $data['in_time'] = $flow_other_time;
                $data['out_time'] = $flow_time;
            }
            $data['position_id'] = $position_id;
            return $data;
        }
        $whereRecordCount = [];
        $whereRecordCount[] = ['car_number', '=', $car_number];
        $whereRecordCount[] = ['village_id', '=', $village_id];
        $whereRecordCount[] = ['park_sys_type', '=', $park_sys_type];
        $flowRecordCount = $db_house_village_car_flow_record->getCount($whereRecordCount);
        if ($flowRecordCount > 0) {
            // 存在记录 但是没有符合条件的出场记录 返空
            return [];
        }
        $park_where = [
            ['park_sys_type', '=', $park_sys_type],
            ['car_number', '=', $car_number],
            ['park_id', '=', $village_id],
            ['is_out', '=', 0],
            ['del_time', '=', 0],
        ];
        $db_in_park = new InPark();
        $fieldIn = 'id, car_number, out_time, in_time, uid, order_id, in_channel_id, out_channel_id, pay_order_id, is_paid, park_id, is_out, park_sys_type, park_name, park_time, total, coupon_id';
        $parkInfoCar = $db_in_park->getOne1($park_where, $fieldIn);
        if ($parkInfoCar && !is_array($parkInfoCar)) {
            $parkInfoCar = $parkInfoCar->toArray();
        }
        return $parkInfoCar;
    }
    
    /**
     * 记录车辆出场信息.
     */
    public function recordCarFlowOut($parkData, $params = [])
    {
        $printParams = $parkData;
        $printParams = unsetParam($printParams, ['imageFile', 'AlarmInfoPlate']);
        fdump_api(['msg' => '记录车辆出场信息', 'parkData' => $printParams, 'params' => $params, 'line' => __LINE__], 'parkBetter/recordCarFlowOutLog');
        $car_number = isset($parkData['car_number']) && $parkData['car_number'] ? $parkData['car_number'] : '';
        $village_id = isset($params['village_id']) && $params['village_id'] ? $params['village_id'] : 0;
        $garage_id = isset($params['garage_id']) && $params['garage_id'] ? $params['garage_id'] : 0;
        $park_passage_id = isset($params['park_passage_id']) && $params['park_passage_id'] ? $params['park_passage_id'] : 0;
//        $flow_direction = isset($params['flow_direction']) && $params['flow_direction'] ? $params['flow_direction'] : 2;
        $flow_direction = 2;
        $device_number = isset($params['device_number']) && $params['device_number'] ? $params['device_number'] : '';
        $park_sys_type = isset($params['park_sys_type']) && $params['park_sys_type'] ? $params['park_sys_type'] : 'D3';
        $flow_time = isset($params['flow_time']) && $params['flow_time'] ? $params['flow_time'] : time();
        $from_id = isset($params['from_id']) && $params['from_id'] ? $params['from_id'] : 0;
        $order_id = isset($params['order_id']) && $params['order_id'] ? $params['order_id'] : '';
        $uid = isset($params['uid']) && $params['uid'] ? $params['uid'] : 0;
        $total = isset($parkData['total']) && $parkData['total'] ? $parkData['total'] : 0;
        $operateType = isset($parkData['operateType']) && $parkData['operateType'] ? $parkData['operateType'] : '';

        $is_paid = isset($parkData['is_paid']) ? intval($parkData['is_paid']) : 1;
        $is_out = isset($parkData['is_out']) ? intval($parkData['is_out']) : 1;
        $pay_time = isset($parkData['pay_time']) ? intval($parkData['pay_time']) : 0;
        $is_paid == 1 && !$pay_time && $pay_time = $flow_time;
        $in_time = isset($parkData['in_time']) && $parkData['in_time'] ? $parkData['in_time'] : 0;
        $park_time = isset($parkData['park_time']) && $parkData['park_time'] ? $parkData['park_time'] : 0;
        $pay_order_id = isset($flowRecord['pay_order_id']) ? intval($flowRecord['pay_order_id']) : 0;
        $isOutSubPark = isset($data['isOutSubPark']) ? $data['isOutSubPark'] : '-1';
        $isOutParentPark = isset($data['isOutParentPark']) ? $data['isOutParentPark'] : '-1';
        $notParentORSubPark = isset($data['notParentORSubPark']) ? $data['notParentORSubPark'] : '-1';
        
        $position_id = isset($parkData['position_id']) ? intval($parkData['position_id']) : 0;
        
        $db_house_village_car_flow_record = new HouseVillageCarFlowRecord();
        if ($operateType == 'updateRecord') {
            $flow_record_id = isset($parkData['flow_record_id']) && $parkData['flow_record_id'] ? $parkData['flow_record_id'] : 0;
            $out_park_id = isset($parkData['out_park_id']) && $parkData['out_park_id'] ? $parkData['out_park_id'] : 0;
            if ($flow_record_id > 0) {
                $flow_record_data = [
                    'total' => $total, 'is_paid' => $is_paid, 'pay_time' => $is_paid > 0 ? $pay_time : 0
                ];
                $position_id && $flow_record_data['position_id'] = $position_id;
                $whereUnique = ['id' => $flow_record_id];
                $db_house_village_car_flow_record->updateThis($whereUnique, $flow_record_data);
            }
            if ($out_park_id > 0) {
                $parkData['isUpdateAccessRecord'] = 1;
                $parkData['order_id'] = $order_id;
                $parkData['jobType'] = 'updateOutPark';
            }
            return $parkData;
        }
        $uniqueArr = [
            'car_number' => $car_number, 'village_id' => $village_id, 'garage_id' => $garage_id, 'park_passage_id' => $park_passage_id,
            'flow_direction' => $flow_direction, 'park_sys_type' => $park_sys_type, 'is_out' => $is_out, 'is_paid' => $is_paid
        ];
        $unique_id = md5(json_encode($uniqueArr, JSON_UNESCAPED_UNICODE));
        $whereUnique = [
            'unique_id' => $unique_id,
        ];
        $flowRecord = $db_house_village_car_flow_record->getOne($whereUnique, 'id');
        $flowData = [
            'village_id' => $village_id, 'garage_id' => $garage_id, 'park_passage_id' => $park_passage_id, 'is_out' => $is_out,
            'flow_direction' => $flow_direction, 'device_number' => $device_number, 'park_sys_type' => $park_sys_type,
            'car_number' => $car_number, 'flow_time' => $flow_time, 'order_id' => $order_id, 'total' => $total,
            'unique_id' => $unique_id,
        ];
        $pay_order_id > 0 && $flowData['pay_order_id'] = $pay_order_id;
        $park_time && $flowData['park_time'] = $park_time;
        $in_time && $flowData['flow_other_time'] = $in_time;
        $is_paid && $flowData['is_paid'] = $is_paid;
        $pay_time && $flowData['pay_time'] = $pay_time;
        $position_id && $flowData['position_id'] = $position_id;
        
        if (isset($flowRecord['id']) && $flowRecord['id']) {
            $flowData['update_time'] = time();
            $db_house_village_car_flow_record->updateThis($whereUnique, $flowData);
            $flow_record_id = $flowRecord['id'];
        } else {
            $flowData['add_time'] = time();
            $flow_record_id = $db_house_village_car_flow_record->add($flowData);
        }
        // 处理出场对应入场 记录相关消息
        $whereInOther = [];
        $whereInOther[] = ['flow_direction', '=', 1];
        $relatedInFlowData = [
            'is_out' => 1,
            'total' => $total,
            'update_time' => time()
        ];
        $park_time && $relatedInFlowData['park_time'] = $park_time;
        $is_paid && $relatedInFlowData['is_paid'] = $is_paid;
        $pay_time && $relatedInFlowData['pay_time'] = $pay_time;
        $pay_order_id > 0 && $relatedInFlowData['pay_order_id'] = $pay_order_id;
        if ($isOutSubPark && $isOutSubPark != '-1') {
            $whereChannel = [];
            $whereChannel[] = ['village_id', '=', $village_id];
            $whereChannel[] = ['area_type', '=', 2];
            $whereChannel[] = ['passage_area|garage_id', '=', $garage_id];
            $db_park_passage = new ParkPassage();
            $in_channel_id_arr = $db_park_passage->getColumn($whereChannel, 'id');
            $whereInOther[] = ['car_number', '=', $car_number];
            $whereInOther[] = ['park_passage_id', 'in', $in_channel_id_arr];
            $whereInOther[] = ['village_id', '=', $village_id];
            $whereInOther[] = ['park_sys_type', '=', $park_sys_type];
            $whereInOther[] = ['is_out', '=', 0];
            $whereInOther[] = ['flow_direction', '=', 1];
        } else {
            $whereInOther[] = ['car_number', '=', $car_number];
            $whereInOther[] = ['village_id', '=', $village_id];
            $whereInOther[] = ['park_sys_type', '=', $park_sys_type];
            $whereInOther[] = ['is_out', '=', 0];
        }
        $db_house_village_car_flow_record->updateThis($whereInOther, $relatedInFlowData);

        $parkData['flow_record_id'] = $flow_record_id;
        $parkData['unique_id'] = $unique_id;
        $parkData['flow_time'] = $flow_time;
        $parkData['garage_id'] = $garage_id;
        $parkData['park_passage_id'] = $park_passage_id;
        $parkData['flow_direction'] = $flow_direction;
        if ($from_id > 0) {
            // 只记录 house_village_car_access_record
            $parkData['from_id'] = $from_id;
            $parkData['accessType'] = 2;
            $parkData['order_id'] = $order_id;
            $parkData['jobType'] = 'addCarAccessRecord';
        } else {
            // 记录表out_park 和 house_village_car_access_record
            $parkData['isAddAccessRecord'] = 1;
            $parkData['order_id'] = $order_id;
            $parkData['jobType'] = 'addOutPark';
        }
        // done-走队列添加
        $job_id = $this->traitCommonCarFlowOut($parkData);
        $parkData['job_id'] = $job_id;
        return $parkData;
    }

    /**
     * 查询对应车辆在对应性小区最后一次进出记录.
     */
    public function getLastCarFlowRecord($car_number, $village_id, $park_sys_type = 'D3', $whereRecord = [], $park_where = [])
    {
        fdump_api(['parameter' => func_get_args(), 'line' => __LINE__], 'parkBetter/getLastCarFlowRecordLog');
        $db_house_village_car_flow_record = new HouseVillageCarFlowRecord();
        $whereRecord[] = ['car_number', '=', $car_number];
        $whereRecord[] = ['village_id', '=', $village_id];
        $whereRecord[] = ['park_sys_type', '=', $park_sys_type];
        $order = ['flow_time' => 'DESC', 'id' => 'DESC'];
        $field = 'id, flow_direction, flow_time, from_id, order_id, is_first, uid, park_name, park_passage_id, garage_id, total, park_time, pay_order_id, access_record_id, position_id';
        $flowRecord = $db_house_village_car_flow_record->getOne($whereRecord, $field, $order);
        if ($flowRecord && !is_array($flowRecord)) {
            $flowRecord = $flowRecord->toArray();
        }
        fdump_api(['flowRecord' => $flowRecord,'whereRecord' => $whereRecord, 'line' => __LINE__], 'parkBetter/getLastCarFlowRecordLog', 1);
        $flow_record_id = isset($flowRecord['id']) && $flowRecord['id'] ? intval($flowRecord['id']) : 0;
        $flow_time = isset($flowRecord['flow_time']) && $flowRecord['flow_time'] ? $flowRecord['flow_time'] : 0;
        $from_id = isset($flowRecord['from_id']) && $flowRecord['from_id'] ? $flowRecord['from_id'] : 0;
        $order_id = isset($flowRecord['order_id']) && $flowRecord['order_id'] ? $flowRecord['order_id'] : '';
        $flow_direction = isset($flowRecord['flow_direction']) && $flowRecord['flow_direction'] ? $flowRecord['flow_direction'] : '';
        $is_first = isset($flowRecord['is_first']) && $flowRecord['is_first'] ? $flowRecord['is_first'] : 0;
        $uid = isset($flowRecord['uid']) && $flowRecord['uid'] ? intval($flowRecord['uid']) : 0;
        $park_name = isset($flowRecord['park_name']) && $flowRecord['park_name'] ? intval($flowRecord['park_name']) : '';
        $park_passage_id = isset($flowRecord['park_passage_id']) && $flowRecord['park_passage_id'] ? intval($flowRecord['park_passage_id']) : 0;
        $total = isset($flowRecord['total']) && $flowRecord['total'] ? floatval($flowRecord['total']) : 0;
        $flow_other_time = isset($flowRecord['flow_other_time']) && $flowRecord['flow_other_time'] ? intval($flowRecord['flow_other_time']) : 0;
        $park_time = isset($flowRecord['park_time']) && $flowRecord['park_time'] ? intval($flowRecord['park_time']) : 0;
        $pay_order_id = isset($flowRecord['pay_order_id']) ? intval($flowRecord['pay_order_id']) : 0;
        $access_record_id = isset($flowRecord['access_record_id']) ? intval($flowRecord['access_record_id']) : 0;
        $position_id = isset($flowRecord['position_id']) ? intval($flowRecord['position_id']) : 0;
        if (!empty($flowRecord)) {
            // 存在记录并且 为进入记录
            $data = [
                'id' => $from_id, 'order_id' => $order_id, 'car_number' => $car_number, 'park_id' => $village_id, 'flow_time' => $flow_time,
                'park_sys_type' => $park_sys_type, 'is_first' => $is_first, 'uid' => $uid, 'park_name' => $park_name, 'flow_direction' => $flow_direction,
                'park_passage_id' => $park_passage_id, 'total' => $total, 'flow_record_id' => $flow_record_id, 'park_time' => $park_time, 'pay_order_id' => $pay_order_id,
            ];
            if ($flow_direction == 1) {
                $data['in_time'] = $flow_time;
                $data['out_time'] = $flow_other_time;
            }
            if ($flow_direction == 2) {
                $data['in_time'] = $flow_other_time;
                $data['out_time'] = $flow_time;
            }
            if ($access_record_id) {
                $data['access_record_id'] = $access_record_id;
            }
            $data['position_id'] = $position_id;
            fdump_api(['data' => $data, 'line' => __LINE__], 'parkBetter/getLastCarFlowRecordLog', 1);
            return $data;
        }
        $park_where[] = ['park_sys_type', '=', $park_sys_type];
        $park_where[] = ['car_number', '=', $car_number];
        $park_where[] = ['park_id', '=', $village_id];
        if ($flow_direction > 1) {
            $park_where[] = ['is_out', '=', 1];
        }
        $park_where[] = ['del_time', '=', 0];
        $db_in_park = new InPark();
        $parkInfoCar = $db_in_park->getOne1($park_where);
        if ($parkInfoCar && !is_array($parkInfoCar)) {
            $parkInfoCar = $parkInfoCar->toArray();
        }
        if (!empty($parkInfoCar)) {
            isset($parkInfoCar['out_time']) && intval($parkInfoCar['out_time']) > 0 && $parkInfoCar['flow_direction'] = 2;
            isset($parkInfoCar['in_time']) && intval($parkInfoCar['in_time']) > 0 && $parkInfoCar['flow_direction'] = 1;
        }
        fdump_api(['park_where' => $park_where, 'parkInfoCar' => $parkInfoCar, 'line' => __LINE__], 'parkBetter/getLastCarFlowRecordLog', 1);
        return $parkInfoCar;
    }

    /**
     * 查询对应车辆在对应性小区最后的进出记录.
     */
    public function getLastCarFlowRecordList($car_number, $village_id, $park_sys_type = 'D3', $whereRecord = [], $park_where = [])
    {
        $db_house_village_car_flow_record = new HouseVillageCarFlowRecord();
        $whereRecord[] = ['car_number', '=', $car_number];
        $whereRecord[] = ['village_id', '=', $village_id];
        $whereRecord[] = ['park_sys_type', '=', $park_sys_type];
        $order = ['flow_time' => 'DESC', 'id' => 'DESC'];
        $field = 'id, flow_direction, flow_time, from_id, order_id, is_first, uid, park_name, park_passage_id, garage_id, total, park_time, pay_order_id, position_id';
        $flowRecordList = $db_house_village_car_flow_record->getSome($whereRecord, $field, $order);
        if ($flowRecordList && !is_array($flowRecordList)) {
            $flowRecordList = $flowRecordList->toArray();
        }
        $inRecord = [];
        foreach ($flowRecordList as $flowRecord) {
            $flow_record_id = isset($flowRecord['id']) ? intval($flowRecord['id']) : 0;
            $flow_time = isset($flowRecord['flow_time']) ? intval($flowRecord['flow_time']) : 0;
            $from_id = isset($flowRecord['from_id']) ? intval($flowRecord['from_id']) : 0;
            $order_id = isset($flowRecord['order_id']) && $flowRecord['order_id'] ? $flowRecord['order_id'] : '';
            $flow_direction = isset($flowRecord['flow_direction']) ? intval($flowRecord['flow_direction']) : '';
            $is_first = isset($flowRecord['is_first']) ? intval($flowRecord['is_first']) : 0;
            $uid = isset($flowRecord['uid']) ? intval($flowRecord['uid']) : 0;
            $park_name = isset($flowRecord['park_name']) && $flowRecord['park_name'] ? trim($flowRecord['park_name']) : '';
            $park_passage_id = isset($flowRecord['park_passage_id']) ? intval($flowRecord['park_passage_id']) : 0;
            $total = isset($flowRecord['total']) && floatval($flowRecord['total']) > 0 ? $flowRecord['total'] : 0;
            $flow_other_time = isset($flowRecord['flow_other_time']) ? intval($flowRecord['flow_other_time']) : 0;
            $park_time = isset($flowRecord['park_time']) ? intval($flowRecord['park_time']) : 0;
            $pay_order_id = isset($flowRecord['pay_order_id']) ? intval($flowRecord['pay_order_id']) : 0;
            $position_id = isset($flowRecord['position_id']) ? intval($flowRecord['position_id']) : 0;

            // 存在记录并且 为进入记录
            $data = [
                'id' => $from_id, 'order_id' => $order_id, 'car_number' => $car_number, 'park_id' => $village_id, 'flow_time' => $flow_time,
                'park_sys_type' => $park_sys_type, 'is_first' => $is_first, 'uid' => $uid, 'park_name' => $park_name, 'flow_direction' => $flow_direction,
                'park_passage_id' => $park_passage_id, 'total' => $total, 'flow_record_id' => $flow_record_id, 'park_time' => $park_time, 'pay_order_id' => $pay_order_id,
            ];
            if ($flow_direction == 1) {
                $data['in_time'] = $flow_time;
                $data['out_time'] = $flow_other_time;
            }
            if ($flow_direction == 2) {
                $data['in_time'] = $flow_other_time;
                $data['out_time'] = $flow_time;
            }
            $data['position_id'] = $position_id;
            $inRecord[] = $data;
        }
        if (!empty($inRecord)) {
            return $inRecord;
        }
        $park_where[] = ['park_sys_type', '=', $park_sys_type];
        $park_where[] = ['car_number', '=', $car_number];
        $park_where[] = ['park_id', '=', $village_id];
        if ($flow_direction > 1) {
            $park_where[] = ['is_out', '=', 1];
        }
        $park_where[] = ['del_time', '=', 0];
        $db_in_park = new InPark();
        $parkInfoCarList = $db_in_park->getList($park_where);
        if ($parkInfoCarList && !is_array($parkInfoCarList)) {
            $parkInfoCarList = $parkInfoCarList->toArray();
        }
        foreach ($parkInfoCarList as &$parkInfoCar) {
            isset($parkInfoCar['out_time']) && intval($parkInfoCar['out_time']) > 0 && $parkInfoCar['flow_direction'] = 2;
            isset($parkInfoCar['in_time']) && intval($parkInfoCar['in_time']) > 0 && $parkInfoCar['flow_direction'] = 1;
        }
        return $parkInfoCarList;
    }
    
    /**
     * 获取对应进出最新识别记录.
     */
    public function getFlowIdentifyRecord($village_id,  $param = [], $whereFlow = [], $whereLog = [])
    {
        fdump_api(['parameter' => func_get_args(), 'line' => __LINE__], 'parkBetter/getFlowIdentifyRecordLog');
        $car_number = isset($param['car_number']) ? trim($param['car_number']) : '';
        $garage_id = isset($param['garage_id']) ? intval($param['garage_id']) : 0;
        $flow_direction = isset($param['flow_direction']) ? intval($param['flow_direction']) : 1;
        $park_sys_type = isset($param['park_sys_type']) ? trim($param['park_sys_type']) : 'D3';
        $dbHouseVillageCarFlowIdentify = new HouseVillageCarFlowIdentify();
        if ($car_number) {
            $whereFlow[] = ['car_number', '=', $car_number];
        } else {
            $whereFlow[] = ['car_number', '<>', '_无_'];
        }
        $whereFlow[] = ['village_id', '=', $village_id];
        if ($flow_direction) {
            $whereFlow[] = ['flow_direction', '=', $flow_direction];
        }
        $whereFlow[] = ['park_sys_type', '=', $park_sys_type];
        $garage_id && $whereFlow[] = ['garage_id', '=', $garage_id];
        $order = ['flow_time' => 'DESC', 'id' => 'DESC'];
        $identifyInfo = $dbHouseVillageCarFlowIdentify->getOne($whereFlow, true, $order);
        if ($identifyInfo && !is_array($identifyInfo)) {
            $identifyInfo = $identifyInfo->toArray();
        }
        if ($identifyInfo && isset($identifyInfo['id']) && $identifyInfo['id']) {
            return [
                'channel_id' => $identifyInfo['device_number'], 'car_number' => $identifyInfo['car_number'], 'add_time' => $identifyInfo['flow_time'],
                'village_id' => $identifyInfo['village_id'], 'park_type' => $flow_direction, 'park_sys_type' => $park_sys_type,
            ];
        }
        $db_park_plateresult_log = new ParkPlateresultLog();
        $whereLog=array();
        if ($car_number) {
            $whereLog[] = ['car_number', '=', $car_number];
        } else {
            $whereLog[] = ['car_number', '<>', '_无_'];
        }
        //$whereLog[] = ['village_id', '=', $village_id];
        //$whereLog[] = ['park_sys_type', '=', $park_sys_type];
        if ($flow_direction) {
            $park_type = $flow_direction == 1 ? 1 : 2;
            $whereLog[] = ['park_type', '=', $park_type];
        }
        $limitTop = time() + 100;
        $limitLow = time() - 600;
        if(empty($car_number) && isset($param['out_channel_id']) && $param['out_channel_id']){
            $parkPassageDb=new ParkPassage();
            $whereArr=array('village_id'=>$village_id,'device_number'=>$param['out_channel_id']);
            $parkPassageObj=$parkPassageDb->getFind($whereArr,'id,village_id,passage_name,status,passage_direction,device_number,channel_number,park_sys_type,passage_area,area_type,garage_id');
            if($parkPassageObj && !$parkPassageObj->isEmpty()){
                $whereLog[] = ['channel_id ', '=', $param['out_channel_id']];
            }
        }
        $whereLog[] = ['add_time', '>=', $limitLow];
        $whereLog[] = ['add_time', '<=', $limitTop];
        $resultLog = $db_park_plateresult_log->get_one($whereLog,'*');
        if ($resultLog && !is_array($resultLog)) {
            $resultLog = $resultLog->toArray();
        }
        return $resultLog;
    }
    
    /**
     * 对应通道有效时长范围内最新待查询支付信息.
     */
    public function getParkPayRecord($channel_id, $passage_info = [], $params = [])
    {
        fdump_api(['msg' => '方法入参', 'parameter' => func_get_args(), 'line' => __LINE__], 'parkBetter/getParkPayRecordLog');
        if (empty($channel_id)) {
            return false;
        }
        $db_park_scrcu_record = new ParkScrcuRecord();
        $nowTime = isset($params['nowTime']) && $params['nowTime'] ? $params['nowTime'] : time();
        // 查询5分钟内的订单
        $add_time = $nowTime - CarFlowConst::CAR_FLOW_SCRCU_RECORD_LIMIT_TIME;
        $whereArr = array();
        $whereArr[] = array('channel_id', '=', $channel_id);
        $whereArr[] = array('over', '=', 0);
        $whereArr[] = array('add_time', '>=', $add_time);
        $record_info = $db_park_scrcu_record->get_one($whereArr);
        if ($record_info && !is_array($record_info)) {
            $record_info = $record_info->toArray();
        }
        fdump_api(['msg' => '8分钟内未查询出结果订单', 'record_info' => $record_info, 'whereArr' => $whereArr, 'line' => __LINE__], 'parkBetter/getParkPayRecordLog', 1);
        $id = isset($record_info['id']) && $record_info['id'] ? intval($record_info['id']) : 0;
        $car_number = isset($record_info['car_number']) && $record_info['car_number'] ? trim($record_info['car_number']) : '';
        $order_number = isset($record_info['order_number']) && $record_info['order_number'] ? trim($record_info['order_number']) : '';
        if (empty($record_info) || !$order_number || !$car_number || !$id) {
            return false;
        }
        $db_pay_order_info = new PayOrderInfo();
        $db_plat_order = new PlatOrder();
        $payOrderInfo = $db_pay_order_info->getByOrderNo($order_number);
        if ($payOrderInfo && !is_array($payOrderInfo)) {
            $payOrderInfo = $payOrderInfo->toArray();
        }
        fdump_api(['msg' => '对应公共支付订单信息', 'payOrderInfo' => $payOrderInfo, 'order_number' => $order_number, 'line' => __LINE__], 'parkBetter/getParkPayRecordLog', 1);
        if (empty($payOrderInfo) || !isset($payOrderInfo['business_order_id']) || !$payOrderInfo['business_order_id']) {
            $db_park_scrcu_record->delOne(['id' => $id]);
            return false;
        }
        if ($payOrderInfo['channel'] != 'scrcu') {
            // 非四川农信支付渠道 不做处理
            $db_park_scrcu_record->delOne(['id' => $id]);
            return false;
        }
        $platOrderInfo = $db_plat_order->get_one(['order_id' => $payOrderInfo['business_order_id']]);
        if ($platOrderInfo && !is_array($platOrderInfo)) {
            $platOrderInfo = $platOrderInfo->toArray();
        }
        fdump_api(['msg' => '对应小区中间订单表plat_order', 'platOrderInfo' => $platOrderInfo, 'order_id' => $payOrderInfo['business_order_id'], 'line' => __LINE__], 'parkBetter/getParkPayRecordLog', 1);
        if (!empty($platOrderInfo) && isset($platOrderInfo['paid']) && $platOrderInfo['paid'] == 1) {
            $db_park_scrcu_record->delOne(['id' => $id]);
            return false;
        }
        $pay_order_id = isset($platOrderInfo['business_id']) ? intval($platOrderInfo['business_id']) : 0;
        $pay_time = isset($payOrderInfo['paid_time']) && $payOrderInfo['paid_time'] ? $payOrderInfo['paid_time'] : 0;
        $paid = isset($payOrderInfo['paid']) ? intval($payOrderInfo['paid']) : 0;

        if ($channel_id) {
            $passage_info = $this->getParkPassageInfo($channel_id);
        } else {
            $passage_info = [];
        }
        fdump_api(['msg' => '小区中间订单记录支付状态', 'pay_time' => $pay_time, 'paid' => $paid, 'line' => __LINE__], 'parkBetter/getParkPayRecordLog', 1);
        if ($pay_time > 1 && $paid == 1) {
            // 四川农信支付且已经支付
            $flow_time = $pay_time;
            $park_passage_id = isset($passage_info['id']) && $passage_info['id'] ? intval($passage_info['id']) : 0;
            $village_id = isset($passage_info['village_id']) && $passage_info['village_id'] ? intval($passage_info['village_id']) : 0;
            $park_sys_type = isset($passage_info['park_sys_type']) && $passage_info['park_sys_type'] ? trim($passage_info['park_sys_type']) : 'D3';
            $village_info = $this->getVillageInfo($village_id);
            $village_name = isset($village_info['village_name']) && $village_info['village_name'] ? $village_info['village_name'] : '';
            if (isset($passage_info['garage_id']) && intval($passage_info['garage_id']) > 0) {
                $garage_id = intval($passage_info['garage_id']);
            } elseif (isset($passage_info['area_type']) && isset($passage_info['passage_area']) && intval($passage_info['area_type']) == 2 && intval($passage_info['passage_area']) > 0) {
                $garage_id = intval($passage_info['passage_area']);
            } else {
                $garage_id = 0;
            }
            $park_data = [];
            $park_data['car_number'] = $car_number;
            $park_data['order_id'] = md5(uniqid().$car_number.$nowTime);
            $park_data['in_channel_id'] = $park_passage_id;
            $park_data['park_id'] = $village_id;
            $park_data['village_id'] = $village_id;
            $park_data['park_sys_type'] = $park_sys_type;
            $park_data['park_name'] = $village_name;
            $park_data['channel_number'] = $passage_info['channel_number'];
            $park_data['passage_name'] = $passage_info['passage_name'];
            $park_data['garage_id'] = $garage_id;
            $outParams = [
                'village_id' => $village_id, 'garage_id' => $garage_id, 'park_passage_id' => $park_passage_id,
                'flow_direction' => 2, 'device_number' => $passage_info['device_number'], 'park_sys_type' => $park_sys_type,
                'flow_time' => $nowTime,
            ];
            // 出场
            $park_data['is_out'] = 1;
            $park_data['is_paid'] = 1;
            fdump_api(['msg' => '获取车辆入场信息', 'car_number' => $car_number, 'village_id' => $village_id, 'park_sys_type' => $park_sys_type, 'line' => __LINE__], 'parkBetter/getParkPayRecordLog', 1);
            $in_park_info = $this->getCarFlowInt($car_number, $village_id, $park_sys_type);
            fdump_api(['msg' => '车辆入场信息', 'in_park_info' => $in_park_info, 'line' => __LINE__], 'parkBetter/getParkPayRecordLog', 1);
            if (!empty($in_park_info)) {
                $order_id = $in_park_info['order_id'];
                !$pay_order_id && $pay_order_id = isset($in_park_info['pay_order_id']) && intval($in_park_info['pay_order_id']) > 0 ? intval($in_park_info['pay_order_id']) : 0;
                $park_data['order_id'] = $order_id;
                fdump_api(['msg' => '获取车辆已支付临时信息', 'car_number' => $car_number, 'village_id' => $village_id, 'park_sys_type' => $park_sys_type, 'order_id' => $order_id, 'line' => __LINE__], 'parkBetter/getParkPayRecordLog', 1);
                $parkTemp = $this->getCarFlowTemp($car_number, $village_id, 1, $park_sys_type, false, $order_id);
                fdump_api(['msg' => '车辆已支付临时信息', 'parkTemp' => $parkTemp, 'line' => __LINE__], 'parkBetter/getParkPayRecordLog', 1);
                $in_record_id = isset($parkTemp['in_record_id']) && $parkTemp['in_record_id'] ? $parkTemp['in_record_id'] : 0;
                $is_paid = isset($parkTemp['in_record_id']) && intval($parkTemp['is_pay_scene']) === 0 ? 2 : 1;
                $total = isset($payOrderInfo['pay_money']) && $payOrderInfo['pay_money'] ? $payOrderInfo['pay_money'] : 0;
                !$total && $total = isset($parkTemp['price']) && $parkTemp['price'] ? $parkTemp['price'] : 0;
                $in_time = isset($in_park_info['in_time']) && $in_park_info['in_time'] ? intval($in_park_info['in_time']) : 0;
                !$in_time && $in_time = isset($parkTemp['in_time']) && $parkTemp['in_time'] ? intval($parkTemp['in_time']) : 0;
                // todo 记录出场信息
                $accessMode = 4;
                $in_park_id = isset($in_park_info['id']) && intval($in_park_info['id']) > 0 ? intval($in_park_info['id']) : 0;
                $outParams['order_id'] = $order_id;
                $park_data['accessMode'] = $accessMode;
                $park_data['total'] = $total;
                $park_data['pay_type'] = $this->pay_type[6];
                $park_data['in_time'] = $in_time;
                $park_data['out_time'] = $nowTime;
                $park_data['pay_order_id'] = $pay_order_id;
                $park_data['in_park_id'] = $in_park_id;
                $park_data['is_paid'] = $is_paid;
                $park_data['pay_time'] = $pay_time;
                $park_data['in_record_id'] = $in_record_id;
                fdump_api(['msg' => '记录出场信息', 'park_data' => $park_data, 'outParams' => $outParams,  'id' => $id, 'line' => __LINE__], 'parkBetter/getParkPayRecordLog', 1);
                $this->recordCarFlowOut($park_data, $outParams);
                $db_park_scrcu_record->delOne(['id' => $id]);
                return false;
            } else {
                // todo 没有入场未出场记录时候查询下 最近识别记录 如果时间段 抬杆 时间长不处理
                $recordLogParam = [
                    'car_number' => $car_number,
                    'garage_id' => 0,
                    'flow_direction' => 2,
                    'park_sys_type' => $park_sys_type,
                ];
                fdump_api(['msg' => '查询车辆最新出场识别记录', 'village_id' => $village_id, 'recordLogParam' => $recordLogParam, 'line' => __LINE__], 'parkBetter/getParkPayRecordLog', 1);
                $recordLog = $this->getFlowIdentifyRecord($village_id, $recordLogParam);
                fdump_api(['msg' => '车辆最新出场识别记录', 'recordLog' => $recordLog, 'id' => $id, 'line' => __LINE__], 'parkBetter/getParkPayRecordLog', 1);
                $record_add_time = isset($recordLog['add_time']) && intval($recordLog['add_time']) > 0 ? intval($recordLog['add_time']) : 0;
                $diffTimes = 0; // 差异时间
                if ($record_add_time > $flow_time) {
                    $diffTimes = $record_add_time - $flow_time;
                }
                if ($record_add_time <= $flow_time) {
                    $diffTimes = $flow_time - $record_add_time;
                }
                $db_park_scrcu_record->delOne(['id' => $id]);
                if ($diffTimes <= 120) {
                    // 识别出场时间 与 支付时间差异 2分钟内 支持抬杆
                    return true;
                } else {
                    return false;
                }
            }
        }

        $pay_service = new PayService;
        try {
            fdump_api(['msg' => '获取支付结果', 'order_number' => $order_number, 'line' => __LINE__], 'parkBetter/getParkPayRecordLog', 1);
            $notice = $pay_service->notice($order_number);
            fdump_api(['msg' => '支付结果', 'notice' => $notice, 'line' => __LINE__], 'parkBetter/getParkPayRecordLog', 1);
        } catch (\Exception $e) {
            fdump_api(['msg' => '获取支付结果错误', 'errLine' => $e->getLine(), 'errMsg' => $e->getMessage(), 'errFile' => $e->getFile(), 'errCode' => $e->getCode(), 'line' => __LINE__], 'parkBetter/getParkPayRecordLog', 1);
            return false;
        }
        if (isset($notice['after_pay']) && $notice['after_pay']) {
            $pay_service->afterPay($notice['business'], $notice['business_order_id'], $notice['extra']);
        }
        return false;
    }

    /**
     * 记录临时车辆出场相关费用信息.
     */
    public function recordCarFlowTemp($data_temp, $params = [])
    {
        $car_number = isset($data_temp['car_number']) && $data_temp['car_number'] ? $data_temp['car_number'] : '';
        $village_id = isset($data_temp['village_id']) && $data_temp['village_id'] ? $data_temp['village_id'] : 0;
        $free_out_time = isset($data_temp['free_out_time']) && $data_temp['free_out_time'] ? $data_temp['free_out_time'] : 0;
        $add_time = isset($data_temp['add_time']) && $data_temp['add_time'] ? $data_temp['add_time'] : time();
        $duration = isset($data_temp['duration']) && $data_temp['duration'] ? $data_temp['duration'] : 0;
        $derate_money = isset($data_temp['derate_money']) && $data_temp['derate_money'] ? $data_temp['derate_money'] : 0;
        $derate_duration = isset($data_temp['derate_duration']) && $data_temp['derate_duration'] ? $data_temp['derate_duration'] : 0;
        $order_id = isset($data_temp['order_id']) && $data_temp['order_id'] ? $data_temp['order_id'] : 0;
        $query_order_no = isset($data_temp['query_order_no']) && $data_temp['query_order_no'] ? $data_temp['query_order_no'] : 0;
        $errmsg = isset($data_temp['errmsg']) && $data_temp['errmsg'] ? $data_temp['errmsg'] : '';
        $price = isset($data_temp['price']) && $data_temp['price'] ? $data_temp['price'] : 0;
        $total = isset($data_temp['total']) && $data_temp['total'] ? $data_temp['total'] : 0;
        $in_time = isset($data_temp['in_time']) && $data_temp['in_time'] ? $data_temp['in_time'] : 0;
        $out_channel_id = isset($data_temp['out_channel_id']) && $data_temp['out_channel_id'] ? $data_temp['out_channel_id'] : '';
        $is_pay_scene = isset($data_temp['is_pay_scene']) && $data_temp['is_pay_scene'] ? $data_temp['is_pay_scene'] : 1;
        $park_sys_type = isset($data_temp['park_sys_type']) && $data_temp['park_sys_type'] ? $data_temp['park_sys_type'] : 'D3';
        $coupon_id = isset($data_temp['coupon_id']) && $data_temp['coupon_id'] ? $data_temp['coupon_id'] : 0;
        $is_paid = isset($data_temp['is_paid']) && $data_temp['is_paid'] ? $data_temp['is_paid'] : 0;
        $pay_time = isset($data_temp['pay_time']) && $data_temp['pay_time'] ? $data_temp['pay_time'] : 0;
        $in_record_id = isset($data_temp['in_record_id']) && $data_temp['in_record_id'] ? $data_temp['in_record_id'] : 0;

        $hasCoupon = false;
        if ($coupon_id > 0) {
            $hasCoupon = true;
        }
        $uniqueArr = [
            'car_number' => $car_number, 'village_id' => $village_id, 'is_paid' => $is_paid, 'is_pay_scene' => $is_pay_scene,
            'out_channel_id' => $out_channel_id, 'park_sys_type' => $park_sys_type, 'hasCoupon' => $hasCoupon,
        ];
        $unique_id = md5(json_encode($uniqueArr, JSON_UNESCAPED_UNICODE));
        $whereUnique = [
            'unique_id' => $unique_id,
        ];
        $dbHouseVillageCarFlowTemp = new HouseVillageCarFlowTemp();
        $flowTempInfo = $dbHouseVillageCarFlowTemp->getOne($whereUnique, 'id');
        $flowTempData = [
            'village_id' => $village_id, 'car_number' => $car_number, 'is_paid' => $is_paid, 'pay_time' => $pay_time,
            'park_sys_type' => $park_sys_type, 'coupon_id' => $coupon_id, 'out_channel_id' => $out_channel_id,
            'is_pay_scene' => $is_pay_scene, 'order_id' => $order_id, 'price' => $price, 'query_time' => $add_time,
            'in_record_id' => $in_record_id, 'unique_id' => $unique_id, 'out_record_id' => '', 'is_out' => 0
        ];
        if (isset($flowTempInfo['id']) && $flowTempInfo['id']) {
            $flowTempData['update_time'] = time();
            $dbHouseVillageCarFlowTemp->updateThis($whereUnique, $flowTempData);
            $flow_temp_id = $flowTempInfo['id'];
        } else {
            $flowTempData['add_time'] = time();
            $flow_temp_id = $dbHouseVillageCarFlowTemp->add($flowTempData);
        }

        $data_temp['flow_temp_id'] = $flow_temp_id;
        $data_temp['jobType'] = 'addDataTemp';
        return $this->traitCommonCarFlowTemp($data_temp);
    }

    /**
     * 处理优惠券为已使用.
     */
    public function updateCouponToUsed($car_number, $village_id, $park_coupon_id = 0, $park_sys_type = '', $nowTime = 0)
    {
        !$nowTime && $nowTime = time();
        $dbHouseVillageCarFlowCoupon = new HouseVillageCarFlowCoupon();
        $where_coupon = [];
        $where_coupon['village_id'] = $village_id;
        $where_coupon['car_number'] = $car_number;
        if ($park_sys_type) {
            $where_coupon['park_sys_type'] = $park_sys_type;
        }
        $where_coupon['park_coupon_id'] = $park_coupon_id;
        $saveArr = array('is_use' => 1, 'use_time' => $nowTime, 'update_time' => $nowTime);
        $save = $dbHouseVillageCarFlowCoupon->updateThis($where_coupon, $saveArr);
        // todo 走队列更改优惠券使用记录
        $queueData = [
            'jobType' => 'updateCouponToUsed',
            'village_id' => $village_id,
            'car_number' => $car_number,
            'park_sys_type' => $park_sys_type,
            'id' => $park_coupon_id,
            'use_time' => $nowTime,
        ];
        return $this->traitCommonCarFlowCoupon($queueData);
    }

    /**
     * 处理优惠券，将已使用但是未用于支付的优惠券重置为未使用.
     */
    public function checkCouponUseStatus($car_number, $village_id, $park_sys_type)
    {
        $parkTemp = $this->getCarFlowTemp($car_number, $village_id, 0, $park_sys_type, true);
        fdump_api(['msg' => '获取临时记录中未实际使用的优惠券', 'line' => __LINE__, 'car_number' => $car_number, 'village_id' => $village_id, 'park_sys_type' => $park_sys_type, 'parkTemp' => $parkTemp], 'parkBetter/checkCouponUseStatus');
        if (isset($parkTemp['coupon_id']) && intval($parkTemp['coupon_id']) > 0) {
            // 上述优惠券id 有值标明一定有领用 所以直接处理 如果没有反馈走原来逻辑
            $dbHouseVillageCarFlowCoupon = new HouseVillageCarFlowCoupon();
            $where_coupon = [];
            $where_coupon['village_id'] = $village_id;
            $where_coupon['car_number'] = $car_number;
            if ($park_sys_type) {
                $where_coupon['park_sys_type'] = $park_sys_type;
            }
            $where_coupon['park_coupon_id'] = $parkTemp['coupon_id'];
            $saveArr = array('is_use' => 0, 'use_time' => 0, 'update_time' => time());
            fdump_api(['msg' => '更改优惠券', 'line' => __LINE__, 'where_coupon' => $where_coupon, 'saveArr' => $saveArr], 'parkBetter/checkCouponUseStatus', true);
            $save = $dbHouseVillageCarFlowCoupon->updateThis($where_coupon, $saveArr);
            if ($save !== false) {
                $db_house_village_park_coupon = new HouseVillageParkCoupon();
                $where_coupon = [];
                $where_coupon['village_id'] = $village_id;
                $where_coupon['car_number'] = $car_number;
                if ($park_sys_type) {
                    $where_coupon['park_sys_type'] = $park_sys_type;
                }
                $where_coupon['id'] = $parkTemp['coupon_id'];
                $saveArr = array('is_use' => 0, 'use_time' => 0, 'use_txt' => '');
                fdump_api(['msg' => '更改优惠券', 'line' => __LINE__, 'where_coupon' => $where_coupon, 'saveArr' => $saveArr], 'parkBetter/checkCouponUseStatus', true);
                $db_house_village_park_coupon->saveOne($where_coupon, $saveArr);
            } else {
                // todo 走队列更改优惠券使用记录
                $queueData = [
                    'jobType' => 'updateCouponToNotUsed',
                    'village_id' => $village_id,
                    'car_number' => $car_number,
                    'park_sys_type' => $park_sys_type,
                    'id' => $parkTemp['coupon_id'],
                ];
                $job_id = $this->traitCommonCarFlowCoupon($queueData);
            }
            return $parkTemp['coupon_id'];
        }
        return true;
    }

    /**
     * 获取最新的对应车牌在对应小区的临时记录.
     */
    public function getCarFlowTemp($car_number, $village_id, $is_paid = -1, $park_sys_type = '', $getCoupon = false, $order_id = '', $param = [])
    {
        fdump_api(['parameter' => func_get_args(), 'line' => __LINE__], 'parkBetter/getCarFlowTempLog',1);
        $flow_temp_id = isset($param['flow_temp_id']) ? intval($param['flow_temp_id']) : 0;
        $dbHouseVillageCarFlowTemp = new HouseVillageCarFlowTemp();
        $whereFlow = [];
        if ($flow_temp_id > 0) {
            $whereFlow[] = ['id', '=', $flow_temp_id];
        } else {
            $whereFlow[] = ['car_number', '=', $car_number];
            $whereFlow[] = ['village_id', '=', $village_id];
            if ($is_paid != -1) {
                $whereFlow[] = ['is_paid', '=', $is_paid];
            }
            if ($park_sys_type) {
                $whereFlow[] = ['park_sys_type', '=', $park_sys_type];
            }
            if ($order_id) {
                $whereFlow[] = ['order_id', '=', $order_id];
            }
            if ($getCoupon) {
                $whereFlow[] = ['coupon_id', '>', 0];
            }
            $order = ['add_time' => 'DESC', 'id' => 'DESC'];
        }
        $tempInfo = $dbHouseVillageCarFlowTemp->getOne($whereFlow, true, $order);
        if ($tempInfo && !is_array($tempInfo)) {
            $tempInfo = $tempInfo->toArray();
        }
        fdump_api(['line' => __LINE__,'tempInfo' => $tempInfo,'whereFlow'=>$whereFlow], 'parkBetter/getCarFlowTempLog',1);
        if (isset($tempInfo['id']) && $tempInfo['id']) {
            $flow_temp_id = isset($tempInfo['id']) ? intval($tempInfo['id']) : 0;
            $parking_temp_id = isset($tempInfo['parking_temp_id']) ? intval($tempInfo['parking_temp_id']) : 0;
            $pay_time = isset($tempInfo['pay_time']) ? intval($tempInfo['pay_time']) : 0;
            $price = isset($tempInfo['price']) ? floatval($tempInfo['price']) : 0;
            $order_id = isset($tempInfo['order_id']) ? trim($tempInfo['order_id']) : '';
            $coupon_id = isset($tempInfo['coupon_id']) ? intval($tempInfo['coupon_id']) : 0;
            $in_time = isset($tempInfo['in_time']) ? intval($tempInfo['in_time']) : 0;
            $in_record_id = isset($tempInfo['in_record_id']) ? intval($tempInfo['in_record_id']) : 0;
            $out_record_id = isset($tempInfo['out_record_id']) ? intval($tempInfo['out_record_id']) : 0;
            $tempInfo['flow_temp_id']=$flow_temp_id;
            $tempInfo['id']=$parking_temp_id;
            $tempInfo['pay_time']=$pay_time;
            $tempInfo['price']=$price;
            $tempInfo['order_id']=$order_id;
            $tempInfo['coupon_id']=$coupon_id;
            $tempInfo['in_time']=$in_time;
            $tempInfo['in_record_id']=$in_record_id;
            $tempInfo['out_record_id']=$out_record_id;
            return $tempInfo;
        }
        $db_house_village_park_temp = new HouseVillageParkingTemp();
        $whereTemp = [];
        $whereTemp[] = ['car_number', '=', $car_number];
        $whereTemp[] = ['village_id', '=', $village_id];
        if ($is_paid != -1) {
            $whereTemp[] = ['is_paid', '=', $is_paid];
        }
        if ($park_sys_type) {
            $whereTemp[] = ['park_sys_type', '=', $park_sys_type];
        }
        if ($order_id) {
            $whereTemp[] = ['order_id', '=', $order_id];
        }
        if ($getCoupon) {
            $whereTemp[] = ['coupon_id', '>', 0];
        }
        $tempInfo = $db_house_village_park_temp->getOne($whereTemp, '*', 'pay_time DESC,id DESC');
        if ($tempInfo && !is_array($tempInfo)) {
            $tempInfo = $tempInfo->toArray();
        }
        fdump_api(['line' => __LINE__,'tempInfo' => $tempInfo,'whereFlow'=>$whereTemp], 'parkBetter/getCarFlowTempLog',1);
        return $tempInfo;
    }

    /**
     * 更新进出记录中间表.
     */
    public function updateCarFlowRecord($whereUnique, $flow_record_data) {
        $db_house_village_car_flow_record = new HouseVillageCarFlowRecord();
        return $db_house_village_car_flow_record->updateThis($whereUnique, $flow_record_data);
    }
    
    /**
     * 异常情况下的出场处理.
     */
    public function commonExceptionOutHandle($data, $passage_info, $park_data = [], $village_info = [])
    {
        $car_number = isset($data['car_number']) ? trim($data['car_number']) : '';
        $open_type = isset($data['open_type']) ? intval($data['open_type']) : '';
        $nowTime = isset($data['nowTime']) ? intval($data['nowTime']) : time();
        $price = isset($data['price']) && floatval($data['price']) > 0 ? $data['price'] : 0;
        $park_time = isset($data['park_time']) ? intval($data['park_time']) : 0;
        $park_passage_id = isset($passage_info['id']) && $passage_info['id'] ? intval($passage_info['id']) : 0;
        $village_id = isset($passage_info['village_id']) && $passage_info['village_id'] ? intval($passage_info['village_id']) : 0;
        $park_sys_type = isset($passage_info['park_sys_type']) && $passage_info['park_sys_type'] ? trim($passage_info['park_sys_type']) : 'A11';

        if (empty($village_info)) {
            $village_info = $this->getVillageInfo($village_id);
        }
        $isA11 = false;
        $park_sys_type == 'A11' && $isA11 = true;
        $village_name = isset($village_info['village_name']) && $village_info['village_name'] ? $village_info['village_name'] : '';
        if (isset($passage_info['garage_id']) && intval($passage_info['garage_id']) > 0) {
            $garage_id = intval($passage_info['garage_id']);
        } elseif (isset($passage_info['area_type']) && isset($passage_info['passage_area']) && intval($passage_info['area_type']) == 2 && intval($passage_info['passage_area']) > 0) {
            $garage_id = intval($passage_info['passage_area']);
        } else {
            $garage_id = 0;
        }
        $accessMode = 3;
        /** @var bool isOutSubPark 是否小场（子停车场）出场 */
        $isOutSubPark = false;
        /** @var bool isOutParentPark 是否大场（父停车场）出场 */
        $isOutParentPark = false;
        /** @var bool notParentORSubPark 是否不是大小（父子）场 */
        $notParentORSubPark = true;

        if ($isA11) {
            $passage_garage_id = $garage_id;
            $passage_garage_info = $this->getParkingGarage($passage_garage_id, $village_id);
            if (isset($passage_garage_info['fid']) && intval($passage_garage_info['fid']) > 0) {
                // 通过的车道是 小场（子停车场）出场
                $isOutParentPark = false;
                $isOutSubPark = true;
                $notParentORSubPark = false;
            } elseif ($passage_garage_id > 0) {
                $sub_passage_garage_info = $this->getSubParkingGarage($passage_garage_id, $village_id);
                if (isset($sub_passage_garage_info['garage_id']) && intval($sub_passage_garage_info['garage_id']) > 0) {
                    // 通过的车道是 大场（父停车场）出场
                    $sub_passage_garage_id = intval($sub_passage_garage_info['garage_id']);
                    $isOutParentPark = true;
                    $isOutSubPark = false;
                    $notParentORSubPark = false;
                }
            }
            $park_data['isOutSubPark'] = $isOutSubPark;
            $park_data['isOutParentPark'] = $isOutParentPark;
            $park_data['notParentORSubPark'] = $notParentORSubPark;
        }
        if (empty($park_data)) {
            $park_data = [];
            $park_data['car_number'] = $car_number;
            $park_data['order_id'] = md5(uniqid().$car_number.$nowTime);
            $park_data['in_channel_id'] = $park_passage_id;
            $park_data['park_id'] = $village_id;
            $park_data['village_id'] = $village_id;
            $park_data['park_sys_type'] = $park_sys_type;
            $park_data['park_name'] = $village_name;
            $park_data['channel_number'] = $passage_info['channel_number'];
            $park_data['passage_name'] = $passage_info['passage_name'];
            $park_data['garage_id'] = $garage_id;
            if (isset($data['park_car_type']) && !empty($data['park_car_type'])) {
                $park_data['park_car_type'] = $data['park_car_type'];
            } else {
                $park_data['park_car_type'] = '临时车A';
            }
        }
        $outParams = [
            'village_id' => $village_id, 'garage_id' => $garage_id, 'park_passage_id' => $park_passage_id,
            'flow_direction' => 2, 'device_number' => $passage_info['device_number'], 'park_sys_type' => $park_sys_type,
            'flow_time' => $nowTime,
        ];
        // 出场
        $park_data['is_out'] = 1;
        $park_data['is_paid'] = 1;
        $in_park_info = $this->getCarFlowInt($car_number, $village_id, $park_sys_type);
        $position_id = isset($in_park_info['position_id']) && intval($in_park_info['position_id']) > 0 ? intval($in_park_info['position_id']) : 0;
        if ($position_id > 0) {
            $park_data['position_id'] = $position_id;
            $park_data['isRightPosition'] = 1;
        }
        $park_time > 0 && $park_data['park_time'] = $park_time * 60;
        $price > 0 && $park_data['price'] = $price;
        $price > 0 && $park_data['pay_type'] = $this->pay_type[0];
        if (!empty($in_park_info)) {
            $db_house_new_pay_order = new HouseNewPayOrder();
            $order_id = $in_park_info['order_id'];
            $pay_order_id = isset($in_park_info['pay_order_id']) && intval($in_park_info['pay_order_id']) > 0 ? intval($in_park_info['pay_order_id']) : 0;
            $park_data['order_id'] = $order_id;
            $parkTemp = $this->getCarFlowTemp($car_number, $village_id, 1, $park_sys_type, false, $order_id);
            if ($pay_order_id > 0) {
                $payOrderInfo = $db_house_new_pay_order->get_one(['summary_id' => $pay_order_id, 'is_paid' => 1]);
            } else {
                $payOrderInfo = [];
            }
            $in_record_id = isset($parkTemp['in_record_id']) && $parkTemp['in_record_id'] ? $parkTemp['in_record_id'] : 0;
            $pay_time = isset($parkTemp['pay_time']) && $parkTemp['pay_time'] ? $parkTemp['pay_time'] : 0;
            !$pay_time && $pay_time = isset($payOrderInfo['pay_time']) && $payOrderInfo['pay_time'] ? $payOrderInfo['pay_time'] : $nowTime;
            $is_paid = isset($parkTemp['in_record_id']) && intval($parkTemp['is_pay_scene']) === 0 ? 2 : 1;
            $total = isset($parkTemp['price']) && $parkTemp['price'] ? $parkTemp['price'] : 0;
            $coupon_id = isset($parkTemp['coupon_id']) && $parkTemp['coupon_id'] ? $parkTemp['coupon_id'] : 0;
            !$total && $total = isset($payOrderInfo['pay_money']) && $payOrderInfo['pay_money'] ? $payOrderInfo['pay_money'] : 0;
            $in_time = isset($in_park_info['in_time']) && $in_park_info['in_time'] ? intval($in_park_info['in_time']) : 0;
            !$in_time && $in_time = isset($parkTemp['in_time']) && $parkTemp['in_time'] ? intval($parkTemp['in_time']) : 0;
            // todo 记录出场信息
            $accessMode = 3;
            $in_park_id = isset($in_park_info['id']) && intval($in_park_info['id']) > 0 ? intval($in_park_info['id']) : 0;

            if ($open_type == 2) {
                $accessMode = 4;
            } elseif ($open_type == 3) {
                $accessMode = 7;
                $park_data['exception_type'] = 2;
            }
            $outParams['order_id'] = $order_id;
            $park_data['accessMode'] = $accessMode;
            if ($price <= 0) {
                $park_data['total'] = $total;
                $park_data['pay_type'] = $this->pay_type[6];
            }
            $park_data['in_time'] = $in_time;
            $park_data['out_time'] = $nowTime;
            $park_data['pay_order_id'] = $pay_order_id;
            $park_data['in_park_id'] = $in_park_id;
            $park_data['is_paid'] = $is_paid;
            $park_data['pay_time'] = $pay_time;
            $park_data['in_record_id'] = $in_record_id;
            $park_data['open_type'] = $open_type;
            $coupon_id && $park_data['coupon_id'] = $coupon_id;
            $this->recordCarFlowOut($park_data, $outParams);
        } else {
            // 出场
            $park_data['is_out'] = 1;
            $park_data['out_time'] = $nowTime;
            if ($price <= 0) {
                $park_data['total'] = 0;
                $park_data['pay_type'] = $this->pay_type[6];
            }
            if ($open_type == 2) {
                $accessMode = 4;
            } elseif ($open_type == 3) {
                $accessMode = 7;
                $park_data['exception_type'] = 2;
            }
            $park_data['accessMode'] = $accessMode;
            $park_data['open_type'] = $open_type;
            $this->recordCarFlowOut($park_data, $outParams);
        }
        return true;
    }
    
    public function setParkPassageHeart($serialno, $heartTime = 0,$suffix='')
    {
        !$heartTime && $heartTime = time();
        $cacheKey = 'device_number' . $serialno.$suffix;
        $this->setCacheTag('parkPassageHeart:' . $serialno);
        $this->CacheTagSet($cacheKey, $heartTime);
        return $serialno;
    }

    public function getParkPassageHeart($serialno, $isHandle = 0,$suffix='')
    {
        $cacheKey = 'device_number' . $serialno.$suffix;
        $heartTime = $this->CacheTagGet($cacheKey);
        !$heartTime && $heartTime = 0;
        if ($isHandle == 1) {
            $heartTime = $heartTime > 100 ? date('Y-m-d H:i:s', $heartTime) : '';
        }
        return $heartTime;
    }
}