<?php

namespace app\social_activity\model\db;

use think\Model;

class SocialActivityAdmin extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 根据账号获取管理员信息
     * @param $account
     * @return array|bool|Model|null
     */
    public function getAdminByAccount($account)
    {
        if (empty($account)) {
            return false;
        }
        $where = ["account" => trim($account)];
        $result = $this->where($where)->find();
        return $result;
    }

    /**
     * 根据ID获取管理员信息
     * @param $id
     * @return array|bool|Model|null
     */
    public function getAdminById($id)
    {
        if (empty($id)) {
            return false;
        }
        $where = ["id" => intval($id)];
        $result = $this->where($where)->find();
        return $result;
    }

    /**
     * 密码修改器
     * @param $value
     * @return string
     */
    public function setPasswordAttr($value)
    {
        return md5($value);
    }

    /**
     * 验证密码
     * @param $password
     * @param $hashedPassword
     * @return bool
     */
    public function verifyPassword($password, $hashedPassword)
    {
        return md5($password) === $hashedPassword;
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
     * 最后登录时间获取器 - 格式化
     * @param $value
     * @return string
     */
    public function getLastLoginTimeTextAttr($value, $data)
    {
        if (empty($data['last_login_time'])) {
            return '从未登录';
        }
        return date('Y-m-d H:i:s', $data['last_login_time']);
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
     * 账号搜索器
     * @param $query
     * @param $value
     */
    public function searchAccountAttr($query, $value)
    {
        if ($value) {
            $query->where('account', 'like', '%' . htmlentities($value) . '%');
        }
    }

    /**
     * 姓名搜索器
     * @param $query
     * @param $value
     */
    public function searchRealnameAttr($query, $value)
    {
        if ($value) {
            $query->where('realname', 'like', '%' . htmlentities($value) . '%');
        }
    }

    /**
     * 电话搜索器
     * @param $query
     * @param $value
     */
    public function searchPhoneAttr($query, $value)
    {
        if ($value) {
            $query->where('phone', 'like', '%' . htmlentities($value) . '%');
        }
    }

    /**
     * 角色搜索器
     * @param $query
     * @param $value
     */
    public function searchRoleIdAttr($query, $value)
    {
        if ($value) {
            $query->where('role_id', '=', intval($value));
        }
    }

    /**
     * 根据手机号获取管理员信息
     * @param $phone
     * @return array|bool|Model|null
     */
    public function getAdminByPhone($phone)
    {
        if (empty($phone)) {
            return false;
        }
        $where = ["phone" => trim($phone)];
        $result = $this->where($where)->find();
        return $result;
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
     * 关联角色表
     * @return \think\model\relation\BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(SocialActivityRole::class, 'role_id', 'id');
    }
}
