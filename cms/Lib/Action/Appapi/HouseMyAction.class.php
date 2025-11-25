<?php
/**
 * 小区个人中心相关接口
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/6/24 13:23
 */

class HouseMyAction  extends BaseAction{

    use customization;

    public $thirdConfig;
    public function index(){
        $village_id = I('village_id');
//        $wxapp_own_village_id = I('wxapp_own_village_id',0);
        if (empty($village_id)) {
            $this->returnCode('30000001');
        }
        $wxapp_own_village_id = I('wxapp_own_village_id');
        $db_house_village = D('House_village');
        $app_version = intval($_POST['app_version']);
        $deviceId = trim($_POST['Device-Id']);
        $arr = [];
        $now_village = D('House_village')->get_one($village_id);
        $now_village_config = M('House_village_config')->where(['village_id'=>$village_id])->find();
        $pigcms_id = I('pigcms_id',0);
        $address = '';
        $site_url = $this->config['site_url'];
        $base_url = $site_url . $db_house_village->base_url;
        $arr['set_url'] = $base_url."pages/my/myinfo";      
        //在该小区有无身份 true 标识有
        $has_identity = false;
        $today_sing = 0;
        $arr['pigcms_id'] = 0;
        if($this->_uid){
            $money_where=[
                'business_id'=>$village_id,
                'uid'=>$this->_uid
            ];
            $village_user_money = M('village_user_money_list')->where($money_where)->find();
            $now_user = D('User')->get_user($this->_uid);
            $level = M(_view('User_level'))->getField('level,lname');
            $now_user['lname'] = $this->_uid ? ($now_user['level'] ? $level[$now_user['level']] : 'VIP0') : '--';

            $recently_sign = M('User_sign')->where(array('uid'=>$now_user['uid']))->order('id DESC')->find();
            if($recently_sign && strtotime(date('Ymd',$_SERVER['REQUEST_TIME']))==strtotime(date('Ymd',$recently_sign['sign_time']))){
                $today_sing = 1;
            }
            if ($this->is_weixin_browser) {
                $rtn = D('Weixin_bind')->is_property_weixin($now_village['property_id'],$this->_uid);
                if($rtn['error'] == 1001){
                    $arr['redirect_uri'] = $site_url.$rtn['redirect_uri'];
                }else{
                    $arr['redirect_uri'] = '';
                }
            } else {
                $arr['redirect_uri'] = '';
            }
            if ($pigcms_id) {
                $bind_user_info = $this->get_user_village_info($pigcms_id, $village_id, $this->_uid, true);
                if($now_village['set_id_code'] == 1){
                    if(empty($bind_user_info['id_card'])){
//                        $param = [
//                            'pageName' => 'myinfo',
//                            'isAll' => true,
//                            'urlParam' => "village_id={$village_id}&pigcms_id={$pigcms_id}",
//                        ];
//                        $myinfoUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
//                        $arr['set_id_code'] = true;
//                        $arr['set_id_code_info'] = '您的身份证信息未完善，请前去完善';
//                        $arr['set_id_code_url'] = $myinfoUrl;
                    }else{
                        $arr['set_id_code'] = false;
                        $arr['set_id_code_info'] = '';
                        $arr['set_id_code_url'] = '';
                    }
                }else{
                    $arr['set_id_code'] = false;
                    $arr['set_id_code_info'] = '';
                    $arr['set_id_code_url'] = '';
                }
                if (4==$bind_user_info['type']) {
                    $address = '物业工作人员';
                } elseif(2==$bind_user_info['status']) {
                    $address = '房间待审核中';
                } else {
                    $address = $bind_user_info['address'];
                }
                $has_identity = true;
                $arr['pigcms_id'] = $pigcms_id;
            } else {
                // 如果没有传递身份查询下是否存在身份
                $where['uid'] = $this->_uid;
                $where['village_id'] = $village_id;
                $where['status'] = 2;
                $check_pigcms_id = M('House_village_user_bind')->where($where)->getField('pigcms_id');
                if ($check_pigcms_id) {
                    $has_identity = true;
                    $address = '房间待审核中';
                } else {
                    // 检查下是否存在唯一身份
                    $bind_village_list =  D('House_village_user_bind')->get_user_bind_list($this->_uid, $village_id);
                    if (!empty($bind_village_list) && count($bind_village_list)==1) {
                        $bind_user_info = reset($bind_village_list);
                        if($now_village['set_id_code'] == 1){
                            if(empty($bind_user_info['id_card'])){
//                                $param = [
//                                    'pageName' => 'myinfo',
//                                    'isAll' => true,
//                                    'urlParam' => "village_id={$village_id}&pigcms_id={$pigcms_id}",
//                                ];
//                                $myinfoUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
//                                $arr['set_id_code'] = true;
//                                $arr['set_id_code_info'] = '您的身份证信息未完善，请前去完善';
//                                $arr['set_id_code_url'] = $myinfoUrl;
                            }else{
                                $arr['set_id_code'] = false;
                                $arr['set_id_code_info'] = '';
                                $arr['set_id_code_url'] = '';
                            }
                        }else{
                            $arr['set_id_code'] = false;
                            $arr['set_id_code_info'] = '';
                            $arr['set_id_code_url'] = '';
                        }
                        $has_identity = true;
                        $address = $bind_user_info['address'];
                        if ($bind_user_info['pigcms_id']) {
                            $arr['pigcms_id'] = $bind_user_info['pigcms_id'];
                            $pigcms_id = $bind_user_info['pigcms_id'];
                        }
                    } else {
                        $has_identity = false;
                        $address = '无住户身份';
                    }
                }
            }
            $yuanJino2oCustomized=D('Config_customization')->getYuanJino2oCustomized();
            $registrationId = I('registrationId', '','trim');
            if($yuanJino2oCustomized && $registrationId && $village_id){
                $jpush_info=array('bind_id'=>$pigcms_id,'user_table_type'=>'house_village_user_bind');
                $client_type = I('app_type', 0,'intval'); //1是 ios  2是android
                $platform_client='';
                if($client_type==1){
                    $platform_client='ios';
                }else if($client_type==2){
                    $platform_client='android';
                }
                $jpush_info['platform']=$platform_client;
                D('House_app_jpush_info')->saveJpushInfo($registrationId,$village_id,$this->_uid,$jpush_info);
            }
        }
        if(empty($now_user['avatar'])){
            $now_user['avatar']   =   $site_url . '/static/images/user_avatar.jpg';
        }

        if ($bind_user_info['phone']) {
            $phone = $bind_user_info['phone'];
        } elseif ($now_user['phone']) {
            $phone = $now_user['phone'];
        } else {
            $phone = '';
        }
        if ($bind_user_info['name']) {
            $name = $bind_user_info['name'];
        } elseif ($now_user['nickname']) {
            $name = $now_user['nickname'];
        } else {
            $name = '';
        }
        $is_sign = true;
        if($this->config['sign_get_score'] == '0' || empty($this->config['sign_get_score'])){
            $is_sign = false;
        }
        $arr['user'] = array(
            'phone'     =>  $phone,
            'nickname'  =>  $name,
            'address' => $address,
            'uid'=>$this->_uid,
            'type' => $bind_user_info['type']?$bind_user_info['type']:'',
            'now_money' =>  $this->_uid ? getFormatNumber($now_user['now_money']) : '--',
            'score_count'=> $this->_uid ? getFormatNumber($now_user['score_count']) : '--',
            'level'     =>  $now_user['lname']?$now_user['lname']:'',
            'avatar'    =>  $now_user['avatar'],
            'bg'        =>  $site_url.'/tpl/Wap/pure/static/images/my-photo.png',
            'is_sign'        => $is_sign,
            'sign_today'        => $today_sing,
            'sign_get_score'    => intval($this->config['sign_get_score']),
            'open_custom_sign'  => C('config.open_custom_sign') ? "1" : "0",
            'custom_sign_url'   => $site_url.'/wap.php?g=Wap&c=My&a=sign',
        );
        //余额
        $arr['user_link'] = [];
        if($this->config['open_user_money'] || getFormatNumber($now_user['now_money'])){
            $user_link = [];
            $user_link['is_house']  = 0;
            $user_link['value']  = $this->_uid ? getFormatNumber($now_user['now_money']) : '--';
            $user_link['name']  = L_('余额');
            if ($this->_uid && $wxapp_own_village_id>0) {
                $user_link['url']  = $site_url.'/wap.php?g=Wap&c=My&a=my_money&wxapp_own_village_id='.$wxapp_own_village_id;
            } elseif ($this->_uid) {
                $user_link['url']  = $site_url.'/wap.php?g=Wap&c=My&a=my_money';
            } else {
                $user_link['url']  = '';
            }
            $arr['user_link'][] = $user_link;
        }

        //住户余额
       //  print_r([$now_village_config['open_village_balance'],getFormatNumber($village_user_money['current_money'])]);die;
        if($now_village_config['open_village_balance'] || getFormatNumber($village_user_money['current_money'])){
            $user_link = [];
            $user_link['is_house']  = 1;
            $user_link['value']  = $this->_uid ? getFormatNumber($village_user_money['current_money']) : '--';
            $user_link['name']  = L_('住户余额');
            if ($this->_uid && $wxapp_own_village_id>0) {
                $user_link['url']  = $base_url.'pages/houseMeter/NewCollectMoney/balanceRecord?village_id='.$village_id.'&uid='.$this->_uid.'&wxapp_own_village_id='.$wxapp_own_village_id;
            } elseif ($this->_uid) {
                $user_link['url']  = $base_url.'pages/houseMeter/NewCollectMoney/balanceRecord?village_id='.$village_id.'&uid='.$this->_uid;
            } else {
                $user_link['url']  = '';
            }
            $arr['user_link'][] = $user_link;
        }
        //积分
        $user_link = [];
        $user_link['is_house']  = 0;
        $user_link['value']  = $this->_uid ? getFormatNumber($now_user['score_count']) : '--';
        $user_link['name']  = $this->config['score_name']?$this->config['score_name']:L_('积分');
        if ($this->_uid) {
            $user_link['url']  = $site_url.'/wap.php?g=Wap&c=My&a=integral';
        } else {
            $user_link['url']  = '';
        }
        $arr['user_link'][] = $user_link;
        //等级
        $user_link = [];
        $user_link['is_house']  = 0;
        $user_link['value']  = $this->_uid ? $now_user['lname'] : '--';
        $user_link['name']  = L_('等级');
        if ($this->_uid) {
            $user_link['url']  = $site_url.'/wap.php?g=Wap&c=My&a=levelUpdate';
        } else {
            $user_link['url']  = '';
        }
        $arr['user_link'][] = $user_link;
        // 滚动消息
        $arr['scroll_msg'] = [];
        $arr['scroll_image'] = '';
        if($this->config['show_scroll_msg']){
            $tmp_scroll =  D('Scroll_msg')->get_msg();
            foreach ($tmp_scroll as $v) {
                $arr['scroll_msg'][] = $v['content'];
            }
            $arr['scroll_image'] = $site_url.'/static/images/my_new/scroll.png';
        }

        $arr['block'] = [];
        // 基本信息
        $base_block = [];


        if (in_array($bind_user_info['type'],[0,1,2,3])) {
            // 三方楼宇只有是 业主家属租客显示
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
                fdump_api(['Appapi-houseMy',$whereThirdConfig,$e->getMessage()],'thirdConfigLog');
            }
            if ($thirdConfigInfo && $thirdConfigInfo['thirdSite'] && $thirdConfigInfo['thirdId']) {
                $thirdBaseUrl = 'https://'.$thirdConfigInfo['thirdSite'];
                $appid = trim($this->config['third_building_appid']);
                $key = md5($appid.$phone);
                $referer = $thirdBaseUrl . "/h5/#/pages/community/tenantApprovalList?key={$key}&phone={$phone}&build_id={$thirdConfigInfo['thirdId']}&third_type=wechat";
                if (!$this->_uid) {
                    $title = L_('我的员工');
                    $url = C('config.site_url')."/packapp/my/login.html?back=". urlencode($referer) ."&loginFail=". urlencode($referer);
                } elseif (!$phone) {
                    $title = L_('绑定手机号');
                    $url = get_base_url("pages/my/newnumber?back=". urlencode($referer));
                } else {
                    $title = L_('我的员工');
                    $url = $referer;
                }
                $base_block[] = array(
                    'type' => 'third_linghui',
                    'is_house' => $has_identity?0:1,
                    'title'=> $title,
                    'url' => $has_identity?$url:'',
                    'icon' => $this->config['site_url'].'/static/images/my_new/staffs.png'
                );
            }
        }
        $house_name=$this->config['house_name'];
        $house_name=trim($house_name);
        if(empty($house_name)){
            $house_name='小区';
        }
        if (empty($base_block)) {
            $param = [
                'pageName' => 'myVillage',
                'isAll' => true,
                'urlParam' => 'village_id='.$village_id,
            ];
            $myVillageUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
            $base_block[] = [
                'is_house' => 1,
                'title' => L_('我的'.$house_name),
                'url' => $has_identity? $myVillageUrl : '',
                'icon' => $site_url . '/static/images/house/my_house.png?t=03',
            ];
        }
        $package_order_data = D('PrivilegePackage')->getPackageContent($now_village['property_id']);
        $content_id = isset($package_order_data['content'])&&$package_order_data['content']?$package_order_data['content']:[];
        $is_open_pay_management = D('House_menu_new')->getPayCostAppIsUsable($village_id);
        if($is_open_pay_management && in_array('5',$content_id)){//套餐相关购买套餐有次功能，方可使用
            $getIsAihorseCustomized = D('Config_customization')->getIsAihorseCustomized();
            $third_pay_url = '';
            if($getIsAihorseCustomized){
                $village_info_arr= M('House_village_info')->where(['village_id'=>$village_id])->field('third_pay_url,third_ccb_pay_url')->find();
                $third_pay_url = isset($village_info_arr['third_pay_url']) && $village_info_arr['third_pay_url'] ? $village_info_arr['third_pay_url'] : $third_pay_url;
                $third_pay_url = isset($village_info_arr['third_ccb_pay_url']) && $village_info_arr['third_ccb_pay_url'] ? $village_info_arr['third_ccb_pay_url'] : $third_pay_url;
            }
            if(!$third_pay_url && C('customization.abchina')){
                $abchina_set = M('House_abchina_set')->where(['property_id'=>$now_village['property_id']])->find();
                if($abchina_set && $abchina_set['epay_code'] && $abchina_set['merchant_id'] && $abchina_set['merchant_pfx'] && $abchina_set['trust_pay_cer'] && $abchina_set['abchina_prikey'] && $abchina_set['pay_url']){
                    $third_pay_url = $abchina_set['pay_url'];
                }
            }
            $is_new_charge=D('House_village')->checkNewCharge($now_village['property_id']);
            if ($is_new_charge) {
                /***
                $hzhou_grapefruit_order=D('Config_customization')->getHzhouGrapefruitOrderJudge();
                 **/
                $hzhou_grapefruit_order=1;
                if ($hzhou_grapefruit_order){
                    $liveExpenses =[
                        'is_house' => 1,
                        'title' => L_('生活缴费'),
                        'url' => $this->_uid&&$pigcms_id?$base_url . 'pages/houseMeter/NewCollectMoney/newLiveExpenses?url_type=village&village_id=' . $village_id . '&pigcms_id=' . $pigcms_id:'',
                        'icon' => $site_url . '/static/images/house/my_paylists.png?t=03',
                    ];  
                }else{
                    $liveExpenses =[
                        'is_house' => 1,
                        'title' => L_('生活缴费'),
                        'url' => $this->_uid&&$pigcms_id?$base_url . 'pages/houseMeter/NewCollectMoney/liveExpenses?village_id=' . $village_id . '&pigcms_id=' . $pigcms_id:'',
                        'icon' => $site_url . '/static/images/house/my_paylists.png?t=03',
                    ]; 
                }
            }else{
                $param = [
                    'pageName' => 'payMentlist',
                    'isAll' => true,
                    'urlParam' => 'village_id=' . $village_id . '&pigcms_id=' . $pigcms_id,
                ];
                $payMentlistUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
                $liveExpenses =[
                    'is_house' => 1,
                    'title' => L_('生活缴费'),
                    'url' => $this->_uid&&$pigcms_id?$payMentlistUrl:'',
                    'icon' => $site_url . '/static/images/house/my_paylists.png?t=03',
                ];
            }
            $liveExpenses['url'] = $third_pay_url ? $third_pay_url : $liveExpenses['url'];
            $base_block[] = $liveExpenses;
        }
        if (!$myinfoUrl) {
            $param = [
                'pageName' => 'myinfo',
                'isAll' => true,
                'urlParam' => 'village_id=' . $village_id . '&pigcms_id=' . $pigcms_id,
            ];
            $myinfoUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
        }
        $base_block[] =[
            'is_house' => 1,
            'title' => L_('完善资料'),
            'url' => $this->_uid&&$pigcms_id?$myinfoUrl:'',
            'icon' => $site_url . '/static/images/house/user_info.png?t=03',
        ];

