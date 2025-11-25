<?php
/*
    小区角色管理
 */
class RoleAction extends BaseAction{
    protected $village_id;

    public function _initialize(){
        parent::_initialize();
        $this->village_id = $this->house_session['village_id'];
    }
  

    //角色列表
    public function role_list(){
        //角色管理-查看 权限
        if (!in_array(8, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $database_house_admin = D('House_admin');
        $where = array();
        $where['village_id'] = $this->village_id;
        $where['wid'] = 0;
        $list = $database_house_admin->get_limit_list_page($where);
        $this->assign('list',$list);
        $this->display();
    }

    //添加角色
    public function role_add(){
        $this->assign('bg_color', '#F3F3F3');

        //角色管理-添加 权限
        if (!in_array(9, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $whereArr=array('village_id'=>$this->village_id,'xtype'=>array('in',array('order_refund_check','service_time_check')));
        $order_refund_check=D('House_village_checkauth_set')->get_one_set($whereArr);
        $is_order_refund_check_open=0;
        if(!empty($order_refund_check)){
            $is_order_refund_check_open=$order_refund_check['is_open'];
        }
        $database_house_admin = D('House_admin');
        if (IS_POST) {
            //验证账号是否存在
            if (!$_POST['account']) {
                $this->error('请输入账号');
            }
            if (!$_POST['pwd']) {
                $this->error('请输入密码');
            }
            $role_info = $database_house_admin->get_info_one(array('account'=>$_POST['account']));
            $village_info = D('House_village')->where(array('account'=>$_POST['account']))->find();
            if ($role_info || $village_info) {
                $this->error('该账号已存在，请重新输入账号');
            }
            $data = array();
            $data['village_id'] = $this->village_id;
            $data['account'] = $_POST['account'];
            $data['pwd'] = md5($_POST['pwd']);
            $data['realname'] = $_POST['realname'];
            $data['phone'] = $_POST['phone'];
            // $data['email'] = $_POST['email'];
            $data['remarks'] = $_POST['remarks'];
            $data['group_id'] = $_POST['group_id'];
            $data['time'] = $_SERVER['REQUEST_TIME'];
            if(isset($_POST['wid']) && !empty($_POST['wid'])){
                $data['wid']= intval($_POST['wid']);
            }elseif ($is_order_refund_check_open>0){
                $this->error('开启了修改物业服务时间或者订单作废、退款审核，必须从组织架构中添加管理员！');
            }
            if ($data['group_id']>0) {
                $data['menus'] = '';
            }else{
                if (is_array($_POST['menus']) && in_array(194,$_POST['menus'])) {
                    // 勾选了我的应用即勾选了 功能应用库
                    $_POST['menus'][] = 430;
                }
                $data['menus'] = $_POST['menus'] ? implode(',',$_POST['menus']) : '';
            }
            $res = $database_house_admin->data($data)->add();
            if ($res) {
                $this->success('添加成功',U('role_list'));
            }else{
                $this->error('添加失败');
            }
        }else{
            $d_house_menu_new = D('House_menu_new');
            $house_session_menus = implode(',',$this->house_session['menus']);
            $house_session_menus = $d_house_menu_new->repair_menus_info($house_session_menus);
            $where = [];
            $where['id'] = ['in', $house_session_menus];
            $where['status'] = 1;
            $menus = D('House_menu_new')->where($where)->order('sort DESC,id asc')->select();
            //处理套餐购买功能应用 start
            $property_id = D('House_village')->where(array('village_id'=>$this->village_id))->getField('property_id');
            $package_order_info =  D('PrivilegePackage')->disposeAuth($menus,$property_id);
            $menus = $package_order_info['list'];
            $application_id = $package_order_info['application_id'];
            //处理套餐购买功能应用 end
            $list = $this->getTrees($menus,$application_id);
            $this->assign('menus', $list);

            //权限分组
            $is_system = $this->house_session['is_system'] ? $this->house_session['is_system'] : 0;
            $group_list = D('House_admin_group')->get_limit_list_page(array('village_id'=>$this->village_id),0);
            foreach ($group_list['list'] as $key => $value) {
                // 过滤权限高于当前管理员的权限分组
                $group_diff_menu = array_diff(explode(',',$value['group_menus']), $this->house_session['menus']);
                if ( (!isset($group_diff_menu[0]) || $group_diff_menu[0]) && $group_diff_menu && $is_system<=0) { // 大于当前管理员权限
                    unset($group_list['list'][$key]);
                }
            }
            $this->assign('group_list', $group_list['list']);
            $this->assign('is_order_refund_check_open', $is_order_refund_check_open);

            $this->display();
        }
    }

    /**
     * Notes: 处理树 购买功能套餐处理
     * @param $array
     * @param $application_id
     * @return array
     * @author: weili
     * @datetime: 2020/9/28 17:58
     */
    public function getTrees($array,$application_id=[]){
        //第一步 构造数据
        $items = array();
        foreach($array as $value){
            if ($value['id']==430) {
                continue;
            } elseif($value['id']==194) {
                $value['name'] = '功能应用库';
            }
            $items[$value['id']] = $value;
        }
        //第二部 遍历数据 生成树状结构
        $tree = array();
        foreach($items as $key => $value){
            if(isset($items[$value['fid']])){
                $items[$value['fid']]['child'][] = &$items[$key];
                if (isset($items[$value['fid']]['count'])) {
                    $items[$value['fid']]['count']++;
                }else{
                    $items[$value['fid']]['count'] = 1;
                }
            }
//            else{
//                $tree[] = &$items[$key];
//            }
            //处理套餐购买功能应用 start
            if($value['fid'] == 0)
            {
                if($application_id && !in_array($value['application_id'],$application_id))
                {
                    unset($items[$value['fid']]);
                    unset($items[$key]);
                }else{
                    $tree[] = &$items[$key];
                }
            }
            //处理套餐购买功能应用 end
        }
        return $tree;
    }
    //编辑角色
    public function role_edit(){
        //角色管理-查看 权限
        if (!in_array(10, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $id = $_GET['id'] + 0;
        if (!$id) {
            $this->error('参数不对');
        }
        $this->assign('id',$id);

        $database_house_admin = D('House_admin');
        //角色信息
        $condition['id'] = $id;
        $condition['village_id'] = $this->village_id;
        $role = $database_house_admin->get_info_one($condition);
        if (empty($role)) {
           $this->error('数据库中没有查询到该管理员的信息！');
        }
        $d_house_menu_new = D('House_menu_new');
        $role['menus'] = explode(',', $role['menus']);
        $role['menus'] = $d_house_menu_new->repair_menus_info($role['menus']);
        $this->assign('role', $role);

        $where = '`status`=1';

        //权限分组
        $group_list = D('House_admin_group')->get_limit_list_page(array('village_id'=>$this->village_id),0);

        // 判断角色权限是否小于当前管理员权限
        $diff_menu = array_diff($role['menus'], $this->house_session['menus']);
        if ( (!isset($diff_menu[0]) || $diff_menu[0]) && $diff_menu ) { // 大于当前管理员权限
            //权限信息
        }else{
            $where .= ' AND `id` in ('.implode(',',$this->house_session['menus']).')';
            foreach ($group_list['list'] as $key => $value) {
                // 过滤权限高于当前管理员的权限分组
                $group_diff_menu = array_diff(explode(',',$value['group_menus']), $this->house_session['menus']);
                if ( (!isset($group_diff_menu[0]) || $group_diff_menu[0]) && $group_diff_menu ) { // 大于当前管理员权限
                    unset($group_list['list'][$key]);
                }
            }
        }
        $menus = D('House_menu_new')->where($where)->order('sort DESC,id asc')->select();
        //处理套餐购买功能应用 start
        $property_id = D('House_village')->where(array('village_id'=>$this->village_id))->getField('property_id');
        $package_order_info =  D('PrivilegePackage')->disposeAuth($menus,$property_id);
        $menus = $package_order_info['list'];
        $application_id = $package_order_info['application_id'];
        //处理套餐购买功能应用 end
        $list = $this->getTrees($menus,$application_id);

        $this->assign('menus', $list);
        $this->assign('group_list', $group_list['list']);

        $is_system = $this->house_session['is_system'] ? $this->house_session['is_system'] : 0;
        if (IS_POST) {
            //角色管理-编辑 权限
            if (!in_array(10, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }
            //todo 去掉权限对应id为430的功能应用库
            if($diff_menu){
                if ($search_key=array_search(430,$diff_menu)){
                    unset($diff_menu[$search_key]);
                    if(!empty($diff_menu)){
                        $diff_menu=array_values($diff_menu);
                    }
                }
            }
            if ( (!isset($diff_menu[0]) || $diff_menu[0]) && $diff_menu && $is_system<=0) { // 大于当前管理员权限
                $this->error('对不起，您没有权限编辑此账号');
            }
            //验证账号是否存在
            if (!$_POST['account']) {
                $this->error('请输入账号');
            }
            $is_exist = $database_house_admin->get_info_one(array('account'=>$_POST['account']));
            if ($is_exist&& $is_exist['id']!=$id) {
                $this->error('该账号已存在，请重新输入账号');
            }
            //2020/10/20 start  小区物业工作人员
//            $house_worker_info = D('House_worker')->where(['account'=>$_POST['account']])->find();
//            if($house_worker_info){
//                $this->error('账号已存在！');
//            }
            //2020/10/20 end
            $data = array();
            $data['account'] = $_POST['account'];
            if ($_POST['pwd']) {
                $data['pwd'] = md5($_POST['pwd']);
            }
            $data['realname'] = $_POST['realname'];
            $data['phone'] = $_POST['phone'];
            // $data['email'] = $_POST['email'];
            $data['remarks'] = $_POST['remarks'];
            $data['group_id'] = $_POST['group_id'];
            if(isset($_POST['wid']) && !empty($_POST['wid'])){
                $data['wid']= intval($_POST['wid']);
            }
            if ($data['group_id']>0) {
                $data['menus'] = '';
            }else{
                if (is_array($_POST['menus']) && in_array(194,$_POST['menus'])) {
                    // 勾选了我的应用即勾选了 功能应用库
                    $_POST['menus'][] = 430;
                }
                $data['menus'] = $_POST['menus'] ? implode(',',$_POST['menus']) : '';
            }
            $res = $database_house_admin->where($condition)->data($data)->save();
            if ($res!==false) {
                $this->success('编辑成功',U('role_list'));
            }else{
                $this->error('编辑失败');
            }
        }else{
            $this->display();
        }
    } 

    //角色删除
    public function role_del(){
        //角色管理-删除 权限
        if (!in_array(11, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $id = $_GET['id'] + 0;
        if ($id) {
            $database_house_admin = D('House_admin');
            //角色信息
            $condition['id'] = $id;
            $condition['village_id'] = $this->village_id;
            $role = $database_house_admin->get_info_one($condition);
            if (empty($role)) {
               $this->error('数据库中没有查询到该账号的信息！');
            }
            $role['menus'] = explode(',', $role['menus']);

            // 判断角色权限是否小于当前管理员权限
            $is_system = $this->house_session['is_system'] ? $this->house_session['is_system'] : 0;
            $diff_menu = array_diff($role['menus'], $this->house_session['menus']);
            if ( (!isset($diff_menu[0]) || $diff_menu[0]) && $diff_menu && $is_system<-0) { // 大于当前管理员权限
               $this->error('对不起，您没有权限删除此账号');
            }

            // 不能删除自己
            if ( $id ==  $this->house_session['role_id'] ) { 
               $this->error('不能删除自己的账号');
            }

            $result = D('House_admin')->where(array('id'=>$id))->delete();
            if($result){
                $this->success('删除成功');
            }else{
                $this->error('删除失败');
            }
        }
    } 

    //导出管理员
    public function role_export(){
        //权限管理-导出管理员 权限
        if (!in_array(270, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $now_village = D('House_village')->get_one($this->village_id);
        $title = $now_village['village_name'] . '小区-管理员列表';
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        if($this->village_id){
            $where_condition['village_id'] = $this->village_id;
            $count = D('House_admin')->where($where_condition)->count();
            $length = ceil($count / 1000);
            for ($i = 0; $i < $length; $i++) {
                $i && $objExcel->createSheet();
                $objExcel->setActiveSheetIndex($i);

                $objExcel->getActiveSheet()->setTitle('第' . ($i+1) . '个一千管理员');
                $objActSheet = $objExcel->getActiveSheet();
                $objActSheet->setCellValue('A1', C('config.house_name').'名称');
                $objActSheet->setCellValue('B1', '账号');
                $objActSheet->setCellValue('C1', '姓名');
                $objActSheet->setCellValue('D1', '手机号');
                $objActSheet->setCellValue('E1', '备注');
                $objActSheet->setCellValue('F1', '权限分组');
                $objActSheet->setCellValue('G1', '父级权限');
                $objActSheet->setCellValue('H1', '子级权限');
              
                $result = D('House_admin')->get_limit_list_page($where_condition , ($i+1)*1000 , true);
                if (!empty($result['list'])) {
                    $index = 2;
                    foreach ($result['list'] as $value) {
                        $objExcel->getActiveSheet()->getStyle('A'. $index. ':G'. $index)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                        $objExcel->getActiveSheet()->getStyle('H'.$index)->getAlignment()->setWrapText(true);//设置换行
                        // $objExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);//宽度自适应
                        // $objExcel->getActiveSheet()->getDefaultColumnDimension('G')->setWidth(1200);//设置全部宽度
                        $objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(1200);
                        $objActSheet->setCellValueExplicit('A' . $index, $now_village['village_name']);
                        $objActSheet->setCellValueExplicit('B' . $index, $value['account']);
                        $objActSheet->setCellValueExplicit('C' . $index, $value['realname']);
                        $objActSheet->setCellValueExplicit('D' . $index, $value['phone']);
                        $objActSheet->setCellValueExplicit('E' . $index, $value['remarks']);
                        $objActSheet->setCellValueExplicit('F' . $index, $value['name']);

                        if ($value['group_id']) {
                            $menus = D('House_menu_new')->where(array('id'=>array('in',$value['group_menus'])))->select();
                        }else{
                            $menus = D('House_menu_new')->where(array('id'=>array('in',$value['menus'])))->select();
                        }
                        $menus_tree = $this->getTree($menus);
                        if ($menus) {
                            foreach ($menus_tree as $_k => $_menu) {
                                if ($_k==0) {
                                    $objActSheet->setCellValueExplicit('G' . $index, $_menu['name']);
                                }else{
                                    $objExcel->getActiveSheet()->getStyle('A'. $index. ':G'. $index)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                                    $objExcel->getActiveSheet()->getStyle('H'.$index)->getAlignment()->setWrapText(true);//设置换行
                                    $objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(1200);
                                    $objActSheet->setCellValueExplicit('A' . $index, '');
                                    $objActSheet->setCellValueExplicit('B' . $index, '');
                                    $objActSheet->setCellValueExplicit('C' . $index, '');
                                    $objActSheet->setCellValueExplicit('D' . $index, '');
                                    $objActSheet->setCellValueExplicit('E' . $index, '');
                                    $objActSheet->setCellValueExplicit('F' . $index, '');
                                    $objActSheet->setCellValueExplicit('G' . $index, $_menu['name']);
                                }
                                $child = '';
                                foreach ($_menu['child'] as  $_child) {
                                    $child .= '【'.$_child['name'].'】';
                                    if ($_child['child']) {
                                        foreach ($_child['child'] as $_child2) {
                                            $child .= $_child2['name'].'，';
                                        }
                                        $child = trim($child,'，');
                                    }
                                    $child .= "\r\n";
                                }
                                $objActSheet->setCellValueExplicit('H' . $index, $child);
                                $index++;
                            }
                        }else{
                           $index++; 
                        }

                        // $menus_name = '';
                        // if ($menus) {
                        //     foreach ($menus as $key => $value) {
                        //         $menus_name .= $value['name'] . '，';
                        //     }
                        //     $menus_name = trim($menus_name,'，');
                        // }
                        // $objActSheet->setCellValueExplicit('G' . $index, $menus_name);
                        
                    }
                }
                sleep(2);
            }
        }

        //输出
        ob_end_clean();
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
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

    //权限分组列表
    public function group_list(){
        //角色管理-查看 权限
        if (!in_array(271, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $database_house_admin_group = D('House_admin_group');
        $where = array();
        $where['village_id'] = $this->village_id;
        $list = $database_house_admin_group->get_limit_list_page($where);
        $this->assign('list',$list);
        $this->display();
    }

    //添加分组
    public function group_add(){
        //角色管理-添加 权限
        if (!in_array(272, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $database_house_admin_group = D('House_admin_group');
        if (IS_POST) {
            $data = array();
            $data['village_id'] = $this->village_id;
            $data['name'] = $_POST['name'];
            $data['remarks'] = $_POST['remarks'];
            // $data['status'] = $_POST['status'];
            $data['time'] = $_SERVER['REQUEST_TIME'];
            $data['group_menus'] = $_POST['menus'] ? implode(',',$_POST['menus']) : ''; 

            if (!$_POST['name']) {
                $this->error('请输入分组名称');
            }

            $res = $database_house_admin_group->data($data)->add();
            if ($res) {
                $this->success('添加成功',U('group_list'));
            }else{
                $this->error('添加失败');
            }
        }else{
            $d_house_menu_new = D('House_menu_new');
            $house_session_menus = implode(',',$this->house_session['menus']);
            $house_session_menus = $d_house_menu_new->repair_menus_info($house_session_menus);
            $where = [];
            $where['id'] = ['in', $house_session_menus];
            $where['status'] = 1;

            $menus = D('House_menu_new')->where($where)->order('sort DESC,id asc')->select();
            $list = $this->getTree($menus);
            $this->assign('menus', $list);

            $this->display();
        }
    } 

    //编辑分组
    public function group_edit(){
        //角色管理-查看 权限
        if (!in_array(273, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $id = $_GET['group_id'] + 0;
        if (!$id) {
            $this->error('参数不对');
        }
        $this->assign('id',$id);

        $database_house_admin_group = D('House_admin_group');
        //角色信息
        $condition['group_id'] = $id;
        $condition['village_id'] = $this->village_id;
        $role = $database_house_admin_group->get_info_one($condition);
        if (empty($role)) {
           $this->error('数据库中没有查询到该管理员的信息！');
        }
        $d_house_menu_new = D('House_menu_new');
        $role['menus'] = explode(',', $role['group_menus']);
        $role['menus'] = $d_house_menu_new->repair_menus_info($role['menus']);
        $this->assign('role', $role);

        $where = '`status`=1';

        $is_system = $this->house_session['is_system'] ? $this->house_session['is_system'] : 0;
        // 判断角色权限是否小于当前管理员权限
        $diff_menu = array_diff($role['menus'], $this->house_session['menus']);
        if ( (!isset($diff_menu[0]) || $diff_menu[0]) && $diff_menu  && $is_system<=0) { // 大于当前管理员权限
            //权限信息
        }else{
            $where .= ' AND `id` in ('.implode(',',$this->house_session['menus']).')';
        }
        $menus = $d_house_menu_new->where($where)->order('sort DESC,id asc')->select();
        $list = $this->getTree($menus);
        $this->assign('menus', $list);

        if (IS_POST) {
            //角色管理-编辑 权限
            if (!in_array(273, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }
            if ( (!isset($diff_menu[0]) || $diff_menu[0]) && $diff_menu && $is_system<=0) { // 大于当前管理员权限
                $this->error('对不起，您没有权限编辑此分组');
            }
  
            $data = array();
            $data['name'] = $_POST['name'];
            $data['remarks'] = $_POST['remarks'];
            // $data['status'] = $_POST['status'];
            $data['group_menus'] = $_POST['menus'] ? implode(',',$_POST['menus']) : '';

            if (!$_POST['name']) {
                $this->error('请输入分组名称');
            }

            $res = $database_house_admin_group->where($condition)->data($data)->save();
            if ($res!==false) {
                $this->success('编辑成功',U('group_list'));
            }else{
                $this->error('编辑失败');
            }
        }else{
            $this->display();
        }
    } 

    //删除分组
    public function group_del(){
        //角色管理-删除 权限
        if (!in_array(274, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $id = $_GET['group_id'] + 0;
        if ($id) {
            $database_house_admin_group = D('House_admin_group');
            //角色信息
            $condition['group_id'] = $id;
            $condition['village_id'] = $this->village_id;
            $role = $database_house_admin_group->get_info_one($condition);
            if (empty($role)) {
               $this->error('数据库中没有查询到该账号的信息！');
            }
            $role['menus'] = explode(',', $role['group_menus']);

            // 判断角色权限是否小于当前管理员权限
            $is_system = $this->house_session['is_system'] ? $this->house_session['is_system'] : 0;
            $diff_menu = array_diff($role['menus'], $this->house_session['menus']);
            if ( (!isset($diff_menu[0]) || $diff_menu[0]) && $diff_menu && $is_system<=0) { // 大于当前管理员权限
               $this->error('对不起，您没有权限删除此账号');
            }

            $result = $database_house_admin_group->where(array('group_id'=>$id))->delete();
            if($result){
                $this->success('删除成功');
            }else{
                $this->error('删除失败');
            }
        }else{
            $this->error('参数错误');
        }
    }

    //导出管理员分组
    public function group_export(){
        //物业对账-导出 权限
        if (!in_array(275, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $now_village = D('House_village')->get_one($this->village_id);
        $title = $now_village['village_name'] . '小区-权限分组列表';
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        if($this->village_id){
            $where_condition['village_id'] = $this->village_id;
            $count = D('House_admin')->where($where_condition)->count();
            $length = ceil($count / 1000);
            for ($i = 0; $i < $length; $i++) {
                $i && $objExcel->createSheet();
                $objExcel->setActiveSheetIndex($i);

                $objExcel->getActiveSheet()->setTitle('第' . ($i+1) . '个一千权限分组');
                $objActSheet = $objExcel->getActiveSheet();
                $objActSheet->setCellValue('A1', C('config.house_name').'名称');
                $objActSheet->setCellValue('B1', '分组名称');
                $objActSheet->setCellValue('C1', '备注');
                // $objActSheet->setCellValue('D1', '状态');
                $objActSheet->setCellValue('D1', '父级权限');
                $objActSheet->setCellValue('E1', '子级权限');
              
                $result = D('House_admin_group')->get_limit_list_page($where_condition , ($i+1)*1000 , true);
                if (!empty($result['list'])) {
                    $index = 2;
                    foreach ($result['list'] as $value) {
                        $objExcel->getActiveSheet()->getStyle('A'. $index. ':E'. $index)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                        $objExcel->getActiveSheet()->getStyle('E'. $index)->getAlignment()->setWrapText(true);//设置换行
                        $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(1200);
                        $objActSheet->setCellValueExplicit('A' . $index, $now_village['village_name']);
                        $objActSheet->setCellValueExplicit('B' . $index, $value['name']);
                        $objActSheet->setCellValueExplicit('C' . $index, $value['remarks']);

                        // if ($value['status']==1) {
                        //     $objActSheet->setCellValueExplicit('D' . $index, '开启');
                        // }else{
                        //     $objActSheet->setCellValueExplicit('D' . $index, '关闭');                          
                        // }
                        $menus = D('House_menu_new')->where(array('id'=>array('in',$value['group_menus'])))->select();
                        $menus_tree = $this->getTree($menus);
                        if ($menus) {
                            foreach ($menus_tree as $_k => $_menu) {
                                if ($_k==0) {
                                    $objActSheet->setCellValueExplicit('D' . $index, $_menu['name']);
                                }else{
                                    $objExcel->getActiveSheet()->getStyle('A'. $index. ':E'. $index)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                                    $objExcel->getActiveSheet()->getStyle('E'.$index)->getAlignment()->setWrapText(true);//设置换行
                                    $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(1200);
                                    $objActSheet->setCellValueExplicit('A' . $index, '');
                                    $objActSheet->setCellValueExplicit('B' . $index, '');
                                    $objActSheet->setCellValueExplicit('C' . $index, '');
                                    $objActSheet->setCellValueExplicit('D' . $index, $_menu['name']);
                                }
                                $child = '';
                                foreach ($_menu['child'] as  $_child) {
                                    $child .= '【'.$_child['name'].'】';
                                    if ($_child['child']) {
                                        foreach ($_child['child'] as $_child2) {
                                            $child .= $_child2['name'].'，';
                                        }
                                        $child = trim($child,'，');
                                    }
                                    $child .= "\r\n";
                                }
                                $objActSheet->setCellValueExplicit('E' . $index, $child);
                                $index++;
                            }
                        }else{
                           $index++; 
                        }

                        // $menus_name = '';
                        // if ($menus) {
                        //     foreach ($menus as $key => $value) {
                        //         $menus_name .= $value['name'] . '，';
                        //     }
                        //     $menus_name = trim($menus_name,'，');
                        // }
                        // $objActSheet->setCellValueExplicit('D' . $index, $menus_name);
                        
                        // $index++;
                    }
                }
                sleep(2);
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
    /**
     * 实现无限极分类
    */
    function getTree($array){
        //第一步 构造数据
        $items = array();
        foreach($array as $value){
            if ($value['id']==430) {
                continue;
            } elseif($value['id']==194) {
                $value['name'] = '功能应用库';
            }
            $items[$value['id']] = $value;
        }
        //第二部 遍历数据 生成树状结构
        $tree = array();
        foreach($items as $key => $value){
            if(isset($items[$value['fid']])){
                $items[$value['fid']]['child'][] = &$items[$key];
                if (isset($items[$value['fid']]['count'])) {
                    $items[$value['fid']]['count']++;
                }else{
                    $items[$value['fid']]['count'] = 1;
                }
            }else{
                $tree[] = &$items[$key];
            }
        }
        return $tree;
    }

}
?>

