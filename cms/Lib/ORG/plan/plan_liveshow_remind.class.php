<?php
class plan_liveshow_remind extends plan_base{
	public function runTask(){
		//这里把断流超过10分钟的直播强制结束
		$interrupt = M("Live_show")->where(['status'=>1,'is_interrupt'=>1,'is_del'=>0,'interrupt_last_time'=>['lt',time()-10*60]])->field("live_id,live_no")->select();
		if($interrupt){
			$save['real_end_time'] = date('Y-m-d H:i:s');
			$save['status'] = 3;
			$save['is_interrupt'] = 0;
			import("@.ORG.TencentIM");
            import("@.ORG.TencentLive");

			$live_lib = new TencentLive();
            $im_lib = new TencentIM();
			foreach($interrupt as $v){
				//查询腾讯云那边直播的状态
				if(!$live_lib->get_live_state($v['live_no'])){
					M("Live_show")->where(['live_id'=>$v['live_id']])->data($save)->save();
					$im_lib->send_system_notify($v['live_no'], 'unexpected_interruption', ['live_id' => $v['live_id']]);
				}
			}
			
		}
		
		//找到即将要开播的直播
		$min = 5;
		$string = 'unix_timestamp(estimate_time) < '.(time()+$min*60).' and unix_timestamp(estimate_time) > '.time();
		$live = array_column(M("Live_show")->where(['is_del'=>0,'status'=>2,'_string'=>$string])->field("live_id")->select(), 'live_id');
		if($live){
			//找到设置提醒了的用户
			$where = [
				'live_id' => ['in', $live],
				'remind' => 0,
				'is_del' => 0
			];
			$user = M("Live_show_remind")->where($where)->field("id,uid,live_id")->select();
			//发提醒
			if($user){
				foreach ($user as $key => $value) {
					$now_live = M("Live_show")->where(['live_id'=>$value['live_id']])->find();
					$now_user = D("User")->get_user($value['uid']);
					if ($now_user['openid']) {
			            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
			            $href = C('config.site_url')."/packapp/plat/pages/live_and_video/live/viewer/main_page?live_id=".$value['live_id'];
			            $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $now_user['openid'],'first' => '您预约直播【'.$now_live['title'].'】马上开始了哦，点击立即参与直播~', 'keyword1' => '直播', 'keyword2' => '马上开始', 'keyword3' => '将于'.$now_live['estimate_time'].'开始直播', 'keyword4' => $now_live['live_nickname'], 'remark' => L_('\n请点击查看详细信息！')));
			        }
					import('@.ORG.Apppush');
					$apppush = new Apppush();
			        $order['uid'] = $value['uid'];
			        $order['order_sn'] = $now_live['live_no'];
			        $order['title'] = '直播提醒';
			        $order['msg'] = '您预约直播【'.$now_live['title'].'】马上开始了哦，点击立即参与直播~';
					$apppush->send($order, 'liveshow_remind');
					M("Live_show_remind")->where(['id'=>$value['id']])->data(['remind'=>1])->save();
				}
			}
		}
		return true;
	}
}