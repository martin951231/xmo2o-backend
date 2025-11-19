<?php
/**
 * 团购分类
 * Author: hengtingmei
 * Date Time: 2020/11/16 11:16
 */

namespace app\group\controller\platform;
use app\group\controller\platform\AuthBaseController;
use app\group\model\service\GroupCategoryService;

class GroupCategoryController extends AuthBaseController
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

        $param = $this->request->param();
        // 获得列表
        $list = (new GroupCategoryService())->getCategoryTree($param);

        return api_output(0, $list);
    }

    /**
     * 获得团购分类列表
     */
    public function getGroupCategorylist()
    {
        $param['cat_id'] = $this->request->param("cat_id", "0", "intval");
        $param['page'] = $this->request->param("page", "1", "intval");
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");

        try {
            // 获得列表
            $res = (new GroupCategoryService())->getGrouptCategorylist($param);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 添加团购分类
     */
    public function addGroupCategory()
    {
        $param = $this->request->param();

        try {
            // 获得列表
            $res = (new GroupCategoryService())->addGroupCategory($param);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取团购分类信息
     */
    public function getGroupCategoryInfo()
    {
        $param = $this->request->param();

        try {
            // 获得详情
            $detail = (new GroupCategoryService())->getGroupCategoryInfo($param);
            return api_output(0, $detail);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除团购分类
     */
    public function delGroupCategory()
    {
        $param = $this->request->param();

        try {
            // 获得列表
            $res = (new GroupCategoryService())->delGroupCategory($param);
            return api_output(0);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    /**
     * 团购分类购买须知填写项列表
     */
    public function getGroupCategoryCueList()
    {
        $param['cat_id'] = $this->request->param("cat_id", "0", "intval");

        try {
            // 获得列表
            $res = (new GroupCategoryService())->getGroupCategoryCueList($param);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 团购分类购买须知填写项编辑
     */
    public function editGroupCategoryCue()
    {
        $param = $this->request->param();

        try {
            (new GroupCategoryService())->editGroupCategoryCue($param);
            return api_output(0);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 团购分类购买须知填写项删除
     */
    public function delGroupCategoryCue()
    {
        $param = $this->request->param();

        try {
            (new GroupCategoryService())->delGroupCategoryCue($param);
            return api_output(0);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 定制-管理商品属性字段 列表
     */
    public function getCatFieldList()
    {
        $param['cat_id'] = $this->request->param("cat_id", "0", "intval");

        try {
            // 获得列表
            $res = (new GroupCategoryService())->getCatFieldList($param);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 定制-管理商品属性字段 添加
     */
    public function addCatField()
    {
        $param = $this->request->param();

        try {
            (new GroupCategoryService())->addCatField($param);
            return api_output(0);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 定制-管理商品属性字段 前端显示隐藏
     */
    public function catFieldShow()
    {
        $param = $this->request->param();

        try {
            (new GroupCategoryService())->catFieldShow($param);
            return api_output(0);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取定制-自定义填写选项列表
     */
    public function getWriteFieldList()
    {
        $param['cat_id'] = $this->request->param("cat_id", "0", "intval");

        try {
            // 获得列表
            $res = (new GroupCategoryService())->getWriteFieldList($param);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 定制-自定义填写选项添加字段 操作
     */
    public function addWriteField()
    {
        $param = $this->request->param();

        try {
            (new GroupCategoryService())->addWriteField($param);
            return api_output(0);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    /**
     * 定制-自定义填写选项添加字段 删除
     */
    public function delWriteField()
    {
        $param = $this->request->param();

        try {
            (new GroupCategoryService())->delWriteField($param);
            return api_output(0);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 团购分类 保存排序
     */
    public function saveSort()
    {
        $param = $this->request->param();

        try {
            (new GroupCategoryService())->saveSort($param);
            return api_output(0);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 团购分类背景色 保存
     */
    public function updateGroupCategoryBgColor()
    {
        $param = $this->request->param();

        try {
            (new GroupCategoryService())->updateGroupCategoryBgColor($param);
            return api_output(0);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}
