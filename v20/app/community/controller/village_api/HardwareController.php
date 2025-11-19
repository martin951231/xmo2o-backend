<?php
/**
 * @author : liukezhu
 * @date : 2021/10/25
 */
namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\HardwareServe;
use app\community\model\service\HouseWorkerService;
use app\community\model\service\HouseMaintenanWorkerService;

class HardwareController extends CommunityBaseController{

    /**
     *首页接口
     * @author: liukezhu
     * @date : 2021/10/25
     */
    public function index(){
        $HardwareServe = new HardwareServe();
        try{
            $list=$HardwareServe->getIndex();
            $list['face_device'] = $HardwareServe->getFaceDevice();
            $list['charging_device'] = $HardwareServe->getCharging();
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     *门禁设备状态
     * @author: liukezhu
     * @date : 2021/10/26
     * @return \json
     */
    public function getFaceDeviceStatistics(){
        $page = $this->request->param('page',1,'int');
        $limit = $this->request->param('limit',10,'int');
        try{
            $list = (new HardwareServe())->getVillageFaceDevice($page,$limit);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 开门方式统计
     * @author: liukezhu
     * @date : 2021/10/26
     * @return \json
     */
    public function getOpenDoorStatistics(){
        $page = $this->request->param('page',1,'int');
        $type = $this->request->param('type',1,'int');
        $limit = $this->request->param('limit',10,'int');
        switch ($type) {
            case 1:
                $log_from=0;
                break;
            case 2:
                $log_from=1;
                break;
            case 3:
                $log_from=2;
                break;
            default:
                return api_output_error(1001,'必传参数缺失');
        }
        try{
            $list = (new HardwareServe())->getVillageOpenDoor($log_from,$page,$limit);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 充电桩统计
     * @author: liukezhu
     * @date : 2021/10/27
     * @return \json
     */
    public function getPileEquipmentStatistics(){
        $page = $this->request->param('page',1,'int');
        $limit = $this->request->param('limit',10,'int');
        try{
            $list = (new HardwareServe())->getVillagePileOrder($page,$limit);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 人脸门禁实时记录
     * @author: liukezhu
     * @date : 2021/10/27
     * @return \json
     */
    public function getOpenDoorLog(){
        $page = $this->request->param('page',1,'int');
        $limit = $this->request->param('limit',10,'int');
        try{
            $list = (new HardwareServe())->getVillageOpenDoorLog($page,$limit);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 报警信息
     * @author: liukezhu
     * @date : 2021/10/27
     * @return \json
     */
    public function getWarningLog(){
        $page = $this->request->param('page',1,'int');
        $limit = $this->request->param('limit',10,'int');
        try{
            $list = (new HardwareServe())->getMaintenanLog($page,$limit);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 获取界面设备参数
     * @author: liukezhu
     * @date : 2021/11/26
     * @return \json
     */
    public function getDeviceParam(){
        $type = $this->request->param('type',0,'int');
        try{
            $list = (new HardwareServe())->getDeviceParam($type);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 门禁设备数据
     * @author: liukezhu
     * @date : 2021/11/26
     * @return \json
     */
    public function getFaceDeviceList(){
        $page = $this->request->param('page',1,'int');
        $type = $this->request->param('type',1,'int');
        $limit = $this->request->param('limit',10,'int');
        $device_sn = $this->request->param('device_sn','','trim');
        $device_type = $this->request->param('device_type',0,'int');
        $device_status = $this->request->param('device_status',0,'int');
        try{
            $where[]=[ 'd.is_del','=',0];
            $where[] = ['v.status','=',1];
            switch ($type) {
                case 1://全部设备
                    if(!empty($device_status) && in_array($device_status,[1,2])){
                        $where[]=[ 'd.device_status','=',$device_status];
                    }
                    break;
                case 2://在线设备
                    $where[]=[ 'd.device_status','=',1];
                    break;
                case 3://离线设备
                    $where[]=[ 'd.device_status','=',2];
                    break;
                default:
                    return api_output_error(1001,'必传参数缺失');
            }
            if(!empty($device_sn)){
                $where[] = ['d.device_sn', 'like', '%'.$device_sn.'%'];
            }
            if(!empty($device_type)){
                $where[]=[ 'd.device_type','=',$device_type];
            }
            $field='v.village_name,d.device_sn,d.device_name,d.device_type,d.device_status';
            $order='d.device_id desc';
            $list = (new HardwareServe())->getFaceDeviceList($where,$field,$order,$page,$limit);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**充电桩设备数据
     * @author: liukezhu
     * @date : 2021/11/26
     * @return \json
     */
    public function getPileEquipmentList(){
        $page = $this->request->param('page',1,'int');
        $type = $this->request->param('type',1,'int');
        $limit = $this->request->param('limit',10,'int');
        $device_sn = $this->request->param('device_sn','','trim');
        $device_type = $this->request->param('device_type',0,'int');
        $device_status = $this->request->param('device_status',0,'int');
        $time=time();
        try{
            $where[]=[ 'e.is_del','=',1];
            $where[]=[ 'v.status','=',1];
            switch ($type) {
                case 1://全部设备
                   if($device_status == 1){
                       $where['_string']= ' ( e.type = 2 and  e.last_heart_time is not null and  e.last_heart_time >= '.($time - 60).') or ( e.type <> 2 and  e.status = 1)';
                   }elseif ($device_status == 2){
                       $where['_string']= ' (  e.type = 2 and ( e.last_heart_time is null or  e.last_heart_time < '.($time - 60).')) or ( e.type <> 2 and  e.status <> 1)';
                   }
                    break;
                case 2://在线设备
                    $where['_string']= ' ( e.type = 2 and  e.last_heart_time is not null and  e.last_heart_time >= '.($time - 60).') or ( e.type <> 2 and  e.status = 1)';
                    break;
                case 3://离线设备
                    $where['_string']= ' (  e.type = 2 and ( e.last_heart_time is null or  e.last_heart_time < '.($time - 60).')) or ( e.type <> 2 and  e.status <> 1)';
                    break;
                default:
                    return api_output_error(1001,'必传参数缺失');
            }
            if(!empty($device_sn)){
                $where[] = ['e.equipment_serial', 'like', '%'.$device_sn.'%'];
            }
            if(!empty($device_type)){
                $where[]=[ 'e.type','=',$device_type];
            }
            $field='v.village_name,e.equipment_serial as device_sn,e.equipment_name as device_name,e.type,e.last_heart_time,e.status';
            $order='e.id desc';
            $list = (new HardwareServe())->getPileEquipmentList($where,$field,$order,$page,$limit);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    public function getOffLineDevList(){
        $page = $this->request->param('page',1,'int');
        $limit = $this->request->param('limit',20,'int');
        try{
            $hardwareServe=new HardwareServe();
            $village_id = $this->adminUser['village_id'];
            $houseMaintenanWorkerService = new HouseMaintenanWorkerService();
            $lists=$houseMaintenanWorkerService->getMaintenanWorkerList(['village_id'=>$village_id,'is_del_time'=>0],'*');
            $workerStr='';
            if(!empty($lists)){
                $service_house_workers = new HouseWorkerService();
                foreach ($lists as $vv){
                    $whereArr=array('wid'=>$vv['wid'],'status'=>1,'is_del'=>0);
                    $tmpArr=$service_house_workers->getOneWorker($whereArr,'wid,name,village_id,nickname,phone,openid');
                    if($tmpArr){
                        $workerStr .=empty($workerStr) ? $tmpArr['name']:'、'.$tmpArr['name'];
                    }
                }
            }
            $whereArr=array('village_id'=>$village_id);
            $list = $hardwareServe->getMainTenanLogList($whereArr,$page,$limit,$workerStr);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 工作人员列表
     * @author lijie
     * @date_time 2021/07/09
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getNoticeWorkers()
    {
        $village_id = $this->adminUser['village_id'];
        $service_house_workers = new HouseWorkerService();
        $where[] = ['village_id','=',$village_id];
        $where[] = ['status','=',1];
        $where[] = ['is_del','=',0];
        $data = $service_house_workers->getWorker($where,'wid,name,village_id,nickname,phone,openid');
        $tags=array();
        if($data && !$data->isEmpty()){
            $data=$data->toArray();
        }else{
            $data=array();
        }
        $houseMaintenanWorkerService = new HouseMaintenanWorkerService();
        $lists=$houseMaintenanWorkerService->getMaintenanWorkerList(['village_id'=>$village_id,'is_del_time'=>0],'*');
        if(!empty($lists) && !empty($data)){
            foreach ($lists  as $vv){
                foreach ($data as $wvv){
                    if($vv['wid']==$wvv['wid']){
                        $tags[]=$wvv;
                        break;
                    }
                }
            }
        }
        $ret=array('workers'=>$data,'tags'=>$tags);
        return api_output(0,$ret);
    }

    public function saveNoticeWorkers(){
        $village_id = $this->adminUser['village_id'];
        $tags = $this->request->param('tags');
        try {
            $houseMaintenanWorkerService = new HouseMaintenanWorkerService();
            $houseMaintenanWorkerService->saveMaintenanWorkers($village_id,$tags);
            return api_output(0,'操作成功！');
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }


    public function getRootAiNoticeWorkers()
    {
        $thirdRobotSubscribeMessageService= new \app\thirdRobot\model\service\ThirdRobotSubscribeMessageService();
        $village_id = $this->adminUser['village_id'];
        $service_house_workers = new HouseWorkerService();
        $where[] = ['village_id','=',$village_id];
        $where[] = ['status','=',1];
        $where[] = ['is_del','=',0];
        $data = $service_house_workers->getWorker($where,'wid,name,village_id,nickname,phone,openid');
        $tags=array();
        if($data && !$data->isEmpty()){
            $data=$data->toArray();
            if(!empty($data)){
                foreach ($data as $kk=>$wvv){
                    $wx_bind='未绑定微信';
                    if($wvv['openid'] && strlen($wvv['openid'])>15){
                        $wx_bind='已绑定微信';
                    }
                    $title_str=$wvv['name'].'，手机号：'.$wvv['phone'].'（'.$wx_bind.'）';
                    $data[$kk]['title_str']=$title_str;
                }
            }
        }else{
            $data=array();
        }
        $whereArr=array();
        $whereArr[]=array('village_id','=',$village_id);
        $whereArr[]=array('id_type','=',2);
        $whereArr[]=array('del_time','<',1);
        $workerSetInfo=$thirdRobotSubscribeMessageService->getNoticeWorkerSetInfo($whereArr,'*');
        $ai_send_type=2;
        $extra_data=array();
        if($workerSetInfo && $workerSetInfo['extra_data']){
            $extra_data=json_decode($workerSetInfo['extra_data'],true);
        }
        if($workerSetInfo && $workerSetInfo['ai_send_type']){
            $ai_send_type=$workerSetInfo['ai_send_type'];
        }
        $ai_send_type=strval($ai_send_type);
        $ret=array('workers'=>$data,'tags'=>$extra_data,'ai_send_type'=>$ai_send_type);
        return api_output(0,$ret);
    }

    public function saveRootAiNoticeWorkers(){
        $thirdRobotSubscribeMessageService= new \app\thirdRobot\model\service\ThirdRobotSubscribeMessageService();
        $village_id = $this->adminUser['village_id'];
        $tags = $this->request->param('tags');
        $ai_send_type= $this->request->param('ai_send_type',2,'intval');
        try {
            $extra_data=array();
            if($tags && is_array($tags)){
                foreach ($tags as $tvv){
                    $extra_data[]=array('wid'=>$tvv['wid'],'name'=>$tvv['name'],'village_id'=>$tvv['village_id']);
                }
            }
            $extra_data_str=!empty($extra_data) ? json_encode($extra_data,JSON_UNESCAPED_UNICODE):'';
            $save_data=array('extra_data'=>$extra_data_str,'ai_send_type'=>$ai_send_type,'id_type'=>2);
            $thirdRobotSubscribeMessageService->saveNoticeWorkerSet($village_id,$save_data);
            return api_output(0,'操作成功！');
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
}