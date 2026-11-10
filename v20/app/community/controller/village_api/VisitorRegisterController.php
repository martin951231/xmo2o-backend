<?php
namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseVillageService;
use app\community\model\service\VisitorRegisterService;
use app\community\model\service\VisitorRegisterValidation;
use think\facade\Db;

class VisitorRegisterController extends CommunityBaseController
{
    public function configInfo() { return $this->handle('configInfo'); }
    public function saveConfig() { return $this->handle('saveConfig'); }
    public function testConnection() { return $this->handle('testConnection'); }
    public function forms() { return $this->handle('forms'); }
    public function generateQr() { return $this->handle('generateQr'); }
    public function publish() { return $this->handle('publish'); }
    public function disable() { return $this->handle('disable'); }
    public function records() { return $this->handle('records'); }
    public function detail() { return $this->handle('detail'); }
    public function registerExit() { return $this->handle('registerExit'); }
    public function export() { return $this->handle('export'); }

    private function handle($action)
    {
        try {
            if (!$this->request->isPost()) { throw new \InvalidArgumentException('请使用POST请求'); }
            $villageId = intval($this->adminUser['village_id'] ?? 0);
            if (!$villageId || !$this->login_role || !in_array($this->login_role, $this->villageRole)) { return api_output_error(1002, '请先登录小区后台'); }
            $menuId = Db::name('house_menu_new')->where(['component' => 'visitorRegisterNew', 'status' => 1])->value('id');
            if (!VisitorRegisterValidation::hasMenu($this->adminUser, $this->login_role, $this->dismissPermissionRole, $menuId) || !(new HouseVillageService())->checkPermissionMenu($menuId, $this->adminUser, $this->login_role, $this->dismissPermissionRole)) {
                return api_output_error(1003, '没有访客登记(新)管理权限');
            }
            $params = $this->request->post();
            $type = $params['form_type'] ?? '';
            $service = new VisitorRegisterService();
            switch ($action) {
                case 'configInfo': case 'testConnection': case 'forms': $result = $service->{$action}($villageId); break;
                case 'saveConfig': $result = $service->saveConfig($villageId, $params); break;
                case 'generateQr': $result = $service->generateQr($villageId, $type, !empty($params['regenerate'])); break;
                case 'publish': case 'disable': $result = $service->changeStatus($villageId, $type, $action === 'publish' ? 'published' : 'disabled'); break;
                case 'records': case 'export': $result = $service->{$action}($villageId, $type, $params); break;
                case 'detail': $result = $service->detail($villageId, $type, $params['id'] ?? 0); break;
                case 'registerExit': $result = $service->registerExit($villageId, $params['id'] ?? 0, $params['exit_time'] ?? ''); break;
            }
            return api_output(0, $result)->header(['Cache-Control' => 'no-store']);
        } catch (\InvalidArgumentException $e) { return api_output_error(1003, $e->getMessage()); }
        catch (\Throwable $e) { return api_output_error(1003, '操作失败，请检查数据库升级、存储目录和服务端配置'); }
    }
}
