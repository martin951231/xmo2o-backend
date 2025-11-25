<?php
/**
 * 车辆相关
 * @author weili
 * @datetime 2020/8/3
 */

namespace app\community\model\service;

use app\community\model\db\HouseNewChargeTime;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseNewPayOrderSummary;
use app\community\model\db\HouseVillageCarAccessRecord;
use app\community\model\db\HouseVillageParkConfig;
use app\community\model\db\HouseVillageParkGarageConfig;
use app\community\model\db\HouseVillageParkingGarage;
use app\community\model\db\InPark; //进场车辆
use app\community\model\db\OutPark; //入场车辆
use app\community\model\db\HouseVillageParkingPosition; //车位
use app\community\model\db\HouseVillagePayOrder; //停车收费
use app\community\model\db\HouseVillageParkingCar;
use app\community\model\db\ParkPassage; //停车收费
class ParkService
{
    /**
     * Notes: 社区车辆相关统计
     * @param $village_id
     * @param string $startTime
     * @param string $endTime
     * @return array
     * @author: weili
     * @datetime: 2020/8/4 13:33
     */
    public function parkInfo($property_id,$village_id,$startTime='',$endTime='')
    {
        $dbInPark = new InPark();
        $dbOutPark = new OutPark();
        $dbHouseVillageParkingPosition = new HouseVillageParkingPosition();
        $dbHouseVillagePayOrder = new HouseVillagePayOrder();
        $dbHouseVillageParkingCar = new HouseVillageParkingCar();
        $dbParkPassage = new ParkPassage();          //智慧停车场设备
        $dbChargeTime=new HouseNewChargeTime();
        $dbHouseNewPayOrderSummary = new HouseNewPayOrderSummary();
        $houseVillageCarAccessRecord= new HouseVillageCarAccessRecord();
        $dbHouseNewCashierService=new HouseNewCashierService();
        $dbHouseNewPayOrder = new HouseNewPayOrder();

        //套餐过滤
        $package_content = $this->getOrderPackage($village_id);

        $whereOut = [];
        $whereIn = [];
        $whereInAccess=array(['park_id', '=', $village_id],['accessType','=',1]);
        $whereInAccess[] = ['del_time', '<', 1];
        $whereInAccess[] = ['order_id', '<>', ''];

        $whereOutAccess=array(['a.park_id', '=', $village_id],['a.accessType','=',1]);
        $whereOutAccess[] = ['a.del_time', '<', 1];
        $whereOutAccess[] = ['a.order_id', '<>', ''];

        $whereOutAccess[] = ['b.accessType', '=', 2];
        $whereOutAccess[] = ['b.park_id', '=', $village_id];
        $whereOutAccess[] = ['b.order_id', '<>', ''];
        $whereOutAccess[] = ['b.del_time', '<', 1];
        
        if($startTime){
            $map[]=['o.out_time','>=',$startTime];
            $temporaryWhere[] = ['pay_time','>=',$startTime];
            $whereOut[] = ['out_time','>=',$startTime];
            $whereIn[] = ['in_time','>=',$startTime];
            $whereInAccess[] =['accessTime', '>=',$startTime];
            $whereOutAccess[] =['b.accessTime', '>=',$startTime];
        }
        if($endTime){
            $map[] = ['o.out_time','<=',$endTime];
            $temporaryWhere[] = ['pay_time','<=',$endTime];
            $whereOut[] = ['out_time','<=',$endTime];
            $whereIn[] = ['in_time','<=',$endTime];
            $whereInAccess[] =['accessTime', '<=',$endTime];
            $whereOutAccess[] =['b.accessTime', '<=',$endTime];
        }
        //套餐过滤
        if(in_array(10,$package_content)) {
            $whereIn[] = ['park_id', '=', $village_id];
            //$inCount = $dbInPark->getCount($whereIn);//今日入场车辆
            $inCount = $houseVillageCarAccessRecord->getCount($whereInAccess);

            $whereOut[] = ['park_id', '=', $village_id];
            //$outCount1 = $dbOutPark->getInfoCount($whereOut); //今日出场车辆
            $outCount1 = $houseVillageCarAccessRecord->getOutCounts($whereOutAccess);
            $outCount = $outCount1;
            /*
            if ($outCount1 > 0) {
                $outCount = $outCount1;
            } else {
                $map[] = ['o.park_id|i.park_id', '=', $village_id];
                $map[] = ['i.is_out','=',1];
                $outCount = $dbInPark->getParkCount($map); //今日出场车辆
            }
            */
        }
        //车场数据
        $parkDeviceWheres[] = ['village_id','=',$village_id];
        $parkCountStatus = $dbParkPassage->getCount($parkDeviceWheres);
        $positionWhere[] = ['pp.village_id','=',$village_id];
        $positionWhere[] = ['pg.status','=',1];
        if($parkCountStatus) {
            $positionWhere[] = ['pp.position_pattern', '=', 1];
        }
        $positionCount = $dbHouseVillageParkingPosition->getCountss($positionWhere);//总固定车位

        $ownerWhere[] = ['p.village_id','=',$village_id];
        $ownerWhere[] = ['p.position_pattern','=',1];//固定车位
        $ownerBindCount = $dbHouseVillageParkingPosition->getBindPosition($ownerWhere); //业主绑定车位

        $temWhere[] = ['village_id','=',$village_id];
        $temWhere[] = ['position_pattern','=',1];//固定车位
        $temWhere[] = ['position_type','=',3];
        $temporaryCount = $dbHouseVillageParkingPosition->get_village_park_position_num($temWhere);//临时车位

        //获取已绑定车辆的车位
        $bindCarWhere[] = ['p.village_id','=',$village_id];
        $bindCarWhere[] = ['p.position_pattern','=',1];
        $bindCarCount = $dbHouseVillageParkingPosition->getBindCar($bindCarWhere);

        $carWhere[] = ['park_id','=',$village_id];
        $carWhere[] = ['is_out','=',0];
        $carNumber = $dbInPark->getColumn($carWhere,'car_number');
        $hasWhere[]= ['c.car_number','in',$carNumber];
        $hasWhere[]= ['c.village_id','=',$village_id];
        $hasWhere[] = ['p.position_pattern','=',1];//固定车位
        $hasCount = $dbHouseVillageParkingCar->getCarNum($hasWhere);//进场车辆已绑定车位的车辆

       
        //已用车位
        $tmp_time=time()-100*86400;
        $usedWhere=array();
        $usedWhere[] = ['accessType','=',1];
        $usedWhere[] = ['park_id','=',$village_id];
        $usedWhere[] = ['is_out','=',0];
        $usedWhere[] = ['accessTime','>',$tmp_time];
        $usedCount = $houseVillageCarAccessRecord->getCount($usedWhere);
//        $usedWhere[] = ['p.village_id','=',$village_id];
//        $usedWhere[] = ['p.position_pattern','=',1];//固定车位
//        $usedWhere[] = ['p.position_type','=',3];
//        $usedWhere[] = ['i.is_out','=',0];
//        $usedCount = $dbHouseVillageParkingPosition->getInPackCount($usedWhere);
        //剩余车位
        $notCount = $usedCount-$hasCount;//进场车辆没有绑定车位的车辆
        $notCount = ($usedCount-$hasCount)?($usedCount-$hasCount):0;//进场车辆没有绑定车位的车辆
       // $remain = $positionCount-$bindCarCount-$notCount;
        $remain = $positionCount-$usedCount;
        if($remain<0)//小于0说明车位已满，超出的车辆停的是虚拟车位
        {
            $remain = 0;
        }
        $parkCount = 0;//初始化 本月停车累计收费
        $temporaryMoney = 0; //初始化 当天临时停车累计收费

        $parkWhere=$temporaryWhere=[];
        //套餐过滤
        if(in_array(5,$package_content)) {

            //todo 【新版收费-小区统计停车收费】
            $new_charge_time=$dbChargeTime->get_one(['property_id'=>$property_id]);
            $nowtime = time();
            if (!empty($new_charge_time) && $new_charge_time['take_effect_time'] < $nowtime && $new_charge_time['take_effect_time'] > 1) {
                // 本月停车累计收费
                $sTime = strtotime(date('Y-m-01', time()));
                $eTime = strtotime(date('Y-m-t 23:59:59', time()));
                $parkWhere[] = ['pay_time', '>=', $sTime];
                $parkWhere[] = ['pay_time', '<=', $eTime];
                $parkWhere[] = ['village_id', '=', $village_id];
                $parkWhere[] = ['is_paid', '=', 1];
                $parkWhere[] = ['order_type','=','park_new'];
                $parkWhere[] = ['car_type', '=', 'temporary_type'];
                $parkCount=get_number_format($dbHouseNewCashierService->getChargeProjectMoney($parkWhere));

                //当天临时停车累计收费
                $s_Times = strtotime(date('Y-m-d', time()));
                $e_Times = strtotime(date('Y-m-d 23:59:59', time()));
                $temporaryWhere[] = ['pay_time', '>=', $s_Times];
                $temporaryWhere[] = ['pay_time', '<=', $e_Times];
                $temporaryWhere[] = ['village_id', '=', $village_id];
                $temporaryWhere[] = ['is_paid', '=', 1];
                $temporaryWhere[] = ['order_type','=','park_new'];
                $temporaryWhere[] = ['car_type', '=', 'temporary_type'];
                $temporaryMoney=get_number_format($dbHouseNewCashierService->getChargeProjectMoney($temporaryWhere));
            }else{
                $sTime = strtotime(date('Y-m-01', time()));
                $eTime = strtotime(date('Y-m-t 23:59:59', time()));
                $parkWhere[] = ['pay_time', '>=', $sTime];
                $parkWhere[] = ['pay_time', '<=', $eTime];
                $parkWhere[] = ['village_id', '=', $village_id];
                $parkWhere[] = ['order_type', '=', 'park'];
                $parkWhere[] = ['order_status', '=', 1];
                $parkCount = get_number_format($dbHouseVillagePayOrder->sumMoney($parkWhere));//本月停车累计收费

                $s_Times = strtotime(date('Y-m-d', time()));
                $e_Times = strtotime(date('Y-m-d 23:59:59', time()));
                $temporaryWhere[] = ['pay_time', '>=', $s_Times];
                $temporaryWhere[] = ['pay_time', '<=', $e_Times];
                $temporaryWhere[] = ['village_id', '=', $village_id];
                $temporaryWhere[] = ['order_type', '=', 'park'];
                $temporaryMoney = get_number_format($dbHouseVillagePayOrder->sumMoney($temporaryWhere));//当天临时停车累计收费
            }
        }
        //占比
        if ($usedCount > $positionCount) {
            $proportion = '100%';
        } else {
            $proportion = $positionCount?sprintf("%.1f",(($usedCount/$positionCount)*100)).'%':0;
        }
        $data =[
            //'inCount'=>$inCount,//今日入场车辆
            //'outCount'=>$outCount,//今日出场车辆
            'positionCount'=>$positionCount,//总固定车位
            'ownerBindCount'=>$ownerBindCount,//业主绑定车位
            'temporaryCount'=>$temporaryCount,//临时车位
            'usedCount'=>$usedCount,//已用车位
            'remain'=>$remain,//剩余车位
            //'parkCount'=>$parkCount,//本月停车累计收费
            //'temporaryMoney'=>$temporaryMoney,//当天临时停车累计收费
            'proportion'=>$proportion,
        ];
        $datemonth=date('Y-m').'-01 00:00:30';
        $datemonthtime=strtotime($datemonth);
        $tmpMonth=date('Y-m');
        $monthArr=array($tmpMonth);
        for($ii=0;$ii<5;$ii++){
            $datemonthtime=strtotime('-1 month',$datemonthtime);
            $tmpMonth=date('Y-m',$datemonthtime);
            $monthArr[]=$tmpMonth;
        }
        $monthArr=array_reverse($monthArr);
        $monthlycar=array();
        $temporarycar=array();
        foreach ($monthArr as $mm){
            $sTimeStr=$mm.'-01 00:00:00';
            $sTime = strtotime($sTimeStr);
            $eTimeTmp=$sTime+100;
            $eTimeStr = date('Y-m-t',$eTimeTmp).' 23:59:59';
            $eTime = strtotime($eTimeStr);
            $payOrderWhere=array();
            $payOrderWhere[] = ['village_id', '=', $village_id];
            $payOrderWhere[] = ['car_type', '=', 'month_type'];
            $payOrderWhere[] = ['is_paid', '=', 1];
            $payOrderWhere[] = ['pay_time', '>=', $sTime];
            $payOrderWhere[] = ['pay_time', '<=', $eTime];
            $payOrderWhere[] = ['order_type', '=', 'park_new'];
            $payOrderWhere[] = ['refund_type', '<', 1];
            $month_money=$dbHouseNewPayOrder->getSum($payOrderWhere,'pay_money');
            $month_money=$month_money>0 ? $month_money:0;
            $monthlycar[]=$month_money;
            
            $payOrderWhere=array();
            $payOrderWhere[] = ['village_id', '=', $village_id];
            $payOrderWhere[] = ['car_type', '=', 'temporary_type'];
            $payOrderWhere[] = ['is_paid', '=', 1];
            $payOrderWhere[] = ['pay_time', '>=', $sTime];
            $payOrderWhere[] = ['pay_time', '<=', $eTime];
            $payOrderWhere[] = ['order_type', '=', 'park_new'];
            $payOrderWhere[] = ['refund_type', '<', 1];
            $temporary_money=$dbHouseNewPayOrder->getSum($payOrderWhere,'pay_money');
            $temporary_money=$temporary_money>0 ? $temporary_money:0;
            $temporarycar[]=$temporary_money;
        }

        $data['car_pay_info']=array('legend_data'=>array('月租车缴费金额','临时车缴费金额'),'xAxis_data'=>$monthArr,'series'=>array('monthlycar'=>$monthlycar,'temporarycar'=>$temporarycar));
        //套餐过滤
        if(in_array(10,$package_content)) {
            $data['inCount'] = $inCount;//今日入场车辆
            $data['outCount'] = $outCount;//今日出场车辆
        }
        $data['parkCount'] = $parkCount;//本月停车累计收费
        $data['temporaryMoney'] = $temporaryMoney;//当天临时停车累计收费

        $parkCounts = 0;
        //套餐过滤
        if(in_array(10,$package_content)) {
            //智慧停车设备  显示状态
            $parkDeviceWhere[] = ['village_id', '=', $village_id];
            $parkCounts = $dbParkPassage->getCount($parkDeviceWhere);
        }
        if($parkCounts){
            $data['status'] = 1;
        }else{
            $data['status'] = 0;
        }
        return $data;
    }

