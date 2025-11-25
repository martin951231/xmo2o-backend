<?php
//腾讯云直播流回调地址
class Live_streamAction extends Action{
	public function back(){
		$json_input = file_get_contents("php://input");
		$input = json_decode($json_input, true);
		fdump($input, 'api/log/live/'.date('Ymd').'/'.'lumin0319', 1);
		$event_type = $input['event_type'];//事件通知信息类型：推流事件为1；断流事件为0；录制事件为100；截图事件为200。
		$sign = $input['sign'];
		$t = $input['t'];
		$key = D("Config")->get_config_by_name("live_back_key")['value'];
		if(md5($key.$t) == $sign){
			if($event_type == '100'){
				//【直播录制】文件生成事件
				$stream_param = explode('&', urldecode($input['stream_param']));
				$param = [];
				foreach ($stream_param as $temp) {
					$tem = explode('=', $temp);
					$param[$tem[0]] = $tem[1];
				}
				// if($param['type'] == '1'){
				// 	//直播回放
				// 	$now_live = D("Live_show")->where(['live_no'=>$param['liveno']])->find();
				// 	if($now_live){
				// 		D("Live_show")->where(['live_id'=>$now_live['live_id']])->data(['record_status'=>1,'record_result'=>serialize($input)])->save();
				// 	}
				// }
				// elseif($param['type'] == '2'){
				if($param['type'] == '2'){
					//商品录制
					$now_live = D("Live_show")->where(['live_no'=>$param['liveno']])->find();
					if($now_live){
						$now_goods = D("Live_show_goods")->where(['live_id'=>$now_live['live_id'], 'goods_id'=>$param['goodsid']])->find();
						if($now_goods){
							D("Live_show_goods")->where(['id'=>$now_goods['id']])->data(['record_status'=>1,'record_result'=>serialize($input),'recording'=>1,'recording_end_time'=>date('Y-m-d H:i:s')])->save();
						}
					}
				}
				elseif($param['type'] == '1' || !isset($param['type'])){
					//直播回放
					$now_live = D("Live_show")->where(['live_no'=>$input['stream_id']])->find();
					if($now_live){
						D("Live_show")->where(['live_id'=>$now_live['live_id']])->data(['record_status'=>1,'record_result'=>serialize($input)])->save();
					}
				}
				else{
					$this->output(['code'=>0]);
				}
			}
			elseif($event_type == '0'){
				//断流
				$now_live = D("Live_show")->where(['live_no'=>$input['channel_id']])->find();
				$data = [
					'is_interrupt' => 1,
					'interrupt_last_time' => time()
				];
				D("Live_show")->where(['live_id'=>$now_live['live_id']])->data($data)->save();
			}
			elseif($event_type == '1'){
				//推流
				$now_live = D("Live_show")->where(['live_no'=>$input['channel_id']])->find();
				$data = [
					'is_interrupt' => 0
				];
				D("Live_show")->where(['live_id'=>$now_live['live_id']])->data($data)->save();
			}
			$this->output(['code'=>0]);
		}
		else{
			//签名不通过
			$this->output(['code'=>0]);
		}
	}

	private function output($output){
		echo json_encode($output);
		exit;
	}

}