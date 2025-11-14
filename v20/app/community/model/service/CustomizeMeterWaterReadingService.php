<?php
/**
 * @author : liukezhu
 * @date : 2022/7/20
 */

namespace app\community\model\service;

use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\HouseNewChargeStandardBind;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageInfo;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\HouseVillageVacancyNum;
use app\community\model\db\HouseVillageVacancyWaterType;
use app\community\model\db\User;
use app\community\model\service\StorageService;
use net\Http;
use think\facade\Cache;
use app\community\model\db\ProcessSubPlan;
use app\common\model\service\UserService;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseNewPayOrderSummary;
use app\common\model\service\send_message\SmsService;
use app\traits\VillageUserWaterElectricToSmsTraits;
use app\community\model\db\HouseVillageNewtaskLog;
class CustomizeMeterWaterReadingService
{
    use VillageUserWaterElectricToSmsTraits;
    protected $time;
    protected $meterEleReadingUrl='http://api.tw-iot.cn/ps_api/directive/read/';
    protected $meterEleOpeningUrl='http://api.tw-iot.cn/ps_api/directive/open/'; //通电
    protected $meterEleCloseingUrl='http://api.tw-iot.cn/ps_api/directive/close/'; //关电
    protected $meterEleReadingKey='APP_68cd90f0ac';
    protected $meterEleReadingScret='878508931632421db6fb317aa6369aaa';
    protected $meterWaterReadingUrl='http://114.115.162.140:8099/water/Interface/data/GetRangeMeterIndex.jsp';
    protected $meterWaterPaymentInfoUrl="http://114.115.162.140:8099/water/Interface/data/PaymentInfo.jsp"; //断水 通水
    protected $meterWaterTureUrl='http://114.115.162.140:8099/water/Interface/data/GetWatermeter.jsp';

    protected $HouseVillageVacancyNum;
    protected $HouseVillageUserBind;
    protected $HouseVillageUserVacancy;
    protected $HouseNewChargeStandardBind;
    protected $HouseNewMeterService;
    protected $ProcessSubPlan;
    protected $HouseVillage;
    protected $HouseNewCashierService;
    protected $UserService;
    protected $HouseNewPayOrder;
    protected $StorageService;
    protected $TemplateNewsService;
    protected $User;
    protected $HouseVillageService;
    protected $HouseVillageVacancyWaterType;

    public function __construct()
    {
        $this->time=time();
        $this->HouseVillageVacancyNum=new HouseVillageVacancyNum();
        $this->HouseVillageUserBind=new HouseVillageUserBind();
        $this->HouseVillageUserVacancy=new HouseVillageUserVacancy();
        $this->HouseNewChargeStandardBind=new HouseNewChargeStandardBind();
        $this->HouseNewMeterService=new HouseNewMeterService();
        $this->ProcessSubPlan=new ProcessSubPlan();
        $this->HouseVillage=new HouseVillage();
        $this->HouseNewCashierService = new HouseNewCashierService();
        $this->UserService = new UserService();
        $this->HouseNewPayOrder = new HouseNewPayOrder();
        $this->StorageService = new StorageService();
        $this->TemplateNewsService=new TemplateNewsService();
        $this->User =new User();
        $this->HouseVillageService=new HouseVillageService();
        $this->HouseVillageVacancyWaterType=new HouseVillageVacancyWaterType();
    }


