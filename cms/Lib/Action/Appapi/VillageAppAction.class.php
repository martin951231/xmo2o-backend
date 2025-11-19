<?php

class VillageAppAction extends BaseAction{
    public $appid;
    public $appkey;
    public $thirdConfig;
    public function __construct()
    {
        parent::__construct();
        $this->appid = trim($this->config['third_building_appid']);
        $this->appkey = trim($this->config['third_building_appkey']);
        if (!$this->appid || !$this->appkey) {
            // 系统未配置此功能，无法使用。
            $this->returnCodeError('系统未配置此功能，无法使用');
        }

        if(IS_POST){
            //todo... 简单对比下，没有时间开发更有强度的校验
            if($_POST['appid'] != $this->appid || $_POST['appkey'] != $this->appkey){
                $this->returnCodeError('参数校验失败');
            }
        }
    }
    
    /**
     * URL跳转.
     */
	public function url()
    {
        if(md5($_SERVER['original_get']['url'] . $this->appid . $this->appkey) != $_SERVER['original_get']['key']){
            exit('鉴权失败.');
        }
		if(strpos($_SERVER['original_get']['url'], 'Application&a=detail') > 0){
			$url = '/v20/public/platform/#/village/village.iframe/house_application_index?url=' . urlencode($_SERVER['original_get']['url']);
		}else{
			$url = $_SERVER['original_get']['url'];
		}
        echo $url;
        $villageId = I('villageId');
        $propertyId = I('propertyId');
        if ($villageId) {
            $database_village = D('House_village');
            $condition_village['village_id'] = $villageId;
            $now_village = $database_village->field(true)->where($condition_village)->find();
            if (empty($now_village) || $now_village['status'] == 2) {
                // 报错格式看下是否需要更改
                $this->returnCodeError("该' . C('config.house_name') . '的状态不存在！请查阅。");
            }
            // 是否为平台登录
            $now_village['is_system'] = 1;
            //查询权限 超级管理员赋予所有权限
            $admin_menus = D('House_menu_new')->field(true)->where(array('status' => 1))->select();
            $menus = array();
            foreach ($admin_menus as $value) {
                $menus[] = $value['id'];
            }
            $now_village['menus'] = $menus;
            session('house', $now_village);
            import("@.ORG.Token");
            // 给予小区管理员身份
            $ticket = Token::createToken($villageId, 6);
            setcookie('village_access_token', $ticket, $_SERVER['REQUEST_TIME'] + 10000000, '/');
            fdump($url,'$url');
            redirect($url);
        } elseif ($propertyId) {
            $database_property = D('House_property');
            $condition_village['id'] = $propertyId;
            $now_property = $database_property->field(true)->where($condition_village)->find();
            $now_property['login_identify'] = 1;
            session('property', $now_property);
            import("@.ORG.Token");
            // 给予物业管理员身份
            $ticket = Token::createToken($propertyId, 8);
            setcookie('property_access_token', $ticket, $_SERVER['REQUEST_TIME'] + 10000000, '/');
            $database_property->where($condition_village)->data(array('last_time' => time()))->save();
            redirect($url);
        }
        dump($_SERVER['original_get']);
        dump($_SERVER['original_post']);
        die;
		redirect($url);
	}

