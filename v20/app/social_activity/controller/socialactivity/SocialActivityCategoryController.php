<?php

namespace app\social_activity\controller\socialactivity;

use app\social_activity\controller\socialactivity\AuthBaseController as BaseController;
use app\social_activity\model\service\SocialActivityCategoryService;

class SocialActivityCategoryController extends BaseController
{
    /**
     * 获取分类列表
     */
    public function getCategoryList()
    {
        $param['page'] = $this->request->param('page', 1, 'int');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'int');
        
        $categoryService = new SocialActivityCategoryService();
        try {
            $data = $categoryService->getCategoryList($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $data, "success");
    }

    /**
     * 获取分类详情
     */
    public function getCategoryDetail()
    {
        $param['id'] = $this->request->param('id', 0, 'int');
        
        $categoryService = new SocialActivityCategoryService();
        try {
            $data = $categoryService->getCategoryDetail($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $data, "success");
    }

    /**
     * 保存分类（新增/编辑）
     */
    public function saveCategory()
    {
        $param['id'] = $this->request->param('id', 0, 'int');
        $param['name'] = $this->request->param('name', '', 'trim');
        $param['creator'] = $this->socialUser['realname'] ?: $this->socialUser['account'];
        $param['modifier'] = $this->socialUser['realname'] ?: $this->socialUser['account'];
        $param['creator_uid'] = $this->socialUser['id'];
        $param['modifier_uid'] = $this->socialUser['id'];
        
        $categoryService = new SocialActivityCategoryService();
        try {
            $categoryService->saveCategory($param, $param['id']);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        $msg = $param['id'] > 0 ? "编辑成功" : "新增成功";
        return api_output(0, [], $msg);
    }

    /**
     * 删除分类
     */
    public function deleteCategory()
    {
        $param['id'] = $this->request->param('id', 0, 'int');
        
        $categoryService = new SocialActivityCategoryService();
        try {
            $categoryService->deleteCategory($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, [], "删除成功");
    }
}