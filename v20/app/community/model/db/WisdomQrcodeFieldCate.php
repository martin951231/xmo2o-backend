<?php


namespace app\community\model\db;

use think\Model;
class WisdomQrcodeFieldCate extends Model
{
    /**
     * Notes: 获取数据列表
     * @param $where
     * @param bool $field
     * @param string $order
     * @return mixed
     * @author: weili
     * @datetime: 2020/11/3 15:51
     */
    public function getList($where,$field=true,$order='id desc')
    {
        $list = $this->where($where)->field($field)->order($order)->select();
        return $list;
    }
}