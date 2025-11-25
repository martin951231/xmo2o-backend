<?php

/**
 * This file is part of Kuaijing Bailing.
 *
 * @link     https://www.kuaijingai.com
 * @document https://help.kuaijingai.com
 * @contact  www.kuaijingai.com 7*12 9:00-21:00
 */
class RePairPlanModel extends Model
{
    public function repairSubPlan() {
        set_time_limit(0);
        $now_time = time();
        $where = [];
        $where['plan_time'] = array('elt',$now_time);
        $taskList = M('Process_sub_plan')->where($where)->order('plan_time asc,space_time asc,id asc')->limit(30)->select();

        foreach ($taskList as $k => $task) {
            $executeResult=1;
            if(empty($task['url'])){
                $eventClassName = 'plan_'.$task['file'];
                if(!class_exists($eventClassName,false)){
                    if(file_exists(LIB_PATH . 'ORG/plan/' . $eventClassName . '.class.php')){
                        import('@.ORG.plan.' . $eventClassName);
                        if(!class_exists($eventClassName,false)){
                            $executeResult=0;
                        }
                    }else{
                        $executeResult=0;
                    }
                }
                if($executeResult){
                    //初始化对应的类文件
                    $eventClass=new $eventClassName($this->plan_process_file);
                    if(method_exists($eventClass, 'runtask')){
                        if(empty($task['param'])){
                            if($eventClass->runTask($task['param'],$task['plan_time'])!==true){
                                $executeResult=0;
                            }
                        }else{
                            $param=unserialize($task['param']);
                            $param['plan_task_id']=$task['id'];
                            if($eventClass->runTask($param,$task['plan_time'])!==true){
                                $executeResult=0;
                            }
                        }
                    }
                    unset($eventClass);
                }
                if ($executeResult == 1 || $task['error_count'] == 7) {
                    //定时计划任务
                    if ($task['space_time']) {
                        if ($task['time_type']) {
                            $plan_time = time() + $task['space_time'];
                        } else {
                            $plan_time = $task['plan_time'] + $task['space_time'];
                        }
                        M('Process_sub_plan')->where(array('id' => $task['id']))->save(array('error_count' => 0, 'plan_time' => $plan_time));
                    } else {
                        M('Process_sub_plan')->where(array('id' => $task['id']))->delete();
                    }
                }
            }
        }
        return true;
    }
}