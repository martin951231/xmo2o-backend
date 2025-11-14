<?php

class Village_groupAction extends BaseAction{
	public function __construct()
	{
		parent::__construct();
		//首页底部客服开关
		if($this->config['village_group_index_kf']){
			$kefu['phone'] = empty($this->config['site_phone']) ? array() : explode(' ',$this->config['site_phone']);
			
			if (($this->is_weixin_browser || $this->is_wexin_browser) && $this->config['open_kefu_url'] &&  $_SESSION['openid'] && $services = D('Customer_service')->where(array('mer_id' => $this->config['kefu_mer_id']))->select()) {
                $key = $this->get_encrypt_key(array('app_id'=>$this->config['im_appid'],'openid' => $_SESSION['openid']), $this->config['im_appkey']);
                $kefu['url'] = ($this->config['im_url'] ? $this->config['im_url'] : 'http://im-link.weihubao.com').'/?app_id=' . $this->config['im_appid'] . '&openid=' . $_SESSION['openid'] . '&key=' . $key . '#serviceList_' . $this->config['kefu_mer_id'];
            }
			
			$this->assign('kefu',$kefu);
		}

		$this->assign('open',$this->config['village_group_index_kf']);
	}

	public function check_login(){

		if(empty($this->user_session)){
			if($this->is_app_browser){
				$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
				$this->error_tips('请先进行登录！',U('Login/index',$location_param));
			}else{
				$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
				$redirect_url = U('Login/index',$location_param);
			}
			$this->assign('redirect_url',$redirect_url);

			$this->display('my_login');
			die;
		}
	}

	//社区拼团
	public function index(){
		$leader_id = intval($_GET['leader_id']);
		if ($leader_id > 0) { 
			redirect($this->config['site_url'] . "/packapp/community_group/#/index?leaderId=" . intval($_GET['leader_id']));
		} else {
			redirect($this->config['site_url'] . "/packapp/community_group/#/index");
		}

		
        redirect($this->config['site_url']."/packapp/community_group/index.html?c=Village_group&a=index".($_GET['leader_id'] ? "&leaderId=".intval($_GET['leader_id']) : ""));
		//redirect($this->config['site_url']."/packapp/community_group/index.html#/index".($_GET['leader_id'] ? "?leaderId=".intval($_GET['leader_id']) : ""));
		//===>郭先森重构
		$leader_id = intval($_GET['leader_id']);

		if(!$leader_id){
			if($_COOKIE['village_group_leader_id']){
				redirect(U('index',array('leader_id'=>$_COOKIE['village_group_leader_id'])));
				fdump("111111",'-------',true);
			}
		}
		if($leader_id){
			$_SESSION['leader_id'] = $_GET['leader_id'];
			setcookie('village_group_leader_id',$_GET['leader_id'],$_SERVER['REQUEST_TIME']+31536000,'/');

		}else{
			redirect(U('leader'));

		}


//		$leader_id = intval($_GET['leader_id']);
//		if(!$leader_id){
//			if($_SESSION['leader_id']>0) {
//				redirect(U('index',array('leader_id'=>$_SESSION['leader_id'])));
//			} elseif($_COOKIE['village_group_leader_id']){
//				redirect(U('index',array('leader_id'=>$_COOKIE['village_group_leader_id'])));
//			}
//		}
//		if($leader_id && $leader_id != $_SESSION['leader_id']){
//			$_SESSION['leader_id'] = $leader_id;
//			$_COOKIE['village_group_leader_id'] = $leader_id;
//			setcookie('village_group_leader_id',$leader_id,$_SERVER['REQUEST_TIME']+72000000,'/');
//		}elseif(!$leader_id){
//			redirect(U('leader'));
//		}

		$village_group_index = D('Adver')->get_adver_by_key('village_group_index',3);
		$header = D('Village_group')->get_header_info(0,$leader_id);
		$header  = $header['msg'];
		if($header['status']==0){
			$this->error_tips('团长信息有误，请更换团长',U('leader'));
		}

		if($header['pick_address_status']==0){
			$this->error_tips('提货地点状态异常，请重新选择团长',U('leader'));
		}

		if( $this->user_session['uid'] && $this->config['open_rand_send']){
			$coupon_html = D('System_coupon')->rand_send_coupon_get(array('time'=>$_SERVER['REQUEST_TIME'],'uid'=>$this->user_session['uid'],'cate_name'=>'village_group'));
			$coupon_html && $this->assign('coupon_html',$coupon_html);
		}

		$category = D('Village_group')->get_category();

		if (C('config.village_group_hide_detail')) {
			$header['phone'] = '';
			$header['name'] = str_replace_name($header['name']);
			if (mb_strlen($header['pick_address']['pick_addr'],'UTF8') > 3) {
					if (mb_strlen($header['pick_address']['pick_addr'],'UTF8') == 4) {
					$header['pick_address']['pick_addr'] =  mb_substr($header['pick_address']['pick_addr'],0,-3).'****';
				}else{
					$header['pick_address']['pick_addr'] =  mb_substr($header['pick_address']['pick_addr'],0,-4).'****';
				}
			}
		}

		$this->assign('header',$header);
		$this->assign('village_group_index',$village_group_index);
		$this->assign('category',$category);
		$this->assign('share_txt',D('Village_group')->get_share_txt(false,$_GET['leader_id']));
		$share_txt_img = D('Village_group')->get_share_txt(true,$_GET['leader_id']);

		$this->assign('share_txt_img',$share_txt_img);
//		//首页底部客服开关
//		$open = D('Config')->get_config_by_name('village_group_index_kf');
//		if($open['value']==1){
//			$site_phone = empty($this->config['site_phone'])?array():explode(' ',$this->config['site_phone']);
//			$kefu['phone'] =$site_phone;
//
//			if ($this->is_weixin_browser && $this->config['open_kefu_url'] &&  $this->now_user['openid'] && $services = D('Customer_service')->where(array('mer_id' => $this->config['kefu_mer_id']))->select()) {
//				$key = $this->get_encrypt_key(array('app_id'=>$this->config['im_appid'],'openid' => $this->now_user['openid']), $this->config['im_appkey']);
//				$kefu['url'] = ($this->config['im_url'] ? $this->config['im_url'] : 'http://im-link.weihubao.com').'/?app_id=' . $this->config['im_appid'] . '&openid=' . $this->now_user['openid'] . '&key=' . $key . '#serviceList_' . $this->config['kefu_mer_id'];
//			}
//			$this->assign('kefu',$kefu);
//		}
//
//		$this->assign('open',$open['value']);
		$this->display();
	}



	//分享页面
	public function imgBoxShare(){
		if(IS_POST){

		}else{

            $leader_id = intval($_GET['leader_id']);
            $share_txt_img = D('Village_group')->get_share_txt(true,$leader_id);
		    $this->assign('share_txt_img',$share_txt_img);
		    $this->assign('share_txt',D('Village_group')->get_share_txt(false,$leader_id));
            //setcookie('village_group_leader_id',$leader_id,$_SERVER['REQUEST_TIME']+72000000,'/');//郭先森//进入分享页面后,设置cookie,后退没有leader_id,则进入首页
            $where['g.status']  = 1;
            if($leader_id){
                $address = M('Village_group_pick_address')->where(array('header_uid'=>$leader_id))->find();
                $where['_string'] = "(b.city_id IS NUll OR (ga.area_id = {$address['province_id']}) OR ga.area_id = {$address['city_id']} OR ga.area_id = {$address['area_id']}) AND (b.buy_user_type IS NUll OR b.buy_user_type=0 OR (b.buy_user_type=1 AND locate({$address['id']} , b.address_list)) OR  (b.buy_user_type=2 AND locate({$address['id']} , b.address_list)=0))";
            }
            $left_join = 'as g LEFT JOIN '.C('DB_PREFIX').'village_group_goods_bind_area b ON g.goods_id = b.goods_id LEFT JOIN '.C('DB_PREFIX').'village_group_area ga ON ga.pigcms_id = b.city_id  LEFT JOIN '.C('DB_PREFIX').'area a ON a.area_id = ga.area_id LEFT JOIN '.C('DB_PREFIX').'village_group_goods_increase_price i ON i.goods_id = g.goods_id AND i.header_uid='.$leader_id;

            $list = D('Village_group_goods')->field('g.goods_id')->join($left_join)->where($where)->limit(20)->order('`g`.`sort` DESC,`g`.`goods_id` DESC')->select();

			if(count($list)==0){
				$this->error_tips('没有商品可供分享');
			}
			$this->assign('list',$list);
			$this->assign('totalPage',count($list));

			$this->display();
		}
	}


	public function get_goods_by_cat_id(){
		$leader_id = intval($_POST['leader_id']);

		$cat_fid = intval($_POST['cat_id']);
		$model = D('Village_group');
//		$now_group_info = $model->get_village_group_info($cat_fid);
		//郭先森 重构
		$now_group_info = $model->get_village_group_info($cat_fid,'',$leader_id);

		if($now_group_info['goods']){
			$now_group_info['goods'] = array_values($now_group_info['goods']);
		} else {
			$now_group_info['goods'] = array();
		}

//		$header = $model->get_header_info(0,$_SESSION['leader_id']);
		//郭先森 //重构
		$header = $model->get_header_info(0,$_POST['leader_id']);

		foreach ($now_group_info['goods'] as &$value) { //升级到区县 intval($header['msg']['pick_address']['area_id'] ===>郭先森
			$goods_bind_area = D('Village_group_goods_bind_area')->get_area_goods_bind_info($value['goods_id'],intval($header['msg']['pick_address']['province_id']),intval($header['msg']['pick_address']['city_id']),intval($header['msg']['pick_address']['area_id']));
			if ($goods_bind_area) {
				$value['price'] = $goods_bind_area['price'];
				if ($goods_bind_area['is_add_price'] && $value['increase_price'] && $value['increase_price']>0) {
					$value['price'] += $value['increase_price'];
				}
			}
			$value['image'] = thumb_img($value['image'],240,240,'fill');
		}
		echo json_encode($now_group_info);die;
	}
	
	//数据统计
	public function statistics_list(){
		if(empty($this->user_session)){
			if($this->is_app_browser){
				$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
				$redirect_url = U('Login/index',$location_param);
			}else{
				$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
				$redirect_url = U('Login/index',$location_param);
			}
			$this->assign('redirect_url',$redirect_url);

			$this->display('my_login');
			die;
		}

		// 团长详情
		$my_header = D('Village_group')->get_header_info($this->user_session['uid']);
		if($my_header['error_code']){
			$this->error_tips('您不是团长，不能查看统计');
		}

		// 查询订单
		$where['start_uid'] = $my_header['msg']['id'];
		$where['status'] = array('in','2,3,13');
		$return = array();
		// 总订单数
		$return['all_order_count'] = M('Village_group_order')->where($where)->count();

		// 总订单金额
		$return['all_order_momey'] = M('Village_group_order')->field('sum(discount_price) as total_money')->where($where)->select();
		$return['all_order_momey'] = $return['all_order_momey'][0]['total_money'];

		// 商品数
		$order_list = M('Village_group_order')->field('order_id')->where($where)->select();
		$order_ids = array();
		foreach ($order_list as $value) {
			$order_ids[] = $value['order_id'];
		}
		$goods_count = 0;
		$order_ids && $goods_count = M('Village_group_order_detail')->where(array('order_id'=>array('in',$order_ids)))->count();
		$return['all_goods_count'] = $goods_count;


		// var_dump(M('Village_group_order')->_sql());
		// 今日订单数
		$start_time = strtotime(date('Y-m-d'));
		$end_time = time();
		$where['pay_time'] = array('between',array($start_time,$end_time));
		$return['today_order_count'] = M('Village_group_order')->where($where)->count();
		$return['today_time'] = date('Y-m-d');

		// 总订单金额
		$return['today_order_momey'] = M('Village_group_order')->field('sum(discount_price) as total_money')->where($where)->select();
		$return['today_order_momey'] = $return['today_order_momey'][0]['total_money'];

		// 商品数
		$order_list = M('Village_group_order')->field('order_id')->where($where)->select();
		$order_ids = array();
		foreach ($order_list as $value) {
			$order_ids[] = $value['order_id'];
		}
		$goods_count = 0;
		$order_ids && $goods_count = M('Village_group_order_detail')->where(array('order_id'=>array('in',$order_ids)))->count();
		$return['today_goods_count'] = $goods_count;	

		// var_dump(M('Village_group_order')->_sql());
		// 本月订单数
		$start_time = strtotime( date('Y-m-01'));
		$end_time = strtotime( date('Y-m-t'));
		$return['month_start_time'] = date('Y-m-01');
		$return['month_end_time'] = date('Y-m-t');
		$where['pay_time'] = array('between',array($start_time,$end_time));
		$return['month_order_count'] = M('Village_group_order')->where($where)->count();

		// 本月订单金额
		$return['month_order_momey'] = M('Village_group_order')->field('sum(discount_price) as total_money')->where($where)->select();
		$return['month_order_momey'] = $return['month_order_momey'][0]['total_money'];

		// 本月商品数
		$order_list = M('Village_group_order')->field('order_id')->where($where)->select();
		$order_ids = array();
		foreach ($order_list as $value) {
			$order_ids[] = $value['order_id'];
		}
		$goods_count = 0;
		$order_ids && $goods_count = M('Village_group_order_detail')->where(array('order_id'=>array('in',$order_ids)))->count();
		$return['month_goods_count'] = $goods_count;

		// 查询订单 按月统计
		$where = array();
		$where['o.start_uid'] = $my_header['msg']['id'];
		$where['o.status'] = array('in','2,3,13');

		$list = M('Village_group_order_detail')->field('d.goods_id,d.name,d.num,o.pay_time')->join(' as d left join '.C('DB_PREFIX').'village_group_order as o on o.order_id=d.order_id')->where($where)->order('pay_time ASC')->select();
//		if (!$list) {
//			exit(json_encode(array('code'=>1,'msg'=>'暂无数据')));
//		}
		foreach ($time_arr as &$_time) {
			$_tmp = array();
			foreach ($list as $key => $value) {
				if ($value['pay_time'] >= $_time && $value['pay_time'] < ($_time+86400)) {
					if ($_tmp[$value['goods_id']]) {
						$_tmp[$value['goods_id']]['num'] += $value['num'];
					}else{
						$_tmp[$value['goods_id']] = $value;
					}
				}
			}
			$return[$_time]['goods'] = $_tmp;
		}
		$this->assign('data',$return);
		$this->display();
	}

	//查询订单 按月统计
	public function ajax_statistics_list(){
		
		$page = $_POST['page'] ? $_POST['page'] : 1;
		$pageSize = 10;

		// 团长详情
		$my_header = D('Village_group')->get_header_info($this->user_session['uid']);
		if($my_header['error_code']){
			exit(json_encode(array('code'=>1,'msg'=>'您不是团长，不能查看统计')));
		}

	
		$where['start_uid'] = $my_header['msg']['id'];
		$where['status'] = array('in','2,3,13');
		$order_first = M('Village_group_order')->field('pay_time')->order('pay_time DESC')->where($where)->find();
		if (!$order_first) {
			exit(json_encode(array('code'=>1,'msg'=>'暂无数据')));
		}
		$end_time = $order_first['pay_time'];
		$f =	date('Y-m-01',$end_time);

		$end_time_tmp = date('Y-m-d 23:59:59',strtotime("$f +1 month -1 day"));
		$next_page = ($page-1)*$pageSize;
		$next_page_1 = $page*$pageSize;
		$end_time	= strtotime("$end_time_tmp -$next_page month -0 day");
		$start_time	= strtotime("$end_time_tmp -$next_page_1 month -0 day");
		$start_time = strtotime('2018-01-01') > $start_time_tmp ? strtotime('2018-01-01'): $start_time_tmp;

		// 总页数
		$month_numbers = $this->month_numbers('2018-01-01',$end_time_tmp);
		$pagetotal = ceil($month_numbers/$pageSize);

		// 时间
		$time_arr = array();
		$_temp = date('Y-m-d H:i:s',$end_time);
		for($i=1;$i<$pageSize;$i++){
			$_temp_time = strtotime("$_temp -$i month -1 day");
			if ($_temp_time<strtotime('2018-01-01')) {
				break;
			}
			$time_arr[$i] = date('Y-m',$_temp_time);
		}

		// 订单数，订单金额
		$where['pay_time'] = array('between',array($start_time,$end_time));
		$list = M('Village_group_order')->field('count(*) as count, sum(discount_price) as total_money, DATE_FORMAT(from_unixtime(pay_time),"%Y-%m") months')->where($where)->group('months')->select();
		// var_dump(M('Village_group_order')->_sql());;
		$list1 = array();
		foreach ($list as $key => $value) {
			$list1[$value['months']] = $value;
		}

		// 商品数
		$where = array();
		$where['o.start_uid'] = $my_header['msg']['id'];
		$where['o.status'] = array('in','2,3,13');
		$where['o.pay_time'] = array('between',array($start_time,$end_time));
		$list = M('Village_group_order_detail')->field('sum(d.num) as num,o.pay_time, DATE_FORMAT(from_unixtime(o.pay_time),"%Y-%m") months')->join(' as d left join '.C('DB_PREFIX').'village_group_order as o on o.order_id=d.order_id')->where($where)->order('pay_time DESC')->group('months')->select();

		$list2 = array();
		foreach ($list as $key => $value) {
			$list2[$value['months']] = $value;
		}

		$return = array();
		foreach ($time_arr as $value) {
			$_tmp = array();
			$_tmp['date'] = $value;
			$_tmp['count'] = intval($list1[$value]['count']);
			$_tmp['total_money'] = $list1[$value]['total_money'] ? $list1[$value]['total_money'] : 0;
			$_tmp['num'] = intval($list2[$value]['num']);
			$_tmp['start_time'] = date('Y-m-01',strtotime($value));
			$start_time =$_tmp['start_time'];
			$_tmp['end_time'] = date('Y-m-d',strtotime("$start_time +1 month -1 day"));
			$return[] = $_tmp;
		}
		exit(json_encode(array('code'=>0,'data'=>array('list'=>$return,'page'=>$page,'pagetotal'=>$pagetotal))));
	} 

	public function month_numbers($start_m,$end_m){ //日期格式为2018-8-28
		$date1 = explode('-',$start_m);
		$date2 = explode('-',$end_m);

		if($date1[1]<$date2[1]){ //判断月份大小，进行相应加或减
		   $month_number= abs($date1[0] - $date2[0]) * 12 + abs($date1[1] - $date2[1]);
		}else{
		   $month_number= abs($date1[0] - $date2[0]) * 12 - abs($date1[1] - $date2[1]);
		}
		return $month_number;
	}
	
	//商品销售明细
	public function pro_sales_details(){
		 $this->display();
	} 	

	//商品销售明细
	public function ajax_pro_sales_details(){
		$start_time = $_POST['start_time'];
		$end_time = $_POST['end_time'];
		$page = $_POST['page'] ? $_POST['page'] : 1;
		$pageSize = 10;

		// 团长详情
		$my_header = D('Village_group')->get_header_info($this->user_session['uid']);
		if($my_header['error_code']){
			exit(json_encode(array('code'=>1,'msg'=>'您不是团长，不能查看统计')));
		}

		if (!$start_time) {
			$where['start_uid'] = $my_header['msg']['id'];
			$where['status'] = array('in','2,3,13');
			$order_first = M('Village_group_order')->field('pay_time')->order('pay_time ASC')->where($where)->find();
			if (!$order_first) {
				exit(json_encode(array('code'=>1,'msg'=>'暂无数据')));
			}
			$start_time = strtotime(date('Y-m-d',$order_first['pay_time']));
		}else{
			$start_time = strtotime($start_time);
		}

		if (!$end_time) {
			$end_time = strtotime(date('Y-m-d 23:59:59',time()));
		}else{
			$end_time = strtotime($end_time) + 86400 -1;
		}

		// 总页数
		$day_numbers =	ceil(($end_time - $start_time)/86400);
		$pagetotal = ceil($day_numbers/$pageSize);

		$start_time = $start_time + ($page-1)*$pageSize*86400;
			// var_dump($start_time);
		$end_time_tmp = $start_time + ($page)*$pageSize*86400-1;
		$end_time = $end_time > $end_time_tmp ? $end_time_tmp : $end_time;

		// 时间
		$time_arr = array();
		$return = array();
		for($i=0;$i<$pageSize;$i++){
			if ($start_time + $i*86400>$end_time) {
				break;
			}
			$time_arr[] = $start_time + $i*86400;
			$return[$start_time + $i*86400]['date'] = date('Y-m-d',$start_time + $i*86400);
		}

		// 查询订单
		$where = array();
		$where['o.start_uid'] = $my_header['msg']['id'];
		$where['o.status'] = array('in','2,3,13');
		$where['o.pay_time'] = array('between',array($start_time,$end_time));

		$list = M('Village_group_order_detail')->field('d.goods_id,d.name,d.num,o.pay_time')->join(' as d left join '.C('DB_PREFIX').'village_group_order as o on o.order_id=d.order_id')->where($where)->order('pay_time ASC')->select();

		// var_dump(M('Village_group_order_detail')->_sql());
		// if (!$list) {
		// 	exit(json_encode(array('code'=>1,'msg'=>'暂无数据')));
		// }
		foreach ($time_arr as &$_time) {
			$_tmp = array();
			foreach ($list as $key => $value) {
				if ($value['pay_time'] >= $_time && $value['pay_time'] < ($_time+86400)) {
					if ($_tmp[$value['goods_id']]) {
						$_tmp[$value['goods_id']]['num'] += $value['num'];
					}else{
						$_tmp[$value['goods_id']] = $value;
					}
				}
			}
			$return[$_time]['goods'] = array_values($_tmp);
		}
		$return = array_values($return);
		exit(json_encode(array('code'=>0,'data'=>array('list'=>$return,'page'=>$page,'pagetotal'=>$pagetotal))));
	} 


	

	public function ajax_leader(){
		$leader_id = $_POST['leader_id'];

		$header = D('Village_group')->get_header_info(0,$leader_id);
		if($header['error_code'] || empty($header['msg']) || $header['msg']['status']!=1){
			$this->error('团长不存在',U('leader'));
		}else{
			$this->success('');
		}
	}

	public function leader(){
		if($_GET['leader_id']){

			$header = D('Village_group')->get_header_info(0,$_GET['leader_id']);
			$header  =$header['msg'];
		}else{
			$lat = $_COOKIE['userLocationLat'];
			$lng = $_COOKIE['userLocationLong'];
			$now_area =  D('Area')->cityMatching($lat,$lng);
			$condition['h.status'] = 1;
			$condition['s.status'] = 1;
			$field = "h.*,s.city_id,s.area_id,s.province_id,s.name as village_name,s.long,s.lat,s.pick_addr,u.avatar,ROUND(12756.276 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$lng}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli";
			$header = M('Village_group_pick_address')->field($field)->join('as s LEFT JOIN '.C('DB_PREFIX').'village_group_header as h ON s.header_uid = h.id LEFT JOIN '.C('DB_PREFIX').'user as u ON u.uid = h.uid')->where($condition)->order('juli ASC')->find();
			if(empty($header)){
				redirect(U('leaderList'));
			}
			$header['pick_address']['name'] = $header['village_name'];
			$header['pick_address']['pick_addr'] = $header['pick_addr'];
		}

		if (C('config.village_group_hide_detail')) {
			$header['phone'] = '';
			$header['name'] = str_replace_name($header['name']);
			if (mb_strlen($header['pick_address']['pick_addr'],'UTF8') > 3) {
					if (mb_strlen($header['pick_address']['pick_addr'],'UTF8') == 4) {
					$header['pick_address']['pick_addr'] =  mb_substr($header['pick_address']['pick_addr'],0,-3).'****';
				}else{
					$header['pick_address']['pick_addr'] =  mb_substr($header['pick_address']['pick_addr'],0,-4).'****';
				}
			}
		}

		if ($lat > 0 && $lng > 0 && $header['pick_address']['lat'] > 0 && $header['pick_address']['long'] > 0) {
			$header['distance']=$this->wapFriendRange( getDistance($lat,$lng,$header['pick_address']['lat'],$header['pick_address']['long']));
		} else {
			$header['distance']='';
		}
		$this->assign('header',$header);
		$this->display();
	}

	public function leaderOrder(){
		$this->check_login();

		$this->display();
	}

