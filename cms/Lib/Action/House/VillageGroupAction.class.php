<?php
/**
 * Created by PhpStorm.
 * User: 郭先森
 * Date: 2019/10/29
 * Time: 9:28
 */
//组织后台
class VillageGroupAction extends BaseAction{

    protected $property_id;
    protected $worker;

    public function _initialize(){
        parent::_initialize();

//        $property = session('property');
//        $this->property_id = $property['id'];
    }


    public function index_list()
    {
        if (!in_array(401, $this->house_session['menus']) && !in_array(290, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $now_house = D('House_village')->field(true)->where(array('village_id'=>$this->house_session['village_id']))->find();
        $now_property = D('House_property')->field(true)->where(array('id'=>$now_house['property_id']))->find();

        $res = M('Property_group')->where(array('property_id'=>$now_house['property_id']))->find();
        if(!$res){
            $data['fid'] = 0;
            $data['name'] = $now_property['property_name'];
            $data['type'] = 99;
            $data['status'] = 1;
            $data['sort'] = 1;
            $data['property_id'] = $now_property['id'];
            $data['village_id'] = 0;
            $data['section_type'] = '';
            M('Property_group')->data($data)->add();
        }

        $this->display('VillageGroup/index');
    }
    public function worker_list($department_id){
        $now_house = D('House_village')->field(true)->where(array('village_id'=>$this->house_session['village_id']))->find();
        $dataList = M('House_worker')->where(array('property_id'=>$now_house['property_id'],'department_id'=>$department_id,'is_del'=>0))->select();

        $worker_user = [];
        foreach($dataList as $k=>$value){

            //$worker_user[$value['department_id']][] = $value;
            $worker_user[] = $value;

        }
       return $worker_user;
    }
    public function index_new()
    {
        $db_house_village = M('House_village');
        $db_house_property = M('House_property');
        $now_house = $db_house_village->field(true)->where(array(
            'village_id'=>$this->house_session['village_id'],
            'status'=>array('neq', 5)
        ))->find();

        $dataList = M('Property_group')->where(array('property_id'=>$now_house['property_id'],'is_del'=>0))->order('sort desc,id desc')->select();
        $house_property = $db_house_property->where(array('id'=>$now_house['property_id']))->field('property_name')->find();
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
                $list['text'] = $value['name'].'-(共'.$count.'人)';
                $list['text_name'] = $value['name'];
            }elseif($value['type'] == 0 && $value['property_id'] && $value['village_id']){
                $where['people_type'] = array('in','0,1');
                $value['name'] = $db_house_village->where(['village_id' =>$value['village_id'],'status'=>array('neq', 5)])->getField('village_name');
                $count = M('House_worker')->where(array('property_id'=>$value['property_id'],'village_id'=>$value['village_id'],'is_del'=>0))->where($where)->count();
                $list['text'] = $value['name'].'-(共'.$count.'人)';
                $list['text_name'] = $value['name'];
            }elseif($value['type'] == 1 && $value['property_id'] && $value['village_id']){
                $count = M('House_worker')->where(array('property_id'=>$value['property_id'],'village_id'=>$value['village_id'],'department_id'=>$value['id'],'is_del'=>0))->count();
                $list['text'] = $value['name'].'-(共'.$count.'人)';
                $list['text_name'] = $value['name'];
            }elseif($value['type'] == 2 && $value['property_id']){
                $count = M('House_worker')->where(array('property_id'=>$value['property_id'],'department_id'=>$value['id'],'is_del'=>0))->count();
                $list['text'] = $value['name'].'-(共'.$count.'人)';
                $list['text_name'] = $value['name'];
            }else{
                $count = M('House_worker')->where(array('property_id'=>$value['property_id'],'department_id'=>$value['id'],'is_del'=>0))->count();
                $list['text'] = $value['name'].'-(共'.$count.'人)';
                $list['text_name'] = $value['name'];
            }
            $list['permission_url'] = '';
            $list['tag'] = '';
            $list['small_icon_name'] = 'fa fa-desktop';
            $list['level'] = $value['fid'];
            $list['after_html'] = '';
            if($value['type'] == 0){//小区
                $list['after_html'] .= '<span class="button_z">';
                if(in_array(292,$this->house_session['menus'])){
                    if($this->house_session['village_id'] == $value['village_id']){
                        $list['after_html'] .= '<button type="button" class="btn btn btn-info btn-xs" onclick="addPropertyGroup('.$value['id'].','.$value['village_id'].',this);">添加子组织</button>';
                    }

                }
                if(in_array(293,$this->house_session['menus'])){
                    if($this->house_session['village_id'] == $value['village_id']){
                        $list['after_html'] .= '&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn btn-info btn-xs" onclick="addHouseWorkerDep('.$value['village_id'].','.$value['type'].','.$value['id'].',this);">添加人员</button>';
                    }

                }
                if(in_array(296,$this->house_session['menus'])){
                    if($this->house_session['village_id'] == $value['village_id']){
                        $list['after_html'] .= '&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-danger btn-xs" onclick="del('.$value['id'].');">删除</button>';
                    }

                }
                $list['after_html'] .= '</span>';

            }elseif($value['type'] == 1){//小区部门
                $list['after_html'] .= '<span class="button_z">';
                if(in_array(292,$this->house_session['menus'])){
                    if($this->house_session['village_id'] == $value['village_id']){
                        $list['after_html'] .= '&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn btn-info btn-xs" onclick="addPropertyGroup('.$value['id'].','.$value['village_id'].',this);">添加子组织</button>';
                    }
                }
                if(in_array(297,$this->house_session['menus'])){
                    if($this->house_session['village_id'] == $value['village_id']){
                        $list['after_html'] .= '&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn btn-info btn-xs" onclick="edit_property_group('.$value['id'].');">编辑</button>';
                    }
                }
                if(in_array(293,$this->house_session['menus'])){
                    if($this->house_session['village_id'] == $value['village_id']){
                        $list['after_html'] .= '&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn btn-info btn-xs" onclick="addHouseWorkerDep('.$value['village_id'].','.$value['type'].','.$value['id'].',this);">添加人员</button>';
                    }

                }

                if(in_array(296,$this->house_session['menus'])){
                    if($this->house_session['village_id'] == $value['village_id']){
                        $list['after_html'] .= '&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-danger btn-xs" onclick="del('.$value['id'].');">删除</button>';
                    }
                }
                $list['after_html'] .= '</span>';
            }elseif($value['type'] == 2){//物业部门

            }elseif($value['type'] == 99){//物业

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
                    $list['text_name'] = $v1['name'];
                    $list['permission_url'] = '';
                    $list['tag'] = '';
                    $list['small_icon_name'] = 'fa fa-desktop';
                    $list['level'] = $v1['department_id'];
                    $list['after_html'] = '';
                   if(in_array(294,$this->house_session['menus'])){

                       $list['after_html'] .='<span class="button_z"><button type="button" class="btn btn-success btn-xs" onclick="info_people('.$v1['wid'].');">查看</button>&nbsp;&nbsp;&nbsp;&nbsp;';
                   }
                    if(in_array(295,$this->house_session['menus'])){
                        if($this->house_session['village_id'] == $v1['village_id']){
                            $list['after_html'] .='<button type="button" class="btn btn btn-info btn-xs" onclick="edit_people('.$v1['wid'].');">编辑</button>&nbsp;&nbsp;&nbsp;&nbsp;';
                        }

                    }
                    if(in_array(296,$this->house_session['menus'])){
                        if($this->house_session['village_id'] == $v1['village_id']){
                            $list['after_html'] .='<button type="button" class="btn btn-danger btn-xs" onclick="del_people('.$v1['wid'].');">删除</button></span>';
                        }


                    }

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
        if (!in_array(292, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if(IS_POST){
            $data = array();
           if($_POST['type'] == 0){//小区
               $data['fid'] = $_POST['fid'];
               $village = explode('-',$_POST['village_name']);
               $data['name'] = $village[1];
               $data['village_id'] = $village[0];
               $data['type'] = 0;
               $data['status'] = 1;
               $data['sort'] = $_POST['sort'];
               $data['property_id'] = $_POST['property_id'];
//               $data['section_type'] = '';
               $section_type = explode('-',$_POST['section_type']);
               $data['section_type'] = $section_type[1];
               $data['section_status'] = $section_type[0];

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
               $data['property_id'] = $_POST['property_id'];
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
               $data['property_id'] = $_POST['property_id'];
               $section_type = explode('-',$_POST['section_type']);
               $data['section_type'] = $section_type[1];
               $data['section_status'] = $section_type[0];
           }
          $add_a = M('Property_group')->data($data)->add();
            if($add_a){
                $data['id'] = $add_a;
                $index_num = $_GET['index_num'];
                $html = $this->single_info_html($data, false, $index_num);
                if (!$html) {
                    $html = '';
                }
                $msg = array('message' => '添加成功！','html' => $html,'status' => 1);
                $this->ajaxReturn($msg);
            }else{
                $this->error('添加失败！');
            }
        }else{
            $now_house = D('House_village')->field(true)->where(array('village_id'=>$this->house_session['village_id']))->find();
            $now_property = D('House_property')->field(true)->where(array('id'=>$now_house['property_id']))->find();
            $this->assign('now_property',$now_property);
            $house_village = M('House_village')->where(array('property_id'=>$now_property['id']))->select();
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
                    $html .= '<span class="button_z">';
                    if(in_array(292,$this->house_session['menus'])){
                        if($this->house_session['village_id'] == $data['village_id']){
                            $html .= '<button type="button" class="btn btn btn-info btn-xs" onclick="addPropertyGroup('.$data['id'].','.$data['village_id'].',this);">添加子组织</button>';
                        }

                    }
                    if(in_array(293,$this->house_session['menus'])){
                        if($this->house_session['village_id'] == $data['village_id']){
                            $html .= '&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn btn-info btn-xs" onclick="addHouseWorkerDep('.$data['village_id'].','.$data['type'].','.$data['id'].',this);">添加人员</button>';
                        }

                    }
                    if(in_array(296,$this->house_session['menus'])){
                        if($this->house_session['village_id'] == $data['village_id']){
                            $html .= '&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-danger btn-xs" onclick="del('.$data['id'].');">删除</button>';
                        }

                    }
                    $html .= '</span>';
                    break;
                case '1':
                    $html .= "<li class='list-group-item node-treeview-checkable node-selected' style='color:#000000;background-color:#ffffcc;'>".$index_str."<span class='icon glyphicon'></span>{$data['name']}-(共<span id='permission_text_{$data['id']}'>0</span>人)</li>";

                    $html .= '<span class="button_z">';
                    if(in_array(292,$this->house_session['menus'])){
                        if($this->house_session['village_id'] == $data['village_id']){
                            $html .= '&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn btn-info btn-xs" onclick="addPropertyGroup('.$data['id'].','.$data['village_id'].',this);">添加子组织</button>';
                        }
                    }
                    if(in_array(297,$this->house_session['menus'])){
                        if($this->house_session['village_id'] == $data['village_id']){
                            $html .= '&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn btn-info btn-xs" onclick="edit_property_group('.$data['id'].');">编辑</button>';
                        }
                    }
                    if(in_array(293,$this->house_session['menus'])){
                        if($this->house_session['village_id'] == $data['village_id']){
                            $html .= '&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn btn-info btn-xs" onclick="addHouseWorkerDep('.$data['village_id'].','.$data['type'].','.$data['id'].',this);">添加人员</button>';
                        }

                    }
                    if(in_array(296,$this->house_session['menus'])){
                        if($this->house_session['village_id'] == $data['village_id']){
                            $html .= '&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-danger btn-xs" onclick="del('.$data['id'].');">删除</button>';
                        }
                    }
                    $html .= '</span>';
                    break;
                case '2':
                    break;
                case '99':
                    break;
            }
        } else {
            $html .= "<li class='list-group-item node-treeview-checkable node-selected' style='color:#000000;background-color:#ffffcc;'>".$index_str."<span class='icon glyphicon'></span>{$data['name']}</li>";
            $html .='<span class="button_z">';
            if(in_array(294,$this->house_session['menus'])){
                $html .='<button type="button" class="btn btn-success btn-xs" onclick="info_people('.$data['wid'].');">查看</button>&nbsp;&nbsp;&nbsp;&nbsp;';
            }
            if(in_array(295,$this->house_session['menus'])){
                if($this->house_session['village_id'] == $data['village_id']){
                    $html .='<button type="button" class="btn btn btn-info btn-xs" onclick="edit_people('.$data['wid'].');">编辑</button>&nbsp;&nbsp;&nbsp;&nbsp;';
                }
            }
            if(in_array(296,$this->house_session['menus'])){
                if($this->house_session['village_id'] == $data['village_id']){
                    $html .='<button type="button" class="btn btn-danger btn-xs" onclick="del_people('.$data['wid'].');">删除</button>';
                }
            }
            $html .= '</span>';
        }

        return $html;
    }

    public function del()
    {
        if (!in_array(296,$this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if($_POST['id']){
            $result = $this->get_del($_POST['id']);
            $db_house_village = M('House_village');
            $property_id = $db_house_village->field(true)->where(array('village_id'=>$this->house_session['village_id']))->getField('property_id');
            D('Property_group')->sync_to_enterprise_wechat($property_id);
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
        if (!in_array(293, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if(IS_POST){
            if(empty($_POST['name'])){
                $this->error('名称不能为空！');
            }
            if(empty($_POST['phone'])){
                $this->error('手机号不能为空！');
            }
            $data['id_card'] = $_POST['id_card'] ? trim($_POST['id_card']) : '';
            if(empty($data['id_card'])){
                //$this->error('身份证号不能为空！');
            }elseif(!empty($data['id_card']) && is_idcard($data['id_card']) == false){
                $this->error('请填写正确的身份证号码');
            }
            $house_worker = D('House_worker')->where(array('phone'=>$_POST['phone'],'village_id'=>$_POST['village_id'],'status'=>1,'is_del'=>0))->find();
            if($house_worker){
                $this->error('工作人员已经存在!');
            }
            $village_id = $this->house_session['village_id'];
            $now_house = D('House_village')->field(true)->where(array('village_id'=>$village_id))->find();
            $data['job_number'] = $_POST['job_number'];
            $data['name'] = $_POST['name'];
            $data['status'] = 1;
            $data['gender'] = $_POST['gender'];
            $data['phone'] = $_POST['phone'];
            $data['ic_card'] = $_POST['ic_card'] ? trim($_POST['ic_card']) : '';
//            $data['weixin_num'] = $_POST['weixin_num'];
            $data['job_create_time'] = empty($_POST['job_create_time'])?'':strtotime($_POST['job_create_time']);
            $data['open_door'] = $_POST['open_door'];
            $data['note'] = $_POST['note'];
            $data['people_type'] = $_POST['type'];
            $data['village_id'] = empty($_POST['village_id'])?$village_id:$_POST['village_id'];
            $data['property_id'] = $now_house['property_id']?$now_house['property_id']:$_POST['village_id'];
            $data['department_id'] = empty($_POST['department_id'])?'':$_POST['department_id'];
            if($_POST['department_id']){
                $property_group = M('Property_group')->where(array('id'=>$_POST['department_id']))->find();
                //$data['type'] = ($property_group['section_status'] && intval($property_group['section_status'])>=0)?intval($property_group['section_status']):'-1';
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
            $now_house = D('House_village')->field(true)->where(array('village_id'=>$this->house_session['village_id']))->find();
            $now_property = D('House_property')->field(true)->where(array('id'=>$now_house['property_id']))->find();
            $this->assign('now_property',$now_property);
            $this->display();
        }
    }


    public function worker_add_new()
    {
        if(IS_POST){
            if(empty($_POST['name'])){
                $this->error('名称不能为空！');
            }
            if(empty($_POST['phone'])){
                $this->error('手机号不能为空！');
            }
            $data['job_number'] = $_POST['job_number'];
            $data['name'] = $_POST['name'];
            $data['status'] = 1;
            $data['gender'] = $_POST['gender'];
            $data['phone'] = $_POST['phone'];
            $data['id_card'] = $_POST['id_card'];
            $data['ic_card'] = $_POST['ic_card'];
            //$data['weixin_num'] = $_POST['weixin_num'];
            $data['job_create_time'] = strtotime($_POST['job_create_time']);
            $data['open_door'] = $_POST['open_door'];
            $data['note'] = $_POST['note'];
            $data['people_type'] = $_POST['type'];
            $data['village_id'] = empty($_POST['village_id'])?'':$_POST['village_id'];
            $data['property_id'] = $_POST['property_id'];
            $data['department_id'] = empty($_POST['department_id'])?'':$_POST['department_id'];
            $property_group = M('Property_group')->array(array('id'=>$_POST['department_id']))->find();
            $data['type'] = $property_group['section_status'];
            $add_a = M('House_worker')->data($data)->add();
            if($add_a){
                $this->success('添加成功！',U('VillageGroup/index_list'));
            }else{
                $this->error('添加失败！');
            }
        }else{
            $this->display();
        }
    }


    public function edit_property_group()
    {
        if (!in_array(295, $this->house_session['menus']) && !in_array(297, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
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
            M('House_worker')->where(array('department_id'=>$_POST['id'],'is_del'=>0))->data(['type'=>$data['section_status']])->save();
            if($res){
                $this->success('修改成功！',U('index_list'));
            }else{
                $this->error('修改失败，请检查是否进行了修改！');
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


    public function del_people(){
        if (!in_array(296,$this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if($_POST['id']){
            $data['is_del'] = 1;
            $data['openid'] = '';
            $result = D('House_worker')->where(array('wid'=>$_POST['id']))->data($data)->save();
            if($result){
                $house_worker = D('House_worker')->where(array('wid'=>$_POST['id']))->find();
                if($house_worker['phone'] && $house_worker['village_id']){
                    $bind_user =  D('House_village_user_bind')->where(array('phone'=>$house_worker['phone'],'village_id'=>$house_worker['village_id'],'type'=>4))->find();

                    //todo A185同步删除设备数据
                    D('House_village_face_door')->del_work_user_bind($bind_user['pigcms_id'], $house_worker['village_id'],[],$bind_user);
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
        if (!in_array(294, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
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
        if ($this->config['householdIdShow']=='1' && isset($res['village_id']) && isset($res['phone']) && $res['phone'] && $res['village_id']) {
            $where['phone'] = $res['phone'];
            $where['village_id'] = $res['village_id'];
            $where['type'] = 4;
            $pigcms_id = M('House_village_user_bind')->where($where)->getField('pigcms_id');
            $res['bindId'] = $pigcms_id;
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
                //$this->error('身份证号不能为空！');
            }elseif(!empty($_POST['id_card']) && is_idcard($_POST['id_card']) == false){
                $this->error('请填写正确的身份证号码');
            }
            $data['job_number'] = $_POST['job_number'];
            $data['name'] = $_POST['name'];

            $data['gender'] = $_POST['gender'];
            $data['phone'] = $_POST['phone'];
            $data['id_card'] = !empty($_POST['id_card']) ? trim($_POST['id_card']):'';
            $data['ic_card'] = $_POST['ic_card'];
            $data['weixin_num'] = trim($_POST['weixin_num']);
            $data['job_create_time'] = strtotime($_POST['job_create_time']);
            $data['open_door'] = $_POST['open_door'];
            $data['note'] = $_POST['note'];
            $data['update_time'] = time();

            //start 2020/10/20 小区物业工作人员
            $data['account'] = trim($_POST['account']);
            if ($_POST['password']) {
                $data['password'] = md5($_POST['password']);
            }
            if(!empty($data['account'])){
                $whereArr=['account'=>$data['account'],'wid'=>array('neq',$_POST['wid'])];
                $house_admin_info = D('House_admin')->where($whereArr)->find();
                if($house_admin_info){
                    $this->error('管理员账号已存在！');
                }
                $house_worker_tmp=D('House_worker')->where($whereArr)->field('wid')->find();
                if($house_worker_tmp){
                    $this->error('账号已存在了！');
                }
            }

            //end 2020/10/20

            $property_group = M('Property_group')->where(array('id'=>$_POST['department_id']))->find();
            $data['type'] = $property_group['section_status'];
            //未修改之前的数据  2020/10/26 start
            $raw_data = D('House_worker')->where(array('wid'=>$_POST['wid']))->field('phone,village_id')->find();
            //未修改之前的数据  2020/10/26 end
            $res = D('House_worker')->where(array('wid'=>$_POST['wid']))->data($data)->save();
            if($res){
                //2020/10/26 start
                $where['phone'] = $raw_data['phone']; // 原手机号
                $where['village_id'] = $raw_data['village_id'];
                $where['type'] = 4;
                M('House_village_user_bind')->where($where)->data(['status'=>0])->save();
                //2020/10/26 end
                if ($raw_data['village_id'] && !isset($data['village_id'])) {
                    $data['village_id'] = $raw_data['village_id'];
                }
                $this->worker_user_amend($data,$_POST['wid']);
                $this->success('修改成功！',U('VillageGroup/index_list'));
            }else{
                $this->error('修改失败，请检查是否进行了修改！');
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
			
			//找到自动生成的业主，用于上传人脸
			if($res){
				$user_bind = D('House_village_user_bind')->where(['phone' => $res['phone'], 'village_id' => $res['village_id'], 'type' => '4'])->find();
				$this->assign('user_bind', $user_bind);
			}
			
            $this->display();
        }
    }


    //工作人员对应的用户更新
    public function worker_user_amend($data,$wid=0){
        $data['type'] = 4 ;//工作人员
        $data['memo'] ='工作人员自动绑定小区';
        $data['status'] = 1 ;
        $data['pass_time'] = $_SERVER['REQUEST_TIME'];
        if($wid && $data['village_id']){
            $worker = D('House_worker')->where(array('village_id' => $this->house_session['village_id'], 'wid' => $wid))->find();
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

            if ($bind_info) { // 已有工作人员信息 更新
                $set = M('House_village_user_bind')->where($where)->save($data);
                if (!$set) {
                    fdump('小区编辑工作人员错误>>>'.__LINE__,'api/log/'.date('Ymd').'/house_group/worker_user_amend_log',true);
                    fdump(M('House_village_user_bind')->getDbError(),'api/log/'.date('Ymd').'/house_group/worker_user_amend_log',true);
                    fdump($data,'api/log/'.date('Ymd').'/house_group/worker_user_amend_log',true);
                }
                $pigcms_id = $bind_info['pigcms_id'];
            }else{
                $data['usernum'] = rand(0,99999) . '-' . time();
                $data['add_time'] = $_SERVER['REQUEST_TIME'] ;
                $pigcms_id = M('House_village_user_bind')->add($data);
                if (!$pigcms_id) {
                    fdump('小区添加工作人员错误>>>'.__LINE__,'api/log/'.date('Ymd').'/house_group/worker_user_amend_log',true);
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