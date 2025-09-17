<?php
/**
 * Created by PhpStorm.
 * User: win7
 * Date: 2018/11/4
 * Time: 15:41
 * 从业者模块
 */

class EmployeeAction extends BaseAction {
    /**
     * 从业者配置
    */
    public function employee_config(){
        redirect(U('Config/index',array('galias'=>'employee','header'=>'Employee/config')));
    }
    public function config(){
        $this->display();
    }
    /**
     * 从业者分类
     */
    public function category(){
        $database_employee_category = D('Employee_category');
        $condition_employee_category['cat_fid'] = intval($_GET['cat_fid']);
        $condition_employee_category['cat_pfcid'] = intval($_GET['cat_pfcid']);
        $condition_employee_category['cat_status'] = array('neq','4');

        $count_employee_category = $database_employee_category->where($condition_employee_category)->count();
        import('@.ORG.system_page');
        $p = new Page($count_employee_category,50);
        $category_list = $database_employee_category->field(true)->where($condition_employee_category)->order('`cat_sort` DESC,`cat_id` ASC')->limit($p->firstRow.','.$p->listRows)->select();
        $this->assign('category_list',$category_list);
        $pagebar = $p->show();
        $this->assign('pagebar',$pagebar);

        if($_GET['cat_fid']){
            // 获取父级分类信息
            $condition_now_employee_category['cat_id'] = intval($_GET['cat_fid']);
            $now_category = $database_employee_category->field(true)->where($condition_now_employee_category)->find();
            if(empty($now_category)){
                $this->error_tips('没有找到该分类信息！',3,U('Employee/category'));
            }
            $this->assign('now_category',$now_category);
        }
        if($_GET['cat_pfcid']){
            // 获取父父级分类信息
            $condition_now_employee_category['cat_id'] = intval($_GET['cat_pfcid']);
            $pf_now_category = $database_employee_category->field(true)->where($condition_now_employee_category)->find();
            if(empty($pf_now_category)){
                $this->error_tips('没有找到该分类信息！',3,U('Employee/category'));
            }
            $this->assign('pf_now_category',$pf_now_category);
        }
        $this->display();
    }
    /**
     * 添加分类页面
     */
    public function cat_add(){
        $database_merchant_category = M(_view('Merchant_category'));
        $condition_merchant_category['cat_status'] = 1;
        $condition_merchant_category['cat_fid'] = 0;
        // 获取商家分类信息
        $merchant_category = $database_merchant_category->field(true)->where($condition_merchant_category)->select();
        $database_employee_category = M('Employee_category');
        foreach ($merchant_category as $key=>$val) {
            if ($database_employee_category->where(array('merchant_cat_id' => $val['cat_id']))->getField('merchant_cat_id')) {
                unset($merchant_category[$key]);
            }
        }


        $this->assign('merchant_category',$merchant_category);
        $this->assign('bg_color','#F3F3F3');
        $this->display();
    }
    /**
     * 添加分类
     */
    public function cat_modify(){
        if(IS_POST){
            $image = D('Image')->handle($this->system_session['id'], 'system', 0, array('size' => 10));
            if (!$image['error']) {
                $_POST = array_merge($_POST, str_replace('/upload/system/', '', $image['url']));
            }

            if ($_POST['cat_fid'] > 0 && $_POST['cat_pfcid'] > 0) {
                $_POST['subdir'] = 3;
            } elseif ($_POST['cat_fid'] > 0) {
                $_POST['subdir'] = 2;
            } else {
                $_POST['subdir'] = 1;
            }
            $database_employee_category = D('Employee_category');
            if ($_POST['merchant_cat_id']) {
                $database_merchant_category = M('Merchant_category');
                $condition_merchant_category['cat_status'] = 1;
                $condition_merchant_category['cat_fid'] = 0;

                if ($_POST['merchant_cat_id']>0 && M(_view('Merchant_category'))->where(array('merchant_cat_id' => $_POST['merchant_cat_id']))->getField('merchant_cat_id')) {
                    $this->frame_error_tips('添加失败！该商家分类已被关联~');
                } elseif ($_POST['merchant_cat_id']<=0) {
                    unset($_POST['merchant_cat_id']);
                    unset($_POST['merchant_cat_url']);
                } else {
                    $condition_merchant_category['cat_id'] = $_POST['merchant_cat_id'];
                    // 获取商家分类信息
                    $_POST['merchant_cat_url'] = M(_view('Merchant_category'))->field(true)->where($condition_merchant_category)->getField('cat_url');
                }
            }
            $_POST['add_time'] = time();

            if($cat_id = $database_employee_category->data($_POST)->add()){
                $this->write_field_fixed($cat_id);
                $this->frame_submit_tips(1,'添加成功！');
            }else{
                $this->frame_error_tips('添加失败！请重试~');
            }
        }else{
            $this->frame_error_tips('非法提交,请重新提交~');
        }
    }
    /**
     * 查看分类、编辑分类 信息获取
     */
    public function cat_edit(){
        $this->assign('bg_color','#F3F3F3');
        $database_employee_category = D('Employee_category');
        $condition_employee_category['cat_id'] = intval($_GET['cat_id']);
        $now_category = $database_employee_category->field(true)->where($condition_employee_category)->find();
        if(empty($now_category)){
            $this->frame_error_tips('没有找到该分类信息！');
        }
        $this->assign('now_category',$now_category);

        $database_merchant_category = M('merchant_category');
        $condition_merchant_category['cat_status'] = 1;
        $condition_merchant_category['cat_fid'] = 0;
        // 获取商家分类信息
        $merchant_category = M(_view('Merchant_category'))->field(true)->where($condition_merchant_category)->select();
        foreach ($merchant_category as $key=>$val) {
            if ($database_employee_category->where(array('merchant_cat_id' => $val['cat_id']))->getField('merchant_cat_id')) {
                if ($val['cat_id'] != $now_category['merchant_cat_id']) {
                    unset($merchant_category[$key]);
                }
            }
        }
        $this->assign('merchant_category',$merchant_category);

        $this->display();
    }
    /**
     * 编辑分类 操作
     */
    public function cat_amend(){
        if(IS_POST){

            $image = D('Image')->handle($this->system_session['id'], 'system', 0, array('size' => 10));
            if (!$image['error']) {
                $_POST = array_merge($_POST, str_replace('/upload/system/', '', $image['url']));
            }

            if ($_POST['cat_fid'] > 0 && $_POST['cat_pfcid'] > 0) {
                $_POST['subdir'] = 3;
            } elseif ($_POST['cat_fid'] > 0) {
                $_POST['subdir'] = 2;
            } else {
                $_POST['subdir'] = 1;
            }

            $database_employee_category = D('Employee_category');
            if ($_POST['merchant_cat_id']>0) {
                $database_merchant_category = M('Merchant_category');
                $condition_merchant_category['cat_status'] = 1;
                $condition_merchant_category['cat_fid'] = 0;
                $condition_merchant_category['cat_id'] = $_POST['merchant_cat_id'];
                $cat_id = $database_employee_category->where(array('merchant_cat_id' => $_POST['merchant_cat_id']))->getField('cat_id');
                if ($cat_id && $cat_id != $_POST['cat_id']) {
                    $this->frame_error_tips('添加失败！该商家分类已被关联~');
                }
                // 获取商家分类信息
                $_POST['merchant_cat_url'] = $M(_view('Merchant_category'))->field(true)->where($condition_merchant_category)->getField('cat_url');
            }

            if($database_employee_category->data($_POST)->save()){
                D('Image')->update_table_id('/upload/system/' . $_POST['cat_pic'], $_POST['cat_id'], 'employee_category');
                $this->frame_submit_tips(1,'编辑成功！');
            }else{
                $this->frame_error_tips('编辑失败！您是不是没做过修改？请重试~');
            }
        }else{
            $this->frame_error_tips('非法提交,请重新提交~');
        }
    }
    /**
     * 删除分类
     */
    public function cat_del(){
        if(IS_POST){
            $database_employee_category = D('Employee_category');
            $condition_now_employee_category['cat_id'] = intval($_POST['cat_id']);
            $data_now_employee_category['cat_status'] = '4';
            $now_category = $database_employee_category->field(true)->where($condition_now_employee_category)->find();
            if($database_employee_category->where($condition_now_employee_category)->data($data_now_employee_category)->save()){
                if(empty($now_category['cat_fid'])){
                    $condition_son_employee_category['cat_fid'] = $now_category['cat_id'];
                    $database_employee_category->where($condition_son_employee_category)->data($data_now_employee_category)->save();
                    $condition_group['cat_fid'] = $now_category['cat_id'];
                }else{
                    $condition_group['cat_id'] = $now_category['cat_id'];
                }
                $this->success('删除成功！');
            }else{
                $this->error('删除失败！请重试~');
            }
        }else{
            $this->error('非法提交,请重新提交~');
        }
    }

    /**
     * 自定义填写选项列表
     */
    public function write_field(){
        $database_employee_category = D('Employee_category');
        $condition_now_employee_category['cat_id'] = intval($_GET['cat_id']);
        $now_category = $database_employee_category->field(true)->where($condition_now_employee_category)->find();
        if(empty($now_category)){
            $this->frame_error_tips('没有找到该分类信息！');
        }
        if(!empty($now_category['cat_fid']) || !empty($now_category['cat_pfcid'])){
            $this->frame_error_tips('该分类是子分类，无法使用自定义填写字段功能！');
        }
        $write_group_info = M('Employee_write_group')->field(true)->order('gsort DESC, gid DESC')->where(array('status'=>0))->getField('gid, gname,status');
        if (empty($write_group_info)) {
            $write_group_info = D('Employee_category')->write_group_info;
        }

        $database_employee_write_field = M('Employee_category_write_field');
        $condition_now_employee_category['status'] = 0;
        $write_field = $database_employee_write_field->field(true)->where($condition_now_employee_category)->order('sort DESC, id ASC')->select();
        if (!empty($write_field)) {
            foreach ($write_field as &$val) {
                if ($write_group_info[$val['group_id']]) {
                    if (empty($write_group_info[$val['group_id']]['write_info'])) {
                        $write_group_info[$val['group_id']]['write_info'] = array();
                    }
                    $write_group_info[$val['group_id']]['write_info'][] = $val;
                }
            }
            $this->assign('write_group_info',$write_group_info);
        }

        $this->assign('now_category',$now_category);
        $this->assign('write_field',$write_field);

        $this->display();
    }


