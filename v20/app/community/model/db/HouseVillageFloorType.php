<?php
/**
 * 访问类型
 * @author weili
 * @date 2020/9/16
 */

namespace app\community\model\db;

use think\Model;
class HouseVillageFloorType extends Model
{
    /**
     * Notes: 获取某个字段值
     * @param $where
     * @param $field
     * @return array
     * @author: weili
     * @datetime: 2020/9/16 14:48
     */
    public function getValues($where,$field)
    {
        $data = $this->where($where)->value($field);
        return $data;
    }

    public function getList($where,$field = true,$order='id desc') {
        $list = $this->field($field)->where($where)->order($order)->select();
        return $list;
    }

    public function getColumn($where,$field = 'id',$key=''){
        return $this->where($where)->column($field,$key);
    }
    
}