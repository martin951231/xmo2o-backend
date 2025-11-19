<?php
/**
 * 定时清除开门记录和摄像机记录
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/3/4 15:02
 */
class plan_clear_open_info extends plan_base{
    public function runTask($param=null){
        $clear_open_days = C('config.house_open_info_days');
        if ($clear_open_days && $clear_open_days>0) {
            $this->del_open_info($clear_open_days);
            $this->del_camera_info($clear_open_days);
            $this->del_a7_request($clear_open_days);
            $this->del_a7_log($clear_open_days);
        }
        // 避免堆积过多 每天执行一次清理，清理3天前命令数据
        $clear_open_days = 3;
        $this->del_park_total_record($clear_open_days);
        return true;
    }

    /**
     * Notes: 删除开门记录 多少天前的开门事件
     * @param $clear_days
     * @return bool
     * @author: wanzy
     * @date_time: 2021/3/4 15:31
     */
    public function del_open_info($clear_days) {
        // 计算实现
        $day = date("Y-m-d", strtotime(' -'. $clear_days . 'day'));
        $times  = strtotime($day);
        $where = [
            'add_time' => ['lt', $times]
        ];
        $whereStr='add_time>100 AND add_time<'.$times;
        fdump_api(['删除开门记录>'.__LINE__,'whereStr'=>$whereStr,'clear_days'=>$clear_days],'del_open_info_log',1);
        M('House_user_log')->where($whereStr)->delete();
        $this->keepThread();
//        M('')->execute("optimize table `pigcms_house_user_log`");
//        $this->keepThread();

        $judge = date('Ymd', time()-$clear_days*86400);
        //删除对应日期的人脸图片
        import('ORG.Util.Dir');
        $dir_class = new Dir('./upload/face_log/');
        $dir_class->listFile('./upload/face_log/');
        if($dir_class->_values){
            foreach($dir_class->_values as $value){
                if($value['type'] == 'dir' && preg_match("/^\d*$/",$value['filename']) && strlen($value['filename'])==8 && $value['filename'] < $judge){
                    Dir::delDir('./upload/face_log/' . $value['filename'], false);
                }
            }
        }
        $this->keepThread();
        //删除对应日期的文件
        $dir_class1 = new Dir('./upload/face_door/');
        $dir_class1->listFile('./upload/face_door/');
        if($dir_class1->_values){
            foreach($dir_class1->_values as $value1){
                if($value1['type'] == 'dir' && preg_match("/^\d*$/",$value1['filename']) && strlen($value1['filename'])==8 && $value1['filename'] < $judge){
                    Dir::delDir('./upload/face_door/' . $value1['filename'], false);
                }
            }
        }
        $this->keepThread();
        //删除对应捕捉的车辆图片
        $dir_class2 = new Dir('./upload/car_log/');
        $dir_class2->listFile('./upload/car_log/');
        if($dir_class2->_values){
            foreach($dir_class2->_values as $value2){
                if($value2['type'] == 'dir' && preg_match("/^\d*$/",$value2['filename']) && strlen($value2['filename'])==8 && $value2['filename'] < $judge){
                    Dir::delDir('./upload/car_log/' . $value2['filename'], false);
                }
            }
        }

        return true;
    }

    /**
     * Notes: 按照时间 删除 多少天前的照相机捕捉事件 必须是比对过的
     * @param $clear_days
     * @return bool
     * @author: wanzy
     * @date_time: 2021/3/5 11:26
     */
    public function del_camera_info($clear_days) {
        // 计算实现
        $day = date("Y-m-d", strtotime(' -'. $clear_days . 'day'));
        $times  = strtotime($day);
        fdump_api('删除摄像记录>'.__LINE__,'del_camera_info_log',1);
        fdump_api(date('Y-m-d',$times),'del_camera_info_log',1);
        $where_img = [
            'camera_time' => ['lt', $times]
        ];
        // 删除存储记录
        M('House_camera_img')->where($where_img)->delete();
        $this->keepThread();
        $where_tip = [
            'tip_time' => ['lt', $times],
            'is_plant' => 1
        ];
        // 删除已经通知消息记录
        M('House_camera_tip')->where($where_tip)->delete();
        $this->keepThread();
//        M('')->execute("optimize table `pigcms_house_camera_img`");
//        $this->keepThread();
//        M('')->execute("optimize table `pigcms_house_camera_tip`");
//        $this->keepThread();
        //删除对应日期的文件
        import('ORG.Util.Dir');
        $dir_class = new Dir('./upload/camera_log/');
        $dir_class->listFile('./upload/camera_log/');
        $judge = date('Ymd', time()-$clear_days*86400);
        if($dir_class->_values){
            foreach($dir_class->_values as $value){
                if($value['type'] == 'dir' && preg_match("/^\d*$/",$value['filename']) && strlen($value['filename'])==8 && $value['filename'] < $judge){
                    Dir::delDir('./upload/camera_log/' . $value['filename'], false);
                }
            }
        }
        $this->keepThread();
        return true;
    }

    /**
     * Notes: 按照时间 删除 多少天前的命令事件 必须是执行过的
     * @param $clear_days
     * @return bool
     * @author: wanzy
     * @date_time: 2021/3/5 11:47
     */
    public function del_a7_request($clear_days) {
        // 计算实现
        $day = date("Y-m-d", strtotime(' -'. $clear_days . 'day'));
        $times  = strtotime($day);
        fdump_api('删除A7命令记录>'.__LINE__,'del_a7_request_log',1);
        fdump_api(date('Y-m-d',$times),'del_a7_request_log',1);
        $where = [
            'add_time' => ['lt', $times],
            'status' => ['neq', 0]
        ];
        // 删除命令记录
        M('Face_a7_request_record')->where($where)->delete();
        $this->keepThread();
//        M('')->query("optimize table `pigcms_face_a7_request_record`");
//        $this->keepThread();
        return true;
    }

    /**
     * Notes:删除对应日期的A7日志文件
     * @param $clear_days
     * @return bool
     * @author: wanzy
     * @date_time: 2021/5/13 19:00
     */
    public function del_a7_log($clear_days) {
        //删除对应日期的日志文件
        import('ORG.Util.Dir');
        $dir_class = new Dir('./api/log/');
        $dir_class->listFile('./api/log/');
        if($dir_class->_values){
            $clear_day = date('Ymd', time()-$clear_days*86400);
            $clear_day = intval($clear_day);
            foreach($dir_class->_values as $value){
                $file_name = intval($value['filename']);
                if ($file_name && $clear_day && $file_name<$clear_day) {
                    Dir::delDir('./api/log/' . $file_name.'/a7', false);
                }
            }
        }
        $this->keepThread();
        return true;
    }

    /**
     * Notes:删除对应日期的A1执行命令
     * @param $clear_days
     * @return bool
     */
    public function del_park_total_record($clear_days) {
        $day = date("Y-m-d", strtotime(' -'. $clear_days . 'day'));
        $times  = strtotime($day);
        fdump_api('删除A1智慧停车命令记录>'.__LINE__,'del_park_total_record_log',1);
        fdump_api(date('Y-m-d',$times),'del_park_total_record_log',1);
        $where = [
            'create_time' => ['lt', $times],
            'status' => ['neq', 0]
        ];
        // 删除命令记录
        M('Park_total_record')->where($where)->delete();
        $this->keepThread();
        return true;
    }
}