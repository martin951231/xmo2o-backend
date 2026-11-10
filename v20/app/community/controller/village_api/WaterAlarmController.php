<?php
namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseVillageService;
use app\community\model\service\WaterAlarmService;
use think\facade\Db;

class WaterAlarmController extends CommunityBaseController
{
    public function devices() { return $this->handle('devices'); }
    public function sensors() { return $this->handle('sensors'); }
    public function generateSensor() { return $this->handle('generateSensor'); }
    public function saveSensor() { return $this->handle('saveSensor'); }
    public function save() { return $this->handle('save'); }
    public function operate() { return $this->handle('operate'); }
    public function logs() { return $this->handle('logs'); }
    public function texts() { return $this->handle('texts'); }
    public function addText() { return $this->handle('addText'); }
    public function deleteText() { return $this->handle('deleteText'); }

    private function handle($action)
    {
        try {
            if (!$this->request->isPost()) { throw new \InvalidArgumentException('请使用POST请求'); }
            $villageId = (int)($this->adminUser['village_id'] ?? 0);
            if (!$villageId || !$this->login_role || !in_array($this->login_role, $this->villageRole)) { return api_output_error(1002, '请先登录小区后台'); }
            $menuId = Db::name('house_menu_new')->where(['component' => 'waterAlarm', 'status' => 1])->value('id');
            if (!WaterAlarmService::hasMenu($this->adminUser, $this->login_role, $this->dismissPermissionRole, $menuId) || !(new HouseVillageService())->checkPermissionMenu($menuId, $this->adminUser, $this->login_role, $this->dismissPermissionRole)) {
                return api_output_error(1003, '没有水浸管理权限');
            }
            $params = $this->request->post();
            $service = new WaterAlarmService();
            switch ($action) {
                case 'sensors': $result = (new \app\community\model\service\WaterAlarmSensorService())->sensors($villageId); break;
                case 'generateSensor': $result = (new \app\community\model\service\WaterAlarmSensorService())->generate($villageId); break;
                case 'saveSensor': $result = (new \app\community\model\service\WaterAlarmSensorService())->save($villageId, $params['sensor_id'] ?? '', $params['alarm_text'] ?? ''); break;
                case 'devices': $result = $service->devices($villageId); break;
                case 'save': $result = $service->save($villageId, $params); break;
                case 'logs': $result = $service->logs($villageId, $params['id'] ?? 0); break;
                case 'texts': $result = $service->texts($villageId, $params['id'] ?? 0); break;
                case 'addText': $result = $service->addText($villageId, $params['id'] ?? 0, $params['content'] ?? ''); break;
                case 'deleteText': $result = $service->deleteText($villageId, $params['id'] ?? 0, $params['text_id'] ?? 0); break;
                case 'operate': $result = $service->operate($villageId, $params['id'] ?? 0, $params['action'] ?? '', $params['volume'] ?? null, (int)$this->login_userId, (int)$this->login_role, $params['text_id'] ?? 0); break;
            }
            return api_output(0, $result)->header(['Cache-Control' => 'no-store']);
        } catch (\InvalidArgumentException $e) { return api_output_error(1003, $e->getMessage()); }
        catch (\Throwable $e) {
            \think\facade\Log::error('WaterAlarm: ' . $e->getMessage());
            return api_output_error(1003, '操作未能确认，请刷新设备状态；若设备仍在报警，请发送停止指令。请管理员检查数据库升级和服务日志');
        }
    }
}
