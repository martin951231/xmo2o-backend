<?php
class HouseAction  extends BaseAction{
    protected $village_bind;
    public $pay_list_type = array(
        'property'=>'物业费',
        'water'=>'水费',
        'electric'=>'电费',
        'gas'=>'燃气费',
        'park'=>'停车费',
        'custom'=>'其他缴费',
    );

    protected $send_time_type = array('分钟', '小时', '天', '周', '月');

    // 手机app 判定当前用户是否为小区绑定用户
    public function check_village_session($village_id, $info, $pigcms_id = 0){
        if(!empty($village_id)){
            $where['uid'] =  $info['uid'];
            $where['village_id'] =  $village_id;
            // 房间
            if ($pigcms_id) {
                $where['pigcms_id'] =  $pigcms_id;
            }
            $House_village_user_bindModel = D('House_village_user_bind');
            $bind_village_list = $House_village_user_bindModel->field(true)->where($where)->order('`pigcms_id` DESC')->select();

            if(!$bind_village_list){
                $bind_village_list = $House_village_user_bindModel->where(array('uid' => $info['uid'], 'village_id' => $village_id , 'parent_id' => array('neq',0)))->order('`pigcms_id` DESC')->select();
                if(!$bind_village_list){
                    $bind_village_list = $House_village_user_bindModel->field(true)->where(array('uid' => $bind_village_list[0]['parent_id'], 'village_id' => $village_id))->order('`pigcms_id` DESC')->select();
                }
            }
            if(!empty($bind_village_list)){
                if(count($bind_village_list) == 1){
                    return $bind_village_list[0];
                }else{
                    return array();
                }
            }
        }else{
            return array();
        }
    }

    public function config(){
        $config = M('Appapi_app_config')->select();
        foreach ($config as $v) {
            if ($v['var'] == 'village_android_v') {
                $arr['android_version'] = $v['value'];
            } elseif ($v['var'] == 'village_android_url') {
                $arr['android_downurl'] = $v['value'];
            } elseif ($v['var'] == 'village_android_vcode') {
                $arr['android_version_code'] = $v['value'];
            } elseif ($v['var'] == 'village_android_vdesc') {
                $arr['android_version_version_desc'] = $v['value'];
            }
        }
    }


    //	小区列表
    public function index(){
        $long_lat	=	array(
            'lat'	=>	I('lat',0),
            'long'	=>	I('long',0),
        );
        $city	=	I('city',C('config.now_city'));
        C('config.now_city',$city);
        $keyword	=	I('keyword');
        $_GET['page']	=	I('page',1);
        $House_village	=	D('House_village');
        //	查询社区列表
        $aHouseVillageList	=	$House_village->wap_get_list($long_lat,$keyword);
        $arr	=	array();
        if($aHouseVillageList){
            foreach($aHouseVillageList['village_list'] as $k=>$v){
                $arr['village_list'][$k]['village_id']	=	$v['village_id'];
                $arr['village_list'][$k]['village_name']	=	$v['village_name'];
                $arr['village_list'][$k]['village_address']	=	$v['village_address'];
                $arr['village_list'][$k]['range']	=	$v['range'];
                $arr['village_list'][$k]['market_url'] = $this->config['site_url'] . '/wap.php?g=Wap&c=Housemarket&a=index&village_id=' . $v['village_id'];
                // 社区团购链接
                $header_id = $v['header_id'] ? $v['header_id'] : $this->config['house_village_group_headerId'];
                $exist = M('Village_group_header')->where(array('id'=>$header_id))->find();
                $header_id = $exist ? $header_id : 0;
                $arr['village_list'][$k]['village_group_url'] = $this->config['site_url'] . '/wap.php?g=Wap&c=Villagr_group&a=index&leader_id=' . $header_id;
                
                if ($this->config['open_house_logo_show']) {
                    $arr['village_list'][$k]['village_logo'] =  $v['village_logo'];
                    $property_phone = explode(' ',$v['property_phone']);
                    $arr['village_list'][$k]['property_phone'] =  $property_phone[0];
                    $arr['village_list'][$k]['isverify'] =  $v['isverify'];
                    $arr['village_list'][$k]['property_name'] =  $v['property_name'];
                }
            }
            $arr['totalPage']	=	$aHouseVillageList['totalPage'];
            $arr['village_count']	=	$aHouseVillageList['village_count'];
        }else{
            $arr['village_list']	=	array();
            $arr['totalPage']		=	0;
            $arr['village_count']	=	0;
        }
        if($keyword){
            $arr['village_me']	=	array();
        }else{
            $ticket	=	I('ticket');
            $info = ticket::get($ticket,$this->DEVICE_ID,true);
            if($info){
                //	查询我居住的社区列表
                $bindList	=	$House_village->get_bind_list($info['uid'],'',true,$long_lat);
                $village_ids = array();
                if($bindList){
                    foreach($bindList as $k=>$v){
                        $arr['village_me'][$k]['village_id']	=	$v['village_id'];
                        $arr['village_me'][$k]['village_name']	=	$v['village_name'];
                        $arr['village_me'][$k]['village_address']	=	$v['village_address'];
                        $arr['village_me'][$k]['range']			=	$v['range'];
                        $arr['village_me'][$k]['market_url'] = $this->config['site_url'] . '/wap.php?g=Wap&c=Housemarket&a=index&village_id=' . $v['village_id'];
                        // 社区团购链接
                        $header_id = $v['header_id'] ? $v['header_id'] : $this->config['house_village_group_headerId'];
                        $exist = M('Village_group_header')->where(array('id'=>$header_id))->find();
                        $header_id = $exist ? $header_id : 0;
                        $arr['village_me'][$k]['village_group_url'] = $this->config['site_url'] . '/wap.php?g=Wap&c=Villagr_group&a=index&leader_id=' . $header_id;

                        if ($this->config['open_house_logo_show']) {
                            $arr['village_me'][$k]['village_logo'] =   $v['village_logo'];
                            $property_phone = explode(' ',$v['property_phone']);
                            $arr['village_me'][$k]['property_phone'] =  $property_phone[0];
                            $arr['village_me'][$k]['isverify'] =  $v['isverify'];
                            $arr['village_me'][$k]['property_name'] =  $v['property_name'];
                        }
                        $village_ids[] = $v['village_id'];
                    }
                }else{
                    $arr['village_me']	=	array();
                }

                $database_house_village_user_bind = D('House_village_user_bind');
                $database_house_village = D('House_village');
                $now_user =  D('User')->get_user($info['uid']);
                $where['phone'] = $now_user['phone'];
                $where['parent_id'] = array('neq',0);
                $where['status'] = array('eq',1); //2017-05-09
                $parent_id_arr = $database_house_village_user_bind->where($where)->getField('pigcms_id,parent_id');
                if(!empty($parent_id_arr)){
                    $Map['pigcms_id'] = array('in' , $parent_id_arr);
                    $Map['uid'] = array('gt' , 0);
                    $bind_family_list = $database_house_village->get_bind_family_list($Map);
                    foreach($bind_family_list as $v){

                        if(!in_array($v['village_id'],$village_ids)){
                            $tmp['range'] = getRange(getDistance($v['lat'],$v['long'],$long_lat['lat'],$long_lat['long']));
                            $tmp['village_id'] = $v['village_id'];
                            $tmp['village_name'] = $v['village_name'];
                            $tmp['village_address'] = $v['village_address'];
                            $tmp['market_url'] = $this->config['site_url'] . '/wap.php?g=Wap&c=Housemarket&a=index&village_id=' . $v['village_id'];
                            // 社区团购链接
                            $header_id = $v['header_id'] ? $v['header_id'] : $this->config['house_village_group_headerId'];
                            $exist = M('Village_group_header')->where(array('id'=>$header_id))->find();
                            $header_id = $exist ? $header_id : 0;
                            $tmp['village_group_url'] = $this->config['site_url'] . '/wap.php?g=Wap&c=Villagr_group&a=index&leader_id=' . $header_id;

                            if ($this->config['open_house_logo_show']) {
                                $tmp['village_logo'] =  $v['village_logo'];
                                $property_phone = explode(' ',$v['property_phone']);
                                $tmp['property_phone'] =  $property_phone[0];
                                $tmp['isverify'] =  $v['isverify'];
                                $tmp['property_name'] =  $v['property_name'];
                            }
                            $arr['village_me'][]=$tmp;
                        }
                    }
                }
            }else{
                $arr['village_me']	=	array();
            }
        }
        $this->returnCode(0,$arr);
    }
    //	我的小区列表
    public function bind_list(){
        $long_lat	=	array(
            'lat'	=>	I('lat',0),
            'long'	=>	I('long',0),
        );
        $House_village	=	D('House_village');
        $ticket	=	I('ticket');
        $info = ticket::get($ticket, $this->DEVICE_ID, true);
        $bindList	=	$House_village->get_bind_list($info['uid'],'',true,$long_lat);
        $arr	=	array();
        if($bindList){
            foreach($bindList as $k=>$v){
                $arr[$k]['village_id']	=	$v['village_id'];
                $arr[$k]['village_name']	=	$v['village_name'];
                $arr[$k]['village_address']	=	$v['village_address'];
                $arr[$k]['range']		=	$v['range'];
            }
        }
        $this->returnCode(0,$arr);
    }
    //	我的小区里的房子
    public function village_list(){
        if($_POST['app_version']>=500){
            $this->village_list_new();
        }else{
            $this->village_list_old();
        }
    }

    // 我的小区里的房子
    public function village_list_old(){
        $ticket =   I('ticket');
        if(empty($ticket)){
            $this->returnCode('20044013');
        }
        $info = ticket::get($ticket,$this->DEVICE_ID,true);
        if(empty($info)){
            $this->returnCode('20000009');
        }
        $village_id =   I('village_id');
        if(empty($village_id)){
            $this->returnCode('30000001');
        }
        $this->get_village($village_id);
        $House_village  =   D('House_village_user_bind');
        $village_list   =   $House_village->get_user_bind_list($info['uid'],$village_id);
        $arr    =   array();
        if($village_list){
            foreach($village_list as $k=>$v){
                $arr[$k]    =   array(
                    'pigcms_id' =>  $v['pigcms_id'],
                    'address'   =>  $v['address'],
                    'usernum'   =>  $v['usernum'],
                    'parent_id' =>  $v['parent_id'],
                );
            }
        }else{
            $village_list   =   $House_village->get_family_user_bind_list($info['uid'],$village_id);
            foreach($village_list as $k=>$v){
                $arr[$k]    =   array(
                    'pigcms_id' =>  $v['pigcms_id'],
                    'address'   =>  $v['address'],
                    'usernum'   =>  $v['usernum'],
                    'parent_id' =>$v['parent_id']
                );
            }
        }
        $this->returnCode(0,$arr);
    }

    // 我的小区里的房子
    public function village_list_new(){
        $ticket =   I('ticket');
        if(empty($ticket)){
            $this->returnCode('20044013');
        }
        $info = ticket::get($ticket,$this->DEVICE_ID,true);
        if(empty($info)){
            $this->returnCode('20000009');
        }
        $village_id =   I('village_id');
        if(empty($village_id)){
            $this->returnCode('30000001');
        }
        $this->get_village($village_id);
        $House_village  =   D('House_village_user_bind');
        $village_list   =   $House_village->get_user_bind_list($info['uid'],$village_id);
        $arr    =   array(
            'bind' => array(),
            'url' => ''
        );
        if($village_list){
            foreach($village_list as $k=>$v){
                $arr['bind'][$k]    =   array(
                    'pigcms_id' =>  $v['pigcms_id'],
                    'address'   =>  $v['address'],
                    'usernum'   =>  $v['usernum'],
                    'parent_id' =>  $v['parent_id'],
                );
            }
        }else{
            $village_list   =   $House_village->get_family_user_bind_list($info['uid'],$village_id);
            foreach($village_list as $k=>$v){
                $arr['bind'][$k]    =   array(
                    'pigcms_id' =>  $v['pigcms_id'],
                    'address'   =>  $v['address'],
                    'usernum'   =>  $v['usernum'],
                    'parent_id' =>$v['parent_id']
                );
            }
            if(empty($village_list)){
                // 查询是否存在申请中的数据
                $where['village_id'] = $village_id;
                $where['is_del'] = 0;
                $where['uid'] = $info['uid'];
                $result = D('House_village_user_vacancy')->where($where)->find();
               
                $pigcms_type    =   I('pigcms_type',1);
                if($result){
                    $arr['url']  =   $this->config['site_url'].U('Wap/House/my_village_list');
                    if($pigcms_type == 1){
                        $arr['url']   =   str_replace('appapi.php','wap.php',$arr['url']);
                    }else if($pigcms_type == 2){
                        $arr['url']   =   str_replace('appapi.php','wap_house.php',$arr['url']);
                    }
                }
            }
        }
        $this->returnCode(0,$arr);
    }
    //	社区--首页
    public  function village_index(){
        if($_POST['app_version']>=150){
            $this->village_new();
        }else{
            $this->village_old();
        }
    }

