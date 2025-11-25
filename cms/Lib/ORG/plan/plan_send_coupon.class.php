<?php
	/*
	 * 派发优惠券，平台和商家
	 * */
class plan_send_coupon extends plan_base{
	public function runTask($param){
		//删除计划任务
		if($param['plan_task_id']){
			$this->dropPlan($param['plan_task_id']);
		}
		set_time_limit(0);
		ini_set('memory_limit', '-1');
		
		$where['pigcms_id']= $param['id'];
		$res = M('Send_coupon_log')->where($where)->find();
		M('Send_coupon_log')->where($where)->setField('status',1);
		$coupon_list = explode(',',$res['coupon_id']);
		$coupon_send_num = explode(',',$res['send_coupon_num']);
		$where = array();
		if($res['diy_ids']){
			$diy_ids = explode(',',$res['diy_ids']);
			$condition = array();
			foreach ($diy_ids as $val) {
				$condition[] = "FIND_IN_SET('$val',diy_field_select)";
			}
			$condition_str = implode(' OR ', $condition);
			$where['_string'] = $condition_str;
			fdump($res,'diy_ids_send_coupon_log',1);
			fdump($where,'diy_ids_send_coupon_log',1);
			$user_list = M('Card_userlist')->field('uid')->where($where)->select();
			fdump(M()->getLastsql(),'diy_ids_send_coupon_log',1);
		}elseif($res['group_id']){
			$group_id = explode(',',$res['group_id']);
			$where['gid'] = array('in',$group_id);
            $where['status'] = 1;
			$user_list = M('Card_userlist')->field('uid')->where($where)->select();
		}elseif($res['mer_id'] && $res['type']==1){ // 商家优惠券发送全部，包括粉丝
			$user_list = M('Card_userlist')->field('uid')->where(array('mer_id'=>$res['mer_id'],'uid' => array('neq','0')))->select();

			//查询粉丝
        	$where_f['c.mer_id'] = $res['mer_id'];
	        $fans_lists = M('Merchant_user_relation')->join('as c left join '.C('DB_PREFIX').'user u ON c.openid = u.openid')->field('u.uid')->where($where_f)->select();
	        // 去重
	        if ($user_list) {
	            foreach ($user_list as $kuser => &$card_user) {
	                if($card_user['uid'] == '0') {
	            		unset($user_list[$kuser]);
	            	}
	            	else{
		                foreach ($fans_lists as $key => $fans) {
		                    if ($fans['uid'] == '0' || $fans['uid'] == $card_user['uid']) {
		                        unset($fans_lists[$key]);
		                    }
		                }
		            }
	            }
	            $user_list = $fans_lists ? array_merge($user_list,$fans_lists) : $user_list;
	        }else{
	            $user_list = $fans_lists;
	        }
		}elseif($res['uid'] && $res['type']==2){
			$user_list[] = array('uid'=>$res['uid']);

		}else{
			$where['status'] = 1;
			$user_list = M('User')->where($where)->field('uid')->select();
		}
		if($res['mer_id']){
			$model = D('Card_new_coupon');
			$history_model = M('Card_coupon_send_history');

		}else{
			$model = D('System_coupon');
			$history_model = M('System_coupon_send_history');
		}

        fdump_sql($user_list, 'plan_send_coupon');
		foreach ($user_list as $v) {
			foreach ($coupon_list as $key=>$item) {
				$send_num = $coupon_send_num[$key]>0?$coupon_send_num[$key]:1;
				$success = 0;
				$fail = 0;
				$fail_msg = [];
				for ($index = 0; $index < $send_num; $index++) {
					if ($res['mer_id']) {
						$tmp = $model->had_pull($item, $v['uid'], '', false, false, false, 0, 1,true);
					} else {
						$tmp = $model->had_pull($item, $v['uid'], '', 0, 1);
					}

					$model->decrease_sku(0, 1, $item); //网页领取完，微信卡券库存需要同步减少
					if ($tmp['error_code'] > 0) {
						$fail++;
						$fail_msg[] = $this->coupon_error($tmp['error_code']);
						$tmp['msg'] = '发送失败（' . $this->coupon_error($tmp['error_code']) . ')';
					} else {
						$success++;
						$tmp['msg'] = $this->coupon_error($tmp['error_code']);
					}
				}

				if ($fail > 0) {
					$tmp['msg'] = sprintf("部分发送成功,成功数量%d,失败数量%d,失败原因如下:%s", $success, $fail, implode(array_unique($fail_msg)));
				} else {
					$tmp['msg'] = sprintf("全部发送成功,数量%d", $success);
				}

				$data['uid'] = $v['uid'];
				$data['coupon_id'] = $item;
				$res['mer_id'] && $data['mer_id'] = $res['mer_id'];
				$data['error_code']  =$tmp['error_code'];
				$data['msg']  =$tmp['msg'];
				$data['add_time']  =time();
				$history_model->add($data);
				$return[$tmp['coupon']['coupon_id']]['error_msg'] = $tmp['msg'];
				$return[$tmp['coupon']['coupon_id']]['coupon_name'] = $tmp['coupon']['name'];
				$return[$tmp['coupon']['coupon_id']]['send_code'] = $tmp['error_code'];
			}

			// 粉丝自动分发会员卡
			if ($res['mer_id']) { // 商家优惠券
				D('Card_new')->auto_get($v['uid'],$res['mer_id'],'',true);
			}
		}

		return true;
	}

	public function coupon_error($error_code){
		switch($error_code) {
			case '0':
				$error_msg = '领取成功';
				break;
			case '1':
				$error_msg = '领取发生错误';
				break;
			case '2':
				$error_msg = '优惠券已过期';
				break;
			case '3':
				$error_msg = '优惠券已经领完了';
				break;
			case '4':
				$error_msg = '只允许新用户领取';
				break;
			case '5':
				$error_msg = '不能再领取了';
				break;
		}
		return $error_msg;
	}

}
?>