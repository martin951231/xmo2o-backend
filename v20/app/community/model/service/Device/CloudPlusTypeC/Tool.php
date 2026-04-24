<?php

namespace app\community\model\service\Device\CloudPlusTypeC;

class Tool
{
    public static function normalizeFrameByte($value)
    {
        if (is_string($value)) {
            $hex = strtoupper(trim($value));
            $hex = str_replace('0X', '', $hex);
            if (preg_match('/^[0-9A-F]{1,2}$/', $hex)) {
                return str_pad($hex, 2, '0', STR_PAD_LEFT);
            }
        }

        return self::normalizeHexByte($value);
    }

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
            $normalized[] = self::normalizeFrameByte($byte);
        }
        return implode('', $normalized);
    }

    public static function checksum(array $bytes)
    {
        $checksum = 0;
        foreach ($bytes as $byte) {
            $checksum ^= hexdec(self::normalizeFrameByte($byte));
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

    public static function stringToBytes($value)
    {
        $value = (string)$value;
        $bytes = [];
        $length = strlen($value);
        for ($i = 0; $i < $length; $i++) {
            $bytes[] = self::normalizeHexByte(ord($value[$i]));
        }
        return $bytes;
    }

    public static function stringToFixedBytes($value, $length)
    {
        $bytes = self::stringToBytes($value);
        if (count($bytes) > $length) {
            return array_slice($bytes, 0, $length);
        }
        while (count($bytes) < $length) {
            $bytes[] = '00';
        }
        return $bytes;
    }

    public static function gb2312ToFixedBytes($value, $length)
    {
        $value = (string)$value;
        if ($value !== '' && function_exists('iconv')) {
            $encoded = @iconv('UTF-8', 'GB2312//IGNORE', $value);
            if ($encoded !== false) {
                $value = $encoded;
            }
        }
        return self::stringToFixedBytes($value, $length);
    }

    public static function decimalToLittleEndianBytes($value, $length)
    {
        $value = self::digitsOnly($value);
        $value = ltrim($value, '0');
        if ($value === '') {
            return array_fill(0, $length, '00');
        }

        $hex = self::decimalStringToHex($value);
        if (strlen($hex) % 2 !== 0) {
            $hex = '0' . $hex;
        }

        $bytes = array_reverse(str_split($hex, 2));
        if (count($bytes) > $length) {
            $bytes = array_slice($bytes, 0, $length);
        }
        while (count($bytes) < $length) {
            $bytes[] = '00';
        }

        return array_map([self::class, 'normalizeFrameByte'], $bytes);
    }

    public static function decimalToBCDFixedBytes($value, $length)
    {
        $value = self::digitsOnly($value);
        if ($value === '') {
            return array_fill(0, $length, 'FF');
        }

        if (strlen($value) % 2 !== 0) {
            $value .= 'F';
        }

        $bytes = str_split(strtoupper($value), 2);
        if (count($bytes) > $length) {
            $bytes = array_slice($bytes, 0, $length);
        }
        while (count($bytes) < $length) {
            $bytes[] = 'FF';
        }

        return array_map([self::class, 'normalizeFrameByte'], $bytes);
    }

    public static function dateTimeToCommandBytes($value, $use_end_default = false)
    {
        $digits = self::normalizeCommandDateDigits($value, $use_end_default);
        $bytes = [];
        for ($i = 0; $i < 10; $i += 2) {
            $bytes[] = self::normalizeHexByte((int)substr($digits, $i, 2));
        }
        return $bytes;
    }

    public static function digitsOnly($value)
    {
        return preg_replace('/\D+/', '', trim((string)$value));
    }

    private static function normalizeCommandDateDigits($value, $use_end_default)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return $use_end_default ? '9912312359' : '0001010000';
        }

        if (preg_match('/^\d{10}$/', $value)) {
            return $value;
        }
        if (preg_match('/^\d{12}$/', $value)) {
            return substr($value, 2, 10);
        }
        if (preg_match('/^\d{14}$/', $value)) {
            return substr($value, 2, 10);
        }

        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return date('ymdHi', $timestamp);
        }

        return $use_end_default ? '9912312359' : '0001010000';
    }

    private static function decimalStringToHex($value)
    {
        $value = ltrim((string)$value, '0');
        if ($value === '') {
            return '0';
        }

        if (function_exists('base_convert')) {
            $hex = strtoupper(base_convert($value, 10, 16));
            if ($hex !== '') {
                return $hex;
            }
        }

        $hex = '';
        while ($value !== '0') {
            list($value, $remainder) = self::divideDecimalString($value, 16);
            $hex = strtoupper(dechex($remainder)) . $hex;
        }

        return $hex;
    }

    private static function divideDecimalString($value, $divisor)
    {
        $quotient = '';
        $remainder = 0;
        $length = strlen($value);
        for ($i = 0; $i < $length; $i++) {
            $remainder = $remainder * 10 + (int)$value[$i];
            $digit = intdiv($remainder, $divisor);
            if ($quotient !== '' || $digit > 0) {
                $quotient .= (string)$digit;
            }
            $remainder %= $divisor;
        }

        if ($quotient === '') {
            $quotient = '0';
        }

        return [$quotient, $remainder];
    }
}
