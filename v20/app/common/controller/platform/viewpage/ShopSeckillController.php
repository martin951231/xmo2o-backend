<?php
/**
 * 后台可视化页面 外卖首页-限时秒杀功能
 * author by hengtingmei
 */
namespace app\common\controller\platform\viewpage;

use app\common\controller\platform\AuthBaseController;
use app\common\model\service\viewpage\ShopSeckillCategoryGoodsService;
use app\common\model\service\viewpage\ShopSeckillCategoryService;

class ShopSeckillController extends AuthBaseController {
    public function initialize()
    {
        parent::initialize();
    }

    /**
     * desc: 返回默认基本信息
     * return :array
     */
    public function getDefaultInfo(){
        $returnArr = (new ShopSeckillCategoryService())->getDefaultInfo();

        return api_output(0, $returnArr);
    }

    /**
     * desc: 修改分类信息
     * return :array
     */
    public function editCategory(){
        $param = $this->request->param();
        $returnArr = (new ShopSeckillCategoryService())->editCategory($param,$this->systemUser);

        return api_output(0, $returnArr);
    }

    /**
     * desc: 获得分类详情
     * return :array
     */
    public function getCategoryDetail(){
        $param = $this->request->param();
        $returnArr = (new ShopSeckillCategoryService())->getCategoryDetail($param);

        return api_output(0, $returnArr);
    }

    /**
     * desc: 获得分类列表
     * return :array
     */
    public function getCategoryList(){
        $param = $this->request->param();
        $returnArr = (new ShopSeckillCategoryService())->getCategoryList($param,$this->systemUser);

        return api_output(0, $returnArr);
    }

    /**
     * desc: 删除分类信息
     * return :array
     */
    public function delCategory(){
        $param = $this->request->param();
        $returnArr = (new ShopSeckillCategoryService())->delCategory($param);

        return api_output(0, $returnArr);
    }



    /**
     * desc: 添加分类绑定的商品
     * return :array
     */
    public function addCategoryGoods(){
        $param = $this->request->param();
        $returnArr = (new ShopSeckillCategoryGoodsService())->addCategoryGoods($param);

        return api_output(0, $returnArr);
    }

    /**
     * desc: 删除分类绑定的商品
     * return :array
     */
    public function delCategoryGoods(){
        $param = $this->request->param();
        $returnArr = (new ShopSeckillCategoryGoodsService())->delCategoryGoods($param);

        return api_output(0, $returnArr,L_('删除成功'));
    }

    /**
     * desc: 修改分类绑定的商品的排序
     * return :array
     */
    public function editCategoryGoodsSort(){
        $param = $this->request->param();
        $returnArr = (new ShopSeckillCategoryGoodsService())->editCategoryGoodsSort($param);

        return api_output(0, $returnArr);
    }

    /**
     * desc: 获得分类绑定的商品列表
     * return :array
     */
    public function getCategoryGoodsList(){
        $param = $this->request->param();
        $returnArr = (new ShopSeckillCategoryGoodsService())->getCategoryGoodsList($param);

        return api_output(0, $returnArr);
    }
}