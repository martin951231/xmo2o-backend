<?php


namespace app\community\model\db;

use think\Model;

class MeterReadPerson extends Model
{
    /**
     *获取列表
     * @author lijie
     * @date_time 2020/11/02
     * @param $where
     * @param $field
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLists($where,$field=true)
    {
        $data = $this->where($where)->field($field)->select();
        return $data;
    }

    /**
     * 获取单个数据
     * @author lijie
     * @date_time 2020/11/02
     * @param $where
     * @param $wheres
     * @param bool $field
     * @param string $order
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where=array(),$wheres=array(),$field=true,$order='id DESC')
    {
        $data = $this->where($where)->where($wheres)->field($field)->order($order)->find();
        return $data;
    }
}