    /**
     * 创建物业.
     */
    public function createProperty()
    {
        $property_name = trim($_POST['property_name']);
        if(!$property_name){
            $this->returnCodeError('物业名称参数必带，请重试');
        }
        $thirdType = trim($_POST['thirdType']);
        if (!$thirdType) {
            $this->returnCodeError('三方对应类型参数必带，请重试');
        }
        $thirdSite = trim($_POST['thirdSite']);
        if (!$thirdSite) {
            $this->returnCodeError('三方对应域名必带，请重试');
        }
        $thirdId = isset($_POST['thirdPropertyId'])&&trim($_POST['thirdPropertyId'])?trim($_POST['thirdPropertyId']):0;
        $businessType = 'property';
        $dbHouseProperty = D('House_property');
        if ($thirdId) {
            if (empty($this->thirdConfig)) {
                import('@.ORG.ThirdAccess.thirdConfig');
                $this->thirdConfig = new thirdConfig();
            }
            // 存在三方对应物业id 查询下是否已经存在了对应物业
            $whereThirdConfig = [
                'businessType' => $businessType,
                'thirdId' => $thirdId,
            ];
            try {
                $info = $this->thirdConfig->getHouseThirdConfig($whereThirdConfig);
            } catch (\Exception $e) {
                $this->returnCodeError($e->getMessage());
            }
            if ($info&&$info['businessId']) {
                $whereProperty = [
                    'id' => $info['businessId'],
                ];
                $propertyInfo = $dbHouseProperty->where($whereProperty)->find();
                if ($propertyInfo && $propertyInfo['status']==2) {
                    $this->returnCodeError('您的物业已被禁用，无法使用');
                } elseif ($propertyInfo && $propertyInfo['status']==4) {
                    $this->returnCodeError('您的物业已被禁用，无法使用');
                }
                if ($propertyInfo) {
                    $this->returnCodeOk(['propertyId' => $propertyInfo['id']]);
                }
            }
        }
        $nowTime = time();
        $data = [
            'property_name' => $property_name,
            'property_address' => $_POST['property_address'] ?? '',
            'province_id' => $_POST['province_id'] ?? 0,
            'city_id' => $_POST['province_id'] ?? 0,
            'area_id' => $_POST['province_id'] ?? 0,
            'status' => '1',
            'create_time' => $nowTime,
            'last_time' => $nowTime,
        ];
        $propertyId = $dbHouseProperty->data($data)->add();
        
        if(!$propertyId){
            $this->returnCodeError('物业创建失败，请重试');
        }
        // 绑定对应套餐
        $this->PermanentlyFreePrivilegePackage($propertyId);
        D('House_village')->setSoftwareNewEffectCharge($propertyId);
        // ↓ 添加对应三方信息
        $businessId = $propertyId;
        $thirdName = isset($_POST['thirdName'])&&trim($_POST['thirdName'])?trim($_POST['thirdName']):'';
        $paramData = [
            'businessType' => $businessType,
            'businessId' => $businessId,
            'thirdType' => $thirdType,
            'thirdSite' => $thirdSite,
            'thirdId' => $thirdId,
            'thirdName' => $thirdName,
            'nowTime' => $nowTime,
        ];
        if (empty($this->thirdConfig)) {
            import('@.ORG.ThirdAccess.thirdConfig');
            $this->thirdConfig = new thirdConfig();
        }
        try {
            $this->thirdConfig->setHouseThirdConfig($paramData);
        } catch (\Exception $e) {
            $this->returnCodeError($e->getMessage());
        }
        // ↑ 添加对应三方信息
        $this->returnCodeOk(['propertyId' => $propertyId]);
    }

    /**
     * 编辑物业
     */
    public function editProperty()
    {
        if(!$_POST['property_id']){
            $this->returnCodeError('ID参数必带，请重试');
        }
        $nowTime = time();
        $data = [
            'last_time' => $nowTime,
        ];
        isset($_POST['property_name']) && $data['property_name'] = $_POST['property_name'];
        isset($_POST['property_address']) && $data['property_address'] = $_POST['property_address'];
        isset($_POST['province_id']) && $data['province_id'] = $_POST['province_id'];
        isset($_POST['city_id']) && $data['city_id'] = $_POST['city_id'];
        isset($_POST['area_id']) && $data['area_id'] = $_POST['area_id'];

        if(D('House_property')->where(['id' => $_POST['property_id']])->data($data)->save() === false){
            $this->returnCodeError('信息编辑失败，请重试');
        }
        $businessId = $_POST['property_id'];
        $thirdType = trim($_POST['thirdType']);
        $thirdSite = trim($_POST['thirdSite']);
        $businessType = 'property';
        $thirdName = isset($_POST['thirdName'])&&trim($_POST['thirdName'])?trim($_POST['thirdName']):'';
        $paramData = [
            'businessType' => $businessType,
            'businessId' => $businessId,
            'thirdType' => $thirdType,
            'nowTime' => $nowTime,
        ];
        if (isset($_POST['thirdPropertyId'])) {
            $thirdId = trim($_POST['thirdPropertyId'])?trim($_POST['thirdPropertyId']):0;
            $paramData['thirdId'] = $thirdId;
        }
        if (isset($_POST['thirdName'])) {
            $paramData['thirdName'] = $thirdName;
        }
        if (isset($_POST['thirdSite'])) {
            $paramData['thirdSite'] = $thirdSite;
        }
        if (empty($this->thirdConfig)) {
            import('@.ORG.ThirdAccess.thirdConfig');
            $this->thirdConfig = new thirdConfig();
        }
        try {
            $this->thirdConfig->setHouseThirdConfig($paramData);
        } catch (\Exception $e) {
            $this->returnCodeError($e->getMessage());
        }
        // ↑ 添加对应三方信息
        $this->returnCodeOk('信息编辑成功');
    }

