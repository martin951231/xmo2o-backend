<?php
declare (strict_types=1);

namespace app\classify\validate;

use think\Validate;

class ClassifyValidate extends Validate
{
    /**
     * 定义验证规则
     * @var array
     */
    protected $rule = [
        'classify_fid' => 'require|number',
    ];

    /**
     * 定义错误信息
     * @var array
     */
    protected $message = [
        'classify_fid.require' => '分类ID不可为空！',

    ];

    /**
     * @var array
     */
    protected $scene = [
        'api_classify_user_input_list' => ['classify_fid'],
    ];

}
