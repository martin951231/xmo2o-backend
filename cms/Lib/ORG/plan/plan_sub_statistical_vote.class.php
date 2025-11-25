<?php
	/*
	 *投票统计计划
	 * */
class plan_sub_statistical_vote extends plan_base{
	public function runTask($param){
		//删除计划任务
		if($param['plan_task_id']){
			$this->dropPlan($param['plan_task_id']);
		}
        $vote_id = $param['vote_id'];
		if ($vote_id>0) {
            include(WEB_PATH . 'plugin/vote/Lib/Model/Plugin_voteModel.class.php');
            $msg = new Plugin_voteModel();
            $info = $msg->statistical_voting($vote_id);
        } else {
            fdump('==========vote_set============','api/log/'.date('Ymd').'/plan_sub_statistical_vote_log',true);
            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/plan_sub_statistical_vote_log',true);
            fdump($param,'api/log/'.date('Ymd').'/plan_sub_statistical_vote_log',true);
        }
		return true;
	}
}
?>