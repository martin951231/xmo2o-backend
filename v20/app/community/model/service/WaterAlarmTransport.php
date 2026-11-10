<?php
namespace app\community\model\service;

/** WK-902P/912P: Modbus RTU frames (including CRC) over a TCP stream. */
class WaterAlarmTransport
{
    private $socket;
    private $unit;
    private $timeout;

    public function __construct(array $config, $socket = null)
    {
        $this->unit = (int)$config['unit'];
        $this->timeout = (float)$config['timeout'];
        $this->socket = $socket ?: @stream_socket_client('tcp://' . $config['host'] . ':' . (int)$config['port'], $errno, $error, $this->timeout);
        if (!$this->socket) { throw new \RuntimeException('无法连接设备，请检查供电、网线及服务器到设备的网络'); }
        stream_set_blocking($this->socket, true);
    }

    public function close()
    {
        if (is_resource($this->socket)) { fclose($this->socket); }
        $this->socket = null;
    }

    public function __destruct() { $this->close(); }

    public static function crc($bytes)
    {
        $crc = 0xffff;
        for ($i = 0; $i < strlen($bytes); $i++) {
            $crc ^= ord($bytes[$i]);
            for ($j = 0; $j < 8; $j++) { $crc = ($crc & 1) ? (($crc >> 1) ^ 0xa001) : ($crc >> 1); }
        }
        return pack('v', $crc);
    }

    private function remaining($deadline)
    {
        $remaining = $deadline - microtime(true);
        if ($remaining <= 0) { throw new \RuntimeException('设备响应超时，执行结果未知，请刷新状态或再次发送停止指令'); }
        $seconds = (int)$remaining;
        stream_set_timeout($this->socket, $seconds, max(1, (int)(($remaining - $seconds) * 1000000)));
    }

    private function readExact($length, $deadline)
    {
        $data = '';
        while (strlen($data) < $length) {
            $this->remaining($deadline);
            $part = @fread($this->socket, $length - strlen($data));
            if ($part === false || $part === '') { throw new \RuntimeException('设备回复超时或连接中断，执行结果未知，请刷新状态或发送停止指令'); }
            $data .= $part;
        }
        return $data;
    }

    protected function exchange($pdu)
    {
        $deadline = microtime(true) + $this->timeout;
        $request = chr($this->unit) . $pdu;
        $request .= self::crc($request);
        $offset = 0;
        while ($offset < strlen($request)) {
            $this->remaining($deadline);
            $written = @fwrite($this->socket, substr($request, $offset));
            if (!$written) { throw new \RuntimeException('设备指令发送失败，执行结果未知'); }
            $offset += $written;
        }
        $head = $this->readExact(3, $deadline);
        $function = ord($head[1]);
        if ($function & 0x80) { $tailLength = 2; }
        elseif ($function === 3) {
            $tailLength = ord($head[2]) + 2;
            if ($tailLength > 252) { throw new \RuntimeException('设备回复长度不正确'); }
        } elseif ($function === 6 || $function === 16) { $tailLength = 5; }
        else { throw new \RuntimeException('设备回复功能码不正确'); }
        $frame = $head . $this->readExact($tailLength, $deadline);
        if (substr($frame, -2) !== self::crc(substr($frame, 0, -2))) { throw new \RuntimeException('设备回复CRC校验失败，执行结果未知'); }
        if (ord($frame[0]) !== $this->unit || ($function & 0x7f) !== ord($pdu[0])) { throw new \RuntimeException('设备回复与请求不匹配'); }
        if ($function & 0x80) {
            $errors = [1 => '不支持的功能', 2 => '寄存器地址无效', 3 => '参数值无效', 4 => '设备内部故障'];
            throw new \RuntimeException('设备拒绝指令：' . ($errors[ord($frame[2])] ?? '未知异常'));
        }
        return substr($frame, 1, -2);
    }

