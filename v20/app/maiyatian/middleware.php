<?php

return [
	app\middleware\User::class,
	app\middleware\Agent::class,
	app\middleware\Http::class,//必须引入
];
