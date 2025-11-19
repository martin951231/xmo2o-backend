<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      立林社区平台对接
 * v20/public/index.php/community/common.LiLinAccess/index
 */

namespace app\community\controller\common;

use app\BaseController;
use app\community\model\service\HouseVillageLilinPushRecordService;

class LiLinAccessController extends BaseController
{
    public function index()
    {
        echo '欢迎对接立林社区平台';
        /*** result返回码说明 ***/
        $resultCode = array(
            '0' => '请求失败',
            '1' => '成功',
            '403' => '签名认证失败',
            '4003' => '请求参数错误',
            '4004' => '设备不存在',
            '4005' => '住家不存在',
            '4006' => '卡号已经存在',
            '4007' => '该卡不存在',
            '4009' => '人脸质量校验失败',
            '4011' => '删除设备卡接口不能用来删除住家卡',
            '4012' => '删除住家卡接口不能用来删除设备卡',
            '4013' => '该卡为人员卡，不能通过该接口进行删除！',
            '4014' => '住家底下不存在该卡！',
            '4015' => '动态密码已存在',
        );
    }
	public function imgFileDownload(){
	    try {
            $houseVillageLilinPushRecordService = new HouseVillageLilinPushRecordService();
			$pdata=array('filePath'=>'/file/record/access/2025-09-13/000110011cb8ba1a34ec_1757730401239.jpg','type'=>1,'fileName'=>'000110011cb8ba1a34ec_1757730401239.jpg');
            $ret = $houseVillageLilinPushRecordService->imgFileDownload($pdata);
        } catch (\Exception $e) {
       
           echo $e->getMessage();
         
        }
	}
    /**
     * 新增楼栋
     **/
    public function pushAddBuilding()
    {
        $pdata = $this->request->post('data', '');
        if (!empty($pdata)) {
            $pdata = json_decode($pdata, true);
        }
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'add-building-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $houseVillageLilinPushRecordService = new HouseVillageLilinPushRecordService();
            $ret = $houseVillageLilinPushRecordService->pushAddOrEditBuilding($pdata,'add');
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 0;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
        } catch (\Exception $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushAddBuilding');
        } catch (\Throwable $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushAddBuilding');
        }
        return json($returnArr);
    }

    /**
     * 删除楼栋
     **/
    public function pushDelBuilding()
    {
        $pdata = $this->request->post('data', '');
        if (!empty($pdata)) {
            $pdata = json_decode($pdata, true);
        }
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'del-building-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $houseVillageLilinPushRecordService = new HouseVillageLilinPushRecordService();
            $ret = $houseVillageLilinPushRecordService->pushDelBuilding($pdata);
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 0;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
        } catch (\Exception $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushDelBuilding');
        } catch (\Throwable $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushDelBuilding');
        }
        return json($returnArr);
    }


    /**
     * 编辑楼栋
     **/
    public function pushEditBuilding()
    {
        $pdata = $this->request->post('data', '');
        if (!empty($pdata)) {
            $pdata = json_decode($pdata, true);
        }
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'edit-building-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $houseVillageLilinPushRecordService = new HouseVillageLilinPushRecordService();
            $ret = $houseVillageLilinPushRecordService->pushAddOrEditBuilding($pdata,'edit');
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 0;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
        } catch (\Exception $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushEditBuilding');
        } catch (\Throwable $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushEditBuilding');
        }
        return json($returnArr);
    }

    /**
     * 新增房屋推送
     **/
    public function pushAddHouse()
    {
        $pdata = $this->request->post('data', '');
        if (!empty($pdata)) {
            $pdata = json_decode($pdata, true);
        }
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'add-house-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $houseVillageLilinPushRecordService = new HouseVillageLilinPushRecordService();
            $ret = $houseVillageLilinPushRecordService->pushAddOrEditHouse($pdata,'add');
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 0;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
        } catch (\Exception $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushAddHouse');
        } catch (\Throwable $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushAddHouse');
        }
        return json($returnArr);
    }

    /**
     * 删除房屋推送
     **/
    public function pushDelHouse()
    {
        $pdata = $this->request->post('data', '');
        if (!empty($pdata)) {
            $pdata = json_decode($pdata, true);
        }
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'del-house-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $houseVillageLilinPushRecordService = new HouseVillageLilinPushRecordService();
            $ret = $houseVillageLilinPushRecordService->pushDelHouse($pdata);
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 0;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
        } catch (\Exception $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushDelHouse');
        } catch (\Throwable $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushDelHouse');
        }
        return json($returnArr);
    }
    
    /**
     * 编辑房屋推送
     **/
    public function pushEditHouse()
    {
        $pdata = $this->request->post('data', '');
        if (!empty($pdata)) {
            $pdata = json_decode($pdata, true);
        }
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'edit-house-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $houseVillageLilinPushRecordService = new HouseVillageLilinPushRecordService();
            $ret = $houseVillageLilinPushRecordService->pushAddOrEditHouse($pdata);
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 0;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
        } catch (\Exception $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushEditHouse');
        } catch (\Throwable $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushEditHouse');
        }
        return json($returnArr);
    }


    /**
     * 新增设备推送
     **/
    public function pushAddDevice()
    {
        $pdata = $this->request->post('data', '');
        if (!empty($pdata)) {
            $pdata = json_decode($pdata, true);
        }
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'add-device-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $houseVillageLilinPushRecordService = new HouseVillageLilinPushRecordService();
            $ret = $houseVillageLilinPushRecordService->pushAddOrEditDevice($pdata,'add');
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 0;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
        } catch (\Exception $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushAddDevice');
        } catch (\Throwable $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushAddDevice');
        }
        return json($returnArr);
    }

    /**
     * 删除设备推送
     **/
    public function pushDelDevice()
    {
        $pdata = $this->request->post('data', '');
        if (!empty($pdata)) {
            $pdata = json_decode($pdata, true);
        }
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'del-device-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $houseVillageLilinPushRecordService = new HouseVillageLilinPushRecordService();
            $ret = $houseVillageLilinPushRecordService->pushDelDevice($pdata);
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 0;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
        } catch (\Exception $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushDelDevice');
        } catch (\Throwable $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushDelDevice');
        }
        return json($returnArr);
    }
    
    /**
     * 编辑设备推送
     **/
    public function pushEditDevice()
    {
        $pdata = $this->request->post('data', '');
        if (!empty($pdata)) {
            $pdata = json_decode($pdata, true);
        }
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'edit-device-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $houseVillageLilinPushRecordService = new HouseVillageLilinPushRecordService();
            $ret = $houseVillageLilinPushRecordService->pushAddOrEditDevice($pdata,'edit');
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 0;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
        } catch (\Exception $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushEditDevice');
        } catch (\Throwable $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushEditDevice');
        }
        return json($returnArr);
    }

    /**
     * 新增小区住户人员推送
     **/
    public function pushAddHousehold()
    {
        $pdata = $this->request->post('data', '');
        if (!empty($pdata)) {
            $pdata = json_decode($pdata, true);
        }
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'add-household-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        if (!isset($pdata['personInfo']) || empty($pdata['personInfo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少人员信息！';
            return json($returnArr);
        }
        if (!isset($pdata['houseInfo']) || empty($pdata['houseInfo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少住家信息！';
            return json($returnArr);
        }
        try {
            $houseVillageLilinPushRecordService = new HouseVillageLilinPushRecordService();
            $ret = $houseVillageLilinPushRecordService->pushAddOrEditHousehold($pdata,'add');
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 0;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
        } catch (\Exception $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushAddHousehold');
        } catch (\Throwable $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushAddHousehold');
        }
        return json($returnArr);
    }
    
    /**
     * 删除小区住户人员推送
     **/
    public function pushDelHousehold()
    {
        $pdata = $this->request->post('data', '');
        if (!empty($pdata)) {
            $pdata = json_decode($pdata, true);
        }
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'del-household-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $houseVillageLilinPushRecordService = new HouseVillageLilinPushRecordService();
            $ret = $houseVillageLilinPushRecordService->pushDelHousehold($pdata);
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 0;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
        } catch (\Exception $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushDelHousehold');
        } catch (\Throwable $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushDelHousehold');
        }
        return json($returnArr);
    }

    /**
     * 编辑小区住户人员推送
     **/
    public function pushEditHousehold()
    {
        $pdata = $this->request->post('data', '');
        if (!empty($pdata)) {
            $pdata = json_decode($pdata, true);
        }
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'edit-household-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        if (!isset($pdata['personInfo']) || empty($pdata['personInfo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少人员信息！';
            return json($returnArr);
        }
        if (!isset($pdata['houseInfo']) || empty($pdata['houseInfo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少住家信息！';
            return json($returnArr);
        }
        try {
            $houseVillageLilinPushRecordService = new HouseVillageLilinPushRecordService();
            $ret = $houseVillageLilinPushRecordService->pushAddOrEditHousehold($pdata,'edit');
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 0;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
        } catch (\Exception $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushEditHousehold');
        } catch (\Throwable $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushEditHousehold');
        }
        return json($returnArr);
    }

    /**
     * 新增工作人员推送
     **/
    public function pushAddWorker()
    {
        $pdata = $this->request->post('data', '');
        if (!empty($pdata)) {
            $pdata = json_decode($pdata, true);
        }
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'add-worker-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        if (!isset($pdata['personInfo']) || empty($pdata['personInfo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少人员信息！';
            return json($returnArr);
        }
        try {
            $houseVillageLilinPushRecordService = new HouseVillageLilinPushRecordService();
            $ret = $houseVillageLilinPushRecordService->pushAddOrEditWorker($pdata,'add');
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 0;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
        } catch (\Exception $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushAddWorker');
        } catch (\Throwable $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushAddWorker');
        }
        return json($returnArr);
    }

    /**
     * 删除工作人员推送
     **/
    public function pushDelWorker()
    {
        $pdata = $this->request->post('data', '');
        if (!empty($pdata)) {
            $pdata = json_decode($pdata, true);
        }
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'del-worker-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $houseVillageLilinPushRecordService = new HouseVillageLilinPushRecordService();
            $ret = $houseVillageLilinPushRecordService->pushDelWorker($pdata);
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 0;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
        } catch (\Exception $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushDelWorker');
        } catch (\Throwable $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushDelWorker');
        }
        return json($returnArr);
    }

    /**
     * 编辑工作人员推送
     **/
    public function pushEditWorker()
    {
        $pdata = $this->request->post('data', '');
        if (!empty($pdata)) {
            $pdata = json_decode($pdata, true);
        }
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'edit-worker-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $houseVillageLilinPushRecordService = new HouseVillageLilinPushRecordService();
            $ret = $houseVillageLilinPushRecordService->pushAddOrEditWorker($pdata,'edit');
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 0;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
        } catch (\Exception $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushEditWorker');
        } catch (\Throwable $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushEditWorker');
        }
        return json($returnArr);
    }

    /**
     * 访客登记记录推送
     **/
    public function pushAddVisitor()
    {
        $pdata = $this->request->post('data', '');
        if (!empty($pdata)) {
            $pdata = json_decode($pdata, true);
        }
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'add-visitor-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $houseVillageLilinPushRecordService = new HouseVillageLilinPushRecordService();
            $ret = $houseVillageLilinPushRecordService->pushAddVisitor($pdata);
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 0;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
        } catch (\Exception $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushAddVisitor');
        } catch (\Throwable $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushAddVisitor');
        }
        return json($returnArr);
    }
    
    /**
     * 设备在离线状态推送
     **/
    public function pushDeviceStatus()
    {
        $pdata = $this->request->post('data', '');
        if (!empty($pdata)) {
            $pdata = json_decode($pdata, true);
        }
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'device-status-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $houseVillageLilinPushRecordService = new HouseVillageLilinPushRecordService();
            $ret = $houseVillageLilinPushRecordService->pushDeviceStatus($pdata);
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 0;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
        } catch (\Exception $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushDeviceStatus');
        } catch (\Throwable $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushDeviceStatus');
        }
        return json($returnArr);
    }

    /**
     * 门禁记录推送
     **/
    public function pushAccessEvent()
    {
        $pdata = $this->request->post('data', '');
        if (!empty($pdata)) {
            $pdata = json_decode($pdata, true);
        }
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'access-event-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $houseVillageLilinPushRecordService = new HouseVillageLilinPushRecordService();
            $ret = $houseVillageLilinPushRecordService->pushAccessEvent($pdata);
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 0;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
        } catch (\Exception $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushAccessEvent');
        } catch (\Throwable $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushAccessEvent');
        }
        return json($returnArr);
    }

    /**
     * 对讲记录推送
     **/
    public function pushCallEvent()
    {
        $pdata = $this->request->post('data', '');
        if (!empty($pdata)) {
            $pdata = json_decode($pdata, true);
        }
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'call-event-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        if (!isset($pdata['dstNo']) || empty($pdata['dstNo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少接收对讲的住户号码！';
            return json($returnArr);
        }
        try {
            $houseVillageLilinPushRecordService = new HouseVillageLilinPushRecordService();
            $ret = $houseVillageLilinPushRecordService->pushCallEvent($pdata);  
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 0;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
        } catch (\Exception $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushCallEvent');
        } catch (\Throwable $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushCallEvent');
        }
        return json($returnArr);
    }

    /**
     * 门状态信息推送
     **/
    public function pushDoorStateEvent()
    {
        $pdata = $this->request->post('data', '');
        if (!empty($pdata)) {
            $pdata = json_decode($pdata, true);
        }
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'door-state-event-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $houseVillageLilinPushRecordService = new HouseVillageLilinPushRecordService();
            $ret = $houseVillageLilinPushRecordService->pushDoorStateEvent($pdata);
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 0;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
        } catch (\Exception $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushDoorStateEvent');
        } catch (\Throwable $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushDoorStateEvent');
        }
        return json($returnArr);
    }

    /**
     * 设备报警信息推送
     **/
    public function pushDeviceAlarmEvent()
    {
        $pdata = $this->request->post('data', '');
        if (!empty($pdata)) {
            $pdata = json_decode($pdata, true);
        }
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'device-alarm-event-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $houseVillageLilinPushRecordService = new HouseVillageLilinPushRecordService();
            $ret = $houseVillageLilinPushRecordService->pushDeviceAlarmEvent($pdata);
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 0;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
        } catch (\Exception $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushDeviceAlarmEvent');
        } catch (\Throwable $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushDeviceAlarmEvent');
        }
        return json($returnArr);
    }

    /**
     * 图片推送 
     * 图片base64数据
     **/
    public function pushPhoto()
    {
        $pdata = $this->request->post('data', '');
        if (!empty($pdata)) {
            $pdata = json_decode($pdata, true);
        }
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'photo-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 0;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $houseVillageLilinPushRecordService = new HouseVillageLilinPushRecordService();
            $ret = $houseVillageLilinPushRecordService->pushPhoto($pdata);
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 0;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
        } catch (\Exception $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushPhoto');
        } catch (\Throwable $e) {
            $returnArr['result'] = 0;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinAccess/pushPhoto');
        }
        return json($returnArr);
    }
}