<?php
/**
 * 支付通道的service
 * add by lumin
 * 2020-05-25
 */

namespace app\pay\model\service\channel;

class ChannelService{
	/**
	 * 获取通道service
	 * @param string $channel_name 通道名称
	 * @param string $agent 浏览器标识（对应Agent中间件）
	 * @param array $channel_param 通道所需的参数（一维数组）
	 * @return Object 通道service
	 */
	public static function getChannelService($channel_name, $agent, $channel_param = []){
		if(empty($channel_name) || empty($agent)) return null;
		$class = 'app\\pay\\model\\service\\channel\\'.ucfirst($channel_name).'Service';
		return new $class($agent, $channel_param);
	}
}