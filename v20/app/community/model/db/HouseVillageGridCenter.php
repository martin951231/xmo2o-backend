<?php


namespace app\community\model\db;

use think\Model;

class HouseVillageGridCenter extends Model
{
    /**
     * 增加中心点
     * @author lijie
     * @date_time 2020/12/22
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insert($data);
        return $res;
    }

    /**
     * 删除中心点
     * @author lijie
     * @date_time 2020/12/22
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function delOne($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }

    /**
     * 查找中心点
     * @author lijie
     * @date_time 2020/12/22
     * @param $where
     * @param bool $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where,$field=true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }

    /**
     * 修改中心点
     * @author lijie
     * @date_time 2021/02/20
     * @param $where
     * @param $data
     * @return bool
     */
    public function saveOne($where,$data)
    {
        $data = $this->where($where)->save($data);
        return $data;
    }
}