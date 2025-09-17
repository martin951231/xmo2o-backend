<?php
// 应用公共文件
use app\common\model\service\export\ExportService as BaseExportService;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;
use \app\http\exceptions\CustomException;
use think\File;

/**
 * 前端接口输出公共方法（json格式）
 * @param  integer $status    状态码
 *                            1000 		= 正常
 *                            1001		= 必填参数缺失错误
 *                            1002		= 用户权限错误（需跳转登录）
 *                            1003		= 业务逻辑错误
 *                            1004		= 请求地址错误
 *                            1005      = 服务器端代码异常
 *                            1006      = 需要授权登录拿到授权信息，没有账号的时候不需要跳转注册手机号
 *                            1007      = 再次请求该接口（卢敏增加，一般用于当前接口信息已经变更了返回信息，需要前端重新再次访问）
 *                            1008      = 此返回在弹层报错之后，返回上一页。
 *                            1009      = 此错误码无视，不用做任何弹层提示。

 *                            1301      = 新开窗口跳转地址（data存放跳转地址）
 *                            1302      = 当前窗口跳转地址（data存放跳转地址）
 *                            1501      = 弹窗提示 然后点击 确认后进行 路径跳转  data 值 {title:'弹框标题','tip':'弹窗提示内容',url:'具体跳转路径，不跳转给空字符串','logout':'1:清除登录信息跳转，0：不清除登录信息'}
 							  ...
 * @param  array   $data      需要返回给调用端的数据
 * @param  string  $msg       错误消息描述
 * @param  integer $http_code http状态码
 * @param  string $refresh_ticket 更新给前端的新的 ticket
 * @return \think\response\Json
 */
function api_output($status = 0, $data = [], $msg = '', $http_code = 200, $refresh_ticket=''){
	if(!$data){
		$data = [];
	}

    //增加敏感词过滤
    if (cfg('open_filter_word') == 1 && request()->param('system_type') != 'platform') {
        $encodeData = json_encode($data, JSON_UNESCAPED_UNICODE);
        $encodeData = (new \app\common\model\db\FilterWord())->filter($encodeData);
        $decodeData = json_decode($encodeData, true);
        is_array($decodeData) && $data = $decodeData;
    }

	$output = [
		'status' => $status  == '0' ? 1000 : $status,
		'msg' 	 => $msg,
		'data' 	 => $data
	];
    $msgAry = explode('_returnUrl_',$msg);
    if($status = 1003 && count($msgAry) == 2){
        $output['msg'] = $msgAry[0];
        $output['url'] = $msgAry[1];
    }
	if(cfg('staff_ticket')){
	    // 店员端每次要返回新的ticket,防止过期
        $output['staff_ticket'] = cfg('staff_ticket');
    }
	if ($refresh_ticket) {
        // 传递了刷新
        $output['refresh_ticket'] = $refresh_ticket;
    } else {
        // 空值返回
        $output['refresh_ticket'] = strval(request()->refresh_ticket);
    }
	return json($output)->code($http_code);
}

/**
 * 前端接口错误输出公共方法
 * @param  integer $status    状态码
 *                            1001		= 必填参数缺失错误
 *                            1002		= 用户权限错误（需跳转登录）
 *                            1003		= 业务逻辑错误
 *                            1004		= 请求地址错误
 							  ...
 * @param  string  $msg       错误消息描述
 * @param  integer $http_code http状态码
 * @return [type]             json
 */
function api_output_error($status = 1001, $msg = '', $http_code = 200){
	return api_output($status, [], $msg, $http_code);
}

/**
 * 人民币小写转大写
 *
 * @param string $number 数值
 * @param string $int_unit 币种单位，默认"元"，有的需求可能为"圆"
 * @param bool $is_round 是否对小数进行四舍五入
 * @param bool $is_extra_zero 是否对整数部分以0结尾，小数存在的数字附加0,比如1960.30，
 *             有的系统要求输出"壹仟玖佰陆拾元零叁角"，实际上"壹仟玖佰陆拾元叁角"也是对的
 * @return string
 */
function cny($number = 0, $int_unit = '圆', $is_round = TRUE, $is_extra_zero = FALSE)
{
    // 将数字切分成两段
    $parts = explode('.', $number, 2);
    $int = isset($parts[0]) ? strval($parts[0]) : '0';
    $dec = isset($parts[1]) ? strval($parts[1]) : '';

    // 如果小数点后多于2位，不四舍五入就直接截，否则就处理
    $dec_len = strlen($dec);
    if (isset($parts[1]) && $dec_len > 2)
    {
        $dec = $is_round
            ? substr(strrchr(strval(round(floatval("0.".$dec), 2)), '.'), 1)
            : substr($parts[1], 0, 2);
    }

    // 当number为0.001时，小数点后的金额为0元
    if(empty($int) && empty($dec))
    {
        return '零';
    }

    // 定义
    $chs = array('0','壹','贰','叁','肆','伍','陆','柒','捌','玖');
    $uni = array('','拾','佰','仟');
    $dec_uni = array('角', '分');
    $exp = array('', '万');
    $res = '';

    // 整数部分从右向左找
    for($i = strlen($int) - 1, $k = 0; $i >= 0; $k++)
    {
        $str = '';
        // 按照中文读写习惯，每4个字为一段进行转化，i一直在减
        for($j = 0; $j < 4 && $i >= 0; $j++, $i--)
        {
            $u = $int{$i} > 0 ? $uni[$j] : ''; // 非0的数字后面添加单位
            $str = $int{$i}=='-' ? '负'. $u . $str :$chs[$int{$i}] . $u . $str;
        }
        //echo $str."|".($k - 2)."<br>";
        $str = rtrim($str, '0');// 去掉末尾的0
        $str = preg_replace("/0+/", "零", $str); // 替换多个连续的0
        if(!isset($exp[$k]))
        {
            $exp[$k] = $exp[$k - 2] . '亿'; // 构建单位
        }
        $u2 = $str != '' ? $exp[$k] : '';
        $res = $str . $u2 . $res;
    }

    // 如果小数部分处理完之后是00，需要处理下
    $dec = rtrim($dec, '0');

    // 小数部分从左向右找
    if(!empty($dec))
    {
        if ($res) {
            $res .= $int_unit;
        }
        // 是否要在整数部分以0结尾的数字后附加0，有的系统有这要求
        if ($is_extra_zero)
        {
            if (substr($int, -1) === '0')
            {
                $res.= '零';
            }
        }
        $smallpart='';
        for($i = 0, $cnt = strlen($dec); $i < $cnt; $i++)
        {
            if ($dec{$i} > 0 ) {
                $u = $dec{$i} > 0 ? $dec_uni[$i] : ''; // 非0的数字后面添加单位
                //$res .= $chs[$dec{$i}] . $u;
                $smallpart .=$chs[$dec{$i}] . $u;
            }
        }
        if($smallpart && strpos($smallpart,'角')===false){
            $res=$res ? $res.'零'.$smallpart:$smallpart;
            $res=str_replace('零零','零',$res);
        }elseif ($smallpart){
            $res.=$smallpart;
        }
        $res = rtrim($res, '0');// 去掉末尾的0
        $res = preg_replace("/0+/", "零", $res); // 替换多个连续的0
    }
    else
    {
        if ($res) {
            $res .= $int_unit . '整';
        }else{

            $res = '零'.$int_unit.'整';
        }
    }
    return $res;
}


/**
 * 前端接口带刷新ticket输出公共方法
 * @param  integer $status    状态码
 *                            1001		= 必填参数缺失错误
 *                            1002		= 用户权限错误（需跳转登录）
 *                            1003		= 业务逻辑错误
 *                            1004		= 请求地址错误
...
 * @param  string  $msg       错误消息描述
 * @param  integer $http_code http状态码
 * @return [type]             json
 */
function api_output_refresh($data, $refresh_ticket, $status = 0,$msg = '', $http_code = 200){
    return api_output($status, $data, $msg, $http_code, $refresh_ticket);
}

/**
 * 跳转url地址
 * @param  string $redirect http地址
 */
function api_output_redirect($redirect = ''){
    return api_output(1301, $redirect);
}

// 生成带小数的随机数
function random_float($min = 0, $max = 1) {
    $num = $min + mt_rand() / mt_getrandmax() * ($max - $min);
    return sprintf("%.2f",$num);  //控制小数后几位
}

/**
 * 格式化数字
 * @param  [type] $number 数字
 * @param  [type] $retain 小数点后的位数
 * @return string        格式化后的数字
 */
function get_format_number($number = 0, $retain = 2){
    $number = $number ?: 0;
	$number = number_format($number,$retain);
	if(strpos($number,'.') !== false){
		$number = rtrim($number,'0');
		$number = rtrim($number,'0');
		$number = rtrim($number,'.');
	}
	$number = str_replace(',','',$number);

	return $number;
}


function getFormatNumber($number){
    $number = number_format($number,2);
    if(strpos($number,'.') !== false){
        $number = rtrim($number,'0');
        $number = rtrim($number,'0');
        $number = rtrim($number,'.');
    }
    $number = str_replace(',','',$number);

    if($number === ''){
        $number = '0';
    }

    return $number;
}

/**
 * @param float $number 原始数字
 * @param int $digit  小数位数
 * @param int $format_type  保留类型 1四舍五入 2全舍
 * @return string
 */
function formatNumber($number=0.00,$digit=2,$format_type=1){
    $tmp_digit=$digit+5;
    $number=round($number,$tmp_digit);  //防止当 形如5.489999999999 这样溢出数据被舍去问题
    if($format_type == 1){
        $number = round_number($number,$digit);
    }else{
        $multiple_num=pow(10,$digit);
        $number=bcmul($number,$multiple_num,0);
        $number = $number/$multiple_num;
        $number = sprintf("%.".$digit."f",$number);
    }
    return $number;
}


/**
 * 格式化数字 保留小数点后的0
 * @param  [type] $number 数字
 * @param  [type] $retain 小数点后的位数
 * @return [type]         格式化后的数字
 */
function get_number_format($number = 0, $retain = 2){
    $number = number_format($number,$retain);
    $number = str_replace(',','',$number);
    return $number;
}

/**
 * 判断字符串是否为 Json 格式  主要正对 h5上传是数组 APP上传为json做处理
 * @param  string  $data  Json 字符串
 * @param  bool    $assoc 是否返回关联数组。默认返回对象
 *
 * @return array|bool|object 成功返回转换后的对象或数组，失败返回 false
 */
function isJson($data = '', $assoc = false) {
    $data = json_decode($data, $assoc);
    if (($data && is_object($data)) || (is_array($data) && !empty($data))) {
        return $data;
    }
    return false;
}
//身份证号码校验
function is_idcard($id_card){

	$id_card = strtoupper($id_card);
	// 校验白名单
	$white_list = [
		'410881198401197124', '320304196206140411', '110109196507244011', '520202197106113035'
	];
	if (in_array($id_card, $white_list)) {
		return TRUE;
	}
	$patternDalu     =  "#(^\d{15}$)|(^\d{17}(\d|X)$)#"; //大陆
	$patternHongkong = "/^((\s?[A-Za-z])|([A-Za-z]{2}))\d{6}(\([0−9aA]\)|[0-9aA])$/"; //香港
	$patternTaiwan   = "/^[a-zA-Z][0-9]{9}$/";  //台湾
	$patternMacao    = "/^[1|5|7][0-9]{6}\([0-9Aa]\)/";  //澳门

	if (  preg_match($patternDalu, $id_card)
		|| preg_match($patternHongkong, $id_card)
		|| preg_match($patternTaiwan, $id_card)
		|| preg_match($patternMacao, $id_card)
	) {
		return  true;
	}else{
		return false;
	}
	
}
/**
 * 获取静态资源文件
 * @param  boolean $is_application 	[是否为应用目录 true=是 false=不是]
 * @return [string]                 返回共用地址
 */
function static_resources($is_application = true){
	$resources = '/v20/public/static/'.($is_application ? app('http')->getName().'/' : '');
	return $resources;
}

/**
 * 获取应用配置项
 * @param  string $name 应用配置项名称
 * @return string       应用配置项对应的值
 */
function cfg($name = null, $value = null){
    if(empty($name)) return '';

    static $static_config;//优先返回静态变量里面的参数信息

    if(empty($static_config)){
        $cache = cache();
        $nowLang = $cache->get('now_lang');
        $configService = new \app\common\model\service\ConfigService;
        $config = $configService->getConfigData($nowLang);

        $static_config = $config;
    }
    //优先执行设置获取或 赋值
    if(!is_null($value)) {
        if(!isset($static_config[$name])){
            $static_config[$name] = $value;
            return $static_config[$name];
        }
        else{
            $static_config[$name] = $value;
            return $static_config[$name];
        }
    }
    elseif(isset($static_config[$name])){
        return $static_config[$name];
    }
    return isset($config[$name]) ? $config[$name] : '';
}

