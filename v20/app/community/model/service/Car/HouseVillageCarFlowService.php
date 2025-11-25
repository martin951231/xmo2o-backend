<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      停车优化方法
 */

namespace app\community\model\service\Car;

use app\community\model\db\Car\HouseVillageCarFlowRecord;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\InPark;
use app\community\model\db\ParkOpenLog;
use app\community\model\db\ParkPassage;
use app\community\model\db\ParkSerialLog;
use app\community\model\db\ParkShowscreenLog;
use app\community\model\service\HouseVillageParkingService;
use app\community\model\service\Park\A11Service;
use app\consts\Car\CarFlowConst;
use app\traits\Car\CarFlowHandleTraits;
use app\traits\Car\CarFlowPlateResultTraits;

class HouseVillageCarFlowService
{
    use CarFlowPlateResultTraits, CarFlowHandleTraits;

    public $webhookurl = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send';
    // 临时车免登记时长(默认)
    public $register_day = 15;
    // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
    // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
    //交易流水号(pay_type为wallet、scancode、sweepcode必传)
    public $pay_type = [0 => 'cash', 1 => 'wallet', 2 => 'sweepcode', 3 => 'escape', 4 => 'monthuser', 5 => 'free', 6 => 'scancode'];

    // 默认免停时长.
    public $default_free_park_time = 900;

    public $sign_advance_pay_time = 600;

    public $toTestPassageArr = ['1111'];

    public function judgeToNew($serialno = '')
    {
        return false;
        if (empty($this->toTestPassageArr)) {
            // 没有测试通道-默认全员走新逻辑
            return true;
        }
        if ($serialno && in_array($serialno, $this->toTestPassageArr)) {
            // 属于测试通道走新逻辑
            return true;
        }
        return false;
    }
    
    /**
     * 停车系统识别推送处理.
     */
    public function handlePlateResult($data, $reportTime = 0, $nowTime = 0)
    {
        try {
            return $this->handlePlateResultBox($data, $reportTime, $nowTime);
        } catch (\Exception $e) {
            fdump_api(['msg' => '停车系统识别推送处理-捕捉异常盒子', 'errLine' => $e->getLine(), 'errMsg' => $e->getMessage(), 'errFile' => $e->getFile(), 'errCode' => $e->getCode(), 'line' => __LINE__], 'parkBetter/tryBoxErr', 1);
            return false;
        }
    }

    /**
     * A11停车信息处理.
     */
    public function handleA11ParkCar($car_number, $serialno, $data, $passage_info = [], $village_info = [], $params = [])
    {
        try {
            return $this->handleA11ParkCarBox($car_number, $serialno, $data, $passage_info, $village_info, $params);
        } catch (\Exception $e) {
            fdump_api(['msg' => 'A11停车信息处理-捕捉异常盒子', 'errLine' => $e->getLine(), 'errMsg' => $e->getMessage(), 'errFile' => $e->getFile(), 'errCode' => $e->getCode(), 'line' => __LINE__], 'parkBetter/tryBoxErr', 1);
            return false;
        }
    }

    /**
     * D3停车信息处理.
     */
    public function handleD3ParkCar($car_number, $serialno, $data, $passage_info = [], $village_info = [], $params = [])
    {
        try {
            return $this->handleD3ParkCarBox($car_number, $serialno, $data, $passage_info, $village_info, $params);
        } catch (\Exception $e) {
            fdump_api(['msg' => 'D3停车信息处理-捕捉异常盒子', 'errLine' => $e->getLine(), 'errMsg' => $e->getMessage(), 'errFile' => $e->getFile(), 'errCode' => $e->getCode(), 'line' => __LINE__], 'parkBetter/tryBoxErr', 1);
            return false;
        }
    }

