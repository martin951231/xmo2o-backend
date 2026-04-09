<?php
/**
 * @author : liukezhu
 * @date : 2021/6/11
 */

namespace app\community\model\service;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\db\HouseNewAutoOrderLog;
use app\community\model\db\HouseNewChargeNumber;
use app\community\model\db\HouseNewChargePrepaid;
use app\community\model\db\HouseNewChargeProject;
use app\community\model\db\HouseNewChargeRule;
use app\community\model\db\HouseNewChargeStandardBind;
use app\community\model\db\HouseNewOrderLog;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseNewPileCharge;
use app\community\model\db\HouseVillageBindPosition;
use app\community\model\db\HouseVillageInfo;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageLayer;
use app\community\model\db\HouseVillageParkCharge;
use app\community\model\db\HouseVillageParkConfig;
use app\community\model\db\HouseVillageParkingGarage;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\HouseNewChargePrepaidDiscount;
use app\community\model\service\HouseNewPayOrderService;
use app\consts\newChargeConst;
use app\traits\AddPayOrderHouseTraits;
use customization\customization;
use think\facade\Queue;
use app\traits\CommonLogTraits;
use app\traits\house\AdminLoginTraits;

class HouseNewChargeRuleService
{
    
    use AddPayOrderHouseTraits;
    use customization;
    use CommonLogTraits;
    use AdminLoginTraits;
    protected $HouseNewChargeRule;
    protected $HouseNewChargeProject;
    protected $HouseNewChargePrepaid;
    protected $HouseNewChargeStandardBind;
    protected $HouseNewChargePrepaidService;

    public $charge_time=[
        0=>['title'=>'15分钟','value'=>15],
        1=>['title'=>'30分钟','value'=>30],
        2=>['title'=>'45分钟','value'=>45],
        3=>['title'=>'1小时','value'=>60],
    ];
    public $pile_time=[
        0=>'00:00',
        1=>'00:30',
        2=>'01:00',
        3=>'01:30',
        4=>'02:00',
        5=>'02:30', 
        6=>'03:00',
        7=>'03:30',
        8=>'04:00',
        9=>'04:30',
        10=>'05:00',
        11=>'05:30',
        12=>'06:00',
        13=>'06:30',
        14=>'07:00',
        15=>'07:30',
        16=>'08:00',
        17=>'08:30',
        18=>'09:00',
        19=>'09:30',
        20=>'10:00',
        21=>'10:30',
        22=>'11:00',
        23=>'11:30',
        24=>'12:00',
        25=>'12:30',
        26=>'13:00',
        27=>'13:30',
        28=>'14:00',
        29=>'14:30',
        30=>'15:00',
        31=>'15:30',
        32=>'16:00',
        33=>'16:30',
        34=>'17:00',
        35=>'17:30',
        36=>'18:00',
        37=>'18:30',
        38=>'19:00',
        39=>'19:30',
        40=>'20:00',
        41=>'20:30',
        42=>'21:00',
        43=>'21:30',
        44=>'22:00',
        45=>'22:30',
        46=>'23:00',
        47=>'23:30',
        48=>'24:00',
    ];
    public $pile_time_txt=[
        '00:00'=>0,
        '00:30'=>1,
        '01:00'=>2, 
        '01:30'=>3,
        '02:00'=>4,
        '02:30'=>5,
        '03:00'=>6,
        '03:30'=>7,
        '04:00'=>8,
        '04:30'=>9,
        '05:00'=>10,
        '05:30'=>11,
        '06:00'=>12,
        '06:30'=>13,
        '07:00'=>14,
        '07:30'=>15,
        '08:00'=>16,
        '08:30'=>17,
        '09:00'=>18,
        '09:30'=>19,
        '10:00'=>20,
        '10:30'=>21,
        '11:00'=>22,
        '11:30'=>23,
        '12:00'=>24,
        '12:30'=>25,
        '13:00'=>26,
        '13:30'=>27,
        '14:00'=>28,
        '14:30'=>29,
        '15:00'=>30,
        '15:30'=>31,
        '16:00'=>32,
        '16:30'=>33,
        '17:00'=>34,
        '17:30'=>35,
        '18:00'=>36,
        '18:30'=>37,
        '19:00'=>38,
        '19:30'=>39,
        '20:00'=>40,
        '20:30'=>41,
        '21:00'=>42,
        '21:30'=>43,
        '22:00'=>44,
        '22:30'=>45,
        '23:00'=>46,
        '23:30'=>47,
        '24:00'=>48,
    ];
    public function __construct()
    {
        $this->HouseNewChargeRule = new HouseNewChargeRule();
        $this->HouseNewChargeProject = new HouseNewChargeProject();
        $this->HouseNewChargePrepaid = new HouseNewChargePrepaid();
        $this->HouseNewChargeStandardBind = new HouseNewChargeStandardBind();
        $this->HouseNewChargePrepaidService = new HouseNewChargePrepaidService();
    }


    /**
     *收费规则列表
     * @param $param
     * @return mixed
     * @author: liukezhu
     * @date : 2021/6/11
     */
    public function getList($param)
    {
        $HouseVillageParkConfig=new HouseVillageParkConfig();
        $data=array();
        $limit = isset($param['limit']) && $param['limit'] ? $param['limit'] : 8;
        $page = isset($param['page']) && $param['page'] ? $param['page'] : 0;
        $where=array();
        if (isset($param['villageIds'])) {
            $where[] = ['village_id', 'in', $param['villageIds']];
        } elseif(isset($param['from_id'])) {
            $where[] = ['village_id', '=', $param['from_id']];
        }elseif(isset($param['village_id']) && $param['village_id']>0) {
            $where[] = ['village_id', '=', $param['village_id']];
        }
        if(isset($param['charge_project_id']) && !empty($param['charge_project_id'])){
            if(is_array($param['charge_project_id'])){
                $where[] = ['charge_project_id', 'in', $param['charge_project_id']];
            }else{
                $where[] = ['charge_project_id', '=', $param['charge_project_id']];
            }
        }
        if(isset($param['subjectId']) && !empty($param['subjectId'])){
            $where[] = ['subject_id', '=', $param['subjectId']];
        }
        if (isset($param['keyword']) && !empty($param['keyword'])) {
            $where[] = ['charge_name', 'like', '%' . $param['keyword'] . '%'];
        }
        $where[] = ['status', '<>', 4];
        $order = 'id DESC';
        $selectdata=0;
        if(isset($param['type']) && $param['type'] == 'selectdata'){
            $selectdata=1;  //筛选项中数据
            $order = 'status ASC,id DESC';
            //$where[] = ['status', 'in', '1,2'];
        }else if(isset($param['type']) && $param['type'] == 'del'){
            $order = 'update_time DESC';
            $where[] = ['status', '=', 4];
        }else{
            $where[] = ['status', 'in', [1,2]];
        }
        $field = 'id,village_id,charge_project_id,charge_name,status,charge_valid_type,charge_valid_time,unit_price,rate,not_house_rate,fees_type,bill_create_set,bill_arrears_set,bill_type,is_prepaid,update_time';
        if($selectdata==1){
            $field = 'id,charge_name,status';
            $page=0;
        }
        $list = $this->HouseNewChargeRule->getList($where, $field, $order, $page, $limit);
        $not_project_rule_to_order_charge_type=array('electric','gas','water','park_new','pile','non_motor_vehicle','electricity_loss','qunuanfei','new_nuanqifei');
        if (!empty($list)) {
            $nowtime=time();
            if(!is_array($list) && is_object($list)){
                $list=$list->toArray();
            }
            $configCustomizationService= new ConfigCustomizationService();
            $isfuncKaidalaicnSpecial=$configCustomizationService->getfuncKaidalaicnSpecial();
            $isAihorseCustomized=$configCustomizationService->getAihorseCustomizedJudge();
            if($selectdata==1){
                foreach ($list as &$rv) {
                    if ($rv['status'] == 4) {
                        $rv['charge_name'] = $rv['charge_name'] . '(已删除)';
                    }
                }
                if($isfuncKaidalaicnSpecial) {
                    $whereArr = array(['village_id', '=', 0], ['subject_id', '=', 0], ['status', '=', 5], ['from_type', 'in', ['red_reversal', 'append_money']]);
                    if(isset($param['charge_project_id']) && !empty($param['charge_project_id'])){
                        if(is_array($param['charge_project_id'])){
                            $whereArr[] = ['charge_project_id', 'in', $param['charge_project_id']];
                        }else{
                            $whereArr[] = ['charge_project_id', '=', $param['charge_project_id']];
                        }
                    }
                    $ruleListObj = $this->HouseNewChargeRule->getList($whereArr, 'id,charge_name,status','id desc',0,10);
                    if($ruleListObj && !$ruleListObj->isEmpty()){
                        $ruleList=$ruleListObj->toArray();
                        foreach ($ruleList as $rvv){
                            $list[]=$rvv;
                        }
                    }
                }
                $data['list'] = $list;
                return $data;
            }
            $houseVillageDb= new HouseVillage();
            $villageInfoArr=array();
            foreach ($list as &$v) {
                $v['charge_valid_time'] = date('Y-m-d', $v['charge_valid_time']);
                $charge_valid_time=strtotime($v['charge_valid_time']);
                if (isset($v['update_time'])&&$v['update_time']) {
                    $v['update_time_txt'] = date('Y-m-d H:i:s',$v['update_time']);
                }
                if(isset($villageInfoArr[$v['village_id']])){
                    $v['village_name']=$villageInfoArr[$v['village_id']];
                }else{
                    $villageInfoObj=$houseVillageDb->getInfo(array('village_id'=>$v['village_id']),'village_name');
                    if($villageInfoObj && !$villageInfoObj->isEmpty() ){
                        $v['village_name']=$villageInfoObj['village_name'];
                        $villageInfoArr[$v['village_id']]=$villageInfoObj['village_name'];
                    }
                }

                /*switch ($v['charge_valid_type']) {
                    case 1:
                        //年月日
                        $v['charge_valid_time'] = date('Y-m-d', $v['charge_valid_time']);
                        break;
                    case 2:
                        //年月
                        $v['charge_valid_time'] = date('Y-m', $v['charge_valid_time']);
                        break;
                    case 3:
                        //年
                        $v['charge_valid_time'] = date('Y', $v['charge_valid_time']);
                        break;
                }*/
                $where_rule = [];
                $where_rule[] = ['r.id','=',$v['id']];
                $where_rule[] = ['r.charge_project_id','=',$v['charge_project_id']];
                $arr = ['water','electric','gas'];
                $charge_type = 'property';
                $v['charge_type']='';
                $v['project_type']=0;
                $v['project_type_str']='';
                // 获取上级信息
                $subject = $this->HouseNewChargeRule->getFind($where_rule,'c.charge_number_name,c.charge_type,p.type as project_type,p.name');
                if($subject && !$subject->isEmpty()){
                    $subject = $subject->toArray();
                    $v['charge_number_name'] = $subject['charge_number_name'];
                    $v['project_name'] = $subject['name'];
                    $v['charge_type'] =$subject['charge_type'];
                    $v['project_type'] =$subject['project_type'];
                    $v['project_type_str']=in_array($subject['charge_type'],['water','electric','gas']) ? '-' : (($subject['project_type'] != 2) ? "一次性费用" : "周期性费用");
                    $charge_type = $subject['charge_type'];
                }
                $v['rule_to_order_btn']=0;
                $v['project_rule_to_order_btn']=0;  //一次性项目的 手动生成账单
                if($this->hasMeijuWuyeCustomized() && in_array($v['charge_type'],array('public_water','public_electric')) && $charge_valid_time<$nowtime){
                    $v['rule_to_order_btn']=1;
                }elseif ($v['project_type']==1 && !in_array($v['charge_type'],$not_project_rule_to_order_charge_type) && $charge_valid_time<$nowtime){
                    $v['project_rule_to_order_btn']=1;
                }
                if($isAihorseCustomized && $v['charge_type']=='park_new' && $v['fees_type']==4){
                    $v['project_rule_to_order_btn']=1;
                }
                $v['fees_type_status'] = $v['fees_type'];
                $v['fees_type_txt']=1;
                if(in_array($charge_type,$arr)){
                    $v['rate'] = empty($v['rate']) ? '--' : $v['rate'].'倍';
                    $v['unit_price'] = empty($v['unit_price']) ? '--' : $v['unit_price'].'元';

                    $v['not_house_rate'] = empty($v['not_house_rate']) ? '100%' : $v['not_house_rate'].'%';
                    $v['fees_type'] = '--';
                    $v['bill_create_set'] = '--';
                    $v['bill_arrears_set'] = '--';
                    $v['bill_type'] = '--';
                    $v['is_prepaid'] = empty($v['is_prepaid']) ? '--' : ($v['is_prepaid'] == 1 ? '支持' : '不支持');
                }elseif($charge_type=='park_new'){
                    if($v['fees_type_status']==3){
                        $v['fees_type_txt']=0;
                    }
                    $v['rate'] = '--';
                    $v['not_house_rate'] = empty($v['not_house_rate']) ? '100%' : $v['not_house_rate'].'%';
                    $v['unit_price'] = '--';

                    $v['fees_type'] =  '单价计量单位';
                    $v['bill_create_set'] = '--';
                    $v['bill_arrears_set'] = '--';
                    $v['bill_type'] = '手动生成';
                    $v['is_prepaid'] = empty($v['is_prepaid']) ? '--' : ($v['is_prepaid'] == 1 ? '支持' : '不支持');

                }
                else{
                    $v['rate'] = '--';
                    $v['not_house_rate'] = empty($v['not_house_rate']) ? '100%' : $v['not_house_rate'].'%';
                    $v['unit_price'] = '--';
                    $fees_type = isset($v['fees_type']) ? $v['fees_type'] : '';
                    $v['fees_type'] = $this->getFeesTypeTxt($fees_type);
                    $v['bill_create_set'] = empty($v['bill_create_set']) ? '--' : ($v['bill_create_set'] == 1 ? '按日生成' : ($v['bill_create_set'] == 2 ? '按月生成' : '按年生成'));
                    $v['bill_arrears_set'] = empty($v['bill_arrears_set']) ? '--' : ($v['bill_arrears_set'] == 1 ? '预生成' : '后生成');
                    $v['bill_type'] = empty($v['bill_type']) ? '--' : ($v['bill_type'] == 1 ? '手动生成' : '自动生成');
                    $v['is_prepaid'] = empty($v['is_prepaid']) ? '--' : ($v['is_prepaid'] == 1 ? '支持' : '不支持');
                }
                if($v['charge_type']=='qrcode'){
                    $v['bill_create_set'] = '--';
                    $v['bill_arrears_set'] = '--';
                    $v['bill_type'] = '--';
                    $v['is_prepaid'] = '--';
                    $v['not_house_rate'] = '--';
                }
                if($v['charge_type']=='electricity_loss'){
                    $v['bill_create_set'] = '--';
                    $v['bill_arrears_set'] = '--';
                    $v['bill_type'] = '--';
                    $v['is_prepaid'] = '--';
                    $v['not_house_rate'] = '--';
                }
            }
            unset($v);
        }
        $count = $this->HouseNewChargeRule->getCount($where);
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }

    public function getLists($param, $field = true)
    {
        $where[] = ['status', 'in', '1,2'];
        $where[] = ['charge_project_id', '=', $param['charge_project_id']];
        $list = $this->HouseNewChargeRule->getList($param, $field, 0, 0);
        if($list){
            foreach ($list as &$v){
                if($v['fees_type'] == 2 && empty($v['unit_gage'])){
                    $v['fees_type'] = 1;
                }
                $v['charge_valid_time_txt'] = date("Y-m-d H:i:s",$v['charge_valid_time']);
            }
        }
        return $list;
    }

    /**
     * 获取收费标准列表
     * @author lijie
     * @date_time 2022/01/12
     * @param $param
     * @param bool $field
     * @return mixed
     */
    public function getRuleLists($param, $field = true)
    {
        $where[] = ['status', 'in', '1,2'];
        $where[] = ['charge_project_id', '=', $param['charge_project_id']];
        $list = $this->HouseNewChargeRule->getList($param, $field, 0, 0);
        if($list){
            foreach ($list as &$v){
                if($v['fees_type'] == 2 && empty($v['unit_gage'])){
                    $v['fees_type'] = 1;
                }
                $v['charge_valid_time_txt'] = date("Y-m-d H:i:s",$v['charge_valid_time']);
                //$v['bill_create_set'] = 1;
            }
        }
        return $list;
    }

    /**
     *返标准参数
     * @author: liukezhu
     * @date : 2021/6/12
     */
    public function ruleParam($param)
    {
        $where[] = ['p.village_id', '=', $param['village_id']];
        $where[] = ['p.id', '=', $param['charge_project_id']];
        if(isset($param['type']) && $param['type'] == 'del'){
            $where[] = ['p.status', 'in', '1,2,4'];
        }else{
            $where[] = ['p.status', 'in', '1,2'];
        }
        $list = $this->HouseNewChargeProject->getFind($where, 'p.id,p.name,p.type,p.subject_id,c.charge_type');
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        if (!$list) {
            throw new \think\Exception("该收费项目不存在");
        }
        $have_order_discount_disable=false;
        if (in_array($list['charge_type'], ['water', 'electric', 'gas','park_new','deposit_new','new_nuanqifei'])) {
            //水电燃
            $data = [
                'is_unit_price' => true,
                'is_rate' => true,
                'is_fees_type' => false,
                'is_charge_price' => false,
                'is_cyclicity_type_set' => false,
                'is_bill_create_set' => false,
                'is_bill_arrears_set' => false,
                'is_bill_type' => false,
                'is_cycle' => false,
                'is_prepaid_disabled' => true,
                'is_prepaid_button' => false
            ];
            $have_order_discount_disable = $list['charge_type'] == 'new_nuanqifei' ? false : true;
        } else {
            $data = [
                'is_unit_price' => false,
                'is_rate' => false,
                'is_fees_type' => true,
                'is_charge_price' => true,
                'is_cyclicity_type_set' => true,
                'is_bill_create_set' => true,
                'is_bill_arrears_set' => true,
                'is_bill_type' => true,
                'is_cycle' => true,
                'is_prepaid_disabled' => false,
                'is_prepaid_button' => false
            ];
        }
        $data['charge_type']=$list['charge_type'];
        $data['have_order_discount_disable']=$have_order_discount_disable;
        if ($list['charge_type']=='deposit_new'){
            $data['is_unit_price'] = false;
            $data['is_charge_price'] = true;
            $data['is_fees_type'] = true;
        }
        if (in_array($list['type'],[1,3]) ) {
            $data['is_prepaid_button'] = false;
            $data['is_prepaid_disabled'] = true;
        }
        if($list['type'] != 2){
            $data['is_cyclicity_type_set'] = false;
            $data['is_bill_create_set'] = false;
            $data['is_bill_arrears_set'] = false;
            $data['is_bill_type'] = false;
        }
        $data['project_cycle_type']=$list['type'];
        $data['park_numbers_judge'] = false;
        $charge_rule_fees_type_by_park_numbers_judge = (new ConfigCustomizationService())->getchargeRuleFeesTypeByParkNumbers();
        if ($charge_rule_fees_type_by_park_numbers_judge) {
            $data['park_numbers_judge'] = true;
        }
        $data['heating_area_judge'] = false;
        $data['billable_area_judge'] = false;
        $jz_wsxdsj_net_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        if ($jz_wsxdsj_net_customized_open) {
            $data['heating_area_judge'] = true;
            $data['billable_area_judge'] = false;
        }
        
        $db_house_new_charge_number = new HouseNewChargeNumber();
        $numberInfo =$db_house_new_charge_number->get_one(['id'=>$list['subject_id']]);
        $data['projectName'] = isset($list['name'])&&$list['name']?$list['name']:'';
        $data['charge_number_name'] = isset($numberInfo['charge_number_name'])&&$numberInfo['charge_number_name']?$numberInfo['charge_number_name']:'';
        $data['titleName'] = '';
        $data['charge_time']=$this->charge_time;
        if ($data['charge_number_name']) {
            $data['titleName'] .= '科目【'.$data['charge_number_name'].'】';
        }
        if ($data['projectName']) {
            $data['titleName'] .= '项目【'.$data['projectName'].'】';
        }
        return $data;
    }

    /**
     *返账单类型数据
     * @return array
     * @author: liukezhu
     * @date : 2021/6/15
     */
    public function ruleBillParam($village_id=0)
    {
        $data = array(
            'cycle_array' => array(
                array(
                    'key' => 1,
                    'value' => '按日生成'
                ),
                array(
                    'key' => 2,
                    'value' => '按月生成'
                ),
                array(
                    'key' => 3,
                    'value' => '按年生成'
                )
            ),
            'arrears_array' => array(
                array(
                    'key' => 1,
                    'value' => '预生成'
                ),
                array(
                    'key' => 2,
                    'value' => '后生成'
                ),
            )
        );
        $data['park_sys_type'] ='';
        return $data;
    }

