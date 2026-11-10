<?php
namespace app\community\model\service;

/** Local authenticated RPC; the persistent gateway owns the device socket. */
class WaterAlarmGatewayTransport extends WaterAlarmTransport
{
    private $control;
    private $rpcTimeout;
    private $sequence = 0;

    public function __construct(array $config)
    {
        $gateway = $config['gateway'];
        if (strlen($gateway['secret']) < 32) { throw new \RuntimeException('TCP接入服务密钥未配置，请联系管理员'); }
        $this->rpcTimeout = (float)$config['timeout'] + 3;
        $this->control = @stream_socket_client('tcp://127.0.0.1:' . (int)$gateway['control_port'], $errno, $error, 2);
        if (!$this->control) { throw new \RuntimeException('TCP接入服务未启动或不可用，请联系管理员'); }
        try {
            $this->request(['op' => 'open', 'secret' => $gateway['secret'], 'device_key' => $gateway['device_key']]);
        } catch (\Throwable $e) { $this->close(); throw $e; }
    }

    private function request(array $data)
    {
        if (!is_resource($this->control)) { throw new \RuntimeException('设备控制会话已断开，执行结果未知，请刷新或重新停止'); }
        $data['seq'] = ++$this->sequence;
        $packet = json_encode($data) . "\n";
        $deadline = microtime(true) + $this->rpcTimeout;
        $offset = 0;
        while ($offset < strlen($packet)) {
            $this->setDeadline($deadline);
            $count = @fwrite($this->control, substr($packet, $offset));
            if (!$count) { throw new \RuntimeException('接入服务连接中断，执行结果未知'); }
            $offset += $count;
        }
        $reply = '';
        while (substr($reply, -1) !== "\n") {
            $this->setDeadline($deadline);
            $part = @fgets($this->control, 2048);
            if ($part === false || $part === '') { throw new \RuntimeException('接入服务回复超时或断开，执行结果未知'); }
            $reply .= $part;
            if (strlen($reply) > 4096) { throw new \RuntimeException('接入服务回复长度异常'); }
        }
        $result = json_decode($reply, true);
        if (!is_array($result) || ($result['seq'] ?? null) !== $data['seq']) { throw new \RuntimeException('接入服务回复不匹配'); }
        if (empty($result['ok'])) { throw new \RuntimeException($result['message'] ?? '设备未连接，请确认设备TCP CLIENT配置'); }
        return $result;
    }

    private function setDeadline($deadline)
    {
        $left = $deadline - microtime(true);
        if ($left <= 0) { throw new \RuntimeException('接入服务回复超时，执行结果未知'); }
        stream_set_timeout($this->control, (int)$left, max(1, (int)(($left - (int)$left) * 1000000)));
    }

    protected function exchange($pdu)
    {
        try {
            $reply = $this->request(['op' => 'exchange', 'pdu' => bin2hex($pdu)]);
            $hex = $reply['pdu'] ?? '';
            if (!is_string($hex) || strlen($hex) < 2 || strlen($hex) > 512 || strlen($hex) % 2 || !ctype_xdigit($hex)) { throw new \RuntimeException('接入服务设备回复格式不正确'); }
            return hex2bin($hex);
        } catch (\Throwable $e) { $this->close(); throw $e; }
    }

    public function close()
    {
        if (is_resource($this->control)) { fclose($this->control); }
        $this->control = null;
    }
}
