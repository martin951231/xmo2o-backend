<?php
//  创建客户端
$client = new \Swoole\Client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

//  连接服务器事件
$client->on('connect', function (\Swoole\Client $client) {
    $client->send('我来了');
});

//  接收消息事件
$client->on('receive', function (\Swoole\Client $client, $data) {
    echo $data . PHP_EOL;
});

//  出错事件
$client->on('error', function (\Swoole\Client $client) {
    echo '出错了' . PHP_EOL;
});

//  关闭连接事件
$client->on('close', function (\Swoole\Client $client) {
    echo '关闭了' . PHP_EOL;
});

//  连接到服务器
$client->connect('127.0.0.1', 9001);

//  关闭连接
$client->close();