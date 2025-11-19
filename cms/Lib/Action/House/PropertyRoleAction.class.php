<?php

/**
 * Created by PhpStorm.
 * User: 郭杰
 * Date: 2019/11/6
 * Time: 10:39
 */
class PropertyRoleAction extends BaseAction{

    public function _initialize(){
        parent::_initialize();
    }

    public function role_list(){


        $database_property_admin = D('Property_admin');
        $where = array();
        $where['property_id'] = $this->property_id;
        $list = $database_property_admin->get_limit_list_page($where);

        $this->assign('list',$list);
        $this->display();

    }


    //添加角色
    public function role_add(){
        $this->assign('bg_color', '#F3F3F3');
        if (IS_POST) {
            //验证账号是否存在
            if (!$_POST['account']) {
                $this->error('请输入账号');
            }
            if (!$_POST['pwd']) {
                $this->error('请输入密码');
            }
            $property_info = D('Property_admin')->where(array('account'=>trim($_POST['account'])))->find();
            if ($property_info) {
                $this->error('该账号已存在，请重新输入其他账号');
            }
            $house_property = M('House_property')->field(true)->where(array('account'=>trim($_POST['account'])))->find();
            if ($house_property) {
                $this->error('该账号已存在，请重新输入其他账号');
            }
            $data = array();
            $data['property_id'] = $this->property_id;
            $data['account'] = trim($_POST['account']);
            $data['pwd'] = md5($_POST['pwd']);
            $data['realname'] = $_POST['realname'];
            $data['phone'] = $_POST['phone'];

            $data['remarks'] = $_POST['remarks'];

            $data['time'] = $_SERVER['REQUEST_TIME'];
            $data['menus'] = $_POST['menus_village'] ? implode(',',$_POST['menus_village']) : '';
            $menus = $_POST['menus'] ? implode(',',$_POST['menus']) : '';
            $res = D('Property_admin')->data($data)->add();
            if ($res) {
                M('Property_admin_auth')->data(['update_time'=>time(),'menus'=>$menus,'add_time'=>time(),'admin_id'=>$res,'property_id'=>$this->property_id])->add();
                M('Property_admin_auth')->where(['admin_id'=>0])->data(['admin_id'=>$res])->save();
                $this->success('添加成功',U('role_list',['iframe'=> $_GET['iframe']]));
            }else{
                $this->error('添加失败');
            }
        }else{
            M('Property_admin_auth')->where(['admin_id'=>0])->delete();
            $condition_where = array();
            if($_GET['village_name']){
                $this->assign('village_name',$_GET['village_name']);
                $condition_where['village_name'] = array('like', '%' . $_GET['village_name'] . '%');
            }
            if($_GET['province_id']){
                $condition_where['province_id'] = $_GET['province_id'];
            }
            if($_GET['city_id']){
                $condition_where['city_id'] = $_GET['city_id'];
            }
            if($_GET['area_id']){
                $condition_where['area_id'] = $_GET['area_id'];
            }
            $condition_where['property_id'] = $this->property_id;
            $condition_where['status'] = array('neq',5);
            $dataList = M('House_village')->where($condition_where)->order('`village_id` DESC')->select();
            $this->assign('dataList',$dataList);
            $menus_arr1 = array(
                ['id'=>'property_3','fid'=>0,'name'=>'数据中心'],
                ['id'=>'property_3_1','fid'=>'property_3','name'=>'小区列表'],
                ['id'=>'property_3_2','fid'=>'property_3','name'=>'业主档案设置'],
                ['id'=>'property_3_3','fid'=>'property_3','name'=>'账单服务'],
                ['id'=>'property_33_1','fid'=>'property_3_3','name'=>'收支流水'],
                ['id'=>'property_33_2','fid'=>'property_3_3','name'=>'物业流水'],
            );
            $jz_wsxdsj_net_customized_open = D('Config_customization')->getWSXCustomizedOpen();
            if ($jz_wsxdsj_net_customized_open) {
                $menus_arr1[] = ['id'=>'property_3_4_1','fid'=>'property_3','name'=>'质保明细'];
            }
            $menus_arr2 = array(
                ['id'=>'property_3_4','fid'=>'property_3','name'=>'广告设置'],
                ['id'=>'property_7','fid'=>0,'name'=>'商家管理'],
                ['id'=>'property_8','fid'=>0,'name'=>'微信公众号'],
                ['id'=>'property_81','fid'=>'property_8','name'=>'公众号绑定'],
                ['id'=>'property_82','fid'=>'property_8','name'=>'模板消息'],
                ['id'=>'property_83','fid'=>'property_8','name'=>'自动回复'],
                ['id'=>'property_83','fid'=>'property_8','name'=>'自定义菜单'],
                ['id'=>'property_84','fid'=>'property_8','name'=>'关键词回复'],
                ['id'=>'property_84','fid'=>'property_8','name'=>'图文素材'],
                ['id'=>'property_9','fid'=>0,'name'=>'微信小程序'],
                ['id'=>'property_10','fid'=>0,'name'=>'企业微信SCRM'],
                ['id'=>'property_10_1','fid'=>'property_10','name'=>'基本配置'],
                ['id'=>'property_10_2','fid'=>'property_10','name'=>'渠道活码'],
                ['id'=>'property_10_3','fid'=>'property_10','name'=>'添加渠道码'],
                ['id'=>'property_10_4','fid'=>'property_10','name'=>'内容引擎'],
                ['id'=>'property_10_5','fid'=>'property_10','name'=>'群发消息'],
                ['id'=>'property_10_7','fid'=>'property_10','name'=>'聊天侧边栏'],
                ['id'=>'property_10_8','fid'=>'property_10','name'=>'数据运营中心'],
                ['id'=>'property_10_9','fid'=>'property_10','name'=>'会话存档'],
            );
            $menus_arr = array_merge($menus_arr1, $menus_arr2);
            $list = $this->getTree($menus_arr,0);
            $this->assign('menus', $list);
            $this->display();
        }
    }


