<?php

namespace app\community\model\service\Device\CloudPlusTypeC;

class Tool
{
    public static function normalizeHexByte($value)
    {
        if (is_int($value) || ctype_digit((string)$value)) {
            return strtoupper(str_pad(dechex((int)$value), 2, '0', STR_PAD_LEFT));
        }

        $hex = strtoupper(trim((string)$value));
        $hex = str_replace('0X', '', $hex);
        return str_pad($hex, 2, '0', STR_PAD_LEFT);
    }

    public static function bytesToHex(array $bytes)
    {
        $normalized = [];
        foreach ($bytes as $byte) {
            $normalized[] = self::normalizeHexByte($byte);
        }
        return implode('', $normalized);
    }

    public static function checksum(array $bytes)
    {
        $checksum = 0;
        foreach ($bytes as $byte) {
            $checksum ^= hexdec(self::normalizeHexByte($byte));
        }
        return self::normalizeHexByte($checksum);
    }

    public static function normalizeDoor($door)
    {
        $door = trim((string)$door);
        if ($door === '') {
            $door = '01';
        }
        if (ctype_digit($door)) {
            return strtoupper(str_pad(dechex((int)$door), 2, '0', STR_PAD_LEFT));
        }
        return self::normalizeHexByte($door);
    }
}