        $base_block[] =[
            'is_house' => 1,
            'title' => L_('信息二维码'),
            'url' => $this->_uid&&$pigcms_id?$base_url . 'pages/village/visitorPass/userinfoCode?village_id=' . $village_id . '&pigcms_id=' . $pigcms_id:'',
            'icon' => $site_url . '/static/images/house/erweima.png?t=03',
        ];

        $arr['block'][] = [
            'title' => '基本信息',
            'type' => 'base',
            'list' => $base_block
        ];
        //我的爱车
        $where_bind_car='village_id='.$now_village['village_id'].' AND is_default=1 AND (user_id='.$pigcms_id.' OR uid='.$this->_uid.')';
        $car_id=M('House_village_bind_car')->where($where_bind_car)->find();
        $car_list=[];
        if(in_array($_POST['app_type'], ['1', '2', 'android', 'ios'])){
            $car_list1=[];
            $car_list1['pay_url']=$base_url.'pages/parkingLot/index?currentIndex=3&village_id='.$now_village['village_id'].'&pigcms_id='.$pigcms_id;
            $car_list1['url']=$base_url.'pages/parkingLot/pages/carManage?village_id='.$now_village['village_id'].'&pigcms_id='.$pigcms_id;
            $car_list1['is_house']=1;
            if (!empty($car_id)){
                $where_car = [
                    'car_id' => $car_id['car_id'],
                ];
                $car_info=M('House_village_parking_car')->where($where_car)->find();
                if (!empty($car_info)){
                    $aa=explode('-',$car_info['car_brands']);
                    $brand_cars=M('Brand_cars')->where(['brand_name'=>$aa[0]])->find();

                    $car_list1['car_info']=[
                        'car_number'=>$car_info['province'].mb_substr($car_info['car_number'],0,1).'-'.mb_substr($car_info['car_number'],1),
                        'car_brands'=>empty($car_info['car_brands'])?'':$car_info['car_brands'],
                        'car_brands_logo'=>empty($brand_cars['brand_logo'])?$site_url.'/static/images/car_logo/default.png':$site_url.$brand_cars['brand_logo'],
                    ];
                    $car_list1['news_info']=[
                        'title'=>'',
                        'number'=>'',
                        'price'=>'',
                        'content'=>'',
                    ];
                    /*$car_list1['news_info']=[
                        'title'=>'今日油价',
                        'number'=>'98#',
                        'price'=>'￥8.7',
                        'content'=>'加油满200，最高减40！',
                    ];*/
                    $car_list1['pay_url']=$base_url.'pages/parkingLot/index?currentIndex=3&village_id='.$now_village['village_id'].'&pigcms_id='.$pigcms_id;
                    $car_list1['url']=$base_url.'pages/parkingLot/pages/carManage?village_id='.$now_village['village_id'].'&pigcms_id='.$pigcms_id;
                    $car_list1['is_house']=1;
                }

            } else{

                $car_list1['car_info']=null;
                $car_list1['news_info']=[
                    'title'=>'',
                    'number'=>'',
                    'price'=>'',
                    'content'=>'',
                ];
            }
            $car_list[]=$car_list1;
        }else{
            if (!empty($car_id)){
                $where_car = [
                    'car_id' => $car_id['car_id'],
                ];
                $car_info=M('House_village_parking_car')->where($where_car)->find();
                if (!empty($car_info)){
                    $aa=explode('-',$car_info['car_brands']);
                    $brand_cars=M('Brand_cars')->where(['brand_name'=>$aa[0]])->find();

                    $car_list['car_info']=[
                        'car_number'=>$car_info['province'].mb_substr($car_info['car_number'],0,1).'-'.mb_substr($car_info['car_number'],1),
                        'car_brands'=>empty($car_info['car_brands'])?'':$car_info['car_brands'],
                        'car_brands_logo'=>empty($brand_cars['brand_logo'])?$site_url.'/static/images/car_logo/default.png':$site_url.$brand_cars['brand_logo'],
                    ];
                }else{
                    $car_list['car_info']=[];
                }
            }else{
                $car_list['car_info']=[];
            }

            $car_list['news_info']=[
                'title'=>'',
                'number'=>'',
                'price'=>'',
                'content'=>'',
            ];
            $car_list['pay_url']=$base_url.'pages/parkingLot/index?currentIndex=4&village_id='.$now_village['village_id'].'&pigcms_id='.$pigcms_id;
            $car_list['url']=$base_url.'pages/parkingLot/pages/carManage?village_id='.$now_village['village_id'].'&pigcms_id='.$pigcms_id;
            $car_list['is_house']=1;
        }

