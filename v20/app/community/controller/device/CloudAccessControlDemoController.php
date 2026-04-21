<?php

namespace app\community\controller\device;

use app\community\controller\CommunityBaseController;
use app\community\model\service\Device\CloudPlusTypeC\CommandFactory;
use app\community\model\service\Device\HouseAccessDeviceService;
use app\community\model\service\Device\HouseAccessRecordsService;
use app\community\model\service\Device\HouseAccessSensorsService;
use JMessage\Cross\Message;

class CloudAccessControlDemoController extends CommunityBaseController
{
    protected $rawRequestBody = null;

    public function getDemoState()
    {
        $request_data = $this->collectRequestData();
        $response = [];
        if (!empty($request_data['Key'])) {
            $response['Key'] = (string)$request_data['Key'];
        }
        return $this->renderDeviceJson($response);
    }

    public function saveDemoConfig()
    {
        $village_id = $this->getVillageId();
        $state = $this->readState($village_id);
        $request_data = $this->collectRequestData();

        $state['config']['device_name'] = trim((string)($request_data['device_name'] ?? $state['config']['device_name']));
        $state['config']['serial'] = trim((string)($request_data['Serial'] ?? $request_data['serial'] ?? $state['config']['serial']));
        $state['config']['time'] = trim((string)($request_data['Time'] ?? $request_data['time'] ?? $state['config']['time']));
        $state['config']['door'] = trim((string)($request_data['door'] ?? $state['config']['door']));
        if (!empty($request_data['Key'])) {
            $state['config']['key'] = trim((string)$request_data['Key']);
        }

        $this->appendLog($state, 'save_config', 'save device config', $state['config']);
        $this->writeState($village_id, $state);
        return $this->renderRawJson([]);
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

        $recordResult = [];
        $recordError = '';
        $recordVillageId = intval($deviceInfo['village_id'] ?? $sensorInfo['village_id'] ?? 0);
        try {
            $recordResult = (new HouseAccessRecordsService())->recordsAdd($recordVillageId, [
                'type' => intval($request_data['type'] ?? 0),
                'reader' => intval($request_data['Reader'] ?? 0),
                'card_id' => intval($sensorInfo['id'] ?? 0),
                'serial_id' => intval($deviceInfo['id'] ?? 0),
                'MAC' => $mac,
                'access_time' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            $recordError = $e->getMessage();
        }

        $this->writeRootRequestLog('queueCommand', $request_data, [
            'serial' => $serial,
            'card' => $card,
            'mac' => $mac,
            'response' => $res,
            'sensor_id' => intval($sensorInfo['id'] ?? 0),
            'serial_id' => intval($deviceInfo['id'] ?? 0),
            'record_result' => $recordResult,
            'record_error' => $recordError,
        ]);
        $this->storeRecentQueueResponse($serial, $card, $mac, $res);

        return $this->renderRawJson($res);
    }

    public function queueOpenDoor()
    {
        return $this->requestOpenDoor();
    }

    private function handleQueueCommand($village_id, $command, array $request_data)
    {
        $state = $this->readState($village_id);
        $serial = trim((string)($request_data['Serial'] ?? $request_data['serial'] ?? $state['config']['serial']));
        $device_name = trim((string)($request_data['device_name'] ?? $state['config']['device_name']));
        $door = trim((string)($request_data['door'] ?? $state['config']['door'] ?: '01'));
        $key = trim((string)($request_data['Key'] ?? $state['config']['key']));

        if ($key !== '') {
            $state['config']['key'] = $key;
        }
        if ($serial !== '') {
            $state['config']['serial'] = $serial;
        }
        if ($device_name !== '') {
            $state['config']['device_name'] = $device_name;
        }
        if ($door !== '') {
            $state['config']['door'] = $door;
        }

        if ($serial === '' || $state['config']['key'] === '') {
            $this->writeState($village_id, $state);
            return $this->renderDeviceJson([
                'Key' => (string)$state['config']['key']
            ]);
        }

        try {
            $built = CommandFactory::build($command, $door);
        } catch (\Exception $e) {
            $this->writeState($village_id, $state);
            return $this->renderDeviceJson([
                'Key' => (string)$state['config']['key']
            ]);
        }

        $state['pending_command'] = [
            'index_cmd' => (string)$this->nextIndexCmd($state),
            'command' => $built['command'],
            'label' => $built['label'],
            'door' => $built['door'],
            'cmd_value' => $built['cmd_value'],
            'serial' => $serial,
            'status' => 'queued',
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->appendLog($state, 'queue_command', 'queue command', $state['pending_command']);
        $this->writeState($village_id, $state);

        return $this->renderDeviceJson([
            'Key' => (string)$state['config']['key'],
            'IndexCmd' => (string)$state['pending_command']['index_cmd'],
            'CmdValue' => (string)$state['pending_command']['cmd_value'],
        ]);
    }

    private function requestOpenDoor()
    {
        $res = [
            'AcsRes' => 1,
            'ActIndex' => 0,
            'Time' => 1,
        ];
        $url = 'http://192.168.0.243/cdor.cgi?open=1&door=0';
        $authorization = 'Basic ' . base64_encode('admin:888888');
        $response_body = '';
        $http_code = 0;

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_HTTPHEADER => [
                    'Authorization: ' . $authorization,
                ],
            ]);
            $response_body = curl_exec($ch);
            if ($response_body === false) {
                $error_message = curl_error($ch);
                curl_close($ch);
                return api_output(1003, [], $error_message ?: 'Open door request failed');
            }
            $http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 10,
                    'header' => "Authorization: {$authorization}\r\n",
                ]
            ]);
            $response_body = @file_get_contents($url, false, $context);
            if ($response_body === false) {
                return api_output(1003, [], 'Open door request failed');
            }
            if (!empty($http_response_header) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches)) {
                $http_code = (int)$matches[1];
            }
        }

        if ($http_code !== 0 && ($http_code < 200 || $http_code >= 300)) {
            return api_output(1003, [
                'http_code' => $http_code,
                'response' => is_string($response_body) ? trim($response_body) : $response_body,
            ], 'Open door request failed');
        }

        return api_output(0, $res, 'Open door success');
    }

    public function httpCallback()
    {
        $request_data = $this->collectRequestData();
        fdump_api([
            'msg' => 'cloud access control callback',
            '_GET' => $_GET,
            '_POST' => $_POST,
            '_REQUEST' => $_REQUEST,
            'request' => $request_data,
            '_SERVER' => $_SERVER
        ], 'cloud_access_control/demo_callback', 1);

        $village_id = (int)($request_data['village_id'] ?? 0);
        if (empty($village_id)) {
            $village_id = (int)$this->matchVillageIdBySerial((string)($request_data['Serial'] ?? $request_data['serial'] ?? ''));
        }
        if (empty($village_id)) {
            return $this->renderDeviceJson(['Key' => (string)($request_data['Key'] ?? '')]);
        }

        $state = $this->readState($village_id);
        $method = trim((string)($request_data['method'] ?? ''));

        if ($method === 'GetStatus') {
            $response = $this->handleHeartbeat($state, $request_data);
            $this->writeState($village_id, $state);
            return $this->renderDeviceJson($response);
        }

        if (isset($request_data['IndexEvent']) && $request_data['IndexEvent'] !== '') {
            $this->appendLog($state, 'history_event', 'history swipe event', $request_data);
            $this->writeState($village_id, $state);
            return $this->renderRawJson(['IndexEvent' => (string)$request_data['IndexEvent']]);
        }

        if (isset($request_data['IndexAlarm']) && $request_data['IndexAlarm'] !== '') {
            $this->appendLog($state, 'history_alarm', 'history alarm event', $request_data);
            $this->writeState($village_id, $state);
            return $this->renderRawJson(['IndexAlarm' => (string)$request_data['IndexAlarm']]);
        }

        if ($method === 'SearchCardAcs') {
            $response = $this->handleSearchCardAcs($state, $request_data);
            $this->writeState($village_id, $state);
            return $this->renderDeviceJson($response);
        }

        $this->appendLog($state, 'unknown_request', 'unknown request', $request_data);
        $this->writeState($village_id, $state);
        return $this->renderDeviceJson(['Key' => (string)($request_data['Key'] ?? $state['config']['key'])]);
    }

    private function handleHeartbeat(array &$state, array $request_data)
    {
        $state['last_heartbeat'] = array_merge($request_data, [
            'received_at' => date('Y-m-d H:i:s')
        ]);

        if (!empty($request_data['Key'])) {
            $state['config']['key'] = (string)$request_data['Key'];
        }
        if (!empty($request_data['Serial'])) {
            $state['config']['serial'] = (string)$request_data['Serial'];
        }

        if (isset($request_data['IndexCmd']) && isset($request_data['CmdOK']) && !empty($state['pending_command'])) {
            if ((string)$request_data['IndexCmd'] === (string)($state['pending_command']['index_cmd'] ?? '')) {
                $state['pending_command']['status'] = ((string)$request_data['CmdOK'] === '1') ? 'done' : 'failed';
                $state['pending_command']['feedback_at'] = date('Y-m-d H:i:s');
                $this->appendLog(
                    $state,
                    'command_feedback',
                    ((string)$request_data['CmdOK'] === '1') ? 'command success' : 'command failed',
                    $request_data
                );
                if ((string)$request_data['CmdOK'] === '1') {
                    $state['last_command'] = $state['pending_command'];
                    $state['pending_command'] = [];
                }
            }
        }

        $response = [
            'Key' => (string)($request_data['Key'] ?? $state['config']['key'])
        ];

        if (
            !empty($state['pending_command']) &&
            ($state['pending_command']['status'] ?? '') === 'queued' &&
            (string)($state['pending_command']['serial'] ?? '') === (string)($request_data['Serial'] ?? '')
        ) {
            $response['IndexCmd'] = (string)$state['pending_command']['index_cmd'];
            $response['CmdValue'] = (string)$state['pending_command']['cmd_value'];
            $state['pending_command']['status'] = 'sent';
            $state['pending_command']['sent_at'] = date('Y-m-d H:i:s');
            $this->appendLog($state, 'heartbeat_with_command', 'heartbeat response with command', $response);
        } else {
            $this->appendLog($state, 'heartbeat', 'heartbeat response key only', $request_data);
        }

        return $response;
    }

    private function handleSearchCardAcs(array &$state, array $request_data)
    {
        $reader = isset($request_data['Reader']) ? intval($request_data['Reader']) : 0;
        $response = [
            'ActIndex' => (string)($reader & 0x01),
            'AcsRes' => '1',
            'Time' => (string)($state['config']['time'] ?: '1')
        ];

        $state['last_auth_request'] = array_merge($request_data, [
            'received_at' => date('Y-m-d H:i:s')
        ]);
        $this->appendLog($state, 'search_card_acs', 'search card acs', array_merge($request_data, $response));
        return $response;
    }

    private function collectRequestData()
    {
        $data = $_REQUEST;
        $input = $this->getRawRequestBody();
        if ($input !== '') {
            $json = json_decode($input, true);
            if (is_array($json)) {
                $data = array_merge($data, $json);
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

    private function writeRootRequestLog($scene, array $request_data = [], array $extra = [])
    {
        $log_file = rtrim(root_path(), '/\\') . DIRECTORY_SEPARATOR . 'cloud_access_control_request.log';
        $log_data = [
            'time' => date('Y-m-d H:i:s'),
            'scene' => (string)$scene,
            'method' => (string)($_SERVER['REQUEST_METHOD'] ?? ''),
            'uri' => (string)($_SERVER['REQUEST_URI'] ?? ''),
            'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            'raw_input' => $this->getRawRequestBody(),
            '_POST' => $_POST,
            '_REQUEST' => $_REQUEST,
            'request_data' => $request_data,
            'extra' => $extra,
        ];

        file_put_contents($log_file, json_encode($log_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function normalizeQueueValue($value)
    {
        return strtoupper(trim((string)$value));
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

    private function getVillageId()
    {
        return 3;
    }

    private function nextIndexCmd(array &$state)
    {
        $last = (int)($state['meta']['last_index_cmd'] ?? 1000);
        $next = $last + 1;
        if ($next <= 0) {
            $next = 1001;
        }
        $state['meta']['last_index_cmd'] = $next;
        return $next;
    }

    private function appendLog(array &$state, $event_type, $summary, array $payload = [])
    {
        array_unshift($state['logs'], [
            'log_id' => uniqid('', true),
            'event_type' => $event_type,
            'summary' => $summary,
            'payload' => $payload,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $state['logs'] = array_slice($state['logs'], 0, 30);
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
        $dir = runtime_path() . 'community/cloud_access_control_demo/';
        if (!is_dir($dir)) {
            return 0;
        }
        $files = glob($dir . 'village_*.json');
        if (empty($files)) {
            return 0;
        }
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            if (!empty($data['config']['serial']) && (string)$data['config']['serial'] === $serial) {
                if (preg_match('/village_(\d+)\.json$/', $file, $matches)) {
                    return (int)$matches[1];
                }
            }
        }
        return 0;
    }

    private function defaultState()
    {
        return [
            'config' => [
                'device_name' => '',
                'serial' => '',
                'key' => '',
                'time' => '1',
                'door' => '01',
            ],
            'pending_command' => [],
            'last_command' => [],
            'last_heartbeat' => [],
            'last_auth_request' => [],
            'logs' => [],
            'meta' => [
                'last_index_cmd' => 1000
            ]
        ];
    }

    private function renderDeviceJson(array $payload)
    {
        return $this->renderRawJson($this->filterDeviceResponseFields($payload));
    }

    private function renderRawJson(array $payload)
    {
        return json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    private function filterDeviceResponseFields(array $payload)
    {
        $allowed_fields = [
            'Key',
            'Now',
            'AcsRes',
            'ActIndex',
            'Time',
            'Reader',
            'LCD',
            'LCDTime',
            'Name',
            'Note',
            'Card',
            'Systime',
            'Voice',
            'QRCode',
            'OEM',
            'IndexCmd',
            'CmdValue',
            'CmdBase64',
        ];

        $filtered = [];
        foreach ($allowed_fields as $field) {
            if (!array_key_exists($field, $payload)) {
                continue;
            }
            if ($payload[$field] === null || $payload[$field] === '') {
                continue;
            }
            $filtered[$field] = (string)$payload[$field];
        }

        return $filtered;
    }
}
