<?php

/**
 * 微信小程序消息推送
 *
 * @author: zt
 * @date: 2025/05/13
 */
class WechatMiniAction extends CommonAction
{
	public function index()
	{
		$config = [
			'wechat_encode' => C('config.wechat_mini_appid'),
			'wechat_token' => C('config.wechat_mini_token'),
			'wechat_encodingaeskey' => C('config.wechat_mini_encodingaeskey'),
			'wechat_appid' => C('config.wechat_mini_token')
		];
		$wechat = new Wechat($config);
		$data = $wechat->request();


		//记录日志,目前只记录“多媒体内容安全识别”结果的推送
		if ($data['MsgType'] == 'event' && $data['Event'] == 'wxa_media_check') {
			$data['result'] = $data['result'] ? json_encode($data['result'], JSON_UNESCAPED_UNICODE) : '';
			$data['detail'] = $data['detail'] ? json_encode($data['detail'], JSON_UNESCAPED_UNICODE) : '';
			D('wxapp_event_logs')->add($data);
		}
		exit("success");
	}
}
