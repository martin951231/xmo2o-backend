<?php
/***
 *** 博铭智能平台
 *******/

namespace app\community\model\service\Device;

use app\common\model\service\send_message\SmsService;
use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseVillageNewtaskLog;
use app\community\model\db\HouseVillageRoomMeterInfo;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageInfo;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\User;
use app\common\model\service\UserService;
use app\community\model\service\HouseNewMeterService;
use app\community\model\db\HouseNewChargeStandardBind;
use app\community\model\db\HousePropertyDigit;
use app\community\model\service\HouseVillageService;
use app\community\model\service\StorageService;
use app\traits\NbLotWaterElectricMeterTraits;
use think\facade\Cache;

class BoMingPfMeterApiService
{
    use NbLotWaterElectricMeterTraits;

    public $village_id = 0;  
    protected $boming_meter_base_url = 'http://47.120.65.209:8000/api'; //博铭智能平台对接url
    protected $boming_meter_pfaccount = '';  //博铭智能平台用户名
    protected $boming_meter_pfpwd = '';    //博铭智能平台密码
    protected $boming_token = '';    //博铭智能平台请求token

    public function __construct($village_id = 0)
    {
        $this->village_id = $village_id;
        $this->boming_meter_base_url = cfg('boming_meter_base_url');  //博铭智能平台对接url
        $this->boming_meter_base_url = $this->boming_meter_base_url ? rtrim($this->boming_meter_base_url, '/') : '';
        $this->boming_meter_pfaccount = cfg('boming_meter_pfaccount');    //博铭智能平台用户名
        $this->boming_meter_pfaccount = $this->boming_meter_pfaccount ? trim($this->boming_meter_pfaccount) : '';
        $this->boming_meter_pfpwd = cfg('boming_meter_pfpwd');    //博铭智能平台密码
        $this->boming_meter_pfpwd = $this->boming_meter_pfpwd ? trim($this->boming_meter_pfpwd) : '';

    }

    public function getboMingMeterConfig($village_id = 0)
    {
        $this->village_id = $village_id;
        $houseVillageInfoExtend = new HouseVillageInfo();
        $infoExtendObj = $houseVillageInfoExtend->getOne(array('village_id' => $village_id), 'meter_extended_data');
        if ($infoExtendObj && !$infoExtendObj->isEmpty()) {
            $meter_extended_data = $infoExtendObj['meter_extended_data'] ? json_decode($infoExtendObj['meter_extended_data'], JSON_UNESCAPED_UNICODE) : array();
            if ($meter_extended_data && isset($meter_extended_data['meter_apiset']) && $meter_extended_data['meter_apiset']) {

            }
        }
        return true;
    }


    /***
     *抄表开始
     ****/
    public function handleWaterElectricOrder()
    {
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (empty($this->boming_meter_pfaccount) || empty($this->boming_meter_pfpwd)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有配置博铭智能平台对接参数！';
            return $returnArr;
        }
        $whereVillage = array();
        $whereVillage[] = array('village_id', '>', 0);
        $whereVillage[] = array('status', 'in', array(0, 1));
        $vfield = 'village_id,meter_type,meter_time,meter_nopayorder_date,property_id';
        $houseVillageDB = new HouseVillage();
        $houseVillagesObj = $houseVillageDB->getList($whereVillage, $vfield, 0, 0, 'village_id DESC', 0);
        $houseVillages = array();
        if ($houseVillagesObj && !$houseVillagesObj->isEmpty()) {
            $houseVillages = $houseVillagesObj->toArray();
        }
        if (empty($houseVillages)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有可生成账单的小区！';
            return $returnArr;
        }
        $today = date('d');
        $today = intval($today);
        $houseVillageRoomMeterInfoDb = new HouseVillageRoomMeterInfo();
        $today_date = date('Ymd');
        $today_date = intval($today_date);
        foreach ($houseVillages as $hvv) {
            $meter_time = intval($hvv['meter_time']);
            if ($today == $meter_time) {
                //是生成的时间
                $whereArr = array(['village_id', '=', $hvv['village_id']], ['is_del', '<', 1], ['meter_code', '<>', ''], ['meter_pf', '=', 'bomingmeter']);
                $roomMeterInfo = $houseVillageRoomMeterInfoDb->getOneData($whereArr, '*', 'recent_order_time desc');
                if ($roomMeterInfo && !$roomMeterInfo->isEmpty() && $roomMeterInfo['recent_order_time'] < $today_date) {
                    //有数据 先抄表 需要生成账单
                    $queueData = array('meter_pf' => 'bomingmeter', 'village_id' => $hvv['village_id'], 'property_id' => $hvv['property_id'], 'opt_type' => 'meter_village_reading', 'meter_order_time' => $meter_time, 'meter_order_date' => $today_date);
                    $this->nbLotWaterElectricMeterQueuePushToJob($queueData);
                }
            }
        }
        return $returnArr;
    }


