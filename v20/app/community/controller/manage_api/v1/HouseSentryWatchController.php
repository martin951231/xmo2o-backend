<?php
/**
 * 小区岗亭
 **/

namespace app\community\controller\manage_api\v1;
use app\community\controller\manage_api\BaseController;
use app\community\model\service\HouseNewParkingService;
use app\community\model\service\HouseNewPorpertyService;
use app\community\model\service\HouseVillageParkingService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageCarWorkerRecordService;

class HouseSentryWatchController extends BaseController
{
    public $is_opt_sentry=0;
    public $loginType=0;
    public $property_id=0;
    public $village_id=0;
    public $login_name=0;
    public $wid=0;
    public $admin_id=0;  //property_admin表id
    public $sentry_info='';
    public $current_village_name='';
    public $current_village_id=0;
    public  $work_info=array();
    public function initialize()
    {
        parent::initialize();
        $logInfo = $this->getLoginInfo();
        if (6==$this->login_role) {
            $this->wid = $this->_uid;
        }else{
            return api_output(1000, array('err_code'=>1,'login_code'=>1,'msg'=>'登录角色类型错误！'));
        }
        if($this->wid<1){
            return api_output(1000, array('err_code'=>1,'login_code'=>1,'msg'=>'登录角色类型错误，请重新登录！'));
        }
        $current_village_id = $this->request->post('current_village_id', 0, 'intval');
        $this->loginType=1; //小区
        $this->village_id= $logInfo['user']['village_id'];
        $this->current_village_id=$current_village_id;
        $this->current_village_name= $logInfo['user']['village_name'];
        if($this->village_id<1){
            return api_output(1000, array('err_code'=>1,'login_code'=>1,'msg'=>'登录小区数据错误！'));
        }
        $methodName = $this->request->action();
        $data = $this->getSentryLoginInfo($methodName);
        if($methodName!='getSentryInfo' && empty($data['sentry_info'])){
            return api_output(1000, array('err_code'=>1,'login_code'=>1,'msg'=>'登录信息错误，请重新登录！'));
        }
        if($methodName!='getSentryInfo' && !empty($data['sentry_info']) && $data['sentry_info']['is_login']<1){
            return api_output(1000, array('err_code'=>1,'login_code'=>1,'msg'=>'登录失效了，请重新登录！'));
        }
        if(empty($this->work_info) || $this->work_info['is_sentry']<1 || $this->work_info['xtype']!=2 || $this->work_info['relation_id']<1){
            return api_output(1000, array('err_code'=>1,'login_code'=>1,'msg'=>'您没有管理岗亭的权限！'));
        }
    }
    public function getSentryInfo(){
        $current_village_id = $this->request->post('current_village_id', 0, 'intval');
        $is_login = $this->request->post('is_login', 0, 'intval');
        $data = $this->getSentryLoginInfo();
        $data['current_village_id']=$this->village_id;
        $data['current_village_name']=$this->current_village_name;
        if($is_login==1){
            try {
                $carWorkerRecordService= new HouseVillageCarWorkerRecordService();
                $ret= $carWorkerRecordService->handleCurrentLoginSentry($this->loginType,$this->login_role,$this->work_info,'wap_manage');
            } catch (\Exception $e) {
                return api_output_error(-1, $e->getMessage());
            }
        }
        return api_output(1000,$data);
    }
    public function getSentryLoginInfo($methodName=''){
        $carWorkerRecordService= new HouseVillageCarWorkerRecordService();
        $whereArr=array('wid'=>$this->wid,'village_id'=>$this->village_id);
        $this->work_info = $carWorkerRecordService->getOneWorker($whereArr,'*');
        $this->login_name=$this->work_info && $this->work_info['name'] ? $this->work_info['name'] : $this->work_info['account'];
        $this->is_opt_sentry=1;
        $loginSentry=array();
        
        if($methodName!='getSentryInfo'){
            //检查账号登录状态
            $carWorkerRecordService= new HouseVillageCarWorkerRecordService();
            $loginSentry= $carWorkerRecordService->checkCurrentLoginSentry($this->loginType,$this->login_role,$this->work_info,'wap_manage');
        }
        $this->sentry_info=$loginSentry;
        $data=array('loginType'=>$this->loginType,'login_role'=>$this->login_role,'is_opt_sentry'=>$this->is_opt_sentry,'village_id'=>$this->village_id,'wid'=>$this->wid,'property_id'=>0,'login_name'=>$this->login_name,'admin_id'=>$this->admin_id);
        $data['sentry_info']=$loginSentry;
        return $data;
    }
    
