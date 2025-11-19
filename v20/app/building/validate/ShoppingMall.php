<?php

namespace app\building\validate;

use think\Validate;

class ShoppingMall extends Validate
{
	protected $rule = [
		'name|商城名称' => 'require',
		'short_name|缩略名称' => 'require',
		'lng|经度' => 'require',
		'lat|纬度' => 'require',
		'address|详细地址' => 'require',
		'phone|联系电话' => 'require',
		'area|占地面积' => 'require',
		'status|状态' => 'require|in:0,1',
		'admin_ids|管理员' => 'require|array',
	];

	protected $message = [
		
	];
	
	protected $scene = [
		'save_shopping_mall' => ['name', 'short_name', 'lng', 'lat', 'address', 'phone', 'area', 'admin_ids', 'status'],
	];
}