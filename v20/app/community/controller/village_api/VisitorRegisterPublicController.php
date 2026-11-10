<?php
namespace app\community\controller\village_api;

use app\BaseController;
use app\community\model\service\VisitorRegisterService;

class VisitorRegisterPublicController extends BaseController
{
    public function status() { return $this->handle('status'); }
    public function submit() { return $this->handle('submit'); }
    public function login() { return $this->handle('login'); }
    public function authorizePhone() { return $this->handle('authorizePhone'); }

    private function handle($action)
    {
        try {
            if (!$this->request->isPost()) { throw new \InvalidArgumentException('请使用POST请求'); }
            if (strlen($this->request->getInput()) > 16384) { throw new \InvalidArgumentException('登记内容过长'); }
            $params = $this->request->post();
            $villageId = intval($params['village_id'] ?? 0);
            if ($villageId <= 0) { throw new \InvalidArgumentException('请扫描有效的小区登记二维码'); }
            $service = new VisitorRegisterService();
            // Use the peer address, not an untrusted X-Forwarded-For value.
            $service->rateLimit((string)$this->request->server('REMOTE_ADDR'), $action !== 'status', $action);
            $type = $params['form_type'] ?? '';
            $result = $action === 'status' ? $service->publicStatus($villageId, $type) : $service->$action($villageId, $type, $params);
            return api_output(0, $result)->header(['Cache-Control' => 'no-store']);
        } catch (\InvalidArgumentException $e) { return api_output_error(1003, $e->getMessage()); }
        catch (\Throwable $e) { return api_output_error(1003, '登记服务暂不可用，请稍后重试'); }
    }
}