    public function readState($includeTts = false)
    {
        $reply = $this->exchange(pack('Cnn', 3, 1, 3));
        if (strlen($reply) !== 8 || ord($reply[1]) !== 6) { throw new \RuntimeException('设备状态回复长度不正确'); }
        $values = unpack('nvolume/nled/ntrack', substr($reply, 2));
        $state = [
            'volume' => $values['volume'], 'led' => $values['led'], 'track' => $values['track'],
            'alarm_active' => ($values['track'] & 255) > 0,
            'flash_active' => ($values['led'] & 255) > 0,
        ];
        if ($includeTts) {
            $reply = $this->exchange(pack('Cnn', 3, 0x21, 3));
            if (strlen($reply) !== 8 || ord($reply[1]) !== 6) { throw new \RuntimeException('TTS状态回复长度不正确'); }
            $tts = unpack('nstatus/nvolume/nled', substr($reply, 2));
            if ($tts['status'] > 2) { throw new \RuntimeException('TTS状态值不正确'); }
            $state['tts_status'] = $tts['status'];
            $state['tts_active'] = $tts['status'] === 2;
            $state['tts_volume'] = $tts['volume'];
            $state['tts_led'] = $tts['led'];
            $state['normal_volume'] = $state['volume'];
            $state['alarm_active'] = $state['alarm_active'] || $state['tts_active'];
            $state['flash_active'] = $state['flash_active'] || ($state['tts_active'] && ($tts['led'] & 255) > 0);
            if ($state['tts_active']) { $state['volume'] = $tts['volume']; }
        }
        return $state;
    }

    public function readModel()
    {
        $reply = $this->exchange(pack('Cnn', 3, 0, 1));
        if (strlen($reply) !== 4 || ord($reply[1]) !== 2) { throw new \RuntimeException('设备型号回复不正确'); }
        return unpack('nmodel', substr($reply, 2))['model'];
    }

    // The gateway accepts only the register operations used by this module.
    public function forward($pdu)
    {
        if (!is_string($pdu) || strlen($pdu) < 5 || strlen($pdu) > 206) { throw new \InvalidArgumentException('无效设备指令'); }
        $fields = unpack('Cfunction/naddress/nvalue', substr($pdu, 0, 5));
        $f = $fields['function']; $a = $fields['address']; $v = $fields['value'];
        $allowed = false;
        if ($f === 3 && strlen($pdu) === 5) { $allowed = ($a === 0 && $v === 1) || (($a === 1 || $a === 0x21) && $v === 3); }
        if ($f === 6 && strlen($pdu) === 5) {
            $allowed = (($a === 1 || $a === 0x22) && $v >= 1 && $v <= 31) || ($a === 0x1f && $v === 6) || ($a === 0x20 && $v === 1) || ($a === 0x23 && $v <= 1);
        }
        if ($f === 16 && strlen($pdu) >= 6 && ord($pdu[5]) === $v * 2 && strlen($pdu) === 6 + $v * 2) {
            $allowed = ($a === 1 && $v === 3) || ($a === 2 && ($v === 1 || $v === 2)) || ($a === 0x22 && $v === 2) || ($a === 0x3d && $v >= 1 && $v <= 100);
        }
        if (!$allowed) { throw new \InvalidArgumentException('不支持的设备指令'); }
        return $this->exchange($pdu);
    }

    public function writeRegisters($address, array $values)
    {
        $data = '';
        foreach ($values as $value) { $data .= pack('n', $value); }
        $pdu = pack('CnnC', 16, $address, count($values), strlen($data)) . $data;
        $reply = $this->exchange($pdu);
        if ($reply !== pack('Cnn', 16, $address, count($values))) { throw new \RuntimeException('设备写入确认不匹配，执行结果未知'); }
    }

    public function setVolume($volume)
    {
        $this->writeSingle(1, $volume);
    }

    public function writeSingle($address, $value)
    {
        $pdu = pack('Cnn', 6, $address, $value);
        if ($this->exchange($pdu) !== $pdu) { throw new \RuntimeException('设备音量确认不匹配，执行结果未知'); }
    }

    public function stopTts() { $this->writeSingle(0x20, 1); }

    public static function ttsPayload($text)
    {
        if (!is_string($text) || !preg_match('//u', $text) || trim($text) === '' || strlen($text) > 199 || preg_match('/[\x00-\x1f\x7f\[\]]/u', $text)) {
            throw new \InvalidArgumentException('播报内容须为1至199个UTF-8字节，不支持换行、控制字符或方括号');
        }
        $data = "\xff" . $text;
        // The manual's odd-length examples are non-standard. A trailing ASCII
        // space preserves speech and guarantees two bytes per register, <=200.
        if (strlen($data) % 2) { $data .= ' '; }
        return $data;
    }

    public function speak($text, $volume)
    {
        $data = self::ttsPayload($text);
        $this->writeSingle(0x1f, 6); // UTF-8, matching the encoded HTTP/DB text.
        $this->writeRegisters(0x22, [$volume, 1]); // TTS volume and flashing mode.
        $count = (int)(strlen($data) / 2);
        $reply = $this->exchange(pack('CnnC', 16, 0x3d, $count, strlen($data)) . $data);
        if ($reply !== pack('Cnn', 16, 0x3d, $count)) { throw new \RuntimeException('TTS播报确认不匹配，执行结果未知'); }
    }
}
