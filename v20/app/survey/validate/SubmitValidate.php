<?php

namespace app\survey\validate;

use think\Validate;

class SubmitValidate extends Validate
{
	protected $rule = [
		'form_id' => 'require|integer',
		'answers' => 'array',
	];

	protected $message = [
		'form_id.require' => '问卷ID不能为空',
		'form_id.integer' => '问卷ID必须为整数',
		'answers.array' => '答案列表必须为数组',
	];

	protected $scene = [
		'add' => ['form_id', 'answers'],
		'detail' => ['form_id'],
	];
}
