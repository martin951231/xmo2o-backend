<?php

namespace app\community\model\service\Device\CloudPlusTypeC;

class TcpCloudCCmd extends TcpCloudCBase
{
    private static function buildCommandFrame($command, $door, array $body)
    {
        $body_length = count($body);
        $frame = array_merge([
            '02',
            '00',
            Tool::normalizeFrameByte($command),
            '00',
            Tool::normalizeDoor($door),
            Tool::normalizeHexByte($body_length % 256),
            Tool::normalizeHexByte(intdiv($body_length, 256)),
        ], $body);

        return self::buildFrame($frame);
    }

    public static function command2C($door)
    {
        return self::buildCommandFrame('2C', $door, []);
    }

    public static function command2E($door)
    {
        return self::buildCommandFrame('2E', $door, []);
    }

    public static function command2D($door)
    {
        return self::buildCommandFrame('2D', $door, []);
    }

    public static function command2F($door, $status)
    {
        $status = (string)$status === '1' ? '01' : '00';
        return self::buildCommandFrame('2F', $door, [$status]);
    }

    public static function command07()
    {
        $year = date('y');
        $month = date('m');
        $day = date('d');
        $hour = date('H');
        $minute = date('i');
        $second = date('s');
        $week = date('w');
        return self::buildCommandFrame('07', '00', [$year, $month, $day, $hour, $minute, $second, $week]);
    }

    public static function commandC1($door, array $payload = [])
    {
        $card_no = Tool::digitsOnly($payload['CardNo'] ?? $payload['card_no'] ?? $payload['Card'] ?? $payload['card'] ?? $payload['code'] ?? '');
        $china_card = strtoupper(trim((string)($payload['ChinaCard'] ?? $payload['china_card'] ?? $payload['id_card'] ?? $payload['identity_card'] ?? '')));
        $qrcode = trim((string)($payload['QRCode'] ?? $payload['qrcode'] ?? $payload['qr_code'] ?? ''));
        $pin = Tool::digitsOnly($payload['Pin'] ?? $payload['pin'] ?? '');
        $tz = $payload['TZ'] ?? $payload['tz'] ?? 65535;
        $index = $payload['Index'] ?? $payload['index'] ?? 0;
        $start_time = $payload['StartTime'] ?? $payload['start_time'] ?? '2000-01-01 00:00';
        $end_time = $payload['EndTime'] ?? $payload['end_time'] ?? '2099-12-31 23:59';
        $name = trim((string)($payload['Name'] ?? $payload['name'] ?? ''));
        if ((string)$tz === '') {
            $tz = 65535;
        }
        if (trim((string)$start_time) === '') {
            $start_time = '2000-01-01 00:00';
        }
        if (trim((string)$end_time) === '') {
            $end_time = '2099-12-31 23:59';
        }

        $body = array_merge(
            Tool::decimalToLittleEndianBytes($index, 3),
            Tool::decimalToLittleEndianBytes($card_no, 4),
            Tool::stringToFixedBytes($qrcode, 9),
            Tool::stringToFixedBytes($china_card, 18),
            Tool::decimalToBCDFixedBytes($pin, 4),
            Tool::decimalToLittleEndianBytes($tz, 2),
            Tool::dateTimeToCommandBytes($start_time, false),
            Tool::dateTimeToCommandBytes($end_time, true),
            array_fill(0, 5, '00'),
            Tool::gb2312ToFixedBytes($name, 8)
        );

        return self::buildCommandFrame('C1', $door, $body);
    }
}
