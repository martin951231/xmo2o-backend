<?php
/**
 * Created by PhpStorm.
 * User: 郭先森
 * Date: 2019/10/29
 * Time: 9:28
 */
//组织后台
class PropertyGroupAction extends BaseAction{

    protected $property_id;
    protected $property;
    protected $worker;

    public function _initialize(){
        parent::_initialize();

//        $property = session('property');
//        $this->assign('property',$property);
//        $this->property = session('property');
//        $this->property_id = $property['id'];

    }

    public function index()
    {

        $res = M('Property_group')->where(array('property_id'=>$this->property_id))->find();
        if(!$res){
            $data['fid'] = 0;
            $data['name'] = $this->property['property_name'];
            $data['type'] = 99;
            $data['status'] = 1;
            $data['sort'] = 1;
            $data['property_id'] = $this->property_id;
            $data['village_id'] = 0;
            $data['section_type'] = '';
            M('Property_group')->data($data)->add();
        } else {
            $where_property_group = [
                'fid' => 0,
                'type' => 99,
                'status' => 1,
                'sort' => 1,
                'property_id' =>  $this->property_id,
                'village_id' => 0,
            ];
            $property_group_info = M('Property_group')->where($where_property_group)->find();
            if ($this->property['property_name'] && $this->property['property_name']!=$property_group_info['name']) {
                $save_data = [
                    'name' => $this->property['property_name']
                ];
                M('Property_group')->where(['id' => $property_group_info['id']])->save($save_data);
            }
        }

        $is_syn = false;
        $where['bind_id'] = $this->property_id;
        $access_obj = M('House_enterprise_wx_bind')->where($where)->field('access_token, expires_in, corpid, permanent_code')->find();
        if ($access_obj['corpid'] && $access_obj['permanent_code']) {
            $is_syn = true;
            // 在授权了微信的情况下 统计下当前同步数量
        }
        $this->assign('is_syn', $is_syn);

        $this->display();
    }

    public function ajax_list()
    {
        $arr = M('Property_group')->where(array('property_id'=>$this->property_id))->order('sort desc,id desc')->select();
        $this->generateTree($arr);
    }

    public function generateTree($array){

        $items = array();
        $db_house_village = M('House_village');
        $db_house_property = M('House_property');
        foreach($array as $value){

            $list['permission_id'] = $value['id'];
            $list['parent_permission_id'] = $value['fid'];
            if($value['type'] == 99 && $value['property_id']){
                $house_property = $db_house_property->where(array('id'=>$value['property_id']))->field('property_name')->find();
                $value['name'] = $house_property['property_name'];
                $count = M('House_worker')->where(array('property_id'=>$value['property_id']))->count();
                $list['text'] = $value['name'].'-'.$count.'人';
            }elseif($value['type'] == 0 && $value['property_id'] && $value['village_id']){
//                $where['people_type'] = array('in','0,1');
                $name = $value['name'];
                $value['name'] = $db_house_village->where(['village_id' =>$value['village_id']])->getField('village_name');
                if ($name && $value['name'] && $value['name']!=$name) {
                    $save_data = [
                        'name' => $value['name']
                    ];
                    M('Property_group')->where(['id' => $value['id']])->save($save_data);
                }
                $count = M('House_worker')->where(array('property_id'=>$value['property_id'],'village_id'=>$value['village_id'],'department_id'=>$value['id']))->count();
                $list['text'] = $value['name'].'-'.$count.'人';
            }elseif($value['type'] == 1 && $value['property_id'] && $value['village_id']){
                $count = M('House_worker')->where(array('property_id'=>$value['property_id'],'village_id'=>$value['village_id'],'department_id'=>$value['id']))->count();
                $list['text'] = $value['name'].'-'.$count.'人';
            }elseif($value['type'] == 2 && $value['property_id']){
                $count = M('House_worker')->where(array('property_id'=>$value['property_id'],'department_id'=>$value['id']))->count();
                $list['text'] = $value['name'].'-'.$count.'人';
            }else{
                $count = M('House_worker')->where(array('property_id'=>$value['property_id'],'department_id'=>$value['id']))->count();
                $list['text'] = $value['name'].'-'.$count.'人';
            }


            $list['permission_url'] = '';
            $list['tag'] = '';
            $list['small_icon_name'] = 'fa fa-desktop';
            $list['level'] = $value['fid'];
            if($value['type'] == 0){//小区
                $list['after_html'] = '<span class="button_z"><button type="button" class="btn btn btn-info btn-xs" onclick="addPropertyGroup('.$value['id'].','.$value['village_id'].',this);">添加子组织</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn btn-info btn-xs" onclick="addHouseWorkerDep('.$value['village_id'].','.$value['type'].','.$value['id'].',this);">添加人员</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-danger btn-xs" onclick="del('.$value['id'].');">删除</button></span>';

            }elseif($value['type'] == 1){//小区部门
                $list['after_html'] = '<span class="button_z"><button type="button" class="btn btn btn-info btn-xs" onclick="addHouseWorkerDep('.$value['village_id'].','.$value['type'].','.$value['id'].',this);">添加人员</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-danger btn-xs" onclick="del('.$value['id'].');">删除</button></span>';

            }elseif($value['type'] == 2){//物业部门
                $list['after_html'] = '<span class="button_z"><button type="button" class="btn btn btn-info btn-xs" onclick="addPropertyDep('.$value['type'].','.$value['id'].',this);">添加人员</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-danger btn-xs" onclick="del('.$value['id'].');">删除</button></span>';

            }elseif($value['type'] == 99){//物业
                $list['after_html'] = '<span class="button_z"><button type="button" class="btn btn btn-info btn-xs" onclick="addPropertyGroup('.$value['id'].',0,this);">添加子组织</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn btn-info btn-xs" onclick="addHouseWorkers(99, 0, this);">添加人员</button>&nbsp;&nbsp;&nbsp;&nbsp;</span>';

            }

            $items[$value['id']] = $list;
        }

        $tree = array();
        foreach($items as $key => $value){

            if(isset($items[$value['parent_permission_id']])){

                $items[$value['parent_permission_id']]['nodes'][] = &$items[$key];
            }else{
                $tree[] = &$items[$key];
            }
        }
        echo json_encode($tree);
    }


