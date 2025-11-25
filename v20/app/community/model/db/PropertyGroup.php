<?php


namespace app\community\model\db;

use think\Model;

class PropertyGroup extends Model
{
    /**
     * 获取组织架构
     * @author lijie
     * @date_time 2021/03/17
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where,$field=true,$page=1,$limit=10,$order='id DESC')
    {
        if ($page) {
            $data = $this->where($where)->field($field)->page($page, $limit)->order($order)->select();
        } else {
            $data = $this->where($where)->field($field)->order($order)->select();
        }
        return $data;
    }


    public function getOne($where = [], $field = true, $order = []) {
        $result = $this->field($field)->where($where)->order($order)->find();
        return $result;
    }

    public function getColumn($where,$column,$key = '')
    {
        $data = $this->where($where)->column($column, $key);
        return $data;
    }

    public function addFind($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }

    public function editFind($where,$data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }
}