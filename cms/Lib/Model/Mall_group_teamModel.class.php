<?php
//商城拼团活动团队模型表
class Mall_group_teamModel extends Model{
	//发起团购
	public function add_group_team($user_id=null,$goods_id=null){
		$result_arr=D('Mall_group_act')->check_group_info($goods_id);
		if($result_arr['error']==1){
			return $result_arr;
		}
		$act_info=$result_arr['data'];
		$data=array();
		$data['mer_id']=$act_info['mer_id'];
		$data['group_id']=$act_info['id'];
		$data['user_id']=$user_id;
		$data['num']=0;
		$data['complete_num']=$act_info['complete_num'];
		$time=time();
		$data['start_time']=$time;
		$data['update_time']=$time;
		$data['status']=4;
		$fid=$this->add($data);
		$result_arr=array();
		if($fid>0){
			$result_arr['error']=0;
			$result_arr['msg']=$fid;
		}else{
			$result_arr['error']=1;
			$result_arr['msg']=L_('团购发起失败！');
		}
		return $result_arr;
	}

	//参加团购
	public function join_group_team($group_id,$user_id,$act_info,$order_info,$tid=0){
		$start_time=time()-$act_info['affect_time'];
		if(empty($tid)){
			$where=array();
			$where['group_id']=$group_id;
			$where['status']=array('lt',2);
			$where['start_time']=array('egt',$start_time);
			$team_info=$this->where($where)->order('status asc,num desc,start_time desc')->find();
		}else{
			$where['id']=$tid;
			$team_info=$this->where($where)->find();
		}
		$team_join_num=M('mall_group_user')->where(array('tid'=>$team_info['id'],'user_id'=>$user_id))->count(1);
		//团存在且用户未参与过该团
		if(isset($team_info) && $team_join_num<=0){
			$this->add_group_user($team_info['id'],$user_id,$order_info);
			$data=array();
			$data['id']=$team_info['id'];
			$data['num']=$team_info['num']+1;
			$data['update_time']=time();
			$this->data($data)->save();
			//看看团里面的成员有没有未支付的,解决tapd bug编号1005373
			//$nopay = M("Shop_order")->where(['team_id'=>$team_info['id'],'paid'=>0])->find();
			$nopay = 0;//这里的nopay没有必要，因为$team_info只有支付成功后才会记录下拼团信息。
			if($team_info['complete_num']<=$team_info['num']+1 && empty($nopay)){
				//更新团的状态
				$res=$this->where(array('id'=>$team_info['id']))->save(array('status'=>1,'update_time'=>time()));
				$this->send_template_message($tid);
				return $res;
			}else{
				return $team_info;
			}
		}else{
			//不存在可参与的团或已参与过该团，自动发起新的拼团
			return $this->add_group_team($user_id,$act_info['good_id']);
		}
	}

	//记录参团用户信息
	public function add_group_user($tid=null,$user_id=null,$order_info=null){
		$data=array();
		$data['tid']=$tid;
		$data['user_id']=$user_id;
		$data['order_id']=$order_info['order_id'];
		$res=M('mall_group_user')->data($data)->add();
		$this->send_group_template_message($order_info);
		return $res;
	}

	//校验"未成团"的团人数是否满足成团的条件
	public function check_group_team($tid){
		$info=$this->where(array('id'=>$tid))->find();
		if(isset($info) && $info['status']==0){
			if($info['complete_num']<=$info['num']){
				$this->update_group_status($tid,1);
			}
		}
	}

	//更新拼团团队的状态
	public function update_group_status($tid,$status){
		$data=array();
		if($status==3){
			$where=array();
			if(is_array($tid)){
				$where['id']=array('in',$tid);
			}else{
				$where['id']=$tid;
			}			
			$data['status']=1;
			$data['update_time']=time();
			$res=$this->where($where)->save($data);
			/*模板消息*/
			if(is_array($tid)){
				foreach ($tid as $k => $v) {
					$this->send_template_message($v);
				}
			}else{
				$this->send_template_message($tid);
			}
			
			/*--------*/
			return $res;
		}else{
			$data['status']=$status;
			$data['update_time']=time();
			if(is_array($tid)){
				$res=$this->where(array('id'=>array('in',$tid)))->save($data);
			}else{
				$res=$this->where(array('id'=>$tid))->save($data);
			}
			return $res;
		}		
	}

