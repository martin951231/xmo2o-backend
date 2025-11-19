<?php
class Pushkit{
	protected static $password;
	protected static $cert;
	/*调试信息*/
	private static $sandboxSsl = 'tls://gateway.sandbox.push.apple.com:2195';
	/*正式信息*/
	private static $ssl = 'tls://gateway.push.apple.com:2195';

	public function __construct($cert,$password){
		self::$password=$password;
		self::$cert=$cert;
	}

    //iOS10之后，才支持title,subtitle
	public static function push($deviceToken='',$message){
		return self::push_message($deviceToken,$message);
	}

	public static function push_title($deviceToken='',$message,$title){
		return self::push_message($deviceToken,$message,$title);
	}

	/*自定义推送*/
	public static function push_message($deviceToken='',$message='',$title='',$subtitle=''){
		$aps=array(
			'alert'=>array(
				'title'=>$title,
				'subtitle'=>$subtitle,
				'body'=>$message
			),
			'sound'=>'default',
			'badge'=>1
		);
		return self::push_apns($deviceToken,$aps);
	}

	/*格式化推送*/
	public static function reg_push_message($app_data,$is_test=1){
		if($app_data['platform']['0']!='ios'){
			return array('status'=>0,'msg'=>'设备类型错误！');
		}
		$deviceToken=$app_data['audience']['tag']['0'];
		if(empty($deviceToken)){
			return array('status'=>0,'msg'=>'缺少设备号！');
		}
		$data=$app_data['message'];
		$notification=$app_data['notification'];

		$alert=array();
		$alert['title']=$data['title'];
		$alert['subtitle']='';
		$alert['body']=$data['msg_content'];
		$aps=array();
		$aps['alert']=$alert;
		$aps['sound']=$notification['ios']['sound']?$notification['ios']['sound']:'default';
		$aps['badge']=$notification['ios']['badge'];
		$aps['extras']=$data['extras'];
		return self::push_apns($deviceToken,$aps,$is_test);
	}

	public static function push_apns($deviceToken='',$aps=array('alert'=>'','sound'=>'default','badge'=>0),$is_test=1){
		if(strlen($deviceToken)<=0){
			return false;
		}

		$ctx = stream_context_create();
		stream_context_set_option($ctx, 'ssl', 'local_cert', self::$cert);
        stream_context_set_option($ctx, 'ssl', 'passphrase', self::$password);
        
        /*是否是测试环境*/
        $ssl=$is_test==1?self::$sandboxSsl:self::$ssl;
        $fp=stream_socket_client($ssl, $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);
        $now_time=time();
        if (!$fp) {
        	return array('status'=>0,'msg'=>"Failed to connect $err $errstrn",'time'=>$now_time);
        }

        $body=array();
        $body['aps']=$aps;
        # 把字典转化成 json字符串
        $payload = json_encode($body);
        // 这是去掉空格，什么的，因为token里面含有一些不用的符号
        $msg = chr(0) . pack("n",32) . pack('H*', str_replace(' ', '', $deviceToken)) . pack("n",strlen($payload)) . $payload;

        // 发生推送
        $result=fwrite($fp, $msg);
        fclose($fp);
		if($result){
			return array('status'=>1,'msg'=>'推送成功！','time'=>$now_time);
		}else{
			return array('status'=>0,'msg'=>'推送失败！','time'=>$now_time);
		}
	}
}

?>