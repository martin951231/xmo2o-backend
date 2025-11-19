<?php


namespace app\community\model\service;

use app\common\model\service\config\ConfigCustomizationService;
use app\common\model\service\send_message\SmsService;
use app\community\model\db\CloseHeatingAuditLog;
use app\community\model\db\CloseHeatingList;
use app\community\model\db\CloseHeatingRoom;
use app\community\model\db\CloseHeatingSend;
use app\community\model\db\FlowChargingAuditLog;
use app\community\model\db\FlowChargingList;
use app\community\model\db\HeatingProtocol;
use app\community\model\db\HouseNewCharge;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\HouseNewChargeStandardBind;
use app\consts\newChargeConst;
use think\facade\Db;

class HeatingService
{
    /**
     * 申请停暖审核列表
     */
    public function closeHeatingList($param)
    {
        $param['status'] = $param['status'] ?? -1;
        $param['keyword'] = $param['keyword'] ?? '';
        $param['name'] = $param['name'] ?? '';
        $param['phone'] = $param['phone'] ?? '';
        $param['audit_status'] = $param['audit_status'] ?? -1;
        $param['is_measure_up'] = $param['is_measure_up'] ?? -1;
        $pigcmsId = $param['pigcms_id'] ?? 0;
        $where = [
            ['is_del','=',0],
            ['village_id','=',$param['village_id']],
        ];
        
        if($param['status'] == 0){//待审核
            $where[] = ['audit_status','=',0];
        }elseif($param['status'] == 1){//已审核
            $where[] = ['audit_status','>',0];
        }elseif($param['status'] == 2){//已检查
            $where[] = ['is_measure_up','>',0];
        }
        
        if($param['keyword']){
            $where[] = ['name|phone','like','%'.htmlentities($param['keyword']).'%'];
        }
        
        if($pigcmsId){
            $userBindInfo = (new HouseVillageUserBindService())->getBindInfo(['pigcms_id'=>$pigcmsId],'vacancy_id');
            $room_id = $userBindInfo['vacancy_id'] ?? 0;
            $where[] = ['room_id','=',$room_id];
        }
        if($param['name']){
            $where[] = ['name','like','%'.htmlentities($param['name']).'%'];
        }
        if($param['phone']){
            $where[] = ['phone','=',$param['phone']];
        }
        if($param['audit_status'] > -1){
            $where[] = ['audit_status','=',$param['audit_status']];
        }
        if($param['is_measure_up'] > -1){
            $where[] = ['is_measure_up','=',$param['is_measure_up']];
        }
        $field = 'pigcms_id,sn,name,phone,application_time,room_id,audit_status,is_measure_up,user_type';
        $order = 'pigcms_id desc';
        $list = (new CloseHeatingList())->getList($where,$field,$order,$param['limit']);
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $house_village_service=new HouseVillageService();
        $db_house_new_pay_order = new HouseNewPayOrder();
        $houseVillageService=new HouseVillageService();
        foreach ($list['data'] as $k=>$v){
            $list['data'][$k]['application_time'] = $v['application_time'] ? date('Y-m-d H:i:s',$v['application_time']) : '';
            $list['data'][$k]['audit_status_msg'] = (new CloseHeatingList())->getAuditStatusMsg($v['audit_status']);
            $list['data'][$k]['is_measure_up_msg'] = (new CloseHeatingList())->getIsMeasureUpMsg($v['is_measure_up']);
            $list['data'][$k]['user_type_msg'] = (new CloseHeatingList())->getUserTypeMsg($v['user_type']);
            //获取房间名称
            $room = $db_house_village_user_vacancy->getLists(['pigcms_id' => $v['room_id']]);
            $room1 = $room[0];
            $number =$house_village_service->word_replce_msg(array('single_name'=>$room1['single_name'],'floor_name'=>$room1['floor_name'],'layer'=>$room1['layer_name'],'room'=>$room1['room']),$param['village_id']);
            $list['data'][$k]['room'] = $number;
            //权限
            $select=$pigcmsId ? 1 : $houseVillageService->checkPermissionMenu(1113008,$param['adminUser'],$param['login_role'],$param['dismissPermissionRole']);
            $role_audit=$pigcmsId ? 1 : $houseVillageService->checkPermissionMenu(1113009,$param['adminUser'],$param['login_role'],$param['dismissPermissionRole']);
            $get_role_measure_up=$pigcmsId ? 1 : $houseVillageService->checkPermissionMenu(1113010,$param['adminUser'],$param['login_role'],$param['dismissPermissionRole']);
            $role_audit = $v['audit_status'] == 0 ? $role_audit : 0;
            if($pigcmsId){
                $role_delete = $v['audit_status'] > 0 ? 0 : 1;
            }else{
                $role_delete=$pigcmsId ? 1 : $houseVillageService->checkPermissionMenu(1113011,$param['adminUser'],$param['login_role'],$param['dismissPermissionRole']);
                $role_delete = ($v['audit_status'] == 2 || $v['is_measure_up'] > 0) ? $role_delete : 0;
            }
            $role_measure_up = 0;
            if($v['is_measure_up'] > 0){
                $role_measure_up = 0;
            }elseif($get_role_measure_up && $v['audit_status'] == 1 && $v['user_type'] == 1){
                $role_measure_up = 1;
            }elseif($get_role_measure_up && $v['is_measure_up'] == 0 && $v['audit_status'] == 1 && $v['user_type'] == 2){
                $where = [];
                $where[] = ['o.room_id','=',$v['room_id']];
                $where[] = ['o.is_discard','=',1];
                $where[] = ['o.is_paid','=',2];
                $where[] = ['o.order_type','in', ['new_nuanqifei','heating']];
                $where[] = ['o.village_id','=',$param['village_id']];
                $where[]=  ['o.check_status','<>',1];
                $field='o.order_id';
                $order= $db_house_new_pay_order->getList($where, $field, 0);
                if(empty($order) || $order->isEmpty()){
                    $role_measure_up = 1;
                }
            }
            $list['data'][$k]['role'] = [
                'delete' => $role_delete,//删除(审核未通过、已检查)
            ];
            if(!$pigcmsId){
                $list['data'][$k]['role']['select'] = $select;//查看
                $list['data'][$k]['role']['audit'] = $role_audit;//审核
                $list['data'][$k]['role']['measure_up'] = $role_measure_up;//是否可以检查（串联用户审核通过后可以检查，并联用户缴费后才能检查）
            }
        }
        $list['role'] = [
            'add'=>$pigcmsId ? 1 : $houseVillageService->checkPermissionMenu(1113005,$param['adminUser'],$param['login_role'],$param['dismissPermissionRole'])
        ];
        if(!$pigcmsId){
            $list['role']['protocol'] = $houseVillageService->checkPermissionMenu(1113006,$param['adminUser'],$param['login_role'],$param['dismissPermissionRole']);
            $list['role']['set'] = $houseVillageService->checkPermissionMenu(1113007,$param['adminUser'],$param['login_role'],$param['dismissPermissionRole']);
        }
        return $list;
    }

    /**
     * 新增申请停暖
     */
    public function addCloseHeating($param,$isverify=true)
    {
        $check = $this->check($param,$isverify);
        if($check['error']){
            throw_exception($check['msg']);
        }
        $sn = 'TNBH'.time().createRandomStr(2,false,true,false);
        $param['application_time'] = $param['application_time'] ? strtotime($param['application_time']) : '';
        
        $data = (new CloseHeatingList())->getOne([
            'room_id'=>$param['room_id'],
            'is_del'=>0
        ],'*');
        //已经申请成功的房间，只能等待下一年申请
        if($data && $data['audit_status'] == 0){
            throw_exception('该房间已经申请停暖，请等待结果');
        }
        if($data && $data['audit_status'] > 0 && $data['application_time'] > strtotime(date('Y').'-1-1')){
            throw_exception('该房间今年已经申请停暖');
        }
        $getProtocolParam = [
            'village_id' => $param['village_id'], 'xtype' => 'notice'
        ];
        $detailProtocolInfo = $this->getHeatingProtocol($getProtocolParam);
        $currentYear = date('Y');
        $nextYear = $currentYear + 1;
        $stopDateRange = isset($detailProtocolInfo['stopDateRange']) && !empty($detailProtocolInfo['stopDateRange']) ? $detailProtocolInfo['stopDateRange'] : [$currentYear . '-10-15', $nextYear . '-03-15'];
        $startTime = isset($stopDateRange[0]) && $stopDateRange[0] ? strtotime($stopDateRange[0]) : strtotime($currentYear . '-10-15');
        $stopTime = isset($stopDateRange[1]) && $stopDateRange[1] ? strtotime($stopDateRange[1] . '23:59:59') : strtotime($nextYear . '-03-15 23:59:59');

        $startTimeNum = date('md', $startTime);
        $stopTimeNum = date('md', $stopTime);
        if ($stopTimeNum > $startTimeNum && $param['application_time'] < $startTime || $param['application_time'] > $stopTime) {
            throw_exception('申请时间不在停暖协议范围内');
        }
        if ($startTimeNum > $stopTimeNum) {
            $startTimeDate = $currentYear . '-' . date('m-d', $startTime);
            $startTimeDate = strtotime($startTimeDate);
            $stopTimeDate = $currentYear . '-' . date('m-d', $stopTime);
            $stopTimeDate = strtotime($stopTimeDate);
            if ($param['application_time'] < $startTimeDate && $param['application_time'] > $stopTimeDate) {
                throw_exception('申请时间不在停暖协议范围内');
            }
        }
        
        $audit_status = isset($param['audit_status']) && $param['audit_status'] ? $param['audit_status'] : 0;
        $user_type = isset($param['user_type']) && $param['user_type'] ? $param['user_type'] : 0;
        $audit_msg = isset($param['audit_msg']) && $param['audit_msg'] ? $param['audit_msg'] : '';
        $is_measure_up = isset($param['is_measure_up']) && $param['is_measure_up'] ? $param['is_measure_up'] : 0;
        $measure_up_msg = isset($param['measure_up_msg']) && $param['measure_up_msg'] ? $param['measure_up_msg'] : '';

        $setChargeStandardBindId = isset($param['setChargeStandardBindId']) && $param['setChargeStandardBindId'] ? $param['setChargeStandardBindId'] : '';
        $setProjectId = isset($param['setProjectId']) && $param['setProjectId'] ? $param['setProjectId'] : 0;
        $setRulesId = isset($param['setRulesId']) && $param['setRulesId'] ? $param['setRulesId'] : 0;
        $service_start_time = isset($param['service_start_time']) && $param['service_start_time'] ? $param['service_start_time'] : '';
        $service_end_time = isset($param['service_end_time']) && $param['service_end_time'] ? $param['service_end_time'] : '';
        if ($is_measure_up == 1 && !$setChargeStandardBindId) {
            throw_exception('请选择生成账单绑定的收费标准！');
        }
        
        $addData = [
            'sn' => $sn,
            'village_id' => $param['village_id'],
            'room_id' => $param['room_id'],
            'name' => $param['name'],
            'phone' => $param['phone'],
            'application_time' => $param['application_time'],
            'house_property_evidence_img' => $param['house_property_evidence_img'],
            'id_card_img' => $param['id_card_img'],
            'id_card_back_img' => $param['id_card_back_img'],
            'create_time' => time(),
            'update_time' => time()
        ];
        $audit_status && $addData['audit_status'] = $audit_status;
        $user_type && $addData['user_type'] = $user_type;
        $audit_msg && $addData['audit_msg'] = $audit_msg;
        $is_measure_up && $addData['is_measure_up'] = $is_measure_up;
        $measure_up_msg && $addData['measure_up_msg'] = $measure_up_msg;

        $setChargeStandardBindId && $addData['set_charge_standard_bind_id'] = $setChargeStandardBindId;
        $setProjectId && $addData['set_project_id'] = $setProjectId;
        $setRulesId && $addData['set_rules_id'] = $setRulesId;
        $service_start_time && $addData['set_start_time'] = $service_start_time;
        $service_end_time && $addData['set_end_time'] = $service_end_time;

        if ($setChargeStandardBindId > 0) {
           $payOrderResult = $this->handleHeatingParams($setChargeStandardBindId, $param);
            $payOrderIds = isset($payOrderResult['payOrderIds']) && $payOrderResult['payOrderIds'] ? $payOrderResult['payOrderIds'] : [];
            $pay_order_id = isset($payOrderResult['pay_order_id']) && $payOrderResult['pay_order_id'] ? $payOrderResult['pay_order_id'] : 0;
            if ($pay_order_id) {
                $addData['set_order_id'] = $pay_order_id;
            } elseif ($payOrderIds) {
                $addData['set_order_id'] = implode(',', $payOrderIds);
            }
        }
        $add = (new CloseHeatingList())->addOne($addData);
        if (! $add) {
            if (isset($payOrderIds) && !empty($payOrderIds)) {
                $service_house_new_cashier = new HouseNewCashierService();
                $whereOrder = [];
                $whereOrder[] = ['order_id', 'in', $payOrderIds];
                $saveData = [
                    'is_discard' => 2, 'discard_reason' => '申请停暖失败自动作废账单', 'update_time' => time()
                ];
                $service_house_new_cashier->saveOrder($whereOrder, $saveData);
            }
            if (isset($pay_order_id) && !empty($pay_order_id)) {
                $service_house_new_cashier = new HouseNewCashierService();
                $whereOrder = [];
                $whereOrder[] = ['order_id', '=', $pay_order_id];
                $saveData = [
                    'is_discard' => 2, 'discard_reason' => '申请停暖失败自动作废账单', 'update_time' => time()
                ];
                $service_house_new_cashier->saveOrder($whereOrder, $saveData);
            }
        }
        return $add;
    }
    
