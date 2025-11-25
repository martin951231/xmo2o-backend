<?php
/**
 * 智能充电桩
 * @author weili
 * @date 2020/8/27
 */

namespace app\community\model\db;

use think\Model;
class HouseSmartCharging extends Model
{
    /**
     * Notes: 获取数量
     * @param $where
     * @return int
     * @author: weili
     * @datetime: 2020/8/27 13:54
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }
}