    /**
     *添加收费规则
     * @param $param
     * @return int|string
     * @throws \think\Exception
     * @author: liukezhu
     * @date : 2021/6/15
     */
    public function add($param)
    {
        if (!isset($param['charge_project_id']) || empty($param['charge_project_id'])) {
            throw new \think\Exception("非法数据");
        }
        $where[] = ['p.id', '=', $param['charge_project_id']];
        $where[] = ['p.village_id', '=', $param['village_id']];
        $where[] = ['p.status', '<>', 4];
        $dbHouseNewChargeProject = $this->HouseNewChargeProject->getFind($where, 'p.id,p.subject_id,p.type,c.charge_type');
        if (empty($dbHouseNewChargeProject)) {
            throw new \think\Exception("该收费项目不存在");
        }
        if (isset($param['charge_name'])){
            $param['charge_name']=trim($param['charge_name']);
        }

        if (!isset($param['charge_name']) || empty($param['charge_name'])) {
            throw new \think\Exception("请输入收费标准名称");
        }
        $time = time();
        $data = array(
            'village_id' => $param['village_id'],
            'subject_id' => $dbHouseNewChargeProject['subject_id'],
            'charge_project_id' => $param['charge_project_id'],
            'charge_name' => $param['charge_name'],
            'unit_gage'=>'',
            'status' => 1,
            'add_time' => $time
        );
        $configCustomizationService=new ConfigCustomizationService();
        $is_grapefruit_prepaid=$configCustomizationService->getHzhouGrapefruitOrderJudge();
        $isWsxCustomizedOpen=$configCustomizationService->getWSXCustomizedOpen();
        if (in_array($dbHouseNewChargeProject['charge_type'], ['water', 'electric', 'gas','new_nuanqifei'])) {
            //水电燃 新版暖气
            if (!isset($param['unit_price']) || empty($param['unit_price'])) {
                throw new \think\Exception("请输入单价");
            }
            $data['unit_price'] = abs($param['unit_price']);
            $data['charge_price'] = abs($param['unit_price']);
            $data['rate'] = (intval($param['rate']) == 0) ? 1 : intval($param['rate']);
            $data['is_prepaid'] = 2;
            if(isset($param['measure_unit']) && $param['measure_unit']){
                $data['measure_unit'] = $param['measure_unit'];
            }
            if(isset($param['measure_unit']) && empty($param['measure_unit'])){
                $data['measure_unit'] = '元/度';
            }
            $dateType = 2;
        }
        elseif ($dbHouseNewChargeProject['charge_type'] == 'park_new'){
            $param['unit_price']=isset($param['unit_price'])?$param['unit_price']:0;
            $param['rate']=isset($param['rate'])?$param['unit_price']:1;
            $park_info=$this->addNewParkRule($param);
            $data=array_merge($data,$park_info);
            $dateType = $param['bill_create_set'];
            $data['bill_create_set'] = $param['bill_create_set'];
        }
        elseif ($dbHouseNewChargeProject['charge_type'] == 'pile'){
            //添加汽车充电桩收费标准
            $pile_info=$this->addNewPileRule($param);
           //  $data=array_merge($data,$pile_info);
            $dateType = $param['bill_create_set'];
            $data['bill_create_set'] = $param['bill_create_set'];
            $data['fees_type'] = 1;
        }
        else {
            if($dbHouseNewChargeProject['charge_type']=='qrcode'){
                if($param['market_price'] && $param['market_price']<=$param['charge_price']){
                    throw new \think\Exception("市场价必须大于收费金额!");
                }
                $data['market_price'] = $param['market_price']??0;
            }
            if(in_array($dbHouseNewChargeProject['charge_type'],['qrcode','qunuanfei'])){
                $param['is_prepaid'] = 2;
                $param['charge_valid_time'] = date('Y-m-d H:i:s');
            }

            if($dbHouseNewChargeProject['charge_type']=='electricity_loss'){
                $param['fees_type'] = 1;
                $param['is_prepaid'] = 2;
                $param['bill_create_set'] = 2;
                $param['charge_valid_time'] = date('Y-m-d H:i:s');
            }
            //其它
            if (!isset($param['fees_type']) || empty($param['fees_type'])) {
                throw new \think\Exception("请选择计费模式");
            }
            if ($param['fees_type'] == 2) {
                if (!isset($param['unit_gage_type']) || empty($param['unit_gage_type'])) {
                    throw new \think\Exception("请选择计量单位");
                }
                if ($param['unit_gage_type'] == 2) {
                    if (!isset($param['unit_gage_txt']) || empty($param['unit_gage_txt'])) {
                        throw new \think\Exception("请输入自定义计量单位");
                    }
                    $data['unit_gage'] = $param['unit_gage_txt'];
                }
            }
            $data['unit_gage_type'] = $param['unit_gage_type']??1;
            if (!isset($param['charge_price']) || empty($param['charge_price'])) {
                throw new \think\Exception("请输入计费金额");
            }
            $data['cyclicity_set'] = 0;
            //todo 针对收费项目类型选择周期性费用
            if($dbHouseNewChargeProject['type'] == 2){
                if (!isset($param['is_cyclicity_type_set'])) {
                    throw new \think\Exception("请选择周期性费用");
                }
                if (!isset($param['bill_arrears_set']) || empty($param['bill_arrears_set'])) {
                    throw new \think\Exception("请选择账单欠费模式");
                }
                if (!isset($param['bill_type']) || empty($param['bill_type'])) {
                    throw new \think\Exception("请选择生成账单模式");
                }
                if (intval($param['is_cyclicity_type_set']) > 0) {
                    if (!isset($param['cyclicity_set']) || empty($param['cyclicity_set'])) {
                        throw new \think\Exception("请输入周期数");
                    }
                    $data['cyclicity_set'] = $param['cyclicity_set'];
                }
                $data['bill_arrears_set'] = $param['bill_arrears_set'];
                $data['bill_type'] = $param['bill_type'];
                if (!isset($param['bill_create_set']) || empty($param['bill_create_set'])) {
                    throw new \think\Exception("请选择账单生成周期");
                }
            }elseif(in_array($dbHouseNewChargeProject['type'],array(0,1,3))){
                //一次性费用呢
                $data['bill_type']=1;
                if(isset($param['bill_type']) && !empty($param['bill_type'])){
                    $data['bill_type']=intval($param['bill_type']);
                }
                $data['bill_arrears_set']=0;
                $param['bill_arrears_set']=0;
                $data['bill_create_set']=0;
                $param['bill_create_set']=0;
            }
            if (!isset($param['is_prepaid']) || empty($param['is_prepaid'])) {
                throw new \think\Exception("请选择是否支持预缴");
            }
            $data['fees_type'] = $param['fees_type'];
            $data['charge_price'] = abs($param['charge_price']);
            $data['unit_price'] = abs($param['charge_price']);
            if($is_grapefruit_prepaid==1 && $param['bill_create_set']==3 && $param['fees_type']==2){
                $data['charge_price'] = $data['charge_price']*12;
                $data['unit_price'] = $data['unit_price']*12;
            }
            $data['bill_create_set'] = $param['bill_create_set'];
            $data['is_prepaid'] = $param['is_prepaid'];
            $dateType = $param['bill_create_set'];
        }
        if (!isset($param['charge_valid_time']) || empty($param['charge_valid_time'])) {
            throw new \think\Exception("请选择费用标准生效时间");
        }
        $data['charge_valid_type'] = $dateType;
        $charge_valid_time = self::checkDate($dateType, $param['charge_valid_time'], $time);
        $data['charge_valid_time'] = $charge_valid_time;
        if($dbHouseNewChargeProject['charge_type']!='qrcode' && $dbHouseNewChargeProject['charge_type']!='electricity_loss' && $dbHouseNewChargeProject['charge_type']!='qunuanfei'){
            self::checkValidTime($data['charge_project_id'], $dateType, $charge_valid_time);
        }
        if (isset($param['not_house_rate']) && !empty($param['not_house_rate'])) {
            $data['not_house_rate'] = $param['not_house_rate'];
        }else{
            $data['not_house_rate']=100;
        }
        if (isset($param['late_fee_reckon_day']) && !empty($param['late_fee_reckon_day'])) {
            $data['late_fee_reckon_day'] = $param['late_fee_reckon_day'];
        }
        if (isset($param['late_fee_top_day']) && !empty($param['late_fee_top_day'])) {
            $data['late_fee_top_day'] = $param['late_fee_top_day'];
        }
        if (isset($param['late_fee_rate']) && !empty($param['late_fee_rate'])) {
            if (gettype($param['late_fee_rate']) == 'double') {
                $param['late_fee_rate'] = sprintf("%.2f", $param['late_fee_rate']);
            }
            $data['late_fee_rate'] = $param['late_fee_rate'];
        }
        $data['rule_digit']=-1;
        if(isset($param['rule_digit']) && ($param['rule_digit']!='' && is_numeric($param['rule_digit']))){
            $data['rule_digit']=$param['rule_digit'];
        }
        if(isset($param['is_order_discount'])){
            $data['is_order_discount']=$param['is_order_discount'];
        }
        if(isset($param['advance_order_day']) && !empty($param['advance_order_day'])){
            $data['advance_order_day']=$param['advance_order_day'];
        }
        $id=$this->HouseNewChargeRule->addFind($data);
        
        if ($dbHouseNewChargeProject['charge_type'] == 'pile' && isset($pile_info)) {
            $db_house_new_pile_charge = new HouseNewPileCharge();
            $db_house_new_pile_charge->save_one(['id' => $pile_info], ['rule_id' => $id]);
        }
        if($is_grapefruit_prepaid>0 || $isWsxCustomizedOpen){
            $houseNewChargePrepaidDiscount=new HouseNewChargePrepaidDiscount();
            $whereArr=array(['village_id','=',$param['village_id']]);
            $whereArr[]=['charge_project_id','=',$param['charge_project_id']];
            $whereArr[]=['charge_rule_id','=',0];
            $whereArr[]=['status','=',1];
            if($isWsxCustomizedOpen){
                $tmpWhereArr=$whereArr;
                $tmpWhereArr[]=['order_discount_type','>',0];
                if(isset($param['is_order_discount']) && $param['is_order_discount']>0){
                    $houseNewChargePrepaidDiscount->editFind($tmpWhereArr,['charge_rule_id'=>$id]);
                }else{
                    $houseNewChargePrepaidDiscount->editFind($tmpWhereArr,['charge_rule_id'=>$id,'status'=>4,'update_time'=>time()]);
                }
                
            }else{
                if($data['is_prepaid']==1){
                    $tmpWhereArr=$whereArr;
                    $whereArr[]=['bill_create_set','=',$dateType];
                    $houseNewChargePrepaidDiscount->editFind($whereArr,['charge_rule_id'=>$id]);
                    $tmpWhereArr[]=['bill_create_set','<>',$dateType];
                    $houseNewChargePrepaidDiscount->editFind($tmpWhereArr,['charge_rule_id'=>$id,'status'=>4,'update_time'=>time()]);
                }else{
                    //不支持 删除掉已创建的 
                    $houseNewChargePrepaidDiscount->editFind($whereArr,['charge_rule_id'=>$id,'status'=>4,'update_time'=>time()]);
                }
            }
        }else{
            $this->bindChargePrepaid($id);
        }
        
        if($this->hasMeijuWuyeCustomized() && $dbHouseNewChargeProject['charge_type'] != 'park_new'){
            if(isset($param['copy_rule_id']) && ($param['copy_rule_id']>0) && ($dbHouseNewChargeProject['charge_type']=='public_electric')){
                //复制绑定关系
                $this->copyStandardBindRelation($param['copy_rule_id'],$id,$param['village_id']);
            }elseif($dbHouseNewChargeProject['charge_type']=='public_water'){
                $this->standardBindAllRoom($id,$param['village_id']);
            }
        }
        return $id;
    }
    
    //绑定所有房间
    public function standardBindAllRoom($to_rule_id=0,$village_id=0){
        if($to_rule_id<1){
            return false;
        }
        $whereArr=array();
        $whereArr[] = ['r.id', '=', $to_rule_id];
        if($village_id>0){
            $whereArr[] = ['r.village_id', '=', $village_id];
        }
        $to_rule_info = $this->HouseNewChargeRule->getFind($whereArr, 'r.*,p.type,c.charge_type');
        if($to_rule_info && !$to_rule_info->isEmpty()){
            $to_rule_info=$to_rule_info->toArray();
            if($village_id<1){
                $village_id=$to_rule_info['village_id'];
            }
            if($village_id<1){
                return false;
            }
            $whereArr=array();
            $whereArr[] = ['village_id', '=', $village_id];
            $whereArr[] = ['is_del', '=', 0];
            $whereArr[] = ['status', 'in', array(1,3)];
            $whereArr[] = ['single_id', '>', 0];
            $db_standardbind = new HouseNewChargeStandardBind();
            $fieldStr='village_id,single_id,floor_id,layer_id,pigcms_id as vacancy_id';
            $vacancyDb = new HouseVillageUserVacancy();
            $vacancyObj = $vacancyDb->getList1($whereArr, $fieldStr);
            if($vacancyObj && !$vacancyObj->isEmpty()){
                $bindList=$vacancyObj->toArray();
                fdump_api(['to_rule_id'=>$to_rule_id,'bindList'=>$bindList],'00standardBindAllRoom',1);
                $nowtime=time();
                if($bindList){
                    foreach ($bindList as $sbvv){
                        $sbvv['rule_id']=$to_rule_id;
                        $sbvv['bind_type']=1;
                        $sbvv['project_id']=$to_rule_info['charge_project_id'];
                        $sbvv['order_add_time']=0;
                        $sbvv['order_add_type']=$to_rule_info['bill_create_set'];
                        $sbvv['add_time']=$nowtime;
                        $sbvv['charge_valid_time']=$to_rule_info['charge_valid_time'];
                        $sbvv['cycle']=1;
                        $sbvv['custom_value']='';
                        $sbvv['add_time']=$nowtime;
                        $sbvv['is_del'] = 1;
                        $bind_id = $db_standardbind->addOne($sbvv);
                    }
                }
                return false;
            }
        }
        return false;
    }
    
    //复制一个标准的绑定关系
    public function copyStandardBindRelation($copy_rule_id=0,$to_rule_id=0,$village_id=0){
        if($copy_rule_id<1 || $to_rule_id<1){
            return false;
        }
        $whereArr=array();
        $whereArr[] = ['r.id', '=', $to_rule_id];
        if($village_id>0){
            $whereArr[] = ['r.village_id', '=', $village_id];
        }
        $to_rule_info = $this->HouseNewChargeRule->getFind($whereArr, 'r.*,p.type,c.charge_type');
        if($to_rule_info && !$to_rule_info->isEmpty()){
            $to_rule_info=$to_rule_info->toArray();
            $whereArr=array();
            $whereArr[] = ['rule_id', '=', $copy_rule_id];
            if($village_id>0){
                $whereArr[] = ['village_id', '=', $village_id];
            }
            $whereArr[] = ['is_del', '=', 1];
            $db_standardbind = new HouseNewChargeStandardBind();
            $fieldStr='village_id,single_id,floor_id,layer_id,vacancy_id,garage_id,position_id,custom_value,cycle';
            $standardbindObj=$db_standardbind->getLists1($whereArr,$fieldStr);

            if($standardbindObj && !$standardbindObj->isEmpty()){
                $bindList=$standardbindObj->toArray();
                fdump_api(['copy_rule_id'=>$copy_rule_id,'to_rule_id'=>$to_rule_id,'bindList'=>$bindList],'00copyStandardBindRelation',1);
                $nowtime=time();
                if($bindList){
                    foreach ($bindList as $sbvv){
                        $sbvv['rule_id']=$to_rule_id;
                        $sbvv['bind_type']=1;
                        $sbvv['project_id']=$to_rule_info['charge_project_id'];
                        $sbvv['order_add_time']=0;
                        $sbvv['order_add_type']=$to_rule_info['bill_create_set'];
                        $sbvv['add_time']=$nowtime;
                        $sbvv['charge_valid_time']=$to_rule_info['charge_valid_time'];
                        $sbvv['cycle']=$sbvv['cycle']>0 ? $sbvv['cycle']:1;
                        $sbvv['add_time']=$nowtime;
                        $sbvv['is_del'] = 1;
                        $bind_id = $db_standardbind->addOne($sbvv);
                    }
                }
                return false;
            }
        }
        return false;
    }

   public function NextNumberArray($itm, $arr){
        $item=0;
        if(isset($arr[9]) && $itm >= $arr[9]){
            $item=$arr[9];
        }else{
            for ($i=0;$i < count($arr);$i++){
                if($itm < $arr[$i]){
                    $item=$arr[$i-1];
                    break;
                }

            }
        }
       return $item;
    }

    //todo 操作未绑定的预缴周期绑定收费标准
    private function bindChargePrepaid($rule_id){
        $where[] = ['id', '=',$rule_id];
        $where[] = ['status', '<>', 4];
        $list = $this->HouseNewChargeRule->getOne($where);
        if(!$list){
            return false;
        }
        if($list['is_prepaid'] == 2){
            //收费标准在非预缴状态下 处理未绑定预缴周期
            $data=array(
                'status'=>4,
                'update_time'=>time(),
            );
             $this->HouseNewChargePrepaid->editFind(['charge_rule_id'=>0],$data);
        }else{
            $where=[];
            $where[] = ['charge_rule_id', '=','0'];
            $where[] = ['village_id', '=',$list['village_id']];
            $where[] = ['status', '<>', 4];
            $dbHouseNewChargePrepaid= $this->HouseNewChargePrepaid->getList($where);
            $cycle_value=array_column($this->HouseNewChargePrepaidService->cycle,'value');
            unset($cycle_value[10]);
            $give_cycle=array_column($this->HouseNewChargePrepaidService->give_cycle,'value');
            unset($give_cycle[10]);
            if($dbHouseNewChargePrepaid){
                $dbHouseNewChargePrepaid=$dbHouseNewChargePrepaid->toarray();
                foreach ($dbHouseNewChargePrepaid as $v){
                    $data=[];
                    $data['charge_rule_id']=$rule_id;
                    if(intval($list['cyclicity_set']) > 0){
                        //预缴周期判断
                        if($v['cycle'] > intval($list['cyclicity_set'])){
                            if($v['cycle_param'] > 0){
                                //走的自定义预缴周期
                                $data['cycle']=$list['cyclicity_set'];
                            }else{
                                //走的选择预缴周期
                                $data['cycle']=$this->NextNumberArray($list['cyclicity_set'],$cycle_value);
                            }
                        }
                        //赠送周期判断
                        if($v['type'] == 2){
                            if(isset($data['cycle'])){
                                if($v['give_cycle_type'] > $data['cycle']){
                                    $data['give_cycle_type']=$this->NextNumberArray($data['cycle'],$give_cycle);
                                    $data['give_cycle_param']=$this->HouseNewChargePrepaidService->getGiveCycle($data['give_cycle_type'],2);
                                }
                            }
                        }
                    }
                    $this->HouseNewChargePrepaid->editFind(['id'=>$v['id']],$data);
                }
            }

        }

    }

    //todo 校验日期参数
    private function checkDate($dateType, $valid_time, $time)
    {
        $timestamp = strtotime($valid_time);
        if ($timestamp < 1) {
            throw new \think\Exception("请选择费用标准生效时间");
        }
        $date = date('Y-m-d', $timestamp);
        $division = explode('-', $date);
        switch ($dateType) {
            case 0:
                //年月日
//                if(($division[0].$division[1].$division[2]) <= date('Ymd',$time)){
//                    throw new \think\Exception("费用标准生效年月日不可小于当前时间");
//                }
                $charge_valid_time = strtotime($date);
                break;
            case 1:
                //年月日
//                if(($division[0].$division[1].$division[2]) <= date('Ymd',$time)){
//                    throw new \think\Exception("费用标准生效年月日不可小于当前时间");
//                }
                $charge_valid_time = strtotime($date);
                break;
            case 2:
                //年月
//                if(($division[0].$division[1]) < date('Ym',$time)){
//                    throw new \think\Exception("费用标准生效年月不可小于当前时间");
//                }
                $charge_valid_time = strtotime($division[0] . '-' . $division[1] . '-01');
                break;
            case 3:
                //年
//                if($division[0] < date('Y',$time)){
//                    throw new \think\Exception("费用标准生效年份不可小于当前年份");
//                }
                $charge_valid_time = strtotime($division[0] . '-01-01');
                break;
            default:
                throw new \think\Exception("请求参数格式错误");
        }
        $charge_valid_time = strtotime($date);
        return $charge_valid_time;
    }

    /**
     * 校验收费规则
     * @param $charge_project_id  收费项目id
     * @param $dateType 1:年月日 2：年月 3：年
     * @param $charge_valid_time  有效时间
     * @param int $charge_rule_id 编辑规则id
     * @throws \think\Exception
     * @author: liukezhu
     * @date : 2021/6/17
     */
    private function checkValidTime($charge_project_id, $dateType, $charge_valid_time, $charge_rule_id = 0)
    {
        $where[] = ['charge_project_id', '=', $charge_project_id];
        $where[] = ['charge_valid_type', '=', $dateType];
        $where[] = ['charge_valid_time', '=', $charge_valid_time];
        $where[] = ['status', '<>', 4];
        if ($charge_rule_id > 0) {
            $where[] = ['id', '<>', $charge_rule_id];
        }
        $list = $this->HouseNewChargeRule->getOne($where);
        if ($list) {
            throw new \think\Exception("该【" . (date('Y-m-d', $list['charge_valid_time'])) . "】有效时间已存在");
        }
    }

