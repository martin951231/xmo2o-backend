<?php

namespace app\social_activity\model\service;

use app\social_activity\model\db\SocialActivityMenu;
use app\social_activity\model\db\SocialActivityAdmin;
use app\social_activity\model\db\SocialActivityRole;
use app\social_activity\model\db\SocialActivityRoleMenu;

class SocialActivityMenuService
{
    /**
     * 获取菜单列表
     * @param $param
     * @return array
     */
    public function getMenuList($param)
    {
        $field = 'id,pid,name,title,icon,type,component,path,perms,sort,status,create_time,update_time';
        $paginate = (new SocialActivityMenu())
            ->with(['parent' => function ($query) {
                $query->field('id,title');
            }])
            ->withSearch(['title', 'type', 'status'], [
                'title' => $param['title'] ?? '',
                'type' => isset($param['type']) && $param['type'] !== '' ? $param['type'] : null,
                'status' => isset($param['status']) && $param['status'] !== '' ? $param['status'] : null,
            ])
            ->field($field)
            ->order('sort DESC,id ASC')
            ->paginate([
                'list_rows' => $param['pageSize'],
                'page' => $param['page'],
            ]);
        $list = $paginate ? $paginate->items() : [];

        foreach ($list as &$item) {
            $item['parent_name'] = $item['pid'] > 0 ? ($item['parent']['title'] ?? '') : '顶级菜单';
            unset($item['parent']);
        }

        return [
            'list' => $list,
            'count' => $paginate->total(),
            'total_limit' => $param['pageSize'],
        ];
    }

    /**
     * 获取菜单树形列表
     * @param $param
     * @return array
     */
    public function getMenuTreeList($param)
    {
        $where = [];
        if (isset($param['status']) && $param['status'] !== '') {
            $where[] = ['status', '=', intval($param['status'])];
        }

        $field = 'id,pid,name,title,icon,type,component,path,perms,sort,status';
        $list = (new SocialActivityMenu())->where($where)->field($field)->order('sort DESC,id ASC')->select()->toArray();
        return $this->buildTree($list);
    }

    /**
     * 获取菜单详情
     * @param $param
     * @return array
     */
    public function getMenuDetail($param)
    {
        if (!$param['id']) {
            throw_exception('查看对象不能为空！');
        }
        $info = (new SocialActivityMenu())->getMenuById($param['id']);
        if (!$info || $info->isEmpty()) {
            throw_exception('查看对象不存在或已被删除！');
        }
        return $info->toArray();
    }

    /**
     * 保存菜单（新增/编辑）
     * @param $param
     * @return bool
     */
    public function saveMenu($param)
    {
        $id = $param['id'] ?? 0;

        if ($id > 0) {
            // 编辑模式
            $info = (new SocialActivityMenu())->getMenuById($id);
            if (!$info || $info->isEmpty()) {
                throw_exception('编辑对象不存在！');
            }

            // 检查菜单标识是否重复
            if (isset($param['name']) && $param['name'] != $info['name']) {
                $existMenu = (new SocialActivityMenu())->where(['name' => $param['name']])->find();
                if ($existMenu && !$existMenu->isEmpty()) {
                    throw_exception('菜单标识已存在！');
                }
            }

            $data = [
                'pid' => isset($param['pid']) ? intval($param['pid']) : $info['pid'],
                'name' => $param['name'] ?? $info['name'],
                'title' => $param['title'] ?? $info['title'],
                'icon' => $param['icon'] ?? $info['icon'],
                'type' => isset($param['type']) ? intval($param['type']) : $info['type'],
                'component' => isset($param['component']) ? $param['component'] : $info['component'],
                'path' => $param['path'] ?? $info['path'],
                'perms' => $param['perms'] ?? $info['perms'],
                'sort' => isset($param['sort']) ? intval($param['sort']) : $info['sort'],
                'status' => isset($param['status']) ? intval($param['status']) : $info['status'],
            ];

            $result = (new SocialActivityMenu())->where(['id' => $id])->save($data);
            if (!$result) {
                throw_exception('编辑失败！');
            }
        } else {
            // 新增模式
            $existMenu = (new SocialActivityMenu())->where(['name' => $param['name']])->find();
            if ($existMenu && !$existMenu->isEmpty()) {
                throw_exception('菜单标识已存在！');
            }

            $data = [
                'pid' => intval($param['pid']),
                'name' => $param['name'],
                'title' => $param['title'],
                'icon' => $param['icon'] ?? '',
                'type' => intval($param['type']),
                'component' => $param['component'] ?? null,
                'path' => $param['path'] ?? '',
                'perms' => $param['perms'],
                'sort' => intval($param['sort']),
                'status' => intval($param['status']),
            ];

            $result = (new SocialActivityMenu())->save($data);
            if (!$result) {
                throw_exception('新增失败！');
            }
        }

        return true;
    }