        if (!empty($car_list)) {
            $arr['block'][] = [
                'title' => '我的爱车',
                'type' => 'car',
                'list' => $car_list
            ];
        }
        // 硬件服务
        $device_block = [];
        if (4 == $bind_user_info['type']) {
            $m_house_worker = M('House_worker');
            $worker_info = $m_house_worker->where(array('phone' => $now_user['phone'], 'status' => 1,'is_del'=>0, 'village_id' => $bind_user_info['village_id']))->field('wid,open_door')->find();
            $bind_user_info['worker_open_door'] = $worker_info['open_door']?$worker_info['open_door']:0;
        } else {
            $bind_user_info['worker_open_door'] = 1;
        }
        $my_face_door_switch = $this->config['my_face_door_switch']?1:0;
        if (!$my_face_door_switch && $this->_uid && $now_village['open_face_door'] == 1 && $bind_user_info['worker_open_door'] == 1) {
            // 获取下社区 家属出入通知设置 是否存在
            if (0 == $bind_user_info['type'] || 3 == $bind_user_info['type']) {
                $is_notice = true;
            } elseif (1 == $bind_user_info['type'] && 1 == $bind_user_info['relatives_type']) {
                $is_notice = true;
            } else {
                $is_notice = false;
            }
            //套餐相关购买套餐有次功能，方可使用
            $third_where = [
                'type' => 'chinapopin_sichuan',
                'village_id' => $village_id,
            ];
            $third_config = M('House_village_third_config')->where($third_where)->find();
            if(in_array('7',$content_id)) {
                if ($third_config && 1==$third_config['status'] && $third_config['face_url']) {
                    $device_block[] = array(
                        'is_house' => 1,
                        'title' => L_('人脸识别门禁'),
                        'url' => $this->_uid&&$pigcms_id ? $third_config['face_url']:'',
                        'icon' => $site_url . '/static/images/house/my_face_img.png?t=03'
                    );
                } elseif (0 == $bind_user_info['type'] || 3 == $bind_user_info['type']) {
                    $family_info = M('House_village_user_bind')->where(array('parent_id' => $bind_user_info['pigcms_id']))->field('pigcms_id')->select();
                    if (count($family_info) <= 0) {
                        $param = [
                            'pageName' => 'faceAccessControl',
                            'isAll' => true,
                            'urlParam' => 'village_id='.$village_id . '&add_pigcms_id=' . $bind_user_info['pigcms_id']. '&pigcms_id=' . $bind_user_info['pigcms_id'],
                        ];
                        $faceAccessControlUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
                        $device_block[] = array(
                            'is_house' => 1,
                            'title' =>  L_('人脸识别门禁'),
                            'url' => $this->_uid&&$pigcms_id ? $faceAccessControlUrl:'',
                            'icon' => $site_url . '/static/images/house/my_face_img.png?t=03'
                        );
                    } else {
                        $param = [
                            'pageName' => 'selectBindFace',
                            'isAll' => true,
                            'urlParam' => '',
                        ];
                        $selectBindFaceUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
                        $device_block[] = array(
                            'is_house' => 1,
                            'title' =>  L_('人脸识别门禁'),
                            'url' => $this->_uid&&$pigcms_id ? $selectBindFaceUrl:'',
                            'icon' => $site_url . '/static/images/house/my_face_img.png?t=03'
                        );
                    }
                } else {
                    $param = [
                        'pageName' => 'faceAccessControl',
                        'isAll' => true,
                        'urlParam' => 'village_id='.$village_id . '&add_pigcms_id=' . $bind_user_info['pigcms_id']. '&pigcms_id=' . $bind_user_info['pigcms_id'],
                    ];
                    $faceAccessControlUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
                    $device_block[] = array(
                        'is_house' => 1,
                        'title' => L_('人脸识别门禁'),
                        'url' => $this->_uid&&$pigcms_id ? $faceAccessControlUrl:'',
                        'icon' => $site_url . '/static/images/house/my_face_img.png?t=03'
                    );
                }
            }
            //套餐相关购买套餐有次功能，方可使用
            if(in_array('7',$content_id) || in_array('8',$content_id)) {
                if ($is_notice) {
                    $param = [
                        'pageName' => 'familyMembersNotice',
                        'isAll' => true,
                        'urlParam' => '',
                    ];
                    $familyMembersNoticeUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
                    $device_block[] = array(
                        'is_house' => 1,
                        'title' => L_('家属出入通知'),
                        'url' => $this->_uid&&$pigcms_id ? $familyMembersNoticeUrl:'',
                        'icon' => $site_url . '/static/images/house/my_notice_family.png?t=03'
                    );
                }
            }
        }

