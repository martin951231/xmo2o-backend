<?php
/* 招聘
 * 20190327
 */
class RecruitAction extends BaseAction{
	public function __construct()
	{
		parent::__construct();
		if ($this->user_session['uid']) {
			// 企业信息
			$this->now_company = D('Recruit_company')->get_one($this->user_session['uid'],'uid');
			$this->assign('now_company',$this->now_company);
		}
	}

	public function check_login(){
		if(empty($this->user_session['uid'])){
			if($this->is_app_browser){
				$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
				$this->error_tips('请先进行登录！',U('Login/index',$location_param));
			}else{
				$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
				redirect(U('Login/index',$location_param));
			}
		}else{

			$now_user = D("User")->get_user($this->user_session['uid']);
			if(empty($now_user)){
				if($this->is_app_browser){
					$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
					$this->error_tips('请先进行登录！',U('Login/index',$location_param));
				}else{
					$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
					redirect(U('Login/index',$location_param));
				}
			}

		}
	}

	public function logout(){
		session('user',null);
		session('openid',null);
		session('now_village_bind',null);
	
		if($this->is_app_browser){
	        if(strpos($_SERVER['HTTP_USER_AGENT'],'pigcmso2oreallifeapp') !== false){
				preg_match('/ticket=(.*?),/',$_SERVER['HTTP_USER_AGENT'],$ticketArr);
				setcookie('disabled_ticket',$ticketArr[1]);
			}
			setcookie('app_ticket',null);
		}
		redirect(U('Recruit/index'));
	}

	public function ajax_check_login(){
		if(empty($this->user_session)){
			$this->returnCode('73000008');
		}else{
			$this->returnCode(0);
		}
	}

	public function index()
	{
		//顶部广告
		$wap_index_top_adver = D('Adver')->get_adver_by_key('wap_recruit_index',5);
		$this->assign('wap_index_top_adver',$wap_index_top_adver);

		//导航条
		$wap_recruit_index_slider = D('Slider')->get_slider_by_key('wap_recruit_index',0);
		$wap_index_slider = array();
		if(count($wap_recruit_index_slider) >= 10 &&$this->config['wap_slider_number'] == 10){
			$wap_slider_number = $this->config['wap_slider_number'];
		}else{
			$wap_slider_number = 8;
		}
		foreach($wap_recruit_index_slider as $key=>$value){
			$tmp_i = floor($key/$wap_slider_number);
			$wap_index_slider[$tmp_i][] = $value;
		}
		$this->assign('wap_recruit_index_slider',$wap_index_slider);

		// 滚动播报
		$scrollmsg= D('Recruit_scrollmsg')->where(array('business_type'=>'position'))->limit(8)->order('time desc')->select($data);

        $wap_slider_number = $this->config['wap_slider_number'];
		$this->assign('wap_slider_number',$wap_slider_number);
		$this->assign('scrollmsg',$scrollmsg);

		$long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);
		$this->assign('long_lat',$long_lat);


		$this->display();
	}

	public function search()
	{

		$this->display();
	}

