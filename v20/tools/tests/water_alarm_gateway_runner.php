<?php
// Test-only entry point: loopback listeners, no database, no physical device.
require dirname(__DIR__, 2) . '/app/community/model/service/WaterAlarmTransport.php';
require dirname(__DIR__, 2) . '/app/community/model/service/WaterAlarmGatewayServer.php';
$config = [
    'unit' => 1, 'timeout' => 0.3,
    'gateway' => [
        'listen_host' => '127.0.0.1', 'listen_port' => (int)$argv[1],
        'control_port' => (int)$argv[2], 'secret' => str_repeat('test-only-', 5),
        'allowed_peers' => $argv[3] ?? '127.0.0.1', 'device_key' => 'primary',
        'expected_models' => [902, 912], 'heartbeat_seconds' => 5, 'session_seconds' => 5,
    ],
];
try {
    (new \app\community\model\service\WaterAlarmGatewayServer($config, function ($line) { echo $line . PHP_EOL; }))->run();
} catch (Throwable $e) { fwrite(STDERR, $e->getMessage()); exit(1); }