    public function worker_list($department_id){

        $dataList = M('House_worker')->where(array('property_id'=>$this->property_id,'department_id'=>$department_id,'is_del'=>0))->select();

        $worker_user = [];
        foreach($dataList as $k=>$value){

            //$worker_user[$value['department_id']][] = $value;
            $worker_user[] = $value;

        }
        return $worker_user;
    }
    public function index_new()
    {

        $dataList = M('Property_group')->where(array('property_id'=>$this->property_id,'is_del'=>0))->order('sort desc,id desc')->select();
        $db_house_village = M('House_village');
        $db_house_property = M('House_property');
        $house_property = $db_house_property->where(array('id'=>$this->property_id))->field('property_name')->find();
        foreach($dataList as $k=>$value){
            if(!empty($value['village_id'])){
                $vv=$db_house_village->where([
                    'village_id'=>$value['village_id'],
                    'status'=>array('neq', 5)
                ])->getField('village_id');
                if(!$vv){
                    continue;
                }
            }
            $list['permission_id'] = $value['id'];
            $list['parent_permission_id'] = $value['fid'];
            if($value['type'] == 99 && $value['property_id']){
                $value['name'] = $house_property['property_name'];
                $count = M('House_worker')->where(array('property_id'=>$value['property_id'],'is_del'=>0))->count();
                $list['text'] = $value['name'].'-(共<span id="permission_text_'.$value['id'].'">'.$count.'</span>人)';
            }elseif($value['type'] == 0 && $value['property_id'] && $value['village_id']){
                $where['people_type'] = array('in','0,1');
                $name = $value['name'];
                $value['name'] = $db_house_village->where(['village_id' =>$value['village_id']])->getField('village_name');
                if ($name && $value['name'] && $value['name']!=$name) {
                    $save_data = [
                        'name' => $value['name']
                    ];
                    M('Property_group')->where(['id' => $value['id']])->save($save_data);
                }
                $count = M('House_worker')->where(array('property_id'=>$value['property_id'],'village_id'=>$value['village_id'],'is_del'=>0))->where($where)->count();
                $list['text'] = $value['name'].'-(共<span id="permission_text_'.$value['id'].'">'.$count.'</span>人)';
            }elseif($value['type'] == 1 && $value['property_id'] && $value['village_id']){

                $house_worer_group_list = M('Property_group')->where(array('property_id'=>$value['property_id'],'is_del'=>0,'fid'=>$value['id']))->select();
                $list_data = [];
                foreach ($house_worer_group_list as $key => $value1) {
                    $list_data[] = $value1['id'];
                }
                $list_data[] =  $value['id'];
                $list_where['department_id'] = array('in',$list_data);
                $count = M('House_worker')->where(array('property_id'=>$value['property_id'],'is_del'=>0))->where($list_where)->count();

                //$count = M('House_worker')->where(array('property_id'=>$value['property_id'],'village_id'=>$value['village_id'],'department_id'=>$value['id'],'is_del'=>0))->count();
                $list['text'] = $value['name'].'-(共<span id="permission_text_'.$value['id'].'">'.$count.'</span>人)';
            }elseif($value['type'] == 2 && $value['property_id']){
                $house_worer_group_list = M('Property_group')->where(array('property_id'=>$value['property_id'],'is_del'=>0,'fid'=>$value['id']))->select();
                $list_data = [];
                foreach ($house_worer_group_list as $key => $value2) {
                    $list_data[] = $value2['id'];
                }
                $list_data[] =  $value['id'];
                $list_where['department_id'] = array('in',$list_data);
                $count = M('House_worker')->where(array('property_id'=>$value['property_id'],'is_del'=>0))->where($list_where)->count();
                $list['text'] = $value['name'].'-(共<span id="permission_text_'.$value['id'].'">'.$count.'</span>人)';
            }else{
                $count = M('House_worker')->where(array('property_id'=>$value['property_id'],'department_id'=>$value['id'],'is_del'=>0))->count();
                $list['text'] = $value['name'].'-(共<span id="permission_text_'.$value['id'].'">'.$count.'</span>人)';
            }
            $list['permission_url'] = '';
            $list['tag'] = '';
            $list['small_icon_name'] = 'fa fa-desktop';
            $list['level'] = $value['fid'];
            if($value['type'] == 0){//小区
                $list['after_html'] = '<span class="button_z button_property"><button type="button" class="btn btn btn-info btn-xs" onclick="addPropertyGroup('.$value['id'].','.$value['village_id'].',this);">添加子组织</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn btn-info btn-xs" onclick="addHouseWorkerDep('.$value['village_id'].','.$value['type'].','.$value['id'].',this);">添加人员</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-danger btn-xs" onclick="del('.$value['id'].');">删除</button></span>';

            }elseif($value['type'] == 1){//小区部门
                $list['after_html'] = '<span class="button_z button_property"><button type="button" class="btn btn btn-info btn-xs" onclick="addPropertyGroup('.$value['id'].','.$value['village_id'].',this);">添加子组织</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn btn-info btn-xs" onclick="addHouseWorkerDep('.$value['village_id'].','.$value['type'].','.$value['id'].',this);">添加人员</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn btn-info btn-xs" onclick="edit_property_group('.$value['id'].');">编辑</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-danger btn-xs" onclick="del('.$value['id'].');">删除</button></span>';

            }elseif($value['type'] == 2){//物业部门
                $list['after_html'] = '<span class="button_z button_property"><button type="button" class="btn btn btn-info btn-xs" onclick="addPropertyGroup('.$value['id'].',0,this);">添加子组织</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn btn-info btn-xs" onclick="addPropertyDep('.$value['type'].','.$value['id'].',this);">添加人员</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn btn-info btn-xs" onclick="edit_property_group('.$value['id'].');">编辑</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-danger btn-xs" onclick="del('.$value['id'].');">删除</button></span>';

            }elseif($value['type'] == 99){//物业
                $list['after_html'] = '<span class="button_z button_property"><button type="button" class="btn btn btn-info btn-xs" onclick="addPropertyGroup('.$value['id'].',0,this);">添加子组织</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn btn-info btn-xs" onclick="addHouseWorkers(99,'.$value['id'].',this);">添加人员</button></span>';

            }

            $items[] = $list;
        }
        $res = $this->getTree($items,0);

        echo json_encode($res);
    }

