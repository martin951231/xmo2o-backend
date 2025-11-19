<?php
namespace app\community\model\service;

use app\community\model\db\HouseVillagePayOrder;
use app\community\model\db\PropertyBill;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseProperty;
use app\community\model\db\HouseVillageInfo;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\HouseVillageParkingPosition;
//硬件
use app\community\model\db\HouseCameraDevice;
use app\community\model\db\HouseFaceDevice;
use app\community\model\db\HouseSmartCharging;
use app\community\model\db\HouseVillageDoor;
use app\community\model\db\houseVillageParkConfig;
use app\community\model\db\HouseVillageParkGarageConfig;
use app\community\model\db\IntelligenceExpress;
use app\community\model\db\ParkPassage;
//人流量
use app\community\model\db\InPark;
use app\community\model\db\HouseVillageParkingCar;
use app\community\model\db\Area;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageVisitor;
use app\community\model\db\HouseVillageRepairList;

class CommunityStatisticsService
{
    //首页
    public function getData($property_id,$login_name='')
    {
        $dbHouseProperty = new HouseProperty();
        $dbHouseVillage = new HouseVillage();
        $dbHouseVillageInfo = new HouseVillageInfo();
        $dbHouseVillageUserVacancy = new HouseVillageUserVacancy();
        $dbHouseVillageSingle = new HouseVillageSingle();
        $dbHouseVillageParkingPosition = new HouseVillageParkingPosition();
        $service_login = new ManageAppLoginService();
//        $service_house_worker = new HouseWorkerService();
        $service_house_village = new HouseVillageService();
        if ($property_id) {
            $property_info = $service_house_village->get_house_property($property_id,'property_name');
            $village_name = $property_info['property_name'];
        }
        if ($login_name) {
            $login_name = '，'.$login_name;
        }
        $prompt = $service_login->time_tip(0,$login_name,$village_name);
        $where[] = ['id','=',$property_id];
        $field = 'id,property_name';
        $property_info = $dbHouseProperty->get_one($where,$field);//物业信息

        $num_where[] = ['property_id','=',$property_id];
        $num_where[] = ['status','=',1];
        $group='city_id';
        $stay_num = $dbHouseVillage->getNum($num_where,$group);//入住城市
        $village_num = $dbHouseVillage->getNum($num_where);//项目数量

        $building_where[] = ['property_id','=',$property_id];
        $building_where[] = ['building_area','>',0];
        $building_area = $dbHouseVillageInfo->getNum($building_where,'building_area');
        $not_where[] = ['property_id','=',$property_id];
        $not_where[] = ['building_area','<=',0];
        $info_village_id = $dbHouseVillageInfo->getColumn($not_where,'village_id');
        $vacancy[] = ['village_id','in',$info_village_id];
        $vacancy[] = ['is_del','=',0];
        $vacancy[] = ['status','=',1];
        $house_size = $dbHouseVillageUserVacancy->getSum($vacancy,'housesize');
        $house_area = $building_area+$house_size;//覆盖面积

        $village_where[] = ['property_id','=',$property_id];
        $village_where[] = ['status','=',1];
        $village_id_arr = $dbHouseVillage->getColumn($village_where,'village_id');
        $map[] = ['village_id','in',$village_id_arr];
        $map[] = ['status','=',1];
        $single_num = $dbHouseVillageUserVacancy->getCount($map);//楼栋数量
        $residents_num = $dbHouseVillageSingle->getCount($map);//住户数量

        $packing_where[] = ['village_id','in',$village_id_arr];
        $packing_where[] = ['position_pattern','=',1];
        $parking_count = $dbHouseVillageParkingPosition->get_village_park_position_num($packing_where);//车位数量

        $list=[
            ['title'=>'入住城市','num'=>$stay_num],
            ['title'=>'项目数量','num'=>$village_num],
            ['title'=>'覆盖面积','num'=>$house_area],
            ['title'=>'楼栋数量','num'=>$single_num],
            ['title'=>'住户数量','num'=>$residents_num],
            ['title'=>'车位数量','num'=>$parking_count],
        ];
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $http = $http_type.$_SERVER['SERVER_NAME'].'/v20/public/static/community/images/statistics_column/';
        $column_list=[
            ['ioc'=>$http.'cost.png','title'=>'费用统计','url'=>''],
            ['ioc'=>$http.'hardware.png','title'=>'硬件统计','url'=>''],
            ['ioc'=>$http.'people.png','title'=>'人流量统计','url'=>''],
            ['ioc'=>$http.'parking.png','title'=>'车场统计','url'=>''],
            ['ioc'=>$http.'work_order.png','title'=>'工单统计','url'=>''],
        ];
        if (!cfg('ComingSoonHide')) {
            $column_list[] = ['ioc'=>$http.'other.png','title'=>'敬请期待','url'=>''];
        }
        $data['prompt'] = $prompt;
        $data['list'] = $list;
        $data['column_list'] = $column_list;
        $data['property'] = $property_info;
        return $data;
    }
    /**
     * Notes: 收入统计
     * @param $village_id
     * @param $date
     * @param $type
     * @param $property_id
     * @return mixed
     * @author: weili
     * @datetime: 2020/10/13 13:33
     */
    public function costStatistics($village_id,$date,$type,$property_id)
    {
        $dbHouseVillagePayOrder = new HouseVillagePayOrder();
        $dbPropertyBill = new PropertyBill();
        $dbHouseProperty = new HouseProperty();
        if($village_id){
            $where[] = ['village_id','=',$village_id];
        }
        if(!$date){
            $date = date('Y-m',time());
        }
        if ($type == 2)//按年
        {
            //总收入/支出
            $time = strtotime($date);
            $start_time = strtotime(date($date.'-01-01'));
            $end_time = date('Y-m-t 23:59:59',$start_time);
            $end_time = strtotime("$end_time +11 month");
            //一年内收入
            $line_list = $this->getMonth($start_time,$property_id,1);
        }else{//按月
            //总收入/支出
            $current_time = $date.'-1';
            $end_time = $current_time.' 23:59:59';
            $start_time = strtotime($current_time);
            $end_time = strtotime("$end_time +1 month -1 day");
            //一个月内收入
            $line_list = $this->getDays($start_time,$property_id,1);
        }
        //收入
        $where[] = ['order_status','=',1];
        $where[] = ['pay_time','>=',$start_time];
        $where[] = ['pay_time','<=',$end_time];
        //支出
        $map[] = ['bill_type','=',1];
        $map[] = ['payment_time','>=',$start_time];
        $map[] = ['payment_time','<=',$end_time];
        $map[] = ['property_id','=',$property_id];

        $total_money = $dbHouseVillagePayOrder->sumMoney($where,[],'money');
        $total_price = $dbPropertyBill->sumMoney($map,'price');
        //收入分类
        $where_custom[] = ['order_type','=','custom'];
        $custom = $dbHouseVillagePayOrder->sumMoney($where,$where_custom,'money');
        $where_water[] = ['order_type','=','water'];
        $water = $dbHouseVillagePayOrder->sumMoney($where,$where_water,'money');
        $where_property[] = ['order_type','=','property'];
        $property = $dbHouseVillagePayOrder->sumMoney($where,$where_property,'money');
        $where_electric[] = ['order_type','=','electric'];
        $electric = $dbHouseVillagePayOrder->sumMoney($where,$where_electric,'money');
        $where_park[] = ['order_type','=','park'];
        $park = $dbHouseVillagePayOrder->sumMoney($where,$where_park,'money');
        $where_gas[] = ['order_type','=','gas'];
        $gas = $dbHouseVillagePayOrder->sumMoney($where,$where_gas,'money');
        $where_custom_payment[] = ['order_type','=','custom_payment'];
        $custom_payment = $dbHouseVillagePayOrder->sumMoney($where,$where_custom_payment,'money');
        $ring_list = [
            ['price'=>sprintf("%.2f",$water),'name'=>'水费','color' => '#ff5e2d'],//水费
            ['price'=>sprintf("%.2f",$property),'name'=>'物业费','color' => '#458bfa'],//物业费
            ['price'=>sprintf("%.2f",$park),'name'=>'停车费','color' => '#5bddbf'],//停车费
            ['price'=>sprintf("%.2f",$electric),'name'=>'电费','color' => '#ffd100'],//电费
            ['price'=>sprintf("%.2f",$gas),'name'=>'燃气费','color' => '#813cf9'],//燃气费
            ['price'=>sprintf("%.2f",$custom),'name'=>'临时费用','color' => '#d645fa'],//自主缴费 《临时费用》
            ['price'=>sprintf("%.2f",$custom_payment),'name'=>'自定义缴费','color' => '#ff814d'],//自定义缴费
        ];
        $property_where[] = ['id','=',$property_id];
        $field = 'id,property_name';
        $property_info = $dbHouseProperty->get_one($property_where,$field);//物业信息

        $data['property'] = $property_info;
        $data['total_money'] = sprintf("%.2f", $total_money);//总收入
        $data['total_price'] = sprintf("%.2f", $total_price);//总支出
        $data['line_list'] = $line_list;//折线统计
        $data['ring_list'] = $ring_list;//圆环统计
        return $data;
    }
    /**
     * Notes:一月内
     * @param $time
     * @param $property_id
     * @param $type
     * @return array
     * @author: weili
     * @datetime: 2020/10/13 13:33
     */
    public function getDays($time,$property_id,$type)
    {

        $dbHouseVillagePayOrder = new HouseVillagePayOrder();
        $dbPropertyBill = new PropertyBill();
        $s_time = date('Y-m-d',$time);
        $e_time = date('Y-m-d 23:59:59',$time);
        $days= date("t",$time);
        $line_list=[];
        for ($i=0;$i<$days;$i++){
            $time_data[] = [
                'start_time'=>date('Y-m-d H:i:s',strtotime("$s_time +$i day")),
                'end_time'=>date('Y-m-d H:i:s',strtotime("$e_time +$i day")),
            ];
            $start_time=strtotime("$s_time +$i day");
            $end_time=strtotime("$e_time +$i day");
            $where = [];
            if($type == 1) {//收入
                $where[] = ['order_status', '=', 1];
                $where[] = ['pay_time', '>=', $start_time];
                $where[] = ['pay_time', '<=', $end_time];
                $money = sprintf("%.2f", $dbHouseVillagePayOrder->sumMoney($where, [], 'money'));
            }else{//支出
                $map=[];
                $map[] = ['bill_type','=',1];
                $map[] = ['payment_time','>=',$start_time];
                $map[] = ['payment_time','<=',$end_time];
                $map[] = ['property_id','=',$property_id];
                $money = sprintf("%.2f", $dbPropertyBill->sumMoney($map,'price'));
            }
            $line_list[] = [
                'money'=>$money,
                'date'=>date('Y-m-d',strtotime("$s_time +$i day"))
             ];
        }
        return $line_list;
    }
    /**
     * Notes:一年内
     * @param $time
     * @param $property_id
     * @param $type
     * @return array
     * @author: weili
     * @datetime: 2020/10/13 13:32
     */
    public function getMonth($time,$property_id,$type)
    {
        $dbHouseVillagePayOrder = new HouseVillagePayOrder();
        $dbPropertyBill = new PropertyBill();
        $s_time = date('Y-m-01',$time);
        $line_list=[];
        for ($i=0;$i<12;$i++){
            $days_data[] = [
                'start_time' => date('Y-m-d H:i:s',strtotime("$s_time +$i month")),
                'end_time'=> date('Y-m-t 23:59:59', strtotime("$s_time +$i month")),
            ];
            $start_time=strtotime("$s_time +$i month");
            $end_time=strtotime(date('Y-m-t 23:59:59', strtotime("$s_time +$i month")));
            $where = [];
            if($type == 1) {//收入
                $service_house_village = new HouseVillageService();
                $village_lists = $service_house_village->getList(['property_id'=>$property_id],'village_id')->toArray();
                $villages = '';
                foreach ($village_lists as $v){
                    $villages .= $v['village_id'].',';
                }
                $villages = rtrim($villages,',');
                $where[] = ['order_status', '=', 1];
                $where[] = ['pay_time', '>=', $start_time];
                $where[] = ['pay_time', '<=', $end_time];
                $where[] = ['village_id', 'in', $villages];
                $money = sprintf("%.2f", $dbHouseVillagePayOrder->sumMoney($where, [], 'money'));
            }else{//支出
                $map=[];
                $map[] = ['bill_type','=',1];
                $map[] = ['payment_time','>=',$start_time];
                $map[] = ['payment_time','<=',$end_time];
                $map[] = ['property_id','=',$property_id];
                $money = sprintf("%.2f", $dbPropertyBill->sumMoney($map,'price'));
            }
            $line_list[] = [
                'money' => $money,
                'date' => date('Y-m', strtotime("$s_time +$i month"))
            ];

        }
        return $line_list;
    }
    /**
     * Notes: 支出统计
     * @param $village_id
     * @param $date
     * @param $type
     * @param $property_id
     * @return mixed
     * @author: weili
     * @datetime: 2020/10/13 13:32
     */
    public function spendStatistics($village_id,$date,$type,$property_id)
    {
        $dbHouseVillagePayOrder = new HouseVillagePayOrder();
        $dbPropertyBill = new PropertyBill();
        $dbHouseProperty = new HouseProperty();
        if($village_id){
            $where[] = ['village_id','=',$village_id];
        }
        if(!$date){
            $date = date('Y-m',time());
        }
        if ($type == 2)//按年
        {
            //总收入/支出
            $time = strtotime($date);
            $start_time = strtotime(date($date.'-01-01'));
            $end_time = date('Y-m-t 23:59:59',$start_time);
            $end_time = strtotime("$end_time +11 month");
            //一年内收入
            $line_list = $this->getMonth($start_time,$property_id,2);// 2=>支出
        }else{//按月
            //总收入/支出
            $current_time = $date.'-1';
            $end_time = $current_time.' 23:59:59';
            $start_time = strtotime($current_time);
            $end_time = strtotime("$end_time +1 month -1 day");
            //一个月内收入
            $line_list = $this->getDays($start_time,$property_id,2);// 2=>支出
        }
        //收入
        $where[] = ['order_status','=',1];
        $where[] = ['pay_time','>=',$start_time];
        $where[] = ['pay_time','<=',$end_time];
        //支出
        $map[] = ['bill_type','=',1];
        $map[] = ['payment_time','>=',$start_time];
        $map[] = ['payment_time','<=',$end_time];
        $map[] = ['property_id','=',$property_id];
        $total_money = $dbHouseVillagePayOrder->sumMoney($where,[],'money');
        $total_price = $dbPropertyBill->sumMoney($map,'price');
        //圆环统计
        $field = 'title as name,sum(price) as price';
        $ring_list =$dbPropertyBill->getSelect($map,$field,'title',0,7);
        $title=[];
        foreach ($ring_list as $key=>&$val){
            switch ($key){
                case 0:
                    $val['color'] = '#ff5e2d';
                    break;
                case 1:
                    $val['color'] = '#ffd100';
                    break;
                case 2:
                    $val['color'] = '#458bfa';
                    break;
                case 3:
                    $val['color'] = '#813cf9';
                    break;
                case 4:
                    $val['color'] = '#5bddbf';
                    break;
                case 5:
                    $val['color'] = '#ff814d';
                    break;
                case 6:
                    $val['color'] = '#d645fa';
                    break;
            }
            $title[] = $val['name'];
        }
        if($title && count($title)>0) {
            $map[] = ['title', 'not in', $title];
            $other = $dbPropertyBill->sumMoney($map, 'price');
            $count = count($ring_list);
            $ring_list[$count] = ['name' => '其他', 'price' => sprintf("%.2f", $other),'color' => ''];
        }
        $property_where[] = ['id','=',$property_id];
        $field = 'id,property_name';
        $property_info = $dbHouseProperty->get_one($property_where,$field);//物业信息

        $data['property'] = $property_info;
        $data['total_money'] = sprintf("%.2f", $total_money);//总收入
        $data['total_price'] = sprintf("%.2f", $total_price);//总支出
        $data['line_list'] = $line_list;//折线统计
        $data['ring_list'] = $ring_list;//圆环统计
        return $data;
    }

