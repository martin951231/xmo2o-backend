<?php
declare (strict_types=1);

namespace app\mall\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\http\exceptions\ParametersException;
use app\mall\model\service\MallDeliveryService;
use app\mall\model\service\MerchantStoreService;
use app\mall\validate\MallDeliveryValidate;
use think\helper\Str;

class MallPlatformDeliveryController extends AuthBaseController
{
    const STATUS_OK = 1000;//状态码 正常

    /**
     * @param string $method
     * @param array $params
     * @param string $scenario
     * @return array
     */
    private function validateParameter($method = 'post', array $params = [], string $scenario = ''): array
    {
        empty($params) && $params = input($method . '.');
        $validate = validate(MallDeliveryValidate::class);
        if (!$validate->scene($scenario ?: 'platform_' . Str::snake(request()->action()))->check($params)) {
            throw new ParametersException($validate->getError());
        }

        return $params;
    }

    public function merchantStoreList()
    {
        $params = $this->validateParameter();

        $data = app(MerchantStoreService::class)->merchantStoreList($params);

        return api_output(self::STATUS_OK, $data);
    }

    public function deliverySetting()
    {
        $params = $this->validateParameter();

        $data = app(MallDeliveryService::class)->deliverySetting($params);

        return api_output(self::STATUS_OK, $data);
    }

    public function deliverCustom()
    {
        $data = app(MallDeliveryService::class)->deliverCustom();

        return api_output(self::STATUS_OK, $data);
    }
    
}