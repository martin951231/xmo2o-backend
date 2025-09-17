<?php


namespace app\community\model\service;

use app\community\model\db\HouseNewChargeNumber;
use app\community\model\db\HouseNewChargeProject;
use app\community\model\db\HouseNewOrderLog;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseVillageBindPosition;
use app\community\model\service\HouseNewChargeRuleService;
use app\community\model\service\HousePropertyDigitService;
use app\community\model\service\HouseVillageParkingService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\HouseVillageUserVacancyService;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\service\HouseNewChargeService;
use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\service\StorageService;

use app\consts\newChargeConst;

class HouseNewPayOrderService
{
    // 手动生成账单
    public function manualCreateNewPayOrder($standard_bind = array(), $extra = array())
    {
        $retArr = array('status' => 0, 'msg' => '', 'data' => array());
        if (empty($standard_bind)) {
            $retArr['status'] = 1001;
            $retArr['msg'] = '绑定的数据不存在！';
            return $retArr;
        }
        $village_id = 0;
        if (isset($extra['village_id']) && ($extra['village_id'] > 0)) {
            $village_id = $extra['village_id'];
        }
        if (isset($standard_bind['village_id']) && ($standard_bind['village_id'] > 0)) {
            $village_id = $standard_bind['village_id'];
        }
        if ($village_id < 1) {
            $retArr['status'] = 1001;
            $retArr['msg'] = '小区ID错误！';
            return $retArr;
        }
        $role_id = 0;
        if (isset($extra['role_id'])) {
            $role_id = $extra['role_id'];
        }
        $service_start_time = 0;
        $service_house_new_charge_rule = new HouseNewChargeRuleService();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
        $service_house_new_charge = new HouseNewChargeService();
        $service_house_village = new HouseVillageService();
        $db_house_new_pay_order = new HouseNewPayOrder();
        $charge_standard_bind_info = $standard_bind;
        $contract_info = $service_house_new_charge_rule->getHouseVillageInfo(['village_id' => $village_id], 'contract_time_start,contract_time_end');
        $cycle = $charge_standard_bind_info['cycle'];
        if ($charge_standard_bind_info['vacancy_id'] > 0) {
            $type = 1;
            $id = $charge_standard_bind_info['vacancy_id'];
        } else {
            $type = 2;
            $id = $charge_standard_bind_info['position_id'];
        }
        $info = array();
        if (isset($extra['ruleInfo']) && !empty($extra['ruleInfo'])) {
            $info = $extra['ruleInfo'];
        } else {

            $infoObj = $service_house_new_charge_rule->getCallInfo(['r.id' => $charge_standard_bind_info['rule_id']], 'n.charge_type,r.*,p.type,p.name as order_name');
            if ($infoObj && is_object($infoObj) && !$infoObj->isEmpty()) {
                $info = $infoObj->toArray();
            } elseif ($infoObj && is_array($infoObj)) {
                $info = $infoObj;
            }
            if (empty($info)) {
                $retArr['status'] = 1001;
                $retArr['msg'] = '收费标准信息不存在！';
                return $retArr;
            }
        }
        if ($type == 2 && $info['fees_type'] == 2 && empty($info['unit_gage'])) {
            $retArr['status'] = 1001;
            $retArr['msg'] = '车场没有房屋面积，无法生成账单！';
            return $retArr;
        }
        if ($info['fees_type'] == newChargeConst::FEES_TYPE_NUMBER_OF_PARKING_SPACES) {
            $ruleHasParkNumInfo = $service_house_new_charge_rule->getRuleHasParkNumInfo($id, $charge_standard_bind_info['rule_id'], $type, $info);
            if (empty($ruleHasParkNumInfo) || !isset($ruleHasParkNumInfo['parkingNum']) || intval($ruleHasParkNumInfo['parkingNum']) <= 0) {
                $retArr['status'] = 1001;
                $retArr['msg'] = '计费模式为车位数量缺少车位数量，无法生成账单';
                return $retArr;
            }
            $parkingNum = $ruleHasParkNumInfo['parkingNum'];
        } else {
            $parkingNum = 1;
        }
        if ($info['cyclicity_set'] > 0) {
            if ($type == 1) {
                $order_list = $db_house_new_pay_order->getList(['o.is_discard' => 1, 'o.room_id' => $charge_standard_bind_info['vacancy_id'], 'o.project_id' => $charge_standard_bind_info['project_id'], 'o.position_id' => 0, 'o.rule_id' => $charge_standard_bind_info['rule_id'], 'o.is_refund' => 1], 'o.*');
            } else {
                $order_list = $db_house_new_pay_order->getList(['o.is_discard' => 1, 'o.position_id' => $charge_standard_bind_info['position_id'], 'o.project_id' => $charge_standard_bind_info['project_id'], 'o.rule_id' => $charge_standard_bind_info['rule_id'], 'o.is_refund' => 1], 'o.*');
            }
            if ($order_list) {
                $order_list = $order_list->toArray();
                if (count($order_list) >= $info['cyclicity_set']) {
                    $retArr['status'] = 1001;
                    $retArr['msg'] = '超过最大缴费周期数';
                    return $retArr;
                }
                $order_count = 0;
                foreach ($order_list as $item) {
                    if ($item['service_month_num'] == 0) {
                        $order_count += 1;
                    } else {
                        $order_count = $order_count + $item['service_month_num'] + $item['service_give_month_num'];
                    }
                }
                if ($order_count >= $info['cyclicity_set']) {
                    $retArr['status'] = 1001;
                    $retArr['msg'] = '超过最大缴费周期数！';
                    return $retArr;
                }
            }
        }
        $housesize = 0;
        $heating_size=0;
        $billable_size=0;
        if ($charge_standard_bind_info['vacancy_id']) {
            //使用房子的 未入住状态来判断
            $whereArrTmp = array();
            $whereArrTmp[] = array('pigcms_id', '=', $charge_standard_bind_info['vacancy_id']);

            $whereArrTmp[] = array('status', 'in', [1, 2, 3]);
            $whereArrTmp[] = array('is_del', '=', 0);
            $room_vacancy = $service_house_village_user_vacancy->getUserVacancyInfo($whereArrTmp,'*');
            if ($room_vacancy && !is_array($room_vacancy)) {
                $room_vacancy = $room_vacancy->toArray();
            }
            if (!empty($room_vacancy)) {
                $housesize = isset($info['housesize']) ? $info['housesize'] : 0;
                $heating_size = isset($room_vacancy['heating_size']) ? $room_vacancy['heating_size'] : 0;
                $billable_size = isset($room_vacancy['billable_size']) ? $room_vacancy['billable_size'] : 0;
            }
            $whereArrTmp[] = array('user_status', '=', 2);
            $room_vacancy = $service_house_village_user_vacancy->getUserVacancyInfo($whereArrTmp,'*');
            $not_house_rate = 100;
            if ($room_vacancy && !$room_vacancy->isEmpty()) {
                $room_vacancy = $room_vacancy->toArray();
                if (!empty($room_vacancy)) {
                    $not_house_rate = $info['not_house_rate'];
                }
            }
            $projectBindInfo = $service_house_new_charge_rule->getBindDetail(['project_id' => $info['charge_project_id'], 'rule_id' => $info['id'], 'vacancy_id' => $charge_standard_bind_info['vacancy_id']]);
        } else {
            $service_house_village_parking = new HouseVillageParkingService();
            $carInfo = $service_house_village_parking->getCar(['car_position_id' => $charge_standard_bind_info['position_id']]);
            if ($carInfo) {
                $carInfo = $carInfo->toArray();
            }
            if (empty($carInfo)) {
                $not_house_rate = $info['not_house_rate'];
            } else {
                $not_house_rate = 100;
            }
        }
        if ($charge_standard_bind_info['position_id']) {
            $projectBindInfo = $service_house_new_charge_rule->getBindDetail(['project_id' => $info['charge_project_id'], 'rule_id' => $info['id'], 'position_id' => $charge_standard_bind_info['position_id']]);
        }
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
        
        if($info['unit_gage_type'] == 4){//供暖面积
            $housesize = $heating_size;
            $custom_value=$housesize;
        }elseif($info['unit_gage_type'] == 5){//计费面积
            $housesize = $billable_size;
            $custom_value=$housesize;
        }
        
        $postData = array();
        if ($not_house_rate <= 0 || $not_house_rate > 100) {
            $not_house_rate = 100;
        }
        $postData['order_type'] = $info['charge_type'];
        $charge_type_arr = $service_house_new_charge->charge_type;
        $postData['order_name'] = isset($charge_type_arr[$info['charge_type']]) ? $charge_type_arr[$info['charge_type']] : $info['charge_type'];
        if(isset($info['order_name']) && !empty($info['order_name'])){
            $postData['order_name'] =$info['order_name'];
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
        if ($info['fees_type'] == newChargeConst::FEES_TYPE_NUMBER_OF_PARKING_SPACES && $parkingNum > 1) {
            $postData['total_money'] = $postData['total_money'] * intval($parkingNum);
            $postData['parking_num'] = intval($parkingNum);
            $postData['parking_lot'] = isset($ruleHasParkNumInfo) && isset($ruleHasParkNumInfo['parking_lot']) ? $ruleHasParkNumInfo['parking_lot'] : '';
        }
        if (!empty($digit_info)) {
            if ($postData['order_type'] == 'water' || $postData['order_type'] == 'electric' || $postData['order_type'] == 'gas') {
                $postData['total_money'] = formatNumber($postData['total_money'], $digit_info['meter_digit'], $digit_info['type']);
            } else {
                $postData['total_money'] = formatNumber($postData['total_money'], $digit_info['other_digit'], $digit_info['type']);
            }
        }
        $postData['total_money'] = formatNumber($postData['total_money'], 2, 1);
        $postData['modify_money'] = $postData['total_money'];
        $postData['is_paid'] = 2;
        $postData['role_id'] = $role_id;
        $postData['is_prepare'] = 2;
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
        $postData['from'] = 1;
        if ($info['type'] == 2) {
            $postData['service_month_num'] = $cycle;
            $projectInfo = $this->getProjectInfo(['id' => $info['charge_project_id']], 'subject_id');
            $numberInfo = $this->getNumberInfo(['id' => $projectInfo['subject_id']], 'charge_type');
            if ($type == 1) {
                $last_order = $this->getOrderLog([['room_id', '=', $charge_standard_bind_info['vacancy_id']], ['order_type', '=', $numberInfo['charge_type']], ['project_id', '=', $info['charge_project_id']], ['position_id', '=', 0]], true, 'id DESC');
            } else {
                $last_order = $this->getOrderLog([['position_id', '=', $charge_standard_bind_info['position_id']], ['order_type', '=', $numberInfo['charge_type']], ['project_id', '=', $info['charge_project_id']]], true, 'id DESC');
            }

            //查询未缴账单
            $subject_id_arr = $this->getNumberArr(['charge_type' => $numberInfo['charge_type'], 'status' => 1], 'id');
            if (!empty($subject_id_arr)) {
                $getProjectArr = $this->getProjectArr(['subject_id' => $subject_id_arr, 'type' => 2, 'status' => 1], 'id');
            }
            if ($type == 1) {
                $pay_where = ['is_discard' => 1, 'is_paid' => 2, 'room_id' => $charge_standard_bind_info['vacancy_id'], 'order_type' => $numberInfo['charge_type']];
                if (isset($getProjectArr) && !empty($getProjectArr)) {
                    $pay_where['project_id'] = $getProjectArr;
                }
                $pay_order_info = $db_house_new_pay_order->get_one($pay_where, 'project_id,service_start_time,service_end_time', 'service_end_time DESC,order_id DESC');
            } else {
                $pay_where = ['is_discard' => 1, 'is_paid' => 2, 'position_id' => $charge_standard_bind_info['position_id'], 'order_type' => $numberInfo['charge_type']];
                if (isset($getProjectArr) && !empty($getProjectArr)) {
                    $pay_where['project_id'] = $getProjectArr;
                }
                $pay_order_info = $db_house_new_pay_order->get_one($pay_where, 'project_id,service_start_time,service_end_time', 'service_end_time DESC,order_id DESC');
            }
            if (cfg('new_pay_order') == 1 && !empty($pay_order_info) && $pay_order_info['service_end_time'] > 100) {
                if ($pay_order_info['project_id'] != $info['charge_project_id']) {
                    $retArr['status'] = 1001;
                    $retArr['msg'] = '当前房间的该类别下有其他项目的待缴账单，无法生成账单';
                    return $retArr;
                }
                $postData['service_start_time'] = $pay_order_info['service_end_time'] + 1;
                $postData['service_start_time'] = strtotime(date('Y-m-d', $postData['service_start_time']));
                if ($info['bill_create_set'] == 1) {
                    $postData['service_end_time'] = $postData['service_start_time'] + 86400 * $cycle - 1;
                } elseif ($info['bill_create_set'] == 2) {
                    //todo 判断是不是按照自然月来生成订单
                    if (cfg('open_natural_month') == 1) {
                        $postData['service_end_time'] = strtotime("+$cycle month", $postData['service_start_time']) - 1;
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
                    $new_order_log = $this->getOrderLog($where22, true, 'id DESC');
                    if (!empty($new_order_log)) {
                        $last_order = $new_order_log;
                    }
                }
                if ($service_start_time) {
                    if ($info['charge_valid_type'] == 1) {
                        $postData['service_start_time'] = strtotime(date('Y-m-d', strtotime($service_start_time)));
                    } elseif ($info['charge_valid_type'] == 2) {
                        $postData['service_start_time'] = strtotime(date('Y-m-d', strtotime($service_start_time)));
                    } else {
                        $postData['service_start_time'] = strtotime(date('Y-m-d', strtotime($service_start_time)));
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
                        $postData['service_end_time'] = strtotime("+$cycle month", $postData['service_start_time']) - 1;
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
                    $retArr['status'] = 1001;
                    $retArr['msg'] = '账单开始时间不在合同范围内';
                    return $retArr;
                }
                if ($postData['service_end_time'] < $contract_info['contract_time_start'] || $postData['service_end_time'] > $contract_info['contract_time_end']) {
                    $retArr['status'] = 1001;
                    $retArr['msg'] = '账单结束时间不在合同范围内！';
                    return $retArr;
                }
            }
        }
        if ($info['type'] == 1 || $info['type'] == 3) {
            $postData['service_start_time'] = time();
            $postData['service_end_time'] = time();
            if(isset($extra['service_start_time']) && $extra['service_start_time']>100){
                $postData['service_start_time'] = $extra['service_start_time'];
                $postData['service_end_time'] = $extra['service_end_time'];
            }
        }
        $postData['unit_price'] = $info['charge_price'];
        if ($info['fees_type'] == 4 && empty($charge_standard_bind_info['position_id'])) {
            $retArr['status'] = 1001;
            $retArr['msg'] = '该收费标准需绑定车位才能生成账单！';
            return $retArr;
        }

        $postData['uid'] = 0;
        $postData['name'] = '';
        $postData['phone'] = '';
        if ($charge_standard_bind_info['vacancy_id']) {
            $postData['room_id'] = $charge_standard_bind_info['vacancy_id'];
            $user_info = $service_house_village_user_bind->getBindInfo([['vacancy_id', '=', $charge_standard_bind_info['vacancy_id']], ['type', 'in', '0,3'], ['status', '=', 1]], 'uid,pigcms_id,name,phone');
            if ($user_info && !$user_info->isEmpty()) {
                $postData['pigcms_id'] = $user_info['pigcms_id'];
                $postData['name'] = $user_info['name'];
                $postData['uid'] = $user_info['uid'];
                $postData['phone'] = $user_info['phone'];
            }
        }
        if ($charge_standard_bind_info['position_id']) {
            $service_house_village_parking = new HouseVillageParkingService();
            $postData['position_id'] = $charge_standard_bind_info['position_id'];
            $user_info = $this->getRoomUserBindByPosition($charge_standard_bind_info['position_id'], $charge_standard_bind_info['village_id']);
            if ($user_info && !$user_info->isEmpty()) {
                $postData['pigcms_id'] = $user_info['pigcms_id'];
                $postData['name'] = $user_info['name'];
                $postData['uid'] = $user_info['uid'];
                $postData['phone'] = $user_info['phone'];
                $postData['room_id'] = $user_info['vacancy_id'];
            }
            if ($info['fees_type'] == 4) {
                $car_info = $service_house_village_parking->getCar(['car_position_id' => $postData['position_id']], 'car_id,province,car_number,end_time');
                $postData['car_type'] = 'month_type';
                $postData['is_prepare'] = 2;
                $postData['car_number'] = !empty($car_info) ? $car_info['province'] . $car_info['car_number'] : '';
                $postData['car_id'] = !empty($car_info) ? $car_info['car_id'] : 0;
                $postData['service_month_num'] = $cycle ? $cycle : 1;
                $postData['service_start_time'] = $car_info['end_time'] > time() ? ($car_info['end_time'] + 1) : strtotime(date('Y-m-d 00:00:00'));
                if ($info['bill_create_set'] == 1) {
                    $postData['service_end_time'] = $postData['service_start_time'] + 86400 * $cycle - 1;
                } elseif ($info['bill_create_set'] == 2) {
                    //todo 判断是不是按照自然月来生成订单
                    if (cfg('open_natural_month') == 1) {
                        $postData['service_end_time'] = strtotime("+$cycle month", $postData['service_start_time']) - 1;
                    } else {
                        $cycle = $cycle * 30;
                        $postData['service_end_time'] = strtotime("+$cycle day", $postData['service_start_time']) - 1;
                    }
                } else {
                    $postData['service_end_time'] = strtotime("+$cycle year", $postData['service_start_time']) - 1;
                }
            }
        }
        $res = $this->addOrder($postData);
        if ($res) {
            $retArr['status'] = 0;
            $retArr['msg'] = '账单已生成！';
            $retArr['data'] = $res;
            return $retArr;
        }
        $retArr['status'] = 1001;
        $retArr['msg'] = '账单生成失败！';
        return $retArr;
    }

    /**
     * 添加订单
     * @param array $data
     * @return int|string
     */
    public function addOrder($data = [])
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $id = $db_house_new_pay_order->addOne($data);
        return $id;
    }

    /**
     * 收费项目信息
     * @param array $where
     * @param bool $field
     * @return array|\think\Model|null
     */
    public function getProjectInfo($where = [], $field = true)
    {
        $db_house_new_charge_project = new HouseNewChargeProject();
        $data = $db_house_new_charge_project->getOne($where, $field);
        return $data;
    }

    /**
     * 获取科目信息
     * @param array $where
     * @param bool $field
     * @return array|\think\Model|null
     */
    public function getNumberInfo($where = [], $field = true)
    {
        $db_house_new_charge_number = new HouseNewChargeNumber();
        $data = $db_house_new_charge_number->get_one($where, $field);
        return $data;
    }

    /**
     * 收取收费项目服务到期时间
     * @param array $where
     * @param bool $field
     * @param string $order
     */
    public function getOrderLog($where = [], $field = true, $order = 'id DESC')
    {
        $db_house_new_order_log = new HouseNewOrderLog();
        $data = $db_house_new_order_log->getOne($where, $field, $order);
        return $data;
    }

    /**
     * 收费项目信息
     * @param array $where
     * @param bool $field
     * @return array|\think\Model|null
     */
    public function getProjectArr($where = [], $field = true)
    {
        $db_house_new_charge_project = new HouseNewChargeProject();
        $data = $db_house_new_charge_project->getColumn($where, $field);
        return $data;
    }

    /**
     * 获取科目信息
     * @param array $where
     * @param bool $field
     * @return array|\think\Model|null
     */
    public function getNumberArr($where = [], $field = true)
    {
        $db_house_new_charge_number = new HouseNewChargeNumber();
        $data = $db_house_new_charge_number->getColumn($where, $field);
        return $data;
    }

    public function getRoomUserBindByPosition($position_id = 0, $village_id = 0)
    {
        $db_house_village_bind_position = new HouseVillageBindPosition();
        $roomUserBind = array();
        if ($position_id > 0 && $village_id > 0) {
            $bind_where = array();
            $bind_where[] = ['bp.position_id', '=', $position_id];
            $bind_where[] = ['bp.village_id', '=', $village_id];
            $bind_where[] = ['ub.village_id', '=', $village_id];
            $bind_where[] = ['ub.status', '=', 1];
            $bind_where[] = ['ub.vacancy_id', '>', 0];
            $field = 'ub.*';
            $user_bind_obj = $db_house_village_bind_position->getRoomUserBindByPosition($bind_where, $field);
            if ($user_bind_obj && !$user_bind_obj->isEmpty()) {
                $roomUserBind = $user_bind_obj->toArray();
            }
        }
        return $roomUserBind;
    }

    public function handle_room_water_balance_order()
    {
        /*** 客户不要这个定制功能了 先注释掉****/
        return true;
        $configCustomizationService = new ConfigCustomizationService();
        $houseVillageUserVacancy = new HouseVillageUserVacancy();
        $isAihorseCustomized = $configCustomizationService->getAihorseCustomizedJudge();
        if (false &&$isAihorseCustomized) {
            $houseVillageRoomWaterBalanceLog = new \app\community\model\db\HouseVillageRoomWaterBalanceLog();
            $whereArr = array(['room_id', '>', '0'], ['is_need_order', '=', 1], ['village_id', '>', 0]);
            $roomWaterBalanceLog = $houseVillageRoomWaterBalanceLog->getListData($whereArr, 'id,room_id,is_need_order,village_id', 'id DESC', 1, 10);
            if ($roomWaterBalanceLog && !$roomWaterBalanceLog->isEmpty()) {
                $roomWaterBalance = $roomWaterBalanceLog->toArray();
                $houseNewPayOrder = new HouseNewPayOrder();
                foreach ($roomWaterBalance as $rvv) {
                    $whereRoomArr = array(['village_id', '=', $rvv['village_id']], ['room_id', '=', $rvv['room_id']]);
                    $whereRoomArr[] = array('order_type', '=', 'water');
                    $whereRoomArr[] = array('total_money', '>', 0);
                    $whereRoomArr[] = array('is_discard', '=', '1');
                    $whereRoomArr[] = array('is_paid', '=', '2');
                    $fieldStr= 'order_id,summary_id,uid,pigcms_id,name,phone,pay_bind_id,pay_bind_name,pay_bind_phone,order_type,village_id,total_money,room_id,modify_money,property_id';
                    $room_pay_order_obj = $houseNewPayOrder->getOrderListByRoomId($whereRoomArr, '',$fieldStr, 'total_money asc', 1, 10);
                    $nowtime = time();
                    if ($room_pay_order_obj && !$room_pay_order_obj->isEmpty()) {
                        $room_pay_order = $room_pay_order_obj->toArray();
                        foreach ($room_pay_order as $ovv) {
                            $whereArr = array('pigcms_id' => $rvv['room_id'], 'village_id' => $rvv['village_id']);
                            $userVacancy = $houseVillageUserVacancy->getOne($whereArr, 'water_balance');
                            if (empty($userVacancy) || !isset($userVacancy['water_balance']) || $userVacancy['water_balance'] < $ovv['total_money']) {
                                //钱不够
                                $bak = '房间数据不存在！';
                                $water_balance = 0;
                                if (!empty($userVacancy) && isset($userVacancy['water_balance'])) {
                                    $bak = '房间预存水费余额不足！';
                                    $water_balance = $userVacancy['water_balance'];
                                }
                                $whereTmp = array('id' => $rvv['id']);
                                $extra_data = array('bak' => $bak, 'order' => $ovv, 'water_balance' => $water_balance, 'dtime' => date('Y-m-d H:i:s'));
                                $extra_data_str = json_encode($extra_data, JSON_UNESCAPED_UNICODE);
                                $saveTmpData = array('is_need_order' => 2, 'handle_order_time' => $nowtime, 'extra_data' => $extra_data_str);
                                $houseVillageRoomWaterBalanceLog->saveOneData($whereTmp, $saveTmpData);
                                continue;
                            }
                            $storageService = new StorageService();
                            $PayOrder = [
                                'is_paid' => 1,
                                'pay_time' => time(),
                                'pay_type' => 4,
                                'pay_money' => $ovv['total_money'],
                                'village_balance' => $ovv['total_money'],
                                'system_balance' => 0,
                                'pay_bind_id' => 0,
                                'pay_bind_name' => '',
                                'pay_bind_phone' => '',
                            ];
                            $PayOrder['order_type_flag'] = 'room_water_balance';
                            $PayOrder['is_service_time'] = 0;
                            $result = $storageService->orderSummary($ovv, $PayOrder);
                            if ($result) {
                                $water_balance = $userVacancy['water_balance'] - $ovv['total_money'];
                                $saveTmpArr = [
                                    'water_balance' => $water_balance
                                ];
                                $whereArr = array('pigcms_id' => $ovv['room_id'], 'village_id' => $rvv['village_id']);
                                $houseVillageUserVacancy->saveOne($whereArr, $saveTmpArr);
                                $bak = '水费已自动出扣费！';
                                $whereTmp = array('id' => $rvv['id']);
                                $extra_data = array('bak' => $bak, 'order' => $ovv, 'water_balance' => $userVacancy['water_balance'], 'dtime' => date('Y-m-d H:i:s'));
                                $extra_data_str = json_encode($extra_data, JSON_UNESCAPED_UNICODE);
                                $saveTmpData = array('is_need_order' => 2, 'handle_order_time' => $nowtime, 'extra_data' => $extra_data_str);
                                $houseVillageRoomWaterBalanceLog->saveOneData($whereTmp, $saveTmpData);
                                
                                //添加变动
                                $logBalanceRoomArr = array('room_id' => $ovv['room_id'], 'village_id' => $rvv['village_id'], 'current_v' => $water_balance);
                                $logBalanceRoomArr['xtype'] = 'room_water_balance';
                                $logBalanceRoomArr['change_v'] = $ovv['total_money'];
                                $logBalanceRoomArr['old_v'] = $userVacancy['water_balance'];
                                $logBalanceRoomArr['cfrom'] = 'order_auto_takeoff';
                                $logBalanceRoomArr['is_need_order'] = 0;
                                $logBalanceRoomArr['add_time'] = time();
                                $logBalanceRoomArr['bak'] = '余额变动后，水费订单【' . $ovv['order_id'] . '】自动扣除余额' . date('Y-m-d H:i:s');
                                $houseVillageRoomWaterBalanceLog->addOneData($logBalanceRoomArr);
                            }
                            usleep(300000);
                        }
                    }else{
                        
                        $whereTmp = array('id' => $rvv['id']);
                        $extra_data = array('bak' => '无水费待缴订单', 'order' => '', 'dtime' => date('Y-m-d H:i:s'));
                        $extra_data_str = json_encode($extra_data, JSON_UNESCAPED_UNICODE);
                        $saveTmpData = array('is_need_order' => 2, 'handle_order_time' => $nowtime, 'extra_data' => $extra_data_str);
                        $houseVillageRoomWaterBalanceLog->saveOneData($whereTmp, $saveTmpData);
                    }

                }
            }
        }
        return true;
    }
}