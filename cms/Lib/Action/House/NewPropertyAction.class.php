<?php
/**
 * Created by PhpStorm.
 * User: 郭先森
 * Date: 2019/10/10
 * Time: 9:28
 */
//物业后台
class NewPropertyAction extends BaseAction{

    protected $village_id;
    protected $property;
    protected $property_id;

    public function _initialize(){
        parent::_initialize();
        $this->village_id = $this->house_session['village_id'];
        $this->property = session('property');
        $this->property_id = $this->property ? $this->property['id']:0;
		$this->assign('property', $this->property);
    }
    public function index()
    {

        $condition_where = array();
        if($_GET['village_name']){
            $this->assign('village_name',$_GET['village_name']);
            $condition_where['village_name'] = array('like', '%' . $_GET['village_name'] . '%');
        }
        if(isset($_GET['province_id']) && $_GET['province_id']){
            $condition_where['province_id'] = $_GET['province_id'];
        }else if(isset($_GET['province_idss']) && $_GET['province_idss']){
            $condition_where['province_id'] = $_GET['province_idss'];
        }
        if(isset($_GET['city_id']) && $_GET['city_id']){
            $condition_where['city_id'] = $_GET['city_id'];
        }else if(isset($_GET['city_idss']) && $_GET['city_idss']){
            $condition_where['city_id'] = $_GET['city_idss'];
        }
        if($_GET['area_id']){
            $condition_where['area_id'] = $_GET['area_id'];
        }
        if(isset($_GET['street_idss']) && $_GET['street_idss']){
            $condition_where['street_id'] = $_GET['street_idss'];
        }
        if(isset($_GET['community_idss']) && $_GET['community_idss']){
            $condition_where['community_id'] = $_GET['community_idss'];
        }
        if($this->property['login_identify'] == 2){
            if($this->property){
                $condition_where['a.village_id'] = array('in',$this->property['menus']);
            }
        }
        $condition_where['a.status'] = array('neq',5);
        $condition_where['a.property_id'] = $this->property_id;
        $count_user = M('House_village')->join(" as a  left  JOIN " . C('DB_PREFIX') . "house_village_config as b ON (a.village_id=b.village_id)")->where($condition_where)->count();

        import('@.ORG.merchant_page');
        $p = new Page($count_user, 10, 'page');
        $field = "a.*,b.village_sort,(CASE WHEN (`status`='0') then 1 WHEN (`status` = 1) then 0 ELSE 2 END) as `t_sort`";
        $dataList = M('House_village')->field($field)->join(" as a  left  JOIN " . C('DB_PREFIX') . "house_village_config as b ON (a.village_id=b.village_id)")->where($condition_where)->order('`b`.`village_sort` DESC,`t_sort` ASC,`a`.`village_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        if ($dataList) {
            $villageIds = array_unique(array_column($dataList,'village_id'));
            $db_house_community_about = D('House_community_about');
            $where = [
                'village_id' => ['in', $villageIds],
                'is_virtual' => 1,
            ];
            $aboutInfoArr = $db_house_community_about->where($where)->getField('village_id, facilities_type');

            $house_admin = M('House_admin');
            foreach ($dataList as &$val) {
                if ($val['village_id']) {
                    $last_time = $house_admin->where(['village_id'=>$val['village_id']])->order('last_time DESC')->getField('last_time');
                    if (intval($last_time)>intval($val['last_time'])) {
                        $val['last_time'] = $last_time;
                    }
                }
                if (isset($aboutInfoArr[$val['village_id']]) && $aboutInfoArr[$val['village_id']]) {
                    $val['facilities_type_text'] = $db_house_community_about->getFacilitiesTypeText($aboutInfoArr[$val['village_id']]);
                }
            }
        }
        $this->assign('dataList',$dataList);
        $this->assign('property_id',$this->property_id);
        $this->assign('pagebar',$p->show());
        $this->assign('totalPage',$p->totalPage);

        $limit_info = D('House_property')->get_room_limit_num($this->property_id);

        $this->assign('limit_info',$limit_info);

       $this->display();
    }

    public function add()
    {
        if(IS_POST) {
           $data['property_id'] = $this->property_id;//物业id
           $data['village_name'] = $_POST['village_name'];
           $data['village_address'] = $_POST['village_address'];
           $data['status'] = 0;
           $data['add_time'] = time();
            $data['province_id']=0;
            $data['city_id'] = 0;
            if(isset($_POST['province_id']) && $_POST['province_id']){
                $data['province_id'] = intval($_POST['province_id']);
            }
            if(isset($_POST['city_id']) && $_POST['city_id']){
                $data['city_id'] = intval($_POST['city_id']);
            }
            if($_POST['province_idss']){
                $data['province_id'] = intval($_POST['province_idss']);
                unset($_POST['province_idss']);
            }
            if($_POST['city_idss']){
                $data['city_id'] = intval($_POST['city_idss']);
                unset($_POST['city_idss']);
            }
            if($_POST['street_idss']){
                $data['street_id'] = intval($_POST['street_idss']);
                unset($_POST['street_idss']);
            }
            if($_POST['community_idss']){
                $data['community_id'] = intval($_POST['community_idss']);
                unset($_POST['community_idss']);
            }
            $data['area_id'] = intval($_POST['area_id']);

            // 去除相关信息
            $village_about_data = [];
            if (isset($_POST['plot_area'])) {
                $village_about_data['plot_area'] = $_POST['plot_area'];
            }
            if (isset($_POST['building_area'])) {
                $village_about_data['building_area'] = $_POST['building_area'];
            }
            if (isset($_POST['public_area'])) {
                $village_about_data['public_area'] = $_POST['public_area'];
            }
            if (isset($_POST['green_area'])) {
                $village_about_data['green_area'] = $_POST['green_area'];
            }
            if (isset($_POST['garage_area'])) {
                $village_about_data['garage_area'] = $_POST['garage_area'];
            }
            if (isset($_POST['design_parking_area'])) {
                $village_about_data['design_parking_area'] = $_POST['design_parking_area'];
            }
            if (isset($_POST['cars_park_num'])) {
                $village_about_data['cars_park_num'] = $_POST['cars_park_num'];
            }
            if (isset($_POST['remark'])) {
                $village_about_data['remark'] = trim($_POST['remark']);
            }
            $data_config = [];
            if (isset($_POST['village_single_support_digit'])) {
                $village_single_support_digit = $_POST['village_single_support_digit'];
                if (!in_array($village_single_support_digit, [2,3])) {
                    $village_single_support_digit = 2;
                }
                $data_config['village_single_support_digit'] = $village_single_support_digit;
                $data_config['single_support_digit_set_time'] = time();
                unset($_POST['village_single_support_digit']);
            }
            $data['diy_village_name']='';
           $add_a = M('House_village')->data($data)->add();
            if($add_a){
                $database_house_village_config = D('House_village_config');
                $data_config['village_id'] = $add_a;
                $database_house_village_config->add($data_config);
                $info_id = M('House_village_info')->where(array('village_id'=>$add_a))->getField('info_id');
                if ($info_id) {
                    $village_about_data['add_time'] = time();
                    M('House_village_info')->where(array('info_id'=>$info_id))->save($village_about_data);
                } else {
                    $village_about_data['village_id'] = $add_a;
                    $village_about_data['property_id'] = $this->property_id;
                    $village_about_data['add_time'] = time();
                    M('House_village_info')->data($village_about_data)->add();
                }
                D('House_village')->update_user_info($add_a);
                $arr = array();
                $arr['param'] = serialize(array('village_id' =>$add_a,'type'=>'village'));
                $arr['plan_time'] = time()+30;
                $arr['space_time'] = 0;
                $arr['add_time'] = time();
                $arr['file'] = 'sub_house_village_third';
                $arr['time_type'] = 1;
                $arr['unique_id'] = 'sub_house_village_third_village_'.$add_a;
                $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                M('process_sub_plan')->data($arr)->add();
                if ($this->property_id) {
                    $db_house_community_about = M('House_community_about');
                    $house_community_about_info = $db_house_community_about->where(['property_id' => $this->property_id, 'village_id' => 0])->find();
                    if (isset($house_community_about_info['is_virtual']) && intval($house_community_about_info['is_virtual']) == 1) {
                        $facilities_type = $house_community_about_info['facilities_type'];
                        $from_user = ['type' => 'property_admin', 'admin_id' => $this->property['id'], 'account' => $this->property['account']];
                        if ($this->property['user_name']) {
                            $from_user['name'] = $this->property['user_name'];
                        } elseif ($this->property['show_account']) {
                            $from_user['name'] = $this->property['show_account'];
                        }
                        if ($this->property['property_phone']) {
                            $from_user['phone'] = $this->property['property_phone'];
                        }
                        if ($this->property['login_identify']) {
                            $from_user['login_identify'] = $this->property['login_identify'];
                        }
                        if ($this->property['sLoginRole']) {
                            $from_user['sLoginRole'] = $this->property['sLoginRole'];
                        }
                        if ($this->property['sLoginUid']) {
                            $from_user['sLoginUid'] = $this->property['sLoginUid'];
                        }
                        $from_user = json_encode($from_user,JSON_UNESCAPED_UNICODE);
                        $data = [
                            'property_id' => $this->property_id, 'village_id' => $add_a, 'facilities_type' => $facilities_type,
                            'from' => 'property_admin', 'from_user' => $from_user, 'is_virtual' => 1, 'add_time' => time(),
                        ];
                        $db_house_community_about->add($data);
                    }
                }
                $this->success('添加成功！',U('index',['iframe' => $_GET['iframe']]));
            }else{
                $this->error('添加失败！');
            }
        }else{
            $this->display();

        }

    }

