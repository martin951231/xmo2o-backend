<?php
/*定时获取摄像机资源*/
class plan_sub_camera_ftp_img extends plan_base{
    public function runTask($param=null){
        if (empty(C('config.house_camera_ftp_host')) || empty(C('config.house_camera_ftp_admin')) || empty(C('config.house_camera_ftp_password'))) {
            return true;
        }
        set_time_limit(0);
        ini_set('memory_limit','-1');
        import('@.ORG.ftp');
        $config = array(
            'hostname' => C('config.house_camera_ftp_host'),
            'username' => C('config.house_camera_ftp_admin'),
            'password' => C('config.house_camera_ftp_password'),
        );
        import('@.ORG.ftp');
        $ftp = new ftp();
        $conn = $ftp->connect($config);
        $this->keepThread();
        $data = $ftp->dir_switch();
        $this->keepThread();
        fdump_api(['图片资源'.__LINE__,$config,$conn],'camera/plan_sub_camera_ftp_img_log',true);

//        $img_mer_id = sprintf("%09d", $id);
//        $rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
        $rand_num = date('Ymd');// 换成日期存储
        $up_dir = '/upload/camera_log/'.$rand_num.'/';
        if (!is_dir($_SERVER['DOCUMENT_ROOT'].$up_dir)) {
            mkdir($_SERVER['DOCUMENT_ROOT'].$up_dir, 0777, true);
        }

        if ($data) {
            fdump_api(['图片'.__LINE__,$data],'camera/plan_sub_camera_ftp_img_log',true);
            $d_house_camera_img = M('House_camera_img');
            $d_house_camera_device = M('House_camera_device');
            $nowtime=time();
            foreach ($data as $item) {
                $item_arr  = explode("/", $item);
                $img_arr = explode(".", $item);
                $type = array_pop($img_arr);
                if (!in_array($type,array('jpg','jpeg','png','gif'))) {
                    continue;// 如果不符合类型 继续
                }
                $url_file = $up_dir.'ftp'.date('YmdHis_').uniqid().'.'.$type;
                $new_file = $_SERVER['DOCUMENT_ROOT'] . $url_file;
                $msg = $d_house_camera_img->where(array('camera_img' => $item))->find();
                if (!$msg) {
                    $url = str_replace('./','/',$item);
                    $tmp = $ftp->download($url, $new_file);
                    fdump_api([__LINE__,'download_ret'=>$tmp,$url,'new_file'=>$new_file],'camera/plan_sub_camera_ftp_img_log',true);
                    if($tmp){
                            // 获取具体摄像机抓拍时间
                            $date_msg='';
                            if(isset($item_arr['4'])){
                                $day_msg_arr = explode("[M]", $item_arr[4]);
                                $day_msg = str_replace('.',':',$day_msg_arr[0]);
                                $date_msg = $item_arr[2] . ' ' . $day_msg;
                            }
                            $file = array_pop($item_arr);
                            $file_name = str_replace('.'.$type,'',$file);
                            $cameraFileTime = 0;
                            if(stripos($file_name, "[M]") !== false){
                                $filemtime_arr = explode("[M]", $file_name);
                                $time_msg = $filemtime_arr[0];
                                $filemtime_time_arr = explode("_", $time_msg);
                                foreach ($filemtime_time_arr as $filemtime_time) {
                                    if ($filemtime_time && strtotime($filemtime_time)) {
                                        $cameraFileTime = strtotime($filemtime_time);
                                        break;
                                    }
                                }
                            }
                            $fileTime = $ftp->ftp_mdtm($item);
                            fdump_api(['单条'.__LINE__,$fileTime,$cameraFileTime,$date_msg,$item_arr],'camera/plan_sub_camera_ftp_img_log',true);
                            $camera_id = 0;
                            $camera_time = 0;
                            if ($cameraFileTime) {
                                $camera_time = $cameraFileTime;
                            } elseif ($fileTime>1000) {
                                $camera_time = $fileTime;
                            } elseif (!empty($date_msg) && strtotime($date_msg)) {
                                $camera_time = strtotime($date_msg);
                            } else {
                                $time_detail = '';
                                // 处理详细时间
                                $time_details = explode('[M]', $item);
                                if ($time_details && $time_details[0]) {
                                    $time_detail1 = explode("jpg/", $time_details[0]);
                                    if ($time_detail1 && $time_detail1[1]) {
                                        $time_detail = str_replace('/',':',$time_detail1[1]);
                                    }
                                }
                                if (!$time_detail) {
                                    // 没有具体时间 去当前时间详细
                                    $time_detail = date('H:i', time());
                                }
                            }
                            $camera_sn='';
                            foreach ($item_arr as $v1) {
                                if (!$camera_id) {
                                    $where_deviece = [
                                        'camera_sn' => $v1
                                    ];
                                    $camera_id = $d_house_camera_device->where($where_deviece)->getField('camera_id');
                                    if ($camera_id) {
                                        $camera_sn = $v1;
                                    }
                                }
                                if (!$camera_time && strtotime($v1)) {
                                    if ($time_detail) {
                                        $v1 .= ' '. $time_detail;
                                    }
                                    $camera_time = strtotime($v1);
                                }
                            }
                            $camera_time=$camera_time>1000 ? $camera_time:$nowtime;
                            $house_camera_img = array(
                                'camera_sn' => $camera_sn,
                                'camera_time' => $camera_time,
                                'camera_img' => $item,
                                'camera_name' => $file_name,
                                'camera_url' => $url_file,
                                'add_time' => $nowtime
                            );
                            fdump_api(['添加前信息'.__LINE__,$house_camera_img,$item],'camera/plan_sub_camera_ftp_img_log',true);
                            $info = $d_house_camera_img->data($house_camera_img)->add();
                            fdump_api(['添加后信息'.__LINE__,'sql'=>$d_house_camera_img->getLastSql()],'camera/plan_sub_camera_ftp_img_log',true);
                            if ($info) {
                                // 如果成功下载下来并进行了记录就进行删除
                                fdump_api(['存储过信息删除'.__LINE__,$info,$item],'camera/plan_sub_camera_ftp_img_log',true);
                                $del = $ftp->delete_file($item);
                            }
                        
                        echo "upload $new_file -> success \n";
                    }
                } else {
                    // 如果成功下载下来就进行删除
                    fdump_api(['存储过信息删除'.__LINE__,$msg,$item],'camera/plan_sub_camera_ftp_img_log',true);
                    $del = $ftp->delete_file($item);
                }
            }
        }
        $close = $ftp->close();
        return true;
    }
}
?>