<?php
/**
 * 系统后台接口
 */
declare (strict_types = 1);

namespace app\pay\controller\property;

use app\community\controller\CommunityBaseController;
use app\community\model\service\AdminLoginService;
use app\pay\model\service\PayService;


class PayController extends CommunityBaseController
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
		// 获取物业角色集合
        $loginRoleArr = (new AdminLoginService())->getPropertyLoginRoleNumberArr();
		if (!in_array($this->login_role, $loginRoleArr) || !$this->adminUser['property_id']) {
            return api_output_error(1001, '非物业身份登录');
        }
		
		$pay  = new PayService;
//		$info = $pay->getSingleByCode($code);

		//获取当前支付方式有多少环境
        $param = [
            'property_id' => $this->adminUser['property_id']
        ];
		$group = $pay->getChannelByCode($code,0, 0, 0, $param);

    	$ret_group = [];
    	foreach ($group as $key => $value) {
    	    // 先去除物业公众号的
            if ($key == 'wechat') {
                continue;
            }
    		$ret_group[] = [
    			'key' => $key,
    			'tab' => $pay->env[$key].'('.count($value).')'
    		];
    	}

		return api_output(0, ['basic'=>[], 'all_group_channel'=>$ret_group]);
	}

	public function getChannels(){
		$code = request()->param('code');
		$env  = request()->param('env');
		if(empty($code) || empty($env)){
			return api_output_error(1001, 'code|env参数必填');
		}
        // 获取物业角色集合
        $loginRoleArr = (new AdminLoginService())->getPropertyLoginRoleNumberArr();
        if (!in_array($this->login_role, $loginRoleArr) || !$this->adminUser['property_id']) {
            return api_output_error(1001, '非物业身份登录');
        }

        $pay = new PayService;

		//获取当前支付方式有多少环境
        $param = [
            'property_id' => $this->adminUser['property_id']
        ];
        $group = $pay->getChannelByCode($code,0, 0, 0, $param);

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
			$env_channels[0]['switch'] = '0';
		}
		return api_output(0, ['default_channel' => $default_channel, 'channels' => $env_channels]);
	}

	public function getChannelInfo(){
		$channel_id = request()->param('channel_id');
		if(empty($channel_id)){
			return api_output_error(1001, 'channel_id参数必填');
		}
        // 获取物业角色集合
        $loginRoleArr = (new AdminLoginService())->getPropertyLoginRoleNumberArr();
        if (!in_array($this->login_role, $loginRoleArr) || !$this->adminUser['property_id']) {
            return api_output_error(1001, '非物业身份登录');
        }
        
		$pay    = new PayService;
		$info   = $pay->getChannelInfo($channel_id);
		$params = $pay->getChannelParam($channel_id);

        fdump_api($info, '$info');

		//返回前端需要的格式
		$output = [
			[
				'title' => '开启它',
				'name'  => 'open_this',
				'type'  => 'switch',
				'value' => $info['switch'] ? '1' : '0',
				'tips'  => ''
			]
		];
		foreach ($params as $key => $value) {
			$temp = [
				'title'       => $value['title'],
				'name'        => $value['name'],
				'type'        => $pay->channelType[$value['type']],
				'required'    => true,
				'value'       => $value['value'],
				'tips'        => $value['tips'],
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
		$form_data  = request()->param('formData');
		if(empty($channel_id) || empty($form_data)){
			return api_output_error(1001, '请求参数异常！');
		}
        // 获取物业角色集合
        $loginRoleArr = (new AdminLoginService())->getPropertyLoginRoleNumberArr();
        if (!in_array($this->login_role, $loginRoleArr) || !$this->adminUser['property_id']) {
            return api_output_error(1001, '非物业身份登录');
        }
        
		//校验参数
		foreach ($form_data as $key => $value) {
			if(empty($value) && $value !== false){
				return api_output_error(1001, $key.'不能为空！');
			}
		}
		$msg = '修改成功！';
		$pay = new PayService;
		//修改通道参数
		if($pay->updateChannnelParams($channel_id, $form_data)){
			return api_output(0, [], $msg);
		}
		else{
			return api_output_error(1004, '修改通道参数失败!');
		}
	}
}
