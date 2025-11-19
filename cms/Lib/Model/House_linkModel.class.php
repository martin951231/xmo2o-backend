<?php
class House_linkModel extends Model{

    //todo 小区后台 小区功能库  $village_id 0:默认所有
    public function village_library($village_id=0,$config,$modules,$str='shequ.php',$property_id=0){
        $base_url = D('House_village')->base_url;
        $street_url = D('House_village')->street_url;
        $db_house_village = D('House_village');
        $app_version = intval($_POST['app_version']);
        $deviceId = trim($_POST['Device-Id']);
        $carPile=D('Config_customization')->getCarPileJudge();
        $param = [
            'pageName' => 'villagelist',
            'isAll' => true,
            'urlParam' => "",
        ];
        $villagelistUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
        $param = [
            'pageName' => 'bindFamily',
            'isAll' => true,
            'urlParam' => "",
        ];
        $bindFamilyUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
        $param = [
            'pageName' => 'myVillage',
            'isAll' => true,
            'urlParam' => "",
        ];
        $myVillageUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
        $param = [
            'pageName' => 'payMentlist',
            'isAll' => true,
            'urlParam' => "",
        ];
        $payMentlistUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
        $param = [
            'pageName' => 'independentPayement',
            'isAll' => true,
            'urlParam' => "",
        ];
        $independentPayementUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
        $param = [
            'pageName' => 'lifepayment',
            'isAll' => true,
            'urlParam' => "",
        ];
        $lifepaymentUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
        $lifepaymentUrl=$config['site_url'] .$base_url .'pages/houseMeter/NewCollectMoney/preStorage';
        $param = [
            'pageName' => 'waterReportlist',
            'isAll' => true,
            'urlParam' => "",
        ];
        
        $waterReportlistUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
        $param = [
            'pageName' => 'expressManagement',
            'isAll' => true,
            'urlParam' => "",
        ];
        $expressManagementUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
        $param = [
            'pageName' => 'visitorRegistration',
            'isAll' => true,
            'urlParam' => "",
        ];
        $visitorRegistrationUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
        $param = [
            'pageName' => 'faceAccessControl',
            'isAll' => true,
            'urlParam' => "",
        ];
        $faceAccessControlUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
        $param = [
            'pageName' => 'hawkEye',
            'isAll' => true,
            'urlParam' => "",
        ];
        $hawkEyeUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
        $is_close_help_info=0;
        if($village_id>0){
            $village_info=D('House_village_info')->getVillageInfo(['village_id'=>$village_id],'help_info');
            if($village_info && $village_info['help_info']){
                $help_info=unserialize($village_info['help_info']);
                if($help_info && isset($help_info['is_close'])){
                    $is_close_help_info=$help_info['is_close'];
                }
            }
        }
        $is_tmp_sub=1;
        if($village_id<1){
            $is_tmp_sub=0;
        }
        $t = array(
//            array('module' => 'Home', 'linkcode' => str_replace($str, 'wap.php', U('Wap/Home/index', '', true, false, true)), 'name'=>'平台首页','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>$modules['Home'],'askeyword'=>1),

            array('module' => 'Housevillage', 'linkcode' => $config['site_url'] . $base_url . 'pages/village_menu/index', 'name'=>$modules['Housevillage'], 'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),

            array('module' => 'HousevillageList', 'linkcode' => $villagelistUrl, 'name'=>$modules['HousevillageList'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),

            array('module' => 'HouseRide', 'linkcode' => str_replace($str, 'wap.php', U('Wap/Ride/ride_list', array('village_id'=>$village_id), true, false, true)),'name'=>$modules['HouseRide'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'HousephoneIndex', 'linkcode' => $config['site_url'] . $base_url . 'pages/village/commonly_used/house_phone?village_id='.$village_id, 'name'=>$modules['HousephoneIndex'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),

            array('module' => 'HousevillageNewsCatelist', 'linkcode' => $config['site_url'] .$base_url . 'pages/village/index/notice?village_id=' . $village_id, 'name'=>$modules['HousevillageNewsCatelist'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),


            //todo 场馆预约
            array('module' => 'HouseVenue', 'linkcode' => $config['site_url'] .$base_url . 'pages/houseMeter/venuerese/venuelist?village_id=' . $village_id,'name'=>$modules['HouseVenue'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),

            //todo 装修申请单
            array('module' => 'RenovationApplyList', 'linkcode' => $config['site_url'] .$base_url . 'pages/village/index/notice?village_id=' . $village_id, 'name'=>$modules['RenovationApplyList'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),


           //  array('module' => 'Member', 'linkcode' => str_replace($str, 'wap.php', U('Wap/My/index', '', true, false, true)),'name'=>$modules['Member'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Member', 'linkcode' => $config['site_url'] .$base_url .'pages/village_menu/my','name'=>$modules['Member'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),


            array('module' => 'HousevillageMy', 'linkcode' => $config['site_url'] .$base_url . 'pages/village_menu/my', 'name'=>$modules['HousevillageMy'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),


            //array('module' => 'HousevillageMyPaylists', 'linkcode' => str_replace($str, 'wap.php', U('Wap/House/village_my_paylists', array('village_id'=>$village_id), true, false, true)),'name'=>$modules['HousevillageMyPaylists'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'HousevillageMyBindFamilyAdd', 'linkcode' => $bindFamilyUrl,'name'=>$modules['HousevillageMyBindFamilyAdd'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'HousevillageMyBindFamilyList', 'linkcode' => $myVillageUrl,'name'=>$modules['HousevillageMyBindFamilyList'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),

            array('module' => 'HouseserviceserviceCategory', 'linkcode' => $config['site_url'] . $base_url . 'pages/village_menu/convenient?village_id='.$village_id, 'name'=>$modules['HouseserviceserviceCategory'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),

            array('module' => 'HousevillageActivity', 'linkcode' => str_replace($str, 'wap.php', U('Wap/House/village_activitylist',array('village_id'=>$village_id) , true, false, true)),'name'=>$modules['HousevillageActivity'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            //小区缴费 start 2020/9/27 (注释) 移动到收费管理
            //array('module' => 'HousePay', 'linkcode' => str_replace($str, 'wap.php', $config['site_url'] . $base_url.'pages/village/my/payMentlist?village_id=' . $village_id),'name'=>$modules['HousePay'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            //小区缴费 end 2020/9/27 (注释)
//            array('module' => 'HousevillageManager', 'linkcode' => str_replace($str, 'wap.php', U('Wap/House/village_manager_list',array('village_id'=>$village_id) , true, false, true)),'name'=>$modules['HousevillageManager'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),



            array('module' => 'MyVillage', 'linkcode' => $myVillageUrl,'name'=>$modules['MyVillage'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            
            // 2020/9/27 (注释) start 移动到收费管理
            //预存物业费
            //array('module' => 'pre_storage_property', 'linkcode' => str_replace($str, 'wap.php', $config['site_url'].$base_url . 'pages/village/my/lifepayment?village_id='.$village_id),'name'=>$modules['pre_storage_property'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            //自主缴费
            //array('module' => 'custom', 'linkcode' => str_replace($str, 'wap.php', $config['site_url'].$base_url . 'pages/village/my/independentPayement?village_id='.$village_id),'name'=>$modules['custom'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            // 2020/9/27 (注释) end
        );
        if($is_tmp_sub){
            $t[]=  array('module' => 'WorkOrder', 'linkcode' => $config['site_url'] . $base_url.'pages/houseMeter/workOrder/workOrder','name'=>$modules['WorkOrder'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }else{
            $t[]=  array('module' => '', 'linkcode' => $config['site_url'] . $base_url.'pages/houseMeter/workOrder/workOrder','name'=>$modules['WorkOrder'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        
        if($carPile){
            // 扫一扫
            $t[]= array('module' => 'scanCode', 'linkcode' =>$config['site_url'] .$base_url . 'pages/newCharge/pages/scanCode','name'=>'扫一扫','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            // 汽车充电桩
            $t[]=array('module' => 'newCharge', 'linkcode' =>$config['site_url'] .$base_url . 'pages/newCharge/index','name'=>'汽车充电桩','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            //个人余额
            $t[]=array('module' => 'personCenter', 'linkcode' =>$config['site_url'] .$base_url . 'pages/newCharge/pages/personCenter?village_id=' . $village_id,'name'=>'个人余额','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            //充电桩订单
            $t[]=array('module' => 'carPileOrderList', 'linkcode' =>$config['site_url'] .$base_url . 'pages/newCharge/pages/orderList?village_id=' . $village_id,'name'=>'充电桩订单','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
  
        }
        //todo 寻求帮助
        if($is_close_help_info<1){
            $houseAskHelpUrl = $config['site_url'] .$base_url . 'pages/houseMeter/workOrder/dayNight';
            if ($village_id > 0) {
                $houseAskHelpUrl .= '?village_id=' . $village_id;
            }
            $t[]=array('module' => 'HouseAskHelp', 'linkcode' => $houseAskHelpUrl,'name'=>$modules['HouseAskHelp'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if($village_id == 0){
            $application_arr=[];
        }else{
            $application_arr = D('Application_list')->get_application_all_auth($village_id);
        }


        //收费管理 start 2020/9/27
        if(in_array(33,$application_arr) || ($village_id == 0)){
            //todo 新版物业功能库
            if($property_id > 0){

                $is_new_charge=D('House_village')->checkNewCharge($property_id);
                if($is_new_charge){
                    //缴费
                    /*
                    *$hzhou_grapefruit_order=D('Config_customization')->getHzhouGrapefruitOrderJudge();
                    */
                    $hzhou_grapefruit_order=1;
                    $tmpLinkUrl=$config['site_url'].$base_url . 'pages/houseMeter/NewCollectMoney/liveExpenses?village_id='.$village_id;
                    if($hzhou_grapefruit_order){
                        $tmpLinkUrl=$config['site_url'].$base_url . 'pages/houseMeter/NewCollectMoney/newLiveExpenses?village_id='.$village_id;
                    }
                    $tmpLinkUrl=str_replace('shequ.php', 'wap.php',$tmpLinkUrl);
                    $t[] = array('module' => 'HousevillageProperty', 'linkcode' => $tmpLinkUrl,'name'=>$modules['HousevillageProperty'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
                }else{
                    //小区缴费
                    $t[] = array('module' => 'HousePay', 'linkcode' => $payMentlistUrl.'?village_id=' . $village_id,'name'=>$modules['HousePay'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
                    //自主缴费
                    $t[] = array('module' => 'custom', 'linkcode' => $independentPayementUrl.'?village_id='.$village_id,'name'=>$modules['custom'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
                    //预存物业费
                    $t[] = array('module' => 'pre_storage_property', 'linkcode' => $lifepaymentUrl.'?village_id='.$village_id,'name'=>$modules['pre_storage_property'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
                }
            }else{
                //小区缴费
                $t[] = array('module' => 'HousePay', 'linkcode' => $payMentlistUrl.'?village_id=' . $village_id,'name'=>$modules['HousePay'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
                //自主缴费
                $t[] = array('module' => 'custom', 'linkcode' => $independentPayementUrl.'?village_id='.$village_id,'name'=>$modules['custom'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
                //预存物业费
                $t[] = array('module' => 'pre_storage_property', 'linkcode' => $lifepaymentUrl.'?village_id='.$village_id,'name'=>$modules['pre_storage_property'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            }
        }
        //收费管理 end 2020/9/27

        if (in_array(16,$application_arr) || ($village_id == 0)) {
            /*
             * $t[] = array('module' => 'HousevillageMyRepairlists', 'linkcode' => $waterReportlistUrl.'?type=1&title='.urlencode('物业报修').'&village_id='.$village_id,'name'=>$modules['HousevillageMyRepairlists'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            */
            $t[] = array('module' => '', 'linkcode' => $config['site_url'] . $base_url.'pages/houseMeter/workOrder/workOrder?village_id='.$village_id,'name'=>$modules['HousevillageMyRepairlists'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if (in_array(18,$application_arr) || ($village_id == 0)) {
            $t[] = array('module' => 'HousevillageMyUtilitieslists', 'linkcode' => $waterReportlistUrl.'?type=2&title='.urlencode('水电煤上报').'&village_id='.$village_id,'name'=>$modules['HousevillageMyUtilitieslists'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if (in_array(19,$application_arr) || ($village_id == 0)) {
            $t[] = array('module' => 'HousevillageMySuggestist', 'linkcode' => $waterReportlistUrl.'?type=3&title='.urlencode('投诉建议').'&village_id='.$village_id,'name'=>$modules['HousevillageMySuggestist'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        if (in_array(24,$application_arr) || ($village_id == 0)) {
            $t[] = array('module' => 'ActiveGroupList', 'linkcode' => str_replace($str, 'wap.php', U('Wap/House/village_grouplist', array('village_id'=>$village_id), true, false, true)),'name'=>$modules['ActiveGroupList'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if (in_array(14,$application_arr) || ($village_id == 0)) {
            $t[] = array('module' => 'HousevillageExpress', 'linkcode' => $expressManagementUrl . '?village_id='.$village_id, 'name'=>$modules['HousevillageExpress'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] =  array('module' => 'HousevillageExpressSend', 'linkcode' => $expressManagementUrl . '?current=1&village_id='.$village_id, 'name'=>$modules['HousevillageExpressSend'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        if (in_array(25,$application_arr) || ($village_id == 0)) {
            $t[] = array('module' => 'ActiveMealList', 'linkcode' => str_replace($str, 'wap.php', U('Wap/House/village_meallist', array('village_id'=>$village_id), true, false, true)),'name'=>$modules['ActiveMealList'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if (in_array(26,$application_arr) || ($village_id == 0)) {
            $t[] = array('module' => 'ActiveAppointList', 'linkcode' => str_replace($str, 'wap.php', U('Wap/House/village_appointlist', array('village_id'=>$village_id), true, false, true)),'name'=>$modules['ActiveAppointList'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if (in_array(27,$application_arr) || ($village_id == 0)) {
            $t[] = array('module' => 'ActiveStoreList', 'linkcode' => str_replace($str, 'wap.php', U('Wap/House/shop', array('village_id'=>$village_id.'#cat-all'), true, false, true)),'name'=>$modules['ActiveStoreList'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if (in_array(28,$application_arr) || ($village_id == 0)) {
            $t[] = array('module' => 'VisitorRegistration', 'linkcode' => $visitorRegistrationUrl.'?village_id='.$village_id,'name'=>$modules['VisitorRegistration'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

// wap.php?g=Wap&c=House&a=shop&village_id=1
        //$systemLiveServiceTypeArr = explode(',',$config['live_service_type']);
        //if(in_array('water',$systemLiveServiceTypeArr)){
        //	$t[] = array('module' => 'PlatformPayWater', 'linkcode' => str_replace($str, 'wap.php', U('Lifeservice/query',array('type'=>'water'), true, false, true)),'name'=>$modules['PlatformPayWater'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        //}
        //if(in_array('electric',$systemLiveServiceTypeArr)){
        //	$t[] = array('module' => 'PlatformPayElectric', 'linkcode' => str_replace($str, 'wap.php', U('Lifeservice/query',array('type'=>'electric'), true, false, true)),'name'=>$modules['PlatformPayElectric'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        //}
        //if(in_array('gas',$systemLiveServiceTypeArr)){
        //	$t[] = array('module' => 'PlatformPayGas', 'linkcode' => str_replace($str, 'wap.php', U('Lifeservice/query',array('type'=>'gas'), true, false, true)),'name'=>$modules['PlatformPayGas'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        //}

        if($config['crowdsourcing_is']){
            $t[] = array('module' => 'Crowdsourcing', 'linkcode' => str_replace($str, 'wap.php', U('Wap/Crowdsourcing/index', array('village_id'=>$village_id), true, false, true)),'name'=>$modules['Crowdsourcing'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        // 智能访客开门
        if ($config['house_village_temporary_visitor'] == 1) {
            if($village_id == 0){
                $t[] = array('module' => 'TemporaryVisitorOpen', 'linkcode' => $config['site_url'] . $base_url.'pages/village/index/visitorPass','name'=>$modules['TemporaryVisitorOpen'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            }else{
                $now_village = D('House_village')->get_one($village_id);
                if ($now_village['temporary_visitor_switch'] == 1) {
                    $t[] = array('module' => 'TemporaryVisitorOpen', 'linkcode' => $config['site_url'] . $base_url.'pages/village/index/visitorPass','name'=>$modules['TemporaryVisitorOpen'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
                }
            }
        }

        //狄耐克二维码开门
        $face_device = D('House_face_device')->where(array('device_type' => 26))->getField('device_id');
        if (!empty($face_device)){
            $url = $config['site_url'] . $base_url . 'pages/houseMeter/VisitorInvitation/visitorOpenDoor?type=2';
            $t[] = array('module' => 'visitorOpenDoor', 'linkcode' => $url,'name'=>'扫码开门','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $url = $config['site_url'] . $base_url . 'pages/houseMeter/VisitorInvitation/visitorPass';
            $t[] = array('module' => 'visitorPass', 'linkcode' => $url,'name'=>'访客邀请','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);

        }
//        $url = $config['site_url'] . '/packapp/vote_app/index.html';
        if (in_array(2,$application_arr) || ($village_id == 0)){
            $url = $config['site_url'] . $base_url . 'pages/vote/index';
            $t[] = array('module' => 'Vote', 'linkcode' => $url,'name'=>$modules['Vote'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if($village_id == 0){
            $url = $config['site_url'] . $base_url . 'pages/village/index/oneClickAlarm';
            $t[] = array('module' => 'AlarmPhone', 'linkcode' => $url,'name'=>'一键报警','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }else{
            $touch_alarm_phone = M('House_village')->where(array('village_id' => $village_id))->getField('touch_alarm_phone');
            if ($touch_alarm_phone) {
                $url = $config['site_url'] . $base_url . 'pages/village/index/oneClickAlarm';
                $t[] = array('module' => 'AlarmPhone', 'linkcode' => $url,'name'=>'一键报警','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            }
        }

//        $url = $config['site_url'] . $base_url . 'pages/village/street/streetNews?village_id='.$village_id;
//        $t[] = array('module' => 'StreetNews', 'linkcode' => $url,'name'=>'街道新闻','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
//
//        $url = $config['site_url'] . $base_url . 'pages/village/communityNews/communityNews?village_id='.$village_id;
//        $t[] = array('module' => 'CommunityNews', 'linkcode' => $url,'name'=>'社区新闻','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);


        
        $iscbztssq=D('Config_customization')->getCbztssqCustom();
        if($iscbztssq){
            $url = $config['site_url'] . $base_url . 'pages/village_menu/new_resident';
            if($village_id==0){
                $t[] = array('module' => 'neighborhood', 'linkcode' => $url,'name'=>'邻里分类','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            }else{
                $t[] = array('module' => 'neighborhood', 'linkcode' => $url,'name'=>'邻里分类','sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            }
        }
        $url = $config['site_url'] . $base_url . 'pages/village_menu/resident';
        $t[] = array('module' => 'neighborhood', 'linkcode' => $url,'name'=>'邻里','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        
        $url = $faceAccessControlUrl;
        $t[] = array('module' => 'FaceDevice', 'linkcode' => $url,'name'=>'人脸识别门禁','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);

        if($village_id == 0){
            // 街道首页
            $url = $config['site_url'] . $base_url . "pages/street/street_index?area_street_id={$now_village['street_id']}&linkFrom=street";
            $t[] = array('module' => '', 'linkcode' => $url,'name'=>'街道首页','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            // 社区首页
            $url = $config['site_url'] . $base_url. "pages/street/street_index?area_street_id={$now_village['community_id']}&linkFrom=community";
            $t[] = array('module' => '', 'linkcode' => $url,'name'=>'社区首页','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }else{
            if (empty($now_village)) {
                $now_village = D('House_village')->get_one($village_id,'street_id, community_id');
            }
            if ($now_village && $now_village['street_id']) {
                // 街道首页
                $url = $config['site_url'] . $base_url . "pages/street/street_index?area_street_id={$now_village['street_id']}&linkFrom=street";
                $t[] = array('module' => 'Street', 'linkcode' => $url,'name'=>'街道首页','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            }
            if ($now_village && $now_village['community_id']) {
                // 社区首页
                $url = $config['site_url'] . $base_url. "pages/street/street_index?area_street_id={$now_village['community_id']}&linkFrom=community";
                $t[] = array('module' => 'Community', 'linkcode' => $url,'name'=>'社区首页','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            }
        }

        if ($config['discuzx_url']) {
            //todo 论坛
            $t[] = array('module' => 'forum', 'linkcode' => str_replace($str, 'wap.php', U('Wap/Discuz/main', array('village_id'=>$village_id,'type'=>1), true, false, true)),'name'=>$modules['forum'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);

            //todo 论坛版块
            $t[] = array('module' => 'forum_list', 'linkcode' => str_replace($str, 'wap.php', U('Wap/Discuz/main', array('village_id'=>$village_id,'type'=>2), true, false, true)),'name'=>$modules['forum_list'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);

            //todo 单篇贴子
            $t[] = array('module' => 'single_forum', 'linkcode' => str_replace($str, 'wap.php', U('Wap/Discuz/main', array('village_id'=>$village_id,'type'=>3), true, false, true)),'name'=>$modules['single_forum'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        $hawkEyeUrl = $hawkEyeUrl . "?url_type=village&village_id=$village_id";
        $hawkEyeName = $config['hawkEyeName']?$config['hawkEyeName']:'鹰眼服务';
        $t[] = array('module' => 'Community', 'linkcode' => $hawkEyeUrl,'name'=>$hawkEyeName,'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);

        $openCodeUrl = $config['site_url'] . $base_url . "pages/village/visitorPass/openCode?url_type=village&village_id=$village_id";
        $openCodeName = '开门/投放二维码';
        $t[] = array('module' => 'openCode', 'linkcode' => $openCodeUrl,'name'=>$openCodeName,'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);

        $houseMyPartyMemberName = isset($modules['HouseMyPartyMember']) && $modules['HouseMyPartyMember'] ? $modules['HouseMyPartyMember'] : '党员信息';
        $t[]= array('module' => 'HouseMyPartyMember', 'linkcode' => $config['site_url'] .$base_url . 'pages/street/index/partyDetail?village_id=' . $village_id,'name'=> $houseMyPartyMemberName,'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        
        $jz_wsxdsj_net_customized_open=D('Config_customization')->getWSXCustomizedOpen();
        if($jz_wsxdsj_net_customized_open){
            $t[] = array('module' => 'add_close_heating', 'linkcode' => $config['site_url'] . $base_url . 'pages/stopFeating/index','name'=>'停暖申请','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] = array('module' => 'add_flow_charging', 'linkcode' => $config['site_url'] . $base_url . 'pages/trafficBilling/index','name'=>'申请流量计费','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        $isVr720yunCustomized = D('Config_customization')->getVr720yunCustomized();
        if ($isVr720yunCustomized) {
            $vr720yunCustomizedUrl = "https://www.720yun.com/vr/e8ajOgmaOy5";
            $t[] = array('module' => 'vr720yunCustomized', 'linkcode' => $vr720yunCustomizedUrl, 'name' => 'VR实景链接', 'sub' => 0, 'canselected' => 1, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1);
        }
        $zhsq_hs_zhiyunkjt_customized=D('Config_customization')->get_zhsq_hs_zhiyunkjt_customized();
        if($zhsq_hs_zhiyunkjt_customized){
            $t[] = array('module' => 'reserveH5External', 'linkcode' => 'http://ctjywy.cn/visitor/weChatAndH5/visitorRegistered','name'=>'分享链接','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        $isAihorseCustomized=D('Config_customization')->getIsAihorseCustomized();
        if($isAihorseCustomized){
            $t[] = array('module' => '', 'linkcode' =>  $config['site_url'] .'/packapp/village/pages/village/index/openLock','name'=>'开锁中心','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        return $t;
    }
    
    //todo 小区后台 商家功能库  $mer_id  0:默认所有
    public function merchant_library($mer_id=0,$config,$merchant,$village_modules,$token,$str='shequ.php'){
        $base_url = D('House_village')->base_url;
        $t = array(
            array('module' => 'Home',		'linkcode' => str_replace($str, 'wap.php', U('Wap/Index/index', array('token' => $token), true, false, true)), 'name'=>L_('微站首页'),'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>$village_modules['Home'],'askeyword'=>1),
            array('module' => 'Classify',	'linkcode'=> str_replace($str, 'wap.php', U('Wap/Index/lists', array('token' => $token), true, false, true)),'name'=>$merchant['Classify'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>0),
            array('module' => 'Group',		'linkcode'=>'','name'=>$merchant['Group'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Meal',		'linkcode'=>'','name'=>$merchant['Meal'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Lottery',	'linkcode'=>str_replace($str, 'wap.php', U('Wap/Lottery/Lottery', array('token' => $token), true, false, true)),'name'=>$merchant['Lottery'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Guajiang',	'linkcode'=>'','name'=>$merchant['Guajiang'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'GoldenEgg',	'linkcode'=>'','name'=>$merchant['GoldenEgg'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'LuckyFruit',	'linkcode'=>'','name'=>$merchant['LuckyFruit'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Article',	'linkcode'=>'','name'=>$merchant['Article'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Shop',	'linkcode'=>'','name'=>$merchant['Shop'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'ShopLink',	'linkcode'=>'','name'=>$merchant['ShopLink'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Wxapp',	'linkcode'=>'','name'=>$merchant['Wxapp'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
// 			array('module' => 'PadMeal',		'linkcode'=>'','name'=>$merchant['PadMeal'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Mall', 'linkcode' => '', 'name' => $merchant['Mall'], 'sub' => 1, 'canselected' => 0, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1),
            array('module' => 'Workstaff',		'linkcode' => str_replace($str, 'wap.php', U('Wap/Workerstaff/login', array(), true, false, true)), 'name'=>($config['appoint_worker_name'] ? $config['appoint_worker_name'] : L_('技师')).L_('中心'),'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>$merchant['Workerstaff'],'askeyword'=>1),
            array('module' => 'Card_new_coupon',		'linkcode' => str_replace($str, 'wap.php', U('Wap/My_card/merchant_coupon_list', array('mer_id'=>$mer_id), true, false, true)), 'name'=>L_('商家会员卡优惠券'),'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>$merchant['Card_new'],'askeyword'=>1),
            array('module' => 'Card_new',		'linkcode' => str_replace($str, 'wap.php', U('Wap/My_card/merchant_card', array('mer_id'=>$mer_id), true, false, true)), 'name'=>L_('商家会员卡'),'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>$merchant['Card_new'],'askeyword'=>1),
            array('module' => 'Store',		'linkcode' => str_replace($str, 'wap.php', U('Wap/My/pay', array('mer_id'=>$mer_id), true, false, true)), 'name'=>$merchant['Store'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>$merchant['Card_new'],'askeyword'=>1),
            array('module' => 'Appoint',		'linkcode' => '', 'name'=>$merchant['Appoint'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>$merchant['Card_new'],'askeyword'=>1),
            array('module' => 'Storestaff',		'linkcode' => $config['site_url'].'/packapp/storestaff/index.html', 'name'=>L_('手机版店员中心'),'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>$merchant['Card_new'],'askeyword'=>1),
            array('module' => 'Deliver',		'linkcode' => $config['site_url'].'/packapp/deliver/index.html', 'name'=>L_('手机版配送员中心'),'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>$merchant['Card_new'],'askeyword'=>1),
            array('module' => 'Im',		'linkcode' => $config['site_url'].'/wap.php?c=Home&a=jump_im&mer_id='.$mer_id, 'name'=>$merchant['Im'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>$merchant['Im'],'askeyword'=>1),
            array('module' => 'MarkList',
                'linkcode' => $config['site_url'].'/packapp/merchant/market_list.html',
                'name'=>$merchant['MarkList'],
                'sub'=>1,
                'canselected'=>1,
                'linkurl'=>'',
                'keyword'=>$merchant['MarkList'],
                'askeyword'=>1),
            /*
            array('module' => 'Integral_mall_index',
                'linkcode' => $config['site_url'].'/wap.php?g=Wap&c=Integral_mall&a=index&mer_id='.$mer_id,
                'name'=>$merchant['Integral_mall_index'],
                'sub'=>0,
                'canselected'=>1,
                'linkurl'=>'',
                'keyword'=>$merchant['Integral_mall_index'],
                'askeyword'=>1),
            */
        );

        if ($config['merchant_integral_mall_control'] == 1) {
            array_push($t, array('module' => 'IntegralMall',
                'linkcode' => $config['site_url'].'/wap.php?g=Wap&c=Integral_mall&a=storelist&mer_id='.$mer_id,
                'name'=>$merchant['IntegralMall'],
                'sub'=>0,
                'canselected'=>1,
                'linkurl'=>'',
                'keyword'=>$merchant['IntegralMall'],
                'askeyword'=>1));
        }

        if($config['is_open_weidian']){
            array_push($t,array('module' => 'Weidian',	'linkcode'=>'','name'=>$merchant['Weidian'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1));
        }

        // 添加用户平台个人链接-即wap端个人中心
        array_push($t,array('module' => 'User_Platform_Personal_Center',		'linkcode' => str_replace($str, 'wap.php', U('Wap/My/index', array(), true, false, true)), 'name'=>$merchant['User_Platform_Personal_Center'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>$merchant['User_Platform_Personal_Center'],'askeyword'=>1));

        if($config['open_banking']){// 定制金融产品
              array_push($t, array('module' => 'bankingIndex',
                'linkcode' => $config['site_url'] . $base_url. "pages/loanModule/index",
                'name'=>'金融产品首页',
                'sub'=>0,
                'canselected'=>1,
                'linkurl'=>'',
                'keyword'=>'bankingIndex',
                'askeyword'=>1));
        }


        foreach($t as $k=>$v){
            if(!$config['group_alias_name'] && in_array($v['module'],array('Group'))){
                unset($t[$k]);
            }
            if(!$config['shop_alias_name'] && in_array($v['module'],array('Shop','ShopLink'))){
                unset($t[$k]);
            }
            if(!$config['meal_alias_name'] && in_array($v['module'],array('Meal',))){
                unset($t[$k]);
            }
            if(!$config['mall_alias_name'] && in_array($v['module'],array('Mall',))){
                unset($t[$k]);
            }
            if(!$config['appoint_alias_name'] && in_array($v['module'],array('Appoint','Workstaff'))){
                unset($t[$k]);
            }
            if(!$config['pay_in_store'] && in_array($v['module'],array('Store'))){
                unset($t[$k]);
            }
            if(!empty($config['single_system']) && in_array($v['module'],array('Activity','City_car','Wxapp','Wapactivity'))){
                unset($t[$k]);
            }
        }

        return $t;
    }

    //todo 首页替换补充小区参数
    public function replace_village_parameter($url,$village_id,$uid=-1,$now_village=[],$arr=[]){
        $str=$url;
        if (!empty($url)) {
            if(isset($arr['is_new_charge']) && (int)$arr['is_new_charge'] == 1){
                $old_pay_url='pages/village_my/payMentlist';
                if(false !== strstr($str, $old_pay_url)){
                    /**
                     * $hzhou_grapefruit_order=D('Config_customization')->getHzhouGrapefruitOrderJudge();
                     */
                    $hzhou_grapefruit_order=1;
                    $tmpLinkUrl='pages/houseMeter/NewCollectMoney/liveExpenses';
                    if($hzhou_grapefruit_order){
                        $tmpLinkUrl='pages/houseMeter/NewCollectMoney/newLiveExpenses';
                    }
                    $str = str_replace($old_pay_url,$tmpLinkUrl,$str);
                }
            }
            if(false !== strstr($str, 'village_id=0')){
                $str = str_replace('village_id=0','village_id='.$village_id,$str);
            }
            if(false !== strstr($str, 'token=0')){
                $str = str_replace('token=0','token='.$village_id,$str);
            }
            if(false !== strstr($str, 'pages/street/street_index')){
                // 如果是街道社区 坐下匹配认证
                if (empty($now_village)) {
                    $now_village = D('House_village')->where(array('village_id'=>$village_id))->find();
                }
                $parse_arr = parse_url($str);
                parse_str($parse_arr['query'],$query_arr);
                $changeUrl = false;
                if (isset($query_arr['linkFrom'])&&$query_arr['linkFrom']=='street'&&$query_arr['area_street_id']!=$now_village['street_id']) {
                    $query_arr['area_street_id'] = $now_village['street_id'];
                    $changeUrl = true;
                } elseif (isset($query_arr['linkFrom'])&&$query_arr['linkFrom']=='community'&&$query_arr['area_street_id']!=$now_village['community_id']) {
                    $query_arr['area_street_id'] = $now_village['community_id'];
                    $changeUrl = true;
                } elseif (isset($query_arr['area_street_id'])&&$query_arr['area_street_id']&&$query_arr['area_street_id']!=$now_village['street_id']&&$query_arr['area_street_id']!=$now_village['community_id']) {
                    // 存在id  但是不等于正确 进行更正
                    $area_type = M('Area_street')->where(['area_id' => $query_arr['area_street_id']])->getField('area_type');
                    if ($area_type==1 && $now_village['community_id']) {
                        $query_arr['area_street_id'] = $now_village['community_id'];
                    } elseif ($now_village['street_id']) {
                        $query_arr['area_street_id'] = $now_village['street_id'];
                    }
                    $changeUrl = true;
                }
                if ($changeUrl && isset($query_arr['area_street_id'])&&$query_arr['area_street_id'])  {
                    $query = http_build_query($query_arr);
                    $urlArr = explode('?', $str);
                    if ($urlArr[0]) {
                        $str = $urlArr[0].'?'.$query;
                    }
                }
            }
            $db_house_village = D('House_village');
            $app_version = intval($_POST['app_version']);
            $deviceId = trim($_POST['Device-Id']);
            if (strstr($str, 'pages/village/my/') || strstr($str, 'pages/village_my/')) {
                $param = [
                    'pagePath' => $str,
                ];
                $str = $db_house_village->villagePagePath($app_version,$deviceId,$param);
            }
            $param = [
                'pagePath' => 'pages/village/my/selectRooms',
            ];
            $selectRoomsUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
            $param = [
                'pagePath' => 'pages/village/my/myVillage',
            ];
            $myVillageUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
            if($uid!=-1 && false !== strstr($str, $selectRoomsUrl)){
                // 三方楼宇只有是 业主家属租客显示
                $thirdConfigInfo = S('thirdConfigInfoLingHuiVillage_'.$village_id);
                if (empty($thirdConfigInfo)) {
                    if (empty($this->thirdConfig)) {
                        import('@.ORG.ThirdAccess.thirdConfig');
                        $this->thirdConfig = new thirdConfig();
                    }
                    // 存在三方对应物业id 查询下是否已经存在了对应物业
                    $whereThirdConfig = [
                        'businessType' => 'village',
                        'businessId' => $village_id,
                        'thirdType' => 'linghui',
                    ];
                    try {
                        $thirdConfigInfo = $this->thirdConfig->getHouseThirdConfig($whereThirdConfig);
                    } catch (\Exception $e) {
                        fdump_api(['House-linkModel-replace_village_parameter',$whereThirdConfig,$e->getMessage()],'thirdConfigLog');
                    }
                }
                if ($thirdConfigInfo && $thirdConfigInfo['thirdSite'] && $thirdConfigInfo['thirdId']) {
                    $now_user = D('User')->get_user($uid);
                    S('thirdConfigInfoLingHuiVillage_'.$village_id, $thirdConfigInfo, 86400);
                    $thirdBaseUrl = 'https://'.$thirdConfigInfo['thirdSite'];
                    $appid = trim(C('config.third_building_appid'));
                    $key = md5($appid.$now_user['phone']);
                    $referer = $thirdBaseUrl . "/h5/#/pages/community/buildingBindApply?key={$key}&phone={$now_user['phone']}&build_id={$thirdConfigInfo['thirdId']}&third_type=wechat";
                    if (!$now_user['uid']) {
                        $str = C('config.site_url')."/packapp/my/login.html?back=". urlencode($referer) ."&loginFail=". urlencode($referer);
                    } elseif (!$now_user['phone']) {
                        $str = get_base_url("pages/my/newnumber?back=". urlencode($referer));
                    } else {
                        $str = $referer;
                    }
                }
            } elseif($uid!=-1 && false !== strstr($str, $myVillageUrl)){
                // 三方楼宇只有是 业主家属租客显示
                $thirdConfigInfo = S('thirdConfigInfoLingHuiVillage_'.$village_id);
                if (empty($thirdConfigInfo)) {
                    if (empty($this->thirdConfig)) {
                        import('@.ORG.ThirdAccess.thirdConfig');
                        $this->thirdConfig = new thirdConfig();
                    }
                    // 存在三方对应物业id 查询下是否已经存在了对应物业
                    $whereThirdConfig = [
                        'businessType' => 'village',
                        'businessId' => $village_id,
                        'thirdType' => 'linghui',
                    ];
                    try {
                        $thirdConfigInfo = $this->thirdConfig->getHouseThirdConfig($whereThirdConfig);
                    } catch (\Exception $e) {
                        fdump_api(['House-linkModel-replace_village_parameter',$whereThirdConfig,$e->getMessage()],'thirdConfigLog');
                    }
                }
                if ($thirdConfigInfo && $thirdConfigInfo['thirdSite'] && $thirdConfigInfo['thirdId']) {
                    $now_user = D('User')->get_user($uid);
                    S('thirdConfigInfoLingHuiVillage_'.$village_id, $thirdConfigInfo, 86400);
                    $thirdBaseUrl = 'https://'.$thirdConfigInfo['thirdSite'];
                    $appid = trim(C('config.third_building_appid'));
                    $key = md5($appid.$now_user['phone']);
                    $referer = $thirdBaseUrl . "/h5/#/pages/community/tenantApprovalList?key={$key}&phone={$now_user['phone']}&build_id={$thirdConfigInfo['thirdId']}&third_type=wechat";
                    if (!$now_user['uid']) {
                        $str = C('config.site_url')."/packapp/my/login.html?back=". urlencode($referer) ."&loginFail=". urlencode($referer);
                    } elseif (!$now_user['phone']) {
                        $str = get_base_url("pages/my/newnumber?back=". urlencode($referer));
                    } else {
                        $str = $referer;
                    }
                }
            }
        }
        return $str;
    }

    //todo 【记录下小区功能库返回的参数】
    public function get_source_type($url){
        $url=parse_url($url);
        $source_type='0';
        if(isset($url['query']) && !empty($url['query'])){
            $path=convertUrlQuery($url['query']);
            if(isset($path['source']) && !empty($path['source'])){
                $source_type=$path['source'];
            }
        }
        return $source_type;
    }

    //todo 过滤小区id
    public function get_filter_village_id($village_id=0,$info,$type='all'){
        $switch= C('config.house_visualize_switch') ? C('config.house_visualize_switch') : 0;
        $filter_village_ids=$info['filter_village_ids'];
        if(!empty($filter_village_ids)){
            $filter_village_ids=explode(',',$filter_village_ids);
        }else{
            $filter_village_ids=[];
        }
        $data=[];
        if(!empty($village_id) && intval($switch) > 0 && (intval($info['village_id']) < 1) && (!in_array($village_id,$filter_village_ids) || $type == 'del' )){
            // 原广告数据写入关联village_ids
            $filter_village_ids[]=$village_id;
            $data=array_unique($filter_village_ids);
        }
        return $data;
    }


    public function frame_submit_tips($type,$tips,$time=3){

        if($type){
            if($type == 11){
                exit('<html><head><script>window.top.msg(1,"'.$tips.'",true,'.$time.');window.parent.location.reload();</script></head></html>');
            }
            if($_GET['from_page']){
                exit('<html><head><script>window.top.msg(1,"'.$tips.'",true,'.$time.');window.top.main_refresh();window.top.closeiframe();</script></head></html>');

//				exit('<html><head><script>window.top.msg(1,"'.$tips.'",true,'.$time.');window.top.refresh_iframe("'.$_GET['from_page'].'");window.top.closeiframebyid("form_submit_tips");window.top.closeiframebyid(window.name.replace("Open",""));</script></head></html>');
            }else{
                exit('<html><head><script>window.top.msg(1,"'.$tips.'",true,'.$time.');window.top.main_refresh();window.top.closeiframe();</script></head></html>');
            }
        }else{
            exit('<html><head><script>window.top.msg(0,"'.$tips.'",true,'.$time.');if(window.top.frames["Openadd"]){window.top.frames["Openadd"].history.back();}else{window.top.frames["Openedit"].history.back();}</script></head></html>');
        }
    }
}