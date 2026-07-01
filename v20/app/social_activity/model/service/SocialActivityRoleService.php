<?php

namespace app\social_activity\model\service;

use app\social_activity\model\db\SocialActivityRole;
use app\social_activity\model\db\SocialActivityRoleMenu;

class SocialActivityRoleService
{
    /**
     * 获取角色列表
     * @param $param
     * @return \think\Paginator
     */
    public function getRoleList($param)
    {
        $field = 'id,name,description,status,create_time,update_time';
        $paginate = (new SocialActivityRole())
            ->with(['role_menus'])
            ->withSearch(['name', 'status'], [
                'name' => $param['name'] ?? '',
                'status' => isset($param['status']) && $param['status'] !== '' ? $param['status'] : null,
            ])
            ->field($field)
            ->paginate([
                'list_rows' => $param['pageSize'],
                'page' => $param['page'],
            ]);
        $list = $paginate ? $paginate->items() : [];

        foreach ($list as &$item) {
            $item['menus'] = $item->role_menus->toArray();
	        $item['menus'] = array_column($item['menus'], 'menu_id');
            unset($item['role_menus']);
        }

        return $paginate;
    }

    /**
     * 获取角色详情
     * @param $param
     * @return array
     */
    public function getRoleDetail($param)
    {
        if (!$param['id']) {
            throw_exception('查看对象不能为空！');
        }
        $info = (new SocialActivityRole())
            ->withCount(['roleMenus'])
            ->find($param['id']);
        if (!$info || $info->isEmpty()) {
            throw_exception('查看对象不存在或已被删除！');
        }
        $info = $info->toArray();

        $info['menu_count'] = $info['role_menus_count'] ?? 0;
        unset($info['role_menus_count']);

        $info['menu_ids'] = (new SocialActivityRoleMenu())->getMenuIdsByRoleId($param['id']) ?: [];

        return $info;
    }

    /**
     * 保存角色（新增/编辑）
     * @param $param
     * @return bool
     */
    public function saveRole($param)
    {
        $id = $param['id'] ?? 0;
        if ($id > 0) {
            // 编辑模式
            if ($id == 1) {
                throw_exception('超级管理员角色不可编辑！');
            }
            
            $info = (new SocialActivityRole())->getRoleById($id);
            if (!$info || $info->isEmpty()) {
                throw_exception('编辑对象不存在！');
            }

            // 检查角色名称是否重复
            if (isset($param['name']) && $param['name'] != $info['name']) {
                $existRole = (new SocialActivityRole())->where(['name' => $param['name']])->find();
                if ($existRole && !$existRole->isEmpty()) {
                    throw_exception('角色名称已存在！');
                }
            }

            $data = [
                'name' => $param['name'] ?? $info['name'],
                'description' => $param['description'] ?? $info['description'],
                'status' => isset($param['status']) ? intval($param['status']) : $info['status'],
            ];

            $result = (new SocialActivityRole())->where(['id' => $id])->save($data);
            if ($result === false) {
                throw_exception('编辑失败！');
            }

            // 更新角色菜单关联
            if (isset($param['menu_ids']) && is_array($param['menu_ids'])) {
                (new SocialActivityRoleMenu())->batchSave($id, $param['menu_ids']);
            }
        } else {
            // 新增模式
            $existRole = (new SocialActivityRole())->where(['name' => $param['name']])->find();
            if ($existRole && !$existRole->isEmpty()) {
                throw_exception('角色名称已存在！');
            }

            $data = [
                'name' => $param['name'],
                'description' => $param['description'] ?? '',
                'status' => intval($param['status']),
            ];

            $role = (new SocialActivityRole())->create($data);
            // 保存角色菜单关联
            if (!empty($param['menu_ids']) && is_array($param['menu_ids'])) {
                (new SocialActivityRoleMenu())->batchSave($role->id, $param['menu_ids']);
            }
        }

        return true;
    }

    /**
     * 删除角色
     * @param $param
     * @return bool
     */
    public function deleteRole($param)
    {
        if (!$param['id']) {
            throw_exception('删除对象不能为空！');
        }
        $info = (new SocialActivityRole())->getRoleById($param['id']);
        if (!$info || $info->isEmpty()) {
            throw_exception('删除对象不存在！');
        }

        // 检查是否有管理员使用该角色
        $adminCount = \app\social_activity\model\db\SocialActivityAdmin::where(['role_id' => $param['id'], 'status' => 1])->count();
        if ($adminCount > 0) {
            throw_exception('该角色下有管理员，无法删除！');
        }

        // 删除角色
        $result = (new SocialActivityRole())->where(['id' => $param['id']])->delete();
        if ($result === false) {
            throw_exception('删除失败！');
        }

        // 删除角色菜单关联
        (new SocialActivityRoleMenu())->where(['role_id' => $param['id']])->delete();

        return true;
    }

    /**
     * 修改角色状态
     * @param $param
     * @return bool
     */
    public function changeStatus($param)
    {
        if (!$param['id']) {
            throw_exception('操作对象不能为空！');
        }
        $info = (new SocialActivityRole())->getRoleById($param['id']);
        if (!$info || $info->isEmpty()) {
            throw_exception('操作对象不存在！');
        }

        $result = (new SocialActivityRole())->where(['id' => $param['id']])->save(['status' => intval($param['status'])]);
        if (!$result) {
            throw_exception('操作失败！');
        }
        return true;
    }
}
