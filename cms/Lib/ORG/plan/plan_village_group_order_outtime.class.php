<?php
/*拼团超时付款 和 周期购 超时未发货 自动延期
 * */
class plan_village_group_order_outtime extends plan_base
{
	public function runTask(){
		//超时把状态改为超时未支付
		$now_time = time();
		$where['status'] = 0;
		$where['add_time'] = array('lt',$now_time-(C('config.village_group_order_unpaid_timeout')?C('config.village_group_order_unpaid_timeout')*60:1800));
        $list=M('Village_group_order')->where($where)->order('order_id DESC')->limit(30)->select();
        if(!empty($list)){
            foreach ($list as $k=>$v){
                $where1=['real_orderid'=>$v['real_orderid'],'type'=>'village_group'];
                M('System_order')->where($where1)->setField('system_status',5);

                //下单后减库存，未支付订单回滚库存
                D('Village_group_order')->rollbackStockByOrderId($v['order_id']);
            }
        }
		M('Village_group_order')->where($where)->setField('status',10);


		$condition['_string'] = "end_time > 0 AND end_time+86400 <{$now_time} AND status <> 4 AND status <> 3";

		M('Village_group_goods')->where($condition)->setField('status',3);

		$condition2['_string'] = "get_time > 0 AND get_time+86400 <{$now_time} AND status <> 4 AND status <> 5";

		M('Village_group_goods')->where($condition2)->setField('status',5);

		// 如果存在超时未发货的周期购，做自动延期处理
        $detail_where = array(
            'village_group_type' => 1,
            'is_delay' => 0,
            'send_time' => array('neq', time())
        );
        $order_detail = M('Village_group_order_detail')->where($detail_where)->limit(5)->select();
        if ($order_detail) {
            foreach ($order_detail as $detail) {
                $this->village_group_delay($detail);
            }
        }

		return true;
	}


    // 自动延期
    private function village_group_delay($detail){
        if (!$detail) {
            return false;
        }
        if ($detail['status']>0 && $detail['status']!=6) {
            return false;
        }
        if ($detail['is_delay'] || $detail['status']==6) {
            return false;
        }
        $order = M('Village_group_order')->where(array('order_id' => $detail['order_id']))->find();
        $delay_user = 4; // 自动延期
        // 计算从当前天数计算备货日期节点时间
        $limit_days = intval($order['limit_days']);
        $limit_time = $order['limit_time']?$order['limit_time']:'18:00';
        $now_time = strtotime(date('Y-m-d '.$limit_time));
        $now_day = strtotime("+".$limit_days." day", $now_time);
        $send_time = $detail['send_time'];
        if ($now_day>$send_time){
            return false;
        }
        $save = array(
            'is_delay'=>1,
            'status'=>6,
            'delay_user'=>$delay_user,
            'delay_time'=>time()
        );
        if (M('Village_group_order_detail')->where(array('id'=>$detail['id']))->data($save)->save()) {
            // 查询周期购最后一条
            $cycle_info = M('Village_group_order_detail')->where(array('order_id' => $detail['order_id']))->order('date_num DESC, send_time DESC')->find();
            if ($detail['date_num']>=$order['delivery_periods'] || M('Village_group_order_detail')->where(array('order_id' => $detail['order_id'],'is_delay'=>0,'status'=>0,'date_num'=>array('gt',$detail['date_num'])))->setDec('date_num')) {
                // 延期后的期数所有统一减一期  再在最后一期后面再生成一期
                $create_data = $detail;
                unset($create_data['id']);
                $d_village_group=D('Village_group');
                $create_data['send_time'] = $d_village_group->order_lately_cycle_time($order,$detail['goods_id'],$order['delivery_date'],true,1,$cycle_info['send_time'],$cycle_info['send_time']);
                $create_data['is_delay']=0;
                $create_data['status']=0;
                $create_data['village_group_type']=1;
                $create_data['date_num']=$order['delivery_periods'];
                $info = M('Village_group_order_detail')->add($create_data);
                if ($info) {
                    return true;
                } else {
                    $del_save = array(
                        'is_delay'=>0,
                        'status'=>$detail['status'],
                        'delay_user'=>0,
                        'delay_time'=>0
                    );
                    M('Village_group_order_detail')->where(array('id'=>$detail['id']))->data($del_save)->save();
                    return false;
                }
            } else {
                $del_save = array(
                    'is_delay'=>0,
                    'status'=>$detail['status'],
                    'delay_user'=>0,
                    'delay_time'=>0
                );
                M('Village_group_order_detail')->where(array('id'=>$detail['id']))->data($del_save)->save();
                M('Village_group_order_detail')->where(array('order_id' => $detail['order_id'],'is_delay'=>0,'status'=>0,'date_num'=>array('gt',$detail['date_num'])))->setInc('date_num');

                return false;
            }
        } else {
            return false;
        }
    }


}
?>