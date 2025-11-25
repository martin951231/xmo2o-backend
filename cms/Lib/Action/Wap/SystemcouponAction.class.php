<?php
class SystemcouponAction extends BaseAction{
	public function index(){
		$coupon  = D('System_coupon');
		$get_where = ['rand_send'=>0,'is_privileged'=>0,'is_hide'=>0];
		if(!empty($this->user_session)) {
			$now_user = D('User')->get_user($this->user_session['uid']);
			$get_where['city_id'] = [['eq',0], ['eq',$now_user['city_id']], 'or'];
		}
		$coupon_list = $coupon->get_coupon_list($get_where);
		if(!empty($this->user_session)) {
			$now_user_coupon  = $coupon->get_coupon_category_by_phone($this->user_session['uid']);
			foreach($now_user_coupon as $v){
				if(!empty($coupon_list[$v['coupon_id']])) {
					$coupon_list[$v['coupon_id']]['selected'] = 1;
				}
			}
		}
		$category_platform = D('Card_new_coupon')->cate_platform();

		$category = $category_platform['category'];

		//dump($category);exit;
		if(empty($this->config['appoint_page_row'])){
			unset($category['appoint']);
		}
		$platform = $category_platform['platform'];
		
		//获取用户已经领取券的列表
		$had_pull_obj = [];
		if($coupon_list){
			$had_pull_arr = M('System_coupon_hadpull')->field('`coupon_id`')->where(['uid'=>$this->user_session['uid'], 'coupon_id'=>['in', array_column($coupon_list, 'coupon_id')]])->select();
			foreach($had_pull_arr as $value){
				$had_pull_obj[$value['coupon_id']]++;
			}
		}
		
		foreach ($coupon_list as $vv) {
			if($vv['status']==2 && $vv['last_time']<$_SERVER['REQUEST_TIME']-86400){
				continue;
			}
			
			$had_pull = $had_pull_obj[$vv['coupon_id']];

			if($had_pull>=$vv['limit']){
				$vv['status'] = 4;//超过限制
			}
			$vv['platform'] = unserialize($vv['platform']);
			$vv['has_get'] =$had_pull;
			if($vv['limit']-intval($had_pull)<=$vv['num']-$vv['had_pull']){
				$vv['can_get_num'] = $vv['limit']-intval($had_pull);
			}else{
				$vv['can_get_num'] = $vv['num']-$vv['had_pull'];
			}
			$vv['can_get'] = unserialize($vv['platform']);
			$tmp_platform = '';
			foreach ($vv['platform'] as $vt) {
				$tmp_platform .= $platform[$vt] . '/';
			}
			$vv['platform'] = substr($tmp_platform, 0, -1);

			//平台优惠券增加指定店铺提示
            $vv['designated_shop'] = '';
            if ($vv['use_type'] == 1) {
                $fist_store_name = M('System_coupon_store')->alias('cs')
                    ->join('LEFT JOIN ' . C('DB_PREFIX') . 'merchant_store AS s ON s.store_id = cs.store_id')
                    ->where(['cs.coupon_id' => $vv['coupon_id']])
                    ->order('cs.id ASC')
                    ->getField('s.name');
                $vv['designated_shop'] = $fist_store_name ?$fist_store_name: '';
            }
			if($vv['allow_new']==1){//这里硬加一个新人券类型
				$tmp['new_user'][] = $vv;
			}
			$tmp[$vv['cate_name']][] = $vv;
		}
		//dump($tmp);exit;
		foreach ($category as $k => $c) {
			if (empty($tmp[$k])) {
				$tmp[$k] = array();
				$category_tmp[$k]['count'] = 0;
			}else{
				$category_tmp[$k]['count'] = count($tmp[$k]);
			}
		}
		arsort($category_tmp);

		$category_tmp = $this->deal_category_data($category_tmp);//需求是必须将新人券放在第一位
		//$max_category = array_keys($category_tmp);
		//$this->assign('max_category', $max_category[0]);
		$this->assign('max_category', 'new_user');
		$this->assign('category', $category);
		$this->assign('category_tmp', $category_tmp);
		
		// $this->assign('isnew', D('User')->check_new($this->user_session['uid'],'all'));
		
		//dump($tmp);exit;
		$this->assign('coupon_list',$tmp);
		$this->display();
	}

	protected function deal_category_data($catrgory){
		$temp = $catrgory['new_user'];
		unset($catrgory['new_user']);
		return array_merge(array('new_user'=>$temp),$catrgory);

	}

