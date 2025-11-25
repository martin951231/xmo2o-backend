<?php
class Employee_memory_bindModel extends Model
{
    /**
     * 绑定标签
     * @param $memory_arr
     * @param $uid
     * @param $employee_id
     * @param $reply_id
     * @return bool|mixed
     */
    public function bind_arr_info($memory_arr, $uid, $employee_id, $reply_id)
    {
        $bind_data = array(
            'uid' => $uid,
            'employee_id' => $employee_id,
            'reply_id' => $reply_id,
            'add_time' => time()
        );
        $m_employee_memory = M('Employee_memory');
        foreach ($memory_arr as $val) {
            if (!$val) continue;
            $bind_data['memory_id'] = $val;
            $bind_id = $this->data($bind_data)->add();
            if (!$bind_id) {
                return true;
                break;
            } else {
                $m_employee_memory->where(array('memory_id'=> $val))->setInc('num');
            }
        }
        return true;
    }

    /**
     * 获取评论所属标签
     * @param $reply_id
     * @return bool|mixed
     */
    public function reply_memory($reply_id) {
        $memory_info = $this->alias('a')
            ->field('a.*, b.memory_name')
            ->join('LEFT JOIN pigcms_employee_memory as b on a.memory_id=b.memory_id')
            ->where(array('a.reply_id' => $reply_id))->select();
        return $memory_info;
    }


    /**
     * 获取标签评论相关信息
     * @param $memory_id
     * @return bool|mixed
     */
    public function info_memory($memory_id) {
        $memory_info = $this->alias('a')
            ->field('a.*, b.memory_name, b.num, c.true_name, c.business_phone, d.nickname as login_name, d.phone as login_phone')
            ->join('LEFT JOIN pigcms_employee_memory as b on a.memory_id=b.memory_id')
            ->join('LEFT JOIN pigcms_employee_list as c on c.id=a.employee_id')
            ->join('LEFT JOIN pigcms_user as d on d.uid=c.uid')
            ->where(array('a.memory_id' => $memory_id))->select();
        return $memory_info;
    }

    /**
     * 获取从业者对应的标签和标签数量
     * @param $employee_id
     * @return bool|mixed
     */
    public function employee_memory_info($employee_id) {
        $memory_info = $this->alias('a')
            ->field('a.memory_id, a.employee_id, a.reply_id, a.uid, count(a.id) as num, b.memory_name, b.num as memory_num')
            ->join('LEFT JOIN pigcms_employee_memory as b on a.memory_id=b.memory_id')
            ->group('a.memory_id')
            ->where(array('a.employee_id' => $employee_id))->select();
        return $memory_info;
    }


}
?>