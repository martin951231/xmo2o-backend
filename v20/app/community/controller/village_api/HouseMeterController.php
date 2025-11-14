<?php


namespace app\community\controller\village_api;


use app\common\model\service\config\ConfigCustomizationService;
use app\common\model\service\plan\PlanService;
use app\community\controller\CommunityBaseController;
use app\community\model\db\HouseNewChargeProject;
use app\community\model\db\HouseVillageMeterReading;
use app\community\model\db\HouseVillageRoomCustomizedMeters;
use app\community\model\db\HouseVillageVacancyWaterType;
use app\community\model\service\HouseNewCashierService;
use app\community\model\service\HouseNewChargeRuleService;
use app\community\model\service\HouseNewMeterService;
use app\community\model\service\HousePropertyDigitService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageSingleService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\HouseVillageUserVacancyService;
use app\community\model\service\HouseWorkerService;
use app\community\model\service\StorageService;
use app\community\model\service\HeatingService;
class HouseMeterController extends CommunityBaseController
{
    /**
     * 抄表列表
     * @author lijie
     * @date_time 2021/07/09
     * @return \json
     */
    public function getMeterProject()
    {
        $village_id = $this->adminUser['village_id'];
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        $service_house_new_meter = new HouseNewMeterService();
        $chargeType = ['water','electric','gas'];
        $jz_wsxdsj_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        if($jz_wsxdsj_customized_open){
            array_push($chargeType,'new_nuanqifei');
        }
        $where[] = ['p.village_id','=',$village_id];
        $where[] = ['p.status','=',1];
        $where[] = ['c.charge_type','in',$chargeType];
        try{
            $field = 'p.name as project_name,c.charge_number_name as subject_name,charge_type,p.id as project_id,p.subject_id,p.mday';
            if($jz_wsxdsj_customized_open){
                $field .= ',p.meter_use_v,p.reset_btn_show,p.sign_btn_show';
            }
            $data = $service_house_new_meter->getMeterProject($where,$field,$page,$limit,'p.id DESC')->toArray();
            if($data){
                $service_house_new_charge_rule = new HouseNewChargeRuleService();
                foreach ($data as $k=>$v){
                    $rule_id = $service_house_new_charge_rule->getValidChargeRule($v['project_id']);
                    if(!$rule_id){
                        unset($data[$k]);
                        continue;
                    }
                    $rule_info = $service_house_new_charge_rule->getCallInfo(['r.id'=>$rule_id],'r.charge_name,r.unit_price,r.rate,r.id as rule_id');
                    $data[$k]['charge_name'] = $v['project_name'];//注意这个字段转成了项目名称
                    $data[$k]['rule_name'] = $rule_info['charge_name'];// 这个字段才是标准名称
                    $data[$k]['unit_price'] = $rule_info['unit_price'];
                    $data[$k]['rate'] = $rule_info['rate'];
                    $data[$k]['rule_id'] = $rule_info['rule_id'];
                }
                $data = array_values($data);
            }
            $count = $service_house_new_meter->getMeterProjectCount($where);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        $res=array();
        $res['total_limit'] = $limit;
        $res['list'] = $data;
        $res['count'] = $count;
        $res['is_show'] = cfg('meter_reading_price')==1?true:false;

        $res['is_revise_btn']=0;
        //todo 针对定制水电表设备 不支持修改起止度参数
        if((int)cfg('customized_meter_reading')){
            $res['is_revise_btn']=1;
        }
        $res['jz_wsxdsj_customized_open']=$jz_wsxdsj_customized_open ? 1:0;
        $houseVillageService=new HouseVillageService();
        $res['role_addmeter']=$houseVillageService->checkPermissionMenu(112104,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
        $res['role_meterset']=$houseVillageService->checkPermissionMenu(112105,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
        $res['role_managebe']=$houseVillageService->checkPermissionMenu(112106,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
        $res['role_recordmeter']=$houseVillageService->checkPermissionMenu(112107,$this->adminUser,$this->login_role,$this->dismissPermissionRole);

        return api_output(0,$res);
    }

    /**
     * 负责人列表
     * @author lijie
     * @date_time 2021/07/09
     * @return \json
     */
    public function getMeterDirectorList()
    {
        $village_id = $this->adminUser['village_id'];
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        $project_id = $this->request->post('project_id',0);
        $service_house_new_meter = new HouseNewMeterService();
        if($project_id){
            $where[] = ['project_id','=',$project_id];
        }
        $where[] = ['village_id','=',$village_id];
        $where[] = ['status','in',[1,2]];
        try{
            $data = $service_house_new_meter->getMeterDirectorList($where,true,$page,$limit,'id DESC');
            $count = $service_house_new_meter->getMeterDirectorCount($where);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        $res['list'] = $data;
        $res['count'] = $count;
        $res['total_limit'] = $limit;
        $houseVillageService=new HouseVillageService();
        $res['role_addmanage']=$houseVillageService->checkPermissionMenu(112108,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
        $res['role_editmanage']=$houseVillageService->checkPermissionMenu(112109,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
        $res['role_delmanage']=$houseVillageService->checkPermissionMenu(112110,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
        return api_output(0,$res);
    }

    /**
     * 添加负责人
     * @author lijie
     * @date_time 2021/07/09
     * @return \json
     */
    public function addMeterDirector()
    {
        $village_id = $this->adminUser['village_id'];
        $name = $this->request->post('name','');
        $phone = $this->request->post('phone','');
        $status = $this->request->post('status',1);
        $worker_id = $this->request->post('worker_id',0);
        $day = $this->request->post('day',1);
        $dateDay = $this->request->post('dateDay','09:00');
        $project_id = $this->request->post('project_id',0);
        if(!$project_id || !$worker_id || !$status)
            return api_output_error(1001,'缺少必传参数');
        $service_house_new_meter = new HouseNewMeterService();
        $data['name'] = $name;
        $data['phone'] = $phone;
        $data['status'] = $status;
        $data['worker_id'] = $worker_id;
        $data['project_id'] = $project_id;
        $data['village_id'] = $village_id;
        $data['add_time'] = time();
        $data['notice_time'] =$day.','.$dateDay;
        try{
            $id = $service_house_new_meter->addMeterDirector($data);
            if($id){
                if(date('d') > $day){
                    $time = strtotime(date('Y-m',strtotime('+1 month')).'-'.$day.' '.$dateDay);
                }else{
                    $time = strtotime(date('Y-m').'-'.$day.' '.$dateDay);
                }
                /*
                $service_plan = new PlanService();
                $param['plan_time'] = $time;
                $param['space_time'] = 0;
                $param['add_time'] = time();
                $param['file'] = 'sub_village_send_meter_message';
                $param['time_type'] = 1;
                $param['unique_id'] = 'worker_meter_message_'.$id;
                $param['param'] = ['director_id'=>$id];
                $service_plan->addTask($param,1);
                */
            }
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        if($id)
            return api_output(0,[],'添加成功');
        return api_output_error(1001,'服务异常');
    }

    /**
     * 修改负责人信息
     * @author lijie
     * @date_time 2021/07/09
     * @return \json
     */
    public function saveMeterDirector()
    {
        $id = $this->request->post('id',0);
        $name = $this->request->post('name','');
        $phone = $this->request->post('phone','');
        $status = $this->request->post('status',1);
        $worker_id = $this->request->post('worker_id',0);
        $day = $this->request->post('day',1);
        $dateDay = $this->request->post('dateDay','09:00');
        if(!$worker_id || !$status || !$id)
            return api_output_error(1001,'缺少必传参数');
        $service_house_new_meter = new HouseNewMeterService();
        $data['name'] = $name;
        $data['phone'] = $phone;
        $data['status'] = $status;
        $data['worker_id'] = $worker_id;
        $data['update_time'] = time();
        $data['notice_time'] =$day.','.$dateDay;
        try{
            $res = $service_house_new_meter->saveMeterDirector(['id'=>$id],$data);
            if($res){
                $service_plan = new PlanService();
                //$process_info = $service_plan->getOne(['unique_id'=>'worker_meter_message_'.$id]);
                if(date('d') > $day){
                    $time = strtotime(date('Y-m',strtotime('+1 month')).'-'.$day.' '.$dateDay);
                }else{
                    $time = strtotime(date('Y-m').'-'.$day.' '.$dateDay);
                }
                /*
                $service_plan = new PlanService();
                $param['plan_time'] = $time;
                $param['space_time'] = 0;
                $param['add_time'] = time();
                $param['file'] = 'sub_village_send_meter_message';
                $param['time_type'] = 1;
                $param['unique_id'] = 'worker_meter_message_'.$id.time();
                $param['param'] = ['director_id'=>$id];
                $service_plan->addTask($param,1);
                */
            }
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        if($res)
            return api_output(0,[],'编辑成功');
        return api_output_error(1001,'服务异常');
    }

    /**
     * 删除负责人
     * @author lijie
     * @date_time 2021/07/09
     * @return \json
     */
    public function delMeterDirector()
    {
        $id = $this->request->post('id',0);
        if(!$id)
            return api_output_error(1001,'缺少必传参数');
        $service_house_new_meter = new HouseNewMeterService();
        try{
            $res = $service_house_new_meter->saveMeterDirector(['id'=>$id],['status'=>4]);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,[],'删除成功');
    }

    /**
     * 工作人员列表
     * @author lijie
     * @date_time 2021/07/09
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getWorkers()
    {
        $village_id = $this->adminUser['village_id'];
        $service_house_workers = new HouseWorkerService();
        $where[] = ['village_id','=',$village_id];
        $where[] = ['status','=',1];
        $where[] = ['is_del','=',0];
        $data = $service_house_workers->getWorker($where,'wid,name,type,phone');
        return api_output(0,$data);
    }

    /**
     * 负责人详情
     * @author lijie
     * @date_time 2021/07/10
     * @return \json
     */
    public function getWorkerInfo()
    {
        $id = $this->request->post('id',0);
        if(!$id)
            return api_output_error(1001,'缺少必传参数');
        $service_house_new_meter = new HouseNewMeterService();
        try{
            $data = $service_house_new_meter->getMeterDirectorInfo(['id'=>$id],true);
            $notice_time = explode(',',$data['notice_time']);
            $data['day'] = $notice_time[0];
            $data['dateDay'] = $notice_time[1];
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 抄表录入
     * @author lijie
     * @date_time 2021/07/10
     * @return \json
     */
    public function meterReadingAdd()
    {
        $village_id = $this->adminUser['village_id'];
        $role_id=$this->login_role== 5?$this->_uid:0;
        $single_id = $this->request->post('single_id',0);
        $floor_id = $this->request->post('floor_id',0);
        $layer_id = $this->request->post('layer_id',0);
        $vacancy_id = $this->request->post('vacancy_id',0);
        $start_ammeter = $this->request->post('start_ammeter',0);
        $last_ammeter = $this->request->post('last_ammeter',0);
        $charge_name = $this->request->post('charge_name','');
        $unit_price = $this->request->post('unit_price',0);
        $charge_type = $this->request->post('charge_type','','trim');
        $rule_id = $this->request->post('rule_id',0,'intval');
        $project_id = $this->request->post('project_id',0);
        $rate = $this->request->post('rate',1);
        $note = $this->request->post('note','');
        $opt_meter_time = $this->request->post('opt_meter_time','','trim');
        $opt_xtype = $this->request->post('opt_xtype','','trim');  //revise_data 矫正数据记录
        $pic_start = $this->request->post('pic_start',[]);//附件
        $pic_last = $this->request->post('pic_last',[]);//附件
        $water_meter_number = $this->request->post('water_meter_number','');
        $electric_meter_number = $this->request->post('electric_meter_number','');
        $electric_service_rule_id = $this->request->post('electric_service_rule_id','');
        $electric_rule_name = $this->request->post('electric_rule_name','');
        $electric_service_fee = $this->request->post('electric_service_fee',0);
        $electric_project_id = $this->request->post('electric_service_project_id',0);
        $save_type=$this->request->post('save_type','','trim');
        //$is_exceed = $this->request->post('is_exceed',0);//止度是否超过最大刻度，1：超过，0：不超过。超过最大刻度，需要增加一轮刻度
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_new_charge_rule = new HouseNewChargeRuleService();
        $service_house_new_meter = new HouseNewMeterService();
        $service_house_village = new HouseVillageService();
        $service_house_new_cashier = new HouseNewCashierService();


       if ((!$single_id || !$floor_id || !$layer_id) && $vacancy_id ) {
           $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
           $vacancy_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id'=>$vacancy_id],'single_id,floor_id,layer_id,village_id');
           $single_id =$vacancy_info ?  $vacancy_info['single_id']:0;
           $floor_id = $vacancy_info ? $vacancy_info['floor_id']:0;
           $layer_id = $vacancy_info ? $vacancy_info['layer_id']:0;
       }
        $customized_meter_reading=cfg('customized_meter_reading');
        $customized_meter_reading=$customized_meter_reading ? intval($customized_meter_reading):0;
        
        if(!$single_id || !$floor_id || !$layer_id || !$vacancy_id || !$rate || empty($charge_name) || empty($charge_type)){
            return api_output_error(1001,'缺少必传参数');
        }
        if($last_ammeter<=0 && !$customized_meter_reading){
            return api_output_error(1001,'抄表止度不能小于等于0!');
        }
        if($last_ammeter < 0 || $start_ammeter < 0){
            return api_output_error(1001,'抄表起始度不能小于0');
        }
        $jz_wsxdsj_net_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        if($jz_wsxdsj_net_customized_open && $charge_type=='electric' && empty($electric_meter_number)){
            return api_output_error(1001,'请选择一个电表编号！');
        }elseif($jz_wsxdsj_net_customized_open && $charge_type=='water' && empty($water_meter_number)){
            return api_output_error(1001,'请选择一个水表编号！');
        }
        $res = $service_house_new_meter->getIsBind(['project_id'=>$project_id,'vacancy_id'=>$vacancy_id,'is_del'=>1]);
        if(empty($res)){
            return api_output_error(1001,'当前房间没有绑定该收费项目');
        }
        $start_ammeter=$start_ammeter>0 ? round($start_ammeter,2):0;
        $last_ammeter=$last_ammeter>0 ? round($last_ammeter,2):0;
        $is_exceed=0;
        
        $insert_last_ammeter = $last_ammeter>0 ? $last_ammeter: 0;//需要写入的止度
        $meter_number='';
        $meter_type=0;  //1水表2电表;
        $meter_extra_data='';
        if($jz_wsxdsj_net_customized_open && ($water_meter_number || $electric_meter_number)){
            $meter_id = $water_meter_number ? $water_meter_number : $electric_meter_number;
            //查询最大刻度
            $meterInfoObj = (new HouseVillageRoomCustomizedMeters())->getOneData(['id'=>$meter_id],'id,village_id,room_id,meter_type,meter_number,meter_max_v');
            $meterInfo=array();
            if($meterInfoObj && !$meterInfoObj->isEmpty()){
                $meterInfo= $meterInfoObj->toArray();
            }
            $meter_extra_data=$meterInfo;
            $max = (isset($meterInfo['meter_max_v']) && $meterInfo['meter_max_v']>0) ? $meterInfo['meter_max_v'] : 0;
            if($meterInfo && $meterInfo['meter_type']=='water'){
                $meter_type=1;
            }else if($meterInfo && $meterInfo['meter_type']=='electric'){
                $meter_type=2;
            }
            if($meterInfo && $meterInfo['meter_number']){
                $meter_number=$meterInfo['meter_number'];
            }
            if($max <= 0){
                $is_exceed = 0;
            }
            if($max>0 && $last_ammeter>$max){
                $is_exceed = 1; //止度 超过最大刻度
                $insert_last_ammeter=$last_ammeter-$max;
            }else if($max>0 && $start_ammeter>$last_ammeter){
                $is_exceed = 1; //止度小于等于起度 超过最大刻度
                $last_ammeter = bcadd(strval($last_ammeter),strval($max),3);//用来计算的止度
            }
            if($is_exceed == 0 && $last_ammeter<$start_ammeter){
                return api_output_error(1001,'止度不能小于起度！');
            }
        }else if ($last_ammeter<$start_ammeter) {
            return api_output_error(1001,'止度不能小于起度！');
        }
        
        $res = $service_house_new_meter->getIsBind(['project_id' => $project_id, 'vacancy_id' => $vacancy_id, 'is_del' => 1]);
        if (empty($res)) {
            return api_output_error(1001, '当前房间没有绑定该收费项目');
        }
        $info = $service_house_new_charge_rule->getCallInfo(['r.id'=>$rule_id],'n.charge_type,n.water_type,r.*,p.type');


        /*
        $condition1 = [];
        $condition1[] = ['vacancy_id','=',$vacancy_id];
        $condition1[] = ['status','=',1];
        $condition1[] = ['type','in',[0,3,1,2]];
        $bind_list = $service_house_village_user_bind->getList($condition1,true);
        */
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
        $whereArrTmp=array();
        $whereArrTmp[]=array('pigcms_id','=',$vacancy_id);
        $whereArrTmp[]=array('user_status','=',2);  // 2未入住
        $whereArrTmp[]=array('status','in',[1,2,3]);
        $whereArrTmp[]=array('is_del','=',0);
        $room_vacancy=$service_house_village_user_vacancy->getUserVacancyInfo($whereArrTmp);
        $not_house_rate = 100;
        if($room_vacancy && !$room_vacancy->isEmpty()){
            $room_vacancy = $room_vacancy->toArray();
            if(!empty($room_vacancy)){
                $not_house_rate = $info['not_house_rate'];
            }
        }
        $projectBindInfo = $service_house_new_charge_rule->getBindDetail(['project_id'=>$info['charge_project_id'],'rule_id'=>$info['id'],'vacancy_id'=>$vacancy_id]);
        if(isset($projectBindInfo) && !empty($projectBindInfo)){
            if($projectBindInfo['custom_value']){
                $custom_value = $projectBindInfo['custom_value'];
                $custom_number=$custom_value;
            }else{
                $custom_value = 1;
            }
        }else{
            $custom_value = 1;
        }
        $custom_2value=1;
        if($charge_type == 'new_nuanqifei'){
            $heatingService=new HeatingService();
            $param=array('village_id'=>$village_id,'type'=>1,'xtype'=>'flowCharg');
            $flowChargSet=$heatingService->getHeatingProtocol($param);
            if(!is_array($meter_extra_data)){
                $meter_extra_data=array();
            }
            $meter_extra_data['flowChargSet']=$flowChargSet;
            if($flowChargSet['order_yh_per']>0){
                $custom_2value=$flowChargSet['order_yh_per']/100;
            }
            if($flowChargSet['unit_price_base']>0){
                $custom_2value=$custom_2value*$flowChargSet['unit_price_base'];
            }
        }
        $rule_digit=-1;
        if(isset($info['rule_digit']) && $info['rule_digit']>-1 && $info['rule_digit']<5){
            $rule_digit=$info['rule_digit'];
        }
        $digit_type=1;
        $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$village_id],'property_id');
        $db_house_property_digit_service = new HousePropertyDigitService();
        $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$village_info['property_id']]);
        if(!empty($digit_info)){
            $digit_type=$digit_info['type']==2 ? 2:1;
            if($rule_digit<=-1 || $rule_digit>=5){
                $rule_digit=intval($digit_info['meter_digit']);
            }
        }
        $rule_digit=$rule_digit>-1 ? $rule_digit:2;

        $user_info = $service_house_village_user_bind->getBindInfo([['vacancy_id','=',$vacancy_id],['status','=',1],['type','in',[0,3]]],'uid,name,phone,pigcms_id,village_id');

        $start_ammeter = $start_ammeter ?: 0;
        $last_ammeter = $last_ammeter ?: 0;
        
        $system_remarks=(new StorageService())->getRoleData($this->_uid,$this->login_role,$this->adminUser);
        $insertData=array();
        if (isset($info['water_type']) && intval($info['water_type']) > 0) {
            $insertData['water_type'] = intval($info['water_type']);
        }
        $insertData['village_id'] = $village_id;
        $insertData['single_id'] = $single_id;
        $insertData['floor_id'] = $floor_id;
        $insertData['layer_id'] = $layer_id;
        $insertData['layer_num'] = $vacancy_id;
        $insertData['charge_name'] = $charge_name;
        $insertData['unit_price'] = $unit_price;
        $insertData['start_ammeter'] = $start_ammeter;
        $insertData['last_ammeter'] = $insert_last_ammeter;
        $insertData['rate'] = $rate;
        $insertData['note'] = $note;
        $insertData['cost_num'] = $last_ammeter-$start_ammeter;
        $cost_money=$insertData['cost_num']*$unit_price*$rate*($not_house_rate/100)*$custom_value*$custom_2value;
        $cost_money=formatNumber($cost_money, $rule_digit, $digit_type);
        $cost_money=formatNumber($cost_money, 2, 1);
        $insertData['cost_money'] = $cost_money;
        $insertData['add_time'] = time();
        $insertData['project_id'] = $project_id;
        $insertData['role_id'] = $role_id?$role_id:0;

        //todo 同步写入关联数据
        $insertData['user_name'] = $user_info['name'];
        $insertData['user_bind_id'] = $user_info['pigcms_id'];
        $insertData['user_bind_phone'] = $user_info['phone'];
        $insertData['work_name'] = $system_remarks['account'];
        if(!empty($opt_meter_time)){
            $opt_meter_time=strtotime($opt_meter_time);
            if($opt_meter_time>0){
                $insertData['opt_meter_time']=$opt_meter_time;
            }else{
                $insertData['opt_meter_time']=time();
            }
        }
        if(!empty($opt_xtype)){
            $insertData['source_type']=$opt_xtype;
            if($opt_xtype=='revise_data') {
                $insertData['opt_meter_time'] = time();
                $insertData['cost_money']=0;
            }
        }
        if($charge_type == 'new_nuanqifei'){
            $pic_start = $pic_start ? implode(',',$pic_start) : '';
            $pic_last = $pic_last ? implode(',',$pic_last) : '';
            $insertData['pic_start'] = $pic_start;
            $insertData['pic_last'] = $pic_last;
            if($meter_extra_data){
                $insertData['meter_extra_data']= json_encode($meter_extra_data,JSON_UNESCAPED_UNICODE);
            }
            $insertData['meter_type']=3; //新版暖气
        }
        if($jz_wsxdsj_net_customized_open && ($water_meter_number || $electric_meter_number)){
            $insertData['meter_id'] = $water_meter_number ? $water_meter_number : $electric_meter_number;
            $insertData['meter_number']=$meter_number;
            $insertData['meter_type']=$meter_type;
            if($meter_extra_data){
                $insertData['meter_extra_data']= json_encode($meter_extra_data,JSON_UNESCAPED_UNICODE);
            }
            //查询是否超过设定异常刻度
            $meterMax = (new HouseNewChargeProject())->getOne(['id'=>$project_id],'meter_use_v');
            $ammeter = bcsub(strval($last_ammeter),strval($start_ammeter),4);
            if($meterMax && $meterMax['meter_use_v'] > 0 && $ammeter > $meterMax['meter_use_v']){
                $insertData['is_unusual'] = 1;
            }
        }
        try{
            if($save_type=='no_formal_save'){
                $insertDataCache=array('room_id'=>$vacancy_id,'village_id'=>$village_id,'charge_type'=>$charge_type,'project_id'=>$project_id);
                $insertDataCache['floor_id']=$insertData['floor_id'];
                $insertDataCache['rule_id']=$rule_id;
                $insertDataCache['charge_name']=$insertData['charge_name'];
                $insertDataCache['start_ammeter']=$insertData['start_ammeter'];
                $insertDataCache['last_ammeter']=$insertData['last_ammeter'];
                $insertDataCache['note']=$insertData['note'];
                $insertDataCache['cost_money']=$insertData['cost_money'];
                $insertDataCache['cost_num']=$insertData['cost_num'];
                $insertDataCache['single_id']=$insertData['single_id'];
                $insertDataCache['layer_id']=$insertData['layer_id'];
                $insertDataCache['add_time']=$insertData['add_time'];
                $insertDataCache['opt_meter_time']=isset($insertData['opt_meter_time']) ? $insertData['opt_meter_time']:0;
                $extra_data_cache=array('pic_start'=>'','pic_last'=>'');
                if(isset($insertData['pic_start'])){
                    $extra_data_cache['pic_start']=$insertData['pic_start'];
                }
                if(isset($insertData['pic_last'])){
                    $extra_data_cache['pic_last']=$insertData['pic_last'];
                }
                if(isset($meter_extra_data) && $meter_extra_data){
                    $extra_data_cache=array_merge($extra_data_cache,$meter_extra_data);
                }
                $insertDataCache['extra_data']=!empty($extra_data_cache) ? json_encode($extra_data_cache,JSON_UNESCAPED_UNICODE):'';
                $insertDataCache['meter_type']=isset($insertData['meter_type']) ? $insertData['meter_type']:0;
                $whereArrCache=array('room_id'=>$vacancy_id,'village_id'=>$village_id,'charge_type'=>$charge_type,'project_id'=>$project_id,'del_time'=>'0');
                $id = $service_house_new_meter->addMeterReadingCache($insertDataCache,$whereArrCache);
                return api_output(0,['idd'=>$id],'仅保存成功');
            }
            $whereArr=['project_id'=>$project_id,'village_id'=>$village_id,'layer_num'=>$vacancy_id];
            $meter_reading=$service_house_new_meter->getMeterRecordInfo($whereArr,'*','id DESC');
            $id = $service_house_new_meter->addMeterReading($insertData);
            if($id){
                if($charge_type == 'new_nuanqifei'){
                    $whereArrCache=array(['village_id','=',$village_id],['room_id','=',$vacancy_id],['project_id','=',$project_id],['del_time','<',1]);
                    $whereArrCache[]=array('charge_type','=','new_nuanqifei');
                    $service_house_new_meter->updateMeterReadingCache($whereArrCache,array('del_time'=>time()));
                }
                if($opt_xtype=='revise_data'){
                    return api_output(0,[],'矫正录入成功');
                }
                if($last_ammeter<=$start_ammeter){
                    //止度小于等于起度直接成功 不生成订单
                    return api_output(0,[],'抄表成功');
                }
                if($insertData['cost_money']<=0){
                    return api_output(0,[],'抄表成功');
                }
                $orderData=array();
                $user_uid=0;
                if($user_info){
                    $user_uid=$user_info['uid'];
                    $orderData['uid'] = $user_info['uid'];
                    $orderData['name'] = $user_info['name'];
                    $orderData['phone'] = $user_info['phone'];
                    $orderData['pigcms_id'] = $user_info['pigcms_id'];
                }

                $orderData['meter_reading_id'] = $id;
                $orderData['property_id'] = $village_info['property_id'];
                $orderData['village_id'] = $village_id;
                $orderData['order_type'] = $charge_type;
                $orderData['order_name'] = $charge_name;
                $orderData['room_id'] = $vacancy_id;
                $orderData['total_money'] = $insertData['cost_money'];
                $orderData['modify_money'] = $orderData['total_money'];
                $orderData['project_id'] = $project_id;
                $orderData['rule_id'] = $rule_id;
                $orderData['unit_price'] = $unit_price;
                $orderData['last_ammeter'] = $start_ammeter;
                $orderData['now_ammeter'] = $insert_last_ammeter;
                $orderData['add_time'] = time();
                if($not_house_rate>0 && $not_house_rate<100){
                    $orderData['not_house_rate'] = $not_house_rate;
                }
                if(isset($custom_number)){
                    $orderData['number'] = $custom_number;
                }
                $service_start_time=time();
                if($meter_reading && !$meter_reading->isEmpty()){
                    $service_start_time=$meter_reading['add_time'];
                }
                $orderData['service_start_time']=$service_start_time;
                $service_end_time=time();
                $orderData['service_end_time']=$service_end_time;

                $electricityOrder = true;
                if($jz_wsxdsj_net_customized_open && $electric_meter_number && $electric_service_rule_id){
                    //水表编号仅仅记录一下
                    $unify_flage_id = 'electricityloss_'.$id;
                    //有服务费，生成关联标记，unify_flage_id：unify_flage_id：electricityloss_电表记录id   order_type_flag：electricityloss
                    $orderData['unify_flage_id'] = $unify_flage_id;
                    $orderData['order_type_flag'] = 'electricityloss';
                    //有服务费，生成一条有关联订单的服务费订单
                    $electricityOrderData = [
                        'uid'=>$orderData['uid']??0,
                        'name'=>$orderData['name']??'',
                        'phone'=>$orderData['phone']??'',
                        'pigcms_id'=>$orderData['pigcms_id']??0,
                        'meter_reading_id'=>$orderData['meter_reading_id'],
                        'property_id'=>$orderData['property_id'],
                        'village_id'=>$orderData['village_id'],
                        'order_type'=>'electricity_loss',
                        'order_name'=>$electric_rule_name,
                        'room_id'=>$orderData['room_id'],
                        'total_money'=>$electric_service_fee,
                        'modify_money'=>$electric_service_fee,
                        'project_id'=>$electric_project_id,
                        'rule_id'=>$electric_service_rule_id,
                        'unit_price'=>$electric_service_fee,
                        'add_time'=>time(),
                        'service_start_time'=>$orderData['service_start_time'],
                        'service_end_time'=>$orderData['service_end_time'],
                        'unify_flage_id'=>$orderData['unify_flage_id'],
                        'order_type_flag'=>$orderData['order_type_flag'],
                    ];
                    $electricityOrder = $service_house_new_cashier->addOrder($electricityOrderData);
                }
                $res = $service_house_new_cashier->addOrder($orderData);
                if($res && $electricityOrder){                    
                    $charge_all=['water' => '水费', 'electric' => '电费', 'gas' => '燃气费', 'new_nuanqifei' => '取暖费'];
                    if(isset($charge_all[$charge_type]) && !empty($charge_all[$charge_type])){
                        $user_remarks = '后台'.$charge_all[$charge_type].'缴费操作';
                        $opt_money_type = $charge_type=='new_nuanqifei' ? 'qunuanfei' : '';
                        if($charge_type == 'water' && cfg('customized_meter_reading')==1 && isset($info['water_type']) && $info['water_type']==1){//冷水表
                            $opt_money_type = 'cold_water_balance';
                            $user_remarks = '后台冷水费缴费操作，扣除小区冷水费余额';
                        }elseif($charge_type == 'water' && cfg('customized_meter_reading')==1 && isset($info['water_type']) && $info['water_type']==2){//热水表
                            $opt_money_type = 'hot_water_balance';
                            $user_remarks = '后台热水费缴费操作，扣除小区热水费余额';
                        }elseif($charge_type == 'electric' && cfg('customized_meter_reading')==1){
                            $opt_money_type = 'electric_balance';
                            $user_remarks = '后台电费缴费操作，扣除小区电费余额';
                        }
                        if($user_uid>0){
                            $extra_data=array('order_id'=>$res,'meter_reading_id'=>$orderData['meter_reading_id'],'opt_type'=>'meter_split_order');
                            (new StorageService())->userBalanceChange($user_uid,2,$insertData['cost_money'],$system_remarks['remarks'],$user_remarks,$res,$village_id,$opt_money_type,$extra_data);
                        }
                    }
                    return api_output(0,[],'抄表成功');
                }else{
                    return api_output_error(1001,'服务异常');
                }


            }else{
                return api_output_error(1001,'服务异常');
            }
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
    }


    /**
     * 录入费用
     * @author:zhubaodi
     * @date_time: 2022/8/2 9:35
     */
    public function meterReadingPriceAdd()
    {

        $data['village_id'] = $this->adminUser['village_id'];
        $data['uid']=$this->login_role== 5?$this->_uid:0;
        $data['login_role']=$this->login_role;
        $data['adminUser']=$this->adminUser;
        $data['single_id'] = $this->request->post('single_id',0);
        $data['floor_id'] = $this->request->post('floor_id',0);
        $data['layer_id'] = $this->request->post('layer_id',0);
        $data['vacancy_id'] = $this->request->post('vacancy_id',0);
        $data['total'] = $this->request->post('total','','trim');
        $data['offline_pay_type'] = $this->request->post('offline_pay_type',0);
        $data['charge_name'] = $this->request->post('charge_name','');
        $data['unit_price'] = $this->request->post('unit_price',0);
        $data['charge_type'] = $this->request->post('charge_type','');
        $data['rule_id'] = $this->request->post('rule_id',0);
        $data['project_id'] = $this->request->post('project_id',0);
        $data['rate'] = $this->request->post('rate',1);
        $data['note'] = $this->request->post('note','');
        $data['opt_meter_time'] = $this->request->post('opt_meter_time','');
        if(!$data['single_id'] || !$data['floor_id'] || !$data['layer_id'] || !$data['vacancy_id'] || !$data['rate'] || empty($data['charge_name']) || empty($data['charge_type']))
        {
            return api_output_error(1001,'房间不能为空');
        }
        if (empty($data['total'])){
            return api_output_error(1001,'总价不能为空');
        }else{
            if ($data['total'] <= 0) {
                return api_output_error(1001, '总价需要大于0');
            }
            if(!is_numeric($data['total'])){
                return api_output_error(1001,'总价需要是数字');
            }
        }
        if (empty($data['offline_pay_type'])){
            return api_output_error(1001, '线下支付方式不能为空');
        }
        if (empty($data['opt_meter_time'])){
            return api_output_error(1001, '抄表时间不能为空');
        }
        $service_house_new_meter = new HouseNewMeterService();
        try {
            $ret=$service_house_new_meter->meterReadingPriceAdd($data);
            return api_output(0,$ret);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
    }


    /*
     * 生成修改止度增加的费用账单
     * 这个是对已缴的记录再修改后 在生成账单，会产生一条记录对应多个订单 起度 止度 金额都是对不上的 有问题
     * 废弃掉，优化成只能对未缴的记录修改
    */
    public function addMdyMeterReadingOrder(){
        $village_id = $this->adminUser['village_id'];
        $idd = $this->request->post('idd',0,'int');
        if($idd<1){
            return api_output_error(1001,'抄表记录ID错误');
        }
        $service_house_new_meter = new HouseNewMeterService();
        try {
            $system_remarks=(new StorageService())->getRoleData($this->_uid,$this->login_role,$this->adminUser);
            $ret=$service_house_new_meter->addMdyMeterReadingOrder($idd,$village_id,$this->_uid,$system_remarks);
            return api_output(0,$ret,'订单生成成功!');
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
    }

    public function meterReadingEdit(){
        $village_id = $this->adminUser['village_id'];
        $id = $this->request->post('id',0,'int');
        $start_ammeter = $this->request->post('start_ammeter',0);
        $start_ammeter=$start_ammeter>0 ? floatval($start_ammeter):0;
        $last_ammeter = $this->request->post('last_ammeter',0);
        $last_ammeter=$last_ammeter>0 ? floatval($last_ammeter):0;
        $note = $this->request->post('note','');
        $note=$note ? htmlspecialchars($note,ENT_QUOTES) :'';
        $rule_id = $this->request->post('rule_id',0);
        $rule_id=$rule_id>0 ? $rule_id:0;
        $project_id = $this->request->post('project_id',0);
       // $is_exceed = $this->request->post('is_exceed',0);//是否超过最大刻度，1：超过，0：不超过。超过最大刻度，需要增加一轮刻度
        if($id<1){
            return api_output_error(1001,'修改抄表止度参数ID错误');
        }
        if($start_ammeter<0 || $last_ammeter<0){
            return api_output_error(1001,'抄表起始度不能小于0');
        }
        $jz_wsxdsj_net_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        if($start_ammeter>$last_ammeter && !$jz_wsxdsj_net_customized_open){
            return api_output_error(1001,'抄表止度需要大于起度');
        }
        $service_house_new_meter = new HouseNewMeterService();
        try {
            $editData=array('adminUser'=>$this->adminUser,'uid'=>$this->_uid);
            $editData['login_role']=$this->login_role;
            $editData['id']=$id;
            $editData['start_ammeter']=$start_ammeter;
            $editData['last_ammeter']=$last_ammeter;
            $editData['note']=$note;
            $editData['rule_id']=$rule_id;
            $editData['project_id']=$project_id;
            //$editData['is_exceed']=$is_exceed;
            $ret=$service_house_new_meter->meterReadingModifyEdit($editData,$village_id);
            $ret=!empty($ret) ? $ret:array();
            return api_output(0,$ret,'抄表修改成功');
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
    }

    public function getMeterReadingMdylog(){
        $village_id = $this->adminUser['village_id'];
        $meter_reading_id = $this->request->post('meter_reading_id',0,'int');
        if($meter_reading_id<1){
            return api_output(0,['list'=>array()]);
        }
        $service_house_new_meter = new HouseNewMeterService();
        try {
            $ret=$service_house_new_meter->getMeterReadingMdylog($meter_reading_id,$village_id);
            return api_output(0,$ret);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
    }
    /**
     * 判断房间是否绑定项目
     * @return \json
     */
    public function getIsBind()
    {
        $room_id = $this->request->post('vacancy_id',0);
        $project_id = $this->request->post('project_id',0);
        if(!$room_id || !$project_id){
            return api_output_error(1001,'缺少必传参数');
        }
        $service_house_new_meter = new HouseNewMeterService();
        try{
            $data = $service_house_new_meter->getIsBind(['project_id'=>$project_id,'vacancy_id'=>$room_id,'is_del'=>1]);
            if($data){
                $res['status'] = 1;
            }else{
                $res['status'] = 0;
            }
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
    }

    /**
     * 抄表记录
     * @author lijie
     * @date_time 2021/07/10
     * @return \json
     */
    public function getMeterReadingRecord()
    {
        $village_id = $this->adminUser['village_id'];
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        $charge_name = $this->request->post('charge_name');
        $project_id = $this->request->post('project_id',0);
        $single_id = $this->request->post('single_id',0);
        $floor_id = $this->request->post('floor_id',0);
        $layer_id = $this->request->post('layer_id',0);
        $room_id = $this->request->post('room_id',0);
        $date_time = $this->request->post('date_time',[]);
        $transaction_type = $this->request->post('transaction_type',0);
        $is_unusual = $this->request->post('is_unusual',0);
        $split_record_no = $this->request->post('split_record_no','','trim');
        $service_house_new_meter = new HouseNewMeterService();
        $where[] = ['m.village_id','=',$village_id];
        if($project_id){
            $where[] = ['m.project_id','=',$project_id];
        }else{
            if($charge_name)
                $where[] = ['m.charge_name','=',$charge_name];
        }
        if (!empty($transaction_type)){
            $where[] = ['m.transaction_type','=',$transaction_type];
        }
        if($is_unusual==1){
            $where[] = ['m.is_unusual','=',1];
        }
        if($split_record_no){
            $where[] = ['m.split_record_no','=',$split_record_no];
        }
        if(!empty($room_id) && is_array($room_id)){
            if (isset($room_id[3])){
                $where[]=['m.layer_num','=',$room_id[3]];
            }elseif (isset($room_id[2])){
                $where[]=['m.layer_id','=',$room_id[2]];
            }elseif (isset($room_id[1])){
                $where[]=['m.floor_id','=',$room_id[1]];
            } else{
                $where[]=['m.single_id','=',$room_id[0]];
            }
        }
        if($date_time)
            $where[] = ['m.add_time','between',[strtotime($date_time[0]),strtotime($date_time[1].' 23:23:59')]];
        try{
            $data = $service_house_new_meter->getMeterReadingRecord($where,'m.*,a.realname',$page,$limit,'m.id DESC');
            $count = $service_house_new_meter->getMeterReadingRecordCount($where);
            $total_info = $service_house_new_meter->meterReadingStatistics($where);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        $configCustomizationService=new ConfigCustomizationService();
        $jz_wsxdsj_net_customized_open = $configCustomizationService->getWSXCustomizedOpen();
        $res['total_info'] = $total_info;
        $res['list'] = $data;
        $res['count'] = $count;
        $res['total_limit'] = $limit;
        $res['is_show'] = cfg('meter_reading_price')==1?true:false;
        if($project_id<1 && $data && $data['0']){
            $project_id=$data['0']['project_id'];
        }
        $charge_type='';
        if($project_id>0){
            $whereArr=array();
            $whereArr[] = ['p.village_id','=',$village_id];
            $whereArr[] = ['p.status','=',1];
            $whereArr[] = ['p.id','=',$project_id];
            $meterProjectInfo= $service_house_new_meter->getMeterProjectInfo($whereArr,'p.name,p.subject_id,c.charge_type');
            if($meterProjectInfo && isset($meterProjectInfo['charge_type'])){
                $charge_type=$meterProjectInfo['charge_type'];
            }
        }
        $res['jzwsxdsj_customized_meter']=0;
        if($charge_type && $jz_wsxdsj_net_customized_open && in_array($charge_type,['electric','water'])){
            $res['jzwsxdsj_customized_meter']=1;
        }
        $isAihorseCustomized=$configCustomizationService->getAihorseCustomizedJudge();
        $res['isAihorseCustomized']=$isAihorseCustomized;
        $houseVillageService=new HouseVillageService();
        $res['role_export']=$houseVillageService->checkPermissionMenu(112111,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
        $res['role_import']=$houseVillageService->checkPermissionMenu(112112,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
        $res['role_mfymeter']=$houseVillageService->checkPermissionMenu(112113,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
        
        return api_output(0,$res);
    }

    /**
     * 获取上一次抄表止度
     * @author lijie
     * @date_time 2021/07/15
     * @return \json
     */
    public function getLastMeter()
    {
        $project_id = $this->request->post('project_id',0,'intval');
        $rule_id = $this->request->post('rule_id',0,'intval');
        
        $vacancy_id = $this->request->post('vacancy_id',0,'intval');
        $charge_type= $this->request->post('charge_type','','trim');
        $water_meter_number = $this->request->post('water_meter_number',0,'trim');
        $electric_meter_number = $this->request->post('electric_meter_number',0,'trim');
        $service_house_new_meter = new HouseNewMeterService();
        $village_id = $this->adminUser['village_id'];
        try{
            $where = ['project_id'=>$project_id,'layer_num'=>$vacancy_id];
            $meter_id = 0;
            if($water_meter_number || $electric_meter_number){
                $meter_id = $water_meter_number ? $water_meter_number : $electric_meter_number;
                $where['meter_id'] = $meter_id;
            }
            $data = $service_house_new_meter->getMeterRecordInfo($where,'last_ammeter','id DESC');
            if($data && !$data->isEmpty()){
                $data=$data->toArray();
            }else{
                $data=array();
            }
            $jz_wsxdsj_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
            if(isset($data['last_ammeter']) && $data['last_ammeter'] > 0 && $jz_wsxdsj_customized_open && $meter_id){
                //查询水电表的最大刻度，如果大于0需要处理一下
                $meterInfo = (new HouseVillageRoomCustomizedMeters())->getOneData(['id'=>$meter_id],'meter_max_v');
                $max = (isset($meterInfo['meter_max_v']) && $meterInfo['meter_max_v']>0) ? $meterInfo['meter_max_v'] : 0;
                $data['last_ammeter'] = $max ? fmod(floatval($data['last_ammeter']), $max) : $data['last_ammeter'];
            }
            if($charge_type == 'new_nuanqifei'){
                $whereArrCache=array(['village_id','=',$village_id],['room_id','=',$vacancy_id],['project_id','=',$project_id],['del_time','<',1]);
                $whereArrCache[]=array('charge_type','=',$charge_type);
                $meterReadingCache = $service_house_new_meter->getMeterReadingCache($whereArrCache);
                if(!empty($meterReadingCache)){
                    $data['meterReadingCache']=array('last_ammeter'=>$meterReadingCache['last_ammeter']);
                    $data['meterReadingCache']['note']=$meterReadingCache['note'];
                    $data['meterReadingCache']['rule_id']=$meterReadingCache['rule_id'];
                    $data['meterReadingCache']['opt_meter_time']=$meterReadingCache['opt_meter_time']>100 ? date('Y-m-d H:i',$meterReadingCache['opt_meter_time']):'';
                    $pic_start=array();
                    $pic_last=array();
                    $extra_data=$meterReadingCache['extra_data'] ? json_decode($meterReadingCache['extra_data'],true):'';
                    if($extra_data && isset($extra_data['pic_start']) && !empty($extra_data['pic_start'])){
                        $pic_start=explode(',',$extra_data['pic_start']);
                        foreach ($pic_start as $kk=>$img){
                            $pic_start[$kk]=replace_file_domain($img);
                        }
                    }
                    if($extra_data && isset($extra_data['pic_last']) && !empty($extra_data['pic_last'])){
                        $pic_last=explode(',',$extra_data['pic_last']);
                        foreach ($pic_last as $kk=>$img){
                            $pic_last[$kk]=replace_file_domain($img);
                        }
                    }
                    $data['meterReadingCache']['pic_start']=$pic_start;
                    $data['meterReadingCache']['pic_last']=$pic_last;
                }
                
            }
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 导出抄表记录
     * @author lijie
     * @date_time 2021/07/15
     * @return \json
     */
    public function printRecordList()
    {
        $village_id = $this->adminUser['village_id'];
        $charge_name = $this->request->post('charge_name');
        $room_id = $this->request->post('room_id',0);
        $date_time = $this->request->post('date_time',[]);
        $project_id = $this->request->post('project_id',0);
        $is_unusual = $this->request->post('is_unusual',0);
        $split_record_no = $this->request->post('split_record_no','','trim');
        $service_house_new_meter = new HouseNewMeterService();
        $where[] = ['m.village_id','=',$village_id];

        if($project_id){
            $where[] = ['m.project_id','=',$project_id];
        }elseif($charge_name){
            $where[] = ['m.charge_name','=',$charge_name];
        }
        if($is_unusual == 1){
            $where[] = ['m.is_unusual','=',1];
            $file_name = '抄表用量异常记录';
        }
        if($split_record_no){
            $where[] = ['m.split_record_no','=',$split_record_no];
        }
        if($charge_name)
            $where[] = ['m.charge_name','=',$charge_name];
        if(!empty($room_id) && is_array($room_id)){
            if (isset($room_id[3])){
                $where[]=['m.layer_num','=',$room_id[3]];
            }elseif (isset($room_id[2])){
                $where[]=['m.layer_id','=',$room_id[2]];
            }elseif (isset($room_id[1])){
                $where[]=['m.floor_id','=',$room_id[1]];
            } else{
                $where[]=['m.single_id','=',$room_id[0]];
            }
        }
        if($date_time)
            $where[] = ['m.add_time','between',[strtotime($date_time[0].' 00:00:00'),strtotime($date_time[1].' 23:59:59')]];
        try{
            $data = $service_house_new_meter->getMeterReadingRecord($where,'m.*,a.realname',0,15,'m.id DESC')->toArray();
            $file_name = ($file_name ?? '抄表记录').'.xlsx';

            if (cfg('meter_reading_price')==1){
                $title = ['楼栋','单元','楼层','房间号', '业主名', '电话', '单价（元）', '倍率', '抄表时间', '起度', '止度','交易类型', '操作人', '备注'];
                $res = $service_house_new_meter->saveExcel_price($data,$title,$file_name);
            }else{
                $title = ['楼栋','单元','楼层','房间号', '业主名', '电话', '单价（元）', '倍率', '抄表时间', '起度', '止度', '操作人', '备注'];
                $res = $service_house_new_meter->saveExcel($data,$title,$file_name);
            }



        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$res);
    }

    //===============================移动端抄表================================
    public function getFrontMeterProject()
    {
        $room_id = $this->request->post('room_id', 0);
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
        $service_house_new_meter = new HouseNewMeterService();
        $service_house_village = new HouseVillageService();
        $service_house_village_single = new HouseVillageSingleService();
        try{
            $room_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id' => $room_id], 'usernum,name,phone,single_id,floor_id,layer_id,pigcms_id,village_id');
            $village_id = $room_info['village_id'];
            $room_address = $service_house_village->getSingleFloorRoom($room_info['single_id'],$room_info['floor_id'],$room_info['layer_id'],$room_info['pigcms_id'],$room_info['village_id']);
            $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$village_id],'village_address,long,lat,village_name');
            $where[] = ['p.village_id','=',$village_id];
            $where[] = ['p.status','=',1];
            $where[] = ['c.charge_type','in',['water','electric','gas']];
            $data = $service_house_new_meter->getMeterProject($where,'p.name as project_name,c.charge_number_name as subject_name,charge_type,p.id as project_id,r.charge_name,r.unit_price,r.rate,r.id as rule_id',0,0,'p.id DESC');
            $next_room = $service_house_village_user_vacancy->getUserVacancyInfo([['pigcms_id','>',$room_id]],'room,single_id,pigcms_id');
            $single_info = $service_house_village_single->getSingleInfo(['id'=>$next_room['single_id']],'single_name');
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        $res['list'] = $data;
        $res['room_info'] = ['name'=>$room_info['name'],'phone'=>$room_info['phone'],'room_address'=>$village_info['village_name'].$room_address];
        $res['village_info'] = $village_info;
        $res['next_room'] = ['next_room'=>$single_info['single_name'].'#'.$next_room['room'],'room_id'=>$next_room['pigcms_id']];
        return api_output(0,$res);
    }

    /**
     * 移动抄表录入
     * @author lijie
     * @date_time 2021/07/16
     * @return \json
     */
    public function frontMeterReadingAdd()
    {
        $uid = $this->_uid;
        $vacancy_id = $this->request->post('room_id', 0);
        $start_ammeter = $this->request->post('start_ammeter',0);
        $last_ammeter = $this->request->post('last_ammeter',0);
        $charge_name = $this->request->post('charge_name','');
        $unit_price = $this->request->post('unit_price',0,'intval');
        $charge_type = $this->request->post('charge_type','');
        $rule_id = $this->request->post('rule_id',0);
        $project_id = $this->request->post('project_id',0);
        $rate = $this->request->post('rate',1);
        $note = $this->request->post('note','');
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
        $vacancy_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id'=>$vacancy_id],'single_id,floor_id,layer_id,village_id');
        $single_id = $vacancy_info['single_id'];
        $floor_id = $vacancy_info['floor_id'];
        $layer_id = $vacancy_info['layer_id'];
        $village_id = $vacancy_info['village_id'];
        if(!$single_id || !$floor_id || !$layer_id || !$vacancy_id || !$last_ammeter || !$rate || empty($charge_name) || empty($charge_type)){
            return api_output_error(1001,'缺少必传参数');
        }
        $service_house_new_charge_rule = new HouseNewChargeRuleService();
        $rule_info = $service_house_new_charge_rule->getCallInfo(['r.id'=>$rule_id],'n.charge_type,n.water_type,r.*,p.type');
        $rule_digit=-1;
        if(isset($rule_info['rule_digit']) && $rule_info['rule_digit']>-1 && $rule_info['rule_digit']<5){
            $rule_digit=$rule_info['rule_digit'];
        }
        $digit_type=1;
        $service_house_village = new HouseVillageService();
        $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$village_id],'property_id');
        $db_house_property_digit_service = new HousePropertyDigitService();
        $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$village_info['property_id']]);
        if(!empty($digit_info)){
            $digit_type=$digit_info['type']==2 ? 2:1;
            if($rule_digit<=-1 || $rule_digit>=5){
                $rule_digit=intval($digit_info['meter_digit']);
            }
        }
        $rule_digit=$rule_digit>-1 ? $rule_digit:2;
        $insertData=array();
        if (isset($rule_info['water_type']) && intval($rule_info['water_type']) > 0) {
            $insertData['water_type'] = intval($rule_info['water_type']);
        }
        $insertData['village_id'] = $village_id;
        $insertData['single_id'] = $single_id;
        $insertData['floor_id'] = $floor_id;
        $insertData['layer_id'] = $layer_id;
        $insertData['layer_num'] = $vacancy_id;
        $insertData['charge_name'] = $charge_name;
        $insertData['unit_price'] = $unit_price;
        $insertData['start_ammeter'] = $start_ammeter;
        $insertData['last_ammeter'] = $last_ammeter;
        $insertData['rate'] = $rate;
        $insertData['note'] = $note;
        $insertData['cost_num'] = $last_ammeter-$start_ammeter;
        $cost_money = $insertData['cost_num']*$unit_price*$rate;
        $cost_money=formatNumber($cost_money, $rule_digit, $digit_type);
        $cost_money=formatNumber($cost_money, 2, 1);
        $insertData['cost_money']=$cost_money;
        $insertData['add_time'] = time();
        $insertData['project_id'] = $project_id;
        $insertData['role_id'] = $uid?$uid:0;
        $service_house_new_meter = new HouseNewMeterService();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_village = new HouseVillageService();
        $service_house_new_cashier = new HouseNewCashierService();
        try{
            $whereArr=['project_id'=>$project_id,'village_id'=>$village_id,'layer_num'=>$vacancy_id];
            $meter_reading=$service_house_new_meter->getMeterRecordInfo($whereArr,'*','id DESC');
            $id = $service_house_new_meter->addMeterReading($insertData);
            if($id){
                if($insertData['cost_money']<=0){
                    return api_output(0,[],'抄表成功');
                }
                $user_info = $service_house_village_user_bind->getBindInfo([['vacancy_id','=',$vacancy_id],['status','=',1],['type','in',[0,3]]],'uid,name,phone,pigcms_id,village_id');
                $orderData=array();
                if($user_info){
                    $orderData['uid'] = $user_info['uid'];
                    $orderData['name'] = $user_info['name'];
                    $orderData['phone'] = $user_info['phone'];
                    $orderData['pigcms_id'] = $user_info['pigcms_id'];
                    $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$user_info['village_id']],'property_id');
                    $orderData['village_id'] = $village_id;
                    $orderData['property_id'] = $village_info['property_id'];
                }
                $orderData['order_type'] = $charge_type;
                $orderData['order_name'] = $charge_name;
                $orderData['room_id'] = $vacancy_id;
                $orderData['total_money'] = $insertData['cost_money'];
                $orderData['modify_money'] = $orderData['total_money'];
                $orderData['project_id'] = $project_id;
                $orderData['rule_id'] = $rule_id;
                $orderData['unit_price'] = $unit_price;
                $orderData['last_ammeter'] = $start_ammeter;
                $orderData['now_ammeter'] = $last_ammeter;
                $orderData['add_time'] = time();
                $orderData['meter_reading_id'] = $id;
                $service_start_time=time();
                if($meter_reading && !$meter_reading->isEmpty()){
                    $service_start_time=$meter_reading['add_time'];
                }
                $orderData['service_start_time']=$service_start_time;
                $service_end_time=time();
                $orderData['service_end_time']=$service_end_time;
                $res = $service_house_new_cashier->addOrder($orderData);
                if($res) {
                    return api_output(0,[],'抄表成功');
                }
                return api_output_error(1001,'服务异常');
            }else{
                return api_output_error(1001,'服务异常');
            }
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
    }

    /**
     * 移动抄表抄表记录
     * @author lijie
     * @date_time 2021/07/10
     * @return \json
     */
    public function getFrontMeterReadingRecord()
    {
        $village_id = $this->request->post('village_id',0);
        $page = $this->request->post('page',1);
        $project_name = $this->request->post('project_name','');
        $date_time = $this->request->post('date_time',[]);
        $service_house_new_meter = new HouseNewMeterService();
        $where[] = ['m.village_id','=',$village_id];
        if($project_name)
            $where[] = ['p.name','=',$project_name];
        if($date_time)
            $where[] = ['m.add_time','between',[strtotime($date_time[0]),strtotime($date_time[1])]];
        try{
            $data = $service_house_new_meter->getMeterReadingRecord($where,'m.*,a.realname,p.name as project_name',$page,15,'m.id DESC');
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        $res['list'] = $data;
        return api_output(0,$res);
    }

    /**
     * 收费项目列表
     * @author lijie
     * @date_time 2021/07/16
     * @return \json
     */
    public function getMeterProjectList()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $service_house_new_meter = new HouseNewMeterService();
        $where[] = ['p.village_id','=',$village_id];
        $where[] = ['p.status','=',1];
        $where[] = ['c.charge_type','in',['water','electric','gas']];
        try{
            $data = $service_house_new_meter->getMeterProject($where,'p.name as project_name',0,0,'p.id DESC');
            $list[0]['project_name_show'] = '全部';
            $list[0]['project_name'] = '';
            if($data){
                foreach ($data as $k=>$v){
                    $list[$k+1]['project_name'] = $v['project_name'];
                    $list[$k+1]['project_name_show'] = $v['project_name'];
                }
            }
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$list);
    }
    /****
    **抄表余额拆单订单记录
     **/
    public function getMeterReadingSplitOrders(){

        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $room_id = $this->request->post('room_id',0);
        $date_time = $this->request->post('date_time',[]);
        $project_id = $this->request->post('project_id',0);
        $split_record_no = $this->request->post('split_record_no','','trim');
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        $key_val       = $this->request->post('key_val','','trim');
        $key_value         = $this->request->post('key_value','','trim');
        $service_house_new_meter = new HouseNewMeterService();
        $where=array();
        $where[] = ['o.village_id','=',$village_id];
        $where[] = ['m.village_id','=',$village_id];
       // $where[] = ['o.charge_type','in',['water','electric','gas']];
        $where[] = ['o.order_type_flag','=','split_order_by_balance'];
        $where[] = ['o.meter_reading_id','>',0];
        if($project_id>0){
            $where[] = ['o.project_id','=',$project_id];
        }
        if($split_record_no){
            $where[] = ['o.order_serial','=',$split_record_no];
        }
        if(!empty($room_id) && is_array($room_id)){
            if (isset($room_id[3])){
                $where[]=['m.layer_num','=',$room_id[3]];
                $where[]=['o.room_id','=',$room_id[3]];
            }elseif (isset($room_id[2])){
                $where[]=['m.layer_id','=',$room_id[2]];
            }elseif (isset($room_id[1])){
                $where[]=['m.floor_id','=',$room_id[1]];
            } else{
                $where[]=['m.single_id','=',$room_id[0]];
            }
        }
        if (!empty($key_val) && !empty($value)){
            if($key_val=='key_name'){
                $where[] = ['o.name','=',$value];
            }else if($key_val=='key_phone'){
                $where[] = ['o.phone','=',$value];
            }
        }
        if($date_time){
            $where[] = ['o.add_time','between',[strtotime($date_time[0].' 00:00:00'),strtotime($date_time[1].' 23:59:59')]];
        }
        try{
            $fieldStr='o.order_id,o.uid,o.pigcms_id,o.name,o.phone,o.pay_bind_id,o.pay_bind_name,o.pay_bind_phone,o.order_type,o.order_name,o.room_id,o.village_id,o.total_money,o.modify_money,o.pay_money,o.add_time,o.is_discard,o.refund_money';
            $fieldStr .=',o.pay_time,o.pay_type,o.offline_pay_type,o.rule_id,o.project_id,o.service_start_time,o.service_end_time,o.last_ammeter,o.now_ammeter,o.order_serial,o.meter_reading_id,o.village_balance,o.order_type_flag,o.primitive_order_id';
            $fieldStr .=',o.is_paid,o.is_refund,m.split_num,m.split_record_no';
            $datas = $service_house_new_meter->getMeterReadingSplitOrders($where,$fieldStr,$page,$limit,$village_id,'m.id DESC,o.order_id DESC');
            return api_output(0,$datas);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        
        
    }
    /**
     * 导入抄表
     * @return \json
     */
    public function uploadFiles()
    {
        $file = $this->request->file('file');
        if(!$file){
            return api_output_error(1001,'请上传文件');
        }
        try {
            validate(['file' => [
                'fileSize' => 1024 * 1024 * 20,
                'fileExt' => 'xls,xlsx',
            ]])->check(['file' => $file]);

            $fileName = $file->getOriginalName();

            $file_arr = explode(('.'.$file->extension()),$fileName);
            if(count($file_arr)==2)
            {
//                $file_type = $file_arr[1];
                $file_type=$file->extension();
            }else{
                return api_output_error(1001,'请上传有效文件');
            }
            $upload_path=$this->request->get('upload_path','','trim');
            if(empty($upload_path)){
                $upload_path='meter/file';
            }
            $savename = \think\facade\Filesystem::disk('public_upload')->putFile( $upload_path,$file);
            if(strpos($savename,"\\") !== false){
                $savename = str_replace('\\','/',$savename);
            }
            $data['url'] = '/upload/'.$savename;
            $data['name'] = $fileName;
            $data['file_type'] = $file_type;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");

    }

    /**
     * 提交抄表表格
     * @return \json
     */
    public function exportMeter()
    {
        $village_id = $this->adminUser['village_id'];
        $uid = $this->_uid;
        $service = new HouseNewMeterService();
        $file = $this->request->post('file');
        $charge_name = $this->request->post('charge_name','');
        $project_id = $this->request->post('project_id',0,'intval');
        if($project_id<1){
            return api_output_error(1001,'缺少必传参数');
        }
        try {
            $savenum = $service->upload($file,$village_id,$uid,$project_id);
            return api_output(0, $savenum, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    //设置每月多少号 抄表
    public function setMeterReadingDay(){
        $village_id = $this->adminUser['village_id'];
        $mday = $this->request->post('mday',0,'int');
        $project_id = $this->request->post('project_id',0,'int');
        $subject_id = $this->request->post('subject_id',0,'int');
        $meter_use_v = $this->request->post('meter_use_v',0,'int');
        $reset_btn_show = $this->request->post('reset_btn_show',0,'int');
        if($project_id<1){
            return api_output_error(1000,'项目参数错误！');
        }
        try {
            $service_house_new_meter = new HouseNewMeterService();
            $dataArr = array('mday' => $mday,'meter_use_v'=>$meter_use_v,'reset_btn_show'=>$reset_btn_show);
            $whereArr = array('id' => $project_id, 'village_id' => $village_id);
            $jz_wsxdsj_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
            if($jz_wsxdsj_customized_open){
                $dataArr['sign_btn_show'] = $this->request->post('sign_btn_show',0,'int');
            }
            $service_house_new_meter->updateNewChargeProject($whereArr, $dataArr);
            return api_output(0,[], '保存成功！');
        }catch (\Exception $e) {
                return api_output_error(1003, $e->getMessage());
            }
    }

    /**
     * 获取一条抄表记录
     * @return
     */
    public function getOneMeterReading()
    {
        $idd = $this->request->post('id',0,'int');
        $village_id = $this->adminUser['village_id'];
        if($idd<1){
            return api_output_error(1000,'修改记录参数错误！');
        }
        $service_house_new_meter = new HouseNewMeterService();
        try{
            $whereArr=array('id'=>$idd,'village_id'=>$village_id);
            $data = $service_house_new_meter->getOneMeterReading($whereArr);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    public function getMeterCustomizedInfo(){
        $village_id = $this->adminUser['village_id'];
        $room_id = $this->request->post('room_id',0,'int');
        $charge_type = $this->request->post('charge_type','','trim');
        $jz_wsxdsj_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        $jz_wsxdsj_customized_open=$jz_wsxdsj_customized_open ? 1:0;
        $service_house_new_meter = new HouseNewMeterService();
        try{
            $parameter=array('room_id'=>$room_id,'jz_wsxdsj_customized_open'=>$jz_wsxdsj_customized_open);
            $data = $service_house_new_meter->getMeterCustomizedInfo($village_id,$charge_type,$parameter);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }
    
    public function getAddMeterDemo()
    {
        $project_id = $this->request->post('project_id','','trim');
        $service_house_new_meter = new HouseNewMeterService();
        try{
            $data = $service_house_new_meter->getAddMeterDemo($project_id);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return  api_output(0,$data);
    }
}