    /**
     * 车场数据
     * @author lijie
     * @date_time 2020/12/05
     * @param $village_id
     * @param string $startTime
     * @param string $endTime
     * @return array
     */
    public function parkData($property_id,$village_id,$startTime='',$endTime='')
    {
        $dbInPark = new InPark();
        $dbOutPark = new OutPark();
        $dbHouseVillageParkingPosition = new HouseVillageParkingPosition();
        $dbHouseVillagePayOrder = new HouseVillagePayOrder();
        $dbHouseVillageParkingCar = new HouseVillageParkingCar();
        $dbParkPassage = new ParkPassage();          //智慧停车场设备
        $dbChargeTime=new HouseNewChargeTime();
        $dbHouseNewPayOrderSummary = new HouseNewPayOrderSummary();
        $dbHouseNewCashierService=new HouseNewCashierService();
        $dbHouseNewPayOrder = new HouseNewPayOrder();

        if($startTime){
            $where[] = ['in_time','>=',$startTime];
            $map[]=['o.out_time','>=',$startTime];
            $temporaryWhere[] = ['pay_time','>=',$startTime];
        }
        if($endTime){
            $where[] = ['in_time','<=',$endTime];
            $map[] = ['o.out_time','<=',$endTime];
            $temporaryWhere[] = ['pay_time','<=',$endTime];
        }
        $where[] = ['park_id','in',$village_id];
//        $where[] = ['is_out','=',0];
        $inCount = $dbInPark->getCount($where);//今日入场车辆

        $map[] = ['o.park_id|i.park_id','in',$village_id];
//        $map[] = ['i.is_out','<>',0];
        $outCount = $dbOutPark->getCount($map); //今日出场车辆

        //车场数据
        $parkDeviceWheres[] = ['village_id','in',$village_id];
        $parkCountStatus = $dbParkPassage->getCount($parkDeviceWheres);
        $positionWhere[] = ['pp.village_id','in',$village_id];
        $positionWhere[] = ['pg.status','=',1];
        if($parkCountStatus) {
            $positionWhere[] = ['pp.position_pattern', '=', 1];
        }
        $positionCount = $dbHouseVillageParkingPosition->getCountss($positionWhere);//总固定车位

        $ownerWhere[] = ['p.village_id','in',$village_id];
        $ownerWhere[] = ['p.position_pattern','=',1];//固定车位
        $ownerBindCount = $dbHouseVillageParkingPosition->getBindPosition($ownerWhere); //业主绑定车位

        $temWhere[] = ['village_id','in',$village_id];
        $temWhere[] = ['position_pattern','=',1];//固定车位
        $temWhere[] = ['position_type','=',3];
        $temporaryCount = $dbHouseVillageParkingPosition->get_village_park_position_num($temWhere);//临时车位

        //获取已绑定车辆的车位
        $village_ids=is_array($village_id) ? $village_id:explode(',',$village_id);
        $bindCarCount=0;
        $village_ids = array_unique($village_ids);
        foreach ($village_ids as $vid) {
            if ($vid > 0) {
                $bindCarWhere = array();
                $bindCarWhere[] = ['p.village_id', '=', $vid];
                $bindCarWhere[] = ['p.position_pattern', '=', 1];
                $carCount = $dbHouseVillageParkingPosition->getBindCarCount($bindCarWhere);
                $carCount = $carCount > 0 ? $carCount : 0;
                $bindCarCount += $carCount;
            }
        }
        /*
        $bindCarWhere[] = ['p.village_id','in',$village_id];
        $bindCarWhere[] = ['p.position_pattern','=',1];
        $bindCarCount = $dbHouseVillageParkingPosition->getBindCar($bindCarWhere);
        */
        $carWhere[] = ['park_id','in',$village_id];
        $carWhere[] = ['is_out','=',0];
        $carNumber = $dbInPark->getColumn($carWhere,'car_number');
        $hasWhere[]= ['c.car_number','in',$carNumber];
        $hasWhere[]= ['c.village_id','in',$village_id];
        $hasWhere[] = ['p.position_pattern','=',1];//固定车位
        $hasCount = $dbHouseVillageParkingCar->getCarNum($hasWhere);//进场车辆已绑定车位的车辆


        //已用车位
        $usedWhere[] = ['park_id','in',$village_id];
        $usedWhere[] = ['is_out','=',0];
        $usedCount = $dbInPark->getCount($usedWhere);
        //剩余车位
        $notCount = $usedCount-$hasCount;//进场车辆没有绑定车位的车辆
        $remain = $positionCount-$bindCarCount-$notCount;
        if($remain<0)//小于0说明车位已满，超出的车辆停的是虚拟车位
        {
            $remain = 0;
        }

        //todo 【新版收费-物业统计停车收费】
        $new_charge_time=$dbChargeTime->get_one(['property_id'=>$property_id]);
        $nowtime = time();
        $parkWhere=$temporaryWhere=[];
        if (!empty($new_charge_time) && $new_charge_time['take_effect_time'] < $nowtime && $new_charge_time['take_effect_time'] > 1) {
            // 本月停车累计收费
            $sTime = strtotime(date('Y-m-01', time()));
            $eTime = strtotime(date('Y-m-t 23:59:59', time()));
            $parkWhere[] = ['pay_time', '>=', $sTime];
            $parkWhere[] = ['pay_time', '<=', $eTime];
            $parkWhere[] = ['property_id', '=', $property_id];
            $parkWhere[] = ['is_paid', '=', 1];
            $parkWhere[] = ['car_type', '=', 'temporary_type'];
            $parkWhere[] = ['order_type', '=', 'park_new'];
            $parkCount=get_number_format($dbHouseNewCashierService->getChargeProjectMoney($parkWhere));

            //当天临时停车累计收费
            $s_Times = strtotime(date('Y-m-d', time()));
            $e_Times = strtotime(date('Y-m-d 23:59:59', time()));
            $temporaryWhere[] = ['pay_time', '>=', $s_Times];
            $temporaryWhere[] = ['pay_time', '<=', $e_Times];
            $temporaryWhere[] = ['property_id', '=', $property_id];
            $temporaryWhere[] = ['is_paid', '=', 1];
            $temporaryWhere[] = ['car_type', '=', 'temporary_type'];
            $temporaryWhere[] = ['order_type', '=', 'park_new'];
            $temporaryMoney=get_number_format($dbHouseNewCashierService->getChargeProjectMoney($temporaryWhere));
        }else{
            $sTime =  strtotime(date('Y-m-01', time()));
            $eTime =  strtotime(date('Y-m-t 23:59:59', time()));
            $parkWhere[] = ['pay_time','>=',$sTime];
            $parkWhere[] = ['pay_time','<=',$eTime];
            $parkWhere[] = ['village_id','in',$village_id];
            $parkWhere[] = ['order_type','=','park'];
            $parkWhere[] = ['order_status','=',1];
            $parkCount = get_number_format($dbHouseVillagePayOrder->sumMoney($parkWhere));//本月停车累计收费

            $s_Times =  strtotime(date('Y-m-d', time()));
            $e_Times =  strtotime(date('Y-m-d 23:59:59', time()));
            $temporaryWhere[] = ['pay_time','>=',$s_Times];
            $temporaryWhere[] = ['pay_time','<=',$e_Times];
            $temporaryWhere[] = ['village_id','in',$village_id];
            $temporaryWhere[] = ['order_type','=','park'];
            $temporaryMoney = get_number_format($dbHouseVillagePayOrder->sumMoney($temporaryWhere));//当天临时停车累计收费
        }



        //占比
        $proportion = $positionCount?round(($usedCount/$positionCount)*100,1).'%':'0%';
        $data =[
            'inCount'=>$inCount,//今日入场车辆
            'outCount'=>$outCount,//今日出场车辆
            'positionCount'=>$positionCount,//总固定车位
            'ownerBindCount'=>$ownerBindCount,//业主绑定车位
            'temporaryCount'=>$temporaryCount,//临时车位
            'usedCount'=>$usedCount,//已用车位
            'remain'=>$remain,//剩余车位
            'parkCount'=>$parkCount,//本月停车累计收费
            'temporaryMoney'=>$temporaryMoney,//当天临时停车累计收费
            'proportion'=>$proportion,
        ];
        //智慧停车设备  显示状态
        $parkDeviceWhere[] = ['village_id','in',$village_id];
        $parkCount = $dbParkPassage->getCount($parkDeviceWhere);
        if($parkCount){
            $data['status'] = 1;
        }else{
            $data['status'] = 0;
        }
        return $data;
    }
    //过滤套餐 2020/11/11 start
    public function getOrderPackage($village_id)
    {
        $servicePackageOrder = new PackageOrderService();
        $dataPackage = $servicePackageOrder->getPropertyOrderPackage('',$village_id);
        if($dataPackage) {
            $dataPackage = $dataPackage->toArray();
            $package_content = $dataPackage['content'];
        }else{
            $package_content = [];
        }
        return $package_content;
    }
    //过滤套餐 2020/11/11 end