	public function coupon_error($error_code){
		switch($error_code){
			case 0:
				return array("msg"=>L_("领取成功！"));
			break;
			case 1:
				return array("msg"=>L_("领取失败！"));
				break;
			case 2:
				return array("msg"=>L_("该优惠券已过期"));
				break;
			case 3:
				return array("msg"=>L_("该优惠券已经被领完了！"));
				break;
			case 4:
				return array("msg"=>L_("该优惠券只能新用户领取！"));
				break;
			case 5:
				return array("msg"=>L_("该优惠券不能再领取了"));
				break;
			case 6:
				return array("msg"=>L_("该优惠券您当前所在城市无法领取"));
				break;
		}
	}

	//领取优惠券
	public function had_pull()
	{
		$coupon_id = intval($_POST['coupon_id']);
		$uid = $this->user_session['uid'];
		if (empty($this->user_session)) {
			echo json_encode(array('error_code' => 6, 'msg' => L_('未登录')));
			die;
		}elseif(empty($this->user_session['phone'])){
			echo json_encode(array('error_code' => 7, 'msg' => L_('未绑定手机号码')));
		}
		//是否一次领取最多可领取数量
		$get_all=I('get_all',0,'intval');
		$model = D('System_coupon');
		$has_get = $model->get_coupon_count_by_uid($coupon_id, $uid);
		//$return['has_get'] = $has_get;

		$result = $model->had_pull($coupon_id, $uid,'',$get_all);
		if ($result['error_code'] != 0) {
			switch ($result['error_code']) {
				case '1':
					$error_msg = L_('领取失败');
					break;
				case '2':
					$error_msg = L_('优惠券已过期');
					break;
				case '3':
					$error_msg = L_('优惠券已经领完了');
					break;
				case '4':
					$error_msg = L_('只允许新用户领取');
					break;
				case '5':
					$error_msg = L_('不能再领取了');
				case '6':
					$error_msg = L_('您当前所在城市无法领取该优惠券');
					break;
			}
			echo json_encode(array('error_code' => $result['error_code'], 'msg' => $error_msg));
			die;
		}
		$reduce_num=$result['coupon']['reduce_num'];
        $reduce_num=$reduce_num>0?$reduce_num:1;
		$model->decrease_sku(0,$reduce_num,$coupon_id);//网页领取完，微信卡券库存需要同步减少
		if($result['coupon']['limit']-$result['coupon']['has_get']<=$result['coupon']['num']-$result['coupon']['had_pull']){
			$result['coupon']['can_get'] = $result['coupon']['limit']-$result['coupon']['has_get'];
		}else{
			$result['coupon']['can_get'] =$result['coupon']['num']-$result['coupon']['had_pull'];
		}
		echo json_encode(array('error_code' => 0, 'msg' => L_('领取成功'), 'coupon' => $result['coupon']));
		die;

	}



	public function ajax_check_login(){
		if (empty($this->user_session)) {
			echo json_encode(array('error_code' => 6, 'msg' => L_('未登录,登录后才能领取')));
		}else{
			if(intval($_POST['isnew']) && (!$res = D('User')->check_new($this->user_session['uid'], 'all'))){
				//echo json_encode(array('error_code' => 2, 'msg' => L_('只允许新用户领取')));die;
				echo json_encode(array('error_code' => 2, 'msg' => L_('您不符合活动规则，无法领取优惠券哦')));die;
			}
			echo json_encode(array('error_code' => 0, 'msg' => L_('已登录')));
		}
		exit;
	}

	public function link(){
		if($_GET['cate_name']=='all'){
			redirect($this->config['site_url'].'/wap.php');
		}else if($_GET['cate_name']=='shop'){
			redirect($this->config['site_url'].'/wap.php?c=Shop&a=index');
		}else if($_GET['cate_name']=='group'){
			redirect($this->config['site_url'].'/wap.php?c=Group&a=index');
		}else if($_GET['cate_name']=='meal'){
			redirect($this->config['site_url'].'/wap.php?c=Foodshop&a=index');
		}else if($_GET['cate_name']=='store'){
			redirect($this->config['site_url'].'/wap.php?c=Merchant&a=store_list');
		}else if($_GET['cate_name']=='appoint'){
			redirect($this->config['site_url'].'/wap.php?c=Appoint&a=index');
		}else if($_GET['cate_name']=='mall'){
			redirect($this->config['site_url'].'/wap.php?c=Mall&a=index');
		}
	}
}
