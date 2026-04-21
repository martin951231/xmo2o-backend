<?php

namespace app\community\model\service\Device\CloudPlusTypeC;

class TcpCloudCCmd extends TcpCloudCBase
{
    public static function command2C($door)
    {
        return self::buildFrame(['02', '00', '2C', '00', Tool::normalizeDoor($door), '00', '00']);
    }

    public static function command2E($door)
    {
        return self::buildFrame(['02', '00', '2E', '00', Tool::normalizeDoor($door), '00', '00']);
    }

    public static function command2D($door)
    {
        return self::buildFrame(['02', '00', '2D', '00', Tool::normalizeDoor($door), '00', '00']);
    }

    public static function command2F($door, $status)
    {
        $status = (string)$status === '1' ? '01' : '00';
        return self::buildFrame(['02', '00', '2F', '00', Tool::normalizeDoor($door), '01', '00', $status]);
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
        return self::buildFrame(['02', '00', '07', '00', '00', $year, $month, $day, $hour, $minute, $second, $week]);
    }
}