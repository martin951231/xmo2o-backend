<?php
/*拼团超时付款
 * */
class plan_village_group_order_outtime extends plan_base
{
	public function runTask(){
		//超时把状态改为超时未支付
		$now_time = time();
		$where['status'] = 0;
		$where['add_time'] = array('lt',$now_time-1800);
		M('Village_group_order')->where($where)->setField('status',10);


		$condition['_string'] = "end_time > 0 AND end_time+86400 <{$now_time} AND status <> 4 AND status <> 3";

		M('Village_group_goods')->where($condition)->setField('status',3);

		$condition2['_string'] = "get_time > 0 AND get_time+86400 <{$now_time} AND status <> 4 AND status <> 5";

		M('Village_group_goods')->where($condition2)->setField('status',5);

		return true;
	}


}
?>