<?php
	/*
	 *百度人脸计划任务
	 * */
class plan_sub_baidu_ai_face extends plan_base{
	public function runTask($param){
        fdump_api([__LINE__,'baidu_ai_api_key'=>C('config.baidu_ai_api_key'),'baidu_ai_secret_key'=>C('config.baidu_ai_secret_key'),'param'=>$param],'plan_sub/plan_sub_baidu_ai_face',1);
	    if (!empty(C('config.baidu_ai_api_key')) && !empty(C('config.baidu_ai_secret_key'))) {
            //删除计划任务
            if($param['plan_task_id']){
                $this->dropPlan($param['plan_task_id']);
            }
            
            $res = D('House_face_img')->face_runtask($param);
            fdump_api([__LINE__,'baidu_ai_api_key'=>C('config.baidu_ai_api_key'),'baidu_ai_secret_key'=>C('config.baidu_ai_secret_key'),'res'=>$res],'plan_sub/plan_sub_baidu_ai_face',1);
        }
		return true;
	}
}
?>