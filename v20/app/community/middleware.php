<?php
return [
    app\middleware\Agent::class,
    app\middleware\User::class,
    app\middleware\Http::class,//必须引入
];