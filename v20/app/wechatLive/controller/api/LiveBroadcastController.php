<?php


namespace app\wechatLive\controller\api;


use app\http\exceptions\ParametersException;
use app\wechatLive\model\service\LiveGoodsService;
use app\wechatLive\validate\LiveGoodsValidate;
use think\helper\Str;

class LiveBroadcastController extends ApiBaseController
{
    const STATUS_OK = 1000;//状态码 正常

    /**
     * @param string $method
     * @param array $params
     * @param string $scenario
     * @return array
     */
    private function validateParameter($method = 'post',array $params = [], string $scenario = '') : array
    {
        empty($params) && $params = input($method.'.');
        $validate = validate(LiveGoodsValidate::class);
        if (!$validate->scene($scenario ?: 'api_'. Str::snake(request()->action()))->check($params)) {
            throw new ParametersException($validate->getError());
        }

        return $params;
    }
    
    public function liveList()
    {
        $params = $this->validateParameter();
        
        $data = app(LiveGoodsService::class)->wechatLiveList($params);

        return api_output(self::STATUS_OK, $data);
    }

    public function hasWechatLiveOnline()
    {
        $data = app(LiveGoodsService::class)->hasWechatLiveOnline();

        return api_output(self::STATUS_OK, $data);
    }
}