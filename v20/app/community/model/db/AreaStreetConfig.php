<?php
/**
 * 街道配置
 */

namespace app\community\model\db;

use think\Model;
class AreaStreetConfig extends Model
{
    /**
     * Notes: 修改数据
     * @param $where
     * @param $data
     * @return AreaStreetConfig
     * @author: weili
     * @datetime: 2020/9/21 13:39
     */
    public function saveData($where,$data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }

    /**
     * Notes: 添加数据
     * @param $data
     * @return int|string
     * @author: weili
     * @datetime: 2020/9/21 13:40
     */
    public function addData($data){
        $res = $this->insert($data);
        return $res;
    }

    /**
     * Notes: 获取一条数据
     * @param $where
     * @param bool $field
     * @return array|Model|null
     * @author: weili
     * @datetime: 2020/9/21 13:43
     */
    public function getFind($where,$field=true)
    {
        $info = $this->where($where)->field($field)->find();
        return $info;
    }

    public function getOne($where,$field=true)
    {
        $data = $this->alias('a')
            ->leftJoin('Area_street_party_build b','a.area_id = b.area_id')
            ->where($where)
            ->field($field)
            ->find();
        return $data;
    }
}