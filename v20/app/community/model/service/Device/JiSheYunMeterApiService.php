<?php

/***
 *** 集社云水电表
 * 文档 https://c3-openapi.apifox.cn/
 * 文档 https://c3-openapi.apifox.cn/api-198073180
 * 文档 https://docs.qq.com/doc/DR2xVbVZBSGdwcUZO
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
class JiSheYunMeterApiService
{
    use NbLotWaterElectricMeterTraits;
    public $village_id = 0;  //小区id
    protected $jisheyun_base_url = 'http://external.c.jisheyun.com/api/prepayment/action'; //集社云水电表
    protected $jisheyun_electric_authorize_code = '';
    protected $jisheyun_electric_project_id = '';
    protected $jisheyun_water_authorize_code = '';
    protected $jisheyun_water_project_id = '';
    protected $meter_type=0;  //0未知 1水表2电表 
    protected $water_user_id=1;  //抄表人id  76826
    protected $electric_user_id=1;  //抄表人id  76826
    public function __construct($village_id = 0)
    {
        $this->village_id = $village_id;
        $this->jisheyun_base_url = cfg('jisheyun_base_url');
        $this->jisheyun_base_url = $this->jisheyun_base_url  ? rtrim($this->jisheyun_base_url,'/'):'http://external.c.jisheyun.com/api/prepayment/action';
        
        $this->jisheyun_electric_authorize_code = cfg('jisheyun_electric_authorize_code');  //电表授权码
        $this->jisheyun_electric_authorize_code=$this->jisheyun_electric_authorize_code ? trim($this->jisheyun_electric_authorize_code):'';

        $this->jisheyun_electric_project_id = cfg('jisheyun_electric_project_id'); //电表项目id
        $this->jisheyun_electric_project_id=$this->jisheyun_electric_project_id ? trim($this->jisheyun_electric_project_id):'';

        $this->jisheyun_water_authorize_code = cfg('jisheyun_water_authorize_code');  //水表授权码
        $this->jisheyun_water_authorize_code=$this->jisheyun_water_authorize_code ? trim($this->jisheyun_water_authorize_code):'';

        $this->jisheyun_water_project_id = cfg('jisheyun_water_project_id'); //水表项目id
        $this->jisheyun_water_project_id=$this->jisheyun_water_project_id ? trim($this->jisheyun_water_project_id):'';
    }
    
    public function getEasyMeterConfig($village_id=0)
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
     * 获取表详细数据
     **/
    public function getMeterDataInfo($id = 0, $village_id = 0)
    {
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if ($id < 1 || $village_id < 1) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '参数错误！';
            return $returnArr;
        }
        fdump_api(['_Line_' . __LINE__, 'id' => $id, 'village_id' => $village_id], 'jisheyun/getMeterDataInfo' . date('H'), 1);
        $nowtime = time();
        $houseVillageRoomMeterInfoDb = new HouseVillageRoomMeterInfo();
        $whereArr = array(['id', '=', $id], ['meter_pf', '=', 'jisheyunmeter'], ['is_del', '<', 1], ['village_id', '=', $village_id]);
        $roomMeterInfo = $houseVillageRoomMeterInfoDb->getOneData($whereArr);
        if ($roomMeterInfo && !$roomMeterInfo->isEmpty()) {
            $pdata = array('pageIndex' => 0, 'pageSize' => 10, 'address' => $roomMeterInfo['meter_code']);
            $this->meter_type = $roomMeterInfo['meter_type'];
            $method = '';
            if ($roomMeterInfo['meter_type'] == 1) {
                //水表
                $pdata['projectId'] = $this->jisheyun_water_project_id;
                $method = 'watermeter.getlist';
            } else if ($roomMeterInfo['meter_type'] == 2) {
                //电表
                $pdata['projectId'] = $this->jisheyun_electric_project_id;
                $method = 'ammeter.getlist';
            }
            $paramArr = array('method' => $method, 'data' => $pdata);
            $returnArr = $this->handleCommonRequest($paramArr);
            $meterInfo = '';
            if (isset($returnArr['data']) && $returnArr['data'] && isset($returnArr['data']['DataLst'])) {
                $meterInfo = $returnArr['data']['DataLst'] ? $returnArr['data']['DataLst']['0'] : '';
            }
            if ($meterInfo) {
                $returnArr['data'] = $meterInfo;
                $extra_data_json = json_encode(array('mdetail' => $meterInfo), JSON_UNESCAPED_UNICODE);
                $saveArr['extra_data'] = $extra_data_json;
                if (isset($meterInfo['ID'])) {
                    $saveArr['third_meter_id'] = $meterInfo['ID'];
                } else if (isset($meterInfo['Id'])) {
                    $saveArr['third_meter_id'] = $meterInfo['Id'];
                }
                $saveArr['third_deal_time']=$nowtime;
                $houseVillageRoomMeterInfoDb->saveData(array('id' => $roomMeterInfo['id']), $saveArr);
            }
        }

        return $returnArr;
    }

    public function checkMeterDetail()
    {
        $houseVillageRoomMeterInfoDb = new HouseVillageRoomMeterInfo();
        $whereArr = array(['village_id', '>',0], ['is_del', '<', 1], ['meter_code', '<>', ''], ['meter_pf', '=', 'jisheyunmeter'], ['third_meter_id', '<', 1]);
        $field='id,village_id,meter_type,meter_code,third_meter_id';
        $roomMeterInfoObj = $houseVillageRoomMeterInfoDb->getListData($whereArr, $field, 'third_deal_time ASC', 1, 800);
        $roomMeterList = array();
        if ($roomMeterInfoObj && !$roomMeterInfoObj->isEmpty()) {
            $roomMeterList = $roomMeterInfoObj->toArray();
        }
        $nowtime = time();
        $hourKey = date('H');
        if (empty($roomMeterList)) {
            fdump_api([  __LINE__, 'whereArr' => $whereArr, 'msg' => '没有可处理的数据'], 'jisheyun/checkMeterDetail' . $hourKey, 1);
            return true;
        }
        foreach ($roomMeterList as $mvv){
            $pdata = array('pageIndex' => 0, 'pageSize' => 10, 'address' => $mvv['meter_code']);
            $this->meter_type = $mvv['meter_type'];
            $method = '';
            if ($mvv['meter_type'] == 1) {
                //水表
                $pdata['projectId'] = $this->jisheyun_water_project_id;
                $method = 'watermeter.getlist';
            } else if ($mvv['meter_type'] == 2) {
                //电表
                $pdata['projectId'] = $this->jisheyun_electric_project_id;
                $method = 'ammeter.getlist';
            }
            $paramArr = array('method' => $method, 'data' => $pdata);
            $returnArr = $this->handleCommonRequest($paramArr);
            $meterInfo = '';
            if (isset($returnArr['data']) && $returnArr['data'] && isset($returnArr['data']['DataLst'])) {
                $meterInfo = $returnArr['data']['DataLst'] ? $returnArr['data']['DataLst']['0'] : '';
            }
            $saveArr=array();
            if ($meterInfo) {
                $returnArr['data'] = $meterInfo;
                $extra_data_json = json_encode(array('mdetail' => $meterInfo), JSON_UNESCAPED_UNICODE);
                $saveArr['extra_data'] = $extra_data_json;
                if (isset($meterInfo['ID'])) {
                    $saveArr['third_meter_id'] = $meterInfo['ID'];
                } else if (isset($meterInfo['Id'])) {
                    $saveArr['third_meter_id'] = $meterInfo['Id'];
                }
            }
            $saveArr['third_deal_time']=$nowtime;
            $houseVillageRoomMeterInfoDb->saveData(array('id' => $mvv['id']), $saveArr);
            sleep(2);
        }
        return true;
    }

    /***
     *生成订单
     ****/
    
    public function handleWaterElectricOrder(){
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (empty($this->jisheyun_electric_authorize_code) && empty($this->jisheyun_water_authorize_code)){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有配置集社系统水电表对接参数！';
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
               $whereArr=array(['village_id','=',$hvv['village_id']],['is_del', '<', 1],['meter_code', '<>', ''],['meter_pf', '=', 'jisheyunmeter']);
               $roomMeterInfo = $houseVillageRoomMeterInfoDb->getOneData($whereArr,'*','recent_order_time desc');
               if($roomMeterInfo && !$roomMeterInfo->isEmpty() && $roomMeterInfo['recent_order_time']<$today_date){
                   //有数据 需要生成账单
                   $queueData=array('meter_pf'=>'jisheyunmeter','village_id'=>$hvv['village_id'],'property_id'=>$hvv['property_id'],'opt_type'=>'meter_village_reading','meter_order_time'=>$meter_time,'meter_order_date'=>$today_date);
                   $this->nbLotWaterElectricMeterQueuePushToJob($queueData);
               }
           }
        }
        return $returnArr;
    }

    //todo 查询对应科目的收费标准
    public function getChargingTypeMethod($charge_type, $vacancy_id = 0)
    {
        $where = [];
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
        $whereArr = array(['village_id', '=', $data['village_id']], ['is_del', '<', 1], ['meter_code', '<>', ''], ['meter_pf', '=', 'jisheyunmeter']);
        $whereArr[] = array('recent_order_time', '<', $meter_order_date);
        $whereArr[] = array('third_meter_id', '>', 0);
        $field = 'id,village_id,room_id,meter_pf,meter_type,meter_code,last_num,recent_num,recent_time,recent_order_time,recent_order_id,recent_meter_reading_id';
        $roomMeterInfoObj = $houseVillageRoomMeterInfoDb->getListData($whereArr, $field, 'id DESC', 0, 1000);
        $roomMeterList = array();
        if ($roomMeterInfoObj && !$roomMeterInfoObj->isEmpty()) {
            $roomMeterList = $roomMeterInfoObj->toArray();
        }
        if (empty($roomMeterList)) {
            fdump_api(['没有抄表房间数据==' . __LINE__, 'whereArr' => $whereArr, 'data' => $data], 'jisheyun/meter_village_reading' . $hourKey, 1);
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
                fdump_api(['房间不存在跳过==' . __LINE__, 'item' => $item], 'jisheyun/meter_village_reading' . $hourKey, 1);
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
                fdump_api(['收费规则不存在==' . __LINE__, 'item' => $item, 'charge_type' => $charge_type], 'jisheyun/meter_village_reading' . $hourKey, 1);
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
            $queueData = array('meter_pf' => 'jisheyunmeter', 'village_id' => $village_id, 'property_id' => $property_id, 'opt_type' => 'meter_create_order', 'meter_order_date' => $meter_order_date);
            $queueData['rule_digit'] = $rule_digit;
            $queueData['digit_type'] = $digit_type;
            $queueData['rule'] = $rule;
            $queueData['user_bind'] = $user_bind;
            $queueData['idid'] = $item['id'];
            $ret=$this->nbLotWaterElectricMeterLaterQueuePushToJob($queueData, 2);
            sleep(2);
        }
        return true;
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
            fdump_api(['抄表数据错误=1=' . __LINE__, 'data' => $data], 'jisheyun/meter_create_order' . $hourKey, 1);
            return true;
        }
        
        $houseVillageRoomMeterInfoDb = new HouseVillageRoomMeterInfo();
        $houseNewMeterService = new HouseNewMeterService();
        $nowtime = time();
        $whereArr = array(['id', '=', $idid], ['meter_pf', '=', 'jisheyunmeter'], ['is_del', '<', 1], ['village_id', '=', $village_id]);
        $whereArr[] = array('recent_order_time', '=', $meter_order_date);
        $whereArr[] = array('recent_time', '=', $meter_order_date);
        $roomMeterInfo = $houseVillageRoomMeterInfoDb->getOneData($whereArr);
        if (empty($roomMeterInfo) || $roomMeterInfo->isEmpty()) {
            fdump_api(['抄表数据错误=2=' . __LINE__, 'whereArr' => $whereArr], 'jisheyun/meter_create_order' . $hourKey, 1);
            return true;
        }
        $roomMeterInfo = $roomMeterInfo->toArray();
        //$pdata = array('UserId' => 1, 'MeterId' => $roomMeterInfo['third_meter_id']);
        $startTime=time()-30*86400;
        $startDate=date('Y-m-d',$startTime);
        $endDate=date('Y-m-d');
        $pdata = array('PageIndex' => 0, 'PageSize' => 2, 'MeterAddress' => $roomMeterInfo['meter_code'],'StartDate'=>$startDate,'EndDate'=>$endDate,'RoomIds'=>'','RoomName'=>'');
        $this->meter_type = $roomMeterInfo['meter_type'];
        $charge_type = '';
        $retReading=array();
        $recent_num = 0;
        $reading_arr = '';
        if ($roomMeterInfo['meter_type'] == 1) {
            //水表
            $pdata['projectId'] = $this->jisheyun_water_project_id;
            $charge_type = 'water';
            $waterFrozendata= $this->getWaterMeterFrozendata($pdata,1);
            if (isset($waterFrozendata['data']) && $waterFrozendata['data'] && isset($waterFrozendata['data']['DataLst'])) {
                $retReading = $waterFrozendata['data']['DataLst'] ? $waterFrozendata['data']['DataLst']['0'] : '';
            }
            if ($retReading && isset($retReading['TotalKwh']) && !empty($retReading['TotalKwh'])) {
                $reading_arr = $retReading;
                $recent_num = $reading_arr['TotalKwh'];
            }
        } else if ($roomMeterInfo['meter_type'] == 2) {
            //电表
            $pdata['projectId'] = $this->jisheyun_electric_project_id;
            $charge_type = 'electric';
            $electricFrozendata=$this->getElectricMeterFrozendata($pdata,2);
            if (isset($electricFrozendata['data']) && $electricFrozendata['data'] && isset($electricFrozendata['data']['DataLst'])) {
                $retReading = $electricFrozendata['data']['DataLst'] ? $electricFrozendata['data']['DataLst']['0'] : '';
            }
            if ($retReading && isset($retReading['TotalKwh']) && !empty($retReading['TotalKwh'])) {
                $reading_arr = $retReading;
                $recent_num = $reading_arr['TotalKwh'];
            }
        }
        if ($recent_num<=0 || ($recent_num <= $roomMeterInfo['recent_num'])) {
            fdump_api(['表读数错误=3=' . __LINE__, 'roomMeterInfo' => $roomMeterInfo,'retReading'=>$retReading], 'jisheyun/meter_create_order' . $hourKey, 1);
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

        $orderData['order_type_flag'] = 'jisheyunmeter_order';
        $orderData['third_meter_code'] = $roomMeterInfo['meter_code'];
        $orderData['third_meter_id'] = $roomMeterInfo['third_meter_id'];
        $db_house_new_pay_order = new HouseNewPayOrder();
        $order_id = $db_house_new_pay_order->addOne($orderData);

        $saveArr['recent_order_id'] = $order_id;
        $houseVillageRoomMeterInfoDb->saveData(array('id' => $roomMeterInfo['id']), $saveArr);
        fdump_api(['抄表成功' . __LINE__, 'saveArr' => $saveArr,'user_bind'=>$user_bind], 'jisheyun/meter_create_order' . $hourKey, 1);
        if ($user_bind && isset($user_bind['uid']) && $user_bind['uid'] > 0) {
            //扣除余缴费额操作
            $orderData['order_id'] = $order_id;
            $this->userBalancePayMethod($order_id, $orderData);
        }
        return true;
    }
    //todo 抵扣余额，支付成功
    public function userBalancePayMethod($order_id,$orderData=array()){
        fdump_api(['参数进来了=='.__LINE__,$order_id,$orderData],'jisheyun/userBalancePayMethod',1);
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
        fdump_api(['住户余额=='.__LINE__,'now_village_user'=>$now_village_user,'uid'=>$uid,'price'=>$price],'jisheyun/userBalancePayMethod',1);
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
        $desc='水费预缴，扣除余额，订单编号X1';
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
            fdump_api(['住户余额=='.__LINE__,$village_money_data,$useresult],'jisheyun/userBalancePayMethod',1);
            if ($useresult['error_code']) {
                $datetime=date('m月d日H时');
                $storageService->sendMessage($village_id, $order_id,$property_id);//todo 余额扣款失败 发送通知
                return true;
            }
        } else {
            $userService=new UserService();
            $useresult = $userService->userMoney($uid, $price, L_($desc, array("X1" => $order_no)));
            $system_balance=$price;
            fdump_api(['平台余额=='.__LINE__,$uid,$price,$useresult],'jisheyun/userBalancePayMethod',1);
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
        fdump_api(['扣款结果=='.__LINE__,$order_id,$uid,$price,$order_no,$PayOrder,$result],'jisheyun/userBalancePayMethod',1);
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
        fdump_api(['支付成功,发送通知=='.__LINE__,$village_id,$order,$price,$charge_type,$data],'jisheyun/sendSuccessNoticeMethod',1);
        return true;
    }
    /***
     *抄表后 多长时间内订单未支付断电 断水
     ****/
    public function handleMeterWaterElectricUse()
    {
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (empty($this->jisheyun_electric_authorize_code) && empty($this->jisheyun_water_authorize_code)){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有配置集社系统水电表对接参数！';
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
        fdump_api([__LINE__, 'houseVillages' => $houseVillages], 'jisheyun/handleMeterWaterElectricUse', 1);
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
            $taskLogWhere[] = ['task_type', '=', 'handleJiSheYunMeterApi'];
            $tmplog = $houseVillageNewtaskLog->getOneData($taskLogWhere, '*');
            $dateTmp = date('Y-m') . '-' . $hvv['meter_time'] . ' 00:00:01';
            $dateTmpTime = strtotime($dateTmp);
            $newtime = strtotime("+" . $hvv['meter_nopayorder_date'] . " day", $dateTmpTime); //该操作断水电的时间
            $exec_day = $hvv['meter_nopayorder_date'] + $hvv['meter_time'];
            $current_day = date('j');
            $exedate = date('Ymd', $newtime);
            $exedate = intval($exedate);
            fdump_api([__LINE__, $dateTmp, $newtime, 'exedate' => $exedate, 'exec_day' => $exec_day, 'current_day' => $current_day, 'taskLogWhere' => $taskLogWhere, 'tmplog' => $tmplog], 'jisheyun/handleMeterWaterElectricUse', 1);
            if (!empty($tmplog) && $exec_day == $current_day) {
                //到时间执行 更新掉执行时间
                $houseVillageNewtaskLog->updateOneData(['id' => $tmplog['id']], ['execute_time' => $nowtime]);
            } elseif (!empty($tmplog) && $tmplog['execute_time'] > 10) {
                continue;
            } elseif (empty($tmplog)) {
                $tmplogarr = array();
                $tmplogarr['village_id'] = $hvv['village_id'];
                $tmplogarr['task_type'] = 'handleJiSheYunMeterApi';
                $tmplogarr['need_execute_time'] = $exedate;
                $tmplogarr['add_time'] = $nowtime;
                $houseVillageNewtaskLog->addOneData($tmplogarr);
                continue;
            } else {
                continue;
            }
            
            fdump_api([__LINE__, 'no_pay_order_time' => $no_pay_order_time], 'jisheyun/handleMeterWaterElectricUse', 1);
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
            $whereArr[] = ['order_type_flag', '=', 'jisheyunmeter_order'];
            $whereArr[] = ['third_meter_code', '<>', ''];
            $fieldStr = 'order_id,uid,pigcms_id,name,phone,order_type,room_id,property_id,village_id,order_name,order_type_flag,extra_data,third_meter_code,third_meter_id';
            $orders = $db_house_new_pay_order->getOrder($whereArr, $fieldStr, 'order_id ASC');
            $nowtime = time();
            if ($orders && !$orders->isEmpty()) {
                $orders = $orders->toArray();
                fdump_api([__LINE__, 'orders' => $orders], 'jisheyun/handleMeterWaterElectricUse', 1);
                foreach ($orders as $vv) {
                    $meter_type=1;
                    if($vv['order_type']=='electric'){
                        $meter_type=2;
                    }
                    $ret = $this->meterCloseUse($vv['third_meter_id'],$meter_type,$vv['third_meter_code'], $vv['room_id']);
                    if ($ret && is_array($ret) && isset($ret['data']) && $ret['data']) {
                        //操作成功了
                        $extra_data = !empty($vv['extra_data']) ? json_decode($vv['extra_data'], 1) : array();
                        $extra_data = is_array($extra_data) ? $extra_data : array();
                        $extra_data['jisheyun_meter'] = array('third_meter_id' => $vv['third_meter_id'],'third_meter_code' => $vv['third_meter_code'], 'opt_time' => $nowtime, 'opt_type' => 'off');
                        $extra_data['jisheyun_meter']['closerMeter'] = $ret['data'];
                        $saveArr = array('extra_data' => json_encode($extra_data, JSON_UNESCAPED_UNICODE));
                        $saveArr['third_opt_status'] = 1;
                        $db_house_new_pay_order->saveOne(['order_id' => $vv['order_id']], $saveArr);
                    }
                    sleep(2); //延迟2秒
                }
            }
        }
        return true;
    }
    
    /***
     *抄表查询 表读数
     *这个接口 第三方技术说 NB水表是不支持在线抄读的
     * 有线485采集数据的  支持
     ***/
    public function getMeterReadingNum($pdata,$meter_type=0)
    {
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $this->meter_type= $meter_type;
        if(!in_array($this->meter_type,array(1,2))){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '水电表类型错误！';
            return $returnArr;
        }
        if($this->meter_type==1 && (empty($this->jisheyun_water_authorize_code) || empty($this->jisheyun_water_project_id))){
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = '水表对接未配置好！';
                return $returnArr;
        }else if($this->meter_type==2 && (empty($this->jisheyun_electric_authorize_code) || empty($this->jisheyun_electric_project_id))){
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = '电表对接未配置好！';
                return $returnArr;
        }
        $paramArr = array('method' => 'metercontrol.copy', 'data' => $pdata);
        $returnArr=$this->handleCommonRequest($paramArr);
        return $returnArr;
    }

    /***
     *查询水表冻结数据 取最新的一条就是最近一次抄表数据
     ***/
    public function getWaterMeterFrozendata($pdata,$meter_type=0)
    {
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $this->meter_type= $meter_type;
        if(!in_array($this->meter_type,array(1))){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '水表类型错误！';
            return $returnArr;
        }
        if(empty($this->jisheyun_water_authorize_code) || empty($this->jisheyun_water_project_id)){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '水表对接未配置好！';
            return $returnArr;
        }
        $paramArr = array('method' => 'watermeter.getlist.frozendata', 'data' => $pdata);
        $returnArr=$this->handleCommonRequest($paramArr);
        return $returnArr;
    }

    /***
     *查询电表冻结数据 取最新的一条就是最近一次抄表数据
     ***/
    public function getElectricMeterFrozendata($pdata,$meter_type=0)
    {
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $this->meter_type= $meter_type;
        if(!in_array($this->meter_type,array(2))){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '电表类型错误！';
            return $returnArr;
        }
        if(empty($this->jisheyun_electric_authorize_code) || empty($this->jisheyun_electric_project_id)){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '电表对接未配置好！';
            return $returnArr;
        }
        $paramArr = array('method' => 'ammeter.getlist.frozendata', 'data' => $pdata);
        $returnArr=$this->handleCommonRequest($paramArr);
        return $returnArr;
    }
    
    /***
     *通水通电
     ***/
    public function meterOpenUse($third_meter_id=0, $meter_type = 0,$meter_code = '', $room_id = 0)
    {
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $this->meter_type=$meter_type;
        if(!in_array($this->meter_type,array(1,2))){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '水电表类型错误！';
            return $returnArr;
        }
        $meterData = array();
        if($this->meter_type==1 && (empty($this->jisheyun_water_authorize_code) || empty($this->jisheyun_water_project_id))){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '水表对接未配置好！';
            return $returnArr;
        }else if($this->meter_type==2 && (empty($this->jisheyun_electric_authorize_code) || empty($this->jisheyun_electric_project_id))){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '电表对接未配置好！';
            return $returnArr;
        }
        $houseVillageRoomMeterInfoDb = new HouseVillageRoomMeterInfo();
        if (empty($third_meter_id)  &&  $room_id > 0 && $meter_type > 0) {
            $whereArr = array(['room_id', '=', $room_id], ['meter_pf', '=', 'jisheyunmeter'], ['is_del', '<', 1], ['meter_type', '=', $meter_type]);
            if(!empty($meter_code)){
                $whereArr[]=array('meter_code','=',$meter_code);
            }
            $roomMeterInfo = $houseVillageRoomMeterInfoDb->getOneData($whereArr);
            if ($roomMeterInfo && !$roomMeterInfo->isEmpty() && $roomMeterInfo['third_meter_id']) {
                $third_meter_id=intval($roomMeterInfo['third_meter_id']);
                $meterData['MeterIds'] = array($third_meter_id);
            }
        } else if (is_array($third_meter_id)) {
            $meterData['MeterIds']=array();
            foreach ($third_meter_id as $meter_id) {
                $meterData['MeterIds'][] = intval($meter_id);
            }
        } else if ($third_meter_id) {
            $meterData['MeterIds']=array();
            $meterData['MeterIds'][] = intval($third_meter_id);
        }
        if (empty($meterData) || empty($meterData['MeterIds'])) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有表号数据！';
            return $returnArr;
        }
        if($this->meter_type==1){
            //水表
            $meterData['UserId']=$this->water_user_id;
            $meterData['State']=true;
            $meterData['powerState']=1;
            $meterData['ProjectId'] = $this->jisheyun_water_project_id;
            $meterData['Type'] = 'S';
        }else if($this->meter_type==2 ){
            //电表
            $meterData['UserId']=$this->electric_user_id;
            $meterData['State']=true;
            $meterData['powerState']=1;
            $meterData['ProjectId'] = $this->jisheyun_electric_project_id;
            $meterData['Type'] = 'D';
        }
        $paramArr = array('method' => 'metercontrol.jionbrake', 'data' => $meterData);
        $returnArr=$this->handleCommonRequest($paramArr);
        fdump_api(['_Line_' . __LINE__, 'openMeterUse', 'paramArr' => $paramArr, 'returnArr' => $returnArr], 'jisheyun/meterOpenUse' . date('H'), 1);
        return $returnArr;
    }
    
    /***
     *断水断电
     ***/
    public function meterCloseUse($third_meter_id=0, $meter_type = 0,$meter_code = '', $room_id = 0)
    {
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $this->meter_type=$meter_type;
        if(!in_array($this->meter_type,array(1,2))){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '水电表类型错误！';
            return $returnArr;
        }
        $meterData = array();
        if($this->meter_type==1 && (empty($this->jisheyun_water_authorize_code) || empty($this->jisheyun_water_project_id))){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '水表对接未配置好！';
            return $returnArr;
        }else if($this->meter_type==2 && (empty($this->jisheyun_electric_authorize_code) || empty($this->jisheyun_electric_project_id))){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '电表对接未配置好！';
            return $returnArr;
        }
        $houseVillageRoomMeterInfoDb = new HouseVillageRoomMeterInfo();
        if (empty($third_meter_id)  &&  $room_id > 0 && $meter_type > 0) {
            $whereArr = array(['room_id', '=', $room_id], ['meter_pf', '=', 'jisheyunmeter'], ['is_del', '<', 1], ['meter_type', '=', $meter_type]);
            if(!empty($meter_code)){
                $whereArr[]=array('meter_code','=',$meter_code);
            }
            $roomMeterInfo = $houseVillageRoomMeterInfoDb->getOneData($whereArr);
            if ($roomMeterInfo && !$roomMeterInfo->isEmpty() && $roomMeterInfo['third_meter_id']) {
                $third_meter_id=intval($roomMeterInfo['third_meter_id']);
                $meterData['MeterIds'] = array($third_meter_id);
            }
        } else if (is_array($third_meter_id)) {
            $meterData['MeterIds']=array();
            foreach ($third_meter_id as $meter_id) {
                $meterData['MeterIds'][] = intval($meter_id);
            }
        } else if ($third_meter_id) {
            $meterData['MeterIds']=array();
            $meterData['MeterIds'][] = intval($third_meter_id);
        }
        if (empty($meterData) || empty($meterData['MeterIds'])) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有表号数据！';
            return $returnArr;
        }
        if($this->meter_type==1){
            //水表
            $meterData['UserId']=$this->water_user_id;
            $meterData['State']=false;
            $meterData['powerState']=1;
            $meterData['ProjectId'] = $this->jisheyun_water_project_id;
            $meterData['Type'] = 'S';
        }else if($this->meter_type==2 ){
            //电表
            $meterData['UserId']=$this->electric_user_id;
            $meterData['State']=false;
            $meterData['powerState']=1;
            $meterData['ProjectId'] = $this->jisheyun_electric_project_id;
            $meterData['Type'] = 'D';
        }
        $paramArr = array('method' => 'metercontrol.jionbrake', 'data' => $meterData);
        $returnArr=$this->handleCommonRequest($paramArr);
        fdump_api(['_Line_' . __LINE__, 'meterCloseUse', 'paramArr' => $paramArr, 'returnArr' => $returnArr], 'jisheyun/meterCloseUse' . date('H'), 1);
        return $returnArr;
    }
    
    /***
     * $cmdData中每个数组字段
     *$meterCode 表号
     * $cmdStr 0 断闸  1开闸
     * $operatorName 操作员名称
     **/
    public function handleCommonRequest($dataArr = array())
    {
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if(!in_array($this->meter_type,array(1,2))){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '水电表类型错误！';
            return $returnArr;
        }
        
        $headers = array();
        $headers[] = "Content-Type:application/json";
        if($this->meter_type==1){
            $headers[] = 'authorize_code:'.$this->jisheyun_water_authorize_code;
            $headers[] = 'project_id:'.$this->jisheyun_water_project_id;
            if(empty($this->jisheyun_water_authorize_code) || empty($this->jisheyun_water_project_id)){
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = '水表对接未配置好！';
                return $returnArr;
            }
        }else if($this->meter_type==2){
            $headers[] = 'authorize_code:'.$this->jisheyun_electric_authorize_code;
            $headers[] = 'project_id:'.$this->jisheyun_electric_project_id;
            if(empty($this->jisheyun_electric_authorize_code) || empty($this->jisheyun_electric_project_id)){
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = '电表对接未配置好！';
                return $returnArr;
            }
        }
        $result = $this->commonApiRequest($dataArr,$headers);
        if ($result && isset($result['data']) && $result['data']) {
            $returnArr['err_code'] = 0;
            $returnArr['data'] = $result['data'];
        } else if ($result && isset($result['msg'])) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = $result['msg'];
        } else {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '请求出错了';
        }
        fdump_api(['_Line_' . __LINE__, 'dataArr' => $dataArr,'headers'=>$headers,'result' => $result], 'jisheyun/handleCommonRequest' . date('H'), 1);
        return $returnArr;
    }

    /**
     * 公用的组装请求.
     */
    public function commonApiRequest( $params = array(),$headers=array(), $method = 'post')
    {
        $requestJsonStr = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $requestApiUrl= $this->jisheyun_base_url;
        $result = $this->http_curl_request($requestApiUrl, $method, $requestJsonStr, $headers);
        $responseData = array();
        fdump_api(['_Line_' . __LINE__, 'params' => $params, 'requestJsonStr' => $requestJsonStr, 'requestApiUrl' => $requestApiUrl,'headers'=>$headers, 'result' => $result], 'jisheyun/commonApiRequest' . date('H'), 1);
        if ($result && $result['1']) {
            $responseData = json_decode($result['1'], true);
        }
        return $responseData;
    }

    public function http_curl_request($url, $method = 'GET', $postfields = null, $headers = array()) {
        $method = strtoupper($method);
        $ci = curl_init();
        /* Curl settings */
        curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
        curl_setopt($ci, CURLOPT_TIMEOUT, 100); /* 设置cURL允许执行的最长秒数 */
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        switch ($method) {
            case "POST":
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'POST');
                if (!empty($postfields)) {
                    $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
                }
                break;
            default:
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
                break;
        }
        $ssl = preg_match('/^https:\/\//i', $url) ? TRUE : FALSE;
        curl_setopt($ci, CURLOPT_URL, $url);
        if ($ssl) {
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
        }
        //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
        curl_setopt($ci, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ci, CURLOPT_MAXREDIRS, 10); /* 指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的 */
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        /* curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */
        $response = curl_exec($ci);
        $requestinfo = curl_getinfo($ci);
        $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        curl_close($ci);
        return array($http_code, $response, $requestinfo);
    }
}