    public function handleHeatingParams($setChargeStandardBindId, $params)
    {
        $service_start_time_params = isset($params['service_start_time']) && $params['service_start_time'] ? $params['service_start_time'] : '';
        $service_end_time_params = isset($params['service_end_time']) && $params['service_end_time'] ? $params['service_end_time'] : '';
        
        $service_house_new_charge_rule = new HouseNewChargeRuleService();
        $service_house_new_cashier = new HouseNewCashierService();
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
        $service_house_new_charge = new HouseNewChargeService();
        $service_house_village = new HouseVillageService();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        
        $where = [];
        $where[] = ['id', '=', $setChargeStandardBindId];
        $charge_standard_bind_info = $service_house_new_charge_rule->getBindDetail($where, true);
        if ($charge_standard_bind_info && !is_array($charge_standard_bind_info)) {
            $charge_standard_bind_info = $charge_standard_bind_info->toArray();
        }
        if (empty($charge_standard_bind_info)) {
            throw_exception('所选择的房间对应绑定标准绑定信息不存在！');
        }
        $cycle = $charge_standard_bind_info['cycle'];
        $cycle = $cycle > 0 ? $cycle : 1;
        $x_cycle = $cycle;
        if ($charge_standard_bind_info['vacancy_id']) {
            $type = 1;
            $id = $charge_standard_bind_info['vacancy_id'];
        } else {
            throw_exception('当前不支持绑定车位收费，请更换收费标准！');
        }
        $is_allow = $service_house_new_charge_rule->checkChargeValid($charge_standard_bind_info['project_id'], $charge_standard_bind_info['rule_id'], $id, $type);
        if (!$is_allow) {
            throw_exception('当前收费标准未生效，请更换收费标准！');
        }
        $info = $service_house_new_charge_rule->getCallInfo(['r.id' => $charge_standard_bind_info['rule_id']], 'n.charge_type,r.*,p.type,p.name as order_name');
        if ($info && !is_array($info)) {
            $info = $info->toArray();
        }
        if(empty($info)) {
            throw_exception('所选择的房间对应绑定标准绑定收费标准不存在！');
        }
        if ($info['unit_gage_type'] == 3) {
            throw_exception('当前不支持绑定车位收费，请更换收费标准！');
        }
        if(in_array($info['fees_type'], [3, 4, 5])) {
            throw_exception('当前不支持绑定车位收费，请更换收费标准！');
        }
        if ($info['cyclicity_set'] > 0) {
            if ($charge_standard_bind_info['vacancy_id']) {
                $order_list = $service_house_new_cashier->getOrderLists(['o.is_discard' => 1, 'o.room_id' => $charge_standard_bind_info['vacancy_id'], 'o.project_id' => $charge_standard_bind_info['project_id'], 'o.position_id' => 0, 'o.rule_id' => $info['id'], 'o.is_refund' => 1], 'o.*');
            } else {
                $order_list = $service_house_new_cashier->getOrderLists(['o.is_discard' => 1, 'o.position_id' => $charge_standard_bind_info['position_id'], 'o.project_id' => $charge_standard_bind_info['project_id'], 'o.rule_id' => $info['id'], 'o.is_refund' => 1], 'o.*');
            }
            $order_count = 0;
            if ($order_list) {
                $order_list = $order_list->toArray();
                if (count($order_list) >= $info['cyclicity_set']) {
                    throw_exception('超过最大缴费周期数！');
                }
                foreach ($order_list as $item) {
                    if ($item['service_month_num'] == 0) {
                        $order_count += 1;
                    } else {
                        $order_count = $order_count + $item['service_month_num'] + $item['service_give_month_num'];
                    }
                }
                if ($order_count >= $info['cyclicity_set']) {
                    throw_exception('超过最大缴费周期数！');
                }
            }
            if ($cycle > 0) {
                $order_count += $cycle;
            }
            if ($order_count > $info['cyclicity_set']) {
                throw_exception('超过最大缴费周期数！');
            }
        }
        $housesize = 0;
        $postData = array();
        //使用房子的 未入住状态来判断
        $whereArrTmp = array();
        $whereArrTmp[] = array('pigcms_id', '=', $charge_standard_bind_info['vacancy_id']);

        $whereArrTmp[] = array('status', 'in', [1, 2, 3]);
        $whereArrTmp[] = array('is_del', '=', 0);
        $room_vacancy = $service_house_village_user_vacancy->getUserVacancyInfo($whereArrTmp);
        if ($room_vacancy && !is_array($room_vacancy)) {
            $room_vacancy = $room_vacancy->toArray();
        }
        if (!empty($room_vacancy)) {
            $housesize = isset($info['housesize']) ? $info['housesize'] : 0;
            $heating_size = isset($room_vacancy['heating_size']) ? $room_vacancy['heating_size'] : 0;
            $billable_size = isset($room_vacancy['billable_size']) ? $room_vacancy['billable_size'] : 0;
        }
        $whereArrTmp[]=array('user_status','=',2);
        $room_vacancy=$service_house_village_user_vacancy->getUserVacancyInfo($whereArrTmp);
        $not_house_rate = 100;
        if($room_vacancy && !$room_vacancy->isEmpty()){
            $room_vacancy = $room_vacancy->toArray();
            if(!empty($room_vacancy)){
                $not_house_rate = $info['not_house_rate'];
            }
        }
        $projectBindInfo = $service_house_new_charge_rule->getBindDetail(['project_id' => $info['charge_project_id'], 'rule_id' => $info['id'], 'vacancy_id' => $charge_standard_bind_info['vacancy_id']]);
        if (isset($projectBindInfo) && !empty($projectBindInfo)) {
            if ($projectBindInfo['custom_value']) {
                $custom_value = $projectBindInfo['custom_value'];
                $custom_number = $custom_value;
            } else {
                $custom_value = 1;
            }
        } else {
            $custom_value = 1;
        }
        if ($not_house_rate <= 0 || $not_house_rate > 100) {
            $not_house_rate = 100;
        }
        if ($info['unit_gage_type'] == 4) {//供暖面积
            $housesize = $heating_size ?? 0;
            $custom_value = $housesize;
            if ($housesize <= 0) {
                throw_exception('供暖面积为0，无法生成账单！');
            }
        } elseif ($info['unit_gage_type'] == 5) {//计费面积
            $housesize = $billable_size ?? 0;
            $custom_value = $housesize;
            if ($housesize <= 0) {
                throw_exception('计费面积为0，无法生成账单！');
            }
        }

        $postData['order_type'] = $info['charge_type'];
        $charge_type_arr = $service_house_new_charge->charge_type;
        $postData['order_name'] = isset($charge_type_arr[$info['charge_type']]) ? $charge_type_arr[$info['charge_type']] : $info['charge_type'];
        if (isset($info['order_name']) && !empty($info['order_name'])) {
            $postData['order_name'] = $info['order_name'];
        }
        $postData['village_id'] = $charge_standard_bind_info['village_id'];
        $village_info = $service_house_village->getHouseVillageInfo(['village_id' => $charge_standard_bind_info['village_id']], 'property_id');
        $postData['property_id'] = $village_info['property_id'];
        if ($info['fees_type'] == 1) {
            $postData['total_money'] = $info['charge_price'] * $not_house_rate / 100 * $info['rate'] * $cycle;
        } else {
            if ($info['charge_type'] == 'property' && $custom_value <= 1) {
                $custom_number = $custom_value = $housesize;
            }
            $postData['total_money'] = $info['charge_price'] * $not_house_rate / 100 * $custom_value * $info['rate'] * $cycle;
        }
        $rule_digit = -1;
        if (isset($info['rule_digit']) && $info['rule_digit'] > -1 && $info['rule_digit'] < 5) {
            $rule_digit = $info['rule_digit'];
        }
        $db_house_property_digit_service = new HousePropertyDigitService();
        $digit_info = $db_house_property_digit_service->get_one_digit(['property_id' => $village_info['property_id']]);
        if ($rule_digit > -1 && $rule_digit < 5) {
            if (!empty($digit_info)) {
                $digit_info['meter_digit'] = $rule_digit;
                $digit_info['other_digit'] = $rule_digit;
            } else {
                $digit_info = array('type' => 1);
                $digit_info['meter_digit'] = $rule_digit;
                $digit_info['other_digit'] = $rule_digit;
            }
        }
        if (!empty($digit_info)) {
            if ($postData['order_type'] == 'water' || $postData['order_type'] == 'electric' || $postData['order_type'] == 'gas') {
                $postData['total_money'] = formatNumber($postData['total_money'], $digit_info['meter_digit'], $digit_info['type']);
            } else {
                $postData['total_money'] = formatNumber($postData['total_money'], $digit_info['other_digit'], $digit_info['type']);
            }
        }
        $role_id = isset($params['role_id']) && $params['role_id'] ? $params['role_id'] : 0;
        $postData['total_money'] = formatNumber($postData['total_money'], 2, 1);
        $postData['modify_money'] = $postData['total_money'];
        $postData['is_paid'] = 2;
        $postData['role_id'] = $role_id;
        $postData['is_prepare'] = 2;
        //$postData['service_month_num'] = $info['cyclicity_set'];
        $postData['rule_id'] = $info['id'];
        $postData['project_id'] = $info['charge_project_id'];
        $postData['order_no'] = '';
        $postData['add_time'] = time();
        if ($not_house_rate > 0 && $not_house_rate < 100) {
            $postData['not_house_rate'] = $not_house_rate;
        }
        if (isset($custom_number)) {
            $postData['number'] = $custom_number;
        }
        // 是否为收银台订单  1是 2否 3-导入欠费账单 4-工单收费 5-申请停暖
        $postData['from'] = 5;
        if ($info['type'] == 2) {
            $postData['service_month_num'] = $cycle;
            $projectInfo = $service_house_new_cashier->getProjectInfo(['id' => $info['charge_project_id']], 'subject_id');
            $numberInfo = $service_house_new_cashier->getNumberInfo(['id' => $projectInfo['subject_id']], 'charge_type');
            if ($type == 1) {
                $last_order = $service_house_new_cashier->getOrderLog([['room_id', '=', $charge_standard_bind_info['vacancy_id']], ['order_type', '=', $numberInfo['charge_type']], ['project_id', '=', $info['charge_project_id']], ['position_id', '=', 0]], true, 'id DESC');
            } else {
                $last_order = $service_house_new_cashier->getOrderLog([['position_id', '=', $charge_standard_bind_info['position_id']], ['order_type', '=', $numberInfo['charge_type']], ['project_id', '=', $info['charge_project_id']]], true, 'id DESC');
            }

            //查询未缴账单
            $whereSubjectArr = array(['charge_type', '=', $numberInfo['charge_type']], ['property_id', '=', $village_info['property_id']], ['status', '<>', 4]);
            $subject_id_arr = $service_house_new_cashier->getNumberArr($whereSubjectArr, 'id');
            if (!empty($subject_id_arr)) {
                $whereProjectArr = array(['village_id', '=', $charge_standard_bind_info['village_id']], ['subject_id', 'in', $subject_id_arr], ['type', '=', 2], ['status', '<>', 4]);
                $getProjectArr = $service_house_new_cashier->getProjectArr($whereProjectArr, 'id');
            }
            if ($type == 1) {
                $pay_where = ['is_discard' => 1, 'is_paid' => 2, 'room_id' => $charge_standard_bind_info['vacancy_id'], 'order_type' => $numberInfo['charge_type']];
                if (isset($getProjectArr) && !empty($getProjectArr)) {
                    $pay_where['project_id'] = $getProjectArr;
                }
                $pay_order_info = $service_house_new_cashier->getInfo($pay_where, 'project_id,service_start_time,service_end_time', 'service_end_time DESC,order_id DESC');
            } else {
                $pay_where = ['is_discard' => 1, 'is_paid' => 2, 'position_id' => $charge_standard_bind_info['position_id'], 'order_type' => $numberInfo['charge_type']];
                if (isset($getProjectArr) && !empty($getProjectArr)) {
                    $pay_where['project_id'] = $getProjectArr;
                }
                $pay_order_info = $service_house_new_cashier->getInfo($pay_where, 'project_id,service_start_time,service_end_time', 'service_end_time DESC,order_id DESC');
            }
            if (cfg('new_pay_order') == 1 && !empty($pay_order_info) && $pay_order_info['service_end_time'] > 100) {
                if ($pay_order_info['project_id'] != $info['charge_project_id']) {
                    throw_exception('当前房间的该类别下有其他项目的待缴账单，无法生成账单！');
                }
                $postData['service_start_time'] = $pay_order_info['service_end_time'] + 1;
                $postData['service_start_time'] = strtotime(date('Y-m-d', $postData['service_start_time']));
                if ($info['bill_create_set'] == 1) {
                    $postData['service_end_time'] = $postData['service_start_time'] + 86400 * $cycle - 1;
                } elseif ($info['bill_create_set'] == 2) {
                    //todo 判断是不是按照自然月来生成订单
                    if (cfg('open_natural_month') == 1) {
                        $start_d = date('d', $postData['service_start_time']);
                        $tmp_service_end_time = strtotime("+$cycle month", $postData['service_start_time']);
                        $end_d = date('d', $tmp_service_end_time);
                        $end_date = '';
                        if ($start_d != $end_d) {
                            $end_date = date('Y-m', $tmp_service_end_time) . '-01 00:00:00';
                            $end_date_time = strtotime($end_date);
                            $tmp_service_end_time = $end_date_time;
                        }
                        $postData['service_end_time'] = $tmp_service_end_time - 1;
                    } else {
                        $cycle = $cycle * 30;
                        $postData['service_end_time'] = strtotime("+$cycle day", $postData['service_start_time']) - 1;
                    }
                } else {
                    $postData['service_end_time'] = strtotime("+$cycle year", $postData['service_start_time']) - 1;
                }
            } else {
                if ($numberInfo['charge_type'] == 'property') {
                    if ($type != 1) {
                        $where22 = [
                            ['position_id', '=', $charge_standard_bind_info['position_id']],
                            ['order_type', '=', $numberInfo['charge_type']],
                        ];
                    } else {
                        $where22 = [
                            ['room_id', '=', $charge_standard_bind_info['vacancy_id']],
                            ['order_type', '=', $numberInfo['charge_type']],
                            ['position_id', '=', 0]
                        ];
                    }
                    $new_order_log = $service_house_new_cashier->getOrderLog($where22, true, 'id DESC');
                    if (!empty($new_order_log)) {
                        $last_order = $new_order_log;
                    }
                }
                if ($service_start_time_params) {
                    $service_start_time_tmp = strtotime($service_start_time_params);
                    if ($info['charge_valid_type'] == 1) {
                        $postData['service_start_time'] = strtotime(date('Y-m-d', $service_start_time_tmp));
                    } elseif ($info['charge_valid_type'] == 2) {
                        $postData['service_start_time'] = strtotime(date('Y-m-d', $service_start_time_tmp));
                    } else {
                        $postData['service_start_time'] = strtotime(date('Y-m-d', $service_start_time_tmp));
                    }
                    if ($last_order && $last_order['service_end_time'] > 1) {
                        $updateLogArr = array('service_start_time' => $service_start_time_tmp, 'service_end_time' => $service_start_time_tmp);
                        $whereLogArr = array();
                        $whereLogArr[] = array('order_id', '>', 0);
                        $whereLogArr[] = array('project_id', '=', $last_order['project_id']);
                        $whereLogArr[] = array('order_type', '=', $last_order['order_type']);
                        $whereLogArr[] = array('room_id', '=', $last_order['room_id']);
                        $whereLogArr[] = array('village_id', '=', $last_order['village_id']);
                        $service_house_new_cashier->saveOrderLog($whereLogArr, $updateLogArr);
                    }
                } elseif ($last_order && $last_order['service_end_time'] > 100) {
                    $postData['service_start_time'] = $last_order['service_end_time'] + 1;
                    $postData['service_start_time'] = strtotime(date('Y-m-d', $postData['service_start_time']));
                } elseif ($charge_standard_bind_info['order_add_time']) {
                    $postData['service_start_time'] = strtotime(date('Y-m-d', $charge_standard_bind_info['order_add_time']));
                    if (!$postData['service_start_time']) {
                        $postData['service_start_time'] = strtotime(date('Y-m-d', $info['charge_valid_time']));
                    }
                } else {
                    $postData['service_start_time'] = strtotime(date('Y-m-d', $info['charge_valid_time']));
                }
                if ($info['bill_create_set'] == 1) {
                    $postData['service_end_time'] = $postData['service_start_time'] + 86400 * $cycle - 1;
                } elseif ($info['bill_create_set'] == 2) {
                    //todo 判断是不是按照自然月来生成订单
                    if (cfg('open_natural_month') == 1) {
                        $start_d = date('d', $postData['service_start_time']);
                        $tmp_service_end_time = strtotime("+$cycle month", $postData['service_start_time']);
                        $end_d = date('d', $tmp_service_end_time);
                        $end_date = '';
                        if ($start_d != $end_d) {
                            $end_date = date('Y-m', $tmp_service_end_time) . '-01 00:00:00';
                            $end_date_time = strtotime($end_date);
                            $tmp_service_end_time = $end_date_time;
                        }
                        $postData['service_end_time'] = $tmp_service_end_time - 1;
                        fdump_api(['postData' => $postData, 'tmp_service_end_time' => $tmp_service_end_time, $start_d, $end_d, 'end_date' => $end_date], '000naturalMonthCall', 1);
                    } else {
                        $cycle = $cycle * 30;
                        $postData['service_end_time'] = strtotime("+$cycle day", $postData['service_start_time']) - 1;
                    }
                } else {
                    $postData['service_end_time'] = strtotime("+$cycle year", $postData['service_start_time']) - 1;
                }
            }
            if (isset($contract_info['contract_time_start']) && $contract_info['contract_time_start'] > 1) {
                if ($postData['service_start_time'] < $contract_info['contract_time_start'] || $postData['service_start_time'] > $contract_info['contract_time_end']) {
                    throw_exception('账单开始时间不在合同范围内！');
                }
                if ($postData['service_end_time'] < $contract_info['contract_time_start'] || $postData['service_end_time'] > $contract_info['contract_time_end']) {
                    throw_exception('账单结束时间不在合同范围内！');
                }
            }
        }
        if ($info['type'] == 1 || $info['type'] == 3) {
            $postData['service_start_time'] = time();
            $postData['service_end_time'] = time();
        }
        $postData['unit_price'] = $info['charge_price'];
        if ($info['fees_type'] == 4 && empty($charge_standard_bind_info['position_id'])) {
            throw_exception('该收费标准需绑定车位才能生成账单！');
        }
        if ($charge_standard_bind_info['vacancy_id']) {
            $postData['room_id'] = $charge_standard_bind_info['vacancy_id'];
            $user_info = $service_house_village_user_bind->getBindInfo([['vacancy_id', '=', $charge_standard_bind_info['vacancy_id']], ['type', 'in', '0,3'], ['status', '=', 1]], 'uid,pigcms_id,name,phone');
            if ($user_info) {
                $postData['pigcms_id'] = $user_info['pigcms_id'] ? $user_info['pigcms_id'] : 0;
                $postData['name'] = $user_info['name'] ? $user_info['name'] : '';
                $postData['uid'] = $user_info['uid'] ? $user_info['uid'] : 0;
                $postData['phone'] = $user_info['phone'] ? $user_info['phone'] : '';
            }
        }
        $unify_flage_id = '130' . date('YmdHis') . rand(100000, 999999);
        $tmp_cycle = 0;
        if (isset($charge_standard_bind_info['per_one_order']) && $charge_standard_bind_info['per_one_order'] > 0 && $info['bill_create_set'] == 2 && $x_cycle > 1) {
            //月按月拆
            $tmp_cycle = $x_cycle;
        } else if (isset($charge_standard_bind_info['per_one_order']) && $charge_standard_bind_info['per_one_order'] > 0 && $info['bill_create_set'] == 3) {
            //年按月拆
            $tmp_cycle = $x_cycle * 12;
        }
        $payOrderIds = [];
        if ($tmp_cycle > 1) {
            $postData['unify_flage_id'] = $unify_flage_id;
            $service_start_time = $postData['service_start_time'];
            $service_end_time = $postData['service_end_time'];
            $total_money = $postData['total_money'];
            $modify_money = $postData['modify_money'];
            $service_month_num = $postData['service_month_num'];
            //拆订单 拆成按一个月一个月的
            $month_end_time_arr = array();
            $total_money_arr = array();
            $tmp_total_money = $total_money / $tmp_cycle;
            $tmp_total_money = round($tmp_total_money, 2);
            $tmp_total_money = $tmp_total_money * 1;
            for ($ii = 1; $ii <= $tmp_cycle; $ii++) {
                $per_total_money = $tmp_total_money;
                if ($ii == 1) {
                    //第一次
                    $tmp_service_start_time = $service_start_time;
                    $tmp_service_end_time = strtotime("+1 month", $tmp_service_start_time) - 1;
                } elseif ($ii == $tmp_cycle) {
                    //最后一次
                    $mckey = $ii - 1;
                    $tmp_service_start_time = $month_end_time_arr[$mckey] + 1;
                    $tmp_service_end_time = $service_end_time;
                    $x_total_money = array_sum($total_money_arr);
                    $x_total_money = round($x_total_money, 2);
                    $x_total_money = $x_total_money * 1;
                    $per_total_money = $total_money - $x_total_money;
                    $per_total_money = round($per_total_money, 2);
                } else {
                    $mckey = $ii - 1;
                    $tmp_service_start_time = $month_end_time_arr[$mckey] + 1;
                    $tmp_service_end_time = strtotime("+1 month", $tmp_service_start_time) - 1;
                }
                $postData['service_start_time'] = $tmp_service_start_time;
                $postData['service_end_time'] = $tmp_service_end_time;
                $month_end_time_arr[$ii] = $tmp_service_end_time;
                $postData['service_month_num'] = 1;
                $postData['total_money'] = $per_total_money;
                $postData['modify_money'] = $per_total_money;
                $total_money_arr[] = $per_total_money;
                if ($info['type'] == 1) {
                    $postData['service_start_time'] = $service_start_time_params ? strtotime($service_start_time_params) : $postData['service_start_time'];
                    $postData['service_end_time'] = $service_end_time_params ? strtotime($service_end_time_params) : $postData['service_end_time'];
                }
                $res = $service_house_new_cashier->addOrder($postData);
                fdump_api(['$setChargeStandardBindId' => $setChargeStandardBindId, '$params' => $params, '$postData' => $postData, '$res' => $res], 'addPayOrder');
                if ($res) {
                    if ($postData['order_type'] == 'property' && intval(cfg('cockpit')) && isset($postData['pigcms_id']) && !empty($postData['pigcms_id'])) {
                        $roleData = isset($params['roleData']) ? $params['roleData'] : [];
                        $roleUid = isset($roleData['uid']) ? $roleData['uid'] : 0;
                        $roleLoginRole = isset($roleData['login_role']) ? $roleData['login_role'] : 0;
                        $roleAdminUser = isset($roleData['adminUser']) ? $roleData['adminUser'] : [];
                        $system_remarks = (new StorageService())->getRoleData($roleUid, $roleLoginRole, $roleAdminUser);
                        $uid = (isset($user_info['uid']) && !empty($user_info['uid'])) ? $user_info['uid'] : 0;
                        if (!empty($uid)) {
                            $result = (new StorageService())->userBalanceChange($uid, 2, $postData['modify_money'], $system_remarks['remarks'], '收银台手动生成账单，物业费自动扣除余额', $res, $this->adminUser['village_id']);
                            if ($result['error']) {
                                throw_exception($result['msg']);
                            }
                        }
                    }
                }
                $payOrderIds[] = $res;
            }
            return ['msg' => '账单已生成', 'payOrderIds' => $payOrderIds, 'status' => 0, 'new_pay_order' => cfg('new_pay_order')];
        }
        if ($info['type'] == 1) {
            $postData['service_start_time'] = $service_start_time_params ? strtotime($service_start_time_params) : $postData['service_start_time'];
            $postData['service_end_time'] = $service_end_time_params ? strtotime($service_end_time_params) : $postData['service_end_time'];
        }
        $res = $service_house_new_cashier->addOrder($postData);
        fdump_api(['$setChargeStandardBindId' => $setChargeStandardBindId, '$params' => $params, '$postData' => $postData, '$res' => $res], 'addPayOrder');
        if ($res) {
            if ($postData['order_type'] == 'property' && intval(cfg('cockpit')) && isset($postData['pigcms_id']) && !empty($postData['pigcms_id'])) {
                $roleData = isset($params['roleData']) ? $params['roleData'] : [];
                $roleUid = isset($roleData['uid']) ? $roleData['uid'] : 0;
                $roleLoginRole = isset($roleData['login_role']) ? $roleData['login_role'] : 0;
                $roleAdminUser = isset($roleData['adminUser']) ? $roleData['adminUser'] : [];
                $system_remarks = (new StorageService())->getRoleData($roleUid, $roleLoginRole, $roleAdminUser);
                $uid = (isset($user_info['uid']) && !empty($user_info['uid'])) ? $user_info['uid'] : 0;
                if (!empty($uid)) {
                    $result = (new StorageService())->userBalanceChange($uid, 2, $postData['modify_money'], $system_remarks['remarks'], '收银台手动生成账单，物业费自动扣除余额', $res, $this->adminUser['village_id']);
                    if ($result['error']) {
                        throw_exception($result['msg']);
                    }
                }

            }
        }
        return ['msg' => '账单已生成', 'pay_order_id' => $res, 'status' => 0, 'new_pay_order' => cfg('new_pay_order')];
    }