    public function meter_village_reading($data = array())
    {
        $meter_order_date = isset($data['meter_order_date']) ? $data['meter_order_date'] : date('Ymd');
        $meter_order_date = intval($meter_order_date);
        $today_date = date('Ymd');
        $meter_order_time = $data['meter_order_time'];
        $nowtime = time();
        $hourKey = date('H');
        $property_id = $data['property_id'];
        $village_id = $data['village_id'];
        $houseVillageRoomMeterInfoDb = new HouseVillageRoomMeterInfo();
        $whereArr = array(['village_id', '=', $data['village_id']], ['is_del', '<', 1], ['meter_code', '<>', ''], ['meter_pf', '=', 'bomingmeter']);
        $whereArr[] = array('recent_order_time', '<', $meter_order_date);
        $field = 'id,village_id,room_id,meter_pf,meter_type,meter_code,last_num,recent_num,recent_time,recent_order_time,recent_order_id,recent_meter_reading_id';
        $roomMeterInfoObj = $houseVillageRoomMeterInfoDb->getListData($whereArr, $field, 'id DESC', 0, 1000);
        $roomMeterList = array();
        if ($roomMeterInfoObj && !$roomMeterInfoObj->isEmpty()) {
            $roomMeterList = $roomMeterInfoObj->toArray();
        }
        if (empty($roomMeterList)) {
            fdump_api(['没有抄表房间数据==' . __LINE__, 'whereArr' => $whereArr, 'data' => $data], 'bomingmeter/meter_village_reading' . $hourKey, 1);
            return true;
        }

        $houseVillageUserBindDb = new HouseVillageUserBind();
        $houseVillageUserVacancyDb = new HouseVillageUserVacancy();

        $housePropertyDigitDb = new HousePropertyDigit();
        $whereDtmp = array(['property_id', '=', $property_id]);
        $infoDigitObj = $housePropertyDigitDb->getOne($whereDtmp, '*');
        $propertyDigit = array();
        if ($infoDigitObj && !$infoDigitObj->isEmpty()) {
            $propertyDigit = $infoDigitObj->toArray();
        }
        foreach ($roomMeterList as $item) {
            $saveArr = array('recent_order_time' => $meter_order_date, 'recent_time' => $meter_order_date);
            $houseVillageRoomMeterInfoDb->saveData(array('id' => $item['id']), $saveArr);
            $charge_type = 'water';
            if ($item['meter_type'] == 1) {
                $charge_type = 'water';
            } elseif ($item['meter_type'] == 2) {
                $charge_type = 'electric';
            }
            $user_bind_obj = $houseVillageUserBindDb->getOne([
                ['vacancy_id', '=', $item['room_id']],
                ['status', '=', 1],
                ['type', 'in', [0, 3]]
            ], 'pigcms_id,uid,village_id,name,phone,single_id,floor_id,layer_id,vacancy_id');
            $user_bind = array();
            if (!empty($user_bind_obj) && !$user_bind_obj->isEmpty()) {
                $user_bind = $user_bind_obj->toArray();
            }

            $roomInfo = array();
            $village_room_obj = $houseVillageUserVacancyDb->getOne([
                ['pigcms_id', '=', $item['room_id']],
                ['is_del', '=', 0],
            ], '*');
            if ($village_room_obj && !$village_room_obj->isEmpty()) {
                $roomInfo = $village_room_obj->toArray();
            }
            if (empty($roomInfo)) {
                fdump_api(['房间不存在跳过==' . __LINE__, 'item' => $item], 'bomingmeter/meter_village_reading' . $hourKey, 1);
                continue;
            }
            if (empty($user_bind)) {
                $user_bind = array();
            }
            $user_bind['single_id'] = $roomInfo['single_id'];
            $user_bind['floor_id'] = $roomInfo['floor_id'];
            $user_bind['layer_id'] = $roomInfo['layer_id'];
            $rule = $this->getChargingTypeMethod($charge_type, $item['room_id']);
            if (!$rule) {
                fdump_api(['收费规则不存在==' . __LINE__, 'item' => $item, 'charge_type' => $charge_type], 'bomingmeter/meter_village_reading' . $hourKey, 1);
                continue;
            }
            $rule_digit = -1;
            if (isset($rule['rule_digit']) && $rule['rule_digit'] > -1 && $rule['rule_digit'] < 5) {
                $rule_digit = $rule['rule_digit'];
            }
            if (($rule_digit <= -1 || $rule_digit >= 5) && $propertyDigit) {
                $rule_digit = $propertyDigit['meter_digit'] ? intval($propertyDigit['meter_digit']) : 2;
            }
            $digit_type = $propertyDigit['type'] == 2 ? 2 : 1;
            $queueData = array('meter_pf' => 'bomingmeter', 'village_id' => $village_id, 'property_id' => $property_id, 'opt_type' => 'meter_create_order', 'meter_order_date' => $meter_order_date);
            $queueData['rule_digit'] = $rule_digit;
            $queueData['digit_type'] = $digit_type;
            $queueData['rule'] = $rule;
            $queueData['user_bind'] = $user_bind;
            $queueData['idid'] = $item['id'];
            $ret = $this->nbLotWaterElectricMeterLaterQueuePushToJob($queueData, 2);
            sleep(2);
        }
        return true;
    }

    public function getChargingTypeMethod($charge_type, $vacancy_id = 0)
    {
        $where = [];
        switch ($charge_type) {
            case "water": //水费
                break;
            case "electric": //电费
                break;
            case "gas": //燃气
                break;
            default:
                return false;
        }
        $where[] = ['n.status', '=', 1];
        $where[] = ['n.charge_type', '=', $charge_type];
        $where[] = ['b.is_del', '=', 1];
        if ($vacancy_id > 0) {
            $where[] = ['b.vacancy_id', '=', $vacancy_id];
        }
        $where[] = ['b.charge_valid_time', '<=', time()];
        $field = 'r.id,r.unit_price,r.not_house_rate,r.rate,r.charge_project_id,r.rule_digit,r.charge_name,b.custom_value';
        $order = 'b.charge_valid_time desc';
        $houseNewChargeStandardBindDb = new HouseNewChargeStandardBind();
        $listObj = $houseNewChargeStandardBindDb->getBindProject($where, $field, $order);
        if ($listObj && !$listObj->isEmpty()) {
            $list = $listObj->toArray();
            return $list;
        }
        return false;
    }