    public function getTree($arr=[],$fid){

        $temp_new_arr = [];
        foreach($arr as $k=>$v){
            if($v['parent_permission_id'] == $fid){
                $temp_arr = $v;
                $temp_arr['worker'] =$this->worker_list($v['permission_id']);
                if($temp_arr['worker'] == []){
                    unset($temp_arr['worker']);
                }
                $temp_arr['nodes'] =$this->getTree($arr,$v['permission_id']);

                foreach($temp_arr['worker'] as $k1=>$v1){
                    $list['permission_id'] = $v1['wid'];
                    $list['parent_permission_id'] = $v1['department_id'];
                    $list['text'] = $v1['name'];
                    $list['permission_url'] = '';
                    $list['tag'] = '';
                    $list['small_icon_name'] = 'fa fa-desktop';
                    $list['level'] = $v1['department_id'];
                    $list['after_html'] = '<span class="button_z"><button type="button" class="btn btn-success btn-xs" onclick="info_people('.$v1['wid'].');">查看</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn btn-info btn-xs" onclick="edit_people('.$v1['wid'].');">编辑</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-danger btn-xs" onclick="del_people('.$v1['wid'].');">删除</button></span>';
                    array_unshift($temp_arr['nodes'],$list);
                }
                unset($temp_arr['worker']);
                if(count($temp_arr['nodes']) == 0){
                    unset($temp_arr['nodes']);
                }
                $temp_new_arr[] = $temp_arr;
            }

        }
        return $temp_new_arr;
    }






