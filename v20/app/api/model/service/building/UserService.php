<?php

namespace app\api\model\service\building;

use think\facade\Db;
use token\Token;

class UserService
{

    public function getInfo($code)
    {
        $token = Token::checkToken($code);
        if (isset($token['memberId']) && $token['memberId']) {
            $user = Db::name('user')->where('uid', $token['memberId'])->find();
            if (empty($user) || $user['status'] != 1) {
                throw new \Exception('用户不存在或已被禁用');
            }
            return [
                'nickname' => $user['nickname'],
                'wxapp_openid' => $user['wxapp_openid'],
                'phone' => $user['phone'],
                'wxapp_appid' => cfg('pay_wxapp_appid'),
                'union_id' => $user['union_id'],
                'country_code' => $user['country_code']??''
            ];
        } else {
            throw new \Exception('code已失效，请重新获取');
        }
    }
}