    /**
     * 添加小区.
     */
    public function createVillage()
    {
        if(!$_POST['property_id']){
            $this->returnCodeError('ID参数必带，请重试');
        }
        $village_name = trim($_POST['village_name']);
        if(!$village_name){
            $this->returnCodeError('小区名称参数必带，请重试');
        }
        $thirdType = trim($_POST['thirdType']);
        if (!$thirdType) {
            $this->returnCodeError('三方对应类型参数必带，请重试');
        }
        $thirdSite = trim($_POST['thirdSite']);
        if (!$thirdSite) {
            $this->returnCodeError('三方对应域名必带，请重试');
        }
        $thirdId = isset($_POST['thirdVillageId'])&&trim($_POST['thirdVillageId'])?trim($_POST['thirdVillageId']):0;
        $businessType = 'village';
        $dbHouseVillage = D('House_village');
        if ($thirdId) {
            if (empty($this->thirdConfig)) {
                import('@.ORG.ThirdAccess.thirdConfig');
                $this->thirdConfig = new thirdConfig();
            }
            // 存在三方对应物业id 查询下是否已经存在了对应物业
            $whereThirdConfig = [
                'businessType' => $businessType,
                'thirdId' => $thirdId,
            ];
            try {
                $info = $this->thirdConfig->getHouseThirdConfig($whereThirdConfig);
            } catch (\Exception $e) {
                $this->returnCodeError($e->getMessage());
            }
            if ($info&&$info['businessId']) {
                $whereVillage = [
                    'village_id' => $info['businessId'],
                ];
                $villageInfo = $dbHouseVillage->where($whereVillage)->find();
                if ($villageInfo && in_array($villageInfo['status'],[2,4,5])) {
                    $this->returnCodeError('您的小区已被禁用，无法使用');
                }
                if ($villageInfo) {
                    $this->returnCodeOk(['villageId' => $villageInfo['village_id']]);
                }
            }
        }
        $nowTime = time();
        $data = [
            'property_id' => $_POST['property_id'],
            'village_name' => $village_name,
            'village_address' => $_POST['village_address'] ?? '',
            'property_name' => $_POST['property_name'] ?? '',
            'property_address' => $_POST['property_address'] ?? '',
            'status' => '1',
            'add_time' => time(),
            'last_time' => time(),
        ];
        $villageId = $dbHouseVillage->data($data)->add();
        // ↓ 添加对应三方信息
        $businessId = $villageId;
        $thirdName = isset($_POST['thirdName'])&&trim($_POST['thirdName'])?trim($_POST['thirdName']):'';
        $paramData = [
            'businessType' => $businessType,
            'businessId' => $businessId,
            'thirdType' => $thirdType,
            'thirdSite' => $thirdSite,
            'thirdId' => $thirdId,
            'thirdName' => $thirdName,
            'nowTime' => $nowTime,
        ];
        if (empty($this->thirdConfig)) {
            import('@.ORG.ThirdAccess.thirdConfig');
            $this->thirdConfig = new thirdConfig();
        }
        try {
            $this->thirdConfig->setHouseThirdConfig($paramData);
        } catch (\Exception $e) {
            $this->returnCodeError($e->getMessage());
        }
        // ↑ 添加对应三方信息

        if(!$villageId){
            $this->returnCodeError('小区创建失败，请重试');
        }
        $this->returnCodeOk(['villageId' => $villageId]);
    }

