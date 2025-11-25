<?php
/*
 * 
 *  配送员接口
 * 
 */
class Interface_deliverAction extends BaseAction{
	
	public function set_dispatch(){
		header("Content-type: application/json");
		
		if($_POST['key'] != md5('Hark2017')){
			exit(json_encode(['status'=>'error','err_msg'=>'对接key错误']));
		}
		
		$not_dispatch = intval($_POST['not_dispatch']);
		
		$order_ids = $_POST['order_ids'];
		if(empty($order_ids)){
			exit(json_encode(['status'=>'error','err_msg'=>'订单号 order_ids 为空']));
		}
		$order_ids = explode(',' , $order_ids);
		
		D('Deliver_supply')->where(['order_id' => ['in', $order_ids]])->data(['not_dispatch'=>$not_dispatch])->save();
		
		exit(json_encode(['status'=>'ok','err_msg'=>'设置完成']));
	}
	
	public function supply(){
		header("Content-type: application/json");
		
		if($_POST['key'] != md5('Hark2017')){
			exit(json_encode(array('status'=>'error','err_msg'=>'对接key错误')));
		}
		$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    	$supply = D('Deliver_supply')->field(true)->where(array('order_id' => $order_id, 'type' => 0))->find();
    	if (empty($supply)){
			exit(json_encode(array('status'=>'error','err_msg'=>'不存在的数据')));
		}
    	if (IS_POST) {
    		if ($supply['status'] > 4){
				exit(json_encode(array('status'=>'error','err_msg'=>'配送已完成，不能重新指派了')));
			}
			
			$save_data = [];
			//智能分单参数
			if(isset($_POST['type'])){
				$save_data['get_type'] = '4';	//0:默认用户操作，1：系统指派，2：系统更换，3：计划任务指定，4：智能分单
				if($_POST['type'] == 0){
					$save_data['expect_uid'] = $_POST['expect_uid'];
					$save_data['expect_fetch_time'] = $_POST['expect_fetch_time'];
					$save_data['expect_arrival_time'] = $_POST['expect_arrival_time'];
				}else if($_POST['type'] == 1){
					$save_data['uid'] = $_POST['uid'];
					$save_data['expect_fetch_time'] = $_POST['expect_fetch_time'];
					$save_data['expect_arrival_time'] = $_POST['expect_arrival_time'];
					$uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;
				}else if($_POST['type'] == 2){
					$save_data['expect_fetch_time'] = $_POST['expect_fetch_time'];
					$save_data['expect_arrival_time'] = $_POST['expect_arrival_time'];
				}
				
				//如果没有uid，直接保存不做下一步处理。
				if(!$uid){
					$result = D('Deliver_supply')->where(array('supply_id' => $supply['supply_id']))->save($save_data);
					exit(json_encode(array('status'=>'ok','err_msg'=>'预指派数据存储成功')));
				}
			}else{
				$uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;
			}
			
			
			if($uid == $supply['uid']){
				exit(json_encode(array('status'=>'error','err_msg'=>'该订单已经属于该配送员')));
			}
			
    		$user = D('Deliver_user')->field(true)->where(array('uid' => $uid, 'group' => 1, 'status' => 1))->find();
    		if (empty($user)){
				exit(json_encode(array('status'=>'error','err_msg'=>'配送员不存在')));
			}
    		$status = $supply['status'] == 1 ? 2 : $supply['status'];
    		$save_data['uid'] = $uid;
			$save_data['status'] = $status;
			if ($status == 2) {
				$save_data['start_time'] = time();
			}
			$from_type = 1;//0:默认用户操作，1：系统指派，2：系统更换，3：计划任务指定，4：智能分单
    		if ($supply['uid']) {
    		    $from_type = 2;
    			$save_data['get_type'] = 2;
    			$save_data['change_log'] = $supply['change_log'] ? $supply['change_log'] . ',' . $supply['uid'] : $supply['uid'];

    		} else {
    			$save_data['get_type'] = 1;
            }
			$save_data['get_type'] = '4';
            $save_data['deliver_check'] = 0;
            $save_data['is_fetch_order'] = 0;
            $save_data['check_time'] = time()+$this->config['deliver_look_out_time']*60;
            if($supply['item'] == '3'){//帮买帮送
                $publishInfo = D('Service_user_publish')->where(array('publish_id'=> $supply['order_id']))->find();
                if($publishInfo['catgory_type'] == 2){
                    //帮我买
                    $cat_field_info = D('Service_user_publish_buy')->where(array('publish_id'=>$publishInfo['publish_id']))->find();
                }
                if($publishInfo['catgory_type'] == 3){
                    //帮我送
                    $cat_field_info = D('Service_user_publish_give')->where(array('publish_id'=>$publishInfo['publish_id']))->find();
                }
                if($cat_field_info){
                    $fc = $cat_field_info['basic_distance_price'] + $cat_field_info['weight_price'] + $cat_field_info['distance_price'];//基础配送费+超重费用+超距离配送
                    $save_data['deliver_user_fee'] = $fc - ($fc * ($user['take_percent']/100));//配送员所得费用（扣除平台抽成）
                }
            }
            else{
                $save_data['deliver_user_fee'] = $supply['freight_charge'] - ($supply['freight_charge'] * ($user['take_percent']/100));//配送员所得费用（扣除平台抽成）
            }
			
			

            $result = D('Deliver_supply')->where(array('supply_id' => $supply['supply_id']))->save($save_data);
    		if ($status == 2) {
    			if ($supply['item'] == 0) {
    				$result = D("Meal_order")->where(array('order_id' => $supply['order_id']))->data(array('order_status' => 8))->save();
    			} elseif ($supply['item'] == 2) {
    				$deliver_info = serialize(array('uid' => $user['uid'], 'name' => $user['name'], 'phone' => $user['phone'], 'store_id' => $user['store_id']));
    				$result = D("Shop_order")->where(array('order_id' => $supply['order_id']))->data(array('order_status' => 2, 'deliver_info' => $deliver_info))->save();
    				D('Shop_order_log')->add_log(array('order_id' => $supply['order_id'], 'from_type' => $from_type, 'status' => 3, 'name' => $user['name'], 'phone' => $user['phone']));
    			} elseif ($supply['item'] == 3) {
    			    $offer_id = D('Service_offer')->add_offer($supply['order_id'], $user['uid'], '-1');
    			    $data = array('offer_id' => $offer_id);
    			    if ($supply['server_type'] != 1) {
    			        $data['appoint_time'] = time() + $supply['server_time'] * 60;
    			    }
    			    D('Deliver_supply')->where(array('supply_id' => $supply['supply_id']))->save($data);
    			} elseif ($supply['item'] == 8) {
                    // 社区拼团
                    $deliver_info = serialize(array('uid' => $user['uid'], 'name' => $user['name'], 'phone' => $user['phone'], 'store_id' => $user['store_id']));
                    D('Village_group_order')->where(array('order_id' => $supply['order_id']))->data(array('order_status' => 1, 'deliver_info' => $deliver_info))->save();

                    $detail_info = M('Village_group_order_detail')->where(array('order_id' => $supply['order_id']))->select();

                    foreach ($detail_info as $value) {
                        $param['goods_id'] = $value['goods_id'];
                        $param['from_type'] = 1;
                        $param['deliver_status'] = 6;
                        $param['note'] = '系统分配配送员接单';
                        $param['name'] = '自动处理';

                        $param['order_id'] = $value['order_id'];
                        $param['order_id'] = $value;
                        D('Village_group')->add_log($param);
                    }
                }
    		} else {
    		    D('Shop_order_log')->add_log(array('order_id' => $supply['order_id'], 'from_type' => $from_type, 'status' => $status + 1, 'name' => $user['name'], 'phone' => $user['phone']));
    		}
    		D('Deliver_supply')->sendNotice($user, $supply);
			
			exit(json_encode(array('status'=>'ok','err_msg'=>'指派成功')));
    	}
	}
}