    /**
     *编辑收费标准
     * @param $param
     * @param bool $id
     * @return \app\community\model\db\WorkMsgAuditInfo|bool
     * @throws \think\Exception
     * @author: liukezhu
     * @date : 2021/6/15
     */
    public function edit($param, $id = false)
    {
        $where=array();
        $where[] = ['r.id', '=', $param['id']];
        $where[] = ['r.village_id', '=', $param['village_id']];
        if(isset($param['type']) && $param['type'] == 'del'){
            $where[] = ['r.status', 'in', '1,2,4'];
        }else{
            $where[] = ['r.status', 'in', '1,2'];
        }
        $listObj = $this->HouseNewChargeRule->getFind($where, 'r.*,p.type,c.charge_type');
        if (empty($listObj) || $listObj->isEmpty()) {
            throw new \think\Exception("该收费规则不存在");
        }
        $list=$listObj->toArray();
        $time = time();
        $configCustomizationService=new ConfigCustomizationService();
        $is_grapefruit_prepaid=$configCustomizationService->getHzhouGrapefruitOrderJudge();
        if ($id) {
            if (!isset($param['charge_name']) || empty($param['charge_name'])) {
                throw new \think\Exception("请输入收费标准名称");
            }
            $data = array(
                'charge_name' => $param['charge_name'],
                'update_time' => $time,
                'unit_gage'=>''
            );
            if (in_array($list['charge_type'], ['water', 'electric', 'gas','new_nuanqifei'])) {
                //水电燃 新版暖气
                if (!isset($param['unit_price']) || empty($param['unit_price'])) {
                    throw new \think\Exception("请输入单价");
                }
                $data['unit_price'] = abs($param['unit_price']);
                $data['measure_unit'] = $param['measure_unit'];
                $data['charge_price'] = abs($param['unit_price']);
                $data['rate'] = (intval($param['rate']) == 0) ? 1 : intval($param['rate']);
                if (isset($param['is_prepaid']) && !empty($param['is_prepaid'])) {
                    $data['is_prepaid'] = $param['is_prepaid'];
                }
                $dateType = 2;

            }
            elseif ($list['charge_type'] == 'park_new'){
                $park_info=$this->addNewParkRule($param,$list);
                $data=array_merge($data,$park_info);
                $dateType = $param['bill_create_set'];
            }
            else {
                //其它
                if (!isset($param['fees_type']) || empty($param['fees_type'])) {
                    throw new \think\Exception("请选择计费模式");
                }
                if ($param['fees_type'] == 2) {
                    if (!isset($param['unit_gage_type']) || empty($param['unit_gage_type'])) {
                        throw new \think\Exception("请选择计量单位");
                    }
                    $data['unit_gage_type']=$param['unit_gage_type'];
                    if ($param['unit_gage_type'] == 2) {
                        if (!isset($param['unit_gage_txt']) || empty($param['unit_gage_txt'])) {
                            throw new \think\Exception("请输入自定义计量单位");
                        }
                        $data['unit_gage'] = trim($param['unit_gage_txt']);
                    }
                }

                if (!isset($param['charge_price']) || empty($param['charge_price'])) {
                    throw new \think\Exception("请输入计费金额");
                }
                $data['cyclicity_set'] = 0;
                //todo 针对收费项目类型选择周期性费用
                if($list['type'] == 2){
                    if (!isset($param['is_cyclicity_type_set'])) {
                        throw new \think\Exception("请选择周期性费用");
                    }
                    if (!isset($param['bill_arrears_set']) || empty($param['bill_arrears_set'])) {
                        throw new \think\Exception("请选择账单欠费模式");
                    }
                    if (!isset($param['bill_type']) || empty($param['bill_type'])) {
                        throw new \think\Exception("请选择生成账单模式");
                    }
                    if (!isset($param['bill_create_set']) || empty($param['bill_create_set'])) {
                        throw new \think\Exception("请选择账单生成周期");
                    }
                    if (intval($param['is_cyclicity_type_set']) > 0) {
                        if (!isset($param['cyclicity_set']) || empty($param['cyclicity_set'])) {
                            throw new \think\Exception("请输入周期数");
                        }
                        $data['cyclicity_set'] = $param['cyclicity_set'];
                        $prepaid_where[] = ['charge_rule_id', '=', $list['id']];
                        $prepaid_where[] = ['village_id', '=', $list['village_id']];
                        $prepaid_where[] = ['status', '<>', 4];
                        $max_cycle=$this->HouseNewChargePrepaid->getMax($prepaid_where,'cycle');
                        if($max_cycle){
                            if($data['cyclicity_set'] < $max_cycle){
                                throw new \think\Exception("该收费标准存在预缴周期，自定义周期不可低于：".$max_cycle.'周期');
                            }
                        }
                    }
                    $data['bill_arrears_set'] = $param['bill_arrears_set'];
                    $data['bill_type'] = $param['bill_type'];
                }elseif(in_array($list['type'],array(0,1,3))){
                    //一次性费用呢
                    $data['bill_type']=1;
                    if(isset($param['bill_type']) && !empty($param['bill_type'])){
                        $data['bill_type']=intval($param['bill_type']);
                    }
                    $data['bill_arrears_set']=0;
                    $param['bill_arrears_set']=0;
                    $data['bill_create_set']=0;
                    $param['bill_create_set']=0;
                }
                
                if (!isset($param['is_prepaid']) || empty($param['is_prepaid'])) {
                    throw new \think\Exception("请选择是否支持预缴");
                }
                $data['fees_type'] = $param['fees_type'];
                $data['charge_price'] = abs($param['charge_price']);
                $data['unit_price'] = abs($param['charge_price']);
                if($is_grapefruit_prepaid==1 && $param['bill_create_set']==3 && $param['fees_type']==2){
                    $data['charge_price'] = $data['charge_price']*12;
                    $data['unit_price'] = $data['unit_price']*12;
                }
                $data['is_prepaid'] = $param['is_prepaid'];
                $dateType = $param['bill_create_set'];
                $data['bill_create_set'] = $param['bill_create_set'];
            }
            if (!isset($param['charge_valid_time']) || empty($param['charge_valid_time'])) {
                throw new \think\Exception("请选择费用标准生效时间");
            }
            $data['charge_valid_type'] = $dateType;
            $charge_valid_time = self::checkDate($dateType, $param['charge_valid_time'], $time);
            if($list['charge_type']!='qrcode' && $list['charge_type']!='electricity_loss'){
                self::checkValidTime($list['charge_project_id'], $dateType, $charge_valid_time, $list['id']);
            }
            $data['charge_valid_time'] = $charge_valid_time;
            if (isset($param['not_house_rate'])) {
                $data['not_house_rate'] = $param['not_house_rate'];
            }
            if (isset($param['late_fee_reckon_day']) && !empty($param['late_fee_reckon_day'])) {
                $data['late_fee_reckon_day'] = $param['late_fee_reckon_day'];
            } else {
                $data['late_fee_reckon_day'] = 0;
            }
            if (isset($param['late_fee_top_day']) && !empty($param['late_fee_top_day'])) {
                $data['late_fee_top_day'] = $param['late_fee_top_day'];
            } else {
                $data['late_fee_top_day'] = 0;
            }
            if (isset($param['late_fee_rate']) && !empty($param['late_fee_rate'])) {
                if (gettype($param['late_fee_rate']) == 'double') {
                    $param['late_fee_rate'] = sprintf("%.2f", $param['late_fee_rate']);
                }
                $data['late_fee_rate'] = $param['late_fee_rate'];
            } else {
                $data['late_fee_rate'] = 0;
            }
            $data['rule_digit']=-1;
            if(isset($param['rule_digit']) && ($param['rule_digit']!='' && is_numeric($param['rule_digit']))){
                $data['rule_digit']=$param['rule_digit'];
            }
            if(isset($param['is_order_discount']) && !empty($param['is_order_discount'])){
                $data['is_order_discount']=$param['is_order_discount'];
            }
            if(isset($param['advance_order_day'])){
                $data['advance_order_day']=$param['advance_order_day']>0 ? $param['advance_order_day']:0;
            }
            //todo 同步更新字段
            $this->HouseNewChargeStandardBind->editFind(['rule_id' => $list['id'], 'village_id' => $list['village_id']], ['update_time' => time(), 'charge_valid_time' => $charge_valid_time]);
            $saveRet=$this->HouseNewChargeRule->editFind(['id' => $list['id']], $data);

            $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
            $queuData = [
                'logData' => [
                    'tbname' => '收费管理-收费标准管理',
                    'table'  => 'house_new_charge_rule',
                    'client' => '小区后台',
                    'trigger_path' => '收费标准管理->编辑',
                    'trigger_type' => $this->getUpdateNmae(),
                    'addtime'      => time(),
                    'village_id'   => $list['village_id'],
                    'property_id' =>0,
                    'op_id'   => $parseInfo['op_id'],
                    'op_type'   => $parseInfo['op_type'],
                    'op_name'   => $parseInfo['op_name'],
                ],
                'newData' => $data,
                'oldData' => $list
            ];
            $this->laterLogInQueue($queuData);
            return $saveRet;
            
        }
        else {
            $edit_status = [
                'cyclicity_set_txt_status' => false,
                'unit_gage_txt_status' => false,
                'is_disabled' => false
            ];
            $edit_data = array(
                'id' => $list['id'],
                'charge_name' => $list['charge_name'],
                'not_house_rate' => (intval($list['not_house_rate']) == 100 || empty($list['not_house_rate'])) ? '100' : $list['not_house_rate'],
                'late_fee_reckon_day' => empty($list['late_fee_reckon_day']) ? '' : $list['late_fee_reckon_day'],
                'late_fee_top_day' => empty($list['late_fee_top_day']) ? '' : $list['late_fee_top_day'],
                'late_fee_rate' => empty($list['late_fee_rate']) ? '' : $list['late_fee_rate'],
                'is_prepaid' => $list['is_prepaid'],
                'rule_digit'=>'',
                'charge_project_id'=>$list['charge_project_id'],
                'advance_order_day'=>0,
            );
            if(isset($list['rule_digit']) && ($list['rule_digit']>=0)){
                $edit_data['rule_digit']=$list['rule_digit'];
            }
            if(isset($list['advance_order_day']) && ($list['advance_order_day']>=0)){
                $edit_data['advance_order_day']=$list['advance_order_day'];
            }
            $date = date('Y-m-d', $list['charge_valid_time']);
            $division = explode('-', $date);
            if (empty($list['unit_gage'])) {
                $edit_data['unit_gage_type'] = $list['unit_gage_type'];
            } else {
                $edit_status['unit_gage_txt_status'] = true;
                $edit_data['unit_gage_type'] = 2;
                $edit_data['unit_gage_txt'] = $list['unit_gage'];
            }
            if(isset($list['is_order_discount'])){
                $edit_data['is_order_discount']=intval($list['is_order_discount']);
            }
            $have_order_discount_disable=false;
            if (in_array($list['charge_type'], ['water', 'electric', 'gas','new_nuanqifei'])) {
                //水电燃 新版暖气
                $edit_data['unit_price'] = $list['unit_price'];
                $edit_data['measure_unit'] = $list['measure_unit'];
                $edit_data['rate'] = empty($list['rate']) ? '' : $list['rate'];
                $dateType = 2;
                $have_order_discount_disable = $list['charge_type'] == 'new_nuanqifei' ? false : true;
            } elseif ($list['charge_type']=='park_new') {
                $have_order_discount_disable=true;
                $edit_data['fees_type'] = $list['fees_type'];
                $edit_data['charge_price'] = $list['charge_price'];
                $edit_data['cyclicity_set'] = $list['cyclicity_set'];
                $edit_data['bill_create_set'] = $list['bill_create_set'];
                $edit_data['bill_arrears_set'] = $list['bill_arrears_set'];
                $edit_data['bill_type'] = $list['bill_type'];
                $park_info=$this->getTempRuleInfo($list);
               if (!empty($park_info)&&!empty($park_info['id'])){
                   unset($park_info['id']);
               }
                $edit_data=array_merge($edit_data,$park_info);
                $dateType = $list['bill_create_set'];
            }
            elseif ($list['charge_type']=='pile') {
                $have_order_discount_disable=true;
                $edit_data['fees_type'] = $list['fees_type'];
                $edit_data['charge_price'] = $list['charge_price'];
                $edit_data['cyclicity_set'] = $list['cyclicity_set'];
                $edit_data['bill_create_set'] = $list['bill_create_set'];
                $edit_data['bill_arrears_set'] = $list['bill_arrears_set'];
                $edit_data['bill_type'] = $list['bill_type'];
                $pile_info=$this->getNewPileRule($param);
                if (!empty($pile_info)&&!empty($pile_info['id'])){
                    unset($pile_info['id']);
                }
                $edit_data=array_merge($edit_data,$pile_info);
                $dateType = $list['bill_create_set'];
            }
            else {
                if($is_grapefruit_prepaid==1 && $list['bill_create_set']==3 && $list['fees_type']==2){
                    $list['charge_price']=$list['charge_price']/12;
                    $list['charge_price']=round($list['charge_price'],4);
                    $list['unit_price'] = $list['unit_price']/12;
                    $list['unit_price']=round($list['unit_price'],4);
                }
                $edit_data['fees_type'] = $list['fees_type'];
                $edit_data['charge_price'] = $list['charge_price'];
                $edit_data['cyclicity_set'] = $list['cyclicity_set'];
                $edit_data['bill_create_set'] = $list['bill_create_set'];
                $edit_data['bill_arrears_set'] = $list['bill_arrears_set'];
                $edit_data['bill_type'] = $list['bill_type'];
                if (intval($list['cyclicity_set']) > 0) {
                    $edit_data['is_cyclicity_type_set'] = 1;
                    $edit_status['cyclicity_set_txt_status'] = true;
                } else {
                    $edit_data['is_cyclicity_type_set'] = 0;
                }
                $dateType = $list['bill_create_set'];
            }
            if ($list['charge_valid_time'] <= $time) {
                $edit_status['is_disabled'] = true;
            }
            $edit_data['charge_valid_time'] = date('Y-m-d', $list['charge_valid_time']);
            $edit_data['market_price'] = $list['market_price']>0?$list['market_price']:'';
           /* switch ($dateType) {
                case 1:
                    //年月日
                    if (($division[0] . $division[1] . $division[2]) <= date('Ymd', $time)) {
                        $edit_status['is_disabled'] = true;
                    }
                    $edit_data['charge_valid_time'] = date('Y-m-d', $list['charge_valid_time']);
                    break;
                case 2:
                    //年月
                    if (($division[0] . $division[1]) <= date('Ym', $time)) {
                        $edit_status['is_disabled'] = true;
                    }
                    $edit_data['charge_valid_time'] = date('Y-m', $list['charge_valid_time']);
                    break;
                case 3:
                    //年
                    if ($division[0] <= date('Y', $time)) {
                        $edit_status['is_disabled'] = true;
                    }
                    $edit_data['charge_valid_time'] = date('Y', $list['charge_valid_time']);
                    break;
                default:
                    throw new \think\Exception("请求参数格式错误");
            }*/
            $param = array(
                'village_id' => $param['village_id'],
                'type' => isset($param['type']) ? $param['type'] : '',
                'charge_project_id' => $list['charge_project_id']
            );
            $ruleParam = self::ruleParam($param);
            if ($list['fees_type'] == 2) {
                $ruleParam['is_unit_gage'] = true;
            } else {
                $ruleParam['is_unit_gage'] = false;
            }
            if ($list['is_prepaid'] == 2) {
                $ruleParam['is_prepaid_button'] = false;
            } else {
                $ruleParam['is_prepaid_button'] = true;
            }
            $ruleParam['order_discount_btn']= false;
            if(isset($list['is_order_discount']) && $list['is_order_discount']>0){
                $ruleParam['order_discount_btn']= true;
            }
            $ruleParam['charge_type']=$list['charge_type'];
            return ['edit_data' => $edit_data, 'edit_status' => $edit_status, 'show_data' => $ruleParam,'project_cycle_type'=>$list['type'],'have_order_discount_disable'=>$have_order_discount_disable];
        }

    }

    /**
     * 删除项目规则 同步预缴周期
     * @param $param
     * @return \app\community\model\db\WorkMsgAuditInfo|bool
     * @author: liukezhu
     * @date : 2021/6/15
     */
    public function del($param)
    {
        $where=array();
        $where[] = ['id', '=', $param['id']];
        $where[] = ['village_id', '=', $param['village_id']];
        $list = $this->HouseNewChargeRule->getOne($where, 'id,village_id,charge_name');
        if (!$list) {
            return false;
        }
        $data = array(
            'status' => 4,
            'update_time' => time(),
        );
        $dbHouseNewChargeRule = $this->HouseNewChargeRule->editFind(['id' => $list['id']], $data);
        
        $where=array();
        $where[] = ['id', '=', $list['id']];
        $oneRuleObj = $this->HouseNewChargeRule->getOne($where, 'id,village_id,status');
        if(empty($oneRuleObj) || $oneRuleObj->isEmpty() || $oneRuleObj['status']==4){
            $this->HouseNewChargePrepaid->editFind(['charge_rule_id' => $list['id'], 'village_id' => $list['village_id']], $data);
            $this->HouseNewChargeStandardBind->editFind(['rule_id' => $list['id'], 'village_id' => $list['village_id']], ['update_time' => time(), 'is_del' => 4]);
            $db_house_new_pay_order=new HouseNewPayOrder();
            $discard_reason='收费标准为【'. $list['charge_name'].'】已删除,删除时间为【'.date('Y-m-d H:i:s').'】';
            $db_house_new_pay_order->editFind(['rule_id' => $list['id'], 'village_id' => $list['village_id'],'is_paid'=>2,'is_discard'=>1], ['update_time' => time(), 'is_discard' => 2,'discard_reason'=>$discard_reason]);
        }
        $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
        $queuData = [
            'logData' => [
                'tbname' => '收费管理-收费标准管理',
                'table'  => 'house_new_charge_rule',
                'client' => '小区后台',
                'trigger_path' => '收费标准管理->删除',
                'trigger_type' => $this->getDeleteName(),
                'addtime'      => time(),
                'village_id'   => $param['village_id'],
                'property_id' =>0,
                'op_id'   => $parseInfo['op_id'],
                'op_type'   => $parseInfo['op_type'],
                'op_name'   => $parseInfo['op_name'],
            ],
            'newData' => $param,
            'oldData' => []
        ];
        $this->laterLogInQueue($queuData);
        return true;
    }


    /**
     * 查询收费标准绑定的列表
     * @author:zhubaodi
     * @date_time: 2021/6/17 9:48
     */
    public function getStandardBindList2($data)
    {
        if (empty($data['rule_id'])) {
            throw new \think\Exception("收费标准id不能为空");
        }
        $db_rule = new HouseNewChargeRule();
        $ruleInfo = $db_rule->getOne(['id' => $data['rule_id']]);
        if (empty($ruleInfo)) {
            throw new \think\Exception("收费标准信息不存在");
        }
        $db_subject = new HouseNewChargeNumber();
        $subjectInfo = $db_subject->get_one(['id' => $ruleInfo['subject_id']]);
        if (empty($subjectInfo)) {
            throw new \think\Exception("收费科目信息不存在");
        }
        $where[] = ['b.rule_id', '=', $data['rule_id']];
        $where[] = ['b.village_id', '=', $data['village_id']];
        $where[] = ['b.bind_type', '=', $data['bind_type']];
        $where[] = ['b.is_del', '=', 1];


        $db_bind = new HouseNewChargeStandardBind();
        if (isset($data['garage_id']) && !empty($data['garage_id'])) {
            $where[] = ['b.garage_id', '=', $data['garage_id']];
        }
        if (isset($data['position_num']) && !empty($data['position_num'])) {
            $where[] = ['pp.position_num', 'like','%'.$data['position_num'].'%'];
        }
        //  print_r($where);exit;
        $count = $db_bind->getBindCount2($where);
        $bindList = $db_bind->getBindList2($where, 'b.*,pp.position_num,pg.garage_num,pp.position_area,vv.housesize', $data['page'], $data['limit']);

        if (!empty($bindList)){
            $bindList=$bindList->toArray();
            if (!empty($bindList)){
                foreach ($bindList as &$v){
                    if (empty($v['order_add_time'])){
                        $v['order_add_time']='--';
                    }else{

                        $v['order_add_time']=date('Y-m-d',$v['order_add_time']);
                    }
                    if (in_array($subjectInfo['charge_type'],['electric','gas','water'])){
                        $v['date_status']='--';
                    }else{
                        if ($v['order_add_type']==1)
                        {
                            $v['date_status']='按日生成';
                        }elseif ($v['order_add_type']==2){
                            $v['date_status']='按月生成';
                        }elseif ($v['order_add_type']==3){
                            $v['date_status']='按年生成';
                        }
                    }
                }
            }
        }

        // print_r($bindList);exit;
        $data1 = [];
        $data1['count'] = $count;
        $data1['total_limit'] = $data['limit'];
        $data1['list'] = $bindList;
        $data1['rule_info'] = $ruleInfo;
        return $data1;
    }


    /**
     * 查询收费标准绑定的列表
     * @author:zhubaodi
     * @date_time: 2021/6/17 9:48
     */
    public function getStandardBindList($data)
    {
        if (empty($data['rule_id'])) {
            throw new \think\Exception("收费标准id不能为空");
        }
        $db_rule = new HouseNewChargeRule();
        $ruleInfo = $db_rule->getOne(['id' => $data['rule_id']]);
        if (empty($ruleInfo)) {
            throw new \think\Exception("收费标准信息不存在");
        }
        $db_subject = new HouseNewChargeNumber();
        $subjectInfo = $db_subject->get_one(['id' => $ruleInfo['subject_id']]);
        if (empty($subjectInfo)) {
            throw new \think\Exception("收费科目信息不存在");
        }
        $where[] = ['b.rule_id', '=', $data['rule_id']];
        $where[] = ['b.village_id', '=', $data['village_id']];
        $where[] = ['b.bind_type', '=', $data['bind_type']];
        $where[] = ['b.is_del', '=', 1];


        $db_bind = new HouseNewChargeStandardBind();
        if ($data['bind_type'] == 1) {
            if (isset($data['vecancy_id']) && !empty($data['vecancy_id'])) {
               // $where[] = ['b.vacancy_id', '=', $data['vecancy_id'][3]];
                if (isset($data['vecancy_id'][3])){
                    $where[]=['b.vacancy_id','=',$data['vecancy_id'][3]];
                }elseif (isset($data['vecancy_id'][2])){
                    $where[]=['v.layer_id','=',$data['vecancy_id'][2]];
                }elseif (isset($data['vecancy_id'][1])){
                    $where[]=['v.floor_id','=',$data['vecancy_id'][1]];
                } else{
                    $where[]=['v.single_id','=',$data['vecancy_id'][0]];
                }
            }
            $where[] = ['v.is_del', '=', 0];
            $count = $db_bind->getCount_vecancy($where);
            $bindList = $db_bind->getLists($where, 'b.*,s.single_name,f.floor_name,l.layer_name,v.room', $data['page'], $data['limit']);
        } else {
            if (isset($data['garage_id']) && !empty($data['garage_id'])) {
                $where[] = ['b.garage_id', '=', $data['garage_id']];
            }
            if (isset($data['position_num']) && !empty($data['position_num'])) {
                    $where[] = ['pp.position_num', 'like','%'.$data['position_num'].'%'];
            }
           //  print_r($where);exit;
            $count = $db_bind->getBindCount($where);
            $bindList = $db_bind->getBindList($where, 'b.*,pp.position_num,pg.garage_num', $data['page'], $data['limit']);
        }

        if (!empty($bindList)){
            $bindList=$bindList->toArray();
            if (!empty($bindList)){
                foreach ($bindList as &$v){
                    if (empty($v['order_add_time'])){
                        $v['order_add_time']='--';
                    }else{

                        $v['order_add_time']=date('Y-m-d',$v['order_add_time']);
                    }
                    if (in_array($subjectInfo['charge_type'],['electric','gas','water'])){
                        $v['date_status']='--';
                    }else{
                        if ($v['order_add_type']==1)
                        {
                            $v['date_status']='按日生成';
                        }elseif ($v['order_add_type']==2){
                            $v['date_status']='按月生成';
                        }elseif ($v['order_add_type']==3){
                            $v['date_status']='按年生成';
                        }
                    }
                }
            }
        }

        // print_r($bindList);exit;
        $data1 = [];
        $data1['count'] = $count;
        $data1['total_limit'] = $data['limit'];
        $data1['list'] = $bindList;
        $data1['rule_info'] = $ruleInfo;
        return $data1;
    }