	//推送成团的模板消息
	public function send_template_message($team_id){
		//获取团队用户列表
		$list=$this->get_team_user_list($team_id,'',0);
		$template = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
		foreach ($list as $k => $v) {
			if($v['type']==1){
				continue;
			}
			$href=C('config.site_url').'/wap.php?c=Mall&a=group_team_detail&group_id='.$v['group_id'].'&team_id='.$v['team_id'];
			$data=array();
			$data['href']=$href;
			$data['wecha_id']=$v['openid'];
			$data['first']=C('config.mall_alias_name').L_('拼团提醒');
			$data['OrderSn']=$v['real_orderid'];
			$data['OrderStatus']=L_('您的拼团已于 X1 成团',date('Y-m-d H:i:s'));
			$data['remark']=L_('拼团成功');
			$template->sendTempMsg('TM00017',$data);
			if(C('config.sms_pin_success_order')==1){
				/*$sms_data=array();
				$sms_data['mer_id']=$v['mer_id'];
				$sms_data['store_id']=$v['store_id'];
				$sms_data['type']='mall_group';
				$sms_data['uid'] = $v['user_id'];
                $sms_data['mobile'] = $v['phone'];
                $sms_data['sendto'] = 'user';
                $sms_data['content'] =L_('您的拼团已于 ').date('Y-m-d H:i:s').' 成团';
                Sms::sendSms($sms_data);*/
			}			
			//成团使用团长的订单号给店员发送新订单信息
			if($v['buy_type']==1){
				D('Merchant_store_staff')->sendMsg($v['store_id'],$v);
			}
		}
	}

	//推送参团、拼团信息
	public function send_group_template_message($order_info){
		if($order_info['group_id']==0){
			return false;
		}
		$template = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
		$where=array();
		$where['uid']=$order_info['uid'];
		$openid=M('user')->where($where)->getField('openid');

		$href=C('config.site_url').U('Wap/Mall/group_team_detail',array('group_id'=>$order_info['group_id'],'team_id'=>$order_info['team_id']));
		$href=str_replace('/source/', '/',$href);
        $href=str_replace('wap_weixin_notice.php', 'wap.php',$href);
		if($order_info['buy_type']==1){
			$desc=L_('您于 X1 发起拼团',date('Y-m-d H:i:s'));
		}else{
			$desc=L_('您于 X1 参加拼团',date('Y-m-d H:i:s'));			
		}
		$data=array();
		$data['href']=$href;
		$data['wecha_id']=$openid;
		$data['first']=C('config.mall_alias_name').L_('拼团提醒');
		$data['OrderSn']=$order_info['real_orderid'];
		$data['OrderStatus']=$desc;
		$data['remark']=L_('查看详情');
		$template->sendTempMsg('TM00017',$data);
	}

	//更新已超时的拼团活动状态
	public function save_timeout_group_status($order_id){
		$tid=M('mall_group_user')->where(array('order_id'=>$order_id))->getfield('tid');
		if($tid>0){
			$this->where(array('id'=>$tid))->setField('status',2);
		}
	}

