<?php

$database_config = include( __DIR__ . '/../../conf/redis.php');

return [
    // redis连接配置信息
    'connections'     => [
            // 服务器地址
            'host'          => $database_config['host'],
            // 端口
            'port'          => $database_config['port'],
            // 密码
            'password'          => $database_config['password'],
            'select'          => $database_config['select'],
            'timeout'          => $database_config['timeout'],
            'expire'          => $database_config['expire'],
            'persistent'          => $database_config['persistent'],
            'prefix'          => $database_config['prefix'],
            'tag_prefix'          => $database_config['tag_prefix'],
            'serialize'          => $database_config['serialize'],
            // redis连接参数
    ],
];
