<?php
/**
 * 用户签到 Model
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/5/29 14:16
 */

namespace app\common\model\db;

use think\Model;

class UserSign extends Model
{
    /**
     * 获取用户的签到信息
     * User: chenxiang
     * Date: 2020/5/29 14:19
     * @param array $where
     * @param bool $field
     * @param string $order
     * @return array|mixed|Model|null
     */
    public function getUserSignData($where = [], $field = true, $order = '')
    {
        $result = $this->field($field)->where($where)->order($order)->find();
        return $result;
    }

    /**
     * 添加用户签到记录
     * User: chenxiang
     * Date: 2020/5/29 14:47
     * @param array $data
     * @return int|string
     */
    public function addUserSign($data = []) {
        $result = $this->insert($data);
        return $result;
    }

    /**
     * 统计个数
     * User: chenxiang
     * Date: 2020/6/1 10:58
     * @param array $where
     * @return int
     */
    public function countNum($where = []) {
        $result = $this->where($where)->count();
        return $result;
    }
}