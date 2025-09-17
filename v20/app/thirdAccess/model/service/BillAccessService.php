<?php
namespace app\thirdAccess\model\service;

use app\common\model\db\ProcessSubPlan;
use app\common\model\service\UserService;
use app\community\model\db\HouseNewOrderLog;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseNewPayOrderSummary;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\VillageUserMoneyList;
use app\community\model\db\VillageUserMoneyLog;
use app\community\model\service\HouseNewCashierService;
use app\community\model\service\HouseNewChargeRuleService;
use app\community\model\service\HouseNewChargeService;
use app\community\model\service\HouseNewPorpertyService;
use app\community\model\service\HousePropertyDigitService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageUserService;
use app\community\model\service\NewPayService;
use app\community\model\service\PlatOrderService;
use app\pay\model\db\PayOrderInfo;
use app\pay\model\service\PayService;
use file_handle\FileHandle;
use thirdLink\sftp;

class BillAccessService
{
    public function getVillageData($paramData) {
        $whereVillage = [];
        $whereVillage[] = ['status', '=', 1];
        if (isset($paramData['property_id'])&&$paramData['property_id']) {
            $whereVillage[] = ['property_id', '=', intval($paramData['property_id'])];
        }
        $fieldVillage = 'village_id,village_name,village_address,property_id';
        $orderVillage = 'village_id DESC';
        if (isset($paramData['page'])&&$paramData['page']) {
            $page = intval($paramData['page']);
            $limit = isset($paramData['limit'])&&$paramData['limit']?intval($paramData['limit']):20;
            $type = 1;
        } else {
            $page = $limit = $type = 0;
        }
        $villages = (new HouseVillageService)->getList($whereVillage,$fieldVillage,$page,$limit,$orderVillage,$type);
        if (!empty($villages)) {
            $villages = $villages->toArray();
        }
        if (empty($villages)) {
            $villages = [];
        }
        return $villages;
    }
    public function getVillageOwnerInfo($village_id=0,$phone=''){
        $houseVillageUserBind=new HouseVillageUserBind();
        $whereArr=array(['village_id','=',$village_id],['phone','=',$phone]);
        $whereArr[]= ['type','<',4];
        $whereArr[]= ['status','=',1];
        $whereArr[]= ['vacancy_id','>',0];
        $userBindObj=$houseVillageUserBind->getOne($whereArr,'*','uid desc,type asc');
        $userBind=array();
        if($userBindObj && !$userBindObj->isEmpty()){
            $userBind=$userBindObj->toArray();
        }
        if(empty($userBind)){
            throw new \Exception('未查到手机号是'.$phone.'的业主信息！');
        }
        if($userBind['uid']<1){
            throw new \Exception('查到手机号用户业主没有平台UID，无法使用住户余额功能！');
        }
        $returnArr=array('uid'=>$userBind['uid'],'phone'=>$phone,'name'=>$userBind['name'],'current_balance_money'=>0,'room_list'=>array(),'user_bind_id'=>0,'room_address'=>'','room_id'=>0,'type'=>0,'type_str'=>'业主');
        $villageUserMoneyListDb= new VillageUserMoneyList();
        $whereTmpArr=array(['business_type','=',1],['business_id','=',$village_id],['uid','=',$userBind['uid']]);
        $villageUserMoneyObj=$villageUserMoneyListDb->getOne($whereTmpArr,'*');
        if($villageUserMoneyObj && !$villageUserMoneyObj->isEmpty()){
            $returnArr['current_balance_money']=$villageUserMoneyObj['current_money']>0  ? round($villageUserMoneyObj['current_money'],2):0;
        }
        $typeArr=array('业主','家属','租客','业主');
        $whereArr=array(['village_id','=',$village_id],['uid','=',$userBind['uid']]);
        $whereArr[]= ['type','<',4];
        $whereArr[]= ['status','=',1];
        $whereArr[]= ['vacancy_id','>',0];
        $houseVillageService= new HouseVillageService();
        $userBindListObj=$houseVillageUserBind->getBindUserList($whereArr,'*','type asc,pigcms_id desc',1,100);
        if($userBindListObj && !$userBindListObj->isEmpty()){
            $userBinds=$userBindListObj->toArray();
            foreach ($userBinds as $kk=>$vv){
                $tmpArr=array('user_bind_id'=>$vv['pigcms_id'],'uid'=>$vv['uid'],'room_id'=>$vv['vacancy_id'],'room_address'=>'','type'=>$vv['type'],'type_str'=>$typeArr[$vv['type']]);
                $room_address=$houseVillageService->getSingleFloorRoom($vv['single_id'],$vv['floor_id'],$vv['layer_id'],$vv['vacancy_id'],$village_id);
                $tmpArr['room_address']=$room_address;
                if(in_array($vv['type'],[0,3])){
                    $returnArr['user_bind_id']=$tmpArr['user_bind_id'];
                    $returnArr['room_address']=$room_address;
                    $returnArr['room_id']=$vv['vacancy_id'];
                    $returnArr['type']=$vv['type'];
                    $returnArr['type_str']=$tmpArr['type_str'];
                }
                $returnArr['room_list'][]=$tmpArr;
            }
            if( $returnArr['user_bind_id']<1){
                $returnArr['user_bind_id']= $returnArr['room_list']['0']['user_bind_id'];
                $returnArr['room_address']=$returnArr['room_list']['0']['room_address'];
                $returnArr['room_id']=$returnArr['room_list']['0']['room_id'];
                $returnArr['type']=$returnArr['room_list']['0']['type'];
                $returnArr['type_str']=$returnArr['room_list']['0']['type_str'];
            }
        }
        return $returnArr;
    }

