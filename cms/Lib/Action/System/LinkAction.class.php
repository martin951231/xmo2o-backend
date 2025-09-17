<?php
class LinkAction extends BaseAction
{
	public $modules;

    public $village_modules;
    public $village_merchant_content;

	public function _initialize()
	{
		parent::_initialize();

		$this->modules = array(
			'Home' => '首页',
			'AroundGroup' => '附近'.$this->config['group_alias_name'],
			'Group' => $this->config['group_alias_name'].'店铺首页',
			'GroupNew' => $this->config['group_index_name']?$this->config['group_index_name']:'团购优选',
			'Store' => $this->config['merchant_alias_name'].'分类',
// 			'AroundMeal' => '附近'.$this->config['meal_alias_name'],
			'Meal' => $this->config['meal_alias_name'],
			'Meal_order' => $this->config['meal_alias_name'].'订单',
			'Group_order' => $this->config['group_alias_name'].'订单',
			'Shop_order' => $this->config['shop_alias_name'].'订单',
			'Group_collect' => $this->config['group_alias_name'].'收藏',
			'Card_list' => '我的平台优惠券',
			'Card_mer_list' => '我的商家优惠券',
			'Member' => '会员中心',
			'Invitation' => '交友',
			'Navigation' => $this->config['group_alias_name'].'导航',
			'Activity' => '找活动',
			'Classify' => '分类信息',
			'Storestaff_new' => '店员中心',
			'NearWeiDian' => '附近微店',
			'Takeout' => '外卖',
			'NearMerchant' => '附近商家',
			'Lifeservice' => '生活缴费',
			'AppointList' => '预约首页',
			'AppointCategory' => '预约分类',
			'AppointOrder' => '预约订单',
			'WaimaiIndex' => $this->config['waimai_alias_name'].'首页',
			'WaimaiUser' => $this->config['waimai_alias_name'].'用户中心',
			'WaimaiOrder' => $this->config['waimai_alias_name'].'订单列表',
			'WaimaiCoupon2' => $this->config['waimai_alias_name'].'平台红包列表',
			'WaimaiCoupon1' => $this->config['waimai_alias_name'].'店铺红包列表',
			'WaimaiAddress' => $this->config['waimai_alias_name'].'用户地址列表',
// 			'Deliver' => '配送员中心',
			'Deliver_new' => '配送员中心',
            'DeliverRegister' => '配送员注册页面',
			'HousevillageList' => C('config.house_name').'列表',
			'News' => '平台快报',
//			'Coupon' => '平台优惠券',
			'All_Merchant_coupon' => '全部商家优惠券',
			'Ride' => '顺风车',
			'Crowdsourcing' => '众包',
			'Shop' => $this->config['shop_alias_name'],
			'MerchantStore' => $this->config['store_alias_name'].'列表',
			'Gift' => $this->config['gift_alias_name'],
			'Workerstaff'=>($this->config['appoint_worker_name'] ? $this->config['appoint_worker_name'] : '技师').'中心',
			'Mall'=>'商城',
            'newMallVersion'=>'新版商城',
			'Sign'=>'签到',
			'Wxapp'=>'营销活动',
			'Portal'=>'门户首页',
			'Wapactivity'=>'限时活动',
			'City_car'=>'扫码挪车',
			'Mobile_recharge'=>'手机充值',
			'Scenic_index'=>'景区首页',
			'Service_index'=>'服务快派',
			'Sub_card'=>'免单套餐',
			'Yuedan'=>'约单',
			'Hotel'=>'酒店',
			'Paotui'=>'跑腿',
			'Special_shop'=>$this->config['shop_alias_name'].'专题',
			'Special_mall'=>($this->config['mall_alias_name']?$this->config['mall_alias_name']:'商城').'专题',
			'NewMerchantapp'=>'商家中心',
			'GroupMainPage'=>$this->config['group_alias_name'].'首页',
			'Miniprogram'=> '小程序',
            'Discover' => '发现',
            'Portalactivity' => '门户活动',
            'Portaltieba' => '门户贴吧',
            'Portalyellow' => '门户黄页',
            'Portalarticle' => '门户资讯',
            'QuickPay'=>'快速买单店铺列表',
            'LocalShopMall' => '本地化商城',
            'Village_group' => $this->config['village_group_alias'],
            'Circle_category'=>'商圈分类',
            'Lotterynew'=>'幸运大转盘',
            'Platform_classify_manager'=>'分类管理员申请入驻',
            'Platform_activity_inspect'=>'分类管理员管理',
            'Platform_activity_indexList'=>'平台活动列表',
            'Employee_owner_detail'=>$this->config['employee_alias'].'个人详情页面',
            'Employee_apply'=>$this->config['employee_alias'].'申请入驻页面',
            'Employee_edit'=>$this->config['employee_alias'].'详情编辑',
            'Employee_list_info'=>$this->config['employee_alias'].'列表页',
            'Employee_claim_store_add'=>$this->config['employee_alias'].'用户添加店铺',
            'Employee_claim_store'=>$this->config['employee_alias'].'认领店铺列表',
            'Employee_collect_list'=>$this->config['employee_alias'].'收藏列表',
            'UserSpreadMer'=>'我的推广商家佣金',
            'seckill_alias_name'=>$this->config['seckill_alias_name']?$this->config['seckill_alias_name']:'限时优惠',
            'platOnlineService'=>'平台在线客服',
            'Distributor_agent'=>'我的推广',
            'ecard_alias_name'=>$this->config['ecard_alias_name']?$this->config['ecard_alias_name']:'平台E卡',
            'activity_offline'=>'线下活动首页',
            'course'=>'商学院首页',
            'recruit'=>'招聘首页',
            'recruit_energe'=>'能量包首页',
            'recruit_score'=>'积分购买首页',
            'recruit_resume'=>'简历首页',
            'recruit_message'=>'消息首页',
            'recruit_course_cagetory'=>'商学院分类',
//			'IntegralMall' => '积分商城-我能兑换',
			'MarkList' => '商家中心-批发市场',
			'Distributor_apply' => '申请成为分销员',
			'Partition_coupon' => '瓜分券',
			'my_spread_qrcode' => '我的推广二维码',
			'sweepCode' => '付款码',
			'privacy_policy' => '平台app隐私政策',
			'staff_agreement' => '店员app隐私政策',
			'mer_agreement' => '商家app隐私政策',
			'deliver_agreement' => '配送app隐私政策',
			'village_manerge_agreement' => '社区管理app隐私政策',
			'parentRecharge' => '家长在线充值',
			'Live' => '直播',
            'new_foodshop_store' => '新版'.C('config.meal_alias_name'),
            'new_mall_home'=>'商城3.0首页',
            'new_mall_bargain'=>'商城3.0砍价',
            'new_mall_group'=>'商城3.0拼团',
            'new_mall_limited'=>'商城3.0秒杀',
            'forum' => 'DiscuzX论坛',
            'forum_list'=>'论坛版块',
            'single_forum'=>'单篇贴子',
            'sport'=>'体育健身',
            'employeeCard'=>'员工卡列表',
			'newsCenic'=>'新版景区',
			'ticketAppoint'	=> '门票预约',
            'newGroupVersion' =>'新版团购',
		);

        $this->village_modules = array(
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
            //'HousevillageMyPaylists'=>'缴费订单列表',
            'HousevillageMyRepairlists'=>'在线报修',
            'HousevillageMyUtilitieslists'=>'水电煤上报',
            'HousevillageMyBindFamilyAdd'=>'绑定'.(C('config.open_house_user_name')?'亲友':'家属'),
            'HousevillageMyBindFamilyList'=>'绑定'.(C('config.open_house_user_name')?'亲友':'家属').'列表',
            'HousephoneIndex'=>'常用电话列表',
            'HouseRide'=>C('config.house_name').'顺风车',
            'PlatformPayWater'=>'平台缴水费',
            'PlatformPayGas'=>'平台缴燃气费',
            'PlatformPayElectric'=>'平台缴电费',
            'HousevillageMySuggestist'=>'投诉建议',
            'HousevillageActivity'=>'小区活动',
            'HousePay'=>'小区缴费',
            'Crowdsourcing' => '众包',
            'HousevillageManager' => C('config.house_name').'管家',
            'HousevillageExpress' => '快递代收',
            'HousevillageExpressSend' => '快递代发',
            'MyVillage' => '我的'.C('config.house_name'),
            'VisitorRegistration' => '访客登记',
            'ActiveStoreList'=>L_('推荐X1',['X1'=>$this->config['shop_alias_name']]),
            'TemporaryVisitorOpen'=>'智能访客开门',
            'Vote'=>'投票',
            'pre_storage_property'=>'预存物业费',
            'custom'=>'自主缴费',
            'Street'=>'街道首页',
            'Community'=>'社区首页',
            'forum'=>'DiscuzX论坛',
            'forum_list'=>'论坛版块',
            'single_forum'=>'单篇贴子',
            'HouseAskHelp'=>'寻求帮助',
            'WorkOrder'=>'工单',
        );

        $this->village_merchant_content = array(
            'Home' => L_('首页'),
            'Classify' => L_('网站分类'),
            'Group' => $this->config['group_alias_name'],
            'Meal' => $this->config['meal_alias_name'],
//          'PadMeal' => 'Pad点餐',
            'Takeout' => L_('外卖'),
            'Lottery' => L_('大转盘'),
            'Guajiang' => L_('刮刮卡'),
//          'Coupon' => '优惠券',
            'GoldenEgg' => L_('砸金蛋'),
            'LuckyFruit' => L_('水果机'),
            'Article' => L_('文章'),
            'Weidian' => L_('微店'),
            'Wxapp' => L_('营销活动'),
            'Workerstaff'=>($this->config['appoint_worker_name'] ? $this->config['appoint_worker_name'] : L_('技师')).L_('中心'),
            'Card_new' =>L_('会员卡'),
            'Card_new_coupon' =>L_('会员卡优惠券'),
            'Shop' =>$this->config['shop_alias_name'],
            'ShopLink' => L_('链接版').$this->config['shop_alias_name'],
            'Appoint' =>$this->config['appoint_alias_name'],
            'Appoint_product' =>$this->config['appoint_alias_name'].L_('产品'),
            'Mer_lottery' =>L_('活动'),
            'Mall' => L_('商城'),
            'Store' => $this->config['cash_alias_name'],
            'Im' => L_('客服聊天'),
            'User_Platform_Personal_Center' => L_('用户平台个人中心'),
            'IntegralMall' => '积分商城-我能兑换',
            'Integral_mall_index' => '积分商城-首页',
            'MarkList' => '商家中心-批发市场',
        );
	}
	public function insert()
	{
	    $type = isset($_GET['type']) ? $_GET['type'] : 'modules';
	    if ($type == 'modules') {
	        $modules = $this->modules();
	    } else {
	        $modules = $this->programs();
	    }
	    $this->assign('modules', $modules);
	    $this->display();
	}


