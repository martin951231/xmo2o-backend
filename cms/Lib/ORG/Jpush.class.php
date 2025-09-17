<?php
class Jpush
{
    public $jpush_api = "https://api.jpush.cn/v3/push";
    public $user, $pwd;

    public function __construct($user = null, $pwd = null) {
        $this->user = $user;
        $this->pwd = $pwd;
        //$this->jpush_api = $jpush_api;
    }
    /**
    * 发送Jpush HTTP请求方法
    * @param  string $url    请求URL
    * @param  array  $params 请求参数
    * @param  string $method 请求方法GET/POST
    * @return array  $data   响应数据
    */
    public function request($url, $params, $method = 'POST', $header=array("Content-Type: application/json")){
		
		$authorization = base64_encode($this->user.':'.$this->pwd);
        $header[] = "Authorization: Basic ".$authorization;
		
        $opts = array(
                //CURLOPT_TIMEOUT        => 30,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_USERPWD => $this->user.':'.$this->pwd,
                CURLOPT_HTTPHEADER     => $header
        );
        /* 根据请求类型设置特定参数 */
        switch(strtoupper($method)){
            case 'GET':
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
                break;
            case 'POST':
                //判断是否传输文件
                //$params = $multi ? $params : http_build_query($params);
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = 1;
                $opts[CURLOPT_POSTFIELDS] = json_encode($params);
                break;
            default:
                throw new Exception('不支持的请求方式！');
        }
        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data  = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            //throw new ThinkException($error);
        }
        $dataArr=$data ? json_decode($data, true):array();
        return  $dataArr;
    }

    /**
    * 封装Jpush body体
    * @param  string  $platform     推送平台设置
    * @param  array   $audience     推送设备指定
    * @param  array   $notification 通知内容体
    * @param  array   $message      消息内容体
    * @return boolean $return       推送结果
    */
    public function send($platform, $audience, $notification, $message=null,$is_product=true) {
        $columns = array();
        $columns['platform'] = $platform;
		if($platform[0] != 'android' && isset($audience['tag'][0]) && is_string($audience['tag'][0])){
			$audience['tag'][0] = strtoupper($audience['tag'][0]);
		}
        $columns['audience'] = $audience;
		
		//极光FCM需要有个参数
		if($notification['android'] && $message['send_type']){
			if($message['send_type'] == 'storestaff'){
				$des = '店员通知';
				$appConfig  =   D('Appapi_app_config')->where(array('var'=>'storestaff_android_package_name'))->field(true)->find();
			}else{
				$des = '配送员通知';
				$appConfig  =   D('Appapi_app_config')->where(array('var'=>'deliver_android_package_name'))->field(true)->find();
			}
			$package_name = $appConfig['value'];
			$activity_path = $package_name . '.jpushvip.OpenClickActivity';
			$notification['android']['uri_action'] = $activity_path;
		}
		//删除极光VIP额外增加的配置参数，不删除会导致极光报错多了参数
		if($message['send_type']){
			unset($message['send_type']);
		}
		
        $columns['notification'] = $notification;
        if ($message) {
            $columns['message'] = $message;
        }
        $columns['options'] = array(
            'apns_production'   =>  $is_product,
            'time_to_live'      =>  86400,
        );
		
		
        $result = $this->request($this->jpush_api, $columns, "POST");
        fdump_api(array('columns'=>$columns,'result'=>$result),'00org_jpush_send'.date('H'),1);
        
        if ($result && isset($result['sendno']) && $result['sendno']>0) {
            return array('status'=>0, 'msg'=>'推送失败了','result'=>$result);
        }

        return array('status'=>1, 'msg'=>"推送成功",'result'=>$result);
    }

    public function createBody($client, $title, $msg, $extra, $sound='default') {
        switch ($client) {
            case 1:
                $return = array(
					'ios'   =>  array(
                        'alert'     => $msg,
                        'sound'     => $sound,
                        'badge'     => "+1",
                        'extras'    => $extra
                    )
                );break;
            case 2:
                $return = array(
                    'android'       =>  array(
                        'alert'      =>  $msg,
                        'title'      =>  $title,
                        'builder_id' =>  1,
                        'extras'     =>  $extra
                    )
                );break;
            case 3:
                $return = array(
                    'android'       =>  array(
                        'alert'      =>  $msg,
                        'title'      =>  $title,
                        'builder_id' =>  1,
                        'extras'     =>  $extra
                    ),
                    'ios'   =>  array(
                        'alert'     => $msg,
                        'sound'     => $sound,
                        'badge'     => "+1",
                        'extras'    => $extra
                    )
                );break;
            default:
                $return = array();
        }

        return $return;
    }

    public function createMsg($title, $msg, $extra) {
        $return =   array(
            'msg_content'   =>  $msg,
            'title'         =>  $title,
            'content_type'  =>  1,
            'extras'        =>  $extra,
        );
        return $return;
    }
}