    /**
     * 统计信息
     */
    public function getCurrentSentryStatistics()
    {
        $current_village_id = $this->current_village_id>0 ? $this->current_village_id:$this->village_id;
        try {
            $carWorkerRecordService= new HouseVillageCarWorkerRecordService();
            $sentry_info=$carWorkerRecordService->getCurrentSentryStatistics($current_village_id,$this->loginType,$this->sentry_info,true);
            $carParkInfo=$carWorkerRecordService->getCurrentCarStatistics($current_village_id,'wap_manage');
            $sentry_info['car_park_info']=$carParkInfo;
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$sentry_info);
    }
    
    public function getCarAccessRecordList()
    {
        $current_village_id = $this->current_village_id>0 ? $this->current_village_id:$this->village_id;
        $page= $this->request->param('page');//标签id
        $page=$page>0 ? $page:1;
        $limit=$this->request->param('pageSize',10,'intval');
        try {
            $carWorkerRecordService= new HouseVillageCarWorkerRecordService();
            $car_access_record=$carWorkerRecordService->getCarAccessRecord($current_village_id,$page,$limit);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$car_access_record);
    }

    public function getCarParkPassages(){
        $current_village_id = $this->current_village_id>0 ? $this->current_village_id:$this->village_id;

        try {
            $carWorkerRecordService= new HouseVillageCarWorkerRecordService();
            $car_access_record=$carWorkerRecordService->getCarParkPassages($current_village_id,'wap_manage');
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$car_access_record);
    }

