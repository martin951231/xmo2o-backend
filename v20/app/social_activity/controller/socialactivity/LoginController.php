<?php

namespace app\social_activity\controller\socialactivity;

use app\common\controller\CommonBaseController;
use app\common\model\db\Admin;
use app\social_activity\model\service\SocialActivityAdminService;
use app\social_activity\validate\SocialActivityAdminValidate;

class LoginController extends CommonBaseController
{
    /**
     * 管理员登录
     */
    public function login()
    {
        if (!$this->request->isPost()) {
            return api_output_error(1003, "非法请求");
        }
		if($userId = intval($this->request->log_uid) && $this->request->log_extends == 'admin') {
			$admin = Admin::where('id', $userId)->find();
			if($admin && $admin['level'] == 2) {
				try {
					$data = (new SocialActivityAdminService())->login(['super_admin' => 1]);
				} catch (\Exception $e) {
					return api_output_error(-1, $e->getMessage());
				}
				return api_output(0, $data, "登录成功");
			}
		}
        $param['account'] = $this->request->param('account', '', 'trim');
        $param['password'] = $this->request->param('password', '', 'trim');

        $validate = validate(SocialActivityAdminValidate::class);
        if (!$validate->scene('login')->check($param)) {
            return api_output_error(1001, $validate->getError());
        }

        $adminService = new SocialActivityAdminService();
        try {
            $data = $adminService->login($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $data, "登录成功");
    }
}
