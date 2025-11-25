<?php
/**
 * D1智慧停车 接收消息接口
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/4/16 17:44
 */
class House_park_d1Action  extends BaseAction {

    public function receiveDeviceInfo() {
        fdump_api($_GET,'receiveDeviceInfo_log',1);
        fdump_api($_POST,'receiveDeviceInfo_log',1);
        $data = [];
        $msg = [
            'error' => 0,
            'data' => $data
        ];
        $return = json_encode($msg,JSON_UNESCAPED_UNICODE);
        echo $return;exit();
    }

    public function plateResult() {
        fdump_api($_GET,'plateResult_log',1);
        fdump_api($_POST,'plateResult_log',1);
        $data = [];
        $msg = [
            'error' => 0,
            'data' => $data
        ];
        $return = json_encode($msg,JSON_UNESCAPED_UNICODE);
        echo $return;exit();
    }

    public function gio() {
        fdump_api($_GET,'gio_log',1);
        fdump_api($_POST,'gio_log',1);
        $data = [];
        $msg = [
            'error' => 0,
            'data' => $data
        ];
        $return = json_encode($msg,JSON_UNESCAPED_UNICODE);
        echo $return;exit();
    }

    public function serial() {
        fdump_api($_GET,'serial_log',1);
        fdump_api($_POST,'serial_log',1);
        $data = [];
        $msg = [
            'error' => 0,
            'data' => $data
        ];
        $return = json_encode($msg,JSON_UNESCAPED_UNICODE);
        echo $return;exit();
    }
}