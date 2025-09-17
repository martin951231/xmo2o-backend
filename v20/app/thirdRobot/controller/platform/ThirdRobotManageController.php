<?php


namespace app\thirdRobot\controller\platform;
use app\thirdRobot\controller\platform\AuthBaseController;
use app\thirdRobot\model\service\ThirdRobotDeviceService;
class ThirdRobotManageController extends AuthBaseController
{
    /**
     * 机器人列表
     */
    public function getRoboterList()
    {
        $whereArr=array();
        $whereArr[]=array('id','>',0);
        $name = $this->request->post('name','','trim');
        $robot_code= $this->request->post('robot_code','','trim');
        $device_type_v= $this->request->post('device_type_v',0,'intval');
        $work_status_v = $this->request->post('work_status_v', -1, 'intval');
        $device_online_v = $this->request->post('device_online_v', -1, 'intval');
        $page = $this->request->post('page', 1, 'intval');
        if(!empty($name)){
            $whereArr[]=array('name','like','%'.$name.'%');
        }
        if(!empty($robot_code)){
            $whereArr[]=array('robot_code','like','%'.$robot_code.'%');
        }
        if($device_type_v>0){
            $whereArr[]=array('device_type','=',$device_type_v);
        }
        if($work_status_v>=0){
            $whereArr[]=array('work_flag','=',$work_status_v);
        }
        if($device_online_v>=0){
            $whereArr[]=array('online_flag','=',$device_online_v);
        }
        try {
            $thirdRobotDeviceService= new ThirdRobotDeviceService();
            $thirdRobotDeviceService->checkConfigure(true);
            $data= $thirdRobotDeviceService->getRobotList($whereArr,'*',$page);
            return api_output(0,$data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function getAllRobotsList(){
        try {
            $whereArr=array();
            $whereArr[]=array('id','>',0);
            $whereArr[]=array('del_time','<',1);
            $thirdRobotDeviceService= new ThirdRobotDeviceService();
            $field='id,name,robot_code';
            $data= $thirdRobotDeviceService->getRobotSelectList($whereArr,$field);
            return api_output(0,$data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function getRemoteRobotList()
    {
        try {
            $thirdRobotDeviceService= new ThirdRobotDeviceService();
            $current=1;
            $pagesize=100;
            $data= $thirdRobotDeviceService->getRemoteRobotData($current,$pagesize);
            if(is_array($data) && isset($data['err_msg'])){
                return api_output_error(-1, $data['err_msg']);
            }
            return api_output(0,$data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function getOneDetailInfo(){
        $robot_id = $this->request->post('robot_id','0','intval');
        $xtype = $this->request->post('xtype',0,'intval'); // 1不需要额外信息
        $get_look_video = $this->request->post('get_look_video',0,'intval'); // 1获取视频地址
        try {
            $thirdRobotDeviceService= new ThirdRobotDeviceService();
            $whereArr=array();
            $whereArr[]= ['id','=',$robot_id];
            $data= $thirdRobotDeviceService->getOneDetailInfo($whereArr,$xtype);
            $data['look_video_url']='';
            $robot_login_lnfo=$data['robot_login_lnfo'] ? json_decode($data['robot_login_lnfo'],true):array();
            $got_time=0;
            if($robot_login_lnfo && isset($robot_login_lnfo['got_time'])){
                $got_time=$robot_login_lnfo['got_time'];
            }
            if($get_look_video){
                $loginInfo = $thirdRobotDeviceService->getRobotLoginInfo($data['robot_code'],$data['id']);
                $siteUrl=cfg('site_url');
                $look_video_url=$siteUrl . '/admin.php?g=System&c=House&a=robot_look_video&robot_device_id='.$data['id'].'&robot_code='.$data['robot_code'];
                $data['look_video_url']=$look_video_url;
            }
            return api_output(0,$data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function saveUpdateRobotSet(){
        $robot_id = $this->request->post('robot_id','0','intval');
        $look_video_type = $this->request->post('look_video_type','0','intval');
        try {
            $thirdRobotDeviceService= new ThirdRobotDeviceService();
            $whereArr=array();
            $whereArr[]= ['id','=',$robot_id];
            $saveData=array('look_video_type'=>$look_video_type);
            $data= $thirdRobotDeviceService->saveOneRobotInfo($whereArr,$saveData);
            return api_output(0,$data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function robotBindVillageOpt(){
        $robot_id = $this->request->post('robot_id',0,'intval');
        $robot_code = $this->request->post('robot_code','','trim');
        $village_id = $this->request->post('village_id',0,'intval');
        $xtype = $this->request->post('xtype',0,'intval'); // 1绑定 2取消绑定
        if($robot_id<1){
            return api_output_error(1001,'机器人参数错误！');
        }
        if(empty($robot_code)){
            return api_output_error(1001,'机器人编码错误！');
        }
        if($village_id<1){
            return api_output_error(1001,'小区ID参数错误！');
        }
        try {
            $thirdRobotDeviceService= new ThirdRobotDeviceService();
            $whereArr=array();
            $whereArr[]= ['id','=',$robot_id];
            $whereArr[]= ['robot_code','=',$robot_code];
            $data= $thirdRobotDeviceService->robotBindVillageOpt($whereArr,$xtype,$village_id);
            return api_output(0,$data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    /**
     * 将分支下所有机器人绑定到小区
    ***/
    public function robotBranchBindVillageOpt(){
        $robot_id = $this->request->post('robot_id',0,'intval');
        $village_id = $this->request->post('village_id',0,'intval');
        $branch_id = $this->request->post('branch_id',0,'intval');
        if($village_id<1){
            return api_output_error(1001,'小区ID参数错误！');
        }
        if($branch_id<1){
            return api_output_error(1001,'组织机构ID参数错误！');
        }
        try {
            $thirdRobotDeviceService= new ThirdRobotDeviceService();
            $data= $thirdRobotDeviceService->robotBranchBindVillageOpt($branch_id,$village_id);
            return api_output(0,$data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function getRemoteRobotTaskList(){
        $robot_id = $this->request->post('robot_id','0','intval');
        $robot_code = $this->request->post('robot_code','','trim');
        if($robot_id<1){
            return api_output_error(1001,'机器人参数错误！');
        }
        if(empty($robot_code)){
            return api_output_error(1001,'机器人编码错误！');
        }
        try {
            $thirdRobotDeviceService= new ThirdRobotDeviceService();
            $current=1;
            $pagesize=100;
            $data= $thirdRobotDeviceService->getRemoteRoboterHistoryTask($robot_code,$current,$pagesize);
            return api_output(0,$data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function getThirdRobotTaskList(){
        $robot_id = $this->request->post('robot_id','0','intval');
        $robot_code = $this->request->post('robot_code','','trim');
        $page = $this->request->post('page', 1, 'intval');
        if($robot_id<1){
            return api_output_error(1001,'机器人参数错误！');
        }
        if(empty($robot_code)){
            return api_output_error(1001,'机器人编码错误！');
        }
        try {
            $thirdRobotDeviceService= new ThirdRobotDeviceService();
            $whereArr=array();
            $whereArr[]= ['robot_code','=',$robot_code];
            $data= $thirdRobotDeviceService->getThirdRobotTaskList($whereArr,'*',$page);
            return api_output(0,$data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function geteRobotTaskDetails(){
        $id = $this->request->post('id',0,'intval');
        $task_id = $this->request->post('task_id','','trim');
        $task_instance_id = $this->request->post('task_instance_id','','trim');
        $robot_code = $this->request->post('robot_code','','trim');
        $page = $this->request->post('page', 1, 'intval');
        if($task_id<1){
            return api_output_error(1001,'任务参数错误！');
        }
        if(empty($robot_code)){
            return api_output_error(1001,'机器人编码错误！');
        }
        try {
            $thirdRobotDeviceService= new ThirdRobotDeviceService();
            $data= $thirdRobotDeviceService->getRobotRemoteTaskDetails($task_instance_id,$id,$task_id);
            return api_output(0,$data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function getRemoteRobotAlarmList(){
        $robot_id = $this->request->post('robot_id','0','intval');
        $robot_code = $this->request->post('robot_code','','trim');
        $level = $this->request->post('level',0,'trim'); //告警级别 - 1、2、3、4四个等级（1为最高异常级别，4为正常告警）
        $status = $this->request->post('status',0,'trim'); //状态：1-告警中，2-已消除
        if($robot_id<1){
            return api_output_error(1001,'机器人参数错误！');
        }
        if(empty($robot_code)){
            return api_output_error(1001,'机器人编码错误！');
        }
        try {
            $thirdRobotDeviceService= new ThirdRobotDeviceService();
            $page=1;
            $pagesize=100;
            $params=array();
            if($level>0 && $level<5){
                $params['level']=$level;
            }
            if(in_array($status,[1,2])){
                $params['status']=$status-1;
            }
            $data= $thirdRobotDeviceService->getRemoteRobotAlarmList($robot_code,$page,$pagesize,$params);
            return api_output(0,$data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function getRobotAlarmList(){
        $robot_id = $this->request->post('robot_id','0','intval');
        $robot_code = $this->request->post('robot_code','','trim');
        $level = $this->request->post('level',0,'trim'); //告警级别 - 1、2、3、4四个等级（1为最高异常级别，4为正常告警）
        $status = $this->request->post('status',0,'trim'); //状态：1-告警中，2-已消除
        $page = $this->request->post('page', 1, 'intval');
        if($robot_id<1){
            return api_output_error(1001,'机器人参数错误！');
        }
        if(empty($robot_code)){
            return api_output_error(1001,'机器人编码错误！');
        }
        try {
            $thirdRobotDeviceService= new ThirdRobotDeviceService();
            $pagesize=10;
            $params=array();
            if($level>0 && $level<5){
                $params['level']=$level;
            }
            if(in_array($status,[1,2])){
                $params['status']=$status-1;
            }
            $data= $thirdRobotDeviceService->getRobotAlarmList($robot_code,$page,$pagesize,$params);
            $whereArr=array(['id','=',$robot_id]);
            $fieldStr='id,village_id,is_bind_village,bind_village_time,name,robot_code,robot_sku,robot_model,device_type,robot_type';
            $robotInfo=$thirdRobotDeviceService->getOneDataInfo($whereArr,$fieldStr);
            $data['robot_info']=$robotInfo;
            return api_output(0,$data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function getAdminWorkers(){
        $village_id= $this->request->post('village_id',0,'intval');
        $robot_id= $this->request->post('robot_id',0,'intval');
        $xtype= $this->request->post('xtype','','trim');
        try {
            $levels_arr = array(['levelv' => 1], ['levelv' => 2], ['levelv' => 3], ['levelv' => 4]);
            if($xtype=='village_worker'){
                if($village_id<1){
                    $returnArr = array('list' => array(), 'seted_ids' => array(),'levels_arr'=>$levels_arr);
                    return api_output(0,$returnArr);
                }
                $thirdRobotDeviceService= new ThirdRobotDeviceService();
                $whereArr=array();
                $whereArr[]=['village_id','=',$village_id];
                $whereArr[]=['status','<=',1];
                $whereArr[]=['is_del','<',1];
                $field='wid,village_id,account,type,department_id,name as realname,phone,openid,nickname,xtype,relation_id';
                $data= $thirdRobotDeviceService->getVillageWorkers($whereArr,$field,$robot_id);
            }else{
                $thirdRobotDeviceService= new ThirdRobotDeviceService();
                $whereArr=array();
                $whereArr[]=['id','>',0];
                $whereArr[]=['status','=',1];
                $field='id,account,realname,phone,openid,nickname,level';
                $data= $thirdRobotDeviceService->getAdminWorkers($whereArr,$field); 
            }
            $data['levels_arr'] = $levels_arr;
            return api_output(0,$data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function saveAlarmNoticeSet(){
        $admin_id = $this->request->post('admin_id',0,'intval');
        $id = $this->request->post('id',0,'intval');
        $levels = $this->request->post('levels','');
        $robot_codes = $this->request->post('robot_codes','');
        $robot_code = $this->request->post('robot_code','','trim');
        $xtype= $this->request->post('xtype','village_worker','trim');
        $village_id = $this->request->post('village_id',0,'intval');
        $robot_id = $this->request->post('robot_id',0,'intval');
        
        if($admin_id<1){
            return api_output_error(1001,'请选择人员信息！');
        }
        if(empty($levels)||!is_array($levels)){
            return api_output_error(1001,'请至少选择一个告警等级！');
        }
        if($xtype=='village_worker' && (empty($robot_code) || $robot_id<1)){
            return api_output_error(1001,'机器人信息错误！');
        }
        if($xtype!='village_worker' && (empty($robot_codes)||!is_array($robot_codes))){
            return api_output_error(1001,'请至少选择一个机器人编号！');
        }
        try {
            $thirdRobotDeviceService= new ThirdRobotDeviceService();
            $saveArr=array('levels'=>$levels,'robot_code'=>$robot_codes);
            if($xtype=='village_worker'){
                $saveArr['robot_code']=$robot_code;
                $saveArr['village_id']=$village_id;
                $saveArr['robot_id']=$robot_id;
            }
            $data= $thirdRobotDeviceService->saveRobotAlarmNoticeSet($saveArr,$admin_id,$id,$xtype);
            return api_output(0,$data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function getAlarmNoticeList(){
        $robot_id = $this->request->post('robot_id',0,'intval');
        $xtype = $this->request->post('xtype','','trim');
        $village_id = $this->request->post('village_id',0,'intval');
        $page = $this->request->post('page', 1, 'intval');
        try {
            $thirdRobotDeviceService= new ThirdRobotDeviceService();
            if($xtype=='village_worker'){
                $whereArr=array();
                $whereArr[]=['id','>',0];
                $whereArr[]=['del_time','<',1];
                $whereArr[]=['village_id','=',$village_id];
                $whereArr[]=['id_type','=',1];
            }else{
                $whereArr=array();
                $whereArr[]=['id','>',0];
                $whereArr[]=['del_time','<',1];
                $whereArr[]=['id_type','<',1];
            }
            $data= $thirdRobotDeviceService->getAlarmNoticeSetList($whereArr,'*',$page);
            return api_output(0,$data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function delAlarmNoticeSet(){
        $id = $this->request->post('id', 0, 'intval');
        if($id<1){
            return api_output_error(1001,'参数错误！');
        }
        try {
            $thirdRobotDeviceService= new ThirdRobotDeviceService();
            $data= $thirdRobotDeviceService->delAlarmNoticeSet($id);
            return api_output(0,$data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
}