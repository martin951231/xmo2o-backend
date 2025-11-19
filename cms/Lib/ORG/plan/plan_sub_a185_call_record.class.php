<?php
/*
 *a185 呼叫监听
 * */
class plan_sub_a185_call_record extends plan_base{
    public function runTask($param){
        fdump(['a185呼叫监听>>'.__LINE__,$param],'api/log/'.date('Ymd').'/a185/plan_a185_call_record_log',true);
        //删除计划任务
        if($param['plan_task_id']){
            $this->dropPlan($param['plan_task_id']);
        }
		//检测A185参数不存在，则不触发
		if(!C('config.key_facedoor_a185') || !C('config.sdkappid_facedoor_a185') || !C('config.secretid_facedoor_a185') || !C('config.secretkey_facedoor_a185')){
			return true;
		}
        D('Face_a185_call_record')->a185_closes($param['call_room_id']);
        return true;
    }
}
?>