/**
 * 获取定制开关配置项
 * @param  string $name 应用配置项名称
 * @return string       应用配置项对应的值
 */
function customization($name = null, $value = null){
    if(empty($name)) return '';

    static $static_customization;//优先返回静态变量里面的参数信息

    if(empty($static_customization)){
        $configService = new \app\common\model\service\config\ConfigCustomizationService;
        $customization = $configService->getConfigData();

        $static_customization = $customization;
    }
    //优先执行设置获取或 赋值
    if(!is_null($value)) {
        if(!isset($static_customization[$name])){
            $static_customization[$name] = $value;
            return $static_customization[$name];
        }
        else{
            $static_customization[$name] = $value;
            return $static_customization[$name];
        }
    }
    elseif(isset($static_customization[$name])){
        return $static_customization[$name];
    }
    return isset($customization[$name]) ? $customization[$name] : '';
}


/**
 * 获取config_data配置项
 * @param  string $name 应用配置项名称
 * @return string       应用配置项对应的值
 */
function config_data($name = null, $value = null){
    if(empty($name)) return '';

    static $static_config_data;//优先返回静态变量里面的参数信息

    if(empty($static_config_data)){
        $configService = new \app\common\model\service\ConfigDataService;
        $config = $configService->getConfigData();

        $static_config_data = $config;
    }
    //优先执行设置获取或 赋值
    if(!is_null($value)) {
        if(!isset($static_config_data[$name])){
            $static_config_data[$name] = $value;
            return $static_config_data[$name];
        }
        else{
            $static_config_data[$name] = $value;
            return $static_config_data[$name];
        }
    }
    elseif(isset($static_config_data[$name])){
        return $static_config_data[$name];
    }
    return isset($config[$name]) ? $config[$name] : '';
}
//function cfg($name){
//	if(empty($name)) return '';
//	$cache = cache();
//	if(empty($cache->get('config'))){
//		$configService = new \app\common\model\service\ConfigService;
//		$all_config = $configService->getConfigData();
//		$cache->set('config',$all_config);
//    }
//    $config = $cache->get('config');
//	return isset($config[$name]) ? $config[$name] : '';
//}

/**
 * *封装一个通用的
 * cURL封装**
 * *$postfields 参数
 * */
function http_request($url, $method = 'GET', $postfields = null, $headers = array(), $debug = false) {
    $method = strtoupper($method);
    $ci = curl_init();
    /* Curl settings */
    curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
    curl_setopt($ci, CURLOPT_TIMEOUT, 100); /* 设置cURL允许执行的最长秒数 */
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
    switch ($method) {
        case "POST":
            curl_setopt($ci, CURLOPT_POST, true);
            if (!empty($postfields)) {
                $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
            }
            break;
        default:
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
            break;
    }
    $ssl = preg_match('/^https:\/\//i', $url) ? TRUE : FALSE;
    curl_setopt($ci, CURLOPT_URL, $url);
    if ($ssl) {
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
    }
    //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
    //curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
    //curl_setopt($ci, CURLOPT_MAXREDIRS, 2); /* 指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的 */
    curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
     /* curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */
    $response = curl_exec($ci);
    $requestinfo = curl_getinfo($ci);
    $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    if ($debug) {
        echo "=====post data======\r\n";
        var_dump($postfields);
        echo "=====info===== \r\n";
        print_r($requestinfo);

        echo "=====response=====\r\n";
        print_r($response);
    }
    curl_close($ci);
    return array($http_code, $response, $requestinfo);
}



function curlPost($url,$data,$timeout=45,$header = "Content-type: application/x-www-form-urlencoded;charset=utf-8"){

    $ch = curl_init();
    $headers[] = $header;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
   //  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.80 Safari/537.36');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch ,CURLOPT_TIMEOUT ,$timeout);
    $result = curl_exec($ch);
    curl_close($ch);
   //  $result = json_decode($result, true);
    /*if ($result && $result['rstCode']) {
        $rstCodeMsg = $this->a4_rstCode($result['rstCode']);
        $result['rstCodeMsg'] = $rstCodeMsg;
    }*/
    return $result;
}


/**
 * 替换图片网址URL为支持云存储的方法，如果开启OSS，会自动将图片网址换成OSS的
 * @param  string  $url 	[图片网址]
 * @return [string]         返回处理后的网址
 */
function replace_file_domain($url){
	if(empty($url)){
		return '';
	}

	if(cfg('static_oss_access_domain_names') && strpos($url,cfg('static_oss_access_domain_names').'/upload/') !== false){
        if (cfg('use_https') && stripos($url, 'http://') !== false) {
            $url = 'https://' . substr($url, 7);
        }
		return $url;
	}
	if(cfg('static_obs_access_domain_names') && strpos($url,cfg('static_obs_access_domain_names').'/upload/') !== false){
        if (cfg('use_https') && stripos($url, 'http://') !== false) {
            $url = 'https://' . substr($url, 7);
        }
        return $url;
    }
	$stripos_result = stripos($url,'./upload/');
	if($stripos_result === 0){
		$url = cfg('site_url').ltrim($url,'.');
	}
	$stripos_result = stripos($url,'/upload/');
	if($stripos_result === 0){
		$url = cfg('site_url').$url;
	}
    if (request()->server('REQUEST_SCHEME')) {
        $request_scheme = request()->server('REQUEST_SCHEME');
    } else {
        $request_scheme = 'https';
        if (stripos($request_scheme.'://',cfg('site_url')) == false) {
            $request_scheme = 'http';
        }
    }
	//阿里云
	if($stripos_result !== false && cfg('static_oss_switch') && cfg('static_oss_access_domain_names')){
		$url = str_replace(cfg('site_url'),$request_scheme.'://'.cfg('static_oss_access_domain_names'),$url);
		$url = str_replace(cfg('upload_site_url'),$request_scheme.'://'.cfg('static_oss_access_domain_names'),$url);
		if(cfg('use_https') && stripos($url,'http://') !== false){
			$url = str_replace(str_replace('https://','http://',cfg('site_url')),$request_scheme.'://'.cfg('static_oss_access_domain_names'),$url);
			$url = str_replace(str_replace('https://','http://',cfg('upload_site_url')),$request_scheme.'://'.cfg('static_oss_access_domain_names'),$url);
		}
	}
    //腾讯云
    if($stripos_result !== false && cfg('static_cos_switch') && cfg('static_cos_region')){
        $url = str_replace(cfg('site_url'),$request_scheme.'://'.cfg('static_cos_access_domain_names'),$url);
        $url = str_replace(cfg('upload_site_url'),$request_scheme.'://'.cfg('static_cos_access_domain_names'),$url);
        if(cfg('use_https') && stripos($url,'http://') !== false){
            $url = str_replace(str_replace('https://','http://',cfg('site_url')),$request_scheme.cfg('static_cos_access_domain_names'),$url);
            $url = str_replace(str_replace('https://','http://',cfg('upload_site_url')),$request_scheme.cfg('static_cos_access_domain_names'),$url);
        }
    }
	//华为云
	if($stripos_result !== false && cfg('static_obs_switch') && cfg('static_obs_access_domain_names')){
        $url = str_replace(cfg('site_url'),$request_scheme.'://'.cfg('static_obs_access_domain_names'),$url);
        $url = str_replace(cfg('upload_site_url'),$request_scheme.'://'.cfg('static_obs_access_domain_names'),$url);
        if(cfg('use_https') && stripos($url,'http://') !== false){
            $url = str_replace(str_replace('https://','http://',cfg('site_url')),$request_scheme.'://'.cfg('static_obs_access_domain_names'),$url);
            $url = str_replace(str_replace('https://','http://',cfg('upload_site_url')),$request_scheme.'://'.cfg('static_obs_access_domain_names'),$url);
        }
    }

	return $url;
}

/**
 * Notes: v20处理图片路劲
 * @param $url
 * @param string $exit
 * @return string
 * @author: weili
 * @datetime: 2020/9/9 13:50
 */
function dispose_url($url, $exit='') {
    if (!$url) {
        return '';
    }
    if (strpos($url,'/v20/') !== false) {
        $msg_url = cfg('site_url').$url;
    } elseif($exit) {
        $msg_url = file_domain().$exit.$url;
    } else {
        $msg_url = replace_file_domain($url);
    }
    return $msg_url;
}

/**
 * RGB转 十六进制
 * @param $rgb RGB颜色的字符串 如：rgb(255,255,255);
 * @return string 十六进制颜色值 如：#FFFFFF
 */
function rgb_to_hex($rgb){
    $regexp = "/^rgb\(([0-9]{0,3})\,\s*([0-9]{0,3})\,\s*([0-9]{0,3})\)/";
    $re = preg_match($regexp, $rgb, $match);
    $re = array_shift($match);
    $hexColor = "#";
    $hex = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F');
    for ($i = 0; $i < 3; $i++) {
        $r = null;
        $c = $match[$i];
        $hexAr = array();
        while ($c > 16) {
            $r = $c % 16;
            $c = ($c / 16) >> 0;
            array_push($hexAr, $hex[$r]);
        }
        array_push($hexAr, $hex[$c]);
        $ret = array_reverse($hexAr);
        $item = implode('', $ret);
        $item = str_pad($item, 2, '0', STR_PAD_LEFT);
        $hexColor .= $item;
    }
    return $hexColor;
}


/**
 * 裁剪图片的方法，得到期望尺寸的图片
 *
 * @param  string  $img 	[图片网址]
 * @param  string  $width 	[宽度]
 * @param  string  $height 	[高度]
 * @param  string  $resize 	[裁剪类别]   传 fill 固定宽高，居中裁剪。不传为保留比例的图片。
 * @return [string]         返回处理后的网址
 */
function thumb_img($img,$width,$height = 0,$resize = ''){
	$img = replace_file_domain($img);
	//高度可以不传，自动使用宽度，识别为正方形
	if(!$height){
		$height = $width;
	}
	if(stripos($img, '.gif') !== false){
		return $img;
	}

	//检测图片是否已经包含压缩，若压缩则去除
	if(stripos($img, 'c=Image&a=thumb') !== false){
		$url_arr = parse_url($img);
		if($url_arr['query']){
            $queryParts = explode('&', $url_arr['query']);

            $params = array();
            foreach ($queryParts as $param) {
                $item = explode('=', $param);
                $params[$item[0]] = $item[1];
            }
            $urlQuery = $params;
            if($urlQuery['url']){
                $img = urldecode($urlQuery['url']);
            }
		}
	}


	//阿里云
	if(cfg('static_oss_switch') && strpos($img,cfg('static_oss_access_domain_names').'/upload/') !== false){
		//检测图片是否已经包含压缩，若压缩则去除
		$tmp_img_arr = explode('?x-oss-process=', $img);
		if(count($tmp_img_arr) >= 2){
			$img = $tmp_img_arr[0];
		}

		//如果是 fill
		if($resize == 'fill'){
			return $img.'?x-oss-process=image/resize,m_fill,w_'.$width.',h_'.$height;
		}
		return $img.'?x-oss-process=image/resize,w_'.$width.',h_'.$height;
	}

    //腾讯云
    if(cfg('static_cos_switch') && strpos($img,cfg('static_cos_access_domain_names').'/upload/') !== false){
        //检测图片是否已经包含压缩，若压缩则去除
        $tmp_img_arr = explode('?imageView', $img);
        if(count($tmp_img_arr) >= 2){
            $img = $tmp_img_arr[0];
        }

        //如果是 fill
        if($resize == 'fill'){
            return $img.'?imageView2/1/w/'.$width.'/h/'.$height;
        }
        return $img.'?imageView2/2/w/'.$width.'/h/'.$height;
    }

	//华为云
	if(cfg('static_obs_switch') && strpos($img,cfg('static_obs_access_domain_names').'/upload/') !== false){
		//检测图片是否已经包含压缩，若压缩则去除
		$tmp_img_arr = explode('?x-image-process=', $img);
		if(count($tmp_img_arr) >= 2){
			$img = $tmp_img_arr[0];
		}

		//如果是 fill
		if($resize == 'fill'){
			return $img.'?x-image-process=image/resize,m_fill,w_'.$width.',h_'.$height;
		}
		return $img.'?x-image-process=image/resize,w_'.$width.',h_'.$height;
	}

	if(strpos($img,'/index.php?c=Image&a=thumb') !== false){
		return $img;
	}
	return cfg('site_url').'/index.php?c=Image&a=thumb&url='.urlencode($img).'&width='.$width.'&height='.$height.'&resize='.$resize;
}

/**
 * 裁剪图片的方法，得到期望尺寸的图片
 *
 * thumb_img 的别名
 *
 */
function thumb($img,$width,$height = 0,$resize = ''){
	return thumb_img($img,$width,$height,$resize);
}

