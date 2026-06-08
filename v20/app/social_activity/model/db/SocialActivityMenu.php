<?php

namespace app\social_activity\model\db;

use think\Model;

class SocialActivityMenu extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 根据ID获取菜单信息
     * @param $id
     * @return array|bool|Model|null
     */
    public function getMenuById($id)
    {
        if (empty($id)) {
            return false;
        }
        $where = ["id" => intval($id)];
        $result = $this->where($where)->find();
        return $result;
    }

    /**
     * 类型获取器
     * @param $value
     * @return string
     */
    public function getTypeTextAttr($value, $data)
    {
        $typeMap = [
            1 => '目录',
            2 => '菜单',
            3 => '按钮',
        ];
        return $typeMap[$data['type']] ?? '未知';
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
     * 标题搜索器
     * @param $query
     * @param $value
     */
    public function searchTitleAttr($query, $value)
    {
        if ($value) {
            $query->where('title', 'like', '%' . htmlentities($value) . '%');
        }
    }

    /**
     * 类型搜索器
     * @param $query
     * @param $value
     */
    public function searchTypeAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('type', '=', intval($value));
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
     * 关联父级菜单
     * @return \think\model\relation\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(SocialActivityMenu::class, 'pid', 'id');
    }

    /**
     * 关联子菜单
     * @return \think\model\relation\HasMany
     */
    public function children()
    {
        return $this->hasMany(SocialActivityMenu::class, 'pid', 'id');
    }

    /**
     * 关联角色菜单表
     * @return \think\model\relation\HasMany
     */
    public function roleMenus()
    {
        return $this->hasMany(SocialActivityRoleMenu::class, 'menu_id', 'id');
    }
}
