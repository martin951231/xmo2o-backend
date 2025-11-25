<?php
/**
 * 店铺订单
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/6/1 11:37
 */

namespace app\common\model\db;

use think\Model;

class StoreOrder extends Model
{
    /**
     * 获取店铺订单信息
     * User: chenxiang
     * Date: 2020/6/1 16:09
     * @param bool $field
     * @param array $where
     * @param string $group
     * @return mixed
     */
    public function getList($field = true, $where = [], $group = '') {
        $result = $this->field($field)->where($where)->group($group)->select();
        return $result;
    }

    /**
     * 更新某个字段信息
     * User: chenxiang
     * Date: 2020/6/1 16:21
     * @param array $where
     * @param string $field
     * @param string $value
     * @return mixed
     */
    public function setField($where = [], $field = '', $value = '') {
        $result = $this->where($where)->update([$field => $value]);
        return $result;
    }
}