/**
 * 替换富文本中的图片网址为支持云存储的方法，如果开启OSS，会自动将图片网址换成OSS的
 * @param  string  $content 	[富文本内容]
 * @return [string]         返回处理后的内容
 */
function replace_file_domain_content($content){
	//if(cfg('static_oss_switch') && cfg('static_oss_access_domain_names')){
		$src = 'src="'.file_domain().'/upload/';
		$content = str_replace('src="'.cfg('site_url').'/upload/', $src, $content);
		$content = str_replace(str_replace('https','http','src="'.cfg('site_url').'/upload/'), $src, $content);
		$content = str_replace('src="'.cfg('config_site_url').'/upload/', $src, $content);
        $content = str_replace('src="/upload/', $src, $content);
        $content = str_replace('src=&quot;/upload/', $src, $content);
        $content = str_replace('src=&quot;'.cfg('config_site_url').'/upload/', $src, $content);
		if(cfg('use_https') && stripos($content,'http://') !== false){
			$content = str_replace('src="'.str_replace('https://','http://',cfg('site_url')).'/upload/', $src, $content);
			$content = str_replace('src="'.str_replace('https://','http://',cfg('config_site_url')).'/upload/', $src, $content);
		}
    //华为云
    if(cfg('static_obs_switch') && cfg('static_obs_access_domain_names')){
        $content = str_replace(cfg('site_url'),request()->server('REQUEST_SCHEME').'://'.cfg('static_obs_access_domain_names'),$content);
        $content = str_replace(cfg('upload_site_url'),request()->server('REQUEST_SCHEME').'://'.cfg('static_obs_access_domain_names'),$content);
        if(cfg('use_https') && stripos($content,'https://life-unee.oss-cn-guangzhou.aliyuncs.com') !== false){
            $content = str_replace(cfg('site_url'),'https://life-unee.oss-cn-guangzhou.aliyuncs.com',$content);
            $content = str_replace(cfg('upload_site_url'),'https://life-unee.oss-cn-guangzhou.aliyuncs.com',$content);
        }
        if(cfg('use_https') && stripos($content,'http://') !== false){
            $content = str_replace(str_replace('https://','http://',cfg('site_url')),request()->server('REQUEST_SCHEME').'://'.cfg('static_obs_access_domain_names'),$content);
            $content = str_replace(str_replace('https://','http://',cfg('upload_site_url')),request()->server('REQUEST_SCHEME').'://'.cfg('static_obs_access_domain_names'),$content);
        }
    }
    //embed标签视频无法播放，特殊处理type=video/x-ms-asf-plugin转换成video标签
    preg_match_all('/<embed(.*?)\/>/i', $content, $m);
    if ($m) {
        $search = $m[0];
        $replace = $m[1];
        foreach ($search as $k => $v) {
            if (strpos($v, 'video/x-ms-asf-plugin') !== false) {
                $content = str_replace($v, '<video ' . $replace[$k] . ' controls="controls">您的浏览器不支持 video 标签' . '</video>', $content);
            }
        }
    }

	//}
	return $content;
}

//替换富文本里的图片为正常的，去除样式
function replace_file_domain_content_img($content){
	$content = str_replace("font-family:Lato, \"", '', $content);
	$content = str_replace("\" font-size:", 'font-size:', $content);
	$content = str_replace("=\"\"", '', $content);
    $content = preg_replace("/<img.*?src=(\"|\')(.*?)(\"|\').*?\/\>/is", '<img style="max-width:100%;vertical-align:top;" src="$2" />', $content);
	$content = preg_replace("/<video.*?src=(\"|\')(.*?)(\"|\').*?\>/is", '<video style="width:100%;height:calc(100vw / 16 * 9);vertical-align:top;" src="$2" controls preload="none" />', $content);
    return replace_file_domain_content($content);
}

/**
 * 给数组处理内容 域名自动换OSS。
 * $data : array 要处理的数组
 * $img_field: array  图片字段，若传递则替换指定，不传递替换key包含image或video的
 * $rich_field:  array 富文本字段，若传递则替换指定，不传递替换key包含richText的
 * $img_arr: array 图片或视频是数组如：
 * "image" => [
'/upload/diypage/000/000/811/603c65f7bab02849.jpg',
'/upload/diypage/000/000/811/603c65f7bab02849.jpg',
'/upload/diypage/000/000/811/603c65f7bab02849.jpg'
]
 */
function replace_file_domain_arr($data, $img_field = [], $rich_field = [], $img_arr = [])
{
    if (is_array($data)) {
        foreach ($data as $key => $val) {
            if (is_array($val) && !in_array($key, $img_arr)) {
                $data[$key] = replace_file_domain_arr($val, $img_field, $rich_field, $img_arr);
            } elseif (is_array($val) && in_array($key, $img_arr)) {
                foreach ($val as $k => $v) {
                    $data[$key][$k] = replace_file_domain($v);
                }
            } else {
                if (!empty($img_field) && in_array($key, $img_field)) {        //根据传递的 img_field 参数替换图片
                    $data[$key] = replace_file_domain($val);
                } elseif (empty($img_field) && (stripos($key, 'image') !== false || stripos($key, 'video') !== false)) {        //若未传递 img_field，则根据参数包含来替换
                    $data[$key] = replace_file_domain($val);
                }
                if (!empty($rich_field) && in_array($key, $rich_field)) {        //根据传递的 rich_field 参数替换富文本
                    $data[$key] = replace_file_domain_content($val);
                } elseif (empty($rich_field) && (stripos($key, 'richText') !== false)) {        //若未传递 rich_field，则根据参数包含来替换富文本
                    $data[$key] = replace_file_domain_content($val);
                }
            }
        }
    }
    return $data;
}
//上传文件目录
function file_domain(){
    static $static_file_domain;
    if (empty($static_file_domain)){
        if(cfg('static_oss_switch') && cfg('static_oss_access_domain_names')){ //阿里云
            $static_file_domain = $_SERVER['REQUEST_SCHEME'].'://'.cfg('static_oss_access_domain_names');
        } else if (cfg('static_obs_switch') && cfg('static_obs_access_domain_names')){  //华为云
            $static_file_domain = $_SERVER['REQUEST_SCHEME'].'://'.cfg('static_obs_access_domain_names');
        } else if (cfg('static_cos_switch') && cfg('static_cos_region')){  //腾讯云
            $static_file_domain = $_SERVER['REQUEST_SCHEME'].'://'.cfg('static_cos_access_domain_names');
        } else {
            $static_file_domain = cfg('site_url');
        }
    }
    return $static_file_domain;
}


/**
 * 多域名支持下替换域名为当前访问域名	字符串或数组都可以
 * @param  string|array  $array 	[需要替换的内容]
 * @return [array|array]         返回处理后的内容
 */
function replace_domain($array){
	if(!$array){
		return $array;
	}
	if(cfg('more_domain_visit') && cfg('config_site_url') != cfg('now_site_url')){
		foreach($array as $key=>$value){
			if(is_array($array[$key])){
				$array[$key] = replace_domain($value);
			}else{
				$array[$key] = str_replace(cfg('config_site_url'),cfg('now_site_url'),$value);
			}
		}
	}
	return $array;
}

/**
 * @desc 根据两点间的经纬度计算距离
 * @param float $lat 纬度值
 * @param float $lng 经度值
 * @return float
*/
function get_distance($lat1, $lng1, $lat2, $lng2){
	$earthRadius = 6367000;
	$lat1 = ($lat1 * pi() ) / 180;
	$lng1 = ($lng1 * pi() ) / 180;

	$lat2 = ($lat2 * pi() ) / 180;
	$lng2 = ($lng2 * pi() ) / 180;

	$calcLongitude = $lng2 - $lng1;
	$calcLatitude = $lat2 - $lat1;
	$stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
	$stepTwo = 2 * asin(min(1, sqrt($stepOne)));
	$calculatedDistance = $earthRadius * $stepTwo;
	return round($calculatedDistance);
}

/**
 * 获得可读的距离
 * @param  string  $range 	距离
 * @param  bool  $space 	距离与单位之前是否加空格
 * @param  bool  $is_ch 	是否中文
 * @return string
 */
function get_range($range, $space = true, $isChinese = false){
	if($range < 1000){
		return round($range,2).($space ? ' ' : ''). ($isChinese ? L_('米') : 'm');
	}else{
		return floatval(round($range/1000,2)).($space ? ' ' : ''). ($isChinese ? L_('公里') : 'km');
	}
}

/**
 * 获取语言展示
 * $key string 语言键名
 * $param array 支持多参数
 * return string ;
 */
function L_1($key = '', $param = '', $js = false, $from = 0, $replace = 1, $filter = ''){
    if (empty($key)) {
        return $key;
	}
	$result = $key;
    if($replace){
        if(is_array($param)){
            foreach ($param as $k => $v) {
                if(empty($v) && $v != '0') $v = '';
                $result = str_replace($k, $v, $result);//替换语言中的变量
            }
        }
        else{
            if(empty($param) && $param != '0') $param = '';
            $pattern = '/x1|X1/';
            $result = preg_replace($pattern, $param, $result);//替换语言中的变量
        }
    }

    if ($filter && function_exists($filter)) {
        return $filter($result);
    }
    return $result;
}

function L_($key = '', $param = '', $js = false, $from = 0, $replace = 1, $filter = ''){
    if (empty($key)) {
        return $key;
    }
    $open_multilingual = cfg('open_multilingual');//是否开启多语言
    $default_language = cfg('default_language');//默认语言
    if(!$open_multilingual){
        $result = $key;
    }else{
        $result = '';
        $phpSelf = Request()->server('PHP_SELF');
        $current_module_str = 'v20|v20|v20';
        $key = trim($key);

        $fields = cfg('tmp_system_lang') ? cfg('tmp_system_lang') : (cfg('system_lang') ? cfg('system_lang') :  $default_language);//默认中文
        if(empty($fields)) return $key;

        $s_key = 'lang/php_get'.$key.$current_module_str.$fields;

        $cache = cache();
        $result = $cache->get($s_key);

        if(!$result){

            //引入繁体类
            $ZhConvert = new \language\ZhConvert();
            //这里将来可以做缓存
            $langDb = new \app\common\model\service\config\LangService();
            $where = array();
            $where['key'] = $key;
            $where['from'] = $from;
            $field = 'id,`key`,group_name,module_name,action_name,'.$fields;
            $lang = $langDb->getSome($where,$field);
            //没找到当前数据，则插入一条数据
            if(empty($lang)){
                if(!$js){
                    $insert = array(
                        'key' => $key,
                        'chinese' => $key,
                        'traditional' => $ZhConvert->zh($key),
                        'add_time' => time(),
                        'from' => $from,
                        'group_name' => 'v20',
                        'module_name' => 'v20',
                        'action_name' => 'v20',
                    );
                }
                else{//如果是js代码上报的词语，不存它的GROUPNAME MODULENAME
                    $insert = array(
                        'key' => $key,
                        'chinese' => $key,
                        'from' => $from,
                        'traditional' => $ZhConvert->zh($key),
                        'add_time' => time()
                    );
                }
                $langDb->add($insert);
                $result = $key;//方便后续替换参数
                $cache->set($s_key,$result);
            } else{
                $zero = $first = $second = $third = array();
                foreach ($lang as $k => $value) {
                    if($value['action_name']) {
                        $third[$value['key'].$value['group_name'].'|'.$value['module_name'].'|'.$value['action_name']] = $value[$fields];
                    }
                    if($value['module_name']) {
                        $second[$value['key'].$value['group_name'].'|'.$value['module_name']] = $value[$fields];
                    }
                    if($value['group_name']) {
                        $first[$value['key'].$value['group_name']] = $value[$fields];
                    }
                    $zero[$value['key']] = $value[$fields];
                }

                $module3 = 'v20|v20|v20';
                $module2 = 'v20|v20';
                $module1 = 'v20';

                if(isset($third[$key.$module3])){
                    $result = $third[$key.$module3];
                }
                elseif(isset($second[$key.$module2])){
                    $result = $second[$key.$module2];
                }
                elseif(isset($first[$key.$module1])){
                    $result = $first[$key.$module1];
                }
                else{
                    $result = isset($zero[$key]) ? $zero[$key] : '';
                }
                $cache->set($s_key,$result);
            }
        }
        if(empty($result)){
            $result = $key;
        }
    }
    if($replace){
        if(is_array($param)){
            foreach ($param as $k => $v) {
                if(empty($v) && $v != '0') $v = '';
                $result = str_replace($k, $v, $result);//替换语言中的变量
            }
        }
        else{
            if(empty($param) && $param != '0') $param = '';
            $pattern = '/x1|X1/';
            $result = preg_replace($pattern, $param, $result);//替换语言中的变量
        }
    }

    if ($filter && function_exists($filter)) {
        return $filter($result);
    }
    return $result;
}
/**
 * 调试数据的本地保存
 *
 * <code>
 * // O2O缓存目录在网站根目录下的runtime文件夹
 * // 简单的调试
 * fdump($arr); 会在缓存目录下保存一个  test_fdump.php 的文件
 * // 自定义文件名的调试
 * fump($arr,'custom'); 会在缓存目录下替换保存一个  custom_fdump.php 的文件
 * // 追加到文件中的调试
 * fump($arr,'custom',true); 会在缓存目录下保存一个  custom_fdump.php 的文件 在文件末尾追加内容
 * </code>
 *
 * @access public
 * @param  string  $data    进行调试的数据
 * @param  string  $filename 调试文件的文件名，后面会自动追加 _fdump.php，方便文件存储的分类辨别
 * @param  string  $append    是否采用追加的模式，默认不采用、覆盖文件
 * @return string
 */