    public function add()
    {
        if(IS_POST){
            if($_POST['type'] == 0){//小区
                $data['fid'] = $_POST['fid'];
                $village = explode('-',$_POST['village_name']);
                $data['name'] = $village[1];
                $data['village_id'] = $village[0];
                $data['type'] = 0;
                $data['status'] = 1;
                $data['sort'] = $_POST['sort'];
                $data['property_id'] = $this->property_id;
                $data['section_type'] = '';


            }elseif($_POST['type'] == 2){//物业部门
                if(empty($_POST['group_name'])){
                    $this->error('名称不能为空！');
                }
                $data['fid'] = $_POST['fid'];
                $data['name'] = $_POST['group_name'];
                $data['village_id'] = '';
                $data['type'] = 2;
                $data['status'] = 1;
                $data['sort'] = $_POST['sort'];
                $data['property_id'] = $this->property_id;
                $section_type = explode('-',$_POST['section_type']);
                $data['section_type'] = $section_type[1];
                $data['section_status'] = $section_type[0];

            }elseif($_POST['type'] == 1){//小区部门
                if(empty($_POST['group_name'])){
                    $this->error('名称不能为空！');
                }
                $data['fid'] = $_POST['fid'];
                $data['name'] = $_POST['group_name'];
                $data['village_id'] = $_POST['village_id'];
                $data['type'] = 1;
                $data['status'] = 1;
                $data['sort'] = $_POST['sort'];
                $data['property_id'] = $this->property_id;
                $section_type = explode('-',$_POST['section_type']);
                $data['section_type'] = $section_type[1];
                $data['section_status'] = $section_type[0];
            }
            $add_a = M('Property_group')->data($data)->add();
            if($add_a){
                $data['id'] = $add_a;
                $index_num = $_GET['index_num'];
                $html = $this->single_info_html($data, false, $index_num);
                $msg = array(
                    'message' => '添加成功！',
                    'html' => $html,
                    'status' => 1
                );
                $this->ajaxReturn($msg);
            }else{
                $this->error('添加失败！');
            }
        }else{
            $now_property = M('Property_group')->where(array('property_id'=>$this->property_id,'type'=>0,'is_del'=>0))->select();

            $data = [];
            foreach($now_property as $k=>$v){
                $data[] = $v['village_id'];
            }
            $where = [];
            if($data){
                $where['village_id'] = array('not in',$data);
            }

            $house_village = M('House_village')->where(array('property_id'=>$this->property_id))->where($where)->select();
            $this->assign('house_village',$house_village);
            $this->assign('village',$_GET['village_id']);
            $this->display();
        }

    }

    public function single_info_html($data, $is_worker=false, $index_num='-1') {
        $html = '';
        $index_str = '';
        if ($index_num!='-1' && $index_num>=0) {
            $num = $index_num + 1;
            for($i=0;$i<$num;$i++) {
                $index_str .= "<span class='indent'></span>";
            }
        }

        if (!$is_worker) {
            switch($data['type']){
                case '0':
                    $html .= "<li class='list-group-item node-treeview-checkable node-selected' style='color:#000000;background-color:#ffffcc;'>".$index_str."<span class='icon glyphicon'></span>{$data['name']}-(共<span id='permission_text_{$data['id']}'>0</span>人)</li>";
                    $html .= '<span class="button_z button_property"><button type="button" class="btn btn btn-info btn-xs" onclick="addPropertyGroup('.$data['id'].','.$data['village_id'].',this);">添加子组织</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn btn-info btn-xs" onclick="addHouseWorkerDep('.$data['village_id'].','.$data['type'].','.$data['id'].',this);">添加人员</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-danger btn-xs" onclick="del('.$data['id'].');">删除</button></span>';
                    break;
                case '1':
                    $html .= "<li class='list-group-item node-treeview-checkable node-selected' style='color:#000000;background-color:#ffffcc;'>".$index_str."<span class='icon glyphicon'></span>{$data['name']}-(共<span id='permission_text_{$data['id']}'>0</span>人)</li>";
                    $html .= '<span class="button_z button_property"><button type="button" class="btn btn btn-info btn-xs" onclick="addPropertyGroup('.$data['id'].','.$data['village_id'].',this);">添加子组织</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn btn-info btn-xs" onclick="addHouseWorkerDep('.$data['village_id'].','.$data['type'].','.$data['id'].',this);">添加人员</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn btn-info btn-xs" onclick="edit_property_group('.$data['id'].');">编辑</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-danger btn-xs" onclick="del('.$data['id'].');">删除</button></span>';
                    break;
                case '2':
                    $html .= "<li class='list-group-item node-treeview-checkable node-selected' style='color:#000000;background-color:#ffffcc;'>".$index_str."<span class='icon glyphicon'></span>{$data['name']}-(共<span id='permission_text_{$data['id']}'>0</span>人)</li>";
                    $html .= '<span class="button_z button_property"><button type="button" class="btn btn btn-info btn-xs" onclick="addPropertyGroup('.$data['id'].',0,this);">添加子组织</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn btn-info btn-xs" onclick="addPropertyDep('.$data['type'].','.$data['id'].',this);">添加人员</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn btn-info btn-xs" onclick="edit_property_group('.$data['id'].');">编辑</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-danger btn-xs" onclick="del('.$data['id'].');">删除</button></span>';
                    break;
                case '99':
                    $html .= "<li class='list-group-item node-treeview-checkable node-selected' style='color:#000000;background-color:#ffffcc;'>".$index_str."<span class='icon glyphicon'></span>{$data['name']}-(共<span id='permission_text_{$data['id']}'>0</span>人)</li>";
                    $html .= '<span class="button_z button_property"><button type="button" class="btn btn btn-info btn-xs" onclick="addPropertyGroup('.$data['id'].',0,this);">添加子组织</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn btn-info btn-xs" onclick="addHouseWorkers(99,'.$data['id'].',this);">添加人员</button></span>';
                    break;
            }
        } else {
            $html .= "<li class='list-group-item node-treeview-checkable node-selected' style='color:#000000;background-color:#ffffcc;'>".$index_str."<span class='icon glyphicon'></span>{$data['name']}</li>";
            $html .= '<span class="button_z"><button type="button" class="btn btn-success btn-xs" onclick="info_people('.$data['wid'].');">查看</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn btn-info btn-xs" onclick="edit_people('.$data['wid'].');">编辑</button>&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-danger btn-xs" onclick="del_people('.$data['wid'].');">删除</button></span>';
        }

        return $html;
    }