    /**
     * 小区车辆进出场记录
     * @author lijie
     * @date_time 2020/01/15
     * @param $where
     * @param bool $field
     * @param $page
     * @param $limit
     * @param string $order
     * @return mixed
     */
    public function getInOutRecord($where,$field=true,$page,$limit,$order='p.id DESC')
    {
        $dbInPark = new InPark();
        $dbOutPark = new OutPark();
        $in_park_list = $dbInPark->getLists($where,'p.car_number,p.in_time as time',$page,$limit,$order,1)->toArray();
        foreach ($in_park_list as $k=>$v){
            $in_park_list[$k]['type'] = '进场';
            $in_park_list[$k]['status'] = '成功';
            $in_park_list[$k]['time_str'] = date('Y-m-d H:i:s',$v['time']);
        }
        $out_park_list = $dbOutPark->getLists($where,'p.car_number,p.out_time as time',$page,$limit,$order,1)->toArray();
        foreach ($out_park_list as $k=>$v){
            $out_park_list[$k]['type'] = '出场';
            $out_park_list[$k]['status'] = '成功';
            $out_park_list[$k]['time_str'] = date('Y-m-d H:i:s',$v['time']);
        }
        $in_park_count = $dbInPark->getCountByVillageId($where);
        $out_park_count = $dbOutPark->getCountByVillageId($where);
        $arr = array_merge($in_park_list,$out_park_list);
        $arr = $this->getRsort($arr,'time');
        $arr = array_slice($arr, ($page-1)*5, 5, true);
        $arr = array_values($arr);
        $data['count'] = $in_park_count+$out_park_count;
        $data['list'] = $arr;
        return $data;
    }