    public function village_old(){
        $village_id	=	I('village_id');
        $pigcms_type	=	I('pigcms_type',1);
        if(empty($village_id)){
            $this->returnCode('30000001');
        }

        $database_shequ_slider = D('House_village_slider');
        $now_village = $this->get_village($village_id);
        $has_slide = $this->getHasConfig($now_village['village_id'],'has_slide');
        if($has_slide){
            //幻灯片
            $where['village_id'] = $now_village['village_id'];
            $where['status'] = '1';
            $where['type'] = '0';
            $slider_list = $database_shequ_slider->where($where)->order('`sort` DESC,`id` ASC')->select();
            if($slider_list){
                foreach($slider_list as $k=>$v){
                    $slider[$k]['id'] 	= $v['id'];
                    $slider[$k]['name'] = $v['name'];
                    $slider[$k]['pic'] = file_domain().'/upload/slider/'.$v['pic'];
                    $slider[$k]['url'] = $v['url'];
                }
            }else{
                $slider	=	array();
            }
        }else{
            $slider	=	array();
        }
        $arr['slider']	=	$slider;
        //找到模板排序
        $displayArr = explode(' ',$this->config['house_display']);
        $displayTplArr = array(
            1=>'village_index_news',
            2=>'village_index_pay',
            3=>'village_index_group',
            4=>'village_index_meal',
            5=>'village_index_appoint',
            6=>'village_index_bbs',
        );
        $displayIncludeTplArr = array();
        foreach($displayArr as $value){
            if($value>=1 && $value<=6){
                $displayIncludeTplArr[] = $displayTplArr[$value];
            }
        }
        $arr['sort']	=	$displayArr;
        $long_lat	=	array(
            'lat'	=>	I('lat'),
            'long'	=>	I('long'),
        );
        $user_long_lat = D('User_long_lat')->getLocation(1,0,$long_lat);
        foreach($displayIncludeTplArr as $v){
            if($v == 'village_index_news'){
                $news = D('House_village_news')->get_limit_list($now_village['village_id'],2);
                if($news){
                    foreach($news as $kk=>$vv){
                        $new_url	=	$this->config['site_url'].U('Wap/House/village_news',array('village_id'=>$village_id,'news_id'=>$vv['news_id']));
                        if($pigcms_type == 1){
                            $new_url	=	str_replace('/appapi.php?','/wap.php?',$new_url);
                        }else if($pigcms_type == 2){
                            $new_url	=	str_replace('/appapi.php?','/wap_house.php?',$new_url);
                        }
                        unset($news[$kk]['status'],$news[$kk]['is_hot'],$news[$kk]['cat_id'],$news[$kk]['is_notice'],$news[$kk]['content']);
                        $news[$kk]['add_time_s']	=	date('Y-m-d H:i',$vv['add_time']);
                        $news[$kk]['url']	=	$new_url;
                    }
                    $arr['news']['list']	=	$news;
                    $news_url	=	$this->config['site_url'].U('Wap/House/village_newslist',array('village_id'=>$village_id));
                    $news_url	=	str_replace('/appapi.php?','/wap_house.php?',$news_url);
                    if($pigcms_type == 1){
                        $news_url	=	str_replace('/appapi.php?','/wap.php?',$news_url);
                    }else if($pigcms_type == 2){
                        $news_url	=	str_replace('/appapi.php?','/wap_house.php?',$news_url);
                    }
                    $arr['news']['news_url']	=	$news_url;
                }else{
                    $arr['news']['list']	=	array();
                    $arr['news']['news_url']	=	'';
                }
                $arr['news']['sort']	=	'1';
            }
            if($v == 'village_index_pay'){
                if($this->config['house_bbsservice_limit']){
                    $category	= D('House_service_category')->getIndexCatList($now_village['village_id'],$this->config['house_bbsservice_limit']);
                }else{
                    $category	= D('House_service_category')->getIndexCatList($now_village['village_id'],16);
                }
                if($category){
                    foreach($category as $kk=>$vv){
                        $cat_url	=	substr($vv['cat_url'],0,3);
                        if($cat_url != 'htt'){
                            $category[$kk]['cat_url']	=	$this->config['site_url'].$vv['cat_url'];
                            if($pigcms_type == 1){
                                $category[$kk]['cat_url']	=	str_replace('/appapi.php?','/wap.php?',$category[$kk]['cat_url']);
                                $category[$kk]['cat_url']	=	str_replace('Appapi','Wap',$category[$kk]['cat_url']);
                            }else if($pigcms_type == 2){
                                $category[$kk]['cat_url']	=	str_replace('/appapi.php?','/wap_house.php?',$category[$kk]['cat_url']);
                                $category[$kk]['cat_url']	=	str_replace('Appapi','Wap',$category[$kk]['cat_url']);
                            }

                        }
                    }
                    $arr['category']['list']	=	$category;
                }else{
                    $arr['category']['list']	=	array();
                }
                $arr['category']['sort']	=	'2';
            }
            if($v == 'village_index_group'){
                $group = D('House_village_group')->get_limit_list($now_village['village_id'],3,$user_long_lat);
                if($group){
                    foreach($group as $kk=>$vv){
                        $group_url	=	$this->config['site_url'].$vv['url'];
                        if($pigcms_type == 1){
                            $group_url	=	str_replace('/appapi.php?','/wap.php?',$group_url);
                            $group_url	=	str_replace('Appapi','Wap',$group_url);
                        }else if($pigcms_type == 2){
                            $group_url	=	str_replace('/appapi.php?','/wap_house.php?',$group_url);
                            $group_url	=	str_replace('Appapi','Wap',$group_url);
                        }
                        $group_url	=	str_replace('g=Appapi','g=Wap',$group_url);
                        $group_list[$kk]	=	array(
                            'group_id'	=>	$vv['group_id'],
                            'group_name'	=>	$vv['group_name'],
                            'prefix_title'	=>	$vv['prefix_title'],
                            'price'			=>	$vv['price'],
                            'wx_cheap'		=>	$vv['wx_cheap'],
                            'sale_count'	=>	$vv['sale_count'],
                            'list_pic'		=>	$vv['list_pic'],
                            'range'			=>	isset($vv['range'])?$vv['range']:'',
                            'url'			=>	$group_url,
                            'pin_num'			=>	$vv['pin_num'],
                        );
                        if($vv['tuan_type'] == 2){
                            $group_list[$kk]['name']	=	$vv['s_name'];
                        }else{
                            $group_list[$kk]['name']	=	$vv['name'];
                        }
                    }
                    $arr['group']['list']		=	$group_list;
                    $list_url	=	$this->config['site_url'].U('Wap/House/village_grouplist',array('village_id'=>$village_id));
                    if($pigcms_type == 1){
                        $list_url	=	str_replace('/appapi.php?','/wap.php?',$list_url);
                        $list_url	=	str_replace('Appapi','Wap',$list_url);
                    }else if($pigcms_type == 2){
                        $list_url	=	str_replace('/appapi.php?','/wap_house.php?',$list_url);
                        $list_url	=	str_replace('Appapi','Wap',$list_url);
                    }
                    $arr['group']['list_url']	=	$list_url;
                }else{
                    $arr['group']['list']	=	array();
                    $arr['group']['list_url']	=	'';
                }
                $arr['group']['sort']	=	'3';
            }
            if($v == 'village_index_meal'){
                $meal = D('House_village_meal')->get_limit_list($now_village['village_id'],3,$user_long_lat);
                if($meal){
                    foreach($meal as $kk=>$vv){
                        $meal_url	=	$this->config['site_url'].$vv['wap_url'];
                        if($pigcms_type == 1){
                            $meal_url	=	str_replace('/appapi.php?','/wap.php?',$meal_url);
                            $meal_url	=	str_replace('Appapi','Wap',$meal_url);
                        }else if($pigcms_type == 2){
                            $meal_url	=	str_replace('/appapi.php?','/wap_house.php?',$meal_url);
                            $meal_url	=	str_replace('Appapi','Wap',$meal_url);
                        }
                        $meal_url	=	str_replace('g=Appapi','g=Wap',$meal_url);
                        $meal_list[$kk]	=	array(
                            'name'			=>	$vv['name'],
                            'adress'		=>	$vv['adress'],
                            'mean_money'	=>	$vv['mean_money'],
                            'sale_count'	=>	$vv['sale_count'],
                            'store_type'	=>	$vv['store_type'],
                            'list_pic'		=>	$vv['list_pic'],
                            'range'			=>	isset($vv['range'])?$vv['range']:'',
                            'state'			=>	isset($vv['state'])?$vv['state']:'',
                            'wap_url'		=>	$meal_url,
                        );
                    }
                    $arr['meal']['list']	=	$meal_list;
                    $list_url	=	$this->config['site_url'].U('village_meallist',array('village_id'=>$village_id));
                    if($pigcms_type == 1){
                        $list_url	=	str_replace('/appapi.php?','/wap.php?',$list_url);
                        $list_url	=	str_replace('Appapi','Wap',$list_url);
                    }else if($pigcms_type == 2){
                        $list_url	=	str_replace('/appapi.php?','/wap_house.php?',$list_url);
                        $list_url	=	str_replace('Appapi','Wap',$list_url);
                    }
                    $arr['meal']['list_url']=	$list_url;
                }else{
                    $arr['meal']['list']	=	array();
                    $arr['meal']['list_url']	=	'';
                }
                $arr['meal']['sort']	=	'4';
            }
            if($v == 'village_index_appoint'){
                $appoint = D('House_village_appoint')->get_limit_list($now_village['village_id'],3,$user_long_lat);
                if($appoint){
                    foreach($appoint as $kk=>$vv){
                        $appoint_url	=	$this->config['site_url'].$vv['url'];
                        if($pigcms_type == 1){
                            $appoint_url	=	str_replace('appapi.php?','wap.php?',$appoint_url);
                            $appoint_url	=	str_replace('Appapi','Wap',$appoint_url);
                        }else if($pigcms_type == 2){
                            $appoint_url	=	str_replace('appapi.php?','wap_house.php?',$appoint_url);
                            $appoint_url	=	str_replace('Appapi','Wap',$appoint_url);
                        }
                        $appoint_url	=	str_replace('g=Appapi','g=Wap',$appoint_url);
                        $appoint_list[$kk]	=	array(
                            'appoint_name'	=>	$vv['appoint_name'],
                            'payment_money'	=>	$vv['payment_money'],
                            'appoint_content'	=>	$vv['appoint_content'],
                            'appoint_sum'	=>	$vv['appoint_sum'],
                            'list_pic'		=>	$vv['list_pic'],
                            'range'			=>	isset($vv['range'])?$vv['range']:'',
                            'appoint_status'=>	$vv['appoint_status'],
                            'url'	=>	$appoint_url,
                        );
                    }
                    $arr['appoint']['list']	=	$appoint_list;
                    $list_url	=	$this->config['site_url'].U('village_appointlist',array('village_id'=>$village_id));
                    if($pigcms_type == 1){
                        $list_url	=	str_replace('/appapi.php?','/wap.php?',$list_url);
                        $list_url	=	str_replace('Appapi','Wap',$list_url);
                    }else if($pigcms_type == 2){
                        $list_url	=	str_replace('/appapi.php?','/wap_house.php?',$list_url);
                        $list_url	=	str_replace('Appapi','Wap',$list_url);
                    }
                    $arr['appoint']['list_url']	=	$list_url;
                }else{
                    $arr['appoint']['list']	=	array();
                    $arr['appoint']['list_url']	=	'';
                }
                $arr['appoint']['sort']	=	'5';
            }
            if($v == 'village_index_bbs'){
                $bbs = D('Bbs')->bbsHotAricle('house',$now_village['village_id'],$this->config['house_bbsarticle_limit']);
                if($bbs){
                    foreach($bbs['aricle'] as $kk=>$vv){
                        if($pigcms_type == 1){
                            $bbs_url	=	str_replace('wap.php?','wap.php?',$vv['url']);
                            $bbs_url	=	str_replace('Appapi','Wap',$vv['url']);
                        }else if($pigcms_type == 2){
                            $bbs_url	=	str_replace('wap.php?','wap_house.php?',$vv['url']);
                            $bbs_url	=	str_replace('Appapi','Wap',$vv['url']);
                        }
                        $aricle[$kk]	=	array(
                            'aricle_id'	=>	$vv['aricle_id'],
                            'aricle_img'	=>	$vv['aricle_img'],
                            'aricle_title'	=>	$vv['aricle_title'],
                            'aricle_praise_num'		=>	$vv['aricle_praise_num'],
                            'aricle_comment_num'		=>	$vv['aricle_comment_num'],
                            'url'		=>	$bbs_url,
                            'update_time'	=>	date('Y-m-d H:i',$vv['update_time']),
                        );
                    }
                    $arr['bbs']['aricle']	=	$aricle;
                    if($pigcms_type == 1){
                        $arr['bbs']['bbs_url']	=	str_replace('wap.php?','wap.php?',$bbs['bbs_url']);
                        $arr['bbs']['bbs_url']	=	str_replace('Appapi','Wap',$bbs['bbs_url']);
                    }else if($pigcms_type == 2){
                        $arr['bbs']['bbs_url']	=	str_replace('wap.php?','wap_house.php?',$bbs['bbs_url']);
                        $arr['bbs']['bbs_url']	=	str_replace('Appapi','Wap',$bbs['bbs_url']);
                    }
                }else{
                    $arr['bbs']['aricle']	=	array();
                    $arr['bbs']['bbs_url']	=	'';
                }
                $arr['bbs']['sort']	=	'6';
            }
        }
        if(empty($arr['news'])){
            $arr['news']	=	array(
                'list'	=>	array(),
                'news_url'	=>	'',
                'sort'	=>	'1',
            );
        }
        if(empty($arr['category'])){
            $arr['category']	=	array(
                'list'	=>	array(),
                'sort'	=>	'2',
            );
        }
        if(empty($arr['group'])){
            $arr['group']	=	array(
                'list'	=>	array(),
                'list_url'	=>	'',
                'sort'	=>	'3',
            );
        }
        if(empty($arr['meal'])){
            $arr['meal']	=	array(
                'list'	=>	array(),
                'list_url'	=>	'',
                'sort'	=>	'4',
            );
        }
        if(empty($arr['appoint'])){
            $arr['appoint']	=	array(
                'list'	=>	array(),
                'list_url'	=>	'',
                'sort'	=>	'5',
            );
        }
        if(empty($arr['bbs'])){
            $arr['bbs']	=	array(
                'aricle'	=>	array(),
                'bbs_url'	=>	'',
                'sort'	=>	'6',
            );
        }
        $this->returnCode(0,$arr);
    }

    public function village_new(){
        //$ticket	=	I('ticket');
        //$info = ticket::get($ticket, $this->DEVICE_ID, true);
        //if(empty($info)){
        //	$this->returnCode('20000009');
        //}
        //$now_user = D('User')->get_user($info['uid']);
        if(!$_POST['village_id']){
            $this->returnCode('20090003');
        }
        $ticket	=	I('ticket');
        $info = ticket::get($ticket, $this->DEVICE_ID, true);
        $database_shequ_slider = D('House_village_slider');

        $now_village = $this->get_village($_POST['village_id']);

        $slider_list = array();
        if($now_village['has_slide']){
            //幻灯片
            $where['village_id'] = $now_village['village_id'];
            $where['status'] = '1';
            $where['type'] = '0';
            $slider_list = $database_shequ_slider->where($where)->order('`sort` DESC,`id` ASC')->select();
            if(!empty($slider_list)){
                foreach($slider_list as $k=>$v){

                    $slider_list[$k]['url'] = $v['url'];
                    $slider_list[$k]['pic'] =  file_domain().'/upload/slider/'.$slider_list[$k]['pic'];
                }
            }else{
                $slider_list = array();
            }


            $arr['silider_list'] = $slider_list;
        }

        //首页是否显示快店

        $arr['has_index_store']=!empty($now_village['has_index_store'])?$now_village['has_index_store']:'';

        // 首页是否处于游客
        $tourist = $this->getHasConfig($now_village['village_id'],'tourist');
        // 返回值 is_house_bind  0: 游客状态 1:　需要绑定 2： 跳转路径 is_redirect 数据
        if ($tourist != 1) {
            $bind_info = $this->check_village_session($_POST['village_id'], $info,$_POST['pigcms_id']);
            if (!empty($bind_info)) {
                if ($bind_info['status'] != 1) {
                    $arr['is_house_bind'] = 2;
                    $arr['is_redirect'] = $this->config['site_url'] .'/wap.php?g=Wap&c=House&a=my_village_list';
                } else {
                    $arr['is_house_bind'] = 0; //游客
                    $arr['is_redirect'] = '';
                }
            } else {
                $arr['is_house_bind'] = 1; //绑定
                $arr['is_redirect'] = '';
            }
        } else {
            $arr['is_house_bind'] = 0; //游客
            $arr['is_redirect'] = '';
        }

        //5条最新新闻
        $news_list = D('House_village_news')->get_limit_list($now_village['village_id'],3);
        foreach ($news_list as &$nv) {
            $nv['add_time'] = date('m/d H:i',$nv['add_time']);
            $nv['content'] = '';
        }
        $arr['news_list']=empty($news_list)?array():$news_list;
        $arr['news_url']=$this->config['site_url'] .'/wap.php?g=Wap&c=House&a=village_newslist&village_id='.$_POST['village_id'];
        $arr['activity_listurl']=$this->config['site_url'] .'/wap.php?g=Wap&c=House&a=village_activitylist&village_id='.$_POST['village_id'];
        //}

        //if(in_array('2',$displayArr)){
        if($now_village["has_index_nav"]){
            $nav_where = [];
            $nav_where['type'] = 0;
            $nav_where['village_id'] = $now_village['village_id'];
            $nav_where['status'] = 1;

            if($this->config['house_bbsservice_limit']){
                $tmp_index_service_cat_list = D('House_village_nav')->house_village_nav_page_list($nav_where,true,'sort desc',20);
            }else{
                $tmp_index_service_cat_list = D('House_village_nav')->house_village_nav_page_list($nav_where,true,'sort desc',99999);
            }

            $index_service_cat_list = array();
            foreach($tmp_index_service_cat_list['result']['list'] as $value){
                $value['img'] = file_domain().'/upload/service/'.$value['img'];
                $value['url'] = html_entity_decode($value['url']);
                //$tmp_i = floor($key/8);
                $index_service_cat_list[] = $value;
            }
        }else{
            $index_service_cat_list = array(
                array(
                    'img'=>$this->config['site_url']."/tpl/Wap/pure/static/img/house_index_2.png",
                    'url'=>$this->config['site_url'].U('Wap/House/village_manager_list',array('village_id'=>$now_village['village_id'])),
                    'name'=>$this->config['house_name'].L_("管家"),
                ),
                array(
                    'img'=>$this->config['site_url']."/tpl/Wap/pure/static/img/house_index_5.png",
                    'url'=>$this->config['site_url'].U('Wap/House/village_my_pay',array('village_id'=>$now_village['village_id'])),
                    'name'=>L_("生活缴费"),
                ),
                array(
                    'img'=>$this->config['site_url']."/tpl/Wap/pure/static/img/house_index_4.png",
                    'url'=>$this->config['site_url'].U('Wap/Ride/ride_list',array('village_id'=>$now_village['village_id'])),
                    'name'=>$this->config['house_name'].L_("用车"),
                ),
                array(
                    'img'=>$this->config['site_url']."/tpl/Wap/pure/static/img/house_index_1.png",
                    'url'=>$this->config['site_url'].U('Wap/Library/express_service_list',array('village_id'=>$now_village['village_id'],'app_no_header'=>1)),
                    'name'=>L_("快递代收"),
                ),
                array(
                    'img'=>$this->config['site_url']."/tpl/Wap/pure/static/img/house_index_3.png",
                    'url'=>$this->config['site_url'].U('Wap/House/village_activitylist',array('village_id'=>$now_village['village_id'])),
                    'name'=>$this->config['house_name'].L_("活动"),
                ),
                array(
                    'img'=>$this->config['site_url']."/tpl/Wap/pure/static/img/house_index_7.png",
                    'url'=>$this->config['site_url'].U('Wap/House/village_grouplist',array('village_id'=>$now_village['village_id'])),
                    'name'=>L_("周边").$this->config['group_alias_name'],
                ),
                array(
                    'img'=>$this->config['site_url']."/tpl/Wap/pure/static/img/house_index_6.png",
                    'url'=>$this->config['site_url'].U('Wap/House/shop',array('village_id'=>$now_village['village_id']."#cat-all")),
                    'name'=>L_("周边").$this->config['shop_alias_name'],
                ),
                array(
                    'img'=>$this->config['site_url']."/tpl/Wap/pure/static/img/house_index_8.png",
                    'url'=>$this->config['site_url'].U('Wap/House/village_more_list',array('village_id'=>$now_village['village_id'])),
                    'name'=>L_("更多"),
                ),
            );
        }
        $arr['index_service_cat_list']=$index_service_cat_list;

        //社区活动
        $database_house_village_activity = D('House_village_activity');
        $activity_where['status'] = 1;
        $activity_where['village_id'] = $_POST['village_id'] ;
        $activity_where['pic'] = array('neq' , '');
        $activity_list = $database_house_village_activity->house_village_activity_page_list($activity_where,true,'sort desc',3);
        $activity_list = $activity_list['list']['list'];
        foreach ($activity_list as &$v) {
            $tempImg = explode(';', $v['pic']);
            $tmp['pic'] = file_domain() . '/upload/activity/' . $tempImg[0];
            $tmp['url'] = $this->config['site_url'] . str_replace('appapi.php', 'wap.php', $v['url']);
            if ($v["apply_end_time"] + 86400 > time()) {
                $tmp['time_str'] = L_('截至') . date('Y/m/d', $v['apply_end_time']);
            } else {
                $tmp['time_str'] = L_('已截止');
            }
            $tmp['title'] = $v['title'];
            //$tmp['content'] = $v['content'];
            $tmp['apply_fee'] = $v['apply_fee'];
            $activity_list_tmp[] = $tmp;
        }
        $arr['activity_list']=$activity_list_tmp?$activity_list_tmp:array() ;
        //}

        $this->returnCode(0,$arr);
    }

    // 进入社区的判断
    public function village_tourist(){
        //$ticket	=	I('ticket');
        //$info = ticket::get($ticket, $this->DEVICE_ID, true);
        //if(empty($info)){
        //	$this->returnCode('20000009');
        //}
        //$now_user = D('User')->get_user($info['uid']);
        if(!$_POST['village_id']){
            $this->returnCode('20090003');
        }
        $ticket	=	I('ticket');
        $info = ticket::get($ticket, $this->DEVICE_ID, true);

        $now_village = $this->get_village($_POST['village_id']);

        // 首页是否处于游客
        $tourist = $this->getHasConfig($now_village['village_id'],'tourist');

        // 返回值 is_house_bind  0: 游客状态 1:　需要绑定 2： 跳转路径 is_redirect 数据
        if ($tourist != 1) {
            $bind_info = $this->check_village_session($_POST['village_id'], $info,$_POST['pigcms_id']);
            if (!empty($bind_info)) {
                if ($bind_info['status'] != 1) {
                    $arr['is_house_bind'] = 2;
                    $arr['is_redirect'] = $this->config['site_url'] .'/wap.php?g=Wap&c=House&a=my_village_list';
                } else {
                    $arr['is_house_bind'] = 0;
                    $arr['is_redirect'] = '';
                }
            } else {
                $arr['is_house_bind'] = 1;
                $arr['is_redirect'] = '';
            }
        } else {
            $arr['is_house_bind'] = 0;
            $arr['is_redirect'] = '';
        }

        $this->returnCode(0,$arr);
    }

