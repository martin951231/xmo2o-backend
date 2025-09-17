<?php
	/*
	 * 派发优惠券，平台和商家
	 * */
class plan_order_export extends plan_base{
	public function runTask($param){
        $where=array();
		$where['export_id']= $param['export_id'];
		$res = M('Export_log')->where($where)->find();
		if(empty($res)){
            return true;
        }
		if($res['type']=='room_export' && !empty($res['file_name'])){
		    if($res['status']==1){
                return true;
            }
            if(file_exists('./runtime/'.$res['file_name'])){
                return true;
            }
        }
		//删除计划任务
		if(isset($param['plan_task_id']) && !empty($param['plan_task_id'])){
            M('Process_plan')->where(array('id'=>$param['plan_task_id']))->delete();
		}else{
			$plan_list = M('Process_plan')->where(array('file'=>'order_export'))->select();
			foreach($plan_list	 as $v){
				$tmp = unserialize($v['param']);
				if($tmp['export_id']==$res['export_id']){
					M('Process_plan')->where(array('id'=>$v['id']))->delete();
				}
			}
		}

		if(D('Order')->order_export_runtask($param)){
			M('Export_log')->where($where)->setField('status',1);
		}

		return true;
	}
}
?>