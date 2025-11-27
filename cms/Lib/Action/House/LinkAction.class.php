<?php
class LinkAction extends BaseAction{
    public $modules;
    public $modules_admin;
    public $modules_merchant_content;

    public function _initialize()
    {
        parent::_initialize();

        $this->modules = array(
            'Member' => '会员中心',
            'HousevillageList' => C('config.house_name').'列表',
            'Housevillage'=>C('config.house_name').'首页',
            'HousevillageNewsCatelist'=>C('config.house_name').'新闻分类',
            'HousevillageNewslist'=>C('config.house_name').'新闻',
            'HouseVenue'=>'场馆预约',
            'RenovationApplyList'=>'在线文件管理',
            'RenovationApplyTitle'=>C('config.house_name').'在线文件管理',
            'ActiveGroupList'=>'推荐'.$this->config['group_alias_name'],
            'ActiveMealList'=>"推荐".$this->config['meal_alias_name'],
            'ActiveAppointList'=>'推荐'.$this->config['appoint_alias_name'],
            'HousevillageMy'=>"个人中心",
            'HouseserviceserviceCategory'=>'便民服务',
            'HousevillagePayProperty'=>'缴物业费',
            'HousevillageProperty'=>'缴费',
            'WorkOrder'=>'工单',
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
            'openCode'=>'开门二维码',
            'forum'=>'DiscuzX论坛',
            'forum_list'=>'论坛版块',
            'single_forum'=>'单篇贴子',
            'HouseAskHelp'=>L_('寻求帮助'),
            'HouseMyPartyMember'=>L_('党员信息'),
        );

        $this->modules_admin = array(
            'Home' => '首页',
            'AroundGroup' => '附近'.$this->config['group_alias_name'],
            'Group' => $this->config['group_alias_name'],
            'Store' => $this->config['merchant_alias_name'].'分类',
// 			'AroundMeal' => '附近'.$this->config['meal_alias_name'],
            'Meal' => $this->config['meal_alias_name'],
            'new_foodshop_store' => '新版'.C('config.meal_alias_name'),
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
            'HousevillageList' => C('config.house_name').'列表',
            'News' => '平台快报',
            'Coupon' => '平台优惠券',
            'All_Merchant_coupon' => '全部商家优惠券',
            'Ride' => '顺风车',
            'Crowdsourcing' => '众包',
            'Shop' => $this->config['shop_alias_name'],
            'ShopLink' => '链接版'.$this->config['shop_alias_name'],
            'MerchantStore' => $this->config['store_alias_name'].'列表',
            'Gift' => $this->config['gift_alias_name'],
            'Workerstaff'=>($this->config['appoint_worker_name'] ? $this->config['appoint_worker_name'] : '技师').'中心',
            'Mall'=>'商城',
            'newMallVersion'=>'新版商城',
            'Sign'=>'签到',
            'Wxapp'=>'营销活动',
            'Portal'=>'门户首页',
            'Wapactivity'=>'限时活动',
            'City_car'=>'快捷挪车',
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
            'Live' => '直播',
            'new_mall_home'=>'商城3.0首页',
            'new_mall_bargain'=>'商城3.0砍价',
            'new_mall_group'=>'商城3.0拼团',
            'new_mall_limited'=>'商城3.0秒杀',
            'project_employment' => '求职首页',
            'wantToBuyIndex' => '种草首页',
            'wantToBuySeach' => '种草搜索页',
            'wantToBuySquare' => '种草话题广场',
            'wantToBuyTopic' => '种草话题',
            'newGroupVersion' =>'新版团购',
        );

        $this->modules_merchant_content = array(
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
          //  'Integral_mall_index' => '积分商城-首页',
            'MarkList' => '商家中心-批发市场',
        );
    }


    public function insert()
    {

        $modules = $this->modules();

        $this->assign('modules', $modules);
        //商家功能库
        $village_id = $this->house_session['village_id'];
        $house_village = D('House_village')->where(array('village_id'=>$village_id))->find();//property_id
        $property = M('House_property')->where(array('id'=>$house_village['property_id']))->find();

        $modules_admin = $this->modules_admin($property);
        $this->assign('modules_admin', $modules_admin);

        if($property['merchant_id'] >0){
            $database_merchant = D('Merchant');
            $condition_merchant['mer_id'] = $property['merchant_id'];
            $now_merchant = $database_merchant->field('*')->where($condition_merchant)->find();
            session('merchant',$now_merchant);
            session('staff',null);
            $this->merchant_session = session('merchant');
            $modules_merchant = $this->modules_merchant();
        }else{
            $modules_merchant = [];
        }
//        echo "<pre>";
//        var_dump($modules);die;

        $this->assign('modules_merchant', $modules_merchant);

        $this->display();
    }


    public function programs()
    {

    }