    public function getCurrentPassagePlateresult(){
        $current_village_id = $this->current_village_id>0 ? $this->current_village_id:$this->village_id;
        $passage_id = $this->request->post('passage_id', 0, 'intval');
        $passage_direction = $this->request->post('passage_direction', 0, 'intval');
        try {
            $carWorkerRecordService= new HouseVillageCarWorkerRecordService();
            $plateresult=$carWorkerRecordService->getCurrentPassagePlateresult($passage_id,$current_village_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$plateresult);
    }
    public function addParkInfo(){
        $current_village_id = $this->current_village_id>0 ? $this->current_village_id:$this->village_id;
        $data=array();
        $data['village_id'] = $current_village_id;
        $data['village_name'] = '';
        $data['id'] = $this->request->param('passage_id',0,'int');//设备id
        $data['car_number'] = $this->request->param('car_number','','trim');//车牌号
        $data['accessTime'] = $this->request->param('accessTime','','trim');//通行时间
        $data['park_time'] = $this->request->param('park_time','','trim');//停车时长(分钟)
        $data['user_name'] = $this->request->param('username','','trim');//用户姓名
        $data['user_phone'] = $this->request->param('phone','','trim');//用户手机号
        $data['price'] = $this->request->param('price','','trim');//停车费
        $data['img'] = $this->request->param('img','','trim');//图片

        if (empty($data['id'])){
            return api_output_error(1001, '通道id不能为空');
        }
        if (empty($data['car_number'])){
            return api_output_error(1001, '车牌号不能为空');
        }
        if (empty($data['accessTime'])){
            return api_output_error(1001, '通行时间不能为空');
        }
        if (!empty($data['park_time'])) {
            if ($data['park_time'] < 0) {
                return api_output_error(1001, '停车时长不能小于0');
            }
            if (floor($data['park_time']) != $data['park_time']) {
                return api_output_error(1001, '停车时长请输入正整数');
            }
        }else{
            $data['park_time']=0;
        }
        if (!empty($data['price'])) {
            if ($data['price'] < 0) {
                return api_output_error(1001, '停车费用不能小于0');
            }
            if (floor($data['price']) != $data['price']) {
                return api_output_error(1001, '停车费用请输入正整数');
            }
            $data['price']=floor($data['price']);
        }else{
            $data['price']=0;
        }
        $db_house_new_park_service = new HouseVillageParkingService();
        try{
            $data['park_time']=$data['park_time']*60; //转成秒
            $data['comfrom']='pc_admin';
            $res = $db_house_new_park_service->addParkInfo($data);
        }catch (\Exception $e){
            $errMsg=$e->getMessage();
            if($errMsg && strpos($errMsg,'Duplicate entry') && strpos($errMsg,'car_number_accessType_accessTime')){
                return api_output_error(1001, '此车此时间的记录已经存在了');
            }
            return api_output_error(-1,$errMsg);
        }
        return api_output(0,$res);
    }

    public function open_gate(){
        $current_village_id = $this->current_village_id>0 ? $this->current_village_id:$this->village_id;
        $data=array();
        $data['village_id']=$current_village_id;
        $data['id'] = $this->request->param('passage_id',0);
        $data['car_number'] = $this->request->param('car_number','','trim');
        $data['open_type'] = $this->request->param('open_type',0); //1收费
        $data['pay_type'] = $this->request->param('pay_type',0);
        $data['park_time'] = $this->request->param('park_time','','trim');
        $data['price'] = $this->request->param('price',0);

        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        if (empty($data['id'])){
            return api_output_error(1001, '通道id不能为空');
        }
        if (empty($data['car_number'])){
            return api_output_error(1001, '车牌号不能为空');
        }
        if($data['open_type']==1) {
            if (empty($data['park_time'])) {
                return api_output_error(1001, '停车时长不能为空');
            }
            if ($data['park_time'] < 0) {
                return api_output_error(1001, '停车时长不能小于0');
            }
            if (floor($data['park_time']) != $data['park_time']) {
                return api_output_error(1001, '停车时长请输入正整数');
            }
            if (empty($data['price'])) {
                return api_output_error(1001, '停车费用不能为空');
            }
            if ($data['price'] < 0) {
                return api_output_error(1001, '停车费用不能小于0');
            }
        }else{
            $data['price']=0;
            $data['park_time']=0;
        }
        $db_house_visitor_service = new HouseNewParkingService();
        try{
            $list = $db_house_visitor_service->open_gate($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    public function getOfflineList()
    {
        $current_village_id = $this->current_village_id>0 ? $this->current_village_id:$this->village_id;
        $service_house_new_offline = new HouseNewPorpertyService();
        $service_house_village = new HouseVillageService();
        $village_info = $service_house_village->getHouseVillageInfo(['village_id' => $current_village_id], 'property_id');
        $where['property_id'] = $village_info['property_id'];
        $where['status'] = 1;
        $data = $service_house_new_offline->getOfflineList($where, 'id,name');
        return api_output(0, $data);
    }
    public function handOverToNext()
    {
        $current_village_id = $this->current_village_id>0 ? $this->current_village_id:$this->village_id;
        $carWorkerRecordService = new HouseVillageCarWorkerRecordService();
        $sentry_info = $carWorkerRecordService->getCurrentSentryStatistics($current_village_id, $this->loginType, $this->sentry_info);
        $nowtime = time();
        $sentry_info['start_time_str'] = date('Y-m-d H:i:s', $sentry_info['start_time']);
        $sentry_info['end_time_str'] = date('Y-m-d H:i:s');
        $login_time = $nowtime - $sentry_info['start_time'];
        $login_time_str = '';
        if ($login_time < 60) {
            $login_time_str = '1分钟内';
        } else {
            if ($login_time >= 3600) {
                $h_time = ($login_time / 3600);
                $h_time = floor($h_time);
                $login_time_str = $h_time . '小时';
                $login_time = $login_time - $h_time * 3600;
            }
            if ($login_time >= 60) {
                $m_time = ($login_time / 60);
                $m_time = ceil($m_time);
                $login_time_str = $login_time_str . $m_time . '分钟';
            }
        }
        $sentry_info['login_time_str'] = $login_time_str;
        return api_output(0,$sentry_info);
    }
    
    public function handOverToNextOut()
    {
        $current_village_id = $this->current_village_id>0 ? $this->current_village_id:$this->village_id;
        $carWorkerRecordService = new HouseVillageCarWorkerRecordService();
        try{
            $tmpRecord = $carWorkerRecordService->outSentryStatistics($current_village_id, $this->loginType, $this->sentry_info);
            $tmpRecord['msg']='交班成功！';
            return api_output(0,$tmpRecord);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
    
}