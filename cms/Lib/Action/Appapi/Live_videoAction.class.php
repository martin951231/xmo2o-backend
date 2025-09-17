<?php
//腾讯云点播回调地址
class Live_videoAction extends Action{
	public function back(){
		$json_input = file_get_contents("php://input");
		$input = json_decode($json_input, true);
		fdump($input, "lumin0428", 1);
		if($input['EventType'] == 'ProcedureStateChanged'){
			$data = [
				'file_id' => $input['ProcedureStateChangeEvent']['FileId'],
				'img' => $input['ProcedureStateChangeEvent']['MediaProcessResultSet'][0]['CoverBySnapshotTask']['Output']['CoverUrl'],
				'addtime' => date('Y-m-d H:i:s'),
				'content' => $json_input
			];
			$lastid = M("Live_video_plat_notify")->data($data)->add();
			fdump($lastid, "lumin0428", 1);
			if($has = M("Live_video")->where(['plat_video_id'=>$data['file_id']])->find()){
				if(stripos($has['img'],'upload') === false){// 上传过封面图不需要再保存了
					if($data['img']) {
						$save['img'] = $data['img'];
						M("Live_video")->where(['video_id'=>$has['video_id']])->data($save)->save();
					}
				}
			}
		}
		
		$this->output(['code'=>0]);
	}

	private function output($output){
		echo json_encode($output);
		exit;
	}

}