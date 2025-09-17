<?php

namespace app\community\model\service;
use app\common\model\service\export\ExportService as BaseExportService;
use app\common\model\db\PaidOrderRecord;
use app\community\model\db\HouseNewPileWithdraw;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\db\PackageOrder;
use app\community\model\db\PackageRoomOrder;
use app\community\model\db\PackageRoomParentOrder;
use app\community\model\db\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use app\community\model\service\HouseNewChargeService;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseNewPilePayOrder;
use think\Exception;

class HousePaidOrderRecordService
{
    public function getPaidOrderRecordList($village_id = 0, $whereArr,$field ='*',$page=0, $limit=15,$is_export=false)
    {
        $db_paid_order_record = new PaidOrderRecord();
        $returnArr = array('lists' => array(), 'count' => 0, 'total_limit' => $limit);
        $paid_orders = $db_paid_order_record->getListData($whereArr,$field, 'id DESC',$page, $limit);
        $exportTitle=array();
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
        $service_house_village = new HouseVillageService();
        $houseNewPayOrder =new HouseNewPayOrder();
        if($is_export){
            $exportTitle[]='商户支付订单号';
            $exportTitle[]='业务订单编号';
            $exportTitle[]='第三方支付流水号';
            $exportTitle[]='应收费用';
            $exportTitle[]='实际缴费金额';
            $exportTitle[]='支付时间';
            $exportTitle[]='余额支付金额';
            $exportTitle[]='支付方式';
            $exportTitle[]='积分抵扣金额';
            $exportTitle[]='退款金额';
            $exportTitle[]='退款时间';
            $exportTitle[]='订单状态';
            $exportTitle[]='所属业务';
            $exportTitle[]='房间号/车位号';
            $exportTitle[]='缴费人';
            $exportTitle[]='电话';
        }
        $exportData=array();
        if ($paid_orders && !$paid_orders->isEmpty()) {
            $paid_orders = $paid_orders->toArray();
            $count = $db_paid_order_record->getCount($whereArr);
            $returnArr['count'] = $count;
            $houseNewChargeService=new HouseNewChargeService();
            $pay_type_arr = ['wechat' => '微信支付', 'weixin' => '微信支付','aihorsepay' => '通联支付','alipay' => '支付宝', 'unionpay' => '银联','hqpay_wx'=>'环球汇通微信支付','hqpay_al'=>'环球汇通支付宝支付','farmersbankpay'=>'仪征农商行支付','score_deducte'=>'积分抵扣','balance'=>'余额支付','offline'=>'线下支付','offline_scan'=>'扫码支付','scanpay'=>'扫码支付','allinpay'=>'通联支付'];
            $charge_type_arr=$houseNewChargeService->charge_type;
            foreach ($paid_orders as $kk => $vv) {
                $business_type_name=$vv['business_name'];
                if($vv['house_type'] && strpos($vv['house_type'],',')){
                    $house_type_name=array();
                    $house_type_arr=explode(',',$vv['house_type']);
                    foreach($house_type_arr as $tvv) {
                        if(isset($charge_type_arr[$tvv])){
                            $house_type_name[]=$charge_type_arr[$tvv];
                        }
                    }
                    $business_type_name=!empty($house_type_name) ? implode(',',$house_type_name):'';
                }elseif ($vv['house_type'] && isset($charge_type_arr[$vv['house_type']])){
                    $business_type_name=$charge_type_arr[$vv['house_type']];
                }
                $paid_orders[$kk]['business_type_name']=$business_type_name;
                $paid_orders[$kk]['pay_time_str']=date('Y-m-d H:i:s',$vv['pay_time']);
                $paid_orders[$kk]['pay_type_str']='';
                $pay_type=$vv['pay_type'] ? strtolower($vv['pay_type']):'';
                if($pay_type && isset($pay_type_arr[$pay_type])){
                    $paid_orders[$kk]['pay_type_str']=$pay_type_arr[$pay_type];
                }
                $paid_orders[$kk]['refund_time_str']='';
                if($vv['last_refund_time']>0){
                    $paid_orders[$kk]['refund_time_str']=date('Y-m-d H:i:s',$vv['last_refund_time']);
                }
                $paid_orders[$kk]['order_status_str']='已支付';
                if($vv['refund_status']==1){
                    $paid_orders[$kk]['order_status_str']='部分退款';
                }elseif($vv['refund_status']==2){
                    $paid_orders[$kk]['order_status_str']='已退款';
                }
                $paid_orders[$kk]['house_new_pay_order_list']=0;
                if($vv['table_name']=='house_new_pay_order_summary' && $vv['house_type']!='non_motor_vehicle'){
                    $paid_orders[$kk]['house_new_pay_order_list']=1;
                }
                if($is_export){
                    $tmpArr=array();
                    $tmpArr['pay_order_no']=$paid_orders[$kk]['pay_order_no'];
                    $tmpArr['order_no']=$paid_orders[$kk]['order_no'];
                    $tmpArr['third_transaction_no']=$paid_orders[$kk]['third_transaction_no'];
                    $tmpArr['order_money']=$paid_orders[$kk]['order_money'];
                    $tmpArr['pay_money']= $paid_orders[$kk]['pay_money'];
                    $tmpArr['pay_time']=$paid_orders[$kk]['pay_time_str'];
                    $tmpArr['balance_money']= $paid_orders[$kk]['balance_money'];
                    $tmpArr['pay_type']= $paid_orders[$kk]['pay_type_str'];
                    $tmpArr['score_money']= $paid_orders[$kk]['score_money'];
                    $tmpArr['refund_money']= $paid_orders[$kk]['refund_money'];
                    $tmpArr['refund_time']= $paid_orders[$kk]['refund_time_str'];
                    $tmpArr['order_status']= $paid_orders[$kk]['order_status_str'];
                    $tmpArr['business_type_name']= $paid_orders[$kk]['business_type_name'];
                    $address_name='';
                    $name=$paid_orders[$kk]['u_name'];
                    $phone=$paid_orders[$kk]['u_phone'];
                    if($vv['table_name']=='house_new_pay_order_summary'){
                        $extra_data=$vv['extra_data'] ? json_decode($vv['extra_data'],true):array();
                        if ($extra_data && $extra_data['car_position_id'] > 0) {
                            $address_name = $this->getCarParkingById($extra_data['car_position_id']);
                        } elseif ($vv['room_id'] > 0) {
                            $vacancy_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id' => $vv['room_id']], 'single_id,floor_id,layer_id,village_id');
                            if ($vacancy_info) {
                                $address_name = $service_house_village->getSingleFloorRoom($vacancy_info['single_id'], $vacancy_info['floor_id'], $vacancy_info['layer_id'], $vv['room_id'], $vacancy_info['village_id']);
                            }
                        }
                        if ($vv['order_type'] == 'month_type' && empty($vv['order_type_v'])) {
                            $address_name = '月租车缴费【' . $vv['order_type_v'] . '】';
                        } elseif ($vv['order_type'] == 'stored_type' && empty($vv['order_type_v'])) {
                            $address_name = '储值车缴费【' . $vv['order_type_v'] . '】';
                        } elseif ($vv['order_type'] == 'temporary_type' && empty($vv['order_type_v'])) {
                            $address_name = '临时车缴费【' . $vv['order_type_v'] . '】';
                        }
                    }
                    $tmpArr['address_name']= $address_name;
                    $tmpArr['name']= $name;
                    $tmpArr['phone']= $phone;
                    $exportData[]=$tmpArr;
                }
            }
            $returnArr['lists'] = $paid_orders;

        }
        if($is_export){
            $returnArr['lists'] = $exportData;
            $returnArr['export_title'] = $exportTitle;
        }
        unset($paid_orders);
        unset($exportData);
        return $returnArr;
    }
    public function getCarParkingById($position_id = 0, $village_id = 0)
    {
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        $park_number = '';
        if ($position_id < 1) {
            return $park_number;
        }
        $whereArr = array('pp.position_id' => $position_id);
        if ($village_id > 0) {
            $whereArr['pp.village_id'] = $village_id;
        }
        $position_num = $db_house_village_parking_position->getLists($whereArr, 'pp.position_num,pg.garage_num', 0);
        if (!empty($position_num) && !$position_num->isEmpty()) {
            $position_num = $position_num->toArray();
            if (!empty($position_num)) {
                $position_num1 = $position_num[0];
                if (empty($position_num1['garage_num'])) {
                    $position_num1['garage_num'] = '临时车库';
                }
                $park_number = $position_num1['garage_num'] . '-' . $position_num1['position_num'];
            }
        }
        return $park_number;
    }
    public function carElePileOrderPayRecord($order_id=0,$village_id=0,$pay_money=0,$is_withdraw=false){
        $paidOrderRecordDb=new PaidOrderRecord();
        $nowtime=time();
        if(!$is_withdraw){
            $whereTmpArr=array();
            $whereTmpArr[]=array('order_id','=',$order_id);
            $whereTmpArr[]=array('table_name','=','house_new_pile_pay_order');
            if($village_id>0){
                $whereTmpArr[]=array('village_id','=',$village_id);
            }
            $paidOrderRecordInfo = $paidOrderRecordDb->getOneData($whereTmpArr, 'id,update_time');
            if($paidOrderRecordInfo && !$paidOrderRecordInfo->isEmpty()){
                return true;
            }
            $houseNewPilePayOrder=new HouseNewPilePayOrder();
            $whereArr=array();
            $whereArr[]=array('id','=',$order_id);
            if($village_id>0){
                $whereArr[]=array('village_id','=',$village_id);
            }
            $pilePayOrder=$houseNewPilePayOrder->getFind($whereArr);
            if($pilePayOrder && !$pilePayOrder->isEmpty()){
                $service_user = new User();
                $pilePayOrder=$pilePayOrder->toArray();
                $save_paid_order_record=array('source_from'=>1);
                $save_paid_order_record['house_type']='car_pile_charge_order';
                $save_paid_order_record['business_type']='car_pile_charge_order';
                $save_paid_order_record['business_name']='汽车充电扣款';
                $save_paid_order_record['uid']=$pilePayOrder['uid'];
                $save_paid_order_record['order_id']=$order_id;
                $save_paid_order_record['order_no']=$pilePayOrder['order_no'];
                $user_info = $service_user->getOne(['uid' => $pilePayOrder['uid']]);
                if (!empty($user_info) && isset($user_info['nickname'])) {
                    $save_paid_order_record['u_name']=$user_info['nickname'] ? $user_info['nickname']:'';
                    $save_paid_order_record['u_phone']=$user_info['phone'] ? $user_info['phone']:'';
                }
                $save_paid_order_record['table_name']='house_new_pile_pay_order';
                $save_paid_order_record['pay_order_no']=$pilePayOrder['order_no'];
                $save_paid_order_record['third_transaction_no']=$pilePayOrder['order_serial'];
                $save_paid_order_record['order_money']=$pay_money;
                $save_paid_order_record['is_own']=1;
                $save_paid_order_record['pay_money']=0;
                $save_paid_order_record['pay_type']='balance';
                $save_paid_order_record['balance_money']=$pay_money;
                $save_paid_order_record['pay_type_from']='house_new_pile_user_money';
                $save_paid_order_record['is_online']=1;
                $save_paid_order_record['pay_time']=$nowtime;
                $save_paid_order_record['village_id']=$village_id;
                $extra_data=array();
                $extra_data['equipment_id']=$pilePayOrder['equipment_id'];
                $extra_data['continued_time']=$pilePayOrder['continued_time'];
                $extra_data['use_ele']=$pilePayOrder['use_ele'];
                $extra_data['end_time']=$pilePayOrder['end_time'];
                $extra_data['socket_no']=$pilePayOrder['socket_no'];
                $extra_data['type']=$pilePayOrder['type'];
                $extra_data['car_number']=$pilePayOrder['car_number'] ? $pilePayOrder['car_number'] :'';
                $save_paid_order_record['extra_data']=json_encode($extra_data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                $paidOrderRecordDb->addOneData($save_paid_order_record);
            }
        }else{
            $db_house_new_pile_withdraw=new HouseNewPileWithdraw();
            $whereArr=array();
            $whereArr[]=array('id','=',$order_id);
            if($village_id>0){
                $whereArr[]=array('village_id','=',$village_id);
            }
            $withdrawInfo=$db_house_new_pile_withdraw->getFind($whereArr);
            if($withdrawInfo && !$withdrawInfo->isEmpty()){
                $withdrawInfo=$withdrawInfo->toArray();
                $whereTmpArr=array();
                $whereTmpArr[]=array('order_id','=',$order_id);
                $whereTmpArr[]=array('table_name','=','house_new_pile_withdraw');
                if($village_id>0){
                    $whereTmpArr[]=array('village_id','=',$village_id);
                }
                $paidOrderRecordDb = new PaidOrderRecord();
                $paidOrderRecordInfo = $paidOrderRecordDb->getOneData($whereTmpArr, 'id,update_time');
                if($paidOrderRecordInfo && !$paidOrderRecordInfo->isEmpty()){
                    return true;
                }
                $pay_type='balance';
                $pay_type_from='';
                if($withdrawInfo['refundType']==1){
                    $pay_type='weixin';
                }elseif ($withdrawInfo['refundType']==2){
                    $pay_type_from='system';
                }
                $service_user = new User();
                $save_paid_order_record=array('source_from'=>1);
                $save_paid_order_record['house_type']='car_pile_withdraw_order';
                $save_paid_order_record['business_type']='car_pile_withdraw_order';
                $save_paid_order_record['business_name']='汽车充电提现';
                $save_paid_order_record['uid']=$withdrawInfo['uid'];
                $save_paid_order_record['order_id']=$order_id;
                $save_paid_order_record['order_no']='';
                $user_info = $service_user->getOne(['uid' => $withdrawInfo['uid']]);
                if (!empty($user_info) && isset($user_info['nickname'])) {
                    $save_paid_order_record['u_name']=$user_info['nickname'] ? $user_info['nickname']:'';
                    $save_paid_order_record['u_phone']=$user_info['phone'] ? $user_info['phone']:'';
                }
                $save_paid_order_record['table_name']='house_new_pile_withdraw';
                $save_paid_order_record['pay_order_no']='';
                $save_paid_order_record['third_transaction_no']='';
                if($withdrawInfo['extra_data']){
                    $extra_data=json_decode($withdrawInfo['extra_data'],true);
                    if($extra_data && isset($extra_data['third_id'])){
                        $save_paid_order_record['third_transaction_no']=$extra_data['third_id'];
                    }
                    if($extra_data && isset($extra_data['partner_trade_no'])){
                        $save_paid_order_record['pay_order_no']=$extra_data['partner_trade_no'];
                    }
                }
                $save_paid_order_record['order_money']=$pay_money;
                $save_paid_order_record['is_own']=1;
                $save_paid_order_record['pay_money']=0;
                $save_paid_order_record['pay_type']=$pay_type;
                $save_paid_order_record['pay_type_from']=$pay_type_from;
                if($pay_type=='balance'){
                    $save_paid_order_record['balance_money']=$pay_money;
                }else{
                    $save_paid_order_record['pay_money']=$pay_money;
                }
                $save_paid_order_record['pay_type_from']='house_new_pile_user_money';
                $save_paid_order_record['is_online']=1;
                $save_paid_order_record['pay_time']=$nowtime;
                $save_paid_order_record['village_id']=$village_id;
                if($withdrawInfo['extra_data']){
                    $save_paid_order_record['extra_data']=$withdrawInfo['extra_data'];
                }
                $paidOrderRecordDb->addOneData($save_paid_order_record);
                
            }
        }
        return true;
    }
    public function addPayPackageRoomOrderRecord($parent_id=0,$extra=array()){
        $dbPackageRoomOrder = new PackageRoomOrder();
        $whereArr= array('parent_id'=>$parent_id);
        $roomOrder=$dbPackageRoomOrder->getFind($whereArr);
        if($roomOrder && !$roomOrder->isEmpty()){
            $dbPackageRoomParentOrder = new PackageRoomParentOrder();
            $roomOrder=$roomOrder->toArray();
            $save_paid_order_record=array('source_from'=>1);
            $save_paid_order_record['house_type']='package_room_order';
            $save_paid_order_record['business_type']='package_room_order';
            $save_paid_order_record['business_name']='物业购买房间套餐';
            $save_paid_order_record['order_id']=$parent_id;
            $save_paid_order_record['order_no']=$roomOrder['order_no'];
            $save_paid_order_record['u_name']=$roomOrder['property_name'];
            $save_paid_order_record['u_phone']=$roomOrder['property_tel'];
            $save_paid_order_record['table_name']='package_room_order';
            $save_paid_order_record['pay_order_no']=$extra['paid_orderid'];
            $pay_order_info_id=0;
            if(isset($extra['pay_order_info_id']) && !empty($extra['pay_order_info_id'])){
                $save_paid_order_record['pay_order_info_id']=$extra['pay_order_info_id'];
                $pay_order_info_id=$extra['pay_order_info_id'];
            }
            $order_record_id=0;
            if(isset($extra['order_record_id'])){
                $order_record_id=$extra['order_record_id'];
            }
            if(isset($extra['paid_extra']) && !empty($extra['paid_extra'])){
                $save_paid_order_record['third_transaction_no']=$extra['paid_extra'];
            }
            $save_paid_order_record['order_money']=$extra['paid_money'];
            $whereTmpArr = array('order_id'=>$parent_id);
            $roomParentOrder=$dbPackageRoomParentOrder->getInfo($whereTmpArr);
            if($roomParentOrder && isset($roomParentOrder['pay_money'])){
                $save_paid_order_record['order_money']=$roomParentOrder['pay_money'];
            }
            $save_paid_order_record['pay_money']=$extra['paid_money'];
            $save_paid_order_record['pay_time']=time();
            $save_paid_order_record['property_id']=$roomOrder['property_id'];
            $extra['package_order_id']=$roomOrder['package_order_id'];
            $save_paid_order_record['extra_data']=json_encode($extra,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            $whereArr=array();
            if ($pay_order_info_id > 0) {
                $whereArr[] = array('pay_order_info_id', '=', $pay_order_info_id);
            }else if($order_record_id>0){
                $whereArr[] = array('id', '=', $order_record_id);
            }else{
                $whereArr[]=array('property_id','=',$roomOrder['property_id']);
                $whereArr[]=array('source_from','=',1);
                $whereArr[]=array('order_id','=',$save_paid_order_record['order_id']);
                $whereArr[]=array('order_no','=',$save_paid_order_record['order_no']);
                $whereArr[]=array('table_name','=','package_room_order');
            }
            $paidOrderRecordDb = new PaidOrderRecord();
            $paidOrderRecordInfo = $paidOrderRecordDb->getOneData($whereArr, 'id,update_time');
            if ($paidOrderRecordInfo && !$paidOrderRecordInfo->isEmpty()) {
                $paidOrderRecordDb->saveOneData(array(['id', '=', $paidOrderRecordInfo['id']]), $save_paid_order_record);
            } else {
                if(isset($extra['paid_type']) && !empty($extra['paid_type'])){
                    $save_paid_order_record['pay_type']=$extra['paid_type'];
                }
                $paidOrderRecordDb->addOneData($save_paid_order_record);
            }
        }
        return true;
    }

    public function addPayPackageOrderRecord($order_id=0,$extra=array()){
        $dbPackageOrder = new PackageOrder();
        $whereArr= array('order_id'=>$order_id);
        $packageOrder=$dbPackageOrder->getFind($whereArr);
        if($packageOrder && !$packageOrder->isEmpty()){
            $packageOrder=$packageOrder->toArray();
            $save_paid_order_record=array('source_from'=>1);
            $save_paid_order_record['house_type']='property_package_order';
            $save_paid_order_record['business_type']='property_package_order';
            $save_paid_order_record['business_name']='物业购买功能套餐';
            $save_paid_order_record['order_id']=$order_id;
            $save_paid_order_record['order_no']=$packageOrder['order_no'];
            $save_paid_order_record['u_name']=$packageOrder['property_name'];
            $save_paid_order_record['u_phone']=$packageOrder['property_tel'];
            $save_paid_order_record['table_name']='package_order';
            $save_paid_order_record['pay_order_no']=$extra['paid_orderid'];
            $pay_order_info_id=0;
            if(isset($extra['pay_order_info_id']) && !empty($extra['pay_order_info_id'])){
                $save_paid_order_record['pay_order_info_id']=$extra['pay_order_info_id'];
                $pay_order_info_id=$extra['pay_order_info_id'];
            }
            $order_record_id=0;
            if(isset($extra['order_record_id'])){
                $order_record_id=$extra['order_record_id'];
            }
            if(isset($extra['paid_extra']) && !empty($extra['paid_extra'])){
                $save_paid_order_record['third_transaction_no']=$extra['paid_extra'];
            }
            $save_paid_order_record['order_money']=$packageOrder['paid_money'];
            $save_paid_order_record['pay_money']=$extra['paid_money'];
            $save_paid_order_record['pay_time']=time();
            $save_paid_order_record['property_id']=$packageOrder['property_id'];
            $extra['package_id']=$packageOrder['package_id'];
            $save_paid_order_record['extra_data']=json_encode($extra,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            $whereArr=array();
            if ($pay_order_info_id > 0) {
                $whereArr[] = array('pay_order_info_id', '=', $pay_order_info_id);
            }else if($order_record_id>0){
                $whereArr[] = array('id', '=', $order_record_id);
            }else{
                $whereArr[]=array('property_id','=',$packageOrder['property_id']);
                $whereArr[]=array('source_from','=',1);
                $whereArr[]=array('order_id','=',$save_paid_order_record['order_id']);
                $whereArr[]=array('order_no','=',$save_paid_order_record['order_no']);
                $whereArr[]=array('table_name','=','package_order');
            }
            $paidOrderRecordDb = new PaidOrderRecord();
            $paidOrderRecordInfo = $paidOrderRecordDb->getOneData($whereArr, 'id,update_time');
            if ($paidOrderRecordInfo && !$paidOrderRecordInfo->isEmpty()) {
                $paidOrderRecordDb->saveOneData(array(['id', '=', $paidOrderRecordInfo['id']]), $save_paid_order_record);
            } else {
                if(isset($extra['paid_type']) && !empty($extra['paid_type'])){
                    $save_paid_order_record['pay_type']=$extra['paid_type'];
                }
                $paidOrderRecordDb->addOneData($save_paid_order_record);
            }
        }
        return true;
    }
    public function addHouseMeterUserPayorderRecord($order_info=array(),$extra=array()){
        $pay_order_info_id=0;
        if(isset($extra['pay_order_info_id']) && !empty($extra['pay_order_info_id'])){
            $pay_order_info_id=$extra['pay_order_info_id'];
        }
        $order_record_id=0;
        if(isset($extra['order_record_id'])){
            $order_record_id=$extra['order_record_id'];
        }
        if(!empty($order_info)&& isset($order_info['uid']) && ($pay_order_info_id>0 || $order_record_id>0)){
            $save_paid_order_record=array('source_from'=>1);
            $save_paid_order_record['house_type']='house_meter';
            $save_paid_order_record['business_type']='house_meter';
            $save_paid_order_record['business_name']='水电燃缴费';
            $save_paid_order_record['table_name']='house_meter_user_payorder';
            $save_paid_order_record['order_money']=$order_info['charge_price'];
            $save_paid_order_record['uid']=$order_info['uid'];
            $save_paid_order_record['order_id']=$order_info['id'];
            $save_paid_order_record['order_no']=$order_info['order_no'];
            $save_paid_order_record['u_phone']=$order_info['phone'];
            $save_paid_order_record['village_id']=$order_info['village_id'];
            $save_paid_order_record['room_id']=$order_info['vacancy_id'];
            $extra['electric_id']=$order_info['electric_id'];
            $extra['payment_num']=$order_info['payment_num'];
            $save_paid_order_record['extra_data']=json_encode($extra,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            $paidOrderRecordDb = new PaidOrderRecord();
            $whereArr=array();
            if ($pay_order_info_id > 0) {
                $whereArr[] = array('pay_order_info_id', '=', $pay_order_info_id);
            }else if($order_record_id>0){
                $whereArr[] = array('id', '=', $order_record_id);
            }
            $paidOrderRecordInfo = $paidOrderRecordDb->getOneData($whereArr, 'id,update_time');
            if ($paidOrderRecordInfo && !$paidOrderRecordInfo->isEmpty()) {
                $paidOrderRecordDb->saveOneData(array(['id', '=', $paidOrderRecordInfo['id']]), $save_paid_order_record);
            }
        }
        return true;
    }
    public function addHouseVillagePayOrderRecord($order_info=array(),$extra=array()){

        $pay_order_info_id=0;
        if(isset($extra['pay_order_info_id']) && !empty($extra['pay_order_info_id'])){
            $pay_order_info_id=$extra['pay_order_info_id'];
        }
        $order_record_id=0;
        if(isset($extra['order_record_id'])){
            $order_record_id=$extra['order_record_id'];
        }
        if(!empty($order_info)&& isset($order_info['order_id']) && ($pay_order_info_id>0 || $order_record_id>0)){
            $save_paid_order_record=array('source_from'=>1);
            $save_paid_order_record['house_type']='village_pay_order';
            $save_paid_order_record['business_type']='village_pay_order';
            $save_paid_order_record['business_name']='老版小区缴费';
            $save_paid_order_record['table_name']='house_village_pay_order';
            $save_paid_order_record['order_money']=$order_info['money'];
            $save_paid_order_record['uid']=$order_info['uid'] ? $order_info['uid']:$order_info['pay_uid'];
            $save_paid_order_record['order_id']=$order_info['order_id'];
            $save_paid_order_record['order_no']=$order_info['order_no'] ? $order_info['order_no']:'';
            $service_user = new User();
            $user_info = $service_user->getOne(['uid' => $save_paid_order_record['uid']]);
            if (!empty($user_info) && isset($user_info['nickname'])) {
                $save_paid_order_record['u_name']=$user_info['nickname'] ? $user_info['nickname']:'';
                $save_paid_order_record['u_phone']=$user_info['phone'] ? $user_info['phone']:'';
            }
            $save_paid_order_record['is_own']=$order_info['is_own'] ? $order_info['is_own']:0;
            $save_paid_order_record['bind_user_id']=$order_info['bind_id'] ? $order_info['bind_id']:$order_info['pay_bind_id'];
            $save_paid_order_record['village_id']=$order_info['village_id'];
            $save_paid_order_record['order_type']=$order_info['order_type'];
            $save_paid_order_record['order_type_v']=$order_info['order_name'];
            $extra['cashier_id']=$order_info['cashier_id'];
            $extra['payment_bind_id']=$order_info['payment_bind_id'] ? $order_info['payment_bind_id']:0;
            $extra['car_number']=$order_info['car_number'] ? $order_info['car_number']:'';
            $extra['car_type']=$order_info['car_type'] ? $order_info['car_type']:'';
            $extra['car_id']=$order_info['car_id'] ? $order_info['car_id']:'';
            $save_paid_order_record['extra_data']=json_encode($extra,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            $paidOrderRecordDb = new PaidOrderRecord();
            $whereArr=array();
            if ($pay_order_info_id > 0) {
                $whereArr[] = array('pay_order_info_id', '=', $pay_order_info_id);
            }else if($order_record_id>0){
                $whereArr[] = array('id', '=', $order_record_id);
            }
            $paidOrderRecordInfo = $paidOrderRecordDb->getOneData($whereArr, 'id,update_time');
            if ($paidOrderRecordInfo && !$paidOrderRecordInfo->isEmpty()) {
                $paidOrderRecordDb->saveOneData(array(['id', '=', $paidOrderRecordInfo['id']]), $save_paid_order_record);
            }
        }
        return true;
    }
    public function getBusinessTypeInfo(){
        $houseNewChargeService=new HouseNewChargeService();
        $newBusinessType=array(['key' => 'car_pile_charge_order','value' => '汽车充电扣款']);
        $newBusinessType[]=array('key' => 'car_pile_withdraw_order','value' => '汽车充电提现');
        $newBusinessType[]=array('key' => 'village_pile_pay','value' => '充电桩充电支付');
        $newBusinessType[]=array('key' => 'house_express_send','value' => '快递代发');
        $newBusinessType[]=array('key' => 'package_room_order','value' => '物业购买房间套餐');
        $newBusinessType[]=array('key' => 'property_package_order','value' => '物业购买功能套餐');
        $newBusinessType[]=array('key' => 'village_pay_order','value' => '老版小区缴费');
        $newBusinessType[]=array('key' => 'user_recharge_order','value' => '用户余额充值');
        $newBusinessType[]=array('key' => 'sms_buy_order','value' => '小区购买短信');
        $newBusinessType[]=array('key' => 'village_recharge_order','value' => '小区余额充值');
        $charge_type_arr=$houseNewChargeService->charge_type_arr;
        $charge_type_arr=array_merge($charge_type_arr,$newBusinessType);
        $businessTypeInfo=array('lists'=>$charge_type_arr);
        return  $businessTypeInfo;
    }
    /**
     * 导出文件
     * @author:zhubaodi
     * @date_time: 2021/5/21 14:44
     */
    public function saveExcel($title, $data, $fileName, $tips = '', $exporttype = '')
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getDefaultColumnDimension()->setWidth(24);
        $sheet->getDefaultRowDimension()->setRowHeight(22);//设置行高
        $sheet->getRowDimension('1')->setRowHeight(25);//设置行高
        $mergeCellCoordinate = 'A1:Q1';
        if ($exporttype == 'feeRate') {
            $mergeCellCoordinate = 'A1:H1';
        }
        $sheet->getStyle($mergeCellCoordinate)->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells($mergeCellCoordinate); //合并单元格
        $sheet->getColumnDimension('A')->setWidth(32);
        $sheet->setCellValue('A1', $tips);

        //设置单元格内容
        $titCol = 'A';
        foreach ($title as $key => $value) {
            //单元格内容写入
            $sheet->setCellValue($titCol . '2', $value);
            $sheet->getStyle('A2:Ak2')->getFont()->setBold(true);
            $titCol++;
        }
        //设置单元格内容
        $row = 3;
        foreach ($data['list'] as $k => $item) {
            $dataCol = 'A';
            foreach ($item as $value) {
                //单元格内容写入
                $sheet->setCellValue($dataCol . $row, $value);
                $dataCol++;
            }
            $row++;
        }

        if ($exporttype == 'feeRate') {
            $mergeCellCoordinate = 'A' . $row . ':C' . $row;
            $sheet->mergeCells($mergeCellCoordinate); //合并单元格
            $sheet->setCellValue('A' . $row, '合计=>');
            $sheet->setCellValue('E' . $row, $data['all_modify_money']);
            $sheet->setCellValue('F' . $row, $data['all_pay_money']);
            $no_pay = $data['all_modify_money'] - $data['all_pay_money'];
            $no_pay = round($no_pay, 2);
            $sheet->setCellValue('G' . $row, $no_pay);
            $rate = $data['all_pay_money'] / $data['all_modify_money'];
            $rate = round($rate, 2);
            $sheet->setCellValue('H' . $row, $rate);
            $row++;
        }

        //保存
        $styleArrayBody = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        $total_rows = $row + 1;
        //添加所有边框/居中

        if ($exporttype == 'feeRate') {
            $sheet->getStyle('A1:H' . $total_rows)->applyFromArray($styleArrayBody);
        } else {
            $sheet->getStyle('A1:AA' . $total_rows)->applyFromArray($styleArrayBody);
        }
        //下载
        $filename = $fileName . '.xlsx';
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet);
        return $this->downloadExportFile($filename);
    }

    /**
     * 下载表格
     */
    public function downloadExportFile($param)
    {
        $returnArr = [];
        if (!file_exists(request()->server('DOCUMENT_ROOT') . '/v20/runtime/' . $param)) {
            $returnArr['error'] = 1;
            return $returnArr;
        }
        $filename = $param;

        $ua = request()->server('HTTP_USER_AGENT');
        $ua = strtolower($ua);
        if (preg_match('/msie/', $ua) || preg_match('/edge/', $ua) || preg_match('/trident/', $ua)) { //判断是否为IE或Edge浏览器
            $filename = str_replace('+', '%20', urlencode($filename)); //使用urlencode对文件名进行重新编码
        }
        $returnArr['url'] = cfg('site_url') . '/v20/runtime/' . $param;
        $returnArr['error'] = 0;

        return $returnArr;
    }
}