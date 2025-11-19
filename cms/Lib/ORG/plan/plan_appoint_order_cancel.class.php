<?php

class plan_appoint_order_cancel extends plan_base{
	public function runTask(){
		$where['paid'] = 0;
		$where['service_status'] = 0;
		$where['is_del'] = 0;
		$where['_string'] = 'order_time<'.(time()-C('config.appoint_cancel_over_time')*60);
		$order_list  = M('Appoint_order')->field('order_id')->where($where)->select();

		if(!empty($order_list)){
			foreach ($order_list as $v){
				$this->keepThread();
				$data['is_del'] = 2;
                $data['del_time'] = time();
				$set = M('Appoint_order')->where(array('order_id'=>$v['order_id']))->save($data);
				if ($set) {
                    /*-----记录系统订单信息 订单取消成功 改写状态已取消 2020/8/7----*/
                    D("System_order")->save_order_status('appoint',$v['order_id'],5);
                }
			}
		}
		return true;
	}

}
?>