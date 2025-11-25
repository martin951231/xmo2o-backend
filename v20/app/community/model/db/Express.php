<?php


namespace app\community\model\db;

use think\Model;
class Express extends Model
{
    /**
     * 获取快递公司详情
     * @author lijie
     * @date_time 2020/08/17 17:18
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getOne($where,$field=true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }

    /**
     * 快递公司列表
     * @author lijie
     * @date_time 2020/08/19 14:16
     * @param $where
     * @param $field
     * @return mixed
     */
    public function getLists($where,$field)
    {
        $data = $this->where($where)->field($field)->select();
        return $data;
    }
}