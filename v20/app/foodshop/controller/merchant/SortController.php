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
use app\foodshop\model\service\export\ExportService;
class SortController extends AuthBaseController
{
    /**
     * 商品分类列表
     */
    public function sortList()
    {
        $foodshopGoodsSortService = new FoodshopGoodsSortService();

        $param['store_id'] = $this->request->param("store_id", "0", "intval");
        $param['order_status'] = $this->request->param("order_status", "0", "intval");

        // 获得列表
        $list = $foodshopGoodsSortService->getMerchantSortList($param, $this->merchantUser);
        return api_output(0, $list);
    }


    /**
     * 商品分类列表
     */
    public function selectSortList()
    {
        $foodshopGoodsSortService = new FoodshopGoodsSortService();

        $param['store_id'] = $this->request->param("store_id", "0", "intval");

        // 获得列表
        $list = $foodshopGoodsSortService->getSelectSortList($param, $this->merchantUser);
        return api_output(0, $list);
    }

    /**
     * 分类排序
     */
    public function changeSort()
    {
        $foodshopGoodsSortService = new FoodshopGoodsSortService();

        $sortList = $this->request->param("sort_list", "", "");

        // 获得列表
        $res = $foodshopGoodsSortService->changeSort($sortList);
        
        return api_output(0);
    }

    /**
     * 获得编辑父分类所需数据
     */
    public function geSortDetail()
    {
        $foodshopGoodsSortService = new FoodshopGoodsSortService();
        // 分类ID
        $param['sort_id'] = $this->request->param("sort_id", "", "intval");

        // 分类详情
        $detail = $foodshopGoodsSortService->geSortDetail($param);
        return api_output(0, $detail);
    }


    /**
     * 添加编辑分类
     */
    public function editSort()
    {
        try {
            
            $foodshopGoodsSortService = new FoodshopGoodsSortService();
            // 分类ID
            $param['sort_id'] = $this->request->param("sort_id", "", "intval");
            // 分类名称
            $param['sort_name'] = $this->request->param("sort_name", "", "trim");
            // 店铺id
            $param['store_id'] = $this->request->param("store_id", "", "intval");
            // 排序值
            $param['sort'] = $this->request->param("sort", "", "intval");
            // 归属打印机
            $param['print_id'] = $this->request->param("print_id", "", "trim");
            // 是否开启分类打印机
            $param['is_open_print'] = $this->request->param("is_open_print", "", "trim");
            // 分类状态
            $param['status'] = $this->request->param("status", "", "intval");
            // 图片
            $param['image'] = $this->request->param("image", "", 'trim');

            // 显示日期
            $param['show_start_date'] = $this->request->param("show_start_date", "", 'trim');
            $param['show_end_date'] = $this->request->param("show_end_date", "", 'trim');
            // 星期几显示
            $param['week'] = $this->request->param("week", "", 'trim');

            // 显示时间段1
            $param['show_start_time'] = $this->request->param("show_start_time", "", 'trim');
            $param['show_end_time'] = $this->request->param("show_end_time", "", 'trim');
            // 显示时间段2
            $param['show_start_time2'] = $this->request->param("show_start_time2", "", 'trim');
            $param['show_end_time2'] = $this->request->param("show_end_time2", "", 'trim');
            // 显示时间段3
            $param['show_start_time3'] = $this->request->param("show_start_time3", "", 'trim');
            $param['show_end_time3'] = $this->request->param("show_end_time3", "", 'trim');
            // 是否自定义日期
            $param['all_date'] = $this->request->param("all_date", "", 'trim');
            // 是否自定义时间
            $param['all_time'] = $this->request->param("all_time", "", 'trim');
            // 分类折扣率
            $param['sort_discount'] = $this->request->param("sort_discount", 0, 'trim');

            $list = $foodshopGoodsSortService->editSort($param);

        
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0, $list);
    }

    /**
     * 获得编辑父分类所需数据
     */
    public function delSort()
    {

        $foodshopGoodsSortService = new FoodshopGoodsSortService();
        // 分类ID
        $param['sort_id'] = $this->request->param("sort_id", "", "intval");
        // 店铺id
        $param['store_id'] = $this->request->param("store_id", "", "intval");
        try {
            $list = $foodshopGoodsSortService->delSort($param);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0, $list);
    }
}
