<?php

namespace app\social_activity\controller\socialactivity;

use app\community\model\db\HouseVillage;
use app\social_activity\controller\socialactivity\AuthBaseController as BaseController;
use app\social_activity\model\service\SocialActivityAdminService;
use app\social_activity\validate\SocialActivityAdminValidate;

class SocialActivityAdminController extends BaseController
{
    protected $validateClass = SocialActivityAdminValidate::class;

    /**
     * 获取管理账号列表
     */
    public function getAdminList()
    {
        $param['page'] = input('page', 1, 'trim,intval');
        $param['pageSize'] = input('pageSize', 20, 'trim,intval');
        $param['account'] = input('account', '', 'trim');
        $param['realname'] = input('realname', '', 'trim');
        $param['phone'] = input('phone', '', 'trim');
        $param['status'] = input('status', '', 'trim');
        $param['role_id'] = input('role', 0, 'trim,intval');

        $adminService = new SocialActivityAdminService();
        try {
            $data = $adminService->getAdminList($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $data, "success");
    }

    /**
     * 获取管理账号详情
     */
    public function getAdminDetail()
    {
        $param = $this->validateParameter([], 'detail');
        $adminService = new SocialActivityAdminService();
        try {
            $data = $adminService->getAdminDetail($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $data, "success");
    }

	public function getVillageList()
	{
		$adminService = new HouseVillage();
		try {
			$data = $adminService->getVillageList(['status' => 1], ['village_name','village_id']);
		} catch (\Exception $e) {
			return api_output_error(-1, $e->getMessage());
		}

		return api_output(0, $data, "success");
	}

    /**
     * 保存管理账号（新增/编辑）
     */
    public function saveAdmin()
    {
        $param['id'] = input('id', 0, 'trim,intval');
        $param['account'] = input('account', '', 'trim');
        $param['password'] = input('password', '', 'trim');
        $param['realname'] = input('realname', '', 'trim');
        $param['phone'] = input('phone', '', 'trim');
        $param['role_id'] = input('role', 0, 'trim,intval');
        $param['village_id'] = input('village_ids', '', 'trim');
        $param['status'] = input('status', 1, 'trim,intval');
        $scene = $param['id'] > 0 ? 'edit' : 'add';
        $param = $this->validateParameter($param, $scene);
        $adminService = new SocialActivityAdminService();
        try {
			is_array($param['village_id']) && $param['village_id'] = implode(',', $param['village_id']);
            $adminService->saveAdmin($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        $msg = $param['id'] > 0 ? "编辑成功" : "新增成功";
        return api_output(0, [], $msg);
    }

    /**
     * 删除管理账号
     */
    public function deleteAdmin()
    {
        $param = $this->validateParameter([], 'delete');
        $adminService = new SocialActivityAdminService();
        try {
            $adminService->deleteAdmin($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, [], "删除成功");
    }

    /**
     * 修改账号状态
     */
    public function changeStatus()
    {
        $param = $this->validateParameter([], 'status');
        $param['id'] = input('id', 0, 'trim,intval');
        $adminService = new SocialActivityAdminService();
        try {
            $adminService->changeStatus($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, [], "操作成功");
    }

    /**
     * desc: 返回用户信息
     * return :array
     */
    public function userInfo(){
        $params = [
            'uid' => $this->_uid,
        ];
        $returnArr = (new SocialActivityAdminService())->formatUserData($params);
        if (!$returnArr) {
            return api_output_error(1002, "用户不存在或未登录");
        }
        return api_output(0, $returnArr);
    }
}
