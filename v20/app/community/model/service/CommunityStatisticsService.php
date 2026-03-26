<?php
namespace app\community\model\service;

use app\community\model\db\HouseNewRepairWorksOrder;
use app\community\model\db\HouseVillagePayOrder;
use app\community\model\db\HouseVillagePileEquipment;
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
use app\community\model\db\HouseVillageParkConfig;
use app\community\model\db\IntelligenceExpress;
use app\community\model\db\ParkPassage;
//人流量
use app\community\model\db\InPark;
use app\community\model\db\HouseVillageParkingCar;
use app\community\model\db\Area;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageVisitor;
use app\community\model\db\HouseVillageRepairList;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseVillagePilePayOrder;
use app\community\model\service\WisdowQrcodeService;
use app\community\model\db\HouseVillageCheckauthApply;
use app\community\model\service\HouseVillageService;
use app\community\model\db\HouseMenuNew;
use think\response\Json;

class CommunityStatisticsService
{
    //首页
    public function getData($property_id,$login_name,$app_type='packapp')
    {
        $dbHouseProperty = new HouseProperty();
        $dbHouseVillage = new HouseVillage();
        $dbHouseVillageInfo = new HouseVillageInfo();
        $dbHouseVillageUserVacancy = new HouseVillageUserVacancy();
        $dbHouseVillageSingle = new HouseVillageSingle();
        $dbHouseVillageParkingPosition = new HouseVillageParkingPosition();
        $service_login = new ManageAppLoginService();
        $house_village_user_bind = new HouseVillageUserBindService();
        $where[] = ['id','=',$property_id];
        $field = 'id,property_name';
        $property_info = $dbHouseProperty->get_one($where,$field);//物业信息

        $prompt = $service_login->time_tip(0,$login_name,$property_info['property_name']);
        $num_where[] = ['property_id','=',$property_id];
        $num_where[] = ['status','=',1];
        $group='city_id';
        $stay_num = $dbHouseVillage->getNum($num_where,$group);//入住城市
        $village_num = $dbHouseVillage->getNum($num_where);//项目数量

        $building_where[] = ['property_id','=',$property_id];
        $building_where[] = ['building_area','>',0];
        $building_area = $dbHouseVillageInfo->getNum($building_where,'building_area');
        $not_where[] = ['property_id','=',$property_id];
//        $not_where[] = ['building_area','<=',0];
        $info_village_id = $dbHouseVillageInfo->getColumn($not_where,'village_id');
//        dump($info_village_id);die;
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
        $residents_num = $dbHouseVillageUserVacancy->getCount($map);
        $single_num = $dbHouseVillageSingle->getCount($map);//楼栋数量
        //住户数量
        $where_owner[] = ['status','=',1];
        //$where_owner[] = ['parent_id','=',0];
        $where_owner[] = ['village_id','in',$village_id_arr];
        $where_owner[] = ['type','in','0,1,2,3'];
        $owner_count = $house_village_user_bind->getUserCount($where_owner);

        $packing_where[] = ['village_id','in',$village_id_arr];
        $packing_where[] = ['position_pattern','=',1];
        $parking_count = $dbHouseVillageParkingPosition->get_village_park_position_num($packing_where);//车位数量

        $house_area = $this->bigDataChange($house_area);
        $list=[
            ['title'=>'入住城市','num'=>$stay_num],
            ['title'=>'项目数量','num'=>$village_num],
            ['title'=>'覆盖面积','num'=>strval($house_area)],
            ['title'=>'楼栋数量','num'=>$single_num],
            ['title'=>'住户数量','num'=>$owner_count],
            ['title'=>'车位数量','num'=>$parking_count],
        ];
        //过滤套餐 2020/11/9 start
        $servicePackageOrder = new PackageOrderService();
        $dataPackage = $servicePackageOrder->getPropertyOrderPackage($property_id,'');
        $dataPackage = $dataPackage->toArray();
        $package_content = $dataPackage['content'];
        //过滤套餐 2020/11/9 end

        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $http = $http_type.$_SERVER['SERVER_NAME'].'/v20/public/static/community/images/statistics_column/';
//        $column_list=[
//            ['ioc'=>$http.'cost.png','title'=>'费用统计','url'=>''],
//            ['ioc'=>$http.'hardware.png','title'=>'硬件统计','url'=>''],
//            ['ioc'=>$http.'people.png','title'=>'人流量统计','url'=>''],
//            ['ioc'=>$http.'parking.png','title'=>'车场统计','url'=>''],
//            ['ioc'=>$http.'work_order.png','title'=>'工单统计','url'=>''],
//            ['ioc'=>$http.'other.png','title'=>'敬请期待','url'=>''],
//        ];
        $site_url = cfg('site_url');
        $base_url = '/packapp/community/';

        if(in_array(5,$package_content)){
            $column_list[] = [
                'ioc'=>$http.'cost.png',
                'title'=>'费用统计',
                'url'=>$site_url . $base_url .'pages/Community/Property/CostStatistics',
                'type'=>'cost'
            ];
        }
        //套餐过滤
        $hardware_intersect = array_intersect([6,7,8,9,10,11],$package_content);
        if($hardware_intersect && count($hardware_intersect)>0) {
            $column_list[] = [
                'ioc' => $http . 'hardware.png',
                'title' => '硬件统计',
                'url' => $site_url . $base_url .'pages/Community/Property/HardwareStatistics',
                'type'=>'hardware'
            ];
        }

        $column_list[] = [
            'ioc'=>$http.'people.png',
            'title'=>'人流量统计',
            'url'=>$site_url . $base_url .'pages/Community/Property/TrafficStatistics',
            'type'=>'people'
        ];
        $column_list[] = [
            'ioc'=>$http.'parking.png',
            'title'=>'车场统计',
            'url'=>$site_url . $base_url .'pages/Community/Property/ParkingStatistics',
            'type'=>'parking'
        ];
        //套餐过滤
        $work_order_intersect = array_intersect([16,18,19],$package_content);
        if($work_order_intersect && count($work_order_intersect)>0) {
            $column_list[] = [
                'ioc' => $http . 'work_order.png',
                'title' => '工单统计',
                'url' => $site_url . $base_url . 'pages/Community/Property/WorkStatistics',
                'type'=>'work_order'
            ];
        }

        if($work_order_intersect && count($work_order_intersect)>0) {
            $column_list[] = [
                'ioc' => $http . 'work_order.png',
                'title' => '工单处理中心',
                'url' => $site_url . $base_url . 'pages/CommunityPages/workOrder/eventList',
                'type'=>'repair_order'
            ];
        }
        $unitRentalArr=array('id'=>'112041','module'=>'UnitRental','status'=>1,'show'=>1);
        $houseMenuNew=new HouseMenuNew();
        $unitRentalObj=$houseMenuNew->getOne($unitRentalArr);
        if($unitRentalObj && !$unitRentalObj->isEmpty()) {
            //todo 公租房模块
            $column_list[] = [
                'ioc' => $http . 'house_public_ruzhu_examine.png',
                'title' => '入住审核',
                'url' => $site_url . $base_url . 'pages/Community/checkoutApplication/leaseCancellation?page_type=checkin',
                'type' => 'house_public_ruzhu_examine'
            ];
            $column_list[] = [
                'ioc' => $http . 'house_public_tuizu_examine.png',
                'title' => '退租审核',
                'url' => $site_url . $base_url . 'pages/Community/checkoutApplication/leaseCancellation?page_type=rentcancell',
                'type' => 'house_public_tuizu_examine'
            ];
            $column_list[] = [
                'ioc' => $http . 'house_public_inspection.png',
                'title' => '验房管理',
                'url' => $site_url . $base_url . 'pages/Community/checkoutApplication/inspectionManagement',
                'type' => 'house_public_inspection'
            ];

        }
       //  if($app_type == 'ios' || $app_type == 'android'){
            // 设置
            $column_list[] = [
                'ioc'=>$http.'setting.png',
                'title'=>'设置',
                'url'=>$site_url . $base_url .'pages/Community/index/setup',
                'type'=>'setting'
            ];
       //  }

        if (!cfg('ComingSoonHide')) {
            $column_list[] = ['ioc'=>$http.'other.png','title'=>'敬请期待','url'=>'','type'=>'other'];
        }

        $data['prompt'] = $prompt;
        $data['list'] = $list;
        $data['column_list'] = $column_list;
        $data['property'] = $property_info;
        return $data;
    }

