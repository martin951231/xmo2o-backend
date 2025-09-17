<?php
/**
 * 用户绑定人脸设备表
 */
namespace app\community\model\db;

use think\Model;
use think\Db;
class HouseFaceBindDevice extends Model{

    public function getDeviceList($where,$field)
    {
        $list = $this->alias('fbd')
            ->join('house_face_device fd','fd.device_id = fbd.device_id')
            ->where($where)
            ->field($field)
            ->select();
        $list = $list ? $list->toArray() : [];
        return $list;
    }
}