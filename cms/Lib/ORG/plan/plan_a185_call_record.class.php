<?php
/*
 *a185 呼叫监听
 * */
class plan_a185_call_record extends plan_base{
    public function runTask(){

        fdump('a185呼叫监听>>'.__LINE__,'api/log/'.date('Ymd').'/a185/plan_a185_call_record_log',true);
        //删除计划任务
//        if($param['plan_task_id']){
//            $this->dropPlan($param['plan_task_id']);
//        }
        $res = D('Face_a185_call_record')->a185_closes();
        return true;
    }
}
?>