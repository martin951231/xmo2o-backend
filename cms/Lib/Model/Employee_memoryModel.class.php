<?php
class Employee_memoryModel extends Model
{
    /**
     * 查询标签信息
     * @param $memory_id
     * @return array|mixed
     */
    public function employee_memory($memory_id)
    {
        $where = array(
            'memory_id' => $memory_id,
            'status' => 0
        );
        $employee_memory_info = $this->where($where)->find();
        if ($employee_memory_info) {
            return $employee_memory_info;
        } else {
            return false;
        }
    }


    /**
     * 查询标签信息列表
     * @return array|mixed
     */
    public function employee_memory_list()
    {
        $where['status'] = 0;
        $employee_memory_info = $this->where($where)->order('sort desc,add_time desc')->select();
        if ($employee_memory_info) {
            return $employee_memory_info;
        } else {
            return array();
        }
    }

}
?>