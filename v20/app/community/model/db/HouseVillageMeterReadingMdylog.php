<?php
/**
 * 抄表相关
 * @author weili
 * @date 2020/10/19
 */

namespace app\community\model\db;

use think\Model;

class HouseVillageMeterReadingMdylog extends Model
{

    public function getLists($where = [], $field = true, $order = 'id DESC')
    {
        $dataObj = $this->where($where)->field($field)->order($order)->select();
        $data = array();
        if ($dataObj && !$dataObj->isEmpty()) {
            $data = $dataObj->toArray();
        }
        return $data;
    }

    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count > 0 ? $count : 0;
    }

    public function getOne($where = [], $field = true, $order = 'id DESC')
    {
        $dataObj = $this->where($where)->field($field)->order($order)->find();
        $data = array();
        if ($dataObj && !$dataObj->isEmpty()) {
            $data = $dataObj->toArray();
        }
        return $data;
    }


    public function addOne($data = array())
    {
        if (empty($data)) {
            return 0;
        }
        $id = $this->insertGetId($data);
        return $id;
    }

    public function updateOneData($whereArr, $updateData = array())
    {
        if (empty($whereArr) || empty($updateData)) {
            return false;
        }
        $ret = $this->where($whereArr)->update($updateData);
        return $ret;
    }
}