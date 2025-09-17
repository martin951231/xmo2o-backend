<?php
/**
 * 快店 订单 Model
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/6/1 11:48
 */

namespace app\common\model\db;

use think\Model;

class ShopOrderLog extends Model
{
    /**
     * 快店订单列表信息
     * User: chenxiang
     * Date: 2020/6/1 11:52
     * @param string $field
     * @param array $where
     * @param string $group
     * @return mixed
     */
    public function getList($field = '', $where = [], $group = '') {
        $result = $this->field($field)->where($where)->group($group)->select();
        return $result;
    }

    /**
     * 获取快店订单信息
     * User: chenxiang
     * Date: 2020/6/1 19:01
     * @param array $where
     * @param bool $field
     * @return array|Model|null
     */
    public function getOne($where = [], $field = true) {
        $result = $this->field($field)->where($where)->find();
        return $result;
    }
}