<?php

namespace app\community\model\service;

use app\community\model\db\HouseVillageParkBlack;
use app\community\model\db\HouseVillageParkingCar;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\service\Park\AiHorseSzymzhService;
use app\community\model\db\HouseVillageParkingGarage;
use app\community\model\db\ParkPassage;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageCarAccessRecord;
use app\traits\ParkPassageHouseTraits;
use think\facade\Cache;
use think\Exception;

class HouseNewAiParkingService
{
    use ParkPassageHouseTraits;

    public $carTypeNoArr = array(
        '3651' => '临时车A', '3650' => '临时车B', '3649' => '临时车C', '3648' => '临时车D',
        '3652' => '月租车A', '3653' => '月租车B', '3654' => '月租车C', '3655' => '月租车D', '3661' => '月租车E',
        '3662' => '月租车F', '3663' => '月租车G', '3664' => '月租车H',
        '3656' => '免费车', '3657' => '储值车', '3658' => '访客车', '3660' => '超时收费',
    );
    public $plateColorArr = array('0' => '未知', '1' => '蓝色', '2' => '黄色', '3' => '白色', '4' => '黑色', '5' => '绿色', '6' => '黄绿色');

    public function __construct()
    {

    }

    public function getCarTypeJudge($carType = '')
    {
        $carType = strval($carType);
        $returnArr = array('car_type' => 'temporaryCar', 'car_type_name' => '临时车');
        $tempCarTypeNoArr = array('3651' => '临时车A', '3650' => '临时车B', '3649' => '临时车C', '3648' => '临时车D', '3656' => '免费车', '3657' => '储值车', '3658' => '访客车', '3660' => '超时收费');
        $monthCarTypeNoArr = array('3652' => '月租车A', '3653' => '月租车B', '3654' => '月租车C', '3655' => '月租车D', '3661' => '月租车E',
            '3662' => '月租车F', '3663' => '月租车G', '3664' => '月租车H');
        if (isset($monthCarTypeNoArr[$carType])) {
            $returnArr['car_type'] = 'monthCar';
            $returnArr['car_type_name'] = $monthCarTypeNoArr[$carType];
        } else if (isset($tempCarTypeNoArr[$carType])) {
            $returnArr['car_type_name'] = $tempCarTypeNoArr[$carType];
        }
        return $returnArr;
    }

    public function handleAihorsePlatformGarage($village_id)
    {
        $aiHorseSzymzhService = new AiHorseSzymzhService($village_id);
        $parkGarages = $aiHorseSzymzhService->getVillageAllParkGarages();
    }

    public function getReturnThirdDataSign($village_id = 0, $data = array())
    {
        $aiHorseSzymzhService = new AiHorseSzymzhService($village_id);
        $signData = $aiHorseSzymzhService->getThirdDataSign($data);
        return $signData;
    }

    public function updateParkingGarageInfo($data = array(), $parkingGarage = array())
    {
        $returnArr = array('code' => '1', 'msg' => '', 'rand' => rand(1, 9) . '.' . date('His') . rand(100, 999), 'sign' => '');
        $village_id = $parkingGarage['village_id'];
        $garage_id = $parkingGarage['garage_id'];
        $houseVillageParkingGarageDb = new HouseVillageParkingGarage();
        $whereArr = array(['garage_id', '=', $garage_id], ['village_id', '=', $village_id]);
        $saveArr = array('position_count' => $data['total_spaces'], 'empty_count' => $data['remainder_spaces']);
        $ret = $houseVillageParkingGarageDb->saveOne($whereArr, $saveArr);
        $returnArr['code'] = '1';
        $returnArr['msg'] = '车场车位数更新成功！';
        $dataSign = $this->getReturnThirdDataSign($village_id, $returnArr);
        $returnArr['sign'] = $dataSign;
        return $returnArr;
    }

    public function updateParkCarBlack($data = array(), $parkingGarage = array())
    {
        $houseVillageParkBlackDb = new HouseVillageParkBlack();
        $returnArr = array('code' => '1', 'msg' => '', 'rand' => rand(1, 9) . '.' . date('His') . rand(100, 999), 'sign' => '');
        if (!isset($data['carNo']) || empty($data['carNo'])) {
            $returnArr['code'] = '0';
            $returnArr['msg'] = '进场停车的车牌为空！';
            $dataSign = $this->getReturnThirdDataSign(0, $returnArr);
            $returnArr['sign'] = $dataSign;
            return $returnArr;
        }
        $village_id = $parkingGarage['village_id'];
        $garage_id = $parkingGarage['garage_id'];
        $car_number = trim($data['carNo']);
        $nowtime = time();
        $whereArr = array(['village_id', '=', $village_id], ['car_number', '=', $car_number]);
        $blackCarObj = $houseVillageParkBlackDb->getFind($whereArr, '*');
        if ($blackCarObj && !$blackCarObj->isEmpty() && $data['status'] < 1) {
            $houseVillageParkBlackDb->del_one(array('id' => $blackCarObj['id']));
        } else if ($data['status'] == 1) {
            $blackCarArr = array('village_id' => $village_id, 'car_number' => $car_number);
            $blackCarArr['user_name'] = isset($data['userName']) && $data['userName'] ? $data['userName'] : '';
            $blackCarArr['phone'] = isset($data['phone']) && $data['phone'] ? $data['phone'] : '';
            $blackCarArr['park_sys_type'] = 'third_ai_park';
            $blackCarArr['remark'] = isset($data['remark']) && $data['remark'] ? $data['remark'] : '';
            $blackCarArr['addTime'] = $nowtime;
            if (isset($data['iusseTime']) && $data['iusseTime']) {
                $blackCarArr['addTime'] = strtotime($data['iusseTime']);
            }
            if (!$blackCarArr['addTime']) {
                $blackCarArr['addTime'] = $nowtime;
            }
            if (isset($data['beginTime']) && $data['beginTime']) {
                $blackCarArr['updateTime'] = strtotime($data['beginTime']);
            }
            if (isset($data['endTime']) && $data['endTime']) {
                $blackCarArr['end_time'] = strtotime($data['endTime']);
            }
            $houseVillageParkBlackDb->addOne($blackCarArr);
        }
        $returnArr['code'] = '1';
        $returnArr['msg'] = '车辆黑名单处理成功！';
        $dataSign = $this->getReturnThirdDataSign($village_id, $returnArr);
        $returnArr['sign'] = $dataSign;
        return $returnArr;
    }