    /**
     * 自定义填写选项添加字段
     */
    public function write_field_add(){
        $this->assign('bg_color','#F3F3F3');

        $this->display();
    }

    /**
     * 添加分类固定填写字段
     * @param $cat_id
     */
    private function write_field_fixed($cat_id) {
        // 0 单行文本 1 多行文本 2 地图 3 下拉框选择 4 数字 5 邮件 6 日期 7 时间 8 日期时间 9 手机 10 多张图片  拓展  11 单选   12 单图
        $time = time();
        $info = array(
            0=>array(
                'name' => '对外称呼', // 前端个人主页和列表页作为名称展示
                'alias_name' => 'nickname',
                'type' => 0,
                'sort' => 100,
                'is_show' => 1,
                'no_del' => 1,
                'no_hide' => 1,
                'iswrite' => 1,
                'cat_id' => $cat_id,
                'des' => '前端个人主页和列表页作为名称展示',
                'add_time' => $time,
                'group_id' => 1
            ),
            1=>array(
                'name' => '性别',
                'alias_name' => 'working_sex',
                'type' => 11,
                'sort' => 99,
                'is_show' => 1,
                'no_del' => 1,
                'no_hide' => 1,
                'iswrite' => 1,
                'cat_id' => $cat_id,
                'des' => '性别',
                'add_time' => $time,
                'group_id' => 1
            ),
            2=>array(
                'name' => '从业年限',
                'alias_name' => 'working_years',
                'type' => 0,
                'sort' => 98,
                'is_show' => 1,
                'no_del' => 1,
                'no_hide' => 1,
                'iswrite' => 1,
                'cat_id' => $cat_id,
                'des' => '从业年限',
                'add_time' => $time,
                'group_id' => 1
            ),
            3=>array(
                'name' => '个性签名',
                'alias_name' => 'personality_signature',
                'type' => 1,
                'sort' => 97,
                'is_show' => 1,
                'no_del' => 1,
                'no_hide' => 1,
                'iswrite' => 1,
                'cat_id' => $cat_id,
                'des' => '个性签名',
                'add_time' => $time,
                'group_id' => 1
            ),
            4=>array(
                'name' => '头像',
                'alias_name' => 'working_avatar',
                'type' => 12,
                'sort' => 96,
                'is_show' => 1,
                'no_del' => 1,
                'no_hide' => 1,
                'iswrite' => 1,
                'cat_id' => $cat_id,
                'des' => '选择头像（如不选择默认使用微信头像）',
                'add_time' => $time,
                'group_id' => 1
            ),
            5=>array(
                'name' => '所属单位',
                'alias_name' => 'work_unit',
                'type' => 0,
                'sort' => 95,
                'is_show' => 1,
                'no_del' => 1,
                'no_hide' => 1,
                'iswrite' => 1,
                'cat_id' => $cat_id,
                'des' => '所属单位',
                'add_time' => $time,
                'group_id' => 2
            ),
            6=>array(
                'name' => '单位电话', // 前端点击电话图标即拨打此电话
                'alias_name' => 'unit_phone',
                'type' => 9,
                'sort' => 94,
                'is_show' => 1,
                'no_del' => 1,
                'no_hide' => 1,
                'iswrite' => 1,
                'cat_id' => $cat_id,
                'des' => '单位电话',
                'add_time' => $time,
                'group_id' => 2
            ),
            7=>array(
                'name' => '详细地址', // 单位区域商圈/详细地址和导航（前端点击即可导航）
                'alias_name' => 'unit_address',
                'type' => 2,
                'sort' => 93,
                'is_show' => 1,
                'no_del' => 1,
                'no_hide' => 1,
                'iswrite' => 1,
                'cat_id' => $cat_id,
                'des' => '详细地址',
                'add_time' => $time,
                'group_id' => 2
            ),
            8=>array(
                'name' => '个人风采', // 可添加照片
                'alias_name' => 'person_mien',
                'type' => 10,
                'sort' => 92,
                'is_show' => 1,
                'no_del' => 1,
                'no_hide' => 1,
                'iswrite' => 1,
                'cat_id' => $cat_id,
                'des' => '个人风采',
                'add_time' => $time,
                'group_id' => 4
            )

        );
        $database_employee_write_field = M('Employee_category_write_field');
        $database_employee_write_field->addAll($info);
    }


    /**
     * 自定义填写选项添加字段 操作
     */
    public function write_field_modify(){
        if(IS_POST){
            $database_employee_write_field = M('Employee_category_write_field');
            $condition_now_employee_write_field['cat_id'] = intval($_POST['cat_id']);
            $condition_now_employee_write_field['name'] = trim($_POST['name']);
            $write_field = $database_employee_write_field->field(true)->where($condition_now_employee_write_field)->find();

            if(!empty($write_field)){
                $this->error('该填写项已经添加，请勿重复添加！');
            }

            $post_data['name'] = trim($_POST['name']);
            $post_data['type'] = $_POST['type'];
            $post_data['group_id'] = 3;
            $post_data['des'] = $_POST['des'];
            $post_data['sort'] = strval($_POST['sort']);
            $post_data['iswrite'] = $_POST['iswrite'];
            $post_data['is_show'] = $_POST['is_show'];
            $post_data['add_time'] = time();
            $post_data['cat_id'] =intval($_POST['cat_id']);
            $post_data['cat_id'] =intval($_POST['cat_id']);
            if ($_POST['type'] == 3 && empty($_POST['use_field'])) {
                $this->error('请填写候选项！');
            } elseif(!empty($_POST['use_field'])){
                $use_field = explode(PHP_EOL, $_POST['use_field']);
                $post_data['use_field'] = serialize($use_field);
            }
            if($database_employee_write_field->data($post_data)->add()){
                $this->success('添加成功！');
            }else{
                $this->error('添加失败！请重试~');
            }
        }else{
            $this->error('非法提交,请重新提交~');
        }
    }

    /**
     * 自定义填写项编辑页面
     */
    public function write_field_edit() {
        $database_employee_write_field = M('Employee_category_write_field');
        $condition_now_employee_write_field['cat_id'] = intval($_GET['cat_id']);
        $condition_now_employee_write_field['name'] = trim($_GET['name']);
        $write_field = $database_employee_write_field->field(true)->where($condition_now_employee_write_field)->find();
        if ($write_field['use_field']) {
            $write_field['use_field'] = unserialize($write_field['use_field']);
            if ($write_field['use_field']){
                $info = '';
                foreach ($write_field['use_field'] as $val) {
                    if ($val && !$info) {
                        $info .= $val;
                    } elseif ($val) {
                        $info .= '\n'.$val;
                    }
                }
                $write_field['use_field_info'] = $info;
            }
        }
        $this->assign('write_field',$write_field);
        $this->assign('bg_color','#F3F3F3');
        $this->display();
    }


    /**
     * 自定义填写项编辑 操作
     */
    public function write_field_edit_modify(){
        if(IS_POST){
            $database_employee_write_field = M('Employee_category_write_field');
            $condition_now_employee_write_field['cat_id'] = intval($_POST['cat_id']);
            $condition_now_employee_write_field['name'] = trim($_POST['name']);
            $write_field = $database_employee_write_field->field(true)->where($condition_now_employee_write_field)->find();

            if(empty($write_field)){
                $this->error('此填写项不存在！');
            }
            $edit_data = array();
            if ($_POST['type'] != '' && $_POST['type'] != $write_field['type']) {
                $edit_data['type'] = $_POST['type'];
            }
            if ($_POST['des'] != '' && $_POST['des'] != $write_field['des']) {
                $edit_data['des'] = $_POST['des'];
            }
            if ($_POST['sort'] != '' && $_POST['sort'] != $write_field['sort']) {
                $edit_data['sort'] = $_POST['sort'];
            }
            if ($_POST['iswrite'] != '' && $_POST['iswrite'] != $write_field['iswrite']) {
                $edit_data['iswrite'] = $_POST['iswrite'];
            }
            if ($_POST['is_show'] != '' && $_POST['is_show'] != $write_field['is_show']) {
                $edit_data['is_show'] = $_POST['is_show'];
            }
            if ($_POST['type'] == 3 && empty($_POST['use_field'])) {
                $this->error('请填写候选项！');
            } elseif(!empty($_POST['use_field'])){
                $use_field = explode(PHP_EOL, $_POST['use_field']);
                $edit_data['use_field'] = serialize($use_field);
            }
            if($database_employee_write_field->where($condition_now_employee_write_field)->data($edit_data)->save()){
                $this->success('修改成功！');
            }else{
                $this->error('修改失败！请重试~');
            }
        }else{
            $this->error('非法提交,请重新提交~');
        }
    }

    /**
     * 自定义填写选项添加字段 删除
     */
    public function write_field_del(){
        if(IS_POST){
            $database_employee_write_field = M('Employee_category_write_field');
            $condition_now_employee_write_field['cat_id'] = intval($_POST['cat_id']);
            $condition_now_employee_write_field['name'] = trim($_POST['name']);
            $write_field = $database_employee_write_field->field(true)->where($condition_now_employee_write_field)->find();

            if(!empty($write_field)){
                if ($write_field['no_del'] == 1) {
                    $this->error('此填写项不允许删除！');
                }
            }else{
                $this->error('此填写项不存在！');
            }
            $data_employee_write_field['status'] = 1;
            if($database_employee_write_field->where($condition_now_employee_write_field)->data($data_employee_write_field)->save()){
                $this->success('删除成功！');
            }else{
                $this->error('删除失败！请重试~');
            }
        }else{
            $this->error('非法提交,请重新提交~');
        }
    }


