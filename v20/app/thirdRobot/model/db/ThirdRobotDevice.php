<?php
/**
 *机器人设备表
 */

namespace app\thirdRobot\model\db;

use app\thirdRobot\model\service\ThirdRobotDeviceService;
use think\Model;

class ThirdRobotDevice extends Model
{

    public function getSum($where, $field)
    {
        $data = $this->where($where)->sum($field);
        return $data;
    }

    public function getListData($where, $field = true, $orderby = 'id DESC', $page = 0, $page_size = 10)
    {
        $appid = cfg('third_robot_recognition_appid');
        if(isset($where['0']) && count($where['0'])==3 && !empty($appid)){
            $where[]=array('appid','=',$appid);
        }
        $data_sql = $this->field($field)->where($where)->order($orderby);
        if ($page > 0) {
            $data_sql->page($page, $page_size);
        }
        $data = $data_sql->select();
        return $data;
    }

    public function getCount($where = [])
    {
        $appid = cfg('third_robot_recognition_appid');
        if(isset($where['0']) && count($where['0'])==3 && !empty($appid)){
            $where[]=array('appid','=',$appid);
        }
        $result = $this->where($where)->count();
        return $result;
    }

    public function getOneData($where, $field = true, $orderby = 'id DESC')
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

    public function getRobotImgAttr($value,$data)
    {
        return (new ThirdRobotDeviceService())->getRobotImg($data['robot_type']);
    }
}