    /**
     * 编辑小区.
     */
    public function editVillage()
    {
        if(!$_POST['village_id']){
            $this->returnCodeError('ID参数必带，请重试');
        }
        $nowTime = time();
        $data = [
            'last_time' => $nowTime,
        ];
        isset($_POST['village_name']) && $data['village_name'] = $_POST['village_name'];
        isset($_POST['village_address']) && $data['village_address'] = $_POST['village_address'];
        isset($_POST['property_name']) && $data['property_name'] = $_POST['property_name'];
        isset($_POST['property_address']) && $data['property_address'] = $_POST['property_address'];

        if(D('House_village')->where(['village_id' => $_POST['village_id']])->data($data)->save() === false){
            $this->returnCodeError('信息编辑失败，请重试');
        }
        $businessId = $_POST['village_id'];
        $thirdType = trim($_POST['thirdType']);
        $thirdSite = trim($_POST['thirdSite']);
        $businessType = 'village';
        $thirdName = isset($_POST['thirdName'])&&trim($_POST['thirdName'])?trim($_POST['thirdName']):'';
        $paramData = [
            'businessType' => $businessType,
            'businessId' => $businessId,
            'thirdType' => $thirdType,
            'nowTime' => $nowTime,
        ];
        if (isset($_POST['thirdVillageId'])) {
            $thirdId = trim($_POST['thirdVillageId'])?trim($_POST['thirdVillageId']):0;
            $paramData['thirdId'] = $thirdId;
        }
        if (isset($_POST['thirdName'])) {
            $paramData['thirdName'] = $thirdName;
        }
        if (isset($_POST['thirdSite'])) {
            $paramData['thirdSite'] = $thirdSite;
        }
        if (empty($this->thirdConfig)) {
            import('@.ORG.ThirdAccess.thirdConfig');
            $this->thirdConfig = new thirdConfig();
        }
        try {
            $this->thirdConfig->setHouseThirdConfig($paramData);
        } catch (\Exception $e) {
            $this->returnCodeError($e->getMessage());
        }
        $this->returnCodeOk('信息编辑成功');
    }