        if($this->_uid && $bind_user_info && in_array('7',$content_id) || in_array('8',$content_id)) {
            $db_house_camera_device = M('House_camera_device');
            $camera_where = array(
                'village_id' => $now_village['village_id'],
                'is_support_look' => 1,
                'look_url' => array('neq', ''),
                'camera_status' => 0
            );
            $camera_where['_string'] = "(floor_id>0 && floor_id={$bind_user_info['floor_id']}) OR (floor_id<=0)";

            $count_info = $db_house_camera_device->where($camera_where)->count();
            if ($count_info && $count_info > 0) {
                $param = [
                    'pageName' => 'hawkEye',
                    'isAll' => true,
                    'urlParam' => '',
                ];
                $hawkEyeUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
                $device_block[] = array(
                    'is_house' => 1,
                    'title' => $this->config['hawkEyeName']?$this->config['hawkEyeName']:'鹰眼服务',
                    'url' => $hawkEyeUrl,
                    'icon' => $site_url . '/static/images/house/camera.png?t=03'
                );
            }
        }
        $third_robot_recognition_open = $this->config['third_robot_recognition_open'];
        if (intval($third_robot_recognition_open) === 1) {
            $device_block[] = array(
                'is_house' => 0,
                'title' => '机器人服务',
                'url' => $this->_uid ? $base_url . "pages/robot/call_robot_list?village_id={$village_id}&call_from=village_my&call_from_id={$village_id}" : '',
                'icon' => $site_url . '/static/images/house/robot.png?t=03'
            );
        }
        $getFaceCloud260Judge = D('Config_customization')->getFaceCloud260Judge();
        $deviceQrCodeJudge = D('Config_customization')->deviceQrCodeJudge($village_id);
        if ($this->_uid && $bind_user_info && ($getFaceCloud260Judge || $deviceQrCodeJudge)) {
            $db_face_user_bind_device = D('Face_user_bind_device');
            $whereFaceUserBindDevice = [
                'bind_type' => 30,
                'bind_id' => $bind_user_info['pigcms_id'],
                'device_type' => 30,
            ];
            $fieldFaceUserBindDevice = 'id, bind_type, bind_id, device_type, open_key, open_key_time, code, person_id, person_text';
            $infoFaceUserBindDevice = $db_face_user_bind_device->where($whereFaceUserBindDevice)->field($fieldFaceUserBindDevice)->find();
            if (isset($infoFaceUserBindDevice['code']) && $infoFaceUserBindDevice['code']) {
                $device_block[] = array(
                    'is_house' => 1,
                    'title' => L_('开门二维码'),
                    'url' => $this->_uid && $pigcms_id ? $base_url . 'pages/village/visitorPass/openCode?village_id=' . $village_id . '&pigcms_id=' . $pigcms_id : '',
                    'icon' => $site_url . '/static/images/house/erweima.png?t=03',
                );
            } elseif ($deviceQrCodeJudge) {
                $device_block[] = array(
                    'is_house' => 1,
                    'title' => L_('开门二维码'),
                    'url' => $this->_uid && $pigcms_id ? $base_url . 'pages/village/visitorPass/openCode?village_id=' . $village_id . '&pigcms_id=' . $pigcms_id : '',
                    'icon' => $site_url . '/static/images/house/erweima.png?t=03',
                );
            } else {
                $whereFaceUserBindDevice = [
                    'bind_type' => 31,
                    'personID' => $this->_uid,
                ];
                $fieldFaceUserBindDevice = 'id, bind_type, bind_id, device_type, open_key, open_key_time, code, person_id, person_text';
                $infoFaceUserBindDevice = $db_face_user_bind_device->where($whereFaceUserBindDevice)->field($fieldFaceUserBindDevice)->find();
                if (isset($infoFaceUserBindDevice['code']) && $infoFaceUserBindDevice['code']) {
                    $device_block[] = array(
                        'is_house' => 1,
                        'title' => L_('开门二维码'),
                        'url' => $this->_uid && $pigcms_id ? $base_url . 'pages/village/visitorPass/openCode?village_id=' . $village_id . '&pigcms_id=' . $pigcms_id : '',
                        'icon' => $site_url . '/static/images/house/erweima.png?t=03',
                    );
                }
            }
        }
        
