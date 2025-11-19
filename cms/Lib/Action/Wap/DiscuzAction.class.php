<?php


class DiscuzAction extends BaseAction{


    const UC_CLIENT_VERSION = '1.6.0';
    const UC_CLIENT_RELEASE = '20110501';

    const API_DELETEUSER = 1; // note 用户删除 API 接口开关
    const API_RENAMEUSER = 1; // note 用户改名 API 接口开关
    const API_GETTAG = 1; // note 获取标签 API 接口开关
    const API_SYNLOGIN = 1; // note 同步登录 API 接口开关
    const API_SYNLOGOUT = 1; // note 同步登出 API 接口开关
    const API_UPDATEPW = 1; // note 更改用户密码 开关
    const API_UPDATEBADWORDS = 1; // note 更新关键字列表 开关
    const API_UPDATEHOSTS = 1; // note 更新域名解析缓存 开关
    const API_UPDATEAPPS = 1; // note 更新应用列表 开关
    const API_UPDATECLIENT = 1; // note 更新客户端缓存 开关
    const API_UPDATECREDIT = 1; // note 更新用户积分 开关
    const API_GETCREDITSETTINGS = 1; // note 向 UCenter 提供积分设置 开关
    const API_GETCREDIT = 1; // note 获取用户的某项积分 开关
    const API_UPDATECREDITSETTINGS = 1; // note 更新应用积分设置 开关
    const API_ADDFEED = 1; // note FEED推送 开关; UCenter HOME专用，要用这个功能，请在UCENTER后台把应用类型改为UCenter HOME

    const API_RETURN_SUCCEED = '1';
    const API_RETURN_FAILED = '-1';
    const API_RETURN_FORBIDDEN = '-2';


    //todo 初始化
    public function __construct(){
        parent::__construct();
        require_cache(APP_PATH.'Lib/ORG/uc_client/config.php'); //加载配置信息
        define('UC_CLIENT_PATH', APP_PATH.'Lib/ORG/uc_client');
        if(!defined('UC_API')) {
            die('未发现config配置文件');
        }
        require_cache(APP_PATH . '/Lib/ORG/uc_client/client.php'); //加载配置信息
    }


    //todo 解析请求
    public function index(){
        $code=$_GET['/api/uc_php?code'];
        fdump_api(['success--获取code------'.$code],'discuz/data_log',1);
        if(empty($code)){
            return false;
        }

        fdump_api(['解析请求获取code------'.$code],'discuz/aa_log',1);

        $get = $post = array();
        parse_str($this->_uc_authcode($code), $get);
        if(!empty($get)){
            $xml = file_get_contents('php://input');
            if ($xml) {
                Log::record('XML: ' . $xml);
                $post = xml_unserialize($xml);
            }
            $action=$get['action'];
            fdump_api(['success--请求完成',$code,$get,$post],'discuz/data_log',1);
            if (method_exists($this, $action)) {
                fdump_api(['解析获取参数------',$get],'discuz/aa_log',1);
                exit(call_user_func([$this, $action], $get));
            } else {
                fdump_api(['error--方法未定义',$code,$get,$post],'discuz/data_log',1);
                exit($action . '方法未定义');
            }
        }
    }

    function dstrlen($str) {
        if(strtolower(UC_CHARSET) != 'utf-8') {
            return strlen($str);
        }
        $count = 0;
        for($i = 0; $i < strlen($str); $i++){
            $value = ord($str[$i]);
            if($value > 127) {
                $count++;
                if($value >= 192 && $value <= 223) $i++;
                elseif($value >= 224 && $value <= 239) $i = $i + 2;
                elseif($value >= 240 && $value <= 247) $i = $i + 3;
            }
            $count++;
        }
        return $count;
    }
	
