<?php

class FakeLoginAction extends BaseAction
{

    public function index()
    {
        $tmpTicket = I('get.tmp_ticket', '', 'trim');
        $type = I('get.type', '', 'trim');
        $tm = I('get.tm', 0, 'intval');
        $nowTime = time();
        $expireSecond = 300;

        if (abs($tm - $nowTime) > $expireSecond) {
            $this->error('登录链接已过期');
        }

        $ticket = ticket::get($tmpTicket, $type . ($tm + $expireSecond), true);
        $id = $ticket['uid'] ?? 0;
        if ($id < 1) {
            $this->error('非法链接，登录失败');
        }

        if ($type == 'merchant') {
            $merchant = D('Merchant')->field(true)->where(['mer_id' => $id])->find();
            if (empty($merchant) || $merchant['status'] != 1) {
                $this->error('商户不存在或已禁用');
            }
            $merchant['login_type'] = "fake_login";  //系统后台伪登录标识
            session('merchant', $merchant);
            session('staff', null);
            import("@.ORG.Token");
            $ticket = Token::createToken($id);
            setcookie('merchant_access_token', $ticket, -1, '/');
            redirect(C('config.site_url') . '/v20/public/platform/#/merchant/merchant.iframe/menu_999999');
            exit;
        } else if ($type == 'platform') {
            $admin = D('Admin')->field(true)->where(['id' => $id])->find();
            if (empty($admin) || $admin['status'] != 1) {
                $this->error('系统管理员不存在或已禁用');
            }
            $result = invoke_v20_service('\app\common\model\service\admin_user\AdminUserService/login', ['data' => ['ltype' => '3', 'id' => $id]]);
            $result = $result['data']['retval'] ?? [];
            setcookie('platform_access_token', $result['ticket'], -1, '/');

            $menuId = I('get.menu_id', 0, 'intval');
            $hideMenu = I('get.hide_menu', 1, 'intval');

            if($menuId > 0){
                $menu = D('System_menu')->where(['id'=>$menuId])->find();
                if($menu['component'] && $menu['path']){
                    //新版v20页面
                    $url = '/v20/public/platform/#'.$menu['path'];
                }else{
                    //旧版页面
                    $url = '/v20/public/platform/#/common/platform.iframe/menu_'.$menuId;
                }
                if($hideMenu){
                    $url .= '?from=iframe';
                }
                redirect(C('config.site_url') . $url);
            }else{
                redirect(C('config.site_url') . "/v20/public/platform/#/common/platform.iframe/menu_999999");
            }
        } else {
            $this->error('登录地址有误，登录失败');
        }
    }
}
