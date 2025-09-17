<?php
/**
 * 处理瓜分券并发
 */

class plan_partition_coupon_hadpull_check extends plan_base{
	
    //查询所有待处理的记录
    public function runTask(){
		
        $where = array();
        $where['status'] = 0;
		
		$num = 5;
		
		$process = true;
		while($process){
			
			$list = M('Partition_coupon_hadpull_check')->field(true)->where($where)->limit($num)->select();

			if(empty($list)){
				
				return true;
				
			}else{
				
				foreach($list as $val){
        			$not_enough = false;

					$share_coupon = json_decode($val['coupon'],true);
					$data = json_decode($val['data'],true);

					// 平台/商家瓜分券
        			$type = $val['type'] ? $val['type'] : 'system';
			        if ($type=='system') {
			            $database_coupon = D('System_partition_coupon');
			            $database_coupon_hadpull = D('System_partition_coupon_hadpull');
			        }else{
			            $database_coupon = D('Merchant_partition_coupon');
			            $database_coupon_hadpull = D('Merchant_partition_coupon_hadpull');
			        }

			        // 瓜分券详情
			        $now_detail = $database_coupon->where(array('pigcms_id'=> $share_coupon['coupon_id'],'delete'=>0))->find();

					 // 查看参与瓜分总数
                    $where['_string'] = '(id='.$share_coupon['id'].' OR pid='.$share_coupon['id'].')';
                    $where['status'] = 0;
                    $where['coupon_id'] =$share_coupon['coupon_id'];
                    $has_hadpull_count = $database_coupon_hadpull->where($where)->count();
                    if ($has_hadpull_count>=$now_detail['user_num'] || $share_coupon['is_complete']) {
                        // 人数已满
                        $not_enough = true;
                    }

                    $save = [];
			        if(empty($not_enough)){//可以加入
			            $save['status'] = 1;

			            // 保存加入数据
			            $insert_id = $database_coupon_hadpull->add($data);
			            $msg['id'] = $insert_id;

			            // 验证人员是否已集齐
	                    if ($insert_id && $now_detail['user_num']<=($has_hadpull_count+1)) {                   

	                        $database_coupon_hadpull->where(array('id'=> $share_coupon['id']))->setField('is_complete', 1);
			            	$msg['is_complete'] = 1;

	                    }
			            $save['msg'] = json_encode($msg);

			        }else{//不可加入
			            $save['status'] = 2;
			        }
			        M('Partition_coupon_hadpull_check')->where(['id'=>$val['id']])->data($save)->save();

				}
			}
			
		}
        return true;
    }
}
?>