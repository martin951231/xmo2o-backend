<?php

namespace app\classify\controller\platform;


use app\common\controller\CommonBaseController;
use app\http\exceptions\ParametersException;
use think\helper\Str;


class ClassifyBaseController extends CommonBaseController
{
    const STATUS_OK = 1000;//状态码 正常
    protected $classifyValidate;
    
    public function initialize()
    {
        parent::initialize();
    }

    public function checkLogin()
    {
        if ($this->request->log_uid < 1) {
            throw_exception(L_('未登录'), 1002);
        }
    }

    //region 参数验证

    /**
     * 参数验证
     * @param $scenario
     * @param array $param
     * @param string $method
     * @return array|mixed
     */
    protected function validateParameter(string $scenario = '', array $params = [], $method = 'post'): array
    {
        empty($params) && $params = input($method . '.');
        $validate = validate($this->classifyValidate);
        if (!$validate->scene($scenario ?: Str::snake(request()->action()))->check($params)) {
            throw new ParametersException(L_($validate->getError()));
        }

        return $params;
    }
}
