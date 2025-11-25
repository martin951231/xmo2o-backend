<?php
/**
 * 店员后台餐饮商品管理控制器
 * Author: hengtingmei
 * Date Time: 2020/08/27 16:52
 */

namespace app\foodshop\controller\storestaff;
use app\storestaff\controller\storestaff\AuthBaseController;
use app\foodshop\model\service\goods\FoodshopGoodsLibraryService;
use app\foodshop\model\service\goods\FoodshopGoodsSortService;
use app\foodshop\model\service\package\FoodshopGoodsPackageDetailService;
class GoodsController extends AuthBaseController
{
    /**
     * 商品分类列表
     */
    public function goodsListTree()
    {
        $foodshopGoodsLibraryService = new FoodshopGoodsLibraryService();

        $param['store_id'] = $this->staffUser['store_id'];
        $param['keyword'] = $this->request->param("keyword", "", "trim");
        // 是否是店员估清菜单列表页
        $param['is_clear_stock'] = $this->request->param("is_clear_stock", "0", "intval");

        $param['show_type'] = 'tree';//展示类型 分类和商品组合一起返回
        if($param['keyword'] !== ''){
            $param['show_type'] = 'list';//展示类型 商品列表
        }
        $param['is_must'] = 0;
        // 获得列表
        $list = $foodshopGoodsLibraryService->getGoodsListByStoreId($param['store_id'] , $param);
        return api_output(0, $list);
    }

    /**
     * 获得商品详情
     */
    public function goodsDetail()
    {
        $type = $this->request->param("type", "1", "intval");
        if ($type == 1) {
            $foodshopGoodsLibraryService = new FoodshopGoodsLibraryService();

            $param['goods_id'] = $this->request->param("product_id", "0", "intval");

            $detail = $foodshopGoodsLibraryService->getGoodsDetailByGoodsId($param['goods_id'],'wap');
        } else {
            $foodshopGoodsPackageDetailService = new FoodshopGoodsPackageDetailService();

            $param['pid'] = $this->request->param("product_id", "0", "intval");

            $detail = $foodshopGoodsPackageDetailService->getPackageDetailByPid($param,'wap');
        }

        return api_output(0, $detail);
    }

    /**
     * 获得套餐商品详情
     */
    public function packageDetail()
    {
        $foodshopGoodsPackageDetailService = new FoodshopGoodsPackageDetailService();

        $param['pid'] = $this->request->param("product_id", "0", "intval");

        $detail = $foodshopGoodsPackageDetailService->getPackageDetailByPid($param,'wap');

        return api_output(0, $detail);
    }

    /**
     * 估清置满
     */
    public function editStock()
    {
        $foodshopGoodsLibraryService = new FoodshopGoodsLibraryService();

        $param['goods_id'] = $this->request->param("goods_id");
        $param['store_id'] = $this->staffUser['store_id'];
        $param['type'] =  $this->request->param("type");//0-估清1-置满（取消估清）
        $param['type'] = $param['type'] + 1;
        $param['index'] = $this->request->post('index', '', 'trim');
        try {
            $result = $foodshopGoodsLibraryService->editGoodsBatch($param);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 已估清的列表
     */
    public function clearGoodsList()
    {
        $foodshopGoodsLibraryService = new FoodshopGoodsLibraryService();

        $param['store_id'] = $this->staffUser['store_id'];
        $where['spec_stock'] = 0;
        $where['show_type'] = 'list';
        try {
            $result = $foodshopGoodsLibraryService->getGoodsListByStoreId($param['store_id'],$where);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        return api_output(0, $result);
    }

    
    
    
}
