<?php
// 这是系统自动生成的middleware定义文件
return [
    app\middleware\User::class,
    app\middleware\Http::class,//必须引入
    app\middleware\Agent::class,
//    'think\middleware\SessionInit'
];
