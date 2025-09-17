<?php


namespace app\community\model\db;

use think\Model;

class VillageQywxMessageDetail extends Model
{
    /**
     * 群发消息内容
     * @author lijie
     * @date_time 2021/03/18
     * @param $where
     * @param bool $field
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where,$field=true)
    {
        $data = $this->where($where)->field($field)->order('id ASC')->select();
        return $data;
    }

    /**
     * 群发消息内容
     * @author lijie
     * @date_time 2021/03/23
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
     * 添加发送消息内容
     * @author lijie
     * @date_time 2021/03/22
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }
}