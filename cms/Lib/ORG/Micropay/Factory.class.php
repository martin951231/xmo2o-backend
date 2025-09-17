<?php

namespace Micropay;

use Micropay\Type\Upay;
use Micropay\Type\Wechat;
use Micropay\Type\Alipay;

spl_autoload_register(array('Micropay\Factory', 'autoLoad'));

class Factory
{
    public static function getInstance($type, $data = [], $orderInfo = [])
    {
        if ($type == 'Wechat') {
            return new Wechat($data, $orderInfo);
        } else if ($type == 'Alipay') {
            return new Alipay($data, $orderInfo);
        } else if ($type == 'Upay') {
            return new Upay($data, $orderInfo);
        } else {
            throw new \Exception('支付方式不存在');
        }
    }

    public static function autoLoad($className)
    {
        $split = explode('\\', $className);
        $fileName = LIB_PATH . 'ORG/' . implode($split, '/') . '.class.php';
        if (file_exists($fileName)) {
            require_once $fileName;
        }
    }
}