    public function edit_village()
    {
        $village_id = I('village_id', 0, 'intval');
        if (IS_POST) {
            $village_id = I('post.village_id', 0, 'intval');
            if ($village_id < 1) {
                $this->error('小区ID不存在！');
            }
            $whereArr = array('village_id' => $village_id, 'property_id' => $this->property_id);
            $villageinfo = M('House_village')->where($whereArr)->field('*')->find();
            if (empty($villageinfo)) {
                $this->error('小区数据不存在！');
            }
            $data = array();
            $data['property_id'] = $this->property_id;//物业id
            $data['village_name'] = $_POST['village_name'];
            $data['village_address'] = $_POST['village_address'];
            if (isset($_POST['province_id']) && $_POST['province_id']) {
                $data['province_id'] = intval($_POST['province_id']);
            }
            if (isset($_POST['city_id']) && $_POST['city_id']) {
                $data['city_id'] = intval($_POST['city_id']);
            }
            if ($_POST['province_idss']) {
                $data['province_id'] = intval($_POST['province_idss']);
                unset($_POST['province_idss']);
            }
            if ($_POST['city_idss']) {
                $data['city_id'] = intval($_POST['city_idss']);
                unset($_POST['city_idss']);
            }
            if ($_POST['street_idss']) {
                $data['street_id'] = intval($_POST['street_idss']);
                unset($_POST['street_idss']);
            }
            if ($_POST['community_idss']) {
                $data['community_id'] = intval($_POST['community_idss']);
                unset($_POST['community_idss']);
            }
            $data['area_id'] = intval($_POST['area_id']);

            // 去除相关信息
            $village_about_data = [];
            if (isset($_POST['plot_area'])) {
                $village_about_data['plot_area'] = $_POST['plot_area'];
            }
            if (isset($_POST['building_area'])) {
                $village_about_data['building_area'] = $_POST['building_area'];
            }
            if (isset($_POST['public_area'])) {
                $village_about_data['public_area'] = $_POST['public_area'];
            }
            if (isset($_POST['green_area'])) {
                $village_about_data['green_area'] = $_POST['green_area'];
            }
            if (isset($_POST['garage_area'])) {
                $village_about_data['garage_area'] = $_POST['garage_area'];
            }
            if (isset($_POST['design_parking_area'])) {
                $village_about_data['design_parking_area'] = $_POST['design_parking_area'];
            }
            if (isset($_POST['cars_park_num'])) {
                $village_about_data['cars_park_num'] = $_POST['cars_park_num'];
            }
            if (isset($_POST['remark'])) {
                $village_about_data['remark'] = trim($_POST['remark']);
            }
            if (isset($_POST['desensitization_tel'])) {
                $data['desensitization_tel'] = intval($_POST['desensitization_tel']);
            }
            if (isset($_POST['desensitization_idnum'])) {
                $data['desensitization_idnum'] = intval($_POST['desensitization_idnum']);
            }
            $whereArr = array('village_id' => $village_id, 'property_id' => $this->property_id);
            $ret = M('House_village')->where($whereArr)->data($data)->save();
            if ($village_about_data) {
                $village_info = M('House_village_info')->where(array('village_id' => $village_id))->field('info_id')->find();
                if ($village_info && $village_info['info_id']) {
                    $village_about_data['add_time'] = time();
                    M('House_village_info')->where(array('info_id' => $village_info['info_id']))->save($village_about_data);
                } else {
                    $village_about_data['village_id'] = $village_id;
                    $village_about_data['property_id'] = $this->property_id;
                    $village_about_data['add_time'] = time();
                    M('House_village_info')->data($village_about_data)->add();
                }
            }
            $this->success('保存成功！', U('index', ['iframe' => $_GET['iframe']]));


        } else {
            if ($village_id < 1) {
                $this->error('小区ID不存在！');
            }
            $whereArr = array('village_id' => $village_id, 'property_id' => $this->property_id);
            $villageinfo = M('House_village')->where($whereArr)->field('*')->find();
            if (empty($villageinfo)) {
                $this->error('小区数据不存在！');
            }
            $village_info_other = M('House_village_info')->where(array('village_id' => $village_id))->field('*')->find();
            if ($village_info_other) {
                $villageinfo = array_merge($villageinfo, $village_info_other);
            }
           
            $village_config = M('House_village_config')->where(array('village_id' => $village_id))->field('*')->find();
            if ($village_config) {
                $villageinfo = array_merge($villageinfo, $village_config);
            }
            $house_desensitization_tel=-1;  //未设置
            $config_desensitization=D('Config')->get_config_by_name('house_desensitization_tel');  //手机号脱敏
            if(!empty($config_desensitization)){
                $house_desensitization_tel=$config_desensitization['value'] ? intval($config_desensitization['value']):0;
            }
            $house_desensitization_idnum=-1; //未设置
            $config_desensitization=D('Config')->get_config_by_name('house_desensitization_idnum');  //身份证号脱敏
            if(!empty($config_desensitization)){
                $house_desensitization_idnum=$config_desensitization['value'] ? intval($config_desensitization['value']):0;
            }
            $this->assign('house_desensitization_tel', $house_desensitization_tel);
            $this->assign('house_desensitization_idnum', $house_desensitization_idnum);
            $this->assign('villageinfo', $villageinfo);
            $this->display();
        }

    }

    public function village_login(){
        $database_village = D('House_village');
        $village_id = I('village_id',0,'intval');
        if ($_GET['villageTabLogin']&&!$village_id) {
            $village_id = $database_village->where(['property_id'=> $_GET['villageTabLogin'],'status'=> ['in', [0,1]]])->order('village_id ASC')->getField('village_id');
        }
        $condition_village['village_id'] = $village_id;
        $now_village = $database_village->field(true)->where($condition_village)->find();
        if(empty($now_village) || $now_village['status'] == 2){
            exit('<html><head><script>window.top.toggleMenu(0);window.top.msg(0,"该'.C('config.house_name').'的状态不存在！请查阅。",true,5);window.history.back();</script></head></html>');
        }
        $house_admin_id=I('house_admin_id',0,'intval');
        $wid=I('wid',0,'intval');
        $house_admin='';
        if($house_admin_id>0 && $wid>0){
            //员工关联多个小区切换
            $whereArr=array('id'=>$house_admin_id,'wid'=>$wid,'village_id'=>$village_id);
            $house_admin=D('House_admin')->where($whereArr)->find();
            if($house_admin['status']>1){
                exit('<html><head><script>alert("当前账号【'.$house_admin['account'].'】异常！不能进入'.C('config.house_name').'小区,请检查！");window.history.back();</script></head></html>');
            }
        }
        $property_id=$this->house_session ? $this->house_session['property_id']:0;
        if (($this->property_id !=0 && $this->property_id != $now_village['property_id']) || ($this->property_id==0 && $house_admin_id>0 && $property_id!=$now_village['property_id'])) {
            exit('<html><head><script>alert("当前'.C('config.house_name').'您不能进入！");window.history.back();</script></head></html>');
        }
        if (isset($_GET['villageTabType'])&&$_GET['villageTabType']) {
            // 存储下选择
            D('Config_data')->save_config_data('propertyChange'.$_GET['villageTabType'], $village_id);
        }

        // 是否为平台登录
        $now_village['is_system'] = 2;

        //查询权限 超级管理员赋予所有权限
        $admin_menus = D('House_menu_new')->field(true)->where(array('status'=>1))->select();
        $menus = array();
        foreach ($admin_menus as $value) {
            $menus[] = $value['id'];
        }
        $now_village['menus'] = $menus;
        session('house',$now_village);
        import("@.ORG.Token");
        invoke_v20_service('\app\community\model\service\VillageMenuService/clearCache',['village_id' => $village_id]);

        $v2o_ticket = $_COOKIE['property_access_token'] ? $_COOKIE['property_access_token'] : '';
        $ticket = Token::checkToken($v2o_ticket);
        if ($ticket && $ticket['memberId'] && $ticket['extends']) {
            $user_id = $ticket['memberId'];
            $login_role = $ticket['extends'];
            if ($login_role==D('CommunityAdmin')::SYSTEM_ADMIN_TO_PROPERTY) {
                $user_id = $_GET['village_id'].'_'.$user_id;
                $ticket = Token::createToken($user_id,D('CommunityAdmin')::SYSTEM_ADMIN_GO_PROPERTY_TO_VILLAGE);
            } elseif ($login_role==D('CommunityAdmin')::PROPERTY_ADMIN_LOGIN) {
                $property_id = session('property')['id'];
                $user_id = $_GET['village_id'].'_'.$property_id;
                $ticket = Token::createToken($user_id,D('CommunityAdmin')::PROPERTY_ADMIN_TO_VILLAGE);
            } elseif ($login_role==D('CommunityAdmin')::PROPERTY_USER_LOGIN) {
                $admin_id = session('property')['admin_id'];
                $user_id = $_GET['village_id'].'_'.$admin_id;
                $ticket = Token::createToken($user_id,D('CommunityAdmin')::PROPERTY_USER_TO_VILLAGE);
            }
        }
        if(!$ticket && session('property')['login_identify'] == 2){
            $admin_id = session('property')['admin_id'];
            // 给予小区管理员身份
            $ticket = Token::createToken($_GET['village_id'].'_'.$admin_id,12);
        }elseif (!$ticket){
            // 给予小区管理员身份
            $ticket = Token::createToken($_GET['village_id'],7);
        }
        if($house_admin_id>0 && $wid>0 && $house_admin){
            //$user_id = $_GET['village_id'].'_'.$house_admin_id;
            $ticket = Token::createToken($house_admin_id,D('CommunityAdmin')::VILLAGE_ADMIN_LOGIN);
        }
        setcookie('village_access_token',$ticket,$_SERVER['REQUEST_TIME']+10000000,'/');
        $script_name = trim($_SERVER['SCRIPT_NAME'],'/');

        if($_GET['group_id']){
            redirect($this->config['site_url'].'/shequ.php?c=Group&a=frame_edit&group_id='.$_GET['group_id'].'&system_file='.$script_name);
        }else if($_GET['activity_id']){
            redirect($this->config['site_url'].'/shequ.php?c=Activity&a=frame_edit&id='.$_GET['activity_id'].'&system_file='.$script_name);
        }else if($_GET['appoint_id']){
            redirect($this->config['site_url'].'/shequ.php?c=Appoint&a=frame_edit&appoint_id='.$_GET['appoint_id'].'&system_file='.$script_name);
        }else{
            if($_GET['type'] == 'room'){//房间
                //$url = $this->config['site_url'].'/v20/public/platform/#/village/village.iframe/house_unit_import_village';
                $url = $this->config['site_url'].'/v20/public/platform/#/village/village_api.Room/index';
                redirect($url);
            }elseif($_GET['type'] == 'user'){//业主
                $url = $this->config['site_url'].'/v20/public/platform/#/village/village.iframe/house_user_index';
                redirect($url);
            }elseif($_GET['type'] == 'audit_child'){//待审核家属
                $url = $this->config['site_url'].'/v20/public/platform/#/village/village.iframe/house_user_bind_audit_list';
                redirect($url);
            }elseif($_GET['type'] == 'audit_tenant'){//待审核租客
                $url = $this->config['site_url'].'/v20/public/platform/#/village/village.iframe/house_user_bind_audit_list_tenant';
                redirect($url);
            }elseif($_GET['type'] == 'audit_user'){//待审核业主
                $url = $this->config['site_url'].'/v20/public/platform/#/village/village.iframe/house_user_audit_index';
                redirect($url);
            }elseif($_GET['type'] == 'baoxiu'){//报修
                $url = $this->config['site_url'].'/v20/public/platform/#/community/village/workOrder/orderTongji/orderTongji';
                redirect($url);
            }elseif($_GET['type'] == 'suggest'){//投诉建议
                $url = $this->config['site_url'].'/v20/public/platform/#/community/village/workOrder/orderTongji/orderTongji';
                redirect($url);
            }elseif($_GET['type'] == 'water'){//水电煤
                $url = $this->config['site_url'].'/v20/public/platform/#/village/village.iframe/house_repair_water';
                redirect($url);
            }elseif($_GET['type'] == 'unbind'){//业主解绑
                $url = $this->config['site_url'].'/v20/public/platform/#/village/village.iframe/house_user_audit_unbind';
                redirect($url);
            }elseif($_GET['type'] == 'bbs'){//文章
                $url = $this->config['site_url'].'/v20/public/platform/#/village/village.iframe/house_bbs_index';
                redirect($url);
            }elseif($_GET['type'] == 'express'){//快递
                $url = $this->config['site_url'].'/v20/public/platform/#/village/village.iframe/house_library_express_service_list';
                redirect($url);
            }else{
                $redirect_url = $this->config['site_url'].'/v20/public/platform/#/village/village.iframe/house_index_index';
                if ($house_admin_id > 0 && $wid > 0 && $house_admin) {
                    if ($house_admin && !empty($house_admin['wid'])) {
                        $whereArr = array('r.wid' => $wid, 'r.village_id' => $village_id);
                        $listGroup = M('House_programme_relation')->join(' as r LEFT JOIN ' . C('DB_PREFIX') . 'house_programme p  on p.id = r.pid')->where($whereArr)->order('r.id desc')->field('p.group_id')->select();
                        $groupID = [];
                        if ($listGroup) {
                            foreach ($listGroup as $gvv) {
                                $groupID[] = $gvv['group_id'];
                            }
                        }
                        if ($house_admin['group_id'] > 0) {
                            $groupID[] = $house_admin['group_id'];
                        }
                        $menusArr = array();
                        if ($groupID) {
                            $groupID = array_unique($groupID);
                            $whereTmpArr = array('status' => 1, 'group_id' => array('in', $groupID));
                            $group_menus = M('House_admin_group')->where($whereTmpArr)->field('group_menus')->select();
                            if (!empty($group_menus)) {
                                foreach ($group_menus as $gmv) {
                                    if ($gmv['group_menus']) {
                                        $group_menus = explode(',', $gmv['group_menus']);
                                        $menusArr = array_merge($menusArr, $group_menus);
                                    }
                                }
                            }
                        }
                        if ($house_admin['menus']) {
                            $menusTmp = explode(',', $house_admin['menus']);
                            $menusArr = array_merge($menusArr, $menusTmp);
                        }
                        if ($menusArr) {
                            $menusArr = array_unique($menusArr);
                            $house_admin['menus'] = implode(',', $menusArr);
                        }
                    } elseif ($house_admin && $house_admin['group_id']) {
                        $group = M('House_admin_group')->where(['group_id' => $house_admin['group_id'], 'status' => 1])->field('group_id,group_menus')->find();
                        $house_admin['menus'] = $group['group_menus'] ? $group['group_menus'] : '';
                    }
                    if ($house_admin && $house_admin['menus']) {
                        
                        $dataHouse = [];
                        $dataHouse['login_count'] = $house_admin['login_count'] + 1;
                        $dataHouse['last_time'] = time();
                        D('House_admin')->where(array('id'=>$house_admin['id']))->data($dataHouse)->save();
                        
                        $menus = explode(',', $house_admin['menus']);
                        $map = array('id' => array('in', $menus), 'status' => 1,'is_hide'=>0,'show'=>1,'type'=>array('neq',2));
                        $menusInfo = M('House_menu_new')->where($map)->field('*')->order('fid ASC,sort DESC')->select();
                        if ($menusInfo) {
                            $notInArrId=array(111163,112163,112064,112067,112073,111157,219,222,224,416,45,55,112030,112022);
                            $menusUrlArr=array();
                            foreach ($menusInfo as $key => $value) {
                                if($value['name'] == '新版收银台' || $value['name']=='收银台'){
                                    continue;
                                }
                                if(in_array($value['id'],$notInArrId)){
                                    continue;
                                }
                                if (isset($value['jump_type']) && $value['jump_type'] == 1) {
                                    $url = '/shequ.php?g=House&c=Plugin&a=' . $value['action'] . '&url=' . urlencode(cfg('site_url') . $value['jump_url']);
                                    $value['url'] = $this->config['site_url'] . $url;
                                } elseif (isset($value['jump_type']) && $value['jump_type'] == 2) {
                                    $url = '/shequ.php?g=House&c=Plugin&a=page&url=' . urlencode($value['jump_url']);
                                    $value['url'] = $this->config['site_url'] . $url;
                                } elseif (isset($value['module']) && $value['module']) {
                                    $url = "/shequ.php?g=House&c={$value['module']}&a={$value['action']}";
                                    $value['url'] = $this->config['site_url'] . $url;
                                }
                                $path = '';
                                if ((isset($value['url']) && $value['url']) || (isset($value['jump_type']) && $value['jump_type'] == 3)) {
                                    if (isset($value['jump_type']) && $value['jump_type'] == 3) {
                                        $path = isset($value['path']) && $value['path'] ? $value['path'] : '';
                                    }
                                    if (!$path) {
                                        if (isset($value['jump_type']) && $value['jump_type'] == 3 && isset($value['component']) && $value['component']) {
                                            $path_name = 'house_' . strtolower($value['component']);
                                            $path = "/village/village.iframe/{$path_name}";
                                        } elseif($value['module'] && $value['action']) {
                                            $path_name = 'house_' . strtolower($value['module']) . '_' . strtolower($value['action']);
                                            $path = "/village/village.iframe/{$path_name}";
                                        }
                                    }
                                }
                                if ($path  && 0 ==$value['fid']  && '首页' == $value['name']) {
                                    $redirect_url = $this->config['site_url'].'/v20/public/platform/#/village/village.iframe/house_index_index';
                                    break;
                                    //$menusUrlArr[]=array('id'=>$value['id'],'url'=>$redirect_url,'xsort'=>0,'name'=>$value['name']);
                                } if ($path && $value['fid']>0 &&  ($value['type']<2) ) {
                                    $redirect_url  =  $this->config['site_url'].'/v20/public/platform/#'.$path;
                                    break;
                                    //$menusUrlArr[]=array('id'=>$value['id'],'url'=>$redirect_url,'xsort'=>0,'name'=>$value['name']);
                                }
                            }
                        }
                    }
                    
                }
                fdump_api(['village_id'=>$village_id,'house_admin_id'=>$house_admin_id,'redirect_url'=>$redirect_url],'00village_login',1);
                redirect($redirect_url);
            }

        }
    }

