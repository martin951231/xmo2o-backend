<?php
/**
 * 人脸下发人脸设备日志
 */

namespace app\community\model\db;

use think\Model;
class HouseFaceSyncLog extends Model
{
    public $status = [
        0=>'同步中',
        1=>'成功',
        2=>'失败',
    ];
    public function getStatusStr($status)
    {
        return $this->status[$status] ?? '';
    }
    public function getListAndDeviceInfo($where,$field,$order,$pageSize)
    {
        $list = $this->alias('log')
            ->join('house_face_device d','d.device_id = log.device_id')
            ->where($where)
            ->field($field)
            ->order($order);
        if($pageSize){
            $list = $list->paginate($pageSize)->toArray();
        }else{
            $list = $list->select();
            $list = $list ? $list->toArray() : [];
        }
        return $list;
    }

    public function getOne($where = [], $field = true, $order = [])
    {
        $result = $this->field($field)->where($where)->order($order)->find();
        return $result;
    }
    
    public function add($data){
        $result = $this->insertGetId($data);
        return $result;
    }

    public function updateThis($where, $data){
        $result = $this->where($where)->update($data);
        return $result;
    }

    public function getList($where = [], $field = true, $order = true, $page = 0, $listRows = 0)
    {
        $sql = $this->field($field)->where($where)->order($order);
        if ($listRows) {
            $sql->page($page, $listRows);
        }
        $result = $sql->select();
        return $result;
    }
    
    public function getColumn($where,$column, $key = '')
    {
        $data = $this->where($where)->column($column, $key);
        return $data;
    }
}