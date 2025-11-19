<?php
/**
 * 外卖商品
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/23 11:28
 */

namespace app\shop\controller\platform;
use app\shop\model\service\goods\ShopGoodsService;
use app\shop\model\service\goods\TimeLimitedDiscountGoodsService;

class GoodsController extends AuthBaseController
{
    /**
     * 获得限时优惠商品列表
     */
    public function getSeckillGoodsList()
    {
        


        $param['page'] = $this->request->param("page", "1", "intval");
        // 每页显示数量
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        $param['keywords'] = $this->request->param("keywords", "", "trim");

        $param['status'] = 2;
        // 获得列表
        $list = (new TimeLimitedDiscountGoodsService())->getGoodsList($param,$this->systemUser);

        
        return api_output(0, $list);
    }
}