    public function edit_property_group()
    {
        if(IS_POST){
            $data = [];
            $data['sort'] = $_POST['sort'];
            $section_type = explode('-',$_POST['section_type']);
            $data['section_type'] = $section_type[1];
            $data['section_status'] = $section_type[0];
            if($_POST['group_name']){
                $data['name'] = $_POST['group_name'];
            }else{
                $data['name'] = $_POST['group_name'];
//                $data['village_id'] = $_POST['village_id'];
            }

            $res = D('Property_group')->where(array('id'=>$_POST['id']))->data($data)->save();
            if($res){
                $this->success('修改成功！',U('index',['iframe' => $_GET['iframe']]));
            }else{
                $this->error('修改失败！');
            }
        }else{
            if(!isset($_GET['id'])){
                $this->error('数据不存在!');
            }
            $res = D('Property_group')->where(array('id'=>$_GET['id']))->find();

            $qy_color = '';
            $qy_txt = '';
            $qy_time = '';
            if ($res['property_id']) {
                $where['bind_id'] = $res['property_id'];
                $wx_bind_id = M('House_enterprise_wx_bind')->where($where)->getField('pigcms_id');
                if ($wx_bind_id) {
                    if ($res['update_time']) {
                        $qy_time = date('Y-m-d H:i:s', $res['update_time']);
                    } elseif($res['create_time']) {
                        $qy_time = date('Y-m-d H:i:s', $res['create_time']);
                    } elseif ($res['del_time']) {
                        $qy_time = date('Y-m-d H:i:s', $res['del_time']);
                    }
                    if (!in_array($res['create_status'],[1,4]) && trim($res['qy_reasons'])) {
                        $qy_txt = '【同步失败】'.trim($res['qy_reasons']);
                        $qy_color = 'red';
                    } elseif (1==$res['create_status']) {
                        if (trim($res['qy_reasons'])) {
                            $qy_txt = '【同步成功】'.trim($res['qy_reasons']);
                        } else {
                            $qy_txt = '【同步成功】';
                        }
                        $qy_color = 'green';
                    } elseif (4==$res['create_status']) {
                        if (trim($res['qy_reasons'])) {
                            $qy_txt = '【同步删除】'.trim($res['qy_reasons']);
                        } else {
                            $qy_txt = '【同步删除】';
                        }
                        if ($res['del_time']) {
                            $qy_time = date('Y-m-d H:i:s', $res['del_time']);
                        }
                        $qy_color = 'green';
                    } else {
                        $qy_txt = '未同步';
                        $qy_time = '';
                        $qy_color = '';
                    }
                }
            }
            $this->assign('qy_color',$qy_color);
            $this->assign('qy_txt',$qy_txt);
            $this->assign('qy_time',$qy_time);


            $this->assign('res',$res);
            $this->display();
        }
    }

    public function del()
    {
        if($_POST['id']){
            $result = $this->get_del($_POST['id']);
            // 删除完成考虑到可能删除数据过多 直接同步整体同步一次
            D('Property_group')->sync_to_enterprise_wechat($this->property_id);
            echo json_encode(['code'=>1,'msg'=>'删除成功!']);die;
        }else{
            echo json_encode(['code'=>0,'msg'=>'数据不存在']);
        }

    }

    public function get_del($id){

        $data_base = D('Property_group');
        $database_worker = D('House_worker');
        $condition_area['fid'] = $id;
        $now_property = $data_base->field('`id`')->where($condition_area)->select();
        if (is_array($now_property)) {
            foreach ($now_property as $key => $value) {
                $this->get_del($value['id']);
            }
        }
        $condition_del_area['id'] = $id;
        $data_base->where($condition_del_area)->setField('is_del', 1);
        $house_worker_list = $database_worker->where(array('department_id'=>$id))->select();
        $set['is_del'] = 1;
        $set['openid'] = '';
        $database_worker->where(array('department_id'=>$id))->data($set)->save();
        foreach ($house_worker_list as $house_worker) {
            if($house_worker['phone'] && $house_worker['village_id']){
                $bind_user =  D('House_village_user_bind')->where(array('phone'=>$house_worker['phone'],'village_id'=>$house_worker['village_id'],'type'=>4))->find();
                //todo 同步删除设备数据
                D('House_village_face_door')->del_work_user_bind($bind_user['pigcms_id'], $house_worker['village_id'],$bind_user);

                D('House_village_user_bind')->where(array('phone'=>$house_worker['phone'],'village_id'=>$house_worker['village_id'],'type'=>4))->delete();
//                D('Face_door_a4_service')->delA4Property($bind_user['pigcms_id'],0,0, $bind_user);
            }
        }
    }

