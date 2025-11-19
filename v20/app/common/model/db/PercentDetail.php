<?php
/**
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/6/15 13:17
 */

namespace app\common\model\db;


use think\Model;
class PercentDetail extends Model
{
    /**
     * 获取列表信息
     * @author: wanziyang
     * @date_time: 2020/6/15 13:18
     * @param array $where
     * @param bool|string $field
     * @param string $order
     * @return \think\Collection
     */
    public function getList($where,$field =true,$order='id ASC') {
        $list = $this->field($field)->where($where)->order($order)->select();
        return $list;
    }
}