    //编辑角色
    public function role_edit(){
        $id = $_GET['id'] + 0;
        if (!$id) {
            $this->error('参数不对');
        }
        $this->assign('id',$id);

        $database_property_admin = D('Property_admin');
        //角色信息
        $condition['id'] = $id;
        $condition['property_id'] = $this->property_id;
        $role = $database_property_admin->where($condition)->find();
        if (empty($role)) {
            $this->error('数据库中没有查询到该管理员的信息！');
        }
        $role_menu = M('Property_admin_auth')->where(['property_id'=>$this->property_id,'admin_id'=>$id])->find();
        $role['menus_village'] = explode(',', $role['menus']);
        $role['menus'] = explode(',', $role_menu['menus']);
        $this->assign('role', $role);

        $condition_where = array();
        if($_GET['village_name']){
            $this->assign('village_name',$_GET['village_name']);
            $condition_where['village_name'] = array('like', '%' . $_GET['village_name'] . '%');
        }
        if($_GET['province_id']){
            $condition_where['province_id'] = $_GET['province_id'];
        }
        if($_GET['city_id']){
            $condition_where['city_id'] = $_GET['city_id'];
        }
        if($_GET['area_id']){
            $condition_where['area_id'] = $_GET['area_id'];
        }
        $condition_where['property_id'] = $this->property_id;
        $condition_where['status'] = array('neq',5);
        $dataList = M('House_village')->where($condition_where)->order('`village_id` DESC')->select();
        $this->assign('dataList',$dataList);
        $menus_arr1 = array(
            ['id'=>'property_3','fid'=>0,'name'=>'数据中心'],
            ['id'=>'property_3_1','fid'=>'property_3','name'=>'小区列表'],
            ['id'=>'property_3_2','fid'=>'property_3','name'=>'业主档案设置'],
            ['id'=>'property_3_3','fid'=>'property_3','name'=>'账单服务'],
            ['id'=>'property_33_1','fid'=>'property_3_3','name'=>'收支流水'],
            ['id'=>'property_33_2','fid'=>'property_3_3','name'=>'物业流水'],
        );
        $jz_wsxdsj_net_customized_open = D('Config_customization')->getWSXCustomizedOpen();
        if ($jz_wsxdsj_net_customized_open) {
            $menus_arr1[] = ['id'=>'property_3_4_1','fid'=>'property_3','name'=>'质保明细'];
        }
        $menus_arr2 = array(
            ['id'=>'property_3_4','fid'=>'property_3','name'=>'广告设置'],
            ['id'=>'property_7','fid'=>0,'name'=>'商家管理'],
            ['id'=>'property_8','fid'=>0,'name'=>'微信公众号'],
            ['id'=>'property_81','fid'=>'property_8','name'=>'公众号绑定'],
            ['id'=>'property_82','fid'=>'property_8','name'=>'模板消息'],
            ['id'=>'property_83','fid'=>'property_8','name'=>'自动回复'],
            ['id'=>'property_83','fid'=>'property_8','name'=>'自定义菜单'],
            ['id'=>'property_84','fid'=>'property_8','name'=>'关键词回复'],
            ['id'=>'property_84','fid'=>'property_8','name'=>'图文素材'],
            ['id'=>'property_9','fid'=>0,'name'=>'微信小程序'],
            ['id'=>'property_10','fid'=>0,'name'=>'企业微信SCRM'],
            ['id'=>'property_10_1','fid'=>'property_10','name'=>'基本配置'],
            ['id'=>'property_10_2','fid'=>'property_10','name'=>'渠道活码'],
            ['id'=>'property_10_3','fid'=>'property_10','name'=>'添加渠道码'],
            ['id'=>'property_10_4','fid'=>'property_10','name'=>'内容引擎'],
            ['id'=>'property_10_5','fid'=>'property_10','name'=>'群发消息'],
            ['id'=>'property_10_7','fid'=>'property_10','name'=>'聊天侧边栏'],
            ['id'=>'property_10_8','fid'=>'property_10','name'=>'数据运营中心'],
            ['id'=>'property_10_9','fid'=>'property_10','name'=>'会话存档'],
        );
        $menus_arr = array_merge($menus_arr1, $menus_arr2);
        $list = $this->getTree($menus_arr,0);
        $this->assign('menus', $list);

        if (IS_POST) {
            //验证账号是否存在
            if (!$_POST['account']) {
                $this->error('请输入账号');
            }
            $is_exist = D('Property_admin')->where(array('account'=>$_POST['account']))->find();
            if ($is_exist&&$is_exist['id']!=$id) {
                $this->error('该账号已存在，请重新输入账号');
            }
            $data = array();
            $data['account'] = $_POST['account'];
            if ($_POST['pwd']) {
                $data['pwd'] = md5($_POST['pwd']);
            }
            $admin_id = $id;
            $menus = $_POST['menus'] ? implode(',',$_POST['menus']) : '';
            if(M('Property_admin_auth')->where(['admin_id'=>$admin_id,'property_id'=>$this->property_id])->find()){
                M('Property_admin_auth')->where(['admin_id'=>$admin_id,'property_id'=>$this->property_id])->data(['update_time'=>time(),'menus'=>$menus])->save();
            }else{
                M('Property_admin_auth')->data(['update_time'=>time(),'menus'=>$menus,'add_time'=>time(),'admin_id'=>$admin_id,'property_id'=>$this->property_id])->add();
            }
            $data['realname'] = $_POST['realname'];
            $data['phone'] = $_POST['phone'];
            $data['remarks'] = $_POST['remarks'];
            $data['menus'] = $_POST['menus_village'] ? implode(',',$_POST['menus_village']) : '';
            $res = $database_property_admin->where($condition)->data($data)->save();
            if ($res!==false) {
                $this->success('编辑成功',U('role_list',array('iframe'=> $_GET['iframe'])));
            }else{
                $this->error('编辑失败');
            }
        }else{
            $this->display();
        }
    }