	public function leaderOrderDetail(){
		$this->check_login();
		$order  = D('Village_group_order')->order_detail($_GET['order_id'],$_GET['leader_id']);
		if($order['status']==0 && $order['add_time']+1800<time()){
			M('Village_group_order')->where(array('order_id'=>$_GET['order_id']))->save(array('status'=>10,'last_time'=>time()));
			D("System_order")->save_order_status('village_group', $_GET['order_id'], 5);
			$order['status'] = 10;
		}

        $is_mobile_pay = $order['plat_order']['is_mobile_pay'] ? $order['plat_order']['is_mobile_pay'] : 1;
        $order['pay_type_txt'] = D('Pay')->get_pay_name($order['pay_type'],$is_mobile_pay,1);

		$order['status_txt'] = D('Village_group')->order_status[$order['status']];
		if ($order['order_status']==2 && $order['is_pick_group']==1) {
            $order['status_txt'] = '团长收货 待配送';
        }

        if ($order['discount_detail']) {
            $goods = $order['goods'];
            $discount_detail = unserialize($order['discount_detail']);
            $good_discount = array();
            foreach ($discount_detail as $val) {
                if ($val['goods_id']) {
                    if (empty($val['discount_price'])) {
                        if ($goods[$val['goods_id']]['area_price']>0) {
                            $area_price = $goods[$val['goods_id']]['area_price'];
                            $val['price'] = $area_price;
                        }
                        $val['increase_price'] = $val['increase_price']>0?floatval($val['increase_price']):0;
                        if ($val['level_discount_increase']<=0 || !$val['level_discount_increase']) {
                            $val['level_discount_increase']=0;
                        }
                        $val['discount_price'] = round_number($val['price']+$val['increase_price']-$val['level_discount']-$val['level_discount_increase'],2);
                    }
                    $good_discount[$val['goods_id']]=$val;
                }
            }
            $this->assign('good_discount',$good_discount);
        }

        $level_off_system = M(_view('User_level'))->where(array('level' => $order['user_level']))->find();
        $this->assign('level_off_system',$level_off_system);
        // 获取优惠券信息
        $system_coupon = D('System_coupon')->get_coupon_by_id($order['coupon_id']);
        $this->assign('system_coupon',$system_coupon);

        // 计算实际支付金额
        $order['actual_discount_money'] = $order['discount_price'] - $order['score_deducte'];
        $order['actual_discount_money'] = round_number($order['actual_discount_money'],2);
        if ($order['delivery_fee'] > 0) {
            $order['actual_discount_money_info'] = round_number($order['actual_discount_money']+$order['delivery_fee'],2);
        } else {
            $order['actual_discount_money_info'] = $order['actual_discount_money'];
        }


        foreach ($order['order_detail'] as &$value) {
            $goods_log = M('Village_group_order_log')->where(array('deliver_status'=>array('gt',0),'order_id'=>$_GET['order_id'],'goods_id'=>array('in',array(0,$value['goods_id']))))->select();
            $status_arr = array();
            foreach ($goods_log as $key => $_log) {
                if (!in_array($_log['deliver_status'], $status_arr)) {
                    $status_arr[] = $_log['deliver_status'];
                }else{
                    unset($goods_log[$key]);
                }
            }
            $value['goods_log'] = array(array('status'=>0,'dateline'=>$value['create_time']));
            $goods_log  && $value['goods_log'] = array_merge($value['goods_log'],$goods_log);
            //区域售价加价信息
            if ($value['area_price']>0) {
                $value['price'] = $value['area_price'];
            }
            $value['increase_price'] = $value['increase_price']>0?floatval($value['increase_price']):0;
            $value['price'] +=$value['increase_price'];
        }

		// 处理周期购信息获取
		if ($order['village_group_type'] && $order['village_group_type']==1){
			$cycle_order_detail=M('Village_group_order_detail')->where(array('order_id' => $_GET['order_id']))->select();
			$goods_all_log = array();
			if ($cycle_order_detail) {
				foreach ($cycle_order_detail as $val) {
					$goods_all_log[$val['id']] = M('Village_group_order_log')->where(array('deliver_status'=>array('gt',0),'order_id'=>$_GET['order_id'],'goods_id'=>$val['goods_id'],'date_num'=>$val['date_num']))->select();

					$status_arr = array();
					foreach ($goods_all_log[$val['id']] as $key => $_log) {
						if (!in_array($_log['deliver_status'], $status_arr)) {
							$status_arr[] = $_log['deliver_status'];
						}else{
							unset($goods_all_log[$val['id']][$key]);
						}
					}
				}
			}
			$cycle_order_count=M('Village_group_order_detail')->where(array('order_id' => $_GET['order_id'],'status'=>array('in', array(1,2,3,4,5))))->count();
			$this->assign('goods_all_log',$goods_all_log);
			$this->assign('cycle_order_count',$cycle_order_count);
			$this->assign('cycle_order_detail',$cycle_order_detail);
		}
        $this->assign('order',$order);
        $this->display();
    }

	public function reward(){
		$this->check_login();
		$condition_user_money_list['uid'] = $this->user_session['uid'];
		$condition_user_money_list['_string'] =" `desc` like '%拼团验证消费%' ";

		$money = D('User_money_list')->where($condition_user_money_list)->sum('money');
		$this->assign('money',$money);
		$this->display();
	}

	public function rewardDetail(){
		$this->check_login();
		$where['pigcms_id'] = $_GET['reward_id'];
		$detail = M('User_money_list')->where($where)->find();
		if ($detail && $detail['uid']) {
			$user_info = M('User')->where(array('uid' => $detail['uid']))->field('avatar, nickname')->find();
			$this->assign('user_info', $user_info);
		}
		$this->assign($detail);
		$this->display();
	}
	//团长或者个人页面

	public function my(){
		redirect($this->config['site_url']."/packapp/community_group/index.html#/my");
		$category = D('Village_group')->get_category();
		$this->assign('category',$category);
		if(empty($this->user_session)){
			if($this->is_app_browser){
				$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
				$redirect_url = U('Login/index',$location_param);
			}else{
				$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
				$redirect_url = U('Login/index',$location_param);
			}
			$this->assign('redirect_url',$redirect_url);

			$this->display('my_login');
			die;
		}
		$my_header = D('Village_group')->get_header_info($this->user_session['uid']);

        //	平台优惠券
        $coupon_list = D('System_coupon')->get_user_coupon_list($this->user_session['uid'],$this->user_session['phone'],1);
        $coupon_number	=	count($coupon_list);
        $this->assign('coupon_number',$coupon_number);

		if(!$my_header['error_code']){
			$my_header = $my_header['msg'];
			$this->assign('header',$my_header);
			$lat = $my_header['pick_address']['lat'];
			$long = $my_header['pick_address']['long'];

			if ($my_header['pick_address']) {
				if ($my_header['pick_address']['province_id']>0 || $my_header['pick_address']['city_id']>0 || $my_header['pick_address']['area_id']>0){
					$sale_price = D('Village_group_order')->sum_header_sale_num($my_header['id']);
					// 如果存在
					$province_id = $my_header['pick_address']['province_id'];
					$city_id = $my_header['pick_address']['city_id'];
					$area_id = $my_header['pick_address']['area_id'];
					$area_level = array();
					if ($area_id > 0 && $sale_price>0) {
						$area_level = M('Village_group_leader_area_level')
							->where(array('city_id' => $area_id, 'min_sale_num' => array('elt', $sale_price), 'max_sale_num' => array('egt', $sale_price)))
							->find();
						if (!$area_level && $sale_price>0) {
							$area_level = M('Village_group_leader_area_level')
								->where(array('city_id' => $area_id, 'min_sale_num' => array('elt', $sale_price)))
								->order('level DESC')
								->find();
						}
					}
					if ($city_id > 0 && $sale_price>0 && empty($area_level)) {
						$area_level = M('Village_group_leader_area_level')
							->where(array('city_id' => $city_id, 'min_sale_num' => array('elt', $sale_price), 'max_sale_num' => array('egt', $sale_price)))
							->find();
						if (!$area_level && $sale_price>0) {
							$area_level = M('Village_group_leader_area_level')
								->where(array('city_id' => $city_id, 'min_sale_num' => array('elt', $sale_price)))
								->order('level DESC')
								->find();
						}
					}
					if ($province_id > 0 && $sale_price>0 && empty($area_level)) {
						$area_level = M('Village_group_leader_area_level')
							->where(array('city_id' => $province_id, 'min_sale_num' => array('elt', $sale_price), 'max_sale_num' => array('egt', $sale_price)))
							->find();
						if (!$area_level && $sale_price>0) {
							$area_level = M('Village_group_leader_area_level')
								->where(array('city_id' => $province_id, 'min_sale_num' => array('elt', $sale_price)))
								->order('level DESC')
								->find();
						}
					}
					$this->assign('area_level',$area_level);

				}
			}
			if ($lat>0 && $long>0) {
				$now_area =  D('Area')->cityMatching($lat,$long);

				$this->assign('now_area',$now_area);
			}
		}
		//得到所有城市并以城市首拼排序
		$database_area = D('Area');
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

		$code = D('Village_group')->get_code($this->user_session['uid']);
		$this->assign('code',str_split($code));

		$now_user = D('User')->get_user($this->user_session['uid']);
		$level = M(_view('User_level'))->getField('level,lname,icon');
		$now_user['lname'] = $level[$now_user['level']]['lname'];
		$now_user['icon'] = $level[$now_user['level']]['icon'];
        if (!$now_user['avatar']) {
            $now_user['avatar'] = $this->config['site_url'] . '/static/qrcode/wxapp/avatar.png';
        }
//		var_dump($now_user);
		$this->assign('now_user',$now_user);
		// 获取用户等级及相关信息

		$this->display();
	}

	public function editNickname(){
		if(IS_POST){
			M('Village_group_header')->where(array('uid'=>$this->user_session['uid']))->save($_POST);
			$this->success('保存成功',U('my'));
		}else{
			$my_header = D('Village_group')->get_header_info($this->user_session['uid']);
			$this->assign('header',$my_header['msg']);
			$this->display();
		}
	}

	// 编辑修改 团长地址和取货点信息
	public function editHeaderAddress(){
		$my_header = D('Village_group')->get_header_info($this->user_session['uid']);
		if(IS_POST){
			if(!$my_header['error_code']){
				$this->assign('header',$my_header['msg']);
			} else {
				$this->error_tips('该团长信息不存在');
			}
			$save = $header_save = array();
			if ($_POST['long'] && $_POST['lat']) {
				$save = array(
					'long' => trim($_POST['long']),
					'lat' => trim($_POST['lat']),
				);
			}
			if ($_POST['province_id']>0) {
				$save = array(
					'province_id' => intval($_POST['province_id'])
				);
			}
			if ($_POST['city_id']>0) {
				$save = array(
					'city_id' => intval($_POST['city_id'])
				);
			}
			if ($_POST['area_id']>0) {
				$save = array(
					'area_id' => intval($_POST['area_id'])
				);
			}
			if ($_POST['address']) {
				$save = array(
					'pick_addr' => trim($_POST['address'])
				);
				$header_save = array(
					'address' => trim($_POST['address'])
				);
			}
			if ($header_save) {
			   $header_id = M('Village_group_header')->where(array('uid'=>$this->user_session['uid']))->save($header_save);
			}
			if ($save) {
			   $pick_id = M('Village_group_pick_address')->where(array('header_uid'=>$my_header['msg']['id']))->save($save);
			}
			if ($header_id && $pick_id) {
				$this->success('修改成功',U('my'));
			} else {
			    $this->error('修改失败');
			}
		}else{
			$this->assign('header',$my_header['msg']);
			$this->display();
		}
	}


	public function consumer_code(){
		$this->check_login();
		import('@.ORG.phpqrcode');
		$code = D('Village_group')->get_code($this->user_session['uid']);
		QRcode::png($code,false,2,8,2);

	}



	public function apply(){
		$this->check_login();

		if(IS_POST){

			$_POST['status']=3;
			$_POST['uid'] = $this->user_session['uid'];
			// $_POST['name'] = $this->user_session['nickname'];
			$_POST['last_time'] = $_SERVER['REQUEST_TIME'];

			$condition['status']=array('in', array(1,3));
			$condition['uid']= $this->user_session['uid'];

			if(M('Village_group_header')->where($condition)->find()){
				$this->success('你已经申请过了，请耐心等待');
			}
			if($res = M('Village_group_header')->add($_POST)){

				$this->success('申请成功，请等待平台审核',U('my'));
			}
		}else{
            $condition['status']=array('in', array(1,3));
            $condition['uid']= $this->user_session['uid'];

            if($info = M('Village_group_header')->where($condition)->find()){
                if ($info['status']==3) {
                    $this->success_tips('你已经申请过了，请等待平台审核',U('my'));
                } else {
                    $this->success_tips('你的申请已经通过了',U('my'));
                }
            }
			$this->display();
		}
	}

	public function cart(){
		$category = D('Village_group')->get_category();

		$this->assign('category',$category);

		$this->display();
	}

	public function myOrder(){
		if(empty($this->user_session)){
			if($this->is_app_browser){
				$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
				$this->error_tips('请先进行登录！',U('Login/index',$location_param));
			}else{
				$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
				redirect(U('Login/index',$location_param));
			}
		}
		
        if (isset($_GET['status']) && $_GET['status'] == '2,3') {
            $_GET['status'] = '2,3,13';
            $this->redirect('Village_group/myOrder', $_GET);
        }
		$this->display();
	}

