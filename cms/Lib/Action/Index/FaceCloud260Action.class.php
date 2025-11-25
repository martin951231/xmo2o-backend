<?php
/**
 * 260云人脸门禁设备
 * This file is part of Kuaijing Bailing.
 *
 * @link     https://www.kuaijingai.com
 * @document https://help.kuaijingai.com
 * @contact  www.kuaijingai.com 7*12 9:00-21:00
 */
class FaceCloud260Action extends BaseAction
{
    /**
     * upload-2.1 设备主动往云端实时上传打卡记录.
     * 设备主动往云端实时上传打卡记录.
     * 相当刷脸刷卡等上报.
     */
    public function insertNoteFace() {
        $postParam = json_decode(file_get_contents("php://input"), true);
        if (empty($postParam) && !empty($_POST)) {
            $postParam = $_POST;
        }
        if ($postParam) {
            try {
                $param = [
                    'params' => $postParam,
                ];
                $invokeResult = invoke_v20_service('\app\community\model\service\Device\HouseVillageDeviceCmdService/insertNoteFace',$param);
                $result = $invokeResult['data']['retval'];
                fdump_api(['msg' => '设备主动往云端实时上传打卡记录', '_POST' => $postParam, 'result' => $result], 'FaceCloud260/insertNoteFaceLog', 1);
            }catch (\Exception $e){
                fdump_api(['msg' => '设备主动往云端实时上传打卡记录', 'err' => $e->getMessage()], 'FaceCloud260/errInsertNoteFaceLog');
            }
        } else {
            fdump_api(['msg' => '设备主动往云端实时上传打卡记录', 'err' => '无参数', 'param' => $_REQUEST], 'FaceCloud260/errInsertNoteFaceLog');
        }
        $this->returnOk(["success" => 0, "msg" => 'OK']);
    }

    /**
     * download-2.2 设备主动触发云端导入雇员授权信息.
     */
    public function selectPassInfo() {
        $postParam = json_decode(file_get_contents("php://input"), true);
        if (empty($postParam) && !empty($_POST)) {
            $postParam = $_POST;
        }
        $result = [];
        if ($postParam) {
            try {
                $param = [
                    'params' => $postParam,
                ];
                $invokeResult = invoke_v20_service('\app\community\model\service\Device\HouseVillageDeviceCmdService/selectPassInfo',$param);
                $result = $invokeResult['data']['retval'];
                fdump_api(['msg' => '设备主动触发云端导入雇员授权信息', 'result' => $result, '_POST' => $postParam, 'invokeResult' => $invokeResult], 'FaceCloud260/selectPassInfoLog', 1);
            }catch (\Exception $e){
                fdump_api(['msg' => '设备主动触发云端导入雇员授权信息', 'err' => $e->getMessage()], 'FaceCloud260/errSelectPassInfoLog');
            }
        } else {
            fdump_api(['msg' => '设备主动触发云端导入雇员授权信息', 'err' => '无参数', 'param' => $_REQUEST], 'FaceCloud260/errSelectPassInfoLog');
        }
        if (! empty($result)) {
            fdump_api(['msg' => '设备主动触发云端导入雇员授权信息-数据', 'result' => $result, 'param' => $postParam], 'FaceCloud260/dataSelectPassInfoLog');
        }
        $this->returnOk($result);
    }

    /**
     * upload-2.3 导入雇员授权信息结果反馈.
     */
    public function setPassResult() {
        $postParam = json_decode(file_get_contents("php://input"), true);
        if (empty($postParam) && !empty($_POST)) {
            $postParam = $_POST;
        }
        if ($postParam) {
            try {
                $param = [
                    'params' => $postParam,
                ];
                fdump_api(['msg' => '导入雇员授权信息结果反馈', '_POST' => $postParam], 'FaceCloud260/setPassResultLog', 1);
                $invokeResult = invoke_v20_service('\app\community\model\service\Device\HouseVillageDeviceCmdService/setPassResult',$param);
                $result = $invokeResult['data']['retval'];
                fdump_api(['msg' => '导入雇员授权信息结果反馈', 'result' => $result], 'FaceCloud260/setPassResultLog', 1);
            }catch (\Exception $e){
                fdump_api(['msg' => '导入雇员授权信息结果反馈', 'err' => $e->getMessage()], 'FaceCloud260/errSetPassResultLog');
            }
        } else {
            fdump_api(['msg' => '导入雇员授权信息结果反馈', 'err' => '无参数', 'param' => $_REQUEST], 'FaceCloud260/errSetPassResultLog');
        }
        $this->returnOk();
    }

