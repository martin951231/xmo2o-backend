<?php
//商城砍价活动团队模型类
class Mall_bargain_teamModel extends Model{
	//发起砍价活动
	public function add_bargain_team($bar_info,$mer_id,$user_id){
		$res=$this->check_add_bargain($user_id,$bar_info['id'],$bar_info['max_bar_num']);
		$result_arr=array();
		if($res['error']==1){
			return $res;
		}
		$data=array();
		$data['mer_id']=$mer_id;
		$data['bar_id']=$bar_info['id'];
		$data['user_id']=$user_id;
		$data['floor_price']=$bar_info['floor_price'];
		$data['bar_total_price']=0;
		$data['bar_num']=0;
		$time=time();;
		$data['start_time']=$time;
		$data['update_time']=$time;
		$res=$this->data($data)->add();
		if($res){
			$result_arr['error']=0;
			$result_arr['msg']=$res;
		}else{
			$result_arr['error']=1;
			$result_arr['msg']=L_('抱歉，发起失败，请重试！');
		}
		return $result_arr;
	}

	//校验是否可以发起砍价
	public function check_add_bargain($user_id,$bar_id,$max_bar_num){
		$where=array();
		$where['team.bar_id']=$bar_id;
		$where['team.user_id']=$user_id;
		$time=time();
		$where['_string']="team.start_time+act.affect_time>=".$time;
		$where['team.status']=array('eq',0);
		$num=$this->alias('team')->join('left join '.C('DB_PREFIX').'mall_bargain_act act on team.bar_id=act.id')->where($where)->count(1);
		if($num>0){
			return array('error'=>1,'msg'=>L_('您有正在进行中的砍价，请等待该砍价结束后再来发起新的砍价'));
		}
		$result_arr=array();
		if($max_bar_num>0){
			$where=array();
			$where['bar_id']=$bar_id;
			$where['user_id']=$user_id;
			$where['status']=1;
			$start_num=$this->where($where)->count(1);
			$result_arr['error']=$start_num>=$max_bar_num?1:0;
			$result_arr['msg']=L_("抱歉，该商品每个用户最多以砍价价格购买X1次，您已发起并成功购买X2次，请选择其他商品发起砍价！",array("X1" => $max_bar_num,"X2" => $start_num)); 
		}else{
			$result_arr['error']=0;
		}
		return $result_arr;
	}

	//校验能否帮助砍价
	public function check_help_bar($user_id,$team_id,$bar_info=null,$type=1){
		$where=array();
		$where['id']=$team_id;
		$where['bar_id']=$bar_info['id'];
		$team_info=$this->where($where)->find();
		if(empty($team_info)){
			return array('error'=>1,'msg'=>L_('该砍价不存在！'));
		}
		$end_time=$team_info['start_time']+$bar_info['affect_time'];
		$time=time();
		if($team_info['status']==0){
			if($end_time<$time){
				$res=$this->where(array('id'=>$team_id))->save(array('status'=>2));
				if($res){
					//推送失败的模板消息 2019/1/28
					$this->send_fail_template_msg($team_info['user_id'],$team_info['mer_id'],$bar_info['goods_id'],$team_info['bar_id'],$team_info['id'],$team_info['start_time']);
				}
				
				return array('error'=>1,'msg'=>L_('该砍价已失败！'));
			}else{
				$buy_price=$this->get_good_price($bar_info['goods_id'],$bar_info['spec_id']);
				$floor_price=($buy_price*100-$team_info['bar_total_price']*100)/100;
				if($floor_price<=$team_info['floor_price']){
					return array('error'=>1,'msg'=>L_('已砍至底价！'));
				}
			}
		}
		if($team_info['status']==1){
			return array('error'=>1,'msg'=>L_('该砍价已成功！'));
		}
		if($team_info['status']==2){
			return array('error'=>1,'msg'=>L_('该砍价已失败！!'));
		}
		$where=array();
		$where['tid']=$team_info['id'];
		$where['user_id']=$user_id;
		$num=M('mall_bargain_user')->where($where)->count(1);
		if($num>0){
			return array('error'=>1,'msg'=>L_('您已帮助过用户砍价！'));
		}

		//存在每日最大帮砍数
		if($type==1 && C('config.max_help_bargain_num')>0){
			$where=array();
			$where['user_id']=$user_id;
			$where['is_start']=0;
			$stime=strtotime(date('Ymd'));
			$ftime=$stime+24*3600;
			$where['bar_time']=array('between',array($stime,$ftime));
			$bar_num=M('mall_bargain_user')->where($where)->count(1);
			if($bar_num>=C('config.max_help_bargain_num')){
				return array('error'=>1,'msg'=>L_('每日最大帮砍次数x1,当前已帮砍x2次，请明日再来帮助该用户砍价！',array('x1'=>C('config.max_help_bargain_num'),'x2'=>$bar_num)));
			}
		}
		return array('error'=>0,'msg'=>$team_info);
	}

