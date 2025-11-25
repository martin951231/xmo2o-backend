<?php
/**
 * 商家后台店铺套餐控制器
 * Created by subline.
 * Author: 钱大双
 * Date Time: 2020年12月14日17:17:14
 */

namespace app\foodshop\controller\merchant;

use app\merchant\controller\merchant\AuthBaseController;
use app\foodshop\model\service\package\FoodshopGoodsPackageService;
use app\foodshop\model\service\package\FoodshopGoodsPackageDetailService;

class PackageController extends AuthBaseController
{
    /**
     * 店铺套餐列表
     */
    public function getPackageList()
    {
        $param['store_id'] = $this->request->param("store_id", "0", "intval");
        $param['page'] = $this->request->param("page", "1", "intval");
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");

        try {
            $foodshopGoodsPackageService = new FoodshopGoodsPackageService();
            // 获得列表
            $list = $foodshopGoodsPackageService->getPackageList($param);

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $list);
    }

    /**
     * 添加编辑店铺套餐
     */
    public function editPackage()
    {
        $param = $this->request->param();
        try {
            $res = (new FoodshopGoodsPackageService())->editPackage($param);

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $res);
    }

    /**
     * 店铺套餐详情
     */
    public function getPackageDetail()
    {
        $param['id'] = $this->request->param("id", "0", "intval");

        try {
            // 获得详情
            $detail = (new FoodshopGoodsPackageService())->getDetail($param);

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $detail);
    }

    /**
     * 删除店铺套餐
     */
    public function delPackage()
    {
        $param['id'] = $this->request->param("id", "0", "intval");

        try {
            (new FoodshopGoodsPackageService())->delPackage($param);

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0);
    }

    /**
     * 店铺套餐详情分组列表
     */
    public function getPackageDetailList()
    {
        $param['pid'] = $this->request->param("pid", "0", "intval");

        try {
            // 获得列表
            $list = (new FoodshopGoodsPackageDetailService())->getPackageDetailList($param);

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $list);
    }

    /**
     * 店铺套餐详情分组详情
     */
    public function getPackageDetailInfo()
    {
        $param['id'] = $this->request->param("id", "0", "intval");

        try {
            // 获得详情
            $detail = (new FoodshopGoodsPackageDetailService())->getDetail($param);

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $detail);
    }

    /**
     * 店铺套餐详情分组商品详情列表
     */
    public function getPackageDetailGoodsList()
    {
        $param['id'] = $this->request->param("id", "0", "intval");
        
        try {
            // 获得列表
            $rs = (new FoodshopGoodsPackageDetailService())->getPackageDetailGoodsList($param);

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $rs);
    }

    /**
     * 添加编辑套餐分组
     */
    public function editPackageDetail()
    {
        $param = $this->request->param();
        try {
            (new FoodshopGoodsPackageDetailService())->editPackageDetail($param);

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0);
    }

    /**
     * 删除套餐分组
     */
    public function delPackageDetail()
    {
        $param['id'] = $this->request->param("id", "0", "intval");
        try {
            (new FoodshopGoodsPackageDetailService())->delPackageDetail($param);

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0);
    }

    /**
     * 获得套餐选择商品列表
     */
    public function getPackageGoodsList()
    {
        $param['store_id'] = $this->request->param("store_id", "0", "intval");
        $param['id'] = $this->request->param("id", "0", "intval");
        $param['sort_id'] = $this->request->param("sort_id", "0", "intval");
        $param['keywords'] = $this->request->param("keywords", "", "trim");

        // 获得列表
        $list = (new FoodshopGoodsPackageDetailService())->getPackageGoodsList($param);
        return api_output(0, $list);
    }
}