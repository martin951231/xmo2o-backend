<?php
/**
 * 餐饮订单 model
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/6/1 16:45
 */

namespace app\common\model\db;

use think\Model;

class FoodshopOrder extends Model
{
    /**
     * 获取餐饮订单列表
     * User: chenxiang
     * Date: 2020/6/1 16:54
     * @param array $where
     * @param bool $field
     * @return \think\Collection
     */
    public function getOrderList($where = [], $field = true, $group = '', $join = '')
    {
        if(empty($group) && empty($join)) {
            $result = $this->field($field)->where($where)->select();
        } elseif(!empty($join)) {
            $result = $this->field($field)->join($join)->where($where)->group($group)->select();
        }

        return $result;
    }

    /**
     * 更新某个字段信息
     * User: chenxiang
     * Date: 2020/6/1 17:11
     * @param array $where
     * @param string $field
     * @param string $value
     * @return FoodshopOrder
     */
    public function setField($where = [], $field = '', $value = '') {
        $result = $this->where($where)->update([$field => $value]);
        return $result;
    }
    
}