    public function villageTabLogin() {}

    public function ajax_check_village_add()
    {
        $property_id = $_POST['property_id'];
        $house_village_count = M('House_village')->where(array('property_id'=>$property_id))->count();
        $house_property = M('House_property')->field(true)->where(array('id'=>$property_id))->find();
        if($house_property){
//            if($house_property['village_num'] <= $house_village_count){
//                exit(json_encode(array('error'=>'0','msg'=>'已超过所能添加的小区数量，请联系平台工作人员')));
//            }else{
                exit(json_encode(array('error'=>'1','msg'=>'可以添加小区')));
//            }
        }
    }


    public function index_new()
    {
        if (!$_GET['iframe']) {
            $query = $_GET;
            unset($_GET['iframe']);
            $query = http_build_query($query);
            $url = $this->config['site_url'].'/v20/public/platform/#/property/property.iframe/property_index';
            if ($query) {
                $url .= "?&".trim($query, "?&");
            }
            redirect($url);
        }
        $where_villages = ['status' => 1,'property_id'=>$this->property_id];
        if(session('property')['login_identify'] == 2){
            if(session('property')){
                $where_villages['village_id'] = array('in',session('property')['menus']);
            }
        }
        // 房间数量
        $village_arr =  D('House_village')->where($where_villages)->getField('village_id', true);

        // 工作人员
        $worker_count =  D('House_worker')->where(array('is_del'=>'0','status' => 1,'property_id'=>$this->property_id))->count();
        $is_v20 = true;
        if ($is_v20) {
            $propertyGroupUrl = '/property/framework/frameworkList';
        } else {
            $propertyGroupUrl = $this->config['site_url']."/shequ.php?g=House&c=PropertyGroup&a=index&iframe=true";
        }
        $this->assign('propertyGroupUrl',$propertyGroupUrl);
        $this->assign('is_v20',$is_v20);

        // 新版收费生效时间
        $is_new_charge=D('House_village')->checkNewCharge($this->property_id);
        // 房间数量
        $village_arr =  D('House_village')->where(array('status' => 1,'property_id'=>$this->property_id))->getField('village_id', true);
        $sum_money=0;
        if (count($village_arr)>=1){
            foreach ($village_arr as $v){
                if ($is_new_charge){
                   /* $where1=[];
                    $where1[]='village_id'=>$v;
                    $where1[]=['is_discard'=>1];
                    $where1[]=['is_paid'=>1];*/
                    $field1='sum(pay_money) as pay_money,sum(refund_money) as refund_money';
                    $order_info1= D('House_new_pay_order')->where(array('village_id'=>$v,'is_discard'=>1,'is_paid'=>1))->field($field1)->find();
                  //   print_r(D('House_new_pay_order')->_sql());exit;
                    $pay_money=$order_info1['pay_money']-$order_info1['refund_money'];
                    $sum_money=$sum_money+$pay_money;
                  //   print_r($sum_money);exit;
                }else{
                    $before_yesterday = array('income'=>1);
                    $before_yesterday['village_id'] = $v;
                    $village_money = M('Village_money_list')->where($before_yesterday)->sum('money');
                    $sum_money=$sum_money+$village_money;
                }
            }
        }
        $sum_money=round($sum_money,2);
        $this->assign('sum_money',$sum_money);
        $property_info =  D('House_property')->where(array('id'=>$this->property_id))->field('create_time')->find();
        $now_time=time();
        $days=round(($now_time-$property_info['create_time'])/3600/24) ;
        $this->assign('days',$days);

        //小区数量
        $village_count =  count($village_arr);

        $room_counts =  D('House_village_user_vacancy')->where(array('is_del'=>'0','village_id'=>array('in',$village_arr)))->count();
        if (!$room_counts) {
            $room_counts = 0;
        }

        $user_counts =  D('House_village_user_bind')->where(array('type'=>['in', [0,3]],'vacancy_id'=>['gt',0], 'village_id'=>array('in',$village_arr),'status'=>1))->count();
        if (!$user_counts) {
            $user_counts = 0;
        }

        // 业主数量
        $this->assign('worker_count',$worker_count);
        $this->assign('village_count',$village_count);
        $this->assign('room_count',$room_counts);
        $this->assign('user_count',$user_counts);


        // 待审核业主
//        $audit_user_counts =  D('House_village_user_vacancy')->where(array('is_del'=>'0','status'=>'2','village_id'=>array('in',$village_arr)))->count();
        $audit_user_counts =  D('House_village_user_bind')->where(array('type'=>['in', [0,3]],'status'=>'2','village_id'=>array('in',$village_arr)))->count();
        if (!$audit_user_counts) {
            $audit_user_counts = 0;
        }


        // 待审核家属
        $audit_child_counts =  D('House_village_user_bind')->where(array('type'=>1,'status'=>'2','village_id'=>array('in',$village_arr)))->count();
        if (!$audit_child_counts) {
            $audit_child_counts = 0;
        }
        // 待审核租客
        $audit_tenant_counts =  D('House_village_user_bind')->where(array('type'=>2,'status'=>'2','village_id'=>array('in',$village_arr)))->count();
        if (!$audit_tenant_counts) {
            $audit_tenant_counts = 0;
        }

        $cat_fid=D('House_new_repair_cate')->where([
            'village_id'=>['in',$village_arr],
            'cate_name'=>'在线报修',
            'parent_id'=>0,
            'status'=>1,
        ])->getField('id',true);
        // 待处理报修
        $baoxiu_counts =  D('House_new_repair_works_order')->where(['village_id'=>array('in',$village_arr),'cat_fid'=>['in',$cat_fid],'event_status'=>['lt',40]])->count();
        if (!$baoxiu_counts) {
            $baoxiu_counts = 0;
        }
        $cat_fid=D('House_new_repair_cate')->where([
            'village_id'=>['in',$village_arr],
            'cate_name'=>'投诉建议',
            'parent_id'=>0,
            'status'=>1,
        ])->getField('id',true);


        // 待处理投诉建议
        $whereArr=['village_id'=>array('in',$village_arr),'cat_fid'=>['in',$cat_fid],'event_status'=>array('lt',40)];
        $suggest_counts = D('House_new_repair_works_order')->where($whereArr)->count();
        if (!$suggest_counts) {
            $suggest_counts = 0;
        }

        // 待处理水电煤气上报
        $water_counts =  D('House_village_repair_list')->where(array('type'=>2,'status'=>'0','village_id'=>array('in',$village_arr)))->count();
        if (!$water_counts) {
            $water_counts = 0;
        }

        // 待处理快递
        $express_counts =  D('House_village_express')->where(array('status'=>'0','village_id'=>array('in',$village_arr)))->count();
        if (!$express_counts) {
            $express_counts = 0;
        }

        // 待申请解绑
        $unbind_counts =  D('House_village_user_unbind')->where(array('status'=>'1','village_id'=>array('in',$village_arr)))->count();
        if (!$unbind_counts) {
            $unbind_counts = 0;
        }

        // 待审核文章
        $left_join = 'as a left join '.C('DB_PREFIX').'bbs_ban_list as b on `a`.`uid` = `b`.`uid` left join '.C('DB_PREFIX').'bbs_category as c on `a`.`cat_id` = `c`.`cat_id` left join '.C('DB_PREFIX').'bbs as bbs on `bbs`.`bbs_id` = `c`.`bbs_id`';
        $bbs_counts = M('Bbs_aricle')->join($left_join)->where('a.aricle_status=2 AND `bbs`.`third_id` in ('.implode(',',$village_arr) . ')')->count();
        if (!$bbs_counts) {
            $bbs_counts = 0;
        }


        $sum_count=$audit_user_counts+$audit_child_counts+$audit_tenant_counts+$baoxiu_counts+$suggest_counts+$water_counts+$express_counts+$unbind_counts+$bbs_counts;
        $this->assign('sum_count',$sum_count);
        $this->assign('audit_user_count',$audit_user_counts);
        $this->assign('audit_child_count',$audit_child_counts);
        $this->assign('audit_tenant_counts',$audit_tenant_counts);
        $this->assign('baoxiu_count',$baoxiu_counts);
        $this->assign('suggest_count',$suggest_counts);
        $this->assign('water_count',$water_counts);
        $this->assign('express_count',$express_counts);
        $this->assign('unbind_count',$unbind_counts);
        $this->assign('bbs_count',$bbs_counts);

        //物业公告
        $database_house_notice = D('House_notice');
        $notice_list = $database_house_notice->where(['status'=>['neq','-1']])->field(true)->order('`is_top` DESC,`add_time` DESC')->limit(10)->select();
        $this->assign('notice_list',$notice_list);

        $enterprise_wx_corpid = D('House_enterprise_wx_bind')->where(array('bind_id' => $this->property_id, 'bind_type' => 0))->getField('corpid');
        $this->assign('enterprise_wx_corpid',$enterprise_wx_corpid);

        $weixin_bind = D('Weixin_bind')->where(array('mer_id' =>  $this->property_id, 'type' => 2))->find();
        $property_weixin_qrcode_url = '';
        if ($weixin_bind && $weixin_bind['qrcode_url']) {
            $property_weixin_qrcode_url = $this->config['site_url'].'/index.php?c=Image&a=wx_image_download&url='.urlencode($weixin_bind['qrcode_url']).'&path='.$this->property_id;
        }
        $this->assign('property_weixin_qrcode_url',$property_weixin_qrcode_url);


        $news = M('System_news');
        $where = [];
        $where['status'] = 1;
        $plat_news_count = $news->where($where)->count();
        $this->assign('plat_news_count',$plat_news_count);

        $this->display();
    }
    //物业公告详情
    public function notice($id)
    {
        $dbHouseNotice = D('House_notice');
        $where['id'] = $id;
        $info = $dbHouseNotice->field(true)->where($where)->find();
        if(empty($info)){
            $this->error(L_('当前内容不存在！'));
        }
        $this->assign('info',$info);
        $this->display();
    }
    //近七日交易走势
    public function get_week_order_list(){
        //todo 【新版收费】
        $is_new_charge=D('House_village')->checkNewCharge($this->property_id);
        $return['order_money_list'] = array();
        $return['order_count_list'] = array();
        if ($is_new_charge) {
            for($i=6;$i>=0;$i--){
                if($i == 0){
                    $today_zero = strtotime(date('Y-m-d').' 00:00:00');
                }else{
                    $today_zero = strtotime('-'.$i.' day',strtotime(date('Y-m-d').' 00:00:00'));
                }
                $condition=[];
                $condition['property_id'] = $this->property_id;
                $condition['is_paid'] = 1;
                $condition['is_discard'] = 1;
                $condition['_string'] = '(pay_time >= '.$today_zero.' AND pay_time <= '.($today_zero+86400-1).") AND ( refund_money < pay_money)";
                $field1='sum(pay_money) as pay_money,sum(refund_money) as refund_money';
                $order=M('House_new_pay_order')->where($condition)->field($field1)->find();
                $num=M('House_new_pay_order')->where($condition)->count();
                $return['order_money_list'][] = floatval(($order ? $order['pay_money'] - $order['refund_money'] : 0));
                $return['order_count_list'][] =  intval($num);
                $return['xAxis_txt'][] =  date('Y-m-d',$today_zero);
            }
        }else{
            $where_villages = array('is_del'=>'0','property_id'=>$this->property_id);
            if(session('property')['login_identify'] == 2){
                if(session('property')){
                    $where_villages['village_id'] = array('in',session('property')['menus']);
                }
            }
            $village_list =  D('House_village')->where($where_villages)->select();
            $village_ids = [];
            foreach($village_list as $k=>$v){
                $village_ids[] = $v['village_id'];
            }
            $village_id = implode(',',$village_ids);
            for($i=6;$i>=0;$i--){
                if($i == 0){
                    $today_zero = strtotime(date('Y-m-d'));
                }else{
                    $today_zero = strtotime('-'.$i.' day');
                }
                $condition=[];
                $condition['village_id'] = array('in',$village_id);
                $condition['_string'] = 'use_time>'.$today_zero.' AND use_time <='.($today_zero+86400)." AND type <> 'withdraw'";
                $return['order_money_list'][] = floatval(M('Village_money_list')->where($condition)->sum('money'));
                $return['order_count_list'][] =  intval(M('Village_money_list')->where($condition)->count());
                $return['xAxis_txt'][] =  date('Y-m-d',$today_zero);
            }
        }
        $this->success($return);
    }

