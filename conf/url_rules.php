<?php

//正则关系
$regExps = [
	[
		'contain' => '',			//匹配，为空则不匹配
		'match' => '#shop-(\d+)',	//正则
		'matched' => '/packapp/plat/pages/shop_new/shopDetail/shopDetail?store_id=:1', // :1则匹配第一个
	],
	[
		'contain' => '',			//匹配，为空则不匹配
		'match' => '#goods-(\d+)-(\d+)',	//正则
		'matched' => '/packapp/plat/pages/shop_new/goodsDetail/goodsDetail?store_id=:1&product_id:2', // :1则匹配第一个
	],
	[
		'contain' => 'c=Merchant&a=shop',			//匹配，为空则不匹配
		'match' => 'Merchant&a=shop&store_id=(\d+)',	//正则
		'matched' => '/platn/pages/store/v1/home/index?store_id=:1', // :1则匹配第一个
	],
	[
		'contain' => 'pages/store/homePage',			//匹配，为空则不匹配
		'match' => 'pages\/store\/homePage\?store_id=(\d+)',	//正则
		'matched' => '/platn/pages/store/v1/home/index?store_id=:1', // :1则匹配第一个
	],
	[
		'contain' => 'my/store/index.html',			//匹配，为空则不匹配
		'match' => 'my\/store\/index.html\?store_id=(\d+)',	//正则
		'matched' => '/platn/pages/store/v1/home/index?store_id=:1', // :1则匹配第一个
	],
	[
		'contain' => 'c=Group&a=detail',			//匹配，为空则不匹配
		'match' => 'group_id=(\d+)',	//正则
		'matched' => '/platn/pages/group/v1/groupDetail/index?group_id=:1', // :1则匹配第一个
	],
	[
		'contain' => 'c=Groupnew&a=detail',			//匹配，为空则不匹配
		'match' => 'group_id=(\d+)',	//正则
		'matched' => '/platn/pages/group/v1/groupDetail/index?group_id=:1', // :1则匹配第一个
	],

];

//匹配关系
$containExps = [
	[
		'contain' => 'pages/shop_new/shopDetail/shopDetail',			//匹配，为空则不匹配
		'route'  => 'shopDetail',
		'params' => [
			'store_id' => ['store_id', 'shop_id']
		]
	],
	[
		'contain' => 'pages/shop_new/goodsDetail/goodsDetail',			//匹配，为空则不匹配
		'route'  => 'shopGoodsDetail',
		'params' => [
			'store_id' => ['store_id', 'shop_id'],
			'product_id' => ['product_id', 'id']
		]
	],
];

//合并返回
return [
	'regExps' 		=> $regExps,
	'containExps' 	=> $containExps,
];