<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/5/17 20:22
 */

class informationClueCollection
{
    public function qyapiSend($param) {
        
	    try {
		    $result = invoke_v20_service('\app\common\model\service\CacheSqlService/getIpLocationV20',['param' => ['ip'=>$param['add_ip']]]);
		    if (!empty($result)){
			    if (isset($result['data']['retval']['province'])){
				    $last_location['country'] =  $result['data']['retval']['province'];
			    }
			    if (isset($result['data']['retval']['city'])){
				    $last_location['area'] =  $result['data']['retval']['city'];
			    }
		    }else{
			    import('ORG.Net.IpLocation');
			    $IpLocation = new IpLocation();
			    $last_location = $IpLocation->getlocation($param['add_ip']);
			    $value['country'] = iconv('GBK','UTF-8',$last_location['country']);
			    $value['area'] = iconv('GBK','UTF-8',$last_location['area']);

		    }
	    }catch (Exception $e){
		    import('ORG.Net.IpLocation');
		    $IpLocation = new IpLocation();
		    $last_location = $IpLocation->getlocation($param['add_ip']);
		    $value['country'] = iconv('GBK','UTF-8',$last_location['country']);
		    $value['area'] = iconv('GBK','UTF-8',$last_location['area']);

	    }
 
        $data = json_encode([
            'msgtype' => 'text',
            'text' => [
                'content' => '手机号： ' . $value['phone'] . ' 
用户昵称： ' . $value['nickname'] .'
注册城市：' . $value['country'] . ' '.$value['area']. '
注册时间：' . date('Y-m-d H:i:s', $value['add_time']) . '
注册来源：' . $user_source . '
体验产品：' . $product
            ],
        ],JSON_UNESCAPED_UNICODE);
 		if (C('config.system_type') == 'village'){
			Http::curlPostOwn("https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=3c881b9a-4f48-4d26-9094-2b3186616487", $data);
		}else{
			Http::curlPostOwn("https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=8fd4b8f6-20af-45fe-8576-67790e3adb0c", $data);
		}

    }
}