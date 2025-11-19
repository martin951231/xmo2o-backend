<?php
class plan_group_order_rollback extends plan_base{
	public function runTask(){
		//20分钟前的订单回滚
		$nowtime = time() - 1200;
		$orders = M('Group_order')->field('group_id, order_id, num, specifications_id')->where("`stock_reduce_method`=1 AND `paid`=0 AND `status`=0 AND `add_time`<'{$nowtime}'")->order('order_id ASC')->limit('0, 30')->select();
		$groups = array();
        $specifications_info = array();
        $m_group_specifications = M('Group_specifications');
		foreach ($orders as $order){
			$this->keepThread();
//			if (isset($groups[$order['group_id']])) {
//				$groups[$order['group_id']] += $order['num'];
//			} else {
//				$groups[$order['group_id']] = $order['num'];
//			}
            if ($order['specifications_id'] && isset($specifications_info[$order['specifications_id']])) {
                // 如果是团购规格 且已存在回滚信息 累加
                $specifications_info[$order['specifications_id']] += $order['num'];
            } elseif($order['specifications_id']) {
                // 如果是团购规格 查询赋值
                $specifications_info[$order['specifications_id']] = $order['num'];
            } elseif (isset($groups[$order['group_id']])) {
                $groups[$order['group_id']] += $order['num'];
            } else {
                $groups[$order['group_id']] = $order['num'];
            }
//			M('Group_order')->where(array('order_id' => $order['order_id']))->save(array('status' => 5));
            // 改为已取消
            M('Group_order')->where(array('order_id' => $order['order_id']))->save(array('status' => 4));
            //更新总销量
            M('Group')->where(['group_id'=>$order['group_id']])->setDec('sale_count_total',$order['num']);
            $group = M('Group')->where(array('group_id' => $order['group_id']))->find();
            if($group&&$group['type']==3){
                M('Group')->where(array('group_id' => $order['group_id']))->save(['type'=>1]);
            }

            /*-----记录系统订单信息 订单未支付取消 改写状态已取消 2021/1/6---团购状态-未付款取消订单4-*/
            D("System_order")->save_order_status('group',$order['order_id'],5,4);
		}
		foreach ($groups as $group_id => $num) {
			$this->keepThread();
			M('Group')->where(array('group_id' => $group_id, 'sale_count' => array('egt', $num)))->setDec('sale_count', $num);
		}
        // 如果存在规格信息 销量回滚
        if ($specifications_info) {
            foreach ($specifications_info as $specifications_id => $num) {
                $m_group_specifications->where(array('specifications_id' => $specifications_id, 'sale_count' => array('egt', $num)))->setDec('sale_count', $num);
            }
        }
		return true;
	}
}
?>