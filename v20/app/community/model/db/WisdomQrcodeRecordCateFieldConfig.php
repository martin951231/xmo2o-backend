<?php


namespace app\community\model\db;

use think\Model;
class WisdomQrcodeRecordCateFieldConfig extends  Model
{
    /**
     * Notes: 获取数据列表
     * @param $where
     * @param bool $field
     * @param string $order
     * @return \think\Collection
     * @author: weili
     * @datetime: 2020/11/3 18:00
     */
    public function getList($where,$field=true,$order='acid desc')
    {
        $list = $this->where($where)->field($field)->order($order)->select();
        return $list;
    }
}