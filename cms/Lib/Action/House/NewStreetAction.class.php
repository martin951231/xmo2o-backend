<?php
/**
 * Created by PhpStorm.
 * User: 郭先森
 * Date: 2019/10/10
 * Time: 9:28
 */
//物业后台
class NewStreetAction extends BaseAction{

    protected $area_id;
    protected $area_info;
    // 0 街道 1 社区 2小区
    protected $line_color = array(
        0=> '#ff0000',
        1=> '#2681f3',
        2=> '#1b9d18',
    );

    public function _initialize(){
        parent::_initialize();
        $this->area_info = session('house_street');
        $this->area_id = $this->area_info['area_id'];
		$this->assign('area_info', $this->area_info);
    }


    public function index()
    {
        $condition_where = array();
        if($_GET['area_name']){
            $this->assign('area_name',$_GET['area_name']);
            $condition_where['area_name'] = array('like', '%' . $_GET['area_name'] . '%');
        }
        if ($this->area_info['area_type'] == 0) {
            $condition_where['area_pid'] = $this->area_id;
        }
        $count = M('Area_street')->where($condition_where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, 15, 'page');
        $dataList = M('Area_street')->where($condition_where)->order('`area_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('dataList',$dataList);
        $this->assign('area_id',$this->area_id);

        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);

        $this->display();
    }