        $is_func_cloudmrsqfw_special=D('Config_customization')->getFuncCloudmrsqfwSpecial();
        if($is_func_cloudmrsqfw_special && $this->_uid && $bind_user_info){
            $whereArr=array('village_id'=>$village_id);
            $village_data = M('House_village')->where($whereArr)->find();
            if($village_data && !empty($village_data['lilin_pf_client_id']) && !empty($village_data['lilin_pf_client_secret']) && !empty($village_data['lilin_village_no'])){
                $whereArr=array('village_id'=>$village_id,'third_dev_source'=>'lilin_pf','third_dev_id'=>array('neq',''),'is_del'=>array('lt',4));
                $lilin_third_dev= D('House_face_device')->where($whereArr)->find();
                if($lilin_third_dev){
                    $device_block[] = array(
                        'is_house' => 1,
                        'title' => L_('开门二维码'),
                        'url' => $this->_uid && $pigcms_id ? $base_url . 'pages/village/visitorPass/openCode?village_id=' . $village_id . '&pigcms_id=' . $pigcms_id : '',
                        'icon' => $site_url . '/static/images/house/erweima.png?t=03',
                    );
                }
            }
        }
        
        if (!empty($device_block)) {
            $arr['block'][] = [
                'title' => '硬件服务',
                'type' => 'device',
                'list' => $device_block
            ];
        }
        
        $whereSystemMenu = ['id' => 13, 'show' => 1, 'status' => 1];
        $hasMerchant = M('System_menu')->where($whereSystemMenu)->find();
        if (!empty($hasMerchant)) {
            $uid = $this->_uid;
            $tip_number = 0;
            $mer_number = 0;
            $coupon_number = 0;
            $activity_number = 0;
            $receive_num = 0;
            $discount_block = [];
            if ($uid) {
                //	商家优惠券
                $mer_list = D('Card_new_coupon')->get_user_all_coupon_list($uid, 1);
                if ($mer_list) {
                    $mer_number = count($mer_list);
                }
                //	平台优惠券
                $coupon_list = D('System_coupon')->get_user_coupon_list($uid, $now_user['phone'], 1);
                if ($coupon_list) {
                    $coupon_number = count($coupon_list);
                }
                $tip_number = $mer_number + $coupon_number;
            }
            $discount_block[] = array(
                'is_house' => 0, 'title' => L_('优惠券'),
                'url' => $uid ? $base_url . 'pages/coupon/myCoupon' : '',
                'icon' => $site_url . '/static/images/my_new/card_merchant.png?t=03',
                'showNumber' => 1, 'number' => $uid ? $tip_number : '--',
            );
            //	统计我参与的活动
            if ($uid) {
                $sql = "SELECT COUNT(*) AS tp_count FROM `pigcms_extension_activity_record` `ear`,`pigcms_extension_activity_list` `eal`,`pigcms_merchant` `m` WHERE(`ear`.`activity_list_id` = `eal`.`pigcms_id` AND `eal`.`mer_id` = `m`.`mer_id`AND `ear`.`uid` = '$uid') GROUP BY `eal`.`pigcms_id` ";
                $mod = new Model();
                $result = $mod->query($sql);
                $activity_number = count($result);
            }
            $discount_block[] = array(
                'is_house' => 0, 'title' => L_('参与活动'),
                'url' => $uid ? $site_url . '/wap.php?g=Wap&c=My&a=join_activity' : '',
                'icon' => $site_url . '/static/images/my_new/card_active.png?t=03',
                'showNumber' => 1, 'number' => $uid ? $activity_number : '--',
            );
            if (C('customization.group_order_giving') == 1) {
                //获取未使用转赠券数量
                if ($uid) {
                    $card_new_coupon_list = D('System_coupon')->get_user_card_new_coupon(['uid' => $uid], ['status' => 1]);
                    $receive_num = count($card_new_coupon_list);
                }
                $discount_block[] = array(
                    'is_house' => 0, 'title' => L_('团购券'),
                    'url' => $uid ? $base_url . 'pages/coupon/myGroupCoupon' : '',
                    'icon' => $site_url . '/static/images/my_new/tuangou_zhuanzeng.png?t=03',
                    'showNumber' => 1, 'number' => $uid ? $receive_num : '--',
                );
            }
            $has_card_number = true;
            if ($this->config['open_company_advance_deposit']) {
                $has_card_number = false;
            }
            if ($has_card_number) {
                //	统计我的会员卡
                $sql = 'SELECT c.card_id,c.bg,c.diybg,m.name,c.discount,cl.id as cardid,cl.card_money,cl.card_money_give,m.pic_info,m.mer_id FROM '
                    . C('DB_PREFIX') . 'card_userlist `cl`  left join '
                    . C('DB_PREFIX') . 'card_new `c` on cl.mer_id = c.mer_id left join '
                    . C('DB_PREFIX') . 'merchant m on c.mer_id  = m.mer_id WHERE ( cl.uid = ' . $uid . ' AND c.status=1 AND cl.status=1 AND m.status=1 ) ';
                $res = M('')->query($sql);
                foreach ($res as $v) {
                    $tmp[$v['card_id']]['id'] = $v['cardid'];
                }
                $card_number = count($tmp);
                $discount_block[] =  array(
                    'is_house' => 0, 'title' => L_('会员卡'),
                    'url' => $site_url . '/wap.php?g=Wap&c=My&a=cards',
                    'icon' => $site_url . '/static/images/my_new/card_vip.png?t=03',
                    'showNumber' => 1, 'number' => $uid ? $card_number : '--',
                );
            }
            $arr['block'][] = [
                'title' => '我的卡券',
                'type' => 'coupon',
                'list' => $discount_block
            ];
        }

        // 我的订单
        $where_system_order = [
            'uid' => $this->_uid,
            'is_del'=>0,
            'type' => ['in', ['mall', 'shop', 'meal', 'group', 'appoint', 'service', 'store', 'mobile_recharge','gift','village_group', 'mall3']]
        ];
        $db_system_order = M('System_order');
        $nopayList = $db_system_order->where(array_merge($where_system_order, ['is_del' => 0, 'paid' => 0, 'system_status' => 0]))->select();
        if (!empty($nopayList)) {//先删除无效订单
            foreach ($nopayList as $nv) {
                if ($nv['type'] == 'mall3') {
                    $order_info1 = M('mall_order')->where(['order_id' => $nv['order_id']])->find();
                    if(empty($order_info1)){//屏蔽掉无效订单
                        M("System_order")->where(['id' => $nv['id']])->setField('is_del', 1);
                    }
                }
            }
        }
        $total_count = $db_system_order->where($where_system_order)->count();

