<?php
/**
 * Created by PhpStorm.
 * User: win7
 * Date: 2018/11/4
 * Time: 15:41
 * 从业者模块
 */

class EmployeeAction extends BaseAction {
    public $now_user = array();

    public function __construct(){
        parent::__construct();
        if(empty($this->user_session)){
            if($this->is_app_browser){
                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                $this->error_tips('请先进行登录！',U('Login/index',$location_param));
            }else{
                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                redirect(U('Login/index',$location_param));
            }
        }

        $now_user = D('User')->get_user($this->user_session['uid']);
        if(empty($now_user)){
            session('user',null);
            $this->error_tips('未获取到您的帐号信息，请重新登录！',U('Login/index'));
        }
        if(!$now_user['status']){
            session('user',null);
            $this->error_tips('您的账号被禁止登录',U('Login/index'));
        }
        $this->now_user = $now_user;
        $this->assign('now_user',$now_user);

    }



    /**
     * 从业人员入驻
    */
    public function index() {

        $uid = $this->now_user['uid'];
        $m_employee_list = M('Employee_list');
        $data['uid'] = $uid;
        $info = $m_employee_list->where(array('uid' => $uid))->find();
        $employee_register_agreement = $this->config['employee_register_agreement'];

        if ($employee_register_agreement) {
            $src = '<img src="'.file_domain().'/';
            $employee_register_agreement = str_replace('<img src="/', $src, $employee_register_agreement);
            $employee_register_agreement = str_replace('<img alt="" src="/', $src, $employee_register_agreement);
            $employee_register_agreement = str_replace('img src=&quot;/', 'img src=&quot;'.file_domain().'/', $employee_register_agreement);
        }

        $this->assign('employee_register_agreement',$employee_register_agreement);
        if ($info && $info['examine_status'] != 4) {
            // 如果审核通过，跳转个人详情页面
            if ($info['examine_status'] == 1) {
                redirect(U('Employee/my_detail'));
            }

            // 如果处于审核拒绝状态或者审核通过，查询分类 自定义填写项
            $write_field_standard = array();
            if ($info['examine_status'] == 2 || $info['examine_status'] == 1) {
                $database_employee_category = D('Employee_category');
                $database_employee_write_field = M('Employee_category_write_field');
//                $condition_now_employee_category['status'] = 0;
                $condition_now_employee_category['cat_id'] = $info['cat_pfcid'];
                $now_category = $database_employee_category->field(true)->where($condition_now_employee_category)->order('add_time asc')->find();
                if(empty($now_category)){
                    $this->error_tips('没有找到该分类信息！');
                }
                $write_field = $database_employee_write_field->field(true)->where($condition_now_employee_category)->select();
                if (!$write_field) {
                    $this->error_tips('请联系后台管理员添加填写项！');
                }
                foreach ($write_field as $vals) {
                    $write_field_standard[$vals['name']] = $vals;
                }
            }
            $write_field = unserialize($info['write_field']);
            if ($write_field_standard && $write_field) {
                foreach ($write_field as &$val) {
                    if ($val && $val['type'] == 3 && $write_field_standard[$val['name']]) {
                        $val['use_field'] = unserialize($write_field_standard[$val['name']]['use_field']);
                    }
                    if ($val && $write_field_standard[$val['name']]) {
                        $val['iswrite']  = unserialize($write_field_standard[$val['name']]['iswrite']);
                    }
                }
            }
            $this->assign('info',$info);
            $this->assign('write_field',$write_field);
            $upload_key = array();
            foreach ($write_field as $key=>$value) {
                if ($value['type'] == 10) {
                    $msg = intval($key) + 1;
                    $upload_key[] = $key;
                    $upload_key[] = $msg;
                }
            }
            $upload_str = implode(',', $upload_key);
            $this->assign('upload_str',$upload_str);
            $this->assign('cat_id',$info['cat_pfcid']);
            $this->assign('cat_fid',$info['cat_fid']);
            $this->assign('cat_pfcid',$info['cat_id']);
            // 处理定位问题
            $long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);
            if(empty($long_lat) || empty($long_lat['long']) || empty($long_lat['lat'])){
                $long_lat = array(
                    'long' => 0,
                    'lat' => 0
                );
            }
            $this->assign('long_lat',$long_lat);
            $now_city = D('Area')->get_area_by_areaId($this->config['now_city']);
            $this->assign('city_name', $now_city['area_name']);
            $this->display('employee_info');
        } else {
            // 按照分类获取从业人员 需要填写的信息
            $cat_id = intval($_GET['cat_id']);  // 分类id
            $cat_fid = intval($_GET['cat_fid']);  // 分类父id
            $cat_pfcid = intval($_GET['cat_pfcid']);  // 分类父父id

            $database_employee_category = D('Employee_category');
            $database_employee_write_field = M('Employee_category_write_field');
            $condition_employee_category = array();
            $condition_now_employee_category = array();
            $write_field = array();
            $cat_write_field_id = 0;
            if ($cat_pfcid > 0) {
                // 查询对应分类信息条件
                $condition_employee_category['cat_id'] = $cat_pfcid;
                $condition_employee_category['cat_fid'] = 0;
                $condition_employee_category['cat_pfcid'] = 0;
                // 查询填写项信息条件
                $condition_now_employee_category['status'] = 0;
                $condition_now_employee_category['cat_id'] = $cat_pfcid;

                $cat_write_field_id = $cat_pfcid;
            } elseif ($cat_fid > 0) {
                // 查询对应分类信息条件
                $condition_employee_category['cat_id'] = $cat_fid;
                $condition_employee_category['cat_fid'] = 0;
                $condition_employee_category['cat_pfcid'] = 0;
                // 查询填写项信息条件
                $condition_now_employee_category['status'] = 0;
                $condition_now_employee_category['cat_id'] = $cat_fid;

                $cat_write_field_id = $cat_fid;
            } elseif ($cat_id > 0) {
                // 查询对应分类信息条件
                $condition_employee_category['cat_id'] = $cat_id;
                $condition_employee_category['cat_fid'] = 0;
                $condition_employee_category['cat_pfcid'] = 0;
                // 查询填写项信息条件
                $condition_now_employee_category['status'] = 0;
                $condition_now_employee_category['cat_id'] = $cat_id;

                $cat_write_field_id = $cat_id;
            }
            if ($condition_employee_category) {
                $now_category = $database_employee_category->field(true)->where($condition_employee_category)->find();
                if(empty($now_category)){
                    $this->error_tips('没有找到该分类信息！');
                }
            }
            if ($condition_now_employee_category) {
                $write_field = $database_employee_write_field->field(true)->where($condition_now_employee_category)->select();
                if (empty($write_field)) {
                    $this->write_field_fixed($cat_write_field_id);
                } else {
                    foreach ($write_field as &$val) {
                        if ($val['type'] == 3 && $val['use_field']) {
                            $val['use_field'] = unserialize($val['use_field']);
                        }
                    }
                }
            }

            $this->assign('info',array());
            $this->assign('write_field',$write_field);
            $this->assign('cat_id',$cat_id);
            $this->assign('cat_fid',$cat_fid);
            $this->assign('cat_pfcid',$cat_pfcid);
            // 处理定位问题
            $long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);
            if(empty($long_lat) || empty($long_lat['long']) || empty($long_lat['lat'])){
                $long_lat = array(
                    'long' => 0,
                    'lat' => 0
                );
            }
            $this->assign('long_lat',$long_lat);
            $now_city = D('Area')->get_area_by_areaId($this->config['now_city']);
            $this->assign('city_name', $now_city['area_name']);
            $this->display();
        }
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
     * 从业人员分类信息获取
     */
    public function employee_category(){
        $where['cat_status'] = 1;
        $where['cat_fid'] = $cat_fid = intval($_POST['cat_fid']) ? intval($_POST['cat_fid']) : 0;
        if (intval($_POST['subdir'])) {
            $where['subdir'] = intval($_POST['subdir']);
        }
        $where['cat_pfcid'] = $cat_pfcid = intval($_POST['cat_pfcid']) ? intval($_POST['cat_pfcid']) : 0;

        $info['employee_category'] = M('Employee_category')->field(true)->where($where)->select();
        if (empty($info['employee_category'])) {
            $this->error_tips($_POST['cat_name'] . '下'. '没有子分类信息，请联系管理员添加!');
        }

        $database_employee_category = D('Employee_category');
        $database_employee_write_field = M('Employee_category_write_field');
        $condition_employee_category = array();
        $condition_now_employee_category = array();
        if ($cat_pfcid > 0) {
            // 查询对应分类信息条件
            $condition_employee_category['cat_id'] = $cat_pfcid;
            $condition_employee_category['cat_fid'] = 0;
            $condition_employee_category['cat_pfcid'] = 0;
            // 查询填写项信息条件
            $condition_now_employee_category['status'] = 0;
            $condition_now_employee_category['cat_id'] = $cat_pfcid;
        }

        if ($condition_employee_category) {
            $now_category = $database_employee_category->field(true)->where($condition_employee_category)->find();
            if(empty($now_category)){
                $this->returnCode(1,'没有找到该分类信息!');
            }
        }
        $write_group_info = M('Employee_write_group')->field(true)->order('gsort DESC, gid DESC')->where(array('status'=>0))->getField('gid, gname,status');
        if (empty($write_group_info)) {
            $write_group_info = D('Employee_category')->write_group_info;
        }
        $condition_now_employee_category['status'] = 0;
        if ($condition_now_employee_category) {
            $write_field = $database_employee_write_field->field(true)->where($condition_now_employee_category)->select();
            foreach ($write_field as &$val) {
                if ($val['type'] == 3 && $val['use_field']) {
                    $val['use_field'] = unserialize($val['use_field']);
                }
            }
            if (!empty($write_field)) {
                foreach ($write_field as &$val) {
                    if ($write_group_info[$val['group_id']]) {
                        if (empty($write_group_info[$val['group_id']]['write_info'])) {
                            $write_group_info[$val['group_id']]['write_info'] = array();
                        }
                        $write_group_info[$val['group_id']]['write_info'][] = $val;
                    }
                }
                $info['write_group_info']=$write_group_info;
            }
            $info['write_field'] = $write_field;
        }

        if($info){
            $this->returnCode(0,$info);
        }else{
            $this->returnCode(1,'没有查询到数据!');
        }
    }

    /**
     * 从业人员信息提交
     */
    public function employee_info_add() {
        $data = array();
        $uid = $this->now_user['uid'];
        $m_employee_list = M('Employee_list');
        $data['uid'] = $uid;
        $info = $m_employee_list->where(array('uid' => $uid))->find();
        if ($info && $info['examine_status'] != 4) {
            $this->error_tips('您已经申请入驻！',U('Employee/employee_info'));
        }
        // 处理分类信息获取填写字段
        $database_employee_category = D('Employee_category');
        $database_employee_write_field = M('Employee_category_write_field');
        $write_field_standard = array();
        if ($_POST['cat_id'] && $_POST['cat_fid'] && $_POST['cat_pfcid']) {
            // 查询对应分类信息条件
            $cat_id = intval($_POST['cat_pfcid']); // 三级分类id
            $cat_fid = intval($_POST['cat_fid']);  // 二级分类id
            $cat_pfcid = intval($_POST['cat_id']); // 一级分类id
            $condition_employee_category['cat_id'] = $cat_pfcid;
            $condition_employee_category['cat_fid'] = 0;
            $condition_employee_category['cat_pfcid'] = 0;
            // 查询填写项信息条件
            $condition_now_employee_category['status'] = 0;
            $condition_now_employee_category['cat_id'] = $cat_pfcid;

            if ($condition_employee_category) {
                $now_category = $database_employee_category->field(true)->where($condition_employee_category)->find();
                if(empty($now_category)){
                    $this->error('没有找到该分类信息！');
                }
            }
            $write_field = $database_employee_write_field->field(true)->where($condition_now_employee_category)->select();
            if (!$write_field) {
                $this->error('请联系后台管理员添加填写项！');
            }
            foreach ($write_field as $vals) {
                $write_field_standard[$vals['name']] = $vals;
            }
            $data['cat_id'] = $cat_id;
            $data['cat_fid'] = $cat_fid;
            $data['cat_pfcid'] = $cat_pfcid;
        } else {
            $this->error('请选择分类！');
        }

        $d_area = D('Area');
        // 处理上传过来的数据
        if ($_POST['write_field']) {
            foreach ($_POST['write_field'] as $val) {
                if ($write_field_standard[$val['name']]['type'] == 8 && $write_field_standard[$val['name']]['iswrite'] == 1 && empty($val['date_value']) && empty($val['time_value'])) {
                    $this->error(' 请填写【'. $val['name'] . '】内容！');
                } elseif ($write_field_standard[$val['name']]['type'] != 8 && $write_field_standard[$val['name']]['iswrite'] == 1 && empty($val['value'])) {
                    $this->error(' 请填写【'. $val['name'] . '】内容！');
                }
                if ($val['alias_name'] == 'unit_address') {
                    if ($val['long'] && $val['lat']) {
                        $area_info = $d_area->cityMatching($val['lat'], $val['long']);
                        if ($area_info['area_info']['province_id']) {
                            $data['province_id'] = $area_info['area_info']['province_id'];
                        }
                        if ($area_info['area_info']['city_id'] && $area_info['area_info']['city_id'] > $data['province_id']) {
                            $data['city_id'] = $area_info['area_info']['city_id'];
                        }
                        if ($area_info['area_info']['area_id'] && $area_info['area_info']['area_id'] > 0) {
                            $data['area_id'] = $area_info['area_info']['area_id'];
                        }
                        $data['lat'] = $val['lat'];
                        $data['long'] = $val['long'];
                    } else {
                        $this->error('请上传定位信息！');
                    }

                } elseif ($val['alias_name'] == 'nickname') {
                    $data['true_name'] = $val['value'];
                } elseif ($val['alias_name'] == 'unit_phone') {
                    $data['business_phone'] = $val['value'];
                } elseif ($val['alias_name'] == 'working_avatar') {
                    $data['avatar'] = reset($val['value']);
                } elseif ($val['alias_name'] == 'working_sex') {
                    $data['sex'] = $val['value'];
                }
            }
            $data['write_field'] = serialize($_POST['write_field']);
        }
        // 处理下是否需要审核
        if ($this->config['examine_employee'] != 1) {
            $data['examine_status'] = 1; // 不需要审核 处于审核通过
        } else {
            $data['examine_status'] = 0; // 需要审核 就处于待审核状态
        }
        // 如果选择了店铺 绑定店铺id
        if ($_POST['store_id']) {
            $data['store_id'] = $_POST['store_id'];
        }
        $data['add_time'] = time();
        $employee_id = $m_employee_list->data($data)->add();
        if ($employee_id) {
            $this->success('入驻成功！');
        } else {
            $this->error('入驻失败，请重试~！');
        }
    }


    /**
     * 从业人员信息提交获取
     */
    public function employee_info() {
        $data = array();
        $uid = $this->now_user['uid'];
        $m_employee_list = M('Employee_list');
        $data['uid'] = $uid;
        $info = $m_employee_list->where(array('uid' => $uid))->order('id DESC')->find();
        if (!$info) {
            $this->error_tips('您未申请入驻，请申请入驻！',U('Employee/index'));
        }
        if ($info['examine_status'] == 4) {
            $this->error_tips('您申请入驻信息已经被删除，请重新申请入驻！',U('Employee/index'));
        }
        // 如果处于审核拒绝状态，查询分类 自定义填写项
        $write_field_standard = array();
        if ($info['examine_status'] == 2 || $info['examine_status'] == 1) {
            $database_employee_category = D('Employee_category');
            $database_employee_write_field = M('Employee_category_write_field');
//            $condition_now_employee_category['status'] = 0;
            $condition_now_employee_category['cat_id'] = $info['cat_pfcid'];
            $now_category = $database_employee_category->field(true)->where($condition_now_employee_category)->order('add_time asc')->find();
            if(empty($now_category)){
                $this->error_tips('没有找到该分类信息！');
            }
            $write_field = $database_employee_write_field->field(true)->where($condition_now_employee_category)->select();
            if (!$write_field) {
                $this->error_tips('请联系后台管理员添加填写项！');
            }
            foreach ($write_field as $vals) {
                $write_field_standard[$vals['name']] = $vals;
            }
        }
        $write_field = unserialize($info['write_field']);
        if ($write_field_standard && $write_field) {
            foreach ($write_field as &$val) {
                if ($val && $val['type'] == 3 && $write_field_standard[$val['name']]) {
                    $val['use_field'] = unserialize($write_field_standard[$val['name']]['use_field']);
                }
                if ($val && $write_field_standard[$val['name']]) {
                    $val['iswrite']  = $write_field_standard[$val['name']]['iswrite'];
                    $val['des']  = $write_field_standard[$val['name']]['des'];
                }
                if (!$val['des']) {
                    if ($val['type'] == 0 || $val['type'] == 1 || $val['type'] == 4 || $val['type'] == 5 || $val['type'] == 6 || $val['type'] == 7 || $val['type'] == 8 || $val['type'] == 9) {
                        $val['des'] = '请填写'.$val['name'].'';
                    } else if($val['type'] == 2 || $val['type'] == 3 || $val['type'] == 11) {
                        $val['des'] = '请点击选择'.$val['name'].'';
                    } else if($val['type'] == 10 || $val['type'] == 12) {
                        $val['des'] = '请上传'.$val['name'].'';
                    }
                }
                if (!$val['group_id']) {
                    $val['group_id'] = $write_field_standard[$val['name']]['group_id'];
                }
            }
        }
        $write_group_info = M('Employee_write_group')->field(true)->order('gsort DESC, gid DESC')->where(array('status'=>0))->getField('gid, gname,status');
        if (empty($write_group_info)) {
            $write_group_info = D('Employee_category')->write_group_info;
        }
        if (!empty($write_field)) {
            foreach ($write_field as &$val) {
                if ($write_group_info[$val['group_id']]) {
                    if (empty($write_group_info[$val['group_id']]['write_info'])) {
                        $write_group_info[$val['group_id']]['write_info'] = array();
                    }
                    $write_group_info[$val['group_id']]['write_info'][] = $val;
                }
            }
            count($write_group_info);
            $this->assign('write_group_info',$write_group_info);
        }

        $this->assign('info',$info);
        $this->assign('write_field',$write_field);
        $upload_key = array();
        $avatar_key = array();
        foreach ($write_field as $key=>$value) {
            if ($value['type'] == 10) {
                $msg = intval($key) + 1;
                $upload_key[] = $key;
                $upload_key[] = $msg;
            } elseif ($value['type'] == 12) {
                $msg = intval($key) + 1;
                $avatar_key[] = $key;
                $avatar_key[] = $msg;
            }
        }
        $upload_str = implode(',', $upload_key);
        $this->assign('upload_str',$upload_str);
        $avatar_str = implode(',', $avatar_key);
        $this->assign('avatar_str',$avatar_str);

        $this->assign('cat_id',$info['cat_pfcid']);
        $this->assign('cat_fid',$info['cat_fid']);
        $this->assign('cat_pfcid',$info['cat_id']);
        // 处理定位问题
        $long_lat = D('User_long_lat')->getLocation($_SESSION['openid'],0);
        if(empty($long_lat) || empty($long_lat['long']) || empty($long_lat['lat'])){
            $long_lat = array(
                'long' => 0,
                'lat' => 0
            );
        }
        $this->assign('long_lat',$long_lat);
        $now_city = D('Area')->get_area_by_areaId($this->config['now_city']);
        $this->assign('city_name', $now_city['area_name']);
        $employee_register_agreement = $this->config['employee_register_agreement'];

        if ($employee_register_agreement) {
            $src = '<img src="'.file_domain().'/';
            $employee_register_agreement = str_replace('<img src="/', $src, $employee_register_agreement);
            $employee_register_agreement = str_replace('<img alt="" src="/', $src, $employee_register_agreement);
            $employee_register_agreement = str_replace('img src=&quot;/', 'img src=&quot;'.file_domain().'/', $employee_register_agreement);
        }

        $this->assign('employee_register_agreement',$employee_register_agreement);

        $this->display('employee_info');
    }


    /**
     * 从业人员信息编辑
     */
    public function employee_info_edit() {
        $data = array();
        $uid = $this->now_user['uid'];
        $m_employee_list = M('Employee_list');
        $info = $m_employee_list->where(array('uid' => $uid))->find();
        if (!$info) {
            $this->error_tips('您未申请入驻，请申请入驻！',U('Employee/index'));
        } elseif ($info['examine_status'] == 0) {
            $this->error_tips('您的申请正在审核，不可修改！',U('Employee/index'));
        }
        // 处理分类信息获取填写字段
        $database_employee_category = D('Employee_category');
        $database_employee_write_field = M('Employee_category_write_field');
        $write_field_standard = array();
        if ($_POST['cat_id'] && $_POST['cat_fid'] && $_POST['cat_pfcid']) {
            // 查询对应分类信息条件
            $cat_id = intval($_POST['cat_pfcid']); // 三级分类id
            $cat_fid = intval($_POST['cat_fid']);  // 二级分类id
            $cat_pfcid = intval($_POST['cat_id']); // 一级分类id
            $condition_employee_category['cat_id'] = $cat_pfcid;
            $condition_employee_category['cat_fid'] = 0;
            $condition_employee_category['cat_pfcid'] = 0;
            // 查询填写项信息条件
            $condition_now_employee_category['status'] = 0;
            $condition_now_employee_category['cat_id'] = $cat_pfcid;

            if ($condition_employee_category) {
                $now_category = $database_employee_category->field(true)->where($condition_employee_category)->find();
                if(empty($now_category)){
                    $this->error('没有找到该分类信息！');
                }
            }
            $write_field = $database_employee_write_field->field(true)->where($condition_now_employee_category)->select();
            if (!$write_field) {
                $this->error('请联系后台管理员添加填写项！');
            }
            foreach ($write_field as $vals) {
                $write_field_standard[$vals['name']] = $vals;
            }
            if ($cat_id != $info['cat_id']) {
                $data['cat_id'] = $cat_id;
            }
            if ($cat_fid != $info['cat_fid']) {
                $data['cat_fid'] = $cat_fid;
            }
            if ($cat_pfcid != $info['cat_pfcid']) {
                $data['cat_pfcid'] = $cat_pfcid;
            }
        } else {
            $this->error('请选择分类！');
        }

        $d_area = D('Area');
        // 处理上传过来的数据
        if ($_POST['write_field']) {
            foreach ($_POST['write_field'] as $val) {
                if ($write_field_standard[$val['name']]['type'] == 8 && $write_field_standard[$val['name']]['iswrite'] == 1 && (!$val['date_value'] || !$val['time_value'])) {
                    $this->error(' 请填写【'. $val['name'] . '】内容！');
                } elseif ($write_field_standard[$val['name']]['type'] != 8 && $write_field_standard[$val['name']]['iswrite'] == 1 && !$val['value']) {
                    $this->error(' 请填写【'. $val['name'] . '】内容！');
                }
                if ($val['alias_name'] == 'unit_address') {
                    if ($val['long'] && $val['lat']) {
                        $area_info = $d_area->cityMatching($val['lat'], $val['long']);
                        if ($area_info['area_info']['province_id'] && $info['province_id'] != $area_info['area_info']['province_id']) {
                            $data['province_id'] = $area_info['area_info']['province_id'];
                        }
                        if ($area_info['area_info']['city_id']  && $info['city_id'] != $area_info['area_info']['city_id'] && $area_info['area_info']['city_id'] > $data['province_id']) {
                            $data['city_id'] = $area_info['area_info']['city_id'];
                        }
                        if ($area_info['area_info']['area_id'] && $info['area_id'] != $area_info['area_info']['area_id'] && $area_info['area_info']['area_id'] > 0) {
                            $data['area_id'] = $area_info['area_info']['area_id'];
                        }
                        $data['lat'] = $val['lat'];
                        $data['long'] = $val['long'];
                    } else {
                        $this->error('请上传定位信息！');
                    }

                } elseif ($val['alias_name'] == 'nickname' && $info['true_name'] != $val['value']) {
                    $data['true_name'] = $val['value'];
                } elseif ($val['alias_name'] == 'unit_phone' && $info['business_phone'] != $val['value']) {
                    $data['business_phone'] = $val['value'];
                } elseif ($val['alias_name'] == 'working_avatar') {
                    $data['avatar'] = reset($val['value']);
                } elseif ($val['alias_name'] == 'working_sex') {
                    $data['sex'] = $val['value'];
                }
            }
            $write_field = serialize($_POST['write_field']);
            if ($write_field != $info['write_field']) {
                $data['write_field'] = serialize($_POST['write_field']);
            }
        }
        // 处理下是否需要审核
        if ($this->config['examine_employee'] == 1) {
            if (!empty($data)) {
                $data['examine_status'] = 0; // 如果本身为拒绝，改为待审核
            }
        } else {
            if (!empty($data)) {
                $data['examine_status'] = 1; // 不需要审核 处于审核通过
            }
        }
        // 如果选择了店铺 绑定店铺id
        if ($_POST['store_id']) {
            $data['store_id'] = $_POST['store_id'];
        }
        if (!empty($data)) {
            $data['last_time'] = time(); // 最后更新时间
            $employee_id = $m_employee_list->where(array('uid' => $uid))->data($data)->save();
            if ($employee_id) {
                $this->success('信息修改成功！', U('Employee/my_detail'));
            } else {
                $this->error('信息修改失败，请重试~！');
            }
        } else {
            $this->error('没有任何修改信息，请重试~！');
        }
    }

    /**
     *图片上传
     *
     */
    public function ajaxImgUpload(){
        if($_FILES['file']['error'] != 4){
            $image = D('Image')->handle($this->user_session['uid'], 'employee', 2);
            if(!$image['error']){
                echo 'success|'.$image['url']['file'];
            } else {
                echo 'error|'.$image['message'];
            }
        }
    }

    /**获取从业者列表信息*/
    public function employee_list(){
        if ($_GET['key'] == 'store') {
            $this->employee_store_list();
            exit;
        }
        $keyword = I('keyword', false);
        if ($keyword) {
            $this->assign('keyword',$keyword);
        }
        //导航条
        $tmp_wap_index_slider = D('Slider')->get_slider_by_key('wap_employee_slider',0);

        $wap_index_slider = array();

        if(count($tmp_wap_index_slider) >= 10 &&$this->config['wap_slider_number'] == 10){
            $wap_slider_number = $this->config['wap_slider_number'];
        }else{
            $wap_slider_number = 8;
        }

        foreach($tmp_wap_index_slider as $key=>$value){
            $tmp_i = floor($key/$wap_slider_number);
            $wap_index_slider[$tmp_i][] = $value;
        }

        $this->assign('wap_index_slider',$wap_index_slider);
        $this->assign('wap_index_slider_count',count($wap_index_slider));
        $this->assign('wap_index_slider_number',$wap_slider_number);

        //判断分类信息
        $cat_url = !empty($_GET['cat_url']) ? $_GET['cat_url'] : '';
        $this->assign('now_cat_url', $cat_url);

        //判断地区信息
        $area_url = !empty($_GET['area_url']) ? $_GET['area_url'] : '';
        $this->assign('now_area_url',$area_url);

        $circle_id = 0;
        if(!empty($area_url)){
            $tmp_area = D('Area')->get_area_by_areaUrl($area_url);
            if(empty($tmp_area)){
                $this->error('当前区域不存在！');
            }
            $this->assign('now_area', $tmp_area);

            if ($tmp_area['area_type'] == 3) {
                $now_area = $tmp_area;
            } else {
                $now_circle = $tmp_area;
                $this->assign('now_circle', $now_circle);
                $now_area = D('Area')->get_area_by_areaId($tmp_area['area_pid'], true, $area_url);
                if (empty($tmp_area)) {
                    $this->error('当前区域不存在！');
                }
                $circle_url = $now_circle['area_url'];
                $circle_id = $now_circle['area_id'];
                $area_url = $now_area['area_url'];
            }
            $this->assign('top_area', $now_area);
            $area_id = $now_area['area_id'];
        }else{
            $area_id = 0;
        }

        //判断排序信息
        $sort_id = !empty($_GET['sort_id']) ? $_GET['sort_id'] : 'defaults';
        $long_lat = D('User_long_lat')->getLocation($_SESSION['openid'],0);
        if(empty($long_lat) || empty($long_lat['long']) || empty($long_lat['lat'])){
            $sort_id = $sort_id == 'juli' ? 'defaults' : $sort_id;
            $sort_array = array(
                array('sort_id'=>'defaults','sort_value'=>'智能排序'),
                array('sort_id'=>'juli','sort_value'=>'离我最近'),
                array('sort_id'=>'rating','sort_value'=>'评价最高'),
                array('sort_id'=>'popularity','sort_value'=>'人气最高'),
                array('sort_id'=>'start','sort_value'=>'最新发布')
            );
        } else {
            $sort_array = array(
                array('sort_id'=>'defaults','sort_value'=>'智能排序'),
                array('sort_id'=>'juli','sort_value'=>'离我最近'),
                array('sort_id'=>'rating','sort_value'=>'评价最高'),
                array('sort_id'=>'popularity','sort_value'=>'人气最高'),
                array('sort_id'=>'start','sort_value'=>'最新发布')
            );
            $this->assign('long_lat',$long_lat);
        }
        foreach($sort_array as $key=>$value){
            if($sort_id == $value['sort_id']){
                $now_sort_array = $value;
                break;
            }
        }

        $this->assign('sort_array',$sort_array);
        $this->assign('now_sort_array',$now_sort_array);


        //所有分类 包含2级分类
        $all_category_list = D('Employee_category')->get_all_category();
        $this->assign('all_category_list',$all_category_list);

        //根据分类信息获取分类
        if(!empty($cat_url)){
            $now_category = D('Employee_category')->get_category_by_catUrl($cat_url);
            if(empty($now_category)){
                $this->error_tips('此分类不存在！');
            }
            $this->assign('now_category',$now_category);

            if(!empty($now_category['cat_pfcid']) && !empty($now_category['cat_fid'])) {
                $top_category = D('Employee_category')->get_category_by_id($now_category['cat_pfcid']);
                $category_url = $top_category['cat_url'];
                $category_write_field = $top_category['write_field'];

                $this->assign('top_category',$top_category);
                $cat_id = $now_category['cat_id'];
                $cat_fid = $now_category['cat_fid'];
                $cat_pfcid = $now_category['cat_pfcid'];
            }elseif(empty($now_category['cat_pfcid']) && !empty($now_category['cat_fid'])){
                $top_category = D('Employee_category')->get_category_by_id($now_category['cat_fid']);
                $category_url = $top_category['cat_url'];
                $category_write_field = $top_category['write_field'];

                $this->assign('top_category',$top_category);
                $cat_id = $now_category['cat_id'];
                $cat_fid = $now_category['cat_fid'];
                $cat_pfcid = $now_category['cat_pfcid'];
            }else{
                $category_url = $now_category['cat_url'];
                $category_write_field = $now_category['write_field'];

                $this->assign('top_category',$now_category);
                $cat_id = $now_category['cat_id'];
                $cat_fid = $now_category['cat_fid'];
                $cat_pfcid = $now_category['cat_pfcid'];
            }
        } else {
            $cat_id = 0;
            $cat_fid = 0;
            $cat_pfcid = 0;
        }
        $all_area_list = D('Area')->get_all_area_list();
        $this->assign('all_area_list',$all_area_list);
//        $this->assign(D('Employee_list')->employee_list($sort_id,$cat_id,$cat_fid,$cat_pfcid,$area_id,$circle_id, $long_lat['lat'], $long_lat['long']));


        //底部导航
        $home_menu_list = D('Home_menu')->getMenuList('employee_footer');
        if($home_menu_list){
            $this->assign('home_menu_list',$home_menu_list);
        }


        /* 粉丝行为分析 */
        $this->behavior(array('model'=>'employee_list'));
        if ($_GET['key'] == 'store') {
            $this->display('employee_store_list');
        } else {
            $this->display();
        }
    }

    /**ajax获取从业者列表信息*/
    public function ajaxList(){
        $this->header_json();
        //判断分类信息
        $cat_url = !empty($_GET['cat_url']) ? $_GET['cat_url'] : '';

        //判断地区信息
        $area_url = !empty($_GET['area_url']) ? $_GET['area_url'] : '';

        $circle_id = 0;
        if(!empty($area_url)){
            $tmp_area = D('Area')->get_area_by_areaUrl($area_url);
            if(empty($tmp_area)){
                $this->error('当前区域不存在！');
            }

            if ($tmp_area['area_type'] == 3) {
                $now_area = $tmp_area;
            } else {
                $now_circle = $tmp_area;
                $now_area = D('Area')->get_area_by_areaId($tmp_area['area_pid'], true, $area_url);
                if (empty($tmp_area)) {
                    $this->error('当前区域不存在！');
                }
                $circle_url = $now_circle['area_url'];
                $circle_id = $now_circle['area_id'];
                $area_url = $now_area['area_url'];
            }
            $area_id = $now_area['area_id'];
        }else{
            $area_id = 0;
        }

        //判断排序信息
        $sort_id = !empty($_GET['sort_id']) ? $_GET['sort_id'] : 'juli';
        $long_lat = D('User_long_lat')->getLocation($_SESSION['openid'],0);
        if(empty($long_lat) || empty($long_lat['long']) || empty($long_lat['lat'])){
            $sort_id = $sort_id == 'juli' ? 'defaults' : $sort_id;
            $sort_array = array(
                array('sort_id'=>'defaults','sort_value'=>'智能排序'),
                array('sort_id'=>'juli','sort_value'=>'离我最近'),
                array('sort_id'=>'rating','sort_value'=>'评价最高'),
                array('sort_id'=>'popularity','sort_value'=>'人气最高'),
                array('sort_id'=>'start','sort_value'=>'最新发布')
            );
        } else {
            $sort_array = array(
                array('sort_id'=>'defaults','sort_value'=>'智能排序'),
                array('sort_id'=>'juli','sort_value'=>'离我最近'),
                array('sort_id'=>'rating','sort_value'=>'评价最高'),
                array('sort_id'=>'popularity','sort_value'=>'人气最高'),
                array('sort_id'=>'start','sort_value'=>'最新发布')
            );
            $this->assign('long_lat',$long_lat);
        }
        $now_sort_array = array();
        foreach($sort_array as $key=>$value){
            if($sort_id == $value['sort_id']){
                $now_sort_array = $value;
                break;
            }
        }

        //根据分类信息获取分类
        $top_category = array();
        if(!empty($cat_url)){
            $now_category = D('Employee_category')->get_category_by_catUrl($cat_url);
            if(empty($now_category)){
                $this->error_tips('此分类不存在！');
            }
            $this->assign('now_category',$now_category);

            if(!empty($now_category['cat_pfcid']) && !empty($now_category['cat_fid'])) {
                $top_category = D('Employee_category')->get_category_by_id($now_category['cat_pfcid']);
                $category_url = $top_category['cat_url'];
                $category_write_field = $top_category['write_field'];

                $this->assign('top_category',$top_category);
                $cat_id = $now_category['cat_id'];
                $cat_fid = $now_category['cat_fid'];
                $cat_pfcid = $now_category['cat_pfcid'];
            }elseif(empty($now_category['cat_pfcid']) && !empty($now_category['cat_fid'])){
                $top_category = D('Employee_category')->get_category_by_id($now_category['cat_fid']);
                $category_url = $top_category['cat_url'];
                $category_write_field = $top_category['write_field'];

                $this->assign('top_category',$top_category);
                $cat_id = $now_category['cat_pfcid'];
                $cat_fid = $now_category['cat_id'];
                $cat_pfcid = $now_category['cat_fid'];
            }else{
                $top_category = $now_category;
                $category_url = $now_category['cat_url'];
                $category_write_field = $now_category['write_field'];

                $this->assign('top_category',$now_category);
                $cat_id = $now_category['cat_pfcid'];
                $cat_fid = $now_category['cat_fid'];
                $cat_pfcid = $now_category['cat_id'];
            }
        } else {
            $cat_id = 0;
            $cat_fid = 0;
            $cat_pfcid = 0;
        }

        $keyword = I('get.keyword', false);

        $employee_list = D('Employee_list')->employee_list(5,$sort_id,$cat_id,$cat_fid,$cat_pfcid,$area_id,$circle_id, $long_lat['lat'], $long_lat['long'], $keyword);


        $employee_list['now_sort_array'] = $now_sort_array;
        $employee_list['top_category'] = $top_category;
        echo json_encode($employee_list);
        /* 粉丝行为分析 */
        $this->behavior(array('model'=>'employee_list'));
    }


    /**用户收藏的从业者列表*/
    public function employee_collect_list(){
        $uid = $this->now_user['uid'];
        if(empty($uid)){
            session('user',null);
            $this->error_tips('未获取到您的帐号信息，请重新登录！',U('Login/index'));
        }
        /* 粉丝行为分析 */
        $this->behavior(array('model'=>'employee_list'));
        if ($_GET['key'] == 'store') {
            $this->display('employee_store_list');
        } else {
            $this->display();
        }
    }

    /**ajax获取用户收藏的从业者列表信息*/
    public function collect_ajax_list(){
        $this->header_json();
        $uid = $this->now_user['uid'];
        $circle_id = 0;
        $area_id = 0;
        //判断排序信息
        $sort_id = 'defaults';
        $long_lat = D('User_long_lat')->getLocation($_SESSION['openid'],0);
        $this->assign('long_lat',$long_lat);
        //根据分类信息获取分类
        $cat_id = 0;
        $cat_fid = 0;
        $cat_pfcid = 0;

        $keyword = I('get.keyword', false);

        $employee_list = D('Employee_list')->employee_collect_list($uid,5,$sort_id,$cat_id,$cat_fid,$cat_pfcid,$area_id,$circle_id, $long_lat['lat'], $long_lat['long'], $keyword);

        echo json_encode($employee_list);
        /* 粉丝行为分析 */
        $this->behavior(array('model'=>'employee_list'));
    }


    /**店铺列表*/
    public function employee_store_list(){
        //判断分类信息
        $cat_url = !empty($_GET['cat_url']) ? $_GET['cat_url'] : '';
        $this->assign('now_cat_url', $cat_url);

        //判断地区信息
        $area_url = !empty($_GET['area_url']) ? $_GET['area_url'] : '';
        $this->assign('now_area_url',$area_url);



        //导航条
        $tmp_wap_index_slider = D('Slider')->get_slider_by_key('wap_store_slider',0);

        $wap_index_slider = array();

        if(count($tmp_wap_index_slider) >= 10 &&$this->config['wap_slider_number'] == 10){
            $wap_slider_number = $this->config['wap_slider_number'];
        }else{
            $wap_slider_number = 8;
        }

        foreach($tmp_wap_index_slider as $key=>$value){
            $tmp_i = floor($key/$wap_slider_number);
            $wap_index_slider[$tmp_i][] = $value;
        }

        $this->assign('wap_index_slider',$wap_index_slider);
        $this->assign('wap_index_slider_count',count($wap_index_slider));
        $this->assign('wap_index_slider_number',$wap_slider_number);

        $circle_id = 0;
        if(!empty($area_url)){
            $tmp_area = D('Area')->get_area_by_areaUrl($area_url);
            if(empty($tmp_area)){
                $this->error('当前区域不存在！');
            }
            $this->assign('now_area', $tmp_area);

            if ($tmp_area['area_type'] == 3) {
                $now_area = $tmp_area;
            } else {
                $now_circle = $tmp_area;
                $this->assign('now_circle', $now_circle);
                $now_area = D('Area')->get_area_by_areaId($tmp_area['area_pid'], true, $cat_url);
                if (empty($tmp_area)) {
                    $this->error('当前区域不存在！');
                }
                $circle_url = $now_circle['area_url'];
                $circle_id = $now_circle['area_id'];
                $area_url = $now_area['area_url'];
            }
            $this->assign('top_area', $now_area);
            $area_id = $now_area['area_id'];
        }else{
            $area_id = 0;
        }

        //判断排序信息
        $sort_id = !empty($_GET['sort_id']) ? $_GET['sort_id'] : 'juli';

        $long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);	//获取用户的经度和纬度
        if(empty($long_lat) || empty($long_lat['long']) || empty($long_lat['lat'])){
            $sort_id = $sort_id == 'juli' ? 'defaults' : $sort_id;
            $sort_array = array(
                array('sort_id'=>'defaults','sort_value'=>'默认排序'),
                array('sort_id'=>'rating','sort_value'=>'评价最高'),
                array('sort_id'=>'start','sort_value'=>'最新发布'),
                array('sort_id'=>'solds','sort_value'=>'人气最高'),
            );
        } else {
            $sort_array = array(
                array('sort_id'=>'juli','sort_value'=>'离我最近'),
                array('sort_id'=>'rating','sort_value'=>'评价最高'),
                array('sort_id'=>'start','sort_value'=>'最新发布'),
                array('sort_id'=>'solds','sort_value'=>'人气最高'),
            );
            $this->assign('long_lat',$long_lat);
        }
        foreach($sort_array as $key=>$value){
            if($sort_id == $value['sort_id']){
                $now_sort_array = $value;
                break;
            }
        }
        $this->assign('sort_array',$sort_array);
        $this->assign('now_sort_array',$now_sort_array);

        //所有分类 包含2级分类
        $all_category_list = D('Merchant_category')->get_all_category();
        $this->assign('all_category_list',$all_category_list);

        //根据分类信息获取分类
        if(!empty($cat_url)){
            $now_category = D('Merchant_category')->get_category_by_catUrl($cat_url);
            if(empty($now_category)){
                $this->error_tips('此分类不存在！');
            }
            $this->assign('now_category',$now_category);

            if(!empty($now_category['cat_fid'])){
                $f_category = D('Merchant_category')->get_category_by_id($now_category['cat_fid']);
                $all_category_url = $f_category['cat_url'];
                $category_cat_field = $f_category['cat_field'];

                $top_category = $f_category;
                $this->assign('top_category',$f_category);

                $get_grouplist_catfid = 0;
                $get_grouplist_catid = $now_category['cat_id'];
            }else{
                $all_category_url = $now_category['cat_url'];
                $category_cat_field = $now_category['cat_field'];
                $top_category = $now_category;
                $this->assign('top_category',$now_category);

                $get_grouplist_catfid = $now_category['cat_id'];
                $get_grouplist_catid = 0;
            }
        }

        $all_area_list = D('Area')->get_all_area_list();
        $this->assign('all_area_list',$all_area_list);
        //$this->assign(D('Merchant')->wap_get_Merchant_list_by_catid($get_grouplist_catid,$get_grouplist_catfid,$area_id,$sort_id, $long_lat['lat'], $long_lat['long'], $circle_id,$_GET['w']));

        /* 粉丝行为分析 */
        $this->behavior(array('model'=>'Employee_store_list'));


        //底部导航
        $home_menu_list = D('Home_menu')->getMenuList('employee_footer');
        if($home_menu_list){
            $this->assign('home_menu_list',$home_menu_list);
        }

        $this->display();
    }

    /**ajax获取店铺列表*/
    public function employee_store_list_ajaxList(){
        $this->header_json();
        //判断分类信息
        $cat_url = !empty($_GET['cat_url']) ? $_GET['cat_url'] : '';

        //判断地区信息
        $area_url = !empty($_GET['area_url']) ? $_GET['area_url'] : '';
        $circle_id = 0;
        if(!empty($area_url)){
            $tmp_area = D('Area')->get_area_by_areaUrl($area_url);
            $tmp_area_id	=	$tmp_area['area_id'];
            if(empty($tmp_area)){
                $this->error('当前区域不存在！');
            }

            if ($tmp_area['area_type'] == 3) {
                $now_area = $tmp_area;
            } else {
                $now_circle = $tmp_area;
                $now_area = D('Area')->get_area_by_areaId($tmp_area['area_pid'], true, $cat_url);
                if (empty($tmp_area)) {
                    $this->error('当前区域不存在！');
                }
                $circle_url = $now_circle['area_url'];
                $circle_id = $now_circle['area_id'];
                $area_url = $now_area['area_url'];
            }
            $area_id = $now_area['area_id'];
        }else{
            $area_id = 0;
        }
        //判断商圈分类信息
        $default_circle_id=I('get.default_circle_id','','intval');
        if($default_circle_id>0){
            $circle_id=$default_circle_id;
        }
        //判断排序信息
        $sort_id = !empty($_GET['sort_id']) ? $_GET['sort_id'] : 'juli';
        $long_lat = D('User_long_lat')->getLocation($_SESSION['openid'],0);
        if(empty($long_lat) || empty($long_lat['long']) || empty($long_lat['lat'])){
            $sort_id = $sort_id == 'juli' ? 'defaults' : $sort_id;
        }
        if($_GET['page'] == 1){
            if(empty($long_lat) || empty($long_lat['long']) || empty($long_lat['lat'])){
                $condition_field = "`img`,`area_id`,`introduce`,`market_name`,`market_id`, ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$long_lat['lat']}*PI()/180-`lat`*PI()/180)/2),2)+COS({$long_lat['lat']}*PI()/180)*COS(`lat`*PI()/180)*POW(SIN(({$long_lat['long']}*PI()/180-`long`*PI()/180)/2),2)))*1000) AS juli";
            }else{
                $condition_field = "`img`,`area_id`,`introduce`,`market_name`,`market_id`";
            }
            if($tmp_area_id){
                $area_market	=	M('Area_market')->field($condition_field)->order('market_sort desc')->where(array('area_id'=>$tmp_area_id,'is_open'=>1))->limit(3)->select();
            }else{
//				$area_market	=	M('Area_market')->field($condition_field)->order('market_sort desc')->where(array('city_id'=>C('config.now_city'),'is_hot'=>1,'is_open'=>1))->limit(3)->select();
            }
            foreach($area_market as $k=>$v){
                $area_market[$k]['img']		=	replace_file_domain($v['img']);
                $area_market[$k]['count']	=	M('merchant_store')->where(array('market_id'=>$v['market_id'],'status'=>1))->count();
                if($v['juli']){
                    $area_market[$k]['range_txt']	=	$this->wapFriendRange($v['juli']);
                }else{
                    $area_market[$k]['range_txt']	=	'';
                }
                $area_market[$k]['url']	=	U('Merchant/store_market_list',array('market_id'=>$v['market_id']));
            }
        }
        //所有分类 包含2级分类
        // $all_category_list = D('Merchant_category')->get_all_category();

        //根据分类信息获取分类
        $employee_cat_url = '';
        if(!empty($cat_url)){
            $now_category = D('Merchant_category')->get_category_by_catUrl($cat_url);
            if(empty($now_category)){
                $this->error_tips('此分类不存在！');
            }

            if(!empty($now_category['cat_fid'])){
                $f_category = D('Merchant_category')->get_category_by_id($now_category['cat_fid']);

                // 查询一下与之关联的从业者分类
                $employee_cat_url = M('Employee_category')->where(array('merchant_cat_url' => $f_category['cat_url']))->getField('cat_url');

                $all_category_url = $f_category['cat_url'];
                $category_cat_field = $f_category['cat_field'];

                $top_category = $f_category;

                $get_grouplist_catfid = 0;
                $get_grouplist_catid = $now_category['cat_id'];
            }else{
                // 查询一下与之关联的从业者分类
                $employee_cat_url = M('Employee_category')->where(array('merchant_cat_url' => $now_category['cat_url']))->getField('cat_url');
                $all_category_url = $now_category['cat_url'];
                $category_cat_field = $now_category['cat_field'];
                $top_category = $now_category;

                $get_grouplist_catfid = $now_category['cat_id'];
                $get_grouplist_catid = 0;
            }
        }

        $return = S('wap_store_'.($get_grouplist_catfid ? $get_grouplist_catfid : $get_grouplist_catid).'_'.$area_id.'_'.$sort_id.'_'.$_GET['page']);
        if(empty($return)){
            $return = D('Employee_claim_store')->wap_get_storeList_by_catid($get_grouplist_catid,$get_grouplist_catfid,$area_id,$sort_id, $long_lat['lat'], $long_lat['long'], $circle_id,$_GET['w']);
            S('wap_store_group_'.($get_grouplist_catfid ? $get_grouplist_catfid : $get_grouplist_catid).'_'.$area_id.'_'.$sort_id.'_'.$_GET['page'],$return,360);
        }
        $m_merchant_category = M('Merchant_category');
        $m_area = M('Area');
        if ($return['store_list']) {
            foreach($return['store_list'] as &$storeValue){

                if ($storeValue['mer_id']<=0 && $storeValue['is_claim']==1) {
                    $is_claim_store = 1;
                } else {
                    $is_claim_store = 0;
                }
                $storeValue['url'] = U('Employee/shop', array(
                    'store_id' => $storeValue['store_id'],
                    'is_claim_store' => $is_claim_store
                ));

                //得到友好的距离
                if($long_lat&&$long_lat['long']&&$long_lat['lat']&&($sort_id=='defaults'||$sort_id=='juli')){
                    $storeValue['range_txt'] = $this->wapFriendRange($storeValue['juli']);
                }else{
                    $storeValue['range_txt'] =	'';
                }
                $storeValue['fans_count'] = M('Merchant_user_relation')->where(array('mer_id'=>$storeValue['mer_id']))->count();
                //	预约
                if($this->config['appoint_site_logo']){
                    $storeValue['now_appoint']	=	D('Appoint')->get_appointlist_by_StoreId($storeValue['store_id'],true,2);
                }
                // 从业者和店铺切换显示 获取分类和商圈
                $storeValue['classification'] = $M(_view('Merchant_category'))->where(array('cat_id' => $storeValue['cat_id']))->getField('cat_name');
                $storeValue['circle_info'] = $m_area->where(array('area_id' => $storeValue['circle_id']))->getField('area_name');

            }
        } else {
            $return['store_list'] = array();
        }
        $return['style'] = 'group';
        $return['employee_cat_url'] = $employee_cat_url;
        $return['market_list'] = isset($area_market)?$area_market:array();
        $return['img']	=	array(
            'group_alias_name'	=>	substr($this->config['group_alias_name'],0,3),
            'waimai_alias_name'	=>	substr($this->config['waimai_alias_name'],0,3),
            'meal_alias_name'	=>	substr($this->config['meal_alias_name'],0,3),
        );
        echo json_encode($return);
        /* 粉丝行为分析 */
        $this->behavior(array('model'=>'Employee_store_list'));
    }


    /**
     * 获取店铺列表
     */
    public function ajaxMerchantStore() {
        $name = isset($_GET['key']) ? htmlspecialchars($_GET['key']) : '';
        $m_merchant_store = M('Merchant_store');

        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $where = array(
            'status' => array('in', array(1,6))
        );
        $where['_string'] = '((is_claim=1 AND auth=0 AND claim_status IN (0,1)) OR (is_claim!=1 AND auth=3))';
        if ($name) {
            $where['name'] = array('like', '%' . $name . '%');
        }
        $cid = isset($_GET['cid']) ? intval($_GET['cid']) : '';
        if ($cid) {
            $merchant_cat_id = M('Employee_category')->where(array('cat_id' => $cid))->getField('merchant_cat_id');
            if ($merchant_cat_id) {
                $where['cat_fid'] = $merchant_cat_id;
            }
        }
        $pagesize = 10;
        $pageNum = $pagesize * ($page - 1);
        $total = $m_merchant_store->where($where)->count();
        $list = $m_merchant_store->where($where)->limit($pageNum, $pagesize)->select();

        $store_image_class = new store_image();
        foreach ($list as &$val) {
            $images = $store_image_class->get_allImage_by_path($val['pic_info']);
            $val['image'] = $images ? array_shift($images) : '';
        }


        $totalPage = ceil($total / $pagesize);

        echo json_encode(array('store_list' => $list, 'has_more' => $totalPage > $page ? true : false));
    }

    /**评论页面*/
    public function comment_reply() {
        $uid = $this->now_user['uid'];
        $employee_id = I('employee_id', 0, 'intval');
        $this->assign('employee_id',$employee_id);
        $m_employee_list = M('Employee_list');
        $employee_info = $m_employee_list->where(array('id' => $employee_id))->find();
        if (empty($employee_info)) {
            $this->error_tips('评论对象不存在或者已经被删除！');
        }
        if ($uid == $employee_info['uid']) {
            $this->error_tips('自己不能评论自己！');
        }
        $m_employee_reply = M('Employee_reply');
        $already_reply = $m_employee_reply->where(array('reply_uid' => $uid, 'employee_id' => $employee_id))->find();
        if ($already_reply) {
            $this->error_tips('您已经评论过，不能再进行评论！');
        }
        // 获取所有标签
        $employee_memory_list = D('Employee_memory')->employee_memory_list();
        $this->assign('employee_memory_list',$employee_memory_list);
        // 获取一下从业最上级分类信息
        if ($employee_info['cat_pfcid']) {
            $cat_id = $employee_info['cat_pfcid'];
        } elseif ($employee_info['cat_fid']) {
            $cat_id = $employee_info['cat_fid'];
        } else {
            $cat_id = $employee_info['cat_id'];
        }
        $is_comment = M('Employee_category')->where(array('cat_id' => $cat_id))->getField('is_comment');
        $this->assign('is_comment',$is_comment);


        $this->display();
    }

    /**
     * 用户绑定标签， 评价， 文字， 评分
     */
    public function employee_reply() {
        $uid = $this->now_user['uid'];
        $data['reply_uid'] = $uid;
        $employee_id = I('employee_id', 0, 'intval');
        if (empty($employee_id)) {
            $this->error_tips('缺少评论对象！');
        }
        $m_employee_list = M('Employee_list');
        $employee_info = $m_employee_list->where(array('id' => $employee_id))->find();
        if (empty($employee_info)) {
            $this->error_tips('评论对象不存在或者已经被删除！');
        }
        if ($uid == $employee_info['uid']) {
            $this->error_tips('自己不能评论自己！');
        }
        $m_employee_reply = M('Employee_reply');
        $already_reply = $m_employee_reply->where(array('reply_uid' => $uid, 'employee_id' => $employee_id))->find();
        if ($already_reply) {
            $this->error_tips('您已经评论过，不能再进行评论！');
        }

        $data['uid'] = $employee_info['uid'];
        $data['employee_id'] = $employee_id;

        $score_num = I('score_num', 0, 'intval');
        $reply_star = I('reply_star', 0, 'intval');
        if ($reply_star > 0) {
            $score_num = $reply_star;
        }
        if (empty($score_num)) {
            $this->error_tips('请选择评分！');
        }
        if ($score_num<=0 || $score_num >10) {
            $this->error_tips('请正确评分！');
        }
        $data['score_num'] = $score_num;
        $reply_content = I('reply_content', '', 'htmlspecialchars');
        if (empty($reply_content)) {
            $this->error_tips('请填写评论内容！');
        }

        // 处理图片
        $image = array();
        if ($_FILES) {
            $image_info = D('Image')->handle($uid, 'employee', 0, array('size' => 20));
            if (!$image_info['error']) {
                $image = $image_info['url']['file'];
            }
        } else {
            $image = I('image');
        }

        $is_comment = D('Employee_category')->category_is_comment($employee_info['cat_pfcid']);
        if (!$is_comment && ($reply_content || $image)) {
            $this->error_tips('不能进行文字评论和上传图片！');
        }
        $data['reply_content'] = $reply_content;
        if ($image) {
            $data['image'] = serialize($image);
        }
        // 处理标签印象
        $memory_arr = I('memory_arr');
        if (!is_array($memory_arr)) {
            $memory_arr = array_unique(explode(',',$memory_arr));
        }
        $d_employee_memory = D('Employee_memory');
        $d_employee_memory_bind = D('Employee_memory_bind');
        foreach ($memory_arr as &$val) {
            if (!$val) {
                unset($val);
            } elseif (!($d_employee_memory->employee_memory($val))) {
                $this->error_tips('所选标签被禁用或已删除，请刷新重试！');
            }
        }



        D()->startTrans();
        $data['add_time'] = time();
        $reply_id = $m_employee_reply->data($data)->add();
        if ($reply_id) {
            $bind = $d_employee_memory_bind->bind_arr_info($memory_arr, $employee_info['uid'], $employee_id, $reply_id);
            if ($bind) {
                $score = D('Employee_list')->employee_score_num($employee_id);
                if ($score) { // 计算一下从业者平均评分
                    $m_employee_list->where(array('id' => $employee_id))->data(array('score_average' => $score))->save();
                }
                D()->commit();
                $this->error_tips('评论成功！');
            } else {
                D()->rollback();
                $this->error_tips('评论失败，请重试~！');
            }
        } else {
            D()->rollback();
            $this->error_tips('评论失败，请重试~！');
        }

    }

    /**从业人员详情信息*/
    public function employee_detail() {
        $employee_id = I('employee_id', 0, 'intval');
        $where['a.id'] = $employee_id;
        $where['a.is_open'] = 1;
        $where['a.examine_status'] = 1;
        $list = M('Employee_list')
            ->alias('a')
            ->field('a.*,a.avatar as employee_avatar,b.name as merchant_store_name,c.avatar,c.phone')
            ->where($where)
            ->join('left join pigcms_merchant_store as b on a.store_id=b.store_id')
            ->join('left join pigcms_user as c on a.uid=c.uid')
            ->find();
        if (empty($list)) {
            $this->error_tips('该'.$this->config['employee_alias'].'信息不存在或者已删除！', U('Employee/employee_list'));
        } elseif ($list['examine_status'] == 0) {
            $this->error_tips('该'.$this->config['employee_alias'].'处于待审核状态！', U('Employee/employee_list'));
        } elseif ($list['examine_status'] == 2) {
            $this->error_tips('该'.$this->config['employee_alias'].'审核未通过！', U('Employee/employee_list'));
        }
        if ($list['store_id'] > 0) {
            $list['merchant_store_name']=$merchant_store = M('Merchant_store')->where(array('store_id' => $list['store_id']))->getField('name');
        }
        if ($list['cat_id']) {
            $employee_category = M('Employee_category')->where(array('cat_id' => $list['cat_id']))->find();
            $list['cat_name'] = $employee_category['cat_name'];
            $list['adver_url'] = $employee_category['adver_url'];
            $list['adver_color'] = $employee_category['adver_color'];
            $list['adver_info'] = $employee_category['adver_info'];
            $list['is_open_adver'] = $employee_category['is_open_adver'];
        }

        $uid = $this->now_user['uid'];
        if ($list['uid'] == $uid) {
            $list['can_collect'] = false;  // 自己不可收藏自己
            $list['can_thumb'] = false;  // 自己不可点赞自己
            $list['can_reply'] = false;  // 自己不可评论自己
        } else {
            $list['can_collect'] = true;  // 自己不可收藏自己
            $list['can_thumb'] = true;  // 自己不可点赞自己
            $list['can_reply'] = true;  // 可评论其他人自己
        }
        // 如果已经进行过评论，隐藏评论入口
        $m_employee_reply = M('Employee_reply');
        $already_reply = $m_employee_reply->where(array('reply_uid' => $uid, 'employee_id' => $employee_id))->find();
        if ($already_reply) {
            $list['can_reply'] = false;  // 自己不可评论自己
        }

        // 是否已经收藏、是否已经点赞
        $already_thumb = M('Employee_thumb_bind')->where(array('thumb_id' => $employee_id, 'uid' => $uid))->find();
        if ($already_thumb) {
            $list['is_thumbed'] = true; // 已经点赞过
        } else {
            $list['is_thumbed'] = false; // 未点赞过
        }
        $already_collect = M('Employee_collect_bind')->where(array('collect_id' => $employee_id, 'uid' => $uid))->find();
        if ($already_collect) {
            $list['is_collected'] = true; // 已经收藏过
        } else {
            $list['is_collected'] = false; // 未收藏过
        }
        if($list['employee_avatar']){
            //默认头像
            $list['avatar'] = replace_file_domain($list['employee_avatar']);
        }

        if(!$list['avatar']){
            //默认头像
            $list['avatar'] = C('config.site_url') . '/tpl/Wap/pure/static/employee/images/pic-default.png?t=001';
        }
        $write_field = unserialize($list['write_field']);
        $list['write_field'] = $write_field;
        $d_employee_category_write_field = M('employee_category_write_field');
        $write_group_info = M('Employee_write_group')->field(true)->order('gsort DESC, gid DESC')->where(array('status'=>0))->getField('gid, gname,status,gsort');
        if (empty($write_group_info)) {
            $write_group_info = D('Employee_category')->write_group_info;
        }
        $group = array();
        foreach ($write_field as $key => $value) {
            if ($value['id'] && $value['cat_id']) {
                $is_show = $d_employee_category_write_field->where(array('id' => $value['id']))->getField('is_show');
                $group_id = $d_employee_category_write_field->where(array('id' => $value['id']))->getField('group_id');
            } elseif ($value['name'] && $value['cat_id']) {
                $is_show = $d_employee_category_write_field->where(array('name' => $value['name'], 'cat_id' => $value['cat_id']))->order('add_time desc')->getField('is_show');
                $group_id = $d_employee_category_write_field->where(array('name' => $value['name'], 'cat_id' => $value['cat_id']))->getField('group_id');
            } else {
                $is_show = false;
            }
            if($value['alias_name'] && $is_show){
                $list['person_info'][$value['alias_name']]['name'] = $value['name'];
                if($value['type'] == 10){
                    foreach($value['value'] as $k=>&$v){
                        $list['person_info'][$value['alias_name']]['value'][] = replace_file_domain($v);
                        $v = replace_file_domain($v);
                    }
                }else{
                    $list['person_info'][$value['alias_name']]['value'] = $value['value'];
                }
                if ($value['type'] == 2) {
                    $list['person_info'][$value['alias_name']]['long'] = $value['long'];
                    $list['person_info'][$value['alias_name']]['lat'] = $value['lat'];
                    if ($value['address']) {
                        $list['person_info'][$value['alias_name']]['address'] = $value['address'];
                    } else {
                        $list['person_info'][$value['alias_name']]['address'] = $value['value'];
                    }
                }

                $list['person_info'][$value['alias_name']]['alias_name'] = $value['alias_name'];
                $list['person_info'][$value['alias_name']]['type'] = $value['type'];
            }elseif ($is_show){
                $list['person_info'][$key]['name'] = $value['name'];
                $list['person_info'][$key]['value'] = $value['value'];
                $list['person_info'][$key]['alias_name'] = $value['alias_name'];
                $list['person_info'][$key]['type'] = $value['type'];
                if ($value['type'] == 2) {
                    $list['person_info'][$value['alias_name']]['long'] = $value['long'];
                    $list['person_info'][$value['alias_name']]['lat'] = $value['lat'];
                    if ($value['address']) {
                        $list['person_info'][$value['alias_name']]['address'] = $value['address'];
                    } else {
                        $list['person_info'][$value['alias_name']]['address'] = $value['value'];
                    }
                }
            }
            if ($write_group_info && $group_id && $write_group_info[$group_id] && in_array($group_id, array(3,4))) {
                $gname = $write_group_info[$group_id]['gname'];
                $gsort = $write_group_info[$group_id]['gsort'];
                if (empty($group[$group_id])) {
                    $group[$group_id] = array();
                }
                $group[$group_id]['gid'] = $group_id;
                $group[$group_id]['gname'] = $gname;
                $group[$group_id]['sort'] = $gsort;
                if (empty($group[$group_id]['info'])) {
                    $group[$group_id]['info'] = array();
                }
                if ($value['type'] == 2) {
                    if (!$value['address']) {
                        $value['address'] = $value['value'];
                    }
                }
                $group[$group_id]['info'][] = $value;
            }
        }
        $gsort = array();
        foreach ($group as $val){
            $sort[] = $val['sort'];
        }
        array_multisort($sort, SORT_DESC, $group);
        $this->assign('group',$group);
        $this->assign('first_id',reset($group)['gid']);

        if ($list['score_average']) {
            $start = $list['score_average']/10 *100 +1;
        } else {
            $start = 0;
        }
        $this->assign('start',$start);
        $score = $list['score_average']/2;
        $this->assign('score',$score);
        // 处理标签信息
        $employee_memory_info = D('Employee_memory_bind')->employee_memory_info($list['id']);

        // 处理评论信息
        $employee_reply_info = D('Employee_reply')->employee_reply($list['id'], 3);

        $this->assign('employee_reply_info',$employee_reply_info);

        // 处理预约问题
        $phone = $list['phone'];
        $worker_appoint_merchant = D('Appoint')->worker_appoint_merchant($phone);
        if ($worker_appoint_merchant) {
            $is_worker_appoint = true;
        } else {
            $is_worker_appoint = false;
        }
        $this->assign('is_worker_appoint',$is_worker_appoint);

        if (!$list['business_phone']) {
            $list['business_phone'] = $phone;
        }
        // 处理是否收藏-是否点赞


        $count = count($employee_memory_info);//标签数量
        $num = ceil($count/4)*42.5;
        $this->assign('employee_memory_info',$employee_memory_info);
        $this->assign('num',$num);
        $this->assign('list',$list);
        $this->display();
    }


    /**从业人员的预约页面*/
    public function employee_appoint() {
        $employee_id = I('employee_id', 0, 'intval');
        $employee_info = M('Employee_list')->where(array('id' => $employee_id))->find();
        if (!$employee_id) {
            $this->error('缺少关键参数！');
            exit(json_encode(array('msg'=>'缺少关键参数!','status'=>0)));
        }
        $phone = M('User')->where(array('uid' => $employee_info['uid']))->getField('phone');
        $worker_appoint_merchant = D('Appoint')->worker_appoint_merchant($phone);


        $write_field = unserialize($employee_info['write_field']);
        $employee_info['write_field'] = $write_field;
        foreach ($write_field as $key => $value) {
            if($value['alias_name']){
                $employee_info['person_info'][$value['alias_name']]['name'] = $value['name'];
                if($value['type'] == 10){
                    foreach($value['value'] as $k=>$v){
                        $employee_info['person_info'][$value['alias_name']]['value'][] = replace_file_domain($v);
                    }
                }else{
                    $employee_info['person_info'][$value['alias_name']]['value'] = $value['value'];
                }

                $employee_info['person_info'][$value['alias_name']]['alias_name'] = $value['alias_name'];
                $employee_info['person_info'][$value['alias_name']]['type'] = $value['type'];
            }else{
                $employee_info['person_info'][$key]['name'] = $value['name'];
                $employee_info['person_info'][$key]['value'] = $value['value'];
                $employee_info['person_info'][$key]['alias_name'] = $value['alias_name'];
                $employee_info['person_info'][$key]['type'] = $value['type'];
            }
        }
        $img_info = $employee_info['person_info']['person_mien']['value'];
        $this->assign('img_info',$img_info);

        $this->assign('employee_info',$employee_info);
        $this->assign('worker_appoint_merchant',$worker_appoint_merchant);
        $this->display();
    }


    /**获取评论更多页面*/
    public function reply_more() {
        $employee_id = I('employee_id', 0, 'intval');
        if (!$employee_id) {
            $this->error('缺少关键参数！');
        }
        $memory_id = I('memory_id', 0, 'intval');
        $have_img = I('have_img', 0, 'intval');
        // 查询下用户从业者身份
        $employee_info = M('Employee_list')->where(array('id' => $employee_id))->find();
        if (!$employee_info || $employee_info['examine_status'] != 1) {
            if ($employee_info['examine_status'] == 2) {
                $this->error('该'.$this->config['employee_alias'].'审核未通过!');
            } elseif ($employee_info['examine_status'] == 0) {
                $this->error('该'.$this->config['employee_alias'].'待审核!');
            } else {
                $this->error('该'.$this->config['employee_alias'].'信息不存在或者已删除!');
            }
        }
        $list = D('Employee_reply')->employee_reply($employee_id, 10, $memory_id, $have_img);

        // 获取标签信息
        $info = D('Employee_reply')->reply_memory_info($employee_id);

        $this->assign('info',$info);
        $this->assign('list',$list);
        $this->display();
    }

    public function reply_more_ajax() {
        $employee_id = I('employee_id', 0, 'intval');
        if (!$employee_id) {
            exit(json_encode(array('msg'=>'缺少关键参数!','status'=>0)));
        }

        // 查询下用户从业者身份
        $employee_info = M('Employee_list')->where(array('id' => $employee_id))->find();
        if (!$employee_info || $employee_info['examine_status'] != 1) {
            if ($employee_info['examine_status'] == 2) {
                exit(json_encode(array('msg'=>'该'.$this->config['employee_alias'].'审核未通过!','status'=>0)));
            } elseif ($employee_info['examine_status'] == 0) {
                exit(json_encode(array('msg'=>'该'.$this->config['employee_alias'].'待审核!','status'=>0)));
            } else {
                exit(json_encode(array('msg'=>'该'.$this->config['employee_alias'].'信息不存在或者已删除!','status'=>0)));
            }
        }
        // 处理参数
        $type = I('get.type', '', 'strval');
        $type_id = I('get.type_id', 0, 'intval');

        $have_img = 0;
        $memory_id = 0;
        $grade = 0;
        if ($type == 'memory') {
            // 标签
            $memory_id = $type_id;
        } elseif ($type == 'have_img') {
            // 有图
            $have_img = 1;
        } elseif ($type == 'praise') {
            // 好评
            $grade = 1;
        } elseif ($type == 'medium') {
            // 中评
            $grade = 2;
        } elseif ($type == 'negative') {
            // 差评
            $grade = 3;
        }
        $list = D('Employee_reply')->employee_reply($employee_id, 10, $memory_id, $have_img, $grade);

        $info = array(
            'status'=>1,
            'info' => $list
        );
        echo json_encode($info);
    }


    /**
     * [my_detail 个人详情]
     */
    public function my_detail(){
        $uid = $this->now_user['uid'];
        $where['a.uid'] = $uid;
        $where['a.is_open'] = 1;
        $where['a.examine_status'] = 1;
        $list = M('Employee_list')
            ->alias('a')
            ->field('a.*,b.name as merchant_store_name,c.avatar,c.phone')
            ->where($where)
            ->join('left join pigcms_merchant_store as b on a.store_id=b.store_id')
            ->join('left join pigcms_user as c on a.uid=c.uid')
            ->find();
        if (empty($list) || $list['examine_status'] != 1) {
            redirect(U('Employee/index'));
        }
        if ($list['cat_id']) {
            $employee_category = M('Employee_category')->where(array('cat_id' => $list['cat_id']))->find();
            $list['cat_name'] = $employee_category['cat_name'];
        }
        if($list['employee_avatar']){
            //默认头像
            $list['avatar'] = replace_file_domain($list['employee_avatar']);
        }
        if(!$list['avatar']){
            //默认头像
            $list['avatar'] = C('config.site_url') . '/tpl/Wap/pure/static/employee/images/pic-default.png?t=001';
        }
        $write_field = unserialize($list['write_field']);
        $list['write_field'] = $write_field;
        $d_employee_category_write_field = M('employee_category_write_field');
        $write_group_info = M('Employee_write_group')->field(true)->order('gsort DESC, gid DESC')->where(array('status'=>0))->getField('gid, gname,status,gsort');
        if (empty($write_group_info)) {
            $write_group_info = D('Employee_category')->write_group_info;
        }
        foreach ($write_field as $key => $value) {
            if ($value['id'] && $value['cat_id']) {
                $group_id = $d_employee_category_write_field->where(array('id' => $value['id']))->getField('group_id');
            } elseif ($value['name'] && $value['cat_id']) {
                $group_id = $d_employee_category_write_field->where(array('name' => $value['name'], 'cat_id' => $value['cat_id']))->getField('group_id');
            } else {
                $group_id = 0;
            }
            if($value['alias_name']){
                $list['person_info'][$value['alias_name']]['name'] = $value['name'];
                if($value['type'] == 10){
                    foreach($value['value'] as $k=>$v){
                        $list['person_info'][$value['alias_name']]['value'][] = replace_file_domain($v);
                    }
                }else{
                    $list['person_info'][$value['alias_name']]['value'] = $value['value'];
                }
                if ($value['type'] == 2) {
                    $list['person_info'][$value['alias_name']]['long'] = $value['long'];
                    $list['person_info'][$value['alias_name']]['lat'] = $value['lat'];
                    if ($value['address']) {
                        $list['person_info'][$value['alias_name']]['address'] = $value['address'];
                    } else {
                        $list['person_info'][$value['alias_name']]['address'] = $value['value'];
                    }
                }

                $list['person_info'][$value['alias_name']]['alias_name'] = $value['alias_name'];
                $list['person_info'][$value['alias_name']]['type'] = $value['type'];
            }else{
                $list['person_info'][$key]['name'] = $value['name'];
                $list['person_info'][$key]['value'] = $value['value'];
                $list['person_info'][$key]['alias_name'] = $value['alias_name'];
                $list['person_info'][$key]['type'] = $value['type'];
            }
            if ($write_group_info && $group_id && $write_group_info[$group_id] && in_array($group_id, array(3,4))) {
                $gname = $write_group_info[$group_id]['gname'];
                $gsort = $write_group_info[$group_id]['gsort'];
                if (empty($group[$group_id])) {
                    $group[$group_id] = array();
                }
                $group[$group_id]['gid'] = $group_id;
                $group[$group_id]['gname'] = $gname;
                $group[$group_id]['sort'] = $gsort;
                if (empty($group[$group_id]['info'])) {
                    $group[$group_id]['info'] = array();
                }
                if ($value['type'] == 2) {
                    if (!$value['address']) {
                        $value['address'] = $value['value'];
                    }
                }
                $group[$group_id]['info'][] = $value;
            }
        }
        $sort = array();
        foreach ($group as $val){
            $sort[] = $val['sort'];
        }
        array_multisort($sort, SORT_DESC, $group);
        $this->assign('group',$group);
        $this->assign('first_id',reset($group)['gid']);

        if ($list['score_average']) {
            $start = $list['score_average']/10 *100 +1;
        } else {
            $start = 0;
        }
        $this->assign('start',$start);
        $score = $list['score_average']/2;
        $this->assign('score',$score);
        // 处理标签信息
        $employee_memory_info = D('Employee_memory_bind')->employee_memory_info($list['id']);

        // 处理评论信息
        $employee_reply_info = D('Employee_reply')->employee_reply($list['id'], 3, 13);

        $this->assign('employee_reply_info',$employee_reply_info);

        // 处理预约问题
        $phone = $list['phone'];
        $worker_appoint_merchant = D('Appoint')->worker_appoint_merchant($phone);
        if ($worker_appoint_merchant) {
            $is_worker_appoint = true;
        } else {
            $is_worker_appoint = false;
        }
        $this->assign('is_worker_appoint',$is_worker_appoint);

        if (!$list['business_phone']) {
            $list['business_phone'] = $phone;
        }


        $count = count($employee_memory_info);//标签数量
        $num = ceil($count/4)*42.5;
        $this->assign('employee_memory_info',$employee_memory_info);
        $this->assign('num',$num);
        $this->assign('list',$list);
        $this->display();
    }

    //点赞
    public function ajax_zan(){
        $thumb_model =M('Employee_thumb_bind');
        $uid = $this->now_user['uid'];
        $data['uid'] = $uid;
        $data['thumb_id'] = $_POST['thumb_id'];
        $data['add_time'] = time();
        $info = M('Employee_list')->where(array('id'=>$data['thumb_id']))->find();
        if (!$info) {
            exit(json_encode(array('msg'=>'点赞对象不存在或者已删除!','status'=>0)));
        }
        if ($info['uid'] == $uid) {
            exit(json_encode(array('msg'=>'自己不可点赞!','status'=>0)));
        }
        $result = $thumb_model->where(array('uid'=>$data['uid'],'thumb_id'=>$data['thumb_id']))->find();
        if($result){
            exit(json_encode(array('msg'=>'已赞!','status'=>0)));
        }

        $res = $thumb_model->add($data);
        if($res){
            $count  = $thumb_model->where(array('thumb_id'=>$data['thumb_id']))->count();
            M('Employee_list')->where(array('id'=>$data['thumb_id']))->setInc('thumb_num',1);
            exit(json_encode(array('msg'=>'点赞成功!','status'=>1,'count'=>$count)));
        }else{
            exit(json_encode(array('msg'=>'点赞失败,请重试!','status'=>0)));
        }
    }

    //收藏
    public function ajax_collect(){
        $collect_model =M('Employee_collect_bind');
        $uid = $this->now_user['uid'];
        $data['uid'] = $uid;
        $data['collect_id'] = $_POST['collect_id'];
        $data['add_time'] = time();
        $info = M('Employee_list')->where(array('id'=>$data['collect_id']))->find();
        if (!$info) {
            exit(json_encode(array('msg'=>'收藏对象不存在或者已删除!','status'=>0)));
        }
        if ($info['uid'] == $uid) {
            exit(json_encode(array('msg'=>'自己不可收藏!','status'=>0)));
        }
        $result = $collect_model->where(array('uid'=>$data['uid'],'collect_id'=>$data['collect_id']))->find();
        if($result){
            $collect_model->where(array('uid'=>$data['uid'],'collect_id'=>$data['collect_id']))->delete();
            $count  = $collect_model->where(array('collect_id'=>$data['collect_id']))->count();
            M('Employee_list')->where(array('id'=>$data['collect_id']))->setDec('collect_num',1);
            exit(json_encode(array('msg'=>'已取消收藏!','status'=>2,'count'=>$count)));
        }
        $res = $collect_model->add($data);
        if($res){
            $count  = $collect_model->where(array('collect_id'=>$data['collect_id']))->count();
            M('Employee_list')->where(array('id'=>$data['collect_id']))->setInc('collect_num',1);
            exit(json_encode(array('msg'=>'收藏成功!','status'=>1,'count'=>$count)));
        }else{
            exit(json_encode(array('msg'=>'收藏失败,请重试!','status'=>0)));
        }
    }

    //店铺主页
    public function shop(){
        $store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;

        $store = M('Merchant_store')->field(true)->where(array('store_id' => $store_id))->find();
        $is_claim_store = $store && $store['mer_id']>0 ? 0 : 1;


        if (empty($store)) {
            $this->error_tips('不存在的店铺');
            exit;
        } else {
            $store['business_time'] = '';
            $store['is_close']      = 1;
            if ($store['open_1'] == '00:00:00' && $store['close_1'] == '00:00:00') {
                $store['is_close']      = 0;
                $store['business_time'] = '24小时营业';
            } else {
                $now_time = time();
                $store['business_time'] = substr($store['open_1'], 0, -3) . '~' . substr($store['close_1'], 0, -3);
                $open_1 = strtotime(date('Y-m-d') . $store['open_1']);
                $close_1 = strtotime(date('Y-m-d') . $store['close_1']);
                if ($open_1 >= $close_1) {
                    $close_1 += 86400;
                }
                if ($open_1 < $now_time && $now_time < $close_1) {
                    $store['is_close'] = 0;
                }
                if ($store['open_2'] != '00:00:00' || $store['close_2'] != '00:00:00') {
                    $store['business_time'] .= ';' . substr($store['open_2'], 0, -3) . '~' . substr($store['close_2'], 0, -3);
                    $open_2 = strtotime(date('Y-m-d') . $store['open_2']);
                    $close_2 = strtotime(date('Y-m-d') . $store['close_2']);
                    if ($open_2 >= $close_2) {
                        $close_2 += 86400;
                    }
                    if ($open_2 < $now_time && $now_time < $close_2) {
                        $store['is_close'] = 0;
                    }
                }
                if ($store['open_3'] != '00:00:00' || $store['close_3'] != '00:00:00') {
                    $store['business_time'] .= ';' . substr($store['open_3'], 0, -3) . '~' . substr($store['close_3'], 0, -3);
                    $open_3 = strtotime(date('Y-m-d') . $store['open_3']);
                    $close_3 = strtotime(date('Y-m-d') . $store['close_3']);
                    if ($open_3 >= $close_3) {
                        $close_3 += 86400;
                    }
                    if ($open_3 < $now_time && $now_time < $close_3) {
                        $store['is_close'] = 0;
                    }
                }
            }
        }


        $store_image_class   = new store_image();
        $images              = $store_image_class->get_allImage_by_path($store['pic_info']);
        $store['image_list'] = $images;
        $store['pic_str']   = implode(',', $images);
        $store['image']      = $images ? array_shift($images) : array();
        if (empty($store['image']) && $is_claim_store>0) {
            $store['image'] = $this->config['site_url'] . '/tpl/Wap/pure/static/employee/images/claim_store.png?t=001';
        }

        if ($is_claim_store<=0 || $store['mer_id'] > 0) {
            $merchant = M('Merchant')->field(true)->where(array('mer_id' => $store['mer_id']))->find();
            if (empty($merchant)) {
                $this->error_tips('不存在的商家');
                exit;
            }

            $card_info   = D('Card_new')->get_card_by_mer_id($store['mer_id']);
            $coupon_list = D('Card_new_coupon')->get_coupon_list_by_type_merid('store', $store['mer_id'], 0, 5, -1);

            $this->assign('card_info', $card_info);
            $this->assign('coupon_list', $coupon_list);
        }

        $now_time          = time();
        $sql               = 'SELECT * FROM ' . C('DB_PREFIX') . 'group_store AS gs INNER JOIN ' . C('DB_PREFIX') . 'group AS g ON g.group_id=gs.group_id WHERE gs.store_id=' . $store_id . ' AND `g`.`status`=1  AND `g`.`type`=1 AND `g`.`end_time`>\'' . $now_time . '\' ORDER BY `g`.`sort` DESC,`g`.`group_id` DESC';
        $groups            = D()->query($sql);
        $group_image_class = new group_image();
        foreach ($groups as $row) {
            $tmp_pic_arr     = explode(';', $row['pic']);
            $row['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0], 's');
            $row['url']      = U('Group/detail', array('pin_num' => $row['pin_num'], 'group_id' => $row['group_id']), true, false, true);

            $row['price']     = floatval($row['price']);
            $row['old_price'] = floatval($row['old_price']);
            $row['wx_cheap']  = floatval($row['wx_cheap']);
            $row['is_start']  = 1;
            $row['pin_num']   = $row['pin_num'];
            if ($now_time < $row['begin_time']) {
                $row['is_start'] = 0;
            }
            if ($row['begin_time'] + 864000 > time() && $row['sale_count'] == 0) {
                $row['sale_txt'] = '新品上架';
            } elseif ($row['begin_time'] + 864000 < time() && $row['sale_count'] == 0) {
                $row['sale_txt'] = '';
            } else {
                $row['sale_txt'] = '已售' . floatval($row['sale_count'] + $row['virtual_num']);
            }
            $row['begin_time']        = date("Y-m-d H:i:s", $row['begin_time']);
            $store['group_list'][] = $row;
        }
        if ($store['is_takeout'] && ($shop = M('Merchant_store_shop')->field(true)->where(array('store_id' => $store_id))->find())) {
            $store['is_takeout'] = 1;
        } else {
            $store['is_takeout'] = 0;
        }
        $this->assign('store', $store);

        $reply_list = D('Reply')->get_reply_list($store_id, 4, 1, 3);

        $reply_count = D('Reply')->where(array('status' => array('lt', 2), 'parent_id' => $store_id, 'order_type' => 4))->count();
//      echo '<pre/>';
//      print_r($reply_list);die;
        $this->assign('reply_list', $reply_list);
        $this->assign('merchant', $merchant);
        $this->assign('reply_count', $reply_count);

        $collection_info = D('Merchant_user_relation')->get_foodshop_collect($this->user_session['uid'],intval($_GET['store_id']));
        if ($collection_info) {
            $this->assign('collection_info', $collection_info);
        }

        //==================
        if ($is_claim_store>0) {
            $tmp_wap_index_slider = array();
        } else {
            $tmp_wap_index_slider = M('Store_slider')->field('url,pic,name')->where(array('status' => 1, 'store_id' => $store_id))->order('sort DESC')->select();
        }

        foreach($tmp_wap_index_slider as &$vs){
            $vs['pic'] = file_domain().'/upload/slider/'.$vs['pic'];
        }
        if ($store['is_book'] && $store['mer_id'] > 0) {
            $default_slider[] = array(
                    'url'  => U('Foodshop/book_order', array('store_id' => $store_id)),
                    'pic'  => $this->static_path . 'images/book.png',
                    'name' => '订桌点餐'
            );
        }
        if ($store['is_queue'] && $store['mer_id'] > 0) {

            $default_slider[] = array(
                    'url'  => U('Foodshop/queue', array('store_id' => $store_id)),
                    'pic'  => $this->static_path . 'images/xqym_05.png',
                    'name' => '排号'
            );
        }
        if ($this->config['pay_in_store'] && $store['mer_id'] > 0) {

            $default_slider[] = array(
                    'url'  => U('My/pay', array('store_id' => $store_id)),
                    'pic'  => $this->static_path . 'images/xqym_07.png',
                    'name' => $this->config['cash_alias_name']
            );
        }
        if ($store['is_takeout'] && $store['mer_id'] > 0) {
            $default_slider[] = array(
                    'url'  => U('Shop/index') . '#shop-' . $store_id,
                    'pic'  => $this->static_path . 'images/xqym_09.png',
                    'name' => $this->config['shop_alias_name']
            );
        }
        if ($card_info && $card_info['self_get'] == 1 && $store['mer_id']>0){
            $default_slider[] = array(
                    'url'  => U('My_card/merchant_card', array('mer_id' => $store['mer_id'])),
                    'pic'  => $this->static_path . 'images/merchant_card.png',
                    'name' => '会员卡'
            );
        }


        $wap_index_slider = array();
        if($tmp_wap_index_slider){

            $tmp_wap_index_slider = array_merge($default_slider,$tmp_wap_index_slider);
        }else{
            $tmp_wap_index_slider = $default_slider;
        }

        //if(count($tmp_wap_index_slider) >= 10 &&$this->config['wap_slider_number'] == 10){
            $wap_slider_number = $this->config['wap_slider_number'];
        //}else{
        //  $wap_slider_number = 8;
        //}
        $slider_num =0;
        foreach($tmp_wap_index_slider as $key=>$value){
            $tmp_i = floor($key/$wap_slider_number);
            $wap_index_slider[$tmp_i][] = $value;
            $slider_num ++;
        }

        // 获取一下对应团队风采
        $employee_info = M('Employee_list')->alias('a')
            ->join('LEFT JOIN pigcms_user AS b ON a.uid=b.uid')
            ->field('a.*, b.nickname, b.avatar,d.cat_name')
            ->join('LEFT JOIN pigcms_employee_category as d on a.cat_id=d.cat_id')
            ->where(array('store_id'=>$store_id,'examine_status' => 1, 'is_open' => 1))->select();
        $employee_index_info = array();
        $m_employee_category = M('Employee_category');
        if ($employee_info) {
            foreach ($employee_info as $key => &$val) {
                if ($val['write_field']){
                    $val['write_field'] = unserialize($val['write_field']);
                    if ($val['write_field']) {
                        foreach ($val['write_field'] as $v) {
                            if ($v['alias_name'] == 'working_years') {
                                $val['working_years'] = $v['value'];
                            } elseif ($v['name'] == '从业年限') {
                                $val['working_years'] = $v['value'];
                            } elseif ($v['alias_name'] == 'nickname') {
                                $val['nickname'] = $v['value'];
                            }elseif ($v['name'] == '对外称呼') {
                                $val['nickname'] = $v['value'];
                            }
                        }
                    }
                }
                // 获取三级分类信息
                if ($val['cat_id']) {
                    $val['cat_name'] = $m_employee_category->where(array('cat_id' => $val['cat_id']))->getField('cat_name');
                } else {
                    $val['cat_name'] = '';
                }
                if ($val && !$val['avatar']) {
                    // 头像没有赋予默认头像
                    $val['avatar'] = C('config.site_url') . '/tpl/Wap/pure/static/employee/images/pic-default.png?t=001';
                }
                $val['url'] = C('config.site_url')  . '/wap.php?g=Wap&c=Employee&a=employee_detail&employee_id='.$val['id'];
                $info_i = floor($key/4);
                $employee_index_info[$info_i][] = $val;
            }
        }

        $this->assign('employee_number',4);
        $this->assign('employee_index_info',$employee_index_info);
        $this->assign('employee_count',count($employee_info));

        $this->assign('slider_num',$slider_num);
        $this->assign('isnew',D('User')->check_new($_SESSION['user']['uid'],'shop'));
        $this->assign('wap_index_slider',$wap_index_slider);
        $this->assign('wap_index_slider_number',$wap_slider_number);
        //==================

        $this->display();
    }


    /**
     * 回复评论， 文字回复
     */
    public function employee_reply_comment() {
        $uid = $this->now_user['uid'];
        $data['uid'] = $uid;
        $reply_id = I('reply_id', 0, 'intval');
        if (empty($reply_id)) {
            $this->error_tips('缺少评论对象！');
        }
        $m_employee_reply = M('Employee_reply');
        $comment_info = $m_employee_reply->where(array('id' => $reply_id))->find();
        if (empty($comment_info) || $comment_info['status'] == 1) {
            $this->error_tips('评论对象不存在或者已经被删除！');
        }
        $data['reply_id'] = $reply_id;
        $data['reply_uid'] = $comment_info['reply_uid'];

        // 做扩展-如果回复了回复 记录p_id
        $p_id = I('p_id', 0, 'intval');
        $data['p_id'] = $p_id;

        $reply_content = I('reply_content', '', 'htmlspecialchars');
        if (empty($reply_content)) {
            $this->error_tips('请填写评论内容！');
        }
        $data['reply_content'] = $reply_content;
        $m_employee_reply_info = M('Employee_reply_info');
        $data['add_time'] = time();
        $reply = $m_employee_reply_info->data($data)->add();
        if ($reply) {
            $m_employee_reply->where(array('id' => $reply_id))->setInc('reply_num');
            $this->error_tips('评论成功！');
        } else {
            $this->error_tips('评论失败，请重试~！');
        }

    }

    /**
     * 获取相关评论回复信息
     */
    public function employee_reply_comment_list(){
        $reply_id = I('reply_id', 0, 'intval');
        if (empty($reply_id)) {
            $this->error_tips('缺少评论对象！');
        }
        $m_employee_reply = M('Employee_reply');
        $comment_info = $m_employee_reply->where(array('id' => $reply_id))->find();
        if (empty($comment_info) || $comment_info['status'] == 1) {
            $this->error_tips('评论对象不存在或者已经被删除！');
        }
        $comment_user_info = M('User')->where(array('uid' => $comment_info['reply_uid']))->field('nickname, avatar')->find();
        if ($comment_user_info) {
            $comment_info['nickname'] = $comment_user_info['nickname'];
            $comment_info['avatar'] = $comment_user_info['avatar'];
        }

        $d_employee_memory_bind = D('Employee_memory_bind');
        if ($comment_info['image']){
            $comment_info['image'] = unserialize($comment_info['image']);
            $comment_info['image'] = D('Employee_reply')->reply_image($comment_info['image']);
        }
        $comment_info['memory_info'] = $d_employee_memory_bind->reply_memory($comment_info['id']);
        $comment_info['add_time_info'] = date('Y-m-d H:i:s', $comment_info['add_time']);

        $comment_info['start'] = $comment_info['score_num']/10 *100 +1;
        if ($comment_info && !$comment_info['avatar']) {
            // 头像没有赋予默认头像
            $comment_info['avatar'] = C('config.site_url') . '/tpl/Wap/pure/static/employee/images/pic-default.png?t=001';
        }



        // 回复对象 的 评论信息
        $this->assign('comment_info',$comment_info);

        // 评论回复列表
        $uid = $this->now_user['uid'];
        $employee_reply_comment_list = D('Employee_reply_info')->employee_reply_info($reply_id, 10, -1, $uid);
        $this->assign('employee_reply_comment_list',$employee_reply_comment_list);


        $this->display();
    }

    /**
     * 获取相关评论回复信息ajax
     */
    public function employee_reply_comment_ajax(){
        $reply_id = I('reply_id', 0, 'intval');
        if (empty($reply_id)) {
            $this->error_tips('缺少评论对象！');
        }
        $m_employee_reply = M('Employee_reply');
        $comment_info = $m_employee_reply->where(array('id' => $reply_id))->find();
        if (empty($comment_info) || $comment_info['status'] == 1) {
            $this->error_tips('评论对象不存在或者已经被删除！');
        }

        // 评论回复列表
        $uid = $this->now_user['uid'];
        $employee_reply_comment_list = D('Employee_reply_info')->employee_reply_info($reply_id, 10, -1, $uid);

        $list = array(
            'employee_reply_comment_list' => $employee_reply_comment_list,
        );
        $info = array(
            'status'=>1,
            'info' => $list
        );
        echo json_encode($info);
    }

    /**删除评论回复-（只能自己删除自己的-假删除）*/
    public function reply_comment_del() {
        $uid = $this->now_user['uid'];
        $reply_info_id = I('id', 0, 'intval');
        if (empty($reply_info_id)) {
            exit(json_encode(array('msg'=>'缺少删除对象！','status'=>0)));
        }
        $m_employee_reply_info = M('Employee_reply_info');
        $reply_info = $m_employee_reply_info->where(array('id' => $reply_info_id))->find();
        if (empty($reply_info) || $reply_info['status'] == 1) {
            exit(json_encode(array('msg'=>'评论不存在或者已经被删除','status'=>0)));
        }
        if ($reply_info['uid'] != $uid) {
            exit(json_encode(array('msg'=>'只能删除自己的评论','status'=>0)));
        }
        $reply = $m_employee_reply_info->where(array('id' => $reply_info_id))->data(array('status' => 1))->save();
        if ($reply) {
            exit(json_encode(array('msg'=>'删除成功!','status'=>1)));
        } else {
            exit(json_encode(array('msg'=>'删除失败，请重试~','status'=>0)));
        }

    }

    /** 添加店铺页面 */
    public function store_add(){

        $catList = D('Merchant_category')->lists();
        $info = array();
        $info['catList'] = $catList;
        $this->assign('info',$info);
        $this->display();
    }

    /**添加店铺*/
    public function store_modify(){
        if(IS_POST){
            $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
            if(empty($_POST['name'])){
                exit(json_encode(array('errorMsg'=>'店铺名称必填!','errorCode'=>1)));
            }
            if(empty($_POST['phone'])){
                exit(json_encode(array('errorMsg'=>'联系电话必填!','errorCode'=>1)));
            }
            if(empty($_POST['long_lat'])){
                exit(json_encode(array('errorMsg'=>'店铺经纬度必填!','errorCode'=>1)));
            }
            if(empty($_POST['adress'])){
                exit(json_encode(array('errorMsg'=>'店铺地址必填!','errorCode'=>1)));
            }
            if(empty($_POST['txt_info'])){
                exit(json_encode(array('errorMsg'=>'请输入店铺描述信息!','errorCode'=>1)));
            }
            $long_lat = explode(',',$_POST['long_lat']);
            $_POST['long'] = trim($long_lat[0]);
            $_POST['lat'] = trim($long_lat[1]);

            if ($_POST['lat'] && $_POST['long']) {
                $area_info = D('Area')->cityMatching($_POST['lat'], $_POST['long']);
                if ($area_info && $area_info['area_info']) {
                    $_POST['province_id'] = $area_info['area_info']['province_id'];
                    $_POST['city_id'] = $area_info['area_info']['city_id'];
                    $_POST['area_id'] = $area_info['area_info']['area_id'];
                    if ($area_info['address_name']) {
                        $_POST['adress_long_lat'] = $area_info['address_name'];
                    }elseif ($area_info['area_info']['province_name'] || $area_info['area_info']['city_name'] || $area_info['area_info']['area_name']) {
                        $_POST['adress_long_lat'] = $area_info['area_info']['province_name'] . $area_info['area_info']['city_name'] . $area_info['area_info']['area_name'];
                    }
                }
            }

            $_POST['last_time'] = $_SERVER['REQUEST_TIME'];
            $_POST['add_from'] = 2;
            $_POST['open_1'] = isset($_POST['open_1']) && $_POST['open_1'] ? $_POST['open_1'] : '00:00';
            $_POST['close_1'] = isset($_POST['close_1']) && $_POST['close_1'] ? $_POST['close_1'] : '00:00';
            $_POST['open_2'] = isset($_POST['open_2']) && $_POST['open_2'] ? $_POST['open_2'] : '00:00';
            $_POST['close_2'] = isset($_POST['close_2']) && $_POST['close_2'] ? $_POST['close_2'] : '00:00';
            $_POST['open_3'] = isset($_POST['open_3']) && $_POST['open_3'] ? $_POST['open_3'] : '00:00';
            $_POST['close_3'] = isset($_POST['close_3']) && $_POST['close_3'] ? $_POST['close_3'] : '00:00';
            $_POST['circle_id'] = isset($_POST['circle_id']) ? intval($_POST['circle_id']) : 0;
            $uid = $this->now_user['uid'];
            $_POST['add_uid'] = $uid;

            // 处理状态问题 如果管理了用户添加店铺审核，添加的店铺自动审核通过 主要控制是否出现在待认领列表及是否可以被认领
            if ($this->config['examine_employee_claim_store'] == 1) {
                $_POST['claim_status'] = 1;
            } else {
                $_POST['claim_status'] = 2;
            }

            $database_merchant_store = D('Merchant_store');

            if ($store_id) {
                $condition_store['store_id'] = $store_id;
                $store = $database_merchant_store->where($condition_store)->find();
                if ($store) {
                    if ($store['is_claim'] != 1) {
                        exit(json_encode(array('errorMsg'=>'您不能修改该店铺信息~','errorCode'=>1)));
                    }
                    if ($store['add_uid'] != $uid) {
                        exit(json_encode(array('errorMsg'=>'您不能修改该店铺信息~','errorCode'=>1)));
                    }
                    if($database_merchant_store->where($condition_store)->data($_POST)->save()){
                        $this->returnCode(0);
                    } else {
                        exit(json_encode(array('errorMsg'=>'保存失败！您是不是没做过修改？请重试~','errorCode'=>1)));
                    }
                } else {
                    exit(json_encode(array('errorMsg'=>'该店铺不存在或者已经被删除~','errorCode'=>1)));
                }
            }else {
                $_POST['is_claim'] = 1;
                $_POST['claim_from'] = 1;
                $_POST['status'] = 6;
                if($insert_id=$database_merchant_store->data($_POST)->add()){
                    // 添加且自动审核通过下，添加用户积分
                    if ($_POST['status'] == 1 && $this->config['employee_recommend_store_get_score'] == 1 && $this->config['employee_recommend_score'] > 0) {
                        D('User')->add_score($uid, intval($this->config['employee_recommend_score']), '用户添加推荐店铺信息，获得积分');
                        $database_merchant_store->where(array('store_id' => $insert_id))->data(array('score_give' => intval($this->config['employee_recommend_score'])))->save();
                    }
                    $this->returnCode(0, array('store_id'=>$insert_id, 'url' => U('Employee/claim_store', array('store_id' => $insert_id))));
                }else{
                    exit(json_encode(array('errorMsg'=>'添加失败!','errorCode'=>1)));
                }
            }
        }else{
            exit(json_encode(array('errorMsg'=>'非法提交,请重新提交~','errorCode'=>1)));
        }
    }

    /**获取城市相关信息*/
    public function cityList(){
        $area_list = M('Area')->field('`area_id`,`area_pid`,`area_name`')->where(array('is_open'=>'1','area_type'=>array('lt','4')))->order('`area_type` ASC')->select();

        $tmp_area_list = array();
        foreach($area_list as $key=>$value){
            $tmp_area_list[$value['area_pid']][] = $value;
        }

        $new_area_list = $tmp_area_list[0];

        foreach($new_area_list as $key=>$value){
            if(empty($tmp_area_list[$value['area_id']])){
                unset($new_area_list[$key]);
            }else{
                $new_area_list[$key]['children'] = $tmp_area_list[$value['area_id']];
                $tmp_new_area_list[] = array('children'=>$tmp_area_list[$value['area_id']],'area_id'=>$value['area_id'],'area_name'=>$value['area_name']);
            }
        }



        foreach($tmp_new_area_list as $key=>$value){
            $tmp_new_area_list[$key]['children'] = array();
            foreach($value['children'] as $k=>$v){
                if(empty($tmp_area_list[$v['area_id']])){
                    unset($tmp_new_area_list[$key]['children'][$k]);
                }else{
                    // $tmp_new_area_list[$key]['children'][$k]['children'] = $tmp_area_list[$v['area_id']];

                    $tmp_new_area_list[$key]['children'][] = array('children'=>$tmp_area_list[$v['area_id']],'area_id'=>$v['area_id'],'area_name'=>$v['area_name']);
                }
            }
        }


        $returnAreaList = array();
        foreach($tmp_new_area_list as $key=>&$value){
            $value['value'] = $value['area_id'];
            $value['text'] = $value['area_name'];
            unset($value['area_id'],$value['area_name'],$value['area_pid']);
            foreach($value['children'] as $k=>&$v){
                $v['value'] = $v['area_id'];
                $v['text'] = $v['area_name'];
                unset($v['area_id'],$v['area_name'],$v['area_pid']);
                foreach($v['children'] as $kk=>&$vv){
                    $vv['value'] = $vv['area_id'];
                    $vv['text'] = $vv['area_name'];
                    unset($vv['area_id'],$vv['area_name'],$vv['area_pid']);
                }
            }
        }

        $this->returnCode(0,$tmp_new_area_list);
    }
    
    /**获取子信息*/
    private function children($list)
    {
        if (empty($list)) {
            return $list;
        }
        $result = array();
        foreach ($list as $l) {
            $value = array();
            $value['value'] = $l['area_id'];
            $value['text'] = $l['area_name'];
            $value['children'] = $this->children($l['children']);
            $result[] = $value;
        }
        return $result;
    }
    /**获取商圈信息*/
    public function ajaxCircle()
    {
        $condition_area = array();
        $condition_area['area_pid'] = intval($_POST['id']);
        $condition_area['is_open'] = 1;
        $result = D('Area')->field('`area_id`,`area_name`')->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();
        $this->returnCode(0, $result);
    }


    /**待认领和认领的店铺列表*/
    public function employee_claim_store_list(){
        //判断分类信息
        $cat_url = !empty($_GET['cat_url']) ? $_GET['cat_url'] : '';
        $this->assign('now_cat_url', $cat_url);

        //判断地区信息
        $area_url = !empty($_GET['area_url']) ? $_GET['area_url'] : '';
        $this->assign('now_area_url',$area_url);


        if(!empty($area_url)){
            $tmp_area = D('Area')->get_area_by_areaUrl($area_url);
            if(empty($tmp_area)){
                $this->error('当前区域不存在！');
            }
            $this->assign('now_area', $tmp_area);

            if ($tmp_area['area_type'] == 3) {
                $now_area = $tmp_area;
            } else {
                $now_circle = $tmp_area;
                $this->assign('now_circle', $now_circle);
                $now_area = D('Area')->get_area_by_areaId($tmp_area['area_pid'], true, $cat_url);
                if (empty($tmp_area)) {
                    $this->error('当前区域不存在！');
                }
            }
            $this->assign('top_area', $now_area);
        }

        //判断排序信息
        $sort_id = !empty($_GET['sort_id']) ? $_GET['sort_id'] : 'juli';

        $long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);	//获取用户的经度和纬度
        if(empty($long_lat) || empty($long_lat['long']) || empty($long_lat['lat'])){
            $sort_id = $sort_id == 'juli' ? 'defaults' : $sort_id;
            $sort_array = array(
                array('sort_id'=>'defaults','sort_value'=>'默认排序'),
                array('sort_id'=>'start','sort_value'=>'最新发布'),
                array('sort_id'=>'solds','sort_value'=>'人气最高'),
            );
        } else {
            $sort_array = array(
                array('sort_id'=>'juli','sort_value'=>'离我最近'),
                array('sort_id'=>'start','sort_value'=>'最新发布'),
                array('sort_id'=>'solds','sort_value'=>'人气最高'),
            );
            $this->assign('long_lat',$long_lat);
        }
        foreach($sort_array as $key=>$value){
            if($sort_id == $value['sort_id']){
                $now_sort_array = $value;
                break;
            }
        }
        $this->assign('sort_array',$sort_array);
        $this->assign('now_sort_array',$now_sort_array);

        //所有分类 包含2级分类
        $all_category_list = D('Merchant_category')->get_all_category();
        $this->assign('all_category_list',$all_category_list);

        //根据分类信息获取分类
        if(!empty($cat_url)){
            $now_category = D('Merchant_category')->get_category_by_catUrl($cat_url);
            if(empty($now_category)){
                $this->error_tips('此分类不存在！');
            }
            $this->assign('now_category',$now_category);

            if(!empty($now_category['cat_fid'])){
                $f_category = D('Merchant_category')->get_category_by_id($now_category['cat_fid']);
                $this->assign('top_category',$f_category);
            }else{
                $this->assign('top_category',$now_category);
            }
        }

        $all_area_list = D('Area')->get_all_area_list();
        $this->assign('all_area_list',$all_area_list);

        /* 粉丝行为分析 */
        $this->behavior(array('model'=>'Employee_store_list'));

        $this->display();
    }


    /**ajax获取店铺列表*/
    public function employee_claim_store_list_ajaxList(){
        $this->header_json();
        //判断分类信息
        $cat_url = !empty($_GET['cat_url']) ? $_GET['cat_url'] : '';

        //判断地区信息
        $area_url = !empty($_GET['area_url']) ? $_GET['area_url'] : '';
        $circle_id = 0;
        if(!empty($area_url)){
            $tmp_area = D('Area')->get_area_by_areaUrl($area_url);
            $tmp_area_id	=	$tmp_area['area_id'];
            if(empty($tmp_area)){
                $this->error('当前区域不存在！');
            }

            if ($tmp_area['area_type'] == 3) {
                $now_area = $tmp_area;
            } else {
                $now_circle = $tmp_area;
                $now_area = D('Area')->get_area_by_areaId($tmp_area['area_pid'], true, $cat_url);
                if (empty($tmp_area)) {
                    $this->error('当前区域不存在！');
                }
                $circle_id = $now_circle['area_id'];
            }
            $area_id = $now_area['area_id'];
        }else{
            $area_id = 0;
        }
        //判断商圈分类信息
        $default_circle_id=I('get.default_circle_id','','intval');
        if($default_circle_id>0){
            $circle_id=$default_circle_id;
        }
        //判断排序信息
        $sort_id = !empty($_GET['sort_id']) ? $_GET['sort_id'] : 'juli';
        $long_lat = D('User_long_lat')->getLocation($_SESSION['openid'],0);
        if(empty($long_lat) || empty($long_lat['long']) || empty($long_lat['lat'])){
            $sort_id = $sort_id == 'juli' ? 'defaults' : $sort_id;
        }
        if($_GET['page'] == 1){
            if(empty($long_lat) || empty($long_lat['long']) || empty($long_lat['lat'])){
                $condition_field = "`img`,`area_id`,`introduce`,`market_name`,`market_id`, ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$long_lat['lat']}*PI()/180-`lat`*PI()/180)/2),2)+COS({$long_lat['lat']}*PI()/180)*COS(`lat`*PI()/180)*POW(SIN(({$long_lat['long']}*PI()/180-`long`*PI()/180)/2),2)))*1000) AS juli";
            }else{
                $condition_field = "`img`,`area_id`,`introduce`,`market_name`,`market_id`";
            }
            if($tmp_area_id){
                $area_market	=	M('Area_market')->field($condition_field)->order('market_sort desc')->where(array('area_id'=>$tmp_area_id,'is_open'=>1))->limit(3)->select();
            }else{
//				$area_market	=	M('Area_market')->field($condition_field)->order('market_sort desc')->where(array('city_id'=>C('config.now_city'),'is_hot'=>1,'is_open'=>1))->limit(3)->select();
            }
            foreach($area_market as $k=>$v){
                $area_market[$k]['img']		=	replace_file_domain($v['img']);
                $area_market[$k]['count']	=	M('merchant_store')->where(array('market_id'=>$v['market_id'],'status'=>1))->count();
                if($v['juli']){
                    $area_market[$k]['range_txt']	=	$this->wapFriendRange($v['juli']);
                }else{
                    $area_market[$k]['range_txt']	=	'';
                }
                $area_market[$k]['url']	=	U('Merchant/store_market_list',array('market_id'=>$v['market_id']));
            }
        }

        //根据分类信息获取分类
        $employee_cat_url = '';
        $cat_fid = 0;
        $cat_id = 0;
        if(!empty($cat_url)){
            $now_category = D('Merchant_category')->get_category_by_catUrl($cat_url);
            if(empty($now_category)){
                $this->error_tips('此分类不存在！');
            }

            if(!empty($now_category['cat_fid'])){
                $cat_fid = 0;
                $cat_id = $now_category['cat_id'];
            }else{

                $cat_fid = $now_category['cat_id'];
                $cat_id = 0;
            }
        }

        $return = D('Employee_claim_store')->employee_claim_store_list($cat_id,$cat_fid,$area_id,$sort_id, $long_lat['lat'], $long_lat['long'], $circle_id,$_GET['w']);

        $m_merchant_category = M('Merchant_category');
        $m_area = M('Area');
        if ($return['store_list']) {
            foreach($return['store_list'] as &$storeValue){
                //得到友好的距离
                if($long_lat&&$long_lat['long']&&$long_lat['lat']&&($sort_id=='defaults'||$sort_id=='juli')){
                    $storeValue['range_txt'] = $this->wapFriendRange($storeValue['juli']);
                }else{
                    $storeValue['range_txt'] =	'';
                }
                // 从业者和店铺切换显示 获取分类和商圈
                $storeValue['classification'] = M(_view('Merchant_category'))->where(array('cat_id' => $storeValue['cat_id']))->getField('cat_name');
                $storeValue['circle_info'] = $m_area->where(array('area_id' => $storeValue['circle_id']))->getField('area_name');

            }
        } else {
            $return['store_list'] = array();
        }
        $return['market_list'] = isset($area_market)?$area_market:array();
        $return['img']	=	array(
            'group_alias_name'	=>	substr($this->config['group_alias_name'],0,3),
            'waimai_alias_name'	=>	substr($this->config['waimai_alias_name'],0,3),
            'meal_alias_name'	=>	substr($this->config['meal_alias_name'],0,3),
        );
        echo json_encode($return);
        /* 粉丝行为分析 */
        $this->behavior(array('model'=>'employee_claim_store'));
    }


    /**店铺主页*/
    public function claim_store() {
        $now_store = D('Employee_claim_store')->get_store_by_storeId($_GET['store_id']);
        if(empty($now_store)){
            $this->error_tips('该店铺不存在！');
        }
        $now_store['business_time'] = '';
        $now_store['is_close']      = 1;
        if ($now_store['open_1'] == '00:00:00' && $now_store['close_1'] == '00:00:00') {
            $now_store['is_close']      = 0;
            $now_store['business_time'] = '24小时营业';
        } else {
            $now_time = time();
            $now_store['business_time'] = substr($now_store['open_1'], 0, -3) . '~' . substr($now_store['close_1'], 0, -3);
            $open_1 = strtotime(date('Y-m-d') . $now_store['open_1']);
            $close_1 = strtotime(date('Y-m-d') . $now_store['close_1']);
            if ($open_1 >= $close_1) {
                $close_1 += 86400;
            }
            if ($open_1 < $now_time && $now_time < $close_1) {
                $now_store['is_close'] = 0;
            }
            if ($now_store['open_2'] != '00:00:00' || $now_store['close_2'] != '00:00:00') {
                $now_store['business_time'] .= ';' . substr($now_store['open_2'], 0, -3) . '~' . substr($now_store['close_2'], 0, -3);
                $open_2 = strtotime(date('Y-m-d') . $now_store['open_2']);
                $close_2 = strtotime(date('Y-m-d') . $now_store['close_2']);
                if ($open_2 >= $close_2) {
                    $close_2 += 86400;
                }
                if ($open_2 < $now_time && $now_time < $close_2) {
                    $now_store['is_close'] = 0;
                }
            }
            if ($now_store['open_3'] != '00:00:00' || $now_store['close_3'] != '00:00:00') {
                $now_store['business_time'] .= ';' . substr($now_store['open_3'], 0, -3) . '~' . substr($now_store['close_3'], 0, -3);
                $open_3 = strtotime(date('Y-m-d') . $now_store['open_3']);
                $close_3 = strtotime(date('Y-m-d') . $now_store['close_3']);
                if ($open_3 >= $close_3) {
                    $close_3 += 86400;
                }
                if ($open_3 < $now_time && $now_time < $close_3) {
                    $now_store['is_close'] = 0;
                }
            }
        }
        //得到当前店铺的评分
        if ($now_store['mer_id'] >0) {
            $store_score = D('Merchant_score')->field('`score_all`,`reply_count`')->where(array('parent_id'=>$now_store['claim_store_id'],'type'=>'2'))->find();
            $this->assign('store_score',$store_score);

            if(!empty($this->user_session)){
                $database_user_collect = D('User_collect');
                $condition_user_collect['type'] = 'group_shop';
                $condition_user_collect['id'] = $now_store['claim_store_id'];
                $condition_user_collect['uid'] = $this->user_session['uid'];
                if($database_user_collect->where($condition_user_collect)->find()){
                    $now_store['is_collect'] = true;
                }
            }
            //	快店
            $now_store['wap_url'] = U('Food/shop',array('mer_id'=>$now_store['mer_id'],'store_id'=>$now_store['claim_store_id']));
            $store_group_list = D('Group')->get_store_group_list($now_store['store_id'],0,true);
            $this->assign('store_group_list',$store_group_list);

            //	预约
            if($this->config['appoint_page_row']){
                $now_appoint	=	D('Appoint')->get_appointlist_by_StoreId($now_store['claim_store_id'],true);
                $this->assign('now_appoint',$now_appoint);
            }
            /* 粉丝行为分析 */
            D('Merchant_request')->add_request($now_store['mer_id'],array('group_hits'=>1));
            /*人气自增*/
            M('Merchant_store')->where(array('store_id'=>$now_store['claim_store_id']))->setInc('hits');
            /* 粉丝行为分析 */
            $this->behavior(array('mer_id'=>$now_store['mer_id'],'store_id'=>$now_store['claim_store_id']));

            if ($_SESSION['openid'] && $services = D('Customer_service')->where(array('mer_id' => $now_store['mer_id']))->select()) {
                $key = $this->get_encrypt_key(array('app_id'=>$this->config['im_appid'],'openid' => $_SESSION['openid']), $this->config['im_appkey']);
                $kf_url = ($this->config['im_url'] ? $this->config['im_url'] : 'http://im-link.weihubao.com').'/?app_id=' . $this->config['im_appid'] . '&openid=' . $_SESSION['openid'] . '&key=' . $key . '#serviceList_' . $now_store['mer_id'];
                $this->assign('kf_url', $kf_url);
            }

            $this->assign('is_wap', M('Classify')->field(true)->where(array('token' => $now_store['mer_id']))->find());
        }
        $this->assign('now_store',$now_store);
        $this->display();
    }


    /**推广海报*/
    // 获取推广海报信息
    public function employee_spread_hb(){
        if(empty($this->now_user['openid'])){
            $this->error_tips("你没有绑定微信，无法生成推广二维码");
        }
        $share_type = trim($_GET['share_type']);
        if(empty($share_type)){
            $this->error_tips("请上传正确的推广对象");
        }
        $this->assign('uid',$this->now_user['uid']);
        $spread = M('Employee_user_spread_qrcode');
        $spread_info = $spread->where(array('uid'=>$this->now_user['uid'], 'share_type' => $share_type))->find();
        $now_time = time();

        $employee_info = M('Employee_list')->where(array('uid'=> $this->now_user['uid']))->field('id, examine_status, is_open')->find();
        if(empty($employee_info)){
            $this->error_tips("您还没有入驻" . $this->config['employee_alias']);
        }
        if($employee_info['examine_status'] == 0){
            $this->error_tips("您还入驻申请还未审核通过");
        }
        if($employee_info['examine_status'] == 2){
            $this->error_tips("您还入驻申请已经审核拒绝");
        }
        if($employee_info['is_open'] == 0){
            $this->error_tips("您入驻信息已经被关闭，请联系管理员！");
        }
        $employee_id = $employee_info['id'];

        if(!empty($spread_info)){
            if($spread_info['qrcode_type']==0&&$now_time>strtotime(date('Y-m-d',$spread_info['last_time']))+86400){
                if ($share_type == 'normal') {
                    $spread_info['url'] .= $employee_id;
                }
                $this->get_spread_qrcode($spread_info['url'],$this->now_user['uid'], $share_type, $employee_id);
            }
        } else {
            $employee_platform = M('Employee_platform');
            $key_info ='';
            if ($share_type == 'normal') {
                $key_info = 'normal_employee_extension';
            } elseif ($share_type == 'friend') {
                $key_info = 'friend_employee_extension';
            } elseif ($share_type == 'unit_manager') {
                $key_info = 'unit_manager_employee_extension';
            } else {
                $this->error_tips("请上传正确的推广对象");
            }
            $employee_platform_url = $employee_platform->where(array('key' => $key_info, 'share_type' => $share_type))->getField('url');
            if ($share_type == 'normal') {
                $employee_platform_url .= $employee_id;
            }
            $this->get_spread_qrcode($employee_platform_url,$this->now_user['uid'], $share_type, $employee_id);
        }

        $uid = $this->now_user['uid'];

        $where['uid'] = $uid;
        $spread_info = $spread->where($where)->find();
        $setting_where['status'] = 1;
        $setting_where['type'] = 1;
        $setting_where['share_type'] = array('in', array($share_type, 'all'));
        $res = D('Employee_promote_setting')->where($setting_where)->select();
        $key = array_rand($res,1);
        $promote = $res[$key];
        $promote['qrcode'] = $spread_info['ticket'];
        $image_url = D('Employee_promote_setting')->createImage($promote,  $spread_info['ticket'], $this->now_user, '');
        $this->assign('image',$image_url);
        $this->display();
    }
    private function get_spread_qrcode($spread_url='',$uid=0,$share_type = 'normal', $employee_id=0,$is_func=false){
        $_POST['url'] = empty($spread_url)?$_POST['url']:$spread_url;
        $_POST['uid'] = empty($uid)?$_POST['uid']:$this->now_user['uid'];
        $url = $_POST['url'];

        $url_info = parse_url($url);
        $n = preg_match('/(.*\.)?(\w+\.\w+)$/',$url_info['host'], $matches);

        if(!strpos($_POST['url'], $matches[2])&&empty($spread_url)){
            echo json_encode(array('error_code'=>1,'msg'=>'您输入的域名有误！'));exit;
        }
        $spread = M('Employee_user_spread_qrcode');
        if ($share_type == 'normal') {
            $qrcode_id =500000000+$_POST['uid'];
        } elseif ($share_type == 'friend') {
            $qrcode_id =510000000+$_POST['uid'];
        } elseif ($share_type == 'unit_manager') {
            $qrcode_id =520000000+$_POST['uid'];
        } else {
            return false;
        }
        if($this->config['user_spread_qrcode_tmp']){
            $date['qrcode_type']=0;
            $res = D('Recognition')->get_tmp_qrcode($qrcode_id);
        }else{
            $date['qrcode_type']=1;
            $res = D('Recognition')->get_new_qrcode('employee_spread_' . $share_type,$qrcode_id);
            $res['ticket']=$res['qrcode'];
        }
        if($is_func){
            return $res['ticket'];
        }
        $date['qrcode_id']=$qrcode_id;
        if(strpos($url,'://')){
            $date['url']=$url;
        }else{
            $date['url']='http://'.$url;
        }
        if(!strpos($url,'openid')) {
            if (strpos($url, '?')) {
                $date['url'] .= "&openid=" . $this->now_user['openid'];
            } else {
                $date['url'] .= "?openid=" . $this->now_user['openid'];
            }
        }
        $date['url'] = html_entity_decode($date['url']);
        $date['ticket']=$res['ticket'];
        $date['last_time']=time();
        $date['share_type']=$share_type;
        $date['employee_id']=$employee_id;
        $where['uid']=$_POST['uid'];
        $where['share_type']=$share_type;
        if($result=$spread->where($where)->find()){
            $spread->where($where)->save($date);
        }else{
            $date['create_time']=time();
            $date['uid']=$_POST['uid'];
            $id = $spread->data($date)->add();
        }
        if(!empty($result)){
            $id = $result['id'];
        }
        $res['qrcode_type']=$date['qrcode_type'];
        if(empty($spread_url)){
            echo json_encode(array('error_code'=>0,'msg'=>$res,'id'=>$id));exit;
        }
    }




    /**用户添加的待审核和审核通过的店铺列表*/
    public function employee_add_store_list(){
        //判断分类信息
        $cat_url = !empty($_GET['cat_url']) ? $_GET['cat_url'] : '';
        $this->assign('now_cat_url', $cat_url);

        //判断地区信息
        $area_url = !empty($_GET['area_url']) ? $_GET['area_url'] : '';
        $this->assign('now_area_url',$area_url);


        if(!empty($area_url)){
            $tmp_area = D('Area')->get_area_by_areaUrl($area_url);
            if(empty($tmp_area)){
                $this->error('当前区域不存在！');
            }
            $this->assign('now_area', $tmp_area);

            if ($tmp_area['area_type'] == 3) {
                $now_area = $tmp_area;
            } else {
                $now_circle = $tmp_area;
                $this->assign('now_circle', $now_circle);
                $now_area = D('Area')->get_area_by_areaId($tmp_area['area_pid'], true, $cat_url);
                if (empty($tmp_area)) {
                    $this->error('当前区域不存在！');
                }
            }
            $this->assign('top_area', $now_area);
        }

        //判断排序信息
        $sort_id = !empty($_GET['sort_id']) ? $_GET['sort_id'] : 'juli';

        $long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);	//获取用户的经度和纬度
        if(empty($long_lat) || empty($long_lat['long']) || empty($long_lat['lat'])){
            $sort_id = $sort_id == 'juli' ? 'defaults' : $sort_id;
            $sort_array = array(
                array('sort_id'=>'defaults','sort_value'=>'默认排序'),
                array('sort_id'=>'start','sort_value'=>'最新发布'),
                array('sort_id'=>'solds','sort_value'=>'人气最高'),
            );
        } else {
            $sort_array = array(
                array('sort_id'=>'juli','sort_value'=>'离我最近'),
                array('sort_id'=>'start','sort_value'=>'最新发布'),
                array('sort_id'=>'solds','sort_value'=>'人气最高'),
            );
            $this->assign('long_lat',$long_lat);
        }
        foreach($sort_array as $key=>$value){
            if($sort_id == $value['sort_id']){
                $now_sort_array = $value;
                break;
            }
        }
        $this->assign('sort_array',$sort_array);
        $this->assign('now_sort_array',$now_sort_array);

        //所有分类 包含2级分类
        $all_category_list = D('Merchant_category')->get_all_category();
        $this->assign('all_category_list',$all_category_list);

        //根据分类信息获取分类
        if(!empty($cat_url)){
            $now_category = D('Merchant_category')->get_category_by_catUrl($cat_url);
            if(empty($now_category)){
                $this->error_tips('此分类不存在！');
            }
            $this->assign('now_category',$now_category);

            if(!empty($now_category['cat_fid'])){
                $f_category = D('Merchant_category')->get_category_by_id($now_category['cat_fid']);
                $this->assign('top_category',$f_category);
            }else{
                $this->assign('top_category',$now_category);
            }
        }

        $all_area_list = D('Area')->get_all_area_list();
        $this->assign('all_area_list',$all_area_list);

        /* 粉丝行为分析 */
        $this->behavior(array('model'=>'Employee_store_list'));

        $this->display();
    }


    /**ajax获取店铺列表*/
    public function employee_add_store_list_ajaxList(){
        $this->header_json();
        $uid = $this->now_user['uid'];
        //判断分类信息
        $cat_url = !empty($_GET['cat_url']) ? $_GET['cat_url'] : '';

        //判断地区信息
        $area_url = !empty($_GET['area_url']) ? $_GET['area_url'] : '';
        $circle_id = 0;
        if(!empty($area_url)){
            $tmp_area = D('Area')->get_area_by_areaUrl($area_url);
            $tmp_area_id	=	$tmp_area['area_id'];
            if(empty($tmp_area)){
                $this->error('当前区域不存在！');
            }

            if ($tmp_area['area_type'] == 3) {
                $now_area = $tmp_area;
            } else {
                $now_circle = $tmp_area;
                $now_area = D('Area')->get_area_by_areaId($tmp_area['area_pid'], true, $cat_url);
                if (empty($tmp_area)) {
                    $this->error('当前区域不存在！');
                }
                $circle_id = $now_circle['area_id'];
            }
            $area_id = $now_area['area_id'];
        }else{
            $area_id = 0;
        }
        //判断商圈分类信息
        $default_circle_id=I('get.default_circle_id','','intval');
        if($default_circle_id>0){
            $circle_id=$default_circle_id;
        }
        //判断排序信息
        $sort_id = !empty($_GET['sort_id']) ? $_GET['sort_id'] : 'juli';
        $long_lat = D('User_long_lat')->getLocation($_SESSION['openid'],0);
        if(empty($long_lat) || empty($long_lat['long']) || empty($long_lat['lat'])){
            $sort_id = $sort_id == 'juli' ? 'defaults' : $sort_id;
        }
        if($_GET['page'] == 1){
            if(empty($long_lat) || empty($long_lat['long']) || empty($long_lat['lat'])){
                $condition_field = "`img`,`area_id`,`introduce`,`market_name`,`market_id`, ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$long_lat['lat']}*PI()/180-`lat`*PI()/180)/2),2)+COS({$long_lat['lat']}*PI()/180)*COS(`lat`*PI()/180)*POW(SIN(({$long_lat['long']}*PI()/180-`long`*PI()/180)/2),2)))*1000) AS juli";
            }else{
                $condition_field = "`img`,`area_id`,`introduce`,`market_name`,`market_id`";
            }
            if($tmp_area_id){
                $area_market	=	M('Area_market')->field($condition_field)->order('market_sort desc')->where(array('area_id'=>$tmp_area_id,'is_open'=>1))->limit(3)->select();
            }else{
//				$area_market	=	M('Area_market')->field($condition_field)->order('market_sort desc')->where(array('city_id'=>C('config.now_city'),'is_hot'=>1,'is_open'=>1))->limit(3)->select();
            }
            foreach($area_market as $k=>$v){
                $area_market[$k]['img']		=	replace_file_domain($v['img']);
                $area_market[$k]['count']	=	M('merchant_store')->where(array('market_id'=>$v['market_id'],'status'=>1))->count();
                if($v['juli']){
                    $area_market[$k]['range_txt']	=	$this->wapFriendRange($v['juli']);
                }else{
                    $area_market[$k]['range_txt']	=	'';
                }
                $area_market[$k]['url']	=	U('Merchant/store_market_list',array('market_id'=>$v['market_id']));
            }
        }

        //根据分类信息获取分类
        $employee_cat_url = '';
        $cat_fid = 0;
        $cat_id = 0;
        if(!empty($cat_url)){
            $now_category = D('Merchant_category')->get_category_by_catUrl($cat_url);
            if(empty($now_category)){
                $this->error_tips('此分类不存在！');
            }

            if(!empty($now_category['cat_fid'])){
                $cat_fid = 0;
                $cat_id = $now_category['cat_id'];
            }else{

                $cat_fid = $now_category['cat_id'];
                $cat_id = 0;
            }
        }

        $return = D('Employee_claim_store')->employee_add_store_list($uid, $cat_id,$cat_fid,$area_id,$sort_id, $long_lat['lat'], $long_lat['long'], $circle_id,$_GET['w']);

        $m_merchant_category = M('Merchant_category');
        $m_area = M('Area');
        if ($return['store_list']) {
            foreach($return['store_list'] as &$storeValue){
                //得到友好的距离
                if($long_lat&&$long_lat['long']&&$long_lat['lat']&&($sort_id=='defaults'||$sort_id=='juli')){
                    $storeValue['range_txt'] = $this->wapFriendRange($storeValue['juli']);
                }else{
                    $storeValue['range_txt'] =	'';
                }
                // 从业者和店铺切换显示 获取分类和商圈
                $storeValue['classification'] = M(_view('Merchant_category'))->where(array('cat_id' => $storeValue['cat_id']))->getField('cat_name');
                $storeValue['circle_info'] = $m_area->where(array('area_id' => $storeValue['circle_id']))->getField('area_name');

            }
        } else {
            $return['store_list'] = array();
        }
        $return['market_list'] = isset($area_market)?$area_market:array();
        $return['img']	=	array(
            'group_alias_name'	=>	substr($this->config['group_alias_name'],0,3),
            'waimai_alias_name'	=>	substr($this->config['waimai_alias_name'],0,3),
            'meal_alias_name'	=>	substr($this->config['meal_alias_name'],0,3),
        );
        echo json_encode($return);
        /* 粉丝行为分析 */
        $this->behavior(array('model'=>'employee_claim_store'));
    }

    public function test() {
        $catList = D('Merchant_category')->lists();
        $info = array();
        $info['catList'] = $catList;
        dump($info);
    }

}