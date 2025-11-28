<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      立林社区平台对接
 * v20/public/index.php/community/common.LiLinDevAccess/receiveInfo
 */

namespace app\community\controller\common;

use app\BaseController;
use app\community\model\service\Device\LiLinDevAccessApiService;

class LiLinDevAccessController extends BaseController
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
    
    public function receiveInfo(){

        $inputJson = file_get_contents("php://input");
        $returnArr=array('result'=>1,'message'=>'操作成功','requestId'=>'receive-R' . date('Ymd-His') . '-' . rand(100000, 999999),'data'=>'');
        try{
            fdump_api(['inputJson' => $inputJson], 'LiLinDevAccess/receiveInfo' . date('H') , 1);
            $pdata=$inputJson ? json_decode($inputJson,true) :'';
            if(empty($pdata)|| !isset($pdata['clientId']) || !isset($pdata['neighNo'])){
                $returnArr['result']=400;
                $returnArr['message']='缺少小区号码必要参数！';
                return json($returnArr);
            }
            $neighNo=$pdata['neighNo'];  //小区号
            $clientId=$pdata['clientId'];
            $dataInfo=isset($pdata['data']) ? $pdata['data']:'';
            if(empty($dataInfo) || !isset($dataInfo['pushType'])){
                $returnArr['result']=400;
                $returnArr['message']='缺少推送业务类型参数！';
                return json($returnArr);
            }
            $pushType=intval($dataInfo['pushType']);
            fdump_api(['pushType' =>$pushType,'pdata'=>$pdata], 'LiLinDevAccess/receiveInfo' . date('H') , 1);
            switch ($pushType){
                case 100:
                    //推送设备在离线状态
                    $returnArr=$this->pushDeviceStatus($dataInfo['param'],$neighNo,$clientId);
                    break;
                case 101:
                    //推送门禁记录
                    $returnArr=$this->pushAccessEvent($dataInfo['param'],$neighNo,$clientId);
                    break;
                case 102:
                    //推送对讲记录 
                    //$returnArr=$this->pushCallEvent($dataInfo['param'],$neighNo,$clientId);
                    break;
                case 103:
                    //推送车辆出入记录
                    break;
                case 104:
                    //门锁电量属性
                    break;
                case 105:
                    //门锁信号属性
                    break;
                case 106:
                    //门锁被破坏事件
                    break;
                case 107:
                    //门锁密码连续输错事件
                    break;
                case 200:
                    //小区信息推送
                    break;
                case 201:
                    //片区/楼栋/单元/别墅/地点信息推送
                    $returnArr=$this->pushAddOrEditBuilding($dataInfo['param'],$neighNo,$clientId);
                    break;
                case 202:
                    //房屋信息推送
                    $returnArr=$this->pushAddOrEditHouse($dataInfo['param'],$neighNo,$clientId);
                    break;
                case 203:
                    //删除片区/楼栋/单元/别墅/地点/房屋推送
                    $returnArr=$this->pushDelBuilding($dataInfo['param'],$neighNo,$clientId);
                    break;
                case 204:
                    //设备信息推送
                    $returnArr=$this->pushAddOrEditDevice($dataInfo['param'],$neighNo,$clientId);
                    break;
                case 205:
                    //删除设备信息推送
                    $returnArr=$this->pushDelDevice($dataInfo['param'],$neighNo,$clientId);
                    break;
                case 206:
                    //住户信息推送
                    $returnArr=$this->pushAddOrEditHousehold($dataInfo['param'],$neighNo,$clientId);
                    break;
                case 207:
                    //删除住户信息推送
                    $returnArr=$this->pushDelHousehold($dataInfo['param'],$neighNo,$clientId);
                    break;
                case 208:
                    //访客信息推送
                    $returnArr=$this->pushAddVisitor($dataInfo['param'],$neighNo,$clientId);
                    break;
                case 209:
                    //删除访客信息推送
                    break;
                case 210:
                    //工作人员推送
                    $returnArr=$this->pushAddOrEditWorker($dataInfo['param'],$neighNo,$clientId);
                    break;
                case 211:
                    //删除工作人员推送
                    $returnArr=$this->pushDelWorker($dataInfo['param'],$neighNo,$clientId);
                    break;
                default:
                    fdump_api(['pushType' =>$pushType,'default'=>1], 'LiLinDevAccess/receiveInfo' . date('H') , 1);
            }
        }catch (\Exception $e){
            fdump_api(['_Line_' . __LINE__, 'dataArr' => $e->getMessage(), 'input' => $inputJson], 'LiLinDevAccess/receiveInfo' . date('H') , 1);
        }
        return json($returnArr);
        /*
        echo json_encode($returnArr,JSON_UNESCAPED_UNICODE);
        exit();
        */
    }
    
    /**
     * 新增楼栋
     **/
    public function pushAddOrEditBuilding($pdata=array(),$neighNo='',$clientId='')
    {
        if($neighNo && $pdata && (!isset($pdata['neighNo']) || empty($pdata['neighNo']))){
            $pdata['neighNo']=$neighNo;
        }
        fdump_api(['clientId' => $clientId, 'neighNo' => $neighNo, 'pdata' => $pdata], 'liLinDevAccess/pushAddOrEditBuilding');
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'add-building-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 2;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $liLinDevAccessApiService = new LiLinDevAccessApiService();
            $ret = $liLinDevAccessApiService->pushAddOrEditBuilding($pdata);
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 2;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
            fdump_api([__LINE__, 'ret' =>  $ret, 'returnArr' => $returnArr], 'liLinDevAccess/pushAddOrEditBuilding');
        } catch (\Exception $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushAddOrEditBuilding');
        } catch (\Throwable $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushAddOrEditBuilding');
        }
        return json($returnArr);
    }

    /**
     * 删除楼栋
     **/
    public function pushDelBuilding($pdata=array(),$neighNo='',$clientId='')
    {
        if($neighNo && $pdata && (!isset($pdata['neighNo']) || empty($pdata['neighNo']))){
            $pdata['neighNo']=$neighNo;
        }
        fdump_api(['clientId' => $clientId, 'neighNo' => $neighNo, 'pdata' => $pdata], 'liLinDevAccess/pushDelBuilding');
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'del-building-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 2;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $liLinDevAccessApiService = new LiLinDevAccessApiService();
            $ret = $liLinDevAccessApiService->pushDelBuilding($pdata);
            
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 2;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
            fdump_api([__LINE__, 'ret' =>  $ret, 'returnArr' => $returnArr], 'liLinDevAccess/pushDelBuilding');
        } catch (\Exception $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushDelBuilding');
        } catch (\Throwable $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushDelBuilding');
        }
        return json($returnArr);
    }
    
    /**
     * 新增房屋推送
     **/
    public function pushAddOrEditHouse($pdata=array(),$neighNo='',$clientId='')
    {
        if($neighNo && $pdata && (!isset($pdata['neighNo']) || empty($pdata['neighNo']))){
            $pdata['neighNo']=$neighNo;
        }
        fdump_api(['clientId' => $clientId, 'neighNo' => $neighNo, 'pdata' => $pdata], 'liLinDevAccess/pushAddOrEditBuilding');
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'add-house-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 2;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $liLinDevAccessApiService = new LiLinDevAccessApiService();
            $ret = $liLinDevAccessApiService->pushAddOrEditHouse($pdata);
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 2;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
            fdump_api([__LINE__, 'ret' =>  $ret, 'returnArr' => $returnArr], 'liLinDevAccess/pushAddOrEditBuilding');
        } catch (\Exception $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushAddOrEditBuilding');
        } catch (\Throwable $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushAddOrEditBuilding');
        }
        return json($returnArr);
    }
    
    /**
     * 新增设备推送
     **/
    public function pushAddOrEditDevice($pdata=array(),$neighNo='',$clientId='')
    {
        if($neighNo && $pdata && (!isset($pdata['neighNo']) || empty($pdata['neighNo']))){
            $pdata['neighNo']=$neighNo;
        }
        fdump_api(['clientId' => $clientId, 'neighNo' => $neighNo, 'pdata' => $pdata], 'liLinDevAccess/pushAddOrEditDevice');
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'add-device-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 2;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $liLinDevAccessApiService = new LiLinDevAccessApiService();
            $ret = $liLinDevAccessApiService->pushAddOrEditDevice($pdata);
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 2;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
            fdump_api([__LINE__, 'ret' =>  $ret, 'returnArr' => $returnArr], 'liLinDevAccess/pushAddOrEditDevice');
        } catch (\Exception $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushAddOrEditDevice');
        } catch (\Throwable $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushAddOrEditDevice');
        }
        return json($returnArr);
    }

    /**
     * 删除设备推送
     **/
    public function pushDelDevice($pdata=array(),$neighNo='',$clientId='')
    {
        if($neighNo && $pdata && (!isset($pdata['neighNo']) || empty($pdata['neighNo']))){
            $pdata['neighNo']=$neighNo;
        }
        fdump_api(['clientId' => $clientId, 'neighNo' => $neighNo, 'pdata' => $pdata], 'liLinDevAccess/pushDelDevice');
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'del-device-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 2;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $liLinDevAccessApiService = new LiLinDevAccessApiService();
            $ret = $liLinDevAccessApiService->pushDelDevice($pdata);
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 2;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
            fdump_api([__LINE__, 'ret' =>  $ret, 'returnArr' => $returnArr], 'liLinDevAccess/pushDelDevice');
        } catch (\Exception $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushDelDevice');
        } catch (\Throwable $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushDelDevice');
        }
        return json($returnArr);
    }
    
    /**
     * 新增小区住户人员推送
     **/
    public function pushAddOrEditHousehold($pdata=array(),$neighNo='',$clientId='')
    {
        if($neighNo && $pdata && (!isset($pdata['neighNo']) || empty($pdata['neighNo']))){
            $pdata['neighNo']=$neighNo;
        }
        fdump_api(['clientId' => $clientId, 'neighNo' => $neighNo, 'pdata' => $pdata], 'liLinDevAccess/pushAddOrEditHousehold');
        
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'add-household-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 2;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        if (!isset($pdata['personInfo']) || empty($pdata['personInfo'])) {
            $returnArr['result'] = 2;
            $returnArr['message'] = '请求数据错误，缺少人员信息！';
            return json($returnArr);
        }
        if (!isset($pdata['houseInfo']) || empty($pdata['houseInfo'])) {
            $returnArr['result'] = 2;
            $returnArr['message'] = '请求数据错误，缺少住家信息！';
            return json($returnArr);
        }
        try {
            $liLinDevAccessApiService = new LiLinDevAccessApiService();
            $ret = $liLinDevAccessApiService->pushAddOrEditHousehold($pdata);
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 2;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
            fdump_api([__LINE__, 'ret' =>  $ret, 'returnArr' => $returnArr], 'liLinDevAccess/pushAddOrEditHousehold');
        } catch (\Exception $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushAddOrEditHousehold');
        } catch (\Throwable $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushAddOrEditHousehold');
        }
        return json($returnArr);
    }
    
    /**
     * 删除小区住户人员推送
     **/
    public function pushDelHousehold($pdata=array(),$neighNo='',$clientId='')
    {
        if($neighNo && $pdata && (!isset($pdata['neighNo']) || empty($pdata['neighNo']))){
            $pdata['neighNo']=$neighNo;
        }
        fdump_api(['clientId' => $clientId, 'neighNo' => $neighNo, 'pdata' => $pdata], 'liLinDevAccess/pushDelHousehold');
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'del-household-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 2;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $liLinDevAccessApiService = new LiLinDevAccessApiService();
            $ret = $liLinDevAccessApiService->pushDelHousehold($pdata);
            
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 2;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
            fdump_api([__LINE__, 'ret' =>  $ret, 'returnArr' => $returnArr], 'liLinDevAccess/pushDelHousehold');
        } catch (\Exception $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushDelHousehold');
        } catch (\Throwable $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushDelHousehold');
        }
        return json($returnArr);
    }
    
    /**
     * 新增工作人员推送
     **/
    public function pushAddOrEditWorker($pdata=array(),$neighNo='',$clientId='')
    {
        if($neighNo && $pdata && (!isset($pdata['neighNo']) || empty($pdata['neighNo']))){
            $pdata['neighNo']=$neighNo;
        }
        fdump_api(['clientId' => $clientId, 'neighNo' => $neighNo, 'pdata' => $pdata], 'liLinDevAccess/pushAddOrEditWorker');
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'add-worker-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 2;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $liLinDevAccessApiService = new LiLinDevAccessApiService();
            $ret = $liLinDevAccessApiService->pushAddOrEditWorker($pdata);
            
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 2;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
            fdump_api([__LINE__, 'ret' =>  $ret, 'returnArr' => $returnArr], 'liLinDevAccess/pushAddOrEditWorker');
        } catch (\Exception $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushAddOrEditWorker');
        } catch (\Throwable $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushAddOrEditWorker');
        }
        return json($returnArr);
    }

    /**
     * 删除工作人员推送
     **/
    public function pushDelWorker($pdata=array(),$neighNo='',$clientId='')
    {
        if($neighNo && $pdata && (!isset($pdata['neighNo']) || empty($pdata['neighNo']))){
            $pdata['neighNo']=$neighNo;
        }
        fdump_api(['clientId' => $clientId, 'neighNo' => $neighNo, 'pdata' => $pdata], 'liLinDevAccess/pushDelWorker');
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'del-worker-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 2;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $liLinDevAccessApiService = new LiLinDevAccessApiService();
            $ret = $liLinDevAccessApiService->pushDelWorker($pdata);
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 2;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
            fdump_api([__LINE__, 'ret' =>  $ret, 'returnArr' => $returnArr], 'liLinDevAccess/pushDelWorker');
        } catch (\Exception $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushDelWorker');
        } catch (\Throwable $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushDelWorker');
        }
        return json($returnArr);
    }
    
    /**
     * 访客登记记录推送
     * 不需要对接 未实现
     **/
    public function pushAddVisitor($pdata=array(),$neighNo='',$clientId='')
    {
        if($neighNo && $pdata && (!isset($pdata['neighNo']) || empty($pdata['neighNo']))){
            $pdata['neighNo']=$neighNo;
        }
        fdump_api(['clientId' => $clientId, 'neighNo' => $neighNo, 'pdata' => $pdata], 'liLinDevAccess/pushAddVisitor');
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'add-visitor-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 2;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $liLinDevAccessApiService = new LiLinDevAccessApiService();
            $ret = $liLinDevAccessApiService->pushAddVisitor($pdata);
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 2;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
        } catch (\Exception $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushAddVisitor');
        } catch (\Throwable $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushAddVisitor');
        }
        return json($returnArr);
    }
    
    /**
     * 设备在离线状态推送
     **/
    public function pushDeviceStatus($pdata=array(),$neighNo='',$clientId='')
    {
        if($neighNo && $pdata && (!isset($pdata['neighNo']) || empty($pdata['neighNo']))){
            $pdata['neighNo']=$neighNo;
        }
        fdump_api(['clientId' => $clientId, 'neighNo' => $neighNo, 'pdata' => $pdata], 'liLinDevAccess/pushDeviceStatus');
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'device-status-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 2;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $liLinDevAccessApiService = new LiLinDevAccessApiService();
            $ret = $liLinDevAccessApiService->pushDeviceStatus($pdata);
            
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 2;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
            fdump_api([__LINE__, 'ret' =>  $ret, 'returnArr' => $returnArr], 'liLinDevAccess/pushDeviceStatus');
        } catch (\Exception $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushDeviceStatus');
        } catch (\Throwable $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushDeviceStatus');
        }
        return json($returnArr);
    }

    /**
     * 门禁记录推送
     **/
    public function pushAccessEvent($pdata=array(),$neighNo='',$clientId='')
    {
        if($neighNo && $pdata && (!isset($pdata['neighNo']) || empty($pdata['neighNo']))){
            $pdata['neighNo']=$neighNo;
        }
        $hh=date('H');
        fdump_api(['clientId' => $clientId, 'neighNo' => $neighNo, 'pdata' => $pdata], 'liLinDevAccess/pushAccessEvent'.$hh);
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'access-event-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 2;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        try {
            $liLinDevAccessApiService = new LiLinDevAccessApiService();
            $ret = $liLinDevAccessApiService->pushAccessEvent($pdata);
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 2;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
            fdump_api([__LINE__, 'ret' =>  $ret, 'returnArr' => $returnArr], 'liLinDevAccess/pushAccessEvent'.$hh);
        } catch (\Exception $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushAccessEvent'.$hh);
        } catch (\Throwable $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushAccessEvent'.$hh);
        }
        return json($returnArr);
    }

    /**
     * 对讲记录推送
     * 
     **/
    public function pushCallEvent($pdata=array(),$neighNo='',$clientId='')
    {
        if($neighNo && $pdata && (!isset($pdata['neighNo']) || empty($pdata['neighNo']))){
            $pdata['neighNo']=$neighNo;
        }
        $hh=date('H');
        $returnArr = array('result' => '', 'message' => '', 'requestId' => 'call-event-R' . date('Ymd-His') . '-' . rand(100000, 999999), 'data' => '');
        $returnArr['result'] = 1;
        $returnArr['message'] = '请求处理成功！';
        return json($returnArr);
        fdump_api(['clientId' => $clientId, 'neighNo' => $neighNo, 'pdata' => $pdata], 'liLinDevAccess/pushCallEvent'.$hh);
        if (empty($pdata) || !isset($pdata['neighNo']) || empty($pdata['neighNo'])) {
            $returnArr['result'] = 2;
            $returnArr['message'] = '请求数据错误，缺少小区号码！';
            return json($returnArr);
        }
        if (!isset($pdata['receiverDid']) || empty($pdata['receiverDid'])) {
            $returnArr['result'] = 2;
            $returnArr['message'] = '请求数据错误，缺少被叫设备did！';
            return json($returnArr);
        }
        try {
            /**
            $liLinDevAccessApiService = new LiLinDevAccessApiService();
            $ret = $liLinDevAccessApiService->pushCallEvent($pdata);
            **/
            if ($ret['err_code'] == 1) {
                $returnArr['result'] = 2;
                $returnArr['message'] = $ret['err_msg'];
            } else {
                $returnArr['result'] = 1;
                $returnArr['message'] = '请求处理成功！';
            }
            fdump_api([__LINE__, 'ret' =>  $ret, 'returnArr' => $returnArr], 'liLinDevAccess/pushCallEvent'.$hh);
        } catch (\Exception $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Exception', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushCallEvent'.$hh);
        } catch (\Throwable $e) {
            $returnArr['result'] = 2;
            $returnArr['message'] = $e->getMessage();
            fdump_api(['xtype' => 'Throwable', 'err_msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'liLinDevAccess/pushCallEvent'.$hh);
        }
        return json($returnArr);
    }
}