<?php

namespace app\social_activity\controller\socialactivity;

use app\social_activity\controller\socialactivity\AuthBaseController as BaseController;
use app\social_activity\model\service\SocialActivityRoleService;
use app\social_activity\validate\SocialActivityRoleValidate;

class SocialActivityRoleController extends BaseController
{
    protected $validateClass = SocialActivityRoleValidate::class;

    /**
     * 获取角色列表
     */
    public function getRoleList()
    {
        $param['page'] = $this->request->param('page', 1, 'int');
        $param['pageSize'] = $this->request->param('pageSize', 20, 'int');
        $param['name'] = $this->request->param('name', '', 'trim');
        $param['status'] = $this->request->param('status', '', 'trim');

        $roleService = new SocialActivityRoleService();
        try {
            $data = $roleService->getRoleList($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $data, "success");
    }

    /**
     * 获取角色详情
     */
    public function getRoleDetail()
    {
        $param = $this->validateParameter([], 'detail');
        $roleService = new SocialActivityRoleService();
        try {
            $data = $roleService->getRoleDetail($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $data, "success");
    }

    /**
     * 保存角色（新增/编辑）
     */
    public function saveRole()
    {
        $param['id'] = $this->request->param('id', 0, 'int');
        $param['name'] = $this->request->param('name', '', 'trim');
        $param['description'] = $this->request->param('description', '', 'trim');
        $param['status'] = $this->request->param('status', 1, 'int');
        $scene = $param['id'] > 0 ? 'edit' : 'add';
        $param = $this->validateParameter($param, $scene);
        $param['menu_ids'] = $this->request->param('menu_ids', []);
        $roleService = new SocialActivityRoleService();
        try {
            $roleService->saveRole($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        $msg = $param['id'] > 0 ? "编辑成功" : "新增成功";
        return api_output(0, [], $msg);
    }

    /**
     * 删除角色
     */
    public function deleteRole()
    {
        $param = $this->validateParameter([], 'delete');
        $roleService = new SocialActivityRoleService();
        try {
            $roleService->deleteRole($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, [], "删除成功");
    }

    /**
     * 修改角色状态
     */
    public function changeStatus()
    {
        $param = $this->validateParameter([], 'status');
        $param['id'] = $this->request->param('id', 0, 'int');
        $roleService = new SocialActivityRoleService();
        try {
            $roleService->changeStatus($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, [], "操作成功");
    }
}