    /**
     * 添加收费标准绑定
     * @author:zhubaodi
     * @date_time: 2021/6/17 9:48
     */
    public function addStandardBind($data)
    {
        if (empty($data['rule_id'])) {
            throw new \think\Exception("收费标准id不能为空");
        }
        if (empty($data['village_id'])) {
            throw new \think\Exception("小区id不能为空");
        }
        if (empty($data['bind_type'])) {
            throw new \think\Exception("绑定类型不能为空");
        }
        
        $db_rule = new HouseNewChargeRule();
        $ruleInfo = $db_rule->getOne(['id' => $data['rule_id']]);
        if (empty($ruleInfo)) {
            throw new \think\Exception("收费标准信息不存在，无法添加绑定");
        }

        if ($ruleInfo['fees_type']==4){
            if (empty($ruleInfo['park_charge_id'])){
                $data['cycle']=1;
            }else{
                $data['cycle']=$ruleInfo['park_charge_id'];
            }
        }
        if (!isset($data['order_add_time']) || empty($data['order_add_time'])) {
            $data['order_add_time'] = 0;
        } else {
           /*if($ruleInfo['bill_create_set']==2){
                $data['order_add_time']=$data['order_add_time'].'-1';
            }elseif($ruleInfo['bill_create_set']==3){
                $data['order_add_time']=$data['order_add_time'].'-1-1';
            }*/
            $data['order_add_time'] = strtotime($data['order_add_time'].' 00:00:00');
        }
        if (!isset($data['custom_value']) || empty($data['custom_value'])) {
            $data['custom_value'] = '';
        }
        $db_bind = new HouseNewChargeStandardBind();

        if ($data['bind_type'] == 1) {
            if (isset($data['single_id']) && empty($data['single_id'])) {
                throw new \think\Exception("楼栋id不能为空");
            }
            if (isset($data['layer_id']) && empty($data['layer_id'])) {
                throw new \think\Exception("楼层id不能为空");
            }

            $single_service = new HouseVillageSingleService();
            // 房间ID
            $vacancy_id = [];
            // 根据收费标准获取所有已绑定的车位对应的业主信息
            $userBind = $single_service->getPositionToVacancy($data['rule_id'],'vacancy_id');
            if(!empty($userBind)){
                $vacancy_id = array_column($userBind,'vacancy_id');
            }
            if (isset($data['vacancy_id']) && empty($data['vacancy_id'])) {
                $where[] = ['status', 'in', '1,2,3'];
                $where[] = ['layer_id', '=', $data['layer_id']];
                if(!empty($vacancy_id)){
                    $where[] = ['pigcms_id', 'not in', $vacancy_id];
                }
                // 查询房间号
                $vacancy = new HouseVillageUserVacancy();
                $list = $vacancy->getList($where, 'pigcms_id as id ,room as name,pigcms_id,room,layer_id');
                if (empty($list)) {
                    throw new \think\Exception("房间id不能为空");
                }
                $list = $list->toArray();
                if (empty($list)) {
                    throw new \think\Exception("房间id不能为空");
                }
                foreach ($list as $v) {
                    $bindInfo = $db_bind->getOne(['rule_id' => $data['rule_id'], 'vacancy_id' => $v['pigcms_id'], 'is_del' => 1]);
                    if (!empty($bindInfo)) {
                        throw new \think\Exception("房间已绑定该收费标准");
                    }
                    if (!empty($data['order_add_time']) && $data['order_add_time'] < $ruleInfo['charge_valid_time']) {
                        throw new \think\Exception("账单生成时间不能小于收费标准生效时间");
                    }
                    if (empty($data['order_add_time'])) {
                        $ruleInfo['bill_create_set'] = 0;
                    }
                    $bind_data = [
                        'rule_id' => $data['rule_id'],
                        'project_id' => $ruleInfo['charge_project_id'],
                        'village_id' => $data['village_id'],
                        'single_id' => $data['single_id'],
                        'floor_id' => $data['floor_id'],
                        'layer_id' => $data['layer_id'],
                        'vacancy_id' => $v['id'],
                        'bind_type' => $data['bind_type'],
                        'order_add_time' => $data['order_add_time'],
                        'order_add_type' => $ruleInfo['bill_create_set'],
                        'custom_value' => $data['custom_value'],
                        'charge_valid_time' => $ruleInfo['charge_valid_time'],
                        'add_time' => time(),
                        'update_time' => time(),
                        'is_del' => 1,
                        'cycle'=>isset($data['cycle'])?$data['cycle']:1,
                        
                    ];
                    if(isset($data['per_one_order']) && !empty($data['per_one_order'])){
                        $bind_data['per_one_order']=$data['per_one_order'];
                    }
                    $id = $db_bind->addOne($bind_data);
                }
            } else {
                if(!empty($vacancy_id) && in_array($data['vacancy_id'],$vacancy_id)){
                    throw new \think\Exception("该收费标准已绑定了该房间对应的车位，不能再次绑定房间");
                }
                $bindInfo = $db_bind->getOne(['rule_id' => $data['rule_id'], 'vacancy_id' => $data['vacancy_id'], 'is_del' => 1]);
                if (!empty($bindInfo)) {
                    throw new \think\Exception("房间已绑定该收费标准");
                }
                // print_r($ruleInfo['charge_valid_time']);echo '<pre>';
                //  print_r($data['order_add_time']);exit;
                if (!empty($data['order_add_time']) && $data['order_add_time'] < $ruleInfo['charge_valid_time']) {
                    throw new \think\Exception("账单生成时间不能小于收费标准生效时间");
                }
                /*if (empty($data['order_add_time'])) {
                    $ruleInfo['bill_create_set'] = 0;
                }*/
                $bind_data = [
                    'rule_id' => $data['rule_id'],
                    'project_id' => $ruleInfo['charge_project_id'],
                    'village_id' => $data['village_id'],
                    'single_id' => $data['single_id'],
                    'floor_id' => $data['floor_id'],
                    'layer_id' => $data['layer_id'],
                    'vacancy_id' => $data['vacancy_id'],
                    'bind_type' => $data['bind_type'],
                    'order_add_time' => $data['order_add_time'],
                    'order_add_type' => $ruleInfo['bill_create_set'],
                    'custom_value' => $data['custom_value'],
                    'charge_valid_time' => $ruleInfo['charge_valid_time'],
                    'add_time' => time(),
                    'update_time' => time(),
                    'is_del' => 1,
                    'cycle'=>isset($data['cycle'])?$data['cycle']:1,
                ];
                if(isset($data['per_one_order']) && !empty($data['per_one_order'])){
                    $bind_data['per_one_order']=$data['per_one_order'];
                }
                $id = $db_bind->addOne($bind_data);
            }

        } else {
            if (empty($data['garage_id'])) {
                throw new \think\Exception("车库id不能为空");
            }
            if (empty($data['position_id'])) {
                throw new \think\Exception("车位id不能为空");
            }

            $parking_service = new HouseVillageParkingService();
            // 房间ID
            $position_id = [];
            // 根据收费标准获取所有已绑定的车位对应的业主信息
            $userBind = $parking_service->getVacancyToPosition($data['rule_id']);
            if(!empty($userBind)){
                $position_id = array_column($userBind,'position_id');
            }
            if(!empty($position_id) && in_array($data['position_id'],$position_id)){
                throw new \think\Exception("该收费标准已绑定了该车位对应的房间，不能再次绑定车位");
            }

            $bindInfo = $db_bind->getOne(['rule_id' => $data['rule_id'], 'position_id' => $data['position_id'], 'is_del' => 1]);
            if (!empty($bindInfo)) {
                throw new \think\Exception("车位已绑定该收费标准");
            }
            /*if (empty($data['order_add_time'])) {
                $ruleInfo['bill_create_set'] = 0;
            }*/
            $bind_data = [
                'rule_id' => $data['rule_id'],
                'project_id' => $ruleInfo['charge_project_id'],
                'village_id' => $data['village_id'],
                'garage_id' => $data['garage_id'],
                'position_id' => $data['position_id'],
                'bind_type' => $data['bind_type'],
                'order_add_time' => $data['order_add_time'],
                'order_add_type' => $ruleInfo['bill_create_set'],
                'custom_value' => $data['custom_value'],
                'charge_valid_time' => $ruleInfo['charge_valid_time'],
                'add_time' => time(),
                'update_time' => time(),
                'is_del' => 1,
                'cycle'=>isset($data['cycle'])?$data['cycle']:1,
            ];
            if(isset($data['per_one_order']) && !empty($data['per_one_order'])){
                $bind_data['per_one_order']=$data['per_one_order'];
            }
            $id = $db_bind->addOne($bind_data);

        }

        if ($id < 1) {
            throw new \think\Exception("绑定失败");
        }
        $res=[];
        if ($id > 0) {
            fdump_api([$id,$data['order_add_time']],'laterLogInQueue_0720',1);
            if ($data['order_add_time']<time()){
                $condition[] = ['b.id','=',$id];
                $data1 = (new HouseNewCashierService())->getBindList($condition,'p.type,b.*,r.bill_type,p.name,r.unit_price,r.rate,r.not_house_rate,r.unit_gage,r.fees_type,n.charge_type,r.unit_gage_type',0);
                if (!empty($data1)){
                    $data1=$data1->toArray();
                }
                fdump_api([$id,$data['order_add_time'],$data1],'laterLogInQueue_0720',1);
                if (!empty($data1)&&$data1[0]['bill_type']==2){
                    $queuData = $data1[0];
                    $queuData['room_id']=$queuData['vacancy_id'];
                    $res['callorder']=(new HouseNewCashierService())->call($queuData);
                }
            }

        }

        $res['id']=$id;
        return $res;
    }

