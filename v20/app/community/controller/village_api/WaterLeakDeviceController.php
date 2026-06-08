<?php

namespace app\community\controller\village_api;

use app\BaseController;

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

        return json([
            'status' => 1000,
            'msg' => 'ok',
            'data' => ['time' => $record['time']],
        ]);
    }

    private function writeDeviceLog(array $record)
    {
        $logFile = dirname(app()->getRootPath()) . DIRECTORY_SEPARATOR . 'water_leak_device.log';
        $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        file_put_contents($logFile, json_encode($record, $jsonFlags) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
