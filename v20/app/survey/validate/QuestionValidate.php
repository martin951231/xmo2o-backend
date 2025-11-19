<?php
namespace app\survey\validate;

use think\Validate;

class QuestionValidate extends Validate
{
    /**
     * 定义验证规则
     * @var array
     */
    protected $rule = [
        'title'       => 'require|max:200',
        'type'        => 'require|in:radio,checkbox,text,textarea,select,image,rating,file,areaAddress,phone,name,sex',
        'options'     => 'array',
        'placeholder' => 'max:200',
    ];

    /**
     * 定义错误信息
     * @var array
     */
    protected $message = [
        'title.require'      => '问题标题不能为空',
        'title.max'          => '问题标题不能超过200个字符',
        'type.require'       => '问题类型必须指定',
        'type.in'            => '问题类型不支持',
        'placeholder.max'    => '占位符不能超过200个字符',
    ];

    /**
     * 定义验证场景
     * @var array
     */
    protected $scene = [
        'add'  => ['title', 'type', 'options', 'placeholder'],
    ];
}