    public function meter_create_order($data = array())
    {
        $idid = $data && isset($data['idid']) ? $data['idid'] : 0;
        $rule_digit = $data && isset($data['rule_digit']) ? $data['rule_digit'] : 2;
        $digit_type = $data && isset($data['digit_type']) ? $data['digit_type'] : 1;
        $rule = $data && isset($data['rule']) ? $data['rule'] : '';
        $user_bind = $data && isset($data['user_bind']) ? $data['user_bind'] : '';
        $village_id = $data && isset($data['village_id']) ? $data['village_id'] : 0;
        $meter_order_date = $data && isset($data['meter_order_date']) ? $data['meter_order_date'] : 0;
        $property_id = $data && isset($data['property_id']) ? $data['property_id'] : 0;
        $hourKey = date('H');
        if ($idid < 1 || empty($rule) || $village_id < 0 || $meter_order_date < 100 || empty($user_bind)) {
            fdump_api(['抄表数据错误=1=' . __LINE__, 'data' => $data], 'bomingmeter/meter_create_order' . $hourKey, 1);
            return true;
        }

        $houseVillageRoomMeterInfoDb = new HouseVillageRoomMeterInfo();
        $houseNewMeterService = new HouseNewMeterService();
        $nowtime = time();
        $whereArr = array(['id', '=', $idid], ['meter_pf', '=', 'bomingmeter'], ['is_del', '<', 1], ['village_id', '=', $village_id]);
        $whereArr[] = array('recent_order_time', '=', $meter_order_date);
        $whereArr[] = array('recent_time', '=', $meter_order_date);
        $roomMeterInfo = $houseVillageRoomMeterInfoDb->getOneData($whereArr);
        if (empty($roomMeterInfo) || $roomMeterInfo->isEmpty()) {
            fdump_api(['抄表数据错误=2=' . __LINE__, 'whereArr' => $whereArr], 'bomingmeter/meter_create_order' . $hourKey, 1);
            return true;
        }
        $roomMeterInfo = $roomMeterInfo->toArray();

        $pdata = array($roomMeterInfo['meter_code']);
        $this->meter_type = $roomMeterInfo['meter_type'];
        $charge_type = '';
        $retReading = array();
        $recent_num = 0;
        $reading_arr = '';
        if ($roomMeterInfo['meter_type'] == 1) {
            //水表 暂时无对接

        } else if ($roomMeterInfo['meter_type'] == 2) {
            //电表
            //$electricInfodata=$this->getElectricMeterInfo($pdata,2);
            $meterUserInfo = $this->getMeterUserInfo($roomMeterInfo['meter_code']);
            if (isset($meterUserInfo['data']) && $meterUserInfo['data'] && isset($meterUserInfo['data']['LASTREADING'])) {
                $retReading = $meterUserInfo['data'];
            }
            if ($retReading && isset($retReading['LASTREADING'])) {
                $reading_arr = $retReading;
                $recent_num = $reading_arr['LASTREADING'] > 0 ? $reading_arr['LASTREADING'] : 0;
            }
        }
        if ($recent_num <= 0 || ($recent_num <= $roomMeterInfo['recent_num'])) {
            fdump_api(['表读数错误=3=' . __LINE__, 'roomMeterInfo' => $roomMeterInfo, 'retReading' => $retReading], 'bomingmeter/meter_create_order' . $hourKey, 1);
            $saveArr = array('recent_time' => $nowtime);
            $extra_data = $roomMeterInfo['extra_data'] ? json_decode($roomMeterInfo['extra_data'], true) : array();
            $extra_data = is_array($extra_data) ? $extra_data : array();
            $extra_data['reading'] = $reading_arr;
            $extra_data_json = json_encode($extra_data, JSON_UNESCAPED_UNICODE);
            $saveArr['extra_data'] = $extra_data_json;
            $houseVillageRoomMeterInfoDb->saveData(array('id' => $roomMeterInfo['id']), $saveArr);
            return true;
        } else {
            $saveArr = array('recent_time' => $nowtime);
            $extra_data = $roomMeterInfo['extra_data'] ? json_decode($roomMeterInfo['extra_data'], true) : array();
            $extra_data = is_array($extra_data) ? $extra_data : array();
            $extra_data['reading'] = $reading_arr;
            $extra_data_json = json_encode($extra_data, JSON_UNESCAPED_UNICODE);
            $saveArr['extra_data'] = $extra_data_json;
            $saveArr['last_num'] = $roomMeterInfo['recent_num'];
            $saveArr['recent_num'] = $recent_num;
            $houseVillageRoomMeterInfoDb->saveData(array('id' => $roomMeterInfo['id']), $saveArr);
        }

        $cost_num = $recent_num - $roomMeterInfo['recent_num'];
        $cost_num = round($cost_num, 2);
        $cost_money = $cost_num * $rule['unit_price'] * $rule['rate'];
        $cost_money = formatNumber($cost_money, $rule_digit, $digit_type);
        $meter_extra_data = array('rule_id' => $rule['id'], 'meter_order_date' => $meter_order_date, 'item' => $roomMeterInfo, 'user_bind' => $user_bind);
        $meterData = [
            'village_id' => $village_id,
            'single_id' => $user_bind['single_id'],
            'floor_id' => $user_bind['floor_id'],
            'layer_id' => $user_bind['layer_id'],
            'layer_num' => $roomMeterInfo['room_id'],
            'charge_name' => $rule['charge_name'],
            'unit_price' => $rule['unit_price'],
            'start_ammeter' => $roomMeterInfo['recent_num'],
            'last_ammeter' => $recent_num,
            'rate' => $rule['rate'],
            'note' => '',
            'cost_num' => $cost_num,
            'cost_money' => $cost_money,
            'add_time' => $nowtime,
            'project_id' => $rule['charge_project_id'],
            'user_name' => $user_bind && isset($user_bind['name']) ? $user_bind['name'] : '',
            'user_bind_id' => $user_bind && isset($user_bind['pigcms_id']) ? $user_bind['pigcms_id'] : 0,
            'user_bind_phone' => $user_bind && isset($user_bind['phone']) ? $user_bind['phone'] : '',
            'work_name' => '',
            'meter_number' => $roomMeterInfo['meter_code'],
            'meter_extra_data' => json_encode($meter_extra_data, JSON_UNESCAPED_UNICODE),
            'source_type' => $roomMeterInfo['meter_pf'],
            'meter_id' => $roomMeterInfo['id'],
            'meter_type' => $roomMeterInfo['meter_type'],
            'opt_meter_time' => $nowtime
        ];
        $meter_reading_id = $houseNewMeterService->addMeterReading($meterData);
        $saveArr = array();
        $saveArr['recent_meter_reading_id'] = $meter_reading_id;
        $orderData = [
            'uid' => $user_bind && isset($user_bind['uid']) ? $user_bind['uid'] : 0,
            'name' => $user_bind && isset($user_bind['name']) ? $user_bind['name'] : '',
            'phone' => $user_bind && isset($user_bind['phone']) ? $user_bind['phone'] : '',
            'pigcms_id' => $user_bind && isset($user_bind['pigcms_id']) ? $user_bind['pigcms_id'] : 0,
            'property_id' => $property_id,
            'village_id' => $village_id,
            'order_type' => $charge_type,
            'order_name' => $rule['charge_name'],
            'room_id' => $roomMeterInfo['room_id'],
            'total_money' => $meterData['cost_money'],
            'modify_money' => $meterData['cost_money'],
            'project_id' => $rule['charge_project_id'],
            'rule_id' => $rule['id'],
            'unit_price' => $meterData['unit_price'],
            'last_ammeter' => $meterData['start_ammeter'],
            'now_ammeter' => $meterData['last_ammeter'],
            'add_time' => $nowtime,
            'meter_reading_id' => $meter_reading_id,
        ];
        $service_start_time = time();
        $whereArr = ['project_id' => $meterData['project_id'], 'village_id' => $village_id, 'layer_num' => $meterData['layer_num']];

        $meter_reading = $houseNewMeterService->getMeterRecordInfo($whereArr, '*', 'id DESC');
        if ($meter_reading && !$meter_reading->isEmpty()) {
            $service_start_time = $meter_reading['add_time'];
        }
        $orderData['service_start_time'] = $service_start_time;
        $service_end_time = time();
        $orderData['service_end_time'] = $service_end_time;

        $orderData['order_type_flag'] = 'bomingmeter_order';
        $orderData['third_meter_code'] = $roomMeterInfo['meter_code'];
        //$orderData['third_meter_id'] = $roomMeterInfo['third_meter_id'];
        $db_house_new_pay_order = new HouseNewPayOrder();
        $order_id = $db_house_new_pay_order->addOne($orderData);

        $saveArr['recent_order_id'] = $order_id;
        $houseVillageRoomMeterInfoDb->saveData(array('id' => $roomMeterInfo['id']), $saveArr);
        fdump_api(['抄表成功' . __LINE__, 'saveArr' => $saveArr, 'user_bind' => $user_bind], 'bomingmeter/meter_create_order' . $hourKey, 1);
        if ($user_bind && isset($user_bind['uid']) && $user_bind['uid'] > 0) {
            //扣除余缴费额操作
            $orderData['order_id'] = $order_id;
            $this->userBalancePayMethod($order_id, $orderData);
        }
        return true;
    }