    public function village_shop_list(){
        $now_village = $this->get_village($_POST['village_id']);
        $database_merchant_store_shop = D('Merchant_store_shop');
        $merchant_store_shop_where['lat'] = $now_village['lat'];
        $merchant_store_shop_where['long'] = $now_village['long'];
        $merchant_store_shop_lists = $database_merchant_store_shop->get_list_by_option($merchant_store_shop_where,3);
        // 同步微信端， 获取当前所在小区的前10条后台添加的店铺
        if ($_POST['village_id']) {
            $where = array('lat' => $now_village['lat'], 'long' => $now_village['long'],'village_id'=>$_POST['village_id']);
            $merchant_store_shop_lists = D('House_village_store')->get_list_by_option($where,10);
        }
        $merchant_store_shop_result = array();
        $deliver_type = 'all';
        $now_time = date('H:i:s');
        $return = array();
        foreach ($merchant_store_shop_lists['shop_list'] as $row) {
            $temp = array();
            $temp['store_id'] = $row['store_id'];
            $temp['name'] = $row['name'];
            $temp['store_theme'] = $row['store_theme'];
            $temp['isverify'] = $row['isverify'];
            $temp['juli_wx'] = $row['juli'];
            $temp['range'] = $row['range'];
            $temp['image'] = $this->config['site_url'].'/index.php?c=Image&a=thumb&width=180&height=120&url='.urlencode($row['image']);
            $temp['star'] = $row['score_mean'];
            $temp['month_sale_count'] = $row['sale_count'];
            $temp['delivery'] = $deliver_type == 'pick' ? 0 : $row['deliver'];//是否支持配送
            $temp['delivery'] = $temp['delivery'] ? true : false;
            $temp['delivery_time'] = $row['send_time'];//配送时长
            $temp['send_time_type'] = $row['send_time_type'];
            $temp['delivery_time_type'] = $this->send_time_type[$row['send_time_type']];
            $temp['delivery_price'] = floatval($row['basic_price']);//起送价
            $temp['delivery_money'] = floatval($row['delivery_fee']);//配送费
            $temp['delivery_system'] = $row['deliver_type'] == 0 || $row['deliver_type'] == 3 ? true : false;//是否是平台配送
            $temp['is_close'] = 1;

            if ($row['open_1'] == '00:00:00' && $row['close_1'] == '00:00:00') {
                $temp['time'] = '24小时营业';
                $temp['is_close'] = 0;
            } else {
                $temp['time'] = $row['open_1'] . '~' . $row['close_1'];
                if ($row['open_1'] < $now_time && $now_time < $row['close_1']) {
                    $temp['is_close'] = 0;
                }
                if ($row['open_2'] != '00:00:00' || $row['close_2'] != '00:00:00') {
                    $temp['time'] .= ',' . $row['open_2'] . '~' . $row['close_2'];
                    if ($row['open_2'] < $now_time && $now_time < $row['close_2']) {
                        $temp['is_close'] = 0;
                    }
                }
                if ($row['open_3'] != '00:00:00' || $row['close_3'] != '00:00:00') {
                    $temp['time'] .= ',' . $row['open_3'] . '~' . $row['close_3'];
                    if ($row['open_3'] < $now_time && $now_time < $row['close_3']) {
                        $temp['is_close'] = 0;
                    }
                }
            }

            $temp['coupon_list'] = array();
            if ($row['is_invoice']) {
                $temp['coupon_list']['invoice'] = floatval($row['invoice_price']);
            }
            if ($row['store_discount'] != 0 && $row['store_discount'] != 100) {
                $temp['coupon_list']['discount'] = $row['store_discount']/10;
            }
            $system_delivery = array();
            foreach ($row['system_discount'] as $row_d) {
                if ($row_d['type'] == 0) {//新单
                    $temp['coupon_list']['system_newuser'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']));
                } elseif ($row_d['type'] == 1) {//满减
                    $temp['coupon_list']['system_minus'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']));
                } elseif ($row_d['type'] == 2) {//配送
                    if ($row_d['full_money'] > 0 && $row_d['reduce_money'] > 0) {
                        $system_delivery[] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']));
                    }
                }
            }
            foreach ($row['merchant_discount'] as $row_m) {
                if ($row_m['type'] == 0) {
                    $temp['coupon_list']['newuser'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']));
                } elseif ($row_m['type'] == 1) {
                    $temp['coupon_list']['minus'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']));
                }
            }
            if ($row['deliver']) {
                if ($temp['delivery_system']) {
                    $system_delivery && $temp['coupon_list']['delivery'] = $system_delivery;
                } else {
                    if ($row['is_have_two_time']) {
                        if ($row['reach_delivery_fee_type2'] == 0) {
                            if ($row['basic_price'] > 0 && $row['delivery_fee2'] > 0) {
                                $temp['coupon_list']['delivery'][] = array('money' => floatval($row['basic_price']), 'minus' => floatval($row['delivery_fee2']));
                            }
                        } elseif ($row['reach_delivery_fee_type2'] == 1) {
                            //$temp['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
                        } elseif ($row['reach_delivery_fee_type2'] == 2) {
                            $row['delivery_fee2'] && $temp['coupon_list']['delivery'][] = array('money' => floatval($row['no_delivery_fee_value2']), 'minus' => floatval($row['delivery_fee2']));
                        }
                    } else {
                        if ($row['reach_delivery_fee_type'] == 0) {
                            if ($row['basic_price'] > 0 && $row['delivery_fee'] > 0) {
                                $temp['coupon_list']['delivery'][] = array('money' => floatval($row['basic_price']), 'minus' => floatval($row['delivery_fee']));
                            }
                        } elseif ($row['reach_delivery_fee_type'] == 1) {
                            //$temp['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
                        } elseif ($row['reach_delivery_fee_type'] == 2) {
                            $row['delivery_fee'] && $temp['coupon_list']['delivery'][] = array('money' => floatval($row['no_delivery_fee_value']), 'minus' => floatval($row['delivery_fee']));
                        }
                    }
                }
            }
            $temp['coupon_count'] = count($temp['coupon_list']);
            $temp['coupon_list'] = $this->parseCoupon($temp['coupon_list'],'array');
            $temp['has_index_store'] = $now_village['hax_index_store'];
            $temp['deliver_name'] = isset($this->config['deliver_name']) && !empty($this->config['deliver_name']) ? $this->config['deliver_name'] : L_('平台配送');
            $return[] = $temp;
        }

        $this->returnCode(0,$return);
    }

    //便民
    public function house_service_new(){
        $now_village = $this->get_village($_POST['village_id']);

        //$hot_cat_list = D('House_service_category')->getHotCatList($now_village['village_id'],6);
        //foreach ($hot_cat_list as &$v) {
        //	$v['cat_url'] = $this->config['site_url'].str_replace('appapi','wap',$v['cat_url']);
        //	$v['cat_url'] =str_replace('Appapi','Wap',$v['cat_url']);
        //}
        $where_category['_string'] = '(parent_id>0 AND is_index_show=1) OR (parent_id=0)';
        $cat_list = D('House_service_category')->getAllCatList($now_village['village_id'], $where_category);
        //if(!$cat_list){
        //$this->house_service_test();
        //$cat_list = D('House_service_category')->getAllCatList($now_village['village_id']);
        //}

        foreach ($cat_list as &$vv) {
            foreach ($vv['son_list'] as &$vvv) {
                $vvv['cat_img'] =  strpos($vvv['cat_img'],'tpl/Wap') !== false ? $this->config['site_url']."/".$vvv['cat_img'] : $vvv['cat_img'];
                if(empty($vvv['cat_url'])){
                    //$cat_url = $this->config['site_url'] . U('Wap/Houseservice/cat_list',array('village_id'=>$now_village['village_id'],'id'=>$vvv['id']));
                    $cat_url = U('Wap/Houseservice/cat_list',array('village_id'=>$now_village['village_id'],'id'=>$vvv['id']));
                    $vvv['cat_url'] = str_replace('appapi.php','wap.php',$cat_url);
                }


                //$vvv['cat_url'] = $this->config['site_url']."/".$vvv['cat_url'];
                $vvv['cat_url'] = strpos($vvv['cat_url'],'http')===0?$vvv['cat_url']:$this->config['site_url'].$vvv['cat_url'];
            }
            $tmp[] = $vv;
        }
        //$arr['hot_cat_lists']=$hot_cat_list;
        if(!empty($tmp)){
            $arr['cat_list']=$tmp;
        }else{
            $arr['cat_list']=array();
        }
        $this->returnCode(0,$arr);
    }

    private function get_village($village_id){
        $now_village = D('House_village')->get_one($village_id);
        if(empty($now_village)){
            $this->returnCode('20120001');
        }
        return $now_village;
    }
	
    private function getHasConfig($village_id,$field){
        $database_house_village = D('House_village');
        $house_village_info = $database_house_village->get_one($village_id,$field);
        $config_info = $house_village_info[$field];
        return $config_info;
    }
	
    //	社区--便民服务
    public function house_service(){
        if($_POST['app_version']>=150){
            $this->house_service_new();
        }else{
            $this->house_service_old();
        }
    }
	
    public function house_service_old(){
        $village_id	=	I('village_id');
        $pigcms_type	=	I('pigcms_type',1);
        if(empty($village_id)){
            $this->returnCode('30000001');
        }
        $now_village = $this->get_village($village_id);
        $hot_cat_list = D('House_service_category')->getHotCatList($now_village['village_id'],6);

        if($hot_cat_list){
            foreach($hot_cat_list as $k=>$v){
                $url	=	htmlspecialchars_decode($v['cat_url']);
                $url	=	substr($url,0,3);
                if($url != 'htt'){
                    $cat_url	=	$this->config['site_url'].$v['cat_url'];
                    $hot_cat_list[$k]['cat_url']	=	str_replace('appapi.php','wap.php',$cat_url);
                }
                if($pigcms_type == 2){
                    $hot_cat_list[$k]['cat_url']	=	str_replace('wap.php','wap_house.php',$url);
                }
            }
        }
		
        //幻灯片
        $has_service_slide = $this->getHasConfig($now_village['village_id'],'has_service_slide');
        if($has_service_slide){
            $where['village_id'] = $now_village['village_id'];
            $where['status'] = '1';
            $where['type'] = '1';
            $slider_list = M('House_village_slider')->where($where)->order('sort DESC,id ASC')->select();
            if($slider_list){
                foreach($slider_list as $k=>$v){
                    $url	=	htmlspecialchars_decode($v['url']);
                    if(stripos($url , 'LBS://')!==FALSE){
                        $url	=	wapLbsTranform($url);
                    }
                    if($pigcms_type == 2){
                        $url	=	str_replace('wap.php','wap_house.php',$url);
                    }
                    $slider[$k]['id']	=	$v['id'];
                    $slider[$k]['url']	=	$url;
                    $slider[$k]['name']	=	$v['name'];
                    $slider[$k]['pic']	=	file_domain().'/upload/service/'.$v['pic'];
                }
            }
        }
        $cat_list = D('House_service_category')->getAllCatList($now_village['village_id']);
        if($cat_list){
            foreach($cat_list as $k=>$v){
                if(is_array($v)){
                    foreach($v['son_list'] as $kk=>$vv){
                        if(empty($vv['cat_url'])){
                            $cat_url = $this->config['site_url'] . U('Wap/Houseservice/cat_list',array('village_id'=>$now_village['village_id'],'id'=>$vv['id']));
                            $vv['cat_url'] = str_replace('appapi.php','wap.php',$cat_url);
                        }
                        $vvUrl	=	htmlspecialchars_decode($vv['cat_url']);
                        if(stripos($vvUrl , 'LBS://')!==FALSE){
                            $vvUrl	=	wapLbsTranform($vvUrl);
                        }
                        if($pigcms_type == 2){
                            $vvUrl	=	str_replace('wap.php','wap_house.php',$vvUrl);
                        }
                        $cat[$k][]	=	array(
                            'cat_fname'	=>	$v['cat_name'],
                            'id'		=>	$vv['id'],
                            'cat_name'	=>	$vv['cat_name'],
                            'cat_url'	=>	$vvUrl,
                            'cat_img'	=>	$vv['cat_img'],
                        );
                    }
                }
            }
            foreach($cat as $v){
                $cat_lists[]	=	$v;
            }
            if(empty($cat_lists)){
                $cat_lists[]	=	array();
            }
        }
        $arr['slider_list']	=	isset($slider)?$slider:array();
        $arr['hot_cat_list']=	isset($hot_cat_list)?$hot_cat_list:array();
        $arr['cat_list']	=	isset($cat_lists)?$cat_lists:array();
        $this->returnCode(0,$arr);
    }
	
    //	社区--常用电话
    public function house_phone(){
        $village_id	=	I('village_id');
        if(empty($village_id)){
            $this->returnCode('30000001');
        }
        $now_village = $this->get_village($village_id);
        $phone_list = D('House_village_phone_category')->getAllCatPhoneList($now_village['village_id']);
        if(empty($phone_list)){
            $phone_list	=	array();
        }else{
            foreach($phone_list as $k=>$v){
                $tmp = array();
                foreach($v['phone_list'] as $kk=>$vv){
                    $tmp[$kk]=	$vv;
                    $tmp[$kk]['cat_id']	=	$v['cat_id'];
                    $tmp[$kk]['cat_name']	=	$v['cat_name'];
                }
                $arr['phone_list'][] = $tmp;
            }
        }
        $arr['phone']	=	array(
            'name'	=>	L_('物业服务中心'),
            'title'	=>	L_('拨打物业服务中心电话'),
            'phone'	=>	isset($now_village['property_phone'])?$now_village['property_phone']:'',
        );
        $this->returnCode(0,$arr);
    }
	
    //	社区--我的
    public  function village_my(){
        if($_POST['app_version']>=150){
            $this->village_my_new();
        }else{
            $this->village_my_old();

        }
    }

    public function village_my_old(){
        //判断用户是否属于本小区
        $village_id	=	I('village_id');
        if(empty($village_id)){
            $this->returnCode('30000001');
        }
        $ticket	=	I('ticket');
        $info = ticket::get($ticket, $this->DEVICE_ID, true);
        if(empty($info)){
            $this->returnCode('20000009');
        }

        $pigcms_type    =   I('pigcms_type',1);

        $pigcms_id	=	I('pigcms_id');
        if(empty($pigcms_id)){            
            $this->returnCode('20120004');            
        }

        $now_village = $this->get_village($village_id);
        $now_user_info = $this->get_user_village_info($pigcms_id,$village_id,$info['uid']);
        $now_user = D('User')->get_user($info['uid']);
        $arr['user']	=	array(
            'name'		=>	$now_user_info['name'],
            'usernum'	=>	$now_user_info['usernum'],
            'address'	=>	$now_user_info['address'],
            'avatar'	=>	$now_user['avatar'],
            'url'		=>	$this->config['site_url'].U('Wap/My/myinfo'),
        );
        if(empty($now_user['avatar'])){
            $arr['user']['avatar']	=	$this->config['site_url'].'/tpl/Wap/pure/static/images/pic-default.png';
        }
        if(empty($arr['user'])){
            $arr['user']	=	array();
        }else{
            if($pigcms_type == 1){
                $arr['user']['url']	=	str_replace('appapi.php','wap.php',$arr['user']['url']);
            }else if($pigcms_type == 2){
                $arr['user']['url']	=	str_replace('appapi.php','wap_house.php',$arr['user']['url']);
            }
        }
        if($arr['now_user_info']['parent_id'] == 0){
            $arr['village'][]	=	array(
                'url'	=>	$this->config['site_url'].U('Wap/House/village_my_bind_family_add',array('village_id'=>$now_village['village_id'])),
                'title'	=>	L_('绑定').(C('config.open_house_user_name')?L_('亲友'):L_('家属')),
                'img'	=>	$this->config['site_url'].'/static/images/wap_house/family.png',
            );
        }
        $arr['village'][]		=	array(
            'url'	=>	$this->config['site_url'].U('Wap/House/village_my_pay',array('village_id'=>$now_village['village_id'])),
            'title'	=>	$this->config['house_name'].L_('缴费'),
            'img'	=>	$this->config['site_url'].'/static/images/wap_house/fee.png',
        );
        $arr['village'][]	=	array(
            'url'	=>	$this->config['site_url'].U('Wap/House/village_my_repair',array('village_id'=>$now_village['village_id'])),
            'title'	=>	L_('在线报修'),
            'img'	=>	$this->config['site_url'].'/static/images/wap_house/repair.png',
        );
        $arr['village'][]=	array(
            'url'	=>	$this->config['site_url'].U('Wap/House/village_my_utilities',array('village_id'=>$now_village['village_id'])),
            'title'	=>	L_('水电煤上报'),
            'img'	=>	$this->config['site_url'].'/static/images/wap_house/newspaper.png',
        );
        $arr['service'][]	=	array(
            'url'	=>	$this->config['site_url'].U('Wap/My/group_order_list'),
            'title'	=>	L_('团购订单'),
            'img'	=>	$this->config['site_url'].'/static/images/wap_house/group.png',
        );
        $arr['service'][]		=	array(
            'url'	=>	$this->config['site_url'].U('Wap/My/appoint_order_list'),
            'title'	=>	L_('预约订单'),
            'img'	=>	$this->config['site_url'].'/static/images/wap_house/meal.png',
        );
        $arr['service'][]	=	array(
            'url'	=>	$this->config['site_url'].U('Wap/My/meal_order_list'),
            'title'	=>	L_('外卖订单'),
            'img'	=>	$this->config['site_url'].'/static/images/wap_house/appoint.png',
        );

        $arr['life'][]		=	array(
            'url'	=>	$this->config['site_url'].U('Wap/House/village_my_paylists',array('village_id'=>$now_village['village_id'])),
            'title'	=>	L_('缴费订单列表'),
            'img'	=>	$this->config['site_url'].'/static/images/wap_house/book.png',
        );
        $arr['life'][]		=	array(
            'url'	=>	$this->config['site_url'].U('Wap/House/village_my_repairlists',array('village_id'=>$now_village['village_id'])),
            'title'	=>	L_('在线报修列表'),
            'img'	=>	$this->config['site_url'].'/static/images/wap_house/life_repair.png',
        );
        $arr['life'][]	=	array(
            'url'	=>	$this->config['site_url'].U('Wap/House/village_my_utilitieslists',array('village_id'=>$now_village['village_id'])),
            'title'	=>	L_('水电煤上报列表'),
            'img'	=>	$this->config['site_url'].'/static/images/wap_house/life_newspaper.png',
        );
        if($arr['now_user_info']['parent_id'] == 0){
            $arr['life'][]	=	array(
                'url'	=>	$this->config['site_url'].U('Wap/House/village_my_bind_family_list',array('village_id'=>$now_village['village_id'])),
                'title'	=>	L_('绑定').(C('config.open_house_user_name')?L_('亲友'):L_('家属')).L_('列表'),
                'img'	=>	$this->config['site_url'].'/static/images/wap_house/parent_id.png',
            );
        }
        $arr['life'][]	=	array(
            'url'	=>	$this->config['site_url'].U('Wap/Library/express_service_list',array('village_id'=>$now_village['village_id'])),
            'title'	=>	L_('快递代收'),
            'img'	=>	$this->config['site_url'].'/static/images/wap_house/pass.png',
        );
        $arr['life'][]	=	array(
            'url'	=>	$this->config['site_url'].U('Wap/Library/visitor_list',array('village_id'=>$now_village['village_id'])),
            'title'	=>	L_('访客登记'),
            'img'	=>	$this->config['site_url'].'/static/images/wap_house/interview.png',
        );
        $arr['interaction'][]	=	array(
            'url'	=>	$this->config['site_url'].U('Wap/House/village_my_suggest',array('village_id'=>$now_village['village_id'])),
            'title'	=>	L_('投诉建议'),
            'img'	=>	$this->config['site_url'].'/static/images/wap_house/suggest.png',
        );
        foreach($arr['village'] as &$v){
            if($pigcms_type == 1){
                $v['url']	=	str_replace('appapi.php','wap.php',$v['url']);
            }else if($pigcms_type == 2){
                $v['url']	=	str_replace('appapi.php','wap_house.php',$v['url']);
            }
        }
        foreach($arr['service'] as &$v){
            if($pigcms_type == 1){
                $v['url']	=	str_replace('appapi.php','wap.php',$v['url']);
            }else if($pigcms_type == 2){
                $v['url']	=	str_replace('appapi.php','wap_house.php',$v['url']);
            }
        }
        foreach($arr['life'] as &$v){
            if($pigcms_type == 1){
                $v['url']	=	str_replace('appapi.php','wap.php',$v['url']);
            }else if($pigcms_type == 2){
                $v['url']	=	str_replace('appapi.php','wap_house.php',$v['url']);
            }
        }
        foreach($arr['interaction'] as &$v){
            if($pigcms_type == 1){
                $v['url']	=	str_replace('appapi.php','wap.php',$v['url']);
            }else if($pigcms_type == 2){
                $v['url']	=	str_replace('appapi.php','wap_house.php',$v['url']);
            }
        }
        if($pigcms_type == 1){
            $array	=	array('list'=>array($arr['village'],$arr['life'],$arr['service'],$arr['interaction']),'user'=>$arr['user']);
        }else{
            $array	=	array('list'=>array($arr['village'],$arr['service'],$arr['life'],$arr['interaction']),'user'=>$arr['user']);
        }
        $this->returnCode(0,$array);
    }

    public function village_my_new(){
        //判断用户是否属于本小区
        $village_id	=	I('village_id');
        if(empty($village_id)){
            $this->returnCode('30000001');
        }
        $ticket	=	I('ticket');
        $info = ticket::get($ticket, $this->DEVICE_ID, true);
        if(empty($info)){
            $this->returnCode('20000009');
        }

        $pigcms_type    =   I('pigcms_type',1);

        $pigcms_id	=	I('pigcms_id');
        if(empty($pigcms_id)){        
            $this->returnCode('20120004');
        }

        $now_village = $this->get_village($village_id);
        $now_user_info = $this->get_user_village_info($pigcms_id,$village_id,$info['uid']);
        $now_user = D('User')->get_user($info['uid']);
        $arr['user'] = array(
            'name' => $now_user_info['name'],
            'usernum' => $now_user_info['usernum'],
            'address' => $now_user_info['address'],
            'type' => $now_user_info['type'],
            'memo' => $now_user_info['memo'],
            'avatar' => $now_user['avatar'],
            'url' => $this->config['site_url'] . U('Wap/My/myinfo'),
            'money' => $now_user['now_money'], // 金额
            'score' => $now_user['score_count'], // 积分
            'level' => $now_user['level'], // 等级
            'my_money_url' => $this->config['site_url'] . U('Wap/My/my_money'), // 我的钱包链接
            'img' => $this->config['site_url'] . '/tpl/Wap/pure/static/images/money.png'
        );
        if(empty($now_user['avatar'])){
            $arr['user']['avatar']	=	$this->config['site_url'].'/tpl/Wap/pure/static/images/pic-default.png';
        }
        if(empty($arr['user'])){
            $arr['user']	=	array();
        }else{
            if($pigcms_type == 1){
                $arr['user']['url']	=	str_replace('appapi.php','wap.php',$arr['user']['url']);
            }else if($pigcms_type == 2){
                $arr['user']['url']	=	str_replace('appapi.php','wap_house.php',$arr['user']['url']);
            }
        }
        /*if($arr['now_user_info']['parent_id'] == 0){
            $arr['village'][]	=	array(
                    'url'	=>	$this->config['site_url'].U('Wap/House/village_my_bind_family_add',array('village_id'=>$now_village['village_id'])),
                    'title'	=>	L_('绑定').(C('config.open_house_user_name')?L_('亲友'):L_('家属')).'',
                    'img'	=>	$this->config['site_url'].'/static/images/wap_house/family.png',
            );
        }*/
        //我的小区 生活缴费 我的订单 收货地址  我的帖子 积分商城 快递代收 访客登记 物业报修 投诉建议
        $arr['village'][]		=	array(
            'url'	=>	$this->config['site_url'].U('Wap/House/my_village_list'),
            'title'	=>	L_('我的').$this->config['house_name'],
            'img'	=>	$this->config['site_url'].'/tpl/Wap/pure/static/images/house.png',
        );
        $arr['village'][]		=	array(
            'url'	=>	$this->config['site_url'].U('Wap/House/village_my_paylists',array('village_id'=>$now_village['village_id'])),
            'title'	=>	L_('生活缴费'),
            'img'	=>	$this->config['site_url'].'/tpl/Wap/pure/static/images/fee.png',
        );
        $arr['service'][]		=	array(
            'url'	=>	$this->config['site_url'].U('Wap/House/order_list'),
            'title'	=>	L_('我的订单'),
            'img'	=>	$this->config['site_url'].'/tpl/Wap/pure/static/images/order.png',
        );
        $arr['service'][]		=	array(
            'url'	=>	$this->config['site_url'].U('Wap/My/adress'),
            'title'	=>	L_('收货地址'),
            'img'	=>	$this->config['site_url'].'/tpl/Wap/pure/static/images/address.png',
        );
        $arr['service'][]		=	array(
            'url'	=>	$this->config['site_url'].U('Wap/Bbs/my_bbs_list',array('village_id'=>$now_village['village_id'])),
            'title'	=>	L_('我的帖子'),
            'img'	=>	$this->config['site_url'].'/tpl/Wap/pure/static/images/bbs.png',
        );
        $arr['service'][]		=	array(
            'url'	=>	$this->config['site_url'].U('Wap/Gift/index',array('village_id'=>$now_village['village_id'])),
            'title'	=>	L_('积分商城'),
            'img'	=>	$this->config['site_url'].'/tpl/Wap/pure/static/images/gift.png',
        );
        $arr['service'][]		=	array(
            'url'	=>	$this->config['site_url'].U('Wap/Library/express_service_list',array('village_id'=>$now_village['village_id'])),
            'title'	=>	L_('快递代收'),
            'img'	=>	$this->config['site_url'].'/tpl/Wap/pure/static/images/kuaidi.png',
        );
        $arr['service'][]		=	array(
            'url'	=>	$this->config['site_url'].U('Wap/Library/visitor_list',array('village_id'=>$now_village['village_id'])),
            'title'	=>	L_('访客登记'),
            'img'	=>	$this->config['site_url'].'/tpl/Wap/pure/static/images/visitor.png',
        );
        if ($this->config['house_village_temporary_visitor'] == 1 && $now_village['temporary_visitor_switch'] == 1 && $now_user_info['type'] != 4) {
            $arr['service'][]		=	array(
                'url'	=>	$this->config['site_url'].U('Wap/Library/visitor_list_open',array('village_id'=>$now_village['village_id'])),
                'title'	=>	L_('智能访客开门'),
                'img'	=>	$this->config['site_url'].'/tpl/Wap/pure/static/images/visility_home.png',
            );
        }
        $arr['service'][]       =   array(
            'url'   =>  $this->config['site_url'].U('Wap/House/village_my_repairlists',array('village_id'=>$now_village['village_id'])),
            'title' =>  L_('物业报修'),
            'img'   =>  $this->config['site_url'].'/tpl/Wap/pure/static/images/baoxiu2.png',
        );
        $arr['service'][]		=	array(
            'url'	=>	$this->config['site_url'].U('Wap/House/village_my_waste_recovery',array('village_id'=>$now_village['village_id'])),
            'title'	=>	L_('废品回收'),
            'img'	=>	$this->config['site_url'].'/tpl/Wap/pure/static/images/fphs.png',
        );
        $arr['service'][]		=	array(
            'url'	=>	$this->config['site_url'].U('Wap/House/village_my_suggestlist',array('village_id'=>$now_village['village_id'])),
            'title'	=>	L_('投诉建议'),
            'img'	=>	$this->config['site_url'].'/tpl/Wap/pure/static/images/tousu.png',
        );

        foreach($arr['village'] as &$v){
            if($pigcms_type == 1){
                $v['url']	=	str_replace('appapi.php','wap.php',$v['url']);
            }else if($pigcms_type == 2){
                $v['url']	=	str_replace('appapi.php','wap_house.php',$v['url']);
            }
        }
        foreach($arr['service'] as &$v){
            if($pigcms_type == 1){
                $v['url']	=	str_replace('appapi.php','wap.php',$v['url']);
            }else if($pigcms_type == 2){
                $v['url']	=	str_replace('appapi.php','wap_house.php',$v['url']);
            }
        }

        $array	=	array('list'=>array($arr['village'],$arr['service']),'user'=>$arr['user']);

        $this->returnCode(0,$array);
    }

    //搜索小区附近
    public function lbs_search(){
        $keyword = I('keyword');
        $village_id = I('village_id');
        $city_id = I('city_id');
        if(empty($keyword)){
            $this->returnCode('20090012');
        }
        if(empty($village_id)){
            $this->returnCode('30000001');
        }
        if(empty($city_id)){
            $this->returnCode('20090002');
        }
        $now_city = D('Area')->field(true)->where(array('area_id' => $city_id))->find();
        $database_house_village = D('House_village');
        $now_village = $database_house_village->get_one($village_id);
        if(empty($now_village)){
            $this->returnCode('20090005');
        }
        $this->assign('now_village',$now_village);
        $url = 'http://api.map.baidu.com/place/v2/search?query='.urlencode($keyword).'&page_size=9999&page_num=0&scope=1&location=&location='.$now_village['lat'].','.$now_village['long'].'&radius=2000&output=json&ak=' . $this->config['baidu_map_ak'];
        import('ORG.Net.Http');
        $http = new Http();
        $results = $http->curlGet($url);
        $return = array();
        if($results){
            $results = json_decode($results,true);
            if($results['status'] == 0 && $results['results']){
                $return = array();
                foreach($results['results'] as $value){
                    if(!isset($value['location']['lat']) && !isset($value['location']['lng'])){
                        continue;
                    }
                    $url = wapLbsTranform("LBS://".$value['location']['lng'].",".$value['location']['lat'],array('title'=>$value['name'],'village_id'=>$village_id),true);
                    $return['list'][] = array(
                        'name'=>$value['name'],
                        'lat'=>$value['location']['lat'],
                        'long'=>$value['location']['lng'],
                        'address'=>$value['address'],
                        'url'=>$url['url']
                    );
                }
            }
            $return['count'] = count($return['list']);
        }

        $this->returnCode(0,$return);
    }


    //	查询是否绑定了当前小区
    protected function get_user_village_info($bind_id,$village_id,$uid){
        $now_user_info = D('House_village_user_bind')->get_one_by_bindId($bind_id);
        if(empty($now_user_info)){
            $this->returnCode('20120003');
        }
        $database_house_village_user_bind = D('House_village_user_bind');
        $where['parent_id|pigcms_id'] = $bind_id;
        $where['uid'] = $uid;
        $where['village_id'] = $village_id;
        $house_village_user_bind_count = $database_house_village_user_bind->where($where)->count();
        if(!$house_village_user_bind_count){
            $this->returnCode('20120002');
        }
        return $now_user_info;
    }

    public function parseCoupon($obj,$type){
        $returnObj = array();
        foreach($obj as $key=>$value){
            if($key=='invoice'){
                $returnObj[$key] = L_('满X1支持开发票，请在下单时填写发票抬头',C('config.Currency_symbol') . $obj[$key]);
            }else if($key=='discount'){
                $returnObj[$key] = L_('店内全场X1折',$obj[$key]);
            }else{
                $returnObj[$key] = [];
                foreach($obj[$key] as $k=>$v){
                    if ($key == 'delivery')  {
                        $returnObj[$key][] = L_('商品满X1,配送费减X2',array('X1'=>C('config.Currency_symbol') . $obj[$key][$k]['money'],'X2'=>C('config.Currency_symbol') . $obj[$key][$k]['minus']));
                    } else {
                        $returnObj[$key][] = L_('满X1减X2',array('X1'=>C('config.Currency_symbol') . $obj[$key][$k]['money'],'X2'=>C('config.Currency_symbol') . $obj[$key][$k]['minus']));
                    }
                }
            }
        }

        $textObj = array();
        foreach($returnObj as $key=>$value){
            if($key=='invoice' || $key=='discount'){
                $textObj[$key] = $value;
            }else{
                switch($key){
                    case 'system_newuser':
                        $textObj[$key] = L_('平台首单').implode(',',$value);
                        break;
                    case 'system_minus':
                        $textObj[$key] = L_('平台优惠').implode(',',$value);
                        break;
                    case 'newuser':
                        $textObj[$key] = L_('店铺首单').implode(',',$value);
                        break;
                    case 'minus':
                        $textObj[$key] = L_('店铺优惠').implode(',',$value);
                        break;
                    case 'system_minus':
                        $textObj[$key] = L_('平台优惠').implode(',',$value);
                        break;
                    case 'delivery':
                        $textObj[$key] = implode(',',$value);
                        break;
                }
            }
        }
        if($type == 'text'){
            $tmpObj = array();
            foreach($textObj as $key=>$value){
                $tmpObj[] = $value;
            }
            return implode(';',$tmpObj);
        }else{
            $returnObj = array();
            foreach($textObj as $key=>$value){
                $returnObj[] = array(
                    'type'=>$key,
                    'value'=>$value
                );
            }
            return $returnObj;
        }
    }



    public function header_json(){
        header('Content-type: application/json');
    }


    public function house_village_door_share_info() {
        $door_share_id	= I('door_share_id');
        if(empty($door_share_id)){
            $this->returnCode('60000003');
        }
        $where = array();
        $where['share_status'] = 0;
        $where['door_share_id'] = $door_share_id;
        $list = M('House_village_door_share')->where($where)->find();
        if (!empty($list) && $list['share_info']) {
            $share_info = $list['share_info'] = unserialize($list['share_info']);
            if (!empty($share_info)) {
                foreach ($share_info as $key =>$val) {
                    $condition_door['door_status'] = 1;
                    $condition_door['door_id'] = $val;
                    $aDoorList	=	M('House_village_door')->distinct(true)->field(true)->where($condition_door)->select();
                    $floor_id = reset($aDoorList)['floor_id'];
                    $data = $list['share_info'][$key] =	M('House_village_floor')->field(array('floor_name','floor_layer', 'floor_id'))->where(array('floor_id'=>$floor_id))->find();
                    if (!empty($aDoorList)) {
                        $aDoorList = reset($aDoorList);
                        $list['share_info'][$key]['floor_id'] = $aDoorList['floor_id'];
                        $list['share_info'][$key]['door_psword'] = $aDoorList['door_psword'];
                        $list['share_info'][$key]['door_id'] = $aDoorList['door_id'];
                        $list['share_info'][$key]['door_device_id'] = $aDoorList['door_device_id'];
                        $list['share_info'][$key]['door_name'] = $aDoorList['door_name'];
                    }
                    if(empty($data) && $aDoorList['floor_id'] == '-1') {
                        $list['share_info'][$key]['floor_name'] = $this->config['house_name'] ? $this->config['house_name'] : L_('小区');
                        $list['share_info'][$key]['floor_layer'] = L_('大门');
                    }
                }
                $list['share_info'] = $this->wxapp_door($list['share_info']);
            }
            // 获取小区信息
            $list['village_name'] = M('House_village')->where(array('village_id' => $list['village_id']))->getField('village_name');

            $this->returnCode(0,$list);
        } else {
            $this->returnCode(0,array());
        }
    }

    // 获取门禁密码
    private function wxapp_door($aDoor){
        foreach ($aDoor as &$item) {
            $lockDate = S($item['door_psword'].$item['door_device_id']);

            if(empty($lockDate) && $this->DEVICE_ID=='wxapp'){
                $return_msg = file_get_contents(($this->config['wxapp_door_server'] ? : 'http://o2o-service-qcloud.pigcms.com:8080/') . "dhpkgcomm/app/wxlockData?DEVICEPSW={$item['door_psword']}&DEVICE_ID={$item['door_device_id']}");
                $return_msg = json_decode($return_msg,true);
                $item['lockData'] = $return_msg['lockData'];
                S($item['door_psword'].$item['door_device_id'],$item['lockData'],86400);
            }else{
                $item['lockData']  = $lockDate;
            }
        }
        return $aDoor;
    }


    // 访客开门-优化
    // 获取用户 所有可开门禁权限
    public function user_visitor_open_list()
    {
        if(empty($this->_uid)){
            $this->returnCode('20000009');
        }
        $uid = $this->_uid;
        // 单独处理首页传值 index  即首页获取  list 列表获取
        $open_type = $_POST['open_type'] ? $_POST['open_type'] : 'list';
        $face_device_type = isset($_POST['face_device_type'])?$_POST['face_device_type']:0;

        //自动绑定一次
        $now_user = D('User')->get_user($uid);
        if($now_user['phone']){
            D('House_village_user_bind')->bind($now_user['uid'],$now_user['phone']);
        }
            
        $m_house_worker = M('House_worker');
        $m_house_village_door_user = M('House_village_door_user');
        $m_house_village_floor = M('House_village_floor');
        $m_house_village_door = M('House_village_door');
        $m_house_village_user_bind = M('House_village_user_bind');
        // 人脸识别门禁A1和A2
        $d_house_face_device = D('House_face_device');

        $where['a.uid'] = $uid;
        $where['a.status'] = 1;
        $where['b.status'] = 1;
        $aUserSelect = $m_house_village_user_bind->alias('a')
            ->join('LEFT JOIN pigcms_house_village as b on b.village_id=a.village_id')
            ->group('a.pigcms_id')
            ->field(array('a.pigcms_id','a.vacancy_id','a.authentication_field','a.id_card','a.parent_id','a.type','a.name','a.village_id', 'a.floor_id', 'a.property_price', 'a.property_endtime', 'a.phone','a.open_door_day', 'b.village_name', 'b.village_address', 'b.owe_property_open_door', 'b.owe_property_open_door_day'))
            ->where($where)
            ->order('a.type DESC')
            ->select();

        // 处理数据-层次为  小区-门禁-具体信息
        $open_list_info = array();
        // 查询出来的门禁信息存储
        $door_list_info = array();
        // 查询出来的单元信息存储
        $floor_list_info = array();
        // 查询出来的可以开门的信息存储  其中蓝牙关键字 以 ly_ + 蓝牙门禁id  人脸门禁未 face_ + 人脸门禁id
        $aDoor_is_open = array();
        // 存储信息
        $bind_info = array();
        // 是否有蓝牙门禁
        $is_ly = false;
        // 是否有人脸门禁
        $is_face = false;

        $aDoor = array();

        // 楼栋名称缓存
        $single_name_arr = [];
        $db_house_village_single = M('House_village_single');
        // 公共区域直接获取
        $db_house_village_public_area = M('House_village_public_area');
        $house_village_public_area = $db_house_village_public_area->getField('public_area_id,public_area_name');

        $authentication_field_arr = [];
        $id_card_arr = [];
        $db_house_village_nmv_card = M('House_village_nmv_card');
        $nmv_card_arr = [];

        
        if (!empty($aUserSelect)) {
            $village_ids = array_diff(array_unique(array_column($aUserSelect, 'village_id')), [0, '0', null]);
            $whereChannel = [
                'isDel' => 0,
                'device_type' => 'house_face_device',
                'village_id' => ['in', $village_ids]
            ];
            $deviceChannels = M('House_device_channel')->where($whereChannel)->select();
            $deviceChannelArr = [];
            foreach ($deviceChannels as $channel) {
                !isset($deviceChannelArr[$channel['device_id']]) && $deviceChannelArr[$channel['device_id']] = [];
                $deviceChannelArr[$channel['device_id']][] = $channel;
            }
            unset($channel);
            $condition_face_door = [];
            $condition_face_door['is_del'] = 0;
            $condition_door['village_id'] = ['in', $village_ids];
            $aFaceDoorList = $d_house_face_device->field(true)->where($condition_face_door)->order('add_time DESC, device_id DESC')->select();
            foreach ($aFaceDoorList as $door2) {
                $is_face = true;
                !isset($door_list_info[$door2['village_id']]) && $door_list_info[$door2['village_id']] = [];
                if (isset($deviceChannelArr[$door2['device_id']]) && $deviceChannelArr[$door2['device_id']]) {
                    if (count($deviceChannelArr[$door2['device_id']]) == 1) {
                        $channel = reset($deviceChannelArr[$door2['device_id']]);
                        $door2['device_id'] = 'hik_channel_' . $channel['channel_id'];
                        $door_list_info[$door2['village_id']][] = $door2;
                    } else {
                        foreach ($deviceChannelArr[$door2['device_id']] as $channelItem) {
                            $door2['device_id'] = 'hik_channel_' . $channelItem['channel_id'];
                            $door2['device_name'] = $channelItem['channelName'];
                            $door_list_info[$door2['village_id']][] = $door2;
                        }
                    }
                } else {
                    $door_list_info[$door2['village_id']][] = $door2;
                }
            }
            unset($door2);
            
            $condition_door = [];
            $condition_door['door_status'] = 1;
            $condition_door['village_id'] = ['in', $village_ids];
            $aDoorList = $m_house_village_door->field(true)->where($condition_door)->select();
            foreach ($aDoorList as $door1) {
                $is_ly = true;
                !isset($door_list_info[$door1['village_id']]) && $door_list_info[$door1['village_id']] = [];
                $door_list_info[$door1['village_id']][] = $door1;
            }
            unset($door1);
            
            foreach ($aUserSelect as $k => $v) {
                if (isset($v['type']) && !in_array($v['type'], [0, 1, 2, 3, 4])) {
                    continue;
                }
                if ($v['authentication_field']) {
                    $authentication_field_arr[] = $v['authentication_field'];
                }
                if ($v['id_card']) {
                    $id_card_arr[] = $v['id_card'];
                }
                unset($v['authentication_field']);
                unset($v['id_card']);
                if(isset($v['open_door_day']) && $v['open_door_day']>0){
                    $v['owe_property_open_door_day']=$v['open_door_day'];
                }
                if ($v['type'] != 4) {
                    // 家属和租客 物业费信息以业主为准
                    $userTimes =  D('House_village_user_bind')->getUserEndTime($v['pigcms_id']);
                    if (isset($userTimes['propertyEndTime'])) {
                        $v['property_endtime'] = intval($userTimes['propertyEndTime']);
                    } else {
                        $v['property_endtime'] = 0;
                    }
                }
                $bind_info[$v['pigcms_id']] = $v;
                if (!$open_list_info[$v['village_id']]) {
                    // 处理小区层信息
                    $open_list_info[$v['village_id']] = array(
                        'village_name' => $v['village_name'],
                        'village_address' => $v['village_address']
                    );
                    // 放置门禁信息数组
                    $open_list_info[$v['village_id']]['door_list'] = array();
                }
                // 避免重复查询门禁信息
                if ($door_list_info && $door_list_info[$v['village_id']]) {
                    $aDoorList = $door_list_info[$v['village_id']];
                } else {
                    if($this->DEVICE_ID=='wxapp'||$this->is_app_browser){
                        // 小程序才能使用蓝牙 所以非小程序不进行蓝牙设备获取
                        $condition_door['door_status'] = 1;
                        $condition_door['village_id'] = $v['village_id'];
                        $aDoorList = $m_house_village_door->group('village_id, door_device_id')->field(true)->where($condition_door)->select();
                    } else {
                        $aDoorList = [];
                    }
                    // 获取对应的人脸门禁设备
                    $condition_face_door = [];
                    $condition_face_door['is_del'] = 0;
                    $condition_face_door['village_id'] = $v['village_id'];
                    $aFaceDoorList = $d_house_face_device->field(true)->where($condition_face_door)->order('add_time DESC, device_id DESC')->select();
                    if (!empty($aFaceDoorList) && !$is_face) {
                        $is_face = true;
                    }
                    if ($aFaceDoorList) {
                        $aDoorList = array_merge($aDoorList, $aFaceDoorList);
                    }
                    $door_list_info[$v['village_id']] = $aDoorList;
                }
                // 默认存在单元门禁
                if (empty($aDoorList)) {
                    unset($open_list_info[$v['village_id']]);
                    continue;
                } else {
                    $open_list_info[$v['village_id']]['have_village_door'] = true;
                    $open_list_info[$v['village_id']]['tip_info'] = '';
                }

                if ($v['type'] == 4) {
                    // 获取工作人员信息
                    $worker_info = $m_house_worker->where(array('phone'=>$now_user['phone'],'status'=>array('neq',4), 'village_id' => $v['village_id']))->field('village_id,wid,phone,status,open_door')->find();

                    if ($worker_info['open_door'] == 1) {
                        $work_open_status = 1;
                        $work_open_info = L_('可以打开');
                        foreach ($aDoorList as $kk => &$vv) {
                            // 如果门禁设备是A4 跳过
                            if (isset($vv['device_type']) && 4==$vv['device_type']) {
                                continue;
                            }
                            if (!isset($vv['talk_type'])) {
                                $vv['talk_type'] = '';
                            }
                            $vv['pigcms_id'] = $v['pigcms_id'];
                            if (isset($vv['device_id']) && $vv['device_id']) {
                                $key = 'face_' . $vv['device_id'];
                                $vv['key_type'] = 'face';
                                $is_face = true;
                            } elseif (isset($vv['door_id']) && $vv['door_id']) {
                                $key = 'ly_' . $vv['door_id'];
                                $vv['key_type'] = 'ly';
                                $is_ly = true;
                            } else {
                                fdump('--------------------'.__LINE__,'open_List', true);
                                fdump($vv,'open_List', true);
                                continue;
                            }
                            $thirdProtocol = isset($vv['thirdProtocol']) ? intval($vv['thirdProtocol']) : 0;
                            if ($thirdProtocol == $d_house_face_device::DH_YUNRUI) {
                                $vv['videoIntercom']     = 1;
                                $vv['videoIntercomType'] = 'dh_yun_rui';
                                $vv['videoIntercomText'] = '大华云睿';
                            } elseif ($vv['device_type'] == 5) {
                                // 云眸内部协议暂时走和外部协议相同的
                                $vv['videoIntercom']     = 1;
                                $vv['videoIntercomType'] = 'hik_yun_mou_wai_bu';
                                $vv['videoIntercomText'] = '云牟外部独立协议';
                            } else {
                                $vv['videoIntercom']     = 0;
                                $vv['videoIntercomType'] = '';
                                $vv['videoIntercomText'] = '';
                            }
                            if (!empty($aDoor_is_open) && !empty($aDoor_is_open[$key])) {
                                // 门禁信息已经查询过，直接赋予新的状态
                                $door = $aDoor_is_open[$key];
                                $door['door_end_time'] = 0;
                                $door['door_user_type'] = 3; // 工作人员
                                $aDoor_is_open[$key]['door_end_time'] = 0; // 工作人员开门权限无时间限制
                                $aDoor_is_open[$key]['door_user_type'] = 3;
                                $open_list_info[$vv['village_id']]['door_list'][$key] = $door;
                                continue;
                            }
                            if ($vv['floor_id'] != "-1") {
                                $vv['floor_name'] = $vv['device_name']?$vv['device_name']:$vv['door_name'];
                                $vv['floor_layer'] = '';
                                $vv['door_type'] = 1; // 小区单元门
                            } elseif (isset($vv['public_area_id']) && $vv['public_area_id']>0) {
                                $house_name = $this->config['house_name'] ? $this->config['house_name'].L_('大门') : L_('小区').L_('大门');
                                $floor_name = $house_village_public_area[$vv['public_area_id']] ? $house_village_public_area[$vv['public_area_id']] : $house_name;
                                $vv['floor_name'] = $vv['device_name']?$vv['device_name']:$floor_name;
                                $vv['floor_layer'] = $vv['device_name']?$vv['device_name']:$vv['door_name'];
                                $vv['door_type'] = 2; // 小区大门
                            } else {
                                $vv['floor_name'] = $vv['device_name']?$vv['device_name']:$vv['door_name'];
                                $vv['floor_layer'] = '';
                                $vv['door_type'] = 2; // 小区大门
                            }
                            $floor_name_arr = explode((D('House_village')->getHouseAlias('single_name')?:'栋'), $vv['floor_name']);
                            $lens = mb_strlen($vv['floor_name']);
                            if (count($floor_name_arr)==2) {
                                $vv['device_name_top'] = $floor_name_arr[0].(D('House_village')->getHouseAlias('single_name')?:'栋');
                                $vv['device_name_txt'] = $floor_name_arr[1];
                            } elseif ($lens>4 && $lens<=7) {
                                $vv['device_name_top'] = mb_substr($vv['floor_name'],0,2);
                                $vv['device_name_txt'] = mb_substr($vv['floor_name'],2,$lens-2);
                            } elseif ($lens>7) {
                                $vv['device_name_top'] = mb_substr($vv['floor_name'],0,4);
                                $vv['device_name_txt'] = mb_substr($vv['floor_name'],4,$lens-2);
                            } else {
                                $vv['device_name_top'] = '';
                                $vv['device_name_txt'] = $vv['floor_name'];
                            }
                            $vv['door_user_type'] = 3; // 工作人员
                            $vv['door_end_time'] = 0;
                            $vv['open_status'] = $work_open_status;
                            $vv['open_status_txt'] = $work_open_info;
                            $aDoor[] = $vv;
                            $aDoor_is_open[$key] = $vv;
                            $open_list_info[$vv['village_id']]['door_list'][$key] = $vv;
                            $open_list_info[$vv['village_id']]['have_floor_door'] = true;
                        }
                    }
                } else {
                    // 绑定的单元-即拥有该小区的大门和改单元门禁权限
                    $village_floor_id = $v['floor_id'];
                    // 开门到期时间
                    $property_endtime = 0;

                    // 允许开门情况下继续判断
                    if (!$v['property_endtime']) {
                        $ban_open_status = false;   // 是否禁止开门  false 可以开门  true 禁止开门
                    } else {
                        $ban_open_status = false; // 默认可以开门
                        $owe_property_days = 0;
                        if ( $v['property_endtime'] > 0) {
                            // 计算 当前时间到物业截止时间的天数
                            $owe_property_days = (strtotime(date('y-m-d', time())) - strtotime(date('y-m-d', $v['property_endtime']))) / 86400;
                        }
                        if (!$v['owe_property_open_door'] && $v['property_endtime']>0 && (time() > $v['property_endtime'])) {
                            // 判断  不能欠款延期开门 且 当前时间大于 物业截止时间  开门禁止
                            $ban_open_status = true;
                        } else if ($v['owe_property_open_door_day'] > 0 && $owe_property_days >= 0 && $owe_property_days > $v['owe_property_open_door_day']) {
                            // 判断  可以欠款延期开门 且 延期天数大于0 且  当前时间到物业截止时间的天数存在 且大于 延期天数 开门禁止
                            $ban_open_status = true;
                        }
                        // 处理下开门到期时间
                        if ($v['property_endtime'] > 0) {
                            $property_endtime = $v['property_endtime'];
                            if ($v['owe_property_open_door'] && $v['owe_property_open_door_day'] > 0) {
                                $property_endtime +=  $v['owe_property_open_door_day'] * 86400;
                            }else if($v['owe_property_open_door']){
								$property_endtime = 0;
							}
                        }
                    }
                    foreach ($aDoorList as $kk => &$vv) {
                        $vv['pigcms_id'] = $v['pigcms_id'];
                        if (isset($vv['device_id']) && $vv['device_id']) {
                            $key = 'face_' . $vv['device_id'];
                            $vv['key_type'] = 'face';
                        } elseif (isset($vv['door_id']) && $vv['door_id']) {
                            $key = 'ly_' . $vv['door_id'];
                            $vv['key_type'] = 'ly';
                        } else {
                            fdump('--------------------'.__LINE__,'open_List', true);
                            fdump($vv,'open_List', true);
                            continue;
                        }
                        $thirdProtocol = isset($vv['thirdProtocol']) ? intval($vv['thirdProtocol']) : 0;
                        if ($thirdProtocol == $d_house_face_device::DH_YUNRUI) {
                            $vv['videoIntercom']     = 1;
                            $vv['videoIntercomType'] = 'dh_yun_rui';
                            $vv['videoIntercomText'] = '大华云睿';
                        } elseif ($vv['device_type'] == 5) {
                            // 云眸内部协议暂时走和外部协议相同的
                            $vv['videoIntercom']     = 1;
                            $vv['videoIntercomType'] = 'hik_yun_mou_wai_bu';
                            $vv['videoIntercomText'] = '云牟外部独立协议';
                        } else {
                            $vv['videoIntercom']     = 0;
                            $vv['videoIntercomType'] = '';
                            $vv['videoIntercomText'] = '';
                        }
                        $floor_arr = [];
                        if (!empty($aDoor_is_open) && !empty($aDoor_is_open[$key])) {

                            // 判断下哪一个有效期更长
                            if (4==$vv['device_type']) {
                                if ($ban_open_status && ($aDoor_is_open[$key]['door_end_time'] == 0 || ($property_endtime > 0 && $aDoor_is_open[$key]['door_end_time'] && $aDoor_is_open[$key]['door_end_time'] > $property_endtime))) {
                                    // 如果已经存在的门禁权限更久 将跳过下面的步骤
                                    continue;
                                }
                            } else {
                                if ($aDoor_is_open[$key]['door_end_time'] == 0 || ($property_endtime > 0 && $aDoor_is_open[$key]['door_end_time'] && $aDoor_is_open[$key]['door_end_time'] > $property_endtime)) {
                                    // 如果已经存在的门禁权限更久 将跳过下面的步骤
                                    continue;
                                }
                            }
                            // 门禁信息已经查询过，直接赋予新的状态
                            $vv = $aDoor_is_open[$key];
                            $vv['pigcms_id'] = $v['pigcms_id'];
                        } elseif ($vv['floor_id'] != "-1") {
                            $floor_arr = explode(',',$vv['floor_id']);
                            $vv['floor_name'] = $vv['device_name']?$vv['device_name']:$vv['door_name'];
                            $vv['floor_layer'] = '';
                            $vv['door_type'] = 1; // 小区单元门
                        } elseif (isset($vv['public_area_id']) && $vv['public_area_id']>0) {
                            $house_name = $this->config['house_name'] ? $this->config['house_name'].L_('大门') : L_('小区').L_('大门');
                            $floor_name = $house_village_public_area[$vv['public_area_id']] ? $house_village_public_area[$vv['public_area_id']] : $house_name;
                            $vv['floor_name'] = $vv['device_name']?$vv['device_name']:$floor_name;
                            $vv['floor_layer'] = '';
                            $vv['door_type'] = 2; // 小区大门
                        } else {
                            $vv['floor_name'] = $this->config['house_name'] ? $this->config['house_name'] : L_('小区');
                            $vv['floor_layer'] = '';
                            $vv['door_type'] = 2; // 小区大门
                        }

                        $floor_name_arr = explode((D('House_village')->getHouseAlias('single_name')?:'栋'), $vv['floor_name']);
                        $lens = mb_strlen($vv['floor_name']);
                        if (count($floor_name_arr)==2) {
                            $vv['device_name_top'] = $floor_name_arr[0].(D('House_village')->getHouseAlias('single_name')?:'栋');
                            $vv['device_name_txt'] = $floor_name_arr[1];
                        } elseif ($lens>4 && $lens<7) {
                            $vv['device_name_top'] = mb_substr($vv['floor_name'],0,2);
                            $vv['device_name_txt'] = mb_substr($vv['floor_name'],2,$lens-2);
                        } elseif ($lens>=7) {
                            $vv['device_name_top'] = mb_substr($vv['floor_name'],0,4);
                            $vv['device_name_txt'] = mb_substr($vv['floor_name'],4,$lens-2);
                        } else {
                            $vv['device_name_top'] = '';
                            $vv['device_name_txt'] = $vv['floor_name'];
                        }
                        if ($v['type'] == 1 || $v['type'] == 2) {
                            $vv['door_user_type'] = 4; // 家属
                        } else {
                            $vv['door_user_type'] = 1; // 业主
                        }
                        if ($vv['device_type']==29 && isset($vv['thirdDeviceTypeStr'])&&$vv['thirdDeviceTypeStr']==2) {
                            if (isset( $nmv_card_arr[$v['pigcms_id']]) &&  $nmv_card_arr[$v['pigcms_id']]) {
                                $nmv_card = $nmv_card_arr[$v['pigcms_id']];
                            } else {
                                $whereNmvCard = ['bind_id'=>$v['pigcms_id']];
                                $nmv_card = $db_house_village_nmv_card->where($whereNmvCard)->find();
                                if ($nmv_card&&isset($nmv_card['id'])&&$nmv_card['id']) {
                                    $nmv_card_arr[$v['pigcms_id']] = $nmv_card;
                                }
                            }
                            $expiration_time = isset($nmv_card['expiration_time'])&&$nmv_card['expiration_time']?$nmv_card['expiration_time']:0;
                            if ($expiration_time<time()) {
                                $ban_open_status = true;
                            }
                            $property_endtime = $expiration_time;
                        }

                        $vv['door_end_time'] = $property_endtime>0?date('Y-m-d H:i', $property_endtime):0;
                        if (isset($vv['door_id']) && $vv['door_id']>0) {
                            $userWhere = array(
                                'user_id' => $uid,
                                'door_fid' => $vv['door_id'],
                            );
                            $aDoorFind = $m_house_village_door_user->field(true)->where($userWhere)->find();
                        } else {
                            $aDoorFind = array();
                        }
                        
                        $showDevice = true;
                        if (!$ban_open_status && $vv['thirdChnNum'] && ! empty($floor_arr) && intval($v['vacancy_id']) > 0) {
                            $whereRoom = [];
                            $whereRoom['pigcms_id'] = $v['vacancy_id'];
                            $whereRoom['room'] = ['like', '%' . $vv['thirdChnNum']];
                            $roomInfo = M('House_village_user_vacancy')->where($whereRoom)->find();
                            if (!$roomInfo) {
                                $ban_open_status = true;
                                $showDevice = false;
                            }
                        }

                        if (!$ban_open_status && empty($aDoorFind) && ($vv['floor_id'] == -1 || intval($vv['floor_id']) === 0)) {
                            if (empty($aDoor_is_open) || empty($aDoor_is_open[$key])) {
                                $vv['open_status'] = 1;
                                $vv['open_status_txt'] = L_('可以打开');
                                $aDoor[] = $vv;
                                $aDoor_is_open[$key] = $vv;
                            }
                            if (!$is_ly&&$vv['key_type']=='ly') {
                                $is_ly = true;
                            }
                            $aDoor_is_open[$key]['door_user_type'] = 1;
                            $open_list_info[$vv['village_id']]['door_list'][$key] = $vv;
                        } elseif (!$ban_open_status && empty($aDoorFind) && ($village_floor_id==0 || $vv['floor_id'] == $village_floor_id || in_array($village_floor_id, $floor_arr))) {
                            if (empty($aDoor_is_open) || empty($aDoor_is_open[$key])) {
                                $vv['open_status'] = 1;
                                $vv['open_status_txt'] = L_('可以打开');
                                $aDoor[] = $vv;
                                $aDoor_is_open[$key] = $vv;
                            }
                            if (!$is_ly&&$vv['key_type']=='ly') {
                                $is_ly = true;
                            }
                            $aDoor_is_open[$key]['door_user_type'] = 1;
                            $open_list_info[$vv['village_id']]['door_list'][$key] = $vv;
                        } elseif (!$ban_open_status && $aDoorFind['status'] == 1 && ($village_floor_id==0 || $vv['floor_id'] == $village_floor_id || in_array($village_floor_id, $floor_arr))) {
                            if ($aDoorFind == 0 || time() > $aDoorFind['end_time']) {
                                if (empty($aDoor_is_open) || empty($aDoor_is_open[$key])) {
                                    $vv['open_status'] = 1;
                                    $vv['open_status_txt'] = L_('可以打开');
                                    $aDoor[] = $vv;
                                    $aDoor_is_open[$key] = $vv;
                                }
                                if (!$is_ly&&$vv['key_type']=='ly') {
                                    $is_ly = true;
                                }
                                $aDoor_is_open[$key]['door_user_type'] = 1;
                                $open_list_info[$vv['village_id']]['door_list'][$key] = $vv;
                            } else {
                                $vv['open_status']	=	2;	//时间过期
                                $vv['open_status_txt']	=	L_('已过期，请联系物业');
                            }
                            $open_list_info[$vv['village_id']]['door_list'][$key] = $vv;
                        } elseif (!$ban_open_status && $aDoorFind['status'] == 0 && ($village_floor_id==0 || $vv['floor_id'] == $village_floor_id || in_array($village_floor_id, $floor_arr))) {
                            $vv['open_status']	=	0;		//禁止使用
                            $vv['open_status_txt']	=	L_('您没有权限开门，请联系物业');		//禁止使用
                            $open_list_info[$vv['village_id']]['door_list'][$key] = $vv;
                        } elseif ($showDevice && $ban_open_status && ($village_floor_id==0 || $vv['floor_id'] == $village_floor_id || in_array($village_floor_id, $floor_arr))) {
                            $vv['open_status']	=	3;//欠物业费
                            $vv['open_status_txt']	=	L_('您的权限已到期，请联系物业管理人员');
                            $open_list_info[$vv['village_id']]['door_list'][$key] = $vv;
                        } elseif ($showDevice && $ban_open_status && $vv['floor_id'] == -1) {
                            $vv['open_status']	=	3;//欠物业费
                            $vv['open_status_txt']	=	L_('您的权限已到期，请联系物业管理人员');
                            $open_list_info[$vv['village_id']]['door_list'][$key] = $vv;
                        }
                    }
                    if (empty($open_list_info[$v['village_id']]['door_list']) && $open_list_info[$v['village_id']]['have_village_door']) {
                        $open_list_info[$v['village_id']]['have_floor_door'] = false;
                        $open_list_info[$v['village_id']]['tip_info'] = L_('您入住的单元暂未添加门禁');
                    } else {
                        $open_list_info[$v['village_id']]['have_floor_door'] = true;
                    }
                    $open_list_info[$v['village_id']]['village_id'] = $v['village_id'];
                }
            }
        }
        $params = [];
        $params['DEVICE_ID'] = $this->DEVICE_ID;
        $params['is_app_browser'] = $this->is_app_browser;
        $door_info_list = D('House_village_bind_share')->bind_share_door_info($uid, $open_list_info, $door_list_info, $aDoor_is_open, $face_device_type,$params);
        if ($door_info_list['aDoor']) {
            $aDoor =  array_merge($aDoor,$door_info_list['aDoor']);
        }
        if ($door_info_list['open_list_info']) {
            $open_list_info = $door_info_list['open_list_info'];
        }
        if ($door_info_list['is_ly'] && !$is_ly) {
            $is_ly = true;
        }
        if ($door_info_list['is_face'] && !$is_face) {
            $is_face = true;
        }

        $village_arr = [];
        if ($open_list_info) {
            foreach ($open_list_info as $keys => &$val) {
                // 没有门禁信息 去除对应信息
                if ($keys) {
                    $village_arr[] = $keys;
                }
                if (empty($val['door_list'])) {
                    unset($open_list_info[$keys]);
                } else {
                    $val['door_num'] = count($val['door_list']);
                }
            }
            $open_list_info = array_values($open_list_info);
            if ($open_list_info) {
                foreach ($open_list_info as &$val) {
                    $val['door_list'] = array_values($val['door_list']);
                }
            }
        }
        if ($_POST['app_version'] > 1300) {
            $door_info = $this->new_wxapp_door_info($aDoor);
        } else {
            $aDoor = $this->new_wxapp_door($aDoor);
        }

        if ($open_type == 'index_back') {
            if (!empty($open_list_info)) {
                $is_btn = true;
            } else {
                $is_btn = false;
            }
            if ($_POST['app_version'] > 1300) {
                if (empty($door_info)) {
                    $door_info = $this->getObj();
                }
                $village_floor_list = array(
                    'door_info' => $door_info,
                    'is_btn' => $is_btn
                );
            } else {
                $village_floor_list = array(
                    'aDoor' => $aDoor,
                    'is_btn' => $is_btn
                );
            }
            $this->returnCode(0,$village_floor_list);
        } else {
            $invalid_share_arr = array(); // 过期的分享门禁id
            $invalid_share_count = 0; // 过期的分享门禁数量
            if ($door_info_list['invalid_share_arr']) {
                $invalid_share_arr = $door_info_list['invalid_share_arr'];
                $invalid_share_count = count($door_info_list['invalid_share_arr']);
                if ($invalid_share_count>0) {
                    $this->relieve_bind_all_share_user($invalid_share_arr);
                }
            }
            if ($_POST['app_version'] > 1300) {
                if (empty($open_list_info) && empty($door_info)) {
                    $is_btn = false;
                } else {
                    $is_btn = true;
                }
                if ($is_btn && $is_ly && (!$this->is_wxapp_browser && !$this->is_app_browser && !$this->is_weixin_browser)) {
                    // 如果有蓝牙  且不是小程序和APP页面 不显示按钮 (小程序 app 微信环境)
                    $is_btn = false;
                }
                if (empty($door_info)) {
                     $door_info = $this->getObj();
                }

                $village_floor_list = array(
                    'door_info' => $door_info,
                    'open_list_info' => $open_list_info,
                    'is_btn' => $is_btn,
                    'invalid_share_arr' => $invalid_share_arr,
                    'invalid_share_count' => $invalid_share_count,
                    'auto_open_door' => $this->config['house_village_auto_door'] ? true : false,	//进页面是否展示自动开门设置按钮
                    'auto_page_door' => $this->config['house_village_page_door'] ? true : false		//进页面直接开门
                );
            } else {
                $village_floor_list = array(
                    'aDoor' => $aDoor,
                    'open_list_info' => $open_list_info,
                    'is_btn' => !empty($open_list_info) ? true : false,
                    'invalid_share_arr' => $invalid_share_arr,
                    'invalid_share_count' => $invalid_share_count,
                    'auto_open_door' => $this->config['house_village_auto_door'] ? true : false,	//进页面是否展示自动开门设置按钮
                    'auto_page_door' => $this->config['house_village_page_door'] ? true : false		//进页面直接开门
                );
            }
            $village_floor_list['is_ly'] = $is_ly;
            $village_floor_list['is_face'] = $is_face;
            $village_floor_list['village_num'] = count($open_list_info);
            if ($this->config['house_advert_targeted_delivery'] != 1) {
                // 如果开关还是 支持 物业自主管理轮播图广告 此处返回空数组
                $village_floor_list['click_count'] = false;
                $village_floor_list['open_advert'] = $this->getObj();
            } else {
                // 获取最新的符合条件的5条广告然后随机获取一个
                $cat_id = M('House_adver_category')->where(['cat_key' => 'house_open_advert'])->getField('cat_id');
                $where_advert = [
                    'cat_id' => $cat_id,
                    'status' => 1,
                ];
                $where_advert['_string'] = '';
                if ($village_arr) {
                    $village_str = implode(",",$village_arr);
                    $adver_village_list=M('House_adver_village_count')->where(['village_id'=>['in',$village_str]])->getField('adver_id',true);
                    if (empty($adver_village_list)) {
                        $where_advert['_string'] .= "(village_id in ({$village_str}) OR village_id=0)";
                    } else {
                        $adver_village_list = implode(',',$adver_village_list);
                        $where_advert['_string'] .= "(village_id=0 OR (add_type=1 AND village_id in ({$village_str})) OR (id IN ({$adver_village_list})))";
                    }
                }
                $now_time = time();
                $str_time = date('Hi', $now_time);
                $str_time = intval($str_time);
                if ($where_advert['_string']) {
                    $where_advert['_string'] .= " AND ((effective_start_time=0 AND effective_end_time=0) OR (effective_start_time<={$now_time} AND effective_end_time>={$now_time}))";
                } else {
                    $where_advert['_string'] .= "((effective_start_time=0 AND effective_end_time=0) OR (effective_start_time<={$now_time} AND effective_end_time>={$now_time}))";
                }
                $where_advert['_string'] .= " AND ((play_start_time=0 AND play_end_time=0) OR  (play_start_time<={$str_time} AND play_end_time>={$str_time}))";
                $sex = [];
                $sex[] = 0;
                if ($now_user['sex']) {
                    $sex[] = $now_user['sex']==1?1:2;
                }
                $age = 0;
                if ($id_card_arr) {
                    foreach ($id_card_arr as $vas) {
                        if (is_idcard($vas)) {
                            $sex_judge = substr($vas,-2,1);
                            if ($sex_judge%2==0){
                                // 偶数是女
                                $sex[] = 2;
                            }else{
                                // 奇数是男
                                $sex[] = 1;
                            }
                            $age = getAgeByIdcard($vas);
                        }
                    }
                }
                if ($authentication_field_arr) {
                    foreach ($authentication_field_arr as $vas1) {
                        $authentication_field = unserialize($vas1);
                        if ($authentication_field && $authentication_field['sex'] && $authentication_field['sex']['value']) {
                            if ('女'==$authentication_field['sex']['value']) {
                                $sex[] = 2;
                            } elseif ('男'==$authentication_field['sex']['value']) {
                                $sex[] = 2;
                            }
                        }
                        if (!$age && $authentication_field && $authentication_field['birthday'] && $authentication_field['birthday']['value']) {
                            $date_time = $authentication_field['birthday']['value'];
                            $date = strtotime($date_time);
                            if ($date) {
                                //获得今日的时间戳
                                $today = strtotime('today');
                                //得到两个日期相差的大体年数
                                $diff = floor(($today-$date)/86400/365);
                                //strtotime加上这个年数后得到那日的时间戳后与今日的时间戳相比
                                $age = strtotime($date_time.' +'.$diff.'years')>$today?($diff+1):$diff;
                                $age = intval($age);
                            }
                        }
                    }
                }
                $sex = array_unique($sex);
                if ($sex) {
                    $where_advert['lm_sex'] = ['in', $sex];
                } else {
                    $where_advert['lm_sex'] = 0;
                }
                if ($age) {
                    $where_advert['_string'] .= " AND ((lm_age_min=0 AND lm_age_max=0) OR  (lm_age_min<={$age} AND lm_age_max>={$age}))";
                } else {
                    $where_advert['_string'] .= " AND lm_age_min=0 AND lm_age_max=0";
                }
                $house_adver = M('House_adver')->where($where_advert)->field('id,name,bg_color,pic,url,cat_id,lm_day_look_max,lm_day_click_max')->limit(5)->select();

                if ($house_adver) {
                    $max = count($house_adver) - 1;
                    $advert_key = mt_rand(0,$max);
                    $open_advert = $house_adver[$advert_key];
                    if (strpos($open_advert['pic'], '/upload') !== false) {
                        $open_advert['pic'] = replace_file_domain($open_advert['pic']);
                    } elseif($open_advert['pic']) {
                        $open_advert['pic'] = replace_file_domain('/upload/adver/'.$open_advert['pic']);
                    }
                    $param = [
                        'village_id' => $open_advert['village_id'] ? $open_advert['village_id'] : 0,
                        'lm_day_look_max' => $open_advert['lm_day_look_max'] ? $open_advert['lm_day_look_max'] : 0,
                        'lm_day_click_max' => $open_advert['lm_day_click_max'] ? $open_advert['lm_day_click_max'] : 0
                    ];
                    D('House_adver')->add_house_adver_record($open_advert['id'],1,$param);
                    $village_id= isset($_POST['village_id'])?$_POST['village_id']:0;
                    $village_count=D('House_adver_village_count')->where(['village_id'=>$village_id,'adver_id'=>$open_advert['id']])->find();
                    if (!empty($village_count)&&isset($_POST['village_id'])){
                        $data_adver=[];
                        $data_adver['look_num']=$village_count['look_num']+1;
                        $data_adver['last_time']=time();
                        D('House_adver_village_count')->where(['village_id'=>$village_id,'adver_id'=>$open_advert['id']])->data($data_adver)->save();
                    }elseif (empty($village_count)&&isset($_POST['village_id'])){
                        $data_count = [];
                        $data_count['village_id'] = $village_id;
                        $data_count['look_num']=1;
                        $data_count['adver_id'] = $open_advert['id'];
                        $data_count['last_time'] = time();
                        $data_count['add_time'] = time();
                        M('House_adver_village_count')->data($data_count)->add();
                    }
                   if ($open_advert && $open_advert['bg_color']) {
                        $open_advert['bg_color_app'] = preg_match("/^rgb/", $open_advert['bg_color']) ? RGBToHex($open_advert['bg_color']) : $open_advert['bg_color'];
                    } elseif ($open_advert) {
                        $open_advert['bg_color_app'] = '';
                    }
                } else {
                    $open_advert = $this->getObj();
                }
                $village_floor_list['open_advert'] = $open_advert;
                $village_floor_list['click_count'] = true;
            }
            $this->returnCode(0,$village_floor_list);
        }
    }

    // 获取 用户的关联分享相关信息
    private function bind_share_msg($uid){
        $share_where = array(
            'a.uid' =>$uid,
            'd.status' => 1
        );
        $share_info = M('House_village_bind_share')->alias('a')
            ->field('a.uid, a.id as bind_id, b.share_end_time, b.share_uid, c.village_name, c.village_address, d.pigcms_id, d.parent_id, d.type, d.name, d.village_id, d.floor_id, d.property_price, d.property_endtime, d.phone,d.open_door_day, c.owe_property_open_door, c.owe_property_open_door_day')
            ->join('LEFT JOIN pigcms_house_village_door_share as b on b.door_share_id=a.door_share_id')
            ->join('LEFT JOIN pigcms_house_village as c on b.village_id=c.village_id')
            ->join('LEFT JOIN pigcms_house_village_user_bind as d on b.share_uid=d.uid and b.village_id=d.village_id')
            ->where($share_where)
            ->select();
        return $share_info;
    }

    // 获取 用户从分享获取到的门禁
    private function bind_share_door_info($uid, $open_list_info = array(), $door_list_info = array(), $floor_list_info = array(), $aDoor_is_open = array(),$phone = ''){

        $share_info = $this->bind_share_msg($uid);
        if (empty($open_list_info)) {
            $open_list_info = array();
        }

        $m_house_worker = M('House_worker');
        $m_house_village_door_user = M('House_village_door_user');
        $m_house_village_floor = M('House_village_floor');
        $m_house_village_door = M('House_village_door');
        $m_house_village_user_bind = M('House_village_user_bind');
        $d_house_face_device = M('House_face_device');

        $aDoor = array();
        $invalid_share_arr = array();
        // 是否有蓝牙门禁
        $is_ly = false;
        // 是否有人脸门禁
        $is_face = false;
        // 存储信息
        $bind_info = array();
        if ($share_info) {
            $now_time = time();
            foreach ($share_info as $val) {
                if(isset($val['open_door_day']) && $val['open_door_day']>0){
                    $val['owe_property_open_door_day']=$val['open_door_day'];
                }
                if ($val['type'] == 1 || $val['type'] == 2) {
                    // 家属和租客 物业费信息以业主为准
                    if ($bind_info[$val['parent_id']]) {
                        $val['property_price'] = $bind_info[$val['parent_id']]['property_price'];
                        $val['property_endtime'] = $bind_info[$val['parent_id']]['property_endtime'];
                    } else {
                        $info = $m_house_village_user_bind->where(array('pigcms_id' => $val['parent_id']))->field('property_price, property_endtime')->find();
                        $val['property_price'] = $info['property_price'];
                        $val['property_endtime'] = $info['property_endtime'];
                    }
                }
                if ($val['type'] == 4) {
                    continue;
                    // 获取工作人员信息
//                    // 考虑到安全问题，工作人员门禁权限不可分享（暂时注释，后期看是否需要使用）
//                    $worker_info = $m_house_worker->where(array('phone'=>$phone,'status'=>array('neq',4), 'village_id' => $val['village_id']))->field('village_id,wid,phone,status,open_door')->find();
//                    if ($worker_info['open_door'] == 1) {
//                        $work_open_status = 1;
//                        $work_open_info = '可以打开';
//                        foreach ($aDoorList as $kk => &$vv) {
//                            // 如果已经存在业主开门权限且到期时间长于分享的截止时间，后续操作将不再进行，直接使用业主开门权限
//                            if ($open_list_info[$val['village_id']] && $open_list_info[$val['village_id']]['door_list'] && $open_list_info[$val['village_id']]['door_list'][$vv['door_id']] && $open_list_info[$val['village_id']]['door_list'][$vv['door_id']]['door_end_time'] <= 0) {
//                                continue;
//                            }
//                            if (!empty($aDoor_is_open) && !empty($aDoor_is_open[$vv['door_id']])) {
//                                // 门禁信息已经查询过，直接赋予新的状态
//                                $door = $aDoor_is_open[$vv['door_id']];
//                                $door['door_end_time'] = 0; // 到期时间 工作人员无限制
//                                $door['door_user_type'] = 2; // 临时访客
//                                $aDoor_is_open[$vv['door_id']]['door_end_time'] = 0; // 工作人员开门权限无时间限制
//                                $aDoor_is_open[$vv['door_id']]['door_user_type'] = 2; // 临时访客
//                                $open_list_info[$vv['village_id']]['door_list'][$vv['door_id']] = $door;
//                                continue;
//                            }
//                            if ($vv['floor_id'] != "-1") {
//                                // 避免重复查询单元信息
//                                if ($floor_list_info && $floor_list_info[$vv['floor_id']]) {
//                                    $aFloor = $floor_list_info[$vv['floor_id']];
//                                } else {
//                                    $aFloor = $m_house_village_floor->field(array('floor_name', 'floor_layer'))->where(array('floor_id' => $vv['floor_id']))->find();
//                                    $floor_list_info[$vv['floor_id']] = $aFloor;
//                                }
//                                $vv['floor_name'] = strval($aFloor['floor_name']);
//                                $vv['floor_layer'] = strval($aFloor['floor_layer']);
//                                $vv['door_type'] = 1; // 小区单元门
//                            } else {
//                                $vv['floor_name'] = '小区';
//                                $vv['floor_layer'] = '大门';
//                                $vv['door_type'] = 2; // 小区大门
//                            }
//                            $vv['door_user_type'] = 2; // 临时访客
//                            $vv['door_end_time'] = 0;
//                            $vv['open_status'] = $work_open_status;
//                            $vv['open_status_txt'] = $work_open_info;
//                            $aDoor[] = $vv;
//                            $open_list_info[$vv['village_id']]['door_list'][$vv['door_id']] = $vv;
//                        }
//                    }
                } else {
                    $bind_info[$val['pigcms_id']] = $val;

                    // 单独处理-由于工作人员不处理，放置到非工作人员绑定
                    if (!$open_list_info[$val['village_id']]) {
                        // 处理小区层信息
                        $open_list_info[$val['village_id']] = array();
                        $open_list_info[$val['village_id']]['village_name'] = $val['village_name'];
                        $open_list_info[$val['village_id']]['village_address'] = $val['village_address'];
                    }
                    if (!$open_list_info[$val['village_id']]['door_list']) {
                        // 放置门禁信息数组
                        $open_list_info[$val['village_id']]['door_list'] = array();
                    }


                    if ($val['share_end_time'] > $now_time) {
                        $val['time_limit'] = 1; // 有效
                    } else {
                        $val['time_limit'] = 2; // 失效
                        $invalid_share_arr[$val['bind_id']] = $val['bind_id'];
                    }

                    // 获取门禁
                    if ($door_list_info && $door_list_info[$val['village_id']]) {
                        $aDoorList = $door_list_info[$val['village_id']];
                    } else {
                        $condition_door['door_status'] = 1;
                        $condition_door['village_id'] = $val['village_id'];
                        $aDoorList = $m_house_village_door->group('village_id, door_device_id')->field(true)->where($condition_door)->select();
                        if (!empty($aDoorList) && !$is_ly) {
                            $is_ly = true;
                        }
                        // 获取对应的人脸门禁设备
                        $condition_face_door['device_status'] = 1;
                        $condition_face_door['is_del'] = 0;
                        $condition_face_door['village_id'] = $val['village_id'];
                        $aFaceDoorList = $d_house_face_device->group('village_id, device_id')->field(true)->where($condition_face_door)->select();
                        if (!empty($aFaceDoorList) && !$is_face) {
                            $is_face = true;
                        }
                        if ($aFaceDoorList) {
                            $aDoorList = array_merge($aDoorList, $aFaceDoorList);
                        }
                        $door_list_info[$val['village_id']] = $aDoorList;
                    }

                    if (empty($aDoorList)) {
                        unset($open_list_info[$val['village_id']]);
                        continue;
                    } else {
                        $open_list_info[$val['village_id']]['have_village_door'] = true;
                        $open_list_info[$val['village_id']]['tip_info'] = '';
                    }

                    // 绑定的单元-即拥有该小区的大门和改单元门禁权限
                    $village_floor_id = $val['floor_id'];
                    // 物业门禁开锁到期时间处理
                    $property_endtime = 0;

                    // 允许开门情况下继续判断
                    if (!$val['property_endtime']) {
                        $ban_open_status = false;   // 是否禁止开门  false 可以开门  true 禁止开门
                    } else {
                        $ban_open_status = false; // 默认可以开门
                        $owe_property_days = 0;
                        if ( $val['property_endtime'] > 0) {
                            // 计算 当前时间到物业截止时间的天数
                            $owe_property_days = (strtotime(date('y-m-d', time())) - strtotime(date('y-m-d', $val['property_endtime']))) / 86400;
                        }
                        if (!$val['owe_property_open_door'] && $val['property_endtime']>0 && (time() > $val['property_endtime'])) {
                            // 判断  不能欠款延期开门 且 当前时间大于 物业截止时间  开门禁止
                            $ban_open_status = true;
                        } else if ($val['owe_property_open_door_day'] > 0 && $owe_property_days >= 0 && $owe_property_days > $val['owe_property_open_door_day']) {
                            // 判断  可以欠款延期开门 且 延期天数大于0 且  当前时间到物业截止时间的天数存在 且大于 延期天数 开门禁止
                            $ban_open_status = true;
                        }
                        // 处理下开门到期时间
                        if ($val['property_endtime'] > 0) {
                            $property_endtime = $val['property_endtime'];
                            if ($val['owe_property_open_door'] && $val['owe_property_open_door_day'] > 0) {
                                $property_endtime +=  $val['owe_property_open_door_day'] * 86400;
                            }
                        }
                    }

                    foreach ($aDoorList as $kk => &$vv) {
                        if (isset($vv['device_id']) && $vv['device_id']>0) {
                            $key = 'face_' . $vv['device_id'];
                            $vv['key_type'] = 'face';
                        } elseif (isset($vv['door_id']) && $vv['door_id']>0) {
                            $key = 'ly_' . $vv['door_id'];
                            $vv['key_type'] = 'ly';
                        } else {
                            fdump('--------------------'.__LINE__,'open_List', true);
                            fdump($vv,'open_List', true);
                            continue;
                        }
                        // 如果已经存在业主开门权限且到期时间长于分享的截止时间，后续操作将不再进行，直接使用业主开门权限
                        if ($open_list_info[$val['village_id']] && $open_list_info[$vv['village_id']]['door_list'] && $open_list_info[$vv['village_id']]['door_list'][$key]) {
                            $door_end_time = $open_list_info[$vv['village_id']]['door_list'][$key]['door_end_time'];
                            if (strtotime($door_end_time) <= 0) {
                                continue;
                            } elseif (strtotime($door_end_time) >0 && $property_endtime > 0 && strtotime($door_end_time) > $property_endtime) {
                                continue;
                            } elseif (strtotime($door_end_time) >0 && $val['share_end_time'] > 0 && strtotime($door_end_time) > $val['share_end_time']) {
                                continue;
                            }
                        }

                        if (!empty($aDoor_is_open) && !empty($aDoor_is_open[$key])) {
                            // 门禁信息已经查询过，直接赋予新的状态
                            $vv = $aDoor_is_open[$key];
                        } elseif ($vv['floor_id'] != "-1") {
                            // 避免重复查询单元信息
                            if ($floor_list_info && $floor_list_info[$vv['floor_id']]) {
                                $aFloor = $floor_list_info[$vv['floor_id']];
                            } else {
                                $aFloor = $m_house_village_floor->field(array('floor_name', 'floor_layer'))->where(array('floor_id' => $vv['floor_id']))->find();
                                $floor_list_info[$vv['floor_id']] = $aFloor;
                            }
                            $vv['floor_name'] = strval($aFloor['floor_name']);
                            $vv['floor_layer'] = strval($aFloor['floor_layer']);
                            $vv['door_type'] = 1; // 小区单元门
                        } else {
                            $vv['floor_name'] = $this->config['house_name'] ? $this->config['house_name'] : L_('小区');
                            $vv['floor_layer'] = L_('大门');
                            $vv['door_type'] = 2; // 小区大门
                        }
                        $vv['door_user_type'] = 2; // 临时访客
                        if ($property_endtime > 0 && $property_endtime < $val['share_end_time']) {
                            $vv['door_end_time'] = $property_endtime>0?date('Y-m-d H:i', $property_endtime):0;
                        } else {
                            $vv['door_end_time'] = $val['share_end_time']>0?date('Y-m-d H:i', $val['share_end_time']):0;
                        }
                        if (isset($vv['door_id']) && $vv['door_id']>0) {
                            $userWhere = array(
                                'user_id' => $uid,
                                'door_fid' => $vv['door_id'],
                            );
                            $aDoorFind = $m_house_village_door_user->field(true)->where($userWhere)->find();
                        } else {
                            $aDoorFind = array();
                        }
                        if ($val['time_limit'] == 2) {
                            $aDoorList[$kk]['open_status'] = 6;    // 门禁超时
                            $aDoorList[$kk]['open_status_txt'] = L_('分享获得门禁已经超时，不能开门');
                            $open_list_info[$vv['village_id']]['door_list'][$key] = $vv;
                        } elseif (!$ban_open_status && empty($aDoorFind) && $vv['floor_id'] == -1) {
                            if (empty($aDoor_is_open) || empty($aDoor_is_open[$key])) {
                                $vv['open_status'] = 1;
                                $vv['open_status_txt'] = L_('可以打开');
                                $aDoor[] = $vv;
                                $aDoor_is_open[$key] = $vv;
                            }
                            $open_list_info[$vv['village_id']]['door_list'][$key] = $vv;
                        } elseif (!$ban_open_status && empty($aDoorFind) && ($village_floor_id==0 || $vv['floor_id'] == $village_floor_id)) {
                            if (empty($aDoor_is_open) || empty($aDoor_is_open[$key])) {
                                $vv['open_status'] = 1;
                                $vv['open_status_txt'] = L_('可以打开');
                                $aDoor[] = $vv;
                                $aDoor_is_open[$key] = $vv;
                            }
                            $open_list_info[$vv['village_id']]['door_list'][$key] = $vv;
                        } elseif (!$ban_open_status && $aDoorFind['status'] == 1 && ($village_floor_id==0 || $vv['floor_id'] == $village_floor_id)) {
                            if ($aDoorFind == 0 || time() > $aDoorFind['end_time']) {
                                if (empty($aDoor_is_open) || empty($aDoor_is_open[$key])) {
                                    $vv['open_status'] = 1;
                                    $vv['open_status_txt'] = L_('可以打开');
                                    $aDoor[] = $vv;
                                    $aDoor_is_open[$key] = $vv;
                                }
                            } else {
                                $vv['open_status']	=	2;	//时间过期
                                $vv['open_status_txt']	=	L_('已过期，请联系物业');
                            }
                            $open_list_info[$vv['village_id']]['door_list'][$key] = $vv;
                        } elseif (!$ban_open_status && $aDoorFind['status'] == 0 && ($village_floor_id==0 || $vv['floor_id'] == $village_floor_id)) {
                            $vv['open_status']	=	4;		//禁止使用
                            $vv['open_status_txt']	=	L_('分享者已被禁止使用');		//禁止使用
                            $open_list_info[$vv['village_id']]['door_list'][$key] = $vv;
                        } elseif ($ban_open_status && ($village_floor_id==0 || $vv['floor_id'] == $village_floor_id)) {
                            $vv['open_status']	=	3;//欠物业费
                            $vv['open_status_txt']	=	L_('分享者已欠物业费');
                            $open_list_info[$vv['village_id']]['door_list'][$key] = $vv;
                        } elseif ($ban_open_status && $vv['floor_id'] == -1) {
                            $vv['open_status']	=	3;//欠物业费
                            $vv['open_status_txt']	=	L_('分享者已欠物业费');
                            $open_list_info[$vv['village_id']]['door_list'][$key] = $vv;
                        }
                        if (empty($open_list_info[$vv['village_id']]['door_list']) && $open_list_info[$vv['village_id']]['have_village_door']) {
                            $open_list_info[$vv['village_id']]['have_floor_door'] = false;
                            $open_list_info[$vv['village_id']]['tip_info'] = L_('分享者入住的单元暂未添加门禁');
                        } else {
                            $open_list_info[$vv['village_id']]['have_floor_door'] = true;
                        }
                    }
                }
            }

        }
        $share_info = array(
            'aDoor' => $aDoor,
            'open_list_info' => $open_list_info,
            'invalid_share_arr' => $invalid_share_arr,
            'is_ly' => $is_ly,
            'is_face' => $is_face,
        );
        return $share_info;
    }

    // 获取门禁密码
    private function new_wxapp_door($aDoor){
        foreach ($aDoor as $key => &$item) {
            if($this->DEVICE_ID=='wxapp'){
				if(strlen($item['door_device_id']) == 17 && substr($item['door_device_id'],-5) == 'BLOCK'){
					$pwdArr = str_split($item['door_psword']);
					$new16Pwd = '';
					foreach($pwdArr as $pwdValue){ 
						$new16Pwd .= '0'.$pwdValue;
					}
					$item['lockData']  = 'EEA2'.substr($item['door_device_id'],0,12).$new16Pwd.date('ymd').'ED';
				}else{
					$lockDate = S($item['door_psword'].$item['door_device_id']);
					if(empty($lockDate)) {
						$return_msg = file_get_contents(($this->config['wxapp_door_server'] ? : 'http://o2o-service-qcloud.pigcms.com:8080/') . "dhpkgcomm/app/wxlockData?DEVICEPSW={$item['door_psword']}&DEVICE_ID={$item['door_device_id']}");
						$return_msg = json_decode($return_msg,true);
						fdump($return_msg,'return_msg',true);
						$item['lockData'] = $return_msg['lockData'];
						S($item['door_psword'].$item['door_device_id'],$item['lockData'],86400);
					}else{
						$item['lockData']  = $lockDate;
					}
                }
            }
            unset($item['door_psword']);
        }
        return $aDoor;
    }
    // 新获取门禁
    private function new_wxapp_door_info($aDoor) {
        $door_info = array();
        foreach ($aDoor as $key => $item) {
            if (isset($item['device_id']) && $item['device_id']>0) {
                continue;
            }
            if($this->DEVICE_ID=='wxapp'){
                if(strlen($item['door_device_id']) == 17 && substr($item['door_device_id'],-5) == 'BLOCK'){
                    $pwdArr = str_split($item['door_psword']);
                    $new16Pwd = '';
                    foreach($pwdArr as $pwdValue){
                        $new16Pwd .= '0'.$pwdValue;
                    }
                    $lockDate = 'EEA2'.substr($item['door_device_id'],0,12).$new16Pwd.date('ymd').'ED';
                    $door_info[$item['door_device_id']] = (object)array($item['door_id'], $lockDate, $item['door_service_uuid'], $item['door_characteristic_uuid']);
                }else{
                    $lockDate = S($item['door_psword'].$item['door_device_id']);
                    if(empty($lockDate)) {
                        $return_msg = file_get_contents(($this->config['wxapp_door_server'] ? : 'http://o2o-service-qcloud.pigcms.com:8080/') . "dhpkgcomm/app/wxlockData?DEVICEPSW={$item['door_psword']}&DEVICE_ID={$item['door_device_id']}");
                        $return_msg = json_decode($return_msg,true);
                        $door_info[$item['door_device_id']] = (object)array($item['door_id'],$return_msg['lockData'], $item['door_service_uuid'], $item['door_characteristic_uuid']);
                        S($item['door_psword'].$item['door_device_id'],$item['lockData'],86400);
                    }else{
                        $door_info[$item['door_device_id']] = (object)array($item['door_id'], $lockDate, $item['door_service_uuid'], $item['door_characteristic_uuid']);
                    }
                }
            }
        }
        return $door_info;
    }

    // 解除所有过期分享绑定
    private function relieve_bind_all_share_user($invalid_share_arr) {
        if ($invalid_share_arr) {
            $m_house_village_bind_share = M('House_village_bind_share');
            $m_house_village_bind_share->where(array('id' => array('in', $invalid_share_arr)))->delete();
        }
    }

    // 获取门禁信息
    public function village_door_info() {
        if(empty($this->_uid)){
            $this->returnCode('20000009');
        }
        $uid = $this->_uid;
        $door_device_id	= I('door_device_id');
        if(empty($door_device_id)){
            $this->returnCode('20090094');
        }
        $condition_door['door_device_id'] = $door_device_id;
        $m_house_village_door = M('House_village_door');
        // 获取门禁信息
        $aDoor_info = $m_house_village_door->field(true)->where($condition_door)->find();
        if ($aDoor_info['village_id'] <= 0) {
            $this->returnCode('60000004');
        }
        // 如果存在查询下对应小区信息
        if ($aDoor_info && $aDoor_info['village_id']>0) {
            $m_house_village = M('House_village');
            $village_info = $m_house_village->where(array('village_id' => $aDoor_info['village_id']))->field('village_name,village_address, owe_property_open_door, owe_property_open_door_day')->find();
            if ($village_info && $village_info['village_name']) {
                $aDoor_info['village_name'] = $village_info['village_name'];
                $aDoor_info['village_address'] = $village_info['village_address'];
            }
        }
        // 如果存在查询对应单元信息
        if ($aDoor_info && $aDoor_info['floor_id']>0) {
            $m_house_village_floor = M('House_village_floor');
            $floor_info = $m_house_village_floor->where(array('floor_id' => $aDoor_info['floor_id']))->field('floor_name,floor_layer')->find();
            if ($floor_info && $floor_info['floor_name']) {
                $aDoor_info['floor_name'] = $floor_info['floor_name'];
                $aDoor_info['floor_layer'] = $floor_info['floor_layer'];
            }
        } elseif ($aDoor_info['floor_id'] == -1) {
            $aDoor_info['floor_name'] = $this->config['house_name'] ? $this->config['house_name'] : L_('小区');
            $aDoor_info['floor_layer'] = L_('大门');
        }
        $door_detail = '【';
        if ($aDoor_info['village_name']) {
            $door_detail .= $aDoor_info['village_name'];
        }
        if ($aDoor_info['floor_name']) {
            $door_detail .= '--'.$aDoor_info['floor_name'].$aDoor_info['floor_layer'];
        }
        if ($aDoor_info['door_name']) {
            $door_detail .= '--'.$aDoor_info['door_name'];
        }
        $door_detail .='】';

        // 如果存在小区id 查询是否是小区关联人员 如果存在查询下对应小区信息
        if ($aDoor_info['village_id'] > 0) {

            $m_house_village_user_bind = M('House_village_user_bind');
            $where['a.uid'] = $uid;
            $where['a.status'] = 1;
            $where['a.village_id'] = $aDoor_info['village_id'];
            if ($aDoor_info['floor_id'] > 0) {
                $where['_string'] = "a.floor_id={$aDoor_info['floor_id']} OR a.type=4";
            }
            $aUserSelect = $m_house_village_user_bind->alias('a')
                ->join('LEFT JOIN pigcms_house_village as b on b.village_id=a.village_id')
                ->distinct(true)
                ->field(array('a.uid','a.type','a.name','a.village_id', 'a.floor_id', 'a.property_price', 'a.property_endtime', 'a.phone','a.open_door_day',  'b.village_name', 'b.village_address', 'b.owe_property_open_door', 'b.owe_property_open_door_day'))
                ->where($where)
                ->select();
             $bind_door_share_msg = $this->bind_door_share_msg($uid, $aDoor_info['village_id'], $aDoor_info['floor_id']);

            if (empty($aUserSelect)) {
                $aUserSelect = array();
            }
            if ($bind_door_share_msg) {
                $aUserSelect =  array_merge($aUserSelect,$bind_door_share_msg);
            }
            $tip = 0;
            $tip_msg = L_('抱歉！检测到您暂无X1的开门权限。',$door_detail);
            if ($aUserSelect) {
                $m_house_worker = M('House_worker');
                $now_user =  D('User')->get_user($uid);
                $now_time = time();
                $userWhere = array(
                    'user_id' => $uid,
                    'door_fid' => $aDoor_info['door_id'],
                );
                $m_house_village_door_user = M('House_village_door_user');
                $aDoorFind = $m_house_village_door_user->field(true)->where($userWhere)->find();
                if ($aDoorFind != 0 && time() < $aDoorFind['end_time']) {
                    $vv['open_status']	=	2;	//时间过期
                    $vv['open_status_txt']	=	L_('抱歉！检测到您暂无X1的开门权限。',$door_detail);
                } elseif($aDoorFind && time() > $aDoorFind['end_time']) {
                    $aDoor_info['tip'] = 1;
                    $aDoor_info['tip_msg'] = L_('可以开门，请重试');
                    $this->returnCode(0,$aDoor_info);
                }

                foreach ($aUserSelect as $v) {
                    if ($v['share_end_time'] && $v['share_end_time'] < $now_time && $tip != 1) {
                        $tip = 6; // 分享门禁过期
                        $tip_msg = L_('抱歉！检测到您的X1的开门权限期限已失效。',$door_detail);
                        continue;
                    }

                    if(isset($v['open_door_day']) && $v['open_door_day']>0){
                        $v['owe_property_open_door_day']=$v['open_door_day'];
                    }
                    if ($v['type'] == 4) {
                        // 获取工作人员信息
                        $worker_info = $m_house_worker->where(array('phone'=>$now_user['phone'],'status'=>array('neq',4), 'village_id' => $v['village_id']))->getField('village_id,wid,phone,status,open_door');
                        if ($worker_info['open_door'] == 1) {
                            $aDoor_info['tip'] = 1;
                            $aDoor_info['tip_msg'] = L_('可以开门，请重试');
                            $this->returnCode(0,$aDoor_info);
                        } elseif ($worker_info && $tip != 1) {
                            $tip = 2; // 工作人员
                            $tip_msg = L_('抱歉！检测到您暂无X1的开门权限。',$door_detail);
                        }
                    } else {
                        // 允许开门情况下继续判断
                        if ($v['property_endtime']) {
                            $owe_property_days = 0;
                            if ( $v['property_endtime'] > 0) {
                                // 计算 当前时间到物业截止时间的天数
                                $owe_property_days = (strtotime(date('y-m-d', time())) - strtotime(date('y-m-d', $v['property_endtime']))) / 86400;
                            }
                            if (!$v['owe_property_open_door'] && $v['property_endtime']>0 && (time() > $v['property_endtime'])) {
                                // 判断  不能欠款延期开门 且 当前时间大于 物业截止时间  开门禁止
                                if ($v['bind_id'] && $v['bind_id']>0) {
                                    $tip = 4; // 分享者是业主欠物业费
                                    $tip_msg = L_('抱歉！检测到您的开门权限分享者已欠物业费，暂无X1的开门权限。',$door_detail);
                                } else {
                                    $tip = 3; // 自己业主欠物业费
                                    $tip_msg = L_('抱歉！检测到您已欠物业费，暂无X1的开门权限。',$door_detail);
                                }
                            } else if ($v['owe_property_open_door_day'] > 0 && $owe_property_days >= 0 && $owe_property_days > $v['owe_property_open_door_day']) {
                                // 判断  可以欠款延期开门 且 延期天数大于0 且  当前时间到物业截止时间的天数存在 且大于 延期天数 开门禁止
                                if ($v['bind_id'] && $v['bind_id']>0) {
                                    $tip = 4; // 工作人员
                                    $tip_msg = L_('抱歉！检测到您的开门权限分享者已欠物业费，暂无X1的开门权限。',$door_detail);
                                } else {
                                    $tip = 3; // 工作人员
                                    $tip_msg = L_('抱歉！检测到您已欠物业费，暂无X1的开门权限。',$door_detail);
                                }
                            } else {
                                $tip = 1;
                                $tip_msg = L_('可以开门，请重试');
                            }
                        } else {
                            $aDoor_info['tip'] = 1;
                            $aDoor_info['tip_msg'] = L_('可以开门，请重试');
                            $this->returnCode(0,$aDoor_info);
                        }
                    }
                }
            } else {
                $aDoor_info['tip'] = 7; // 不是小区相关者
                $aDoor_info['tip_msg'] = L_('抱歉！检测到您不是当前X1业主，暂无X2的开门权限，请先入驻X1',array('X1'=>$this->config['house_name'],'X2'=>$door_detail));
                $this->returnCode(0,$aDoor_info);
            }
            $aDoor_info['tip'] = $tip;
            $aDoor_info['tip_msg'] = $tip_msg;
            $this->returnCode(0,$aDoor_info);
        } else {
            $this->returnCode('60000004');
        }
    }

    // 单个门禁对应分享信息
    private function bind_door_share_msg($uid, $village_id, $floor_id){
        $share_where = array(
            'a.uid' =>$uid,
            'd.status' => 1,
            'd.uid' => 'b.share_uid'
        );
        if ($village_id>0) {
            $share_where['d.village_id'] = $village_id;
        } else {
            $share_where['c.status'] = 1;
        }
        if ($floor_id>0) {
            $share_where['d.floor_id'] = $floor_id;
        }
        $share_info = M('House_village_bind_share')->alias('a')
            ->field('a.uid, a.id as bind_id, b.village_id, b.share_end_time, b.share_uid, c.village_name, c.village_address, d.type, d.name, d.village_id, d.floor_id, d.property_price, d.property_endtime, d.phone,d.open_door_day, c.owe_property_open_door, c.owe_property_open_door_day')
            ->join('LEFT JOIN pigcms_house_village_door_share as b on b.door_share_id=a.door_share_id')
            ->join('LEFT JOIN pigcms_house_village as c on b.village_id=c.village_id')
            ->join('LEFT JOIN pigcms_house_village_user_bind as d on b.share_uid=d.uid and b.village_id=d.village_id')
            ->where($share_where)
            ->select();
        return $share_info;
    }
	// 保存设备的UUID
	public function door_serviceid_save(){
		$result = M('House_village_door')->where(['door_id' => $_POST['doorId']])->data(['door_service_uuid' => $_POST['serviceId']])->save();
		$this->returnCodeOk($result);
	}
	
	// 保存设备的特征值
	public function door_characteristicid_save(){
		$result = M('House_village_door')->where(['door_id' => $_POST['doorId']])->data(['door_characteristic_uuid' => $_POST['characteristicId']])->save();
		$this->returnCodeOk($result);
	}

    public function empty_village_unit_list(){
        if (IS_GET) {
            $url = $this->config['site_url'] . '/wap.php?c=House&a=empty_village_unit_list&village_id='.$_GET['village_id'];
            redirect($url);
        }
        exit;
    }

    // 上传云对讲铃声
    public function upload_cloud_call_bell() {
        if($_FILES['bellFile']['error'] == 4){
            exit(json_encode(array('error'=>1,'msg'=>'没有选择音频')));
        }
        fdump_api(['上传云对讲铃声'.__LINE__,$_POST,$_GET,$_FILES,$this->_uid],'upload/upload_cloud_call_bell_log',1);
        import('ORG.Net.UploadFile');
        $upload = new UploadFile();
        $upload->maxSize = 20*1024*1024;
        $upload->allowExts = array('mp3','m4a','wav');

        $img_mer_id = sprintf("%09d", $this->_uid);
        $rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
        $upload_dir = "./upload/bell/{$rand_num}/";
        if(!is_dir($upload_dir)){
            mkdir($upload_dir, 0777, true);
        }
        $upload->savePath = $upload_dir;// 设置附件上传目录
        if(!$upload->upload()) {// 上传错误提示错误信息
            $msg = $upload->getErrorMsg();
            fdump_api(['上传云对讲铃声错误'.__LINE__,$msg],'upload/upload_cloud_call_bell_log',1);
            $this->returnCodeError($msg);
        }else{// 上传成功 获取上传文件信息
            $info =  $upload->getUploadFileInfo();
            $title = $info[0]['savename'];
            $path = replace_file_domain('/upload/bell/'.$rand_num.'/'.$info[0]['savename']);
            fdump_api(['上传云对讲铃声错误'.__LINE__,$info, $title, $path],'upload/upload_cloud_call_bell_log',1);
            $data = array('path'=>$path,'url' =>'/upload/bell/'.$rand_num.'/'.$info[0]['savename'],'title'=>$title);
            $this->returnCode(0, $data);
        }
    }

    // 设置
    public function user_set() {
        fdump_api(['设置'.__LINE__,$_POST,$this->_uid],'upload/user_set_log',1);
        if(empty($this->_uid)){
            $this->returnCode('20044010');
        }
        $allow_cloud_calls = I('allow_cloud_calls', '1', 'intval');
        $cloud_call_bell = I('cloud_call_bell', '', 'strval');
        $cloud_call_bell_name = I('cloud_call_bell_name', '', 'strval');
        if (!in_array($allow_cloud_calls, [1,2]) && !$cloud_call_bell) {
            $this->returnCodeError('请最好进行一项设置');
        }
        $data = [];
        if ($cloud_call_bell && !$cloud_call_bell_name) {
            $fileInfo = pathinfo(replace_file_domain($cloud_call_bell));
            fdump_api(['$fileInfo'.__LINE__,$fileInfo],'upload/user_set_log',1);
            $cloud_call_bell_name = $fileInfo['basename'];
            $data['cloud_call_bell_name'] = $cloud_call_bell_name?$cloud_call_bell_name:'云呼叫铃声';
        } elseif ($cloud_call_bell && $cloud_call_bell_name) {
            $data['cloud_call_bell_name'] = $cloud_call_bell_name;
        }
        $user_set = M('User_set');
        $info = $user_set->where(['uid' => $this->_uid])->find();
        $user_info = M('User')->where(['uid'=>$this->_uid])->find();
        if ($allow_cloud_calls) {
            $data['allow_cloud_calls'] = $allow_cloud_calls;
        }
        if ($cloud_call_bell) {
            $data['cloud_call_bell'] = $cloud_call_bell;
        }
        if ($user_info['phone']) {
            $data['phone'] = $user_info['phone'];
        }
        $data['uid'] = $this->_uid;
        $data['set_time'] = time();
        if ($info) {
            $user_set->where(['uid'=>$this->_uid])->save($data);
            $data['set_id'] = $info['set_id'];
        } else {
            $set_id = $user_set->add($data);
        }
        fdump_api(['设置'.__LINE__,$data, $user_set->_sql()],'upload/user_set_log',1);
        $data['set_id'] = $set_id;
        unset($data['phone']);
        $this->returnCode(0, $data);
    }

    /**
     * Notes: 获取设置信息
     * @author: wanzy
     * @date_time: 2021/6/16 20:11
     */
    public function user_set_info() {
        if(empty($this->_uid)){
            $this->returnCode('20044010');
        }
        $user_set = M('User_set');
        $info = $user_set->where(['uid' => $this->_uid])->find();
        if (!empty($info)) {
            unset($info['phone']);
            if ($info['cloud_call_bell']) {
                $info['cloud_call_bell_path'] = replace_file_domain($info['cloud_call_bell']);
            } elseif($this->config['house_a5_cloud_bell']) {
                $info['cloud_call_bell_name'] = '';
                $info['cloud_call_bell_path'] = replace_file_domain($this->config['house_a5_cloud_bell']);
            } else {
                $info['cloud_call_bell'] = '';
                $info['cloud_call_bell_path'] = '';
            }
        } else {
            $info = [
                'set_id' => 0,
                'uid' => 0,
                'allow_cloud_calls' => 1,
                'cloud_call_bell_name' => '',
                'cloud_call_bell' => '',
                'cloud_call_bell_path' => '',
            ];
        }
        $this->returnCode(0, $info);
    }

    /**
     * Notes:删除铃声
     * @author: wanzy
     * @date_time: 2021/7/6 9:30
     */
    public function user_del_bell() {
        fdump_api(['删除铃声'.__LINE__,$_POST,$this->_uid],'upload/user_del_bell_log',1);
        if(empty($this->_uid)){
            $this->returnCode('20044010');
        }
        $user_set = M('User_set');
        $info = $user_set->where(['uid' => $this->_uid])->find();
        $data = [];
        $data['cloud_call_bell_name'] = '';
        $data['cloud_call_bell'] = '';
        $data['set_time'] = time();
        if (!empty($info)) {
            $user_set->where(['uid'=>$this->_uid])->save($data);
        }
        $this->returnCode(0, $data);
    }
}