    public function property_role_list()
    {
    }

    /**
     * 物业下小区的菜单列表
     * @author lijie
     * @date_time 2021/09/02
     */
    public function village_role_list()
    {
        if($_POST){
            $menu_arr = $_POST['menus'];
            $village_id = $_POST['village_id'];
            $admin_id = $_POST['admin_id'];
            if(!$village_id){
                $this->error('缺少参数');
            }
            $village_ids = explode(',',$village_id);
            $menus = implode(',',$menu_arr);
            if(!$admin_id){
                foreach ($village_ids as $village_id){
                    if(M('Property_admin_auth')->where(['admin_id'=>0,'village_id'=>$village_id])->find()){
                        M('Property_admin_auth')->where(['admin_id'=>0,'village_id'=>$village_id])->data(['update_time'=>time(),'menus'=>$menus])->save();
                    }else{
                        M('Property_admin_auth')->data(['update_time'=>time(),'menus'=>$menus,'add_time'=>time(),'admin_id'=>0,'village_id'=>$village_id])->add();
                    }
                }
                $this->success('分配成功');
            }else{
                foreach ($village_ids as $village_id){
                    if(M('Property_admin_auth')->where(['admin_id'=>$admin_id,'village_id'=>$village_id])->find()){
                        M('Property_admin_auth')->where(['admin_id'=>$admin_id,'village_id'=>$village_id])->data(['update_time'=>time(),'menus'=>$menus])->save();
                    }else{
                        M('Property_admin_auth')->data(['update_time'=>time(),'menus'=>$menus,'add_time'=>time(),'admin_id'=>$admin_id,'village_id'=>$village_id])->add();
                    }
                }
                $this->success('分配成功');exit;
            }
        }else{
            $village_id = $_GET['village_id'];
            $admin_id = $_GET['admin_id'];
            if(!$village_id){
                $this->error('缺少小区id');
            }
            $village_ids = explode(',',$village_id);
            $property_id = D('House_village')->where(array('village_id'=>$village_ids[0]))->getField('property_id');
            $database_property_admin = D('Property_admin');
            //角色信息
            if($admin_id){
                $role = $database_property_admin->get_one($admin_id);
                if (empty($role)) {
                    $this->error('数据库中没有查询到该管理员的信息！');
                }
            }
            if(count($village_ids) > 1){
                $menus = [];
            } else{
                if($admin_id){
                    $menus = M('Property_admin_auth')->where(['admin_id'=>$admin_id,'village_id'=>$village_id])->find();
                }else{
                    $menus = M('Property_admin_auth')->where(['admin_id'=>0,'village_id'=>$village_id])->find();;
                }
            }
            $this->assign('role', ['menus'=>explode(',',$menus['menus'])]);
            $this->assign('is_village_auth',0);

            $where = '`status`=1';
            $menus = D('House_menu_new')->where($where)->order('sort DESC,id asc')->select();
            //处理套餐购买功能应用 start
            $package_order_info =  D('PrivilegePackage')->disposeAuth($menus,$property_id);
            $menus = $package_order_info['list'];
            $application_id = $package_order_info['application_id'];
            //处理套餐购买功能应用 end
            $list = $this->getTrees($menus,$application_id);
            $this->assign('menus', $list);
            $this->assign('admin_id',$admin_id);
            $this->assign('village_id',$village_id);
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

    /**
     * Notes: 处理树 购买功能套餐处理
     * @param $array
     * @param $application_id
     * @return array
     * @author: weili
     * @datetime: 2020/9/28 17:58
     */
    public function getTree($array,$application_id=[]){
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
            if(strlen($value['fid']) == 1)
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

    //角色删除
    public function role_del(){
        $id = $_GET['id'] + 0;
        if ($id) {
            $database_property_admin = D('Property_admin');
            //角色信息
            $condition['id'] = $id;
            $condition['property_id'] = $this->property_id;
            $role = $database_property_admin->where($condition)->find();
            if (empty($role)) {
                $this->error('数据库中没有查询到该账号的信息！');
            }
            $result = D('Property_admin')->where(array('id'=>$id))->delete();
            if($result){
                $this->success('删除成功');
            }else{
                $this->error('删除失败');
            }
        }
    }

    public function ajax_get_worker()
    {
        $condition_where['property_id'] = $this->property_id;
        if ($_POST['search']) {
            $condition_where['_string'] = 'name like "%'.trim($_POST['search']).'%" OR phone like "%'.trim($_POST['search']).'%"';
        }
        $condition_where['status'] = 1;
        $condition_where['is_del'] = 0;
        $workers = D('House_worker')->field(true)->where($condition_where)->select();
        exit(json_encode($workers));
    }


    public function ajax_village_list(){
        $condition_where = array();
        if($_POST['village_name']){
            $this->assign('village_name',$_POST['village_name']);
            $condition_where['village_name'] = array('like', '%' . $_POST['village_name'] . '%');
        }
        if($_POST['province_id']){
            $condition_where['province_id'] = $_POST['province_id'];
        }
        if($_POST['city_id']){
            $condition_where['city_id'] = $_POST['city_id'];
        }
        if($_POST['area_id']){
            $condition_where['area_id'] = $_POST['area_id'];
        }
        $condition_where['property_id'] = $this->property_id;
		$condition_where['status'] = array('neq',5);
        $dataList = M('House_village')->where($condition_where)->order('`village_id` DESC')->select();
        exit(json_encode($dataList));
    }
}