// 搜索企业
	public function ajax_search_company_list()
	{

		$province = $_POST['province'];
		$company_name = array('like','%'.$_POST['company_name'].'%');
		if(empty($province)){
			$list = D('Recruit_company')->where(array('status'=>1,'company_name'=>$company_name))->order('company_id desc')->select();
		}else{
			$list = D('Recruit_company')->where(array('province'=>$province,'status'=>1,'company_name'=>$company_name))->order('company_id desc')->select();
		}

		foreach ($list as &$value) {
			$value['add_time_format'] = date('Y-m-d H:i:s',$value['add_time']);
			if ($value['company_pic']) {
				$value['company_pic'] = explode(';',json_decode($value['company_pic']));
				$value['image'] = file_domain().$value['company_pic'][0];
			}

			$value['position_count'] = M('Recruit_position')->where(array('company_id'=>$value['company_id'],'status'=>1))->count();
		}
		
		$this->returnCode(0,$list?$list:array());
	}


	// 首页推荐企业
	public function ajax_company_list()
	{

		if($_GET['user_long'] && $_GET['user_lat']){
			$long_lat = array(
				'long'=>$_GET['user_long'],
				'lat'=>$_GET['user_lat'],
			);
		}else if($_SESSION['openid']){
			$long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);
		}

		$pageSize = $_GET['pageSize'] ? $_GET['pageSize'] : 10;
		if ($_GET['type']) {
            $where['type'] = $_GET['type'];
			//首页推荐（区域推荐）
            switch ($_GET['type']) {
                case 'index':
                	if ($this->config['now_select_area']) {
                    	$where['now_area'] = $this->config['now_select_area']['area_id'];
                	}else{
                    	$now_area = $this->getLocationCity($long_lat['lat'],$long_lat['long']);
                    	$where['now_area'] = $now_area['now_area_id'];
                	}
                    break;
                case 'city':
                    break;
                case 'province':
                    break;
            }
		}
		$where['is_hide'] = 0;
		$list = D('Recruit_company')->get_wap_list_page($where,$pageSize,$long_lat,1);
		foreach ($list['list'] as &$value) {
            $value['add_time_format'] = date('Y-m-d H:i:s',$value['add_time']);
            if ($value['company_pic']) {
        		$value['company_pic'] = explode(';',json_decode($value['company_pic']));
            	$value['image'] = file_domain().$value['company_pic'][0];
            }
            if ($long_lat['long']&&$long_lat['lat']) {
				$value['range_int'] = getDistance($value['lat'],$value['lng'],$long_lat['lat'],$long_lat['long']);
				$value['range'] = getRange($value['range_int']);
            }
            $value['position_count'] = M('Recruit_position')->where(array('company_id'=>$value['company_id'],'status'=>1))->count();
            $field = 'cat.name as position_name,p.position_id';
	        $order = ' `p`.`position_id` DESC';
	        $join = 'as p LEFT JOIN '.C('DB_PREFIX').'Recruit_position_category as cat on `cat`.`id` = `p`.`position_cat_id`';
	        $condition_where = 'p.is_recommend = 1 AND p.status=1 AND p.company_id ='.$value['company_id'];
	        $position_recommend = D('Recruit_position')->join($join)->where($condition_where)->limit(3)->select();
	        $value['position_recommend'] = $position_recommend ? $position_recommend : array();

		}
		$this->returnCode(0,$list);
	}

	// 首页推荐企业详情
	public function company_details()
	{
		$company_id = intval($_GET['company_id']);
		$company_detail = D('Recruit_company')->get_one($company_id);

        $area_list = M('area')->select();
        $area_key = array();
        foreach ($area_list as $key => $value) {
        	$area_key[$value['area_id']] = $value['area_name'];
        }
        $company_detail['province'] = $area_key[$company_detail['province']];
        $company_detail['city'] = $area_key[$company_detail['city']];
        $company_detail['area'] = $area_key[$company_detail['area']];

		//职位
		// $position_list = D('Recruit_position')->where(array('company_id'=>$company_id,'status'=>1))->order('position_id DESC')->select();
		$field = 'p.*,c.name as position_name';
        $order = ' `p`.`position_id` DESC';
        $join = 'as p LEFT JOIN '.C('DB_PREFIX').'recruit_position_category as c on `p`.`position_cat_id` = `c`.`id`';
        $condition_where = 'p.status = 1 AND p.company_id='.$company_id;
        $position_list = D('Recruit_position')->field($field)->join($join)->where($condition_where)->select();
       foreach ($position_list as &$value) {
            $value['work_years'] = D('Recruit_position')->work_years_arr[$value['work_years']] ? D('Recruit_position')->work_years_arr[$value['work_years']] : '';
            $value['salary'] = D('Recruit_position')->salary_arr[$value['salary']] ? D('Recruit_position')->salary_arr[$value['salary']] : '';
            $value['qualifications'] = D('Recruit_position')->qualifications_arr[$value['qualifications']] ? D('Recruit_position')->qualifications_arr[$value['qualifications']] : '';
            $welfare_list = explode(',',$value['welfare']);
			$welfare_txt_list = array();
			foreach ($welfare_list as $_welfare) {
				$welfare_txt_list[] =  D('Recruit_position')->welfare_arr[$_welfare];
			}
			$value['welfare_text'] = $welfare_txt_list;
            $value['province'] = $area_key[$value['province']];
            $value['city'] = $area_key[$value['city']];
            $value['area'] = $area_key[$value['area']];
       }

		$this->assign('position_count',count($position_list));
		$this->assign('position_list',$position_list);
		$this->assign('company_detail',$company_detail);
		$this->display();
	}



	public function my(){
		$this->check_login();
		$now_user = D('User')->get_user($this->user_session['uid']);
		$level = M('User_level')->getField('level,lname,icon');
		$now_user['lname'] = $level[$now_user['level']]['lname'];
		$this->assign('now_user',$now_user);

		$site_phone = empty($this->config['site_phone'])?array():explode(' ',$this->config['site_phone']);
		$kefu['phone'] =$site_phone;

		// if ($this->is_weixin_browser && $this->config['open_kefu_url'] &&  $this->now_user['openid'] && $services = D('Customer_service')->where(array('mer_id' => $this->config['kefu_mer_id']))->select()) {
		// 	$key = $this->get_encrypt_key(array('app_id'=>$this->config['im_appid'],'openid' => $this->now_user['openid']), $this->config['im_appkey']);
		// 	$kefu['url'] = ($this->config['im_url'] ? $this->config['im_url'] : 'http://im-link.weihubao.com').'/?app_id=' . $this->config['im_appid'] . '&openid=' . $this->now_user['openid'] . '&key=' . $key . '#serviceList_' . $this->config['kefu_mer_id'];
		// }
		$this->assign('kefu',$kefu);
		// var_dump($now_user);

		$this->display();
	}

	//职位分类
	public function position_select(){
		if ($_GET['type'] == 'position') {
			// 企业信息
			if (!$this->now_company) {
				$this->error_tips('您还不是企业，请先申请成为企业用户');
			}
			cookie('user_address_position', 0);
		}
		
		$category = D('Recruit_position_category')->where(array('fid'=>0,'is_hide'=>0))->select();
		foreach ($category as $key => $value) {
			$sub_category = D('Recruit_position_category')->where(array('fid'=>$value['id'],'is_hide'=>0))->select();
			$category[$key]['sub'] = $sub_category;
		}
		$this->assign('category',$category);
		$this->display();

	}	

	//获取职位子分类
	public function ajax_get_subcategory(){
		$fid = $_POST['fid'];
		$sub_category = D('Recruit_position_category')->where(array('fid'=>$fid,'is_hide'=>0))->select();
		// var_dump(D('Recruit_position_category')->_sql());
		$sub_category = $sub_category ? $sub_category : array();
		echo json_encode($sub_category);exit;
	}

	//职位详情
	public function position_detail(){
		$long_lat = D('User_long_lat')->getLocation($_SESSION['openid'],60);
		$this->assign('long_lat',$long_lat);

		$position_id = intval($_GET['position_id']);
		$database_recruit_position = D('Recruit_position');
		$position_detail = $database_recruit_position->get_one($position_id);
		// var_dump($position_detail);
        $position_name = M('Recruit_position_category')->where(array('id'=>$position_detail['position_cat_id']))->find();
        $position_detail['position_name'] = $position_name['name'];
		$position_detail['work_years_text'] = D('Recruit_position')->work_years_arr[$position_detail['work_years']];
		$position_detail['qualifications_text'] = D('Recruit_position')->qualifications_arr[$position_detail['qualifications']];
		$position_detail['salary_text'] = D('Recruit_position')->salary_arr[$position_detail['salary']];

		$welfare_list = explode(',',$position_detail['welfare']);
		$welfare_txt_list = array();
		foreach ($welfare_list as $_welfare) {
			$welfare_txt_list[] =  D('Recruit_position')->welfare_arr[$_welfare];
		}
		$position_detail['welfare_text'] = $welfare_txt_list;

		// 距离
		if ($long_lat) {
			$position_detail['distance'] = getDistance($position_detail['lat'],$position_detail['lng'],$long_lat['lat'],$long_lat['long']);
		}

		// 推荐职位
		$where['position_cat_id'] = $position_detail['position_cat_id'];
		$where['position_id'] = array('neq',$position_detail['position_id']);
		$recommend_position_list = $database_recruit_position->recommend_position($where);

		// 是否收藏
        $collect_info = M('Recruit_collect')->where(array('cid'=>$position_detail['position_id'],'type'=>'position','uid'=>$this->user_session['uid']))->find();

        // 城市名
        $city = D('Area')->where(array('area_id'=>$position_detail['city_id']))->find();
		$this->assign('city_name',$city['area_name']);

		// 浏览量
		$database_recruit_position->where(array('position_id'=>$position_id))->setInc('views',1);

		// 简历
		$resume_list = M("Recruit_resume")->where(array('uid'=>$this->user_session['uid']))->select();
		
		$this->assign('resume_list',$resume_list);
		$this->assign('collect_info',$collect_info);
		$this->assign('recommend_position_list',$recommend_position_list);
		$this->assign('position_detail',$position_detail);
		$this->display();

	}

	//职位编辑 添加
	public function position_edit(){
		$database_recruit_position = D('Recruit_position');

		// 企业信息
		if (!$this->now_company) {
			$this->error_tips('您还不是企业，请先申请成为企业用户');
		}

		if (IS_POST) {
			// TODO 验证 城市 详情
			$_POST['uid'] = $this->user_session['uid'];
			$_POST['company_id'] = $this->now_company['company_id'];
			$_POST['add_time'] = time();
			$_POST['status'] = 0; 
			if ($_POST['position_id']) {
				$now_position = $database_recruit_position->where(array('position_id'=>$_POST['position_id']))->find();
				if (!$now_position) {
					exit(json_encode(array('code'=>1,'msg'=>'职位不存在','url'=>'')));
				}
				if ($now_position['uid'] != $this->user_session['uid']) {
					exit(json_encode(array('code'=>1,'msg'=>'您没有权限编辑','url'=>'')));
				}

				$add = $database_recruit_position->where(array('position_id'=>$_POST['position_id']))->save($_POST);
				if ($add!==false) {
					cookie('user_address_position_edit', 0);
					exit(json_encode(array('code'=>0,'msg'=>'编辑成功','url'=>'')));
				}else{
					exit(json_encode(array('code'=>1,'msg'=>'编辑失败','url'=>'')));
				}
			}else{
				$add = $database_recruit_position->add($_POST);
				if ($add!==false) {
					cookie('user_address_position_edit', 0);
					exit(json_encode(array('code'=>0,'msg'=>'添加成功','url'=>U('index'))));
				}else{
					exit(json_encode(array('code'=>1,'msg'=>'添加失败','url'=>'')));
				}
			}
			
		}else{

			if(!$this->is_app_browser){
				cookie('user_address_position_edit', 0);
			}
			// 工作年限
			$work_years_arr = D('Recruit_position')->work_years_arr;
			// 学历
			$qualifications_arr = D('Recruit_position')->qualifications_arr;
			// 月薪
			$salary_arr = D('Recruit_position')->salary_arr;
			// 福利
			$welfare_arr = D('Recruit_position')->welfare_arr;

			$work_years_arr_temp = array();
			foreach ($work_years_arr as $key => $value) {
				$work_years_arr_temp[] = array(
					'title' => $value,
					'value' => $key,
				);
			}
			$qualifications_arr_temp = array();
			foreach ($qualifications_arr as $key => $value) {
				$qualifications_arr_temp[] = array(
					'title' => $value,
					'value' => $key,
				);
			}
			$salary_arr_temp = array();
			foreach ($salary_arr as $key => $value) {
				$salary_arr_temp[] = array(
					'title' => $value,
					'value' => $key,
				);
			}
			$welfare_arr_temp = array();
			foreach ($welfare_arr as $key => $value) {
				$welfare_arr_temp[] = array(
					'title' => $value,
					'value' => $key,
				);
			}
			$this->assign('work_years_arr',json_encode($work_years_arr_temp));
			$this->assign('qualifications_arr',json_encode($qualifications_arr_temp));
			$this->assign('salary_arr',json_encode($salary_arr_temp));
			$this->assign('welfare_arr',json_encode($welfare_arr_temp));

			$cookie_message = json_decode($_COOKIE['user_address_position_edit']);
			if ($_GET['position_id']&& !$cookie_message) {
				$now_position = D('Recruit_position')->get_one($_GET['position_id']);
				$detail['name'] = $now_position['name'];
				$detail['position_cat_id'] = $now_position['position_cat_id'];
				$detail['position_name'] = $now_position['position_name'];
				$detail['province'] = $now_position['province'];
				$detail['area'] = $now_position['area'];
				$detail['city'] = $now_position['city'];
				$detail['lng'] = $now_position['lng'];
				$detail['lat'] = $now_position['lat'];
				$detail['address'] = $now_position['address'];
				$detail['work_years'] = $now_position['work_years'];
				$detail['qualifications'] = $now_position['qualifications'];
				$detail['salary'] = $now_position['salary'];
				$detail['welfare'] = $now_position['welfare'];
				$detail['work_years_txt'] = $work_years_arr[$now_position['work_years']];
				$detail['qualifications_txt'] = $qualifications_arr[$now_position['qualifications']];
				$detail['salary_txt'] = $salary_arr[$now_position['salary']];
				$welfare_list = explode(',',$now_position['welfare']);
				$welfare_txt_list = array();
				foreach ($welfare_list as $_welfare) {
					$welfare_txt_list[] = $welfare_arr[$_welfare];
				}
				$detail['welfare_txt'] = implode(',',$welfare_txt_list);
				
				cookie('user_address_position_edit', json_encode($detail));
				$this->assign('now_position',$now_position);
			}
			$this->display();
		}
		
	}

	//职位描述详情添加
	public function position_experience(){
		$this->display();

	}

	// 收藏类型:position-职位，resume-简历,course-商学院视频
	public function do_collect(){
		$cid = intval($_POST['cid']);
		$type = $_POST['type'] ? $_POST['type'] : 'position';
		$is_cancel = intval($_POST['is_cancel']);

		$collect_info = M('Recruit_collect')->where(array('cid'=>$cid,'type'=>$type,'uid'=>$this->user_session['uid']))->find();

		if ($is_cancel) { 
			// 取消收藏
			if (!$collect_info) {
				$this->returnCode(1,array(),'您未收藏此信息');
			}

			$where = array(
				'cid'=>$cid,
				'type'=>$type,
				'uid'=>$this->user_session['uid']
			);
	        if($collect_id = M('Recruit_collect')->where($where)->delete()){
				$this->returnCode(0,'取消收藏成功');
	        }else{
				$this->returnCode(1,array(),'取消收藏失败');
	        }
		}else{
			if ($collect_info) {
				$this->returnCode(1,array(),'你已收藏');
			}
			$data = array(
				'cid'=>$cid,
				'type'=>$type,
				'uid'=>$this->user_session['uid'],
				'time'=>time(),
			);
	        if($collect_id = M('Recruit_collect')->data($data)->add()){
				$this->returnCode(0,'收藏成功');
	        }else{
				$this->returnCode(1,array(),'收藏失败');
	        }
		}
	}

	// 验证是否登录绑定手机号
	public function ajax_check_status(){
		$now_user = D('User')->get_user($this->user_session['uid']);
		if(!$now_user){
			$this->returnCode('73000008');
		}

		if(!$now_user['phone']){
			$this->returnCode('20120035');
		}
		$this->returnCode(0);
	}

	// 简历
	public function resume_list()
	{
		cookie('user_address_resume', 0);
		//工作年限
		$work_years_arr = D('Recruit_position')->work_years_arr;
		// var_dump($work_years_arr);
		$work_years_arr[1] = '应届毕业生';
		$this->assign('work_years_arr',$work_years_arr);
		$this->display();
	}

	public function ajax_resume_list()
	{
		$pageSize = $_GET['pageSize'] ? $_GET['pageSize'] : 10;

		// 性别
		if (intval($_GET['sex'])>0) {
			$where['sex'] = intval($_GET['sex']);
		}

		// 工作年限
		if (intval($_GET['work_years'])) {
			$where['work_years'] = intval($_GET['work_years']);
		}

		// 职位
		if (intval($_GET['position_cat_id'])) {
			$where['position_cat_id'] = intval($_GET['position_cat_id']);
		}

		$where['city'] = C('config.now_city');
		// 城市
		if (intval($_GET['city_id'])) {
			$where['city'] = intval($_GET['city_id']);
		}

		// 区域
		if (intval($_GET['area_id'])) {
			$where['area'] = intval($_GET['area_id']);
		}

		if ($_GET['order_by']) {
			$where['order_by'] = $_GET['order_by'];
		}

		if ($_POST['keyword']) {
			$where['other'] = '`c`.`name` like "%'.$_POST['keyword'].'%"';
		}


		$where['has_work'] = 1;
		$where['has_education'] = 1;
		$where['is_hide'] = 0;
		if ($where['other'] ) {
			$where['other'] .= ' AND r.self_introduction <>"" AND r.self_introduction is not null';
		}else{
			$where['other'] = 'r.self_introduction <>"" AND r.self_introduction is not null';
		}
		$list = D('Recruit_resume')->get_limit_list_page($where,$pageSize,1);
		// 工作年限
		$work_years_arr = D('Recruit_position')->work_years_arr;
		$work_years_arr[1] = '应届毕业生';
		// 学历
		$qualifications_arr = D('Recruit_position')->qualifications_arr;
		// 月薪
		$salary_arr = D('Recruit_position')->salary_arr;
		// 福利
		$welfare_arr = D('Recruit_position')->welfare_arr;
		foreach ($list['list'] as &$value) {
            $value['work_years'] = $work_years_arr[$value['work_years']] ? $work_years_arr[$value['work_years']] : '';
            $value['salary'] = $salary_arr[$value['salary']] ? $salary_arr[$value['salary']] : '';
            $value['qualifications'] = $qualifications_arr[$value['qualifications']] ? $qualifications_arr[$value['qualifications']] : '';

            $value['position_name'] = $value['position_name'] ? $value['position_name'] : '未知';
            $value['add_time_format'] = date('Y-m-d H:i:s',$value['add_time']);
            $value['update_time_format'] = tmspan($value['update_time'],time());
        	$value['age'] = $value['birthday'] ? date('Y',time()) - date('Y',strtotime($value['birthday'])) : '0';


            $value['can_view'] = 0;
            if ($this->user_session['uid']==$value['uid']) {
            	$value['can_view'] = 1;
            }else{
	            $resume_order = M('Recruit_resume_order')->where(array('uid'=>$this->user_session['uid'],'resume_id'=>$value['resume_id'],'paid'=>1))->find();
				if ($resume_order) {
					// 已购买
	            	$value['can_view'] = 1;
				}
			}
		}
		$this->returnCode(0,$list);
	}

	// 简历详情
	public function resume_detail()
	{
		$this->check_login();
		$database_recruit_resume = D('Recruit_resume');
		$resume_id = intval($_GET['resume_id']);
		$now_resume = $database_recruit_resume->get_one($resume_id);

		if (!$now_resume) {
			$this->error_tips('简历不存在');
		}
		
		// 工作年限
		$work_years_arr = D('Recruit_position')->work_years_arr;
		$work_years_arr[1] = '应届毕业生';
		// 学历
		$qualifications_arr = D('Recruit_position')->qualifications_arr;
		// 月薪
		$salary_arr = D('Recruit_position')->salary_arr;
		$now_resume['work_years'] = $work_years_arr[$now_resume['work_years']];
		$now_resume['qualifications'] = $qualifications_arr[$now_resume['qualifications']];
		$now_resume['salary'] = $salary_arr[$now_resume['salary']];
        $now_resume['age'] = $now_resume['birthday'] ? date('Y',time()) - date('Y',strtotime($now_resume['birthday'])) : '';
        // 简历更新时间
        $now_resume['update_time_format'] = tmspan($now_resume['update_time']);

        if ($now_resume['position_cat_id']) {
            $cat = M('Recruit_position_category')->where(array('id'=>$now_resume['position_cat_id']))->find();
            $now_resume['position_name'] = $cat['name'];
        }

		//是否可以查看全部简历
		$is_self = 0;
		if ($this->user_session['uid'] != $now_resume['uid']) {
			if (!$this->now_company||$this->now_company['status']!=1) {
				$this->error_tips('您没有权限查看');
			}
		}else{
			$is_self = 1;
		}
		$this->assign('is_self',$is_self);
		$can_view = 0;
		if ($now_resume['uid'] == $this->user_session['uid']) {
			$can_view = 1;
		}
		
		if (C('config.recruit_view_resume_need_score') > 0) {
			// 是否已付费
			if ($now_order = D('Recruit_resume_order')->where(array('resume_id'=>$resume_id,'uid'=>$this->user_session['uid'],'paid'=>1))->find()) {
				$can_view = 1;
			}
		}else{
			// 免费
			$can_view = 1;
		}
		
		// 是否收藏
        $collect_info = M('Recruit_collect')->where(array('cid'=>$resume_id,'type'=>'resume','uid'=>$this->user_session['uid']))->find();
		$this->assign('collect_info',$collect_info);
		
		// 浏览量加1
		$database_recruit_resume->where(array('resume_id'=>$resume_id))->setInc('view_times',1);


		// 是否购买了套餐
		if ($this->now_company['status'] == 1&&$this->now_company['resume_times'] > 0&&$this->now_company['energy_expiration_time'] > time()) {
			$resume_times = $this->now_company['resume_times'];
		}
		$this->assign('resume_times',$resume_times?$resume_times:0);

		$position_list = M('Recruit_position')->field('c.name as position_name,p.position_id')->join(' as p LEFT JOIN '.C('DB_PREFIX').'recruit_position_category as c on p.position_cat_id=c.id')->where(array('p.company_id'=>$this->now_company['company_id'],'p.status'=>1))->select();
		$this->assign('position_list',$position_list);

		cookie('user_address_resume', 0);
		$this->assign('can_view',$can_view);
		$this->assign('now_resume',$now_resume);
		$this->display();
	}

	// 查看简历
	public function do_resume_view(){
		$resume_id = intval($_POST['resume_id']);
		$now_resume = D('Recruit_resume')->get_one($resume_id);
		if (!$now_resume) {
			exit(json_encode(array('error'=>1,'msg'=>'简历不存在或已删除')));
		}

		if (!$this->user_session['uid']) {
			exit(json_encode(array('error'=>2,'msg'=>'请先登录')));
		}

		if ($this->user_session['uid'] == $now_resume['uid']) {
			//自己的简历
			exit(json_encode(array('error'=>0,'msg'=>'成功')));
		}

		if (!$this->now_company) {
			exit(json_encode(array('error'=>3,'msg'=>'请先申请成为企业用户')));
		}

		if ($this->now_company['status'] != 1) {
			exit(json_encode(array('error'=>1,'msg'=>'您的企业未通过审核')));
		}

		$resume_order = M('Recruit_resume_order')->where(array('uid'=>$this->user_session['uid'],'resume_id'=>$resume_id,'paid'=>1))->find();
		if ($resume_order) {
			// 已购买
			exit(json_encode(array('error'=>0,'msg'=>'成功')));
		}

		$data = array(
			'resume_id'=>$resume_id,
			'uid'=>$this->user_session['uid'],
			'paid'=>1,
			'add_time'=>time(),
			'pay_time'=>time(),
		);
		$data['score'] = C('config.recruit_view_resume_need_score');

		// 扣除积分
		if (C('config.recruit_view_resume_need_score')>0) {
			if ($this->now_company['resume_times'] > 0 && $this->now_company['energy_expiration_time'] >= time()) {
				$data['pay_type'] = 2;
				$res = D('Recruit_company')->where(array('company_id'=>$this->now_company['company_id']))->setDec('resume_times',1);
			}else{
				$data['pay_type'] = 1;
				$now_user = D('User')->get_user($this->user_session['uid']);
				if ($now_user['score_count']<C('config.recruit_view_resume_need_score')) {
					exit(json_encode(array('error'=>4,'msg'=>'您的积分不足，请充值')));
				}

				// 减少积分
				$res = D('User')->use_score($now_user['uid'],C('config.recruit_view_resume_need_score'),'查看简历扣除积分');
				if ($res['error_code']) {
					exit(json_encode(array('error'=>1,'msg'=>$res['msg'])));
				}
			}
		}
	
        if($id = M('Recruit_resume_order')->data($data)->add()){
			exit(json_encode(array('error'=>0,'msg'=>'成功','phone'=>$now_resume['phone'])));
        }else{
			exit(json_encode(array('error'=>1,'msg'=>'查看失败,请重试')));
        }
	}

	// 刷新简历
	public function resume_refresh(){
		$resume_id = intval($_POST['resume_id']);
		$now_resume = D('Recruit_resume')->get_one($resume_id);
		if (!$now_resume) {
			exit(json_encode(array('error'=>1,'msg'=>'简历不存在或已删除')));
		}

		if (!$this->user_session['uid']) {
			exit(json_encode(array('error'=>2,'msg'=>'请先登录','url'=>U('Login/index'))));
		}

		$data = array(
			'resume_id'=>$resume_id,
			'uid'=>$this->user_session['uid'],
			'paid'=>1,
			'add_time'=>time(),
			'pay_time'=>time(),
		);
		$data['score'] = floatval(C('config.recruit_refresh_resume_score'));

		// 扣除积分
		if ($data['score']>0) {
			
			$now_user = D('User')->get_user($this->user_session['uid']);
			if ($now_user['score_count']<$data['score']) {
				exit(json_encode(array('error'=>4,'msg'=>'您的积分不足，请充值','url'=>U('score_package'))));
			}

			// 减少积分
			$res = D('User')->use_score($now_user['uid'],$data['score'],'刷新简历扣除积分');
			if ($res['error_code']) {
				exit(json_encode(array('error'=>1,'msg'=>$res['msg'])));
			}			
		}
		//  refresh_time
        if($id = M('Recruit_resume_refresh_order')->data($data)->add()){
        	M('Recruit_resume')->where(array('resume_id'=>$resume_id))->setField('update_time',time());        	
			exit(json_encode(array('error'=>0,'msg'=>'刷新成功','phone'=>$now_resume['phone'])));
        }else{
			exit(json_encode(array('error'=>1,'msg'=>'刷新失败,请重试')));
        }
	}

	// 我的基础简历
	public function my_resume_base()
	{
		$this->check_login();
		$database_recruit_resume = D('Recruit_resume');
		$resume_id = intval($_GET['resume_id']);
		$now_resume = $database_recruit_resume->where(array('resume_id'=>$resume_id))->find();

		if (IS_POST) {
			if (C('config.sms_key')!='') {
				$sms_verify_result = D('Smscodeverify')->verify($_POST['sms_code'], $_POST['phone']);
	            if ($sms_verify_result['error_code']) {
	                exit(json_encode(array("error_code"=>1,"msg"=>$sms_verify_result['msg'])));
	            }
			}
			
			$_POST['uid'] = $this->user_session['uid'];
			if ($_POST['resume_id']) {
				$resume_id = $_POST['resume_id'];
				$_POST['update_time'] = time();
				$res = $database_recruit_resume->save($_POST);
			}else{
				// 验证简历是否达到上限
				$resume_total = $database_recruit_resume->where(array('uid'=>$this->user_session['uid']))->count();
				if ($resume_total >= C('config.recruit_resume_limit')&&C('config.recruit_resume_limit')) {
					exit(json_encode(array('code'=>1,'msg'=>'简历数已到达上限，不能再添加','url'=>'')));
				}
				$_POST['add_time'] = time();
				$_POST['update_time'] = time();
				$res = $resume_id = $database_recruit_resume->add($_POST);
			}
			if ($res!==false) {
				// 发送模板消息
				$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $href = C('config.site_url') . '/wap.php?g=Wap&c=Recruit&a=my_resume_edit&resume_id='.$resume_id;
                $model->sendTempMsg('TM00017',
                    array('href' => $href,
                        'wecha_id' => $this->user_session['openid'],
                        'first' => '生成简历成功，请继续完善简历，便于更好的前台展示简历',
                        'OrderSn' => $resume_id,
                        'OrderStatus' => '待处理',
                        'remark' => '请您及时处理！'
                    )
                );
				exit(json_encode(array('code'=>0,'msg'=>'提交成功','url'=>U('my_resume_edit',array('resume_id'=>$resume_id)))));
			}else{
				exit(json_encode(array('code'=>1,'msg'=>'提交失败','url'=>'')));
			}
		}else{
			if (!$now_resume) {
				// 验证简历是否达到上限
				$resume_total = $database_recruit_resume->where(array('uid'=>$this->user_session['uid']))->count();
				if ($resume_total >= C('config.recruit_resume_limit')&&C('config.recruit_resume_limit')) {
					$this->error_tips('简历数已到达上限，不能再添加');
				}
			}
			if(!$this->is_app_browser){
				cookie('user_address_resume', 0);
			}
			// var_dump($now_resume);
			// 工作年限
			$work_years_arr = D('Recruit_position')->work_years_arr;
			$work_years_arr[1] = '应届毕业生';
			// 学历
			$qualifications_arr = D('Recruit_position')->qualifications_arr;
			// 月薪
			$salary_arr = D('Recruit_position')->salary_arr;
		
			$now_resume['work_years_txt'] = $work_years_arr[$now_resume['work_years']];
			$now_resume['qualifications_txt'] = $qualifications_arr[$now_resume['qualifications']];
			$now_resume['salary_txt'] = $salary_arr[$now_resume['salary']];

			$work_years_arr_temp = array();
			foreach ($work_years_arr as $key => $value) {
				$work_years_arr_temp[] = array(
					'title' => $value,
					'value' => $key,
				);
			}
			$qualifications_arr_temp = array();
			foreach ($qualifications_arr as $key => $value) {
				$qualifications_arr_temp[] = array(
					'title' => $value,
					'value' => $key,
				);
			}
			$salary_arr_temp = array();
			foreach ($salary_arr as $key => $value) {
				$salary_arr_temp[] = array(
					'title' => $value,
					'value' => $key,
				);
			}
			$this->assign('work_years_arr',json_encode($work_years_arr_temp));
			$this->assign('qualifications_arr',json_encode($qualifications_arr_temp));
			$this->assign('salary_arr',json_encode($salary_arr_temp));
			
			$cookie_message = json_decode($_COOKIE['user_address_resume']);
			if ($now_resume['resume_id'] && !$cookie_message) {
				$cookie_arr = [];
				$cookie_arr['avatar'] = $now_resume['avatar'];
				$cookie_arr['birthday'] = $now_resume['birthday'];
				$cookie_arr['qualifications'] = $now_resume['qualifications'];
				$cookie_arr['qualifications_txt'] = $now_resume['qualifications_txt'];
				$cookie_arr['work_years'] = $now_resume['work_years'];
				$cookie_arr['work_years_txt'] = $now_resume['work_years_txt'];
				$cookie_arr['salary'] = $now_resume['salary'];
				$cookie_arr['salary_txt'] = $now_resume['salary_txt'];
				$cookie_arr['position_cat_id'] = $now_resume['position_cat_id'];

				$position_name = M('Recruit_position_category')->where(array('id'=>$now_resume['position_cat_id']))->find();
				$cookie_arr['position_name'] = $position_name['name'] ? $position_name['name'] : '';
				if ($now_resume['lat']) {
					$cookie_arr['address'] = $now_resume['address'];
					$cookie_arr['lat'] = $now_resume['lat'];
					$cookie_arr['lng'] = $now_resume['lng'];
					$cookie_arr['province'] = $now_resume['province'];
					$cookie_arr['city'] = $now_resume['city'];
					$cookie_arr['area'] = $now_resume['area'];
				}
				cookie('user_address_resume',json_encode($cookie_arr));
			}
			
			$this->assign('now_resume',$now_resume);
			$this->display();
		}
	}

	// 简历
	public function my_resume_edit()
	{
		$this->check_login();
		$database_recruit_resume_detail = D('Recruit_resume_detail');
		$resume_id = intval($_GET['resume_id']);
		$now_resume = D('Recruit_resume')->get_one($resume_id);
		if (IS_POST) {
			if (!$now_resume['has_work']) {
				exit(json_encode(array('code'=>1,'msg'=>'请完善工作经历','url'=>'')));
			}
			if (!$now_resume['has_education']) {
				exit(json_encode(array('code'=>1,'msg'=>'请完善教育经历','url'=>'')));
			}
			if (!trim($_POST['self_introduction'])) {
				exit(json_encode(array('code'=>1,'msg'=>'请完善个人简介','url'=>'')));
			}
			$_POST['update_time'] = time();
			$res = D('Recruit_resume')->save($_POST);

			if ($res) {
				exit(json_encode(array('code'=>0,'msg'=>'提交成功','url'=>U('my_resume_list'))));
			}else{
				exit(json_encode(array('code'=>1,'msg'=>'提交失败','url'=>'')));
			}
		}else{

			// 学历
			$qualifications_arr = D('Recruit_position')->qualifications_arr;
			$qualifications_arr_temp = array();
			foreach ($qualifications_arr as $key => $value) {
				$qualifications_arr_temp[] = array(
					'title' => $value,
					'value' => $key,
				);
			}
			$this->assign('qualifications_arr',json_encode($qualifications_arr_temp));
			$this->assign('now_resume',$now_resume);
			$this->display();
		}
	}

	// 删除简历
	public function resume_del()
	{
		$this->check_login();
		$database_recruit_resume_detail = D('Recruit_resume_detail');
		$resume_id = intval($_POST['resume_id']);
		$now_resume = D('Recruit_resume')->get_one($resume_id);
		if (!$now_resume) {
			exit(json_encode(array('code'=>1,'msg'=>'简历不存在')));
		}
		if ($now_resume['uid']!=$this->user_session['uid']) {
			exit(json_encode(array('code'=>1,'msg'=>'您没有权限删除简历')));
		}

		$res = D('Recruit_resume')->where(array('resume_id'=>$resume_id))->delete();
		if ($res) {
			D('Recruit_resume_detail')->where(array('resume_id'=>$resume_id))->delete();
			exit(json_encode(array('code'=>0,'msg'=>'删除成功')));
		}else{
			exit(json_encode(array('code'=>1,'msg'=>'删除失败')));
		}
		
	}

	// 添加简历详情教育经历工作经历
	public function ajax_add_resume_detail()
	{
		$_POST['start_time'] = strtotime($_POST['start_time']);
		$_POST['end_time'] = strtotime($_POST['end_time']);
		$_POST['add_time'] = time();
		$_POST['uid'] = $this->user_session['uid'];
		if (!$_POST['name']) {
			if ($_POST['type'] == 1) {
				$this->returnCode('74000006');
			}else{
				$this->returnCode('74000008');				
			}
		}
		if (!$_POST['desc']) {
			if ($_POST['type'] == 1) {
				$this->returnCode('74000007');
			}else{
				$this->returnCode('74000009');				
			}
		}
		if (M('Recruit_resume_detail')->add($_POST)) {
			if ($_POST['type']==1) {
				$data['has_work'] = 1;
			}else{
				$data['has_education'] = 1;
			}
			M('Recruit_resume')->where(array('resume_id'=>$_POST['resume_id']))->data($data)->save();
			$this->returnCode(0,'添加成功');
		}else{
			$this->returnCode(1,array(),'添加失败');
		}
	}	

	// 我的简历列表
	public function my_resume_list()
	{
		$this->check_login();
		$database_recruit_resume = D('Recruit_resume');
		$resume_list = $database_recruit_resume->where(array('uid'=>$this->user_session['uid']))->select();
		$this->assign('resume_list',$resume_list);
		$this->display();
	}	

	public function ajax_my_resume_list()
	{
		$pageSize = $_GET['pageSize'] ? $_GET['pageSize'] : 10;
		$where['uid'] = $this->user_session['uid'];
		$list = D('Recruit_resume')->get_limit_list_page($where,$pageSize,1);
		foreach ($list['list'] as &$value) {
            $value['add_time_format'] = date('Y-m-d H:i:s',$value['add_time']);
            $value['update_time_format'] = date('Y-m-d H:i:s',$value['update_time']);
		}
		$list['page'] = $_GET['page'];
		$this->returnCode(0,$list);
	}

	// 简历投递列表
	public function my_resume_send()
	{
		$this->check_login();
		$this->display();
	}

	public function ajax_my_resume_send()
	{
		$pageSize = $_GET['pageSize'] ? $_GET['pageSize'] : 10;
		$where['uid'] = $this->user_session['uid'];
		$list = D('Recruit_resume_send')->get_limit_list_page($where,$pageSize,1);
		$this->returnCode(0,$list);
	}

	// 企业邀约列表
	public function company_invitation()
	{
		$pageSize = $_GET['pageSize'] ? $_GET['pageSize'] : 10;
		$where['company_id'] = $this->now_company['company_id'];
		$list = D('Recruit_resume_send')->get_limit_list_page($where,$pageSize,1);
		$this->display();
	}

	// 简历被邀约列表
	public function my_resume_invitation()
	{
		$pageSize = $_GET['pageSize'] ? $_GET['pageSize'] : 10;
		$where['company_id'] = $this->now_company['company_id'];
		$list = D('Recruit_resume_send')->get_limit_list_page($where,$pageSize,1);
		$this->display();
	}
	// 简历被邀约列表
	public function ajax_my_resume_invitation()
	{
		$database_recruit_resume_invitation = D('Recruit_resume_invitation');
		$pageSize = $_GET['pageSize'] ? $_GET['pageSize'] : 10;
        $page = intval($_GET['page']) ? intval($_GET['page']) : 1;
        $type = intval($_GET['type']) ? intval($_GET['type']) : 1;
        if ($type==1) {
			$where['to_uid'] = $this->user_session['uid'];
        }else{
			$where['i.uid'] = $this->user_session['uid'];
        }

		$field = 'i.*,u.nickname,c.company_name,p.position_cat_id,p.work_years,r.resume_name,r.birthday,r.qualifications,r.name as truename,r.work_years as user_work_years,r.sex,r.birthday,r.salary,r.avatar,r.phone';
        $order = ' `i`.`id` DESC';
        $join = 'as i LEFT JOIN '.C('DB_PREFIX').'user as u on `i`.`uid` = `u`.`uid` LEFT JOIN '.C('DB_PREFIX').'recruit_company as c on `c`.`company_id` = `i`.`company_id`  LEFT JOIN '.C('DB_PREFIX').'recruit_position as p on `p`.`position_id` = `i`.`position_id` LEFT JOIN '.C('DB_PREFIX').'recruit_resume as r on `r`.`resume_id` = `i`.`resume_id`';

        $count = $database_recruit_resume_invitation->join($join)->where($where)->count();
        $list = $database_recruit_resume_invitation->field($field)->join($join)->where($where)->order($order)->limit(($page-1)*$pageSize.','.$pageSize)->select();
        foreach ($list as &$value) {
        	$field = 'cat.name';
	        $join = 'as p LEFT JOIN '.C('DB_PREFIX').'recruit_position_category as c on `p`.`position_cat_id` = `c`.`id`';
	        $where = 'p.position_id='.$value['position_id'];
        	$position = M('Recruit_position')->join($join)->where($where)->find();
            $value['position_name'] = $position['name'] ? $position['name'] : '';
            
            $value['work_years'] = D('Recruit_position')->work_years_arr[$value['work_years']];
            $value['qualifications'] = D('Recruit_position')->qualifications_arr[$value['qualifications']];
            $value['user_work_years'] = D('Recruit_position')->work_years_arr[$value['user_work_years']];
            $value['salary'] = D('Recruit_position')->salary_arr[$value['salary']];
            $value['age'] = $value['birthday'] ? date('Y',time()) - date('Y',strtotime($value['birthday'])) : '';
            $value['avatar'] = file_domain().$value['avatar'] ;
            $value['add_time_format'] = date('Y-m-d H:i:s',$value['add_time']);
            $value['invitation_time_format'] = date('Y-m-d H:i:s',$value['invitation_time']);
        }

        $return['totalPage'] = ceil($count / $pageSize);
        $return['count'] = count($list);
        $return['page'] = $_GET['page'];
        $return['list'] = $list ? $list : array();
		$this->returnCode(0,$return);
	}
	// 邀约简历
	public function do_change_invitation_status(){
		$id = intval($_POST['id']);
		$data = array(
			'id'=>$id,
			'status'=>1
		);
        if($id = M('Recruit_resume_invitation')->data($data)->save()){
			$this->returnCode(0,'面试成功');
        }else{
			$this->returnCode('74000018');
        }
	}

	// 我的收藏
	public function my_collection()
	{
		$this->check_login();
		$where['uid'] = $this->user_session['uid'];
		$list = D('Recruit_collect')->get_limit_list_page($where);
		$this->display();
	}
	public function ajax_my_collection()
	{
		$pageSize = $_GET['pageSize'] ? $_GET['pageSize'] : 10;
		$where['uid'] = $this->user_session['uid'];
		$where['type'] = 'position';
		$list = D('Recruit_collect')->get_limit_list_page($where,$pageSize,1);
        foreach ($list['list'] as &$value) {
            $cat = M('Recruit_position_category')->where(array('id'=>$value['position_cat_id']))->find();
            $value['position_name'] = $cat['name'] ? $cat['name'] : '';
            $value['work_years'] = D('Recruit_position')->work_years_arr[$value['work_years']];
            $value['salary'] = D('Recruit_position')->salary_arr[$value['salary']];
            $value['add_time_format'] = date('Y-m-d H:i:s',$value['add_time']);
        }
		$this->returnCode(0,$list);
	}

	//我的职位列表
	public function my_position(){
		$this->check_login();
		$where['uid'] = $this->user_session['uid'];
		$list = D('Recruit_position')->get_limit_list_page($where);
		cookie('user_address_position_edit', 0);
		$this->assign('list',$list);
		$this->display();

	}
	public function ajax_my_position()
	{
		$pageSize = $_GET['pageSize'] ? $_GET['pageSize'] : 10;
		$where['uid'] = $this->user_session['uid'];
		$list = D('Recruit_position')->get_limit_list_page($where,$pageSize,1); 
		foreach ($list['list'] as &$value) {
            $cat = M('Recruit_position_category')->where(array('id'=>$value['position_cat_id']))->find();
            $value['position_name'] = $cat['name'] ? $cat['name'] : '';
            $value['work_years'] = D('Recruit_position')->work_years_arr[$value['work_years']] ? D('Recruit_position')->work_years_arr[$value['work_years']] : '';
            $value['salary'] = D('Recruit_position')->salary_arr[$value['salary']] ? D('Recruit_position')->salary_arr[$value['salary']] : '';
            $value['add_time_format'] = date('Y-m-d H:i:s',$value['add_time']);
        }
		$this->returnCode(0,$list);
	}
	// 职位置顶
	public function position_recommend()
	{
		$position_id = intval($_POST['position_id']);
		$is_recommend = intval($_POST['is_recommend']);

		if (!$this->user_session['uid']) {
			exit(json_encode(array('code'=>2,'msg'=>'请先登录')));
		}

		$now_position = D('Recruit_position')->where(array('position_id'=>$position_id))->find();
		if (!$now_position) {
			exit(json_encode(array('code'=>1,'msg'=>'职位不存在')));
		}
		if ($now_position['uid']!=$this->user_session['uid']) {
			exit(json_encode(array('code'=>1,'msg'=>'您没有权限操作此职位')));
		}
		
		$res = D('Recruit_position')->where(array('position_id'=>$position_id))->save(array('is_recommend'=>$is_recommend));
		if ($res) {
			exit(json_encode(array('code'=>0,'msg'=>'操作成功')));
		}else{
			exit(json_encode(array('code'=>1,'msg'=>'操作失败')));
		}
	}
	

	// 收到的简历
	public function my_resume_receive()
	{
		$this->check_login();
		$this->display();
	}
	public function ajax_my_resume_receive()
	{
		$pageSize = $_GET['pageSize'] ? $_GET['pageSize'] : 10;
		$where['company_id'] = $this->now_company['company_id'];
		$list = D('Recruit_resume_send')->get_limit_list_page($where,$pageSize,1);
		foreach ($list['list'] as &$value) {
        	$field = 'cat.name';
	        $join = 'as p LEFT JOIN '.C('DB_PREFIX').'recruit_position_category as c on `p`.`position_cat_id` = `c`.`id`';
	        $where = 'p.position_id='.$value['position_id'];
        	$position = M('Recruit_position')->join($join)->where($where)->find();
        	$value['position_name'] = $position['name'] ? $position['name'] : '';
        	$value['can_view'] = 0;
            if ($this->user_session['uid']==$value['r_uid']) {
            	$value['can_view'] = 1;
            }else{
	            $resume_order = M('Recruit_resume_order')->where(array('uid'=>$this->user_session['uid'],'resume_id'=>$value['resume_id'],'paid'=>1))->find();
				if ($resume_order) {
					// 已购买
	            	$value['can_view'] = 1;
				}
			}
        }
		$this->returnCode(0,$list);
	}

	// 邀约简历
	public function do_resume_invitation(){
		$position_id = intval($_POST['position_id']);
		$resume_id = intval($_POST['resume_id']);
		$invitation_time = strtotime($_POST['invitation_time']);
		
		$database_recruit_position = D('Recruit_position');
		$position_detail = $database_recruit_position->get_one($position_id);

		if (!$this->now_company) {
			exit(json_encode(array('error'=>1,'msg'=>'请先申请成为企业用户')));
		}

		if ($this->now_company['status'] != 1) {
			exit(json_encode(array('error'=>1,'msg'=>'您的企业未通过审核')));
		}

		$now_resume = D('Recruit_resume')->get_one($resume_id);
		$data = array(
			'resume_id'=>$resume_id,
			'position_id'=>$position_id,
			'company_id'=>$position_detail['company_id'],
			'invitation_time'=>$invitation_time,
			'uid'=>$this->user_session['uid'],
			'to_uid'=>$now_resume['uid'],
			'remark'=>$_POST['remark'],
			'add_time'=>time(),
		);

		// 扣除积分
		if (C('config.recruit_invitation_resume_need_score')>0) {
			if ($this->now_company['invitation_times'] > 0 && $this->now_company['energy_expiration_time'] >= time()) {
				$data['type'] = 2;
				$res = D('Recruit_company')->where(array('company_id'=>$this->now_company['company_id']))->setDec('invitation_times',1);
			}else{
				$now_user = D('User')->get_user($this->user_session['uid']);
				if ($now_user['score_count']<C('config.recruit_invitation_resume_need_score')) {
					exit(json_encode(array('error'=>2,'msg'=>'您的积分不足，请充值')));
				}

				// 减少积分
				$res = D('User')->use_score($now_user['uid'],C('config.recruit_invitation_resume_need_score'),'邀约简历扣除积分');
				if ($res['error_code']) {
					exit(json_encode(array('error'=>1,'msg'=>$res['msg'])));
				}
				$data['type'] = 1;
				$data['score'] = C('config.recruit_invitation_resume_need_score');
			}
		}
	
        if($id = M('Recruit_resume_invitation')->data($data)->add()){
        	// 邀约数加1
        	M('Recruit_resume')->data(array('resume_id'=>$resume_id))->setInc('invitation_times',1);
			exit(json_encode(array('error'=>0,'msg'=>'邀约成功')));
        }else{
			exit(json_encode(array('error'=>1,'msg'=>'邀约失败')));
        }
	}

	// 投递简历
	public function do_resume_send(){
		$position_id = intval($_POST['position_id']);
		$resume_id = intval($_POST['resume_id']);
		
		$database_recruit_position = D('Recruit_position');
		$position_detail = $database_recruit_position->get_one($position_id);

		// $resume_send_info = M('Recruit_resume_send')->where(array('resume_id'=>$resume_id,'position_id'=>$position_id,'uid'=>$this->user_session['uid']))->find();
		// if ($resume_send_info) {
		// 	$this->returnCode(1,array(),'你已投递过该职位');
		// }

		$now_user = D('User')->get_user($this->user_session['uid']);
		if(!$now_user){
			$this->returnCode('73000008');
		}

		if(!$now_user['phone']){
			$this->returnCode('20120035');
		}

		// 扣除积分
		if (C('config.recruit_send_resume_need_score')>0) {
			if ($now_user['score_count']<C('config.recruit_send_resume_need_score')) {
				// 积分不足
				$this->returnCode('20140081');
			}

			// 减少积分
			$res = D('User')->use_score($now_user['uid'],C('config.recruit_send_resume_need_score'),'投递简历扣除积分');
			if ($res['error_code']) {
				$this->returnCode(1,array(),$res['msg']);
			}
		}
		
		$data = array(
			'resume_id'=>$resume_id,
			'position_id'=>$position_id,
			'company_id'=>$position_detail['company_id'],
			'uid'=>$this->user_session['uid'],
			'add_time'=>time(),
		);
        if($collect_id = M('Recruit_resume_send')->data($data)->add()){
        	// 投递数加1
        	M('Recruit_resume')->where(array('resume_id'=>$resume_id))->setInc('send_times',1);
			$this->returnCode(0,'投递成功');
        }else{
			$this->returnCode(1,array(),'投递失败');
        }
	}

	// 企业详情
	public function company_detail()
	{
		$this->check_login();
		cookie('user_address_company', 0);
		$this->display();
	}

	// 申请企业
	public function company_apply()
	{
		$this->check_login();
		$database_recruit_position = D('Recruit_company');
		if (IS_POST) {
			$image = D('Image')->handle($this->user_session['uid'], 'recruit', 2, array('size' => 50));
			if(!$image['error']){
				$_POST['license_pic'] = $image['url']['license_pic'];
				$_POST['company_pic'] = $image['url']['company_pic'];
			} else {
				exit(json_encode(array('code'=>1,'msg'=>$image['message'])));
			}

			if (!$_POST['license_pic']) {
				exit(json_encode(array('code'=>1,'msg'=>'请上传营业执照')));
			}

			if (!$_POST['company_pic']) {
				exit(json_encode(array('code'=>1,'msg'=>'请上传店铺环境照片')));
			}

			$_POST['license_pic'] = json_encode(implode(';', $_POST['license_pic']));
			$_POST['company_pic'] = json_encode(implode(';', $_POST['company_pic']));

			// TODO 验证 城市
			$_POST['uid'] = $this->user_session['uid'];
			$_POST['add_time'] = time();

			if ($this->now_company) {
				$_POST['status'] = 0;
				$_POST['company_id'] = $this->now_company['company_id'];
				$res = $database_recruit_position->save($_POST);
			}else{
				$res = $database_recruit_position->add($_POST);
			}
			if ($res) {
				cookie('user_address_company', 0);
				exit(json_encode(array('code'=>0,'msg'=>'提交成功','url'=>U('my'))));
			}else{
				exit(json_encode(array('code'=>1,'msg'=>'提交失败','url'=>'')));
			}
		}else{

    		$this->assign('license_pic',json_encode($this->now_company['license_pic']));
    		$this->assign('company_pic',json_encode($this->now_company['company_pic']));
			$this->display();
		}
	}

	// 购买
	public function score_package()
	{
		$this->check_login();

		$where['status'] = 1;
		$where['is_del'] = 0;
		$list = D('Recruit_score_package')->get_limit_list_page($where);
		$this->assign('list',$list);
		$this->display();
	}
	public function ajax_get_score_package()
	{
		$where['status'] = 1;
		$where['is_del'] = 0;
		$pageSize = $_GET['pageSize'] ? $_GET['pageSize'] : 10;
		$list = D('Recruit_score_package')->get_limit_list_page($where,$pageSize,1);
		$this->returnCode(0,$list);
	}

	/**
     * 购买积分
     * @return [type] [description]
     */
	public function score_go_pay(){
       // 判断用户是否登录
        $this->check_login();
        $data = array();
       
        // 处理添加的数据
        $database_recruit_score_package = M('Recruit_score_package');
        $database_recruit_score_package_order= M('Recruit_score_package_order');

        //活动id
        if (empty($_GET['package_id'])) {
            $this->error_tips('参数传递错误');
        }
        $data['package_id'] = $_GET['package_id'];

        //查询活动
        $score_package = $database_recruit_score_package->where(array('package_id'=>$_GET['package_id'],'status'=>1,'is_del'=>0))->find();
        if (!$score_package) {
        	$this->error_tips('积分套餐不存在');
        }

        $order_data = array(
            'package_id'   =>  $_GET['package_id'],
            'uid'           =>  $this->user_session['uid'],
            'price'         =>  $score_package['price'],
            'score'         =>  $score_package['score'],
            'pay_type'      =>  '',
            'paid'        =>  0,
            'add_time'      =>  time()
        );

        $order_id = $database_recruit_score_package_order->data($order_data)->add();
        if($order_id){
            $pay_order_param = array(
                'business_type' => 'recruit_score_package',
                'business_id' => $order_id,
                'order_name' => '积分套餐【'.$score_package['name'].'】',
                'uid' => $this->user_session['uid'],
                'total_money' => $score_package['price'],
            );
            $plat_order_result = D('Plat_order')->add_order($pay_order_param);
            if ($plat_order_result['error_code']) {
                $this->error_tips($plat_order_result['error_msg']);
            } else {
    			redirect(U('Pay/check', array('order_id' => $plat_order_result['order_id'], 'type' => 'plat')));
    			exit;
            }
        }else{
            $this->error_tips('订单生成失败');
        }
    }

	// 排名购买详情
	public function ranking_detail()
	{
		$this->check_login();
		if (!$this->now_company) {
            $this->error_tips('请先申请成为企业用户');
		}
		if ($this->now_company['status'] != 1) {
            $this->error_tips('您的企业未通过审核');
		}
		$where['status'] = 1;
		$where['is_del'] = 0;
		// $where['province'] = $this->now_company['province'];
		$list = D('Recruit_ranking')->get_limit_list_page($condition);
		$now_ranking = array();
		$all_city = M('Area')->select();
		//当前可选城市
		$city_ids = array();
		$area_ids = array();
		foreach ($list['list'] as &$value) {
			// 指定区域
			$bind_area = M('Recruit_ranking_bind_area')->where(array('package_id'=>$value['package_id'],'province'=>$this->now_company['province']))->select();
			$value['bind_area'] = $bind_area;
			foreach ($bind_area as $_area) {
				if (!in_array($_area['city'], $city_ids)) {
					$city_ids[] = intval($_area['city']);
				}
				if ($_area['city'] ==  $this->now_company['city'] && !in_array($_area['area'], $area_ids)) {
					$area_ids[] = intval($_area['area']);
				}
				if ($_area['city'] ==  $this->now_company['city'] && $_area['area'] ==  $this->now_company['area']) {
					
					if ($_area['company_id']&&$_area['end_time']>=time()) {
						if ($_area['company_id'] == $this->now_company['company_id']) {
							$value['is_owner'] = 1;
						}else{
							$value['is_buy'] = 1;
						}
					}
					$now_ranking[] = $value;
				}
			}
		}
		$city_list = M('Area')->field('area_id,area_name')->where(array('area_id'=>array('in',$city_ids)))->select();
		$area_list = M('Area')->field('area_id,area_name')->where(array('area_id'=>array('in',$area_ids)))->select();
		// var_dump($city_arr);;
		foreach ($city_list as $key => $value) {
			$_tmp['title'] = $value['area_name'];
			$_tmp['value'] = $value['area_id'];
			$city_arr[] = $_tmp;
		}
		foreach ($area_list as $key => $value) {
			$_tmp['title'] = $value['area_name'];
			$_tmp['value'] = $value['area_id'];
			$area_arr[] = $_tmp;
		}
		$this->assign('city_arr',json_encode($city_arr));
		$this->assign('area_arr',json_encode($area_arr));
		// 当前企业所在城市
		$city = M('Area')->where(array('area_id'=>$this->now_company['city']))->find();
		$area = M('Area')->where(array('area_id'=>$this->now_company['area']))->find();
		$this->assign('city',$city['area_name']);
		$this->assign('area',$area['area_name']);

		$this->assign('now_ranking',$now_ranking);

		// 当前城市下所有区域排名
		$condition_table = array(C('DB_PREFIX').'recruit_ranking'=>'r',C('DB_PREFIX').'recruit_ranking_bind_area'=>'b',C('DB_PREFIX').'area'=>'a');
		$condition_field = 'r.*,b.*,a.area_name';
		$condition_where = 'r.status=1 AND r.is_del=0 AND b.city='.$this->now_company['city'].' AND r.package_id=b.package_id AND b.area=a.area_id';
		$ranking_list = D('')->table($condition_table)->where($condition_where)->select();
		$ranking_list_tmp = array();
		foreach ($ranking_list as $_v) {
			if ($_v['company_id'] && $_v['end_time'] >= time()) {
				if ($_v['company_id'] == $this->now_company['company_id']) {
					$_v['is_owner'] = 1;
				}else{
					$_v['is_buy'] = 1;
				}
			}
			$ranking_list_tmp[$_v['area']][] = $_v;
		}

		$this->assign('ranking_list',json_encode($ranking_list_tmp));
		// var_dump($now_ranking);
		$this->display();
	}

	// 通过城市获取区域
	public function ajax_get_ranking_area()
	{
		$condition_table = array(C('DB_PREFIX').'recruit_ranking'=>'r',C('DB_PREFIX').'recruit_ranking_bind_area'=>'b',C('DB_PREFIX').'area'=>'a');
		$condition_where['r.pa'] = '1';
		$condition_where['r.status'] = '1';
		$condition_where['r.is_del'] = '0';
		$condition_where['b.city'] = $_POST['city_id'];
		$condition_where['b.company_id'] = 0;

		$condition_field = 'r.*,b.*,a.area_name';
		$condition_where = 'r.status=1 AND r.is_del=0 AND b.city='.$_POST['city_id'].' AND r.package_id=b.package_id AND b.area=a.area_id';

		$ranking_list = D('')->table($condition_table)->where($condition_where)->select();
		$ranking_list_tmp = array();
		foreach ($ranking_list as $value) {
			if ($value['company_id'] && $value['end_time'] >= time()) {
				if ($value['company_id'] == $this->now_company['company_id']) {
					$value['is_owner'] = 1;
				}else{
					$value['is_buy'] = 1;
				}
			}
			$ranking_list_tmp[$value['area']][] = $value;
		}

		// 区域数组
		foreach ($ranking_list_tmp as $key => $_v) {
			$_tmp['title'] = $_v[0]['area_name'];
			$_tmp['value'] = $_v[0]['area'];
			$area_arr[] = $_tmp;
		}

		$arr['ranking_list'] = $ranking_list_tmp;
		$arr['area_list'] = $area_arr;
		$this->returnCode(0,$arr);
	}

	// 排名购买订单列表
	public function ranking_order_list()
	{
		$this->check_login();
		$where['uid'] = $this->user_session['uid'];
		$list = D('Recruit_ranking_order')->get_limit_list_page($where);
		$this->assign('list',$list);
		$this->display();
	}

	public function ajax_get_ranking_order_list()
	{
		$pageSize = $_GET['pageSize'] ? $_GET['pageSize'] : 10;
		$where['uid'] = $this->user_session['uid'];
		$list = D('Recruit_ranking_order')->get_limit_list_page($where,$pageSize,1);
		foreach ($list['list'] as &$value) {
			$value['pay_time'] = date('Y-m-d H:i:s',$value['pay_time']);
		}
		$this->returnCode(0,$list);
	}

	/**
     * 购买排名
     */
	public function ranking_go_pay(){
       // 判断用户是否登录
        if (!$this->user_session['uid']) {
            $this->returnCode('73000008');
        }

        //查询企业
        if (!$this->now_company) {
        	// 您还未申请成为企业账号，不能购买排名
        	$this->returnCode('74000012');
        }

        if ($this->now_company['status'] == 0) {
        	// 您的企业账号还未通过审核，不能购买排名
        	$this->returnCode('74000013');
        }

        if ($this->now_company['status'] == 2) {
        	// 您的企业账号未通过审核，不能购买排名
        	$this->returnCode('74000015');
        }

        $data = array();
        $database_recruit_ranking = M('Recruit_ranking');
        $database_recruit_ranking_bind_area = M('Recruit_ranking_bind_area');
        $database_recruit_ranking_order= M('Recruit_ranking_order');

        //排名id
        if (empty($_POST['package_id']) || empty($_POST['area_id'])) {
            $this->returnCode('74000001');
        }
        $data['package_id'] = $_POST['package_id'];

        //查询排名
        $now_ranking = $database_recruit_ranking->where(array('package_id'=>$_POST['package_id'],'status'=>1,'is_del'=>0))->find();
        if (!$now_ranking) {
        	// 排名不存在
            $this->returnCode('74000010');
        }

        $bind_area = $database_recruit_ranking_bind_area->where(array('package_id'=>$_POST['package_id'],'area'=>$_POST['area_id']))->find();
        if (!$bind_area) {
            $this->returnCode('74000011');
        }

        if ($bind_area['company_id']) { 
        	// 过期可以再次购买
        	if ($bind_area['end_time']>=time()) {
        		if ($bind_area['company_id']==$this->now_company['company_id']) {
	        		// 您已购买过此排名，不能再次购买
	            	$this->returnCode('74000016');
	        	}else{
	        		// 此排名已被购买过，您不能购买
	            	$this->returnCode('74000017');
	        	}
        	}
        }

        $order_data = array(
            'package_id'   =>  $_POST['package_id'],
            'bind_id'   =>  $bind_area['bind_id'],
            'uid'           =>  $this->user_session['uid'],
            'price'         =>  $now_ranking['price'],
            'bind_ranking_sort'  =>  $now_ranking['ranking_sort'],
            'active_time'         =>  $now_ranking['active_time'],
            'order_name' => '排名【'.$now_ranking['name'].'】',
            'pay_type'      =>  '',
            'paid'        =>  0,
            'order_time'      =>  time(),
            'last_time'      =>  time()
        );
        $order_id = $database_recruit_ranking_order->data($order_data)->add();

        if($order_id){
            $pay_order_param = array(
                'business_type' => 'recruit_ranking',
                'business_id' => $order_id,
                'order_name' => '排名【'.$now_ranking['name'].'】',
                'uid' => $this->user_session['uid'],
                'total_money' => $now_ranking['price'],
            );
            $plat_order_result = D('Plat_order')->add_order($pay_order_param);
            if ($plat_order_result['error_code']) {
        		$this->returnCode(1, $plat_order_result['error_msg']);
            } else {
        		$this->returnCode(0, U('Pay/check', array('order_id' => $plat_order_result['order_id'], 'type' => 'plat')));
            }
        }else{
        	// 订单生成失败
            $this->returnCode('20090170');
        }
    }

	// 能量包
	public function energy_package()
	{
		$this->check_login();
		$where['status'] = 1;
		$where['is_del'] = 0;
		$list = D('Recruit_energy_package')->get_limit_list_page($where,1000,1);
		$this->assign('list',$list);
		$this->display();
	}

	// 能量包购买订单列表
	public function energy_package_order_list()
	{
		$this->check_login();
		$where['uid'] = $this->user_session['uid'];
		$list = D('Recruit_energy_package_order')->get_limit_list_page($where);
		$this->assign('list',$list);
		$this->display();
	}

	public function ajax_get_energy_package_order_list()
	{
		$pageSize = $_GET['pageSize'] ? $_GET['pageSize'] : 10;
		$where['uid'] = $this->user_session['uid'];
		$list = D('Recruit_energy_package_order')->get_limit_list_page($where,$pageSize,1);
		foreach ($list['list'] as &$value) {
			$value['pay_time'] = date('Y-m-d H:i:s',$value['pay_time']);
		}
		$this->returnCode(0,$list);
	}

	/**
     * 购买能量包
     */
	public function energy_go_pay(){
       // 判断用户是否登录
        if (!$this->user_session['uid']) {
            $this->returnCode('73000008');
        }

        $uid = $this->user_session['uid'];
        $data = array();
       
        // 处理添加的数据
        $database_recruit_energy_package = M('Recruit_energy_package');
        $database_recruit_energy_package_order= M('Recruit_energy_package_order');

        //能量包id
        if (empty($_POST['package_id'])) {
            $this->returnCode('74000001');
        }
        $data['package_id'] = $_POST['package_id'];

        //查询能量包
        $energy_package = $database_recruit_energy_package->where(array('package_id'=>$_POST['package_id'],'status'=>1,'is_del'=>0))->find();
        if (!$energy_package) {
        	// 能量包不存在
            $this->returnCode('74000002');
        }

        //查询企业
        $now_company = M('Recruit_company')->where(array('uid'=>$this->user_session['uid']))->find();
        if (!$now_company) {
        	// 您还未申请成为企业账号，不能购买能量包
        	$this->returnCode('74000003');
        }

        if ($now_company['status'] == 0) {
        	// 您的企业账号还未通过审核，不能购买能量包
        	$this->returnCode('74000004');
        }

        if ($now_company['status'] == 2) {
        	// 您的企业账号未通过审核，不能购买能量包
        	$this->returnCode('74000005');
        }

        $order_data = array(
            'package_id'   =>  $_POST['package_id'],
            'uid'           =>  $this->user_session['uid'],
            'price'         =>  $energy_package['price'],
            'couse_times'         =>  $energy_package['couse_times'],
            'resume_times'         =>  $energy_package['resume_times'],
            'invitation_times'         =>  $energy_package['invitation_times'],
            'active_time'         =>  $energy_package['active_time'],
            'order_name' => 'VIP年度会员【'.$energy_package['name'].'】',
            'pay_type'      =>  '',
            'paid'        =>  0,
            'order_time'      =>  time()
        );
        $order_id = $database_recruit_energy_package_order->data($order_data)->add();
        if($order_id){
            $pay_order_param = array(
                'business_type' => 'recruit_energy_package',
                'business_id' => $order_id,
                'order_name' => 'VIP年度会员【'.$energy_package['name'].'】',
                'uid' => $this->user_session['uid'],
                'total_money' => $energy_package['price'],
            );
            $plat_order_result = D('Plat_order')->add_order($pay_order_param);
            if ($plat_order_result['error_code']) {
        		$this->returnCode(1, $plat_order_result['error_msg']);
            } else {
        		$this->returnCode(0, U('Pay/check', array('order_id' => $plat_order_result['order_id'], 'type' => 'plat')));
            }
        }else{
        	// 订单生成失败
            $this->returnCode('20090170');
        }
    }

    /* 地图 */
	public function adres_map(){
		$params = $_GET;
		unset($params['adress_id']);
		$this->assign('params',$params);

		$database_area = D('Area');

		//得到当前城市的经纬度，用于地图初始化定位
		$now_city = $database_area->get_area_by_areaId($this->config['now_city']);
		if(!$now_city['area_lng']){
			$now_cityp['area_lng'] = '116.403847';
		}
		if(!$now_city['area_lat']){
			$now_cityp['area_lat'] = '39.915526';
		}
		$this->assign('now_city',$now_city);

		//得到所有城市并以城市首拼排序
		$all_city = S('all_city_address');
		if(empty($all_city)){
			$database_field = '`area_id`,`area_name`,`area_url`,`first_pinyin`,`is_hot`';
			$condition_all_city['area_type'] = 2;
			$all_city_old = $database_area->field($database_field)->where($condition_all_city)->order('`first_pinyin` ASC,`area_id` ASC')->select();
			foreach($all_city_old as $key=>$value){
				//首拼转成大写
				if(!empty($value['first_pinyin'])){
					$first_pinyin = strtoupper($value['first_pinyin']);
					$all_city[$first_pinyin][] = $value;
				}
			}
			S('all_city_address',$all_city);
		}
		$this->assign('all_city',$all_city);

		$this->display();
	}

	// 商学院
	public function release_video()
	{
		// echo phpinfo();
		$this->check_login();
		exec('/home/ffmpeg/bin/ffmpeg -version',$output,$return_val); 
		$this->assign('return_val',$return_val);
		if(IS_POST){
			$data['title'] = $_POST['title'];
			if (!$_POST['title']) {
				$this->error_tips('请输入标题');
			}
			if (!$_POST['content']) {
				$this->error_tips('请输入内容');
			}
			if (!$_POST['imgList1']) {
				$this->error_tips('请上传封面');
			}

			if ($_POST['type']==1) {
				if (!$_POST['imgList2']) {
					$this->error_tips('请上传详情图');
				}
			}else{
				if (!$_POST['videoUrl']) {
					$this->error_tips('请上传视频');
				}
			}

			$data['imgUrl'] = json_encode($_POST['imgList2']);
			$data['cover_img'] = $_POST['imgList1'][0];
			$data['content'] = $_POST['content'];
			$data['videoUrl'] = trim($_POST['videoUrl'],'.');
			$data['cat_id'] = intval($_POST['cat_id']);
			$data['type'] = intval($_POST['type']);
			$data['uid'] = $this->user_session['uid'];

			if ($_POST['course_id']) {
				$data['status'] = 0;
				$now_course = D('Recruit_course')->where(array('course_id'=>$_POST['course_id']))->find();
				if ($now_course['status']==1) {
					$this->error_tips('该视频不能编辑');
				}
				$res = D('Recruit_course')->where(array('course_id'=>$_POST['course_id']))->data($data)->save();
				if($res){
					$this->success_tips('编辑成功！', U('my_course'));
				}else{
					$this->error_tips('编辑失败，请重试！');
				}
			}else{
				$data['add_time'] = time();
				$res = D('Recruit_course')->data($data)->add();
				if($res){
					$this->success_tips('发布成功！', U('my_course'));
				}else{
					$this->error_tips('发布失败，请重试！');
				}
			}
			

		}else{
			if ($_GET['course_id']) {
				$now_course = D('Recruit_course')->where(array('course_id'=>$_GET['course_id']))->find();
				$now_course['imgUrl'] = json_decode($now_course['imgUrl']);
				$now_course['video_img'] = str_replace('_copy', '', $now_course['imgUrl']).'.png';
				$this->assign('now_course',$now_course);
			}
			$category = M('Recruit_course_category')->where(array('is_hide'=>0))->select();
			$this->assign('category',$category);
			$this->display();
		}
	}

	// 我的视频
	public function my_course()
	{
		$this->display();
	}
	public function ajax_my_course()
	{
		$pageSize = $_GET['pageSize'] ? $_GET['pageSize'] : 10;
		$where['uid'] = $this->user_session['uid'];
		$list = D('Recruit_course')->get_limit_list_page($where,$pageSize,1); 
		foreach ($list['list'] as &$value) {
			if ($value['imgUrl']) {
				$value['imgUrl'] = json_decode($value['imgUrl']);
				$value['image'] = file_domain().$value['imgUrl'][0];
			}
			if(M('Video_comment')->where(array('cl_VideoId'=>$value['course_id']))->count()){
				$value['comment'] = M('Video_comment')->where(array('cl_VideoId'=>$value['course_id']))->count();
			}else{
				$value['comment'] = 0;
			}
            $value['add_time_format'] = date('Y-m-d',$value['add_time']);
        }
		$this->returnCode(0,$list);
	}

	// 删除视频
	public function course_del()
	{
		if (!$this->user_session['uid']) {
			exit(json_encode(array('code'=>2,'msg'=>'请先登录')));
		}

		$database_recruit_course = D('Recruit_course');
		$course_id = intval($_POST['course_id']);
		$now_course = D('Recruit_course')->where(array('course_id'=>$course_id))->find();
		if (!$now_course) {
			exit(json_encode(array('code'=>1,'msg'=>'视频不存在')));
		}
		if ($now_course['uid']!=$this->user_session['uid']) {
			exit(json_encode(array('code'=>1,'msg'=>'您没有权限删除此视频')));
		}
		if ($now_course['status']==1) {
			exit(json_encode(array('code'=>1,'msg'=>'该视频不能删除')));
		}
		$res = D('Recruit_course')->where(array('course_id'=>$course_id))->delete();
		if ($res) {
			exit(json_encode(array('code'=>0,'msg'=>'删除成功')));
		}else{
			exit(json_encode(array('code'=>1,'msg'=>'删除失败')));
		}
		
	}

	// 商学院
	public function course_index()
	{
		//顶部广告
		$wap_index_top_adver = D('Adver')->get_adver_by_key('wap_course_index',5);
		$this->assign('wap_index_top_adver',$wap_index_top_adver);

		//导航条
		$wap_course_index = D('Slider')->get_slider_by_key('wap_course_index',0);

		//重磅推荐
		$course_index_adver = D('Adver')->get_adver_by_key('course_index',1);
		$this->assign('course_index_adver',$course_index_adver);

		$wap_index_slider = array();
		if(count($wap_course_index) >= 10 &&$this->config['wap_slider_number'] == 10){
			$wap_slider_number = $this->config['wap_slider_number'];
		}else{
			$wap_slider_number = 8;
		}
		foreach($wap_course_index as $key=>$value){
			$tmp_i = floor($key/$wap_slider_number);
			$wap_index_slider[$tmp_i][] = $value;
		}


		$this->assign('wap_index_slider',$wap_index_slider);

        $wap_slider_number = $this->config['wap_slider_number'];
		$this->assign('wap_slider_number',$wap_slider_number);
		$this->display();
	}

	//推荐课程列表
	public function course_recommend_list()
	{
		//顶部广告
		$wap_index_top_adver = D('Adver')->get_adver_by_key('wap_course_index',5);
		$this->assign('wap_index_top_adver',$wap_index_top_adver);
		$wap_slider_number = $this->config['wap_slider_number'];
		$this->assign('wap_slider_number',$wap_slider_number);
		$this->display();
	}
	//ajax更多推荐专区
	public function ajax_course_recommend_list()
	{
		$pageSize = $_GET['pageSize'] ? $_GET['pageSize'] : 10;
		$where['status'] = 1;
		$list = D('Recruit_course')->get_recommend_limit_list_page($where,$pageSize,1);
		foreach ($list['list'] as &$value) {
			if ($value['imgUrl']) {
				$value['imgUrl'] = json_decode($value['imgUrl']);
				$value['image'] = file_domain().$value['imgUrl'][0];
			}
			$value['add_time_format'] = date('Y-m-d',$value['add_time']);
		}
		$this->returnCode(0,$list);
	}


	//课程分类列表
	public function course_category_list()
	{
		//顶部广告
		$wap_index_top_adver = D('Adver')->get_adver_by_key('wap_course_index',5);
		$this->assign('wap_index_top_adver',$wap_index_top_adver);
		$wap_slider_number = $this->config['wap_slider_number'];
		$this->assign('wap_slider_number',$wap_slider_number);

		if ($_GET['cat_id']) {
			$category = D('Recruit_course_category')->where(array('id'=>$_GET['cat_id']))->find();
		}
		$this->assign('cat_name',$category['name']?$category['name']:'全部');
		$this->display();
	}

	public function ajax_course_category_list()
	{
		$pageSize = $_GET['pageSize'] ? $_GET['pageSize'] : 10;
		$where['status'] = 1;
		$list = D('Recruit_course')->get_recommend_free_detail_limit_list_page($where,$pageSize,1);
		foreach ($list['list'] as &$value) {
			if ($value['imgUrl']) {
				$value['imgUrl'] = json_decode($value['imgUrl']);
				$value['image'] = file_domain().$value['imgUrl'][0];
			}
			$value['add_time_format'] = date('Y-m-d',$value['add_time']);
		}
		$this->returnCode(0,$list);
	}

	//免费专区更多
	public function course_free_list()
	{
		//顶部广告
		$wap_index_top_adver = D('Adver')->get_adver_by_key('wap_course_index',5);
		$this->assign('wap_index_top_adver',$wap_index_top_adver);
		$wap_slider_number = $this->config['wap_slider_number'];
		$this->assign('wap_slider_number',$wap_slider_number);
		$this->display();
	}
	//ajax免费专区更多
	public function ajax_course_free_detail_list()
	{
		$pageSize = $_GET['pageSize'] ? $_GET['pageSize'] : 10;
		$where['status'] = 1;
		$list = D('Recruit_course')->get_recommend_free_detail_limit_list_page($where,$pageSize,1);
		foreach ($list['list'] as &$value) {
			if ($value['imgUrl']) {
				$value['imgUrl'] = json_decode($value['imgUrl']);
				$value['image'] = file_domain().$value['imgUrl'][0];
			}
			$value['add_time_format'] = date('Y-m-d',$value['add_time']);
		}
		$this->returnCode(0,$list);
	}


	//精品课程列表
	public function course_boutique_list()
	{
		//顶部广告
		$wap_index_top_adver = D('Adver')->get_adver_by_key('wap_course_index',5);
		$this->assign('wap_index_top_adver',$wap_index_top_adver);
		$wap_slider_number = $this->config['wap_slider_number'];
		$this->assign('wap_slider_number',$wap_slider_number);
		$this->display();
	}
	//ajax更多精品专区
	public function ajax_course_boutique_list()
	{
		$pageSize = $_GET['pageSize'] ? $_GET['pageSize'] : 10;
		$where['status'] = 1;
		$list = D('Recruit_course')->get_boutique_limit_list_page($where,$pageSize,1);
		foreach ($list['list'] as &$value) {
			if ($value['imgUrl']) {
				$value['imgUrl'] = json_decode($value['imgUrl']);
				$value['image'] = file_domain().$value['imgUrl'][0];
			}
			if(M('Video_comment')->where(array('cl_VideoId'=>$value['course_id']))->count()){
				$value['comment'] = M('Video_comment')->where(array('cl_VideoId'=>$value['course_id']))->count();
			}else{
				$value['comment'] = 0;
			}
			$value['add_time_format'] = date('Y-m-d',$value['add_time']);
		}
		$this->returnCode(0,$list);
	}

	public function ajax_course_index_list()
	{
		$pageSize = $_GET['pageSize'] ? $_GET['pageSize'] : 10;

		// 分类
		$cat_id = intval($_GET['cat_id']);
		if ($cat_id) {
			$where['cat_id'] = $cat_id;
		}

		$where['status'] = 1;
		$list = D('Recruit_course')->get_limit_list_page($where,$pageSize,1); 
		foreach ($list['list'] as &$value) {
            if ($value['imgUrl']) {
        		$value['imgUrl'] = json_decode($value['imgUrl']);
            	$value['image'] = file_domain().$value['imgUrl'][0];
            }
			$value['comment'] = M('Video_comment')->where(array('cl_VideoId'=>$value['course_id']))->count();
			
            $value['add_time_format'] = date('Y-m-d',$value['add_time']);
        }
		$this->returnCode(0,$list);
	}

	public function ajax_course_free_list()
	{
		$pageSize = $_GET['pageSize'] ? $_GET['pageSize'] : 10;
		$where['status'] = 1;
		$list = D('Recruit_course')->get_recommend_free_limit_list_page($where,$pageSize,1);
		foreach ($list['list'] as &$value) {
			if ($value['imgUrl']) {
				$value['imgUrl'] = json_decode($value['imgUrl']);
				$value['image'] = file_domain().$value['imgUrl'][0];
			}
			if(M('Video_comment')->where(array('cl_VideoId'=>$value['course_id']))->count()){
				$value['comment'] = M('Video_comment')->where(array('cl_VideoId'=>$value['course_id']))->count();
			}else{
				$value['comment'] = 0;
			}


			$value['add_time_format'] = date('Y-m-d',$value['add_time']);
		}
		$this->returnCode(0,$list);
	}

	public function course_detail()
	{
		$course_id = intval($_GET['course_id']);
		$database_recruit_course = D('Recruit_course');
		$now_course = $database_recruit_course->where(array('course_id'=>$course_id))->find();
		if (!$now_course) {
			$this->error_tips('视频不存在');
		}
		if ($now_course['imgUrl']) {
			$now_course['imgUrl'] = json_decode($now_course['imgUrl']);
			$now_course['image'] = file_domain().$now_course['imgUrl'][0];
		}
		//视频分类下的视频
		$commendList = $database_recruit_course->where(array('cat_id'=>$now_course['cat_id']))->limit(6)->select();
		foreach($commendList as &$item){
           $item['imgUrl'] = json_decode($item['imgUrl']);
           $item['image'] = file_domain().$item['imgUrl'][0];


		}
	//	var_dump($commendList);
		$this->assign('commendList',$commendList);
		//评论=>郭先森
		$field = 'c.cl_CommentContent,c.cl_CommentTime,u.nickname,u.avatar,u.uid';
		$commList = M('Video_comment')->field($field)->join('as c LEFT JOIN '.C('DB_PREFIX').'user as u ON c.cl_UserId = u.uid')->where(array('cl_VideoId'=>$course_id,'cl_Status'=>1))->select();
		$commListCount = M('Video_comment')->field($field)->join('as c LEFT JOIN '.C('DB_PREFIX').'user as u ON c.cl_UserId = u.uid')->where(array('cl_VideoId'=>$course_id,'cl_Status'=>1))->count();
		$this->assign('commList',$commList);
		$this->assign('commListCount',$commListCount);
		$can_view = 0;
		if ($now_course['uid'] == $this->user_session['uid']) {
			$can_view = 1;
		}else{
			if ($now_course['status'] != 1) {
				$this->error_tips('视频不存在');
			}
		}
		// 免费
		if ($now_course['score'] > 0) {
			// 是否已付费
			if ($now_order = D('Recruit_course_order')->where(array('course_id'=>$course_id,'uid'=>$this->user_session['uid'],'paid'=>1))->find()) {
				$can_view = 1;
			}else{
				// 是否购买了套餐
				if ($this->now_company['status'] == 1&&$this->now_company['couse_times'] > 0&&$this->now_company['energy_expiration_time'] > time()) {
					$now_course['couse_times'] = $this->now_company['couse_times'];
				}
			}
		}else{
			$can_view = 1;
		}

		// 是否收藏
        $collect_info = M('Recruit_collect')->where(array('cid'=>$course_id,'type'=>'course','uid'=>$this->user_session['uid']))->find();
		$this->assign('collect_info',$collect_info);

		$category = M('Recruit_course_category')->where(array('id'=>$now_course['cat_id']))->find();
		$now_course['cat_name'] = $category['name'] ? $category['name'] : '';

		// 浏览量
		$database_recruit_course->where(array('course_id'=>$course_id))->setInc('view_times',1);
		$this->assign('course_id',$course_id);
		$this->assign('can_view',$can_view);
		$this->assign('now_course',$now_course);
		$this->display();
	}

	//郭先森  //发表评论
	public function ajax_add_commend(){
		if (!$this->user_session['uid']) {
			$this->error_tips('请先进行登录！',U('Login/index',array('referer'=>urlencode($this->config['site_url'].'/wap.php?c=Recruit&a=course_index'))));
//			exit(json_encode(array('error'=>1,'msg'=>'未登录')));
		}
        $data['cl_VideoId'] = $_POST['cl_VideoId'];
		$data['cl_CommentContent'] = $_POST['cl_CommentContent'];
        $data['cl_UserId'] = $this->user_session['uid'];
        $data['cl_CommentTime'] = time();
        $data['cl_Status'] = 1;
        $data['cl_CreateTime'] = time();
		$model = M('Video_comment')->add($data);

		if($model){
			exit(json_encode(array('error'=>0,'msg'=>'发布成功')));
		}else{
			exit(json_encode(array('error'=>1,'msg'=>'发布失败')));
		}
	}

	// 查看视频扣除积分或VIP次数
	public function ajax_pay_course_view(){
		$course_id = intval($_POST['course_id']);
		if (!$this->user_session['uid']) {
			exit(json_encode(array('error'=>2,'msg'=>'未登录')));
		}
		$couse_times = intval($_POST['couse_times']);
		$database_recruit_course = D('Recruit_course');
		$now_course = $database_recruit_course->where(array('course_id'=>$course_id))->find();
		if (!$now_course) {
			exit(json_encode(array('error'=>1,'msg'=>'视频不存在')));
		}

		if ($now_course['uid'] == $this->user_session['uid']) {
			exit(json_encode(array('error'=>0,'msg'=>'可以查看')));
		}else{
			if ($now_course['status'] != 1) {
				exit(json_encode(array('error'=>1,'msg'=>'视频不存在')));
			}
		}
		// 免费
		if ($now_course['score'] > 0) {
			// 是否已付费
			$now_order = D('Recruit_course_order')->where(array('course_id'=>$course_id,'uid'=>$this->user_session['uid'],'paid'=>1))->find();
			if ($now_order) {
				exit(json_encode(array('error'=>0,'msg'=>'可以查看')));
			}else{
				// 订单详情
				$order_data = array(
					'course_id'   =>  $course_id,
					'score'   =>  $now_course['score'],
					'uid'           =>  $this->user_session['uid'],
					'paid'        =>  1,
					'add_time'      =>  time(),
					'pay_time'      =>  time()
				);

				$get_money = 0;
				if(C('config.user_score_recharge_percent')>0){
					//积分兑换成余额
					$get_money = round(1/C('config.user_score_recharge_percent')*$now_course['score'],2);
					$order_data['get_money'] = $get_money;
				}else{
					$order_data['get_score'] = $now_course['score'];
				}

				$is_success = false;
				if ($this->now_company&&$couse_times) {
					if ($this->now_company['status'] != 1) {
						exit(json_encode(array('error'=>1,'msg'=>'您的企业未通过审核')));
					}

					if ($this->now_company['energy_expiration_time'] < time()) {
						exit(json_encode(array('error'=>1,'msg'=>'您的vip已到期')));
					}

					if ($this->now_company['couse_times'] <= 0) {
						exit(json_encode(array('error'=>1,'msg'=>'您的视频免费查看次数已用完')));
					}

					$order_data['pay_type'] = 2;
					$res = D('Recruit_company')->where(array('company_id'=>$this->now_company['company_id']))->setDec('couse_times',1);
					if ($res) {
						$is_success = true;
					}
				}else{
					// 个人
					$now_user = D('User')->get_user($this->user_session['uid']);
					if ($now_user['score_count']<$now_course['score']) {
						exit(json_encode(array('error'=>3,'msg'=>'积分不足,请充值')));
					}
					$order_data['pay_type'] = 1;
					// 减少积分
					$res = D('User')->use_score($this->user_session['uid'],$now_course['score'],'购买视频，减少积分');
					if (!$res['error_code']) {
						$is_success = true;
					}
				}

				if ($is_success) {
					$order_id =	D('Recruit_course_order')->add($order_data);
					// 视频发布者获得奖励
					if ($order_data['get_money']) {
						D('User')->add_money($now_course['uid'],$order_data['get_money'],'用户购买视频，增加余额');
						//平台抽成
						if (C('config.recruit_percent')>0) {
							$money = round($order_data['get_money']*C('config.recruit_percent')/100,2);
							D('User')->user_money($now_course['uid'],$money,'平台抽成，减少余额');
						}
					}

					if ($order_data['get_score']) {
						D('User')->add_score($now_course['uid'],$order_data['get_score'],'用户购买视频，增加积分');
						//平台抽成
						if (C('config.recruit_percent')>0) {
							$score = round($order_data['get_score']*C('config.recruit_percent')/100,2);
							D('User')->use_score($now_course['uid'],$score,'平台抽成，减少积分');
						}
					}
					 
					exit(json_encode(array('error'=>0,'msg'=>'可以查看')));
				}else{
					exit(json_encode(array('error'=>1,'msg'=>'积分扣除失败，请重试')));
				}
			}
		}else{
			exit(json_encode(array('error'=>0,'msg'=>'可以查看')));
		}
	}


	// 消息
	public function message()
	{
		$this->check_login();
		// if (!$this->now_company) {
		// 	$this->error_tips('您还不是企业，请先申请成为企业用户',U('company_apply'));
		// }
		// if ($this->now_company['status'] != 1) {
		// 	$this->error_tips('您的企业还未通过审核，请耐心等待审核通过');
		// }

		$course_id = intval($_GET['course_id']);
		$database_recruit_course = D('Recruit_course');
		$now_course = $database_recruit_course->where(array('course_id'=>$course_id))->find();
		

		// 是否收藏
        $collect_info = M('Recruit_collect')->where(array('cid'=>$course_id,'type'=>'course','uid'=>$this->user_session['uid']))->find();
		$this->assign('collect_info',$collect_info);

		$category = M('Recruit_course_category')->where(array('id'=>$now_course['cat_id']))->find();
		$now_course['cat_name'] = $category['name'] ? $category['name'] : '';

		// 浏览量
		$database_recruit_course->where(array('course_id'=>$course_id))->setInc('view_times',1);
		$this->assign('can_view',$can_view);
		$this->assign('now_course',$now_course);
		$this->display();
	}

	// 获得未读消息数
	public function ajax_message_count()
	{
		// 简历未读数
		$message_count = 0;
		if ($this->now_company) {
			$where['s.company_id'] = $this->now_company['company_id'];
			$where['s.status'] =0;
	        $join = 'as s LEFT JOIN '.C('DB_PREFIX').'user as u on `s`.`uid` = `u`.`uid` INNER JOIN '.C('DB_PREFIX').'recruit_company as c on `c`.`company_id` = `s`.`company_id`  INNER JOIN '.C('DB_PREFIX').'recruit_position as p on `p`.`position_id` = `s`.`position_id` INNER JOIN '.C('DB_PREFIX').'recruit_resume as r on `r`.`resume_id` = `s`.`resume_id`';
	        $message_count = D('Recruit_resume_send')->join($join)->where($where)->count();
		}
		$this->returnCode(0,$message_count);
	}

	public function ajax_message_list()
	{
		$pageSize = $_GET['pageSize'] ? $_GET['pageSize'] : 10;
		$where['other'] = '(s.uid ='.$this->user_session['uid'].' OR s.company_id='.$this->now_company['company_id'].')';
		$list = D('Recruit_resume_send')->get_limit_list_page($where,$pageSize,1);
		foreach ($list['list'] as &$value) {
            $value['add_time_format'] = date('Y-m-d H:i:s',$value['add_time']);
            $value['update_time_format'] = date('Y-m-d H:i:s',$value['update_time']);
            if ($value['uid'] == $this->user_session['uid']) {
            	$value['self'] =1;
            }
		}
		$list['page'] = $_GET['page'];
		$this->returnCode(0,$list);
	}

	// 消息详情
	public function message_detail()
	{
		$this->check_login();
		$id = intval($_GET['id']);
		$resume_send_detail = D('Recruit_resume_send')->get_one($id);
		
		// 置为已读
 		D('Recruit_resume_send')->where(array('id'=>$id))->setField('status',1);

		//邀约
		$invitation = M('Recruit_resume_invitation')->where(array('resume_id'=>$resume_send_detail['resume_id'],'position_id'=>$resume_send_detail['position_id']))->find();
		$this->assign('invitation',$invitation);

		// 是否购买了套餐
		if ($this->now_company['status'] == 1&&$this->now_company['invitation_times'] > 0&&$this->now_company['energy_expiration_time'] > time()) {
			$invitation_times = $this->now_company['invitation_times'];
		}

		$this->assign('invitation_times',$invitation_times?$invitation_times:0);
		$this->assign('resume_send_detail',$resume_send_detail);

		$this->display();
	}

	// 上传图片
	public function ajax_upload_file_base64(){

		$img_mer_id = sprintf("%09d", $this->user_session['uid']);
        $rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
        $up_dir = './upload/recruit/'.$rand_num.'/';

        if(!file_exists($up_dir)){
            $q = mkdir($up_dir,0777,true);
        }

        $base64_img = $_POST['file'];
        if ($base64_img) {
            if(preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_img, $result)){
                $type = $result[2];
                if(in_array($type,array('pjpeg','jpeg','jpg','gif','bmp','png'))){
                    $new_file = $up_dir.date('YmdHis_').uniqid().'.'.$type;
                    if(file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_img)))){	
                    	//判断是否需要上传至云存储
						$file_handle = new file_handle();
						$file_handle->upload($new_file);
                        $img_path = trim($new_file,'.');
						exit(json_encode(array('error'=>0,'msg'=>'上传成功','url'=>$img_path)));
                    }else{
						exit(json_encode(array('error'=>2,'msg'=>'上传失败')));
                    }
                }else{
                    //文件类型错误
						exit(json_encode(array('error'=>1,'msg'=>'文件类型错误')));
                }
            }
        }
            
        
	}

	// 上传图片
	public function ajax_upload_file(){
		if($_FILES['imgFile']['error'] == 4){
			exit(json_encode(array('error'=>1,'msg'=>'没有选择图片')));
		}
		$upload_file = D('Image')->handle($this->user_session['uid'], 'recruit', 0, array('size' => 4), false);
		if ($upload_file['error']){
			exit(json_encode(array('error'=>1,'msg'=>'上传失败，请重试！')));
		}else{
			exit(json_encode(array('error'=>2,'msg'=>'上传成功','url'=>$upload_file['url']['imgFile'])));
		}
	}

	// 上传视频
	public function ajax_upload_video(){
		if($_FILES['videoFile']['error'] == 4){
			exit(json_encode(array('error'=>1,'msg'=>'没有选择')));
		}
		set_time_limit(0);
		import("ORG.Net.UploadFile");
		$upload = new UploadFile();
		$upload->maxSize = 2048 * 1024 * 1024 ; // 2G
		$upload->allowExts = array('mp4', 'flv', 'png', 'gif', 'mp3', 'ico','mov','rmvb','avi','wmv','4gp','mpeg1');

		$upload->savePath =  './Public/Uploads/';// 设置附件上传目录

		$img_mer_id = sprintf("%09d", $this->user_session['uid']);
		
		$rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);

		$upload_dir = "./upload/recruit/video/{$rand_num}/";
		
		if(!is_dir($upload_dir)){
			mkdir($upload_dir, 0777, true);
		}

		$upload->savePath = $upload_dir;// 设置附件上传目录

		if(!$upload->upload()) {// 上传错误提示错误信息
			// $this->error($upload->getErrorMsg());
			exit(json_encode(array('error'=>1,'msg'=>$upload->getErrorMsg())));
		}else{// 上传成功 获取上传文件信息
			$info =  $upload->getUploadFileInfo();
			
			$from = $info[0]['savepath'].$info[0]['savename'];  
			$to = $info[0]['savepath'].$info[0]['savename'].'.png';
			$str = "/home/ffmpeg/bin/ffmpeg -i ".$from." -y -f mjpeg -ss 0.01 -t 1 -s 740x500 ".$to;  
			exec($str); 
			// ffmpeg常用参数的介绍
			// -i 指定要转换视频的源文件
			// -s 视频转换后视频的分辨率
			// -vcodec 视频转换时使用的编解码器
			// -r 视频转换换的桢率(默认25桢每秒)
			// -b 视频转换换的bit率
			// -ab 音频转换后的bit率(默认64k)
			// -acodec 制度音频使用的编码器
			// -ac 制定转换后音频的声道
			// -ar 音频转换后的采样率
			// avi to mp4
			// ffmpeg -i source.avi -f psp -r 29.97 -b 768k -ar 24000 -ab 64k -s 320×240 destination.mp4

			$name_all=$info[0]['savename'];
			$name = substr($name_all,0,strrpos($name_all,'.'));
			$test = "/home/ffmpeg/bin/ffmpeg -i ".$info[0]['savepath'].$info[0]['savename']." -c:v libx264 -strict -2 ".$info[0]['savepath'].$name."_copy.mp4 &";
			exec($test);
			$url = $info[0]['savepath'].$name.'_copy.mp4';
			// $url = substr($url,0,strpos($url,'.'));

			//判断是否需要上传至云存储
			$file_handle = new file_handle();
			$file_handle->upload($to);
			$file_handle->upload($url);
			$url = trim($url,'.');
			$to = trim($to,'.');
			exit(json_encode(array('error'=>2,'msg'=>'上传成功','url'=>$url,'image'=>$to)));
		}
		
	}


    //根据城市ID得到符合的区县或商城
    public function get_area(){
        $area_id = intval($_POST['area_id']);
        $database_area = D('Area');
        if(!empty($area_id)){
            if($this->config['location_mode']){
                // 获取商城（单独客户需求）
                $area_city = $database_area->get_tranding_by_areaPid($area_id,true);
            }else{
                // 获取区县
                $area_city = $database_area->get_arealist_by_areaPid($area_id,true);
            }
        }
        if(!$area_city){
            $area_city = array();
        }
        $this->returnCode(0,$area_city);
    }

    //通过城市名或经纬度获取城市信息
	public function getLocationCity($lat,$lng){	
		$arr = $this->geocoder($lat,$lng);
		$city = $this->cityMatchingAll($arr['city'],$arr['district']);
		return $city ? $city : array();
	}

	public function geocoder($lat, $lng){
		if ($lat&&$lng) {
			$url = 'http://api.map.baidu.com/geocoder/v2/?location='.$lat.','.$lng.'&output=json&pois=1&ak=' . $this->config['baidu_map_ak'];
			import('ORG.Net.Http');
			$http = new Http();
			$result = $http->curlGet($url);
			if($result){
				$result = json_decode($result,true);
				$arr = array(
						'city' => $result['result']['addressComponent']['city'],
						'province' => $result['result']['addressComponent']['province'],
						'district' => $result['result']['addressComponent']['district'],
					);
				return $arr;
			}else{
				return array();
			}
		}else{
			return array();
		}
	}

    public function cityMatchingAll($city_id,$area_name='',$lat=0,$lng=0){
        $long   =   strlen($city_id);
        if($long >= 7){
            $city_id    =   str_replace('市','',$city_id);
			$city_id    =   str_replace('地区','',$city_id);
            $city_id    =   str_replace('特别行政区','',$city_id);
            $city_id    =   str_replace('蒙古自治州','',$city_id);
            $city_id    =   str_replace('回族自治州','',$city_id);
            $city_id    =   str_replace('柯尔克孜自治州','',$city_id);
            $city_id    =   str_replace('哈萨克自治州','',$city_id);
            $city_id    =   str_replace('土家族苗族自治州','',$city_id);
            $city_id    =   str_replace('藏族羌族自治州','',$city_id);
			$city_id    =   str_replace('傣族自治州','',$city_id);
			$city_id    =   str_replace('布依族苗族自治州','',$city_id);
			$city_id    =   str_replace('苗族侗族自治州','',$city_id);
			$city_id    =   str_replace('壮族苗族自治州','',$city_id);
			$city_id    =   str_replace('澳门','',$city_id);
			$city_id    =   str_replace('朝鲜族自治州','',$city_id);
			$city_id    =   str_replace('哈尼族彝族自治州','',$city_id);
			$city_id    =   str_replace('傣族景颇族自治州','',$city_id);
			$city_id    =   str_replace('藏族自治州','',$city_id);
			$city_id    =   str_replace('彝族自治州','',$city_id);
			$city_id    =   str_replace('白族自治州','',$city_id);
			$city_id    =   str_replace('傈僳族自治州','',$city_id);
        }
        $database_area = D('Area');
        $database_field = '`area_id`,`area_name`,`area_url`';
        $condition_all_city['area_name'] = $city_id;
        $condition_all_city['area_type'] = 2;
        $condition_all_city['is_open'] = 1;
        $oCity = $database_area->field($database_field)->where($condition_all_city)->find();
        if($city_id&&$oCity){
            // 获取当前区县
            if($area_name){
                $maybe_areaname = str_replace(array('区','县'),'',$area_name);
                $where_area['area_pid'] = $oCity['area_id'];
                $where_area['_string'] = '(`area_name`= "'.$area_name.'" OR `area_name`="'.$maybe_areaname.'")';
                $now_area = D('Area')->where($where_area)->find();
                
                if(empty($now_area)){
                    import('@.ORG.words');
                    $words_class = new words();
                    $data_area['area_pid'] = $oCity['area_id'];
                    $data_area['area_name'] = $area_name;
                    $data_area['area_url'] = $words_class->stringgetFirstCharter($area_name);
                    $data_area['area_type'] = '3';
                    if($data_area['area_id'] = D('Area')->data($data_area)->add()){
                        $now_area = $data_area;
                    }
                }
                
                $oCity['now_area_id'] = $now_area['area_id'];
                $oCity['now_area_name'] = $now_area['area_name'];
            }

            // 通过经纬度获取该区县下距离最近的商场信息
            if($lat && $lng ){
                $oCity['now_area_id'] = $oCity['area_id'];
                $oCity['now_area_name'] = $oCity['area_name'];
                if ($now_area) {
                    $now_tranding = D('Area')->get_tranding_by_countyPid($now_area['area_id'],true);
                    $min_distance_tranding = array();
                    $distance_old = 0;
                    if ($now_tranding) {
                        foreach ($now_tranding as $key => $value) {
                            $distance = getDistance($lat, $lng, $value['lat'], $value['lng']); // 距离
                            if($min_distance_tranding){
                                if ($distance < $distance_old) { // 取较小的距离
                                    $min_distance_tranding = $value;
                                }
                            }else{
                                $min_distance_tranding = $value;
                                $distance_old = $distance;
                            }
                        }
                        if ($min_distance_tranding) {
                            $oCity['now_area_id'] = $min_distance_tranding['market_id'];
                            $oCity['now_area_name'] = $min_distance_tranding['market_name'];
                        }
                    }
                }
            }
            return  $oCity;
        }else{
	        $condition_all_city = array();
			$condition_all_city['area_id'] = $this->config['now_city'];
	        $condition_all_city['area_type'] = 2;
	        $condition_all_city['is_open'] = 1;
	        $oCity = $database_area->field($database_field)->where($condition_all_city)->find();
        	if ($oCity) {
	            $oCity['now_area_id'] = $oCity['area_id'];
	            $oCity['now_area_name'] = $oCity['area_name'];
            	return  $oCity;
        	}else{
        		return array();
        	}
        }
    }

    // 发送短信
    public function Generate() {
        $phone = trim($_POST['phone']);
        $tmpid = intval($_POST['tmpid']);
        $vfycode = trim($_POST['vfycode']);
        $flesh = trim($_POST['verify']);
        if (empty($phone)) {
            exit(json_encode(array('error_code' => 1, 'msg' => '手机账号不能为空')));
        }
        if (!is_numeric($phone) || (strlen($phone) != 11)) {
            exit(json_encode(array('error_code' => 1, 'msg' => '请输入正确手机账号')));
        }
        $user_modifypwdDb = M('User_modifypwd');
		
		$vcode = createRandomStr(6,true,true);
		$content = L_('您的验证码是：X1。此验证码20分钟内有效，请不要把验证码泄露给其他人。如非本人操作，可不用理会！',$vcode);
		Sms::sendSms(array('mer_id' => 0, 'store_id' => 0, 'content' => $content, 'mobile' => $phone, 'uid' => 0, 'type' => 'recruit'));

        $addtime = time();
        $expiry = $addtime + 20 * 60; /*             * **二十分钟有效期*** */
        $data = array('telphone' => $phone, 'vfcode' => $vcode, 'expiry' => $expiry, 'addtime' => $addtime);
        $insert_id = $user_modifypwdDb->add($data);
        exit(json_encode(array('error_code' => 0, 'id' => $insert_id)));
    }
}