	//确认订单
	public function confirmOrder(){
		//guoxiansen 18356093564   1358676
		$now_user = D('User')->get_user($this->user_session['uid']);
		if(empty($now_user["phone"])){
			$this->error_tips('尊敬的用户，为了提供更好的服务，请您在消费之前先绑定手机号码！',U('My/bind_user',array('referer'=>urlencode(U('confirmOrder',$_GET)))));
		}
		$order  = D('Village_group_order')->order_detail($_GET['order_id']);
		$header = D('Village_group')->get_header_info(0,$order['start_uid'],true);
		$header  =$header['msg'];
		//用户信息
		$now_user = D('User')->get_user($this->user_session['uid']);

		if($order['status']==0 && $order['add_time']+1800<time()){
			M('Village_group_order')->where(array('order_id'=>$_GET['order_id']))->save(array('status'=>10,'last_time'=>time()));
			D("System_order")->save_order_status('village_group', $_GET['order_id'], 5);
		}
		$order['pay_type_txt'] = D('Pay')->get_pay_name($order['pay_type'],1,1);

		$type_ = $_GET['type'] ? $_GET['type'] : 'village_group';
		// 优惠的金额
		$discount_money = 0;
		// 重新计算原来金额
		$total_money_ = 0;
		// 等级优惠后金额
		$total_money = 0;
		// 优惠详情
		$discount_detail = array();
		if ($order['order_detail']) {
			// 获取平台用户等级设置
			$user_= M('User')->where(array('uid'=>$this->user_session['uid']))->find();
			$this->user_session['level'] = $user_['level'];
			$m_village_group_order_detail = M('Village_group_order_detail');
			if(!empty($user_) && isset($user_['level'])){
				$level_off_system = M(_view('User_level'))->where(array('level' => $user_['level']))->find();
				$this->assign('level_off_system',$level_off_system);
				$is_discount = 0;
				$goods_info = $order['goods'];
				foreach ($order['order_detail'] as &$val) {
					// var_dump($val['increase_price']);
					$area_price = $val['goods_bind_area']['price']>0?$val['goods_bind_area']['price']:0;
					$level_info = array();
					$level_info['order_id']=$order['order_id'];
					// 商品如果自定义了等级优惠取商品的，没有定义默认取平台的
					$leveloff=!empty($goods_info[$val['goods_id']]['leveloff']) ? unserialize($goods_info[$val['goods_id']]['leveloff']) : '';
					if ($leveloff) {
						$level_off=$leveloff[$user_['level']];
					} else {
						$level_off_system['vv']=$level_off_system['boon'];
						$level_off=$level_off_system;
					}
					$val['level_info'] = $level_off;
					//区域售价加价信息
					if ($val['goods_bind_area']) {
						$val['price'] = $area_price;
						if ($area_price>0 && $area_price != $val['area_price']) {
							$m_village_group_order_detail->where(array('id' => $val['id']))->setField('area_price', $area_price);
						}
						if ($val['goods_bind_area']['is_add_price']==0) { // 没有打开加价
							$val['increase_price'] = 0;
						}else{
							$val['increase_price'] = floatval($val['increase_price']);
						}
					}

					if($level_off['type']==1 && $level_off['vv'] > 0 && $level_off['vv'] <= 100){
						$level_discount = $val['price']*(1-$level_off['vv']/100);
						if ($level_discount) {
//							$is_discount = 1;
							$val['is_discount'] = 1;
						}
						$level_discount = round_number($level_discount,2);
						$val['discount_price'] = round_number($val['price']-$level_discount,2); // 优惠后的价格
						// 团长加价参与优惠
						if ($val['increase_price'] && $val['increase_price']>0) {
							$level_discount_increase = $val['increase_price']*(1-$level_off['vv']/100);
							$val['discount_price'] = $val['discount_price']  + round_number($val['increase_price'] - $level_discount_increase,2);
							$level_info['level_discount_increase'] = round_number($level_discount_increase,2);
							$level_info['increase_price'] = $val['increase_price'];
						}
						$val['old_money'] = round_number(($val['price']+$val['increase_price'])*$val['num'],2); // 原价计算总金额
						$val['total_money'] = round_number($val['discount_price']*$val['num'],2);
						$discount_money = $discount_money + round_number($val['old_money']-$val['total_money'],2);
						// 计算原金额
						$total_money_ = $total_money_ + $val['old_money'];
						// 处理折扣详情
						$level_info['level_discount'] = round_number($level_discount,2);
						$level_info['offstr']='折扣'.round_number($level_off['vv']/10,2).'折';
						$level_info['type'] = 1;
						$level_info['discount']=$level_off['vv'].'%';
						$level_info['price']=$val['price'];
						$level_info['discount_price'] = $val['discount_price'];
						$level_info['num']=$val['num'];
						$level_info['goods_id']=$val['goods_id'];
						$level_info['area_price']=$area_price;
						$discount_detail[] = $level_info;
					}elseif($level_off['type']==2 && $level_off['vv'] <= ($val['price']+$val['increase_price'])){
						$level_discount = $level_off['vv'];
						if ($level_discount) {
//							$is_discount = 1;
							$val['is_discount'] = 1;
						}
						$val['discount_price'] = round_number($val['price']-$level_discount,2); // 优惠后的价格
						// 团长加价参与优惠
						if ($val['increase_price'] && $val['increase_price']>0) {
							if ($val['discount_price']>=0) {
								$level_discount_increase = 0;
							} else {
								$level_discount_increase = -($val['discount_price']);
							}
							$val['discount_price'] = $val['discount_price'] + $val['increase_price'];
							$level_info['level_discount_increase'] = $level_discount_increase;
							$level_info['increase_price'] = $val['increase_price'];
						}
						$val['old_money'] = round_number(($val['price']+$val['increase_price'])*$val['num'],2);
						$val['total_money'] = round_number($val['discount_price']*$val['num'],2);
						$discount_money = $discount_money + round_number($val['old_money']-$val['total_money'],2);
						// 计算原金额
						$total_money_ = $total_money_ + $val['old_money'];
						// 处理立减详情
						$level_info['level_discount'] = round_number($level_discount,2);
						$level_info['offstr']='每份立减'.$level_off['vv'].C('config.Currency_txt');
						$level_info['type'] = 3;
						$level_info['discount']=$level_off['vv'];
						$level_info['price']=$val['price'];
						$level_info['discount_price'] = $val['discount_price'];
						$level_info['num']=$val['num'];
						$level_info['goods_id']=$val['goods_id'];
						$level_info['area_price']=$area_price;
						$discount_detail[] = $level_info;
					}else{
						$val['old_money'] = round_number(($val['price']+$val['increase_price'])*$val['num'],2);
						// 计算原金额
						$total_money_ = $total_money_ + $val['old_money'];
					}
					$val['price'] = $val['price']+$val['increase_price'];
				}
				$this->assign('is_discount',$is_discount);
			}
			$vip_discount_money = $discount_money = round_number($discount_money,2);
			if ($total_money_ != $order['total_money']) {
				$total_money = round_number($total_money_-$discount_money,2);
			} else {
				$total_money = round_number($order['total_money']-$discount_money,2);
			}

			//平台优惠券
			if ($this->is_app_browser) {
				$platform = 'app';
			} else if ($this->is_weixin_browser) {
				$platform = 'weixin';
			} else {
				$platform = 'wap';
			}
			if ($_GET['unsys_coupon']!=1) {
				if (empty($_GET['sysc_id'])) {

					$old_money = 0;
					if ($total_money != $order['total_money']) {
						$old_money = $order['total_money'];
						$order['total_money'] = $total_money;
					}
					$now_coupon = D('System_coupon')->get_noworder_coupon_list($order, $type_, $this->user_session['phone'], $this->user_session['uid'], $platform);
					$system_coupon = reset($now_coupon);

					if ($old_money>0) {
						$order['total_money'] = $old_money;
					}
				} else {
					$system_coupon = D('System_coupon')->get_coupon_info($_GET['sysc_id']);
					if($system_coupon['is_discount']==1){
						$system_coupon['discount_money'] =round_number( $total_money * (100-$system_coupon['discount_value']*10)/100,2);
					}else{
						$system_coupon['discount_money'] = $system_coupon['discount'];
					}
				}
			}

			if (!empty($system_coupon)) {
				$system_coupon['coupon_url_param'] = array('sysc_id' => $system_coupon['id'], 'order_id' => $order['order_id'], 'type' => $type_,'discount_type'=>'coupon');

				if($_GET['discount_type']=='coupon'){
					$system_coupon['coupon_url_param']['discount_type'] = 'coupon';
				}else{
					unset($system_coupon['coupon_url_param']['discount_type']);
				}

				$_SESSION['sysc_id'] = $system_coupon['id'];

				$system_coupon['discount'] = $system_coupon['discount_money'];
				if ($total_money > $system_coupon['order_money']) {
					$level_info = array();
					if ($system_coupon['is_discount'] == 1) {
						$level_info['type'] = 4;
						$level_info['offstr']='订单满足'.$system_coupon['order_money'].C('config.Currency_txt').'享受折扣'.$system_coupon['discount_value'];
						$level_info['discount']=$system_coupon['discount_value'];
					} else {
						$level_info['type'] = 2;
						$level_info['offstr']='订单满足'.$system_coupon['order_money'].C('config.Currency_txt').'享受满减'.$system_coupon['discount'];
						$level_info['discount']=$system_coupon['discount'];
					}
					$total_money = $total_money - $system_coupon['discount_money'];
					$total_money = round_number($total_money,2);
					$discount_money = $discount_money + $system_coupon['discount_money'];
					$discount_money = round_number($discount_money,2);
					// 处理优惠券详情
					$level_info['level_discount'] = round_number($system_coupon['discount_money'],2);
					$level_info['money'] = $system_coupon['order_money'];
					$level_info['order_id']=$order['order_id'];
					$discount_detail[] = $level_info;
				}
				$this->assign('system_coupon', $system_coupon);

				$order['total_money'] -= empty($system_coupon['discount']) ? 0 : $system_coupon['discount'];

			} else {
				$system_coupon['coupon_url_param'] = array();
			}
			$coupon_url = $system_coupon['coupon_url_param'];
			if($_GET['unsys_coupon']&&!empty($coupon_url)){
				unset($_SESSION['sysc_id']);
				$coupon_url['unsys_coupon']=1;
			}
			$this->assign('coupon_url', $coupon_url);
			// 写入订单数据
			$save_info = array();
			// 平台优惠券id
			if ($system_coupon && $order['coupon_id'] != $system_coupon['id']) {
				$save_info['coupon_id'] = $system_coupon['id']?$system_coupon['id']:0;
			} elseif (!$system_coupon && $order['coupon_id']>0) {
				$save_info['coupon_id'] = 0;
			}
			// 平台优惠券优惠金额
			if ($system_coupon && $order['coupon_price'] != $system_coupon['discount_money']) {
				$save_info['coupon_price'] = $system_coupon['discount_money']?$system_coupon['discount_money']:0;
			} elseif (!$system_coupon && $order['coupon_price']>0) {
				$save_info['coupon_price'] = 0;
			}
			// 用户等级
			if ($user_ && $order['user_level'] != $user_['level']) {
				$save_info['user_level'] = $user_['level'];
			}
			// 用户等级优惠金额
			if ($order['vip_discount_money'] != $vip_discount_money) {
				$save_info['vip_discount_money'] = $vip_discount_money;
			}
			// 优惠后总金额
			if ($order['discount_price'] != $total_money) {
				$save_info['discount_price'] = $total_money;
			}
			// 优惠详情
			if ($discount_detail) {
				$discount_detail = serialize($discount_detail);
				if ($order['discount_detail'] != $discount_detail) {
					$save_info['discount_detail'] = $discount_detail;
				}
			}
			// 重新计算金额，不同，改写金额数据
			if ($total_money_ != $order['total_money']) {
				$save_info['total_money'] = round_number($total_money_,2);
			}
			if ($save_info) {
				M('village_group_order')->where(array('order_id' => $order['order_id']))->data($save_info)->save();
			}



			//使用积分
			$score_can_use_count=0;
			$score_deducte=0;
			$user_score_use_condition = $this->config['user_score_use_condition'];
			$user_score_max_use = D('Percent_rate')->get_max_core_use(-1, $type_,$total_money);//不同业务不同积分

			$user_score_use_percent = (float)$this->config['user_score_use_percent'];
			$score_max_deducte = bcdiv($user_score_max_use, $user_score_use_percent, 2);

			if ($user_score_use_percent > 0 && $score_max_deducte > 0 && $user_score_use_condition > 0 && $now_user['score_count'] > 0) {   //如果设置没有错误
				$total_ = $total_money;

				if ($total_ >= $user_score_use_condition) {             //判断是否够使用积分的条件，即这个订单的总额是否大于规定的金额
					if ($total_ > $score_max_deducte) {                    //判断积分最大抵扣金额是否比这个订单的总额大
						$score_can_use_count = (int)($now_user['score_count'] > $user_score_max_use ? $user_score_max_use : $now_user['score_count']);//如果用户积分大于每个订单的积分可使用数 则按可使用数算
						$score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
						$score_deducte = $score_deducte > $total_ ? $total_ : $score_deducte;
					} else {
						$score_can_use_count = round_number($total_ * $user_score_use_percent,2) > (int)$now_user['score_count'] ? (int)$now_user['score_count'] : round_number($total_ * $user_score_use_percent,2);
						$score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
						$score_deducte = $score_deducte > $total_ ? $total_ : $score_deducte;
					}
				}
			}
			//可用积分
			$total_money = $total_money - $score_deducte; // 实际需要支付的金额 扣除积分抵扣金额
			$this->assign('score_can_use_count', $score_can_use_count);
			$this->assign('score_deducte', round_number($score_deducte,2));
			$this->assign('score_count', $now_user['score_count']);
		}
        $order['actual_total_money_info'] = $order['actual_total_money'] = $total_money;
        $order['actual_discount_money'] = $discount_money;

		if ($this->config['village_group_delivery_switch']==1 && $order['village_group_type'] != 1) {
            // 配送相关处理  暂时只存在平台配送
            $deliver_info = D('Village_group')->village_deliver_fee();

            $address_id = isset($_GET['adress_id']) ? intval($_GET['adress_id']):0;
            $userLocationLat = $header['pick_address']['lat'];
            $userLocationLong = $header['pick_address']['long'];

            $user_address_temp = D('User_adress')->field(true)->where(array('uid' => $this->user_session['uid']))->order('`default` DESC,`adress_id` DESC')->select();
            $deliver_info['address_count'] = count($user_address_temp);
            $user_adress = array();
            $distance_array = array();
            $user_address_list = array();
            $is_adress = false;
            foreach ($user_address_temp as $address) {
                $address['distance'] = getDistance($address['latitude'], $address['longitude'], $userLocationLat, $userLocationLong);
                if ($header['pick_address']['delivery_range_type'] == 0) {
                    $distance = getDistance($address['latitude'], $address['longitude'], $userLocationLat, $userLocationLong);
                    $distance = $distance / 1000;
                    if ($header['pick_address']['delivery_radius'] < $distance) {
                        continue;
                    }
                }
                $is_adress = true;

                if ($address_id == $address['adress_id']) {
                    $user_adress = $address;
                    break;
                }

                if(empty($address_id) && $address['default']){
                    $user_adress = $address;
                    break;
                }
                $distance_array[] = $address['distance'];
                $user_address_list[] = $address;
            }
            $this->assign('is_adress', $is_adress);

            if (empty($user_adress) && $distance_array && $user_address_list) {
                array_multisort($distance_array, SORT_ASC, $user_address_list);
                $user_adress = $user_address_list[0];
            }
            if ($user_adress) {
                $province = D('Area')->get_area_by_areaId($user_adress['province'],false);
                $user_adress['province_txt'] = $province['area_name'];

                $city = D('Area')->get_area_by_areaId($user_adress['city'],false);
                $user_adress['city_txt'] = $city['area_name'];

                $area = D('Area')->get_area_by_areaId($user_adress['area'],false);
                $user_adress['area_txt'] = $area['area_name'];
            }
            $this->assign('user_adress', $user_adress);

            //计算配送费
            if ($user_adress) {
                $distance = 0;
                if (C('config.is_riding_distance')) {
                    import('@.ORG.longlat');
                    $longlat_class = new longlat();
                    $distance = $longlat_class->getRidingDistance($user_adress['latitude'], $user_adress['longitude'], $userLocationLat, $userLocationLong);
                    if($distance == -1) $this->error_tips('请求不到您的具体定位，请重试！');
                }
                $distance || $distance = getDistance($user_adress['latitude'], $user_adress['longitude'], $userLocationLat, $userLocationLong);

                $distance = $distance / 1000;
                $pass_distance = $distance > $deliver_info['basic_distance'] ? floatval($distance - $deliver_info['basic_distance']) : 0;
                $deliver_info['delivery_fee'] += round($pass_distance * $deliver_info['per_km_price'], 2);
                $deliver_info['delivery_fee_old'] = $deliver_info['delivery_fee'];
                $deliver_info['delivery_fee'] = $deliver_info['delivery_fee'] - $deliver_info['delivery_fee_reduce'];
                $deliver_info['delivery_fee'] = $deliver_info['delivery_fee'] > 0 ? $deliver_info['delivery_fee'] : 0;

                $pass_distance = $distance > $deliver_info['basic_distance2'] ? floatval($distance - $deliver_info['basic_distance2']) : 0;
                $deliver_info['delivery_fee2'] += round($pass_distance * $deliver_info['per_km_price2'], 2);
                $deliver_info['delivery_fee2_old'] = $deliver_info['delivery_fee2'];
                $deliver_info['delivery_fee2'] = $deliver_info['delivery_fee2'] - $deliver_info['delivery_fee_reduce'];
                $deliver_info['delivery_fee2'] = $deliver_info['delivery_fee2'] > 0 ? $deliver_info['delivery_fee2'] : 0;

                $pass_distance = $distance > $deliver_info['basic_distance3'] ? floatval($distance - $deliver_info['basic_distance3']) : 0;
                $deliver_info['delivery_fee3'] += round($pass_distance * $deliver_info['per_km_price3'], 2);
                $deliver_info['delivery_fee3_old'] = $deliver_info['delivery_fee3'];
                $deliver_info['delivery_fee3'] = $deliver_info['delivery_fee3'] - $deliver_info['delivery_fee_reduce'];
                $deliver_info['delivery_fee3'] = $deliver_info['delivery_fee3'] > 0 ? $deliver_info['delivery_fee3'] : 0;

                switch (intval($this->config['count_freight_charge_method'])) {
                    case 0:
                        break;
                    case 1:
                        $deliver_info['delivery_fee'] = ceil($deliver_info['delivery_fee'] * 10) / 10;
                        $deliver_info['delivery_fee2'] = ceil($deliver_info['delivery_fee2'] * 10) / 10;
                        $deliver_info['delivery_fee3'] = ceil($deliver_info['delivery_fee3'] * 10) / 10;
                        $deliver_info['delivery_fee_old'] = ceil($deliver_info['delivery_fee_old'] * 10) / 10;
                        $deliver_info['delivery_fee2_old'] = ceil($deliver_info['delivery_fee2_old'] * 10) / 10;
                        $deliver_info['delivery_fee3_old'] = ceil($deliver_info['delivery_fee3_old'] * 10) / 10;
                        break;
                    case 2:
                        $deliver_info['delivery_fee'] = floor($deliver_info['delivery_fee'] * 10) / 10;
                        $deliver_info['delivery_fee2'] = floor($deliver_info['delivery_fee2'] * 10) / 10;
                        $deliver_info['delivery_fee3'] = floor($deliver_info['delivery_fee3'] * 10) / 10;
                        $deliver_info['delivery_fee_old'] = floor($deliver_info['delivery_fee_old'] * 10) / 10;
                        $deliver_info['delivery_fee2_old'] = floor($deliver_info['delivery_fee2_old'] * 10) / 10;
                        $deliver_info['delivery_fee3_old'] = floor($deliver_info['delivery_fee3_old'] * 10) / 10;

                        break;
                    case 3:
                        $deliver_info['delivery_fee'] = round($deliver_info['delivery_fee'] * 10) / 10;
                        $deliver_info['delivery_fee2'] = round($deliver_info['delivery_fee2'] * 10) / 10;
                        $deliver_info['delivery_fee3'] = round($deliver_info['delivery_fee3'] * 10) / 10;
                        $deliver_info['delivery_fee_old'] = round($deliver_info['delivery_fee_old'] * 10) / 10;
                        $deliver_info['delivery_fee2_old'] = round($deliver_info['delivery_fee2_old'] * 10) / 10;
                        $deliver_info['delivery_fee3_old'] = round($deliver_info['delivery_fee3_old'] * 10) / 10;
                        break;
                    case 4:
                        $deliver_info['delivery_fee'] = ceil($deliver_info['delivery_fee']);
                        $deliver_info['delivery_fee2'] = ceil($deliver_info['delivery_fee2']);
                        $deliver_info['delivery_fee3'] = ceil($deliver_info['delivery_fee3']);
                        $deliver_info['delivery_fee_old'] = ceil($deliver_info['delivery_fee_old']);
                        $deliver_info['delivery_fee2_old'] = ceil($deliver_info['delivery_fee2_old']);
                        $deliver_info['delivery_fee3_old'] = ceil($deliver_info['delivery_fee3_old']);
                        break;
                    case 5:
                        $deliver_info['delivery_fee'] = floor($deliver_info['delivery_fee']);
                        $deliver_info['delivery_fee2'] = floor($deliver_info['delivery_fee2']);
                        $deliver_info['delivery_fee3'] = floor($deliver_info['delivery_fee3']);
                        $deliver_info['delivery_fee_old'] = floor($deliver_info['delivery_fee_old']);
                        $deliver_info['delivery_fee2_old'] = floor($deliver_info['delivery_fee2_old']);
                        $deliver_info['delivery_fee3_old'] = floor($deliver_info['delivery_fee3_old']);
                        break;
                    case 6:
                        $deliver_info['delivery_fee'] = round($deliver_info['delivery_fee']);
                        $deliver_info['delivery_fee2'] = round($deliver_info['delivery_fee2']);
                        $deliver_info['delivery_fee3'] = round($deliver_info['delivery_fee3']);
                        $deliver_info['delivery_fee_old'] = round($deliver_info['delivery_fee_old']);
                        $deliver_info['delivery_fee2_old'] = round($deliver_info['delivery_fee2_old']);
                        $deliver_info['delivery_fee3_old'] = round($deliver_info['delivery_fee3_old']);
                        break;
                }
            }
            $paramTime = time();
            if ($header['pick_address'] && $header['pick_address']['s_send_time']) {
                $paramTime += intval($header['pick_address']['s_send_time'])*60;
            }
            $date_list = D('Village_group')->getSelectTime($deliver_info,$paramTime);
            foreach ($date_list as $key => $rowset) {
                $temp = array();
                asort($rowset);
                foreach ($rowset as $rv) {
                    $temp[] = $rv;
                }
                if ($key == date('Y-m-d')) {
                    $d_list[] = array('ymd' => $key, 'show_date' => '今天', 'date_list' => $temp);
                } else if($key == date('Y-m-d',time()+86400)){
                    $d_list[] = array('ymd' => $key, 'show_date' => '明天', 'date_list' => $temp);
                } else if($key == date('Y-m-d',time()+86400*2)){
                    $d_list[] = array('ymd' => $key, 'show_date' => '后天', 'date_list' => $temp);
                } else {
                    $d_list[] = array('ymd' => $key, 'show_date' => date('m月d日', strtotime($key)), 'date_list' => $temp);
                }
            }
            if (empty($d_list)) {
                $d_list = array();
            }
            $this->assign('dates', $d_list);
            if ($d_list && $d_list[0]['date_list'] && $d_list[0]['date_list'][0] && $d_list[0]['date_list'][0]['delivery_fee_old']) {
                $first_fee = $d_list[0]['date_list'][0]['delivery_fee_old'];
                $order['actual_total_money_info'] += $first_fee;
            } else {
                $first_fee = 0;
            }
            $this->assign('first_fee', $first_fee);


            $this->assign('order_id',$_GET['order_id']);
            $this->assign('deliver_info',$deliver_info);
        }


        if (C('config.village_group_hide_detail')) {
            $header['phone'] = '';
            $header['name'] = str_replace_name($header['name']);
            if (mb_strlen($header['pick_address']['pick_addr'],'UTF8') > 3) {
                if (mb_strlen($header['pick_address']['pick_addr'],'UTF8') == 4) {
                    $header['pick_address']['pick_addr'] =  mb_substr($header['pick_address']['pick_addr'],0,-3).'****';
                }else{
                    $header['pick_address']['pick_addr'] =  mb_substr($header['pick_address']['pick_addr'],0,-4).'****';
                }
            }
        }

		$this->assign('header',$header);
		$this->assign('order',$order);
		$this->display();
	}
	// 提交订单
	public function confirm_go_pay() {
		$use_score = $_POST['use_score']?$_POST['use_score']:0;
		$type_ = $_POST['type'] ? $_POST['type'] : 'village_group';
		$order  = D('Village_group_order')->order_detail($_POST['order_id']);
		$total_money = $order['discount_price']>=0?$order['discount_price']:$order['total_money'];
		$total_money = round_number($total_money,2);
		//用户信息
		$now_user = D('User')->get_user($this->user_session['uid']);
		//使用积分
		$score_can_use_count=0;
		$score_deducte=0;
		if ($use_score>=1) {
			$user_score_use_condition = $this->config['user_score_use_condition'];
			$user_score_max_use = D('Percent_rate')->get_max_core_use(-1, $type_,$total_money);//不同业务不同积分

			$user_score_use_percent = (float)$this->config['user_score_use_percent'];
			$score_max_deducte = bcdiv($user_score_max_use, $user_score_use_percent, 2);

			if ($user_score_use_percent > 0 && $score_max_deducte > 0 && $user_score_use_condition > 0 && $now_user['score_count'] > 0) {   //如果设置没有错误
				$total_ = $total_money;

				if ($total_ >= $user_score_use_condition) {             //判断是否够使用积分的条件，即这个订单的总额是否大于规定的金额
					if ($total_ > $score_max_deducte) {                    //判断积分最大抵扣金额是否比这个订单的总额大
						$score_can_use_count = (int)($now_user['score_count'] > $user_score_max_use ? $user_score_max_use : $now_user['score_count']);//如果用户积分大于每个订单的积分可使用数 则按可使用数算
						$score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
						$score_deducte = $score_deducte > $total_ ? $total_ : $score_deducte;
					} else {
						$score_can_use_count = round_number($total_ * $user_score_use_percent,2) > (int)$now_user['score_count'] ? (int)$now_user['score_count'] : round_number($total_ * $user_score_use_percent,2);
						$score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
						$score_deducte = $score_deducte > $total_ ? $total_ : $score_deducte;
					}
				}
			}
			//可用积分
			$total_money = $total_money - $score_deducte; // 实际需要支付的金额 扣除积分抵扣金额
		}
		// 对应处理支付订单列表
		$plat_order_info = M('Plat_order')->where(array('business_id' => $order['order_id'], 'business_type' => $type_))->find();


		// 修改表格 pigcms_village_group_order 数据
		$village_order = array();
		// 如果是平台配送
        // 处理配送信息
        $is_pick_group = $_POST['is_pick_group']>0?$_POST['is_pick_group']:0;// 默认自提
        if ($is_pick_group>0 && $this->config['village_group_delivery_switch'] != 1 && $order['village_group_type'] != 1) {
            $this->error('平台关闭了配送功能！',U('confirmOrder',array('order_id'=>$order['order_id'],'time'=>time())));
        }
        if ($is_pick_group>0 && $order['village_group_type'] != 1) {
            $village_order['is_pick_group'] = $is_pick_group;
            $address_id = $_POST['address_id']>0?$_POST['address_id']:0;
			$phone = isset($_POST['ouserTel']) ? htmlspecialchars($_POST['ouserTel']) : '';
			$name = isset($_POST['ouserName']) ? htmlspecialchars($_POST['ouserName']) : '';
            $arrive_time = isset($_POST['oarrivalTime']) ? htmlspecialchars($_POST['oarrivalTime']) : 0;
            $arrive_date = isset($_POST['oarrivalDate']) ? htmlspecialchars($_POST['oarrivalDate']) : 0;
			$address = isset($_POST['ouserAddres']) ? htmlspecialchars($_POST['ouserAddres']) : '';;// 地址
            if ($address_id<=0) {
                $this->error('地址信息不存在！');
            } else {
                $header = D('Village_group')->get_header_info(0,$order['start_uid'],true);
                $header = $header['msg'];
                $user_address = D('User_adress')->field(true)->where(array('adress_id' => $address_id, 'uid' => $this->user_session['uid']))->find();
                $pick_address = $header['pick_address'];
                if ($user_address['longitude'] > 0 && $user_address['latitude'] > 0) {
                    if ($pick_address['delivery_range_type'] == 0) {
                        $distance = getDistance($user_address['latitude'], $user_address['longitude'], $pick_address['lat'], $pick_address['long']);
                        $delivery_radius = $pick_address['delivery_radius'] * 1000;
                        if ($distance > $delivery_radius) {
                            $this->error('您到团长的距离是' . $distance . '米,超过了' . $delivery_radius . '米的配送范围');
                        }
                    }
                } else {
                    $this->error('您选择的地址没有完善，请先编辑地址，点击“点击选择位置”进行完善', U('My/adress',array('buy_type' => 'village_group', 'order_id' => $order['order_id'], 'current_id'=>$address_id)));
                }
            }
            $village_order['username'] = $name;
            $village_order['userphone'] = $phone;
            $village_order['address'] = $address;
            $village_order['address_id'] = $address_id;
            $village_order['address_lat'] = $user_address['latitude'];
            $village_order['address_long'] = $user_address['longitude'];
            if ($arrive_date == 0) {
                $arrive_date = date('Y-m-d');
            }
            // 配送相关处理  暂时只存在平台配送
            $deliver_info = D('Village_group')->village_deliver_fee();

            $start_time = $deliver_info['village']['delivertime_start'];
            $stop_time = $deliver_info['village']['delivertime_stop'];
            $start_time2 = $deliver_info['village']['delivertime_start2'];
            $stop_time2 = $deliver_info['village']['delivertime_stop2'];
            $start_time3 = $deliver_info['village']['delivertime_start3'];
            $stop_time3 = $deliver_info['village']['delivertime_stop3'];
            if ($start_time == $stop_time && $start_time == '00:00:00') {
                $stop_time = '23:59:59';
            }

            $arrive_time = strtotime($arrive_date . $arrive_time);
            if ($arrive_time == 0) {
                $this->error_tips('请选择配送时间');
            }
            $village_group_delivery_base_time = C('config.village_group_delivery_base_time') ? intval(C('config.village_group_delivery_base_time')) : 30; //默认30分钟

            $village_order['expect_send_time'] = $arrive_time ? $arrive_time : time() + $village_group_delivery_base_time * 60;//无值取默认的期望送达时间

            //计算配送费
            $distance = 0;
            if (C('config.is_riding_distance')) {
                import('@.ORG.longlat');
                $longlat_class = new longlat();
                $distance = $longlat_class->getRidingDistance($user_address['latitude'], $user_address['longitude'],  $pick_address['lat'], $pick_address['long']);
                if($distance == -1) $this->error_tips('请求不到您的具体定位，请重试！');
            }
            $distance || $distance = getDistance($user_address['latitude'], $user_address['longitude'],  $pick_address['lat'], $pick_address['long']);
            $village_order['delivery_distance'] = $distance;
            $distance = $distance / 1000;

            $expect_send_time = strtotime(date('Y-m-d H:i:s', $village_order['expect_send_time']));
            if (D('Deliver_set')->getDeliverTime($expect_send_time, $start_time, $stop_time, 1)) {
                //时间段一
                $pass_distance = $distance > $deliver_info['basic_distance'] ? floatval($distance - $deliver_info['basic_distance']) : 0;
                $deliver_info['delivery_fee'] += round($pass_distance * $deliver_info['per_km_price'], 2);
                $deliver_info['delivery_fee'] = $deliver_info['delivery_fee'] - $deliver_info['delivery_fee_reduce'];
                $deliver_info['delivery_fee'] = $deliver_info['delivery_fee'] > 0 ? $deliver_info['delivery_fee'] : 0;
                $delivery_fee = $village_order['delivery_fee'] = $deliver_info['delivery_fee'];//运费
            } elseif (D('Deliver_set')->getDeliverTime($expect_send_time, $start_time2, $stop_time2, 2)) {
                //时间段二
                $pass_distance = $distance > $deliver_info['basic_distance2'] ? floatval($distance - $deliver_info['basic_distance2']) : 0;
                $deliver_info['delivery_fee2'] += round($pass_distance * $deliver_info['per_km_price2'], 2);
                $deliver_info['delivery_fee2'] = $deliver_info['delivery_fee2'] - $deliver_info['delivery_fee_reduce'];
                $deliver_info['delivery_fee2'] = $deliver_info['delivery_fee2'] > 0 ? $deliver_info['delivery_fee2'] : 0;
                $delivery_fee = $village_order['delivery_fee'] = $deliver_info['delivery_fee2'];//运费
            } elseif (D('Deliver_set')->getDeliverTime($expect_send_time, $start_time3, $stop_time3, 3)) {
                //时间段二
                $pass_distance = $distance > $deliver_info['basic_distance3'] ? floatval($distance - $deliver_info['basic_distance3']) : 0;
                $deliver_info['delivery_fee3'] += round($pass_distance * $deliver_info['per_km_price3'], 2);
                $deliver_info['delivery_fee3'] = $deliver_info['delivery_fee3'] - $deliver_info['delivery_fee_reduce'];
                $deliver_info['delivery_fee3'] = $deliver_info['delivery_fee3'] > 0 ? $deliver_info['delivery_fee3'] : 0;
                $delivery_fee = $village_order['delivery_fee'] = $deliver_info['delivery_fee3'];//运费
            }else {
                $this->error_tips('您选择的时间不在配送时间段内！');
            }

            switch (intval($this->config['count_freight_charge_method'])) {
                case 0:
                    break;
                case 1:
                    $delivery_fee = $village_order['delivery_fee'] = ceil($village_order['delivery_fee'] * 10) / 10;
                    break;
                case 2:
                    $delivery_fee = $village_order['delivery_fee'] = floor($village_order['delivery_fee'] * 10) / 10;
                    break;
                case 3:
                    $delivery_fee = $village_order['delivery_fee'] = round($village_order['delivery_fee'] * 10) / 10;
                    break;
                case 4:
                    $delivery_fee = $village_order['delivery_fee'] = ceil($village_order['delivery_fee']);
                    break;
                case 5:
                    $delivery_fee = $village_order['delivery_fee'] = floor($village_order['delivery_fee']);
                    break;
                case 6:
                    $delivery_fee = $village_order['delivery_fee'] = round($village_order['delivery_fee']);
                    break;
            }
            if ($village_order['delivery_fee']>0) {
                $total_money += $delivery_fee;
            }
            $village_order['village_group_type']=2;
        }


		// 修改表格 pigcms_plat_order 数据
		$save_info = array();
		// 实际支付金额
		if ($total_money>0 && $total_money != $plat_order_info['total_money']) {
			$save_info['total_money'] = $total_money;
		} elseif ($total_money<=0 && $plat_order_info['total_money']>0) {
			$save_info['total_money'] = 0;
		}
		// 积分使用
		if ($score_can_use_count>0 && $score_can_use_count != $order['score_used_count']) {
			$village_order['score_used_count'] = $score_can_use_count;
		} elseif ($score_can_use_count<=0) {
			$village_order['score_used_count'] = 0;
		}
		// 积分抵扣
		if ($score_deducte>0 && $score_deducte != $order['score_deducte']) {
			$village_order['score_deducte'] = $score_deducte;
		} elseif ($score_deducte <= 0) {
			$village_order['score_deducte'] = 0;
		}
		if ($save_info) {
			M('Plat_order')->where(array('business_id' => $order['order_id'], 'business_type' => $type_))->data($save_info)->save();
		}
        $village_order['note'] = $_POST['note']? $_POST['note']:'';//备注信息
        if ($village_order) {
			M('Village_group_order')->where(array('order_id' => $order['order_id']))->data($village_order)->save();
			if ($village_order['village_group_type']==2) {
                M('Village_group_order_detail')->where(array('order_id' => $order['order_id']))->setField('village_group_type',2);
            }
		}

        if ($order['order_id']>0) {
            D('Village_group_order')->clear_cart($order['order_id']);
        }

		$this->success(array('order_id'=>$order['order_id']));
	}


    public function checkTime($time, $start_time, $stop_time, $select)
    {
        $stime = strtotime(date('Y-m-d ' . $start_time));
        $etime = strtotime(date('Y-m-d ' . $stop_time));
        $next_stime = 0;
        $next_etime = 0;
        if ($etime < $stime) {
            $etime = strtotime(date('Y-m-d 23:59:59'));
            $next_stime = strtotime(date('Y-m-d'));
            $next_etime = strtotime(date('Y-m-d ' . $stop_time));
        }

        if ($stime <= $time && $time <= $etime) {
            return $select;
        }
        if ($next_stime <= $time && $time <= $next_etime) {
            return $select;
        }
        return 0;
    }

