<?php
namespace app\social_activity\controller\socialactivity;

use app\social_activity\model\service\SocialActivityRefundService;
use think\facade\Db;

class SocialActivityRefundController extends AuthBaseController
{
    public function initialize()
    {
        parent::initialize();
        if ((int)($this->socialUser['role_id'] ?? 0) !== 1) {
            $allowed = Db::name('social_activity_role_menu')->alias('rm')
                ->join('social_activity_menu m', 'rm.menu_id = m.id')
                ->where(['rm.role_id' => $this->socialUser['role_id'] ?? 0, 'm.component' => 'SocialActivityRefundList', 'm.status' => 1])->count();
            if (!$allowed) { throw new \RuntimeException('没有退款列表权限'); }
        }
    }

    public function getList()
    {
        try {
            return api_output(0, (new SocialActivityRefundService())->getList(request()->param(), $this->socialUser));
        } catch (\Throwable $e) { return api_output_error(1003, $e->getMessage()); }
    }

    public function queryStatus()
    {
        try {
            return api_output(0, (new SocialActivityRefundService())->updateByAdmin(input('id', 0, 'intval'), $this->socialUser));
        } catch (\Throwable $e) { return api_output_error(1003, $e->getMessage()); }
    }
}