        if ($total_count && $total_count>0) {
            $status_block = [];
            $status_block[] = [
                'is_house' => 0,
                'status' => 0, //全部
                'title' => L_('全部订单'),
                'icon' => $site_url.'/static/status/0.png',
                'nums' => -1,//不显示
                'url' => $base_url . 'pages/my/my_order?state=0'
            ];
            $status_block[] = [
                'is_house' => 0,
                'status' => 1,
                'title' => L_('待付款'),
                'icon' => $site_url.'/static/status/1.png',
                'nums' => $db_system_order->where(array_merge($where_system_order, ['is_del' => 0, 'paid' => 0, 'system_status' => 0]))->count(),
                'url' => $base_url . 'pages/my/my_order?state=1'
            ];
            $status_block[] = [
                'is_house' => 0,
                'status' => 2,
                'title' => L_('待发货'),
                'icon' => $site_url.'/static/status/2.png',
                'nums' => $db_system_order->where(array_merge($where_system_order, ['paid'=>1,'system_status'=>0, 'type'=>['in', ['mall', 'shop', 'group', 'appoint', 'service', 'store', 'mobile_recharge','gift','village_group', 'mall3']]]))->count(),
                'url' => $base_url . 'pages/my/my_order?state=2'
            ];
            $status_block[] = [
                'is_house' => 0,
                'status' => 3,
                'title' => L_('待收货'),
                'icon' => $site_url.'/static/status/3.png',
                'nums' => $db_system_order->where(array_merge($where_system_order, ['system_status'=>1, 'type'=>['in', ['mall', 'shop', 'group', 'appoint', 'service', 'store', 'mobile_recharge','gift','village_group', 'mall3']]]))->count(),
                'url' => $base_url . 'pages/my/my_order?state=3'
            ];
            $status_block[] = [
                'is_house' => 0,
                'status' => 4,
                'title' => L_('待评价'),
                'icon' => $site_url.'/static/status/4.png',
                'nums' => $db_system_order->where(array_merge($where_system_order, ['system_status'=>2, 'type'=>['in', ['mall', 'shop', 'group', 'appoint', 'mobile_recharge','gift','village_group', 'mall3']]]))->count(),
                'url' => $base_url . 'pages/my/my_order?state=4'
            ];
            $status_block[] = [
                'is_house' => 0,
                'status' => 5,
                'title' => L_('退款/售后'),
                'icon' => $site_url.'/static/status/5.png',
                'nums' => -1,//不显示
                'url' => $base_url . 'pages/my/my_order?state=5'
            ];
            $arr['block'][] = [
                'title' => '我的订单',
                'type' => 'my_order',
                'list' => $status_block
            ];
        }
        $arr['my_o2o_record']=array();
        //  显示商业的 我的记录 
        $housemy_show_o2o_record = D('Config_customization')->getShowO2oRecord();
        if($housemy_show_o2o_record){
            $myo2oRecord = array();
            $myo2oRecord[] = array(
                'title'=>L_('我的足迹'),
                'image'=> $this->config['site_url'].'/static/images/my_new/my_tracks.png',
                'url'=> $this->config['site_url']. D('House_village')->base_url . 'pages/my/my_tracks',
            );
            $myo2oRecord[] = array(
                'title'=>L_('我的评价'),
                'image'=> $this->config['site_url'].'/static/images/my_new/my_reply.png',
                'url'=> $this->config['site_url']. D('House_village')->base_url . 'pages/my/my_evaluate',
            );
            if($this->config['my_release_show']==1){
                $myo2oRecord[] = array(
                    'title'=>L_('我的发布'),
                    'image'=> $this->config['site_url'].'/static/images/my_new/my_release.png',
                    'url'=> $this->config['site_url']. '/packapp/platn/pages/wantToBuy/v1/releaseList/index',
                );
            }
            $myo2oRecord[] = array(
                'title'=>L_('我的收藏'),
                'image'=> $this->config['site_url'].'/static/images/my_new/my_collect.png',
                'url'=> $this->config['site_url']. D('House_village')->base_url . 'pages/my/my_collect',
            );
            $arr['my_o2o_record'] = $myo2oRecord;
        }
        $arr['list'] = [];
        $list = [];

        //todo 新增平台会员卡 + 我的推广

        if ($membershipCard = (new Membership_cardModel())->getCurrentMembershipCard()){
            $list[] = array(
                'type' => 'platform_card',
                'is_house' => 0,
                'title'=>L_('平台会员卡'),
                'url'=>$this->_uid ? $base_url.'pages/membership/platMembership':'',
                'icon' => $this->config['site_url'].'/static/images/my_new/membership.png'
            );
        }

        if($this->_uid){
            //增加会员等级推广判断
            $nowUserLevel = (new UserModel())->getLevelByUid($now_user['uid']);
            if((($now_user['level'] > 0 && $nowUserLevel['can_spread']) || $now_user['level'] == '0') && $this->config['open_user_spread'] && !$this->config['open_single_spread'] && (!$this->config['open_spread_appoint_user'] || $now_user['can_spread']==1)) {
                $list[] = array(
                    'type' => 'my_extension',
                    'is_house' => 0,
                    'title'=>L_('我的推广'),
                    'url'=>$this->_uid ? $site_url.'/wap.php?c=My&a=my_spread':'',
                    'icon' => $this->config['site_url'].'/static/images/my_new/tuiguang.png'
                );
            }
        }

        //是否开启房产推荐
        $plugin = D('Plugin')->where(['status'=> 1,'plugin_label'=>'fangchandengji'])->find();
        if($this->_uid && $plugin){
            $list[] = array(
                'is_house' => 0,
                'type' => 'real_estate',
                'title' => L_("房产推荐管理"),
                'url' => $this->config['site_url'].'/packapp/plat/pages/my/property_recommendation',
                'icon'=>$site_url .'/static/images/my_new/realstate.png',
            );
        }

