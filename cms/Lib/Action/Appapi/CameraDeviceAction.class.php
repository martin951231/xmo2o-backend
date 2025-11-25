<?php

class CameraDeviceAction extends BaseAction
{
    public function iSecureCenterEventRcv() {
        fdump_api(['$_POST' => $_POST,'input'=>file_get_contents("php://input"),'$_GET' => $_GET,'$_SERVER' => $_SERVER],'iSecureCenter/iSecureCenterEventRcvLog',1);
        $arr = [
            'code' => 0,
            'msg' => "success"
        ];
        exit(json_encode($arr));
    }
}