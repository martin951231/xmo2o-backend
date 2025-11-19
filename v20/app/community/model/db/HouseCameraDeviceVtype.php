<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/11/12 19:24
 */

namespace app\community\model\db;

use think\Model;

class HouseCameraDeviceVtype extends Model
{
    public function getOne($where, $field = true)
    {
        $infoObj = $this->field($field)->where($where)->find();
        $info = [];
        if (!empty($infoObj) && !$infoObj->isEmpty()) {
            $info = $infoObj->toArray();
        }
        return $info;
    }

    public function addOneData($addData = array())
    {
        if (empty($addData)) {
            return false;
        }
        $idd = $this->insertGetId($addData);
        return $idd;
    }

    //更新数据
    public function updateOneData($where = array(), $updateData = array())
    {
        if (empty($where) || empty($updateData)) {
            return false;
        }
        $ret = $this->where($where)->update($updateData);
        //echo $this->getLastSql();
        return $ret;
    }

    /**
     * 获取视频类型明细列表
     * @return array
     */
    public function getDataLists($where, $field = true, $order = 'id desc', $page = 0, $limit = 20)
    {
        if (empty($where)) {
            return [];
        }
        if ($page > 0) {
            $data = $this->field($field)->where($where)
                ->order($order)
                ->where($where)
                ->page($page, $limit)
                ->select();
        } else {
            $data = $this->field($field)->where($where)
                ->order($order)
                ->where($where)
                ->select();
        }

        if ($data && !$data->isEmpty()) {
            $list = $data->toArray();
            return $list;
        } else {
            return [];
        }
    }

    public function getCount($where = [])
    {
        $count = $this->where($where)->count();
        $count = $count > 0 ? $count : 0;
        return $count;
    }


    public function getColumn($where, $column, $key = '')
    {
        $data = $this->where($where)->column($column,$key);
        return $data;
    }
}