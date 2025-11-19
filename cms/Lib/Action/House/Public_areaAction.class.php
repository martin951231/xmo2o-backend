<?php

/**
 * Created by PhpStorm.
 * User: win7
 * Date: 2020/4/17
 * Time: 10:06
 */
class Public_areaAction extends BaseAction{

    /**
     * 公共区域首页列表
     */
    public function index() {

        $where = array(
            'village_id' => $this->house_session['village_id']
        );
        $public_area = D('House_village_public_area')->getList($where);

        if ($public_area['list']) {
            $status_txt_arr = D('House_village_public_area')->status_txt;
            foreach($public_area['list'] as &$val) {
                $val['status_txt'] = $status_txt_arr[$val['status']];
            }
        }
        $this->assign('public_area',$public_area);
        $this->display();
    }

    public function add() {
        if($_POST){
            //公共区域添加 权限
//            if (!in_array(286, $this->house_session['menus'])) {
//                $this->error('对不起，您没有权限执行此操作');exit;
//            }
            $data = array();
            $data['village_id']	= $this->house_session['village_id'];
            if (trim($_POST['public_area_name'])) {
                $data['public_area_name'] = trim($_POST['public_area_name']);
            } else {
                $this->error('请您添加公共区域名称');exit;
            }

            if($_POST['location']){
                $locationArr = explode(',',trim($_POST['location']));
                $data['lng'] = $locationArr[0];
                $data['lat'] = $locationArr[1];
            }
            if(isset($_POST['status'])){
                $data['status'] = $_POST['status'];
            }
            if(isset($_POST['dwid'])){
                $data['dwid'] = $_POST['dwid'] ? trim($_POST['dwid']) : '';
            }

            $info = D('House_village_public_area')->where(array('public_area_name'=>$data['public_area_name'],'village_id' => $data['village_id']))->find();
            if (!empty($info)) {
                $this->error('当前公共区域名称已经被添加');exit;
            }

            $data['add_time'] = time();
            $device_id = D('House_village_public_area')->data($data)->add();
            if($device_id){
                $this->success('操作成功！',U('index'));exit;
            }else{
                $this->error('操作失败！');exit;
            }

        }else {
            //公共区域添加
//            if (!in_array(286, $this->house_session['menus'])) {
//                $this->error('对不起，您没有权限执行此操作');exit;
//            }
            $third_where = [
                'type' => ['in',['chinapopin_sichuan','ezc_chinapopin']],
                'village_id' => $this->house_session['village_id'],
            ];
            $third_config = M('House_village_third_config')->where($third_where)->find();
            $this->assign('third_config',$third_config);

            $this->display();
        }
    }


    public function edit() {
        $where['public_area_id'] = I('public_area_id');
        if(empty($where)){
            $this->error('公共区域信息不存在');exit;
        }
        if($_POST){
            //公共区域-修改 权限
//            if (!in_array(286, $this->house_session['menus'])) {
//                $this->error('对不起，您没有权限执行此操作');exit;
//            }
            $data = array();
            if ($_POST['public_area_name']) {
                $data['public_area_name'] = $_POST['public_area_name'];
            }
            if($_POST['location']){
                $locationArr = explode(',',trim($_POST['location']));
                $data['lng'] = $locationArr[0];
                $data['lat'] = $locationArr[1];
            }
            if(isset($_POST['dwid'])){
                $data['dwid'] = $_POST['dwid'] ? trim($_POST['dwid']) : '';
            }

            if (empty($data)) {
                $this->error('修改失败！请检查是否有修改内容');exit;
            }
            $info = D('House_village_public_area')->where(array('village_id' => $this->house_session['village_id'],'public_area_name'=>$data['public_area_name'], 'public_area_id' => array('neq', $where['public_area_id'])))->find();
            if (!empty($info)) {
                $this->error('当前公共区域名称已经被添加');exit;
            }
            if(isset($_POST['status'])){
                $data['status'] = $_POST['status'];
            }
            $data['add_time'] = time();
            $aSave	=	D('House_village_public_area')->where($where)->data($data)->save();
            if($aSave){
                $this->success('操作成功！',U('index'));exit;
            }else{
                $this->error('操作失败！请检查是否有修改内容');exit;
            }
        }else{
            //门禁设置-查看 权限
//            if (!in_array(286, $this->house_session['menus'])) {
//                $this->error('对不起，您没有权限执行此操作');exit;
//            }
            $third_where = [
                'type' => ['in',['chinapopin_sichuan','ezc_chinapopin']],
                'village_id' => $this->house_session['village_id'],
            ];
            $third_config = M('House_village_third_config')->where($third_where)->find();
            $this->assign('third_config',$third_config);

            $info=D('House_village_public_area')->where($where)->find();
            $this->assign('info',$info);

            $this->display();
        }
    }
}