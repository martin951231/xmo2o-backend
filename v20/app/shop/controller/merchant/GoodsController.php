<?php
/**
 * 商家后台商品管理控制器
 * Author: hengtingmei
 * Date Time: 2020/08/10 13:20
 */

namespace app\shop\controller\merchant;
use app\merchant\controller\merchant\AuthBaseController;
use app\shop\model\service\goods\ShopGoodsService;
use app\shop\model\service\goods\ShopGoodsSortService;
class GoodsController extends AuthBaseController
{
    /**
     * 商品库列表
     */
    public function goodsLibraryList()
    {
        $shopGoodsService = new ShopGoodsService();

        $param['store_id'] = $this->request->param("store_id", "0", "intval");
        $param['sort_id'] = $this->request->param("sort_id", "0", "intval");
        $param['name'] = $this->request->param("name", "", "trim");
        $param['source'] = $this->request->param("source", "", "trim");

        // 获得列表
        $list = $shopGoodsService->getGoodsLibraryList($param);
        return api_output(0, $list);
    }

    /**
     * 商品详情
     */
    public function goodsDetail()
    {
        $shopGoodsService = new ShopGoodsService();

        $param['goods_id'] = $this->request->param("goods_id", "0", "intval");
        $param['store_id'] = $this->request->param("store_id", "0", "intval");

        // 获得列表
        $list = $shopGoodsService->getGoodsDetail($param);
        return api_output(0, $list);
    }
}
