<?php

namespace app\community\model\db;

use think\Model;


class HouseMaintenanWorker extends Model
{

    public function get_one($where, $field = true)
    {
        $info = $this->field($field)->where($where)->find();
        if ($info && !$info->isEmpty()) {
            $info = $info->toArray();
        } else {
            $info = array();
        }
        return $info;
    }

    public function getAll($where, $field = true)
    {
        $data = $this->where($where)->field($field)->order('id ASC')->select();
        if ($data && !$data->isEmpty()) {
            $data = $data->toArray();
        } else {
            $data = array();
        }
        return $data;
    }

    public function updateField($where, $data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }

    public function getWorkLists($where = [], $field = true, $page = 0, $limit = 20,$order = 'id ASC')
    {
        $sql = $this->field($field)->where($where)->order($order);
        if ($page) {
            $sql->page($page, $limit);
        }
        $result = $sql->select();
        if ($result && !$result->isEmpty()) {
            $result = $result->toArray();
        } else {
            $result = array();
        }
        return $result;
    }

    public function addeData($data)
    {
        $id = $this->insertGetId($data);
        return $id;
    }

    public function getColumn($where, $column, string $key = '')
    {
        $data = $this->where($where)->column($column, $key);
        return $data;
    }

    public function getMemberCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }


    /**
     * 获取小区通知预警人员
     */
    public function getWorkerList($where,$field,$order)
    {
        $prefix = config('database.connections.mysql.prefix');
        $data = $this->alias('hmw')
            ->join($prefix.'house_worker hw','hw.wid = hmw.wid')
            ->where($where)
            ->field($field)
            ->order($order)
            ->select()
            ->toArray();
        return $data;
    }
}
