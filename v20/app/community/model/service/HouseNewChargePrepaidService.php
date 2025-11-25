<?php
/**
 * @author : liukezhu
 * @date : 2021/6/11
 */
namespace app\community\model\service;

use app\community\model\db\HouseNewChargePrepaid;
use app\community\model\db\HouseNewChargeRule;
use app\community\model\db\HouseNewChargeStandardBind;
use app\community\model\db\HouseNewOrderLog;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\service\HousePropertyDigitService;
use app\community\model\db\HouseVillage;
use app\community\model\service\HouseVillageUserVacancyService;
use app\consts\newChargeConst;

class HouseNewChargePrepaidService{


    protected $HouseNewChargePrepaid;
    protected $HouseNewChargeRule;
    protected $HouseNewOrderLog;

    public $cycle=[
        array(
            'key'=>1,
            'value'=>1
        ),
        array(
            'key'=>2,
            'value'=>3
        ),
        array(
            'key'=>3,
            'value'=>6
        ),
        array(
            'key'=>4,
            'value'=>9
        ),
        array(
            'key'=>5,
            'value'=>12
        ),
        array(
            'key'=>6,
            'value'=>15
        ),
        array(
            'key'=>7,
            'value'=>18
        ),
        array(
            'key'=>8,
            'value'=>24
        ),
        array(
            'key'=>9,
            'value'=>36
        ),
        array(
            'key'=>10,
            'value'=>48
        ),
        array(
            'key'=>11,
            'value'=>'自定义'
        )
    ];

    public $give_cycle=[
        array(
            'key'=>1,
            'value'=>1
        ),
        array(
            'key'=>2,
            'value'=>3
        ),
        array(
            'key'=>3,
            'value'=>5
        ),
        array(
            'key'=>4,
            'value'=>9
        ),
        array(
            'key'=>5,
            'value'=>12
        ),
        array(
            'key'=>6,
            'value'=>15
        ),
        array(
            'key'=>7,
            'value'=>18
        ),
        array(
            'key'=>8,
            'value'=>24
        ),
        array(
            'key'=>9,
            'value'=>36
        ),
        array(
            'key'=>10,
            'value'=>48
        ),
        array(
            'key'=>11,
            'value'=>'自定义'
        )
    ];

    public $type=[
        array(
            'key'=>1,
            'value'=>'折扣'
        ),
        array(
            'key'=>2,
            'value'=>'赠送时间'
        ),
        array(
            'key'=>3,
            'value'=>'自定义文本'
        ),
        array(
            'key'=>4,
            'value'=>'无优惠'
        ),
    ];

    public function __construct()
    {
        $this->HouseNewChargePrepaid =  new HouseNewChargePrepaid();
        $this->HouseNewChargeRule =  new HouseNewChargeRule();
        $this->HouseNewOrderLog = new HouseNewOrderLog();
    }

    //todo 获取收费标准参数
    public function getPrepaidCycle($charge_rule_id){
        $dbHouseNewChargeRule=$this->HouseNewChargeRule->getOne(['id'=>$charge_rule_id,'status'=>1],'cyclicity_set');
        $cycle=$this->cycle;
        if($dbHouseNewChargeRule){
            $cyclicity_set=$dbHouseNewChargeRule['cyclicity_set'];
            if(intval($cyclicity_set) > 0){
                foreach ($cycle as $k=>$v){
                    if(is_numeric($v['value']) && $v['value'] > $cyclicity_set){
                        unset($cycle[$k]);
                    }
                }
            }
        }
        return [
            'cycle'=>$cycle,
            'give_cycle'=>$this->give_cycle,
            'type'=>$this->type,
        ];
    }

    /**
     *预缴周期列表
     * @author: liukezhu
     * @date : 2021/6/11
     * @param $param
     * @return mixed
     */
    public function getList($param){
        $limit = isset($param['limit']) && $param['limit'] ? $param['limit'] : 8;
        $page = isset($param['page']) && $param['page'] ? $param['page'] : 0;
        $where[]=[ 'village_id','=',$param['from_id']];
        if(isset($param['type']) && $param['type'] != 'del_detail'){
            $where[]=['status', 'in', '1,2'];
        }
        $where[]=['charge_rule_id', '=', $param['charge_rule_id']];
        $list = $this->HouseNewChargePrepaid->getList($where,'id,cycle,status,add_time,type,rate,custom_txt,give_cycle_type','id desc',$page,$limit);
        if($list){
            $typeTxt =function($param){
                switch ((int)$param['type']){
                    case 1 :
                        return '折扣（'.$param['rate'].'%)';
                    case 2:
                        return '赠送周期（'.$param['give_cycle_type'].'）';
                    case 3:
                        return '自定义（'.$param['custom_txt'].'）';
                    case 4:
                        return '无优惠';
                }
            };
            foreach ($list as &$v){
                $v['add_time']=date('Y-m-d',$v['add_time']);
                $v['type_txt'] = $typeTxt($v);
            }
            unset($v);
        }
        $count = $this->HouseNewChargePrepaid->getCount($where);
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }

    /**
     * 预缴周期列表
     * @author lijie
     * @date_time 2021/06/25
     * @param array $where
     * @param bool $field
     * @return mixed
     */
    public function getLists($where=[],$field=true)
    {
        $data = $this->HouseNewChargePrepaid->getList($where,$field);
        if($data){
            foreach ($data as $k=>$v){
                if($v['type'] == 1){
                    $data[$k]['diy_content'] = '折扣率'.$v['rate'].'%';
                }elseif($v['type'] == 2){
                    $data[$k]['diy_content'] = '送'.$v['give_cycle_type'].'个月';
                }elseif ($v['type'] == 3){
                    $data[$k]['diy_content'] = $v['custom_txt'];
                }else{
                    $data[$k]['diy_content'] = '无优惠';
                }
            }
        }
        return $data;
    }

    public function getPrepaidList($where=[],$field=true,$service_end_time=0,$not_house_rate= 1,$custom_value=1,$village_id=0)
    {
        if (isset($where['room_id'])) {
            $whereRoomId = $where['room_id'];
            unset($where['room_id']);
        }
        if (isset($where['position_id'])) {
            $wherePositionId = $where['position_id'];
            unset($where['position_id']);
        }
        $data = $this->HouseNewChargePrepaid->getLists($where,$field);
        if ($data && !is_array($data)) {
            $data = $data->toArray();
        }
        if($data){
            $db_house_property_digit_service = new HousePropertyDigitService();
            if($village_id){
                $db_house_village = new HouseVillage();
                $village_info = $db_house_village->getOne($village_id,'property_id');

                $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$village_info['property_id']]);
            }else{
                $digit_info = [];
            }
            $db_house_new_charge_standard_bind = new HouseNewChargeStandardBind();
            $service_house_new_charge_rule     = new HouseNewChargeRuleService();
            foreach ($data as $k=>$v){
                $whereBind = [
                    'rule_id'    =>$v['charge_rule_id'],
                    'project_id' =>$v['charge_project_id']
                ];
                if (isset($whereRoomId) && $whereRoomId) {
                    $whereBind['vacancy_id'] = $whereRoomId;
                }
                if (isset($wherePositionId) && $wherePositionId) {
                    $whereBind['position_id'] = $wherePositionId;
                }
                $bind_info = $db_house_new_charge_standard_bind->getOne($whereBind,'custom_value, vacancy_id, position_id');
                if ($bind_info && !is_array($bind_info)) {
                    $bind_info = $bind_info->toArray();
                }
                $bind_type = 0;
                $id        = 0;
                if(isset($bind_info['vacancy_id']) && $bind_info['vacancy_id']){
                    $bind_type = 1;
                    $id        = $bind_info['vacancy_id'];
                }elseif(isset($bind_info['position_id']) && $bind_info['position_id']){
                    $bind_type = 2;
                    $id        = $bind_info['position_id'];
                }
                
                if(isset($bind_type) && $bind_type && $v['fees_type'] == newChargeConst::FEES_TYPE_NUMBER_OF_PARKING_SPACES) {
                    $ruleHasParkNumInfo = $service_house_new_charge_rule->getRuleHasParkNumInfo($id, $v['charge_rule_id'], $bind_type, $v);
                    if (empty($ruleHasParkNumInfo) || !isset($ruleHasParkNumInfo['parkingNum']) || intval($ruleHasParkNumInfo['parkingNum']) <= 0) {
                        unset($data[$k]);
                        continue;
                    }
                    $parkingNum  = $ruleHasParkNumInfo['parkingNum'];
                } else {
                    $parkingNum  = 1;
                }
                
                if($v['type'] == 1){
                    if($v['fees_type'] == 1){
                        $data[$k]['prepaid_money'] = $v['charge_price']*$v['rate']/100*$v['r_rule_rate']*$v['cycle']*$not_house_rate*$custom_value;
                    }else{
                        $bind_info['custom_value'] = $bind_info['custom_value']?$bind_info['custom_value']:1;
                        $data[$k]['prepaid_money'] = $v['charge_price']*$v['rate']/100*$v['r_rule_rate']*$v['cycle']*$not_house_rate*$custom_value;
                    }
                    $data[$k]['diy_content'] = '折扣率'.$v['rate'].'%';
                }elseif($v['type'] == 2){
                    if($v['fees_type'] == 1){
                        $data[$k]['prepaid_money'] = $v['charge_price']*$v['r_rule_rate']*$v['cycle']*$not_house_rate*$custom_value;
                    }else{
                        $bind_info['custom_value'] = $bind_info['custom_value']?$bind_info['custom_value']:1;
                        $data[$k]['prepaid_money'] = $v['charge_price']*$v['r_rule_rate']*$v['cycle']*$not_house_rate*$custom_value;
                    }
                    if($v['bill_create_set'] == 1){
                        $data[$k]['diy_content'] = '送'.$v['give_cycle_type'].'天';
                    }elseif($v['bill_create_set'] == 2){
                        $data[$k]['diy_content'] = '送'.$v['give_cycle_type'].'个月';
                    }else{
                        $data[$k]['diy_content'] = '送'.$v['give_cycle_type'].'年';
                    }
                }elseif ($v['type'] == 3){
                    if($v['fees_type'] == 1){
                        $data[$k]['prepaid_money'] = $v['charge_price']*$v['r_rule_rate']*$v['cycle']*$not_house_rate*$custom_value;
                    }else{
                        $bind_info['custom_value'] = $bind_info['custom_value']?$bind_info['custom_value']:1;
                        $data[$k]['prepaid_money'] = $v['charge_price']*$v['r_rule_rate']*$v['cycle']*$not_house_rate*$custom_value;
                    }
                    $data[$k]['diy_content'] = $v['custom_txt'];
                }else{
                    if($v['fees_type'] == 1){
                        $data[$k]['prepaid_money'] = $v['charge_price']*$v['r_rule_rate']*$v['cycle']*$not_house_rate*$custom_value;
                    }else{
                        $bind_info['custom_value'] = $bind_info['custom_value']?$bind_info['custom_value']:1;
                        $data[$k]['prepaid_money'] = $v['charge_price']*$v['r_rule_rate']*$v['cycle']*$not_house_rate*$custom_value;
                    }
                    $data[$k]['diy_content'] = '无优惠';
                }
                if($v['bill_create_set'] == 1){
                    $data[$k]['cycle_txt'] = $v['cycle'].'天';
                }elseif($v['bill_create_set'] == 2){
                    $data[$k]['cycle_txt'] = $v['cycle'].'个月';
                }else{
                    $data[$k]['cycle_txt'] = $v['cycle'].'年';
                }
                if($service_end_time){
                    $cycle = $v['cycle']+$v['give_cycle_type'];
                    if($v['bill_create_set'] == 1){
                        $data[$k]['service_end_time'] = date("Y-m-d",strtotime("+$cycle day",$service_end_time));
                    }elseif($v['bill_create_set'] == 2){
                        $data[$k]['service_end_time'] = date("Y-m",strtotime("+$cycle month",$service_end_time));
                    }else{
                        $data[$k]['service_end_time'] = date("Y",strtotime("+$cycle year",$service_end_time));
                    }
                }
                $rule_digit = -1;
                if (isset($v['rule_digit']) && $v['rule_digit'] > -1 && $v['rule_digit'] < 5) {
                    $rule_digit = $v['rule_digit'];
                } else {
                    $onerule_digit = $db_house_property_digit_service->get_onerule_digit(['id' => $v['charge_rule_id']], 'id,rule_digit');
                    if (!empty($onerule_digit) && $onerule_digit['rule_digit'] > -1 && $onerule_digit['rule_digit'] < 5) {
                        $rule_digit = $onerule_digit['rule_digit'];
                    }
                }
                if($rule_digit>-1){
                    if (!empty($digit_info)) {
                        $digit_info['meter_digit'] = $rule_digit;
                        $digit_info['other_digit'] = $rule_digit;
                    } else {
                        $digit_info = array('type' => 1);
                        $digit_info['meter_digit'] = $rule_digit;
                        $digit_info['other_digit'] = $rule_digit;
                    }
                }
                if($v['fees_type'] == newChargeConst::FEES_TYPE_NUMBER_OF_PARKING_SPACES && $parkingNum > 1) {
                    $data[$k]['prepaid_money'] = $data[$k]['prepaid_money'] * intval($parkingNum);
                }
                
                if(empty($digit_info)){
                    $data[$k]['prepaid_money'] = formatNumber($data[$k]['prepaid_money'],2,1);
                }else{
                    $digit_info['other_digit'] = ($digit_info['other_digit']>2 || empty($digit_info['other_digit'])) && $digit_info['other_digit']!=0?2:$digit_info['other_digit'];
                    $data[$k]['prepaid_money'] = formatNumber($data[$k]['prepaid_money'],$digit_info['other_digit'],$digit_info['type']);
                    $data[$k]['prepaid_money'] =formatNumber($data[$k]['prepaid_money'] , 2, 1);
                }
            }
        }
        if($data){
            $data=array_values($data);
        }
        return $data;
    }

    public function getGivenPrepaid($where=[],$field=true,$service_end_time=0,$give_cycle,$pigcms_id=0,$order_type='')
    {
        $data = $this->HouseNewChargePrepaid->getLists($where,$field,'pre.cycle DESC');
        if($data){
            if (!is_array($data)) {
                $data = $data->toArray();
            }
            $service_house_village_user_bind = new HouseVillageUserBindService();
            $service_house_new_charge_rule = new HouseNewChargeRuleService();
            $userBindInfo = $service_house_village_user_bind->getBindInfo(['pigcms_id'=>$pigcms_id],'vacancy_id,village_id');
            $db_house_village = new HouseVillage();
            $village_info = $db_house_village->getOne($userBindInfo['village_id'],'property_id');

            $db_house_property_digit_service = new HousePropertyDigitService();
            $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$village_info['property_id']]);
            if($order_type && $userBindInfo){
                $rel=$this->HouseNewOrderLog->getOne([
                    ['order_type','=',$order_type],
                    ['room_id','=',$userBindInfo['vacancy_id']]
                ],'id,service_end_time');
                if($rel){
                    $service_end_time=strtotime(date('Y-m-d',$rel['service_end_time']));
                }
            }
            foreach ($data as $k=>$v){
                if($userBindInfo){
                    $room_id = $userBindInfo['vacancy_id'];
                    $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
                    $whereArrTmp=array();
                    $whereArrTmp[]=array('pigcms_id','=',$room_id);
                    $whereArrTmp[]=array('user_status','=',2);  // 2未入住
                    $whereArrTmp[]=array('status','in',[1,2,3]);
                    $whereArrTmp[]=array('is_del','=',0);
                    $vacancy_info = $service_house_village_user_vacancy->getUserVacancyInfo($whereArrTmp,'user_status');
                    $not_house_rate = 1;
                    if($vacancy_info && !$vacancy_info->isEmpty()){
                       // $vacancy_info = $vacancy_info->toArray();
                        if(!empty($vacancy_info)){
                            $not_house_rate = $v['not_house_rate']/100;
                        }
                    }
                    $projectBindInfo = $service_house_new_charge_rule->getBindDetail(['project_id'=>$v['charge_project_id'],'rule_id'=>$v['charge_rule_id'],'vacancy_id'=>$room_id]);
                    if(isset($projectBindInfo) && !empty($projectBindInfo)){
                        $custom_value = $projectBindInfo['custom_value'];
                    }else{
                        $custom_value = 1;
                    }
                    $bind_type = 0;
                    $id        = 0;
                    if(isset($projectBindInfo['vacancy_id']) && $projectBindInfo['vacancy_id']){
                        $bind_type = 1;
                        $id        = $projectBindInfo['vacancy_id'];
                    }elseif(isset($projectBindInfo['position_id']) && $projectBindInfo['position_id']){
                        $bind_type = 2;
                        $id        = $projectBindInfo['position_id'];
                    }
                    if(isset($bind_type) && $bind_type && $v['fees_type'] == newChargeConst::FEES_TYPE_NUMBER_OF_PARKING_SPACES) {
                        $ruleHasParkNumInfo = $service_house_new_charge_rule->getRuleHasParkNumInfo($id, $v['charge_rule_id'], $bind_type, $v);
                        if (empty($ruleHasParkNumInfo) || !isset($ruleHasParkNumInfo['parkingNum']) || intval($ruleHasParkNumInfo['parkingNum']) <= 0) {
                            continue;
                        }
                        $parkingNum  = $ruleHasParkNumInfo['parkingNum'];
                    } else {
                        $parkingNum  = 1;
                    }
                }else{
                    $not_house_rate = 1;
                    $custom_value = 1;
                }
                if($v['type'] == 1){
                    if($v['fees_type'] == 1){
                        $data[$k]['prepaid_money'] = $v['charge_price']*$v['rate']/100*$v['r_rule_rate']*$give_cycle*$not_house_rate;
                    }else{
                        $data[$k]['prepaid_money'] = $v['charge_price']*$v['rate']/100*$v['r_rule_rate']*$give_cycle*$not_house_rate*$custom_value;
                    }
                    $data[$k]['diy_content'] = '折扣率'.$v['rate'].'%';
                }elseif($v['type'] == 2){
                    if($v['fees_type'] == 1){
                        $data[$k]['prepaid_money'] = $v['charge_price']*$v['r_rule_rate']*$give_cycle;
                    }else{
                        $data[$k]['prepaid_money'] = $v['charge_price']*$v['r_rule_rate']*$give_cycle*$custom_value;
                    }
                    if($v['bill_create_set'] == 1){
                        $data[$k]['diy_content'] = '送'.$v['give_cycle_type'].'天';
                    }elseif($v['bill_create_set'] == 2){
                        $data[$k]['diy_content'] = '送'.$v['give_cycle_type'].'个月';
                    }else{
                        $data[$k]['diy_content'] = '送'.$v['give_cycle_type'].'年';
                    }
                }elseif ($v['type'] == 3){
                    if($v['fees_type'] == 1){
                        $data[$k]['prepaid_money'] = $v['charge_price']*$v['r_rule_rate']*$give_cycle;
                    }else{
                        $data[$k]['prepaid_money'] = $v['charge_price']*$v['r_rule_rate']*$give_cycle*$custom_value;
                    }
                    $data[$k]['diy_content'] = $v['custom_txt'];
                }else{
                    if($v['fees_type'] == 1){
                        $data[$k]['prepaid_money'] = $v['charge_price']*$v['r_rule_rate']*$give_cycle;
                    }else{
                        $data[$k]['prepaid_money'] = $v['charge_price']*$v['r_rule_rate']*$give_cycle*$custom_value;
                    }
                    $data[$k]['diy_content'] = '无优惠';
                }
                if($v['bill_create_set'] == 1){
                    $data[$k]['cycle_txt'] = $give_cycle.'天';
                }elseif($v['bill_create_set'] == 2){
                    $data[$k]['cycle_txt'] = $give_cycle.'个月';
                }else{
                    $data[$k]['cycle_txt'] = $give_cycle.'年';
                }
                if(!$service_end_time)
                    $service_end_time = time();
                if($service_end_time){
                    if($v['bill_create_set'] == 1){
                        $data[$k]['service_end_time'] = date("Y-m-d",strtotime("+$give_cycle day",$service_end_time));
                    }elseif($v['bill_create_set'] == 2){
                        $tt=strtotime("+$give_cycle month",$service_end_time)-1;
                        $data[$k]['service_end_time'] = date("Y-m",$tt);
                    }else{
                        $data[$k]['service_end_time'] = date("Y",strtotime("+$give_cycle year",$service_end_time));
                    }
                }
                $rule_digit = -1;
                if (isset($v['rule_digit']) && $v['rule_digit'] > -1 && $v['rule_digit'] < 5) {
                    $rule_digit = $v['rule_digit'];
                } else {
                    $onerule_digit = $db_house_property_digit_service->get_onerule_digit(['id' => $v['charge_rule_id']], 'id,rule_digit');
                    if (!empty($onerule_digit) && $onerule_digit['rule_digit'] > -1 && $onerule_digit['rule_digit'] < 5) {
                        $rule_digit = $onerule_digit['rule_digit'];
                    }
                }
                if($rule_digit>-1){
                    if (!empty($digit_info)) {
                        $digit_info['meter_digit'] = $rule_digit;
                        $digit_info['other_digit'] = $rule_digit;
                    } else {
                        $digit_info = array('type' => 1);
                        $digit_info['meter_digit'] = $rule_digit;
                        $digit_info['other_digit'] = $rule_digit;
                    }
                }
                if($v['fees_type'] == newChargeConst::FEES_TYPE_NUMBER_OF_PARKING_SPACES && isset($parkingNum) && $parkingNum > 1) {
                    $data[$k]['prepaid_money'] = $data[$k]['prepaid_money'] * intval($parkingNum);
                }
                if(empty($digit_info)){
                    $data[$k]['prepaid_money'] = formatNumber($data[$k]['prepaid_money'],2,1);
                }else{
                    $digit_info['other_digit'] = ($digit_info['other_digit']>2 || empty($digit_info['other_digit'])) && $digit_info['other_digit']!=0?2:$digit_info['other_digit'];
                    $data[$k]['prepaid_money'] = formatNumber($data[$k]['prepaid_money'],$digit_info['other_digit'],$digit_info['type']);
                    $data[$k]['prepaid_money'] =formatNumber($data[$k]['prepaid_money'] , 2, 1);
                }
            }
            $giveInfo = [];
            foreach ($data as $k=>$v){
                if($v['cycle'] > $give_cycle){
                    continue;
                }
                if($v['cycle'] = $give_cycle){
                    $giveInfo = $v;
                    break;
                }
                if($v['cycle'] < $give_cycle){
                    $giveInfo = $v;
                    break;
                }
            }
        }
        return $giveInfo;
    }

    //todo 返回预缴周期参数
    public function getCycle($cycle,$type=1,$value=0){
        foreach ($this->cycle as $v){
            if($type == 1){
                if($v['key'] == $cycle){
                    $value=$v['value'];
                    break;
                }
            }else{
                if($v['value'] == $cycle){
                    $value=$v['key'];
                    break;
                }
            }
        }
        return $value;
    }

    public function getGiveCycle($cycle,$type=1,$value=0){
        foreach ($this->give_cycle as $v){
            if($type == 1){
                if($v['key'] == $cycle){
                    $value=$v['value'];
                    break;
                }
            }else{
                if($v['value'] == $cycle){
                    $value=$v['key'];
                    break;
                }
            }
        }
        return $value;
    }

    /**
     *添加预缴周期
     * @author: liukezhu
     * @date : 2021/6/11
     */
    public function add($param){
        $dbHouseNewChargeRule=$this->HouseNewChargeRule->getOne(['id'=>$param['charge_rule_id'],'status'=>1]);
        if(empty($dbHouseNewChargeRule) && (intval($param['charge_rule_id']) > 0)){
            throw new \think\Exception("该收费标准不存在");
        }
        $give_cycle_type=0;
        $cycle=$this->getCycle($param['cycle']);
        if(empty($cycle)){
            throw new \think\Exception("该预缴周期不存在");
        }
        //todo 预缴周期选择
        if($param['cycle'] == 11){
            if(empty($param['cycle_param'])){
                throw new \think\Exception("该输入自定义预缴周期");
            }
            if(intval($dbHouseNewChargeRule['cyclicity_set']) > 0){
                if($param['cycle_param'] > intval($dbHouseNewChargeRule['cyclicity_set'])){
                    throw new \think\Exception("自定义预缴周期不可大于收费标准周期性费用");
                }
            }
            $cycle=$param['cycle_param'];
            $param['cycle_param']=11;
        }
        else{
            if(intval($dbHouseNewChargeRule['cyclicity_set']) > 0){
                if($cycle > intval($dbHouseNewChargeRule['cyclicity_set'])){
                    throw new \think\Exception("预缴周期不可大于收费标准周期性费用");
                }
            }
            $param['cycle_param']=0;
        }
        //todo 赠送周期选择
        if($param['type'] == 2){
            if($param['give_cycle_param'] == 11){
                if(empty($param['give_cycle_txt'])){
                    throw new \think\Exception("该输入自定义赠送周期");
                }
                $give_cycle_type=$param['give_cycle_txt'];
                if($give_cycle_type > $cycle){
                    throw new \think\Exception("自定义赠送周期不可大于预缴周期");
                }
            }
            else{
                if(empty($param['give_cycle_param'])){
                    throw new \think\Exception("该选择赠送周期");
                }
                $give_cycle='';
                foreach ($this->give_cycle as $v){
                    if($v['key'] == $param['give_cycle_param']){
                        $give_cycle=$v['value'];
                    }
                }
                $give_cycle_type=$give_cycle;
                if($give_cycle_type > $cycle){
                    throw new \think\Exception("赠送周期不可大于预缴周期");
                }
            }
        }
        $param['give_cycle_type']=$give_cycle_type;
        $param['cycle']=$cycle;
        $param['add_time']=time();
        return  $this->HouseNewChargePrepaid->addFind($param);
    }


    /**
     * 编辑预缴周期
     * @author: liukezhu
     * @date : 2021/6/11
     * @param $param
     * @param bool $id
     * @return \app\community\model\db\WorkMsgAuditInfo|bool
     * @throws \think\Exception
     */
    public function edit($param,$id=false){
        $list= $this->HouseNewChargePrepaid->getOne([
            'id'=>$param['id'],
            'village_id'=>$param['village_id']
        ],'id,charge_rule_id,cycle,type,rate,give_cycle_param,cycle_param,give_cycle_txt,custom_txt,status');
        if(!$list){
            return false;
        }
        if($id){
            $dbHouseNewChargeRule=$this->HouseNewChargeRule->getOne(['id'=>$list['charge_rule_id'],'status'=>1]);
            if(empty($dbHouseNewChargeRule) && (intval($list['charge_rule_id']) > 0)){
                throw new \think\Exception("该收费标准不存在");
            }
            $give_cycle_type=0;
            if (empty($param['cycle'])){
                throw new \think\Exception("请选择预缴周期!");
            }
            $cycle=$this->getCycle($param['cycle']);
            if(empty($cycle)){
                throw new \think\Exception("该预缴周期不存在");
            }
            //todo 预缴周期选择
            if($param['cycle'] == 11){
                if(empty($param['cycle_param'])){
                    throw new \think\Exception("该输入自定义预缴周期");
                }
                if(intval($dbHouseNewChargeRule['cyclicity_set']) > 0){
                    if($param['cycle_param'] > intval($dbHouseNewChargeRule['cyclicity_set'])){
                        throw new \think\Exception("自定义预缴周期不可大于收费标准周期性费用");
                    }
                }
                $cycle=$param['cycle_param'];
                $cycle_param=11;
            }
            else{
                if(intval($dbHouseNewChargeRule['cyclicity_set']) > 0){
                    if($cycle > intval($dbHouseNewChargeRule['cyclicity_set'])){
                        throw new \think\Exception("预缴周期不可大于收费标准周期性费用");
                    }
                }
                $cycle_param=0;
            }
            //todo 赠送周期选择
            if($param['type'] == 2){
                if($param['give_cycle_param'] == 11){
                    if(empty($param['give_cycle_txt'])){
                        throw new \think\Exception("该输入自定义赠送周期");
                    }
                    if($param['give_cycle_txt'] > $cycle){
                        throw new \think\Exception("自定义赠送周期不可大于预缴周期");
                    }
                    $give_cycle_type=$param['give_cycle_txt'];
                }
                else{
                    if(empty($param['give_cycle_param'])){
                        throw new \think\Exception("该选择赠送周期");
                    }
                    $give_cycle='';
                    foreach ($this->give_cycle as $v){
                        if($v['key'] == $param['give_cycle_param']){
                            $give_cycle=$v['value'];
                        }
                    }
                    if($give_cycle > $cycle){
                        throw new \think\Exception("赠送周期不可大于预缴周期");
                    }
                    $give_cycle_type=$give_cycle;
                }
            }
            $data=array(
                'type'=>$param['type'],
                'rate'=>$param['rate'],
                'cycle'=>$cycle,
                'cycle_param'=>$cycle_param,
                'give_cycle_type'=>$give_cycle_type,
                'give_cycle_param'=>$param['give_cycle_param'],
                'give_cycle_txt'=>$param['give_cycle_txt'],
                'custom_txt'=>$param['custom_txt'],
                'status'=>$param['status'],
                'update_time'=>time(),
            );
            $list=$this->HouseNewChargePrepaid->editFind(['id'=>$id,'village_id'=>$param['village_id']],$data);
        }
        else{
            $list['bill_create_set']=(new HouseNewChargeRuleService())->getChargeValidType($list['charge_rule_id']);
            $cycle=$this->getCycle($list['cycle'],2,'');
            //todo 预缴周期选择
            if($list['cycle_param'] == 11){
                $list['cycle_param']=$list['cycle'];
                $list['cycle']=11;
                $list['cycle_txt_status']=true;
            }else{
                $list['cycle']=$cycle;
                $list['cycle_param']='';
                $list['cycle_txt_status']=false;
            }
            //todo 赠送周期选择
            $list['give_cycle_txt_status']=($list['give_cycle_param'] == 11) ? true : false;
            $list['give_cycle_txt']=empty($list['give_cycle_txt']) ? '' : $list['give_cycle_txt'];
            $list['is_disabled']=($list['status'] > 1) ? true : false;
        }
        return $list;
    }

    /**
     *软删除
     * @author: liukezhu
     * @date : 2021/6/11
     * @param $param
     * @return bool
     */
    public function del($param){
        $where[] = ['id', '=', $param['id']];
        $where[] = ['village_id', '=', $param['village_id']];
        $where[] = ['status', '<>', 4];
        $list= $this->HouseNewChargePrepaid->getOne($where,'id,charge_rule_id,cycle,type,rate,give_cycle_param,give_cycle_txt,custom_txt,status');
        if(!$list){
            return false;
        }
        $data=array(
            'status'=>4,
            'update_time'=>time(),
        );
      return  $this->HouseNewChargePrepaid->editFind(['id'=>$list['id']],$data);
    }

    /**
     * 预缴周期详情
     * @author lijie
     * @date_time 2021/06/25
     * @param array $where
     * @param bool $field
     * @param string $type
     * @param int $from_id
     * @return mixed
     */
    public function getPrepaidDetail($where=[],$field=true,$type='',$from_id=0)
    {
        $data = $this->HouseNewChargePrepaid->getDetail($where,$field);
        if ($data && !is_array($data)) {
            $data = $data->toArray();
        }
        $service_house_new_charge_rule = new HouseNewChargeRuleService();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $db_house_property_digit_service = new HousePropertyDigitService();
        $housesize=0;
        if($type == 'room'){
            $bind_type = 1;
            /*
            $condition1 = [];
            $condition1[] = ['vacancy_id','=',$from_id];
            $condition1[] = ['status','=',1];
            $condition1[] = ['type','in',[0,3,1,2]];
            $bind_list = $service_house_village_user_bind->getList($condition1,true);
            */
            $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
            $whereArrTmp=array();
            $whereArrTmp[]=array('pigcms_id','=',$from_id);
            $whereArrTmp[]=array('status','in',[1,2,3]);
            $whereArrTmp[]=array('is_del','=',0);
            $room_vacancy1=$service_house_village_user_vacancy->getUserVacancyInfo($whereArrTmp);
            if($room_vacancy1 && !$room_vacancy1->isEmpty()){
                $room_vacancy1 = $room_vacancy1->toArray();
                if(!empty($room_vacancy1)){
                    $housesize = $room_vacancy1['housesize'];
                }
            }
            $whereArrTmp[]=array('user_status','=',2);  // 2未入住
            $room_vacancy=$service_house_village_user_vacancy->getUserVacancyInfo($whereArrTmp);
            $not_house_rate = 100;
            if($room_vacancy && !$room_vacancy->isEmpty()){
                $room_vacancy = $room_vacancy->toArray();
                if(!empty($room_vacancy)){
                    $not_house_rate = $data['not_house_rate'];
                }
            }
            $projectBindInfo = $service_house_new_charge_rule->getBindDetail(['project_id'=>$data['charge_project_id'],'rule_id'=>$data['charge_rule_id'],'vacancy_id'=>$from_id,'is_del'=>1]);
        }else{
            $bind_type = 2;
            $service_house_village_parking = new HouseVillageParkingService();
            $carInfo = $service_house_village_parking->getCar(['car_position_id'=>$from_id]);
            if($carInfo){
                $carInfo = $carInfo->toArray();
            }
            if(empty($carInfo)) {
                $not_house_rate = $data['not_house_rate'];
            }
            else {
                $not_house_rate = 100;
            }
        }
        if($type == 'position'){
            $projectBindInfo = $service_house_new_charge_rule->getBindDetail(['project_id'=>$data['charge_project_id'],'rule_id'=>$data['charge_rule_id'],'position_id'=>$from_id]);
        }
        if (isset($projectBindInfo) && $projectBindInfo && !is_array($projectBindInfo)) {
            $projectBindInfo = $projectBindInfo->toArray();
        } else {
            $projectBindInfo = [];
        }
        
        if(isset($data['fees_type']) && $data['fees_type'] == newChargeConst::FEES_TYPE_NUMBER_OF_PARKING_SPACES) {
            $ruleHasParkNumInfo = $service_house_new_charge_rule->getRuleHasParkNumInfo($from_id, $data['charge_rule_id'], $bind_type);
            if (empty($ruleHasParkNumInfo) || !isset($ruleHasParkNumInfo['parkingNum']) || intval($ruleHasParkNumInfo['parkingNum']) <= 0) {
                return [];
            }
            $parkingNum  = $ruleHasParkNumInfo['parkingNum'];
        } else {
            $parkingNum  = 1;
        }
        if(isset($projectBindInfo) && !empty($projectBindInfo['custom_value'])){
            $custom_value = $projectBindInfo['custom_value'];
        }else{
            $custom_value = 1;
        }
        if ($data['charge_type']=='property'&&$custom_value<=1){
            $data['custom_value']=$custom_value=$housesize;
        }
        if(isset($data['unit_gage_type']) && $data['unit_gage_type']== 3 && $projectBindInfo['position_id']) {
            $positionSize = (new HouseVillageParkingPosition())->where(['position_id' =>$projectBindInfo['position_id']])->value('position_area');
            if( $positionSize>0){
                $custom_value=$positionSize;
                $data['custom_value']=$positionSize;
            }
        }
        if($data['type'] == 1){
            if($data['fees_type'] == 1){
                $data['prepaid_money'] = $data['charge_price']*$not_house_rate/100*$data['rate']/100*$data['r_rate']*$data['cycle'];
            }else{
                $data['prepaid_money'] = $data['charge_price']*$not_house_rate/100*$data['rate']/100*$data['r_rate']*$data['cycle']*$custom_value;
            }
            $data['diy_content'] = '折扣率'.$data['rate'].'%';
        }elseif($data['type'] == 2){
            if($data['fees_type'] == 1){
                $data['prepaid_money'] = $data['charge_price']*$not_house_rate/100*$data['r_rate']*$data['cycle'];
            }else{
                $data['prepaid_money'] = $data['charge_price']*$not_house_rate/100*$data['r_rate']*$data['cycle']*$custom_value;
            }
            $data['diy_content'] = '赠送'.$data['give_cycle_type'].'周期';
        }elseif ($data['type'] == 3){
            if($data['fees_type'] == 1){
                $data['prepaid_money'] = $data['charge_price']*$not_house_rate/100*$data['r_rate']*$data['cycle'];
            }else{
                $data['prepaid_money'] = $data['charge_price']*$not_house_rate/100*$data['r_rate']*$data['cycle']*$custom_value;
            }
            $data['diy_content'] = $data['custom_txt'];
        }else{
            if($data['fees_type'] == 1){
                $data['prepaid_money'] = $data['charge_price']*$not_house_rate/100*$data['r_rate']*$data['cycle'];
            }else{
                $data['prepaid_money'] = $data['charge_price']*$not_house_rate/100*$data['r_rate']*$data['cycle']*$custom_value;
            }
            $data['diy_content'] = '无优惠';
        }
        if(isset($data['village_id'])){
            $service_house_village = new HouseVillageService();
            $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$data['village_id']],'property_id');
            $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$village_info['property_id']]);
        }else{
            $digit_info = [];
        }
        $rule_digit = -1;
        if (isset($data['rule_digit']) && $data['rule_digit'] > -1 && $data['rule_digit'] < 5) {
            $rule_digit = $data['rule_digit'];
        } else {
            $onerule_digit = $db_house_property_digit_service->get_onerule_digit(['id' => $data['charge_rule_id']], 'id,rule_digit');
            if (!empty($onerule_digit) && $onerule_digit['rule_digit'] > -1 && $onerule_digit['rule_digit'] < 5) {
                $rule_digit = $onerule_digit['rule_digit'];
            }
        }
        if($rule_digit>-1){
            if (!empty($digit_info)) {
                $digit_info['meter_digit'] = $rule_digit;
                $digit_info['other_digit'] = $rule_digit;
            } else {
                $digit_info = array('type' => 1);
                $digit_info['meter_digit'] = $rule_digit;
                $digit_info['other_digit'] = $rule_digit;
            }
        }
        if(isset($projectBindInfo['fees_type']) && $projectBindInfo['fees_type'] == newChargeConst::FEES_TYPE_NUMBER_OF_PARKING_SPACES && $parkingNum > 1) {
            $data['prepaid_money'] = $data['prepaid_money'] * intval($parkingNum);
        }
        if(empty($digit_info)){
            $data['prepaid_money'] = formatNumber($data['prepaid_money'],2,1);
        }else{
            if(isset($data['charge_type']) && ($data['charge_type'] == 'water' || $data['charge_type'] == 'electric' || $data['charge_type'] == 'gas')){
                $data['prepaid_money'] = formatNumber($data['prepaid_money'],$digit_info['meter_digit'],$digit_info['type']);
            }else{
                $data['prepaid_money'] = formatNumber($data['prepaid_money'],$digit_info['other_digit'],$digit_info['type']);
            }
            $data['prepaid_money'] =formatNumber($data['prepaid_money'] , 2, 1);
        }
        return $data;
    }
}