    public function worker_add()
    {
        if(IS_POST){
            if(empty($_POST['name'])){
                $this->error('名称不能为空！');
            }
            if(empty($_POST['phone'])){
                $this->error('手机号不能为空！');
            }
            $data['id_card'] = $_POST['id_card'] ? trim($_POST['id_card']) : '';
            if(empty($data['id_card'])){
                $this->error('身份证号不能为空！');
            }
            if(is_idcard($data['id_card']) == false){
                $this->error('请填写正确的身份证号码');
            }
            $data['job_number'] = $_POST['job_number'];
            $data['name'] = $_POST['name'];
            $data['status'] = 1;
            $data['gender'] = $_POST['gender'] ? intval($_POST['gender']) : 0;
            $data['phone'] = $_POST['phone'];
            $data['ic_card'] = $_POST['ic_card'] ? trim($_POST['ic_card']) : '';
//            $data['weixin_num'] = $_POST['weixin_num'];
            $data['job_create_time'] = strtotime($_POST['job_create_time']);
            $data['open_door'] = isset($_POST['open_door']) ? intval($_POST['open_door']) : 0;
            $data['note'] = $_POST['note'] ? trim($_POST['note']) : '';
            $data['people_type'] = $_POST['type'] ? $_POST['type'] : '';
            $data['village_id'] = empty($_POST['village_id'])?'':$_POST['village_id'];
            $data['property_id'] = $this->property_id;
            $data['department_id'] = empty($_POST['department_id'])?'':$_POST['department_id'];
            if($_POST['department_id']){
                $property_group = M('Property_group')->where(array('id'=>$_POST['department_id']))->find();
//                $data['type'] = $property_group['section_status']?$property_group['section_status']:'-1';
                if($property_group['section_status'] != '' || $property_group['section_status'] != null){
                    $data['type'] = intval($property_group['section_status'])>=0?intval($property_group['section_status']):'-1';
                }else{
                    $data['type'] = '-1';
                }
            }else{
                $data['type'] = '-1';
            }

            $add_a = M('House_worker')->data($data)->add();
            if($add_a){
                $this->worker_user_amend($data, $add_a);
                $data['wid'] = $add_a;
                $index_num = $_GET['index_num'];
                $html = $this->single_info_html($data, true, $index_num);
                $msg = array(
                    'message' => '添加成功！',
                    'html' => $html,
                    'status' => 1
                );
                $this->ajaxReturn($msg);
            }else{
                $this->error('添加失败！');
            }
        }else{
            $this->display();
        }
    }


    public function del_people(){
        if($_POST['id']){
            $data['is_del'] = 1;
            $data['openid'] = '';
            $result = D('House_worker')->where(array('wid'=>$_POST['id']))->data($data)->save();
            if($result){
                // 同步到企业微信删除下
                $house_worker = D('House_worker')->where(array('wid'=>$_POST['id']))->find();
                if($house_worker['phone'] && $house_worker['village_id']){
                    $bind_user =  D('House_village_user_bind')->where(array('phone'=>$house_worker['phone'],'village_id'=>$house_worker['village_id'],'type'=>4))->find();
                    //todo 同步删除设备数据
                    D('House_village_face_door')->del_work_user_bind($bind_user['pigcms_id'], $house_worker['village_id'],$bind_user);

                    D('House_village_user_bind')->where(array('phone'=>$house_worker['phone'],'village_id'=>$house_worker['village_id'],'type'=>4))->delete();
//                    D('Face_door_a4_service')->delA4Property($bind_user['pigcms_id'],0,0, $bind_user);
                }
                if ($house_worker['property_id']) {
                    // 删除触发一次全物业同步信息
                    D('Property_group')->sync_to_enterprise_wechat($house_worker['property_id']);
                }
                echo json_encode(['code'=>1,'msg'=>'删除成功!']);
            }else{
                echo json_encode(['code'=>0,'msg'=>'删除失败!']);
            }
        }else{
            echo json_encode(['code'=>0,'msg'=>'数据不存在']);
        }
    }


    public function info_people()
    {
        if(!isset($_GET['id'])){
            $this->error('数据不存在!');
        }
        $res = D('House_worker')->where(array('wid'=>$_GET['id']))->find();
        $this->assign('res',$res);
        $this->display();
    }

