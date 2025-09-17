<?php

/*
 * 社区O2O功能
 *
 */

class HouseAction extends BaseAction{
	public function _initialize(){
		parent::_initialize();
	}

	public function index(){
		$database_village = D('House_village');
		$condition_village['village_id'] = array('in',$this->merchant_session['village_ids']);
		$village_list = $database_village->field(true)->where($condition_village)->select();
		$this->assign('village_list',$village_list);
		$this->display();
	} 

	public function login(){
		$village_id = $_GET['village_id'];
		$database_village = D('House_village');
		$condition_village['village_id'] = $village_id;
		$now_village = $database_village->field(true)->where($condition_village)->find();
		if(empty($now_village) || $now_village['status'] == 2){
			$this->error(L_('该'.C('config.house_name').'的状态不存在！请查阅。'));
		}

		// 是否为平台登录
		// $now_village['is_system'] = 1;

		//查询权限 超级管理员赋予所有权限				
		$admin_menus = D('House_menu_new')->field(true)->where(array('status'=>1))->select();
		$menus = array();
		foreach ($admin_menus as $value) {
			$menus[] = $value['id'];
		}
		$now_village['menus'] = $menus;
		session('house',$now_village);
		redirect($this->config['site_url'].'/shequ.php');
	} 
	

}
