<?php
/**
 *导出数据表
 */

namespace app\community\model\db;
use think\Model;

class ExportLog extends Model
{

    public function getSum($where, $field)
    {
        $data = $this->where($where)->sum($field);
        return $data;
    }

    public function getListData($where, $field = true, $orderby = 'export_id DESC', $page = 1, $page_size = 10)
    {
        $data_sql = $this->field($field)->where($where)->order($orderby);
        if ($page > 0) {
            $data_sql->page($page, $page_size);
        }
        $data = $data_sql->select();
        return $data;
    }

    public function getCount($where = [])
    {
        $result = $this->where($where)->count();
        return $result;
    }

    public function getOneData($where, $field = true, $orderby = 'export_id DESC')
    {
        $data = $this->field($field)->where($where)->order($orderby)->find();
        return $data;
    }

    public function saveOneData($where, $data)
    {
        $res = $this->where($where)->save($data);
        return $res;
    }

    public function updateOneData($where, $data)
    {
        $result = $this->where($where)->update($data);
        return $result;
    }

    public function addOneData($data)
    {
        $result = $this->insertGetId($data);
        return $result;
    }
}