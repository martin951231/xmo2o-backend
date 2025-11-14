<?php


namespace app\classify\controller\api;


use app\classify\model\service\ClassifyService;
use app\classify\validate\ClassifyValidate;
use app\http\exceptions\ParametersException;
use think\helper\Str;

class ClassifyUserInputController
{
    //region 参数验证
    const STATUS_OK = 1000;//状态码 正常
    /**
     * 参数验证
     * @param $scenario
     * @param array $param
     * @param string $method
     * @return array|mixed
     */
    protected function validateParameter($method = 'post',array $params = [], string $scenario = '') : array
    {
        empty($params) && $params = input($method.'.');
        $validate = validate(ClassifyValidate::class);
        if (!$validate->scene($scenario ?: 'api_'. Str::snake(request()->action()))->check($params)) {
            throw new ParametersException($validate->getError());
        }

        return $params;
    }
    //endregion

    /**
     * @return \think\response\Json
     */
    public function classifyUserInputList()
    {
        $params = $this->validateParameter();
        $limit = 5;//默认显示5条
        $classifyFid = $params['classify_fid'];

        $data = (new ClassifyService())->classifyUserInputList($classifyFid, $limit);

        return api_output(self::STATUS_OK, $data);
    }

   

}