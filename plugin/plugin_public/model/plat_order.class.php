<?php
class plat_order {
	/*根据 支付平台的英文 和 是否移动端支付 得到中文名称*/
	public function add_order($param){
		if(empty($param['business_type'])){
			return array('error_code' => true, 'error_msg' => L_('请携带业务类型'));
		}
		if(floatval($param['total_money']) < 0){
			return array('error_code' => true, 'error_msg' => L_('请携带订单总价'));
		}
		if(empty($param['order_name'])){
			return array('error_code' => true, 'error_msg' => L_('请携带订单名称'));
		}
		$param['add_time'] = $_SERVER['REQUEST_TIME'];
		if($order_id = M('Plat_order')->data($param)->add()){
		    // 处理下服务快派订单
            if ($param['business_type'] && $param['business_type'] == 'service'){
                /*-----记录系统订单信息 订单创建 19/1/24--服务快派-*/
                plugin_model("System_order")->add_order('service',$param, $order_id);

            }

			return array('error_code' => false, 'order_id' => $order_id);
		}else{
			return array('error_code' => true, 'error_msg' => L_('订单创建失败，请重试'));
		}
	}
}

?>