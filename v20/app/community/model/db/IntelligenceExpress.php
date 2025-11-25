<?php
/**
 * 智能快递柜
 * @author weili
 * @datetime 2020/8/3
 */

namespace app\community\model\db;
use think\Model;

class IntelligenceExpress extends Model
{
    /**
     * Notes: 获取数量
     * @param $where
     * @return int
     * @author: weili
     * @datetime: 2020/8/3 16:29
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * @author: wanziyang
     * @date_time: 2020/5/14 13:59
     * @param array $where 查询条件
     * @param int $page 分页
     * @param string $field 需要查询的字段 默认查询所有
     * @param string $order 排序
     * @param integer $page_size 排序
     * @return array|null|Model
     */
    public function getList($where,$field =true) {

        $list = $this->where($where)->field($field)->select();
        return $list;
    }
}