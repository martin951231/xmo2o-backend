<?php

use think\facade\Route;

//对外接口：
Route::group('/v1/delivery/', function (){
	//订单计费接口
	Route::post('valuating', 'maiyatian/api.Maiyatian/valuating');
	//下单接口
	Route::post('send', 'maiyatian/api.Maiyatian/send');
	//添加小费接口
	Route::post('tips', 'maiyatian/api.Maiyatian/tips');
	//取消配送
	Route::post('cancel', 'maiyatian/api.Maiyatian/cancel');
	//查询配送详情
	Route::post('query_info', 'maiyatian/api.Maiyatian/queryInfo');
	//获取骑手当前位置
	Route::post('rider_location', 'maiyatian/api.Maiyatian/riderLocation');
	//批量获取骑手当前位置
	Route::post('multi_rider_locations', 'maiyatian/api.Maiyatian/multiRiderLocations');
	//授权解绑
	Route::post('token_unbind', 'maiyatian/api.Maiyatian/tokenUnbind');
	//查询当前账号余额
	Route::post('balance', 'maiyatian/api.Maiyatian/balance');
	//在线充值链接
	Route::post('recharge_url', 'maiyatian/api.Maiyatian/rechargeUrl');
});

Route::post('v1/save_recharge_order', 'maiyatian/api.Recharge/saveRechargeOrder');
Route::post('v1/merchant', 'maiyatian/api.Recharge/merchant');