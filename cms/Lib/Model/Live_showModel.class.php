<?php
class Live_showModel extends Model
{
	//获取播放地址
	public function get_play_url($room_no){
		if(empty($room_no)) return "";
		if($_POST['app_type'] != 'packapp'){//非H5环境
			return 'rtmp://'.C("config.live_play_domain").'/live/'.$room_no;
		}
		else{
			return 'http://'.C("config.live_play_domain").'/live/'.$room_no.'.m3u8';	
		}
	}

	/**
      * 获取推流地址
      * 如果不传key和过期时间，将返回不含防盗链的url
      * @param domain 您用来推流的域名
      *        streamName 您用来区别不同推流地址的唯一流名称(也就是房间号)
      *        key 安全密钥
      *        time 过期时间 sample 2016-11-12 12:00:00
      * @return String url
	*/
	public function get_push_url($streamName){
		$key = C("config.live_push_apikey");
		$time = date('Y-m-d H:i:s',time()+24*60*60);
		$domain = C("config.live_push_domain");
		if($key && $time){
			$txTime = strtoupper(base_convert(strtotime($time),10,16));
			//txSecret = MD5( KEY + streamName + txTime )
			$txSecret = md5($key.$streamName.$txTime);
			$ext_str = "?".http_build_query(array(
		      	"txSecret"=> $txSecret,
		      	"txTime"=> $txTime
			));
		}
		return "rtmp://".$domain."/live/".$streamName . (isset($ext_str) ? $ext_str : "");
	}

	/**
	 * 正则表达式匹配live_id
	 * @return [type] [description]
	 */
	public function preg_liveid(){
		preg_match('/liveshow_([0-9]+)/', $_SERVER['HTTP_USER_AGENT'], $matches);
		if($_SESSION['current_liveshow_id']){
			return $_SESSION['current_liveshow_id'];
		}
		return $matches[1] ? : 0;
	}

	/**
	 * 正则表达式匹配video_id
	 * @return [type] [description]
	 */
	public function preg_videoid(){
		preg_match('/livevideo_([0-9]+)/', $_SERVER['HTTP_USER_AGENT'], $matches);
		if($_SESSION['current_livevideo_id']){
			return $_SESSION['current_livevideo_id'];
		}
		return $matches[1] ? : 0;
	}
}