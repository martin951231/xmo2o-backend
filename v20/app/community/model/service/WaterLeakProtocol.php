<?php
namespace app\community\model\service;

/** 塔石 V1.5：读取 0x2380 水浸状态，Modbus RTU 帧。 */
class WaterLeakProtocol
{
    private static function crc($bytes)
    {
        $crc = 0xffff;
        for ($i = 0; $i < strlen($bytes); $i++) {
            $crc ^= ord($bytes[$i]);
            for ($bit = 0; $bit < 8; $bit++) {
                $crc = ($crc & 1) ? (($crc >> 1) ^ 0xa001) : ($crc >> 1);
            }
        }
        return pack('v', $crc);
    }

    public static function readRequest($unit = 1)
    {
        if (!is_int($unit) || $unit < 1 || $unit > 247) {
            throw new \InvalidArgumentException('Modbus 设备地址必须为 1 至 247');
        }
        $frame = pack('CCnn', $unit, 3, 0x2380, 1);
        return $frame . self::crc($frame);
    }

    public static function parseResponse($frame, $unit = 1)
    {
        self::readRequest($unit);
        if (strlen($frame) < 5 || substr($frame, -2) !== self::crc(substr($frame, 0, -2))) {
            throw new \RuntimeException('水浸设备回复不完整或 CRC 校验失败');
        }
        if (ord($frame[0]) !== $unit) {
            throw new \RuntimeException('水浸设备回复地址与请求不符');
        }
        if (ord($frame[1]) === 0x83 && strlen($frame) === 5) {
            throw new \RuntimeException('水浸设备返回 Modbus 异常码：' . ord($frame[2]));
        }
        if (strlen($frame) !== 7 || ord($frame[1]) !== 3 || ord($frame[2]) !== 2) {
            throw new \RuntimeException('水浸设备回复功能码或长度不正确');
        }
        $value = unpack('nvalue', substr($frame, 3, 2))['value'];
        // 文档明确 0 为无水；其他值保留原始值，不擅自推断告警状态。
        return [
            'register' => '0x2380',
            'raw_value' => $value,
            'state' => $value === 0 ? 'dry' : 'unknown',
            'state_text' => $value === 0 ? '无水' : '待确认的设备状态值',
            'response_hex' => strtoupper(bin2hex($frame)),
        ];
    }
}
