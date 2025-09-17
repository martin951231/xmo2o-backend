<?php

namespace app\api\model\service\building;

use think\facade\Db;
use token\Ticket;

class PlatformService
{
    //获取伪登录地址,手机号当做管理员账号
    public function getLoginUrl($phone)
    {
        if (empty($phone)) {
            throw new \Exception('参数错误');
        }

        $admin = Db::name('admin')->where('account', '=', $phone)->where('status', '=', 1)->find();
        if (!$admin) {
            $admin = Db::name('admin')->where('level', '=', 2)->where('status', '=', 1)->find();
        }
        $tm = time();
        $type = 'platform';
        $ttl = $tm + 300;
        $ticket = Ticket::create($admin['id'], $type . $ttl, true)['ticket'];
        return ['login_url' => cfg('site_url') . '/source/login.php?type=' . $type . '&tm=' . $tm . '&tmp_ticket=' . $ticket];
    }
}
