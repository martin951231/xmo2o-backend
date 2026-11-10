<?php

namespace app\community\controller\village_api;

use app\BaseController;
use app\community\model\service\WaterAlarmSensorService;

class WaterLeakDeviceController extends BaseController
{
    /**
     * Public callback endpoint for water leak devices.
     * No community login or permission check is performed here.
     */
    public function index()
    {
        $record = [
            'time' => date('Y-m-d H:i:s'),
            'message' => 'request received',
            'request' => [
                'get' => $_GET,
                'post' => $_POST,
                'raw_body' => file_get_contents('php://input'),
            ],
        ];

        $this->writeDeviceLog($record);

        try {
            $result = (new WaterAlarmSensorService())->report($record['request']['raw_body']);
            $payload = \app\community\model\service\WaterLeakReport::decode($record['request']['raw_body']);
            $this->writeDeviceLog(['time' => date('Y-m-d H:i:s'), 'message' => 'water leak linkage',
                'sensor_id' => $payload['ID'], 'zhuangtai' => $payload['zhuangtai'],
                'ok' => $result['ok'], 'result' => $result['message']]);
            return json(['status' => $result['ok'] ? 1000 : 1001, 'msg' => $result['message'],
                'data' => ['time' => $record['time'], 'zhuangtai' => $payload['zhuangtai'], 'linked' => $result['ok']]]);
        } catch (\Throwable $e) {
            $this->writeDeviceLog(['time' => date('Y-m-d H:i:s'), 'message' => 'water leak linkage failed', 'error' => $e->getMessage()]);
            return json(['status' => 1001, 'msg' => '水浸联动失败，请检查water_leak_device.log', 'data' => ['time' => $record['time']]]);
        }

    }

    private function writeDeviceLog(array $record)
    {
        $logFile = dirname(app()->getRootPath()) . DIRECTORY_SEPARATOR . 'water_leak_device.log';
        $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        file_put_contents($logFile, json_encode($record, $jsonFlags) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
