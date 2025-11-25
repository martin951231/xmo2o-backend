<?php

class Face_request_d4_recordAction extends BaseAction {

    /**
     *todo D4门禁设备回调接口 
     * @author: liukezhu
     * @date : 2021/7/12
     *
     */
    public function device_callback(){
        if($_POST){
            fdump($_POST,'api/log/'.date('Ymd').'/d4/device_callback',true);
            if(isset($_POST['deviceSn']) && !empty($_POST['deviceSn'])){
                $d4 = D('Face_d4_request_record');
                $d4->d4_notify($_POST['deviceSn'],$_POST['serviceType'],$_POST);
            }
            return true;
        }
    }


}