	//校验用户是否已参加该团
	public function get_team_info($group_id,$team_id,$user_id,$act_info=null,$type=1){
		$where=array();
		$where['id']=$team_id;
		$where['group_id']=$group_id;
		$team_info=$this->where($where)->find();
		if($type==1){
			$result_arr=array();
			if(empty($team_info)){
				$result_arr['error']=1;
				$result_arr['msg']=L_('抱歉，该团队不存在！');
				return $result_arr;	
			}
			if(($team_info['status']==0 &&  $team_info['complete_num']<=$team_info['num']) || $team_info['status']==1){
				$result_arr['error']=1;
				$result_arr['msg']=L_('该团已成功，请选择正在进行中的参加拼团！');
				return $result_arr;
			}	
			if($team_info['status']==2){
				$result_arr['error']=1;
				$result_arr['msg']=L_('抱歉，该拼团团队已结束，请选择有效的拼团！');
				return $result_arr;
			}
			//校验是否已结束
			$time=time();
			if($team_info['start_time']+$act_info['affect_time']<$time){
				$result_arr['error']=1;
				$result_arr['msg']=L_('抱歉，该拼团团队已结束，请选择在有效时间的拼团！');
				$this->where(array('id'=>$team_id))->save(array('status',2));
				return $result_arr;
			}
			$where=array();
			$where['tid']=$team_id;
			$where['user_id']=$user_id;
			$num=M('mall_group_user')->where($where)->count(1);
			if($num>0){
				$result_arr['error']=1;
				$result_arr['msg']=L_('您已参与过该拼团，请勿重复参与！');
				return $result_arr;
			}
			$result_arr['error']=0;
			$result_arr['msg']=$team_info;
			return $result_arr;
		}elseif($type==2){
			if(!empty($act_info) && !empty($team_info)){
				$now_time=time();
				if($team_info['status']==0 && $team_info['start_time']+$act_info['affect_time']<$now_time){
					$data=array();
					$data['id']=$team_info['id'];
					$data['status']=2;
					$data['update_time']=$now_time;
					$this->data($data)->save();
					$team_info['status']=2;
				}
			}
			return (array)$team_info;
		}
	}


	//获取当前用户可参加的拼团活动
	public function get_can_join_group($group_id=null,$user_id=null,$affect_time=null,$limit=3,$get_group_count=0){
		$time=time();
		$start_time=$time-$affect_time;
		$where=array();
		$where['team.group_id']=$group_id;
		$where['team.status']=0;
		$where['team.start_time']=array('gt',$start_time);
		$where['num']=array('gt',0);
		if(!empty($user_id)){
			$where['team.user_id']=array('neq',$user_id);
		}else{

		}
		//筛选出可以参加的拼团
		$list=$this->alias('team')->join('left join '.C('DB_PREFIX').'user us on team.user_id=us.uid')->field('team.*,us.nickname,us.avatar')->where($where)->order('team.num,team.start_time asc')->limit($limit)->select();
		foreach ($list as $k => $v) {
			if(preg_match('/\d{3}\*{4}\d{4}/',$v['nickname'],$m)|| preg_match('/\d{11}/',$v['nickname'],$m)){
                $v['nickname'] = L_('游客');
            }
            $v['need_num']=$v['complete_num']-$v['num'];
			$v['end_time']=$v['start_time']+$affect_time;
			$v['time_clock']=$v['end_time']-$time;
			if($v['need_num'] > 0){
				$list[$k]=$v;
			}
		}
		if($get_group_count==1){
			$data=array();
			$team_count=$this->alias('team')->join('left join '.C('DB_PREFIX').'user us on team.user_id=us.uid')->field('team.*,us.nickname,us.avatar')->where($where)->count(1);
			$data['team_count']=$team_count;
			$data['list']=(array)$list;
			return $data;
		}else{
			return (array)$list;	
		}		
	}

