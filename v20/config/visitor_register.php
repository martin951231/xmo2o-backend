<?php
return [
    // 32 random bytes, base64 encoded. Configure on the server; never commit the key.
    'encryption_key' => getenv('VISITOR_REGISTER_ENCRYPTION_KEY') ?: env('visitor_register.encryption_key', ''),
    'forms' => [
        'visitor' => ['name' => '来访人员登记表', 'code' => 'XC-ZX-05', 'page' => 'pages/visitorRegister/person'],
        'vehicle' => ['name' => '外来车辆出入登记表', 'code' => 'XC-ZX-06', 'page' => 'pages/visitorRegister/vehicle'],
    ],
];
