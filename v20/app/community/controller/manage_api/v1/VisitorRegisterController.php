<?php
namespace app\community\controller\manage_api\v1;

use app\community\controller\manage_api\BaseController;
use app\community\model\service\VisitorRegisterService;

class VisitorRegisterController extends BaseController
{
    public function records() { return $this->handle(false); }
    public function detail() { return $this->handle(true); }

    private function handle($detail)
    {
        try {
            if (!$this->request->isPost()) { return api_output_error(1003, '请使用POST请求'); }
            $login = $this->getLoginInfo(false);
            if (isset($login['status'])) { return api_output_error($login['status'], $login['msg']); }
            // The village comes only from the authenticated management account.
            $villageId = intval($this->login_info['village_id'] ?? 0);
            // Every logged-in role can open this page. An account without a village
            // has no current-village records; never trust a client village override.
            if (!$villageId && !$detail) { return api_output(0, ['list' => [], 'total' => 0, 'has_more' => false])->header(['Cache-Control' => 'no-store']); }
            $params = $this->request->post();
            $service = new VisitorRegisterService();
            $data = $detail ? $service->mobileVisitorDetail($villageId, $params['registration_no'] ?? '') : $service->mobileVisitorRecords($villageId, $params);
            return api_output(0, $data)->header(['Cache-Control' => 'no-store']);
        } catch (\InvalidArgumentException $e) {
            return api_output_error(1003, $e->getMessage());
        } catch (\Throwable $e) {
            return api_output_error(1003, '读取登记记录失败，请检查数据库升级后重试');
        }
    }
}
