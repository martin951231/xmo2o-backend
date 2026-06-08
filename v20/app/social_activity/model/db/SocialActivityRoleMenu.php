<?php

namespace app\social_activity\model\db;

use think\Model;

class SocialActivityRoleMenu extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 根据角色ID获取菜单ID列表
     * @param $role_id
     * @return array|bool
     */
    public function getMenuIdsByRoleId($role_id)
    {
        if (empty($role_id)) {
            return false;
        }
        $where = ["role_id" => trim($role_id)];
        $result = $this->where($where)->column('menu_id');
        return $result;
    }

    /**
     * 根据角色ID列表获取菜单ID列表
     * @param $role_ids
     * @return array
     */
    public function getMenuIdsByRoleIds($role_ids)
    {
        if (empty($role_ids)) {
            return [];
        }
        $where = ["role_id" => ['in', $role_ids]];
        $result = $this->where($where)->column('menu_id');
        return array_unique($result);
    }

    /**
     * 批量保存角色菜单关联
     * @param $role_id
     * @param $menu_ids
     * @return bool
     */
    public function batchSave($role_id, $menu_ids)
    {
        if (empty($role_id) || empty($menu_ids)) {
            return false;
        }
        // 先删除旧的关联
        $this->where(['role_id' => $role_id])->delete();
        // 批量插入新的关联
        $data = [];
        foreach ($menu_ids as $menu_id) {
            $data[] = [
                'role_id' => $role_id,
                'menu_id' => $menu_id,
            ];
        }
        return $this->insertAll($data);
    }
}