    /**
     * 删除菜单
     * @param $param
     * @return bool
     */
    public function deleteMenu($param)
    {
        if (!$param['id']) {
            throw_exception('删除对象不能为空！');
        }
        $info = (new SocialActivityMenu())->getMenuById($param['id']);
        if (!$info || $info->isEmpty()) {
            throw_exception('删除对象不存在！');
        }

        // 检查是否有子菜单
        $childCount = (new SocialActivityMenu())->where(['pid' => $param['id']])->count();
        if ($childCount > 0) {
            throw_exception('该菜单下有子菜单，无法删除！');
        }

        $result = (new SocialActivityMenu())->where(['id' => $param['id']])->delete();
        if (!$result) {
            throw_exception('删除失败！');
        }
        return true;
    }

    /**
     * 修改菜单状态
     * @param $param
     * @return bool
     */
    public function changeStatus($param)
    {
        if (!$param['id']) {
            throw_exception('操作对象不能为空！');
        }
        $info = (new SocialActivityMenu())->getMenuById($param['id']);
        if (!$info || $info->isEmpty()) {
            throw_exception('操作对象不存在！');
        }

        $result = (new SocialActivityMenu())->where(['id' => $param['id']])->save(['status' => intval($param['status'])]);
        if (!$result) {
            throw_exception('操作失败！');
        }
        return true;
    }

    /**
     * 根据管理员账号获取权限菜单列表
     * 超级管理员（role_id=1）不限制菜单权限，返回所有菜单
     * @param $admin_id
     * @return array
     */
    public function getAdminMenuList($admin_id)
    {
        if (empty($admin_id)) {
            throw_exception('管理员ID不能为空！');
        }

        // 获取管理员信息
        $adminInfo = (new SocialActivityAdmin())->getAdminById($admin_id);
        if (!$adminInfo || $adminInfo->isEmpty()) {
            throw_exception('管理员不存在！');
        }
        $adminInfo = $adminInfo->toArray();

        // 检查管理员状态
        if ($adminInfo['status'] != 1) {
            throw_exception('账号已被禁用！');
        }

        $role_id = intval($adminInfo['role_id']);
        if (empty($role_id)) {
            throw_exception('管理员未分配角色！');
        }

        $roleInfo = (new SocialActivityRole())->getRoleById($role_id);
        if (!$roleInfo || $roleInfo->isEmpty()) {
            throw_exception('角色不存在！');
        }
        $roleInfo = $roleInfo->toArray();

        // 检查角色状态
        if ($roleInfo['status'] != 1) {
            throw_exception('角色已被禁用！');
        }

        if ($role_id == 1) {
            // 超级管理员获取所有启用的菜单
            $where = [
                ['status', '=', 1],
            ];
        } else {
            // 普通管理员根据角色获取菜单权限
            $menuIds = (new SocialActivityRoleMenu())->getMenuIdsByRoleId($role_id);
            if (empty($menuIds)) {
                return [];
            }
            // 获取菜单详情
            $where = [
                ['id', 'in', $menuIds],
                ['status', '=', 1],
            ];
        }
        // 获取所有菜单（type 1和2）
        $menuField = 'id,pid,name,title,icon,type,component,path,perms action,sort';
        $allMenus = SocialActivityMenu::where($where)
            ->where('type', 'IN', [1, 2])
            ->field($menuField)
            ->order('sort DESC,id ASC')
            ->select()
            ->toArray();
        // 获取所有权限按钮（type 3）
        $permField = 'id,pid,perms action,title';
        $allPermissions = (new SocialActivityMenu())
            ->where($where)
            ->where('type', 3)
            ->field($permField)
            ->order('sort DESC,id ASC')
            ->select()
            ->toArray();
        // 按pid分组权限按钮
        $permGroup = [];
        foreach ($allPermissions as $perm) {
            $pid = $perm['pid'];
            if (!isset($permGroup[$pid])) {
                $permGroup[$pid] = [];
            }
            $permGroup[$pid][] = [
                'id' => $perm['id'],
                'action' => $perm['action'] ?? '',
                'title' => $perm['title'] ?? '',
            ];
        }
	    return $this->buildTreeWithPermissions($allMenus, $permGroup);
    }

