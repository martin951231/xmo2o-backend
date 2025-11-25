<?php
class NewLinkAction extends BaseAction{
    public $modules;
    protected $property_id;

    public function _initialize() 
    {
            parent::_initialize();

            $this->modules = array(
                    'Member' => '会员中心', 
                    'HousevillageList' => C('config.house_name').'列表',
                    'Housevillage'=>C('config.house_name').'首页',
                    'HousevillageNewsCatelist'=>C('config.house_name').'新闻分类',
                    'HousevillageNewslist'=>C('config.house_name').'新闻',
                    'ActiveGroupList'=>'推荐'.$this->config['group_alias_name'],
                    'ActiveMealList'=>"推荐".$this->config['meal_alias_name'],
                    'ActiveAppointList'=>'推荐'.$this->config['appoint_alias_name'],
                    'HousevillageMy'=>"个人中心",
                    'HouseserviceserviceCategory'=>'便民服务',
                    'HousevillagePayProperty'=>'缴物业费',
                    'HousevillagePayWater'=>'缴水费',
                    'HousevillagePayElectric'=>'缴电费',
                    'HousevillagePayGas'=>'缴燃气费',
                    'HousevillagePayPark'=>'缴停车费',
                    'HousevillagePayCustom'=>'自定义缴费',
                    'HousevillageMyRepair'=>"在线报修",
                    'HousevillageMyUtilities'=>'水电煤上报',
                    'HousevillageMyPaylists'=>'缴费订单列表',
                    'HousevillageMyRepairlists'=>'在线报修列表',
                    'HousevillageMyUtilitieslists'=>'水电煤上报列表',
                    'HousevillageMySuggest'=>'投诉建议',
                    'HousevillageMyBindFamilyAdd'=>'绑定'.(C('config.open_house_user_name')?'亲友':'家属'),
                    'HousevillageMyBindFamilyList'=>'绑定'.(C('config.open_house_user_name')?'亲友':'家属').'列表',
                    'HousephoneIndex'=>'常用电话列表',
                    'HouseRide'=>C('config.house_name').'顺风车',
                    'PlatformPayWater'=>'平台缴水费',
                    'PlatformPayGas'=>'平台缴燃气费',
                    'PlatformPayElectric'=>'平台缴电费',
                    'HousevillageMySuggestist'=>'投诉列表',
                    'HousevillageActivity'=>'小区活动',
                    'HousePay'=>'小区缴费',
                    'Crowdsourcing' => '众包',
					'HousevillageManager' => C('config.house_name').'管家',
					'HousevillageExpress' => '快递代收',
					'MyVillage' => '我的'.C('config.house_name'),
					'VisitorRegistration' => '访客登记',
                    'ActiveStoreList'=>L_('推荐X1',['X1'=>$this->config['shop_alias_name']]),
                    'TemporaryVisitorOpen'=>'智能访客开门',
                    'houseMeterNewCollectMoney'=>'新版收费',
            );
    }
    
    
    public function insert()
    {
            $modules = $this->modules();
            $this->assign('modules', $modules);
            $this->display('NewWeixin/insert');
    }
    
