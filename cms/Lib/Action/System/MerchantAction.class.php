<?php
/*
 * 商家管理
 *
 * @  Writers    Jaty
 * @  BuildTime  2014/11/07 14:45
 *
 */

class MerchantAction extends BaseAction{
	public $cert_xiaowei = '';
	public $time_type = array('year'=>'年','month'=>'月','day'=>'日');
	public $default_menus = array(1,2,3,32,110,113,114);

	public function get_default_menus(){
		$menus = M('New_merchant_menu')->where(array('fid'=>0))->select();
		foreach ($menus as $item) {
			$menu[] = $item['id'];
		}
		return $menu;
	}
    public function index(){
		$database_merchant = D('Merchant');
		//搜索
        $_GET['keyword'] = trim($_GET['keyword']);
		if(!empty($_GET['keyword'])){
			if($_GET['searchtype'] == 'mer_id'){
				$condition_merchant['mer_id'] = $_GET['keyword'];
			}else if($_GET['searchtype'] == 'account'){
				$condition_merchant['account'] = array('like','%'.$_GET['keyword'].'%');
			}else if($_GET['searchtype'] == 'name'){
				$condition_merchant['name'] = array('like','%'.$_GET['keyword'].'%');
			}else if($_GET['searchtype'] == 'phone'){
				$condition_merchant['phone'] = array('like','%'.$_GET['keyword'].'%');
			}else if($_GET['searchtype'] == 'store'){
				$where['name'] =  array('like','%'.htmlspecialchars($_GET['keyword']).'%');
				$where['status'] = ['NEQ',4];
				$store_list = M('Merchant_store')->field('mer_id')->where($where)->group('mer_id')->select();
				foreach ($store_list as $value) {
					$store_arr[] = $value['mer_id'];
				}
				$condition_merchant['mer_id'] = array('in',$store_arr);
			}
		}
		if ($this->system_session['area_id']) {
			$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
			if($now_area['area_type']==3){
				$area_index = 'area_id';
			}elseif($now_area['area_type']==2){
				$area_index = 'city_id';
			}elseif($now_area['area_type']==1){
				$area_index = 'province_id';
			}
			$this->assign('admin_area',$now_area['area_type']);
			// $area_index = $this->system_session['level'] == 1 ? 'area_id' : 'city_id';
			$condition_merchant[$area_index] = $this->system_session['area_id'];

			if($this->system_session['street_id']){
                $condition_merchant['street_id'] = $this->system_session['street_id'];
            }
		}
		if($_GET['province_idss'] && $this->config['many_city']){
			$condition_merchant['province_id'] =$_GET['province_idss'];
		}
		if($_GET['city_idss']){
			$condition_merchant['city_id'] =$where['city_id']= $_GET['city_idss'];
		}
		if($_GET['area_id']){
			$condition_merchant['area_id'] =$where['area_id']= $_GET['area_id'];
		}
        if ($_GET['street_id'] > 0) {
            $condition_merchant['street_id'] = $where['street_id'] = $_GET['street_id'];
        }

		$searchstatus = intval($_GET['searchstatus']);
		switch($searchstatus){
			case 0:
				//3 是欠款状态 该状态下商家业务暂停，不能支付，但是可以管理，充值
				$condition_merchant['status'] = array("in", [1,3]);
				break;
			case 1:
				$condition_merchant['status'] = 2;
				break;
			case 2:
				$condition_merchant['status'] = 0;
				break;
		}

		if (!isset($condition_merchant['status'])) {
		    $condition_merchant['status'] = array('lt', 4);//4：假删除状态
		}
		switch($_GET['searchorder']){
			case 0:
				$order = 'mer_id DESC';
				break;
			case 1:
				$order = 'money DESC,mer_id DESC';
				break;

		}
		if ($_GET['ecard_pay']) {
			$merIds = M('Merchant_store')
				->where(['is_internal_card_pay' => 1,'status'=>['NEQ',4]])
				->field('mer_id')
				->select();
			if($merIds) {
				$merIds = array_column($merIds, 'mer_id');
				switch($_GET['ecard_pay']){
					case 1:
						$condition_merchant['mer_id'] = ['IN', $merIds];
						break;
					case 2:
						$condition_merchant['mer_id'] =  ['NOT IN', $merIds];
						break;
				}
			}else{
				$condition_merchant['mer_id'] = '-1';
			}
		}
        // 根据开关获取获取商家类型信息
        if ($this->config['merchant_type_switch'] == 1) {
            $search_type = intval($_GET['merchant_type']);
            if ($search_type > 0) {
                $condition_merchant['type_id'] = $search_type;
            }
            $type_list=  M('Merchant_type')->where(array('type_status'=>0))->order('sort DESC, add_time DESC')->select();
        } else {
            $type_list = array();
        }
        $this->assign('type_list',$type_list);

        if(C('customization.life_tools') && $this->system_session['level'] != 2){// 城投生活通定制 商家绑定管理员
        	$adminIdArr = D('Admin')->getAdminByFid($this->system_session['id']);
        	if($adminIdArr){
                $condition_merchant['admin_id'] = ['in', $adminIdArr];
        	}
        }

		$mer_withdraw_list = D('Merchant_money_list');
		$all_money = $mer_withdraw_list->get_all_mer_money($condition_merchant);

		if(!$all_money){
			$all_money = 0;
		}
		$this->assign('all_money',$all_money);

		$count_merchant = $database_merchant->where($condition_merchant)->count();

		import('@.ORG.system_page');
		$p = new Page($count_merchant,15);
		$merchant_list = $database_merchant->field(true)->where($condition_merchant)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
		foreach ($merchant_list as &$v) {
			$where_fans['mer_id'] = $v['mer_id'];
			$where_fans['_string'] = "u.openid<>'' AND m.openid<> ''";
			$v['fans_count'] = M('Merchant_user_relation')->join('as m LEFT JOIN '.C('DB_PREFIX').'user u ON u.openid = m.openid')->where($where_fans)->count();

			if($v['merchant_end_time']!=0 && $v['merchant_end_time']<time() || $v['try_end_time']!=0 && $v['try_end_time']<time()){
				if($this->config['buy_merchant_auth'] && $menus = M('Authority_group')->where(array('is_default'=>1))->find()){
					$date_auth['authority_group_id'] = $menus['id'];
					$date_auth['merchant_end_time'] = 0;
					$date_auth['try_end_time'] = 0;
					$date_auth['is_try'] = 0;
					M('Merchant')->where(array('mer_id'=>$v['mer_id']))->save($date_auth);
				}else{
					M('Merchant')->where(array('mer_id'=>$v['mer_id']))->setField('status',0);
					$v['status'] = 0;
				}
			}

			if($this->config['open_juhepay'] == 1){

				$mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$v['mer_id']))->find();
				if($mer_juhe){
					$v['juhe_code'] = $mer_juhe['userid'];
				}else{
					$v['juhe_code']  ='';
				}
			}
            if($this->config['open_c2b_spread'] == 1) {
                if ($v['spread_code'] != '') {
                    $user = M('User')->where(array('spread_code' => $v['spread_code']))->find();
                    $v['user_nickname'] = $user['nickname'];
                    $v['user_phone'] = $user['phone'];
                    $v['user_uid'] = $user['uid'];
                }
            }
            if(C("config.pay_weixin_sp_xiaowei_open") == 1){
            	$v['xiaowei'] = $this->get_xiaowei_status($v['mer_id']);
            }

