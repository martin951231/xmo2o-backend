<?php
/*
 * app端前台接口----景点
 *   Writers    hanlu
 *   BuildTime  2016/10/26 9:50
 */
class Scenic_listAction extends BaseAction{
	# 景区列表
    public function index(){
		$city_id['city_id']	=	$_POST['city_id'];
		$city_id['scenic_status']	=	1;
		$page	=	$_POST['page'];
		$count	=	10;
		if(empty($page)){
			$page = 0;
		}else{
			$page	=	($page-1)*$count;
		}
		$limit	=	$page.','.$count;
		$long	=	$_POST['long'];
		$lat	=	$_POST['lat'];
		if($lat && $long){
			$order	=	'juli';
		}else{
			$order	=	'sort DESC';
		}
		if($_POST['is_hot']){
			$city_id['is_hot']	=	1;
		}
		$long_lat['long']	=	$long;
		$long_lat['lat']	=	$lat;
		$scenic_list	=	D('Scenic_list')->get_all_list($city_id,$limit,$order,$long_lat);
		if($scenic_list){
			$scenic_image_class = new scenic_image();
			foreach($scenic_list as &$v){
				$tmp_pic_arr = explode(';',$v['scenic_pic']);
				$v['money'] = floatval($v['money']);
				$v['pic'] = $scenic_image_class->get_image_by_path($tmp_pic_arr[0],$this->config['site_url'],'config','s');
				$v['range_txt'] = $this->wapFriendRange($v['juli']);
				$v['ticket'] = D('Scenic_ticket')->get_scenic_ticket_all(array('scenic_id'=>$v['scenic_id'],'ticket_status'=>1),array('ticket_id','ticket_title','ticket_price','is_children'),'is_children DESC,ticket_sort DESC',1);
				$v['ticket_number']	=	count($v['ticket']);
				unset($v['scenic_pic'],$v['city_id'],$v['level'],$v['now_money']);
			}
		}
		$count	=	M('Scenic_list')->where($city_id)->count();
		$arr	=	array(
			'scenic_list'	=>	$scenic_list,
			'count'	=>	ceil($count/10),
		);
		$this->returnCode(0,$arr);
    }
	# 景点详情
	public function details(){
		# 获取景点详情
		$scenic	=	$_POST['scenic_id'];
		$ticket = I('ticket', false);
		if ($ticket) {
            $info = ticket::get($ticket, $this->DEVICE_ID, true);
            if ($info) {
                $user_id = $info['uid'];
            }
        }
		$scenic_id['scenic_id']	=	$scenic;
		$field	=	array('scenic_id','scenic_title','scenic_pic','scenic_address','scenic_intr','scenic_explain','score_mean','province_id','city_id','area_id','reply_count','score_mean');
		$scenic_list	=	D('Scenic_list')->get_one_list($scenic_id,$field);
		if(empty($scenic_list)){
			$this->returnCode('40000036');
		}else{
			M('Scenic_list')->where($scenic_id)->setInc('hits');
			$tmp_pic_arr = explode(';',$scenic_list['scenic_pic']);
			$scenic_list['scenic_intr'] = htmlspecialchars_decode($scenic_list['scenic_intr']);
			$scenic_list['scenic_explain'] = htmlspecialchars_decode($scenic_list['scenic_explain']);
			$scenic_image_class = new scenic_image();
			foreach($tmp_pic_arr as $k=>$x){
				$scenic_list['pic'][$k] = $scenic_image_class->get_image_by_path($x,$this->config['site_url'],'config','s');
			}
			$half_stars	=		explode(".",$scenic_list['score_mean']);
			for($i=0;$i<$half_stars[0];$i++){
				$one[]	=	1;
			}
			if($half_stars[1] != 0){
				$two	=	1;
			}else{
				$two	=	0;
			}
			$three	=	5-$half_stars[0]-$two;
			for($i=0;$i<$three;$i++){
				$three[]	=	1;
			}
			//if($scenic_list['score_mean'] == '0.0'){
//				$scenic_list['gray_stars']	=	(string)5;
//				$scenic_list['score_mean']	=	'0.0';
//			}else{
//				$scenic_list['red_stars']	=	floor($scenic_list['score_mean']);
//				$half_stars	=		explode(".",$scenic_list['score_mean']);
//				if($half_stars[1] == 0){
//					$scenic_list['half_stars']	=	(string)0;
//				}else{
//					$scenic_list['half_stars']	=	(string)1;
//				}
//				if($scenic_list['half_stars'] == 1){
//					$scenic_list['gray_stars']	=	(string)5-$scenic_list['red_stars']-1;
//				}else{
//					$scenic_list['gray_stars']	=	(string)5-$scenic_list['red_stars'];
//				}
//			}
			if($user_id){
				$collection	=	M('Scenic_collection')->field('collection_id')->where(array('scenic_id'=>$scenic,'user_id'=>$user_id))->find();
				if($collection){
					$scenic_list['collection']	=	(string)1;
				}else{
					$scenic_list['collection']	=	(string)0;
				}
			}else{
				$scenic_list['collection']	=	(string)0;
			}
			$scenic_list['red_stars']	=	(string)$scenic_list['red_stars'];
			$province_id	=	D('Area')->scenic_get_one_city($scenic_list['province_id'],0);
			$city_id	=	D('Area')->scenic_get_one_city($scenic_list['city_id'],0);
			$area_id	=	D('Area')->scenic_get_one_city($scenic_list['area_id'],0);
			$scenic_list['addr']	=	$province_id['area_name'].$city_id['area_name'].$area_id['area_name'].$scenic_list['scenic_address'];
			unset($scenic_list['province_id'],$scenic_list['area_id'],$scenic_list['scenic_pic'],$scenic_list['scenic_address']);
		}
		# 成人门票
		$scenic_id['ticket_status']	=	1;
		$field	=	array('ticket_id','ticket_title','old_price','ticket_price','end_time');
		$scenic_ticket	=	D('Scenic_ticket')->get_scenic_ticket_list($scenic_id,2,$field,1);
		$scenic_list['ticket_number']	=	count($scenic_ticket);
		# 儿童门票
		$scenic_ticket_children	=	D('Scenic_ticket')->get_scenic_ticket_list($scenic_id,1,$field,1);
		$scenic_list['ticket_children_number']	=	count($scenic_ticket_children);
		# 获取就近推荐
		$long_lat = array(
			'long'	=>	$_POST['user_long'],
			'lat'	=>	$_POST['user_lat'],
		);
		if($long_lat['long'] && $long_lat['lat']){
			$nearby	=	D('Scenic_list')->nearby($long_lat);
		}else{
			$nearby	=	D('Scenic_list')->recommend($scenic_list['city_id'],1);
		}
		if($nearby){
			foreach($nearby as $kk=>&$z){
				if($z['scenic_id']==$scenic_list['scenic_id']){
					unset($nearby[$kk]);
					continue;
				}
				$tmp_pic_arr = explode(';',$z['scenic_pic']);
				$z['money'] = floatval($z['money']);
				$z['pic'] = $scenic_image_class->get_image_by_path($tmp_pic_arr[0],$this->config['site_url'],'config','s');
				if(empty($z['money'])){
					$z['money']	=	M('Scenic_ticket')->where(array('scenic_id'=>$z['scenic_id']))->order('ticket_price')->getField('ticket_price');
				}
				$z['money'] = floatval($z['money']);
				unset($z['scenic_pic'],$z['level'],$z['is_broadcast'],$z['is_parking'],$z['is_guide']);
			}
			$nearby = array_values($nearby);
		}
		# 活动
		$field	=	array('activity_id','activity_img','activity_title','sign_number','activity_stitle','start_time');
		$activity	=	D('Scenic_activity')->get_all_list(array('scenic_id'=>$scenic,'status'=>1),2,'start_time',$field);
		if($activity){
			foreach($activity as $k=>&$v){
				if(empty($v)){
					continue;
				}
				$tmp_pic_arr = explode(';',$v['activity_img']);
				$v['activity_img'] = $scenic_image_class->get_image_by_path($tmp_pic_arr[0],$this->config['site_url'],'activity','s');
				$v['start_time']	=	date('Y-m-d H:i',$v['start_time']);
			}
		}
		# 推荐
		$group_category = M('Scenic_com_category')->field(array('cat_id','cat_name'))->where(array('scenic_id'=>$scenic,'is_recom'=>1,'status'=>1))->order('cat_sort DESC,cat_id DESC')->select();
		$arr_category	=	array();
		if($group_category){
			foreach($group_category as $vv){
				$list	=	D('Scenic_com')->field(array('com_id','com_name','com_img','cat_id','price'))->where(array('cat_id'=>$vv['cat_id'],'is_recom'=>1))->order('sort DESC,com_id DESC')->select();
				if(empty($list)){
					continue;
				}
				$store_image_class = new scenic_image();
				foreach($list as $e=>&$vvv){
					$vvv['pic'] = $store_image_class->get_image_by_path($vvv['com_img'],$this->config['site_url'],'com','1');
					if($e == 0){
						$cat_id = $vvv['cat_id'];
					}
					unset($vvv['com_img'],$vvv['cat_id']);
				}
				$arr_category[]	=	array(
					'list'	=>	$list,
					'color'	=>	$this->config['site_url'].'/static/weather/jdxqxt_'.rand(0,1).rand(0,9).'.png',
					'cat_name'	=>	$vv['cat_name'],
					'cat_id'	=>	$cat_id,
				);
			}
		}
		$arr	=	array(
			'scenic_list'	=>	$scenic_list,
			'scenic_ticket'	=>	isset($scenic_ticket)?$scenic_ticket:array(),
			'scenic_ticket_children'	=>	isset($scenic_ticket_children)?$scenic_ticket_children:array(),
			'nearby'	=>	isset($nearby)?$nearby:array(),
			'activity'	=>	isset($activity)?$activity:array(),
			'arr_category'	=>	isset($arr_category)?$arr_category:array(),
			'one'	=>	$one,
			'two'	=>	$two,
			'three'	=>	$three,
		);
		$this->returnCode(0,$arr);
	}
	# 门票订单填写
	public function order(){
		$ticket = I('ticket', false);
		if ($ticket) {
            $info = ticket::get($ticket, $this->DEVICE_ID, true);
            if ($info) {
                $user_id = $info['uid'];
                $user	=	M('User')->field(true)->where(array('uid'=>$user_id))->find();
            }
        }else{
			$this->returnCode('20044013');
        }
		#门票
		$where['ticket_id']	=	$_POST['ticket_id'];
		if(empty($where['ticket_id'])){
			$this->returnCode('40000037');
		}
		$field	=	array('ticket_id','ticket_title','ticket_explain','ticket_cue','park_intr','serialize','ticket_price','end_time','scenic_id');
		$scenic_ticket	=	D('Scenic_ticket')->get_scenic_one_ticket($where,$field);
		if(empty($scenic_ticket)){
			$this->returnCode('40000037');
		}
		if($scenic_ticket['ticket_status'] == 2){
			$this->returnCode('40000038');
		}
		$scenic_ticket['ticket_explain'] = htmlspecialchars_decode($scenic_ticket['ticket_explain']);
		$scenic_ticket['ticket_cue'] = htmlspecialchars_decode($scenic_ticket['ticket_cue']);
		$scenic_ticket['park_intr'] = htmlspecialchars_decode($scenic_ticket['park_intr']);
		$scenic_ticket['ticket_price']	=	intval($scenic_ticket['ticket_price']);
		$scenic_ticket['serialize']	=	unserialize($scenic_ticket['serialize']);
//		$scenic_ticket['serialize']	=	json_encode($scenic_ticket['serialize']);
		$scenic_ticket['end_time']	=	substr($scenic_ticket['end_time'],0,5);
		$scenic_ticket['date_time']	=	date('Y-m-d',$_SERVER['REQUEST_TIME']);
		$scenic_ticket['user_name']	=	$user['truename'];
		$scenic_ticket['user_phone']=	$user['phone'];
		# 景区
		$where['scenic_id']	=	$scenic_ticket['scenic_id'];
		$scenic_list	=	D('Scenic_list')->get_one_list($where);
		$scenic_ticket['parking_price']	=	$scenic_list['parking_price'];
		$scenic_ticket['guide_price']	=	$scenic_list['guide_price'];
//		$scenic_ticket['total_price']	=	$scenic_ticket['ticket_price']+$scenic_list['parking_price']+$scenic_list['guide_price'];
		if($scenic_list['is_parking'] == 1){
			$field = array('parking_id','parking_name','parking_address','parking_count');
			$scenic_park	=	D('Scenic_park')->get_user_park($where,$field);
		}else{
			$scenic_park	=	array();
		}
		if($scenic_list['is_guide'] == 1){
			$field = array('guide_id','guide_name','guide_gender','guide_age','guide_pig','guide_intr','guide_service_number');
			$scenic_guide	=	D('Scenic_guide')->city_get_all_guide($where,$field);
			$scenic_image_class = new scenic_image();
			foreach($scenic_guide as $k=>&$v){
				$v['pic'] = $scenic_image_class->get_image_by_path($v['guide_pig'],$this->config['site_url'],'guide','s');
				if($k == 0){
					$v['is_v']	=	1;
				}else{
					$v['is_v']	=	0;
				}
				if($v['guide_gender'] == 1){
					$v['gender_img'] = $this->config['site_url'].'/tpl/Wap/pure/static/scenic/images/dixdt_01.png';
				}else{
					$v['gender_img'] = $this->config['site_url'].'/tpl/Wap/pure/static/scenic/images/dixdt_02.png';
				}
				unset($v['guide_pig']);
			}
		}else{
			$scenic_guide	=	array();
		}
		$scenic_family	=	D('Scenic_family')->get_all_list(array('user_id'=>$user_id),array('family_id','family_name','certificates'));
		foreach($scenic_family as &$tt){
			$tt['is_secelt']	=	0;
		}
		$ticket_price_year	=	$this->back_year($scenic_ticket['serialize'],$scenic_ticket['ticket_price']);
		$arr	=	array(
			'scenic_ticket'	=>	$scenic_ticket,
			'scenic_park'	=>	$scenic_park,
			'scenic_guide'	=>	$scenic_guide,
			'ticket_price_year'	=>	$ticket_price_year,
			'scenic_family'	=>	isset($scenic_family)?$scenic_family:array(),
		);
		$this->returnCode(0,$arr);
	}
	# 往后找一年
	public function back_year($select,$price){
		$year	=	date('Y',$_SERVER['REQUEST_TIME']);
		$year	=	$year+1;
		$day	=	date('d',$_SERVER['REQUEST_TIME']);
		$StartMonth   = date('Y-m-d',$_SERVER['REQUEST_TIME']); //开始日期
		$EndMonth     = date('-m-d',$_SERVER['REQUEST_TIME']); //结束日期
		$EndMonth	=	$year.$EndMonth;
		$ToStartMonth = strtotime( $StartMonth ); //转换一下
		$ToEndMonth   = strtotime( $EndMonth ); //一样转换一下
		$i            = false; //开始标示
		while( $ToStartMonth < $ToEndMonth ) {
			$NewMonth = !$i ? date('Y-m', strtotime('+0 Month', $ToStartMonth)) : date('Y-m', strtotime('+1 Month', $ToStartMonth));
			$ToStartMonth = strtotime( $NewMonth );
			$i = true;
			$date[]	=	$NewMonth;
		}
		foreach($date as $k=>&$v){
			$yrea	=	explode('-',$v);
			$data_number	=	cal_days_in_month(CAL_GREGORIAN,$yrea[1],$yrea[0]);
			$z	=	1;
			for($y==1;$y<$data_number;$y++){
				if($z < 10){
					$q	=	'0'.$z;
				}else{
					$q	=	$z;
				}
				$son_year	=	$yrea[0].'-'.$yrea[1].'-'.$q;
				if($k==0 && $q<$day){

				}else{
					foreach($select as $sele){
						if($son_year == $sele['Date']){
							$pric	=	$sele['Price'];
						}
					}
					if(empty($pric)){
						$pric	=	$price;
					}
					$son[]	=	array(
						'date'	=>	$son_year,
						'price'	=>	$pric,
					);
					unset($pric,$son_year,$q);
				}
				$z++;
			}
			if($k == 0){
				$is_select	=	1;
			}else{
				$is_select	=	0;
			}
			$arr[]	=	array(
				'date'	=>	$yrea[0].L_('年').$yrea[1].L_('月'),
				'is_date'	=>	$v,
				'date_number'	=>	$data_number,
				'is_select'	=>	$is_select,
				'son'	=>	$son
			);
			unset($yrea,$son,$y);
		}
		//$year	=	date('Y',$_SERVER['REQUEST_TIME']);
//		$month	=	date('m',$_SERVER['REQUEST_TIME']);
//		$day	=	date('d',$_SERVER['REQUEST_TIME']);
//		$month_bad	=	12-$month;
//		for($i==0;$month_bad>$i;$i++){
//			$arr[]	=	$year.'-'.$month;
//			$month++;
//		$this->returnCode(0,$arr);
//		}
		return $arr;
	}
	# 门票订单填写
	public function order_json(){
		$ticket = I('ticket', false);
		if ($ticket) {
            $info = ticket::get($ticket, $this->DEVICE_ID, true);
            if ($info) {
                $user_id = $info['uid'];
            }
        }else{
			$this->returnCode('20044013');
        }
		$where['ticket_id']	=	$_POST['ticket_id'];
		$ticket_num		=	$_POST['num'];
		if(empty($where['ticket_id'])){
			$this->returnCode('40000037');
		}
		$scenic_ticket	=	D('Scenic_ticket')->get_scenic_one_ticket($where);
		if(empty($scenic_ticket)){
			$this->returnCode('40000037');
		}
		if($scenic_ticket['ticket_status'] == 2){
			$this->returnCode('40000038');
		}
		$scenic_list	=	D('Scenic_list')->get_one_list(array('scenic_id'=>$scenic_ticket['scenic_id']));
		$serialize	=	unserialize($scenic_ticket['serialize']);
		$ticket_time	=	explode('-',$_POST['ticket_time']);
		if($ticket_time[1] < 10){
			$ticket_time[1]	=	'0'.$ticket_time[1];
		}
		if($ticket_time[2] < 10){
			$ticket_time[2]	=	'0'.$ticket_time[2];
		}
		$ticket_time	=	$ticket_time[0].'-'.$ticket_time[1].'-'.$ticket_time[2];
		if($serialize){
			foreach($serialize as $v){
				if($ticket_time == $v['Date']){
					$ticket_price	=	$v['Price'];
					break;
				}else{
					$ticket_price	=	$scenic_ticket['ticket_price'];
				}
			}
		}else{
			$ticket_price	=	$scenic_ticket['ticket_price'];
		}
		# 总订单
		$data['ticket_id']	=	$_POST['ticket_id'];
		$data['family_id']	=	$_POST['family_id'];
		$data['truename']	=	$_POST['truename'];
		$data['phone']		=	$_POST['phone'];
		$data['scenic_id']	=	$scenic_list['scenic_id'];
		$data['ticket_time']=	$_POST['ticket_time'];
		$data['user_id']	=	$user_id;
		$data['add_time']	=	$_SERVER['REQUEST_TIME'];
		$data['order_imm']	=	date('YmdHis',$_SERVER['REQUEST_TIME']).rand(100,999);
		$data['paid']		=	1;
		$data['order_status']=	0;
		if($_POST['park'] != 0){
			$park	=	$_POST['park']*$scenic_list['parking_price'];
		}
		if($_POST['guide_price'] == 1){
			$guide_price	=	$scenic_list['guide_price'];
		}
		$data['order_total']	=	$ticket_price*$ticket_num+$park+$guide_price;
		$add_order	=	D('Scenic_order')->add_order($data);
		if($add_order){
			$order_id	=	D('Scenic_order')->getLastInsID();
			# 门票订单
			for($i=0;$i<$ticket_num;$i++){
				$data_com	=	array(
					'order_id'	=>	$order_id,
					'type_id'	=>	$_POST['ticket_id'],
					'price'		=>	$ticket_price,
					'type'		=>	1,
					'status'	=>	1,
				);
				$add_order_com	=	D('Scenic_order')->add_order_com($data_com);
				if(empty($add_order_com)){
					$this->returnCode('40000003');
				}
			}
			# 车位订单
			if($_POST['park'] != 0){
				$scenic_guide_number	=	explode(',',$_POST['array']);
				for($y=0;$y<$_POST['park'];$y++){
					$data_park	=	array(
						'order_id'	=>	$order_id,
						'type_id'	=>	$_POST['park_id'],
						'price'		=>	$scenic_list['parking_price'],
						'code'		=>	$scenic_guide_number[$y],
						'type'		=>	2,
						'status'	=>	1,
					);
					$add_order_com	=	D('Scenic_order')->add_order_com($data_park);
					if(empty($add_order_com)){
						$this->returnCode('40000004');
					}
				}
			}
			# 导游订单
			if($_POST['guide_price'] == 1){
				$data_guide	=	array(
					'order_id'	=>	$order_id,
					'type_id'	=>	$_POST['guide_id'],
					'price'		=>	$scenic_list['guide_price'],
					'type'		=>	3,
					'status'	=>	1,
				);
				$add_order_com	=	D('Scenic_order')->add_order_com($data_guide);
				if(empty($add_order_com)){
					$this->returnCode('40000005');
				}
			}
			# 结伴
			if(!empty($_POST['mate'])){
				$data_mate	=	array(
					'province_id'	=>	$scenic_list['province_id'],
					'city_id'	=>	$scenic_list['city_id'],
					'scenic_id'	=>	$scenic_list['scenic_id'],
					'order_id'	=>	$order_id,
					'mate_intr'	=>	$_POST['mate'],
					'start_time'	=>	$_POST['ticket_time'],
					'mate_status'	=>	0,
					'create_time'	=>	$_SERVER['REQUEST_TIME'],
				);
				$scenic_mate	=	D('Scenic_mate')->add_scenic_mate($data_mate);
				$mate_order_id	=	D('Scenic_mate')->getLastInsID();
				if($scenic_mate){
					$data_mate_order	=	array(
						'mate_id'	=>	$mate_order_id,
						'user_id'	=>	$user_id,
						'rela_status'	=>	1,
						'phone'		=>	$_POST['phone'],
						'is_mate'	=>	1,
						'create_time'	=>	$_SERVER['REQUEST_TIME'],
					);
					D('Scenic_mate')->add_scenic_mate_order($data_mate_order);
				}
			}
		}else{
			$this->returnCode('40000002');
		}
		if(empty($this->user_session['phone'])){
			$user_info['phone']	=	$_POST['phone'];
		}
		if(empty($this->user_session['nickname'])){
			$user_info['nickname']	=	$_POST['name'];
		}
		if($user_info){
			$scenic_save_user	=	D('User')->scenic_save_user($this->user_session['uid'],$user_info);
			if($scenic_save_user){
				$_SESSION['phone']	=	$_POST['phone'];
				$_SESSION['nickname']	=	$_POST['name'];
			}
		}
		$this->returnCode(0);
	}
	# 收藏接口
	public function collection_json(){
		$ticket = I('ticket', false);
		if ($ticket) {
            $info = ticket::get($ticket, $this->DEVICE_ID, true);
            if ($info) {
                $user_id = $info['uid'];
            }
        }else{
			$this->returnCode('20044013');
        }
		if($_POST['type'] == 1){
			$arr	=	array(
				'user_id'	=>	$user_id,
				'scenic_id'	=>	$_POST['scenic_id'],
				'add_time'	=>	$_SERVER['REQUEST_TIME'],
			);
			$add	=	M('Scenic_collection')->data($arr)->add();
			if(empty($add)){
				$this->returnCode('40000022');
			}
		}else if($_POST['type'] == 2){
			$delect	=	M('Scenic_collection')->where(array('user_id'=>$user_id,'scenic_id'=>$_POST['scenic_id']))->delete();
			if(empty($delect)){
				$this->returnCode('40000023');
			}
		}else if($_POST['type'] == 3){
			$delect	=	M('Scenic_collection')->where(array('collection_id'=>$_POST['collection_id']))->delete();
			if(empty($delect)){
				$this->returnCode('40000023');
			}
		}
		$this->returnCode(0);
	}
	# 游伴最新发布json
	public function new_mate_json(){
		$city_id	=	$_POST['city_id'];
		$start_tiem	=	date('Y-m-d',$_SERVER['REQUEST_TIME']);
		$page	=	$_POST['page'];
		if(empty($page)){
			$page = 0;
		}else{
			$page	=	($page-1)*10;
		}
		$scenic_min_mate	=	D('Scenic_mate')->save_all_scenic_mate_time($city_id,$start_tiem);
		if($_POST['source'] == 1){
			$scenic_mate	=	D('Scenic_mate')->get_all_scenic_mate_time($city_id,$start_tiem,$page,'>','people_number DESC');
		}else{
			$scenic_mate	=	D('Scenic_mate')->get_all_scenic_mate_time($city_id,$start_tiem,$page);
		}
		if($scenic_mate){
			foreach($scenic_mate as &$v){
				$v['create_time']	=	date('Y-m-d H:i',$v['create_time']);
				$v['scenic_mate']	=	D('Scenic_mate')->get_one_scenic_mate_order(array('mate_id'=>$v['mate_id'],'is_mate'=>1));
				$field	=	array('scenic_title');
				$scenic_list	=	D('Scenic_list')->get_one_list(array('scenic_id'=>$v['scenic_id']),$field);
				$v['scenic_title']	=	$scenic_list['scenic_title'];
				$scenic_user	=	D('User')->get_user($v['scenic_mate']['user_id']);
				if(empty($scenic_user['avatar'])){
					$v['avatar']	=	$this->config['site_url'] . '/static/images/user_avatar.jpg';
				}
				$v['nickname']	=	$scenic_user['nickname'];
				unset($v['end_time'],$v['province_id'],$v['city_id'],$v['order_id'],$v['mate_status'],$v['scenic_mate'],$scenic_list,$scenic_user);
			}
		}else{
			$this->returnCode('40000008');
		}
		$this->returnCode(0,$scenic_mate);
	}
	# 景区评论
	public function score_json(){
		$where['scenic_id']	=	$_POST['scenic_id'];
		$where['reply_type']=	$_POST['reply_type'];
		$page	=	I('page',1);
		$field	=	array('reply_id','reply_content','user_id','reply_score','reply_time');
		$reply_list	=	D('Scenic_reply')->get_reply_list($where,$page,'reply_time DESC',$field);
		if($reply_list != 0){
			foreach($reply_list as $k=>&$v){
				$user	=	D('User')->get_user($v['user_id']);
				if(empty($user['avatar'])){
					$v['avatar']	=	$this->config['site_url'] . '/static/images/user_avatar.jpg';
				}else{
					$v['avatar']	=	$user['avatar'];
				}
				$v['nickname']		=	$user['nickname'];
				$v['reply_time']	=	date('Y-m-d',$v['reply_time']);
				$v['reply_scores']	=	(string)(5-$v['reply_score']);
			}
			$this->returnCode(0,$reply_list);
		}else{
			$this->returnCode('40000008');
		}
	}
}