function fdump($data,$filename='test',$append=false){
    try {
        empty($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] = WEB_PATH;
        if(strpos($filename,'/') > 0){
            $fileName = rtrim($_SERVER['DOCUMENT_ROOT'],'/').'/v20/'.$filename.'_fdump.php';
            $dirName = dirname($fileName);
            if(!file_exists($dirName)){
                @mkdir($dirName,0777,true);
                @chown($dirName,'www');
                @chgrp($dirName,'www');
            }
        }else{
            $rootPath = app()->getRootPath() . '../';
            $fileName = $rootPath . 'api/log/' . date('Ymd') . '/' .$filename . '_fdump.php';
            $dirName = dirname($fileName);
            if(!file_exists($dirName)){
                @mkdir($dirName, 0777, true);
                @chown($dirName,'www');
                @chgrp($dirName,'www');
            }
        }
        $debug_trace = debug_backtrace();
        $file = __FILE__ ;
        $line = "unknown";
        if (isset($debug_trace[0]) && isset($debug_trace[0]['file'])) {
            $file = $debug_trace[0]['file'] ;
            $line = $debug_trace[0]['line'];
        }
        $f_l = '['.$file.' : '.$line.']';
        if(!file_exists($fileName)){
            @file_put_contents($fileName,'<?php');
        }
        @chmod($fileName,0777);
        @chown($fileName,'www');
        @chgrp($fileName,'www');
        
        if($append){
            @file_put_contents($fileName,PHP_EOL.$f_l . PHP_EOL.date('Y-m-d H:i:s').' '.$_SERVER['REQUEST_URI'].PHP_EOL.var_export($data,true).PHP_EOL,FILE_APPEND);
        }else{
            @file_put_contents($fileName,'<?php'.PHP_EOL.date('Y-m-d H:i:s').' '.$f_l.PHP_EOL.$_SERVER['REQUEST_URI'].PHP_EOL.var_export($data,true));
        }
    } catch (\Exception $e) {
        @file_put_contents('fdump.log','----打印---- > '.print_r($e->getMessage(),true). PHP_EOL, 8);
    }
}

/**
 * 调试数据的API目录长时间保存
 *
 * <code>
 * // 简单的调试
 * fdump($arr); 会在缓存目录下保存一个  test_fdump.php 的文件
 * // 自定义文件名的调试
 * fump($arr, 'custom'); 会在缓存目录下替换保存一个  custom_fdump.php 的文件
 * // 追加到文件中的调试
 * fump($arr, 'custom', true); 会在缓存目录下保存一个  custom_fdump.php 的文件 在文件末尾追加内容
 * // 创建目录的调试
 * fump($arr, 'test/custom'); 会在缓存目录下创建一个test目录，且保存一个  custom_fdump.php 的文件
 * </code>
 *
 * @access public
 * @param  string|array  $data    进行调试的数据
 * @param  string  $filename 调试文件的文件名，后面会自动追加 _fdump.php，方便文件存储的分类辨别
 * @param  string  $append    是否采用追加的模式，默认不采用、覆盖文件
 * @return string
 */
function fdump_api($data, $filename='test', $append=false){
    $oldUmask = umask();
    try {
        $oldUmask = umask();
        umask(0);
        if (isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT']) {
            $fileName = rtrim($_SERVER['DOCUMENT_ROOT'],'/') . '/api/log/' . date('Ymd') . '/' . $filename . '_fdump.php';
            $dirName = dirname($fileName);
            if(!file_exists($dirName)){
                @mkdir($dirName,0777,true);
                @chown($dirName,'www');
                @chgrp($dirName,'www');
            }
        } else {
            $rootPath = app()->getRootPath() . '../';
            $fileName = $rootPath . 'api/log/' . date('Ymd') . '/' . $filename . '_fdump.php';
            $dirName = dirname($fileName);
            if(!file_exists($dirName)){
                @mkdir($dirName, 0777, true);
                @chown($dirName,'www');
                @chgrp($dirName,'www');
            }
        }
        $debug_trace = debug_backtrace();
        $file = __FILE__ ;
        $line = "unknown";
        if (isset($debug_trace[0]) && isset($debug_trace[0]['file'])) {
            $file = $debug_trace[0]['file'] ;
            $line = $debug_trace[0]['line'];
        }
        $f_l = '['.$file.' : '.$line.']';
        
        if(!file_exists($fileName)){
            @file_put_contents($fileName,'<?php');

        }
        if(file_exists($fileName)){
            @chmod($fileName,0777);
            @chown($fileName,'www');
            @chgrp($fileName,'www');
            /*
            @chmod($fileName,0777);
            $upLevelFile=dirname($fileName);
            @chmod($upLevelFile,0777);
            $upLevelFile=dirname($upLevelFile);
            @chmod($upLevelFile,0777);
            */
       }
        if($append){
            file_put_contents($fileName,PHP_EOL.$f_l.PHP_EOL.date('Y-m-d H:i:s').' '.$_SERVER['REQUEST_URI'].PHP_EOL.var_export($data,true).PHP_EOL,FILE_APPEND);
        }else{
            @file_put_contents($fileName,'<?php'.PHP_EOL.date('Y-m-d H:i:s').' '.$_SERVER['REQUEST_URI'].PHP_EOL.var_export($data,true));
        }
        umask($oldUmask);
    } catch (\Exception $e) {
        umask($oldUmask);
        @file_put_contents('fdump_api.log','----打印---- > '.print_r($e->getMessage(),true). PHP_EOL, 8);
    }
    umask($oldUmask);
}

/*****
 **生成简单的随机数
 **$length 需要的长度
 **$onlynum 生成纯数字的
 **$nouppLetter  不需要大写的，数字和小写的混合
 **$capitalize  只需要大写和数字
 **/
function createRandomStr($length=6,$onlynum=false,$nouppLetter=false,$capitalize=false){
    if(!($length>0)) return false;
    $returnstr='';
    if($onlynum){
        for($i=0;$i<$length;$i++){
            $returnstr .= rand(0,9);
        }
    }elseif ($capitalize) {
        $strarr = array_merge(range(0,9),range('A','Z'));
        shuffle($strarr);
        shuffle($strarr);
        $returnstr = implode('',array_slice($strarr,0,$length));
    }else if($nouppLetter){
        $strarr = array_merge(range(0,9),range('a','z'));
        shuffle($strarr);
        shuffle($strarr);
        $returnstr = implode('',array_slice($strarr,0,$length));
    }else{
        $strarr = array_merge(range(0,9),range('a','z'),range('A','Z'));
        shuffle($strarr);
        shuffle($strarr);
        $returnstr = implode('',array_slice($strarr,0,$length));
    }
    return $returnstr;
}


/**
 * 调试数据的数据库保存
 *
 *
 * @access public
 * @param  string|array  $data     调试数据
 * @param  string  $filename 调试名称
 * @return string
 */
function fdump_sql($data,$name){
    $data = var_export($data,true);
    $dataFdump = array(
        'data' => $data,
        'name' => $name,
        'time' => time(),
    );
    (new \app\common\model\service\FdumpSqlService())->add($dataFdump);
}

/**
 * 生成订单号
 * @param $uid
 * @return string
 */
function build_real_orderid($uid)
{
    return date('ymdhis') . substr(microtime(), 2, 9 - strlen($uid)) . $uid;
}

/**
 * 隐藏显示的用户名
 * @param $name
 * @return string
 */
function str_replace_name($name){
    $length = utf8_strlen($name);
    if ( $length == 2) {
       $name = preg_replace("/(.).{1,1}(.*)/iu","$1*$2",$name);
    }elseif ($length==3) {
       $name = preg_replace("/(.).{1,1}(.*)/iu","$1*$2",$name);
    }elseif ($length==4) {
       $name = preg_replace("/(.).{1,2}(.*)/iu","$1**$2",$name);
    }elseif ($length==5) {
       $name = preg_replace("/(.).{1,4}(.*)/iu","$1***$2",$name);
    }elseif ($length>=6) {
       $name = preg_replace("/(.).{1,4}(.*)/iu","$1****$2",$name);
    }
    return $name;
}

/**
 * 计算中文字符串长度
 * @param $name
 * @return string
 */
function utf8_strlen($string = null) {
    // 将字符串分解为单元
    preg_match_all("/./us", $string, $match);
    // 返回单元个数
    return count($match[0]);
}

/*
 * 截取中文字符串
 */
function msubstr($str,$start=0,$length,$suffix=true,$charset="utf-8"){
    if(function_exists("mb_substr")){
        if ($suffix && mb_strlen($str, $charset)>$length)
            return mb_substr($str, $start, $length, $charset)."...";
        else
            return mb_substr($str, $start, $length, $charset);
    }elseif(function_exists('iconv_substr')) {
        if ($suffix && strlen($str)>$length)
            return iconv_substr($str,$start,$length,$charset)."...";
        else
            return iconv_substr($str,$start,$length,$charset);
    }
    $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
    $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
    $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
    $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
    preg_match_all($re[$charset], $str, $match);
    $slice = join("",array_slice($match[0], $start, $length));
    if($suffix) return $slice."…";
    return $slice;
}

/**
 * 二维数组排序
 */
function sortArrayAsc($preData,$sortType='price'){
    $sortData = array();
    foreach ($preData as $key_i => $value_i){
        $price_i = $value_i[$sortType];
        $value_i['array_key'] = $key_i;
        $min_key = '';
        $sort_total = count($sortData);
        foreach ($sortData as $key_j => $value_j){
            if($price_i<$value_j[$sortType]){
                $min_key = $key_j+1;
                break;
            }
        }
        if(empty($min_key)){
            array_push($sortData, $value_i);
        }else {
            $sortData1 = array_slice($sortData, 0,$min_key-1);
            array_push($sortData1, $value_i);
            if(($min_key-1)<$sort_total){
                $sortData2 = array_slice($sortData, $min_key-1);
                foreach ($sortData2 as $value){
                    array_push($sortData1, $value);
                }
            }
            $sortData = $sortData1;
        }
    }
    return $sortData;
}


/**
 * 二维数组排序
 *
**/
function sortArray($arrays,$sort_key,$sort_order=SORT_ASC,$sort_type=SORT_NUMERIC){
	if(is_array($arrays)){
		foreach ($arrays as $array){
			if(is_array($array)){
				$key_arrays[] = $array[$sort_key];
			}else{
				return $arrays;
			}
		}
	}else{
		return $arrays;
	}
	array_multisort($key_arrays,$sort_order,$sort_type,$arrays);
	return $arrays;
}

/**
 * 格式化手机号存储
 *
**/
function phone_format($country, $phone){
    if($country == '61'){
        // $phone = ltrim($phone,'+61');   // +6104 +614都可以去除 +61
        if(substr($phone, 0,3) == '+61'){
            $phone = substr($phone, 3);
        }
        if(strlen($phone) > 10){      // 6104是12位大于10，614是11位大于10
            // $phone = ltrim($phone,'61');
            if(substr($phone, 0,2) == '61'){
                $phone = substr($phone, 2);
            }
        }
        $phone = '0'.ltrim($phone,'0');   // 如果前面没有0，加个0
    }else if($country == '855'){
        // $phone = ltrim($phone,'+855');    // +85504 +8554都可以去除 +855
        if(substr($phone, 0,4) == '+855'){
            $phone = substr($phone, 4);
        }
        if(strlen($phone) > 10){      // 85504是12位大于10，8554是11位大于10
            // $phone = ltrim($phone,'855');
            if(substr($phone, 0,3) == '855'){
                $phone = substr($phone, 3);
            }
        }
        $phone = '0'.ltrim($phone,'0');   // 如果前面没有0，加个0
    }

    return $phone;
}

/*****
 **生成简单的随机数
 **$length 需要的长度
 **$onlynum 生成纯数字的
 **$nouppLetter  不需要大写的，数字和小写的混合
 **/
