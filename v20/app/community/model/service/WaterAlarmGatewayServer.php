<?php
namespace app\community\model\service;

/** Single-device TCP ingress and a loopback-only authenticated control channel. */
class WaterAlarmGatewayServer
{
    private $config;
    private $gateway;
    private $allowedPeers;
    private $deviceListener;
    private $controlListener;
    private $deviceSocket;
    private $device;
    private $clients = [];
    private $owner;
    private $generation = 0;
    private $lastHeartbeat = 0;
    private $running = true;
    private $logger;
    private $lastRejectLog = 0;

    public function __construct(array $config, callable $logger = null)
    {
        $this->config = $config;
        $this->gateway = $config['gateway'];
        $this->logger = $logger;
        $peers = $this->gateway['allowed_peers'];
        $this->allowedPeers = is_array($peers) ? $peers : array_filter(array_map('trim', explode(',', $peers)));
        if (!$this->allowedPeers) { throw new \InvalidArgumentException('请配置 WATER_ALARM.ALLOWED_PEERS 为设备现场的公网出口IPv4，多个地址用逗号分隔'); }
        foreach ($this->allowedPeers as $peer) {
            if (!filter_var($peer, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) { throw new \InvalidArgumentException('ALLOWED_PEERS只能填写明确的IPv4地址'); }
        }
        if (strlen($this->gateway['secret']) < 32) { throw new \InvalidArgumentException('请配置至少32字符的 WATER_ALARM.GATEWAY_SECRET，Web与常驻服务必须相同'); }
        foreach (['listen_port', 'control_port'] as $key) {
            if ((int)$this->gateway[$key] < 1 || (int)$this->gateway[$key] > 65535) { throw new \InvalidArgumentException('接入端口配置不正确'); }
        }
        if ($this->gateway['listen_port'] == $this->gateway['control_port']) { throw new \InvalidArgumentException('设备端口与内部控制端口必须不同'); }
    }

    private function log($message)
    {
        if ($this->logger) { call_user_func($this->logger, date('Y-m-d H:i:s') . ' ' . $message); }
    }

    public function stop() { $this->running = false; }

    public function run()
    {
        try {
            $this->deviceListener = $this->listen($this->gateway['listen_host'], $this->gateway['listen_port']);
            // Never expose the control channel on the public interface.
            $this->controlListener = $this->listen('127.0.0.1', $this->gateway['control_port']);
            $this->log('设备监听 ' . $this->gateway['listen_host'] . ':' . $this->gateway['listen_port'] . '；内部控制 127.0.0.1:' . $this->gateway['control_port']);
            while ($this->running) {
                $this->expireClients();
                $read = [$this->deviceListener, $this->controlListener];
                if (is_resource($this->deviceSocket)) { $read[] = $this->deviceSocket; }
                foreach ($this->clients as $client) { $read[] = $client['socket']; }
                $write = null; $except = null;
                $ready = @stream_select($read, $write, $except, 1);
                if ($ready === false) { continue; }
                foreach ($read as $socket) {
                    if (!is_resource($socket)) { continue; }
                    if ($socket === $this->deviceListener) { $this->acceptDevice(); }
                    elseif ($socket === $this->controlListener) { $this->acceptControl(); }
                    elseif ($socket === $this->deviceSocket) {
                        // The RTU device is request/response only. Unsolicited or late
                        // bytes must not be mistaken for a future command's reply.
                        $data = @fread($socket, 1024);
                        $this->dropDevice($data === '' ? '设备连接已断开' : '收到非请求数据，请关闭设备自定义心跳包/注册包');
                    } else { $this->readControl((int)$socket); }
                }
                if ($this->device && $this->owner === null && microtime(true) - $this->lastHeartbeat >= max(5, (int)$this->gateway['heartbeat_seconds'])) {
                    try {
                        $this->checkModel($this->device->readModel());
                        $this->lastHeartbeat = microtime(true);
                    } catch (\Throwable $e) { $this->dropDevice('设备心跳失败：' . $e->getMessage()); }
                }
            }
        } finally {
            foreach (array_keys($this->clients) as $id) { $this->closeClient($id); }
            $this->dropDevice('接入服务停止');
            foreach ([$this->deviceListener, $this->controlListener] as $listener) { if (is_resource($listener)) { fclose($listener); } }
        }
    }

    private function listen($host, $port)
    {
        $listener = @stream_socket_server('tcp://' . $host . ':' . (int)$port, $errno, $error);
        if (!$listener) { throw new \RuntimeException('无法监听 ' . $host . ':' . $port . '，请检查端口占用和绑定地址'); }
        stream_set_blocking($listener, false);
        return $listener;
    }

    private function checkModel($model)
    {
        if (!in_array($model, $this->gateway['expected_models'], true)) { throw new \RuntimeException('设备型号不符合配置'); }
    }

    private function acceptDevice()
    {
        $socket = @stream_socket_accept($this->deviceListener, 0, $peer);
        if (!$socket) { return; }
        $ip = substr($peer, 0, strrpos($peer, ':'));
        if (!in_array($ip, $this->allowedPeers, true) || $this->device) {
            fclose($socket);
            if (time() - $this->lastRejectLog >= 10) {
                $this->log('拒绝设备连接 ' . $peer . '：来源未放行或已有设备在线');
                $this->lastRejectLog = time();
            }
            return;
        }
        $transport = null;
        try {
            $transport = new WaterAlarmTransport($this->config, $socket);
            $model = $transport->readModel();
            $this->log(
                '设备型号校验：实际型号=' . var_export($model, true)
                . '，允许型号=' . var_export($this->gateway['expected_models'], true)
            );
            $this->checkModel($model);
            $this->device = $transport;
            $this->deviceSocket = $socket;
            $this->generation++;
            $this->lastHeartbeat = microtime(true);
            $this->log('设备在线 ' . $peer . '，型号 ' . $model . '，连接代次 ' . $this->generation);
        } catch (\Throwable $e) {
            if ($transport) { $transport->close(); } elseif (is_resource($socket)) { fclose($socket); }
            $this->log('设备握手失败 ' . $peer . '：' . $e->getMessage());
        }
    }

    private function acceptControl()
    {
        $socket = @stream_socket_accept($this->controlListener, 0);
        if (!$socket) { return; }
        if (count($this->clients) >= 8) { fclose($socket); return; }
        stream_set_blocking($socket, false);
        $this->clients[(int)$socket] = ['socket' => $socket, 'buffer' => '', 'authenticated' => false, 'seq' => 0, 'generation' => 0, 'deadline' => microtime(true) + 3];
    }

    private function readControl($id)
    {
        if (!isset($this->clients[$id])) { return; }
        $chunk = @fread($this->clients[$id]['socket'], 4096);
        if ($chunk === false || $chunk === '') { $this->closeClient($id); return; }
        $this->clients[$id]['buffer'] .= $chunk;
        if (strlen($this->clients[$id]['buffer']) > 8192) { $this->closeClient($id); return; }
        while (isset($this->clients[$id]) && ($pos = strpos($this->clients[$id]['buffer'], "\n")) !== false) {
            $line = substr($this->clients[$id]['buffer'], 0, $pos);
            $this->clients[$id]['buffer'] = substr($this->clients[$id]['buffer'], $pos + 1);
            $request = json_decode($line, true);
            if (!is_array($request) || !isset($request['seq']) || !is_int($request['seq']) || $request['seq'] !== $this->clients[$id]['seq'] + 1) { $this->closeClient($id); return; }
            $this->clients[$id]['seq'] = $request['seq'];
            $this->handle($id, $request);
        }
    }

    private function handle($id, array $request)
    {
        $client = $this->clients[$id];
        $seq = $request['seq'];
        if (!$client['authenticated']) {
            if (($request['op'] ?? '') !== 'open' || !is_string($request['secret'] ?? null) || !hash_equals($this->gateway['secret'], $request['secret']) || ($request['device_key'] ?? '') !== $this->gateway['device_key']) {
                $this->reply($id, ['seq' => $seq, 'ok' => false, 'message' => '接入服务认证失败或设备标识不正确']);
                $this->closeClient($id); return;
            }
            if (!$this->device || $this->owner !== null) {
                $this->reply($id, ['seq' => $seq, 'ok' => false, 'message' => !$this->device ? '设备未连接生产服务器，请检查TCP CLIENT配置、网络和来源IP白名单' : '设备正在执行操作，请稍后重试']);
                $this->closeClient($id); return;
            }
            $this->owner = $id;
            $this->clients[$id]['authenticated'] = true;
            $this->clients[$id]['generation'] = $this->generation;
            $this->clients[$id]['deadline'] = microtime(true) + min(30, max(5, (int)$this->gateway['session_seconds']));
            $this->reply($id, ['seq' => $seq, 'ok' => true]);
            return;
        }
        if (!$this->device || $this->owner !== $id || $client['generation'] !== $this->generation || microtime(true) >= $client['deadline']) {
            $this->reply($id, ['seq' => $seq, 'ok' => false, 'message' => '设备连接已变更或控制会话已到期，请重新操作']);
            $this->closeClient($id); return;
        }
        $hex = $request['pdu'] ?? '';
        if (($request['op'] ?? '') !== 'exchange' || !is_string($hex) || strlen($hex) < 10 || strlen($hex) > 412 || strlen($hex) % 2 || !ctype_xdigit($hex)) {
            $this->reply($id, ['seq' => $seq, 'ok' => false, 'message' => '无效设备请求']);
            $this->closeClient($id); return;
        }
        try {
            $response = $this->device->forward(hex2bin($hex));
            $this->lastHeartbeat = microtime(true);
            $this->reply($id, ['seq' => $seq, 'ok' => true, 'pdu' => bin2hex($response)]);
        } catch (\InvalidArgumentException $e) {
            $this->reply($id, ['seq' => $seq, 'ok' => false, 'message' => $e->getMessage()]);
            $this->closeClient($id);
        } catch (\Throwable $e) {
            $this->reply($id, ['seq' => $seq, 'ok' => false, 'message' => $e->getMessage()]);
            // No transaction IDs exist in RTU. After timeout, discard this TCP
            // connection so a late reply can never confirm a subsequent command.
            $this->dropDevice('设备通信失败：' . $e->getMessage());
        }
    }

    private function reply($id, array $response)
    {
        if (!isset($this->clients[$id])) { return; }
        $socket = $this->clients[$id]['socket'];
        $packet = json_encode($response, JSON_UNESCAPED_UNICODE) . "\n";
        stream_set_blocking($socket, true);
        stream_set_timeout($socket, 1);
        $offset = 0;
        $deadline = microtime(true) + 1;
        while ($offset < strlen($packet) && microtime(true) < $deadline) {
            $written = @fwrite($socket, substr($packet, $offset));
            if (!$written) { break; }
            $offset += $written;
        }
        if ($offset !== strlen($packet)) { $this->closeClient($id); return; }
        stream_set_blocking($socket, false);
    }

    private function expireClients()
    {
        foreach ($this->clients as $id => $client) {
            if (microtime(true) >= $client['deadline']) { $this->closeClient($id); }
        }
    }

    private function closeClient($id)
    {
        if (isset($this->clients[$id])) {
            if (is_resource($this->clients[$id]['socket'])) { fclose($this->clients[$id]['socket']); }
            unset($this->clients[$id]);
        }
        if ($this->owner === $id) { $this->owner = null; }
    }

    private function dropDevice($reason)
    {
        if ($this->device) { $this->device->close(); $this->log($reason); }
        $this->device = null;
        $this->deviceSocket = null;
        $this->generation++;
        if ($this->owner !== null) { $this->closeClient($this->owner); }
    }
}