	public function create_url()
    {
        if (IS_POST) {
            $qr_path = $this->config['site_url'] . '/wap.php?c=Discuz&a=main&village_id=' . $_POST['village_id'] . '&url=' . urlencode($_SERVER['original_post']['url']);
            echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="utf-8" /><title>生成链接</title></head><body>论坛新链接：<span style="color:red;">' . $qr_path . '</span><br/><br/><a href=" ">重新填写</a>
			</body></html>';
        } else {
            echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="utf-8" /><title>生成链接</title></head><body><form method="post"><input name="village_id" placeholder="小区ID" style="width:800px;height:30px;line-height:30px;"/><br/><br/><input name="url" placeholder="论坛的网址" style="width:800px;height:30px;line-height:30px;"/><br/><br/><input type="submit" value="生成"/></form></body></html>';
        }
    }

    //todo 小区跳转登陆、注册社区
    public function main(){
        if(empty($this->user_session)){
            $this->error_tips('请先进行登录',U('Login/index'));
        }
        fdump_api(['登录用户信息'.__LINE__,$this->user_session],'discuz/main_log',1);
        $bind_user = D('User')->get_user($this->user_session['uid']);
        $type=intval($_GET['type']);
        if(empty($type)){
            $type = 1;
//            $this->error_tips('缺少参数！<br/>将跳转首页',U('Home/index'));
        }
        if(!$bind_user){
            $this->error_tips('用户不存在！<br/>将跳转首页',U('Home/index'));
        }
        if (!trim($bind_user['phone'])) {
            $this->error_tips('请先绑定手机号',U('My/bind_user',array('referer'=>urlencode(U('Discuz/main',$_GET)))));
        }
        $bind_user['phone'] = trim($bind_user['phone']);
        $db_user_set = M('User_set');
        $where_set = [
            'uid' => $this->user_session['uid']
        ];
        $set_info =  $db_user_set->where($where_set)->find();
        if ($set_info && trim($set_info['discuzx_name']) && trim($set_info['discuzx_password'])) {
            $username = trim($set_info['discuzx_name']);
            $password = trim($set_info['discuzx_password']);
        } else {
            if (strpos($bind_user['nickname'],'*') !== false) {
                $nickname = '用户'.$bind_user['uid'];
            } else {
                $nickname = $bind_user['nickname'];
            }
            $username = $nickname;
            $password='Discuzx@'.$bind_user['phone'];// 密码恒定是 Discuzx@+手机号
            $username = $this->replaceWord($username);
            $username = $this->filterEmoji2($username);
            $username = addslashes($username);
            if(preg_match("/^\d*$/",$username)){
                $username = '用户'.$username;
            }
            $len = $this->dstrlen($username);
            fdump_api(['昵称参数'.__LINE__,$username,$len],'discuz/main_log',1);
            if(($len > 50 || $len < 3) || (iconv_strlen($username) >= 25 || iconv_strlen($username) <= 2) ){
                $username='用户'.$bind_user['uid'];
            }
        }
//        $username='0619_9';
//        $password='xiaozhu888';
        fdump_api(['论坛参数'.__LINE__,$bind_user['uid'],$bind_user['phone'],$_POST,$username, $password,$set_info,$bind_user],'discuz/main_log',1);
        list($uid, $username_, $email) = uc_get_user($username);
        fdump_api(['论坛参数获取返回'.__LINE__,$username, $password, $uid, $username_, $email],'discuz/main_log',1);
        if($uid){
            //登陆
            $msg = $this->login($username_, $password);
            fdump_api(['论坛参数登录'.__LINE__,$username, $password, $msg],'discuz/main_log',1);
            if($msg !== true){
                $this->error_tips($msg.'<br/>将跳转首页',U('Home/index'));
            }
            if (empty($set_info) || !$set_info['discuzx_name'] || !$set_info['discuzx_password'] || !$set_info['discuzx_email'] || !$set_info['discuzx_uid']) {
                $save_data = [
                    'discuzx_name' => $username,
                    'discuzx_password' => $password,
                    'discuzx_email' => $email,
                    'discuzx_uid' => $uid,
                    'discuzx_time' => time()
                ];
                if (empty($set_info)) {
                    $save_data['uid'] = $this->user_session['uid'];
                    if (trim($bind_user['phone'])) {
                        $save_data['phone'] = $bind_user['phone'];
                    }
                    $db_user_set->data($save_data)->add($save_data);
                } else {
                    $db_user_set->where($where_set)->data($save_data)->save();
                }
            }
        }
        else{
            //注册
            $rand=rand(100,999);
            $email= $bind_user['phone'].$bind_user['uid'].$rand.'@qq.com';// 邮箱为恒定结构 手机号+'@qq.com'
            fdump_api(['注册参数'.__LINE__,$username, $password, $email],'discuz/main_log',1);
            $msg = $this->register($username, $password, $email);
            fdump_api(['论坛参数注册返回'.__LINE__,$msg],'discuz/main_log',1);
            if ($msg) {
                $save_data = [
                    'discuzx_name' => $username,
                    'discuzx_password' => $password,
                    'discuzx_email' => $email,
                    'discuzx_time' => time()
                ];
                list($reg_uid, $reg_username, $reg_email) = uc_get_user($username);
                if($reg_uid){
                    $save_data['discuzx_uid']=$reg_uid;
                }
                fdump_api(['论坛注册参数'.__LINE__,$username,$reg_uid, $reg_username, $reg_email,$save_data],'discuz/main_log',1);
                if (empty($set_info)) {
                    $save_data['uid'] = $this->user_session['uid'];
                    if (trim($bind_user['phone'])) {
                        $save_data['phone'] = $bind_user['phone'];
                    }
                    $db_user_set->data($save_data)->add($save_data);
                } else {
                    $db_user_set->where($where_set)->data($save_data)->save();
                }
            } else {
                fdump_api(['论坛参数注册返回'.__LINE__,$msg, $username, $password, $email, $set_info,$bind_user],'discuz/main_err_log',1);
            }
        }
		
		//若传递了URL参数，则直接跳URL
		if($_GET['url']){
			$_SERVER['original_get']['url'] = htmlspecialchars_decode(htmlspecialchars_decode(urldecode($_SERVER['original_get']['url'])));
			$url = $_SERVER['original_get']['url'];
		}else{
			$discuzxUrl = $this->config['discuzx_url'];

			switch ($type) {
				case 1:
					// 跳转 论坛首页
					$url=$discuzxUrl.'/forum.php?mod=guide&view=hot&mobile=2';
					break;
				case 2:
					//跳转 论坛列表
					$url=$discuzxUrl.'/forum.php?mod=forumdisplay&fid=43&page=1';
					break;
				case 3:
					//跳转 单篇贴子
					$url=$discuzxUrl.'/forum.php?mod=viewthread&tid=559&extra';
					break;
				default:
					//跳转 论坛首页
					$url=$discuzxUrl.'/forum.php?mod=guide&view=hot&mobile=2';
			}
        }

		if(isset($_GET['forum_url']) && !empty($_GET['forum_url'])){
            $url = htmlspecialchars_decode(htmlspecialchars_decode(urldecode($_GET['forum_url'])));
        }
        fdump_api(['跳转链接'.__LINE__,$url],'discuz/main_log',1);
        header("Refresh:0;url={$url}");
        exit();
    }