	public function orderDetail(){
		$this->check_login();
		$order  = D('Village_group_order')->order_detail($_GET['order_id']);
		if(empty($order)){
			$this->error_tips('当前订单不存在');
		}

		if($order['uid'] != $this->user_session['uid']){
			$this->error_tips('非法请求');
		}

		$header = D('Village_group')->get_header_info(0,$order['start_uid'],true);
		$header = $header['msg'];

		if($order['status']==0 && $order['add_time']+1800<time()){
			M('Village_group_order')->where(array('order_id'=>$_GET['order_id']))->save(array('status'=>10,'last_time'=>time()));
			$order['status'] = 10;
			D("System_order")->save_order_status('village_group', $_GET['order_id'], 5);
			$order['status_txt']  = D('Village_group')->order_status[$order['status']];
            if ($order['order_status']==2 && $order['is_pick_group']==1) {
                $order['status_txt'] = '团长收货 待配送';
            }
		}
		if ($order['discount_detail']) {
			$goods = $order['goods'];
			$discount_detail = unserialize($order['discount_detail']);
			$good_discount = array();
			foreach ($discount_detail as $val) {
				if ($val['goods_id']) {
					if (empty($val['discount_price'])) {
						if ($goods[$val['goods_id']]['area_price']>0) {
							$area_price = $goods[$val['goods_id']]['area_price'];
							$val['price'] = $area_price;
						}
                        $val['increase_price'] = $val['increase_price']>0?floatval($val['increase_price']):0;
						if ($val['level_discount_increase']<=0 || !$val['level_discount_increase']) {
							$val['level_discount_increase']=0;
						}
						$val['discount_price'] = round_number($val['price']+$val['increase_price']-$val['level_discount']-$val['level_discount_increase'],2);
						$val['discount_price'] = $val['discount_price'] < 0 ? 0.00 : $val['discount_price'];
					}
					$good_discount[$val['goods_id']]=$val;
				}
			}
			$this->assign('good_discount',$good_discount);
		}

		$level_off_system = M(_view('User_level'))->where(array('level' => $order['user_level']))->find();
		$this->assign('level_off_system',$level_off_system);
		// 获取优惠券信息
		$system_coupon = D('System_coupon')->get_coupon_by_id($order['coupon_id']);
		$this->assign('system_coupon',$system_coupon);

		// 计算实际支付金额
		$order['actual_discount_money'] = $order['discount_price'] - $order['score_deducte'];
		$order['actual_discount_money'] = round_number($order['actual_discount_money'],2);
		if ($order['delivery_fee'] > 0) {
            $order['actual_discount_money_info'] = round_number($order['actual_discount_money']+$order['delivery_fee'],2);
        } else {
            $order['actual_discount_money_info'] = $order['actual_discount_money'];
        }

		foreach ($order['order_detail'] as &$value) {
			$goods_log = M('Village_group_order_log')->where(array('deliver_status'=>array('gt',0),'order_id'=>$_GET['order_id'],'goods_id'=>array('in',array(0,$value['goods_id']))))->select();
			$status_arr = array();
			foreach ($goods_log as $key => $_log) {
				if (!in_array($_log['deliver_status'], $status_arr)) {
					$status_arr[] = $_log['deliver_status'];
				}else{
					unset($goods_log[$key]);
				}
			}
			$value['image'] = replace_file_domain($value['image']);
            $value['goods_log'] = array(array('status'=>0,'dateline'=>$value['create_time']));
            $goods_log  && $value['goods_log'] = array_merge($value['goods_log'],$goods_log);
			//区域售价加价信息
			if ($value['area_price']>0) {
				$value['price'] = $value['area_price'];
			}
            $value['increase_price'] = $value['increase_price']>0?floatval($value['increase_price']):0;
            $value['price'] +=$value['increase_price'];
		}
		// 处理周期购信息获取
		if ($order['village_group_type'] && $order['village_group_type']==1){
			$cycle_order_detail=M('Village_group_order_detail')->where(array('order_id' => $_GET['order_id']))->select();
			$goods_all_log = array();
			if ($cycle_order_detail) {
				foreach ($cycle_order_detail as $val) {
					$goods_all_log[$val['id']] = M('Village_group_order_log')->where(array('deliver_status'=>array('gt',0),'order_id'=>$_GET['order_id'],'goods_id'=>$val['goods_id'],'date_num'=>$val['date_num']))->select();

					$status_arr = array();
					foreach ($goods_all_log[$val['id']] as $key => $_log) {
						if (!in_array($_log['deliver_status'], $status_arr)) {
							$status_arr[] = $_log['deliver_status'];
						}else{
							unset($goods_all_log[$val['id']][$key]);
						}
					}
				}
			}
			// 查询下用户自身延期次数 是否受限
			$delay_num = M('Village_group_order_detail')->where(array('order_id' => $_GET['order_id'],'is_delay'=>1,'delay_user'=>0))->count();
			if ($order['is_limit_delay']==1 && ($order['limit_delay_num'] <=0 || $delay_num>=$order['limit_delay_num'])) {
				$this->assign('no_delay',true);
			}
			$cycle_order_count=M('Village_group_order_detail')->where(array('order_id' => $_GET['order_id'],'status'=>array('in', array(1,2,3,4,5))))->count();
			$this->assign('goods_all_log',$goods_all_log);
			$this->assign('cycle_order_count',$cycle_order_count);
			$this->assign('cycle_order_detail',$cycle_order_detail);
		}

        $is_mobile_pay = $order['plat_order']['is_mobile_pay'] ? $order['plat_order']['is_mobile_pay'] : 1;
		$order['pay_type_txt'] = D('Pay')->get_pay_name($order['pay_type'],$is_mobile_pay,1);
		$this->assign('label',D('Village_group')->get_label());


		// if (C('config.village_group_hide_detail')) {
		//     $header['phone'] = '';
		//     $header['name'] = str_replace_name($header['name']);
		//     if (mb_strlen($header['pick_address']['pick_addr'],'UTF8') > 3) {
		//         if (mb_strlen($header['pick_address']['pick_addr'],'UTF8') == 4) {
		//             $header['pick_address']['pick_addr'] =  mb_substr($header['pick_address']['pick_addr'],0,-3).'****';
		//         }else{
		//             $header['pick_address']['pick_addr'] =  mb_substr($header['pick_address']['pick_addr'],0,-4).'****';
		//         }
		//     }
		//     if (mb_strlen($order['pick_addr'],'UTF8') > 3) {
		//         if (mb_strlen($order['pick_addr'],'UTF8') == 4) {
		//             $order['pick_addr'] =  mb_substr($order['pick_addr'],0,-3).'****';
		//         }else{
		//             $order['pick_addr'] =  mb_substr($order['pick_addr'],0,-4).'****';
		//         }
		//     }
		// }

		//待支付订单超时时间（秒）
		$unpaid_timeout_seconds = C('config.village_group_order_unpaid_timeout') > 0 ? C('config.village_group_order_unpaid_timeout') * 60 : 1800;
		$this->assign('unpaid_timeout_seconds', $unpaid_timeout_seconds);

		$order['show_after_sale_btn'] = $order['bill_time'] + C('config.village_group_order_refund_days') * 86400 > time();
		$this->assign('village_group_unshipped_cancel_order',$this->config['village_group_unshipped_cancel_order']);
		$this->assign('village_group_consumed_cancel_order',$this->config['village_group_consumed_cancel_order']);
		$this->assign('header',$header);
		$this->assign('order',$order);
		$this->display();
	}


	public function group_order_refund(){
		//已支付未发货,取消订单==>郭先森 $this->user_session['uid']
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		$villageGroupOrder = D('Village_group_order')->where(array('order_id'=>$_GET['order_id'],'uid'=>$this->user_session['uid']))->find();
		$OrderDetailList = D('Village_group_order_detail')->where(array('order_id'=>$_GET['order_id']))->select();
		$platOrder = D('Plat_order')->where(array('business_type'=>'village_group','business_id'=>$_GET['order_id'],'uid'=>$this->user_session['uid']))->find();
		if(empty($platOrder)){
			$this->error_tips("当前订单的支付信息不存在");
		}
		$plat_money = $platOrder['total_money'];
		$this->assign('plat_money',$plat_money);
		if(empty($villageGroupOrder)){
			$this->error_tips('当前订单不存在');
		}
		$goodsCount = 0;
		$goodsMonery = 0.00;
		foreach($OrderDetailList as $k=>$v){
			$goodsCount += $v['num'];
//			$villageGroupGoodsIncreasePrice = D('Village_group_goods_increase_price')->where(array('goods_id'=>$v['goods_id'],'header_uid'=>$villageGroupOrder['start_uid']))->find();
//			if($villageGroupGoodsIncreasePrice){
//				$goodsMonery += $v['price']+$villageGroupGoodsIncreasePrice['increase_price'];
//			}else{
//				$goodsMonery += $v['price'];
//			}

		}
		$count_money = 0;
		if($villageGroupOrder['score_deducte'] > 0){
			$count_money = $villageGroupOrder['score_deducte'];
		}
		$coupon_price = 0;
		if($villageGroupOrder['coupon_price'] > 0){
			$coupon_price = $villageGroupOrder['coupon_price'];
		}
		$goodsMonery = $villageGroupOrder['discount_price'] +$coupon_price;
		$villageGroupOrder['Discount'] = $count_money + $coupon_price;

		$this->assign('goodsCount',$goodsCount);//商品总数量
		$this->assign('goodsMonery',$goodsMonery);//商品价格
		$this->assign('villageGroupOrder',$villageGroupOrder);//订单信息

		$this->assign('village_group_unshipped_cancel_order',$this->config['village_group_unshipped_cancel_order']);//未发货退款标识
		$this->assign('village_group_unshipped_cancel_order_desc',$this->config['village_group_unshipped_cancel_order_desc']);//未发货描述

		$this->display();

	}

	/**
	 * 申请售后
	 * @auth 郭先森
	 * @return array()
	 */
	public function refund(){

		if (empty($this->config['village_group_consumed_cancel_order'])) {
			$this->error_tips('平台未开启申请售后的权限！');
		}
		$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
		$order = D('Village_group_order')->field(true)->where(array('order_id' => $order_id, 'uid' => $this->user_session['uid']))->find();
		if (empty($order)) {
			$this->error_tips('订单信息有误');
		}
		if(!in_array($order['status'],[2, 13])){
			$this->error_tips('必须是已取货才能申请退款！');
		}
		if($order['status'] ==4 || $order['status'] ==12){
			$this->error_tips('订单已退款！');
		}
		if($order['is_apply_refund'] == 1){
			$this->error_tips('订单已申请退款！');
		}
		if ($order['bill_time'] + C('config.village_group_order_refund_days') * 86400 < time()) {
			$this->error_tips(sprintf('已超过售后时间（%d天），无法申请！', C('config.village_group_order_refund_days')));
		}

		if ($order['status'] == 2 || $order['status'] == 3  || $order['status'] == 13) {
			$sql = "SELECT g.image,d.status, d.goods_id,d.name, d.pay_price,d.increase_price, d.discount_price,d.discount_start_money, d.num, d.refundNum, d.id,d.packing_fee FROM " . C('DB_PREFIX') . "village_group_goods AS g INNER JOIN " . C('DB_PREFIX') . "village_group_order_detail AS d ON g.goods_id=d.goods_id WHERE d.status = 4 and d.refundNum = 0 and d.order_id={$order_id}";
			$list = D()->query($sql);

			$goods_image_class = new goods_image();
			$totleMoney = '';
			$totle_price = '';
			$goodsList = [];
			$discount_price = $order['discount_price'];
			foreach ($list as $row) {
				if($row['pay_price'] - $row['discount_start_money'] < 0){
					$row['pay_prices'] = 0;
					$row['totle_price'] = 0;
				}else{
					$row['pay_prices'] = $row['pay_price']* $row['num']+round_number($row['increase_price'],2)-round_number($row['discount_start_money'],2);
					$row['totle_price'] = ($row['pay_price'] - $row['discount_start_money'])*$row['num'];
				}


				$totleMoney += round_number($row['pay_prices'],2);
				$totle_price +=  round_number($row['totle_price'],2);

				if ($row['refundNum'] >= $row['num']) {
					unset($row);
					continue;
				}
				$tmp_pic_arr = $goods_image_class->get_allImage_by_path($row['image'], 's');
				$row['image'] = thumb_img($tmp_pic_arr[0],240,240,'fill');
				$goodsList[] = $row;
			}
			if($discount_price == 0){
				$order['price'] = 0;
			}else{
				$order['price'] = $totleMoney-round_number($order['score_deducte'],2) - round_number($order['coupon_price'],2);
			}

			//$order['totle_price'] = $totle_price-$order['score_deducte'] - $order['coupon_price'];
			$order['totle_prices'] = $totle_price - $order['score_deducte'] - $order['coupon_price'];

			$this->assign('goodsList', $goodsList);
			$this->assign('discount_price', round_number($order['score_deducte'],2)+round_number($order['coupon_price'],2));
			$this->assign('expire_tips', '商品已经全部申请退货了');
			$this->assign('expire', empty($goodsList) ? 1 : 0);
		}
		//用户实际支付的钱
		$customer_money = $order['price']*100;
		if(strpos($customer_money,'.') !== false){
			$customer_money = floor($customer_money)/100;
		}else{
			$customer_money = $customer_money/100;
		}

		$order['customer_money'] = $customer_money;
		$order['totle_price'] = $totle_price;
		$order['pay_prices'] = $order['discount_price']-$order['score_deducte'];

		$this->assign('order', $order);

		$this->assign('village_group_consumed_cancel_order',$this->config['village_group_consumed_cancel_order']);//已消费退款标识
		$this->assign('village_group_consumed_cancel_order_desc',$this->config['village_group_consumed_cancel_order_desc']);//已消费描述
		$this->display();
	}

	public function refundStatusList()
	{

		$order_id = $_GET['order_id'];
		$refundStatusList = D('Village_group_refund_detail')->where(array('order_id'=>$order_id))->order(array('id'=>'desc'))->select();
		$status = array('审核中', '已完成退货', '拒绝退货','取消退货申请');
		foreach ($refundStatusList as &$refund) {


			$refund['showStatus'] = $status[$refund['status']];
		}
        $this->assign('refund_list',$refundStatusList);
		$this->display();
	}

	public function refundDetail()
	{
		$refund_id = $_GET['refund_id'];
		$refundDetail = D('Village_group_refund_detail')->where(array('id'=>$refund_id))->order(array('id'=>'desc'))->find();
		if($refundDetail['goods_id']){
			$goods = explode(',',$refundDetail['goods_id']);
			foreach($goods as $val){
				$goodsdetail = D('Village_group_order_detail')->where(array('order_id'=>$refundDetail['order_id'],'goods_id'=>$val))->find();
				$village_group_goods = D('Village_group_goods')->where(array('goods_id'=>$val))->find();
				$goodsArr = array(
					'name'=>$goodsdetail['name'],
					'num'=>$goodsdetail['num'],
					'image'=>$village_group_goods['image'],
				);
				$goodsList[] = $goodsArr;
			}

		}
		$refundDetail['goodsList'] = $goodsList;
		$status = array('审核中', '已完成退货', '拒绝退货','取消退货申请');
		$refundDetail['showStatus'] = $status[$refundDetail['status']];
		$refundDetail['image'] = $refundDetail['image'] ? explode(',', $refundDetail['image']) : '';
		$this->assign('refund_list',$refundDetail);
		$this->display();
	}

	//已经申请售后订单列表
	public function refundStatus(){
		$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : '';
        $dataList = D('Village_group_refund_detail')->where(array('order_id'=>$order_id))->order(array('id'=>'desc'))->select();
		$goodsList = array();

        foreach($dataList as $item){
			if($item['goods_id']){
				$goods = explode(',',$item['goods_id']);
				foreach($goods as $val){
					$goodsdetail = D('Village_group_order_detail')->where(array('order_id'=>$order_id,'goods_id'=>$val))->find();
					$village_group_goods = D('Village_group_goods')->where(array('goods_id'=>$val))->find();
					$goodsArr = array(
						'name'=>$goodsdetail['name'],
						'num'=>$goodsdetail['num'],
						'image'=>$village_group_goods['image'],
					);
					$goodsList[] = $goodsArr;
				}

			}

		}
		$status = array('审核中', '已完成退货', '拒绝退货','取消退货申请');
		foreach ($dataList as &$refund) {

			$refund['image'] = $refund['image'] ? explode(',', $refund['image']) : '';
			$refund['goodsList'] = isset($goodsList) ?$goodsList : array();//原来数组
			$refund['showStatus'] = $status[$refund['status']];
		}

		$this->assign('refund_list', $dataList);
		$this->display();
	}

	//取消退货申请
	public function cancelRefund()
	{
		$refund_id = isset($_POST['refund_id']) ? intval($_POST['refund_id']) : 0;
		$refund = D('Village_group_order_refund')->field(true)->where(array('id' => $refund_id, 'uid' => $this->user_session['uid']))->find();
		if (empty($refund)) {
			exit(json_encode(array('errcode' => 1, 'msg' => '退货信息不正确')));
		}
		if ($refund['status'] > 0) {
			exit(json_encode(array('errcode' => 1, 'msg' => '当前状态不可取消退货申请了')));
		}
		$refundList = D('Village_group_refund_detail')->field(true)->where(array('refund_id' => $refund_id))->select();
		foreach ($refundList as $ref) {
			$jdOrder = M('Village_group_order_detail')->where(array('order_id' => $ref['order_id'], 'goods_id' => $ref['goods_id']))->find();
		}
		if ($jdOrder['jd_afs_id'] > 0) {

		}

		if (D('Village_group_order_refund')->where(array('id' => $refund_id, 'uid' => $this->user_session['uid']))->save(array('status' => 4))) {
			D('Village_group_refund_log')->add(array('refund_id' => $refund_id, 'status' => 4, 'dateline' => time(), 'note' => ''));
			$refundDetailList = D('Village_group_refund_detail')->field(true)->where(array('refund_id' => $refund_id))->select();
			$orderDetailDB = D('Village_group_order_detail');
			foreach ($refundDetailList as $row) {
				$orderDetailDB->where(array('id' => $row['detail_id']))->save(array('refundNum' => 0));
			}
			D('Shop_order')->where(array('order_id' => $refund['order_id'], 'uid' => $this->user_session['uid']))->save(array('is_apply_refund' => 0));
			exit(json_encode(array('errcode' => 0, 'msg' => 'ok')));
		} else {
			exit(json_encode(array('errcode' => 1, 'msg' => '取消审核失败，稍后重试')));
		}
	}

	public function ajax_upload_file()
	{
		if ($_FILES['imgFile']['error'] == 4) {
			exit(json_encode(array(
				'error' => 1,
				'msg' => '没有选择图片'
			)));
		}
		$upload_file = D('Image')->handle($this->user_session['uid'], 'villagegrouporderrefund', 0, array('size' => 4), false);
		if ($upload_file['error']) {
			exit(json_encode(array(
				'error' => 1,
				'msg' => '上传失败，请重试！'
			)));
		} else {
			exit(json_encode(array(
				'error' => 2,
				'msg' => '上传成功',
				'url' => $upload_file['url']['imgFile']
			)));
		}
	}

	//社区拼团售后退款===>郭先森=>社区拼团售后退款
	public function saveRefund()
	{
		$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
		$order = D('village_group_order')->field(true)->where(array('order_id' => $order_id, 'uid' => $this->user_session['uid']))->find();
		$order_detail_count = D('Village_group_order_detail')->where(array('order_id' => $order_id))->count();
		if (empty($order)) {
			exit(json_encode(array('errcode' => 1, 'msg' => '订单信息有误！')));
		}
		$goods = isset($_POST['goods']) ? $_POST['goods'] : '';

		$reason = empty($_POST['reason'])?'':$_POST['reason'];
		$images = empty($_POST['images'])?'':$_POST['images'];
		$nowTime = time();
		if (empty($goods)) {
			exit(json_encode(array('errcode' => 1, 'msg' => '请选择要退货的商品')));
		}

		$lockKey = 'village_group_order_refund_' . $this->user_session['uid'] . $order_id;
		if (S($lockKey)) {
			exit(json_encode(array('errcode' => 1, 'msg' => '频繁操作，请稍后再试！')));
		}
		S($lockKey, 1, 30);
     
		if(D('Village_group_refund_detail')->where(['order_id' => $order_id, 'status' => 0])->count()){
            exit(json_encode(array('errcode' => 1, 'msg' => '订单已申请退款，请勿重复操作！')));
        }

		$detailIds = array();
		$keyNums = array();
		foreach ($goods as $row) {
			$detailIds[] = $row['detail_id'];
			$keyNums[$row['detail_id']] = $row['num'];
		}
		$totalMoney = 0;
		$refund_nums = 0;
		$total_Money = '';
        $score_used_count = 0;
		if($order_detail_count == count($keyNums)){
            $score_used_count = $order['score_used_count'];//全退，返还积分
			$orderDetailDB = D('Village_group_order_detail');
			$list = $orderDetailDB->field(true)->where(array('order_id' => $order_id, 'id' => array('in', $detailIds)))->select();
			foreach ($list as $val) {
				$refund_nums += $keyNums[$val['id']];
				//商品id已逗号拼接
				$goodsList[] = $val['goods_id'];
				if($val['packing_fee'] > 0){
					$price = $val['packing_fee']*$val['num'];
					$total_Money += $price;
				}
				$orderDetailDB->where(array('id' => $val['id']))->save(array('refundNum' => $keyNums[$val['id']]));
			}
			//用户实际支付的钱 = 在线支付 - 优惠券-jifen
			$platOrder = D('Plat_order')->where(array('business_type'=>'village_group','business_id'=>$order['order_id']))->find();
			$order['price'] = $platOrder['total_money'];
			$customer_money = $platOrder['total_money']*100;
			if (strpos($customer_money, '.') !== false) {
				$customer_money = floor($customer_money) / 100;
			} else {
				$customer_money = $customer_money / 100;
			}
			//咱也不能让客户吃亏啊  如果是全退，就退实际支付的钱
			$discount_price = $order['discount_price'];
			if($discount_price == 0){
				$totalMoney = 0;
			}else{
				$totalMoney = $customer_money-$total_Money;
				$totalMoneys = $customer_money-$total_Money;
			}

		}else{
			$orderDetailDB = D('Village_group_order_detail');
			$list = $orderDetailDB->field(true)->where(array('order_id' => $order_id, 'id' => array('in', $detailIds)))->select();
			$goodsList = array();

			foreach ($list as $val) {
				if($val['pay_price'] - $val['discount_start_money'] <= 0){
					$price = 0;
				}else{
					$price = $val['pay_price']- $val['discount_start_money'];
				}
				$discount_price = $order['discount_price'];
				if($discount_price == 0){
					$totalMoney = 0;
				}else{
					$totalMoney += $price * $keyNums[$val['id']];
				}

				$refund_nums += $keyNums[$val['id']];
				//商品id已逗号拼接
				$goodsList[] = $val['goods_id'];
				$orderDetailDB->where(array('id' => $val['id']))->save(array('refundNum' => $keyNums[$val['id']]));
			}
			$totalMoneys = $totalMoney - $order['coupon_price']-$order['score_deducte'];

		}
		$newData = array(
			'goods_id'=>implode(',',$goodsList),
			'price'=>$totalMoneys,
			'image'=>$images,
			'reason'=>$reason,
			'create_time'=>$nowTime,
			'applytime'=>$nowTime,
			'order_id'=>$order_id,
			'uid'=>$this->user_session['uid'],
			'status'=>0,
            'score_used_count' => $score_used_count
		);

        $refundData = D('Village_group_refund_detail')->where(['order_id' => $newData['order_id'], 'goods_id' => $newData['goods_id'], 'uid' => $newData['uid']])->find();
        if ($refundData) {
			S($lockKey, null);
            exit(json_encode(array('errcode' => 0, 'msg' => '申请成功')));
        }

		$platOrder = D('Plat_order')->where(array('business_type'=>'village_group','business_id'=>$order['order_id']))->find();
		$newData['is_own'] = $platOrder['is_own'];//是否自由支付
		if($order_detail_count == count($keyNums)){
			if($platOrder['system_balance'] >0){
				if($platOrder['system_balance'] >= $totalMoney){
					$newData['balance_money'] = $totalMoney;//平台余额
				}else{
					$newData['balance_money'] = 0.00;
				}
			}
			if($platOrder['pay_money'] >0){
				if($platOrder['pay_money']>=$totalMoney){
					$newData['pay_money'] = $totalMoney;//在线支付
				}else{
					$newData['pay_money'] = 0;
				}

			}else{
				$newData['pay_money'] = 0.00;//在线支付
			}


		}else{
			$newData['coupon_id'] = 0;
			$newData['score_used_count'] = 0;
			if($platOrder['system_balance'] >0){
				if($platOrder['system_balance'] >= ($totalMoney-$order['coupon_price']-$order['score_deducte'])){
					$newData['balance_money'] = $totalMoney-$order['coupon_price']-$order['score_deducte'];//平台余额
				}
			}
			if($platOrder['pay_money'] >0){
				$newData['pay_money'] = $totalMoney-$order['coupon_price']-$order['score_deducte'];//在线支付
			}

		}
// var_dump($newData);die;
		//插入退款详情表
		$shopRefundDetailDB = D('Village_group_refund_detail');

		$shopRefundDetail = $shopRefundDetailDB->add($newData);

		if($shopRefundDetail){

			D('Village_group_order')->where(array('order_id' => $order_id, 'uid' => $this->user_session['uid']))->save(array('is_apply_refund' => 1));
			D('Village_group_refund_log')->add(array('refund_id' => $shopRefundDetail, 'status' => 0, 'dateline' => $nowTime, 'note' => $reason));
			S($lockKey, null);
			exit(json_encode(array('errcode' => 0, 'msg' => '申请成功')));
		}else{
			S($lockKey, null);
			exit(json_encode(array('errcode' => 1, 'msg' => '申请失败，稍后重试')));
		}

	}





	public function address(){
		$this->display();
	}

	public function leaderList(){
		$this->display();
	}

	public function selectAddress(){
		if(is_numeric($_COOKIE['now_city'])){
			$res = D('Area')->get_area_by_areaId($_COOKIE['now_city']);
			$this->assign('now_area',$res);
		}else if($_COOKIE['now_city']!=''){
			$res = D('Area')->get_area_by_areaUrl($_COOKIE['now_city']);
			$this->assign('now_area',$res);
		}
		$this->display();
	}

