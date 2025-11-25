<?php
/**
 * Created by PigCMS.
 * User: htm.
 * Date: 2019/7/15.
 * Time: 16:23.
 */

/**
 * Class plan_store_time 自动标签绑定用户->主计划任务
 *
* */
class plan_user_label extends plan_base{  

    public function runTask(){
        // 查询所有自动标签
        $label_list = M('User_label')->where(array('is_del'=>0,'type'=>1))->select();
        if (!$label_list) {
            return true;
        }
        $now_time = time();
        foreach($label_list as $key =>$val){
            $this->keepThread();
            $arr= array();
            $arr['param'] = serialize(array('label_id'=>$val['id']));
            $arr['plan_time'] = 0;
            $arr['space_time'] = 0;
            $arr['add_time'] = $now_time;
            $arr['file'] = 'user_label_user';
            $arr['time_type'] = 1;
            $arr['unique_id'] = 'user_label_user'.$val['id'];
			$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
            M('process_sub_plan')->data($arr)->add();
        }
        return true;
    }
}
?>