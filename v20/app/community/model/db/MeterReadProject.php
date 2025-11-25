<?php


namespace app\community\model\db;

use think\Model;

class MeterReadProject extends Model
{
    /**
     * 抄表项目列表
     * @author lijie
     * @date_time 2020/11/02
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getList($where,$field=true)
    {
        $data = $this->where($where)->field($field)->select();
        return $data;
    }

    /**
     * 抄表项目
     * @author lijie
     * @date_time 2020/11/02
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getOne($where,$field=true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }
    
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }
}