    /**
     * 批量添加收费标准绑定
     * @author:zhubaodi
     * @date_time: 2021/6/17 9:48
     */
    public function addAllStandardBind($data)
    {
        if (empty($data['rule_id'])) {
            throw new \think\Exception("收费标准id不能为空");
        }
        if (empty($data['village_id'])) {
            throw new \think\Exception("小区id不能为空");
        }

        $ids = [];
        $db_rule = new HouseNewChargeRule();
        $ruleInfo = $db_rule->getOne(['id' => $data['rule_id']]);
        if ($ruleInfo && !is_array($ruleInfo)) {
            $ruleInfo = $ruleInfo->toArray();
        }
        if (empty($ruleInfo)) {
            throw new \think\Exception("收费标准信息不存在，无法添加绑定");
        }
        $db_number = new HouseNewChargeNumber();
        $numberInfo = $db_number->get_one(['id' => $ruleInfo['subject_id']]);
        if ($numberInfo && !is_array($numberInfo)) {
            $numberInfo = $numberInfo->toArray();
        }
        if (empty($numberInfo)) {
            throw new \think\Exception("收费科目信息不存在，无法添加绑定");
        }

        $numberIds = $db_number->getColumn(['property_id' => $numberInfo['property_id'],'charge_type'=>$numberInfo['charge_type']],'id');
        $db_project = new HouseNewChargeProject();
        $project_where=[
            ['village_id' ,'=', $data['village_id']],
            ['subject_id','in',$numberIds],
            ['id','<>',$ruleInfo['charge_project_id']],
        ];
        $projectIds = $db_project->getColumn($project_where,'id');
        if (!isset($data['order_add_time']) || empty($data['order_add_time'])) {
            $data['order_add_time'] = 0;
        } else {
            $data['order_add_time'] = strtotime($data['order_add_time'].' 00:00:00');
        }
        if (!isset($data['custom_value']) || empty($data['custom_value'])) {
            $data['custom_value'] = '';
        }
        $db_bind = new HouseNewChargeStandardBind();
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
        $service_house_parking = new HouseVillageParkingService();
        if (!empty($data['order_add_time']) && $data['order_add_time'] < $ruleInfo['charge_valid_time']) {
            throw new \think\Exception("账单生成时间需大于收费标准生效时间");
        }

        $db_house_village_info = new HouseVillageInfo();
        $contract_time=$db_house_village_info->getOne(['village_id'=>$data['village_id']],'contract_time_start,contract_time_end,village_id');
        if (!empty($data['order_add_time'])&&$contract_time['contract_time_end']>1&&$data['order_add_time'] > $contract_time['contract_time_end']) {
            throw new \think\Exception("账单生成时间不能大于合同到期时间");
        }
        if (!empty($data['order_add_time'])&&$contract_time['contract_time_start']>1&&$data['order_add_time'] < $contract_time['contract_time_start']) {
            throw new \think\Exception("账单生成时间不能小于合同开始时间");
        }

        if ($numberInfo['charge_type']=='park_new'&&$ruleInfo['fees_type']==4){
            if (empty($ruleInfo['park_charge_id'])){
                $data['cycle']=1;
            }else{
                $data['cycle']=$ruleInfo['park_charge_id'];
            }
        }
        if (empty($data['cycle'])){
            $data['cycle']=1;
        }
        if (!empty($ruleInfo['cyclicity_set'])&&$ruleInfo['cyclicity_set']<$data['cycle']){
            throw new \think\Exception("收费周期不能大于收费标准的自定义周期");
        }
        $errMsgArr     = [];
        $errMsgStrArr  = [];
        $successMsgArr = [];
        if (!empty($data['pigcms_id'])) {
            $single_service = new HouseVillageSingleService();
            // 房间ID
            $vacancy_id = [];
            // 根据收费标准获取所有已绑定的车位对应的业主信息
            $userBind = $single_service->getPositionToVacancy($data['rule_id'],'vacancy_id');
            if(!empty($userBind)){
                $vacancy_id = array_column($userBind,'vacancy_id');
            }
            fdump_api([$data['pigcms_id'],$vacancy_id],'addAllStandardBind_0712',1);
            $jz_wsxdsj_net_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
            foreach ($data['pigcms_id'] as $v) {
                // 判断该房间对应的车位是否已绑定了该收费规则
                if(!empty($vacancy_id) && in_array($v,$vacancy_id)){
                    $message = '房间的车位已经绑定了改收费规则';
                    $errMsgArr[] = [
                        'bind_type'  => 1,
                        'bind_id'    => $v,
                        'room_id'    => $v,
                        'msg'        => $message,
                    ];
                    $errMsgStrArr[] = $message;
                    continue;
                }
                fdump_api([$v,$projectIds],'addAllStandardBind_0712',1);
                $bindInfo = $db_bind->getOne(['rule_id' => $data['rule_id'], 'vacancy_id' => $v, 'is_del' => 1]);
                if ($bindInfo && !is_array($bindInfo)) {
                    $bindInfo = $bindInfo->toArray();
                }
                if (!empty($bindInfo)) {
                    $message = '房间已绑定该消费标准';
                    $errMsgArr[] = [
                        'bind_type'  => 1,
                        'bind_id'    => $v,
                        'room_id'    => $v,
                        'msg'        => $message,
                    ];
                    $errMsgStrArr[] = $message;
                    continue;
                    //  throw new \think\Exception("房间已绑定该消费标准");
                }
                fdump_api([$v,$bindInfo],'addAllStandardBind_0712',1);
                $vacancy_file = 'village_id,single_id,floor_id,layer_id,housesize';
                if($jz_wsxdsj_net_customized_open){
                    $vacancy_file .= ',heating_size,billable_size';
                }
                $vacancy_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id' => $v, 'is_del' => 0], $vacancy_file);
                if ($vacancy_info && !is_array($vacancy_info)) {
                    $vacancy_info = $vacancy_info->toArray();
                }
                if (empty($vacancy_info)) {
                    $message = '房间不存在或者已经被删除';
                    $errMsgArr[] = [
                        'bind_type'  => 1,
                        'bind_id'    => $v,
                        'room_id'    => $v,
                        'msg'        => $message,
                    ];
                    $errMsgStrArr[] = $message;
                    continue;
                    //  throw new \think\Exception("房间已绑定该消费标准");
                }
                fdump_api([$v,$vacancy_info],'addAllStandardBind_0712',1);
                if($vacancy_info['village_id'] != $data['village_id']){
                    //不是同一小区的
                    $message = '房间不是本小区的';
                    $errMsgArr[] = [
                        'bind_type'  => 1,
                        'bind_id'    => $v,
                        'room_id'    => $v,
                        'msg'        => $message,
                    ];
                    $errMsgStrArr[] = $message;
                    continue;
                }
                fdump_api([$v,$ruleInfo,$vacancy_info['village_id'],$data['village_id']],'addAllStandardBind_0712',1);
                // 判断该房间的车位号是否已绑定
                if($ruleInfo['fees_type'] == 2 && empty($ruleInfo['unit_gage']) && !in_array($ruleInfo['unit_gage_type'],[4,5])){
                    $data['custom_value'] = $vacancy_info['housesize']?$vacancy_info['housesize']:1;
                }elseif($ruleInfo['unit_gage_type'] == 4){
                    $data['custom_value'] = $vacancy_info['heating_size']??0;
                    if($data['custom_value'] <= 0){
                        $message = '供暖面积为0，无法生成账单';
                        $errMsgArr[] = [
                            'bind_type'  => 1,
                            'bind_id'    => $v,
                            'room_id'    => $v,
                            'msg'        => $message,
                        ];
                        $errMsgStrArr[] = $message;
                        continue;
                    }
                }elseif($ruleInfo['unit_gage_type'] == 5){
                    $data['custom_value'] = $vacancy_info['billable_size']??0;
                    if($data['custom_value'] <= 0){
                        $message = '计费面积为0，无法生成账单';
                        $errMsgArr[] = [
                            'bind_type'  => 1,
                            'bind_id'    => $v,
                            'room_id'    => $v,
                            'msg'        => $message,
                        ];
                        $errMsgStrArr[] = $message;
                        continue;
                    }
                }
                if($ruleInfo['fees_type'] == newChargeConst::FEES_TYPE_NUMBER_OF_PARKING_SPACES) {
                    $ruleHasParkNumInfo = $this->getRuleHasParkNumInfo($v, $data['rule_id'], 1, $ruleInfo);
                    if (empty($ruleHasParkNumInfo) || !isset($ruleHasParkNumInfo['parkingNum']) || intval($ruleHasParkNumInfo['parkingNum']) <= 0) {
                        $message = '计费模式为车位数量缺少车位数量，无法生成账单';
                        $errMsgArr[] = [
                            'bind_type'  => 1,
                            'bind_id'    => $v,
                            'room_id'    => $v,
                            'msg'        => $message,
                        ];
                        $errMsgStrArr[] = $message;
                        continue;
//                        return api_output_error(1001,'计费模式为车位数量缺少车位数量，无法生成账单');
                    }
                }
                $data['single_id'] = $vacancy_info['single_id'];
                $data['floor_id'] = $vacancy_info['floor_id'];
                $data['layer_id'] = $vacancy_info['layer_id'];
                $bind_data = [
                    'rule_id' => $data['rule_id'],
                    'project_id' => $ruleInfo['charge_project_id'],
                    'village_id' => $data['village_id'],
                    'single_id' => $data['single_id'],
                    'floor_id' => $data['floor_id'],
                    'layer_id' => $data['layer_id'],
                    'vacancy_id' => $v,
                    'bind_type' => 1,
                    'order_add_time' => $data['order_add_time'],
                    'order_add_type' => $ruleInfo['bill_create_set'],
                    'custom_value' => $data['custom_value'],
                    'charge_valid_time' => $ruleInfo['charge_valid_time'],
                    'add_time' => time(),
                    'update_time' => time(),
                    'is_del' => 1,
                    'cycle'=>$data['cycle'],
                ];
                if(isset($data['per_one_order']) && !empty($data['per_one_order'])){
                    $bind_data['per_one_order']=$data['per_one_order'];
                }
                fdump_api([$bind_data],'addAllStandardBind_0712',1);
                $id = $db_bind->addOne($bind_data);
                fdump_api([$id],'addAllStandardBind_0712',1);
                if ($id > 0) {
                    $ids['vacancy'][] = $id;
                    fdump_api([$id,$data['order_add_time']],'laterLogInQueue_0720',1);
                    if ($data['order_add_time']<time()){
                        $condition=[];
                        $condition[] = ['b.id','=',$id];
                        $data1 = (new HouseNewCashierService())->getBindList($condition,'p.type,b.*,r.bill_type,p.name,r.unit_price,r.rate,r.not_house_rate,r.unit_gage,r.fees_type,n.charge_type,r.unit_gage_type',0);
                        if (!empty($data1)){
                            $data1=$data1->toArray();
                        }
                        fdump_api([$id,$data['order_add_time'],$data1],'laterLogInQueue_0720',1);
                        if (!empty($data1)&&$data1[0]['bill_type']==2){
                            $data1[0]['room_id']=$data1[0]['vacancy_id'];
                            $auto_order=[];
                            $auto_order['rule_id']=$data1[0]['rule_id'];
                            $auto_order['project_id']=$data1[0]['project_id'];
                            $auto_order['room_id']=$data1[0]['room_id'];
                            $auto_order['position_id']=$data1[0]['position_id'];
                            $auto_order['village_id']=$data1[0]['village_id'];
                            $auto_order['add_time']=time();
                            (new HouseNewAutoOrderLog())->addOne($auto_order);
                            $queuData = $data1[0];
                            $this->laterAddPayOrderQueue($queuData);
                        }
                    }
                    $successMsgArr[] = [
                        'bind_type'  => 1,
                        'bind_id'    => $v,
                        'room_id'    => $v,
                        'msg'        => '绑定成功',
                    ];
                } else {
                    $message = '绑定添加失败';
                    $errMsgArr[] = [
                        'bind_type'  => 1,
                        'bind_id'    => $v,
                        'room_id'    => $v,
                        'msg'        => $message,
                    ];
                    $errMsgStrArr[] = $message;
                }
            }
        }
        if (isset($data['position_id']) && !empty($data['position_id'])) {
            $parking_service = new HouseVillageParkingService();
            // 房间ID
            $position_id = [];
            // 根据收费标准获取所有已绑定的车位对应的业主信息
            $userBind = $parking_service->getVacancyToPosition($data['rule_id']);
            if(!empty($userBind)){
                $position_id = array_column($userBind,'position_id');
            }
            foreach ($data['position_id'] as $v) {
                // 判断该车位对应的房间是否已绑定了该收费规则
                if(!empty($position_id) && in_array($v,$position_id)){
                    $message = '车位对应的房间已经绑定了收费规则';
                    $errMsgArr[] = [
                        'bind_type'   => 2,
                        'bind_id'     => $v,
                        'position_id' => $v,
                        'msg'         => $message,
                    ];
                    $errMsgStrArr[] = $message;
                    continue;
                }
                $bindInfo = $db_bind->getOne(['rule_id' => $data['rule_id'], 'position_id' => $v, 'is_del' => 1]);
                if (!empty($bindInfo)) {
                    $message = '车位已绑定该消费标准';
                    $errMsgArr[] = [
                        'bind_type'   => 2,
                        'bind_id'     => $v,
                        'position_id' => $v,
                        'msg'         => $message,
                    ];
                    $errMsgStrArr[] = $message;
                    continue;
                    //   throw new \think\Exception("车位已绑定该消费标准");
                }
                $garage_info = $service_house_parking->getParkingPositionDetail(['pp.position_id' => $v], 'pp.position_num,pg.garage_num,pp.garage_id');
                $data['garage_id'] = $garage_info['detail']['garage_id'];
                $bind_data = [
                    'rule_id' => $data['rule_id'],
                    'project_id' => $ruleInfo['charge_project_id'],
                    'village_id' => $data['village_id'],
                    'garage_id' => $data['garage_id'],
                    'position_id' => $v,
                    'bind_type' => 2,
                    'order_add_time' => $data['order_add_time'],
                    'order_add_type' => $ruleInfo['bill_create_set'],
                    'custom_value' => $data['custom_value'],
                    'charge_valid_time' => $ruleInfo['charge_valid_time'],
                    'add_time' => time(),
                    'update_time' => time(),
                    'is_del' => 1,
                    'cycle'=>$data['cycle'],
                ];
                if(isset($data['per_one_order']) && !empty($data['per_one_order'])){
                    $bind_data['per_one_order']=$data['per_one_order'];
                }
                $id = $db_bind->addOne($bind_data);
                if ($id > 0) {
                    fdump_api([$id,$data['order_add_time']],'laterLogInQueue_0720',1);
                    $ids['position'][] = $id;
                    if ($data['order_add_time']<time()){
                        $condition=[];
                        $condition[] = ['b.id','=',$id];
                        $data1 = (new HouseNewCashierService())->getBindList($condition,'p.type,b.*,r.bill_type,p.name,r.unit_price,r.rate,r.not_house_rate,r.unit_gage,r.fees_type,n.charge_type,r.unit_gage_type',0);
                        fdump_api([$id,$data['order_add_time'],$data1],'laterLogInQueue_0720',1);
                        if (!empty($data1)){
                            $data1=$data1->toArray();
                        }
                        if (!empty($data1)&&$data1[0]['bill_type']==2){

                            $data1[0]['room_id']=$data1[0]['vacancy_id'];

                            $auto_order=[];
                            $auto_order['rule_id']=$data1[0]['rule_id'];
                            $auto_order['project_id']=$data1[0]['project_id'];
                            $auto_order['room_id']=$data1[0]['vacancy_id'];
                            $auto_order['position_id']=$data1[0]['position_id'];
                            $auto_order['village_id']=$data1[0]['village_id'];
                            $auto_order['add_time']=time();
                            (new HouseNewAutoOrderLog())->addOne($auto_order);
                            $queuData = $data1[0];
                            $this->laterAddPayOrderQueue($queuData);
                        }
                    }
                    $successMsgArr[] = [
                        'bind_type'   => 2,
                        'bind_id'     => $v,
                        'position_id' => $v,
                        'msg'         => '绑定成功',
                    ];
                } else {
                    $message = '绑定添加失败';
                    $errMsgArr[] = [
                        'bind_type'   => 2,
                        'bind_id'     => $v,
                        'position_id' => $v,
                        'msg'         => $message,
                    ];
                    $errMsgStrArr[] = $message;
                }
            }

        }
        fdump_api([$ids],'addAllStandardBind_0712',1);
        if (empty($errMsgArr) && (empty($ids)||(isset($ids['vacancy'])&&empty($ids['vacancy'])))){
            throw new \think\Exception("绑定失败,对应房间已绑定");
        }
        if (empty($errMsgArr) && (empty($ids)||(isset($ids['position'])&&empty($ids['position'])))){
            throw new \think\Exception("绑定失败，对应车位已绑定");
        }
        fdump_api([$ids],'addAllStandardBind_0712',1);
        if (empty($errMsgStrArr)) {
            $errMsgStr = '';
        } else {
            $errMsgStrArr = array_unique($errMsgStrArr);
            $errMsgStr = implode(';', $errMsgStrArr);
        }
        $arr = [];
        $arr['ids']           = $ids;
        $arr['successMsgArr'] = $successMsgArr;
        $arr['errMsgArr']     = $errMsgArr;
        $arr['errMsgStr']     = $errMsgStr;
        $arr['success_count'] = count($successMsgArr);
        $arr['err_count']     = count($errMsgArr);
        return $arr;
    }
    //队列处理批量绑定
    public function houseMassChargeStandardBind($param=array()){
        set_time_limit(0);
        fdump_api(['start_param'=>$param],'00houseMassChargeStandardBind',1);
        if(empty($param) || !isset($param['pigcms_arr']) || empty($param['pigcms_arr']) || !isset($param['village_id']) || ($param['village_id']<1)){
            return false;
        }
        if (!isset($param['rule_id']) || ($param['rule_id']<1)) {
            return false;
        }
        $db_rule = new HouseNewChargeRule();
        $whereArr=array(['id','=',$param['rule_id']]);
        $whereArr[]=array('status','<>',4);
        $ruleInfo = $db_rule->getOne($whereArr);
        if ($ruleInfo && !is_array($ruleInfo)) {
            $ruleInfo = $ruleInfo->toArray();
        }
        if (empty($ruleInfo)) {
            return false;
        }
        $db_number = new HouseNewChargeNumber();
        $numberInfo = $db_number->get_one(['id' => $ruleInfo['subject_id']]);
        if ($numberInfo && !is_array($numberInfo)) {
            $numberInfo = $numberInfo->toArray();
        }
        if (empty($numberInfo)) {
            return false;
        }

        if (!isset($param['order_add_time']) || empty($param['order_add_time'])) {
            $param['order_add_time'] = 0;
        } else {
            $param['order_add_time'] = strtotime($param['order_add_time'].' 00:00:00');
        }
        if (!empty($param['order_add_time']) && $param['order_add_time'] < $ruleInfo['charge_valid_time']) {
            fdump_api(['errMsgArr'=>'账单生成时间需大于收费标准生效时间','ruleInfo'=>$ruleInfo],'00houseMassChargeStandardBind',1);
            return false;
        }

        $db_house_village_info = new HouseVillageInfo();
        $contract_time=$db_house_village_info->getOne(['village_id'=>$param['village_id']],'contract_time_start,contract_time_end,village_id');
        if (!empty($param['order_add_time']) && $contract_time['contract_time_end']>1 && $param['order_add_time'] > $contract_time['contract_time_end']) {
            fdump_api(['errMsgArr'=>'账单生成时间不能大于合同到期时间','contract_time_end'=>$contract_time['contract_time_end']],'00houseMassChargeStandardBind',1);
            return false;
        }
        if (!empty($param['order_add_time'])&&$contract_time['contract_time_start']>1&&$param['order_add_time'] < $contract_time['contract_time_start']) {
            fdump_api(['errMsgArr'=>'账单生成时间不能小于合同开始时间','contract_time_start'=>$contract_time['contract_time_start']],'00houseMassChargeStandardBind',1);
            return false;
        }
        $vacancy = new HouseVillageUserVacancy();
        $vacancy_id=[];
        if ($numberInfo['charge_type']=='park_new'&&$ruleInfo['fees_type']==4){
            if (empty($ruleInfo['park_charge_id'])){
                $param['cycle']=1;
            }else{
                $param['cycle']=$ruleInfo['park_charge_id'];
            }
        }
        if (!isset($param['cycle']) || empty($param['cycle'])){
            $param['cycle']=1;
        }
        $errMsgArr     = [];
        $successMsgArr  =[];
        $ids=array();
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
        foreach ($param['pigcms_arr'] as $v) {
            $whereArr=array();
            $whereArr[]=array('village_id','=',$param['village_id']);
            $whereArr[]=array('single_id','=',$v['single_id']);
            $whereArr[] = ['status', 'in', array(1,2,3)];
            $whereArr[] = ['is_del', '=', 0];
            if(isset($v['layer_id']) && !empty($v['layer_id'])){
                if(!is_array($v['layer_id']) && is_numeric($v['layer_id'])) {
                    $whereArr[] = ['layer_id', '=', $v['layer_id']];
                }else{
                    $whereArr[] = ['layer_id', 'in', $v['layer_id']];
                }
            }
            if(isset($v['floor_id']) && !empty($v['floor_id'])){
                if(!is_array($v['floor_id']) && is_numeric($v['floor_id'])){
                    $whereArr[] = ['floor_id', '=', $v['floor_id']];
                }else{
                    $whereArr[] = ['floor_id', 'in', $v['floor_id']];
                }
            }
            // 查询房间号
            $list = $vacancy->getList($whereArr, 'pigcms_id');
            if (!empty($list)){
                $list=$list->toArray();
                if (!empty($list)){
                    foreach ($list as $vv){
                        $vacancy_id[]=$vv['pigcms_id'];
                    }
                }
            }
        }
        if($vacancy_id){
            $db_bind = new HouseNewChargeStandardBind();
            $vacancy_id=array_unique($vacancy_id);
            $jz_wsxdsj_net_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
            foreach ($vacancy_id as $v) {
                $bindInfo = $db_bind->getOne(['rule_id' => $param['rule_id'], 'vacancy_id' => $v, 'is_del' => 1]);
                if ($bindInfo && !is_array($bindInfo)) {
                    $bindInfo = $bindInfo->toArray();
                }
                if (!empty($bindInfo)) {
                    $message = '房间已绑定该消费标准';
                    $errMsgArr[] = [
                        'bind_id'    => $bindInfo['id'],
                        'room_id'    => $v,
                        'msg'        => $message,
                        'village_id'=>$param['village_id'],
                    ];
                    continue;
                }
                $vacancy_file = 'village_id,single_id,floor_id,layer_id,housesize';
                if($jz_wsxdsj_net_customized_open){
                    $vacancy_file .= ',heating_size,billable_size';
                }
                $vacancy_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id' => $v, 'is_del' => 0], $vacancy_file);
                if ($vacancy_info && !is_array($vacancy_info)) {
                    $vacancy_info = $vacancy_info->toArray();
                }
                if (empty($vacancy_info)) {
                    $message = '房间不存在或者已经被删除';
                    $errMsgArr[] = [
                        'village_id'=>$param['village_id'],
                        'bind_id'    => 0,
                        'room_id'    => $v,
                        'msg'        => $message,
                    ];
                    continue;
                }
                if($vacancy_info['village_id'] != $param['village_id']){
                    //不是同一小区的
                    $message = '房间不是本小区的';
                    $errMsgArr[] = [
                        'village_id'=>$param['village_id'],
                        'bind_id'    => 0,
                        'room_id'    => $v,
                        'msg'        => $message,
                    ];
                    continue;
                }
                // 判断该房间的车位号是否已绑定
                if($ruleInfo['fees_type'] == 2 && empty($ruleInfo['unit_gage'])){
                    $param['custom_value'] = $vacancy_info['housesize']?$vacancy_info['housesize']:1;
                }elseif($ruleInfo['unit_gage_type'] == 4){
                    $data['custom_value'] = $vacancy_info['heating_size']??0;
                    if($data['custom_value'] <= 0){
                        $message = '供暖面积为0，无法生成账单';
                        $errMsgArr[] = [
                            'village_id'=>$param['village_id'],
                            'bind_id'    => 0,
                            'room_id'    => $v,
                            'msg'        => $message,
                        ];
                        continue;
                    }
                }elseif($ruleInfo['unit_gage_type'] == 5){
                    $data['custom_value'] = $vacancy_info['billable_size']??0;
                    if($data['custom_value'] <= 0){
                        $message = '计费面积为0，无法生成账单';
                        $errMsgArr[] = [
                            'village_id'=>$param['village_id'],
                            'bind_id'    => 0,
                            'room_id'    => $v,
                            'msg'        => $message,
                        ];
                        continue;
                    }
                }
                if($ruleInfo['fees_type'] == newChargeConst::FEES_TYPE_NUMBER_OF_PARKING_SPACES) {
                    $ruleHasParkNumInfo = $this->getRuleHasParkNumInfo($v, $param['rule_id'], 1, $ruleInfo);
                    if (empty($ruleHasParkNumInfo) || !isset($ruleHasParkNumInfo['parkingNum']) || intval($ruleHasParkNumInfo['parkingNum']) <= 0) {
                        $message = '计费模式为车位数量缺少车位数量，无法生成账单';
                        $errMsgArr[] = [
                            'village_id'=>$param['village_id'],
                            'bind_id'    => 0,
                            'room_id'    => $v,
                            'msg'        => $message,
                        ];
                        continue;
                    }
                }
                $param['single_id'] = $vacancy_info['single_id'];
                $param['floor_id'] = $vacancy_info['floor_id'];
                $param['layer_id'] = $vacancy_info['layer_id'];
                $bind_data = [
                    'rule_id' => $param['rule_id'],
                    'project_id' => $ruleInfo['charge_project_id'],
                    'village_id' => $param['village_id'],
                    'single_id' => $param['single_id'],
                    'floor_id' => $param['floor_id'],
                    'layer_id' => $param['layer_id'],
                    'vacancy_id' => $v,
                    'bind_type' => 1,
                    'order_add_time' => $param['order_add_time'],
                    'order_add_type' => $ruleInfo['bill_create_set'],
                    'custom_value' => $param['custom_value'],
                    'charge_valid_time' => $ruleInfo['charge_valid_time'],
                    'add_time' => time(),
                    'update_time' => time(),
                    'is_del' => 1,
                    'cycle'=>$param['cycle'],
                ];
                if(isset($param['per_one_order']) && !empty($param['per_one_order'])){
                    $bind_data['per_one_order']=$param['per_one_order'];
                }
                $id = $db_bind->addOne($bind_data);
                if ($id > 0) {
                    $ids[] = $id;
                    if ($param['order_add_time']<time()){
                        $condition=[];
                        $condition[] = ['b.id','=',$id];
                        $data1 = (new HouseNewCashierService())->getBindList($condition,'p.type,b.*,r.bill_type,p.name,r.unit_price,r.rate,r.not_house_rate,r.unit_gage,r.fees_type,n.charge_type,r.unit_gage_type',0);
                        if (!empty($data1) && !$data1->isEmpty){
                            $data1=$data1->toArray();
                        }else{
                            $data1=array();
                        }
                        fdump_api([$id,$param['order_add_time'],$data1],'00houseMassChargeStandardBind',1);
                        if (!empty($data1)&&$data1[0]['bill_type']==2){
                            $data1[0]['room_id']=$data1[0]['vacancy_id'];
                            $auto_order=[];
                            $auto_order['rule_id']=$data1[0]['rule_id'];
                            $auto_order['project_id']=$data1[0]['project_id'];
                            $auto_order['room_id']=$data1[0]['room_id'];
                            $auto_order['position_id']=$data1[0]['position_id'];
                            $auto_order['village_id']=$data1[0]['village_id'];
                            $auto_order['add_time']=time();
                            (new HouseNewAutoOrderLog())->addOne($auto_order);
                            $queuData = $data1[0];
                            $this->laterAddPayOrderQueue($queuData);
                        }
                    }
                    $successMsgArr[] = [
                        'village_id'=>$param['village_id'],
                        'bind_id'    => $id,
                        'room_id'    => $v,
                        'msg'        => '绑定成功',
                    ];
                } else {
                    $message = '绑定添加失败';
                    $errMsgArr[] = [
                        'village_id'=>$param['village_id'],
                        'bind_id'    =>0,
                        'room_id'    => $v,
                        'msg'        => $message,
                    ];
                }
            }
        }
        fdump_api(['successMsgArr'=>$successMsgArr,'errMsgArr'=>$errMsgArr,'param'=>$param],'00houseMassChargeStandardBind',1);
        return true;
    }
    
    //手动批量给标准绑定的房间生成账单
    public function createManyOrderByRuleId($datas = array())
    {
        if (empty($datas) || empty($datas['rule_id'])) {
            throw new \think\Exception("收费标准id不能为空");
        }
        if (empty($datas) || empty($datas['village_id'])) {
            throw new \think\Exception("小区id不能为空");
        }
        /*
        if (empty($datas)||empty($datas['single_data'])) {
            throw new \think\Exception("请选择需要操作的楼栋数据");
        }
        */
        $db_rule = new HouseNewChargeRule();
        $ruleInfoObj = $db_rule->getDetail(['r.id' => $datas['rule_id'], 'r.village_id' => $datas['village_id']], 'r.*,n.charge_type,p.type,p.name as order_name');
        $ruleInfo = array();
        if (!empty($ruleInfoObj) && !$ruleInfoObj->isEmpty()) {
            $ruleInfo = $ruleInfoObj->toArray();
        }
        if (empty($ruleInfo)) {
            throw new \think\Exception("收费标准信息不存在。");
        }
        fdump_api(['datas' => $datas], '000createNewPayOrder', 1);
        $extra = $datas;
        unset($extra['single_data']);
        $extra['ruleInfo'] = $ruleInfo;
        $single_data = array();
        /*
        if (isset($datas['single_data']) && !empty($datas['single_data'])) {
            foreach ($datas['single_data'] as $vv) {
                if (is_array($vv) && isset($vv['single_id']) && isset($single_data[$vv['single_id']])) {
                    $vv['single_id'] = trim($vv['single_id']);
                    if (!empty($single_data[$vv['single_id']]['floor_id']) && isset($vv['floor_id']) && !empty($vv['floor_id'])) {
                        $single_data[$vv['single_id']]['floor_id'] = array_merge($single_data[$vv['single_id']]['floor_id'], $vv['floor_id']);
                    } elseif (!empty($single_data[$vv['single_id']]['floor_id']) && (!isset($vv['floor_id']) || empty($vv['floor_id']))) {
                        //有 没有选单元的 即 整栋楼
                        $single_data[$vv['single_id']]['floor_id'] = array();
                    }
                } elseif (is_array($vv) && isset($vv['single_id']) && !empty($vv['single_id']) && !isset($single_data[$vv['single_id']])) {
                    $vv['single_id'] = trim($vv['single_id']);
                    $single_data[$vv['single_id']] = array('single_id' => $vv['single_id'], 'floor_id' => array());
                    if (isset($vv['floor_id']) && !empty($vv['floor_id'])) {
                        $single_data[$vv['single_id']]['floor_id'] = $vv['floor_id'];
                    }
                }
            }

            if (empty($single_data)) {
                throw new \think\Exception("请选择需要操作的楼栋数据！");
            }
        }
        */
        $db_bind = new HouseNewChargeStandardBind();
        $fieldStr = '*';
        $houseNewPayOrderService = new HouseNewPayOrderService();
        $count = 0;
        $standard_bind_count=0;

        if (false && $single_data) {
            foreach ($single_data as $skey => $svv) {
                $whereArr = array();
                $whereArr[] = array('village_id', '=', $datas['village_id']);
                $whereArr[] = array('rule_id', '=', $datas['rule_id']);
                $whereArr[] = array('single_id', '=', $svv['single_id']);
                $whereArr[] = array('is_del', '=', 1);
                if (isset($svv['floor_id']) && !empty($svv['floor_id'])) {
                    $floor_id = array_unique($svv['floor_id']);
                    $whereArr[] = array('floor_id', 'in', $floor_id);
                }
                $standard_bind_obj = $db_bind->getLists1($whereArr, $fieldStr);
                $standard_bind = array();
                if ($standard_bind_obj && is_object($standard_bind_obj) && !$standard_bind_obj->isEmpty()) {
                    $standard_bind = $standard_bind_obj->toArray();
                } else if ($standard_bind_obj && is_array($standard_bind_obj)) {
                    $standard_bind = $standard_bind_obj;
                }
                if (!empty($standard_bind)) {
                    foreach ($standard_bind as $bindV) {
                        $standard_bind_count++;
                        $is_allow = $this->checkChargeValid($bindV['project_id'], $bindV['rule_id'], $bindV['vacancy_id'], 1);
                        if (!$is_allow) {
                            fdump_api(['bindV' => $bindV, 'msg' => '当前收费标准未生效'], '000createNewPayOrder', 1);
                            continue;
                        }
                        $rets = $houseNewPayOrderService->manualCreateNewPayOrder($bindV, $extra);
                        $count++;
                        fdump_api(['bindV' => $bindV, 'rets' => $rets], '000createNewPayOrder', 1);
                    }
                }
            }
        } else {
            $bind_type=1;

            $whereArr = array();
            $whereArr[] = array('village_id', '=', $datas['village_id']);
            $whereArr[] = array('rule_id', '=', $datas['rule_id']);
            if(isset($extra['charge_type']) && $extra['charge_type']=='park_new'){
                $bind_type=2;
                $whereArr[] = array('position_id', '>', 0);
            }else{
                $whereArr[] = array('single_id', '>', 0);
            }
            $whereArr[] = array('is_del', '=', 1);
            $standard_bind_obj = $db_bind->getLists1($whereArr, $fieldStr);
            $standard_bind = array();
            if ($standard_bind_obj && is_object($standard_bind_obj) && !$standard_bind_obj->isEmpty()) {
                $standard_bind = $standard_bind_obj->toArray();
            } else if ($standard_bind_obj && is_array($standard_bind_obj)) {
                $standard_bind = $standard_bind_obj;
            }
            if (!empty($standard_bind)) {
                foreach ($standard_bind as $bindV) {
                    $standard_bind_count++;
                    $bind_id_tmp=$bindV['vacancy_id']>0 ? $bindV['vacancy_id']:$bindV['position_id'];
                    if($bind_type==2){
                        $bind_id_tmp=$bindV['position_id'];
                    }
                    $is_allow = $this->checkChargeValid($bindV['project_id'], $bindV['rule_id'], $bind_id_tmp, $bind_type);
                    if (!$is_allow) {
                        fdump_api(['bindV' => $bindV, 'msg' => '当前收费标准未生效'], '000createNewPayOrder', 1);
                        continue;
                    }
                    $rets = $houseNewPayOrderService->manualCreateNewPayOrder($bindV, $extra);
                    if($rets && $rets['status']==0){
                        $count++;
                    }
                    fdump_api(['bindV' => $bindV, 'rets' => $rets], '000createNewPayOrder', 1);
                }
            }
        }
        
        return array('ordercount'=>$count,'standard_bind_count'=>$standard_bind_count,'status'=>0);
    }
    /**
     * 删除收费标准绑定
     * @author:zhubaodi
     * @date_time: 2021/6/17 9:48
     */
    public function delStandardBind($data)
    {
        if (empty($data['bind_id'])) {
            throw new \think\Exception("收费标准绑定id不能为空");
        }
        $where[] = ['id', '=', $data['bind_id']];
        $where[] = ['is_del', '=', 1];

        $db_bind = new HouseNewChargeStandardBind();
        $bindInfo = $db_bind->getOne($where);
        if (empty($bindInfo)) {
            throw new \think\Exception("绑定信息不存在，无法解绑");
        }
        $res = $db_bind->saveOne($where, ['is_del' => 4]);
        return $res;
    }