    public function villageUserBalanceChange($village_id = 0, $uid = 0, $pdata = array())
    {
        $villageUserMoneyListDb = new VillageUserMoneyList();
        $villageUserMoneyLogDb = new VillageUserMoneyLog();
        $houseVillageUserBind = new HouseVillageUserBind();
        $whereArr = array(['village_id', '=', $village_id], ['uid', '=', $uid]);
        $whereArr[] = ['type', '<', 4];
        $whereArr[] = ['status', '=', 1];
        if (isset($pdata['room_id']) && $pdata['room_id'] > 0) {
            $whereArr[] = ['vacancy_id', '=', $pdata['room_id']];
        } else {
            $whereArr[] = ['vacancy_id', '>', 0];
        }
        if (isset($pdata['user_bind_id']) && $pdata['user_bind_id'] > 0) {
            $whereArr[] = ['pigcms_id', '=', $pdata['user_bind_id']];
        }
        $opt_type = isset($pdata['opt_type']) ? $pdata['opt_type'] : '';
        if (!in_array($opt_type, ['user_recharge', 'user_deduct'])) {
            throw new \Exception('住户余额操作类型错误！');
        }
        $opt_money = isset($pdata['opt_money']) ? $pdata['opt_money'] : 0;
        if ($opt_money <= 0) {
            throw new \Exception('住户余额操作金额错误！');
        }
        $userBindObj = $houseVillageUserBind->getOne($whereArr, '*', 'uid desc,type asc');
        if (empty($userBindObj) || $userBindObj->isEmpty()) {
            throw new \Exception('未查到业主住房信息！');
        }
        $user_bind_id=$userBindObj['pigcms_id'];
        $room_id=$userBindObj['vacancy_id'];
        $nowtime = time();
        
        $whereTmpArr = array(['business_type', '=', 1], ['business_id', '=', $village_id], ['uid', '=', $uid]);
        $villageUserMoneyObj = $villageUserMoneyListDb->getOne($whereTmpArr, '*');
        $villageUserMoney = array();
        $current_money=0;
        if ($villageUserMoneyObj && !$villageUserMoneyObj->isEmpty()) {
            $villageUserMoney = $villageUserMoneyObj->toArray();
            $current_money=$villageUserMoney['current_money'];
        }
        $moneyLogArr = array('uid' => $uid, 'business_type' => 1, 'business_id' => $village_id, 'current_money' => 0, 'money' => $opt_money, 'add_time' => $nowtime, 'order_type' => 'third_opt');
        $update_current_money=$opt_money;
        if ($opt_type == 'user_deduct') {
            if (empty($villageUserMoney)) {
                throw new \Exception('当前住户余额为0元，不够扣除！');
            } elseif ($villageUserMoneyObj['current_money'] < $opt_money) {
                throw new \Exception('当前住户余额为' . $villageUserMoneyObj['current_money'] . '元，不够扣除！');
            }
            $moneyLogArr['current_money'] = $villageUserMoneyObj['current_money'];
            $moneyLogArr['type'] = 2;
            $update_current_money = $villageUserMoneyObj['current_money'] - $opt_money;
            $update_current_money = round($update_current_money, 2);
            $moneyLogArr['after_price'] = $update_current_money;
            $saveArr = array('current_money' => $update_current_money, 'update_time' => $nowtime);
            $whereTmpArr = array(['business_type', '=', 1], ['business_id', '=', $village_id], ['uid', '=', $uid]);
            $villageUserMoneyListDb->saveOne($whereTmpArr, $saveArr);
        } else {
            $moneyLogArr['type'] = 1;
            if (empty($villageUserMoney)) {
                $addArr = array('uid' => $uid, 'business_type' => 1, 'business_id' => $village_id);
                $addArr['current_money'] = $opt_money;
                $addArr['add_time'] = $nowtime;
                $addArr['remarks'] = isset($pdata['desc']) && $pdata['desc'] ? $pdata['desc'] : '';
                $villageUserMoneyListDb->addOne($addArr);
                $moneyLogArr['after_price'] = $opt_money;
            } else {
                $update_current_money = $villageUserMoneyObj['current_money'] + $opt_money;
                $saveArr = array('current_money' => $update_current_money, 'update_time' => $nowtime);
                $moneyLogArr['current_money'] = $villageUserMoneyObj['current_money'];
                $moneyLogArr['after_price'] = $update_current_money;
                $whereTmpArr = array(['business_type', '=', 1], ['business_id', '=', $village_id], ['uid', '=', $uid]);
                $villageUserMoneyListDb->saveOne($whereTmpArr, $saveArr);
            }
        }
        if (isset($pdata['ip']) && $pdata['ip']) {
            $moneyLogArr['ip'] = $pdata['ip'];
        }
        if (isset($pdata['desc']) && $pdata['desc']) {
            $moneyLogArr['desc'] = $pdata['desc'];
        }
        if (isset($pdata['pay_type']) && $pdata['pay_type']) {
            $moneyLogArr['pay_type'] = $pdata['pay_type'];
        }
        if (isset($pdata['third_order_id']) && $pdata['third_order_id']) {
            $moneyLogArr['order_id'] = $pdata['third_order_id'];
        }
        $log_id = $villageUserMoneyLogDb->addOne($moneyLogArr);
        return array('uid'=>$uid,'user_bind_id'=>$user_bind_id,'room_id'=>$room_id,'current_money'=>$current_money,'opt_after_balance_money'=>$update_current_money,'village_id'=>$village_id);
    }
    public function userNewPayOrders($param) {
        $village_id = isset($param['village_id'])&&intval($param['village_id'])?intval($param['village_id']):0;
        $operation = isset($param['operation'])&&trim($param['operation'])?trim($param['operation']):'';
        $thirdType = isset($param['thirdType'])&&trim($param['thirdType'])?trim($param['thirdType']):'';
        $thirdSite = isset($param['thirdSite'])&&trim($param['thirdSite'])?trim($param['thirdSite']):'';
        $phone = isset($param['phone'])&&trim($param['phone'])?trim($param['phone']):'';
        $name = isset($param['name'])&&trim($param['name'])?trim($param['name']):'';
        $pigcms_id = isset($param['pigcms_id'])&& $param['pigcms_id'] ? intval($param['pigcms_id']):'';
        $order_type = isset($param['order_type'])&& $param['order_type'] ? $param['order_type']:'';
        $page = isset($param['page'])&&intval($param['page'])?intval($param['page']):0;
        $limit = isset($param['limit'])&&intval($param['limit'])?intval($param['limit']):20;
        $businessType = 'userNewOrders';

        if (!$village_id) {
            throw new \Exception('请上传小区ID');
        }
        $service_house_village = new HouseVillageService();
        $service_house_new_property = new HouseNewPorpertyService();
        $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$village_id],'property_id');
        $is_new_version = $service_house_new_property->getTakeEffectTimeJudge($village_info['property_id']);
        if (!$is_new_version) {
            throw new \Exception('目前仅支持新版物业收费，请前往设置');
        }
        $service_user = new HouseVillageUserService();
        $where = [];
        $where[] = ['a.village_id','=',$village_id];
        $where[] = ['a.type','in','0,1,2,3'];
        $where[] = ['a.status','=',1];
        $where[] = ['a.vacancy_id','>',0];
        if ($pigcms_id) {
            $where[] = ['a.pigcms_id','=',$pigcms_id];
        } elseif ($phone) {
            $where[] = ['a.phone','=',$phone];
        }
        $field = 'a.pigcms_id,a.village_id,a.uid,a.name,a.phone,a.single_id,a.floor_id,a.layer_id,a.vacancy_id,hvf.floor_name,hvs.single_name';
        $list = $service_user->getLimitRoomList($where,0,$field,'a.pigcms_id DESC',false);
        if (!empty($list)) {
            $list = $list->toArray();
        }
        $arr = [];
        $userRoomBinds = [];
        if (empty($list)) {
            $list = [];
            $arr['users'] = $list;
            $arr['orderCode'] = '1001';
            $arr['orderMsg'] = '当前用户无欠费账单';
            $arr['orders'] = [];
            return $arr;
        } else {
            $users = [];
            $bindRoomIds = [];
            $dbHouseVillageUserVacancy = new HouseVillageUserVacancy(); //门牌号
            foreach ($list as $item) {
                if (isset($item['pigcms_id'])&&$item['pigcms_id']) {
                    $bindRoomIds[] = $item['vacancy_id'];
                    $vacancy = $dbHouseVillageUserVacancy->getOne(['pigcms_id'=>$item['vacancy_id']],'room');
                    $room = $vacancy['room'];
                    $user = [
                        'pigcms_id' => $item['pigcms_id'],
                        'village_id' => $item['village_id'],
                        'unitName' => $item['floor_name'],
                        'buildingName' => $item['single_name'],
                        'room' => $room,
                        'room_id' => $item['vacancy_id'],
                        'uid' => $item['uid'],
                        'name' => $item['name'],
                        'phone' => $item['phone'],
                        'address' => $item['address'],
                    ];
                    $users[] = $user;
                    $userRoomBinds[$item['vacancy_id']] = $user;
                }
            }
            $arr['users'] = $users;
            $arr['orders'] = [];
        }
        $db_house_new_pay_order = new HouseNewPayOrder();
        $newOrder = 'o.order_type asc,o.service_start_time asc';
        $whereOrder = [];
        $whereOrder[] = ['o.is_paid','=',2];
        $whereOrder[] = ['o.is_discard','=',1];
        $whereOrder[] = ['o.village_id','=',$village_id];
        $whereOrder[] = ['o.room_id','in',$bindRoomIds];
        $whereOrder[] = ['o.check_status','<>',1];
        if($order_type){
            $whereOrder[] = ['o.order_type','=',$order_type];
        }else{
            $whereOrder[] = ['o.order_type','<>','non_motor_vehicle'];
        }
        $fieldOrderStr='o.order_id,o.pigcms_id,o.name,o.phone,o.order_type,o.order_name,o.room_id,o.village_id,o.total_money,o.modify_money,o.modify_reason,o.modify_time,o.add_time,
        o.late_payment_day,o.late_payment_money,o.service_month_num,o.service_give_month_num,o.service_start_time,o.service_end_time,
        r.charge_name,p.name as project_name,n.charge_number_name';
        $dataOrder = $db_house_new_pay_order->getList($whereOrder, $fieldOrderStr, $page, $limit, $newOrder);
        if (!empty($dataOrder)) {
            $dataOrder = $dataOrder->toArray();
        }
        $db_house_village = new HouseVillage();
        $village_info = $db_house_village->getOne($village_id,'property_id');
        $property_id = $village_info['property_id'];
        if($property_id){
            $db_house_property_digit_service = new HousePropertyDigitService();
            $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$property_id]);
        }else{
            $digit_info = [];
        }
        $service_house_new_charge = new HouseNewChargeService();
        $year_arr = array();
        if (!empty($dataOrder)) {
            foreach ($dataOrder as $v) {
                $year = date('Y', $v['add_time']);
                $yearType = $year .'_'. $v['order_type'];
                $order_name = isset($service_house_new_charge->charge_type[$v['order_type']]) ? $service_house_new_charge->charge_type[$v['order_type']]:$v['order_type'];
                if (isset($year_arr[$yearType])&&$year_arr[$yearType]) {
                    $year_arr[$yearType]['orderIds'][] = $v['order_id'];
                    $total_money = $year_arr[$yearType]['total_money'];
                    if (isset($v['modify_money'])&&floatval($v['modify_money'])>0) {
                        $total_money += $v['modify_money'];
                        $year_arr[$yearType]['moneys'][$v['order_id']] = $v['modify_money'];
                    } elseif (isset($v['total_money'])) {
                        $total_money += $v['total_money']?$v['total_money']:0;
                        $year_arr[$yearType]['moneys'][$v['order_id']] = $v['total_money']?$v['total_money']:0;
                    }
                    if($digit_info && isset($digit_info['other_digit'])){
                        $digit_info['other_digit'] = ($digit_info['other_digit']>2 || empty($digit_info['other_digit'])) && $digit_info['other_digit']!=0?2:$digit_info['other_digit'];
                        $other_digit=$digit_info['other_digit'];
                        if($other_digit<$digit_info['meter_digit']){
                            $other_digit=$digit_info['meter_digit'];
                        }
                        $total_money = formatNumber($total_money,$other_digit,$digit_info['type']);
                    }
                    $total_money = formatNumber($total_money,2,1);
                    $year_arr[$yearType]['total_money'] = $total_money;
                    $orderIds = $year_arr[$yearType]['orderIds'];
                    $orderIdString = implode(',',$orderIds);
                    $year_arr[$yearType]['orderIdString'] = $orderIdString;
                    if ($pigcms_id) {
                        $year_arr[$yearType]['order_id'] = md5(json_encode([$orderIdString,$pigcms_id]));
                    } elseif ($phone) {
                        $year_arr[$yearType]['order_id'] = md5(json_encode([$orderIdString,$phone]));
                    }
                } else {
                    $year_arr[$yearType] = [];
                    $year_arr[$yearType]['order_name'] = $year.'年'.$order_name;
                    $year_arr[$yearType]['order_type'] = $v['order_type'];
                    $year_arr[$yearType]['village_id'] = $v['village_id'];
                    if (isset($v['modify_money'])&&isset($v['total_money'])&&floatval($v['modify_money'])>0) {
                        $total_money = $v['modify_money'];
                    } elseif (isset($v['total_money'])) {
                        $total_money = $v['total_money']?$v['total_money']:0;
                    } else {
                        $total_money = 0;
                    }
                    if($digit_info && isset($digit_info['other_digit'])){
                        $digit_info['other_digit'] = ($digit_info['other_digit']>2 || empty($digit_info['other_digit'])) && $digit_info['other_digit']!=0?2:$digit_info['other_digit'];
                        $other_digit=$digit_info['other_digit'];
                        if($other_digit<$digit_info['meter_digit']){
                            $other_digit=$digit_info['meter_digit'];
                        }
                        $total_money = formatNumber($total_money,$other_digit,$digit_info['type']);
                    }
                    $total_money = formatNumber($total_money,2,1);
                    $year_arr[$yearType]['total_money'] = $total_money;
                    $year_arr[$yearType]['moneys'] = [];
                    $year_arr[$yearType]['moneys'][$v['order_id']] = $total_money;
                    $year_arr[$yearType]['orderIds'] = [];
                    $year_arr[$yearType]['orderIds'][] = $v['order_id'];
                    $orderIdString = $v['order_id'];
                    $year_arr[$yearType]['orderIdString'] = strval($orderIdString);
                    if ($pigcms_id) {
                        $year_arr[$yearType]['order_id'] = md5(json_encode([$orderIdString,$pigcms_id]));
                    } elseif ($phone) {
                        $year_arr[$yearType]['order_id'] = md5(json_encode([$orderIdString,$phone]));
                    }
                    if (isset($v['name']) && $v['name']) {
                        $year_arr[$yearType]['name'] = $v['name'];
                    }
                    if (isset($v['phone']) && $v['phone']) {
                        $year_arr[$yearType]['phone'] = $v['phone'];
                    }
                    if ($v['room_id'] && isset($userRoomBinds[$v['room_id']])) {
                        $year_arr[$yearType]['pigcms_id'] = $userRoomBinds[$v['room_id']]['pigcms_id'];
                        if (!isset($year_arr[$yearType]['name']) || !$year_arr[$yearType]['name']) {
                            $year_arr[$yearType]['name'] = $userRoomBinds[$v['room_id']]['name'];
                        }
                        if (!isset($year_arr[$yearType]['phone']) || !$year_arr[$yearType]['phone']) {
                            $year_arr[$yearType]['phone'] = $userRoomBinds[$v['room_id']]['phone'];
                        }
                        $year_arr[$yearType]['buildingName'] = $userRoomBinds[$v['room_id']]['buildingName'];
                        $year_arr[$yearType]['unitName'] = $userRoomBinds[$v['room_id']]['unitName'];
                        $year_arr[$yearType]['room'] = $userRoomBinds[$v['room_id']]['room'];
                        $year_arr[$yearType]['address'] = $userRoomBinds[$v['room_id']]['address'];
                    }
                }
            }
        }
        $arr['orderCode'] = '1000';
        $arr['orderMsg'] = '用户欠费账单';
        if (empty($year_arr)) {
            $year_arr = [];
            $arr['orderCode'] = '1001';
            $arr['orderMsg'] = '当前用户无欠费账单';
        } else {
            $thirdAccessCheckService = new ThirdAccessCheckService();
            $year_arr = array_values($year_arr);
            foreach ($year_arr as $key=>$order) {
                $whereThird = [];
                $whereThird[] = ['thirdType','=',$thirdType];
                $whereThird[] = ['businessType','=',$businessType];
                $whereThird[] = ['checkId','=',$order['order_id']];
                $whereThird[] = ['status','=',1];
                $thirdInfo = $thirdAccessCheckService->getThirdConfigOne($whereThird,'third_id');
                if ($thirdInfo && isset($thirdInfo['third_id']) && $thirdInfo['third_id']) {
                    unset($year_arr[$key]['orderIds'],$year_arr[$key]['orderIdString'],$year_arr[$key]['moneys']);
                    continue;
                } else {
                    $thirdData = $order;
                    unset($thirdData['order_id'],$thirdData['total_money']);
                    if (isset($order['moneys']) && is_array($order['moneys']) && $order['total_money']) {
                        $keyLength = count($order['moneys']) - 1;
                        $moneyRatioTotal = 0;
                        $thirdData['moneyRatio'] = [];
                        foreach ($order['moneys'] as $keyMoney=>$money) {
                            if ($keyMoney==$keyLength) {
                                $thirdData['moneyRatio'][$keyMoney] = 1-$moneyRatioTotal;
                            } else {
                                $thirdData['moneyRatio'][$keyMoney] = formatNumber($money/$order['total_money'],4);
                            }
                        }
                    }
                    $thirdData = [
                        'checkId' => $order['order_id'],
                        'businessType' => $businessType,
                        'businessId' => $order['total_money'],
                        'thirdType' => $thirdType,
                        'thirdSite' => $thirdSite,
                        'thirdData' => json_encode($thirdData,JSON_UNESCAPED_UNICODE),
                        'addTime' => time(),
                    ];
                    $thirdAccessCheckService->addThirdConfig($thirdData);
                    unset($year_arr[$key]['orderIds'],$year_arr[$key]['orderIdString'],$year_arr[$key]['moneys']);
                }
            }
        }
        $arr['orders'] = $year_arr;
        return $arr;
    }

    public function payUserOrders($param) {
        $village_id = isset($param['village_id'])&&intval($param['village_id'])?intval($param['village_id']):0;
        $operation = isset($param['operation'])&&trim($param['operation'])?trim($param['operation']):'';
        $thirdType = isset($param['thirdType'])&&trim($param['thirdType'])?trim($param['thirdType']):'';
        $phone = isset($param['phone'])&&trim($param['phone'])?trim($param['phone']):'';
        $name = isset($param['name'])&&trim($param['name'])?trim($param['name']):'';
        $pigcms_id = isset($param['pigcms_id'])&&intval($param['pigcms_id'])?intval($param['pigcms_id']):'';

        /**order: 1001,1002,1003  注意是英文逗号拼接*/
        $orders = isset($param['orders'])&&$param['orders']?$param['orders']:'';
        /** orderDetail: [{orderId: cc9886b964d438b91e903c2205fff3d, payMoney: 100},{orderId: a637d9eacd5e8f3d6f651b4e6cd54a66, payMoney: 80},{orderId: c89d74639ca36990191d0a61adb69586, payMoney: 70}] */
        $orderDetail = isset($param['orderDetail'])&&$param['orderDetail']?$param['orderDetail']:'';
        $payType = isset($param['payType'])&&$param['payType']?$param['payType']:'';
        $payTime = isset($param['payTime'])&&$param['payTime']?$param['payTime']:time();
        if (!$orders) {
            throw new \Exception('请上传订单');
        }
        $orderArr = explode(",",$orders);
        if (empty($orderArr) || !$orderArr[0]) {
            throw new \Exception('请上传订单');
        }
        if (!$village_id) {
            throw new \Exception('请上传小区ID');
        }
        $thirdAccessCheckService = new ThirdAccessCheckService();
        $businessType = 'userNewOrders';
        $db_house_new_pay_order = new HouseNewPayOrder();
        $total_money = 0;
        $pay_money = 0;
        $payMoneyArr = [];
        if (!empty($orderDetail)) {
            foreach ($orderDetail as $item) {
                if (!in_array($item['orderId'], $orderArr)) {
                    throw new \Exception('对应订单和订单详情信息不对应');
                    break;
                }
                if (!isset($item['payMoney'])) {
                    throw new \Exception('缺少实际支付金额');
                    break;
                }
                $pay_money += $item['payMoney'];
                $payMoneyArr[$item['orderId']] = $item['payMoney'];
            }
        }

        $whereThird = [];
        $whereThird[] = ['thirdType','=',$thirdType];
        $whereThird[] = ['businessType','=',$businessType];
        $whereThird[] = ['checkId','in',$orderArr];
        $whereThird[] = ['status','=',1];
        $thirdList = $thirdAccessCheckService->getThirdConfigSome($whereThird);
        if (!empty($thirdList)) {
            $thirdList = $thirdList->toArray();
        }
        if (empty($thirdList)) {
            throw new \Exception('订单信息有误');
        }
        $orderIds = [];
        $orderMoneyPay = [];
        foreach ($thirdList as &$third) {
            if (isset($third['thirdData']) && $third['thirdData']) {
                $third['thirdData'] = json_decode($third['thirdData'],true);
                $orderIdItems = isset($third['thirdData']['orderIds']) && is_array($third['thirdData']['orderIds'])?$third['thirdData']['orderIds']:[];
                if (isset($payMoneyArr[$third['checkId']]) && isset($third['thirdData']['moneyRatio'])) {
                    $ratioLength = count($third['thirdData']['moneyRatio']);
                    $ratioTotal = 0;
                    foreach ($third['thirdData']['moneyRatio'] as $keyRatio=>$ratio) {
                        if ($keyRatio==$ratioLength) {
                            $orderMoneyPay[$keyRatio] = $payMoneyArr[$third['checkId']] - $ratioTotal;
                        } else {
                            $orderMoneyPay[$keyRatio] = formatNumber($payMoneyArr[$third['checkId']] * $ratio);
                        }
                    }
                }
                if (!empty($orderIdItems)) {
                    $orderIds = array_merge($orderIds,$orderIdItems);
                }
            }
        }
        $whereOrder = [];
        $whereOrder[] = ['order_id', 'in', $orderIds];
        $ordersObj = $db_house_new_pay_order->getOrder($whereOrder,'*','room_id desc,order_id DESC');
        $orders=array();
        if($ordersObj && !$ordersObj->isEmpty()){
            $orders=$ordersObj->toArray();
        }
        if(empty($orders)){
            throw new \Exception('对应订单不存在');
        }
        $frst_order=$orders['0'];
        if ($frst_order['is_paid']==1) {
            throw new \Exception('对应订单已经支付');
        }
        
        $service_house_village = new HouseVillageService();
        $service_house_new_property = new HouseNewPorpertyService();
        $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$village_id],'property_id');
        $is_new_version = $service_house_new_property->getTakeEffectTimeJudge($village_info['property_id']);
        if (!$is_new_version) {
            throw new \Exception('目前仅支持新版物业收费，请前往设置');
        }
        $pay_uid=0;
        $houseVillageUserBind=new HouseVillageUserBind();
        if($pigcms_id || $phone){
            $where = [];
            $where[] = ['village_id','=',$village_id];
            $where[] = ['type','in',[0,1,2,3]];
            $where[] = ['status','=',1];
            $where[] = ['vacancy_id','>',0];
            if ($pigcms_id) {
                $where[] = ['pigcms_id','=',$pigcms_id];
            } elseif ($phone) {
                $where[] = ['phone','=',$phone];
            }
            $listObj =$houseVillageUserBind->getOne($where,'*','type asc,uid desc');
            $pay_user_info='';
            if (!empty($listObj) && !$listObj->isEmpty()) {
                $pay_user_info = $listObj->toArray();
            }
            if($pay_user_info){
                $pay_uid = isset($pay_user_info['uid'])?$pay_user_info['uid']:0;
                if (!$name) {
                    $name = isset($pay_user_info['name'])?$pay_user_info['name']:'';
                }
                if (!$phone) {
                    $phone = isset($pay_user_info['phone'])?$pay_user_info['phone']:'';
                }
            }elseif ($phone) {
                $userService = new UserService();
                $buyer = $userService->getUser($phone, 'phone,uid,nickname');
                $pay_uid = isset($buyer['uid'])?$buyer['uid']:0;
                if (!$name) {
                    $name = isset($buyer['nickname'])?$buyer['nickname']:'';
                }
                if (!$phone) {
                    $phone = isset($buyer['phone'])?$buyer['phone']:'';
                }
            }
        }
        
        if(empty($phone) && empty($name)){
            $whereArr=array(['village_id','=',$frst_order['village_id']],['vacancy_id','=',$frst_order['room_id']]);
            $whereArr[] = ['type','in',['0,3']];
            $whereArr[]= ['status','=',1];
            $userBindObj=$houseVillageUserBind->getOne($whereArr,'*','uid desc,type asc');
            if($userBindObj && !$userBindObj->isEmpty()){
                $name =$userBindObj['name'];
                $phone =$userBindObj['phone'];
                $pay_uid =$userBindObj['uid'];
            }
        }
        
        $service_house_new_cashier = new HouseNewCashierService();
        $summaryData = [];
        $summaryData['village_id'] = $village_id;
        $summaryData['property_id'] = $village_info['property_id'];
        $summaryData['pigcms_id'] = $pigcms_id;
        $summaryData['pay_uid'] = $pay_uid;
        $summaryData['is_paid'] = 2;
        $summaryData['pay_bind_id'] = $pigcms_id;
        $summaryData['order_no'] =  build_real_orderid($summaryData['pay_uid']);
        $summaryData['pay_type'] = 4;
        $summaryData['remark'] = $thirdType.'支付';
        $summaryData['order_pay_type'] = $payType;
        $summaryData['online_pay_type'] = $payType;
        $summary_id = $service_house_new_cashier->addOrderSummary($summaryData);

        $service_new_pay = new NewPayService();
        $billOrder = [];
        foreach ($orders as $now_order) {

            $order_info = $now_order;
            $orderData['summary_id'] = $summary_id;
            $order_info['summary_id'] = $summary_id;
            $orderData['uid'] = $pay_uid;
            $order_info['uid'] = $pay_uid;
            $orderData['name'] = $name;
            $order_info['name'] = $name;
            $orderData['phone'] = $phone;
            $order_info['phone'] = $phone;
            $orderData['is_paid'] = 1;
            $order_info['is_paid'] = 1;
            $total_money += $now_order['total_money'];
            $orderData['late_payment_money'] = 0;
            $order_info['late_payment_money'] = 1;
            $orderData['late_payment_day'] = 0;
            $order_info['late_payment_day'] = 1;
            $orderData['pay_time'] = $payTime;
            $order_info['pay_time'] = $payTime;
            if (isset($now_order['modify_money'])&&floatval($now_order['modify_money'])>0) {
                $orderData['pay_money'] = $now_order['modify_money'];
            } else {
                $orderData['pay_money'] = $now_order['total_money'];
            }
            if (isset($orderMoneyPay[$now_order['order_id']]) && $orderMoneyPay[$now_order['order_id']]) {
                $orderData['pay_money'] = $orderMoneyPay[$now_order['order_id']];
            }
            $orderData['pay_amount_points'] = $orderData['pay_money'] * 100;
            $order_info['pay_money'] = $orderData['pay_money'];
            $order_info['pay_amount_points'] = $orderData['pay_amount_points'];

            $whereOder = [];
            $whereOder[] = ['order_id','=',$now_order['order_id']];
            $service_house_new_cashier->saveOrder($whereOder,$orderData);
            //社区对账.
            $orderData['score_used_count'] = 0;
            $orderData['score_can_get'] = 0;
            $order_info['score_used_count'] = 0;
            $order_info['score_can_get'] = 0;
            $tmp_order['payment_money'] = round_number($orderData['pay_money']/100,2);
            $tmp_order['score_deducte'] = 0;
            $tmp_order['balance_pay'] = 0;
            $tmp_order['order_type'] = 'village_new_pay';
            $tmp_order['desc'] = '社区缴费';
            $order_info['order_type'] = 'village_new_pay';
            $order_info['is_own'] = 1;
            if (!isset($order_info['balance_pay'])||!$order_info['balance_pay']) {
                $order_info['balance_pay'] = 0;
            }
            $order_info['money'] = $order_info['pay_money'];
            $billOrder[] = $order_info;
            // 处理服务周期问题
            $service_new_pay->setHouseNewOrderLog($now_order);
        }
        $order_no = (new PayService)->createOrderNo();
        if (empty($orderDetail)) {
            $pay_money = $total_money;
        }
        $plat_order = array(
            'business_type' => 'village_new_pay',
            'business_id' => $summary_id,
            'order_name' => $thirdType.'支付【'.$orders.'】',
            'uid' => $summaryData['pay_uid'],
            'total_money' => $pay_money,
            'wx_cheap' => 0,
            'pay_type'=>$payType,
            'add_time'=>time(),
            'pay_time'=>$payTime,
            'paid'=>1,
            'orderid'=>$order_no,
        );
        $service_plat_order = new PlatOrderService();
        $plat_id = $service_plat_order->addPlatOrder($plat_order);
        $summaryData = ['is_paid' => 1, 'pay_time' =>$payTime, 'total_money'=>$total_money,'pay_money'=>$pay_money];
        $service_house_new_cashier->saveOrderSummary(['summary_id'=>$summary_id],$summaryData);

        $pay_db = new PayOrderInfo;
        $data = [
            'business' 				=> 'village_new_pay',
            'business_order_id' 	=> $plat_id,
            'orderid' 				=> $order_no,
            'addtime' 				=> date('Y-m-d H:i:s'),
            'money' 				=> $pay_money*100,
            'pay_type' 				=> $payType,
            'env'					=> $thirdType,
            'channel' 				=>  '',
            'pay_config' 			=> [],
            'is_own'				=> 1,
            'paid' 					=> 1,
            'paid_money' 			=> $pay_money,
            'paid_time' 			=> $payTime,
            'paid_extra' 			=> '',
            'current_score_use'		=> 0,
            'current_score_deducte'	=> 0,
            'current_system_balance'=> 0,
            'current_merchant_balance'	=> 0,
            'current_merchant_give_balance'	=> 0,
            'current_qiye_balance'	=> 0,
        ];
        $insert = $pay_db->add($data);
        if ($insert && $plat_id && !empty($billOrder)) {
            foreach ($billOrder as &$item) {
                $item['plat_order_id'] = $plat_id;
                invoke_cms_model('SystemBill/bill_method',['type'=>5,'order_info'=>$item,'is_fenzhang'=>0,'is_new_charge'=>1]);
            }
        }
        return [
            'summary_id' => $summary_id,
            'plat_id' => $plat_id
        ];
    }

    public function billBankFile($param) {
        $village_id = isset($param['village_id'])&&intval($param['village_id'])?intval($param['village_id']):0;
        $operation = isset($param['operation'])&&trim($param['operation'])?trim($param['operation']):'';
        $thirdType = isset($param['thirdType'])&&trim($param['thirdType'])?trim($param['thirdType']):'';
        $thirdSite = isset($param['thirdSite'])&&trim($param['thirdSite'])?trim($param['thirdSite']):'';
        $startTime = isset($param['startTime'])&&trim($param['startTime'])?trim($param['startTime']):0;
        $sftpFilePath = isset($param['sftpFilePath'])&&trim($param['sftpFilePath'])?trim($param['sftpFilePath']):'';
        $billId = isset($param['billId'])&&trim($param['billId'])?trim($param['billId']):'';
        try {
            $sftp = new sftp();
            $sftpFileData = $sftp->downloadFile($thirdType, $sftpFilePath);
        } catch (\Exception $e) {
            fdump_api(['param'=>$param,'e'=>$e->getMessage(),'sftpFilePath'=>$sftpFilePath],'thirdAccessBillBankFileLog',1);
            $sftpFileData = [];
            $sftpFileData['sftpFilePath'] = $sftpFilePath;
        }
        if (isset($sftpFileData['filePath']) && $sftpFileData['filePath']) {
            $billPath = cfg('site_url') . $sftpFileData['filePath'];
        } else {
            $billPath = '';
        }
        $thirdAccessCheckService = new ThirdAccessCheckService();
        $businessType = 'billBankFile';
        $whereThird = [];
        $whereThird[] = ['thirdType','=',$thirdType];
        $whereThird[] = ['businessType','=',$businessType];
        $whereThird[] = ['checkId','=',$billId];
        $thirdInfo = $thirdAccessCheckService->getThirdConfigOne($whereThird,'third_id');
        if ($thirdInfo && isset($thirdInfo['third_id']) && $thirdInfo['third_id']) {
            $thirdData = [
                'checkId' => $billId,
                'businessType' => $businessType,
                'businessId' => $sftpFilePath,
                'thirdType' => $thirdType,
                'thirdSite' => $thirdSite,
                'thirdData' => json_encode($sftpFileData,JSON_UNESCAPED_UNICODE),
                'updateTime' => time(),
            ];
            $thirdAccessCheckService->saveThirdConfig($whereThird,$thirdData);
            $third_id = $thirdInfo['third_id'];
        } else {
            $thirdData = [
                'checkId' => $billId,
                'businessType' => $businessType,
                'businessId' => $sftpFilePath,
                'thirdType' => $thirdType,
                'thirdSite' => $thirdSite,
                'status' => 21,
                'thirdData' => json_encode($sftpFileData,JSON_UNESCAPED_UNICODE),
                'addTime' => time(),
            ];
            $third_id = $thirdAccessCheckService->addThirdConfig($thirdData);
        }
        $planParam = [];
        $planParam['thirdType'] = $thirdType;
        $planParam['businessType'] = $businessType;
        $planParam['third_id'] = $third_id;
        $this->addPlan($planParam);
        return [
            'third_id' => $third_id,
            'billPath' => $billPath,
        ];
    }


    public function addPlan($param) {
        $arr= array();
        $arr['param'] = serialize($param);
        $arr['plan_time'] = time();
        $arr['space_time'] = 0;
        $arr['add_time'] = time();
        $arr['file'] = 'orderBillBankFile';
        $arr['time_type'] = 1;
        if (isset($param['third_id'])&&$param['third_id']) {
            $arr['unique_id'] = 'orderBillBankFile_'.$param['third_id'];
        } else {
            $arr['unique_id'] = 'orderBillBankFile';
        }
        $arr['rand_number'] = mt_rand(1, max(cfg('sub_process_num'), 3));
        try {
            $id = (new ProcessSubPlan())->add($arr);
            fdump_api(['param'=>$param,'id'=>$id,'arr'=>$arr],'thirdAccessAddPlanLog',1);
        } catch (\Exception $e) {
            fdump_api(['param'=>$param,'e'=>$e->getMessage(),'arr'=>$arr],'thirdAccessAddPlanLog',1);
            $id = 0;
        }
        return $id;
    }

    public function orderBillBankFile($param) {
        $businessType = isset($param['businessType'])&&$param['businessType']?$param['businessType']:'billBankFile';
        $thirdType = isset($param['thirdType'])&&$param['thirdType']?$param['thirdType']:'aiHorse';
        $third_id = isset($param['third_id'])&&$param['third_id']?$param['third_id']:0;
        $whereThird = [];
        if ($third_id) {
            $whereThird[] = ['third_id','=',$third_id];
        } else {
            $whereThird[] = ['thirdType','=',$thirdType];
            $whereThird[] = ['businessType','=',$businessType];
            $whereThird[] = ['status','=',22];
        }
        $thirdAccessCheckService = new ThirdAccessCheckService();
        $thirdList = $thirdAccessCheckService->getThirdConfigSome($whereThird,true,'addTime ASC');
        if (!empty($thirdList)) {
            $thirdList = $thirdList->toArray();
            $file_handle = new FileHandle();
            $sftp = new sftp();
            foreach ($thirdList as $item) {
                $filePath = '';
                if (isset($item['thirdData'])&&$item['thirdData']) {
                    $item['thirdData'] = json_decode($item['thirdData'],true);
                    if (isset($item['thirdData']['filePath'])&&$item['thirdData']['filePath']) {
                        $filePath = $item['thirdData']['filePath'];
                        try {
                            if ($file_handle->check_open_oss()) {
                                $file_handle->download(replace_file_domain($filePath));
                            }
                        } catch (\Exception $e) {}
                    } elseif (isset($item['thirdData']['sftpFilePath'])&&$item['thirdData']['sftpFilePath']) {
                        try {
                            $sftpFileData = $sftp->downloadFile($thirdType, $item['thirdData']['sftpFilePath']);
                            if (isset($sftpFileData['filePath'])&&$sftpFileData['filePath']) {
                                $filePath = $sftpFileData['filePath'];
                            }
                        } catch (\Exception $e) {
                            fdump_api(['param'=>$param,'e'=>$e->getMessage(),'sftpFilePath'=>$item['thirdData']['sftpFilePath']],'thirdAccessOrderBillBankFileLog',1);
                        }
                    }
                    if ($filePath) {
                        $filename = app()->getRootPath().'..'.$filePath;
                        $handle = fopen($filename, "r");//读取二进制文件时，需要将第二个参数设置成'rb'
                        //通过filesize获得文件大小，将整个文件一下子读到一个字符串中
                        $contents = fread($handle, filesize ($filename));
                        fclose($handle);
                        $contents_arr = json_decode($contents, true);
                        foreach ($contents_arr as $content) {
                            $whereThird = [];
                            $whereThird[] = ['thirdType','=',$thirdType];
                            $whereThird[] = ['businessType','=',$businessType];
                            $whereThird[] = ['checkId','=',$content['order_id']];
                            $thirdInfo = $thirdAccessCheckService->getThirdConfigOne($whereThird,'third_id');
                            if ($thirdInfo && isset($thirdInfo['third_id']) && $thirdInfo['third_id']) {
                                $thirdData = [
                                    'checkData' => json_encode($content,JSON_UNESCAPED_UNICODE),
                                    'status' => 21,
                                    'updateTime' => time(),
                                ];
                                $thirdAccessCheckService->saveThirdConfig($whereThird,$thirdData);
                            }
                        }
                    }
                }
            }
        }
        return $thirdList;
    }


    public function billOrders($param) {
        $village_id = isset($param['village_id'])&&intval($param['village_id'])?intval($param['village_id']):0;
        $operation = isset($param['operation'])&&trim($param['operation'])?trim($param['operation']):'';
        $thirdType = isset($param['thirdType'])&&trim($param['thirdType'])?trim($param['thirdType']):'';
        $startTime = isset($param['startTime'])&&trim($param['startTime'])?trim($param['startTime']):0;
        $endTime = isset($param['endTime'])&&trim($param['endTime'])?trim($param['endTime']):0;
        $db_house_new_pay_order_summary = new HouseNewPayOrderSummary();
        $where_summary[] = ['is_paid', '=', 1];
        $where_summary[] = ['village_id', '=', $village_id];
        $where_summary[] = ['remark', '=', $thirdType.'支付'];
        if ($startTime) {
            $where_summary[] = ['pay_time', '>=', $startTime];
        }
        if ($endTime) {
            $where_summary[] = ['pay_time', '<=', $endTime];
        }
        if ($startTime && $endTime && $endTime<$startTime) {
            throw new \Exception('查询支付开始时间不能大于结束时间');
        }
        $summaryOrder = 'pay_time DESC, summary_id DESC';
        $summary_list = $db_house_new_pay_order_summary->getLists($where_summary, '*',0,0,$summaryOrder);
        if (!empty($summary_list)) {
            $summary_list = $summary_list->toArray();
        }
        if (empty($summary_list)) {
            throw new \Exception('无相关支付账单');
        }
        $fileName = date('Ymd').'支付订单';
        $msgTxt = '物业ID|小区ID|房间ID|账单ID|支付金额|支付者姓名|支付时间';
        $fileData = $this->addTxt($fileName, $msgTxt,$thirdType);
        $db_house_new_pay_order = new HouseNewPayOrder();
        $summaryIds = [];
        foreach ($summary_list as $item) {
            $summaryIds[] = $item['summary_id'];
        }
        $whereOder = [];
        $whereOder[] = ['summary_id', 'in', $summaryIds];
        $order = 'pay_time DESC, summary_id DESC';
        $nowOrders = $db_house_new_pay_order->getOrder($whereOder,'order_id,name,phone,room_id,village_id,property_id,pay_time,pay_money',$order);
        if (!empty($nowOrders)) {
            $nowOrders = $nowOrders->toArray();
        }
        if (empty($nowOrders)) {
            throw new \Exception('无相关支付账单');
        }
        foreach ($nowOrders as $order) {
            $payTime = date('Y-m-d H:i:s', $order['pay_time']);
            $orderTxt = "{$order['property_id']}|{$order['village_id']}|{$order['room_id']}|{$order['order_id']}|{$order['pay_money']}|{$order['name']}|{$payTime}";
            $this->addTxt($fileName, $orderTxt,$thirdType,true);
        }
        return [
            'fileUrl' => $fileData['fileUrl'],
            'nowOrders' => $nowOrders,
        ];
    }


    function addTxt($fileName='',$data='',$thirdType='aiHorse',$append=false)
    {
        $fileUrl = cfg('site_url') . '/api/'.$thirdType.'/' . date('Ymd') . '/' . $fileName . '.txt';
        $fileName = rtrim($_SERVER['DOCUMENT_ROOT'],'/') . '/api/'.$thirdType.'/' . date('Ymd') . '/' . $fileName . '.txt';
        if(is_array($data)){
            $data = json_encode($data,JSON_UNESCAPED_UNICODE);
        }
        $dirName = dirname($fileName);
        if(!file_exists($dirName)){
            mkdir($dirName, 0777, true);
        }
        $info = var_export( $data, true);
        if ($append) {
            file_put_contents($fileName, trim($info,"'") . PHP_EOL,FILE_APPEND);
        } else {
            file_put_contents($fileName, trim($info,"'") . PHP_EOL);
        }
        return [
            'fileUrl' => $fileUrl,
            'fileName' => $fileName
        ];
    }
}