    public function info_people_new()
    {
        if(!isset($_GET['id'])){
            $this->error('数据不存在!');
        }
        $res = D('House_worker')->where(array('wid'=>$_GET['id']))->find();

        $qy_color = '';
        $qy_txt = '';
        $qy_time = '';
        if ($res['property_id']) {
            $where['bind_id'] = $res['property_id'];
            $wx_bind_id = M('House_enterprise_wx_bind')->where($where)->getField('pigcms_id');
            if ($wx_bind_id) {
                if ($res['update_time']) {
                    $qy_time = date('Y-m-d H:i:s', $res['update_time']);
                } elseif($res['qy_time']) {
                    $qy_time = date('Y-m-d H:i:s', $res['qy_time']);
                } elseif ($res['del_time']) {
                    $qy_time = date('Y-m-d H:i:s', $res['del_time']);
                }
                if (!in_array($res['qy_status'],[1,4]) && trim($res['qy_reasons'])) {
                    $qy_txt = '【同步失败】'.trim($res['qy_reasons']);
                    $qy_color = 'red';
                } elseif (1==$res['qy_status']) {
                    if (trim($res['qy_reasons'])) {
                        $qy_txt = '【同步成功】'.trim($res['qy_reasons']);
                    } else {
                        $qy_txt = '【同步成功】';
                    }
                    $qy_color = 'green';
                } elseif (4==$res['qy_status']) {
                    if (trim($res['qy_reasons'])) {
                        $qy_txt = '【同步删除】'.trim($res['qy_reasons']);
                    } else {
                        $qy_txt = '【同步删除】';
                    }
                    if ($res['del_time']) {
                        $qy_time = date('Y-m-d H:i:s', $res['del_time']);
                    }
                    $qy_color = 'green';
                } else {
                    $qy_txt = '未同步';
                    $qy_time = '';
                    $qy_color = '';
                }
            }
        }
        $this->assign('qy_color',$qy_color);
        $this->assign('qy_txt',$qy_txt);
        $this->assign('qy_time',$qy_time);

        $this->assign('res',$res);
        $this->display();
    }





    public function edit_people()
    {
        if(IS_POST){
            if(empty($_POST['name'])){
                $this->error('名称不能为空！');
            }
            if(empty($_POST['phone'])){
                $this->error('手机号不能为空！');
            }
            if(empty($_POST['id_card'])){
                $this->error('身份证号不能为空！');
            }
            if(is_idcard($_POST['id_card']) == false){
                $this->error('请填写正确的身份证号码');
            }
            $data['job_number'] = $_POST['job_number'];
            $data['name'] = $_POST['name'];

            $data['gender'] = $_POST['gender'];
            $data['phone'] = $_POST['phone'];
            $data['id_card'] = $_POST['id_card'];
            $data['ic_card'] = $_POST['ic_card'];
            $data['weixin_num'] = $_POST['weixin_num'];
            $data['job_create_time'] = $_POST['job_create_time'] ? strtotime($_POST['job_create_time']) : '';
            $data['open_door'] = $_POST['open_door'];
            $data['note'] = $_POST['note'];
            $property_group = M('Property_group')->where(array('id'=>$_POST['department_id']))->find();
            if ($property_group && $property_group['section_status']) {
                $data['type'] = $property_group['section_status'];
            }
            //未修改之前的数据  2020/10/26 start
            $raw_data = D('House_worker')->where(array('wid'=>$_POST['wid']))->field('phone,village_id')->find();

            $res = D('House_worker')->where(array('wid'=>$_POST['wid']))->data($data)->save();
            if($res){
                if ($raw_data && $raw_data['village_id']) {
                    //2020/10/26 start
                    $where['phone'] = $raw_data['phone']; // 原手机号
                    $where['village_id'] = $raw_data['village_id'];
                    $where['type'] = 4;
                    M('House_village_user_bind')->where($where)->data(['status'=>0])->save();
                    //2020/10/26 end
                    $data['village_id'] = $raw_data['village_id'];
                    $this->worker_user_amend($data,$_POST['wid']);
                }
                $this->success('修改成功！',U('index',['iframe' => $_GET['iframe']]));
            }else{
                $this->error('修改失败！');
            }

        }else{
            if(!isset($_GET['id'])){
                $this->error('数据不存在!');
            }
            $res = D('House_worker')->where(array('wid'=>$_GET['id']))->find();
            $qy_color = '';
            $qy_txt = '';
            $qy_time = '';
            if ($res['property_id']) {
                $where['bind_id'] = $res['property_id'];
                $wx_bind_id = M('House_enterprise_wx_bind')->where($where)->getField('pigcms_id');
                if ($wx_bind_id) {
                    if ($res['update_time']) {
                        $qy_time = date('Y-m-d H:i:s', $res['update_time']);
                    } elseif($res['qy_time']) {
                        $qy_time = date('Y-m-d H:i:s', $res['qy_time']);
                    } elseif ($res['del_time']) {
                        $qy_time = date('Y-m-d H:i:s', $res['del_time']);
                    }
                    if (!in_array($res['qy_status'],[1,4]) && trim($res['qy_reasons'])) {
                        $qy_txt = '【同步失败】'.trim($res['qy_reasons']);
                        $qy_color = 'red';
                    } elseif (1==$res['qy_status']) {
                        if (trim($res['qy_reasons'])) {
                            $qy_txt = '【同步成功】'.trim($res['qy_reasons']);
                        } else {
                            $qy_txt = '【同步成功】';
                        }
                        $qy_color = 'green';
                    } elseif (4==$res['qy_status']) {
                        if (trim($res['qy_reasons'])) {
                            $qy_txt = '【同步删除】'.trim($res['qy_reasons']);
                        } else {
                            $qy_txt = '【同步删除】';
                        }
                        if ($res['del_time']) {
                            $qy_time = date('Y-m-d H:i:s', $res['del_time']);
                        }
                        $qy_color = 'green';
                    } else {
                        $qy_txt = '未同步';
                        $qy_time = '';
                        $qy_color = '';
                    }
                }
            }
            $this->assign('qy_color',$qy_color);
            $this->assign('qy_txt',$qy_txt);
            $this->assign('qy_time',$qy_time);

            $this->assign('res',$res);
            $this->display();
        }
    }


