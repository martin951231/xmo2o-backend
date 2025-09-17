<?php

namespace app\common\controller\platform;

use app\common\model\service\CrmUserService;

/**
 * CRM管理
 * @package app\common\controller\platform
 */
class CrmController extends AuthBaseController
{
    /**
     * 是否绑定企业微信
     * @author: 张涛
     * @date: 2021/03/24
     */
    public function isBind()
    {
        $isBind = (new CrmUserService())->isBind();
        $crmUser = (new CrmUserService())->getCrmAdminUser();
        return api_output(0, ['is_bind' => $isBind, 'account' => $crmUser['account'] ?? '', 'password' => $crmUser['password'] ?? '']);
    }

    /**
     * 注册用户
     * @author: 张涛
     * @date: 2021/03/16
     */
    public function register()
    {
        try {
            (new CrmUserService())->register();
            $rs = (new CrmUserService())->getLoginUrl();
            return api_output(0, $rs);
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 获取快速登录地址
     * @author: 张涛
     * @date: 2021/03/16
     */
    public function getLoginUrl()
    {
        try {
            $rs = (new CrmUserService())->getLoginUrl();
            return api_output(0, $rs);
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 企业微信用户列表
     * @author: 张涛
     * @date: 2021/03/16
     */
    public function users()
    {
        try {
            $users = (new CrmUserService())->users();
            return api_output(0, $users);
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }
}