	//增加砍价记录
	public function add_help_bar_log($team_id=null,$user_id=null,$bar_price=null,$team_info,$is_start=0){
		$data=array();
		$data['tid']=$team_id;
		$data['user_id']=$user_id;
		$data['bar_price']=$bar_price;
		$data['bar_time']=time();
		$data['is_start']=$is_start;
		$res=M('mall_bargain_user')->data($data)->add();
		if($res){
			$data2=array();
			$data2['id']=$team_id;
			$data2['bar_num']=$team_info['bar_num']+1;
			$data2['bar_total_price']=$team_info['bar_total_price']+$bar_price;
			$this->data($data2)->save();
			M('mall_bargain_act')->where(array('id'=>$team_info['bar_id']))->setInc('bar_total_num',1);
			/*推送砍价信息*/
			$this->send_template_message($team_id,$user_id,$bar_price);
		}
		return $res;
	}

	//推送砍价通知
	public function send_template_message($team_id=null,$user_id=0,$bar_price=0){
		if($team_id<=0){
			return false;
		}
		$where=array();
		$where['team.id']=$team_id;
		$info=$this->alias('team')->join('inner join '.C('DB_PREFIX').'mall_bargain_act act on team.bar_id=act.id')->where($where)->field('goods_id,spec_id,bar_id,bar_total_price,team.floor_price')->find();
		$tprice=$this->get_good_price($info['goods_id'],$info['spec_id']);
		$href=C('config.site_url').'/wap.php?c=Mall&a=detail&goods_id='.$info['goods_id'].'&bar_id='.$info['bar_id'].'&team_id='.$team_id;

		$first=L_('成功发起砍价提醒');
		$keyword1=L_('发起砍价');
		$remark=L_("您已成功砍价X1，点击查看详情",array("X1" => $bar_price.C('config.Currency_txt'))); 

		/*砍至最低价提醒*/
		if($tprice<=$info['floor_price']+$info['bar_total_price']){
			$user_id=M('mall_bargain_user')->where(array('tid'=>$team_id,'is_start'=>1))->getfield('user_id');
			$first=L_('砍价成功提醒');
			$keyword1=L_('砍价成功');
			$remark=L_('点击查看详情');
		}

		$where2=array();
		$where2['uid']=$user_id;
		$uinfo=M('user')->where($where2)->field('openid,nickname')->find();

		$data=array();
		$data['wecha_id']=$uinfo['openid'];
		$data['href']=$href;
		$data['first']=$first;
		$data['keyword1']=$keyword1;
		$data['keyword2']=L_('已发送');
		$data['keyword3']=date('Y/m/d H:i');
		$data['keyword4']=$uinfo['nickname'];
		$data['remark']=$remark;
		
		$template = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
		$template->sendTempMsg('OPENTM405462911',$data);
	}

	//获取当前砍价用户可砍价的价格
	public function get_bargain_price($bar_info,$team_info){
		//底价
		$floor_price=$bar_info['floor_price'];
		$tprice=$this->get_good_price($bar_info['goods_id'],$bar_info['spec_id']);
		$low_price=$bar_info['low_price'];
		$high_price=$bar_info['high_price'];
		//差价
		$distance_price=($tprice*100-$floor_price*100)/100;
		//最少助力人数
		$min_help=$bar_info['min_num'];
		//剩余砍价金额
		$now_distance_price=($distance_price*100-$team_info['bar_total_price']*100)/100;
		if($now_distance_price<=0){
			return array('error'=>1,'msg'=>L_('抱歉，网络出错，请重试！'));
		}
		$i=$team_info['bar_num']+1;
		//获取实际最高价
		if($min_help>0){
			if(($min_help+1)*$low_price>$distance_price){
				$min_help=ceil($distance_price/$low_price)-1;
			}
			//剩余用户按照最低金额计算后，当前用户能砍价的最高金额
	        $max_price=$now_distance_price-($min_help-$i+1)*$low_price;
	        //在设置的最低和最高之间
	        $max_price=max($low_price,$max_price);
	        $max_price=min($high_price,$max_price);
		}else{
			$max_price=$high_price;
		}
		//实际最低价
		$min_price=$low_price;
		$percent=pow((7/10),$i);
    	$percent+=$this->randFloat(0,1)*0.1;
    	$price=$this->get_price($min_price,$max_price,$percent);
    	$price=min($now_distance_price,$price);
    	$price=round($price,2);
    	return $price;
	}

