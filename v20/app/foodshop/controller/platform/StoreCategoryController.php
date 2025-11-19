<?php
/**
 * 餐饮店铺分类控制器
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/19 10:46
 */

namespace app\foodshop\controller\platform;
use app\foodshop\controller\platform\AuthBaseController;
use app\foodshop\model\service\store\MealStoreCategoryService;
class StoreCategoryController extends AuthBaseController
{
    
    /**
     * 获得分类列表
     */
    public function categoryList()
    {
        
        $mealStoreCategoryService = new MealStoreCategoryService();

        // 获得分类列表
        $list = $mealStoreCategoryService->getCategoryTree([]);

        
        return api_output(0, $list);
    }   

    /**
     * 获得编辑父分类所需数据
     */
    public function getEditInfo()
    {
        
        $mealStoreCategoryService = new MealStoreCategoryService();
        // 分类ID
        $param['cat_id'] = $this->request->param("cat_id", "", "intval");

        // 获得分类信息
        $list = $mealStoreCategoryService->getEditInfo($param);
        return api_output(0, $list);
    }

     
    /**
     * 获得编辑父分类所需数据
     */
    public function editSort()
    {
        
        $mealStoreCategoryService = new MealStoreCategoryService();
        // 分类ID
        $param['cat_id'] = $this->request->param("cat_id", "", "intval");
        // 分类名称
        $param['cat_name'] = $this->request->param("cat_name", "", "trim");
        // 分类父ID
        $param['cat_fid'] = $this->request->param("cat_fid", "", "intval");
        // 不营业时显示状态
        $param['show_method'] = $this->request->param("show_method", "", "intval");
        // 分类排序
        $param['cat_sort'] = $this->request->param("cat_sort", "", "intval");
        // 分类状态
        $param['cat_status'] = $this->request->param("cat_status", "", "intval");
        
        try {
            $list = $mealStoreCategoryService->editSort($param);
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
        
        $mealStoreCategoryService = new MealStoreCategoryService();
        // 分类ID
        $param['cat_id'] = $this->request->param("cat_id", "", "intval");
        try {
            $list = $mealStoreCategoryService->delSort($param);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0, $list);
    }   
}
