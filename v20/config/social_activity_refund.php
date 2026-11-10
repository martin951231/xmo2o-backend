<?php
return [
    // 暂停自动查单兜底；启用前需配置队列消费者。
    'auto_query_enabled' => false,
    // 可选兼容项；优先复用原支付单或系统已有公钥，不要求新增配置。
    'public_key' => env('allinpay.refund_public_key', ''),
    'notify_url' => env('allinpay.refund_notify_url', ''),
    'test' => env('allinpay.refund_test', false),
];