    public function userBalancePayMethod($order_id, $orderData = array())
    {
        fdump_api(['参数进来了==' . __LINE__, $order_id, $orderData], 'bomingmeter/userBalancePayMethod', 1);
        $uid = $orderData['uid'];
        $village_id = $orderData['village_id'];
        $price = round($orderData['total_money'], 2);
        $property_id = $orderData['property_id'];
        $phone = '';
        $village_name = '';
        $order_no = build_real_orderid($uid);
        $now_village_user = [];
        $storageService = new StorageService();
        if (!empty($uid) && !empty($village_id)) {
            $now_village_user = $storageService->getVillageUser($uid, $village_id);
        }
        fdump_api(['住户余额==' . __LINE__, 'now_village_user' => $now_village_user, 'uid' => $uid, 'price' => $price], 'bomingmeter/userBalancePayMethod', 1);
        // 不允许使用小区余额
        $can_use_village_balance = false;
        $use_village_balance = 0;
        $village_balance = 0;
        $system_balance = 0;
        $charge_type = [
            'electric' => '电费',
            'water' => '水费'
        ];
        $msgbalance = '水电费，扣除小区住户余额';
        $smsService = new SmsService();
        $charge_type_str = isset($charge_type[$orderData['charge_type']]) ? $charge_type[$orderData['charge_type']] : '';
        if (isset($now_village_user['can_use_village_balance']) && $now_village_user['can_use_village_balance']) {
            $phone = $now_village_user['phone'];
            $village_name = $now_village_user['village_name'];
            $can_use_village_balance = true;
            $use_village_balance = round($now_village_user['current_money'], 2);
            if ($use_village_balance < $price) {
                //相应的钱不够
                $storageService->sendMessage($village_id, $order_id, $property_id);//todo 余额扣款失败 发送通知
                return true;
            }
        }
        $desc = $charge_type_str . '预缴，扣除余额，订单编号X1';
        if ($can_use_village_balance) {
            $village_money_data = [
                'uid' => $uid,
                'village_id' => $village_id,
                'type' => 2,
                'current_village_balance' => $price,
                'role_id' => 0,
                'desc' => L_($msgbalance . "，订单编号X1", array("X1" => $order_no)),
                'order_id' => $order_id,
                'order_type' => 1,
                'opt_money_type' => ''
            ];
            $useresult = $storageService->addVillageUserMoney($village_money_data);
            $village_balance = $price;
            fdump_api(['住户余额==' . __LINE__, $village_money_data, $useresult], 'bomingmeter/userBalancePayMethod', 1);
            if ($useresult['error_code']) {
                $datetime = date('m月d日H时');
                $storageService->sendMessage($village_id, $order_id, $property_id);//todo 余额扣款失败 发送通知
                return true;
            }
        } else {
            $userService = new UserService();
            $useresult = $userService->userMoney($uid, $price, L_($desc, array("X1" => $order_no)));
            $system_balance = $price;
            fdump_api(['平台余额==' . __LINE__, $uid, $price, $useresult], 'bomingmeter/userBalancePayMethod', 1);
            if ($useresult['error_code']) {
                $storageService->sendMessage($village_id, $order_id, $property_id);//todo 余额扣款失败 发送通知
                return true;
            }
        }
        $PayOrder = [
            'is_paid' => 1,
            'pay_time' => time(),
            'pay_type' => 4,
            'pay_money' => $price,
            'pay_bind_id' => $orderData['pigcms_id'],
            'pay_bind_name' => $orderData['name'],
            'pay_bind_phone' => $orderData['phone'],
            'is_service_time' => 0,
            'village_balance' => $village_balance,
            'system_balance' => $system_balance
        ];
        $result = $storageService->orderSummary($orderData, $PayOrder, $order_no);
        $this->sendSuccessNoticeMethod($village_id, $orderData, $price, $charge_type_str, $property_id); //todo 支付成功 发送通知
        fdump_api(['扣款结果==' . __LINE__, $order_id, $uid, $price, $order_no, $PayOrder, $result], 'bomingmeter/userBalancePayMethod', 1);
        return true;
    }