    //todo [计划任务]请求电表参数
    public function getMeterEleReading(){
        if(!(int)cfg('customized_meter_reading')){
            return true;
        }
        $where=[
            ['a.ele_number','<>', '']
        ];
        $list=$vacancy_num=$this->HouseVillageVacancyNum->getVillageEle($where,'a.vacancy_id,b.meter_type,b.meter_time,b.property_id,a.ele_number');
        $dayKey = date('H');
        fdump_api(['参数进来了=='.__LINE__,((!$list || $list->isEmpty()) ? [] : $list->toArray())],'customize_water/getMeterEleReading_data'.$dayKey,1);
        if (!$list || $list->isEmpty()){
            return true;
        }
        $list=$list->toArray();
        $charge_type='electric';
        $mark_str="QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm";
        foreach ($list as $v){
            if($v['meter_type'] == 1){ //每月
                if($v['meter_time'] != date('d')){
                    fdump_api(['不符合每月跳过=='.__LINE__, 'v' => $v],'customize_water/getMeterEleReading_data'.$dayKey,1);
                    continue;
                }
            }
            if($v['meter_type'] == 2){ //每日
                if($v['meter_time'] != date('H')){
                    fdump_api(['不符合每日跳过=='.__LINE__, 'v' => $v],'customize_water/getMeterEleReading_data'.$dayKey,1);
                    continue;
                }
            }
            $user_bind=$this->HouseVillageUserBind->getOne([
                ['vacancy_id','=',$v['vacancy_id']],
                ['status', '=', 1],
                ['type','in',[0,3]]
            ],'pigcms_id,uid,village_id,usernum,name,phone,single_id,floor_id,layer_id,vacancy_id');
            if(empty($user_bind)){
                fdump_api(['房间没有业主跳过=='.__LINE__, 'v' => $v],'customize_water/getMeterEleReading_data'.$dayKey,1);
                continue;
            }
            $user_bind=$user_bind->toArray();
            $village_room=$this->HouseVillageUserVacancy->getOne([
                ['pigcms_id','=',$v['vacancy_id']],
                ['is_del','=',0],
            ],'floor_id,name');
            if (!$village_room || $village_room->isEmpty()){
                fdump_api(['房屋信息有误跳过=='.__LINE__, 'v' => $v],'customize_water/getMeterEleReading_data'.$dayKey,1);
                continue;
            }
            $rule=$this->getChargingTypeMethod($charge_type,$user_bind);
            if(!$rule){
                fdump_api(['缺少标准跳过=='.__LINE__, 'charge_type' => $charge_type, 'user_bind' => $user_bind, 'v' => $v],'customize_water/getMeterEleReading_data'.$dayKey,1);
                continue;
            }
            $rand=substr(str_shuffle($mark_str),mt_rand(0,strlen($mark_str)-11),4);
            $url=$this->meterEleReadingUrl.$v['ele_number'];
            $json =json_encode(["businessNo"=>$this->time.$rand],true);
            $a = strtoupper($this->encodeHexStringMethod($this->md5HexMethod($json)));
            $sb = $url.":POST:$a:$this->time:".$this->meterEleReadingScret;
            $b = strtoupper($this->encodeHexStringMethod($this->md5HexMethod($sb)));
            $sign = $this->meterEleReadingKey.":".$this->time.":".$b;
            fdump_api(['请求前参数=='.__LINE__, 'url' => $url, 'json' => $json, 'v' => $v],'customize_water/getMeterEleReading_data'.$dayKey,1);
            $output = $this->jsonPostMethod($url,$json,array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length:' . strlen($json),
                'Cache-Control: no-cache',
                'Pragma: no-cache',
                'sign:'.$sign
            ));
            fdump_api(['记录返回数据=='.__LINE__, 'url' => $url, 'json' => $json,'output' => $output, 'v' => $v],'customize_water/getMeterEleReading_data'.$dayKey,1);
        }
        return true;
    }

    //todo [计划任务]电表参数回调
    public function getMeterEleCallback($param){
        $dayKey = date('H');
        fdump_api(['参数进来了=='.__LINE__,$param],'customize_water/getMeterEleCallback_data'.$dayKey,1);
        if(!(int)cfg('customized_meter_reading')){
            return true;
        }
        if(!isset($param['data'])|| !is_array($param['data']) || !isset($param['data']['totalActiveE']) || empty($param['dataMarker']) || !in_array($param['dataMarker'],array('00000000','04601001'))){
            fdump_api(['参数不合法=='.__LINE__,$param],'customize_water/getMeterEleCallback_data'.$dayKey,1);
            return true;
        }
        if(!isset($param['businessNo']) || empty($param['businessNo'])){
            fdump_api(['缺少businessNo字段=='.__LINE__],'customize_water/getMeterEleCallback_data'.$dayKey,1);
            return true;
        }
        $charge_type='electric';
        $equipNumber = $param['equipNumber'];
        $vacancy_num=$this->HouseVillageVacancyNum->getVillageFind([
            ['a.ele_number','=',$equipNumber],
        ],'a.vacancy_id,b.meter_type,b.meter_time,d.meter_digit,d.type,b.property_id,a.ele_meter_date,a.id');
        if (!$vacancy_num || $vacancy_num->isEmpty()){
            fdump_api(['对应房间不存在=='.__LINE__,['equipNumber' => $equipNumber, 'charge_type' => $charge_type]],'customize_water/getMeterEleCallback_data'.$dayKey,1);
            return true;
        }
        $vacancy_num=$vacancy_num->toArray();
        fdump_api(['房间关联表信息=='.__LINE__,'vacancy_num' => $vacancy_num],'customize_water/getMeterEleCallback_data'.$dayKey,1);
        if($vacancy_num['meter_type'] == 1){ //每月
            if($vacancy_num['meter_time'] != date('d')){
                fdump_api(['月份不对跳过=='.__LINE__],'customize_water/getMeterEleCallback_data'.$dayKey,1);
                return true;
            }
        }
        if((string)$vacancy_num['ele_meter_date'] == (string)date('Ymd')){
            fdump_api(['已完成当天首次抄表=='.__LINE__],'customize_water/getMeterEleCallback_data'.$dayKey,1);
            return true;
        }
        $user_bind=$this->HouseVillageUserBind->getOne([
            ['vacancy_id','=',$vacancy_num['vacancy_id']],
            ['status', '=', 1],
            ['type','in',[0,3]]
        ],'pigcms_id,uid,village_id,usernum,name,phone,single_id,floor_id,layer_id,vacancy_id');
        if(empty($user_bind)){
            fdump_api(['对应用户不存在=='.__LINE__],'customize_water/getMeterEleCallback_data'.$dayKey,1);
            return true;
        }
        $user_bind=$user_bind->toArray();
        $village_room=$this->HouseVillageUserVacancy->getOne([
            ['pigcms_id','=',$vacancy_num['vacancy_id']],
            ['is_del','=',0],
        ],'floor_id,name');
        if (!$village_room || $village_room->isEmpty()){
            fdump_api(['房屋不存在=='.__LINE__, 'vacancy_id' => $vacancy_num['vacancy_id']],'customize_water/getMeterEleCallback_data'.$dayKey,1);
            return true;
        }
        $rule=$this->getChargingTypeMethod($charge_type,$user_bind);
        if(!$rule){
            fdump_api(['规则不存在跳过=='.__LINE__, 'charge_type' => $charge_type, 'user_bind' => $user_bind],'customize_water/getMeterEleCallback_data'.$dayKey,1);
            return true;
        }
        $nowDay=date('Ymd');
        $start_num = 0.00;
        $end_num = $param['data']['totalActiveE'];
        $isexceptional=0;
        $whereArr=[['project_id','=',$rule['charge_project_id']],['layer_num','=',$user_bind['vacancy_id']]];
        $meter_reading=$this->HouseNewMeterService->getMeterRecordInfo($whereArr,'last_ammeter,add_time','id DESC');
        fdump_api(['抄表记录=='.__LINE__, 'whereArr' => $whereArr, 'meter_reading' => $meter_reading],'customize_water/getMeterEleCallback_data'.$dayKey,1);
        if($meter_reading && !$meter_reading->isEmpty()){
            if($meter_reading['last_ammeter'] < $end_num){
                $start_num = $meter_reading['last_ammeter'];   //起度
            }elseif($meter_reading['last_ammeter'] >= $end_num){
				$isexceptional=1;
			}
            $addtimeTemp=date('Ymd',$meter_reading['add_time']);
            if($meter_reading['last_ammeter']>=$end_num && $addtimeTemp== $nowDay){
                //当天已经超过表了
                fdump_api(['当天已经超过表了=='.__LINE__, 'param' => $param, 'meter_reading' => $meter_reading, 'start_num' => $start_num, 'end_num' => $end_num],'customize_water/getMeterEleCallback_data'.$dayKey,1);
                return true;
            }
        }
        if($isexceptional>0  || $end_num<=0){
            fdump_api(['不符合条件跳过=='.__LINE__, 'rule' => $rule, 'user_bind' => $user_bind, 'isexceptional' => $isexceptional, 'end_num' => $end_num],'customize_water/getMeterEleCallback_data'.$dayKey,1);
            return true;
        }
        $assemble_data=[
            'vacancy_num'=>$vacancy_num,
            'user_bind'=>$user_bind,
            'rule'=>$rule,
            'start_num'=>$start_num,
            'end_num'=>$end_num,
            'charge_type'=>$charge_type,
            'dec'=>'电费预缴，扣除余额，订单编号X1',
            'type'=>'customize_water_reading',
        ];
        fdump_api(['组装数据前=='.__LINE__, 'assemble_data' => $assemble_data, 'isexceptional' => $isexceptional],'customize_water/getMeterEleCallback_data'.$dayKey,1);
        $data=$this->assembleMethod($assemble_data,$isexceptional);
        fdump_api(['组装数据后=='.__LINE__, 'data' => $data],'customize_water/getMeterEleCallback_data'.$dayKey,1);
        $result=$this->ProcessSubPlan->add($data);
        fdump_api(['计划任务=='.__LINE__, 'result' => $result],'customize_water/getMeterEleCallback_data'.$dayKey,1);
        //记录当前首次抄电表日期
        $this->HouseVillageVacancyNum->saveOne([['id','=',$vacancy_num['id']]],['ele_meter_date'=>date('Ymd')]);
        fdump_api(['记录当前首次抄电表日期=='.__LINE__, 'ele_meter_date' => date('Ymd')],'customize_water/getMeterEleCallback_data'.$dayKey,1);
        return $result;

    }

    //todo [计划任务]拉取设备方数据，处理水表逻辑
    public function getMeterWaterReading(){
        if(!(int)cfg('customized_meter_reading')){
            return true;
        }
        set_time_limit(0);
        $dayKey = date('H');
        $dlineday = date("Y-m-d");
        $startdaytime = time()-86400;
        $startday= date("Y-m-d",$startdaytime);
        $requestUrl=$this->meterWaterReadingUrl.'?company=2802&startday='.$startday.'&dlineday='.$dlineday;
        $result=$this->curlGetMethod($requestUrl);
        fdump_api(['参数进来了=='.__LINE__,'requestUrl' => $requestUrl, 'result' => $result],'customize_water/getMeterWaterReading_data'.$dayKey,1);
        if(!isset($result['data']) || empty($result['data'])){
            fdump_api(['数据data无值=='.__LINE__],'customize_water/getMeterWaterReading_data'.$dayKey,1);
            return true;
        }
        $data=[];
        $charge_type='water';
        $nowDay=date('Ymd');
        foreach ($result['data'] as $kk=>$v){
            $v['watermeter']=trim($v['watermeter']);
            $vacancy_num=$this->HouseVillageVacancyNum->getVillageFind([
                ['a.water_number|a.heat_water_number','=',$v['watermeter']],
            ],'a.*,b.meter_type,b.meter_time,d.meter_digit,d.type,b.property_id');
            if (!$vacancy_num || $vacancy_num->isEmpty()){
                fdump_api(['房间表数据不存在=='.__LINE__, 'v' => $v],'customize_water/getMeterWaterReading_data'.$dayKey,1);
                continue;
            }
            $usercode='';
            if(isset($v['code']) && !empty($v['code'])){
                $usercode=trim($v['code']);
            }
            $vacancy_num=$vacancy_num->toArray();
            if($vacancy_num['meter_type'] == 1){ //每月
                if($vacancy_num['meter_time'] != date('d')){
                    fdump_api(['不符合对应月份跳过=='.__LINE__, 'v' => $v, 'meter_time' => $vacancy_num['meter_time']],'customize_water/getMeterWaterReading_data'.$dayKey,1);
                    continue;
                }
            }
            if($vacancy_num['meter_type'] == 2){ //每日
                if($vacancy_num['meter_time'] != date('H')){
                    fdump_api(['不符合对应日份跳过=='.__LINE__, 'v' => $v, 'meter_time' => $vacancy_num['meter_time']],'customize_water/getMeterWaterReading_data'.$dayKey,1);
                    continue;
                }
            }
            $user_bind=$this->HouseVillageUserBind->getOne([
                ['vacancy_id','=',$vacancy_num['vacancy_id']],
                ['status', '=', 1],
                ['type','in',[0,3]]
            ],'pigcms_id,uid,village_id,usernum,name,phone,single_id,floor_id,layer_id,vacancy_id');
            if(empty($user_bind)){
                fdump_api(['房间业主不存在跳过=='.__LINE__, 'v' => $v, 'vacancy_id' => $vacancy_num['vacancy_id']],'customize_water/getMeterWaterReading_data'.$dayKey,1);
                continue;
            }
            $user_bind=$user_bind->toArray();
            $village_room=$this->HouseVillageUserVacancy->getOne([
                ['pigcms_id','=',$vacancy_num['vacancy_id']],
                ['is_del','=',0],
            ],'floor_id,name');
            if (!$village_room || $village_room->isEmpty()){
                fdump_api(['房间不存在跳过=='.__LINE__, 'v' => $v, 'vacancy_id' => $vacancy_num['vacancy_id']],'customize_water/getMeterWaterReading_data'.$dayKey,1);
                continue;
            }
            $water_type=0;
            $info_type=$this->HouseVillageVacancyWaterType->getFind([
                ['village_id','=',$user_bind['village_id']],
                ['water_number','=',$v['watermeter']],
            ],'water_number,water_type,water_user_code');
            if($info_type && !$info_type->isEmpty()){
                $info_type=$info_type->toArray();
                $water_type=$info_type['water_type'];
                if(!empty($info_type['water_user_code'])){
                    $usercode=$info_type['water_user_code'];
                }
            }
            $saveArr=array();
            if(!empty($usercode) && empty($vacancy_num['heat_water_user_code']) &&($v['watermeter']==$vacancy_num['heat_water_number'])){
                $saveArr['heat_water_user_code']=$usercode;
            }
            if(!empty($usercode) && empty($vacancy_num['water_user_code']) &&($v['watermeter']==$vacancy_num['water_number'])){
                $saveArr['water_user_code']=$usercode;
            }
            if(!empty($saveArr)){
                $this->HouseVillageVacancyNum->saveOne(['id'=>$vacancy_num['id']],$saveArr);
            }
            $rule=$this->getChargingTypeMethod($charge_type,$user_bind,$water_type);
            if(!$rule){
                fdump_api(['规则不存在=='.__LINE__, 'v' => $v, 'charge_type' => $charge_type, 'user_bind' => $user_bind, 'water_type' => $water_type],'customize_water/getMeterWaterReading_data'.$dayKey,1);
                continue;
            }
            $start_num = 0.00;
            $end_num =trim($v['currentindex']);//止度
            $isexceptional=0;
            $whereArr=array(['project_id','=',$rule['charge_project_id']],['layer_num','=',$user_bind['vacancy_id']],['water_type','=',$water_type]);
            if($water_type>0){
                $whereArr[]=array('water_type','in',array(0,$water_type));
            }else{
                $whereArr[]=array('water_type','=',$water_type);
            }
            $meter_reading=$this->HouseNewMeterService->getMeterRecordInfo($whereArr,'last_ammeter,add_time','id DESC');
            if($meter_reading && !$meter_reading->isEmpty()){
                if($meter_reading['last_ammeter'] <= $v['currentindex']){
                    $start_num = $meter_reading['last_ammeter'];   //起度
                }elseif($meter_reading['last_ammeter'] > $v['currentindex']){
                    $isexceptional=1;
                }
                $addtimeTemp=date('Ymd',$meter_reading['add_time']);
                if($addtimeTemp== $nowDay){
                      //当天已经超过表了
                    fdump_api(['当天已经超过表了=='.__LINE__, 'v' => $v, 'meter_reading' => $meter_reading, 'start_num' => $start_num, 'end_num' => $end_num],'customize_water/getMeterWaterReading_data'.$dayKey,1);
                    continue;
                }
            }
            if($isexceptional>1 || ($end_num<=0)){
                fdump_api(['制度起度参数相同=='.__LINE__, 'v' => $v, 'meter_reading' => $meter_reading, 'start_num' => $start_num, 'end_num' => $end_num],'customize_water/getMeterWaterReading_data'.$dayKey,1);
                continue;
            }
            $param=[
                'vacancy_num'=>$vacancy_num,
                'user_bind'=>$user_bind,
                'rule'=>$rule,
                'start_num'=>$start_num,
                'end_num'=>$end_num,
                'charge_type'=>$charge_type,
                'dec'=>'水费预缴，扣除余额，订单编号X1',
                'type'=>'customize_water_reading',
                'water_type'=>$water_type,
                'watermeter'=>$v['watermeter']
            ];
            fdump_api(['符合的$param组装数据前=='.__LINE__, 'param' => $param],'customize_water/getMeterWaterReading_data'.$dayKey,1);
            $dataTmp=$this->assembleMethod($param,$isexceptional);
            $inset_id=$this->ProcessSubPlan->add($dataTmp);
            fdump_api([__LINE__, 'kk'=>$kk,'inset_id' => $inset_id],'customize_water/getMeterWaterReading_data'.$dayKey,1);
        }
        return true;
    }

    //todo 统一组织数据
    public function assembleMethod($param,$isexceptional=0){
        $vacancy_num=$param['vacancy_num'];
        $user_bind=$param['user_bind'];
        $rule=$param['rule'];
        $start_num=$param['start_num'];
        $end_num=$param['end_num'];
        $charge_type=$param['charge_type'];
        $desc=$param['dec'];
        $type=$param['type'];
        $rule_digit=-1;
        if(isset($rule['rule_digit']) && $rule['rule_digit']>-1 && $rule['rule_digit']<5){
            $rule_digit=$rule['rule_digit'];
        }
        if($vacancy_num['meter_digit']){
            if($rule_digit<=-1 || $rule_digit>=5){
                $rule_digit=intval($vacancy_num['meter_digit']);
            }
        }
        $this->time=time();
        $digit_type=$vacancy_num['type']==2 ? 2:1;
        $rule_digit=$rule_digit>-1 ? $rule_digit:2;
        //总价=（止度-起度）*单价*倍率
        $cost_num= $end_num-$start_num;
        $cost_money=$cost_num*$rule['unit_price']*$rule['rate'];
        $cost_money=formatNumber($cost_money, $rule_digit, $digit_type);
        $meterData=[
            'village_id'=> $user_bind['village_id'],
            'single_id'=> $user_bind['single_id'],
            'floor_id'=> $user_bind['floor_id'],
            'layer_id'=> $user_bind['layer_id'],
            'layer_num'=> $user_bind['vacancy_id'],
            'charge_name'=> $rule['charge_name'],
            'unit_price'=> $rule['unit_price'],
            'start_ammeter'=>$start_num,
            'last_ammeter'=>$end_num,
            'rate'=>$rule['rate'],
            'note'=>'',
            'cost_num'=>$cost_num,
            'cost_money'=>$cost_money,
            'add_time'=>$this->time,
            'project_id'=>$rule['charge_project_id'],
            'user_name'=>$user_bind['name'],
            'user_bind_id'=>$user_bind['pigcms_id'],
            'user_bind_phone'=>$user_bind['phone'],
            'work_name'=>'',
            'water_type'=>$param['water_type'],
            'opt_meter_time'=>$this->time
        ];

        if($isexceptional>0){
            $meterData['note']='止度数据有异常！';
            $meterData['extra_data']='autoisexceptional';
        }
        $orderData=[
            'uid'=>$user_bind['uid'],
            'name'=>$user_bind['name'],
            'phone'=>$user_bind['phone'],
            'pigcms_id'=>$user_bind['pigcms_id'],
            'property_id'=>$vacancy_num['property_id'],
            'village_id'=>$user_bind['village_id'],
            'order_type'=>$charge_type,
            'order_name'=>$rule['charge_name'],
            'room_id'=>$vacancy_num['vacancy_id'],
            'total_money'=>$meterData['cost_money'],
            'modify_money'=>$meterData['cost_money'],
            'project_id'=>$rule['charge_project_id'],
            'rule_id'=>$rule['id'],
            'unit_price'=>$meterData['unit_price'],
            'last_ammeter'=>$meterData['start_ammeter'],
            'now_ammeter'=>$meterData['last_ammeter'],
            'add_time'=>$this->time
        ];
        $service_start_time=time();
        $whereArr=['project_id'=>$meterData['project_id'],'village_id'=>$user_bind['village_id'],'layer_num'=>$meterData['layer_num']];
        $water_type=0;
        if($meterData['water_type']){
            $water_type=intval($meterData['water_type']);
        }
        $whereArr['water_type']=$water_type;
        $meter_reading=$this->HouseNewMeterService->getMeterRecordInfo($whereArr,'*','id DESC');
        if($meter_reading && !$meter_reading->isEmpty()){
            $service_start_time=$meter_reading['add_time'];
        }
        $orderData['service_start_time']=$service_start_time;
        $service_end_time=time();
        $orderData['service_end_time']=$service_end_time;
        $unique_id_str=$user_bind['village_id'].$type.$user_bind['vacancy_id'].'_'.$rule['charge_project_id'].'_'.$rule['id'].'_'.$param['water_type'].$this->time;
        $data=[
            'param'=>serialize([
                'desc'      =>  $desc,
                'userData'  =>  $user_bind,
                'meterData' =>  $meterData,
                'orderData' =>  $orderData,
                'type'      =>  $type,
                'charge_type'=> $param['charge_type'],
                'isexceptional'=>$isexceptional,
            ]),
            'plan_time'     =>  -120,
            'space_time'    =>  0,
            'add_time'      =>  $this->time,
            'file'          =>  'sub_d5_park',
            'time_type'     =>  1,
            'unique_id'     => $unique_id_str,
            'rand_number'   =>  mt_rand(1, max(cfg('sub_process_num'), 3)),
        ];
        return $data;
    }

    //todo [计划任务]拉取设备方数据，获取水表冷/热水类型
    public function getMeterWaterType(){
        $dayKey = date('H');
        fdump_api(['参数进来了=='.__LINE__,(int)cfg('customized_meter_reading')],'customize_water/getMeterWaterType_data'.$dayKey,1);
        if(!(int)cfg('customized_meter_reading')){
            return true;
        }
        $where=[
            ['company_name', '<>', ''],
            ['status','=',1],
        ];
        $list=$this->HouseVillage->getList($where,'village_id,company_name');
        if (!$list || $list->isEmpty()){
            fdump_api(['符合条件小区不存在=='.__LINE__,'where' =>$where],'customize_water/getMeterWaterType_data'.$dayKey,1);
            return true;
        }
        $list=$list->toArray();
        $data=[];
        foreach ($list as $v){
            if(empty($v['company_name'])){
                fdump_api(['缺少公司名称跳过=='.__LINE__,'v' =>$v],'customize_water/getMeterWaterType_data'.$dayKey,1);
                continue;
            }
            $data[]=[
                'param'=>serialize([
                    'village_id' =>  $v['village_id'],
                    'company_name' =>  $v['company_name'],
                    'type'      =>  'customize_water_type',
                ]),
                'plan_time'     =>  -130,
                'space_time'    =>  0,
                'add_time'      =>  $this->time,
                'file'          =>  'sub_d5_park',
                'time_type'     =>  1,
                'unique_id'     =>  'customize_water_type_'.$v['village_id'].'_'.$this->time.'_'.uniqid(),
                'rand_number'   =>  mt_rand(1, max(cfg('sub_process_num'), 3)),
            ];
        }
        fdump_api(['组装后数据=='.__LINE__,'data' =>$data],'customize_water/getMeterWaterType_data'.$dayKey,1);
        if($data){
            $this->ProcessSubPlan->addAll($data);
        }
        return true;
    }

    //todo 水电燃 生成账单
    public function createOrder($param){
        fdump_api(['参数进来了=='.__LINE__,$param],'customize_water/createOrder_data',1);
        if(!isset($param['meterData']) || !isset($param['orderData'])){
            fdump_api(['缺少参数=='.__LINE__,$param],'customize_water/createOrder_data',1);
            return true;
        }
        $price=floatval($param['orderData']['total_money']);
        if($price <= 0){
            fdump_api(['支付金额为零=='.__LINE__,$price,$param],'customize_water/createOrder_data',1);
            return true;
        }
        $nowDay=date('Ymd');
        $param['meterData']['water_type']=isset($param['meterData']['water_type']) ? intval($param['meterData']['water_type']) : 0;
        $whereArr=[['project_id','=',$param['meterData']['project_id']],['layer_num','=',$param['meterData']['layer_num']]];
        $whereArr[]=array('charge_name','=',$param['meterData']['charge_name']);
        $meter_reading=$this->HouseNewMeterService->getMeterRecordInfo($whereArr,'*','id DESC');
        if($meter_reading && !$meter_reading->isEmpty()){
            $addtimeTemp=date('Ymd',$meter_reading['add_time']);
            if($meter_reading['last_ammeter']>=$param['meterData']['last_ammeter'] && $addtimeTemp== $nowDay){
                //当天已经超过表了
                fdump_api(['当天已经超过表了=='.__LINE__, 'meterData' => $param['meterData'], 'meter_reading' => $meter_reading],'customize_water/createOrder_data',1);
                return true;
            }
        }
        try{
            $meterDataString = md5(json_encode($param['meterData'], JSON_UNESCAPED_UNICODE));
            $addRecord  = Cache::store('redis')->get($meterDataString);
            if (intval($addRecord) == 1) {
                fdump_api(['重复记录=='.__LINE__,'addRecord' => $addRecord,'param' => $param],'customize_water/createOrder_data',1);
                return true;
            }
            $cacheTag = 'autoCreateOrder';
            Cache::store('redis')->tag($cacheTag)->set($meterDataString, 1, 3600);
            $id = $this->HouseNewMeterService->addMeterReading($param['meterData']);
            if($id){
                fdump_api(['避免重复进行记录=='.__LINE__,'cacheTag' => $cacheTag,'meterDataString' => $meterDataString],'customize_water/createOrder_data',1);
                $param['orderData']['meter_reading_id'] = $id;
                if(isset($param['isexceptional']) && ($param['isexceptional']==1) && $param['meterData']['start_ammeter']==0){
                    //异常只生成记录，不生成账单
                }else{
                    $opt_money_type='';
                    $param['orderData']['order_type_flag']='';
                    if($param['orderData']['order_type']=='water' && $param['meterData']['water_type']==2){
                        $param['orderData']['order_type_flag']='hot_water_balance';
                        $opt_money_type='hot_water_balance';
                    }elseif($param['orderData']['order_type']=='water' && $param['meterData']['water_type']!=2){
                        $opt_money_type='cold_water_balance';
                        $param['orderData']['order_type_flag']='cold_water_balance';
                    }elseif($param['orderData']['order_type']=='electric'){
                        $opt_money_type='electric_balance';
                        $param['orderData']['order_type_flag']='electric_balance';
                    }
                    $order_id=$this->HouseNewCashierService->addOrder($param['orderData']);
                    $this->userBalancePayMethod($order_id,$param,$opt_money_type);
                }
            }
        }catch (\Exception $e){
            fdump_api(['生成账单失败=='.__LINE__,$e->getMessage(),$param],'customize_water/createOrder_data',1);
        }
        return true;
    }

    //todo 更新水表冷/热水类型
    public function updateWaterType($village_id,$company_name){
        fdump_api(['参数进来了=='.__LINE__,$village_id,$company_name],'customize_water/update_water_type_data',1);
        $requestUrl=$this->meterWaterTureUrl.'?companyName='.urlencode($company_name);
        $result=$this->curlGetMethod($requestUrl);
        fdump_api(['获取到参数=='.__LINE__,$village_id,$company_name,$result],'customize_water/update_water_type_data',1);
        if(!isset($result['data']) || empty($result['data'])){
            return true;
        }
        $add_data=[];
        foreach ($result['data'] as $v){
            if(!isset($v['waternature']) || empty($v['waternature'])){
                continue;
            }
            $usercode='';
            if(isset($v['code']) && !empty($v['code'])){
                $usercode=trim($v['code']);
            }
            $waternature=trim($v['waternature']);
            $water_type=0;
            if($waternature == '冷水' || strpos($waternature,'冷水')!==false){
                $water_type=1;
            }elseif ($waternature == '热水' || strpos($waternature,'热水')!==false){
                $water_type=2;
            }
            $info=$this->HouseVillageVacancyWaterType->getFind([
                ['village_id','=',$village_id],
                ['water_number','=',$v['watermeter']],
            ],'id,water_type');
            if($info && !$info->isEmpty()){
                if((int)$info['water_type'] != (int)$water_type){
                    $this->HouseVillageVacancyWaterType->saveOne([['id','=',$info['id']]],['water_type'=>$water_type,'water_user_code'=>$usercode,'update_time'=>date('Y-m-d H:i:s',$this->time)]);
                }
            }else{
                $add_data[]=[
                    'village_id'=>$village_id,
                    'water_number'=>$v['watermeter'],
                    'water_type'=>$water_type,
                    'water_user_code'=>$usercode,
                    'add_time'=>date('Y-m-d H:i:s',$this->time),
                    'update_time'=>date('Y-m-d H:i:s',$this->time)
                ];

            }
        }
        if($add_data){
            $this->HouseVillageVacancyWaterType->addAll($add_data);
        }
        fdump_api(['接收参数=='.__LINE__,$village_id,$company_name,$add_data],'customize_water/update_water_type_data',1);
        return true;
    }

    //todo 抵扣余额，支付成功
    public function userBalancePayMethod($order_id,$param,$opt_money_type=''){
        fdump_api(['参数进来了=='.__LINE__,$order_id,$param,'opt_money_type'=>$opt_money_type],'customize_water/userBalancePayMethod_data',1);
        $uid=$param['userData']['uid'];
        $village_id=$param['userData']['village_id'];
        $price=round($param['orderData']['total_money'],2);
        $property_id=$param['orderData']['property_id'];
        $phone='';
        $village_name='';
        $order_no=build_real_orderid($uid);
        $order=$this->HouseNewPayOrder->get_one([
            ['order_id', '=', $order_id]
        ]);
        if (!$order || $order->isEmpty()){
            fdump_api(['订单不存在=='.__LINE__,$order_id,$uid,$price,$param['desc'],$order_no],'customize_water/userBalancePayMethod',1);
            return true;
        }
        $now_village_user = [];
        $village_user = new StorageService();
        if(!empty($uid)&&!empty($village_id)){
            $now_village_user = $village_user->getVillageUser($uid,$village_id);
        }
        fdump_api(['住户余额=='.__LINE__,'now_village_user'=>$now_village_user,'uid'=>$uid,'price'=>$price],'customize_water/userBalancePayMethod',1);
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
        $charge_type_str=isset($charge_type[$param['charge_type']]) ? $charge_type[$param['charge_type']]:'';
        if(isset($now_village_user['can_use_village_balance']) && $now_village_user['can_use_village_balance']){
            $phone=$now_village_user['phone'];
            $village_name=$now_village_user['village_name'];
            $can_use_village_balance = true;
            $use_village_balance=round($now_village_user['current_money'],2);
            if($opt_money_type=='hot_water_balance'){
                $use_village_balance=$now_village_user['hot_water_balance'];
                $charge_type_str='热水费';
                $msgbalance='热水费，扣除小区热水费余额';
            }elseif($opt_money_type=='cold_water_balance'){
                $use_village_balance=$now_village_user['cold_water_balance'];
                $charge_type_str='冷水费';
                $msgbalance='冷水费，扣除小区冷水费余额';
            }elseif($opt_money_type=='electric_balance'){
                $charge_type_str='电费';
                $msgbalance='电费，扣除小区电费余额';
                $use_village_balance=$now_village_user['electric_balance'];
            }
            if($use_village_balance<$price){
                //相应的钱不够
                $datetime=date('m月d日H时');
                if($opt_money_type=='hot_water_balance' && $phone) {
                    $sms_data = array('type' => 'fee_notice');
                    $sms_data['uid'] = $uid;
                    $sms_data['village_id'] = $village_id;
                    $sms_data['mobile'] = $phone;
                    $sms_data['sendto'] = 'user';
                    $sms_data['mer_id'] = 0;
                    $sms_data['store_id'] = 0;
                    $sms_data['content'] = '尊敬的用户，截止'.$datetime.'，您'.$village_name.'的热水余额已欠费'.$price.'元，为保障您正常用水不受影响，请您在48小时内及时交清欠费。';
                    $sms = $smsService->sendSms($sms_data);
                }else if($opt_money_type=='cold_water_balance' && $phone){
                    $sms_data = array('type' => 'fee_notice');
                    $sms_data['uid'] = $uid;
                    $sms_data['village_id'] = $village_id;
                    $sms_data['mobile'] = $phone;
                    $sms_data['sendto'] = 'user';
                    $sms_data['mer_id'] = 0;
                    $sms_data['store_id'] = 0;
                    $sms_data['content'] = '尊敬的用户，截止'.$datetime.'，您'.$village_name.'的冷水余额已欠费'.$price.'元，为保障您正常用水不受影响，请您在48小时内及时交清欠费。';
                    $sms = $smsService->sendSms($sms_data);
                }else if($opt_money_type=='electric_balance' && $phone){
                    $sms_data = array('type' => 'fee_notice');
                    $sms_data['uid'] = $uid;
                    $sms_data['village_id'] = $village_id;
                    $sms_data['mobile'] = $phone;
                    $sms_data['sendto'] = 'user';
                    $sms_data['mer_id'] = 0;
                    $sms_data['store_id'] = 0;
                    $sms_data['content'] = '尊敬的用户，截止'.$datetime.'，您'.$village_name.'的电费余额已欠费'.$price.'元，为保障您正常用电不受影响，请您在48小时内及时交清欠费。';
                    $sms = $smsService->sendSms($sms_data);
                }
                $this->StorageService->sendMessage($village_id, $order_id,$property_id);//todo 余额扣款失败 发送通知
                return true;
            }
        }
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
                'opt_money_type'=>$opt_money_type
            ];
            $useresult = $village_user->addVillageUserMoney($village_money_data);
            $village_balance=$price;
            fdump_api(['住户余额=='.__LINE__,$village_money_data,$useresult],'customize_water/userBalancePayMethod',1);
            if ($useresult['error_code']) {
                    $datetime=date('m月d日H时');
                if($opt_money_type=='hot_water_balance' && $phone) {
                    $sms_data = array('type' => 'fee_notice');
                    $sms_data['uid'] = $uid;
                    $sms_data['village_id'] = $village_id;
                    $sms_data['mobile'] = $phone;
                    $sms_data['sendto'] = 'user';
                    $sms_data['mer_id'] = 0;
                    $sms_data['store_id'] = 0;
                    $sms_data['content'] = '尊敬的用户，截止'.$datetime.'，您'.$village_name.'的热水余额已欠费'.$price.'元，为保障您正常用水不受影响，请您在48小时内及时交清欠费。';
                    $sms = $smsService->sendSms($sms_data);
                }else if($opt_money_type=='cold_water_balance' && $phone){
                    $sms_data = array('type' => 'fee_notice');
                    $sms_data['uid'] = $uid;
                    $sms_data['village_id'] = $village_id;
                    $sms_data['mobile'] = $phone;
                    $sms_data['sendto'] = 'user';
                    $sms_data['mer_id'] = 0;
                    $sms_data['store_id'] = 0;
                    $sms_data['content'] = '尊敬的用户，截止'.$datetime.'，您'.$village_name.'的冷水余额已欠费'.$price.'元，为保障您正常用水不受影响，请您在48小时内及时交清欠费。';
                    $sms = $smsService->sendSms($sms_data);
                }else if($opt_money_type=='electric_balance' && $phone){
                    $sms_data = array('type' => 'fee_notice');
                    $sms_data['uid'] = $uid;
                    $sms_data['village_id'] = $village_id;
                    $sms_data['mobile'] = $phone;
                    $sms_data['sendto'] = 'user';
                    $sms_data['mer_id'] = 0;
                    $sms_data['store_id'] = 0;
                    $sms_data['content'] = '尊敬的用户，截止'.$datetime.'，您'.$village_name.'的电费余额已欠费'.$price.'元，为保障您正常用电不受影响，请您在48小时内及时交清欠费。';
                    $sms = $smsService->sendSms($sms_data);
                }
                $this->StorageService->sendMessage($village_id, $order_id,$property_id);//todo 余额扣款失败 发送通知
                return true;
            }
        } else {
            $useresult = $this->UserService->userMoney($uid, $price, L_($param['desc'], array("X1" => $order_no)));
            $system_balance=$price;
            fdump_api(['平台余额=='.__LINE__,$uid,$price,$useresult],'customize_water/userBalancePayMethod',1);
            if ($useresult['error_code']) {
                $this->StorageService->sendMessage($village_id, $order_id,$property_id);//todo 余额扣款失败 发送通知
                return true;
            }
        }
        $order=$order->toArray();
        $PayOrder=[
            'is_paid'=>1,
            'pay_time'=>$this->time,
            'pay_type'=>4,
            'pay_money'=>$price,
            'pay_bind_id'=>$param['userData']['pigcms_id'],
            'pay_bind_name'=>$param['userData']['name'],
            'pay_bind_phone'=>$param['userData']['phone'],
            'is_service_time'=>0,
            'village_balance'=>$village_balance,
            'system_balance'=>$system_balance
        ];
        if(!empty($opt_money_type)){
            $PayOrder['village_balance']=$price;
            $PayOrder['system_balance']=0;
            if($opt_money_type!='village_balance'){
                $PayOrder['order_type_flag']=$opt_money_type;
            }
        }
        $result=$this->StorageService->orderSummary($order,$PayOrder,$order_no);
        $this->sendSuccessNoticeMethod($village_id,$order,$price,$charge_type_str,$property_id); //todo 支付成功 发送通知
        fdump_api(['扣款结果=='.__LINE__,$order_id,$uid,$price,$param['desc'],$order_no,$order,$PayOrder,$result],'customize_water/userBalancePayMethod_data',1);
        return true;
    }
    /***
    * 余额不够发短信断水断电60秒计划任务
     ***/
    public function handleUserWaterElectricToSmsTask(){
        $customized_meter_reading = cfg('customized_meter_reading');
        $customized_meter_reading=$customized_meter_reading? intval($customized_meter_reading):0;
        if($customized_meter_reading<1){
            return true;
        }
        $whereVillage=array();
        $whereVillage[]=array('village_id','>',0);
        $whereVillage[]=array('status','in',array(0,1));
        $vfield='village_id,village_name,property_id,meter_type,meter_time,meter_nopayorder_date,company_name';
        $houseVillages=$this->HouseVillage->getList($whereVillage,$vfield,0,0,'village_id DESC',0);
        fdump_api([__LINE__, 'whereVillage' => $whereVillage, 'vfield' => $vfield, 'houseVillages' => $houseVillages], 'customize_water/handleUserWaterElectricToSmsTask', 1);
        $villageIds=array();
        $today = date('Ymd');
        $today = intval($today) - 1;
        $houseVillageInfoArr=array();
        $houseVillageInfoDb=new HouseVillageInfo();
        if($houseVillages && !$houseVillages->isEmpty()){
            $houseVillages=$houseVillages->toArray();
            fdump_api(['余额不够发短信断水断电60秒计划任务=='.__LINE__,'today'=>$today,'houseVillages'=>$houseVillages],'customize_water/handleUserWaterElectricToSmsTask',1);
            foreach ($houseVillages as $hvv){
                $per_day = isset($hvv['meter_nopayorder_date']) && intval($hvv['meter_nopayorder_date']) > 0 ? intval($hvv['meter_nopayorder_date']) : 0;
                fdump_api(['line=='.__LINE__,'hvv'=>$hvv,'per_day'=>$per_day],'customize_water/handleUserWaterElectricToSmsTask',1);
                if(empty($hvv['company_name'])){
                    continue;
                }
                $okday = $today - $per_day;
                if($hvv['meter_type']==1){
                    $dateTmp=date('Ym').$hvv['meter_time'];
                    $dateTmp=intval($dateTmp);
                    fdump_api(['line=='.__LINE__,'okday'=>$okday,'dateTmp'=>$dateTmp],'customize_water/handleUserWaterElectricToSmsTask',1);
                    if($okday!=$dateTmp){
                        continue;
                    }
                }elseif($hvv['meter_type']==2){
                    //按日
                }
                $villageIds[]=$hvv['village_id'];
                $houseVillageInfoArr[$hvv['village_id']]=$hvv;
                $whereVillageArr=array('village_id'=>$hvv['village_id']);
                $houseVillageInfo=$houseVillageInfoDb->getOne($whereVillageArr,'village_id,meter_extended_data');
                if($houseVillageInfo && !$houseVillageInfo->isEmpty()){
                    $houseVillageInfo=$houseVillageInfo->toArray();
                    $meter_extended_data=json_decode($houseVillageInfo['meter_extended_data'],1);
                    $houseVillageInfoArr[$hvv['village_id']]['meter_extended_data']=$meter_extended_data;
                }
            }
        }
        fdump_api([__LINE__,'villageIds'=>$villageIds,'houseVillageInfoArr'=>$houseVillageInfoArr],'customize_water/handleUserWaterElectricToSmsTask',1);
        if(empty($villageIds)){
            return true;
        }
        $whereArr=array();
        $whereArr[] =array('vacancy_id','>',0);
        $whereArr[]=array('village_id','in',$villageIds); 
        $field='village_id,vacancy_id,water_number,ele_number,heat_water_number,water_user_code,heat_water_user_code';
        $listData=$this->HouseVillageVacancyNum->getList($whereArr,$field,'id ASC');

        if($listData && !$listData->isEmpty()){
            $listData=$listData->toArray();
            if($listData){
                foreach ($listData as $vv){
                    $meter_extended_data='';
                    $dwmc='';
                    $village_name='';
                    if(isset($houseVillageInfoArr[$vv['village_id']])){
                        $meter_extended_data=$houseVillageInfoArr[$vv['village_id']]['meter_extended_data'];
                        $dwmc=$houseVillageInfoArr[$vv['village_id']]['company_name'];
                        $village_name=$houseVillageInfoArr[$vv['village_id']]['village_name'];
                    }
                    if(empty($meter_extended_data) || empty($dwmc)){
                        continue;
                    }
                    $whereUserBind=array();
                    $whereUserBind[]=array('village_id','=',$vv['village_id']);
                    $whereUserBind[]=array('vacancy_id','=',$vv['vacancy_id']);
                    $whereUserBind[]=array('status','=',1);
                    $whereUserBind[]=array('type','in',array(0,3));
                    $whereUserBind[]=array('uid','>',0);
                    $bindUser=$this->HouseVillageUserBind->getOne($whereUserBind,'pigcms_id,village_id,uid,name,phone,vacancy_id','type ASC');
                    if($bindUser && !$bindUser->isEmpty()){
                        $bindUser=$bindUser->toArray();
                        $userStorage=$this->StorageService->getVillageUser($bindUser['uid'],$vv['village_id']);
                        fdump_api([__LINE__,'bindUser'=>$bindUser,'vv'=>$vv,'userStorage'=>$userStorage, 'meter_extended_data' => $meter_extended_data],'customize_water/handleUserWaterElectricToSmsTask',1);
                        if(!empty($vv['water_number']) && $meter_extended_data && isset($meter_extended_data['cold_water_balance']) && ($meter_extended_data['cold_water_balance']['is_open']==1)){
                            //冷水
                            $balance=$meter_extended_data['cold_water_balance']['balance'];
                            $balance=$balance>0 ? $balance:0;
                            if($userStorage['cold_water_balance']<=$balance){
                                $param=array('type'=>'cold_water');
                                $param['bindUser']=$bindUser;
                                $param['dwmc']=$dwmc;
                                $param['village_name']=$village_name;
                                $param['number_no']=$vv['water_number'];
                                $param['user_code']=$vv['water_user_code'];
                                $param['balance']=$userStorage['cold_water_balance'];
                                fdump_api([__LINE__,'cold_water'=>$param],'customize_water/handleUserWaterElectricToSmsTask',1);
                                $this->villageMeterAutoTipsQueuePushToJob($param);
                            }
                        }
                        if(!empty($vv['heat_water_number']) && $meter_extended_data && isset($meter_extended_data['hot_water_balance']) && ($meter_extended_data['hot_water_balance']['is_open']==1)){
                            //热水
                            $balance=$meter_extended_data['hot_water_balance']['balance'];
                            $balance=$balance>0 ? $balance:0;
                            if($userStorage['hot_water_balance']<=$balance){
                                $param=array('type'=>'hot_water');
                                $param['bindUser']=$bindUser;
                                $param['dwmc']=$dwmc;
                                $param['village_name']=$village_name;
                                $param['number_no']=$vv['heat_water_number'];
                                $param['user_code']=$vv['heat_water_user_code'];
                                $param['balance']=$userStorage['hot_water_balance'];
                                fdump_api([__LINE__,'hot_water'=>$param],'customize_water/handleUserWaterElectricToSmsTask',1);
                                $this->villageMeterAutoTipsQueuePushToJob($param);
                            }
                        }
                        if(!empty($vv['ele_number']) && $meter_extended_data && isset($meter_extended_data['electric_balance']) && ($meter_extended_data['electric_balance']['is_open']==1)){
                            //电费
                            $balance=$meter_extended_data['electric_balance']['balance'];
                            $balance=$balance>0 ? $balance:0;
                            if($userStorage['electric_balance']<=$balance){
                                $param=array('type'=>'electric');
                                $param['bindUser']=$bindUser;
                                $param['dwmc']=$dwmc;
                                $param['village_name']=$village_name;
                                $param['number_no']=$vv['ele_number'];
                                $param['user_code']='';
                                $param['balance']=$userStorage['electric_balance'];
                                fdump_api([__LINE__,'electric'=>$param],'customize_water/handleUserWaterElectricToSmsTask',1);
                                $this->villageMeterAutoTipsQueuePushToJob($param);
                            }
                        }
                    }

                }
            }
        }
        return true;
    }
    /***
    ***余额不够发短信断水断电60秒队列处理
     ***/
    public function handleUserToSmsTipsJob($data=array()){
        $bindUser=$data['bindUser'];
        $user=$this->User->getOne(['uid'=>$bindUser['uid']],'uid,openid,wxapp_openid,phone,nickname');
        if($user && !$user->isEmpty()){
            $user=$user->toArray();
            if($user['phone']){
                $bindUser['phone']=$user['phone'];
                if(empty($bindUser['name'])&& !empty($user['nickname'])){
                    $bindUser['name']=$user['nickname'];
                }
            }
        }
        $this->waterElectricToSmsFdump(['执行发短息和断水断电=='.__LINE__,'data'=>$data,'bindUser'=>$bindUser],'customize_water/00handleUserToSmsTipsJob',1);
        $balance=isset($data['balance']) && $data['balance'] ? round($data['balance'],2):0;
        $smsService=new SmsService();
        $datetime=date('m月d日H时');
        if($data['type']=='cold_water'){
                $sms_data = array('type' => 'fee_notice');
                $sms_data['uid'] = $bindUser['uid'];
                $sms_data['village_id'] = $bindUser['village_id'];
                $sms_data['mobile'] = $bindUser['phone'];
                $sms_data['sendto'] = 'user';
                $sms_data['mer_id'] = 0;
                $sms_data['store_id'] = 0;
                $sms_data['content'] ='尊敬的用户，截止'.$datetime.'，您'.$data['village_name'].'的冷水余额已不足最低限额（额度为'.$balance.'元），请及时充值';
                $sms = $smsService->sendSms($sms_data);
            if($data['number_no']){
                $closerWater=array('dwmc'=>$data['dwmc']);
                $closerWater['code']=$data['user_code'];
                $closerWater['watermeter']=$data['number_no'];
                $closerWater['valvestate']=1;  //关
                $closerWaterArr=array();
                $closerWaterArr[]=$closerWater;
                $this->handleUserWaterUse($closerWaterArr);
                $this->villageOpenWaterElectricQueuePushToJob($data,60);
            }
        }else if($data['type']=='hot_water'){
            $sms_data = array('type' => 'fee_notice');
            $sms_data['uid'] = $bindUser['uid'];
            $sms_data['village_id'] = $bindUser['village_id'];
            $sms_data['mobile'] = $bindUser['phone'];
            $sms_data['sendto'] = 'user';
            $sms_data['mer_id'] = 0;
            $sms_data['store_id'] = 0;
            $sms_data['content'] ='尊敬的用户，截止'.$datetime.'，您'.$data['village_name'].'的热水余额已不足最低限额（额度为'.$balance.'元），请及时充值';
            $sms = $smsService->sendSms($sms_data);
            if($data['number_no']){
                $closerWater=array('dwmc'=>$data['dwmc']);
                $closerWater['code']=$data['user_code'];
                $closerWater['watermeter']=$data['number_no'];
                $closerWater['valvestate']=1;  //关
                $closerWaterArr=array();
                $closerWaterArr[]=$closerWater;
                $this->handleUserWaterUse($closerWaterArr);
                $this->villageOpenWaterElectricQueuePushToJob($data,60);
            }
        }else if($data['type']=='electric'){
            $sms_data = array('type' => 'fee_notice');
            $sms_data['uid'] = $bindUser['uid'];
            $sms_data['village_id'] = $bindUser['village_id'];
            $sms_data['mobile'] = $bindUser['phone'];
            $sms_data['sendto'] = 'user';
            $sms_data['mer_id'] = 0;
            $sms_data['store_id'] = 0;
            $sms_data['content'] = '尊敬的用户，截止'.$datetime.'，您'.$data['village_name'].'的电费余额已不足最低限额（额度为'.$balance.'元），请及时充值';
            $sms = $smsService->sendSms($sms_data);
            if($data['number_no']){
                $this->closeUserElectricUse($data['number_no']);
                $this->villageOpenWaterElectricQueuePushToJob($data,60);
            }
        }
    }
    public function waterElectricToSmsFdump($data, $filename='test', $append=false){
        $oldUmask = umask();
        try {
            umask(0);
            $root=dirname(dirname(dirname(__DIR__)));
            $root=dirname(dirname($root));
            $fileName =  rtrim($root,'/') . '/api/log/' . date('Ymd') . 'a/' . $filename . '_fdump.php';
            $dirName = dirname($fileName);
            if(!file_exists($dirName)){
                mkdir($dirName, 0777, true);
            }
            $debug_trace = debug_backtrace();
            $file = __FILE__ ;
            $line = "unknown";
            if (isset($debug_trace[0]) && isset($debug_trace[0]['file'])) {
                $file = $debug_trace[0]['file'] ;
                $line = $debug_trace[0]['line'];
            }
            $f_l = '['.$file.' : '.$line.']';
            
            if($append){
                if(!file_exists($fileName)){
                    file_put_contents($fileName,'<?php');
                    chmod($fileName,0777);
                }
                file_put_contents($fileName,PHP_EOL.$f_l.PHP_EOL.date('Y-m-d H:i:s').' '.PHP_EOL.var_export($data,true).PHP_EOL,FILE_APPEND);
            }
            else{
                file_put_contents($fileName,'<?php'.PHP_EOL.date('Y-m-d H:i:s').' '.PHP_EOL.var_export($data,true));
            }
            umask($oldUmask);
        } catch (\Exception $e) {
            umask($oldUmask);
        }
        umask($oldUmask);
    }
    /***
    *60秒后回复水电
     **/
    public function handleUserToOpenWaterElectricJob($data=array()){
        //$bindUser=$data['bindUser'];
        $this->waterElectricToSmsFdump(['60秒后回复水电=='.__LINE__,'data'=>$data],'customize_water/00handleUserToOpenWaterElectricJob',1);
        if($data['type']=='cold_water' && !empty($data['number_no'])){
            if($data['number_no']){
                $closerWater=array('dwmc'=>$data['dwmc']);
                $closerWater['code']=$data['user_code'];
                $closerWater['watermeter']=$data['number_no'];
                $closerWater['valvestate']=0;  //开
                $closerWaterArr=array();
                $closerWaterArr[]=$closerWater;
                $this->handleUserWaterUse($closerWaterArr);
            }
        }else if($data['type']=='hot_water' && !empty($data['number_no'])){

            if($data['number_no']){
                $closerWater=array('dwmc'=>$data['dwmc']);
                $closerWater['code']=$data['user_code'];
                $closerWater['watermeter']=$data['number_no'];
                $closerWater['valvestate']=0;  //开
                $closerWaterArr=array();
                $closerWaterArr[]=$closerWater;
                $this->handleUserWaterUse($closerWaterArr);
            }
        }else if($data['type']=='electric' && !empty($data['number_no'])){
              $this->openUserElectricUse($data['number_no']);
        }
    }
    /***
     *抄表后 多长时间内订单未支付断电 断水
     ****/
    public function handleUserWaterElectricUse(){
        $customized_meter_reading = cfg('customized_meter_reading');
        $customized_meter_reading=$customized_meter_reading? intval($customized_meter_reading):0;
        if($customized_meter_reading<1){
            return true;
        }
        $whereVillage=array();
        $whereVillage[]=array('village_id','>',0);
        $whereVillage[]=array('status','in',array(0,1));
        $vfield='village_id,village_name,property_id,meter_type,meter_time,meter_nopayorder_date,company_name';
        $houseVillages=$this->HouseVillage->getList($whereVillage,$vfield,0,0,'village_id DESC',0);
        $houseVillageNewtaskLog=new HouseVillageNewtaskLog();
        $villageIds=array();
        $todaydate=date('Y-m-d').' 00:00:01';
        if($houseVillages && !$houseVillages->isEmpty()){
            $houseVillages=$houseVillages->toArray();
            fdump_api(['多长时间内订单未支付断电 断水==='.__LINE__,'houseVillages'=>$houseVillages],'customize_water/handleUserWaterElectricUse',1);
            $nowtime=time();
            $starttime=date('Y-m').'-01 00:00:01';
            $starttime=strtotime($starttime);
            $endtime=date('Y-m').'-'.date('t').' 23:59:59';
			$endtime=strtotime($endtime);
            $no_pay_order_time=0;
            foreach ($houseVillages as $hvv){
                if(empty($hvv['company_name']) || $hvv['meter_nopayorder_date']<=0){
                    continue;
                }
                $hvv['meter_nopayorder_date']=intval($hvv['meter_nopayorder_date']);
                $no_pay_order_time=strtotime("-".$hvv['meter_nopayorder_date']." day")+29*60; //每30执行一次
                if($hvv['meter_type']==1){
                    $hvv['meter_time']=intval($hvv['meter_time']);
                    $taskLogWhere=array();
                    $taskLogWhere[]=['village_id','=',$hvv['village_id']];
                    $taskLogWhere[]=['add_time','>',$starttime];
                    $taskLogWhere[]=['add_time','<',$endtime];
                    $taskLogWhere[]=['task_type','=','handleVillageUserWaterElectricUse'];
                    $tmplog=$houseVillageNewtaskLog->getOneData($taskLogWhere,'*');
                    $dateTmp=date('Y-m').'-'.$hvv['meter_time'].' 00:00:01';
                    $dateTmpTime=strtotime($dateTmp);
                    $newtime=strtotime("+".$hvv['meter_nopayorder_date']." day",$dateTmpTime);
                    $exec_day=$hvv['meter_nopayorder_date']+$hvv['meter_time'];
                    $current_day=date('j');
                    $exedate=date('Ymd',$newtime);
                    $exedate=intval($exedate);
                    fdump_api([__LINE__,$dateTmp,$newtime,'exedate'=>$exedate,'exec_day'=>$exec_day,'current_day'=>$current_day,'taskLogWhere'=>$taskLogWhere,'tmplog'=>$tmplog],'customize_water/handleUserWaterElectricUse',1);
                    if(!empty($tmplog) && $exec_day==$current_day ){
                        //到时间执行 更新掉执行时间
                        $houseVillageNewtaskLog->updateOneData(['id'=>$tmplog['id']],['execute_time'=>$nowtime]);
                    }elseif(!empty($tmplog) && $tmplog['execute_time']>10){
                        continue;
                    }elseif(empty($tmplog)){
                        $tmplogarr=array();
                        $tmplogarr['village_id']=$hvv['village_id'];
                        $tmplogarr['task_type']='handleVillageUserWaterElectricUse';
                        $tmplogarr['need_execute_time']=$exedate;
                        $tmplogarr['add_time']=$nowtime;
                        $houseVillageNewtaskLog->addOneData($tmplogarr);
                        continue;
                    }else{
                        continue;
                    }
                    
                }elseif($hvv['meter_type']==2){
                    //按日
                    //$no_pay_order_time=strtotime("-".$hvv['meter_nopayorder_date']." day",$todaydate);
                }
                fdump_api([__LINE__,'no_pay_order_time'=>$no_pay_order_time],'customize_water/handleUserWaterElectricUse',1);
                if($no_pay_order_time>0){
                    $no_pay_order_2time=$no_pay_order_time-3600*24*30;
                    $whereArr=array();
                    $whereArr[]=['village_id','=',$hvv['village_id']];
                    $whereArr[]=['is_paid','=',2];
                    $whereArr[]=['is_discard','=',1];
                    $whereArr[] = ['add_time','<=',$no_pay_order_time];
                    $whereArr[] = ['add_time','>=',$no_pay_order_2time];
                    $whereArr[] = ['third_opt_status','<',1];
                    $whereArr[]=['order_type_flag','in',array('cold_water_balance','hot_water_balance','electric_balance')];
                    $fieldStr='order_id,uid,pigcms_id,name,phone,order_type,room_id,property_id,village_id,order_name,order_type_flag,extra_data';
                    $orders=$this->HouseNewPayOrder->getOrder($whereArr,$fieldStr,'order_id ASC');
                    $nowtime=time();
                    if($orders && !is_array($orders) && !$orders->isEmpty()){
                        $orders=$orders->toArray();
                        fdump_api([__LINE__,'orders'=>$orders],'customize_water/handleUserWaterElectricUse',1);
                        foreach ($orders as $vv){
                            if(empty($vv['order_type_flag'])){
                                continue;
                            }
                            $whereArr=array();
                            $whereArr[] =array('vacancy_id','=',$vv['room_id']);
                            $whereArr[]=array('village_id','=',$vv['village_id']);
                            $field='village_id,vacancy_id,water_number,ele_number,heat_water_number,water_user_code,heat_water_user_code';
                            $vacancyNum=$this->HouseVillageVacancyNum->getFind($whereArr,$field,'id desc');
                            if($vacancyNum && !$vacancyNum->isEmpty()){
                                if($vv['order_type_flag']=='cold_water_balance' && $vacancyNum['water_number']){
                                    $closerWater=array('dwmc'=>$hvv['company_name']);
                                    $closerWater['code']=$vacancyNum['water_user_code'];
                                    $closerWater['watermeter']=$vacancyNum['water_number'];
                                    $closerWater['valvestate']=1;  //关
                                    $closerWaterArr=array();
                                    $closerWaterArr[]=$closerWater;
                                    $ret=$this->handleUserWaterUse($closerWaterArr);
                                    if($ret && is_array($ret) && isset($ret['errcode']) && ($ret['errcode']===0||$ret['errcode']==='0')){
                                        //操作成功了
                                        $extra_data=!empty($vv['extra_data']) ? json_decode($vv['extra_data'],1):array();
                                        $extra_data= is_array($extra_data) ? $extra_data:array();
                                        $extra_data['cold_water']=array();
                                        $extra_data['cold_water']['closerWater']=$closerWater;
                                        $ret['opt_time']=$nowtime;
                                        $extra_data['cold_water']['result']=$ret;
                                        $saveArr=array('extra_data'=>json_encode($extra_data,JSON_UNESCAPED_UNICODE));
                                        $saveArr['third_opt_status']=1;
                                        $this->HouseNewPayOrder->saveOne(['order_id'=>$vv['order_id']],$saveArr);
                                    }
                                }else if($vv['order_type_flag']=='hot_water_balance' && $vacancyNum['heat_water_number']){
                                    $closerWater=array('dwmc'=>$hvv['company_name']);
                                    $closerWater['code']=$vacancyNum['heat_water_user_code'];
                                    $closerWater['watermeter']=$vacancyNum['heat_water_number'];
                                    $closerWater['valvestate']=1;  //关
                                    $closerWaterArr=array();
                                    $closerWaterArr[]=$closerWater;
                                    $ret=$this->handleUserWaterUse($closerWaterArr);
                                    if($ret && is_array($ret) && isset($ret['errcode']) && ($ret['errcode']===0||$ret['errcode']==='0')){
                                        //操作成功了
                                        $extra_data=!empty($vv['extra_data']) ? json_decode($vv['extra_data'],1):array();
                                        $extra_data= is_array($extra_data) ? $extra_data:array();
                                        $extra_data['hot_water']=array();
                                        $extra_data['hot_water']['closerWater']=$closerWater;
                                        $ret['opt_time']=$nowtime;
                                        $extra_data['hot_water']['result']=$ret;
                                        $saveArr=array('extra_data'=>json_encode($extra_data,JSON_UNESCAPED_UNICODE));
                                        $saveArr['third_opt_status']=1;
                                        $this->HouseNewPayOrder->saveOne(['order_id'=>$vv['order_id']],$saveArr);
                                    }
                                }else if($vv['order_type_flag']=='electric_balance' && $vacancyNum['ele_number']){
                                    $ret=$this->closeUserElectricUse($vacancyNum['ele_number']);
                                    if($ret && is_array($ret) && isset($ret['statusCode']) && ($ret['statusCode']==='000000' || $ret['statusCode']===0)){
                                        //操作成功了
                                        $extra_data=!empty($vv['extra_data']) ? json_decode($vv['extra_data'],1):array();
                                        $extra_data= is_array($extra_data) ? $extra_data:array();
                                        $extra_data['electric']=array();
                                        $extra_data['electric']['closerElectric']=array('ele_number'=>$vacancyNum['ele_number']);
                                        $ret['opt_time']=$nowtime;
                                        $extra_data['electric']['result']=$ret;
                                        $saveArr=array('extra_data'=>json_encode($extra_data,JSON_UNESCAPED_UNICODE));
                                        $saveArr['third_opt_status']=1;
                                        $this->HouseNewPayOrder->saveOne(['order_id'=>$vv['order_id']],$saveArr);
                                    }
                                }
                                usleep(500000); //延迟半秒
                            }
                            /*
                            $user_bind=$this->HouseVillageUserBind->getOne([
                                ['vacancy_id','=',$vv['room_id']],
                                ['status', '=', 1],
                                ['type','in',[0,3]]
                            ],'pigcms_id,uid,village_id,usernum,name,phone,single_id,floor_id,layer_id,vacancy_id');
                            if(empty($user_bind)||$user_bind->isEmpty()){
                                continue;
                            }
                            */
                        }
                    }
                }
            }
        }
        return true;
    }
    /***
    *订单支付后 通水通电
     ***/
    public function  openUserWaterElectricUse($village_id=0,$room_id=0,$type='',$uid=0){
        if(empty($type) || $village_id<=0 || $room_id<=0){
            return true;
        }
        $vfield='village_id,village_name,property_id,meter_type,meter_time,meter_nopayorder_date,company_name';
        $houseVillage=$this->HouseVillage->getOne($village_id,$vfield);
        $company_name='';
        if($houseVillage && !$houseVillage->isEmpty()){
            $houseVillage=$houseVillage->toArray();
            $company_name=$houseVillage['company_name'];
        }
		fdump_api([__LINE__,'village_id'=>$village_id,'room_id'=>$room_id,'type'=>$type,'houseVillage'=>$houseVillage],'customize_water/openUserWaterElectricUse',1);
		
        if(empty($company_name)){
            return true;
        }
        $whereArr=array();
        $whereArr[] =array('vacancy_id','=',$room_id);
        $whereArr[]=array('village_id','=',$village_id);
        $field='village_id,vacancy_id,water_number,ele_number,heat_water_number,water_user_code,heat_water_user_code';
        $vacancyNum=$this->HouseVillageVacancyNum->getFind($whereArr,$field,'id desc');
        fdump_api([__LINE__,'vacancyNum'=>$vacancyNum],'customize_water/openUserWaterElectricUse',1);
        if($vacancyNum && !$vacancyNum->isEmpty()){
		//fdump_api([__LINE__,'water_number'=>$vacancyNum['water_number']],'customize_water/openUserWaterElectricUse',1);
            if($type=='cold_water_balance' && $vacancyNum['water_number']){
                $closerWater=array('dwmc'=>$company_name);
                $closerWater['code']=$vacancyNum['water_user_code'];
                $closerWater['watermeter']=$vacancyNum['water_number'];
                $closerWater['valvestate']=0;  //开
                $closerWaterArr=array();
                $closerWaterArr[]=$closerWater;
				fdump_api([__LINE__,'closerWaterArr'=>$closerWaterArr],'customize_water/openUserWaterElectricUse',1);
                $this->handleUserWaterUse($closerWaterArr);
            }else if($type=='hot_water_balance' && $vacancyNum['heat_water_number']){
                $closerWater=array('dwmc'=>$company_name);
                $closerWater['code']=$vacancyNum['heat_water_user_code'];
                $closerWater['watermeter']=$vacancyNum['heat_water_number'];
                $closerWater['valvestate']=0;  //开
                $closerWaterArr=array();
                $closerWaterArr[]=$closerWater;
                $this->handleUserWaterUse($closerWaterArr);
            }else if($type=='electric_balance' && $vacancyNum['ele_number']){
                $this->openUserElectricUse($vacancyNum['ele_number']);
            }
        }
        return true;
    }
    /**断电****/
    public function closeUserElectricUse($ele_number=''){
        $mark_str="QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm";
        $rand=substr(str_shuffle($mark_str),mt_rand(0,strlen($mark_str)-11),4);
        $url=$this->meterEleCloseingUrl.$ele_number;
        $json =json_encode(["businessNo"=>$this->time.$rand],true);
        $a = strtoupper($this->encodeHexStringMethod($this->md5HexMethod($json)));
        $sb = $url.":POST:$a:$this->time:".$this->meterEleReadingScret;
        $b = strtoupper($this->encodeHexStringMethod($this->md5HexMethod($sb)));
        $sign = $this->meterEleReadingKey.":".$this->time.":".$b;
        $output = $this->jsonPostMethod($url,$json,array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length:' . strlen($json),
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'sign:'.$sign
        ));
        $this->waterElectricToSmsFdump([__LINE__,'requestUrl'=>$url,'ele_number'=>$ele_number,'output'=>$output],'customize_water/closeUserElectricUse',1);
        if($output && is_string($output)){
            $output=json_decode($output,1);
        }
        return $output;
    }
    /**通电****/
    public function openUserElectricUse($ele_number=''){
        $mark_str="QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm";
        $rand=substr(str_shuffle($mark_str),mt_rand(0,strlen($mark_str)-11),4);
        $url=$this->meterEleOpeningUrl.$ele_number;
        $postData=["businessNo"=>$this->time.$rand];
        $json =json_encode($postData,true);
        $a = strtoupper($this->encodeHexStringMethod($this->md5HexMethod($json)));
        $sb = $url.":POST:$a:$this->time:".$this->meterEleReadingScret;
        $b = strtoupper($this->encodeHexStringMethod($this->md5HexMethod($sb)));
        $sign = $this->meterEleReadingKey.":".$this->time.":".$b;
        $output = $this->jsonPostMethod($url,$json,array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length:' . strlen($json),
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'sign:'.$sign
        ));
        $this->waterElectricToSmsFdump([__LINE__,'requestUrl'=>$url,'ele_number'=>$ele_number,'output'=>$output],'customize_water/openUserElectricUse',1);
        if($output && is_string($output)){
            $output=json_decode($output,1);
        }
        return $output;
    }
    /**断水，通水处理****/
    public function handleUserWaterUse($dataArr=array()){


        $requestUrl=$this->meterWaterPaymentInfoUrl;
        $postJson=json_encode($dataArr,JSON_UNESCAPED_UNICODE);
        $postArr=array('data'=>$postJson);
        $headerArr=array('Content-Type: application/x-www-form-urlencoded');
        $result =$this->jsonPostMethod($requestUrl,$postArr,$headerArr,false);
        $result=$result?strip_tags($result):'';
        $resultArr=explode(PHP_EOL,$result);
        $result=$resultArr['0'] ? trim($resultArr['0']):'';
        $this->waterElectricToSmsFdump([__LINE__,'requestUrl'=>$requestUrl,'postArr'=>$postArr,'result'=>$result],'customize_water/handleUserWaterUse',1);
        $result = !empty($result)? json_decode($result, true):'';
        return $result;
    }
    
    //todo 支付成功 发送通知
    public function sendSuccessNoticeMethod($village_id,$order,$price,$charge_type='水费',$property_id=0){
        $room = $this->HouseVillageUserVacancy->getLists(['pigcms_id' => $order['room_id']]);
        if (!$order['pigcms_id'] && $order['order_id']) {
            $href = get_base_url('pages/houseMeter/NewCollectMoney/billDetails?order_id=' .$order['order_id'].'&type=2');
        } else {
            $href = get_base_url('pages/houseMeter/NewCollectMoney/billsPaid?pigcms_id='.$order['pigcms_id'].'&village_id='.$village_id);
        }
        $address='--';
        if($room && !$room->isEmpty()){
            $room=$room->toArray();
            if($room){
                $room=$room[0];
            }
            $address =$this->HouseVillageService->word_replce_msg(array('single_name'=>$room['single_name'],'floor_name'=>$room['floor_name'],'layer'=>$room['layer_name'],'room'=>$room['room']),$village_id);
        }
        $openid = $this->User->getOne(['uid' => $order['uid']], 'openid');
        $data = [
            'tempKey' => 'TM01008',
            'dataArr' => [
                'href' => $href,
                'wecha_id' => $openid['openid'],
                'first' => '缴费成功提醒',
                'keynote2' => $address,
                'keynote1' => $charge_type,
                'remark' => '缴费时间:' . date('Y-m-d H:i', $this->time) . '\n' . '缴费金额:￥' . $price,

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
        $this->TemplateNewsService->sendTempMsg($data['tempKey'], $data['dataArr'],0,$property_id,$xtype);
        fdump_api(['支付成功,发送通知=='.__LINE__,$village_id,$order,$price,$charge_type,$data],'customize_water/sendSuccessNoticeMethod_data',1);
        return true;
    }

    //todo curl get请求
    public function curlGetMethod($url){
        $result = Http::curlGet($url);
        if(!empty($result)){
            $result=substr($result,0,strrpos($result,'}')).'}';
            if(!is_array($result)){
                $result = json_decode($result,true);
            }
        }
        return $result;
    }

    //todo 查询对应科目的收费标准
    public function getChargingTypeMethod($charge_type,$param,$type=0){
        $where=[];
        switch ($charge_type) {
            case "water": //水费
                if($type > 0 && in_array($type,[1,2])){
                    $where[]=['n.water_type', '=', $type];
                }
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
        if(isset($param['vacancy_id']) && $param['vacancy_id']){
            $where[]=['b.vacancy_id', '=', $param['vacancy_id']];
        }
        $where[] = ['b.charge_valid_time','<=',$this->time];
        $list=$this->getChargingRuleMethod($where,'r.id,r.unit_price,r.not_house_rate,r.rate,r.charge_project_id,r.rule_digit,r.charge_name,b.custom_value','b.charge_valid_time desc');
        if(!$list){
            return false;
        }
        return $list;
    }

    //todo 匹配对应房间的收费标准
    public function getChargingRuleMethod($where,$field,$order=''){
        $list= $this->HouseNewChargeStandardBind->getBindProject($where,$field,$order);
        if($list && !$list->isEmpty()){
            return $list->toArray();
        }else{
            return false;
        }
    }

    public function encodeHexStringMethod(array $bytes)
    {
        $LOWER = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c','d','e','f'];
        $charArr = [];
        foreach ($bytes as $value) {
            $value = intval($value);
            $charArr[] = $LOWER[$this->urightMethod(0xF0 & $value, 4)];
            $charArr[] = $LOWER[0x0F & $value];
        }
        return implode("", $charArr);
    }

    /** php 无符号右移 */
    public function urightMethod($a, $n)
    {
        $c = 2147483647 >> ($n - 1);
        return $c & ($a >> $n);
    }

    /**
     * 模拟DigestUtils.md5
     * @param    [string]                   $string 加密字符
     * @return   [array]                           加密之后的byte数组
     * @dateTime 2021-01-05T09:28:33+0800
     */
    public function md5HexMethod($string)
    {
        return unpack("c*", md5($string, true));
    }

    public function jsonPostMethod($url, $data = NULL,$header,$is_no_json=true)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if(is_array($data) && $is_no_json)
        {
            $data = json_encode($data,JSON_UNESCAPED_UNICODE);
        }elseif(is_array($data)){
            $data= http_build_query($data);
        }
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER,$header);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.80 Safari/537.36');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl ,CURLOPT_TIMEOUT ,60);
        $res = curl_exec($curl);
        $requestinfo = curl_getinfo($curl);
        $errorno = curl_errno($curl);
        $this->waterElectricToSmsFdump([__LINE__,'requestinfo'=>$requestinfo,'res'=>$res,'header'=>$header,$data],'customize_water/000jsonPostMethod',1);
        if ($errorno) {
            return $errorno;
        }
        curl_close($curl);
        return $res;

    }




}