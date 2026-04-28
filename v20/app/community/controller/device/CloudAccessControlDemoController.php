<?php

namespace app\community\controller\device;

use app\community\controller\CommunityBaseController;
use app\community\model\service\Device\CloudPlusTypeC\CommandFactory;
use app\community\model\service\Device\HouseAccessDeviceService;
use app\community\model\service\Device\HouseAccessRecordsService;
use app\community\model\service\Device\HouseAccessSensorsService;

class CloudAccessControlDemoController extends CommunityBaseController
{
    protected $rawRequestBody = null;

    public function deviceHeartbeat()
    {
        $request_data = $this->collectRequestData();
        $serial = $this->extractRequestSerial($request_data);
        $village_id = $this->resolveVillageId($request_data);
        // $this->writeRootRequestLog('heartbeat_request', [
        //     'village_id' => intval($village_id),
        //     'serial' => $serial,
        //     'request_data' => $request_data,
        // ]);
        $state = $village_id > 0 ? $this->readState($village_id) : $this->defaultState();
        $response = $this->handleHeartbeat($state, $request_data, $village_id);
        if ($village_id > 0) {
            $this->writeState($village_id, $state);
        }
        // $this->writeRootRequestLog('heartbeat_response', [
        //     'village_id' => intval($village_id),
        //     'serial' => $serial,
        //     'response_data' => $response,
        // ]);
        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    public function queueCommand()
    {
        $request_data = $this->collectRequestData();
        $serial = $this->normalizeQueueValue($request_data['Serial'] ?? $request_data['serial'] ?? '');
        $card = $this->normalizeQueueValue($request_data['Card'] ?? $request_data['card'] ?? $request_data['code'] ?? '');
        $mac = $this->normalizeMacValue($request_data['MAC'] ?? $request_data['mac'] ?? '');

        $cachedResponse = $this->getRecentQueueResponse($serial, $card, $mac);
        if (!empty($cachedResponse)) {
            return $this->renderRawJson($cachedResponse);
        }

        $deviceInfo = (new HouseAccessDeviceService())->getDeviceBySerial($serial);
        $sensorService = new HouseAccessSensorsService();
        $sensorInfo = $sensorService->getEnabledSensorByCode($card, intval($deviceInfo['village_id'] ?? 0));
        if (empty($sensorInfo) && empty($deviceInfo) && $card !== '') {
            $sensorInfo = $sensorService->getEnabledSensorByCode($card);
        }

        if (!empty($sensorInfo)) {
            $res = [
                'AcsRes' => 1,
                'ActIndex' => 0,
                'Time' => 1,
            ];
        } else {
            $res = [
                'AcsRes' => 0,
                'ActIndex' => 0,
                'Time' => 1,
            ];
        }

        $recordVillageId = intval($deviceInfo['village_id'] ?? $sensorInfo['village_id'] ?? 0);
        // try {
        //     (new HouseAccessRecordsService())->recordsAdd($recordVillageId, [
        //         'type' => intval($request_data['type'] ?? 0),
        //         'reader' => intval($request_data['Reader'] ?? 0),
        //         'card_id' => intval($sensorInfo['id'] ?? 0),
        //         'serial_id' => intval($deviceInfo['id'] ?? 0),
        //         'MAC' => $mac,
        //         'access_time' => date('Y-m-d H:i:s'),
        //     ]);
        // } catch (\Exception $e) {
        // }

        $this->storeRecentQueueResponse($serial, $card, $mac, $res);

        return $this->renderRawJson($res);
    }

    private function handleHeartbeat(array &$state, array $request_data, $village_id = 0)
    {
        $serial = $this->extractRequestSerial($request_data, $state);
        $village_id = intval($village_id);
        $sensorService = new HouseAccessSensorsService();
        $deviceState =& $this->getDeviceState($state, $serial);
        $deviceInfo = [];
        if ($village_id > 0 && $serial !== '') {
            $deviceInfo = (new HouseAccessDeviceService())->getDeviceBySerial($serial, $village_id);
        }
        $device_id = intval($deviceInfo['id'] ?? 0);
        $deviceState['last_heartbeat'] = array_merge($request_data, [
            'received_at' => date('Y-m-d H:i:s')
        ]);

        if (!empty($request_data['Key'])) {
            $deviceState['key'] = (string)$request_data['Key'];
        }

        if (
            $village_id > 0 &&
            isset($request_data['IndexCmd']) &&
            isset($request_data['CmdOK']) &&
            !empty($deviceState['pending_command']) &&
            (string)$request_data['IndexCmd'] === (string)($deviceState['pending_command']['index_cmd'] ?? '') &&
            (string)($deviceState['pending_command']['serial'] ?? '') === $serial &&
            $this->isWhitelistSyncCommand($deviceState['pending_command'])
        ) {
            $feedback_command = $deviceState['pending_command'];
            $is_success = ((string)$request_data['CmdOK'] === '1');
            $deviceState['pending_command']['status'] = $is_success ? 'done' : 'failed';
            $deviceState['pending_command']['feedback_at'] = date('Y-m-d H:i:s');
            $updated_count = $sensorService->markWhitelistResultForDevice(
                $village_id,
                intval($deviceState['pending_command']['device_id'] ?? $device_id),
                (string)($deviceState['pending_command']['serial'] ?? $serial),
                $deviceState['pending_command']['sensor_ids'] ?? [],
                (string)($deviceState['pending_command']['index_cmd'] ?? ''),
                (string)($deviceState['pending_command']['cmd_value'] ?? ''),
                $is_success
            );
            $deviceState['pending_command']['updated_whitelist_count'] = intval($updated_count);
            $this->writeWhitelistCommandLog(
                'result',
                $feedback_command,
                $request_data,
                [],
                [
                    'village_id' => $village_id,
                    'serial' => $serial,
                    'device_id' => intval($deviceState['pending_command']['device_id'] ?? $device_id),
                    'execution_result' => $is_success ? 'success' : 'failed',
                    'request_index_cmd' => (string)$request_data['IndexCmd'],
                    'request_cmd_ok' => (string)$request_data['CmdOK'],
                    'updated_whitelist_count' => intval($deviceState['pending_command']['updated_whitelist_count'] ?? 0),
                ]
            );
            $deviceState['last_command'] = $deviceState['pending_command'];
            $deviceState['pending_command'] = [];
        } elseif (
            $village_id > 0 &&
            isset($request_data['IndexCmd']) &&
            isset($request_data['CmdOK']) &&
            ((string)$request_data['IndexCmd'] !== '') &&
            ((string)$request_data['IndexCmd'] !== '0')
        ) {
            // $this->writeRootRequestLog('command_result', [
            //     'phase' => 'result',
            //     'village_id' => $village_id,
            //     'serial' => $serial,
            //     'device_id' => $device_id,
            //     'execution_result' => 'unmatched',
            //     'request_index_cmd' => (string)$request_data['IndexCmd'],
            //     'request_cmd_ok' => (string)$request_data['CmdOK'],
            //     'request_data' => $request_data,
            // ]);
        }

        if (empty($deviceState['pending_command']) && $village_id > 0) {
            $this->queuePendingWhitelistSync($deviceState, $request_data, $village_id, $serial);
        }

        $response = [
            'Key' => (string)($request_data['Key'] ?? $deviceState['key'] ?? '')
        ];
        if (
            !empty($deviceState['pending_command']) &&
            $this->isWhitelistSyncCommand($deviceState['pending_command']) &&
            (string)($deviceState['pending_command']['serial'] ?? '') === $serial
        ) {
            $response['IndexCmd'] = (string)$deviceState['pending_command']['index_cmd'];
            $response['CmdValue'] = (string)$deviceState['pending_command']['cmd_value'];
            $deviceState['pending_command']['status'] = 'sent';
            $deviceState['pending_command']['sent_at'] = date('Y-m-d H:i:s');
            $sensorService->markWhitelistCommandSentForDevice(
                $village_id,
                intval($deviceState['pending_command']['device_id'] ?? $device_id),
                (string)($deviceState['pending_command']['serial'] ?? $serial),
                $deviceState['pending_command']['sensor_ids'] ?? [],
                (string)$deviceState['pending_command']['index_cmd'],
                (string)$deviceState['pending_command']['cmd_value']
            );
            $this->writeWhitelistCommandLog('send', $deviceState['pending_command'], $request_data, $response, [
                'village_id' => $village_id,
                'serial' => $serial,
                'device_id' => intval($deviceState['pending_command']['device_id'] ?? $device_id),
                'execution_result' => 'pending',
            ]);
        }

        return $response;
    }

    private function queuePendingWhitelistSync(array &$deviceState, array $request_data, $village_id, $serial = '')
    {
        $village_id = intval($village_id);
        if ($village_id <= 0) {
            return;
        }

        $serial = trim((string)$serial);
        if ($serial === '') {
            $serial = $this->extractRequestSerial($request_data);
        }
        if ($serial === '') {
            return;
        }

        $deviceInfo = (new HouseAccessDeviceService())->getDeviceBySerial($serial, $village_id);
        $device_id = intval($deviceInfo['id'] ?? 0);
        if ($device_id <= 0 || intval($deviceInfo['enable'] ?? 0) !== 1) {
            return;
        }

        $door = trim((string)($request_data['door'] ?? $deviceState['door'] ?? '01')) ?: '01';
        $deviceState['door'] = $door;
        $sensorService = new HouseAccessSensorsService();
        $pending_sensors = $sensorService->getPendingWhitelistSensors($village_id, $device_id, 1);
        if (empty($pending_sensors)) {
            return;
        }

        $command = 'Command_C1';
        $command_options = $this->buildWhitelistCommandOptions($pending_sensors);
        if (empty($command_options['code'])) {
            return;
        }

        $sensor_id = intval($pending_sensors[0]['id'] ?? 0);
        if ($sensor_id <= 0) {
            return;
        }

        try {
            $built = CommandFactory::build($command, $door, $command_options);
        } catch (\Exception $e) {
            $this->writeRootRequestLog('command_result', [
                'phase' => 'build_failed',
                'village_id' => intval($village_id),
                'serial' => $serial,
                'execution_result' => 'build_failed',
                'message' => $e->getMessage(),
                'request_data' => $request_data,
                'command_options' => $command_options,
            ]);
            return;
        }

        $deviceState['pending_command'] = [
            'index_cmd' => (string)$sensor_id,
            'command' => $built['command'],
            'cmd_value' => $built['cmd_value'],
            'serial' => $serial,
            'device_id' => $device_id,
            'status' => 'queued',
            'created_at' => date('Y-m-d H:i:s'),
            'sync_type' => 'whitelist_single',
            'sensor_ids' => [$sensor_id],
        ];
    }

    private function buildWhitelistCommandOptions(array $pending_sensors)
    {
        $sensor = $pending_sensors[0];
        $sensor_id = intval($sensor['id'] ?? 0);
        $sensor_enable = intval($sensor['enable'] ?? 0);
        return [
            'index' => $sensor_id,
            'code' => (string)$sensor['code'],
            'name' => (string)($sensor['parking_num'] ?: $sensor['code']),
            'qrcode' => '',
            'china_card' => '',
            'pin' => '',
            'tz' => $sensor_enable === 0 ? 0 : 1,
            'start_time' => '2022-07-28 00:00',
            'end_time' => '2048-01-01 01:01',
        ];
    }

    private function isWhitelistSyncCommand(array $command)
    {
        return (string)($command['sync_type'] ?? '') === 'whitelist_single';
    }

    private function collectRequestData()
    {
        $data = [];

        if (function_exists('request')) {
            try {
                $params = request()->param();
                if (is_array($params)) {
                    $data = array_merge($data, $params);
                }
            } catch (\Throwable $e) {
            }
        }

        if (function_exists('input')) {
            try {
                $params = input();
                if (is_array($params)) {
                    $data = array_merge($data, $params);
                }
            } catch (\Throwable $e) {
            }
        }

        $data = array_merge($data, $_GET, $_POST, $_REQUEST);

        $input = $this->getRawRequestBody();
        if ($input !== '') {
            $json = json_decode($input, true);
            if (is_array($json)) {
                $data = array_merge($data, $json);
            } else {
                $parsed = [];
                parse_str($input, $parsed);
                if (!empty($parsed)) {
                    $data = array_merge($data, $parsed);
                }
            }
        }

        if (empty($data) && !empty($_SERVER['QUERY_STRING'])) {
            $query = [];
            parse_str((string)$_SERVER['QUERY_STRING'], $query);
            if (!empty($query)) {
                $data = array_merge($data, $query);
            }
        }

        return $data;
    }

    private function getRawRequestBody()
    {
        if ($this->rawRequestBody === null) {
            $this->rawRequestBody = file_get_contents('php://input');
            if ($this->rawRequestBody === false) {
                $this->rawRequestBody = '';
            }
        }

        return $this->rawRequestBody;
    }

    private function normalizeQueueValue($value)
    {
        return strtoupper(trim((string)$value));
    }

    private function extractRequestSerial(array $request_data, array $state = [])
    {
        return trim((string)($request_data['Serial'] ?? $request_data['serial'] ?? $state['config']['serial'] ?? ''));
    }

    private function normalizeMacValue($value)
    {
        $value = strtoupper(trim((string)$value));
        return preg_replace('/\s+/', '', $value);
    }

    private function buildQueueRequestSignature($serial, $card, $mac)
    {
        $serial = $this->normalizeQueueValue($serial);
        $card = $this->normalizeQueueValue($card);
        $mac = $this->normalizeMacValue($mac);
        if ($serial === '' && $card === '' && $mac === '') {
            return '';
        }

        return md5($serial . '|' . $card . '|' . $mac);
    }

    private function &getDeviceState(array &$state, $serial)
    {
        if (empty($state['device_states']) || !is_array($state['device_states'])) {
            $state['device_states'] = [];
        }
        $serial = trim((string)$serial);
        if ($serial === '') {
            $serial = '__default__';
        }
        if (empty($state['device_states'][$serial]) || !is_array($state['device_states'][$serial])) {
            $state['device_states'][$serial] = $this->defaultDeviceState();
        }

        return $state['device_states'][$serial];
    }

    private function getRecentQueueResponse($serial, $card, $mac)
    {
        $signature = $this->buildQueueRequestSignature($serial, $card, $mac);
        if ($signature === '') {
            return [];
        }

        $cache = $this->readQueueRequestCache();
        $currentTime = time();
        $cacheChanged = false;
        foreach ($cache as $key => $item) {
            if ($currentTime - intval($item['time'] ?? 0) > 10) {
                unset($cache[$key]);
                $cacheChanged = true;
            }
        }

        $response = [];
        if (!empty($cache[$signature]) && ($currentTime - intval($cache[$signature]['time'] ?? 0) <= 3)) {
            $response = is_array($cache[$signature]['response'] ?? null) ? $cache[$signature]['response'] : [];
        }

        if ($cacheChanged) {
            $this->writeQueueRequestCache($cache);
        }

        return $response;
    }

    private function storeRecentQueueResponse($serial, $card, $mac, array $response)
    {
        $signature = $this->buildQueueRequestSignature($serial, $card, $mac);
        if ($signature === '') {
            return;
        }

        $cache = $this->readQueueRequestCache();
        $cache[$signature] = [
            'time' => time(),
            'response' => $response,
        ];
        $this->writeQueueRequestCache($cache);
    }

    private function readQueueRequestCache()
    {
        $file = $this->getQueueRequestCacheFile();
        if (!file_exists($file)) {
            return [];
        }

        $content = file_get_contents($file);
        if ($content === false || $content === '') {
            return [];
        }

        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    private function writeQueueRequestCache(array $cache)
    {
        $file = $this->getQueueRequestCacheFile();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($file, json_encode($cache, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    private function getQueueRequestCacheFile()
    {
        return runtime_path() . 'community/cloud_access_control_demo/request_cache.json';
    }

    private function resolveVillageId(array $request_data)
    {
        $serial = trim((string)($request_data['Serial'] ?? $request_data['serial'] ?? ''));
        if ($serial !== '') {
            $village_id = (int)$this->matchVillageIdBySerial($serial);
            if ($village_id > 0) {
                return $village_id;
            }
        }

        $village_id = (int)($request_data['village_id'] ?? 0);
        if ($village_id > 0) {
            return $village_id;
        }

        return 0;
    }

    private function writeRootRequestLog($scene, array $payload = [])
    {
        $scene = (string)$scene;
        if (!in_array($scene, ['heartbeat_request', 'heartbeat_response', 'command_result'], true)) {
            return;
        }

        $log_file = $this->getRootLogFileByScene($scene);
        $entry = array_merge([
            'time' => date('Y-m-d H:i:s'),
            'scene' => $scene,
        ], $payload);
        file_put_contents($log_file, json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function writeWhitelistCommandLog($phase, array $command, array $request_data = [], array $response_data = [], array $extra = [])
    {
        $payload = [
            'phase' => (string)$phase,
            'village_id' => intval($extra['village_id'] ?? 0),
            'serial' => (string)($extra['serial'] ?? $command['serial'] ?? ''),
            'device_id' => intval($extra['device_id'] ?? $command['device_id'] ?? 0),
            'instruction_command' => (string)($command['command'] ?? ''),
            'instruction_value' => (string)($command['cmd_value'] ?? ''),
            'index_cmd' => (string)($command['index_cmd'] ?? ''),
            'sensor_ids' => array_values($command['sensor_ids'] ?? []),
            'execution_result' => (string)($extra['execution_result'] ?? ''),
            'request_index_cmd' => (string)($extra['request_index_cmd'] ?? ''),
            'request_cmd_ok' => (string)($extra['request_cmd_ok'] ?? ''),
            'updated_whitelist_count' => intval($extra['updated_whitelist_count'] ?? 0),
            'request_data' => $request_data,
            'response_data' => $response_data,
        ];

        $this->writeRootRequestLog('command_result', $payload);
    }

    private function getRootLogFileByScene($scene)
    {
        $scene = (string)$scene;
        $map = [
            'heartbeat_request' => 'cloud_access_control_heartbeat_request.log',
            'heartbeat_response' => 'cloud_access_control_heartbeat_response.log',
            'command_result' => 'cloud_access_control_command_result.log',
        ];
        $filename = $map[$scene] ?? 'cloud_access_control_command_result.log';
        return rtrim(root_path(), '/\\') . DIRECTORY_SEPARATOR . $filename;
    }

    private function readState($village_id)
    {
        $file = $this->getStateFile($village_id);
        if (!file_exists($file)) {
            return $this->defaultState();
        }
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        return is_array($data) ? array_replace_recursive($this->defaultState(), $data) : $this->defaultState();
    }

    private function writeState($village_id, array $state)
    {
        $file = $this->getStateFile($village_id);
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($file, json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    }

    private function getStateFile($village_id)
    {
        return runtime_path() . 'community/cloud_access_control_demo/village_' . intval($village_id) . '.json';
    }

    private function matchVillageIdBySerial($serial)
    {
        $serial = trim((string)$serial);
        if ($serial === '') {
            return 0;
        }

        $device_info = (new HouseAccessDeviceService())->getDeviceBySerial($serial);
        return intval($device_info['village_id'] ?? 0);
    }

    private function defaultState()
    {
        return [
            'device_states' => [],
            'meta' => []
        ];
    }

    private function defaultDeviceState()
    {
        return [
            'key' => '',
            'door' => '01',
            'pending_command' => [],
            'last_command' => [],
            'last_heartbeat' => [],
        ];
    }

    private function renderRawJson(array $payload)
    {
        return json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
