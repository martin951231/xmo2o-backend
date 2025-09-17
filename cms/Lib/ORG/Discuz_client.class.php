<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 14-5-15
 * Time: 下午3:23
 */
class Discuz_client{
	public function __construct(){
        require_cache(__DIR__ . '/uc_client/config.php'); //加载配置信息
        define('UC_CLIENT_PATH', __DIR__. '/uc_client');
        if(!defined('UC_API')) {
           die('未发现config配置文件');
        }
        require_cache(__DIR__ . '/uc_client/client.php'); //加载配置信息
	}



	public function synchro($username,$password){
        list($uid, $username, $email) = uc_get_user($username);
        if($uid){
            //登陆
//            $this->login_($uid,$username,$password,$email);
          return  $this->login($username,$password);
        }else{
            //注册

        }
    }

	public function login_($uid,$username,$password,$email){
        $authPre='zqYC_2132_';
        setcookie($authPre . 'auth', '', 0);

        setcookie($authPre . 'auth', uc_authcode($uid . "\t" . $username . "\t" . md5($password) . "\t" . $email, 'ENCODE'));

        return array(
            'uid' => $uid ,
            'username' => $username ,
            'password' => $password ,
            'email'  => $email ,
            'synlogin' => uc_user_synlogin($uid),
        );
    }

    public function reg_($username,$password,$email='123456@qq.com'){
        $ip = get_client_ip();
        return uc_user_register($username, md5($password), $email, '', '',$ip);
    }



    /**
     * UC加密与解密
     * @param string $string 提供需要加密的字符串
     * @param string $operation 加密方式，ENCODE是加密，DECODE是解密
     * @param string $key 密钥，在整合程序时填写的密钥
     * @param int $expiry
     * @return string
     */
    function _uc_authcode($string, $operation = 'DECODE', $key = UC_KEY, $expiry = 0) {
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
    public function register($username, $password, $email){
        $uid = uc_user_register($username, $password, $email);//UCenter的注册验证函数
        if($uid <= 0) {
            if($uid == -1) {
                $return = array('type' => 'error', 'msg' => UCMSG_REGISTER_CODE1);
            } elseif($uid == -2) {
                $return = array('type' => 'error', 'msg' => UCMSG_REGISTER_CODE2);
            } elseif($uid == -3) {
                $return = array('type' => 'error', 'msg' => UCMSG_REGISTER_CODE3);
            } elseif($uid == -4) {
                $return = array('type' => 'error', 'msg' => UCMSG_REGISTER_CODE4);
            } elseif($uid == -5) {
                $return = array('type' => 'error', 'msg' => UCMSG_REGISTER_CODE5);
            } elseif($uid == -6) {
                $return = array('type' => 'error', 'msg' => UCMSG_REGISTER_CODE6);
            } else {
                $return = array('type' => 'error', 'msg' => UCMSG_REGISTER_CODE7);
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
        if(IS_AJAX){
            return $this->_getJson($return);
        }else{
            return $return;
        }
    }

    public function login($username, $password){
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

    public function logout(){
        unset($_SESSION[COOKIE_PREFIX.'auth']);
        if(IS_AJAX){
            $match = $this->_synMatch(uc_user_synlogout());
            return $this->_getJson(array("type" => "success", "msg" => UCMSG_LOGOUT_SUCCESS, "syn" => $match));
        }else{
            return uc_user_synlogout();
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

    public function uc_synlogin($uid){
        return uc_user_synlogin($uid);
    }

    public function uc_useredit($username, $oldpw, $newpw='', $email=''){
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

    public function check_login(){
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