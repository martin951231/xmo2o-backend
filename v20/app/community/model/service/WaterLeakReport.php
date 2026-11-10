<?php
namespace app\community\model\service;

class WaterLeakReport
{
    public static function decode($raw)
    {
        // Only quote bare uppercase alphanumeric ID tokens. Consume other JSON
        // strings intact so text inside a quoted value is never rewritten.
        $normalized = preg_replace_callback(
            '/("ID"\s*:\s*)([A-Z0-9]{1,32})(?=\s*[,}])|"(?:\\\\.|[^"\\\\])*"/',
            function ($match) {
                return isset($match[2]) ? $match[1] . '"' . $match[2] . '"' : $match[0];
            },
            $raw
        );
        $data = json_decode($normalized, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            throw new \InvalidArgumentException('上报格式错误，仅兼容ID省略双引号，其他字段须符合JSON格式');
        }
        if (!is_string($data['ID'] ?? null) || !preg_match('/^[A-Z0-9]{1,32}$/D', $data['ID'])) {
            throw new \InvalidArgumentException('ID必须为已登记的大写字母或数字ID');
        }
        return $data;
    }

    public static function parse($raw, $sensorId)
    {
        $data = self::decode($raw);
        if (!is_array($data) || !isset($data['ID']) || !is_scalar($data['ID']) || (string)$data['ID'] !== (string)$sensorId) {
            throw new \InvalidArgumentException('水浸设备ID不匹配或请求格式错误');
        }
        if (!array_key_exists('zhuangtai', $data)) { throw new \InvalidArgumentException('缺少zhuangtai，无法判断漏水状态，未执行声光控制'); }
        $value = $data['zhuangtai'];
        if (!(is_int($value) || is_float($value) || is_string($value)) || !is_numeric($value) || !in_array((float)$value, [0.0, 1.0], true)) {
            throw new \InvalidArgumentException('zhuangtai必须为0或1');
        }
        return (int)$value;
    }
}
