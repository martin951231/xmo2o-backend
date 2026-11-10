<?php
// Device destinations and gateway credentials are server-side configuration only.
return [
    'mode' => env('water_alarm.mode', 'gateway'), // gateway: device TCP CLIENT; direct: old LAN mode
    // Keep the original endpoint as the stable database identity for this device.
    'host' => '192.168.0.80',
    'port' => 123,
    'unit' => 1,
    'timeout' => 2,
    'default_volume' => 1,
    'leak_sensor_id' => env('water_alarm.leak_sensor_id', '1111'),
    'gateway' => [
        'listen_host' => env('water_alarm.listen_host', '0.0.0.0'),
        'listen_port' => (int)env('water_alarm.listen_port', 32986),
        'control_port' => (int)env('water_alarm.control_port', 32987),
        'secret' => (string)env('water_alarm.gateway_secret', ''),
        // Comma-separated site public IPv4 addresses. Empty means refuse startup.
        'allowed_peers' => (string)env('water_alarm.allowed_peers', ''),
        'device_key' => 'primary',
        'expected_models' => [510, 902, 912],
        'heartbeat_seconds' => 15,
        'session_seconds' => 30,
    ],
];
