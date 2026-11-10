<?php


namespace app\community\controller\village_api;


use app\common\model\service\config\ConfigCustomizationService;
use app\common\model\service\UserService;
use app\community\controller\CommunityBaseController;
use app\community\model\db\HouseEConfig;
use app\community\model\db\HouseNewChargeProject;
use app\community\model\db\HouseNewChargeRule;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\User;
use app\community\model\service\HousePropertyDigitService;
use app\community\model\service\HouseNewCashierService;
use app\community\model\service\HouseNewChargePrepaidService;
use app\community\model\service\HouseNewChargeProjectService;
use app\community\model\service\HouseNewChargeService;
use app\community\model\db\HouseVillageInfo;
use app\community\model\service\HouseVillageParkingService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\HouseVillageUserVacancyService;
use app\community\model\service\HouseNewChargeRuleService;
use app\community\model\service\NewPayService;
use app\community\model\service\HouseEConfigService;
use app\community\model\db\HouseNewChargeNumber;
use app\consts\newChargeConst;
use customization\customization;


class CashierApiController extends CommunityBaseController
{
    use customization;
    /**
     * 未缴账单合计页
     * @author lijie
     * @date_time 2021/07/01
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getNopayBill()
    {
        $pigcms_id = $this->request->post('pigcms_id',0);
        $static = $this->request->post('static',true);
        $year = $this->request->post('year','');
        $is_all_select = $this->request->post('is_all_select',true);
        $type = $this->request->post('type',0);
        if(!$pigcms_id)
        {
            return api_output_error(1002,'');
        }
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_village = new HouseVillageService();
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
        $service_house_new_cashier = new HouseNewCashierService();
        $bind_info = $service_house_village_user_bind->getBindInfo(['pigcms_id'=>$pigcms_id],'vacancy_id,village_id,type');
        $vacancy_id = isset($bind_info['vacancy_id']) && intval($bind_info['vacancy_id']) > 0 ? intval($bind_info['vacancy_id']) : -1;
        $village_id = isset($bind_info['village_id']) && intval($bind_info['village_id']) > 0 ? intval($bind_info['village_id']) : 0;

        $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$village_id],'village_name');
        if ($vacancy_id > 0) {
            $user_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id'=>$vacancy_id],'usernum,name,phone,single_id,floor_id,layer_id,pigcms_id,village_id');
        }
        if(isset($user_info) && $user_info){
            $address = $service_house_village->getSingleFloorRoom($user_info['single_id'],$user_info['floor_id'],$user_info['layer_id'],$user_info['pigcms_id'],$user_info['village_id']);
        }else{
            $address = '';
        }
        $res['address'] = $village_info['village_name'].$address;
        $where = [];
        $where[] = ['o.room_id','=',$vacancy_id];
        $where[] = ['o.is_discard','=',1];
        $where[] = ['o.is_paid','=',2];
        $where[] = ['o.order_type','<>','non_motor_vehicle'];
        $where[] = ['o.village_id','=',$village_id];
        $where[]=  ['o.check_status','<>',1];
        $field='o.add_time';
        $data = $service_house_new_cashier->getYear($where,$field,$pigcms_id,$village_id,$vacancy_id,$static,$year,$is_all_select,$type);
        $res['deposit_money']=0;
        if ($vacancy_id > 0) {
            $data_deposit['room_id'] = $vacancy_id;
            $data_deposit['village_id'] = $bind_info['village_id'];
            $data_deposit['money'] = $data['total_money'];
            $deposit_money = $service_house_new_cashier->getDepositInfo($data_deposit);
            if (!empty($deposit_money['total_money'])) {
                $res['deposit_money'] = $deposit_money['total_money'];
            }
        }
        $service_charge_project = new HouseNewChargeProjectService();
        $charge_set_info = $service_charge_project->getChargeSetInfo($village_id);
        $res['is_combine'] = isset($charge_set_info['is_combine'])&&$charge_set_info['is_combine']?$charge_set_info['is_combine']:2;
        $res['list'] = $data['list'];
        $res['is_all_select'] = $data['is_all_select'];
        $res['total_money'] = $data['total_money'];
        $res['selected_count'] = $data['selected_count'];
        $res['room_id'] = $vacancy_id;

        if (isset($bind_info['type']) && $bind_info['type'] == 4) {
            $res['storage_info'] = [];
        } else {
            $res['storage_info'] = (new HouseVillageService)->getVillageStorage($village_id, $pigcms_id);
        }

        $res['domain_restrict_yilvbao'] = false;
        if($this->hasYiLvBao()){
            $res['domain_restrict_yilvbao'] = true;
        }
        $res['share_info']=array('link'=>'');
        $res['share_info']['link']=get_base_url('pages/houseMeter/NewCollectMoney/newLiveExpenses?village_id='.$bind_info['village_id'].'&pigcms_id='.$pigcms_id.'&url_type=village');
        $res['share_info']['title']="账单信息";
        $res['share_info']['desc']="当前企业微信外部联系人的账单信息";
        $res['share_info']['imgUrl']="https://hf.pigcms.com/static/wxapp/images/bill_msg_image_1.png";
   
        
        return api_output(0,$res);
    }

    public function addLog()
    {
        $pigcms_id = $this->request->post('pigcms_id',0);
        $village_id = $this->request->post('village_id',0);
        if(!$pigcms_id || !$village_id){
            $url=get_base_url('/pages/village_menu/my');
            return api_output_redirect($url);
            //return api_output_error(5005,'返回异常！');
        }
        $service_house_new_cashier = new HouseNewCashierService();
        try{
            $service_house_new_cashier->delRecord(['pigcms_id'=>$pigcms_id,'village_id'=>$village_id]);
            $res = $service_house_new_cashier->addLog(['pigcms_id'=>$pigcms_id,'village_id'=>$village_id,'action_name'=>'layOutCycle','add_time'=>time()]);
        }catch (\Exception $e){
            $url=get_base_url('/pages/village_menu/my');
            return api_output_redirect($url);
           // return api_output_error(5005,'返回异常！');
        }
        if($res){
            return api_output(0,[]);
        }
        $url=get_base_url('/pages/village_menu/my');
        return api_output_redirect($url);
        //return api_output_error(5005,'返回异常！');
    }

    /**
     * 未缴账单明细
     * @author lijie
     * @date_time 2021/07/07
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getNoPayDetail()
    {
        $year = $this->request->post('year','');
        $pigcms_id = $this->request->post('pigcms_id',0);
        $project_id = $this->request->post('project_id',0);
        $is_all_select = $this->request->post('is_all_select','');
        $static = $this->request->post('static',true);
        $type = $this->request->post('type',0);
        if(!$year || !$pigcms_id)
            return api_output_error(1001,'缺少必传参数');
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_new_cashier = new HouseNewCashierService();
        try{
            $bind_info = $service_house_village_user_bind->getBindInfo(['pigcms_id'=>$pigcms_id],'vacancy_id,village_id');
            if(empty($bind_info)){
                return api_output_error(1001,'pigcms_id异常');
            }
            $room_id = $bind_info['vacancy_id'];
            $data = $service_house_new_cashier->getNoPayDetail($year,$room_id,0,$project_id,$pigcms_id,$bind_info['village_id'],$is_all_select,$static,$type);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 账单详情
     * @author lijie
     * @date_time 2021/07/07
     * @return \json
     */
    public function getOrderDetail()
    {
        $order_id = $this->request->post('order_id',0);
        if(!$order_id)
            return api_output_error(1001,'缺少必传参数');
        $service_house_new_cashier = new HouseNewCashierService();
        try{
            $field ='o.*,r.late_fee_reckon_day,r.late_fee_top_day,r.late_fee_rate,r.bill_create_set,r.charge_name,p.name as project_name,p.type,o.order_name,r.charge_valid_time';
            $data = $service_house_new_cashier->getOrder(['order_id'=>$order_id], $field,1);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 获取收费项列表
     * @author lijie
     * @date_time 2021/06/16
     * @return \json
     */
    public function getCharges()
    {
        $pigcms_id = $this->request->post('pigcms_id',0);
        if(!$pigcms_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_new_cashier = new HouseNewCashierService();
        $bind_info = $service_house_village_user_bind->getBindInfo(['pigcms_id'=>$pigcms_id],'vacancy_id,type');
        if(empty($bind_info)){
            return api_output_error(1001,'pigcms_id异常');
        }
        $vacancy_id = isset($bind_info['vacancy_id']) && intval($bind_info['vacancy_id']) > 0 ? intval($bind_info['vacancy_id']) : -1;
        $type = isset($bind_info['type']) ? $bind_info['type'] : 0;
        $room_id = $vacancy_id;
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',100);
        if ($type == 4) {
            $res['list'] = [];
            $res['limit'] = $limit;
            $res['count'] = 0;
            return api_output(0,$res);
        }
        $where[] = ['b.vacancy_id','=',$room_id];
        $where[] = ['b.is_del','=',1];
        $where[] = ['r.is_prepaid','=',1];
        $validPrepaid=$service_house_new_cashier->getValidPrepaid($room_id);
        if($validPrepaid){
            $where[] = ['b.id','in',$validPrepaid];
        }else{
            $res['list'] = [];
            $res['limit'] = $limit;
            $res['count'] = 0;
            return api_output(0,$res);
        }
        $field = 'r.id as charge_rule_id,r.fees_type,b.id,p.name as project_name,r.charge_name,b.order_add_type,b.order_add_time,r.charge_valid_time,r.bill_type,r.is_prepaid,p.type,n.charge_type,p.id as project_id,r.bill_create_set';
        $data = $service_house_new_cashier->getPrepaidList($where,$field,$page,$limit,'b.id DESC',$room_id);
        $count = $service_house_new_cashier->getChargeStandardBindCount($where);
        $res['list'] = $data;
        $res['limit'] = 10;
        $res['count'] = $count;
        return api_output(0,$res);
    }

    /**
     * 业主车辆列表
     * @author lijie
     * @date_time 2021/07/08
     * @return \json
     */
    public function getUserCarList()
    {
        $pigcms_id = $this->request->post('pigcms_id',0);
        if(!$pigcms_id)
            return api_output_error(1001,'缺少必传参数');
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',15);
        try{
            $service_house_new_cashier = new HouseNewCashierService();
            $data = $service_house_new_cashier->getUserCarList($pigcms_id,'b.province,b.car_number,c.position_num,b.end_time,c.position_id',$page,$limit);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 获取收费标准预缴周期列表
     * @author lijie
     * @date_time 2021/06/25
     * @return \json
     */
    public function getPrepaid()
    {
        $charge_rule_id = $this->request->post('charge_rule_id',0);
        $project_id = $this->request->post('project_id',0);
        $pigcms_id = $this->request->post('pigcms_id',0);
        $position_id = $this->request->post('position_id',0);
        if(!$charge_rule_id  || !$project_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $service_house_new_charge_prepaid = new HouseNewChargePrepaidService();
        $service_house_new_charge_rule = new HouseNewChargeRuleService();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $where['pre.charge_rule_id'] = $charge_rule_id;
        $where['pre.status'] = 1;
        try{
            $userBindInfo = $service_house_village_user_bind->getBindInfo(['pigcms_id'=>$pigcms_id],'vacancy_id,village_id');
            $rule_info = $service_house_new_charge_rule->get_rule_info(['id'=>$charge_rule_id],'village_id,fees_type,unit_price,unit_gage,bill_create_set,rate,not_house_rate,charge_project_id,bill_create_set,id,cyclicity_set,charge_valid_time,subject_id',$userBindInfo['vacancy_id']?$userBindInfo['vacancy_id']:0,$position_id);
            if(empty($rule_info) || (!isset($rule_info['village_id']) ||$rule_info['village_id']<1)){
                return api_output_error(1001,'预交标准出错了！');
            }
            if($pigcms_id>0 && cfg('new_pay_order')==1){
                $houseNewCashierService=new HouseNewCashierService();
                $whereOrderArr=array();
                $whereOrderArr[]=array('village_id','=',$rule_info['village_id']);
                $whereOrderArr[]=array('project_id','=',$project_id);
                $whereOrderArr[]=array('pigcms_id','=',$pigcms_id);
                $whereOrderArr[]=array('is_paid','=',2);
                $whereOrderArr[]=array('is_discard','=',1);
                $not_pay_order=$houseNewCashierService->getInfo($whereOrderArr);
                if($not_pay_order && isset($not_pay_order['order_id']) && $not_pay_order['order_id']){
                    if($not_pay_order['check_status']==1){
                        return api_output_error(1001,'您还有未缴的'.$not_pay_order['order_name'].'订单，在等待作废审核，请耐心等待，或联系商家催促审核！');
                    }else{
                        return api_output_error(1001,'您还有未缴的'.$not_pay_order['order_name'].'订单，请先去缴费掉哦！');
                    }
                    
                }
            }
            if($userBindInfo && !$position_id){
                $room_id = $userBindInfo['vacancy_id'];
                $where['room_id'] = $room_id;
                $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
                $whereArrTmp=array();
                $whereArrTmp[]=array('pigcms_id','=',$room_id);
                $whereArrTmp[]=array('user_status','=',2);
                $whereArrTmp[]=array('status','in',[1,2,3]);
                $whereArrTmp[]=array('is_del','=',0);
                $vacancy_info = $service_house_village_user_vacancy->getUserVacancyInfo($whereArrTmp,'user_status');
                $not_house_rate = 1;
                if($vacancy_info && !$vacancy_info->isEmpty()){
                    $vacancy_info = $vacancy_info->toArray();
                    if(!empty($vacancy_info)){
                        $not_house_rate = $rule_info['not_house_rate']/100;
                    }
                }
                $projectBindInfo = $service_house_new_charge_rule->getBindDetail(['project_id'=>$rule_info['charge_project_id'],'rule_id'=>$rule_info['id'],'vacancy_id'=>$room_id,'is_del'=>1]);
                if(isset($projectBindInfo) && !empty($projectBindInfo['custom_value'])){
                    if($projectBindInfo['custom_value']) {
                        $custom_value = $projectBindInfo['custom_value'];
                    }else {
                        $custom_value = 1;
                    }
                }else{
                    $custom_value = 1;
                }
            }else{
                $not_house_rate = 1;
                $where['position_id'] = $position_id;
                $projectBindInfo = $service_house_new_charge_rule->getBindDetail(['project_id'=>$rule_info['charge_project_id'],'rule_id'=>$rule_info['id'],'position_id'=>$position_id,'is_del'=>1]);
                if(isset($projectBindInfo) && !empty($projectBindInfo['custom_value'])){
                    $custom_value = $projectBindInfo['custom_value'];
                }else{
                    $custom_value = 1;
                }
            }
            $data = $service_house_new_charge_prepaid->getPrepaidList($where,'pre.*,r.rate as r_rule_rate,r.id as charge_rule_id,r.fees_type,r.charge_project_id,charge_price,r.bill_create_set',strtotime($rule_info['service_end_time']),$not_house_rate,$custom_value,$userBindInfo['village_id']);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if(!$rule_info['cyclicity_set'])
            $rule_info['big_cycle' ] = 100;
        else
            $rule_info['big_cycle'] = $rule_info['cyclicity_set'];
        if(empty($data)){
            $rule_info['min_cycle'] = 0;
        }else{
            $count = count($data);
            $rule_info['min_cycle'] = $data[$count-1]['cycle'];
        }
        $status=0;
        $url='';
        if(isset($userBindInfo['village_id']) && !empty($userBindInfo['village_id']) && $rule_info){
            $number_info = (new HouseNewChargeNumber())->get_one(['id'=>$rule_info['subject_id']],'charge_type');
            if($number_info && $number_info['charge_type'] == 'property'){
                $status=intval(cfg('cockpit')) ? 1 : 0;
                $url=cfg('site_url').'/wap.php?g=Wap&c=My&a=recharge&label=village_property'.'_'.$userBindInfo['village_id'].'_'.$pigcms_id;
            }
        }
        $rule_info['property_storage']=[
            'status'=> $status,
            'title' => '自定义费用',
            'url'   => $url
        ];
        $res['info'] = $rule_info;
        $res['list'] = $data;
        return api_output(0,$res);
    }

    /**
     *手动输入预缴周期
     * @author lijie
     * @date_time 2021/07/29
     * @return \json
     */
    public function getGivenPrepaid()
    {
        $charge_rule_id = $this->request->post('charge_rule_id',0);
        $give_cycle = $this->request->post('give_cycle',0);
        $pigcms_id = $this->request->post('pigcms_id',0);
        if(!$charge_rule_id || !$give_cycle || !$pigcms_id)
            return api_output_error(1001,'缺少必传参数');
        $service_house_new_charge_prepaid = new HouseNewChargePrepaidService();
        $service_house_new_charge_rule = new HouseNewChargeRuleService();
        $where['pre.charge_rule_id'] = $charge_rule_id;
        $where['pre.status'] = 1;
        try{
            $rule_info = $service_house_new_charge_rule->get_rule_info(['id'=>$charge_rule_id],'fees_type,unit_price,unit_gage,bill_create_set,rate,not_house_rate,charge_project_id,bill_create_set');
            $data = $service_house_new_charge_prepaid->getGivenPrepaid($where,'pre.*,r.rate as r_rule_rate,r.fees_type,r.charge_project_id,charge_price,r.bill_create_set,r.not_house_rate',strtotime($rule_info['service_end_time']),$give_cycle,$pigcms_id,$rule_info['order_type']);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 预缴支付
     * @author lijie
     * @date_time 2021/07/27
     * @return \json
     */
    public function prepaidGoPay()
    {
        $village_id = $this->request->post('village_id',0);
        $prepaid_id = $this->request->post('prepaid_id', 0);
        $project_id = $this->request->post('project_id', 0);
        $pigcms_id = $this->request->post('pigcms_id', 0);
        $position_id = $this->request->post('position_id',0);
        $notenum = $this->request->post('notenum',0);
        $app_type = $this->request->post('app_type','');
        if (!$prepaid_id || !$project_id || !$pigcms_id || !$village_id) {
            return api_output_error(1001, '缺少必传参数');
        }
        $service_new_pay = new NewPayService();
        $service_house_new_charge_rule = new HouseNewChargeRuleService();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_new_charge = new HouseNewChargeService();
        $service_house_village = new HouseVillageService();
        $service_house_new_charge_prepaid = new HouseNewChargePrepaidService();
        $service_house_new_cashier = new HouseNewCashierService();
        $where['pre.id'] = $prepaid_id;
        try{
            $contract_info = $service_house_new_charge_rule->getHouseVillageInfo(['village_id'=>$village_id],'contract_time_start,contract_time_end');

            $field = 'pre.*,r.charge_price,r.not_house_rate,r.unit_gage,r.fees_type,r.rate as r_rate,r.charge_project_id,r.rule_digit,p.village_id,n.charge_type';
            $prepaid_info = $service_house_new_charge_prepaid->getPrepaidDetail($where, $field);
            if (empty($prepaid_info)) {
                return api_output_error(1001, '参数异常');
            }
            if($notenum){
                $prepaid_info['cycle'] = $notenum;
            }
            $info = $service_house_new_charge_rule->getCallInfo(['r.id' => $prepaid_info['charge_rule_id']], 'n.charge_type,r.*');
            if ($info && !is_array($info)) {
                $info = $info->toArray();
            }
            if (empty($info)) {
                return api_output_error(1001, '数据不存在');
            }
            $postData['order_type'] = $info['charge_type'];
            $postData['order_name'] = $service_house_new_charge->charge_type[$info['charge_type']];
            $postData['village_id'] = $prepaid_info['village_id'];
            $village_info = $service_house_village->getHouseVillageInfo(['village_id' => $prepaid_info['village_id']], 'property_id');
            $postData['property_id'] = $village_info['property_id'];
            $postData['service_month_num'] = $prepaid_info['cycle'];
            $postData['prepaid_cycle'] = $prepaid_info['cycle'];
            $userBindInfo = $service_house_village_user_bind->getBindInfo(['pigcms_id'=>$pigcms_id],'vacancy_id');
            $room_id = $userBindInfo['vacancy_id'];
            if($room_id){
                $type = 1;
                $id = $room_id;
            }else{
                $type = 2;
                $id = $position_id;
            }
            $is_allow = $service_house_new_charge_rule->checkChargeValid($project_id, $prepaid_info['charge_rule_id'],$id,$type);
            if (!$is_allow) {
                return api_output_error(1001, '当前收费标准未生效');
            }
            if($type == 2 && $info['fees_type'] == 2 && empty($info['unit_gage'])) {
                return api_output_error(1001,'车场没有房屋面积，无法生成账单');
            }
            if($info['fees_type'] == newChargeConst::FEES_TYPE_NUMBER_OF_PARKING_SPACES) {
               $ruleHasParkNumInfo = $service_house_new_charge_rule->getRuleHasParkNumInfo($id, $prepaid_info['charge_rule_id'], $type, $info);
               if (empty($ruleHasParkNumInfo) || !isset($ruleHasParkNumInfo['parkingNum']) || intval($ruleHasParkNumInfo['parkingNum']) <= 0) {
                   return api_output_error(1001,'计费模式为车位数量缺少车位数量，无法生成账单');
               }
               $parkingNum  = $ruleHasParkNumInfo['parkingNum'];
            } else {
               $parkingNum  = 1;
            }
            if($room_id && !$position_id) {
                $history_list = $service_house_new_cashier->getOneOrder(['room_id'=>$room_id,'project_id'=>$project_id,'is_discard'=>1,'is_paid'=>2,'position_id'=>0]);
            }
            else {
                $history_list = $service_house_new_cashier->getOneOrder(['position_id'=>$position_id,'project_id'=>$project_id,'is_discard'=>1,'is_paid'=>2]);
            }
            if(isset($history_list['order_id'])){
                return api_output_error(1001,'该项目存在未支付的账单，请先支付');
            }
            if($info['cyclicity_set'] > 0){
                if(!$position_id) {
                    $order_list = $service_house_new_cashier->getOrderLists(['o.is_discard'=>1,'o.room_id'=>$room_id,'o.project_id'=>$project_id,'o.rule_id'=>$info['id'],'o.is_refund'=>1],'o.*');
                }
                else {
                    $order_list = $service_house_new_cashier->getOrderLists(['o.is_discard'=>1,'o.position_id'=>$position_id,'o.project_id'=>$project_id,'o.rule_id'=>$info['id'],'o.is_refund'=>1],'o.*');
                }
                if($order_list){
                    $order_list = $order_list->toArray();
                    if(count($order_list) >= $info['cyclicity_set']) {
                        return api_output_error(1001,'超过最大缴费周期数');
                    }
                    $order_count = 0;
                    foreach ($order_list as $item){
                        if($item['service_month_num'] == 0) {
                            $order_count += 1;
                        }
                        else {
                            $order_count = $order_count+$item['service_month_num']+$item['service_give_month_num'];
                        }
                    }
                    if($order_count >= $info['cyclicity_set']) {
                        return api_output_error(1001,'超过最大缴费周期数');
                    }
                }
            }
            //$vacancy_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id'=>$room_id],'user_status')->toArray();
            if(!$position_id){
                /*
                $condition1 = [];
                $condition1[] = ['vacancy_id','=',$room_id];
                $condition1[] = ['status','=',1];
                $condition1[] = ['type','in',[0,3,1,2]];
                $bind_list = $service_house_village_user_bind->getList($condition1,true);
                */
                $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
                $whereArrTmp=array();
                $whereArrTmp[]=array('pigcms_id','=',$room_id);
                $whereArrTmp[]=array('user_status','=',2);
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
                $projectBindInfo = $service_house_new_charge_rule->getBindDetail(['project_id'=>$info['charge_project_id'],'rule_id'=>$info['id'],'vacancy_id'=>$room_id]);
            }else{
                $service_house_village_parking = new HouseVillageParkingService();
                $carInfo = $service_house_village_parking->getCar(['car_position_id'=>$position_id]);
                if($carInfo){
                    $carInfo = $carInfo->toArray();
                }
                if(empty($carInfo)) {
                    $not_house_rate = $info['not_house_rate'];
                }
                else {
                    $not_house_rate = 100;
                }
            }
            if($position_id){
                $projectBindInfo = $service_house_new_charge_rule->getBindDetail(['project_id'=>$info['charge_project_id'],'rule_id'=>$info['id'],'position_id'=>$position_id]);
            }
            if(isset($projectBindInfo) && !empty($projectBindInfo)){
                $custom_value = $projectBindInfo['custom_value'];
                $custom_number=$custom_value;
            }else{
                $custom_value = 1;
            }
            if ($info['fees_type'] == 1) {
                if ($prepaid_info['type'] == 1) {
                    $postData['total_money'] = $info['charge_price'] * $not_house_rate/ 100 * $prepaid_info['rate']/100*$info['rate']* $prepaid_info['cycle'];
                    $postData['rate'] = $prepaid_info['rate'];
                    $postData['diy_content'] = '折扣率'.$prepaid_info['rate'].'%';
                    $postData['diy_type'] = 1;
                } elseif ($prepaid_info['type'] == 2) {
                    $postData['total_money'] = $info['charge_price'] * $not_house_rate / 100 * $prepaid_info['cycle']*$info['rate'];
                    $postData['service_give_month_num'] = $prepaid_info['give_cycle_type'];
                    if ($info['bill_create_set'] == 1) {
                        $postData['diy_content'] = '赠送周期'.$prepaid_info['give_cycle_type'].'天';
                    } elseif ($info['bill_create_set'] == 2) {
                        $postData['diy_content'] = '赠送周期'.$prepaid_info['give_cycle_type'].'个月';
                    } else {
                        $postData['diy_content'] = '赠送周期'.$prepaid_info['give_cycle_type'].'年';
                    }
                    $postData['diy_type'] = 2;
                } elseif ($prepaid_info['type'] == 3) {
                    $postData['total_money'] = $info['charge_price'] * $not_house_rate / 100 * $prepaid_info['cycle']*$info['rate'];
                    $postData['diy_content'] = $prepaid_info['custom_txt'];
                    $postData['diy_type'] = 3;
                } else {
                    $postData['total_money'] = $info['charge_price'] * $not_house_rate/ 100 * $prepaid_info['cycle']*$info['rate'];
                    $postData['diy_type'] = 4;
                }
            } else {
                if ($prepaid_info['type'] == 1) {
                    $postData['total_money'] = $info['charge_price'] * $not_house_rate/ 100 * $prepaid_info['rate']/100 * $prepaid_info['cycle'] * $custom_value*$info['rate'];
                    $postData['rate'] = $prepaid_info['rate'];
                    $postData['diy_content'] = '折扣率'.$prepaid_info['rate'].'%';
                    $postData['diy_type'] = 1;
                } elseif ($prepaid_info['type'] == 2) {
                    $postData['total_money'] = $info['charge_price'] * $not_house_rate/ 100 * $prepaid_info['cycle'] * $custom_value*$info['rate'];
                    $postData['service_give_month_num'] = $prepaid_info['give_cycle_type'];
                    if ($info['bill_create_set'] == 1) {
                        $postData['diy_content'] = '赠送周期'.$prepaid_info['give_cycle_type'].'天';
                    } elseif ($info['bill_create_set'] == 2) {
                        $postData['diy_content'] = '赠送周期'.$prepaid_info['give_cycle_type'].'个月';
                    } else {
                        $postData['diy_content'] = '赠送周期'.$prepaid_info['give_cycle_type'].'年';
                    }
                    $postData['diy_type'] = 2;
                } elseif ($prepaid_info['type'] == 3) {
                    $postData['total_money'] = $info['charge_price'] * $not_house_rate/ 100 * $prepaid_info['cycle'] * $custom_value*$info['rate'];
                    $postData['diy_content'] = $prepaid_info['custom_txt'];
                    $postData['diy_content'] = $prepaid_info['custom_txt'];
                    $postData['diy_type'] = 3;
                } else {
                    $postData['total_money'] = $info['charge_price'] * $not_house_rate/ 100 * $prepaid_info['cycle'] * $custom_value*$info['rate'];
                    $postData['diy_type'] = 4;
                }
            }

            $db_house_property_digit_service = new HousePropertyDigitService();
            $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$postData['property_id']]);
            $rule_digit=0;
            if(isset($info['rule_digit']) && $info['rule_digit']>-1 && $info['rule_digit']<5){
                $rule_digit=$info['rule_digit'];
                if(!empty($digit_info)){
                    $digit_info['meter_digit']=$rule_digit;
                    $digit_info['other_digit']=$rule_digit;
                }else{
                    $digit_info=array('type'=>1);
                    $digit_info['meter_digit']=$rule_digit;
                    $digit_info['other_digit']=$rule_digit;
                }
            }
            if($info['fees_type'] == newChargeConst::FEES_TYPE_NUMBER_OF_PARKING_SPACES && $parkingNum > 1) {
                $postData['total_money'] = $postData['total_money'] * intval($parkingNum);
                $postData['parking_num'] = intval($parkingNum);
                $postData['parking_lot'] = isset($ruleHasParkNumInfo) && isset($ruleHasParkNumInfo['parking_lot']) ? $ruleHasParkNumInfo['parking_lot'] : '';
            }
            if(empty($digit_info)){
                $postData['total_money']   = formatNumber($postData['total_money'],2,1);
            }else{
                $digit_info['other_digit'] = ($digit_info['other_digit']>2 || empty($digit_info['other_digit'])) && $digit_info['other_digit']!=0?2:$digit_info['other_digit'];
                $postData['total_money']   = formatNumber($postData['total_money'],$digit_info['other_digit'],$digit_info['type']);
                $postData['total_money']   = formatNumber($postData['total_money'], 2, 1);
            }
            if($not_house_rate>0 && $not_house_rate<100){
                $postData['not_house_rate'] = $not_house_rate;
            }
            if(isset($custom_number)){
                $postData['number'] = $custom_number;
            }
            $postData['modify_money']      = $postData['total_money'];
            $postData['prepare_pay_money'] = $postData['total_money'];
            $postData['is_paid'] = 2;
            $postData['is_prepare'] = 1;
            $postData['rule_id'] = $info['id'];
            $postData['project_id'] = $info['charge_project_id'];
            $postData['order_no'] = '';
            $postData['add_time'] = time();
            $postData['from'] = 1;
            $projectInfo = $service_house_new_cashier->getProjectInfo(['id'=>$project_id],'subject_id');
            $numberInfo = $service_house_new_cashier->getNumberInfo(['id'=>$projectInfo['subject_id']],'charge_type');
            if($position_id) {
                $last_order = $service_house_new_cashier->getOrderLog([['position_id','=',$position_id],['order_type','=',$numberInfo['charge_type']],['project_id','=',$info['charge_project_id']]],true,'id DESC');
            }
            else {
                $last_order = $service_house_new_cashier->getOrderLog([['room_id','=',$room_id],['order_type','=',$numberInfo['charge_type']],['project_id','=',$info['charge_project_id']],['position_id','=',0]],true,'id DESC');
            }
            if($numberInfo['charge_type'] == 'property'){
                if($type != 1){
                    $where22=[
                        ['position_id','=',$position_id],
                        ['order_type','=',$numberInfo['charge_type']],
                    ];
                }else{
                    $where22=[
                        ['room_id','=',$room_id],
                        ['order_type','=',$numberInfo['charge_type']],
                        ['position_id','=',0]
                    ];
                }
                $new_order_log = $service_house_new_cashier->getOrderLog($where22,true,'id DESC');
                if(!empty($new_order_log)){
                    $last_order=$new_order_log;
                }
            }
            if($last_order){
                $postData['service_start_time'] = $last_order['service_end_time']+1;
                $postData['service_start_time'] = strtotime(date('Y-m-d',$postData['service_start_time']));
            } else{
                if(isset($projectBindInfo) && !empty($projectBindInfo['order_add_time'])){
                    $postData['service_start_time'] = strtotime(date('Y-m-d',$projectBindInfo['order_add_time']));
                    if(!$postData['service_start_time']){
                        $postData['service_start_time'] = strtotime(date('Y-m-d',$info['charge_valid_time']));
                    }
                }else{
                    $postData['service_start_time'] = strtotime(date('Y-m-d',$info['charge_valid_time']));
                }
            }
            $postData['pigcms_id'] = $pigcms_id;
            if(isset($postData['service_give_month_num'])){
                $cycle = $postData['service_give_month_num'] + $postData['service_month_num'];
            }else{
                $cycle = $postData['service_month_num'];
            }
            if ($info['bill_create_set'] == 1) {
                $postData['service_end_time'] = $postData['service_start_time'] + $cycle * 86400-1;
            } elseif ($info['bill_create_set'] == 2) {
                //todo 判断是不是按照自然月来生成订单
                if(cfg('open_natural_month') == 1){
                    $postData['service_end_time'] = strtotime("+" . $cycle . " month", $postData['service_start_time'])-1;
                }else{
                    $cycle = $cycle*30;
                    $postData['service_end_time'] = strtotime("+".$cycle." day",$postData['service_start_time'])-1;
                }
            } else {
                $postData['service_end_time'] = strtotime("+" . $cycle . " year", $postData['service_start_time'])-1;
            }
            if(isset($contract_info['contract_time_start']) && $contract_info['contract_time_start'] > 1){
                if($postData['service_start_time'] < $contract_info['contract_time_start'] || $postData['service_start_time'] > $contract_info['contract_time_end']){
                    return api_output_error(1001,'账单开始时间不在合同范围内');
                }
                if($postData['service_end_time'] < $contract_info['contract_time_start'] || $postData['service_end_time'] > $contract_info['contract_time_end']){
                    return api_output_error(1001,'账单结束时间不在合同范围内');
                }
            }
            $postData['unit_price'] = $info['charge_price'];
            if ($position_id) {
                $service_house_village_parking = new HouseVillageParkingService();
                $postData['position_id'] = $position_id;
                /*
                $bind_position = $service_house_village_parking->getBindPosition(['position_id' => $postData['position_id']]);
                $user_info = $service_house_village_user_bind->getBindInfo(['pigcms_id' => $bind_position['user_id']], 'pigcms_id,name,phone,uid,vacancy_id');
                */
                $user_info=$service_house_new_cashier->getRoomUserBindByPosition($position_id,$postData['village_id']);
                if($user_info){
                    $postData['pigcms_id'] = $user_info['pigcms_id'] ? $user_info['pigcms_id']:0;
                    $postData['name'] = $user_info['name'] ? $user_info['name']:'';
                    $postData['uid'] = $user_info['uid']?$user_info['uid']:0;
                    $postData['phone'] = $user_info['phone'] ? $user_info['phone']:'';
                    $postData['room_id'] = $user_info['vacancy_id' ]? $user_info['vacancy_id']:0;
                }
            }else{
                if ($room_id) {
                    $postData['room_id'] = $room_id;
                    $user_info = $service_house_village_user_bind->getBindInfo([['vacancy_id', '=', $postData['room_id']], ['type', 'in', '0,3'], ['status', '=', 1]], 'pigcms_id,name,phone,uid');
                    if ($user_info) {
                        $postData['name'] = $user_info['name'] ? $user_info['name'] : '';
                        $postData['phone'] = $user_info['phone'] ? $user_info['phone'] : '';
                        $postData['uid'] = $user_info['uid'] ? $user_info['uid'] : 0;
                    }
                }
            }
            $pay_user_info = $service_house_village_user_bind->getBindInfo(['pigcms_id'=>$pigcms_id]);
            $postData['pay_bind_name'] = $pay_user_info['name'];
            $postData['pay_bind_id'] = $pigcms_id;
            $postData['pay_bind_phone'] = $pay_user_info['phone'];
            $id = $service_house_new_cashier->addOrder($postData);
            if($id){
                $postData['order_id'] = $id;
                $data = $service_new_pay->CashierPrepaidGoPay($postData,$village_id,$pigcms_id,$app_type);
                return api_output(0,$data);
            }
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
    }

    /**
     * 收费项目列表
     * @author lijie
     * @date_time 2021/07/14
     * @return \json
     */
    public function ChargeProjectList()
    {
        $village_id = $this->request->post('village_id',0);
        if(!$village_id){
            return api_output_error(1001, '缺少必传参数小区id');
        }
        $HouseNewChargeProjectService = new HouseNewChargeProjectService();
        $service_house_e_config = new HouseEConfigService();
        $where['p.village_id'] = $village_id;
        $where['p.status'] = 1;
        try{
            $list = $HouseNewChargeProjectService->getLists($where,'p.id,p.name,p.type,c.charge_type')->toArray();
            array_unshift($list, ['id'=>0,'name'=>'全部']);
            $is_open_e = $service_house_e_config->getEConfig(['village_id'=>$village_id],'is_open,is_tui');
            if(empty($is_open_e['is_tui']) || $is_open_e['is_tui'] == 1)
                $is_open = 2;
            else
                $is_open = $is_open_e['is_open'];
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,['list'=>$list,'is_open'=>$is_open]);
    }

    /**
     * 已缴账单
     * @author lijie
     * @date_time 2021/07/08
     * @return \json
     */
    public function payableOrderList(){
        $service_house_new_cashier = new HouseNewCashierService();
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        $date = $this->request->post('date','','trim');
        $project_id = $this->request->post('project_id',0,'intval');
        $pigcms_id = $this->request->post('pigcms_id',0,'intval');
        $village_id = $this->request->post('village_id',0,'intval');
        $order_type = $this->request->post('order_type','','trim');
        if($pigcms_id<1){
            return api_output_error(1001, '缴费人未绑定小区房间，绑定后可查看缴费记录');
        }
        try{
            $whereRaw ='(`o`.`is_refund`=1 OR `o`.`refund_money`<`o`.`modify_money`)';
            $service_house_village_user_bind = new HouseVillageUserBindService();
            $bind_info = $service_house_village_user_bind->getBindInfo(['pigcms_id'=>$pigcms_id],'vacancy_id,village_id,uid,phone');
            if(!empty($bind_info) && $bind_info['vacancy_id']>0){
                $village_id=$bind_info['village_id'];
                $whereArr=['vacancy_id'=>$bind_info['vacancy_id'],'village_id'=>$bind_info['village_id'],'status'=>1];
                $bind_list = $service_house_village_user_bind->getList($whereArr,'pigcms_id,uid');
                $pay_bind_ids = $uid_ary = array();
                if(!$bind_list->isEmpty()){
                    $tmpArr=$bind_list->toArray();
                    foreach ($tmpArr as $item){
                        $pay_bind_ids[]=$item['pigcms_id'];
                        $uid_ary[]=$item['uid'];
                    }

                }
                $pay_bind_phone = isset($bind_info['phone']) && trim($bind_info['phone']) ? trim($bind_info['phone']) : '';
                $pay_bind_ids[]=$pigcms_id;
                $pay_bind_ids=array_unique($pay_bind_ids);
//                $where[] = ['o.pay_bind_id','in',$pay_bind_ids];
                if ($pay_bind_phone) {
                    $whereRaw .= ' AND ((`o`.`pay_bind_phone` = '.$pay_bind_phone.') or (`o`.`pay_bind_id` in ('.implode(',',$pay_bind_ids).')) or (`o`.`pay_bind_id`=0 and `o`.`order_type` = "qrcode" and `o`.`uid` in  ('.implode(',',$uid_ary).')))';
                } else {
                    $whereRaw .= ' AND ((`o`.`pay_bind_id` in ('.implode(',',$pay_bind_ids).')) or (`o`.`pay_bind_id`=0 and `o`.`order_type` = "qrcode" and `o`.`uid` in  ('.implode(',',$uid_ary).')))';
                }
            }else{
//                $where[] = ['o.pay_bind_id','=',$pigcms_id];
                $whereRaw .= ' AND ((`o`.`pay_bind_id` = '.$pigcms_id.') or (`o`.`pay_bind_id`=0 and `o`.`order_type` = "qrcode" and `o`.`uid` = '.$bind_info['uid'].'))';
            }

            if (!empty($date)){
                $where[] = ['o.pay_time','>=',strtotime($date.' 00:00:00')];
                $where[] = ['o.pay_time','<=',strtotime($date.' 23:59:59')];
            }
            if (!empty($project_id)){
                $where[] = ['o.project_id','=',$project_id];
            }
            $where[] = ['o.is_discard','=',1];
            $where[] = ['o.is_paid','=',1];
            if ($order_type) {
                $where[] = ['o.order_type','=',$order_type];
            } else {
                $where[] = ['o.order_type','<>','non_motor_vehicle'];
            }
            $where[] = ['o.village_id','=',$village_id];
            
            $field='o.car_number,o.car_type,o.order_id,o.order_no,o.room_id,o.position_id,p.name as project_name,o.order_name,o.modify_money,o.late_payment_money,o.pay_money,o.pay_bind_name,o.pay_bind_phone,o.pay_time,o.pay_type,o.is_refund,o.refund_money,o.village_id,o.property_id,o.order_type,o.invoice_status';
            $list = $service_house_new_cashier->getCancelOrderUser($where,$whereRaw,$field,$page,$limit,'o.pay_time DESC');

        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 订单详情
     * @return array|\json|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2021/06/15
     */
    public function getPayOrderInfo()
    {
        $order_id = $this->request->post('order_id', 0);
        if (!$order_id){
            return api_output_error(1001, '缺少必传参数');  
        }
        $service_house_new_cashier = new HouseNewCashierService();
        $where[] = ['o.order_id', '=', $order_id];
        $field = 'o.name,o.room_id,o.position_id,o.parking_num,o.parking_lot,r.charge_valid_type,o.meter_reading_id,o.car_number,o.car_type,o.summary_id,o.pay_amount_points,o.system_balance,o.score_deducte,o.score_used_count,o.is_prepare,o.order_type,o.total_money,o.modify_money,o.diy_content,o.service_month_num,service_give_month_num,o.is_auto,o.modify_money,o.add_time,r.charge_name,p.name as project_name,p.type,o.order_name,r.late_fee_reckon_day,r.late_fee_rate,r.late_fee_top_day,r.charge_valid_time,r.bill_create_set,o.order_no,o.modify_reason,o.pay_money,o.discount_money,o.discount_detail,o.extra_data,o.pay_type,o.offline_pay_type,o.pay_time,o.unit_price,o.service_month_num,o.diy_content,o.service_start_time,o.service_end_time,o.order_id,o.is_refund,o.refund_money,o.last_ammeter,o.now_ammeter,o.late_payment_money,o.late_payment_day,o.village_id,o.property_id,o.village_balance,o.offline_pay_money';
        $field .= ',r.unit_price as ticket_rule_unit_price,r.fees_type as ticket_fees_type,r.unit_gage_type as ticket_unit_gage_type,r.unit_gage as ticket_unit_gage,r.measure_unit as ticket_measure_unit';
        $data = $service_house_new_cashier->getOrderInfo($where, $field,1);
        return api_output(0, $data);
    }

    /**
     * 用户端生成支付总账单
     * @author lijie
     * @date_time 2021/07/08
     * @return \json
     */
    public function goPay()
    {
        $village_id = $this->request->post('village_id',0);
        $pigcms_id = $this->request->post('pigcms_id',0);
        $app_type = $this->request->post('app_type','');
        $data['pay_money'] = $this->request->post('pay_money','');//待支付金额
        $data['deposit_money'] = $this->request->post('deposit_money','');//押金抵扣金额
        $data['deposit_type'] = $this->request->post('deposit_type','');//是否开启押金抵扣
        $data['order_type'] = $this->request->post('order_type','');//支付项目类型
        $data['rule_id'] = $this->request->post('rule_id',0);//支付项目标准id(二维码项目)
        $summary_id = $this->request->param('summary_id', 0,'intval');
        $data['resource_from']=$this->request->post('resource_from','','trim');
        $uid = $this->request->param('uid', 0,'intval');
        $data['uid']=$uid;
        if($this->request->log_uid>0){
            $data['uid']=$this->request->log_uid;
        }
        $data['summary_id'] = $summary_id;
        
        if($village_id <1){
            return api_output_error(1001,'缺少必传参数village_id');
        }
        if( (!$pigcms_id && $summary_id<1 && $data['order_type']!='qrcode')){
            return api_output_error(1001,'缺少必传参数');
        }
        if ($summary_id>0 && ($data['uid']<1 && $pigcms_id<1)){
            return api_output_error(1001, '用户信息不存在！');
        }
        $service_new_pay = new NewPayService();
        $service_house_new_cashier = new HouseNewCashierService();

        try{
            $service_house_new_cashier->addLog(['pigcms_id'=>$pigcms_id,'village_id'=>$village_id,'action_name'=>'layOutCycle','add_time'=>time()]);
            if($data['order_type']=='qrcode'){//二维码项目付款
                $data = $service_new_pay->CashierApiGoPayQrcode($pigcms_id,$village_id,$app_type,$data);
            }else{
                $data = $service_new_pay->CashierApiGoPay($pigcms_id,$village_id,$app_type,$data);
            }
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }


    /**
     * 查询账单周期为年的账单列表
     * @author:zhubaodi
     * @date_time: 2023/2/14 17:30
     */
    public function getYearsOrderList(){
        $order_id = $this->request->param('order_id', 0);
        if (!$order_id){
            return api_output_error(1001, '缺少必传参数');
        }
        $service_house_new_cashier = new HouseNewCashierService();
        try {
            $data = $service_house_new_cashier->getYearsOrderList($order_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
      
        return api_output(0, $data);
    }

    /**
     * 获取待缴账单的类别
     * @author:zhubaodi
     * @date_time: 2023/2/15 10:28
     */
    public function getOrderChargeTypeList(){
        $pigcms_id = $this->request->param('pigcms_id', 0,'intval');
        $summary_id = $this->request->param('summary_id', 0,'intval');
        $uid = $this->request->param('uid', 0,'intval');
        $village_id = $this->request->post('village_id',0,'intval');
        if ($pigcms_id<1 && ($summary_id<1)){
            return api_output_error(1001, '缺少必传参数');
        }
        if($this->request->log_uid>0){
            $uid=$this->request->log_uid;
        }
        if ($summary_id>0 && ($uid<1 && $pigcms_id<1)){
            return api_output_error(1001, '用户信息不存在！');
        }
        $configCustomizationService=new ConfigCustomizationService();
        $jz_wsxdsj_customized_open = $configCustomizationService->getWSXCustomizedOpen();
        $service_house_new_cashier = new HouseNewCashierService();
        try {
            $extra_data=array('summary_id'=>$summary_id,'uid'=>$uid,'village_id'=>$village_id);
            
            $data = $service_house_new_cashier->getOrderChargeTypeList($pigcms_id,$extra_data);
            $is_grapefruit_prepaid=isset($data['is_grapefruit_prepaid']) ? $data['is_grapefruit_prepaid']:0;

            if (isset($data['type']) && intval($data['type']) == 4) {
                $data['storage_info'] = [];
                $data['order_id'] = [];
                $data['charge_type'] = [];
            } else {
                $data['storage_info'] = (new HouseVillageService)->getVillageStorage($data['village_id'], $pigcms_id, $is_grapefruit_prepaid);
            }

            $data['domain_restrict_yilvbao'] = false;
            if($this->hasYiLvBao()){
                $data['domain_restrict_yilvbao'] = true;
            }
            $data['share_info']=array('link'=>'');
            $data['share_info']['link']=get_base_url('pages/houseMeter/NewCollectMoney/newLiveExpenses?village_id='.$data['village_id'].'&pigcms_id='.$pigcms_id.'&url_type=village');
            $data['share_info']['title']="账单信息";
            $data['share_info']['desc']="当前企业微信外部联系人的账单信息";
            $data['share_info']['imgUrl']="https://hf.pigcms.com/static/wxapp/images/bill_msg_image_1.png";
            $data['jz_wsxdsj_customized_open'] = $jz_wsxdsj_customized_open ? 1 : 0;
            $isShowHangPayOrderInfo=false;
            $is_aihorse_customized=$configCustomizationService->getAihorseCustomizedJudge();
            if($jz_wsxdsj_customized_open){
                $isShowHangPayOrderInfo=true;
                $regular = $electric = [];
                foreach ($data['charge_type'] as $v){
                    if(in_array($v['key'],['electric','electricity_loss'])){
                        $electric[] = $v;
                    }else{
                        $regular[] = $v;
                    }
                }
                //常规费用、电费（电费、服务费）
                $data['charge_all'] = [
                    [
                        'title'=>'常规费用',
                        'item'=>$regular
                    ],
                    [
                        'title'=>'电费',
                        'item'=>$electric
                    ]
                ];
            }
            if($is_aihorse_customized){
                $isShowHangPayOrderInfo=true;
            }
            $data['hangOrderTips']='';
            if ($isShowHangPayOrderInfo && $data['vacancy_id']) {
                $whereJzOpen = [];
                $whereJzOpen[] = ['o.is_paid', '=', 2];
                $whereJzOpen[] = ['o.is_discard', '=', 3];
                $whereJzOpen[] = ['o.village_id', '=', $village_id];
                $whereJzOpen[] = ['o.room_id', '=', $data['vacancy_id']];
                $countJzOpen = $service_house_new_cashier->getNewPayOrdersCount($whereJzOpen);
                if ($countJzOpen > 0) {
                    $hangOrderMoney= $service_house_new_cashier->getNewPayOrder2Sum($whereJzOpen,'o.modify_money');
                    $hangOrderMoney = round($hangOrderMoney,2);
                    $data['hangOrderTips'] = "您有".$countJzOpen."笔历史欠缴账单（总金额为".$hangOrderMoney."元），具体账单详情请咨询物业";
                }
            }
            $data['log_uid']=$this->request->log_uid;
            $service_charge_project = new HouseNewChargeProjectService();
            $charge_set_info = $service_charge_project->getChargeSetInfo($village_id);
            $data['is_combine'] = isset($charge_set_info['is_combine']) && $charge_set_info['is_combine'] ==1 ? 1:0;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $data);
    }

    /**
     * 获取待缴账单的项目列表
     * @author:zhubaodi
     * @date_time: 2023/2/15 10:28
     */
    public function getOrderProjectList(){
        $data=array();
        $data['vacancy_id'] = $this->request->param('vacancy_id', 0);
        $data['village_id'] = $this->request->param('village_id', 0);
        $data['charge_type'] = $this->request->param('charge_type', '');
        $data['subject_id'] = $this->request->param('subject_id', 0,'intval');  //科目id
        $summary_id = $this->request->param('summary_id', 0,'intval');
        $uid = $this->request->param('uid', 0,'intval');
        if($this->request->log_uid>0){
            $uid=$this->request->log_uid;
        }
        $data['summary_id']=$summary_id;
        $data['uid']=$uid;
        if (!$data['vacancy_id'] && $summary_id<1){
            return api_output_error(1001, '房间id不能为空');
        }
        if (!$data['village_id']){
            return api_output_error(1001, '小区id不能为空');
        }
        if (!$data['charge_type']){
            return api_output_error(1001, '账单类别不能为空');
        }
        $service_house_new_cashier = new HouseNewCashierService();
        try {
            $res = $service_house_new_cashier->getOrderProjectList($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $res);
    }


    /**
     * 获取待缴账单的收费标准列表
     * @author:zhubaodi
     * @date_time: 2023/2/15 10:28
     */
    public function getOrderRuleList(){
        $data=array();
        $data['vacancy_id'] = $this->request->param('vacancy_id', 0);
        $data['village_id'] = $this->request->param('village_id', 0);
        $data['project_id'] = $this->request->param('project_id', 0);
        $summary_id = $this->request->param('summary_id', 0,'intval');
        $uid = $this->request->param('uid', 0,'intval');
        if($this->request->log_uid>0){
            $uid=$this->request->log_uid;
        }
        $data['summary_id']=$summary_id;
        $data['uid']=$uid;
        if (!$data['vacancy_id'] && $summary_id<1){
            return api_output_error(1001, '房间id不能为空');
        }
        if (!$data['village_id']){
            return api_output_error(1001, '小区id不能为空');
        }
        if (!$data['project_id']){
            return api_output_error(1001, '项目id不能为空');
        }
        $service_house_new_cashier = new HouseNewCashierService();
        try {
            $res = $service_house_new_cashier->getOrderRuleList($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $res);
    }


    /**
     * 根据收费标准获取待缴账单列表
     * @author:zhubaodi
     * @date_time: 2023/2/15 10:28
     */
    public function getChargeOrderList(){
        $data=array();
        $data['vacancy_id'] = $this->request->param('vacancy_id', 0,'int');
        $data['village_id'] = $this->request->param('village_id', 0,'int');
        $data['rule_id'] = $this->request->param('rule_id', 0,'int');
        $data['project_id'] = $this->request->param('project_id', 0,'int');
        $summary_id = $this->request->param('summary_id', 0,'intval');
        $uid = $this->request->param('uid', 0,'intval');
        if($this->request->log_uid>0){
            $uid=$this->request->log_uid;
        }
        $data['summary_id']=$summary_id;
        $data['uid']=$uid;
        if (!$data['vacancy_id']&& $summary_id<1){
            return api_output_error(1001, '房间id不能为空');
        }
        if (!$data['village_id']){
            return api_output_error(1001, '小区id不能为空');
        }
        if (!$data['rule_id']){
            return api_output_error(1001, '收费标准id不能为空');
        }
        $service_house_new_cashier = new HouseNewCashierService();
        try {
            $res = $service_house_new_cashier->getChargeOrderList($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $res);
    }

    /**
     * 计算勾选的订单金额
     * @author:zhubaodi
     * @date_time: 2023/2/15 18:55
     */
    public function CalculateOrderTotal (){
        $data=array();
        $data['all_checked'] = $this->request->param('all_checked');  //全选
        $data['charge_type'] = $this->request->param('charge_type'); //项目全选
        $data['order_id'] = $this->request->param('order_id'); //部分选
        $data['vacancy_id'] = $this->request->param('vacancy_id',0);
        $data['village_id'] = $this->request->param('village_id',0);
        $data['pigcms_id'] = $this->request->param('pigcms_id',0,'intval');
        $data['subject_id'] = $this->request->param('subject_id',0);
        $summary_id = $this->request->param('summary_id', 0,'intval');
        $uid = $this->request->param('uid', 0,'intval');
        if($this->request->log_uid>0){
            $uid=$this->request->log_uid;
        }
        $data['uid']=$uid;
        $data['summary_id'] = $summary_id;
        $data['is_electric'] = $this->request->param('is_electric',0,'intval');//是否是电费或者服务费
        if (!$data['vacancy_id'] && $summary_id<1){
            return api_output_error(1001, '房间id不能为空');
        }
        if (!$data['village_id']){
            return api_output_error(1001, '小区id不能为空');
        }
        if (!$data['pigcms_id'] && $summary_id<1){
            return api_output_error(1001, '住户id不能为空');
        }
        if ($summary_id>0 && ($uid<1 && $data['pigcms_id']<1)){
            return api_output_error(1001, '用户信息不存在！');
        }
        if (empty($data['all_checked']) && empty($data['charge_type']) && empty($data['order_id'])){
            $res= ['count'=>0,'total_money'=>0,'pay_money'=>0,'Discount_money'=>0,'all_checked'=>false,'charge_type'=>[],'order_id'=>[]];
            return api_output(0, $res);
        }
        $service_house_new_cashier = new HouseNewCashierService();
        try {
            $res = $service_house_new_cashier->CalculateOrderTotal($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $res);
    }
    
    public function test(){

        $use_result = (new UserService())->userScore(112358769, 5, L_("物业缴费 ，扣除X1 ，订单编号X2", array( "X1" => cfg('score_name'), "X2" => '220224034854112358769')));

        print_r($use_result);exit;
    }

    /**
     * 获取二维码收款信息
     */
    public function getQrcodeInfo()
    {
        $village_id = $this->request->post('village_id',0);
        $project_id  = $this->request->post('project_id',0);
        $uid  = $this->request->post('uid',0);
        try{
            if(!$village_id || !$project_id){
                throw_exception(L_('缺少必要参数'));
            }
            if(!$uid){
                throw_exception(L_('请先登录'));
            }
            $where = [
                'village_id'=>$village_id,
                'id'=>$project_id,
                'status'=>1
            ];
            $projectInfo = (new HouseNewChargeProject())->where($where)->field('name,subject_id')->find();
            if(!$projectInfo){
                throw_exception(L_('收费项目不存在或已删除'));
            }
            $projectType = (new HouseNewChargeNumber())->where('id',$projectInfo['subject_id'])->value('charge_type');
            if(!$projectType){
                throw_exception(L_('收费科目不存在或已删除'));
            }
            $detail = (new HouseNewChargeRule())->getList(['charge_project_id'=>$project_id,'status'=>1],'id as rule_id,charge_price,market_price')->toArray();
            if(!$detail){
                throw_exception(L_('收费项目标准不存在或已删除，无法缴费'));
            }
            foreach ($detail as &$v){
                $v['charge_price'] = getFormatNumber($v['charge_price']);
                $v['market_price'] = getFormatNumber($v['market_price']);
                $v['market_price'] = (!$v['market_price'] || $v['market_price']<$v['charge_price']) ? '' : $v['market_price'];
                $v['unit'] = L_('元');
                $v['market_price_txt'] = L_('市场价');
            }
            $userBind = (new HouseVillageUserBind())->where([
                'uid'=>$uid,
                'village_id'=>$village_id,
                'status'=>1
            ])->field('name,pigcms_id')->find();
            if(!$userBind){
                $userBind['name'] = '';
                $userBind['pigcms_id'] = 0;
            }
            $data['project_id'] = $project_id;
            //标题
            $data['title'] = L_('二维码收款');
            //小区名称
            $data['village_name_txt'] = L_('当前小区');
            $data['village_name'] = (new HouseVillage())->where('village_id',$village_id)->value('village_name')?:'';
            //用户名称
            $data['user_name_txt'] = L_('用户');
            $data['user_name'] = $userBind['name']?:((new User())->where('uid',$uid)->value('nickname')?:'');
            //收费项目
            $data['project_name_txt'] = L_('收费项目');
            $data['project_name'] = $projectInfo['name'];
            //用户小区绑定id
            $data['pigcms_id'] = $userBind['pigcms_id'];
            $data['order_type'] = $projectType;
            //收费标准
            $data['item'] = $detail;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }
}

