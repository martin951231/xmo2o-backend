<?php


namespace app\community\model\db;

use think\Model;
class WisdomQrcodeCateFieldCateConfig extends Model
{
    /**
     * Notes:
     * @param $where
     * @param bool $field
     * @param string $order
     * @return mixed
     * @author: weili
     * @datetime: 2020/11/3 15:55
     */
    public function getList($where,$field=true,$order='')
    {
        $list = $this->where($where)->field($field)->order($order)->select();
        return $list;
    }
}