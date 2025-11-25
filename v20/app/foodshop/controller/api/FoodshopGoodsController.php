<?php
/**
 * 餐饮商品控制器
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/19 10:46
 */

namespace app\foodshop\controller\api;
use app\foodshop\model\service\goods\FoodshopGoodsLibraryService as FoodshopGoodsLibraryService;
use app\foodshop\controller\api\ApiBaseController;
use app\foodshop\model\service\store\MerchantStoreFoodshopDataService as MerchantStoreFoodshopDataService;
use app\foodshop\model\service\package\FoodshopGoodsPackageDetailService;

class FoodshopGoodsController extends ApiBaseController
{
    /**
     * 获得店铺橱窗店铺信息
     */
    public function index()
    {
        
        $foodshopGoodsLibraryService = new FoodshopGoodsLibraryService();
        
        // 搜索条件
        $where = [];

        // 店铺ID
        $storeId = $this->request->param("store_id", "", "intval");

        if($this->userInfo){
            $user = $this->userInfo;
        }else{
            $user = $this->userTemp;
        }

        // 未获取到用户信息
        $user = (new \app\foodshop\model\service\order\DiningOrderService())->getFormatUser($user); 
        if(empty($user)){
            return api_output_error(1006);
        }

        try {
            // 获得店铺橱窗店铺信息
            $goodsList = $foodshopGoodsLibraryService->getStoreIndex($storeId, $user);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }

        return api_output(0, $goodsList);
    }

    /**
     * 获得店铺菜品
     */
    public function goodsListByStore()
    {
        $foodshopGoodsLibraryService = new FoodshopGoodsLibraryService();
        
        // 搜索条件
        $condition = [];

        // 店铺ID
        $storeId = $this->request->param("store_id", "", "intval");

        // 搜索商品名
        $keyword = $this->request->param("keyword", "", "trim");
        $condition['show_type'] = 'tree';//展示类型 分类和商品组合一起返回

        if($keyword !== ''){
            $condition['name'] = $keyword;
            $condition['show_type'] = 'list';//展示类型 商品列表
        }


        //热销商品分类列表
        $hot_goods = (new MerchantStoreFoodshopDataService())->getStoreDataInfoType($storeId, 'hot');

        //优惠商品分类列表
        $discount_goods = (new MerchantStoreFoodshopDataService())->getStoreDataInfoType($storeId, 'discount');

        $condition['is_must'] = 0;
        // 获得商品列表
        $goodsList = $foodshopGoodsLibraryService->getGoodsListByStoreId($storeId, $condition, 'wap',[],'user');

        if(empty($keyword)) {
            $goodsList = array_merge($hot_goods, $discount_goods, $goodsList);
        }

        return api_output(0, $goodsList);
    }   
    
    /**
     * 获得单个商品详情
     */
    public function goodsDetail()
    {
        $type = $this->request->param("type", "1", "intval");
        try {
            if ($type == 1) {
                $foodshopGoodsLibraryService = new FoodshopGoodsLibraryService();
                // 商品id
                $goodsId = $this->request->param("product_id", "", "intval");
                // 获得商品列表
                $goodsDetail = $foodshopGoodsLibraryService->getGoodsDetailByGoodsId($goodsId, 'wap');
            } else {
                $foodshopGoodsPackageDetailService = new FoodshopGoodsPackageDetailService();
                // 套餐id
                $param['pid'] = $this->request->param("product_id", "0", "intval");

                $goodsDetail = $foodshopGoodsPackageDetailService->getPackageDetailByPid($param,'wap');
            }
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0, $goodsDetail);
    }

     /**
     * 推荐菜列表
     */
    public function recommendGoodsList()
    {
        
        $foodshopGoodsLibraryService = new FoodshopGoodsLibraryService();

        // 店铺id
        $storeId = $this->request->param("store_id", "", "intval");

        // 页码
        $page = $this->request->param("page", "", "intval");

        // 获得商品列表
        $goodsDetail = $foodshopGoodsLibraryService->getRecommendGoodsList($storeId);

        return api_output(0, $goodsDetail);
    }
}