    /**
     * 查看申请停暖详情
     */
    public function getCloseHeatingInfo($param)
    {
        $param['return_base'] = $param['return_base'] ?? 1;
        if(!$param['pigcms_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        $where = ['pigcms_id'=>$param['pigcms_id'],'is_del'=>0];
        $data = (new CloseHeatingList())->getOne($where,'*');
        if($data){
            $data['application_time'] = $data['application_time'] ? date('Y-m-d H:i:s',$data['application_time']) : '';
            $data['house_property_evidence_img'] = $data['house_property_evidence_img'] ? replace_file_domain($data['house_property_evidence_img']) : '';
            $data['id_card_img'] = $data['id_card_img'] ? replace_file_domain($data['id_card_img']) : '';
            $data['id_card_back_img'] = $data['id_card_back_img'] ? replace_file_domain($data['id_card_back_img']) : '';
            $data['measure_up_img'] = $data['measure_up_img'] ? explode(',',$data['measure_up_img']) : [];
            $data['measure_up_img'] = $data['measure_up_img'] ? array_map(function ($r) {
                $r = replace_file_domain($r);
                return $r;
            },$data['measure_up_img']) : [];
            //获取房间名称
            $db_house_village_user_vacancy = new HouseVillageUserVacancy();
            $house_village_service=new HouseVillageService();
            $room = $db_house_village_user_vacancy->getLists(['pigcms_id' => $data['room_id']]);
            $room1 = $room[0];
            $number =$house_village_service->word_replce_msg(array('single_name'=>$room1['single_name'],'floor_name'=>$room1['floor_name'],'layer'=>$room1['layer_name'],'room'=>$room1['room']),$param['village_id']);
            $data['room'] = $number;
            $data['audit_status_msg'] = (new CloseHeatingList())->getAuditStatusMsg($data['audit_status']);
            $data['is_measure_up_msg'] = (new CloseHeatingList())->getIsMeasureUpMsg($data['is_measure_up']);
            $data['user_type_msg'] = (new CloseHeatingList())->getUserTypeMsg($data['user_type']);
            $data['can_delete'] = $data['audit_status'] > 0 ? 0 : 1;//业主是否可以取消申请
            $service_house_village = new HouseVillageService();
            $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$param['village_id']],'village_name');
            $data['village_name'] = $village_info['village_name'] ?? '';
            $data['can_audits'] = $data['audit_status'] == 0 ? 1 : 0;//工作人员是否可以审核
            $data['can_measure_up'] = $data['audit_status'] > 0 && $data['is_measure_up'] == 0 ? 1 : 0;//工作人员是否可以检查
            $data['notice_str'] = '';
            if($param['return_base']){
                $imgAry = [];
                if($data['house_property_evidence_img']){
                    $imgAry[] = $data['house_property_evidence_img'];
                }
                if($data['id_card_img']){
                    $imgAry[] = $data['id_card_img'];
                }
                if($data['id_card_back_img']){
                    $imgAry[] = $data['id_card_back_img'];
                }
                //返回前端可以循环遍历的格式
                $data['base'] = [
                    [
                        'key'=>'编号',
                        'value'=>$data['sn'],
                        'type'=>'txt'
                    ],
                    [
                        'key'=>'小区名称',
                        'value'=>$data['village_name'],
                        'type'=>'txt'
                    ],
                    [
                        'key'=>'房间',
                        'value'=>$data['room'],
                        'type'=>'txt'
                    ],
                    [
                        'key'=>'申请人',
                        'value'=>$data['name'],
                        'type'=>'txt'
                    ],
                    [
                        'key'=>'联系方式',
                        'value'=>$data['phone'],
                        'type'=>'txt'
                    ],
                    [
                        'key'=>'申请时间',
                        'value'=>$data['application_time'],
                        'type'=>'txt'
                    ],
                    [
                        'key'=>'状态',
                        'value'=>$data['audit_status_msg'],
                        'type'=>'txt'
                    ],
                    [
                        'key'=>'证件',
                        'value'=>$imgAry,
                        'type'=>'img_ary'
                    ],
                ];
                if($data['audit_status'] > 0){
                    $data['base'][] = [
                        'key'=>'供暖方式',
                        'value'=>$data['user_type_msg'],
                        'type'=>'txt'
                    ];
                    $data['base'][] = [
                        'key'=>'审核内容',
                        'value'=>$data['audit_msg'],
                        'type'=>'long_txt'
                    ];
                }
                if($data['is_measure_up'] > 0){
                    $data['base'][] = [
                        'key'=>'是否符合供暖',
                        'value'=>$data['is_measure_up_msg'],
                        'type'=>'txt'
                    ];
                    $data['base'][] = [
                        'key'=>'检查内容',
                        'value'=>$data['measure_up_msg'],
                        'type'=>'long_txt'
                    ];
                    $data['base'][] = [
                        'key'=>'检查图片',
                        'value'=>$data['measure_up_img'],
                        'type'=>'img_ary'
                    ];
                }
            }
        }
        if($data && $data['user_type'] == 2 && $data['is_measure_up'] == 0){//并联用户，且未检查的记录才展示
            $setInfo = (new HeatingProtocol())->getOne(['village_id'=>$param['village_id']],'work_time');
            $work_time = $setInfo['work_time'] ?? '';
            $data['notice_str'] = $work_time ? '工作人员给并联用户拆除管路,需要在'.date('Y-m-d',$work_time).'前完成上报管路拆除信息' : '';
        }
        return $data;
    }

