<?php
//腾讯云即时通信 回调业务处理
class Live_imAction extends Action{
	public function back(){
		//禁止发言  不允许用户发言，该消息将不会下发，同时给调用方返回错误码10016。
		$no_speaking_tips = [
			'ActionStatus' => 'OK',
			'ErrorInfo' => '',
			'ErrorCode' => 1
		];
		//禁默丢弃（禁言） 不允许用户发言，该消息将不会下发，但会给调用方返回成功，使调用方以为消息已经发出。
		$no_speaking = [
			'ActionStatus' => 'OK',
			'ErrorInfo' => '',
			'ErrorCode' => 2
		];
		//允许发言  允许用户发言，同时也不修改即将下发的消息的内容。
		$allow_speaking = [
			'ActionStatus' => 'OK',
			'ErrorInfo' => '',
			'ErrorCode' => 0
		];
		$CallbackCommand = I("get.CallbackCommand");
		$json_input = file_get_contents("php://input");
		$input = json_decode($json_input, true);
		fdump($input, "lumin0319", 1);
		switch ($CallbackCommand) {
			case 'Group.CallbackBeforeSendMsg':
				$im_group_id = $input['GroupId'];
				//找到这条直播
				$now_live = M("Live_show")->where(['im_group_id' => $im_group_id])->find();
				$uid = $this->split_get_uid($input['From_Account'], $now_live['live_no']);
				//群内发言之前回调				
				if(empty($now_live)){
					$this->output($no_speaking_tips);
				}				
				if(empty($uid)){
					$this->output($no_speaking_tips);
				}
				$user = M("Live_show_room_user")->where(['live_show_id'=>$now_live['live_id'],'uid'=>$uid, 'no_speaking'=>1])->find();

				if($user){
					$this->output($no_speaking);
				}
				$data = [
					'uid' => $uid,
					'content' => $input['MsgBody'][0]['MsgContent']['Text'],
					'live_id' => $now_live['live_id'],
					'create_time' => date('Y-m-d H:i:s')
				];
				M("Live_show_comment")->data($data)->add();
				$this->output($allow_speaking);
				break;
			case 'Group.CallbackAfterNewMemberJoin':
				$im_group_id = $input['GroupId'];
				//找到这条直播
				$now_live = M("Live_show")->where(['im_group_id' => $im_group_id])->find();
				$uid = $this->split_get_uid($input['Operator_Account'], $now_live['live_no']);
				//新成员入群之后回调
				$data = [
					'live_show_id' => $now_live['live_id'],
					'uid' => $uid,
					'status' => 1,
					'create_time' => date('Y-m-d H:i:s'),
					'is_admin' => 0,
					'zan' => 0,
					'buy' => 0,
					'no_speaking' => 0
				];
				if($has = M("Live_show_room_user")->where(['live_show_id'=>$now_live['live_id'],'uid'=>$uid])->find()){
					M("Live_show_room_user")->where(['id'=>$has['id']])->data(['status' => 1])->save();
				}
				else{
					M("Live_show_room_user")->data($data)->add();
				}
				//更新观看峰值
				$online = $this->update_top_watch_nums($now_live['live_id'],$now_live['top_watch_nums']);
				import("@.ORG.TencentIM");
				$im = new TencentIM();
				//发送通知
				$user = D("User")->get_user($uid);
				$send = $im->send_system_notify($im_group_id, "join_new_member", ['nickname' => $user['nickname'], 'online'=>$online, 'seen'=>M("Live_show_room_user")->where(['live_show_id'=>$now_live['live_id']])->count()+$now_live['live_virtual_views'], 'type'=>'enter']);
				break;
			case 'Group.CallbackAfterMemberExit':
				$im_group_id = $input['GroupId'];
				//找到这条直播
				$now_live = M("Live_show")->where(['im_group_id' => $im_group_id])->find();
				$uid = $this->split_get_uid($input['Operator_Account'], $now_live['live_no']);
				//群成员离开之后回调
				if($has = M("Live_show_room_user")->where(['live_show_id'=>$now_live['live_id'],'uid'=>$uid])->find()){
					M("Live_show_room_user")->where(['id'=>$has['id']])->data(['status' => 2])->save();
				}
				//更新观看峰值
				$online = $this->update_top_watch_nums($now_live['live_id'],$now_live['top_watch_nums']);
				import("@.ORG.TencentIM");
				$im = new TencentIM();
				//发送通知
				$user = D("User")->get_user($uid);
				$send = $im->send_system_notify($im_group_id, "join_new_member", ['nickname' => $user['nickname'], 'online'=>$online, 'seen'=>M("Live_show_room_user")->where(['live_show_id'=>$now_live['live_id']])->count()+$now_live['live_virtual_views'], 'type'=>'leave']);
				break;
			// case 'State.StateChange':
			// 	//状态变更回调
			
			// 	break;

			default:
				# code...
				break;
		}
		$this->output($allow_speaking);
	}

	//更新观看峰值
	private function update_top_watch_nums($live_id, $old_nums = 0){
		if(empty($live_id)) return 0;
		$now = M("Live_show_room_user")->where(['status' => 1, 'live_show_id'=>$live_id])->count();
		if($now > $old_nums){
			M("Live_show")->where(['live_id'=>$live_id])->data(['top_watch_nums'=>$now])->save();
		}
		return $now;
	}

	//分隔字符串获取用户ID
	private function split_get_uid($account, $live_no){
		if(empty($account) || empty($live_no)) return "";
		$uid = substr($account, strlen($live_no));
		return $uid;
	}

	private function output($output){
		echo json_encode($output);
		exit;
	}
}