        // 物业管家
        $keeper_info = D('House_village')->property_contact(0,$village_id,$pigcms_id,true);
        if ($this->_uid && $pigcms_id && $keeper_info) {
            $contactArr = [
                'is_house' => 1,
                'type' => 'manager',
                'title' => L_("{$keeper_info['title']}"),
                'phone' => $keeper_info['phone'],
                'right_text' => $keeper_info['name'],
                'url' => $keeper_info['url'],
                'icon'=>$keeper_info['head'],
                'wx_contact' => $keeper_info['wx_contact'],
                'wx_config_id' => $keeper_info['wx_config_id'],
                'phone_icon' => $site_url .'/static/images/house/qywx/phone.png',
                'wx_icon' => $site_url .'/static/images/house/qywx/wx.png',
            ];
            if (isset($keeper_info['wx_contact']) && $keeper_info['wx_contact'] && isset($keeper_info['wx_config_id']) && $keeper_info['wx_config_id']) {
                $localUrl = "./runtime/wx_contact/" . $pigcms_id . '/' . $keeper_info['wx_config_id'] . '.png';
                if(!file_exists("./runtime/wx_contact/" . $pigcms_id)){
                    mkdir("./runtime/wx_contact/" . $pigcms_id,0777,true);
                }
                $wx_contact = str_replace('?.png', '', $keeper_info['wx_contact']);
                $put = @file_put_contents($localUrl, file_get_contents($wx_contact));
                if ($put) {
                    $contactArr['wx_contact'] = $site_url . "/runtime/wx_contact/" . $pigcms_id . '/' . $keeper_info['wx_config_id'] . '.png';
                }
            }
            $list[] = $contactArr;
        }
        elseif($this->_uid && $pigcms_id && $bind_user_info['floor_id']){
            $house_floor = D('House_village_floor')->where(array('floor_id'=>$bind_user_info['floor_id']))->find();
            $house_single = M('House_village_single')->where(array('id'=>$bind_user_info['single_id']))->find();
            if($house_floor['floor_keeper_name'] && $house_floor['floor_keeper_phone']){
                $list[] = [
                    'is_house' => 1,
                    'type' => 'manager',
                    'title' => L_("楼栋管家"),
                    'right_text' => $house_floor['floor_keeper_name']?$house_floor['floor_keeper_name']:'',
                    'phone' => $house_floor['floor_keeper_phone']?$house_floor['floor_keeper_phone']:'',
                    'url' => '',
                    'icon' => $house_floor['floor_keeper_head'] ? replace_file_domain($house_floor['floor_keeper_head']):$site_url . '/static/images/user_avatar.jpg',
                    'wx_contact' => '',
                    'wx_config_id' => '',
                    'phone_icon' => $site_url .'/static/images/house/qywx/phone.png',
                    'wx_icon' => $site_url .'/static/images/house/qywx/wx.png',
                ];
            }elseif($house_single['single_keeper_name'] && $house_single['single_keeper_phone']){
                $list[] = [
                    'is_house' => 1,
                    'type' => 'manager',
                    'title' => L_("楼栋管家"),
                    'right_text' => $house_single['single_keeper_name']?$house_single['single_keeper_name']:'',
                    'phone' => $house_single['single_keeper_phone']?$house_single['single_keeper_phone']:'',
                    'url' => '',
                    'icon' => $house_single['single_keeper_head'] ? replace_file_domain($house_single['single_keeper_head']):$site_url . '/static/images/user_avatar.jpg',
                    'wx_contact' => '',
                    'wx_config_id' => '',
                    'phone_icon' => $site_url .'/static/images/house/qywx/phone.png',
                    'wx_icon' => $site_url .'/static/images/house/qywx/wx.png',
                ];
            }
        }
        elseif($this->_uid && $pigcms_id && $bind_user_info['single_id']){
            $house_single = M('House_village_single')->where(array('id'=>$bind_user_info['single_id']))->find();
            if($house_single['single_keeper_name'] && $house_single['single_keeper_phone']){
                $list[] = [
                    'is_house' => 1,
                    'type' => 'manager',
                    'title' => L_("楼栋管家"),
                    'right_text' => $house_single['single_keeper_name']?$house_single['single_keeper_name']:'',
                    'phone' => $house_single['single_keeper_phone']?$house_single['single_keeper_phone']:'',
                    'url' => '',
                    'icon' => $house_single['single_keeper_head'] ? replace_file_domain($house_single['single_keeper_head']):$site_url. '/static/images/user_avatar.jpg',
                    'wx_contact' => '',
                    'wx_config_id' => '',
                    'phone_icon' => $site_url .'/static/images/house/qywx/phone.png',
                    'wx_icon' => $site_url .'/static/images/house/qywx/wx.png',
                ];
            }

        }
        $list[] = array(
            'type' => 'manager_phone',
            'is_house' => 0,
            'title'=>L_('物业电话'),
            'url'=>$this->_uid?$base_url.'pages/village/commonly_used/house_phone?village_id='.$bind_user_info['village_id'] . '&pigcms_id='.$pigcms_id:'',
            'icon' => $this->config['site_url'].'/static/images/house/my_house_phone.png?t=03'
        );

        // 营山域名限制
        if ($this->hasCloudIntercom()){
            $list[] = array(
                'type' => 'non-motor_vehicle',
                'is_house' => 0,
                'title'=>L_('非机动车卡管理'),
                'url'=>$this->_uid?$base_url.'pages/houseMeter/BicycleLane/BicycleLane':'',
                'icon' => $this->config['site_url'].'/static/images/house/my_nmv_card.png?t=03'
            );
        }

        // 获取客服联系路径
        if ($worker_info) {
            $kf = M('Merchant_store_kefu')->where(array('belong' => 'village', 'store_id' => $village_id, 'bind_uid' => $worker_info['wid']))->find();
            if ($village_id && $now_village['has_kefu']==1 && $kf && $pigcms_id) {
                $kfUrl = build_store_im_conversation_url($kf['username'], 'village2villageBind');
                $list[] = array(
                    'type' => 'my_kefu',
                    'is_house' => 1,
                    'title'=>'我是客服',
                    'url'=>$kfUrl,
                    'icon' => $this->config['site_url'].'/static/images/house/my_kefu.png?t=03'
                );
            }
        }
        $open_user_spread= $this->config['open_user_spread'] ? intval($this->config['open_user_spread']):0;
        if ($open_user_spread>0 && $bind_user_info && 4==$bind_user_info['type']) {
            $list[] = [
                'type' => 'extension_qrcode',
                'is_house' => 1,
                'title' => L_('推广二维码'),
                'url' => $base_url . 'pages/village/my_pages/invitationCode',
                'icon' => $site_url . '/static/images/house/invitation.png?t=03'
            ];
        }

        //todo 新增装修申请
        $list[] = array(
            'type' => 'renovation_apple',
            'is_house' => 0,
            'title'=>L_('装修申请'),
            'url'=>$this->_uid?$base_url.'pages/village/materialDiy/materialList?village_id='.$bind_user_info['village_id'] . '&pigcms_id='.$pigcms_id:'',
            'icon' => $this->config['site_url'].'/static/images/house/zhuangxiu.png?t=03'
        );

        $venue_reservation_switch_judge = D('Config_customization')->getVenueReservationSwitch();
        $area_street_reservation_service_switch_judge = D('Config_customization')->getAreaStreetReservationServiceSwitch();
        if($venue_reservation_switch_judge || $area_street_reservation_service_switch_judge){
            $source_type=0;
            if($area_street_reservation_service_switch_judge){
                $source_type=3;
            }
            //todo 我的消息
            $list[] = array(
                'type' => 'msg_apple',
                'is_house' => 0,
                'title'=>L_('我的消息'),
                'url'=>$this->_uid ? $base_url.'pages/village/materialDiy/myMsg?village_id='.$bind_user_info['village_id'] . '&pigcms_id='.$pigcms_id.'&source_type='.$source_type : '',
                'icon' => $this->config['site_url'].'/static/images/house/msg.png?t=03'
            );
        }
        if($venue_reservation_switch_judge){
            //todo 我的收藏
            $list[] = array(
                'type' => 'venue_my_collect',
                'is_house' => 0,
                'title'=>L_('我的收藏'),
                'url'=>$this->_uid?$base_url.'pages/houseMeter/venuerese/mycollect?village_id='.$bind_user_info['village_id'] . '&pigcms_id='.$pigcms_id:'',
                'icon' => $this->config['site_url'].'/static/images/venue/collect.png?t=03'
            );
        }

        $unitRentalArr=array('id'=>'112041','module'=>'UnitRental','status'=>1,'show'=>1);
        $unitRental=M('House_menu_new')->where($unitRentalArr)->find();
        $res['have_unit_rental']=0;
        if($unitRental){
            $whereArr=array('from'=>'house','from_id'=>$village_id,'template_type'=>array('in',array(1,2,3)));
            $tmpArr=M('Plugin_material_diy_template')->where($whereArr)->field('template_type')->find();
            if(!empty($tmpArr)){
                $list[] = array(
                    'type' => 'house_public_rental',
                    'is_house' => 0,
                    'title'=>L_('公租房申请记录'),
                    'url'=>$base_url.'pages/houseManage/pages/checkoutView',
                    'icon' => $this->config['site_url'].'/static/images/house/house_public_rental_yuyue.png?t=03'
                );
            }
        }
        

        $whereArr=array('bind_id'=>$pigcms_id);
        $user_label=D('House_village_user_label')->where($whereArr)->field('*')->find();
        if(!empty($user_label)){
            //todo 我的党员
            $list[] = array(
                'type' => 'party_member',
                'is_house' => 0,
                'title'=>L_('我的党员'),
                'url'=>$base_url.'pages/street/index/partyDetail?village_id='.$bind_user_info['village_id'].'&pigcms_id='.$pigcms_id,
                'icon' => $this->config['site_url'].'/static/images/house/party_member.png'
            );
        }
        