function create_random_str($length=6,$onlynum=false,$nouppLetter=false){
    if(!($length>0)) return false;
    $returnstr='';
    if($onlynum){
        for($i=0;$i<$length;$i++){
            $returnstr .= rand(0,9);
        }
    }else if($nouppLetter){
        $strarr = array_merge(range(0,9),range('a','z'));
        shuffle($strarr);
        shuffle($strarr);
        $returnstr = implode('',array_slice($strarr,0,$length));
    }else{
        $strarr = array_merge(range(0,9),range('a','z'),range('A','Z'));
        shuffle($strarr);
        shuffle($strarr);
        $returnstr = implode('',array_slice($strarr,0,$length));
    }
    return $returnstr;
}

/**
 * 自定义异常处理
 * @param string $msg 异常消息
 * @param string $type 异常类型 默认为\think\Exception
 * @param integer $code 异常代码 默认为1003
 * @return void
 */
function throw_exception($msg, $type=CustomException::class, $code=1003) {
    throw new $type($msg, $code);
}

/**
 * 获取字符串首字母拼音
 * @return string
 */
function get_first_charter($str){

    if(empty($str)){return '';}

    $fchar = ord($str{0});


    if($fchar>=ord('A')&&$fchar<=ord('z')) return strtoupper($str{0});
    if($fchar>=ord('0')&&$fchar<=ord('9')) return strtoupper($str{0});

    $s1=iconv('UTF-8','gb2312',$str);

    $s2=iconv('gb2312','UTF-8',$s1);

    $s=$s2==$str?$s1:$str;

    $asc=ord($s{0})*256+ord($s{1})-65536;

    if($asc>=-20319&&$asc<=-20284) return 'A';

    if($asc>=-20283&&$asc<=-19776) return 'B';

    if($asc>=-19775&&$asc<=-19219) return 'C';

    if($asc>=-19218&&$asc<=-18711) return 'D';

    if($asc>=-18710&&$asc<=-18527) return 'E';

    if($asc>=-18526&&$asc<=-18240) return 'F';

    if($asc>=-18239&&$asc<=-17923) return 'G';

    if($asc>=-17922&&$asc<=-17418) return 'H';

    if($asc>=-17417&&$asc<=-16475) return 'J';

    if($asc>=-16474&&$asc<=-16213) return 'K';

    if($asc>=-16212&&$asc<=-15641) return 'L';

    if($asc>=-15640&&$asc<=-15166) return 'M';

    if($asc>=-15165&&$asc<=-14923) return 'N';

    if($asc>=-14922&&$asc<=-14915) return 'O';

    if($asc>=-14914&&$asc<=-14631) return 'P';

    if($asc>=-14630&&$asc<=-14150) return 'Q';

    if($asc>=-14149&&$asc<=-14091) return 'R';

    if($asc>=-14090&&$asc<=-13319) return 'S';

    if($asc>=-13318&&$asc<=-12839) return 'T';

    if($asc>=-12838&&$asc<=-12557) return 'W';

    if($asc>=-12556&&$asc<=-11848) return 'X';

    if($asc>=-11847&&$asc<=-11056) return 'Y';

    if($asc>=-11055&&$asc<=-10247) return 'Z';

    return '';

}

/**
 * 调用cms模型类方法
 * @param string $func  方法名  格式：模型/方法  例如:Shop_order/test  => D('Shop_order')->test();
 * @param array $params 方法参数
 * @return array|mixed  ['error_no'=>0,'error_msg'=>'','retval'=>[]]
 * @author: 张涛
 * @date: 2020/9/11
 */
function invoke_cms_model($func, $params = [], $throwException = false,$retry=false)
{
    static $num = 1;
    $siteUrl = cfg('config_site_url');
    $api = $siteUrl . '/index.php?c=Service&a=invoke';
    $tm = time();
    $token = md5($siteUrl . $tm);
    $postData = ['func' => $func, 'params' => urlencode(serialize($params)), 'tm' => $tm];
    $result = \net\Http::curlPostOwnWithHeader($api, json_encode($postData), ['Invoke-Auth-Token:' . $token], 30);
    fdump_sql([$postData, $result], 'invoke_cms_model');

    if($retry && $result === false && $num <= 3){//请求重试
        fdump_sql([$postData, $result, $num], 'invoke_cms_model_repeat');
        $num++;
        sleep(1);
        invoke_cms_model($func, $params, $throwException);
    }

    $result = $result ? json_decode($result, true) : [];
    if ($throwException && is_array($result) && isset($result['error_no']) && $result['error_no'] == 1) {
        throw new \think\Exception($result['error_msg'] ?: '调用cms接口出错');
    }
    return $result;
}



function generate_password( $length = 8 ) {
// 密码字符集，可任意添加你需要的字符
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password ='';
    for ( $i = 0; $i < $length; $i++ )
    {
        $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
    }
    return $password;
}




/**
 * @desc 根据两点间的经纬度计算距离
 * @param float $lat 纬度值
 * @param float $lng 经度值
 */
function getDistance($lat1, $lng1, $lat2, $lng2){
    $earthRadius = 6367000;
    $lat1 = ($lat1 * pi() ) / 180;
    $lng1 = ($lng1 * pi() ) / 180;

    $lat2 = ($lat2 * pi() ) / 180;
    $lng2 = ($lng2 * pi() ) / 180;

    $calcLongitude = $lng2 - $lng1;
    $calcLatitude = $lat2 - $lat1;
    $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
    $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
    $calculatedDistance = $earthRadius * $stepTwo;
    return round($calculatedDistance);
}


function getRange($range,$space = true,$is_ch = false){
    if($range < 1000){
        return $range.($space ? ' ' : ''). ($is_ch ? L_('米') : 'm');
    }else{
        return floatval(round($range/1000,2)).($space ? ' ' : ''). ($is_ch ? L_('公里') : 'km');
    }
}

function convert_url_query($query){
    $queryParts = explode('&', $query);
    $params = array();
    foreach ($queryParts as $param)
    {
        $item = explode('=', $param);
        $params[$item[0]] = $item[1];
    }
    return $params;
}


/**
 * 求两个已知经纬度之间的距离,单位为米
 *
 * @param lng1 $ ,lng2 经度
 * @param lat1 $ ,lat2 纬度
 * @return float 距离，单位米
 */
/*function getDistance($lng1, $lat1, $lng2, $lat2)
{
    // 将角度转为狐度
    $radLat1 = deg2rad($lat1); //deg2rad()函数将角度转换为弧度
    $radLat2 = deg2rad($lat2);
    $radLng1 = deg2rad($lng1);
    $radLng2 = deg2rad($lng2);
    $a = $radLat1 - $radLat2;
    $b = $radLng1 - $radLng2;
    $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.140 * 1000;
    return $s;

}*/

function sortArr($arrays,$sort_key,$sort_order=SORT_ASC,$sort_type=SORT_NUMERIC){
    if(is_array($arrays)){
        foreach ($arrays as $array){
            if(is_array($array)){
                $key_arrays[] = $array[$sort_key];
            }else{
                return $arrays;
            }
        }
    }else{
        return $arrays;
    }
    array_multisort($key_arrays,$sort_order,$sort_type,$arrays);
    return $arrays;
}


//时间转换成秒
function time_to_second($time)
{
    if ($time == '00:00') return 0;
    $arr = explode(":", $time);
    $hour = isset($arr[0]) ? $arr[0] : 0;
    $minute = isset($arr[1]) ? $arr[1] : 0;
    return $hour * 3600 + $minute * 60;
}

//秒转换成时间
function second_to_time($v)
{
    $h = floor($v / 3600);
    if ($h < 10) {
        $h = '0' . $h;
    }
    $t = $v % 3600;
    $m = ceil($t / 60);
    if ($m < 10) {
        $m = '0' . $m;
    }
    return $h . ':' . $m;
}


/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @return mixed
 */
function get_client_ip($type = 0) {
    $type       =  $type ? 1 : 0;
    static $ip  =   NULL;
    if ($ip !== NULL) return $ip[$type];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $pos    =   array_search('unknown',$arr);
        if(false !== $pos) unset($arr[$pos]);
        $ip     =   trim($arr[0]);
    }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip     =   $_SERVER['HTTP_CLIENT_IP'];
    }elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip     =   $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u",ip2long($ip));
    $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}


/**
 * @desc 根据后台配置(配送费计算方式)取舍数字
 * @param $number
 * @return return
 */
function format_number_by_config($number){
    if(empty($number)) return 0;
    switch (cfg('count_freight_charge_method'))
    {
        case '0':$return  =  round($number,2);break;//精确到分（四舍五入到分）
        case '1':$return  =  ceil($number*10)/10;break;//进一到角收取
        case '2':$return  =  floor($number*10)/10;break;//减一到角收取
        case '3':$return  =  round($number,1);break;//四舍五入到角收取
        case '4':$return  =  ceil($number);break;//进一到元收取
        case '5':$return  =  floor($number);break;//减一到元收取
        case '6':$return  =  round($number);break;//四舍五入到元收取
        default: $return  =  round($number,2);//四舍五入到分
    }
    return $return;
}

/**8
 * 计算出两个日期之间的月份
 * @author Eric
 * @param  [type] $start_date [开始日期，如2014-03]
 * @param  [type] $end_date   [结束日期，如2015-12]
 * @param  string $explode    [年份和月份之间分隔符，此例为 - ]
 * @param  boolean $addOne    [算取完之后最后是否加一月，用于算取时间戳用]
 * @return [type]             [返回是两个月份之间所有月份字符串]
 */
function dateMonths($start_date,$end_date,$explode='-',$addOne=false)
{
    //判断两个时间是不是需要调换顺序
    $start_int = strtotime($start_date);
    $end_int = strtotime($end_date);
    if ($start_int > $end_int) {
        $tmp = $start_date;
        $start_date = $end_date;
        $end_date = $tmp;
    }


    //结束时间月份+1，如果是13则为新年的一月份
    $start_arr = explode($explode, $start_date);
    $start_year = intval($start_arr[0]);
    $start_month = intval($start_arr[1]);


    $end_arr = explode($explode, $end_date);
    $end_year = intval($end_arr[0]);
    $end_month = intval($end_arr[1]);


    $data = array();
    $data[] = $start_date;


    $tmp_month = $start_month;
    $tmp_year = $start_year;


    //如果起止不相等，一直循环
    while (!(($tmp_month == $end_month) && ($tmp_year == $end_year))) {
        $tmp_month++;
        //超过十二月份，到新年的一月份
        if ($tmp_month > 12) {
            $tmp_month = 1;
            $tmp_year++;
        }
        $data[] = $tmp_year . $explode . str_pad($tmp_month, 2, '0', STR_PAD_LEFT);
    }


    if ($addOne == true) {
        $tmp_month++;
        //超过十二月份，到新年的一月份
        if ($tmp_month > 12) {
            $tmp_month = 1;
            $tmp_year++;
        }
        $data[] = $tmp_year . $explode . str_pad($tmp_month, 2, '0', STR_PAD_LEFT);
    }
    return $data;
}

/**
 * Notes: 生成联系客服地址
 * @param $fromUser
 * @param $toUser
 * @param $relation
 * @param array $params
 * @return string
 * @throws Exception
 * @author: wanzy
 * @date_time: 2021/2/20 14:21
 */
function build_im_chat_url($fromUser, $toUser, $relation, $params = [])
{
    if (empty($fromUser) || empty($relation)) {
        throw new \Exception('参数有误');
    }
    $url = cfg('site_url') . '/packapp/customer_service_h5/pages/chat/conversations?from_user=' . $fromUser . '&to_user=' . $toUser . '&relation=' . $relation;
    
    $params['t'] = time();
    $params['s'] = md5($params['t'] . $fromUser . cfg('site_url'));

    foreach ($params as $k => $v) {
        $url .= '&' . $k . '=' . $v;
    }
    if(cfg('open_multilingual')){
        $url .= '&now_lang=' . cfg('system_lang');
    }
    return $url;
}

/**
 * Notes: 数据多语言方法
 * @param $model string 表名
 * @param $language string 当前语言
 * @return string
 * @throws Exception
 * @author: 衡婷妹
 * @date_time: 2021/3/8 9:31
 */
function _view($model){
    $openMultilingual = cfg('open_multilingual');//是否开启多语言
    $defaultLanguage = cfg('default_language');//默认语言

    if(!$openMultilingual){
        return $model;
    }
    // 用户当前语言
    $configLang = cfg('system_lang');

    if(!cfg('tmp_system_lang') && $defaultLanguage == $configLang){
        return $model;
    }
    $tmpSystemLang = cfg('tmp_system_lang');
//    $systemLang = $tmpSystemLang ? ucfirst($tmpSystemLang) : ($configLang ? ucfirst($configLang)  : ucfirst($defaultLanguage) );

    $systemLang = $tmpSystemLang ?'_'.$tmpSystemLang : ($configLang ? '_'.$configLang : '_'.$defaultLanguage);

    if (strpos($model,$systemLang. '_view') !== false ) {
        $model = str_replace($systemLang. '_view','',$model);
    }

    if(!(new \app\common\model\service\LangService())->checkLangModel($model)){
        die("LangService.php中需要添加该表{$model}的映射.");
    }

    return $model.$systemLang . '_view';
}

