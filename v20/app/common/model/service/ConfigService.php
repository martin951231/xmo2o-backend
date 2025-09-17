<?php
/**
 * 系统后台用户登录权限服务
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/7 09:01
 */

namespace app\common\model\service;

use app\common\model\db\Admin;
use app\common\model\db\Config as ConfigModel;
use think\facade\Cache;
use app\pay\model\service\channel\TianqueService;

class ConfigService {
    public $configObj = null;
    public function __construct()
    {
        $this->configObj = new ConfigModel();
    }

    /**
     * 返回正常显示的菜单
     * @return array
     */
    public function getConfigData($now_lang = '') {
		if(!$now_lang){
			//尝试从POST读参数
			$now_lang = request()->param('now_lang', '', 'trim');
			if(!$now_lang){
				//尝试从cookie里读参数
				$now_lang = cookie('system_lang');
			}
		}
		
        // 获得所有菜单
        $returnArr = Cache::get('o2oconfig_'.$now_lang);//这里改名的原因是因为系统其他地方用到了这个config名字，所以跟这里冲突了，但又没查出哪里用到
        if(empty($returnArr)){
            $field = 'name,value';
            $where = [['name','in','open_multilingual,default_language']];
            $open_multilingual = array_column($this->getConfigList($where,$field),'value', 'name');
            $_field = '`name`,`value`';
            if(isset($open_multilingual['open_multilingual']) && $open_multilingual['open_multilingual'] == '1'){
                $_field .= ',`is_lang`';
                if(empty($now_lang)){
                    $now_lang = $open_multilingual['default_language'];
                }
                $_field .= ($now_lang && $now_lang != 'chinese') ? ',`value_'.$now_lang.'`' : '';
            }

            $configList = $this->getConfigList([],$_field);
            if(!$configList) {
                return [];
            }

            foreach($configList as $key=>$value){
                if(isset($open_multilingual['open_multilingual']) && $open_multilingual['open_multilingual'] == '1' && $value['is_lang'] == '1' && $now_lang && $now_lang != 'chinese'){//系统开启了多语言
                    $returnArr[$value['name']] = $value['value_'.$now_lang];
                }
                else{
                    $returnArr[$value['name']] = $value['value'];
                }
            }
			


			//图片、APK链接强制更改为 资源域名 OSS
			if(isset($returnArr['static_oss_switch']) && isset($returnArr['static_oss_access_domain_names']) && $returnArr['static_oss_switch'] && $returnArr['static_oss_access_domain_names']){
				$replace_extension = array('jpg','png','gif','apk','jpeg','ico');
				foreach($returnArr as $key=>$value){
					//图片、APK链接强制更改为 资源域名
                        $pathinfoArr = pathinfo($value);
                    if (isset($pathinfoArr['extension']) && !empty($pathinfoArr['extension'])){
                        if(in_array(strtolower($pathinfoArr['extension']),$replace_extension)){
                            if(stripos($value, '/upload/') === 0){
                                $returnArr[$key] = $_SERVER['REQUEST_SCHEME'].'://'.$returnArr['static_oss_access_domain_names'] . $value;
                            }else if(stripos($value, 'upload/') === 0){
                                $returnArr[$key] = $_SERVER['REQUEST_SCHEME'].'://'.$returnArr['static_oss_access_domain_names']  . '/' . $value;
                            }else if(stripos($value,'/upload/') !== false){
                                $pathinfoArr = pathinfo($value);
                                if(isset($pathinfoArr['extension']) && in_array(strtolower($pathinfoArr['extension']), $replace_extension)){
                                    $returnArr[$key] = str_replace($returnArr['site_url'], $_SERVER['REQUEST_SCHEME'].'://'.$returnArr['static_oss_access_domain_names'], $value);
                                }
                            }
                        }
                    }
				}
			}
            //图片、APK链接强制更改为 资源域名 COS
            if(isset($returnArr['static_cos_switch']) && isset($returnArr['static_cos_region']) && $returnArr['static_cos_switch'] && $returnArr['static_cos_region']){
                $replace_extension = array('jpg','png','gif','apk','jpeg','ico');
                foreach($returnArr as $key=>$value){
                    //图片、APK链接强制更改为 资源域名

                    $pathinfoArr = pathinfo($value);
                    if (isset($pathinfoArr['extension']) && !empty($pathinfoArr['extension'])){
                        if(in_array(strtolower($pathinfoArr['extension']),$replace_extension)){
                            if(stripos($value, '/upload/') === 0){
                                $returnArr[$key] = $_SERVER['REQUEST_SCHEME'].'://'.$returnArr['static_cos_access_domain_names'] . $value;
                            }else if(stripos($value, 'upload/') === 0){
                                $returnArr[$key] = $_SERVER['REQUEST_SCHEME'].'://'.$returnArr['static_cos_access_domain_names']  . '/' . $value;
                            }else if(stripos($value,'/upload/') !== false){
                                $pathinfoArr = pathinfo($value);
                                if(isset($pathinfoArr['extension']) && in_array(strtolower($pathinfoArr['extension']), $replace_extension)){
                                    $returnArr[$key] = str_replace($returnArr['site_url'], $_SERVER['REQUEST_SCHEME'].'://'.$returnArr['static_cos_access_domain_names'], $value);
                                }
                            }
                        }
                    }
                }
            }
			//图片、APK链接强制更改为 资源域名 OBS
			if(isset($returnArr['static_obs_switch']) && isset($returnArr['static_obs_access_domain_names']) && $returnArr['static_obs_switch'] && $returnArr['static_obs_access_domain_names']){
				$replace_extension = array('jpg','png','gif','apk','jpeg','ico');
				foreach($returnArr as $key=>$value){
					//图片、APK链接强制更改为 资源域名
                    $pathinfoArr = pathinfo($value);
                    if (isset($pathinfoArr['extension']) && !empty($pathinfoArr['extension'])){
                        if(in_array(strtolower($pathinfoArr['extension']),$replace_extension)){
                            if(stripos($value, '/upload/') === 0){
                                $returnArr[$key] = $_SERVER['REQUEST_SCHEME'].'://'.$returnArr['static_obs_access_domain_names'] . $value;
                            }else if(stripos($value, 'upload/') === 0){
                                $returnArr[$key] = $_SERVER['REQUEST_SCHEME'].'://'.$returnArr['static_obs_access_domain_names']  . '/' . $value;
                            }else if(stripos($value,'/upload/') !== false){
                                $pathinfoArr = pathinfo($value);
                                if(isset($pathinfoArr['extension']) && in_array(strtolower($pathinfoArr['extension']), $replace_extension)){
                                    $returnArr[$key] = str_replace($returnArr['site_url'], $_SERVER['REQUEST_SCHEME'].'://'.$returnArr['static_obs_access_domain_names'], $value);
                                }
                            }
                        }
                    }
				}
			}
			
			//初始化部分参数
			$returnArr['system_lang'] = $now_lang;	//当前语言
			$returnArr['system_lang_alias'] = (isset($returnArr['system_lang_alias']) && !empty($returnArr['system_lang_alias'])) ? : 'zh_CN';	//当前语言别名
			$returnArr['Currency_txt'] = (isset($returnArr['Currency_txt']) && !empty($returnArr['Currency_txt'])) ? $returnArr['Currency_txt']: '元';				//当前货币单位
			$returnArr['Currency_symbol'] = (isset($returnArr['Currency_symbol']) && !empty($returnArr['Currency_symbol'])) ? $returnArr['Currency_symbol']: '￥';			//当前货币符号

			//判断网站是否开启https
            if(stripos($returnArr['site_url'], 'https://') === 0){
                $returnArr['use_https'] = true;
            }else{
                $returnArr['use_https'] = false;
            }

            Cache::set('now_lang',$now_lang);
            Cache::set('o2oconfig_'.$now_lang, $returnArr);
        }
		
		//数据库里实际保存的链接
		$returnArr['config_site_url'] = $returnArr['site_url'];	
		
		// 设置当前使用域名，为了兼容后台填写了http、但实质是https访问的情况、导致返回的资源链接都是http的浏览器无法访问。
        
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') || $_SERVER['REQUEST_SCHEME']=='https') ? 'https://' : 'http://';
        if (!empty($_SERVER['HTTP_HOST'])) {
            $returnArr['site_url'] = $returnArr['now_site_url'] = $http_type . $_SERVER['HTTP_HOST'];
        }

        return $returnArr;
    }

