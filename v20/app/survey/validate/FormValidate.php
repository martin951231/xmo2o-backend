<?php
namespace app\survey\validate;

use think\Validate;

class FormValidate extends Validate
{
    protected $rule = [
        'title' => 'require|max:255',
        'status' => 'in:1,2,3',
        'submit_user_type' => 'in:all,system_user,level_user',
	    'form_id' => 'require|number',
        'questions' => 'array'
    ];

    protected $message = [
        'title.require' => '问卷标题不能为空',
        'title.max' => '问卷标题不能超过255个字符',
        'status.in' => '状态值不正确',
        'questions.array' => '问题列表必须为数组',
	    'form_id.number' => '表单ID必须为数字',
        'form_id.require' => '表单ID不能为空',
    ];

    protected $scene = [
        'save' => ['title', 'status', 'questions', 'submit_user_type'],
        'detail' => ['form_id'],
        'change' => ['form_id', 'status'],
    ];
}
