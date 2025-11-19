<?php
/*
 * 腾讯云即时聊天
 *
 * 	文档地址 https://cloud.tencent.com/document/product/269
 */
include_once 'tencentIM/TLSSigAPIv2.php';
class TencentIM{
	private $domain = null;
	private $sdkappid = null;
	private $key = null;
	private $administrator = null;
	public function __construct(){
		$this->sdkappid = trim(D("Config")->get_config_by_name("tencent_SDKAppID")['value']);//这里就是这个写法没问题的哈，别乱改成C方法的写法
		$this->key = trim(D("Config")->get_config_by_name("tencent_key")['value']);
		$this->administrator = trim(D("Config")->get_config_by_name("tencent_administrator")['value']);
		$this->domain = 'https://console.tim.qq.com/';
	}

	public function get_usersig($account = ''){
		if(empty($account)){
			$account = $this->administrator;
		}
		$api = new \Tencent\TLSSigAPIv2($this->sdkappid, $this->key);
		$sig = $api->genSig($account);
		return $sig;
	}

	//获取请求地址
	private function rest_url($servicename){
		if(empty($servicename)) return "";
		$random = rand(10000000,99999999);
		$rest_url = $this->domain.$servicename.'?sdkappid='.$this->sdkappid.'&identifier='.$this->administrator.'&usersig='.$this->get_usersig().'&random='.$random.'&contenttype=json';
		return $rest_url;
	}

	//创建群组
	public function create_group($room_no){
		if(empty($room_no)) return ['errcode' => 1, 'errmsg' => '必填参数未填'];
		$servicename = 'v4/group_open_http_svc/create_group';
		$rest_url = $this->rest_url($servicename);
		$post = [
			'Type' => 'AVChatRoom',
			'GroupId' => $room_no,
			'Name' => '直播间'.$room_no,
			'Notification' => C("config.live_show_notice")
		];
		import('ORG.Net.Http');
		$json = json_encode($post);
		$return = Http::curlPost($rest_url, $json, 15, 'json');
		if($return['errcode'] === 0 && $return['ActionStatus'] == 'OK'){
			if($return['ErrorCode'] == '0'){
				return ['errcode' => 0, 'errmsg' => '', 'group_id' => $return['GroupId']];
			}
			else{
				return ['errcode' => 1, 'errmsg' => '请求腾讯云接口报错，腾讯云返回错误码：'.$return['ErrorCode'].';错误信息：'.$return['ErrorInfo']];
			}
		}
		else{
		    if($return['ErrorCode']==10036){
                $return['ErrorInfo'] = '创建的音视频聊天室数量超过限制，请先解散部分音视频聊天室或者购买升级';
            }
			return ['errcode' => 1, 'errmsg' => $return['ErrorCode'].';错误信息：'.$return['ErrorInfo']];
		}
	}

	//解散群组
	public function destroy_group($room_no){
		if(empty($room_no)) return ['errcode' => 1, 'errmsg' => '必填参数未填'];
		$servicename = 'v4/group_open_http_svc/destroy_group';
		$rest_url = $this->rest_url($servicename);
		$post = [
			'GroupId' => $room_no
		];
		import('ORG.Net.Http');
		$json = json_encode($post);
		$return = Http::curlPost($rest_url, $json, 15, 'json');
		if($return['errcode'] === 0 && $return['ActionStatus'] == 'OK'){
			if($return['ErrorCode'] == '0'){
				return ['errcode' => 0, 'errmsg' => '', 'group_id' => $return['GroupId']];
			}
			else{
				return ['errcode' => 1, 'errmsg' => '请求腾讯云接口报错，腾讯云返回错误码：'.$return['ErrorCode'].';错误信息：'.$return['ErrorInfo']];
			}
		}
		else{
			return ['errcode' => 1, 'errmsg' => $return['ErrorCode'].';错误信息：'.$return['ErrorInfo']];
		}
	}

	//对群组发送系统通知
	public function send_system_notify($im_group_id, $type, $param = [], $ToMembers = []){
		if(empty($im_group_id) || empty($type)) return ['errcode' => 1, 'errmsg' => '必填参数未填'];
		switch ($type) {
			case 'start_recording':
			case 'end_recording':
				//开始/结束录制商品
				$content = $type.'{pigcms}'.$param['goods_id'];				
				break;
			case 'upper_screen':
				//上屏
				$content = $type.'{pigcms}'.$param['goods_id'];				
				break;
			case 'send_coupon':
				//发送优惠券（播放端天降优惠券）
				$content = $type.'{pigcms}'.$param['coupon_id'];				
				break;
			case 'live_ending':
				//直播结束
				$content = $type.'{pigcms}'.$param['live_id'];				
				break;
            case 'unexpected_interruption':
                //直播意外结束（计划任务关闭）
                $content = $type.'{pigcms}'.$param['live_id'];
                break;
            case 'forbid_live':
                //系统后台禁播直播
                $content = $type.'{pigcms}'.$param['msg'];
                break;
			case 'join_new_member':
				//进入直播间
				$content = $type.'{pigcms}'.$param['nickname'].'{pigcms}'.$param['seen'].'{pigcms}'.$param['online'].'{pigcms}'.$param['type'];
				break;
			case 'goto_buy':
				//正在购买
				$content = $type.'{pigcms}'.$param['nickname'];				
				break;
			case 'follow_live':
				//关注主播
				$content = $type.'{pigcms}'.$param['nickname'];				
				break;
			case 'share_live':
				//分享直播
				$content = $type.'{pigcms}'.$param['nickname'];				
				break;
			case 'liveshow_zan':
				//点赞
				$content = $type.'{pigcms}'.$param['zan'];				
				break;
			case 'set_admin':
				//设置是否为管理员
                $content = $type.'{pigcms}'.$param['is_admin'].'{pigcms}'.'liveUid_'.($ToMembers?$ToMembers[0]:'');
				break;
			case 'set_speaking':
				//设置是否禁言
                $content = $type.'{pigcms}'.$param['is_speaking'].'{pigcms}'.'liveUid_'.($ToMembers?$ToMembers[0]:'');
				break;
			case 'begin_push':
				//设置是否禁言
				$content = $type.'{pigcms}';				
				break;
		}
		if(!$content){
			return ['errcode' => 1, 'errmsg' => '没有获取到发送内容'];
		}
		$post = [
			'GroupId' => $im_group_id,
			'Content' => $content,
		];
		if($ToMembers){
			$post['ToMembers_Account'] = $ToMembers;
		}
		$servicename = 'v4/group_open_http_svc/send_group_system_notification';
		$rest_url = $this->rest_url($servicename);
		import('ORG.Net.Http');
		$json = json_encode($post);
		$return = Http::curlPost($rest_url, $json, 15, 'json');
		if($return['errcode'] === 0 && $return['ActionStatus'] == 'OK'){
			if($return['ErrorCode'] == '0'){
				return ['errcode' => 0, 'errmsg' => ''];
			}
			else{
				return ['errcode' => 1, 'errmsg' => '请求腾讯云接口报错，腾讯云返回错误码：'.$return['ErrorCode'].';错误信息：'.$return['ErrorInfo']];
			}
		}
		else{
			return ['errcode' => 1, 'errmsg' => '请求腾讯云接口报错，腾讯云返回错误码：'.$return['ErrorCode'].';错误信息：'.$return['ErrorInfo']];
		}
	}
}