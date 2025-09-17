<?php
/**
 * 物业演示用
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/5/17 20:26
 */

namespace app\community\model\service;

use app\traits\WebIpLocationTraits;
use net\Http as Http;

class propertyDemoService
{
	use WebIpLocationTraits;
    /**
     * Notes: 发送消息至企业微信
     * @param $param
     * @return bool
     * @author: wanzy
     * @date_time: 2021/5/17 20:29
     */
    public function qywxSend($param) {
        $is_demo_domain = cfg('is_demo_domain');
        if (!$is_demo_domain) {
            return true;
        }
//        fdump_api(['发送消息'.__LINE__,$param],'work_qyweixin/qywxSendLog',1);
//        $product = '智慧物业SAAS';
	    $ip = request()->ip();
	    $ipdata = $this->getIpBrowserInfo($ip);
	    $city = isset($ipdata['province']) ? $ipdata['province'] : ' '. isset($ipdata['city']) ? $ipdata['city'] : ' ' . isset($ipdata['isp']) ? $ipdata['isp'] : '';
        $data = json_encode([
            'msgtype' => 'text',
            'text' => [
                'content' => '预约产品演示【智慧物业SAAS】'."\n".'
注册城市：' . $city . '
客户姓名：' . $param['name'] . '
手机号：' . $param['phone'] . '
公司名称：' . $param['company'] . '
提交时间：' . date('Y-m-d H:i:s', $param['add_time'])
            ],
        ],JSON_UNESCAPED_UNICODE);

	    //change 2023年01月07日14:16:32
	    $postParams = [
		    'name' 			=> $param['nickname'] ?: '未知姓名',
		    'phone' 		=> $param['phone'],
		    'company' 		=> $param['company'],
		    'source_name' 	=> '预约产品演示【智慧物业SAAS】',
		    'province' 		=> $param['country'],
		    'city' 			=> $param['area'],
		    'system_type' 	=> cfg('system_type'),
		    'extra' 		=> '',
	    ];
		

//	    if (cfg('system_type') == 'village'){
//		     Http::curlQyWxPost("https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=3c881b9a-4f48-4d26-9094-2b3186616487", $data);
		     Http::curlQyWxPost("http://o2o-service.pigcms.com/custom-send.php", $postParams);
			 
//	    }else{
//		    Http::curlQyWxPost("https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=8fd4b8f6-20af-45fe-8576-67790e3adb0c", $data);
//	    }
        return true;
    }
}