    public function bill_list()
    {
        $where_villages = array('property_id'=>$this->property_id,'status'=>1);
        if(session('property')['login_identify'] == 2){
            if(session('property')){
                $where_villages['village_id'] = array('in',session('property')['menus']);
            }
        }
        $village_list =  D('House_village')->where($where_villages)->select();
        $is_new_charge=D('House_village')->checkNewCharge($this->property_id);
        foreach($village_list as $k=>&$v){
            if($is_new_charge){
                $condition=[];
                $condition['village_id'] = $v['village_id'];
                $condition['is_paid'] = 1;
                $condition['is_discard'] = 1;
                $pay_time_date=date('Y-m-d');
                $pay_time1_num=strtotime($pay_time_date);
                $pay_time2_num=$pay_time1_num-86400;
                $condition['_string'] = '(pay_time >= '.$pay_time2_num.' AND pay_time < '.$pay_time1_num.") AND ( refund_money < pay_money)";
                $field1='sum(pay_money) as pay_money,sum(refund_money) as refund_money';
                $order=M('House_new_pay_order')->where($condition)->field($field1)->find();
                $v['order_money_list_yesterday']=0;
                if($order && isset($order['pay_money'])){
                    $v['order_money_list_yesterday']= $order['pay_money'] - $order['refund_money'];
                }
                $v['order_money_list_before_yesterday']=0;
                $pay_time1_num=strtotime($pay_time_date) - 86400;
                $pay_time2_num=$pay_time1_num-86400;
                $condition['_string'] = '(pay_time >= '.$pay_time2_num.' AND pay_time < '.$pay_time1_num.") AND ( refund_money < pay_money)";
                $field1='sum(pay_money) as pay_money,sum(refund_money) as refund_money';
                $order=M('House_new_pay_order')->where($condition)->field($field1)->find();
                if($order && isset($order['pay_money'])){
                    $v['order_money_list_before_yesterday']= $order['pay_money'] - $order['refund_money'];
                }

                $v['order_money_list_server_yesterday']=0;
                $pay_time1_num=time();
                $pay_time2_num=$pay_time1_num-7*86400;
                $condition['_string'] = '(pay_time >= '.$pay_time2_num.' AND pay_time < '.$pay_time1_num.") AND ( refund_money < pay_money)";
                $field1='sum(pay_money) as pay_money,sum(refund_money) as refund_money';
                $order=M('House_new_pay_order')->where($condition)->field($field1)->find();
                if($order && isset($order['pay_money'])){
                    $v['order_money_list_server_yesterday']= $order['pay_money'] - $order['refund_money'];
                }
            }else{
                $condition=array();
                $condition['village_id'] = $v['village_id'];
                $condition['_string'] = 'use_time>'.strtotime('-1 day').' AND use_time <='.strtotime(date('Y-m-d'))." AND type <> 'withdraw'";
                $v['order_money_list_yesterday'] = M('Village_money_list')->where($condition)->sum('money');//昨天
                $before_yesterday=array();
                $before_yesterday['village_id'] = $v['village_id'];
                $before_yesterday['_string'] = 'use_time>'.strtotime('-2 day').' AND use_time <='.strtotime('-1 day')." AND type <> 'withdraw'";
                $v['order_money_list_before_yesterday'] = M('Village_money_list')->where($before_yesterday)->sum('money');//前天
                $server_before_yesterday=array();
                $server_before_yesterday['village_id'] = $v['village_id'];
                $server_before_yesterday['_string'] = 'use_time>'.strtotime('-7 day').' AND use_time <='.strtotime(date('Y-m-d'))." AND type <> 'withdraw'";
                $v['order_money_list_server_yesterday'] = M('Village_money_list')->where($server_before_yesterday)->sum('money');//前7天 
            }

        }
        $this->assign('dataList',$village_list);
        $this->display();
    }