    /**
     * 返回正常显示的菜单
     * @param $id
     * @return array
     */
    public function getConfigList($where = [],$field=true) {
        // $where['status'] = 1;
        $list = $this->configObj->getConfigList($where,$field);
        if(!$list) {
            return [];
        }
        return $list->toArray();
    }

    /**
     * 更新阿里云oss配置信息
     * @author: chenxiang
     * @date: 2020/5/19 11:19
     * @param $where
     * @param $data
     * @return string
     */
    public function saveConfig($where,$data) {
        if(empty($where)) {
            return '';
        }
        $result = $this->configObj->saveConfig($where,$data);
        return $result;
    }

    /**
     * 获取配置项组信息
     * @param $field
     * @param array $where
     * @param string $sort
     * @param array $config
     * @return array
     */
    public function getConfigGroupList($field, $where = [], $sort = '', $config = []) {
        $configGroupService = new ConfigGroupService();
        $group_list = $configGroupService->getConfigGroupList($field, $where, $sort, $config);
        return $group_list;
    }


    /**
     * 获取正常显示的配置项
     * @author: chenxiang
     * @date: 2020/5/19 11:49
     * @param array $where
     * @param $order
     * @return array
     */
    public function getTmpConfigList($where = [],$order = '',$config = []) {
        $tmp_config_list = $this->configObj->getTmpConfigList($where,$order);

        if(!$tmp_config_list) {
            return [];
        }
        // 单独处理主题色问题
        $head_th = [];

        foreach($tmp_config_list->toArray() as $key=>$value){
            $value['info'] = str_replace('订餐',cfg('meal_alias_name'),$value['info']);
            if(strpos($value['info'], '社区团购')!==false){
                $value['info'] = str_replace('社区团购', cfg('village_group_alias') ?? '社区团购', $value['info']);
            }elseif (strpos($value['info'], '团购')!==false){
                $value['info'] = str_replace('团购',cfg('group_alias_name'),$value['info']);
            }
            $value['info'] = str_replace('预约',cfg('appoint_alias_name'),$value['info']);
            $value['info'] = str_replace('礼品',cfg('gift_alias_name'),$value['info']);
            $value['info'] = str_replace('快店',cfg('shop_alias_name'),$value['info']);

            $value['tab_name'] = str_replace('订餐',cfg('meal_alias_name'),$value['tab_name']);
            if(strpos($value['tab_name'], '社区团购')!==false){
                $value['tab_name'] = str_replace('社区团购', cfg('village_group_alias') ?? '社区团购', $value['tab_name']);
            }elseif (strpos($value['tab_name'], '团购')!==false){
                $value['tab_name'] = str_replace('团购',cfg('group_alias_name'),$value['tab_name']);
            }
            $value['tab_name'] = str_replace('预约',cfg('appoint_alias_name'),$value['tab_name']);
            $value['tab_name'] = str_replace('礼品',cfg('gift_alias_name'),$value['tab_name']);
            $value['tab_name'] = str_replace('快店',cfg('shop_alias_name'),$value['tab_name']);

            if($value['name'] == 'search_first_type'){
                $value['type'] = str_replace('订餐',cfg('meal_alias_name'),$value['type']);
                if(strpos($value['type'], '社区团购')!==false){
                    $value['type'] = str_replace('社区团购', cfg('village_group_alias') ?? '社区团购', $value['type']);
                }elseif (strpos($value['type'], '团购')!==false){
                    $value['type'] = str_replace('团购',cfg('group_alias_name'),$value['type']);
                }
                $value['type'] = str_replace('预约',cfg('appoint_alias_name'),$value['type']);
                $value['type'] = str_replace('礼品',cfg('gift_alias_name'),$value['type']);
                $value['type'] = str_replace('快店',cfg('shop_alias_name'),$value['type']);
            }elseif ($value['name'] == 'village_group_threshold_warning_member'){
                $tmparr = (new Admin())->field(true)->where(['status'=>1])->order('id ASC')->select();
                $value['type'] = 'type=selectAll&value=';
                $value['value'] = $value['value']?explode(',',$value['value']):[];
                foreach ($tmparr as $vc) {
                    $value['type'] .= $vc['id'].':'.$vc['account'].'|';
                }
            }

            if(in_array($value['name'], array('shop_sort', 'store_shop_auth', 'shop_goods_score_edit', 'shop_goods_spread_edit'))){
                $value['desc'] = str_replace('快店',cfg('shop_alias_name'),$value['desc']);
            }

            /*if($value['name'] == 'tianqueteach_mno'){
                if($value['value'] == ''){
                    $value['desc'] = '您的授权地址是：'.cfg('site_url').'/v20/public/index.php/pay/index/getTianqueUrl?mno=xxx。将xxx换成商户编号';
                }
                else{
                    $TianqueService = new TianqueService;
                    $res = $TianqueService->getUrl($value['value']);
                    if(isset($res['retUrl']) && $res['retUrl'] != ''){
                        $value['desc'] = '您的授权地址是：'.cfg('site_url').'/v20/public/index.php/pay/index/getTianqueUrl?mno='.trim($value['value']);
                    }
                    elseif ($res['bizMsg'] == '该商户已签约, 请勿重复签约') {
                        $value['desc'] = '您的授权地址是：'.cfg('site_url').'/v20/public/index.php/pay/index/getTianqueUrl?mno='.trim($value['value']);
                    }
                    else{
                        $value['desc'] = '您的商户编号配置有误';
                    }
                }
            }*/

            if(stripos($value['type'],'type=image') !== false){
                $value['value'] = replace_file_domain($value['value']);
            }
//            $config_list[$value['tab_id']]['name'] = $value['tab_name'];
//            $config_list[$value['tab_id']]['list'][] = $value;

            $config_list[$value['tab_id']]['name'] = $value['tab_name']?$value['tab_name']:'基本配置';
            $config_list[$value['tab_id']]['tab_id'] = $value['tab_id'];
            if(in_array($value['name'],['mobile_head_color','mobile_head_affect','mobile_head_floor_color','mobile_head_bg_img'])){
                $head_th[$value['name']] = $value;
                if($value['name'] == 'mobile_head_color'){
                    $colorHeadInfo = $value['value'];
                    $config_list[$value['tab_id']]['colorHeadInfo'] = $colorHeadInfo;
                }
                if($value['name'] == 'mobile_head_floor_color'){
                    $colorFloorInfo = $value['value'];
                    $config_list[$value['tab_id']]['colorFloorInfo'] = $colorFloorInfo;
                }
                continue;
            }
            if($value['name']=='village_group_deliver_custom_time'){//社区团购配送时间段自定义设置
                $value['value'] = $value['value'] ? json_decode($value['value'],true) : [
                    [
                        'start_time'=>'00:00',
                        'end_time'=>'00:00',
                        'delivery_weight'=>0,
                        'delivery_weight_desc'=>'平台初始重量 (kg)',
                        'per_weight_fee'=>0,
                        'per_weight_fee_desc'=>'超出初始重量时收取的费用 (单位:元)',
                        'delivery_distance'=>0,
                        'delivery_distance_desc'=>'平台初始距离（km）',
                        'delivery_fee'=>0,
                        'delivery_fee_desc'=>'平台配送时收取每单在起步范围内的配送费',
                        'per_km_fee'=>0,
                        'per_km_fee_desc'=>'超出起步距离的路程每公里的单价',
                    ],
                ];
            }
            $config_list[$value['tab_id']]['list'][] = $value;

            if ($value['name']=='employee_register_agreement') {
                $src = '<img src="'.file_domain().'/';
                $value['value'] = str_replace('<img src="/', $src, $value['value']);
                $value['value'] = str_replace('<img alt="" src="/', $src, $value['value']);
                $value['value'] = str_replace('img src=&quot;/', 'img src=&quot;'.file_domain().'/', $value['value']);
            }
        }

        if(!empty($head_th)){
            // 排序
            sort($head_th);
            // 当前主题色效果
            $mobileEffect = 0;
            $tab_id = 0;
            foreach ($head_th as $va){
                if($va['name'] == 'mobile_head_affect'){
                    if($va['value']==1){
                        $mobileEffect=0;
                    }else if($va['value']==0){
                        $mobileEffect=1;
                    }elseif($va['value']){
                        $mobileEffect=intval($va['value']);
                    }
                    $config_list[$va['tab_id']]['mobile_head_affect'] = $mobileEffect;
                }
                $tab_id = $va['tab_id'];
            }
            array_splice($config_list[$tab_id]['list'],10,0,$head_th);
            $config_list = array_values($config_list);
        }
        
        if(empty($config_list)) {
            $config_list = [];
        } else {
            $config_list = $this->build_list($config_list);
        }

        return $config_list;
    }

