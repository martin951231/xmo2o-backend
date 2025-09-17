<?php


namespace app\community\model\db;

use think\Model;
class WisdomQrcodeRecordCate extends  Model
{
    /**
     * Notes: 获取数据列表
     * @param $where
     * @param $field
     * @param $order
     * @return \think\Collection
     * @author: weili
     * @datetime: 2020/11/3 17:54
     */
    public function getList($where,$field=true,$order='id desc')
    {
        $list = $this->where($where)->field($field)->order($order)->select();
        return $list;
    }
}