    public function room_list(){

        $condition_where = array('a.status'=>'1','a.property_id'=>$this->property_id);
        if(session('property')['login_identify'] == 2){
            if(session('property')){
                $condition_where['a.village_id'] = array('in',session('property')['menus']);
            }
        }
        $count_user = M('House_village')->join(" as a  left  JOIN " . C('DB_PREFIX') . "house_village_config as b ON (a.village_id=b.village_id)")->where($condition_where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 10, 'page');
        $field = "a.*,b.village_sort,(CASE WHEN (`status`='0') then 1 WHEN (`status` = 1) then 0 ELSE 2 END) as `t_sort`";
        $village_list = M('House_village')
            ->field($field)->join(" as a  left  JOIN " . C('DB_PREFIX') . "house_village_config as b ON (a.village_id=b.village_id)")
            ->where($condition_where)->order('`b`.`village_sort` DESC,`t_sort` ASC,`a`.`village_id` DESC')
            ->limit($p->firstRow . ',' . $p->listRows)->select();

        $dataList = [];
        foreach($village_list as $k=>$v){
            $room_count =  D('House_village_user_vacancy')->where(array('is_del'=>'0','village_id'=>$v['village_id']))->count();
            !$room_count && $room_count = 0;
            $dataList[] = array(
                'room_count'=>$room_count,
                'village_name'=>$v['village_name'],
                'village_id'=>$v['village_id'],
            );
        }

        $this->assign('dataList',$dataList);
        $this->assign('pagebar',$p->show());
        $this->display();
    }

    public function user_list()
    {

        $condition_where = array('a.status'=>'1','a.property_id'=>$this->property_id);
        if(session('property')['login_identify'] == 2){
            if(session('property')){
                $condition_where['a.village_id'] = array('in',session('property')['menus']);
            }
        }
        $count_user = M('House_village')->join(" as a  left  JOIN " . C('DB_PREFIX') . "house_village_config as b ON (a.village_id=b.village_id)")->where($condition_where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 10, 'page');
        $field = "a.*,b.village_sort,(CASE WHEN (`status`='0') then 1 WHEN (`status` = 1) then 0 ELSE 2 END) as `t_sort`";
        $village_list = M('House_village')
            ->field($field)->join(" as a  left  JOIN " . C('DB_PREFIX') . "house_village_config as b ON (a.village_id=b.village_id)")
            ->where($condition_where)->order('`b`.`village_sort` DESC,`t_sort` ASC,`a`.`village_id` DESC')
            ->limit($p->firstRow . ',' . $p->listRows)->select();

        $dataList = [];
        foreach($village_list as $k=>$v){
            $user_count =  D('House_village_user_bind')->where(array('type'=>['in', [0,3]], 'status' => 1, 'village_id'=>$v['village_id']))->count();
            !$user_count && $user_count = 0;
            $dataList[] = array(
                'user_count'=>$user_count,
                'village_name'=>$v['village_name'],
                'village_id'=>$v['village_id'],
            );
        }
        
        $this->assign('pagebar',$p->show());

        $this->assign('dataList',$dataList);
        $this->display();
    }