    public function sendSuccessNoticeMethod($village_id, $order, $price, $charge_type = '水费', $property_id = 0)
    {
        $houseVillageUserVacancyDb = new HouseVillageUserVacancy();
        $roomListObj = $houseVillageUserVacancyDb->getLists(['pigcms_id' => $order['room_id']]);
        if (!$order['pigcms_id'] && $order['order_id']) {
            $href = get_base_url('pages/houseMeter/NewCollectMoney/billDetails?order_id=' . $order['order_id'] . '&type=2');
        } else {
            $href = get_base_url('pages/houseMeter/NewCollectMoney/billsPaid?pigcms_id=' . $order['pigcms_id'] . '&village_id=' . $village_id);
        }
        $address = '--';
        if ($roomListObj && !$roomListObj->isEmpty()) {
            $roomList = $roomListObj->toArray();
            $room = $roomList[0];
            $houseVillageService = new HouseVillageService();
            $address = $houseVillageService->word_replce_msg(array('single_name' => $room['single_name'], 'floor_name' => $room['floor_name'], 'layer' => $room['layer_name'], 'room' => $room['room']), $village_id);
        }
        $userDb = new User();
        $openid = $userDb->getOne(['uid' => $order['uid']], 'openid');
        $data = [
            'tempKey' => 'TM01008',
            'dataArr' => [
                'href' => $href,
                'wecha_id' => $openid['openid'],
                'first' => '缴费成功提醒',
                'keynote2' => $address,
                'keynote1' => $charge_type,
                'remark' => '缴费时间:' . date('Y-m-d H:i') . '\n' . '缴费金额:￥' . $price,
            ]
        ];

        if ($property_id <= 0 && isset($order['property_id']) && !empty($order['property_id'])) {
            $property_id = $order['property_id'];
        }
        $xtype = 0;
        if ($property_id > 0) {
            $xtype = 1;
        } else {
            $property_id = 0;
        }
        $templateNewsService = new TemplateNewsService();
        $templateNewsService->sendTempMsg($data['tempKey'], $data['dataArr'], 0, $property_id, $xtype);
        fdump_api(['支付成功,发送通知==' . __LINE__, $village_id, $order, $price, $charge_type, $data], 'bomingmeter/sendSuccessNoticeMethod', 1);
        return true;
    }

