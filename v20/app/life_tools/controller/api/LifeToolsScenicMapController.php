<?php
declare (strict_types = 1);

namespace app\life_tools\controller\api;

use app\http\exceptions\ParametersException;
use app\life_tools\model\service\LifeToolsScenicMapService;
use app\life_tools\validate\merchant\LifeToolsScenicMap as LifeToolsScenicMapValidate;
use think\helper\Str;

class LifeToolsScenicMapController extends ApiBaseController
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
    private function validateParameter($method = 'post',array $params = [], string $scenario = '') : array
    {
        empty($params) && $params = input($method.'.');
        $validate = validate(LifeToolsScenicMapValidate::class);
        if (!$validate->scene($scenario ?: 'api_'. Str::snake(request()->action()))->check($params)) {
            throw new ParametersException($validate->getError());
        }

        return $params;
    }
    //endregion

    //region 景区地图

    public function scenicMapCategory()
    {
        $params = $this->validateParameter();

        $data = app(LifeToolsScenicMapService::class)->scenicMapCategory($params);

        return api_output(self::STATUS_OK, $data);
    }

    public function scenicMapPlace()
    {
        $params = $this->validateParameter();

        $data = app(LifeToolsScenicMapService::class)->scenicMapPlace($params);

        return api_output(self::STATUS_OK, $data);
    }

    public function mapPlaceDetail()
    {
        $params = $this->validateParameter();

        $data = app(LifeToolsScenicMapService::class)->mapPlaceDetail($params);

        return api_output(self::STATUS_OK, $data);
    }

    public function scenicMapLine()
    {
        $params = $this->validateParameter();

        $data = app(LifeToolsScenicMapService::class)->scenicMapLine($params);

        return api_output(self::STATUS_OK, $data);
    }

    public function scenicMapLineDistance()
    {
        $params = $this->validateParameter();

        $data = app(LifeToolsScenicMapService::class)->scenicMapLineDistance($params);

        return api_output(self::STATUS_OK, $data);
    }
    //endregion


}
