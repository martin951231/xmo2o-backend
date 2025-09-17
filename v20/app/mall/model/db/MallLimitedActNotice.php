<?php


namespace app\mall\model\db;

use think\Model;
class MallLimitedActNotice extends Model
{
    /**
     * 添加数据 获取插入的数据id
     * User: mrdeng
     * Date: 2020/10/13 16:38
     * @param $data
     * @return int|string
     */
    public function add($data) {
        return $this->insertGetId($data);
    }

    /**
     * @param $act_id
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author mrdeng
     */
    public function getNoticeStatus($where,$field){
        $arr= $this->where($where)->value($field);
        return $arr;
    }

    public function getSome($where, $field)
    {
        return $this->field($field)->where($where)->select()->toArray();
    }

    public function updateOne($where, $data)
    {
        return $this->where($where)->update($data);
    }
}