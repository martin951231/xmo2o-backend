<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      挂单相关操作
 */

namespace app\community\model\service\order;

use app\community\model\db\HouseNewChargeNumber;
use app\community\model\db\HouseNewChargePrepaid;
use app\community\model\db\HouseNewChargeProject;
use app\community\model\db\HouseNewOrderLog;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageNmvCard;
use app\community\model\db\HouseVillageParkConfig;
use app\community\model\db\HouseVillageParkingCar;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\db\ParkPassage;
use app\community\model\db\ParkSystem;
use app\community\model\db\ParkTotalRecord;
use app\community\model\service\CloudIntercomService;
use app\community\model\service\Device\HouseFaceImgService;
use app\community\model\service\HouseNewChargeRuleService;
use app\community\model\service\HouseNewParkingService;
use app\community\model\service\HouseVillageParkingService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\Park\D5Service;
use app\community\model\service\Park\D6Service;
use think\facade\Db;

class HouseNewOrderHangService
{
    public function hangOrder($params)
    {
        $village_id = isset($params['village_id']) && $params['village_id'] ? intval($params['village_id']) : 0;
        $order_id = isset($params['order_id']) && $params['order_id'] ? intval($params['order_id']) : 0;
        $opt_type = isset($params['opt_type']) && $params['opt_type'] ? trim($params['opt_type']) : ''; //batch_opt 批量挂单操作
        $order_ids = isset($params['order_ids']) && $params['order_ids'] ? $params['order_ids'] : ''; //batch_opt 批量挂单操作的订单id

        $db_house_new_pay_order = new HouseNewPayOrder();
        $whereOrder = [];
        $whereOrder[] = ['village_id', '=', $village_id];
        if ($opt_type == 'batch_opt' && $order_ids) {
            $whereOrder[] = ['order_id', 'in', $order_ids];
        } else {
            $whereOrder[] = ['order_id', '=', $order_id];
        }
        $field = 'order_id, car_id, pay_money,car_type,car_number,rule_id,village_id,property_id,position_id,room_id,order_type,order_name,check_status,is_discard,is_paid,is_prepare,service_month_num,service_give_month_num,summary_id,order_name,service_start_time,service_end_time,pay_time,project_id,service_time_handle';
        $orderInfoObj = $db_house_new_pay_order->getOrder($whereOrder, $field,'service_end_time asc');
        $orderInfoArr = array();
        if ($orderInfoObj && !$orderInfoObj->isEmpty()) {
            $orderInfoArr = $orderInfoObj->toArray();
            /*
            if (!empty($orderInfo)) {
                $orderInfo = reset($orderInfo);
            }
            */
        }
        
        if (empty($orderInfoArr)) {
            return array('error_code' => true, 'msg' => '挂单订单不存在！');
        }
        $retArr=array();
        $returnArr='';
        foreach ($orderInfoArr as $kk => $orderInfo) {
            $retArrTmp=$this->hangOrderOpt($orderInfo,$village_id,$opt_type);
            if(!$retArrTmp['error_code']){
                $returnArr=$retArrTmp;
            }
            $retArr[]=$retArrTmp;
        }
        if(empty($returnArr)){
            $returnArr=$retArr[0];
        }
        return $returnArr;
    }
    public function hangOrderOpt($orderInfo = array(),$village_id=0,$opt_type='')
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        Db::startTrans();
        $village_id=$village_id>0 ? $village_id :$orderInfo['village_id'];
        $order_id=$orderInfo['order_id'];
        if (isset($orderInfo['check_status']) && intval($orderInfo['check_status']) == 1) {
            return array('error_code' => true, 'msg' => '所选待缴账单已被挂单，请重新操作！','can_ignore'=>1);
        }
        if (isset($orderInfo['is_discard']) && intval($orderInfo['is_discard']) == 2) {
            return array('error_code' => true, 'msg' => '订单已作废不允许挂单！','can_ignore'=>1);
        }
        if (isset($orderInfo['is_discard']) && intval($orderInfo['is_discard']) == 3) {
            return array('error_code' => true, 'msg' => '订单已挂单不允许重复挂单！','can_ignore'=>1);
        }
        if (isset($orderInfo['is_paid']) && intval($orderInfo['is_paid']) == 1) {
            return array('error_code' => true, 'msg' => '订单已支付不允许挂单！','can_ignore'=>1);
        }
        if( $opt_type!='batch_opt'){
            $judgeHang = $this->checkHang($orderInfo);
            if (!$judgeHang ) {
                return array('error_code' => true, 'msg' => '周期性待缴账单挂单，请按照计费时间的先后顺序进行挂单！','can_ignore'=>0);
            }
        }
        fdump_api(['orderInfo' => $orderInfo], '00hangOrder',1);
        