        if(isset($this->config['open_bd_spread']) && $this->config['open_bd_spread']==1){
            $bd_info = M("Bd")->where(['uid'=>$this->_uid, 'is_del' => 0, 'status' => 1])->find();
            if($bd_info){
                $url = $base_url . 'pages/my/myextension/mine_extension';
                $list[] = array(
                    'type' => 'bd_spread',
                    'is_house' => 0,
                    'title'=>L_('我推广的商家'),
                    'icon'=> $this->config['site_url'].'/static/images/my_new/bd.png', /*图片暂定为 26*26的像素 */
                    'url'=> $url,
                );
            }
        }
        
        if(C('customization.sqaeondelightjiangsu_customized') && C('config.privacy_policy') && C('config.register_agreement')){
            if(C('config.personal_information_collection')){
                $list[] = array(
                    'type' => 'bd_spread',
                    'is_house' => 0,
                    'title'=>L_('个人信息收集清单'),
                    'icon'=> $this->config['site_url'].'/static/images/my_new/personal_information_collection.png', /*图片暂定为 26*26的像素 */
                    'url'=> $this->config['site_url'] . '/wap.php?c=Login&a=personal_information_collection',
                );
            }
            $list[] = array(
                'type' => 'bd_spread',
                'is_house' => 0,
                'title'=>L_('隐私政策'),
                'icon'=> $this->config['site_url'].'/static/images/my_new/user_privacy_policy.png', /*图片暂定为 26*26的像素 */
                'url'=> $this->config['site_url'] . '/wap.php?c=Login&a=user_privacy_policy',
            );
            $list[] = array(
                'type' => 'bd_spread',
                'is_house' => 0,
                'title'=>L_('用户注册协议'),
                'icon'=> $this->config['site_url'].'/static/images/my_new/register_agreement.png', /*图片暂定为 26*26的像素 */
                'url'=> $this->config['site_url'] . '/wap.php?c=Login&a=register_agreement',
            );
        }
        
        if (!empty($list)) {
            $arr['list'] = $list;
        }


        if($now_village){
            $property = M('house_property')->where(array('id'=>$now_village['property_id']))->find();
            if($property['is_config_show'] == 1){
                $arr['footer_link'] = array(
                    'image_path' => $site_url.'/static/images/house/fastwhale-logo.png',
                    'url' => $site_url.'/static/pigcms_company/fastwhale/index.html',
                    'content'=>'快鲸智慧社区提供技术支持',
                    'title'=>'快鲸智慧社区',
                    'site_icp'=>C('config.site_icp'),
                    'site_icp_url'=>'https://beian.miit.gov.cn/'
                );

            }else{
                $arr['footer_link'] = null;
            }
        }else{
            $arr['footer_link'] = null;
        }

        // 环球 屏蔽小程序和app里面的个别功能
        $user_link_hide = false;
        /*if($this->hasYiLvBao()){
            $user_link_hide = true;
            $arr['user_link'] = [];
            // 滚动消息
            $arr['scroll_msg'] = [];
            $arr['scroll_image'] = '';
        }*/
        $arr['user_link_show'] = $user_link_hide;

        $this->returnCode(0,$arr);
    }


    //	查询是否绑定了当前小区
    protected function get_user_village_info($bind_id,$village_id,$uid, $is_my=false){
        $database_house_village_user_bind = D('House_village_user_bind');
        $bind_user_info = $database_house_village_user_bind->get_one_by_bindId($bind_id);
        if(empty($bind_user_info)){
            if ($is_my) {
                $this->returnCode('20120002');
            } else {
                $this->returnCode('20120004');
            }
        }
        $where['parent_id|pigcms_id'] = $bind_id;
        $where['uid'] = $uid;
        $where['type'] = ['in', [0,1,2,3,4]];
        $where['status'] = ['not in', [0,3,4]];
        $where['village_id'] = $village_id;
        $house_village_user_bind_count = $database_house_village_user_bind->where($where)->count();
        if(!$house_village_user_bind_count){
            if ($is_my) {
                $this->returnCode('20120002');
            } else {
                $this->returnCode('20120004');
            }
        }
        return $bind_user_info;
    }

    public function getBindUserStatus() {
        $pigcms_id = I('pigcms_id',0);
        if ($pigcms_id) {
            $database_house_village_user_bind = D('House_village_user_bind');
            $bind_user_info = $database_house_village_user_bind->get_one_by_bindId($pigcms_id);
            if(empty($bind_user_info)){
                $this->returnCode('20120002');
            }
            $arr['pigcms_id'] = $pigcms_id;
        } else {
            $arr['pigcms_id'] = $pigcms_id;
        }
        $this->returnCode(0,$arr);
    }

    public function  getUnlockVillages(){
        import('@.ORG.aihorseUnlockApi');
        $phone='';
        if($this->_uid && $this->_uid>0){
            $whereArr=array('uid'=>$this->_uid);
            $now_user = D('User')->getUser($whereArr,'*');
            if($now_user && $now_user['phone']){
                $phone=$now_user['phone'];
            }
        }
        if(empty($phone)){
            $this->returnCodeError('手机号不存在！');
        }
        $village_id	=	I('village_id',0,'intval');
        $aihorseUnlockApi=new aihorseUnlockApi($village_id);
        $apiRet=$aihorseUnlockApi->getUserVillageList($phone);
        $apiRet['uid']=$this->_uid;
        $apiRet['phone']=$phone;
        $this->returnCode(0,$apiRet);
    }

    public function  setUnlockUserDefaultVillage(){
        import('@.ORG.aihorseUnlockApi');
        $phone='';
        if($this->_uid && $this->_uid>0){
            $whereArr=array('uid'=>$this->_uid);
            $now_user = D('User')->getUser($whereArr,'*');
            if($now_user && $now_user['phone']){
                $phone=$now_user['phone'];
            }
        }
        $store_id = I('store_id',0,'intval');
        if(empty($phone)){
            $this->returnCodeError('手机号不存在！');
        }
        $village_id	=	I('village_id',0,'intval');
        if($store_id<1){
            $this->returnCodeError('缺少开锁小区ID参数！');
        }
        $aihorseUnlockApi=new aihorseUnlockApi($village_id);
        $apiRet=$aihorseUnlockApi->setUserDefaultVillage($phone,$store_id);
        $apiRet['uid']=$this->_uid;
        $apiRet['phone']=$phone;
        $apiRet['store_id']=$store_id;
        $this->returnCode(0,$apiRet);
    }

    public function  getDefaultAccessEwmCode(){
        import('@.ORG.aihorseUnlockApi');
        $phone='';
        if($this->_uid && $this->_uid>0){
            $whereArr=array('uid'=>$this->_uid);
            $now_user = D('User')->getUser($whereArr,'*');
            if($now_user && $now_user['phone']){
                $phone=$now_user['phone'];
            }
        }
        if(empty($phone)){
            $this->returnCodeError('手机号不存在！');
        }
        $village_id	=	I('village_id',0,'intval');
        $aihorseUnlockApi=new aihorseUnlockApi($village_id);
        $apiRet=$aihorseUnlockApi->getDefaultAccessEwmCode($phone,$this->config['site_url']);
        $apiRet['uid']=$this->_uid;
        $apiRet['phone']=$phone;
        $this->returnCode(0,$apiRet);
    }
}