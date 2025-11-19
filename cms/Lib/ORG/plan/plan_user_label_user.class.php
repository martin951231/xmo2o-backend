<?php
/**
 * Created by PigCMS.
 * User: htm.
 * Date: 2019/7/15.
 * Time: 16:23.
 */

/**
 * Class plan_user_label_user 自动标签绑定用户->子计划任务 查询所有用户
 *
* */
class plan_user_label_user extends plan_base{  

    public function runTask($param = array()){
        if(!$param['label_id']){
            return true;
        }
        $now_time = time();
        $limit = 0;
        $user_list = M('User')->field('uid')->where(array('status'=>1))->order('uid DESC')->limit($limit,1000)->select();
        while ( $user_list ) {
            foreach ($user_list as $key => $_user) {
                $this->keepThread();
                $arr= array();
                $arr['param'] = serialize(array('label_id'=>$param['label_id'],'uid'=>$_user['uid']));
                $arr['plan_time'] = 0;
                $arr['space_time'] = 0;
                $arr['add_time'] = $now_time;
                $arr['file'] = 'user_label_get_label';
                $arr['time_type'] = 1;
                $arr['unique_id'] = 'user_label_get_label_'.$param['label_id'].$_user['uid'];
				$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                M('process_sub_plan')->data($arr)->add();
            }
            $limit += 1000;
            $user_list = M('User')->field('uid')->where(array('status'=>1))->order('uid DESC')->limit($limit,1000)->select();
        }
        return true;
    }
}
?>