<?php
/**
 * This file is part of Kuaijing Bailing.
 *
 * @link     https://www.kuaijingai.com
 * @document https://help.kuaijingai.com
 * @contact  www.kuaijingai.com 7*12 9:00-21:00
 */

namespace app\community\controller\device;

use app\community\controller\CommunityBaseController;
use app\community\model\service\Device\HouseVillageDeviceCmdService;

class FaceCloud260Controller extends CommunityBaseController
{
    /**
     * upload-2.1 设备主动往云端实时上传打卡记录.
     */
    public function insertNoteFace() {
        if ($_POST) {
            try {
                $result = (new HouseVillageDeviceCmdService())->insertNoteFace($_POST);
                fdump_api(['msg' => '设备主动往云端实时上传打卡记录', 'result' => $result], 'FaceCloud260/insertNoteFaceLog', 1);
            }catch (\Exception $e){
                fdump_api(['msg' => '设备主动往云端实时上传打卡记录', 'err' => $e->getMessage()], 'FaceCloud260/errInsertNoteFaceLog');
            }
        }
        $this->returnOk();
    }

    /**
     * download-2.2 设备主动触发云端导入雇员授权信息.
     */
    public function selectPassInfo() {
        $result = [ "success" => 0, "msg" => 'OK'];
        if ($_POST) {
            try {
                $result = (new HouseVillageDeviceCmdService())->selectPassInfo($_POST);
                fdump_api(['msg' => '设备主动触发云端导入雇员授权信息', 'result' => $result], 'FaceCloud260/selectPassInfoLog', 1);
            }catch (\Exception $e){
                fdump_api(['msg' => '设备主动触发云端导入雇员授权信息', 'err' => $e->getMessage()], 'FaceCloud260/errSelectPassInfoLog');
            }
        }
        $this->returnOk($result);
    }

    /**
     * upload-2.3 导入雇员授权信息结果反馈.
     */
    public function setPassResult() {
        if ($_POST) {
            try {
                $result = (new HouseVillageDeviceCmdService())->setPassResult($_POST);
                fdump_api(['msg' => '导入雇员授权信息结果反馈', 'result' => $result], 'FaceCloud260/setPassResultLog', 1);
            }catch (\Exception $e){
                fdump_api(['msg' => '导入雇员授权信息结果反馈', 'err' => $e->getMessage()], 'FaceCloud260/errSetPassResultLog');
            }
        }
        $this->returnOk();
    }

    /**
     * download-2.4 设备主动触发云端获取删除雇员信息.
     */
    public function selectDeleteInfo() {
        $result = [ "success" => 0, "msg" => 'OK'];
        if ($_POST) {
            try {
                $result = (new HouseVillageDeviceCmdService())->selectDeleteInfo($_POST);
                fdump_api(['msg' => '设备主动触发云端获取删除雇员信息', 'result' => $result], 'FaceCloud260/selectDeleteInfoLog', 1);
            }catch (\Exception $e){
                fdump_api(['msg' => '设备主动触发云端获取删除雇员信息', 'err' => $e->getMessage()], 'FaceCloud260/errSelectDeleteInfoLog');
            }
        }
        $this->returnOk($result);
    }

    /**
     * upload-2.5 设备上传当前设备初始参数信息.
     */
    public function inertParameter() {
        if ($_POST) {
            try {
                $result = (new HouseVillageDeviceCmdService())->inertParameter($_POST);
                fdump_api(['msg' => '设备上传当前设备初始参数信息', 'result' => $result], 'FaceCloud260/inertParameterLog', 1);
            }catch (\Exception $e){
                fdump_api(['msg' => '设备上传当前设备初始参数信息', 'err' => $e->getMessage()], 'FaceCloud260/errInertParameterLog');
            }
        }
        $this->returnOk();
    }
    
    /**
     * download-2.6 设备主动触发云端获取设备需要设置的参数信息.
     */
    public function selectParameterInfo() {
        $result = [ "success" => 0, "msg" => 'OK'];
        if ($_POST) {
            try {
                $result = (new HouseVillageDeviceCmdService())->selectParameterInfo($_POST);
                fdump_api(['msg' => '设备主动触发云端获取设备需要设置的参数信息', 'result' => $result], 'FaceCloud260/selectParameterInfoLog', 1);
            }catch (\Exception $e){
                fdump_api(['msg' => '设备主动触发云端获取设备需要设置的参数信息', 'err' => $e->getMessage()], 'FaceCloud260/errSelectParameterInfoLog');
            }
        }
        $this->returnOk($result);
    }

    /**
     * upload-2.7 验证设备在线状态.
     */
    public function updateStateDevice() {
        if ($_POST) {
            try {
                $result = (new HouseVillageDeviceCmdService())->updateStateDevice($_POST);
                fdump_api(['msg' => '验证设备在线状态', 'result' => $result], 'FaceCloud260/updateStateDeviceLog', 1);
            }catch (\Exception $e){
                fdump_api(['msg' => '验证设备在线状态', 'err' => $e->getMessage()], 'FaceCloud260/errUpdateStateDeviceLog');
            }
        }
        $this->returnOk();
    }

    /**
     * download-2.8 设备请求服务器特殊管理(重启、恢复出厂设置).
     */
    public function selectRestart() {
        $result = [ "success" => 0, "msg" => 'OK'];
        if ($_POST) {
            try {
                $result = (new HouseVillageDeviceCmdService())->selectRestart($_POST);
                fdump_api(['msg' => '设备主动触发云端获取设备需要设置的参数信息', 'result' => $result], 'FaceCloud260/selectRestartLog', 1);
            }catch (\Exception $e){
                fdump_api(['msg' => '设备主动触发云端获取设备需要设置的参数信息', 'err' => $e->getMessage()], 'FaceCloud260/errSelectRestartLog');
            }
        }
        $this->returnOk($result);
    }

    /**
     * upload-2.9 设备更新服务器路径结果反馈.
     */
    public function setPathNews() {
        if ($_POST) {
            try {
                $result = (new HouseVillageDeviceCmdService())->setPathNews($_POST);
                fdump_api(['msg' => '设备更新服务器路径结果反馈', 'result' => $result], 'FaceCloud260/setPathNewsLog', 1);
            }catch (\Exception $e){
                fdump_api(['msg' => '设备更新服务器路径结果反馈', 'err' => $e->getMessage()], 'FaceCloud260/errSetPathNewsLog');
            }
        }
        $this->returnOk();
    }

    /**
     * download-3.0 设备主动上报二维码参数这边反馈是否可以进行开门
     */
    public function qrCodePassOpen() {
        $result = [ "success" => 0, "msg" => 'OK'];
        if ($_POST) {
            try {
                $result = (new HouseVillageDeviceCmdService())->qrCodePassOpen($_POST);
                fdump_api(['msg' => '设备主动触发云端获取设备需要设置的参数信息', 'result' => $result], 'FaceCloud260/qrCodePassOpenLog', 1);
            }catch (\Exception $e){
                fdump_api(['msg' => '设备主动触发云端获取设备需要设置的参数信息', 'err' => $e->getMessage()], 'FaceCloud260/errQrCodePassOpenLog');
                $result = [
                    "success" => 9, "msg" => 'PLATFORM_ERR',
                ];
            }
        }
        $this->returnOk($result);
    }

    /**
     * 统一返回结构.
     */
    private function returnOk($data = []) {
        if (empty($data)) {
            $data = ['success' => 0, 'msg' => 'OK'];
        }
        echo json_encode($data);
        exit;
    }
}