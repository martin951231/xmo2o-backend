<?php
// 这是系统自动生成的middleware定义文件
return [
    app\middleware\ThirdAccess::class, // 三方校验
    app\middleware\User::class,
    app\middleware\Http::class,//必须引入
];