    public function sys_qy_weixin_group() {
        $where['bind_id'] = $this->property_id;
        $access_obj = M('House_enterprise_wx_bind')->where($where)->field('access_token, expires_in, corpid, permanent_code')->find();
        if (!$access_obj['corpid'] || !$access_obj['permanent_code']) {
            $result = array('code'=>0,'msg'=>'请先进行企业微信授权');
            echo json_encode($result);
        }
        $info = D('Property_group')->sync_to_enterprise_wechat($this->property_id);
        if ($info) {
            $result = array('code'=>1,'msg'=>'已经同步');
            echo json_encode($result);
        } else{
            $result = array('code'=>0,'msg'=>'同步失败');
            echo json_encode($result);
        }
    }

    //工作人员对应的用户更新
    public function worker_user_amend($data,$wid=0){
        $data['type'] = 4 ;//工作人员
        $data['memo'] ='工作人员自动绑定小区';
        $data['status'] = 1 ;
        $data['pass_time'] = $_SERVER['REQUEST_TIME'];
//        $data['village_id'] = $this->house_session['village_id'];
        if($wid && $data['village_id']){
            $worker = D('House_worker')->where(array('village_id' => $data['village_id'], 'wid' => $wid))->find();
            // 原绑定信息
            $where['phone'] = $data['phone']; // 原手机号
            $where['village_id'] = $worker['village_id'];
            $where['type'] = 4;
            $bind_info = M('House_village_user_bind')->where($where)->find();
            if ($worker['phone'] != $where['phone'] || $bind_info['uid']<=0) { // 修改手机号
                $data['phone'] = $worker['phone'];
                $now_user = D('User')->get_user($data['phone'], 'phone');
                $data['uid'] = $now_user ? $now_user['uid'] : 0;
            }

            if ($bind_info) { // 已有业主信息 更新
                $set = M('House_village_user_bind')->where($where)->save($data);
                if (!$set) {
                    fdump('物业编辑工作人员错误>>>'.__LINE__,'api/log/'.date('Ymd').'/house_group/worker_user_amend_log',true);
                    fdump(M('House_village_user_bind')->getDbError(),'api/log/'.date('Ymd').'/house_group/worker_user_amend_log',true);
                    fdump($data,'api/log/'.date('Ymd').'/house_group/worker_user_amend_log',true);
                }
                $pigcms_id = $bind_info['pigcms_id'];
            }else{
                $data['usernum'] = rand(0,99999) . '-' . time();
                $data['add_time'] = $_SERVER['REQUEST_TIME'] ;
                $pigcms_id = M('House_village_user_bind')->add($data);
                if (!$pigcms_id) {
                    fdump('物业添加工作人员错误>>>'.__LINE__,'api/log/'.date('Ymd').'/house_group/worker_user_amend_log',true);
                    fdump(M('House_village_user_bind')->getDbError(),'api/log/'.date('Ymd').'/house_group/worker_user_amend_log',true);
                    fdump($data,'api/log/'.date('Ymd').'/house_group/worker_user_amend_log',true);
                }
            }
            if ($pigcms_id>0) {
                D('House_face_img')->house_user_face_device_info($data['uid'],$pigcms_id);
            }
        }elseif ($data['village_id']){
            $data['add_time'] = $_SERVER['REQUEST_TIME'] ;
            $data['usernum'] = rand(0,99999) . '-' . time();
            $pigcms_id = M('House_village_user_bind')->add($data);
            if (!$pigcms_id) {
                fdump('物业添加工作人员错误>>>'.__LINE__,'api/log/'.date('Ymd').'/house_group/worker_user_amend_log',true);
                fdump(M('House_village_user_bind')->getDbError(),'api/log/'.date('Ymd').'/house_group/worker_user_amend_log',true);
                fdump($data,'api/log/'.date('Ymd').'/house_group/worker_user_amend_log',true);
            }
        }
    }
}