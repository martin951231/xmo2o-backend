<?php
/**
 *机器人任务表
 */

namespace app\thirdRobot\model\db;
use app\thirdRobot\model\service\ThirdRobotDeliveryOrderService;
use think\Model;

class ThirdRobotHistoryTaskDetail extends Model
{
    const STATUS_7 = 7;//'0未开始1抵达中2异常挂起3异常结束4成功完成5取消结束6暂停中7已抵达'
    
    public function getSum($where, $field)
    {
        $data = $this->where($where)->sum($field);
        return $data;
    }

    public function getListData($where, $field = true, $orderby = 'id DESC', $page = 1, $page_size = 10)
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

    public function getOneData($where, $field = true, $orderby = 'id DESC')
    {
        $data = $this->field($field)->where($where)->order($orderby)->find();
        return $data;
    }

    public function saveOneData($where, $data)
    {
        $res = $this->where($where)->save($data);
        
        $task = $this->where($where)->find();
        if(!empty($task)){
            app(ThirdRobotDeliveryOrderService::class)->orderRobotEndLocation($task->task_instance_id, $data);
        }

        return $res;
    }

    public function updateOneData($where, $data)
    {
        $result = $this->where($where)->update($data);
        
        $task = $this->where($where)->find();
        if(!empty($task)){
            app(ThirdRobotDeliveryOrderService::class)->orderRobotEndLocation($task->task_instance_id, $data);
        }

        return $result;
    }

    public function addOneData($data)
    {
        $result = $this->insertGetId($data);
        return $result;
    }
}