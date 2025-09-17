<?php
use think\facade\Route;
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

Route::rule('createKey','api/index/createKey'); #路由访问方式：http://mallbook.com/createKey
Route::rule('callback','api/index/callback');
Route::rule('register','api/index/register');
Route::rule('bindAccount','api/index/bindAccount');
Route::rule('unBindAccount','api/index/unBindAccount');
Route::rule('fileUpload','api/index/fileUpload');
Route::rule('registerFront','api/index/registerFront');
//Route::rule('paymentOrder','/api/index/paymentOrder');
Route::rule('refund','api/index/refund');
Route::rule('receive','api/index/receive');
Route::rule('withdraw','api/index/withdraw');
Route::rule('deposit','api/index/deposit');
Route::rule('transfer','api/index/transfer');
Route::rule('complete','api/index/complete');
Route::rule('fastBindApply','api/index/fastBindApply');
Route::rule('fastBindConfirm','api/index/fastBindConfirm');
Route::rule('fastUnbind','api/index/fastUnbind');
Route::rule('fastpayConfirm','api/index/fastpayConfirm');
Route::rule('fastpaySendSms','api/index/fastpaySendSms');
Route::rule('payOrderClose','api/index/payOrderClose');
Route::rule('queryUser','api/index/queryUser');
Route::rule('queryOrder','api/index/queryOrder');
Route::rule('queryBalance','api/index/queryBalance');
Route::rule('queryFastCard','api/index/queryFastCard');

