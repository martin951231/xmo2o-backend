<?php
/**
 * 润雅定制 对接商盈通
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/5/28 16:47
 */

namespace app\common\model\db;

use think\Model;

class UserShangyingtong extends Model
{
    /**
     * 获取商盈通用户信息
     * User: chenxiang
     * Date: 2020/5/28 16:53
     * @param string $field
     * @param array $where
     * @param string $order
     * @return array|Model|null
     */
    public function getUserData($field = '', $where = [], $order = '')
    {
        $result = $this->field($field)->where($where)->order($order)->find();
        return $result;
    }
}