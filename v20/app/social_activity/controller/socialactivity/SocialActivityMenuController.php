<?php

namespace app\social_activity\controller\socialactivity;

use app\social_activity\controller\socialactivity\AuthBaseController as BaseController;
use app\social_activity\model\service\SocialActivityMenuService;
use app\social_activity\validate\SocialActivityMenuValidate;

class SocialActivityMenuController extends BaseController
{
    protected $validateClass = SocialActivityMenuValidate::class;

    /**
     * 获取菜单列表
     */
    public function getMenuList()
    {
        $param['page'] = $this->request->param('page', 1, 'int');
        $param['pageSize'] = $this->request->param('pageSize', 20, 'int');
        $param['title'] = $this->request->param('title', '', 'trim');
        $param['status'] = $this->request->param('status', '', 'trim');
        $param['type'] = $this->request->param('type', '', 'trim');

        $menuService = new SocialActivityMenuService();
        try {
            $data = $menuService->getMenuList($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $data, "success");
    }

    /**
     * 获取菜单树形列表
     */
    public function getMenuTreeList()
    {
        $param['status'] = $this->request->param('status', 1, 'int');
        $menuService = new SocialActivityMenuService();
        try {
            $data = $menuService->getMenuTreeList($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $data, "success");
    }

    /**
     * 获取菜单详情
     */
    public function getMenuDetail()
    {
        $param = $this->validateParameter([], 'detail');
        $menuService = new SocialActivityMenuService();
        try {
            $data = $menuService->getMenuDetail($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $data, "success");
    }

    /**
     * 保存菜单（新增/编辑）
     */
    public function saveMenu()
    {
        $param['id'] = $this->request->param('id', 0, 'int');
        $param['pid'] = $this->request->param('pid', 0, 'int');
        $param['name'] = $this->request->param('name', '', 'trim');
        $param['title'] = $this->request->param('title', '', 'trim');
        $param['icon'] = $this->request->param('icon', '', 'trim');
        $param['type'] = $this->request->param('type', 2, 'int');
        $param['component'] = $this->request->param('component', '', 'trim');
        $param['path'] = $this->request->param('path', '', 'trim');
        $param['perms'] = $this->request->param('perms', '', 'trim');
        $param['sort'] = $this->request->param('sort', 0, 'int');
        $param['status'] = $this->request->param('status', 1, 'int');
        $scene = $param['id'] > 0 ? 'edit' : 'add';
        $param = $this->validateParameter($param, $scene);
        $menuService = new SocialActivityMenuService();
        try {
            $menuService->saveMenu($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        $msg = $param['id'] > 0 ? "编辑成功" : "新增成功";
        return api_output(0, [], $msg);
    }

    /**
     * 删除菜单
     */
    public function deleteMenu()
    {
        $param = $this->validateParameter([], 'delete');
        $menuService = new SocialActivityMenuService();
        try {
            $menuService->deleteMenu($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, [], "删除成功");
    }

    /**
     * 修改菜单状态
     */
    public function changeStatus()
    {
        $param = $this->validateParameter([], 'status');
        $param['id'] = $this->request->param('id', 0, 'int');
        $menuService = new SocialActivityMenuService();
        try {
            $menuService->changeStatus($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, [], "操作成功");
    }

    /**
     * 根据管理员账号获取权限菜单列表
     * 超级管理员（role_id=1）不限制菜单权限，返回所有菜单
     */
    public function getAdminMenuList()
    {
        $param['admin_id'] = $this->request->param('admin_id', 0, 'int');
        $menuService = new SocialActivityMenuService();
        try {
            $data = $menuService->getAdminMenuList($param['admin_id']);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $data, "success");
    }

    /**
     * 根据管理员账号获取权限标识列表
     * 超级管理员（role_id=1）返回所有权限标识
     */
    public function getAdminPermsList()
    {
        $param['admin_id'] = $this->request->param('admin_id', 0, 'int');
        $menuService = new SocialActivityMenuService();
        try {
            $data = $menuService->getAdminPermsList($param['admin_id']);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $data, "success");
    }
}
