<?php
/**
 *
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/9/18 15:46
 */

namespace app\mall\controller\merchant;

use app\mall\model\service\ExpressTemplateService;
use app\mall\model\service\MallCategoryService;
use app\mall\model\service\MallCategorySpecService;
use app\mall\model\service\MallGoodsService;
use app\mall\model\service\MallGoodsSortService;
use app\merchant\controller\merchant\AuthBaseController;
use app\shop\model\service\goods\ShopGoodsService;
use app\mall\model\db\MerchantStore;
use app\mall\model\service\BrandsService;
use app\mall\model\service\ProductGuaranteeService;
use pinyin\Pinyin;

class MallGoodsController extends AuthBaseController
{
    /**
     * 商品库列表
     */
    public function getMallGoodsList()
    {
        $shopGoodsService = new shopGoodsService();

        $param['store_id'] = $this->request->param("store_id", "0", "intval");
        $param['sort_id'] = $this->request->param("sort_id", "0", "intval");
        $param['name'] = $this->request->param("name", "", "trim");
        $param['source'] = $this->request->param("source", "", "trim");

        // 获得列表
        $list = $shopGoodsService->getMallGoodsList($param);
        return api_output(0, $list);
    }

    /**
     * @author zhumengqun
     * 商家后台-按照分类获取商品列表
     */
    public function getGoodsList()
    {
        $param['mer_id'] = $this->merId;
        $param['store_id'] = $this->request->param('store_id', '', 'intval');
        $param['search_type'] = $this->request->param('search_type', 1, 'intval');
        $param['keyword'] = $this->request->param('keyword', '', 'trim');
        $param['sort_id'] = $this->request->param('sort_id', '', 'intval');
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $param['audit_status'] = $this->request->param('audit_status', null, 'intval');
        $mallGoodsService = new MallGoodsService();
        try {
            $res = $mallGoodsService->getMerchantGoodsList($param);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @author zhumengqun
     * 设置/批量设置商品上下架
     */
    public function setStatusLot()
    {
        $goods_ids = $this->request->param('goods_ids');
        $status = $this->request->param('status', 1, 'intval');
        $mallGoodsService = new MallGoodsService();
        try {
            $res = $mallGoodsService->setStatusLot($goods_ids, $status);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 批量设置虚拟销量
     */
    public function setVirtualSales()
    {
        $goods_ids = $this->request->param('goods_ids');
        $sales = $this->request->param('sales', 0, 'intval');
        $mallGoodsService = new MallGoodsService();
        try {
            $res = $mallGoodsService->setVirtualSalesLot($goods_ids, $sales);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @author zhumengqun
     * 设置商品排序
     */
    public function setSort()
    {
        $goods_id = $this->request->param('goods_id', '', 'intval');
        $sort = $this->request->param('sort', '', 'intval');
        $type = 'merchant';
        $mallGoodsService = new MallGoodsService();
        try {
            $res = $mallGoodsService->setSort($goods_id, $sort, $type);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @author zhumengqun
     * 获取某个商品的sku信息
     */
    public function getGoodsSkuInfo()
    {
        $goods_id = $this->request->param('goods_id', '', 'intval');
        $mallGoodsService = new MallGoodsService();
        try {
            $res = $mallGoodsService->getGoodsSkuInfo($goods_id);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @author zhumengqun
     * 设置某个商品的sku信息
     */
    public function setGoodsSkuInfo()
    {
        $type = $this->request->param('type', 'spu', 'trim');
        $goods_id = $this->request->param('goods_id', '', 'intval');
        $sku_info = $this->request->param('sku_info');
        $price = $this->request->param('price', 0, 'trim');
        $mallGoodsService = new MallGoodsService();
        try {
            $res = $mallGoodsService->setGoodsSkuInfo($goods_id, $sku_info, $type, $price);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @author zhumengqun
     * 编辑或添加商品
     */
    public function addOrEditGoods()
    {
        $common = new Pinyin();
        $param['goods_id'] = $this->request->param('goods_id', '', 'intval');
        $param['store_id'] = $this->request->param('store_id', '', 'intval');
        $param['mer_id'] = $this->merId;
        //校验商家和店铺
        $storeInfo = (new MerchantStore)->getOne([['store_id','=',$param['store_id']]]);
        if($storeInfo['mer_id'] != $param['mer_id']){
            return api_output_error(1003, "您当前选择的店铺不属于您当前登录的商户，原因可能是您登录了多个商家导致。");
        }
        $param['price'] = $this->request->param('price', '', 'trim');
        $param['price_range_low'] = $this->request->param('price_range_low', '', 'trim');
        $param['price_range_height'] = $this->request->param('price_range_height', '', 'trim');
        $param['marketing_ratio'] = $this->request->param('marketing_ratio', '', 'trim');   // 分销员比例
        $ratio_list = $this->request->param('ratio_list', [], 'trim');// 分销员单独比例
        $param['name'] = $this->request->param('name', '', 'trim');
        $param['spell_capital'] = pinyin_long(str_replace(['➕','(', ')', '.', '·', '（', '）', '-', '\\', '/', '\'', '@', '|', '{', '}', '[', ']', '+', '=', '#', '*', '$', '^', '&', '+', ';', ':', '、', '<', '>', '?'], '', $param['name']));
        $param['spell_full'] = $common->isChinese($param['name']);
        $param['min_price'] = $this->request->param('min_price', '', 'trim');
        $param['max_price'] = $this->request->param('max_price', '', 'trim');
        $param['free_shipping'] = $this->request->param('free_shipping', 0, 'intval');
        $param['image'] = $this->request->param('image', '', 'trim');
        $param['like_image'] = $this->request->param('like_image', '', 'trim');//猜你喜欢首页图片展示
        $param['images'] = $this->request->param('images');
        $param['fright_id'] = $this->request->param('fright_id', '', 'intval');
        $param['unit'] = $this->request->param('unit', '', 'trim');
        $param['goods_desc'] = $this->request->param('goods_desc', '', 'trim');
        $param['spec_desc'] = $this->request->param('spec_desc', '', 'trim');
        $param['pack_desc'] = $this->request->param('pack_desc', '', 'trim');
        $param['common_goods_id'] = $this->request->param('common_goods_id', '', 'intval');
        $param['common_stock_num'] = $this->request->param('common_stock_num', '', 'trim');
        $param['service_desc'] = $this->request->param('service_desc');
        $param['videos'] = $this->request->param('videos');
        $param['cate_first'] = $this->request->param('cate_first', '', 'intval');
        $param['cate_second'] = $this->request->param('cate_second', '', 'intval');
        $param['cate_three'] = $this->request->param('cate_three', '', 'intval');
        $param['stock_num'] = $this->request->param('stock_num', '', 'intval');
        $param['notes'] = $this->request->param('notes');
        $param['sort_id'] = $this->request->param('sort_id', '', 'intval');
        $param['sort_first'] = $this->request->param('sort_first', '', 'intval');
        $param['sort_second'] = $this->request->param('sort_second', '', 'intval');
        $param['sort_third'] = $this->request->param('sort_third', '', 'intval');
        $param['cat_id'] = $this->request->param('cat_id', '', 'intval');
        $param['leave_message'] = $this->request->param('leave_message');
        $param['other_area_fright'] = $this->request->param('other_area_fright', 0, 'trim');
        $param['is_restriction'] = $this->request->param('is_restriction', 0, 'intval');
        $param['restriction_periodic'] = $this->request->param('restriction_periodic', 1, 'intval');
        $param['restriction_num'] = $this->request->param('restriction_num', '', 'intval');
        $param['restriction_type'] = $this->request->param('restriction_type', '', 'intval');
        $param['initial_salenum'] = $this->request->param('initial_salenum', 1, 'intval');
        $param['stock_type'] = $this->request->param('stock_type', 2, 'intval');
        $param['spec_val_ids'] = $this->request->param('cat_spec_val', '', 'trim');
        $param['spec_list'] = $this->request->param('spec_list');
        $param['list'] = $this->request->param('list');
        $param['area_id'] = $this->request->param('area_id');
        $param['areaParam'] = $this->request->param('areaParam');
        $param['video_url'] = $this->request->param('video_url', '', 'trim');
        $param['brand_id'] = $this->request->param('brand_id', 0, 'intval');
        if($param['list']){
            foreach($param['list'] as $v){
                if(isset($v['price_range_low']) && $v['price_range_low']  > 0){
                    if($v['price_range_height'] < $v['price_range_low']){
                        return api_output_error(1003, "调价区间最高价不能低于调价区间最低价");
                    }
                    if($v['price'] < $v['price_range_low']){
                        return api_output_error(1003, "售价不能小于调价区间最低价");
                    }
                    if($v['price'] > $v['price_range_height']){
                        return api_output_error(1003, "售价不能高于调价区间最高价");
                    }
                }
            }
        }
        if($param['price_range_low'] > 0){
            if($param['price_range_low'] > $param['price_range_height']){
                return api_output_error(1003, "调价区间最高价不能低于调价区间最低价");
            }
            if($param['price_range_low'] > $param['price']){
                return api_output_error(1003, "售价不能小于调价区间最低价");
            }
            if($param['price'] > $param['price_range_height']){
                return api_output_error(1003, "售价不能高于调价区间最高价");
            }
        }


        $param['virtual_sales'] = $this->request->param('virtual_sales', '', 'intval');
        $param['describe'] = $this->request->param('describe','','trim,string');
        $mallGoodsService = new MallGoodsService();
        try {
            $res = $mallGoodsService->addOrEditGoods($param,$ratio_list);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @author zhumengqun
     * 获取编辑时的商品信息
     */
    public function getEditGoods()
    {
        $goods_id = $this->request->param('goods_id', '', 'intval');
        $mallGoodsService = new MallGoodsService();
        try {
            $arr = $mallGoodsService->getEditGoods($goods_id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @author zhumengqun
     * 选择商品库后页面展示的商品库数据
     */
    public function getGoodsLibInfo()
    {
        $goods_id = $this->request->param('goods_id', '', 'intval');
        $mallGoodsService = new MallGoodsService();
        try {
            $arr = $mallGoodsService->getGoodsLibInfo($goods_id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @author zhumengqun
     * 删除/批量删除商品
     */
    public function delGoods()
    {
        $goods_ids = $this->request->param('goods_ids');
        $mallGoodsService = new MallGoodsService();
        try {
            $res = $mallGoodsService->delGoods($goods_ids);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @author zhumengqun
     * 获取平台分类
     */
    public function getPlatformSort()
    {
        $merchantSortService = new MallCategoryService();
        try {
            $arr = $merchantSortService->goodsCategoryList('', '',1);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @author zhumengqun
     * 获取商家分类
     */
    public function getMerchantSort()
    {
        $store_id = $this->request->param('store_id', '', 'intval');
        $type = $this->request->param('type', 0, 'intval');
        $mer_id = $this->merId;
        $merchantSortService = new MallGoodsSortService();
        try {
            $arr = $merchantSortService->getSortList($mer_id, $store_id, $type, '');
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @author zhumengqun
     *获取平台分类属性
     */
    public function getPlatformProperties()
    {
        $cat_id = $this->request->param('cat_id');
        $categoryService = new MallCategorySpecService();
        try {
            $arr = $categoryService->propertyList($cat_id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * @author zhumengqun
     * 获取运费模板
     */
    public function getfreightList()
    {
        $mer_id = $this->merId;
        $ETService = new ExpressTemplateService();
        try {
            $arr = $ETService->getMerchantET($mer_id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     *选择添加的商品（各种活动选择商品）
     */
    public function getMallGoodsSelect()
    {
        $param['type'] = $this->request->param('type', '', 'trim');
        $param['sort_id'] = $this->request->param('sort_id', '', 'intval');
        $param['store_id'] = $this->request->param('store_id', '', 'intval');
        $param['keyword'] = $this->request->param('keyword', '', 'trim');
        $param['mer_id'] = $this->merId;
        $param['start_time'] = $this->request->param('start_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $mallGoodsService = new MallGoodsService();

        try {
//            $param['start_time'] = $param['type'] != 'periodic' ? strtotime(date('Y-m-d', $param['start_time']) . " 00:00:00") : time();
//            $param['end_time'] = $param['type'] != 'periodic' ? strtotime(date('Y-m-d', $param['end_time']) . " 23:59:59") : time();//杨宁宁说就按照当前日期算失效时间
            $arr = $mallGoodsService->getMallGoodsSelect($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 商家商品导出
     */
    public function exportGoods()
    {
        $param['mer_id'] = $this->merId;
        $param['store_id'] = $this->request->param('store_id', '', 'intval');
        $param['mer_name'] = $this->merchantUser['name'];
        $mallGoodsService = new MallGoodsService();
        try {
            $res = $mallGoodsService->addGoodsExportMerchant($param, [], $this->merchantUser);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 获取各种数
     */
    public function getNumbers()
    {
        $mer_id = $this->merId;
        $store_id = $this->request->param('store_id', '', 'intval');
        $keyword = $this->request->param('keyword', '', 'trim');
        $mallGoodsService = new MallGoodsService();
        try {
            $res = $mallGoodsService->getNumbers($store_id, $keyword, $mer_id);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    public function dealService()
    {
        $mallGoodsService = new MallGoodsService();
        try {
            $arr = $mallGoodsService->dealService();
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 判断商品能否被处理
     */
    public function deleteJudge()
    {
        $goods_ids = $this->request->param('goods_ids');
        $mallGoodsService = new MallGoodsService();
        try {
            $res = $mallGoodsService->judgeDeleted($goods_ids);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 批量新建商品
     */
    public function goodsBatch()
    {
        $param = $this->request->param();
        $mallGoodsService = new MallGoodsService();
        try {
            $res = $mallGoodsService->goodsBatch($param, $this->merId);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 获取品牌列表
     */
    public function getBrandsList()
    {
        $brands = (new BrandsService())->getBrandsSelect();
        return api_output(0, $brands, 'success');
    }

    public function getGuaranteeList()
    {
        $brands = (new ProductGuaranteeService())->getGuaranteeList();
        return api_output(0, $brands, 'success');
    }

    /**
     * 批量设置商品店铺分类
     */
    public function setStoreGoodsSort()
    {
        $goodsIds = $this->request->param('goods_ids');
        $sortId = $this->request->param('sort_id', 0, 'intval');
        $sortArr = array(
            'sort_first' => $this->request->param('sort_first', 0, 'intval'),
            'sort_second' => $this->request->param('sort_second', 0, 'intval'),
            'sort_third' => $this->request->param('sort_third', 0, 'intval'),
        );
        try {
            $res = (new MallGoodsService())->setStoreGoodsSort($goodsIds, $sortId, $sortArr);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    //批量给商品设置品牌
    public function setGoodsBrand(){
        $goodsIds = $this->request->param('goods_ids');
        $brandId = $this->request->param('brand_id', 0, 'intval');
        try {
            $res = (new MallGoodsService())->setGoodsBrand($goodsIds, $brandId);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}