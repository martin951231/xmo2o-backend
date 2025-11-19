<?php
/**
 * 团购分类
 * Author: hengtingmei
 * Date Time: 2020/11/16 11:16
 */

namespace app\group\controller\api;
use app\group\model\service\GroupCategoryService;

class GroupCategoryController extends ApiBaseController
{
    
    /**
     * 获得团购分类列表
     */
    public function getGroupFirstCategorylist()
    {
        // 获得列表
        $list = (new GroupCategoryService())->getGroupFirstCategorylist();
        
        return api_output(0, $list);
    }


    /**
     * 获得团购分类列表
     */
    public function getCategoryTree()
    {
        // 获得列表
        $list = (new GroupCategoryService())->getCategoryTree();

        return api_output(0, $list);
    }
}
