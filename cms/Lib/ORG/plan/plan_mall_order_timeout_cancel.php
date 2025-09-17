<?php

/**
 * author 卢敏
 * description 商城订单超时自动取消订单
 */
class plan_mall_order_timeout_cancel extends plan_base
{
	public function runTask()
    {
    	$where = [
    		'paid' => 0,
    		'prepare_end' => 0,
    		'is_timeout_cancel' => 0,
    		'addtime' => ['lt', time() - 15*60]
    	];
    	$data = M("Mall_order_combine")->where($where)->field('combine_id, order_ids')->order('addtime asc')->limit(5)->select();
    	foreach ($data as $key => $value) {
    		if($value['order_ids']){
    			$order_ids = explode(',', $value['order_ids']);
    			foreach ($order_ids as $order_id) {
    				$param = [
    					'order_id' => $order_id,
    					'status' => 51,
    					'note' => '超时取消订单（计划任务）',
    				];
    				invoke_v20_service('\app\mall\model\service\MallOrderService/changeOrderStatus', $param);
    			}
    		}
            M("Mall_order_combine")->where(['combine_id' => $value['combine_id']])->data(['is_timeout_cancel' => 1])->save();
    	}
        return true;
    }
}