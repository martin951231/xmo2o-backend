<?php
/*
 * APP端前台页面----向导
 *   Writers    hanlu
 *   BuildTime  2016/10/27 14:00
 */
class Scenic_aguideAction extends BaseAction{
	# 向导列表json
	public function index(){
		$city_id	=	$_POST['city_id'];
		$page	=	$_POST['page'];
		if(empty($page)){
			$page = 0;
		}else{
			$page	=	($page-1)*10;
		}
		$list	=	D('Scenic_aguide')->city_get_all_aguide($page,$city_id);
		if($list){
			foreach($list as $k=>&$v){
				if(empty($v['avatar'])){
					$v['avatar']	=	$this->config['site_url'] . '/static/images/user_avatar.jpg';
				}
				$v['guide_price']	=	intval($v['guide_price']);
				if($v['date'] != '0000-00-00'){
					$v['date']	=	D('User')->age($v['date']);
				}else{
					$v['date']	=	L_('保密');
				}
				unset($v['province_id'],$v['city_id'],$v['reply_count'],$v['score_all'],$v['score_mean'],$v['guide_pic'],$v['guide_intr'],$v['guide_card'],$v['guide_card_img'],$v['guide_card_back_img'],$v['guide_service_number'],$v['guide_remarks'],$v['guide_status'],$v['create_time'],$v['update_time'],$v['remarks_time'],$v['real_name'],$v['guide_nickname'],$v['guide_phone'],$v['user_id'],$v['guide_life']);
			}
		}else{
			$this->returnCode('40000001');
		}
		$this->returnCode(0,$list);
	}
	# 向导详情
	public function details(){
		$ticket = I('ticket', false);
		if ($ticket) {
            $info = ticket::get($ticket, $this->DEVICE_ID, true);
            if ($info) {
                $user_id = $info['uid'];
            }
        }
		$where['guide_id']	=	$_POST['guide_id'];
		if(empty($where)){
			$this->returnCode('40000001');
		}
		$list	=	D('Scenic_aguide')->city_get_one_aguide($where);
		if($list){
			$user	=	D('User')->get_user($list['user_id']);
			if(empty($user['avatar'])){
				$list['avatar']	=	$this->config['site_url'].'/tpl/Wap/pure/static/scenic/images/user_avatar.jpg';
			}else{
				$list['avatar']	=	$user['avatar'];
			}
			$list['real_name']	=	$user['real_name'];
			$list['guide_price']	=	floatval($list['guide_price']);
			if($list['date'] != '0000-00-00'){
				$list['date']	=	D('User')->age($list['date']);
			}else{
				$list['date']	=	L_('保密');
			}
			$city_id	=	D('Area')->scenic_get_one_city($list['city_id'],false);
			$list['city_id']	=	$city_id['area_name'];
			$scenic_image_class = new scenic_image();
			$tmp_pic_arr = explode(';',$list['guide_pic']);
			foreach($tmp_pic_arr as $k=>$v){
				if(empty($v)){
					continue;
				}
				$list['pic'][$k] = $scenic_image_class->get_image_by_path($v,$this->config['site_url'],'aguide','s');
			}
			if($list['score_all'] == '0.0'){
				$score_mean	=	array('0','0','5');
				$list['score_mean']	=	L_('暂无评分');
			}else{
				$score_mean = explode('.',$list['score_mean']);
				if($score_mean[1] == 0){
					$score_mean[2]	=	5-$score_mean[0];
				}else{
					$score_mean[2]	=	5-$score_mean[0]-1;
				}
			}
			$list['guide_intr']	=	str_replace("\n","<br>",$list['guide_intr']);
			if($list['user_id'] == $user_id){
				$oneself	=	1;
			}else{
				$oneself	=	0;
			}
			unset($list['user_id'],$list['province_id'],$list['guide_nickname'],$list['guide_card'],$list['guide_card_img'],$list['guide_card_back_img'],$list['create_time'],$list['update_time'],$list['remarks_time'],$list['real_name'],$list['guide_phone'],$list['guide_pic'],$list['guide_service_number'],$list['guide_status']);
		}else{
			$this->returnCode('40000001');
		}
		$arr	=	array(
			'list'	=>	$list,
			'oneself'	=>	$oneself,
			'score_mean'	=>	isset($score_mean)?$score_mean:array(),
		);
		$this->returnCode(0,$arr);
	}
	# 向导下单
	public function order(){
		$ticket = I('ticket', false);
		if ($ticket) {
            $info = ticket::get($ticket, $this->DEVICE_ID, true);
            if ($info) {
                $user_id = $info['uid'];
            }
        }else{
			$this->returnCode('20044013');
        }
		$where['guide_id']	=	$_POST['guide_id'];
		if(empty($where)){
			$this->returnCode('40000001');
		}
		$list	=	D('Scenic_aguide')->city_get_one_aguide($where);
		if($list['user_id'] == $user_id){
			$this->returnCode('40000039');
		}
		if($list['guide_status'] != 1){
			$this->returnCode('40000040');
		}
		if($list){
			$user	=	D('User')->get_user($list['user_id']);
			if(empty($user['avatar'])){
	            $list['avatar']   =   $this->config['site_url'] . '/static/images/user_avatar.jpg';
	        }else{
				$list['avatar']		=	$user['avatar'];
	        }
			$list['real_name']	=	$user['real_name'];
			$list['guide_price']	=	floatval($list['guide_price']);
			if($list['date'] == '0000-00-00'){
				$list['date']	=	L_('保密');
			}else{
				$list['date']	=	D('User')->age($list['date']);
			}
			if($list['score_all'] == '0.0'){
				$score_mean	=	array('0','0','5');
				$list['score_mean']	=	L_('暂无评分');
			}else{
				$score_mean = explode('.',$list['score_mean']);
				if($score_mean[1] == 0){
					$score_mean[2]	=	(string)(5-$score_mean[0]);
				}else{
					$score_mean[2]	=	(string)(5-$score_mean[0]-1);
				}
			}
		}else{
			$this->returnCode('40000001');
		}
		unset($list['user_id'],$list['province_id'],$list['guide_nickname'],$list['guide_card'],$list['guide_card_img'],$list['guide_card_back_img'],$list['create_time'],$list['update_time'],$list['remarks_time'],$list['real_name'],$list['guide_phone'],$list['guide_pic'],$list['guide_service_number'],$list['guide_status']);
		$scenic_family	=	D('Scenic_family')->get_all_list(array('user_id'=>$user_id),array('family_id','family_name','certificates'));
		$arr	=	array(
			'list'	=>	$list,
			'score_mean'	=>	isset($score_mean)?$score_mean:array(),
			'scenic_family'	=>	isset($scenic_family)?$scenic_family:array(),
		);
		$this->returnCode(0,$arr);
	}
	# 生成订单
	public function go_pay(){
		$ticket = I('ticket', false);
		if ($ticket) {
            $info = ticket::get($ticket, $this->DEVICE_ID, true);
            if ($info) {
                $user_id = $info['uid'];
            }
        }else{
			$this->returnCode('20044013');
        }
		$where['guide_id']	=	$_POST['guide_id'];
		if(empty($where)){
			$this->returnCode('40000001');
		}
		$scenic_aguide	=	D('Scenic_aguide')->city_get_one_aguide($where);
		if(empty($scenic_aguide)){
			$this->returnCode('40000001');
		}
		$data	=	array(
			'start_time'	=>	$_POST['start_time'],	//开始时间
			'end_time'		=>	$_POST['end_time'],		//结束时间
			'gender'		=>	$_POST['gender'],		//性别
			'number'		=>	$_POST['number_day'],	//天数
			'phone'			=>	$_POST['phone'],	//电话
			'name'			=>	$_POST['name'],		//联系人
			'family_id'		=>	$_POST['family_id'],	//出行人ID
			'explain'		=>	$_POST['explain'],		//说明
			'user_id'		=>	$user_id,			//用户ID
			'guide_id'		=>	$scenic_aguide['guide_id'],		//向导ID
			'guide_price'	=>	$scenic_aguide['guide_price'],	//单价
			'total_price'	=>	$scenic_aguide['guide_price']*$_POST['number_day'],	//总价
			'create_time'	=>	$_SERVER['REQUEST_TIME'],
			'pay_status'	=>	1,
			'rela_status'	=>	0,
		);
		$add_aguide	=	D('Scenic_aguide')->add_aguide_order($data);
		$order_id	=	D('Scenic_aguide')->getLastInsID();
		if($add_aguide){
			$this->returnCode(0,$order_id);
		}else{
			$this->returnCode('40000005');
		}
	}
}
?>