    /**
     * 停车系统识别推送处理-盒子.
     */
    protected function handlePlateResultBox($data, $reportTime = 0, $nowTime = 0)
    {
        fdump_api(['msg' => '方法入参', 'parameter' => func_get_args(), 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
        $result = false;
        if (isset($data['AlarmInfoPlate']['result']['PlateResult']['license'])) {
            $car_number = trim($data['AlarmInfoPlate']['result']['PlateResult']['license']);
        } else {
            $car_number = '';
        }
        if (isset($data['AlarmInfoPlate']['serialno'])) {
            $serialno = $data['AlarmInfoPlate']['serialno'];
        } else {
            $serialno = '';
        }
        $carHandleGetInfoService = new CarHandleGetInfoService();
        $passage_info = $carHandleGetInfoService->getParkPassageInfo($serialno, $car_number);
        if (!$passage_info) {
            fdump_api(['msg' => '缺少通道信息', 'line' => __LINE__, 'car_number' => $car_number, 'serialno' => $serialno, 'data' => $data], 'parkBetter/handlePlateResultLog' . $reportTime, true);
            return false;
        }
        fdump_api(['msg' => '通道信息', 'passage_info' => $passage_info, 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
        $village_id = isset($passage_info['village_id']) && $passage_info['village_id'] ? intval($passage_info['village_id']) : 0;
        $village_info = $carHandleGetInfoService->getVillageInfo($village_id);
        $village_name = isset($village_info['village_name']) && $village_info['village_name'] ? $village_info['village_name'] : '';
        $device_name = isset($passage_info['passage_name']) && $passage_info['passage_name'] ? $passage_info['passage_name'] : '';
        if (intval($passage_info['passage_direction']) == 1) {
            $passage_direction = '入口';
        } else {
            $passage_direction = '出口';
        }
        if (!$car_number || strpos($car_number, '无') !== false) {
            $info = '设备未识别到车牌';
            $flow_status = '拒绝通行';
            $reason = '未识别到车牌';
            $carHandleGetInfoService->pushMessageQy($info, $village_name, $car_number, $device_name, $flow_status, $passage_direction, $reason);
            fdump_api(['msg' => $info, 'line' => __LINE__, 'data' => $data, 'serialno' => $serialno, 'passage_info' => $passage_info, 'village_info' => $village_info], 'parkBetter/handlePlateResultLog' . $reportTime, true);
            return false;
        }
        $park_sys_type = isset($passage_info['park_sys_type']) && $passage_info['park_sys_type'] ? trim($passage_info['park_sys_type']) : 'D3';
        !$nowTime && $nowTime = time();
        !$reportTime && $reportTime = date('YmdH');
        fdump_api(['msg' => '调用对应不同方法', 'park_sys_type' => $park_sys_type, 'reportTime' => $reportTime, 'nowTime' => $nowTime, 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
        if ($park_sys_type == 'A11') {
            // todo A11停车
            $params = [
                'reportTime' => $reportTime,
                'nowTime' => $nowTime,
            ];
            $result = $this->handleA11ParkCar($car_number, $serialno, $data, $passage_info, $village_info, $params);
            return $result;
        } else {
            // todo D3停车（营山）
            $params = [
                'reportTime' => $reportTime,
                'nowTime' => $nowTime,
            ];
            $result = $this->handleD3ParkCar($car_number, $serialno, $data, $passage_info, $village_info, $params);
            return $result;
        }
    }

    /**
     * D3停车信息处理.
     */
    protected function handleD3ParkCarBox($car_number, $serialno, $data, $passage_info = [], $village_info = [], $params = [])
    {
        fdump_api(['msg' => '方法入参', 'parameter' => func_get_args(), 'line' => __LINE__], 'parkBetter/handleD3ParkCarLog', 1);
        $reportTime = isset($params['reportTime']) && $params['reportTime'] ? $params['reportTime'] : '';
        $carHandleGetInfoService = new CarHandleGetInfoService();
        $result = false;
        if (empty($passage_info)) {
            $passage_info = $carHandleGetInfoService->getParkPassageInfo($serialno, $car_number);
            if (!$passage_info) {
                fdump_api(['msg' => '缺少通道信息', 'line' => __LINE__, 'car_number' => $car_number, 'serialno' => $serialno, 'village_info' => $village_info, 'params' => $params], 'parkBetter/handlePlateResultLog' . $reportTime, true);
                return $result;
            }
        }
        $village_id = isset($passage_info['village_id']) && $passage_info['village_id'] ? intval($passage_info['village_id']) : 0;
        if (empty($village_info) && $village_id) {
            $village_info = $carHandleGetInfoService->getVillageInfo($village_id);
        }
        if ($passage_info['passage_direction'] == 1) {
            $passage_direction = '入口';
        } else {
            $passage_direction = '出口';
        }
        $park_sys_type = isset($passage_info['park_sys_type']) && $passage_info['park_sys_type'] ? trim($passage_info['park_sys_type']) : 'D3';
        (!isset($passage_info['park_sys_type']) || !$passage_info['park_sys_type']) && $passage_info['park_sys_type'] = $park_sys_type;
        $blackInfo = $carHandleGetInfoService->judgeBlacklist($car_number, $village_id, $park_sys_type);
        $village_name = isset($village_info['village_name']) && $village_info['village_name'] ? $village_info['village_name'] : '';
        $device_name = isset($passage_info['passage_name']) && $passage_info['passage_name'] ? $passage_info['passage_name'] : '';
        if (!empty($blackInfo)) {
            $info = $car_number . '车辆是黑名单车辆';
            $flow_status = '拒绝通行';
            $reason = '黑名单车辆';
            $carHandleGetInfoService->pushMessageQy($info, $village_name, $car_number, $device_name, $flow_status, $passage_direction, $reason);
            fdump_api(['msg' => $info, 'line' => __LINE__, 'car_number' => $car_number, 'serialno' => $serialno, 'passage_info' => $passage_info, 'village_info' => $village_info, 'params' => $params], 'parkBetter/handlePlateResultLog' . $reportTime, true);
            return $result;
        }
        // 判断车辆出入场
        //车辆入场
        if (intval($passage_info['passage_direction']) == 1) {
            $result = $this->carFlowIn($car_number, $serialno, $data, $passage_info, $village_info, $params);
        }
        //车辆出场
        if (intval($passage_info['passage_direction']) == 0) {
            $result = $this->carFlowOut($car_number, $serialno, $data, $passage_info, $village_info, $params);
        }
        return $result;
    }
    
    /**
     * A11停车信息处理-盒子.
     */
    protected function handleA11ParkCarBox($car_number, $serialno, $data, $passage_info = [], $village_info = [], $params = [])
    {
        $reportTime = isset($params['reportTime']) && $params['reportTime'] ? $params['reportTime'] : '';
        fdump_api(['msg' => 'A11停车信息处理方法入参', 'parameter' => func_get_args(), 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
        $carHandleGetInfoService = new CarHandleGetInfoService();
        $result = false;
        if (empty($passage_info)) {
            $passage_info = $carHandleGetInfoService->getParkPassageInfo($serialno, $car_number);
            if (!$passage_info) {
                fdump_api(['msg' => '缺少通道信息', 'line' => __LINE__, 'car_number' => $car_number, 'serialno' => $serialno, 'village_info' => $village_info, 'params' => $params], 'parkBetter/handlePlateResultLog' . $reportTime, true);
                return $result;
            }
        }
        $village_id = isset($passage_info['village_id']) && $passage_info['village_id'] ? intval($passage_info['village_id']) : 0;
        if (empty($village_info) && $village_id) {
            $village_info = $carHandleGetInfoService->getVillageInfo($village_id);
        }
        $park_sys_type = isset($passage_info['park_sys_type']) && $passage_info['park_sys_type'] ? trim($passage_info['park_sys_type']) : 'A11';
        (!isset($passage_info['park_sys_type']) || !$passage_info['park_sys_type']) && $passage_info['park_sys_type'] = $park_sys_type;
        $blackInfo = $carHandleGetInfoService->judgeBlacklist($car_number, $village_id, $park_sys_type);
        $showScreenData = [];
        $showScreenData['passage'] = $passage_info;
        $showScreenData['village_id'] = $passage_info['village_id'];
        $showScreenData['car_number'] = $car_number;
        $showScreenData['channel_id'] = $serialno;
        $showScreenData['car_type'] = '';
        $showScreenData['voice_content'] = 1;
        $showScreenData['content'] = '欢迎光临';
        if (!empty($blackInfo)) {
            $showScreenData['car_type'] = 'black_type';
            $showScreenData['content'] = '黑名单禁止通行';
            $showScreenData['voice_content'] = 10;
            (new CarFlowShowScreenService())->addA11ParkShowScreenLog($showScreenData);
            fdump_api(['msg' => '黑名单禁止通行', 'line' => __LINE__, 'car_number' => $car_number, 'blackInfo' => $blackInfo, 'showScreenData' => $showScreenData, 'serialno' => $serialno, 'passage_info' => $passage_info, 'village_info' => $village_info, 'params' => $params], 'parkBetter/handlePlateResultLog' . $reportTime, true);
            return $result;
        }
        fdump_api(['msg' => '根据通道走进场或者出场方法', 'passage_info' => $passage_info, 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
        // 判断车辆出入场
        //车辆入场
        if (intval($passage_info['passage_direction']) == 1) {
            $result = $this->carFlowIn($car_number, $serialno, $data, $passage_info, $village_info, $params);
        }
        //车辆出场
        if (intval($passage_info['passage_direction']) == 0) {
            $result = $this->carFlowOut($car_number, $serialno, $data, $passage_info, $village_info, $params);
        }
        return $result;
    }

    /**
     * 入场处理然后反馈是否可以开闸.
     */
    public function carFlowIn($car_number, $serialno, $data, $passage_info = [], $village_info = [], $params = [])
    {
        $reportTime = isset($params['reportTime']) && $params['reportTime'] ? $params['reportTime'] : '';
        fdump_api(['msg' => '车辆入场', 'parameter' => func_get_args(), 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
        $park_sys_type = isset($passage_info['park_sys_type']) && $passage_info['park_sys_type'] ? trim($passage_info['park_sys_type']) : 'D3';
        $nowTime = isset($params['nowTime']) && $params['nowTime'] ? $params['nowTime'] : time();
        $village_id = isset($passage_info['village_id']) && $passage_info['village_id'] ? intval($passage_info['village_id']) : 0;
        $village_name = isset($village_info['village_name']) && $village_info['village_name'] ? $village_info['village_name'] : '';
        $property_id = isset($village_info['property_id']) && $village_info['property_id'] ? intval($village_info['property_id']) : '';
        $park_passage_id = isset($passage_info['id']) && $passage_info['id'] ? intval($passage_info['id']) : 0;
        !$village_id && $village_id = isset($village_info['village_id']) && $village_info['village_id'] ? intval($village_info['village_id']) : 0;

        $showScreenData = [];
        $showScreenData['passage'] = $passage_info;
        $showScreenData['village_id'] = $village_id;
        $showScreenData['car_number'] = $car_number;
        $showScreenData['channel_id'] = $serialno;
        $showScreenData['car_type'] = '';
        $showScreenData['voice_content'] = 1;
        $showScreenData['content'] = '欢迎光临';

        if (isset($data['AlarmInfoPlate']['result']['PlateResult']['type'])) {
            $car_number_type = trim($data['AlarmInfoPlate']['result']['PlateResult']['type']);
        } else {
            $car_number_type = '';
        }

        if (isset($passage_info['garage_id']) && intval($passage_info['garage_id']) > 0) {
            $garage_id = intval($passage_info['garage_id']);
        } elseif (isset($passage_info['area_type']) && isset($passage_info['passage_area']) && intval($passage_info['area_type']) == 2 && intval($passage_info['passage_area']) > 0) {
            $garage_id = intval($passage_info['passage_area']);
        } else {
            $garage_id = 0;
        }
        fdump_api(['msg' => '车辆入场', 'car_number_type' => $car_number_type, 'garage_id' => $garage_id, 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
        // todo 识别记录走队列
        $parkPlateResultLog = [];
        $parkPlateResultLog['car_number'] = $car_number;
        $parkPlateResultLog['channel_id'] = $serialno;
        $parkPlateResultLog['park_type'] = 1;
        $parkPlateResultLog['add_time'] = $nowTime;
        $parkPlateResultLog['village_id'] = $village_id;
        $parkPlateResultLog['park_sys_type'] = $park_sys_type;
        $parkPlateResultLog['park_passage_id'] = $park_passage_id;
        $parkPlateResultLog['garage_id'] = $garage_id;
        $parkPlateResultLog['car_number_type'] = $car_number_type;
        $parkPlateResultLog['jobType'] = 'plateResultLog';
        $plateResultLogJobId = $this->traitCommonCarFlowPlateResult($parkPlateResultLog);
        if (!$plateResultLogJobId) {
            // todo 队列没有写入直接执行
            (new CarFlowJobService())->plateResultLog($data);
        }
        fdump_api(['msg' => '识别记录走队列', 'parkPlateResultLog' => $parkPlateResultLog, 'plateResultLogJobId' => $plateResultLogJobId, 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
        // todo 识别记录走队列

        $park_data = [];
        $park_data['car_number'] = $car_number;
        $park_data['in_time'] = $nowTime;
        $park_data['order_id'] = md5($serialno.$car_number.$nowTime);
        $park_data['in_channel_id'] = $park_passage_id;
        $park_data['is_paid'] = 0;
        $park_data['park_id'] = $village_id;
        $park_data['village_id'] = $village_id;
        $park_data['park_sys_type'] = $park_sys_type;
        $park_data['park_name'] = $village_name;
        $park_data['channel_number'] = $passage_info['channel_number'];
        $park_data['passage_name'] = $passage_info['passage_name'];
        $park_data['car_number_type'] = $car_number_type;
        $park_data['garage_id'] = $garage_id;
        $park_data['serialno'] = $serialno;

        $carHandleGetInfoService = new CarHandleGetInfoService();
        // 查询判断是否是免费车
        $feeInfo = $carHandleGetInfoService->judgeFreeList($car_number, $village_id, $park_sys_type);
        $inParams = [
            'village_id' => $village_id, 'garage_id' => $garage_id, 'park_passage_id' => $park_passage_id,
            'flow_direction' => 1, 'device_number' => $passage_info['device_number'], 'park_sys_type' => $park_sys_type,
            'car_number' => $car_number, 'flow_time' => $nowTime, 'order_id' => $park_data['order_id'], 'is_out' => 0, 
            'park_name' => $village_name, 'total' => 0, 'is_paid' => 0, 'pay_time' => 0,
        ];
        // 查询下车辆信息
        $car_info = $carHandleGetInfoService->getCarInfo($car_number, $village_id);
        
        $rightPosition = array();
        if ($car_info && isset($car_info['car_id'])) {
            $rightPosition = (new HouseVillageParkingService())->getCarRightInPosition($car_info, $passage_info['village_id'], $garage_id);
            fdump_api(['msg' => '获取车辆绑定的车位 最长停车时间', 'rightPosition' => $rightPosition, 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
            if (!empty($rightPosition)) {
                if ($rightPosition['start_time'] <= $nowTime && $rightPosition['end_time'] >= $car_info['end_time']) {
                    $car_info['end_time'] = $rightPosition['end_time'];
                }
                if($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids']) && $rightPosition['start_time']>$nowTime){
                    //车位时间还没开始
                    $car_info['end_time']=0;
                }
                if (isset($rightPosition['position_id']) && $rightPosition['position_id']) {
                    $park_data['position_id'] = $rightPosition['position_id'];
                }
                if (isset($rightPosition['garage_id']) && $rightPosition['garage_id']) {
                    $park_data['garage_id'] = $rightPosition['garage_id'];
                }
                if (isset($rightPosition['position_num']) && $rightPosition['position_num']) {
                    $park_data['position_num'] = $rightPosition['position_num'];
                }
            } elseif (empty($rightPosition)) {
                $bind_position_ids=array();
                if($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids'])){
                    //新的绑定车位 多车多位逻辑，没有可有车位时
                    $bind_position_ids=explode(',',$car_info['bind_position_ids']);
                    $car_info['end_time']=0;  //没有可有车位时
                }
            }
            $park_data['car_id'] = $car_info['car_id'];
        }
        $isFee = false;
        if (!empty($feeInfo)) {
            $isFee = true;
        }
        $serviceA11 = new A11Service();
        if ($park_sys_type == 'A11' && isset($car_info['parking_car_type']) && $car_info['parking_car_type'] == $serviceA11::Free_A) {
            // 车辆卡类为免费车时
            $isFee = true;
        }
        $parking_car_type = isset($car_info['parking_car_type']) && intval($car_info['parking_car_type']) > 0 ? intval($car_info['parking_car_type']) : 0;
        if (isset($car_info['stored_balance']) && floatval($car_info['stored_balance']) > 0) {
            $showScreenData['stored_balance'] = $car_info['stored_balance'];
            $showScreenData['car_type'] = 'storedCar';
        } elseif ($park_sys_type == 'A11' && in_array($parking_car_type, [13, 14, 15, 16])) {
            $showScreenData['stored_balance'] = $car_info['stored_balance'];
            $showScreenData['car_type'] = 'storedCar';
        }
        if (isset($data['AlarmInfoPlate']['result']['PlateResult']['imageFile']) && trim($data['AlarmInfoPlate']['result']['PlateResult']['imageFile'])) {
            $imageFile = trim($data['AlarmInfoPlate']['result']['PlateResult']['imageFile']);
            $park_data['imageFile'] = $imageFile;
        }
        if (isset($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile']) && trim($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile'])) {
            $imageFragmentFile = trim($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile']);
            $park_data['imageFragmentFile'] = $imageFragmentFile;
        }
        $rand_num = date('Ymd', $nowTime);// 换成日期存储
        $path = '/upload/park_log/' . $rand_num . '/' . $serialno . '/';
        $up_dir = $_SERVER['DOCUMENT_ROOT'] . $path;
        $park_data['imageSavePath'] = $up_dir;
        $car_garage_id = isset($car_info['garage_id']) ? intval($car_info['garage_id']) : 0;
        $car_end_time = isset($car_info['end_time']) ? intval($car_info['end_time']) : 0;
        $stored_balance = isset($car_info['stored_balance']) && floatval($car_info['stored_balance']) > 0 ? $car_info['stored_balance'] : 0;
        $car_id = isset($car_info['car_id']) ? intval($car_info['car_id']) : 0;
        $car_user_phone = isset($car_info['car_user_phone']) && $car_info['car_user_phone'] ? trim($car_info['car_user_phone']) : '';
        
        if ($isFee) {
            fdump_api(['msg' => '车辆是免费车', 'park_sys_type' => $park_sys_type, 'car_info' => $car_info, 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
            $accessMode = 8;
            // todo 免费车 直接反馈开闸 并记录
            $flowIntInfo = $carHandleGetInfoService->checkCarFlowInt($car_number, $village_id, $garage_id, $nowTime, $park_sys_type);
            fdump_api(['msg' => '查询入场记录', 'flowIntInfo' => $flowIntInfo, 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
            $in_park_id = isset($flowIntInfo['id']) && intval($flowIntInfo['id']) > 0 ? intval($flowIntInfo['id']) : 0;
            $inParams['from_id'] = $in_park_id;
            $park_data['accessMode'] = $accessMode;
            $printParams = unsetParam($park_data, ['imageFile', 'AlarmInfoPlate']);
            fdump_api(['msg' => '记录入场', 'park_data' => $printParams, 'inParams' => $inParams, 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
            $park_data = $carHandleGetInfoService->recordCarFlowInt($park_data, $inParams);
            $showScreenData['content'] = '免费车通行';
            if ($park_sys_type == 'A11') {
                fdump_api(['msg' => '显屏', 'showScreenData' => $showScreenData, 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
                (new CarFlowShowScreenService())->addA11ParkShowScreenLog($showScreenData);
                $sendParam = [
                    'garage_id' => $garage_id, 'end_time' => $car_end_time, 'car_garage_id' => $car_garage_id, 'stored_balance' => $stored_balance,
                    'car_id' => $car_id, 'car_user_phone' => $car_user_phone, 'park_name' => $village_name, 'passage_direction' => '入场',
                    'remark' => '\n入场类型：免费车', 'is_small' => false, 'property_id' => $property_id, 'nowTime' => $nowTime, 'park_time' => 0,
                ];
                fdump_api(['msg' => '模板推送', 'sendParam' => $sendParam, 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
                $carHandleGetInfoService->sendCarMessage($car_number, $village_id, $sendParam);
            } else {
                fdump_api(['msg' => '显屏', 'showScreenData' => $showScreenData, 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
                (new CarFlowShowScreenService())->addParkShowScreenLog($showScreenData);
            }
            // 特殊车辆通行
            $printParams = unsetParam($park_data, ['imageFile', 'AlarmInfoPlate']);
            fdump_api(['msg' => '免费车通行', 'line' => __LINE__, 'nowTime' => $nowTime, 'park_data' => $printParams, 'inParams' => $inParams, 'showScreenData' => $showScreenData], 'parkBetter/handlePlateResultLog' . $reportTime, true);
            return true;
        }
        // todo A11存在大小场 逻辑单独处理
        $isMonthCar = false;
        $passage_garage_id = $garage_id;
        if ($park_sys_type == 'A11') {
            $car_garage_id = isset($car_info['garage_id']) ? intval($car_info['garage_id']) : 0;
            $car_position_id = isset($car_info['car_position_id']) ? intval($car_info['car_position_id']) : 0;
            if (! $car_garage_id) {
                $position_info = $carHandleGetInfoService->getCarPosition($car_position_id, $village_id);
                isset($position_info['garage_id']) && $position_info['garage_id'] && $car_garage_id = intval($position_info['garage_id']);
            }
            if ($car_end_time >= $nowTime && $passage_garage_id && $car_garage_id && $car_garage_id == $passage_garage_id) {
                // 月租车有效时间内 且入场车场和车辆所属为相同车场 月租车入场
                $isMonthCar = true;
                fdump_api(['msg' => '月租车有效时间内 且入场车场和车辆所属为相同车场 月租车入场', 'end_time' => $car_end_time, 'car_garage_id' => $car_garage_id, 'passage_garage_id' => $passage_garage_id, 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
            }
        }

        if ($park_sys_type == 'D3' && $car_end_time >= $nowTime) {
            // todo 判断车辆为月租车 直接反馈开闸 并记录
            $isMonthCar = true;
            fdump_api(['msg' => 'd3直接判断车的月租期限', 'end_time' => $car_end_time, 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
        }
        
        if ($isMonthCar) {
            // todo 判断车辆为月租车 直接反馈开闸 并记录
            $accessMode = 5;
            $flowIntInfo = $carHandleGetInfoService->checkCarFlowInt($car_number, $village_id, $garage_id, $nowTime, $park_sys_type);
            fdump_api(['msg' => '查询入场记录', 'flowIntInfo' => $flowIntInfo, 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
            $in_park_id = isset($flowIntInfo['id']) && intval($flowIntInfo['id']) > 0 ? intval($flowIntInfo['id']) : 0;
            $inParams['from_id'] = $in_park_id;
            $park_data['car_type'] = 'monthCar';
            $park_data['accessMode'] = $accessMode;
            $printParams = unsetParam($park_data, ['imageFile', 'AlarmInfoPlate']);
            fdump_api(['msg' => '记录入场', 'park_data' => $printParams, 'inParams' => $inParams, 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
            if(!empty($rightPosition)){
                $park_data['isRightPosition'] = 1;
            }
            $park_data = $carHandleGetInfoService->recordCarFlowInt($park_data, $inParams);
            // TODO:屏显和语音提示特殊车辆通行
            $showScreenData['car_type'] = 'month_type';
            $showScreenData['end_time'] = $car_end_time;
            $showScreenData['end_time_txt'] = date('Y-m-d', $car_end_time);
            // 月租车通行
            if ($park_sys_type == 'A11') {
                fdump_api(['msg' => '显屏', 'showScreenData' => $showScreenData, 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
                (new CarFlowShowScreenService())->addA11ParkShowScreenLog($showScreenData);
                $sendParam = [
                    'garage_id' => $garage_id, 'end_time' => $car_end_time, 'car_garage_id' => $car_garage_id, 'stored_balance' => $stored_balance,
                    'car_id' => $car_id, 'car_user_phone' => $car_user_phone, 'park_name' => $village_name, 'passage_direction' => '入场',
                    'remark' => '\n入场类型：月租车', 'is_small' => false, 'property_id' => $property_id, 'nowTime' => $nowTime, 'park_time' => 0,
                ];
                fdump_api(['msg' => '模板推送', 'sendParam' => $sendParam, 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
                $carHandleGetInfoService->sendCarMessage($car_number, $village_id, $sendParam);
            } else {
                fdump_api(['msg' => '显屏', 'showScreenData' => $showScreenData, 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
                (new CarFlowShowScreenService())->addParkShowScreenLog($showScreenData);
            }
            $printParams = unsetParam($park_data, ['imageFile', 'AlarmInfoPlate']);
            fdump_api(['msg' => '月租车通行', 'line' => __LINE__, 'nowTime' => $nowTime, 'car_info' => $car_info, 'park_data' => $printParams, 'inParams' => $inParams, 'showScreenData' => $showScreenData], 'parkBetter/handlePlateResultLog' . $reportTime, true);
            return true;
        }
        
        $parkConfig = $carHandleGetInfoService->getParkConfig($village_id, $garage_id);
        fdump_api(['msg' => '停车配置项', 'parkConfig' => $parkConfig, 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);

        // 是否允许临时车入场
        $isTemporaryCar = false;
        // 是否禁止临时车入场
        $notTemporaryCar = false;
        /** @var bool isOverMonth 当前车辆是否为已过期月租车 */
        $isOverMonth = false;
        /** @var bool hasMonth 当前车辆是否为为未过期月租车 */
        $hasMonth = false;
        /** @var bool notParentORSubPark 是否不是大小（父子）场 */
        $notParentORSubPark = true;
        if ($car_end_time >= $nowTime) {
            $hasMonth = true;
        }
        if (!$hasMonth && $car_end_time > 100) {
            $isOverMonth = true;
        }
        if ($park_sys_type == 'A11' && isset($car_garage_id) && $car_garage_id > 0) {
            $car_garage_info = $carHandleGetInfoService->getParkingGarage($car_garage_id, $village_id);
            $car_garage_fid = isset($car_garage_info['fid']) && intval($car_garage_info['fid']) > 0 ? intval($car_garage_info['fid']) : 0;
            $car_is_month_access = isset($car_garage_info['is_month_access']) && intval($car_garage_info['is_month_access']) > 0 ? intval($car_garage_info['is_month_access']) : 0;
            if ($passage_garage_id > 0) {
                $passage_garage_info = $carHandleGetInfoService->getParkingGarage($passage_garage_id, $village_id);
            } else {
                $passage_garage_info = [];
            }
            $passage_is_month_access = isset($passage_garage_info['is_month_access']) && intval($passage_garage_info['is_month_access']) > 0 ? intval($passage_garage_info['is_month_access']) : 0;
            $passage_garage_fid = isset($passage_garage_info['fid']) && intval($passage_garage_info['fid']) > 0 ? intval($passage_garage_info['fid']) : 0;
            if ($passage_garage_fid <= 0 && $passage_garage_id > 0) {
                // 检测当前通道是否为大场（父停车场）
                $sub_passage_garage_info = $carHandleGetInfoService->getSubParkingGarage($passage_garage_id, $village_id);
                if (isset($sub_passage_garage_info['garage_id']) && intval($sub_passage_garage_info['garage_id']) > 0) {
                    // 存在子车场
                    $notParentORSubPark = false;
                }
            } elseif ($passage_garage_fid > 0) {
                $parent_passage_garage_info = $carHandleGetInfoService->getParkingGarage($passage_garage_fid, $village_id);
                if (isset($parent_passage_garage_info['garage_id']) && intval($parent_passage_garage_info['garage_id']) > 0) {
                    // 存在父车场
                    $notParentORSubPark = false;
                }
            }
            if ($hasMonth) {
                if ($car_garage_fid > 0 && $car_garage_fid == $passage_garage_id && $passage_is_month_access == 1) {
                    // 车辆月租车所属车场的[父级车场]为[入场通道所属车场],即[小停车场月租车]进入[大停车场]的入口通道 且 通道停车场允许【月租车进入非月租场】 支持车辆入场
                    fdump_api(['msg' => '车辆月租车所属车场的[父级车场]为[入场通道所属车场],即[小停车场月租车]进入[大停车场]的入口通道 且 通道停车场允许【月租车进入非月租场】 支持车辆入场', 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
                    $isTemporaryCar = true;
                } elseif ($passage_garage_fid > 0 && $passage_garage_fid == $car_garage_id && $passage_is_month_access == 1) {
                    // 车辆入场通道所属[父级车场]为[车辆月租归属车场],即[大停车场月租车]进入[小停车场]的[入口通道] 且 通道停车场允许【月租车进入非月租场】 支持车辆入场
                    fdump_api(['msg' => '车辆入场通道所属[父级车场]为[车辆月租归属车场],即[大停车场月租车]进入[小停车场]的[入口通道] 且 通道停车场允许【月租车进入非月租场】 支持车辆入场', 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
                    $isTemporaryCar = true;
                } elseif ($car_garage_fid > 0 && $car_garage_fid == $passage_garage_id && $passage_is_month_access == 0) {
                    // 车辆月租车所属车场的[父级车场]为[入场通道所属车场],即[小停车场月租车]进入[大停车场]的入口通道 且 通道停车场 禁止【月租车进入非月租场】 禁止车辆入场
                    fdump_api(['msg' => '车辆月租车所属车场的[父级车场]为[入场通道所属车场],即[小停车场月租车]进入[大停车场]的入口通道 且 通道停车场 禁止【月租车进入非月租场】 禁止车辆入场', 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
                    $notTemporaryCar = true;
                } elseif ($passage_garage_fid > 0 && $passage_garage_fid == $car_garage_id && $passage_is_month_access == 0) {
                    // 车辆入场通道所属[父级车场]为[车辆月租归属车场],即[大停车场月租车]进入[小停车场]的[入口通道] 且 通道停车场 禁止 【月租车进入非月租场】 禁止车辆入场
                    fdump_api(['msg' => '车辆入场通道所属[父级车场]为[车辆月租归属车场],即[大停车场月租车]进入[小停车场]的[入口通道] 且 通道停车场 禁止 【月租车进入非月租场】 禁止车辆入场', 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
                    $notTemporaryCar = true;
                }
            } elseif($notParentORSubPark && $passage_is_month_access == 0) {
                // 非月租车 且 禁止临时车入场
                fdump_api(['msg' => '存在大小场 禁止临时车入场', 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
                $notTemporaryCar = true;
            }
        }
        if (!$notTemporaryCar && $isOverMonth) {
            // 月租车过期了
            // 1-月租车过期按临时车收费 2-月租车过期禁止入场 3-过期多少天后禁止入场
            $expire_month_car_type = isset($parkConfig['expire_month_car_type']) && $parkConfig['expire_month_car_type'] ? intval($parkConfig['expire_month_car_type']) : 0;
            $expire_month_car_day = isset($parkConfig['expire_month_car_day']) && $parkConfig['expire_month_car_day'] ? intval($parkConfig['expire_month_car_day']) : 0;

            if ($expire_month_car_type == 2 || ($expire_month_car_type == 3 && $expire_month_car_day <= 0)) {
                // 月租车过期禁止入场 或者 过期后0天直接禁止入场
                $notTemporaryCar = true;
                fdump_api(['msg' => '月租车过期禁止入场 或者 过期后0天直接禁止入场', 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
            } elseif ($expire_month_car_type == 3 && $expire_month_car_day > 0) {
                $expire_month_car_second = $expire_month_car_day * 86400;
                $month_end_time = $car_end_time;
                $expire_month_time_tmp = $nowTime - $month_end_time;
                if ($expire_month_time_tmp > $expire_month_car_second) {
                    // 月租车过期后N天直接禁止入场
                    $notTemporaryCar = true;
                    fdump_api(['msg' => '月租车过期后N天直接禁止入场', 'expire_month_car_second' => $expire_month_car_second, 'month_end_time' => $month_end_time, 'expire_month_time_tmp' => $expire_month_time_tmp, 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
                }
            }
        }
        if ($notTemporaryCar || (!empty($parkConfig) && empty($parkConfig['temp_in_park_type']))) {
            // 禁止临时车入场
            $showScreenData['car_type'] = 'temporary_type';
            $showScreenData['content'] = '临时车禁止通行';
            $showScreenData['voice_content'] = 7;
            if ($park_sys_type == 'A11') {
                (new CarFlowShowScreenService())->addA11ParkShowScreenLog($showScreenData);
            } else {
                (new CarFlowShowScreenService())->addParkShowScreenLog($showScreenData);
                $info = $car_number . '临时车禁止入场';
                $flow_status = '拒绝通行';
                $reason = '临时车禁止入场';
                $carHandleGetInfoService->pushMessageQy($info, $village_name, $car_number, $passage_info['passage_name'], $flow_status, '入口', $reason);
            }
            fdump_api(['msg' => '临时车禁止入场', 'line' => __LINE__, 'isTemporaryCar' => $isTemporaryCar, 'notTemporaryCar' => $notTemporaryCar, 'nowTime' => $nowTime, 'parkConfig' => $parkConfig, 'car_number' => $car_number, 'serialno' => $serialno, 'passage_info' => $passage_info, 'village_info' => $village_info, 'showScreenData' => $showScreenData], 'parkBetter/handlePlateResultLog' . $reportTime, true);
            return false;
        }
        $register_day = isset($parkConfig['register_day']) && $parkConfig['register_day'] ? intval($parkConfig['register_day']) : $this->register_day;
        $register_type = isset($parkConfig['register_type']) && $parkConfig['register_type'] ? intval($parkConfig['register_type']) : 0;
        if (!$isTemporaryCar && !empty($parkConfig) && $register_type == 1) {
            // 禁止未登记临时车入场
            $minAddTime = strtotime(date('Y-m-d 00:00:00', $nowTime)) - $register_day * 86400;
            $maxAddTime = strtotime(date('Y-m-d 23:59:59'));
            $car_visitor_info = $carHandleGetInfoService->getCarVisitorInfo($car_number, $village_id);
            $car_visitor_add_time = isset($car_visitor_info['add_time']) ? intval($car_visitor_info['add_time']) : 0;
            $hasVisitor = true;
            if ($car_visitor_add_time <= $minAddTime || $car_visitor_add_time >= $maxAddTime) {
                $car_visitor_info = [];
                $hasVisitor = false;
            }
            $carAddTime = isset($car_info['car_addtime']) && intval($car_info['car_addtime']) > 0 ? intval($car_info['car_addtime']) : 0;
            $examineTime = isset($car_info['examine_time']) && intval($car_info['examine_time']) > 0 ? intval($car_info['examine_time']) : 0;
            if ($examineTime > 0) {
                $carAddTime = $examineTime;
            }
            $examine_status = isset($car_info['examine_status']) && intval($car_info['examine_status']) > 0 ? intval($car_info['examine_status']) : 0;
            if ($carAddTime > $minAddTime && $carAddTime < $maxAddTime && $examine_status == 1) {
                $hasVisitor = true;
            }
            fdump_api(['msg' => '临时车需要登记才能进场', 'hasVisitor' => $hasVisitor, 'register_type' => $register_type, 'register_day' => $register_day, 'car_visitor_info' => $car_visitor_info, 'car_number' => $car_number, 'village_id' => $village_id, 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
            if (!$hasVisitor) {
                // 需要登记未登记的车牌禁止入场
                $showScreenData['car_type'] = 'temporary_type';
                $showScreenData['content'] = '临时车未登记，请扫码入场';
                $showScreenData['voice_content'] = 3;
                if ($park_sys_type == 'A11') {
                    (new CarFlowShowScreenService())->addA11ParkShowScreenLog($showScreenData);
                } else {
                    (new CarFlowShowScreenService())->addParkShowScreenLog($showScreenData);
                    $info = $car_number . '临时车未登记禁止入场';
                    $flow_status = '拒绝通行';
                    $reason = '临时车未登记禁止入场';
                    $carHandleGetInfoService->pushMessageQy($info, $village_name, $car_number, $passage_info['passage_name'], $flow_status, '入口', $reason);
                }
                fdump_api(['msg' => '临时车未登记禁止入场', 'line' => __LINE__, 'nowTime' => $nowTime, 'parkConfig' => $parkConfig, 'car_number' => $car_number, 'serialno' => $serialno, 'passage_info' => $passage_info, 'village_info' => $village_info, 'showScreenData' => $showScreenData], 'parkBetter/handlePlateResultLog' . $reportTime, true);
                return false;
            }
        }
        if ($isTemporaryCar || !empty($parkConfig)) {
            // todo 临时车登记入场 或 直接入场
            $accessMode = 8;
            $isTemporaryCar && $accessMode = 9;
            $register_type == 1 && $accessMode = 9;
            $flowIntInfo = $carHandleGetInfoService->checkCarFlowInt($car_number, $village_id, $garage_id, $nowTime, $park_sys_type);
            fdump_api(['msg' => '查询入场记录', 'flowIntInfo' => $flowIntInfo, 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
            $in_park_id = isset($flowIntInfo['id']) && intval($flowIntInfo['id']) > 0 ? intval($flowIntInfo['id']) : 0;
            $inParams['from_id'] = $in_park_id;
            $park_data['car_type'] = 'temporaryCar';
            $park_data['accessMode'] = $accessMode;
            $carHandleGetInfoService->recordCarFlowInt($park_data, $inParams);
            $showScreenData['content'] = '欢迎光临';
            $showScreenData['voice_content'] = 1;
            if ($park_sys_type == 'A11') {
                (new CarFlowShowScreenService())->addA11ParkShowScreenLog($showScreenData);
                $remark = '\n入场类型：临时车';
                if ($register_type == 1 && !$isTemporaryCar) {
                    $remark = '\n入场类型：临时车登记入场';
                }
                $sendParam = [
                    'garage_id' => $garage_id, 'end_time' => $car_end_time, 'car_garage_id' => $car_garage_id, 'stored_balance' => $stored_balance,
                    'car_id' => $car_id, 'car_user_phone' => $car_user_phone, 'park_name' => $village_name, 'passage_direction' => '入场',
                    'remark' => $remark, 'is_small' => false, 'property_id' => $property_id, 'nowTime' => $nowTime, 'park_time' => 0,
                ];
                $carHandleGetInfoService->sendCarMessage($car_number, $village_id, $sendParam);
            } else {
                (new CarFlowShowScreenService())->addParkShowScreenLog($showScreenData);
            }
            // 特殊车辆通行
            $printParams = unsetParam($park_data, ['imageFile', 'AlarmInfoPlate']);
            fdump_api(['msg' => '临时车登记入场或直接入场', 'line' => __LINE__, 'nowTime' => $nowTime, 'park_data' => $printParams, 'inParams' => $inParams, 'showScreenData' => $showScreenData], 'parkBetter/handlePlateResultLog' . $reportTime, true);
            return true;
        } else {
            $info = $car_number . '车场未配置禁止入场';
            $flow_status = '拒绝通行';
            $reason = '车场未配置禁止入场';
            $carHandleGetInfoService->pushMessageQy($info, $village_name, $car_number, $passage_info['passage_name'], $flow_status, '入口', $reason);
            fdump_api(['msg' => '车场未配置禁止入场', 'line' => __LINE__, 'nowTime' => $nowTime, 'parkConfig' => $parkConfig, 'car_number' => $car_number, 'serialno' => $serialno, 'passage_info' => $passage_info, 'village_info' => $village_info, 'params' => $params], 'parkBetter/handlePlateResultLog' . $reportTime, true);
        }
        return false;
    }

    /**
     * 出场处理然后反馈是否可以开闸.
     */
    public function carFlowOut($car_number, $serialno, $data, $passage_info = [], $village_info = [], $params = [])
    {
        $reportTime = isset($params['reportTime']) && $params['reportTime'] ? $params['reportTime'] : '';
        fdump_api(['msg' => '车辆出场', 'parameter' => func_get_args(), 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
        $park_sys_type = isset($passage_info['park_sys_type']) && $passage_info['park_sys_type'] ? trim($passage_info['park_sys_type']) : 'D3';
        $nowTime = isset($params['nowTime']) && $params['nowTime'] ? $params['nowTime'] : time();
        $village_id = isset($passage_info['village_id']) && $passage_info['village_id'] ? intval($passage_info['village_id']) : 0;
        $property_id = isset($village_info['property_id']) && $village_info['property_id'] ? intval($village_info['property_id']) : '';
        $village_name = isset($village_info['village_name']) && $village_info['village_name'] ? $village_info['village_name'] : '';
        $park_passage_id = isset($passage_info['id']) && $passage_info['id'] ? intval($passage_info['id']) : 0;

        $showScreenData = [];
        $showScreenData['passage'] = $passage_info;
        $showScreenData['village_id'] = $village_id;
        $showScreenData['car_number'] = $car_number;
        $showScreenData['channel_id'] = $serialno;
        $showScreenData['car_type'] = '';
        $showScreenData['voice_content'] = 1;
        $showScreenData['content'] = '欢迎光临';

        if (isset($data['AlarmInfoPlate']['result']['PlateResult']['type'])) {
            $car_number_type = trim($data['AlarmInfoPlate']['result']['PlateResult']['type']);
        } else {
            $car_number_type = '';
        }

        if (isset($passage_info['garage_id']) && intval($passage_info['garage_id']) > 0) {
            $garage_id = intval($passage_info['garage_id']);
        } elseif (isset($passage_info['area_type']) && isset($passage_info['passage_area']) && intval($passage_info['area_type']) == 2 && intval($passage_info['passage_area']) > 0) {
            $garage_id = intval($passage_info['passage_area']);
        } else {
            $garage_id = 0;
        }
        // todo 识别记录走队列
        $parkPlateResultLog = [];
        $parkPlateResultLog['car_number'] = $car_number;
        $parkPlateResultLog['channel_id'] = $serialno;
        $parkPlateResultLog['park_type'] = 2;
        $parkPlateResultLog['add_time'] = $nowTime;
        $parkPlateResultLog['village_id'] = $village_id;
        $parkPlateResultLog['park_sys_type'] = $park_sys_type;
        $parkPlateResultLog['park_passage_id'] = $park_passage_id;
        $parkPlateResultLog['garage_id'] = $garage_id;
        $parkPlateResultLog['car_number_type'] = $car_number_type;
        $parkPlateResultLog['jobType'] = 'plateResultLog';
        $this->traitCommonCarFlowPlateResult($parkPlateResultLog);
        // todo 识别记录走队列

        $out_time = $nowTime;
        $park_data = [];
        $park_data['car_number'] = $car_number;
        $park_data['out_time'] = $out_time;
        $park_data['is_out'] = 1;
        $park_data['is_paid'] = 1;
        $park_data['in_channel_id'] = $park_passage_id;
        $park_data['park_id'] = $village_id;
        $park_data['village_id'] = $village_id;
        $park_data['park_sys_type'] = $park_sys_type;
        $park_data['park_name'] = $village_name;
        $park_data['channel_number'] = $passage_info['channel_number'];
        $park_data['passage_name'] = $passage_info['passage_name'];
        $park_data['garage_id'] = $garage_id;
        $park_data['serialno'] = $serialno;

        $carHandleGetInfoService = new CarHandleGetInfoService();
        $in_park_info = $carHandleGetInfoService->getCarFlowInt($car_number, $village_id, $park_sys_type);
        fdump_api(['msg' => '车辆入场记录', 'in_park_info' => $in_park_info, 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);

        if (isset($in_park_info['in_time']) && intval($in_park_info['in_time']) > ($nowTime - 60)) {
            fdump_api(['msg' => '车辆入场和出场时间间隔必须大于60 避免由于识别到车辆尾部导致的意外开闸', 'park_data' => $park_data], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
            return false;
        }
        $in_time = isset($in_park_info['in_time']) && $in_park_info['in_time'] ? $in_park_info['in_time'] : 0;
        $order_id = isset($in_park_info['order_id']) && $in_park_info['order_id'] ? $in_park_info['order_id'] : '';
        $in_park_id = isset($in_park_info['id']) && $in_park_info['id'] ? intval($in_park_info['id']) : 0;
        $in_time && $park_data['in_time'] = $in_time;
        $order_id && $park_data['order_id'] = $order_id;
        
        // 查询判断是否是免费车
        $isFee = false;
        $serviceA11 = new A11Service();
        if ($park_sys_type == 'A11' && isset($car_info['parking_car_type']) && $car_info['parking_car_type'] == $serviceA11::Free_A) {
            // 车辆卡类为免费车时
            $isFee = true;
        }
        if (!$isFee) {
            $feeInfo = $carHandleGetInfoService->judgeFreeList($car_number, $village_id, $park_sys_type);
            if (!empty($feeInfo)) {
                $isFee = true;
            }
        }
        if ($in_time > $nowTime) {
            $park_time = intval($in_time - $nowTime);
        } else {
            $park_time = 0;
        }
        $park_time_text = '';
        if ($park_time > 0) {
            $showScreenData['duration'] = $park_time;
            $park_data['park_time'] = $park_time;
            $park_time_text = $carHandleGetInfoService->commonChangeTimeText($park_time);
            $park_time_text && $showScreenData['duration_txt']  = $park_time_text;
            $park_time_text && $park_data['park_time_stamp'] = $park_time_text;
        }
        $outParams = [
            'village_id' => $village_id, 'garage_id' => $garage_id, 'park_passage_id' => $park_passage_id,
            'flow_direction' => 2, 'device_number' => $passage_info['device_number'], 'park_sys_type' => $park_sys_type,
            'flow_time' => $nowTime, 'car_number' => $car_number,
        ];
        $car_info = $carHandleGetInfoService->getCarInfo($car_number, $village_id);
        $parking_car_type = isset($car_info['parking_car_type']) && intval($car_info['parking_car_type']) > 0 ? intval($car_info['parking_car_type']) : 0;
        if (isset($car_info['stored_balance']) && floatval($car_info['stored_balance']) > 0) {
            $showScreenData['stored_balance'] = $car_info['stored_balance'];
            $showScreenData['car_type'] = 'storedCar';
        } elseif ($park_sys_type == 'A11' && in_array($parking_car_type, [13, 14, 15, 16])) {
            $showScreenData['stored_balance'] = $car_info['stored_balance'];
            $showScreenData['car_type'] = 'storedCar';
        }
        $rightPosition = array();
        if ($car_info && isset($car_info['car_id'])) {

            $rightPosition = (new HouseVillageParkingService())->getCarRightInPosition($car_info, $passage_info['village_id'], $garage_id);
            fdump_api(['msg' => '获取车辆绑定的车位 最长停车时间', 'rightPosition' => $rightPosition, 'line' => __LINE__], 'parkBetter/handlePlateResultLog' . $reportTime, 1);
            if (!empty($rightPosition)) {
                if ($rightPosition['start_time'] <= $nowTime && $rightPosition['end_time'] >= $car_info['end_time']) {
                    $car_info['end_time'] = $rightPosition['end_time'];
                }
                if($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids']) && $rightPosition['start_time']>$nowTime){
                    //车位时间还没开始
                    $car_info['end_time']=0;
                }
                if (isset($rightPosition['position_id']) && $rightPosition['position_id']) {
                    $park_data['position_id'] = $rightPosition['position_id'];
                }
                if (isset($rightPosition['garage_id']) && $rightPosition['garage_id']) {
                    $park_data['garage_id'] = $rightPosition['garage_id'];
                }
                if (isset($rightPosition['position_num']) && $rightPosition['position_num']) {
                    $park_data['position_num'] = $rightPosition['position_num'];
                }
            } elseif (empty($rightPosition)) {
                $bind_position_ids=array();
                if($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids'])){
                    //新的绑定车位 多车多位逻辑，没有可有车位时
                    $bind_position_ids=explode(',',$car_info['bind_position_ids']);
                    $car_info['end_time']=0;   //没有可有车位时
                }
            }
            $park_data['car_id'] = $car_info['car_id'];
        }
        
        if (isset($data['AlarmInfoPlate']['result']['PlateResult']['imageFile']) && trim($data['AlarmInfoPlate']['result']['PlateResult']['imageFile'])) {
            $imageFile = trim($data['AlarmInfoPlate']['result']['PlateResult']['imageFile']);
            $park_data['imageFile'] = $imageFile;
        }
        if (isset($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile']) && trim($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile'])) {
            $imageFragmentFile = trim($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile']);
            $park_data['imageFragmentFile'] = $imageFragmentFile;
        }
        $rand_num = date('Ymd', $nowTime);// 换成日期存储
        $path = '/upload/park_log/' . $rand_num . '/' . $serialno . '/';
        $up_dir = $_SERVER['DOCUMENT_ROOT'] . $path;
        $park_data['imageSavePath'] = $up_dir;
        $car_garage_id = isset($car_info['garage_id']) ? intval($car_info['garage_id']) : 0;
        $car_end_time = isset($car_info['end_time']) ? intval($car_info['end_time']) : 0;
        $stored_balance = isset($car_info['stored_balance']) && floatval($car_info['stored_balance']) > 0 ? $car_info['stored_balance'] : 0;
        $car_id = isset($car_info['car_id']) ? intval($car_info['car_id']) : 0;
        $car_user_phone = isset($car_info['car_user_phone']) && $car_info['car_user_phone'] ? trim($car_info['car_user_phone']) : '';
        $car_position_id = isset($car_info['car_position_id']) ? intval($car_info['car_position_id']) : 0;
        $park_data['car_position_id'] = $car_position_id;
        
        if ($isFee) {
            // todo 免费车 直接反馈开闸 并记录
            $accessMode = 3;
            $pay_order_id = isset($in_park_info['pay_order_id']) && intval($in_park_info['pay_order_id']) > 0 ? intval($in_park_info['pay_order_id']) : 0;
            $outParams['order_id'] = $order_id;
            $park_data['total'] = 0;
            $park_data['pay_type'] = $this->pay_type[5];
            $park_data['pay_order_id'] = $pay_order_id;
            $park_data['in_park_id'] = $in_park_id;
            $park_data['accessMode'] = $accessMode;
            if(!empty($rightPosition)){
                $park_data['isRightPosition'] = 1;
            }
            $carHandleGetInfoService->recordCarFlowOut($park_data, $outParams);
            $showScreenData['content'] = '免费车通行';
            if ($park_sys_type == 'A11') {
                (new CarFlowShowScreenService())->addA11ParkShowScreenLog($showScreenData);
                $sendParam = [
                    'garage_id' => $garage_id, 'end_time' => $car_end_time, 'car_garage_id' => $car_garage_id, 
                    'stored_balance' => $stored_balance, 'car_id' => $car_id, 'car_user_phone' => $car_user_phone, 
                    'park_name' => $village_name, 'passage_direction' => '出场', 'remark' => '\n出场类型：免费车', 
                    'is_small' => false, 'property_id' => $property_id, 'nowTime' => $nowTime, 'park_time' => $park_time_text,
                ];
                $carHandleGetInfoService->sendCarMessage($car_number, $village_id, $sendParam);
            } else {
                (new CarFlowShowScreenService())->addParkShowScreenLog($showScreenData);
            }
            // 免费车辆通行
            fdump_api(['msg' => '免费车通行', 'line' => __LINE__, 'nowTime' => $nowTime, 'park_data' => $park_data, 'outParams' => $outParams, 'showScreenData' => $showScreenData], 'parkBetter/handlePlateResultLog' . $reportTime, true);
            return true;
        }
        // 查询入场最后一次出场的对应入场记录
        $whereLastRecord = [];
        $whereLastRecord[] = ['flow_direction', '=', 2];
        $lastRecord = $carHandleGetInfoService->getLastCarFlowRecord($car_number, $village_id, $park_sys_type, $whereLastRecord);
        $out_park_passage_id = isset($lastRecord['park_passage_id']) ? intval($lastRecord['park_passage_id']) : 0;
        !$out_park_passage_id && $out_park_passage_id = isset($lastRecord['out_channel_id']) ? $lastRecord['out_channel_id'] : 0;
        $last_out_time = isset($lastRecord['out_time']) ? intval($lastRecord['out_time']) : 0;
        $last_order_id = isset($lastRecord['order_id']) ? trim($lastRecord['order_id']) : '';
        $last_in_time = isset($lastRecord['in_time']) ? intval($lastRecord['in_time']) : 0;
        $last_park_time = isset($lastRecord['park_time']) ? intval($lastRecord['park_time']) : 0;
        // 相同车道短时间重复出场直接出场限制
        $samePassageOutLimitTime = $nowTime - 60;
        if ($last_out_time > $samePassageOutLimitTime && $last_out_time < $nowTime && $park_passage_id == $out_park_passage_id) {
            //放行
            fdump_api(['msg' => '同一个车道 短时间重复出场直接抬杆', 'line' => __LINE__, 'nowTime' => $nowTime, 'park_data' => $park_data, 'lastRecord' => $lastRecord], 'parkBetter/handlePlateResultLog' . $reportTime, true);
            if (isset($park_data['position_id']) && intval($park_data['position_id']) > 0) {
                $position_id = intval($park_data['position_id']);
                $access_record_id = isset($lastRecord['access_record_id']) ? intval($lastRecord['access_record_id']) : 0;
                $whereArr = array('position_id' => $position_id);
                $saveArr = array('use_car_id' => $car_info['car_id'], 'use_start_time' => $nowTime, 'use_car_number' => $car_number, 'access_record_id' => $access_record_id);
                $jobParams = [
                    'jobType' => 'handleUseParkingPosition', 'village_id' => $village_id, 'car_id' => $car_id,
                    'nowTime' => $nowTime, 'position_id' => $position_id, 'car_number' => $car_number,
                    'where' => $whereArr, 'updateData' => $saveArr,
                ];
                (new ParkCarToJobService())->HandleUseParkingPositionToJob($jobParams);
            }
            return true;
        }

        // todo A11存在大小场 逻辑单独处理
        /** @var bool isMonthCar 是否允许直接月租车出场 */
        $isMonthCar = false;
        /** @var bool isOutSubPark 是否小场（子停车场）出场 */
        $isOutSubPark = false;
        /** @var bool isOutParentPark 是否大场（父停车场）出场 */
        $isOutParentPark = false;
        /** @var bool notParentORSubPark 是否不是大小（父子）场 */
        $notParentORSubPark = true;
        /** @var bool isOverMonth 当前车辆是否为已过期月租车 */
        $isOverMonth = false;
        /** @var bool hasMonth 当前车辆是否为为未过期月租车 */
        $hasMonth = false;
        /** @var bool isSameGarage 车辆所属车场和通行车道所在车场是否为相同 */
        $isSameGarage = false;
        if ($car_end_time >= $nowTime) {
            $hasMonth = true;
        }
        if (!$hasMonth && $car_end_time > 100) {
            $isOverMonth = true;
        }
        $monthRemark = '';
        $passage_garage_id = $garage_id;
        $sub_passage_garage_id = 0;
        $sub_passage_garage_info = [];
        $passage_garage_info = [];
        $out_passage_info = [];
        $outParkParkingGarage = [];
        if ($park_sys_type == 'A11') {
            $passage_garage_info = $carHandleGetInfoService->getParkingGarage($passage_garage_id, $village_id);
            if (isset($passage_garage_info['fid']) && intval($passage_garage_info['fid']) > 0) {
                // 通过的车道是 小场（子停车场）出场
                $isOutParentPark = false;
                $isOutSubPark = true;
                $notParentORSubPark = false;
            } elseif ($passage_garage_id > 0) {
                $sub_passage_garage_info = $carHandleGetInfoService->getSubParkingGarage($passage_garage_id, $village_id);
                if (isset($sub_passage_garage_info['garage_id']) && intval($sub_passage_garage_info['garage_id']) > 0) {
                    // 通过的车道是 大场（父停车场）出场
                    $sub_passage_garage_id = intval($sub_passage_garage_info['garage_id']);
                    $isOutParentPark = true;
                    $isOutSubPark = false;
                    $notParentORSubPark = false;
                }
            }
            if (! $car_garage_id) {
                $position_info = $carHandleGetInfoService->getCarPosition($car_position_id, $village_id);
                isset($position_info['garage_id']) && $position_info['garage_id'] && $car_garage_id = intval($position_info['garage_id']);
            }
            if ($passage_garage_id && $car_garage_id && $car_garage_id == $passage_garage_id) {
                $isSameGarage = true;
            }
            if ($hasMonth && ($notParentORSubPark || $isOutSubPark) && $isSameGarage) {
                // 月租车有效时间内 且 （出场通道为小场或者非大小场车场）且 出场车场和车辆所属为相同车场 月租车出场
                $monthRemark = '\n支付金额： 0(月租车出场)';
                $isMonthCar = true;
            }
            // 上一次出场的通道所属车场信息获取
            $out_passage_info = $carHandleGetInfoService->getParkPassageInfoById($out_park_passage_id, $village_id);
            if (isset($out_passage_info['garage_id']) && intval($out_passage_info['garage_id']) > 0) {
                /** @var int out_park_garage_id 上次出场通道所属车场id */
                $out_park_garage_id = intval($out_passage_info['garage_id']);
            } elseif (isset($out_passage_info['area_type']) && isset($out_passage_info['passage_area']) && intval($out_passage_info['area_type']) == 2 && intval($out_passage_info['passage_area']) > 0) {
                $out_park_garage_id = intval($out_passage_info['passage_area']);
            } else {
                $out_park_garage_id = 0;
            }
            $outParkParkingGarage = $carHandleGetInfoService->getParkingGarage($out_park_garage_id, $village_id);
            /** @var int is_month_charge 月租车进入非月租场 是否收费 0-不收费 1-收费 */
            $is_month_charge = isset($outParkParkingGarage['is_month_charge']) ? intval($outParkParkingGarage['is_month_charge']) : 0;
            if (!$isMonthCar && $isOutParentPark && $hasMonth && $isSameGarage && (empty($lastRecord) || $out_park_garage_id == $passage_garage_id)) {
                // 月租车有效时间内 且 出场通道为大场 且 且出场车场和车辆所属为相同车场 且 （无出场记录或者上次出场记录为相同车场）直接以月租车出场
                $monthRemark = '\n支付金额： 0(月租车出场)';
                $isMonthCar = true;
            }
            if(!$isMonthCar && $isOutParentPark && $hasMonth && $isSameGarage && $is_month_charge != 1){
                // 月租车有效时间内 且 出场通道为大场 且 且出场车场和车辆所属为相同车场 且 小场（上次出场停车场）[月租车进入非月租场]不收费 直接月租车出场
                $monthRemark = '\n支付金额： 0(月租车出场)';
                $isMonthCar = true;
            }
            if(!$isMonthCar && $isOutParentPark && $hasMonth && $isSameGarage && $is_month_charge == 1){
                // 月租车有效时间内 且 出场通道为大场 且 且出场车场和车辆所属为相同车场 且 小场（上次出场停车场）[月租车进入非月租场]收费
                $total = isset($lastRecord['total']) && floatval($lastRecord['total']) > 0 ? $lastRecord['total'] : 0;

                if (! $last_park_time && $last_in_time && $last_out_time) {
                    $last_park_time = $last_out_time - $last_in_time;
                }
                $last_time = '';
                if (intval($last_park_time) > 0) {
                    $last_time = $carHandleGetInfoService->commonChangeTimeText($last_park_time);
                } else {
                    $last_park_time = 0;
                }
                
                if ($total > 0 && $stored_balance > $total) {
                    // 非月租车 且需要支付 上一个停车场（一般是小场未结算费用）的费用 且 车辆存储余额足以支付直接进行抵扣
                    $flow_record_id = isset($lastRecord['flow_record_id']) ? intval($lastRecord['flow_record_id']) : 0;
                    $out_park_id = isset($lastRecord['id']) ? intval($lastRecord['id']) : 0;
                    $last_park_data = $park_data;
                    $lastOutParams = $outParams;
                    $last_park_data['operateType'] = 'updateRecord';
                    $last_park_data['flow_record_id'] = $flow_record_id;
                    $last_park_data['out_park_passage_id'] = isset($lastRecord['park_passage_id']) ? intval($lastRecord['park_passage_id']) : 0;
                    $last_park_data['out_park_id'] = $out_park_id;
                    $last_park_data['last_out_time'] = $last_out_time;
                    $last_park_data['last_order_id'] = $last_order_id;
                    $last_park_data['total'] = $total;
                    $last_park_data['stored_balance'] = getFormatNumber($stored_balance - $total);
                    $last_park_data['pay_type'] = $this->pay_type[1];
                    $last_park_data['is_paid'] = 1;
                    $last_park_data['pay_time'] = $nowTime;
                    $last_park_data['car_type'] = 'storedCar';
                    if(!empty($rightPosition)){
                        $last_park_data['isRightPosition'] = 1;
                    }
                    unset($last_park_data['out_time'], $last_park_data['in_channel_id'], $last_park_data['channel_number'], $last_park_data['passage_name']);
                    unset($lastOutParams['flow_time'], $lastOutParams['device_number']);
                    $carHandleGetInfoService->recordCarFlowOut($last_park_data, $lastOutParams);
                    // 上面结清小场费用 下面大场直接月租车出场
                    $accessMode = 5;
                    $pay_order_id = isset($in_park_info['pay_order_id']) && intval($in_park_info['pay_order_id']) > 0 ? intval($in_park_info['pay_order_id']) : 0;
                    $outParams['order_id'] = $order_id;
                    $park_data['accessMode'] = $accessMode;
                    $park_data['total'] = 0;
                    $park_data['pay_type'] = $this->pay_type[4];
                    $park_data['pay_order_id'] = $pay_order_id;
                    $park_data['in_park_id'] = $in_park_id;
                    $park_data['car_type'] = 'monthCar';
                    if(!empty($rightPosition)){
                        $park_data['isRightPosition'] = 1;
                    }
                    $carHandleGetInfoService->recordCarFlowOut($park_data, $outParams);
                    
                    // TODO:屏显和语音提示特殊车辆通行
                    $showScreenData['duration_txt']  = $last_time;
                    $showScreenData['duration']      = $last_park_time;
                    $showScreenData['price']         = $total;
                    $showScreenData['voice_content'] = 9;
                    $showScreenData['content']       = '';
                    if ($park_sys_type == 'A11') {
                        (new CarFlowShowScreenService())->addA11ParkShowScreenLog($showScreenData);
                        $remarkTxt = '\n支付金额：'.$total.'元（储值抵扣）';
                        $sendParam = [
                            'garage_id' => $garage_id, 'end_time' => $car_end_time, 'car_garage_id' => $car_garage_id,
                            'stored_balance' => $stored_balance, 'car_id' => $car_id, 'car_user_phone' => $car_user_phone,
                            'park_name' => $village_name, 'passage_direction' => '大场月租车出场', 'remark' => $remarkTxt,
                            'is_small' => false, 'property_id' => $property_id, 'nowTime' => $nowTime, 'park_time' => $park_time_text,
                        ];
                        $carHandleGetInfoService->sendCarMessage($car_number, $village_id, $sendParam);
                    } else {
                        (new CarFlowShowScreenService())->addParkShowScreenLog($showScreenData);
                    }
                    fdump_api(['msg' => '月租车-大场离场-小场费用储值抵扣-直接通行', 'line' => __LINE__, 'nowTime' => $nowTime,  'sendParam' => $sendParam, 'park_data' => $park_data, 'showScreenData' => $showScreenData], 'parkBetter/handlePlateResultLog' . $reportTime, true);
                    return true;
                }

                if ($total > 0) {
                    // 停车小场（子停车场）费用未结清 需缴费通行
                    //TODO:屏显和语音提示错误信息
                    $showScreenData['voice_content'] = 5;
                    $showScreenData['content'] = '';
                    $showScreenData['car_type'] = 'temporary_type';
                    $showScreenData['duration_txt'] = $last_time;
                    $showScreenData['duration'] = $last_park_time;
                    $showScreenData['price'] = $total;
                    if ($park_sys_type == 'A11') {
                        (new CarFlowShowScreenService())->addA11ParkShowScreenLog($showScreenData);
                    } else {
                        (new CarFlowShowScreenService())->addParkShowScreenLog($showScreenData);
                    }
                    fdump_api(['msg' => '月租车-大场离场-小场费用需缴费通行', 'line' => __LINE__, 'nowTime' => $nowTime, 'park_data' => $park_data, 'showScreenData' => $showScreenData], 'parkBetter/handlePlateResultLog' . $reportTime, true);
                    return false;
                }
                
                if ($total <= 0) {
                    // 月租车 小场收费金额为0 月租车出场
                    $monthRemark = '\n支付金额： 0(月租车出场)';
                    $isMonthCar = true;
                }
            }
        }
        if (!$isMonthCar && $park_sys_type == 'D3' && $hasMonth) {
            // todo 判断车辆为月租车 直接反馈开闸 并记录
            $isMonthCar = true;
        }
        if (isset($park_data['garage_id']) && intval($park_data['garage_id']) > 0) {
            $parkConfig = $carHandleGetInfoService->getParkConfig($village_id, intval($park_data['garage_id']));
        } else {
            $parkConfig = $carHandleGetInfoService->getParkConfig($village_id, $garage_id);
        }
        // 允许重复离场时长（单位：分钟）
        $out_park_time_limit = $nowTime - $parkConfig['out_park_time'] * 60;
        // 是否是重复出场
        $isRepeatOut = false;
        if (isset($lastRecord['out_time']) && intval($lastRecord['out_time']) > 0 && intval($lastRecord['out_time']) > intval($out_park_time_limit)) {
            $isRepeatOut = true;
        }
        if ($isMonthCar && $isRepeatOut) {
            // todo 判断是否为重复离场 暂时不重复记录出场
            //TODO:屏显和语音提示车辆通行
            $showScreenData['car_type'] = 'month_type';
            $showScreenData['end_time'] = $car_info['end_time'];
            $showScreenData['end_time_txt'] = date('Y-m-d', $car_info['end_time']);
            if ($park_sys_type == 'A11') {
                (new CarFlowShowScreenService())->addA11ParkShowScreenLog($showScreenData);
            } else {
                (new CarFlowShowScreenService())->addParkShowScreenLog($showScreenData);
            }
            fdump_api(['msg' => '月租车-重复离场-直接通行', 'line' => __LINE__, 'nowTime' => $nowTime, 'park_data' => $park_data, 'showScreenData' => $showScreenData], 'parkBetter/handlePlateResultLog' . $reportTime, true);
            if (isset($park_data['position_id']) && intval($park_data['position_id']) > 0) {
                $position_id = intval($park_data['position_id']);
                $access_record_id = isset($lastRecord['access_record_id']) ? intval($lastRecord['access_record_id']) : 0;
                $whereArr = array('position_id' => $position_id);
                $saveArr = array('use_car_id' => $car_info['car_id'], 'use_start_time' => $nowTime, 'use_car_number' => $car_number, 'access_record_id' => $access_record_id);
                $jobParams = [
                    'jobType' => 'handleUseParkingPosition', 'village_id' => $village_id, 'car_id' => $car_id,
                    'nowTime' => $nowTime, 'position_id' => $position_id, 'car_number' => $car_number,
                    'where' => $whereArr, 'updateData' => $saveArr,
                ];
                (new ParkCarToJobService())->HandleUseParkingPositionToJob($jobParams);
            }
            return true;
        }
        if ($isRepeatOut) {
            // 以上车类型都不是 那么就是临时车
            // todo 判断是否为重复离场 暂时不重复记录出场
            //TODO:屏显和语音提示车辆通行
            $showScreenData['car_type'] = 'temporary_type';
            $showScreenData['duration_txt'] = 0;
            $showScreenData['duration'] = 0;
            $showScreenData['price'] = 0;
            if ($park_sys_type == 'A11') {
                (new CarFlowShowScreenService())->addA11ParkShowScreenLog($showScreenData);
            } else {
                (new CarFlowShowScreenService())->addParkShowScreenLog($showScreenData);
            }
            fdump_api(['msg' => '临时车-重复离场-直接通行', 'line' => __LINE__, 'nowTime' => $nowTime, 'park_data' => $park_data, 'showScreenData' => $showScreenData], 'parkBetter/handlePlateResultLog' . $reportTime, true);
            if (isset($park_data['position_id']) && intval($park_data['position_id']) > 0) {
                $position_id = intval($park_data['position_id']);
                $access_record_id = isset($lastRecord['access_record_id']) ? intval($lastRecord['access_record_id']) : 0;
                $whereArr = array('position_id' => $position_id);
                $saveArr = array('use_car_id' => $car_info['car_id'], 'use_start_time' => $nowTime, 'use_car_number' => $car_number, 'access_record_id' => $access_record_id);
                $jobParams = [
                    'jobType' => 'handleUseParkingPosition', 'village_id' => $village_id, 'car_id' => $car_id,
                    'nowTime' => $nowTime, 'position_id' => $position_id, 'car_number' => $car_number,
                    'where' => $whereArr, 'updateData' => $saveArr,
                ];
                (new ParkCarToJobService())->HandleUseParkingPositionToJob($jobParams);
            }
            return true;
        }
        if ($isMonthCar) {
            // todo 判断车辆为月租车 直接反馈开闸 并记录
            $accessMode = 5;
            $pay_order_id = isset($in_park_info['pay_order_id']) && intval($in_park_info['pay_order_id']) > 0 ? intval($in_park_info['pay_order_id']) : 0;
            $outParams['order_id'] = $order_id;
            $park_data['accessMode'] = $accessMode;
            $park_data['total'] = 0;
            $park_data['pay_type'] = $this->pay_type[4];
            $park_data['pay_order_id'] = $pay_order_id;
            $park_data['in_park_id'] = $in_park_id;
            $park_data['car_type'] = 'monthCar';
            $carHandleGetInfoService->recordCarFlowOut($park_data, $outParams);
            // TODO:屏显和语音提示特殊车辆通行
            $showScreenData['voice_content'] = 4;
            $showScreenData['content'] = '';
            $showScreenData['car_type'] = 'month_type';
            $showScreenData['end_time'] = $car_info['end_time'];
            $showScreenData['end_time_txt'] = date('Y-m-d', $car_info['end_time']);

            if ($park_sys_type == 'A11') {
                (new CarFlowShowScreenService())->addA11ParkShowScreenLog($showScreenData);
                $sendParam = [
                    'garage_id' => $garage_id, 'end_time' => $car_end_time, 'car_garage_id' => $car_garage_id,
                    'stored_balance' => $stored_balance, 'car_id' => $car_id, 'car_user_phone' => $car_user_phone,
                    'park_name' => $village_name, 'passage_direction' => '出场', 'remark' => $monthRemark,
                    'is_small' => false, 'property_id' => $property_id, 'nowTime' => $nowTime, 'park_time' => $park_time_text,
                ];
                $carHandleGetInfoService->sendCarMessage($car_number, $village_id, $sendParam);
            } else {
                (new CarFlowShowScreenService())->addParkShowScreenLog($showScreenData);
            }
            // 月租车通行
            fdump_api(['msg' => '月租车通行', 'line' => __LINE__, 'nowTime' => $nowTime, 'car_info' => $car_info, 'park_data' => $park_data, 'outParams' => $outParams, 'showScreenData' => $showScreenData], 'parkBetter/handlePlateResultLog' . $reportTime, true);
            return true;
        }
        $lastTimeLimit = 0;
        if (!empty($lastRecord)) {
            isset($lastRecord['flow_direction']) && intval($lastRecord['flow_direction']) == 1 && $lastTimeLimit = intval($lastRecord['in_time']);
            isset($lastRecord['flow_direction']) && intval($lastRecord['flow_direction']) == 2 && $lastTimeLimit = intval($lastRecord['out_time']);
            (!isset($lastRecord['flow_direction']) || !$lastRecord['flow_direction']) && $lastTimeLimit = intval($lastRecord['out_time']);
        }
        fdump_api(['msg' => '界限时间', 'line' => __LINE__, 'nowTime' => $nowTime, 'lastTimeLimit' => $lastTimeLimit], 'parkBetter/handlePlateResultLog' . $reportTime, true);
        if (empty($in_park_info) || !isset($in_park_info['id']) || !$in_park_info['id']) {
            //todo 无对应入场纪录，根据车牌推送信息计算停车费用
            $recordLogParam = [
                'car_number' => $car_number, 'garage_id' => 0, 'flow_direction' => 0, 'park_sys_type' => $park_sys_type,
            ];
            $recordLog = $carHandleGetInfoService->getFlowIdentifyRecord($village_id, $recordLogParam);
            $db_park_passage = new ParkPassage();
            $catch_in_time = $nowTime;
            $allRecord = true;
            $catchInfo = false;
            if (!empty($recordLog)) {
                // 取车辆识别入场通道信息
                $whereLimitPassage = [
                    'village_id' => $village_id, 'device_number' => $recordLog['channel_id'], 'park_sys_type' => $park_sys_type
                ];
                $passageIn = $db_park_passage->getFind($whereLimitPassage);
                if ($passageIn && !is_array($passageIn)) {
                    $passageIn = $passageIn->toArray();
                }
                if (!empty($passageIn) && ($lastTimeLimit < intval($recordLog['add_time']))) {
                    // todo 存在上次识别通道信息 并且对应车牌最近识别通道信息或者说是出场时间小于记录时间
                    $catch_in_time = $recordLog['add_time'];
                    $allRecord = false;
                    $catchInfo = true;
                } elseif (!empty($passageIn) && $lastTimeLimit > intval($recordLog['add_time'])) {
                    $catch_in_time = $lastTimeLimit;
                    $allRecord = false;
                    $catchInfo = true;
                }
            }
            fdump_api(['msg' => '处理判断', 'line' => __LINE__, 'nowTime' => $nowTime, 'allRecord' => $allRecord, 'catchInfo' => $catchInfo], 'parkBetter/handlePlateResultLog' . $reportTime, true);
            if ($catchInfo) {
                // todo 对应小区没有车辆入场记录【todo 以当前时间[时间取自识别记录，没有就以当前时间]生成一个入场记录 后续根据配置进行处理】
                $whereLimitPassage = [
                    'village_id' => $village_id, 'status' => 1, 'passage_direction' => 1, 'park_sys_type' => $park_sys_type
                ];
                $passageList = $db_park_passage->getLists($whereLimitPassage, true, 1, 2);
                if ($passageList && !is_array($passageList)) {
                    $passageList = $passageList->toArray();
                }
                $in_park_passage_id = 0;
                $in_channel_number = '';
                $in_passage_name = '';
                $in_garage_id = 0;
                $in_device_number = '';
                if (count($passageList) == 1) {
                    $passageIn = reset($passageList);
                    $in_park_passage_id = isset($passageIn['id']) && $passageIn['id'] ? intval($passageIn['id']) : 0;
                    $in_channel_number = isset($passageIn['channel_number']) && $passageIn['channel_number'] ? intval($passageIn['channel_number']) : 0;
                    $in_passage_name = isset($passageIn['passage_name']) && $passageIn['passage_name'] ? trim($passageIn['passage_name']) : '';
                    $in_device_number = isset($passageIn['device_number']) && $passageIn['device_number'] ? trim($passageIn['device_number']) : '';
                    if (isset($passageIn['garage_id']) && intval($passageIn['garage_id']) > 0) {
                        $in_garage_id = intval($passageIn['garage_id']);
                    } elseif (isset($passageIn['area_type']) && isset($passageIn['passage_area']) && intval($passageIn['area_type']) == 2 && intval($passageIn['passage_area']) > 0) {
                        $in_garage_id = intval($passageIn['passage_area']);
                    } else {
                        $in_garage_id = 0;
                    }
                }
                $order_id = md5(uniqid().$car_number.$nowTime);
                $in_park_data = [];
                $in_park_data['car_number'] = $car_number;
                $in_park_data['in_time'] = $catch_in_time;
                $in_park_data['order_id'] = $order_id;
                $in_park_data['in_channel_id'] = $in_park_passage_id;
                $in_park_data['is_paid'] = 0;
                $in_park_data['park_id'] = $village_id;
                $in_park_data['village_id'] = $village_id;
                $in_park_data['park_sys_type'] = $park_sys_type;
                $in_park_data['park_name'] = $village_name;
                $in_park_data['channel_number'] = $in_channel_number;
                $in_park_data['passage_name'] = $in_passage_name;
                $in_park_data['exception_type'] = 1;
                $inParams = [
                    'village_id' => $village_id, 'garage_id' => $in_garage_id, 'park_passage_id' => $in_park_passage_id,
                    'flow_direction' => 1, 'device_number' => $in_device_number, 'park_sys_type' => $park_sys_type,
                    'flow_time' => $catch_in_time, 'car_number' => $car_number, 'park_name' => $village_name,
                ];
                $accessMode = 9;
                $inParams['accessMode'] = $accessMode;
                $recordData = $carHandleGetInfoService->recordCarFlowInt($in_park_data, $inParams);
                if ($allRecord && $park_sys_type != 'A11') {
                    // todo 记录出场信息
                    $accessMode = 3;
                    $outParams['order_id'] = $order_id;
                    $park_data['accessMode'] = $accessMode;
                    $park_data['total'] = 0;
                    $park_data['pay_type'] = $this->pay_type[5];
                    $park_data['in_time'] = $catch_in_time;
                    $carHandleGetInfoService->recordCarFlowOut($park_data, $outParams);
                    // TODO:屏显和语音提示特殊车辆通行
                    $showScreenData['car_type'] = 'temporary_type';
                    $showScreenData['duration_txt'] = '';
                    $showScreenData['duration'] = 0;
                    $showScreenData['price'] = 0;
                    (new CarFlowShowScreenService())->addParkShowScreenLog($showScreenData);
                    // 无入场记录 自动放行
                    fdump_api(['msg' => '无入场记录(也无入场识别记录)-自动放行', 'line' => __LINE__, 'nowTime' => $nowTime, 'park_data' => $park_data, 'outParams' => $outParams, 'showScreenData' => $showScreenData], 'parkBetter/handlePlateResultLog' . $reportTime, true);
                    return true;
                }
                // 手动填充入场记录.
                $is_first = 0;
                $db_house_village_car_flow_record = new HouseVillageCarFlowRecord();
                $whereFlowCount = [];
                $whereFlowCount[] = ['car_number', '=', $car_number];
                $whereFlowCount[] = ['village_id', '=', $village_id];
                $flowCount = $db_house_village_car_flow_record->getCount($whereFlowCount);
                if ($flowCount <= 1) {
                    $db_in_park = new InPark();
                    $park_count = $db_in_park->getCount(['car_number' => $car_number, 'park_id' => $village_id, 'del_time' => 0]);
                    if ($park_count <= 1) {
                        $is_first = 1;
                    }
                }
                $flow_record_id = isset($recordData['flow_record_id']) && $recordData['flow_record_id'] ? $recordData['flow_record_id'] : 0;
                $in_park_info = [
                    'order_id' => $order_id, 'car_number' => $car_number, 'park_id' => $village_id, 'in_time' => $catch_in_time,
                    'flow_record_id' => $flow_record_id, 'park_sys_type' => $park_sys_type, 'is_first' => $is_first,
                    'park_name' => $park_data['park_name'],
                ];
                fdump_api(['msg' => '手动填充入场记录', 'line' => __LINE__, 'nowTime' => $nowTime, 'in_park_info' => $in_park_info], 'parkBetter/handlePlateResultLog' . $reportTime, true);
            }
        }
        $pay_info = [];
        $isComputeMoney = false;
        // 计算费用
        $payData = [
            'car_number' => $car_number, 'village_id' => $village_id,
            'park_id' => $village_id, 'garage_id' => $garage_id,
            'park_sys_type' => $park_sys_type, 'lastTimeLimit' => $lastTimeLimit,
            'nowTime' => $nowTime, 'device_number' => $serialno,
            'park_name' => $village_name, 'car_number_type' => $car_number_type,
            'isOutSubPark' => $isOutSubPark, 'isOutParentPark' => $isOutParentPark,
            'notParentORSubPark' => $notParentORSubPark, 'isOverMonth' => $isOverMonth,
            'isSameGarage' => $isSameGarage, 'car_garage_id' => $car_garage_id,
            'passage_garage_id' => $passage_garage_id,
        ];
        if ($isOutSubPark) {
            $isComputeMoney = true;
            // todo 小场临时车出场 查询费用
            fdump_api(['msg' => '1-计算停车费用-A11', 'line' => __LINE__, 'nowTime' => $nowTime, 'payData' => $payData, 'in_park_info' => $in_park_info, 'parkConfig' => $parkConfig, 'car_info' => $car_info], 'parkBetter/handlePlateResultLog' . $reportTime, true);
            $pay_info = $carHandleGetInfoService->getA11TmpCarPay($payData, $in_park_info, $parkConfig, $car_info);
            $flow_temp_id = isset($pay_info['flow_temp_id']) ? intval($pay_info['flow_temp_id']) : 0;
            $park_data['flow_temp_id'] = $flow_temp_id;
            fdump_api(['msg' => '2-计算停车费用-A11', 'line' => __LINE__, 'nowTime' => $nowTime, 'pay_info' => $pay_info], 'parkBetter/handlePlateResultLog' . $reportTime, true);
            $pay_money = isset($pay_info['pay_money']) && floatval($pay_info['pay_money']) > 0 ? $pay_info['pay_money'] : 0;
            $time = isset($pay_info['time']) ? trim($pay_info['time']) : '';
            $park_time = isset($pay_info['park_time']) ? intval($pay_info['park_time']) : 0;
            $park_data['coupon_id'] = isset($pay_info['coupon_id']) ? $pay_info['coupon_id'] : 0;
            // todo 记录出场信息
            $accessMode = 3;
            $outParams['order_id'] = $order_id;
            $park_data['accessMode'] = $accessMode;
            $park_data['is_paid'] = 0;
            $park_data['pay_time'] = 0;
            $park_data['total'] = $pay_money;
            $park_data['in_time'] = $catch_in_time;
            $carHandleGetInfoService->recordCarFlowOut($park_data, $outParams);
            // TODO:屏显和语音提示特殊车辆通行
            $showScreenData['car_type'] = 'temporary_type';
            $showScreenData['duration_txt'] = $time;
            $showScreenData['duration'] = $park_time;
            $showScreenData['price'] = $pay_money;
            (new CarFlowShowScreenService())->addA11ParkShowScreenLog($showScreenData);
            $sendParam = [
                'garage_id' => $garage_id, 'end_time' => $car_end_time, 'car_garage_id' => $car_garage_id,
                'stored_balance' => $stored_balance, 'car_id' => $car_id, 'car_user_phone' => $car_user_phone,
                'park_name' => $village_name, 'passage_direction' => '出场', 'remark' => '\n待支付金额：' . $pay_money . '（小场出场）',
                'is_small' => false, 'property_id' => $property_id, 'nowTime' => $nowTime, 'park_time' => $time,
            ];
            $carHandleGetInfoService->sendCarMessage($car_number, $village_id, $sendParam);
            // 无入场记录 自动放行
            fdump_api(['msg' => '小场临时车出场-计算完费用记录后-自动放行', 'line' => __LINE__, 'nowTime' => $nowTime, 'park_data' => $park_data, 'outParams' => $outParams, 'showScreenData' => $showScreenData], 'parkBetter/handlePlateResultLog' . $reportTime, true);
            return true;
        }
        if ($isOutParentPark || ($park_sys_type == 'A11' && !$isOutSubPark)) {
            // 大场出场 或者 非大小场出场
            $isComputeMoney = true;
            // 上一次出场的通道所属车场信息获取
            empty($out_passage_info) && $out_passage_info = $carHandleGetInfoService->getParkPassageInfoById($out_park_passage_id, $village_id);
            if (isset($out_passage_info['garage_id']) && intval($out_passage_info['garage_id']) > 0) {
                /** @var int out_park_garage_id 上次出场通道所属车场id */
                $out_park_garage_id = intval($out_passage_info['garage_id']);
            } elseif (isset($out_passage_info['area_type']) && isset($out_passage_info['passage_area']) && intval($out_passage_info['area_type']) == 2 && intval($out_passage_info['passage_area']) > 0) {
                $out_park_garage_id = intval($out_passage_info['passage_area']);
            } else {
                $out_park_garage_id = 0;
            }
            empty($outParkParkingGarage) && $outParkParkingGarage = $carHandleGetInfoService->getParkingGarage($out_park_garage_id, $village_id);
            /** @var int is_month_charge 月租车进入非月租场 是否收费 0-不收费 1-收费 */
            $is_month_charge = isset($outParkParkingGarage['is_month_charge']) ? intval($outParkParkingGarage['is_month_charge']) : 0;
            if ($hasMonth && $is_month_charge != 1) {
                // 是月租车 由于前面相关逻辑已经处理 这里只会是 小城月租车 大场出场
                $whereLastRecord = [];
                $whereLastRecord[] = ['flow_direction', '=', 2];
                $whereLastRecord[] = ['is_out', '=', 1];
                $whereLastRecord[] = ['flow_time', '<', $nowTime];
                $whereLastRecord[] = ['flow_other_time|flow_time', '>', $in_time];
                $park_where = [];
                $park_where[] = ['in_time|out_time', '>', $in_time];
                $park_where[] = ['out_time', '<', $nowTime];
                $subLastRecordList = $carHandleGetInfoService->getLastCarFlowRecordList($car_number, $village_id, $park_sys_type, $whereLastRecord, $park_where);
                $tmp_park_time = 0;   //之前停车时长累加下
                foreach ($subLastRecordList as $subLastRecord) {
                    if (intval($subLastRecord['out_time']) > intval($subLastRecord['in_time']) && intval($subLastRecord['park_time']) > 0) {
                        $tmp_park_time += $subLastRecord['park_time'];
                    }
                }
                $park_time = $nowTime - $in_time - $tmp_park_time;
                $time = $carHandleGetInfoService->commonChangeTimeText($park_time);
                // todo 记录出场信息
                $accessMode = 3;
                $outParams['order_id'] = $order_id;
                $park_data['accessMode'] = $accessMode;
                $park_data['is_paid'] = 1;
                $park_data['total'] = 0;
                $park_data['in_time'] = $catch_in_time;
                $carHandleGetInfoService->recordCarFlowOut($park_data, $outParams);
                // TODO:屏显和语音提示特殊车辆通行
                $showScreenData['car_type'] = 'temporary_type';
                $showScreenData['duration_txt'] = $time;
                $showScreenData['duration'] = $park_time;
                $showScreenData['price'] = 0;
                $showScreenData['voice_content'] = 9;
                $showScreenData['content'] = '';
                (new CarFlowShowScreenService())->addA11ParkShowScreenLog($showScreenData);
                $sendParam = [
                    'garage_id' => $garage_id, 'end_time' => $car_end_time, 'car_garage_id' => $car_garage_id,
                    'stored_balance' => $stored_balance, 'car_id' => $car_id, 'car_user_phone' => $car_user_phone,
                    'park_name' => $village_name, 'passage_direction' => '出场', 'remark' => '\n支付金额：0（小场月租车大场免费出场）',
                    'is_small' => false, 'property_id' => $property_id, 'nowTime' => $nowTime, 'park_time' => $time,
                ];
                $carHandleGetInfoService->sendCarMessage($car_number, $village_id, $sendParam);
                // 无入场记录 自动放行
                fdump_api(['msg' => '无入场记录(也无入场识别记录)-自动放行', 'line' => __LINE__, 'nowTime' => $nowTime, 'park_data' => $park_data, 'outParams' => $outParams, 'showScreenData' => $showScreenData], 'parkBetter/handlePlateResultLog' . $reportTime, true);
                return true;
            } else {
                // todo 大场临时车出场 查询费用（包含了是小场月租车但大场收费+都是临时车收费）
                fdump_api(['msg' => '1-计算停车费用-A11', 'line' => __LINE__, 'nowTime' => $nowTime, 'payData' => $payData, 'in_park_info' => $in_park_info, 'parkConfig' => $parkConfig, 'car_info' => $car_info], 'parkBetter/handlePlateResultLog' . $reportTime, true);
                $pay_info = $carHandleGetInfoService->getA11TmpCarPay($payData, $in_park_info, $parkConfig, $car_info);
                $flow_temp_id = isset($pay_info['flow_temp_id']) ? intval($pay_info['flow_temp_id']) : 0;
                $park_data['flow_temp_id'] = $flow_temp_id;
                fdump_api(['msg' => '2-计算停车费用-A11', 'line' => __LINE__, 'nowTime' => $nowTime, 'pay_info' => $pay_info], 'parkBetter/handlePlateResultLog' . $reportTime, true);
            }
        }

        if (!$isComputeMoney) {
            fdump_api(['msg' => '1-计算停车费用', 'line' => __LINE__, 'nowTime' => $nowTime, 'payData' => $payData, 'in_park_info' => $in_park_info, 'parkConfig' => $parkConfig, 'car_info' => $car_info], 'parkBetter/handlePlateResultLog' . $reportTime, true);
            $pay_info = $carHandleGetInfoService->getA11TmpCarPay($payData, $in_park_info, $parkConfig, $car_info);
            $flow_temp_id = isset($pay_info['flow_temp_id']) ? intval($pay_info['flow_temp_id']) : 0;
            $park_data['flow_temp_id'] = $flow_temp_id;
            fdump_api(['msg' => '2-计算停车费用', 'line' => __LINE__, 'nowTime' => $nowTime, 'pay_info' => $pay_info], 'parkBetter/handlePlateResultLog' . $reportTime, true);
        }
        $pay_money = isset($pay_info['pay_money']) && floatval($pay_info['pay_money']) > 0 ? $pay_info['pay_money'] : 0;
        $time = isset($pay_info['time']) ? trim($pay_info['time']) : '';
        $park_time = isset($pay_info['park_time']) ? intval($pay_info['park_time']) : 0;
        if (!empty($in_park_info)) {
            $park_data['park_time'] = $park_time;
            $park_data['park_time_stamp'] = $time;
            if ($pay_money <= 0) {
                // todo 记录出场信息
                $accessMode = 3;
                $pay_order_id = isset($in_park_info['pay_order_id']) && intval($in_park_info['pay_order_id']) > 0 ? intval($in_park_info['pay_order_id']) : 0;
                $outParams['order_id'] = $order_id;
                if (isset($pay_info['is_advance_pay']) && $pay_info['is_advance_pay'] > 0 && isset($pay_info['parkTemp_money']) && $pay_info['parkTemp_money'] > 0) {
                    $park_data['total'] = $pay_info['parkTemp_money'];
                    $park_data['pay_type'] = $this->pay_type[6];
                } else {
                    $park_data['total'] = 0;
                    $park_data['pay_type'] = $this->pay_type[5];
                }
                $park_data['accessMode'] = $accessMode;
                $park_data['in_time'] = $catch_in_time;
                $park_data['pay_order_id'] = $pay_order_id;
                $park_data['in_park_id'] = $in_park_id;
                $carHandleGetInfoService->recordCarFlowOut($park_data, $outParams);
                // TODO:屏显和语音提示特殊车辆通行
                $showScreenData['car_type'] = 'temporary_type';
                $showScreenData['duration_txt'] = $time;
                $showScreenData['duration'] = $park_time;
                $showScreenData['price'] = 0;
                if ($park_sys_type == 'A11') {
                    (new CarFlowShowScreenService())->addA11ParkShowScreenLog($showScreenData);
                    $sendParam = [
                        'garage_id' => $garage_id, 'end_time' => $car_end_time, 'car_garage_id' => $car_garage_id,
                        'stored_balance' => $stored_balance, 'car_id' => $car_id, 'car_user_phone' => $car_user_phone,
                        'park_name' => $village_name, 'passage_direction' => '出场', 'remark' => '\n支付金额：0',
                        'is_small' => false, 'property_id' => $property_id, 'nowTime' => $nowTime, 'park_time' => $time,
                    ];
                    $carHandleGetInfoService->sendCarMessage($car_number, $village_id, $sendParam);
                } else {
                    (new CarFlowShowScreenService())->addParkShowScreenLog($showScreenData);
                }
                // 金额无需支付 自动抬杆
                fdump_api(['msg' => '0金额无需支付 自动抬杆', 'line' => __LINE__, 'nowTime' => $nowTime, 'park_data' => $park_data, 'outParams' => $outParams, 'showScreenData' => $showScreenData], 'parkBetter/handlePlateResultLog' . $reportTime, true);
                return true;
            }
            if ($pay_info['pay_money'] > 0 && $stored_balance > $pay_money) {
                // todo 记录出场信息
                $accessMode = 3;
                $pay_order_id = isset($in_park_info['pay_order_id']) && intval($in_park_info['pay_order_id']) > 0 ? intval($in_park_info['pay_order_id']) : 0;
                $outParams['order_id'] = $order_id;
                $park_data['accessMode'] = $accessMode;
                $park_data['total'] = $pay_money;
                $park_data['pay_type'] = $this->pay_type[1];
                $park_data['in_time'] = $catch_in_time;
                $park_data['pay_order_id'] = $pay_order_id;
                $park_data['in_park_id'] = $in_park_id;
                $park_data['car_type'] = 'storedCar';
                $park_data['pay_time'] = $nowTime;
                $park_data['is_paid'] = 1;
                $park_data['stored_balance'] = getFormatNumber($stored_balance - $pay_money);
                $park_data['prepayTotal'] = isset($pay_info['parkTemp_money']) ? $pay_info['parkTemp_money'] : 0;
                $carHandleGetInfoService->recordCarFlowOut($park_data, $outParams);
                // TODO:屏显和语音提示特殊车辆通行
                $showScreenData['car_type'] = 'temporary_type';
                $showScreenData['duration_txt'] = $time;
                $showScreenData['duration'] = $pay_info['park_time'];
                $showScreenData['price'] = $pay_money;
                $showScreenData['voice_content'] = 9;
                $showScreenData['content'] = '';
                if ($park_sys_type == 'A11') {
                    (new CarFlowShowScreenService())->addA11ParkShowScreenLog($showScreenData);
                    $remarkTxt = '\n支付金额：'.$pay_money.'元（储值抵扣）';
                    $sendParam = [
                        'garage_id' => $garage_id, 'end_time' => $car_end_time, 'car_garage_id' => $car_garage_id,
                        'stored_balance' => $stored_balance, 'car_id' => $car_id, 'car_user_phone' => $car_user_phone,
                        'park_name' => $village_name, 'passage_direction' => '出场', 'remark' => $remarkTxt,
                        'is_small' => false, 'property_id' => $property_id, 'nowTime' => $nowTime, 'park_time' => $time,
                    ];
                    $carHandleGetInfoService->sendCarMessage($car_number, $village_id, $sendParam);
                } else {
                    (new CarFlowShowScreenService())->addParkShowScreenLog($showScreenData);
                }
                // 金额由车辆存储余额直接支付 自动抬杆
                fdump_api(['msg' => '金额由车辆存储余额直接支付 自动抬杆', 'line' => __LINE__, 'nowTime' => $nowTime, 'park_data' => $park_data, 'outParams' => $outParams, 'showScreenData' => $showScreenData], 'parkBetter/handlePlateResultLog' . $reportTime, true);
                return true;
            }
            $pay_order_id = isset($in_park_info['pay_order_id']) && intval($in_park_info['pay_order_id']) > 0 ? intval($in_park_info['pay_order_id']) : 0;
            if ($pay_order_id > 0) {
                $db_house_new_pay_order = new HouseNewPayOrder();
                $payOrderInfo = $db_house_new_pay_order->get_one(['summary_id' => $in_park_info['pay_order_id'], 'is_paid' => 1]);
                $payOrderPayTime = isset($payOrderInfo['pay_time']) ? intval($payOrderInfo['pay_time']) : 0;
                $payOrderPayMoney = isset($payOrderInfo['pay_money']) && floatval($payOrderInfo['pay_money']) > 0 ? $payOrderInfo['pay_money'] : 0;
                if ($payOrderPayTime > $in_time) {
                    if ($payOrderPayMoney >= $pay_money) {
                        $accessMode = 4;
                        $outParams['order_id'] = $order_id;
                        $park_data['accessMode'] = $accessMode;
                        $park_data['total'] = $pay_money;
                        $park_data['pay_type'] = $this->pay_type[6];
                        $park_data['in_time'] = $catch_in_time;
                        $park_data['pay_order_id'] = $pay_order_id;
                        $park_data['in_park_id'] = $in_park_id;
                        $park_data['pay_time'] = $payOrderPayTime;
                        $park_data['is_paid'] = 1;
                        $park_data['prepayTotal'] = isset($pay_info['parkTemp_money']) ? $pay_info['parkTemp_money'] : 0;
                        $carHandleGetInfoService->recordCarFlowOut($park_data, $outParams);
                        // TODO:屏显和语音提示特殊车辆通行
                        $showScreenData['car_type'] = 'temporary_type';
                        $showScreenData['duration_txt'] = $time;
                        $showScreenData['duration'] = $pay_info['park_time'];
                        $showScreenData['price'] = 0;
                        $showScreenData['content'] = '';
                        if ($park_sys_type == 'A11') {
                            (new CarFlowShowScreenService())->addA11ParkShowScreenLog($showScreenData);
                            $remarkTxt = '\n支付金额：'.$pay_money.'元（已支付）';
                            $sendParam = [
                                'garage_id' => $garage_id, 'end_time' => $car_end_time, 'car_garage_id' => $car_garage_id,
                                'stored_balance' => $stored_balance, 'car_id' => $car_id, 'car_user_phone' => $car_user_phone,
                                'park_name' => $village_name, 'passage_direction' => '出场', 'remark' => $remarkTxt,
                                'is_small' => false, 'property_id' => $property_id, 'nowTime' => $nowTime, 'park_time' => $time,
                            ];
                            $carHandleGetInfoService->sendCarMessage($car_number, $village_id, $sendParam);
                        } else {
                            (new CarFlowShowScreenService())->addParkShowScreenLog($showScreenData);
                        }
                        // 金额已经进行了支付 自动抬杆
                        fdump_api(['msg' => '金额已经进行了支付 自动抬杆', 'line' => __LINE__, 'nowTime' => $nowTime, 'park_data' => $park_data, 'outParams' => $outParams, 'showScreenData' => $showScreenData], 'parkBetter/handlePlateResultLog' . $reportTime, true);
                        return true;
                    } else {
                        //TODO:屏显和语音提示错误信息
                        $showScreenData['car_type'] = 'temporary_type';
                        $showScreenData['duration_txt'] = $time;
                        $showScreenData['duration'] = $park_time;
                        $showScreenData['price'] = $pay_money;
                        $showScreenData['voice_content'] = 5;
                        $showScreenData['content'] = '';
                        if ($park_sys_type == 'A11') {
                            (new CarFlowShowScreenService())->addA11ParkShowScreenLog($showScreenData);
                        } else {
                            (new CarFlowShowScreenService())->addParkShowScreenLog($showScreenData);
                        }
                        //企业微信群提醒
                        if (isset($pay_info['pay_money'])) {
                            $payMoney = $pay_info['pay_money'] - $payOrderInfo['pay_money'];
                        } else {
                            $payMoney = $pay_info['pay_money'];
                        }
                        $info = '车牌为' . $car_number . '的车辆停车时长' . $pay_info['time'] . '需缴费' . $payMoney . '元';
                        $flow_status = '拒绝通行';
                        $reason = '临时车需缴费通行';
                        $carHandleGetInfoService->pushMessageQy($info, $village_name, $car_number, $passage_info['passage_name'], $flow_status, '出口', $reason);
                        fdump_api(['msg' => '临时车需缴费通行 不抬杆', 'line' => __LINE__, 'nowTime' => $nowTime, 'park_data' => $park_data, 'showScreenData' => $showScreenData], 'parkBetter/handlePlateResultLog' . $reportTime, true);
                        return false;
                    }
                }
            }
            //TODO:屏显和语音提示错误信息
            $showScreenData['voice_content'] = 5;
            $showScreenData['content'] = '';
            $showScreenData['car_type'] = 'temporary_type';
            $showScreenData['duration_txt'] = $pay_info['time'];
            $showScreenData['duration'] = $pay_info['park_time'];
            $showScreenData['price'] = $pay_info['pay_money'];
            if ($park_sys_type == 'A11') {
                (new CarFlowShowScreenService())->addA11ParkShowScreenLog($showScreenData);
            } else {
                (new CarFlowShowScreenService())->addParkShowScreenLog($showScreenData);
            }
            $payMoney = $pay_info['pay_money'];
            $info = '车牌为' . $car_number . '的车辆停车时长' . $pay_info['time'] . ',需缴费' . $payMoney . '元';
            $flow_status = '拒绝通行';
            $reason = '临时车需缴费通行';
            $carHandleGetInfoService->pushMessageQy($info, $village_name, $car_number, $passage_info['passage_name'], $flow_status, '出口', $reason);
            fdump_api(['msg' => '临时车需缴费通行 不抬杆', 'line' => __LINE__, 'nowTime' => $nowTime, 'park_data' => $park_data, 'showScreenData' => $showScreenData], 'parkBetter/handlePlateResultLog' . $reportTime, true);
            return false;
        }
        //TODO:屏显和语音提示错误信息
        $showScreenData['voice_content'] = 8;
        $showScreenData['content'] = '无入场信息';
        $showScreenData['car_type'] = 'temporary_type';
        $showScreenData['duration_txt'] = '';
        $showScreenData['duration'] = 0;
        $showScreenData['price'] = 0;
        if ($park_sys_type == 'A11') {
            (new CarFlowShowScreenService())->addA11ParkShowScreenLog($showScreenData);
        } else {
            (new CarFlowShowScreenService())->addParkShowScreenLog($showScreenData);
        }
        $info = '车牌为' . $car_number . '的车辆出场无对应入场信息';
        $flow_status = '拒绝通行';
        $reason = '车辆无对应入场信息';
        $carHandleGetInfoService->pushMessageQy($info, $village_name, $car_number, $passage_info['passage_name'], $flow_status, '出口', $reason);
        fdump_api(['msg' => '车辆无对应入场信息 不抬杆', 'line' => __LINE__, 'nowTime' => $nowTime, 'park_data' => $park_data, 'showScreenData' => $showScreenData], 'parkBetter/handlePlateResultLog' . $reportTime, true);
        return false;
    }
    
    /**
     * 停车设备心跳处理.
     */
    public function handleHeartbeat($data)
    {
        $nowTime = isset($data['nowTime']) && $data['nowTime'] ? $data['nowTime'] : time();
        $reportTime = isset($data['reportTime']) && $data['reportTime'] ? $data['reportTime'] : date('YmdH');
        fdump_api(['msg' => '停车设备心跳处理', 'data' => $data, 'line' => __LINE__], 'parkBetter/handleHeartbeatLog' . $reportTime, 1);
        $result = [];
        $result['data'] = false;
        $result['showscreen'] = [];
        $serialno = isset($data['serialno']) && $data['serialno'] ? $data['serialno'] : '';
        if (!$serialno) {
            return $result;
        }
        $carHandleGetInfoService = new CarHandleGetInfoService();
        $passage_info = $carHandleGetInfoService->getParkPassageInfo($serialno);
        if (!$passage_info) {
            fdump_api(['msg' => '缺少通道信息', 'line' => __LINE__, 'serialno' => $serialno, 'data' => $data], 'parkBetter/handleHeartbeatLog' . $reportTime, true);
            return $result;
        }
        // 更新通道心跳
        if (!empty($passage_info)) {
            (new CarHandleGetInfoService())->setParkPassageHeart($data['serialno'], $nowTime);
//            $updatePassageTime = [
//                'jobType' => 'update_park_passage', 'device_number' => $serialno, 'nowTime' => $nowTime
//            ];
//            $this->traitCommonCarFlowHandle($updatePassageTime, $this->passageHeartSpaceTimes);
        }
        $park_sys_type = isset($passage_info['park_sys_type']) && $passage_info['park_sys_type'] ? trim($passage_info['park_sys_type']) : 'D3';
        $result = [];
        $result['data'] = false;
        $result['showscreen'] = [];
        $white = [];
        $white['device_number'] = $serialno;
        $white_list = (new HouseVillageParkingService())->getWhitelist($white, $passage_info);
        if ($white_list && !is_array($white_list)) {
            $white_list = $white_list->toArray();
        }
        if (!empty($white_list)) {
            $result['white_list'] = $white_list;
        }
        $db_park_showscreen_log = new ParkShowscreenLog();
        $showLimitTime = $nowTime - CarFlowConst::CAR_FLOW_PARK_SHOW_SCREEN_LIMIT_TIME;
        $where = [];
        $where[] = ['channel_id', '=', $serialno];
        $where[] = ['add_time', '>', $showLimitTime];
        $showscreen_info = $db_park_showscreen_log->getFind($where);
        if ($showscreen_info && !is_array($showscreen_info)) {
            $showscreen_info = $showscreen_info->toArray();
        }
        if (isset($showscreen_info['id']) && $showscreen_info['id']) {
            if ($passage_info['passage_type'] == 2 && $passage_info['passage_direction'] == 1) {
                $showscreen_info['passage_type'] = 2;
            } else {
                $showscreen_info['passage_type'] = 1;
            }
            $result['showscreen'] = $showscreen_info;
            $db_park_showscreen_log->delOne(['id' => $showscreen_info['id']]);
        }
        fdump_api(['msg' => '停车设备心跳处理', 'passage_info' => $passage_info,'showscreen_info' => $showscreen_info, 'line' => __LINE__], 'parkBetter/handleHeartbeatLog' . $reportTime, 1);

        $db_park_open_log = new ParkOpenLog();
        $openLimitTime = $nowTime - CarFlowConst::CAR_FLOW_OPEN_LOG_LIMIT_TIME;
        $where = [];
        $where[] = ['channel_id', '=', $serialno];
        $where[] = ['add_time', '>', $openLimitTime];
        $where[] = ['open_type', '<', 4];
        $log_info = $db_park_open_log->get_one($where);
        if ($log_info && !is_array($log_info)) {
            $log_info = $log_info->toArray();
        }
        fdump_api(['msg' => '停车设备心跳处理', 'park_sys_type' => $park_sys_type, 'log_info' => $log_info, 'line' => __LINE__], 'parkBetter/handleHeartbeatLog' . $reportTime, 1);
        if ($park_sys_type == 'A11') {
            // todo A11停车
            if (!empty($log_info) && isset($log_info['id']) && $log_info['car_number'] && $log_info['car_number'] != '_无_') {
                $params = [
                    'reportTime' => $reportTime,
                    'nowTime' => $nowTime,
                ];
                $result = $this->handleA11Heartbeat($serialno, $data, $passage_info, $params, $result, $log_info);
            }
            isset($log_info['id']) && $log_info['id'] && $db_park_open_log->delOne(['id' => $log_info['id']]);
            return $result;
        } else {
            // todo D3停车（营山）
            $params = [
                'reportTime' => $reportTime,
                'nowTime' => $nowTime,
            ];
            $result = $this->handleD3Heartbeat($serialno, $data, $passage_info, $params, $result, $log_info);
            if ($passage_info['passage_direction'] == 0 && !$result['data']) {
                // 出场且不允许抬杆情况下才进行支付相关定位查询
                // todo 处理下处理停车支付结果查询指令
                $result['pay_info'] = $carHandleGetInfoService->getParkPayRecord($serialno, $passage_info, $params);
            } else {
                $result['pay_info'] = false;
            }
            isset($log_info['id']) && $log_info['id'] && $db_park_open_log->delOne(['id' => $log_info['id']]);
            return $result;
        }
    }

    /**
     * A11停车设备心跳处理.
     */
    public function handleA11Heartbeat($serialno, $data, $passage_info, $params, $result, $log_info)
    {
        $reportTime = isset($params['reportTime']) && $params['reportTime'] ? $params['reportTime'] : date('YmdH');
        fdump_api(['msg' => 'A11停车设备心跳处理', 'parameter' => func_get_args(), 'line' => __LINE__], 'parkBetter/handleHeartbeatLog' . $reportTime, 1);
        $nowTime = isset($params['nowTime']) && $params['nowTime'] ? $params['nowTime'] : time();
        $car_number = isset($log_info['car_number']) && $log_info['car_number'] ? trim($log_info['car_number']) : '';
        $open_type = isset($log_info['open_type']) && $log_info['open_type'] ? intval($log_info['open_type']) : 0;
        $add_time = isset($params['add_time']) && $params['add_time'] ? intval($params['add_time']) : 0;
        if ($car_number) {
            $carHandleGetInfoService = new CarHandleGetInfoService();
            $park_passage_id = isset($passage_info['id']) && $passage_info['id'] ? intval($passage_info['id']) : 0;
            $village_id = isset($passage_info['village_id']) && $passage_info['village_id'] ? intval($passage_info['village_id']) : 0;
            $park_sys_type = isset($passage_info['park_sys_type']) && $passage_info['park_sys_type'] ? trim($passage_info['park_sys_type']) : 'A11';
            $village_info = $carHandleGetInfoService->getVillageInfo($village_id);
            $village_name = isset($village_info['village_name']) && $village_info['village_name'] ? $village_info['village_name'] : '';
            if (isset($passage_info['garage_id']) && intval($passage_info['garage_id']) > 0) {
                $garage_id = intval($passage_info['garage_id']);
            } elseif (isset($passage_info['area_type']) && isset($passage_info['passage_area']) && intval($passage_info['area_type']) == 2 && intval($passage_info['passage_area']) > 0) {
                $garage_id = intval($passage_info['passage_area']);
            } else {
                $garage_id = 0;
            }
            $accessMode = 3;
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
            if ($passage_info['passage_direction'] == 1) {
                // 进场
                $park_data['in_time'] = $add_time > 0 ? $add_time : $nowTime;
                $park_data['is_paid'] = 0;
                $park_data['is_out'] = 0;
                $inParams = [
                    'village_id' => $village_id, 'garage_id' => $garage_id, 'park_passage_id' => $park_passage_id,
                    'flow_direction' => 1, 'device_number' => $passage_info['device_number'], 'park_sys_type' => $park_sys_type,
                    'flow_time' => $nowTime, 'car_number' => $car_number, 'park_name' => $village_name,
                ];
                $flowIntInfo = $carHandleGetInfoService->checkCarFlowInt($car_number, $village_id, $garage_id, $nowTime, $park_sys_type);
                $in_park_id = isset($flowIntInfo['id']) && intval($flowIntInfo['id']) > 0 ? intval($flowIntInfo['id']) : 0;
                $position_id = isset($flowIntInfo['position_id']) && intval($flowIntInfo['position_id']) > 0 ? intval($flowIntInfo['position_id']) : 0;
                $inParams['from_id'] = $in_park_id;
                if ($open_type == 1) {
                    $accessMode = 9;
                } elseif ($open_type == 3) {
                    $accessMode = 7;
                }
                if ($position_id > 0) {
                    $park_data['position_id'] = $position_id;
                    $park_data['isRightPosition'] = 1;
                }
                $park_data['accessMode'] = $accessMode;
                $carHandleGetInfoService->recordCarFlowInt($park_data, $inParams);
            }
            if ($passage_info['passage_direction'] == 0) {
                $commonData = [
                    'car_number' => $car_number,
                    'open_type' => $open_type,
                    'nowTime' => $add_time > 0 ? $add_time : $nowTime,
                    'park_car_type' => $park_data['park_car_type']
                ];
                $carHandleGetInfoService->commonExceptionOutHandle($commonData, $passage_info, $park_data, $village_info);
            }
            $result['data'] = true;
        }
        if (!$result['data'] && isset($log_info['id']) && $log_info['id']) {
            $result['data'] = true;
        }
        return $result;
    }

    /**
     * D3停车设备心跳处理.
     */
    public function handleD3Heartbeat($serialno, $data, $passage_info, $params, $result, $log_info)
    {
        $reportTime = isset($params['reportTime']) && $params['reportTime'] ? $params['reportTime'] : date('YmdH');
        $nowTime = isset($params['nowTime']) && $params['nowTime'] ? $params['nowTime'] : time();
        $car_number = isset($log_info['car_number']) && $log_info['car_number'] ? trim($log_info['car_number']) : '';
        $open_type = isset($log_info['open_type']) && $log_info['open_type'] ? intval($log_info['open_type']) : 0;
        if ($car_number) {
            $carHandleGetInfoService = new CarHandleGetInfoService();
            // 判断是否有车牌
            $park_passage_id = isset($passage_info['id']) && $passage_info['id'] ? intval($passage_info['id']) : 0;
            $village_id = isset($passage_info['village_id']) && $passage_info['village_id'] ? intval($passage_info['village_id']) : 0;
            $park_sys_type = isset($passage_info['park_sys_type']) && $passage_info['park_sys_type'] ? trim($passage_info['park_sys_type']) : 'D3';
            $village_info = $carHandleGetInfoService->getVillageInfo($village_id);
            $village_name = isset($village_info['village_name']) && $village_info['village_name'] ? $village_info['village_name'] : '';
            if (isset($passage_info['garage_id']) && intval($passage_info['garage_id']) > 0) {
                $garage_id = intval($passage_info['garage_id']);
            } elseif (isset($passage_info['area_type']) && isset($passage_info['passage_area']) && intval($passage_info['area_type']) == 2 && intval($passage_info['passage_area']) > 0) {
                $garage_id = intval($passage_info['passage_area']);
            } else {
                $garage_id = 0;
            }
            $accessMode = 3;
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
            if ($passage_info['passage_direction'] == 1) {
                // 进场
                $park_data['in_time'] = $nowTime;
                $park_data['is_paid'] = 0;
                $park_data['is_out'] = 0;

                $inParams = [
                    'village_id' => $village_id, 'garage_id' => $garage_id, 'park_passage_id' => $park_passage_id,
                    'flow_direction' => 1, 'device_number' => $passage_info['device_number'], 'park_sys_type' => $park_sys_type,
                    'flow_time' => $nowTime, 'car_number' => $car_number, 'park_name' => $village_name,
                ];
                $flowIntInfo = $carHandleGetInfoService->checkCarFlowInt($car_number, $village_id, $garage_id, $nowTime, $park_sys_type);
                $in_park_id = isset($flowIntInfo['id']) && intval($flowIntInfo['id']) > 0 ? intval($flowIntInfo['id']) : 0;
                $inParams['from_id'] = $in_park_id;
                if ($open_type == 1) {
                    $accessMode = 9;
                } elseif ($open_type == 3) {
                    $accessMode = 7;
                }
                $park_data['accessMode'] = $accessMode;
                $carHandleGetInfoService->recordCarFlowInt($park_data, $inParams);
            }
            if ($passage_info['passage_direction'] == 0) {
                $outParams = [
                    'village_id' => $village_id, 'garage_id' => $garage_id, 'park_passage_id' => $park_passage_id,
                    'flow_direction' => 2, 'device_number' => $passage_info['device_number'], 'park_sys_type' => $park_sys_type,
                    'flow_time' => $nowTime,
                ];
                // 出场
                $park_data['is_out'] = 1;
                $park_data['is_paid'] = 1;
                $in_park_info = $carHandleGetInfoService->getCarFlowInt($car_number, $village_id, $park_sys_type);
                $db_house_new_pay_order = new HouseNewPayOrder();
                if (!empty($in_park_info)) {
                    $order_id = $in_park_info['order_id'];
                    $pay_order_id = isset($in_park_info['pay_order_id']) && intval($in_park_info['pay_order_id']) > 0 ? intval($in_park_info['pay_order_id']) : 0;
                    $park_data['order_id'] = $order_id;
                    $parkTemp = $carHandleGetInfoService->getCarFlowTemp($car_number, $village_id, 1, $park_sys_type, false, $order_id);
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
                    if ($pay_time > 1 && $in_time > 1 && $pay_time > $in_time) {
                        $outParams['park_time'] = $pay_time - $in_time;
                    }
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
                    $park_data['open_type'] = $open_type;
                    $carHandleGetInfoService->recordCarFlowOut($park_data, $outParams);
                } else {
                    // 出场
                    $park_data['is_out'] = 1;
                    $park_data['out_time'] = $nowTime;
                    $park_data['total'] = 0;
                    $park_data['pay_type'] = $this->pay_type[6];
                    if ($open_type == 2) {
                        $accessMode = 4;
                    } elseif ($open_type == 3) {
                        $accessMode = 7;
                        $park_data['exception_type'] = 2;
                    }
                    $park_data['accessMode'] = $accessMode;
                    $park_data['open_type'] = $open_type;
                    $carHandleGetInfoService->recordCarFlowOut($park_data, $outParams);
                }
            }
            $result['data'] = true;
        }
        if (!$result['data'] && isset($log_info['id']) && $log_info['id']) {
            $result['data'] = true;
        }
        return $result;
    }

    /**
     * gio推送开闸指令处理.
     */
    public function addPortData($data)
    {
        $serialno = isset($data['serialno']) && trim($data['serialno']) ? trim($data['serialno']) : '';
        $fromType = isset($data['fromType']) && trim($data['fromType']) ? trim($data['fromType']) : 'gio';
        $opt_source = isset($data['opt_source']) ? intval($data['opt_source']) : 0;
        $msg = isset($data['msg']) ? trim($data['msg']) : '';
        $content = isset($data['content']) ? trim($data['content']) : '';
        $function_code = $operation_code = $data_code = $no_code = [];
        $optcode = '';
        if (!$serialno) {
            return false;
        }
        if ($fromType == 'gio' && $opt_source !== 0) {
            /***opt_source 0表示开闸***/
            return false;
        }
        $nowTime = time();
        $carHandleGetInfoService = new CarHandleGetInfoService();
        $passage_info = $carHandleGetInfoService->getParkPassageInfo($serialno);
        if (empty($passage_info)) {
            return false;
        }
        $passage_type = isset($passage_info['passage_type']) ? intval($passage_info['passage_type']) : 1;
        $village_id = isset($passage_info['village_id']) ? intval($passage_info['village_id']) : 0;
        if (isset($passage_info['garage_id']) && intval($passage_info['garage_id']) > 0) {
            $garage_id = intval($passage_info['garage_id']);
        } elseif (isset($passage_info['area_type']) && isset($passage_info['passage_area']) && intval($passage_info['area_type']) == 2 && intval($passage_info['passage_area']) > 0) {
            $garage_id = intval($passage_info['passage_area']);
        } else {
            $garage_id = 0;
        }
        $park_sys_type = isset($passage_info['park_sys_type']) ? trim($passage_info['park_sys_type']) : '';
        
        $uniqueArr = [
            'channel_id' => $serialno,
            'content' => $content,
            'content_de' => $msg,
            'village_id' => $village_id,
            'park_sys_type' => $park_sys_type,
        ];
        if ($fromType == 'serial') {
            if (stripos($msg, 'AFAB') !== false) {
                $handleMsg = str_replace('AFAB', 'AF,AB', $msg);
                $arr_msg = explode(',', $handleMsg);
                if (!empty($arr_msg)) {
                    foreach ($arr_msg as $v) {
                        $function_code[] = substr($v, 6, 2);
                        $operation_code[] = substr($v, 8, 2);
                        $data_code[] = substr($v, 10, 2);
                        $no_code[] = substr($v, 12, 8);
                    }
                }
            } else {
                $function_code[] = substr($msg, 6, 2);
                $operation_code[] = substr($msg, 8, 2);
                $data_code[] = substr($msg, 10, 2);
                $no_code[] = substr($msg, 12, 8);
            }
            if (!in_array('78', $function_code) || !in_array('01', $operation_code) || !in_array('06', $data_code)) {
                return false;
            }

            $unique_id = md5(json_encode($uniqueArr, JSON_UNESCAPED_UNICODE));
            $db_park_serial_log = new ParkSerialLog();
            $whereParkSerialLog = array();
            $timeTmp = $nowTime - CarFlowConst::PARK_SERIAL_LOG_LIMIT_TIME;
            $whereParkSerialLog[] = ['unique_id', '=', $unique_id];
            $whereParkSerialLog[] = ['add_time', '>', $timeTmp];
            $serialLogRecord = $db_park_serial_log->get_one($whereParkSerialLog);
            if ($serialLogRecord && !is_array($serialLogRecord)) {
                $serialLogRecord = $serialLogRecord->toArray();
            }
            if(!empty($serialLogRecord) && intval($serialLogRecord['add_time']) - $nowTime < 10){
                //10秒内多次提交同样的数据过滤掉
                return false;
            }
            $arr = $uniqueArr;
            $arr['add_time'] = $nowTime;
            $db_park_serial_log->add($arr);
            if (!empty($no_code)) {
                foreach ($no_code as $v) {
                    if ($v != '00000000') {
                        $optcode = base_convert($v, 16, 10);
                        break;
                    }
                }
            }
        }
        
        $device_number_arr = array();
        $serialno_arr = array();
        $db_park_passage = new ParkPassage();
        if ($passage_type == 2) {
            $whereCommonPassage = ['village_id' => $village_id, 'status' => 1, 'park_sys_type' => $park_sys_type, 'passage_type' => 2];
            $passage_info_list = $db_park_passage->getList($whereCommonPassage);
            if ($passage_info_list && !is_array($passage_info_list)) {
                $passage_info_list = $passage_info_list->toArray();
            }
            foreach ($passage_info_list as $kk => $vv) {
                $device_number_arr[$vv['device_number']] = $vv;
                $serialno_arr[] = $vv['device_number'];
            }
        }
        $whereFlow = [];
        $whereLog = [];
        if (!empty($serialno_arr)) {
            $whereFlow[] = ['device_number', 'in', $serialno_arr];
            $whereLog[] = ['channel_id', 'in', $serialno_arr];
        } elseif ($serialno) {
            $whereFlow[] = ['device_number', '=', $serialno];
            $whereLog[] = ['channel_id', '=', $serialno_arr];
        }

        $recordLogParam = [
            'car_number' => '',
            'garage_id' => 0,
            'flow_direction' => 0,
            'park_sys_type' => $park_sys_type,
        ];
        $recordLog = $carHandleGetInfoService->getFlowIdentifyRecord($village_id, $recordLogParam, $whereFlow, $whereLog);
        $car_number = isset($recordLog['car_number']) ? trim($recordLog['car_number']) : '';
        if (!$car_number){
            return false;
        }
        $village_info = $carHandleGetInfoService->getVillageInfo($village_id);
        $village_name = isset($village_info['village_name']) && $village_info['village_name'] ? $village_info['village_name'] : '';
        $channel_id = isset($recordLog['channel_id']) ? trim($recordLog['channel_id']) : '';
        if ($device_number_arr && isset($device_number_arr[$channel_id])) {
            $passage_info = $device_number_arr[$channel_id];
        }
        $parkConfig = $carHandleGetInfoService->getParkConfig($village_id, $garage_id);
        if (empty($parkConfig)){
            return false;
        }
        !$park_sys_type && $park_sys_type = isset($parkConfig['park_sys_type']) ? trim($parkConfig['park_sys_type']) : '';
        $data_add = [
            'id' => $passage_info['id'], 'village_id' => $village_id, 'car_number' => $car_number,
            'accessTime' => date('Y-m-d H:i:s', $nowTime), 'nowTime' => $nowTime,
            'user_phone' => '', 'user_name' => '', 'village_name' => $village_name,
            'img' => '', 'price' => 0, 'park_time' => 0, 'optcode' => $optcode, 'park_sys_type' => $park_sys_type
        ];
        $recordLogTime = isset($recordLog['add_time']) ? intval($recordLog['add_time']) : 0;

        $passage_direction = isset($passage_info['passage_direction']) ? intval($passage_info['passage_direction']) : 0;
        $car_number_type = isset($recordLog['car_number_type']) ? intval($recordLog['car_number_type']) : 0;
        $pay_money = 0;
        $in_park_info = $carHandleGetInfoService->getCarFlowInt($car_number, $village_id, $park_sys_type);
        // 查询下车辆信息
        $car_info = $carHandleGetInfoService->getCarInfo($car_number, $village_id);
        $hasMonth = false;
        $isOverMonth = false;
        if (isset($car_info['end_time']) && intval($car_info['end_time']) >= $nowTime) {
            $hasMonth = true;
        }
        if (!$hasMonth && isset($car_info['end_time']) && intval($car_info['end_time']) > 100) {
            $isOverMonth = true;
        }
        $data_add['hasMonth'] = $hasMonth;
        $data_add['isOverMonth'] = $isOverMonth;
        if (($recordLogTime + 300) > $nowTime) {
            if ($passage_direction == 0) {
                $passage_garage_id = $garage_id;
                $car_garage_id = isset($car_info['garage_id']) ? intval($car_info['garage_id']) : 0;
                $car_position_id = isset($car_info['car_position_id']) ? intval($car_info['car_position_id']) : 0;
                $passage_garage_info = $carHandleGetInfoService->getParkingGarage($passage_garage_id, $village_id);
                $isOutParentPark = false;
                $isOutSubPark = false;
                $notParentORSubPark = true;
                $isSameGarage = false;
                if (isset($passage_garage_info['fid']) && intval($passage_garage_info['fid']) > 0) {
                    // 通过的车道是 小场（子停车场）出场
                    $isOutParentPark = false;
                    $isOutSubPark = true;
                    $notParentORSubPark = false;
                } elseif (isset($passage_garage_info['garage_id']) && intval($passage_garage_info['garage_id']) > 0) {
                    $sub_passage_garage_info = $carHandleGetInfoService->getParkingGarage($passage_garage_info['garage_id'], $village_id);
                    if (isset($sub_passage_garage_info['garage_id']) && intval($sub_passage_garage_info['garage_id']) > 0) {
                        // 通过的车道是 大场（父停车场）出场
                        $sub_passage_garage_id = intval($sub_passage_garage_info['garage_id']);
                        $isOutParentPark = true;
                        $isOutSubPark = false;
                        $notParentORSubPark = false;
                    }
                }
                $payData = [
                    'car_number' => $car_number, 'village_id' => $village_id,
                    'park_id' => $village_id, 'garage_id' => $garage_id,
                    'park_sys_type' => $park_sys_type, 'nowTime' => $nowTime, 'device_number' => $serialno,
                    'park_name' => $village_name, 'car_number_type' => $car_number_type,
                    'isOutSubPark' => $isOutSubPark, 'isOutParentPark' => $isOutParentPark,
                    'notParentORSubPark' => $notParentORSubPark, 'isOverMonth' => $isOverMonth,
                    'isSameGarage' => $isSameGarage, 'car_garage_id' => $car_garage_id,
                    'passage_garage_id' => $passage_garage_id,
                ];
                try {
                    $pay_info = $carHandleGetInfoService->getA11TmpCarPay($payData, $in_park_info, $parkConfig, $car_info);
                    $flow_temp_id = isset($pay_info['flow_temp_id']) ? intval($pay_info['flow_temp_id']) : 0;
                    $data_add['flow_temp_id'] = $flow_temp_id;
                } catch (\Exception $e) {
                    fdump_api(['msg' => '错误', 'err' => $e->getMessage(), 'errLine' => $e->getLine(), 'errCode' => $e->getCode(), 'errFile' => $e->getFile(), 'line' => __LINE__, 'nowTime' => $nowTime, 'payData' => $payData], 'gio/addPortData', true);
                    $pay_info = [];
                }
            } else {
                $pay_info = [];
            }
            $pay_money = isset($pay_info['pay_money']) && floatval($pay_info['pay_money']) > 0 ? $pay_info['pay_money'] : 0;
            $time = isset($pay_info['time']) ? trim($pay_info['time']) : '';
            $park_time = isset($pay_info['park_time']) ? intval($pay_info['park_time']) : 0;
            $data_add['price'] = $pay_money;
            $data_add['park_time'] = $park_time;
            $data_add['car_number'] = $car_number;
        }
        if ($pay_money <= 0 && !empty($in_park_info)) {
            $order_id = isset($in_park_info['order_id']) && $in_park_info['order_id'] ? $in_park_info['order_id'] : 0;
            $parkTemp = $carHandleGetInfoService->getCarFlowTemp($car_number, $village_id, 0, '', false, $order_id);
            $park_pay_price = isset($parkTemp['price']) && floatval($parkTemp['price']) > 0 ? $parkTemp['price'] : 0;
            $data_add['price'] = $park_pay_price;
        }
        try {
            $res1 = $this->addParkInfo($data_add, $passage_info, $car_info, $in_park_info);
        }catch (\Exception $e) {
            $res1 = 0;
            fdump_api(['msg' => '错误', 'err' => $e->getMessage(), 'errLine' => $e->getLine(), 'errCode' => $e->getCode(), 'errFile' => $e->getFile(), 'line' => __LINE__, 'nowTime' => $nowTime, 'payData' => $payData], 'gio/addPortData', true);
        }
        if ($res1){
            $showScreenData = [];
            $showScreenData['passage'] = $passage_info;
            $showScreenData['village_id'] = $village_id;
            $showScreenData['car_number'] = $car_number;
            $showScreenData['channel_id'] = $passage_info['device_number'];
            $showScreenData['voice_content'] = 11;
            $showScreenData['content'] = '岗亭人工确认，请通行';
            $showScreenData['car_type'] = 'temporary_type';
            fdump_api(['msg' => '显屏语音推送', 'showScreenData' => $showScreenData, 'line' => __LINE__, 'nowTime' => $nowTime, 'payData' => $payData], 'gio/addPortData', true);
            if ('A11' == $park_sys_type) {
                (new CarFlowShowScreenService())->addA11ParkShowScreenLog($showScreenData);
            } else {
                (new CarFlowShowScreenService())->addParkShowScreenLog($showScreenData);
            }
        }
        return  true;
    }
    
    /**
     * 处理新增gio推送或者
     */
    public function addParkInfo($data, $passage_info, $car_info, $in_park_info) {
        $nowTime = isset($params['nowTime']) && $params['nowTime'] ? $params['nowTime'] : time();
        $car_number = isset($log_info['car_number']) && $log_info['car_number'] ? trim($log_info['car_number']) : '';
        $park_passage_id = isset($passage_info['id']) && $passage_info['id'] ? intval($passage_info['id']) : 0;
        $village_id = isset($data['village_id']) && $data['village_id'] ? intval($data['village_id']) : 0;
        $park_sys_type = isset($data['park_sys_type']) && $data['park_sys_type'] ? trim($data['park_sys_type']) : '';
        $village_name = isset($data['village_name']) && $data['village_name'] ? trim($data['village_name']) : '';
        $optname = isset($data['optcode']) && $data['optcode'] ? trim($data['optcode']) : '';
        if (isset($passage_info['garage_id']) && intval($passage_info['garage_id']) > 0) {
            $garage_id = intval($passage_info['garage_id']);
        } elseif (isset($passage_info['area_type']) && isset($passage_info['passage_area']) && intval($passage_info['area_type']) == 2 && intval($passage_info['passage_area']) > 0) {
            $garage_id = intval($passage_info['passage_area']);
        } else {
            $garage_id = 0;
        }
        $park_data = [];
        $flow_temp_id = isset($data['flow_temp_id']) ? intval($data['flow_temp_id']) : 0;
        $flow_temp_id &&  $park_data['flow_temp_id'] = $flow_temp_id;
        $park_data['car_number'] = $car_number;
        $park_data['order_id'] = md5(uniqid().$car_number.$nowTime);
        $park_data['in_channel_id'] = $park_passage_id;
        $park_data['park_id'] = $village_id;
        $park_data['village_id'] = $village_id;
        $park_data['park_sys_type'] = $park_sys_type;
        $park_data['park_name'] = $village_name;
        $park_data['channel_number'] = $passage_info['channel_number'];
        $park_data['passage_name'] = $passage_info['passage_name'];
        $park_data['optname'] = $optname;
        $park_data['garage_id'] = $garage_id;
        $accessMode = 7;
        //月卡车通行
        if (isset($car_info['end_time']) && intval($car_info['end_time']) >= $nowTime) {
            $accessMode = 5;
        }
        $carHandleGetInfoService = new CarHandleGetInfoService();
        if ($passage_info['passage_direction'] == 1) {
            // 进场
            $park_data['in_time'] = $nowTime;
            $park_data['is_paid'] = 0;
            $park_data['is_out'] = 0;
            $inParams = [
                'village_id' => $village_id, 'garage_id' => $garage_id, 'park_passage_id' => $park_passage_id,
                'flow_direction' => 1, 'device_number' => $passage_info['device_number'], 'park_sys_type' => $park_sys_type,
                'flow_time' => $nowTime, 'car_number' => $car_number, 'park_name' => $village_name,
            ];
            $in_park_id = isset($in_park_info['id']) && intval($in_park_info['id']) > 0 ? intval($in_park_info['id']) : 0;
            $inParams['from_id'] = $in_park_id;
            $park_data['accessMode'] = $accessMode;
            $park_data['exception_type'] = 1;
            $carHandleGetInfoService->recordCarFlowInt($park_data, $inParams);
        }

        if ($passage_info['passage_direction'] == 0) {
            $outParams = [
                'village_id' => $village_id, 'garage_id' => $garage_id, 'park_passage_id' => $park_passage_id,
                'flow_direction' => 2, 'device_number' => $passage_info['device_number'], 'park_sys_type' => $park_sys_type,
                'flow_time' => $nowTime,
            ];
            // 出场
            $park_data['is_out'] = 1;
            $park_data['is_paid'] = 1;
            $in_park_info = $carHandleGetInfoService->getCarFlowInt($car_number, $village_id, $park_sys_type);
            $db_house_new_pay_order = new HouseNewPayOrder();
            if (!empty($in_park_info)) {
                $order_id = $in_park_info['order_id'];
                $pay_order_id = isset($in_park_info['pay_order_id']) && intval($in_park_info['pay_order_id']) > 0 ? intval($in_park_info['pay_order_id']) : 0;
                $park_data['order_id'] = $order_id;
                $params = ['flow_temp_id' => $flow_temp_id];
                $parkTemp = $carHandleGetInfoService->getCarFlowTemp($car_number, $village_id, 1, $park_sys_type, false, $order_id);
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
                !$total && $total = isset($payOrderInfo['pay_money']) && $payOrderInfo['pay_money'] ? $payOrderInfo['pay_money'] : 0;
                $in_time = isset($in_park_info['in_time']) && $in_park_info['in_time'] ? intval($in_park_info['in_time']) : 0;
                !$in_time && $in_time = isset($parkTemp['in_time']) && $parkTemp['in_time'] ? intval($parkTemp['in_time']) : 0;
                // todo 记录出场信息
                $accessMode = 3;
                $in_park_id = isset($in_park_info['id']) && intval($in_park_info['id']) > 0 ? intval($in_park_info['id']) : 0;

                $park_data['exception_type'] = 2;
                if ($pay_time > 1 && $in_time > 1 && $pay_time > $in_time) {
                    $outParams['park_time'] = $pay_time - $in_time;
                }
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
                $carHandleGetInfoService->recordCarFlowOut($park_data, $outParams);
            } else {
                // 出场
                $park_data['is_out'] = 1;
                $park_data['out_time'] = $nowTime;
                $park_data['total'] = 0;
                $park_data['pay_type'] = $this->pay_type[6];
                $park_data['exception_type'] = 2;
                $park_data['accessMode'] = $accessMode;
                $carHandleGetInfoService->recordCarFlowOut($park_data, $outParams);
            }
        }
        return true;
    }
}