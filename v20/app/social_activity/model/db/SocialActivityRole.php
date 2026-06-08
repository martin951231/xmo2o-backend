<?php

namespace app\social_activity\model\db;

use think\Model;

class SocialActivityRole extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 根据ID获取角色信息
     * @param $id
     * @return array|bool|Model|null
     */
    public function getRoleById($id)
    {
        if (empty($id)) {
            return false;
        }
        $where = ["id" => intval($id)];
        $result = $this->where($where)->find();
        return $result;
    }

    /**
     * 状态获取器
     * @param $value
     * @return string
     */
    public function getStatusTextAttr($value, $data)
    {
        $statusMap = [
            0 => '禁用',
            1 => '正常',
        ];
        return $statusMap[$data['status']] ?? '未知';
    }

    /**
     * 创建时间获取器 - 格式化
     * @param $value
     * @return string
     */
    public function getCreateTimeTextAttr($value, $data)
    {
        if (empty($data['create_time'])) {
            return '';
        }
        return date('Y-m-d H:i:s', $data['create_time']);
    }

    /**
     * 更新时间获取器 - 格式化
     * @param $value
     * @return string
     */
    public function getUpdateTimeTextAttr($value, $data)
    {
        if (empty($data['update_time'])) {
            return '';
        }
        return date('Y-m-d H:i:s', $data['update_time']);
    }

    /**
     * 名称搜索器
     * @param $query
     * @param $value
     */
    public function searchNameAttr($query, $value)
    {
        if ($value) {
            $query->where('name', 'like', '%' . htmlentities($value) . '%');
        }
    }

    /**
     * 状态搜索器
     * @param $query
     * @param $value
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('status', '=', intval($value));
        }
    }

    /**
     * 关联管理员表
     * @return \think\model\relation\HasMany
     */
    public function admins()
    {
        return $this->hasMany(SocialActivityAdmin::class, 'role_id', 'id');
    }

    /**
     * 关联角色菜单表
     * @return \think\model\relation\HasMany
     */
    public function roleMenus()
    {
        return $this->hasMany(SocialActivityRoleMenu::class, 'role_id', 'id');
    }
}
