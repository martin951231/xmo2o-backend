<?php

$database_config = include( __DIR__ . '/../../conf/db.php');

return [
    // 默认使用的数据库连接配置
    'default'         => env('database.driver', 'mysql'),

    // 自定义时间查询规则
    'time_query_rule' => [],

    // 自动写入时间戳字段
    // true为自动识别类型 false关闭
    // 字符串则明确指定时间字段类型 支持 int timestamp datetime date
    'auto_timestamp'  => false,

    // 时间字段取出后的默认时间格式
    'datetime_format' => false,

    // 数据库连接配置信息
    'connections'     => [
        'mysql' => [
            // 数据库类型
            'type'              => 'mysql',
            // 服务器地址
            'hostname'          => $database_config['DB_HOST'],
            // 数据库名
            'database'          => $database_config['DB_NAME'],
            // 用户名
            'username'          => $database_config['DB_USER'],
            // 密码
            'password'          => $database_config['DB_PWD'],
            // 端口
            'hostport'          => $database_config['DB_PORT'],
            // 数据库连接参数
            'params'            => [],
            // 数据库编码默认采用utf8
            'charset'           => 'utf8mb4',
            // 数据库表前缀
            'prefix'            => $database_config['DB_PREFIX'],

            // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
            'deploy'            => $database_config['DB_DEPLOY_TYPE']??0,
            // 数据库读写是否分离 主从式有效
            'rw_separate'       => $database_config['DB_RW_SEPARATE']??false,
            // 读写分离后 主服务器数量
            'master_num'        => 1,
            // 指定从服务器序号
            'slave_no'          => '',
            // 是否严格检查字段是否存在
            'fields_strict'     => true,
            // 是否需要断线重连
            'break_reconnect'   => false,
            // 监听SQL
            'trigger_sql'       => env('app_debug', false),
            // 开启字段缓存
            'fields_cache'      => false,
            // 字段缓存路径
            'schema_cache_path' => app()->getRuntimePath() . 'schema' . DIRECTORY_SEPARATOR,
        ],

        // 更多的数据库配置信息
    ],
];
