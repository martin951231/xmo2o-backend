<?php


namespace service;


class BaseService
{
    protected static $err_msg = [];
    protected static $err_code = 0;

    public static function _err($msg = '系统错误', $err_code = 0)
    {
        self::$err_msg[] = L_($msg);
        self::$err_code = $err_code;
        return false;
    }

    public static function getLastErrMsg()
    {
        return end(self::$err_msg);
    }
}