			$v['is_internal_card_pay'] = M('Merchant_store')
				->where(['is_internal_card_pay' => 1, 'mer_id' => $v['mer_id'], 'status' => ['NEQ',4]])
				->getField('is_internal_card_pay');
		}

		$this->assign('merchant_list',$merchant_list);
		$pagebar = $p->show();
		$this->assign('pagebar',$pagebar);

		$export = 1;//导出列表的权限
		// 判断该管理员是否有登录商家后台的权限
        if ($this->system_session['level'] < 2) {
        	$export = 0;
            if (!in_array(310, $this->system_session['menus'])) $this->assign('notShow', 1);
        }
        $this->assign('export',$export);

		$open_yibao = C('customization.open_yibao')?:0;
		$this->assign('open_yibao',$open_yibao);
		$this->display();
    }

    protected function get_xiaowei_status($mer_id){
    	if(empty($mer_id)) return 0;
    	$xiaowei = M("Merchant_xiaowei")->where(['mer_id'=>$mer_id])->order('addtime desc')->find();
    	if(!$xiaowei){
    		return 1;//未提交
    	}
    	else{
    		if($xiaowei['status'] == '0' && $xiaowei['addtime'] > time()-5*60){
    			return 2;//已提交，但未到5分钟
    		}
    		elseif($xiaowei['status'] == '0'){
    			return 3;//已提交，到5分钟了，可以查询结果了
    		}
    		elseif($xiaowei['status'] == '1'){
    			return 4;//审核失败，请重新提交审核
    		}
    		elseif($xiaowei['status'] == '2'){
    			return 5;//审核成功（待扫码）
    		}
    		elseif($xiaowei['status'] == '3'){
    			return 6;//扫码成功
    		}
    	}
    }

	public function checkMerchantLogin(){
		if(session('merchant')){
			$this->error(['merchant_login_name' => session('merchant.name')]);
		}else{
			$this->success();
		}
	}

	public function wait_merchant(){
		$condition_merchant['status'] = 2;
		//搜索
		if(!empty($_GET['keyword'])){
			if($_GET['searchtype'] == 'mer_id'){
				$condition_merchant['mer_id'] = $_GET['keyword'];
			}else if($_GET['searchtype'] == 'account'){
				$condition_merchant['account'] = array('like','%'.$_GET['keyword'].'%');
			}else if($_GET['searchtype'] == 'name'){
				$condition_merchant['name'] = array('like','%'.$_GET['keyword'].'%');
			}else if($_GET['searchtype'] == 'phone'){
				$condition_merchant['phone'] = array('like','%'.$_GET['keyword'].'%');
			}
		}
		if ($this->system_session['area_id']) {
			$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
			if($now_area['area_type']==3){
				$area_index = 'area_id';
			}elseif($now_area['area_type']==2){
				$area_index = 'city_id';
			}elseif($now_area['area_type']==1){
				$area_index = 'province_id';
			}
			$condition_merchant[$area_index] = $this->system_session['area_id'];
		}
        // 根据开关获取获取商家类型信息
        if ($this->config['merchant_type_switch'] == 1) {
            $search_type = intval($_GET['searchtype']);
            if ($search_type > 0) {
                $condition_merchant['type_id'] = $search_type;
            }
            $type_list=  M('Merchant_type')->where(array('type_status'=>0))->order('sort DESC, add_time DESC')->select();
        } else {
            $type_list = array();
        }
        $this->assign('type_list',$type_list);
		$database_merchant = D('Merchant');

		$count_merchant = $database_merchant->where($condition_merchant)->count();
		import('@.ORG.system_page');
		$p = new Page($count_merchant,15);
		$merchant_list = $database_merchant->field(true)->where($condition_merchant)->order('`mer_id` DESC')->limit($p->firstRow.','.$p->listRows)->select();
        if($this->config['open_c2b_spread'] == 1) {
            foreach ($merchant_list as &$mer) {
                if ($mer['spread_code'] != '') {
                    $user = M('User')->where(array('spread_code' => $mer['spread_code']))->find();
                    $mer['user_nickname'] = $user['nickname'];
                    $mer['user_phone'] = $user['phone'];
                    $mer['user_uid'] = $user['uid'];
                }
            }
        }
		$this->assign('merchant_list',$merchant_list);
		$pagebar = $p->show();
		$this->assign('pagebar',$pagebar);

		$this->display();
    }
	public function add(){
		if($this->system_session['area_type']==3){

			$now_area = D('Area')->field('area_name,`area_pid`')->where(array('area_id'=>$this->system_session['area_id']))->find();
			$merchant['city_id'] = $now_area['area_pid'];
			$merchant['area_name'] = $now_area['area_name'];
			$now_city = D('Area')->field('area_name,`area_pid`')->where(array('area_id'=>$merchant['city_id']))->find();
			$merchant['province_id'] = $now_city['area_pid'];
			$merchant['city_name'] = $now_city['area_name'];
			$now_province = D('Area')->field('area_name,`area_pid`')->where(array('area_id'=>$merchant['province_id']))->find();
			$merchant['province_name'] = $now_province['area_name'];
			$now_street = D('Area_street')->where(['area_id'=>$this->system_session['street_id']])->getField('area_name');
			$merchant['street_name'] = $now_street;

			$this->assign('merchant',$merchant);
		}
        // 根据开关获取获取商家类型信息
        if ($this->config['merchant_type_switch'] == 1) {
            $type_list=  M('Merchant_type')->where(array('type_status'=>0))->order('sort DESC, add_time DESC')->select();
        } else {
            $type_list = array();
        }
        $this->assign('type_list',$type_list);
		$this->assign('system_session',$this->system_session);
		$this->assign('bg_color','#F3F3F3');
		if($this->config['open_bd_spread'] == '1'){
        	$bd_list = M("Bd")->where(['type' => 2, 'is_del'=>0, 'status'=>1])->select();//业务经理
        	$bd_son_list = M("Bd")->where(['type' => 1, 'is_del'=>0, 'status'=>1])->select();//业务员
        	$this->assign("bd_list", $bd_list);
        	$this->assign("bd_son_list", $bd_son_list);
        }

		//获取上级管理员
		$super_admins = D('Admin')->field('id,IF(realname=\'\',account,realname) AS show_name')->where(['status' => 1, 'level' => ['NEQ', 2]])->select();
		$this->assign('super_admins', $super_admins);
		$this->display();
	}
	public function modify(){
		if(IS_POST){
			$_POST['account'] = trim($_POST['account']);
			if($_POST['pwd']){
				//校验密码
				$check_password = verifyPassword($_POST['pwd']);
				if($check_password['code']!=0){
					$this->error($check_password['msg']);
				}
			}
			$_POST['pwd'] = md5($_POST['pwd']);
			$_POST['reg_time'] = $_SERVER['REQUEST_TIME'];
			$_POST['reg_ip'] = get_client_ip(0);
			$_POST['from'] = '1';
			$_POST['area_id'] = $_POST['area_id'];
			if($_POST['merchant_end_time']){
				$_POST['merchant_end_time'] = strtotime($_POST['merchant_end_time']);
			}else{
				$_POST['merchant_end_time'] = '0';
			}
                        //$_POST['is_new_auth'] = 1;
			$database_merchant = D('Merchant');
			if ($database_merchant->field(true)->where(array('account' => htmlspecialchars($_POST['account'])))->find()) {
				$this->error('账号已存在，请更换！');
			}
            //手机号唯一判断
            if ($_POST['phone'] && !$database_merchant->phoneIsUnique($_POST['phone'], [])) {
                $this->error(L_('联系电话已绑定其他商户'));
            }

			if($menus = M('Authority_group')->where(array('is_default'=>1))->find()){
				$_POST['menus']=$menus['menus'];
				$_POST['authority_group_id']=$menus['id'];
			}

			if($this->config['nmgzhcs_appid']){
				$url= $this->config['nmgzhcs_base_url'].'/api/business_login/index';

				$appid = $this->config['nmgzhcs_appid'];
				$appsecret = $this->config['nmgzhcs_appsecret'];

				$params=array(
						'appid'=>$appid,
						'username'=>$_POST['nmgzhcs_account'],
						'pwd'=>$_POST['nmgzhcs_passwd'],
						'deviceCode'=>get_client_ip(1),
				);
				ksort($params);
				$paramsJoined = array();
				foreach($params as $param => $value) {
					$paramsJoined[] = "$param=$value";
				}
				$paramData = implode('&', $paramsJoined);

				$sign = strtoupper(md5($paramData.$appsecret));

				$params['sign'] = $sign;


				$res = httpRequest($url,'POST',$params,$this->header);
				$res = json_decode($res[1],true);
				if(empty($res['business_user_id'])){
					$this->error('接口方未查找到账号对应的用户ID，无法添加');
				}

				$_POST['nmgzhcs_user_id'] = $res['business_user_id'];
				$_POST['nmgzhcs_appid'] = $res['data']['appid'];
				$_POST['money'] = $res['data']['money_sum'];
			}

			if($this->config['open_bd_spread'] == '1'){
				if($_POST['bd_type'] == '1'){
					$_POST['bd_id'] = $_POST['bd_son_id'];
				}
			}
			if($insert_id=$database_merchant->data($_POST)->add()){
				D("System_log")->add_log("增加了商家，商家ID：".$insert_id,"商家管理",$insert_id);
				D('Scroll_msg')->add_msg('mer_reg',$insert_id,'商家'.str_replace_name($_POST['name']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']). '注册成功！');
				M('Merchant_score')->add(array('parent_id'=>$insert_id,'type'=>1));
				//添加分佣比例记录
				M('Merchant_percent_rate')->add(array('mer_id'=>$insert_id));
				$this->success('添加成功！');
			}else{
				$this->error('添加失败！请重试~');
			}
		}else{
			$this->error('非法提交,请重新提交~');
		}
	}
	public function edit(){
		$this->assign('bg_color','#F3F3F3');
		$merchant_percent_menu = M('System_menu')->where(array('module'=>'Merchant_percent'))->find();

		$can_percent = 0;
		if(in_array($merchant_percent_menu['id'],$this->system_session['menus']) || $this->system_session['level']==2 ){
			$can_percent = 1;
		}
		$this->assign('can_percent',$can_percent);

		$database_merchant = D('Merchant');
		$condition_merchant['mer_id'] = intval($_GET['mer_id']);
		$merchant = $database_merchant->field(true)->where($condition_merchant)->find();
		if(!empty($merchant['try_end_time'])) {
			$merchant['merchant_end_time'] = date('Y-m-d H:i',$merchant['try_end_time']);
		}else if(empty($merchant['merchant_end_time'])){
			$merchant['merchant_end_time'] = '';
		}else{
			$merchant['merchant_end_time'] = date('Y-m-d H:i',$merchant['merchant_end_time']);
		}
		if(empty($merchant)){
			$this->frame_error_tips('数据库中没有查询到该商家的信息！');
		}


		$now_area = D('Area')->field('area_name,`area_pid`')->where(array('area_id'=>$merchant['area_id']))->find();
		$merchant['city_id'] = $now_area['area_pid'];
		$merchant['area_name'] = $now_area['area_name'];
		$now_city = D('Area')->field('area_name,`area_pid`')->where(array('area_id'=>$merchant['city_id']))->find();
		$merchant['province_id'] = $now_city['area_pid'];
		$merchant['city_name'] = $now_city['area_name'];
		$now_province = D('Area')->field('area_name,`area_pid`')->where(array('area_id'=>$merchant['province_id']))->find();
		$merchant['province_name'] = $now_province['area_name'];

        $merchant['street_name']='';
        if($merchant['street_id'] > 0){
            $merchant['street_name'] = D('Area_street')->where(['area_id'=>$merchant['street_id']])->getField('area_name');
        }


		// 获取下推广码
        if($this->config['merchant_recommend_wholesale_switch'] == 1 && empty($merchant['mer_code'])){
            $mer_code = $this->make_code();
            M('Merchant')->where(array('mer_id'=>$merchant['mer_id']))->setField('mer_code',$mer_code);
            $merchant['mer_code'] = $mer_code;
        }

        if(trim($merchant['tianque_mno']) != ''){
        	import('ORG.Net.Http');
        	$http = new Http();
        	try{
        		$curl_get = $http->curlGet($this->config['site_url'].'/v20/public/index.php/pay/pay/getTianqueUrl?mno='.trim($merchant['tianque_mno']));
        		$curl_res = json_decode($curl_get, true);
        		if(isset($curl_res['url']) && $curl_res['url'] != ''){
        			$merchant['tianque_mno_url'] = $this->config['site_url'].'/v20/public/index.php/pay/index/getTianqueUrl?mno='.trim($merchant['tianque_mno']);
        		}
        		elseif(isset($curl_res['already'])){
        			$merchant['tianque_mno_status'] = 1;
        		}
        		else{
        			$merchant['tianque_mno_status'] = 2;
        		}
        	}
        	catch(Exeception $e){

        	}
        }

        //自定义表单数据
        $merchant['diy_form_data'] = $merchant['diy_form_data'] ? unserialize($merchant['diy_form_data']) : [];
        //dump($merchant['diy_form_data']);exit;
        //替换营业执照证书照片、身份证正面照、身份证反面照显示不对
        $merchant['trading_certificate_image_info'] = D('Merchant')->replace_merchant_img($merchant['trading_certificate_image']);
        $merchant['id_card_front_info'] = D('Merchant')->replace_merchant_img($merchant['id_card_front']);
        $merchant['id_card_reverse_info'] = D('Merchant')->replace_merchant_img($merchant['id_card_reverse']);
		//利楚扫呗配置
		$lichu = 0;
		if(C('config.lichu_open')){
			$lichu = 1;
		}
		$this->assign('lichu_config',$lichu);
		$this->assign('merchant',$merchant);
		$this->assign('system_session',$this->system_session);

		$home_share = D('Home_share')->where(array('mer_id' => $condition_merchant['mer_id']))->find();
		$this->assign('home_share', $home_share);
		$condition = array('invit_code'=>array('neq',''));
		if($this->system_session['area_id']){
			$system_area_id = $this->system_session['area_id'];

			if($system_area_id){
				$system_area = M('Area')->where(array('area_id'=>$system_area_id))->find();
				$area_arr = $this->get_area_arr( $system_area_id,$system_area['area_type']+1);
				$condition['area_id'] = array('in',$area_arr);
			}
		}

		$admin = M('Admin')->field('realname,invit_code')->where($condition)->select();
		$this->assign('admin',$admin);

		$list=  M('Authority_group')->where(array('gid'=>1))->select();
		$this->assign('authority_group', $list);

		// 根据开关获取获取商家类型信息
        if ($this->config['merchant_type_switch'] == 1) {
            $type_list=  M('Merchant_type')->where(array('type_status'=>0))->order('sort DESC, add_time DESC')->select();
        } else {
            $type_list = array();
        }
        $this->assign('type_list', $type_list);


        if ($this->system_session['level'] < 2 && $this->system_session['menus']) {
            $draw_in_settings = D('System_menu')->where(array('module' => 'Merchant_percent'))->find();
            $id = $draw_in_settings['id'];
            if ($draw_in_settings && in_array($id, $this->system_session['menus'])) {
                $draw_in_settings = true;
            } else {
                $draw_in_settings = false;
            }
        } else {
            $draw_in_settings = true;
        }
        $this->assign('draw_in_settings', $draw_in_settings);

		//超级管理员支持修改账号
		$this->assign('can_edit_account', $this->system_session['level'] == 2);

        if ($this->config['open_bd_spread_new'] == '1') {
            $jointable = C('DB_PREFIX') . 'new_marketing_person';
            $bd_list = M("New_marketing_person_manager")->join('as a LEFT JOIN ' . $jointable . ' as b on a.person_id = b.id')->where(['a.is_del' => 0, 'a.team_id' => ['neq', 0], 'b.is_del' => 0])->field('b.id as bd_id,b.name as bd_name')->select();//业务经理
            $bd_son_list = M("New_marketing_person_salesman")->join('as a LEFT JOIN ' . $jointable . ' as b on a.person_id = b.id')->where(['a.is_del' => 0, 'b.is_del' => 0])->field('b.id as bd_id,b.name as bd_name')->select();//业务员
            $this->assign("bd_list", $bd_list);
            $this->assign("bd_son_list", $bd_son_list);
        } else if ($this->config['open_bd_spread'] == '1') {
        	$bd_list = M("Bd")->where(['type' => 2, 'is_del'=>0, 'status'=>1])->select();//业务经理
        	$bd_son_list = M("Bd")->where(['type' => 1, 'is_del'=>0, 'status'=>1])->select();//业务员
        	$this->assign("bd_list", $bd_list);
        	$this->assign("bd_son_list", $bd_son_list);
        }

		//增加拉卡拉信息展示
		if (C('config.lakala_pay_switch')) {
			$qianzhangtong = M('Lakala_qianzhangtong_archives')->where(['item_type' => 'merchant', 'item_id' => intval($_GET['mer_id'])])->find();
			$this->assign('qianzhangtong', $qianzhangtong);
		}

		//获取上级管理员
		$super_admins = D('Admin')->field('id,IF(realname=\'\',account,realname) AS show_name')->where(['status' => 1, 'level' => ['NEQ', 2]])->select();
		$this->assign('super_admins', $super_admins);
		$this->assign('open_chinaums_without_asset',C('config.open_chinaums_without_asset')?:'');
		$this->display();
	}
    // 生成推广码 改为纯数字暂定为 100000-999999  记得对应注册商家的位置添加对应判断-pc和App端
    private function make_code()
    {
        $str = mt_rand(100000,999999);
        if(!M('Merchant')->where(array('mer_spread_code'=>$str))->find()){
            return $str;
        }else{
            return  $this->make_code();
        }
    }

	public function get_area_arr($fid,$level=2,$tmp=array()){
		if($level>3){
			return true ;
		}
		$area = M('Area')->where(array('area_pid'=>$fid))->select();
		foreach ($area as $v) {

			$tmp[]= $v['area_id'];
			if($level<3){
				$tmp = $this->get_area_arr($v['area_id'],$v['area_type']+1,$tmp);
			}
		}
		return $tmp;
	}

	public function amend(){
		if(IS_POST){
			//超级管理员才支持修改商家账号
			if ($this->system_session['level'] == 2) {
				$merId = $_POST['mer_id'];
				$account = $_POST['account'];
				if (empty($account)) {
					$this->error('商家账号不能为空');
				}
				$checkMerchant = M('Merchant')->where(['account' => $account, 'mer_id' => ['NEQ', $merId]])->find();
				if ($checkMerchant) {
					$this->error('商家账号已被其他商家（商家ID:' . $checkMerchant['mer_id'] . '）使用，请更换');
				}
			} else {
				unset($_POST['account']);
			}

			if($_POST['pwd']){
				//校验密码
				$check_password = verifyPassword($_POST['pwd']);
				if($check_password['code']!=0){
					$this->error($check_password['msg']);
				}
				$_POST['pwd'] = md5($_POST['pwd']);
				$_POST['update_pwd_time'] = time();
			}else{
				unset($_POST['pwd']);
			}

    		$data['a_href'] = isset($_POST['a_href']) && $_POST['a_href'] ? htmlspecialchars_decode($_POST['a_href']) : $this->config['site_url'] . '/wap.php?c=Index&a=index&token=' . $_POST['mer_id'];
    		$data['a_name'] = isset($_POST['a_name']) && $_POST['a_name'] ? htmlspecialchars($_POST['a_name']) : '进入';
    		$data['title'] = isset($_POST['a_title']) && $_POST['a_title'] ? htmlspecialchars($_POST['a_title']) : '您是' . $_POST['name'] . '的粉丝';
    		unset($_POST['a_name'], $_POST['a_title'], $_POST['a_href']);

			$database_merchant = D('Merchant');
			$old = $database_merchant->where(['mer_id'=>$_POST['mer_id']])->find();
			if($_POST['authority_group_id']>0 && $old['authority_group_id'] != $_POST['authority_group_id']){
				$group = M('Authority_group')->where(array('id'=>$_POST['authority_group_id']))->find();
				$_POST['menus'] =$group['menus'];
			}
			$now_merchant = $database_merchant->where(array('mer_id'=>$_POST['mer_id']))->find();

			if($_POST['merchant_end_time']){
				if($now_merchant['is_try']){
					$_POST['try_end_time'] = strtotime($_POST['merchant_end_time']);
                    $_POST['merchant_end_time'] = '0';
				}else{
					$_POST['merchant_end_time'] = strtotime($_POST['merchant_end_time']);
					if($_POST['merchant_end_time'] < time()){
						$this->error('该商家服务时间已到期，请先延长服务时间后再修改。');
					}
				}
			}else{
				$_POST['try_end_time'] = '0';
				$_POST['merchant_end_time'] = '0';
			}
			if ($_POST['discount_percent'] > 0 && ($_POST['discount_order_num'] > 0 && $_POST['discount_order_num'] > $now_merchant['discount_num'] || $_POST['discount_order_num'] == 0)) {
			    $_POST['is_discount'] = 1;
			}
			if ($_POST['mer_spread_code'] && $_POST['mer_spread_code'] == $now_merchant['mer_code']) {
                $this->error('不能填写商家自身推广码');
            } elseif ($this->config['merchant_recommend_wholesale_switch'] != 1) {
			    // 如果关闭了推广功能开关，去除后台填写的推广码
                unset($_POST['mer_spread_code']);
            }

			if($_POST['invit_code'] && !M('Admin')->where(array('invit_code'=>$_POST['invit_code']))->find()){
				$this->error('该邀请码不存在');
			}

			//手机号唯一判断
            if ($_POST['phone'] && !$database_merchant->phoneIsUnique($_POST['phone'], [$_POST['mer_id']])) {
                $this->error(L_('联系电话已绑定其他商户'));
            }

			// 判断是否有改动商家平台积分
            if ($_POST['sore_count'] && floatval($_POST['sore_count']) > 0) {
			    if (empty($_POST['remark'])) {
                    $this->error('更改积分时请填写备注');
                }
                $mer_id = $_POST['mer_id'];
			    $set_type = intval($_POST['set_type']) ? intval($_POST['set_type']) : 0;
                $sore_count = floatval($_POST['sore_count']);
                $platform_score = $database_merchant->where(array('mer_id'=>$mer_id))->getField('platform_score');
                if ($set_type == 1) {
                    if ($platform_score < $sore_count) {
                        $this->error('减少的积分不能大于现有的积分');
                    }
                }
                $data_sms_buy_order['operation_type'] = 1;
                $data_sms_buy_order['set_type'] = $_POST['set_type'] ? $_POST['set_type'] : 0;
                $data_sms_buy_order['mer_id'] = $mer_id;
                $data_sms_buy_order['sore_count'] = $sore_count;
                $data_sms_buy_order['add_time'] = $_SERVER['REQUEST_TIME'];
                $data_sms_buy_order['pay_type'] = 'system';// 管理员操作
                $data_sms_buy_order['operation'] = $this->system_session['id'];// 管理员uid
                $data_sms_buy_order['paid'] = '2'; // 管理员操作
                $data_sms_buy_order['orderid'] = 'mer_'.date("ymdHis").rand(10,99).sprintf("%06d",$mer_id);
                $data_sms_buy_order['remark'] = $_POST['remark']; // 更改余额填写备注
                M('Merchant_score_order')->data($data_sms_buy_order)->add();
                if ($data_sms_buy_order['set_type'] == 0) {
                    $database_merchant->where(array('mer_id'=>$mer_id))->setInc('platform_score', $sore_count);
                } else {
                    $database_merchant->where(array('mer_id'=>$mer_id))->setDec('platform_score', $sore_count);
                }
            }

			// 判断是否有改动商家积分
            if ($_POST['merchant_score'] && floatval($_POST['merchant_score']) > 0 && ($this->system_session['level'] == 2 || in_array(646, $this->system_session['menus']) )) {
			    if (empty($_POST['merchant_score_remark'])) {
                    $this->error('更改积分时请填写备注');
                }
                $mer_id = $_POST['mer_id'];
			    $set_merchant_score_type = intval($_POST['set_merchant_score_type']) ? intval($_POST['set_merchant_score_type']) : 0;
                $merchant_score_edit = floatval($_POST['merchant_score']);
                unset($_POST['merchant_score']);
                $merchant_score = $database_merchant->where(array('mer_id'=>$mer_id))->getField('merchant_score');
                if ($set_merchant_score_type == 1) {
                    if ($merchant_score < $merchant_score_edit) {
                        $this->error('减少的积分不能大于现有的积分');
                    }
                }
                $data_sms_buy_order = [];
                $data_sms_buy_order['set_type'] = $_POST['set_merchant_score_type'] ? $_POST['set_merchant_score_type'] : 0;
                $data_sms_buy_order['mer_id'] = $mer_id;
                $data_sms_buy_order['score'] = $merchant_score_edit;
                $data_sms_buy_order['add_time'] = $_SERVER['REQUEST_TIME'];
                $data_sms_buy_order['order_type'] = 'system_operate';// 管理员操作
                $data_sms_buy_order['operation'] = $this->system_session['id'];// 管理员uid
                $data_sms_buy_order['real_orderid'] = 'mer_'.date("ymdHis").rand(10,99).sprintf("%06d",$mer_id);
                $data_sms_buy_order['remark'] = $_POST['merchant_score_remark']; // 更改余额填写备注
                M('Merchant_get_score_order_log')->data($data_sms_buy_order)->add();

                if ($data_sms_buy_order['set_type'] == 0) {
                    $database_merchant->where(array('mer_id'=>$mer_id))->setInc('merchant_score', $merchant_score_edit);
                } else {
                    $database_merchant->where(array('mer_id'=>$mer_id))->setDec('merchant_score', $merchant_score_edit);
                }
            }else{
            	if(isset($_POST['merchant_score'])){
            		unset($_POST['merchant_score']);
            	}
            }

			if($this->config['nmgzhcs_appid']){
				$url= $this->config['nmgzhcs_base_url'].'/api/business_login/index';

				$appid = $this->config['nmgzhcs_appid'];
				$appsecret = $this->config['nmgzhcs_appsecret'];

				$params=array(
						'appid'=>$appid,
						'username'=>$_POST['nmgzhcs_account'],
						'pwd'=>$_POST['nmgzhcs_passwd'],
						'deviceCode'=>get_client_ip(1),
				);
				ksort($params);
				$paramsJoined = array();
				foreach($params as $param => $value) {
					$paramsJoined[] = "$param=$value";
				}
				$paramData = implode('&', $paramsJoined);

				$sign = strtoupper(md5($paramData.$appsecret));

				$params['sign'] = $sign;


				$res = httpRequest($url,'POST',$params,$this->header);
				$res = json_decode($res[1],true);
				if(empty($res['business_user_id'])){
					$this->error('接口方未查找到账号对应的用户ID，无法添加');
				}

				$_POST['nmgzhcs_user_id'] = $res['business_user_id'];
				$_POST['nmgzhcs_appid'] = $res['data']['appid'];
				$_POST['money'] = $res['data']['money_sum'];
			}

			// 绑定社区id
			if ($_POST['village_ids']) {
				$_POST['village_ids'] = str_replace('，', ',', $_POST['village_ids']);
			}
			$mer_id = $_POST['mer_id'];
			$before = $database_merchant->where(['mer_id' => $mer_id])->find();

			if($this->config['open_bd_spread_new'] == '1' || $this->config['open_bd_spread'] == '1'){
				if($_POST['bd_type'] == '1'){
					$_POST['bd_id'] = $_POST['bd_son_id'];
				}
				if ($this->config['open_bd_spread_new'] == '1' && $_POST['bd_id'] > 0) {
                    if ($_POST['bd_type'] == '1') {
                        $personData = D('New_marketing_person_salesman')->where(['person_id' => $_POST['bd_id'], 'is_del' => 0])->field('person_id,team_id')->find() ?? [];
                    } else {
                        $personData = D('New_marketing_person_manager')->where(['person_id' => $_POST['bd_id'], 'is_del' => 0])->field('person_id,team_id')->find() ?? [];
                    }
                    if (!$personData) {
                        $this->error('业务员有误请检查');
                    }
                    D('New_marketing_person_mer')->where(['mer_id' => $mer_id, 'type' => 0])->delete();
                        D('New_marketing_person_mer')->data([
                            'mer_id' => $mer_id,
                            'person_id' => $personData['person_id'],
                            'team_id' => $personData['team_id'],
                            'type' => 0,
                            'add_time' => time()
                        ])->add();
                    D('New_marketing_join_log')->data([
                        'invite_id' => $personData['person_id'],
                        'mer_id' => $mer_id,
                        'type' => 1,
                        'add_time' => time()
                    ])->add();
                }
			}

			$database_merchant->data($_POST)->save();
			$after = $database_merchant->where(['mer_id' => $mer_id])->find();
			D("System_log")->add_log("编辑了商家信息，商家ID：".$mer_id,"商家信息",$mer_id,$before, $after);
            if ($_POST['status'] == 0) {
                M('Plat_recommend')->where(array('mer_id' => intval($_POST['mer_id'])))->delete();
            }

			if($_POST['status']==1){
				if( $this->config['open_score_fenrun'] && $now_merchant['spread_code']&& $now_merchant['uid']==0){
					$date_user['phone'] =  $now_merchant['phone'];
					$date_user['nickname'] =  $now_merchant['name'];
					$date_user['pwd'] = md5('123456');
					if($user = D('User')->where(array('phone'=>$date_user['phone'] ))->find()){

					}else{
						$user = D('User')->autoreg($date_user);
					}
					$database_merchant->where(array('mer_id'=>$_POST['mer_id']))->setField('uid',$user['uid']);

					if($spread_user = M('User')->where(array('spread_code'=>$now_merchant['spread_code']))->find()){
						// D('User_spread')->data(array('spread_openid'=>$spread_user['openid'],'spread_uid'=>$spread_user['uid'],'openid'=>'','uid'=>$user['uid']))->add();
						D('User_spread')->data(array('spread_uid' => $spread_user['uid'], 'uid' => $user['uid'], 'add_time' => time()))->add();
						$Res = D('Fenrun')->add_recommend_award($spread_user['uid'],$_POST['mer_id'],2,$this->config['spread_mer_give_money'],'推荐商家注册奖励');
					}
				}

				if(empty($_POST['authority_group_id'])){
//					if($menus = M('Authority_group')->where(array('is_default'=>1))->find()){
					if(!$now_merchant['menus'] && $menus = M('Authority_group')->where(array('is_default'=>1))->find()){
						$date_menus['menus']=$menus['menus'];
						$date_menus['authority_group_id']=$menus['id'];
						$database_merchant->where(array('mer_id'=>$_POST['mer_id']))->save($date_menus);
					}
				}else if($_POST['authority_group_id']!=$now_merchant['authority_group_id']){
					$menus = M('Authority_group')->where(array('authority_group_id'=>$_POST['authority_group_id']))->find();
					$date['label'] = $_POST['authority_group_id'];
					$date['mer_id'] = $_POST['mer_id'];
					$date['add_time'] = time();
					$date['last_time'] = time();
					$date['money'] = 0;
					$date['is_try'] = intval($menus['is_try']);
					$date['num'] =1;
					$date['paid'] =3;
					$order_id = M('Authority_group_order')->add($date);
				}

				if($this->config['open_distributor']==1 && $now_merchant['spread_code']){

					D('Distributor_agent')->agent_spread_log($_POST['mer_id']);
				}

				if($this->config['open_c2b_spread'] && $now_merchant['spread_code']&& $now_merchant['is_spread_reward']==0){
                    if($spread_user = M('User')->where(array('spread_code'=>$now_merchant['spread_code']))->find()){
                        //0:平台余额|1:积分|2:两者都送|3:两者都不送
                        if($this->config['register_c2b_give_money_type']==0){
                            D('User')->add_money($spread_user['uid'],$this->config['spread_c2b_mer_give_money'],'推荐商家注册奖励');
                        }elseif($this->config['register_c2b_give_money_type']==1){
                            D('User')->add_score($spread_user['uid'], $this->config['spread_c2b_mer_give_score'], '推荐商家注册奖励');
                        }elseif($this->config['register_c2b_give_money_type']==2){
                            D('User')->add_money($spread_user['uid'],$this->config['spread_c2b_mer_give_money'],'推荐商家注册奖励');
                            D('User')->add_score($spread_user['uid'], $this->config['spread_c2b_mer_give_score'], '推荐商家注册奖励');
                        }
                        $database_merchant->where(array('mer_id'=>$_POST['mer_id']))->setField('is_spread_reward',1);
                    }
                }
                D('Scroll_msg')->add_msg('mer_reg',$now_merchant['mer_id'],'商家'.str_replace_name($now_merchant['name']).'于'.date('Y-m-d H:i',$now_merchant['reg_time']). '注册成功！');
			}else if($_POST['status'] == 0){
				//商家下的店铺全部禁用
				M('Merchant_store')->where(['mer_id'=>$_POST['mer_id']])->save([
					'status' => 0,
					'have_mall'	=>	0,
					'have_group' => 0,
					'have_meal'	=>	0,
					'have_shop'	=>	0
				]);
			}
			/*审核列表编辑后，开启短信推送*/
			$is_verify=I('is_verify',0,'intval');
			if($is_verify==1 && C('config.sms_merchant_verify')){
				$data=array_merge($now_merchant,$_POST);
				$this->merchant_verify_sms($data,$_POST['status']);
			}

			$home_share = D('Home_share')->where(array('mer_id' => $_POST['mer_id']))->find();
			if ($home_share) {
				D('Home_share')->where(array('mer_id' => $_POST['mer_id']))->save($data);
			} else {
				$data['mer_id'] = $_POST['mer_id'];
				D('Home_share')->add($data);
			}
			if($_POST['is_open_scenic']){
				$scenic_list	=	M('Scenic_list')->where(array('company_id'=>$_POST['mer_id']))->find();
				if(empty($scenic_list)){
					$arr	=	array(
						'company_id'	=>	$_POST['mer_id'],
						'add_time'		=>	$_SERVER['REQUEST_TIME'],
						'update_time'	=>	$_SERVER['REQUEST_TIME'],
					);
					$add	=	M('Scenic_list')->data($arr)->add();
					if(empty($add)){
						//$this->error('开通景区失败，请联系管理员！');
					}
				}
			}
			$this->success('修改成功！');
// 			}else{
// 				$this->error('修改失败！请检查内容是否有过修改（必须修改）后重试~');
// 			}
		}else{
			$this->error('非法提交,请重新提交~');
		}
	}

	/*商家入驻审核通过提醒*/
	public function merchant_verify_sms($now_merchant,$status=0){
		$phone=$now_merchant['phone'];
		$name=$now_merchant['name'];
		if(!$phone || !C('config.sms_merchant_verify'))return false;

		$link = S('merchantWapIndexUrl');
		if(empty($link)){
			$link = C('config.site_url').'/packapp/merchant/index.html';

			$res = D('Access_token_expires')->get_access_token();
			if($res['access_token']){
				$send_to_url = 'https://api.weixin.qq.com/cgi-bin/shorturl?access_token='.$res['access_token'];
				$postData = array(
					'action' => 'long2short',
					'long_url' => $link
				);
				import('ORG.Net.Http');
				$http = new Http();

				$return = Http::curlPostOwn($send_to_url, json_encode($postData));
				$returnArr = json_decode($return,true);
				if($returnArr['short_url']){
					$link = $returnArr['short_url'];
					S('merchantWapIndexUrl',$link);	//生成好了短链接，才永久缓存。
				}
			}
		}

		if($status==1){
			$content='恭喜您申请的商家（'.$name.'）入驻审核通过！请登录移动端商家中心官网'.$link.'查看。';
		}else{
			$content='您申请的商家（'.$name.'）入驻审核不通过，如有异议请联系平台管理人员！';
		}
		$sms_data=array();
		$sms_data['mer_id']=$now_merchant['mer_id'];
		$sms_data['store_id']=0;
		$sms_data['type']='system';
		$sms_data['uid'] = 0;
        $sms_data['mobile'] = $phone;
        $sms_data['sendto'] = 'merchant';
        $sms_data['content'] =$content ;
        Sms::sendSms($sms_data);
    }

	public function del(){
		if(IS_POST){
			$database_merchant_store = D('Merchant_store');

			$store_count=M('Merchant_store')->where(array('mer_id'=>$_POST['mer_id'],'status'=>array('neq','4')))->count(1);
			if($store_count>0){
				$this->error('抱歉，该商家下存在店铺，请先删除店铺！');
			}

			$condition_merchant_store['mer_id'] = intval($_POST['mer_id']);
			$data_merchant_store['status'] = 4;
			$data_merchant_store['last_time'] = $_SERVER['REQUEST_TIME'];
			/**改软删除*4禁用***/
			if(!$database_merchant_store->where($condition_merchant_store)->find() || $database_merchant_store->where($condition_merchant_store)->data($data_merchant_store)->save()){
				$database_merchant = D('Merchant');
				$condition_merchant['mer_id'] = intval($_POST['mer_id']);
				$now_merchant = $database_merchant->where($condition_merchant)->find();
				$data_merchant['status'] = 4;
				$data_merchant['account'] = $now_merchant['account'].'~no_use';
				$data_merchant['phone'] = $now_merchant['phone'].'~no_use';
				$data_merchant['name'] = $now_merchant['name'].'~no_use';
				$data_merchant['email'] = $now_merchant['email'].'~no_use';
				$data_merchant['last_time'] = $_SERVER['REQUEST_TIME'];
				//删除商家下所有配送员
                D('Deliver_user')->where(array('mer_id'=>$_POST['mer_id']))->delete();
				if($database_merchant->where($condition_merchant)->data($data_merchant)->save()){
					D("System_log")->add_log("删除了商家，商品ID：".$_POST['mer_id'],"商家信息",$_POST['mer_id']);
                    M('Plat_recommend')->where(array('mer_id' => intval($_POST['mer_id'])))->delete();
					$this->success('删除成功！');
				}else{
					$this->error('删除失败！请重试~');
				}
			}else{
				$this->error('商家下的店铺删除失败，请重试');
			}
		}else{
			$this->error('非法提交,请重新提交~');
		}
	}
	public function merchant_login(){
        if($this->system_session['level']<2){
            if(!in_array(310,$this->system_session['menus'])) $this->error('您没有访问的权限！');
        }
		$database_merchant = D('Merchant');
		$condition_merchant['mer_id'] = $_GET['mer_id'];
		$now_merchant = $database_merchant->field(true)->where($condition_merchant)->find();
		if(empty($now_merchant) || $now_merchant['status'] == 0 || $now_merchant['status'] == 2){
			$this->error('该商家的状态不存在！请查阅。' . ($now_merchant ? '商家名：' . $now_merchant['name'] : ''));
		}
		if(!empty($now_merchant['last_ip'])){
			import('ORG.Net.IpLocation');
			$IpLocation = new IpLocation();
			$last_location = $IpLocation->getlocation($now_merchant['last_ip']);
			$now_merchant['last']['country'] = mb_convert_encoding($last_location['country'],'UTF-8','GBK');
			$now_merchant['last']['area'] = mb_convert_encoding($last_location['area'],'UTF-8','GBK');
            $now_merchant['login_type'] = "fake_login";  //系统后台伪登录标识
		}
		session('merchant',$now_merchant);
		session('staff',null);
		$script_name = trim($_SERVER['SCRIPT_NAME'],'/');
		if($_GET['group_id']){
			redirect($this->config['site_url'].'/merchant.php?c=Group&a=frame_edit&group_id='.$_GET['group_id'].'&system_file='.$script_name);
		}else if($_GET['activity_id']){
			redirect($this->config['site_url'].'/merchant.php?c=Activity&a=frame_edit&id='.$_GET['activity_id'].'&system_file='.$script_name);
		}else if($_GET['appoint_id']){
			redirect($this->config['site_url'].'/merchant.php?c=Appoint&a=frame_edit&appoint_id='.$_GET['appoint_id'].'&system_file='.$script_name);
		}else{
			redirect($this->config['site_url'].'/merchant.php');
		}
	}

    public function merchant_login_v20(){
        if($this->system_session['level']<2){
            if(!in_array(310,$this->system_session['menus'])) exit(json_encode(['status'=>0,'msg'=>'您没有访问的权限']));

        }

        $database_merchant = D('Merchant');
        $condition_merchant['mer_id'] = $_GET['mer_id'];
        $now_merchant = $database_merchant->field(true)->where($condition_merchant)->find();
        if(empty($now_merchant) || $now_merchant['status'] == 0 || $now_merchant['status'] == 2){
            exit(json_encode(['status'=>0,'msg'=>'该商家的状态不存在！请查阅。']));
        }

        if(!empty($now_merchant['last_ip'])){
            import('ORG.Net.IpLocation');
            $IpLocation = new IpLocation();
            $last_location = $IpLocation->getlocation($now_merchant['last_ip']);
            $now_merchant['last']['country'] = mb_convert_encoding($last_location['country'],'UTF-8','GBK');
            $now_merchant['last']['area'] = mb_convert_encoding($last_location['area'],'UTF-8','GBK');
            $now_merchant['login_type'] = "fake_login";  //系统后台伪登录标识
        }
        session('merchant',$now_merchant);
        session('staff',null);
        $script_name = trim($_SERVER['SCRIPT_NAME'],'/');
        $returnArr = [
            'status' => 1
        ];

        import("@.ORG.Token");
        $ticket = Token::createToken($_GET['mer_id']);
        setcookie('merchant_access_token', $ticket, -1, '/');

        $returnArr['url'] = $this->config['site_url'].'/v20/public/platform/#/merchant/merchant.iframe/menu_999999';
        $returnArr['ticket'] = $ticket;
        exit(json_encode(['status'=>0,'data'=>$returnArr]));
    }

    public function storestaff_login_v20(){
        if($this->system_session['level']<2){
            if(!in_array(310,$this->system_session['menus'])) exit(json_encode(['status'=>0,'msg'=>'您没有访问的权限！']));
        }
        $database_store_staff = M('Merchant_store_staff');
        $condition_store_staff['store_id'] = $_GET['store_id'];
        $now_staff = $database_store_staff->where($condition_store_staff)->order('`type` DESC,`id` DESC')->find();
        if(!$now_staff){
            exit(json_encode(['status'=>0,'msg'=>'该店铺没有找到店员！']));
        }

        $data_store_staff['id'] = $now_staff['id'];
        $data_store_staff['last_time'] = $_SERVER['REQUEST_TIME'];
        $database_store_staff->data($data_store_staff)->save();

        session('staff',$now_staff);
        session('merchant',null);

        $returnArr = [
            'status' => 1
        ];

        import("@.ORG.Token");
        $ticket = Token::createToken($now_staff['id']);
        setcookie('storestaff_access_token', $ticket, -1, '/');

        $returnArr['url'] = $this->config['site_url'].'/v20/public/platform/#/storestaff/storestaff.index/index';
        exit(json_encode(['status'=>0,'data'=>$returnArr]));
    }

	// 商品管理登录
	public function goods_merchant_login(){
		$database_merchant = D('Merchant');
		$condition_merchant['mer_id'] = $_GET['mer_id'];
		$now_merchant = $database_merchant->field(true)->where($condition_merchant)->find();
		if(empty($now_merchant) || $now_merchant['status'] == 0 || $now_merchant['status'] == 2){
			$this->error('该商家的状态不存在！请查阅。' . ($now_merchant ? '商家名：' . $now_merchant['name'] : ''));
		}
		if(!empty($now_merchant['last_ip'])){
			import('ORG.Net.IpLocation');
			$IpLocation = new IpLocation();
			$last_location = $IpLocation->getlocation($now_merchant['last_ip']);
			$now_merchant['last']['country'] = mb_convert_encoding($last_location['country'],'UTF-8','GBK');
			$now_merchant['last']['area'] = mb_convert_encoding($last_location['area'],'UTF-8','GBK');
		}
		session('merchant',$now_merchant);
		session('staff',null);
		$script_name = trim($_SERVER['SCRIPT_NAME'],'/');
		if($_GET['group_id']){
			redirect($this->config['site_url'].'/merchant.php?c=Group&a=frame_edit&group_id='.$_GET['group_id'].'&system_file='.$script_name);
		}else if($_GET['activity_id']){
			redirect($this->config['site_url'].'/merchant.php?c=Activity&a=frame_edit&id='.$_GET['activity_id'].'&system_file='.$script_name);
		}else if($_GET['appoint_id']){
			redirect($this->config['site_url'].'/merchant.php?c=Appoint&a=frame_edit&appoint_id='.$_GET['appoint_id'].'&system_file='.$script_name);
		}
	}

	public function storestaff_login(){
        if($this->system_session['level']<2){
            if(!in_array(310,$this->system_session['menus'])) $this->error('您没有访问的权限！');
        }
		$database_store_staff = M('Merchant_store_staff');
		$condition_store_staff['store_id'] = $_GET['store_id'];
		$now_staff = $database_store_staff->where($condition_store_staff)->order('`type` DESC,`id` DESC')->find();
		if(!$now_staff){
			$this->error('该店铺没有找到店员');
		}

		$data_store_staff['id'] = $now_staff['id'];
		$data_store_staff['last_time'] = $_SERVER['REQUEST_TIME'];
		$database_store_staff->data($data_store_staff)->save();

		session('staff',$now_staff);
		session('merchant',null);
		if ($now_staff['type'] == 2) {
			$store = M('Merchant_store')->field(true)->where(array('store_id' => $now_staff['store_id']))->find();
			$database_merchant = D('Merchant');
			$condition_merchant['mer_id'] = $store['mer_id'];
			$now_merchant = $database_merchant->field(true)->where($condition_merchant)->find();

			if(!empty($now_merchant['last_ip'])){
				import('ORG.Net.IpLocation');
				$IpLocation = new IpLocation();
				$last_location = $IpLocation->getlocation($now_merchant['last_ip']);
				$now_merchant['last']['country'] = iconv('GBK','UTF-8',$last_location['country']);
				$now_merchant['last']['area'] = iconv('GBK','UTF-8',$last_location['area']);
			}
			$now_merchant['store_id'] = $now_staff['store_id'];
			session('merchant', $now_merchant);
		}
		$shop_type=I('get.shop_type',1,'intval');
		redirect($this->config['site_url'].'/store.php?g=Merchant&c=Store&a=shop_list&shop_type='.$shop_type);
	}
	/*店铺管理*/
	public function store(){
		$database_merchant = D('Merchant');
		$condition_merchant['mer_id'] = intval($_GET['mer_id']);
		$merchant = $database_merchant->field(true)->where($condition_merchant)->find();
		if(empty($merchant)){
			$this->error_tips('数据库中没有查询到该商家的信息！',5,U('Merchant/index'));
		}
		$this->assign('merchant',$merchant);

		$database_merchant_store = D('Merchant_store');
		$condition_merchant_store['mer_id'] = $merchant['mer_id'];
		$condition_merchant_store['status'] = array('neq',4);
		$count_store = $database_merchant_store->where($condition_merchant_store)->count();
		import('@.ORG.system_page');
		$p = new Page($count_store,20);
		$store_list = $database_merchant_store->field(true)->where($condition_merchant_store)->order('`sort` DESC,`store_id` ASC')->limit($p->firstRow.','.$p->listRows)->select();
		$this->assign('store_list',$store_list);
		$pagebar = $p->show();
		$this->assign('pagebar',$pagebar);

		$this->display();
	}
	public function miaoMaiGoodsSync(){
		
		$merId = I('mer_id', 0, 'intval');
		$storeId = I('store_id', 0, 'intval');
		$getPercent = I('get_percent', 0, 'intval');
		$data = D('Shop_goods')->miaoMaiGoodsSync($merId, $storeId, $getPercent);
		$this->ajaxReturn($data, 'JSON');
	}
	public function wait_store(){
		$where = array('status' => 2);
		//搜索
		if(!empty($_GET['keyword'])){
			if($_GET['searchtype'] == 'store_id'){
				$where['store_id'] =trim( $_GET['keyword']);
			}else if($_GET['searchtype'] == 'name'){
				$where['name'] = array('like','%'.trim($_GET['keyword']).'%');
			}else if($_GET['searchtype'] == 'phone'){
				$where['phone'] = array('like','%'.trim($_GET['keyword']).'%');
			}
		}
		if ($this->system_session['area_id']) {
			$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
			if($now_area['area_type']==3){
				$area_index = 'area_id';
			}elseif($now_area['area_type']==2){
				$area_index = 'city_id';
			}elseif($now_area['area_type']==1){
				$area_index = 'province_id';
			}
			$where[$area_index] = $this->system_session['area_id'];
		}
		$database = D('Merchant_store');
		$count = $database->where($where)->count();
		import('@.ORG.system_page');
		$p = new Page($count, 20);
		$list = $database->field(true)->where($where)->order('`store_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
		$this->assign('store_list', $list);
		$pagebar = $p->show();
		$this->assign('pagebar', $pagebar);

		$this->display();
    }
	public function close_store(){
		$where = array('status' => 4);
		//搜索
		if(!empty($_GET['keyword'])){
			if($_GET['searchtype'] == 'store_id'){
				$where['store_id'] =trim( $_GET['keyword']);
			}else if($_GET['searchtype'] == 'name'){
				$where['name'] = array('like','%'.trim($_GET['keyword']).'%');
			}else if($_GET['searchtype'] == 'phone'){
				$where['phone'] = array('like','%'.trim($_GET['keyword']).'%');
			}
		}
		if ($this->system_session['area_id']) {
			$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
			if($now_area['area_type']==3){
				$area_index = 'area_id';
			}elseif($now_area['area_type']==2){
				$area_index = 'city_id';
			}elseif($now_area['area_type']==1){
				$area_index = 'province_id';
			}
			$where[$area_index] = $this->system_session['area_id'];
		}
		$database = D('Merchant_store');
		$count = $database->where($where)->count();
		import('@.ORG.system_page');
		$p = new Page($count, 20);
		$list = $database->field(true)->where($where)->order('`store_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
		$this->assign('store_list', $list);
		$pagebar = $p->show();
		$this->assign('pagebar', $pagebar);

		$this->display();
    }
	public function store_add(){
		$database_merchant = D('Merchant');
		$condition_merchant['mer_id'] = intval($_GET['mer_id']);
		$merchant = $database_merchant->field(true)->where($condition_merchant)->find();
		if(empty($merchant)){
			$this->frame_error_tips('数据库中没有查询到该商家的信息！无法添加店铺。',5);
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
		}
		$this->assign('merchant',$merchant);

		$this->assign('map_default_city_id',$merchant['city_id']);

		$this->assign('bg_color','#F3F3F3');

		$this->display();
	}
	public function store_modify(){
		if(IS_POST){
			$long_lat = explode(',',$_POST['long_lat']);
			$_POST['long'] = $long_lat[0];
			$_POST['lat'] = $long_lat[1];
			$_POST['last_time'] = $_SERVER['REQUEST_TIME'];
			$_POST['add_from'] = '1';
			$database_merchant_store = D('Merchant_store');
			if($insert_id=$database_merchant_store->data($_POST)->add()){
				D("System_log")->add_log("添加了店铺，店铺ID：".$insert_id,"店铺信息",$insert_id);
				M('Merchant_score')->add(array('parent_id'=>$insert_id,'type'=>2));
				$this->success('添加成功！');
			}else{
				$this->error('添加失败！请重试~');
			}
		}else{
			$this->error('非法提交,请重新提交~');
		}
	}

	public function store_edit(){
		$database_merchant_store = D('Merchant_store');
		$condition_merchant_store['store_id'] = intval($_GET['store_id']);
		$store = $database_merchant_store->field(true)->where($condition_merchant_store)->find();
		$store_percent_menu = M('System_menu')->where(array('module'=>'Store_percent'))->find();

		$can_percent = 0;
		if($this->config['open_store_own_percent'] && (in_array($store_percent_menu['id'],$this->system_session['menus']) || $this->system_session['level']==2)){
			$can_percent = 1;
		}
		$this->assign('can_percent',$can_percent);
		if(empty($store)){
			$this->frame_error_tips('数据库中没有查询到该店铺的信息！',5);
		}
		$merchant = D('Merchant')->where(['mer_id' => $store['mer_id']])->find();
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
			
		}
		$this->assign('store',$store);
		
		$this->assign('merchant',$merchant);
		$this->assign('bg_color','#F3F3F3');
        $this->assign('open_mall_check_id_card', C('config.open_mall_check_id_card'));
		$this->display();
	}

	public function store_amend(){
		if(IS_POST){
			$long_lat = explode(',',$_POST['long_lat']);
			$_POST['long'] = $long_lat[0];
			$_POST['lat'] = $long_lat[1];
			$_POST['last_time'] = $_SERVER['REQUEST_TIME'];
			$database_merchant_store = D('Merchant_store');
			$before = $database_merchant_store->where(['store_id'=>$_POST['store_id']])->find();
			//开启店铺检测商家状态
			if($before['status'] != 1 && $_POST['status'] == 1){
				$merchant_status = M('merchant')->where(['mer_id' => $before['mer_id']])->getField('status');
				if($merchant_status != 1){
					$this->error('请先开启商家');
				}
			}
			if(C('customization.internal_membership_card') && !empty($_POST['miaomaiyun_erp_storeid']) && empty($before['miaomaiyun_erp_storeid'])){
				$bindMiaomaiStore =	$database_merchant_store
					->where([
						'store_id'=>['NEQ', $_POST['store_id']],
						'miaomaiyun_erp_storeid' => $_POST['miaomaiyun_erp_storeid']
					])
					->find();
				if($bindMiaomaiStore){
					$this->error('当前淼迈云店铺ID已存在');
				}
				$goods = D('Shop_goods')->where(['store_id'=>$_POST['store_id']])->find();
				if($goods){
					$this->error('当前店铺已有商品，无法绑定');
				}
			}
			if($database_merchant_store->data($_POST)->save()){
				$after = $database_merchant_store->where(['store_id'=>$_POST['store_id']])->find();
				D("System_log")->add_log("编辑了店铺，店铺ID：".$_POST['store_id'],"店铺信息",$_POST['store_id'], $before, $after);
                if ($_POST['status'] == 0) {
                    M('Plat_recommend')->where(array('store_id' => intval($_POST['store_id'])))->delete();
                } else { //更新 Plat_recommend 表business_able 字段
                    //业务标签
                    $business_label_arr = array();
                    if($_POST['have_group']){
                        $business_label_arr[] = msubstr(C('config.group_alias_name'),0,1,false);
                    }
                    if($_POST['have_shop']){
                        $business_label_arr[] = msubstr(C('config.shop_alias_name'),0,1,false);
                    }
                    if($_POST['have_meal']){
                        $business_label_arr[] = msubstr(C('config.meal_alias_name'),0,1,false);
                    }
                    $tmp_store['business_label'] = implode(',',$business_label_arr);
                    $update_data = ['business_label'=>$tmp_store['business_label']];
                    M('Plat_recommend')->where(array('store_id' => intval($_POST['store_id'])))->save($update_data);
                }

				$this->success('修改成功！');
			}else{
				$this->error('修改失败！请检查内容是否有过修改（必须修改）后重试~');
			}
		}else{
			$this->error('非法提交,请重新提交~');
		}
	}
	public function store_del(){
		if(IS_POST){
			$database_group_store = D('Group_store');
			$condition_group_store['store_id'] = intval($_POST['store_id']);
			if($database_group_store->where($condition_group_store)->find()){
				$this->error('该店铺下有'.$this->config['group_alias_name'].'，请先解除店铺与对应'.$this->config['group_alias_name'].'的关系才能删除！');
			}

			$database_merchant_store = D('Merchant_store');
			$condition_merchant_store['store_id'] = intval($_POST['store_id']);
			/**$database_merchant_store->where($condition_merchant_store)->delete();**改软删除*4禁用***/
			if($database_merchant_store->where($condition_merchant_store)->save(array('status'=>4))){
				D("System_log")->add_log("删除了店铺，店铺ID：".$_POST['store_id'],"店铺信息",$_POST['store_id']);
                M('Plat_recommend')->where(array('store_id' => intval($_POST['store_id'])))->delete();
				$this->success('删除成功！');
			}else{
				$this->error('删除失败！请重试~');
			}
		}else{
			$this->error('非法提交,请重新提交~');
		}
	}

	/*商家公告*/
	public function news(){
		$database_merchant_news = D('Merchant_news');
		$news_list = $database_merchant_news->order('`is_top` DESC,`add_time` DESC')->select();
		$this->assign('news_list',$news_list);

		$this->display();
	}
	public function news_add(){
		$this->assign('bg_color','#F3F3F3');

		$this->display();
	}
	public function news_modify(){
		$database_merchant_news = D('Merchant_news');
		$_POST['content'] = fulltext_filter($_POST['content']);
		$_POST['add_time'] = $_SERVER['REQUEST_TIME'];
		if($database_merchant_news->data($_POST)->add()){
			$this->success('添加成功！');
		}else{
			$this->error('添加失败！');
		}
	}
	public function news_edit(){
		$database_merchant_news = D('Merchant_news');
		$condition_merchant_news['id'] = $_GET['id'];
		$now_news = $database_merchant_news->field(true)->where($condition_merchant_news)->find();
		if(empty($now_news)){
			$this->frame_error_tips('数据库中没有查询到该条公告！',5);
		}
		$this->assign('now_news',$now_news);

		$this->assign('bg_color','#F3F3F3');

		$this->display();
	}
	public function news_amend(){
		$database_merchant_news = D('Merchant_news');
		$_POST['content'] = fulltext_filter($_POST['content']);
		$_POST['add_time'] = $_SERVER['REQUEST_TIME'];
		if($database_merchant_news->data($_POST)->save()){
			$this->success('编辑成功！');
		}else{
			$this->error('编辑失败！');
		}
	}
	public function news_del(){
		if(IS_POST){
			$database_merchant_news = D('Merchant_news');
			$condition_merchant_news['id'] = $_POST['id'];
			if($database_merchant_news->where($condition_merchant_news)->delete()){
				$this->success('删除成功！');
			}else{
				$this->error('删除失败！请重试~');
			}
		}else{
			$this->error('非法提交,请重新提交~');
		}
	}

	public function order(){
		$percent = 0;
        $period = 0;
        $time = '';
		if (!$_GET['begin_time']) {
            $mer_id = isset($_GET['mer_id']) ? intval($_GET['mer_id']) : 0;
            $type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'meal';
        }else{
            $mer_id = isset($_GET['mer_id']) ? intval($_GET['mer_id']) : 0;
            $type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'meal';
        }
		switch($type){
			case 'meal':
				$where = "mer_id=".$mer_id." AND paid=1 AND is_own=0 AND status in (1,2) AND (pay_type<>'offline' OR balance_pay<>'0.00')";
				break;
			case 'group':
				$where = "mer_id=".$mer_id." AND paid=1 AND is_own=0 AND status in (1,2,6) AND (pay_type<>'offline' OR balance_pay<>'0.00')";
				break;
			case 'weidian':
				$where = "mer_id=".$mer_id." AND paid=1 AND pay_type<>'offline'";
				break;
			case 'wxapp':
				$where = "mer_id=".$mer_id." AND paid=1 AND pay_type<>'offline'";
				break;
			case 'appoint':
				$where = "mer_id=".$mer_id." AND paid=1 AND is_own=0 AND pay_type<>'offline' AND service_status=1";
				break;
			case 'store':
				$where = "mer_id=".$mer_id." AND paid=1 AND is_own=0 AND refund=0";
				break;
			case 'waimai':
				$where = "mer_id=".$mer_id." AND paid=1 AND is_own=0 ";
				break;
			case 'shop':
				$where = "mer_id=".$mer_id." AND paid=1 AND is_own=0 AND status IN (2,3) AND (pay_type<>'offline' OR balance_pay<>'0.00' OR coupon_price<>'0.00' OR payment_money<>'0.00' OR score_deducte<>'0.00')";
				break;
		}
		if($type=='waimai'){
			$un_bill_count = D($type.'_order')->where($where." AND is_pay_bill=0 ")->count();
		}else if($type=='appoint'){
			$un_bill_count = D($type.'_order')->join('as o left join '.C('DB_PREFIX') ."appoint a ON o.appoint_id = a.appoint_id")->where("o.mer_id=".$mer_id." AND o.paid=1 AND o.is_own=0 AND o.pay_type<>'offline' AND o.service_status=1 AND o.is_pay_bill=0 AND a.payment_status=1")->count();
		}else if($type=='store'){
			$un_bill_count = D($type.'_order')->where($where." AND is_pay_bill=0 AND (payment_money<>'0.00' OR balance_pay<>'0.00') ")->count();
		}else{
			$un_bill_count = D($type.'_order')->where($where." AND is_pay_bill=0 ")->count();
		}

        $pay_time=D($type.'_order')->where($where." AND pay_time<>0 AND pay_time<>''")->field('pay_time')->order('pay_time ASC')->limit('0,1')->getField('pay_time');
        $start_year =empty($pay_time)?"":date('Y',$pay_time);
        if ($_GET['year']) {
            $time = array('year'=>$_GET['year'],'month'=>$_GET['month'],'period'=>$period);
            $time = serialize($time);
        }elseif(isset($_GET['begin_time'])&&isset($_GET['end_time'])){
            if ($_GET['begin_time']>$_GET['end_time']) {
                $this->error("结束时间应大于开始时间");
            }
            $period =array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
            $time = array('year'=>$_GET['year'],'month'=>$_GET['month'],'period'=>$period);
            $time = serialize($time);

        }
        $selected_year = isset($_GET['year'])?$_GET['year']:0;
        $selected_month = isset($_GET['month'])?$_GET['month']:0;

		$merchant = D('Merchant')->field(true)->where('mer_id=' . $mer_id)->find();
		if ($merchant['percent']) {
			$percent = $merchant['percent'];
		} elseif ($this->config['platform_get_merchant_percent']) {
			$percent = $this->config['platform_get_merchant_percent'];
		}
		$this->assign('percent', $percent);
		$result = D("Order")->get_order_by_mer_id($mer_id, $type, 1,$time,$_GET['is_pay_bill']);
		$this->assign($result);
		$this->assign('total_percent', $result['total'] * $percent * 0.01);
		$this->assign('all_total_percent', ($result['alltotal']+$result['alltotalfinsh']) * $percent * 0.01);

// 		$this->assign(D("Meal_order")->get_order_by_mer_id($mer_id, 1));
		$this->assign('un_bill_count',$un_bill_count);
        $this->assign('start_year',$start_year);
        $this->assign('selected_year',$selected_year);
        $this->assign('selected_month',$selected_month);
		$this->assign('now_merchant', $merchant);
		$this->assign('mer_id', $mer_id);
		$this->assign('type', $type);
		$this->display();
	}

	public function export_info(){
		$param = $_POST;
		$param['type'] = 'merchant_list';
		$param['rand_number'] = time();
		$param['system_session'] = $this->system_session;
		//$param['merchant_session']['mer_id'] = $this->merchant_session['mer_id'];
		if($res = D('Order')->order_export($param)){
			echo json_encode(array('error_code'=>0,'msg'=>'添加导出计划成功','file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
		}else{
			echo json_encode(array('error_code'=>1,'msg'=>'导出失败'));
		}
	}
    public function export_reply(){
        $param = $_POST;
        $param['type'] = 'reply';
        $param['rand_number'] = time();
        if($this->system_session['area_id']){
            $param['area_id'] = $this->system_session['area_id'];
        }
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>'添加导出计划成功','file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>'导出失败'));
        }
    }

	public function export()
	{
		$param = $_POST;
		$param['type'] = 'income';
		$param['rand_number'] = time();
		//$param['merchant_session']['mer_id'] = $this->merchant_session['mer_id'];
		if($res = D('Order')->order_export($param)){
			echo json_encode(array('error_code'=>0,'msg'=>'添加导出计划成功','file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
		}else{
			echo json_encode(array('error_code'=>1,'msg'=>'导出失败'));
		}
		die;
		set_time_limit(0);
		$mer_id = isset($_GET['mer_id']) ? intval($_GET['mer_id']) : 0;
		$type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'meal';
		$title = '';
		switch ($type) {
			case 'meal':
				$title = '餐饮账单';
				break;
			case 'group':
				$title = '团购账单';
				break;
			case 'weidian':
				$title = '微店账单';
				break;
			case 'wxapp':
				$title = '预约账单';
				break;
			case 'appoint':
				$title = '营销账单';
				break;
			case 'store':
				$title = '收银账单';
				break;
			case 'waimai':
				$title = '外卖账单';
				break;
			case 'shop':
				$title = $this->config['shop_alias_name'].'账单';
				break;
			case 'income':
				$title = '收入明细';
				break;
		}
		require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
		$cacheMethod = PHPExcel_CachedObjectStorageFactory:: cache_to_discISAM;
		$cacheSettings = array( 'dir' => './runtime' );
		PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
		$objExcel = new PHPExcel();
		$objProps = $objExcel->getProperties();
		// 设置文档基本属性
		$objProps->setCreator($title);
		$objProps->setTitle($title);
		$objProps->setSubject($title);
		$objProps->setDescription($title);
		// 设置当前的sheet
		$objExcel->setActiveSheetIndex(0);


		$objExcel->getActiveSheet()->setTitle($type);
		$objActSheet = $objExcel->getActiveSheet();
		$cell_meal    = array('store_name'=>'门店名称','order_id'=>'订单编号','orderid'=>'流水号', 'num'=>'数量', 'order_price'=>'金额','balance_pay'=> '余额支付金额','payment_money'=> '在线支付金额', 'coupon_price'=> '优惠券','score_deducte'=> $this->config['score_name'].'抵扣','pay_time'=>'支付时间','pay_type'=> '支付类型','bill_money'=>'应对账金额');
		$cell_group   = array('store_name'=>'门店名称','real_orderid'=>'订单编号','orderid'=>'流水号', 'num'=>'数量', 'order_price'=>'金额','balance_pay'=> '余额支付金额','payment_money'=> '在线支付金额', 'coupon_price'=> '优惠券','score_deducte'=> $this->config['score_name'].'抵扣','refund_money'=>'退款金额','refund_fee'=>'退款手续费','pay_time'=>'支付时间','pay_type'=> '支付类型','bill_money'=>'应对账金额');
		$cell_appoint = array('store_name'=>'门店名称','order_id'=>'订单编号','orderid'=>'流水号', 'num'=>'数量', 'order_price'=>'金额','balance_pay'=> '余额支付金额','payment_money'=> '在线支付金额', 'coupon_price'=> '优惠券','score_deducte'=> $this->config['score_name'].'抵扣','pay_time'=>'支付时间','pay_type'=> '支付类型','bill_money'=>'应对账金额');
		$cell_shop    = array('store_name'=>'门店名称','real_orderid'=>'订单编号','orderid'=>'流水号', 'num'=>'数量', 'order_price'=>'金额','balance_pay'=> '余额支付金额','payment_money'=> '在线支付金额', 'coupon_price'=> '优惠券','score_deducte'=> $this->config['score_name'].'抵扣','pay_time'=>'支付时间','pay_type'=> '支付类型','bill_money'=>'应对账金额');
		$cell_waimai  = array('store_name'=>'门店名称','order_id'=>'订单编号','orderid'=>'流水号', 'num'=>'数量', 'order_price'=>'金额','balance_pay'=> '余额支付金额','payment_money'=> '在线支付金额', 'pay_time'=>'支付时间','pay_type'=> '支付类型','bill_money'=>'应对账金额');
		$cell_store   = array('store_name'=>'门店名称','order_id'=>'订单编号','orderid'=>'流水号', 'num'=>'数量', 'order_price'=>'金额','balance_pay'=> '余额支付金额','payment_money'=> '在线支付金额', 'pay_time'=>'支付时间','pay_type'=> '支付类型','bill_money'=>'应对账金额');
		$cell_weidian = array('store_name'=>'门店名称','order_id'=>'订单编号','orderid'=>'流水号', 'num'=>'数量', 'order_price'=>'金额','balance_pay'=> '余额支付金额','payment_money'=> '在线支付金额', 'pay_time'=>'支付时间','pay_type'=> '支付类型','bill_money'=>'应对账金额');
		$cell_wxapp   = array('store_name'=>'门店名称','order_id'=>'订单编号','orderid'=>'流水号', 'num'=>'数量', 'order_price'=>'金额','balance_pay'=> '余额支付金额','payment_money'=> '在线支付金额', 'pay_time'=>'支付时间','pay_type'=> '支付类型','bill_money'=>'应对账金额');
		//$cell_income   = array('store_name'=>'店铺名称','type'=>'类型','order_id'=>'订单编号', 'num'=>'数量','total_money'=>'订单总价','goods_money'=>'商品价格','no_bill_money'=>'不参与对账金额', 'money'=>'金额','system_take'=>'系统抽成','score'=>'送出'.$this->config['score_name'],'score_count'=>$this->config['score_name'].'使用数量','use_time'=>'记账时间','desc'=>'描述');
		$cell_income   = array('store_name'=>'店铺名称','type'=>'类型','order_id'=>'订单编号', 'num'=>'数量','total_money'=>'订单总价','goods_money'=>'商品价格','score_deducte'=>'积分抵扣','coupon_price'=>'系统优惠券抵扣','card_price'=>'商家优惠券抵扣','no_bill_money'=>'不参与对账金额', 'money'=>'金额','system_take'=>'系统抽成','score'=>'送出'.$this->config['score_name'],'score_count'=>$this->config['score_name'].'使用数量','use_time'=>'记账时间','desc'=>'描述');

		// 开始填充头部
		$cell_name = 'cell_'.$type;
		$cell_count = count($$cell_name);
		$cell_start = 1;
		for($f='A';$f<='Z';$f++,$cell_start++){
				if($cell_start>$cell_count){
					break;
				}
				$col_char[]=$f;
		}
		$col_k=0;
		foreach($$cell_name as $key=>$v){
			$objActSheet->getColumnDimension($col_char[$col_k])->setWidth(20);
			$objActSheet->setCellValue($col_char[$col_k].'1', $v);
			$col_k++;
		}
		$i = 2;
		if ($_GET['bill_id']) {
			$res = M('Bill_info')->where(array('id' => $_GET['bill_id']))->find();
			$result = D('Order')->export_order_by_mid($mer_id, $type,1,$res['id_list']);

		}else if($type=='income'){
			$where['m.mer_id']=$mer_id;
			if($_GET['order_type']&&$_GET['order_type']!='all'){
				$where['type']=$_GET['order_type'];
			}
			if($_GET['order_id']){
				$where['order_id']=$_GET['order_id'];
			}
			if(isset($_GET['begin_time'])&&isset($_GET['end_time'])&&!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
				if ($_GET['begin_time']>$_GET['end_time']) {
					$this->error_tips("结束时间应大于开始时间");
				}
				$period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
				$time_condition = " (use_time BETWEEN ".$period[0].' AND '.$period[1].")";
				$where['_string']=$time_condition;

			}
			$result = M('Merchant_money_list')->join('as m left join '.C('DB_PREFIX').'merchant_store s ON m.store_id = s.store_id')->field('m.type,m.total_money,(m.system_take+pow(-1,m.income+1)*m.money) as goods_money,(m.total_money-m.system_take-pow(-1,m.income+1)*m.money) as no_bill_money,m.system_take,m.order_id,m.num,pow(-1,m.income+1)*m.money as money,m.use_time,m.desc,m.score,m.score_count,s.name as store_name')->where($where)->order('use_time DESC')->select();

		}else {
			$result = D("Order")->export_order_by_mid($mer_id, $type);
		}
		$alias_name = $this->get_alias_name();
		foreach ($result as $row) {
			$col_k=0;
			switch($row['type']){
				case 'group':
					$now_order = D('Group_order')->field('score_deducte,coupon_price,card_price')->where(array('real_orderid'=>$row['order_id']))->find();
					break;
				case 'shop':
					$now_order = D('Shop_order')->field('score_deducte,coupon_price,card_price')->where(array('real_orderid'=>$row['order_id']))->find();
					break;
				case 'meal':
					$now_order = D('Plat_order')->join('as p LEFT JOIN '.C('DB_PREFIX').'foodshop_order as f ON f.order_id = p.order_id')->field('system_coupon_price as coupon_price,merchant_coupon_price as card_price,system_score_money as score_deducte')
							->where(array(
									'business_type'=>'foodshop',
									'f.real_orderid'=>$row['order_id'],
									'_string'=>'system_coupon_price<>0 OR merchant_coupon_price<>0 OR system_score_money <>0'
							))
							->find();
					if(empty($now_order)){
						$now_order['score_deducte']=  0;
						$now_order['coupon_price']=  0;
						$now_order['card_price']=  0;
					}
					break;
				case 'store':
					$now_order = D('Store_order')->field('score_deducte,coupon_price,card_price')->where(array('order_id'=>$row['order_id']))->find();
					break;
				case 'appoint':
					$now_order = D('Appoint_order')->field('score_deducte,coupon_price,card_price')->where(array('order_id'=>$row['order_id']))->find();
					break;

			}
			foreach($$cell_name as $k=>$vv){

				switch($k){
					case 'type':
						$objActSheet->setCellValue($col_char[$col_k] . $i, $alias_name[$row[$k]].' ');
						break;
					case 'order_id':
						$objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k].' ');
						break;
					case 'real_orderid':
						$objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k].' ');
						break;
					case 'orderid':
						$objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k].' ');
						break;
					case 'pay_time':
					case 'use_time':
						$objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k]?date('Y-m-d H:i:s', $row[$k]) : '');
						break;
					case 'desc':
					case 'name':
						$objActSheet->setCellValue($col_char[$col_k] . $i, str_replace('</br>','',$row[$k]).' ');
						break;
					case 'score_deducte':
						$objActSheet->setCellValue($col_char[$col_k] . $i,$now_order['score_deducte'].' ');
						break;
					case 'coupon_price':
						$objActSheet->setCellValue($col_char[$col_k] . $i, $now_order['coupon_price'].' ');
						break;
					case 'card_price':
						$objActSheet->setCellValue($col_char[$col_k] . $i, $now_order['card_price'].' ');
						break;
					default:
						$objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k]);
				}
				$col_k++;
			}
			if($type!='income'){
				$objActSheet->setCellValue($col_char[$cell_count-1] . $i, $row['balance_pay']+$row['coupon_price']+$row['score_deducte']+$row['payment_money']);
			}
			$i++;
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
		header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
		header("Content-Transfer-Encoding:binary");
		$objWriter->save('php://output');
		exit();

	}

	public function weidian_order(){

		$percent = 0;
		$mer_id = isset($_GET['mer_id']) ? intval($_GET['mer_id']) : 0;
		$merchant = D('Merchant')->field(true)->where('mer_id=' . $mer_id)->find();
		if ($merchant['percent']) {
			$percent = $merchant['percent'];
		} elseif ($this->config['platform_get_merchant_percent']) {
			$percent = $this->config['platform_get_merchant_percent'];
		}
		$this->assign('percent', $percent);

		$result = D("Weidian_order")->get_order_by_mer_id($mer_id, 1);
		$this->assign($result);
		$this->assign('total_percent', $result['total'] * $percent * 0.01);
		$this->assign('all_total_percent', ($result['alltotal']+$result['alltotalfinsh']) * $percent * 0.01);

// 		$this->assign(D("Meal_order")->get_order_by_mer_id($mer_id, 1));
		$this->assign('now_merchant', $merchant);
		$this->assign('mer_id', $mer_id);
		$this->display();
	}

	public function change()
	{
		$mer_id = isset($_GET['mer_id']) ? intval($_GET['mer_id']) : 0;
		$strids = isset($_GET['strids']) ? htmlspecialchars($_GET['strids']) : '';
		$type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : '';
		if ($strids) {
			$array = explode(',', $strids);
			$mealids = $groupids = array();
			foreach ($array as $val) {
				switch ($type) {
					case 'meal' :
						$mealids[] = intval($val);
						break;
					case 'group' :
						$groupids[] = intval($val);
						break;
					case 'weidian' :
						$weidianids[] = intval($val);
						break;
					case 'appoint' :
						$appointids[] = intval($val);
						break;
					case 'wxapp' :
						$wxappids[] = intval($val);
						break;
					case 'store' :
						$storeids[] = intval($val);
						break;
					case 'waimai' :
						$waimaiids[] = intval($val);
						break;
					case 'shop' :
						$shopids[] = intval($val);
						break;
				}
			}

			$now_time=time();
			$date_bill_info['mer_id']=$mer_id;
			$date_bill_info['name']=$type;
			$date_bill_info['money']=$_GET['money']*100;
			$date_bill_info['num']=count($array);
			$date_bill_info['bill_time']=$now_time;
			$date_bill_info['id_list']=$strids;
			if(!$res = M('Bill_info')->add($date_bill_info)){
				$this->error("保存对账信息失败！");
			}
			$res_bill = M('Bill_time')->where(array('merid'=>$mer_id))->find();
			if(!$res_bill){
				$date_bill_time['merid']=$mer_id;
				$date_bill_time[$type.'_time']=$now_time;
				$date_bill_time['update_time']=$now_time;
				$date_bill_time[$type.'_bill_info']=serialize(array('bill_id'=>$res,'num'=>$date_bill_info['num']));
				if(!M('Bill_time')->add($date_bill_time)){
					$this->error("添加对账汇总信息失败！");
				}
			}else{
				$date_bill_time['merid']=$mer_id;
				$date_bill_time[$type.'_time']=$now_time;
				$date_bill_time['update_time']=$now_time;
				$date_bill_time[$type.'_bill_info']=serialize(array('bill_id'=>$res,'num'=>$date_bill_info['num']));
				if(!M('Bill_time')->where(array('id'=>$res_bill['id']))->save($date_bill_time)){
					$this->error("保存对账汇总信息失败！");
				}

			}

			$data = array('is_pay_bill'=>1);
			$mealids && D('Meal_order')->where(array('mer_id' => $mer_id, 'order_id' => array('in', $mealids)))->save($data);
			$groupids && D('Group_order')->where(array('mer_id' => $mer_id, 'order_id' => array('in', $groupids)))->save(array('is_pay_bill' => 1));
			$weidianids && D('Weidian_order')->where(array('mer_id' => $mer_id, 'order_id' => array('in', $weidianids)))->save(array('is_pay_bill' => 1));
			$appointids && D('Appoint_order')->where(array('mer_id' => $mer_id, 'order_id' => array('in', $appointids)))->save(array('is_pay_bill' => 1));
			$wxappids && D('Wxapp_order')->where(array('mer_id' => $mer_id, 'order_id' => array('in', $wxappids)))->save(array('is_pay_bill' => 1));
			$storeids && D('Store_order')->where(array('mer_id' => $mer_id, 'order_id' => array('in', $storeids)))->save(array('is_pay_bill' => 1));
			$waimaiids && D('Waimai_order')->where(array('mer_id' => $mer_id, 'order_id' => array('in', $waimaiids)))->save(array('is_pay_bill' => 1));
			$shopids && D('Shop_order')->where(array('mer_id' => $mer_id, 'order_id' => array('in', $shopids)))->save(array('is_pay_bill' => 1));
		}
		exit(json_encode(array('error_code' => 0)));
	}

	protected  function get_alias_name(){
		$c_name = array(
				'all'=>'选择分类',
				'group'=>$this->config['group_alias_name'],
				'shop'=>$this->config['shop_alias_name'],
				'shop_offline'=>$this->config['shop_alias_name'].'线下零售',
				'meal'=>$this->config['meal_alias_name'],
				'appoint'=>$this->config['appoint_alias_name'],
				'waimai'=>'外卖',
				'store'=>'优惠买单',
				'cash'=>'到店支付',
				'weidian'=>'微店',
				'wxapp'=>'营销',
				'withdraw'=>'提现',
				'coupon'=>'优惠券',
				'withdraw'=>'提现',
				'activity'=>'平台活动',
				'spread'=>'商家推广',
				'market_order'=>'批发',
		);
		if(!$this->config['store_open_waimai']) unset($c_name['waimai']);
		if(!$this->config['wxapp_url']) unset($c_name['wxapp']);
		if(!$this->config['appoint_page_row']) unset($c_name['appoint']);
		if(!$this->config['is_open_weidian']) unset($c_name['weidian']);
		if(!$this->config['is_cashier']) unset($c_name['store']);
		if(!$this->config['pay_in_store'] || !$this->config['is_cashier'] ) unset($c_name['cash'],$c_name['shop_offline']);
		return $c_name ;
	}

	public function change_money(){
		$this->display();
	}


	//平台提现
	public function companypay(){
		if(IS_POST){
			if(!$res=D('Merchant')->where(array('mer_id'=>$_POST['mer_id']))->find()){
				$this->error('商家不存在！');
			}
			sort($_POST['orderid']);
			$type=$_POST['pay_type'];
			$mer_id=$_POST['mer_id'];
			$orderids = implode(',',$_POST['orderid']);
			$data['pay_type'] = 'merchant';
			$data['pay_id'] = $_POST['mer_id'];
			$data['phone'] = $res['phone'];
			$data['money'] = $_POST['money'];
			$data['desc'] = '商家'.$this->config[$_POST['pay_type'].'_alias_name'].'订单对账|订单号'.'('.$orderids.')'.'|转账 '.(float)($_POST['money']/100).C('config.Currency_txt').'';
			$data['status'] = 0;
			$now_time = time();
			$data['add_time'] = $now_time;

			$data_bill = array('is_pay_bill'=>1);

			$date_bill_info['mer_id']=$_POST['mer_id'];
			$date_bill_info['name']=$_POST['pay_type'];
			$date_bill_info['money']=$_POST['money'];
			$date_bill_info['num']=count($_POST['orderid']);

			$date_bill_info['id_list']=$orderids;
			if(!$res = M('Bill_info')->add($date_bill_info)){
				$this->error("保存对账信息失败！");
			}
			$res_bill = M('Bill_time')->where(array('merid'=>$mer_id))->find();
			if(!$res_bill){
				$date_bill_time['merid']=$mer_id;
				$date_bill_time[$type.'_time']=$now_time;
				$date_bill_time['update_time']=$now_time;
				$date_bill_time[$type.'_bill_info']=serialize(array('bill_id'=>$res,'num'=>$date_bill_info['num']));
				if(!M('Bill_time')->add($date_bill_time)){
					$this->error("添加对账汇总信息失败！");
				}
			}else{
				$date_bill_time['merid']=$mer_id;
				$date_bill_time[$type.'_time']=$now_time;
				$date_bill_time['update_time']=$now_time;
				$date_bill_time[$type.'_bill_info']=serialize(array('bill_id'=>$res,'num'=>$date_bill_info['num']));
				if(!M('Bill_time')->where(array('id'=>$res_bill['id']))->save($date_bill_time)){
					$this->error("保存对账汇总信息失败！");
				}
			}
			$model=new Model();
			$where['order_id']=array('in',$orderids);
			if($model->table(C('DB_PREFIX').'companypay')->add($data)&&$model->table(C('DB_PREFIX').$_POST['pay_type'].'_order')->where($where)->setField($data_bill)){
				$this->success("提现申请成功！");
			}else{
				$this->error("提现失败！请联系管理员！");
			}
		}else{
			$this->error('您提交的数据不正确');
		}
	}
	//微店对账
	public function weidian_change()
	{
		$mer_id = isset($_GET['mer_id']) ? intval($_GET['mer_id']) : 0;
		$strids = isset($_GET['strids']) ? htmlspecialchars($_GET['strids']) : '';
		if ($strids) {
			$array = explode(',', $strids);
			$ids = array();
			foreach ($array as $val) {
				$ids[] = $val;
			}
			$ids && D('Weidian_order')->where(array('mer_id' => $mer_id, 'order_id' => array('in', $ids)))->save(array('is_pay_bill' => 1));
		}
		exit(json_encode(array('error_code' => 0)));
	}

	public function menu()
	{
		$this->assign('bg_color','#F3F3F3');

		$database_merchant = D('Merchant');
		$condition_merchant['mer_id'] = intval($_GET['mer_id']);
		$merchant = $database_merchant->field(true)->where($condition_merchant)->find();
		if(empty($merchant)){
			$this->frame_error_tips('数据库中没有查询到该商家的信息！');
		}
		$merchant['menus'] = explode(',', $merchant['menus']);
		$this->assign('merchant',$merchant);

		$where = array('status' => 1, 'show' => 1);
		if(empty($this->config['wxapp_url'])){
			$where['_string'] = ' (`id`<>70 AND `fid`<>70)';
		}
		$menus = D('New_merchant_menu')->field(true)->where($where)->order('`sort` DESC,`id` ASC')->select();

		foreach($menus as &$menus_value){
			$menus_value['name']=str_replace(array(('团购'),('订餐'),('快店'),('预约')),array($this->config['group_alias_name'],$this->config['meal_alias_name'],$this->config['shop_alias_name'],$this->config['appoint_alias_name']),$menus_value['name']);
		}

//		if(!empty($merchant['menus']) && empty($merchant['root_edit_time'])){
//			foreach ($menus as $k => $v) {
//				/*是否开启快店权限*/
//				if($v['module']=='Shop' && $v['action']=='index'){
//					if(in_array($v['id'], $merchant['menus'])){
//						$shop_root=1;
//					}else{
//						$shop_root=0;
//					}
//					break;
//				}
//			}
//			foreach ($menus as $k => $v) {
//				/*商城的权限*/
//				if($shop_root==1 && $v['module']=='Mall' && $v['action']=='index'){
//					$merchant['menus'][]=$v['id'];
//				}
//			}
//			$this->assign('merchant',$merchant);
//		}
		$list = arrayPidProcess($menus);
		$this->assign('menus', $list);
		$this->display();
	}

	public function savemenu()
	{
		if (IS_POST) {

			$mer_id = isset($_POST['mer_id']) ? intval($_POST['mer_id']) : 0;
			$menus = isset($_POST['menus']) ? $_POST['menus'] : '';
			$database_new_merchant_menu = D('New_merchant_menu');
			$where['fid'] = 0;
			$top_menus = $database_new_merchant_menu->where($where)->getField('id',true);

			$menus = array_merge($menus,$top_menus);

			// 没有勾选子集的父级也不选中
			$menuList = M('New_merchant_menu')->where(['id'=>['in',$menus],'fid'=>['gt',0]])->select();
			$menuFid = array_unique(array_column($menuList,'fid')) ;

			$menuFidAll = array_column(M('New_merchant_menu')->where(['fid'=>0,'id'=>['neq',1]])->select(), 'id');
			foreach ($menus as $key => $value) {
				if(in_array($value, $menuFidAll) && !in_array($value, $menuFid)){
					unset($menus[$key]);
				}
			}

			$menus = implode(',', $menus);
			$database_merchant = D('Merchant');

			$data['menus'] = $menus;
			$data['root_edit_time']=time();
			//$data['is_new_auth'] = 1;
			$database_merchant->where(array('mer_id' => $mer_id))->save($data);
			$this->success('权限设置成功！');
		} else {
			$this->error('非法提交,请重新提交~');
		}
	}

    //商家/店铺餐饮折扣设置
    public function m_discount()
    {
        $this->assign('bg_color','#F3F3F3');

        $database_merchant = D('Merchant');
        $database_merchant_store_shop = D('Merchant_store_foodshop');
        if(isset($_GET['mer_id'])&&$_GET['mer_id']){
            $condition_merchant['mer_id'] = intval($_GET['mer_id']);
            $merchant = $database_merchant->field(true)->where($condition_merchant)->find();
        }else{
            $condition_store['store_id'] = intval($_GET['store_id']);
            $merchant = $database_merchant_store_shop->field(true)->where($condition_store)->find();
        }

        if(empty($merchant)){
            $this->frame_error_tips('数据库中没有查询到该商家的信息！');
        }
        if($merchant['foodshop_tables_discount']!=''){
            $merchant['foodshop_tables_discount'] = substr($merchant['foodshop_tables_discount'], 0, -1);
            $discountArr= explode('|', $merchant['foodshop_tables_discount']);
            $tables = [];
            foreach($discountArr as $k => $v){
               $v = explode(':',$v);
                $tables[$k] = $v;
            }
            $merchant['tables'] = $tables;
        }
        $this->assign('merchant',$merchant);
        $this->display();
    }
    //保存商家餐饮折扣设置
    public function savem_discount()
    {
        if (IS_POST) {
            $other_discount = isset($_POST['other_discount']) ? $_POST['other_discount'] : 0;
            $other_scale = isset($_POST['other_scale']) ? $_POST['other_scale'] : 0;
            $discounts = '';
            if($_POST['tables']) {
                $tables = $_POST['tables'];
                $table_discount = $_POST['table_discount'];
                $table_scale = $_POST['table_scale'];
                foreach ($tables as $v) {
                    if(empty($table_discount[$v - 1])){
                        $this->error('折扣不能为空！');
                    }else{
                        $d = $table_discount[$v - 1];
                    }
                    $s = $table_scale[$v - 1] ? $table_scale[$v - 1] : 0;
                    $t = $v . ':' . $d . ':' . $s;
                    $discounts .= $t . '|';
                }
            }
            $database_merchant = D('Merchant');
            $database_merchant_store_shop = D('Merchant_store_foodshop');
            $data['foodshop_tables_discount'] = $discounts;
            $data['other_discount'] = $other_discount;
            $data['other_scale'] = $other_scale;
            $mer_id = isset($_POST['mer_id']) ? intval($_POST['mer_id']) : 0;
            if(!$mer_id){
                $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
                if($database_merchant_store_shop->where(array('store_id' => $store_id))->save($data)){
                    $this->success('设置成功！');
                }else{
                    $this->error('设置失败！');
                }
            }else{
                if($database_merchant->where(array('mer_id' => $mer_id))->save($data)){
                    $this->success('设置成功！');
                }else{
                    $this->error('设置失败！');
                }
            }
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }

    //	商店分类
	public	function	category_list(){
		$database_group_category = D('Merchant_category');
		$condition_group_category['cat_fid'] = intval($_GET['cat_fid']);

		$count_group_category = $database_group_category->where($condition_group_category)->count();
		import('@.ORG.system_page');
		$p = new Page($count_group_category,50);
		$category_list = D(_view('merchant_category'))->field(true)->where($condition_group_category)->order('`cat_sort` DESC,`cat_id` ASC')->limit($p->firstRow.','.$p->listRows)->select();
		$this->assign('category_list',$category_list);
		$pagebar = $p->show();
		$this->assign('pagebar',$pagebar);

		if($_GET['cat_fid']){
			$condition_now_group_category['cat_id'] = intval($_GET['cat_fid']);
			$now_category = $database_group_category->field(true)->where($condition_now_group_category)->find();
			if(empty($now_category)){
				$this->error_tips('没有找到该分类信息！',3,U('category_list'));
			}
			$this->assign('now_category',$now_category);
		}

		$this->display();
	}
	//	分类添加
	public function category_add(){
		$this->assign('bg_color','#F3F3F3');
		$this->display();
	}
	//	添加完成
	public function category_modify(){
		if(IS_POST){
			if($_FILES){
				$image = D('Image')->handle($this->system_session['id'], 'system/merchant', 0, array('size' => 10));
				if (!$image['error']) {
					$_POST = array_merge($_POST, str_replace('/upload/system/merchant/', '', $image['url']));
				}
			}
			$database_group_category = D('Merchant_category');
			if($cat_id=$database_group_category->data($_POST)->add()){
				//多语言表增加数据
				$lang_model = D('Lang');
				$_POST['cat_id'] = $cat_id;
				$lang_model->add_lang_data('Merchant_category',$_POST);
				if($_POST['cat_pic']){
					D('Image')->update_table_id('/upload/system/merchant/' . $_POST['cat_pic'], $_POST['cat_id'], 'merchant_category');
				}
				$this->frame_submit_tips(1,'添加成功！');
			}else{
				$this->frame_submit_tips(0,'添加失败！请重试~');
			}
		}else{
			$this->frame_submit_tips(0,'非法提交,请重新提交~');
		}
	}
	//	分类编辑
	public function category_edit(){
		$this->assign('bg_color','#F3F3F3');

		$database_group_category = D(_view('merchant_category'));
		$condition_now_group_category['cat_id'] = intval($_GET['cat_id']);
		$now_category = $database_group_category->field(true)->where($condition_now_group_category)->find();
		if(empty($now_category)){
			$this->frame_error_tips('没有找到该分类信息！');
		}
		$this->assign('now_category',$now_category);
		$this->display();
	}
	//	编辑成功
	public function category_amend(){
		if(IS_POST){
			if($_FILES){
				$image = D('Image')->handle($this->system_session['id'], 'system/merchant', 0, array('size' => 10));
				if (!$image['error']) {
					$_POST = array_merge($_POST, str_replace('/upload/system/merchant/', '', $image['url']));
				}
			}
			$database_group_category = D('Merchant_category');
			if($database_group_category->data($_POST)->save() !== false){
				//多语言表修改数据
				$lang_model = D('Lang');
				//$_POST['cat_id'] = $_POST['cat_id'];
				$lang_model->edit_lang_data('Merchant_category',$_POST);
				if($_POST['cat_pic']){
					D('Image')->update_table_id('/upload/system/merchant/' . $_POST['cat_pic'], $_POST['cat_id'], 'merchant_category');
				}
				$this->frame_submit_tips(1,'编辑成功！');
			}else{
				$this->frame_submit_tips(0,'编辑失败！请重试~');
			}
		}else{
			$this->frame_submit_tips(0,'非法提交,请重新提交~');
		}
	}
	//	删除分类
	public function category_del(){
		if(IS_POST){
			$database_group_category = D('Merchant_category');
			$condition_now_group_category['cat_id'] = intval($_POST['cat_id']);
			$now_category = $database_group_category->field(true)->where($condition_now_group_category)->find();
			if($database_group_category->where($condition_now_group_category)->delete()){
				if(empty($now_category['cat_fid'])){
					$condition_son_group_category['cat_fid'] = $now_category['cat_id'];
					$database_group_category->where($condition_son_group_category)->delete();
					$condition_group['cat_fid'] = $now_category['cat_id'];
				}else{
					$condition_group['cat_id'] = $now_category['cat_id'];
				}
//				D('Group')->where($condition_group)->delete();
				$this->success('删除成功！');
			}else{
				$this->error('删除失败！请重试~');
			}
		}else{
			$this->error('非法提交,请重新提交~');
		}
	}
	//	商品字段管理
	public function category_field(){
		$database_group_category = D(_view('merchant_category'));
		$condition_now_group_category['cat_id'] = intval($_GET['cat_id']);
		$now_category = $database_group_category->field(true)->where($condition_now_group_category)->find();
		if(empty($now_category)){
			$this->frame_error_tips('没有找到该分类信息！');
		}
		if(!empty($now_category['cat_fid'])){
			$this->frame_error_tips('该分类不是主分类，无法使用商品字段功能！');
		}
		if(!empty($now_category['cat_field'])){
			$now_category['cat_field'] = unserialize($now_category['cat_field']);
			foreach($now_category['cat_field'] as $key=>$value){
				if($value['use_field'] == 'area'){
					$now_category['cat_field'][$key]['name'] = '区域(内置)';
					$now_category['cat_field'][$key]['url'] = 'area';
				}
				if($value['use_field'] == 'price'){
					$now_category['cat_field'][$key]['name'] = '价格(内置)';
					$now_category['cat_field'][$key]['url'] = 'area';
				}
			}
		}
		$this->assign('now_category',$now_category);

		$this->display();
	}
	//	商品字段管理添加
	public function category_field_add(){
		$this->assign('bg_color','#F3F3F3');
		$this->display();
	}
	//	商品字段管理修改
	public function category_field_modify(){
		if(IS_POST){
			$database_group_category = D('Merchant_category');
			$condition_now_group_category['cat_id'] = intval($_POST['cat_id']);
			$now_category = D(_view('merchant_category'))->field(true)->where($condition_now_group_category)->find();

			if(!empty($now_category['cat_field'])){
				$cat_field = unserialize($now_category['cat_field']);
				foreach($cat_field as $key=>$value){
					if( (!empty($_POST['use_field']) && $value['use_field'] == $_POST['use_field']) || (!empty($_POST['url']) && $value['url'] == $_POST['url']) ){
						$this->error('字段已经添加，请勿重复添加！');
					}
				}
			}else{
				$cat_field = array();
			}
			if(count($cat_field) >= 5){
				$this->error('添加字段失败，最多5个自定义字段！');
			}
			if(empty($_POST['use_field'])){
				$post_data['name'] = $_POST['name'];
				$post_data['url'] = $_POST['url'];
				$post_data['value'] = explode(PHP_EOL,$_POST['value']);
				$post_data['type'] = $_POST['type'];
			}else{
				$post_data['use_field'] = $_POST['use_field'];
			}

			array_push($cat_field,$post_data);
			$data_group_category['cat_field'] = serialize($cat_field);
			$data_group_category['cat_id'] = $now_category['cat_id'];
			if($database_group_category->data($data_group_category)->save()){
				////多语言表修改数据
				$lang_model = D('Lang');
				$lang_model->edit_lang_data('Merchant_category',$data_group_category);

				$this->success('添加成功！');
			}else{
				$this->error('添加失败！请重试~');
			}
		}else{
			$this->error('非法提交,请重新提交~');
		}
	}

	public function reply()
	{
		$where = ' WHERE r.status<2';
		if($this->config['open_shop_reply_check']){
			$where .= ' AND ((order_type=3 and `check`=2) or order_type!=3)';
		}
		if($this->config['open_mall_reply_check']){
			$where .= ' AND ((order_type=6 and `check`=2) or order_type!=6)';
		}
		if(!empty($_GET['keyword'])){
			if($_GET['searchtype'] == 'm_name'){
				$where .= " AND m.name LIKE '%" . htmlspecialchars($_GET['keyword']) . "%'";
				$condition_merchant['mer_id'] = $_GET['keyword'];
			}else if($_GET['searchtype'] == 's_name'){
				$where .= " AND s.name LIKE '%" . htmlspecialchars($_GET['keyword']) . "%'";
			}else if($_GET['searchtype'] == 'nickname'){
				$where .= " AND u.nickname LIKE '%" . htmlspecialchars($_GET['keyword']) . "%'";
			}else if($_GET['searchtype'] == 'phone'){
				$where .= " AND u.phone LIKE '%" . htmlspecialchars($_GET['keyword']) . "%'";
			}
		}

		$order_type = isset($_GET['order_type']) ? intval($_GET['order_type']) : -1;
		if ($order_type != -1) {
			if($order_type==3 || $order_type==6){
		    	$where .= ' AND r.order_type=3 ';
			}else{
		 	   $where .= ' AND r.order_type=' . $order_type;
			}
		} else {
		    $_GET['order_type'] = -1;
		}
        if ($this->system_session['area_id']) {
			$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
			if($now_area['area_type']==3){
				$area_index = 'area_id';
			}elseif($now_area['area_type']==2){
				$area_index = 'city_id';
			}elseif($now_area['area_type']==1){
				$area_index = 'province_id';
			}
            $where .= ' AND m.' . $area_index . '=' . $this->system_session['area_id'];
        }
        $field_str="r.*, m.name AS m_name, s.name AS s_name, u.nickname, u.phone";
        if($order_type==3 || $order_type==6){
        	$field_str.=',ord.order_from';
        	if($order_type==6){
        		$where=' inner join '.C('DB_PREFIX').'shop_order ord on r.order_id=ord.order_id '.$where.' and ord.order_from=1';
        	}else{
        		$where=' inner join '.C('DB_PREFIX').'shop_order ord on r.order_id=ord.order_id '.$where.' and ord.order_from!=1';
        	}
        }

		$sql_count = "SELECT count(1) AS count FROM " . C('DB_PREFIX') . "merchant AS m INNER JOIN " . C('DB_PREFIX') . "reply AS r ON r.mer_id = m.mer_id INNER JOIN " . C('DB_PREFIX') . "user AS u ON r.uid=u.uid LEFT JOIN " . C('DB_PREFIX') . "merchant_store AS s ON s.store_id=r.store_id {$where}";
		$count_res = D()->query($sql_count);
		$count = isset($count_res[0]['count']) ? intval($count_res[0]['count']) : 0;
		import('@.ORG.system_page');
		$p = new Page($count, 50);

		$sql = "SELECT ".$field_str." FROM " . C('DB_PREFIX') . "merchant AS m INNER JOIN " . C('DB_PREFIX') . "reply AS r ON r.mer_id = m.mer_id INNER JOIN " . C('DB_PREFIX') . "user AS u ON r.uid=u.uid LEFT JOIN " . C('DB_PREFIX') . "merchant_store AS s ON s.store_id=r.store_id {$where} ORDER BY r.pigcms_id DESC LIMIT {$p->firstRow},{$p->listRows}";
		$reply_list = D()->query($sql);

		foreach ($reply_list as $k => $v) {
			if($v['order_type']==3){
				if(!isset($v['order_from'])){
					$v['order_from']=M('Shop_order')->where(array('order_id'=>$v['order_id']))->getField('order_from');
				}
				if($v['order_from']==1){
					$v['order_type']=6;
				}
			}
			switch ($v['order_type']) {
				case '0': // 团购
                    $v['url'] = U('Group/order_detail',array('order_id'=>$v['order_id']));
					break;
				case '1':
					# code...
					break;
				case '2':
					$v['url'] = U('Appoint/order_detail',array('order_id'=>$v['order_id']));
					break;
				case '6':
				case '3':
					$v['url'] = U('Shop/order_detail',array('order_id'=>$v['order_id']));
					break;
				case '4':
					$v['url'] = U('Foodshop/order_detail',array('order_id'=>$v['order_id']));
					break;
			}
			$reply_list[$k]=$v;
		}

		$this->assign('reply_list', $reply_list);
		$this->assign('pagebar', $p->show());
		$select[-1] = '全部';
		$select[0] = isset($this->config['group_alias_name']) ? $this->config['group_alias_name'] : '团购';// 团购
		$select[4] = isset($this->config['meal_alias_name']) ? $this->config['meal_alias_name'] : '餐饮'; // 餐饮
		$select[3] = isset($this->config['shop_alias_name']) ? $this->config['shop_alias_name'] : '快店'; // 快店
		$select[2] = isset($this->config['appoint_alias_name']) ? $this->config['appoint_alias_name'] : '预约'; // 预约
		$select[6] = isset($this->config['mall_alias_name']) ? $this->config['mall_alias_name'] : '商城'; // 商城

		$this->assign('selectList', $select);
		$this->display();
	}

	public function replydel()
	{
		if (IS_POST) {
			$reply_id = intval($_POST['reply_id']);
            D()->startTrans();
            try {
                if ($reply = D('Reply')->field(true)->where(array('pigcms_id' => $reply_id))->find()) {
                    if (D('Reply')->where(array('pigcms_id' => $reply_id))->save(array('status' => 2))) {
                        if ($reply['order_type'] == 0) {
                            D('Group')->setDec_group_reply($reply);
                        } elseif ($reply['order_type'] == 1) {
                            D('Merchant_store')->setDec_meal_reply($reply);
                        } elseif ($reply['order_type'] == 2) {
                            D('Appoint')->setDec_appoint_reply($reply);
                        } elseif ($reply['order_type'] == 3) {
                            D('Merchant_store')->setDec_shop_reply($reply);
                        }
                        D("System_log")->add_log("删除了商品评论",'平台配置项',$reply_id);
                        
                    } else {
						throw new \Exception('删除失败！请重试~');
                    }
					D()->commit();
                }
            } catch(\Exception $e) {
                D()->rollback();
                $this->error($e->getMessage());
            }
			$this->success('删除成功！');
		}
		$this->error('非法提交,请重新提交~');
	}

	public function set_check(){
		if (IS_POST) {
			$reply_id = intval($_POST['reply_id']);
			if ($reply = D('Reply')->field(true)->where(array('pigcms_id' => $reply_id))->find()) {
				$check = intval($_GET['check']);
				if(!$check){
					$this->error('数据错误！请重试~');
				}
				if (D('Reply')->where(array('pigcms_id' => $reply_id))->save(array('check' => $check))) {

					$this->success('审核成功！');
				} else {
					$this->error('审核失败！请重试~');
				}
			}
		}
		$this->error('非法提交,请重新提交~');
	}
	public function shop_and_mall_check_reply(){
		$where = ' WHERE r.status<2 and (order_type=3 or order_type=6) and (`check`<>2)';
		if(!empty($_GET['keyword'])){
			if($_GET['searchtype'] == 'm_name'){
				$where .= " AND m.name LIKE '%" . htmlspecialchars($_GET['keyword']) . "%'";
				$condition_merchant['mer_id'] = $_GET['keyword'];
			}else if($_GET['searchtype'] == 's_name'){
				$where .= " AND s.name LIKE '%" . htmlspecialchars($_GET['keyword']) . "%'";
			}else if($_GET['searchtype'] == 'nickname'){
				$where .= " AND u.nickname LIKE '%" . htmlspecialchars($_GET['keyword']) . "%'";
			}else if($_GET['searchtype'] == 'phone'){
				$where .= " AND u.phone LIKE '%" . htmlspecialchars($_GET['keyword']) . "%'";
			}
		}

		$order_type = isset($_GET['order_type']) ? intval($_GET['order_type']) : -1;
		if ($order_type != -1) {
			if($order_type==3 || $order_type==6){
		    	$where .= ' AND r.order_type=3 ';
			}else{
		 	   $where .= ' AND r.order_type=' . $order_type;
			}
		} else {
		    $_GET['order_type'] = -1;
		}
        if ($this->system_session['area_id']) {
			$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
			if($now_area['area_type']==3){
				$area_index = 'area_id';
			}elseif($now_area['area_type']==2){
				$area_index = 'city_id';
			}elseif($now_area['area_type']==1){
				$area_index = 'province_id';
			}
            $where .= ' AND m.' . $area_index . '=' . $this->system_session['area_id'];
        }
        $field_str="r.*, m.name AS m_name, s.name AS s_name, u.nickname, u.phone";
        if($order_type==3 || $order_type==6){
        	$field_str.=',ord.order_from';
        	if($order_type==6){
        		$where=' inner join '.C('DB_PREFIX').'shop_order ord on r.order_id=ord.order_id '.$where.' and ord.order_from=1';
        	}else{
        		$where=' inner join '.C('DB_PREFIX').'shop_order ord on r.order_id=ord.order_id '.$where.' and ord.order_from!=1';
        	}
        }

		$sql_count = "SELECT count(1) AS count FROM " . C('DB_PREFIX') . "merchant AS m INNER JOIN " . C('DB_PREFIX') . "reply AS r ON r.mer_id = m.mer_id INNER JOIN " . C('DB_PREFIX') . "user AS u ON r.uid=u.uid LEFT JOIN " . C('DB_PREFIX') . "merchant_store AS s ON s.store_id=r.store_id {$where}";
		$count_res = D()->query($sql_count);
		$count = isset($count_res[0]['count']) ? intval($count_res[0]['count']) : 0;
		import('@.ORG.system_page');
		$p = new Page($count, 50);

		$sql = "SELECT ".$field_str." FROM " . C('DB_PREFIX') . "merchant AS m INNER JOIN " . C('DB_PREFIX') . "reply AS r ON r.mer_id = m.mer_id INNER JOIN " . C('DB_PREFIX') . "user AS u ON r.uid=u.uid LEFT JOIN " . C('DB_PREFIX') . "merchant_store AS s ON s.store_id=r.store_id {$where} ORDER BY r.pigcms_id DESC LIMIT {$p->firstRow},{$p->listRows}";
		$reply_list = D()->query($sql);

		foreach ($reply_list as $k => $v) {
			if($v['order_type']==3){
				if(!isset($v['order_from'])){
					$v['order_from']=M('Shop_order')->where(array('order_id'=>$v['order_id']))->getField('order_from');
				}
				if($v['order_from']==1){
					$v['order_type']=6;
				}
			}
			switch ($v['order_type']) {
				case '0': // 团购
                    $v['url'] = U('Group/order_detail',array('order_id'=>$v['order_id']));
					break;
				case '1':
					# code...
					break;
				case '2':
					$v['url'] = U('Appoint/order_detail',array('order_id'=>$v['order_id']));
					break;
				case '6':
				case '3':
					$v['url'] = U('Shop/order_detail',array('order_id'=>$v['order_id']));
					break;
				case '4':
					$v['url'] = U('Foodshop/order_detail',array('order_id'=>$v['order_id']));
					break;
			}
			$reply_list[$k]=$v;
		}

		$this->assign('reply_list', $reply_list);
		$this->assign('pagebar', $p->show());
		$select[-1] = '全部';
		$select[3] = isset($this->config['shop_alias_name']) ? $this->config['shop_alias_name'] : '快店'; // 快店
		$select[6] = isset($this->config['mall_alias_name']) ? $this->config['mall_alias_name'] : '商城'; // 商城
		$this->assign('selectList', $select);

		$this->display();
	}

	public function replyinfo()
	{
		$reply_id = isset($_GET['reply_id']) ? intval($_GET['reply_id']) : 0;
		$reply = D('Reply')->field(true)->where(array('pigcms_id' => $reply_id))->find();
		if (empty($reply)) $this->error('不存在的评论或该评论已经被删除！');
		if ($user = D('User')->field(true)->where(array('uid' => $reply['uid']))->find()) {
			$reply['nickname'] = $user['nickname'];
			$reply['phone'] = $user['phone'];
		}
		if ($merchant = D('Merchant')->field(true)->where(array('mer_id' => $reply['mer_id']))->find()) {
			$reply['m_name'] = $merchant['name'];
		}

		if ($merchant_store = D('Merchant_store')->field(true)->where(array('store_id' => $reply['store_id']))->find()) {
			$reply['s_name'] = $merchant_store['name'];
		}

		if($reply['order_type'] == 0){
			$pic_filepath = 'group';
			$reply['type_name'] = $this->config['group_alias_name'];
		}elseif($reply['order_type'] == 1){
			$pic_filepath = 'meal';
			$reply['type_name'] = $this->config['meal_alias_name'];
		} elseif ($reply['order_type'] == 2) {
			$pic_filepath = 'appoint';
			$reply['type_name'] = $this->config['appoint_alias_name'];
		} elseif ($reply['order_type'] == 3) {
            $pic_filepath = 'shop';
            $orderId = $reply['order_id'];
            $orderFrom = M('Shop_order')->where(['order_id' => $orderId])->getField('order_from');
            if ($orderFrom == 1) {
                $reply['type_name'] = $this->config['mall_alias_name'];
            } else {
                $reply['type_name'] = $this->config['shop_alias_name'];
            }
		}

		if($reply['pic']){

			$reply_pic_list = D('Reply_pic')->field('`pigcms_id`,`pic`')->where(array('pigcms_id' => array('in', explode(',', $reply['pic']))))->select();
			$reply_image_class = new reply_image();
			foreach($reply_pic_list as $key=>$value){
				$tmp_value = $reply_image_class->get_image_by_path($value['pic'], $pic_filepath);
				$reply['pics'][] = $tmp_value;
			}
		}
		$this->assign('bg_color','#F3F3F3');
		$this->assign('reply', $reply);
		$this->display();
	}

    public function edit_percent()
    {
        $where['mer_id'] = $_POST['mer_id'];
        $merchant_percent_menu = M('System_menu')->where(array('module' => 'Merchant_percent'))->find();
        $this->assign('merchant_percent_menu', $merchant_percent_menu);
        if ($this->system_session['level'] < 2 && !in_array($merchant_percent_menu['id'], $this->system_session['menus'])) {
            $this->error_tips('您没有该权限');
        }
        if (IS_POST) {
            //快店平台配送抽成
            if (isset($_POST['plat_deliver'])) {
                $_POST['shop_plat_deliver_percent'] = $_POST['plat_deliver'] == 1 ? $_POST['shop_plat_deliver_percent'] : -1;
                unset($_POST['plat_deliver']);
            }
            //快店商家配送抽成
            if (isset($_POST['merchant_deliver'])) {
                $_POST['shop_merchant_deliver_percent'] = $_POST['merchant_deliver'] == 1 ? $_POST['shop_merchant_deliver_percent'] : -1;
                unset($_POST['merchant_deliver']);
            }
            //快店快递配送抽成
            if (isset($_POST['express_deliver'])) {
                $_POST['shop_express_deliver_percent'] = $_POST['express_deliver'] == 1 ? $_POST['shop_express_deliver_percent'] : -1;
                unset($_POST['express_deliver']);
            }
            //快店自提抽成
            if (isset($_POST['self_pick'])) {
                $_POST['shop_self_pick_percent'] = $_POST['self_pick'] == 1 ? $_POST['shop_self_pick_percent'] : -1;
                unset($_POST['self_pick']);
            }

            foreach ($_POST as $key => &$vv) {

                if($key == 'mer_id'){
                    continue;
                }
                if ($vv == '' && $vv !== 0) {
                    $_POST[$key] = -1;
                }
                if(stripos($key, '_percent') > 0 && !is_array($vv) && (!is_numeric($vv) || $vv > 100)){
                    $this->error('输入不合法数字!~');
                }
            }
            if (strpos($key, 'score_max') && strpos($vv, '%')) {
                $tmp_v = floatval(str_replace('%', '', $vv));
                if ($tmp_v > 100) {
                    $this->error('积分使用数据不能大于100%');
                }
            }

            if (empty($_POST['type'])) {
                if (M('Merchant_percent_rate')->where($where)->find()) {
					if(isset($_POST['merchant_online'])&&empty($_POST['merchant_online'])){//关闭了线上支付
						//团购业务
						if(!$_POST['group_offline']&&$this->check_merchant_business($_POST['mer_id'],'group')){
							$this->error('该商家的店铺开启了'.$this->config['group_alias_name'].'业务，您需要为该商家开启至少一个“'.$this->config['group_alias_name'].'线下支付”或商家线上支付！');
						}
						//快店（商城）业务
						if($this->check_merchant_business($_POST['mer_id'],'shop')){
							if(!$_POST['shop_offline']){
								$this->error('该商家的店铺开启了'.$this->config['shop_alias_name'].'业务，您需要为该商家开启至少一个“'.$this->config['shop_alias_name'].'线下支付”或商家线上支付！');
							}
							if(!$_POST['mall_offline']){
								$this->error('该商家的店铺开启了'.$this->config['mall_alias_name'].'业务，您需要为该商家开启至少一个“'.$this->config['mall_alias_name'].'线下支付”或商家线上支付！');
							}

						}
					}

                	if($_POST['merchant_rate']){// 商家推广佣金
	            		$percent_arr = $_POST['money_percent'];
	                	$spreadData['fid'] = $_POST['mer_id'];
	                	$spreadData['merchant_percent_detail'] = implode(',', $percent_arr);
	                	if (M('Percent_spread_merchant_detail_by_type')->where(array('fid' => $_POST['mer_id']))->find()) {
		                    $result_detail_save = M('Percent_spread_merchant_detail_by_type')->where(array('fid' => $_POST['mer_id']))->save($spreadData);
		                } else {
		                    $result_detail_add = M('Percent_spread_merchant_detail_by_type')->add($spreadData);
		                }
		            }

                    if (M('Merchant_percent_rate')->where($where)->save($_POST)!== false) {

                        $this->success('保存成功！');
                    } else {
                        $this->error('请检查数据是否修改!~');
                    }
                } else {
                    if (M('Merchant_percent_rate')->add($_POST)) {
                        $this->success('添加成功！');
                    } else {
                        $this->error('添加失败！请重试~');
                    }
                }
            } else {
                if (M('Merchant_percent_rate')->where($where)->find()) {
                    $result_save = M('Merchant_percent_rate')->where($where)->save($_POST);
                } else {
                    $result_add = M('Merchant_percent_rate')->add($_POST);
                }
                $data['fid'] = $_POST['mer_id'];
                $percent_arr = $_POST['money_percent'];
                $data[$_POST['type'] . '_percent_detail'] = implode(',', $percent_arr);

                if (M('Percent_detail_by_type')->where(array('fid' => $_POST['mer_id']))->find()) {
                    $result_detail_save = M('Percent_detail_by_type')->where(array('fid' => $_POST['mer_id']))->save($data);
                } else {
                    $result_detail_add = M('Percent_detail_by_type')->add($data);
                }

                if ($result_detail_add || $result_add || $result_save !== false || $result_detail_save !== false) {
                    $this->success('保存成功！');
                    die;
                } else {
                    $this->error('请检查数据是否修改!~');
                }
            }
        } else {
            $percent_detail = M('Percent_detail')->select();
            $where['mer_id'] = $_GET['mer_id'];
            if ($mer_pr = M('Merchant_percent_rate')->where($where)->find()) {
                $this->assign('mer_pr', $mer_pr);
            }
            if (!empty($_GET['type'])) {
                $result = M('Percent_detail_by_type')->where(array('fid' => $_GET['mer_id']))->find();
                $detail = explode(',', $result[$_GET['type'] . '_percent_detail']);
                $this->assign('detail', $detail);
            }

            $this->assign('percent_detail', $percent_detail);
            $mer_id = $_GET['mer_id'];
            $this->assign('mer_id', $mer_id);
        }
        $this->display();
    }

	/**
	 * @desc 判断该商户下所有店铺是否有开启过相关业务
	 * @param $mer_id
	 * @param $busisess：meal=>餐饮，group=>团购，shop=>快店（商城）
	 * @return bool
	 */
	private function check_merchant_business($mer_id,$busisess){
		$config = array(
			'meal' => 'have_meal',
			'group' => 'have_group',
			'shop' => 'have_shop',
		);
		$field = $config[$busisess];
		if(empty($field)) return false;
		$db = D('Merchant_store');
		$where = array();
		$where['mer_id'] = $mer_id;
		$info = $db->where($where)->field($field)->select();
		if(empty($info)) return false;
		foreach($info as $val){
			if($val[$field]==1){
				return true;
			}
		}
		return false;
	}

	public function store_edit_percent(){
		$where['store_id'] = $_POST['store_id'];
		$store_percent_menu = M('System_menu')->where(array('module'=>'Store_percent'))->find();

		if($this->system_session['level']<2 && !in_array($store_percent_menu['id'],$this->system_session['menus'])){
			$this->error_tips('您没有该权限');
		}
		if(IS_POST){
			foreach($_POST as $key=>&$vv){
            	if($key == 'store_id'){
            		continue;
            	}
				if($vv=='' && $vv!==0){
					$_POST[$key] = -1;
				}
				if(stripos($key, '_percent') > 0 && !is_array($vv) && (!is_numeric($vv) || $vv > 100)){
					$this->error('输入不合法数字!~');
				}
			}
			if(empty($_POST['type'])) {
				if (M('Store_percent_rate')->where($where)->find()) {
					if (M('Store_percent_rate')->where($where)->save($_POST)) {
						D("System_log")->add_log("编辑了店铺抽成比例，门店ID：".$_POST['store_id'],'店铺抽成',$_POST['store_id']);
						$this->success('保存成功！');
					} else {
						$this->error('请检查数据是否修改!~');
					}
				} else {
					if (M('Store_percent_rate')->add($_POST)) {
						D("System_log")->add_log("添加了店铺抽成比例，门店ID：".$_POST['store_id'],'店铺抽成',$_POST['store_id']);
						$this->success('添加成功！');
					} else {
						$this->error('添加失败！请重试~');
					}
				}
			}else{
				if (M('Store_percent_rate')->where($where)->find()) {
					$result_save = M('Store_percent_rate')->where($where)->save($_POST);
				} else {
					$result_add = M('Store_percent_rate')->add($_POST);
				}
				$data['fid'] = $_POST['store_id'];
				$percent_arr = $_POST['money_percent'];
				$data[$_POST['type'].'_percent_detail'] = implode(',',$percent_arr);
				if(M('Percent_detail_by_store_type')->where(array('fid'=>$_POST['store_id']))->find()){
					$result_detail_save = M('Percent_detail_by_store_type')->where(array('fid'=>$_POST['store_id']))->save($data);
				}else{
					$result_detail_add = M('Percent_detail_by_store_type')->add($data);
				}

				if($result_detail_add||$result_add||$result_save!==false||$result_detail_save!==false){
					D("System_log")->add_log("编辑了".$_POST['type']."业务店铺抽成比例，门店ID：".$_POST['store_id'],'店铺抽成',$_POST['store_id']);
					$this->success('保存成功！');die;
				} else {
					$this->error('请检查数据是否修改!~');
				}
			}
		}else{
			$percent_detail = M('Percent_detail')->select();
			$where['store_id'] = $_GET['store_id'];
			if($mer_pr = M('Store_percent_rate')->where($where)->find()){
				$this->assign('mer_pr',$mer_pr);
			}

			if(!empty($_GET['type'])){
				$result = M('Percent_detail_by_store_type')->where(array('fid'=> $_GET['store_id']))->find();
				$detail = explode(',',$result[$_GET['type'].'_percent_detail']);
				$this->assign('detail',$detail);
			}

			$this->assign('percent_detail', $percent_detail);
			$store_id = $_GET['store_id'];
			$this->assign('store_id',$store_id);
		}
		$this->display();
	}

	public function edit_rate(){
		$where['mer_id'] = $_GET['mer_id'];
		if($mer_pr = M('Merchant_percent_rate')->where($where)->find()){
			$this->assign('mer_pr',$mer_pr);
		}

		$spread_where = ['fid'=>$_GET['mer_id']];
        $result = M('Percent_spread_merchant_detail_by_type')->where($spread_where)->find();
        $detail = explode(',',$result['merchant_percent_detail']);
		$this->assign('detail',$detail);

		$percent_detail = M('Percent_spread_merchant_detail')->select();
		$this->assign('percent_detail',$percent_detail);
		$this->display();
	}

	public function edit_score(){
		$where['mer_id'] = $_GET['mer_id'];
		if($mer_pr = M('Merchant_percent_rate')->where($where)->find()){
			$this->assign('mer_pr',$mer_pr);
		}
		$this->display();
	}

	public function edit_offline(){
		$where['mer_id'] = $_GET['mer_id'];
		if($mer_pr = M('Merchant_percent_rate')->where($where)->find()){
			$this->assign('mer_pr',$mer_pr);
		}
		$this->display();
	}

	public function edit_user_rate(){
		if(IS_POST){
			$where['mer_id'] = $_POST['mer_id'];

			// 区间设置
            $percent_arr = $_POST['money_percent'];
			$spread_where = ['fid'=>$_POST['mer_id']];
        	$spread_data[$_POST['use_spread_type'].'_percent_detail'] = implode(',',$percent_arr);
            if(M('Percent_spread_detail_by_type')->where($spread_where)->find()){
                $result = M('Percent_spread_detail_by_type')->where($spread_where)->save($spread_data);
            }else{
                $spread_data['fid'] = $_POST['mer_id'];
                $result = M('Percent_spread_detail_by_type')->add($spread_data);
            }
			if(M('Merchant_percent_rate')->where($where)->find()){
				if(M('Merchant_percent_rate')->where($where)->save($_POST) !== false){
					$this->success('保存成功！');
				}else{
					$this->error('保存失败！请重试~');
				}
			}else{
				if(M('Merchant_percent_rate')->add($_POST)){
					$this->success('添加成功！');
				}else{
					$this->error('添加失败！请重试~');
				}
			}
		}else{
			$where['mer_id'] = $_GET['mer_id'];
			if($mer_pr = M('Merchant_percent_rate')->where($where)->find()){
				$this->assign('mer_pr',$mer_pr);
			}
			$mer_id = $_GET['mer_id'];
			$this->assign('mer_id',$mer_id);

			$spread_where = ['fid'=>$_GET['mer_id']];
            $result = M('Percent_spread_detail_by_type')->where($spread_where)->find();
            $detail = explode(',',$result['shop_percent_detail']);
			$this->assign('detail',$detail);
	        $percent_detail = M('Percent_spread_detail')->select();
			$this->assign('percent_detail',$percent_detail);
		}
		$this->display();
	}

    // 获得用户推广分佣的区间配置
    public function ajax_get_user_rate(){
		$where['fid'] = $_POST['mer_id'];
        if($_POST['type']){
            $result = M('Percent_spread_detail_by_type')->where($where)->find();
            $detail = explode(',',$result[$_POST['type'].'_percent_detail']);
        }

        $percent_detail = M('percent_spread_detail')->select();

        $return['percent_detail'] = $percent_detail;
        $return['detail'] = $detail ?? [];
        echo json_encode($return);
    }

	public function store_auth()
	{
		$database_merchant_store = D('Merchant_store');
		$status = isset($_GET['status']) ? intval($_GET['status']) : 0;
		if ($status == 0) {
			$condition_merchant = array('auth' => array('in','1, 4'));
		} else {
			$condition_merchant = array('auth' => array('in','2, 3, 5'));
		}
		$condition_merchant['status'] = array('neq','4');
		//搜索
		if(!empty($_GET['keyword'])){
			$condition_merchant['name'] = array('like','%'.$_GET['keyword'].'%');
		}
		if ($this->system_session['area_id']) {
			$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
			if($now_area['area_type']==3){
				$area_index = 'area_id';
			}elseif($now_area['area_type']==2){
				$area_index = 'city_id';
			}elseif($now_area['area_type']==1){
				$area_index = 'province_id';
			}
			$condition_merchant[$area_index] = $this->system_session['area_id'];
		}
		$count_merchant = $database_merchant_store->where($condition_merchant)->count();
		import('@.ORG.system_page');
		$p = new Page($count_merchant, 15);
		$store_list = $database_merchant_store->field(true)->where($condition_merchant)->order('auth_time ASC')->limit($p->firstRow.','.$p->listRows)->select();
		$this->assign('store_list', $store_list);
		$pagebar = $p->show();
		$this->assign('pagebar', $pagebar);
		$this->assign('status', $status);
		$this->display();
	}

	public function auth_edit()
	{
		$database_merchant_store = D('Merchant_store');
		$database_store_authfile = D('Merchant_store_authfile');

		$store_id = intval($_REQUEST['store_id']);
		$now_store = $database_merchant_store->field(true)->where(array('store_id' => $store_id))->find();
		if (empty($now_store)) {
			$this->error('店铺不存在！');
		}
		$store_authfile = $database_store_authfile->field(true)->where(array('store_id' => $store_id))->find();
		if (empty($store_authfile)) {
			$this->error('申请资料不存在！');
		}

		if(IS_POST){
			$status = isset($_POST['status']) ? intval($_POST['status']) : 0;
			$reason = isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : '';
			if ($status == 1) {
				$auth = 3;
			} else {
				$auth = $now_store['auth'] < 3 ? 2 : 5;
			}
			$data = array('auth' => $auth, 'reason' => $reason);
			if ($auth == 3) {
				$data['auth_files'] = $store_authfile['auth_files'];
			}
			if ($database_merchant_store->where(array('store_id' => $store_id))->save($data)) {
				$data_authfile = array('reason' => $reason);
				$database_store_authfile->where(array('store_id' => $store_id))->save($data_authfile);
				$this->success('审核成功');
			} else {
				$this->error('审核失败');
			}
			exit;
		}
		$auth_files = array();
		if (!empty($store_authfile['auth_files'])) {
			$auth_file_class = new auth_file();
			$tmp_pic_arr = explode(';', $store_authfile['auth_files']);
			foreach($tmp_pic_arr as $key => $value){
			    $image = $auth_file_class->get_image_by_path($value);
				$auth_files[] = array('title' => $value, 'url' => $image['image']);
			}
		}
		$now_store['reason'] = $store_authfile['reason'];
		$now_store['auth_files'] = $auth_files;
		$this->assign('now_store', $now_store);
		$this->display();
	}

	//商家权限价格
	public function authority_price(){
		/*if($this->system_session['level']<2){
			$this->error('您的权限不够');
		}else{*/
			$menus = D('New_merchant_menu')->where(array('status'=>1))->select();

			foreach($menus as &$value){
				$value['name'] = str_replace(array('团购','订餐','快店','预约'),array($this->config['group_alias_name'],$this->config['meal_alias_name'],$this->config['shop_alias_name'],$this->config['appoint_alias_name']),$value['name']);
			}

			$list = array();
			$list = arrayPidProcess($menus);
			if(empty($this->config['wxapp_url'])){
				unset($list[113]['menu_list'][70]);
			}
			$this->assign('menus', $list);
			$this->display();
		//}
	}

	public function change_authority_price(){
		if($this->system_session['level']<2){
			echo json_encode(array('error_code'=>1,'msg'=>'您的权限不够','price'=>$_POST['price']));exit;
		}else{
			$res = D('New_merchant_menu')->where(array( 'id' =>$_POST['id']))->setField('price',$_POST['price']);
			if($res){
				echo json_encode(array('error_code'=>0,'msg'=>'价格改变成功'));exit;
			}else{
				echo json_encode(array('error_code'=>1,'msg'=>'价格改变失败','price'=>$_POST['price']));exit;
			}
		}
	}

	public function authority_group(){
		/*if($this->system_session['level']<2){
			$this->error('您的权限不够');
		}else{*/
			$list=  M('Authority_group')->where(array('gid'=>1))->order('sort desc,id desc')->select();
			$this->default_menus = $this->get_default_menus();
			foreach ($list as &$item) {
				$menus = explode(',',$item['menus']);
				$menus = array_unique($menus);
				sort($menus);
				if($menus == $this->default_menus){
					$item['is_default_menus'] = 1;
				}
				$item['menu_count'] = count($menus);
			}
			$this->assign('time_type', $this->time_type);
			$this->assign('list',$list);
			$this->display();
		//}
	}

	public function authority_add(){
		if(IS_POST){
			if(empty($_POST['name'])){
				$this->error('名称不能为空');
			}
			$menus = isset($_POST['menus']) ? $_POST['menus'] : '';

			// 没有勾选子集的父级也不选中
			$menuList = M('New_merchant_menu')->where(['id'=>['in',$menus],'fid'=>['gt',0]])->select();
			$menuFid = array_unique(array_column($menuList,'fid')) ;
			foreach ($menus as $key => $value) {
				if(in_array($value, ['131','174','114','10055','113','32','2','10126','10122','3','10123','10530']) && !in_array($value, $menuFid)){
					unset($menus[$key]);
				}
			}
			$date['menus'] = implode(',', $menus);
			$date['name'] = $_POST['name'];
			$date['add_time'] = $_SERVER['REQUEST_TIME'];
			$date['gid'] = 1;
			$date['price'] = $_POST['price'];
			$date['is_default'] = $_POST['is_default'];
			$date['is_try'] = $_POST['is_try'];
			$date['try_time'] = $_POST['is_try']==0?0:$_POST['try_time'];
			$date['try_time_type'] = $_POST['is_try']==0?'':$_POST['try_time_type'];
			$date['time_type'] = $_POST['time_type'];
			$date['des'] = $_POST['des'];
			$date['detail'] = $_POST['detail'];
			$date['percent'] = $_POST['percent'];
			$date['sort'] = $_POST['sort'];
			$date['status'] = $_POST['status'];
			$date['update_all'] = $_POST['update_all'];

			if($_POST['is_default']){
				$date['is_try'] = 0;
				$date['try_time'] = 0;
				$date['try_time_type'] = '';
				$date['time_type'] = '';
				$date['price'] = 0;
			}

			if($_POST['id']){
				if($_POST['is_default']==1 && M('Authority_group')->where(array('gid'=>1,'is_default'=>1,'id'=>array('neq',$_POST['id'])))->find()){
					$this->error('默认套餐只能有一个');
				}
				$where_authority_id['authority_group_id'] = $_POST['id'];
				$result = M('Authority_group')->where(array('id'=>$_POST['id']))->save($date);
				//编辑权限的时候直接覆盖之前购买本套餐的商家
				if($_POST['update_all']==1){
					M('Merchant')->where(['authority_group_id'=>$_POST['id']])->save(['menus'=>$date['menus']]);
				}
			}else{
				if($_POST['is_default']==1 &&  M('Authority_group')->where(array('gid'=>1,'is_default'=>1))->find()){
					$this->error('默认套餐只能有一个');
				}
				$result = M('Authority_group')->add($date);
				$where_authority_id['authority_group_id'] = $result;
			}

			if($result){
//				M("Merchant")->where($where_authority_id)->setField('menus',$date['menus']);
				$this->success('操作成功');
			}else{
				$this->error('操作失败');
			}
		}else {
			$menus = D('New_merchant_menu')->field(true)->where(array('status' => 1, 'show' => 1))->order('`sort` DESC,`id` ASC')->select();
			$list = arrayPidProcess($menus);

			$group = M('Authority_group')->where(array('id'=>$_GET['id']))->find();
			$group['menus'] = explode(',', $group['menus']);
			$this->assign('group', $group);

			$this->assign('menus', $list);
			$this->display();
		}
	}



	public function authority_del(){
		$atuh = M('Authority_group');
		if(M('Merchant')->where(array('authority_group_id'=>$_GET['id'],'merchant_end_time'=>array('gt',time())))->find()){
			$this->error('该权限有商家正在使用，不能删除！');
		}
		if($atuh->where(array('id'=>$_GET['id']))->delete()){
			$this->success('删除成功！');
		}else{
			$this->error('删除失败！');
		}
	}

    public function discount()
    {
        $area_id = isset($_GET['area_id']) ? intval($_GET['area_id']) : 0;
        $area = D('Area')->field(true)->where(array('area_id' => $area_id))->find();
        if (empty($area) || $area['area_type'] > 3) {
            $this->error('选择城市区域');
        }

        $where = "`m`.`status`=1";
        $condition = array('status' => 1);
        if ($area['area_type'] == 1) {
            $condition['province_id'] = $area_id;
            $where .= " AND `m`.`province_id`=" . $area_id;
        } elseif ($area['area_type'] == 2) {
            $condition['city_id'] = $area_id;
            $where .= " AND `m`.`city_id`=" . $area_id;
        } elseif ($area['area_type'] == 2) {
            $condition['area_id'] = $area_id;
            $where .= " AND `m`.`area_id`=" . $area_id;
        }

        $database_merchant = D('Merchant');
        // 搜索
        if (! empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'mer_id') {
                $condition['mer_id'] = $_GET['keyword'];

                $where .= " AND `m`.`mer_id`=" . $area_id;
            } elseif ($_GET['searchtype'] == 'account') {
                $condition['account'] = array('like', '%' . $_GET['keyword'] . '%');
                $where .= " AND `m`.`account` LIKE '%" . $_GET['keyword'] . "%'";
            } elseif ($_GET['searchtype'] == 'name') {
                $condition['name'] = array('like', '%' . $_GET['keyword'] . '%');
                $where .= " AND `m`.`name` LIKE '%" . $_GET['keyword'] . "%'";
            } elseif ($_GET['searchtype'] == 'phone') {
                $condition['phone'] = array('like', '%' . $_GET['keyword'] . '%');
                $where .= " AND `m`.`phone` LIKE '%" . $_GET['keyword'] . "%'";
            }
        }

        $LogWhere = " 1";
        if (! empty($_GET['begin_time']) && ! empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $LogWhere .= " AND (dateline BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }


        $count_merchant = $database_merchant->where($condition)->count();
        import('@.ORG.system_page');
        $p = new Page($count_merchant, 15);
        $sql = "SELECT `m`.`mer_id`, `m`.`name`, `m`.`phone`, `l`.`num`, `l`.`price` FROM " . C('DB_PREFIX') . "merchant as m LEFT JOIN ";
        $sql .= "(SELECT COUNT(1) AS num, SUM(discount_money) AS price, mer_id FROM " . C('DB_PREFIX'). "merchant_discount_log WHERE {$LogWhere} GROUP BY mer_id) as l ON m.mer_id=l.mer_id ";
        $sql .= "WHERE $where ORDER BY `m`.`mer_id` DESC LIMIT {$p->firstRow},{$p->listRows}";
        $merchant_list = D()->query($sql);

        $sql = "SELECT sum(`l`.`num`) as num, sum(`l`.`price`) as price FROM " . C('DB_PREFIX') . "merchant as m LEFT JOIN ";
        $sql .= "(SELECT COUNT(1) AS num, SUM(discount_money) AS price, mer_id FROM " . C('DB_PREFIX'). "merchant_discount_log WHERE {$LogWhere} GROUP BY mer_id) as l ON m.mer_id=l.mer_id ";
        $sql .= "WHERE $where LIMIT 1";
        $total = D()->query($sql);
        $totalNum = isset($total[0]['num']) ? intval($total[0]['num']) : 0;
        $totalPrice = isset($total[0]['price']) ? floatval($total[0]['price']) : 0;

        $this->assign('merchant_list', $merchant_list);
        $pagebar = $p->show();
        $this->assign('pagebar',$pagebar);
        $this->assign(array('totalNum' => $totalNum, 'totalPrice' => $totalPrice));
        $this->display();
    }

    public function discount_detail()
    {
        $mer_id = isset($_REQUEST['mer_id']) ? intval($_REQUEST['mer_id']) : 0;
        $merchant = D('Merchant')->field(true)->where(array('mer_id' => $mer_id))->find();
        if (empty($merchant)) {
            $this->error_tips("商家信息错误");
        }
        $where = '1';
        if (isset($_POST['begin_time']) && isset($_POST['end_time']) && !empty($_POST['begin_time']) && !empty($_POST['end_time'])) {
            if ($_POST['begin_time'] > $_POST['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_POST['begin_time'] . " 00:00:00"), strtotime($_POST['end_time'] . " 23:59:59"));
            $where .= " AND (use_time BETWEEN ".$period[0].' AND '.$period[1].")";

            $this->assign('begin_time', $_POST['begin_time']);
            $this->assign('end_time', $_POST['end_time']);
        }
        if (isset($_POST['order_id']) && !empty($_POST['order_id'])) {
            $where .= " AND real_orderid='" . htmlspecialchars(trim($_POST['order_id'])) . "'";
        }
        $this->assign('order_id', $_POST['order_id']);
        $count = D('Merchant_discount_log')->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 15);
        $sql = "SELECT `s`.`name`, `l`.* FROM " . C('DB_PREFIX') . "merchant_store as s INNER JOIN " . C('DB_PREFIX'). "merchant_discount_log as l ON `s`.`store_id`=`l`.`store_id` ";
        $sql .= "WHERE $where ORDER BY `l`.`id` DESC LIMIT {$p->firstRow},{$p->listRows}";
        $order_list = D()->query($sql);

        $this->assign('order_list', $order_list);
        $this->assign('pagebar', $p->show());
        $this->assign('now_merchant', $merchant);
        $this->display();
    }

	//门店小程序
	public function wx_store(){
		$wx_store = M('Wx_store')->select();
		$this->assign('wx_store',$wx_store);
		$this->display();
	}


	public function add_wx_store(){
		import('@.ORG.Wxapp_store');
		$store = new Wxapp_store();
		if(IS_POST){
			//$list = explode(';',substr($_POST['qualification_list'],0,-1));
			//if(count($list)==1){
				//$list = $list[0];
			//}
			//$_POST['qualification_list'] =$list;
			$param['first_catid'] = $_POST['first_catid'];
			$param['second_catid'] = $_POST['second_catid'];
			$param['qualification_list'] = $_POST['qualification_list'];
			$param['headimg_mediaid'] = $_POST['headimg_mediaid'];
			$param['nickname'] = $_POST['nickname'];
			$param['intro'] = $_POST['intro'];
			$param['org_code'] = $_POST['org_code'];
			unset($_POST['dosubmit']);
			fdump($_POST,'sss');
			$res = $store->create_wx_store($param);

			if(!$res['errcode'] && $res['errmsg']=='ok'){
//				$_POST['qualification_list'] =serialize($list);
				M('Wx_store')->add($_POST);
				$this->success('添加成功');
			}else{
				$this->error($res['errmsg']);
			}
		}else{
			$category_list = $store->get_category();
			$this->assign("category_list",json_encode($category_list));
			$this->display();
		}
	}


    public function exportDiscount()
    {
        set_time_limit(0);

        $mer_id = isset($_REQUEST['mer_id']) ? intval($_REQUEST['mer_id']) : 0;
        $merchant = D('Merchant')->field(true)->where(array('mer_id' => $mer_id))->find();
        if (empty($merchant)) {
            $this->error_tips("商家信息错误");
        }
        $where = '1';
        if (isset($_REQUEST['begin_time']) && isset($_REQUEST['end_time']) && !empty($_REQUEST['begin_time']) && !empty($_REQUEST['end_time'])) {
            if ($_REQUEST['begin_time'] > $_REQUEST['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_REQUEST['begin_time'] . " 00:00:00"), strtotime($_REQUEST['end_time'] . " 23:59:59"));
            $where .= " AND (use_time BETWEEN ".$period[0].' AND '.$period[1].")";

        }
        if (isset($_REQUEST['order_id']) && !empty($_REQUEST['order_id'])) {
            $where .= " AND real_orderid='" . htmlspecialchars(trim($_REQUEST['order_id'])) . "'";
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = '商家统一折扣订单列表';
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $count = D('Merchant_discount_log')->where($where)->count();

        $length = ceil($count / 1000);
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('第' . ($i+1) . '个一千配送订单');
            $objActSheet = $objExcel->getActiveSheet();

            $objActSheet->setCellValue('A1', '店铺名称');
            $objActSheet->setCellValue('B1', '订单号');
            $objActSheet->setCellValue('C1', '订单总额');
            $objActSheet->setCellValue('D1', '商家统一折扣比例');
            $objActSheet->setCellValue('E1', '商家统一折扣优惠总金额');
            $objActSheet->setCellValue('F1', '支付时间');

            $sql = "SELECT `s`.`name`, `l`.* FROM " . C('DB_PREFIX') . "merchant_store as s INNER JOIN " . C('DB_PREFIX'). "merchant_discount_log as l ON `s`.`store_id`=`l`.`store_id` ";
            $sql .= "WHERE $where ORDER BY `l`.`id` DESC LIMIT " . $i * 1000 . ', 1000';
            $order_list = D()->query($sql);
            if (!empty($order_list)) {
                $index = 2;
                foreach ($order_list as $value) {
                    $value['confirm_time'] = $value['create_time'] && $value['order_time'] ? intval(($value['create_time'] - $value['order_time']) / 60) . '分钟' : '-';
                    $value['grab_time'] = $value['start_time'] ? intval(($value['start_time'] - $value['create_time']) / 60) . '分钟' : '-';
                    $value['deliver_use_time'] = $value['end_time'] ? intval(($value['end_time'] - $value['start_time']) / 60) . '分钟' : '-';

                    $objActSheet->setCellValueExplicit('A' . $index, $value['name']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['real_orderid'] . ' ');
                    $objActSheet->setCellValueExplicit('C' . $index, $value['price']);
                    $objActSheet->setCellValueExplicit('D' . $index, floatval($value['discount']/10) . '折');
                    $objActSheet->setCellValueExplicit('E' . $index, floatval($value['discount_money']) . ' ');
                    $objActSheet->setCellValueExplicit('F' . $index, date('Y-m-d H:i:s', $value['dateline']));

                    $index++;
                }
            }
            sleep(2);
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
	 * 每个店铺独立维护的通联微信小程序支付配置。
	 */
	public function store_allinpay(){
		$storeId = IS_POST ? intval($_POST['store_id']) : intval($_GET['store_id']);
		$store = M('Merchant_store')->field('store_id,mer_id,name')->where(array('store_id' => $storeId))->find();
		if(empty($store)){
			$this->frame_error_tips('数据库中没有查询到该店铺的信息！');
		}

		$configModel = M('Merchant_store_allinpay_config');
		if(IS_POST){
			$status = isset($_POST['allinpay_status']) ? (string)$_POST['allinpay_status'] : '';
			if(!in_array($status, array('0', '1'), true)){
				$this->error('请选择通联支付开启状态');
			}

			$required = array(
				'allinpay_appid' => 'AppID',
				'allinpay_cusid' => '商户号',
				'pay_allinpay_private_key' => '通联私钥',
			);
			$data = array(
				'store_id' => $storeId,
				'mer_id' => intval($store['mer_id']),
				'allinpay_status' => intval($status),
				'update_time' => $_SERVER['REQUEST_TIME'],
			);
			foreach($required as $field => $label){
				$value = trim(stripslashes(htmlspecialchars_decode((string)$_POST[$field], ENT_QUOTES)));
				if($value === ''){
					$this->error('请填写通联支付' . $label);
				}
				$data[$field] = $value;
			}

			$current = $configModel->where(array('store_id' => $storeId))->find();
			if($current){
				$result = $configModel->where(array('store_id' => $storeId))->save($data);
			}else{
				$data['add_time'] = $_SERVER['REQUEST_TIME'];
				$result = $configModel->add($data);
			}
			if($result === false){
				$this->error('通联支付配置保存失败');
			}
			D('System_log')->add_log('编辑了店铺通联支付配置，店铺ID：' . $storeId, '店铺信息', $storeId);
			$this->success('通联支付配置保存成功');
		}

		$config = $configModel->where(array('store_id' => $storeId))->find();
		if(empty($config)){
			$config = array('allinpay_status' => 0);
		}
		foreach(array('allinpay_appid', 'allinpay_cusid', 'pay_allinpay_private_key') as $field){
			$config[$field] = htmlspecialchars((string)$config[$field], ENT_QUOTES, 'UTF-8');
		}
		$this->assign('store', $store);
		$this->assign('allinpay', $config);
		$this->assign('bg_color', '#F3F3F3');
		$this->display();
	}

	public function juhepay(){


		if(IS_POST){
			$data['mer_id'] = $_POST['mer_id'];
			$data['userid'] = $_POST['userid'];
//			$data['wx_userid'] = $_POST['wx_userid'];
//			$data['mercid'] = $_POST['mercid'];
//			$data['private_key'] = $_POST['private_key'];
//			$data['key_secret'] = $_POST['key_secret'];
			$juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$_POST['mer_id']))->find();
			if($juhe && M('Merchant_juhe_config')->save($data)){
				$this->success('编辑成功');
			}else if(empty($juhe) &&  M('Merchant_juhe_config')->add($data)){
				$this->success('添加成功');
			}else{
				$this->error('编辑失败');
			}
			die;
		}
		$juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$_GET['mer_id']))->find();
		$this->assign('juhe',$juhe);
		$this->display();
	}

	public function weikebaba(){


		if(IS_POST){
			$data['mer_id'] = $_POST['mer_id'];
			$data['user_id'] = $_POST['user_id'];
			$data['wxappid'] = $_POST['wxappid'];
			$data['wxappid_mini'] = $_POST['wxappid_mini'];
			$data['key'] = $_POST['key'];
			$juhe = M('Weikebaba')->where(array('mer_id'=>$_POST['mer_id']))->find();
			if($juhe && M('Weikebaba')->where('id='.$juhe['id'])->save($data)){
				$this->success('编辑成功');
			}elseif(empty($juhe) &&  M('Weikebaba')->add($data)){
				$this->success('添加成功');
			}else{
				$this->error('编辑失败');
			}
			die;
		}
		$data = M('Weikebaba')->where(array('mer_id'=>$_GET['mer_id']))->find();
		$this->assign('data',$data);
		$this->display();
	}

	//已审核店铺列表
	public function already_store(){
		$where = array('status' => 1);
		//搜索
		if(!empty($_GET['keyword'])){
			if($_GET['searchtype'] == 'store_id'){
				$where['store_id'] = trim($_GET['keyword']);
			}else if($_GET['searchtype'] == 'name'){
				$where['name'] = array('like','%'.trim($_GET['keyword']).'%');
			}else if($_GET['searchtype'] == 'phone'){
				$where['phone'] = array('like','%'.trim($_GET['keyword']).'%');
			}
		}
		if($_GET['store'] || $_GET['status']){
			if($_GET['status'] == 'all' && $_GET['store'] == 'all'){
			    $where['status'] = ['IN',[0,1,4]];
			}
			if($_GET['store'] == 'all' && $_GET['status'] !='all'){
				if($_GET['status'] == 'open'){
					if($_GET['store'] == 'all'){
						$where['status'] = 1;
					}else{
						if(isset($this->config['meal_alias_name'])){
							$where['have_meal'] = 1;
						}
						if(isset($this->config['group_alias_name'])){
							$where['have_group'] = 1;
						}
						if(isset($this->config['shop_alias_name'])){
							$where['have_shop'] = 1;
						}
					}

				}
				if($_GET['status'] == 'close'){
					if($_GET['store'] == 'all'){
                        $where['status'] = ['IN', [0, 4]];
					}else{
						if(isset($this->config['meal_alias_name'])){
						$where['have_meal'] = 0;
						}
						if(isset($this->config['group_alias_name'])){
							$where['have_group'] = 0;
						}
						if(isset($this->config['shop_alias_name'])){
							$where['have_shop'] = 0;
						}
					}
				}
			}
			if($_GET['status'] != 'all' && $_GET['store'] !='all'){
				$store_index = $_GET['store'];//下标
				if($_GET['status'] == 'open'){
					$where[$store_index] = 1;
				}
				if($_GET['status'] == 'close'){
					$where[$store_index] = 0;
				}
			}

		}
		//一卡通
		if($_GET['ecard_pay'] == '1'){
			$where['is_internal_card_pay'] = 1;
		}
		if($_GET['ecard_pay'] == '2'){
			$where['is_internal_card_pay'] = 0;
		}
		if ($this->system_session['area_id']) {
			$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
			if($now_area['area_type']==3){
				$area_index = 'area_id';
			}elseif($now_area['area_type']==2){
				$area_index = 'city_id';
			}elseif($now_area['area_type']==1){
				$area_index = 'province_id';
			}
			$where[$area_index] = $this->system_session['area_id'];
		}
		$database = D('Merchant_store');
		$count = $database->where($where)->count();
		import('@.ORG.system_page');
		$p = new Page($count, 20);
		$list = $database->field(true)->where($where)->order('`store_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
		$this->assign('store_list', $list);
		$pagebar = $p->show();
		$this->assign('pagebar', $pagebar);
		$this->display();
    }

    // 商家平台积分信息
    public function get_score(){
    	$condition_where = array();
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'mer_id') {
                $mer_id = trim($_GET['keyword']);
                $condition_where['mer_id'] = $mer_id;
            } else if ($_GET['searchtype'] == 'mer_name') {
                $condition_where['name'] = trim($_GET['keyword']);
            }
        }
        // 时间
        if(!empty($_GET['start_time']) && !empty($_GET['end_time'])){
            $condition_where['time'] = array(array('gt',strtotime($_GET['start_time']." 00:00:00")),array('lt',strtotime($_GET['end_time']." 23:59:59")));
        }elseif($_GET['start_time']){
            $condition_where['time'] = array('gt',strtotime($_GET['start_time']." 00:00:00"));
        }elseif($_GET['end_time']){
            $condition_where['time'] = array('lt',strtotime($_GET['end_time']." 23:59:59"));
        }
        // 类型
        if($_GET['type']){
            $condition_where['type'] = $_GET['type'];
        }

        // var_dump($condition_where);
        $db_merchant_score_list = D("Merchant_score_list");
        $score_list = $db_merchant_score_list->get_limit_list_page($condition_where);
        $sore_num = array();
        $condition_where['type'] = 1;
        $score_get = $db_merchant_score_list->field('sum(score) as num')->where($condition_where)->find();
        $sore_num['score_get'] = floatval($score_get['num']) ? $score_get['num'] : 0;

        $condition_where['type'] = 2;
        $score_use = $db_merchant_score_list->field('sum(score) as num')->where($condition_where)->find();
        $sore_num['score_use'] = floatval($score_use['num']) ? $score_use['num'] : 0;
        $this->assign('score_list',$score_list);
        $this->assign('sore_num',$sore_num);

        $this->display();
    }




    public function get_score_export() {
    	$condition_where = array();
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'mer_id') {
                $mer_id = trim($_GET['keyword']);
                $condition_where['mer_id'] = $mer_id;
            } else if ($_GET['searchtype'] == 'mer_name') {
                $condition_where['name'] = trim($_GET['keyword']);
            }
        }
        // 时间
        if(!empty($_GET['start_time']) && !empty($_GET['end_time'])){
            $condition_where['time'] = array(array('gt',strtotime($_GET['start_time']." 00:00:00")),array('lt',strtotime($_GET['end_time']." 23:59:59")));
        }elseif($_GET['start_time']){
            $condition_where['time'] = array('gt',strtotime($_GET['start_time']." 00:00:00"));
        }elseif($_GET['end_time']){
            $condition_where['time'] = array('lt',strtotime($_GET['end_time']." 23:59:59"));
        }
        // 类型
        if($_GET['type']){
            $condition_where['type'] = $_GET['type'];
        }

        $db_merchant_score_list = D("Merchant_score_list");
        $sore_num = array();
        $condition_count = $condition_where;
        $condition_count['type'] = 1;
        $score_get = $db_merchant_score_list->field('sum(score) as num')->where($condition_count)->find();
        $sore_num['score_get'] = floatval($score_get['num']) ? $score_get['num'] : 0;

        $condition_count['type'] = 2;
        $score_use = $db_merchant_score_list->field('sum(score) as num')->where($condition_count)->find();
        $sore_num['score_use'] = floatval($score_use['num']) ? $score_use['num'] : 0;

        $title_msg = '获得总积分: ' . $sore_num['score_get'];
        $title_msg .= '；消耗总积分: ' . $sore_num['score_use'];

        $count = $db_merchant_score_list->where($condition_where)->count();
        if($count <= 0 ){
            $this->error('无数据导出！');
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

        $title = $this->merchant_session['name'] . '平台商家积分记录';

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $length = ceil($count/1000);

        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('共' . $count . '条记录');
            $objActSheet = $objExcel->getActiveSheet();
            $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);

            $objExcel->getActiveSheet()->mergeCellsByColumnAndRow('0', '1',12, '1');
            $objExcel->getActiveSheet()->setCellValueByColumnAndRow('0', '1', $title_msg);

            $objActSheet->setCellValue('A2', '编号');
            $objActSheet->setCellValue('B2', '订单号');
            $objActSheet->setCellValue('C2', '商家名称');
            $objActSheet->setCellValue('D2', '类型');
            $objActSheet->setCellValue('E2', '积分数');
            $objActSheet->setCellValue('F2', '添加时间');
            $objActSheet->setCellValue('G2', '备注');

            $_GET['page'] = $i+1;
            $score_list = $db_merchant_score_list->get_limit_list_page($condition_where,1000);

            if (!empty($score_list['list'])) {
                $index = 3;
                foreach ($score_list['list'] as $value) {
                    $objActSheet->setCellValueExplicit('A' . $index, $value['id']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['order_id']);
                    $objActSheet->setCellValueExplicit('C' . $index, $value['name']);
                    $objActSheet->setCellValueExplicit('D' . $index, $value['type']==1?'增加':'消耗');
                    $objActSheet->setCellValueExplicit('E' . $index, $value['score']);
                    $objActSheet->setCellValueExplicit('F' . $index, date('Y-m-d H:i:s',$value['time']));
                    $objActSheet->setCellValueExplicit('G' . $index, $value['desc']);
                    $index++;
                }
            }
            sleep(2);
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
            header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');
            exit();
        }
    }

    // 商家平台积分信息
    public function reserve_fund(){
    	$condition_where = array();
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'mer_id') {
                $mer_id = trim($_GET['keyword']);
                $condition_where['mer_id'] = $mer_id;
            } else if ($_GET['searchtype'] == 'mer_name') {
                $condition_where['name'] = trim($_GET['keyword']);
            }
        }
        // 时间
        if(!empty($_GET['start_time']) && !empty($_GET['end_time'])){
            $condition_where['time'] = array(array('gt',strtotime($_GET['start_time']." 00:00:00")),array('lt',strtotime($_GET['end_time']." 23:59:59")));
        }elseif($_GET['start_time']){
            $condition_where['time'] = array('gt',strtotime($_GET['start_time']." 00:00:00"));
        }elseif($_GET['end_time']){
            $condition_where['time'] = array('lt',strtotime($_GET['end_time']." 23:59:59"));
        }
        // 类型
        if($_GET['type']){
            $condition_where['type'] = $_GET['type'];
        }

        // var_dump($condition_where);
        $db_merchant_reserve_fund = D("Merchant_reserve_fund");
        $list = $db_merchant_reserve_fund->get_limit_list_page($condition_where);
        $fund_num = array();
        $condition_where['type'] = 1;
        $fund_get = $db_merchant_reserve_fund->field('sum(money) as num')->where($condition_where)->find();
        $fund_num['fund_get'] = floatval($fund_get['num']) ? $fund_get['num'] : 0;

        $condition_where['type'] = 2;
        $fund_use = $db_merchant_reserve_fund->field('sum(money) as num')->where($condition_where)->find();
        $fund_num['fund_use'] = floatval($fund_use['num']) ? $fund_use['num'] : 0;
        $this->assign('list',$list);
        $this->assign('fund_num',$fund_num);

        $this->display();
    }




    public function reserve_fund_export() {
    	$condition_where = array();
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'mer_id') {
                $mer_id = trim($_GET['keyword']);
                $condition_where['mer_id'] = $mer_id;
            } else if ($_GET['searchtype'] == 'mer_name') {
                $condition_where['name'] = trim($_GET['keyword']);
            }
        }
        // 时间
        if(!empty($_GET['start_time']) && !empty($_GET['end_time'])){
            $condition_where['time'] = array(array('gt',strtotime($_GET['start_time']." 00:00:00")),array('lt',strtotime($_GET['end_time']." 23:59:59")));
        }elseif($_GET['start_time']){
            $condition_where['time'] = array('gt',strtotime($_GET['start_time']." 00:00:00"));
        }elseif($_GET['end_time']){
            $condition_where['time'] = array('lt',strtotime($_GET['end_time']." 23:59:59"));
        }
        // 类型
        if($_GET['type']){
            $condition_where['type'] = $_GET['type'];
        }

        $db_merchant_reserve_fund = D("Merchant_reserve_fund");
        $fund_num = array();
        $condition_where_type = $condition_where;
        $condition_where_type['type'] = 1;
        $fund_get = $db_merchant_reserve_fund->field('sum(money) as num')->where($condition_where_type)->find();
        $fund_num['fund_get'] = floatval($fund_get['num']) ? $fund_get['num'] : 0;

        $condition_where_type['type'] = 2;
        $fund_use = $db_merchant_reserve_fund->field('sum(money) as num')->where($condition_where_type)->find();
        $fund_num['fund_use'] = floatval($fund_use['num']) ? $fund_use['num'] : 0;

        $title_msg = '获得总备用金：' . $fund_num['fund_get'];
        $title_msg .= '消耗总备用金：' . $fund_num['fund_use'];

        $list = $db_merchant_reserve_fund->get_limit_list_page($condition_where,1);
        $count = $list['totalCount'];
        if($count <= 0 ){
            $this->error('无数据导出！');
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

        $title = $this->merchant_session['name'] . '平台采购备用金记录';

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $length = ceil($count/1000);

        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('共' . $count . '条记录');
            $objActSheet = $objExcel->getActiveSheet();
            $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);

            $objExcel->getActiveSheet()->mergeCellsByColumnAndRow('0', '1',12, '1');
            $objExcel->getActiveSheet()->setCellValueByColumnAndRow('0', '1', $title_msg);

            $objActSheet->setCellValue('A2', '编号');
            $objActSheet->setCellValue('B2', '订单号');
            $objActSheet->setCellValue('C2', '商家名称');
            $objActSheet->setCellValue('D2', '类型');
            $objActSheet->setCellValue('E2', '金额');
            $objActSheet->setCellValue('F2', '添加时间');
            $objActSheet->setCellValue('G2', '备注');

            $_GET['page'] = $i+1;
            $list = $db_merchant_reserve_fund->get_limit_list_page($condition_where,1000);

            if (!empty($list['list'])) {
                $index = 3;
                foreach ($list['list'] as $value) {
                    $objActSheet->setCellValueExplicit('A' . $index, $value['id']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['order_id']);
                    $objActSheet->setCellValueExplicit('C' . $index, $value['name']);
                    $objActSheet->setCellValueExplicit('D' . $index, $value['type']==1?'增加':'消耗');
                    $objActSheet->setCellValueExplicit('E' . $index, $value['money']);
                    $objActSheet->setCellValueExplicit('F' . $index, date('Y-m-d H:i:s',$value['time']));
                    $objActSheet->setCellValueExplicit('G' . $index, $value['desc']);
                    $index++;
                }
            }
            sleep(2);
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
            header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');
            exit();
        }
    }

    // 商家平台积分信息
    public function minimum_profit(){
    	$condition_where = array();

        // 时间
        if(!empty($_GET['start_time']) && !empty($_GET['end_time'])){
            $condition_where['take_date'] = array(array('egt',strtotime($_GET['start_time']." 00:00:00")),array('lt',strtotime($_GET['end_time']." 23:59:59")));
        }elseif($_GET['start_time']){
            $condition_where['take_date'] = array('egt',strtotime($_GET['start_time']." 00:00:00"));
        }elseif($_GET['end_time']){
            $condition_where['take_date'] = array('elt',strtotime($_GET['end_time']." 23:59:59"));
        }

        // var_dump($condition_where);
        $db_merchant_minimum_profit = D("Merchant_minimum_profit");
        $list = $db_merchant_minimum_profit->get_limit_list_page($condition_where);
        $count_array = array();

        $minimum_profit_count = $db_merchant_minimum_profit->field('sum(money*minimum_profit) as num')->where($condition_where)->find();
        $minimum_profit_count = $minimum_profit_count['num'] ? round($minimum_profit_count['num']/100,2) : 0;
        $this->assign('list',$list);
        $this->assign('minimum_profit_count',$minimum_profit_count);
        $this->assign('fund_num',$fund_num);

        $this->display();
    }




    public function minimum_profit_export() {
    	$condition_where = array();
        // 时间
        if(!empty($_GET['start_time']) && !empty($_GET['end_time'])){
            $condition_where['take_date'] = array(array('egt',strtotime($_GET['start_time']." 00:00:00")),array('lt',strtotime($_GET['end_time']." 23:59:59")));
        }elseif($_GET['start_time']){
            $condition_where['take_date'] = array('egt',strtotime($_GET['start_time']." 00:00:00"));
        }elseif($_GET['end_time']){
            $condition_where['take_date'] = array('elt',strtotime($_GET['end_time']." 23:59:59"));
        }
        $db_merchant_minimum_profit = D("Merchant_minimum_profit");
        $minimum_profit_count = $db_merchant_minimum_profit->field('sum(money*minimum_profit) as num')->where($condition_where)->find();
        $minimum_profit_count = $minimum_profit_count['num'] ? round($minimum_profit_count['num']/100,2) : 0;

        $title_msg = '平台最低利润总额：' . $minimum_profit_count;

        $list = $db_merchant_minimum_profit->get_limit_list_page($condition_where,1);
        $count = $list['totalCount'];
        if($count <= 0 ){
            $this->error('无数据导出！');
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

        $title = $this->merchant_session['name'] . '平台最低利润记录';

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $length = ceil($count/1000);

        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('共' . $count . '条记录');
            $objActSheet = $objExcel->getActiveSheet();
            $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);

            $objExcel->getActiveSheet()->mergeCellsByColumnAndRow('0', '1',12, '1');
            $objExcel->getActiveSheet()->setCellValueByColumnAndRow('0', '1', $title_msg);

            $objActSheet->setCellValue('A2', '编号');
            $objActSheet->setCellValue('B2', '日期');
            $objActSheet->setCellValue('C2', '抽成金额');
            $objActSheet->setCellValue('D2', '用户积分返现');
            $objActSheet->setCellValue('E2', '商家积分返现');
            $objActSheet->setCellValue('F2', '用户分佣');
            $objActSheet->setCellValue('G2', '商家分佣');
            $objActSheet->setCellValue('H2', '商家备用金');
            $objActSheet->setCellValue('I2', '平台最低利润');

            $_GET['page'] = $i+1;
            $list = $db_merchant_minimum_profit->get_limit_list_page($condition_where,1000);

            if (!empty($list['list'])) {
                $index = 3;
                foreach ($list['list'] as $value) {
                    $objActSheet->setCellValueExplicit('A' . $index, $value['id']);
                    $objActSheet->setCellValueExplicit('B' . $index, date('Y-m-d H:i:s',$value['take_date']));
                    $objActSheet->setCellValueExplicit('C' . $index, $value['money']);
                    $objActSheet->setCellValueExplicit('D' . $index, $value['score_pay_back_money'].'（'.$value['score_pay_back'].'%）');
                    $objActSheet->setCellValueExplicit('E' . $index, $value['score_pay_back_mer_money'].'（'.$value['score_pay_back'].'%）');
                    $objActSheet->setCellValueExplicit('F' . $index, $value['user_spread_money'].'（'.$value['mer_spread'].'%）');
                    $objActSheet->setCellValueExplicit('G' . $index, $value['mer_spread_money'].'（'.$value['score_pay_back'].'%）');
                    $objActSheet->setCellValueExplicit('H' . $index, $value['reserve_fund_money'].'（'.$value['reserve_fund'].'%）');
                    $objActSheet->setCellValueExplicit('I' . $index, $value['minimum_profit_money'].'（'.$value['minimum_profit'].'%）');
                    $index++;
                }
            }
            sleep(2);
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
            header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');
            exit();
        }
    }


    // 商家推广商家信息
    public function recommend_index(){
        $database_merchant = D('Merchant');
        //搜索
        if(!empty($_GET['keyword'])){
            if($_GET['searchtype'] == 'mer_id'){
                $condition_merchant['mer_id'] = $_GET['keyword'];
            }else if($_GET['searchtype'] == 'account'){
                $condition_merchant['account'] = array('like','%'.$_GET['keyword'].'%');
            }else if($_GET['searchtype'] == 'name'){
                $condition_merchant['name'] = array('like','%'.$_GET['keyword'].'%');
            }else if($_GET['searchtype'] == 'phone'){
                $condition_merchant['phone'] = array('like','%'.$_GET['keyword'].'%');
            }else if($_GET['searchtype'] == 'store'){
                $where['name'] =  array('like','%'.htmlspecialchars($_GET['keyword']).'%');
                $store_list = M('Merchant_store')->field('mer_id')->where($where)->group('mer_id')->select();
                foreach ($store_list as $value) {
                    $store_arr[] = $value['mer_id'];
                }
                $condition_merchant['mer_id'] = array('in',$store_arr);
            }
        }
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if($now_area['area_type']==3){
                $area_index = 'area_id';
            }elseif($now_area['area_type']==2){
                $area_index = 'city_id';
            }elseif($now_area['area_type']==1){
                $area_index = 'province_id';
            }
            $this->assign('admin_area',$now_area['area_type']);
            // $area_index = $this->system_session['level'] == 1 ? 'area_id' : 'city_id';
            $condition_merchant[$area_index] = $this->system_session['area_id'];
        }
        if($_GET['province_idss'] && $this->config['many_city']){
            $condition_merchant['province_id'] =$_GET['province_idss'];
        }
        if($_GET['city_idss']){
            $condition_merchant['city_id'] =$where['city_id']= $_GET['city_idss'];
        }
        if($_GET['area_id']){
            $condition_merchant['area_id'] =$where['area_id']= $_GET['area_id'];
        }

        $searchstatus = intval($_GET['searchstatus']);
        switch($searchstatus){
            case 0:
                //3 是欠款状态 该状态下商家业务暂停，不能支付，但是可以管理，充值
                $condition_merchant['_string'] = 'status = 1 OR status = 3';
                break;
            case 1:
                $condition_merchant['status'] = 2;
                break;
            case 2:
                $condition_merchant['status'] = 0;
                break;
        }

        if (!isset($condition_merchant['status'])) {
            $condition_merchant['status'] = array('lt', 4);//4：假删除状态
        }
        switch($_GET['searchorder']){
            case 0:
                $order = 'mer_id DESC';
                break;
            case 1:
                $order = 'money DESC,mer_id DESC';
                break;

        }

        $count_merchant = $database_merchant->where($condition_merchant)->count();

        import('@.ORG.system_page');
        $p = new Page($count_merchant,15);
        $merchant_list = $database_merchant->field(true)->where($condition_merchant)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
        foreach ($merchant_list as &$v) {
            $where['mer_id'] = $v['mer_id'];
            $where['order_type'] = 'market_spread';
            $all_spread_money = M('Merchant_recommend_merchant_record')->where($where)->sum('money');
            $v['all_spread_money'] = $all_spread_money>0?$all_spread_money:0;
        }

        $this->assign('merchant_list',$merchant_list);
        $pagebar = $p->show();
        $this->assign('pagebar',$pagebar);

        $this->display();
    }

    // 上级推广商家
    public function spread_higher_merchant(){
	    $mer_id = I('mer_id');
        $database_merchant = D('Merchant');
        $merchant_info = $database_merchant->where(array('mer_id' => $mer_id))->find();
        if ($merchant_info['mer_spread_code']) {
            import('@.ORG.system_page');
            $condition_merchant['status'] = array('lt', 4);//4：假删除状态
            $condition_merchant['mer_code'] = $merchant_info['mer_spread_code'];
            $count_merchant = $database_merchant->where($condition_merchant)->count();
            $p = new Page($count_merchant,15);
            $merchant_list = $database_merchant->field(true)->where($condition_merchant)->order('mer_id DESC')->limit($p->firstRow.','.$p->listRows)->select();
            foreach ($merchant_list as &$v) {
                $where['mer_id'] = $v['mer_id'];
                $where['order_type'] = 'market_spread';
                $all_spread_money = M('Merchant_recommend_merchant_record')->where($where)->sum('money');
                $v['all_spread_money'] = $all_spread_money>0?$all_spread_money:0;
            }

        } else {
            $merchant_list = array();
        }
        $this->assign('merchant_list',$merchant_list);
        $this->assign('merchant_info',$merchant_info);
        $this->display();
    }

    // 下级推广商家
    public function spread_merchant(){
        $mer_id = I('mer_id');
        $database_merchant = D('Merchant');
        $merchant_info = $database_merchant->where(array('mer_id' => $mer_id))->find();
        if ($merchant_info['mer_code']) {

            //搜索
            if(!empty($_GET['keyword'])){
                if($_GET['searchtype'] == 'mer_id'){
                    $condition_merchant['mer_id'] = $_GET['keyword'];
                }else if($_GET['searchtype'] == 'account'){
                    $condition_merchant['account'] = array('like','%'.$_GET['keyword'].'%');
                }else if($_GET['searchtype'] == 'name'){
                    $condition_merchant['name'] = array('like','%'.$_GET['keyword'].'%');
                }else if($_GET['searchtype'] == 'phone'){
                    $condition_merchant['phone'] = array('like','%'.$_GET['keyword'].'%');
                }else if($_GET['searchtype'] == 'store'){
                    $where['name'] =  array('like','%'.htmlspecialchars($_GET['keyword']).'%');
                    $store_list = M('Merchant_store')->field('mer_id')->where($where)->group('mer_id')->select();
                    foreach ($store_list as $value) {
                        $store_arr[] = $value['mer_id'];
                    }
                    $condition_merchant['mer_id'] = array('in',$store_arr);
                }
            }
            if ($this->system_session['area_id']) {
                $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
                if($now_area['area_type']==3){
                    $area_index = 'area_id';
                }elseif($now_area['area_type']==2){
                    $area_index = 'city_id';
                }elseif($now_area['area_type']==1){
                    $area_index = 'province_id';
                }
                $this->assign('admin_area',$now_area['area_type']);
                // $area_index = $this->system_session['level'] == 1 ? 'area_id' : 'city_id';
                $condition_merchant[$area_index] = $this->system_session['area_id'];
            }
            if($_GET['province_idss'] && $this->config['many_city']){
                $condition_merchant['province_id'] =$_GET['province_idss'];
            }
            if($_GET['city_idss']){
                $condition_merchant['city_id'] =$where['city_id']= $_GET['city_idss'];
            }
            if($_GET['area_id']){
                $condition_merchant['area_id'] =$where['area_id']= $_GET['area_id'];
            }

            $searchstatus = intval($_GET['searchstatus']);
            switch($searchstatus){
                case 0:
                    //3 是欠款状态 该状态下商家业务暂停，不能支付，但是可以管理，充值
                    $condition_merchant['_string'] = 'status = 1 OR status = 3';
                    break;
                case 1:
                    $condition_merchant['status'] = 2;
                    break;
                case 2:
                    $condition_merchant['status'] = 0;
                    break;
            }

            if (!isset($condition_merchant['status'])) {
                $condition_merchant['status'] = array('lt', 4);//4：假删除状态
            }
            $order = 'mer_id DESC';
            switch($_GET['searchorder']){
                case 0:
                    $order = 'mer_id DESC';
                    break;
                case 1:
                    $order = 'money DESC,mer_id DESC';
                    break;
            }


            import('@.ORG.system_page');
            $condition_merchant['mer_spread_code'] = $merchant_info['mer_code'];
            $count_merchant = $database_merchant->where($condition_merchant)->count();
            $p = new Page($count_merchant,15);
            $merchant_list = $database_merchant->field(true)->where($condition_merchant)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
            foreach ($merchant_list as &$v) {
                $where['spread_mer_id'] = $v['mer_id'];
                $where['mer_id'] = $mer_id;
                $where['order_type'] = 'market_spread';
                $all_spread_money = M('Merchant_recommend_merchant_record')->where($where)->sum('money');
                $v['all_spread_money'] = $all_spread_money>0?$all_spread_money:0;
            }

        } else {
            $merchant_list = array();
        }
        $this->assign('merchant_list',$merchant_list);
        $this->assign('merchant_info',$merchant_info);
        $this->display();

    }

    // 商家类型信息
    public function type_index() {

        $database_merchant_type = D('Merchant_type');

        $condition_type['type_status'] = array('lt', 2);//4：假删除状态

        // 处理排序
        $order = 'sort DESC,add_time DESC';

        $count_merchant_type = $database_merchant_type->where($condition_type)->count();

        import('@.ORG.system_page');
        $p = new Page($count_merchant_type,15);
        $merchant_type_list = $database_merchant_type->field(true)->where($condition_type)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

        $this->assign('merchant_type_list',$merchant_type_list);
        $pagebar = $p->show();
        $this->assign('pagebar',$pagebar);

        $this->display();
    }

    // 添加商家分类
    public function add_merchant_type(){
        if(IS_POST){
            $data['type_name'] = $_POST['type_name'];
            $data['type_status'] = intval($_POST['type_status']) ? intval($_POST['type_status']) :0;
            $data['sort'] = intval($_POST['sort']) ? intval($_POST['sort']) :0;
            $data['is_vip'] = intval($_POST['is_vip']);
            $data['add_uid'] = $this->system_session['id'];
            $data['add_time'] = time();
            if($data['type_name']){
                $res = M('Merchant_type')->add($data);
                if($res){
                    $this->frame_submit_tips(1,'添加成功！');
                }else{
                    $this->frame_submit_tips(0,'添加失败!');
                }
            }else{
                $this->frame_submit_tips(1,'参数传递错误!');
            }
        }else{
            $this->display();
        }
    }
    // 编辑商家分类
    public function edit_merchant_type(){
        if(IS_POST){
            $data['type_name'] = $_POST['type_name'];
            $data['type_status'] = intval($_POST['type_status']);
            $data['sort'] = intval($_POST['sort']);
            $data['type_id'] = intval($_POST['type_id']);
            $data['is_vip'] = intval($_POST['is_vip']);
            if($data['type_name']){
                $res = M('Merchant_type')->data($data)->save();
                $msg = '添加';
                if($data['type_id']){
                    $msg = '修改';
                }
                if($res!==false){
                    $this->frame_submit_tips(1,$msg.'成功！');
                }else{
                    $this->frame_submit_tips(0,$msg.'失败,请检查是否修改过参数，请修改过后再提交!');
                }
            }else{
                $this->frame_submit_tips(1,'参数传递错误!');
            }
        }else{
            $type_id  = intval($_GET['type_id']);
            if($type_id){
                $info = M('Merchant_type')->where(array('type_id'=>$type_id))->find();
                $this->assign('info',$info);
            }
            $this->display();
        }
    }


    /**
     * 切换商家分类状态
     */
    public function switch_merchant_type(){
        $type_id = $_POST['type_id'];
        $type_status = $_POST['type_status'];
        $community_type = M('Merchant_type');
        $info = $community_type->where(array('type_id'=>$type_id))->find();
        if (empty($info) || $info['type_status'] >= 2) {
            $this->error('该分类不存在或者已经删除');
        }
        if($type_id && in_array($type_status, array(0,1))){
            $res = $community_type->where(array('type_id'=>$type_id))->setField('type_status', $type_status);
            if($res){
                $this->success('操作成功！');
            }else{
                $this->error('操作失败！请重试~');
            }
        }else{
            $this->error_tips('参数传递错误');
        }
    }



    /**
     * 删除商家分类
     */
    public function del_merchant_type(){
        $type_id = $_POST['type_id'];
        $community_type = M('Merchant_type');
        $info = $community_type->where(array('type_id'=>$type_id))->find();
        if (empty($info) || $info['type_status'] >= 2) {
            $this->error('该分类不存在或者已经删除');
        }
        if($type_id){
            $res = $community_type->where(array('type_id'=>$type_id))->setField('type_status', 2);
            if($res){
                $this->success('操作成功！');
            }else{
                $this->error('操作失败！请重试~');
            }
        }else{
            $this->error_tips('参数传递错误');
        }
    }

	public function config(){
		redirect(U('Config/index',array('galias'=>'merchant','header'=>'Merchant/config')));
	}

	public function add_xiaowei(){
		$mer_id = intval($_GET['mer_id']);
		$database_merchant = D('Merchant');
		$condition_merchant['mer_id'] = intval($mer_id);
		$merchant = $database_merchant->field(true)->where($condition_merchant)->find();
		$this->assign("merchant", $merchant);

		$xiaowei = M("Merchant_xiaowei")->where(['mer_id'=>$mer_id])->order("addtime desc")->find();
		$this->assign("xiaowei", $xiaowei);
		$this->display();
	}

	public function add_xiaowei_submit(){
		$mer_id = $_POST['mer_id'];
		if(empty($mer_id)){
			$this->error('非法操作');
		}
		foreach ($_POST as $key => $value) {
			$_POST[$key] = trim($value);
		}
		$insert = [
			'mer_id' => $_POST['mer_id'],
			'id_card_copy' => $_POST['id_card_copy'],
			'id_card_copy_media_id' => $_POST['id_card_copy_media_id'],
			'id_card_national' => $_POST['id_card_national'],
			'id_card_national_media_id' => $_POST['id_card_national_media_id'],
			'sfz_name' => $_POST['sfz_name'],
			'sfz_number' => $_POST['sfz_number'],
			'start_time' => $_POST['start_time'],
			'end_time' => $_POST['end_time'],
			'account_name' => $_POST['account_name'],
			'account_bank' => $_POST['account_bank'],
			'search_city' => $_POST['search_city'],
			'bank_address_code' => $_POST['bank_address_code'],
			'account_number' => $_POST['account_number'],
			'store_name' => $_POST['store_name'],
			'search_store_city' => $_POST['search_store_city'],
			'store_address_code' => $_POST['store_address_code'],
			'store_street' => $_POST['store_street'],
			'store_entrance_pic' => $_POST['store_entrance_pic'],
			'store_entrance_pic_media_id' => $_POST['store_entrance_pic_media_id'],
			'indoor_pic' => $_POST['indoor_pic'],
			'indoor_pic_media_id' => $_POST['indoor_pic_media_id'],
			'merchant_shortname' => $_POST['merchant_shortname'],
			'service_phone' => $_POST['service_phone'],
			'product_desc' => $_POST['product_desc'],
			'rate' => $_POST['rate'],
			'contact' => $_POST['contact'],
			'contact_phone' => $_POST['contact_phone'],
			'status' => 0,
			'addtime' => time(),
			'result' => '',
		];
		if($_id = M("Merchant_xiaowei")->data($insert)->add()){
			$data = [
				'version' => '3.0',
				'cert_sn' => $this->getserial_no(),
				'mch_id' => C("config.pay_weixin_sp_mchid"),
				'nonce_str' => createRandomStr(32),
				'sign_type' => 'HMAC-SHA256',
				'sign' => '',
				'business_code' => rand(1000,9999).$mer_id, // 业务申请编号
				'id_card_copy' => $_POST['id_card_copy_media_id'], // 身份证人像面照片  media_id
				'id_card_national' => $_POST['id_card_national_media_id'], // 身份证国徽面照片
				'id_card_name' => $this->getEncrypt($_POST['sfz_name']),
				'id_card_number' => $this->getEncrypt($_POST['sfz_number']),
				'id_card_valid_time' => '["'.$_POST['start_time'].'","'.($_POST['end_time'] ? : '长期').'"]', // '["1970-01-01","长期"]' string(50)
				'account_name' => $this->getEncrypt($_POST['account_name']),
				'account_bank' => $_POST['account_bank'],
				'bank_address_code' => $_POST['bank_address_code'],
				'account_number' => $this->getEncrypt($_POST['account_number']),
				'store_name' => $_POST['store_name'],
				'store_address_code' => $_POST['store_address_code'],
				'store_street' => $_POST['store_street'],
				'store_entrance_pic' => $_POST['store_entrance_pic_media_id'],
				'indoor_pic' => $_POST['indoor_pic_media_id'],
				'merchant_shortname' => $_POST['merchant_shortname'],
				'service_phone' => $_POST['service_phone'],
				'product_desc' => $_POST['product_desc'] ?? '',
				'rate' => $_POST['rate'] ? $_POST['rate'].'%' : '0.6%',
				'contact' => $this->getEncrypt($_POST['contact']),
				'contact_phone' => $this->getEncrypt($_POST['contact_phone'])
			];
			// 签名
			$data['sign'] = $this->makeSign($data, $data['sign_type']);
			$url = 'https://api.mch.weixin.qq.com/applyment/micro/submit';
			// 数组转xml
			$xml = $this->toXml($data);
			// 发起入驻申请请求
			$res = $this->httpsRequest($url, $xml, [], true);
			// 处理返回值
			$rt = $this->disposeReturn($res, ['applyment_id'], ['business_code' => $data['business_code']]);
			if($rt['applyment_id']){
				M("Merchant_xiaowei")->where(['id'=>$_id])->data(['applyment_id'=>$rt['applyment_id']])->save();
				$this->success('添加进件成功，等待微信审核，请5分钟后再来查看审核结果！');
			}
			else{
				$this->error('进件失败！');
			}
		}
		else{
			$this->error('进件失败！请联系管理员');
		}

	}

	private function getEncrypt($str){
         //$str是待加密字符串
         // $public_key_path = '.'.C('config.pay_weixin_sp_client_cert');
         // $public_key = file_get_contents($public_key_path);
         $public_key = $this->cert_xiaowei;
         $encrypted = '';
         openssl_public_encrypt($str,$encrypted,$public_key);
         //base64编码
         $sign = base64_encode($encrypted);
         return $sign;
     }

     public function search_cityno(){
     	$keywords = trim(I("term"));
    	$condition['name'] = array('like','%'.$keywords.'%');
    	$data = M('Wx_cityno')->field("code as id,name as value")->where($condition)->select();
    	echo json_encode($data);
    	exit;
     }

     /**
     * MakeSign 生成签名
     * @param $data
     * @param string $signType
     * @return string
     */
    protected function makeSign(array $data, $signType = 'HMAC-SHA256')
    {

        //签名步骤一：按字典序排序参数
        ksort($data);

        $string = $this->toUrlParams($data);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . C("config.pay_weixin_sp_key");

        //签名步骤三：MD5加密或者HMAC-SHA256
        if ($signType == 'md5') {
            //如果签名小于等于32个,则使用md5验证
            $string = md5($string);
        } else {
            //是用sha256校验
            $string = hash_hmac("sha256", $string, C("config.pay_weixin_sp_key"));
        }
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * ToUrlParams     格式化参数格式化成url参数
     * @param $data
     * @return string
     */
    protected function toUrlParams(array $data)
    {
        $buff = "";
        foreach ($data as $k => $v) {
            if ($k != "sign" && $v !== "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 输出xml字符
     * @throws WxPayException
     **/
    protected function toXml($data)
    {
        if (!is_array($data) || count($data) <= 0) {
            throw new WxException(30001);
        }

        $xml = "<xml>";
        foreach ($data as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     * httpsRequest  https请求（支持GET和POST）
     * @param $url
     * @param null $data
     * @return mixed
     */
    protected function httpsRequest($url, $data = '', array $headers = [], $userCert = false, $timeout = 30)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        //设置超时
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        if ($userCert) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);//严格校验
            curl_setopt($curl, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($curl, CURLOPT_SSLKEYTYPE, 'PEM');
            $sslCertPath = '.'.C('config.pay_weixin_sp_client_cert');
            $sslKeyPath = '.'.C('config.pay_weixin_sp_client_key');
            curl_setopt($curl, CURLOPT_SSLCERT, $sslCertPath);
            curl_setopt($curl, CURLOPT_SSLKEY, $sslKeyPath);
        } else {
            if (substr($url, 0, 5) == 'https') {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 检查证书中是否设置域名
            }
        }
        if (!empty($headers)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            // curl_setopt($curl, CURLINFO_HEADER_OUT, true); //TRUE 时追踪句柄的请求字符串，从 PHP 5.1.3 开始可用。这个很关键，就是允许你查看请求header
            // $headers = curl_getinfo($curl, CURLINFO_HEADER_OUT); //官方文档描述是“发送请求的字符串”，其实就是请求的header。这个就是直接查看请求header，因为上面允许查看
        }
        curl_setopt($curl, CURLOPT_HEADER, true);    // 是否需要响应 header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output          = curl_exec($curl);
        $header_size     = curl_getinfo($curl, CURLINFO_HEADER_SIZE);    // 获得响应结果里的：头大小
        $response_header = substr($output, 0, $header_size);    // 根据头大小去获取头信息内容
        $http_code       = curl_getinfo($curl, CURLINFO_HTTP_CODE);    // 获取响应状态码
        $response_body   = substr($output, $header_size);
        $error           = curl_error($curl);
        curl_close($curl);

        return [$response_body, $http_code, $response_header, $error];
    }

    /**
     * disposeReturn 处理微信小微商户接口返回值
     * @param $res  httpsRequest 方法调用接口返回的数组
     * @param array $need_fields 需要接口返回的字段（当return_code 、result_code都为SUCCESS时返回的字段的key）
     * @param array $arr 自定义的参数返回出去，例如入驻接口生成的业务编号
     * @return array 微信返回系统级错误不暴露出去，直接返回操作失败，业务级别错误返回具体错误消息
     */
    protected function disposeReturn($res, array $need_fields = [], array $arr = [])
    {
        if ($res[1] == 200) {
            $rt = $this->fromXml($res[0]);
            if ($rt['return_code'] != 'SUCCESS') {
                return [];
            }
            if ($rt['result_code'] != 'SUCCESS') {
                return [];
            }
            if ($this->checkSign($rt)) {
                if (!empty($need_fields)) {
                    $need = [];
                    array_map(function($v) use ($rt, &$need) {
                        $need[$v] = $rt[$v] ?? '';
                    }, $need_fields);
                    return array_merge($need, $arr);
                }
                return $arr;
            }
            return [];
        }
    }

        /**
     * @param WxPayConfigInterface $config 配置对象
     * 检测签名
     */
    protected function checkSign($data)
    {
        strlen($data['sign']) <= 32 && $sign_type = 'md5';
        if ($this->makeSign($data, $sign_type ?? '') == $data['sign']) {
            return true;
        }
        throw new WxException(20000);
    }

    /**
     * 将xml转为array
     * @param string $xml
     * @throws WxPayException
     */
    protected function fromXml($xml)
    {
        if (!$xml) {
            throw new WxException(30000);
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $xml_parser = xml_parser_create();
        if (!xml_parse($xml_parser, $xml, true)) {
            xml_parser_free($xml_parser);
            throw new WxException(30000);
        } else {
            $arr = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        }
        return $arr;
    }

    protected function getserial_no(){
    	$data['mch_id'] = C("config.pay_weixin_sp_mchid");
    	$data['nonce_str'] = createRandomStr(32);
    	$data['sign_type'] = 'HMAC-SHA256';
    	// 签名
		$data['sign'] = $this->makeSign($data, $data['sign_type']);
		$url = 'https://api.mch.weixin.qq.com/risk/getcertficates';
		// 数组转xml
		$xml = $this->toXml($data);
		// 发起入驻申请请求
		$res = $this->httpsRequest($url, $xml, [], false);
		$rt = $this->disposeReturn($res, ['certificates']);
		$certificates = json_decode($rt['certificates'], true);
		$this->decodePem($certificates['data'][0]['encrypt_certificate']);

		return $certificates['data'][0]['serial_no'];
    }

    public function decodePem($data){
		$ciphertext = $data['ciphertext'];
		$nonce = $data['nonce'];
		$associated_data = $data['associated_data'];
		$key = C("config.pay_weixin_sp_key");
		$check_sodium_mod = extension_loaded('sodium');
		if($check_sodium_mod === false){
			echo '没有安装sodium模块';die;
		}
		$check_aes256gcm = sodium_crypto_aead_aes256gcm_is_available();
		if($check_aes256gcm === false){
			echo '当前不支持aes256gcm';die;
		}

		$pem = sodium_crypto_aead_aes256gcm_decrypt(base64_decode($ciphertext),$associated_data,$nonce,$key);
		$this->cert_xiaowei = $pem;
	}

	//查看审核结果
	public function view_status(){
		$mer_id = intval($_GET['mer_id']);
		$database_merchant = D('Merchant');
		$condition_merchant['mer_id'] = $mer_id;
		$merchant = $database_merchant->field(true)->where($condition_merchant)->find();
		$this->assign("merchant", $merchant);

		$last_submit = M("Merchant_xiaowei")->where(['mer_id'=>$mer_id])->order("addtime desc")->find();
		$status = $this->get_xiaowei_status($mer_id);
		if(!in_array($status,['3','5']) || empty($last_submit['applyment_id'])){
			$this->error("状态异常！");
		}
		$data = [
			'version' => '1.0',
			'mch_id' => C("config.pay_weixin_sp_mchid"),
			'nonce_str' => createRandomStr(32),
			'sign_type' => 'HMAC-SHA256',
			'applyment_id' => $last_submit['applyment_id']
		];
		$data['sign'] = $this->makeSign($data, $data['sign_type']);
		$url = 'https://api.mch.weixin.qq.com/applyment/micro/getstate';
		// 数组转xml
		$xml = $this->toXml($data);
		// 发起入驻申请请求
		$res = $this->httpsRequest($url, $xml, [], true);
		// 处理返回值
		$rt = $this->disposeReturn($res, ['applyment_state', 'applyment_state_desc', 'sub_mch_id', 'sign_url', 'audit_detail']);
		$result_status = "未知结果";
		$status_num = 0;
		$qrcode = "";
		if($rt['applyment_state'] == 'AUDITING'){
			$result_status = "还在审核中";
			$status_num = 1;
		}
		elseif($rt['applyment_state'] == 'REJECTED'){
			$audit_detail = json_decode($rt['audit_detail'], true);
			M("Merchant_xiaowei")->where(['id'=>$last_submit['id']])->data(['status'=>1, 'result'=>$rt['audit_detail']])->save();
			$result_status = "审核失败，原因：".$rt['applyment_state_desc']."。".$audit_detail['audit_detail'][0]['reject_reason'];
			$status_num = 2;
			$reurl = U("add_xiaowei?mer_id=".$mer_id);
		}
		elseif($rt['applyment_state'] == 'FROZEN'){
			M("Merchant_xiaowei")->where(['id'=>$last_submit['id']])->data(['status'=>1, 'result'=>$rt['audit_detail']])->save();
			$result_status = "审核已冻结，具体情况请咨询微信官方";
			$status_num = 3;
		}
		elseif($rt['applyment_state'] == 'TO_BE_SIGNED'){
			$qrcode = $rt['sign_url'];
			M("Merchant_xiaowei")->where(['id'=>$last_submit['id']])->data(['status'=>2, 'result'=>$rt['audit_detail']])->save();
			$result_status = "审核通过，请邀请商户扫码！注意：扫描二维码的微信所绑定的银行卡（实名认证）的持卡人姓名要跟小微商户姓名一致";
			$status_num = 4;
		}
		elseif($rt['applyment_state'] == 'FINISH'){
			M("Merchant_xiaowei")->where(['id'=>$last_submit['id']])->data(['status'=>3])->save();
			M("Merchant")->where(['mer_id'=>$last_submit['mer_id']])->data(['open_sub_mchid'=>1, 'sub_mch_id'=> $rt['sub_mch_id']])->save();
			$result_status = "审核通过，商户扫码已完成";
			$status_num = 5;
		}
		$this->assign("result_status", $result_status);
		$this->assign("status_num", $status_num);
		$this->assign("qrcode", $qrcode);
		$this->assign("reurl", $reurl);
		$this->display();
	}

	public function store_edit_on_sale(){
		$model = D('Merchant_store');
		if(IS_POST){
			$store_id = $_POST['store_id'];
			if(empty($store_id)){
				$this->success('店铺不存在！');
			}
			$r = $model->set_business_on_sale($store_id,$_POST);
			if($r){
				$this->success('设置成功！');die;
			}else{
				$this->error('修改失败！请检查内容是否有过修改（必须修改）后重试~');
			}
		}else{
			$store_id = $_GET['store_id'];
			$data = $model->get_business_on_sale($store_id);
			$this->assign('data',$data);
		}
		$this->display();
	}

	// 编辑支付手续费
	public function edit_pay_rates()
    {
        $merchant_percent_menu = M('System_menu')->where(array('module' => 'Merchant_percent'))->find();
        $this->assign('merchant_percent_menu', $merchant_percent_menu);
        if ($this->system_session['level'] < 2 && !in_array($merchant_percent_menu['id'], $this->system_session['menus'])) {
            $this->error_tips('您没有该权限');
        }
        if (IS_POST) {
            foreach ($_POST as $key => &$vv) {
                if ($vv == '' && $vv !== 0) {
                    $_POST[$key] = '';
                }
            }

        	$where['mer_id'] = $_POST['mer_id'];
            $where['store_id'] = $_POST['store_id'];
            if (M('Merchant_store_pay_rate')->where($where)->find()) {
                $result_save = M('Merchant_store_pay_rate')->where($where)->save($_POST);
            } else {
                $result_add = M('Merchant_store_pay_rate')->add($_POST);
            }

            if ($result_add || $result_save !== false) {
                $this->success('保存成功！');
                die;
            } else {
                $this->error('请检查数据是否修改!~');
            }

        } else {
            $where['mer_id'] = $_GET['mer_id'];
            $where['store_id'] = $_GET['store_id'];
            if ($mer_pr = M('Merchant_store_pay_rate')->where($where)->find()) {
                $this->assign('mer_pr', $mer_pr);
            }

            $mer_id = $_GET['mer_id'];
            $this->assign('mer_id', $mer_id);
        }
        $this->display();
    }

    public function mer_card_union(){
		$database_merchant_card_union = D('Merchant_card_union');
		if (IS_POST) {
			$mer_id = $_POST['mer_id'];
			$mer_ids = $_POST['mer_ids'];
			$condition_merchant['mer_id'] = $_POST['mer_id'];
			$condition_merchant['is_del'] = 0;

			if (!$mer_id) {
				$this->error('商家不存在');
			}

			// 原来的
			$old_mer_ids =array_column($database_merchant_card_union->where($condition_merchant)->select(), 'union_mer_id') ;
			// 需要添加
			$add_mer_ids = array_diff($mer_ids, $old_mer_ids);

			// 需要删除
			$del_mer_ids = array_diff($old_mer_ids, $mer_ids);

			if ($add_mer_ids) {
				foreach ($add_mer_ids as $key => $value) {
					$data = array(
						'mer_id' => $mer_id,
						'union_mer_id' => $value,
						'add_time' => time(),
					);
					$database_merchant_card_union->add($data);
				}
			}

			if ($del_mer_ids) {
				$database_merchant_card_union->where(array('mer_id'=>$mer_id,'union_mer_id'=>array('in',$del_mer_ids)))->setField('is_del','1');
			}

			$this->success('设置成功！');
		} else {

			$condition_merchant['u.mer_id'] = $_GET['mer_id'];
			$condition_merchant['u.is_del'] = 0;
			$count_merchant_card_union = $database_merchant_card_union->where($condition_merchant)->count();

			$merchant_list = $database_merchant_card_union->field('u.*,m.name,c.status')->where($condition_merchant)->join(' as u left join '.C('DB_PREFIX').'merchant as m on u.union_mer_id = m.mer_id left join '.C('DB_PREFIX').'card_new as c on u.union_mer_id=c.mer_id')->order('u.mer_id DESC')->select();
			$selected_mer_ids = array();
			foreach ($merchant_list as $key => $value) {
				$selected_mer_ids[] = $value['union_mer_id'];
				$merchant_list[$key]['user_count'] = M('Card_userlist')->where(array('mer_id'=>$value['union_mer_id'],'status'=>1))->count();
			}
			$this->assign('merchant_list',$merchant_list);
			$this->assign('selected_mer_ids',implode(',', $selected_mer_ids));
			$this->display();
		}
    }

    public function mer_list(){
		$database_merchant = D('Merchant');
		//搜索
        $selected = trim($_GET['selected'],',');
        $_GET['keyword'] = trim($_GET['keyword']);
		if(!empty($_GET['keyword'])){
			$condition_merchant['name'] = array('like','%'.$_GET['keyword'].'%');
		}

		if ($this->system_session['area_id']) {
			$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
			if($now_area['area_type']==3){
				$area_index = 'area_id';
			}elseif($now_area['area_type']==2){
				$area_index = 'city_id';
			}elseif($now_area['area_type']==1){
				$area_index = 'province_id';
			}
			$this->assign('admin_area',$now_area['area_type']);
			$condition_merchant[$area_index] = $this->system_session['area_id'];
		}

		if($_GET['province_idss'] && $this->config['many_city']){
			$condition_merchant['province_id'] =$_GET['province_idss'];
		}
		if($_GET['city_idss']){
			$condition_merchant['city_id'] =$where['city_id']= $_GET['city_idss'];
		}
		if($_GET['area_id']){
			$condition_merchant['area_id'] =$where['area_id']= $_GET['area_id'];
		}

		$condition_merchant['status'] = array('lt', 4);//4：假删除状态
		$condition_merchant['mer_id'] = array('neq', $_GET['mer_id']);//排除当前商家
		$order = 'mer_id DESC';

		$count_merchant = $database_merchant->where($condition_merchant)->count();

		import('@.ORG.system_page');
		$p = new Page($count_merchant,5);
		$merchant_list = $database_merchant->field(true)->where($condition_merchant)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

		foreach ($merchant_list as &$v) {
			if (in_array($v['mer_id'], explode(',', $selected))) {
				$v['select'] = 1;
			}
		}
		$this->assign('merchant_list',$merchant_list);
		$pagebar = $p->show();
		$this->assign('pagebar',$pagebar);
		$this->display();
    }

    public function ajaxGetMerchant(){
		$database_merchant = D('Merchant');
		//搜索
        $selected = trim($_GET['selected'],',');

		$condition_merchant['m.mer_id'] = array('in', $selected);
		$order = 'm.mer_id DESC';

		$merchant_list = $database_merchant->field('m.mer_id,m.name,c.status')->where($condition_merchant)->join(' as m left join '.C('DB_PREFIX').'card_new as c on m.mer_id=c.mer_id')->select();
		foreach ($merchant_list as $key => $value) {
			$merchant_list[$key]['user_count'] = M('Card_userlist')->where(array('mer_id'=>$value['mer_id'],'status'=>1))->count();
		}
		$merchant_list = $merchant_list ? $merchant_list : array();
		exit(json_encode($merchant_list));
    }

	//导出饭票充值二维码
	public function export_meal_cart(){

		set_time_limit(0);

       	$bak_name = 'meal_card_code_' . date('His') . '.zip';
		$zip_name_path = './runtime/meal_card_code'.date('Ymd').'/';
		$img_name_path = './runtime/meal_card_code'.date('Ymd').'/image/';

		if (!is_dir($zip_name_path)){
	        mkdir($zip_name_path,0777,true);
	    }

	    if (!is_dir($img_name_path)){
	        mkdir($img_name_path,0777,true);
	    }
		$zip_name_path .= $bak_name;
		$zip = new ZipArchive();

		if($zip->open($zip_name_path, ZipArchive::CREATE)=== TRUE){
			$result_list = M('Merchant')->field('mer_id,name')->select();
	       	$base_path = $this->config['site_url'].'/index.php?g=Index&c=Recognition&a=see_qrcode&type=meal_card&id=';
	        if (!empty($result_list)) {
	            foreach ($result_list as $value) {
					$image_path = $base_path.$value['mer_id'];
					$img = file_get_contents($image_path);
					file_put_contents($img_name_path.$value['mer_id'].'.png',$img);
					$image_name = $value['name'].'_饭票充值二维码'.'.png';
					$zip->addFile($img_name_path.$value['mer_id'].'.png', $image_name);
				}
	        }
			$zip->close();
		}

		header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header('Content-disposition: attachment; filename='.basename($bak_name)); //文件名
        header("Content-Type: application/zip"); //zip格式的
        header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件
        header('Content-Length: '. filesize($zip_name_path)); //告诉浏览器，文件大小
        @readfile($zip_name_path);
        exit();
	}

	//商户会员卡自定义标签
	public function diy_field(){
		$model = D('Merchant_card_field');
		$list = $model->get_list();
		$this->assign('list', $list);
		$this->display();
	}

	public function add_field(){

		$this->display();
	}

	public function edit_field(){
		$id  = intval($_GET['id']);
		$model = D('Merchant_card_field');
		$info = $model->get_one($id);
		$optstr = str_replace(",",PHP_EOL,$info['opt']);
		$this->assign('optstr',$optstr);
		$this->assign('info',$info);
		$this->display();
	}

	public function diy_field_modify(){
		/** type值：1单文本框2单选框3复选框4下拉框5多文本框****** */
		if (IS_POST) {
			$model = D('Merchant_card_field');
			$id = intval($_POST['id']);

			$data = array();
			$data['name'] = $_POST['name'];
			$data['url'] = $_POST['url'];
			$data['type'] = $_POST['type'];
			$data['icat_field_editnarr'] = $_POST['inarr'];
			$data['inunit'] = $_POST['inunit'];
			$data['isfilter'] = $_POST['isfilter'];
			$data['opt'] = str_replace(PHP_EOL,",",$_POST['valueoftype']);

			if($_POST['isfilter']==1){
				$check_filter=$model->check_filter($id);
				if(!$check_filter){
					$this->error('抱歉，最多只能设置4个筛选项！');exit;
				}
			}

			if($id){//编辑
				$relust = $model->save_row($data,$id);
				if($relust){
					$this->success('编辑成功！');
				}else{
					$this->error('编辑失败！');
				}

			}else{//新增

				$relust = $model->add_row($data);
				if($relust){
					$this->success('添加成功！');
				}else{
					$this->error('添加失败！');
				}
			}

		}else{
			$this->error('非法提交,请重新提交~');
		}
	}

	public function field_del(){
		$id = intval($_POST['id']);
		$r = D('Merchant_card_field')->del_row($id);
		if($r){
			$this->success('删除成功！');
		}else{
			$this->error('删除失败！请重试~');
		}
	}

    /**
     * 注册表单
     * @author: 张涛
     * @date: 2021/01/22
     */
    public function regFormSetting()
    {
        $fieldsMod = new Merchant_register_fieldsModel();
        $setting = $fieldsMod->order('sort DESC')->select();
        $cueFieldTypes = (new Merchant_register_fieldsModel())->cueFieldsType();
        $this->assign('cueFieldTypes', $cueFieldTypes);
        $this->assign('setting', $setting);
        $this->display();
    }

    /**
     * 设置表单
     * @author: 张涛
     * @date: 2021/01/22
     */
    public function addRegFormSetting()
    {
        if (IS_POST) {
            $fieldMod = new Merchant_register_fieldsModel();

            $_POST['name'] = trim($_POST['name']);
            if ($fieldMod->where(['name' => $_POST['name']])->find()) {
                $this->error('该填写项已经添加，请勿重复添加！');
            }

            $data['name'] = $_POST['name'];
            $data['type'] = $_POST['type'];
            $data['sort'] = strval($_POST['sort']);
            $data['iswrite'] = $_POST['iswrite'];
            if (!empty($_POST['use_field'])) {
                $data['use_field'] = explode(PHP_EOL, $_POST['use_field']);
                foreach ($data['use_field'] as $ulkey => $ulvalue) {
                    $data['use_field'][$ulkey] = trim($ulvalue);
                }
            }
            $data['use_field'] && $data['use_field'] = serialize($data['use_field']);
            if ($fieldMod->add($data)) {
                $this->success('添加成功！');
            } else {
                $this->error('添加失败！请重试~');
            }
        } else {
            $cueFieldTypes = (new Merchant_register_fieldsModel())->cueFieldsType();
            $this->assign('cueFieldTypes', $cueFieldTypes);
            $this->display();
        }
    }

    /**
     * 删除表单
     * @author: 张涛
     * @date: 2021/01/22
     */
    public function delRegFormSetting()
    {
        $id = intval($_POST['id']);
        (new Merchant_register_fieldsModel())->where(['id' => $id])->delete();
        $this->success('删除成功');
    }

    /**
     * 入驻合同管理
     * @author: 张涛
     * @date: 2021/01/27
     */
    public function contract()
    {
        $database_merchant = D('Merchant');
        //搜索
        $_GET['keyword'] = trim($_GET['keyword']);
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'mer_id') {
                $condition_merchant['m.mer_id'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'account') {
                $condition_merchant['m.account'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'name') {
                $condition_merchant['m.name'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'phone') {
                $condition_merchant['m.phone'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'store') {
                $where['name'] = array('like', '%' . htmlspecialchars($_GET['keyword']) . '%');
                $where['status'] = ['NEQ', 4];
                $store_list = M('Merchant_store')->field('mer_id')->where($where)->group('mer_id')->select();
                foreach ($store_list as $value) {
                    $store_arr[] = $value['mer_id'];
                }
                $condition_merchant['m.mer_id'] = array('in', $store_arr);
            }
        }
        $condition_merchant['m.is_sign_contract'] = 1;
		$join = 'LEFT JOIN ' . C('DB_PREFIX') . 'merchant_contract AS c ON m.mer_id=c.mer_id and c.status = 1';
		$fields = 'm.*,c.sign_time,c.contract_number';
        $count_merchant = $database_merchant->alias('m')->join($join)->where($condition_merchant)->count();

        import('@.ORG.system_page');
        $p = new Page($count_merchant, 15);
        $merchant_list = $database_merchant->alias('m')->join($join)->field($fields)->where($condition_merchant)->order('mer_id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $mer_contract_nums = [];
        if($merchant_list){
			$mer_ids = array_column($merchant_list,'mer_id');
			$mer_contract_info = D('Merchant_contract')->field('count(mer_id) as nums,mer_id')->where(['mer_id'=>['in',$mer_ids]])->group('mer_id')->select();
			$mer_contract_nums = array_column($mer_contract_info,'nums','mer_id');
		}
        foreach ($merchant_list as $k=>$v){
			$merchant_list[$k]['nums'] = $mer_contract_nums[$v['mer_id']]??0;
		}
        $this->assign('merchant_list', $merchant_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

    public function notContract()
    {
        $database_merchant = D('Merchant');
        //搜索
        $_GET['keyword'] = trim($_GET['keyword']);
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'mer_id') {
                $condition_merchant['m.mer_id'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'account') {
                $condition_merchant['m.account'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'name') {
                $condition_merchant['m.name'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'phone') {
                $condition_merchant['m.phone'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'store') {
                $where['name'] = array('like', '%' . htmlspecialchars($_GET['keyword']) . '%');
                $where['status'] = ['NEQ', 4];
                $store_list = M('Merchant_store')->field('mer_id')->where($where)->group('mer_id')->select();
                foreach ($store_list as $value) {
                    $store_arr[] = $value['mer_id'];
                }
                $condition_merchant['m.mer_id'] = array('in', $store_arr);
            }
        }
        $condition_merchant['m.is_sign_contract'] = array('neq',1);
        $condition_merchant['m.status'] = 1;
        $fields = 'm.*';
        $count_merchant = $database_merchant->alias('m')->where($condition_merchant)->count();

        import('@.ORG.system_page');
        $p = new Page($count_merchant, 15);
        $merchant_list = $database_merchant->alias('m')->field($fields)->where($condition_merchant)->order('mer_id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        foreach ($merchant_list as $k=>$v){
        	//查询是否有历史记录
			$his_con = D('Merchant_contract')->where(['mer_id'=>$v['mer_id']])->find();
			$merchant_list[$k]['is_his'] = $his_con?1:0;
		}
        $this->assign('merchant_list', $merchant_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }
    
    public function singleResign(){
		$mer_id = $_POST['mer_id']?:'';
		if(!$mer_id){
			exit(json_encode(['status'=>1,'data'=>L_('请选择要重签合同的商家！')]));
		}
		//查询商家是否存在
		$merchant_model = D('Merchant');
		$where = ['mer_id'=>$mer_id];
		$merchant = $merchant_model->where($where)->find();
		if(empty($merchant)||!$merchant){
			exit(json_encode(['status'=>2,'data'=>L_('商家不存在！')]));
		}
		D()->startTrans();
		try {
			$merchant_model->where($where)->save(['is_sign_contract'=>0,'contract_end_time'=>NULL]); //单个重签状态改成未签署
			$where['status'] = 1;
			D('Merchant_contract')->where($where)->save(['status'=>0]);
			D()->commit();
			exit(json_encode(['status'=>0,'data'=>L_('操作成功')]));
		}catch (\Exception $e){
			D()->rollback();
			exit(json_encode(['status'=>3,'data'=>L_('操作失败！')]));
		}
	}

    public function contractSetting()
    {
        $mod = M('Config_data');
        $fields = ['contract_must_sign', 'contract_second_company', 'contract_second_name', 'contract_second_tel', 'contract_second_address', 'contract_content','contract_official_seal','contract_effective_time','contract_effective_type','contract_can_renewed','contract_few_days_notice'];
        if (IS_POST) {
            if ($_FILES && $_FILES['contract_official_seal']['size'] > 0) {
                $image = D('Image')->handle($this->system_session['id'], 'contract', 0, array('size' => 10), false);
                if ($image['error']) {
                    $this->error($image['msg'] ? $image['msg'] : $image['message']);
                }
                $_POST = array_merge($_POST, $image['url']);
            }
            if(!$_POST['contract_effective_time']||!is_numeric($_POST['contract_effective_time'])||$_POST['contract_effective_time']<=0){
				$this->error(L_('合同有效期格式错误'));
			}
			if($_POST['contract_can_renewed']){
				if(!$_POST['contract_few_days_notice']||!is_numeric($_POST['contract_few_days_notice'])||$_POST['contract_few_days_notice']<=0){
					$this->error(L_('提前几天提醒用户续签合同格式错误'));
				}
			}else{
				$_POST['contract_few_days_notice'] = 0;
			}
            foreach ($_POST as $key => $v) {
                if (in_array($key, $fields)) {
                    $mod->where(['name' => $key])->save(['value' => $v]);
                }
            }
            $this->success('设置成功');
        } else {
            $configs = $mod->where(['name' => ['IN', $fields]])->select();
            $configs = array_column($configs, 'value', 'name');
            //dump($configs);exit();
            $this->assign('configs', $configs);
	    $this->display();
	}
    }

    /**
     * 平台余额抵扣金额
     * @date: 2021/12/27
     */
    public function balancePay($params = [])
    {
        $where = ['o.mer_id' => ['gt', 0], 'o.paid' => 1, 'o.system_status' => ['NOT IN', [4, 5]]];
        $params = $params ? $params : $_GET;
        $keyword = $params['keyword'] ? trim($params['keyword']) : '';;
        $beginTime = $params['begin_time'] ? trim($params['begin_time']) : '';
        $endTime = $params['end_time'] ? trim($params['end_time']) : '';

        if ($keyword) {
            $where['m.name'] = ['LIKE', '%' . $keyword . '%'];
        }
        if ($beginTime) {
            $where['o.use_time'][] = ['egt', strtotime($beginTime)];
        }
        if ($endTime) {
            $where['o.use_time'][] = ['elt', strtotime($endTime)];
        }

        if ($_GET['is_export']) {
            $lists = D('System_order')->alias('o')
                ->join(C('DB_PREFIX') . 'merchant m ON m.mer_id=o.mer_id')
                ->field('m.mer_id,m.name AS mer_name,sum(balance_pay) AS total_balance')
                ->where($where)
                ->group('m.mer_id')
                ->order('m.mer_id ASC')
                ->select();
            $config = [
                'header' => [
                    ['title' => L_('商家名称'), 'value_key' => 'mer_name'],
                    ['title' => L_('	平台余额使用总量'), 'value_key' => 'total_balance'],
                ],
                'body' => $lists,
                'file_name' => time(),
                'sheet_title' => '商家平台余额统计'
            ];
            R('System/Shop/download_store_list', ['config' => $config]);
        } else {
            import('@.ORG.system_page');
            $count = D('System_order')->alias('o')
                ->join(C('DB_PREFIX') . 'merchant m ON m.mer_id=o.mer_id')
                ->where($where)->count('DISTINCT m.mer_id');


            $p = new Page($count, 15);
            $lists = D('System_order')->alias('o')
                ->join(C('DB_PREFIX') . 'merchant m ON m.mer_id=o.mer_id')
                ->field('m.mer_id,m.name AS mer_name,sum(balance_pay) AS total_balance')
                ->where($where)
                ->limit($p->firstRow . ',' . $p->listRows)
                ->group('m.mer_id')
                ->order('m.mer_id ASC')
                ->select();
            $this->assign('lists', $lists);
            $this->assign('pagebar', $p->show());
            $this->display();
        }
    }


    public function viewContract()
    {
        $merId = I('get.mer_id', 0, 'intval');
		$id = I('get.id', 0, 'intval');
		$where = ['mer_id' => $merId];
		if($id){
			$where['id'] = $id;
		}else{
			$where['status'] = 1;
		}
        $contract = (new Merchant_contractModel())->where($where)->find();
        $contract['content_data'] = unserialize($contract['content_data']);
        if($contract['content_data']){
			$merchant = D('Merchant')->where(['mer_id'=>$merId])->find();
			$contract['content_data']['partner_a']['address'] .= $merchant?'  '.$merchant['address']:'';
		}
		$contract['content_data']['contract_content'] = replace_file_domain_content($contract['content_data']['contract_content']);
        $this->assign('contract', $contract);
        $this->display();
    }

    public function downloadContract(){
        require APP_PATH . 'Lib/ORG/mpdf/mpdf.php';
        try{
			define("_MPDF_TEMP_PATH", RUNTIME_PATH.'tmp/');
            $mpdf=new \mPDF('zh-cn','A4', 0, '宋体', 0, 0);
            $merId = I('get.mer_id', 0, 'intval');
			$id = I('get.id', 0, 'intval');
			$where = ['mer_id' => $merId];
			if($id){
				$where['id'] = $id;
			}else{
				$where['status'] = 1;
			}
            $contract = (new Merchant_contractModel())->where($where)->find();
            $contract['content_data'] = unserialize($contract['content_data']);
			if($contract['content_data']){
				$merchant = D('Merchant')->where(['mer_id'=>$merId])->find();
				$contract['content_data']['partner_a']['address'] .= $merchant?'  '.$merchant['address']:'';
			}
			$contract['content_data']['contract_content'] = replace_file_domain_content($contract['content_data']['contract_content']);
            $this->assign('contract', $contract);
            $html=  $this->fetch('viewContract');

            $html = str_replace('margin-left:100px;', '', $html);// es
            $mpdf->SetDisplayMode('fullpage');
            $mpdf->autoScriptToLang = true;
            $mpdf->autoLangToFont = true;
			$mpdf->showImageErrors = true;

            $mpdf->WriteHTML($html);
            $fileName=APP_PATH.'File/'.uniqid().'.pdf'; //保存到文件
            $mpdf->Output($fileName,'I');
        }catch (Exception $e){
            return false;
        }
    }

	//商家城市区域佣金设置
	public function areaCommission()
	{
		$merId = I('get.mer_id', 0, 'intval');
		$type = I('get.type','','trim');
		$merchant = D('Merchant')->where(['mer_id'=>$merId])->find();
		$setting = $merchant['area_business_commission_setting']?json_decode($merchant['area_business_commission_setting'],true):[];

		if (IS_POST) {
			$post = $_POST;
			unset($post['dosubmit']);
			$newSet = array_merge($setting,$post);

			D('Merchant')->where(['mer_id'=>$merId])->save(['area_business_commission_setting'=>json_encode($newSet,JSON_UNESCAPED_UNICODE)]);
			$this->success(L_('保存成功'));
		}

		$percent = [
			'area_percent_open' => $setting[$type . '_area_percent_open'] ?? 0,
			'province_percent' => $setting[$type . '_province_percent'] ?? '',
			'city_percent' => $setting[$type . '_city_percent'] ?? '',
			'area_percent' => $setting[$type . '_area_percent'] ?? '',
		];
		$this->assign('percent',$percent);
		$this->assign('mer_id', $merId);
		$this->display();
	}

	//导出拉卡拉钱账通对账文件
	public function downloadLakalaBill()
	{
		$export = I('get.export', 0, 'intval');
		$billDate = I('get.bill_date', '', 'trim');

		if ($export == 1 && $billDate) {
			//导出
			import('@.ORG.pay.Lakala');
        	$url = (new Lakala())->orderCheckDownload($billDate);
			$this->ajaxReturn(['url'=>$url]);
		} else {
			$this->display();
		}
	}
}
?>