	public function get_price($min,$max,$percent){
	    if($min>=$max){
	        $price=$min;
	    }else{
	        $price=$min+($max-$min)*$percent;  
	    }
	    return $price;
	}

	//获取当前商品的实际价格
	public function get_good_price($goods_id,$spec_id=null,$get_stock_num=0){
		$good_info=M('shop_goods')->where(array('goods_id'=>$goods_id))->field('price,spec_value,stock_num')->find();
		if(empty($spec_id)){
			$price=$good_info['price'];
			$stock_num=$good_info['stock_num'];
		}else{
			$data=D('Shop_goods')->format_spec_value($good_info['spec_value'],$goods_id);
			$list=$data['list'];
			$price=$list[$spec_id]['price'];
			$stock_num=$list[$spec_id]['stock_num'];
		}
		if($get_stock_num==1){
			return array('price'=>$price,'stock_num'=>$stock_num);
		}else{
			return $price;
		}
	}
	/**
	 * 生成0~1随机小数
	 * @param Int  $min
	 * @param Int  $max
	 * @return Float
	 */
	public function randFloat($min=0, $max=1){
	    return $min + mt_rand()/mt_getrandmax() * ($max-$min);
	}

	//校验当前砍价后的价格
	public function get_bar_good_price($bar_id=null,$team_id){
		$where=array();
		$where['act.id']=$bar_id;
		$where['team.id']=$team_id;
		$info=$this->alias('team')->join(C('DB_PREFIX').'mall_bargain_act act on team.bar_id=act.id')->where($where)->field('team.*,act.goods_id,act.spec_id,(team.start_time+act.affect_time) as end_time')->find();
		if(empty($info)){
			return array('error'=>1,'msg'=>L_('抱歉，网络出错，请重试！'));
		}
		$time=time();
		if($info['status']==0 && $info['end_time']<$time){
			$data=array();
			$data['id']=$team_id;
			$data['status']=2;
			$data['update_time']=time();
			$res=$this->data($data)->save();
			if($res){
				//推送失败的模板消息 2019/1/28
				$this->send_fail_template_msg($info['user_id'],$info['mer_id'],$info['goods_id'],$info['bar_id'],$info['id'],$info['start_time']);
			}
			return array('error'=>1,'msg'=>L_('抱歉，砍价价格已失效，请重新购买！'));
		}
		if($info['status']==1){
			return array('error'=>1,'msg'=>L_('该砍价已失效，请重新发起新的砍价！'));
		}
		$buy_price=$this->get_good_price($info['goods_id'],$info['spec_id']);
		$tprice=($buy_price*100-$info['bar_total_price']*100)/100;
		if($tprice>=0){
			return array('error'=>0,'price'=>$tprice);
		}else{
			return array('error'=>0,'price'=>$buy_price);
		}
	}

