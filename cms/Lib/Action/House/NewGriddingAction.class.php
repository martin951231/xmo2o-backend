<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * Date: 2019/10/10
 * Time: 9:28
 * 社区网格化管理
 */
class NewGriddingAction extends BaseAction{

    protected $village_id;
    protected $house_session;
    // 0 街道 1 社区 2小区
    protected $line_color = array(
        0=> '#ff0000',
        1=> '#2681f3',
        2=> '#1b9d18',
        3=> '#fff143',
    );
    public function _initialize(){
        parent::_initialize();
        $this->house_session = session('house');
        $this->village_id = $this->house_session['village_id'];
        $this->assign('house_session', $this->house_session);
    }



    /**网格化管理*/
    public function area_street_custom1() {
        $bind_id = isset($_GET['bind_id'])?intval($_GET['bind_id']):$this->village_id;
        $type = isset($_GET['type'])?intval($_GET['type']):2;
        $fid = isset($_GET['fid'])?intval($_GET['fid']):0;
        $custom_id = isset($_GET['custom_id'])?intval($_GET['custom_id']):0;
        $where = array();

        /* 当前所处层级地图区块信息开始 */
        if ($fid > 0) {
            $where['hvc.fid'] = $fid;
        }elseif ($bind_id > 0) {
            $where['hvc.bind_id'] = $bind_id;
            $where['hvc.type'] = $type;
        } else if ($this->village_id) {
            $where['hvc.bind_id'] = $this->village_id;
            $where['hvc.type'] = $type;
        }
        $house = M('House_village')->where(array('village_id' => $this->village_id))->field('community_id, street_id')->find();
        if ($fid<=0 && $house['community_id']>0) {
            $fid = $house['community_id'];
        } elseif ($fid<=0 && $house['street_id']>0) {
            $fid = $house['street_id'];
        }
        $field = 'hvp.*,hvc.type,hvc.fid,hvc.bind_id,hvc.lng,hvc.lat';
        $custom_polygon =  D('House_village_custom_polygon')->get_custom_polygon($where,$field);
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


        $def_city = $this->house_session['area_id'] ? $this->house_session['area_id']: $this->config['now_city'];
        $defaultCity = M('Area')->where(array('area_id'=>$def_city))->find();
        if ($_GET['lng'] && $_GET['lat']) {
            $defaultCity['area_lng']=$_GET['lng'];
            $defaultCity['area_lat']=$_GET['lat'];
            $custom['lng'] = $_GET['lng'];
            $custom['lat'] = $_GET['lat'];
            $custom['lng_lat'] = $_GET['lng'] . ',' . $_GET['lat'];
        }elseif (floatval($this->house_session['long'])!=0 && floatval($this->house_session['lat'])!=0) {
            $defaultCity['area_lng']=$this->house_session['long'];
            $defaultCity['area_lat']=$this->house_session['lat'];
            $custom['lng'] = $this->house_session['long'];
            $custom['lat'] = $this->house_session['lat'];
            $custom['lng_lat'] = $this->house_session['lng'] . ',' . $this->house_session['lat'];
        } else {
            $community_id = $this->house_session['community_id'];
            if ($community_id) {
                $defaultCity = M('Area_street')->where(array('area_id'=>$community_id))->find();
            }
            if (!$defaultCity || (floatval($defaultCity['long'])==0 && floatval($defaultCity['long'])==0)) {
                $street_id = $this->house_session['street_id'];
                $defaultCity = M('Area_street')->where(array('area_id'=>$street_id))->find();
            }
            if ($defaultCity) {
                $defaultCity['area_lng'] = $defaultCity['long'];
                $defaultCity['area_lat'] = $defaultCity['lat'];
            }
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

    public function area_street_custom()
    {
        $house = M('House_village')->where(array('village_id' => $this->village_id))->field('community_id, street_id,area_id,long,lat')->find();
        $list1 = M('House_village_grid_range')->where(['bind_id'=>$house['street_id'],'type'=>1])->field('id,manage_range_polygon,type')->select();
        $list2 = M('House_village_grid_range')->where(['bind_id'=>$house['community_id'],'type'=>2])->field('id,manage_range_polygon,type')->select();
        $list3 = M('House_village_grid_range')->where(['bind_id'=>$this->village_id,'type'=>3])->field('id,manage_range_polygon,type')->select();
        $list4 = M('House_village_grid_range')->where(['fid'=>$this->village_id,'type'=>4])->field('id,manage_range_polygon,type')->select();
        $list = array_merge($list1,$list2,$list3,$list4);
        $manage_range_polygon = array();
        $arr_type = array();
        $village_grid_range_ids= array();
        if($list){
            foreach ($list as $k=>$v){
                switch ($v['type']){
                    case 1:
                        $arr_type[$k] = $this->line_color[0];
                        break;
                    case 2:
                        $arr_type[$k] = $this->line_color[1];
                        break;
                    case 3:
                        $arr_type[$k] = $this->line_color[2];
                        break;
                    case 4:
                        $arr_type[$k] = $this->line_color[3];
                        break;
                }
                $village_grid_range_ids[$k]=$v['id'];
                $v['manage_range_polygon'] = explode('|',$v['manage_range_polygon']);
                foreach ($v['manage_range_polygon'] as $k1=>$v1){
                    $point = explode(',',$v1);
                    $manage_range_polygon[$k][$k1]['lng'] = $point[0];
                    $manage_range_polygon[$k][$k1]['lat'] = $point[1];
                }
            }
        }
        //var_dump(json_encode($manage_range_polygon));
        $custom['manage_range_polygon'] = json_encode($manage_range_polygon);
        $custom['village_grid_range_ids'] = json_encode($village_grid_range_ids);
        $this->assign('arr_color',json_encode($arr_type));


        $def_city = $house['area_id'] ? $house['area_id']: $this->config['now_city'];
        $defaultCity = M('Area')->where(array('area_id'=>$def_city))->find();
        if ($_GET['lng'] && $_GET['lat']) {
            $defaultCity['area_lng']=$_GET['lng'];
            $defaultCity['area_lat']=$_GET['lat'];
            $custom['lng'] = $_GET['lng'];
            $custom['lat'] = $_GET['lat'];
            $custom['lng_lat'] = $_GET['lng'] . ',' . $_GET['lat'];
        }elseif (floatval($house['long'])!=0 && floatval($house['lat'])!=0) {
            $defaultCity['area_lng']=$house['long'];
            $defaultCity['area_lat']=$house['lat'];
            $custom['lng'] = $house['long'];
            $custom['lat'] = $house['lat'];
            $custom['lng_lat'] = $house['lng'] . ',' . $house['lat'];
        } else {
            $community_id = $house['community_id'];
            if ($community_id) {
                $defaultCity = M('Area_street')->where(array('area_id'=>$community_id))->find();
            }
            if (!$defaultCity || (floatval($defaultCity['long'])==0 && floatval($defaultCity['long'])==0)) {
                $street_id = $house['street_id'];
                $defaultCity = M('Area_street')->where(array('area_id'=>$street_id))->find();
            }
            if ($defaultCity) {
                $defaultCity['area_lng'] = $defaultCity['long'];
                $defaultCity['area_lat'] = $defaultCity['lat'];
            }
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
        $this->assign('custom',$custom);

        $this->display();
    }

    /**
     * 单机获取绘制区域信息
     * @author lijie
     * @date_time 2020/01/07
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function showInfo()
    {
        $where=array('id'=>array('gt',0));
        $str_lng_lat = $_POST['str_lng_lat'];
        $id = isset($_POST['id']) ? intval($_POST['id']):0;
        if($id>0){
            $where['id']=$id;
        }elseif(!empty($str_lng_lat)){
            $where['manage_range_polygon'] = array('like','%'.$str_lng_lat.'%');
        }
        $info = M('House_village_grid_range')->where($where)->find();
        if($info['type'] == 1 || $info['type'] == 2)
            $data = M('Area_street')->where(['area_id'=>$info['bind_id']])->field('area_name,phone')->find();
        $grid_member_info = M('House_village_grid_member')->where(['id'=>$info['grid_member_id']])->field('name,phone')->find();
        $data['grid_name'] = $grid_member_info['name'] ? $grid_member_info['name'] : '';
        $data['grid_phone'] = $grid_member_info['phone'] ? $grid_member_info['phone'] : '';
        $data['polygon_name'] = $info['polygon_name'];
        if($info['type'] == 1){
            $count = 64;
            $data['type'] = 1;
            $data['count'] = $count;
        }elseif($info['type'] == 2){
            $data['type'] = 2;
        }elseif($info['type'] == 3){
            $data['type'] = 3;
            $village_info = M('House_village')->where(['village_id'=>$info['bind_id']])->field('village_name,community_id')->find();
            $area_info = M('Area_street')->where(['area_id'=>$village_info['community_id']])->field('area_name,phone')->find();
            $data['area_name'] = $area_info['area_name'];
            $data['phone'] = $area_info['phone'];
            $data['village_name'] = $village_info['village_name'];
        }else{
            $single_info = M('House_village_single')->where(['id'=>$info['bind_id']])->field('single_name,village_id')->find();
            $village_info = M('House_village')->where(['village_id'=>$single_info['village_id']])->field('village_name,community_id')->find();
            $single_info['village_name'] = $village_info['village_name'];
            $single_info['community_id'] = $village_info['community_id'];
            $area_info = M('Area_street')->where(['area_id'=>$single_info['community_id']])->field('area_name,phone')->find();
            $data['single_name'] = $single_info['single_name'];
            $data['village_name'] = $single_info['village_name'];
            $data['area_name'] = $area_info['area_name'];
            $data['phone'] = $area_info['phone'];
            $data['type'] = 4;
        }
        exit(json_encode(array('status'=>0,'data'=>$data)));
    }
}