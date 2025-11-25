<?php
class Group_pass_relationModel extends Model{
	public function get_pass_array($order_id){
		$pass_array = $this->field(true)->where(array('order_id'=>$order_id))->select();
		return $pass_array;
	}

	public function get_pass_num($order_id,$status=0){
		$count = $this->where(array('order_id'=>$order_id,'status'=>$status))->count();
		return $count;
	}

	public function get_orderid_by_pass($group_pass){
		$order_id = $this->field('order_id')->where(array('group_pass'=>$group_pass))->find();
		return $order_id;
	}

	public function change_refund_status($order_id,$status=2,$staff_info=array()){
	    if ($staff_info) {
            $staff_info['status'] = $status;
            $rs = $this->where(array('order_id'=>$order_id,'status'=>array('in','0,3')))->data($staff_info)->save();
        } else {
            $rs =  $this->where(array('order_id'=>$order_id,'status'=>array('in','0,3')))->setField('status',$status);
        }
        fdump_api([$this->getLastSql(),$rs],'order/group_verify');
	}
	
	public function get_status_msg($status,$deadline_time,$effective_type,$pay_time)
    {
        $msg = '';
        if($status == 2){
            $msg = '已退款';
        }elseif($status == 1){
            $msg = '已核销';
        }elseif($status == 0){
            $msg = '待核销';
            if(($effective_type == 0 && $deadline_time < time()) || ($effective_type == 1 && $deadline_time*24*60*60+$pay_time < time())){
                $msg = '已过期';
            }
        }
        return $msg;
    }
    
    public function get_verify_type_msg($verify_type)
    {
        $msg = '';
        if($verify_type == 1){
            $msg = '移动端核销';
        }elseif($verify_type == 2){
            $msg = 'pc端核销';
        }
        return $msg;
    }
}
?>