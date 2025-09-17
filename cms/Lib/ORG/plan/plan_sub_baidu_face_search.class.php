<?php
/*定时识别获取到的摄像机图片*/
class plan_sub_baidu_face_search extends plan_base{
	public function runTask($param=null){
        fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/baidu_face/plan_sub_baidu_face_search_log',true);
        $d_house_camera_img = M('House_camera_img');
        $d_house_face_img = D('House_face_img');
        $camera_img = $d_house_camera_img->where(array('is_ai_face'=>0))->limit(100)->select();
        fdump($camera_img,'api/log/'.date('Ymd').'/baidu_face/plan_sub_baidu_face_search_log',true);
        if ($camera_img) {
            foreach ($camera_img as $item) {
                $this->keepThread();
                $d_house_face_img->baidu_face_search($item['img_id']);
            }
        }
		return true;
	}
}
?>