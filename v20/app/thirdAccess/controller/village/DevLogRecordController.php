<?php


namespace app\thirdAccess\controller\village;

use app\common\controller\CommonBaseController;
use app\thirdAccess\model\service\DevLogRecordService;
use think\App;
class DevLogRecordController extends CommonBaseController
{
    public $checkErrData='';
    public function __construct(App $app)
    {
        parent::__construct($app);
        $thirdBusinessType=$this->request->thirdBusinessType;
        $thirdBusinessId= $this->request->thirdBusinessId;
        $thirdType=$this->request->thirdType;
        $thirdSite=$this->request->thirdSite;
        $secretCheckId=$this->request->secretCheckId;
        /***
        ***INSERT INTO `pigcms_house_third_config`(`checkId`, `businessType`, `businessId`, `thirdType`, `thirdSite`, `thirdName`, `thirdId`, `thirdAppid`, `thirdAppkey`, `addTime`, `updateTime`, `status`, `thirdFid`, `thirdData`, `checkData`) VALUES 
         *('7d9278ce513a574d447c8e6ba434a0a3', 'devLogRecordApi', 'devLogRecordApi', 'shequHanglan', 'shequ.hanglan.com', '', '0', 'hl10000333', 'a7i20PFYMN7l9QCyrDd3', 0, 0, 1, 0, '', '');
         ***/
        if(empty($thirdBusinessType) || empty($thirdBusinessId) || empty($thirdType) || empty($secretCheckId)){
            $this->checkErrData =  api_output_error('1001', '您无权访问！');
            return $this->checkErrData;
        }
        $appid = $this->request->post('appid','','trim');
        $appkey = $this->request->post('appkey','','trim');
        $thirdPostType = $this->request->post('thirdType','','trim');
        if (empty($thirdPostType) || $thirdType!=$thirdPostType) {
            $this->checkErrData = api_output_error('1001', '类型错误，无权访问！');
            return $this->checkErrData;
        }
        $md5=$appid.$thirdType.$thirdBusinessType.$appkey;
        $md5Str=md5($md5);
        if($md5Str!=$secretCheckId){
            $this->checkErrData = api_output_error('1001', '您无权访问，appkey已过期！');
            return $this->checkErrData;
        }
    }
    
    public function villageLists() {
        if($this->checkErrData){
            return $this->checkErrData;
        }
        $property_id = $this->request->post('property_id',0,'intval');
        $page = $this->request->post('page',1,'intval');
        $limit = $this->request->post('limit',20,'intval');
        $limit=$limit>100 ? 100:$limit;
        $paramData = [
            'property_id' => $property_id,
            'page' => $page,
            'limit' => $limit,
        ];
        $devLogRecordService = new DevLogRecordService();
        try {
            $dataRet = $devLogRecordService->getVillageData($paramData);
        } catch (\Exception $e) {
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$dataRet);
    }
    
    /***
     **获取人脸门禁设备
     *****/
    public function getFaceDoorList() {
        if($this->checkErrData){
           return $this->checkErrData;
        }
        $property_id = $this->request->post('property_id',0,'intval');
        $village_id = $this->request->post('village_id',0,'intval');
        
        $page = $this->request->post('page',1,'intval');
        $limit = $this->request->post('limit',20,'intval');
        $limit=$limit>100 ? 100:$limit;
        $paramData = [
            'property_id' => $property_id,
            'village_id' =>  $village_id,
            'page' => $page,
            'limit' => $limit,
        ];
        $devLogRecordService = new DevLogRecordService();
        try {
            $dataRet = $devLogRecordService->getFaceDoorList($paramData);
        } catch (\Exception $e) {
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$dataRet);
    }
    
    /***
    **获取人脸进出记录
     *****/
    public function getFaceDoorLogRecord() {
        if($this->checkErrData){
            return $this->checkErrData;
        }
        $village_id = $this->request->post('village_id',0,'intval');
        $device_sn = $this->request->post('device_sn','','trim');
        $device_id = $this->request->post('device_id',0,'intval');
        $xstatus = $this->request->post('xstatus',1,'intval');  //0全部 1是成功 2失败
        $start_date = $this->request->post('start_date','','trim');
        $end_date = $this->request->post('end_date','','trim');

        $page = $this->request->post('page',1,'intval');
        $limit = $this->request->post('limit',20,'intval');
        $limit=$limit>100 ? 100:$limit;
        $paramData = [
            'village_id' =>  $village_id,
            'device_sn'=>$device_sn,
            'device_id'=>$device_id,
            'xstatus'=>$xstatus,
            'start_date'=>$start_date,
            'end_date'=>$end_date,
            'page' => $page,
            'limit' => $limit,
        ];
        $devLogRecordService = new DevLogRecordService();
        try {
            $dataRet = $devLogRecordService->getFaceDoorLogRecord($paramData);
        } catch (\Exception $e) {
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$dataRet);
    }

    /***
     **获取停车进出记录
     *****/
    public function getParkCarAccessRecord() {
        if($this->checkErrData){
            return $this->checkErrData;
        }
        $village_id = $this->request->post('village_id',0,'intval');
        $car_number = $this->request->post('car_number','','trim');
        $date_type = $this->request->post('date_type',1,'intval');  // 1进场 2出场
        $start_date = $this->request->post('start_date','','trim');
        $end_date = $this->request->post('end_date','','trim');
        $park_type = $this->request->post('park_type',0,'intval');  //0全部 1以月租车通行的记录  2以临时车通行的记录
        
        $page = $this->request->post('page',1,'intval');
        $limit = $this->request->post('limit',20,'intval');
        $limit=$limit>100 ? 100:$limit;
        $paramData = [
            'village_id' => $village_id,
            'car_number'=>$car_number,
            'park_type'=>$park_type,
            'date_type'=>$date_type>0 ? $date_type:1,
            'start_date'=>$start_date,
            'end_date'=>$end_date,
            'page' => $page,
            'limit' => $limit,
        ];
        $devLogRecordService = new DevLogRecordService();
        try {
            $dataRet = $devLogRecordService->getParkCarAccessRecord($paramData);
        } catch (\Exception $e) {
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$dataRet);
    }
}