    //todo 修改用户名
    public function renameuser($data){
        fdump_api(['进来修改用户名------',$data],'discuz/aa_log',1);
        fdump_api($data,'discuz/renameuser',1);
        if((isset($data['uid']) && !empty($data['uid'])) && (isset($data['newusername']) && !empty($data['newusername']))){
            M('User_set')->where(['discuzx_uid'=>$data['uid']])->data([
                'discuzx_name'=>$data['newusername']
            ])->save();
            fdump_api(['修改用户名传参--'.__LINE__,$data],'discuz/renameuser',1);
        }
    }

    //todo 修改密码
    public function updatepw($data){
        fdump_api(['进来修改密码------',$data],'discuz/aa_log',1);
        fdump_api($data,'discuz/updatepw',1);
        if((isset($data['uid']) && !empty($data['uid'])) && (isset($data['password']) && !empty($data['password']))){
            M('User_set')->where(['discuzx_uid'=>$data['uid']])->data([
                'discuzx_password'=>$data['password']
            ])->save();
            fdump_api(['修改密码传参--'.__LINE__,$data],'discuz/updatepw',1);
        }
    }

    function filterEmoji2($str){
        if(empty($str)) return null;
        $str = preg_replace_callback(
            '/[\xf0-\xf7].{3}/',
            function($r){
                return '@E' . base64_encode($r[0]);
            },$str);
        $countt=substr_count($str,"@");
        for ($i=0; $i < $countt; $i++) {
            $c = stripos($str,"@");
            $str=substr($str,0,$c).substr($str,$c+10,strlen($str)-1);
        }
        $str = preg_replace_callback(
            '/@E(.{6}==)/',
            function($r){
                return base64_decode($r[1]);
            }, $str);
        return $str;
    }
    //todo 过滤
    public function replaceWord($word) {
        $pregTitleSave = "/[\x{4e00}-\x{9fa5}\·\.\",\?|A-Za-z\.\。\d？！!(（)）：:+-{}\[\]【】《》<>-_· “”：，]+/iu";
        $word1 = trim($word);
        preg_match_all($pregTitleSave,$word1,$titleArray);
        $newWord = implode('',$titleArray[0]);
//        $pattern = "/[`~!@#$%^&*()=|{}':;',\\.<>《》\/?~！@#￥……&*（）—|{}【】‘；：”“'。，、？丶]/u";
//        $txt = trim($newWord,$pattern);
        $search = array(
            '/', '\/','\\', '`', '~', '!', '@','#',
            '$','%','^','&','*','(',')',
            '=','|','{','}',':',';',',','[',']',
            '.','<','>','《','》',' ',
            '?','~','！','#','￥',
            '…','…','&','*','（','）',
            '—','|','{','}','【','】','·',
            '‘','‘’','；','：','”','“',"'",
            '。','，','、','？','丶','+','-','_','='
        );
        $txt = str_replace($search,'', $newWord);
        return $txt;
    }