	public function goods(){
		redirect($this->config['site_url']."/packapp/community_group/index.html#/goods?goodsId=".$_GET['goods_id']."&leaderId=".$_GET['leader_id']."&fromOrder=".$_GET['from_order']);
		$goods = M('Village_group_goods')->where(array('goods_id'=>$_GET['goods_id']))->find();

		if(empty($goods)){
			$this->error_tips('该商品不存在');
		}

		$group_image_class = new group_image();
		$goods['all_pic'] = $group_image_class->get_allImage_by_path($goods['pic_list']);

		$this->assign('label',D('Village_group')->get_label());
//			if(time()<strtotime(date('Y-m-d',$goods['start_time']))+86400){
//				$active = 0;
//			}
//			if(time()<strtotime(date('Y-m-d',$goods['end_time']))+86400){
//				$active = 1;
//			}
//			if(time()<strtotime(date('Y-m-d',$goods['send_time']))+86400){
//				$active = 2;
//			}
//			if(time()<strtotime(date('Y-m-d',$goods['get_time']))+86400){
//				$active = 3;
//			}
		$now_date_zero  = strtotime(date('Y-m-d'));

		if($goods['get_time']>=$now_date_zero){
			$goods['open_time']=3;
		}
		if($goods['get_time']>=$now_date_zero+86400){
			$goods['open_time']=2;
		}


		if($goods['get_time']>$now_date_zero+86400*2){
			$goods['open_time']=1;
		}

		if($goods['own_sell']==0){
			$merchant = D('Merchant')->get_info($goods['mer_id']);
			$this->assign('merchant',$merchant);
		}
        $leader_id = $_GET['leader_id'];
//        if($leader_id){
//            $_SESSION['leader_id'] = $leader_id;
//            setcookie('village_group_leader_id',$leader_id,$_SERVER['REQUEST_TIME']+72000000,'/');
//        }
//		$header = D('Village_group')->get_header_info(0,$_SESSION['leader_id']);
		//===>郭先森重构
		$header = D('Village_group')->get_header_info(0,$leader_id);
        if($header['error_code']==2){
            $this->error_tips('没有查找到团长信息，请更换团长',U('new_group_header'));
        }
		// $all_count = M('Village_group_order_detail')->join('as d LEFT JOIN '.C('DB_PREFIX').'village_group_order o ON o.order_id = d.order_id')->where(array('goods_id'=>$_GET['goods_id'],'o.status'=>1))->group('o.uid')->count('id');

		$all_count =  M('')->query("select count(a.uid) as count from (SELECT o.uid from pigcms_village_group_order_detail as d LEFT JOIN pigcms_village_group_order as o ON o.order_id=d.order_id where d.goods_id={$_GET['goods_id']} AND d.date_num=o.newest_delivery_periods AND (o.status=1 OR o.status=3 OR o.status=5 OR o.status=6 OR o.status=7 OR o.status=8) group by o.uid) as a ");

		$all_count = $all_count[0]['count'];
		$all_num = M('Village_group_order_detail')->join('as d LEFT JOIN '.C('DB_PREFIX').'village_group_order o ON o.order_id = d.order_id')->where(array('goods_id'=>$_GET['goods_id'],'o.status'=>1))->sum('num');
		$all_wait_hadpull = M('Village_group_order_detail')->join('as d LEFT JOIN '.C('DB_PREFIX').'village_group_order o ON o.order_id = d.order_id')->where(array('goods_id'=>$_GET['goods_id'],'_string'=>'d.date_num=o.newest_delivery_periods AND (o.status=1 OR o.status=3 OR o.status=5 OR o.status=6 OR o.status=7 OR o.status=8)'))->sum('num');

		$buyer_list = M('Village_group_order_detail')->field('u.uid,u.nickname,u.uid,u.avatar')->join('as d LEFT JOIN '.C('DB_PREFIX').'village_group_order o ON o.order_id = d.order_id LEFT JOIN '.C('DB_PREFIX').'user u ON u.uid=o.uid')
				->group('o.uid')
				->where(array('d.goods_id'=>$_GET['goods_id'],'_string'=>'d.date_num=o.newest_delivery_periods AND (o.status=1 OR o.status=3 OR o.status=5 OR o.status=6 OR o.status=7 OR o.status=8)'))->order('d.id DESC')->limit(11)->select();

		/*替换资源地址*/
		$goods['content'] = html_entity_decode($goods['content']);
		$src = '<img src="'.file_domain().'/';
		$goods['content'] = str_replace('<img src="/', $src, $goods['content']);
		$goods['content'] = str_replace('<img src="'.$this->config['site_url'].'/', $src, $goods['content']);
		$goods['content'] = str_replace('<img alt="" src="/', $src, $goods['content']);
		$goods['content'] = str_replace('<img alt="" src="'.$this->config['site_url'].'/', $src, $goods['content']);

		$goods['image'] = replace_file_domain($goods['image']);

		// 获取评价详情
		$reply_all = D('Village_group_reply')->where(array('goods_id'=>$_GET['goods_id']))->count();
		$reply_level_1 = D('Village_group_reply')->where(array('goods_id'=>$_GET['goods_id'],'level'=>1))->count();
		$reply_level_2 = D('Village_group_reply')->where(array('goods_id'=>$_GET['goods_id'],'level'=>2))->count();
		$reply_level_3 = D('Village_group_reply')->where(array('goods_id'=>$_GET['goods_id'],'level'=>3))->count();
		$reply['reply_count'] = $reply_all;
		$reply['percent_1'] = round($reply_level_1/$reply_all,2)*100;
		$reply['percent_2'] = round($reply_level_2/$reply_all,2)*100;
		$reply['percent_3'] = round($reply_level_3/$reply_all,2)*100;
		// 满意度
		$reply['satisfaction'] = round(($reply_level_1*60 + $reply_level_2*80 + $reply_level_3*100)/($reply_all*100),2)*100;
		$this->assign('reply',$reply);

		if (C('config.village_group_hide_detail')) {
			$header['msg']['phone'] = '';
			$header['msg']['name'] = str_replace_name($header['msg']['name']);
			if (mb_strlen($header['msg']['pick_address']['pick_addr'],'UTF8') > 3) {
					if (mb_strlen($header['msg']['pick_address']['pick_addr'],'UTF8') == 4) {
					$header['msg']['pick_address']['pick_addr'] =  mb_substr($header['msg']['pick_address']['pick_addr'],0,-3).'****';
				}else{
					$header['msg']['pick_address']['pick_addr'] =  mb_substr($header['msg']['pick_address']['pick_addr'],0,-4).'****';
				}
			}
		}

		// 绑定区域信息
		$goods_bind_area =D('Village_group_goods_bind_area')->get_area_goods_bind_info($_GET['goods_id'],intval($header['msg']['pick_address']['province_id']),intval($header['msg']['pick_address']['city_id']));
		$this->assign('goods_bind_area',$goods_bind_area);

		//非团长用户查看价格加上团长加价
		if ($goods_bind_area) {
			$goods['price'] = $goods_bind_area['price'];
		}

		// 处理佣金比率问题
		$my_header = $header['msg'];
		$leader_give_money = 0;
		$is_leader = 0;
		if ($this->user_session) {
			$uid = $this->user_session['uid'];
			if ($uid == $my_header['uid']) {
				$is_leader = 1;
				// 登录了且是团长的用户
				$percent = floatval(str_replace('%','',$my_header['money']));
				$leader_money = $goods['leader_money']?$goods['leader_money']:0;
				if ($percent<=0 && $my_header['pick_address'] && $leader_money<=0) {
					if ($my_header['pick_address']['province_id']>0 || $my_header['pick_address']['city_id']>0 || $my_header['pick_address']['area_id']>0){
						$sale_num = D('Village_group_order')->sum_header_sale_num($my_header['id']);
						// 如果存在
						$province_id = $my_header['pick_address']['province_id'];
						$city_id = $my_header['pick_address']['city_id'];
						$area_id = $my_header['pick_address']['area_id'];
						$leader_money_percent = 0;
						if ($area_id > 0) {
							$leader_money_percent = M('Village_group_leader_area_level')
								->where(array('city_id' => $area_id, 'min_sale_num' => array('elt', $sale_num), 'max_sale_num' => array('egt', $sale_num)))
								->getField('leader_money');
							if (!$leader_money_percent && $sale_num>0) {
								$leader_money_percent = M('Village_group_leader_area_level')
									->where(array('city_id' => $area_id, 'min_sale_num' => array('elt', $sale_num)))
									->order('level DESC')
									->getField('leader_money');
							}
						}
						if ($city_id > 0 && (!$leader_money_percent || $leader_money_percent<=0)) {
							$leader_money_percent = M('Village_group_leader_area_level')
								->where(array('city_id' => $city_id, 'min_sale_num' => array('elt', $sale_num), 'max_sale_num' => array('egt', $sale_num)))
								->getField('leader_money');
							if (!$leader_money_percent && $sale_num>0) {
								$leader_money_percent = M('Village_group_leader_area_level')
									->where(array('city_id' => $city_id, 'min_sale_num' => array('elt', $sale_num)))
									->order('level DESC')
									->getField('leader_money');
							}
						}
						if ($province_id > 0 && (!$leader_money_percent || $leader_money_percent<=0)) {
							$leader_money_percent = M('Village_group_leader_area_level')
								->where(array('city_id' => $province_id, 'min_sale_num' => array('elt', $sale_num), 'max_sale_num' => array('egt', $sale_num)))
								->getField('leader_money');
							if (!$leader_money_percent && $sale_num>0) {
								$leader_money_percent = M('Village_group_leader_area_level')
									->where(array('city_id' => $province_id, 'min_sale_num' => array('elt', $sale_num)))
									->order('level DESC')
									->getField('leader_money');
							}
						}
						if ($leader_money_percent) {
							$percent = floatval(str_replace('%','',$leader_money_percent));
						}

					}
				}
				if ($leader_money>0) {
					$percent= floatval(str_replace('%','',$leader_money));
				}
				// 获取商品价格
				$price = $goods['price'];
				if ($percent>0) {
					$leader_give_money = $price*$percent/100;
                    $leader_give_money = round_number($leader_give_money,2);
				} else {
					$leader_give_money = 0;
				}
			}
		}
		$this->assign('is_leader',$is_leader);
		$this->assign('leader_give_money',$leader_give_money);

		// 获取加价
		$increase_price = M('Village_group_goods_increase_price')->where(array('header_uid' => $my_header['id'], 'goods_id' => $goods['goods_id']))->find();
		$increase_price = $increase_price['increase_price']>0?$increase_price['increase_price']:0;


		if ($goods_bind_area['is_add_price'] && $increase_price && !$is_leader) {
			$goods['price'] = round($goods['price']+$increase_price,2);
		}
		// 获取周期购的信息
		if ($goods && $goods['village_group_type'] == 1) {
			$village_group_cycle = M('Village_group_cycle')->where(array('goods_id'=>$goods['goods_id']))->find();
			// 处理日期问题
			$d_village_group = D('Village_group');
			if ($village_group_cycle['delivery_date']) {
				$village_group_cycle['delivery_date_arr'] = explode(',',$village_group_cycle['delivery_date']);
			}
			if ($village_group_cycle['cycle_type']==1) {
				$village_group_cycle['first_delivery_date'] = $d_village_group->lately_cycle_time($goods['goods_id'],0);
			} elseif ($village_group_cycle['cycle_type']==2) {
				if ($village_group_cycle['is_all_date']==1) {
					$village_group_cycle['delivery_date_arr'] = $d_village_group->choose_date['week'];
					$village_group_cycle['first_delivery_date'] = $d_village_group->lately_cycle_time($goods['goods_id'],1);
				} else {
					$week = min($village_group_cycle['delivery_date_arr']);
					$week = $week>0?$week:1;
					$week_info = $d_village_group->choose_date['week'];
					$new_delivery_date_arr = array();
					foreach ($village_group_cycle['delivery_date_arr'] as $key=>$val) {
						if ($week_info[$val]) {
							$new_delivery_date_arr[$val] = $week_info[$val];
						}
					}
					if ($new_delivery_date_arr) {
						$village_group_cycle['delivery_date_arr'] = $new_delivery_date_arr;
					}
					$village_group_cycle['first_delivery_date'] = $d_village_group->lately_cycle_time($goods['goods_id'],$week);
				}
			} elseif ($village_group_cycle['cycle_type']==3) {
				if ($village_group_cycle['is_all_date']==1) {
					$village_group_cycle['delivery_date_arr'] = $d_village_group->choose_date['date'];
					$village_group_cycle['first_delivery_date'] = $d_village_group->lately_cycle_time($goods['goods_id'],1);
				} else {
					$day = min($village_group_cycle['delivery_date_arr']);
					$day = $day>0?$day:1;
					$village_group_cycle['first_delivery_date'] = $d_village_group->lately_cycle_time($goods['goods_id'],$day);
				}
			}
			$village_group_cycle['date_msg'] = $d_village_group->choose_date['date_msg'];
			$this->assign('cycle_info',$village_group_cycle);
		}

		if ($buyer_list) {
		    foreach ($buyer_list as &$val) {
                if (!$val['avatar']) {
                    $val['avatar'] = $this->config['site_url'] . '/static/qrcode/wxapp/avatar.png';
                }
            }
        }
        $goods['sell_count']  = $goods['sell_count'] + $goods['virtual_sell_count'];

		$this->assign('increase_price',$increase_price);

		// 添加判断依据
        if ($goods) {
            if (($goods['stock_num']-$goods['sell_count'] <= 0) || ($goods['end_time'] > 0 && ($goods['end_time']+86400 < time() || $goods['start_time'] > time()))){
                $no_can_buy = true;
            } else {
                $no_can_buy = false;
            }
        } else {
            $no_can_buy = false;
        }
        $this->assign('no_can_buy',$no_can_buy);

		$this->assign('header',$header['msg']);
		$this->assign('all_num',$all_num);
		$this->assign('all_count',$all_count);
		$this->assign('all_wait_hadpull',$all_wait_hadpull);
		$this->assign('buyer_list',$buyer_list);
		$this->assign('goods',$goods);
		$this->assign('category',$this->get_one_category($goods['cat_id']));
		$this->display();
	}

	//根据拼团分类id获取分类信息
	protected function get_one_category($cat_id){
		if(empty($cat_id)) return array();
		$where = array();
		$where['cat_id'] = $cat_id;
		return M('Village_group_category')->where($where)->find();
	}

	public function cancelOrder(){
		M('Village_group_order')->where(array('order_id'=>$_GET['order_id']))->save(array('status'=>11,'last_time'=>time()));
		D("System_order")->save_order_status('village_group', $_GET['order_id'], 5);
		$where['start_id'] = $_GET['leader_id'];
		M('Village_group_order_cart')->where($where)->delete();
		$this->success_tips("订单取消成功",U('index'));
	}



	public function ajax_list(){

		$Village_group = D('Village_group');
		$verify = $_POST['verify']>0?$_POST['verify']:0;

		//兼容之前的订单,已取货显示全部自提的状态
		$header = $Village_group->get_header_info($this->user_session['uid']);
		$condition['o.status'] = 6;
		$condition['d.status'] = 4;
		$condition['o.start_uid'] = $header['msg']['id'];
		$field = "o.*,d.status as sstatus";
		$dataList = D('Village_group_order')->field($field)->join('as o LEFT JOIN '.C('DB_PREFIX').'village_group_order_detail as d ON o.order_id = d.order_id')->where($condition)->select();
		// if($dataList){
		// 	foreach($dataList as $item){
		// 		if($item['sstatus'] == 4){
		// 			D('Village_group_order_detail')->where(array('order_id'=>$item['order_id']))->save(array('status'=>2));
		// 		}
		// 	}
		// }


		if($_POST['verify']){
			$header = $Village_group->get_header_info($this->user_session['uid']);
			$where['start_uid'] = $header['msg']['id'];
			$where['_string'] ='status >0 AND status <10 OR status =13 ';
		}else{
			$where['uid'] = $this->user_session['uid'];
		}

		if($_POST['code']){
			$user = M('Village_group_user_code')->where(array('code'=>$_POST['code'],'status'=>1))->find();
			$where['uid'] = $user['uid'];
		}
		$_POST['status']!=-1 && $where['status'] = array('in',$_POST['status']);

		$page = $_POST['page'] ? $_POST['page'] : 1;
		$model = M('Village_group_order');
		if($_POST['status']==0){
			$where['add_time'] = array('gt',time()-1800);
		}
		$count = $model->where($where)->count();
		$totalPage = ceil($count/10);
		if($page > $totalPage){
			echo json_encode(array('totalPage'=>$totalPage,'order_list'=>array()));die;
		}
		$order_list = $model->where($where)->order('order_id DESC')->limit((($page-1)*10).',10')->select();

		$order_status = $Village_group->order_status;

		$m_user = M('User');
		foreach ($order_list as &$order) {
			$tmp_detail = $Village_group->get_order_detail($order['order_id'],$verify);
			$order['order_detail'] = $tmp_detail['goods_detail']?:array();
			if(!$_POST['verify']){
				$header = $Village_group->get_header_info(0,$order['start_uid'],true);
			}
			$order['header'] = $header['msg'];
			$order['status_txt'] = $order_status[$order['status']];
			// 查询一下参团者信息
			$order['join_user'] = $m_user->where(array('uid' => $order['uid']))->field('avatar, nickname,phone')->find();
			if (!$order['join_user']) {
				$order['join_user'] = array();
			}
			if ($order['delivery_fee']>0) {
			    $order['total_money'] = round_number($order['total_money'] + $order['delivery_fee'],2);
            }
		}

		echo json_encode(array('totalPage'=>$totalPage,'order_list'=>$order_list));
	}

	public function wapFriendRange($meter){
		if($meter < 100){
			return '<100m';
		}else if($meter <1000){
			return $meter.'m';
		}else{
			return round($meter/1000,1).'km';
		}
	}


	public function get_leader_list(){
		$now_area =  D('Area')->cityMatching($_POST['lat'],$_POST['lng']);
		$condition['h.status'] = 1;
		$condition['s.status'] = 1;
		$field = "h.*,s.city_id,s.area_id,s.province_id,s.name as village_name,s.long,s.lat,s.pick_addr,u.avatar,ROUND(12756.276 * ASIN(SQRT(POW(SIN(({$_POST['lat']}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$_POST['lat']}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$_POST['lng']}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli";
		$header = M('Village_group_pick_address')->field($field)->join('as s LEFT JOIN '.C('DB_PREFIX').'village_group_header as h ON s.header_uid = h.id LEFT JOIN '.C('DB_PREFIX').'user as u ON u.uid = h.uid')->where($condition)->order('h.sort desc')->select();

//        var_dump(M('Village_group_pick_address')->_sql());
		if($header){
			$header = sortArray($header,'juli');
			foreach ($header as &$item) {
				$item['juli_txt'] = $this->wapFriendRange($item['juli']);
				if (C('config.village_group_hide_detail')) {
					$item['name'] = str_replace_name($item['name']);
					if (mb_strlen($item['pick_addr'],'UTF8') > 3) {
							if (mb_strlen($item['pick_addr'],'UTF8') == 4) {
							$item['pick_addr'] =  mb_substr($item['pick_addr'],0,-3).'****';
						}else{
							$item['pick_addr'] =  mb_substr($item['pick_addr'],0,-4).'****';
						}
					}
				}
                if (!$item['avatar']) {
                    $item['avatar'] = $this->config['site_url'] . '/static/qrcode/wxapp/avatar.png';
                }
			}
		}
		echo json_encode($header);die;
	}



	public function ajax_cart_save(){

//		var_dump($_POST);
		if(!$_POST['cart_cookie']){
            $this->error_tips('购物车信息有误，请重新进入',U('index'));
		}
		$all_cart = $_POST['goodsCart']['all'];
		unset($_POST['goodsCart']['all']);
//		$res = D('Village_group')->check_cart($_POST['start_id'],$_POST['goodsCart']);
		//郭先森 重构
		$res = D('Village_group')->check_cart($_POST['start_id'],$_POST['goodsCart'],$_POST['leader_id']);

		if($res['error_code']){
			echo json_encode(array('status'=>0,'info'=>$res['msg'],'return'=>$res));die;
		}
		if($_POST['single_buy']){
			$condition_delete['single_buy'] = 1;
		}else{
			$condition_delete['single_buy'] = 0;
		}

		if($_POST['goodsCart']) {
			foreach ($_POST['goodsCart'] as $value) {

				if ($this->user_session['uid']) {

					$where['uid'] = $this->user_session['uid'];
					$where['cart_cookie'] = $_POST['cart_cookie'];
					// $where['start_id'] = $_POST['start_id'];
					$where['_logic'] = 'or';
					$condition_delete['_complex'] = $where;
				} else {
					$condition_delete['cart_cookie'] = $_POST['cart_cookie'];
				}

				M('Village_group_order_cart')->where($condition_delete)->delete();
			}
		}

		if($_POST['goodsCart']){
//			$header = D('Village_group')->get_header_info(0,$_SESSION['leader_id']);
			//郭先森 重构
			$header = D('Village_group')->get_header_info(0,$_POST['leader_id']);
            if($header['error_code']==2){
                $this->error_tips('没有查找到团长信息，请更换团长',U('new_group_header'));
            }
			foreach($_POST['goodsCart'] as $goods_id=>$value){
				$data = $value;
				$goods_bind_area = D('Village_group_goods_bind_area')->get_area_goods_bind_info($value['goods_id'],intval($header['msg']['pick_address']['province_id']),intval($header['msg']['pick_address']['city_id']));

				if ($goods_bind_area) {
					$value['good_price'] = $goods_bind_area['price'];
					if ($goods_bind_area['is_add_price'] && $value['increase_price'] && $value['increase_price'] > 0) {
						$value['good_price'] += $value['increase_price'];
					}
					$data['good_price'] = $value['good_price'];
				}
				$data['productParam'] = $value? serialize($value) : '';
				$data['start_id'] = $_POST['start_id'] ? $_POST['start_id'] :$_POST['start_id'];
				$data['goods_id'] = $goods_id;
				$data['count'] = $data['num'];
				$data['productPrice'] = $data['good_price'];
				$data['cart_cookie'] = $_POST['cart_cookie'];
				$data['uid'] = intval($this->user_session['uid']);
				$_POST['single_buy'] && $data['single_buy'] =$_POST['single_buy'];
				$data['num']>0 && M('Village_group_order_cart')->data($data)->add();
			}
			$this->success('ok');
		}else{
			$this->error('fail');
		}
	}


	public function ajax_cart_get(){
//		var_dump("郭先森");
//		var_dump($_POST['leader_id']);
		if(!$_POST['cart_cookie']){
			$this->error('error');
		}
		if($this->user_session['uid']){
			$where['uid'] = $this->user_session['uid'];
			// $where['start_id'] = $_POST['start_id'];
			$where['cart_cookie'] = $_POST['cart_cookie'];
			$where['_logic'] = 'or';
			$condition['_complex'] = $where;
		}else{
			$condition['cart_cookie'] = $_POST['cart_cookie'];
		}
		$_POST['goods_id'] && $condition['goods_id'] = $_POST['goods_id'];
		$condition['start_id'] = $_POST['start_id'];
		$condition['single_buy'] = array('neq',1);
		$cartList = M('Village_group_order_cart')
			->join('as c LEFT JOIN '.C('DB_PREFIX').'village_group_goods AS g ON g.goods_id = c.goods_id')
			->where($condition)->order('`id` ASC')->select();

		if(!$cartList){
			$cartList = array();

		}else{
			$label = D('Village_group')->get_label();
//			$leader_id = $_SESSION['leader_id']>0?$_SESSION['leader_id']:0;
			//郭先森 重构
			$leader_id = $_POST['leader_id']>0?$_POST['leader_id']:0;
			$header = D('Village_group')->get_header_info(0,$leader_id);
            if($header['error_code']==2){
                $this->error_tips('没有查找到团长信息，请更换团长',U('new_group_header'));
            }
			$goods_bind_area = array();
            $now_date_zero  = strtotime(date('Y-m-d'));
            foreach($cartList as $key=>&$cart_value){
				if ($leader_id>0) {
					$cart_value['increase_price'] = M('Village_group_goods_increase_price')->where(array('goods_id' => $cart_value['goods_id'],'header_uid' => $leader_id))->getField('increase_price');
				}
				if ($header && $header['msg'] && $header['msg']['pick_address']) {
					$goods_bind_area = D('Village_group_goods_bind_area')->get_area_goods_bind_info($cart_value['goods_id'],intval($header['msg']['pick_address']['province_id']),intval($header['msg']['pick_address']['city_id']));
				}
				$cart_value['productParam'] = $cart_value['productParam'] ? unserialize($cart_value['productParam']) : array();
				if(($cart_value['end_time']>0 && $cart_value['end_time']+86400<$_SERVER['REQUEST_TIME'] )
						||($cart_value['start_time']>0 && $cart_value['start_time']>$_SERVER['REQUEST_TIME'] )
						|| $cart_value['status']!=1){
					$cart_value['count'] = 0;
					unset($cartList[$key]);
				}else{
					$cart_value['count'] = intval($cart_value['count']);
				}
				$cart_value['stock_num'] = intval($cart_value['stock_num']-$cart_value['sell_count']);
				if ($goods_bind_area) {
					$cart_value['price'] = $goods_bind_area['price'];
					if ($goods_bind_area['is_add_price'] && $cart_value['increase_price'] && $cart_value['increase_price']>0) {
						$cart_value['price'] += $cart_value['increase_price'];
					}
				}
                if($cart_value['get_time']>=$now_date_zero){
                    $cart_value['open_time']=3;
                }
                if($cart_value['get_time']>=$now_date_zero+86400){
                    $cart_value['open_time']=2;
                }


                if($cart_value['get_time']>$now_date_zero+86400*2){
                    $cart_value['open_time']=1;
                }

				$cart_value['price'] =floatval($cart_value['price']);
				$cart_value['old_price'] =floatval($cart_value['old_price']);
				$cart_value['label'] =$label[$cart_value['label']];

				//获取分类信息
				$cate = $this->get_goods_category($cart_value['goods_id']);
				$cart_value['cat_show_tag'] = $cate['show_tag'];
			}
		}
		$this->success($cartList);
	}

	protected function get_goods_category($goods_id){
		if(empty($goods_id)) return array();
		$where = array();
		$where['g.goods_id'] = $goods_id;
		$field = 'c.*';
		$left_join = 'as g LEFT JOIN '.C('DB_PREFIX').'village_group_category c ON g.cat_fid = c.cat_id';
		return D('Village_group_goods')->field($field)->join($left_join)->where($where)->find();
	}

	public function check_order(){
		$where['uid'] = $this->user_session['uid'];
		$where['status'] = 6;
		if(M('Village_group_order')->where($where)->find()){
			$this->success(0);
		}else{

		}

	}

	public function save_village_group_order(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！',U('Login/index',array('referer'=>urlencode($this->config['site_url'].'/wap.php?c=Village_group&a=index&leader_id='.$_POST['start_id']))));
		}