	//获取用户发起的砍价活动
	public function get_user_team_list($user_id=null,$page=1,$limit=null){
		$where=array();
		$where['te.user_id']=$user_id;
		$group_str="(case when te.status=2 then concat(te.bar_id,'-') else te.id end)";
		$model=$this->alias('te')->join('inner join '.C('DB_PREFIX').'mall_bargain_act act on act.id=te.bar_id')->join('inner join '.C('DB_PREFIX').'shop_goods gd on act.goods_id=gd.goods_id')->where($where)->field('te.*,max(te.id) max_id,te.start_time+act.affect_time as end_time,act.goods_id,act.spec_id,gd.spec_value,gd.name,gd.image,gd.price,gd.old_price')->group($group_str)->order('te.start_time desc');
		if($limit>0){
			$first=($page-1)*$limit;
			$list=$model->limit($first,$limit)->select();
		}else{
			$list=$model->select();
		}
		$time=time();
		$goods_image=new goods_image();
		foreach ($list as $k => $v) {
			if($v['status']==0){
				if($v['end_time']<$time){
					$data=array();
					$data['id']=$v['id'];
					$data['status']=2;
					$data['update_time']=time();
					$res=$this->data($data)->save();
					if($res){
						//推送失败的模板消息 2019/1/28
						$this->send_fail_template_msg($v['user_id'],$v['mer_id'],$v['goods_id'],$v['bar_id'],$v['id'],$v['start_time']);
					}
					$v['status']=2;	
				}else{
					$v['time_clock']=$v['end_time']-$time;
				}
			}
			if($v['status']!=2){
				$v['tprice']=$this->get_good_price($v['goods_id'],$v['spec_id']);
				$v['bar_discount_price']=$v['tprice']*100-$v['floor_price']*100-$v['bar_total_price']*100;
				$v['bar_discount_price']=floatval($v['bar_discount_price']/100);
				$v['bar_discount_price']=max($v['bar_discount_price'],0);
			}
			$img_arr=D('Shop_goods')->reg_good_img($v['image'],$goods_image);
			//$v['image']=$img_arr['0']['url']['s_image'];
			$v['image']=$img_arr['0']['url'];
			
			$v['url']=U('Mall/detail',array('goods_id'=>$v['goods_id'],'bar_id'=>$v['bar_id'],'team_id'=>$v['id']));
			$v['buy_url']=U('Mall/detail',array('goods_id'=>$v['goods_id'],'bar_id'=>$v['bar_id']));
			$v['new_url']=U('Mall/cut_goods_list');
			if(!empty($v['spec_id'])){
				$where2=array();
				$where2['id']=array('in',explode('_',$v['spec_id']));
				$spec_arr=M('Shop_goods_spec_value')->where($where2)->order('id asc')->getfield('name',true);
				$spec_str=implode(',',$spec_arr);
				$v['spec_str']=$spec_str;
			}
			$list[$k]=$v;
		}

		return (array)$list;
	}

	//获取用户当前商品砍价的信息
	public function get_team_info($bar_info=null,$team_id=null,$user_id=null,$now_goods=null){
		if((empty($user_id) && empty($team_id)) || empty($bar_info)){
			return array();
		}
		$where=array();
		$where['bar_id']=$bar_info['id'];
		$time=time();
		if($team_id>0){
			$where['id']=$team_id;
		}else{
			$where['user_id']=$user_id;			
			$start_time=$time-$bar_info['affect_time'];
			$where['start_time']=array('egt',$start_time);
			$where['status']=array('eq',0);
		}
		$team_info=$this->where($where)->order('start_time desc')->find();
		if(empty($team_info)){
			return array();
		}
		if($team_id>0 && $team_info['status']==0){
			//超时未购买，状态变为2
			if($team_info['start_time']+$bar_info['affect_time']<$time){
				$data=array();
				$data['id']=$team_id;
				$data['status']=2;
				$data['update_time']=$time;
				$res=$this->data($data)->save();
				if($res){
					//推送失败的模板消息 2019/1/28
					$this->send_fail_template_msg($team_info['user_id'],$team_info['mer_id'],$bar_info['goods_id'],$team_info['bar_id'],$team_info['id'],$team_info['start_time']);
				}
				$team_info['status']=2;
			}
		}
		if($team_info['status']==0){
			$team_info['time_clock']=$team_info['start_time']+$bar_info['affect_time']-$time;	
		}
		$team_info['now_price']=($bar_info['tprice']*100-$team_info['bar_total_price']*100)/100;
		$team_info['bar_discount_price']=($team_info['now_price']*100-$team_info['floor_price']*100)/100;
		$distance_price=($bar_info['tprice']*100-$bar_info['floor_price']*100)/100;
		$team_info['percent']=floor(($team_info['bar_total_price']/$distance_price)*100);
		//不是发起者本人
		if($team_info['user_id']!=$user_id){
			$where=array();
			$where['user_id']=$user_id;
			$where['tid']=$team_id;
			$num=M('mall_bargain_user')->where($where)->count(1);
			if($num>0){
				$team_info['is_help']=1;
			}
		}else{
			$goods_arr=array();
			$goods_arr['store_id']=$bar_info['store_id'];
			$goods_arr['productId']=$bar_info['goods_id'];
			$goods_arr['count']=1;
			$goods_arr['productName']=$now_goods['name'];
			$goods_arr['extra_pay_price']=$now_goods['extra_pay_price'];
			$goods_arr['productStock']=$now_goods['stock_num'];
			$goods_arr['productPrice']=$team_info['now_price'];
			$goods_arr['productPackCharge']=(int)$now_goods['packing_charge'];
			$goods_arr['maxNum']=0;
			$goods_arr['minNum']=0;
			$goods_arr['unit']=$now_goods['unit'];
			$goods_arr['limit_type']=0;
			$goods_arr['oldPrice']=$bar_info['tprice'];
			$productParam=array();
			$spec_arr=explode('_',$bar_info['spec_id']);
			foreach ($now_goods['spec_list'] as $k => $v) {
				foreach ($v['list'] as $k2 => $v2) {
					if(in_array($v2['id'],$spec_arr)){
						$arr=array();
						$arr['type']='spec';
						$arr['spec_id']=$v['id'];
						$arr['id']=$v2['id'];
						$arr['name']=$v2['name'];
						$productParam[]=$arr;
					}
				}
			}
			$goods_arr['productParam']=$productParam;
			$json=json_encode(array($goods_arr),JSON_UNESCAPED_UNICODE);
			cookie('only_buy_cart_0',$json);
		}
		return (array)$team_info;
	}