/*
 * Notes: 对应获取链接
 * @return string
 * @author: wanzy
 * @date_time: 2021/4/6 16:56
 */
function get_base_url($url='', $isNew = 0) {
    if(cfg('system_type') == 'village'){
        if($isNew){
            $base_url = '/packapp/platn/';
        }else{
            $base_url = '/packapp/village/';
        }
    }else{
        if($isNew){
            $base_url = '/packapp/platn/';
        }else{
            $base_url = '/packapp/plat/';
        }
    }
    $_url = cfg('site_url').$base_url;
    if ($url) {
        $_url .= ltrim($url, '/');
    }
    return $_url;
}


/**
 * Notes: 拼接参数
 * @param array
 * @return string
 */
function get_encrypt_key($array, $appKey)
{
    $newArr = array();
    ksort($array);
    foreach ($array as $key => $value) {
        $newArr[] = $key . '=' . $value;
    }
    $newArr[] = 'app_key=' . $appKey;

    $string = implode('&', $newArr);
    return md5($string);
}

function fulltext_filter($value){
	$value = htmlspecialchars_decode($value);
	$value = str_replace(['<script', '</script', '%3cscript', '%3c/script'], '', $value);
	return $value;
}

//判断字符为正整数、整数、正数、正小数、负整数、小数、负数、负小数
function positive_integer($num=0,$positive=true,$int=true){
    /**
     * $num         字符串判断
     * $positive    正负判断
     * $int         整数/小数判断
     */
    if($num)
    {
        if(is_numeric($num)){
            if($positive && $num>0 && !$int){
                return true;        //正数
            }elseif($int && floor($num)==$num && !$positive){
                return true;        //整数
            }elseif($positive && $int && $num>0 && floor($num)==$num){
                return true;    //正整数
            }elseif($positive && $int && $num>0 && floor($num)!=$num){
                return true;    //正小数
            }elseif($positive && $num<0 && !$int){
                return false;       //负数
            }elseif($int && floor($num)!=$num && !$positive){
                return false;       //小数
            }elseif($positive && $int && $num<0 && floor($num)!=$num){
                return false;   //负小数
            }elseif($positive && $int && $num<0 && floor($num)==$num){
                return false;   //负整数
            }else{
                return false; //未知类型的数字
            }
        }else{
            return false;   //不是数字
        }
    }elseif($num==='0'){
        return false;
    }else{
        return true;    //表单未填写
    }
}


//格式化手机号隐藏展示
function phone_show($phone){
    $phone_show = strlen($phone) == 11 ? substr($phone,0,3).'****'.substr($phone,7,4) : $phone;
    return $phone_show;
}

// 避免溢出的4舍5入共有方法 $number 具体数字 $precision 精确度即小数点后保留位数
function round_number($number, $precision=2) {
    $val = round($number, $precision); // 首先进行四舍五入
    $val = sprintf("%.".$precision."f",$val); // 防止溢出
//    $val = floatval($val);  // 将字符串转换成数字
    return $val;
}

/**
 * 数据库版本是否8.0+
 * @return bool
 * @author 刘若飞
 * @date 2021/08/05/
 */
function isMysqlVer8()
{
    $ver = Cache::get('mysql_version');
    if (!$ver) {
        $ver = Db::query("select VERSION() as version");
        $ver = $ver[0]['version'];
        $ver = $ver ? intval(explode('.', $ver)[0]) : 0;
        Cache::set('mysql_version', $ver, 86400 * 2);//两天缓存
    }
    if ($ver >= 8) {
        return true;
    } else {
        return false;
    }
}



/**
 * Notes: base64 转图片/视频/语音
 * @datetime: 2021/11/5 14:40
 * @param $file_name
 * @param $base64_file
 * @param $savePath
 * @param $type
 * @return string
 */
function base64_to_img($savePath,$file_name,$base64_file,$type='', $flags = '')
{
    try {
        if (!is_dir($savePath)) {
            mkdir($savePath, 0777, true);
        }
        if ($type) {
            $file_path = $savePath.$file_name.'.'.$type;
        } else {
            $file_path = $savePath.$file_name;
        }
	    // 从Base64字符串中移除数据类型和编码声明
	    list($type, $data) = explode(';', $base64_file);
	    list(, $base64_file) = explode(',', $data);
        if ($flags) {
            file_put_contents($file_path, base64_decode($base64_file), $flags);
        } else {
            file_put_contents($file_path, base64_decode($base64_file));
        }
        $return_url = trim($file_path,'.');
    }catch (\Exception $exception){
        fdump_api(['savePath' => $savePath, 'file_name' => $file_name, 'exception' => $exception->getMessage(), 'line' => $exception->getLine(), 'file' => $exception->getFile(), 'code' => $exception->getCode()], 'base64_to_img/imgLog');
        $return_url = '';
    }
    return $return_url;
}

//PHP 计算两个时间戳之间相差的时间
//功能：计算两个时间戳之间相差的日时分秒
//$begin_time  开始时间戳
//$end_time 结束时间戳
    function timediff($begin_time, $end_time)
    {
        if ($begin_time < $end_time) {
            $starttime = $begin_time;
            $endtime = $end_time;
        } else {
            $starttime = $end_time;
            $endtime = $begin_time;
        }
        //计算天数
        $timediff = $endtime - $starttime;
        $days = intval($timediff / 86400);
        //计算小时数
        $remain = $timediff % 86400;
        $hours = intval($remain / 3600);
        //计算分钟数
        $remain = $remain % 3600;
        $mins = intval($remain / 60);
        //计算秒数
        $secs = $remain % 60;
        $res = array("day" => $days, "hour" => $hours, "min" => $mins, "sec" => $secs);
        return $res;
    }

    /**
     * 生成会话列表客服页面地址
     * @param $fromUser
     * @param array $params
     */
    function build_store_im_conversation_url($fromUser, $relation='', $params = [],$isPc = false)
    {
        if (empty($fromUser)) {
            throw new \Exception('参数有误');
        }
        if($isPc){
            $url = cfg('site_url') . '/packapp/customer_service_pc/chat?username=' . $fromUser . '&relation=' . $relation;
        }else{
            $url = cfg('site_url') . '/packapp/customer_service_h5/?from_user=' . $fromUser . '&relation=' . $relation;
        }

        $params['t'] = time();
        $params['s'] = md5($params['t'] . $fromUser . cfg('site_url'));
        
        foreach ($params as $k => $v) {
            $url .= '&' . $k . '=' . $v;
        }
        return $url;
    }

    /**
     * 获取某个月最大天数（最后一天）
     * @param $month
     * @param $year
     * @return int
     */
    function getMonthLastDay($month, $year) {
        switch ($month) {
            case '04' :
            case '06' :
            case '09' :
            case '11' :
                $days = 30;
                break;
            case '02' :
                if ($year % 4 == 0) {
                    if ($year % 100 == 0) {
                        $days = $year % 400 == 0 ? 29 : 28;
                    } else {
                        $days = 29;
                    }
                } else {
                    $days = 28;
                }
                break;
            default :
                $days = 31;
                break;
        }
        return $days;
    }


    //提取富文本中纯文字
    function stringText($string, $num = 30){
        $string = htmlspecialchars_decode($string);//把一些预定义的 HTML 实体转换为字符
        $string = str_replace("&nbsp;","",$string);//将空格替换成空
        $string = strip_tags($string);//函数剥去字符串中的 HTML、XML 以及 PHP 的标签,获取纯文本内容
        return mb_strlen($string,'utf-8') > $num ? mb_substr($string, 0, $num, "utf-8").'...' : mb_substr($string, 0, $num, "utf-8");
    }
/**
 * 获取当前运行域名的顶级域名
 */
function getRunTopDomain($getHost=false){
    $host = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'].($_SERVER['SERVER_PORT']=='80' ? '' : ':'.$_SERVER['SERVER_PORT']));
    $host=strtolower($host);
    if(strpos($host,'/')!==false) {
        $parse = @parse_url($host);
        $host = $parse['host'];
    }
    if($getHost){
        return $host;
    }
    $topleveldomaindb=array('com','cn','cc','cd','beer','center','vip','ceo','cern','cf','ch','channel','chat','cheap','christmas','chrome','church','ci','city','com.au','com.br','com.cn','com.co','com.de','com.ec','com.fr','com.gr','com.gt','com.hk','com.mm','com.mx','com.my','com.ph','com.pl','com.pt','com.ro','com.ru','com.sg','com.tr','com.tw','com.ua','com.ve','community','company','computer','condos','construction','consulting','contractors','cooking','cool','coop','country','courses','cq.cn','credit','creditcard','cricket','cruises','cuisinella','cx','cymru','cz','dad','dance','dating','datsun','day','dclk','de','deals','degree','delivery','democrat','dental','dentist','desi','design','dev','diamonds','diet','digital','direct','directory','discount','dk','dm','dn.ua','docs','domains','doosan','durban','dvag','dz','eat','ec','ecn.br','edu','edu.au','edu.cn','edu.gr','edu.gt','edu.hk','edu.mm','edu.mx','edu.my','edu.pl','edu.pt','edu.rs','edu.sg','edu.tr','edu.za','education','ee','email','emerck','energy','eng.br','engineer','engineering','enterprises','epson','equipment','ernet.in','es','esp.br','esq','estate','etc.br','eti.br','eu','eu.com','eu.lv','eurovision','eus','events','exchange','expert','exposed','fail','fans','farm','fashion','feedback','fi','fin.ec','finance','financial','firm.ro','firmdale','fish','fishing','fit','fitness','flights','florist','flowers','flsmidth','fly','fm','fm.br','fo','foo','football','forsale','fot.br','foundation','fr','frl','frogans','fst.br','fund','furniture','futbol','g12.br','gal','gallery','garden','gb.com','gb.net','gbiz','gd','gd.cn','gdn','geek.nz','gen.nz','gent','gf','gg','ggee','gi','gift','gifts','gives','gl','glass','gle','global','globo','gmail','gmina.pl','gmx','go.id','go.jp','go.kr','go.th','gob.gt','gob.mx','goldpoint','goo','goog','google','gop','gov','gov.br','gov.cn','gov.ec','gov.gr','gov.il','gov.in','gov.mm','gov.mx','gov.my','gov.sg','gov.tr','gov.za','gq','gr','graphics','gratis','green','gripe','gs','gs.cn','gsm.pl','guide','guitars','guru','gv.ac','gv.at','gx.cn','gy','gz.cn','hamburg','hangout','haus','hb.cn','he.cn','healthcare','help','here','hi.cn','hiphop','hiv','hk','hk.cn','hl.cn','hn','hn.cn','holdings','holiday','horse','host','hosting','house','how','hr','ht','hu','hu.com','ibm','id','id.au','ie','ifm','il','im','immo','immobilien','in','in.rs','in.th','ind.br','ind.gt','industries','inf.br','infiniti','info','info.pl','info.ro','info.ve','ing','ink','institute','insure','int','international','investments','io','iq','ir','is','it','iwi.nz','java','jcb','je','jl.cn','jobs','joburg','jor.br','jp','js.cn','juegos','k12.il','k12.tr','kaufen','kddi','ke','kg','kh.ua','ki','kiev.ua','kim','kitchen','kiwi','kiwi.nz','koeln','kr','krd','ky','kyoto','kz','la','lacaixa','land','lat','latrobe','lawyer','lc','lease','leclerc','legal','lel.br','lg.ua','lgbt','li','life','lighting','limited','limo','link','ln.cn','loans','london','lotte','lt','ltd.uk','ltda','lu','luxe','luxury','lv','lviv.ua','ly','ma','madrid','mail.pl','maison','management','mango','maori.nz','market','marketing','md','me','me.uk','med.br','med.ec','media','media.pl','melbourne','meme','memorial','menu','mg','mi.th','miami','miasta.pl','mil','mil.br','mil.ec','mil.gt','mil.id','mil.pl','mil.tr','mil.za','mini','mk','ml','mn','mo','mo.cn','mobi','moda','monash','money','mortgage','moscow','mov','ms','msk.ru','mtpc','mu','muni.il','museum','mx','my','mz','na','name','navy','nc','ne.jp','ne.kr','net','net.au','net.br','net.cn','net.co','net.ec','net.gr','net.gt','net.hk','net.il','net.in','net.mm','net.mx','net.my','net.nz','net.ph','net.pl','net.ru','net.sg','net.th','net.tr','net.tw','net.ua','net.uk','net.ve','net.za','network','new','nexus','nf','ng','ngo','ngo.ph','ngo.za','nico','ninja','nissan','nl','nm.cn','nm.kr','no','no.com','nom.br','nom.co','nom.pl','nom.ro','nom.za','nra','nrw','nt.ro','ntr.br','nu','nx.cn','nz','odo.br','om','one','ong','onl','ooo','or.ac','or.at','or.jp','or.kr','or.th','org','org.au','org.br','org.cn','org.ec','org.gr','org.gt','org.hk','org.il','org.in','org.mm','org.mx','org.my','org.nz','org.ph','org.pl','org.ro','org.rs','org.ru','org.sg','org.tr','org.tw','org.ua','org.uk','org.ve','org.za','organic','ovh','paris','partners','parts','pc.pl','pe','pf','ph','photo','photography','photos','physio','pics','pictures','pink','pizza','pl','place','plc.uk','plumbing','pm','pohl','poker','porn','post','pp.ru','ppg.br','pr','press','presse.fr','priv.pl','pro','pro.br','prod','productions','prof','properties','property','psc.br','psi.br','pt','pub','pw','qa','qc.com','qh.cn','quebec','re','re.kr','realestate.pl','rec.br','rec.ro','recipes','red','rehab','reise','reisen','reit','rel.pl','rentals','repair','report','republican','res.in','rest','restaurant','reviews','rich','rio','rip','ro','rocks','rodeo','rs','rsvp','ru','ruhr','sa','sa.com','saarland','sale','samsung','sarl','saxo','sb','sc','sc.cn','sca','scb','schmidt','school','school.nz','school.za','schule','scot','se','se.com','se.net','services','sexy','sg','sh','sh.cn','shiksha','shoes','shop.pl','si','singles','sk','sklep.pl','sky','slg.br','sm','sn','sn.cn','so','sochi.su','social','software','solar','solutions','sos.pl','soy','space','spb.ru','spiegel','st','store.ro','study','style','su','sucks','supplies','supply','support','surf','surgery','sx','sy','sydney','systems','taipei','targi.pl','tatar','tattoo','tax','tc','technology','tel','tennis','tf','th','tienda','tips','tires','tirol','tj','tj.cn','tk','tl','tm','tm.fr','tm.mc','tm.pl','tm.ro','tm.za','tmp.br','tn','to','today','tools','top','toshiba','tourism.pl','town','toys','training','travel','travel.pl','trust','tt','tui','tur.br','turystyka.pl','tv','tv.br','tw','tw.cn','tz','ua','ug','uk','uk.co','uk.com','uk.net','university','uno','uol','us','us.com','uy','uy.com','uz','vacations','vc','ve','vegas','ventures','versicherung','vet','vet.br','vg','viajes','video','villas','vision','vlaanderen','vodka','vote','voting','voto','voyage','vu','wales','wang','watch','web.ve','web.za','website','wed','wedding','wf','whoswho','wien','wiki','wme','work','works','world','ws','wtc','wtf','www.ro','xj.cn','xxx','xyz','xz.cn','yn.cn','yodobashi','yoga','youtube','yt','za.com','za.net','za.org','zip','zj.cn','zlg.br','zm','zone','zuerich','abogado','ac','ac.ac','ac.at','ac.be','ac.cn','ac.il','ac.in','ac.jp','ac.ke','ac.kr','ac.nz','ac.th','ac.uk','ac.za','academy','accountants','actor','ad','adm.br','adult','adv.br','ae','aero','af','ag','agency','agro.pl','ah.cn','aid.pl','airforce','allfinanz','alsace','alt.za','am','am.br','android','apartments','aquarelle','archi','army','arq.br','art.br','arts.ro','as','asia','asn.au','asso.fr','asso.mc','associates','at','atm.pl','attorney','au','auction','audio','auto.pl','aw','ax','band','bank','bar','barclaycard','barclays','bargains','bayern','bbs.tr','be','berlin','best','bg','bi','bike','bingo','bio','bio.br','biz','biz.pl','bj','bj.cn','black','blackfriday','blue','bmw','bnpparibas','boo','boutique','br','br.com','brussels','budapest','build','builders','business','bw','by','bz','bzh','ca','cab','cal','camera','camp','cancerresearch','canon','capetown','capital','cards','care','career','careers','casa','cash','casino','cat','catering','cl','claims','cleaning','click','clinic','clothing','club','cn.com','cng.br','cnt.br','co','co.ac','co.at','co.il','co.in','co.jp','co.ke','co.kr','co.nz','co.rs','co.th','co.uk','co.ve','co.za','coach','codes','coffee','college','cologne','test','shop','store');
    $str='';

    foreach($topleveldomaindb as $v) {
        $str.=($str ? '|' : '').$v;
    }

    $matchstr="[^\.]+\.(?:(".$str.")|\w{2}|((".$str.")\.\w{2}))$";
    if(preg_match("/".$matchstr."/",$host,$matchs)) {
        $domain=$matchs['0'];
    } else {
        $domain=$host;
    }

    return $domain;
}