    /**
     * 拼装数组list
     * @param $config_list
     * @return array
     */
    public function build_list($config_list) {
        if(is_array($config_list)) {
            if (count($config_list) > 1){
                $has_tab = true;
            } else {
                $has_tab = false;
            }
            foreach ($config_list as $pigcms_key => &$pigcms_value) {
                if ($has_tab) {
                    $pigcms_value['name'] ? $pigcms_value['name'] : '基本设置';
                }

                foreach ($pigcms_value['list'] as $key => &$value) {
                    $tmp_type_arr = explode('&', $value['type']);

//                    $type_arr = array();
                    foreach ($tmp_type_arr as $k => $v) {
                        $tmp_value = explode('=', $v);
                        if($tmp_value[0] == 'value') {
                            $tmp_value[0] = 'typeValue';
                            if(stripos($tmp_value[1], '|')) {
                                $value_arr = explode('|', $tmp_value[1]);
                                $sel_arr_value = [];
                                foreach ($value_arr as $k_arr => $v_arr) {
                                    if(strpos($v_arr, ':')) {
                                        $sel_arr = explode(':',$v_arr);
                                        $sel_arr_value[] = ['label'=>$sel_arr[1], 'value'=>$sel_arr[0]];
                                    }
                                }
                            }
                            $pigcms_value['list'][$key][$tmp_value[0]] = $sel_arr_value;
                        } else {
                            if(!empty($tmp_value[1])) {
                                $pigcms_value['list'][$key][$tmp_value[0]] = $tmp_value[1];
                            }
                        }

                        if($tmp_value[0] == 'validate') {

                            if(strpos($tmp_value[1], ',')) {
                                $tmp_validate_arr = explode(',', $tmp_value[1]);
                                foreach($tmp_validate_arr as $kk => $vv) {
                                    $tmp_validate_arr_value = explode(':', $vv);

                                    if(!empty($tmp_validate_arr_value[1])) {
                                        $pigcms_value['list'][$key][$tmp_validate_arr_value[0]] = $tmp_validate_arr_value[1];
                                    }
                                }
                            } else {
                                    $tmp_validate_arr_value = explode(':', $tmp_value[1]);
                                    if(!empty($tmp_validate_arr_value)) {
                                        $pigcms_value['list'][$key][$tmp_validate_arr_value[0]] = $tmp_validate_arr_value[1]??'';
                                    }
                                }
                            }
                        }
                    }
                }
            }

        $config_list = array_values($config_list);

        return $config_list;

    }

