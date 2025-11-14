<?php

/***
 *** 聆音智能平台
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
class LingYinPfMeterApiService
{
    use NbLotWaterElectricMeterTraits;
    public $village_id = 0;  //聆音智能平台
    protected $lingyin_meter_base_url = ''; //聆音智能平台对接url
    protected $lingyin_meter_pfaccount = '';  //聆音智能平台用户名
    protected $lingyin_meter_pfpwd = '';    //聆音智能平台密码
    protected $lingyin_token = '';    //聆音智能平台请求token
    public function __construct($village_id = 0)
    {
        $this->village_id = $village_id;
        $this->lingyin_meter_base_url = cfg('lingyin_meter_base_url');  //聆音智能平台对接url
        $this->lingyin_meter_base_url = $this->lingyin_meter_base_url  ? rtrim($this->lingyin_meter_base_url,'/'):'';
        $this->lingyin_meter_pfaccount = cfg('lingyin_meter_pfaccount');    //聆音智能平台用户名
        $this->lingyin_meter_pfaccount=$this->lingyin_meter_pfaccount ? trim($this->lingyin_meter_pfaccount):'';
        $this->lingyin_meter_pfpwd = cfg('lingyin_meter_pfpwd');    //聆音智能平台密码
        $this->lingyin_meter_pfpwd=$this->lingyin_meter_pfpwd ? trim($this->lingyin_meter_pfpwd):'';
        
    }
    
    public function getLingYinMeterConfig($village_id=0)
    {
        $this->village_id = $village_id;
        $houseVillageInfoExtend = new HouseVillageInfo();
        $infoExtendObj = $houseVillageInfoExtend->getOne(array('village_id'=>$village_id),'meter_extended_data');
        if($infoExtendObj && !$infoExtendObj->isEmpty()){
            $meter_extended_data=$infoExtendObj['meter_extended_data'] ? json_decode($infoExtendObj['meter_extended_data'],JSON_UNESCAPED_UNICODE):array();
            if($meter_extended_data && isset($meter_extended_data['meter_apiset']) && $meter_extended_data['meter_apiset']){

            }
        }
        return true;
    }
    
    /***
     * 接收表推送过来的数据
     **/
    public function receiveMeterData($meterData = array())
    {
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (empty($meterData) || !is_array($meterData)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有数据！';
            return $returnArr;
        }
        fdump_api(['_Line_' . __LINE__, 'meterData' => $meterData], 'lingyinmeter/receiveMeterInfo' . date('H'), 1);
        if (empty($this->lingyin_meter_pfaccount) || empty($this->lingyin_meter_pfpwd)){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '客户未配置对接信息！';
            return $returnArr;
        }
        $nowtime = time();
        $houseVillageRoomMeterInfoDb = new HouseVillageRoomMeterInfo();
        foreach ($meterData as $mvv) {
            if (!isset($mvv['meterNum']) || empty($mvv['meterNum'])) {
                continue;
            }
            if (!isset($mvv['reading'])) {
                continue;
            }
            $whereArr = array(['meter_code', '=', $mvv['meterNum']], ['meter_pf', '=', 'lingyinmeter'], ['is_del', '<', 1]);
            if (isset($mvv['meterType']) && $mvv['meterType'] == 'water') {
                //水表
                $whereArr[] = array('meter_type', '=', 1);
            } else if (isset($mvv['meterType']) && $mvv['meterType'] == 'ele') {
                //电表
                $whereArr[] = array('meter_type', '=', 2);
            }
            $recent_status = -1;  //-1异常1开2关
            if (isset($mvv['valveStatus']) && $mvv['valveStatus'] == 0) {
                $recent_status = 2;
            }
            if (isset($mvv['valveStatus']) && $mvv['valveStatus'] == 1) {
                $recent_status = 1;
            }
            $roomMeterInfo = $houseVillageRoomMeterInfoDb->getOneData($whereArr);
            if ($roomMeterInfo && !$roomMeterInfo->isEmpty()) {
                $saveArr = array('recent_status' => $recent_status, 'recent_num' => $mvv['reading'], 'recent_time' => $nowtime);
                $extra_data_json = json_encode(array('reading'=>$mvv), JSON_UNESCAPED_UNICODE);
                $saveArr['extra_data'] = $extra_data_json;
                $houseVillageRoomMeterInfoDb->saveData(array('id'=>$roomMeterInfo['id']),$saveArr);
            }
        }
        return $returnArr;
    }
    
    /***
     *生成订单
     ****/
    
    public function handleWaterElectricOrder(){
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (empty($this->lingyin_meter_pfaccount) || empty($this->lingyin_meter_pfpwd)){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有配置聆音智能平台对接参数！';
            return $returnArr;
        }
        $whereVillage=array();
        $whereVillage[]=array('village_id','>',0);
        $whereVillage[]=array('status','in',array(0,1));
        $vfield='village_id,meter_type,meter_time,meter_nopayorder_date,property_id';
        $houseVillageDB= new HouseVillage();
        $houseVillagesObj=$houseVillageDB->getList($whereVillage,$vfield,0,0,'village_id DESC',0);
        $houseVillages=array();
        if($houseVillagesObj && !$houseVillagesObj->isEmpty()) {
            $houseVillages = $houseVillagesObj->toArray();
        }
        if(empty($houseVillages)){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有可生成账单的小区！';
            return $returnArr;
        }
        $today=date('d');
        $today=intval($today);
        $houseVillageRoomMeterInfoDb = new HouseVillageRoomMeterInfo();
        $today_date=date('Ymd');
        $today_date=intval($today_date);
        foreach ($houseVillages as $hvv){
           $meter_time= intval($hvv['meter_time']);
           if($today==$meter_time){
                //是生成的时间
               $whereArr=array(['village_id','=',$hvv['village_id']],['is_del', '<', 1],['meter_code', '<>', ''],['meter_pf', '=', 'lingyinmeter'],['recent_num','>',0]);
               $roomMeterInfo = $houseVillageRoomMeterInfoDb->getOneData($whereArr,'*','recent_order_time desc');
               if($roomMeterInfo && !$roomMeterInfo->isEmpty() && $roomMeterInfo['recent_order_time']<$today_date){
                   //有数据 需要生成账单
                   $queueData=array('meter_pf'=>'lingyinmeter','village_id'=>$hvv['village_id'],'property_id'=>$hvv['property_id'],'opt_type'=>'meter_create_order','meter_order_time'=>$meter_time,'meter_order_date'=>$today_date);
                   $this->nbLotWaterElectricMeterQueuePushToJob($queueData);
               }
           }
        }
        return $returnArr;
    }
    //todo 查询对应科目的收费标准
    public function getChargingTypeMethod($charge_type,$vacancy_id=0){
        $where=[];
        switch ($charge_type) {
            case "water": //水费
                break;
            case "electric": //电费

                break;
            case "gas": //燃气费

                break;
            default:
                return false;
        }
        $where[]=['n.status', '=', 1];
        $where[]=['n.charge_type', '=', $charge_type];
        $where[]=['b.is_del', '=', 1];
        if($vacancy_id>0){
            $where[]=['b.vacancy_id', '=', $vacancy_id];
        }
        $where[] = ['b.charge_valid_time','<=',time()];
        $field='r.id,r.unit_price,r.not_house_rate,r.rate,r.charge_project_id,r.rule_digit,r.charge_name,b.custom_value';
        $order='b.charge_valid_time desc';
        $houseNewChargeStandardBindDb=new HouseNewChargeStandardBind();
        $listObj= $houseNewChargeStandardBindDb->getBindProject($where,$field,$order);
        if($listObj && !$listObj->isEmpty()){
            $list= $listObj->toArray();
            return $list;
        }
        return false;
    }

    public function meter_create_order($data = array())
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
        $whereArr = array(['village_id', '=', $data['village_id']], ['is_del', '<', 1], ['meter_code', '<>', ''], ['meter_pf', '=', 'lingyinmeter'], ['recent_num', '>', 0]);
        $whereArr[] = array('recent_order_time', '<', $meter_order_date);
        $field='id,village_id,room_id,meter_pf,meter_type,meter_code,last_num,recent_num,recent_time,recent_order_time,recent_order_id,recent_meter_reading_id';
        $roomMeterInfoObj = $houseVillageRoomMeterInfoDb->getListData($whereArr, $field, 'id DESC', 0, 1000);
        $roomMeterList = array();
        if ($roomMeterInfoObj && !$roomMeterInfoObj->isEmpty()) {
            $roomMeterList = $roomMeterInfoObj->toArray();
        }
        if (empty($roomMeterList)) {
            fdump_api(['没有抄表房间数据==' . __LINE__, 'whereArr' => $whereArr, 'data' => $data], 'lingyinmeter/meter_create_order' . $hourKey, 1);
            return true;
        }
        $houseNewMeterService = new HouseNewMeterService();
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
            $saveArr = array('recent_order_time' => $meter_order_date);
            $houseVillageRoomMeterInfoDb->saveData(array('id' => $item['id']), $saveArr);
            if ($item['recent_num'] <= $item['last_num']) {
                //不正常的不生成记录和订单
                fdump_api(['表读数错误==' . __LINE__, 'item' => $item], 'lingyinmeter/meter_create_order' . $hourKey, 1);
            } else {
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
                if (empty($user_bind_obj) && !$user_bind_obj->isEmpty()) {
                    $user_bind = $user_bind_obj->toArray();
                }
                /*
                if(empty($user_bind)){
                    fdump_api(['房间业主不存在跳过=='.__LINE__, 'item' => $item],'lingyinmeter/meter_create_order'.$hourKey,1);
                    continue;
                }
                */
                $roomInfo = array();
                $village_room_obj = $houseVillageUserVacancyDb->getOne([
                    ['pigcms_id', '=', $item['room_id']],
                    ['is_del', '=', 0],
                ], '*');
                if ($village_room_obj && !$village_room_obj->isEmpty()) {
                    $roomInfo = $village_room_obj->toArray();
                }
                if (empty($roomInfo)) {
                    fdump_api(['房间不存在跳过==' . __LINE__, 'item' => $item], 'lingyinmeter/meter_create_order' . $hourKey, 1);
                    continue;
                }
                $rule = $this->getChargingTypeMethod($charge_type, $item['room_id']);
                if (!$rule) {
                    fdump_api(['收费规则不存在==' . __LINE__, 'item' => $item, 'charge_type' => $charge_type], 'lingyinmeter/meter_create_order' . $hourKey, 1);
                    continue;
                }
                $rule_digit = -1;
                if (isset($rule['rule_digit']) && $rule['rule_digit'] > -1 && $rule['rule_digit'] < 5) {
                    $rule_digit = $rule['rule_digit'];
                }
                if (($rule_digit <= -1 || $rule_digit >= 5) && $propertyDigit) {
                    $rule_digit = $propertyDigit['meter_digit'] ? intval($propertyDigit['meter_digit']) : 2;
                }
                
                $saveArr['last_num']=$item['recent_num'];
                $houseVillageRoomMeterInfoDb->saveData(array('id' => $item['id']), $saveArr);
                
                $digit_type = $propertyDigit['type'] == 2 ? 2 : 1;

                $cost_num = $item['recent_num'] - $item['last_num'];
                $cost_num = round($cost_num, 2);
                $cost_money = $cost_num * $rule['unit_price'] * $rule['rate'];
                $cost_money = formatNumber($cost_money, $rule_digit, $digit_type);
                $meter_extra_data = array('rule_id' => $rule['id'], 'meter_order_date' => $meter_order_date,'item' => $item,'user_bind'=>$user_bind);
                $meterData = [
                    'village_id' => $village_id,
                    'single_id' => $roomInfo['single_id'],
                    'floor_id' => $roomInfo['floor_id'],
                    'layer_id' => $roomInfo['layer_id'],
                    'layer_num' => $item['room_id'],
                    'charge_name' => $rule['charge_name'],
                    'unit_price' => $rule['unit_price'],
                    'start_ammeter' => $item['last_num'],
                    'last_ammeter' => $item['recent_num'],
                    'rate' => $rule['rate'],
                    'note' => '',
                    'cost_num' => $cost_num,
                    'cost_money' => $cost_money,
                    'add_time' => $nowtime,
                    'project_id' => $rule['charge_project_id'],
                    'user_name' => $user_bind ? $user_bind['name'] : '',
                    'user_bind_id' => $user_bind ? $user_bind['pigcms_id'] : 0,
                    'user_bind_phone' => $user_bind ? $user_bind['phone'] : '',
                    'work_name' => '',
                    'meter_number' => $item['meter_code'],
                    'meter_extra_data' => json_encode($meter_extra_data, JSON_UNESCAPED_UNICODE),
                    'source_type' => $item['meter_pf'],
                    'meter_id' => $item['id'],
                    'meter_type' => $item['meter_type'],
                    'opt_meter_time' => $nowtime
                ];
                $meter_reading_id = $houseNewMeterService->addMeterReading($meterData);
                $saveArr['recent_meter_reading_id'] = $meter_reading_id;
                $orderData = [
                    'uid' => $user_bind ? $user_bind['uid'] : 0,
                    'name' => $user_bind ? $user_bind['name'] : '',
                    'phone' => $user_bind ? $user_bind['phone'] : '',
                    'pigcms_id' => $user_bind ? $user_bind['pigcms_id'] : 0,
                    'property_id' => $property_id,
                    'village_id' => $village_id,
                    'order_type' => $charge_type,
                    'order_name' => $rule['charge_name'],
                    'room_id' => $item['room_id'],
                    'total_money' => $meterData['cost_money'],
                    'modify_money' => $meterData['cost_money'],
                    'project_id' => $rule['charge_project_id'],
                    'rule_id' => $rule['id'],
                    'unit_price' => $meterData['unit_price'],
                    'last_ammeter' => $meterData['start_ammeter'],
                    'now_ammeter' => $meterData['last_ammeter'],
                    'add_time' => $nowtime,
                    'meter_reading_id'=>$meter_reading_id,
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
                
                $orderData['order_type_flag'] = 'lingyinmeter_order';
                $orderData['third_meter_code'] = $item['meter_code'];
                
                $db_house_new_pay_order = new HouseNewPayOrder();
                $order_id = $db_house_new_pay_order->addOne($orderData);
                
                $saveArr['recent_order_id'] = $order_id;
                $houseVillageRoomMeterInfoDb->saveData(array('id' => $item['id']), $saveArr);
                
                if($user_bind && $user_bind['uid']>0){
                    //扣除余缴费额操作
                    $orderData['order_id']=$order_id;
                    $this->userBalancePayMethod($order_id, $orderData);
                }
                
            }
        }
        
        return true;
    }

    //todo 抵扣余额，支付成功
    public function userBalancePayMethod($order_id,$orderData=array()){
        fdump_api(['参数进来了=='.__LINE__,$order_id,$orderData],'lingyinmeter/userBalancePayMethod',1);
        $uid=$orderData['uid'];
        $village_id=$orderData['village_id'];
        $price=round($orderData['total_money'],2);
        $property_id=$orderData['property_id'];
        $phone='';
        $village_name='';
        $order_no=build_real_orderid($uid);
        $now_village_user = [];
        $storageService = new StorageService();
        if(!empty($uid)&&!empty($village_id)){
            $now_village_user = $storageService->getVillageUser($uid,$village_id);
        }
        fdump_api(['住户余额=='.__LINE__,'now_village_user'=>$now_village_user,'uid'=>$uid,'price'=>$price],'lingyinmeter/userBalancePayMethod',1);
        // 不允许使用小区余额
        $can_use_village_balance = false;
        $use_village_balance=0;
        $village_balance=0;
        $system_balance=0;
        $charge_type=[
            'electric'=>'电费',
            'water'=>'水费'
        ];
        $msgbalance='水电费，扣除小区住户余额';
        $smsService=new SmsService();
        $charge_type_str=isset($charge_type[$orderData['charge_type']]) ? $charge_type[$orderData['charge_type']]:'';
        if(isset($now_village_user['can_use_village_balance']) && $now_village_user['can_use_village_balance']){
            $phone=$now_village_user['phone'];
            $village_name=$now_village_user['village_name'];
            $can_use_village_balance = true;
            $use_village_balance=round($now_village_user['current_money'],2);
            if($use_village_balance<$price){
                //相应的钱不够
                $storageService->sendMessage($village_id, $order_id,$property_id);//todo 余额扣款失败 发送通知
                return true;
            }
        }
        $desc=$charge_type_str.'预缴，扣除余额，订单编号X1';
        if($can_use_village_balance){
            $village_money_data=[
                'uid'=>$uid,
                'village_id'=>$village_id,
                'type'=>2,
                'current_village_balance'=>$price,
                'role_id'=>0,
                'desc'=>L_($msgbalance."，订单编号X1", array("X1" => $order_no)),
                'order_id'=>$order_id,
                'order_type'=>1,
                'opt_money_type'=>''
            ];
            $useresult = $storageService->addVillageUserMoney($village_money_data);
            $village_balance=$price;
            fdump_api(['住户余额=='.__LINE__,$village_money_data,$useresult],'lingyinmeter/userBalancePayMethod',1);
            if ($useresult['error_code']) {
                $datetime=date('m月d日H时');
                $storageService->sendMessage($village_id, $order_id,$property_id);//todo 余额扣款失败 发送通知
                return true;
            }
        } else {
            $userService=new UserService();
            $useresult = $userService->userMoney($uid, $price, L_($desc, array("X1" => $order_no)));
            $system_balance=$price;
            fdump_api(['平台余额=='.__LINE__,$uid,$price,$useresult],'lingyinmeter/userBalancePayMethod',1);
            if ($useresult['error_code']) {
                $storageService->sendMessage($village_id, $order_id,$property_id);//todo 余额扣款失败 发送通知
                return true;
            }
        }
        $PayOrder=[
            'is_paid'=>1,
            'pay_time'=>time(),
            'pay_type'=>4,
            'pay_money'=>$price,
            'pay_bind_id'=>$orderData['pigcms_id'],
            'pay_bind_name'=>$orderData['name'],
            'pay_bind_phone'=>$orderData['phone'],
            'is_service_time'=>0,
            'village_balance'=>$village_balance,
            'system_balance'=>$system_balance
        ];
        $result=$storageService->orderSummary($orderData,$PayOrder,$order_no);
        $this->sendSuccessNoticeMethod($village_id,$orderData,$price,$charge_type_str,$property_id); //todo 支付成功 发送通知
        fdump_api(['扣款结果=='.__LINE__,$order_id,$uid,$price,$order_no,$PayOrder,$result],'lingyinmeter/userBalancePayMethod',1);
        return true;
    }
    //todo 支付成功 发送通知
    public function sendSuccessNoticeMethod($village_id,$order,$price,$charge_type='水费',$property_id=0){
        $houseVillageUserVacancyDb= new HouseVillageUserVacancy();
        $roomListObj = $houseVillageUserVacancyDb->getLists(['pigcms_id' => $order['room_id']]);
        if (!$order['pigcms_id'] && $order['order_id']) {
            $href = get_base_url('pages/houseMeter/NewCollectMoney/billDetails?order_id=' .$order['order_id'].'&type=2');
        } else {
            $href = get_base_url('pages/houseMeter/NewCollectMoney/billsPaid?pigcms_id='.$order['pigcms_id'].'&village_id='.$village_id);
        }
        $address='--';
        if ($roomListObj && !$roomListObj->isEmpty()) {
            $roomList = $roomListObj->toArray();
            $room = $roomList[0];
            $houseVillageService = new HouseVillageService();
            $address = $houseVillageService->word_replce_msg(array('single_name' => $room['single_name'], 'floor_name' => $room['floor_name'], 'layer' => $room['layer_name'], 'room' => $room['room']), $village_id);
        }
        $userDb=new User();
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
        $xtype=0;
        if($property_id>0){
            $xtype=1;
        }else{
            $property_id=0;
        }
        $templateNewsService= new TemplateNewsService();
        $templateNewsService->sendTempMsg($data['tempKey'], $data['dataArr'],0,$property_id,$xtype);
        fdump_api(['支付成功,发送通知=='.__LINE__,$village_id,$order,$price,$charge_type,$data],'lingyinmeter/sendSuccessNoticeMethod',1);
        return true;
    }
    /***
     *抄表后 多长时间内订单未支付断电 断水
     ****/
    public function handleLingYinMeterWaterElectricUse()
    {
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (empty($this->lingyin_meter_pfaccount) || empty($this->lingyin_meter_pfpwd)){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有配置聆音智能平台对接参数！';
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
        fdump_api([__LINE__, 'houseVillages' => $houseVillages], 'lingyinmeter/handleLingYinMeterWaterElectricUse', 1);
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
            $taskLogWhere[] = ['task_type', '=', 'handleLingYinMeterApi'];
            $tmplog = $houseVillageNewtaskLog->getOneData($taskLogWhere, '*');
            $dateTmp = date('Y-m') . '-' . $hvv['meter_time'] . ' 00:00:01';
            $dateTmpTime = strtotime($dateTmp);
            $newtime = strtotime("+" . $hvv['meter_nopayorder_date'] . " day", $dateTmpTime); //该操作断水电的时间
            $exec_day = $hvv['meter_nopayorder_date'] + $hvv['meter_time'];
            $current_day = date('j');
            $exedate = date('Ymd', $newtime);
            $exedate = intval($exedate);
            fdump_api([__LINE__, $dateTmp, $newtime, 'exedate' => $exedate, 'exec_day' => $exec_day, 'current_day' => $current_day, 'taskLogWhere' => $taskLogWhere, 'tmplog' => $tmplog], 'lingyinmeter/handlelingyinmeterWaterElectricUse', 1);
            if (!empty($tmplog) && $exec_day == $current_day) {
                //到时间执行 更新掉执行时间
                $houseVillageNewtaskLog->updateOneData(['id' => $tmplog['id']], ['execute_time' => $nowtime]);
            } elseif (!empty($tmplog) && $tmplog['execute_time'] > 10) {
                continue;
            } elseif (empty($tmplog)) {
                $tmplogarr = array();
                $tmplogarr['village_id'] = $hvv['village_id'];
                $tmplogarr['task_type'] = 'handleLingYinMeterApi';
                $tmplogarr['need_execute_time'] = $exedate;
                $tmplogarr['add_time'] = $nowtime;
                $houseVillageNewtaskLog->addOneData($tmplogarr);
                continue;
            } else {
                continue;
            }
            
            fdump_api([__LINE__, 'no_pay_order_time' => $no_pay_order_time], 'lingyinmeter/handleLingYinMeterWaterElectricUse', 1);
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
            $whereArr[] = ['order_type_flag', '=', 'lingyinmeter_order'];
            $whereArr[] = ['third_meter_code', '<>', ''];
            $fieldStr = 'order_id,uid,pigcms_id,name,phone,order_type,room_id,property_id,village_id,order_name,order_type_flag,extra_data,third_meter_code';
            $orders = $db_house_new_pay_order->getOrder($whereArr, $fieldStr, 'order_id ASC');
            $nowtime = time();
            if ($orders && !$orders->isEmpty()) {
                $orders = $orders->toArray();
                fdump_api([__LINE__, 'orders' => $orders], 'lingyinmeter/handleLingYinMeterWaterElectricUse', 1);
                foreach ($orders as $vv) {
                    $ret = $this->meterCloseUse($vv['third_meter_code'], $vv['room_id']);
                    if ($ret && is_array($ret) && isset($ret['err_code']) && $ret['err_code']==0) {
                        //操作成功了
                        $extra_data = !empty($vv['extra_data']) ? json_decode($vv['extra_data'], 1) : array();
                        $extra_data = is_array($extra_data) ? $extra_data : array();
                        $extra_data['lingyinmeter_meter'] = array('third_meter_code' => $vv['third_meter_code'], 'opt_time' => $nowtime, 'opt_type' => 'off');
                        $extra_data['lingyinmeter_meter']['closerMeter'] = $ret;
                        $saveArr = array('extra_data' => json_encode($extra_data, JSON_UNESCAPED_UNICODE));
                        $saveArr['third_opt_status'] = 1;
                        $db_house_new_pay_order->saveOne(['order_id' => $vv['order_id']], $saveArr);
                    }
                    usleep(500000); //延迟半秒
                }
            }
        }
        return true;
    }
    
    /***
     *通水通电
     ***/
    public function meterOpenUse($meter_code = '', $room_id = 0, $meter_type = 1)
    {
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (empty($this->lingyin_meter_pfaccount) || empty($this->lingyin_meter_pfpwd) || empty($this->lingyin_meter_base_url)){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '客户未配置对接信息！';
            return $returnArr;
        }
        $meterData = array();
        $meterTypeStr='water';
        if($meter_type==2){
            $meterTypeStr='ele';
        }
        $houseVillageRoomMeterInfoDb = new HouseVillageRoomMeterInfo();
        if (empty($meter_code) && $room_id > 0 && $meter_type > 0) {
            $whereArr = array(['room_id', '=', $room_id], ['meter_pf', '=', 'lingyinmeter'], ['is_del', '<', 1], ['meter_type', '=', $meter_type]);
            $roomMeterInfo = $houseVillageRoomMeterInfoDb->getOneData($whereArr);
            if ($roomMeterInfo && !$roomMeterInfo->isEmpty()) {
                $meterData = array('meterNum' => $roomMeterInfo['meter_code'], 'action' => 1, 'meterType' => $meterTypeStr);
            }
        } else if ($meter_code) {
            $meterData = array('meterNum' => $meter_code, 'action' => 1, 'meterType' => $meterTypeStr);
        }
        if (empty($meterData)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有表号数据！';
            return $returnArr;
        }
        $returnArr = $this->handleControlCmd($meterData);
        fdump_api(['_Line_' . __LINE__, 'openMeterUse', 'meterData' => $meterData, 'returnArr' => $returnArr], 'lingyinmeter/handleControlCmd' . date('H'), 1);
        return $returnArr;
    }
    
    /***
     *断水断电
     ***/
    public function meterCloseUse($meter_code = '', $room_id = 0, $meter_type = 1)
    {
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (empty($this->lingyin_meter_pfaccount) || empty($this->lingyin_meter_pfpwd) || empty($this->lingyin_meter_base_url)){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '客户未配置对接信息！';
            return $returnArr;
        }
        $meterData = array();
        $meterTypeStr='water';
        if($meter_type==2){
            $meterTypeStr='ele';
        }
        $houseVillageRoomMeterInfoDb = new HouseVillageRoomMeterInfo();
        if (empty($meter_code) && $room_id > 0 && $meter_type > 0) {
            $whereArr = array(['room_id', '=', $room_id], ['meter_pf', '=', 'lingyinmeter'], ['is_del', '<', 1], ['meter_type', '=', $meter_type]);
            $roomMeterInfo = $houseVillageRoomMeterInfoDb->getOneData($whereArr);
            if ($roomMeterInfo && !$roomMeterInfo->isEmpty()) {
                $meterData= array('meterNum' => $roomMeterInfo['meter_code'], 'action' => 0, 'meterType' => $meterTypeStr);
            }
        } else if ($meter_code) {
            $meterData = array('meterNum' => $meter_code, 'action' => 0, 'meterType' => $meterTypeStr);
        }
        if (empty($meterData)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有表号数据！';
            return $returnArr;
        }
        $returnArr = $this->handleControlCmd($meterData);
        fdump_api(['_Line_' . __LINE__, 'closeMeterUse', 'meterData' => $meterData, 'returnArr' => $returnArr], 'lingyinmeter/handleControlCmd' . date('H'), 1);
        return $returnArr;
    }
    
    public function getApiToken($is_cache=true){
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'token' => '');
        if (empty($this->lingyin_meter_pfaccount) || empty($this->lingyin_meter_pfpwd) || empty($this->lingyin_meter_base_url)){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '客户未配置对接信息！';
            return $returnArr;
        }
        $nowtime=time();
        $cacheTokenKey='village:cache:lingyin_meter_token:'.$this->lingyin_meter_pfaccount;
        $dataCache = Cache::get($cacheTokenKey);
        if($is_cache && is_array($dataCache) && isset($dataCache['token']) && $dataCache['expire_time']>$nowtime){
            $this->lingyin_token=$dataCache['token'];
            $returnArr['token']=$dataCache['token'];
            fdump_api(['_Line_' . __LINE__, 'returnArr' => $returnArr], 'lingyinmeter/getApiToken' . date('H'), 1);
            return $returnArr;
        }
        $dataArr=array('username'=>$this->lingyin_meter_pfaccount,'password'=>$this->lingyin_meter_pfpwd);
        $result = $this->commonApiRequest('getToken', $dataArr);
        if ($result && isset($result['param']) && !empty($result['param']) && isset($result['param']['token'])) {
            $this->lingyin_token=$result['param']['token'];  //有2小时（7200秒）后过期
            $returnArr['token']=$result['param']['token'];
            $expire_time=time()+6000;
            $tokenArr=array('token'=>$result['param']['token'],'expire_time'=>$expire_time);
            Cache::set($cacheTokenKey,$tokenArr,6000);
        }
        fdump_api(['_Line_' . __LINE__, 'dataArr' => $dataArr, 'result' => $result], 'lingyinmeter/getApiToken' . date('H'), 1);
        return $returnArr;
    }
    
    /***
     * $cmdData中每个数组字段
     *$meterNum 表号
     * $action 0 断闸  1开闸
     * $meterType water 水 ele电
     **/
    public function handleControlCmd($cmdData = array())
    {
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $timespan=time();
        $timespan=$timespan*1000;
        $dataArr = array('timespan' => $timespan, 'data' => $cmdData);
        $this->getApiToken();
        if(empty($this->lingyin_token)){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = 'Token获取失败！';
            return $returnArr;
        }
        $result = $this->commonApiRequest('valveControl', $dataArr);
        if ($result && isset($result['code']) && $result['code']==0) {
            $returnArr['err_code'] = 0;
        } else if ($result && isset($result['message'])) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = $result['message'];
        } else {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '请求出错了';
        }
        fdump_api(['_Line_' . __LINE__, 'dataArr' => $dataArr, 'result' => $result], 'lingyinmeter/handleControlCmd' . date('H'), 1);
        return $returnArr;
    }

    /**
     * 公用的组装请求.
     */
    public function commonApiRequest($methodUri = '', $params = array(), $method = 'post')
    {
        $requestApiUrl = $this->lingyin_meter_base_url . '/'.$methodUri;
        $headers = array();
        $headers[] = "Accept-Charset: utf-8";
        $headers[] = "Content-type: application/json;";
        $headers[] = 'Content-Encoding: utf-8';
        if($methodUri!='getToken'){
            $headers[] = 'X-Access-Token:'.$this->lingyin_token;
        }
        $requestJsonStr = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $result = http_request($requestApiUrl, $method, $requestJsonStr, $headers);
        $responseData = array();
        fdump_api(['_Line_' . __LINE__, 'params' => $params,'headers'=>$headers, 'requestJsonStr' => $requestJsonStr, 'requestApiUrl' => $requestApiUrl, 'result' => $result], 'lingyinmeter/commonApiRequest' . date('H'), 1);
        if ($result && $result['1']) {
            $responseData = json_decode($result['1'], true);
        }
        return $responseData;
    }


}