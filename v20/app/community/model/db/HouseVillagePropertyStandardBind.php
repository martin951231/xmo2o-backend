<?php


namespace app\community\model\db;

use think\Model;

class HouseVillagePropertyStandardBind extends Model
{
    /**
     * 获取房间绑定的物业费标准
     * @author lijie
     * @date_time 2020/11/09
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
}