    public function getRsort($list,$field){
        $finishTime = [];
        foreach ($list as $val) {
            $finishTime[] = $val[$field];
        }
        array_multisort($finishTime,SORT_DESC,$list);
        return $list;
    }
    /**
     * 获取进场车辆数量
     * @author lijie
     * @date_time 2020/12/16
     * @param $where
     * @return int
     */
    public function getInParkCount($where)
    {
        $dbInPark = new InPark();
        $count = $dbInPark->getCount($where);
        return $count;
    }

    /**
     * 获取进场车辆数量
     * @author lijie
     * @date_time 2020/12/16
     * @param $where
     * @return int
     */
    public function getOutParkCount($where)
    {
        $dbInPark = new OutPark();
        $count = $dbInPark->getCount($where);
        return $count;
    }

    /**
     * 查询D7停车相关信息
     * @author:zhubaodi
     * @date_time: 2022/8/23 13:47
     */
    public function getD7ParkCount($village_id){
        $db_house_village_park_config=new HouseVillageParkConfig();
        $houseVillageParkGarageConfigDb = new HouseVillageParkGarageConfig();
        $db_house_village_parking_garage=new HouseVillageParkingGarage();
        $db_house_village_parking_car=new HouseVillageParkingCar();
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $garageConfigWhere=array();
        $garageConfigWhere[]=array('village_id','=',$village_id);
        $garageConfigWhere[]=array('park_sys_type','=','D7');
        $garageConfig=$houseVillageParkGarageConfigDb->getOneData($garageConfigWhere,'*','park_versions desc');
        if(empty($garageConfig) || $garageConfig->isEmpty()){
            $park_config=$db_house_village_park_config->getFind(['village_id'=>$village_id]);
            $res=[];
            if (empty($park_config)||$park_config['park_sys_type']!='D7'){
                return $res;
            }
        }
        //一、 车场数据统计
        //车库数量：统计小区的总车场数量；
        $where_garage=[
            'village_id'=>$village_id,
            'status'=>1
        ];
        $garage['garage_count'] = $db_house_village_parking_garage->getCount($where_garage);
        //月租车：统计小区的月租车总数；
        $where_month=[
           ['village_id','=',$village_id],
            ['end_time','>',time()]
        ] ;
        $garage['month_count']=$db_house_village_parking_car->get_village_car_num($where_month);
        
        //在停车辆：统计当前小区已在场车辆总数；
        $where = [];
        $where[] = ['park_id','=',$village_id];
        $where[] = ['accessType','=',1];
        $where[] = ['is_out','=',0];
        $where[] = ['del_time','<',1];
	$where[] = ['park_sys_type','=','D7'] ;
        $garage['inPark_count'] =$db_house_village_car_access_record->getCount($where);
        
        
        //二、车辆类型统计
        
        //车辆类型统计（月租车、储值车）
     
        //月租车：统计小区的月租车总数；
        $car['month_count']=$garage['month_count'];
        //储值车：统计小区的免费车总数；
        $where_stored=[
            ['village_id','=',$village_id],
            ['end_time','<',time()],
            ['stored_balance','>',0]
        ] ;
        $car['stored_count']=$db_house_village_parking_car->get_village_car_num($where_stored);
        // 三、车流量趋势图（近一周）
        //车流量趋势图：统计近一周的车辆进场/出场数据；
        $carFlow['chart_title']=[
            0=>'进场数据',
            1=>'出场数据',
        ];
        $carFlow['chart_x']=[
            0=>date('m-d',(time()-86400*4)),
            1=>date('m-d',(time()-86400*3)),
            2=>date('m-d',(time()-86400*2)),
            3=>date('m-d',(time()-86400*1)),
            4=>date('m-d',time()),
        ];
        $where_inPark=[];
        $begin_time = date('Y-m-d',(time()-86400*4));
        $where_inPark[] = ['accessTime', '>=', strtotime($begin_time)];
        $where_inPark[] = ['accessTime', '<=', time()];
        $where_inPark[] = ['accessType', '=', 1];
        $where_inPark[] = ['park_id', '=', $village_id];
        $group = "FROM_UNIXTIME(accessTime, '%Y-%m-%d')";
        $field="count(record_id) as sum,FROM_UNIXTIME(accessTime, '%Y-%m-%d') as time";
        //入场统计
        $inPark_count =$db_house_village_car_access_record->get_counts($where_inPark,$field,$group);
        $inPark_count_data=[0,0,0,0,0];
       if (!empty($inPark_count)){
           foreach ($inPark_count as $vv){
               $vv['time']=substr($vv['time'],5);
               if ($vv['time']==$carFlow['chart_x'][0]){
                   $inPark_count_data[0]=$vv['sum'];
               }elseif($vv['time']==$carFlow['chart_x'][1]){
                   $inPark_count_data[1]=$vv['sum'];
               }elseif($vv['time']==$carFlow['chart_x'][2]){
                   $inPark_count_data[2]=$vv['sum'];
               }elseif($vv['time']==$carFlow['chart_x'][3]){
                   $inPark_count_data[3]=$vv['sum'];
               }elseif($vv['time']==$carFlow['chart_x'][4]){
                   $inPark_count_data[4]=$vv['sum'];
               }
           }
       }
        $where_outPark=[];
        $where_outPark[] = ['accessTime', '>=', strtotime($begin_time)];
        $where_outPark[] = ['accessTime', '<=', time()];
        $where_outPark[] = ['accessType', '=', 2];
        $where_outPark[] = ['park_id', '=', $village_id];
        //出场统计
        $outPark_count =$db_house_village_car_access_record->get_counts($where_outPark,$field,$group);
        $outPark_count_data=[0,0,0,0,0];
        if (!empty($outPark_count)){
            foreach ($outPark_count as $vv){
                $vv['time']=substr($vv['time'],5);
                    if ($vv['time']==$carFlow['chart_x'][0]){
                        $outPark_count_data[0]=$vv['sum'];
                    }elseif($vv['time']==$carFlow['chart_x'][1]){
                        $outPark_count_data[1]=$vv['sum'];
                    }elseif($vv['time']==$carFlow['chart_x'][2]){
                        $outPark_count_data[2]=$vv['sum'];
                    }elseif($vv['time']==$carFlow['chart_x'][3]){
                        $outPark_count_data[3]=$vv['sum'];
                    }elseif($vv['time']==$carFlow['chart_x'][4]){
                        $outPark_count_data[4]=$vv['sum'];
                    }
            }
           
        }
        
        $carFlow['chart_y']=[
            ['name'=>'进场数据','data'=>$inPark_count_data,'color'=>'#3069e9'],
            ['name'=>'出场数据','data'=>$outPark_count_data,'color'=>'#ff9537'],
        ];
        $carFlow['colorArr']=['#3069e9','#ff9537'];
        //四、通行类型统计
        //通行类型统计（临时车、储值车、月租车)：统计小区的临时车、储值车、月租车进去总次数；
        //备注：储值车待定
        //月租车
        $where = [];
        $where[] = ['park_id','=',$village_id];
        $where[] = ['accessType','=',1];
        $where[] = ['accessMode','=',5];
        $where[] = ['del_time','<',1];
        $month_inPark_count =$db_house_village_car_access_record->getCount($where);
        $where = [];
        $where[] = ['park_id','=',$village_id];
        $where[] = ['accessType','=',2];
        $where[] = ['accessMode','=',5];
        $where[] = ['del_time','<',1];
        $month_outPark_count =$db_house_village_car_access_record->getCount($where);
        

        //临时车
        $where = [];
        $where[] = ['park_id','=',$village_id];
        $where[] = ['accessType','=',1];
        $where[] = ['accessMode','in',[3,4,6,9,7]];
        $where[] = ['del_time','<',1];
        $temp_inPark_count =$db_house_village_car_access_record->getCount($where);
        $where = [];
        $where[] = ['park_id','=',$village_id];
        $where[] = ['accessType','=',2];
        $where[] = ['accessMode','in',[3,4,6,9,7]];
        $where[] = ['del_time','<',1];
        $temp_outPark_count =$db_house_village_car_access_record->getCount($where);

       
        //储值车
        $where = [];
        $where[] = ['park_id','=',$village_id];
        $where[] = ['accessType','=',1];
        $where[] = ['car_type','=','storedCar'];
        $where[] = ['del_time','<',1];
        $stored_inPark_count =$db_house_village_car_access_record->getCount($where);
        $where = [];
        $where[] = ['park_id','=',$village_id];
        $where[] = ['accessType','=',2];
        $where[] = ['car_type','=','storedCar'];
        $where[] = ['del_time','<',1];
        $stored_outPark_count =$db_house_village_car_access_record->getCount($where);
        $max_count=max($month_inPark_count,$month_outPark_count,$temp_inPark_count,$temp_outPark_count,$stored_inPark_count,$stored_outPark_count);
        if ($max_count==0){
            $max_count=1;
        }
        $accessType['month_count']=[
            'label'=> '月租车',
            'list'=>[
                ['rate'=>round_number($month_inPark_count/$max_count,2)*100 .'%', 'value'=> $month_inPark_count, 'bottomColor'=> 'rgba(24,141,240,.5)', 'topColor'=> 'rgba(24,141,240,.9)'],
                ['rate'=> round_number($month_outPark_count/$max_count,2)*100 .'%', 'value'=> $month_outPark_count, 'bottomColor'=>  'rgba(254,190,3,.5)', 'topColor'=> 'rgba(254,190,3,.9)'],
            ]
        ];
        
        $accessType['temp_count']=[
            'label'=> '临时车',
            'list'=>[
                ['rate'=>round_number($temp_inPark_count/$max_count,2)*100 .'%', 'value'=> $temp_inPark_count, 'bottomColor'=> 'rgba(24,141,240,.5)', 'topColor'=> 'rgba(24,141,240,.9)'],
                ['rate'=> round_number($temp_outPark_count/$max_count,2)*100 .'%', 'value'=> $temp_outPark_count, 'bottomColor'=>  'rgba(254,190,3,.5)', 'topColor'=> 'rgba(254,190,3,.9)'],
            ]
        ];
        $accessType['stored_count']=[
            'label'=> '储值车',
            'list'=>[
                ['rate'=>round_number($stored_inPark_count/$max_count,2)*100 .'%', 'value'=> $stored_inPark_count, 'bottomColor'=> 'rgba(24,141,240,.5)', 'topColor'=> 'rgba(24,141,240,.9)'],
                ['rate'=> round_number($stored_outPark_count/$max_count,2)*100 .'%', 'value'=> $stored_outPark_count, 'bottomColor'=>  'rgba(254,190,3,.5)', 'topColor'=> 'rgba(254,190,3,.9)'],
            ]
        ];
        //五、最近入场车辆

        //显示进场的图片信息10秒替换一次，没有新数据不做替换；
        $where = [];
        $where[] = ['park_id','=',$village_id];
        $where[] = ['accessImage|accessBigImage','<>',''];
        $where[]=['accessType','=',1];
        $where[]=['del_time','<',1];
        $field='accessImage,accessBigImage';
        $newInPark['in_list'] =$db_house_village_car_access_record->getList($where,$field,1,15);
        if (!empty($newInPark['in_list'])){
            $newInPark['in_list']=$newInPark['in_list']->toArray();
        }
        if (!empty($newInPark['in_list'])){
           foreach ($newInPark['in_list'] as &$v){
               if(!empty($v['accessImage'])){
                   if (stripos($v['accessImage'], 'http://') !== false||stripos($v['accessImage'], 'https://') !== false){
                     
                   }else{
                       $path=explode('/',$v['accessImage']);
                       if (count($path)>2&&$path[2]=='meeting'){
                           $v['accessImage']=replace_file_domain($v['accessImage']);
                       }else{
                           $v['accessImage'] = cfg('site_url').'/v20/public'.$v['accessImage'];
                       } 
                   }
               }
               if(!empty($v['accessBigImage'])){
                   if (stripos($v['accessBigImage'], 'http://') !== false||stripos($v['accessBigImage'], 'https://') !== false){

                   }else {
                       $path = explode('/', $v['accessBigImage']);
                       if (count($path) > 2 && $path[2] == 'meeting') {
                           $v['accessBigImage'] = replace_file_domain($v['accessBigImage']);
                       } else {
                           $v['accessBigImage'] = cfg('site_url') . '/v20/public' . $v['accessBigImage'];
                       }
                   }
               }
           }
        }
        //六、最近出场车辆
        $where = [];
        $where[] = ['park_id','=',$village_id];
        $where[] = ['accessImage|accessBigImage','<>',''];
        $where[]=['accessType','=',2];
        $where[]=['del_time','<',1];
        $field='accessImage,accessBigImage';
        $newInPark['out_list'] =$db_house_village_car_access_record->getList($where,$field,1,15);
        if (!empty($newInPark['out_list'])){
            $newInPark['out_list']=$newInPark['out_list']->toArray();
        }
        if (!empty($newInPark['out_list'])){
            foreach ($newInPark['out_list'] as &$vv){
                if(!empty($vv['accessImage'])){
                    if (stripos($vv['accessImage'], 'http://') !== false||stripos($vv['accessImage'], 'https://') !== false){

                    }else {
                        $path = explode('/', $vv['accessImage']);
                        if (count($path) > 2 && $path[2] == 'meeting') {
                            $vv['accessImage'] = replace_file_domain($vv['accessImage']);
                        } else {
                            $vv['accessImage'] = cfg('site_url') . '/v20/public' . $vv['accessImage'];
                        }
                    }
                }
                if(!empty($vv['accessBigImage'])){
                    if (stripos($vv['accessBigImage'], 'http://') !== false||stripos($vv['accessBigImage'], 'https://') !== false){

                    }else {
                        $path = explode('/', $vv['accessBigImage']);
                        if (count($path) > 2 && $path[2] == 'meeting') {
                            $vv['accessBigImage'] = replace_file_domain($vv['accessBigImage']);
                        } else {
                            $vv['accessBigImage'] = cfg('site_url') . '/v20/public' . $vv['accessBigImage'];
                        }
                    }
                }
            }
        }
        //显示出场的图片信息10秒替换一次，没有新数据不做替换；
       
        //六、车辆进去实时记录（今日）
        //实时进出场车辆图片和信息（统计当天）：
        $where = [];
        $where[] = ['a.park_id','=',$village_id];
         $where[] = ['a.accessTime','>',strtotime(date('Y-m-d'))];
        //$where[] = ['a.accessTime','>',(time()-86400)];
        $where[]=['a.accessType','=',1];
        $where[]=['a.del_time','<',1];
        $field='a.car_number,a.accessTime,a.channel_id,a.channel_name,b.park_time,b.accessTime as out_accessTime,b.channel_id as out_channel_id,b.channel_name as out_channel_name';
        $parkList['list'] =$db_house_village_car_access_record->getLists($where,$field);
        foreach ($parkList['list'] as &$val){
            if($val['accessTime']>1){
                $val['in_accessTime'] = date('Y-m-d H:i:s',$val['accessTime']);
            }else{
                $val['in_accessTime'] = '--';
            }
            if (!empty($val['channel_name'])){
                $val['in_channel_name']=$val['channel_name'];
            }else{
                $val['in_channel_name']='';
            }
            if( $val['out_accessTime']>1){
                $val['out_accessTime'] = date('Y-m-d H:i:s', $val['out_accessTime']);
            }else{
                $val['out_accessTime'] = '--';
            }
            if (!empty($val['park_time'])){
                $hours=intval($val['park_time']/3600);
                $minter=ceil(($val['park_time']-$hours*3600)/60);
                if ($val['park_time']>=3600){
                    $val['park_time']=$hours.'小时'.$minter.'分钟';
                }else{
                    $val['park_time']=$minter.'分钟';
                }

            }

        }
        //列表展示车牌号、入场通道、入场时间、出场通道、出场时间、停车时间；需要做轮播效果
    
        //备注：这个是展示当天的进出场车辆数据（包含临时车/月租车）
        $res=[
            'garage'=>$garage,
            'car'=>$car, 
            'carFlow'=>$carFlow,
            'accessType'=>$accessType,
            'newInPark'=>$newInPark,
            'parkList'=>$parkList['list'],
        ];
        
        return $res;
    }
    
    
	/**
     * 获取进出场车辆数量
     * @author lijie
     * @date_time 2020/12/16
     * @param $where
     * @return int
     */
    public function getParkCount($where)
    {
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $count =$db_house_village_car_access_record->getCount($where);
        return $count;
    }

}