        $order_type = isset($orderInfo['order_type']) && $orderInfo['order_type'] ? $orderInfo['order_type'] : '';
        $order_name = isset($orderInfo['order_name']) && $orderInfo['order_name'] ? $orderInfo['order_name'] : '';
        $room_id = isset($orderInfo['room_id']) && $orderInfo['room_id'] ? $orderInfo['room_id'] : 0;
        $position_id = isset($orderInfo['position_id']) && $orderInfo['position_id'] ? $orderInfo['position_id'] : 0;
        $property_id = isset($orderInfo['property_id']) && $orderInfo['property_id'] ? $orderInfo['property_id'] : 0;
        $rule_id = isset($orderInfo['rule_id']) && $orderInfo['rule_id'] ? $orderInfo['rule_id'] : 0;
        $car_id = isset($orderInfo['car_id']) && $orderInfo['car_id'] ? $orderInfo['car_id'] : 0;
        $project_id = isset($orderInfo['project_id']) && $orderInfo['project_id'] ? $orderInfo['project_id'] : 0;
        $service_start_time = isset($orderInfo['service_start_time']) && $orderInfo['service_start_time'] ? $orderInfo['service_start_time'] : 0;
        $service_end_time = isset($orderInfo['service_end_time']) && $orderInfo['service_end_time'] ? $orderInfo['service_end_time'] : 0;

        if ($order_type == 'non_motor_vehicle' && $position_id > 0) { // 非机动车缴费
            // 更新非机动车卡到期时间
            $village_nmv_card = new HouseVillageNmvCard();
            $village_nmv_card->updateCardInfo(['id' => $position_id], ['expiration_time' => $service_end_time]);
            $send_nmv_card = new CloudIntercomService();
            $send_nmv_card->sendNmvToDevice($position_id);
        }
        $service_house_new_charge_rule = new HouseNewChargeRuleService();
        $db_house_new_order_log = new HouseNewOrderLog();
        $rule_info = $service_house_new_charge_rule->getCallInfo(['r.id' => $rule_id], 'n.charge_type,r.*,p.type');
        if ($rule_info && !is_array($rule_info)) {
            $rule_info = $rule_info->toArray();
        }
        fdump_api(['line' => __LINE__, 'rule_info' => $rule_info, 'rule_id' => $rule_id, 'room_id' => $room_id], '00hangOrder', 1);
        $last_order = [];
        if ($order_type != 'non_motor_vehicle' && ($rule_info['type'] == 2 || $order_type == 'park_new')) {
            if ($position_id > 0) {
                $whereLastOrder = [['position_id', '=', $position_id], ['order_type', '=', $order_type], ['project_id', '=', $project_id]];
                $last_order = $db_house_new_order_log->getOne($whereLastOrder, true, 'id DESC');
                fdump_api(['line' => __LINE__], '00hangOrder', 1);
            } else {
                $whereLastOrder = [['room_id', '=', $room_id], ['order_type', '=', $order_type], ['position_id', '=', 0], ['project_id', '=', $project_id]];
                $last_order = $db_house_new_order_log->getOne($whereLastOrder, true, 'id DESC');
                fdump_api(['line' => __LINE__], '00hangOrder', 1);
            }
            if ($last_order && !is_array($last_order)) {
                $last_order = $last_order->toArray();
            }
            $whereNew = [];
            if ($order_type == 'park') {
                // 兼容老版停车收费
                if ($position_id > 0) {
                    $whereNew = [
                        ['position_id', '=', $position_id], ['order_type', '=', $order_type], ['project_id', '=', 0],
                    ];
                    fdump_api(['line' => __LINE__], '00hangOrder', 1);
                } else {
                    $whereNew = [
                        ['room_id', '=', $room_id], ['order_type', '=', $order_type],
                    ];
                    fdump_api(['line' => __LINE__], '00hangOrder', 1);
                }
            }
            if ($order_type == 'property') {
                if ($position_id > 0) {
                    $whereNew = [
                        ['position_id', '=', $position_id], ['order_type', '=', $order_type],
                    ];
                    fdump_api(['line' => __LINE__], '00hangOrder', 1);
                } else {
                    $whereNew = [
                        ['room_id', '=', $room_id], ['position_id', '=', 0], ['order_type', '=', $order_type],
                    ];
                    fdump_api(['line' => __LINE__], '00hangOrder', 1);
                }
            }
            if (!empty($whereNew)) {
                $new_order_log = $db_house_new_order_log->getOne($whereNew, true, 'id DESC');
                if ($new_order_log && !is_array($new_order_log)) {
                    $new_order_log = $new_order_log->toArray();
                }
                if (!empty($new_order_log)) {
                    fdump_api(['line' => __LINE__], '00hangOrder', 1);
                    $last_order = $new_order_log;
                }
            }
        }
        fdump_api(['line' => __LINE__, 'last_order' => $last_order], '00hangOrder', 1);