/**
 * 运行通过的白名单
 * @param $domin 可以是字符串也可以是域名数组
 */
function MergeWhitDomain($domin='')  {
    $whitDomain = [
        "group.com",
        "pigcms.com"
    ];
    if($domin && is_string($domin)){
        $whitDomain[] = trim($domin);
    }else if(is_array($domin)){
        $whitDomain = array_merge($whitDomain ,$domin);
    }
    return  $whitDomain;
}


/**
 * 判断是否是新安装客户
 * @return bool
 */
function isSoftwareMew() {
    $software_new_version = cfg('software_new_version');
    if ($software_new_version) {
        return true;
    } else {
        return false;
    }
}


/**
 * 抛出自定义异常处理
 *
 * @param string    $msg  异常消息
 * @param integer   $code 异常代码 默认为0
 * @param string    $exception 异常类
 *
 * @throws Exception
 */
function custom_exception($msg, $code = 400,  $exception = '')
{
    $e = $exception ?: CustomException::class;
    throw new $e($msg, $code);
}




/**
 * 封装一个中文可使用的trim
 */
function md_trim($string, $charlist='\\\\s', $ltrim=true, $rtrim=true) {
    $both_ends = $ltrim && $rtrim;

    $char_class_inner = preg_replace(
        array( '/[\^\-\]\\\]/S', '/\\\{4}/S' ),
        array( '\\\\\\0', '\\' ),
        $charlist
    );

    $work_horse = '[' . $char_class_inner . ']+';
    $ltrim && $left_pattern = '^' . $work_horse;
    $rtrim && $right_pattern = $work_horse . '$';

    if($both_ends)
    {
        $pattern_middle = $left_pattern . '|' . $right_pattern;
    }
    elseif($ltrim)
    {
        $pattern_middle = $left_pattern;
    }
    else
    {
        $pattern_middle = $right_pattern;
    }

    return preg_replace("/$pattern_middle/usSD", '', $string);
}

// 解析身份证
function idCardAnalysis($id_card){
    if(is_idcard($id_card) == false){
        return false;
    }
    $date = substr($id_card, 6, 8);
    if (strlen($date) != 8) {
        return false;
    }
    $year  = intval(substr($date, 0, 4));
    $month = intval(substr($date, 4, 2));
    $day   = intval(substr($date, 6, 2));
    // 日期基本格式校验
    if (!checkdate($month, $day, $year)) {
        return false;
    }
    // 日期大于今天
    if ($date > date('Ymd')) {
        return false;
    }
    $res = [
        'birthday' => substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2),
        'age' => date('Y') - $year,
        'sex'  => substr($id_card, (strlen($id_card)==18 ? -2 : -1), 1) % 2 ? '1' : '0',
    ];
    return $res;
}

function get_home_url()
{
    if (cfg('system_type') == 'village') {
        $homeUrl = 'pages/village_menu/index';
    } else {
        $homeUrl = 'pages/plat_menu/index';
    }
    return $homeUrl;
}


/**
 * 文本转语音
 * @param $message  文本内容  例如：您有新订单请及时抢单
 * @param $businessType 业务逻辑  例如：new_deliver_order
 * @return string
 * @date: 2022/06/30
 */
function text2audio($message, $businessType = '')
{
    $apiKey = cfg('text2audio_baidu_api_key');
    $secretKey = cfg('text2audio_baidu_secret_key');
    if (!$apiKey || !$secretKey) {
        return $message;
    }
    $type = cfg('text2audio_baidu_type');
    static $return;
    if (empty($return)) {
        $tokenUrl = "https://openapi.baidu.com/oauth/2.0/token?grant_type=client_credentials&client_id=" . $apiKey . "&client_secret=" . $secretKey;
        $tokenResult = \net\Http::curlGet($tokenUrl);
        fdump_api([$message, $tokenUrl, $tokenResult], 'text2audio', 1);
        $return = json_decode($tokenResult, true);
    }

    if ($return && $return['access_token']) {
        $voiceMp3 = 'http://tsn.baidu.com/text2audio?tex=' . urlencode($message) . '&lan=zh&tok=' . $return['access_token'] . '&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5';
        $type == 1 && $voiceMp3 .= '&per=5118';
    }
    return $voiceMp3 ?? '';
}

//手机号脱敏隐藏展示
function phone_desensitization($phone=''){
    $house_desensitization_tel = cfg('house_desensitization_tel');
    $house_desensitization_tel=!empty($house_desensitization_tel) ? intval($house_desensitization_tel):0;
    if(!empty($phone) && (strpos($phone,'无')!==false || strpos($phone,'--')!==false)){
        return $phone;
    }
    if($house_desensitization_tel==1 && !empty($phone)){
        $len=strlen($phone);
        if($len == 11){
            $phone =substr($phone,0,3).'****'.substr($phone,7,4);
        }else if($len>=6){
            if($len<11){
                $start=($len-3)/2;
                $start=ceil($start);
                $phone = substr_replace($phone,'***',$start,3);
            }else{
                $start=3;
                $length=4;
                $phone = substr_replace($phone,'****',$start,$length);
            }
            
        }
    }
    return $phone;
}

//身份证号脱敏隐藏展示
function idnum_desensitization($idnum=''){    
    $house_desensitization_idnum = cfg('house_desensitization_idnum');
    $house_desensitization_idnum=!empty($house_desensitization_idnum) ? intval($house_desensitization_idnum):0;
    if(!empty($idnum) && (strpos($idnum,'无')!==false || strpos($idnum,'--')!==false)){
        return $idnum;
    }
    if($house_desensitization_idnum==1 && !empty($idnum)){
        $len=strlen($idnum);
        if($len == 18){
            $idnum =substr($idnum,0,8).'********'.substr($idnum,16,2);
        }elseif($len == 15){
            //130503 670401 001
            $idnum =substr($idnum,0,6).'*******'.substr($idnum,13,2);
        }else if($len>15 && $len<=25){
            $idnum = substr_replace($idnum,'********',7,7);
        }
    }
    return $idnum;
}

/**
 * 检查远程文件是否存在，http_code=200则认为是存在
 *
 * @param string $url
 * @return void
 * @author: zt
 * @date: 2023/01/31
 */
function check_remote_file_exists($url, $timeout = 2)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $httpCode == 200;
}
/**
 * 导出CSV
 * @param array $data
 * @param array $headerData
 * @param string $fileName
 */
function exportCsv($data = [], $headerData = [], $fileName = ''){

    $fp = fopen(RUNTIME_PATH.$fileName, 'a');

    $cellNum = count($headerData);
    $dataNum = count($data);

    if (!empty($headerData)) {
        //设置列名称
        $header = [];
        for ($i = 0; $i < $cellNum; $i++) {
            array_push($header, iconv('utf-8', 'gbk', $headerData[$i][1]));
        }
        fputcsv($fp, $header);
    }
    $num = 0;
    //每隔$limit行，刷新一下输出buffer
    $limit = 10000;
    if ($dataNum > 0) {
        for ($i = 0; $i < $dataNum; $i++) {
            $num++;
            //刷新一下输出buffer，防止由于数据过多造成问题
            if ($limit == $num) {
                ob_flush();
                flush();
                $num = 0;
            }
            //赋值
            for ($i = 0; $i < $dataNum; $i++) {
                $lineData = [];
                for ($j = 0; $j < $cellNum; $j++) {
                    array_push($lineData, iconv('utf-8', 'gbk', $data[$i][$headerData[$j][0]]));
                }
                fputcsv($fp, $lineData);
            }
        }
    }
    fclose($fp);
}

/**
 *  foreach (getExcelData($this->request->file('file'),[
 *     'A' => 'name',
 *     'B' => 'type',
 *  ]) as $data){
 *      //$data 即为Excel读取到的数据
 * };
 *
 * @param File $file
 * @param array $keyData
 * @param array $jumpRow
 * @return Generator
 * @throws \PhpOffice\PhpSpreadsheet\Exception
 * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
 */
