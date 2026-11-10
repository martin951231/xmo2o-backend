<?php
/**
 * 系统后台接口
 */
declare (strict_types = 1);

namespace app\pay\controller\property;

use app\community\controller\CommunityBaseController;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillagePayConfig;
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

    /**
     * 获取当前物业下各小区的通联支付配置
     */
    public function getVillageAllinpayConfig()
    {
        $loginRoleArr = (new AdminLoginService())->getPropertyLoginRoleNumberArr();
        $propertyId = intval($this->adminUser['property_id'] ?? 0);
        if (!in_array($this->login_role, $loginRoleArr) || !$propertyId) {
            return api_output_error(1001, '非物业身份登录');
        }

        $villageList = (new HouseVillage())->getList(
            ['property_id' => $propertyId],
            'village_id,village_name',
            0,
            20,
            'village_id ASC'
        );
        $villageList = $villageList ? $villageList->toArray() : [];
        if (!$villageList) {
            return api_output(0, []);
        }

        $villageIds = array_column($villageList, 'village_id');
        $configList = (new HouseVillagePayConfig())
            ->where([['village_id', 'in', $villageIds]])
            ->field('village_id,allinpay_appid,allinpay_mchid,allinpay_private_key,allinpay_status')
            ->select()
            ->toArray();
        $configMap = array_column($configList, null, 'village_id');

        foreach ($villageList as &$village) {
            $config = $configMap[$village['village_id']] ?? [];
            $village['appid'] = $config['allinpay_appid'] ?? '';
            $village['mchid'] = $config['allinpay_mchid'] ?? '';
            $village['private_key'] = $config['allinpay_private_key'] ?? '';
            $village['status'] = intval($config['allinpay_status'] ?? 0);
        }
        unset($village);

        return api_output(0, $villageList);
    }

    /**
     * 保存单个小区的通联支付配置
     */
    public function setVillageAllinpayConfig()
    {
        $loginRoleArr = (new AdminLoginService())->getPropertyLoginRoleNumberArr();
        $propertyId = intval($this->adminUser['property_id'] ?? 0);
        if (!in_array($this->login_role, $loginRoleArr) || !$propertyId) {
            return api_output_error(1001, '非物业身份登录');
        }

        $villageId = request()->param('village_id', 0, 'intval');
        $appid = trim((string)request()->param('appid', ''));
        $mchid = trim((string)request()->param('mchid', ''));
        $privateKey = trim((string)request()->param('private_key', ''));
        $status = request()->param('status', null);
        if (!$villageId || $appid === '' || $mchid === '' || $privateKey === '' || !in_array((string)$status, ['0', '1'], true)) {
            return api_output_error(1001, 'AppID、商户号、通联私钥和开启状态均为必填项');
        }

        $village = (new HouseVillage())->getInfo([
            'village_id' => $villageId,
            'property_id' => $propertyId,
        ], 'village_id');
        if (!$village) {
            return api_output_error(1001, '小区不存在或不属于当前物业');
        }

        $payConfigModel = new HouseVillagePayConfig();
        $payConfig = $payConfigModel->getOne(['village_id' => $villageId], 'id');
        $saveData = [
            'allinpay_appid' => $appid,
            'allinpay_mchid' => $mchid,
            'allinpay_private_key' => $privateKey,
            'allinpay_status' => intval($status),
        ];
        if ($payConfig) {
            $result = $payConfigModel->where(['id' => $payConfig['id']])->update($saveData);
        } else {
            $saveData['property_id'] = $propertyId;
            $saveData['village_id'] = $villageId;
            $saveData['add_time'] = time();
            $result = $payConfigModel->insert($saveData);
        }
        if ($result === false) {
            return api_output_error(1004, '保存失败，请重试');
        }

        return api_output(0, [], '保存成功');
    }

    /**
     * 生成通联支付使用的2048位RSA私钥
     */
    public function generateAllinpayPrivateKey()
    {
        $loginRoleArr = (new AdminLoginService())->getPropertyLoginRoleNumberArr();
        $propertyId = intval($this->adminUser['property_id'] ?? 0);
        if (!in_array($this->login_role, $loginRoleArr) || !$propertyId) {
            return api_output_error(1001, '非物业身份登录');
        }

        $options = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $openSslConfigList = [
            getenv('OPENSSL_CONF'),
            dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . 'extras' . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'openssl.cnf',
        ];
        foreach ($openSslConfigList as $openSslConfig) {
            if ($openSslConfig && is_file($openSslConfig)) {
                $options['config'] = $openSslConfig;
                break;
            }
        }

        while (openssl_error_string() !== false) {
        }
        $keyResource = @openssl_pkey_new($options);
        $privateKey = '';
        $exportResult = $keyResource && @openssl_pkey_export($keyResource, $privateKey, null, $options);
        if (!$keyResource || !$exportResult || !$privateKey) {
            $errors = [];
            while (($error = openssl_error_string()) !== false) {
                $errors[] = $error;
            }
            return api_output_error(1004, 'RSA私钥生成失败：' . ($errors ? end($errors) : 'OpenSSL配置异常'));
        }

        return api_output(0, ['private_key' => $privateKey]);
    }
}
