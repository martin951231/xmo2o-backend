<?php

/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/4/8 13:33
 */

namespace app\community\controller\house_meter;

use app\common\model\db\AccessTokenExpires;
use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseMeterService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageUserService;
use app\community\model\service\ManageAppLoginService;
use app\community\model\service\UserService;

class AdminUserController extends CommunityBaseController
{

    /**
     * 修改密码
     * @param 传参
     * array (
     *  'old_password'=> '原密码',
     *  'new_password' => '新密码'
     *  'confirm_password' => '确认密码'
     * )
     * @return \json
     * @author: zhubaodi
     * @date_time: 2021/4/8 15:31
     */
    public function passwordEdit()
    {
        $old_password = $this->request->param('old_password', '', 'trim');
        if (empty($old_password)) {
            return api_output_error(1001, '请上传原密码！');
        }
        $new_password = $this->request->param('new_password', '', 'trim');
        if (empty($new_password)) {
            return api_output_error(1001, '请上传新密码！');
        }
        if (strlen($new_password) < 6) {
            return api_output_error(1001, '新密码长度不能低于六位！');
        }
        $confirm_password = $this->request->param('confirm_password', '', 'trim');
        if (empty($confirm_password)) {
            return api_output_error(1001, '请上传确认密码！');
        }
        $admin_id = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        $pwd_data = [
            'old_password' => $old_password,
            'new_password' => $new_password,
            'confirm_password' => $confirm_password,
        ];
        $serviceHouseMeter = new HouseMeterService();
        try {
            $data = $serviceHouseMeter->editPassword($admin_id, $pwd_data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($data) {
            return api_output(0, $data, "修改成功");
        }
        return api_output_error(-1, "修改失败");
    }


    /**
     * 获取管理员列表
     * @return \json
     * @author: zhubaodi
     * @date_time: 2021/4/8 16:31
     */
    public function adminUserList()
    {
        // 获取登录信息
        $admin_id = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        //  $admin_id=1;
        if (!$admin_id) {
            return api_output(1002, [], '请先登录！');
        }
        $page = $this->request->param('page', 0, 'intval');
        $limit = 20;
        $serviceHouseMeter = new HouseMeterService();

        try {
            $adminUserList = $serviceHouseMeter->getAdminUserList($admin_id, $page, $limit);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        $adminUserList['total_limit'] = $limit;
        return api_output(0, $adminUserList, '成功');


    }

    /**
     * 获取管理员信息详情
     * @param integer $id
     * @date_time: 2021/4/8 16:31
     * @return \json
     * @author: zhubaodi
     */
    public function adminUserInfo()
    {
        // 获取登录信息
        $admin_id = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        //  $admin_id=1;
        if (!$admin_id) {
            return api_output(1002, [], '请先登录！');
        }
        $id = $this->request->param('id', '', 'intval');
        $serviceHouseMeter = new HouseMeterService();
        $adminUserInfo = $serviceHouseMeter->getAdminUserInfo($admin_id, $id);
        return api_output(0, $adminUserInfo);

    }

    /**
     * 添加管理员
     * @param 传参
     * array (
     *  'username'=> '登录账号 必传',
     *  'password'=> '登录密码  必传',
     *  'confirm_password'=> '确认密码  必传',
     *  'name'=> '姓名',
     *  'phone'=> '手机号',
     *  'qx'=> '管理员权限 1：总管理员 0：普通管理员 必传',
     *  'remark'=> '备注',
     * )
     * @return \json
     * @author: zhubaodi
     * @date_time: 2021/4/8 17:40
     */
    public function adminUserAdd()
    {
        // 获取登录信息
        $admin_id = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        //  $admin_id=1;
        if (!$admin_id) {
            return api_output(1002, [], '用户未登录或登录已失效，请先登录！！');
        }
        $village_id = $this->request->param('list', '', 'trim');
        $detail = $this->request->param('detail', '', 'trim');
        // print_r($detail);exit;
        $username = $detail['username'];
        if (empty($username)) {
            return api_output(1001, [], '请上传登录账号！');
        }
        $password = $detail['password'];
        if (empty($password)) {
            return api_output(1001, [], '请上传登录密码！');
        }
        if (strlen($password) < 6) {
            return api_output_error(1001, '新密码长度不能低于六位！');
        }
        $confirm_password = $detail['confirm_password'];
        if (empty($password)) {
            return api_output(1001, [], '请上传确认密码！');
        }
        $qx = $detail['qx'];
        if (empty($password)) {
            return api_output(1001, [], '请选择管理员权限！');
        }
        $name = $detail['name'];
        $phone = $detail['phone'];
        $remark = $detail['remark'];


        $user_data = [
            'username' => $username,
            'password' => $password,
            'confirm_password' => $confirm_password,
            'name' => $name,
            'phone' => $phone,
            'qx' => $qx,
            'remark' => $remark,
        ];

        $serviceHouseMeter = new HouseMeterService();
        try {
            $user_id = $serviceHouseMeter->addAdminUser($admin_id, $user_data, $village_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($user_id) {
            return api_output(0, ['user_id' => $user_id], '添加成功');
        } else {
            return api_output(1003, [], '添加失败！');
        }

    }

    /**
     * 编辑管理员信息
     * @param 传参
     * array (
     *  'id'=>'登录id 必传',
     *  'username'=> '登录账号 必传',
     *  'password'=> '登录密码  必传',
     *  'confirm_password'=> '确认密码  必传',
     *  'name'=> '姓名',
     *  'phone'=> '手机号',
     *  'qx'=> '管理员权限 1：总管理员 0：普通管理员 必传',
     *  'remark'=> '备注',
     * )
     * @return \json
     * @author: zhubaodi
     * @date_time: 2021/4/8 17:40
     */
    public function adminUserEdit()
    {
        // 获取登录信息
        $admin_id = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        //  $admin_id=1;
        if (!$admin_id) {
            return api_output(1002, [], '用户未登录或登录已失效，请先登录！！');
        }

        $id = $this->request->param('id', '', 'intval');
        if (empty($id)) {
            return api_output(1001, [], '请上传登录账号！');
        }
        /* $username = $this->request->param('username','','trim');
         if (empty($username)) {
             return api_output(1001,[],'请上传登录账号！');
         }*/
        $password = $this->request->param('password', '', 'trim');
        if (empty($password)) {
            return api_output(1001, [], '请上传登录密码！');
        }
        $confirm_password = $this->request->param('confirm_password', '', 'trim');
        if (empty($password)) {
            return api_output(1001, [], '请上传确认密码！');
        }
        $qx = $this->request->param('qx', '', 'intval');
        if (empty($password)) {
            return api_output(1001, [], '请选择管理员权限！');
        }
        $name = $this->request->param('name', '', 'trim');
        $phone = $this->request->param('phone', '', 'trim');

        $remark = $this->request->param('remark', '', 'trim');

        $user_data = [
            'id' => $id,
            'password' => $password,
            'confirm_password' => $confirm_password,
            'name' => $name,
            'phone' => $phone,
            'qx' => $qx,
            'remark' => $remark,
        ];
        $serviceHouseMeter = new HouseMeterService();
        try {
            $user_id = $serviceHouseMeter->editAdminUser($admin_id, $user_data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($user_id) {
            return api_output(0, ['user_id' => $user_id], '编辑成功');
        } else {
            return api_output(1003, [], '编辑失败！');
        }

    }


    /**
     * 删除管理员信息
     * @param integer $id
     * @return \json
     * @author: zhubaodi
     * @date_time: 2021/4/8 17:40
     */
    public function adminUserDelete()
    {
        // 获取登录信息
        $admin_id = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        //  $admin_id=1;
        if (!$admin_id) {
            return api_output(1002, [], '用户未登录或登录已失效，请先登录！！');
        }

        $id = $this->request->param('id', '', 'intval');
        if (empty($id)) {
            return api_output(1001, [], '请上传登录账号！');
        }
        $serviceHouseMeter = new HouseMeterService();
        $user_id = $serviceHouseMeter->deleteAdminUser($admin_id, $id);
        if ($user_id) {
            return api_output(0, ['user_id' => $user_id], '删除成功');
        } else {
            return api_output(1002, [], '删除失败');
        }
    }

    /**
     * 获取城市信息
     * @author:zhubaodi
     * @date_time: 2021/4/15 13:12
     */
    public function areaList()
    {
        $serviceHouseMeter = new HouseMeterService();

        try {
            $areaList = $serviceHouseMeter->getVillageTreeList();
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        //  print_r($areaList);exit;
        return api_output(0, $areaList, '成功');
    }
}