    public function house_index()
    {

        $condition_where = array();
        $name_data = array();
        if($_GET['village_name']){
            $this->assign('village_name',$_GET['village_name']);
            $condition_where['village_name'] = array('like', '%' . $_GET['village_name'] . '%');
        }
        if($_GET['province_idss']){
            $condition_where['province_id'] = $_GET['province_idss'];
        }
        if($_GET['city_idss']){
            $condition_where['city_id'] = $_GET['city_idss'];
        }
        if($_GET['area_id']){
            $condition_where['area_id'] = $_GET['area_id'];
        }
        if($_GET['street_idss']){
            $condition_where['street_id'] = $_GET['street_idss'];
            $name_data['street_id'] = $_GET['street_idss'];
        }
        if($_GET['community_idss']){
            $condition_where['community_id'] = $_GET['community_idss'];
            $name_data['community_id'] = $_GET['community_idss'];
        }

        if ($this->area_info['area_type'] == 0 && !$condition_where['street_id']) {
            $condition_where['street_id'] = $this->area_id;
            $name_data['street_id'] = $this->area_id;
        } elseif($condition_where['community_id']) {
            $condition_where['community_id'] = $this->area_id;
        }
        $this->title_name($name_data);
        // 只查询正常状态的小区
        $condition_where['status'] = 1;
        if($this->area_info['sLoginRole']==549 && isset($this->area_info['street_worker_id']) && ($this->area_info['street_worker_id']>0)){
            $street_worker=M('Area_street_workers')->where(array('worker_id'=>$this->area_info['street_worker_id']))->find();
            if(!empty($street_worker)){
                $village_ids=!empty($street_worker['village_ids']) ? explode(',',$street_worker['village_ids']):array(0);
                $condition_where['village_id']=array('in',$village_ids);
            }
        }
        $count_user = M('House_village')->where($condition_where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $dataList = M('House_village')->where($condition_where)->order('`village_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $database_house_village_config = D('House_village_config');
        if (!empty($dataList)){
            foreach ($dataList as &$v){
                $village_config = $database_house_village_config->field('village_id,village_sort')->where(array('village_id'=>$v['village_id']))->find();
                $v['village_sort']=$village_config['village_sort'];
            }
        }
        array_multisort(array_column($dataList,'village_sort'),SORT_DESC,$dataList);

        $this->assign('dataList',$dataList);
        $this->assign('pagebar',$p->show());

        $this->display();
    }

    public function add()
    {
        if(IS_POST) {
           $data['property_id'] = $this->property_id;//物业id
           $data['village_name'] = $_POST['village_name'];
           $data['province_id'] = $_POST['province_id'];
           $data['city_id'] = $_POST['city_id'];
           $data['area_id'] = $_POST['area_id'];
           $data['village_address'] = $_POST['village_address'];
           $data['status'] = 3;
           $data['add_time'] = time();
           $add_a = M('House_village')->data($data)->add();
            if($add_a){
                    $this->success('添加成功！',U('index'));
            }else{
                $this->error('添加失败！');
            }
        }else{



        }
       $this->display();
    }

    public function village_login(){
        $database_village = D('House_village');
        $condition_village['village_id'] = $_GET['village_id'];
        $now_village = $database_village->field(true)->where($condition_village)->find();
        if(empty($now_village) || $now_village['status'] == 2){
            exit('<html><head><script>window.top.toggleMenu(0);window.top.msg(0,"该'.C('config.house_name').'的状态不存在！请查阅。",true,5);window.history.back();</script></head></html>');
        }

        // 是否为平台登录
        $now_village['is_system'] = 1;

        //查询权限 超级管理员赋予所有权限
        $admin_menus = D('House_menu')->field(true)->where(array('status'=>1))->select();
        $menus = array();
        foreach ($admin_menus as $value) {
            $menus[] = $value['id'];
        }
        $now_village['menus'] = $menus;
        session('house',$now_village);
//        if($_GET['village_id']){
//            D('House_village')->where(array('village_id'=>$_GET['village_id']))->save(array('last_time'=>time()));
//        }
        $script_name = trim($_SERVER['SCRIPT_NAME'],'/');

        if($_GET['group_id']){
            redirect($this->config['site_url'].'/shequ.php?c=Group&a=frame_edit&group_id='.$_GET['group_id'].'&system_file='.$script_name);
        }else if($_GET['activity_id']){
            redirect($this->config['site_url'].'/shequ.php?c=Activity&a=frame_edit&id='.$_GET['activity_id'].'&system_file='.$script_name);
        }else if($_GET['appoint_id']){
            redirect($this->config['site_url'].'/shequ.php?c=Appoint&a=frame_edit&appoint_id='.$_GET['appoint_id'].'&system_file='.$script_name);
        }else{
            if($_GET['type'] == 'room'){//房间
                redirect($this->config['site_url'].'/shequ.php?g=House&c=Unit&a=import_village');
            }elseif($_GET['type'] == 'user'){//业主
                redirect($this->config['site_url'].'/shequ.php?g=House&c=User&a=index');
            }elseif($_GET['type'] == 'audit_child'){//待审核家属
                redirect($this->config['site_url'].'/shequ.php?g=House&c=User&a=index');
            }elseif($_GET['type'] == 'audit_user'){//待审核业主
                redirect($this->config['site_url'].'/shequ.php?g=House&c=User&a=audit_index');
            }elseif($_GET['type'] == 'baoxiu'){//报修
                redirect($this->config['site_url'].'/shequ.php?g=House&c=Repair&a=index');
            }elseif($_GET['type'] == 'suggest'){//投诉建议
                redirect($this->config['site_url'].'/shequ.php?g=House&c=Repair&a=village_suggest');
            }elseif($_GET['type'] == 'water'){//水电煤
                redirect($this->config['site_url'].'/shequ.php?g=House&c=Repair&a=water');
            }elseif($_GET['type'] == 'unbind'){//业主解绑
                redirect($this->config['site_url'].'/shequ.php?g=House&c=User&a=audit_unbind');
            }elseif($_GET['type'] == 'bbs'){//文章
                redirect($this->config['site_url'].'/shequ.php?g=House&c=Bbs&a=aricle_audit_list');
            }elseif($_GET['type'] == 'express'){//快递
                redirect($this->config['site_url'].'/shequ.php?g=House&c=Library&a=express_service_list');
            }else{
                redirect($this->config['site_url'].'/shequ.php');
            }

        }
    }

    public function ajax_check_village_add()
    {
        $property_id = $_POST['property_id'];
        $house_village_count = M('House_village')->where(array('property_id'=>$property_id))->count();
        $house_property = M('House_property')->field(true)->where(array('id'=>$property_id))->find();
        if($house_property){
            if($house_property['village_num'] <= $house_village_count){
                exit(json_encode(array('error'=>'0','msg'=>'已超过所能添加的小区数量，请联系平台工作人员')));
            }else{
                exit(json_encode(array('error'=>'1','msg'=>'可以添加小区')));
            }
        }
    }


    public function index_new()
    {
        $area_id = $this->house_session['area_id'];
        $community_count=0;
        $is_street_worker_login=0;
        $village_ids=array(0);
        if($this->area_info['sLoginRole']==549 && isset($this->area_info['street_worker_id']) && ($this->area_info['street_worker_id']>0)){
            $street_worker=M('Area_street_workers')->where(array('worker_id'=>$this->area_info['street_worker_id']))->find();
            if(!empty($street_worker)){
                $is_street_worker_login=1;
                $village_ids=!empty($street_worker['village_ids']) ? explode(',',$street_worker['village_ids']):array(0);
            }
        }
        if ($this->area_info['area_type']==0) {
            //小区数量
            $whereArr=array('status'=>1,'street_id'=>$area_id);
            if($is_street_worker_login==1){
                $whereArr['village_id']=array('in',$village_ids);
            }
            $village_count =  D('House_village')->where($whereArr)->count();
            $where_ower = array(
                'h.status' => 1,
                'h.street_id'=>$area_id,
                'b.status'=> array('in',array(1)),
                'b.type'=> array('in',array(0,1,3)),
            );
            $where_room = array(
                'h.status' => 1,
                'h.street_id'=>$area_id,
                'v.status'=> array('in',array(0,1,3)),
                'v.is_del'=> 0,
            );
            $where_tenant = array(
                'h.status' => 1,
                'h.street_id'=>$area_id,
                'b.status'=> array('in',array(1)),
                'b.type'=> array('in',array(2)),
            );
            if($is_street_worker_login==1){
                $where_ower['h.village_id']=array('in',$village_ids);
                $where_room['h.village_id']=array('in',$village_ids);
                $where_tenant['h.village_id']=array('in',$village_ids);
            }
            $house_url = $this->config['site_url'] . "/shequ.php?g=House&c=NewStreet&a=house_index&iframe={$_GET['iframe']}";

            //社区数量
            $community_count = D('Area_street')->where([
                'area_pid'=>$area_id,'area_type'=>1
            ])->count();
        }
        elseif ($this->area_info['area_type']==1) {
            //小区数量
            $village_count =  D('House_village')->where(array('status'=>1,'community_id'=>$area_id))->count();
            $where_ower = array(
                'h.status' => 1,
                'h.community_id'=>$area_id,
                'b.status'=> array('in',array(1)),
                'b.type'=> array('in',array(0,1,3)),
            );
            $where_room = array(
                'h.status' => 1,
                'h.community_id'=>$area_id,
                'v.status'=> array('in',array(0,1,3)),
                'v.is_del'=> 0,
            );
            $where_tenant = array(
                'h.status' => 1,
                'h.community_id'=>$area_id,
                'b.status'=> array('in',array(1)),
                'b.type'=> array('in',array(2)),
            );
            $house_url = $this->config['site_url'] . "/shequ.php?g=House&c=NewStreet&a=house_index&iframe={$_GET['iframe']}&community_idss={$area_id}";
        }
        $this->assign('house_url',$house_url);
        $this->assign('community_url',$this->config['site_url'] . "/shequ.php?g=House&c=NewStreet&a=community_list&iframe={$_GET['iframe']}");

        $room_count = D('House_village')->join("as h LEFT JOIN ".C('DB_PREFIX')."house_village_user_vacancy as v ON h.village_id=v.village_id")->where($where_room)->count();

        // 业主数量
        $ower_count = D('House_village')->join("as h LEFT JOIN ".C('DB_PREFIX')."house_village_user_bind as b ON b.village_id=h.village_id")->where($where_ower)->count();

        // 租户数量
        $tenant_count = D('House_village')->join("as h LEFT JOIN ".C('DB_PREFIX')."house_village_user_bind as b ON b.village_id=h.village_id")->where($where_tenant)->count();

        $this->assign('community_count',$community_count);
        $this->assign('is_community',($this->area_info['area_type']==0 ? true : false));
        $this->assign('ower_count',$ower_count);
        $this->assign('village_count',$village_count);
        $this->assign('room_count',$room_count);
        $this->assign('tenant_count',$tenant_count);

        $news = M('System_news');
        $where = [];
        $where['status'] = 1;
        $plat_news_count = $news->where($where)->count();
        $this->assign('plat_news_count',$plat_news_count);

        $this->display();
    }




    /**网格化管理*/
    public function area_street_custom() {
        $bind_id = isset($_GET['bind_id'])?intval($_GET['bind_id']):$this->area_id;
        $type = isset($_GET['type'])?intval($_GET['type']):$this->area_info['area_type'];
        $fid = isset($_GET['fid'])?intval($_GET['fid']):0;
        $custom_id = isset($_GET['custom_id'])?intval($_GET['custom_id']):0;
        $where = array();

        /* 当前所处层级地图区块信息开始 */
        if ($fid > 0) {
            $where['hvc.fid'] = $fid;
        }elseif ($bind_id > 0) {
            $where['hvc.bind_id'] = $bind_id;
            $where['hvc.type'] = $type;
        } else if ($this->area_id) {
            $where['hvc.bind_id'] = $this->area_id;
            $where['hvc.type'] = $type;
        }
        if ($fid<=0 && $type>0) {
            $fid = $this->area_info['area_pid'];
        }
        $field = 'hvp.*,hvc.type,hvc.fid,hvc.bind_id,hvc.lng,hvc.lat';
        fdump($where,'$where');
        $custom_polygon =  D('House_village_custom_polygon')->get_custom_polygon($where,$field);
        fdump($custom_polygon,'$custom_polygon');
        $custom = array();
        $manage_range_polygon = [];
        $color_arr = array();
        foreach ($custom_polygon as $key=>$value) {
            if (empty($custom)) {
                $custom = array(
                    'id' => $value['custom_id'],
                    'type' => $value['type'],
                    'fid' => $value['fid'],
                    'bind_id' => $value['bind_id'],
                    'lng' => $value['lng'],
                    'lat' => $value['lat'],
                );
                $custom_id = $value['custom_id'];
                if ($value['lng'] && $value['lat']) {
                    $custom['lng_lat'] = $value['lng'] . ',' . $value['lat'];
                }
                $type = $value['type'];
                $bind_id = $value['bind_id'];
            }
            if (!$value['charge_avatar']) {
                $custom_polygon[$key]['charge_avatar'] = C('config.site_url') . '/static/images/user_avatar.jpg';
            }
            $color_arr[$value['id']]['line_color'] = $this->line_color[$value['type']];
            $color_arr[$value['id']]['fill_color'] = $this->line_color[$value['type']];

            $manage_range_polygon[$value['id']] = polygon_2_type($value['manage_range_polygon'],'array');
        }
        $this->assign('custom_id', $custom_id);
        $custom['manage_range_polygon'] = json_encode($manage_range_polygon);
        $custom['polygon_length'] = count($manage_range_polygon);
        /* 当前所处层级地图区块信息结束 */
        /* 存在上级处理上级信息 */
        if ($fid>0) {
            $condition_where['area_id'] = $fid;
            $street_info = M('Area_street')->where($condition_where)->find();
            if ($street_info['area_type']==0) {
                $this->assign('map_size', 16);
            } else {
                $this->assign('map_size', 18);
            }
            $where_street = array();
            $where_street['hvc.bind_id'] = $fid;
            $where_street['hvc.type'] = $street_info['area_type'];
            $field = 'hvp.*,hvc.type,hvc.fid,hvc.bind_id,hvc.lng,hvc.lat';
            $superior_custom_polygon =  D('House_village_custom_polygon')->get_custom_polygon($where_street,$field);
            $superior_custom = array();
            $superior_manage_range_polygon = [];
            foreach ($superior_custom_polygon as $keys=>$val) {
                if (empty($custom)) {
                    $superior_custom = array(
                        'id' => $val['custom_id'],
                        'type' => $val['type'],
                        'fid' => $val['fid'],
                        'bind_id' => $val['bind_id'],
                        'lng' => $val['lng'],
                        'lat' => $val['lat'],
                    );
                    if ($val['lng'] && $val['lat']) {
                        $superior_custom['lng_lat'] = $val['lng'] . ',' . $val['lat'];
                    }
                }
                if (!$val['charge_avatar']) {
                    $superior_custom[$keys]['charge_avatar'] = C('config.site_url') . '/static/images/user_avatar.jpg';
                }

                $superior_manage_range_polygon[$val['id']] = polygon_2_type($val['manage_range_polygon'],'array');
            }
            $superior_custom['manage_range_polygon'] = json_encode($superior_manage_range_polygon);
            $superior_custom['polygon_length'] = count($superior_manage_range_polygon);
            $this->assign('superior_custom_polygon', $superior_custom_polygon);
            $this->assign('superior_custom', $superior_custom);
            $this->assign('superior_line_color',$this->line_color[$street_info['area_type']]);
            $this->assign('superior_fill_color',$this->line_color[$street_info['area_type']]);
        }
        /* 存在上级处理上级信息 */




        if ($_GET['lng'] && $_GET['lat']) {
            $defaultCity['area_lng']=$_GET['lng'];
            $defaultCity['area_lat']=$_GET['lat'];
            $custom['lng'] = $_GET['lng'];
            $custom['lat'] = $_GET['lat'];
            $custom['lng_lat'] = $_GET['lng'] . ',' . $_GET['lat'];
        }elseif (floatval($this->area_info['long'])!=0 || floatval($this->area_info['lat'])!=0) {
            $defaultCity['area_lng']=$this->area_info['long'];
            $defaultCity['area_lat']=$this->area_info['lat'];
            $custom['lng'] = $this->area_info['long'];
            $custom['lat'] = $this->area_info['lat'];
            $custom['lng_lat'] = $this->area_info['lng'] . ',' . $this->area_info['lat'];
        } else {
            if ($fid<=0 && $type>0) {
                $fid = $this->area_info['area_pid'];
            }
            $defaultCity = M('Area_street')->where(array('area_id'=>$fid))->find();
            $defaultCity['area_lng'] = $defaultCity['long'];
            $defaultCity['area_lat'] = $defaultCity['lat'];
            if (floatval($defaultCity['long'])==0 && floatval($defaultCity['long'])==0) {
                $defaultCity = M('Area')->where(array('area_id'=>$this->config['now_city']))->find();
            }
            $custom['lng'] = $defaultCity['area_lng'];
            $custom['lat'] = $defaultCity['area_lat'];
            $custom['lng_lat'] = $defaultCity['area_lng'].','.$defaultCity['area_lat'];
        }

        if($defaultCity){
            $defaultPos = ['lng'=>$defaultCity['area_lng'],'lat'=>$defaultCity['area_lat']];
            $this->assign('defaultPos', $defaultPos);
        }
        $this->assign('custom_polygon', $custom_polygon);
        $this->assign('custom', $custom);

        $this->assign('line_color',$this->line_color[$type]);
        $this->assign('fill_color',$this->line_color[$type]);


        $this->assign('bind_id', $bind_id);
        $this->assign('type', $type);
        $this->assign('fid', $fid);


        $this->display();
    }


    /**
     * 删除自定义区域
     */
    public function delCustom() {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;// 网格下区块id
        $d_house_village_custom_polygon = M('House_village_custom_polygon');
        $info = $d_house_village_custom_polygon->where(array('id'=>$id))->find();
        if (empty($info)) {
            $array = array(
                'errorCode'=>0,
                'result'=>$id
            );
            exit(json_encode($array));
        } else {
            $del = $d_house_village_custom_polygon->where(array('id'=>$id))->delete();
            if (!$del) {
                $array = array(
                    'errorCode'=>1,
                    'errorMsg'=>'删除失败请重试~'
                );
                exit(json_encode($array));
            } else {
                $array = array(
                    'errorCode'=>0,
                    'result'=>$id
                );
                exit(json_encode($array));
            }
        }

    }

    /*图片上传*/

    public function ajax_upload_pic(){
        if($_FILES['imgFile']['error'] != 4){
            $image = D('Image')->handle($this->property_id, 'house', 1);
            fdump($image,'$image');
            if ($image['error']) {
                exit(json_encode($image));
            } else {
                $title = $image['title']['imgFile'];
                $house_image_class = new house_image();
                $url = $house_image_class->get_image_by_path($title);
                exit(json_encode(array('error' => 0, 'url' => $url, 'title' => $title)));
            }
        } else {
            exit(json_encode(array('error' => 1,'message' =>'没有选择图片')));
        }
    }

    /**
     * 保存自定义区域
     */
    public function customSave()
    {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;// 网格下区块id
        $bind_id = isset($_POST['bind_id']) ? intval($_POST['bind_id']) : 0; // 绑定者id 即街道社区小区id
        $custom_id = isset($_POST['custom_id']) ? intval($_POST['custom_id']) : 0;// 网格id
        $type = isset($_POST['type']) ? intval($_POST['type']) : 0;
        $fid = isset($_POST['fid']) ? intval($_POST['fid']) : 0;
        $manage_range_polygon = isset($_POST['manage_range_polygon']) ? htmlspecialchars(trim($_POST['manage_range_polygon'])) : '';
        $lng_lat = isset($_POST['lng_lat']) ? htmlspecialchars(trim($_POST['lng_lat'])) : '';

        if (empty($manage_range_polygon)) {
            $array = array(
                'errorCode'=>1,
                'errorMsg'=>'请绘制区域'
            );
            exit(json_encode($array));
        }
        $d_house_village_custom_polygon = M('House_village_custom_polygon');
        $d_house_village_custom = M('House_village_custom');

        $ret_arr = array(
            'id' => $id,
            'bind_id' => $bind_id,
            'custom_id' => $custom_id,
            'type' => $type,
            'fid' => $fid,
            'manage_range_polygon' => $manage_range_polygon,
            'lng_lat' => $lng_lat,
        );

        if ($id>0) {
            $info = $d_house_village_custom_polygon->where(array('id'=>$id))->find();
            if (empty($info)) {
                $array = array(
                    'errorCode'=>1,
                    'errorMsg'=>'编辑的网格对象不存在或者被删除'
                );
                exit(json_encode($array));
            }
            $data = array(
                'last_time' => time()
            );
            $latLngArray = explode('|', $manage_range_polygon);
            if (count($latLngArray) < 3) {
                $array = array(
                    'errorCode'=>1,
                    'errorMsg'=>'请绘制一个合理的范围'
                );
                exit(json_encode($array));
            } else {
                $latLngData = array();
                foreach ($latLngArray as $row) {
                    $latLng = explode(',', $row);
                    $latLngData[] = $latLng[1] . ' ' . $latLng[0];
                }
                $latLngData[] = $latLngData[0];
                $data['manage_range_polygon'] = 'POLYGON((' . implode(',', $latLngData) . '))';
            }
            $add_id = $d_house_village_custom_polygon->where(array('id'=>$id))->data($data)->save();
            if ($add_id) {
                $array = array(
                    'errorCode'=>0,
                    'result'=>$ret_arr
                );
                exit(json_encode($array));
            } else {
                $array = array(
                    'errorCode'=>1,
                    'errorMsg'=>'操作失败'
                );
                exit(json_encode($array));
            }
        } elseif($custom_id>0) {
            $info = $d_house_village_custom->where(array('id'=>$custom_id))->find();
            if (empty($info)) {
                $array = array(
                    'errorCode'=>1,
                    'errorMsg'=>'编辑的网格对象不存在或者被删除'
                );
                exit(json_encode($array));
            }
            $data = array(
                'custom_id' => $custom_id,
                'last_time' => time()
            );
            $latLngArray = explode('|', $manage_range_polygon);
            if (count($latLngArray) < 3) {
                $array = array(
                    'errorCode'=>1,
                    'errorMsg'=>'请绘制一个合理的范围'
                );
                exit(json_encode($array));
            } else {
                $latLngData = array();
                foreach ($latLngArray as $row) {
                    $latLng = explode(',', $row);
                    $latLngData[] = $latLng[1] . ' ' . $latLng[0];
                }
                $latLngData[] = $latLngData[0];
                $data['manage_range_polygon'] = 'POLYGON((' . implode(',', $latLngData) . '))';
            }
            // 存在对应网格，但是没有对应具体网格信息，直接添加一个新的
            $add_id = $d_house_village_custom_polygon->data($data)->add();
            $ret_arr['id'] = $add_id;
            $d_house_village_custom->where(array('id'=>$custom_id))->setField('last_time',time());
            if ($add_id) {
                $array = array(
                    'errorCode'=>0,
                    'result'=>$ret_arr
                );
                exit(json_encode($array));
            } else {
                $array = array(
                    'errorCode'=>1,
                    'errorMsg'=>'操作失败'
                );
                exit(json_encode($array));
            }
        } elseif($bind_id>0) {
            $where_bind = array(
                'type' => $type,
                'bind_id' => $bind_id,
                'fid' => $fid
            );
            $info = $d_house_village_custom->where($where_bind)->find();
            if (empty($info)) {
                if (empty($lng_lat)) {
                    $array = array(
                        'errorCode'=>1,
                        'errorMsg'=>'请绘制区域'
                    );
                    exit(json_encode($array));
                }
                $bind_data = array(
                    'type' => $type,
                    'bind_id' => $bind_id,
                    'fid' => $fid,
                    'add_time' => time(),
                );
                $lngLat = explode(',', $lng_lat);
                $bind_data['lng'] = $lngLat[0];
                $bind_data['lat'] = $lngLat[1];
                $custom_id = $d_house_village_custom->data($bind_data)->add();
            } else {
                $custom_id = $info['id'];
            }
            $ret_arr['custom_id'] = $custom_id;
            $data = array(
                'custom_id' => $custom_id,
                'last_time' => time()
            );
            $latLngArray = explode('|', $manage_range_polygon);
            if (count($latLngArray) < 3) {
                $array = array(
                    'errorCode'=>1,
                    'errorMsg'=>'请绘制一个合理的范围'
                );
                exit(json_encode($array));
            } else {
                $latLngData = array();
                foreach ($latLngArray as $row) {
                    $latLng = explode(',', $row);
                    $latLngData[] = $latLng[1] . ' ' . $latLng[0];
                }
                $latLngData[] = $latLngData[0];
                $data['manage_range_polygon'] = 'POLYGON((' . implode(',', $latLngData) . '))';
            }
            // 存在对应网格，但是没有对应具体网格信息，直接添加一个新的
            $add_id = $d_house_village_custom_polygon->data($data)->add();
            $ret_arr['id'] = $add_id;
            if ($add_id) {
                $array = array(
                    'errorCode'=>0,
                    'result'=>$ret_arr
                );
                exit(json_encode($array));
            } else {
                $array = array(
                    'errorCode'=>1,
                    'errorMsg'=>'操作失败'
                );
                exit(json_encode($array));
            }
        } else {
            $array = array(
                'errorCode'=>1,
                'errorMsg'=>'参数错误'
            );
            exit(json_encode($array));
        }
    }

    /**
     *
     */
    public function custom_polygon_add() {
        $d_house_village_custom_polygon = M('House_village_custom_polygon');
        if (IS_POST) {
            if (empty($_POST['id']) || empty($_POST['custom_id'])) {
                $this->error('参数有误！');
            }
            if(empty($_POST['polygon_name'])) {
                $this->error('请输入区域名称！');
            }
            if(empty($_POST['charge_name'])) {
                $this->error('请输入网格员姓名！');
            }
            if(empty($_POST['charge_phone'])) {
                $this->error('请输入网格员联系方式！');
            }
            if(empty($_POST['charge_address'])) {
                $this->error('请输入网格员住址！');
            }
            if(empty($_POST['charge_ID'])) {
                $this->error('请输入网格员身份证号！');
            }
            $set = $d_house_village_custom_polygon->data($_POST)->save();
            fdump($d_house_village_custom_polygon,'$d_house_village_custom_polygon');
            if(empty($set)) {
                $this->error('操作失败！');
            } else {
                $data = $d_house_village_custom_polygon->where(array('id' => $_POST['id'], 'custom_id' => $_POST['custom_id']))->find();
                if (!$data['charge_avatar']) {
                    $data['charge_avatar'] = C('config.site_url') . '/static/images/user_avatar.jpg';
                }
                $msg = array('message' => '添加成功！','data' => $data,'status' => 1);
                $this->ajaxReturn($msg);
            }
        } else {
            $id = I('id');
            if ($id>0) {
                $custom_polygon = $d_house_village_custom_polygon->where(array('id' => $id))->find();
                if ($custom_polygon['charge_avatar'])  {
                    $custom_polygon['charge_avatar'] = replace_file_domain($custom_polygon['charge_avatar']);
                } elseif(!$custom_polygon['charge_avatar'] && $_GET['look']==1) {
                    $custom_polygon['charge_avatar'] = C('config.site_url') . '/static/images/user_avatar.jpg';
                }
                $this->assign('custom_polygon',$custom_polygon);
            }
            $this->display();
        }


    }


    /**
     * 多边形坐标转换格式
     * @param $pos
     * @param string $type
     * @return array|string
     */
    function polygon_2_type($pos, $type = 'json')
    {
        $pos = substr($pos, 9, strlen($pos) - 11);
        $lngLatData = explode(',', $pos);
        array_pop($lngLatData);
        $lngLats = [];
        foreach ($lngLatData as $v) {
            $lngLat = explode(' ', $v);
            $lngLats[] = array('lng' => $lngLat[0], 'lat' => $lngLat[1]);
        }
        if($type == 'json'){
            return json_encode([$lngLats]);
        }else if($type == 'array'){
            return $lngLats;
        }
    }


    public function frame_map(){
        $long_lat = $_GET['long_lat'];
        if(!$long_lat){
            if($this->config['map_config'] == 'google'){
                if($_GET['city_id']){
                    $now_city = M('Area')->where(['area_id'=>$_GET['city_id']])->find();
                    $long_lat = $now_city['area_lng'] . ',' . $now_city['area_lat'];
                }
                if(empty($now_city) || getFormatNumber($now_city['area_lat']) == '0'){
                    $now_city = M('Area')->where(['area_id'=>$this->config['now_city']])->find();
                    $long_lat = $now_city['area_lng'] . ',' . $now_city['area_lat'];
                }
            }else{
                $long_lat = '116.331398,39.897445';
            }
        }
        $this->assign('long_lat',$long_lat);
        $this->display();
    }



    /**
     * @param $data
     * array {
     *   'street_id' => '街道id',
     *   'community_id' => '社区id',
     *   'community_id' => '社区id',
     * }
     */
    private function title_name($data) {
        if ($data['street_id']>0) {
            // 获取街道名称
            $street_name = M('Area_street')->where(array('area_id' => $data['street_id']))->getField('area_name');
            $this->assign('street_name',$street_name);
        }
        if ($data['community_id']>0) {
            // 获取社区名称
            $community_name = M('Area_street')->where(array('area_id' => $data['community_id']))->getField('area_name');
            $this->assign('community_name',$community_name);
        } elseif ($data['community_name']) {
            $this->assign('community_name',$data['community_name']);
        }
        if ($data['village_id']>0) {
            // 获取小区名称
            $village_name = M('House_village')->where(array('village_id' => $data['village_id']))->getField('village_name');
            $this->assign('village_name',$village_name);
        } elseif ($data['village_name']) {
            $this->assign('village_name',$data['village_name']);
        }
        if ($data['single_id']>0) {
            // 获取楼栋名称
            $single_name = M('House_village_single')->where(array('id' => $data['single_id']))->getField('single_name');
            $this->assign('single_name',$single_name);
        } elseif ($data['single_name']) {
            $this->assign('single_name',$data['single_name']);
        }
        if ($data['floor_id']>0) {
            // 获取单元名称
            $floor_name = M('House_village_floor')->where(array('floor_id' => $data['floor_id']))->getField('floor_name');
            $this->assign('floor_name',$floor_name);
        } elseif ($data['floor_name']) {
            $this->assign('floor_name',$data['floor_name']);
        }
    }
    /**业主管理*/
    /**
     * 街道下社区列表或者社区下小区列表
     */
    public function community_index() {
        // 判断下如果是街道 显示社区列表
        if ($this->area_info['area_type']==0) {
            $condition_where = array(
                'area_pid' =>   $this->area_id
            );
            if($_GET['area_name']){
                $this->assign('area_name',$_GET['area_name']);
                $condition_where['area_name'] = array('like', '%' . $_GET['area_name'] . '%');
            }
            $count = M('Area_street')->where($condition_where)->count();
            import('@.ORG.merchant_page');
            $p = new Page($count, 20, 'page');
            $list = M('Area_street')->where($condition_where)->order('`area_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
            $this->assign('list', $list);
            $this->assign('pagebar',$p->show());
            $this->display();
        } else {
            redirect(U('house_index',array('community_idss'=>$this->area_id)));
        }

    }
    public function community_list() {
        // 判断下如果是街道 显示社区列表
        if ($this->area_info['area_type']==0) {
            $condition_where = array(
                'area_pid' =>   $this->area_id
            );
            if($_GET['area_name']){
                $this->assign('area_name',$_GET['area_name']);
                $condition_where['area_name'] = array('like', '%' . $_GET['area_name'] . '%');
            }
            $count = M('Area_street')->where($condition_where)->count();
            import('@.ORG.merchant_page');
            $p = new Page($count, 20, 'page');
            $list = M('Area_street')->where($condition_where)->order('`area_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
            $this->assign('list', $list);
            $this->assign('pagebar',$p->show());
            $this->display();
        } else {
            redirect(U('house_index',array('community_idss'=>$this->area_id)));
        }

    }

    /**
     * 社区下楼栋列表
     */
    public function single_index() {
        $condition_where = array();
        $name_data = array();
        if($_GET['single_name']){
            $this->assign('single_name',$_GET['single_name']);
            $condition_where['single_name'] = array('like', '%' . $_GET['single_name'] . '%');
        }
        if($_GET['village_id']){
            $condition_where['village_id'] = $_GET['village_id'];
            $village_info = M('House_village')->where(array('village_id' => $_GET['village_id']))->field('street_id,community_id,village_name')->find();
            $this->assign('village_name',$village_info['village_name']);
            $name_data['street_id'] = $village_info['street_id'];
            $name_data['community_id'] = $village_info['community_id'];
            $this->title_name($name_data);
        }
        $condition_where['status'] = 1;

        $count_user = M('House_village_single')->where($condition_where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $dataList = M('House_village_single')->where($condition_where)->order('`sort` DESC,`id` ASC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('list',$dataList);
        $this->assign('pagebar',$p->show());

        $this->display();
    }

    /**
     * 楼栋下单元列表
     */
    public function floor_index() {
        $condition_where = array();
        $name_data = array();
        if($_GET['floor_name']){
            $this->assign('floor_name',$_GET['floor_name']);
            $condition_where['floor_name'] = array('like', '%' . $_GET['floor_name'] . '%');
        }
        if($_GET['single_idss']){
            $condition_where['single_id'] = $_GET['single_idss'];
            $single_info = M('House_village_single')->where(array('id' => $_GET['single_idss']))->find();
            $this->assign('single_name',$single_info['single_name']);
            $this->assign('single_info',$single_info);
            $village_info = M('House_village')->where(array('village_id' => $single_info['village_id']))->field('street_id,community_id,village_name')->find();
            $this->assign('village_name',$village_info['village_name']);
            $name_data['street_id'] = $village_info['street_id'];
            $name_data['community_id'] = $village_info['community_id'];
            $this->title_name($name_data);
        }

        $condition_where['status'] = 1;
        $count_user = M('House_village_floor')->where($condition_where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $dataList = M('House_village_floor')->where($condition_where)->order('`sort` DESC,`floor_id` ASC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('list',$dataList);
        $this->assign('pagebar',$p->show());

        $this->display();
    }

    /**
     * Notes:单元下的层
     * @author: weili
     * @datetime: 2020/7/29 17:57
     */
    public function layer_index(){
        if($_GET['floor_idss']){
            $condition_where['floor_id'] = $_GET['floor_idss'];
            $floor_info = M('House_village_floor')->where(array('floor_id' => $_GET['floor_idss']))->find();
//            dump($floor_info);die();
            $this->assign('floor_info',$floor_info);
            $this->assign('floor_name',$floor_info['floor_name']);
            $village_info = M('House_village')->where(array('village_id' => $floor_info['village_id']))->field('street_id,community_id,village_name')->find();
            $this->assign('village_name',$village_info['village_name']);
            $name_data['street_id'] = $village_info['street_id'];
            $name_data['community_id'] = $village_info['community_id'];
            $name_data['single_id'] = $floor_info['single_id'];
//            dump($name_data);die();
            $this->title_name($name_data);
        }
        $condition_where['status'] = 1;
        $count_user = M('House_village_layer')->where($condition_where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $village_layer = M('House_village_layer')->where($condition_where)->order('sort desc, id asc')->limit($p->firstRow . ',' . $p->listRows)->select();
        if($village_layer){
            foreach($village_layer as $k=>&$v){
                $single = M('House_village_single')->field('single_name')->where(array('id'=>$v['single_id']))->find();
                $v['single_name'] = $single['single_name'];
                $floor = M('House_village_floor')->field('floor_name')->where(array('floor_id'=>$v['floor_id']))->find();
                $v['floor_name'] = $floor['floor_name'];
            }
        }
        $this->assign('dataList',$village_layer);
        $this->assign('pagebar',$p->show());
        $this->display();
    }
    /**
     * 单元下业主列表
     */
    public function owner_index() {
        $condition_where = array();
        $name_data = array();
        if($_GET['name']){
            $this->assign('name',$_GET['name']);
            $condition_where['name'] = array('like', '%' . $_GET['name'] . '%');
        }
        if($_GET['phone']){
            $this->assign('phone',$_GET['phone']);
            $condition_where['phone'] = array('like', '%' . $_GET['phone'] . '%');
        }
        if($_GET['pigcms_id']){
            $condition_where['pigcms_id'] = $_GET['pigcms_id'];
        }
        if($_GET['layer_ids']){
            $condition_where['layer_id'] = $_GET['layer_ids'];

            $layer_info = M('House_village_layer')->where(array('id' => $_GET['layer_ids']))->find();
            $this->assign('layer_info',$layer_info);
            $this->assign('layer_name',$layer_info['layer_name']);
            $floor_info = M('House_village_floor')->where(array('floor_id' => $layer_info['floor_id']))->find();
            $this->assign('floor_info',$floor_info);
            $this->assign('floor_name',$floor_info['floor_name']);
            $village_info = M('House_village')->where(array('village_id' => $floor_info['village_id']))->field('street_id,community_id,village_name')->find();
            $this->assign('village_name',$village_info['village_name']);
            $name_data['street_id'] = $village_info['street_id'];
            $name_data['community_id'] = $village_info['community_id'];
            $name_data['single_id'] = $floor_info['single_id'];
            $this->title_name($name_data);
        } elseif (isset($_GET['layer_ids']) && $_GET['pigcms_id']) {
            $user = M('House_village_user_bind')->where(['pigcms_id'=> intval($_GET['pigcms_id'])])->find();
            $this->assign('floor_info',[]);
            if (C('config.is_demo_domain')) {
                $test_room = D('House_village')->test_room;
            } else {
                $test_room = '无';
            }
            $this->assign('floor_name',$test_room);
            $village_info = M('House_village')->where(array('village_id' => $user['village_id']))->field('street_id,community_id,village_name')->find();
            $this->assign('village_name',$village_info['village_name']);
            $name_data['street_id'] = $village_info['street_id'];
            $name_data['community_id'] = $village_info['community_id'];
            $name_data['single_id'] = $village_info['single_id'];
            if (0==$this->area_info['area_type'] && !$name_data['street_id']) {
                $name_data['street_id'] = $this->area_id;
            } elseif (!$name_data['street_id'] || !$name_data['community_id']) {
                $name_data['street_id'] = $this->area_info['area_pid'];
                $name_data['community_id'] = $this->area_id;
            }
            if (!$name_data['single_id']) {
                $name_data['single_name'] = '体验楼栋';
            }
            $this->title_name($name_data);
        }
        // 街道或者社区需要查看到所有业主
        $condition_where['status'] = array('in', array(1,2));

        $count_user = M('House_village_user_bind')->where($condition_where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $dataList = M('House_village_user_bind')->where($condition_where)->order('`pigcms_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        if ($dataList) {
            foreach ($dataList as &$val) {
                $val['address'] = D('House_village')->get_single_floor_room($val['single_id'],$val['floor_id'],$val['layer_id'],$val['vacancy_id'],$val['village_id']);
            }
        }
        $this->assign('list',$dataList);
        $this->assign('pagebar',$p->show());

        $this->display();
    }
    //没加楼层之前的备份
//    public function owner_index() {
//        $condition_where = array();
//        $name_data = array();
//        if($_GET['name']){
//            $this->assign('name',$_GET['name']);
//            $condition_where['name'] = array('like', '%' . $_GET['name'] . '%');
//        }
//        if($_GET['phone']){
//            $this->assign('phone',$_GET['phone']);
//            $condition_where['phone'] = array('like', '%' . $_GET['phone'] . '%');
//        }
//        if($_GET['pigcms_id']){
//            $condition_where['pigcms_id'] = $_GET['pigcms_id'];
//        }
//        if($_GET['floor_idss']){
//            $condition_where['floor_id'] = $_GET['floor_idss'];
//            $floor_info = M('House_village_floor')->where(array('floor_id' => $_GET['floor_idss']))->find();
//            $this->assign('floor_info',$floor_info);
//            $this->assign('floor_name',$floor_info['floor_name']);
//            $village_info = M('House_village')->where(array('village_id' => $floor_info['village_id']))->field('street_id,community_id,village_name')->find();
//            $this->assign('village_name',$village_info['village_name']);
//            $name_data['street_id'] = $village_info['street_id'];
//            $name_data['community_id'] = $village_info['community_id'];
//            $name_data['single_id'] = $floor_info['single_id'];
////            dump()
//            $this->title_name($name_data);
//        } elseif (isset($_GET['floor_idss']) && $_GET['pigcms_id']) {
//            $user = M('House_village_user_bind')->where(['pigcms_id'=> intval($_GET['pigcms_id'])])->find();
//            $this->assign('floor_info',[]);
//            $this->assign('floor_name',D('House_village')->test_room);
//            $village_info = M('House_village')->where(array('village_id' => $user['village_id']))->field('street_id,community_id,village_name')->find();
//            $this->assign('village_name',$village_info['village_name']);
//            $name_data['street_id'] = $village_info['street_id'];
//            $name_data['community_id'] = $village_info['community_id'];
//            $name_data['single_id'] = $village_info['single_id'];
//            if (0==$this->area_info['area_type'] && !$name_data['street_id']) {
//                $name_data['street_id'] = $this->area_id;
//            } elseif (!$name_data['street_id'] || !$name_data['community_id']) {
//                $name_data['street_id'] = $this->area_info['area_pid'];
//                $name_data['community_id'] = $this->area_id;
//            }
//            if (!$name_data['single_id']) {
//                $name_data['single_name'] = '体验楼栋';
//            }
//            $this->title_name($name_data);
//        }
//        // 街道或者社区需要查看到所有业主
//        $condition_where['status'] = array('in', array(0,1,2));
//
//        $count_user = M('House_village_user_bind')->where($condition_where)->count();
//        import('@.ORG.merchant_page');
//        $p = new Page($count_user, 20, 'page');
//        $dataList = M('House_village_user_bind')->where($condition_where)->order('`pigcms_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
//        if ($dataList) {
//            foreach ($dataList as &$val) {
//                $val['address'] = D('House_village')->get_single_floor_room($val['single_id'],$val['floor_id'],$val['layer_id'],$val['vacancy_id'],$val['village_id']);
//            }
//        }
//        $this->assign('list',$dataList);
//        $this->assign('pagebar',$p->show());
//
//        $this->display();
//    }
    /**查询对应对象下业主拥有房屋数量*/
    public function ajax_owner_info() {
        $village_id = I('village_id');
        $owner_phone = I('owner_phone');
        if ($village_id > 0) {
            $condition_where['village_id'] = $village_id;
        }
        $condition_where['phone'] = array('like','%'.trim($owner_phone).'%');
        $condition_where['status'] = array('in', array(1,2));
        $condition_where['type'] = array('in', array(0,1,2,3));

        $count_info = D('House_village_user_bind')->field(true)->where($condition_where)->field('pigcms_id,layer_id,floor_id')->select();
        $count = count($count_info);
        $info = array();
        $info['owner_room_num'] = $count;
        if ($count>1) {
            $eject_url = U('NewStreet/owner_list', array('iframe'=> $_GET['iframe'],'village_id' => $village_id,'owner_phone'=>$owner_phone,'community_idss'=>$_POST['community_idss']));
            $info['eject_url'] = $eject_url;
        } elseif($count==1) {
            $msg = reset($count_info);
            $into_url = U('NewStreet/owner_index', array('iframe'=> $_GET['iframe'],'layer_ids' => $msg['layer_id'],'floor_idss' => $msg['floor_id'],'pigcms_id'=>$msg['pigcms_id'],'community_idss'=>$_POST['community_idss']));
            $info['into_url'] = $into_url;
        }
        exit(json_encode($info));
    }


    /**
     * 单元下业主列表
     */
    public function owner_list() {
        $condition_where = array();
        $village_id = I('village_id');
        $owner_phone = I('owner_phone');
        if ($village_id > 0) {
            $condition_where['village_id'] = $village_id;
        }
        $condition_where['phone'] = array('like', '%' . $owner_phone . '%');
        // 街道或者社区需要查看到所有业主
        $condition_where['status'] = array('in', array(1,2));
        $condition_where['type'] = array('in', array(0,1,2,3));

        $count_user = M('House_village_user_bind')->where($condition_where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $dataList = M('House_village_user_bind')->where($condition_where)->order('`pigcms_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();


        if ($dataList) {
            foreach ($dataList as &$val) {
                $val['address'] = D('House_village')->get_single_floor_room($val['single_id'],$val['floor_id'],$val['layer_id'],$val['vacancy_id'],$val['village_id']);
            }
        }
        $this->assign('list',$dataList);
        $this->assign('pagebar',$p->show());

        $this->display();
    }



    // 编辑业主
    public function owner_edit(){
        if (IS_POST) {
            $party_id = $_POST['party'];
            unset($_POST['party']);
            $data_post = $_POST;
            unset($data_post['pigcms_id']);
            unset($data_post['usernum']);
            unset($data_post['name']);
            unset($data_post['phone']);
            unset($data_post['id_card']);
            unset($data_post['ic_card']);
            unset($data_post['attribute']);
            unset($data_post['floor_id']);
            unset($data_post['memo']);
            unset($data_post['memo']);
            unset($data_post['water_price']);
            unset($data_post['electric_price']);
            unset($data_post['gas_price']);
            unset($data_post['park_price']);
            unset($data_post['property_starttime']);
            unset($data_post['property_endtime']);
            unset($data_post['user_political_affiliation']);
            unset($data_post['user_special_groups']);
            unset($data_post['user_focus_groups']);
            unset($data_post['user_vulnerable_groups']);
            unset($data_post['party_type']);
            $str_msg='';
            if(isset($_POST['user_political_affiliation']) && $_POST['user_political_affiliation'] == 1){
                $str_msg='【页面返回后请重新选择党员对应的党员属性、党支部】';
            }
            foreach($data_post as $k=>$v){
                if($v['is_must'] == 1 && empty($v['value'])){
                    $this->error($v['title'].'为必填项！'.$str_msg);
                }
            }
            if($_POST['party_type'] < 1 && $_POST['user_political_affiliation'] == 1){
                $this->error('请选择党员属性！'.$str_msg);
            }
            if($_POST['user_political_affiliation'] != 1 || $_POST['party_type'] != 1){
                $party_id = 0;
            }
            if(empty($_POST['id_card'])){
                $this->error('身份证号不能为空！'.$str_msg);
            }
            if(is_idcard($_POST['id_card']) == false){
                $this->error('请填写正确的身份证号码'.$str_msg);
            }
            if (!empty($_POST['phone']) && !preg_match('/^[0-9]{11}$/', $_POST['phone'])) {
                $this->error('请填写正确的手机号'.$str_msg);
            }
            if($_POST['user_political_affiliation'] == 1 && empty(trim($_POST['phone']))){
                $this->error('手机号不能为空！'.$str_msg);
            }
            //处理自定义资料
            $_POST['authentication_field'] = serialize($data_post);
            $_POST['property_starttime'] && $_POST['property_starttime'] = strtotime($_POST['property_starttime'].'00:00:00');
            $_POST['property_endtime'] && $_POST['property_endtime'] = strtotime($_POST['property_endtime'].'23:59:59');
            //$condition['usernum'] = $_POST['usernum'];
            $condition['pigcms_id'] = $_POST['pigcms_id'];

            $_POST['add_time'] = $_SERVER['REQUEST_TIME'];

            $database_house_village_user_bind = D('House_village_user_bind');
            $database_house_village_user_vacancy = D('house_village_user_vacancy');



            // 业主信息
            $user_info = $database_house_village_user_bind->where($condition)->find();
            // 验证是否更新房屋信息
            if (in_array($user_info['type'],array(0,3))) {
                $update_vacancy = true;
            } else {
                $update_vacancy = false;
            }
            if ($user_info['uid']==0 && $user_info['phone']=='' && $user_info['name'] == '') { // 虚拟业主
                if (!$_POST['phone']) { // 没有修改手机号，不更新房屋状态；修改则更新
                    $update_vacancy = false;
                }
                $user_id = 0;
            }elseif($user_info['uid']==0 && $user_info['phone']==''){//没有uid 电话 拿名字创建一个用户
                $data_user = array(
                    'nickname' 	=> $user_info['name'],
                    'source' 	=> 'houseautoreg_street',
                );
                $reg_result = D('User')->autoreg($data_user);
                $user_id = $reg_result['uid'];
                $database_house_village_user_bind->where(['pigcms_id'=>$user_info['pigcms_id']])->data(['uid'=>$user_id])->save();
            }elseif ($user_info['uid']==0){//没有uid 情况
                $user = D('User')->where(['phone'=>$user_info['phone']])->find();
                $user_id = $user['uid'];
                if(!$user){
                    $data_user = array(
                        'nickname' 	=> $user_info['name'],
                        'phone' 	=> $user_info['phone'],
                        'source' 	=> 'houseautoreg_street',
                    );
                    $reg_result = D('User')->autoreg($data_user);
                    $user_id = $reg_result['uid'];
                }
                $database_house_village_user_bind->where(['pigcms_id'=>$user_info['pigcms_id']])->data(['uid'=>$user_id])->save();
            }elseif($user_info['uid'] && $user_info['phone'] == ''){//没有电话的情况
                $user = D('User')->where(['uid'=>$user_info['uid']])->find();
                $database_house_village_user_bind->where(['pigcms_id'=>$user_info['pigcms_id']])->data(['phone'=>$user['phone']])->save();
                $user_id = $user_info['uid'];
            }else{
                $user_id = $user_info['uid'];
            }
            if ($_POST['phone']) {
                $uid = D('User')->get_user_by_phone($_POST['phone']);
            }
            $_POST['uid'] = $uid ? $uid : 0;
            $user_id= $_POST['uid'];
            if($_POST['user_political_affiliation'] == 1 && $user_id && $_POST['party_type'] == 1 && empty($party_id)){
                $this->error('社区党员请选择选择党支部！');
            }
            if($user_id && $_POST['party_type'] > 0)
            {
                $party_bind_user = D('Street_party_bind_user')->where(['uid'=>$user_id])->find();
                if(!$party_bind_user)
                {
                    $party_data = [
                        'uid'=>$user_id,
                        'party_id'=>$party_id,
                        'create_time'=>time()
                    ];
                    D('Street_party_bind_user')->data($party_data)->add();
                }else {
                    if ($party_bind_user['party_id'] != $party_id) {
                        D('Street_party_bind_user')->where(['id' => $party_bind_user['id']])->data(['party_id' => $party_id])->save();
                    }
                }
            }

            $vacancy_where['status'] = array('in' , '1,2,3');
            $vacancy_where['pigcms_id'] = $user_info['vacancy_id'];
            $vacancy_info = $database_house_village_user_vacancy->where($vacancy_where)->find();
            if (!isset($vacancy_info)) {
                $this->error('该房间信息不存在！');
            }


            unset($data_post['user_political_affiliation']);
            unset($data_post['user_special_groups']);
            unset($data_post['user_focus_groups']);
            unset($data_post['user_vulnerable_groups']);
            $user_label = array();
            if (isset($_POST['user_political_affiliation'])) {
                $user_label['user_political_affiliation'] = $_POST['user_political_affiliation'];
            }
            if (isset($_POST['user_special_groups'])) {
                $user_label['user_special_groups'] = implode(',', $_POST['user_special_groups']);
            }else{
                $user_label['user_special_groups']='';
            }
            if (isset($_POST['user_focus_groups'])) {
                $user_label['user_focus_groups'] = implode(',', $_POST['user_focus_groups']);
            }else{
                $user_label['user_focus_groups']='';
            }
            if (isset($_POST['user_vulnerable_groups'])) {
                $user_label['user_vulnerable_groups'] = implode(',', $_POST['user_vulnerable_groups']);
            }else{
                $user_label['user_vulnerable_groups']='';
            }
            if (isset($_POST['party_type'])) {
                $user_label['party_type'] = $_POST['party_type'];
            }else{
                $user_label['party_type']=1;
            }
            //todo 用户同步写入属性表记录
            invoke_v20_service('\app\community\model\service\TaskReleaseService/userAttributeRecord', ['pigcms_id'=>$_POST['pigcms_id'],'field'=>$data_post]);
            if ($database_house_village_user_bind->where($condition)->data($_POST)->save()) {
                if ($update_vacancy) {
                    //修改房间表 信息
                    $condition_vacancy['is_del'] = 0;
                    $condition_vacancy['usernum']    = $vacancy_info['usernum'];
                    $condition_vacancy['village_id'] = $user_info['village_id'];
                    $data_vacancy['status'] = 3;
                    $data_vacancy['uid'] = $_POST['uid'];
                    $data_vacancy['name'] = $_POST['name'];
                    $data_vacancy['phone'] = $_POST['phone'];
                    $data_vacancy['memo'] = $_POST['memo'];
                    $data_vacancy['type'] = 0;
                    $database_house_village_user_vacancy->where($condition_vacancy)->data($data_vacancy)->save();
                }
                $user_label_id = M('House_village_user_label')->where(array('bind_id'=>$_POST['pigcms_id']))->getField('id');
                if ($user_label_id) {
                    $user_label['last_time'] = time();
                    M('House_village_user_label')->where(array('id'=>$user_label_id))->data($user_label)->save();
                }  else {
                    $user_label['bind_id'] = $_POST['pigcms_id'];
                    $user_label['add_time'] = time();
                    M('House_village_user_label')->data($user_label)->add();
                }
                // 修改信息，进行同步
                D('House_village_face_door')->modify_user($_POST['pigcms_id']);
                $this->success('修改成功');
                exit;
            }
            $this->error('保存失败');
            exit;
        } else {
            $party_status5=0;
            $pigcms_id = $_GET['pigcms_id'];
            $usernum = $_GET['usernum'];
            if ($pigcms_id && $usernum) {
                $bind_condition['pigcms_id'] = $pigcms_id;
                $info = D('House_village_user_bind')->where($bind_condition)->find();
//                $dataList = D('House_village_data_config')->where(array('village_id'=>$info['village_id']))->select();
                $where_village['village_id'] = $info['village_id'];
                $property_id = D('House_village')->where($where_village)->getField('property_id');
                $where_data_config = array(
                    'village_id'=>0,
                    'property_id'=>$property_id,
                    'is_display'=>1,
                    'is_close'=>0
                );
                $dataList = D('House_village_data_config')->where($where_data_config)->order('sort desc')->select();
                if (!$dataList) {
                    $where_data_config = array(
                        'village_id'=>$info['village_id'],
                        'is_display'=>1,
                        'is_close'=>0
                    );
                    $dataList = D('House_village_data_config')->where($where_data_config)->order('sort desc')->select();
                }


                foreach($dataList as $k=>&$v){
//                    if($v['type'] == 2){
//                        $v['use_field'] = explode(',',$v['use_field']);
//                    }else{
//                        $v['use_field'] = [];
//                    }
                    if($v['type'] == 2){
                        if($v['is_system'] == 1){
                            $v['use_field'] = D('House_village')->get_system_value($v['key']);
                        }else{
                            $v['use_field'] = explode(',',$v['use_field']);
                        }
                    }elseif($v['type'] == 3){

                        $v['use_field'] = [];
                    }else{
                        $v['use_field'] = [];
                    }
                }
                $this->assign('dataList',$dataList);
                $bind_condition['pigcms_id'] = $pigcms_id;
                $database_house_village_floor = D('House_village_floor');
                if($info['floor_id']){
                    $floor_info = $database_house_village_floor->where(array('floor_id'=>$info['floor_id']))->field('floor_type, floor_name, floor_layer')->find();
                    $floor_type = $floor_info['floor_type'];
                    $info['floor_type_name'] = D('House_village_floor_type')->where(array('id'=>$floor_type))->getField('name');
                }
                
                if($info['authentication_field']){
                    $info['authentication_field'] = unserialize($info['authentication_field']);
                    if($info['authentication_field']){
                    $authentication_field = [];
                    foreach($info['authentication_field'] as $k=>&$v){
                        if ('object'==gettype($v) || !isset($v['type'])) {
                            continue;
                        }
                        if($v['type'] == 3){
                            if (isset($v['value']) && $v['value']) {
                                $v['value'] = explode('#',$v['value']);
                                $v['province_idss'] = $v['value'][0];
                                $v['city_idss'] = $v['value'][1];
                            } else {
                                $v['value'] = '';
                                $v['province_idss'] = 0;
                                $v['city_idss'] = 0;
                            }
                        }
                        $authentication_field[$k] = $v;
                    }
                    $info['authentication_field'] = $authentication_field;
                    }
                }
                
                $word_data = array();
                if ($info['single_id']>0) {
                    $sing_name = M('House_village_single')->where(array('id' => $info['single_id']))->getField('single_name');
                } else {
                    $sing_name = '';
                }
                if (!empty($floor_info)) {
                    $word_data['floor_name'] = $floor_info['floor_name'];
                    if (!$sing_name) {
                        $sing_name = $floor_info['floor_layer'];
                    }
                }
                $word_data['single_name'] = $sing_name;
                $word_data['layer'] = $info['layer_num'];
                $word_data['room'] = $info['room_addrss'];
                $room_diy_name = D('House_village')->word_replce_msg($word_data,$info['village_id']);
                $this->assign('room_diy_name', $room_diy_name);

                $this->assign('info', $info);
				
				//小区信息
				$now_house = D('House_village')->field(true)->where(['village_id' => $info['village_id']])->find();
				$this->assign('now_house',$now_house);


                //车位信息
                $position_list = D('House_village_bind_position')->get_user_position_bind_list(array('pigcms_id'=>$pigcms_id,'village_id'=>$info['village_id']));
                //车辆信息
                $car_list = D('House_village_bind_car')->get_user_car_bind_list(array('pigcms_id'=>$pigcms_id));

                $this->assign('position_list',$position_list);
                $this->assign('car_list',$car_list);
            }
            // 业主标签
            // 政治面貌
            if($now_house){//查询党支部
                $party_bind_user = D('Street_party_bind_user')->where(['uid'=>$info['uid'],'party_status' => ['in', [1,4,5]]])->find();
                if($party_bind_user['party_status'] == 5){
                    $party_bind_user=[];
                    $party_bind_user['party_id']=0;
                    $party_status5=5;
                }
                $this->assign('party_bind_user',$party_bind_user);
                if($this->area_info['area_type']==0){
                    $party_where['status'] = ['neq','-1'];
                    $party_where['street_id'] = $now_house['street_id'];
                }elseif($this->area_info['area_type']==1){
                    $party_where['status'] = ['neq','-1'];
                    $where_bind_community = [
                        'community_id' => $now_house['street_id'],
                    ];
                    $party_bind_party_ids = D('Street_party_bind_community')->where($where_bind_community)->getField('party_id', true);
                    $party_where['party_id'] = ['in', $party_bind_party_ids];
                }else{
                    $party_where['id'] = $party_bind_user['party_id'];
                }
                $party = D('Area_street_party_branch')->where($party_where)->order('sort desc,id desc')->select();
                $this->assign('party',$party);
                $this->assign('area_type',$this->area_info['area_type']);
            }
            $user_political_affiliation_title = D('House_village_user_label')->user_political_affiliation_title;
            $user_political_affiliation_arr = D('House_village_user_label')->user_political_affiliation_arr;
            $this->assign('user_political_affiliation_title',$user_political_affiliation_title);
            $this->assign('user_political_affiliation_arr',$user_political_affiliation_arr);
            // 特殊人群
            $user_special_groups_title = D('House_village_user_label')->user_special_groups_title;
            $user_special_groups_arr = D('House_village_user_label')->user_special_groups_arr;
            $this->assign('user_special_groups_title',$user_special_groups_title);
            $this->assign('user_special_groups_arr',$user_special_groups_arr);
            // 重点人群
            $user_focus_groups_title = D('House_village_user_label')->user_focus_groups_title;
            $user_focus_groups_arr = D('House_village_user_label')->user_focus_groups_arr;
            $this->assign('user_focus_groups_title',$user_focus_groups_title);
            $this->assign('user_focus_groups_arr',$user_focus_groups_arr);
            // 弱势困难群体
            $user_vulnerable_groups_title = D('House_village_user_label')->user_vulnerable_groups_title;
            $user_vulnerable_groups_arr = D('House_village_user_label')->user_vulnerable_groups_arr;
            $this->assign('user_vulnerable_groups_title',$user_vulnerable_groups_title);
            $this->assign('user_vulnerable_groups_arr',$user_vulnerable_groups_arr);

            $user_label = D('House_village_user_label')->getInfo($pigcms_id,'bind_id');

            if($party_status5 == 5 && $user_label['user_political_affiliation'] == 1){
                $user_label['user_political_affiliation']=0;
            }
            $this->assign('user_label',$user_label);

            $this->display();
        }
    }



    public function ajax_help($group, $module, $action)
    {
        $url = strtolower($group . '_' . $module . '_' . $action);
        $url = 'http://o2o-service.pigcms.com/workorder/serviceAnswerApi.php?url=' . $url;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $content = curl_exec($ch);
        curl_close($ch);

        $content = json_decode($content,true);
        $html = '';
        foreach ($content as $value) {
            $class = $value['is_video'] == 1 ? 'class="video"' : 'class="writing"';
            $html .= '<p class="lianjie zuoce_clear "><a ' . $class . ' href="javascript:openwin(' . "'" . U('Index/help', array('answer_id' => $value['answer_id'])) . "'" .',768,960)">'.$value['title'].'</a></p>';
        }
        if (empty($html)) {
            $html = '<p class="lianjie zuoce_clear">没有帮助教程！</a>';
        }
        echo $html;
    }
}