	//获取当前砍价小组帮助的用户列表
	public function get_team_user_list($team_id){
		if(empty($team_id)){
			return array();
		}
		$list=M('mall_bargain_user')->alias('bu')->join('left join '.C('DB_PREFIX').'user us on bu.user_id=us.uid')->where(array('tid'=>$team_id))->field('bu.*,us.nickname,us.phone,us.avatar,us.openid')->select();
		return $list;
	}

	//推送砍价失败的模板消息
	public function send_fail_template_msg($user_id='',$mer_id='',$goods_id=null,$bar_id=null,$team_id='',$start_time=''){
		if(empty($user_id) || empty($goods_id) || empty($bar_id) || empty($team_id)){
			return false;
		}
		$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
		$href = C('config.site_url').'/wap.php?g=Wap&c=Mall&a=detail&goods_id='.$goods_id.'&bar_id='.$bar_id.'&team_id='.$team_id;
		$openid=M('user')->where(array('uid'=>$user_id))->getfield('openid');
		$first=C('config.mall_alias_name').L_('砍价失败');
		$orderid  = date('ymdhis').substr(microtime(),2,8-strlen($user_id)).$user_id;

		$model->sendTempMsg('TM00017', array(
            'href' => $href,
            'wecha_id' => $openid,
            'first' => $first,
            'OrderSn' => $orderid,
            'OrderStatus' => L_('砍价失败'),
            'remark' => date('Y-m-d H:i:s')
        ), $mer_id);
	}

	//获取砍价即将失败的团队信息
	public function get_fast_over_order(){
		$where=array();
		$time=time();
		$tip_end_time=$time+60*60;
		$tip_start_time=$tip_end_time-4;
		$where['_string']="team.start_time+act.affect_time>=".$tip_start_time." and team.start_time+act.affect_time<=".$tip_end_time;
		$where['team.status']=0;
		$where['us.openid']=array('neq','');
		$where['team.push_status']=0;
		$list=$this->alias('team')->join('inner join '.C('DB_PREFIX').'mall_bargain_act act on act.id=team.bar_id')->join('inner join '.C('DB_PREFIX').'user us on team.user_id=us.uid')->join('inner join '.C('DB_PREFIX').'shop_goods gd on act.goods_id=gd.goods_id')->where($where)->field('team.*,us.openid,act.spec_price,gd.name,act.goods_id,act.floor_price')->select();
		$data=array();
		$data['push_status']=1;
		foreach ($list as $k => $v) {
			$v['need_price']=($v['spec_price']*100-($v['bar_total_price']+$v['floor_price'])*100)/100;
			if($v['need_price']>0){
				$real_orderid  = date('ymdhis').substr(microtime(),2,8-strlen($v['user_id'])).$v['user_id'];
				$v['real_orderid']=$real_orderid;
				$list[$k]=$v;
			}else{
				unset($list[$k]);
			}
			$data['id']=$v['id'];
			$this->data($data)->save();
		}
		return $list;
	}

	//处理砍价订单支付后的处理
	public function check_bargain_order($order_info=null){
		if(empty($order_info)){
			return false;
		}
		//砍价用户购买
		if($order_info['bar_id']>0 && $order_info['buy_type']==3){
			$tid=$order_info['team_id'];
			$data=array();
			$data['id']=$tid;
			$data['status']=1;
			$data['update_time']=time();
			$this->data($data)->save();
		}
	}
}
?>