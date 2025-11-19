<?php
/**
 * 餐饮搜索发现控制器
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/23 11:28
 */

namespace app\foodshop\controller\platform;
use app\foodshop\controller\platform\AuthBaseController;
use app\foodshop\model\service\store\SearchHotFoodshopService;
class SearchHotController extends AuthBaseController
{
    /**
     * 获得搜索发现列表
     */
    public function searchHotList()
    {
        
        $searchHotFoodshopService = new SearchHotFoodshopService();

        $param['page'] = $this->request->param("page", "1", "intval");
        // 每页显示数量
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");

        // 获得列表
        $list = $searchHotFoodshopService->getSearchHotList($param,$this->systemUser);

        
        return api_output(0, $list);
    }   

    /**
     * 获得编辑所需数据
     */
    public function getSearchHotDetail()
    {
        
        $searchHotFoodshopService = new SearchHotFoodshopService();

        // ID
        $param['id'] = $this->request->param("id", "", "intval");

        $detail = $searchHotFoodshopService->getSearchHotDetail($param);
        return api_output(0, $detail);
    }
    
    /**
     * 保存搜索发现列表
     */
    public function saveSearchHot()
    {
        
        $searchHotFoodshopService = new SearchHotFoodshopService();

        $param['id'] = $this->request->param("id", "1", "intval");
        $param['name'] = $this->request->param("name", "", "trim");
        $param['sort'] = $this->request->param("sort", "1", "intval");
        $param['is_hot'] = $this->request->param("is_hot", "1", "intval");

        // 获得列表
        $list = $searchHotFoodshopService->saveSearchHot($param,$this->systemUser);

        
        return api_output(0, $list);
    }  

    
    /**
     * 删除
     */
    public function delSearchHot()
    {
        
        $searchHotFoodshopService = new SearchHotFoodshopService();
        // ID
        $param['id'] = $this->request->param("id", "", "intval");
        try {
            $list = $searchHotFoodshopService->delSearchHot($param);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0, $list);
    }   
    
    /**
    * 保存排序
    */
   public function saveSort()
   {
       
        $searchHotFoodshopService = new SearchHotFoodshopService();
       // ID
       $param['id'] = $this->request->param("id", "", "intval");
       // 排序值
       $param['sort'] = $this->request->param("sort", "", "intval");
       
       // 保存店铺排序
       $res = $searchHotFoodshopService->saveSort($param);
       return api_output(0, $res);
   }
}