	//获取用户参加的拼团
	public function get_user_join_team($user_id,$condition=null,$page=1,$limit=null){
		$where=array();
		$where['us.user_id']=$user_id;
		if(is_numeric($condition['team_status'])){
			$where['te.status']=$condition['team_status'];
		}
		$model=$this->alias('te')->join('inner join '.C("DB_PREFIX").'mall_group_user us on te.id=us.tid')->join('inner join '.C('DB_PREFIX').'mall_group_act act on act.id=te.group_id')->join('inner join '.C('DB_PREFIX').'shop_goods gd on act.good_id=gd.goods_id')->where($where)->field('te.*,te.start_time+act.affect_time as end_time,act.store_id,act.is_del,us.order_id,gd.goods_id,gd.name,gd.image,gd.price')->order('te.start_time desc');
		if($limit>0){
			$first=($page-1)*$limit;
			$list=$model->limit($first,$limit)->select();
		}else{
			$list=$model->select();
		}
		$time=time();
		$goods_image=new goods_image();
		foreach ($list as $k => $v) {
			if($v['status']==0 && $v['end_time']<$time){
				$data=array();
				$data['id']=$v['id'];
				$data['status']=2;
				$data['update_time']=$time;
				$this->data($data)->save();
				$v['status']=2;
			}
			$img_arr=D('Shop_goods')->reg_good_img($v['image'],$goods_image);
			//$v['image']=$img_arr['0']['url']['s_image'];
			$v['image']=$img_arr['0']['url'];
			$v['group_url']=U('Mall/group_team_detail',array('goods_id'=>$v['goods_id'],'group_id'=>$v['group_id'],'team_id'=>$v['id']));
			$v['order_url']=U('Mall/status',array('order_id'=>$v['order_id'],'mer_id'=>$v['mer_id'],'store_id'=>$v['store_id']));
			$list[$k]=$v;
		}
		return (array)$list;
	}

	//获取可退款的拼团小组
	//获取超过拼团时间，状态是未成团/失败的拼团小组的订单
	public function get_group_refund(){
		$where=array();		
		$where['team.status']=array('in','0,2');
		//已支付未退款的订单
		$where['ord.paid']=1;
		$where['ord.status']=array('in','0,7,9');
		$where['ord.group_id']=array('gt',0);//拼团订单，团购id肯定是大于0的
		$time=time();
		$where['_string']="team.start_time+act.affect_time<".$time;
		
		$list=$this->alias('team')->join('left join '.C('DB_PREFIX').'mall_group_act act on team.group_id=act.id')->join('left join '.C('DB_PREFIX').'shop_order ord on team.id=ord.team_id')->where($where)->field('ord.*')->select();
		if($list){
			fdump(M()->getLastSql(),'mall_group_refund',1);
			fdump($list,'mall_group_refund',1);
		}
		return (array)$list;
	}

	//获取当前团购组参团用户信息(包括机器人)
	public function get_team_user_list($team_id,$mer_id='',$need_robot=1){
		$list=M('mall_group_user')->alias('gu')->join('left join '.C('DB_PREFIX').'user us on gu.user_id=us.uid')->join('left join '.C('DB_PREFIX').'shop_order ord on gu.order_id=ord.order_id')->where(array('tid'=>$team_id))->field('gu.*,us.nickname,us.phone,us.avatar,us.openid,ord.order_id,ord.order_from,ord.real_orderid,ord.mer_id,ord.store_id,ord.status,buy_type,pay_time,ord.group_id,ord.team_id,ord.buy_type')->select();
		if($need_robot==1){
			$robot_list = M('Robot_list')->where(array('mer_id'=>$mer_id))->getField('id,robot_name',true);
	        foreach ($list as $k=>$v) {
	            if($v['type']==1){
	                $list[$k]['nickname'] = $robot_list[$v['user_id']];
	                $list[$k]['phone'] = '';
	            }
	        }
		}		
		return $list;
	}

	//获取订单拼团状态的一维数组
	public function get_order_group_status(){
		$list=$this->alias('te')->join('inner join '.C('DB_PREFIX').'mall_group_user us on te.id=us.tid')->join('inner join '.C('DB_PREFIX').'mall_group_act act on te.group_id=act.id')->field('te.id,us.order_id,te.status,(te.start_time+act.affect_time) end_time')->select();
		$arr=array();
		$now_time=time();
		foreach ($list as $k => $v) {
			if($v['status']==0){
				if($v['end_time']<$now_time){
					$data=array();
					$data['id']=$v['id'];
					$data['status']=2;
					$data['update_time']=$now_time;
					$this->data($data)->save();
					$v['status']=2;
				}
			}
			$arr[$v['order_id']]=$v['status'];
		}
		return $arr;
	}