    /**
     * download-2.4 设备主动触发云端获取删除雇员信息.
     */
    public function selectDeleteInfo() {
        $postParam = json_decode(file_get_contents("php://input"), true);
        if (empty($postParam) && !empty($_POST)) {
            $postParam = $_POST;
        }
        $result = [];
        if ($postParam) {
            try {
                $param = [
                    'params' => $postParam,
                ];
                $invokeResult = invoke_v20_service('\app\community\model\service\Device\HouseVillageDeviceCmdService/selectDeleteInfo',$param);
                $result = $invokeResult['data']['retval'];
                fdump_api(['msg' => '设备主动触发云端获取删除雇员信息', '_POST' => $postParam, 'result' => $result], 'FaceCloud260/selectDeleteInfoLog', 1);
            }catch (\Exception $e){
                fdump_api(['msg' => '设备主动触发云端获取删除雇员信息', 'err' => $e->getMessage()], 'FaceCloud260/errSelectDeleteInfoLog');
            }
        } else {
            fdump_api(['msg' => '设备主动触发云端获取删除雇员信息', 'err' => '无参数', 'param' => $_REQUEST], 'FaceCloud260/errSelectDeleteInfoLog');
        }
        if (! empty($result)) {
            fdump_api(['msg' => '设备主动触发云端获取删除雇员信息-数据', 'result' => $result, 'param' => $postParam], 'FaceCloud260/dataSelectDeleteInfoLog');
        }
        $this->returnOk($result);
    }

    /**
     * upload-2.5 设备上传当前设备初始参数信息.
     */
    public function inertParameter() {
        $postParam = json_decode(file_get_contents("php://input"), true);
        if (empty($postParam) && !empty($_POST)) {
            $postParam = $_POST;
        }
        if ($postParam) {
            try {
                $param = [
                    'params' => $postParam,
                ];
                $invokeResult = invoke_v20_service('\app\community\model\service\Device\HouseVillageDeviceCmdService/inertParameter',$param);
                $result = $invokeResult['data']['retval'];
                fdump_api(['msg' => '设备上传当前设备初始参数信息', '_POST' => $postParam, 'result' => $result], 'FaceCloud260/inertParameterLog', 1);
            }catch (\Exception $e){
                fdump_api(['msg' => '设备上传当前设备初始参数信息', 'err' => $e->getMessage()], 'FaceCloud260/errInertParameterLog');
            }
        } else {
            fdump_api(['msg' => '设备上传当前设备初始参数信息', 'err' => '无参数', 'param' => $_REQUEST], 'FaceCloud260/errInertParameterLog');
        }
        $this->returnOk(["success" => 0, "msg" => 'OK']);
    }

    /**
     * download-2.6 设备主动触发云端获取设备需要设置的参数信息.
     */
    public function selectParameterInfo() {
        $postParam = json_decode(file_get_contents("php://input"), true);
        if (empty($postParam) && !empty($_POST)) {
            $postParam = $_POST;
        }
        $result = [];
        if ($postParam) {
            try {
                $param = [
                    'params' => $postParam,
                ];
                $invokeResult = invoke_v20_service('\app\community\model\service\Device\HouseVillageDeviceCmdService/selectParameterInfo',$param);
                $result = $invokeResult['data']['retval'];
                fdump_api(['msg' => '设备主动触发云端获取设备需要设置的参数信息', '_POST' => $postParam, 'result' => $result], 'FaceCloud260/selectParameterInfoLog', 1);
            }catch (\Exception $e){
                fdump_api(['msg' => '设备主动触发云端获取设备需要设置的参数信息', 'err' => $e->getMessage()], 'FaceCloud260/errSelectParameterInfoLog');
            }
        } else {
            fdump_api(['msg' => '设备主动触发云端获取设备需要设置的参数信息', 'err' => '无参数', 'param' => $_REQUEST], 'FaceCloud260/errSelectParameterInfoLog');
        }
        if (! empty($result)) {
            fdump_api(['msg' => '设备主动触发云端获取设备需要设置的参数信息-数据', 'result' => $result, 'param' => $postParam], 'FaceCloud260/dataSelectParameterInfoLog');
        }
        $this->returnOk($result);
    }

    /**
     * upload-2.7 验证设备在线状态.
     */
    public function updateStateDevice() {
        $postParam = json_decode(file_get_contents("php://input"), true);
        if (empty($postParam) && !empty($_POST)) {
            $postParam = $_POST;
        }
        if ($postParam) {
            try {
                $param = [
                    'params' => $postParam,
                ];
                $invokeResult = invoke_v20_service('\app\community\model\service\Device\HouseVillageDeviceCmdService/updateStateDevice',$param);
                $result = $invokeResult['data']['retval'];
                fdump_api(['msg' => '验证设备在线状态', '_POST' => $postParam, 'result' => $result], 'FaceCloud260/updateStateDeviceLog', 1);
            }catch (\Exception $e){
                fdump_api(['msg' => '验证设备在线状态', 'err' => $e->getMessage()], 'FaceCloud260/errUpdateStateDeviceLog');
            }
        } else {
            fdump_api(['msg' => '验证设备在线状态', 'err' => '无参数', 'param' => $_REQUEST], 'FaceCloud260/errUpdateStateDeviceLog');
        }
        $this->returnOk(["success" => 0, "msg" => 'OK']);
    }

