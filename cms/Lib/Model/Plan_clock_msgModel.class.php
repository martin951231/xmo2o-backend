<?php
//计划定时消息模型类
class Plan_clock_msgModel extends Model{
	//判断是否已存在
	public function check_bespoke_msg($token){
		$num=$this->where(array('token'=>$token))->count(1);
		return $num;
	}

	//增加模板预约消息
	public function add_bespoke_msg($user_id,$goods_id,$token,$start_time,$is_app=false){
		$content1=$this->get_app_bespoke_msg($user_id,$goods_id,$start_time);
        if(!$content1){
            if($is_app){
                return array('error'=>1,'msg'=>L_('网络出错，请重试！'));
            }
        }else{
            $data=array();
            $data['content']=$content1;
            $data['token']=$token;
            $data['send_time']=$start_time-3*60;
            $data['status']=0;
            $data['update_time']=time();
            $data['type']=4;
            $res1=$this->data($data)->add();
        }
        $content2=$this->get_bespoke_template($goods_id,$start_time);
        if(is_array($content2) && $content2['error']==1){
            if(!$is_app){
                return $content2;    
            }
		}else{
            $data=array();
            $data['content']=$content2;
            $data['token']=$token;
            $data['send_time']=$start_time-3*60;
            $data['status']=0;
            $data['update_time']=time();
            $data['type']=2;
            $res2=$this->data($data)->add();
        }
		if($res1 || $res2){
			return array('error'=>0,'msg'=>L_('预约成功！'));
		}else{
			return array('error'=>1,'msg'=>L_('网络出错，请重试！'));
		}
	}

    //获取预约的app推送消息
    public function get_app_bespoke_msg($uid,$goods_id,$start_time){
        $login_log = D("Appapi_app_login_log")->field(true)->where(array('uid' => $uid))->order("create_time DESC")->find();
        if(empty($login_log)) return false;

        $client = $login_log['client'];
        if ($client == 1) {
            $device_id = str_replace('-', '', $login_log['device_id']);
            $audience = array('tag' => array($device_id));
        } else {
            $audience = array('tag' => array($login_log['device_id']));
        }
        $seckill_alias_name=C('config.seckill_alias_name');
        $seckill_alias_name=$seckill_alias_name?$seckill_alias_name:L_('限时抢购');
        $title=$seckill_alias_name.L_('商品开始提醒');

        $goods_info=M('Shop_goods')->where(array('goods_id'=>$goods_id))->field('name,store_id')->find();
        $time=date('H:i',$start_time);
        $extra=array();
        $extra['pigcms_tag']='bespoke_tag';
        $extra['tag_desc']=L_("您预约的【X1】商品将在今天X2开始",array("X1" => $goods_info['name'],"X2" => $time)); 
        //链接地址
        $url=C('config.site_url').U('Mall/detail',array('goods_id'=>$goods_id));
        $url=str_replace('/static/','/',$url);
        $extra['url']=$url;

        $msg=L_('点击详情马上参与');

        import('@.ORG.Jpush');
        $jpush = new Jpush();
        $notification = $jpush->createBody(3, $title, $msg, $extra);
        $message = $jpush->createMsg($title, $msg, $extra);

        $columns = array();
        $columns['platform'] = $client == 1 ? array('ios') : array('android');
        $columns['audience'] = $audience;
        $columns['notification'] = $notification;
        $columns['message'] = $message;
        return serialize(array($columns));
    }

	//获取预约的微信模板消息
   	public function get_bespoke_template($goods_id,$start_time){
   		$where=array();
   		$where['tempkey']='OPENTM405462911';
   		$where['status']=1;
   		$info=M('tempmsg')->where($where)->find();
   		if(empty($info) || empty($info['tempid'])){
   			return array('error'=>1,'msg'=>L_('暂不支持预约功能！'));
   		}
   		$content=array();
   		$content['touser']=$_SESSION['user']['openid'];
   		if(empty($content['touser'])){
   			return array('error'=>1,'msg'=>L_('抱歉，您的账号暂未绑定微信，不能使用预约功能！'));
   		}
   		$content['template_id']=$info['tempid'];
   		//链接地址
   		$url=C('config.site_url').U('Mall/detail',array('goods_id'=>$goods_id));
        $url=str_replace('/static/','/',$url);
   		$content['url']=$url;
   		$content['topcolor']=$info['topcolor'];
   		//消息内容
   		$data=array();
   		$data['first']=array(
   			'value'=>L_('已预约活动开始提醒'),
   			'color'=>$info['textcolor']
   		);
   		$seckill_alias_name=C('config.seckill_alias_name');
   		$seckill_alias_name=$seckill_alias_name?$seckill_alias_name:L_('限时抢购');
   		$data['keyword1']=array(
   			'value'=>$seckill_alias_name.L_('商品开始提醒'),
   			'color'=>$info['textcolor']
   		);
   		$data['keyword2']=array(
   			'value'=>L_('已发送'),
   			'color'=>$info['textcolor']
   		);
   		$date=date('Y/m/d H:i:s');
   		$data['keyword3']=array(
   			'value'=>$date,
   			'color'=>$info['textcolor']
   		);
   		$goods_info=M('Shop_goods')->where(array('goods_id'=>$goods_id))->field('name,store_id')->find();
   		$time=date('H:i',$start_time);
   		$data['keyword4']=array(
   			'value'=>L_("您预约的【X1】商品将在今天X2开始",array("X1" => $goods_info['name'],"X2" => $time)), 
   			'color'=>$info['textcolor']
   		);
   		$data['remark']=array(
   			'value'=>L_('点击详情马上参与'),
   			'color'=>$info['textcolor']
   		);
   		$content['data']=$data;
   		$result=array();
   		$result['mer_id']=0;//M('Merchant_store')->where(array('store_id'=>$goods_info['store_id']))->getfield('mer_id');
   		$content = str_replace("\\/", "/", json_encode($content,JSON_UNESCAPED_UNICODE)) ;
   		$result['content']=$content;
   		return serialize(array($result));
   	} 
}
?>