	//生成拼团机器人
	public function create_robot($team_info=null){
		$num=$team_info['complete_num']-$team_info['num'];
		$head_arr=array();
		$head_arr[]=file_domain().'/upload/robot_avator/user_avatar.jpg';
		$head_arr[]=file_domain().'/upload/robot_avator/tx.png';
		$robot_count=M('robot_list')->where(array('mer_id'=>$team_info['mer_id']))->count(1);
		$need_num=$num-$robot_count;
		if($need_num>0){
			import('@.ORG.randName');
            $name = new randName();
			$data=array();	
			while ($need_num>0) {
				$arr=array();
				$arr['robot_name']=$name->getName();
				$arr['avatar']=$head_arr[array_rand($head_arr,1)];
				$arr['mer_id']=$team_info['mer_id'];
				$arr['add_time']=time();
				$data[]=$arr;
				$need_num--;
			}
			M('robot_list')->addAll($data);
		}
		$robot_list=M('robot_list')->where(array('mer_id'=>$team_info['mer_id']))->select();
		shuffle($robot_list);
		$data2=array();
		for ($i=0; $i < $num; $i++) { 
			$arr2=array();
			$arr2['tid']=$team_info['id'];
			$arr2['order_id']=0;
			$arr2['user_id']=$robot_list[$i]['id'];
			$arr2['type']=1;
			$data2[]=$arr2;
		}
		M('mall_group_user')->addAll($data2);
		$res=$this->where(array('id'=>$team_info['id']))->setField('num',$team_info['complete_num']);
		return $res;
	}

	//获取即将结束的团推送消息
	public function get_fast_over_order(){
		$where=array();
		$time=time();
		$tip_end_time=$time+60*60;
		$tip_start_time=$tip_end_time-4;
		$where['_string']="team.start_time+act.affect_time>=".$tip_start_time." and team.start_time+act.affect_time<=".$tip_end_time;
		$where['team.status']=0;
		$where['us.openid']=array('neq','');
		$where['ord.paid']=1;
		$where['gu.push_status']=0;
		$where['gu.type']=0;
		$list=$this->alias('team')->join('inner join '.C('DB_PREFIX').'mall_group_act act on act.id=team.group_id')->join('inner join '.C('DB_PREFIX').'mall_group_user gu on team.id=gu.tid')->join('inner join '.C('DB_PREFIX').'shop_order ord on gu.order_id=ord.order_id')->join('inner join '.C('DB_PREFIX').'user us on gu.user_id=us.uid')->where($where)->field("act.good_id goods_id,team.group_id,team.id team_id,team.complete_num,team.num,real_orderid,gu.user_id,us.openid,gu.id")->select();
		$data=array();
		$data['push_status']=1;
		foreach ($list as $k => $v) {
			$where=array();
			$where['goods_id']=$v['goods_id'];
			$name=M('shop_goods')->where($where)->getField('name');
			$v['name']=$name;
			$v['need_num']=$v['complete_num']-$v['num'];
			if($v['need_num']<=0){
				unset($list[$k]);
			}else{
				$list[$k]=$v;
			}
			$data['id']=$v['id'];
			M('mall_group_user')->data($data)->save();
		}
		return $list;
	}

	//拼团订单支付之后处理
	public function check_group_order($order_info=null){
		if(empty($order_info)){
			return false;
		}
		if($order_info['group_id']>0 && ($order_info['buy_type']==1 || $order_info['buy_type']==2)){
			$tid=$order_info['team_id'];
			//团长订单
			if($order_info['buy_type']==1){
				$this->add_group_user($tid,$order_info['uid'],$order_info);
				$data=array();
				$data['num']=1;
				$complete_num=$this->where(array('id'=>$tid))->getfield('complete_num');
				if($complete_num<=$data['num']){
					$data['status']=1;
					$this->send_template_message($tid);
				}else{
					$data['status']=0;
				}
				$data['id']=$tid;
				$this->data($data)->save();
			}else{
				$act_info=M('Mall_group_act')->where(array('id'=>$order_info['group_id']))->find();
				$this->join_group_team($order_info['group_id'],$order_info['uid'],$act_info,$order_info,$tid);
			}
			//增加拼团数量
			M('Mall_group_act')->where(array('id'=>$order_info['group_id']))->setInc('sales_num',$order_info['num']);
		}
	}
}
?>