    //todo 退出
    public function logout(){
        unset($_SESSION[COOKIE_PREFIX.'auth']);
        if(IS_AJAX){
            $match = $this->_synMatch(uc_user_synlogout());
            return $this->_getJson(array("type" => "success", "msg" => UCMSG_LOGOUT_SUCCESS, "syn" => $match));
        }else{
            return uc_user_synlogout();
        }
    }


    /**
     * 此接口供仅测试连接。当 UCenter 发起 test 的接口请求时，如果成功获取到接口返回的 API_RETURN_SUCCEED 值，表示 UCenter 和应用通讯正常
     * @param $get
     * @param $post
     * @return string
     */
    protected function test()
    {
        return self::API_RETURN_SUCCEED;
    }

    protected function updateapps($get, $post)
    {
        if (!self::API_UPDATEAPPS) {
            return self::API_RETURN_FORBIDDEN;
        }
//        @unlink(UC_CLIENT_PATH . '/data/cache/apps.php');

        //uc 写 app 缓存文件
        $cache_file = UC_CLIENT_PATH . '/data/cache/apps.php';
        $s = "<?php\r\n";
        $s .= '$_CACHE[\'apps\'] = ' . var_export($post, TRUE) . ";\r\n";
        file_put_contents($cache_file, $s);

        return self::API_RETURN_SUCCEED;
    }