    //待审核业主
    public function audit_user_list()
    {
        $where_bind = [
            'a.status' => 1,
            'a.property_id' => $this->property_id,
            'b.type' => array('in',array(0,3)),
            'b.status' => 2,
        ];
        if(session('property')['login_identify'] == 2){
            if(session('property')){
                $where_bind['a.village_id'] = array('in',session('property')['menus']);
            }
        }
        $count_user = M('House_village')
            ->join('as a left join '.C('DB_PREFIX').'house_village_user_bind b ON a.village_id = b.village_id ')
            ->where($where_bind)
            ->field('a.village_id')
            ->group('a.village_id')
            ->select();
        $count_user = count($count_user);
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 10, 'page');
        $dataList =  D('House_village')
            ->join('as a left join '.C('DB_PREFIX').'house_village_user_bind b ON a.village_id = b.village_id ')
            ->where($where_bind)
            ->field('a.village_id,a.village_name,count(a.village_id) as audit_user_count')
            ->group('a.village_id')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        $this->assign('pagebar',$p->show());
        $this->assign('dataList',$dataList);
        $this->display();
    }

    // 待审核家属
    public function audit_child_list()
    {
        $where_bind = [
            'a.status' => 1,
            'a.property_id' => $this->property_id,
            'b.type' => 1,
            'b.status' => 2,
        ];
        if(session('property')['login_identify'] == 2){
            if(session('property')){
                $where_bind['a.village_id'] = array('in',session('property')['menus']);
            }
        }
        $count_user = M('House_village')
            ->join('as a left join '.C('DB_PREFIX').'house_village_user_bind b ON a.village_id = b.village_id ')
            ->where($where_bind)
            ->field('a.village_id')
            ->group('a.village_id')
            ->select();
        $count_user = count($count_user);
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 10, 'page');
        $dataList =  D('House_village')
            ->join('as a left join '.C('DB_PREFIX').'house_village_user_bind b ON a.village_id = b.village_id ')
            ->where($where_bind)
            ->field('a.village_id,a.village_name,count(a.village_id) as audit_child_count')
            ->group('a.village_id')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        $this->assign('pagebar',$p->show());
        $this->assign('dataList',$dataList);
        $this->display();
    }

    // 待审核租客
    public function audit_tenant_list()
    {
        $where_bind = [
            'a.status' => 1,
            'a.property_id' => $this->property_id,
            'b.type' => 2,
            'b.status' => 2,
        ];
        if(session('property')['login_identify'] == 2){
            if(session('property')){
                $where_bind['a.village_id'] = array('in',session('property')['menus']);
            }
        }
        $count_user = M('House_village')
            ->join('as a left join '.C('DB_PREFIX').'house_village_user_bind b ON a.village_id = b.village_id ')
            ->where($where_bind)
            ->field('a.village_id')
            ->group('a.village_id')
            ->select();
        $count_user = count($count_user);
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 10, 'page');
        $dataList =  D('House_village')
            ->join('as a left join '.C('DB_PREFIX').'house_village_user_bind b ON a.village_id = b.village_id ')
            ->where($where_bind)
            ->field('a.village_id,a.village_name,count(a.village_id) as audit_tenant_count')
            ->group('a.village_id')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        $this->assign('pagebar',$p->show());
        $this->assign('dataList',$dataList);
        $this->display();
    }

    // 待处理报修
    public function baoxiu_list()
    {
        $where_repair = [
            'a.status' => 1,
            'a.property_id' => $this->property_id,
        ];
        if(session('property')['login_identify'] == 2){
            if(session('property')){
                $where_repair['a.village_id'] = array('in',session('property')['menus']);
            }
        }

        $cat_fid=D('House_new_repair_cate')->where([
            'property_id'=>$this->property_id,
            'cate_name'=>'在线报修',
            'parent_id'=>0,
            'status'=>1,
        ])->getField('id',true);

        $where_repair['b.cat_fid']=['in',$cat_fid];
        $where_repair['b.event_status']=['lt',40];
        
        $count_user = M('House_village')
            ->join('as a left join '.C('DB_PREFIX').'house_new_repair_works_order b ON a.village_id = b.village_id ')
            ->where($where_repair)
            ->field('a.village_id')
            ->group('a.village_id')
            ->select();
        $count_user = count($count_user);
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 10, 'page');
        $dataList =  D('House_village')
            ->join('as a left join '.C('DB_PREFIX').'house_new_repair_works_order b ON a.village_id = b.village_id ')
            ->where($where_repair)
            ->field('a.village_id,a.village_name,count(a.village_id) as baoxiu_count')
            ->group('a.village_id')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();

        $this->assign('pagebar',$p->show());
        $this->assign('dataList',$dataList);
        $this->display();
    }

    // 待处理投诉建议
    public function suggest_list()
    {
        $where_repair = [
            'a.status' => 1,
            'a.property_id' => $this->property_id,
        ];
        if(session('property')['login_identify'] == 2){
            if(session('property')){
                $where_repair['a.village_id'] = array('in',session('property')['menus']);
            }
        }
        $cat_fid=D('House_new_repair_cate')->where([
            'property_id'=>$this->property_id,
            'cate_name'=>'投诉建议',
            'parent_id'=>0,
            'status'=>1,
        ])->getField('id',true);

        $where_repair['b.cat_fid']=['in',$cat_fid];

        $count_user = M('House_village')
            ->join('as a left join '.C('DB_PREFIX').'house_new_repair_works_order b ON a.village_id = b.village_id ')
            ->where($where_repair)
            ->field('a.village_id')
            ->group('a.village_id')
            ->select();
        $count_user = count($count_user);
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 10, 'page');
        $dataList =  D('House_village')
            ->join('as a left join '.C('DB_PREFIX').'house_new_repair_works_order b ON a.village_id = b.village_id ')
            ->where($where_repair)
            ->field('a.village_id,a.village_name,count(a.village_id) as suggest_count')
            ->group('a.village_id')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();

        $this->assign('pagebar',$p->show());
        $this->assign('dataList',$dataList);
        $this->display();
    }

    // 待处理水电煤气上报
    public function water_list()
    {
        $where_repair = [
            'a.status' => 1,
            'a.property_id' => $this->property_id,
            'b.type' => 2,
            'b.status' => 0,
        ];
        if(session('property')['login_identify'] == 2){
            if(session('property')){
                $where_repair['a.village_id'] = array('in',session('property')['menus']);
            }
        }
        $count_user = M('House_village')
            ->join('as a left join '.C('DB_PREFIX').'house_village_repair_list b ON a.village_id = b.village_id ')
            ->where($where_repair)
            ->field('a.village_id')
            ->group('a.village_id')
            ->select();
        $count_user = count($count_user);
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 10, 'page');
        $dataList =  D('House_village')
            ->join('as a left join '.C('DB_PREFIX').'house_village_repair_list b ON a.village_id = b.village_id ')
            ->where($where_repair)
            ->field('a.village_id,a.village_name,count(a.village_id) as water_count')
            ->group('a.village_id')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();

        $this->assign('pagebar',$p->show());
        $this->assign('dataList',$dataList);
        $this->display();
    }

    // 待处理快递
    public function express_list()
    {
        $where_repair = [
            'a.status' => 1,
            'a.property_id' => $this->property_id,
            'b.status' => 0,
        ];
        if(session('property')['login_identify'] == 2){
            if(session('property')){
                $where_repair['a.village_id'] = array('in',session('property')['menus']);
            }
        }
        $count_user = M('House_village')
            ->join('as a left join '.C('DB_PREFIX').'house_village_express b ON a.village_id = b.village_id ')
            ->where($where_repair)
            ->field('a.village_id')
            ->group('a.village_id')
            ->select();
        $count_user = count($count_user);
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 10, 'page');
        $dataList =  D('House_village')
            ->join('as a left join '.C('DB_PREFIX').'house_village_express b ON a.village_id = b.village_id ')
            ->where($where_repair)
            ->field('a.village_id,a.village_name,count(a.village_id) as express_count')
            ->group('a.village_id')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();

        $this->assign('pagebar',$p->show());
        $this->assign('dataList',$dataList);
        $this->display();
    }

    // 待申请解绑
    public function unbind_list()
    {
        $where_repair = [
            'a.status' => 1,
            'a.property_id' => $this->property_id,
            'b.status' => 1
        ];
        if(session('property')['login_identify'] == 2){
            if(session('property')){
                $where_repair['a.village_id'] = array('in',session('property')['menus']);
            }
        }
        $count_user = M('House_village')
            ->join('as a left join '.C('DB_PREFIX').'house_village_user_unbind b ON a.village_id = b.village_id ')
            ->where($where_repair)
            ->field('a.village_id')
            ->group('a.village_id')
            ->select();
        $count_user = count($count_user);
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 10, 'page');
        $dataList =  D('House_village')
            ->join('as a left join '.C('DB_PREFIX').'house_village_user_unbind b ON a.village_id = b.village_id ')
            ->where($where_repair)
            ->field('a.village_id,a.village_name,count(a.village_id) as unbind_count')
            ->group('a.village_id')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        $this->assign('pagebar',$p->show());

        $this->assign('dataList',$dataList);
        $this->display();
    }

    // 待审核文章
    public function bbs_list()
    {
        $where_villages = array('is_del'=>'0','property_id'=>$this->property_id);
        if(session('property')['login_identify'] == 2){
            if(session('property')){
                $where_villages['village_id'] = array('in',session('property')['menus']);
            }
        }
        $village_list =  D('House_village')->where($where_villages)->select();
        $dataList = [];
        foreach($village_list as $k=>$v){

            $left_join = 'as a left join '.C('DB_PREFIX').'bbs_ban_list as b on `a`.`uid` = `b`.`uid` left join '.C('DB_PREFIX').'bbs_category as c on `a`.`cat_id` = `c`.`cat_id` left join '.C('DB_PREFIX').'bbs as bbs on `bbs`.`bbs_id` = `c`.`bbs_id`';
            $bbs_count = M('Bbs_aricle')->join($left_join)->where('a.aricle_status=2 AND `bbs`.`third_id` = '.$v['village_id'])->count();
            if($bbs_count){
                $dataList[] = array(
                    'bbs_count'=>$bbs_count,
                    'village_name'=>$v['village_name'],
                    'village_id'=>$v['village_id'],
                );
            }
        }

        $this->assign('dataList',$dataList);
        $this->display();
    }


    public function config()
    {
        $type = isset($_GET['type']) ? intval($_GET['type']) : 0;
        $house_property = M('House_property')->field(true)->where(array('id'=>$this->property_id))->find();

        $digit_info = M('House_property_digit')->field(true)->where(array('property_id'=>$this->property_id))->find();
        if($digit_info){
            $house_property['type'] = $digit_info['type'];
            $house_property['other_digit'] = $digit_info['other_digit'];
            $house_property['meter_digit'] = $digit_info['meter_digit'];
            $house_property['deleteBillMin'] = $digit_info['deleteBillMin'];
        }else{
            $house_property['deleteBillMin'] = 30;
        }

        $this->assign('house_property',$house_property);
        $this->assign('type',$type);
        if ($this->config['property_self_logo']==1) {
            // 开启了自定义
            $house_property_login = $this->config['site_url'].'/v20/public/platform/#/user/community/login?property='.$this->property_id;
            $this->assign('house_property_login',$house_property_login);
        }
        $this->display();
    }




    // 上传图片
    public function ajax_upload_pic(){
        if ($_FILES['imgFile']['error'] != 4) {
            $upload_dir = './upload/house/village/'.date('Ymd').'/'.date('H').'/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            import('ORG.Net.UploadFile');
            $upload = new UploadFile();
            $upload->maxSize = $this->config['group_pic_size'] * 1024 * 1024;
            $upload->allowExts = array('jpg', 'jpeg', 'png', 'gif');
            $upload->allowTypes = array('image/png', 'image/jpg', 'image/jpeg', 'image/gif','application/octet-stream');
            $upload->savePath = $upload_dir;
            $upload->saveRule = 'uniqid';
            if ($upload->upload()) {
                $uploadList = $upload->getUploadFileInfo();
                $title = $uploadList[0]['savename'];
                $url = $upload_dir.$title;
                exit(json_encode(array('error' => 0, 'url' => $url, 'title' => replace_file_domain($url))));
            } else {
                exit(json_encode(array('error' => 1, 'message' => $upload->getErrorMsg())));
            }
        } else {
            exit(json_encode(array('error' => 1, 'message' => '没有选择图片')));
        }
    }


    public function config_edit()
    {
           $house_property = M('House_property')->field(true)->where(array('id'=>$this->property_id))->find();
           if(IS_POST){
               $rtn = 0;
               if($_POST['type'] || isset($_POST['deleteBillMin'])){
                   $other_digit = $_POST['other_digit'];
                   $meter_digit = $_POST['meter_digit'];
                   $deleteBillMin = $_POST['deleteBillMin'];
                   if($other_digit > 4 || $meter_digit > 4){
                       $this->error('参数设置小数数位不能大于4！');
                   }
                   $digit_info['type'] = $_POST['type']?$_POST['type']:1;
                   $digit_info['other_digit'] = $other_digit || strlen($other_digit) > 0?$other_digit:2;
                   $digit_info['property_id'] = $this->property_id;
                   $digit_info['meter_digit'] = $meter_digit || strlen($meter_digit) > 0?$meter_digit:2;
                   $digit_info['deleteBillMin'] = $deleteBillMin;
                   fdump_api(['property_id'=>$this->property_id,'digit_info'=>$digit_info],'000property_config_edit',1);
                   if(M('House_property_digit')->field(true)->where(array('property_id'=>$this->property_id))->find()){
                       $rtn = M('House_property_digit')->where(array('property_id'=>$this->property_id))->data($digit_info)->save();
                   }else{
                       $digit_info['add_time'] = time();
                       $rtn = M('House_property_digit')->data($digit_info)->add();
                   }
               }
               $data = [];
               if($_POST['province_id']){
                   $data['province_id'] = $_POST['province_id'];
               }
               if($_POST['city_id']){
                   $data['city_id'] = $_POST['city_id'];
               }
               if($_POST['area_id']){
                   $data['area_id'] = $_POST['area_id'];
               }
               if (isset($_POST['property_name']) && trim($_POST['property_name']) && trim($_POST['property_name'])!=trim($house_property['property_name'])) {
                   $data['property_name'] = trim($_POST['property_name']);
                   $data['set_name_time'] = time();
               } elseif (isset($_POST['property_name']) && !trim($_POST['property_name'])) {
                   $this->error('请填写物业名称！');
               }
               if (isset($_POST['property_short_name']) && trim($_POST['property_short_name']) && trim($_POST['property_short_name'])!=trim($house_property['property_short_name'])) {
                   $data['property_short_name'] = trim($_POST['property_short_name']);
                   $data['set_short_name_time'] = time();
               }


               if($_POST['pwd'] && isset($this->property['role_id']) && isset($this->property['admin_id'])){
                   if (mb_strlen($_POST['old_pwd']) < 6) {
                       $this->error('密码最少6位！');
                   }
                   $property_admin_info = M('Property_admin')->where(['id'=>$this->property['admin_id']])->find();
                   if ($property_admin_info['pwd'] != md5($_POST['old_pwd'])) {
                       $this->error('原密码不正确！');
                   }
                   $password = md5($_POST['pwd']);
                   unset($_POST['pwd']);
                   $rtn = M('Property_admin')->where(['id'=>$this->property['admin_id']])->save(['pwd'=>$password]);
               }else{
                   if($_POST['pwd']){
                       $_POST['pwd'] = md5($_POST['pwd']);
                       if ($house_property['password'] != md5($_POST['old_pwd'])) {
                           $this->error('原密码不正确！');
                       }
                       if (mb_strlen($_POST['old_pwd']) < 6) {
                           $this->error('密码最少6位！');
                       }
                       $data['password'] = $_POST['pwd'];
                   }else{
                       unset($_POST['pwd']);
                   }
               }
               $long_lat = explode(',',$_POST['long_lat']);
               $_POST['long'] = floatval($long_lat[0]);
               $_POST['lat'] = floatval($long_lat[1]);
               $data['long'] = $_POST['long'];
               $data['lat'] = $_POST['lat'];
               $data['property_address'] = $_POST['property_address'];
               $data['property_phone'] = $_POST['property_phone'];
               $data['property_logo'] = $_POST['property_logo'];
               if ($data['status']==0) {
                   $data['status'] = 1;
               }

               $res = M('House_property')->where(array('id'=>$this->property_id))->data($data)->save();
               if($res || $rtn){
                   $this->success('修改成功！');
               }else{
                   $this->error('修改失败！请检查是否进行修改再重试。');
               }
           }
    }


    public function house_village_owner_info()
    {

        $dataList = M('House_village_data_config')->where(array('property_id'=>$this->property_id))->order('sort desc')->select();
        $this->assign('dataList',$dataList);
        $this->display();
    }

    public function house_village_owner_info_add()
    {
        $property_id = $this->property_id;
        if(IS_POST){
            if(empty($_POST['title'])){
                $this->error('名称不能为空!');
            }

            $_POST['key'] = uniqid() . mt_rand(10000,99999);
            if($_POST['type'] == 3){
                if(empty($_POST['title'])){
                    $this->error('单选内容不能为空!');
                }
            }
            $data['title'] = trim($_POST['title']);
            $data['sort'] = $_POST['sort'] ? intval($_POST['sort']) : 0;
            $data['type'] = $_POST['type'];
            $data['key'] = $_POST['key'] ? $_POST['key'] : '';
            $data['is_display'] = $_POST['is_display'] ? intval($_POST['is_display']) : 0;
            $data['is_must'] = $_POST['is_must'] ? intval($_POST['is_must']) : 0;
            $data['is_close'] = $_POST['is_close'] ? intval($_POST['is_close']) : 0;
            $data['is_syn'] = $_POST['is_syn'] ? intval($_POST['is_syn']) : 0;
            $data['add_time'] = time();
            $data['village_id'] = 0;
            $data['property_id'] = $property_id;
            $where_repeat = [
                'title' => $data['title'],
                'village_id' => 0,
                'property_id' => $property_id,
            ];
            $repeat_id = M('House_village_data_config')->where($where_repeat)->getField('acid');
            if ($repeat_id) {
                $this->error('字段名称已经存在');
            }
            //是否是系统自带

            if($_POST['use_field']){
                $data['use_field'] = implode(',',explode(PHP_EOL, $_POST['use_field']));
            }
            $inset_id = M('House_village_data_config')->data($data)->add();
            if($inset_id){
                // 企业微信同步添加标签
                $data['acid'] = $inset_id;
                D('Property_group')->add_corp_tag($inset_id, $data);
                $this->success('添加成功！',U('house_village_owner_info',['iframe'=>$_GET['iframe']]));
            }else{
                $this->error('添加失败');
            }
        }else{
            $this->display();
        }

    }

    public function house_village_owner_info_edit()
    {
        $property_id = $this->property_id;
        if(IS_POST){
            $id = $_POST['id'];
            if(empty($_POST['title'])){
                $this->error('名称不能为空!');
            }
            if($_POST['type'] == 2){
                if(empty($_POST['title'])){
                    $this->error('单选内容不能为空!');
                }
            }
            $data_config = M('House_village_data_config')->where(array('acid'=>$id))->find();
            if (empty($data_config)) {
                $this->error('编辑对象不存在');
            }
            if($_POST['is_system'] == 0){
                $data['type'] = $_POST['type'];
                $data['title'] = $_POST['title'];
                if($_POST['type'] == 2){
                    if($_POST['use_field']){
                        $data['use_field'] = implode(',',explode(PHP_EOL, $_POST['use_field']));
                    }
                }else{
                    $data['use_field'] = '';
                }
            }
            $data['sort'] = $_POST['sort'] ? intval($_POST['sort']) : 0;
            $data['is_display'] = $_POST['is_display'] ? intval($_POST['is_display']) : 0;
            $data['is_must'] = $_POST['is_must'] ? intval($_POST['is_must']) : 0;
            $data['is_close'] = $_POST['is_close'] ? intval($_POST['is_close']) : 0;
            $data['is_syn'] = $_POST['is_syn'] ? intval($_POST['is_syn']) : 0;
            $data['village_id'] = 0;
            $data['property_id'] = $property_id;
            $where_repeat = [
                'title' => $data['title'],
                'village_id' => 0,
                'property_id' => $property_id,
                'acid' => ['neq', $id]
            ];
            $repeat_id = M('House_village_data_config')->where($where_repeat)->getField('acid');
            if ($repeat_id) {
                $this->error('字段名称已经存在');
            }
            $inset_id = M('House_village_data_config')->where(array('acid'=>$id))->data($data)->save();
            if($inset_id){
                if (1==$data['is_syn']) {
                    if (isset($data['sort']) && intval($data['sort'])!=intval($data_config['sort'])) {
                        $order = intval($data['sort']);
                        $order_set = 1;
                    }
                    if ($data['title'] && $data['title']!=$data_config['title']) {
                        $group_name = $data['title'];
                    }
                    if ($group_name || 1==$order_set) {
                        if (1==$data_config['corp_tag_status'] && $data_config['corp_tag_group_id']) {
                            $group_id = $data_config['corp_tag_group_id'];
                            $data['acid'] = $id;
                            if (!$order_set) {
                                $order = -1;
                            }
                            D('Property_group')->edit_corp_tag($group_id, $group_name, $order);
                        } else {
                            $house_corp_tag_group = M('House_corp_tag_group');
                            $where_tag_group = [];
                            $where_tag_group['group_name'] = $data['title'];
                            $where_tag_group['group_id'] = ['neq', ''];
                            $where_tag_group['property_id'] = $property_id;
                            $corp_tag_group = $house_corp_tag_group->where($where_tag_group)->find();
                            if ($corp_tag_group && $corp_tag_group['group_id']) {
                                $group_id = $corp_tag_group['group_id'];
                                $data['acid'] = $id;
                                if (!$order_set) {
                                    $order = -1;
                                }
                                D('Property_group')->edit_corp_tag($group_id, $group_name, $order);
                            }
                        }
                    } elseif (1!=$data_config['corp_tag_status'] || !$data_config['corp_tag_group_id']) {
                        $house_corp_tag_group = M('House_corp_tag_group');
                        $where_tag_group = [];
                        $where_tag_group['group_name'] = $data['title'];
                        $where_tag_group['group_id'] = ['neq', ''];
                        $where_tag_group['property_id'] = $property_id;
                        $corp_tag_group = $house_corp_tag_group->where($where_tag_group)->find();
                        if (!$corp_tag_group) {
                            $data['acid'] = $id;
                            D('Property_group')->add_corp_tag($id, $data);
                        }
                    }
                } elseif (1==$data_config['is_syn']) {
                    // 如果原来同步现在不同步  删除标签
                    D('Property_group')->del_village_data_config_qy($data_config);
                }
                $this->success('修改成功！',U('house_village_owner_info',['iframe'=>$_GET['iframe']]));
            }else{
                $this->error('修改失败,请检查是否进行了修改!');
            }
        }else{
            $id = $_GET['id'];
            $cate = D('House_village_data_config')->where(array('acid'=>$id))->find();
            if($cate['use_field']){
                $cate['use_field'] = implode(PHP_EOL,explode(',',$cate['use_field']));
            }

            $this->assign('cate',$cate);
            $this->display();
        }
    }

    public function house_village_owner_info_del()
    {
        $where_del = [
            'acid' => $_GET['id'],
            'property_id' => $this->property_id
        ];
        $data_config = M('House_village_data_config')->where($where_del)->find();
        if (empty($data_config)) {
            $this->error('删除对象不存在');
        }
        $id = M('House_village_data_config')->where($where_del)->delete();
        if ($id) {
            // 同步删除标签
            D('Property_group')->del_village_data_config_qy($data_config);
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }
    // 广告
    /**
     * Notes: 轮播图
     * @author: wanzy
     * @date_time: 2021/1/16 10:12
     */
    public function house_advert_list()
    {
        $this->assign('bg_color','white');
        if($_GET['cat_key']){
            $database_adver_category  = D('House_adver_category');
            $condition_adver_category['cat_key'] = $_GET['cat_key'];
            $condition_adver_category['cat_key'] = $_GET['cat_key'];
            $now_category = $database_adver_category->field(true)->where($condition_adver_category)->find();
        }else{
            $now_category = M('House_adver_category')->where(['cat_key' => 'house_app_index_top'])->find();
            if (!$now_category) {
                $this->frame_submit_tips(0,'获取失败！请联系管理员添加【社区:APP/小程序首页顶部轮播图】广告类别~');
            }
        }

        $this->assign('now_category', $now_category);
        $many_city = $this->config['many_city'];
        $database_adver = D('House_adver');

        $condition_adver = [];
        $condition_adver['a.cat_id'] = $now_category['cat_id'];
        $property_id = $this->property_id;
        $where_property = [
            'id' => $property_id
        ];
        $house_property = M('House_property')->where($where_property)->find();
        // 查询下下属广告
        $where_village = [
            'property_id' => $property_id,
            'status' => 1
        ];
        $village_arr = M('House_village')->where($where_village)->getField('village_id', true);
        if (!$village_arr) {
            $village_arr = [];
        }
        $village_string = implode(',', $village_arr);
        $condition_adver['a.status'] = 1;
/*        if ($house_property['area_id']) {
            $condition_adver['_string'] = "((a.area_id={$house_property['area_id']} OR a.area_id=0) AND a.village_id=0) OR a.village_id in ({$village_string})";
        }
        if ($house_property['city_id']) {
            if ($condition_adver['_string']) {
                $condition_adver['_string'] = "((a.city_id={$house_property['city_id']} OR a.city_id=0) AND a.village_id=0) OR a.village_id in ({$village_string})";
            } else {
                $condition_adver['_string'] .= " AND ((a.city_id={$house_property['city_id']} OR a.city_id=0) AND a.village_id=0) OR a.village_id in ({$village_string})";
            }
        }
        if ($house_property['province_id']) {
            if ($condition_adver['_string']) {
                $condition_adver['_string'] .= " AND ((a.province_id={$house_property['province_id']} OR a.province_id=0) AND a.village_id=0) OR a.village_id in ({$village_string})";
            } else {
                $condition_adver['_string'] = "((a.province_id={$house_property['province_id']} OR a.province_id=0) AND a.village_id=0) OR a.village_id in ({$village_string})";
            }
        }
        if (!$condition_adver['_string']) {
            $condition_adver['_string'] = "a.province_id=0 AND a.city_id=0 AND a.area_id=0 AND a.village_id=0";
        } else {
            $condition_adver['_string'] .= " OR (a.province_id=0 AND a.city_id=0 AND a.area_id=0 AND a.village_id=0)";
        }*/

        $condition_adver['_string'] .= " c.village_id in ({$village_string}) ";
        $count = $database_adver
            ->join(" as a LEFT JOIN ".C('DB_PREFIX')."house_adver_record as b ON (b.adver_id=a.id AND b.type=1 AND b.business_type='house_adver')")
            ->where($condition_adver)
            ->group('`a`.`id`')
            ->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, 8, 'page');
        $adver_list = $database_adver->field('a.*,sum(c.look_num) as look_num,sum(c.click_num) as click_num')
            ->join(" as a LEFT JOIN ".C('DB_PREFIX')."house_adver_village_count as c ON (c.adver_id=a.id)")
            ->where($condition_adver)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->group('`a`.`id`')
            ->order('`a`.`id` DESC')
            ->select();

        /*$count = $database_adver
            ->join(" as a LEFT JOIN ".C('DB_PREFIX')."house_adver_record as b ON (b.adver_id=a.id AND b.type=1 AND b.business_type='house_adver')")
            ->where($condition_adver)
            ->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, 8, 'page');
        $adver_list = $database_adver->field('a.*,b.look_num,b.click_num')
            ->join(" as a LEFT JOIN ".C('DB_PREFIX')."house_adver_record as b ON (b.adver_id=a.id AND b.type=1 AND b.business_type='house_adver')")
            ->where($condition_adver)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->order('`a`.`id` DESC')
            ->select();*/
        if ($many_city == 1 && $adver_list) {
            foreach ($adver_list as &$v) {
                $city = M('Area')->field('area_name')->where(array('area_id' => $v['city_id']))->find();
                if (empty($city)) {
                    $v['city_id'] = '通用';
                } else {
                    $v['city_id'] = $city['area_name'];
                }
                if (strpos($v['pic'], '/upload') !== false) {
                    $v['pic'] = replace_file_domain($v['pic']);
                } elseif($v['pic']) {
                    $v['pic'] = replace_file_domain('/upload/adver/'.$v['pic']);
                }
            }
        }
        $this->assign('pagebar',$p->show());
        $this->assign('adver_list', $adver_list);
        $this->assign('many_city', $many_city);
        $this->display();
    }

    /**
     * Notes: 中间轮播广告
     * @author: wanzy
     * @date_time: 2021/1/16 10:12
     */
    public function house_middle_advert_list() {
        $now_category = M('House_adver_category')->where(['cat_key' => 'house_app_index_center'])->find();
        if (!$now_category) {
            // 如果没有直接添加
            $advert_cat = [
                'cat_id' => 2,
                'cat_key' => 'house_app_index_center',
                'cat_name' => '社区:APP/小程序中间轮播图',
                'cat_type' => 0,
                'is_system' => 1,
                'size_info' => '640*206',
            ];
            $cat_id = M('House_adver_category')->data($advert_cat)->add();
            if ($cat_id) {
                $now_category = $advert_cat;
            }
        }

        $this->assign('now_category', $now_category);
        $many_city = $this->config['many_city'];
        $database_adver = D('House_adver');
        $condition_adver = [];
        $condition_adver['a.cat_id'] = $now_category['cat_id'];
        $property_id = $this->property_id;
        $where_property = [
            'id' => $property_id
        ];
        $house_property = M('House_property')->where($where_property)->find();
        // 查询下下属广告
        $where_village = [
            'property_id' => $property_id,
            'status' => 1
        ];
        $village_arr = M('House_village')->where($where_village)->getField('village_id', true);
        if (!$village_arr) {
            $village_arr = [];
        }
        $village_string = implode(',', $village_arr);
        $condition_adver['a.status'] = 1;
        /*if ($house_property['area_id']) {
            $condition_adver['_string'] = "((a.area_id={$house_property['area_id']} OR a.area_id=0) AND a.village_id=0) OR a.village_id in ({$village_string})";
        }
        if ($house_property['city_id']) {
            if ($condition_adver['_string']) {
                $condition_adver['_string'] = "((a.city_id={$house_property['city_id']} OR a.city_id=0) AND a.village_id=0) OR a.village_id in ({$village_string})";
            } else {
                $condition_adver['_string'] .= " AND ((a.city_id={$house_property['city_id']} OR a.city_id=0) AND a.village_id=0) OR a.village_id in ({$village_string})";
            }
        }
        if ($house_property['province_id']) {
            if ($condition_adver['_string']) {
                $condition_adver['_string'] .= " AND ((a.province_id={$house_property['province_id']} OR a.province_id=0) AND a.village_id=0) OR a.village_id in ({$village_string})";
            } else {
                $condition_adver['_string'] = "((a.province_id={$house_property['province_id']} OR a.province_id=0) AND a.village_id=0) OR a.village_id in ({$village_string})";
            }
        }
        if (!$condition_adver['_string']) {
            $condition_adver['_string'] = "a.province_id=0 AND a.city_id=0 AND a.area_id=0 AND a.village_id=0";
        } else {
            $condition_adver['_string'] .= " OR (a.province_id=0 AND a.city_id=0 AND a.area_id=0 AND a.village_id=0)";
        }*/
        $condition_adver['_string'] .= " c.village_id in ({$village_string}) ";
        $count = $database_adver
            ->join(" as a LEFT JOIN ".C('DB_PREFIX')."house_adver_record as b ON (b.adver_id=a.id AND b.type=1 AND b.business_type='house_adver')")
            ->where($condition_adver)
            ->group('`a`.`id`')
            ->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, 8, 'page');
        $adver_list = $database_adver->field('a.*,sum(c.look_num) as look_num,sum(c.click_num) as click_num')
            ->join(" as a LEFT JOIN ".C('DB_PREFIX')."house_adver_village_count as c ON (c.adver_id=a.id)")
            ->where($condition_adver)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->group('`a`.`id`')
            ->order('`a`.`id` DESC')
            ->select();

       /* $count = $database_adver
            ->join(" as a LEFT JOIN ".C('DB_PREFIX')."house_adver_record as b ON (b.adver_id=a.id AND b.type=1 AND b.business_type='house_adver')")
            ->where($condition_adver)
            ->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, 8, 'page');
        $adver_list = $database_adver->field('a.*,b.look_num,b.click_num')
            ->join(" as a LEFT JOIN ".C('DB_PREFIX')."house_adver_record as b ON (b.adver_id=a.id AND b.type=1 AND b.business_type='house_adver')")
            ->where($condition_adver)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->order('`a`.`id` DESC')
            ->select();*/

        if ($many_city == 1 && $adver_list) {
            foreach ($adver_list as &$v) {
                $city = M('Area')->field('area_name')->where(array('area_id' => $v['city_id']))->find();
                if (empty($city)) {
                    $v['city_id'] = '通用';
                } else {
                    $v['city_id'] = $city['area_name'];
                }
                if (strpos($v['pic'], '/upload') !== false) {
                    $v['pic'] = replace_file_domain($v['pic']);
                } elseif($v['pic']) {
                    $v['pic'] = replace_file_domain('/upload/adver/'.$v['pic']);
                }
            }
        }
        $this->assign('pagebar',$p->show());
        $this->assign('adver_list', $adver_list);
        $this->assign('many_city', $many_city);
        $this->display();
    }

    /**
     * Notes: 一键开门广告
     * @author: wanzy
     * @date_time: 2021/1/16 10:13
     */
    public function house_open_advert_list() {
        $now_category = M('House_adver_category')->where(['cat_key' => 'house_open_advert'])->find();
        if (!$now_category) {
            // 如果没有直接添加
            $advert_cat = [
                'cat_id' => 9,
                'cat_key' => 'house_open_advert',
                'cat_name' => '一键开门广告',
                'cat_type' => 0,
                'is_system' => 1,
                'size_info' => '750*988',
            ];
            $cat_id = M('House_adver_category')->data($advert_cat)->add();
            if ($cat_id) {
                $now_category = $advert_cat;
            }
        }

        $this->assign('now_category', $now_category);
        $many_city = $this->config['many_city'];
        $database_adver = D('House_adver');
        $condition_adver = [];
        $condition_adver['a.cat_id'] = $now_category['cat_id'];
        $property_id = $this->property_id;
        $where_property = [
            'id' => $property_id
        ];
        $house_property = M('House_property')->where($where_property)->find();
        // 查询下下属广告
        $where_village = [
            'property_id' => $property_id,
            'status' => 1
        ];
        $village_arr = M('House_village')->where($where_village)->getField('village_id', true);
        if (!$village_arr) {
            $village_arr = [];
        }
        $village_string = implode(',', $village_arr);
        $condition_adver['a.status'] = 1;
        /*if ($house_property['area_id']) {
            $condition_adver1['_string'] = "((a.area_id={$house_property['area_id']} OR a.area_id=0) AND a.village_id=0) OR a.village_id in ({$village_string})";
        }
        if ($house_property['city_id']) {
            if ($condition_adver['_string']) {
                $condition_adver1['_string'] = "((a.city_id={$house_property['city_id']} OR a.city_id=0) AND a.village_id=0) OR a.village_id in ({$village_string})";
            } else {
                $condition_adver1['_string'] .= " AND ((a.city_id={$house_property['city_id']} OR a.city_id=0) AND a.village_id=0) OR a.village_id in ({$village_string})";
            }
        }
        if ($house_property['province_id']) {
            if ($condition_adver['_string']) {
                $condition_adver1['_string'] .= " AND ((a.province_id={$house_property['province_id']} OR a.province_id=0) AND a.village_id=0) OR a.village_id in ({$village_string})";
            } else {
                $condition_adver1['_string'] = "((a.province_id={$house_property['province_id']} OR a.province_id=0) AND a.village_id=0) OR a.village_id in ({$village_string})";
            }
        }
        if (!$condition_adver['_string']) {
            $condition_adver1['_string'] = "a.province_id=0 AND a.city_id=0 AND a.area_id=0 AND a.village_id=0";
        } else {
            $condition_adver1['_string'] .= " OR (a.province_id=0 AND a.city_id=0 AND a.area_id=0 AND a.village_id=0)";
        }*/
        $condition_adver['_string'] .= " c.village_id in ({$village_string}) ";
        $count = $database_adver
            ->join(" as a LEFT JOIN ".C('DB_PREFIX')."house_adver_record as b ON (b.adver_id=a.id AND b.type=1 AND b.business_type='house_adver')")
            ->where($condition_adver)
            ->group('`a`.`id`')
            ->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, 8, 'page');
        $adver_list = $database_adver->field('a.*,sum(c.look_num) as lookNum,sum(c.click_num) as clickNum')
            ->join(" as a LEFT JOIN ".C('DB_PREFIX')."house_adver_village_count as c ON (c.adver_id=a.id)")
            ->where($condition_adver)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->group('`a`.`id`')
            ->order('`a`.`id` DESC')
            ->select();
        fdump($database_adver->getDbError());
        if ($many_city == 1 && $adver_list) {
            foreach ($adver_list as &$v) {
                $city = M('Area')->field('area_name')->where(array('area_id' => $v['city_id']))->find();
                if (empty($city)) {
                    $v['city_id'] = '通用';
                } else {
                    $v['city_id'] = $city['area_name'];
                }
                if (strpos($v['pic'], '/upload') !== false) {
                    $v['pic'] = replace_file_domain($v['pic']);
                } elseif($v['pic']) {
                    $v['pic'] = replace_file_domain('/upload/adver/'.$v['pic']);
                }
            }
        }
        $this->assign('pagebar',$p->show());
        $this->assign('adver_list', $adver_list);
        $this->assign('many_city', $many_city);
        $this->display();
    }

    /**
     * Notes:  app 全屏广告
     * @author: wanzy
     * @date_time: 2021/1/16 10:30
     */
    public function app_fullscreen_adver(){
        $condition = array();

        if ($this->system_session['area_id']) {
            $area_id = $this->system_session['area_id'];
            if ($this->system_session['level'] == 1) {
                $temp = M('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
                if($temp['area_type'] == 1){
                    $city_list = D('Area')->get_arealist_by_areaPid($temp['area_id'],true);
                    $area_id = array();
                    foreach($city_list as $value){
                        $area_id[] = $value['area_id'];
                    }
                }else if($temp['area_type'] == 2){
                    $area_id = $temp['area_id'];
                }else{
                    $area_id = $temp['area_pid'];
                }
            }
            if(is_array($area_id)){
                $condition['city_id'] = array('in',$area_id);
            }else{
                $condition['city_id'] = $area_id;
            }
        }
        $count = M('App_fullscreen_adver')->where($condition)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, 8, 'page');
        $adver_list = M('App_fullscreen_adver')->where($condition)->limit($p->firstRow,$p->listRows)->select();
        $many_city = $this->config['many_city'];
        if ($many_city == 1 && $adver_list) {
            foreach ($adver_list as &$v) {
                $city = M('Area')->field('area_name')->where(array('area_id' => $v['city_id']))->find();
                if (empty($city)) {
                    $v['city_id'] = '通用';
                } else {
                    $v['city_id'] = $city['area_name'];
                }
                if ($v['ios_pic_s']) {
                    $v['pic'] = replace_file_domain($v['ios_pic_s']);
                }
            }
        }
        $this->assign('adver_list',$adver_list);
        $this->assign('pagebar',$p->show());
        $this->display();
    }
}