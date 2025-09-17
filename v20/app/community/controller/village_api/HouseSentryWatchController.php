<?php
/**
 * 小区岗亭
 **/

namespace app\community\controller\village_api;
use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseNewParkingService;
use app\community\model\service\HouseNewPorpertyService;
use app\community\model\service\HouseVillageParkingService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageCarWorkerRecordService;
use customization\customization;

class HouseSentryWatchController extends CommunityBaseController
{
    use customization;
    public $is_opt_sentry=0;
    public $loginType=0;
    public $property_id=0;
    public $village_id=0;
    public $login_name=0;
    public $wid=0;
    public $admin_id=0;  //property_admin表id
    public $sentry_info='';
    public $current_village_id=0;
    public $err_output=array();
    public function initialize()
    {
        parent::initialize();
        $this->err_output=array();
        if (!in_array($this->login_role, array(201, 202, 401))) {
            //return api_output(1000, array('err_code'=>2,'login_code'=>2,'msg'=>'登录角色类型错误，请重新登录！'));
            $this->err_output=array('err_code'=>2,'login_code'=>2,'msg'=>'登录角色类型错误，请重新登录！');
        }
        $this->current_village_id = $this->request->post('current_village_id', 0, 'intval');
        $methodName = $this->request->action();
        $data = $this->getSentryLoginInfo();
        if($this->loginType==2 && $this->property_id<1){
            //return api_output(1000, array('err_code'=>2,'login_code'=>2,'msg'=>'登录角色类型错误了，请重新登录！'));
            $this->err_output= array('err_code'=>2,'login_code'=>2,'msg'=>'登录角色类型错误了，请重新登录！');
        }else if($this->loginType==1 && $this->village_id<1){
            //return api_output(1000, array('err_code'=>2,'login_code'=>2,'msg'=>'登录角色类型有误，请重新登录！'));
            $this->err_output= array('err_code'=>2,'login_code'=>2,'msg'=>'登录角色类型有误，请重新登录！');
        }
        if($methodName!='getSentryInfo' && empty($data['sentry_info'])){
            //return api_output(1000, array('err_code'=>2,'login_code'=>2,'msg'=>'登录信息错误，请重新登录！'));
            $this->err_output= array('err_code'=>2,'login_code'=>2,'msg'=>'登录信息错误，请重新登录！');
        }
        if($methodName!='getSentryInfo' && !empty($data['sentry_info']) && $data['sentry_info']['is_login']<1){
            //return api_output(1000, array('err_code'=>2,'login_code'=>2,'msg'=>'登录失效了，请重新登录！'));
            $this->err_output= array('err_code'=>2,'login_code'=>2,'msg'=>'登录失效了，请重新登录！');
        }
    }
    public function getSentryInfo(){
        $current_village_id = $this->request->post('current_village_id', 0, 'intval');
        $tmp_village_id=$current_village_id;
        $data = $this->getSentryLoginInfo();
        $data['village_list']=array();
        $current_village_name='';
        if($this->loginType==2){
           $houseVillageService= new HouseVillageService();
           $whereArr=array(['property_id','=',$this->property_id],['village_id','>',0],['status','=',1]);
           $houseVillageObj=$houseVillageService->getList($whereArr,'village_id,village_name',1,200,'village_id DESC',0);
           if($houseVillageObj && !$houseVillageObj->isEmpty()){
               $houseVillages=$houseVillageObj->toArray();
               $data['village_list']=$houseVillages;
               if($current_village_id<1){
                   $current_village_id= $houseVillages['0']['village_id'];
                   $current_village_name=$houseVillages['0']['village_name'];
               }
           }
        }else{
            $current_village_id=$current_village_id>0 ?$current_village_id:$this->village_id;
            $houseVillageService= new HouseVillageService();
            $oneHouseVillage=$houseVillageService->getHouseVillageInfo(array('village_id'=>$current_village_id),'village_id,village_name');
            if($oneHouseVillage && !$oneHouseVillage->isEmpty()){
                $current_village_name=$oneHouseVillage['village_name'];
            }
        }
        $data['is_public_v']=1; //默认外网视频
        $data['current_village_id']=$current_village_id;
        $data['current_village_name']=$current_village_name;
        try {
            $carWorkerRecordService= new HouseVillageCarWorkerRecordService();
            if($tmp_village_id<1){
                //第一次登录
               
                $ret= $carWorkerRecordService->handleCurrentLoginSentry($this->loginType,$this->login_role,$this->adminUser);
            }
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(1000,$data);
    }
    public function getSentryLoginInfo(){
        $village_id = $this->adminUser && isset($this->adminUser['village_id'])? $this->adminUser['village_id']:0;
        $is_opt_sentry=0;
        if($this->login_role==401 && $village_id>0){
            $is_opt_sentry=1;
            $this->loginType=1;  //小区登录
            $this->is_opt_sentry=1;
            $this->login_name=$this->adminUser['realname'] ? $this->adminUser['realname'] : $this->adminUser['account'];
        }
        $this->village_id=$village_id;
        $wid=$this->adminUser && isset($this->adminUser['wid'])? $this->adminUser['wid']:0;
        $this->wid=$wid;
        
        $this->property_id=$this->adminUser && isset($this->adminUser['property_id']) ? $this->adminUser['property_id'] : 0;
        if(in_array($this->login_role, array(201, 202))){

            $this->loginType=2;  //物业登录
            if($this->login_role==201){
                $this->login_name='物业总管理员';
            }
            if($this->login_role==202){
                $realname=$this->adminUser['realname'] ? $this->adminUser['realname'] : $this->adminUser['account'];
                $this->login_name=$realname.'【物业普通管理员】';
                $this->admin_id=$this->adminUser['id'];
            }
        }
        $loginSentry=array();
        if($this->current_village_id>0){
            //检查账号登录状态
            $carWorkerRecordService= new HouseVillageCarWorkerRecordService();
            $loginSentry= $carWorkerRecordService->checkCurrentLoginSentry($this->loginType,$this->login_role,$this->adminUser);
        }
        $this->sentry_info=$loginSentry;
        $data=array('loginType'=>$this->loginType,'login_role'=>$this->login_role,'is_opt_sentry'=>$is_opt_sentry,'village_id'=>$village_id,'wid'=>$wid,'property_id'=>$this->property_id,'login_name'=>$this->login_name,'admin_id'=>$this->admin_id);
        $data['sentry_info']=$loginSentry;
        return $data;
    }
    
    /**
     * 统计信息
     */
    public function getCurrentSentryStatistics()
    {
        if(!empty($this->err_output)){
            return api_output(0,$this->err_output);
        }
        $current_village_id = $this->request->post('current_village_id', 0, 'intval');
        try {
            $carWorkerRecordService= new HouseVillageCarWorkerRecordService();
            $sentry_info=$carWorkerRecordService->getCurrentSentryStatistics($current_village_id,$this->loginType,$this->sentry_info,true);
            $carParkInfo=$carWorkerRecordService->getCurrentCarStatistics($current_village_id);
            $sentry_info['car_park_info']=$carParkInfo;
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$sentry_info);
    }
    
    public function getCarAccessRecordList()
    {
        if(!empty($this->err_output)){
            return api_output(0,$this->err_output);
        }
        $current_village_id = $this->request->post('current_village_id', 0, 'intval');
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
        $current_village_id = $this->request->post('current_village_id', 0, 'intval');
        if(!empty($this->err_output)){
            return api_output(0,$this->err_output);
        }
        try {
            $carWorkerRecordService= new HouseVillageCarWorkerRecordService();
            $car_access_record=$carWorkerRecordService->getCarParkPassages($current_village_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$car_access_record);
    }

    public function getCurrentPassagePlateresult(){
        $current_village_id = $this->request->post('current_village_id', 0, 'intval');
        $passage_id = $this->request->post('passage_id', 0, 'intval');
        $passage_direction = $this->request->post('passage_direction', 0, 'intval');
        if(!empty($this->err_output)){
            return api_output(0,$this->err_output);
        }
        try {
            $carWorkerRecordService= new HouseVillageCarWorkerRecordService();
            $plateresult=$carWorkerRecordService->getCurrentPassagePlateresult($passage_id,$current_village_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$plateresult);
    }
    public function addParkInfo(){
        $current_village_id = $this->request->post('current_village_id', 0, 'intval');
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
        if(!empty($this->err_output)){
            return api_output(0,$this->err_output);
        }
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
        $current_village_id = $this->request->post('current_village_id', 0, 'intval');
        $data=array();
        $data['village_id']=$current_village_id;
        $data['id'] = $this->request->param('passage_id',0,'intval');
        $data['car_number'] = $this->request->param('car_number','','trim');
        $data['open_type'] = $this->request->param('open_type',0); //1收费
        $data['pay_type'] = $this->request->param('pay_type',0);
        $data['park_time'] = $this->request->param('park_time','','trim');
        $data['price'] = $this->request->param('price',0);
        if(!empty($this->err_output)){
            return api_output(0,$this->err_output);
        }
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
        $current_village_id = $this->request->post('current_village_id', 0, 'intval');
        $service_house_new_offline = new HouseNewPorpertyService();
        $service_house_village = new HouseVillageService();
        $village_info = $service_house_village->getHouseVillageInfo(['village_id' => $current_village_id], 'property_id');
        $where=array();
        $where['property_id'] = $village_info['property_id'];
        $where['status'] = 1;
        $data = $service_house_new_offline->getOfflineList($where, 'id,name');
        return api_output(0, $data);
    }
    public function handOverToNext()
    {
        $current_village_id = $this->request->post('current_village_id', 0, 'intval');
        if(!empty($this->err_output)){
            return api_output(0,$this->err_output);
        }
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
        $current_village_id = $this->request->post('current_village_id', 0, 'intval');
        $carWorkerRecordService = new HouseVillageCarWorkerRecordService();
        try{
            if(!empty($this->err_output)){
                return api_output(0,$this->err_output);
            }
            $tmpRecord = $carWorkerRecordService->outSentryStatistics($current_village_id, $this->loginType, $this->sentry_info);
            $tmpRecord['msg']='交班成功！';
            return api_output(0,$tmpRecord);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
    
    public function closeParkPassageCmd(){
        $current_village_id = $this->request->post('current_village_id', 0, 'intval');
        $passage_id = $this->request->param('passage_id',0,'intval');
        $garage_id = $this->request->param('garage_id',0,'intval');
        $carWorkerRecordService = new HouseVillageCarWorkerRecordService();
        try{
            if(!empty($this->err_output)){
                return api_output(0,$this->err_output);
            }
            $tmpRet = $carWorkerRecordService->closeParkPassageCmd($passage_id,$current_village_id);
            if($tmpRet['is_support_close']<1){
                return api_output_error(1001, $tmpRet['tips_msg']);
            }else{
                return api_output(0,$tmpRet);
            }
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
}