    /**
     * 校验收费标准有效期
     * @author: liukezhu
     * @date : 2021/9/15
     * @param $project_id  收费项目id
     * @param $rule_id     收费标准id
     * @param $bind_id
     * @param $bind_type
     * @return bool
     */
    public function checkChargeValid($project_id,$rule_id,$bind_id,$bind_type){
        $where=array();
        $where[] = ['b.is_del', '=', 1];
        $where[] = ['b.project_id', '=', $project_id];
        $where[] = ['r.status', '=', 1];
        //1:绑定房间 2:绑定车位
        if($bind_type == 1){
            $where[] = ['b.vacancy_id', '=', $bind_id];
        }else{
            $where[] = ['b.position_id', '=', $bind_id];
        }
        $rule_id=intval($rule_id);
        $nowtime=time();
        $status=false;
        $bind_list=$this->HouseNewChargeStandardBind->getLists2($where,'b.project_id,b.rule_id,r.charge_valid_time','r.charge_valid_time desc');
        if(empty($bind_list) || $bind_list->isEmpty()){
           return $status;
        }
        $bind_list=$bind_list->toArray();
        $rule_ids=0;
        foreach ($bind_list as $v){
            if($v['charge_valid_time'] <= $nowtime){
                $rule_ids=intval($v['rule_id']);
                break;
            }
        }
        /*
        if(count($bind_list) == 1){
            if($bind_list[0]['charge_valid_time'] <= $time ){
                $rule_ids=$bind_list[0]['rule_id'];
            }
        }else{
            $where[] = ['charge_valid_time', '<=', $time];
            $bind_list=$this->HouseNewChargeStandardBind->getLists1($where,'project_id,rule_id,charge_valid_time','charge_valid_time desc');
            foreach ($bind_list as $v){
                if($v['charge_valid_time'] <= $time){
                    $rule_ids=$v['rule_id'];
                    break;
                }
            }
            
        }
        */
        if($rule_ids>0 && $rule_ids == $rule_id){
            $status=true;
        }
        return $status;
    }

    /**
     * 获取绑定详情
     * @param array $where
     * @param bool $field
     * @return mixed
     * @author lijie
     * @date_time 2021/06/22
     */
    public function getBindDetail($where = [], $field = true)
    {
        $data = $this->HouseNewChargeStandardBind->getOne($where, $field);
        return $data;
    }
    public function getChargeProjectNumber($where = [], $field = true)
    {
        $data = $this->HouseNewChargeProject->getFind($where, $field);
        return $data;
    }

    /**
     *
     * @param array $where
     * @param bool $field
     * @return mixed
     * @author lijie
     * @date_time 2021/06/22
     */
    public function getCallInfo($where = [], $field = true)
    {
        $data = $this->HouseNewChargeRule->getDetail($where, $field);
        return $data;
    }

    public function getRuleInfo($id)
    {
        if (empty($id)) {
            throw new \think\Exception("收费标准id不能为空");
        }
        $db_rule = new HouseNewChargeRule();
        $ruleInfo = $db_rule->getOne(['id' => $id]);
        if ($ruleInfo && !is_array($ruleInfo)) {
            $ruleInfo = $ruleInfo->toArray();
        }
        if (empty($ruleInfo)) {
            throw new \think\Exception("收费标准信息不存在");
        }
        $db_project = new HouseNewChargeProject();
        $db_house_new_charge_number = new HouseNewChargeNumber();
        $projectInfo = $db_project->getOne(['id' => $ruleInfo['charge_project_id']]);
        if (empty($projectInfo)) {
            throw new \think\Exception("收费项目信息不存在");
        }
        $numberInfo =$db_house_new_charge_number->get_one(['id'=>$projectInfo['subject_id']]);
        $ruleInfo['charge_valid_time1'] = date('Y-m-d', $ruleInfo['charge_valid_time']);
        $ruleInfo['charge_type'] = $projectInfo['type'];
        $ruleInfo['project_name'] = $projectInfo['name'];
        if ($numberInfo['charge_type']=='deposit_new'){
            $ruleInfo['charge_type'] = 1;
        }else{
            $ruleInfo['charge_type'] = $projectInfo['type'];
        }
        $ruleInfo['order_type'] = $numberInfo['charge_type'];

        // 详情
        $param = array(
            'village_id' => $ruleInfo['village_id'],
            'charge_project_id' => $ruleInfo['charge_project_id']
        );

        $ruleParam = self::ruleParam($param);
        if ($ruleInfo['fees_type'] == 2) {
            $ruleParam['is_unit_gage'] = true;
        } else {
            $ruleParam['is_unit_gage'] = false;
        }
        if ($ruleInfo['is_prepaid'] == 2) {
            $ruleParam['is_prepaid_button'] = false;
        } else {
            $ruleParam['is_prepaid_button'] = true;
        }
        $ruleInfo['ruleParam'] = $ruleParam;
        $ruleArr = [
            'charge_name' => [
                'title' => '收费标准名称'
            ],
            'fees_type' => [
                'title' => '计费模式',
                '0' => '',
                '1' => '固定费用',
                '2' => '单价计量单位',
                '3' => '临时车收费标准',
                '4' => '月租车收费标准'
            ],
            'unit_gage' => [
                'title' => '计量单位'
            ],
            'charge_price' => [
                'title' => '收费金额'
            ],
            'cyclicity_set' => [
                'title' => '周期性费用设置',
                '0' => '无限期'
            ],
            'unit_price' => [
                'title' => '计费单价'
            ],
            'rate' => [
                'title' => '倍率'
            ],
            'bill_create_set' => [
                'title' => '账单生成周期设置',
                '0' => '',
                '1' => '按日生成',
                '2' => '按月生成',
                '3' => '按年生成'
            ],
            'bill_arrears_set' => [
                'title' => '账单欠费模式',
                '0' => '',
                '1' => '预生成',
                '2' => '后生成'
            ],
            'bill_type' => [
                'title' => '生成账单模式',
                '0' => '',
                '1' => '手动生成',
                '2' => '自动生成'
            ],
            'is_prepaid' => [
                'title' => '是否支持预缴',
                '0' => '',
                '1' => '支持',
                '2' => '不支持'
            ],
            'not_house_rate' => [
                'title' => '未入住房屋折扣'
            ],
            'charge_valid_time' => [
                'title' => '费用标准生效时间'
            ]
        ];
        $ruleArr2 = [
            'late_fee_reckon_day' => [
                'title' => '计算时间'
            ],
            'late_fee_top_day' => [
                'title' => '费用收取封顶天数'
            ],
            'late_fee_rate' => [
                'title' => '违约金收取比例（每天）'
            ]
        ];
        $configCustomizationService=new ConfigCustomizationService();
        $is_grapefruit_prepaid=$configCustomizationService->getHzhouGrapefruitOrderJudge();
        $special_arr = ['fees_type','bill_create_set','bill_arrears_set','bill_type','is_prepaid','cyclicity_set'];
        foreach ($ruleInfo as $key => $val){
            if(isset($ruleArr[$key])){
                if ($key  == 'charge_valid_time'){
                    $value = $ruleInfo['charge_valid_time1'];
                }elseif ($key  == 'not_house_rate' || $key  == 'rate'){
                    $value = $val.'%';
                }elseif ($key == 'charge_price' || $key == 'unit_price'){
                    $val = $val*1;
                    $value = $val.'元';
                    if($is_grapefruit_prepaid==1 && $ruleInfo['fees_type']==2 && $ruleInfo['bill_create_set']==3){
                        $value= $val*1;
                        $permonth=$val/12;
                        $permonth=round($permonth,4) *1;
                        $value .='（'.$permonth.' * 12个月）元';
                    }
                }elseif(($key  == 'cyclicity_set' && $val != 0) || !in_array($key,$special_arr) ){
                    $value = $val;
                }else{
                    $value = isset($ruleArr[$key][$val]) ? $ruleArr[$key][$val]:0;
                }
                $is_show = true;
                if(isset($ruleParam['is_'.$key])){
                    $is_show = $ruleParam['is_'.$key];
                }
                if($key == 'cyclicity_set'){
                    $is_show = $ruleParam['is_cyclicity_type_set'];
                }
                $temp = [
                    'title' => $ruleArr[$key]['title'],
                    'value' => $value,
                    'is_show' => $is_show // 是否展示
                ];
                $ruleInfo['ruleList'][] = $temp;
            }
            if(isset($ruleArr2[$key])){
                if($key == 'late_fee_reckon_day'){
                    $v = '账单生成后'. $val .'（天）开始计算违约金';
                    if($val == 0){
                        $v = '未设置违约金';
                    }
                }elseif ($key == 'late_fee_rate'){
                    $v = $val.'%';
                }else{
                    $v = $val;
                }
                $temp = [
                    'title' => $ruleArr2[$key]['title'],
                    'value' => $v,
                ];
                $ruleInfo['ruleLateList'][] = $temp;
            }
        }

        return $ruleInfo;
    }

    /**
     * 收费标准详情
     * @param array $where
     * @param bool $field
     * @param int $room_id
     * @param int $position_id
     * @return array|\think\Model|null
     * @author lijie
     * @date_time 2021/07/26
     */
    public function get_rule_info($where = [], $field = true, $room_id = 0, $position_id = 0)
    {
        $db_rule = new HouseNewChargeRule();
        $db_house_new_pay_order = new HouseNewPayOrder();
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
        $service_house_new_charge_rule = new HouseNewChargeRuleService();
        $ruleInfo = $db_rule->getOne($where, $field);
        if ($ruleInfo) {
            if (!$position_id && $room_id) {
                $whereArrTmp=array();
                $whereArrTmp[]=array('pigcms_id','=',$room_id);
                $whereArrTmp[]=array('user_status','=',2);  // 2未入住
                $whereArrTmp[]=array('status','in',[1,2,3]);
                $whereArrTmp[]=array('is_del','=',0);
                $vacancy_info = $service_house_village_user_vacancy->getUserVacancyInfo($whereArrTmp, 'user_status');
                $not_house_rate = 1;
                if($vacancy_info && !$vacancy_info->isEmpty()){
                    $vacancy_info = $vacancy_info->toArray();
                    if(!empty($vacancy_info)){
                        $not_house_rate = $ruleInfo['not_house_rate']/100;
                    }
                }
                $projectBindInfo = $service_house_new_charge_rule->getBindDetail(['project_id' => $ruleInfo['charge_project_id'], 'rule_id' => $ruleInfo['id'], 'vacancy_id' => $room_id]);
            } else {
                $not_house_rate = 1;
                $projectBindInfo = $service_house_new_charge_rule->getBindDetail(['project_id' => $ruleInfo['charge_project_id'], 'rule_id' => $ruleInfo['id'], 'position_id' => $position_id]);
            }
            if (isset($projectBindInfo) && !empty($projectBindInfo['custom_value'])) {
                $custom_value = $projectBindInfo['custom_value'];
            } else {
                $custom_value = 1;
            }
     
            if ($ruleInfo['fees_type'] == 2) {
                $ruleInfo['money'] = $ruleInfo['unit_price'] * $ruleInfo['rate'] * $custom_value * $not_house_rate;
                if(empty($ruleInfo['unit_gage'])){
                    $ruleInfo['custom_value'] = $custom_value.'m²';
                    $ruleInfo['unit_gage'] = '房屋面积';
                }else{
                    $ruleInfo['custom_value'] = $custom_value;
                }
            } else {
                $ruleInfo['money'] = $ruleInfo['unit_price'] * $ruleInfo['rate'] * $not_house_rate;
                $ruleInfo['custom_value'] = 1;
            }
            if ($ruleInfo['bill_create_set'] == 1) {
                $ruleInfo['unit_price'] = $ruleInfo['unit_price'] . '元/日';
                $ruleInfo['money'] = $ruleInfo['money'] . '元/日';
            } elseif ($ruleInfo['bill_create_set'] == 2) {
                $ruleInfo['unit_price'] = $ruleInfo['unit_price'] . '元/月';
                $ruleInfo['money'] = $ruleInfo['money'] . '元/月';
            } else {
                $ruleInfo['unit_price'] = $ruleInfo['unit_price'] . '元/年';
                $ruleInfo['money'] = $ruleInfo['money'] . '元/年';
            }
            if ($position_id)
                $res = $db_house_new_pay_order->get_one(['is_paid' => 1, 'is_refund' => 1, 'is_discard' => 1, 'position_id' => $position_id, 'project_id' => $ruleInfo['charge_project_id']], 'service_end_time,order_type');
            else
                $res = $db_house_new_pay_order->get_one(['is_paid' => 1, 'is_refund' => 1, 'is_discard' => 1, 'room_id' => $room_id, 'project_id' => $ruleInfo['charge_project_id'],'position_id'=>0], 'service_end_time,order_type');
            if ($res){
                $ruleInfo['service_end_time'] = $res['service_end_time'];
                $ruleInfo['order_type']=$res['order_type'];
            }else{
                $ruleInfo['service_end_time'] = time();
                //针对查询不到order_type 通过收费项目查询order_type
                $rel=$this->HouseNewChargeProject->getProjectColumn([
                    ['p.id','=',$ruleInfo['charge_project_id']]
                ], 'c.charge_type');
                if($rel){
                    $ruleInfo['order_type']=$rel[0];
                }
            }
            if($room_id){
                $rel=(new HouseNewOrderLog())->getOne([
                    ['order_type','=',$ruleInfo['order_type']],
                    ['room_id','=',$room_id]
                ],'service_end_time');
                if($rel){
                    $ruleInfo['service_end_time']=$rel['service_end_time'];
                }
            }
            if ($ruleInfo['bill_create_set'] == 1) {
                $ruleInfo['service_end_time'] = date('Y-m-d',$ruleInfo['service_end_time']);
            } elseif ($ruleInfo['bill_create_set'] == 2) {
                $ruleInfo['service_end_time'] = date('Y-m',$ruleInfo['service_end_time']);
            } else {
                $ruleInfo['service_end_time'] = date('Y',$ruleInfo['service_end_time']);
            }
        }
        return $ruleInfo;
    }


    /**
     * 获取有效收费规则
     * @param $project_id 收费项目id
     * @return int
     * @author: liukezhu
     * @date : 2021/8/16
     */
    public function getValidChargeRule($project_id)
    {
        $where[] = ['status', '=', 1];
        $where[] = ['charge_project_id', '=', $project_id];
        $where[] = ['charge_valid_time', '<=', time()];
        $list = $this->HouseNewChargeRule->getList($where, 'id', 'charge_valid_type asc,charge_valid_time desc',0,2)->toArray();
        $id = 0;
        if (isset($list[0]['id']) && !empty($list[0]['id'])) {
            $id = $list[0]['id'];
        }
        return $id;
    }

    /**
     * 根据楼栋楼层获取房间列表
     * @author:zhubaodi
     * @date_time: 2021/9/3 15:58
     */
    public function getVacancyList($data,$village_id=0)
    {
        if (empty($data)) {
            return ['code' => 1003,'msg' => '请选择房间！'];
        }
        $vacancy = new HouseVillageUserVacancy();
        $vacancy_id=[];
        $is_break = false;
        $msg = '请选择需要绑定的楼栋！';
        foreach ($data as $v) {
            if(!isset($v['single_id'])){
                $is_break = true;
                break;
            }
            $whereArr=array();
            $whereArr[]=array('village_id','=',$village_id);
            $whereArr[]=array('single_id','=',$v['single_id']);
            $whereArr[] = ['status', 'in', array(1,2,3)];
            $whereArr[] = ['is_del', '=', 0];
            if(isset($v['layer_id']) && !empty($v['layer_id'])){
                if(!is_array($v['layer_id']) && is_numeric($v['layer_id'])) {
                    $whereArr[] = ['layer_id', '=', $v['layer_id']];
                }else{
                    $whereArr[] = ['layer_id', 'in', $v['layer_id']];
                }
            }
            if(isset($v['floor_id']) && !empty($v['floor_id'])){
                if(!is_array($v['floor_id']) && is_numeric($v['floor_id'])){
                    $whereArr[] = ['floor_id', '=', $v['floor_id']];
                }else{
                    $whereArr[] = ['floor_id', 'in', $v['floor_id']];
                }
            }
            // 查询房间号
            $list = $vacancy->getList($whereArr, 'pigcms_id as id ,room as name,pigcms_id,room,layer_id');
            if (!empty($list)){
                $list=$list->toArray();
                if (!empty($list)){
                    foreach ($list as $vv){
                        $vacancy_id[]=$vv['pigcms_id'];
                    }
                }
            }
        }
        if($is_break){
            return ['code' => 1003,'msg' => $msg];
        }
        if($vacancy_id){
            $vacancy_id=array_unique($vacancy_id);
        }
        return $vacancy_id;
    }
    /**
     * 根据楼栋楼层获取房间列表
     * 
     */
    public function getVacancyBySingleList($data)
    {
        if (empty($data)) {
            return ['code' => 1003,'msg' => '请选择房间！'];
        }
        $vacancy = new HouseVillageUserVacancy();
        $vacancy_id=[];
        $is_break = false;
        $msg = '请选择需要绑定的楼栋！';
        foreach ($data as $v) {
            if(!isset($v['single_id'])){
                $is_break = true;
                break;
            }

            if ((!isset($v['layer_id']) || empty($v['layer_id'])) && !empty($v['single_id'])) { // 不选楼层，默认是选中所有楼层 single_id
                $where = [];
                $where[] = ['single_id', '=', $v['single_id']];
                $where[] = ['status', '=', 1];
                // 查询楼层
                $village_layer = new HouseVillageLayer();
                $layerList = $village_layer->getList($where, 'id');
                if($layerList && !$layerList->isEmpty()){
                    $v['layer_id'] = array_column($layerList->toArray(),'id');
                }
            }
            if(!empty($v['layer_id'])){
                $layer=implode(',',$v['layer_id']);
                $where = [];
                $where[] = ['status', 'in', '1,2,3'];
                $where[] = ['layer_id', 'in', $layer];
                // 查询房间号
                $list = $vacancy->getList($where, 'pigcms_id as id ,room as name,pigcms_id,room,layer_id');
                if (!empty($list)){
                    $list=$list->toArray();
                    if (!empty($list)){
                        foreach ($list as $vv){
                            $vacancy_id[]=$vv['pigcms_id'];
                        }
                    }
                }
            }else{
                $is_break = true;
                break;
            }

        }
        if($is_break){
            return ['code' => 1003,'msg' => $msg];
        }
        return $vacancy_id;
    }
    /**
     * 获取收费项目按日收费的收费标准列表
     * @author lijie
     * @date_time 2021/11/10
     * @param array $where
     * @param bool $field
     * @param string $order
     * @return mixed
     */
    public function getRuleListOfDay($where=[],$field=true,$order='charge_valid_time DESC')
    {
        $data = $this->HouseNewChargeRule->getList($where,$field,$order);
        return $data;
    }