    /**
     * Notes:获取小区列表
     * @param $property_id
     * @return mixed
     * @author: weili
     * @datetime: 2020/10/13 13:31
     */
    public function getHouseVillageList($property_id)
    {
        $dbHouseVillage = new HouseVillage();
        $where[] = ['property_id','=',$property_id];
        $where[] = ['status','=',1];
        $field = 'village_id,village_name,property_id';
        $list = $dbHouseVillage->getList($where,$field);
        $data['list'] = $list;
        return $data;
    }
    /**
     * Notes:硬件统计
     * @param $property_id
     * @param $village_id
     * @return array
     * @author: weili
     * @datetime: 2020/10/14 15:20
     */
    public function getHardware($property_id,$village_id)
    {
        $dbHouseVillage = new HouseVillage();
        $dbHouseFaceDevice = new HouseFaceDevice();
        $dbHouseCameraDevice = new HouseCameraDevice();
        $dbHouseVillageDoor = new HouseVillageDoor();
        $dbIntelligenceExpress = new IntelligenceExpress();
        $dbParkPassage = new ParkPassage();
        $dbHouseVillageParkConfig = new HouseVillageParkConfig();
        $houseVillageParkGarageConfigDb = new HouseVillageParkGarageConfig();
        $dbHouseSmartCharging = new HouseSmartCharging();
        if(!$village_id) {
            $village_where[] = ['property_id', '=', $property_id];
            $village_where[] = ['status', '=', 1];
            $village_id_arr = $dbHouseVillage->getColumn($village_where, 'village_id');
            if($village_id_arr && count($village_id_arr)>0) {
                $public_where = ['village_id', 'in', $village_id_arr];
            }else{
                $public_where = ['village_id', '<', 0];
            }
        }else{
            $public_where = ['village_id','=',$village_id];
        }
        $data=[];//初始化返回数据
        //蓝牙门禁
        $doorWhere[] = $public_where;
        $doorCount = $dbHouseVillageDoor->getCount($doorWhere);//总数量
        $doorWhere[] = ['door_status','=',0];
        $doorOffCount = $dbHouseVillageDoor->getCount($doorWhere);//离线数量
        if($doorCount>0) {
            $data['list'][] = [
                'title' => '蓝牙门禁',
                'total' => $doorCount,//总数量
                'normal' => $doorCount ? round((($doorCount - $doorOffCount) / $doorCount) * 100) : 0,//正常设备占比
                'off' => $doorCount ? round(($doorOffCount / $doorCount) * 100) : 0,//离线设备占比
                'fault' => 0,//故障设备占比
            ];
        }
        //人脸门禁
        $where[] = $public_where;
        $where[] = ['is_del','=',0];
        $faceCount = $dbHouseFaceDevice->getCount($where);
        $where[] = ['device_status','=',2];
        $faceOffCount = $dbHouseFaceDevice->getCount($where);
        if($faceCount>0) {
            $data['list'][] = [
                'title' => '人脸识别门禁',
                'total' => $faceCount, //总数量
                'normal' => $faceCount ? round((($faceCount - $faceOffCount) / $faceCount) * 100) : 0,//正常设备占比
                'off' => $faceCount ? round(($faceOffCount / $faceCount) * 100) : 0,//离线设备占比
                'fault' => 0,      //故障数量（暂定0）
            ];
        }
        //人脸识别摄像机
        $map[] = $public_where;
        $cameraCount = $dbHouseCameraDevice->getCount($map);
        $map[] = ['camera_status','<>',0];
        $cameraOffCount = $dbHouseCameraDevice->getCount($map);
        if($cameraCount) {
            $data['list'][] = [
                'title' => '人脸识别摄像机',
                'total' => $cameraCount,
                'normal' => $cameraCount ? round((($cameraCount - $cameraOffCount) / $cameraCount) * 100) : 0,//正常设备占比
                'off' => $cameraCount ? round(($cameraOffCount / $cameraCount) * 100) : 0,//离线设备占比
                'fault' => 0,//故障数量（暂定0）
            ];
        }
        //智能快递柜
        $arkWhere[] = $public_where;
        $arkCount = $dbIntelligenceExpress->getCount($arkWhere);
        $arkWhere[] = ['status','=',0];
        $arkOffCount = $dbIntelligenceExpress->getCount($arkWhere);
        if($arkCount>0) {
            $data['list'][] = [
                'title' => '智能快递柜',
                'total' => $arkCount,
                'normal' => $arkCount ? round((($arkCount - $arkOffCount) / $arkCount) * 100) : 0,//正常设备占比
                'off' => $arkCount ? round(($arkOffCount / $arkCount) * 100) : 0,//离线设备占比
                'fault' => 0,//故障数量（暂定0）
            ];
        }
        $parkCount = 0;
        $garageConfigWhere[]=$public_where;
        $garageConfig=$houseVillageParkGarageConfigDb->getOneData($garageConfigWhere,'*','park_versions desc');
        if($garageConfig && !$garageConfig->isEmpty()){
            $garageConfigWhere[]=array('park_versions','=',2);
            $parkCount=$houseVillageParkGarageConfigDb->getCount($garageConfigWhere);
            $parkCount=$parkCount>0 ? $parkCount:0;
        }else{
            //智慧停车场
            $parkWhere[] = $public_where;
            $parkInfo = $dbHouseVillageParkConfig->getFind($parkWhere,'park_versions');
            
            if($parkInfo['park_versions'] == 2)
            {
                $parkCount = 1;
            } 
        }

        $parkWhere[] = ['status','<>',1];
        $parkOffCount = $dbParkPassage->getCount($parkWhere);
        if($parkCount>0) {
            $data['list'][] = [
                'title' => '智慧停车',
                'total' => $parkCount,
                'normal' => $parkCount ? round((($parkCount - $parkOffCount) / $parkCount) * 100) : 0,//正常设备占比
                'off' => $parkCount ? round(($parkOffCount / $parkCount) * 100) : 0,//离线设备占比
                'fault' => 0,//故障数量（暂定0）
            ];
        }
        //智能充电桩
        $smart_where[] = $public_where;
        $smartCount = $dbHouseSmartCharging->getCount($smart_where);
        if($smartCount>0) {
            $data['list'][] = [
                'title' => '智能充电桩',
                'total' => $smartCount,
                'normal' => $smartCount ? round(($smartCount / $smartCount) * 100) : 0,//正常设备占比
                'off' => 0,//离线设备占比
                'fault' => 0,//故障数量（暂定0）
            ];
        }
        $total = $doorCount+$faceCount+$cameraCount+$arkCount+$parkCount+$smartCount;//所有设备总数量
        $total_off = $doorOffCount+$faceOffCount+$cameraOffCount+$arkOffCount+$parkOffCount;//所有离线设备数量
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $http = $http_type.$_SERVER['SERVER_NAME'].'/v20/public/static/community/images/statistics_column/';

        if($total>0) {
            $ratio_normal = $total ? round((($total - $total_off) / $total) * 100) : 0;//正常总占比
            $ratio_fault = 0;//故障总占比
            $ratio_off = $total ? round(($total_off / $total) * 100) : 0;//离线总占比
            $data['info'] = [
                ['title' => '设备总数', 'device_total' => $total,'ioc'=>$http.'device.png'],
                ['title' => '正常', 'device_total' => $ratio_normal .'%','ioc'=>$http.'normal.png'],
                ['title' => '故障', 'device_total' => $ratio_fault .'%','ioc'=>$http.'fault.png'],
                ['title' => '离线', 'device_total' => $ratio_off .'%','ioc'=>$http.'off.png'],
            ];
        }
        if (!isset($data['info']) || !$data['info']) {
            $data['info'] = [];
        }
        if (!isset($data['list']) || !$data['list']) {
            $data['list'] = [];
        }
        return $data;
    }
    /**
     * Notes: 车场统计
     * @param $property_id
     * @param $village_id
     * @return mixed
     * @author: weili
     * @datetime: 2020/10/15 13:32
     */
    public function getParking($property_id,$village_id)
    {
        $dbHouseVillageParkingPosition = new HouseVillageParkingPosition();
        $dbHouseVillage = new HouseVillage();
        $dbInPark = new InPark();
        $dbHouseVillageParkingCar = new HouseVillageParkingCar();
        if(!$village_id) {
            $village_where[] = ['property_id', '=', $property_id];
            $village_where[] = ['status', '=', 1];
            $village_id_arr = $dbHouseVillage->getColumn($village_where, 'village_id');
            if($village_id_arr && count($village_id_arr)>0) {
                $public_where = ['village_id', 'in', $village_id_arr];
                $relevance_where =['p.village_id','in',$village_id_arr];
                $public_map =['park_id','in',$village_id_arr];
                $hasWhere[]= ['c.village_id','in',$village_id_arr];
            }else{
                $public_where = ['village_id', '<', 0];
                $relevance_where = ['p.village_id','<',0];
                $public_map =['park_id','<',0];
                $hasWhere[]= ['c.village_id','<',0];
            }
        }else{
            $public_where = ['village_id','=',$village_id];
            $relevance_where = ['p.village_id','=',$village_id];
            $public_map =['park_id','=',$village_id];
            $hasWhere[]= ['c.village_id','=',$village_id];
        }
        $positionWhere[] = $public_where;
        $positionWhere[] = ['position_pattern','=',1];
        $positionCount = $dbHouseVillageParkingPosition->get_village_park_position_num($positionWhere);//总固定车位

        $ownerWhere[] =$relevance_where;
        $ownerWhere[] = ['p.position_pattern','=',1];//固定车位
        $ownerBindCount = $dbHouseVillageParkingPosition->getBindPosition($ownerWhere); //业主绑定车位

        $temWhere[] = $public_where;
        $temWhere[] = ['position_pattern','=',1];//固定车位
        $temWhere[] = ['position_type','=',3];//临停车位
        $temporaryCount = $dbHouseVillageParkingPosition->get_village_park_position_num($temWhere);//临时车位

        $carWhere[] = $public_map;
        $carWhere[] = ['is_out','=',0];
        $carNumber = $dbInPark->getColumn($carWhere,'car_number');
        $hasWhere[]= ['c.car_number','in',$carNumber];
        $hasWhere[] = ['p.position_pattern','=',1];//固定车位
        $hasCount = $dbHouseVillageParkingCar->getCarNum($hasWhere);//进场车辆已绑定车位的车辆

        //获取已绑定车辆的车位
        $bindCarWhere[] = $relevance_where;
        $bindCarWhere[] = ['p.position_pattern','=',1];
        $bindCarCount = $dbHouseVillageParkingPosition->getBindCar($bindCarWhere);

        //已用车位
        $usedWhere[] = $public_map;
        $usedWhere[] = ['is_out','=',0];
        $usedCount = $dbInPark->getCount($usedWhere);

        //剩余车位
        $notCount = $usedCount-$hasCount;//进场车辆没有绑定车位的车辆
        $remain = $positionCount-$bindCarCount-$notCount;
        if($remain<0){
            $remain = 0;
        }
        $data['parking'] = [
            'positionCount'=>$positionCount,//总固定车位
            'ownerBindCount'=>$ownerBindCount,//业主绑定车位
            'temporaryCount'=>$temporaryCount,//临时车位
            'usedCount'=>$usedCount,//已用车位
            'remain'=>$remain,//剩余车位
        ];
        return $data;
    }
    /**
     * Notes:人流量统计
     * @param $property_id
     * @param $village_id
     * @return mixed
     * @author: weili
     * @datetime: 2020/10/16 11:32
     */
    public function getTraffic($property_id,$village_id)
    {
        $dbHouseVillage = new HouseVillage();
        $dbHouseVillageUserBind = new HouseVillageUserBind();
        $dbHouseVillageVisitor = new HouseVillageVisitor();
        $dbArea = new Area();
        if(!$village_id) {
            $village_where[] = ['property_id', '=', $property_id];
            $village_where[] = ['status', '=', 1];
            $village_id_arr = $dbHouseVillage->getColumn($village_where, 'village_id');
            if($village_id_arr && count($village_id_arr)>0) {
                $public_where = ['village_id', 'in', $village_id_arr];
            }else{
                $public_where = ['village_id','<',0];
            }
        }else{
            $village_where[] = ['village_id','=',$village_id];
            $public_where = ['village_id','=',$village_id];
        }
        $map[] = $public_where;
        $map[] = ['status','=',1];
        $map[] = ['type','=',0];
        $owner_count = $dbHouseVillageUserBind->getVillageUserNum($map);//业主
        unset($map[2]);
        $map[] = ['type','=',1];
        $tenants_count = $dbHouseVillageUserBind->getVillageUserNum($map);//租客
        $map = array_values($map);
        unset($map[2]);
        $map[] = ['type','=',4];
        $work_count = $dbHouseVillageUserBind->getVillageUserNum($map);//工作人员（服务人员）

        $visitor_where[] = $public_where;
        $visitor_where[] = ['status','=',1];
        $visitor_count = $dbHouseVillageVisitor->getCount($visitor_where);//所有访客
        //饼状图统计数据
        $pie_chart = [
            'total_count'=>$owner_count+$tenants_count+$visitor_count+$work_count,
            'owner_count'=>$owner_count,//业主
            'tenants_count'=>$tenants_count,//租客
            'visitors_count'=>$visitor_count,//访客
            'service_member_count'=>$work_count,//服务人员
        ];
        $city_schedule_chart = [];
        //进度条统计
        if(!$village_id) {
            $villageList = $dbHouseVillage->getList($village_where, 'village_id,city_id');
            $villageList = $villageList->toArray();

            $city_id = array_column($villageList, 'city_id');
            $city_id = array_unique($city_id);

            $map = [];
            $map[] = ['area_id', 'in', $city_id];
            $field = 'area_id,area_name';
            $city = $dbArea->getList($map, $field);
            $city = $city->toArray();

            $new_arr = [];
            foreach ($city as $key => $val) {
                $new_arr[$val['area_id']] = $val['area_name'];
            }
            $village_arr = [];
            if ($new_arr && count($new_arr) > 0) {
                foreach ($villageList as $key => &$val) {
                    foreach ($new_arr as $k => $v) {
                        if ($val['city_id'] == $k) {
                            $village_arr[$k][] = $val['village_id'];
                        }
                    }
                }
            }
            foreach ($village_arr as $key => &$val) {
                $map = [];
                $map[] = ['village_id', 'in', $val];
                $map[] = ['status', '=', 1];
                $map[] = ['type', '=', 0];
                $city_owner_count = $dbHouseVillageUserBind->getVillageUserNum($map);//业主
                unset($map[2]);
                $map[] = ['type', '=', 1];
                $city_tenants_count = $dbHouseVillageUserBind->getVillageUserNum($map);//租客
                $map = array_values($map);
                unset($map[2]);
                $map[] = ['type', '=', 4];
                $city_work_count = $dbHouseVillageUserBind->getVillageUserNum($map);//工作人员（服务人员）

                $visitor_where[] = ['village_id', 'in', $val];
                $visitor_where[] = ['status', '=', 1];
                $city_visitor_count = $dbHouseVillageVisitor->getCount($visitor_where);//所有访客

                $total_count = $city_owner_count+$city_tenants_count+$city_work_count+$city_visitor_count;
                $city_schedule_chart[$key]['title'] = $new_arr[$key];//城市名称
                $city_schedule_chart[$key]['city_owner_ratio'] = $total_count?round(($city_owner_count/$total_count) *100):0;//业主
                $city_schedule_chart[$key]['city_tenants_ratio'] = $total_count?round(($city_tenants_count/$total_count) *100):0;//租客
                $city_schedule_chart[$key]['city_visitors_ratio'] = $total_count?round(($city_visitor_count/$total_count) *100):0;//访客
                $city_schedule_chart[$key]['city_service_member_ratio'] = $total_count?round(($city_work_count/$total_count) *100):0;//服务人员
            }
        }

        $data['city_schedule_chart'] = $city_schedule_chart;//进度条统计
        $data['pie_chart'] = $pie_chart;//饼状图统计
        return $data;
    }