    /**
     * 获取当前地区的时区标识
     * @param string $field
     * @param array $where
     * @return mixed|string
     */
    public function getNowCityTimezone($field = '', $where = []) {
        $areaService = new AreaService();
        $now_city_timezone = $areaService->getNowCityTimezone($field, $where);

        return $now_city_timezone;
    }

    /**
     * 获取name值为条件的个数
     * @param array $where
     * @return int
     */
    public function getDataNumByName($where = []) {
        if(empty($where)) {
            return 0;
        }
        $num = $this->configObj->getDataNumByName($where);
        return $num;
    }

    /**
     * 查询一条数据
     * @param array $where
     * @return mixed
     */
    public function getDataOne($where = []) {
        $data = $this->configObj->getDataOne($where);
        return $data ? $data->toArray() : [];
    }

    /**
     * 查询一条数据 并返回值
     * @param array $where
     * @return mixed
     */
    public function getOneField($field) {
        $return = '';
        $where = [
            'name' => $field
        ];
        $data = $this->getDataOne($where);
        if($data){
            $return = $data['value'];
        }
        return $return;
    }

    /**
     * 查询config_group中的一条数据
     * @param array $where
     * @return array
     */
    public function getDataOneFromConfigGroup($where = []) {
        $configGroupService = new ConfigGroupService();
        $data = $configGroupService->getDataOne($where);
        return $data ? $data->toArray() : [];
    }