    private function modules()
    {
        $base_url = D('House_village')->base_url;
        $street_url = D('House_village')->street_url;
        $iskeyword=trim($_GET['iskeyword']);
        $village_id = $this->house_session['village_id'];
        $list = D('House_link')->village_library($village_id,$this->config,$this->modules,'shequ.php',$this->house_session['property_id']);
        if($iskeyword=='on_deving'){
            //正在开发中
            $list[] = array('module' => 'on_deving', 'linkcode' => 'on_deving','name'=>'功能开发中','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        $list[] = array('module' => 'to_yunmou', 'linkcode' => 'to_yunmou','name'=>'云眸小程序','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);

        $unitRentalArr=array('id'=>'112041','module'=>'UnitRental','status'=>1,'show'=>1);
        $unitRental=M('House_menu_new')->where($unitRentalArr)->find();
        $res['have_unit_rental']=0;
        if($unitRental){
            $whereArr=array('from'=>'house','from_id'=>$village_id,'template_type'=>array('in',array(1,2,3)));
            $tmpArr=M('Plugin_material_diy_template')->where($whereArr)->field('template_type')->find();
            if(!empty($tmpArr)){
               $list[] = array('module' => 'housePublicRental', 'linkcode' => 'housePublicRental','name'=>'公租房预约','sub'=>1,'canselected'=>0,'linkurl'=>U('Link/housePublicRental'),'keyword'=>'','askeyword'=>1);
            }
        }
        $is_jz_wsxdsj=D('Config_customization')->getWSXCustomizedOpen();
        if($is_jz_wsxdsj){
            $list[] = array('module' => 'snatchParkingPosition', 'linkcode' => $this->config['site_url'] .'/packapp/village/pages/snatchParking/index','name'=>'抢购车位','sub'=>0,'canselected'=>1,'linkurl'=>'' ,'keyword'=>'','askeyword'=>1);
            $list[] = array('module' => 'snatchParkingPositionRent', 'linkcode' => $this->config['site_url'] .'/packapp/village/pages/snatchParking/rent_index','name'=>'租赁车位','sub'=>0,'canselected'=>1,'linkurl'=>'' ,'keyword'=>'','askeyword'=>1);
        }
        $zhsq_hs_zhiyunkjt_customized=D('Config_customization')->get_zhsq_hs_zhiyunkjt_customized();
        if($zhsq_hs_zhiyunkjt_customized){
            $list[] = array('module' => 'reserveH5External', 'linkcode' => 'http://ctjywy.cn/visitor/weChatAndH5/visitorRegistered','name'=>'分享链接','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        $isAihorseCustomized=D('Config_customization')->getIsAihorseCustomized();
        if($isAihorseCustomized){
            $list[] = array('module' => '', 'linkcode' => $this->config['site_url'] .'/packapp/village/pages/village/index/openLock','name'=>'开锁中心','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        return $list;
        
        $t = array(
//            array('module' => 'Home', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Home/index', '', true, false, true)), 'name'=>'平台首页','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>$this->modules['Home'],'askeyword'=>1),

            array('module' => 'Housevillage', 'linkcode' => $this->config['site_url'] . $base_url . 'pages/village_menu/index', 'name'=>$this->modules['Housevillage'], 'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),

            array('module' => 'HousevillageList', 'linkcode' => $this->config['site_url'] .$base_url . 'pages/village/my/villagelist', 'name'=>$this->modules['HousevillageList'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),

            array('module' => 'HouseRide', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Ride/ride_list', array('village_id'=>$village_id), true, false, true)),'name'=>$this->modules['HouseRide'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'HousephoneIndex', 'linkcode' => $this->config['site_url'] . $base_url . 'pages/village/commonly_used/house_phone?village_id='.$village_id, 'name'=>$this->modules['HousephoneIndex'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),

            array('module' => 'HousevillageNewsCatelist', 'linkcode' => $this->config['site_url'] .$base_url . 'pages/village/index/notice?village_id=' . $village_id, 'name'=>$this->modules['HousevillageNewsCatelist'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),

            //todo 场馆预约
            array('module' => 'HouseVenue', 'linkcode' => $this->config['site_url'] .$base_url . 'pages/houseMeter/venuerese/venuelist?village_id=' . $village_id,'name'=>$this->modules['HouseVenue'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),

            //todo 装修申请单
            array('module' => 'RenovationApplyList', 'linkcode' => $this->config['site_url'] .$base_url . 'pages/village/index/notice?village_id=' . $village_id, 'name'=>$this->modules['RenovationApplyList'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),

            array('module' => 'Member', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/My/index', '', true, false, true)),'name'=>$this->modules['Member'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),


            array('module' => 'HousevillageMy', 'linkcode' => $this->config['site_url'] .$base_url . 'pages/village_menu/my', 'name'=>$this->modules['HousevillageMy'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),


            //array('module' => 'HousevillageMyPaylists', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_my_paylists', array('village_id'=>$village_id), true, false, true)),'name'=>$this->modules['HousevillageMyPaylists'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'HousevillageMyBindFamilyAdd', 'linkcode' => $this->config['site_url'] .$base_url . 'pages/village/my/bindFamily','name'=>$this->modules['HousevillageMyBindFamilyAdd'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'HousevillageMyBindFamilyList', 'linkcode' => $this->config['site_url'] .$base_url . 'pages/village/my/myVillage','name'=>$this->modules['HousevillageMyBindFamilyList'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),

            array('module' => 'HouseserviceserviceCategory', 'linkcode' => $this->config['site_url'] . $base_url . 'pages/village_menu/convenient?village_id='.$village_id, 'name'=>$this->modules['HouseserviceserviceCategory'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),

            array('module' => 'HousevillageActivity', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_activitylist',array('village_id'=>$village_id) , true, false, true)),'name'=>$this->modules['HousevillageActivity'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            //小区缴费 start 2020/9/27 (注释) 移动到收费管理
            //array('module' => 'HousePay', 'linkcode' => str_replace('shequ.php', 'wap.php', $this->config['site_url'] . $base_url.'pages/village/my/payMentlist?village_id=' . $village_id),'name'=>$this->modules['HousePay'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            //小区缴费 end 2020/9/27 (注释)
//            array('module' => 'HousevillageManager', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_manager_list',array('village_id'=>$village_id) , true, false, true)),'name'=>$this->modules['HousevillageManager'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),



            array('module' => 'MyVillage', 'linkcode' => $this->config['site_url'] . $base_url.'pages/village/my/myVillage','name'=>$this->modules['MyVillage'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            // 2020/9/27 (注释) start 移动到收费管理
            //预存物业费
            //array('module' => 'pre_storage_property', 'linkcode' => str_replace('shequ.php', 'wap.php', $this->config['site_url'].$base_url . 'pages/village/my/lifepayment?village_id='.$village_id),'name'=>$this->modules['pre_storage_property'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            //自主缴费
            //array('module' => 'custom', 'linkcode' => str_replace('shequ.php', 'wap.php', $this->config['site_url'].$base_url . 'pages/village/my/independentPayement?village_id='.$village_id),'name'=>$this->modules['custom'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            // 2020/9/27 (注释) end
        );

        $application_arr = D('Application_list')->get_application_all_auth($village_id);

        //收费管理 start 2020/9/27
        if(in_array(33,$application_arr)){
            //小区缴费
            $t[] = array('module' => 'HousePay', 'linkcode' => str_replace('shequ.php', 'wap.php', $this->config['site_url'] . $base_url.'pages/village/my/payMentlist?village_id=' . $village_id),'name'=>$this->modules['HousePay'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            //自主缴费
            $t[] = array('module' => 'custom', 'linkcode' => str_replace('shequ.php', 'wap.php', $this->config['site_url'].$base_url . 'pages/village/my/independentPayement?village_id='.$village_id),'name'=>$this->modules['custom'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            //预存物业费
            $t[] = array('module' => 'pre_storage_property', 'linkcode' => str_replace('shequ.php', 'wap.php', $this->config['site_url'].$base_url . 'pages/village/my/lifepayment?village_id='.$village_id),'name'=>$this->modules['pre_storage_property'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);

            $is_new_charge=D('House_village')->checkNewCharge($this->house_session['property_id']);
            if($is_new_charge){
                //缴费
                /**
                 $hzhou_grapefruit_order=D('Config_customization')->getHzhouGrapefruitOrderJudge();
                **/
                $hzhou_grapefruit_order=1;
                $tmpLinkUrl=$this->config['site_url'].$base_url . 'pages/houseMeter/NewCollectMoney/liveExpenses?village_id='.$village_id;
                if($hzhou_grapefruit_order){
                    $tmpLinkUrl=$this->config['site_url'].$base_url . 'pages/houseMeter/NewCollectMoney/newLiveExpenses?village_id='.$village_id;
                }
                $tmpLinkUrl=str_replace('shequ.php', 'wap.php',$tmpLinkUrl);
                $t[] = array('module' => 'HousevillageProperty', 'linkcode' =>$tmpLinkUrl ,'name'=>$this->modules['HousevillageProperty'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            }
        }
        //收费管理 end 2020/9/27

        if (in_array(16,$application_arr)) {
            $t[] = array('module' => 'HousevillageMyRepairlists', 'linkcode' => str_replace('shequ.php', 'wap.php', $this->config['site_url'].$base_url . 'pages/village/my/waterReportlist?type=1&title='.urlencode('物业报修').'&village_id='.$village_id),'name'=>$this->modules['HousevillageMyRepairlists'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if (in_array(18,$application_arr)) {
            $t[] = array('module' => 'HousevillageMyUtilitieslists', 'linkcode' => str_replace('shequ.php', 'wap.php', $this->config['site_url'].$base_url . 'pages/village/my/waterReportlist?type=2&title='.urlencode('水电煤上报').'&village_id='.$village_id),'name'=>$this->modules['HousevillageMyUtilitieslists'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if (in_array(19,$application_arr)) {
            $t[] = array('module' => 'HousevillageMySuggestist', 'linkcode' => str_replace('shequ.php', 'wap.php', $this->config['site_url'].$base_url . 'pages/village/my/waterReportlist?type=3&title='.urlencode('投诉建议').'&village_id='.$village_id),'name'=>$this->modules['HousevillageMySuggestist'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        if (in_array(24,$application_arr)) {
            $t[] = array('module' => 'ActiveGroupList', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_grouplist', array('village_id'=>$village_id), true, false, true)),'name'=>$this->modules['ActiveGroupList'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if (in_array(14,$application_arr)) {
            $t[] = array('module' => 'HousevillageExpress', 'linkcode' => $this->config['site_url'] . $base_url . 'pages/village/my/expressManagement?village_id='.$village_id, 'name'=>$this->modules['HousevillageExpress'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] =  array('module' => 'HousevillageExpressSend', 'linkcode' => $this->config['site_url'] . $base_url . 'pages/village/my/expressManagement?current=1&village_id='.$village_id, 'name'=>$this->modules['HousevillageExpressSend'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        if (in_array(25,$application_arr)) {
            $t[] = array('module' => 'ActiveMealList', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_meallist', array('village_id'=>$village_id), true, false, true)),'name'=>$this->modules['ActiveMealList'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if (in_array(26,$application_arr)) {
            $t[] = array('module' => 'ActiveAppointList', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_appointlist', array('village_id'=>$village_id), true, false, true)),'name'=>$this->modules['ActiveAppointList'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if (in_array(27,$application_arr)) {
            $t[] = array('module' => 'ActiveStoreList', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/shop', array('village_id'=>$village_id.'#cat-all'), true, false, true)),'name'=>$this->modules['ActiveStoreList'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if (in_array(28,$application_arr)) {
            $t[] = array('module' => 'VisitorRegistration', 'linkcode' => $this->config['site_url'] . $base_url.'pages/village/my/visitorRegistration?village_id='.$village_id,'name'=>$this->modules['VisitorRegistration'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

// wap.php?g=Wap&c=House&a=shop&village_id=1
        //$systemLiveServiceTypeArr = explode(',',$this->config['live_service_type']);
        //if(in_array('water',$systemLiveServiceTypeArr)){
        //	$t[] = array('module' => 'PlatformPayWater', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Lifeservice/query',array('type'=>'water'), true, false, true)),'name'=>$this->modules['PlatformPayWater'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        //}
        //if(in_array('electric',$systemLiveServiceTypeArr)){
        //	$t[] = array('module' => 'PlatformPayElectric', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Lifeservice/query',array('type'=>'electric'), true, false, true)),'name'=>$this->modules['PlatformPayElectric'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        //}
        //if(in_array('gas',$systemLiveServiceTypeArr)){
        //	$t[] = array('module' => 'PlatformPayGas', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Lifeservice/query',array('type'=>'gas'), true, false, true)),'name'=>$this->modules['PlatformPayGas'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        //}

        if($this->config['crowdsourcing_is']){
            $t[] = array('module' => 'Crowdsourcing', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Crowdsourcing/index', array('village_id'=>$_SESSION['house']['village_id']), true, false, true)),'name'=>$this->modules['Crowdsourcing'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        // 智能访客开门
        if ($this->config['house_village_temporary_visitor'] == 1) {
            $now_village = D('House_village')->get_one($village_id);
            if ($now_village['temporary_visitor_switch'] == 1) {
                $t[] = array('module' => 'TemporaryVisitorOpen', 'linkcode' => $this->config['site_url'] . $base_url.'pages/village/index/visitorPass','name'=>$this->modules['TemporaryVisitorOpen'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            }
        }

//        $url = $this->config['site_url'] . '/packapp/vote_app/index.html';
        if (in_array(2,$application_arr)){
            $url = $this->config['site_url'] . $base_url . 'pages/vote/index';
            $t[] = array('module' => 'Vote', 'linkcode' => $url,'name'=>$this->modules['Vote'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        $touch_alarm_phone = M('House_village')->where(array('village_id' => $village_id))->getField('touch_alarm_phone');
        if ($touch_alarm_phone) {
            $url = $this->config['site_url'] . $base_url . 'pages/village/index/oneClickAlarm';
            $t[] = array('module' => 'AlarmPhone', 'linkcode' => $url,'name'=>'一键报警','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }


//        $url = $this->config['site_url'] . $base_url . 'pages/village/street/streetNews?village_id='.$village_id;
//        $t[] = array('module' => 'StreetNews', 'linkcode' => $url,'name'=>'街道新闻','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
//
//        $url = $this->config['site_url'] . $base_url . 'pages/village/communityNews/communityNews?village_id='.$village_id;
//        $t[] = array('module' => 'CommunityNews', 'linkcode' => $url,'name'=>'社区新闻','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);


        $url = $this->config['site_url'] . $base_url . 'pages/village_menu/resident';
        $t[] = array('module' => 'AlarmPhone', 'linkcode' => $url,'name'=>'邻里','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        $url = $this->config['site_url'] . $base_url . 'pages/village/my/faceAccessControl';
        $t[] = array('module' => 'AlarmPhone', 'linkcode' => $url,'name'=>'人脸识别门禁','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);

        if (empty($now_village)) {
            $now_village = D('House_village')->get_one($village_id,'street_id, community_id');
        }
        if ($now_village && $now_village['street_id']) {
            // 街道首页
            $url = $this->config['site_url'] . $base_url . "pages/street/street_index?area_street_id={$now_village['street_id']}";
            $t[] = array('module' => 'Street', 'linkcode' => $url,'name'=>'街道首页','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if ($now_village && $now_village['community_id']) {
            // 社区首页
            $url = $this->config['site_url'] . $base_url. "pages/street/street_index?area_street_id={$now_village['community_id']}";
            $t[] = array('module' => 'Community', 'linkcode' => $url,'name'=>'社区首页','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);


        }

        if ($this->config['discuzx_url']) {
            //todo 论坛
            $t[] = array('module' => 'forum', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Discuz/main', array('village_id'=>$_SESSION['house']['village_id'],'type'=>1), true, false, true)),'name'=>$this->modules['forum'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);

            //todo 论坛版块
            $t[] = array('module' => 'forum_list', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Discuz/main', array('village_id'=>$_SESSION['house']['village_id'],'type'=>2), true, false, true)),'name'=>$this->modules['forum_list'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);

            //todo 单篇贴子
            $t[] = array('module' => 'single_forum', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Discuz/main', array('village_id'=>$_SESSION['house']['village_id'],'type'=>3), true, false, true)),'name'=>$this->modules['single_forum'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        //print_r($t);
        return $t;
    }


    public function modules_merchant()
    {
        $merchant_session = session('merchant');
        $mer_id = $merchant_session ? $merchant_session['mer_id']:0;
        $list = D('House_link')->merchant_library($mer_id,$this->config,$this->modules_merchant_content,$this->modules['Home'],$this->token);
        return $list;


        $t = array(
            array('module' => 'Home',		'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Index/index', array('token' => $this->token), true, false, true)), 'name'=>L_('微站首页'),'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>$this->modules['Home'],'askeyword'=>1),
            array('module' => 'Classify',	'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Index/lists', array('token' => $this->token), true, false, true)),'name'=>$this->modules_merchant_content['Classify'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>0),
            array('module' => 'Group',		'linkcode'=>'','name'=>$this->modules_merchant_content['Group'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Meal',		'linkcode'=>'','name'=>$this->modules_merchant_content['Meal'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Lottery',	'linkcode'=>str_replace('shequ.php', 'wap.php', U('Wap/Lottery/Lottery', array('token' => $this->token), true, false, true)),'name'=>$this->modules_merchant_content['Lottery'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Guajiang',	'linkcode'=>'','name'=>$this->modules_merchant_content['Guajiang'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'GoldenEgg',	'linkcode'=>'','name'=>$this->modules_merchant_content['GoldenEgg'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'LuckyFruit',	'linkcode'=>'','name'=>$this->modules_merchant_content['LuckyFruit'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Article',	'linkcode'=>'','name'=>$this->modules_merchant_content['Article'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Shop',	'linkcode'=>'','name'=>$this->modules_merchant_content['Shop'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'ShopLink',	'linkcode'=>'','name'=>$this->modules_merchant_content['ShopLink'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Wxapp',	'linkcode'=>'','name'=>$this->modules_merchant_content['Wxapp'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
// 			array('module' => 'PadMeal',		'linkcode'=>'','name'=>$this->modules_merchant_content['PadMeal'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Mall', 'linkcode' => '', 'name' => $this->modules_merchant_content['Mall'], 'sub' => 1, 'canselected' => 0, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1),
            array('module' => 'Workstaff',		'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Workerstaff/login', array(), true, false, true)), 'name'=>($this->config['appoint_worker_name'] ? $this->config['appoint_worker_name'] : L_('技师')).L_('中心'),'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>$this->modules_merchant_content['Workerstaff'],'askeyword'=>1),
            array('module' => 'Card_new_coupon',		'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/My_card/merchant_coupon_list', array('mer_id'=>$this->merchant_session['mer_id']), true, false, true)), 'name'=>L_('商家会员卡优惠券'),'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>$this->modules_merchant_content['Card_new'],'askeyword'=>1),
            array('module' => 'Card_new',		'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/My_card/merchant_card', array('mer_id'=>$this->merchant_session['mer_id']), true, false, true)), 'name'=>L_('商家会员卡'),'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>$this->modules_merchant_content['Card_new'],'askeyword'=>1),
            array('module' => 'Store',		'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/My/pay', array('mer_id'=>$this->merchant_session['mer_id']), true, false, true)), 'name'=>$this->modules_merchant_content['Store'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>$this->modules_merchant_content['Card_new'],'askeyword'=>1),
            array('module' => 'Appoint',		'linkcode' => '', 'name'=>$this->modules_merchant_content['Appoint'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>$this->modules_merchant_content['Card_new'],'askeyword'=>1),
            array('module' => 'Storestaff',		'linkcode' => $this->config['site_url'].'/packapp/storestaff/index.html', 'name'=>L_('手机版店员中心'),'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>$this->modules_merchant_content['Card_new'],'askeyword'=>1),
            array('module' => 'Deliver',		'linkcode' => $this->config['site_url'].'/packapp/deliver/index.html', 'name'=>L_('手机版配送员中心'),'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>$this->modules_merchant_content['Card_new'],'askeyword'=>1),
            array('module' => 'Im',		'linkcode' => $this->config['site_url'].'/wap.php?c=Home&a=jump_im&mer_id='.$this->merchant_session['mer_id'], 'name'=>$this->modules_merchant_content['Im'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>$this->modules_merchant_content['Im'],'askeyword'=>1),
            array('module' => 'MarkList',
                'linkcode' => $this->config['site_url'].'/packapp/merchant/market_list.html',
                'name'=>$this->modules_merchant_content['MarkList'],
                'sub'=>1,
                'canselected'=>1,
                'linkurl'=>'',
                'keyword'=>$this->modules_merchant_content['MarkList'],
                'askeyword'=>1),
            array('module' => 'Integral_mall_index',
                'linkcode' => $this->config['site_url'].'/wap.php?g=Wap&c=Integral_mall&a=index&mer_id='.$this->merchant_session['mer_id'],
                'name'=>$this->modules_merchant_content['Integral_mall_index'],
                'sub'=>0,
                'canselected'=>1,
                'linkurl'=>'',
                'keyword'=>$this->modules_merchant_content['Integral_mall_index'],
                'askeyword'=>1),
        );

        if ($this->config['merchant_integral_mall_control'] == 1) {
            array_push($t, array('module' => 'IntegralMall',
                'linkcode' => $this->config['site_url'].'/wap.php?g=Wap&c=Integral_mall&a=storelist&mer_id='.$this->merchant_session['mer_id'],
                'name'=>$this->modules_merchant_content['IntegralMall'],
                'sub'=>0,
                'canselected'=>1,
                'linkurl'=>'',
                'keyword'=>$this->modules_merchant_content['IntegralMall'],
                'askeyword'=>1));
        }

        if($this->config['is_open_weidian']){
            array_push($t,array('module' => 'Weidian',	'linkcode'=>'','name'=>$this->modules_merchant_content['Weidian'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1));
        }

        // 添加用户平台个人链接-即wap端个人中心
        array_push($t,array('module' => 'User_Platform_Personal_Center',		'linkcode' => get_base_url('pages/plat_menu/my'), 'name'=>$this->modules_merchant_content['User_Platform_Personal_Center'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>$this->modules_merchant_content['User_Platform_Personal_Center'],'askeyword'=>1));


        foreach($t as $k=>$v){
            if(!$this->config['group_alias_name'] && in_array($v['module'],array('Group'))){
                unset($t[$k]);
            }
            if(!$this->config['shop_alias_name'] && in_array($v['module'],array('Shop','ShopLink'))){
                unset($t[$k]);
            }
            if(!$this->config['meal_alias_name'] && in_array($v['module'],array('Meal',))){
                unset($t[$k]);
            }
            if(!$this->config['mall_alias_name'] && in_array($v['module'],array('Mall',))){
                unset($t[$k]);
            }
            if(!$this->config['appoint_alias_name'] && in_array($v['module'],array('Appoint','Workstaff'))){
                unset($t[$k]);
            }
            if(!$this->config['pay_in_store'] && in_array($v['module'],array('Store'))){
                unset($t[$k]);
            }
            if(!empty($this->config['single_system']) && in_array($v['module'],array('Activity','City_car','Wxapp','Wapactivity'))){
                unset($t[$k]);
            }
        }

        return $t;
    }


    public function HouseserviceserviceCategory(){
        $id = $_GET['id'] + 0;
        if($id){
            $this->assign('moduleName', '便民服务子分类');
            $where['parent_id'] = $id;
        }else{
            $this->assign('moduleName', '便民服务分类');
            $where['parent_id'] = 0;
        }

        $village_id = $this->house_session['village_id'];
        $where['village_id'] = $village_id;
        $where['status']  = 1;
        $db = D('House_service_category');
        $count      = $db->where($where)->count();
        $Page       = new Page($count, 5);
        $show       = $Page->show();
        $list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();

        $items = array();
        if($list){
            foreach ($list as $item){
                if (!$item['parent_id']) {
                    array_push($items, array('id' => $item['id'], 'sub' => 1, 'name' => $item['cat_name'],'sublink' => U('Link/HouseserviceserviceCategory', array('id' => $item['id']), true, false, true),'keyword' => $item['cat_name']));
                }else{
                    if(!$item['cat_url']){
                        array_push($items, array('id' => $item['id'], 'sub' => 1, 'name' => $item['cat_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Houseservice/cat_list', array('village_id' => $village_id ,'id'=>$item['id']), true, false, true)),'sublink' => U('Link/HouseserviceserviceCategory', array('id' => $item['id']), true, false, true),'keyword' => $item['cat_name']));
                    }else{
                        array_push($items, array('id' => $item['id'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Houseservice/cat_list', array('village_id' => $village_id ,'id'=>$item['id']), true, false, true)),'sublink' => U('Link/HouseserviceserviceCategory', array('id' => $item['id']), true, false, true),'keyword' => $item['cat_name']));
                    }
                }

            }
        }else{
            $this->assign('moduleName', '便民服务便民详情');

            $db = D('House_service_info');
            $Map['status'] = 1;
            $Map['village_id'] = $village_id;
            $Map['cat_id'] = $id;
            $list = $db->where($Map)->limit($Page->firstRow.','.$Page->listRows)->select();
            foreach ($list as $item){
                $tmpLbs = wapLbsTranform($item['url'],array('title'=>$item['title'],'pic'=>$item['img_path'],'phone'=>$item['phone']),true);

                if($tmpLbs){
                    array_push($items, array('id' => $item['id'], 'sub' => 0, 'name' => $item['title'], 'linkcode'=> $tmpLbs['url'],'sublink' => $tmpLbs['url'],'keyword' => $item['title']));
                }

            }
        }

        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }

    //社区缴费
    public function HousePay(){
        $this->assign('moduleName', $this->modules['HousePay']);
        $village_id = $this->house_session['village_id'];
        $items = array();
        array_push($items, array('id' => 1, 'sub' => 0,'linkcode' =>str_replace('shequ.php', 'wap.php', U('Wap/House/village_pay', array('type'=>'property','village_id'=>$village_id), true, false, true)),'name'=>$this->modules['HousevillagePayProperty'], 'keyword' => $this->modules['HousevillagePayProperty']));
        array_push($items, array('id' =>2, 'sub' => 0, 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_pay', array('type'=>'water','village_id'=>$village_id), true, false, true)),'name'=>$this->modules['HousevillagePayWater'], 'keyword' => $this->modules['HousevillagePayWater']));
        array_push($items, array('id' => 3, 'sub' => 0,'linkcode' =>str_replace('shequ.php', 'wap.php', U('Wap/House/village_pay', array('type'=>'electric','village_id'=>$village_id), true, false, true)),'name'=>$this->modules['HousevillagePayElectric'], 'keyword' => $this->modules['HousevillagePayElectric']));
        array_push($items, array('id' => 4, 'sub' => 0,'linkcode' =>str_replace('shequ.php', 'wap.php', U('Wap/House/village_pay', array('type'=>'gas','village_id'=>$village_id), true, false, true)),'name'=>$this->modules['HousevillagePayGas'], 'keyword' => $this->modules['HousevillagePayGas']));
        array_push($items, array('id' =>5, 'sub' => 0, 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_pay', array('type'=>'park','village_id'=>$village_id), true, false, true)),'name'=>$this->modules['HousevillagePayPark'], 'keyword' => $this->modules['HousevillagePayPark']));
        array_push($items, array('id' => 6, 'sub' => 0,'linkcode' =>str_replace('shequ.php', 'wap.php', U('Wap/House/village_pay', array('type'=>'custom','village_id'=>$village_id), true, false, true)),'name'=>$this->modules['HousevillagePayCustom'], 'keyword' => $this->modules['HousevillagePayCustom']));

        $systemLiveServiceTypeArr = explode(',',$this->config['live_service_type']);
        if(in_array('water',$systemLiveServiceTypeArr)){
            //$t[] = array('module' => 'PlatformPayWater', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Lifeservice/query',array('type'=>'water'), true, false, true)),'name'=>$this->modules['PlatformPayWater'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            array_push($items, array('id' => 7, 'sub' => 0,'linkcode' =>str_replace('shequ.php', 'wap.php',  U('Lifeservice/query',array('type'=>'water'), true, false, true)),'name'=>$this->modules['PlatformPayWater'], 'keyword' => $this->modules['PlatformPayWater']));

        }
        if(in_array('electric',$systemLiveServiceTypeArr)){
            //$t[] = array('module' => 'PlatformPayElectric', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Lifeservice/query',array('type'=>'electric'), true, false, true)),'name'=>$this->modules['PlatformPayElectric'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            array_push($items, array('id' => 7, 'sub' => 0,'linkcode' =>str_replace('shequ.php', 'wap.php',  U('Lifeservice/query',array('type'=>'electric'), true, false, true)),'name'=>$this->modules['PlatformPayElectric'], 'keyword' => $this->modules['PlatformPayElectric']));
        }
        if(in_array('gas',$systemLiveServiceTypeArr)){
            //$t[] = array('module' => 'PlatformPayGas', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Lifeservice/query',array('type'=>'gas'), true, false, true)),'name'=>$this->modules['PlatformPayGas'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            array_push($items, array('id' => 7, 'sub' => 0,'linkcode' =>str_replace('shequ.php', 'wap.php',  U('Lifeservice/query',array('type'=>'gas'), true, false, true)),'name'=>$this->modules['PlatformPayGas'], 'keyword' => $this->modules['PlatformPayGas']));
        }
        $this->assign('list', $items);
        $this->display('detail');
    }


    public function ActiveStoreList(){
        $this->assign('moduleName', $this->modules['ActiveStoreList']);
        $village_id = $this->house_session['village_id'];
        $database_house_village_appoint = D('House_village_store');

        $now_time = $_SERVER['REQUEST_TIME'];
        $condition_table = array(C('DB_PREFIX').'merchant_store'=>'s',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'house_village_store'=>'hvs');
        $condition_field = "`s`.`name` AS `store_name`,`m`.`name` AS `merchant_name`,`s`.*,`m`.*,`hvs`.*";
        $condition_where = "`s`.`mer_id`=`m`.`mer_id` AND `m`.`status`='1' AND `hvs`.`store_id`=`s`.`store_id` AND `hvs`.`village_id`='$village_id'";

        // $store_list = D('')->field('`s`.`name` AS `store_name`,`m`.`name` AS `merchant_name`,`s`.*,`m`.*,`hvs`.*')->table(array(C('DB_PREFIX').'merchant_store'=>'s',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'house_village_store'=>'hvs'))->where("`s`.`mer_id`=`m`.`mer_id` AND `hvs`.`store_id`=`s`.`store_id` AND `hvs`.`village_id`='$village_id'")->order('`hvs`.`sort` DESC,`hvs`.`pigcms_id` DESC')->limit($limit)->select();

        $count = D('')->table($condition_table)->where($condition_where)->count();

        $Page       = new Page($count, 5);
        $show       = $Page->show();

        $list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->limit($Page->firstRow.','.$Page->listRows)->select();
        $items = array();
        foreach ($list as $item){
            if($item!=''){
                array_push($items, array('id' => $item['store_id'], 'sub' => 0, 'name' => $item['store_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php',$item['url']),'sublink' => $item['url'],'keyword' => $item['name']));
            }else {
                array_push($items, array('id' => $item['appoint_id'], 'sub' => 0, 'name' => $item['appoint_name'], 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Appoint/detail', array('appoint_id' => $item['appoint_id']), true, false, true)), 'sublink' => U('Wap/Appoint/detail', array('appoint_id' => $item['appoint_id']), true, false, true), 'keyword' => $item['appoint_name']));
            }
        }

        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }


    public function ActiveAppointList(){
        $this->assign('moduleName', $this->modules['ActiveAppointList']);
        $village_id = $this->house_session['village_id'];
        $database_house_village_appoint = D('House_village_appoint');

        $now_time = $_SERVER['REQUEST_TIME'];
        $condition_table = array(C('DB_PREFIX').'appoint'=>'a',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'house_village_appoint'=>'hva');
        $condition_field = "`m`.`name` AS `merchant_name`,`a`.*,`m`.*,`hva`.*";
        $condition_where = "`a`.`mer_id`=`m`.`mer_id` AND `a`.`check_status`='1' AND `a`.`appoint_status`='0' AND `m`.`status`='1' AND `a`.`start_time`<'$now_time' AND `a`.`end_time`>'$now_time' AND `hva`.`appoint_id`=`a`.`appoint_id` AND `hva`.`village_id`='$village_id'";
        $count = D('')->table($condition_table)->where($condition_where)->count();

        $Page       = new Page($count, 5);
        $show       = $Page->show();

        $list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->limit($Page->firstRow.','.$Page->listRows)->select();

        $items = array();
        foreach ($list as $item){
            if($item!=''){
                array_push($items, array('id' => $item['store_id'], 'sub' => 0, 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php',$item['url']),'sublink' => $item['url'],'keyword' => $item['name']));
            }else {
                array_push($items, array('id' => $item['appoint_id'], 'sub' => 0, 'name' => $item['appoint_name'], 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Appoint/detail', array('appoint_id' => $item['appoint_id']), true, false, true)), 'sublink' => U('Wap/Appoint/detail', array('appoint_id' => $item['appoint_id']), true, false, true), 'keyword' => $item['appoint_name']));
            }
        }

        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }


    public function ActiveGroupList(){
        $this->assign('moduleName', $this->modules['ActiveGroupList']);
        $village_id = $this->house_session['village_id'];

        $now_time = $_SERVER['REQUEST_TIME'];
        $condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'house_village_group'=>'hvg');
        $condition_field = "`g`.`name` AS `group_name`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*,`hvg`.`sort`,`hvm`.`url`";
        $condition_where = "`g`.`mer_id`=`m`.`mer_id` AND `g`.`status`='1' AND `m`.`status`='1' AND `g`.`type`='1' AND `g`.`begin_time`<'$now_time' AND `g`.`end_time`>'$now_time' AND `hvg`.`group_id`=`g`.`group_id` AND `hvg`.`village_id`='$village_id'";
        $count = D('')->field($condition_field)->table($condition_table)->where($condition_where)->count();
        $Page       = new Page($count, 5);
        $show       = $Page->show();

        $list = D('')->table($condition_table)->where($condition_where)->limit($Page->firstRow.','.$Page->listRows)->select();
        $items = array();
        foreach ($list as $item){
            if($item!=''){
                array_push($items, array('id' => $item['store_id'], 'sub' => 0, 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php',$item['url']),'sublink' => $item['url'],'keyword' => $item['name']));
            }else {
                array_push($items, array('id' => $item['group_id'], 'sub' => 0, 'name' => $item['name'], 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Group/detail', array('group_id' => $item['group_id']), true, false, true)), 'sublink' => U('Wap/Group/detail', array('group_id' => $item['group_id']), true, false, true), 'keyword' => $item['name']));
            }
        }

        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }


    public function ActiveMealList(){
        $this->assign('moduleName', $this->modules['ActiveMealList']);
        $village_id = $this->house_session['village_id'];

        $condition_table = array(C('DB_PREFIX').'merchant_store'=>'ms',C('DB_PREFIX').'merchant_store_foodshop'=>'msm',C('DB_PREFIX').'house_village_meal'=>'hvm',C('DB_PREFIX').'merchant'=>'m');
        $condition_field = "`ms`.*,`msm`.*,`m`.`name` AS `merchant_name`,`ms`.`name` AS `store_name`,`hvm`.`url` ";
        $condition_where = "`ms`.`have_meal`='1' AND `ms`.`status`='1' AND `ms`.`store_id`=`msm`.`store_id` AND `ms`.`store_id`=`hvm`.`store_id` AND `hvm`.`village_id`='$village_id' AND `m`.`mer_id`=`ms`.`mer_id` ";

        $count = D('')->table($condition_table)->where($condition_where)->count();
        $Page       = new Page($count, 5);
        $show       = $Page->show();

        $list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->limit($Page->firstRow.','.$Page->listRows)->select();
        $items = array();
        foreach ($list as $item){
            if($item!=''){
                array_push($items, array('id' => $item['store_id'], 'sub' => 0, 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php',$item['url']),'sublink' => $item['url'],'keyword' => $item['name']));
            }else{
                array_push($items, array('id' => $item['store_id'], 'sub' => 0, 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Food/shop', array('store_id' => $item['store_id'],'mer_id'=>$item['mer_id']), true, false, true)),'sublink' => U('Wap/Food/shop', array('store_id' => $item['store_id'],'mer_id'=>$item['mer_id']), true, false, true),'keyword' => $item['name']));
            }
        }

        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }


    public function HousevillageNewsCatelist(){
        $base_url = D('House_village')->base_url;
        $this->assign('moduleName', $this->modules['HousevillageNewsCatelist']);
        $village_id = $this->house_session['village_id'];
        $condition_table  = array(C('DB_PREFIX').'house_village_news_category'=>'c',C('DB_PREFIX').'house_village'=>'v');
        $condition_where = "v.village_id = c.village_id AND  c.cat_status=1  AND c.village_id=".$village_id;

        $condition_field = 'c.*';

        $count = D('')->table($condition_table)->where($condition_where)->count();
        $Page       = new Page($count, 5);
        $show       = $Page->show();

        $list = D('')->table($condition_table)->where($condition_where)->limit($Page->firstRow.','.$Page->listRows)->select();
        $items = array();
        foreach ($list as $item){
            $items[] = array('module' => 'HousevillageNewslist', 'linkcode' => $this->config['site_url'] .$base_url . 'pages/village/index/notice?village_id=' . $village_id.'&cat_id='.$item['cat_id'], 'name'=>$item['cat_name'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);

            // array_push($items, array('id' => $item['store_id'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/House/village_newslist', array('village_id' => $item['village_id'],'cat_id'=>$item['cat_id']), true, false, true)),'sublink' => U('Wap/House/village_newslist', array('village_id' => $item['village_id'],'cat_id'=>$item['cat_id']), true, false, true),'keyword' => $item['cat_name']));
        }
        $this->assign('page', $show);
        $this->assign('list', $items);
        $this->display('detail');
    }


    //todo 工单分类列表
    public function WorkOrder(){
        $base_url = D('House_village')->base_url;
        $this->assign('moduleName', $this->modules['HousevillageNewsCatelist']);
        $village_id = $this->house_session['village_id'];
        $where=[
            'village_id'=>$village_id,
            'parent_id'=>0,
            'status'=>1
        ];
        $count = D('House_new_repair_cate')->where($where)->count();
        $Page       = new Page($count, 5);
        $show       = $Page->show();
        $list = D('House_new_repair_cate')->field('id,cate_name')->where($where)->order('sort desc,id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach ($list as $item){
            $items[] = array(
                'module' => 'WorkOrder',
                'linkcode' => $this->config['site_url'] .$base_url . 'pages/houseMeter/workOrder/eventList?village_id=' . $village_id.'&category_id='.$item['id'].'&subject_name='.$item['cate_name'],
                'name'=>$item['cate_name'],
                'sub'=>0,
                'canselected'=>1,
                'linkurl'=>'',
                'keyword'=>'',
                'askeyword'=>1
            );
        }
        $this->assign('page', $show);
        $this->assign('list', $items);
        $this->display('detail');
    }

    //装修申请单
    public function RenovationApplyList(){
        $base_url = D('House_village')->base_url;
        $this->assign('moduleName', $this->modules['RenovationApplyTitle']);
        $village_id = $this->house_session['village_id'];
        $condition_table  = array(C('DB_PREFIX').'plugin_material_diy_template'=>'c',C('DB_PREFIX').'house_village'=>'v');
        $condition_where = "v.village_id = c.from_id AND  c.from='house' AND c.template_status = 0 AND c.from_id=".$village_id;

        $condition_field = 'c.*';

        $count = D('')->table($condition_table)->where($condition_where)->count();
        $Page       = new Page($count, 5);
        $show       = $Page->show();

        $list = D('')->table($condition_table)->where($condition_where)->limit($Page->firstRow.','.$Page->listRows)->order('c.sort DESC, c.add_time DESC')->select();
        $items = array();
        foreach ($list as $item){
            $items[] = array(
                'module' => 'RenovationApplyTitle',
                'linkcode'=>$this->config['site_url'] .str_replace('shequ.php', 'wap.php', U('Wap/Renovation/user_know', ['template_id'=>$item['template_id']])),
//                'linkcode' => $this->config['site_url'] .$base_url . 'pages/village/materialDiy/materialList?template_id=' . $item['template_id'].'&from='.$item['from'].'&from_id='.$item['from_id'],
                'name'=>$item['template_title'],
                'sub'=>0,
                'canselected'=>1,
                'linkurl'=>'',
                'keyword'=>'',
                'askeyword'=>1

            );
        }
        $this->assign('page', $show);
        $this->assign('list', $items);
        $this->display('detail');
    }


    public function HousevillageNewslist(){
        $this->assign('moduleName', $this->modules['HousevillageNewslist']);
        $village_id = $this->house_session['village_id'];
        $condition_table  = array(C('DB_PREFIX').'house_village_news'=>'n',C('DB_PREFIX').'house_village_news_category'=>'c',C('DB_PREFIX').'house_village'=>'v');
        $condition_where = " n.village_id = v.village_id  AND n.village_id = c.village_id AND  n.cat_id = c.cat_id AND c.cat_status=1  AND n.village_id=".$village_id;
        /*
        if($column['status']){
            $condition_where .= " AND n.status = ".intval($column['status']);
        }
        */
        $condition_field = 'n.*,c.cat_name';

        $count = D('')->table($condition_table)->where($condition_where)->count();
        $Page       = new Page($count, 5);
        $show       = $Page->show();

        $list = D('')->table($condition_table)->where($condition_where)->limit($Page->firstRow.','.$Page->listRows)->select();
        $items = array();
        $base_url = D('House_village')->base_url;
        foreach ($list as $item){
            array_push($items, array('id' => $item['store_id'], 'sub' => 0, 'name' => $item['title'], 'linkcode'=> $this->config['site_url'] .$base_url . 'pages/village/index/villagecontent?village_id=' . $item['village_id'] . '&news_id=' . $item['news_id'], 'keyword' => $item['title']));
        }

        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }

    public function HousevillageMy(){
        $this->assign('HousevillageMy', $this->modules['HousevillageMy']);
        $this->display('detail');
    }


    public function HousevillageActivity(){
        $this->assign('moduleName', $this->modules['HousevillageActivity']);
        $village_id = $this->house_session['village_id'];
        $where['village_id'] = $village_id;
        $where['status'] = 1;
        $database_house_village_activity = D('House_village_activity');

        $count = $database_house_village_activity->where($where)->count();
        $Page       = new Page($count, 5);
        $show       = $Page->show();

        $list = $database_house_village_activity->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
        $items = array();
        $base_url = D('House_village')->base_url;
        foreach ($list as $item){
            array_push($items, array('id' => $item['id'], 'sub' => 0, 'name' => $item['title'], 'linkcode'=> $this->config['site_url'] .$base_url . 'pages/village/index/activitySignup?village_id='.$item['village_id'].'&id='.$item['id'],'sublink' => U('Wap/House/village_activity', array('village_id' => $item['id'],'news_id'=>$item['id']), true, false, true),'keyword' => $item['title']));
        }

        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }

    public function modules_admin($property=[])
    {
        $base_url = D('House_village')->base_url;
        $t = array(
            array('module' => 'Home', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Home/index', array('no_house'=>'1'), true, false, true)), 'name'=>'网站首页','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>$this->modules_admin['Home'],'askeyword'=>1),
            array('module' => 'News', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Systemnews/index', '', true, false, true)),'name'=>$this->modules_admin['News'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
// 		array('module' => 'Meal', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Foodshop/index', '', true, false, true)),'name'=>$this->modules_admin['Meal'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
// 		array('module' => 'AroundMeal', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Meal_list/around', '', true, false, true)),'name'=>$this->modules_admin['AroundMeal'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Meal_order', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/My/foodshop_order_list', '', true, false, true)),'name'=>$this->modules_admin['Meal_order'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Shop_order', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/My/shop_order_list', '', true, false, true)),'name'=>$this->modules_admin['Shop_order'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Card_list', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/My/card_list', array('coupon_type'=>'system'), true, false, true)),'name'=>$this->modules_admin['Card_list'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Member', 'linkcode' => $this->config['site_url'] . $base_url . 'pages/plat_menu/my','name'=>$this->modules_admin['Member'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Activity', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Activity/index', '', true, false, true)),'name'=>$this->modules_admin['Activity'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Systemcoupon', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Systemcoupon/index', '', true, false, true)),'name'=>$this->modules_admin['Coupon'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Shop', 'linkcode' => $this->config['site_url'] . $base_url . 'pages/shop_index/index','name'=>$this->modules_admin['Shop'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'ShopLink', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Shop/classic_index', '', true, false, true)),'name'=>$this->modules_admin['ShopLink'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),

            array('module' => 'Special_shop', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Special/index', '', true, false, true)),'name'=>$this->modules_admin['Special_shop'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Special_mall', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Special/index', '', true, false, true)),'name'=>$this->modules_admin['Special_mall'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1),

            array('module' => 'Mall', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Mall/index', '', true, false, true)),'name'=>$this->modules_admin['Mall'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            //新版商城
            array('module' => 'newMallVersion', 'linkcode' => $this->config['site_url'].'/packapp/plat/pages/shopmall_third/shopmall_index','name'=>'新版商城','sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            
            array('module' => 'Mall', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Mall/cut_goods_list', '', true, false, true)),'name'=>'砍价','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Mall', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Mall/group_goods_list', '', true, false, true)),'name'=>'拼团','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Mall', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Mall/seckill_goods_list', '', true, false, true)),'name'=>$this->modules_admin['seckill_alias_name'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),



            array('module' => 'Wapactivity', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Wapactivity/index', '', true, false, true)),'name'=>$this->modules_admin['Wapactivity'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'City_car', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/City_car/index', '', true, false, true)),'name'=>$this->modules_admin['City_car'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Hotel', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Hotel/index', '', true, false, true)),'name'=>$this->modules_admin['Hotel'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Village_group', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Village_group/index','', true, false, true)),'name'=>$this->modules_admin['Village_group'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),
            array('module' => 'Village_group', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Village_group/apply','', true, false, true)),'name'=>'注册成为团长','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1),



//        array('module' => 'IntegralMall',
//                'linkcode' => $this->config['site_url'].'/wap.php?g=Wap&c=Integral_mall&a=storelist&mer_id='.$this->merchant_session['mer_id'],
//                'name'=>$this->modules_admin['IntegralMall'],
//                'sub'=>0,
//                'canselected'=>1,
//                'linkurl'=>'',
//                'keyword'=>$this->modules_admin['IntegralMall'],
//                'askeyword'=>1),
        );
        if (empty($property) || $property['merchant_id'] || $this->config['merchant_alias_name']) {
            // 商城方面 NewMerchantapp
            $t[] = array('module' => 'Store', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Merchant/store_list', '', true, false, true)),'name'=>$this->modules_admin['Store'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] = array('module' => 'Card_mer_list', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/My/card_list',array('coupon_type'=>'mer'), true, false, true)),'name'=>$this->modules_admin['Card_mer_list'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] =  array('module' => 'Storestaff_new', 'linkcode' => $this->config['site_url'].'/packapp/storestaff/index.html','name'=>$this->modules_admin['Storestaff_new'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] =  array('module' => 'NearMerchant', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Merchant/around', '', true, false, true)),'name'=>$this->modules_admin['NearMerchant'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] =  array('module' => 'All_Merchant_coupon', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/My_card/merchant_coupon_list',array('all_mer'=>1), true, false, true)),'name'=>$this->modules_admin['All_Merchant_coupon'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] =  array('module' => 'MerchantStore', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Merchant/store_list', '', true, false, true)),'name'=>$this->modules_admin['MerchantStore'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            //商城3.0拼团，砍价，秒杀
            $t[] =  array('module' => 'Mall', 'linkcode' =>$this->config['site_url'] . $base_url . 'pages/shopmall_third/mybargaining','name'=>$this->modules_admin['new_mall_bargain'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] =  array('module' => 'Mall', 'linkcode' =>$this->config['site_url'] . $base_url . 'pages/shopmall_third/makeGroup' ,'name'=>$this->modules_admin['new_mall_group'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] =  array('module' => 'Mall', 'linkcode' =>$this->config['site_url'] . $base_url . 'pages/shopmall_third/flashSale','name'=>$this->modules_admin['new_mall_limited'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] =  array('module' => 'NewMall', 'linkcode' =>$this->config['site_url'] . $base_url . 'pages/shopmall_third/shopmall_index','name'=>$this->modules_admin['new_mall_home'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] =  array('module' => 'MarkList',
                'linkcode' => $this->config['site_url'].'/packapp/merchant/market_list.html',
                'name'=>$this->modules_admin['MarkList'],
                'sub'=>1,
                'canselected'=>1,
                'linkurl'=>'',
                'keyword'=>$this->modules_admin['MarkList'],
                'askeyword'=>1);
            $t[] = array('module' => 'NewMerchantapp', 'linkcode' =>  $this->config['site_url'].'/packapp/merchant/index.html','name'=>$this->modules_admin['NewMerchantapp'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] = array('module' => 'QuickPay', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/QuickPay/index', '', true, false, true)), 'name'=>'快速买单店铺列表','sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        if (empty($property) || $property['merchant_id'] || $this->config['group_alias_name']) {
            // 团购方面
            $t[] = array('module' => 'Group', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Group/index', '', true, false, true)),'name'=>$this->modules_admin['Group'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] = array('module' => 'AroundGroup', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Group/around', '', true, false, true)),'name'=>$this->modules_admin['AroundGroup'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] = array('module' => 'Group_order', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/My/group_order_list', '', true, false, true)),'name'=>$this->modules_admin['Group_order'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] = array('module' => 'Group_collect', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/My/group_collect', '', true, false, true)),'name'=>$this->modules_admin['Group_collect'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] = array('module' => 'Navigation', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Group/navigation', '', true, false, true)),'name'=>$this->modules_admin['Navigation'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] = array('module' => 'GroupMainPage', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Group/main_page', '', true, false, true)),'name'=>$this->modules_admin['GroupMainPage'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            //新版团购
            $t[] = array('module' => 'newGroupVersion', 'linkcode' => $this->config['site_url'].'/packapp/plat/pages/group/index/home?currentPage=index','name'=>'新版团购','sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            
        }


        if (isset($this->config['no_foodshop'])) {
            $t[] = array('module' => 'Meal', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Meal_list/index', '', true, false, true)),'name'=>$this->modules_admin['Meal'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        } else {
            $t[] = array('module' => 'Meal', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Foodshop/index', '', true, false, true)),'name'=>$this->modules_admin['Meal'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if ($this->config['meal_alias_name']) {
            // 新版餐饮2.0
            $t[] = array('module' => 'new_foodshop_store', 'linkcode' => C('config.site_url') . D('House_village')->base_url . 'pages/foodshop/index/index','name'=>$this->modules_admin['new_foodshop_store'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        if(!empty($this->config['wxapp_url'])){
            $t[] = array('module' => 'Wxapp', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Wxapp/index', '', true, false, true)),'name'=>$this->modules_admin['Wxapp'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        // 功能-发现模块控制
        if ($this->config['find_msg']) {
            $t[] = array('module' => 'Discover', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Discover/discover_index', '', true, false, true)),'name'=>$this->modules_admin['Discover'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        // 我的推广
        if (!isset($this->config['close_system_spread']) || $this->config['close_system_spread'] != 1) {
            $t[] = array('module' => 'Distributor_agent', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Distributor_agent/index', '', true, false, true)),'name'=>$this->modules_admin['Distributor_agent'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        // 功能-平台活动模块控制
        if ($this->config['is_open_activity']) {
            $t[] = array('module' => 'Platform_classify_manager', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/My/activity_apply', '', true, false, true)),'name'=>$this->modules_admin['Platform_classify_manager'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] = array('module' => 'Platform_activity_inspect', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Platform/activityInspect', '', true, false, true)),'name'=>$this->modules_admin['Platform_activity_inspect'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        // 本地化商城
        if ($this->config['localization_shopping_mall_store'] > 0) {
            $t[] = array('module' => 'LocalShopMall', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/LocalShopMall/index', '', true, false, true)),'name'=>$this->modules_admin['LocalShopMall'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        // 定制-从业者模块
        if ($this->config['employee_switch'] > 0) {
            //  从业者列表页 包含分类详情
            $t[] = array('module' => 'Employee_list_info', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Employee/employee_list', '', true, false, true)),'name'=>$this->modules_admin['Employee_list_info'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            // 从业者申请入驻 如果已经入驻过了会自动跳转个人入驻详情
            $t[] = array('module' => 'Employee_apply', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Employee/index', '', true, false, true)),'name'=>$this->modules_admin['Employee_apply'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            // 从业人员个人详情页面
            $t[] = array('module' => 'Employee_owner_detail', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Employee/my_detail', '', true, false, true)),'name'=>$this->modules_admin['Employee_owner_detail'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            // 从业人员个人详情编辑
            $t[] = array('module' => 'Employee_edit', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Employee/employee_info', '', true, false, true)),'name'=>$this->modules_admin['Employee_edit'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            // 从业人员用户添加店铺
            $t[] = array('module' => 'Employee_claim_store_add', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Employee/store_add', '', true, false, true)),'name'=>$this->modules_admin['Employee_claim_store_add'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            // 从业人员认领店铺列表
            $t[] = array('module' => 'Employee_claim_store', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Employee/employee_claim_store_list', '', true, false, true)),'name'=>$this->modules_admin['Employee_claim_store'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            // 从业人员收藏列表
            $t[] = array('module' => 'Employee_collect_list', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Employee/employee_collect_list', '', true, false, true)),'name'=>$this->modules_admin['Employee_collect_list'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        //商圈分类
        if($this->config['open_circle_category']){
            $t[] = array('module' => 'circle_category', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/LocalShopMall/index', '', true, false, true)),'name'=>$this->modules_admin['Circle_category'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }


        if($this->config['open_sub_card']){
            $t[] = array('module' => 'Sub_card', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Sub_card/index', '', true, false, true)),'name'=>$this->modules_admin['Sub_card'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if(isset($this->config['sign_get_score'])){
            $t[] = array('module' => 'Sign', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/My/sign', '', true, false, true)),'name'=>$this->modules_admin['Sign'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        if($this->config['portal_switch'] && !file_exists('conf/is_hake.no_delete')){
            $t[] = array('module' => 'Portal', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Portal/index', '', true, false, true)),'name'=>$this->modules_admin['Portal'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);

            $t[] = array('module' => 'Portalactivity', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Portal/activity', '', true, false, true)),'name'=>$this->modules_admin['Portalactivity'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] = array('module' => 'Portalarticle', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Portal/article', '', true, false, true)),'name'=>$this->modules_admin['Portalarticle'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] = array('module' => 'Portaltieba', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Portal/tieba', '', true, false, true)),'name'=>$this->modules_admin['Portaltieba'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] = array('module' => 'Portalyellow', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Portal/yellow', '', true, false, true)),'name'=>$this->modules_admin['Portalyellow'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        if(isset($this->config['appoint_site_name']) && !file_exists('conf/is_hake.no_delete')){
            $t[] = array('module' => 'Invitation', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Invitation/datelist', '', true, false, true)),'name'=>$this->modules_admin['Invitation'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if($this->config['appoint_page_row'] && !file_exists('conf/is_hake.no_delete')){
            $t[] = array('module' => 'AppointList', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Appoint/index', '', true, false, true)),'name'=>$this->modules_admin['AppointList'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] = array('module' => 'AppointCategory', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Appoint/category', '', true, false, true)),'name'=>$this->modules_admin['AppointCategory'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] = array('module' => 'AppointOrder', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/My/appoint_order_list', '', true, false, true)),'name'=>$this->modules_admin['AppointOrder'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] = array('module' => 'Home', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Workerstaff/index', array(), true, false, true)), 'name'=>($this->config['appoint_worker_name'] ? $this->config['appoint_worker_name'] : '技师').'中心','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>$this->modules_admin['Workerstaff'],'askeyword'=>1);
        }
        if(isset($this->config['wap_home_show_classify']) && !file_exists('conf/is_hake.no_delete')){
            $t[] = array('module' => 'Classify', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Classify/index', '', true, false, true)),'name'=>$this->modules_admin['Classify'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if ($this->config['house_open']) {
            $t[] = array('module' => 'HousevillageList', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/House/village_list', '', true, false, true)),'name'=>$this->modules_admin['HousevillageList'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if ($this->config['is_open_weidian']) {
            $t[] = array('module' => 'NearWeiDian', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Weidian/near_store_redirect', '', true, false, true)),'name'=>$this->modules_admin['NearWeiDian'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if($this->config['waimai_alias_name']){
            $t[] = array('module' => 'WaimaiIndex', 'linkcode' => str_replace('admin.php', 'index.php', U('WaimaiWap/Index/index', '', true, false, true)),'name'=>$this->modules_admin['WaimaiIndex'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] = array('module' => 'WaimaiUser', 'linkcode' => str_replace('admin.php', 'index.php', U('WaimaiWap/User/index', '', true, false, true)),'name'=>$this->modules_admin['WaimaiUser'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] = array('module' => 'WaimaiOrder', 'linkcode' => str_replace('admin.php', 'index.php', U('WaimaiWap/Order/index', '', true, false, true)),'name'=>$this->modules_admin['WaimaiOrder'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] = array('module' => 'WaimaiCoupon2', 'linkcode' => str_replace('admin.php', 'index.php', U('WaimaiWap/User/coupon', array('coupon_type'=>2), true, false, true)),'name'=>$this->modules_admin['WaimaiCoupon2'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] = array('module' => 'WaimaiCoupon1', 'linkcode' => str_replace('admin.php', 'index.php', U('WaimaiWap/User/coupon', array('coupon_type'=>1), true, false, true)),'name'=>$this->modules_admin['WaimaiCoupon1'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            $t[] = array('module' => 'WaimaiAddress', 'linkcode' => str_replace('admin.php', 'index.php', U('WaimaiWap/User/adresList', '', true, false, true)),'name'=>$this->modules_admin['WaimaiAddress'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

// 			$t[] = array('module' => 'Deliver', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Deliver/grab', '', true, false, true)),'name'=>$this->modules_admin['Deliver'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        $t[] = array('module' => 'Deliver_new', 'linkcode' => $this->config['site_url'].'/packapp/deliver/index.html','name'=>$this->modules_admin['Deliver_new'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);

        if($this->config['ride_is']){
            $t[] = array('module' => 'Ride', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Ride/ride_list', array('plat'=>1), true, false, true)),'name'=>$this->modules_admin['Ride'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if($this->config['crowdsourcing_is']){
            $t[] = array('module' => 'Crowdsourcing', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Crowdsourcing/index', '', true, false, true)),'name'=>$this->modules_admin['Crowdsourcing'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if($this->config['live_service_have']){
            $t[] = array('module' => 'Lifeservice', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Lifeservice/index', '', true, false, true)),'name'=>$this->modules_admin['Lifeservice'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        if(isset($this->config['gift_alias_name']) && !file_exists('conf/is_hake.no_delete')){
            $t[] = array('module' => 'Gift', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Gift/index', '', true, false, true)),'name'=>$this->modules_admin['Gift'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if($this->config['mobile_recharge_APIKey'] && $this->config['mobile_recharge_openid'] ){
            $t[] = array('module' => 'Mobile_recharge', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Third_recharge/mobile_recharge', '', true, false, true)),'name'=>$this->modules_admin['Mobile_recharge'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        if(isset($this->config['scenic_now_city'])){
            $t[] = array('module' => 'Scenic_index', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Scenic_index/index', '', true, false, true)),'name'=>$this->modules_admin['Scenic_index'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        if(isset($this->config['service_days_number']) && !file_exists('conf/is_hake.no_delete')){
            $t[] = array('module' => 'Service_index', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Service/index', '', true, false, true)),'name'=>$this->modules_admin['Service_index'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        if(isset($this->config['is_examine'])){
            $t[] = array('module' => 'Yuedan', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Yuedan/index', '', true, false, true)),'name'=>$this->modules_admin['Yuedan'],'sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if (empty($property) || $property['merchant_id'] || $this->config['group_alias_name']) {
            if (isset($this->config['paotui_service']) && !file_exists('conf/is_hake.no_delete')) {
                $t[] = array('module' => 'Paotui', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Service/paotui', array(), true, false, true)), 'name' => $this->modules_admin['Paotui'], 'sub' => 0, 'canselected' => 1, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1);
            }
        }
        if($this->config['is_open_lotteryturn']){
            $t[] = array('module' => 'Lotterynew', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Lotterynew/index',  array('token'=>-1), true, false, true)),'name'=>$this->modules_admin['Lotterynew'],'sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if($this->config['open_c2b_spread']){
            $t[] = array('module' => 'UserSpreadMer', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/UserSpreadMer/index',  '', true, false, true)),'name'=>$this->modules_admin['UserSpreadMer'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }
        if($this->config['open_kefu_url'] && $this->config['kefu_mer_id'] && (!$this->config['jg_im_appkey'] || !$this->config['jg_im_masterkey'])){
            $t[] = array('module' => 'platOnlineService', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/My/concact_kefu',  array('mer_id'=>$this->config['kefu_mer_id']), true, false, true)),'name'=>$this->modules_admin['platOnlineService'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        /*平台E卡*/
        if($this->config['ecard_alias_name']){
            $t[]=array('module' => 'Mall', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Mall/ecard_index', '', true, false, true)),'name'=>$this->modules_admin['ecard_alias_name'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        /*单个商品分销*/
        if($this->config['open_single_spread']){
            $t[]=array('module' => 'Distributor_apply', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/My/distributor_apply', '', true, false, true)),'name'=>'申请成为分销员','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        // 招聘定制
        if($this->config['open_recruit']){
            /*线下活动*/
            $t[]=array('module' => 'ActivityOffline', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/ActivityOffline/index', '', true, false, true)),'name'=>'线下活动首页','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);

            /*商学院*/
            $t[]=array('module' => 'Recruit', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Recruit/course_index', '', true, false, true)),'name'=>'商学院首页','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            /*招聘首页*/
            $t[]=array('module' => 'Recruit', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Recruit/index', '', true, false, true)),'name'=>'招聘首页','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            /*能量包首页*/
            $t[]=array('module' => 'Recruit', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Recruit/energy_package', '', true, false, true)),'name'=>'能量包首页','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            /*积分购买首页*/
            $t[]=array('module' => 'Recruit', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Recruit/score_package', '', true, false, true)),'name'=>'积分购买首页','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            /*简历首页*/
            $t[]=array('module' => 'Recruit', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Recruit/resume_list', '', true, false, true)),'name'=>'简历首页','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
            /*消息首页*/
            $t[]=array('module' => 'Recruit', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Recruit/message', '', true, false, true)),'name'=>'消息首页','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);

            /*商学院分类*/
            $t[]=array('module' => 'recruit_course_cagetory', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Recruit/course_category_list', '', true, false, true)),'name'=>'商学院分类','sub'=>1,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        // 瓜分券
        if($this->config['open_partition_coupon']){
            /*瓜分券*/
            $t[]=array('module' => 'partition_coupon_list', 'linkcode' => '','name'=>'瓜分券','sub'=>1,'canselected'=>0,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        // 我的推广二维码
        if($this->config['open_user_spread']){
            /*我的推广二维码*/
            $t[]=array('module' => 'my_spread_qrcode', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/My/my_spread_qrcode', '', true, false, true)),'name'=>'我的推广二维码','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        // 付款码
        if($this->config['open_company_advance_deposit']){
            /*付款码*/
            $t[]=array('module' => 'sweepCode', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Company_campus/sweepCode', '', true, false, true)),'name'=>'付款码','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        }

        //平台客服
        if($this->config['jg_im_appkey'] && $this->config['jg_im_masterkey']){
            $t[] = array('module' => 'plat_kf', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Kefu/plat', '', true, false, true)), 'name' => '客服联系用户入口--新版客服', 'sub' => 0, 'canselected' => 1, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1);
            $t[] = array('module' => 'plat_kf', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Kefu/user', '', true, false, true)), 'name' => '用户联系客服入口--新版客服', 'sub' => 0, 'canselected' => 1, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1);
        }


        // 隐私政策
        $t[] = array('module' => 'privacy_policy', 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Login/privacy_policy', '', true, false, true)), 'name' => '平台app隐私政策', 'sub' => 0, 'canselected' => 1, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1);

        if (empty($property) || $property['merchant_id'] || $this->config['group_alias_name']) {
            $t[] = array('module' => 'staff_agreement', 'linkcode' => C('config.site_url').'/packapp/storestaff/agreement.html', 'name' => '店员app隐私政策', 'sub' => 0, 'canselected' => 1, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1);
            $t[] = array('module' => 'mer_agreement', 'linkcode' => C('config.site_url').'/wap.php?c=Login&a=mer_privacy_policy', 'name' => '商家app隐私政策', 'sub' => 0, 'canselected' => 1, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1);
            $t[] = array('module' => 'deliver_agreement', 'linkcode' => C('config.site_url').'/packapp/deliver/agreement.html', 'name' => '配送app隐私政策', 'sub' => 0, 'canselected' => 1, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1);
            $t[] = array('module' => 'Live', 'linkcode' => $this->config['site_url'] . $base_url . 'pages/live_and_video/live/viewer/viewer_list', 'name' => $this->modules_admin['Live'], 'sub' => 0, 'canselected' => 1, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1);
        }
        //新版优惠券中心
        $t[] = array('module' => 'system_coupon_center', 'linkcode' => C('config.site_url') . D('House_village')->base_url . 'pages/coupon/index', 'name' => '新版平台领券中心','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);
        //电表管理
         if($this->config['house_meter']) {
            $t[] = array('module' => 'house_meter', 'linkcode' => C('config.site_url') . D('House_village')->base_url . 'pages/houseMeter/index/meterManagement', 'name' => '电表管理', 'sub' => 0, 'canselected' => 1, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1);
         }

        //充电桩管理
        if($this->config['pile']) {
            $t[] = array('module' => 'pile', 'linkcode' => C('config.site_url') . D('House_village')->base_url . 'pages/village/smartCharge/chargeList', 'name' => '充电桩管理', 'sub' => 0, 'canselected' => 1, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1);
        }
        $t[] = array('module' => 'park', 'linkcode' => C('config.site_url') . D('House_village')->base_url . 'pages/parkingLot/index', 'name' => '智能停车', 'sub' => 0, 'canselected' => 1, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1);

        foreach($t as $k=>$v){
            if(!$this->config['group_alias_name'] && in_array($v['module'],array('Group','AroundGroup','Group_order','Group_collect','Navigation','Hotel','MerchantStore','GroupMainPage','Village_group','Villagegroup'))){
                unset($t[$k]);
            }
            if($this->config['group_alias_name'] && $this->config['single_system_type'] == 'village_group' && in_array($v['module'],array('Group','AroundGroup','Store','News','Group_order','Hotel','Card_list','Group_collect','Navigation','MerchantStore','GroupMainPage','NewMerchantapp','Storestaff_new','Storestaff','Coupon','All_Merchant_coupon','Systemcoupon','Deliver_new','Member','Sign'))){
                unset($t[$k]);
            }
            if(!$this->config['meal_alias_name'] && in_array($v['module'],array('Meal_order','Meal'))){
                unset($t[$k]);
            }
            if(!$this->config['mall_alias_name'] && in_array($v['module'],array('Mall','Special_mall',))){
                unset($t[$k]);
            }
            if(!$this->config['shop_alias_name'] && in_array($v['module'],array('Special_shop','ShopLink','Shop','Shop_order'))){
                unset($t[$k]);
            }
            if(!empty($this->config['single_system']) && in_array($v['module'],array('Activity','City_car','Wxapp','Wapactivity','NearMerchant'))){
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
        if (empty($property) || $property['merchant_id'] || $this->config['group_alias_name']) {
            //$t[] = array('module' => 'project_employment', 'linkcode' => C('config.site_url') . '/packapp/project_employment/pages/index/index', 'name' => '求职首页', 'sub' => 0, 'canselected' => 1, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1);

            $t[] = array('module' => 'wantToBuyIndex', 'linkcode' => get_base_url('pages/wantToBuy/v1/home/index', true), 'name' => '种草首页', 'sub' => 0, 'canselected' => 1, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1);
            $t[] = array('module' => 'wantToBuySeach', 'linkcode' => get_base_url('pages/wantToBuy/v1/search/index', true), 'name' => '种草搜索页', 'sub' => 0, 'canselected' => 1, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1);
            $t[] = array('module' => 'wantToBuySquare', 'linkcode' => get_base_url('pages/wantToBuy/v1/topicSquare/index', true), 'name' => '种草话题广场', 'sub' => 0, 'canselected' => 1, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1);
            $t[] = array('module' => 'wantToBuyTopic', 'linkcode' => '', 'name' => '种草话题', 'sub' => 1, 'canselected' => 0, 'linkurl' => '', 'keyword' => '', 'askeyword' => 1);
        }
        return $t;
    }

    //种草话题
    public function wantToBuyTopic(){
        $where['is_del'] = 0;
        $count = M('Grow_grass_category')->where($where)->count(1);
        $Page = new Page($count, 5);
        $show = $Page->show();
        $list = M('Grow_grass_category')->where($where)->limit($Page->firstRow,$Page->listRows)->select();
        $data = array();
        foreach ($list as $k => $v) {
            $arr=array('id'=>$v['category_id'],'sub'=>0,'name'=>$v['name']);
            $arr['linkcode']=get_base_url('pages/wantToBuy/v1/topicDetail/index?category_id='.$v['category_id'],true);
            $arr['sublink']='';
            $arr['keyword']=$v['name'];
            $data[]=$arr;
        }
        $this->assign('list',$data);
        $this->assign('page',$show);
        $this->assign('moduleName', '种草话题');
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
            $items[] = array('id' => 1, 'sub' => 0, 'name' => '平台首页', 'linkcode'=> D('House_village')->home_url,'sublink' => '','keyword' => '平台首页');
            $items[] = array('id' => 2, 'sub' => 0, 'name' => $this->config['shop_alias_name'].'首页', 'linkcode'=> '/pages/shop_index/index','sublink' => '','keyword' => $this->config['shop_alias_name'].'首页');
            $items[] = array('id' => 3, 'sub' => 0, 'name' => '店铺列表', 'linkcode'=> '/pages/store/index','sublink' => '','keyword' => '店铺列表');
            $items[] = array('id' => 4, 'sub' => 0, 'name' => '个人中心', 'linkcode'=> '/pages/my/index','sublink' => '','keyword' => '个人中心');
        }
        if($this->config['single_system_type'] != 'shop'){
            $items[] = array('id' => 5, 'sub' => 0, 'name' => $this->config['village_group_alias'], 'linkcode'=> D('House_village')->home_url . '?redirect=webview&webview_url=%2Fwap.php%3Fc%3DVillage_group%26a%3Dindex&webview_title=','sublink' => '','keyword' => $this->config['village_group_alias']);
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
                array_push($items, array('id' => $item['cat_id'], 'sub' => 1, 'name' => $item['cat_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Shop/classic_cat', array('cat_url'=>$item['cat_url']), true, false, true)),'sublink' => U('ShopLink', array('cat_fid' => $item['cat_id']), true, false, true),'keyword' => $item['cat_name']));
            } else {
                array_push($items, array('id' => $item['cat_id'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Shop/classic_cat', array('cat_url'=>$item['cat_url']), true, false, true)),'sublink' => '','keyword' => $item['cat_name']));
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
        $db = D('Village_group_category');
        $count      = $db->where($where)->count();
        $Page       = new Page($count, 20);
        $show       = $Page->show();

        $list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
        $items = array();
        foreach ($list as $item){
            array_push($items, array('id' => $item['cat_id'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> $this->config['site_url'].'/packapp/community_group/index.html#/classify?cat_id='.$item['cat_id'],'sublink' => '','keyword' => $item['cat_name']));
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
        $Page       = new Page($count, 5);
        $show       = $Page->show();

        $list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();

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
// 				array_push($items, array('id' => $item['cat_id'], 'sub' => 1, 'name' => $item['cat_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Foodshop/index', array('cat_url'=>$item['cat_url']), true, false, true)),'sublink' => U('Link/Meal', array('cat_fid' => $item['cat_id']), true, false, true),'keyword' => $item['cat_name']));
// 			} else {
            array_push($items, array('id' => $item['cat_id'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Foodshop/index', array('cat_url'=>$item['cat_url']), true, false, true)),'sublink' => '','keyword' => $item['cat_name']));
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
                array_push($items, array('id' => $item['cat_id'], 'sub' => 1, 'name' => $item['cat_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Group/index', array('cat_url' => $item['cat_url']), true, false, true)),'sublink' => U('Link/group', array('cat_fid' => $item['cat_id']), true, false, true),'keyword' => $item['cat_name']));
            } else {
                array_push($items, array('id' => $item['cat_id'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Group/index', array('cat_url' => $item['cat_url']), true, false, true)),'sublink' => '','keyword' => $item['cat_name']));
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
            array_push($items, array('id' => $item['id'], 'sub' => 0, 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Sub_card/sub_card_detail', array('sub_card_id'=>$item['id']), true, false, true)),'sublink' => '','keyword' => $item['name']));
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

            array_push($items, array('id' => $item['rid'], 'sub' => 0, 'name' => $item['title'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Shop/service_detail', array('rid'=>$item['rid']), true, false, true)),'sublink' => '','keyword' => $item['cat_name']));

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
                array_push($items, array('id' => $item['id'], 'sub' => 1, 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php',  U('Wap/Systemnews/index', array('category_id' => $item['id']), true, false, true)),'sublink' => U('Link/news_content', array('category_id' => $item['id']), true, false, true),'keyword' => $item['name']));
            } else {
                array_push($items, array('id' => $item['id'], 'sub' => 0, 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Systemnews/index', array('category_id' => $item['id']), true, false, true)),'sublink' => '','keyword' => $item['cat_name']));
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
            array_push($items, array('id' => $item['id'], 'sub' => 0, 'name' => $item['title'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Systemnews/news', array('id' => $item['id']), true, false, true)),'sublink' => '','keyword' => $item['title']));
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
                array_push($items, array('id' => $item['id'], 'sub' => 1, 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Mall/goods_list', array('cat_id' => 0, 'cat_fid' => $item['id']), true, false, true)),'sublink' => U('Link/Mall',array('fid'=>$item['id']),true,false,true),'keyword' => $item['name']));
            }else{
                array_push($items, array('id' => $item['id'], 'sub' => 0, 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Mall/goods_list', array('cat_id' => $item['id'], 'cat_fid' => $item['fid']), true, false, true)),'sublink' => '','keyword' => $item['name']));
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
                array_push($items, array('id' => $item['id'], 'sub' => 1, 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/LocalShopMall/category', array('cat_id' => $item['id']), true, false, true)),'sublink' => U('Link/LocalShopMall', array('fid' => $item['id']), true, false, true),'keyword' => $item['name']));
            } else {
                array_push($items, array('id' => $item['id'], 'sub' => 0, 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/LocalShopMall/goods_list', array('cat_id' => $item['id'], 'cat_fid' => $item['fid']), true, false, true)),'sublink' => '','keyword' => $item['name']));
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
                array_push($items, array('id' => $item['cat_id'], 'sub' => 2, 'name' => $item['cat_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Employee/employee_list', array('cat_url' => $item['cat_url']), true, false, true)),'sublink' => U('Link/Employee_list_info', array('cat_fid' => $item['cat_id']), true, false, true),'keyword' => $item['cat_name']));
            } elseif ($item['cat_fid'] != 0 && $item['cat_pfcid'] == 0) {
                array_push($items, array('id' => $item['cat_id'], 'sub' => 1, 'name' => $item['cat_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Employee/employee_list', array('cat_url' => $item['cat_url']), true, false, true)),'sublink' => U('Link/Employee_list_info', array('cat_fid' => $item['cat_id'], 'cat_pfcid' => $item['cat_fid']), true, false, true),'keyword' => $item['cat_name']));
            } else {
                array_push($items, array('id' => $item['cat_id'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Employee/employee_list', array('cat_url' => $item['cat_url']), true, false, true)),'sublink' => '','keyword' => $item['cat_name']));
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
            $arr['linkcode']=str_replace('shequ.php', 'wap.php', U('Wap/Merchant/circle_list',array('circle_category_id'=>$v['id']),true,false,true));
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
            $arr['linkcode']=str_replace('shequ.php', 'wap.php', U('Wap/Lotterynew/index',array('token'=>-1,'id'=>$v['id']),true,false,true));
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
            'unitary' => '一元夺宝',
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
            'forum' => '讨论小区',
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
            array_push($items, array('id' => 0, 'sub' => 0, 'name' => $item, 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Wxapp/index', array('cat_url' => $index), true, false, true)),'sublink' => '','keyword' => $item));
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
            array_push($items, array('id' => $item['id'], 'sub' => 0, 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Special/index', array('id' => $item['pigcms_id']), true, false, true)),'sublink' => '','keyword' => $item['name']));
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
            array_push($items, array('id' => $item['id'], 'sub' => 0, 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Special/index', array('id' => $item['pigcms_id']), true, false, true)),'sublink' => '','keyword' => $item['name']));
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
                array_push($items, array('id' => $item['cid'],'fcid'=>$item['cid'], 'sub' => 1, 'name' => $item['cat_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Classify/index', array('cid' => $item['cid']), true, false, true)),'sublink' => U('Link/Classify', array('cid' => $item['cid']), true, false, true),'keyword' => ''));
            } else {
                array_push($items, array('id' => $item['cid'], 'fcid'=>$item['fcid'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Classify/Lists', array('cid' => $item['cid']), true, false, true)),'sublink' => '','keyword' => ''));
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
                array_push($items, array('id' => $item['cat_id'], 'cat_fid'=>$item['cat_fid'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Appoint/two_category', array('cat_id' => $item['cat_id']), true, false, true)),'sublink' => '','keyword' => $item['cat_name']));
            }else{
                array_push($items, array('id' => $item['cat_id'], 'cat_fid'=>$item['cat_fid'], 'sub' => 1, 'name' => $item['cat_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Appoint/category', array('cat_id' => $item['cat_id']), true, false, true)),'sublink' => U('Link/AppointCategory', array('cat_id' => $item['cat_id']), true, false, true),'keyword' => ''));
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

            array_push($items, array('id' => $item['cid'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Portal/activity', array('cid' => $item['cid']), true, false, true)),'keyword' => ''));

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
            array_push($items, array('id' => $item['cid'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Portal/article', array('cid' => $item['cid']), true, false, true)),'keyword' => ''));
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
            array_push($items, array('id' => $item['cat_id'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Gift/gift_list', array('cat_id' => $item['cat_id']), true, false, true)),'keyword' => ''));
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
                array_push($items, array('id' => $item['cat_id'], 'sub' => 1, 'name' => $item['cat_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/QuickPay/index', array('cat_url' => $item['cat_url']), true, false, true)), 'sublink' => U('Link/QuickPay', array('cat_fid' => $item['cat_id']), true, false, true),'keyword' => $item['cat_name']));
            } else {
                array_push($items, array('id' => $item['cat_id'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/QuickPay/index', array('cat_url' => $item['cat_url']), true, false, true)), 'sublink' => '','keyword' => $item['cat_name']));
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
            array_push($items, array('id' => $item['id'], 'sub' => 1, 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Recruit/course_category_list', array('cat_id' => $item['id']), true, false, true)),'sublink' => U('Link/recruit_course_list', array('cat_id' => $item['id'])),'keyword' => $item['name']));

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
            array_push($items, array('id' => $item['id'], 'sub' => 0, 'name' => $item['title'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Recruit/course_detail', array('course_id' => $item['course_id']), true, false, true)),'sublink' => '','keyword' => $item['name']));

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
            array_push($items, array('id' => $item['id'], 'sub' => 0, 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Partition_coupon/partition_coupon_detail', array('id' => $item['pigcms_id']), true, false, true)),'sublink' => '','keyword' => $item['name']));

        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }


    public function Weidian()
    {
        $data = array(
            'token'      => $this->token,
            'type'       => 'product,wei_page',
            'site_url'   => $this->config['site_url']
        );
        $sort_data = $data;
        $sort_data['salt'] = C('config.weidian_sign');
        ksort($sort_data);
        $sign_key = sha1(http_build_query($sort_data));
        $data['sign_key'] = $sign_key;
        $data['request_time'] = time();
        $request_url = $this->config['weidian_url'].'/api/store.php';
        $resultArr = json_decode($this->curl_post($request_url,$data),true);
        if(!empty($resultArr['error_code'])){
            $this->error($resultArr['error_msg']);
        }
        if(empty($resultArr['stores'])){
            $this->error(L_('您的微店没有添加店铺'));
        }
        $items = array();
        foreach ($resultArr['stores'] as $item){
            array_push($items,array('id' => $item['store_id'], 'name' => $item['name'], 'linkcode'=> $item['url'],'linkurl'=>'','keyword' => $item['name'],'product_count'=>$item['product_count'],'wei_page_count'=>$item['wei_page_count']));
        }
        $this->assign('list', $items);
//        $this->assign('page', $show);
        $this->display('weidian_store_list');

    }
    public function Weidian_page()
    {
        $data = array(
            'token'      => $this->token,
            'type'       => 'wei_page',
            'p'       => $_GET['p'] > 0 ? $_GET['p'] : 1,
            'store_id'       => $_GET['id'],
            'page_size'       => 5,
            'site_url'   => $this->config['site_url']
        );
        $sort_data = $data;
        $sort_data['salt'] = C('config.weidian_sign');
        ksort($sort_data);
        $sign_key = sha1(http_build_query($sort_data));
        $data['sign_key'] = $sign_key;
        $data['request_time'] = time();
        $request_url = $this->config['weidian_url'].'/api/store.php';
        $resultArr = json_decode($this->curl_post($request_url,$data),true);
        if(!empty($resultArr['error_code'])){
            $this->error($resultArr['error_msg']);
        }
        if(empty($resultArr['stores'])){
            $this->error(L_('您的微店没有添加店铺'));
        }
        if(empty($resultArr['stores'][0]['wei_pages'])){
            $this->error(L_('您的微店没有添加微页面'));
        }
        $items = array();
        foreach ($resultArr['stores'][0]['wei_pages'] as $item){
            array_push($items,array('id' => $item['page_id'], 'name' => $item['name'], 'linkcode'=> $item['url'],'linkurl'=>'','keyword' => $item['name']));
        }
        $this->assign('list', $items);

        $Page       = new Page($resultArr['stores'][0]['wei_page_count'],5);
        $this->assign('page', $Page->show());
        $this->display('weidian_page_list');

    }
    public function Weidian_product()
    {
        $data = array(
            'token'      => $this->token,
            'type'       => 'product',
            'p'       => $_GET['p'] > 0 ? $_GET['p'] : 1,
            'store_id'       => $_GET['id'],
            'page_size'       => 5,
            'site_url'   => $this->config['site_url']
        );
        $sort_data = $data;
        $sort_data['salt'] = C('config.weidian_sign');
        ksort($sort_data);
        $sign_key = sha1(http_build_query($sort_data));
        $data['sign_key'] = $sign_key;
        $data['request_time'] = time();
        $request_url = $this->config['weidian_url'].'/api/store.php';
        $resultArr = json_decode($this->curl_post($request_url,$data),true);
        if(!empty($resultArr['error_code'])){
            $this->error($resultArr['error_msg']);
        }
        if(empty($resultArr['stores'])){
            $this->error(L_('您的微店没有添加店铺'));
        }
        if(empty($resultArr['stores'][0]['products'])){
            $this->error(L_('您的微店没有添加商品'));
        }
        $items = array();
        foreach ($resultArr['stores'][0]['products'] as $item){
            array_push($items,array('id' => $item['product_id'], 'name' => $item['name'], 'linkcode'=> $item['url'],'linkurl'=>'','keyword' => $item['name']));
        }
        $this->assign('list', $items);

        $Page       = new Page($resultArr['stores'][0]['product_count'],5);
        $this->assign('page', $Page->show());
        $this->display('weidian_product_list');

    }
    public function Meal_test()
    {
        $this->assign('moduleName', $this->modules['Meal']);
        $db = M('Merchant_store');
        $where = array();
        $merchant_session = session('merchant');
        $where['mer_id'] = $merchant_session ? $merchant_session['mer_id']:0;
        $where['status'] = 1;
        $where['have_meal'] = 1;
        $count      = $db->where($where)->count();
        $Page       = new Page($count,5);
        $show       = $Page->show();
        $list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('store_id DESC')->select();
        $items = array();
        foreach ($list as $item){
// 			array_push($items,array('id' => $item['store_id'], 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', '{siteUrl}'. U('Wap/Meal/index', array('token' => $this->token, 'wecha_id' => '{wechat_id}', 'mer_id' => $item['mer_id'], 'store_id' => $item['store_id']))),'linkurl'=>'','keyword' => $item['name']));
            array_push($items,array('id' => $item['store_id'], 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Food/shop', array('token' => $this->token, 'mer_id' => $item['mer_id'], 'store_id' => $item['store_id'], 'otherwc' => 1), true, false, true)),'linkurl'=>'','keyword' => $item['name']));
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');

    }

    //批发市场
    public function MarkList_test(){
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
                    'canselected'=>1,
                    'sub'=>1,
                    'sublink'=>U('MarkList',array('category_id'=>$item['id'])),
                    'keyword' => $item['name']
                );

            }else{//子类，需要跳详情页
                $items[]= array(
                    'id' => $item['store_id'],
                    'name' => $item['name'],
                    'linkcode'=> $this->config['site_url'].'/packapp/merchant/market_category_list.html?cat_fid='.$item['fid'].'&cat_id='.$item['id'],
                    'linkurl'=>'',
                    'sub'=>1,
                    'sublink'=>U('MarkInfo',array('cat_fid'=>$item['fid'],'cat_id'=>$item['id'])),
                    'keyword' => $item['name']
                );
            }

        }
        //dump($items);exit;
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }

    public function MarkInfo_test(){
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
                'linkcode'=> $this->config['site_url'].'/packapp/merchant/market_buy_detail.html?goods_id=7049'.$item['goods_id'],
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


    public function Appoint()
    {
        $this->assign('moduleName', $this->modules['Appoint']);
        $db = M('Appoint');
        $where = array();
        $merchant_session = session('merchant');
        $where['mer_id'] = $merchant_session ? $merchant_session['mer_id']:0;
        $where['check_status'] = 1;

        $count      = $db->where($where)->count();
        $Page       = new Page($count,5);
        $show       = $Page->show();
        $list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('appoint_id DESC')->select();
        $items = array();
        foreach ($list as $item){
// 			array_push($items,array('id' => $item['store_id'], 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', '{siteUrl}'. U('Wap/Meal/index', array('token' => $this->token, 'wecha_id' => '{wechat_id}', 'mer_id' => $item['mer_id'], 'store_id' => $item['store_id']))),'linkurl'=>'','keyword' => $item['name']));
            array_push($items,array('id' => $item['appoint_id'], 'name' => $item['appoint_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Appoint/detail', array('appoint_id' => $item['appoint_id']), true, false, true)),'linkurl'=>'','keyword' => $item['name']));
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');

    }


    public function Meal_content()
    {
        $this->assign('moduleName', $this->modules_content['Meal_content']);
        $db = M('Merchant_store');
        $where = array();
        $merchant_session = session('merchant');
        $where['mer_id'] = $merchant_session ? $merchant_session['mer_id']:0;
        $where['status'] = 1;
        $where['have_meal'] = 1;
        $count      = $db->where($where)->count();
        $Page       = new Page($count,5);
        $show       = $Page->show();
        $list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('store_id DESC')->select();
        $items = array();
        $store_image_class = new store_image();
        foreach ($list as $item){
            $images = $store_image_class->get_allImage_by_path($item['pic_info']);
            $img_url = $images[0];
            array_push($items,array('id' => $item['store_id'], 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Food/shop', array('token' => $this->token, 'mer_id' => $item['mer_id'], 'store_id' => $item['store_id'], 'otherwc' => 1), true, false, true)),'linkurl'=>'','keyword' => $item['name'],'img_url'=>$img_url));
        }

        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('content_detail');

    }

    public function Mer_lottery()
    {
        $this->assign('moduleName', $this->modules_content['Mer_lottery']);

        $where = array();
        $where['status'] = 1;
        $where['have_meal'] = 1;
        $count      = D("Lottery")->field(true)->where(array('statdate' => array('lt', time()), 'enddate' => array('gt', time())))->count();
        $Page       = new Page($count,5);
        $show       = $Page->show();
        //, 'statdate' => array('lt', time()), 'enddate' => array('gt', time()))
        $list = D("Lottery")->field(true)->where(array('statdate' => array('lt', time()), 'enddate' => array('gt', time())))->limit($Page->firstRow.','.$Page->listRows)->select();
        $items = array();
        foreach ($list as $lottery){
            $token = $lottery['token'];
            switch ($lottery['type']){
                case 1:
                    $url = $this->config['site_url'] . "/wap.php?c=Lottery&a=index&wxscan=1&token={$token}&id={$lottery['id']}";
                    break;
                case 2:
                    $url = $this->config['site_url'] . "/wap.php?c=Guajiang&a=index&wxscan=1&token={$token}&id={$lottery['id']}";
                    break;
                case 3:
                    $url =$this->config['site_url'] . "/wap.php?c=Coupon&a=index&wxscan=1&token={$token}&id={$lottery['id']}";
                    break;
                case 4:
                    $url = $this->config['site_url'] . "/wap.php?c=LuckyFruit&a=index&wxscan=1&token={$token}&id={$lottery['id']}";
                    break;
                case 5:
                    $url = $this->config['site_url'] . "/wap.php?c=GoldenEgg&a=index&wxscan=1&token={$token}&id={$lottery['id']}";
                    break;
            }
            array_push($items,array('id' => $lottery['id'], 'name' => $lottery['keyword'], 'linkcode'=> $url,'linkurl'=>'','keyword' => $lottery['title'],'img_url'=> $this->config['site_url'] . $lottery['starpicurl']));
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('content_detail');

    }

    public function PadMeal()
    {
        $this->assign('moduleName', $this->modules['Meal']);
        $db = M('Merchant_store');
        $where = array();
        $merchant_session = session('merchant');
        $where['mer_id'] = $merchant_session ? $merchant_session['mer_id']:0;
        $where['status'] = 1;
        $where['have_meal'] = 1;
        $where['store_type'] = array('neq',2);
        $count      = $db->where($where)->count();
        $Page       = new Page($count,5);
        $show       = $Page->show();
        $list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('store_id DESC')->select();
        $items = array();
        foreach ($list as $item){
// 			array_push($items,array('id' => $item['store_id'], 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', '{siteUrl}'. U('Wap/Meal/index', array('token' => $this->token, 'wecha_id' => '{wechat_id}', 'mer_id' => $item['mer_id'], 'store_id' => $item['store_id']))),'linkurl'=>'','keyword' => $item['name']));
            array_push($items,array('id' => $item['store_id'], 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Food/pad', array('mer_id' => $item['mer_id'], 'store_id' => $item['store_id'], 'otherwc' => 1), true, false, true)),'linkurl'=>'','keyword' => $item['name']));
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');

    }
    public function Takeout()
    {
        $this->assign('moduleName', $this->modules['Takeout']);
        $db = M('Merchant_store');
        $where = array();
        $merchant_session = session('merchant');
        $where['mer_id'] = $merchant_session ? $merchant_session['mer_id']:0;
        $where['status'] = 1;
        $where['have_shop'] = 1;
        $count      = $db->where($where)->count();
        $Page       = new Page($count,5);
        $show       = $Page->show();
        $list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('store_id DESC')->select();
        $items = array();
        foreach ($list as $item){
// 			array_push($items,array('id' => $item['store_id'], 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', '{siteUrl}'. U('Wap/Meal/index', array('token' => $this->token, 'wecha_id' => '{wechat_id}', 'mer_id' => $item['mer_id'], 'store_id' => $item['store_id']))),'linkurl'=>'','keyword' => $item['name']));
// 			array_push($items,array('id' => $item['store_id'], 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Takeout/menu', array('token' => $this->token, 'mer_id' => $item['mer_id'], 'store_id' => $item['store_id'], 'otherwc' => 1), true, false, true)),'linkurl'=>'','keyword' => $item['name']));
            array_push($items,array('id' => $item['store_id'], 'name' => $item['name'], 'linkcode'=> $this->config['site_url'] . "/wap.php?c=Shop&a=index&shop-id={$item['store_id']}",'linkurl'=>'','keyword' => $item['name']));
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');

    }

    public function Article()
    {
        $this->assign('moduleName', $this->modules['Article']);
        $db = M('Image_text');
        $where = array();
        $merchant_session = session('merchant');
        $where['mer_id'] = $merchant_session ? $merchant_session['mer_id']:0;
        $count      = $db->where($where)->count();
        $Page       = new Page($count,5);
        $show       = $Page->show();
        $list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('pigcms_id DESC')->select();
        $items = array();
        foreach ($list as $item){
// 			array_push($items,array('id' => $item['store_id'], 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', '{siteUrl}'. U('Wap/Meal/index', array('token' => $this->token, 'wecha_id' => '{wechat_id}', 'mer_id' => $item['mer_id'], 'store_id' => $item['store_id']))),'linkurl'=>'','keyword' => $item['name']));
            array_push($items,array('id' => $item['pigcms_id'], 'name' => $item['title'], 'linkcode'=> $this->config['site_url'] . '/wap.php?g=Wap&c=Article&a=index&imid=' . $item['pigcms_id'],'linkurl'=>'','keyword' => $item['title']));//str_replace('shequ.php', 'wap.php', U('Wap/Article/index', array('imid' => $item['pigcms_id']), true, false, true))
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');

    }

    public function Article_content()
    {
        $this->assign('moduleName', $this->modules_content['Article_content']);
        $db = M('Image_text');
        $where = array();
        $merchant_session = session('merchant');
        $where['mer_id'] = $merchant_session ? $merchant_session['mer_id']:0;
        $count      = $db->where($where)->count();
        $Page       = new Page($count,5);
        $show       = $Page->show();
        $list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('pigcms_id DESC')->select();
        $items = array();
        foreach ($list as $item){
            $img_url = replace_file_domain($item['cover_pic']);
            array_push($items,array('id' => $item['pigcms_id'], 'name' => $item['title'], 'linkcode'=> $this->config['site_url'] . '/wap.php?g=Wap&c=Article&a=index&imid=' . $item['pigcms_id'],'linkurl'=>'','keyword' => $item['title'],'img_url'=>$img_url));
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('content_detail');

    }

    public function Group_test()
    {
        $this->assign('moduleName', $this->modules['Group']);
        $db = M('Group');
        $where = array();
        $merchant_session = session('merchant');
        $where['mer_id'] = $merchant_session ? $merchant_session['mer_id']:0;
        $where['type'] = array('lt', 3);
        $where['end_time'] = array('gt', time());
        $where['begin_time'] = array('lt', time());
        $where['status'] = 1;
        $count      = $db->where($where)->count();
        $Page       = new Page($count,5);
        $show       = $Page->show();
        $list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('group_id DESC')->select();
        $items = array();
        foreach ($list as $item){
// 			array_push($items,array('id' => $item['group_id'], 'name' => $item['s_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', '{siteUrl}'. U('Wap/Group/detail', array('token' => $this->token, 'wecha_id' => '{wechat_id}', 'mer_id' => $item['mer_id'], 'group_id' => $item['group_id']))),'linkurl'=>'','keyword' => $item['s_name']));
// 			array_push($items,array('id' => $item['group_id'], 'name' => $item['s_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Group/detail', array('token' => $this->token, 'mer_id' => $item['mer_id'], 'group_id' => $item['group_id'], 'otherwc' => 1), true, false, true)),'linkurl'=>'','keyword' => $item['s_name']));
            array_push($items,array('id' => $item['group_id'], 'name' => $item['s_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Group/detail', array('pin_num' => 0, 'group_id' => $item['group_id']), true, false, true)),'linkurl'=>'','keyword' => $item['s_name']));
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');

    }

    public function Group_content()
    {
        $this->assign('moduleName', $this->modules_content['Group_content']);
        $db = M('Group');
        $where = array();
        $merchant_session = session('merchant');
        $where['mer_id'] = $merchant_session ? $merchant_session['mer_id']:0;
        $where['type'] = array('lt', 3);
        $where['end_time'] = array('gt', time());
        $where['begin_time'] = array('lt', time());
        $where['status'] = 1;
        $count      = $db->where($where)->count();
        $Page       = new Page($count,5);
        $show       = $Page->show();
        $list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('group_id DESC')->select();
        $items = array();
        $group_image_class = new group_image();
        foreach ($list as $item){
            $tmp_pic_arr = explode(';',$item['pic']);
            $img_url = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
            array_push($items,array('id' => $item['group_id'], 'name' => $item['s_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Group/detail', array('pin_num' => 0, 'group_id' => $item['group_id']), true, false, true)),'linkurl'=>'','keyword' => $item['name'],'img_url'=>$img_url));
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('content_detail');

    }

    public function Classify_test()
    {
        $pid = (int)$_GET['pid'];
        $this->assign('moduleName', $this->modules['Classify']);
        $db = M('Classify');
        $where = $this->where;
        if ($pid != NULL) {
            $where['fid'] = $pid;
            $count      = $db->where($where)->count();
            $Page       = new Page($count,10);
            $show       = $Page->show();
            $list=$db->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('id DESC')->select();
        } else {
            $where['fid'] = 0;
            $count      = $db->where($where)->count();
            $Page       = new Page($count,10);
            $show       = $Page->show();
            $list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('id DESC')->select();
        }

        $items = array();
        foreach ($list as $k=>$item){
            $fid = $item['id'];
// 			array_push($items,array('id'=>$item['id'],'name'=>$item['name'],'sublink'=> '{siteUrl}'. U('Merchant/Link/Classify', array('pid' => $item['id'], 'iskeyword' => 0)), 'linkcode'=> str_replace('shequ.php', 'wap.php', '{siteUrl}'. U('Wap/Index/lists', array('token' => $this->token, 'wecha_id' => '{wechat_id}', 'classid' => $item['id']))),'linkurl'=>'','keyword'=>$item['keyword'],'sub'=>$db->where(array('token'=>$this->token,'fid'=>$fid))->field('id,name')->select()));
            array_push($items,array('id'=>$item['id'],'name'=>$item['name'],'sublink'=> U('Merchant/Link/Classify', array('pid' => $item['id'], 'iskeyword' => 0), true, false, true), 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Index/lists', array('token' => $this->token, 'classid' => $item['id']), true, false, true)),'linkurl'=>'','keyword'=>$item['keyword'],'sub'=>$db->where(array('token'=>$this->token,'fid'=>$fid))->field('id,name')->select()));
        }
        $this->assign('list',$items);
        $this->assign('page',$show);
        $this->display('detail');
    }

    public function Lottery()
    {
        $moduleName = 'Lottery';
        $this->assign('moduleName', $this->modules[$moduleName]);
        $db = M($moduleName);
        $where = [];
        $where['type'] = 1;
        $count      = $db->where($where)->count();
        $Page       = new Page($count,5);
        $show       = $Page->show();
        $list=$db->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('id DESC')->select();

        $items = array();
        foreach ($list as $item){
// 			array_push($items,array('id'=>$item['id'],'name'=>$item['title'],'linkcode'=> str_replace('shequ.php', 'wap.php', '{siteUrl}'. U('Wap/Lottery/index', array('token' => $this->token, 'wecha_id' => '{wechat_id}', 'id' => $item['id']))),'linkurl'=>'','keyword'=>$item['keyword']));
            array_push($items,array('id'=>$item['id'],'name'=>$item['title'],'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Lottery/index', array('token' => $item['mer_id'], 'id' => $item['id']), true, false, true)),'linkurl'=>'','keyword'=>$item['keyword']));
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }

    public function Guajiang()
    {
        $moduleName = 'Guajiang';
        $this->assign('moduleName', $this->modules[$moduleName]);
        $db = M('Lottery');
        $where = [];
        $where['type'] = 2;
        $count      = $db->where($where)->count();
        $Page       = new Page($count,5);
        $show       = $Page->show();
        $list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('id DESC')->select();
        $items = array();
        foreach ($list as $item){
// 			array_push($items,array('id'=>$item['id'],'name'=>$item['title'],'linkcode'=> str_replace('shequ.php', 'wap.php', '{siteUrl}'. U('Wap/Guajiang/index', array('token' => $this->token, 'wecha_id' => '{wechat_id}', 'id' => $item['id']))),'linkurl'=>'','keyword'=>$item['keyword']));
            array_push($items,array('id'=>$item['id'],'name'=>$item['title'],'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Guajiang/index', array('token' => $item['token'], 'id' => $item['id']), true, false, true)),'linkurl'=>'','keyword'=>$item['keyword']));
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }
    public function Coupon_test()
    {
        $moduleName = 'Coupon';
        $this->assign('moduleName', $this->modules[$moduleName]);
        $db = M('Lottery');
        $where = $this->where;
        $where['type'] = 3;
        $count      = $db->where($where)->count();
        $Page       = new Page($count,5);
        $show       = $Page->show();
        $list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('id DESC')->select();
        $items = array();
        foreach ($list as $item){
// 			array_push($items,array('id'=>$item['id'],'name'=>$item['title'],'linkcode'=> str_replace('shequ.php', 'wap.php', '{siteUrl}'. U('Wap/Coupon/index', array('token' => $this->token, 'wecha_id' => '{wechat_id}', 'id' => $item['id']))),'linkurl'=>'','keyword'=>$item['keyword']));
            array_push($items,array('id'=>$item['id'],'name'=>$item['title'],'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Coupon/index', array('token' => $item['token'], 'id' => $item['id']), true, false, true)),'linkurl'=>'','keyword'=>$item['keyword']));
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }

    public function LuckyFruit()
    {
        $moduleName = 'LuckyFruit';
        $this->assign('moduleName', $this->modules[$moduleName]);
        $db = M('Lottery');
        $where = [];
        $where['type'] = 4;
        $count      = $db->where($where)->count();
        $Page       = new Page($count,5);
        $show       = $Page->show();
        $list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('id DESC')->select();
        $items = array();
        foreach ($list as $item){
// 			array_push($items,array('id'=>$item['id'],'name'=>$item['title'],'linkcode'=> str_replace('shequ.php', 'wap.php', '{siteUrl}'. U('Wap/LuckyFruit/index', array('token' => $this->token, 'wecha_id' => '{wechat_id}', 'id' => $item['id']))),'linkurl'=>'','keyword'=>$item['keyword']));
            array_push($items,array('id'=>$item['id'],'name'=>$item['title'],'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/LuckyFruit/index', array('token' => $item['token'], 'id' => $item['id']), true, false, true)),'linkurl'=>'','keyword'=>$item['keyword']));
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }

    public function GoldenEgg()
    {
        $moduleName = 'GoldenEgg';
        $this->assign('moduleName', $this->modules[$moduleName]);
        $db = M('Lottery');
        $where = $this->where;
        $where['type'] = 5;
        $count      = $db->where($where)->count();
        $Page       = new Page($count, 5);
        $show       = $Page->show();
        $list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('id DESC')->select();
        $items = array();
        foreach ($list as $item){
// 			array_push($items,array('id'=>$item['id'],'name'=>$item['title'],'linkcode'=> str_replace('shequ.php', 'wap.php', '{siteUrl}'. U('Wap/GoldenEgg/index', array('token' => $this->token, 'wecha_id' => '{wechat_id}', 'id' => $item['id']))),'linkurl'=>'','keyword'=>$item['keyword']));
            array_push($items,array('id'=>$item['id'],'name'=>$item['title'],'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/GoldenEgg/index', array('token' => $item['mer_id'], 'id' => $item['id']), true, false, true)),'linkurl'=>'','keyword'=>$item['keyword']));
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }

    public function Wxapp_test()
    {
        $this->assign('moduleName', $this->modules['Wxapp']);
        // 得到营销活动列表
        $listType = array_keys($this->_accessListAction);
        $this->assign('listType',$listType);

        $listType = join(',', $listType);

        $db = M('Wxapp_list');
        $where = $this->where;
        $merchant_session = session('merchant');
        $where['mer_id'] = $merchant_session ? $merchant_session['mer_id']:0;
        $where['type'] = array('in',$listType);
        $count      = $db->where($where)->count();
        $Page       = new Page($count, 5);
        $show       = $Page->show();
        $list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('id DESC')->select();

        $items = array();
        foreach ($list as $i=>$item){
            $item['type'] = $this->_accessListAction[$item['type']];
            array_push($items,array('id'=>$item['id'],'name'=>$item['title'],'type'=>$item['type'],'linkcode'=>$this->config['site_url'] . "/wap.php?c=Wxapp&a=location_href&id=".$item['pigcms_id'],'linkurl'=>'','keyword'=>$item['keyword']));
        }
        $this->assign('list', $items);
        $this->assign('page', $show);

        $this->display('detail');

    }
    public function ShopLink_test(){
        $this->assign('moduleName', $this->modules['ShopLink']);
        $db = M('Merchant_store');
        $where = array();
        $merchant_session = session('merchant');
        $where['mer_id'] = $merchant_session ? $merchant_session['mer_id']:0;
        $where['have_shop'] = 1;
        $where['status'] = 1;
        $count      = $db->where($where)->count();
        $Page       = new Page($count,5);
        $show       = $Page->show();
        $list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('store_id DESC')->select();
        $items = array();
        foreach ($list as $item){
            array_push($items,array('id' => $item['store_id'], 'name' => $item['name'], 'linkcode'=> $this->config['site_url'] . '/wap.php?g=Wap&c=Shop&a=classic_shop&shop_id=' . $item['store_id'],'linkurl'=>'','keyword' => $item['name']));
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }
    public function Shop_test(){
        $this->assign('moduleName', $this->modules['Shop']);
        $db = M('Merchant_store');
        $where = array();
        $merchant_session = session('merchant');
        $where['mer_id'] = $merchant_session ? $merchant_session['mer_id']:0;
        $where['have_shop'] = 1;
        $where['status'] = 1;
        $count      = $db->where($where)->count();
        $Page       = new Page($count,5);
        $show       = $Page->show();
        $list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('store_id DESC')->select();
        $items = array();
        foreach ($list as $item){
// 			array_push($items,array('id' => $item['store_id'], 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', '{siteUrl}'. U('Wap/Meal/index', array('token' => $this->token, 'wecha_id' => '{wechat_id}', 'mer_id' => $item['mer_id'], 'store_id' => $item['store_id']))),'linkurl'=>'','keyword' => $item['name']));
            array_push($items,array('id' => $item['store_id'], 'name' => $item['name'], 'linkcode'=> $this->config['site_url'] . '/wap.php?g=Wap&c=Shop&a=index&shop-id=' . $item['store_id'],'linkurl'=>'','keyword' => $item['name']));//str_replace('shequ.php', 'wap.php', U('Wap/Article/index', array('imid' => $item['pigcms_id']), true, false, true))
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }

    public function Shop_content(){
        $this->assign('moduleName', $this->modules_content['Shop_content']);
        $db = M('Merchant_store');
        $where = array();
        $merchant_session = session('merchant');
        $where['mer_id'] = $merchant_session ? $merchant_session['mer_id']:0;
        $where['have_shop'] = 1;
        $where['status'] = array('neq',4);
        $count      = $db->where($where)->count();
        $Page       = new Page($count,5);
        $show       = $Page->show();
        $list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('store_id DESC')->select();
        $items = array();
        $store_image_class = new store_image();
        foreach ($list as $item){
            $images = $store_image_class->get_allImage_by_path($item['pic_info']);
            $img_url = $images[0];
            array_push($items,array('id' => $item['store_id'], 'name' => $item['name'], 'linkcode'=> $this->config['site_url'] . '/wap.php?g=Wap&c=Shop&a=index&shop-id=' . $item['store_id'],'linkurl'=>'','keyword' => $item['name'],'img_url'=>$img_url));
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('content_detail');
    }

    public function Appoint_content(){
        $this->assign('moduleName', $this->modules_content['Appoint_content']);
        $db = M('Appoint');
        $where = array();
        $merchant_session = session('merchant');
        $where['mer_id'] = $merchant_session ? $merchant_session['mer_id']:0;

        $count      = $db->where($where)->count();
        $Page       = new Page($count,5);
        $show       = $Page->show();
        $list = $db->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('appoint_id DESC')->select();
        $items = array();

        foreach ($list as $item){
            $item['pic'] = explode(';', $item['pic']);
            $item['pic'] = $item['pic'][0];
            $img = str_replace(',','/',$item['pic']);
            $img_url = file_domain().'/upload/appoint/'.$img;
            array_push($items,array('id' => $item['appoint_id'], 'name' => $item['appoint_name'], 'linkcode'=> $this->config['site_url'] . '/wap.php?g=Wap&c=Appoint&a=detail&appoint_id=' . $item['appoint_id'],'linkurl'=>'','keyword' => $item['appoint_content'],'img_url'=>$img_url));
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('content_detail');
    }

    public function Mall_test()
    {
        $this->assign('moduleName', $this->modules['Mall']);
        $db = M('Merchant_store_shop');
        $sqlCount = 'SELECT count(1) AS cnt FROM ' . C('DB_PREFIX') . 'merchant_store AS s INNER JOIN ' . C('DB_PREFIX') . 'merchant_store_shop AS p ON s.store_id=p.store_id WHERE s.mer_id=' . $mer_id . ' AND s.have_shop=1 AND s.status=1 AND p.store_theme=1';
        $merchant_session = session('merchant');
        $mer_id= $merchant_session ? $merchant_session['mer_id']:0;
        $countResult = D()->query($sqlCount);
        $count = isset($countResult[0]['cnt']) ? intval($countResult[0]['cnt']) : 0;
        $Page = new Page($count, 5);
        $show = $Page->show();
        $sql = 'SELECT s.name, s.store_id FROM ' . C('DB_PREFIX') . 'merchant_store AS s INNER JOIN ' . C('DB_PREFIX') . 'merchant_store_shop AS p ON s.store_id=p.store_id WHERE s.mer_id=' . $mer_id . ' AND s.have_shop=1 AND s.status=1 AND p.store_theme=1 LIMIT ' . $Page->firstRow . ',' . $Page->listRows;
        $list = D()->query($sql);
        $items = array();
        foreach ($list as $item){
            array_push($items, array('id' => $item['store_id'],
                'name' => $item['name'],
                'sub' => 1,
                'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Mall/store', array('store_id' => $item['store_id'], 'otherwc' => 1), true, false, true)),
                'sublink' => U('Link/MallSort', array('store_id' => $item['store_id'])),
                'keyword' => $item['name']));
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }

    public function MallSort()
    {
        $this->assign('moduleName', $this->modules['Mall'] . L_('商品分类'));

        $store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
        $items = M(_view('Shop_goods_sort'))->field(true)->where(array('store_id' => $store_id))->order('`sort` DESC,`sort_id` ASC')->select();
        $s_list = array();
        foreach ($items as $value) {
            $s_list[$value['sort_id']] = $value;
        }

        foreach ($s_list as $row) {
            unset($s_list[$row['fid']]);
        }
        $count = count($s_list);
        $Page = new Page($count, 5);


        $list = array_slice($s_list, $Page->firstRow, $Page->listRows);
        $show = $Page->show();
        $items = array();
        foreach ($list as $item){
            array_push($items, array('id' => $item['sort_id'],
                'name' => $item['sort_name'],
                'sub' => 0,
                'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/Mall/store', array('store_id' => $item['store_id'], 'sort_id' => $item['sort_id'], 'otherwc' => 1), true, false, true)),
                'keyword' => $item['name']));
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('detail');
    }

    public function Coupon_list()
    {
        $this->assign('moduleName', $this->modules['Coupon']);
        $where['end_time'] = array('gt',time());
        $where['status'] = 1;
        $where['allow_new'] = 0;
        $merchant_session = session('merchant');
        $where['mer_id'] = $merchant_session ? $merchant_session['mer_id']:0;
        $db2 = D('Card_new_coupon');
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

    protected function curl_post($url,$data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data));
        return curl_exec($ch);
    }


    /**
     * 店铺客服功能库
     */
    public function Store_kf()
    {
        $this->assign('moduleName', $this->modules_content['Store_kf']);
        $db = M('Merchant_store');
        $merchant_session = session('merchant');
        $where = [
            'mer_id' => $merchant_session ? $merchant_session['mer_id']:0,
            'status' => 1
        ];
        $count = $db->where($where)->count();
        $Page = new Page($count, 5);
        $show = $Page->show();
        $list = $db->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->order('store_id DESC')->select();
        $items = [];
        $storeImageClass = new store_image();
        foreach ($list as $item) {
            $images = $storeImageClass->get_allImage_by_path($item['pic_info']);
            $items[] = [
                'id' => $item['store_id'],
                'name' => $item['name'],
                'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Kefu/store', ['token' => $this->token, 'mer_id' => $item['mer_id'], 'store_id' => $item['store_id']], true, false, true)),
                'linkurl' => '',
                'keyword' => $item['name'],
                'img_url' => $images[0]
            ];
        }
        $this->assign('list', $items);
        $this->assign('page', $show);
        $this->display('content_detail');
    }


    public function housePublicRental(){
            $village_id = $this->house_session['village_id'];
            $list=array();
            $linkcode=$this->config['site_url'].'/packapp/village/pages/houseManage/pages/leaseApply?village_id='.$village_id.'&source_type=1';
            $linkcode=str_replace('//packapp','/packapp',$linkcode);
            $list[] = [
                'id' =>$village_id,
                'name' => '入住排号预约',
                'linkcode' => $linkcode,
                'linkurl' => '',
                'keyword' => '入住排号预约',
                'img_url' => ''
            ];
            $linkcode=$this->config['site_url'].'/packapp/village/pages/houseManage/pages/leaseApply?village_id='.$village_id.'&source_type=2';
            $linkcode=str_replace('//packapp','/packapp',$linkcode);
               $list[] = [
                'id' => $village_id,
                'name' => '退租排号预约',
                'linkcode' =>$linkcode,
                'linkurl' => '',
                'keyword' => '退租排号预约',
                'img_url' => ''
            ];
            $this->assign('list', $list);
            $this->display('housePublicRental');
    }
    public function neighborhood(){
        $village_id = $this->house_session['village_id'];
        $aBbs	=	M('Bbs')->where(array('third_type'=>'house','third_id'=>$village_id))->find();
        $list=array();
        $pagedata='';
        $linkcode=$this->config['site_url'].'/packapp/village/pages/village/resident/residentCategory?cat_id=';
        $linkcode=str_replace('//packapp','/packapp',$linkcode);
        if(!empty($aBbs)){
            $where=array();
            $where['bbs_id'] = $aBbs['bbs_id'];
            $where['cat_status'] =1;
            $listTmp = D('Bbs')->bbs_category_page_list($where);
            $listTmp=$listTmp['list'];
            if($listTmp['list']){
                foreach ($listTmp['list'] as $vv){
                    $img_url='';
                    if($vv['cat_logo']){
                        $img_url=$vv['cat_logo'];
                    }
                    $list[] = [
                        'id' => $vv['cat_id'],
                        'name' => $vv['cat_name'],
                        'linkcode' => $linkcode.$vv['cat_id'],
                        'linkurl' => '',
                        'keyword' => '邻里分类',
                        'img_url' =>$img_url,
                        ];
                }
            }
            $pagedata=$listTmp['pagebar'];
            
        }
        $this->assign('page', $pagedata);
        $this->assign('list', $list);
        $this->display('neighborhood_detail');
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