    /**
     * Notes: 工单统计
     * @param $property_id
     * @param $village_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: weili
     * @datetime: 2020/10/19 9:16
     */
    public function getRepairOrder($property_id,$village_id)
    {
        $dbHouseVillageRepairList = new HouseVillageRepairList();
        $dbHouseVillage = new HouseVillage();
        $dbArea = new Area();
        if(!$village_id) {
            $village_where[] = ['property_id', '=', $property_id];
            $village_where[] = ['status', '=', 1];
            $village_id_arr = $dbHouseVillage->getColumn($village_where, 'village_id');
            if($village_id_arr && count($village_id_arr)>0) {
                $public_where = ['village_id', 'in', $village_id_arr];
            }else{
                $public_where = ['village_id','<',0];
            }
        }else{
            $village_where[] = ['village_id','=',$village_id];
            $public_where = ['village_id','=',$village_id];
        }
        $where[] = $public_where;
        $where[] = ['uid','<>',0];//用户uid
        $where[] = ['status','=',0];//待处理
        $pending_count = $dbHouseVillageRepairList->get_repair_list_num($where,'');
        unset($where[2]);
        $where[] = ['status','in',[1,2]];//处理中
        $being_processed_count = $dbHouseVillageRepairList->get_repair_list_num($where,'');
        $where = array_values($where);
        unset($where[2]);
        $where[] = ['status','in',[3,4]];//已处理
        $processed_count = $dbHouseVillageRepairList->get_repair_list_num($where,'');
        $city_schedule_chart=[];
        if(!$village_id){
            $villageList = $dbHouseVillage->getList($village_where, 'village_id,city_id');
            $villageList = $villageList->toArray();

            $city_id = array_column($villageList, 'city_id');
            $city_id = array_unique($city_id);

            $map = [];
            $map[] = ['area_id', 'in', $city_id];
            $field = 'area_id,area_name';
            $city = $dbArea->getList($map, $field);
            $city = $city->toArray();
            $new_arr = [];
            foreach ($city as $key => $val) {
                $new_arr[$val['area_id']] = $val['area_name'];
            }
            $village_arr = [];
            if ($new_arr && count($new_arr) > 0) {
                foreach ($villageList as $key => &$val) {
                    foreach ($new_arr as $k => $v) {
                        if ($val['city_id'] == $k) {
                            $village_arr[$k][] = $val['village_id'];
                        }
                    }
                }
            }
            foreach ($village_arr as $key => &$val) {
                $where = [];
                $where[] = ['village_id','in',$val];
                $where[] = ['uid','<>',0];//用户uid
                $where[] = ['status','=',0];//待处理
                $city_pending_count = $dbHouseVillageRepairList->get_repair_list_num($where,'');
                unset($where[2]);
                $where[] = ['status','in',[1,2]];//处理中
                $city_being_processed_count = $dbHouseVillageRepairList->get_repair_list_num($where,'');
                $where = array_values($where);
                unset($where[2]);
                $where[] = ['status','in',[3,4]];//已处理
                $city_processed_count = $dbHouseVillageRepairList->get_repair_list_num($where,'');

                $total_count=$city_pending_count+$city_being_processed_count+$city_processed_count;//总人数
                $city_schedule_chart[$key]['title']=$new_arr[$key];//城市名称
                $city_schedule_chart[$key]['city_pending_count']=$total_count?round(($city_pending_count/$total_count)*100):0;//待处理工单数据
                $city_schedule_chart[$key]['city_being_processed_count']=$total_count?round(($city_being_processed_count/$total_count)*100):0;//处理中工单数据
                $city_schedule_chart[$key]['city_processed_count']=$total_count?round(($city_processed_count/$total_count)*100):0;//已处理工单数据
            }
        }
        $data['city_schedule_chart'] = $city_schedule_chart;//进度条统计
        $data['pie_chart'] = [//圆环统计
            'total_count'=>$pending_count+$being_processed_count+$processed_count,//总数量
            'pending_count'=>$pending_count,//待处理工单数据
            'being_processed_count'=>$being_processed_count,//处理中工单数据
            'processed_count'=>$processed_count,//已处理工单数据
        ];
        return $data;
    }
}