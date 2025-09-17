<?php
/**
 * 商家后台餐饮商品管理控制器
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/07/06 15:36
 */

namespace app\foodshop\controller\merchant;
use app\merchant\controller\merchant\AuthBaseController;
use app\foodshop\model\service\goods\FoodshopGoodsLibraryService;
use app\foodshop\model\service\goods\FoodshopGoodsSortService;
class GoodsController extends AuthBaseController
{
    /**
     * 商品分类列表
     */
    public function goodsList()
    {
        $foodshopGoodsLibraryService = new FoodshopGoodsLibraryService();

        $param['store_id'] = $this->request->param("store_id", "0", "intval");
        $param['sort_id'] = $this->request->param("sort_id", "0", "intval");
        $param['order_status'] = $this->request->param("order_status", "0", "intval");
        $param['name'] = $this->request->param("name", "", "trim");
        // 获得列表
        $list = $foodshopGoodsLibraryService->getGoodsList($param, 1);
        try {
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        return api_output(0, $list);
    }

    /**
     * 批量编辑商品
     */
    public function editGoodsBatch()
    {
        $foodshopGoodsLibraryService = new FoodshopGoodsLibraryService();
        // 商品id
        $param['goods_id'] = $this->request->param("goods_id", "", "");
        $param['pigcms_id'] = $this->request->param("pigcms_id", "", "");
        // 库存
        $param['stock_num'] = $this->request->param("stock_num", "", "");
        // 原始库存
        $param['original_stock'] = $this->request->param("original_stock", "", "");
        // 修改类型：1-沽清，2-置满，3-修改库存，4-上架，5-下架，6-删除，7-修改分类
        $param['type'] = $this->request->param("type", "", "");
        // 分类id
        $param['sort_id'] = $this->request->param("sort_id", "", "");
        // 店铺id
        $param['store_id'] = $this->request->param("store_id", "", "");

        $result = $foodshopGoodsLibraryService->editGoodsBatch($param, $this->merchantUser);
        try {
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        
        return api_output(0, $result);
    }
    /**
     * 批量添加商品
     */
    public function addGoods()
    {
        $foodshopGoodsLibraryService = new FoodshopGoodsLibraryService();
        $param = $this->request->param();

        $result = $foodshopGoodsLibraryService->addGoods($param, $this->merchantUser);
        try {
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }

        return api_output(0, $result);
    }

    /**
     * 添加编辑商品
     */
    public function editGoods()
    {
        $foodshopGoodsLibraryService = new FoodshopGoodsLibraryService();
        $param = $this->request->param();
        if(isset($param['system_type'])){
            unset($param['system_type']);
        }

        isset($param['is_must']) && $param['is_must'] = intval($param['is_must']);
        isset($param['only_staff']) && $param['only_staff'] = intval($param['only_staff']);
        isset($param['is_recommend']) && $param['is_recommend'] = intval($param['is_recommend']);
        isset($param['show_start_time2']) && is_null($param['show_start_time2']) && $param['show_start_time2'] = '00:00:00';
        isset($param['show_end_time2']) && is_null($param['show_end_time2']) && $param['show_end_time2'] = '00:00:00';
        isset($param['show_start_time3']) &&  is_null($param['show_start_time3']) && $param['show_start_time3'] = '00:00:00';
        isset($param['show_end_time3']) &&  is_null($param['show_end_time3']) && $param['show_end_time3'] = '00:00:00';

        try {
            $result = $foodshopGoodsLibraryService->editGoods($param, $this->merchantUser);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }

        return api_output(0, $result);
    }

    /**
     * 编辑商品排序
     */
    public function editSingleGoods()
    {
        $foodshopGoodsLibraryService = new FoodshopGoodsLibraryService();
        $param = $this->request->param();

        try {
            $result = $foodshopGoodsLibraryService->editSingleGoods($param, $this->merchantUser);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }

        return api_output(0, $result);
    }

    /**
     * 获得商品详情
     */
    public function goodsDetail()
    {
        $foodshopGoodsLibraryService = new FoodshopGoodsLibraryService();
        
        $param['goods_id'] = $this->request->param("goods_id", "0", "intval");

        try {
            $detail = $foodshopGoodsLibraryService->getGoodsDetailByGoodsId($param['goods_id'],'merchant');
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        
        return api_output(0, $detail);
    }

    /**
     * 删除商品
     */
    public function changeStatus()
    {
        $foodshopGoodsLibraryService = new FoodshopGoodsLibraryService();

        $param['store_id'] = $this->request->param("store_id", "0", "intval");
        $param['pigcms_id'] = $this->request->param("pigcms_id", "0", "intval");

        try {
            $result = $foodshopGoodsLibraryService->changeStatus($param, $this->merchantUser);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 删除商品
     */
    public function goodsDel()
    {
        $foodshopGoodsLibraryService = new FoodshopGoodsLibraryService();

        $param['pigcms_id'] = $this->request->param("pigcms_id", "0", "intval");
        $param['store_id'] = $this->request->param("store_id", "0", "intval");
        $param['type'] = 6;
        $param['pigcms_id'] = [$param['pigcms_id']];
        try {
            $result = $foodshopGoodsLibraryService->editGoodsBatch($param, $this->merchantUser);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        return api_output(0, $result);
    }


    // 导出
	public function export(){
        
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        $param['page'] = $this->request->param("page", "1", "intval");
        $param['start_time'] = $this->request->param("start_time", "", "trim");
        $param['end_time'] = $this->request->param("end_time", "", "trim");
        $param['order_status'] = $this->request->param("order_status", "", "intval");
        $param['searchtype'] = $this->request->param("searchtype", "", "trim");
        $param['keyword'] = $this->request->param("keyword", "", "trim");
        $param['province_id'] = $this->request->param("province_id", "", "intval");
        $param['city_id'] = $this->request->param("city_id", "", "intval");
        $param['area_id'] = $this->request->param("area_id", "", "intval");
        
        try {
            $result = (new ExportService())->addDiningOrderExport($param, $this->systemUser); 
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        return api_output(0, $result);
    }
    
    
    
}