    /**
     * 根据收费项目获取关联的收费标准总数
     * @param $param
     * @return int
     * @author cc
     */
    public function getChargeCountByProjectId($param)
    {
        $where['village_id'] =  $param['village_id'];
        $where['charge_project_id'] = $param['charge_project_id'];
        $where['status'] = ['in','1,2']; //排除假删除
        $count = $this->HouseNewChargeRule->getCount($where);
        return (int)$count;
    }

    public function getHouseVillageInfo($where=[],$field=true)
    {
        $db_house_village_info = new HouseVillageInfo();
        $data = $db_house_village_info->getOne($where,$field);
        return $data;
    }

    //todo 通过收费项目id 房间id  获取绑定房屋的收费标准id
    public function getStandardBind($project_id,$vacancy_id)
    {
        $where[] = ['is_del', '=', 1];
        $where[] = ['project_id', '=', $project_id];
        $where[] = ['vacancy_id', '=', $vacancy_id];
        $where[] = ['charge_valid_time', '<=', time()];
        $list = $this->HouseNewChargeStandardBind->getLists1($where, 'rule_id', 'charge_valid_time desc',0,2)->toArray();
        $id = 0;
        if (isset($list[0]['rule_id']) && !empty($list[0]['rule_id'])) {
            $id = $list[0]['rule_id'];
        }
        return $id;
    }

    public function getStandardIds($param,$column) {
        $where = [];
        if (isset($param['project_id'])&&$param['project_id']) {
            $where[] = ['project_id', '=', $param['project_id']];
        }
        if (isset($param['rule_id'])&&$param['rule_id']) {
            $where[] = ['rule_id', '=', $param['rule_id']];
        }
        if (isset($param['village_id'])&&$param['village_id']) {
            $where[] = ['village_id', '=', $param['village_id']];
        }
        $where[] = ['is_del', '=', 1];
        return $this->HouseNewChargeStandardBind->getColumn($where, $column);
    }


    public function addNewParkRule($param,$ruleInfo=[]){
        $data=[];
        if (empty($param['id'])){
            if ($param['fees_type']==3){
                $db_house_village_park_charge=new HouseVillageParkCharge();
                $park_data['charge_name'] = $param['charge_name'];
                /***
                * charge_type 1、24小时计算步长 2、单次只收一次费用 3、零点前只收一次费用 4、24小时逐时收费 5、按次收费
                 * 当 charge_type是5是 要看charge_type5_set字段值  1连续多少小时只收取一次费 2按次收费（和charge_type为2一样） 3、当天只收取一次费（零点前）（和charge_type为3一样）
                 *free_time_no_count 1时 从免费时间后开始计算费用
                 */
                $park_data['charge_type'] = $param['charge_type'];
                $park_data['free_time'] = $param['free_time']?$param['free_time']:0;
                $park_data['village_id'] = $param['village_id'];
                $park_data['park_sys_type'] = '';
                $park_data['status'] = 1;
                $park_data['updateTime'] = time();
                $park_data['addTime'] = time();
                $park_data['version'] = 1;
                $park_data['free_time_no_count']=isset($param['free_time_no_count']) ? intval($param['free_time_no_count']):0;
                if ($param['charge_type']==1){
                    $charge_set=[];
                    foreach ($param['charge_set'][0] as $k=>$v){
                        if (!is_numeric($v)||empty($v)){
                            throw new \think\Exception("请正确输入收费时长");
                        }
                        $charge_set[$k]['time']=(double)$v;
                    }
                    foreach ($param['charge_set'][1] as $k=>$v){
                        if (!is_numeric($v)||empty($v)){
                            throw new \think\Exception("请正确输入收费金额");
                        }
                        $charge_set[$k]['money']=(double)$v;
                    }
                    $charge_set1=[];
                    foreach ($charge_set as $vv){
                        $charge_set1[]=$vv;
                    }
                    if (!is_numeric($param['charge_money'])) {
                        throw new \think\Exception("请正确输入步长对应的金额");
                    }
                    if (!empty($param['max_charge_money'])&&!is_numeric($param['max_charge_money'])) {
                        throw new \think\Exception("请正确输入封顶金额");
                    }
                    if (!empty($param['first_charge_money'])&&!is_numeric($param['first_charge_money'])) {
                        throw new \think\Exception("请正确输入首停金额");
                    }
                    if (isset($param['charge_money'])) {
                        $param['charge_money']=(double)$param['charge_money'];
                    }
                    if (isset($param['max_charge_money'])) {
                        $param['max_charge_money']=(double)$param['max_charge_money'];
                    }
                    if (isset($param['first_charge_money'])) {
                        $param['first_charge_money']=(double)$param['first_charge_money'];
                    }
                    if (empty($param['charge_type'])) {
                        throw new \think\Exception("收费类型不能为空");
                    }
                    if (empty($param['charge_set'])) {
                        throw new \think\Exception("费用设置不能为空");
                    }
                    if (empty($param['charge_time'])) {
                        throw new \think\Exception("计算步长不能为空");
                    }
                    if (empty($param['charge_money'])) {
                        throw new \think\Exception("步长对应的金额不能为空");
                    }
                    if ($param['charge_money']<0) {
                        throw new \think\Exception("请正确输入步长对应的金额");
                    }
                    if (!empty($param['max_charge_money'])&&$param['max_charge_money']<0){
                        throw new \think\Exception("请正确输入封顶金额");
                    }
                    
                    if (!empty($param['first_charge_money'])&&!empty($param['max_charge_money'])&&$param['first_charge_money']>$param['max_charge_money']){
                        throw new \think\Exception("首停金额不能大于封顶费用");
                    }
                    $park_data['charge_set'] = serialize($charge_set1);
                    $park_data['charge_money'] = $param['charge_money'];
                    $park_data['charge_time'] = $param['charge_time'];
                }elseif($param['charge_type']==4){
                    if(empty($param['park24hour_fee']) || !is_array($param['park24hour_fee'])){
                        throw new \think\Exception("费用设置错误！");
                    }
                    $charge_set4=array();
                    foreach ($param['park24hour_fee'] as $hkk=>$hvv){
                        if(empty($hvv) || !isset($hvv['hour'])){
                            throw new \think\Exception("费用设置错误！");
                        }
                        if(!empty($charge_set4)){
                            $old_index=$hkk-1;
                            if($hvv['fee']<$charge_set4[$old_index]['fee']){
                                throw new \think\Exception("第".($hkk+1)."小时费用比第".($hkk)."费用小，请重新设置！");
                            }
                        }
                        $charge_set4[$hkk]=$hvv;
                    }
                    $park_data['charge_set'] = serialize($charge_set4);
                }elseif($param['charge_type']==5){
                    $park_data['charge_type5_set']=isset($param['charge_type5_set']) ? intval($param['charge_type5_set']):2;
                    if($param['charge_type5_set']==1){
                        $park_data['hour_once_fee']=isset($param['hour_once_fee']) ? intval($param['hour_once_fee']):24;
                    }
                    if (empty($param['max_charge_money'])||$param['max_charge_money']<0){
                        throw new \think\Exception("请正确输入收费金额！");
                    }
                }else{
                    if (empty($param['max_charge_money'])||$param['max_charge_money']<0){
                        throw new \think\Exception("请正确输入收费金额");
                    }

                    if (!empty($param['first_charge_money'])&&!empty($param['max_charge_money'])&&$param['first_charge_money']>$param['max_charge_money']){
                        throw new \think\Exception("首停金额不能大于收费费用");
                    }
                }
                if (!empty($param['first_free_time'])&&!empty($param['free_time'])&&$param['first_free_time']<=$param['free_time']){
                    throw new \think\Exception("首停时间需大于免费时长");
                }
                $park_data['max_charge_money'] = $param['max_charge_money']?$param['max_charge_money']:0;
                $park_data['first_free_time'] = $param['first_free_time']?$param['first_free_time']:0;
                $park_data['first_charge_money'] = $param['first_charge_money']? $param['first_charge_money']:0;
                
                $id=$db_house_village_park_charge->addFind($park_data);
                $data['unit_price'] = abs($param['unit_price']);
                $data['charge_price'] = abs($param['unit_price']);
                $data['rate'] = (intval($param['rate']) == 0) ? 1 : intval($param['rate']);
                $data['is_prepaid'] = 2;
                $data['park_charge_id'] = $id;
            }
            elseif ($param['fees_type']==4){
                /*
                if (!empty($param['give_cycle_type'])&&!empty($param['give_cycle_datetype'])) {
                    $data11 = array(
                        'type' => 2,
                        'cycle' => 1,
                        'village_id' => $param['village_id'],
                        'cycle_param' => 0,
                        'give_cycle_type' => $param['give_cycle_type'],
                        'give_cycle_datetype' => $param['give_cycle_datetype'],
                        'status' => 1,
                        'update_time' => time(),
                    );
                    $this->HouseNewChargePrepaid->addFind($data11);
                }
                */
                $data['unit_price'] = abs($param['charge_price']);
                $data['charge_price'] = abs($param['charge_price']);
                $data['rate'] = (intval($param['rate']) == 0) ? 1 : intval($param['rate']);
                $data['is_prepaid'] = 1;
                if (empty($param['park_charge_id'])){
                    $data['park_charge_id']=1;
                }else{
                    $data['park_charge_id']=$param['park_charge_id'];
                }
            }
        }else{
            $park_data=array();
            if ($param['fees_type']==3) {
                if (empty($ruleInfo['park_charge_id'])) {
                    throw new \think\Exception("停车收费标准不能为空");
                }
                $db_house_village_park_charge = new HouseVillageParkCharge();
                $db_house_village_park_config = new HouseVillageParkConfig();
                $charge_set1 = [];
                if ($param['charge_type'] == 1) {
                    $charge_set = [];
                    foreach ($param['charge_set'][0] as $k => $v) {
                        if (!is_numeric($v) || empty($v)) {
                            throw new \think\Exception("请正确输入收费时长");
                        }
                        $charge_set[$k]['time'] = $v;
                    }
                    foreach ($param['charge_set'][1] as $k => $v) {
                        if (!is_numeric($v) || empty($v)) {
                            throw new \think\Exception("请正确输入收费金额");
                        }
                        $charge_set[$k]['money'] = $v;
                    }
                    foreach ($charge_set as $vv) {
                        $charge_set1[] = $vv;
                    }
                if (!is_numeric($param['charge_money'])) {
                    throw new \think\Exception("请正确输入步长对应的金额");
                }
                if (!empty($param['max_charge_money']) && !is_numeric($param['max_charge_money'])) {
                    throw new \think\Exception("请正确输入封顶金额");
                }
                if (!empty($param['first_charge_money']) && !is_numeric($param['first_charge_money'])) {
                    throw new \think\Exception("请正确输入首停金额");
                }
                if (isset($param['charge_money'])) {
                    $param['charge_money'] = (double)$param['charge_money'];
                }
                if (isset($param['max_charge_money'])) {
                    $param['max_charge_money'] = (double)$param['max_charge_money'];
                }
                if (isset($param['first_charge_money'])) {
                    $param['first_charge_money'] = (double)$param['first_charge_money'];
                }
                if (empty($param['charge_type'])) {
                    throw new \think\Exception("收费类型不能为空");
                }
                if (empty($param['charge_set'])) {
                    throw new \think\Exception("费用设置不能为空");
                }
                if (empty($param['charge_time'])) {
                    throw new \think\Exception("计算步长不能为空");
                }
                if (empty($param['charge_money'])) {
                    throw new \think\Exception("步长对应的金额不能为空");
                }
                if (!empty($param['first_free_time']) && !empty($param['free_time']) && $param['first_free_time'] < $param['free_time']) {
                    throw new \think\Exception("首停时间需大于免费时长");
                }
                if (!empty($param['first_charge_money']) && !empty($param['max_charge_money']) && $param['first_charge_money'] > $param['max_charge_money']) {
                    throw new \think\Exception("首停金额不能大于封顶费用");
                }
            }
                $park_charge_info=$db_house_village_park_charge->getFind(['id'=>$ruleInfo['park_charge_id']]);
                if (empty($park_charge_info)){
                    throw new \think\Exception("停车收费标准不能为空");
                }
                if($param['charge_type']==4){
                    if(empty($param['park24hour_fee']) || !is_array($param['park24hour_fee'])){
                        throw new \think\Exception("费用设置错误！");
                    }
                    $charge_set4=array();
                    foreach ($param['park24hour_fee'] as $hkk=>$hvv){
                        if(empty($hvv) || !isset($hvv['hour'])){
                            throw new \think\Exception("费用设置错误！");
                        }
                        if(!empty($charge_set4)){
                            $old_index=$hkk-1;
                            if($hvv['fee']<$charge_set4[$old_index]['fee']){
                                throw new \think\Exception("第".($hkk+1)."小时费用比第".($hkk)."费用小，请重新设置！");
                            }
                        }
                        $charge_set4[$hkk]=$hvv;
                    }
                    $charge_set1 = $charge_set4;
                }elseif($param['charge_type']==5){
                    $park_data['charge_type5_set']=isset($param['charge_type5_set']) ? intval($param['charge_type5_set']):2;
                    if($param['charge_type5_set']==1){
                        $park_data['hour_once_fee']=isset($param['hour_once_fee']) ? intval($param['hour_once_fee']):24;
                    }
                    if (empty($param['max_charge_money'])||$param['max_charge_money']<0){
                        throw new \think\Exception("请正确输入收费金额！");
                    }
                }else{
                    if (empty($param['max_charge_money'])||$param['max_charge_money']<0){
                        throw new \think\Exception("请正确输入收费金额");
                    }

                    if (!empty($param['first_charge_money'])&&!empty($param['max_charge_money'])&&$param['first_charge_money']>$param['max_charge_money']){
                        throw new \think\Exception("首停金额不能大于收费费用");
                    }
                }
                $park_data['free_time_no_count']=isset($param['free_time_no_count']) ? intval($param['free_time_no_count']):0;
                $park_data['charge_name'] = $param['charge_name'];
                $park_data['charge_type'] = $param['charge_type'];
                $park_data['charge_set'] = serialize($charge_set1);
                $park_data['free_time'] = $param['free_time']?$param['free_time']:0;
                $park_data['max_charge_money'] = $param['max_charge_money']?$param['max_charge_money']:0;
                $park_data['first_free_time'] = $param['first_free_time']?$param['first_free_time']:0;
                $park_data['first_charge_money'] = $param['first_charge_money']? $param['first_charge_money']:0;
                $park_data['village_id'] = $param['village_id'];
                $park_data['park_sys_type'] = '';
                $park_data['status'] = 1;
                $park_data['charge_money'] = $param['charge_money'];
                $park_data['charge_time'] = $param['charge_time'];
                $park_data['updateTime'] = time();
                $park_data['addTime'] = time();
                $id=$db_house_village_park_charge->save_one(['id'=>$ruleInfo['park_charge_id']],$park_data);
                $data['charge_price'] = isset($param['unit_price'])?abs($param['unit_price']):0;
                $data['unit_price'] = isset($param['unit_price'])?abs($param['unit_price']):0;
                $data['is_prepaid'] = 2;
                $data['park_charge_id'] = $ruleInfo['park_charge_id'];
            }
            elseif ($param['fees_type']==4){
                /*
                if (!empty($param['give_cycle_type'])&&!empty($param['give_cycle_datetype']) && $ruleInfo && isset($ruleInfo['id'])){
                    $prepaid_info=$this->HouseNewChargePrepaid->getFind(['charge_rule_id'=>$ruleInfo['id']]);
                    if (empty($prepaid_info)){
                        $data1=array(
                            'type'=>2,
                            'charge_rule_id'=>$ruleInfo['id'],
                            'cycle'=>1,
                            'village_id' => $param['village_id'],
                            'cycle_param'=>0,
                            'give_cycle_type'=>$param['give_cycle_type'],
                            'give_cycle_datetype'=>$param['give_cycle_datetype'],
                            'status'=>1,
                            'update_time'=>time(),
                        );
                        $this->HouseNewChargePrepaid->addFind($data1);
                    }else{
                        $data1=array(
                            'type'=>2,
                            'cycle'=>1,
                            'charge_rule_id'=>$ruleInfo['id'],
                            'cycle_param'=>0,
                            'give_cycle_type'=>$param['give_cycle_type'],
                            'give_cycle_datetype'=>$param['give_cycle_datetype'],
                            'status'=>1,
                            'update_time'=>time(),
                        );
                        $this->HouseNewChargePrepaid->editFind(['charge_rule_id'=>$ruleInfo['id']],$data1);
                    }
                }
                */
                if (empty($param['park_charge_id'])){
                    $data['park_charge_id']=1;
                }else{
                    $data['park_charge_id']=$param['park_charge_id'];
                }
                $data['unit_price'] = isset($param['charge_price'])?abs($param['charge_price']):0;
                $data['charge_price'] = isset($param['charge_price'])?abs($param['charge_price']):0;
                $data['is_prepaid'] = 1;
            }
        }
        $data['fees_type'] = $param['fees_type'];
        return $data;
    }

    /**
     * 批量绑定车位
     * @author:zhubaodi
     * @date_time: 2022/3/7 17:46
     */
    public function addBindAllPosition($data){
        $db_house_new_charge_standard_bind=new HouseNewChargeStandardBind();
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $db_rule = new HouseNewChargeRule();
        $db_house_village_park_config=new HouseVillageParkConfig();
        $where_config=[];
        $where_config['village_id']=$data['village_id'];
        $ids=[];
        $ruleInfo = $db_rule->getOne(['id' => $data['rule_id']]);
        if (empty($ruleInfo)) {
            throw new \think\Exception("收费标准信息不存在，无法添加绑定");
        }
        if ($ruleInfo['fees_type']==4){
            if (empty($ruleInfo['park_charge_id'])){
                $cycle=1;
            }else{
                $cycle=$ruleInfo['park_charge_id'];
            }
        }
        if (empty($cycle)||!isset($cycle)){
            $cycle=1;
        }
        if (!empty($data['garage_id'])){
            $where=[];
            $where['garage_id']=$data['garage_id'];
            $where['is_del']=1;
           $position_bind_ids=$db_house_new_charge_standard_bind->getColumn($where,'position_id');
           if (!empty($position_bind_ids)) {
               $where1 = [
                   ['garage_id', 'in', $data['garage_id']],
                   ['position_id', 'not in', $position_bind_ids],
               ];
           }else {
               $where1 = [
                   ['garage_id', 'in', $data['garage_id']],
               ];
           }
               $where1[]= ['children_type', '=', 1];
               $position_ids=$db_house_village_parking_position->getList($where1,'position_id,garage_id',0,3000);

               if (!empty($position_ids)){
                   $position_ids=$position_ids->toArray();
                   if (!empty($position_ids)){
                       foreach ($position_ids as $v) {
                           $bind_data = [
                               'rule_id' => $data['rule_id'],
                               'project_id' => $ruleInfo['charge_project_id'],
                               'village_id' => $data['village_id'],
                               'garage_id' => $v['garage_id'],
                               'position_id' => $v['position_id'],
                               'bind_type' => 2,
                               'order_add_type' => $ruleInfo['bill_create_set'],
                               'charge_valid_time' => $ruleInfo['charge_valid_time'],
                               'add_time' => time(),
                               'update_time' => time(),
                               'is_del' => 1,
                               'cycle'=>$cycle,
                           ];
                           $id = $db_house_new_charge_standard_bind->addOne($bind_data);
                           if ($id > 0) {
                               $ids[] = $id;
                           }
                       }
                   }
               }

        }
        if (empty($ids)){
            throw new \think\Exception("绑定失败");
        }
        return $ids;
    }

    /**
     * 查询临时车收费标准
     * @author:zhubaodi
     * @date_time: 2022/3/9 16:00
     */
    public function getTempRuleInfo($data){
        $rule_info=[];
        $db_house_village_park_charge=new HouseVillageParkCharge();
        $db_house_new_charge_prepaid=new HouseNewChargePrepaid();
        if ($data['fees_type']==4){
            $list=$db_house_new_charge_prepaid->getList(['charge_rule_id'=>$data['id']]);
            if (!empty($list)){
                $list=$list->toArray();
            }
            if (!empty($list)){
                $list=$list[0];
                unset($list['id']);
            }
            if (empty($list)){
                $list['give_cycle_type']=0;
                $list['give_cycle_datetype']=1;
            }
            if (empty($data['park_charge_id'])){
                $list['park_charge_id']=1;   
            }else{
                $list['park_charge_id']=$data['park_charge_id'];
            }
            return $list;
        }else{
            if (!empty($data['park_charge_id'])){
                $rule_info=$db_house_village_park_charge->getFind(['id'=>$data['park_charge_id']]);
                if (!empty($rule_info)){
                    $rule_info=$rule_info->toarray();
                    if (!empty($rule_info['charge_set'])) {
                        $charge_set = unserialize($rule_info['charge_set']);
                        if ($rule_info['charge_type'] == 4) {
                            $tmp_charge_set = array([], [], [], [], [], [], [], []);
                            foreach ($charge_set as $kk => $svv) {
                                $index = $kk / 3;
                                $index = floor($index);
                                $index = $index > 0 ? $index : 0;
                                $tmp_charge_set[$index][] = $svv;
                            }
                            $rule_info['charge_set'] = $tmp_charge_set;
                        } else {
                            $rule_info['charge_set'] = $charge_set;
                        }
                    }
                }
            }
            return $rule_info;
        }

    }