    public function addInParkRecord($postArr = array())
    {
        //进程记录
        $returnArr = array('code' => '1', 'msg' => '', 'rand' => rand(1, 9) . '.' . date('His') . rand(100, 999), 'sign' => '');
        if (empty($postArr) || !isset($postArr['data']) || isset($postArr['data']['key']) || empty($postArr['data']['key'])) {
            $returnArr['code'] = '0';
            $returnArr['msg'] = '进场停车数据错误！';
            $dataSign = $this->getReturnThirdDataSign(0, $returnArr);
            $returnArr['sign'] = $dataSign;
            return $returnArr;
        }
        $xrecord = $postArr['data'];
        if (!isset($xrecord['carNo']) || empty($xrecord['carNo'])) {
            $returnArr['code'] = '0';
            $returnArr['msg'] = '进场停车的车牌为空！';
            $dataSign = $this->getReturnThirdDataSign(0, $returnArr);
            $returnArr['sign'] = $dataSign;
            return $returnArr;
        }
        $parkPassageDb = new ParkPassage();
        $houseVillageParkingGarageDb = new HouseVillageParkingGarage();
        $houseVillageCarAccessRecord = new HouseVillageCarAccessRecord();
        $whereArr = array(['third_park_key', '=', $xrecord['key']], ['status', '=', '1']);
        $parkingGarageObj = $houseVillageParkingGarageDb->getOne($whereArr, '*');
        $village_id = 0;
        $garage_id = 0;
        if ($parkingGarageObj && !$parkingGarageObj->isEmpty()) {
            $village_id = $parkingGarageObj['village_id'];
            $garage_id = $parkingGarageObj['garage_id'];
        }
        if ($village_id < 1) {
            $returnArr['code'] = '0';
            $returnArr['msg'] = '未找到停车小区！';
            $dataSign = $this->getReturnThirdDataSign(0, $returnArr);
            $returnArr['sign'] = $dataSign;
            return $returnArr;
        }
        $whereArr = array(['village_id', '=', $village_id], ['garage_id', '=', $garage_id]);
        $whereArr[] = array('passage_name', '=', $xrecord['gateName']);
        $whereArr[] = array('park_sys_type', '=', 'third_ai_park');
        $passageInfoObj = $parkPassageDb->getFind($whereArr, '*');
        $passageInfo = array();
        if ($passageInfoObj && !$passageInfoObj->isEmpty()) {
            $passageInfo = $passageInfoObj->toArray();
        }
        $nowtime = time();
        $car_number = trim($xrecord['carNo']);
        $carAccessRecord = array('business_id' => $village_id, 'car_number' => $car_number, 'accessType' => 1);
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $province = mb_substr($car_number, 0, 1);
        $car_no = mb_substr($car_number, 1);
        $car_info_obj = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no, 'village_id' => $village_id], '*', 'is_del desc,car_id DESC');
        $car_info = array();
        if ($car_info_obj && !$car_info_obj->isEmpty()) {
            $car_info = $car_info_obj->toArray();
        }
        $houseVillageDb = new HouseVillage();
        $houseVillageObj = $houseVillageDb->getInfo(array('village_id' => $village_id), 'village_id,village_name,property_id');
        $houseVillage = '';
        if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
            $houseVillage = $houseVillageObj->toArray();
        }
        $starttime = $nowtime - 100;
        $endtime = $nowtime + 10;
        $access_record_where = [
            ['car_number', '=', $car_number],
            ['park_id', '=', $village_id],
            ['accessType', '=', 1],
            ['park_sys_type', '=', 'third_ai_park'],
            ['accessTime', '>=', $starttime],
            ['accessTime', '<=', $endtime],
            ['del_time', '<', 1],
        ];
        $access_record_info = $houseVillageCarAccessRecord->getOne($access_record_where);
        $record_id = 0;
        $accessTime = $nowtime;
        if (isset($xrecord['enterTime']) && $xrecord['enterTime']) {
            $accessTime = strtotime($xrecord['enterTime']);
        }
        if (!$accessTime) {
            $accessTime = $nowtime;
        }
        if (empty($access_record_info) || $access_record_info->isEmpty()) {
            $carAccessRecord['accessTime'] = $accessTime;
            $carAccessRecord['accessImage'] = '';
            $carAccessRecord['accessBigImage'] = isset($xrecord['imgUrl']) && $xrecord['imgUrl'] ? $xrecord['imgUrl'] : '';
            if (!empty($passageInfo)) {
                $carAccessRecord['channel_id'] = $passageInfo['id'];
                $carAccessRecord['channel_number'] = $passageInfo['channel_number'];
            }
            $carAccessRecord['channel_name'] = $xrecord['gateName'];
            $carAccessRecord['accessMode'] = 3;
            $carAccessRecord['park_sys_type'] = 'third_ai_park';
            $carAccessRecord['park_id'] = $village_id;
            $carAccessRecord['park_name'] = $houseVillage ? $houseVillage['village_name'] : '';
            $carAccessRecord['car_type'] = 'temporaryCar';
            if (!empty($car_info)) {
                $carAccessRecord['uid'] = $car_info['uid'];
                $carAccessRecord['car_id'] = $car_info['car_id'];
                $carAccessRecord['user_name'] = $car_info['car_user_name'] ? $car_info['car_user_name'] : '';
                $carAccessRecord['user_phone'] = $car_info['car_user_phone'] ? $car_info['car_user_phone'] : '';
                $carAccessRecord['garage_id'] = $garage_id;
                /*
                if ($car_info['end_time'] > $nowtime) {
                    $carAccessRecord['car_type'] = 'monthCar';
                    $carAccessRecord['accessMode'] = 5;
                }
                */
            }
            $car_access_order_id = '330' . date('YmdHis') . rand(100, 999);
            $carAccessRecord['order_id'] = $xrecord['orderNo'] ? $xrecord['orderNo'] : $car_access_order_id;
            $carAccessRecord['trade_no'] = $xrecord['orderNo'] ? $xrecord['orderNo'] : '';
            $carAccessRecord['update_time'] = $nowtime;
            $carAccessRecord['service_name'] = 'third_ai_park_upload';
            $carAccessRecord['carType'] = $xrecord['carType'] ? $xrecord['carType'] : 0;
            $carAccessRecord['optname'] = $xrecord['operatorName'] ? $xrecord['operatorName'] : '';
            $carAccessRecord['flag_status'] = '';
            $carAccessRecord['is_out'] = 0;
            $carTypeJudge = $this->getCarTypeJudge($xrecord['carType']);
            if ($carTypeJudge && isset($carTypeJudge['car_type'])) {
                $carAccessRecord['park_car_type'] = $carTypeJudge['car_type_name'];
                $carAccessRecord['car_type'] = $carTypeJudge['car_type'];
                if ($carTypeJudge['car_type'] == 'monthCar') {
                    $carAccessRecord['accessMode'] = 5;
                    $carAccessRecord['car_type'] = 'monthCar';
                } else if ($xrecord['carType'] == '3657') {
                    //	储值车
                    $carAccessRecord['car_type'] = 'storedCar';
                }

            }
            $xrecord['receive_time'] = date('Y-m-d H:i:s');
            $carAccessRecord['extra_data'] = json_encode($xrecord, JSON_UNESCAPED_UNICODE);
            $record_id = $houseVillageCarAccessRecord->addOne($carAccessRecord);
        } else {
            $record_id = isset($access_record_info['record_id']) ? intval($access_record_info['record_id']) : 0;
        }
        if ($record_id > 0) {
            $access_record_where_near = [
                ['car_number', '=', $car_number],
                ['park_id', '=', $village_id],
                ['accessType', '=', 1],
                ['is_out', '=', 0],
                ['del_time', '<', 1],
                ['record_id', '<', $record_id],
            ];
            $houseVillageCarAccessRecord->saveOne($access_record_where_near, ['is_out' => 1]);
        }
        $returnArr['code'] = '1';
        $returnArr['msg'] = '进场记录处理成功！';
        $dataSign = $this->getReturnThirdDataSign($village_id, $returnArr);
        $returnArr['sign'] = $dataSign;
        return $returnArr;
    }

    public function addOutParkRecord($postArr = array())
    {
        //出场记录
        $returnArr = array('code' => '1', 'msg' => '', 'rand' => rand(1, 9) . '.' . date('His') . rand(100, 999), 'sign' => '');
        if (empty($postArr) || !isset($postArr['data']) || isset($postArr['data']['key']) || empty($postArr['data']['key'])) {
            $returnArr['code'] = '0';
            $returnArr['msg'] = '出场停车数据错误！';
            $dataSign = $this->getReturnThirdDataSign(0, $returnArr);
            $returnArr['sign'] = $dataSign;
            return $returnArr;
        }
        $xrecord = $postArr['data'];
        $parkPassageDb = new ParkPassage();
        $houseVillageParkingGarageDb = new HouseVillageParkingGarage();
        $houseVillageCarAccessRecord = new HouseVillageCarAccessRecord();
        $whereArr = array(['third_park_key', '=', $xrecord['key']], ['status', '=', '1']);
        $parkingGarageObj = $houseVillageParkingGarageDb->getOne($whereArr, '*');
        $village_id = 0;
        $garage_id = 0;
        if ($parkingGarageObj && !$parkingGarageObj->isEmpty()) {
            $village_id = $parkingGarageObj['village_id'];
            $garage_id = $parkingGarageObj['garage_id'];
        }
        if ($village_id < 1) {
            $returnArr['code'] = '0';
            $returnArr['msg'] = '未找到停车小区！';
            $dataSign = $this->getReturnThirdDataSign(0, $returnArr);
            $returnArr['sign'] = $dataSign;
            return $returnArr;
        }
        $whereArr = array(['village_id', '=', $village_id], ['garage_id', '=', $garage_id]);
        $whereArr[] = array('passage_name', '=', $xrecord['gateName']);
        $whereArr[] = array('park_sys_type', '=', 'third_ai_park');
        $passageInfoObj = $parkPassageDb->getFind($whereArr, '*');
        $passageInfo = array();
        if ($passageInfoObj && !$passageInfoObj->isEmpty()) {
            $passageInfo = $passageInfoObj->toArray();
        }
        $nowtime = time();
        $car_number = trim($xrecord['carNo']);
        $carAccessRecord = array('business_id' => $village_id, 'car_number' => $car_number, 'accessType' => 2);
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $province = mb_substr($car_number, 0, 1);
        $car_no = mb_substr($car_number, 1);
        $car_info_obj = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no, 'village_id' => $village_id], '*', 'is_del desc,car_id DESC');
        $car_info = array();
        if ($car_info_obj && !$car_info_obj->isEmpty()) {
            $car_info = $car_info_obj->toArray();
        }
        $houseVillageDb = new HouseVillage();
        $houseVillageObj = $houseVillageDb->getInfo(array('village_id' => $village_id), 'village_id,village_name,property_id');
        $houseVillage = '';
        if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
            $houseVillage = $houseVillageObj->toArray();
        }
        $starttime = $nowtime - 100;
        $endtime = $nowtime + 10;
        $access_record_where = [
            ['car_number', '=', $car_number],
            ['park_id', '=', $village_id],
            ['accessType', '=', 2],
            ['park_sys_type', '=', 'third_ai_park'],
            ['accessTime', '>=', $starttime],
            ['accessTime', '<=', $endtime],
            ['del_time', '<', 1],
        ];
        $access_record_info = $houseVillageCarAccessRecord->getOne($access_record_where);
        $record_id = 0;
        $accessTime = $nowtime;
        if (isset($xrecord['outTime']) && $xrecord['outTime']) {
            $accessTime = strtotime($xrecord['outTime']);
        }
        if (!$accessTime) {
            $accessTime = $nowtime;
        }
        if (empty($access_record_info) || $access_record_info->isEmpty()) {
            $carAccessRecord['accessTime'] = $accessTime;
            $carAccessRecord['accessImage'] = '';
            $carAccessRecord['accessBigImage'] = isset($xrecord['imgUrl']) && $xrecord['imgUrl'] ? $xrecord['imgUrl'] : '';
            if (!empty($passageInfo)) {
                $carAccessRecord['channel_id'] = $passageInfo['id'];
                $carAccessRecord['channel_number'] = $passageInfo['channel_number'];
            }
            $carAccessRecord['channel_name'] = $xrecord['gateName'];
            $carAccessRecord['accessMode'] = 3;
            $carAccessRecord['park_sys_type'] = 'third_ai_park';
            $carAccessRecord['park_id'] = $village_id;
            $carAccessRecord['park_name'] = $houseVillage ? $houseVillage['village_name'] : '';
            $carAccessRecord['car_type'] = 'temporaryCar';
            if (!empty($car_info)) {
                $carAccessRecord['uid'] = $car_info['uid'];
                $carAccessRecord['car_id'] = $car_info['car_id'];
                $carAccessRecord['user_name'] = $car_info['car_user_name'] ? $car_info['car_user_name'] : '';
                $carAccessRecord['user_phone'] = $car_info['car_user_phone'] ? $car_info['car_user_phone'] : '';
                $carAccessRecord['garage_id'] = $garage_id;
                /*
                if ($car_info['end_time'] > $nowtime) {
                    $carAccessRecord['car_type'] = 'monthCar';
                    $carAccessRecord['accessMode'] = 5;
                }
                */
            }
            $carAccessRecord['order_id'] = '';
            $access_record_where_near = [
                ['car_number', '=', $car_number],
                ['park_id', '=', $village_id],
                ['accessType', '=', 1],
                ['is_out', '=', 0],
                ['del_time', '<', 1],
                ['accessTime', '<=', $accessTime],
            ];
            // 通行时间小于出场时间的最近的一条入场时间的记录
            $park_info_car111 = $houseVillageCarAccessRecord->getOne($access_record_where_near);
            if ($park_info_car111 && !$park_info_car111->isEmpty()) {
                $park_info_car111 = $park_info_car111->toArray();
                $car_access_order_id = isset($park_info_car111['order_id']) && $park_info_car111['order_id'] ? $park_info_car111['order_id'] : '';
                $carAccessRecord['order_id'] = $car_access_order_id;
                $carAccessRecord['park_time'] = $nowtime - $park_info_car111['accessTime'];
            } else {
                $carAccessRecord['exception_type'] = 2;
            }
            if (isset($xrecord['exception_type']) && $xrecord['exception_type'] == 2) {
                $carAccessRecord['exception_type'] = 2;
            }
            if (empty($carAccessRecord['order_id']) && !empty($xrecord['orderNo'])) {
                $carAccessRecord['order_id'] = $xrecord['orderNo'];
            }
            $carAccessRecord['totalMoney'] = isset($xrecord['totalAmount']) && $xrecord['totalAmount'] ? $xrecord['totalAmount'] : 0;
            $carAccessRecord['total'] = isset($xrecord['total']) && $xrecord['total'] ? $xrecord['walletPayMoney'] : 0;
            $carAccessRecord['deductionTotal'] = isset($xrecord['couponMoney']) && $xrecord['couponMoney'] ? $xrecord['couponMoney'] : 0;
            $carAccessRecord['pay_type'] = 'free';
            if ($xrecord['total'] > 0) {
                $carAccessRecord['pay_type'] = 'scancode';
            }
            $carAccessRecord['trade_no'] = $xrecord['orderNo'] ? $xrecord['orderNo'] : '';
            $carAccessRecord['update_time'] = $nowtime;
            $carAccessRecord['service_name'] = 'third_ai_park_upload';
            $carAccessRecord['carType'] = $xrecord['carType'] ? $xrecord['carType'] : 0;
            $carAccessRecord['optname'] = $xrecord['operatorName'] ? $xrecord['operatorName'] : '';
            $carAccessRecord['flag_status'] = isset($xrecord['freeReason']) && $xrecord['freeReason'] ? $xrecord['freeReason'] : '';
            $carAccessRecord['is_out'] = 1;
            $carTypeJudge = $this->getCarTypeJudge($xrecord['carType']);
            if ($carTypeJudge && isset($carTypeJudge['car_type'])) {
                $carAccessRecord['park_car_type'] = $carTypeJudge['car_type_name'];
                $carAccessRecord['car_type'] = $carTypeJudge['car_type'];
                if ($carTypeJudge['car_type'] == 'monthCar') {
                    $carAccessRecord['accessMode'] = 5;
                    $carAccessRecord['car_type'] = 'monthCar';
                } else if ($xrecord['carType'] == '3657') {
                    //	储值车
                    $carAccessRecord['car_type'] = 'storedCar';
                }

            }
            if ($carAccessRecord['accessMode'] == 5) {
                $carAccessRecord['pay_type'] = 'monthuser';
            }
            $xrecord['receive_time'] = date('Y-m-d H:i:s');
            $carAccessRecord['extra_data'] = json_encode($xrecord, JSON_UNESCAPED_UNICODE);
            $record_id = $houseVillageCarAccessRecord->addOne($carAccessRecord);
        } else {
            $record_id = isset($access_record_info['record_id']) ? intval($access_record_info['record_id']) : 0;
        }
        if ($record_id > 0) {
            $access_record_where_near = [
                ['car_number', '=', $car_number],
                ['park_id', '=', $village_id],
                ['accessType', '=', 1],
                ['is_out', '=', 0],
                ['del_time', '<', 1],
                ['record_id', '<', $record_id],
            ];
            $houseVillageCarAccessRecord->saveOne($access_record_where_near, ['is_out' => 1]);
        }
        $returnArr['code'] = '1';
        $returnArr['msg'] = '出场记录处理成功！';
        $dataSign = $this->getReturnThirdDataSign($village_id, $returnArr);
        $returnArr['sign'] = $dataSign;
        return $returnArr;
    }

    public function getApiBaseInfo($village_id = 0)
    {
        $aiHorseSzymzhService = new AiHorseSzymzhService($village_id);
        $baseInfo = $aiHorseSzymzhService->getApiBaseInfo();
        return $baseInfo;
    }

    public function handleAihorsePlatformGarageDetail($village_id, $park_key = '', $garage_id = 0)
    {
        $aiHorseSzymzhService = new AiHorseSzymzhService($village_id);
        $houseVillageParkingGarageDb = new HouseVillageParkingGarage();
        if (empty($park_key) && $garage_id > 0) {
            $whereArr = array(['village_id', '=', $village_id], ['status', '=', '1']);
            $parkingGarageObj = $houseVillageParkingGarageDb->getOne($whereArr, '*');
            if ($parkingGarageObj && !$parkingGarageObj->isEmpty()) {
                $parkingGarage = $parkingGarageObj->toArray();
                if ($parkingGarage && $parkingGarage['third_park_key']) {
                    $park_key = $parkingGarage['third_park_key'];
                }
            }
        }
        if (empty($park_key)) {
            throw new \think\Exception("车场唯一编号数据为空！");
        }

        $parkGarage = $aiHorseSzymzhService->getOneParkGarageDetail($park_key);
        if (isset($parkGarage['data']) && !empty($parkGarage['data'])) {
            $parkGarageInfo = array('garage_num' => $parkGarage['data']['parkName']);
            $parkGarageInfo['garage_position'] = $parkGarage['data']['parkAdd'];
            $parkGarageInfo['garage_remark'] = $parkGarage['data']['chargesDesc'] ? $parkGarage['data']['chargesDesc'] : '';
            $parkGarageInfo['position_count'] = $parkGarage['data']['spaceTotal'] ? $parkGarage['data']['spaceTotal'] : 0;
            $parkGarageInfo['position_month_count'] = $parkGarageInfo['position_count'];
            $parkGarageInfo['lx_phone'] = $parkGarage['data']['parkTel'] ? $parkGarage['data']['parkTel'] : '';
            $parkGarageInfo['lx_name'] = $parkGarage['data']['parkLinkman'] ? $parkGarage['data']['parkLinkman'] : '';
            $parkGarageInfo['status'] = 1;
            $parkGarageInfo['lat'] = $parkGarage['data']['parkLatitude'] ? $parkGarage['data']['parkLatitude'] : 0;
            $parkGarageInfo['lng'] = $parkGarage['data']['parkLongitude'] ? $parkGarage['data']['parkLongitude'] : 0;
            $parkGarageInfo['pay_ewm_url'] = $parkGarage['data']['parkQRcodeUrl'] && $parkGarage['data']['parkQRcodeUrl']!='null' ? $parkGarage['data']['parkQRcodeUrl'] : '';
            $parkGarageInfo['third_park_key'] = $park_key;
            if ($garage_id > 0) {
                $whereArr = array(['village_id', '=', $village_id], ['garage_id', '=', $garage_id]);
                $parkGarage['data']['update_time'] = date('Y-m-d H:i:s');
                $parkGarageInfo['extra_data'] = json_encode($parkGarage['data'], JSON_UNESCAPED_UNICODE);
                $ret = $houseVillageParkingGarageDb->saveOne($whereArr, $parkGarageInfo);
                return array('village_id' => $village_id, 'park_key' => $park_key, 'garage_id' => $garage_id);
            } else {
                $parkGarageInfo['extra_data'] = json_encode($parkGarage['data'], JSON_UNESCAPED_UNICODE);
                $whereArr = array(['third_park_key', '=', $park_key], ['status', '=', '1']);
                $parkingGarageObj = $houseVillageParkingGarageDb->getOne($whereArr, '*');
                if ($parkingGarageObj && !$parkingGarageObj->isEmpty()) {
                    if ($parkingGarageObj['village_id'] != $village_id) {
                        $err_msg = '此【' . $park_key . '】车场唯一编号已经在别的小区添加过了！';
                    } else {
                        $err_msg = '此【' . $park_key . '】车场唯一编号已经添加过了！';
                    }
                    throw new \think\Exception($err_msg);
                } else {
                    $parkGarageInfo['garage_addtime'] = time();
                    $parkGarageInfo['village_id'] = $village_id;
                    $ret = $houseVillageParkingGarageDb->addOne($parkGarageInfo);
                    return array('village_id' => $village_id, 'park_key' => $park_key, 'garage_id' => $garage_id);
                }
            }

        } else {
            $err_msg = $parkGarage['err_msg'] ? $parkGarage['err_msg'] : '未获取到数据';
            throw new \think\Exception($err_msg);
        }
    }

    public function handleAihorsePfGaragePassageList($village_id, $garage_id = 0, $park_key = '')
    {
        $aiHorseSzymzhService = new AiHorseSzymzhService($village_id);
        $houseVillageParkingGarageDb = new HouseVillageParkingGarage();
        if ($garage_id < 1) {
            throw new \think\Exception("车场ID不存在！");
        }
        if (empty($park_key) && $garage_id > 0) {
            $whereArr = array(['village_id', '=', $village_id], ['status', '=', '1']);
            $parkingGarageObj = $houseVillageParkingGarageDb->getOne($whereArr, '*');
            if ($parkingGarageObj && !$parkingGarageObj->isEmpty()) {
                $parkingGarage = $parkingGarageObj->toArray();
                if ($parkingGarage && $parkingGarage['third_park_key']) {
                    $park_key = $parkingGarage['third_park_key'];
                }
            }
        }
        if (empty($park_key)) {
            throw new \think\Exception("车场唯一编号数据为空！");
        }
        $nowtime = time();
        $parkPassageDb = new ParkPassage();
        $parkPassageList = $aiHorseSzymzhService->getParkGaragePassageList($park_key);
        $add_count = 0;
        $update_count = 0;
        if (isset($parkPassageList['data']) && !empty($parkPassageList['data'])) {
            $passageIds = array();
            foreach ($parkPassageList['data'] as $kk => $pdata) {
                $passage_id = 0;
                $parkPassageInfo = array('passage_name' => $pdata['vehName']);
                $parkPassageInfo['passage_direction'] = $pdata['vehType'] == 1 ? 0 : 1; //vehType车道类型 0:入口 1:出口
                $parkPassageInfo['device_number'] = $pdata['vehCtrlNo'] ? trim($pdata['vehCtrlNo']) : ''; //控制机机号
                $indexId=$kk+1;
                if(isset($pdata['indexId'])){
                    $indexId=$pdata['indexId']+1;
                }
                $parkPassageInfo['channel_number'] = $indexId; //通道号 
                $parkPassageInfo['veh_number'] = $pdata['vehNumber'] ? trim($pdata['vehNumber']) : ''; //车道编号 
                $parkPassageInfo['park_param'] = $pdata['sentryboxNo']; //岗亭编号
                $parkPassageInfo['park_brand'] = 'third_ai_park';
                $parkPassageInfo['park_sys_type'] = 'third_ai_park';
                $parkPassageInfo['device_ip'] = $pdata['vehVideoIp'] ? $pdata['vehVideoIp'] : ''; //摄像机IP
                $parkPassageInfo['mac_address'] = $pdata['vehCtrlIp'] ? $pdata['vehCtrlIp'] : ''; //控制板IP
                $parkPassageInfo['status'] = $pdata['vehStatus'] ? $pdata['vehStatus'] : 0;
                $parkPassageInfo['is_online']= $pdata['vehStatus'] ? $pdata['vehStatus'] : 0; //车道状态 1为在线 0为不在线
                $parkPassageInfo['park_type'] = $park_key;
                $parkPassageInfo['garage_id'] = $garage_id;
                $parkPassageInfo['last_heart_time'] = $nowtime;
                $whereArr = array(['village_id', '=', $village_id], ['garage_id', '=', $garage_id]);
                $whereArr[] = array('device_number', '=', $parkPassageInfo['device_number']);
                $whereArr[] = array('park_sys_type', '=', 'third_ai_park');
                $passageInfo = $parkPassageDb->getFind($whereArr, '*');
                if ($passageInfo && !$passageInfo->isEmpty()) {
                    $pdata['update_time'] = date('Y-m-d H:i:s');
                    $parkPassageInfo['device_setting'] = json_encode($pdata, JSON_UNESCAPED_UNICODE);
                    $ret = $parkPassageDb->save_one($whereArr, $parkPassageInfo);
                    $passageIds[] = $passageInfo['id'];
                    $passage_id = $passageInfo['id'];
                    $update_count++;
                } else {
                    $parkPassageInfo['start_time'] = $nowtime;
                    $parkPassageInfo['village_id'] = $village_id;
                    $insert_id = $parkPassageDb->addOne($parkPassageInfo);
                    $passageIds[] = $insert_id;
                    $passage_id = $insert_id;
                    $add_count++;
                }
                $queueData = array('village_id' => $village_id, 'passage_id' => $passage_id, 'park_key' => $park_key, 'device_number' => $parkPassageInfo['device_number'], 'opt_type' => 'get_third_ai_park_vehicle_status');
                $tmptime = ($kk + 1) * 2;
                $this->parkPassageQueuePushLaterToJob($queueData, $tmptime);
            }
            $delWhere = array(['village_id', '=', $village_id], ['garage_id', '=', $garage_id]);
            $delWhere[] = array('park_sys_type', '=', 'third_ai_park');
            $delWhere[] = array('id', 'not in', $passageIds);
            $del_count = $parkPassageDb->getCount($delWhere);
            $del_count = $del_count > 0 ? $del_count : 0;
            if ($del_count > 0) {
                $parkPassageDb->del_one($delWhere);
            }
            $msg = '';
            if ($add_count > 0) {
                $msg = '新增了 ' . $add_count . '条车道数据';
            }
            if ($update_count > 0) {
                $msg = $msg ? $msg . '，更新了 ' . $update_count . '条车道数据' : '更新了 ' . $update_count . '条车道数据';
            }
            if ($del_count > 0) {
                $msg = $msg ? $msg . '，删除了已不存在的车道数据 ' . $del_count . '条' : '删除了已不存在的车道数据 ' . $del_count . '条';
            }
            if ($msg) {
                $msg = '此次操作，' . $msg;
            } else {
                $msg = '操作成功！';
            }
            return array('village_id' => $village_id, 'park_key' => $park_key, 'garage_id' => $garage_id, 'msg' => $msg, 'add_count' => $add_count, 'update_count' => $update_count, 'del_count' => $del_count);
        } else {
            $err_msg = $parkPassageList['err_msg'] ? $parkPassageList['err_msg'] : '未获取到车道数据';
            throw new \think\Exception($err_msg);
        }
    }

    public function getGaragePassageStatus($passage_id = 0, $extra_data = array())
    {
        if ($passage_id < 1) {
            return true;
        }
        $parkPassageDb = new ParkPassage();
        $whereArr[] = array(['id', '=', $passage_id]);
        if ($extra_data && isset($extra_data['garage_id']) && $extra_data['garage_id']) {
            $whereArr[] = array('garage_id', '=', $extra_data['garage_id']);
        }
        if ($extra_data && isset($extra_data['village_id']) && $extra_data['village_id']) {
            $whereArr[] = array('village_id', '=', $extra_data['village_id']);
        }
        $park_key = '';
        if ($extra_data && isset($extra_data['park_key']) && $extra_data['park_key']) {
            $park_key = $extra_data['park_key'];
        }
        $passageInfo = $parkPassageDb->getFind($whereArr, '*');
        if ($passageInfo && !$passageInfo->isEmpty() && $passageInfo['device_number']) {
            $aiHorseSzymzhService = new AiHorseSzymzhService($passageInfo['village_id']);
            if (empty($park_key) && !empty($passageInfo['park_type'])) {
                $park_key = $passageInfo['park_type'];
            }
            $passageStatus = $aiHorseSzymzhService->getParkGaragePassageStatus($park_key, $passageInfo['device_number']);
            if ($passageStatus && $passageStatus['data'] && isset($passageStatus['data']['vehStatus'])) {
                $whereTmpArr = array('id' => $passageInfo['id']);
                $saveArr = array('is_online' => $passageStatus['data']['vehStatus'], 'last_heart_time' => time());
                $parkPassageDb->save_one($whereTmpArr, $saveArr);
                if (isset($extra_data['is_return_data']) && $extra_data['is_return_data']) {
                    return array('is_online' => $saveArr['is_online']);
                }
            }
        }
        return true;
    }

    public function openHandlePassageGate($village_id, $passage_id = 0, $park_key = '', $garage_id = 0)
    {
        $aiHorseSzymzhService = new AiHorseSzymzhService($village_id);
        $houseVillageParkingGarageDb = new HouseVillageParkingGarage();
        $parkPassageDb = new ParkPassage();
        if ($passage_id < 1) {
            throw new \think\Exception("车道ID不存在！");
        }
        $whereArr = array(['id', '=', $passage_id], ['village_id', '=', $village_id], ['park_sys_type', '=', 'third_ai_park']);
        $parkPassageObj = $parkPassageDb->getFind($whereArr, '*');
        $parkPassage = '';
        if ($parkPassageObj && !$parkPassageObj->isEmpty()) {
            $parkPassage = $parkPassageObj->toArray();
        }
        $vlCtrNo = '';
        if (empty($parkPassage) || !isset($parkPassage['garage_id']) || $parkPassage['garage_id'] < 1) {
            throw new \think\Exception("车道信息错误！");
        }
        $vlCtrNo = $parkPassage['device_number'];
        if (empty($park_key)) {
            $park_key = $parkPassage['park_type'];
        }
        if ($garage_id < 1) {
            $garage_id = $parkPassage['garage_id'];
        }
        if (empty($park_key) && $garage_id > 0) {
            $whereArr = array(['village_id', '=', $village_id], ['status', '=', '1']);
            $parkingGarageObj = $houseVillageParkingGarageDb->getOne($whereArr, '*');
            if ($parkingGarageObj && !$parkingGarageObj->isEmpty()) {
                $parkingGarage = $parkingGarageObj->toArray();
                if ($parkingGarage && $parkingGarage['third_park_key']) {
                    $park_key = $parkingGarage['third_park_key'];
                }
            }
        }
        if (empty($park_key)) {
            throw new \think\Exception("车场唯一编号数据为空！");
        }
        $openGateRet = $aiHorseSzymzhService->openPassageGate($park_key, $vlCtrNo);
        return $openGateRet;
    }

    public function getVillageParkingGarage($park_key = '')
    {
        $houseVillageParkingGarageDb = new HouseVillageParkingGarage();
        $whereArr = array(['third_park_key', '=', $park_key], ['status', '=', '1']);
        $parkingGarageObj = $houseVillageParkingGarageDb->getOne($whereArr, '*');
        $village_id = 0;
        $garage_id = 0;
        $garage_num = '';
        if ($parkingGarageObj && !$parkingGarageObj->isEmpty()) {
            $village_id = $parkingGarageObj['village_id'];
            $garage_id = $parkingGarageObj['garage_id'];
            $garage_num = $parkingGarageObj['garage_num'];
        }
        return array('village_id' => $village_id, 'garage_id' => $garage_id, 'garage_num' => $garage_num, 'third_park_key' => $park_key);
    }

    public function addNewOneCar($data = array(), $parkingGarage = array())
    {
        $returnArr = array('code' => '1', 'msg' => '', 'rand' => rand(1, 9) . '.' . date('His') . rand(100, 999), 'sign' => '');
        $houseVillageParkingCar = new HouseVillageParkingCar();
        $village_id = $parkingGarage['village_id'];
        $garage_id = $parkingGarage['garage_id'];
        $park_key=$parkingGarage['third_park_key'];
        $car_number = isset($data['carNo']) ? $data['carNo'] : '';
        if (empty($car_number)) {
            $returnArr['code'] = '0';
            $returnArr['msg'] = '车牌号不能为空！';
            $dataSign = $this->getReturnThirdDataSign($village_id, $returnArr);
            $returnArr['sign'] = $dataSign;
            return $returnArr;
        }
        $nowtime = time();
        $province = mb_substr($car_number, 0, 1);
        $car_no = mb_substr($car_number, 1);
        $whereArr = array(['village_id', '=', $village_id], ['province', '=', $province], ['car_number', '=', $car_no], ['is_del', '=', 1]);
        $parkingCarObj = $houseVillageParkingCar->getOneCarInfo($whereArr);
        if ($parkingCarObj && !$parkingCarObj->isEmpty()) {
            $updateCar = array();
            $data['receive_time'] = date('Y-m-d H:i:s');
            $updateCar['extra_data'] = json_encode($data, JSON_UNESCAPED_UNICODE);
            $updateCar['add_cfrom'] = 'third_ai_park';
            $updateCar['equipment_no']=$park_key;
            if(isset($data['carSpaceNo']) && $data['carSpaceNo']){
                $updateCar['car_user_no']=$data['carSpaceNo'];
            }
            $res = $houseVillageParkingCar->editCar(array('car_id' => $parkingCarObj['car_id']), $updateCar);
            $returnArr['code'] = '1';
            $returnArr['msg'] = '月租车登记更新成功！';
            $dataSign = $this->getReturnThirdDataSign($village_id, $returnArr);
            $returnArr['sign'] = $dataSign;
            return $returnArr;
        } else {
            $addCar = array('village_id' => $village_id, 'province' => $province, 'car_number' => $car_no);
            $addCar['car_position_id'] = 0;
            $addCar['car_user_no'] = $data['carSpaceNo'] ? $data['carSpaceNo'] : '';
            $addCar['car_user_name'] = $data['userName'] ? $data['userName'] : '';
            $addCar['car_user_phone'] = $data['mobNumber'] ? $data['mobNumber'] : '';
            $addCar['car_addtime'] = $nowtime;
            $addCar['examine_time'] = $nowtime;
            $addCar['examine_status'] = 1;
            $addCar['parking_car_type'] = $data['carType'] ? $data['carType'] : '';
            $addCar['start_time'] = 0;
            if (isset($data['beginTime']) && $data['beginTime']) {
                $addCar['start_time'] = strtotime($data['beginTime']);
            }
            $addCar['end_time'] = 0;
            if (isset($data['endTime']) && $data['endTime']) {
                $addCar['end_time'] = strtotime($data['endTime']);
            }
            $addCar['garage_id'] = $garage_id;
            $addCar['extra_data'] = json_encode($data, JSON_UNESCAPED_UNICODE);
            $addCar['add_cfrom'] = 'third_ai_park';
            $addCar['equipment_no']=$park_key;
            $inser_id = $houseVillageParkingCar->addHouseVillageParkingCar($addCar);
            $returnArr['code'] = '1';
            $returnArr['msg'] = '月租车登记添加成功！';
            $dataSign = $this->getReturnThirdDataSign($village_id, $returnArr);
            $returnArr['sign'] = $dataSign;
            return $returnArr;
        }
    }

    public function cancelCarTime($data = array(), $parkingGarage = array())
    {
        $returnArr = array('code' => '1', 'msg' => '', 'rand' => rand(1, 9) . '.' . date('His') . rand(100, 999), 'sign' => '');
        $houseVillageParkingCar = new HouseVillageParkingCar();
        $village_id = $parkingGarage['village_id'];
        $garage_id = $parkingGarage['garage_id'];
        $car_number = isset($data['carNo']) ? $data['carNo'] : '';
        if (empty($car_number)) {
            $returnArr['code'] = '0';
            $returnArr['msg'] = '车牌号不能为空！';
            $dataSign = $this->getReturnThirdDataSign($village_id, $returnArr);
            $returnArr['sign'] = $dataSign;
            return $returnArr;
        }
        $nowtime = time();
        $province = mb_substr($car_number, 0, 1);
        $car_no = mb_substr($car_number, 1);
        $whereArr = array(['village_id', '=', $village_id], ['province', '=', $province], ['car_number', '=', $car_no], ['is_del', '=', 1]);
        $parkingCarObj = $houseVillageParkingCar->getOneCarInfo($whereArr);
        if ($parkingCarObj && !$parkingCarObj->isEmpty()) {
            $updateCar = array();
            $data['receive_time'] = date('Y-m-d H:i:s');
            $updateCar['start_time'] = 0;
            $updateCar['end_time'] = 0;
            $updateCar['extra_data'] = json_encode($data, JSON_UNESCAPED_UNICODE);
            $res = $houseVillageParkingCar->editCar(array('car_id' => $parkingCarObj['car_id']), $updateCar);
            $returnArr['code'] = '1';
            $returnArr['msg'] = '月租车注销更新成功！';
            $dataSign = $this->getReturnThirdDataSign($village_id, $returnArr);
            $returnArr['sign'] = $dataSign;
            return $returnArr;
        }
        $returnArr['code'] = '0';
        $returnArr['msg'] = '车牌号不存在！';
        $dataSign = $this->getReturnThirdDataSign($village_id, $returnArr);
        $returnArr['sign'] = $dataSign;
        return $returnArr;
    }

    public function updateCarTime($data = array(), $parkingGarage = array())
    {
        $returnArr = array('code' => '1', 'msg' => '', 'rand' => rand(1, 9) . '.' . date('His') . rand(100, 999), 'sign' => '');
        $houseVillageParkingCar = new HouseVillageParkingCar();
        $village_id = $parkingGarage['village_id'];
        $garage_id = $parkingGarage['garage_id'];
        $car_number = isset($data['carNo']) ? $data['carNo'] : '';
        if (empty($car_number)) {
            $returnArr['code'] = '0';
            $returnArr['msg'] = '车牌号不能为空！';
            $dataSign = $this->getReturnThirdDataSign($village_id, $returnArr);
            $returnArr['sign'] = $dataSign;
            return $returnArr;
        }
        $province = mb_substr($car_number, 0, 1);
        $car_no = mb_substr($car_number, 1);
        $whereArr = array(['village_id', '=', $village_id], ['province', '=', $province], ['car_number', '=', $car_no], ['is_del', '=', 1]);
        $parkingCarObj = $houseVillageParkingCar->getOneCarInfo($whereArr);
        if ($parkingCarObj && !$parkingCarObj->isEmpty()) {
            $updateCar = array();
            $data['receive_time'] = date('Y-m-d H:i:s');
            if (isset($data['beginTime']) && $data['beginTime']) {
                $updateCar['start_time'] = strtotime($data['beginTime']);
            }
            if (isset($data['endTime']) && $data['endTime']) {
                $updateCar['end_time'] = strtotime($data['endTime']);
            }
            $updateCar['extra_data'] = json_encode($data, JSON_UNESCAPED_UNICODE);
            $res = $houseVillageParkingCar->editCar(array('car_id' => $parkingCarObj['car_id']), $updateCar);
            $returnArr['code'] = '1';
            $returnArr['msg'] = '月租车车充值延期更新成功！';
            $dataSign = $this->getReturnThirdDataSign($village_id, $returnArr);
            $returnArr['sign'] = $dataSign;
            return $returnArr;
        }
        $returnArr['code'] = '0';
        $returnArr['msg'] = '车牌号不存在！';
        $dataSign = $this->getReturnThirdDataSign($village_id, $returnArr);
        $returnArr['sign'] = $dataSign;
        return $returnArr;
    }

    public function updateCarAccessRecordCar($data = array(), $parkingGarage = array())
    {
        $returnArr = array('code' => '1', 'msg' => '', 'rand' => rand(1, 9) . '.' . date('His') . rand(100, 999), 'sign' => '');
        $village_id = $parkingGarage['village_id'];
        $garage_id = $parkingGarage['garage_id'];
        $car_number = isset($data['carNo']) ? $data['carNo'] : '';
        if (empty($car_number)) {
            $returnArr['code'] = '0';
            $returnArr['msg'] = '车牌号不能为空！';
            $dataSign = $this->getReturnThirdDataSign($village_id, $returnArr);
            $returnArr['sign'] = $dataSign;
            return $returnArr;
        }
        $orderNo = isset($data['orderNo']) ? trim($data['orderNo']) : '';
        if (empty($orderNo)) {
            $returnArr['code'] = '0';
            $returnArr['msg'] = '停车订单号不能为空！';
            $dataSign = $this->getReturnThirdDataSign($village_id, $returnArr);
            $returnArr['sign'] = $dataSign;
            return $returnArr;
        }
        $houseVillageCarAccessRecord = new HouseVillageCarAccessRecord();
        $access_record_where = [
            ['park_id', '=', $village_id],
            ['accessType', '=', 1],
            ['park_sys_type', '=', 'third_ai_park'],
            ['order_id', '=', $orderNo],
            ['garage_id', '=', $garage_id],
            ['del_time', '<', 1],
        ];
        $access_record_info = $houseVillageCarAccessRecord->getOne($access_record_where);
        if ($access_record_info && $access_record_info->isEmpty()) {
            $saveArr = array('car_number' => $car_number);
            $houseVillageCarAccessRecord->saveOne(array('record_id' => $access_record_info['record_id']), $saveArr);
        }
        $returnArr['code'] = '1';
        $returnArr['msg'] = '进场车辆车牌修正成功！';
        $dataSign = $this->getReturnThirdDataSign($village_id, $returnArr);
        $returnArr['sign'] = $dataSign;
        return $returnArr;
    }
    public function synUpdateGarageThirdAiParkCar($data=array()){
        $village_id= isset($data['village_id']) ? $data['village_id']:0;
        fdump_api(['_Line_' . __LINE__, 'data' => $data,'date'=>date('Y-m-d H:i:s')], 'aiHorseSzymzh/synUpdateGarageThirdAiParkCar' . date('H') . 'Log', 1);
        $laterTime=0;
        if(isset($data['garages']) && !empty($data['garages'])){
            $houseVillageParkingCar= new HouseVillageParkingCar();
            $cacheKey = 'village:cache:'.$village_id.':syn_third_ai_park';
            try{
                Cache::set($cacheKey,3600,3600);
            }catch (\Exception $e) {
                fdump_api(['_Line_' . __LINE__, 'err_msg' =>$e->getMessage()], 'aiHorseSzymzh/synUpdateGarageThirdAiParkCar' . date('H') . 'Log', 1);
            }
            fdump_api(['_Line_' . __LINE__, 'cacheKey' => $cacheKey], 'aiHorseSzymzh/synUpdateGarageThirdAiParkCar' . date('H') . 'Log', 1);
            foreach ($data['garages'] as $gvv){
                $whereArr=array(['village_id','=',$village_id],['garage_id','=',$gvv['garage_id']],['car_user_name','<>',''],['car_user_phone','<>','']);
                $parkingCarObj= $houseVillageParkingCar->getNewList($whereArr,'car_id,car_number',0,1000);
                if($parkingCarObj && !$parkingCarObj->isEmpty()){
                    $parkingCars=$parkingCarObj->toArray();
                    foreach ($parkingCars as $kk=>$carV){
                        $queueData=array('village_id'=>$village_id,'car_id'=>$carV['car_id'],'park_key'=>$gvv['third_park_key'],'garage_id'=>$gvv['garage_id'],'opt_type'=>'update_third_ai_park_car');
                        $laterTime=($kk+1)*1.2;
                        $laterTime=intval($laterTime);
                        $this->parkPassageQueuePushLaterToJob($queueData,$laterTime);
                    }
                }
            }
            $laterTime=$laterTime+100;
            try{
                Cache::set($cacheKey,$laterTime,$laterTime);
            }catch (\Exception $e) {
                fdump_api(['_Line_' . __LINE__, 'err_msg' =>$e->getMessage()], 'aiHorseSzymzh/synUpdateGarageThirdAiParkCar' . date('H') . 'Log', 1);
            }
        }
        fdump_api(['_Line_' . __LINE__, 'data' => $data,'date'=>date('Y-m-d H:i:s'),'laterTime'=>$laterTime], 'aiHorseSzymzh/synUpdateGarageThirdAiParkCar' . date('H') . 'Log', 1);
        return true;
    }
    public function  synUpdateThirdAiParkCar($car_id=0,$data=array()){
         //同步处理 车辆信息
        fdump_api(['_Line_' . __LINE__,'synUpdateThirdAiParkCar','car_id' => $car_id, 'data' => $data], 'aiHorseSzymzh/synUpdateThirdAiParkCar' . date('H') . 'Log', 1);
        $village_id= isset($data['village_id']) ? $data['village_id']:0;
        $park_key=isset($data['park_key']) ? $data['park_key']:'';
        if($car_id <1 || $village_id<1 || empty($park_key)){
            return true;
        }
        $houseVillageParkingCar= new HouseVillageParkingCar();
        $whereArr=array(['car_id','=',$car_id],['village_id','=',$village_id]);
        $parkingCarObj=$houseVillageParkingCar->getOne($whereArr,'*');
        if($parkingCarObj && !$parkingCarObj->isEmpty()){
            $tdayStart=date('Y-m-d').' 00:00:00';
            $tdayStartTime=strtotime($tdayStart);
            $parkingCar= $parkingCarObj->toArray();
            $start_time=$parkingCar['start_time'] ? $parkingCar['start_time']:0;
            $end_time=$parkingCar['end_time'] ? $parkingCar['end_time']:0;
            $bind_position_ids=$parkingCar['bind_position_ids'] ? explode(',',$parkingCar['bind_position_ids']):'';
            if(empty($bind_position_ids)){
                $bind_position_ids=array();
            }
            if($parkingCar['car_position_id']){
                $bind_position_ids[]=$parkingCar['car_position_id'];
            }
            $bind_position_ids=array_unique($bind_position_ids);
            if($bind_position_ids){
                $db_house_village_parking_position=new HouseVillageParkingPosition();
                $where_position=array(['village_id','=',$data['village_id']]);
                $where_position[]=['position_id','in',$bind_position_ids];
                $position_list=$db_house_village_parking_position->getList($where_position,'start_time,position_num,end_time');
                if($position_list && !$position_list->isEmpty()){
                    $position_list=$position_list->toArray();
                    foreach ($position_list as $ppv){
                        if($end_time<$ppv['end_time']){
                            $end_time=$ppv['end_time'];
                            $start_time=$ppv['start_time'];
                        }
                    }
                }
            }
            $car_number=$parkingCar['province'].$parkingCar['car_number'];
            $aiHorseSzymzhService = new AiHorseSzymzhService($village_id);
            $carTypeNoArr = array(
                '3652' => '月租车A', '3653' => '月租车B', '3654' => '月租车C', '3655' => '月租车D', '3661' => '月租车E',
                '3662' => '月租车F', '3663' => '月租车G', '3664' => '月租车H', '3656' => '免费车'
            );
            $car_user_no=$parkingCar['car_user_no'];
            if(empty($parkingCar['car_user_no']) && $parkingCar['car_user_name'] && $parkingCar['car_user_phone']){
                $parking_car_type=$parkingCar['parking_car_type'];
                if(!in_array($parking_car_type,$carTypeNoArr)){
                    $parking_car_type='3652';
                }
                $parking_car_type=strval($parking_car_type);
                $params=array('userName'=>$parkingCar['car_user_name'],'carTypeNo'=>$parking_car_type,'mobNumber'=>$parkingCar['car_user_phone']);
                $params['homeAddress']='';
                $params['machineNo']='FFFFFFFF';
                $rets=$aiHorseSzymzhService->addMonthCarPerson($park_key,$params);
                fdump_api(['_Line_' . __LINE__,'addMonthCarPerson','params' => $params, 'rets' => $rets], 'aiHorseSzymzh/synUpdateThirdAiParkCar' . date('H') . 'Log', 1);
                if(isset($rets['data']) && $rets['data'] && $rets['data']['userNo']){
                    $addCar=array('carNo'=>$car_number,'userNo'=>$rets['data']['userNo']);
                    $retTmp=$aiHorseSzymzhService->addMonthCar($park_key,$addCar);
                    fdump_api(['_Line_' . __LINE__,'addMonthCarPerson','addCar' => $addCar, 'retTmp' => $retTmp], 'aiHorseSzymzh/synUpdateThirdAiParkCar' . date('H') . 'Log', 1);
                    if(!empty($retTmp['data']) || strpos($retTmp['err_msg'],'已存在')!==false){
                        $saveArr=array('add_cfrom'=>'third_ai_park','car_user_no'=>$rets['data']['userNo'],'parking_car_type'=>$parking_car_type,'equipment_no'=>$park_key);
                        $car_user_no=$rets['data']['userNo'];
                        $extra_data=array('carPerson'=>$rets['data'],'car'=>$retTmp);
                        $saveArr['extra_data'] = json_encode($extra_data, JSON_UNESCAPED_UNICODE);
                        $res = $houseVillageParkingCar->editCar(array('car_id' => $parkingCar['car_id']), $saveArr);
                    }
                }
            }else if(!empty($parkingCar['car_user_no'])){
                $parking_car_type=$parkingCar['parking_car_type'];
                if(!in_array($parking_car_type,$carTypeNoArr)){
                    $parking_car_type='3652';
                }
                $parking_car_type=strval($parking_car_type);
                $params=array('userName'=>$parkingCar['car_user_name'],'carTypeNo'=>$parking_car_type,'mobNumber'=>$parkingCar['car_user_phone']);
                $params['machineNo']='FFFFFFFF';
                $params['userNo']=$parkingCar['car_user_no'];
                $rets=$aiHorseSzymzhService->editMonthCarPerson($park_key,$params);
                fdump_api(['_Line_' . __LINE__,'editMonthCarPerson','params' => $params, 'rets' => $rets], 'aiHorseSzymzh/synUpdateThirdAiParkCar' . date('H') . 'Log', 1);
                $addCar=array('carNo'=>$car_number,'userNo'=>$parkingCar['car_user_no']);
                $retTmp=$aiHorseSzymzhService->addMonthCar($park_key,$addCar);
                fdump_api(['_Line_' . __LINE__,'addMonthCar', 'addCar' => $addCar, 'retTmp' => $retTmp], 'aiHorseSzymzh/synUpdateThirdAiParkCar' . date('H') . 'Log', 1);
            }
            if($car_user_no && $end_time>$tdayStartTime){
                $carCarTime=array('carNo'=>$car_number,'mthChargeMoney'=>'0','isUpdateBeginTime'=>'0');
                $carCarTime['endTime']=date('Y-m-d H:i:s',$end_time);
                if($start_time>946656000){
                    $carCarTime['beginTime']=date('Y-m-d H:i:s',$start_time);
                }else{
                    $carCarTime['beginTime']=$tdayStart;
                }
                $chargeCarRet=$aiHorseSzymzhService->chargeMonthCarTime($park_key,$carCarTime);
                fdump_api(['_Line_' . __LINE__,'chargeMonthCarTime', 'carCarTime' => $carCarTime, 'chargeCarRet' => $chargeCarRet], 'aiHorseSzymzh/synUpdateThirdAiParkCar' . date('H') . 'Log', 1);
            }
        }
    }

    public function synUpdateThirdAiParkCarTime($car_id = 0, $data = array())
    {
        $village_id = isset($data['village_id']) ? $data['village_id'] : 0;
        $park_key = isset($data['park_key']) ? $data['park_key'] : '';
        if ($car_id < 1 || $village_id < 1 || empty($park_key)) {
            return true;
        }
        $houseVillageParkingCar = new HouseVillageParkingCar();
        $whereArr = array(['car_id', '=', $car_id], ['village_id', '=', $village_id]);
        $parkingCarObj = $houseVillageParkingCar->getOne($whereArr, '*');
        if ($parkingCarObj && !$parkingCarObj->isEmpty()) {
            $tdayStart = date('Y-m-d') . ' 00:00:00';
            $tdayStartTime = strtotime($tdayStart);
            $parkingCar = $parkingCarObj->toArray();
            $start_time = $parkingCar['start_time'] ? $parkingCar['start_time'] : 0;
            $end_time = $parkingCar['end_time'] ? $parkingCar['end_time'] : 0;
            $bind_position_ids = $parkingCar['bind_position_ids'] ? explode(',', $parkingCar['bind_position_ids']) : '';
            if (empty($bind_position_ids)) {
                $bind_position_ids = array();
            }
            if ($parkingCar['car_position_id']) {
                $bind_position_ids[] = $parkingCar['car_position_id'];
            }
            $bind_position_ids = array_unique($bind_position_ids);
            if ($bind_position_ids) {
                $db_house_village_parking_position = new HouseVillageParkingPosition();
                $where_position = array(['village_id', '=', $data['village_id']]);
                $where_position[] = ['position_id', 'in', $bind_position_ids];
                $position_list = $db_house_village_parking_position->getList($where_position, 'start_time,position_num,end_time');
                if ($position_list && !$position_list->isEmpty()) {
                    $position_list = $position_list->toArray();
                    foreach ($position_list as $ppv) {
                        if ($end_time < $ppv['end_time']) {
                            $end_time = $ppv['end_time'];
                            $start_time = $ppv['start_time'];
                        }
                    }
                }
            }
            $aiHorseSzymzhService = new AiHorseSzymzhService($village_id);
            $car_number = $parkingCar['province'] . $parkingCar['car_number'];
            if ($parkingCar['car_user_no'] && $end_time > $tdayStartTime) {
                $carCarTime = array('carNo' => $car_number, 'mthChargeMoney' => '0', 'isUpdateBeginTime' => '0');
                $carCarTime['endTime'] = date('Y-m-d H:i:s', $end_time);
                if ($start_time > 946656000) {
                    $carCarTime['beginTime'] = date('Y-m-d H:i:s', $start_time);
                } else {
                    $carCarTime['beginTime'] = $tdayStart;
                }
                $chargeCarRet = $aiHorseSzymzhService->chargeMonthCarTime($park_key, $carCarTime);
                fdump_api(['_Line_' . __LINE__, 'chargeMonthCarTime','data'=>$data,'carCarTime' => $carCarTime, 'chargeCarRet' => $chargeCarRet], 'aiHorseSzymzh/synUpdateThirdAiParkCarTime' . date('H') . 'Log', 1);
            }

        }
        return true;
    }

    public function delFailMonthCar($park_key = '', $car_number = '', $village_id = 0)
    {
        if (empty($car_number) || $village_id < 1 || empty($park_key)) {
            return false;
        }
        $aiHorseSzymzhService = new AiHorseSzymzhService($village_id);
        $params = array('deleteCarSpace' => 'true', 'carNo' => $car_number);
        $delret = $aiHorseSzymzhService->failMonthCar($park_key, $params);
        fdump_api(['_Line_' . __LINE__, 'delFailMonthCar', 'params' => $params, 'delret' => $delret], 'aiHorseSzymzh/delFailMonthCar' . date('H') . 'Log', 1);
        return $delret;
    }
    
}