    /**
     * UC加密与解密
     * @param string $string 提供需要加密的字符串
     * @param string $operation 加密方式，ENCODE是加密，DECODE是解密
     * @param string $key 密钥，在整合程序时填写的密钥
     * @param int $expiry
     * @return string
     */
    protected function _uc_authcode($string, $operation = 'DECODE', $key = UC_KEY, $expiry = 0) {
        $ckey_length = 4;
        $key = md5($key ? $key : UC_KEY);
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

        $cryptkey = $keya.md5($keya.$keyc);
        $key_length = strlen($cryptkey);

        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
        $string_length = strlen($string);

        $result = '';
        $box = range(0, 255);

        $rndkey = array();
        for($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        for($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        if($operation == 'DECODE') {
            if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc.str_replace('=', '', base64_encode($result));
        }
    }


    /**
     * 会员注册
     */
    protected function register($username, $password, $email){
        $uid = uc_user_register($username, $password, $email);//UCenter的注册验证函数
        if($uid <= 0) {
            if($uid == -1) {
                $this->error_tips(UCMSG_REGISTER_CODE1.'<br/>将跳转首页',U('Home/index'));
            } elseif($uid == -2) {
                $this->error_tips(UCMSG_REGISTER_CODE2.'<br/>将跳转首页',U('Home/index'));
            } elseif($uid == -3) {
                $this->error_tips(UCMSG_REGISTER_CODE3.'<br/>将跳转首页',U('Home/index'));
            } elseif($uid == -4) {
                $this->error_tips(UCMSG_REGISTER_CODE4.'<br/>将跳转首页',U('Home/index'));
            } elseif($uid == -5) {
                $this->error_tips(UCMSG_REGISTER_CODE5.'<br/>将跳转首页',U('Home/index'));
            } elseif($uid == -6) {
                $this->error_tips(UCMSG_REGISTER_CODE6.'<br/>将跳转首页',U('Home/index'));
            } else {
                $this->error_tips(UCMSG_REGISTER_CODE7.'<br/>将跳转首页',U('Home/index'));
            }
        } else {
            if(IS_REGISTER_ACTIVE){
                $ucmember = D('UcenterMembers');
                $member = D('CommonMember');
                $status = D('CommonMemberStatus');
                $profile = D('CommonMemberProfile');
                $forum = D('CommonMemberFieldForum');
                $home = D('CommonMemberFieldHome');
                $count = D('CommonMemberCount');
                $pswd = $ucmember->where('uid='.$uid)->getField('password');
                $ip = $_SERVER['REMOTE_ADDR'];
                $time = time();
                $userdata = array(
                    'uid'=>$uid,
                    'username'=>$username,
                    'password'=>$pswd,
                    'email'=>$email,
                    'adminid'=>0,
                    'groupid'=>10,
                    'regdate'=>$time,
                    'credits'=>0,
                    'timeoffset'=>9999
                );
                $status_data = array(
                    'uid' => $uid,
                    'regip' => $ip,
                    'lastip' => $ip,
                    'lastvisit' => $time,
                    'lastactivity' => $time,
                    'lastpost' => 0,
                    'lastsendmail' => 0
                );
                $member->add($userdata);
                $status->add($status_data);
                $forum->add(array('uid'=>$uid));
                $profile->add(array('uid'=>$uid));
                $home->add(array('uid'=>$uid));
                $count->add(array('uid'=>$uid));
            }
            if(IS_REGISTER_LOGIN)
                $return = $this->login($username, $password);
            else
                $return = array('type' => 'success', 'uid' => intval($uid));
        }
//        if(IS_AJAX){
//            return $this->_getJson($return);
//        }else{
        return $return;
//        }
    }

    protected function login($username, $password){
        $dz = $this->uc_login($username, $password);
        if(is_array($dz)){
            $_SESSION[COOKIE_PREFIX.'auth'] = $this->_getAuthcode($dz['uid']);
            if(IS_AJAX){
                return $this->_getJson(array(
                    "type" => "success",
                    "msg" => UCMSG_LOGIN_SUCCESS,
                    "auth" => true,
                    "syn" => $this->_synMatch(uc_user_synlogin($dz['uid']))
                ));
            }else{
                return uc_user_synlogin($dz['uid']);
            }
        }else{
            if(IS_AJAX){
                return $this->_getJson(array("type" => "error", "msg" => $dz));
            }else{
                return $dz;
            }
        }
    }

    protected function uc_login($username, $password){
        list($uid, $username, $password, $email) = uc_user_login($username, $password);
        if($uid > 0) {
            return array('uid' => $uid,
                'username' => $username,
                'password' => $password,
                'email' => $email);
        } elseif($uid == -1) {
            return  UCMSG_LOGIN_CODE1;
        } elseif($uid == -2) {
            return UCMSG_LOGIN_CODE2;
        } elseif($uid == -3) {
            return UCMSG_LOGIN_CODE3;
        } else {
            return UCMSG_LOGIN_CODE4;
        }
    }

    protected function uc_synlogin($uid){
        return uc_user_synlogin($uid);
    }

    protected function uc_useredit($username, $oldpw, $newpw='', $email=''){
        $ucresult = uc_user_edit($username, $oldpw, $newpw, $email);
        switch($ucresult){
            case '1':
                $return = UCMSG_USEREDIT_SUCCESS;
                break;
            case '0':
                $return = UCMSG_USEREDIT_NOTHING;
                break;
            case '-1':
                $return = UCMSG_USEREDIT_CODE1;
                break;
            case '-4':
                $return = UCMSG_USEREDIT_CODE4;
                break;
            case '-5':
                $return = UCMSG_USEREDIT_CODE5;
                break;
            case '-6':
                $return = UCMSG_USEREDIT_CODE6;
                break;
            case '-7':
                $return = UCMSG_USEREDIT_CODE7;
                break;
            case '-8':
                $return = UCMSG_USEREDIT_CODE8;
                break;
        }
        return $return;
    }

    protected function check_login(){
        if(!empty($_COOKIE[COOKIE_PREFIX.'auth']) && empty($_SESSION[COOKIE_PREFIX.'auth'])){
            list($uid, $username) = explode("<|>", uc_authcode($_COOKIE[COOKIE_PREFIX.'auth'], 'DECODE'));
            $_SESSION[COOKIE_PREFIX.'auth'] = $this->_getAuthcode($uid);
        }
    }

    private function _synMatch($str){
        $match = str_replace('" reload="1"></script><script type="text/javascript" src="', '|', $str);
        $match = str_replace('<script type="text/javascript" src="', '', $match);
        $match = str_replace('" reload="1"></script>', '', $match);
        return $match;
    }

    private function _getAuthcode($uid){
        $data = D('MemberView');
        $return = $data->where('CommonMember.uid='.$uid)->find();
        $data = M('CommonSetting');
        $credits = $data->where("skey = 'extcredits'")->getField('svalue');
        foreach(unserialize($credits) AS $k => $v){
            if($v['available']) $return['extcred']['extcredits'.$k] = $v['title'];
            else unset($return['extcredits'.$k]);
        }
        return $return;
    }

    private function _getJson($array){
        $this->_arrayRecursive($array, 'urlencode', true);
        return urldecode(json_encode($array));
    }

    private function _arrayRecursive(&$array, $function, $apply_to_keys_also = false){
        static $recursive_counter = 0;
        if (++$recursive_counter > 1000) {
            die('possible deep recursion attack');
        }
        foreach($array as $key => $value){
            if(is_array($value)){
                $this->arrayRecursive($array[$key], $function, $apply_to_keys_also);
            }else{
                $array[$key] = $function($value);
            }
            if($apply_to_keys_also && is_string($key)){
                $new_key = $function($key);
                if ($new_key != $key) {
                    $array[$new_key] = $array[$key];
                    unset($array[$key]);
                }
            }
        }
        $recursive_counter--;
    }









}

