<?php

class Wxapp_paotuiAction extends BaseAction
{
    public function code_login()
    {
        $appid = $this->config['pay_wxapp_paotui_appid'];
        $appsecret = $this->config['pay_wxapp_paotui_appsecret'];

        import('ORG.Net.Http');
        $http = new Http();

        $return = Http::curlPost('https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$appsecret.'&js_code='.$_POST['code'].'&grant_type=authorization_code', array());

        if($return['errcode']){
            $this->returnCodeError($return['errmsg']);
        }

        /*优先使用 unionid 登录*/
        if(!empty($return['unionid'])){
            $now_user = $this->autologin('union_id',$return['unionid'],$return['openid']);
        }else{
            /*再次使用 openid 登录*/
            $now_user = $this->autologin('paotui_openid',$return['openid']);
        }
        if(!empty($now_user)){
            $session_key = $return['session_key'];
            $data_user = array(
                'last_weixin_time' => time(),
                'wxapp_session_key' => $session_key,
            );
            D('User')->where(array('uid'=>$now_user['uid']))->data($data_user)->save();

            $ticket = ticket::create($now_user['uid'],$this->DEVICE_ID, true);
            $return = array(
                'ticket'=>	$ticket['ticket'],
                'v20_ticket' => D("User")->getV20Ticket($now_user['uid']),
                'user'	=>	$now_user,
                'emptyUser'	=>	false,
            );
        }else{
            $return = array(
                'emptyUser' => true,
                'wx_openid' => $return['openid'],//做餐饮2.0用户登录的时候，需要拿到openid，因为他们是做的免登录
            );
        }
        $this->returnCode(0,$return);


    }

    public function wxapp_login()
    {
        if ($_POST['ticket']) {
            $info = ticket::get($_POST['ticket'], $this->DEVICE_ID, true);
            if ($info && $info['uid']) {
                $now_user = $this->autologin('uid', $info['uid']);
                if (!empty($now_user)) {
                    $return = array(
                        'ticket' => $_POST['ticket'],
                        'v20_ticket' => D("User")->getV20Ticket($info['uid']),
                        'user' => $now_user,
                    );
                    $this->returnCode(0, $return);
                } else {
                    $this->returnCode(0, array('emptyUser' => true));
                }
            } else {
                $this->returnCode(0, array('emptyUser' => true));
            }
        }

        $appid = $this->config['pay_wxapp_paotui_appid'];
        $appsecret = $this->config['pay_wxapp_paotui_appsecret'];

        if (!function_exists('mcrypt_module_open')) {
            $this->returnCodeError('mcrypt_module_open PHP模块没有打开，请先打开。');
        }

        import('ORG.Net.Http');
        $http = new Http();

        $return = Http::curlPost('https://api.weixin.qq.com/sns/jscode2session?appid=' . $appid . '&secret=' . $appsecret . '&js_code=' . $_POST['code'] . '&grant_type=authorization_code', array());

        if ($return['errcode']) {
            $this->returnCodeError($return['errmsg']);
        }

        import('@.ORG.aeswxapp.wxBizDataCrypt');

        $session_key = $return['session_key'];
        $pc = new WXBizDataCrypt($appid, $session_key);
        $errCode = $pc->decryptData($_POST['encryptedData'], $_POST['iv'], $data);
        $jsonrt = json_decode($data, true);
        /*优先使用 unionId 登录*/
        if (!empty($jsonrt['unionId'])) {
            $now_user = $this->autologin('union_id', $jsonrt['unionId'], $jsonrt['openId']);
        } else {
            /*再次使用 openId 登录*/
            $now_user = $this->autologin('paotui_openid', $jsonrt['openId']);
        }

        //用户信息
        if ($_POST['userInfo']) {
            $jsonrt['nickName'] = $_POST['userInfo']['nickName'];
            $jsonrt['gender'] = $_POST['userInfo']['gender'];
            $jsonrt['province'] = $_POST['userInfo']['province'];
            $jsonrt['city'] = $_POST['userInfo']['city'];
            $jsonrt['avatarUrl'] = $_POST['userInfo']['avatarUrl'];
        }

        if (empty($now_user)) {
            /*临时注册用户*/
            $third_login = array(
                'from' => 'wxapp',
                'paotui_openid' => $jsonrt['openId'],
                'union_id' => ($jsonrt['unionId'] ? $jsonrt['unionId'] : ''),
                'nickname' => $jsonrt['nickName'],
                'sex' => $jsonrt['gender'],
                'province' => $jsonrt['province'],
                'city' => $jsonrt['city'],
                'avatar' => D('User')->down_avatar($jsonrt['avatarUrl'], $jsonrt['unionId'] ? $jsonrt['unionId'] : $jsonrt['openId']),
                'is_follow' => 0,
                'third_auth_time' => time(),
                'wxapp_session_key' => $session_key,
            );
            if ($third_id = M('User_tmp_third')->data($third_login)->add()) {
                $now_third = M('User_tmp_third')->where(['third_id' => $third_id])->find();
                $ticket = ticket::create($now_third['third_id'], $this->DEVICE_ID, true);
                $return = [
                    'emptyUser' => false,
                    'third_id' => $third_id,
                    'third_from' => $third_login['from'],
                    'ticket' => $ticket['ticket'],
                    'v20_ticket' => D("User")->getV20Ticket($now_third['third_id']),
                    'user' => $now_third,
                ];
                $this->returnCodeOk($return);
            }
        } else {
            $data_user = array(
                'nickname' => $jsonrt['nickName'],
                'sex' => $jsonrt['gender'],
                'province' => $jsonrt['province'],
                'city' => $jsonrt['city'],
                'avatar' => D('User')->down_avatar($jsonrt['avatarUrl'], $jsonrt['unionId'] ? $jsonrt['unionId'] : $jsonrt['openId']),
                'last_weixin_time' => time(),
                'wxapp_session_key' => $session_key,
            );
            D('User')->where(array('uid' => $now_user['uid']))->data($data_user)->save();
        }

        if (!empty($now_user)) {
            $now_user['wxapp_session_key'] = $session_key;
            $ticket = ticket::create($now_user['uid'], $this->DEVICE_ID, true);
            $this->returnCodeOk(['emptyUser' => false, 'third_id' => 0, 'ticket' => $ticket['ticket'], 'user' => $now_user, 'v20_ticket' => D("User")->getV20Ticket($now_user['uid'])]);
        } else {
            $this->returnCodeOk(['emptyUser' => true]);
        }
    }

    public function login()
    {
        if ($_POST['ticket']) {
            $info = ticket::get($_POST['ticket'], $this->DEVICE_ID, true);
            if ($info && $info['uid']) {
                $now_user = $this->autologin('uid', $info['uid']);
                if (!empty($now_user)) {
                    $return = array(
                        'ticket' => $_POST['ticket'],
                        'v20_ticket' => D("User")->getV20Ticket($info['uid']),
                        'user' => $now_user,
                    );
                    $this->returnCode(0, $return);
                } else {
                    $this->returnCode(0, array('emptyUser' => true));
                }
            } else {
                $this->returnCode(0, array('emptyUser' => true));
            }
        }

        $appid = $this->config['pay_wxapp_paotui_appid'];
        $appsecret = $this->config['pay_wxapp_paotui_appsecret'];

        import('ORG.Net.Http');
        $http = new Http();

        $return = Http::curlPost('https://api.weixin.qq.com/sns/jscode2session?appid=' . $appid . '&secret=' . $appsecret . '&js_code=' . $_POST['code'] . '&grant_type=authorization_code', array());

        if ($return['errcode']) {
            $this->returnCodeError($return['errmsg']);
        }

        import('@.ORG.aeswxapp.wxBizDataCrypt');

        $session_key = $return['session_key'];
        $pc = new WXBizDataCrypt($appid, $session_key);
        $errCode = $pc->decryptData($_POST['encryptedData'], $_POST['iv'], $data);
        $jsonrt = json_decode($data, true);
        /*优先使用 unionId 登录*/
        if (!empty($jsonrt['unionId'])) {
            $now_user = $this->autologin('union_id', $jsonrt['unionId'], $jsonrt['openId']);
        } else {
            /*再次使用 openId 登录*/
            $now_user = $this->autologin('paotui_openid', $jsonrt['openId']);
        }
        if (empty($now_user)) {
            $data_user = array(
                'paotui_openid' => $jsonrt['openId'],
                'union_id' => ($jsonrt['unionId'] ? $jsonrt['unionId'] : ''),
                'nickname' => $jsonrt['nickName'],
                'sex' => $jsonrt['gender'],
                'province' => $jsonrt['province'],
                'city' => $jsonrt['city'],
                'avatar' => D('User')->down_avatar($jsonrt['avatarUrl'], $jsonrt['unionId'] ? $jsonrt['unionId'] : $jsonrt['openId']),
                'last_weixin_time' => time(),
                'source' => 'wxapp',
                'wxapp_session_key' => $session_key,
            );
            $reg_result = D('User')->autoreg($data_user);
            if (!$reg_result['error_code']) {
                $now_user = $this->autologin('paotui_openid', $jsonrt['openId']);
            }
        } else {
            $data_user = array(
                'nickname' => $jsonrt['nickName'],
                'sex' => $jsonrt['gender'],
                'province' => $jsonrt['province'],
                'city' => $jsonrt['city'],
                'avatar' => D('User')->down_avatar($jsonrt['avatarUrl'], $jsonrt['unionId'] ? $jsonrt['unionId'] : $jsonrt['openId']),
                'last_weixin_time' => time(),
                'wxapp_session_key' => $session_key,
            );
            D('User')->where(array('uid' => $now_user['uid']))->data($data_user)->save();
        }

        if (!empty($now_user)) {
            $now_user['wxapp_session_key'] = $session_key;
            $ticket = ticket::create($now_user['uid'], $this->DEVICE_ID, true);
            $return = array(
                'ticket' => $ticket['ticket'],
                'v20_ticket' => D("User")->getV20Ticket($now_user['uid']),
                'user' => $now_user,
                'emptyUser' => false,
            );
            $this->returnCode(0, $return);
        } else {
            $this->returnCode(0, array('emptyUser' => true));
        }
    }

    protected function autologin($field, $value, $openid = '')
    {
        $result = D('User')->autologin($field, $value);
        $now_user = array();
        if (empty($result['error_code'])) {
            if ($field == 'union_id' && empty($result['user']['paotui_openid'])) {
                $condition_user['union_id'] = $value;
                D('User')->where($condition_user)->data(array('paotui_openid' => $openid))->save();
                $result['user']['paotui_openid'] = $openid;
            }
            $result['user']['showPhone'] = substr_replace($result['user']['phone'], '****', 3, 4);
            $now_user = $result['user'];
        }
        return $now_user;
    }
}