<?php


class Charge_setAction extends BaseAction
{
    protected $village_id;

    public function _initialize(){
        parent::_initialize();
        $this->village_id = $this->house_session['village_id'];
        $this->role_id = $this->house_session['role_id']; //角色id
    }
    //线下支付方式列表
    public function pay_type_list(){
        //收费设置-线下支付方式 查看 权限
        if (!in_array(1008, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $paytype_list = D('House_village_pay_type')->where(array('village_id'=>$this->village_id))->select();
        foreach ($paytype_list as $key => $value) {
            $paytype_list[$key]['standard_sum'] = D('House_village_payment_standard')->where(array('payment_id'=>$value['payment_id']))->count();
        }
        $this->assign('paytype_list',$paytype_list);
        $this->display();
    }
    //添加线下支付方式
    public function pay_type_add(){

        if ($_GET['id']) {
            //收费设置-线下支付方式 查看 权限
            if (!in_array(79, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }
            $pay_type = D('House_village_pay_type')->where(array('id'=>$_GET['id'],'village_id'=>$this->village_id))->find();
        }
        if(IS_POST){
            if ($_GET['id']) {
                //收费设置-线下支付方式 编辑 权限
                if (!in_array(81, $this->house_session['menus'])) {
                    $this->error('对不起，您没有权限执行此操作');
                }

                if (!trim($_POST['name'])) {
                    $this->error('名称不能为空');
                }

                $res = D('House_village_pay_type')->where(array('id'=>$_GET['id'],'village_id'=>$this->village_id))->data(array('name'=>trim($_POST['name'])))->save();
            }else{
                //收费设置-线下支付方式 添加 权限
                if (!in_array(80, $this->house_session['menus'])) {
                    $this->error('对不起，您没有权限执行此操作');
                }

                if (!trim($_POST['name'])) {
                    $this->error('名称不能为空');
                }

                $res = D('House_village_pay_type')->data(array('name'=>$_POST['name'],'village_id'=>$this->village_id))->add();
            }
            if($res!==false){
                $this->success('添加成功，正在跳转',U('pay_type_list'));
                die;
            }else{
                $this->error('添加失败');
                die;
            }
        }
        $this->assign('pay_type',$pay_type);
        $this->display();
    }

    //删除线下支付方式
    public function pay_type_del(){
        //收费设置-线下支付方式 删除 权限
        if (!in_array(82, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if(D('House_village_pay_type')->where(array('id'=>$_GET['id'],'village_id'=>$this->village_id))->delete()){
            $this->success('删除成功',U('pay_type_list'));die;
        }else{
            $this->error('删除失败');die;
        }
    }
    //缴费设置
    public function pay_set()
    {
        //基本信息-查看 权限
        if (!in_array(1009, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $village_id = $this->village_id;
        if($village_id){
            $d_house_village = D('House_village');
            $village_info = $d_house_village->field(true)->where(array('village_id'=>$village_id))->find();
            $village_info['long'] = floatval($village_info['long']);
            $village_info['lat'] = floatval($village_info['lat']);
            $village_info['door_pwd'] = explode(',', $village_info['door_pwd']);

            // 处理 个性化名称
            if (empty($village_info['diy_village_name'])) {
                $village_info['diy_village_name'] = $d_house_village->defaulf_diy_txt;
            }
            $this->assign('village_param_txt',$d_house_village->village_param_txt);
            $this->assign('village_info',$village_info);
        }
        $redirect_uri = urlencode(C('config.site_url').'/shequ.php?g=House&c=Index&a=getCode') ;
        $this->assign('redirect_uri',$redirect_uri);
        $this->display();
    }
    public function village_edit(){
        $village_id = $this->village_id;
        $condition_village['village_id'] = $village_id;

        //基本信息-查看 权限
        if (!in_array(2, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $village_info = D('House_village')->where($condition_village)->find();

        if(IS_POST){
            //基本信息-编辑 权限
            if (!in_array(3, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }
            $village_about_data = [];
            $result = D('House_village')->where($condition_village)->data($_POST)->save();
            if($result !== false){
                $info_id = M('House_village_info')->where(array('village_id'=>$village_id))->getField('info_id');
                if ($info_id) {
                    $village_about_data['add_time'] = time();
                    M('House_village_info')->where(array('info_id'=>$info_id))->save($village_about_data);
                    fdump(M('House_village_info'),'$village_about_data');
                } else {
                    $village_about_data['village_id'] = $village_id;
                    $village_about_data['property_id'] = $village_info['property_id'];
                    $village_about_data['add_time'] = time();
                    M('House_village_info')->data($village_about_data)->add();
                }
                if ($_POST['owe_property_open_door']!=$village_info['owe_property_open_door'] || $_POST['owe_property_open_door_day']!=$village_info['owe_property_open_door_day'] || $_POST['property_endtime']!=$village_info['property_endtime']) {
                    // 计划任务 修改了相关数据 同步所有业主钥匙信息
//                    D('House_village_face_door')->create_village_plan($village_id,'sync_user');
                    D('House_village_face_door')->sync_user($village_id);
                }
                $community_id = $village_info['community_id'];
                if ($community_id && $_POST['community_id'] && $community_id != $_POST['community_id']) {
                    $where_bind = array(
                        'type' => 2,
                        'bind_id' => $village_id,
                        'fid' => $community_id
                    );
                    // 修改了关联
                    $set_data = array(
                        'fid' => $_POST['community_id']
                    );
                    D('House_village_custom_polygon')->change_village_custom($where_bind,$set_data);
                }
                $this->success('修改成功！');
            }else{
                $this->error('修改失败！请重试。');
            }
        }
    }
}