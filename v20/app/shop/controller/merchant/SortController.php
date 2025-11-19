<?php
/**
 * 商家后台外卖商品管理控制器
 * Created by phpstorm.
 * Author: hengtingmei
 * Date Time: 2020/08/10 13:20
 */

namespace app\shop\controller\merchant;
use app\merchant\controller\merchant\AuthBaseController;
use app\shop\model\service\goods\shopGoodsService;
use app\shop\model\service\goods\ShopGoodsSortService;
class SortController extends AuthBaseController
{
    /**
     * 商品分类列表
     */
    public function sortList()
    {
        $shopGoodsSortService = new ShopGoodsSortService();

        $param['store_id'] = $this->request->param("store_id", "0", "intval");

        // 获得列表
        $list = $shopGoodsSortService->getSortListTree($param);
        return api_output(0, $list);
    }
}