    public function getMonthParkRuleList($data)
    {
        $db_house_new_charge_standard_bind = new HouseNewChargeStandardBind();
        $where = [];
        $where['c.charge_type'] = 'park_new';
        $where['p.village_id'] = $data['village_id'];
        $where['p.status'] = 1;
        $project_ids = $this->HouseNewChargeProject->getProjectColumn($where, 'p.id');
      //  print_r([$where,$project_ids]);exit;

        if (empty($project_ids)) {
            throw new \think\Exception("月租车收费项目不存在，请先添加月租车收费项目");
        }
        $where_bind = [];
        $where_bind['garage_id'] = $data['garage_id'];
        $where_bind['project_id'] = $project_ids;
        $where_bind['is_del'] = 1;
        $rule_ids = $db_house_new_charge_standard_bind->getColumn($where_bind, 'rule_id');
        if (empty($rule_ids)) {
            throw new \think\Exception("当前车库未绑定月租车收费规则，请先绑定月租车收费规则");
        }
        $where_rule = [];
        $where_rule['id'] = $rule_ids;
        $where_rule['fees_type'] = 4;
        $where_rule['status'] = 1;
        $order = 'id desc';
        $rule_list = $this->HouseNewChargeRule->getList($where_rule, "*", $order, $data['page'], $data['limit']);

        if (!empty($rule_list)) {
            $rule_list = $rule_list->toArray();
        }
        $data_list = [];
        if (!empty($rule_list)) {

            foreach ($rule_list as $k => $v) {
                $data_list[$k]['id'] = $v['id'];
                $data_list[$k]['charge_type'] = 'park_new';
                $data_list[$k]['subject_id'] = $v['subject_id'];
                $data_list[$k]['charge_project_id'] = $v['charge_project_id'];
                $data_list[$k]['charge_name'] = $v['charge_name'];
                $data_list[$k]['charge_txt'] = '月租车收费规则';
                if ($v['bill_create_set'] == 1) {
                    $data_list[$k]['bill_create_set_txt'] = '按日生成';
                } elseif ($v['bill_create_set'] == 2) {
                    $data_list[$k]['bill_create_set_txt'] = '按月生成';
                } else {
                    $data_list[$k]['bill_create_set_txt'] = '按年生成';
                }
                if (!empty($v['charge_valid_time'])) {
                    $data_list[$k]['charge_valid_time'] = date('Y-m-d', $v['charge_valid_time']);
                }
                if ($v['bill_type'] == 1) {
                    $data_list[$k]['bill_type_txt'] = '手动生成';
                } else {
                    $data_list[$k]['bill_type_txt'] = '自动生成';
                }
                $where_rule1 = [];
                $where_rule1[] = ['r.id','=',$v['id']];
                // 获取上级信息
                $subject = $this->HouseNewChargeRule->getFind($where_rule1,'c.charge_number_name,c.charge_type,p.name');
                if($subject && !$subject->isEmpty()){
                    $subject = $subject->toArray();
                    $data_list[$k]['charge_number_name'] = $subject['charge_number_name'];
                    $data_list[$k]['project_name'] = $subject['name'];
                }
            }
        }
        $count = $this->HouseNewChargeRule->getCount($where_rule);
        $data1 = [];
        $data1['list'] = $data_list;
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        return $data1;
    }

    public function getChargeValidType($charge_rule_id){
        $type=3;
        $arr=[
            1=>'日',
            2=>'月',
            3=>'年',
        ];
        $rr= $this->HouseNewChargeRule->getOne(['id'=>$charge_rule_id],'bill_create_set');
        if($rr && !$rr->isEmpty()){
            $type= $rr['bill_create_set'];
        }
        return $arr[$type];

    }

    

    /**
     * 添加汽车充电桩收费标准
     * @author:zhubaodi
     * @date_time: 2022/9/14 19:20
     */
    public function addNewPileRule($data){
        $db_house_new_pile_charge=new HouseNewPileCharge(); 
        if (empty($data['charge1_name'])||empty($data['charge2_name'])||empty($data['charge3_name'])||empty($data['charge4_name'])){
            throw new \think\Exception("价格类别名称不能为空");
        }
        if (!isset($data['charge1_ele'])||!isset($data['charge2_ele'])||!isset($data['charge3_ele'])||!isset($data['charge4_ele'])){
            throw new \think\Exception("电费不存在");
        }
        if (!isset($data['charge1_serve'])||!isset($data['charge2_serve'])||!isset($data['charge3_serve'])||!isset($data['charge4_serve'])){
            throw new \think\Exception("服务费不存在");
        }
        $charge_set1=[];
        foreach ($data['price_set_list'] as $vv){
            $arr=[];
            $arr['time_start']=$this->pile_time[$vv['start']];
            $arr['time_end']=$this->pile_time[$vv['end']];
            $arr['money']=$vv['price'];
            $price=$data['charge'.($vv['price']+1).'_serve']+$data['charge'.($vv['price']+1).'_ele'];
            if ($price<=0){
                throw new \think\Exception("价格设置中选择的价格类别总金额需大于0"); 
            }
            $charge_set1[]=$arr;
        }
        $charge_1=['charge_name'=>$data['charge1_name'], 'charge_ele'=>$data['charge1_ele'], 'charge_serve'=>$data['charge1_serve']];
        $charge_2=['charge_name'=>$data['charge2_name'],'charge_ele'=>$data['charge2_ele'], 'charge_serve'=>$data['charge2_serve']];
        $charge_3=['charge_name'=>$data['charge3_name'], 'charge_ele'=>$data['charge3_ele'], 'charge_serve'=>$data['charge3_serve']];
        $charge_4=['charge_name'=>$data['charge4_name'], 'charge_ele'=>$data['charge4_ele'], 'charge_serve'=>$data['charge4_serve']];
       
        $pile_arr=[];
        $pile_arr['village_id']=$data['village_id'];
        $pile_arr['charge_name']=$data['charge_name'];
        $pile_arr['rule_id']=0;
        $pile_arr['charge_1']=json_encode($charge_1,JSON_UNESCAPED_UNICODE);
        $pile_arr['charge_2']=json_encode($charge_2,JSON_UNESCAPED_UNICODE);
        $pile_arr['charge_3']=json_encode($charge_3,JSON_UNESCAPED_UNICODE);
        $pile_arr['charge_4']=json_encode($charge_4,JSON_UNESCAPED_UNICODE);
        $pile_arr['charge_time']=json_encode($charge_set1,JSON_UNESCAPED_UNICODE);
        $pile_arr['price_set_value']=$data['price_set_value'];
        $pile_arr['addTime']=time();
        $pile_arr['updateTime']=time();
        $id=$db_house_new_pile_charge->addOne($pile_arr);
        return $id;
    }

    /**
     * 修改汽车充电桩收费标准
     * @author:zhubaodi
     * @date_time: 2022/9/14 19:36
     */
    public function editNewPileRule($data){
        $db_house_new_pile_charge=new HouseNewPileCharge();
        $pile_info=$db_house_new_pile_charge->getFind(['id'=>$data['id']]);
        if (empty($pile_info)){
            throw new \think\Exception("收费标准不存在");
        }
        if (empty($data['charge1_name'])||empty($data['charge2_name'])||empty($data['charge3_name'])||empty($data['charge4_name'])){
            throw new \think\Exception("价格类别名称不能为空");
        }
        if (!isset($data['charge1_ele'])||!isset($data['charge2_ele'])||!isset($data['charge3_ele'])||!isset($data['charge4_ele'])){
            throw new \think\Exception("电费不存在");
        }
        if (!isset($data['charge1_serve'])||!isset($data['charge2_serve'])||!isset($data['charge3_serve'])||!isset($data['charge4_serve'])){
            throw new \think\Exception("服务费不存在");
        }
        $charge_set1=[];
        foreach ($data['price_set_list'] as $vv){
            $arr=[];
            $arr['time_start']=$this->pile_time[$vv['start']];
            $arr['time_end']=$this->pile_time[$vv['end']];
            $arr['money']=$vv['price'];
            $price=$data['charge'.($vv['price']+1).'_serve']+$data['charge'.($vv['price']+1).'_ele'];
            if ($price<=0){
                throw new \think\Exception("价格设置中选择的价格类别总金额需大于0");
            }
            $charge_set1[]=$arr;
        }
        $charge_1=['charge_name'=>$data['charge1_name'], 'charge_ele'=>$data['charge1_ele'], 'charge_serve'=>$data['charge1_serve']];
        $charge_2=['charge_name'=>$data['charge2_name'],'charge_ele'=>$data['charge2_ele'], 'charge_serve'=>$data['charge2_serve']];
        $charge_3=['charge_name'=>$data['charge3_name'], 'charge_ele'=>$data['charge3_ele'], 'charge_serve'=>$data['charge3_serve']];
        $charge_4=['charge_name'=>$data['charge4_name'], 'charge_ele'=>$data['charge4_ele'], 'charge_serve'=>$data['charge4_serve']];

        $pile_arr=[];
        $pile_arr['village_id']=$data['village_id'];
        $pile_arr['charge_name']=$data['charge_name'];
        $pile_arr['rule_id']=$data['rule_id'];
        $pile_arr['charge_1']=json_encode($charge_1,JSON_UNESCAPED_UNICODE);
        $pile_arr['charge_2']=json_encode($charge_2,JSON_UNESCAPED_UNICODE);
        $pile_arr['charge_3']=json_encode($charge_3,JSON_UNESCAPED_UNICODE);
        $pile_arr['charge_4']=json_encode($charge_4,JSON_UNESCAPED_UNICODE);
        $pile_arr['charge_time']=json_encode($charge_set1,JSON_UNESCAPED_UNICODE);
        $pile_arr['updateTime']=time();
        $pile_arr['price_set_value']=$data['price_set_value'];
        
        $res=$db_house_new_pile_charge->save_one(['id'=>$data['id']],$pile_arr);
        return $res;
    }

    /**
     * 修改汽车充电桩收费标准
     * @author:zhubaodi
     * @date_time: 2022/9/14 19:36
     */
    public function getNewPileRule($data)
    {
        $db_house_new_pile_charge = new HouseNewPileCharge();
        $pile_info = $db_house_new_pile_charge->getFind(['rule_id' => $data['id']]);
        if (empty($pile_info)) {
            throw new \think\Exception("收费标准不存在");
        }
        $pile_info['charge_time'] = json_decode($pile_info['charge_time'], true);
        $charge_set1 = [];
        $pile_time_txt = $this->pile_time_txt;
        foreach ($pile_info['charge_time'] as $vv) {
            $arr = [];
            $arr['start'] = isset($pile_time_txt[$vv['time_start']]) ? $pile_time_txt[$vv['time_start']] : '';
            $arr['end'] = isset($pile_time_txt[$vv['time_end']]) ? $pile_time_txt[$vv['time_end']] : '';
            $arr['price'] = $vv['money'];
            $charge_set1[] = $arr;
        }
        $pile = [];
        $pile['charge_1'] = \json_decode($pile_info['charge_1'], JSON_UNESCAPED_UNICODE);
        $pile['charge_2'] = \json_decode($pile_info['charge_2'], JSON_UNESCAPED_UNICODE);
        $pile['charge_3'] = \json_decode($pile_info['charge_3'], JSON_UNESCAPED_UNICODE);
        $pile['charge_4'] = \json_decode($pile_info['charge_4'], JSON_UNESCAPED_UNICODE);

        $pile_arr = [];
        $pile_arr['charge_id'] = $pile_info['id'];
        $pile_arr['village_id'] = $pile_info['village_id'];
        $pile_arr['charge_name'] = $pile_info['charge_name'];
        $pile_arr['rule_id'] = $pile_info['rule_id'];
        if (!empty($pile)) {
            $pile_arr['charge1_name'] = $pile['charge_1']['charge_name'];
            $pile_arr['charge2_name'] = $pile['charge_2']['charge_name'];
            $pile_arr['charge3_name'] = $pile['charge_3']['charge_name'];
            $pile_arr['charge4_name'] = $pile['charge_4']['charge_name'];

            $pile_arr['charge1_ele'] = $pile['charge_1']['charge_ele'];
            $pile_arr['charge2_ele'] = $pile['charge_2']['charge_ele'];
            $pile_arr['charge3_ele'] = $pile['charge_3']['charge_ele'];
            $pile_arr['charge4_ele'] = $pile['charge_4']['charge_ele'];

            $pile_arr['charge1_serve'] = $pile['charge_1']['charge_serve'];
            $pile_arr['charge2_serve'] = $pile['charge_2']['charge_serve'];
            $pile_arr['charge3_serve'] = $pile['charge_3']['charge_serve'];
            $pile_arr['charge4_serve'] = $pile['charge_4']['charge_serve'];
        }


        $pile_arr['price_set_list'] = $charge_set1;
        $pile_arr['price_set_value'] = $pile_info['price_set_value'];

        return $pile_arr;
    }


    public function  get_rule_bind_vecancy_count($where){
        $count = $this->HouseNewChargeStandardBind->get_bind_vecancy_count($where);
        return $count;
    }

    public function getFeesTypeTxt($fees_type) {
        $fees_type_txt = '-';
        switch ($fees_type) {
            case newChargeConst::FEES_TYPE_FIXED_EXPENSES:
                $fees_type_txt = '固定费用';
                break;
            case newChargeConst::FEES_TYPE_UNIT_PRICE_UOM:
                $fees_type_txt = '单价计量单位';
                break;
            case newChargeConst::FEES_TYPE_TEMPORARY_VEHICLE:
                $fees_type_txt = '临时车';
                break;
            case newChargeConst::FEES_TYPE_MONTHLY_CAR_RENTAL:
                $fees_type_txt = '月租车';
                break;
            case newChargeConst::FEES_TYPE_NUMBER_OF_PARKING_SPACES:
                $fees_type_txt = '车位数量';
                break;
        }
        return $fees_type_txt;
    }
    
    public function getRuleHasParkNumInfo($id, $rule_id, $bind_type = 1, $ruleInfo = []) {
        if (!$id || !$rule_id) {
            return [
                'id'           => $id,
                'bind_type'    => $bind_type,
                'rule_id'      => $rule_id,
                'err_msg'      => '缺少必传参数',
                'parking_lot'  => '',
                'parkingNum'   => 0,
            ];
        }
        if (empty($ruleInfo) || !isset($ruleInfo['id']) || !isset($ruleInfo['village_id']) || !isset($ruleInfo['fees_type'])) {
            $db_rule = new HouseNewChargeRule();
            $ruleInfo = $db_rule->getOne(['id' => $rule_id]);
            if ($ruleInfo && !is_array($ruleInfo)) {
                $ruleInfo = $ruleInfo->toArray();
            }
            if (empty($ruleInfo)) {
                return [
                    'id'           => $id,
                    'bind_type'    => $bind_type,
                    'rule_id'      => $rule_id,
                    'err_msg'      => '对应收费标准不存在',
                    'parking_lot'  => '',
                    'parkingNum'   => 0,
                ];
            }
        }
        if (!isset($ruleInfo['fees_type']) || $ruleInfo['fees_type']!=newChargeConst::FEES_TYPE_NUMBER_OF_PARKING_SPACES)  {
            return [
                'id'           => $id,
                'bind_type'    => $bind_type,
                'rule_id'      => $rule_id,
                'err_msg'      => '计费模式不支持',
                'parking_lot'  => '',
                'parkingNum'   => 0,
            ];
        }
        $village_id = isset($ruleInfo['village_id']) && $ruleInfo['village_id'] ? $ruleInfo['village_id'] : 0;
        //1:绑定房间 2:绑定车位
        if($bind_type == 2){
            $dbHouseVillageParkingPosition = new HouseVillageParkingPosition();
            $dbHouseVillageParkingGarage = new HouseVillageParkingGarage();
            $wherePosition = [];
            $wherePosition[] = ['position_id', '=', $id];
            $positionInfo = $dbHouseVillageParkingPosition->getFind($wherePosition);
            if ($positionInfo && !is_array($positionInfo)) {
                $positionInfo = $positionInfo->toArray();
            }
            if (isset($positionInfo['position_num']) && $positionInfo['position_num']) {
                $garage_id    = $positionInfo['garage_id'];
                $whereGarage = [];
                $whereGarage[] = ['garage_id', '=', $garage_id];
                $garageInfo = $dbHouseVillageParkingGarage->getOne($whereGarage);
                if ($garageInfo && !is_array($garageInfo)) {
                    $garageInfo = $garageInfo->toArray();
                }
                $garage_num = isset($garageInfo['garage_num']) && $garageInfo['garage_num'] ? $garageInfo['garage_num'] : '';
                if ($garage_num) {
                    $parking_lot  = $garage_num .'-'.$positionInfo['position_num'];
                } else {
                    $parking_lot  = $positionInfo['position_num'];
                }
                $parkingNum   = 1;
            } else {
                $parking_lot = '';
                $parkingNum   = 1;
            }
            return [
                'id'           => $id,
                'bind_type'    => $bind_type,
                'rule_id'      => $rule_id,
                'err_msg'      => '',
                'parking_lot'  => $parking_lot,
                'parkingNum'   => $parkingNum,
            ];
        } elseif($bind_type = 1) {
            $dbHouseVillageUserBind = new HouseVillageUserBind();
            $whereUserBind = [];
            $whereUserBind[] = ['vacancy_id', '=', $id];
            $whereUserBind[] = ['status',     '=', 1];
            $whereUserBind[] = ['village_id', '=', $village_id];
            $userPigcmsIdArr = $dbHouseVillageUserBind->getColumn($whereUserBind, 'pigcms_id');
            if (!empty($userPigcmsIdArr)) {
                $dbHouseVillageBindPosition = new HouseVillageBindPosition();
                $where = [];
                $where[] = ['b.user_id',       'in', $userPigcmsIdArr];
                $where[] = ['b.village_id',    '=', $village_id];
                $where[] = ['p.children_type', '=', 1];
                $park_lot = $dbHouseVillageBindPosition->getUserPositionList($where, 'p.*,g.garage_num');
                if ($park_lot && !is_array($park_lot)) {
                    $park_lot = $park_lot->toArray();
                }
                if (!empty($park_lot)) {
                    $parking_lot_arr = [];
                    foreach ($park_lot as $item) {
                        if (isset($item['position_num']) && $item['position_num']) {
                            $parking_lot_arr[] = $item['garage_num'] .'-'. $item['position_num'];
                        }
                    }
                    $parkingNum  = count($parking_lot_arr);
                    $parking_lot = implode(',', $parking_lot_arr);
                } else {
                    $parkingNum  = 0;
                    $parking_lot = '';
                }
                return [
                    'id'           => $id,
                    'bind_type'    => $bind_type,
                    'rule_id'      => $rule_id,
                    'err_msg'      => '',
                    'parking_lot'  => $parking_lot,
                    'parkingNum'   => $parkingNum,
                ];
            } else {
                return [
                    'id'           => $id,
                    'bind_type'    => $bind_type,
                    'rule_id'      => $rule_id,
                    'err_msg'      => '',
                    'parking_lot'  => '',
                    'parkingNum'   => 0,
                ];
            }
        } else {
            return [
                'id'           => $id,
                'bind_type'    => $bind_type,
                'rule_id'      => $rule_id,
                'err_msg'      => '',
                'positionInfo' => [],
                'parking_lot'  => '',
                'parkingNum'   => 0,
            ];
        }
    }

    public function getChargeOtherConfigInfo($village_id=0){
        $configInfoArr=array();
        $configInfoArr['park_sys_type'] ='';
        return $configInfoArr;
    }
    /**
     * 查询临时车收费标准
     * @author:zhubaodi
     * @date_time: 2022/11/18 14:37
     */
    public function getTempParkRuleList($data){
        
    }


    /**
     * 查询收费标准列表
     * @author:zhubaodi
     * @date_time: 2022/9/29 20:53
     */
    public function getChargeTypeRuleList($village_id = 0, $charge_type = '', $property_id = 0)
    {
        $returnArr = array('list' => array(), 'count' => 0);
        if ($village_id < 1 || empty($charge_type)) {
            return $returnArr;
        }
        $houseVillageDb = new HouseVillage();
        if ($property_id < 1) {
            $houseVillageObj = $houseVillageDb->getInfo(array('village_id' => $village_id), 'village_id,village_name,property_id');
            if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
                $property_id = $houseVillageObj['property_id'];
            }
        }
        if ($property_id < 1) {
            return $returnArr;
        }
        $db_house_new_charge_rule = new HouseNewChargeRule();
        $houseNewChargeNumberDb = new HouseNewChargeNumber();
        $whereSubjectArr = array(['property_id', '=', $property_id], ['status', '<>', 4]);
        if(is_array($charge_type)){
            $whereSubjectArr[]=array('charge_type', 'in', $charge_type);
        }else{
            $whereSubjectArr[]=array('charge_type', '=', $charge_type);
        }
        $subject_id_arr = $houseNewChargeNumberDb->getColumn($whereSubjectArr, 'id');

        if (empty($subject_id_arr)) {
            return $returnArr;
        }
        $whereArr = array();
        $whereArr[] = array('r.village_id', '=', $village_id);
        $whereArr[] = array('p.village_id', '=', $village_id);
        $whereArr[] = array('r.charge_project_id', '>', 0);
        $whereArr[] = array('p.subject_id', 'in', $subject_id_arr);
        $whereArr[] = array('p.status', '<>', 4);
        $whereArr[] = array('r.status', '<>', 4);
        $rule_list_obj = $db_house_new_charge_rule->getLists($whereArr, 'r.id,r.village_id,r.subject_id,r.charge_project_id,r.charge_name,p.name as project_name', 0, 0);
        $count = $db_house_new_charge_rule->getCounts($whereArr);
        $rule_list = array();
        if (!empty($rule_list_obj) && !$rule_list_obj->isEmpty()) {
            $rule_list = $rule_list_obj->toArray();
        }
        $returnArr['list'] = $rule_list;
        $returnArr['count'] = $count > 0 ? $count : 0;
        return $returnArr;

    }
}