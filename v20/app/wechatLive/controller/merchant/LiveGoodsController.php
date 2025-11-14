<?php


namespace app\wechatLive\controller\merchant;


use app\http\exceptions\ParametersException;
use app\wechatLive\model\service\LiveGoodsService;
use app\wechatLive\validate\LiveGoodsValidate;
use think\helper\Str;

class LiveGoodsController extends AuthBaseController
{
    const STATUS_OK = 1000;//状态码 正常

    /**
     * @param string $method
     * @param array $params
     * @param string $scenario
     * @return array
     */
	protected function validateParameter(string $method = 'post', array $params = [], string $scenario = ''): array
    {
        empty($params) && $params = input($method . '.');
        $params['mer_id'] = $this->merId;

        $validate = validate(LiveGoodsValidate::class);
        if (!$validate->scene($scenario ?: Str::snake(request()->action()))->check($params)) {
            throw new ParametersException($validate->getError());
        }

        return $params;
    }

    public function goodsList()
    {
        $params = $this->validateParameter();
        
        $data = app(LiveGoodsService::class)->merchantGoodsList($params);

        return api_output(self::STATUS_OK, $data);
    }

    public function liveGoodsList()
    {
        $params = $this->validateParameter();

        $data = app(LiveGoodsService::class)->liveGoodsList($params);
        $data = $data->toArray();

        $limitCount = cfg('wechat_mini_live_sync_goods_limit');
        $useCount = \app\wechatLive\model\db\WechatLiveGoods::where('mer_id', $params['mer_id'])->count();
        $data['limit_count'] = $limitCount;
        $data['left_count'] = $limitCount - $useCount;
        return api_output(self::STATUS_OK, $data);
    }

    public function liveGoodsAdd()
    {
        $params = $this->validateParameter();

        $data = app(LiveGoodsService::class)->liveGoodsAdd($params);

        return api_output(self::STATUS_OK, $data);
    }

    public function liveGoodsEdit()
    {
        $params = $this->validateParameter();

        $data = app(LiveGoodsService::class)->liveGoodsEdit($params);

        return api_output(self::STATUS_OK, $data);
    }

    public function liveGoodsDelete()
    {
        $params = $this->validateParameter();

        $data = app(LiveGoodsService::class)->liveGoodsDelete($params);

        return api_output(self::STATUS_OK, $data);
    }

    public function liveGoodsChangeStatus()
    {
        $params = $this->validateParameter();

        $data = app(LiveGoodsService::class)->liveGoodsChangeStatus($params);

        return api_output(self::STATUS_OK, $data);
    }

    public function liveGoodsStatusUpdate()
    {
        $data = app(LiveGoodsService::class)->liveGoodsStatusUpdate($this->merId);

        return api_output(self::STATUS_OK, $data);
    }
}