		if($_POST['goodsCart']){
			$all_cart = $_POST['goodsCart']['all'];
			unset($_POST['goodsCart']['all']);
			if(empty($_POST['goodsCart'])){
				$this->error('购物车为空');
			}
			$res = D('Village_group')->check_cart($_POST['start_id'],$_POST['goodsCart'],$_POST['leader_id']);

			if($res['error_code']){
				$this->error($res['msg']);
			}else{
				$data['real_orderid']=date('Ymdhis') . substr(microtime(), 2, 8 - strlen($this->user_session['uid'])) . $this->user_session['uid'];
				$data['uid']=$this->user_session['uid'];
				$data['start_uid']=$_POST['start_id'];
				$data['total_money']=$res['order_money'];
				$data['comment']='';
				$data['add_time']=$_SERVER['REQUEST_TIME'];
				$data['status']=0;
				$data['last_time']=$_SERVER['REQUEST_TIME'];
				$data['code']=rand(100000,999999);
				// 处理周期购信息
				$village_group_type = 0;
				if ($_POST['goods_id'] && $_POST['single_buy']==1) {
					$goods = $res['goods'][$_POST['goods_id']];
					$village_group_type = $goods['village_group_type'];
					if ($goods && $goods['village_group_type']==1) {
						$cycle = M('Village_group_cycle')->where(array('goods_id'=>$goods['goods_id']))->find();
						$data['village_group_type'] = 1;
						$data['cycle_type'] = $cycle['cycle_type'];
						if ($cycle['cycle_type']==1) {
							$_POST['time']=1;
						}
                        if ($cycle['delivery_date'] && !$_POST['time']) {
                            $delivery_date_arr = explode(',',$cycle['delivery_date']);
                            if (1==count($delivery_date_arr)) {
                                $_POST['time'] = reset($delivery_date_arr);
                            } else {
                                $this->error_tips('请选择周期送时间');
                            }
                        }
						$data['delivery_date']  = $_POST['time'];
						$data['is_limit_delay']  = $cycle['is_limit_delay'];
						$data['limit_delay_num']  = $cycle['limit_delay_num'];
                        $data['delivery_periods']  = $cycle['delivery_periods'];
                        $data['is_all_date']  = $cycle['is_all_date'];
                        $data['limit_days']  = $cycle['limit_days'];
                        $data['limit_time']  = $cycle['limit_time'];
                        $data['delivery_date_info']  = $cycle['delivery_date'];
					}
				}

				if( $order_id = M('Village_group_order')->add($data)){
//						$where['cart_cookie'] = $_POST['cart_cookie'];
//						M('Village_group_order_cart')->where($where)->delete();
					$data['num'] = $res['num'];
//						D('System_order')->add_order('village_group',$data,$order_id);
					D('Village_group')->save_order_detail($order_id,$res['goods'],$_POST['goodsCart'],$_POST['start_id'],$village_group_type);


//						D('System_order')->add_village_group_goods($order_id);


					$pay_order_param = array(
						'business_type' => 'village_group',
						'business_id' => $order_id,
						'order_name' => $this->config['village_group_alias'].'订单',
						'uid' => $this->user_session['uid'],
						'total_money' => $res['order_money'],
						'wx_cheap' => 0,
					);

					$param['order_id'] = $order_id;
					$param['status'] = 0;
					$param['from_type'] = 0;
					$param['note'] = '用户下单';
					$param['name'] =$this->user_session['nickname'];
					$param['phone'] =$this->user_session['phone'];
					D('Village_group')->add_log($param);


					$plat_order_result = D('Plat_order')->add_order($pay_order_param);
					if ($plat_order_result['error_code']) {
						$this->error_tips($plat_order_result['error_msg']);
					} else {
						$this->success('参团成功',U('confirmOrder',array('order_id'=>$order_id)));
					}
				}else{
					$this->error_tips('参团失败');
				}
			}
		}else{
			$this->error_tips('非法访问');
		}
	}

	public function village_group_order_detail(){
		$order  = D('Village_group_order')->order_detail($_GET['order_id']);
		$this->assign('order',$order);
		$this->display();
	}

	public function orderBarcode(){
		import('@.ORG.phpqrcode');
		QRcode::png($_GET['code'],false,2,8,2);

	}

	public function my_header(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！',U('Login/index',array('referer'=>urlencode($this->config['site_url'].'/wap.php?c=Village_group&a=my_header'))));
		}
		$my_header = D('Village_group')->get_header_info($this->user_session['uid']);

		if($my_header['error_code']==2){
			redirect(U('new_group_header'));
		}

		$this->assign('header',$my_header['msg']);
		$this->display();
	}

	public function header_address(){
		$my_header = D('Village_group')->get_header_info($this->user_session['uid']);

		$province_id = $my_header['msg']['pick_address']['province_id'];
		$city_id = $my_header['msg']['pick_address']['city_id'];
		$area_id = $my_header['msg']['pick_address']['area_id'];
		$province_txt = M('Area')->where(array('area_id'=>$province_id))->find();
		$city_txt = M('Area')->where(array('area_id'=>$city_id))->find();
		$area_txt = M('Area')->where(array('area_id'=>$area_id))->find();
		$this->assign('header',$my_header['msg']);
		$this->assign('province_txt',$province_txt['area_name']);
		$this->assign('city_txt',$city_txt['area_name']);
		$this->assign('area_txt',$area_txt['area_name']);


		$this->display();
	}

	//新团长申请页面
	public function new_group_header(){
		$village_group_index = D('Adver')->get_adver_by_key('village_group_index',3);
		$this->assign('village_group_index',$village_group_index);
		$pick_address  = D('Village_group')->get_unbind_group();
		$this->assign('pick_address',$pick_address);
		$this->display();
	}

	public function apply_new_header(){

		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！',U('Login/index',array('referer'=>urlencode($this->config['site_url'].'/wap.php?c=Village_group&a=apply_new_header'))));
		}
		if(IS_POST){

			$_POST['status']=3;
			$_POST['uid'] = $this->user_session['uid'];
			$_POST['name'] = $this->user_session['nickname'];
			$_POST['last_time'] = $_SERVER['REQUEST_TIME'];
			$condition['status']=3;
			$condition['uid']= $this->user_session['uid'];
			$condition['pick_id']=$_POST['pick_id'];
			if(M('Village_group_header')->where($condition)->find()){
				$this->error_tips('你已经申请过了，请耐心等待');
			}
			if($res = M('Village_group_header')->add($_POST)){

				$this->success_tips('申请成功，请等待平台审核');
			}
		}else{
			$pick_addr = D('Village_group')->get_pick_address_by_id($_GET['id']);

			$this->assign('pick_addr',$pick_addr);
			$this->display();
		}
	}

	public function group_verify(){
		$my_header = D('Village_group')->get_header_info($this->user_session['uid']);
		if($my_header['error_code']){
			$this->error_tips($my_header['msg']);
		}
	}

	function ajax_get_order_info(){
		$code = $_POST['code'];
		$my_header = D('Village_group')->get_header_info($this->user_session['uid']);
		$res = M('Village_group_order')->where(array('code'=>$code,'start_id'=>$my_header['msg']['id']))->find();
	}

	function ajax_verify(){
		$order_id = $_POST['order_id'];
		$res = M('Village_group_order')->where(array('order_id'=>$order_id))->find();
		$my_header = D('Village_group')->get_header_info($this->user_session['uid'], $res['start_uid']);
		$my_header = $my_header['msg'];
		$order_detail = D('Village_group')->get_order_detail($res['order_id']);
		if(empty($res)){
			$this->error_tips('订单不存在');
		}

		if($res['status']==0){
			$this->error_tips('订单未支付');
		}

		if($res['status']==2){
			$this->error_tips('订单已消费');
		}
		if($_POST['confirm']==1) {
			$order = M('Village_group_order')->where(array('order_id' => $_POST['order_id']))->find();
			if ($order && $order['village_group_type']==1) {
				$detail_where = array(
					'order_id'=>$res['order_id'],
					'status' => array('lt', 3),
					'date_num' => $order['newest_delivery_periods']>0?$order['newest_delivery_periods']:1,
					'is_delay'=>0
				);
                // 查询下现有状态
                $info = M('Village_group_order_detail')->where($detail_where)->find();
                if ($info['status'] != 2) {
                    $this->error('当前周期购商品不能点击核销，请不要过于频繁操作!');
                }
				$save = M('Village_group_order_detail')->where($detail_where)->setField('status',3);
                $param['goods_id'] = $info['goods_id'];
				$param['date_num'] =$order['newest_delivery_periods']>0?$order['newest_delivery_periods']:1;
			}elseif($order['village_group_type']==0){
				$href            = C('config.site_url') . '/wap.php?g=Wap&c=My&a=spread_list&status=-1';
                //数据库0普通类型  =>自提订单 =郭先森 ==分佣金
				$villageGroupOrderDetail = M('Village_group_order_detail')->where(array('order_id'=>$order['order_id']))->select();
				$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
				foreach($villageGroupOrderDetail as $k=>$v){
					$goodsDeatail = M('Village_group_goods')
                        ->field('user_share_money, first_share_money,second_share_money,thild_share_money')
                        ->where(array('goods_id'=>$v['goods_id']))
                        ->find();

					if($goodsDeatail){
						$goodsDeatail['first_share_money'] = $goodsDeatail['first_share_money'] ? $goodsDeatail['first_share_money']*$v['num'] : 0;
						$goodsDeatail['second_share_money'] = $goodsDeatail['second_share_money'] ? $goodsDeatail['second_share_money']*$v['num'] : 0;
						$goodsDeatail['thild_share_money'] = $goodsDeatail['thild_share_money'] ? $goodsDeatail['thild_share_money']*$v['num'] : 0;
						$user = D('User')->where(array('uid'=>$order['uid']))->find();
						
						if(!C('customization.village_group_user_share_commission') && empty($order['share_uid'])){
                            $where['uid'] = array('eq',$user['uid']);
                            $userSpread = D('User_spread')->where($where)->find();//用户搜一级
                            $userMod = new UserModel(); 
                        }
						if(!empty($userSpread)){
							//存在一级
							$userSpreadOne = D('User_spread')->where(array('uid'=>$userSpread['spread_uid']))->find();

							if(!empty($userSpreadOne)){
								//一级
								$userOne = D('User')->field(true)->where(array('uid'=>$userSpreadOne['uid']))->find();
                                if ($userOne && !($userMod->canSpread($userOne['uid']))) {
                                    $userOne = [];
                                }

								if(!empty($userOne)){
									D('User')->add_money($userOne['uid'],$goodsDeatail['first_share_money'],'拼团验证消费，增加分佣');
									$spread_data = array('uid'=>$userOne['uid'],'spread_uid'=>0,'get_uid'=>$user['uid'],'money'=>$goodsDeatail['first_share_money'],'order_type'=>'vgroup','order_id'=>$order['order_id'],'third_id'=>0,'add_time'=>$_SERVER['REQUEST_TIME'],'status'=>1);
									D('User_spread_list')->data($spread_data)->add();
									$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $userOne['openid'], 'first' => L_("X1通过您的分享购买社区拼团商品，验证消费获得佣金。",array("X1" => $user['nickname'])) , 'keyword1' => $goodsDeatail['first_share_money'], 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $order['mer_id']);
								}
								//二级
								$userSpreadTwo = D('User_spread')->where(array('uid'=>$userSpreadOne['spread_uid']))->find();
								if(!empty($userSpreadTwo)){
									$userTwo = D('User')->field(true)->where(array('uid'=>$userSpreadTwo['uid']))->find();
                                    if ($userTwo && !($userMod->canSpread($userTwo['uid']))) {
                                        $userTwo = [];
                                    }
									if(!empty($userTwo)){
										D('User')->add_money($userTwo['uid'],$goodsDeatail['second_share_money'],'拼团验证消费，增加分佣');
										$spread_data = array('uid'=>$userTwo['uid'],'spread_uid'=>0,'get_uid'=>$user['uid'],'money'=>$goodsDeatail['second_share_money'],'order_type'=>'vgroup','order_id'=>$order['order_id'],'third_id'=>0,'add_time'=>$_SERVER['REQUEST_TIME'],'status'=>1);
										D('User_spread_list')->data($spread_data)->add();
										$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $userTwo['openid'], 'first' => L_("X1通过您的分享购买社区拼团商品，验证消费获得佣金。",array("X1" => $user['nickname'])) , 'keyword1' => $goodsDeatail['second_share_money'], 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $order['mer_id']);
									}
									//三级
									$userSpreadThree = D('User_spread')->where(array('uid'=>$userSpreadTwo['spread_uid']))->find();
									if(!empty($userSpreadThree)){
										$userThree = D('User')->field(true)->where(array('uid'=>$userSpreadThree['uid']))->find();
                                        if ($userThree && !($userMod->canSpread($userThree['uid']))) {
                                            $userThree = [];
                                        }
										if(!empty($userThree)){
											D('User')->add_money($userThree['uid'],$goodsDeatail['thild_share_money'],'拼团验证消费，增加分佣');
											$spread_data = array('uid'=>$userThree['uid'],'spread_uid'=>0,'get_uid'=>$user['uid'],'money'=>$goodsDeatail['thild_share_money'],'order_type'=>'vgroup','order_id'=>$order['order_id'],'third_id'=>0,'add_time'=>$_SERVER['REQUEST_TIME'],'status'=>1);
											D('User_spread_list')->data($spread_data)->add();
											$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $userThree['openid'], 'first' => L_("X1通过您的分享购买社区拼团商品，验证消费获得佣金。",array("X1" => $user['nickname'])) , 'keyword1' => $goodsDeatail['thild_share_money'], 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $order['mer_id']);
										}
									}else{
										//没有三级结束
										$userThree = D('User')->field('uid,now_money')->where(array('uid'=>$userSpreadTwo['uid']))->find();
                                        if ($userThree && !($userMod->canSpread($userThree['uid']))) {
                                            $userThree = [];
                                        }
										if(!empty($userThree)){
											D('User')->add_money($userThree['uid'],$goodsDeatail['thild_share_money'],'拼团验证消费，增加分佣');
											$spread_data = array('uid'=>$userThree['uid'],'spread_uid'=>0,'get_uid'=>$user['uid'],'money'=>$goodsDeatail['thild_share_money'],'order_type'=>'vgroup','order_id'=>$order['order_id'],'third_id'=>0,'add_time'=>$_SERVER['REQUEST_TIME'],'status'=>1);
											D('User_spread_list')->data($spread_data)->add();
											$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $userThree['openid'], 'first' => L_("X1通过您的分享购买社区拼团商品，验证消费获得佣金。",array("X1" => $user['nickname'])) , 'keyword1' => $goodsDeatail['thild_share_money'], 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $order['mer_id']);
										}
									}
								}else{
									//二级
									$userTwo = D('User')->field('uid,now_money')->where(array('uid'=>$userSpreadOne['spread_uid']))->find();
                                    if ($userTwo && !($userMod->canSpread($userTwo['uid']))) {
                                        $userTwo = [];
                                    }
									if(!empty($userTwo)){
										D('User')->add_money($userTwo['uid'],$goodsDeatail['second_share_money'],'拼团验证消费，增加分佣');
										$spread_data = array('uid'=>$userTwo['uid'],'spread_uid'=>0,'get_uid'=>$user['uid'],'money'=>$goodsDeatail['second_share_money'],'order_type'=>'vgroup','order_id'=>$order['order_id'],'third_id'=>0,'add_time'=>$_SERVER['REQUEST_TIME'],'status'=>1);
										D('User_spread_list')->data($spread_data)->add();
										$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $userTwo['openid'], 'first' => L_("X1通过您的分享购买社区拼团商品，验证消费获得佣金。",array("X1" => $user['nickname'])) , 'keyword1' => $goodsDeatail['second_share_money'], 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $order['mer_id']);
									}
								}
							}else{
								//一级
								$userOne = D('User')->field('uid,now_money')->where(array('uid'=>$userSpread['spread_uid']))->find();
                                if ($userOne && !($userMod->canSpread($userOne['uid']))) {
                                    $userOne = [];
                                }
								if(!empty($userOne)){
									D('User')->add_money($userOne['uid'],$goodsDeatail['first_share_money'],'拼团验证消费，增加分佣');
									$spread_data = array('uid'=>$userOne['uid'],'spread_uid'=>0,'get_uid'=>$user['uid'],'money'=>$goodsDeatail['first_share_money'],'order_type'=>'vgroup','order_id'=>$order['order_id'],'third_id'=>0,'add_time'=>$_SERVER['REQUEST_TIME'],'status'=>1);
									D('User_spread_list')->data($spread_data)->add();
									$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $userOne['openid'], 'first' => L_("X1通过您的分享购买社区拼团商品，验证消费获得佣金。",array("X1" => $user['nickname'])) , 'keyword1' => $goodsDeatail['first_share_money'], 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $order['mer_id']);
								}

							}
						}else{
							//没有来自分享者的推广uid,无操作
						}
					}
				}
				$save = M('Village_group_order_detail')->where(array('order_id'=>$order_id,'status'=>array('lt',3)))->setField('status',3);
			} else {
				$save = M('Village_group_order_detail')->where(array('order_id'=>$order_id,'status'=>array('lt',3)))->setField('status',3);
			}

			fdump($save,'group1');
            fdump_api(['推广分佣1'=>[$save]],'village_group',1);
			if($save!==false){
                if(C('customization.village_group_user_share_commission') && !empty($order['share_uid'])){//直接推广分佣 一次只会购买一种商品
                    $villageGroupOrderDetail = M('Village_group_order_detail')->where(array('order_id'=>$order['order_id']))->select();
                    fdump_api(['推广分佣3'=>[$villageGroupOrderDetail]],'village_group',1);
                    foreach($villageGroupOrderDetail as $k=>$v) {
                        fdump_api(['推广分佣4'=>[$v]],'village_group',1);
                        $goodsDeatail = M('Village_group_goods')
                            ->field('user_share_money')
                            ->where(array('goods_id' => $v['goods_id']))
                            ->find();
                        if($goodsDeatail['user_share_money'] > 0){
                            $userCommission = bcmul($goodsDeatail['user_share_money'], $v['num'],2);
                            D('User')->add_money($order['share_uid'],$userCommission,'拼团验证消费，增加分佣');
                            $shareUserCommission = bcadd($order['share_user_commission'],$userCommission,2);
                            M('Village_group_order')->where(array('order_id' => $_POST['order_id']))->save(['share_user_commission' => $shareUserCommission]);
                            fdump_api(['推广分佣5'=>[ M('Village_group_order')->getLastSql()]],'village_group',1);
                        }
                        if($order['village_group_type']==1){//周期购，只加一次
                            break;
                        }
                    }
                }
                
				$param['from_type'] =2;
				$param['deliver_status'] = 3;
				$param['note'] = '团长点击商品用户确认自提';
				$param['name'] = $my_header['name'].' ID:'.$my_header['id'];
				$param['order_id'] = $order_id;
				D('Village_group')->add_log($param);

			fdump($res,'group1',1);
				$order_detail = D('Village_group')->get_order_detail($res['order_id']);
			fdump($order_detail,'group1',1);
				$this->verify_notice($order_detail,$res,$my_header,2);
                //增加消息通知
                $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                $params['type'] = 0;
                $params['business'] = 'village_group';
                $params['order_id'] = $order_id;
                $params['title'] = '核销成功，已经领取商品';
                $params['content'] = '您已经领取店铺自提商品，请核对订单';
                invoke_v20_service($url, [$params]);
				$this->success('验证成功');
			}else{
				$this->success('验证失败，请重试');
			}
		}else{
			$num =0;
			$goods_des = '';
			foreach ($order_detail['goods_detail'] as $goods) {
				$num += $goods['num'];
				$goods_des .="【{$goods['name']}】X {$goods['num']}<br>";
			}
			$des="订单信息<br>";
			$des.="订单id：{$order_detail['real_orderid']}<br>";
			$des.="订单总价：".floatval($order_detail['total_money'])."<br>";
			$des.="支付方式：{$order_detail['pay_type']}<br>";
			$des.="共：".count($order_detail['goods_detail'])."种，{$num}个商品<br>";

			$des.=$goods_des;
			$des.="用户uid:{$order_detail['uid']}<br>";
			$des.="用户手机:{$order_detail['phone']}<br>";
			$des.="用户昵称:{$order_detail['nickname']}<br>";
			$this->success($des);
		}
		//增加余额

//			D('User')->add_money($order_info['uid']);


	}

