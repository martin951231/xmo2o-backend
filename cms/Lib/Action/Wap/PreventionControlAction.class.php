<?php
class PreventionControlAction extends BaseAction{

	public function add() {
		$control_id = intval($_GET['control_id']);
		if(empty($control_id)){
			$this->error_tips('跳转路径错误，请确定是否正确扫码');
		}
		if(empty($this->user_session)){
			if($this->is_app_browser){
				$this->error_tips('请先进行登录',U('Login/index',array('referer'=>urlencode(U('add',array('control_id'=>$control_id))))));
			}else{
				redirect(U('Login/index',array('referer'=>urlencode(U('add',array('control_id'=>$control_id))))));
			}
		}
        $now_user = D('User')->get_user($this->user_session['uid']);
        if (empty($now_user['phone'])) {
		   $redirctUrl = U('My/bind_user',array('referer'=>urlencode(U('PreventionControl/add',$_GET))));
		   redirect($redirctUrl);exit;
		}

		if (empty($now_user['truename'])) {
			$now_user['truename'] = '';
		}

		// 检查一下是否有未填写下车地点的信息
		$d_prevention_control_info = D('Prevention_control_info');
		$info = $d_prevention_control_info->where(array('id'=>$control_id))->find();
		if(empty($info)){
			$this->error_tips('所扫二维码不存在或者已经被删除');
		}
		$d_prevention_control_user = D('Prevention_control_user');
		$where_user = array(
			'control_id' => $control_id,
			'uid' => $this->user_session['uid']
		);
		$last_info = $d_prevention_control_user->where($where_user)->order('id DESC')->find();
		$real_last_info = $last_info;
		if (empty($last_info) || !empty($last_info['end_address']) || ($info['type'] != '0' && $info['type'] != '2')){
			$last_info = array();
		}
		$is_pay = 0;
		if ($info['type']==0 && $info['bind_id']) {
			$is_pay = 1;
			$info['return_url'] = $this->config['site_url'] . '/wap.php?c=My&a=pay&spread=1&store_id='.$info['bind_id'];
		} elseif ($info['type']==0 && !$info['bind_id'] && $info['pay_url']) {
			$is_url = preg_match('/^http(s)?:\\/\\/.+/', $url) ? TRUE : FALSE;
			if ($is_url) {
				$is_pay = 1;
				$return_url = $info['pay_url'];
				$info['return_url'] = $return_url;
			} elseif ($info['type']==0 && !$info['bind_id'] && $info['pay_img']) {
				$is_pay = 2;
				$info['pay_img_txt'] = replace_file_domain('/upload/system/'.$info['pay_img']);
			}
		} elseif ($info['type']==0 && !$info['bind_id'] && $info['pay_img']) {
			$is_pay = 2;
			$info['pay_img_txt'] = replace_file_domain('/upload/system/'.$info['pay_img']);
		}
		$this->assign('info',$info);
		$this->assign('write_info',$last_info);
		if ($last_info && $last_info['name']) {
			$now_user['truename'] = $last_info['name'];
		}
		
		//餐饮收货地址
		if ($real_last_info && $real_last_info['address']) {
			$now_user['address'] = $real_last_info['address'];
		}else{
			$house_user = D('House_village_user_bind')->where(['uid' => $now_user['uid'], 'type' => ['neq', '4']])->find();
			if($house_user && $house_user['address']){
				if($house_user['name']){
					$now_user['truename'] = $house_user['name'];
				}
				$house_name = D('House_village')->where(['village_id' => $house_user['village_id'], 'type' => ['neq', '4']])->getField('village_name');
				$now_user['address'] = $house_name . $house_user['address'];
			}
		}
		
		$this->assign('now_user',$now_user);
        // 处理判断
		$this->assign('is_pay',$is_pay);
		$is_end = 0;
		if (!empty($last_info)) {
			$is_end = 1;
		}
		$this->assign('is_end',$is_end);
		
		//页面广告
		$adver_list = D('Adver')->get_adver_by_key('city_ncov_2019',1);
		if($adver_list){
			$adver = $adver_list[0];
			$this->assign('adver',$adver);
		}
		
		if($info['type'] == 3){
			$this->display('foodshop_add');
		}else if($info['type'] == 1){
			$this->display('other_add');
		}else{
			$this->display();
		}
	}

	public function save() {
		$control_id = intval($_POST['control_id']);
		if(empty($control_id)){
			$this->error_tips('跳转路径错误，请确定是否正确扫码');
		}
		if(empty($this->user_session)){
			if($this->is_app_browser){
				$this->error_tips('请先进行登录',U('Login/index',array('referer'=>urlencode(U('add',array('control_id'=>$control_id))))));
			}else{
				redirect(U('Login/index',array('referer'=>urlencode(U('add',array('control_id'=>$control_id))))));
			}
		}
		$_POST['control_id'] = $control_id;
		$_POST['uid'] = $this->user_session['uid']; 
		$_POST['ip'] = get_client_ip(0);
		$d_prevention_control_user = D('Prevention_control_user');
		if (isset($_POST['id']) && intval($_POST['id'])>0) {
			$_POST['last_time'] = time();
			$save = $d_prevention_control_user->where(array('id' => intval($_POST['id'])))->data($_POST)->save();
			if ($save) {
				$id = intval($_POST['id']);
			}
		} else {
			$_POST['add_time'] = time();
			$id = $d_prevention_control_user->data($_POST)->add();
		}
		$now_user = D('User')->get_user($this->user_session['uid']);
		if ($_POST['name'] && $_POST['name']!=$now_user['truename']){
			D('User')->where(array('uid' => $this->user_session['uid']))->setField('truename', $_POST['name']);
		}
		if ($id) {
			$this->success($id);
		}else{
		   $this->error_tips('记录失败，请重试保存');
		}
	}

	public function return_url() {
        $url = $_GET['url'];
        $url = htmlspecialchars_decode(urldecode($url));
		header( "Location: {$url}" );
	}
}

?>