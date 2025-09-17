<?php

declare (strict_types=1);

namespace app\appoint\controller\platform;

use app\appoint\model\service\AppointNewsflashService;
use app\appoint\validate\AppointNewsflash as AppointNewsflashValidate;
use app\common\controller\platform\AuthBaseController;
use app\http\exceptions\ParametersException;
use think\helper\Str;

class AppointNewsflashController extends AuthBaseController
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
    private function validateParameter(array $params = [], string $scenario = '', $method = 'post'): array
    {
        empty($params) && $params = input($method . '.');
        $validate = validate(AppointNewsflashValidate::class);
        if (!$validate->scene($scenario ?: Str::snake(request()->action()))->check($params)) {
            throw_exception(L_($validate->getError()), ParametersException::class);
        }

        return $params;
    }

    //endregion

    //region 资讯设置

    /**
     * 资讯列表
     * @return \think\response\Json
     */
    public function newsList()
    {
        $params = input('post.');

        $data = app(AppointNewsflashService::class)->newsList($params);

        return api_output(self::STATUS_OK, $data);
    }
    /**
     * 资讯详情
     * @return \think\response\Json
     */
    public function newsDetail()
    {
        $params = $this->validateParameter();
        $data = app(AppointNewsflashService::class)->newsDetail($params['newsflash_id']);

        return api_output(self::STATUS_OK, $data);
    }
    /**
     * 添加资讯
     * @return \think\response\Json
     */
    public function saveNews()
    {
        $params = $this->validateParameter();
        foreach ($params['appoint_json'] as $appoint){
            $this->validateParameter($appoint, 'news_add_appoint');
        }
        $data = app(AppointNewsflashService::class)->saveNews($params);
        
        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 删除资讯
     * @return \think\response\Json
     */
    public function delNews()
    {
        $params = $this->validateParameter();

        $data = app(AppointNewsflashService::class)->delNews($params);

        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 未加入资讯的预约列表
     * @return \think\response\Json
     */
    public function appointList()
    {
        $data = app(AppointNewsflashService::class)->appointList(input('post.'));

        return api_output(self::STATUS_OK, $data);
    }
    //endregion

}