    /**
     * 更新数据
     * @param array $data
     * @return bool
     */
    public function saveConfigData($data = []) {
        $result = $this->configObj->saveConfigData($data);
        return $result;
    }

    /**
     * 添加一条数据
     * @param array $data
     * @return mixed
     */
    public function addConfigData($data = []) {
        $result = $this->configObj->addConfigData($data);
        return $result;
    }

    /**
     * 获取pigcms_config_data表一条数据
     * @param array $where
     * @return array|\think\Model|null
     */
    public function getDataFromConfigData($where = []) {
        $configDataService = new ConfigDataService();
        $data = $configDataService->getDataOne($where);
        return $data ? $data->toArray() : [];
    }

    /**
     * 更新pigcms_config_data表数据
     * @param array $where
     * @param array $data
     * @return bool
     */
    public function saveDataFromConfigData($where = [], $data = []) {
        $configDataService = new ConfigDataService();
        $result = $configDataService->saveData($where, $data);
        return $result;
    }

    /**
     * 添加pigcms_config_data表数据
     * @param array $data
     * @return int|string
     */
    public function addDataFromConfigData($data = []) {
        $configDataService = new ConfigDataService();
        $result = $configDataService->addData($data);
        return $result;
    }

    /**
     * 老版支付获得支付方式
     * @param array $data
     * @return int|string
     */
    public function getPayName($payType,$isMobilePay=1, $paid = 1){
        switch($payType){
            case 'alipay':
                $payTypeTxt = L_('支付宝WAP支付');
                break;
            case 'alipayh5':
                $payTypeTxt = L_('支付宝WAP支付');
                break;
            case 'tenpay':
                $payTypeTxt = L_('财付通');
                break;
            case 'yeepay':
                $payTypeTxt = L_('易宝支付');
                break;
            case 'allinpay':
                $payTypeTxt = L_('通联支付');
                break;
            case 'chinabank':
                $payTypeTxt = L_('网银在线');
                break;
            case 'weixin':
                $payTypeTxt = L_('微信支付');
                break;
            case 'weixinh5':
                $payTypeTxt = L_('微信WAP支付');
                break;
            case 'baidu':
                $payTypeTxt = L_('百度钱包');
                break;
            case 'unionpay':
                $payTypeTxt = L_('银联支付');
                break;
            case 'weifutong':
                $payTypeTxt = C('config.pay_weifutong_alias_name');
                break;
            case 'offline':
                $payTypeTxt = L_('线下付款');
                break;
            case 'ccb':
                $payTypeTxt = L_('建设银行');
                break;
            case 'yzfpay':
                $payTypeTxt = L_('翼支付');
                break;
            case 'yzfpay_offline':
                $payTypeTxt = L_('翼支付线下');
                break;
            case 'wftpay':
                $payTypeTxt = L_('网付通');
                break;
            case 'allinpay_mer':
                $payTypeTxt = L_('通联支付');
                break;
            case 'merchantwarrior':
                $payTypeTxt = L_('信用卡支付');
                break;
            case 'nmgpay':
                $payTypeTxt = L_('在线支付');
                break;
            default:
                if ($paid) {
                    $payTypeTxt = L_('余额支付');
                } else {
                    $payTypeTxt = L_('未支付');
                    return L_('未支付');
                }

        }
        if($isMobilePay == 1 && $payType != 'alipayh5'){
            $payTypeTxt .= '('.L_('微信端').')';
        } elseif ($isMobilePay == 2) {
            $payTypeTxt .= '(App)';
        }elseif ($isMobilePay == 3) {
            $payTypeTxt .= '('.L_('小程序').')';
        }
        return $payTypeTxt;
    }