    /***
     *抄表后 多长时间内订单未支付断电 断水
     ****/
    public function handleLingYinMeterWaterElectricUse()
    {
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (empty($this->boming_meter_pfaccount) || empty($this->boming_meter_pfpwd)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有配置博铭智能平台对接参数！';
            return $returnArr;
        }
        $whereVillage = array();
        $whereVillage[] = array('village_id', '>', 0);
        $whereVillage[] = array('status', 'in', array(0, 1));
        $vfield = 'village_id,village_name,property_id,meter_type,meter_time,meter_nopayorder_date';
        $houseVillageDB = new HouseVillage();
        $houseVillageObj = $houseVillageDB->getList($whereVillage, $vfield, 0, 0, 'village_id DESC', 0);
        $houseVillageNewtaskLog = new HouseVillageNewtaskLog();
        $houseVillages = array();
        if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
            $houseVillages = $houseVillageObj->toArray();
        }
        if (empty($houseVillages)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有小区数据！';
            return $returnArr;
        }
        fdump_api([__LINE__, 'houseVillages' => $houseVillages], 'bomingmeter/handleLingYinMeterWaterElectricUse', 1);
        $nowtime = time();
        $starttime = date('Y-m') . '-01 00:00:01';
        $starttime = strtotime($starttime);
        $endtime = date('Y-m') . '-' . date('t') . ' 23:59:59';
        $endtime = strtotime($endtime);
        $no_pay_order_time = 0;
        $db_house_new_pay_order = new HouseNewPayOrder();
        foreach ($houseVillages as $hvv) {
            if ($hvv['meter_nopayorder_date'] <= 0) {
                continue;
            }
            $hvv['meter_nopayorder_date'] = intval($hvv['meter_nopayorder_date']);    //抄表后多少天
            $no_pay_order_time = strtotime("-" . $hvv['meter_nopayorder_date'] . " day") + 29 * 60; //每30分钟执行一次
            //按月抄表
            $hvv['meter_time'] = intval($hvv['meter_time']);  //抄表日期
            $taskLogWhere = array();
            $taskLogWhere[] = ['village_id', '=', $hvv['village_id']];
            $taskLogWhere[] = ['add_time', '>', $starttime];
            $taskLogWhere[] = ['add_time', '<', $endtime];
            $taskLogWhere[] = ['task_type', '=', 'handleBoMingMeterApi'];
            $tmplog = $houseVillageNewtaskLog->getOneData($taskLogWhere, '*');
            $dateTmp = date('Y-m') . '-' . $hvv['meter_time'] . ' 00:00:01';
            $dateTmpTime = strtotime($dateTmp);
            $newtime = strtotime("+" . $hvv['meter_nopayorder_date'] . " day", $dateTmpTime); //该操作断水电的时间
            $exec_day = $hvv['meter_nopayorder_date'] + $hvv['meter_time'];
            $current_day = date('j');
            $exedate = date('Ymd', $newtime);
            $exedate = intval($exedate);
            fdump_api([__LINE__, $dateTmp, $newtime, 'exedate' => $exedate, 'exec_day' => $exec_day, 'current_day' => $current_day, 'taskLogWhere' => $taskLogWhere, 'tmplog' => $tmplog], 'bomingmeter/handlelingyinmeterWaterElectricUse', 1);
            if (!empty($tmplog) && $exec_day == $current_day) {
                //到时间执行 更新掉执行时间
                $houseVillageNewtaskLog->updateOneData(['id' => $tmplog['id']], ['execute_time' => $nowtime]);
            } elseif (!empty($tmplog) && $tmplog['execute_time'] > 10) {
                continue;
            } elseif (empty($tmplog)) {
                $tmplogarr = array();
                $tmplogarr['village_id'] = $hvv['village_id'];
                $tmplogarr['task_type'] = 'handleBoMingMeterApi';
                $tmplogarr['need_execute_time'] = $exedate;
                $tmplogarr['add_time'] = $nowtime;
                $houseVillageNewtaskLog->addOneData($tmplogarr);
                continue;
            } else {
                continue;
            }
            fdump_api([__LINE__, 'no_pay_order_time' => $no_pay_order_time], 'bomingmeter/handleLingYinMeterWaterElectricUse', 1);
            if ($no_pay_order_time < 3600) {
                continue;
            }
            $no_pay_order_2time = $no_pay_order_time - 3600 * 24 * 30;
            $whereArr = array();
            $whereArr[] = ['village_id', '=', $hvv['village_id']];
            $whereArr[] = ['is_paid', '=', 2];
            $whereArr[] = ['is_discard', '=', 1];
            $whereArr[] = ['add_time', '<=', $no_pay_order_time];
            $whereArr[] = ['add_time', '>=', $no_pay_order_2time];
            $whereArr[] = ['third_opt_status', '<', 1];
            $whereArr[] = ['order_type_flag', '=', 'bomingmeter_order'];
            $whereArr[] = ['third_meter_code', '<>', ''];
            $fieldStr = 'order_id,uid,pigcms_id,name,phone,order_type,room_id,property_id,village_id,order_name,order_type_flag,extra_data,third_meter_code';
            //$orders = $db_house_new_pay_order->getOrder($whereArr, $fieldStr, 'order_id ASC');
            $orders = $db_house_new_pay_order->getPayLists($whereArr, $fieldStr,1,10,'update_time ASC');
            $nowtime = time();
            if ($orders && !$orders->isEmpty()) {
                $orders = $orders->toArray();
                fdump_api([__LINE__, 'orders' => $orders], 'bomingmeter/handleLingYinMeterWaterElectricUse', 1);
                foreach ($orders as $vv) {
                    $ret = $this->meterCloseUse($vv['third_meter_code'], $vv['room_id']);
                    $saveArr=array();
                    $saveArr['update_time'] = $nowtime;
                    if ($ret && is_array($ret) && isset($ret['err_code']) && $ret['err_code'] == 0) {
                        //操作成功了
                        $extra_data = !empty($vv['extra_data']) ? json_decode($vv['extra_data'], 1) : array();
                        $extra_data = is_array($extra_data) ? $extra_data : array();
                        $extra_data['boming_meter'] = array('third_meter_code' => $vv['third_meter_code'], 'opt_time' => $nowtime, 'opt_type' => 'off');
                        $extra_data['boming_meter']['closerMeter'] = $ret;
                        $saveArr['extra_data'] =  json_encode($extra_data, JSON_UNESCAPED_UNICODE);
                        $saveArr['third_opt_status'] = 1;
                    }
                    $db_house_new_pay_order->saveOne(['order_id' => $vv['order_id']], $saveArr);
                    //usleep(500000); //延迟半秒
                    sleep(10); //接口要求30内不能重复调用
                }
            }
        }
        return true;
    }
    
    public function getApiToken($is_cache = true)
    {
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'token' => '');
        if (empty($this->boming_meter_pfaccount) || empty($this->boming_meter_pfpwd) || empty($this->boming_meter_base_url)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '客户未配置对接信息！';
            return $returnArr;
        }
        $nowtime = time();
        $cacheTokenKey = 'village:cache:boming_meter_token:' . $this->boming_meter_pfaccount;
        $dataCache = Cache::get($cacheTokenKey);
        if ($is_cache && is_array($dataCache) && isset($dataCache['token']) && $dataCache['expire_time'] > $nowtime) {
            $this->boming_token = $dataCache['token'];
            $returnArr['token'] = $dataCache['token'];
            fdump_api(['_Line_' . __LINE__, 'returnArr' => $returnArr], 'bomingmeter/getApiToken' . date('H'), 1);
            return $returnArr;
        }
        $dataArr = array('UserName' => $this->boming_meter_pfaccount, 'UserPwd' => $this->boming_meter_pfpwd);
        $dataArrJson=json_encode($dataArr,JSON_UNESCAPED_UNICODE);
        $result = $this->commonApiRequest('/auth/login', $dataArrJson);
        //$uri='/auth/login?UserName='.$this->boming_meter_pfaccount.'&UserPwd='.$this->boming_meter_pfpwd;
        //$result = $this->commonApiRequest($uri,'','GET');
        if ($result && isset($result['token']) && !empty($result['token'])) {
            $this->boming_token = $result['token'];  //有72小时后过期
            $returnArr['token'] = $result['token'];
            $expire_time = time() + 3600 * 60; //我们60小时过期处理
            $tokenArr = array('token' => $result['token'], 'expire_time' => $expire_time);
            Cache::set($cacheTokenKey, $tokenArr, 3600 * 60);
        }else {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '获取Token失败了！';
            if($result && isset($result['title']) && $result['title']){
                $returnArr['err_msg']= isset($result['status']) ?  $result['status'].'：'.$result['title'] : $result['title'];
            }
        }
        fdump_api(['_Line_' . __LINE__, 'dataArrJson' => $dataArrJson ,'result' => $result], 'bomingmeter/getApiToken' . date('H'), 1);
        return $returnArr;
    }

    /**
     * 公用的组装请求.
     */

    public function commonApiRequest($methodUri = '', $params = array(), $method = 'post')
    {
        $requestApiUrl = $this->boming_meter_base_url . $methodUri;
        $headers = array();
        $headers[] = "Accept-Charset: utf-8";
        $headers[] = 'Content-Encoding: utf-8';
        if (strpos($methodUri, 'auth/login') !== false) {
            $headers[] = "Content-type: application/json;";
            $requestJsonStr = $params;
        } else {
            $headers[] = "Content-type: application/json;";
            $headers[] = 'Authorization:Bearer ' . $this->boming_token;
            $requestJsonStr = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $result = http_request($requestApiUrl, $method, $requestJsonStr, $headers);
        $responseData = array();
        fdump_api(['_Line_' . __LINE__, 'params' => $params, 'headers' => $headers, 'requestData' => $requestJsonStr, 'requestApiUrl' => $requestApiUrl, 'result' => $result], 'bomingmeter/commonApiRequest' . date('H'), 1);
        if ($result && $result['1']) {
            $responseData = json_decode($result['1'], true);
            $responseData = $responseData !==null ? $responseData:$result['1'];
        }
        return $responseData;
    }
    /***
     *断水断电
     ***/
    public function meterCloseUse($meter_code = '', $room_id = 0, $meter_type = 1)
    {
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $this->getApiToken();
        if (empty($this->boming_token)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = 'Token获取失败！';
            return $returnArr;
        }
        $meterData = array();
        $meterTypeStr = 'water';
        if ($meter_type == 2) {
            $meterTypeStr = 'ele';
        }
        $houseVillageRoomMeterInfoDb = new HouseVillageRoomMeterInfo();
        if (empty($meter_code) && $room_id > 0 && $meter_type > 0) {
            $whereArr = array(['room_id', '=', $room_id], ['meter_pf', '=', 'bomingmeter'], ['is_del', '<', 1], ['meter_type', '=', $meter_type]);
            $roomMeterInfo = $houseVillageRoomMeterInfoDb->getOneData($whereArr);
            if ($roomMeterInfo && !$roomMeterInfo->isEmpty()) {
                $meterData = array('meterNum' => $roomMeterInfo['meter_code'], 'meterType' => $meterTypeStr);
            }
        } else if ($meter_code) {
            $meterData = array('meterNum' => $meter_code, 'meterType' => $meterTypeStr);
        }
        if (empty($meterData)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有表号数据！';
            return $returnArr;
        }
        $returnArr = $this->handleControlCmd($meterData,3);
        fdump_api(['_Line_' . __LINE__, 'closeMeterUse', 'meterData' => $meterData, 'returnArr' => $returnArr], 'bomingmeter/handleControlCmd' . date('H'), 1);
        return $returnArr;
    }

    /***
     * $cmdData中每个数组字段
     *$meterNum 表号
     * $action 3 断闸  5和闸送电
     * $meterType water 水 ele电
     **/
    public function handleControlCmd($cmdData = array(),$action=0 )
    {
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $this->getApiToken();
        if (empty($this->boming_token)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = 'Token获取失败！';
            return $returnArr;
        }
        if (!in_array($action,[3,5])) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '操作类型错误！';
            return $returnArr;
        }
        $uri='/power/SetPowerTableStatus?MeterComId='.$cmdData['meterNum'].'&Flag='.$action;
        $result = $this->commonApiRequest($uri,'', 'GET');
        if (empty($result) || (is_array($result) && isset($result['parserMap']))) {
            $returnArr['data'] = $result;
        }else {
            $err_msg='远程操作电表断电失败';
            if($action==5){
                $err_msg='远程操作电表送电失败';
            }
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = $err_msg;
            if($result && isset($result['title']) && $result['title']){
                $returnArr['err_msg']= isset($result['status']) ?  $result['status'].'：'.$result['title'] : $result['title'];
                $returnArr['err_msg'] = $returnArr['err_msg'].$err_msg;
            }else if($result && !is_array($result) ){
                $returnArr['err_msg'] .='（'.$result.'）';
            }
        }
        fdump_api(['_Line_' . __LINE__, 'uri' => $uri, 'result' => $result], 'bomingmeter/handleControlCmd' . date('H'), 1);
        return $returnArr;
    }
    
    public function getMeterTableStatus($meterNum = '')
    {
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $this->getApiToken();
        if (empty($this->boming_token)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = 'Token获取失败！';
            return $returnArr;
        }
        $uri='/power/GetPowerTableStatus?MeterComId='.$meterNum;
        $result = $this->commonApiRequest($uri,'', 'GET');
        if (!empty($result) && is_array($result) && isset($result['parserMap'])) {
            $returnArr['data'] = $result;
        }else {
            $err_msg='未查询到电表状态数据';
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = $err_msg;
            if(is_array($result) && isset($result['title']) && $result['title']){
                $returnArr['err_msg']= isset($result['status']) ?  $result['status'].'：'.$result['title'] : $result['title'];
                $returnArr['err_msg'] = $returnArr['err_msg'].$err_msg;
            }else if($result && !is_array($result) ){
                $returnArr['err_msg'] .='（'.$result.'）';
            }
        }
        fdump_api(['_Line_' . __LINE__, 'uri' => $uri, 'result' => $result], 'bomingmeter/getMeterTableStatus' . date('H'), 1);
        return $returnArr;
    }

    public function getMeterUserInfo($meter_code = '')
    {
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $this->getApiToken();
        if (empty($this->boming_token)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = 'Token获取失败！';
            return $returnArr;
        }
        $uri='/user/GetUserInfo?MeterComId=' . $meter_code;
        $result = $this->commonApiRequest($uri, '', 'GET');
        if ($result && isset($result['0']) && !empty($result['0'])) {
            $returnArr['data'] = $result['0'];
        }elseif($result && isset($result['LASTREADING'])){
            $returnArr['data'] = $result;
        }else {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '获取抄表数据失败了！';
            if($result && isset($result['title']) && $result['title']){
                $returnArr['err_msg']= isset($result['status']) ?  $result['status'].'：'.$result['title'] : $result['title'];
                $returnArr['err_msg'] = $returnArr['err_msg'].' 未获取到抄表数据！';
            }
        }
        fdump_api(['_Line_' . __LINE__,'uri'=>$uri ,'result' => $result,'returnArr'=>$returnArr], 'bomingmeter/getMeterUserInfo' . date('H'), 1);
        return $returnArr;
    }
    
    /***
     *查询电表数据 获取最新抄表数据
     * ZYT 是抄表数据
     ***/
    public function getElectricMeterInfo($pdata, $meter_type = 0)
    {
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $this->getApiToken();
        if (empty($this->boming_token)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = 'Token获取失败！';
            return $returnArr;
        }
        $result = $this->commonApiRequest('/power/GetPowerTableInfoDb', $pdata);
        if (is_array($result) && isset($result['0']) && !empty($result['0'])) {
            $returnArr['data'] = $result['0'];
        }else {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '获取抄表数据失败了！';
            if(is_array($result) && isset($result['title']) && $result['title']){
                $returnArr['err_msg']= isset($result['status']) ?  $result['status'].'：'.$result['title'] : $result['title'];
                $returnArr['err_msg'] = $returnArr['err_msg'].' 未获取到抄表数据！';
            }else{
                $returnArr['err_msg'] .='（'.$result.'）';
            }
        }
        fdump_api(['_Line_' . __LINE__, 'pdata' => $pdata, 'result' => $result], 'bomingmeter/getElectricMeterInfo' . date('H'), 1);
        return $returnArr;
    }

    /***
     *通水通电
     ***/

    public function meterOpenUse($meter_code = '', $room_id = 0, $meter_type = 1)
    {
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $this->getApiToken();
        if (empty($this->boming_token)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = 'Token获取失败！';
            return $returnArr;
        }
        $meterData = array();
        $meterTypeStr = 'water';
        if ($meter_type == 2) {
            $meterTypeStr = 'ele';
        }
        $houseVillageRoomMeterInfoDb = new HouseVillageRoomMeterInfo();
        if (empty($meter_code) && $room_id > 0 && $meter_type > 0) {
            $whereArr = array(['room_id', '=', $room_id], ['meter_pf', '=', 'bomingmeter'], ['is_del', '<', 1], ['meter_type', '=', $meter_type]);
            $roomMeterInfo = $houseVillageRoomMeterInfoDb->getOneData($whereArr);
            if ($roomMeterInfo && !$roomMeterInfo->isEmpty()) {
                $meterData = array('meterNum' => $roomMeterInfo['meter_code'], 'meterType' => $meterTypeStr);
            }
        } else if ($meter_code) {
            $meterData = array('meterNum' => $meter_code, 'meterType' => $meterTypeStr);
        }
        if (empty($meterData)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有表号数据！';
            return $returnArr;
        }
        $returnArr = $this->handleControlCmd($meterData,5);
        fdump_api(['_Line_' . __LINE__, 'openMeterUse', 'meterData' => $meterData, 'returnArr' => $returnArr], 'bomingmeter/handleControlCmd' . date('H'), 1);
        return $returnArr;
    }
}