    private function modules()
	{
        $property = session('property');
        $this->property_id = $property['id'];
        $property_id = $this->property_id;
		$t = array(
			array('module' => 'Home', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Home/index', '', true, false, true)), 'name'=>'平台首页','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>$this->modules['Home'],'askeyword'=>1),
			array('module' => 'Housevillage', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village', array('property_id'=>$property_id), true, false, true)),'name'=>$this->modules['Housevillage'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
			array('module' => 'HousevillageList', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_list', array('property_id'=>$property_id), true, false, true)),'name'=>$this->modules['HousevillageList'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
			array('module' => 'HouseRide', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Ride/ride_list', array('property_id'=>$property_id), true, false, true)),'name'=>$this->modules['HouseRide'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'HousephoneIndex', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Housephone/index', array('property_id'=>$property_id), true, false, true)),'name'=>$this->modules['HousephoneIndex'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
			array('module' => 'HousevillageNewsCatelist', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_newslist', array('property_id'=>$property_id), true, false, true)),'name'=>$this->modules['HousevillageNewsCatelist'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
			array('module' => 'Member', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/My/index', '', true, false, true)),'name'=>$this->modules['Member'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
			array('module' => 'ActiveGroupList', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_grouplist', array('property_id'=>$property_id), true, false, true)),'name'=>$this->modules['ActiveGroupList'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
			array('module' => 'ActiveMealList', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_meallist', array('property_id'=>$property_id), true, false, true)),'name'=>$this->modules['ActiveMealList'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
			array('module' => 'ActiveAppointList', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_appointlist', array('property_id'=>$property_id), true, false, true)),'name'=>$this->modules['ActiveAppointList'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
			array('module' => 'HousevillageMy', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_my', array('property_id'=>$property_id), true, false, true)),'name'=>$this->modules['HousevillageMy'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),

			array('module' => 'HousevillageMyRepair', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_my_repair', array('property_id'=>$property_id), true, false, true)),'name'=>$this->modules['HousevillageMyRepair'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
			array('module' => 'HousevillageMyUtilities', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_my_utilities', array('property_id'=>$property_id), true, false, true)),'name'=>$this->modules['HousevillageMyUtilities'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
			array('module' => 'HousevillageMyPaylists', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_my_paylists', array('property_id'=>$property_id), true, false, true)),'name'=>$this->modules['HousevillageMyPaylists'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
			array('module' => 'HousevillageMyRepairlists', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_my_repairlists', array('property_id'=>$property_id), true, false, true)),'name'=>$this->modules['HousevillageMyRepairlists'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
			array('module' => 'HousevillageMyUtilitieslists', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_my_utilitieslists', array('property_id'=>$property_id), true, false, true)),'name'=>$this->modules['HousevillageMyUtilitieslists'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
			array('module' => 'HousevillageMySuggest', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_my_suggest', array('property_id'=>$property_id), true, false, true)),'name'=>$this->modules['HousevillageMySuggest'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
			array('module' => 'HousevillageMyBindFamilyAdd', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_my_bind_family_add', array('property_id'=>$property_id), true, false, true)),'name'=>$this->modules['HousevillageMyBindFamilyAdd'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
			array('module' => 'HousevillageMyBindFamilyList', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_my_bind_family_list', array('property_id'=>$property_id), true, false, true)),'name'=>$this->modules['HousevillageMyBindFamilyList'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
			array('module' => 'HouseserviceserviceCategory', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Houseservice/index',array('property_id'=>$property_id) , true, false, true)),'name'=>$this->modules['HouseserviceserviceCategory'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'HousevillageMySuggestlist', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_my_suggestlist',array('property_id'=>$property_id) , true, false, true)),'name'=>$this->modules['HousevillageMySuggestist'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'HousevillageActivity', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_activitylist',array('property_id'=>$property_id) , true, false, true)),'name'=>$this->modules['HousevillageActivity'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
			array('module' => 'HousevillageManager', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_manager_list',array('property_id'=>$property_id) , true, false, true)),'name'=>$this->modules['HousevillageManager'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
			array('module' => 'HousevillageExpress', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Library/express_service_list',array('property_id'=>$property_id) , true, false, true)),'name'=>$this->modules['HousevillageExpress'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		    array('module' => 'MyVillage', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/my_village_list', null, true, false, true)),'name'=>$this->modules['MyVillage'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		    array('module' => 'VisitorRegistration', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Library/visitor_list',array('property_id'=>$property_id), true, false, true)),'name'=>$this->modules['VisitorRegistration'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'ActiveStoreList', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/shop', array('property_id'=>$property_id), true, false, true)),'name'=>$this->modules['ActiveStoreList'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
        );
        if($this->config['crowdsourcing_is']){
            $t[] = array('module' => 'Crowdsourcing', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Crowdsourcing/index', array('property_id'=>$property_id), true, false, true)),'name'=>$this->modules['Crowdsourcing'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        // 智能访客开门
        if ($this->config['house_village_temporary_visitor'] == 1) {
            $t[] = array('module' => 'TemporaryVisitorOpen', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Library/visitor_list_open', array('property_id'=>$property_id), true, false, true)),'name'=>$this->modules['TemporaryVisitorOpen'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        $new_charge_time=D('House_new_charge_time')->where(array('property_id'=>$property_id))->find();
        $is_new_effect_time=false;
        if (!empty($new_charge_time) && isset($new_charge_time['take_effect_time']) && $new_charge_time['take_effect_time']){
            $take_effect_time=$new_charge_time['take_effect_time'];
            $nowtime=time();
            if ($nowtime>$take_effect_time) {
                // 设置了生效时间 并且 当前时间大于生效时间 返回true 其他返回false
                $is_new_effect_time= true;
            }
        }
        if($is_new_effect_time){
            $t[] = array('module' => 'houseMeterNewCollectMoney', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/house_meter_new_collect', array('property_id'=>$property_id), true, false, true)),'name'=>$this->modules['houseMeterNewCollectMoney'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }else{

            $t[] =array('module' => 'HousePay', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_my_pay',array('property_id'=>$property_id) , true, false, true)),'name'=>$this->modules['HousePay'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
		return $t;
	}
}
?>