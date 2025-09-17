<?php


namespace app\community\model\db;

use think\Model;

class HouseNewSelectProjectLog extends Model
{
    /**
     * 获取最后一条操作
     * @author lijie
     * @date_time 2021/07/31
     * @param array $where
     * @param bool $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where=[],$field=true)
    {
        $data = $this->where($where)->field($field)->order('log_id DESC')->find();
        return $data;
    }

    /**
     * 添加记录
     * @author lijie
     * @date_time 2021/07/31
     * @param array $data
     * @return int|string
     */
    public function addOne($data=[])
    {
        $res = $this->insert($data);
        return $res;
    }

}