function getExcelData(File $file, array $keyData,array $jumpRow = [1]) : Generator
{
    // 读取Excel文件
    $worksheet = IOFactory::createReader(ucfirst($file->extension()))
        ->setReadDataOnly(true)
        ->load($file->getRealPath())
        ->getActiveSheet();
    // 遍历Excel表格数据
    $data = [];
    $i = 1;
    $chunkSize = 500;
    foreach ($worksheet->getRowIterator() as $key => $row) {
        if (in_array($key, $jumpRow)) {
            // 默认 第一行为表头，跳过
            continue;
        }
        $rowData = [];
        foreach ($row->getCellIterator() as $k => $cell) {
            isset($keyData[$k]) && $rowData[$keyData[$k]] = $cell->getCalculatedValue();
        }
        $data[] = $rowData;
        if ($i % $chunkSize == 0) {
            yield $data; // 返回 $data 数组
            $data = [];
        }
        $i++;
    }
    // 如果还剩下不足 $chunkSize 条数据没有返回，则在此处最后返回一次
    if (!empty($data)) {
        yield $data;
    }
}
/**
 * 导出到Excel
 * @param $expTitle
 * @param $expCellName
 * @param $expTableData
 * @param string $fileName
 * @return mixed
 * @throws \PhpOffice\PhpSpreadsheet\Exception
 * @throws \think\Exception
 */
function export_excel($expTitle,$expCellName,$expTableData,$fileName='')
{
    $cellNum = count($expCellName);
    $dataNum = count($expTableData);

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->getDefaultColumnDimension()->setWidth(25);

    $cellName = array('A','B', 'C','D', 'E', 'F','G','H','I', 'J', 'K','L','M', 'N', 'O', 'P', 'Q','R','S', 'T','U','V', 'W', 'X','Y', 'Z', 'AA',
        'AB', 'AC','AD','AE', 'AF','AG','AH','AI', 'AJ', 'AK', 'AL','AM','AN','AO','AP','AQ','AR', 'AS', 'AT','AU', 'AV','AW', 'AX',
        'AY', 'AZ');
    $sheet->setTitle($expTitle);
    //设置头部导出时间备注
    $sheet->mergeCells('A1:' . $cellName[$cellNum - 1] . '1');//合并单元格
    $sheet->setCellValue('A1', $expTitle . ' 导出时间:' . date('Y-m-d H:i:s'));
    //设置列名称
    for ($i = 0; $i < $cellNum; $i++) {
        $sheet->setCellValue($cellName[$i] . '2', $expCellName[$i][1]);
    }
    //赋值
    for ($i = 0; $i < $dataNum; $i++) {
        for ($j = 0; $j < $cellNum; $j++) {
            if(isset($expTableData[$i][$expCellName[$j][0]])){
                $expTableData[$i][$expCellName[$j][0]] = str_replace('=', '*', $expTableData[$i][$expCellName[$j][0]]);
            }
            $sheet->setCellValueExplicit(
                $cellName[$j] . ($i + 3), $expTableData[$i][$expCellName[$j][0]] ?? '', DataType::TYPE_STRING
            );
        }
    }
    //下载
    $filename = ($fileName ?: $expTitle) . '.xlsx';
    (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet);

    $ua = request()->server('HTTP_USER_AGENT');
    $ua = strtolower($ua);
    if (preg_match('/msie/', $ua) || preg_match('/edge/', $ua) || preg_match('/trident/', $ua)) { //判断是否为IE或Edge浏览器
        $filename = str_replace('+', '%20', urlencode($filename)); //使用urlencode对文件名进行重新编码
    }

    $returnArr['filename'] = $filename;
    $returnArr['url'] = cfg('site_url') . '/v20/runtime/' . $filename;
    $returnArr['error'] = 0;

    return $returnArr;
}

function debug_sql($nowDebug = 0,&$debug = 0)
{
    empty($debug) && $debug = $nowDebug;
    empty($debug) && $debug = input('_debug');
    $debug && Db::listen(function ($sql, $time)use(&$debug) {
        if($debug){
            // 命令行模式下
            if ('cli' == PHP_SAPI) {
                echo $sql . PHP_EOL . "耗时：[{$time}s]" . PHP_EOL;
                return true;
            }
            echo "<p style='color:red;'>$sql<span style='color:darkseagreen;'><br/>耗时：[{$time}s]</span></p>";

            echo '---------------------------------------------------------------------------------------<br/>';
        }
        return true;
    });
}
/**
 * 签名验证(所有参数除sign外都要参与签名)
 * @param $secret string 秘钥
 * @param $input array 请求参数
 * @return true
 */
function checkSign(string $secret, array $input): bool
{
    if (empty($input['sign'])) {
        throw_exception('sign is required', CustomException::class, 402);
    }
    if (empty($input['timestamp'])) {
        throw_exception('timestamp is required', CustomException::class, 403);
    }
    if (!is_numeric($input['timestamp'])) {
        throw_exception('timestamp must be integer', CustomException::class, 405);
    }
    // 时间戳有效期一小时
    if ($input['timestamp'] + 3600 < time()) {
        throw_exception('Request is expire', CustomException::class, 404);
    }
    // 第一步：如果参数的值为空不参与签名
    $input = array_filter($input, function ($v) {
        return $v !== '';
    }, ARRAY_FILTER_USE_BOTH);

    // 提取出请求的签名后面做比较，不参与签名
    $inputSign = $input['sign'];
    unset($input['sign']);

    // 第二步：按照参数名ASCII字典序排序，并对参数按照key=value的格式如下，如value为数组或json则签名时转为json字符串，得到参数串
    ksort($input);

    // 如value为数组或json则签名时转为json字符串
    foreach ($input as $key => $value) {
        if (is_array($value)) {
            $input[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
    }

    // 按照规则拼接键和值
    $inputStr = http_build_query($input);

    // 第三步：参数尾拼接Secret并进行md5加密
    $signNew = md5($inputStr . $secret);
    // 验签失败
    if ($inputSign !== $signNew) {
        throw_exception('sign error', CustomException::class, 401);
    }

    return true;
}
/**
 * 删除对象中特定的字段方法整合.
 */
function unsetWordParam($data, $keyArr)
{
    if (is_array($keyArr)) {
        foreach ($keyArr as $key) {
            if (isset($data[$key])) {
                unset($data[$key]);
            }
        }
    } else {
        if (isset($data[$keyArr])) {
            unset($data[$keyArr]);
        }
    }
    return $data;
}
    /**
 * 去除字符串中的emoji
 * @author Nd
 * @date 2022/8/3
 * @param $str
 * @return string
 */
function removeEmojiChar($str){
    $mbLen = mb_strlen($str);

    $strArr = [];
    for ($i = 0; $i < $mbLen; $i++) {
        $mbSubstr = mb_substr($str, $i, 1, 'utf-8');
        if (strlen($mbSubstr) >= 4) {
            continue;
        }
        $strArr[] = $mbSubstr;
    }

    return implode('', $strArr);
}

function getRealIp(){
    $forwarded = request()->header("x-forwarded-for");
    if($forwarded){
        $ip = explode(',',$forwarded)[0];
    }else{
        $ip = request()->ip();
    }
    return $ip;
}

function unsetParam($data, $keys)
{
    if (is_array($keys)) {
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                unset($data[$key]);
            }
        }
    } else {
        if (isset($data[$keys])) {
            unset($data[$keys]);
        }
    }
    return $data;
 }
/*根据店铺数据表的图片字段来得到图片*/
function get_allImage_by_path($path, $dir = 'store'){
    if(!empty($path)){
        $tmp_pic_arr = explode(';',$path);
        foreach($tmp_pic_arr as $key=>$value){
            if('http' === substr($value,0,4)){
                $return[$key] = $value;
            }elseif(stripos($value, ',')){
                $tmp_pic = explode(',', $value);
                $return[$key] = file_domain().'/upload/'.$dir.'/'.$tmp_pic[0].'/'.$tmp_pic[1];
            }else{
                $return[$key] = file_domain().$value;
            }
        }
        return $return;
    }else{
        return false;
    }

}

/**
 * 指定的经纬是否在多边形的地图内
 * @param float $lng
 * @param float $lat
 * @param array $latLngData
 * @return boolean
 */
function isPtInPolyV20($lng, $lat, $latLngData)
{
    foreach ($latLngData as $latLng) {
        $iCount = count($latLng);
        if ($iCount < 3) continue;
        $iSum = 0;
        for ($i = 0; $i < $iCount; $i++) {
            if ($i == $iCount - 1) {
                $dLon1 = $latLng[$i]['lng'];
                $dLat1 = $latLng[$i]['lat'];
                $dLon2 = $latLng[0]['lng'];
                $dLat2 = $latLng[0]['lat'];
            } else {
                $dLon1 = $latLng[$i]['lng'];
                $dLat1 = $latLng[$i]['lat'];
                $dLon2 = $latLng[$i + 1]['lng'];
                $dLat2 = $latLng[$i + 1]['lat'];
            }
            //以下语句判断A点是否在边的两端点的水平平行线之间，在则可能有交点，开始判断交点是否在左射线上
            if ((($lat >= $dLat1) && ($lat < $dLat2)) || (($lat >= $dLat2) && ($lat < $dLat1))) {
                if (abs($dLat1 - $dLat2) > 0) {
                    //得到 A点向左射线与边的交点的x坐标：
                    $dLon = $dLon1 - (($dLon1 - $dLon2) * ($dLat1 - $lat)) / ($dLat1 - $dLat2);
                    if ($dLon < $lng) $iSum ++;
                }
            }
        }
        if ($iSum % 2 != 0) return true;
    }
    return false;
}

/**
 * 替换目录下文件字符串
 *
 * @param string $source
 * @param string $oldString
 * @param string $string
 * @return void
 * @author: zt
 * @date: 2024/04/19
 */
function edit_dir_file($source, $oldString, $string)
{
    //判断目录是否存在
    if (!is_dir($source)) return;

    $handle = opendir($source);
    while (($item = readdir($handle)) !== false) {
        if ($item == '.' || $item == '..') continue;
        $_source = $source . DIRECTORY_SEPARATOR . $item;
        if (is_file($_source) && is_writable($_source)) {
            $txt = file_get_contents($_source);
            $txt = str_replace($oldString, $string, $txt);
            file_put_contents($_source, $txt);
        };
        if (is_dir($_source)) edit_dir_file($_source, $oldString, $string);
    }
    closedir($handle);
}

/**
 * 验证密码是否为8~16位且包含字母、数字和特殊符号
 * @param $str
 */
function verifyPassword($str){
    $code = 0;
    $msg = '';
    $regex = '/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[!@#$%^&*?()_+])[A-Za-z\d@$!#%*?&]{8,16}$/';
    if (!preg_match($regex, $str)) {
        $msg = L_('密码必须是8~16位且包含字母、数字和特殊符号(@、$、!、#、%、*、?、&)！');
        $code = 1;
    }
    return [
        'code' => $code,
        'msg' => $msg
    ];
}

/**
 * 根据excel地址获取数据
 *  foreach (getExcelDataForUrl($file_url,[
 *     'A' => 'name',
 *     'B' => 'type',
 *  ]) as $data){
 *      //$data 即为Excel读取到的数据
 * };
 * @param $file_url
 * @param array $keyData
 * @param array|int[] $jumpRow
 * @return Generator
 * @throws \PhpOffice\PhpSpreadsheet\Exception
 * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
 */
function getExcelDataForUrl(string $file_url, array $keyData,array $jumpRow = [1]) : Generator
{
    $data = [];
    $file_arr = explode('.',$file_url);
    $file_type = '';
    if($file_arr) {
        $file_type = $file_arr[count($file_arr)-1];
    }
    if(!in_array($file_type,['xlsx','xls'])){
        yield $data;
    }
    if($file_type=='xlsx'){
        $read = IOFactory::createReader('Xlsx');
    }elseif($file_type=='xls'){
        $read = IOFactory::createReader('Xls');
    }
    // 读取Excel文件
    if(file_exists($_SERVER['DOCUMENT_ROOT'].$file_url)){
        $file_url = $_SERVER['DOCUMENT_ROOT'].$file_url;
    }else{
        $file_url = replace_file_domain($file_url);
    }
    $worksheet = $read->setReadDataOnly(true)
        ->load($file_url)->getActiveSheet();

    // 遍历Excel表格数据
    $i = 1;
    $chunkSize = 500;
    foreach ($worksheet->getRowIterator() as $key => $row) {
        if (in_array($key, $jumpRow)) {
            // 默认 第一行为表头，跳过
            continue;
        }
        $rowData = [];
        foreach ($row->getCellIterator() as $k => $cell) {
            isset($keyData[$k]) && $rowData[$keyData[$k]] = $cell->getCalculatedValue();
        }
        $data[] = $rowData;
        if ($i % $chunkSize == 0) {
            yield $data; // 返回 $data 数组
            $data = [];
        }
        $i++;
    }
    // 如果还剩下不足 $chunkSize 条数据没有返回，则在此处最后返回一次
    if (!empty($data)) {
        yield $data;
    }
}