    /**
     * download-2.8 设备请求服务器特殊管理(重启、恢复出厂设置).
     */
    public function selectRestart() {
        $postParam = json_decode(file_get_contents("php://input"), true);
        if (empty($postParam) && !empty($_POST)) {
            $postParam = $_POST;
        }
        $result = [];
        if ($postParam) {
            try {
                $param = [
                    'params' => $postParam,
                ];
                $invokeResult = invoke_v20_service('\app\community\model\service\Device\HouseVillageDeviceCmdService/selectRestart',$param);
                $result = $invokeResult['data']['retval'];
                fdump_api(['msg' => '设备主动触发云端获取设备需要设置的参数信息', '_POST' => $postParam, 'result' => $result], 'FaceCloud260/selectRestartLog', 1);
            }catch (\Exception $e){
                fdump_api(['msg' => '设备主动触发云端获取设备需要设置的参数信息', 'err' => $e->getMessage()], 'FaceCloud260/errSelectRestartLog');
            }
        } else {
            fdump_api(['msg' => '设备主动触发云端获取设备需要设置的参数信息', 'err' => '无参数', 'param' => $_REQUEST], 'FaceCloud260/errSelectRestartLog');
        }
        if (! empty($result)) {
            fdump_api(['msg' => '设备主动触发云端获取设备需要设置的参数信息-数据', 'result' => $result, 'param' => $postParam], 'FaceCloud260/dataSelectRestartLog');
        }
        $this->returnOk($result);
    }

    /**
     * upload-2.9 设备更新服务器路径结果反馈.
     */
    public function setPathNews() {
        $postParam = json_decode(file_get_contents("php://input"), true);
        if (empty($postParam) && !empty($_POST)) {
            $postParam = $_POST;
        }
        if ($postParam) {
            try {
                $param = [
                    'params' => $postParam,
                ];
                $invokeResult = invoke_v20_service('\app\community\model\service\Device\HouseVillageDeviceCmdService/setPathNews',$param);
                $result = $invokeResult['data']['retval'];
                fdump_api(['msg' => '设备更新服务器路径结果反馈', '_POST' => $postParam, 'result' => $result], 'FaceCloud260/setPathNewsLog', 1);
            }catch (\Exception $e){
                fdump_api(['msg' => '设备更新服务器路径结果反馈', 'err' => $e->getMessage()], 'FaceCloud260/errSetPathNewsLog');
            }
        } else {
            fdump_api(['msg' => '设备更新服务器路径结果反馈', 'err' => '无参数', 'param' => $_REQUEST], 'FaceCloud260/errSetPathNewsLog');
        }
        $this->returnOk(["success" => 0, "msg" => 'OK']);
    }

    /**
     * download-3.0 设备主动上报二维码参数这边反馈是否可以进行开门
     */
    public function qrCodePassOpen() {
        $postParam = json_decode(file_get_contents("php://input"), true);
        if (empty($postParam) && !empty($_POST)) {
            $postParam = $_POST;
        }
        if ($postParam) {
            try {
                $param = [
                    'params' => $postParam,
                ];
                $invokeResult = invoke_v20_service('\app\community\model\service\Device\HouseVillageDeviceCmdService/qrCodePassOpen',$param);
                $result = $invokeResult['data']['retval'];
                fdump_api(['msg' => '设备主动触发云端获取设备需要设置的参数信息', '_POST' => $postParam, 'result' => $result], 'FaceCloud260/qrCodePassOpenLog', 1);
            }catch (\Exception $e){
                fdump_api(['msg' => '设备主动触发云端获取设备需要设置的参数信息', 'err' => $e->getMessage()], 'FaceCloud260/errQrCodePassOpenLog');
                $result = [
                    "success" => 9, "msg" => 'PLATFORM_ERR',
                ];
            }
        } else {
            fdump_api(['msg' => '设备更新服务器路径结果反馈', 'err' => '无参数', 'param' => $_REQUEST], 'FaceCloud260/errQrCodePassOpenLog');
            $result = [
                "success" => 1, "msg" => 'DEVICE_ID_NO_HAVE',
            ];
        }
        $this->returnOk($result);
    }

    /**
     * 统一返回结构.
     */
    private function returnOk($data = []) {
        exit(json_encode($data));
    }
}