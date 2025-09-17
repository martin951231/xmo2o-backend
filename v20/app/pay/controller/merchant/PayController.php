<?php
/**
 * 系统后台接口
 */
declare (strict_types = 1);

namespace app\pay\controller\merchant;

use app\merchant\controller\merchant\AuthBaseController;
use app\pay\model\service\PayService;


class PayController extends AuthBaseController
{
	public function getPayTypes(){
        $where = [
            'code' => 'wechat',
        ];
		$list = (new PayService)->getAllowPaytypes($where);
		return api_output(0, $list);
	}

	public function getPayTypeInfo(){
		$code = request()->param('code');
		if(empty($code)){
			return api_output_error(1001, 'code参数必填');
		}
		$pay = new PayService;
		$info = $pay->getSingleByCode($code);

		//获取当前支付方式有多少环境
		$group = $pay->getChannelByCode($code,$this->merchantUser['mer_id']);

    	$ret_group = [];
    	foreach ($group as $key => $value) {
    		$ret_group[] = [
    			'key' => $key,
    			'tab' => $pay->env[$key].'('.count($value).')'
    		];
    	}

		return api_output(0, ['basic'=>$info, 'all_group_channel'=>$ret_group]);
	}

	public function getChannels(){
		$code = request()->param('code');
		$env = request()->param('env');
		if(empty($code) || empty($env)){
			return api_output_error(1001, 'code|env参数必填');
		}
		$pay = new PayService;

		//获取当前支付方式有多少环境
		$group = $pay->getChannelByCode($code,$this->merchantUser['mer_id']);

		$env_channels = $group[$env] ?? [];

		//选择一个默认
		$default_channel = 0;
		foreach ($env_channels as $key => $value) {
			if($value['switch'] == '1'){
				$default_channel = $value['id'];
			}
		}
		if($default_channel === 0){
			$default_channel = $env_channels[0]['id'];
			$env_channels[0]['switch'] = 1;
		}
		return api_output(0, ['default_channel' => $default_channel, 'channels' => $env_channels]);
	}

	public function getChannelInfo(){
		$channel_id = request()->param('channel_id');
		if(empty($channel_id)){
			return api_output_error(1001, 'channel_id参数必填');
		}
		$pay = new PayService;
		$info = $pay->getChannelInfo($channel_id);
		$params = $pay->getChannelParam($channel_id);

		//返回前端需要的格式
		$output = [
			[
				'title' => '开启它',
				'name' => 'open_this',
				'type' => 'switch',
				'value' => $info['switch'] ? true : false,
				'tips' => ''
			]
		];
		foreach ($params as $key => $value) {
			$temp = [
				'title' => $value['title'],
				'name' => $value['name'],
				'type' => $pay->channelType[$value['type']],
				'required' => true,
				'value' => $value['value'],
				'tips' => $value['tips'],
				'selectArray' => [],
			];
			if($value['type'] == '4'){
				$options = explode('|||', $value['options']);
				$selectArray = [];
				foreach ($options as $opt) {
					$selectArray[] = [
						'label' => $opt,
						'value' => $opt
					];
				}
				$temp['selectArray'] = $selectArray;
			}
			$output[] = $temp;
		}
		return api_output(0, $output);
	}

	public function setChannelParams(){
		$channel_id = request()->param('channel_id');
		$form_data = request()->param('formData');
		if(empty($channel_id) || empty($form_data)){
			return api_output_error(1001, '请求参数异常！');
		}

		//校验参数
		foreach ($form_data as $key => $value) {
			if(empty($value) && $value !== false){
				return api_output_error(1001, $key.'不能为空！');
			}
		}

		$msg = '修改成功！';
//		$shutdown = false;
		$pay = new PayService;
//		if($form_data['open_this']){
//			$brothers = $pay->getMyBrothersChannel($channel_id);
//			if($brothers){
//				$msg = '修改成功！且检测到同环境下有其他通道已打开，我们已为您关闭其他通道！';
//				$shutdown = true;
//			}
//		}

		//修改通道参数
		if($pay->updateChannnelParams($channel_id, $form_data)){
//			if($shutdown){
//				$pay->shutdownChannels(array_column($brothers, 'id'));
//			}
			return api_output(0, [], $msg);
		}
		else{
			return api_output_error(1004, '修改通道参数失败!');
		}
	}
}