    //todo 可视化功能库
    public function visualize(){
        $type = isset($_GET['type']) ? $_GET['type'] : 'modules';
        $village_modules = D('House_link')->village_library(0,$this->config,$this->village_modules,'admin.php');
        $village_merchant_modules = D('House_link')->merchant_library(0,$this->config,$this->village_merchant_content,$this->village_modules,$this->token,'admin.php');
        if($village_modules){
            foreach ($village_modules as $k=>$v){
                if(intval($v['sub']) > 0 || in_array($v['module'],['HouseVenue'])){
                    unset($village_modules[$k]);
                }
            }
            $village_modules=array_values($village_modules);
        }
        if ($type == 'modules') {
            $modules = $this->modules();
        } else {
            $modules = $this->programs();
        }
        $this->assign('modules', $modules);
        $this->assign('village_modules', $village_modules);
        $this->assign('village_merchant_modules', $village_merchant_modules);
        $this->display();
    }


	public function programs()
	{
	    $t = array();
	    if (isset($this->config['pay_wxapp_appid']) && $this->config['pay_wxapp_appid']) {
	        $t[] = array('module' => 'Miniprogram', 'linkcode' => '', 'name' => $this->modules['Miniprogram'],'sub' => 1,'canselected' => 0,'linkurl' => '','keyword' => '','askeyword' => 1);
			redirect(U('Miniprogram'));die;
	    }
	    return $t;
	}
	public function modules()
	{   
        $base_url = D('House_village')->base_url;
        $plat_menu_path = "plat_menu";
        if ($this->config['system_type'] == 'village') {
            $plat_menu_path = "village_menu";
        }
		$t = array(
		array('module' => 'Home', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Home/index', array('no_house'=>'1'), true, false, true)), 'name'=>'网站首页','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>$this->modules['Home'],'askeyword'=>1),
		
		//团购首页
		array('module' => 'Group', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Group/index', '', true, false, true)),'name'=>$this->modules['Group'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		// 团购优选
        array('module' => 'GroupNew', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Groupnew/index', '', true, false, true)),'name'=>$this->modules['GroupNew'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
        //新版团购
        array('module' => 'newGroupVersion', 'linkcode' => $this->config['site_url'].'/packapp/plat/pages/group/index/home?currentPage=index','name'=>'新版团购','sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		//店铺列表
		array('module' => 'Store', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Merchant/store_list', '', true, false, true)),'name'=>$this->modules['Store'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		
		//平台快报
		array('module' => 'News', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Systemnews/index', '', true, false, true)),'name'=>$this->modules['News'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		
		//总订单列表
		array('module' => 'SystemOrder', 'linkcode' => $this->config['site_url'] . D('House_village')->base_url . 'pages/my/my_order', 'name'=>'总订单列表','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		
		array('module' => 'AroundGroup', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Group/around', '', true, false, true)),'name'=>$this->modules['AroundGroup'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
// 		array('module' => 'Meal', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Foodshop/index', '', true, false, true)),'name'=>$this->modules['Meal'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
// 		array('module' => 'AroundMeal', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Meal_list/around', '', true, false, true)),'name'=>$this->modules['AroundMeal'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		array('module' => 'Meal_order', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/My/foodshop_order_list', '', true, false, true)),'name'=>$this->modules['Meal_order'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		array('module' => 'Group_order', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/My/group_order_list', '', true, false, true)),'name'=>$this->modules['Group_order'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		array('module' => 'Shop_order', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/My/shop_order_list', '', true, false, true)),'name'=>$this->modules['Shop_order'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		array('module' => 'Group_collect', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/My/group_collect', '', true, false, true)),'name'=>$this->modules['Group_collect'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		//array('module' => 'Card_list', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/My/card_list', array('coupon_type'=>'system'), true, false, true)),'name'=>$this->modules['Card_list'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		//array('module' => 'Card_mer_list', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/My/card_list',array('coupon_type'=>'mer'), true, false, true)),'name'=>$this->modules['Card_mer_list'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		array('module' => 'Member', 'linkcode' => $this->config['site_url'] . D('House_village')->base_url . "pages/{$plat_menu_path}/my", 'name'=>$this->modules['Member'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		array('module' => 'Navigation', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Group/navigation', '', true, false, true)),'name'=>$this->modules['Navigation'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		array('module' => 'Activity', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Activity/index', '', true, false, true)),'name'=>$this->modules['Activity'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		array('module' => 'Storestaff_new', 'linkcode' => $this->config['site_url'].'/packapp/storestaff/index.html','name'=>$this->modules['Storestaff_new'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		array('module' => 'NearMerchant', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Merchant/around', '', true, false, true)),'name'=>$this->modules['NearMerchant'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
/*		array('module' => 'Systemcoupon', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Systemcoupon/index', '', true, false, true)),'name'=>$this->modules['Coupon'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),*/
		array('module' => 'Shop', 'linkcode' => $this->config['site_url'] . D('House_village')->base_url . 'pages/shop_index/index', 'name'=>$this->modules['Shop'],'sub'=>1,'canselected'=>1, 'linkurl'=>'', 'keyword'=>'', 'askeyword'=>1),
		
		array('module' => 'Special_shop', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Special/index', '', true, false, true)),'name'=>$this->modules['Special_shop'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		array('module' => 'Special_mall', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Special/index', '', true, false, true)),'name'=>$this->modules['Special_mall'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		
		array('module' => 'MerchantStore', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Merchant/store_list', '', true, false, true)),'name'=>$this->modules['MerchantStore'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		array('module' => 'Mall', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Mall/index', '', true, false, true)),'name'=>$this->modules['Mall'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
        //新版商城
        array('module' => 'newMallVersion', 'linkcode' => $this->config['site_url'].'/packapp/plat/pages/shopmall_third/shopmall_index','name'=>'新版商城','sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		array('module' => 'Mall', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Mall/cut_goods_list', '', true, false, true)),'name'=>'砍价','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		array('module' => 'Mall', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Mall/group_goods_list', '', true, false, true)),'name'=>'拼团','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		array('module' => 'Mall', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Mall/seckill_goods_list', '', true, false, true)),'name'=>$this->modules['seckill_alias_name'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),

        array('module' => 'QuickPay', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/QuickPay/index', '', true, false, true)), 'name'=>'快速买单店铺列表','sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),

		array('module' => 'Wapactivity', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Wapactivity/index', '', true, false, true)),'name'=>$this->modules['Wapactivity'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		array('module' => 'City_car', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/City_car/index', '', true, false, true)),'name'=>$this->modules['City_car'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		array('module' => 'Hotel', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Hotel/index', '', true, false, true)),'name'=>$this->modules['Hotel'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		array('module' => 'GroupMainPage', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Group/main_page', '', true, false, true)),'name'=>$this->modules['GroupMainPage'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		//array('module' => 'All_Merchant_coupon', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/My_card/merchant_coupon_list',array('all_mer'=>1), true, false, true)),'name'=>$this->modules['All_Merchant_coupon'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		array('module' => 'Village_group', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Village_group/index','', true, false, true)),'name'=>$this->modules['Village_group'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		array('module' => 'Village_group', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Village_group/apply','', true, false, true)),'name'=>'注册成为团长','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
		array('module' => 'Live', 'linkcode' => $this->config['site_url'] . D('House_village')->base_url . 'pages/live_and_video/live/viewer/viewer_list', 'name'=>$this->modules['Live'],'sub'=>0,'canselected'=>1, 'linkurl'=>'', 'keyword'=>'', 'askeyword'=>1),

        //商城3.0拼团，砍价，秒杀
        array('module' => 'Mall', 'linkcode' =>$this->config['site_url'] . D('House_village')->base_url . 'pages/shopmall_third/mybargaining','name'=>$this->modules['new_mall_bargain'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
        array('module' => 'Mall', 'linkcode' =>$this->config['site_url'] . D('House_village')->base_url . 'pages/shopmall_third/makeGroup' ,'name'=>$this->modules['new_mall_group'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
        array('module' => 'Mall', 'linkcode' =>$this->config['site_url'] . D('House_village')->base_url . 'pages/shopmall_third/flashSale','name'=>$this->modules['new_mall_limited'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),

        array('module' => 'NewMall', 'linkcode' =>$this->config['site_url'] . D('House_village')->base_url . 'pages/shopmall_third/shopmall_index','name'=>$this->modules['new_mall_home'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),

//
//        array('module' => 'IntegralMall',
//                'linkcode' => $this->config['site_url'].'/wap.php?g=Wap&c=Integral_mall&a=storelist&mer_id='.$this->merchant_session['mer_id'],
//                'name'=>$this->modules['IntegralMall'],
//                'sub'=>0,
//                'canselected'=>1,
//                'linkurl'=>'',
//                'keyword'=>$this->modules['IntegralMall'],
//                'askeyword'=>1),
        array('module' => 'MarkList',
            'linkcode' => $this->config['site_url'].'/packapp/merchant/market_list.html',
            'name'=>$this->modules['MarkList'],
            'sub'=>1,
            'canselected'=>1,
            'linkurl'=>'',
            'keyword'=>$this->modules['MarkList'],
            'askeyword'=>1),
		);
		
		if (isset($this->config['no_foodshop'])) {
			$t[] = array('module' => 'Meal', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Meal_list/index', '', true, false, true)),'name'=>$this->modules['Meal'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
		} else {
			$t[] = array('module' => 'Meal', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Foodshop/index', '', true, false, true)),'name'=>$this->modules['Meal'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
		}

        // 新版餐饮2.0
		if ($this->config['meal_alias_name']) {
			$t[] = array('module' => 'new_foodshop_store', 'linkcode' => C('config.site_url') . D('House_village')->base_url . 'pages/foodshop/index/index','name'=>$this->modules['new_foodshop_store'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
		}
		
		if(!empty($this->config['wxapp_url'])){
			$t[] = array('module' => 'Wxapp', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Wxapp/index', '', true, false, true)),'name'=>$this->modules['Wxapp'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
		}
		// 功能-发现模块控制
        if ($this->config['find_msg']) {
            $t[] = array('module' => 'Discover', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Discover/discover_index', '', true, false, true)),'name'=>$this->modules['Discover'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        // 我的推广
        if (!isset($this->config['close_system_spread']) || $this->config['close_system_spread'] != 1) {
            $t[] = array('module' => 'Distributor_agent', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Distributor_agent/index', '', true, false, true)),'name'=>$this->modules['Distributor_agent'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        // 功能-平台活动模块控制
        if ($this->config['is_open_activity']) {
            $t[] = array('module' => 'Platform_classify_manager', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/My/activity_apply', '', true, false, true)),'name'=>$this->modules['Platform_classify_manager'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] = array('module' => 'Platform_activity_inspect', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Platform/activityInspect', '', true, false, true)),'name'=>$this->modules['Platform_activity_inspect'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] = array('module' => 'Platform_activity_indexList', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Platform/indexList', '', true, false, true)),'name'=>$this->modules['Platform_activity_indexList'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        // 本地化商城
        if ($this->config['localization_shopping_mall_store'] > 0) {
            $t[] = array('module' => 'LocalShopMall', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/LocalShopMall/index', '', true, false, true)),'name'=>$this->modules['LocalShopMall'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        // 定制-从业者模块
        if ($this->config['employee_switch'] > 0) {
            //  从业者列表页 包含分类详情
            $t[] = array('module' => 'Employee_list_info', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Employee/employee_list', '', true, false, true)),'name'=>$this->modules['Employee_list_info'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            // 从业者申请入驻 如果已经入驻过了会自动跳转个人入驻详情
            $t[] = array('module' => 'Employee_apply', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Employee/index', '', true, false, true)),'name'=>$this->modules['Employee_apply'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            // 从业人员个人详情页面
            $t[] = array('module' => 'Employee_owner_detail', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Employee/my_detail', '', true, false, true)),'name'=>$this->modules['Employee_owner_detail'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            // 从业人员个人详情编辑
            $t[] = array('module' => 'Employee_edit', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Employee/employee_info', '', true, false, true)),'name'=>$this->modules['Employee_edit'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            // 从业人员用户添加店铺
            $t[] = array('module' => 'Employee_claim_store_add', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Employee/store_add', '', true, false, true)),'name'=>$this->modules['Employee_claim_store_add'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            // 从业人员认领店铺列表
            $t[] = array('module' => 'Employee_claim_store', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Employee/employee_claim_store_list', '', true, false, true)),'name'=>$this->modules['Employee_claim_store'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            // 从业人员收藏列表
            $t[] = array('module' => 'Employee_collect_list', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Employee/employee_collect_list', '', true, false, true)),'name'=>$this->modules['Employee_collect_list'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        
        //商圈分类
        if($this->config['open_circle_category']){
        	$t[] = array('module' => 'circle_category', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/LocalShopMall/index', '', true, false, true)),'name'=>$this->modules['Circle_category'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1);
    	}


		if($this->config['open_sub_card']){
			$t[] = array('module' => 'Sub_card', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Sub_card/index', '', true, false, true)),'name'=>$this->modules['Sub_card'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
		}
		if(isset($this->config['sign_get_score'])){
			$t[] = array('module' => 'Sign', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/My/sign', '', true, false, true)),'name'=>$this->modules['Sign'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
		}

		if($this->config['portal_switch'] && !file_exists('conf/is_hake.no_delete')){
			$t[] = array('module' => 'Portal', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Portal/index', '', true, false, true)),'name'=>$this->modules['Portal'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);

			$t[] = array('module' => 'Portalactivity', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Portal/activity', '', true, false, true)),'name'=>$this->modules['Portalactivity'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
			$t[] = array('module' => 'Portalarticle', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Portal/article', '', true, false, true)),'name'=>$this->modules['Portalarticle'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
			$t[] = array('module' => 'Portaltieba', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Portal/tieba', '', true, false, true)),'name'=>$this->modules['Portaltieba'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
			$t[] = array('module' => 'Portalyellow', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Portal/yellow', '', true, false, true)),'name'=>$this->modules['Portalyellow'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
		}

		if(isset($this->config['appoint_site_name']) && !file_exists('conf/is_hake.no_delete')){
			$t[] = array('module' => 'Invitation', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Invitation/datelist', '', true, false, true)),'name'=>$this->modules['Invitation'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
		}
		if($this->config['appoint_page_row'] && !file_exists('conf/is_hake.no_delete')){
			$t[] = array('module' => 'AppointList', 'linkcode' => $this->config['site_url'] .$base_url . 'pages/appoint/index', 'name'=>$this->modules['AppointList'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
			$t[] = array('module' => 'AppointCategory', 'linkcode' => $this->config['site_url'] .$base_url . 'pages/appoint/category', 'name'=>$this->modules['AppointCategory'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
			$t[] = array('module' => 'AppointOrder', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/My/appoint_order_list', '', true, false, true)),'name'=>$this->modules['AppointOrder'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
			$t[] = array('module' => 'Home', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Workerstaff/index', array(), true, false, true)), 'name'=>($this->config['appoint_worker_name'] ? $this->config['appoint_worker_name'] : '技师').'中心','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>$this->modules['Workerstaff'],'askeyword'=>1);
		}
		if(isset($this->config['wap_home_show_classify']) && !file_exists('conf/is_hake.no_delete')){
			$t[] = array('module' => 'Classify', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Classify/index', '', true, false, true)),'name'=>$this->modules['Classify'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
		}
		if ($this->config['house_open']) {
            $param = [
                'pageName' => 'villagelist',
                'isAll' => true,
                'urlParam' => '',
            ];
            $villagelistUrl = D('House_village')->villagePagePath('','',$param);
			$t[] = array('module' => 'HousevillageList', 'linkcode' => $villagelistUrl,'name'=>$this->modules['HousevillageList'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
		}
		if ($this->config['is_open_weidian']) {
			$t[] = array('module' => 'NearWeiDian', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Weidian/near_store_redirect', '', true, false, true)),'name'=>$this->modules['NearWeiDian'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
		}
		if($this->config['waimai_alias_name']){
			$t[] = array('module' => 'WaimaiIndex', 'linkcode' => str_replace('admin.php', 'index.php', U('WaimaiWap/Index/index', '', true, false, true)),'name'=>$this->modules['WaimaiIndex'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
			$t[] = array('module' => 'WaimaiUser', 'linkcode' => str_replace('admin.php', 'index.php', U('WaimaiWap/User/index', '', true, false, true)),'name'=>$this->modules['WaimaiUser'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
			$t[] = array('module' => 'WaimaiOrder', 'linkcode' => str_replace('admin.php', 'index.php', U('WaimaiWap/Order/index', '', true, false, true)),'name'=>$this->modules['WaimaiOrder'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
			$t[] = array('module' => 'WaimaiCoupon2', 'linkcode' => str_replace('admin.php', 'index.php', U('WaimaiWap/User/coupon', array('coupon_type'=>2), true, false, true)),'name'=>$this->modules['WaimaiCoupon2'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
			$t[] = array('module' => 'WaimaiCoupon1', 'linkcode' => str_replace('admin.php', 'index.php', U('WaimaiWap/User/coupon', array('coupon_type'=>1), true, false, true)),'name'=>$this->modules['WaimaiCoupon1'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
			$t[] = array('module' => 'WaimaiAddress', 'linkcode' => str_replace('admin.php', 'index.php', U('WaimaiWap/User/adresList', '', true, false, true)),'name'=>$this->modules['WaimaiAddress'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
		}

// 			$t[] = array('module' => 'Deliver', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Deliver/grab', '', true, false, true)),'name'=>$this->modules['Deliver'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
			$t[] = array('module' => 'Deliver_new', 'linkcode' => $this->config['site_url'].'/packapp/deliver/index.html','name'=>$this->modules['Deliver_new'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);

        if ($this->config['open_deliver_register'] == 1) {
            $t[] = array('module' => 'DeliverRegister', 'linkcode' => $this->config['site_url'] . '/packapp/deliver_web/register.html', 'name' => $this->modules['DeliverRegister'], 'sub' => 0, 'canselected' => 1, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1);
        }

		if($this->config['ride_is']){
			$t[] = array('module' => 'Ride', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Ride/ride_list', array('plat'=>1), true, false, true)),'name'=>$this->modules['Ride'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
		}
		if($this->config['crowdsourcing_is']){
			$t[] = array('module' => 'Crowdsourcing', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Crowdsourcing/index', '', true, false, true)),'name'=>$this->modules['Crowdsourcing'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
		}
		if($this->config['live_service_have']){
			$t[] = array('module' => 'Lifeservice', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Lifeservice/index', '', true, false, true)),'name'=>$this->modules['Lifeservice'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
		}
		
		$t[] = array('module' => 'NewMerchantapp', 'linkcode' =>  $this->config['site_url'].'/packapp/merchant/index.html','name'=>$this->modules['NewMerchantapp'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);

		if(isset($this->config['gift_alias_name']) && !file_exists('conf/is_hake.no_delete')){
			$t[] = array('module' => 'Gift', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Gift/index', '', true, false, true)),'name'=>$this->modules['Gift'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
		}
		if($this->config['mobile_recharge_APIKey'] && $this->config['mobile_recharge_openid'] ){
			$t[] = array('module' => 'Mobile_recharge', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Third_recharge/mobile_recharge', '', true, false, true)),'name'=>$this->modules['Mobile_recharge'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
		}
		
		if(isset($this->config['scenic_now_city'])){
			$t[] = array('module' => 'Scenic_index', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Scenic_index/index', '', true, false, true)),'name'=>$this->modules['Scenic_index'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
		}

		if(isset($this->config['service_days_number']) && !file_exists('conf/is_hake.no_delete')){
			$t[] = array('module' => 'Service_index', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Service/index', '', true, false, true)),'name'=>$this->modules['Service_index'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
		}

		if(isset($this->config['is_examine'])){
			$t[] = array('module' => 'Yuedan', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Yuedan/index', '', true, false, true)),'name'=>$this->modules['Yuedan'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
		}
		if(isset($this->config['paotui_service']) && !file_exists('conf/is_hake.no_delete')){
			$t[] = array('module' => 'Paotui', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Service/paotui', array(), true, false, true)),'name'=>$this->modules['Paotui'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);

			// $t[] = array('module' => 'Paotui', 'linkcode' => C('config.site_url').'/packapp/paotui/pages/runErrands/index','name'=>$this->modules['Paotui'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
		}
        if($this->config['is_open_lotteryturn']){
            $t[] = array('module' => 'Lotterynew', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Lotterynew/index',  array('token'=>-1), true, false, true)),'name'=>$this->modules['Lotterynew'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if($this->config['open_c2b_spread']){
            $t[] = array('module' => 'UserSpreadMer', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/UserSpreadMer/index',  '', true, false, true)),'name'=>$this->modules['UserSpreadMer'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if($this->config['open_kefu_url'] && $this->config['kefu_mer_id'] && (!$this->config['jg_im_appkey'] || !$this->config['jg_im_masterkey'])){
            $t[] = array('module' => 'platOnlineService', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/My/concact_kefu',  array('mer_id'=>$this->config['kefu_mer_id']), true, false, true)),'name'=>$this->modules['platOnlineService'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        /*平台E卡*/
        if($this->config['ecard_alias_name']){
        	$t[]=array('module' => 'Mall', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Mall/ecard_index', '', true, false, true)),'name'=>$this->modules['ecard_alias_name'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        /*单个商品分销*/
        if($this->config['open_single_spread']){
        	$t[]=array('module' => 'Distributor_apply', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/My/distributor_apply', '', true, false, true)),'name'=>'申请成为分销员','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        // 招聘定制
        if($this->config['open_recruit']){
	        /*线下活动*/
	        $t[]=array('module' => 'ActivityOffline', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/ActivityOffline/index', '', true, false, true)),'name'=>'线下活动首页','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
	        
	        /*商学院*/
	        $t[]=array('module' => 'Recruit', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Recruit/course_index', '', true, false, true)),'name'=>'商学院首页','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
	        /*招聘首页*/
	        $t[]=array('module' => 'Recruit', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Recruit/index', '', true, false, true)),'name'=>'招聘首页','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
	        /*能量包首页*/
	        $t[]=array('module' => 'Recruit', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Recruit/energy_package', '', true, false, true)),'name'=>'能量包首页','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
	        /*积分购买首页*/
	        $t[]=array('module' => 'Recruit', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Recruit/score_package', '', true, false, true)),'name'=>'积分购买首页','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
	        /*简历首页*/
	        $t[]=array('module' => 'Recruit', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Recruit/resume_list', '', true, false, true)),'name'=>'简历首页','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
	        /*消息首页*/
	        $t[]=array('module' => 'Recruit', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Recruit/message', '', true, false, true)),'name'=>'消息首页','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);

	        /*商学院分类*/
	        $t[]=array('module' => 'recruit_course_cagetory', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Recruit/course_category_list', '', true, false, true)),'name'=>'商学院分类','sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
	    }
	    
	    // 瓜分券
        if($this->config['open_partition_coupon']){
	        /*瓜分券*/
	        $t[]=array('module' => 'partition_coupon_list', 'linkcode' => '','name'=>'瓜分券','sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1);
	    }
	    
	    // 我的推广二维码
        if($this->config['open_user_spread']){
	        /*我的推广二维码*/
	        $t[]=array('module' => 'my_spread_qrcode', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/My/my_spread_qrcode', '', true, false, true)),'name'=>'我的推广二维码','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
	    }

	    // 付款码
        if($this->config['open_company_advance_deposit']){
	        /*付款码*/
	        $t[]=array('module' => 'sweepCode', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Company_campus/sweepCode', '', true, false, true)),'name'=>'付款码','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
	        /*家长在线充值*/
	        $t[]=array('module' => 'parentRecharge', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Company_campus/searchSchool', '', true, false, true)),'name'=>'家长在线充值','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
	    }


        //平台客服
        if($this->config['jg_im_appkey'] && $this->config['jg_im_masterkey']){
            $t[] = array('module' => 'plat_kf', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Kefu/plat', '', true, false, true)), 'name' => '客服联系用户入口--新版客服', 'sub' => 0, 'canselected' => 1, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1);
            $t[] = array('module' => 'plat_kf', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Kefu/user', '', true, false, true)), 'name' => '用户联系客服入口--新版客服', 'sub' => 0, 'canselected' => 1, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1);
        }


        // 隐私政策
        $t[] = array('module' => 'privacy_policy', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Login/privacy_policy', '', true, false, true)), 'name' => '平台app隐私政策', 'sub' => 0, 'canselected' => 1, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1);
        $t[] = array('module' => 'staff_agreement', 'linkcode' => C('config.site_url').'/packapp/storestaff/agreement.html', 'name' => '店员app隐私政策', 'sub' => 0, 'canselected' => 1, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1);
        $t[] = array('module' => 'mer_agreement', 'linkcode' => C('config.site_url').'/wap.php?c=Login&a=mer_privacy_policy', 'name' => '商家app隐私政策', 'sub' => 0, 'canselected' => 1, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1);
        $t[] = array('module' => 'deliver_agreement', 'linkcode' => C('config.site_url').'/packapp/deliver/agreement.html', 'name' => '配送app隐私政策', 'sub' => 0, 'canselected' => 1, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1);
        $t[] = array('module' => 'village_manerge_agreement', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Login/village_privacy_policy', '', true, false, true)), 'name' => '社区管理app隐私政策', 'sub' => 0, 'canselected' => 1, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1);


        //新版优惠券中心
        $t[] = array('module' => 'system_coupon_center', 'linkcode' => C('config.site_url') . D('House_village')->base_url.'pages/coupon/index', 'name' => '新版平台领券中心','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        $t[] = array('module' => 'system_coupon_center', 'linkcode' => C('config.site_url') . D('House_village')->base_url.'pages/coupon/myCoupon', 'name' => '新版平台已领券列表','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);

		//平台优惠会员卡
		$t[] = array('module' => 'system_membership_card', 'linkcode' => C('config.site_url') . D('House_village')->base_url.'pages/membership/platMembership', 'name' => '平台优惠会员卡','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);


        if ($this->config['discuzx_url']) {
            //todo 论坛
            $t[] = array('module' => 'forum', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Discuz/main', array('village_id'=>$_SESSION['house']['village_id'],'type'=>1), true, false, true)),'name'=>$this->modules['forum'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);

            //todo 论坛版块
            $t[] = array('module' => 'forum_list', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Discuz/main', array('village_id'=>$_SESSION['house']['village_id'],'type'=>2), true, false, true)),'name'=>$this->modules['forum_list'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);

            //todo 单篇贴子
            $t[] = array('module' => 'single_forum', 'linkcode' => str_replace('admin.php', 'wap.php', U('Wap/Discuz/main', array('village_id'=>$_SESSION['house']['village_id'],'type'=>3), true, false, true)),'name'=>$this->modules['single_forum'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        
		if(C('customization.life_tools')){
			$t[] = array('module' => 'sport', 'linkcode' =>$this->config['site_url'] . $base_url . 'pages/lifeTools/sports/index','name'=>"体育健身",'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
			//$t[] = array('module' => 'sport', 'linkcode' =>$this->config['site_url'] . $base_url . 'pages/lifeTools/pocket/list','name'=>"员工卡列表",'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
			$t[] = array('module' => 'newsCenic', 'linkcode' =>$this->config['site_url'] . $base_url . 'pages/lifeTools/scenic/index','name'=>"新版景区",'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
			$t[] = array('module' => 'ticketAppoint', 'linkcode' =>$this->config['site_url'] . $base_url . 'pages/lifeTools/scenic/ticketBook','name'=>"门票预约",'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
		}

		foreach($t as $k=>$v){
			
			if(!$this->config['group_alias_name'] && in_array($v['module'],array('GroupNew','Group','AroundGroup','Group_order','Group_collect','Navigation','Hotel','MerchantStore','GroupMainPage','Village_group','Villagegroup'))){
				unset($t[$k]);
			}
			
			if($this->config['group_alias_name'] && $this->config['single_system_type'] == 'village_group' && in_array($v['module'],array('Group','AroundGroup','Store','News','Group_order','Hotel','Card_list','Group_collect','Navigation','MerchantStore','GroupMainPage','NewMerchantapp','Storestaff_new','Storestaff','All_Merchant_coupon','Deliver_new','Member','Sign','Activity','Card_mer_list','Group','Store','Meal_order','NearMerchant','Shop','Special_shop','Special_mall','Mall','Wapactivity','City_car','Live','Meal','privacy_policy','mer_agreement','deliver_agreement','village_manerge_agreement','staff_agreement','MarkList','QuickPay','Distributor_agent'))){
				unset($t[$k]);
			}
			
			if(!$this->config['meal_alias_name'] && in_array($v['module'],array('new_foodshop_store','Meal_order','Meal'))){
				unset($t[$k]);
			}
			
			if(!$this->config['mall_alias_name'] && in_array($v['module'],array('Mall','Special_mall','NewMall'))){
				unset($t[$k]);
			}
			
			if(!$this->config['shop_alias_name'] && in_array($v['module'],array('Special_shop','ShopLink','Shop','Shop_order'))){
				unset($t[$k]);
			}
			
			if(!empty($this->config['single_system']) && in_array($v['module'],array('City_car','Wxapp','Wapactivity','NearMerchant'))){
				unset($t[$k]);
			}
			
			if(!isset($this->config['live_open']) && in_array($v['module'],array('Live'))){
				unset($t[$k]);
			}
			
			
			if($this->config['single_system_type'] == 'shop' && in_array($v['module'],array('Mall', 'Special_mall', 'Home', 'village_manerge_agreement', 'MarkList', 'QuickPay'))){
				unset($t[$k]);
			}
		}
		if(file_exists('conf/is_hake.no_delete')){
			foreach($t as $k=>$v){
				if(in_array($v['module'],array('QuickPay','City_car','Hotel','MarkList'))){
					unset($t[$k]);
				}
			}
		}

	//	print_r($t);exit;
		return $t;
	}

    public function sport()
    {
        $items = array();
        $items[] = array('id' => 1, 'sub' => 0, 'name' => '体育首页', 'linkcode'=> $this->config['site_url'] . D('House_village')->base_url . 'pages/lifeTools/sports/index','sublink' => '','keyword' => '体育首页');
        $items[] = array('id' => 2, 'sub' => 0, 'name' => '体育课程分类列表', 'linkcode'=> $this->config['site_url'] . D('House_village')->base_url . 'pages/lifeTools/sports/catList?type=1','sublink' => '','keyword' => '体育课程分类列表');
        $items[] = array('id' => 3, 'sub' => 0, 'name' => '体育场馆分类列表', 'linkcode'=> $this->config['site_url'] . D('House_village')->base_url . 'pages/lifeTools/sports/catList?type=2','sublink' => '','keyword' => '体育场馆分类列表');
        $items[] = array('id' => 4, 'sub' => 0, 'name' => '体育资讯列表', 'linkcode'=> $this->config['site_url'] . D('House_village')->base_url . 'pages/lifeTools/information/list?type=sports','sublink' => '','keyword' => '体育资讯列表');
        $items[] = array('id' => 4, 'sub' => 0, 'name' => '赛事列表', 'linkcode'=> $this->config['site_url'] . D('House_village')->base_url . 'pages/lifeTools/match/list','sublink' => '','keyword' => '赛事列表');
        $this->assign('list', $items);
        $this->display('detail');
    }
	//批发市场
	public function MarkList(){
		$this->assign('moduleName', $this->modules['MarkList']);
		$category_id = $_GET['category_id'];
		$db = M('Goods_wholesale_category');
		$where = array();
		//$where['fid'] = array('gt',0);
		$where['status'] = 1;
		if($category_id){//子类
			$where['fid'] = $category_id;
		}else{
			$where['fid'] = 0;
		}
		$count      = $db->where($where)->count();
		$Page       = new Page($count,5);
		$show       = $Page->show();
		$list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('`sort` DESC, `id` ASC')->select();
		$items = array();
		foreach ($list as $item){
			if(empty($category_id)){//父类
				$items[]= array(
						'id' => $item['store_id'],
						'name' => $item['name'],
						'linkcode'=> '',
						'linkurl'=>'',
						'canselected'=>0,
						'sub'=>1,
						'sublink'=>U('MarkList',array('category_id'=>$item['id'])),
						'keyword' => $item['name'],
						'fcid'=>0
				);

			}else{//子类，需要跳详情页
				$items[]= array(
						'id' => $item['store_id'],
						'name' => $item['name'],
						'linkcode'=> $this->config['site_url'].'/packapp/merchant/market_category_list.html?cat_fid='.$item['fid'].'&cat_id='.$item['id'],
						'linkurl'=>'',
						'sub'=>1,
						'sublink'=>U('MarkInfo',array('cat_fid'=>$item['fid'],'cat_id'=>$item['id'])),
						'keyword' => $item['name'],

				);
			}

		}
		//dump($items);exit;
		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
	}

	public function MarkInfo(){
		$this->assign('moduleName', $this->modules['MarkList']);
		$cat_id = $_GET['cat_id'];
		$cat_fid = $_GET['cat_fid'];
		$db = M('market_goods');
		$where = array();
		$where['cat_id'] = $cat_id;
		$where['cat_fid'] = $cat_fid;
		$where['status'] = 1;
		$count      = $db->where($where)->count();
		$Page       = new Page($count,5);
		$show       = $Page->show();
		$list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('`mid` DESC, `last_time` DESC')->select();

		$items = array();
		foreach ($list as $item){
			$items[]= array(
					'id' => $item['goods_id'],
					'name' => $item['name'],
					'linkcode'=> $this->config['site_url'].'/packapp/merchant/market_buy_detail.html?goods_id='.$item['goods_id'],
					'linkurl'=>'',
					'sub'=>null,
					'sublink'=>'',
					'keyword' => $item['name']
			);
		}
		//dump($items);exit;
		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
	}
	
	public function Miniprogram()
	{
	    $items = array();
	    if($this->config['single_system_type'] != 'village_group'){
			$items[] = array('id' => 1, 'sub' => 0, 'name' => '平台首页', 'linkcode'=> '/pages/plat_menu/index','sublink' => '','keyword' => '平台首页');
			$items[] = array('id' => 2, 'sub' => 0, 'name' => $this->config['shop_alias_name'].'首页', 'linkcode'=> '/pages/shop_index/index','sublink' => '','keyword' => $this->config['shop_alias_name'].'首页');
			$items[] = array('id' => 3, 'sub' => 0, 'name' => '店铺列表', 'linkcode'=> '/pages/plat_menu/store','sublink' => '','keyword' => '店铺列表');
			$items[] = array('id' => 4, 'sub' => 0, 'name' => '个人中心', 'linkcode'=> '/pages/plat_menu/my','sublink' => '','keyword' => '个人中心');
		}
		if($this->config['single_system_type'] != 'shop'){
			$items[] = array('id' => 5, 'sub' => 0, 'name' => $this->config['village_group_alias'], 'linkcode'=> '/pages/plat_menu/index?redirect=webview&webview_url=%2Fwap.php%3Fc%3DVillage_group%26a%3Dindex&webview_title=','sublink' => '','keyword' => $this->config['village_group_alias']);
		}
		$this->assign('list', $items);
	    $this->display('detail');
	}
	public function ShopLink()
	{
		$this->assign('moduleName', $this->modules['ShopLink']);
		$cat_fid = isset($_GET['cat_fid']) ? intval($_GET['cat_fid']) : 0;
		$where = array('cat_fid' => $cat_fid);
		$db = D('Shop_category');
		$count      = $db->where($where)->count();
		$Page       = new Page($count, 5);
		$show       = $Page->show();

		$list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
		$items = array();
		foreach ($list as $item){
			if ($db->where(array('cat_fid' => $item['cat_id']))->find()) {
				array_push($items, array('id' => $item['cat_id'], 'sub' => 1, 'name' => $item['cat_name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Shop/classic_cat', array('cat_url'=>$item['cat_url']), true, false, true)),'sublink' => U('ShopLink', array('cat_fid' => $item['cat_id']), true, false, true),'keyword' => $item['cat_name']));
			} else {
				array_push($items, array('id' => $item['cat_id'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Shop/classic_cat', array('cat_url'=>$item['cat_url']), true, false, true)),'sublink' => '','keyword' => $item['cat_name']));
			}
		}
		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
	}

	public function Village_group()
	{
		$this->assign('moduleName', $this->modules['Village_group']);
		$where['status'] = 1;
		$where['fid'] = 0;
		$db = D('Village_group_category');
		$count      = $db->where($where)->count();
		$Page       = new Page($count, 20);
		$show       = $Page->show();

		$list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
		$items = array();
		foreach ($list as $item){
			$have_goods = M('Village_group_goods')->where(array('status'=>array('neq',4),'cat_fid'=>$item['cat_id'],'cat_id'=>0))->count();
			if($have_goods){//只有一级分类
				array_push($items, array(
						'id' => $item['cat_id'], 
						'sub' => 0, 
						'name' => $item['cat_name'], 
						'linkcode'=> $this->config['site_url'].'/packapp/community_group/index.html#/classify?cat_id='.$item['cat_id'],
						'sublink' => '',
						'keyword' => $item['cat_name']
					)
				);
			}else{
				array_push($items, array(
						'id' => $item['cat_id'], 
						'sub' => 1, 
						'name' => $item['cat_name'], 
						'linkcode'=> $this->config['site_url'].'/packapp/community_group/index.html#/classify?cat_id='.$item['cat_id'],
						'sublink' => U('Village_group_son',array('fid'=>$item['cat_id'])),
						'keyword' => $item['cat_name'],
					)
				);
			}
		}

		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
	}

	//二级页面
	public function Village_group_son(){
		$fid = $_GET['fid'];
		$this->assign('moduleName', $this->modules['Village_group']);
		$where['status'] = 1;
		$where['fid'] = $fid;
		$db = D('Village_group_category');
		$count      = $db->where($where)->count();
		$Page       = new Page($count, 20);
		$show       = $Page->show();

		$list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
		$items = array();
		foreach ($list as $item){
			array_push($items, array(
					'id' => $item['cat_id'], 
					'sub' => 0, 
					'name' => $item['cat_name'], 
					'linkcode'=> $this->config['site_url'].'/packapp/community_group/index.html#/classify_goods?cat_son_id='.$item['cat_id'].'&cat_id='.$fid,
					'sublink' => U('Village_group_son',array('fid'=>$item['cat_id'])),
					'keyword' => $item['cat_name']
				)
			);
		}

		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
	}

	public function Shop()
	{
		$this->assign('moduleName', $this->modules['Shop']);
		$cat_fid = isset($_GET['cat_fid']) ? intval($_GET['cat_fid']) : 0;
		$where = array('cat_fid' => $cat_fid);
		$db = D('Shop_category');
		$count      = $db->where($where)->count();
		$Page       = new Page($count, 4);
		$show       = $Page->show();

		$list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
		$items = array();
		
		$items[] = array('id' => '0', 'sub' => 0, 'name' => '全部分类', 'linkcode'=> $this->config['site_url'] . D('House_village')->base_url . 'pages/shop_new/shopType/shopType?cat_url=all&title=' . urlencode('全部分类'), 'keyword' => '全部分类');
		
		foreach ($list as $item){
			if ($db->where(array('cat_fid' => $item['cat_id']))->find()) {
				array_push($items, array('id' => $item['cat_id'], 'sub' => 1, 'name' => $item['cat_name'], 'linkcode'=> $this->config['site_url'] . D('House_village')->base_url . 'pages/shop_new/shopType/shopType?cat_url=' . $item['cat_url'] . '&title=' . urlencode($item['cat_name']), 'sublink' => U('Link/shop', array('cat_fid' => $item['cat_id']), true, false, true),'keyword' => $item['cat_name']));
			} else {
				array_push($items, array('id' => $item['cat_id'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> $this->config['site_url'] . D('House_village')->base_url . 'pages/shop_new/shopType/shopType?cat_url=' . $item['cat_url'] . '&title=' . urlencode($item['cat_name']), 'sublink' => '','keyword' => $item['cat_name']));
			}
		}
		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
	}

	public function Meal()
	{
		$this->assign('moduleName', $this->modules['Meal']);
		$cat_fid = isset($_GET['cat_fid']) ? intval($_GET['cat_fid']) : 0;
		$where = array('cat_fid' => $cat_fid, 'cat_status' => 1);
		$db = D('Meal_store_category');
		$count      = $db->where($where)->count();
		$Page       = new Page($count, 5);
		$show       = $Page->show();

		$list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
		$items = array();
		foreach ($list as $item){
// 			if ($db->where(array('cat_fid' => $item['cat_id']))->find()) {
// 				array_push($items, array('id' => $item['cat_id'], 'sub' => 1, 'name' => $item['cat_name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Foodshop/index', array('cat_url'=>$item['cat_url']), true, false, true)),'sublink' => U('Link/Meal', array('cat_fid' => $item['cat_id']), true, false, true),'keyword' => $item['cat_name']));
// 			} else {
				array_push($items, array('id' => $item['cat_id'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Foodshop/index', array('cat_url'=>$item['cat_url']), true, false, true)),'sublink' => '','keyword' => $item['cat_name']));
// 			}
		}
		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
	}

	// 餐饮2.0
	public function new_foodshop_store()
	{
		$this->assign('moduleName', $this->modules['new_foodshop_store']);
		$cat_fid = isset($_GET['cat_fid']) ? intval($_GET['cat_fid']) : 0;
		$where = array('cat_fid' => $cat_fid, 'cat_status' => 1);
		$db = D('Meal_store_category');
		$count      = $db->where($where)->count();
		$Page       = new Page($count, 5);
		$show       = $Page->show();

		$list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
		$items = array();
		foreach ($list as $item){
		    array_push($items, array('id' => $item['cat_id'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=>
                   C('config.site_url') . D('House_village')->base_url . 'pages/foodshop/search/searchList?cat_id='.$item['cat_id'],'sublink' => '','keyword' => $item['cat_name']));
		}
		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
	}

	public function Group()
	{
		$this->assign('moduleName', $this->modules['Group']);
		$cat_fid = isset($_GET['cat_fid']) ? intval($_GET['cat_fid']) : 0;
		$where = array('cat_fid' => $cat_fid);
		$db = D('Group_category');
		$count      = $db->where($where)->count();
		$Page       = new Page($count, 5);
		$show       = $Page->show();

		$list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
		$items = array();
		foreach ($list as $item){
			if ($db->where(array('cat_fid' => $item['cat_id']))->find()) {
				array_push($items, array('id' => $item['cat_id'], 'sub' => 1, 'name' => $item['cat_name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Group/index', array('cat_url' => $item['cat_url']), true, false, true)),'sublink' => U('Link/group', array('cat_fid' => $item['cat_id']), true, false, true),'keyword' => $item['cat_name']));
			} else {
				array_push($items, array('id' => $item['cat_id'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Group/index', array('cat_url' => $item['cat_url']), true, false, true)),'sublink' => '','keyword' => $item['cat_name']));
			}
		}
		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
	}

    public function GroupNew()
    {
        $this->assign('moduleName', $this->modules['GroupNew']);
        $cat_fid = isset($_GET['cat_fid']) ? intval($_GET['cat_fid']) : 0;
        $where = array('cat_fid' => $cat_fid);
        $db = D('Group_category');
        $count      = $db->where($where)->count();
        $Page       = new Page($count, 5);
        $show       = $Page->show();

        $list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
        $items = array();
        foreach ($list as $item){
            if ($db->where(array('cat_fid' => $item['cat_id']))->find()) {
                array_push($items, array('id' => $item['cat_id'], 'sub' => 1, 'name' => $item['cat_name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Groupnew/category', array('cat_id' => $item['cat_id'],'page_num'=>1), true, false, true)),'sublink' => U('Link/GroupNew', array('cat_fid' => $item['cat_id']), true, false, true),'keyword' => $item['cat_name']));
            } else {
                array_push($items, array('id' => $item['cat_id'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Groupnew/two_category', array('cat_id' => $item['cat_id'],'page_num'=>2), true, false, true)),'sublink' => '','keyword' => $item['cat_name']));
            }
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }


	public function Store()
	{
		$this->assign('moduleName', $this->modules['Store']);
		$cat_fid = isset($_GET['cat_fid']) ? intval($_GET['cat_fid']) : 0;
		$where = array('cat_fid' => $cat_fid,'cat_status'=>1);
		$dbStore = D('Merchant_category');
		$count      = $dbStore->where($where)->count();
		$Page       = new Page($count, 5);
		$show       = $Page->show();
		$list = $dbStore->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('cat_sort DESC, cat_id DESC')->select();
		$items = array();
        $site_url=C('config.site_url');
        if(C('config.config_site_url')){
            $site_url=C('config.config_site_url');
        }
        $site_url=rtrim($site_url,'/');
		foreach ($list as $item){
            $hassub=0;
            $url='';
            $sublink='';
            if($item['cat_fid']<1){
                $sublink=U('Link/Store', array('cat_id' => $item['cat_id']));
                $url = $site_url . '/packapp/platn/pages/store/v1/classifyPrimary/index?source_id=' . $item['cat_id'];
                $subdata=$dbStore->where(array('cat_fid'=>$item['cat_id'],'cat_status' => 1))->find();
                if($subdata && $subdata['cat_id']){
                    $hassub=1;
                }
            }else{
                $url=$site_url . '/packapp/platn/pages/store/v1/classifySecondary/index?source_id=' . $item['cat_id'];
            }
            $items[]=array('id' => $item['cat_id'], 'sub' => $hassub, 'name' => $item['cat_name'], 'linkcode'=> $url,'sublink' => $sublink ,'keyword' => "");
		}
		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
	}

	public function Sub_card()
	{
		$this->assign('moduleName', $this->modules['Sub_card']);
		$db = D('Sub_card');
		$where['status']  = 1;
		$count      = $db->where($where)->count();
		$Page       = new Page($count, 5);
		$show       = $Page->show();

		$list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
		$items = array();
		foreach ($list as $item){
			array_push($items, array('id' => $item['id'], 'sub' => 0, 'name' => $item['name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Sub_card/sub_card_detail', array('sub_card_id'=>$item['id']), true, false, true)),'sublink' => '','keyword' => $item['name']));
		}
		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
	}

	public function Yuedan()
	{
		$this->assign('moduleName', $this->modules['Yuedan']);
		$cat_fid = isset($_GET['cat_fid']) ? intval($_GET['cat_fid']) : 0;
		$where = array('status' => 2);
		$db = D('Yuedan_service_release');
		$count      = $db->where($where)->count();
		$Page       = new Page($count, 5);
		$show       = $Page->show();

		$list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
		$items = array();
		foreach ($list as $item){

			array_push($items, array('id' => $item['rid'], 'sub' => 0, 'name' => $item['title'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Shop/service_detail', array('rid'=>$item['rid']), true, false, true)),'sublink' => '','keyword' => $item['cat_name']));

		}
		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
	}

	public function News()
	{
		$this->assign('moduleName', $this->modules['News']);
		$where['status'] =  1;
		$db = D('System_news_category');
		$db2 = D('System_news');
		$count      = $db->where($where)->count();
		$Page       = new Page($count, 5);
		$show       = $Page->show();
		$list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
		$items = array();
		foreach ($list as $item){
			if ($db2->where(array('categroy_id' => $item['id']))->find()) {
				array_push($items, array('id' => $item['id'], 'sub' => 1, 'name' => $item['name'], 'linkcode'=> str_replace('admin.php', 'wap.php',  U('Wap/Systemnews/index', array('category_id' => $item['id']), true, false, true)),'sublink' => U('Link/news_content', array('category_id' => $item['id']), true, false, true),'keyword' => $item['name']));
			} else {
				array_push($items, array('id' => $item['id'], 'sub' => 0, 'name' => $item['name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Systemnews/index', array('category_id' => $item['id']), true, false, true)),'sublink' => '','keyword' => $item['cat_name']));
			}
		}
		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
	}

	public function News_content()
	{
		$this->assign('moduleName', $this->modules['News']);
		$where['status'] =  1;
		$cat_fid = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
		$where['category_id'] =$cat_fid;
		$db2 = D('System_news');
		$count      = $db2->where($where)->count();
		$Page       = new Page($count, 5);
		$show       = $Page->show();
		$list = $db2->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
		$items = array();
		foreach ($list as $item){
			array_push($items, array('id' => $item['id'], 'sub' => 0, 'name' => $item['title'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Systemnews/news', array('id' => $item['id']), true, false, true)),'sublink' => '','keyword' => $item['title']));
		}
		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
	}

	public function Mall()
	{
		$this->assign('moduleName', $this->modules['Mall']);
		$fid=I('fid',0,'intval');
		$where=array();
		$where['fid']=$fid;
		$where['status']=1;

		$db2 = D('Goods_category');
		$count = $db2->where($where)->count();
		$Page = new Page($count, 5);
		$show = $Page->show();
		$list = $db2->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('`sort` DESC, `id` ASC')->select();
		$items = array();
		foreach ($list as $item){
			if($item['fid']==0){
				array_push($items, array('id' => $item['id'], 'sub' => 1, 'name' => $item['name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Mall/goods_list', array('cat_id' => 0, 'cat_fid' => $item['id']), true, false, true)),'sublink' => U('Link/Mall',array('fid'=>$item['id']),true,false,true),'keyword' => $item['name']));
			}else{
				array_push($items, array('id' => $item['id'], 'sub' => 0, 'name' => $item['name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Mall/goods_list', array('cat_id' => $item['id'], 'cat_fid' => $item['fid']), true, false, true)),'sublink' => '','keyword' => $item['name']));	
			}
		}
		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
	}

    public function NewMall()
    {
        $this->assign('moduleName', $this->modules['new_mall_home']);
        $fid=I('fid',0,'intval');
        $where=array();
        $where['cat_fid']=$fid;
        $where['status']=1;

        $db2 = D('Mall_category');
        $count = $db2->where($where)->count();
        $Page = new Page($count, 5);
        $show = $Page->show();
        $list = $db2->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('`sort` DESC, `cat_id` ASC')->select();

        $items = array();
        foreach ($list as $item){
            $arr=$this->getCategory($item['cat_id'],$item['level'],$item['cat_fid']);
            if($item['level']==1){
                array_push($items, array('id' => $item['cat_id'],'child'=>$arr['child'],'sub' => 1, 'name' => $item['cat_name'], 'linkcode'=> $this->config['site_url'] .D('House_village')->base_url.'pages/shopmall_third/searchresult?searchcontent='.$item['cat_name'].'&cat_first='.$arr['cat_first'],'sublink' => U('Link/NewMall',array('fid'=>$item['cat_id']),true,false,true),'keyword' => $item['cat_name']));
            }elseif($item['level']==2){
                array_push($items, array('id' => $item['cat_id'],'child'=>$arr['child'],'sub' => 1, 'name' => $item['cat_name'], 'linkcode'=> $this->config['site_url'] .D('House_village')->base_url.'pages/shopmall_third/searchresult?searchcontent='.$item['cat_name'].'&cat_second='.$arr['cat_second'].'&cat_first='.$arr['cat_first'],'sublink' => U('Link/NewMall',array('fid'=>$item['cat_id']),true,false,true),'keyword' => $item['cat_name']));
            }else{
                array_push($items, array('id' => $item['cat_id'],'child'=>$arr['child'],'sub' => 1, 'name' => $item['cat_name'], 'linkcode'=> $this->config['site_url'] .D('House_village')->base_url.'pages/shopmall_third/searchresult?searchcontent='.$item['cat_name'].'&cate_three='.$arr['cate_three'].'&cat_second='.$arr['cat_second'].'&cat_first='.$arr['cat_first'],'sublink' => '','keyword' => $item['cat_name']));
            }
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('mall_detail');
    }

//取得分类需要的信息及子集数量
    public function getCategory($id,$level,$fid){
        $db2 = D('Mall_category');
        $where1['cat_fid']=$id;
        $where1['status']=1;
        $arr['child'] = $db2->where($where1)->count();
	    if($level==1){
	        $arr['cat_first']=$id;
        }elseif ($level==2){
            $arr['cat_second']=$id;
            $where1['cat_id']=$id;
            $arr['cat_first']=$fid;
        }else{
            $arr['cate_three']=$id;
            $where1['cat_id']=$arr['cat_second']=$fid;
            $res2=$db2->where($where1)->find();
            $arr['cat_first']=$res2['cat_fid'];
        }
	    return $arr;
    }

	public function LocalShopMall()
    {
        $this->assign('moduleName', $this->modules['LocalShopMall']);
        $fid = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
        $where = array('fid' => $fid, 'status' => 1);
        $db2 = D('Goods_category');
        $count = $db2->where($where)->count();
        $Page = new Page($count, 5);
        $show = $Page->show();
        $list = $db2->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('`sort` DESC, `id` ASC')->select();
        $items = array();
        foreach ($list as $item){
            if ($item['fid'] == 0) {
                array_push($items, array('id' => $item['id'], 'sub' => 1, 'name' => $item['name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/LocalShopMall/category', array('cat_id' => $item['id']), true, false, true)),'sublink' => U('Link/LocalShopMall', array('fid' => $item['id']), true, false, true),'keyword' => $item['name']));
            } else {
                array_push($items, array('id' => $item['id'], 'sub' => 0, 'name' => $item['name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/LocalShopMall/goods_list', array('cat_id' => $item['id'], 'cat_fid' => $item['fid']), true, false, true)),'sublink' => '','keyword' => $item['name']));
            }

        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }

    // 定制-从业者选择分类详情
    public function Employee_list_info(){
        $this->assign('moduleName', $this->modules['Employee_list_info']);
        $cat_fid = isset($_GET['cat_fid']) ? intval($_GET['cat_fid']) : 0;
        $cat_pfcid = isset($_GET['cat_pfcid']) ? intval($_GET['cat_pfcid']) : 0;
        $where = array('cat_fid' => $cat_fid, 'cat_pfcid' => $cat_pfcid, 'cat_status' => 1);
        $employee_category_db = M('Employee_category');
        $count = $employee_category_db->where($where)->count();
        $Page = new Page($count, 5);
        $show = $Page->show();
        $list = $employee_category_db->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('`cat_sort` DESC, `cat_id` ASC')->select();
        $items = array();
        foreach ($list as $item){
            if ($item['cat_fid'] == 0 && $item['cat_pfcid'] == 0) {
                array_push($items, array('id' => $item['cat_id'], 'sub' => 2, 'name' => $item['cat_name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Employee/employee_list', array('cat_url' => $item['cat_url']), true, false, true)),'sublink' => U('Link/Employee_list_info', array('cat_fid' => $item['cat_id']), true, false, true),'keyword' => $item['cat_name']));
            } elseif ($item['cat_fid'] != 0 && $item['cat_pfcid'] == 0) {
                array_push($items, array('id' => $item['cat_id'], 'sub' => 1, 'name' => $item['cat_name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Employee/employee_list', array('cat_url' => $item['cat_url']), true, false, true)),'sublink' => U('Link/Employee_list_info', array('cat_fid' => $item['cat_id'], 'cat_pfcid' => $item['cat_fid']), true, false, true),'keyword' => $item['cat_name']));
            } else {
                array_push($items, array('id' => $item['cat_id'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Employee/employee_list', array('cat_url' => $item['cat_url']), true, false, true)),'sublink' => '','keyword' => $item['cat_name']));
            }

        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }

    //商圈分类列表
    public function circle_category(){
    	$where=array();
    	$count=M('Circle_category')->where($where)->count(1);
    	$Page = new Page($count, 5);
        $show = $Page->show();
    	$list= M('Circle_category')->where($where)->limit($Page->firstRow,$Page->listRows)->select();
    	$data=array();
    	foreach ($list as $k => $v) {
    		$arr=array('id'=>$v['id'],'sub'=>0,'name'=>$v['name']);
    		$arr['linkcode']=str_replace('admin.php', 'wap.php', U('Wap/Merchant/circle_list',array('circle_category_id'=>$v['id']),true,false,true));
    		$arr['sublink']='';
    		$arr['keyword']=$v['name'];
    		$data[]=$arr;
    	}
    	$this->assign('list',$data);
    	$this->assign('page',$show);
    	$this->assign('moduleName', $this->modules['Circle_category']);
		$this->display('detail');
	} 

    //系统幸运大转盘列表
    public function Lotterynew(){
        $where['token'] = -1;
        $where['type'] = 1;
        $count = M('Lottery')->where($where)->count(1);
        $Page = new Page($count, 5);
        $show = $Page->show();
        $list = M('Lottery')->where($where)->limit($Page->firstRow,$Page->listRows)->select();
        $data = array();
        foreach ($list as $k => $v) {
            $arr=array('id'=>$v['id'],'sub'=>0,'name'=>$v['title']);
            $arr['linkcode']=str_replace('admin.php', 'wap.php', U('Wap/Lotterynew/index',array('token'=>-1,'id'=>$v['id']),true,false,true));
            $arr['sublink']='';
            $arr['keyword']=$v['title'];
            $data[]=$arr;
        }
        $this->assign('list',$data);
        $this->assign('page',$show);
        $this->assign('moduleName', $this->modules['Lotterynew']);
        $this->display('detail');
    }

    public function Wxapp()
    {
        $this->assign('moduleName', $this->modules['Wxapp']);
        
        $data = array(
            'bargain' => '砍价',
            'seckill' => '秒杀',
            'crowdfunding' => '众筹',
            'unitary' => $this->config['activity_score_alias'],
            'cutprice' => '降价拍',
            'lottery' => '大转盘',
            'red_packet' => '微信红包',
            'guajiang' => '刮刮卡',
            'jiugong' => '九宫格',
            'luckyFruit' => '幸运水果机',
            'goldenEgg' => '砸金蛋',
            'voteimg' => '图文投票',
            'custom' => '万能表单',
            'card' => '微贺卡', // 暂时不显示渠道二维码
            'game' => '微游戏',
            'live' => '微场景',
            'research' => '微调研',
            'autumn' => '中秋吃月饼活动',
            'helping' => '微助力',
            'donation' => '募捐',
            'coinTree' => '摇钱树',
            'collectword' => '集字游戏',
            'sentiment' => '谁是情圣',
            'frontPage' => '我要上头条',
            'test' => '趣味测试',
            'punish' => '惩罚台',
            'shakeLottery' => '摇一摇',
            'youSetDiscount' => '优惠接力',
            'popularity' => '人气冲榜',
            'problem' => '一战到底',
            'seniorScene' => '微场景',
            'auction' => '微拍卖'
        );
        
        $count = count($data);
        
        $Page = new Page($count, 6);
        $show = $Page->show();
        $list = array_slice($data, $Page->firstRow, $Page->listRows); 
        $items = array();
        foreach ($list as $index => $item){
            array_push($items, array('id' => 0, 'sub' => 0, 'name' => $item, 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Wxapp/index', array('cat_url' => $index), true, false, true)),'sublink' => '','keyword' => $item));
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }
    
	public function Coupon()
	{
		$this->assign('moduleName', $this->modules['Coupon']);
		$where['end_time'] = array('gt',time());
		$where['status'] = array('neq',0);
		$where['allow_new'] = 0;
		$where['delete'] = 0;
		$db2 = D('System_coupon');
		$count = $db2->where($where)->count();

		$Page = new Page($count, 5);
		$list = $db2->where($where)->limit($Page->firstRow,$Page->listRows)->order(' `coupon_id` DESC')->select();
		$show = $Page->show();
		$items = array();
		foreach ($list as $item){
			array_push($items, array('id' => $item['id'], 'sub' => 0, 'name' => $item['name'], 'linkcode'=>$item['coupon_id'],'sublink' => '','keyword' => $item['name']));
		}
		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('coupon_select');
	}
	
	public function Special_shop()
	{
		$where = array('type' => 1);
		if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $where = "0";
            } elseif($now_area['area_type'] == 2) {
                $city_id = $this->system_session['area_id'];
                $province_id = $now_area['area_pid'];
                $where['province_id'] = $province_id;
                $where['city_id'] = $city_id;
            } elseif ($now_area['area_type'] == 1) {
                $province_id = $this->system_session['area_id'];
                $where['province_id'] = $province_id;
            }
        }

		$this->assign('moduleName', $this->modules['Special_shop']);
		$db2 = D('Special');
		$count = $db2->where($where)->count();
		$Page = new Page($count, 5);
		$show = $Page->show();
		$list = $db2->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('`pigcms_id` ASC')->select();
		$items = array();
		foreach ($list as $item){
			array_push($items, array('id' => $item['id'], 'sub' => 0, 'name' => $item['name'], 'linkcode'=> $this->config['site_url'] . D('House_village')->base_url.'pages/shop_new/shopSpecial/shopSpecial?id='.$item['pigcms_id'],'sublink' => '','keyword' => $item['name']));
		}
		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
	}

	//商城专题
	public function Special_mall()
	{
		$this->assign('moduleName', $this->modules['Special_mall']);
		$where = array('type' => 3);
		$db2 = D('Special');
		$count = $db2->where($where)->count();
		$Page = new Page($count, 5);
		$show = $Page->show();
		$list = $db2->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('`pigcms_id` ASC')->select();
		$items = array();
		foreach ($list as $item){
			array_push($items, array('id' => $item['id'], 'sub' => 0, 'name' => $item['name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Special/index', array('id' => $item['pigcms_id']), true, false, true)),'sublink' => '','keyword' => $item['name']));
		}
		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
	}

	public function Classify()
	{
		$this->assign('moduleName', $this->modules['Classify']);
		$cid = isset($_GET['cid']) ? intval($_GET['cid']) : '';
		$where['fcid'] = $cid;
		$where['cat_status'] = 1;
		$db = D('Classify_category');
		$count      = $db->where($where)->count();
		$Page       = new Page($count, 5);
		$show       = $Page->show();

		$list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
		$items = array();
        if($_GET['p']<2){
            $items[] = array(
                'id' => 0,
                'sub' => 0,
                'name' => '房产销售',
                'linkcode'=> $this->config['site_url'].'/packapp/plat/pages/lifeTools/buildingSale/list',
                'sublink' => '',
                'keyword' => ''
            );
        }
		foreach ($list as $item){
			if ($db->where(array('fcid' => $item['cid']))->getField('subdir') == 2 || $item['fcid'] == 0) {
				array_push($items, array('id' => $item['cid'],'fcid'=>$item['cid'], 'sub' => 1, 'name' => $item['cat_name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Classify/index', array('cid' => $item['cid']), true, false, true)),'sublink' => U('Link/Classify', array('cid' => $item['cid']), true, false, true),'keyword' => ''));
			} else {
				array_push($items, array('id' => $item['cid'], 'fcid'=>$item['fcid'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Classify/Lists', array('cid' => $item['cid']), true, false, true)),'sublink' => '','keyword' => ''));
			}
		}
		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
		
	}

	public function AppointCategory()
	{
		$this->assign('moduleName', $this->modules['AppointCategory']);
		$db = D('AppointCategory');
		$cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
		$where['cat_fid'] = $cat_id;
		$where['cat_status'] = 1;
		$count      = $db->where($where)->count();
		$Page       = new Page($count, 5);
		$show       = $Page->show();

		$list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
		$items = array();
		foreach ($list as $item){
			if ($db->where(array('cat_id' => $item['cat_id']))->getField('cat_fid')) {
				array_push($items, array('id' => $item['cat_id'], 'cat_fid'=>$item['cat_fid'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Appoint/two_category', array('cat_id' => $item['cat_id']), true, false, true)),'sublink' => '','keyword' => $item['cat_name']));
			}else{
				$linkCode = $this->config['site_url'] . D('House_village')->base_url . 'pages/appoint/category?cat_id=' . $item['cat_id'];
				array_push($items, array('id' => $item['cat_id'], 'cat_fid'=>$item['cat_fid'], 'sub' => 1, 'name' => $item['cat_name'], 'linkcode'=> $linkCode,'sublink' => U('Link/AppointCategory', array('cat_id' => $item['cat_id']), true, false, true),'keyword' => ''));
			}
		}

		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
	}

	// 门户活动
	public function Portalactivity()
	{
		$this->assign('moduleName', $this->modules['Portalactivity']);
		$db = D('Portal_activity_cat');

		$where['cat_fid'] = 0;
		$where['cat_status'] = 1;
		$order = 'cat_sort desc,cid desc';
		$count      = $db->where($where)->count();
		$Page       = new Page($count, 5);
		$show       = $Page->show();

		$list = $db->where($where)->order($order)->limit($Page->firstRow.','.$Page->listRows)->select();
		$items = array();
		foreach ($list as $item){
			
			array_push($items, array('id' => $item['cid'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Portal/activity', array('cid' => $item['cid']), true, false, true)),'keyword' => ''));
			
		}
		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
	}

	// 门户资讯
	public function Portalarticle()
	{
		$this->assign('moduleName', $this->modules['Portalarticle']);
		$db = D('Portal_article_cat');

		$where['cat_fid'] = 0;
		$where['cat_status'] = 1;
		$count      = $db->where($where)->count();
		$Page       = new Page($count, 5);
		$show       = $Page->show();

		$list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
		$items = array();
		foreach ($list as $item){
			array_push($items, array('id' => $item['cid'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Portal/article', array('cid' => $item['cid']), true, false, true)),'keyword' => ''));
		}
		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
	}

	// 积分商城
	public function Gift()
	{
		$this->assign('moduleName', $this->modules['Gift']);
		$db = D('Gift_category');

		$where['cat_fid'] = 0;
		$where['is_del'] = 0;
		$where['cat_status'] = 1;
		$count      = $db->where($where)->count();
		$Page       = new Page($count, 5);
		$show       = $Page->show();

		$list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
		$items = array();
		foreach ($list as $item){
			array_push($items, array('id' => $item['cat_id'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Gift/gift_list', array('cat_id' => $item['cat_id']), true, false, true)),'keyword' => ''));
		}
		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
	}
	public function QuickPay()
	{
		$this->assign('moduleName', $this->modules['QuickPay']);
		$cat_fid = isset($_GET['cat_fid']) ? intval($_GET['cat_fid']) : 0;
		$where = array('cat_fid' => $cat_fid, 'status' => 1);
		$db = D('Shop_category');
		$count = $db->where($where)->count();
		$Page = new Page($count, 5);
		$show = $Page->show();
		
		$list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
		$items = array();
		foreach ($list as $item){
			if ($db->where(array('cat_fid' => $item['cat_id']))->find()) {
				array_push($items, array('id' => $item['cat_id'], 'sub' => 1, 'name' => $item['cat_name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/QuickPay/index', array('cat_url' => $item['cat_url']), true, false, true)), 'sublink' => U('Link/QuickPay', array('cat_fid' => $item['cat_id']), true, false, true),'keyword' => $item['cat_name']));
			} else {
				array_push($items, array('id' => $item['cat_id'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/QuickPay/index', array('cat_url' => $item['cat_url']), true, false, true)), 'sublink' => '','keyword' => $item['cat_name']));
			}
		}
		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
	}


	public function recruit_course_cagetory()
	{
		$this->assign('moduleName', $this->modules['recruit_course_cagetory']);
		$where['is_hide'] =  0;
		$db = D('Recruit_course_category');
		$count      = $db->where($where)->count();
		$Page       = new Page($count, 5);
		$show       = $Page->show();
		$list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
		$items = array();
		foreach ($list as $item){
			array_push($items, array('id' => $item['id'], 'sub' => 1, 'name' => $item['name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Recruit/course_category_list', array('cat_id' => $item['id']), true, false, true)),'sublink' => U('Link/recruit_course_list', array('cat_id' => $item['id'])),'keyword' => $item['name']));
			
		}
		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
	}

	public function recruit_course_list()
	{
		$this->assign('moduleName', $this->modules['recruit_course_cagetory']);
		$where['status'] =  1;
		$where['cat_id'] =  $_GET['cat_id'];
		$db = D('Recruit_course');
		$count      = $db->where($where)->count();
		$Page       = new Page($count, 5);
		$show       = $Page->show();
		$list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
		$items = array();
		foreach ($list as $item){
			array_push($items, array('id' => $item['id'], 'sub' => 0, 'name' => $item['title'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Recruit/course_detail', array('course_id' => $item['course_id']), true, false, true)),'sublink' => '','keyword' => $item['name']));
			
		}
		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
	}


	public function partition_coupon_list()
	{
		$this->assign('moduleName', $this->modules['Partition_coupon']);
		$where['status'] =  1;
		$where['end_time'] =  array('gt',time());
		$where['delete'] =  0;
		$db = D('System_partition_coupon');
		$count      = $db->where($where)->count();
		$Page       = new Page($count, 5);
		$show       = $Page->show();
		$list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
		$items = array();
		foreach ($list as $item){
			array_push($items, array('id' => $item['id'], 'sub' => 0, 'name' => $item['name'], 'linkcode'=> str_replace('admin.php', 'wap.php', U('Wap/Partition_coupon/partition_coupon_detail', array('id' => $item['pigcms_id']), true, false, true)),'sublink' => '','keyword' => $item['name']));
			
		}
		$this->assign('list', $items);
		$this->assign('page', $show);
		$this->display('detail');
	}

	public function newsCenic()
	{
		$items = array();
        $items[] = array('id' => 1, 'sub' => 0, 'name' => '景区首页', 'linkcode'=> $this->config['site_url'] . D('House_village')->base_url . 'pages/lifeTools/scenic/index','sublink' => '','keyword' => '景区首页');
        $items[] = array('id' => 2, 'sub' => 0, 'name' => '景区资讯', 'linkcode'=> $this->config['site_url'] . D('House_village')->base_url . 'pages/lifeTools/information/list?type=scenic','sublink' => '','keyword' => '景区资讯');
        $items[] = array('id' => 3, 'sub' => 0, 'name' => '寻人求助', 'linkcode'=> $this->config['site_url'] . D('House_village')->base_url . 'pages/lifeTools/help/helpList','sublink' => '','keyword' => '寻人求助');
        $items[] = array('id' => 4, 'sub' => 0, 'name' => '投诉建议', 'linkcode'=> $this->config['site_url'] . D('House_village')->base_url . 'pages/lifeTools/advice/list?type=sports','sublink' => '','keyword' => '投诉建议');
        $items[] = array('id' => 4, 'sub' => 0, 'name' => '景区列表', 'linkcode'=> $this->config['site_url'] . D('House_village')->base_url . 'pages/lifeTools/scenic/ticketBook?type=scenic&showTabs=false','sublink' => '','keyword' => '景区列表');
        $this->assign('list', $items);
        $this->display('detail');
	}

    public function newGroupVersion(){
        $dbGroup = D('Group_category');
        $whereArr=array('cat_status' => 1,'cat_fid'=>0);
        $count      = $dbGroup->where($whereArr)->count();
        $Page       = new Page($count, 5);
        $show       = $Page->show();
        $list = $dbGroup->where($whereArr)->order('cat_sort DESC,cat_id ASC')->limit($Page->firstRow.','.$Page->listRows)->select();
        $items = array();
        foreach ($list as $item){
            $url = get_base_url('pages/group/index/catPage?cat_fid='. $item['cat_id'] . '&cat_name=' . urlencode($item['cat_name']));
            $hassub=0;
            $subdata=$dbGroup->where(array('cat_fid'=>$item['cat_id'],'cat_status' => 1))->find();
            if($subdata && $subdata['cat_id']){
                $hassub=1;
            }
            $items[]=array('id' => $item['cat_id'], 'sub' => $hassub, 'name' => $item['cat_name'], 'linkcode'=> $url,'sublink' =>  U('Link/newGroupVersionSub', array('cat_id' => $item['cat_id'])),'keyword' => "");
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }

    public function newGroupVersionSub(){
        $dbGroup = D('Group_category');
        $cat_id =  $_GET['cat_id'] ? $_GET['cat_id']:0;
        $whereArr=array('cat_status' => 1,'cat_fid'=>$cat_id);
        $fdata=$dbGroup->where(array('cat_id'=>$cat_id,'cat_status' => 1))->find();
        $fcat_name='';
        if($fdata && $fdata['cat_name']){
            $fcat_name=$fdata['cat_name'];
        }
        $count      = $dbGroup->where($whereArr)->count();
        $Page       = new Page($count, 5);
        $show       = $Page->show();
        $list = $dbGroup->where($whereArr)->order('cat_sort DESC,cat_id ASC')->limit($Page->firstRow.','.$Page->listRows)->select();
        $items = array();
        foreach ($list as $item){
            $url = get_base_url('pages/group/index/list?cat_fid='. $item['cat_fid'] . '&cat_name=' .urlencode( $fcat_name) . '&cat_id='.$item['cat_id'].'&type=store');
            $items[]=array('id' => $item['cat_id'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> $url,'sublink' => '','keyword' => "");
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }

    public function newMallVersion(){
        $dbMall = D('Mall_category');
        $whereArr=array('status' => 1,'is_del'=>0,'cat_fid'=>0);
        $count      = $dbMall->where($whereArr)->count();
        $Page       = new Page($count, 5);
        $show       = $Page->show();
        $list = $dbMall->where($whereArr)->order('sort DESC,cat_id ASC')->limit($Page->firstRow.','.$Page->listRows)->select();
        $items = array();
        $site_url=C('config.site_url');
        if(C('config.config_site_url')){
            $site_url=C('config.config_site_url');
        }
        $site_url=rtrim($site_url,'/');
        foreach ($list as $item){
            $url = $site_url . '/packapp/plat/pages/shopmall_third/searchresult?searchcontent='.$item['cat_name'].'&cat_first=' . $item['cat_id'];
            $hassub=0;
            $subdata=$dbMall->where(array('cat_fid'=>$item['cat_id'],'status' => 1,'is_del'=>0,'level'=>2))->find();
            if($subdata && $subdata['cat_id']){
                $hassub=1;
            }
            $items[]=array('id' => $item['cat_id'], 'sub' => $hassub, 'name' => $item['cat_name'], 'linkcode'=> $url,'sublink' =>  U('Link/newMallVersionSub', array('cat_id' => $item['cat_id'])),'keyword' => "");
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }

    public function newMallVersionSub(){
        $dbMall = D('Mall_category');
        $cat_id =  $_GET['cat_id'] ? $_GET['cat_id']:0;
        $whereArr=array('status' => 1,'is_del'=>0,'cat_fid'=>$cat_id);
        $fdata=$dbMall->where(array('cat_id'=>$cat_id))->find();
        $fcat_name='';
        if($fdata && $fdata['cat_name']){
            $fcat_name=$fdata['cat_name'];
        }
        $count      = $dbMall->where($whereArr)->count();
        $Page       = new Page($count, 5);
        $show       = $Page->show();
        $list = $dbMall->where($whereArr)->order('sort DESC,cat_id ASC')->limit($Page->firstRow.','.$Page->listRows)->select();
        $items = array();
        $site_url=C('config.site_url');
        if(C('config.config_site_url')){
            $site_url=C('config.config_site_url');
        }
        $site_url=rtrim($site_url,'/');
        foreach ($list as $item){
            $url = $site_url . '/packapp/plat/pages/shopmall_third/searchresult?searchcontent='.$item['cat_name'].'&cat_second=' . $item['cat_id'];
            if($item['level'] >2){
                $url = $site_url . '/packapp/plat/pages/shopmall_third/searchresult?searchcontent='.$item['cat_name'].'&cat_three=' . $item['cat_id'];
            }
            $hassub=0;
            $subdata=$dbMall->where(array('cat_fid'=>$item['cat_id'],'status' => 1,'is_del'=>0))->find();
            if($subdata && $subdata['cat_id']){
                $hassub=1;
            }
            $items[]=array('id' => $item['cat_id'], 'sub' => $hassub, 'name' => $item['cat_name'], 'linkcode'=> $url,'sublink' =>  U('Link/newMallVersionSub', array('cat_id' => $item['cat_id'])),'keyword' => "");
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }
}
?>