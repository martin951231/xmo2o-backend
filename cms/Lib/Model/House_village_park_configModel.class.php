<?php


class House_village_park_configModel extends Model
{
    protected $tableName = 'house_village_park_config';

    /**
     * 获取小区停车场配置
     * @author lijie
     * @date_time 2020/08/06 9:53
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getConfig($where,$field=true)
    {
        $res = $this->field($field)->where($where)->find();
        return $res;
    }
}