    /**
     * 审核停暖申请
     */
    public function auditCloseHeating($param)
    {
        if(!$param['pigcms_id']){
            throw_exception('操作内容不能为空！');
        }
        if(!in_array($param['audit_status'],[1,2])){
            throw_exception('操作状态异常！');
        }
        if($param['audit_status'] == 2 && !$param['audit_msg']){
            throw_exception('审核不通过请填写审核说明！');
        }
        if($param['audit_status'] == 1 && !in_array($param['user_type'],[1,2])){
            throw_exception('审核通过请选择供暖方式！');
        }
        $where = ['pigcms_id'=>$param['pigcms_id'],'is_del'=>0];
        $data = (new CloseHeatingList())->getOne($where,'audit_status,room_id,sn,village_id');
        if(!$data){
            throw_exception('记录不存在或者已被删除！');
        }
        if($data['audit_status'] > 0){
            throw_exception('记录已被处理，请刷新页面！');
        }

        $param['user_type'] = $param['audit_status'] == 2 ? 0 : $param['user_type'];

        Db::startTrans();
        try {
            $update = (new CloseHeatingList())->updateThis(['pigcms_id'=>$param['pigcms_id']],
                [
                    'audit_status'=>$param['audit_status'],
                    'audit_msg'=>$param['audit_msg'],
                    'user_type'=>$param['user_type'],
                    'update_time'=>time()
                    ]);
            if(!$update){
                throw_exception('审核失败！');
            }
            
            //查询是否是最新的一条
            $lastInfo = (new CloseHeatingList())->getOne([
                ['room_id','=',$data['room_id']],
                ['is_del','=',0],
                ['audit_status','>',0]
            ],'pigcms_id');
            if($lastInfo && $lastInfo['pigcms_id'] == $param['pigcms_id']){//是最后一条
                $roomInfo = (new CloseHeatingRoom())->getOne(['room_id'=>$data['room_id']],'pigcms_id');
                //修改房间通知状态
                $roomData = [
                    'sn' => $data['sn'],
                    'village_id' => $data['village_id'],
                    'room_id' => $data['room_id'],
                    'notice_status' => 0,
                    'update_time' => time()
                ];
                if($param['audit_status'] == 1 && $param['user_type'] == 2){//审核通过，并联用户,直接通知
                    $roomData['notice_status'] = 1;
                }
                if($roomInfo){
                    (new CloseHeatingRoom())->updateThis(['pigcms_id'=>$roomInfo['pigcms_id']],$roomData);
                }else{
                    $roomData['create_time'] = time();
                    (new CloseHeatingRoom())->addOne($roomData);
                }
            }
            
            //增加日志
            (new CloseHeatingAuditLog())->addOne([
                'application_id' => $param['pigcms_id'],
                'operate_type' => $param['operate_type'],
                'operate_uid' => $param['operate_uid'],
                'operate_status' => $param['audit_status'],
                'update_info' => json_encode($param),
                'create_time' => time()
            ]);
            Db::commit();
            if($param['user_type']==2 && $param['audit_status'] == 1){
                $this->sendNoticeMessage($data['village_id'],$data['room_id']);
            }
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 检查停暖申请
     */
    public function inspectCloseHeating($param)
    {
        if(!$param['pigcms_id']){
            throw_exception('操作内容不能为空！');
        }
        if(!in_array($param['is_measure_up'],[1,2])){
            throw_exception('请选择是否符合供暖！');
        }
        $where = ['pigcms_id'=>$param['pigcms_id'],'is_del'=>0];
        $data = (new CloseHeatingList())->getOne($where,'*');
        if(!$data){
            throw_exception('记录不存在或者已被删除！');
        }
        if($data['is_measure_up'] > 0){
            throw_exception('记录已检查，请刷新页面！');
        }
        $measure_up_img = implode(',',$param['measure_up_img']);

        $setChargeStandardBindId = isset($param['setChargeStandardBindId']) && $param['setChargeStandardBindId'] ? $param['setChargeStandardBindId'] : '';
        $setProjectId = isset($param['setProjectId']) && $param['setProjectId'] ? $param['setProjectId'] : 0;
        $setRulesId = isset($param['setRulesId']) && $param['setRulesId'] ? $param['setRulesId'] : 0;
        $service_start_time = isset($param['service_start_time']) && $param['service_start_time'] ? $param['service_start_time'] : '';
        $service_end_time = isset($param['service_end_time']) && $param['service_end_time'] ? $param['service_end_time'] : '';
        if ($param['is_measure_up'] == 1 && !$setChargeStandardBindId) {
            throw_exception('请选择生成账单绑定的收费标准！');
        }
        

        Db::startTrans();
        try {
            if ($setChargeStandardBindId > 0) {
                $payOrderResult = $this->handleHeatingParams($setChargeStandardBindId, $param);
                $payOrderIds = isset($payOrderResult['payOrderIds']) && $payOrderResult['payOrderIds'] ? $payOrderResult['payOrderIds'] : [];
                $pay_order_id = isset($payOrderResult['pay_order_id']) && $payOrderResult['pay_order_id'] ? $payOrderResult['pay_order_id'] : 0;
            }
            $updateData = [
                'is_measure_up'=>$param['is_measure_up'],
                'measure_up_msg'=>$param['measure_up_msg'],
                'measure_up_img'=>$measure_up_img,
                'update_time'=>time()
            ];
            $setChargeStandardBindId && $updateData['set_charge_standard_bind_id'] = $setChargeStandardBindId;
            $setProjectId && $updateData['set_project_id'] = $setProjectId;
            $setRulesId && $updateData['set_rules_id'] = $setRulesId;
            $service_start_time && $updateData['set_start_time'] = $service_start_time;
            $service_end_time && $updateData['set_end_time'] = $service_end_time;
            $update = (new CloseHeatingList())->updateThis(['pigcms_id'=>$param['pigcms_id']], $updateData);
            if(!$update){
                if (isset($payOrderIds) && !empty($payOrderIds)) {
                    $service_house_new_cashier = new HouseNewCashierService();
                    $whereOrder = [];
                    $whereOrder[] = ['order_id', 'in', $payOrderIds];
                    $saveData = [
                        'is_discard' => 2, 'discard_reason' => '申请停暖失败自动作废账单', 'update_time' => time()
                    ];
                    $service_house_new_cashier->saveOrder($whereOrder, $saveData);
                }
                if (isset($pay_order_id) && !empty($pay_order_id)) {
                    $service_house_new_cashier = new HouseNewCashierService();
                    $whereOrder = [];
                    $whereOrder[] = ['order_id', '=', $pay_order_id];
                    $saveData = [
                        'is_discard' => 2, 'discard_reason' => '申请停暖失败自动作废账单', 'update_time' => time()
                    ];
                    $service_house_new_cashier->saveOrder($whereOrder, $saveData);
                }
                throw_exception('检查失败！');
            }
            
            //查询是否是最新的一条
            $lastInfo = (new CloseHeatingList())->getOne([
                ['room_id','=',$data['room_id']],
                ['is_del','=',0],
                ['audit_status','>',0]
            ],'pigcms_id');
            if($lastInfo && $lastInfo['pigcms_id'] == $param['pigcms_id']){//是最后一条
                $roomInfo = (new CloseHeatingRoom())->getOne(['room_id'=>$data['room_id']],'pigcms_id');
                //修改房间通知状态
                $roomData = [
                    'sn' => $data['sn'],
                    'village_id' => $data['village_id'],
                    'room_id' => $data['room_id'],
                    'notice_status' => 0,
                    'update_time' => time()
                ];
                if($data['user_type'] == 2 || $param['is_measure_up'] == 1){
                    //并联用户直接通知，串联用户需要符合供暖才通知
                    $roomData['notice_status'] = 1;
                }
                if($roomInfo){
                    (new CloseHeatingRoom())->updateThis(['pigcms_id'=>$roomInfo['pigcms_id']],$roomData);
                }else{
                    $roomData['create_time'] = time();
                    (new CloseHeatingRoom())->addOne($roomData);
                }
            }

            //增加日志
            (new CloseHeatingAuditLog())->addOne([
                'application_id' => $param['pigcms_id'],
                'operate_type' => $param['operate_type'],
                'operate_uid' => $param['operate_uid'],
                'operate_status' => 4,
                'update_info' => json_encode($param),
                'create_time' => time()
            ]);
            Db::commit();
            if($param['is_measure_up']==1){
                //发催缴消息
                $this->sendNoticeMessage($data['village_id'],$data['room_id']);
            }
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }
    
    public function sendNoticeMessage($village_id=0,$room_id=0){
        //发催缴消息
        if($village_id<1 || $room_id<1){
            return false;
        }
        $db_house_new_pay_order = new HouseNewPayOrder();
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
        $service_house_village = new HouseVillageService();
        $db_house_new_charge = new HouseNewCharge();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_new_cashier = new HouseNewCashierService();
        $service_user = new UserService();

        $whereArr=array('village_id'=>$village_id);
        $heatingProtocol= (new HeatingProtocol())->getOne($whereArr,'*');
        $rule_ids=array();
        if($heatingProtocol && isset($heatingProtocol['rule_id']) && $heatingProtocol['rule_id']){
            $rule_ids=json_decode($heatingProtocol['rule_id'],true);
        }
        if(empty($rule_ids)){
            return false;
        }
        $where=array();
        $where[] = ['is_discard', '=', 1];
        $where[] = ['is_paid', '=', 2];
        $where[] = ['order_type', 'in', ['new_nuanqifei','heating']];
        $where[] = ['room_id', '=', $room_id];
        $where[] = ['village_id', '=', $village_id];
        $where[]=  ['check_status','<>',1];
        $where[] = ['rule_id', 'in', $rule_ids];
        $pay_order_info=$db_house_new_pay_order->get_one($where,'sum(o.total_money) as all_total_money,o.*');
        $no_pay_sum=0;
        if($pay_order_info && !$pay_order_info->isEmpty()){
            $pay_order_info=$pay_order_info->toArray();
            $no_pay_sum=$pay_order_info['all_total_money']>0 ? round($pay_order_info['all_total_money'],2):0;
        }
        if(!($no_pay_sum>0)){
            return false;
        }
        $vacancy_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id'=>$room_id],'single_id,floor_id,layer_id,village_id');
        $address = '';
        if($vacancy_info){
            $address = $service_house_village->getSingleFloorRoom($vacancy_info['single_id'],$vacancy_info['floor_id'],$vacancy_info['layer_id'],$room_id,$vacancy_info['village_id']);
        }
        $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$village_id],'property_id,property_name,village_name');
        $charge_info = $db_house_new_charge->get_one(['village_id' => $village_id],'call_type');
        $call_type=1;
        if($charge_info && !$charge_info->isEmpty() && $charge_info['call_type']>0){
            //1仅业主 2:业主和家属 3:业主、家属和租客
            $call_type=$charge_info['call_type'];
        }
        $village_info_extend = $service_house_village->getHouseVillageInfoExtend(['village_id'=>$village_id]);
        $urge_notice_type=0;
        if($village_info_extend && isset($village_info_extend['urge_notice_type'])){
            //1短信通知2微信模板通知3短信和微信模板通知
            $urge_notice_type=$village_info_extend['urge_notice_type'];
        }
        $condition=array();
        $condition[] = ['vacancy_id','=',$room_id];
        $condition[] = ['status','=',1];
        if($call_type == 1){
            $condition[] = ['type','in',[0,3]];
        }elseif($call_type == 2){
            $condition[] = ['type','in',[0,3,1]];
        }elseif($call_type == 3){
            $condition[] = ['type','in',[0,1,2,3]];
        }
        $bind_list = $service_house_village_user_bind->getList($condition,'uid,pigcms_id,village_id,name');
        if($bind_list && !$bind_list->isEmpty()){
            $bind_list=$bind_list->toArray();
            foreach ($bind_list as $v1){
                $href = get_base_url('pages/houseMeter/NewCollectMoney/newLiveExpenses?village_id=' . $v1['village_id'] . '&pigcms_id='.$v1['pigcms_id']);
                if($urge_notice_type!=1) {
                    $service_house_new_cashier->sendCashierMessage($v1['uid'], $href, $address, $village_info['village_name'], $no_pay_sum, $village_info['property_id'],'heating',$rule_ids,$v1['name'],$v1['village_id']);
                }
                $user_info = $service_user->getUserOne(['uid'=>$v1['uid']],'phone');
                if(isset($user_info['phone']) && $user_info['phone']){
                    $sms_data = array('type' => 'fee_notice');
                    $sms_data['uid'] = $v1['uid'];
                    $sms_data['village_id'] = $v1['village_id'];
                    $sms_data['mobile'] = $user_info['phone'];
                    $sms_data['sendto'] = 'user';
                    $sms_data['mer_id'] = 0;
                    $sms_data['store_id'] = 0;
                    $project_name_str='暖气费';
                    //新模板 =》尊敬的业主，您好！有您的物业待缴账单。截止到当前，您的{1}待缴总额为{2}，请进行缴费。
                    $sms_data['content'] = L_('[物业未缴账单]尊敬的业主，您好！截止到当前，您的（x1）待缴总额为[x2]，请进行缴费。', array('x1' => $project_name_str, 'x2' =>$no_pay_sum));
                    if($urge_notice_type!=2) {
                        $sms = (new SmsService())->sendSms($sms_data);
                    }

                }
            }
        }
    }
    
    /**
     * 账单提醒设置
     */
    public function setOrderNotice($param)
    {
        if(!in_array($param['is_notice'],[0,1])){
            throw_exception('账单催缴设置状态异常!');
        }
        if(!$param['work_time']){
            throw_exception('工作人员提醒中的日期不能为空!');
        }
        $param['work_time'] = strtotime($param['work_time']);
        $where = ['village_id'=>$param['village_id']];
        $type = isset($param['type']) && $param['type'] == 1 ? 1 : 0;
        $where['type'] = $type;
        $data = (new HeatingProtocol())->getOne($where,'*');
        if(!isset($param['rule_id']) && $data && $data['is_notice'] == $param['is_notice'] && $data['notice_date'] == $param['notice_date'] && $data['work_time'] == $param['work_time']){
            return true;
        }
        $editDAry = [
            'village_id'=>$param['village_id'],
            'is_notice'=>$param['is_notice'],
            'notice_date'=>$param['notice_date'],
            'work_time'=>$param['work_time'],
            'update_time'=>time(),
        ];
        if (isset($param['rule_id'])) {
            $editDAry['rule_id'] = $param['rule_id'];
        }
        if (isset($param['stop_heating_start'])) {
            $editDAry['stop_heating_start'] = $param['stop_heating_start'];
        }
        if (isset($param['stop_heating_end'])) {
            $editDAry['stop_heating_end'] = $param['stop_heating_end'];
        }
        if($data){
            $edit = (new HeatingProtocol())->updateThis($where,$editDAry);
        }else{
            $editDAry['create_time'] = time();
            $edit = (new HeatingProtocol())->addOne($editDAry);
        }
        if(!$edit){
            throw_exception('操作失败！');
        }
        return true;
    }
    public function setFlowChargSet($param){
        $heatingProtocolDb= new HeatingProtocol();
        $where = ['village_id'=>$param['village_id'],'type'=>1];
        $data = $heatingProtocolDb->getOne($where,'*');
        $editDAry = [
            'order_yh_per'=>$param['order_yh_per'],
            'unit_price_base'=>$param['unit_price_base'],
            'apply_start_day'=>$param['apply_start_day'],
            'apply_end_day'=>$param['apply_end_day'],
        ];
        if($data && isset($data['village_id'])){
            $heatingProtocolDb->updateThis($where,$editDAry);
        }else{
             $editDAry['village_id'] = $param['village_id'];
             $editDAry['type'] = 1;
             $editDAry['create_time'] = time();
             $heatingProtocolDb->addOne($editDAry);
        }
        return true;
    }
    /**
     * 查看停暖协议
     */
    public function getHeatingProtocol($param)
    {
        $where = ['village_id'=>$param['village_id']];
        $type = isset($param['type']) && $param['type'] == 1 ? 1 : 0;
        $where['type'] = $type;
        $data = (new HeatingProtocol())->getOne($where,'*');
        $workTime = $data['work_time'] ?? '';
        $workTime = $workTime ? date('Y-m-d',$workTime) : '';
        $rule_ids=array();
        if($data && isset($data['rule_id']) && $data['rule_id']){
            $rule_ids=json_decode($data['rule_id'],true);
        }
        $stop_heating_start = $data['stop_heating_start'] ?? '';
        $stop_heating_end = $data['stop_heating_end'] ?? '';
        $currentYear = date('Y');
        $nextYear = $currentYear + 1;
        if ($stop_heating_start && $stop_heating_end) {
            $start = str_replace(['月', '日'], '', $stop_heating_start);
            $end = str_replace(['月', '日'], '', $stop_heating_end);
            $stop_heating_start = str_replace('月', '-', $stop_heating_start);
            $stop_heating_start = str_replace('日', '', $stop_heating_start);
            $stop_heating_end = str_replace('月', '-', $stop_heating_end);
            $stop_heating_end = str_replace('日', '', $stop_heating_end);
            if ($start > $end) {
                $startTime = $currentYear . '-' . $stop_heating_start;
                $endTime = $nextYear . '-' . $stop_heating_end;
            } else {
                $startTime = $currentYear . '-' . $stop_heating_start;
                $endTime = $currentYear . '-' . $stop_heating_end;
            }
        } else {
            $startTime = $currentYear . '-10-15';
            $endTime = $nextYear . '-03-15';
        }
        
        $return = [
            'is_open' => $data['is_open'] ?? 0,
            'title' => $data['title'] ?? '',
            'content' => $data['content'] ?? '',
            'is_notice' => $data['is_notice'] ?? 0,
            'notice_date' => $data['notice_date'] ?? '',
            'work_time' => $workTime ?: date('Y-m-d') ,
            'rule_id' => $rule_ids,
            'stopDateRange' => [$startTime, $endTime],
        ];
        if(isset($param['xtype']) && $param['xtype']=='flowCharg'){
            $return=array();
            $return['order_yh_per']=$data['order_yh_per'] ?? 0;
            $return['unit_price_base']=$data['unit_price_base'] ?? 0;
            $return['apply_start_day']=$data['apply_start_day'] ?? '';
            $return['apply_end_day']=$data['apply_end_day'] ?? '';
        }
        return $return;
    }

    /**
     * 编辑停暖协议
     */
    public function editHeatingProtocol($param)
    {
        if(!in_array($param['is_open'],[0,1])){
            throw_exception('协议状态异常!');
        }
        if($param['is_open'] == 1 && !$param['title']){
            throw_exception('协议名称不能为空!');
        }
        if($param['is_open'] == 1 && !$param['content']){
            throw_exception('协议内容不能为空!');
        }
        $type = isset($param['type']) && $param['type'] == 1 ? 1 : 0;
        $where = ['village_id'=>$param['village_id'],'type'=>$type];
        $data = (new HeatingProtocol())->getOne($where,'is_open,title,content');
        if($data && $data['is_open'] == $param['is_open'] && $data['title'] == $param['title'] && $data['content'] == $param['content']){
            return true;
        }
        $editDAry = [
            'village_id' => $param['village_id'],
            'is_open' => $param['is_open'],
            'title' => $param['title'],
            'content' => $param['content'],
            'type' => $type,
            'update_time' => time()
        ];
        if($data){
            $edit = (new HeatingProtocol())->updateThis($where,$editDAry);
        }else{
            $editDAry['create_time'] = time();
            $edit = (new HeatingProtocol())->addOne($editDAry);
        }
        if(!$edit){
            throw_exception('操作失败！');
        }
        return true;
    }

    /**
     * 删除停暖申请
     */
    public function delCloseHeating($param)
    {
        $param['packapp'] = $param['packapp'] ?? 0;//1-业主端
        if(!$param['pigcms_id']){
            throw_exception('操作内容不能为空！');
        }
        //未通过，或者已通过必须已经检查完成才可以删除
        $where = ['pigcms_id'=>$param['pigcms_id'],'is_del'=>0];
        $data = (new CloseHeatingList())->getOne($where,'audit_status,is_measure_up,room_id,sn,village_id');
        if(!$data){
            throw_exception('记录不存在或者已被删除！');
        }
        if($data['audit_status'] == 0 && $param['packapp'] != 1){
            throw_exception('记录未审核，无法删除！');
        }
        if($data['audit_status'] > 0 && $param['packapp'] == 1){
            throw_exception('记录已审核，无法取消！');
        }
        if($data['audit_status'] == 1 && $data['is_measure_up'] == 0){
            throw_exception('记录未检查，无法删除！');
        }

        Db::startTrans();
        try {
            $update = (new CloseHeatingList())->updateThis(['pigcms_id'=>$param['pigcms_id']],
                [
                    'is_del'=>1,
                    'update_time'=>time()
                ]);
            if(!$update){
                throw_exception('删除失败！');
            }

            //查询删除后最新的一条
            $lastInfo = (new CloseHeatingList())->getOne([
                ['room_id','=',$data['room_id']],
                ['is_del','=',0],
                ['audit_status','>',0]
            ],'audit_status,is_measure_up,user_type');
            //修改房间通知状态
            $roomData = [
                'sn' => $data['sn'],
                'village_id' => $data['village_id'],
                'room_id' => $data['room_id'],
                'notice_status' => 0,
                'update_time' => time()
            ];
            $roomInfo = (new CloseHeatingRoom())->getOne(['room_id'=>$data['room_id']],'pigcms_id');
            if($lastInfo && $lastInfo['audit_status'] == 1 && ($lastInfo['user_type'] == 2 || $lastInfo['is_measure_up'] == 1)){
                //通知：删除后还有已审核的记录，已审核的并联用户 或者 已审核的串联用户已检查符合供暖
                $roomData['notice_status'] = 1;
            }
            if($roomInfo){
                (new CloseHeatingRoom())->updateThis(['pigcms_id'=>$roomInfo['pigcms_id']],$roomData);
            }else{
                $roomData['create_time'] = time();
                (new CloseHeatingRoom())->addOne($roomData);
            }

            //增加日志
            (new CloseHeatingAuditLog())->addOne([
                'application_id' => $param['pigcms_id'],
                'operate_type' => $param['operate_type'],
                'operate_uid' => $param['operate_uid'],
                'operate_status' => 5,
                'update_info' => json_encode($param),
                'create_time' => time()
            ]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 申请流量计费列表
     */
    public function flowChargingList($param)
    {
        $param['keyword'] = $param['keyword'] ?? '';
        $param['name'] = $param['name'] ?? '';
        $param['phone'] = $param['phone'] ?? '';
        $param['audit_status'] = $param['audit_status'] ?? -1;
        $pigcmsId = $param['pigcms_id'] ?? 0;
        $where = [
            ['is_del','=',0],
            ['village_id','=',$param['village_id']],
        ];

        if($param['keyword']){
            $where[] = ['name|phone','like','%'.htmlentities($param['keyword']).'%'];
        }
        
        if($pigcmsId){
            $userBindInfo = (new HouseVillageUserBindService())->getBindInfo(['pigcms_id'=>$pigcmsId],'vacancy_id');
            $room_id = $userBindInfo['vacancy_id'] ?? 0;
            $where[] = ['room_id','=',$room_id];
        }
        if($param['name']){
            $where[] = ['name','like','%'.htmlentities($param['name']).'%'];
        }
        if($param['phone']){
            $where[] = ['phone','=',$param['phone']];
        }
        if($param['audit_status'] > -1){
            $where[] = ['audit_status','=',$param['audit_status']];
        }
        $field = 'pigcms_id,sn,name,phone,application_time,room_id,audit_status';
        $order = 'pigcms_id desc';
        $list = (new FlowChargingList())->getList($where,$field,$order,$param['limit']);
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $house_village_service=new HouseVillageService();
        $houseVillageService=new HouseVillageService();
        foreach ($list['data'] as $k=>$v){
            $list['data'][$k]['application_time'] = $v['application_time'] ? date('Y-m-d H:i:s',$v['application_time']) : '';
            $list['data'][$k]['audit_status_msg'] = (new FlowChargingList())->getAuditStatusMsg($v['audit_status']);
            //获取房间名称
            $room = $db_house_village_user_vacancy->getLists(['pigcms_id' => $v['room_id']]);
            $room1 = $room[0];
            $number =$house_village_service->word_replce_msg(array('single_name'=>$room1['single_name'],'floor_name'=>$room1['floor_name'],'layer'=>$room1['layer_name'],'room'=>$room1['room']),$param['village_id']);
            $list['data'][$k]['room'] = $number;
            //权限
            $select=$pigcmsId ? 1 : $houseVillageService->checkPermissionMenu(1113014,$param['adminUser'],$param['login_role'],$param['dismissPermissionRole']);
            $role_audit=$pigcmsId ? 1 : $houseVillageService->checkPermissionMenu(1113015,$param['adminUser'],$param['login_role'],$param['dismissPermissionRole']);
            $role_audit = $v['audit_status'] == 0 ? $role_audit : 0;
            if($pigcmsId){
                $role_delete = $v['audit_status'] > 0 ? 0 : 1;
            }else{
                $role_delete=$pigcmsId ? 1 : $houseVillageService->checkPermissionMenu(1113016,$param['adminUser'],$param['login_role'],$param['dismissPermissionRole']);
                $role_delete = $v['audit_status'] > 0 ? $role_delete : 0;
            }
            $list['data'][$k]['role'] = [
                'delete' => $role_delete,//删除(审核未通过、已通过)
            ];
            if(!$pigcmsId){
                $list['data'][$k]['role']['select'] = $select;//查看
                $list['data'][$k]['role']['audit'] = $role_audit;//审核
            }
        }
        $list['role'] = [
            'add'=>$pigcmsId ? 1 : $houseVillageService->checkPermissionMenu(1113012,$param['adminUser'],$param['login_role'],$param['dismissPermissionRole'])
        ];
        if(!$pigcmsId){
            $list['role']['protocol'] = $houseVillageService->checkPermissionMenu(1113013,$param['adminUser'],$param['login_role'],$param['dismissPermissionRole']);
        }
        return $list;
    }

    /**
     * 新增申请流量计费
     */
    public function addFlowCharging($param,$isverify=true)
    {
        $param['application_time'] = $param['application_time'] ? strtotime($param['application_time']) : time();
        $param['application_time'] = $param['application_time'] >100 ? $param['application_time']:time();
        $check = $this->check($param,$isverify);
        if($check['error']){
            throw_exception($check['msg']);
        }
        $application_day=date('md',$param['application_time']);
        $application_day=intval($application_day);
        $where=array('village_id'=>$param['village_id'],'type'=>1);
        $heatingProtocol=new HeatingProtocol();
        $heatingSet = $heatingProtocol->getOne($where,'*');
        $apply_start_day=101; //1月1日
        $apply_end_day=1231; //12月31日
        if($heatingSet && isset($heatingSet['apply_start_day'])){
            $apply_start_day=$heatingSet['apply_start_day'] ? str_replace('-','',$heatingSet['apply_start_day']):0;
            $apply_start_day=intval($apply_start_day);
            $apply_start_day=$apply_start_day>0 ? $apply_start_day:101;

            $apply_end_day=$heatingSet['apply_end_day'] ? str_replace('-','',$heatingSet['apply_end_day']):0;
            $apply_end_day=intval($apply_end_day);
            $apply_end_day=$apply_end_day>0 ? $apply_end_day:1231;
        }
        if($apply_start_day<=$apply_end_day){
            if(!($application_day>=$apply_start_day && $application_day<=$apply_end_day)){
                throw_exception('申请时间不在允许范围内！');
            }
        }else{
            if(!(($application_day>=$apply_start_day && $application_day<=1231) || ($application_day>=101 && $application_day<=$apply_end_day) )){
                throw_exception('申请时间不在允许范围内！');
            }
        }
        $sn = 'JFBH'.time().createRandomStr(2,false,true,false);

        $flowChargingList=new FlowChargingList();
        $whereArr=array(['village_id','=',$param['village_id']],['room_id','=',$param['room_id'],['is_del','=',0]]);
        $year=date('Y',$param['application_time']);
        $tmptime1=$year.'-01-01 00:00:00';
        $tmptime1=strtotime($tmptime1);
        $tmptime2=$year.'-12-31 23:59:59';
        $tmptime2=strtotime($tmptime2);
        $whereArr[]=array('application_time','>=',$tmptime1);
        $whereArr[]=array('application_time','<=',$tmptime2);
        $flowChargingTmp=$flowChargingList->getOne($whereArr);
        if($flowChargingTmp && isset($flowChargingTmp['room_id'])){
            throw_exception($year.'年您已经申请过了！');
        }
        $addData = [
            'sn' => $sn,
            'village_id' => $param['village_id'],
            'room_id' => $param['room_id'],
            'name' => $param['name'],
            'phone' => $param['phone'],
            'application_time' => $param['application_time'],
            'house_property_evidence_img' => $param['house_property_evidence_img'],
            'id_card_img' => $param['id_card_img'],
            'id_card_back_img' => $param['id_card_back_img'],
            'create_time' => time(),
            'update_time' => time()
        ];
        if( isset($param['audit_status'])){
            $addData['audit_status']=$param['audit_status'];
        }
        if( isset($param['audit_msg'])){
            $addData['audit_msg']=$param['audit_msg'];
        }
        $add = $flowChargingList->addOne($addData);
        return $add;
    }

    /**
     * 查看申请流量计费详情
     */
    public function getFlowChargingInfo($param)
    {
        $param['return_base'] = $param['return_base'] ?? 1;
        if(!$param['pigcms_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        $where = ['pigcms_id'=>$param['pigcms_id'],'is_del'=>0];
        $data = (new FlowChargingList())->getOne($where,'*');
        if($data){
            $data['application_time'] = $data['application_time'] ? date('Y-m-d H:i:s',$data['application_time']) : '';
            $data['house_property_evidence_img'] = $data['house_property_evidence_img'] ? replace_file_domain($data['house_property_evidence_img']) : '';
            $data['id_card_img'] = $data['id_card_img'] ? replace_file_domain($data['id_card_img']) : '';
            $data['id_card_back_img'] = $data['id_card_back_img'] ? replace_file_domain($data['id_card_back_img']) : '';
            //获取房间名称
            $db_house_village_user_vacancy = new HouseVillageUserVacancy();
            $house_village_service=new HouseVillageService();
            $room = $db_house_village_user_vacancy->getLists(['pigcms_id' => $data['room_id']]);
            $room1 = $room[0];
            $number =$house_village_service->word_replce_msg(array('single_name'=>$room1['single_name'],'floor_name'=>$room1['floor_name'],'layer'=>$room1['layer_name'],'room'=>$room1['room']),$param['village_id']);
            $data['room'] = $number;
            $data['audit_status_msg'] = (new CloseHeatingList())->getAuditStatusMsg($data['audit_status']);
            $data['can_delete'] = $data['audit_status'] > 0 ? 0 : 1;//业主是否可以取消申请
            $service_house_village = new HouseVillageService();
            $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$param['village_id']],'village_name');
            $data['village_name'] = $village_info['village_name'] ?? '';
            $data['can_audits'] = $data['audit_status'] == 0 ? 1 : 0;//工作人员是否可以审核
            if($param['return_base']){
                $imgAry = [];
                if($data['house_property_evidence_img']){
                    $imgAry[] = $data['house_property_evidence_img'];
                }
                if($data['id_card_img']){
                    $imgAry[] = $data['id_card_img'];
                }
                if($data['id_card_back_img']){
                    $imgAry[] = $data['id_card_back_img'];
                }
                //返回前端可以循环遍历的格式
                $data['base'] = [
                    [
                        'key'=>'编号',
                        'value'=>$data['sn'],
                        'type'=>'txt'
                    ],
                    [
                        'key'=>'小区名称',
                        'value'=>$data['village_name'],
                        'type'=>'txt'
                    ],
                    [
                        'key'=>'房间',
                        'value'=>$data['room'],
                        'type'=>'txt'
                    ],
                    [
                        'key'=>'申请人',
                        'value'=>$data['name'],
                        'type'=>'txt'
                    ],
                    [
                        'key'=>'联系方式',
                        'value'=>$data['phone'],
                        'type'=>'txt'
                    ],
                    [
                        'key'=>'申请时间',
                        'value'=>$data['application_time'],
                        'type'=>'txt'
                    ],
                    [
                        'key'=>'状态',
                        'value'=>$data['audit_status_msg'],
                        'type'=>'txt'
                    ],
                    [
                        'key'=>'证件',
                        'value'=>$imgAry,
                        'type'=>'img_ary'
                    ],
                ];
                if($data['audit_status'] > 0){
                    $data['base'][] = [
                        'key'=>'审核内容',
                        'value'=>$data['audit_msg'],
                        'type'=>'long_txt'
                    ];
                }
            }
        }else{
            throw_exception('未查询到申请信息');
        }
        return $data;
    }

    /**
     * 审核流量计费申请
     */
    public function auditFlowCharging($param)
    {
        if(!$param['pigcms_id']){
            throw_exception('操作内容不能为空！');
        }
        if(!in_array($param['audit_status'],[1,2])){
            throw_exception('操作状态异常！');
        }
        if($param['audit_status'] == 2 && !$param['audit_msg']){
            throw_exception('审核不通过请填写审核说明！');
        }
        $where = ['pigcms_id'=>$param['pigcms_id'],'is_del'=>0];
        $data = (new FlowChargingList())->getOne($where,'audit_status');
        if(!$data){
            throw_exception('记录不存在或者已被删除！');
        }
        if($data['audit_status'] > 0){
            throw_exception('记录已被处理，请刷新页面！');
        }

        Db::startTrans();
        try {
            $update = (new FlowChargingList())->updateThis(['pigcms_id'=>$param['pigcms_id']],
                [
                    'audit_status'=>$param['audit_status'],
                    'audit_msg'=>$param['audit_msg'],
                    'update_time'=>time()
                ]);
            if(!$update){
                throw_exception('审核失败！');
            }

            //增加日志
            (new FlowChargingAuditLog())->addOne([
                'application_id' => $param['pigcms_id'],
                'operate_type' => $param['operate_type'],
                'operate_uid' => $param['operate_uid'],
                'operate_status' => $param['audit_status'],
                'update_info' => json_encode($param),
                'create_time' => time()
            ]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 删除流量计费申请
     */
    public function delFlowCharging($param)
    {
        $param['packapp'] = $param['packapp'] ?? 0;//1-业主端
        if(!$param['pigcms_id']){
            throw_exception('操作内容不能为空！');
        }
        //未通过，或者已通过才可以删除
        $where = ['pigcms_id'=>$param['pigcms_id'],'is_del'=>0];
        $data = (new FlowChargingList())->getOne($where,'audit_status');
        if(!$data){
            throw_exception('记录不存在或者已被删除！');
        }
        if($data['audit_status'] == 0 && $param['packapp'] != 1){
            throw_exception('记录未审核，无法删除！');
        }
        if($data['audit_status'] > 0 && $param['packapp'] == 1){
            throw_exception('记录已审核，无法取消！');
        }

        Db::startTrans();
        try {
            $update = (new FlowChargingList())->updateThis(['pigcms_id'=>$param['pigcms_id']],
                [
                    'is_del'=>1,
                    'update_time'=>time()
                ]);
            if(!$update){
                throw_exception('删除失败！');
            }

            //增加日志
            (new FlowChargingAuditLog())->addOne([
                'application_id' => $param['pigcms_id'],
                'operate_type' => $param['operate_type'],
                'operate_uid' => $param['operate_uid'],
                'operate_status' => 5,
                'update_info' => json_encode($param),
                'create_time' => time()
            ]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }
    
    /**
     * 检查参数
     */
    public function check($param,$isverify=true)
    {
        $return = [
            'error'=>0
        ];
        if(!$param['room_id']){
            $return['error'] = 1;
            $return['msg'] = '请选择房间!';
            return $return;
        }
        if(!$param['name']){
            $return['error'] = 1;
            $return['msg'] = '申请人必填!';
            return $return;
        }
        if(!$param['phone']){
            $return['error'] = 1;
            $return['msg'] = '联系方式必填!';
            return $return;
        }
        if(!$param['house_property_evidence_img'] && $isverify){
            $return['error'] = 1;
            $return['msg'] = '请上传房产证照片!';
            return $return;
        }
        if(!$param['id_card_img'] && $isverify){
            $return['error'] = 1;
            $return['msg'] = '请上传身份证正面!';
            return $return;
        }
        if(!$param['id_card_back_img'] && $isverify){
            $return['error'] = 1;
            $return['msg'] = '请上传身份证反面!';
        }
        return $return;
    }
    
    /**
     * 查询房间是否是流量计费
     */
    public function getFlowCharging($param)
    {
        $is_flow_charging = 0;
        $village_id=isset($param['village_id']) ? $param['village_id']:0;
        if($param['room_id']){
            $whereArr=[
                ['room_id' ,'=', $param['room_id']],
                ['is_del' ,'=', 0],
                ['audit_status' ,'>', 0],
            ];
            if($village_id>0){
                $whereArr[]=['village_id' ,'=', $village_id];
            }
            $FlowCharging = (new FlowChargingList())->getOne($whereArr,'audit_status');
            $is_flow_charging = isset($FlowCharging['audit_status']) && $FlowCharging['audit_status'] == 1 ? 1 : 0;
        }
        $flow_charg_set=array('order_yh_per'=>0,'unit_price_base'=>0,'apply_start_day'=>'','apply_end_day'=>'');
        if($village_id>0){
            $where = ['village_id'=>$param['village_id'],'type'=>1];
            $dataObj = (new HeatingProtocol())->getOne($where,'*');
            if($dataObj && isset($dataObj['order_yh_per'])){
                $flow_charg_set['order_yh_per']=$dataObj['order_yh_per'] ?? 0;
                $flow_charg_set['unit_price_base']=$dataObj['unit_price_base'] ?? 0;
                $flow_charg_set['apply_start_day']=$dataObj['apply_start_day'] ?? '';
                $flow_charg_set['apply_end_day']=$dataObj['apply_end_day'] ?? '';
            }
            
        }
        return ['is_flow_charging'=>$is_flow_charging,'flow_charg_set'=>$flow_charg_set];
    }
    public function getRoomBindProjectRule($room_id=0,$village_id=0,$charge_type='new_nuanqifei'){
        $houseNewChargeStandardBind=new HouseNewChargeStandardBind();
        $whereArr=array(['b.village_id','=',$village_id],['b.vacancy_id','=',$room_id]);
        $whereArr[]=array('b.is_del','=',1);
        $whereArr[]=array('b.project_id','>',0);
        $whereArr[]=array('b.rule_id','>',0);
        $whereArr[]=array('r.status','=',1);
        $whereArr[]=array('r.village_id','=',$village_id);
        $whereArr[]=array('p.status','=',1);
        $whereArr[]=array('n.charge_type','=',$charge_type);
        $field='b.rule_id,b.project_id,r.*,p.name as project_name,n.charge_type,n.charge_number_name';
        $bindRuleObj=$houseNewChargeStandardBind->getList($whereArr,$field,0,100);
        $bindRule=array();
        if($bindRuleObj && !$bindRuleObj->isEmpty()){
            $bindRule=$bindRuleObj->toArray();
        }
        return $bindRule;
    }
    /**
     * 推送通知给新版暖气费未缴费超过n天的停暖用户
     */
    public function getCloseHeatingOrderLists()
    {
        $jz_wsxdsj_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        if(!$jz_wsxdsj_customized_open){
            return true;
        }
        //并联用户直接推，串联用户需要检查符合
        $where = [
            ['o.order_type','=','new_nuanqifei'],
            ['o.is_paid','=',2],
            ['o.is_discard','=',1],
            ['p.type','=',0],
            ['p.is_notice','=',1],
        ];
        $whereRaw = ' (s.close_heating_sn<>r.sn OR s.close_heating_sn is null) AND o.add_time+p.notice_date*86400<'.(time()+1);
        $field = 'o.order_id,o.village_id,o.room_id,o.total_money,o.total_money,o.order_type,o.rule_id,cp.name as project_name,r.sn';
        $data = (new HouseNewPayOrder())->getCloseHeartingOrderLists($where,$whereRaw,$field);
        $service_house_village = new HouseVillageService();
        $service_house_new_cashier = new HouseNewCashierService();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
        $service_user = new UserService();
        foreach ($data as $v){
            //查询订单是否需要发送短信
            $village_info_extend = $service_house_village->getHouseVillageInfoExtend(['village_id'=>$v['village_id']]);
            $urge_notice_type=0;
            if($village_info_extend && isset($village_info_extend['urge_notice_type'])){
                //1短信通知2微信模板通知3短信和微信模板通知
                $urge_notice_type=$village_info_extend['urge_notice_type'];
            }
            $condition = [];
            $condition[] = ['vacancy_id','=',$v['room_id']];
            $condition[] = ['status','=',1];
            $condition[] = ['type','in',[0,3]];
            $bind_list = $service_house_village_user_bind->getList($condition,'uid,pigcms_id,village_id,name');
            if($bind_list && !$bind_list->isEmpty()){
                $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$v['village_id']],'property_id,property_name,village_name');
                $vacancy_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id'=>$v['room_id']],'single_id,floor_id,layer_id,village_id');
                if($vacancy_info){
                    $address = $service_house_village->getSingleFloorRoom($vacancy_info['single_id'],$vacancy_info['floor_id'],$vacancy_info['layer_id'],$v['room_id'],$vacancy_info['village_id']);
                }else{
                    $address = '';
                }
                foreach ($bind_list as $v1){
                    if(in_array($urge_notice_type,[2,3])){
                        //微信模板通知
                        $href = get_base_url('pages/houseMeter/NewCollectMoney/newLiveExpenses?village_id=' . $v['village_id'] . '&pigcms_id='.$v1['pigcms_id']);
                        $service_house_new_cashier->sendCashierMessage($v1['uid'], $href, $address, $village_info['village_name'], $v['total_money'], $village_info['property_id'],$v['order_type'],$v['rule_id'],$v1['name']);
                    }
                    if(in_array($urge_notice_type,[1,3])){
                        //短信通知
                        $user_info = $service_user->getUserOne(['uid'=>$v1['uid']],'phone');
                        $sms_data = array('type' => 'fee_notice');
                        $sms_data['uid'] = $v1['uid'];
                        $sms_data['village_id'] = $v1['village_id'];
                        $sms_data['mobile'] = $user_info['phone'];
                        $sms_data['sendto'] = 'user';
                        $sms_data['mer_id'] = 0;
                        $sms_data['store_id'] = 0;
                        //新模板 =》尊敬的业主，您好！有您的物业待缴账单。截止到当前，您的{1}待缴总额为{2}，请进行缴费。
                        $sms_data['content'] = L_('[物业未缴账单]尊敬的业主，您好！截止到当前，您的（x1）待缴总额为[x2]，请进行缴费。', array('x1' => $v['project_name'], 'x2' => $v['total_money']));
                        $sms = (new SmsService())->sendSms($sms_data);
                    }
                }
                //增加日志
                (new CloseHeatingSend())->addOne([
                    'order_id'=>$v['order_id'],
                    'close_heating_sn'=>$v['sn'],
                    'create_time'=>time()
                ]);
            }
        }
        return true;
    }
    
    /**
     * 获取房间信息
     */
    public function getRoomInfo($param,$village_id=0)
    {
        $param['pigcms_id'] = $param['pigcms_id'] ?? 0;
        $room_id = $param['room_id'] ?? 0;
        
        if($param['pigcms_id']){
            $userBindInfo = (new HouseVillageUserBindService())->getBindInfo(['pigcms_id'=>$param['pigcms_id']],'name,phone,vacancy_id,village_id,uid');
            $data['name'] = $userBindInfo['name'] ?? '';
            $data['phone'] = $userBindInfo['phone'] ?? '';
            $room_id = $userBindInfo['vacancy_id'];
            if($userBindInfo && $userBindInfo['village_id']){
                $village_id=$userBindInfo['village_id'];
            }
        }elseif($room_id){
            $userBindInfo = (new HouseVillageUserBindService())->getBindInfo([['vacancy_id','=',$room_id],['type','in',[0,3]],['status','=',1],['name|phone|uid','<>','']],'name,phone,village_id,uid');
            $data['name'] = $userBindInfo['name'] ?? '';
            $data['phone'] = $userBindInfo['phone'] ?? '';
            if($userBindInfo && $userBindInfo['village_id']){
                $village_id=$userBindInfo['village_id'];
            }
        }
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $house_village_service=new HouseVillageService();
        //获取房间名称
        $room = $db_house_village_user_vacancy->getLists(['pigcms_id' => $room_id]);
        $room1 = $room[0] ?? [];
        $number = '';
        if($room1){
            $number =$house_village_service->word_replce_msg(array('single_name'=>$room1['single_name'],'floor_name'=>$room1['floor_name'],'layer'=>$room1['layer_name'],'room'=>$room1['room']),$room1['village_id']);
        }
        $data['room_id'] = $room_id;
        $data['room_name'] = $number;
        $data['room'] = isset($room1['room']) ? $room1['room'] : '';
        $data['current_time']=date('Y-m-d H:i');
        $data['need_payment']=0;
        $whereArr=array('village_id'=>$village_id);
        $heatingProtocol= (new HeatingProtocol())->getOne($whereArr,'*');
        if($room_id>0 && $heatingProtocol && isset($heatingProtocol['rule_id']) && $heatingProtocol['rule_id']){
            $db_house_new_pay_order = new HouseNewPayOrder();
            $rule_ids=json_decode($heatingProtocol['rule_id'],true);
            if($rule_ids){
                $where=array();
                $where[] = ['is_discard', '=', 1];
                $where[] = ['is_paid', '=', 2];
                $where[] = ['order_type', 'in', ['new_nuanqifei','heating']];
                $where[] = ['room_id', '=', $room_id];
                $where[] = ['village_id', '=', $village_id];
                if (is_array($rule_ids)) {
                    $where[] = ['rule_id', 'in', $rule_ids];
                } else {
                    $where[] = ['rule_id', '=', $rule_ids];
                }
                $where[]=  ['check_status','<>',1];
                $no_pay_sum1=$db_house_new_pay_order->getSum($where,'modify_money');
                $data['need_payment']=$no_pay_sum1>0 ? round($no_pay_sum1,2):0;
            }
        }
        return $data;
    }
}