//确认到货
	function ajax_get_all(){
		$order_id = $_POST['order_id'];
		$my_header = D('Village_group')->get_header_info($this->user_session['uid']);
		$my_header = $my_header['msg'];
		$res = M('Village_group_order')->where(array('order_id'=>$order_id))->find();
		$order_detail = D('Village_group')->get_order_detail($res['order_id']);
		if(empty($res)){
			$this->error_tips('订单不存在');
		}

		if($res['status']==0){
			$this->error_tips('订单未支付');
		}

		if($res['status']==2){
			$this->error_tips('订单已消费');
		}


		$order = M('Village_group_order')->where(array('order_id' => $_POST['order_id']))->find();
		if ($order && $order['village_group_type']==1) {
			$detail_where = array(
				'order_id'=>$res['order_id'],
				'status' => array('lt', 2),
				'date_num' => $order['newest_delivery_periods']>0?$order['newest_delivery_periods']:1,
				'is_delay'=>0
			);
            // 查询下现有状态
            $info = M('Village_group_order_detail')->where($detail_where)->find();
            if ($info['status'] != 1) {
                $this->error('当前周期购商品不能点击确认收货，请不要过于频繁操作!');
            }
			M('Village_group_order_detail')->where($detail_where)->setField('status',2);
		}if($order && $order['village_group_type']==0){

			M('Village_group_order_detail')->where(array('order_id'=>$res['order_id'], 'status' => array('lt', 2)))->setField('status',2);


		} else {
			M('Village_group_order_detail')->where(array('order_id'=>$res['order_id'], 'status' => array('lt', 2)))->setField('status',2);
		}
		$now_user = D('User')->field(true)->where(array('uid' => $res['uid']))->find();
		if (($order && $order['village_group_type']==1 && $order_detail['date_num']<=($order['newest_delivery_periods']+1)) || $order['village_group_type']!=1){
			$set_data = array(
				'last_time' => time(),
				'status' => 6
			);
			M('Village_group_order')->where(array('order_id'=>$res['order_id']))->save($set_data);

			$param['order_id'] = $res['order_id'];
			$param['status'] = 6;
			$param['from_type'] = 2;
			$param['note'] = '团长确认商品全部到货';
			$param['name'] =$now_user['nickname'];
			$param['phone'] =$now_user['phone'];
			D('Village_group')->add_log($param);
			unset($param['status']);
		} else {
			$param['goods_id'] = $order_detail['goods_id'];
			$param['order_id'] = $res['order_id'];
			$param['status'] = 6;
			$param['from_type'] = 2;
			$param['note'] = '团长确认商品到货';
			$param['name'] =$now_user['nickname'];
			$param['phone'] =$now_user['phone'];
			$param['date_num'] =$order_detail['date_num'];
		}

		$param['deliver_status'] = 2;
		D('Village_group')->add_log($param);
		if ($res['is_pick_group'] == 1) { //自提单不执行配送订单的操作
            D('Deliver_supply')->saveOrder($res['order_id'], null,8,'village_group_order');
        }
		$this->success('收货成功');
	}

	public function verify_notice($order_detail,$order,$my_header,$from_type){
		if ($order['status'] == 2) {
			return array('error'=>1,'msg'=>'已验证消费');
		}

		// 验证消费的商品
		$order_info_arr = array();

		// 验证消费的单个商品佣金总和
		$leader_money = 0;

		// 验证消费的 、设置了单个商品佣金的商品总价
		$goods_leader_money_total = 0;

		// 验证消费商品id
		$bill_goods_ids = array();
		// 未验证消费商品id
		$un_bill_goods_ids = array();
		// 未自提的商品总价
		$un_bill_leader_money_total = 0;
		// 自提的商品总价
		$bill_leader_money_total = 0;

        // 处理优惠信息
        if ($order_detail['good_discount']) {
            $good_discount = $order_detail['good_discount'];
        } else {
            $good_discount = array();
        }
        // 取订单期数，如果没有默认为1
        $delivery_periods = $order['delivery_periods']>0?intval($order['delivery_periods']):1;
        // 加价金额总计
        $increase_price_total = 0;
        $goods_score = 0;
        $goods_score_price = 0;
        foreach ($order_detail['goods_detail'] as $v) {
            if ($v['area_price']>0) {
                $v['price'] = $v['area_price'];
            }
            if ($good_discount && $good_discount[$v['goods_id']] && $good_discount[$v['goods_id']]['level_discount'] > 0) {
                $level_discount_increase = $good_discount[$v['goods_id']]['level_discount_increase']?intval($good_discount[$v['goods_id']]['level_discount_increase']):0;
                $v['discount_price'] = $v['price'] - $good_discount[$v['goods_id']]['level_discount']-$level_discount_increase;
                $v['discount_price'] = $v['discount_price']>0?round_number($v['discount_price'],2):0;
            } else {
                $v['discount_price'] = $v['price'];
            }
            if ($delivery_periods>0) {
                $v['discount_price'] = $v['discount_price']/$delivery_periods;
                $v['purchase_price'] = $v['purchase_price']/$delivery_periods;
            }
            if ($v['deliver_status'] == 3) { // 已自提 已自提的才能核销 单个商品核销
                $order_info_arr[$v['mer_id']]['total_money'] += $v['purchase_price']*$v['num'];
                $order_info_arr[$v['mer_id']]['total_pay_money'] += $v['discount_price']*$v['num'];

				$order_info_arr[$v['mer_id']]['group_id'] .= $v['group_id'] . ',';
				if($v['leader_money']>0){
					$goods_leader_money_total += $v['discount_price']*$v['num'];
					// 下单支付成功时商品的佣金比例计算
                    // 如果下单的时候未记录取用最新的分佣值
                    if ($v['order_leader_money']<=0) {
                        $leader_money += ($v['discount_price']*$v['num'])*$v['leader_money']/100;
                    } else {
                        $leader_money += ($v['discount_price']*$v['num'])*$v['order_leader_money']/100;
                    }
				}
				$bill_goods_ids[] = $v['id'];
				$bill_leader_money_total += $v['discount_price']*$v['num'];
				if ($v['increase_price']>0) {
					$increase_price_total += $v['increase_price']*$v['num'];
				}
			}else if ($v['deliver_status'] != 4 && $v['deliver_status'] != 5 ){
				$un_bill_leader_money_total += $v['discount_price']*$v['num'];
				$un_bill_goods_ids[] = $v['id'];
			}
			if($v['score_percent'] >= 0){
				$goods_score += $v['discount_price']*$v['num']*$v['score_percent'];
				$goods_score_price += $v['discount_price']*$v['num'];
			}
		}
		if ($delivery_periods>0) {
			$increase_price_total = $increase_price_total/$delivery_periods;
		}

		// 没有设置单个商品佣金的按团长佣金计算
		// 统一使用百分比进行抽成
		// 处理佣金比率问题
		$percent = floatval(str_replace('%','',$my_header['money']));
		if ($percent<=0 && $my_header['pick_address']) {
			if ($my_header['pick_address']['province_id']>0 || $my_header['pick_address']['city_id']>0 || $my_header['pick_address']['area_id']>0){
				$sale_num = D('Village_group_order')->sum_header_sale_num($my_header['id']);
				// 如果存在
				$province_id = $my_header['pick_address']['province_id'];
				$city_id = $my_header['pick_address']['city_id'];
				$area_id = $my_header['pick_address']['area_id'];
				$leader_money_percent = 0;
				if ($area_id > 0) {
					$leader_money_percent = M('Village_group_leader_area_level')
						->where(array('city_id' => $area_id, 'min_sale_num' => array('elt', $sale_num), 'max_sale_num' => array('egt', $sale_num)))
						->getField('leader_money');
					if (!$leader_money_percent && $sale_num>0) {
						$leader_money_percent = M('Village_group_leader_area_level')
							->where(array('city_id' => $area_id, 'min_sale_num' => array('elt', $sale_num)))
							->order('level DESC')
							->getField('leader_money');
					}
				}
				if ($city_id > 0 && (!$leader_money_percent || $leader_money_percent<=0)) {
					$leader_money_percent = M('Village_group_leader_area_level')
						->where(array('city_id' => $city_id, 'min_sale_num' => array('elt', $sale_num), 'max_sale_num' => array('egt', $sale_num)))
						->getField('leader_money');
					if (!$leader_money_percent && $sale_num>0) {
						$leader_money_percent = M('Village_group_leader_area_level')
							->where(array('city_id' => $city_id, 'min_sale_num' => array('elt', $sale_num)))
							->order('level DESC')
							->getField('leader_money');
					}
				}
				if ($province_id > 0 && (!$leader_money_percent || $leader_money_percent<=0)) {
					$leader_money_percent = M('Village_group_leader_area_level')
						->where(array('city_id' => $province_id, 'min_sale_num' => array('elt', $sale_num), 'max_sale_num' => array('egt', $sale_num)))
						->getField('leader_money');
					if (!$leader_money_percent && $sale_num>0) {
						$leader_money_percent = M('Village_group_leader_area_level')
							->where(array('city_id' => $province_id, 'min_sale_num' => array('elt', $sale_num)))
							->order('level DESC')
							->getField('leader_money');
					}
				}
				if ($leader_money_percent) {
					$percent = floatval(str_replace('%','',$leader_money_percent));
				}

			}
		}
		$goods_leader_money_total = round_number($goods_leader_money_total,2);
//            $un_bill_leader_money_total = round_number($un_bill_leader_money_total,2);
		$header_money = ($bill_leader_money_total-$goods_leader_money_total)*$percent/100+$leader_money+$increase_price_total;
		$header_money = round_number($header_money,2);

		// 改变已核销商品状态
		$update_status = M('Village_group_order_detail')->where(array('id'=>array('in',$bill_goods_ids)))->setField('status',4);
		if (!$update_status) {
			return array('error'=>1,'msg'=>'改变商品状态失败');
		}
		// $exist = D('Merchant_money_list')->where(array('order_id'=>$order['order_id'],'type'=>'village_group'))->find();
		// if(empty($exist)){
			// 增加商家余额
			foreach ($order_info_arr as $mer_id => $vv) {
				$order_info = array();
				$order_info['total_money'] = $vv['total_money'];
				$order_info['order_type'] = 'village_group';
				$order_info['order_id'] = $order['order_id'];
				$order_info['desc'] = "用户购买".$this->config['village_group_alias']."记入收入";
				$order_info['mer_id'] = $mer_id;
				$order_info['is_own'] = 0;
				D('Merchant_money_list')->add_money($order_info);
			}
		// }

		//拼团验证消费，增加团长分佣
		D('User')->add_money($my_header['uid'],$header_money,'拼团验证消费，增加团长分佣');
		M('Village_group_header')->where(array('id'=>$my_header['id']))->setInc('header_money',$header_money);
		M('Village_group_order')->where(array('order_id'=>$order['order_id']))->setInc('spread_money',$header_money);

		// 改变订单状态
		if (empty($un_bill_goods_ids) && ($order['village_group_type']!=1 || $order['newest_delivery_periods']>=$order['delivery_periods'])) { // 全部商品都核销
			$set_data = array(
				'bill_time' => time(),
				'status' => 2
			);
			M('Village_group_order')->where(array('order_id'=>$order['order_id']))->save($set_data);
		}
		// 改变对应周期购最新期数
		if ($order['village_group_type']==1 && $order['newest_delivery_periods']<$order['delivery_periods']){
			M('Village_group_order')->where(array('order_id'=>$order['order_id']))->setInc('newest_delivery_periods');
		}

		// 用户信息
		$now_user = D('User')->field(true)->where(array('uid' => $order['uid']))->find();

		// 订单状态改变记录
		$param['order_id'] = $order['order_id'];
		$param['from_type'] = $from_type;
		$param['name'] =$now_user['nickname'];
		$param['phone'] =$now_user['phone'];
		if ((!empty($un_bill_goods_ids) && $order['village_group_type']!=1) || $order['newest_delivery_periods']<$order['delivery_periods']) {
			// 部分商品都核销
			$param['status'] = 3;
			$param['note'] = '用户部分商品已提货，增加团长佣金';
		}else{
			// 全部商品都核销
			$param['status'] = 2;
			$param['note'] = '用户已全部提货，完成消费，增加团长佣金';
		}
		D('Village_group')->add_log($param);
		D("System_order")->save_order_status('village_group', $order['order_id'], 2);

		// 给用户对应添加积分
		if($this->config['open_score_get_percent']==1){
			$score_get = $this->config['score_get_percent']/100;
		}else{
			$score_get = $this->config['user_score_get'];
		}
		// 全部验证消费后获得积分
		if (($score_get > 0 && (empty($un_bill_goods_ids) && ($order['village_group_type']!=1 || $order['newest_delivery_periods']>=$order['delivery_periods']))) || $goods_score>0) {
			// 这里需要处理需要计算的订单总额
			$total_money = $order['total_money'];
			if ($order['discount_price'] > 0) {
				// 如果优惠后金额大于0 以此计算
				$total_money = $order['discount_price'];
			}
			//积分抵扣部分不算积分
			D('User')->add_score($order['uid'], ($total_money - $order['score_deducte'] - $goods_score_price) * $score_get + $goods_score, '购买 社区拼团 消费' . floatval($total_money) . $this->config['Currency_txt'].' 获得'.$this->config['score_name'],0,[
			    'order_id'=>$order['order_id'],
                'order_type'=>'village_group'
            ]);
			D('Scroll_msg')->add_msg('village_group',$now_user['uid'],'用户'.str_replace_name($now_user['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).'购买'.$this->config['group_alias_name'].'拼团成功并消费获得'.$this->config['score_name']);
		}
		return array('error'=>0,'msg'=>'验证消费成功');
	}


	//收货 取货
	public function ajax_get_order(){
		if($_POST['goods_id']) {
			$order_arr[] =$_POST['order_id'];
            $where['order_id'] = $_POST['order_id'];
            $order_info = M('Village_group_order')->where($where)->find();
            if ($order_info['village_group_type']==2) {
                $this->error('需要平台配送的订单不可以单独点击收货，只能点击全部收货!');
            }
			$where['goods_id'] = $_POST['goods_id'];
			$my_header = D('Village_group')->get_header_info($this->user_session['uid']);
			$my_header = $my_header['msg'];
			$status= $_POST['status'];
			$status_txt = D('Village_group')->order_status;
			$order = M('Village_group_order')->where(array('order_id' => $_POST['order_id']))->find();
			if ($order['village_group_type']==1) {
				$where['date_num']=$order['newest_delivery_periods']>0?$order['newest_delivery_periods']:1;
				$where['is_delay']=0;
				$param['date_num'] = $where['date_num'];
			}
			// 查询下现有状态
            $info = M('Village_group_order_detail')->where($where)->find();

			if ($status==2 && $info['status'] != 1) {
                $this->error('当前商品不能点击确认收货，请不要过于频繁操作!');
            } elseif ($status==3 && $info['status'] != 2) {
                $this->error('当前商品不能点击核销，请不要过于频繁操作!');
            }
			if (M('Village_group_order_detail')->where($where)->setField('status', $status)) {
				foreach ($order_arr as $value) {
					$where['order_id'] = $value;
					if (M('Village_group_order_detail')->where(array('order_id' => $value, 'status' => array('lt', $status)))->find()) {
						// 部分商品状态还未改变
						$order_status = 8;
						if($status==3){
							$order_status = 3;
						}
						$res = M('Village_group_order')->where($where)->setField('status', $order_status);
					} else {
						if($status!=3){
							$order_status = 6;
							$res = M('Village_group_order')->where($where)->setField('status', $order_status);
						} else {

							$order_status= 2;
							D('System_order')->save_order_status('village_group',$value,2);
						}
					}

					if($status==3) {
						// 已自提 去核销
						$order_detail = D('Village_group')->get_order_detail($_POST['order_id'],1);
						$order = M('Village_group_order')->where(array('order_id'=>$_POST['order_id']))->find();
						$this->verify_notice($order_detail, $order, $my_header, 2);
					}
					$param['goods_id'] = $_POST['goods_id'];
					$param['from_type'] =2;
					$param['deliver_status'] = $status;
					if($status==2){
						$param['note'] = '团长点击商品收货';
					}else if($status==3) {
						$param['note'] = '团长点击商品用户确认自提';

						//单个商品分佣确认自提处理
						$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
						$href            = C('config.site_url') . '/wap.php?g=Wap&c=My&a=spread_list&status=-1';
						$villageGroupOrderDetail = M('Village_group_order_detail')->where(array('order_id'=>$order['order_id'],'goods_id'=>$_POST['goods_id']))->find();

                        $userMod = new UserModel();
						if($villageGroupOrderDetail){
							$goodsDeatail = M('Village_group_goods')
                                ->field('user_share_money, first_share_money,second_share_money,thild_share_money')
                                ->where(array('goods_id'=>$villageGroupOrderDetail['goods_id']))
                                ->find();

							if($goodsDeatail){
								$user = D('User')->where(array('uid'=>$order['uid']))->find();
								
                                if(C('customization.village_group_user_share_commission') && !empty($order['share_uid'])){//直接推广分佣 一次只会购买一种商品

                                    $userCommission = bcmul($goodsDeatail['user_share_money'], $value['num'],2);
                                    D('User')->add_money($order['share_uid'],$userCommission,'拼团验证消费，增加分佣');
                                    $shareUserCommission = bcadd($order['share_user_commission'], $userCommission,2);
                                    M('Village_group_order')->where(array('order_id' => $_POST['order_id']))->save(['share_user_commission' => $shareUserCommission]);
                                }else{
                                    $where['uid'] = array('eq',$user['uid']);
                                    $userSpread = D('User_spread')->where($where)->find();//用户搜一级
                                }
								if(!empty($userSpread)){
									//存在一级
									$userSpreadOne = D('User_spread')->where(array('uid'=>$userSpread['spread_uid']))->find();

									if(!empty($userSpreadOne)){
										//一级
										$userOne = D('User')->field(true)->where(array('uid'=>$userSpreadOne['uid']))->find();
                                        if ($userOne && !($userMod->canSpread($userOne['uid']))) {
                                            $userOne = [];
                                        }

										if(!empty($userOne)){
											D('User')->add_money($userOne['uid'],$goodsDeatail['first_share_money']*$villageGroupOrderDetail['num'],'拼团验证消费，增加分佣');
											$spread_data = array('uid'=>$userOne['uid'],'spread_uid'=>0,'get_uid'=>$user['uid'],'money'=>$goodsDeatail['first_share_money']*$villageGroupOrderDetail['num'],'order_type'=>'vgroup','order_id'=>$order['order_id'],'third_id'=>0,'add_time'=>$_SERVER['REQUEST_TIME'],'status'=>1);
											D('User_spread_list')->data($spread_data)->add();
											$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $userOne['openid'], 'first' => L_("X1通过您的分享购买社区拼团商品，验证消费获得佣金。",array("X1" => $user['nickname'])) , 'keyword1' => $goodsDeatail['first_share_money'], 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $order['mer_id']);
										}
										//二级
										$userSpreadTwo = D('User_spread')->where(array('uid'=>$userSpreadOne['spread_uid']))->find();
										if(!empty($userSpreadTwo)){
											$userTwo = D('User')->field(true)->where(array('uid'=>$userSpreadTwo['uid']))->find();
                                            if ($userTwo && !($userMod->canSpread($userTwo['uid']))) {
                                                $userTwo = [];
                                            }
											if(!empty($userTwo)){
												D('User')->add_money($userTwo['uid'],$goodsDeatail['second_share_money']*$villageGroupOrderDetail['num'],'拼团验证消费，增加分佣');
												$spread_data = array('uid'=>$userTwo['uid'],'spread_uid'=>0,'get_uid'=>$user['uid'],'money'=>$goodsDeatail['second_share_money']*$villageGroupOrderDetail['num'],'order_type'=>'vgroup','order_id'=>$order['order_id'],'third_id'=>0,'add_time'=>$_SERVER['REQUEST_TIME'],'status'=>1);
												D('User_spread_list')->data($spread_data)->add();
												$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $userTwo['openid'], 'first' => L_("X1通过您的分享购买社区拼团商品，验证消费获得佣金。",array("X1" => $user['nickname'])) , 'keyword1' => $goodsDeatail['second_share_money'], 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $order['mer_id']);
											}
											//三级
											$userSpreadThree = D('User_spread')->where(array('uid'=>$userSpreadTwo['spread_uid']))->find();
											if(!empty($userSpreadThree)){
												$userThree = D('User')->field(true)->where(array('uid'=>$userSpreadThree['uid']))->find();
                                                if ($userThree && !($userMod->canSpread($userThree['uid']))) {
                                                    $userThree = [];
                                                }
												if(!empty($userThree)){
													D('User')->add_money($userThree['uid'],$goodsDeatail['thild_share_money']*$villageGroupOrderDetail['num'],'拼团验证消费，增加分佣');
													$spread_data = array('uid'=>$userThree['uid'],'spread_uid'=>0,'get_uid'=>$user['uid'],'money'=>$goodsDeatail['thild_share_money']*$villageGroupOrderDetail['num'],'order_type'=>'vgroup','order_id'=>$order['order_id'],'third_id'=>0,'add_time'=>$_SERVER['REQUEST_TIME'],'status'=>1);
													D('User_spread_list')->data($spread_data)->add();
													$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $userThree['openid'], 'first' => L_("X1通过您的分享购买社区拼团商品，验证消费获得佣金。",array("X1" => $user['nickname'])) , 'keyword1' => $goodsDeatail['thild_share_money'], 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $order['mer_id']);
												}
											}else{
												//没有三级结束
												$userThree = D('User')->field('uid,now_money')->where(array('uid'=>$userSpreadTwo['spread_uid']))->find();
                                                if ($userThree && !($userMod->canSpread($userThree['uid']))) {
                                                    $userThree = [];
                                                }
												if(!empty($userThree)){
													D('User')->add_money($userThree['uid'],$goodsDeatail['thild_share_money']*$villageGroupOrderDetail['num'],'拼团验证消费，增加分佣');
													$spread_data = array('uid'=>$userThree['uid'],'spread_uid'=>0,'get_uid'=>$user['uid'],'money'=>$goodsDeatail['thild_share_money']*$villageGroupOrderDetail['num'],'order_type'=>'vgroup','order_id'=>$order['order_id'],'third_id'=>0,'add_time'=>$_SERVER['REQUEST_TIME'],'status'=>1);
													D('User_spread_list')->data($spread_data)->add();
													$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $userThree['openid'], 'first' => L_("X1通过您的分享购买社区拼团商品，验证消费获得佣金。",array("X1" => $user['nickname'])) , 'keyword1' => $goodsDeatail['thild_share_money'], 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $order['mer_id']);
												}
											}
										}else{
											//二级
											$userTwo = D('User')->field('uid,now_money')->where(array('uid'=>$userSpreadOne['spread_uid']))->find();
                                            if ($userTwo && !($userMod->canSpread($userTwo['uid']))) {
                                                $userTwo = [];
                                            }
											if(!empty($userTwo)){
												D('User')->add_money($userTwo['uid'],$goodsDeatail['second_share_money']*$villageGroupOrderDetail['num'],'拼团验证消费，增加分佣');
												$spread_data = array('uid'=>$userTwo['uid'],'spread_uid'=>0,'get_uid'=>$user['uid'],'money'=>$goodsDeatail['second_share_money']*$villageGroupOrderDetail['num'],'order_type'=>'vgroup','order_id'=>$order['order_id'],'third_id'=>0,'add_time'=>$_SERVER['REQUEST_TIME'],'status'=>1);
												D('User_spread_list')->data($spread_data)->add();
												$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $userTwo['openid'], 'first' => L_("X1通过您的分享购买社区拼团商品，验证消费获得佣金。",array("X1" => $user['nickname'])) , 'keyword1' => $goodsDeatail['second_share_money'], 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $order['mer_id']);
											}
										}
									}else{
										//一级
										$userOne = D('User')->field('uid,now_money')->where(array('uid'=>$userSpread['spread_uid']))->find();
                                        if ($userOne && !($userMod->canSpread($userOne['uid']))) {
                                            $userOne = [];
                                        }
										if(!empty($userOne)){
											D('User')->add_money($userOne['uid'],$goodsDeatail['first_share_money']*$villageGroupOrderDetail['num'],'拼团验证消费，增加分佣');
											$spread_data = array('uid'=>$userOne['uid'],'spread_uid'=>0,'get_uid'=>$user['uid'],'money'=>$goodsDeatail['first_share_money']*$villageGroupOrderDetail['num'],'order_type'=>'vgroup','order_id'=>$order['order_id'],'third_id'=>0,'add_time'=>$_SERVER['REQUEST_TIME'],'status'=>1);
											D('User_spread_list')->data($spread_data)->add();
											$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $userOne['openid'], 'first' => L_("X1通过您的分享购买社区拼团商品，验证消费获得佣金。",array("X1" => $user['nickname'])) , 'keyword1' => $goodsDeatail['first_share_money'], 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $order['mer_id']);
										}

									}
								}else{
									//没有来自分享者的推广uid,无操作
								}
							}
						}

					}
					$param['name'] = $my_header['name'].' ID:'.$my_header['id'];

					$param['deliver_status'] = $status;
					$param['order_id'] = $value;
					D('Village_group')->add_log($param);

					unset($param['deliver_status'], $param['status']);
					$param['status'] = $order_status;
					$param['note'] = $param['note'].'，订单状态改变为:'.$status_txt[$order_status];
					// D('Village_group')->add_log($param);
				}

				$this->success('商品收货状态改变成功');

			} else {
				$this->error('商品收货状态改变失败');
			}
		}

	}

	//	交易记录json
	public function transaction_json(){
		$page	=	$_POST['page'];

		$condition_user_money_list['uid'] = $this->user_session['uid'];
		$condition_user_money_list['_string'] =" `desc` like '%拼团验证消费%' ";
		if($_POST['month']){
			$firstday = date("Y-m-01",strtotime($_POST['month']));
			$lastday = strtotime(date("Y-m-d",strtotime("$firstday +1 month -1 day")))+86400;
			$firstday = strtotime($firstday);
			$condition_user_money_list['_string'].=" AND (time BETWEEN {$firstday} AND {$lastday})";
		}else if($_POST['begin']){
			$firstday = strtotime($_POST['begin']);
			$lastday_info = date("Y-m-d 23:59:59",strtotime($_POST['end']));
			$lastday = strtotime($lastday_info);
			$condition_user_money_list['_string'].=" AND (time BETWEEN {$firstday} AND {$lastday})";
		}
		import('@.ORG.user_page');
		$count = D('User_money_list')->where($condition_user_money_list)->count();
		$money = D('User_money_list')->where($condition_user_money_list)->sum('money');

		$p = new Page($count,10);

		$money_list = D('User_money_list')->field(true)->where($condition_user_money_list)->order('`time` DESC')->page($page.',20')->select();

		$pagebar= $p->show();

		foreach($money_list as $k=>&$v){
			$v['time_s']	=	date('Y/m/d H:i',$v['time']);
			$v['money']	=	floatval($v['money']);
			if ($v && $v['uid']) {
				$v['user_info'] = M('User')->where(array('uid' => $v['uid']))->field('avatar, nickname')->find();
				$v['user_info']['avatar'] = replace_file_domain($v['user_info']['avatar']);
			}
		}
		echo json_encode(array('money'=>floatval($money),'money_list'=>$money_list));
	}


	public function start_list(){
		$my_header = D('Village_group')->get_header_info($this->user_session['uid']);
		if(isset($_GET['status'])){

			switch($_GET['status']){
				case 0:
					$where['o.status'] = 0;
					break;
				case 1:
					$where['o.status'] = 1;
					break;
				case 2:
					$where['o.status'] = 2;
					break;
				case 3:
					$where['o.status'] = 3;
					break;

			}
		}else{
			$where['o.status'] = 1;
		}

		$where['start_uid'] = $my_header['msg']['id'];
		$start_list = D('Village_group')->get_start_list($where);
		$this->assign('start_list',$start_list);
		$this->display();
	}

	public function check_header(){
		$my_header = D('Village_group')->get_header_info($this->user_session['uid']);
		if($my_header['error_code']){
			$this->error_tips($my_header['msg']);
		}

		return $my_header['msg'];
	}


	public function ajax_start_list(){
		if(empty($this->user_session)){
			$this->error_tips('请先进行登录！');
		}
		$my_header = D('Village_group')->get_header_info($this->user_session['uid']);
		$status = isset($_GET['status']) ? intval($_GET['status']) : 0;
		$where = "is_del=0 AND start_uid={$my_header['msg']['id']}";//array('uid' => $this->user_session['uid'], 'status' => array('lt', 3));

		if ($status == 0) {		//未成团
			$where .= " AND o.status=0";
		} elseif ($status == 1) {	//已成团
			$where .= " AND o.status=1";
		} elseif ($status == 2) {	//已超时
			$where .= " AND  o.status=2";
		}else if($status==3){	//待发货
			$where.=' and o.status=1 and deliver_status=0';
		}


		$order_list = D('Village_group')->get_start_list($where);

		$list = array();
		foreach ($order_list as $ol) {
			if (isset($m[$ol['store_id']]) && $m[$ol['store_id']]) {
				$list[] = array_merge($ol, $m[$ol['store_id']]);
			} else {
				$list[] = $ol;
			}
		}
		if(!empty($list)){
			exit(json_encode(array('status'=>1,'order_list'=>$list)));
		}else{
			exit(json_encode(array('status'=>0,'order_list'=>$list)));
		}

	}

	// 评价
	public function reply(){
		$order  = D('Village_group_order')->order_detail($_GET['order_id']);
		$header = D('Village_group')->get_header_info(0,$order['start_uid'],true);
		$header = $header['msg'];
		// var_dump($order);
		if($order['status']==0 && $order['add_time']+1800<time()){
			M('Village_group_order')->where(array('order_id'=>$_GET['order_id']))->save(array('status'=>10,'last_time'=>time()));
			$order['status'] = 10;
			$order['status_txt']  = D('Village_group')->order_status[$order['status']];
			D("System_order")->save_order_status('village_group', $_GET['order_id'], 5);
		}
		if ($order['discount_detail']) {
			$discount_detail = unserialize($order['discount_detail']);
			$good_discount = array();
			foreach ($discount_detail as $val) {
				if ($val['goods_id']) {
					$good_discount[$val['goods_id']]=$val;
				}
			}
			$this->assign('good_discount',$good_discount);
		}

		$order['pay_type_txt'] = D('Pay')->get_pay_name($order['pay_type'],1,1);
		$this->assign('label',D('Village_group')->get_label());
		$this->assign('header',$header);
		$this->assign('order',$order);
		$this->display();
	}

	// 提交评价
	public function reply_save(){
		$order  = D('Village_group_order')->order_detail($_POST['order_id']);
		if ($order['status']==13) {
			exit(json_encode(array('code'=>1,'msg'=>'您已评价，不要重复评价')));
		}

		if ($order['status']!=2) {
			exit(json_encode(array('code'=>1,'msg'=>'当前商品不能评价')));
		}
		$order_ids = $_POST['order_ids'];
		$data = array();
		foreach ($order_ids as $key => $_order_id) {
			foreach ($order['order_detail'] as $_order_detail) {
				if ($_order_detail['id'] == $_order_id) {
					$data[] = array(
						'order_id' => $_POST['order_id'],
						'order_detail_id' => $_order_id,
						'parent_id' => $_order_detail['mer_group_id'],
						'mer_id' => $_order_detail['mer_id'],
						'uid' => $this->user_session['uid'],
						'goods_id' => $_order_detail['goods_id'],
						'level' => $_POST['level'][$key],
						'add_time' => time(),
					);
				}
			}
		}
		if(M('Village_group_reply')->addAll($data)){
			D('Village_group_order')->where(array('order_id'=>$_POST['order_id']))->save(array('status'=>13));
			$param['order_id'] = $_POST['order_id'];
			$param['status'] = 13;
			$param['from_type'] = 0;
			$param['note'] = '用户评价';
			$param['name'] =$this->user_session['nickname'];
			$param['phone'] =$this->user_session['phone'];
			D('Village_group')->add_log($param);
			unset($param['status']);
			$param['deliver_status'] = 5;
			D('Village_group')->add_log($param);
			D("System_order")->save_order_status('village_group', $_POST['order_id'], 3);


			exit(json_encode(array('code'=>0,'msg'=>'评价成功')));
		}else{
			exit(json_encode(array('code'=>1,'msg'=>'评价失败')));
		}
	}

	// 团长加价
	public function increase_price(){
		$goods_id = $_POST['goods_id'];
		$increase_price = $_POST['increase_price'];
		$is_clear = $_POST['is_clear'];

		// 商品详情
		$goods = M('Village_group_goods')->where(array('goods_id'=>$goods_id))->find();
		if(empty($goods)){
			exit(json_encode(array('code'=>1,'msg'=>'该商品不存在')));
		}

		// 团长详情
		$my_header = D('Village_group')->get_header_info($this->user_session['uid']);
		if($my_header['error_code']){
			exit(json_encode(array('code'=>1,'msg'=>'您不是团长，不能修改价格')));
		}

		// 加价详情
		$goods_increase_price = M('Village_group_goods_increase_price')->where(array('goods_id'=>$goods_id,'header_uid'=>$my_header['msg']['id']))->find();
		if ($is_clear) {
			if ($goods_increase_price) {
				if(M('Village_group_goods_increase_price')->where(array('goods_id'=>$goods_id,'header_uid'=>$my_header['msg']['id']))->delete()){
					exit(json_encode(array('code'=>0,'msg'=>'清除加价成功')));
				}else{
					exit(json_encode(array('code'=>1,'msg'=>'清除加价失败')));
				}
			}else{
				exit(json_encode(array('code'=>0,'msg'=>'清除加价成功')));
			}
			
		}

		// 绑定城市详情
		if ($my_header['msg']['pick_address']['province_id'] || $my_header['msg']['pick_address']['city_id']) {
			$goods_bind_area =D('Village_group_goods_bind_area')->get_area_goods_bind_info($goods_id,intval($my_header['msg']['pick_address']['province_id']),intval($my_header['msg']['pick_address']['city_id']));
			if (!$goods_bind_area['is_add_price']) {
				exit(json_encode(array('code'=>1,'msg'=>'该商品不能修改价格')));
			}
		}else{
			exit(json_encode(array('code'=>1,'msg'=>'您还未绑定 ，不能加价')));
		}

		if ($increase_price<$goods_bind_area['min_price'] || $increase_price>$goods_bind_area['max_price'] ) {
			exit(json_encode(array('code'=>1,'msg'=>'加价金额不在加价区间内')));
		}

		$data = array();
		$data['increase_price'] = $increase_price;
		$data['last_time'] = time();
		if ($goods_increase_price) {
			$res = M('Village_group_goods_increase_price')->where(array('goods_id'=>$goods_id,'header_uid'=>$my_header['msg']['id']))->save($data);
		}else{
			$data['header_uid'] = $my_header['msg']['id'];
			$data['goods_id'] = $goods_id;
			$data['time'] = time();
			$res = M('Village_group_goods_increase_price')->add($data);
		}

		if ($res) {
			exit(json_encode(array('code'=>0,'msg'=>'加价成功')));
		}else{
			exit(json_encode(array('code'=>1,'msg'=>'加价失败')));
		}
	}

	//获取最近可配送时间
	public function lately_cycle_time() {
		$goods_id=$_POST['goods_id']>0?intval($_POST['goods_id']):0;
		$time = $_POST['time']>0?intval($_POST['time']):0;
		if (!$goods_id || !$time) {
			exit(json_encode(array('code'=>1,'msg'=>'参数错误')));
		}
		$lately_cycle_time = D('Village_group')->lately_cycle_time($goods_id,$time);
		exit(json_encode(array('code'=>0,'msg'=>'获取成功', 'lately_cycle_time' => $lately_cycle_time)));
	}

	// 用户延期
	public function village_group_delay(){
		$detail_id = $_POST['id'] > 0 ? intval($_POST['id']) : 0;
		$is_header = $_POST['is_header'] > 0 ? intval($_POST['is_header']) : 0;
		if (!$detail_id) {
			$this->error('缺少必要参数');
		}
		// 延期状态修改
		$detail = M('Village_group_order_detail')->where(array('id'=>$detail_id))->find();
		if (!$detail) {
			$this->error('需要延期的信息不存在或者已经被删除');
		}
		if ($detail['status']>0 && $detail['status']!=6) {
			$this->error('该订单已经发货，无法延期');
		}
		if ($detail['is_delay'] || $detail['status']==6) {
			$this->error('该次配送已经延期');
		}
		$order = M('Village_group_order')->where(array('order_id' => $detail['order_id']))->find();
		$header = D('Village_group')->get_header_info(0,$order['start_uid'],true);
		$header = $header['msg'];
		$delay_user = 0;
		if ($this->user_session['uid'] != $order['uid'] && $is_header<=0) {
			$this->error('您不能对当前订单进行此操作！');
		} elseif ($is_header==1 && $this->user_session['uid'] != $header['uid']) {
			$this->error('您不能对当前订单进行此操作！');
		} else {
			if ($is_header<=0 && $this->user_session['uid'] == $order['uid']) {
				$delay_user = 0;
			} elseif ($is_header==1 && $this->user_session['uid'] == $header['uid']) {
				$delay_user = 1;
			}
		}
		// 查询下用户自身延期次数 是否受限
		$delay_num = M('Village_group_order_detail')->where(array('order_id' => $detail['order_id'],'is_delay'=>1,'delay_user'=>0))->count();
		if ($delay_user==0 && $order['is_limit_delay']==1 && $delay_num>=$order['limit_delay_num']){
			$this->error('你延期次数已受限制，当前不可再延期！');
		}

		// 计算从当前天数计算备货日期节点时间
		$limit_days = intval($order['limit_days']);
		$limit_time = $order['limit_time']?$order['limit_time']:'18:00';
		$now_time = strtotime(date('Y-m-d '.$limit_time));
		if (time()>$now_time) {
            $limit_days += 1;
        }
        $now_time_day = strtotime(date('Y-m-d '));
		$now_day = strtotime("+".$limit_days." day", $now_time_day);
		$send_time = $detail['send_time'];
		if ($now_day>$send_time){
			$this->error('当前商家已经开始准备备货，无法进行延期操作！');
		}
		$save = array(
			'is_delay'=>1,
			'status'=>6,
			'delay_user'=>$delay_user,
			'delay_time'=>time()
		);
		if (M('Village_group_order_detail')->where(array('id'=>$detail_id))->data($save)->save()) {
			// 查询周期购最后一条
			$cycle_info = M('Village_group_order_detail')->where(array('order_id' => $detail['order_id']))->order('date_num DESC, send_time DESC')->find();
			if ($detail['date_num']>=$order['delivery_periods'] || M('Village_group_order_detail')->where(array('order_id' => $detail['order_id'],'is_delay'=>0,'status'=>0,'date_num'=>array('gt',$detail['date_num'])))->setDec('date_num')) {
				// 延期后的期数所有统一减一期  再在最后一期后面再生成一期
				$create_data = $detail;
				unset($create_data['id']);
				$d_village_group=D('Village_group');
				$create_data['send_time'] = $d_village_group->order_lately_cycle_time($order,$detail['goods_id'],$order['delivery_date'],true,1,$cycle_info['send_time'],$cycle_info['send_time']);
				$create_data['is_delay']=0;
				$create_data['status']=0;
				$create_data['village_group_type']=1;
				$create_data['date_num']=$order['delivery_periods'];
				$info = M('Village_group_order_detail')->add($create_data);
				if ($info) {
					$this->success('延期成功！');
				} else {
					$del_save = array(
						'is_delay'=>0,
						'status'=>$detail['status'],
						'delay_user'=>0,
						'delay_time'=>0
					);
					M('Village_group_order_detail')->where(array('id'=>$detail_id))->data($del_save)->save();
					$this->error('操作失败，请重试');
				}
			} else {
				$del_save = array(
					'is_delay'=>0,
					'status'=>$detail['status'],
					'delay_user'=>0,
					'delay_time'=>0
				);
				M('Village_group_order_detail')->where(array('id'=>$detail_id))->data($del_save)->save();
				M('Village_group_order_detail')->where(array('order_id' => $detail['order_id'],'is_delay'=>0,'status'=>0,'date_num'=>array('gt',$detail['date_num'])))->setInc('date_num');

				$this->error('操作失败，请重试');
			}
		} else {
			$this->error('操作失败，请重试');
		}
	}

	protected function get_wxapp_qrcode($path,$width){
		$wxapp_access_token = D('Access_token_wxapp_expires')->get_access_token();
		$wxapp_access_token = $wxapp_access_token['access_token'];

		import('ORG.Net.Http');
		$http = new Http();
		$url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$wxapp_access_token;
		$postData = array(
				'path'=>$path,
				'width'=>$width
		);
		$qrcode = $http->curlPostOwn($url,json_encode($postData));

		return $qrcode;
	}

	public function my_wallet() {
		$this->display();
	}


	//通过坐标获取城市  通过城市名字查询数据
	public function cityMatching(){
		$url = 'http://api.map.baidu.com/geocoder/v2/?output=json&ak=' . $this->config['baidu_map_ak'] . '&location='.$_GET['lat'].','.$_GET['lng'];
		import('ORG.Net.Http');
		$http = new Http();
		$result = $http->curlGet($url);
		$result = json_decode($result,true);
		$city_name = $result['result']['addressComponent']['city'];
		// 查询一下所在区
		$district_name = $result['result']['addressComponent']['district'];
		$long   =   strlen($city_name);
		if($long >= 7){
			$city_name    =   str_replace('市','',$city_name);
			$city_name    =   str_replace('地区','',$city_name);
			$city_name    =   str_replace('特别行政区','',$city_name);
			$city_name    =   str_replace('特別行政區','',$city_name);
			$city_name    =   str_replace('蒙古自治州','',$city_name);
			$city_name    =   str_replace('回族自治州','',$city_name);
			$city_name    =   str_replace('柯尔克孜自治州','',$city_name);
			$city_name    =   str_replace('哈萨克自治州','',$city_name);
			$city_name    =   str_replace('土家族苗族自治州','',$city_name);
			$city_name    =   str_replace('藏族羌族自治州','',$city_name);
			$city_name    =   str_replace('傣族自治州','',$city_name);
			$city_name    =   str_replace('布依族苗族自治州','',$city_name);
			$city_name    =   str_replace('苗族侗族自治州','',$city_name);
			$city_name    =   str_replace('壮族苗族自治州','',$city_name);
			$city_name    =   str_replace('黔东南苗族侗族自治州','',$city_name);
			$city_name    =   str_replace('澳门','澳門',$city_name);
			$city_name    =   str_replace('朝鲜族自治州','',$city_name);
			$city_name 	  =   str_replace('哈尼族彝族自治州','',$city_name);
			$city_name 	  =   str_replace('傣族景颇族自治州','',$city_name);
			$city_name 	  =   str_replace('藏族自治州','',$city_name);
			$city_name 	  =   str_replace('彝族自治州','',$city_name);
			$city_name 	  =   str_replace('白族自治州','',$city_name);
			$city_name 	  =   str_replace('傈僳族自治州','',$city_name);
		}
		$database_area = D('Area');
		$database_field = '`area_id`,`area_name`,`area_url`';
		$condition_all_city['area_name'] = $city_name;
		$condition_all_city['area_type'] = 2;
		$condition_all_city['is_open'] = 1;
		$oCity = $database_area->field($database_field)->where($condition_all_city)->find();

		if(!$oCity){
			exit(json_encode(array('error'=>2,'msg'=>'当前城市未开启，请选择别的城市')));
		}
		$condition_all_district['area_name'] = $district_name;
		$condition_all_district['area_type'] = 3;
		$condition_all_district['is_open'] = 1;
		$oDistrict = $database_area->field($database_field)->where($condition_all_district)->find();
		if(!$oDistrict){
			exit(json_encode(array('error'=>2,'msg'=>'当前城市区域未开启，请选择别的城市或区域')));
		}
		if($oCity){
			exit(json_encode(array('error'=>1,'msg'=>'获取成功','area_id'=>$oCity['area_id'],'area_name'=>$oCity['area_name'], 'now_area_id' => $oDistrict['area_id'])));
		}else{
			exit(json_encode(array('error'=>2,'msg'=>'当前城市未开启，请选择别的城市')));
		}

	}

	/* 	团长查看订单统计
		* 今天 本月 累计
		* 订单数 订单金额（实际支付金额） 商品数
	*/
	public function ajax_statistics() {
		// 团长详情
		$my_header = D('Village_group')->get_header_info($this->user_session['uid']);
		if($my_header['error_code']){
			exit(json_encode(array('code'=>1,'msg'=>'您不是团长，不能查看统计')));
		}

		// 查询订单
		$where['start_uid'] = $my_header['id'];
		$where['status'] = array('in','2,3,13');
		$return = array();
		// 总订单数
		$where['all_order_count'] = M('Village_group_order')->where($where)->count();


		// 今日订单数
		$start_time = strtotime(date('Y-m-d'));
		$end_time = time();
		$where['pay_time'] = array('between',array($start_time,$end_time));
		$where['month_order_count'] = M('Village_group_order')->where($where)->count();
		$where['month_order_momey'] = M('Village_group_order')->field('sum(discount_price) as total_money')->where($where)->select();
		$where['month_order_momey'] = $where['month_order_momey'][0]['total_money'];
		

	}


	//核销已删除团长的待核销的订单(手动执行)
    function auto_verify() {
	    $heardList = M('Village_group_header')->where('status',4)->field('id')->select();
        $heardIds = implode(',', array_column($heardList,'id'));
	    $orderList = M('Village_group_order')->where("status = 6 and start_uid in (" . $heardIds . ")")->field('order_id')->select();
	    if ($orderList) {
            foreach ($orderList as $v) {
                $this->ajax_verify1($v['order_id']);
            }
        }
    }

    function ajax_verify1($order_id){
        $res = M('Village_group_order')->where(array('order_id'=>$order_id))->find();
        $my_header = D('Village_group')->get_header_info($this->user_session['uid'], $res['start_uid']);
        $my_header = $my_header['msg'];
        $order_detail = D('Village_group')->get_order_detail($res['order_id']);
        if(empty($res)){
            $this->error_tips('订单不存在');
        }

        if($res['status']==0){
            $this->error_tips('订单未支付');
        }

        if($res['status']==2){
            $this->error_tips('订单已消费');
        }

            $order = M('Village_group_order')->where(array('order_id' => $order_id))->find();
            if ($order && $order['village_group_type']==1) {
                $detail_where = array(
                    'order_id'=>$res['order_id'],
                    'status' => array('lt', 3),
                    'date_num' => $order['newest_delivery_periods']>0?$order['newest_delivery_periods']:1,
                    'is_delay'=>0
                );
                // 查询下现有状态
                $info = M('Village_group_order_detail')->where($detail_where)->find();
                if ($info['status'] != 2) {
                    $this->error('当前周期购商品不能点击核销，请不要过于频繁操作!');
                }
                $save = M('Village_group_order_detail')->where($detail_where)->setField('status',3);
                $param['goods_id'] = $info['goods_id'];
                $param['date_num'] =$order['newest_delivery_periods']>0?$order['newest_delivery_periods']:1;
            }elseif($order['village_group_type']==0){
                $href            = C('config.site_url') . '/wap.php?g=Wap&c=My&a=spread_list&status=-1';
                //数据库0普通类型  =>自提订单 =郭先森 ==分佣金
                $villageGroupOrderDetail = M('Village_group_order_detail')->where(array('order_id'=>$order['order_id']))->select();
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                foreach($villageGroupOrderDetail as $k=>$v){
                    $goodsDeatail = M('Village_group_goods')->field('first_share_money,second_share_money,thild_share_money')->where(array('goods_id'=>$v['goods_id']))->find();

                    if($goodsDeatail){
                        $goodsDeatail['first_share_money'] = $goodsDeatail['first_share_money'] ? $goodsDeatail['first_share_money']*$v['num'] : 0;
                        $goodsDeatail['second_share_money'] = $goodsDeatail['second_share_money'] ? $goodsDeatail['second_share_money']*$v['num'] : 0;
                        $goodsDeatail['thild_share_money'] = $goodsDeatail['thild_share_money'] ? $goodsDeatail['thild_share_money']*$v['num'] : 0;
                        $user = D('User')->where(array('uid'=>$order['uid']))->find();
                        $where['uid'] = array('eq',$user['uid']);
                        $userSpread = D('User_spread')->where($where)->find();//用户搜一级
                        $userMod = new UserModel();
                        if(!empty($userSpread)){
                            //存在一级
                            $userSpreadOne = D('User_spread')->where(array('uid'=>$userSpread['spread_uid']))->find();

                            if(!empty($userSpreadOne)){
                                //一级
                                $userOne = D('User')->field(true)->where(array('uid'=>$userSpreadOne['uid']))->find();
                                if ($userOne && !($userMod->canSpread($userOne['uid']))) {
                                    $userOne = [];
                                }

                                if(!empty($userOne)){
                                    D('User')->add_money($userOne['uid'],$goodsDeatail['first_share_money'],'拼团验证消费，增加分佣');
                                    $spread_data = array('uid'=>$userOne['uid'],'spread_uid'=>0,'get_uid'=>$user['uid'],'money'=>$goodsDeatail['first_share_money'],'order_type'=>'vgroup','order_id'=>$order['order_id'],'third_id'=>0,'add_time'=>$_SERVER['REQUEST_TIME'],'status'=>1);
                                    D('User_spread_list')->data($spread_data)->add();
                                    $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $userOne['openid'], 'first' => L_("X1通过您的分享购买社区拼团商品，验证消费获得佣金。",array("X1" => $user['nickname'])) , 'keyword1' => $goodsDeatail['first_share_money'], 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $order['mer_id']);
                                }
                                //二级
                                $userSpreadTwo = D('User_spread')->where(array('uid'=>$userSpreadOne['spread_uid']))->find();
                                if(!empty($userSpreadTwo)){
                                    $userTwo = D('User')->field(true)->where(array('uid'=>$userSpreadTwo['uid']))->find();
                                    if ($userTwo && !($userMod->canSpread($userTwo['uid']))) {
                                        $userTwo = [];
                                    }
                                    if(!empty($userTwo)){
                                        D('User')->add_money($userTwo['uid'],$goodsDeatail['second_share_money'],'拼团验证消费，增加分佣');
                                        $spread_data = array('uid'=>$userTwo['uid'],'spread_uid'=>0,'get_uid'=>$user['uid'],'money'=>$goodsDeatail['second_share_money'],'order_type'=>'vgroup','order_id'=>$order['order_id'],'third_id'=>0,'add_time'=>$_SERVER['REQUEST_TIME'],'status'=>1);
                                        D('User_spread_list')->data($spread_data)->add();
                                        $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $userTwo['openid'], 'first' => L_("X1通过您的分享购买社区拼团商品，验证消费获得佣金。",array("X1" => $user['nickname'])) , 'keyword1' => $goodsDeatail['second_share_money'], 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $order['mer_id']);
                                    }
                                    //三级
                                    $userSpreadThree = D('User_spread')->where(array('uid'=>$userSpreadTwo['spread_uid']))->find();
                                    if(!empty($userSpreadThree)){
                                        $userThree = D('User')->field(true)->where(array('uid'=>$userSpreadThree['uid']))->find();
                                        if ($userThree && !($userMod->canSpread($userThree['uid']))) {
                                            $userThree = [];
                                        }
                                        if(!empty($userThree)){
                                            D('User')->add_money($userThree['uid'],$goodsDeatail['thild_share_money'],'拼团验证消费，增加分佣');
                                            $spread_data = array('uid'=>$userThree['uid'],'spread_uid'=>0,'get_uid'=>$user['uid'],'money'=>$goodsDeatail['thild_share_money'],'order_type'=>'vgroup','order_id'=>$order['order_id'],'third_id'=>0,'add_time'=>$_SERVER['REQUEST_TIME'],'status'=>1);
                                            D('User_spread_list')->data($spread_data)->add();
                                            $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $userThree['openid'], 'first' => L_("X1通过您的分享购买社区拼团商品，验证消费获得佣金。",array("X1" => $user['nickname'])) , 'keyword1' => $goodsDeatail['thild_share_money'], 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $order['mer_id']);
                                        }
                                    }else{
                                        //没有三级结束
                                        $userThree = D('User')->field('uid,now_money')->where(array('uid'=>$userSpreadTwo['uid']))->find();
                                        if ($userThree && !($userMod->canSpread($userThree['uid']))) {
                                            $userThree = [];
                                        }
                                        if(!empty($userThree)){
                                            D('User')->add_money($userThree['uid'],$goodsDeatail['thild_share_money'],'拼团验证消费，增加分佣');
                                            $spread_data = array('uid'=>$userThree['uid'],'spread_uid'=>0,'get_uid'=>$user['uid'],'money'=>$goodsDeatail['thild_share_money'],'order_type'=>'vgroup','order_id'=>$order['order_id'],'third_id'=>0,'add_time'=>$_SERVER['REQUEST_TIME'],'status'=>1);
                                            D('User_spread_list')->data($spread_data)->add();
                                            $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $userThree['openid'], 'first' => L_("X1通过您的分享购买社区拼团商品，验证消费获得佣金。",array("X1" => $user['nickname'])) , 'keyword1' => $goodsDeatail['thild_share_money'], 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $order['mer_id']);
                                        }
                                    }
                                }else{
                                    //二级
                                    $userTwo = D('User')->field('uid,now_money')->where(array('uid'=>$userSpreadOne['spread_uid']))->find();
                                    if ($userTwo && !($userMod->canSpread($userTwo['uid']))) {
                                        $userTwo = [];
                                    }
                                    if(!empty($userTwo)){
                                        D('User')->add_money($userTwo['uid'],$goodsDeatail['second_share_money'],'拼团验证消费，增加分佣');
                                        $spread_data = array('uid'=>$userTwo['uid'],'spread_uid'=>0,'get_uid'=>$user['uid'],'money'=>$goodsDeatail['second_share_money'],'order_type'=>'vgroup','order_id'=>$order['order_id'],'third_id'=>0,'add_time'=>$_SERVER['REQUEST_TIME'],'status'=>1);
                                        D('User_spread_list')->data($spread_data)->add();
                                        $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $userTwo['openid'], 'first' => L_("X1通过您的分享购买社区拼团商品，验证消费获得佣金。",array("X1" => $user['nickname'])) , 'keyword1' => $goodsDeatail['second_share_money'], 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $order['mer_id']);
                                    }
                                }
                            }else{
                                //一级
                                $userOne = D('User')->field('uid,now_money')->where(array('uid'=>$userSpread['spread_uid']))->find();
                                if ($userOne && !($userMod->canSpread($userOne['uid']))) {
                                    $userOne = [];
                                }
                                if(!empty($userOne)){
                                    D('User')->add_money($userOne['uid'],$goodsDeatail['first_share_money'],'拼团验证消费，增加分佣');
                                    $spread_data = array('uid'=>$userOne['uid'],'spread_uid'=>0,'get_uid'=>$user['uid'],'money'=>$goodsDeatail['first_share_money'],'order_type'=>'vgroup','order_id'=>$order['order_id'],'third_id'=>0,'add_time'=>$_SERVER['REQUEST_TIME'],'status'=>1);
                                    D('User_spread_list')->data($spread_data)->add();
                                    $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $userOne['openid'], 'first' => L_("X1通过您的分享购买社区拼团商品，验证消费获得佣金。",array("X1" => $user['nickname'])) , 'keyword1' => $goodsDeatail['first_share_money'], 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $order['mer_id']);
                                }

                            }
                        }else{
                            //没有来自分享者的推广uid,无操作
                        }
                    }
                }
                $save = M('Village_group_order_detail')->where(array('order_id'=>$order_id,'status'=>array('lt',3)))->setField('status',3);
            } else {
                $save = M('Village_group_order_detail')->where(array('order_id'=>$order_id,'status'=>array('lt',3)))->setField('status',3);
            }

            fdump($save,'group1');
            if($save!==false){
                $param['from_type'] =2;
                $param['deliver_status'] = 3;
                $param['note'] = '团长点击商品用户确认自提';
                $param['name'] = $my_header['name'].' ID:'.$my_header['id'];
                $param['order_id'] = $order_id;
                D('Village_group')->add_log($param);

                fdump($res,'group1',1);
                $order_detail = D('Village_group')->get_order_detail($res['order_id']);
                fdump($order_detail,'group1',1);
                $this->verify_notice($order_detail,$res,$my_header,2);
                $this->success('验证成功');
            }else{
                $this->success('验证失败，请重试');
            }
        }


}