    /**
     * 获取支付配置
     * @param array $data
     * @return int|string
     */
    public function get_pay_method($notOnline=0,$notOffline=0,$is_wap=false,$is_app=false,$is_all=false,$is_refund=false){
        $tmp_config_list = $this->get_gid_config(7);
        $tmp_config_list_app = $this->get_gid_config(23);
        $tmp_config_list_tl = $this->get_gid_config(60);

        foreach($tmp_config_list as $key=>$value){
            if(in_array($value['tab_id'],array('paylinx','wepayez','fubei'))){
                continue;
            }
            $config_list[$value['tab_id']]['name'] = L_($value['tab_name']);
            $config_list[$value['tab_id']]['config'][$value['name']] = $value['value'];
            if(strpos($value['name'],'service_charge_open') !== false){
                $config_list[$value['tab_id']]['config']['service_charge_open'] = $value['value'];
//                unset($value);
            }
            if(strpos($value['name'],'discount_tips') !== false){
                $config_list[$value['tab_id']]['config']['discount_tips'] = $value['value'] ? $value['value'] : '';
//                unset($value);
            }
            if(strpos($value['name'],'service_charge') !== false){
                $config_list[$value['tab_id']]['config']['service_charge'] = $value['value'] ? $value['value'] : 0;
//                unset($value);
            }
            if(strpos($value['name'],'rates') !== false){
                $config_list[$value['tab_id']]['config']['rates'] = $value['value'] ? $value['value'] : 0;
                unset($value);
            }
        }
        foreach($tmp_config_list_app as $key=>$value){
            $config_list[$value['tab_id']]['name'] = L_($value['tab_name']);
            $config_list[$value['tab_id']]['config'][$value['name']] = $value['value'];
            if(strpos($value['name'],'service_charge_open') !== false){
                $config_list[$value['tab_id']]['config']['service_charge_open'] = $value['value'];
//                unset($value);
            }
            if(strpos($value['name'],'discount_tips') !== false){
                $config_list[$value['tab_id']]['config']['discount_tips'] = $value['value'];
//                unset($value);
            }
            if(strpos($value['name'],'service_charge') !== false){
                $config_list[$value['tab_id']]['config']['service_charge'] = $value['value'];
//                unset($value);
            }
            if(strpos($value['name'],'rates') !== false){
                $config_list[$value['tab_id']]['config']['rates'] = $value['value'] ? $value['value'] : 0;
                unset($value);
            }
        }
        foreach($tmp_config_list_tl as $key=>$value){
            $config_list[$value['tab_id']]['name'] = L_($value['tab_name']);
            $config_list[$value['tab_id']]['config'][$value['name']] = $value['value'];
        }
        //剔除已关闭的支付
        foreach($config_list as $key=>$value){
            $pigcms_key = 'pay_'.$key.'_open';
            if(empty($value['config'][$pigcms_key]) || (!$is_all && $is_wap && $key == 'chinabank') || (!$is_all && !$is_refund && $is_wap && $key == 'alipay' && $value['config'][$pigcms_key] == 3) || (!$is_all && !$is_refund && empty($is_wap) && $key == 'alipay' && $value['config'][$pigcms_key] == 2)){
                unset($config_list[$key]);
            }else{
                $tmp_alias = 'pay_'.$key.'_alias_name';
                if(!empty($value['config'][$tmp_alias])){
                    $config_list[$key]['name'] = $value['config'][$tmp_alias];
                }
            }
        }
        if (!$is_all && (!$is_wap||$is_app)) unset($config_list['alipayh5']);
        if (!$is_all && isset($config_list['alipayh5']) && $config_list['alipayh5']['config']['pay_alipayh5_open'] && $config_list['alipayh5']['config']['pay_alipayh5_appid'] && $config_list['alipayh5']['config']['pay_alipayh5_merchant_private_key'] && $config_list['alipayh5']['config']['pay_alipayh5_public_key']) {
            unset($config_list['alipay']);
        }
        if($notOffline && $config_list['offline']){
            unset($config_list['offline']);
        }
        if($notOnline){
            $new_config_list = array();
            if($config_list['offline']){
                $new_config_list['offline'] = $config_list['offline'];
            }
            $config_list = $new_config_list;
        }

        return $config_list;
    }

    public function get_gid_config($gid){
        $condition_config['gid'] = $gid;
        $config = $this->configObj->getConfigList($condition_config);
        return $config;
    }

}