    /**
     * 根据管理员账号获取权限标识列表
     * 超级管理员（role_id=1）返回所有权限标识
     * @param $admin_id
     * @return array
     */
    public function getAdminPermsList($admin_id)
    {
        if (empty($admin_id)) {
            throw_exception('管理员ID不能为空！');
        }

        // 获取管理员信息
        $adminInfo = (new SocialActivityAdmin())->getAdminById($admin_id);
        if (!$adminInfo || $adminInfo->isEmpty()) {
            throw_exception('管理员不存在！');
        }
        $adminInfo = $adminInfo->toArray();

        // 检查管理员状态
        if ($adminInfo['status'] != 1) {
            throw_exception('账号已被禁用！');
        }

        $role_id = intval($adminInfo['role_id']);
        if (empty($role_id)) {
            throw_exception('管理员未分配角色！');
        }

        // 获取角色信息，判断是否为超级管理员
        $roleInfo = (new SocialActivityRole())->getRoleById($role_id);
        if (!$roleInfo || $roleInfo->isEmpty()) {
            throw_exception('角色不存在！');
        }
        $roleInfo = $roleInfo->toArray();

        // 检查角色状态
        if ($roleInfo['status'] != 1) {
            throw_exception('角色已被禁用！');
        }

        // 超级管理员（role_id=1）返回所有权限标识
        $perms = [];
        if ($role_id == 1) {
            // 超级管理员获取所有启用的菜单权限标识
            $where = [
                ['status', '=', 1],
                ['perms', '<>', ''],
            ];
            $perms = (new SocialActivityMenu())->where($where)->column('perms');
        } else {
            // 普通管理员根据角色获取权限标识
            $menuIds = (new SocialActivityRoleMenu())->getMenuIdsByRoleId($role_id);
            if (empty($menuIds)) {
                return [];
            }

            $where = [
                ['id', 'in', $menuIds],
                ['status', '=', 1],
                ['perms', '<>', ''],
            ];
            $perms = (new SocialActivityMenu())->where($where)->column('perms');
        }

        return $perms;
    }

    /**
     * 构建树形结构
     * @param $list
     * @param int $pid
     * @return array
     */
    private function buildTree($list, $pid = 0)
    {
        $tree = [];
        foreach ($list as $item) {
            if ($item['pid'] == $pid) {
                $children = $this->buildTree($list, $item['id']);
                if ($children) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }

    /**
     * 构建树形结构并附加权限按钮
     * @param $list
     * @param $permGroup
     * @param int $pid
     * @return array
     */
    private function buildTreeWithPermissions($list, $permGroup, $pid = 0)
    {
        $tree = [];
        foreach ($list as $item) {
            if ($item['pid'] == $pid) {
                $children = $this->buildTreeWithPermissions($list, $permGroup, $item['id']);
                if ($children) {
                    $item['children'] = $children;
                }
                // 附加权限按钮
                $item['permissions'] = $permGroup[$item['id']] ?? [];
                $tree[] = $item;
            }
        }
        return $tree;
    }
}