        $is_prepare = isset($orderInfo['is_prepare']) && $orderInfo['is_prepare'] ? $orderInfo['is_prepare'] : 0;
        $service_give_month_num = isset($orderInfo['service_give_month_num']) && $orderInfo['service_give_month_num'] ? $orderInfo['service_give_month_num'] : 0;
        $service_month_num = isset($orderInfo['service_month_num']) && $orderInfo['service_month_num'] ? $orderInfo['service_month_num'] : 0;
        $unify_flage_id = isset($orderInfo['unify_flage_id']) && $orderInfo['unify_flage_id'] ? $orderInfo['unify_flage_id'] : '';
        $bill_create_set = isset($rule_info['bill_create_set']) && $rule_info['bill_create_set'] ? $rule_info['bill_create_set'] : 0;
        $pay_money = isset($orderInfo['pay_money']) && floatval($orderInfo['pay_money']) > 0 ? $orderInfo['pay_money'] : 0;
        if (!empty($last_order) && $orderInfo['service_time_handle']!=5) {
            $service_start_time = $last_order['service_end_time'] + 100;
            $service_start_time = strtotime(date('Y-m-d', $service_start_time));
            if ($is_prepare == 1) {
                $cycleNum = $service_give_month_num + $service_month_num;
            } else {
                $cycleNum = $service_month_num ? $service_month_num : 1;
            }
            fdump_api(['line' => __LINE__, 'service_start_time' => $service_start_time, 'cycleNum' => $cycleNum], '00hangOrder', 1);
            if ($bill_create_set == 1) {
                $service_end_time = strtotime("+" . $cycleNum . " day", $service_start_time) - 1;
            } elseif ($unify_flage_id) {
                $service_end_time = strtotime("+1 month", $service_start_time) - 1;
            } elseif ($bill_create_set == 2) {
                //todo 判断是不是按照自然月来生成订单
                $service_end_time = $this->commonMonthStartTimeToEndTime($service_start_time, $cycleNum);
            } else {
                $service_end_time = $this->commonYearStartTimeToEndTime($service_start_time, $cycleNum);
            }
            fdump_api(['line' => __LINE__, 'service_end_time' => $service_end_time, 'cycleNum' => $cycleNum], '00hangOrder', 1);
        }
        if ($order_type == 'park_new') {
            $db_House_new_parking = new HouseNewParkingService();
            $db_house_village_park_config = new HouseVillageParkConfig();
            $db_house_village = new HouseVillage();
            $db_park_system = new ParkSystem();
            $db_house_village_parking_position = new HouseVillageParkingPosition();
            $db_park_passage = new ParkPassage();
            $db_house_village_parking_car = new HouseVillageParkingCar();
            $service_house_new_charge_prepaid = new HouseNewChargePrepaid();
            $db_park_total_record = new ParkTotalRecord();
            $car_number = isset($orderInfo['car_number']) && $orderInfo['car_number'] ? $orderInfo['car_number'] : '';
            $car_type = isset($orderInfo['car_type']) && $orderInfo['car_type'] ? $orderInfo['car_type'] : '';
            $tmp_order_type_v = $car_number;
            $park_config = $db_house_village_park_config->getFind(['village_id' => $village_id], 'park_sys_type, children_position_type');
            $park_sys_type = isset($park_config['park_sys_type']) && $park_config['park_sys_type'] ? $park_config['park_sys_type'] : '';
            $children_position_type = isset($park_config['children_position_type']) && $park_config['children_position_type'] ? $park_config['children_position_type'] : '';
            $house_village = $db_house_village->getOne($village_id, 'village_name');
            $park_system = $db_park_system->getFind(['park_id' => $village_id]);
            $park_position_set = [];
            if ($car_type == 'month_type') {
                $parking_position = $db_house_village_parking_position->getFind(['position_id' => $position_id]);
                $parking_car = $db_house_village_parking_car->getHouseVillageParkingCarLists(['car_position_id' => $parking_position['position_id']], '*', 0);
                $channel_number_arr = $db_park_passage->getColumn(['village_id' => $village_id], 'channel_number');
                $channel_number_str = implode(',', $channel_number_arr);
                if (!$channel_number_str) {
                    $channel_number_str = '';
                }
                if (2 != $parking_position['position_pattern']) {
                    // 固定车位-共同使用一个月卡会员编号
                    if ($parking_position['card_id']) {
                        $now_car_id = $parking_position['card_id'];
                    } else {
                        $now_car_id = '1' . sprintf("%09d", $parking_position['position_id']);//月卡会员编号（收费系统唯一编号  是
                        $park_position_set['card_id'] = $now_car_id;
                    }
                    $address = $house_village['village_name'] . '车位：' . $parking_position['position_num'];
                } else {
                    $address = $house_village['village_name'];
                }
                // 如果是虚拟停车位 对应每一个车生成唯一收费编号
                $parking_car_type_arr = $db_House_new_parking->parking_car_type_arr;
                if ($parking_position['end_time'] < time()) {
                    $park_end_time = strtotime(date('Y-m-d 23:59:59', (time() - 86400)));
                } else {
                    $park_end_time = $parking_position['end_time'];
                }
                $park_set = [];
                $park_data['begin_time'] = $park_end_time + 1;
                if ($is_prepare == 1) {
                    $prepaid_info = $service_house_new_charge_prepaid->getList(['charge_rule_id' => $rule_id]);
                    if (!empty($prepaid_info)) {
                        $prepaid_info = $prepaid_info->toArray();
                    }
                    if (!empty($prepaid_info)) {
                        $prepaidInfo = $prepaid_info[0];
                        if ($prepaidInfo['give_cycle_datetype'] == 1) {
                            $park_end_time = strtotime("+" . $service_give_month_num . " day", $park_end_time);
                        } elseif ($prepaidInfo['give_cycle_datetype'] == 2) {
                            $park_end_time = strtotime("+" . $service_give_month_num . " day", $park_end_time);
                        } elseif ($prepaidInfo['give_cycle_datetype'] == 3) {
                            $park_end_time = strtotime("+" . $service_give_month_num . " day", $park_end_time);
                        }
                    }
                    $cycle = $service_month_num;
                } else {
                    $cycle = $service_month_num ? $service_month_num : 1;
                }
                if ($bill_create_set == 1) {
                    $park_data['end_time'] = strtotime("+" . $cycle . " day", $park_end_time);
                } elseif ($bill_create_set == 2) {
                    $park_data['end_time'] = $this->commonMonthStartTimeToEndTime($park_end_time, $cycle);
                } else {
                    $park_data['end_time'] = $this->commonYearStartTimeToEndTime($park_end_time, $cycle);
                }
                $park_position_set['end_time'] = $park_data['end_time'];
                $db_house_village_parking_position->saveOne(['position_id' => $position_id], $park_position_set);
                foreach ($parking_car as $k => $vv) {
                    if (intval($parking_position['end_time']) < intval(time())) {
                        $park_data['begin_time'] = time();
                    }
                    $park_set['start_time'] = $park_data['begin_time'];
                    $park_set['end_time'] = $park_data['end_time'];
                    if ($park_sys_type != 'D1') {
                        $park_data['park_id'] = $vv['village_id'];
                    }
                    $park_data['car_number'] = $vv['province'] . $vv['car_number'];
                    if ($now_car_id) {
                        $park_position_set['start_time'] = $park_data['begin_time'];
                        $park_position_set['end_time'] = $park_data['end_time'];
                    }
                    if ($park_sys_type == 'D5') {
                        //todo D5智慧停车 触发下发到设备
                        (new D5Service())->D5AddCar($vv['village_id'], $vv['card_id'], $park_set['start_time'], $park_set['end_time']);
                    } elseif ($park_sys_type == 'D6') {
                        //todo D6智慧停车 触发下发到设备
                        (new D6Service())->d6synVehicle($vv['village_id'], $vv['card_id'], 0, $park_set);
                    }

                    //D3同步白名单到设备上
                    $white_record = [
                        'village_id' => $vv['village_id'],
                        'car_number' => $park_data['car_number']
                    ];
                    (new HouseVillageParkingService())->addWhitelist($white_record);
                    // 3.7月卡会员同步（停车云）
                    $park_data['remark'] = '';
                    $park_data['pid'] = '';
                    $service_name = 'month_member_sync';// 添加会员卡操作
                    if ($now_car_id) {
                        $card_id = $now_car_id;
                    } else {
                        $card_id = '2' . sprintf("%09d", $vv['car_id']);//月卡会员编号（收费系统唯一编号  是
                    }
                    // 同步储值卡
                    if ($parking_car_type_arr && $vv['parking_car_type'] && isset($parking_car_type_arr[$vv['parking_car_type']]) && $parking_car_type_arr[$vv['parking_car_type']]) {
                        $car_type_id = $parking_car_type_arr[$vv['parking_car_type']];
                    } else if ($vv['temporary_car_type']) {
                        $car_type_id = $vv['store_value_car_type'];
                    } else {
                        $car_type_id = '储值车A';
                    }
                    $park_set['card_id'] = $card_id;
                    if ($park_sys_type == 'D1' && $vv['card_id']) {
                        $park_data['is_edit'] = 1;
                    } elseif ($park_sys_type != 'D1') {
                        $park_data['operate_type'] = $vv['card_id'] ? '2' : '1';    //1 添加，2 编辑
                        if ($park_data['operate_type'] == 1) {
                            $park_data['create_time'] = time();
                        } else {
                            $park_data['update_time'] = time();
                        }
                        $park_data['price'] = $pay_money;//实收金额
                        $park_data['channel_id'] = $channel_number_str;//月卡允许通行的通道编号
                        $park_data['amount_receivable'] = $pay_money;//应收金额
                    }
                    if ($park_sys_type == 'D7') {
                        if (isset($vv['parking_car_type']) && !empty($vv['parking_car_type']) && $vv['parking_car_type'] < 17) {
                            $res_park = $db_house_village_parking_car->editHouseVillageParkingCar(['car_id' => $car_id], $park_set);
                            if ($park_position_set) {
                                $db_house_village_parking_position->saveOne(['position_id' => $position_id], $park_position_set);
                            }
                        }
                    } else {
                        $park_data['name'] = $vv['car_user_name'];
                        $park_data['car_type_id'] = $car_type_id;
                        $park_data['tel'] = $vv['car_user_phone'] ? $vv['car_user_phone'] : '';
                        $park_data['address'] = $address;
                        $park_data['p_lot'] = strval($parking_position['position_num']);

                        $park_data['card_id'] = $card_id;
                        $json_data['msg_id'] = createRandomStr(8, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(12, true, true);//是
                        $json_data['data'] = $park_data;
                        $json_data['service_name'] = $service_name;

                        $post_data['unserialize_desc'] = serialize($json_data);
                        $post_data['status'] = 2;
                        $post_data['card_id'] = $card_id;
                        $post_data['msg_id'] = $json_data['msg_id'];
                        $post_data['village_id'] = $village_id;
                        $post_data['park_id'] = $park_system['park_id'];
                        $post_data['token'] = $park_system['token'];
                        $post_data['car_number'] = $park_data['car_number'] ? $park_data['car_number'] : '';
                        $post_data['service_name'] = $service_name;
                        $post_data['create_time'] = time();
                        $res = $db_park_total_record->add($post_data);
                        // 没有配置停车场，或者数据添加成功都保存车辆信息。
                        if (!($park_system['park_id'] && $park_system['token']) || $res) {
                            $db_house_village_parking_car->editHouseVillageParkingCar(['car_id' => $vv['car_id']], $park_set);
                            if ($park_position_set) {
                                $db_house_village_parking_position->saveOne(['position_id' => $parking_position['position_id']], $park_position_set);
                            }
                        } else {
                            $json_data['msg_id'] = createRandomStr(8, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(12, true, true);//是
                            $json_data['data'] = $park_data;
                            $json_data['service_name'] = $service_name;
                            $post_data['unserialize_desc'] = serialize($json_data);
                            $post_data['status'] = 2;
                            $post_data['card_id'] = $card_id;
                            $post_data['msg_id'] = $json_data['msg_id'];
                            $post_data['village_id'] = $vv['village_id'];
                            $post_data['park_id'] = $park_system['park_id'];
                            $post_data['token'] = $park_system['token'];
                            $post_data['car_number'] = $park_data['car_number'] ? $park_data['car_number'] : '';
                            $post_data['service_name'] = $service_name;
                            $post_data['create_time'] = time();
                            $res = $db_park_total_record->add($post_data);//月卡会员下发
                            if ($res) {
                                $db_house_village_parking_car->editHouseVillageParkingCar(['car_id' => $vv['car_id']], $park_set);
                                if ($park_position_set) {
                                    $db_house_village_parking_position->saveOne(['position_id' => $parking_position['position_id']], $park_position_set);
                                }
                            }
                        }
                    }
                }
                //子母车位续费同步到期时间
                if (intval($children_position_type) == 1) {
                    $parking_position_arr = $db_house_village_parking_position->getColumn(['parent_position_id' => $position_id, 'children_type' => 2], 'position_id');

                    if (!empty($parking_position_arr)) {
                        $db_house_village_parking_position->saveOne(['position_id' => $parking_position_arr], $park_position_set);
                        $park_car_arr = $db_house_village_parking_car->get_column(['car_position_id' => $parking_position_arr], 'car_id');

                        if (!empty($park_car_arr) && isset($park_set) && !empty($park_set)) {
                            $db_house_village_parking_car->editHouseVillageParkingCar(['car_id' => $park_car_arr], $park_set);
                        }
                    }
                }
                $service_start_time = $park_data['begin_time'];
                $service_end_time = $park_data['end_time'];
            }
        }


        if (($order_type == 'park' || $order_type == 'parking_management') && $position_id > 0) {
            $service_house_village_parking = new HouseVillageParkingService();
            $service_house_village_parking->editParkingPosition(['position_id' => $position_id], ['end_time' => $service_end_time]);
            $service_house_village_parking->editParkingCar(['car_position_id' => $position_id], ['end_time' => $service_end_time]);
        }
        fdump_api(['line' => __LINE__, 'service_end_time' => $service_end_time, 'room_id' => $room_id], '00hangOrder', 1);
        if ($order_type == 'property' && $room_id > 0) {
            $service_house_village_user_bind = new HouseVillageUserBindService();
            $service_house_village_user_bind->saveUserBind([
                ['vacancy_id', '=', $room_id],
                ['type', 'in', [0, 3]],
                ['status', '=', 1],
            ], ['property_endtime' => $service_end_time]);
            try {
                $service_house_village_user_bind = new HouseVillageUserBindService();
                $userList = $service_house_village_user_bind->getList([['vacancy_id', '=', $room_id], ['status', '=', 1]], 'uid, pigcms_id');
                $houseFaceImgService = new HouseFaceImgService();
                foreach ($userList as $userInfo) {
                    $houseFaceImgService->commonUserToBox($userInfo['uid'], $userInfo['pigcms_id']);
                }
            } catch (\Exception $e) {

            }
        }
        $updateData = [];
        $updateData['service_start_time'] = $service_start_time;
        $updateData['service_end_time'] = $service_end_time;
        // 3-已挂单
        $updateData['is_discard'] = 3;
        $updateData['update_time'] = time();
        $updateData['hang_time'] = time();
        $db_house_new_pay_order->saveOne(['order_id' => $order_id], $updateData);
       
        $newOrderLog = [
            'order_id' => $order_id, 'project_id' => $project_id, 'order_type' => $order_type,
            'order_name' => $order_name, 'room_id' => $room_id, 'position_id' => $position_id,
            'property_id' => $property_id, 'village_id' => $village_id, 'add_time' => time(),
            'service_start_time' => $service_start_time, 'service_end_time' => $service_end_time,
            'desc'=>'挂单操作延期'
        ];
        fdump_api(['line' => __LINE__, 'newOrderLog' => $newOrderLog], '00hangOrder', 1);
        $id = $db_house_new_order_log->addOne($newOrderLog);
        

        Db::commit();
        return array('error_code' => false, 'msg' => '提交成功！');
    }
    
    public function checkHang($orderInfo) {
        $dbHouseNewPayOrder = new HouseNewPayOrder();
        $order_id = isset($orderInfo['order_id']) && $orderInfo['order_id'] ? $orderInfo['order_id'] : 0;
        $order_type = isset($orderInfo['order_type']) && $orderInfo['order_type'] ? $orderInfo['order_type'] : '';
        $service_start_time = isset($orderInfo['service_start_time']) && $orderInfo['service_start_time'] ? $orderInfo['service_start_time'] : 0;
        $service_end_time = isset($orderInfo['service_end_time']) && $orderInfo['service_end_time'] ? $orderInfo['service_end_time'] : 0;
        $village_id = isset($orderInfo['village_id']) && $orderInfo['village_id'] ? $orderInfo['village_id'] : 0;
        
        if (isset($projectInfo['type']) && intval($projectInfo['type']) == 2) {
            // 周期性费用 查下上级归属
            $whereCheckOrder = [];
            $whereCheckOrder[] = ['order_id', '<>', $order_id];
            $whereCheckOrder[] = ['village_id', '=', $village_id];
            $whereCheckOrder[] = ['order_type', '=', $order_type];
            $whereCheckOrder[] = ['service_end_time', '<', $service_end_time];
            $whereCheckOrder[] = ['is_paid', '=', 0];
            $whereCheckOrder[] = ['is_discard', '=', 1];
            $otherInfo = $dbHouseNewPayOrder->getOrder($whereCheckOrder, 'order_id');
            if ($otherInfo && !is_array($otherInfo)) {
                $otherInfo = $otherInfo->toArray();
                if (!empty($otherInfo)) {
                    $otherInfo = reset($otherInfo);
                }
            }
            if (isset($otherInfo['order_id']) && $otherInfo['order_id']) {
                return false;
            }
        }
        return true;
    }

    // 公用使用的延算月份的方法
    public function commonMonthStartTimeToEndTime($service_start_time, $cycleNum) {
        //todo 判断是不是按照自然月来生成订单
        if (cfg('open_natural_month') == 1) {
            $service_end_time = strtotime("+" . $cycleNum . " month", $service_start_time) - 1;
        } else {
            $cycle = $cycleNum * 30;
            $service_end_time = strtotime("+" . $cycle . " day", $service_start_time) - 1;
        }
        return $service_end_time;
    }

    // 公用使用的延算年份的方法
    public function commonYearStartTimeToEndTime($service_start_time, $cycleNum) {
        $service_end_time = strtotime("+" . $cycleNum . " year", $service_start_time) - 1;
        return $service_end_time;
    }
}