    /*自定义标签*/
    /**
     * 标签列表获取
     */
    public function employee_memory_list() {
        $database_employee_memory = D('Employee_memory');
        $status = I('status');
        if ($status != '' && in_array($status, array(0,1))) {
            $where['status'] = $status;// 查询正常和禁用状态的标签
        } else {
            $where['status'] = array('in', array(0,1));// 查询正常和禁用状态的标签
        }
        $count_list= $database_employee_memory->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count_list,30);
        $employee_memory_list = $database_employee_memory
            ->where($where)
            ->limit($p->firstRow.','.$p->listRows)
            ->select();
        $pagebar = $p->show();
        $this->assign('pagebar',$pagebar);
        $this->assign('employee_memory_list',$employee_memory_list);
        $this->display();
    }

    /**
     * 标签添加页面
     */
    public function memory_add() {
        $this->display();
    }
    /**
     *  标签添加页面添加
     */
    public function memory_modify() {
        if(IS_POST){
            $database_employee_memory = D('Employee_memory');
            $_POST['add_time'] = time();
            $info = $database_employee_memory->where(array('memory_name'=>$_POST['memory_name'], 'status' => array('neq', 2)))->find();
            if ($info) {
                if ($info['status'] == 0) {
                    $this->error('该标签已经存在！');
                } else {
                    $this->error('该标签已经存在,但是被禁用！');
                }
            } else {
                if($database_employee_memory->data($_POST)->add()){
                    $this->success('添加成功！');
                }else{
                    $this->error('添加失败！请重试~');
                }
            }
        }else{
            $this->error('非法提交,请重新提交~');
        }
    }
    /**
     * 切换标签禁用和启用
     */
    public function switch_memory_status() {
        $database_employee_memory = D('Employee_memory');
        $status = I('status');
        $memory_id = I('memory_id');
        $info = $database_employee_memory->where(array('memory_id' => $memory_id))->find();
        if (empty($info) || $info['status'] == 2) {
            $this->error('标签不存在或者已经删除~');
        } elseif ($info['status'] == $status) {
            $this->error('未改变状态~');
        }
        if ($status != '' && in_array($status, array(0,1))) {
            $save['status'] = $status;// 查询正常和禁用状态的标签
            if($database_employee_memory->where(array('memory_id' => $memory_id))->data($save)->save()){
                $this->success('修改成功！');
            }else{
                $this->error('修改失败！请重试~');
            }
        } else {
            $this->error('传值错误~');
        }
    }

    /**
     * 删除标签
     */
    public function del_memory() {
        $database_employee_memory = D('Employee_memory');
        $memory_id = I('memory_id');
        $info = $database_employee_memory->where(array('memory_id' => $memory_id))->find();
        if (empty($info) || $info['status'] == 2) {
            $this->error('标签不存在或者已经删除~');
        }
        $save['status'] = 2;// 查询正常和禁用状态的标签
        if($database_employee_memory->where(array('memory_id' => $memory_id))->data($save)->save()){
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！请重试~');
        }
    }

    /**
     * 标签关联从业者相关信息
     */
    public function employee_memory_user() {
        $database_employee_memory = D('Employee_memory');
        $memory_id = I('memory_id');
        $info = $database_employee_memory->where(array('memory_id' => $memory_id))->find();
        if (empty($info) || $info['status'] == 2) {
            $this->error('标签不存在或者已经删除~');
        }
        $this->assign('info',$info);
        // 查询关联信息
        $employee_info = D('Employee_memory_bind')->info_memory($memory_id);
        $this->assign('employee_info',$employee_info);


        $this->display();
    }

    /**
     * 编辑页面
     */
    public function memory_edit() {
        $database_employee_memory = D('Employee_memory');
        $memory_id = I('memory_id');
        $info = $database_employee_memory->where(array('memory_id' => $memory_id))->find();
        if (empty($info) || $info['status'] == 2) {
            $this->error('标签不存在或者已经删除~');
        }
        $this->assign('info',$info);
        $this->display();
    }

    /**
     * 编辑内容
     */
    public function memory_edit_modify() {
        $database_employee_memory = D('Employee_memory');
        $memory_id = I('memory_id');
        $info = $database_employee_memory->where(array('memory_id' => $memory_id))->find();
        if (empty($info) || $info['status'] == 2) {
            $this->error('标签不存在或者已经删除~');
        }
        $save = array();
        foreach ($info as $key => $val) {
            if ($_POST[$key] != $val) {
                $save[$key] = $_POST[$key];
            }
        }
        if ($save) {
            if($database_employee_memory->where(array('memory_id' => $memory_id))->data($save)->save()){
                $this->success('删除成功！');
            }else{
                $this->error('删除失败！请重试~');
            }
        } else {
            $this->error('没有任何改动！请重试~');
        }
    }

    /*评论信息处理*/
    public function reply_list() {
        $database_employee_reply = D('Employee_reply');
        $status = I('status');
        if ($status != '' && in_array($status, array(0,1))) {
            $where['a.status'] = $status;// 查询正常和禁用状态的评论
        } else {
            $where['a.status'] = array('in', array(0,1));// 查询正常和禁用状态的评论
        }
        $employee_id = I('employee_id');
        if ($employee_id) {
            $where['a.employee_id'] = $employee_id;// 查询对应从业者评论
            $true_name = M('Employee_list')->where(array('id' => $employee_id))->getField('true_name');
            $this->assign('true_name',$true_name);
            $this->assign('employee',$employee_id);
        }

        $count_list= $database_employee_reply->alias('a')->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count_list,30);
        $list = $database_employee_reply->alias('a')
            ->field('a.*, b.nickname, c.true_name as employee_name')
            ->join('LEFT JOIN pigcms_user as b on a.reply_uid=b.uid')
            ->join('LEFT JOIN pigcms_employee_list as c on a.employee_id=c.id')
            ->where($where)
            ->limit($p->firstRow.','.$p->listRows)
            ->select();
        if ($list) {
            foreach ($list as &$val) {
                if ($val['image']) {
                    $val['image'] = unserialize($val['image']);
                }
            }
        }
        $pagebar = $p->show();
        $this->assign('pagebar',$pagebar);
        $this->assign('list',$list);
        $this->display();
    }


    /*评论回复信息处理*/
    public function comment_reply_list() {
        $database_employee_reply_info = D('Employee_reply_info');
        $reply_id = I('reply_id');
        if (empty($reply_id)) {
            $this->error_tips('缺少评论对象！');
        }
        $this->assign('reply_id',$reply_id);
        $m_employee_reply = M('Employee_reply');
        $comment_info = $m_employee_reply->where(array('id' => $reply_id))->find();
        if (empty($comment_info) || $comment_info['status'] == 1) {
            $this->error_tips('评论对象不存在或者已经被删除！');
        }
        if ($comment_info['employee_id']) {
            $true_name = M('Employee_list')->where(array('id' => $comment_info['employee_id']))->getField('true_name');
            $this->assign('true_name',$true_name);
            $this->assign('employee',$comment_info['employee_id']);
        }
        $comment_user = M('User')->where(array('uid' => $comment_info['reply_uid']))->field('nickname, avatar')->find();
        $this->assign('comment_user', $comment_user);

        $info = $database_employee_reply_info->employee_reply_info($reply_id, 20, 0);
        $this->assign($info);

        $this->display();
    }

    /**
     * 切换评论禁用和启用
     */
    public function switch_reply_status() {
        $database_employee_reply = D('Employee_reply');
        $status = I('status');
        $reply_id = I('reply_id');
        $info = $database_employee_reply->where(array('id' => $reply_id))->find();
        if (empty($info) || $info['status'] > 1) {
            $this->error('标签不存在或者已经删除~');
        } elseif ($info['status'] == $status) {
            $this->error('未改变状态~');
        }
        if ($status != '' && in_array($status, array(0,1))) {
            $save['status'] = $status;// 查询正常和禁用状态的标签
            if($database_employee_reply->where(array('id' => $reply_id))->data($save)->save()){
                $this->success('修改成功！');
            }else{
                $this->error('修改失败！请重试~');
            }
        } else {
            $this->error('传值错误~');
        }
    }
    /**
     * 切换评论回复禁用和启用
     */
    public function switch_reply_info_status() {
        $database_employee_reply = D('Employee_reply_info');
        $status = I('status');
        $reply_id = I('reply_id');
        $info = $database_employee_reply->where(array('id' => $reply_id))->find();
        if (empty($info) || $info['status'] > 1) {
            $this->error('标签不存在或者已经删除~');
        } elseif ($info['status'] == $status) {
            $this->error('未改变状态~');
        }
        if ($status != '' && in_array($status, array(0,1))) {
            $save['status'] = $status;// 查询正常和禁用状态的标签
            if($database_employee_reply->where(array('id' => $reply_id))->data($save)->save()){
                $this->success('修改成功！');
            }else{
                $this->error('修改失败！请重试~');
            }
        } else {
            $this->error('传值错误~');
        }
    }


    /**
     * 编辑或者查看消息
     */
    public function reply_edit() {
        $database_employee_reply = D('Employee_reply');
        $reply_id = I('reply_id');
        $info = $database_employee_reply->alias('a')
            ->field('a.*, b.nickname, c.true_name as employee_name')
            ->join('LEFT JOIN pigcms_user as b on a.reply_uid=b.uid')
            ->join('LEFT JOIN pigcms_employee_list as c on a.employee_id=c.id')
            ->where(array('a.id' => $reply_id))
            ->find();
        if (empty($info) || $info['status'] > 1) {
            $this->error('评论不存在或者已经删除~');
        }
        if ($info) {
            if ($info['image']) {
                $info['image'] = unserialize($info['image']);
            }
        }

        $this->assign('info',$info);

        $reply_memory = D('Employee_memory_bind')->reply_memory($reply_id);
        $this->assign('reply_memory',$reply_memory);
        $this->display();
    }



    public function employee_list(){

        $database_employee = D('Employee_list');
        $where=array();
        $field = I('get.searchtype');
        $keyword = trim(I('get.keyword'));
        if( $field == 'nickname' && $keyword){//昵称查询
            $where['b.'.$field] = array('like','%'.$keyword.'%');
        }
        if( $field == 'true_name' && $keyword){//姓名查询
            $where['a.'.$field] = array('like','%'.$keyword.'%');
        }
        if( $field == 'phone' && $keyword){//手机号查询
            $where['a.business_phone'] = array('like','%'.$keyword.'%');
        }
        if(I('get.is_open') != ''){//状态查询
            $where['a.is_open'] = I('get.is_open');
        }
        if($_GET['province_idss']){
            $where['a.province_id'] = $_GET['province_idss'];
        }
        if($_GET['city_idss']){
            $where['a.city_id'] = $_GET['city_idss'];
        }
        if($_GET['area_id']){
            $where['a.area_id'] = $_GET['area_id'];
        }
        $where['a.examine_status'] = 1;//已审核
        $count_employee_list= $database_employee->alias('a')->where($where)->join('join pigcms_user as b on a.uid=b.uid')->count();

        import('@.ORG.system_page');
        $p = new Page($count_employee_list,50);
        $employee_list = $database_employee
                    ->alias('a')
                    ->field('a.*,b.nickname,b.phone,b.now_money,b.openid')
                    ->where($where)
                    ->join('join pigcms_user as b on a.uid=b.uid')
                    ->limit($p->firstRow.','.$p->listRows)
                    ->select();

        // 处理粉丝数-即该从业者推广的用户数
        if ($employee_list) {
            $m_area=M('Area');
            foreach ($employee_list as &$val) {
                if ($val['openid']) {
                    $val['fans_num'] = M('User_spread')->where(array('spread_openid'=>$val['openid'],'openid'=>array('neq','')))->count();
                } else {
                    $val['fans_num'] = 0;
                }
                // 处理下从业者所在区域显示
                if ($val['province_id']) {
                    $val['province_name'] = $m_area->where(array('area_id'=>$val['province_id']))->getField('area_name');
                }
                if ($val['city_id']) {
                    $val['city_name'] = $m_area->where(array('area_id'=>$val['city_id']))->getField('area_name');
                }
                if ($val['area_id']) {
                    $val['area_name'] = $m_area->where(array('area_id'=>$val['area_id']))->getField('area_name');
                }
            }
        }


        $pagebar = $p->show();
        $this->assign('pagebar',$pagebar);
        $this->assign('employee_list',$employee_list);
        $this->display();
    }

    /**删除对应正常状态的从业者*/
    public function del_employee(){
        $id = intval($_POST['id']);
        // 查询单条信息-确认是否存在该信息-并判断是否已经关闭
        $database_employee = M('Employee_list');
        $info = $database_employee->where(array('id'=>$id))->find();
        if (empty($info) || $info['examine_status']==4) {
            $this->error('该从业人员不存在或者已经删除');
        }
        // 确认存在未关闭的正常该信息 做关闭处理
        $close = $database_employee->where(array('id'=>$id))->data(array('examine_status' => 4))->save();
        if($close){
            $this->success('操作成功！');
        }else{
            $this->error('操作失败！请重试~');
        }
    }

    /**
     * 关闭
     */
    public function close(){
        $id = intval($_POST['id']);
        // 查询单条信息-确认是否存在该信息-并判断是否已经关闭
        $database_employee = M('Employee_list');
        $info = $database_employee->where(array('id'=>$id))->find();

        if (empty($info)) {
            $this->error('该从业人员不存在或者已经删除');
        }
        if ($info['is_open'] == 0) {
            $this->error('该从业人员已经关闭');
        }
        // 确认存在未关闭的正常该信息 做关闭处理
        $close = $database_employee->where(array('id'=>$id))->data(array('is_open' => 0))->save();
        if($close){
            $this->success('操作成功！');
        }else{
            $this->error('操作失败！请重试~');
        }
    }

    /**
     * 开启
     */
    public function open(){
        $id = intval($_POST['id']);
        // 查询单条信息-确认是否存在该信息-并判断是否已经关闭
        $database_employee = M('Employee_list');
        $info = $database_employee->where(array('id'=>$id))->find();
        
        if (empty($info)) {
            $this->error('该从业人员不存在或者已经删除');
        }
        if ($info['is_open'] == 1) {
            $this->error('该从业人员已经开启');
        }
        // 确认存在未关闭的正常该信息 做开启处理
        $open = $database_employee->where(array('id'=>$id))->data(array('is_open' => 1))->save();
        if($open){
            $this->success('操作成功！');
        }else{
            $this->error('操作失败！请重试~');
        }
    }

    /*     * **删除此项*** */
    public function delItem() {
        $id = intval($_POST['id']);
        if ($id > 0) {
            $tmp = M('Employee_list')->where(array('id' => $id))->delete();
            if ($tmp) {
                $this->success('操作成功！');
            } else {
                $this->error('操作失败！请重试~');
            }
        }
    }

    public function edit(){
        $database_employee = M('Employee_list');
        if(IS_POST){
            if($_POST['dosubmit']){
                if($_POST['id']>0){
                    $data['true_name'] = $_POST['true_name'];
                    $data['nickname'] = $_POST['nickname'];
                    $data['business_phone'] = $_POST['business_phone'];
                    $data['phone'] = $_POST['phone'];
                    $data['is_open'] = $_POST['is_open'];
                    $data['is_open_adver'] = $_POST['is_open_adver'];
                    $data['adver_color'] = $_POST['adver_color'];
                    $data['adver_info'] = $_POST['adver_info'];
                    $data['adver_url'] = $_POST['adver_url'];
                    $data['sort'] = $_POST['sort'];
                    $res = $database_employee->where(array('id'=>$_POST['id']))->data($data)->save();
                    if($res){
                        $this->frame_submit_tips(1,'编辑成功！');
                    }else{
                        $this->frame_error_tips('编辑失败！请重试~');
                    }
                }
            }else{
                $this->frame_error_tips('非法提交');
            }
        }else{
            $id = I('get.id');
            $employee_list = $database_employee->alias('a')
                ->field('a.*,b.nickname,b.phone,b.now_money')
                ->join('join pigcms_user as b on a.uid=b.uid')
                ->where(array('a.id'=>$id))->find();
            if ($employee_list) {
                $employee_list['write_field'] = unserialize($employee_list['write_field']);
            }
            // 查询下分类信息
            if ($employee_list) {
                $m_employee_category = M('Employee_category');
                $employee_list['cat_pfcid_name'] = $m_employee_category->where(array('cat_id' => $employee_list['cat_pfcid']))->getField('cat_name');
                $employee_list['cat_fid_name'] = $m_employee_category->where(array('cat_id' => $employee_list['cat_fid']))->getField('cat_name');
                $employee_list['cat_id_name'] = $m_employee_category->where(array('cat_id' => $employee_list['cat_id']))->getField('cat_name');
            }
            
            $this->assign('employee_list',$employee_list);
            $this->display();
        }
    }

    /**
     * 自定义填写选项添加字段 前端显示隐藏
     */
    public function write_field_show(){
        if(IS_POST){
            $database_employee_write_field = M('Employee_category_write_field');
            $condition_now_employee_write_field['cat_id'] = intval($_POST['cat_id']);
            $condition_now_employee_write_field['name'] = trim($_POST['name']);
            $write_field = $database_employee_write_field->field(true)->where($condition_now_employee_write_field)->find();
            $new_write_field = array();
            if(!empty($write_field)){
                if ($write_field['no_hide'] == 1 && $write_field['is_show'] != 1) {
                    $this->error('此填写项不允许隐藏！');
                } else {
                    $new_write_field['is_show'] = $_POST['is_show'];
                }
            }else{
                $this->error('此填写项不存在！');
            }
            if($database_employee_write_field->where($condition_now_employee_write_field)->data($new_write_field)->save()){
                $this->success('修改成功！');
            }else{
                $this->error('修改失败！请重试~');
            }
        }else{
            $this->error('非法提交,请重新提交~');
        }
    }


     /* 导出excel函数*/  
    public function employee_push($name='Excel'){  
        $database_employee = M('Employee_list');
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        error_reporting(E_ALL);  
        date_default_timezone_set('Europe/London');  
        $objExcel = new PHPExcel();  
  
        $title ='从业人员列表';
        /*以下是一些设置 ，什么作者  标题之类的*/  
         $objExcel->getProperties()->setCreator($title)  
           ->setLastModifiedBy($title)  
           ->setTitle($title)  
           ->setSubject("数据EXCEL导出")  
           ->setDescription("备份数据")  
           ->setKeywords("excel")  
           ->setCategory("result file");  
        
         /*以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改*/ 

        $field = I('get.searchtype');
        $keyword = I('get.keyword');
        $where=array();
        if( $field == 'nickname'){//昵称查询
            $where['b.'.$field] = array('like','%'.I('get.keyword').'%');
        }
        if( $field == 'true_name'){//姓名查询
            $where['a.'.$field] = array('like','%'.I('get.keyword').'%');
        }
        if(I('get.is_open') != ''){
            $where['a.is_open'] = I('get.is_open');
        }
        if($_GET['province_idss']){
            $where['a.province_id'] = $_GET['province_idss'];
        }
        if($_GET['city_idss']){
            $where['a.city_id'] = $_GET['city_idss'];
        }
        $where['a.examine_status'] = 1;//已审核
        $count = $database_employee->alias('a')->where($where)->join('join pigcms_user as b on a.uid=b.uid')->count();
        $data = $database_employee
        ->alias('a')
        ->field('a.*,b.nickname,b.phone,b.now_money,b.openid')
        ->where($where)
        ->join('join pigcms_user as b on a.uid=b.uid')
        ->select();
        
        
        $length = ceil($count / 1000);
        
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objExcel->getActiveSheet()->setTitle($title);
            $objActSheet = $objExcel->getActiveSheet();
            $objActSheet->setCellValue('A1', '编号');
            $objActSheet->setCellValue('B1', '昵称');
            $objActSheet->setCellValue('C1', '从业者姓名');
            $objActSheet->setCellValue('D1', '业务联系电话');
            $objActSheet->setCellValue('E1', '手机号');
            $objActSheet->setCellValue('F1', '主页点击数');
            $objActSheet->setCellValue('G1', '粉丝数');
            $objActSheet->setCellValue('H1', '收藏数');
            $objActSheet->setCellValue('I1', '点赞数');
            $objActSheet->setCellValue('J1', '账户余额');
            $objActSheet->setCellValue('K1', '是否关联店铺');
            $objActSheet->setCellValue('L1', '状态');

//            $data = D('Employee_list')->alias('a')->field('a.*,b.nickname,b.phone,b.now_money,b.openid')->where($where)->join('join pigcms_user as b on a.uid=b.uid')->find();
            if (!empty($data)) {
                $index = 2;
                for ($k = $i*1000; $k < ($i+1)*1000; $k++) { 
                    if (!$data[$k]['id']) {
                       break;
                    }
                    if ($data[$k]['openid']) {
                        $data[$k]['fans_num'] = M('User_spread')->where(array('spread_openid'=>$data[$k]['openid'],'openid'=>array('neq','')))->count();
                    } else {
                        $data[$k]['fans_num'] = 0;
                    }

                    $objActSheet->setCellValueExplicit('A' . $index, $data[$k]['id']);
                    $objActSheet->setCellValueExplicit('B' . $index, $data[$k]['nickname']);
                    $objActSheet->setCellValueExplicit('C' . $index, $data[$k]['true_name']);
                    $objActSheet->setCellValueExplicit('D' . $index, $data[$k]['business_phone']);
                    $objActSheet->setCellValueExplicit('E' . $index, $data[$k]['phone']);
                    $objActSheet->setCellValueExplicit('F' . $index, $data[$k]['click_num']);
                    $objActSheet->setCellValueExplicit('G' . $index, $data[$k]['fans_num']);
                    $objActSheet->setCellValueExplicit('H' . $index, $data[$k]['collect_num']);
                    $objActSheet->setCellValueExplicit('I' . $index, $data[$k]['thumb_num']);
                    $objActSheet->setCellValueExplicit('J' . $index, $data[$k]['now_money']);
                    if($data[$k]['is_relate_store'] == 1){
                        $objActSheet->setCellValueExplicit('K' . $index, '关联');
                    }else{
                        $objActSheet->setCellValueExplicit('K' . $index, '不关联');
                    }
                    if($data[$k]['is_open'] == 1){
                        $objActSheet->setCellValueExplicit('L' . $index, '开启');
                    }else{
                        $objActSheet->setCellValueExplicit('L' . $index, '关闭');
                    }
                    $index++;
                }
            }
        }
        //输出
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:s", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();                      
    } 

    //待审核列表
    public function employee_examine(){
        $database_employee = D('Employee_list');
        $where=array();
        $field = I('get.searchtype');
        $keyword = I('get.keyword');
        if( $field == 'nickname'){//昵称查询
            $where['b.'.$field] = array('like','%'.I('get.keyword').'%');
        }
        if( $field == 'true_name'){//姓名查询
            $where['a.'.$field] = array('like','%'.I('get.keyword').'%');
        }
        if(I('get.is_open') != ''){//状态查询
            $where['a.is_open'] = I('get.is_open');
        }
        if($_GET['province_idss']){
            $where['a.province_id'] = $_GET['province_idss'];
        }
        if($_GET['city_idss']){
            $where['a.city_id'] = $_GET['city_idss'];
        }
        $where['a.examine_status'] = 0;//未审核
        $count_employee_list= $database_employee->alias('a')->where($where)->join('join pigcms_user as b on a.uid=b.uid')->count();

        import('@.ORG.system_page');
        $p = new Page($count_employee_list,50);
        $employee_list = $database_employee
                    ->alias('a')
                    ->field('a.*,b.nickname,b.phone,b.now_money')
                    ->where($where)
                    ->join('join pigcms_user as b on a.uid=b.uid')
                    ->limit($p->firstRow.','.$p->listRows)
                    ->select();
        $pagebar = $p->show();
        $this->assign('pagebar',$pagebar);
        $this->assign('employee_list',$employee_list);
        $this->display();
    }


    //审核拒绝列表
    public function employee_examine_refused(){
        $database_employee = D('Employee_list');
        $where=array();
        $field = I('get.searchtype');
        $keyword = I('get.keyword');
        if( $field == 'nickname'){//昵称查询
            $where['b.'.$field] = array('like','%'.I('get.keyword').'%');
        }
        if( $field == 'true_name'){//姓名查询
            $where['a.'.$field] = array('like','%'.I('get.keyword').'%');
        }
        if(I('get.is_open') != ''){//状态查询
            $where['a.is_open'] = I('get.is_open');
        }
        if($_GET['province_idss']){
            $where['a.province_id'] = $_GET['province_idss'];
        }
        if($_GET['city_idss']){
            $where['a.city_id'] = $_GET['city_idss'];
        }
        $where['a.examine_status'] = 2;//审核拒绝
        $count_employee_list= $database_employee->alias('a')->where($where)->join('join pigcms_user as b on a.uid=b.uid')->count();

        import('@.ORG.system_page');
        $p = new Page($count_employee_list,50);
        $employee_list = $database_employee
            ->alias('a')
            ->field('a.*,b.nickname,b.phone,b.now_money')
            ->where($where)
            ->join('join pigcms_user as b on a.uid=b.uid')
            ->limit($p->firstRow.','.$p->listRows)
            ->select();
        $pagebar = $p->show();
        $this->assign('pagebar',$pagebar);
        $this->assign('employee_list',$employee_list);
        $this->display();
    }


    /**审核*/
    public function check_info(){
        $database_employee = M('Employee_list');
        if(IS_POST){
            if($_POST['id']>0){
                $examine_status = I('examine_status', 1, 'intval');
                $refuse_info = I('refuse_info', '', 'htmlspecialchars');
                if ($examine_status < 1) {
                    $this->frame_error_tips('审核失败！请重试~');
                }
                if ($examine_status == 2 && !$refuse_info) {
                    $this->frame_error_tips('审核拒绝请填写拒绝理由！');
                }
                $data['examine_status'] = $examine_status;
                $data['refuse_info'] = $refuse_info;
                $data['last_time'] = time(); // 最后更新时间
                $res = $database_employee->where(array('id'=>$_POST['id']))->data($data)->save();
                if($res){
                    $this->frame_submit_tips(1,'审核成功！');
                }else{
                    $this->frame_error_tips('审核失败！请重试~');
                }
            }
        }else{
            $id = I('get.id');
            $employee_list = $database_employee->alias('a')
                ->field('a.*,b.nickname,b.phone,b.now_money')
                ->join('join pigcms_user as b on a.uid=b.uid')
                ->where(array('a.id'=>$id))->find();
            if ($employee_list) {
                $employee_list['write_field'] = unserialize($employee_list['write_field']);
            }

            $this->assign('employee_list',$employee_list);
            $this->display();
        }
    }


    /**店铺父分类*/
    public function ajax_cat_fid(){
        $database_area = D('Merchant_category');
        $condition_area['cat_fid'] = 0;
        $condition_area['cat_status'] = 1;
        $province_list = D(_view('merchant_category'))->field(array('cat_id','cat_fid','cat_name'))->where($condition_area)->order('`cat_sort` DESC,`cat_id` ASC')->select();
        if(!empty($province_list)){
            $return['error'] = 0;
            $return['list'] = $province_list;
        }else{
            $return['error'] = 1;
            $return['info'] = '没有已开启的分类！';
        }
        exit(json_encode($return));
    }
    /**店铺分类*/
    public function ajax_cat_id(){
        $database_area = D('Merchant_category');
        $condition_area['cat_fid'] = intval($_POST['cat_id']);
        $condition_area['cat_status'] = 1;
        $city_list = D(_view('merchant_category'))->field(array('cat_id','cat_name'))->where($condition_area)->order('`cat_sort` DESC,`cat_id` ASC')->select();
        if(!empty($city_list)){
            $return['error'] = 0;
            $return['list'] = $city_list;
        }else{
            $return['error'] = 1;
            $return['info'] = $_POST['cat_name'] .' 分类下没有已开启的子分类！';
        }
        exit(json_encode($return));
    }


    /**店铺-添加和认领功能*/

    /* 店铺管理 */
    public function store()
    {
        $database_merchant_store = D('Merchant_store');
        $condition_merchant_store['is_claim'] = 1;
        $condition_merchant_store['status'] = array('in', array(0,1,6));
        $condition_merchant_store['claim_status'] = array('in', array(0,1));
        $count_store = $database_merchant_store->where($condition_merchant_store)->count();
        import('@.ORG.system_page');
        $p = new Page($count_store,20);
        $store_list = $database_merchant_store->field(true)->where($condition_merchant_store)->order('`sort` DESC,`store_id` ASC')->limit($p->firstRow.','.$p->listRows)->select();
        $this->assign('store_list',$store_list);
        $pagebar = $p->show();
        $this->assign('pagebar',$pagebar);

        $this->display();
    }



    /** 添加店铺页面 */
    public function store_add(){
        $this->assign('system_session',$this->system_session);
        if($this->system_session['area_type']==3){

            $now_area = D('Area')->field('area_name,`area_pid`')->where(array('area_id'=>$this->system_session['area_id']))->find();
            $merchant['city_id'] = $now_area['area_pid'];
            $merchant['area_name'] = $now_area['area_name'];
            $now_city = D('Area')->field('area_name,`area_pid`')->where(array('area_id'=>$merchant['city_id']))->find();
            $merchant['province_id'] = $now_city['area_pid'];
            $merchant['city_name'] = $now_city['area_name'];
            $now_province = D('Area')->field('area_name,`area_pid`')->where(array('area_id'=>$merchant['province_id']))->find();
            $merchant['province_name'] = $now_province['area_name'];
            $this->assign('merchant',$merchant);
        }

        $this->assign('bg_color','#F3F3F3');
        $this->display();
    }
    /**添加店铺*/
    public function store_modify(){
        if(IS_POST){
            if(empty($_POST['name'])){
                $this->error('店铺名称必填！');
            }
            if(empty($_POST['phone'])){
                $this->error('联系电话必填！');
            }
            if(empty($_POST['long_lat'])){
                $this->error('店铺经纬度必填！');
            }
            if(empty($_POST['adress'])){
                $this->error('店铺地址必填！');
            }
            if(empty($_POST['txt_info'])){
                $this->returnCode(1, null, '请输入店铺描述信息');
            }
            $long_lat = explode(',',$_POST['long_lat']);
            $_POST['long'] = $long_lat[0];
            $_POST['lat'] = $long_lat[1];
            $_POST['last_time'] = $_SERVER['REQUEST_TIME'];
            $_POST['add_from'] = 2;
            $_POST['add_uid'] = intval($this->system_session['id']);
            $_POST['is_claim'] = 1;
            $_POST['claim_from'] = 2;
            $_POST['status'] = 6;
            $_POST['claim_status'] = 1;

            $database_merchant_store = D('Merchant_store');
            if($insert_id=$database_merchant_store->data($_POST)->add()){
                $this->success('添加成功！');
            }else{
                $this->error('添加失败！请重试~');
            }
        }else{
            $this->error('非法提交,请重新提交~');
        }
    }

    /**编辑页面*/
    public function store_edit(){
        $database_merchant_store = D('Merchant_store');
        $condition_employee_claim_store['store_id'] = intval($_GET['store_id']);
        $condition_employee_claim_store['is_claim'] = 1;
        $store = $database_merchant_store->field(true)->where($condition_employee_claim_store)->find();
        if(empty($store)){
            $this->frame_error_tips('数据库中没有查询到该店铺的信息！',5);
        }
        $this->assign('system_session',$this->system_session);
        if($this->system_session['area_type']==3){

            $now_area = D('Area')->field('area_name,`area_pid`')->where(array('area_id'=>$this->system_session['area_id']))->find();
            $merchant['city_id'] = $now_area['area_pid'];
            $merchant['area_name'] = $now_area['area_name'];
            $now_city = D('Area')->field('area_name,`area_pid`')->where(array('area_id'=>$merchant['city_id']))->find();
            $merchant['province_id'] = $now_city['area_pid'];
            $merchant['city_name'] = $now_city['area_name'];
            $now_province = D('Area')->field('area_name,`area_pid`')->where(array('area_id'=>$merchant['province_id']))->find();
            $merchant['province_name'] = $now_province['area_name'];
            $this->assign('merchant',$merchant);
        }
        $this->assign('store',$store);

        $this->assign('bg_color','#F3F3F3');

        $this->display();
    }

    /**编辑信息*/
    public function store_amend(){
        if(IS_POST){
            if(empty($_POST['name'])){
                $this->error('店铺名称必填！');
            }
            if(empty($_POST['phone'])){
                $this->error('联系电话必填！');
            }
            if(empty($_POST['long_lat'])){
                $this->error('店铺经纬度必填！');
            }
            if(empty($_POST['adress'])){
                $this->error('店铺地址必填！');
            }
            $long_lat = explode(',',$_POST['long_lat']);
            $_POST['long'] = $long_lat[0];
            $_POST['lat'] = $long_lat[1];
            $_POST['last_time'] = $_SERVER['REQUEST_TIME'];
            $_POST['is_claim'] = 1;
            $_POST['claim_from'] = 2;
            $_POST['status'] = 6;
            $_POST['claim_status'] = 1;
            $database_merchant_store = D('Merchant_store');
            if($database_merchant_store->data($_POST)->save()){
                $this->success('修改成功！');
            }else{
                $this->error('修改失败！请检查内容是否有过修改（必须修改）后重试~');
            }
        }else{
            $this->error('非法提交,请重新提交~');
        }
    }
    /**编辑信息*/
    public function store_del(){
        if(IS_POST){
//            $database_employee_claim_store = D('Group_store');
//            $condition_group_store['store_id'] = intval($_POST['store_id']);
//            if($database_employee_claim_store->where($condition_group_store)->find()){
//                $this->error('该店铺下有'.$this->config['employee_alias'].'，请先解除店铺与对应'.$this->config['employee_alias'].'的关系才能删除！');
//            }

            $database_merchant_store = D('Merchant_store');
            $condition_employee_claim_store['is_claim'] = 1;
            $condition_employee_claim_store['store_id'] = intval($_POST['store_id']);
            $store = $database_merchant_store->field(true)->where($condition_employee_claim_store)->find();
            if(empty($store) || $store['claim_status'] == 4){
                $this->frame_error_tips('数据库中没有查询到该店铺的信息或者已经删除！',5);
            }
            if($database_merchant_store->where($condition_employee_claim_store)->save(array('claim_status'=>4))){
                $this->success('删除成功！');
            }else{
                $this->error('删除失败！请重试~');
            }
        }else{
            $this->error('非法提交,请重新提交~');
        }
    }



    /* 店铺审核管理 */
    public function check_store()
    {
        $database_merchant_store = D('Merchant_store');
        $condition_merchant_store['claim_status'] = 2;
        $condition_merchant_store['is_claim'] = 1;
        $count_store = $database_merchant_store->where($condition_merchant_store)->count();
        import('@.ORG.system_page');
        $p = new Page($count_store,20);
        $store_list = $database_merchant_store->field(true)->where($condition_merchant_store)->order('`sort` DESC,`store_id` ASC')->limit($p->firstRow.','.$p->listRows)->select();
        $this->assign('store_list',$store_list);
        $pagebar = $p->show();
        $this->assign('pagebar',$pagebar);

        $this->display();
    }

    /**审核编辑页面*/
    public function check_store_edit(){
        $database_merchant_store = D('Merchant_store');
        $condition_employee_claim_store['is_claim'] = 1;
        $condition_employee_claim_store['store_id'] = intval($_GET['store_id']);
        $store = $database_merchant_store->field(true)->where($condition_employee_claim_store)->find();
        if(empty($store)){
            $this->frame_error_tips('数据库中没有查询到该店铺的信息！',5);
        }
        $this->assign('system_session',$this->system_session);
        if($this->system_session['area_type']==3){

            $now_area = D('Area')->field('area_name,`area_pid`')->where(array('area_id'=>$this->system_session['area_id']))->find();
            $merchant['city_id'] = $now_area['area_pid'];
            $merchant['area_name'] = $now_area['area_name'];
            $now_city = D('Area')->field('area_name,`area_pid`')->where(array('area_id'=>$merchant['city_id']))->find();
            $merchant['province_id'] = $now_city['area_pid'];
            $merchant['city_name'] = $now_city['area_name'];
            $now_province = D('Area')->field('area_name,`area_pid`')->where(array('area_id'=>$merchant['province_id']))->find();
            $merchant['province_name'] = $now_province['area_name'];
            $this->assign('merchant',$merchant);
        }
        $this->assign('store',$store);

        $this->assign('bg_color','#F3F3F3');

        $this->display();
    }
    /**审核编辑信息*/
    public function check_store_amend(){
        if(IS_POST){
            if(empty($_POST['status'])){
                $this->error('审核状态必选！');
            }
            $save_data['claim_status'] = $_POST['status'];
            if ($save_data['claim_status'] == 3) {
                if(empty($_POST['check_remark'])){
                    $this->error('审核拒绝请填写审核备注！');
                }
            }
            $database_merchant_store = D('Merchant_store');
            $save_data['is_claim'] = 1;
            $save_data['store_id'] = $_POST['store_id'];
            $store_info = $database_merchant_store->where(array('store_id' => $_POST['store_id'], 'is_claim' => 1))->find();
            if (!$store_info) {
                $this->error('审核的店铺信息不存在！');
            }

            $save_data['check_remark'] = $_POST['check_remark'];
            $save_data['last_time'] = $_SERVER['REQUEST_TIME'];
            if($database_merchant_store->data($save_data)->save()){
                // 审核成功，如果是审核通过处理积分问题
                if ($store_info['claim_from']=1 && $save_data['status'] == 1 && $this->config['employee_recommend_store_get_score'] == 1 && $this->config['employee_recommend_score'] > 0) {
                    D('User')->add_score($store_info['add_uid'], intval($this->config['employee_recommend_score']), '审核通过，用户添加推荐店铺信息，获得积分');
                    $database_merchant_store->where(array('store_id' => $_POST['store_id']))->data(array('score_give' => intval($this->config['employee_recommend_score'])))->save();
                }

                $this->success('审核成功！');
            }else{
                $this->error('审核失败！请检查内容是否有过修改后重试~');
            }
        }else{
            $this->error('非法提交,请重新提交~');
        }
    }


    /* 店铺审核拒绝管理 */
    public function check_refuse_store()
    {
        $database_merchant_store = D('Merchant_store');
        $condition_employee_claim_store['claim_status'] = 3;
        $condition_employee_claim_store['is_claim'] = 1;
        $count_store = $database_merchant_store->where($condition_employee_claim_store)->count();
        import('@.ORG.system_page');
        $p = new Page($count_store,20);
        $store_list = $database_merchant_store->field(true)->where($condition_employee_claim_store)->order('`sort` DESC,`store_id` ASC')->limit($p->firstRow.','.$p->listRows)->select();
        $this->assign('store_list',$store_list);
        $pagebar = $p->show();
        $this->assign('pagebar',$pagebar);

        $this->display();
    }

    /**店铺详情页面*/
    public function store_detail(){
        $database_merchant_store = D('Merchant_store');
        $condition_employee_claim_store['store_id'] = intval($_GET['store_id']);
        $condition_employee_claim_store['is_claim'] = 1;
        $store = $database_merchant_store->field(true)->where($condition_employee_claim_store)->find();
        if(empty($store)){
            $this->frame_error_tips('数据库中没有查询到该店铺的信息！',5);
        }
        $this->assign('system_session',$this->system_session);
        if($this->system_session['area_type']==3){

            $now_area = D('Area')->field('area_name,`area_pid`')->where(array('area_id'=>$this->system_session['area_id']))->find();
            $merchant['city_id'] = $now_area['area_pid'];
            $merchant['area_name'] = $now_area['area_name'];
            $now_city = D('Area')->field('area_name,`area_pid`')->where(array('area_id'=>$merchant['city_id']))->find();
            $merchant['province_id'] = $now_city['area_pid'];
            $merchant['city_name'] = $now_city['area_name'];
            $now_province = D('Area')->field('area_name,`area_pid`')->where(array('area_id'=>$merchant['province_id']))->find();
            $merchant['province_name'] = $now_province['area_name'];
            $this->assign('merchant',$merchant);
        }
        $this->assign('store',$store);

        $this->assign('bg_color','#F3F3F3');

        $this->display();
    }


    /** *导入店铺页**** */

    public function store_import() {
        $this->display();
    }

    /** *excel导入店铺信息**** */

    public function excel_import() {
        if ($_FILES['file']['error'] != 4) {

            import('ORG.Util.Dir');
            Dir::delDirnotself('./upload/excel/employee');

            $getupload_dir = "/upload/excel/employee/" . date('Ymd') . '/';
            $upload_dir = "." . $getupload_dir;
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            import('ORG.Net.UploadFile');
            $upload = new UploadFile();
            $upload->maxSize = 10 * 1024 * 1024;
            $upload->allowExts = array('xls', 'xlsx');
            $upload->allowTypes = array(); // 允许上传的文件类型 留空不做检查
            $upload->savePath = $upload_dir;
            $upload->thumb = false;
            $upload->thumbType = 0;
            $upload->imageClassPath = '';
            $upload->thumbPrefix = '';
            $upload->saveRule = 'uniqid';
            if ($upload->upload()) {
                $uploadList = $upload->getUploadFileInfo();
                require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel/IOFactory.php';
                $path = $uploadList['0']['savepath'] . $uploadList['0']['savename'];
                //$reader = PHPExcel_IOFactory::createReader('Excel5');
                $fileType = PHPExcel_IOFactory::identify($path); //文件名自动判断文件类型
                $objReader = PHPExcel_IOFactory::createReader($fileType);
                $excelObj = $objReader->load($path);
                $result = $excelObj->getActiveSheet()->toArray(null, true, true, true);
                unlink($path);	//删除文件
                if (!empty($result) && is_array($result)) {
                    unset($result[1]);
                    // 记录 导入异常的数据行数
                    $import_error = array();
                    // 记录 导入正常常的数据行数
                    $import_success = array();
                    $database_category = D('Merchant_category');
                    $database_area = D('Area');
                    $claim_storeDb = D('Merchant_store');
                    foreach ($result as $kk => $vv) {
                        if (empty($vv['A']) || empty($vv['B']) || empty($vv['C']) || empty($vv['E']) || empty($vv['F']) || empty($vv['G']) || empty($vv['H'])) {
                            $vv['error'] = 1;
                            $vv['msg'] = '必填项数据存在未填写项';
                            $vv['row'] = $kk;
                            $import_error[] = $vv;
                            continue;
                        }
                        $tmpdata = array();
                        // 店铺名称
                        $tmpdata['name'] = htmlspecialchars(trim($vv['A']), ENT_QUOTES);
                        // 一级分类
                        $cat_name1 = htmlspecialchars(trim($vv['B']), ENT_QUOTES);
                        $condition_category['cat_fid'] = 0;
                        $condition_category['cat_status'] = 1;
                        $condition_category['cat_name'] = $cat_name1;
                        $tmpdata['cat_fid'] = D(_view('merchant_category'))->where($condition_category)->getField('cat_id');
                        if (empty($tmpdata['cat_fid'])) {
                            $vv['error'] = 1;
                            $vv['msg'] = '填写一级分类信息有误';
                            $vv['row'] = $kk;
                            $import_error[] = $vv;
                            continue;
                        }
                        // 二级分类
                        $cat_name2 = htmlspecialchars(trim($vv['C']), ENT_QUOTES);
                        $condition_category['cat_fid'] = $tmpdata['cat_fid'];
                        $condition_category['cat_name'] = $cat_name2;
                        $tmpdata['cat_id'] = D(_view('merchant_category'))->where($condition_category)->getField('cat_id');
                        if (empty($tmpdata['cat_id'])) {
                            $vv['error'] = 1;
                            $vv['msg'] = '填写二级分类信息有误';
                            $vv['row'] = $kk;
                            $import_error[] = $vv;
                            continue;
                        }
                        // 商圈信息处理
                        $condition_area['circle_category_id'] = array('neq', 0);
                        $condition_area['area_name'] = htmlspecialchars(trim($vv['D']), ENT_QUOTES);
                        $tmpdata['circle_id'] = $database_area->where($condition_area)->getField('area_id');
                        if (empty($tmpdata['circle_id'])) {
                            $vv['error'] = 1;
                            $vv['msg'] = '填写的商圈信息有误';
                            $import_error[] = $vv;
                            continue;
                        }
                        // 详细地址
                        !empty($vv['E']) && $tmpdata['adress'] = htmlspecialchars(trim($vv['E']), ENT_QUOTES);

                        // 店铺经纬度
                        $long_info = htmlspecialchars(trim($vv['F']), ENT_QUOTES);
                        $long_info = str_replace('，', ',', $long_info);
                        $long_arr = explode(',',$long_info);
                        $long = $long_arr[0];
                        $lat = $long_arr[1];
                        $area_info = D('Area')->cityMatching($lat, $long);
                        if ($area_info && $area_info['area_info']) {
                            $tmpdata['province_id'] = $area_info['area_info']['province_id'];
                            $tmpdata['city_id'] = $area_info['area_info']['city_id'];
                            $tmpdata['area_id'] = $area_info['area_info']['area_id'];
                            if ($area_info['address_name']) {
                                $tmpdata['adress_long_lat'] = $area_info['address_name'];
                            }
                        } else {
                            $vv['error'] = 1;
                            $vv['msg'] = '填写的店铺经纬度信息有误';
                            $import_error[] = $vv;
                            continue;
                        }
                        $tmpdata['lat'] = $lat;
                        $tmpdata['long'] = $long;
                        // 号码
                        $tmpdata['phone'] = htmlspecialchars(trim($vv['G']), ENT_QUOTES);
                        $tmpdata['phone'] = str_replace(' ', ' ', $tmpdata['phone']);
                        if (empty($tmpdata['phone'])) {
                            $vv['error'] = 1;
                            $vv['msg'] = '请填写电话信息';
                            $import_error[] = $vv;
                            continue;
                        }
                        // 营业时间
                        $business_time = htmlspecialchars(trim($vv['H']), ENT_QUOTES);
                        $business_time = str_replace('，', ',', $business_time);
                        $business_time_arr = explode(',',$business_time);
                        if ($business_time_arr[0]) {
                            $business_time1 = explode('-',$business_time_arr[0]);
                            $tmpdata['open_1'] = $business_time1[0];
                            $tmpdata['close_1'] = $business_time1[1];
                        } else {
                            $vv['error'] = 1;
                            $vv['msg'] = '请填写正确营业信息';
                            $import_error[] = $vv;
                            continue;
                        }
                        if ($business_time_arr[1]) {
                            $business_time2 = explode('-',$business_time_arr[1]);
                            $tmpdata['open_2'] = $business_time2[0];
                            $tmpdata['close_2'] = $business_time2[1];
                        }
                        if ($business_time_arr[2]) {
                            $business_time3 = explode('-',$business_time_arr[2]);
                            $tmpdata['open_3'] = $business_time3[0];
                            $tmpdata['close_3'] = $business_time3[1];
                        }
                        !empty($vv['I']) && $tmpdata['txt_info'] = htmlspecialchars(trim($vv['I']), ENT_QUOTES);
                        $tmpdata['add_uid'] = intval($this->system_session['id']);
                        $tmpdata['last_time'] = time();
                        $tmpdata['claim_status'] = 1;
                        $tmpdata['is_claim'] = 1;
                        $tmpdata['claim_from'] = 3;
                        $tmpdata['status'] = 6;
                        $store_id = $claim_storeDb->add($tmpdata);
                        if ($store_id) {
                            $import_success[] = $store_id;
                        }
                    }
                    if (!empty($import_success)) {
                        $this->dexit(array('error' => 0, 'import_error' => $import_error));
                    } else {
                        $this->dexit(array('error' => 1, 'msg' => '导入失败！', 'import_error' => $import_error));
                    }
                }
            } else {
                $this->dexit(array('error' => 1, 'msg' => $upload->getErrorMsg()));
            }
        }
        $this->dexit(array('error' => 1, 'msg' => '文件上传失败！'));
    }

    private function dexit($data = '') {
        if (is_array($data)) {
            echo json_encode($data);
        } else {
            echo $data;
        }
        exit();
    }




    // 推广方面信息处理

    public function diytool()
    {
        $this->display();
    }


    /**普通人员图文消息*/
    public function normal_key_edit()
    {
        if (IS_POST) {
            $data = array();
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $data['key'] = 'normal_employee_extension';
            $data['title'] = isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
            $data['content'] = isset($_POST['content']) ? $_POST['content'] : '';
            $data['url'] = $this->config['site_url'] . '/wap.php?g=Wap&c=Employee&a=employee_detail&employee_id=';
            $data['type'] = 0;
            $data['share_type'] = 'normal';
            if (empty($data['title'])) {
                $this->error('标题不能为空');
            }
            if (empty($data['title'])) {
                $this->error('标题不能为空');
            }

            $image = D('Image')->handle($this->system_session['id'], 'employee_key_platform');
            if (!$image['error']) {
                $data = array_merge($data, $image['url']);
            }

            $single = M('Employee_platform')->where(array('key' => 'normal_employee_extension','share_type' => 'normal', 'type' => 0))->find();
            if ($single) {
                D('Employee_platform')->where(array('key' => 'normal_employee_extension','share_type' => 'normal', 'type' => 0))->save($data);
                D('Image')->update_table_id($_POST['pic'], $id, 'employee_key_platform');
                $this->success('更新成功');
            } else {
                if ($id = D("Employee_platform")->add($data)) {
                    D('Image')->update_table_id($_POST['pic'], $id, 'employee_key_platform');
                    $this->success('添加成功');
                } else {
                    $this->error('添加失败');
                }
            }
        } else {
            $data = M('Employee_platform')->where(array('key' => 'normal_employee_extension','share_type' => 'normal', 'type' => 0))->find();
            $this->assign('info', $data);
            $this->display();
        }
    }

    /**发送给同伴的图文消息*/
    public function friend_key_edit()
    {
        if (IS_POST) {
            $data = array();
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $data['key'] = 'friend_employee_extension';
            $data['title'] = isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
            $data['content'] = isset($_POST['content']) ? $_POST['content'] : '';
            $data['url'] = isset($_POST['url']) ? htmlspecialchars_decode($_POST['url']) : '';
            $data['type'] = 0;
            $data['share_type'] = 'friend';
            if (empty($data['title'])) {
                $this->error('标题不能为空');
            }
            if (empty($data['title'])) {
                $this->error('标题不能为空');
            }

            $image = D('Image')->handle($this->system_session['id'], 'employee_key_platform');
            if (!$image['error']) {
                $data = array_merge($data, $image['url']);
            }

            $single = M('Employee_platform')->where(array('key' => 'friend_employee_extension','share_type' => 'friend', 'type' => 0))->find();
            if ($single) {
                D('Employee_platform')->where(array('key' => 'friend_employee_extension','share_type' => 'friend', 'type' => 0))->save($data);
                D('Image')->update_table_id($_POST['pic'], $id, 'employee_key_platform');
                $this->success('更新成功');
            } else {
                if ($id = D("Employee_platform")->add($data)) {
                    D('Image')->update_table_id($_POST['pic'], $id, 'employee_key_platform');
                    $this->success('添加成功');
                } else {
                    $this->error('添加失败');
                }
            }
        } else {
            $data = M('Employee_platform')->where(array('key' => 'friend_employee_extension','share_type' => 'friend', 'type' => 0))->find();
            $this->assign('info', $data);
            $this->display();
        }
    }


    /**单位管理人员的图文消息*/
    public function unit_manager_key()
    {
        if (IS_POST) {
            $data = array();
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $data['key'] = 'unit_manager_employee_extension';
            $data['title'] = isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
            $data['content'] = isset($_POST['content']) ? $_POST['content'] : '';
            $data['url'] = isset($_POST['url']) ? htmlspecialchars_decode($_POST['url']) : '';
            $data['type'] = 0;
            $data['share_type'] = 'unit_manager';
            if (empty($data['title'])) {
                $this->error('标题不能为空');
            }
            if (empty($data['title'])) {
                $this->error('标题不能为空');
            }

            $image = D('Image')->handle($this->system_session['id'], 'employee_key_platform');
            if (!$image['error']) {
                $data = array_merge($data, $image['url']);
            }

            $single = M('Employee_platform')->where(array('key' => 'unit_manager_employee_extension','share_type' => 'unit_manager', 'type' => 0))->find();
            if ($single) {
                D('Employee_platform')->where(array('key' => 'unit_manager_employee_extension','share_type' => 'unit_manager', 'type' => 0))->save($data);
                D('Image')->update_table_id($_POST['pic'], $id, 'employee_key_platform');
                $this->success('更新成功');
            } else {
                if ($id = D("Employee_platform")->add($data)) {
                    D('Image')->update_table_id($_POST['pic'], $id, 'employee_key_platform');
                    $this->success('添加成功');
                } else {
                    $this->error('添加失败');
                }
            }
        } else {
            $data = M('Employee_platform')->where(array('key' => 'unit_manager_employee_extension','share_type' => 'unit_manager', 'type' => 0))->find();
            $this->assign('info', $data);
            $this->display();
        }
    }


    /**推广海报*/

    function promote_index(){
        $store_promote_setting = D('Employee_promote_setting');
        $name = !empty($_POST['keyword']) ? $_POST['keyword'] : '';
        $share_type = !empty($_POST['share_type']) ? $_POST['share_type'] : '';
        $where = array();
        if($name){
            $where['name'] = array('like','%'.$name.'%');
        }
        if($share_type){
            $where['share_type'] = array('like','%'.$share_type.'%');
        }
        $where['status'] = 1;
        $where['owner'] = 2;
        $promote_list = $store_promote_setting->where($where)->order('pigcms_id DESC')->select();

        $this->assign('promote_list', $promote_list);
        $this->assign('name', $name);
        $this->assign('share_type', $share_type);
        $this->display();
    }

    /**
     * 添加海报
     */
    public function promote_add(){
        if (IS_POST) {
            $pigcms_id = !empty($_POST['pigcms_id']) ? $_POST['pigcms_id'] : '';
            $result = D('Employee_promote_setting')->where(array('pigcms_id'=>$pigcms_id))->find();
            $data['name'] = !empty($_POST['name']) ? $_POST['name'] : '';
            $data['store_nickname'] = !empty($_POST['store_name']) ? $_POST['store_name'] : '';
            $data['store_id'] = 0;
            $data['start_time'] = !empty($_POST['start_time']) ? strtotime($_POST['start_time']) : '';
            $data['end_time'] = !empty($_POST['end_time']) ? strtotime($_POST['end_time']) : '';
            $data['descr'] = !empty($_POST['descr']) ? $_POST['descr'] : '';
            $data['banner_config'] = !empty($_POST['banner_config']) ? $_POST['banner_config'] : '';
            $data['status'] = 1;
            $data['poster_type'] = !empty($_POST['poster_type']) ? $_POST['poster_type'] : '';
            $data['owner'] = 2;
            $data['title'] = !empty($_POST['title']) ? $_POST['title'] : '';
            $data['share_type'] = !empty($_POST['share_type']) ? $_POST['share_type'] : '';

            if(!D('Employee_promote_setting')->where(array('type'=>1,'status'=>1,'owner'=>2))->find()){
                $data['type'] = 1;
            }
            if($pigcms_id && $result){
                $data['update_time'] = time();
                $result = D('Employee_promote_setting')->where(array('pigcms_id'=>$pigcms_id))->save($data);

            } else {
                $data['create_time'] = time();
                $data['update_time'] = time();
                $result = D('Employee_promote_setting')->add($data);
            }
            if($result){
                exit(json_encode(array('error' => 0, 'message' => '保存成功')));
            }
        }
        $this->display();
    }


    /**
     * 编辑海报
     */
    public function promote_edit()
    {
        $id = $_GET['pigcms_id'];
        $promote = D('Employee_promote_setting')->where(array('pigcms_id'=>$id,'owner'=>2))->find();
        $this->assign('promote' ,$promote);
        $this->display('promote_add');
    }

    /**
     * 删除
     */
    public function detach_promote(){

        $pigcms_id = !empty($_POST['pigcms_id']) ? $_POST['pigcms_id'] : '';
        $result = D('Employee_promote_setting')->where(array('pigcms_id'=>$pigcms_id,'owner'=>2))->data(array('status'=>2))->save();
        if($result){
            exit(json_encode(array('error' => 0, 'message' => '删除成功')));
        } else {
            exit(json_encode(array('error' => 1001, 'message' => '删除失败')));
        }
    }

    /**
     * 关闭 开启
     */
    public function promote_enable(){
        $pigcms_id = !empty($_POST['pigcms_id']) ? $_POST['pigcms_id'] : '';
        $type = !empty($_POST['type']) ? $_POST['type'] : '';
        if($type == 1){
            $result = D('Employee_promote_setting')->where(array('pigcms_id'=>$pigcms_id,'owner'=>2))->data(array('type'=>1))->save();
            if(!$result){
                exit(json_encode(array('error' => 0, 'message' => '启用成功')));
            } else {
                exit(json_encode(array('error' => 1001, 'message' => '启用失败')));
            }
        } elseif (empty($type)){
            $result = D('Employee_promote_setting')->where(array('pigcms_id'=>$pigcms_id,'owner'=>2))->data(array('type'=>0))->save();
            if($result){
                exit(json_encode(array('error' => 0, 'message' => '关闭成功')));
            } else {
                exit(json_encode(array('error' => 1001, 'message' => '关闭失败')));
            }
        }

    }


    //上传文件
    public function uploadFile()
    {
        // import('upload', './source/class/');
        //import('Image', './source/class/');
        import('@.ORG.upload');
        import('@.ORG.Image');
        if(empty($_FILES['file']))
            $this->ajaxReturn(array('info'=>'上传文件不存在','status'=>0));
        $upload = new Upload();

        $upload->maxSize = 2*1024*1024;
        $upload->autoSub=true;
        $uploadPath='./upload/images/000/000/001/';// 设置附件上传目录
        $upload->savePath = $uploadPath;
        if(!file_exists($uploadPath))
        {
            mkdir($uploadPath,0777,true);
        }
        $upload->allowExts=array('jpg','jpeg','png','gif');
        if(!$upload->upload())
        {
            $this->ajaxReturn(array('status'=>0,'info'=>$upload->getErrorMsg()));
        }
        $info=$upload->getUploadFileInfo();
        $image = new Image();
        $imgPath=$info[0]['savepath'].$info[0]['savename'];
        $dirName=dirname($imgPath);
        $imgName=basename($imgPath);
        $sizeArr=array(array(400,256),array(320,500),array(320,320));
        $pathData=array('path'=>$this->siteUrl.ltrim($info[0]['savepath'],'.').$info[0]['savename']);
        $targetWidth=900;
        for($i=0;$i<count($sizeArr);$i++)
        {
            if(!file_exists($dirName."/{$sizeArr[$i][0]}_{$sizeArr[$i][1]}"))
                mkdir($dirName."/{$sizeArr[$i][0]}_{$sizeArr[$i][1]}",0777,true);
            $image->thumb2($imgPath,$dirName."/{$sizeArr[$i][0]}_{$sizeArr[$i][1]}/".$imgName,'',$sizeArr[$i][0],$sizeArr[$i][1]);
            $pathData["{$sizeArr[$i][0]}_{$sizeArr[$i][1]}"]=$this->siteUrl.ltrim($dirName,'.')."/{$sizeArr[$i][0]}_{$sizeArr[$i][1]}/".$imgName;
            //扩大到900
            $scale=$targetWidth/$sizeArr[$i][0];
            $scaleWidth=round($sizeArr[$i][0]*$scale);
            $scaleHeight=round($sizeArr[$i][1]*$scale);
            if(!file_exists($dirName."/{$scaleWidth}_{$scaleHeight}"))
                mkdir($dirName."/{$scaleWidth}_{$scaleHeight}",0777,true);
            $image->thumb2($imgPath,$dirName."/{$scaleWidth}_{$scaleHeight}/".$imgName,'',$scaleWidth,$scaleHeight);
        }

        //var_dump($pathData);
        $this->ajaxReturn(array('status'=>1,'info'=>'上传成功','data'=>$pathData));
    }




}