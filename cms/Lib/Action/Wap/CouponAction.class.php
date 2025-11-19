<?php
class CouponAction extends LotteryBaseAction{
	public $lottory_record_db;
	public $lottory_db;

	public function index(){
		$this->lottory_record_db = M('Lottery_record');
		$this->lottory_db = M('Lottery');
		
		
		$token		= $this->token;
		$wecha_id	= $this->wecha_id;
		$id 		= $this->_get('id');
		$Lottery 	= $this->lottory_db->where(array('id'=>$id,'token'=>$token,'type'=>3,'status'=>1))->find();
		$Lottery['renametel']=$Lottery['renametel']?$Lottery['renametel']:'手机号';
		$Lottery['renamesn']=$Lottery['renamesn']?$Lottery['renamesn']:'SN码';
		$this->assign('lottery',$Lottery);
		//var_dump($Lottery);
		//0. 判断优惠券是否领完了
		if ($Lottery['statdate']>time()){
			$data['usenums']=0;
		}else {
			
			$data=$this->prizeHandle($token,$wecha_id,$Lottery);
		}
	
		$data['token'] 		= $token;
		$data['wecha_id']	= $wecha_id;		
		$data['lid']		= $Lottery['id'];
		$data['id']		= $Lottery['id'];
		$data['keyword']		= $Lottery['keyword'];
		$data['title']		= $Lottery['title'];
		$data['startpicurl']		= $Lottery['startpicurl'];
		
		$data['phone']		= $data['phone']; 
		$data['usenums']	= $data['usenums'];
		$data['sendtime']	= $data['sendtime'];
		$data['canrqnums']	= $Lottery['canrqnums'];
		$data['fist'] 		= $Lottery['fist'];
		$data['second'] 	= $Lottery['second'];
		$data['third'] 		= $Lottery['third'];
		$data['fistnums'] 	= $Lottery['fistnums'];
		$data['secondnums'] = $Lottery['secondnums'];
		$data['thirdnums'] 	= $Lottery['thirdnums'];	
		$data['info']		= $Lottery['info'];
		$data['aginfo']		= $Lottery['aginfo'];
		$data['txt']		= $Lottery['txt'];
		$data['sttxt']		= $Lottery['sttxt'];
		$data['title']		= $Lottery['title'];
		$data['statdate']	= $Lottery['statdate'];
		$data['enddate']	= $Lottery['enddate'];
		$data['info']=nl2br($data['info']);
		$data['endinfo']=nl2br($data['endinfo']);	
		$this->assign('Coupon',$data);
		//var_dump($data);exit();
		$this->display();
	}

	// 关注有礼，派发优惠券
	public function get_coupon(){
		$coupon_ids = $_GET['coupon_ids'];

	 	$condition = array('coupon_id'=>array('in', $coupon_ids));  
	 	$condition['end_time'] = array('gt',time());
        $condition['status'] = array('in','1');  //状态正常
		$order_string = '`coupon_id` DESC';
		$coupon_list = D('System_coupon')->field(true)->where($condition)->order($order_string)->select();
		foreach ($coupon_list as $key => &$coupon) {
			$coupon['platform']=unserialize($coupon['platform']);
			if($coupon['cate_name']!='all'&&!empty($coupon['cate_id'])){
				$tmp = unserialize($coupon['cate_id']);
				$coupon['cate_id'] = $tmp['cat_name'];
			}

        	$where['coupon_id']=$coupon['coupon_id'];

        	// 当前用户已打领取上限
        	$condition_coupon_record = array('coupon_id' => $coupon['coupon_id'], 'uid' => $this->user_session['uid']);
        	$now_coupon_get_count = M("System_coupon_hadpull")->where($condition_coupon_record)->count();
        	if ($coupon['limit']<=$now_coupon_get_count) {
	        	// 优惠券已达领取上限
	            unset($coupon_list[$key]);
	            continue;
        	}

	        if(($coupon['num']<=$coupon['had_pull']||$coupon['status']==3)){
	        	// 优惠券已领完
	            D('System_coupon')->where($where)->setField('status',3);
	            unset($coupon_list[$key]);
	        }else{
	            if (D('System_coupon')->where($where)->setInc('had_pull')) {
	                D('System_coupon')->where($where)->setField('last_time',$_SERVER['REQUEST_TIME']);
	                $data = array();
	                $data['coupon_id'] = $coupon['coupon_id'];
	                $data['num'] = 1;
	                $data['receive_time'] =$_SERVER['REQUEST_TIME'];
	                $data['status'] = 0;
	                $data['uid']  = $this->user_session['uid'];
	               	M('System_coupon_hadpull')->add($data);
	            } else {
	                return array('error'=>1,'msg'=>L_('领取失败'));
	            }
	        }
		}
		$coupon_list = $coupon_list ? $coupon_list : array();
		$return =  D('System_coupon')->cate_platform();
		$this->assign("category",$return['category']);
		$this->assign("platform",$return['platform']);
		$this->assign('coupon_list',$coupon_list);
		$this->assign('count',count($coupon_list));
		$this->display();
	}
}
	
?>