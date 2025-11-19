<?php
/*
 * 电动车对接
 *
 */
class House_electriccarAction extends BaseAction{
	//	登录
    public function download(){
		if(empty($_POST['cmd'])){
			//厂家心跳包JSON有问题，cmd没有上报左边的括号
			$_POST = json_decode(str_replace(',cmd"', ',"cmd"', file_get_contents('php://input')), true);
		}
//		fdump_api([$_POST, $_GET, file_get_contents('php://input')], 'electriccar', true);
		
		header("Content-type: application/json");
		$device_id = trim($_POST['dev_id']);
        $whereDevice['device_id']	 = $device_id;
        $whereDevice['status']	 = 1;
        $house_electric_device = M('House_electric_device')->where($whereDevice)->find();
        // 对应设备对应命令一天打印最后一条消息
        fdump_api([$_POST, $_GET, file_get_contents('php://input')], "electriccar/{$device_id}/{$_POST['cmd']}");
		
		## 心跳包
		if($_POST['cmd'] == 'heartbeat'){
			$this->notify_save($_POST['dev_id']);
			
			
			//检查有没有事件交互
			$msg_where = [
				'device_id' => $_POST['dev_id'], 	//设备号
				'status' => '0', 	//未消费消息
				'add_time' => ['gt', time() - 210], 	//因为设备每分钟请求一次心跳，则3分钟半以内的事件才返回，避免断电断网恢复后造成门乱开
			];
			$last_msg = M('House_electric_msg')->where($msg_where)->find();
//			fdump([$last_msg, M('House_electric_msg')->getLastSql()], 'last_msg', true);
			if($last_msg && $last_msg['cmd'] == 'open'){
				$msg_data = [
					'use_time' => time(),
					'status' => '1',
				];
				M('House_electric_msg')->where(['msg_id' => $last_msg['msg_id']])->data($msg_data)->save();
				echo json_encode(['cmd' => 'open']);
			}else{
				echo json_encode(['cmd' => '']);
			}
			die;
		}
		
		## 控制开门
		if($_POST['cmd'] == 'control'){
			
			## 读到16进制卡号
            if(strlen($_POST['tag_id'])==8){
                $rfid_card=$_POST['tag_id'];
            }else{
                ## 读到16进制卡号
                $rfid_card = substr($_POST['tag_id'], -6, 6);

                ## 转化为10进制
                $rfid_card = hexdec($rfid_card);
                $rfid_card = sprintf("%08d", $rfid_card);
            }
            fdump_api(['msg' => '卡号', 'tag_id' => $_POST['tag_id'], 'rfid_card' => $rfid_card], "electriccar/{$device_id}/{$_POST['cmd']}", 1);
			
			## 查找出卡号
            $where['car_number'] = array('eq',$rfid_card);
            $where['car_stop_num'] = array('eq',$rfid_card);
            $where['_logic'] = 'OR';
            $condition_where['_complex'] = $where;
            if ($house_electric_device && $house_electric_device['village_id']) {
                $condition_where['village_id'] = array('eq',$house_electric_device['village_id']);
            }
			$now_car = M('House_village_parking_car')->where($condition_where)->find();
			## 若找不到卡号数据，或者停车时间超过了就不允许开门
            fdump_api(['msg' => '卡号结果', 'condition_where' => $condition_where, 'now_car' => $now_car], "electriccar/{$device_id}/{$_POST['cmd']}", 1);
			if(!$now_car || $now_car['end_time'] < time()){
				echo json_encode(['cmd' => '']);
				die;
			}
			
			$data = [
				'village_id' => $now_car['village_id'],
				'car_number' => $now_car['car_number'],
				'car_stop_num' => $now_car['car_stop_num'],
				'add_time' => time(),
                'device_id' => $house_electric_device['device_id']?$house_electric_device['device_id']:$device_id,
                'device_name' => $house_electric_device['device_name']?$house_electric_device['device_name']:'',
			];
            fdump_api(['msg' => '卡号记录', 'data' => $data], "electriccar/{$device_id}/{$_POST['cmd']}", 1);
			M('In_park_electriccar')->data($data)->add();
			
			$this->open_door_save($_POST['dev_id']);
			
			echo json_encode(['cmd' => 'open']);
			die;
		}
	}
	
	// 心跳包
	private function notify_save($device_id){
		fdump($device_id, 'device_id', true);
		if(empty($device_id)){
			return false;
		}
		$where['device_id']	 = trim($device_id);
		$data['notify_time'] = time();
		M('House_electric_device')->where($where)->data($data)->save();
		return true;
	}
	
	// 开门包
	private function open_door_save($device_id){
		fdump($device_id, 'device_id', true);
		if(empty($device_id)){
			return false;
		}
		$where['device_id']	 = trim($device_id);
		$data['notify_time'] = time();
		$data['last_open_time'] = time();
		M('House_electric_device')->where($where)->data($data)->save();
		return true;
	}
}