    //首页 2022-08-18改版
    public function getNewData($property_id=0,$login_name='',$extra_data=array(),$app_type='packapp')
    {
        $site_url = cfg('site_url');
        $base_url = '/packapp/community/';
        $dataArr=array();
        //财务数据
        $menus_village=array();
        if($extra_data['login_role']==4 && isset($extra_data['menus_village']) && !empty($extra_data['menus_village'])){
            $menus_village=$extra_data['menus_village'];
        }
        $dataArr['finance_info']=array('title'=>'财务数据','list'=>array());
        $houseNewPayOrder=new HouseNewPayOrder();
        $dbHouseVillage = new HouseVillage();
        $whereArr=array();
        $whereArr[] = ['property_id','=',$property_id];
        if($extra_data['login_role']==4 && !empty($menus_village)){
            $whereArr[] = ['village_id' ,'in' ,$menus_village];
        }
        $whereArr[] = ['is_paid','=',1];
        $whereArr[] = ['is_discard','=',1];
        $field="sum(ROUND(pay_money,2)) as totalPayMoney";
        //$paidMoney=$houseNewPayOrder->getSum($whereArr,'pay_money');  //已交的
        //$paidMoney=formatNumber($paidMoney, 2, 1);
        $costStatisticsUrl=$site_url.$base_url.'pages/Community/Property/CostStatistics';
        $paiditem=array('icon' =>'', 'title' => '已收费用（万元）', 'url' => $costStatisticsUrl,'num'=>0,'type'=>'order_ispaid_money');
        $paidMoneyObj=$houseNewPayOrder->get_one($whereArr,$field);
        $paidMoney1=0;
        if($paidMoneyObj && !$paidMoneyObj->isEmpty()){
            $paidMoneyArr=$paidMoneyObj->toArray();
            $paidMoney1=$paidMoneyArr['totalPayMoney']/10000;
            $paidMoney1=formatNumber($paidMoney1, 6, 1);
            $paiditem['num']=$paidMoney1;
        }
        
        $nopayitem=array('icon' =>'', 'title' => '剩余费用（万元）', 'url' => $costStatisticsUrl,'num'=>0,'type'=>'order_nopay_money');
        $whereArr=array();
        $whereArr[] = ['property_id','=',$property_id];
        if($extra_data['login_role']==4 && !empty($menus_village)){
            $whereArr[] = ['village_id' ,'in' ,$menus_village];
        }
        $whereArr[] = ['is_paid','=',2];
        $whereArr[] = ['is_discard','=',1];
        $field="sum(ROUND(modify_money,2)) as totalModifyMoney";
        $noPayMoneyObj=$houseNewPayOrder->get_one($whereArr,$field);
        $noPayMoney1=0;
        if($noPayMoneyObj && !$noPayMoneyObj->isEmpty()){
            $noPayMoneyArr=$noPayMoneyObj->toArray();
            $noPayMoney1=$noPayMoneyArr['totalModifyMoney']/10000;
            $noPayMoney1=formatNumber($noPayMoney1, 6, 1);
            $nopayitem['num']=$noPayMoney1;
        }
        $allpayitem=array('icon' =>'', 'title' => '应收费用（万元）', 'url' => $costStatisticsUrl,'num'=>0,'type'=>'order_allpay_money');
        $allpaynum=$paidMoney1+$noPayMoney1;
        $allpaynum=formatNumber($allpaynum, 6, 1);
        $allpayitem['num']=$allpaynum;
        $dataArr['finance_info']['list'][]=$allpayitem;
        $dataArr['finance_info']['list'][]=$paiditem;
        $dataArr['finance_info']['list'][]=$nopayitem;
        
        //月租车
        $monthcaritem=array('icon' =>'', 'title' => '月租车费用（元）', 'url' => '','num'=>0,'type'=>'order_monthcar_money');
        $whereArr=array();
        $whereArr[] = ['property_id','=',$property_id];
        if($extra_data['login_role']==4 && !empty($menus_village)){
            $whereArr[] = ['village_id' ,'in' ,$menus_village];
        }
        $whereArr[] = ['is_paid','=',1];
        $whereArr[] = ['is_discard','=',1];
        $whereArr[] = ['car_type','=','month_type'];
        $field="sum(ROUND(pay_money,2)) as totalPayMoney";
        $carPayMoneyObj=$houseNewPayOrder->get_one($whereArr,$field);
        if($carPayMoneyObj && !$carPayMoneyObj->isEmpty()){
            $carPayMoneyArr=$carPayMoneyObj->toArray();
            $carPayMoney1=formatNumber($carPayMoneyArr['totalPayMoney'], 2, 1);
            $monthcaritem['num']=$carPayMoney1;
        }
        $dataArr['finance_info']['list'][]=$monthcaritem;

        //零食车
        $tempcaritem=array('icon' =>'', 'title' => '临时车费用（元）', 'url' => '','num'=>0,'type'=>'order_tempcar_money');
        $whereArr=array();
        $whereArr[] = ['property_id','=',$property_id];
        if($extra_data['login_role']==4 && !empty($menus_village)){
            $whereArr[] = ['village_id' ,'in' ,$menus_village];
        }
        $whereArr[] = ['is_paid','=',1];
        $whereArr[] = ['is_discard','=',1];
        $whereArr[] = ['car_type','=','temporary_type'];
        $field="sum(ROUND(pay_money,2)) as totalPayMoney";
        $tempCarPayMoneyObj=$houseNewPayOrder->get_one($whereArr,$field);
        if($tempCarPayMoneyObj && !$tempCarPayMoneyObj->isEmpty()){
            $tempCarPayMoneyArr=$tempCarPayMoneyObj->toArray();
            $tempCarPayMoney1=formatNumber($tempCarPayMoneyArr['totalPayMoney'], 2, 1);
            $tempcaritem['num']=$tempCarPayMoney1;
        }
        $dataArr['finance_info']['list'][]=$tempcaritem;
        
        $village_where=array();
        $village_where[] = ['property_id','=',$property_id];
        $village_where[] = ['status','in',array(1,2,3,4)];
        $village_id_arr = $dbHouseVillage->getColumn($village_where,'village_id');
        if($extra_data['login_role']==4 && !empty($menus_village)){
            $village_id_arr=$menus_village;
        }
        //充电桩
        $pilepayitem=array('icon' =>'', 'title' => '充电桩费用（元）', 'url' => '','num'=>0,'type'=>'order_pilepay_money');
        if($village_id_arr){
            $houseVillagePilePayOrder=new HouseVillagePilePayOrder();
            $pile_where=array();
            $pile_where[]=['village_id' ,'in' ,$village_id_arr];
            $pile_where[]=['type', 'in' , [2,21]];
            $pile_where[]=['pay_time', '>' ,100];
            $field="sum(use_money) as total_use_money,sum(refund_money) as total_refund_money";
            $pilePayOrderObj=$houseVillagePilePayOrder->get_one($pile_where,$field);
            if($pilePayOrderObj && !$pilePayOrderObj->isEmpty()){
                $pilePayOrder=$pilePayOrderObj->toArray();
                $pilepay_money=$pilePayOrder['total_use_money']-$pilePayOrder['total_refund_money'];
                $pilepay_money=$pilepay_money>0 ? formatNumber($pilepay_money,2,1):0;
                $pilepayitem['num']=$pilepay_money;
            }
        }
        $dataArr['finance_info']['list'][]=$pilepayitem;
        
        //工作台
        $dataArr['workbench_list']=array('title'=>'工作台','list'=>array());
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        if($extra_data['login_role']==4){
            // 物业普通管理员
            $whereRaw = "a.property_id={$property_id} AND ((a.uid={$extra_data['login_uid']} AND a.order_type=2 AND ((a.event_status>=40 AND a.event_status<50) OR (a.event_status>=70 AND a.event_status<80))) OR (a.event_status>=10 AND a.event_status<20))";
            if(!empty($menus_village)){
                $whereRaw = "a.property_id={$property_id} AND a.village_id in(".implode(',',$menus_village).") AND ((a.uid={$extra_data['login_uid']} AND a.order_type=2 AND ((a.event_status>=40 AND a.event_status<50) OR (a.event_status>=70 AND a.event_status<80))) OR (a.event_status>=10 AND a.event_status<20))";
            } else {
                $whereRaw = "a.property_id={$property_id} AND a.village_id=0 AND ((a.uid={$extra_data['login_uid']} AND a.order_type=2 AND ((a.event_status>=40 AND a.event_status<50) OR (a.event_status>=70 AND a.event_status<80))) OR (a.event_status>=10 AND a.event_status<20))";
            }
        }else {
            // 物业总管理员
            $whereRaw = "a.property_id={$property_id} AND ((a.uid={$extra_data['login_uid']} AND a.order_type=1 AND ((a.event_status>=40 AND a.event_status<50) OR (a.event_status>=70 AND a.event_status<80))) OR (a.event_status>=10 AND a.event_status<20))";
        }

        $work_count = $db_house_new_repair_works_order->getWorkHandleCount([], $whereRaw);
        $work_count=$work_count>0 ? $work_count:0;
        $tmpUrl=$site_url.$base_url.'pages/CommunityPages/workOrder/eventList';
        $repairworkitem=array('icon' =>'https://hf.pigcms.com/static/wxapp/community_index/worl_icon_1.png', 'title' => '待处理工单', 'url' => $tmpUrl,'num'=>$work_count,'type'=>'repair_workorder_num');
        $dataArr['workbench_list']['list'][]=$repairworkitem;
        
        //待巡检任务
        $wisdowQrcodeService=new WisdowQrcodeService();
        $taskCount=$wisdowQrcodeService->getPropertyCheckTask($village_id_arr,$property_id);
        $wisdowqrcodeitem=array('icon' =>'https://hf.pigcms.com/static/wxapp/community_index/worl_icon_2.png', 'title' => '待巡检任务', 'url' => '','num'=>$taskCount,'type'=>'wisdow_qrcode_num');
        $dataArr['workbench_list']['list'][]=$wisdowqrcodeitem;

        $houseVillageCheckauthApply=new HouseVillageCheckauthApply();
        $whereArr=array();
        $whereArr[]=array('property_id','=',$property_id);
        if($extra_data['login_role']==4 && !empty($menus_village)){
            $whereArr[] = ['village_id' ,'in' ,$menus_village];
        }
        $whereArr[]=array('status','in',[0,1]);
        $checkauthApplyCount=$houseVillageCheckauthApply->getCount($whereArr);
        $checkauthApplyCount=$checkauthApplyCount>0 ? $checkauthApplyCount:0;
        $checkauthApplyitem=array('icon' =>'https://hf.pigcms.com/static/wxapp/community_index/worl_icon_3.png', 'title' => '待审批任务', 'url' => '','num'=>$checkauthApplyCount,'type'=>'check_authapply_num');
        $dataArr['workbench_list']['list'][]=$checkauthApplyitem;
        //待审核业主
        $house_village_user_bind = new HouseVillageUserBind();
        $where_owner=array();
        $where_owner[] = ['status','=',2];
        $where_owner[] = ['village_id','in',$village_id_arr];
        $where_owner[] = ['type','in',array(0,3)];
        $owner_count = $house_village_user_bind->getVillageUserNum($where_owner);
        $owner_count=$owner_count>0 ? $owner_count:0;
        $ownerbinditem=array('icon' =>'https://hf.pigcms.com/static/wxapp/community_index/worl_icon_4.png', 'title' => '待审核业主', 'url' => '','num'=>$owner_count,'type'=>'check_ownerbind_num');
        $dataArr['workbench_list']['list'][]=$ownerbinditem;

        //待审核租客
        $house_village_user_bind = new HouseVillageUserBind();
        $where_owner=array();
        $where_owner[] = ['status','=',2];
        $where_owner[] = ['village_id','in',$village_id_arr];
        $where_owner[] = ['type','=',2];
        $owner_count = $house_village_user_bind->getVillageUserNum($where_owner);
        $owner_count=$owner_count>0 ? $owner_count:0;
        $ownerbinditem=array('icon' =>'https://hf.pigcms.com/static/wxapp/community_index/worl_icon_5.png', 'title' => '待审核租客', 'url' => '','num'=>$owner_count,'type'=>'check_tenantbind_num');
        $dataArr['workbench_list']['list'][]=$ownerbinditem;
        //待处理快递
        $service_house_village = new HouseVillageService();
        $where_express = [
            ['status', '=', 0],
            ['village_id','in',$village_id_arr]
        ];
        $express_count = $service_house_village->get_village_express_num($where_express);
        $express_count=$express_count>0 ? $express_count:0;
        $expressitem=array('icon' =>'https://hf.pigcms.com/static/wxapp/community_index/worl_icon_6.png', 'title' => '待处理快递', 'url' => '','num'=>$express_count,'type'=>'village_express_num');
        $dataArr['workbench_list']['list'][]=$expressitem;
        
        //硬件监控
        $dataArr['hardware_list']=array('title'=>'硬件监控','list'=>array());
        $hardwareUrl=$site_url.$base_url.'pages/Community/Property/HardwareStatistics';
        //人脸门禁
        $dbHouseFaceDevice = new HouseFaceDevice();
        $whereArr=array();
        $whereArr[] = ['village_id', 'in', $village_id_arr];
        $whereArr[] = ['is_del','=',0];
        $faceCount = $dbHouseFaceDevice->getCount($whereArr);
        $faceCount=$faceCount>0 ? $faceCount:0;
        $whereArr[] = ['device_status','=',2];
        $faceOffCount = $dbHouseFaceDevice->getCount($whereArr);
        $faceOffCount=$faceOffCount>0 ? $faceOffCount:0;
        $onnum=$faceCount-$faceOffCount;
        $onnum=$onnum>0 ?$onnum:0;
        $deviceitem=array('icon' =>'', 'title' => '人脸设备门禁', 'url' => $hardwareUrl,'num'=>$faceCount,'onnum'=>$onnum,'offnum'=>$faceOffCount,'type'=>'village_facedevice_num');
        $dataArr['hardware_list']['list'][]=$deviceitem;
        
        //蓝牙门禁
        $doorWhere=array();
        $dbHouseVillageDoor = new HouseVillageDoor();
        $doorWhere[] = ['village_id', 'in', $village_id_arr];
        $doorCount = $dbHouseVillageDoor->getCount($doorWhere);//总数量
        $doorCount=$doorCount>0 ?$doorCount:0;
        $doorWhere[] = ['door_status','=',0];
        $doorOffCount = $dbHouseVillageDoor->getCount($doorWhere);//离线数量
        $doorOffCount=$doorOffCount>0 ? $doorOffCount:0;
        $onnum=$doorCount-$doorOffCount;
        $onnum=$onnum>0 ?$onnum:0;
        $doordeviceitem=array('icon' =>'', 'title' => '蓝牙门禁', 'url' => $hardwareUrl,'num'=>$doorCount,'onnum'=>$onnum,'offnum'=>$doorOffCount,'type'=>'village_doordevice_num');
        $dataArr['hardware_list']['list'][]=$doordeviceitem;
        //人脸识别摄像机
        $dbHouseCameraDevice = new HouseCameraDevice();
        $whereCamera=array();
        $whereCamera[]= ['village_id', 'in', $village_id_arr];
        $whereCamera[]= ['device_brand', '<>', 'iSecureCenter'];
        $cameraCount = $dbHouseCameraDevice->getCount($whereCamera);
        $cameraCount =$cameraCount>0 ? $cameraCount:0;
        $whereCamera[] = ['camera_status','<>',0];
        $cameraOffCount = $dbHouseCameraDevice->getCount($whereCamera);
        $cameraOffCount =$cameraOffCount>0 ? $cameraOffCount:0;
        $onnum=$cameraCount-$cameraOffCount;
        $onnum=$onnum>0 ?$onnum:0;
        $cameradeviceitem=array('icon' =>'', 'title' => '人脸设备摄像机', 'url' => $hardwareUrl,'num'=>$cameraCount,'onnum'=>$onnum,'offnum'=>$cameraOffCount,'type'=>'village_cameradevice_num');
        $dataArr['hardware_list']['list'][]=$cameradeviceitem;

        //智慧停车场
        $dbHouseVillageParkConfig = new HouseVillageParkConfig();
        $dbParkPassage = new ParkPassage();
        $parkWhere=array();
        $parkWhere[]= ['village_id', 'in', $village_id_arr];
        $parkWhere[]=array('park_versions','=',2);
        $parkCount = $dbHouseVillageParkConfig->getCount($parkWhere,'park_versions');
        $parkCount=$parkCount>0 ? $parkCount:0;
        $park2Where=array();
        $park2Where[] = ['village_id', 'in', $village_id_arr];
        $park2Where[] = ['status','<>',1];
        $parkOffCount = $dbParkPassage->getCount($park2Where);
        $parkOffCount=$parkOffCount>0 ?$parkOffCount:0;
        $onnum=$parkCount-$parkOffCount;
        $onnum=$onnum>0 ?$onnum:0;
        $parkdeviceitem=array('icon' =>'', 'title' => '停车设备', 'url' => $hardwareUrl,'num'=>$parkCount,'onnum'=>$onnum,'offnum'=>$parkOffCount,'type'=>'village_cameradevice_num');
        $dataArr['hardware_list']['list'][]=$parkdeviceitem;

        //智能充电桩
        $dbHouseSmartCharging = new HouseVillagePileEquipment();
        $smart_where=array();
        $smart_where[] = ['village_id', 'in', $village_id_arr];
        $smart_where[]=array('is_del','=',1);
        $smartCount = $dbHouseSmartCharging->getCount($smart_where);
        $smartCount=$smartCount>0 ? $smartCount:0;
        $parkdeviceitem=array('icon' =>'', 'title' => '智能充电桩', 'url' => $hardwareUrl,'num'=>$smartCount,'onnum'=>$smartCount,'offnum'=>0,'type'=>'village_cameradevice_num');
        $dataArr['hardware_list']['list'][]=$parkdeviceitem;
        //人脸识别摄像机
        $dbHouseCameraDevice = new HouseCameraDevice();
        $whereCamera=array();
        $whereCamera[]= ['village_id', 'in', $village_id_arr];
        $cameraCount = $dbHouseCameraDevice->getCount($whereCamera);
        $cameraCount =$cameraCount>0 ? $cameraCount:0;
        $whereCamera[] = ['camera_status','<>',0];
        $cameraOffCount = $dbHouseCameraDevice->getCount($whereCamera);
        $cameraOffCount =$cameraOffCount>0 ? $cameraOffCount:0;
        $onnum=$cameraCount-$cameraOffCount;
        $onnum=$onnum>0 ?$onnum:0;
        $cameradeviceitem=array('icon' =>'', 'title' => '视频监控', 'url' => $hardwareUrl,'num'=>$cameraCount,'onnum'=>$onnum,'offnum'=>$cameraOffCount,'type'=>'village_monitordevice_num');
        $dataArr['hardware_list']['list'][]=$cameradeviceitem;
        //住户数量
        $dataArr['household_list']=array('title'=>'住户数量','list'=>array());
        $userBindUrl=$site_url.$base_url.'pages/Community/Property/TrafficStatistics';
        // 业主数
        $where_owner = [
            ['village_id', 'in', $village_id_arr],
            ['status','=',1],
            ['type','in',array(0,3)]
        ];
        $owner_count = $house_village_user_bind->getVillageUserNum($where_owner);
        $owner_count=$owner_count>0?$owner_count:0;
        $ownerbinditem=array('icon' =>'', 'title' => '业主数量', 'url' => $userBindUrl,'num'=>$owner_count,'type'=>'village_userowner_num');
        $dataArr['household_list']['list'][]=$ownerbinditem;
        // 家属数
        $where_family = [
            ['village_id', 'in', $village_id_arr],
            ['status','=',1],
            ['type','=',1]
        ];
        $family_count = $house_village_user_bind->getVillageUserNum($where_family);
        $family_count=$family_count>0?$family_count:0;
        $familybinditem=array('icon' =>'', 'title' => '家属数量', 'url' => $userBindUrl,'num'=>$family_count,'type'=>'village_userfamily_num');
        $dataArr['household_list']['list'][]=$familybinditem;
        // 租客数
        $where_tenant = [
            ['village_id', 'in', $village_id_arr],
            ['status','=',1],
            ['type','=',2]
        ];
        $tenant_count = $service_house_village->getVillageUserNum($where_tenant);
        $tenant_count=$tenant_count>0?$tenant_count:0;
        $tenantbinditem=array('icon' =>'', 'title' => '租客数', 'url' => $userBindUrl,'num'=>$tenant_count,'type'=>'village_usertenant_num');
        $dataArr['household_list']['list'][]=$tenantbinditem;
        
        $where=array();
        $where[] = ['id','=',$property_id];
        $field = 'id,account,property_name,property_phone,property_address';
        $dbHouseProperty = new HouseProperty();
        $property_info_obj = $dbHouseProperty->get_one($where,$field);//物业信息
        $property_info=array();
        if($property_info_obj && !$property_info_obj->isEmpty()){
            $property_info=$property_info_obj->toArray();
        }
        $service_login = new ManageAppLoginService();
        $prompt = $service_login->time_tip(0,$login_name,$property_info['property_name']);
        $dataArr['prompt'] = $prompt;
        $dataArr['property'] = $property_info;
        return $dataArr;
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
    public function costStatistics($village_id,$date,$type,$property_id,$app_type,$login_role=3,$menus_village=array())
    {
        $dbHouseVillagePayOrder = new HouseVillagePayOrder();
        $dbPropertyBill = new PropertyBill();
        $dbHouseProperty = new HouseProperty();
        $dbHouseVillage = new HouseVillage();
        if($village_id){
            $where[] = ['village_id','=',$village_id];
            $village_ids[]=$village_id;
        }else{
            $village_id_arr = $dbHouseVillage->getColumn(['property_id'=>$property_id],'village_id');
            if($login_role==4 && !empty($menus_village)){
                $village_id_arr=$menus_village;
            }
            $where[] = ['village_id','in',$village_id_arr];
            $village_ids=$village_id_arr;
        }
        if(!$date){
            $date = date('Y-m',time());
        }
        //todo 【新版收费-物业统计停车收费】
        $dbHouseNewCashierService=new HouseNewCashierService();
        $result=$dbHouseNewCashierService->getChargeProjectType(1,$property_id,0,5);
        $new_charge_data=$result['charge_data'];
        $param_color=[
            '#ff5e2d','#458bfa','#5bddbf','#ffd100','#813cf9','#ff814d'
        ];
        if ($type == 2)//按年
        {
            //总收入/支出
            $time = strtotime($date);
            $start_time = strtotime(date($date.'-01-01'));
            $end_time = date('Y-m-t 23:59:59',$start_time);
            $end_time = strtotime("$end_time +11 month");
            //一年内收入
            $line_list = $this->getMonth($start_time,$property_id,1,$where,$result['status']);
        }else{//按月
            //总收入/支出
            $current_time = $date.'-1';
            $end_time = $current_time.' 23:59:59';
            $start_time = strtotime($current_time);
            $end_time = strtotime("$end_time +1 month -1 day");
            //一个月内收入
            $line_list = $this->getDays($start_time,$property_id,1,$where,$result['status']);
        }
        //支出
        $map[] = ['bill_type','=',1];
        $map[] = ['payment_time','>=',$start_time];
        $map[] = ['payment_time','<=',$end_time];
        $map[] = ['property_id','=',$property_id];
        $total_price = $dbPropertyBill->sumMoney($map,'price');
        if($result['status'] == 1){ //todo 新版收费统计计算
            $param_data=[];
            foreach ($new_charge_data as $k2=>&$v2){
                $where_=[];
                $where_[] = ['is_paid', '=', 1];
                $where_[] = ['pay_time','>=',$start_time];
                $where_[] = ['pay_time','<=',$end_time];
                $where_[] = ['village_id','in',$village_ids];
                if($v2['charge_param'] == 'other' && isset($v2['charge_param_type'])){
                    //todo 针对其他费用单独处理
                    $con = implode(',', $v2['charge_type']);
                    $where_[] = ['order_type', 'not in', $con];
                }else{
                    $where_[] = ['order_type', '=', $v2['charge_type']];
                }
                $price=get_number_format($dbHouseNewCashierService->getChargeProjectMoney($where_));
                $param_data[]=array(
                    'price'=>$price,
                    'name'=>$v2['charge_name'],
                    'color'=>$param_color[$k2]
                );
            }
            //收入
            $where=[];
            $where[] = ['is_paid','=',1];
            $where[] = ['pay_time','>=',$start_time];
            $where[] = ['pay_time','<=',$end_time];
            $where[] = ['village_id','in',$village_ids];
            $total_money = $dbHouseNewCashierService->getChargeProjectMoney($where);
            $ring_list=$param_data;
        }
        else{
            //收入
            $where[] = ['order_status','=',1];
            $where[] = ['pay_time','>=',$start_time];
            $where[] = ['pay_time','<=',$end_time];
            $total_money = $dbHouseVillagePayOrder->sumMoney($where,[],'money');

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
                ['price'=>sprintf("%.2f",$water),'name'=>'水费','color' => '#ff5e2d'],//水费 water
                ['price'=>sprintf("%.2f",$property),'name'=>'物业费','color' => '#458bfa'],//物业费 property
                ['price'=>sprintf("%.2f",$park),'name'=>'停车费','color' => '#5bddbf'],//停车费 electric
                ['price'=>sprintf("%.2f",$electric),'name'=>'电费','color' => '#ffd100'],//电费 electric
                ['price'=>sprintf("%.2f",$gas),'name'=>'燃气费','color' => '#813cf9'],//燃气费 gas
                ['price'=>sprintf("%.2f",$custom),'name'=>'临时费用','color' => '#d645fa'],//自主缴费 《临时费用》 custom
                ['price'=>sprintf("%.2f",$custom_payment),'name'=>'自定义缴费','color' => '#ff814d'],//自定义缴费 custom_payment
            ];

        }
        if($total_money <=0 && ($app_type=='packapp' || $app_type =='wxapp')){
            $ring_list = json([]);
        }
        $property_where[] = ['id','=',$property_id];
        $field = 'id,property_name';
        $property_info = $dbHouseProperty->get_one($property_where,$field);//物业信息

        $data['property'] = $property_info;
        $data['total_money'] = get_number_format($total_money);//总收入
        $data['total_price'] = get_number_format($total_price);//总支出
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
    public function getDays($time,$property_id,$type,$wheres=[],$status=0)
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
                if($status == 1){
                    //todo 新版物业收费年统计
                    $where[] = ['is_paid','=',1];
                    $where[] = ['pay_time', '>=', $start_time];
                    $where[] = ['pay_time', '<=', $end_time];
                    if($wheres && count($wheres)>0){
                        $where[] = $wheres[0];
                    }
                    $dbHouseNewCashierService=new HouseNewCashierService();
                    $money=get_number_format($dbHouseNewCashierService->getChargeProjectMoney($where));
                }
                else{
                    $where[] = ['order_status', '=', 1];
                    $where[] = ['pay_time', '>=', $start_time];
                    $where[] = ['pay_time', '<=', $end_time];
                    if($wheres && count($wheres)>0){
                        $where[] = $wheres[0];
                    }
                    $money = get_number_format( $dbHouseVillagePayOrder->sumMoney($where, [], 'money'));
                }
            }else{//支出
                $map=[];
                $map[] = ['bill_type','=',1];
                $map[] = ['payment_time','>=',$start_time];
                $map[] = ['payment_time','<=',$end_time];
                $map[] = ['property_id','=',$property_id];
                $money = get_number_format($dbPropertyBill->sumMoney($map,'price'));
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
    public function getMonth($time,$property_id,$type,$wheres=[],$status=0)
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
                if($status == 1){
                    //todo 新版物业收费年统计
                    $where[] = ['is_paid','=',1];
                    $where[] = ['pay_time', '>=', $start_time];
                    $where[] = ['pay_time', '<=', $end_time];
                    if($wheres && count($wheres)>0){
                        $where[] = $wheres[0];
                    }
                    $dbHouseNewCashierService=new HouseNewCashierService();
                    $money=get_number_format($dbHouseNewCashierService->getChargeProjectMoney($where));
                }else{
                    $where[] = ['order_status', '=', 1];
                    $where[] = ['pay_time', '>=', $start_time];
                    $where[] = ['pay_time', '<=', $end_time];
                    if($wheres && count($wheres)>0){
                        $where[] = $wheres[0];
                    }
                    $money = get_number_format($dbHouseVillagePayOrder->sumMoney($where, [], 'money'));
                }
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
    public function spendStatistics($village_id,$date,$type,$property_id,$app_type)
    {
        $dbHouseVillagePayOrder = new HouseVillagePayOrder();
        $dbPropertyBill = new PropertyBill();
        $dbHouseProperty = new HouseProperty();
        $dbHouseVillage = new HouseVillage();
        if($village_id){
            $where[] = ['village_id','=',$village_id];
        }else{
            $village_id_arr = $dbHouseVillage->getColumn(['property_id'=>$property_id],'village_id');
            $where[] = ['village_id','in',$village_id_arr];
        }
        if(!$date){
            $date = date('Y-m',time());
        }
        //todo 【新版收费-物业统计停车收费】
        $dbHouseNewCashierService=new HouseNewCashierService();
        $result=$dbHouseNewCashierService->getChargeProjectType(1,$property_id,0,5);
        if ($type == 2)//按年
        {
            //总收入/支出
            $time = strtotime($date);
            $start_time = strtotime(date($date.'-01-01'));
            $end_time = date('Y-m-t 23:59:59',$start_time);
            $end_time = strtotime("$end_time +11 month");
            //一年内收入
            $line_list = $this->getMonth($start_time,$property_id,2,[],$result['status']);// 2=>支出
        }else{//按月
            //总收入/支出
            $current_time = $date.'-1';
            $end_time = $current_time.' 23:59:59';
            $start_time = strtotime($current_time);
            $end_time = strtotime("$end_time +1 month -1 day");
            //一个月内收入
            $line_list = $this->getDays($start_time,$property_id,2,[],$result['status']);// 2=>支出
        }

        if($result['status'] == 1){ //todo 新版收费统计计算
            //收入
            $where[] = ['is_paid','=',1];
            $where[] = ['pay_time','>=',$start_time];
            $where[] = ['pay_time','<=',$end_time];
            if($village_id){
                $where[] = ['village_id', '=', $village_id];
            }else{
                $where[] = ['property_id', '=', $property_id];
            }
            $total_money = $dbHouseNewCashierService->getChargeProjectMoney($where);

        }else{
            //收入
            $where[] = ['order_status','=',1];
            $where[] = ['pay_time','>=',$start_time];
            $where[] = ['pay_time','<=',$end_time];
            $total_money = $dbHouseVillagePayOrder->sumMoney($where,[],'money');
        }

        //支出
        $map[] = ['bill_type','=',1];
        $map[] = ['payment_time','>=',$start_time];
        $map[] = ['payment_time','<=',$end_time];
        $map[] = ['property_id','=',$property_id];

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
        if($total_price<=0 && ($app_type == 'packapp' || $app_type =='wxapp')){
            $ring_list = json([]);
        }
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
    public function getHouseVillageList($property_id,$menus_village=array(),$login_role=3)
    {
        $dbHouseVillage = new HouseVillage();
        $where[] = ['property_id','=',$property_id];
        if($login_role==4 && !empty($menus_village)){
            $where[] = ['village_id','in',$menus_village];
        }
        $where[] = ['status','=',1];
        $field = 'village_id,village_name,property_id';
        $list = $dbHouseVillage->getList($where,$field);
        if($list){
            $list=$list->toarray();
            $test_cate_list = array(
                'property_id'=>0,
                'village_id'=>'',
                'village_name'=>'全部小区'
            );
            array_unshift($list,$test_cate_list);
        }
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
    public function getHardware($property_id,$village_id,$app_type,$login_role=3,$menus_village=array())
    {
        $dbHouseVillage = new HouseVillage();
        $dbHouseFaceDevice = new HouseFaceDevice();
        $dbHouseCameraDevice = new HouseCameraDevice();
        $dbHouseVillageDoor = new HouseVillageDoor();
        $dbIntelligenceExpress = new IntelligenceExpress();
        $dbParkPassage = new ParkPassage();
        $dbHouseVillageParkConfig = new HouseVillageParkConfig();
        $dbHouseSmartCharging = new HouseVillagePileEquipment();
        if(!$village_id) {
            $village_where[] = ['property_id', '=', $property_id];
            $village_where[] = ['status', '=', 1];
            $village_id_arr = $dbHouseVillage->getColumn($village_where, 'village_id');
            if($login_role==4 && !empty($menus_village)){
                $village_id_arr = $menus_village;
            }
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
        $map[]= $public_where;
        $map[]= ['device_brand', '<>', 'iSecureCenter'];
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
        //智慧停车场
        $parkWhere[] = $public_where;
        $parkWhere[]=array('park_versions','=',2);
        $parkCount = $dbHouseVillageParkConfig->getCount($parkWhere,'park_versions');

        $park2Where[] = $public_where;
        $park2Where[] = ['status','<>',1];
        $parkOffCount = $dbParkPassage->getCount($park2Where);
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
        $smart_where[]=array('is_del','=',1);
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
        //视频监控 包含人脸摄像机
        $map=array();
        $map[]= $public_where;
        $cameraCount = $dbHouseCameraDevice->getCount($map);
        $map[] = ['camera_status','<>',0];
        $cameraOffCount = $dbHouseCameraDevice->getCount($map);
        if($cameraCount) {
            $data['list'][] = [
                'title' => '视频监控',
                'total' => $cameraCount,
                'normal' => $cameraCount ? round((($cameraCount - $cameraOffCount) / $cameraCount) * 100) : 0,//正常设备占比
                'off' => $cameraCount ? round(($cameraOffCount / $cameraCount) * 100) : 0,//离线设备占比
                'fault' => 0,//故障数量（暂定0）
            ];
        }
        $total = $doorCount+$faceCount+$cameraCount+$arkCount+$parkCount+$smartCount;//所有设备总数量
        $total_off = $doorOffCount+$faceOffCount+$cameraOffCount+$arkOffCount+$parkOffCount;//所有离线设备数量
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $http = $http_type.$_SERVER['SERVER_NAME'].'/v20/public/static/community/images/statistics_column/';
        if($total>0) {
            $ratio_normal = $total ? round((($total - $total_off) / $total) * 100,1) : 0;//正常总占比
            $ratio_fault = 0;//故障总占比
            $ratio_off = $total ? round(($total_off / $total) * 100,1) : 0;//离线总占比
            $data['info'] = [
                ['title' => '设备总数', 'device_total' => $total,'ioc'=>$http.'device.png'],
                ['title' => '正常', 'device_total' => $ratio_normal?$ratio_normal.'%':'0%','ioc'=>$http.'normal.png'],//ratio_normal
                ['title' => '故障', 'device_total' => $ratio_fault?$ratio_fault.'%':'0%','ioc'=>$http.'fault.png'], //ratio_fault
                ['title' => '离线', 'device_total' => $ratio_off?$ratio_off.'%':'0%','ioc'=>$http.'off.png'],//ratio_off
            ];
        }
        if ( ($app_type=='packapp' || $app_type =='wxapp') && (!isset($data['info']) || !$data['info'])) {
            $data['info'] = json([]);
        }
        if ( ($app_type=='packapp' || $app_type =='wxapp') && (!isset($data['list']) || !$data['list'])) {
            $data['list'] = json([]);
        }
        $data['public_where']=$public_where;
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
    public function getParking($property_id,$village_id,$app_type)
    {
        $dbHouseVillageParkingPosition = new HouseVillageParkingPosition();
        $dbHouseVillage = new HouseVillage();
        $dbInPark = new InPark();
        $dbHouseVillageParkingCar = new HouseVillageParkingCar();
        $dbParkPassage = new ParkPassage();          //智慧停车场设备
        $relevance_where=array();
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
        $parkDeviceWheres[] = ['village_id','=',$village_id];
        $parkCountStatus = $dbParkPassage->getCount($parkDeviceWheres);
        if($parkCountStatus) {
            $positionWhere[] = ['position_pattern', '=', 1];
        }
        $positionCount = $dbHouseVillageParkingPosition->get_village_park_position_num($positionWhere);//总固定车位

        $ownerWhere[] =$relevance_where;
        if($parkCountStatus) {
            $ownerWhere[] = ['p.position_pattern', '=', 1];//固定车位
        }
        $ownerBindCount = $dbHouseVillageParkingPosition->getBindPosition($ownerWhere); //业主绑定车位

        $temWhere[] = $public_where;
        if($parkCountStatus) {
            $temWhere[] = ['position_pattern', '=', 1];//固定车位
        }
        $temWhere[] = ['position_type','=',3];//临停车位
        $temporaryCount = $dbHouseVillageParkingPosition->get_village_park_position_num($temWhere);//临时车位

        $carWhere[] = $public_map;
        $carWhere[] = ['is_out','=',0];
        $carNumber = $dbInPark->getColumn($carWhere,'car_number');
        $hasWhere[]= ['c.car_number','in',$carNumber];
        if($parkCountStatus) {
            $hasWhere[] = ['p.position_pattern', '=', 1];//固定车位
        }
        $hasCount = $dbHouseVillageParkingCar->getCarNum($hasWhere);//进场车辆已绑定车位的车辆

        //获取已绑定车辆的车位
        $bindCarWhere[] = $relevance_where;
        if($parkCountStatus) {
            $bindCarWhere[] = ['p.position_pattern', '=', 1];
        }
        $bindCarCount=0;
        if($relevance_where['1']=='in' && is_array($relevance_where['2'])){
            $village_ids = array_unique($relevance_where['2']);
            foreach ($village_ids as $vid) {
                if ($vid > 0) {
                    $bindCarWhere = array();
                    $bindCarWhere[] = ['p.village_id', '=', $vid];
                    if($parkCountStatus) {
                        $bindCarWhere[] = ['p.position_pattern', '=', 1];
                    }
                    $carCount = $dbHouseVillageParkingPosition->getBindCarCount($bindCarWhere);
                    $carCount = $carCount > 0 ? $carCount : 0;
                    $bindCarCount += $carCount;
                }
            }
        }else{
            $bindCarCount = $dbHouseVillageParkingPosition->getBindCar($bindCarWhere);
        }
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
        if($positionCount>0) {
            if($app_type == 'packapp' || $app_type =='ios' || $app_type =='wxapp') {
                $data['parking'] = [
                    'positionCount' => $positionCount,//总固定车位
                    'ownerBindCount' => $ownerBindCount,//业主绑定车位
                    'temporaryCount' => $temporaryCount,//临时车位
                    'usedCount' => $usedCount,//已用车位
                    'remain' => $remain,//剩余车位
                ];
            }else{
                $data['parking'] = [
                    'positionCount' => $positionCount,//总固定车位
                    'chart'=>[
                        ['name'=>'业主车位','color'=>'#2581f2','ratio'=>$ownerBindCount],
                        ['name'=>'临时车位','color'=>'#f6ca57','ratio'=>$temporaryCount],
                        ['name'=>'已用车位','color'=>'#e26cf7','ratio'=>$usedCount],
                        ['name'=>'剩余车位','color'=>'#2fc9b1','ratio'=>$remain],
                    ]
                ];
            }
        }else{
            //if($app_type=='packapp' || $app_type =='wxapp') {
                $data['parking'] = json([]);
            //}
        }
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
    public function getTraffic($property_id,$village_id,$app_type,$login_role=3,$menus_village=array())
    {
        $dbHouseVillage = new HouseVillage();
        $dbHouseVillageUserBind = new HouseVillageUserBind();
        $dbHouseVillageVisitor = new HouseVillageVisitor();
        $dbArea = new Area();
        if(!$village_id) {
            $village_where[] = ['property_id', '=', $property_id];
            $village_where[] = ['status', '=', 1];
            $village_id_arr = $dbHouseVillage->getColumn($village_where, 'village_id');
            if($login_role==4 && !empty($menus_village)){
                $village_where[] = ['village_id','in',$menus_village];
                $village_id_arr = $menus_village;
            }
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
        $map[] = ['type','in',[0,3]];
        $owner_count = $dbHouseVillageUserBind->getVillageUserNum($map);//业主
        unset($map[2]);
        unset($map[3]);
        $map[] = ['type','=',2];
        $tenants_count = $dbHouseVillageUserBind->getVillageUserNum($map);//租客
        $map = array_values($map);
        unset($map[2]);
        unset($map[3]);
        $map[] = ['type','=',4];
        $work_count = $dbHouseVillageUserBind->getVillageUserNum($map);//工作人员（服务人员）

        $visitor_where[] = $public_where;
        $visitor_where[] = ['status','in',[1,2,4]];
        $visitor_count = $dbHouseVillageVisitor->getCount($visitor_where);//所有访客
        //饼状图统计数据
        if($app_type == 'packapp' || $app_type =='wxapp') {
            $pie_chart = [
                'total_count'=>$owner_count+$tenants_count+$visitor_count+$work_count,
                'owner_count'=>$owner_count,//业主
                'tenants_count'=>$tenants_count,//租客
                'visitors_count'=>$visitor_count,//访客
                'service_member_count'=>$work_count,//服务人员
            ];
            if($pie_chart['total_count']<=0){
                $pie_chart = json([]);
            }
        }elseif($app_type == 'ios'){
            if(($owner_count + $tenants_count + $visitor_count + $work_count)>0) {
                $pie_chart = [
                    'total_count' => $owner_count + $tenants_count + $visitor_count + $work_count,
                    'chart' => [
                        ['color' => '#00D674', 'than' => $owner_count, 'name' => '业主'],
                        ['color' => '#8A5966', 'than' => $tenants_count, 'name' => '租客'],
                        ['color' => '#9ACD32', 'than' => $visitor_count, 'name' => '访客'],
                        ['color' => '#F0AD4E', 'than' => $work_count, 'name' => '服务人员'],
                    ]
                ];
            }else{
                $pie_chart = [
                    'total_count' => $owner_count + $tenants_count + $visitor_count + $work_count,
                    'chart' => [],
                ];
            }
        }else{
            //android
            if(($owner_count + $tenants_count + $visitor_count + $work_count)>0) {
                $pie_chart = [
                    'total_count' => $owner_count + $tenants_count + $visitor_count + $work_count,
                    'chart' => [
                        ['color' => '#00D674', 'than' => $owner_count, 'name' => '业主'],
                        ['color' => '#8A5966', 'than' => $tenants_count, 'name' => '租客'],
                        ['color' => '#9ACD32', 'than' => $visitor_count, 'name' => '访客'],
                        ['color' => '#F0AD4E', 'than' => $work_count, 'name' => '服务人员'],
                    ]
                ];
            }else{
                $pie_chart = json([]);
            }
        }
        $city_schedule_chart = [];
        //进度条统计
        if(!$village_id) {
            $villageList = $dbHouseVillage->getList($village_where, 'village_id,city_id');
            $villageList = $villageList->toArray();
//            dd($villageList);
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
                $map = [];
                $map[] = ['village_id', 'in', $val];
                $map[] = ['status', '=', 1];
                $map[] = ['type', '=', 2];
                $city_tenants_count = $dbHouseVillageUserBind->getVillageUserNum($map);//租客
                $map = [];
                $map[] = ['village_id', 'in', $val];
                $map[] = ['status', '=', 1];
                $map[] = ['type', '=', 4];
                $city_work_count = $dbHouseVillageUserBind->getVillageUserNum($map);//工作人员（服务人员）

                $visitor_where[] = ['village_id', 'in', $val];
                $visitor_where[] = ['status', '=', 1];
                $city_visitor_count = $dbHouseVillageVisitor->getCount($visitor_where);//所有访客
                $total_count = $city_owner_count+$city_tenants_count+$city_work_count+$city_visitor_count;
                if($total_count) {
//                    $city_schedule_chart[$key]['title'] = $new_arr[$key];//城市名称
                    $barList = [];
//                    if($city_owner_count) {
                        $barList[] = [
                            'color' => '#00D674',
                            'place' => $total_count ? round(($city_owner_count / $total_count) * 100,2) : 0,
                            'name' => '业主',
                            'city_owner_count'=>$city_owner_count,
                        ];
//                    }

//                    if($city_tenants_count) {
                        $barList[] = [
                            'color' => '#8A5966',
                            'place' => $total_count ? round(($city_tenants_count / $total_count) * 100,2) : 0,
                            'name' => '租客',
                            'city_tenants_count'=>$city_tenants_count,
                        ];
//                    }
//                    if($city_visitor_count) {
                        $barList[] = [
                            'color' => '#9ACD32',
                            'place' => $total_count ? round(($city_visitor_count / $total_count) * 100,2) : 0,
                            'name' => '访客',
                            'city_visitor_count'=>$city_visitor_count,
                        ];
//                    }
//                    if($city_work_count) {
                        $barList[] = [
                            'color' => '#F0AD4E',
                            'place' => $total_count ? round(($city_work_count / $total_count) * 100,2) : 0,
                            'name' => '服务人员',
                            'city_work_count' => $city_work_count
                        ];
//                    }
                    $city_schedule_chart[] = [
                        "title"=>$new_arr[$key],
                        "barList"=>$barList,
                    ];
                }
//                $city_schedule_chart[$key]['city_owner_ratio'] = $total_count?round(($city_owner_count/$total_count) *100):0;//业主
//                $city_schedule_chart[$key]['city_tenants_ratio'] = $total_count?round(($city_tenants_count/$total_count) *100):0;//租客
//                $city_schedule_chart[$key]['city_visitors_ratio'] = $total_count?round(($city_visitor_count/$total_count) *100):0;//访客
//                $city_schedule_chart[$key]['city_service_member_ratio'] = $total_count?round(($city_work_count/$total_count) *100):0;//服务人员
            }
        }
        $data['color'] = [['name'=>'业主','color'=>'#00D674'],['name'=>'租客','color'=>'#8A5966'],['name'=>'访客','color'=>'#9ACD32'],['name'=>'服务人员','color'=>'#F0AD4E']];
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
    public function getRepairOrder($property_id,$village_id,$app_type)
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
        //$where[] = ['uid','<>',0];//用户uid
        $where[] = ['status','=',0];//待处理
        $pending_count = $dbHouseVillageRepairList->get_repair_list_num($where,'');
        unset($where[1]);
        $where[] = ['status','in',[1,2]];//处理中
        $being_processed_count = $dbHouseVillageRepairList->get_repair_list_num($where,'');
        $where = array_values($where);
        unset($where[1]);
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
                if($total_count) {
//                    $city_schedule_chart[$key]['title'] = $new_arr[$key];//城市名称
                    $barList = [];
//                    if($city_pending_count) {
//                            $barList[] = [
//                                'color' => '#00D674',
//                                'place' => $total_count ? round(($city_pending_count / $total_count) * 100) : 0,
//                                'name' => '待处理'
//                            ];
//                    }
                    $barList[] = [
                        'color' => '#00D674',
                        'place' => ($total_count && $city_pending_count) ? round(($city_pending_count / $total_count) * 100) : 0,
                        'name' => '待处理'
                    ];
//                    if($city_being_processed_count) {
//                        $barList[] = [
//                            'color' => '#8A5966',
//                            'place' => $total_count ? round(($city_being_processed_count / $total_count) * 100) : 0,
//                            'name' => '处理中'
//                        ];
//                    }
                    $barList[] = [
                        'color' => '#8A5966',
                        'place' => ($total_count && $city_being_processed_count) ? round(($city_being_processed_count / $total_count) * 100) : 0,
                        'name' => '处理中'
                    ];
//                    if($city_processed_count) {
//                        $barList[] = [
//                            'color' => '#9ACD32',
//                            'place' => $total_count ? round(($city_processed_count / $total_count) * 100) : 0,
//                            'name' => '已处理'
//                        ];
//                    }
                    $barList[] = [
                        'color' => '#9ACD32',
                        'place' => ($total_count && $city_processed_count) ? round(($city_processed_count / $total_count) * 100) : 0,
                        'name' => '已处理'
                    ];
                    $city_schedule_chart[] = [
                        "title"=>$new_arr[$key],
                        "barList"=>$barList,
                    ];
                }
//                $city_schedule_chart[$key]['city_pending_count']=$total_count?round(($city_pending_count/$total_count)*100):0;//待处理工单数据
//                $city_schedule_chart[$key]['city_being_processed_count']=$total_count?round(($city_being_processed_count/$total_count)*100):0;//处理中工单数据
//                $city_schedule_chart[$key]['city_processed_count']=$total_count?round(($city_processed_count/$total_count)*100):0;//已处理工单数据
            }
        }
        $data['color'] = [['name'=>'待处理','color'=>'#00D674'],['name'=>'处理中','color'=>'#8A5966'],['name'=>'已处理','color'=>'#9ACD32']];
        $data['city_schedule_chart'] = $city_schedule_chart;//进度条统计
        if($app_type == 'packapp' || $app_type =='wxapp') {
            $data['pie_chart'] = [//圆环统计
                'total_count' => $pending_count + $being_processed_count + $processed_count,//总数量
                'pending_count' => $pending_count,//待处理工单数据
                'being_processed_count' => $being_processed_count,//处理中工单数据
                'processed_count' => $processed_count,//已处理工单数据
            ];
            if(($pending_count+$being_processed_count+$processed_count)<=0){
                $data['pie_chart'] = json([]);
            }
        }elseif($app_type == 'ios'){
            if( ($pending_count + $being_processed_count + $processed_count) > 0) {
                $data['pie_chart'] = [
                    'total_count' => $pending_count + $being_processed_count + $processed_count,
                    'chart' => [
                        ['color' => '#00D674', 'than' => $pending_count, 'name' => '待处理工单'],
                        ['color' => '#8A5966', 'than' => $being_processed_count, 'name' => '处理中工单'],
                        ['color' => '#9ACD32', 'than' => $processed_count, 'name' => '已处理工单'],
                    ]
                ];
            }else{
                $data['pie_chart'] = [
                    'total_count' => $pending_count + $being_processed_count + $processed_count,
                    'chart' => [],
                ];
            }
        }else{
            // android
            if( ($pending_count + $being_processed_count + $processed_count)>0) {
                $data['pie_chart'] = [
                    'total_count' => $pending_count + $being_processed_count + $processed_count,
                    'chart' => [
                        ['color' => '#00D674', 'than' => $pending_count, 'name' => '待处理工单'],
                        ['color' => '#8A5966', 'than' => $being_processed_count, 'name' => '处理中工单'],
                        ['color' => '#9ACD32', 'than' => $processed_count, 'name' => '已处理工单'],
                    ]
                ];
            }else{
                $data['pie_chart'] =json([]);
            }
        }

        return $data;
    }

    public function bigDataChange($num) {
        if (floatval($num)>=10000) {
            $changeNum = round_number($num/10000) . '万';
        } else {
            $changeNum = round_number($num);
        }
        return $changeNum;
    }
}