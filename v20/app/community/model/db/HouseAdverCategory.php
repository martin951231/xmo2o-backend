<?php
/**
 * 广告模块表
 * @author weili
 * @date 2020/9/7
 */

namespace app\community\model\db;

use think\Model;
class HouseAdverCategory extends Model
{
    /**
     * Notes: 获取一条
     * @param array $where
     * @param bool $field
     * @param string $order
     * @return mixed
     * @author: weili
     * @datetime: 2020/9/7 17:20
     */
    public function getFind($where=[],$field=true,$order='cat_id desc')
    {
        $info = $this->where($where)->field($field)->order($order)->find();
        return $info;
    }
}