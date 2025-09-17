<?php
declare(strict_types=1);

namespace app\merchant\controller\merchant;

use app\common\controller\platform\AuthBaseController;
use app\http\exceptions\ParametersException;
use app\merchant\model\service\LiveShowGoodsService;
use app\merchant\validate\LiveShowGoods as LiveShowGoodsValidate;
use think\helper\Str;

class LiveShowGoodsController extends AuthBaseController
{
    //region 参数验证
    const STATUS_OK = 1000;//状态码 正常

    public $validateClass = LiveShowGoodsValidate::class;
    /**
     * 参数验证
     * @param $scenario
     * @param array $param
     * @param string $method
     * @return array|mixed
     */
    private function validateParameter($method = 'post', array $params = [], string $scenario = ''): array
    {
        empty($params) && $params = input($method . '.');
        $validate = validate($this->validateClass);
        if (!$validate->scene($scenario ?: Str::snake(request()->action()))->check($params)) {
            throw new ParametersException(L_($validate->getError()));
        }
        $params['mer_id'] = $this->_uid;  
        return $params;
    }
    //endregion

    /**
     * 商品选择列表
     * @return \think\response\Json
     */
    public function selectGoodsList()
    {
        $params = $this->validateParameter();

        switch ($params['goods_type']){
            case 'village_group':
                $data = (new LiveShowGoodsService())->selectGoodsList($params);
                break;
            default:
                throw_exception('商品类型不存在');
        }

        return api_output(0, $data);
    }

    /**
     * 直播商品列表
     * @return \think\response\Json
     */
    public function liveShowGoodsList()
    {
        $params = $this->validateParameter();

        switch ($params['goods_type']){
            case 'village_group':
                $data = (new LiveShowGoodsService())->liveShowGoodsList($params);
                break;
            default:
                throw_exception('商品类型不存在');
        }

        return api_output(0, $data);
    }

    /**
     * 添加直播商品
     * @return \think\response\Json
     */
    public function addLiveShowGoods()
    {
        $params = $this->validateParameter();

        switch ($params['goods_type']){
            case 'village_group':
                $data = (new LiveShowGoodsService())->addLiveShowGoods($params);
                break;
            default:
                throw_exception('商品类型不存在');
        }

        return api_output(0, $data);
    }

    /**
     * 移除商品
     * @return \think\response\Json
     */
    public function delLiveShowGoods()
    {
        $params = $this->validateParameter();

        switch ($params['goods_type']){
            case 'village_group':
                $data = (new LiveShowGoodsService())->delLiveShowGoods($params);
                break;
            default:
                throw_exception('商品类型不存在');
        }

        return api_output(0, $data);
    }

    /**
     * 修改比例
     * @return \think\response\Json
     */
    public function changeLiveShowPercent()
    {
        $params = $this->validateParameter();

        switch ($params['goods_type']){
            case 'village_group':
                $data = (new LiveShowGoodsService())->changeLiveShowPercent($params);
                break;
            default:
                throw_exception('商品类型不存在');
        }

        return api_output(0, $data);
    }

    /**
     * 商品订单
     * @return \think\response\Json
     */
    public function liveShowOrder()
    {
        $params = $this->validateParameter();

        switch ($params['goods_type']){
            case 'village_group':
                $data = (new LiveShowGoodsService())->liveShowOrder($params);
                break;
            default:
                throw_exception('商品类型不存在');
        }

        return api_output(0, $data);
    }
}
