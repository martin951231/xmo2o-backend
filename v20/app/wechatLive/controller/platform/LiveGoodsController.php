<?php


namespace app\wechatLive\controller\platform;


use app\http\exceptions\ParametersException;
use app\wechatLive\model\service\LiveGoodsService;
use app\wechatLive\validate\LiveGoodsValidate;
use think\helper\Str;

class LiveGoodsController extends AuthBaseController
{
    const STATUS_OK = 1000;//状态码 正常
	protected $validateClass = LiveGoodsValidate::class;

    public function liveGoodsList()
    {
        $params = $this->validateParameter();

        $data = app(LiveGoodsService::class)->liveGoodsList($params);

        return api_output(self::STATUS_OK, $data);
    }

    public function liveGoodsSetCommissionRate()
    {
        $params = $this->validateParameter();

        $data = app(LiveGoodsService::class)->liveGoodsSetCommissionRate($params);

        return api_output(self::STATUS_OK, $data);
    }

    public function liveGoodsChangeStatus()
    {
        $params = $this->validateParameter();

        $data = app(LiveGoodsService::class)->liveGoodsChangeStatus($params,true);

        return api_output(self::STATUS_OK, $data);
    }

    public function liveGoodsDelete()
    {
        $params = $this->request->param();

        $data = app(LiveGoodsService::class)->liveGoodsDelete($params);

        return api_output(self::STATUS_OK, $data);
    }
}