    /*
     * 99年免费物业全套餐
     */
    private function PermanentlyFreePrivilegePackage($property_id) {
        $config_data = D('Config_data')->get_config_data();
        // 直接取永久套餐id
        $permanentlyPackageId = ($config_data && $config_data['permanentlyPackageId'])?$config_data['permanentlyPackageId']:0;
        $db_privilege_package = D('Privilege_package');
        $contentIds = D('Privilege_package_content')->getField('content_id',true);
        // 房屋数限制 999999 不允许随意变更
        $room_num = 999999;
        // 期限 99 不允许随意变更
        $package_limit_num = 99;
        // 套餐名称
        $package_title = '99年全功能套餐';
        // 判断是否需要更新
        $updateContents = true;
        $now_time = time();
        if (!$permanentlyPackageId) {
            $whereRepeat = [
                'package_try_days' => -$package_limit_num,
                'package_limit_num' => $package_limit_num,
                'status' => 4,
            ];
            $permanentlyPackageId = $db_privilege_package->where($whereRepeat)->getField('package_id');
            if (!$permanentlyPackageId) {
                $arr = [
                    'package_title'=>$package_title,
                    'package_try_days'=>-$package_limit_num,
                    'package_price'=>0,
                    'package_limit_num'=>$package_limit_num,
                    'txt_des'=>'此套餐包含所有功能',
                    'room_num'=>$room_num,
                    'status'=>4,
                    'add_time'=>time(),
                    'details'=>'此套餐包含所有功能（默认删除，不可从数据库移除）',
                ];
                $permanentlyPackageId = D('Privilege_package')->data($arr)->add();
                $updateContents = false;
                foreach ($contentIds as $val){
                    $bind_arr = [
                        'package_id'=>$permanentlyPackageId,
                        'content_id'=>$val,
                        'add_time'=>time(),
                    ];
                    D('privilege_package_bind')->data($bind_arr)->add();
                }
                // 记录当前变更
                D('Config_data')->save_config_data('updateContentsTime',$now_time);
            }
        }
        if ($updateContents) {
            $updateContentsTime = ($config_data && $config_data['updateContentsTime'])?$config_data['updateContentsTime']:0;
            // 叠加3天 3600*24*3
            $updateContentsTime = intval($updateContentsTime) + 259200;// 延后3天 3天一更新
            if ($now_time>=$updateContentsTime) {
                $whereBind = [
                    'package_id' => $permanentlyPackageId,
                ];
                // 先删除现有的
                D('privilege_package_bind')->where($whereBind)->delete();
                foreach ($contentIds as $val){
                    $bind_arr = [
                        'package_id'=>$permanentlyPackageId,
                        'content_id'=>$val,
                        'add_time'=>time(),
                    ];
                    D('privilege_package_bind')->data($bind_arr)->add();
                }
                // 记录当前变更
                D('Config_data')->save_config_data('updateContentsTime',$now_time);
                // todo 可以考虑后期同步变更已经生成订单了的
            }
        }
        $where = [];
        $where['id'] = $property_id;
        $property_info = D('House_property')->where($where)->field('id,property_name,property_phone')->find();
        $time = strtotime(date('Y-m-d',time()));
        $order_content = implode(',',$contentIds);
        // (99 * 366) * 86400 + $time + 86400  // 暂时最大只能到2147483647 todo以后对应变更
//        $package_end_time = 3130617600 + $time + 86400;
        $package_end_time = 2147483647;
        $order = [
            'order_no' => date('YmdHis') . rand(100, 999),
            'package_id' => $permanentlyPackageId,
            'property_id' => $property_id,
            'property_name' => isset($property_info['property_name'])&&trim($property_info['property_name'])?trim($property_info['property_name']):'物业_'.$property_id,
            'property_tel' => isset($property_info['property_phone'])&&trim($property_info['property_phone'])?trim($property_info['property_phone']):'',
            'pay_money' => 0,
            'order_money' => 0,
            'pay_type' => 3,
            'create_time' => time(),
            'pay_time' => time(),
            'package_period' => $package_limit_num,
            'package_end_time' => $package_end_time,
            'num' => 1,
            'status' => 1,
            'package_title' => $package_title,
            'room_num' => $room_num,
            'package_price' => 0,
            'package_limit_num' => $package_limit_num,
            'content_id' => $order_content,
        ];
        $order_id = D('package_order')->add($order);
        if (!$order_id) {
            fdump_api(['绑定99年全功能套餐'.__LINE__,$order],'PermanentlyFreePrivilegePackageLog');
        }
        return $order_id;
    }

    public function linghuiAdd() {
        $nowTime = time();
        $thirdAppid = $this->config['third_building_appid'];
        $thirdAppkey = $this->config['third_building_appkey'];
        $paramData = [
            'businessType' => 'linghui',
            'businessId' => '-99',
            'thirdType' => 'linghui',
            'thirdSite' => 'app.kuaijingai.com',
            'thirdId' => 0,
            'thirdName' => '智慧楼宇管理系统',
            'thirdAppid' => $thirdAppid,
            'thirdAppkey' => $thirdAppkey,
            'nowTime' => $nowTime,
        ];
        if (empty($this->thirdConfig)) {
            import('@.ORG.ThirdAccess.thirdConfig');
            $this->thirdConfig = new thirdConfig();
        }
        $data = $this->thirdConfig->setHouseThirdConfig($paramData);
        $dataMsg = [];
        if (isset($data['thirdName'])&&$data['thirdName']) {
            $dataMsg['name'] = $data['thirdName'];
        }
        if (isset($data['thirdType'])&&$data['thirdType']) {
            $dataMsg['type'] = $data['thirdType'];
        }
        if (isset($data['checkId'])&&$data['checkId']) {
            $dataMsg['appsecret'] = $data['checkId'];
        }
        if (isset($data['thirdAppid'])&&$data['thirdAppid']) {
            $dataMsg['appid'] = $data['thirdAppid'];
        }
        if (isset($data['thirdAppkey'])&&$data['thirdAppkey']) {
            $dataMsg['appkey'] = $data['thirdAppkey'];
        }
        dump($dataMsg);
    }

    public function test() {
        $info = $this->PermanentlyFreePrivilegePackage(160);
        dump($info);
    }
}
?>