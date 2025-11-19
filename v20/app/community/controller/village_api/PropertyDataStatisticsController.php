<?php


namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\service\HouseFaceDeviceService;
use app\community\model\service\HouseUserLogService;
use app\community\model\service\HouseVillageOrderService;
use app\community\model\service\HouseVillageRepairListService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\HouseVillageParkingService;
use app\community\model\service\ParkService;
use app\community\model\service\HouseVillageVisitorService;
use app\community\model\service\HousePropertyService;
use app\community\model\service\HouseNewCashierService;
use app\community\model\service\CommunityStatisticsService;
use app\community\model\service\HouseNewRepairService;
use app\community\model\service\AreaService;

class PropertyDataStatisticsController extends CommunityBaseController
{
    public function topIndex()
    {
        /**
         * 物业可视化大数据顶部数据统计
         * @author lijie
         * @date_time 2020/12/04
         */
        $property_id = $this->adminUser['property_id'];
        if(!$property_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $service_house_village = new HouseVillageService();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_village_parking = new HouseVillageParkingService();
        $village_list = $service_house_village->getList(['property_id'=>$property_id],'village_id')->toArray();
        if(empty($village_list)){
            return api_output_error(1001,'该物业没有小区');
        }
        $village_arr = [];
        foreach ($village_list as $v){
            $village_arr[] = $v['village_id'];
        }
        $area_num = $service_house_village->getAreaNum([['village_id','in',$village_arr],['status','=',1]]); //入驻城市
        $village_num = $service_house_village->getVillageNum([['village_id','in',$village_arr],['status','=',1]]);  //项目数量
        $size_sum = $service_house_village->getAreaSize([['village_id','in',$village_arr]],'building_area');  //覆盖面积
        $user_bind_num = $service_house_village_user_bind->getUserCount([['village_id','in',$village_arr],['status','=',1],['type','in',[0,1,2,3]]]);//住户数量
//        $parking_position_num = $service_house_village_parking->get_village_park_position_num([['village_id','in',$village_arr],['position_pattern','=',1]]);  //车位数量

        $positionWhere[] = ['pp.village_id','in',$village_arr];
        $positionWhere[] = ['pg.status','=',1];
        $positionWhere[] = ['pp.position_pattern', '=', 1];
        $parking_position_num = (new HouseVillageParkingPosition())->getCountss($positionWhere);//车位数量

        $dbHouseVillageUserVacancy = new HouseVillageUserVacancy();
        $vacancy=array();
        $vacancy[] = ['village_id','in',$village_arr];
        $vacancy[] = ['is_del','=',0];
        $vacancy[] = ['status','in',array(1,3)];
        $house_size = $dbHouseVillageUserVacancy->getSum($vacancy,'housesize');
        $size_sum = $size_sum+$house_size;//覆盖面积
        $communityStatisticsService =new CommunityStatisticsService();
        $size_sum = $communityStatisticsService->bigDataChange($size_sum);
        $data = [
            ['title'=>'入驻城市','value'=>$area_num,'logo'=>cfg('site_url').'/v20/public/static/community/images/top_1.png'],
            ['title'=>'项目数量','value'=>$village_num,'logo'=>cfg('site_url').'/v20/public/static/community/images/top_2.png'],
            ['title'=>'覆盖面积','value'=>$size_sum.'m²','logo'=>cfg('site_url').'/v20/public/static/community/images/top_3.png'],
            ['title'=>'住户数量','value'=>$user_bind_num,'logo'=>cfg('site_url').'/v20/public/static/community/images/top_4.png'],
            ['title'=>'车位数量','value'=>$parking_position_num,'logo'=>cfg('site_url').'/v20/public/static/community/images/top_5.png'],
        ];
        return api_output(0,$data);
    }

    /**
     * 收费统计
     * @author lijie
     * @date_time 2020/12/04
     * @return \json
     */
    public function chargeStatistics()
    {
        $property_id = $this->adminUser['property_id'];
        if(!$property_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $service_house_village = new HouseVillageService();
        $village_list = $service_house_village->getList(['property_id'=>$property_id],'village_id')->toArray();
        if(empty($village_list)){
            return api_output_error(1001,'该物业没有小区');
        }
        $village_arr = [];
        foreach ($village_list as $v){
            $village_arr[] = $v['village_id'];
        }
        $date = $this->get_weeks(time(),'Y-m-d');
        $data = array();
        $house_village_pay_order = new HouseVillageOrderService();

        //todo 【新版收费-物业统计停车收费】
        $dbHouseNewCashierService=new HouseNewCashierService();
        $result=$dbHouseNewCashierService->getChargeProjectType(1,$property_id);
        $new_charge_status=$result['status'];
        $new_charge_data=$result['charge_data'];
        $new_charge_type=$result['charge_type'];

        foreach ($date as $k=>$v){
            $where = array();
            $where1 = array();
            $where2 = array();
            $where3 = array();
            if($new_charge_status == 0){
                //todo 物业旧版收费
                $where[] = ['pay_time','between',[strtotime($v),strtotime($v)+(23*60+59)*60+59]];
                $where[] = ['paid','=',1];
                $where[] = ['village_id','in',$village_arr];
                $where[] = ['order_status','=',1];
                $where1[] = ['order_type','=','property'];
                $where2[] = ['order_type','=','park'];
                $where3[] = ['order_type','in','custom_payment,custom,water,electric,gas'];
                $property_price = $house_village_pay_order->getSumMoney($where,$where1,'money');
                $parking_price = $house_village_pay_order->getSumMoney($where,$where2,'money');
                $custom_price = $house_village_pay_order->getSumMoney($where,$where3,'money');
                $data['list'][$k]['property_price'] = $property_price;
                $data['list'][$k]['parking_price'] = $parking_price;
                $data['list'][$k]['custom_price'] = $custom_price;
            }else{
                //todo 物业新版收费
                if($new_charge_data){
                    $chart_type=[
                        0=>'property_price',
                        1=>'parking_price',
                        2=>'custom_price'
                    ];
                    $other_tmp=0;
                    foreach ($new_charge_data as $k2=>$v2){
                        $where=[];
                        $where[] = ['pay_time', 'between', [strtotime($v), strtotime($v) + (23 * 60 + 59) * 60 + 59]];
                        $where[] = ['property_id', '=', $property_id];
                        $where[] = ['is_paid', '=', 1];
                        if($v2['charge_param'] == 'other'  && isset($v2['charge_param_type']) && ($v2['charge_param_type']==1)){
                            $v2['charge_type']=is_array($v2['charge_type'])?$v2['charge_type']:array($v2['charge_type']);
                            $where[] = ['order_type', 'not in', $v2['charge_type']];
                        }elseif(is_array($v2['charge_type'])){
                            $where[] = ['order_type', 'in', $v2['charge_type']];
                        }else{
                            $where[] = ['order_type', '=', $v2['charge_type']];
                        }
                        /*
                        if($v2['charge_param'] == 'other' && isset($v2['charge_param_type'])){
                            //todo 针对其他费用单独处理
                            $con = implode(',', $v2['project_id']);
                            $where[] = ['project_id', 'not in', $con];
                        }else{
                            $where[] = ['project_id', '=', $v2['project_id']];
                        }
                        */
                        $tmpMoney=get_number_format($dbHouseNewCashierService->getChargeProjectMoney($where));
                        $tmpMoney=round_number($tmpMoney);
                        $tmpMoney=$tmpMoney>0 ?$tmpMoney:0;
                        $data['list'][$k][$chart_type[$k2]]=$tmpMoney;
                    }
                }
            }
            $data['list'][$k]['time'] = date('m-d',strtotime($v));

        }
        $month_start = mktime(0,0,0,date('m'),1,date('Y'));
        $where = array();
        if($new_charge_status == 0){
            $where[] = ['pay_time','>=',$month_start];
            $where[] = ['paid','=',1];
            $where[] = ['village_id','in',$village_arr];
            $where[] = ['order_status','=',1];
            $property_price = $house_village_pay_order->getSumMoney($where,[['order_type','=','property']],'money');
            $parking_price = $house_village_pay_order->getSumMoney($where,[['order_type','=','park']],'money');
            $custom_price = $house_village_pay_order->getSumMoney($where,[['order_type','in','custom_payment,custom,water,electric,gas']],'money');
            $data['total_money'] = $property_price+$parking_price+$custom_price;
            $data['type'] = [['name'=>'物业收入','color'=>''],['name'=>'停车费收入','color'=>''],['name'=>'其他收入','color'=>'']];
        }else{
            //todo  物业新版收费月总收入
            $where[] = ['pay_time', '>=', $month_start];
            $where[] = ['is_paid', '=', 1];
            $where[] = ['property_id', '=', $property_id];
            $data['total_money'] =get_number_format($dbHouseNewCashierService->getChargeProjectMoney($where));
            $data['type'] = $new_charge_type;
        }

        $date = $this->get_weeks(time(),'m-d');
        $data['date'] = $date;
        return api_output(0,$data,'获取成功');
    }

    /**
     * 获取最近一周的日期
     * @author lijie
     * @date_time 2020/08/04 9:56
     * @param string $time
     * @param string $format
     * @return array
     */
    public function get_weeks($time = '', $format='Y-m-d')
    {
        $time = $time != '' ? $time : time();
        //组合数据
        $date = [];
        for ($i=1; $i<=7; $i++){
            $date[$i-1] = date($format ,strtotime( '+' . $i-7 .' days', $time));
        }
        return $date;
    }

    /**
     * 获取各个区域报修数量和人员数量
     * @author lijie
     * @date_time 2020/12/05
     * @return \json
     */
    public function workOrder()
    {
        $property_id = $this->adminUser['property_id'];
        if(!$property_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $service_house_village = new HouseVillageService();
        $village_list = $service_house_village->getList(['property_id'=>$property_id,'status'=>1],'village_id,village_name,city_id,area_id,street_id,community_id')->toArray();
        if(empty($village_list)){
            return api_output_error(1001,'该物业没有小区');
        }
        //$village_arr = [];
        $houseNewRepairService=new HouseNewRepairService();
        $worker = array();
        $cityArr=array();
        $areaArr=array();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_village_visitor = new HouseVillageVisitorService();
        $areaService=new AreaService();
        $person = array();
        $data = array('worker'=>array(),'person'=>array());
        foreach ($village_list as $kk=>$v){
            //$village_arr[] = $v['village_id'];
            
            $whereArr=[['o.village_id','=',$v['village_id']],['o.event_status','in',[20,30]]];
            $processing_count =$houseNewRepairService->getOrderCount($whereArr);

            $whereArr=[['o.village_id','=',$v['village_id']],['o.event_status','in',[40,60,70]]];
            $processed_count=$houseNewRepairService->getOrderCount($whereArr);

            $whereArr=[['o.village_id','=',$v['village_id']],['o.event_status','in',[10]]];
            $untreated_count=$houseNewRepairService->getOrderCount($whereArr);
            $worker[$kk]['title'] = $v['village_name'];
            $worker[$kk]['processing_count'] = $processing_count;
            $worker[$kk]['untreated_count'] = $untreated_count;
            $worker[$kk]['processed_count'] = $processed_count;
            if($v['city_id']){
                $owner_count = $service_house_village_user_bind->getVillageUserBindNum([['a.type','in',[0,3]],['a.status','=',1],['v.city_id','=',$v['city_id']],['v.village_id','=',$v['village_id']]]); //业主数量
                $tenant_count = $service_house_village_user_bind->getVillageUserBindNum([['a.type','=',2],['a.status','=',1],['v.city_id','=',$v['city_id']],['v.village_id','=',$v['village_id']]]); //租客数量
                $service_count = $service_house_village_user_bind->getVillageUserBindNum([['a.type','=',4],['a.status','=',1],['v.city_id','=',$v['city_id']],['v.village_id','=',$v['village_id']]]); //服务人员数量
                $visitor_count = $service_house_village_visitor->getVisitorNum([['a.status','in',[1,2,4]],['v.city_id','=',$v['city_id']],['v.village_id','=',$v['village_id']]]);
                if(isset($cityArr[$v['city_id']])){
                    $cityArr[$v['city_id']]['owner_count']+=$owner_count;
                    $cityArr[$v['city_id']]['tenant_count']+=$tenant_count;
                    $cityArr[$v['city_id']]['service_count']+=$service_count;
                    $cityArr[$v['city_id']]['visitor_count']+=$visitor_count;
                    if($v['area_id']){
                        $cityArr[$v['city_id']]['area_ids'][]=$v['area_id'];
                    }
                }else{
                    $cityArr[$v['city_id']]=array('city_id'=>$v['city_id'],'title'=>'','owner_count'=>$owner_count,'tenant_count'=>$tenant_count,'service_count'=>$service_count,'visitor_count'=>$visitor_count,'area_ids'=>array());
                    if($v['area_id']){
                        $cityArr[$v['city_id']]['area_ids'][]=$v['area_id'];
                    }
                }
            }
            if($v['area_id']>0){
                $owner_count = $service_house_village_user_bind->getVillageUserBindNum([['a.type','in',[0,3]],['a.status','=',1],['v.area_id','=',$v['area_id']],['v.village_id','=',$v['village_id']]]); //业主数量
                $tenant_count = $service_house_village_user_bind->getVillageUserBindNum([['a.type','=',2],['a.status','=',1],['v.area_id','=',$v['area_id']],['v.village_id','=',$v['village_id']]]); //租客数量
                $service_count = $service_house_village_user_bind->getVillageUserBindNum([['a.type','=',4],['a.status','=',1],['v.area_id','=',$v['area_id']],['v.village_id','=',$v['village_id']]]); //服务人员数量
                $visitor_count = $service_house_village_visitor->getVisitorNum([['a.status','in',[1,2,4]],['v.area_id','=',$v['area_id']],['v.village_id','=',$v['village_id']]]);
                if(isset($areaArr[$v['area_id']])){
                    $areaArr[$v['area_id']]['owner_count']+=$owner_count;
                    $areaArr[$v['area_id']]['tenant_count']+=$tenant_count;
                    $areaArr[$v['area_id']]['service_count']+=$service_count;
                    $areaArr[$v['area_id']]['visitor_count']+=$visitor_count;
                }else{
                    $areaArr[$v['area_id']]=array('area_id'=>$v['area_id'],'title'=>'','owner_count'=>$owner_count,'tenant_count'=>$tenant_count,'service_count'=>$service_count,'visitor_count'=>$visitor_count);
                }
            }
        }
        if($cityArr){
            foreach ($cityArr as $kk=>$vv){
                $area_ids=$vv['area_ids'] ? array_unique($vv['area_ids']):array();
                $areaInfo=$areaService->getOneAreaData(array('area_id'=>$vv['city_id']),'area_id,area_name');
                $vv['title']=$areaInfo ? $areaInfo['area_name']:'';
                $person[]=$vv;
                if($area_ids){
                    sort($area_ids,SORT_NUMERIC);
                    foreach ($area_ids as $a_id){
                        if(isset($areaArr[$a_id])){
                            $areaInfo=$areaService->getOneAreaData(array('area_id'=>$a_id),'area_id,area_name');
                            $areaArr[$a_id]['title']=$areaInfo ? $areaInfo['area_name']:'';
                            $person[]= $areaArr[$a_id];
                        }
                    }
                }
            }
        }
        /*
            $area_list = $service_house_village->getVillageArea([['v.village_id','in',$village_arr],['v.city_id','<>',''],['v.status','=',1]],'v.city_id,a.area_name','v.city_id','v.village_name')->toArray();
            $start_time = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1, date("Y")));
            $house_village_repair = new HouseVillageRepairListService();
            foreach ($area_list as $k=>$v){
                //$processing_count = $house_village_repair->getAreaRepairCount([['r.status', 'in', [1,2]],['a.area_id', '=', $v['city_id']],['v.village_id', 'in', $village_arr],['v.status','=',1]]);//处理中数
                //$untreated_count = $house_village_repair->getAreaRepairCount([['r.status', '=', 0],['a.area_id', '=', $v['city_id']],['v.village_id', 'in', $village_arr],['v.status','=',1]]);//未处理数
                //$processed_count = $house_village_repair->getAreaRepairCount([['r.status', 'in', [3,4]],['a.area_id', '=', $v['city_id']],['v.village_id', 'in', $village_arr],['v.status','=',1]]);//已处理
                $owner_count = $service_house_village_user_bind->getVillageUserBindNum([['a.type','in',[0,3]],['a.status','=',1],['v.city_id','=',$v['city_id']],['v.village_id','in',$village_arr]]); //业主数量
                $tenant_count = $service_house_village_user_bind->getVillageUserBindNum([['a.type','=',2],['a.status','=',1],['v.city_id','=',$v['city_id']],['v.village_id','in',$village_arr]]); //租客数量
                $service_count = $service_house_village_user_bind->getVillageUserBindNum([['a.type','=',4],['a.status','=',1],['v.city_id','=',$v['city_id']],['v.village_id','in',$village_arr]]); //服务人员数量
                $visitor_count = $service_house_village_visitor->getVisitorNum([['a.status','in',[1,2,4]],['v.city_id','=',$v['city_id']],['v.village_id','in',$village_arr]]);
                $person[$k]['title'] = $v['area_name'];
                $person[$k]['owner_count'] = $owner_count;
                $person[$k]['tenant_count'] = $tenant_count;
                $person[$k]['service_count'] = $service_count;
                $person[$k]['visitor_count'] = $visitor_count;
            }
        */
        if($worker){
            $data['worker'] = $worker; 
        }
        if($person){
            $data['person'] = array_reverse($person);
        }
        return api_output(0,$data,'获取成功');
    }

    /**
     * 设备数量
     * @author lijie
     * @date_time 2020/12/05
     * @return \json
     */
    public function deviceStatistics()
    {
        $property_id = $this->adminUser['property_id'];
        if(!$property_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $service_house_village = new HouseVillageService();
        $whereArr=array();
        $whereArr[]=['property_id','=',$property_id];
        $whereArr[]=['status','in',array(0,1,3,4)];
        $village_list = $service_house_village->getList($whereArr,'village_id')->toArray();
        if(empty($village_list)){
            return api_output_error(1001,'该物业没有小区');
        }
        $village_arr = [];
        foreach ($village_list as $v){
            $village_arr[] = $v['village_id'];
        }
        $serviceHouseFaceDevice = new HouseFaceDeviceService();
        //所有设备信息列表
        $data = $serviceHouseFaceDevice->getDeviceList($village_arr);
        return api_output(0,$data);
    }

    /**
     * 车场数据
     * @author lijie
     * @date_time  2020/12/05
     * @return \json
     */
    public function carStatistics()
    {
        $property_id = $this->adminUser['property_id'];
        if(!$property_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $service_house_village = new HouseVillageService();
        $whereArr=array();
        $whereArr[]=['property_id','=',$property_id];
        $whereArr[]=['status','in',array(0,1,3,4)];
        $village_list = $service_house_village->getList($whereArr,'village_id')->toArray();
        if(empty($village_list)){
            return api_output_error(1001,'该物业没有小区');
        }
        $village_arr = [];
        foreach ($village_list as $v){
            $village_arr[] = $v['village_id'];
        }
        $startTime = strtotime(date('Y-m-d',time()));
        $endTime = strtotime(date('Y-m-d 23:59:59',time()));
        $servicePark = new ParkService();
        $info = $servicePark->parkData($property_id,$village_arr,$startTime,$endTime);
        return api_output(0,$info);
    }

    /**
     * 今日人流量
     * @author lijie
     * @date_time 2020/12/05
     * @return \json
     */
    public function peopleFlow()
    {
        $property_id = $this->adminUser['property_id'];
        if(!$property_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $service_house_village = new HouseVillageService();
        $service_house_village_visitor = new HouseVillageVisitorService();
        $whereArr=array();
        $whereArr[]=['property_id','=',$property_id];
        $whereArr[]=['status','in',array(0,1,3,4)];
        $village_list = $service_house_village->getList($whereArr,'village_id')->toArray();
        if(empty($village_list)){
            return api_output_error(1001,'该物业没有小区');
        }
        $village_arr = [];
        foreach ($village_list as $v){
            $village_arr[] = $v['village_id'];
        }
        $serviceHouseUserLog = new HouseUserLogService();
        $startTime = strtotime(date('Y-m-d',time()));
        $endTime = strtotime(date('Y-m-d 23:59:59',time()));
        //今日开门 start
        $map[] = ['log_time','>=',$startTime];
        $map[] = ['log_time','<=',$endTime];
        $map[] = ['log_business_id','in',$village_arr];
        $map[] = ['log_status','=',0];
        $map[] = ['log_from','in',[1,2]];
        $peopleCount = $serviceHouseUserLog->getOpenDoorNum($map);
        $where[] = ['a.pass_time','>=',$startTime];
        $where[] = ['a.pass_time','<=',$endTime];
        $where[] = ['a.status','in',[1,2,4]];
        $where[] = ['a.village_id','in',$village_arr];
        $visitorCount = $service_house_village_visitor->getVisitorNum($where);
        $data['people_flow'] = $peopleCount;
        $data['visitor_flow'] = $visitorCount;
        return api_output(0,$data);
    }

    public function carFlow()
    {
        $property_id = $this->adminUser['property_id'];
        if(!$property_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $service_house_village = new HouseVillageService();
        $whereArr=array();
        $whereArr[]=['property_id','=',$property_id];
        $whereArr[]=['status','in',array(0,1,3,4)];
        $village_list = $service_house_village->getList($whereArr,'village_id')->toArray();
        if(empty($village_list)){
            return api_output_error(1001,'该物业没有小区');
        }
        $village_arr = [];
        foreach ($village_list as $v){
            $village_arr[] = $v['village_id'];
        }
        $start_time = strtotime(date("Y-m-d"),time());
        $service_house_village_parking = new HouseVillageParkingService();
        $month_parking_car_list = $service_house_village_parking->getParkingCarLists([['village_id','in',$village_arr],['end_time','>',time()]],'car_number,province',0)->toArray();  //月租车
        $store_parking_car_list = $service_house_village_parking->getParkingCarLists([['village_id','in',$village_arr],['stored_card','<>','']],'car_number,province',0)->toArray();  //储值车
        $temporary_parking_car_list = $service_house_village_parking->getParkingCarLists([['village_id','in',$village_arr],['stored_card','=',null],['end_time','=',null]],'car_number,province',0)->toArray(); //临时车
        $store_parking_car_arr = array();
        $month_parking_car_arr = array();
        $temporary_parking_car_arr = array();
        /*if($month_parking_car_list){
            foreach ($month_parking_car_list as $v){
                $month_parking_car_arr[] = $v['province'].$v['car_number'];
            }*/
            $serve_park = new ParkService();
            $where = [];
            $where[] = ['park_id','in',$village_arr];
            $where[] = ['accessMode','=',5];
            $where[]=['accessType','=',1];
            $where[]=['del_time','<',1];
            $where[]=['accessTime','>',strtotime(date('Y-m-d'))];
            $month_parking_car_in_count = $serve_park->getParkCount($where);
            $where = [];
            $where[] = ['park_id','in',$village_arr];
            $where[] = ['accessMode','=',5];
            $where[]=['accessType','=',2];
            $where[]=['del_time','<',1];
            $where[]=['accessTime','>',strtotime(date('Y-m-d'))];
            $month_parking_car_out_count = $serve_park->getParkCount($where);
           //  $month_parking_car_in_count = $serve_park->getInParkCount([['car_number','in',$month_parking_car_arr],['in_time','>',$start_time]]);
           //  $month_parking_car_out_count = $serve_park->getOutParkCount([['o.car_number','in',$month_parking_car_arr],['o.out_time','>',$start_time]]);
            $month_parking_car_count = $month_parking_car_in_count+$month_parking_car_out_count;//今日月租车车流量
        /*}else{
            $month_parking_car_count = 0;
        }*/
        if($store_parking_car_list){
            foreach ($store_parking_car_list as $v){
                $store_parking_car_arr[] = $v['province'].$v['car_number'];
            }
            $serve_park = new ParkService();
            $store_parking_car_in_count = $serve_park->getInParkCount([['car_number','in',$store_parking_car_arr],['in_time','>',$start_time]]);
            $store_parking_car_out_count = $serve_park->getOutParkCount([['o.car_number','in',$store_parking_car_arr],['o.out_time','>',$start_time]]);
            $store_parking_car_count = $store_parking_car_in_count+$store_parking_car_out_count;//今日储值车车流量
        }else{
            $store_parking_car_count = 0;
        }
        /*if($temporary_parking_car_list){
            foreach ($temporary_parking_car_list as $v){
                $temporary_parking_car_arr[] = $v['province'].$v['car_number'];
            }*/
            $serve_park = new ParkService();
            $where = [];
            $where[] = ['park_id','in',$village_arr];
            $where[] = ['accessMode','in',[3,4,6,9,7]];
            $where[]=['accessType','=',1];
            $where[]=['del_time','<',1];
            $where[]=['accessTime','>',strtotime(date('Y-m-d'))];
            $temporary_parking_car_in_count = $serve_park->getParkCount($where);
            $where = [];
            $where[] = ['park_id','in',$village_arr];
            $where[] = ['accessMode','in',[3,4,6,9,7]];
            $where[]=['accessType','=',2];
            $where[]=['del_time','<',1];
            $where[]=['accessTime','>',strtotime(date('Y-m-d'))];
            $temporary_parking_car_out_count = $serve_park->getParkCount($where);
            
            
            //$temporary_parking_car_in_count = $serve_park->getInParkCount([['car_number','in',$temporary_parking_car_arr],['in_time','>',$start_time]]);
            // $temporary_parking_car_out_count = $serve_park->getOutParkCount([['o.car_number','in',$temporary_parking_car_arr],['o.out_time','>',$start_time]]);
            $temporary_parking_car_count = $temporary_parking_car_in_count+$temporary_parking_car_out_count;//今日临时车车流量
        /*}else{
            $temporary_parking_car_count = 0;
        }*/
        if($month_parking_car_count+$store_parking_car_count+$temporary_parking_car_count == 0){
            $month_parking_car_count_rate = $store_parking_car_count_rate = $temporary_parking_car_count_rate = 0;
        }else{
            $all_count = $month_parking_car_count+$store_parking_car_count+$temporary_parking_car_count;
            $month_parking_car_count_rate = round($month_parking_car_count/$all_count*100,1);  //月租车比例
            $store_parking_car_count_rate = round($store_parking_car_count/$all_count*100,1);  //储值车比例
            $temporary_parking_car_count_rate = round($temporary_parking_car_count/$all_count*100,1); //临时车比例
        }
        $data['month_parking_car_count'] = $month_parking_car_count;
        $data['store_parking_car_count'] = $store_parking_car_count;
        $data['temporary_parking_car_count'] = $temporary_parking_car_count;
        $data['month_parking_car_count_rate'] = $month_parking_car_count_rate;
        $data['store_parking_car_count_rate'] = $store_parking_car_count_rate;
        $data['temporary_parking_car_count_rate'] = $temporary_parking_car_count_rate;
        return api_output(0,$data);
    }

    /**
     * 获取物业信息
     * @author lijie
     * @date_time 2020/12/14
     * @return \json
     */
    public function getConfig()
    {
        $property_id = $this->adminUser['property_id'];
        if(!$property_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $service_house_property = new HousePropertyService();
        $property_info = $service_house_property->getFind(['id'=>$property_id],'property_name,property_address,property_logo,long,lat');
        if(empty($property_info['long'])){
            $service_house_village = new HouseVillageService();
            $village_info = $service_house_village->getHouseVillageInfo([['property_id','=',$property_info],['long','<>','']],'long,lat');
            $property_info['long'] = $village_info['long'];
            $property_info['lat'] = $village_info['lat'];
        }
        if(empty($property_info['property_logo'])){
            $property_info['property_logo'] = cfg('site_url').'/v20/public/static/community/images/property_logo.png';
        }else{
            $property_info['property_logo'] = replace_file_domain($property_info['property_logo']);
        }
        return api_output(0,$property_info);
    }
}