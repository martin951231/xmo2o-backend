<?php
namespace app\community\model\service;
use app\common\model\service\config\ConfigCustomizationService;
use app\community\controller\manage_api\BaseController;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\HouseWorker;
use app\community\model\service\AdminLoginService;
use app\community\model\service\HouseNewPorpertyService;
use app\community\model\service\HouseNewRepairService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\HouseVillageUserVacancyService;
use app\community\model\service\RepairCateService;
use app\community\model\service\workorder\HouseCommunityAboutService;
use app\community\model\service\workorder\HouseCommunityHandleNoticeService;
use app\community\model\service\workorder\HouseCommunityQualityService;
use app\community\model\service\workorder\HouseCommunityWorkOrderService;
use app\consts\WorkOrderConst;
use file_handle\FileHandle;


/** Shared resident submission pipeline extracted from RepairApiController. */
class ResidentRepairSubmissionService
{
    private $defer;
    private $notifications = [];
    public function __construct($defer = false) { $this->defer = $defer; }
    private function notify(callable $task) { if ($this->defer) { $this->notifications[] = $task; } else { $task(); } }
    private function addLog($service, $data, $other) {
        if (!$this->defer) { return $service->addRepairLog($data, $other); }
        $id = $service->addRepairLog($data);
        if ($other && cfg('community_jpush_appkey') && cfg('community_jpush_secret')) {
            $this->notify(function () use ($other) { (new HouseAppJpushInfoService())->handleJpushInfoSend($other, 'repair_works_order'); });
        }
        return $id;
    }
    public function flushNotifications() {
        foreach ($this->notifications as $task) {
            try { $task(); } catch (\Throwable $e) { error_log('Community agent repair notification failed: '.get_class($e)); }
        }
        $this->notifications = [];
    }
    public function create(array $input)
    {
        $service_house_village = new HouseVillageService();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_new_repair = new HouseNewRepairService();
        $db_house_worker = new HouseWorker();
        $village_id = ($input['village_id'] ?? 0);
        $category_id = ($input['category_id'] ?? 0);
        $type_id = ($input['type_id'] ?? 0);
        $cat_fid = ($input['cat_fid'] ?? 0);
        $cat_id = ($input['cat_id'] ?? 0);
        $label_txt = ($input['label_txt'] ?? '');
        $order_content = ($input['order_content'] ?? '');
        $order_imgs = ($input['order_imgs'] ?? []);
        $videos = ($input['videos'] ?? []);
        $bind_id = ($input['pigcms_id'] ?? 0);
        if (!$village_id || !$bind_id || !$category_id || !$cat_id || (empty($order_imgs) && empty($order_content))) {
            throw new \InvalidArgumentException('缺少必传参数');
        }
        $address_type = trim($input['address_type'] ?? '');
        $address_id = ($input['address_id'] ?? 0);
        $go_time = ($input['go_time'] ?? '');

        if (is_array($label_txt)) {
            $label_txt_value = implode(',', $label_txt);
        } else {
            $label_txt_value = $label_txt;
        }
        if (is_array($order_imgs)) {
            $order_imgs_value = implode(',', $order_imgs);
        } else {
            $order_imgs_value = $order_imgs;
        }
        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        if (is_array($videos)) {
            $videos = implode(',', $videos);
        } else {
            $videos = trim($videos);
        }
        $is_supply_station = 0;
        $configInfo = [];
        if ($jz_open) {
            $uploadVideoMax = WorkOrderConst::WORK_ORDER_UPLOAD_VIDEO_MAX;
            $configInfo = (new HouseCommunityAboutService())->getHouseCommunityConfig($village_id);
            $is_pic_video = isset($configInfo['is_pic_video']) ? intval($configInfo['is_pic_video']) : 0;
            if (is_array($videos) && count($videos) > $uploadVideoMax) {
                throw new \InvalidArgumentException('上传视频数量不能超过'.$uploadVideoMax.'个');
            }
            if ($is_pic_video == 1 && empty($videos) && empty($order_imgs_value)) {
                throw new \InvalidArgumentException('图片或者视频必须上传最少1个');
            }
        }
        
        $add_status=$service_house_new_repair->checkWorkOrder($village_id);
        if ($add_status && ($add_status['is_night']==1)){
            throw new \InvalidArgumentException('你好，当前是晚班时间，创建工单，请拨打工作人员电话');
        }
        $bind_info = $service_house_village_user_bind->getBindInfo(['pigcms_id'=>$bind_id],'uid,name,phone,vacancy_id,single_id,floor_id,layer_id,type');
        if($bind_info && !$bind_info->isEmpty()){
            $bind_info=$bind_info->toArray();
        }
        $toSingleId = 0;
        if($address_type){
            if($address_type == 'room'){
                if($bind_info && isset($bind_info['type']) && $bind_info['type']!=4 && $bind_info['vacancy_id']>0 && $address_id<1 ){
                    $address_id = $bind_info['vacancy_id'];
                }
                $postData['room_id'] = $address_id;
                $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
                $vacancy_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id'=>$address_id],'single_id,floor_id,layer_id,pigcms_id,village_id');
                $postData['single_id'] = intval($vacancy_info['single_id']);
                $toSingleId = $postData['single_id'];
                $postData['floor_id'] = intval($vacancy_info['floor_id']);
                $postData['layer_id'] = intval($vacancy_info['layer_id']);
                $address = $service_house_village->getSingleFloorRoom($vacancy_info['single_id'],$vacancy_info['floor_id'],$vacancy_info['layer_id'],$address_id,$vacancy_info['village_id']);
                $postData['address_txt'] = $address;
                if($bind_info && isset($bind_info['type']) && $bind_info['type']==4){
                    //工作人员
                    $bind_info['single_id']=$vacancy_info['single_id'];
                    $bind_info['floor_id']=$vacancy_info['floor_id'];
                    $bind_info['layer_id']=$vacancy_info['layer_id'];
                    $bind_info['vacancy_id']=$address_id;
                }
            }
            if($address_type == 'public'){
                $postData['public_id'] = $address_id;
                $public_info = $service_house_new_repair->getPublicAreaInfo(['public_area_id'=>$address_id],'public_area_name');
                $address = isset($public_info['public_area_name'])?$public_info['public_area_name']:'';
                $postData['address_txt'] = $address;
            }
            $postData['address_type'] = $address_type;
            $postData['address_id'] = $address_id;
        }
        $to_supply_station = 0;
        $other_village_id = 0;
        $other_village_cate_id = 0;
        $other_village_cate_fid = 0;
        if($cat_id>0){
            $field = 'id,subject_id,cate_name,parent_id,type,uid,to_supply_station,other_village_id,other_village_cate_id,other_village_cate_fid';
            $cate_info = $service_house_new_repair->getCateInfo(['id' => $cat_id,'village_id'=>$village_id], $field);
            if (!isset($cate_info['id']) || !$cate_info['id']) {
                throw new \InvalidArgumentException('对应分类信息不存在');
            }
            if (isset($cate_info['parent_id']) && $cate_info['parent_id']) {
                $cat_fid = intval($cate_info['parent_id']);
                $category_id=$cat_fid;
            }
            $to_supply_station = isset($cate_info['to_supply_station']) ? intval($cate_info['to_supply_station']) : 0;
            $other_village_id = isset($cate_info['other_village_id']) ? intval($cate_info['other_village_id']) : 0;
            $other_village_cate_id = isset($cate_info['other_village_cate_id']) ? intval($cate_info['other_village_cate_id']) : 0;
            $other_village_cate_fid = isset($cate_info['other_village_cate_fid']) ? intval($cate_info['other_village_cate_fid']) : 0;
            if ($to_supply_station != 1) {
                $other_village_id = 0;
                $other_village_cate_id = 0;
                $other_village_cate_fid = 0;
            } else {
                $postData['to_station_village_id'] = $other_village_id;
                $postData['now_handle_village_id'] = $other_village_id;
            }
            $postData['other_cate_id'] = $other_village_cate_id;
            $postData['other_cate_fid'] = $other_village_cate_fid;
        }
        $toWorkId = 0;
        $workOrderConfig = [];
        $assign_record = [];
        $communityConfig = [];
        if ($jz_open) {
            $supplyInfo = (new HouseCommunityAboutService())->getVillageFacilitiesInfo($village_id);
            if (isset($supplyInfo['is_virtual']) && intval($supplyInfo['is_virtual']) == 1) {
                // 是供暖站
                $is_supply_station = 1;
            }
            // 按照配置获取下分配人员
            $workOrderParam = [
                'village_id' => $other_village_id > 0 ? $other_village_id : $village_id,
                'is_supply_station' => $to_supply_station == 1 ? 1 : $is_supply_station,
                'configInfo' => $other_village_id > 0 ? [] : $configInfo,
                'cat_id' => $cat_id,
                'toSingleId' => $other_village_id > 0 ? 0 : $toSingleId,
                'bind_id' => $bind_id,
            ];
            $assignRecord = (new HouseCommunityWorkOrderService())->getWorkOrderToWorker($workOrderParam);
            if (isset($assignRecord['errCode']) && intval($assignRecord['errCode']) == WorkOrderConst::WORK_ORDER_ERR_CODE_NOT_BUILDER) {
                $telephone = isset($assignRecord['telephone']) ? trim($assignRecord['telephone']) : '';
                $msg = '未设置楼栋管家，不能提交工单请先联系物业！';
                $telephone && $msg .= "\n【联系电话：{$telephone}】";
                throw new \InvalidArgumentException($msg);
            }
            $toWorkId = isset($assignRecord['toWorkId']) ? intval($assignRecord['toWorkId']) : 0;
            $communityAutoToWork = isset($assignRecord['communityAutoToWork']) ? $assignRecord['communityAutoToWork'] : [];
            $communityConfig = isset($assignRecord['communityConfig']) ? $assignRecord['communityConfig'] : [];
            $repairCateConfig = isset($assignRecord['repairCateConfig']) ? $assignRecord['repairCateConfig'] : [];
            $communityAutoToWork && $assign_record['communityAutoToWork'] = $communityAutoToWork;
            $workOrderConfig['village_id'] = $village_id;
            $communityConfig && $workOrderConfig['community_config'] = json_encode($communityConfig,JSON_UNESCAPED_UNICODE);
            $repairCateConfig && $workOrderConfig['repair_cate_config'] = json_encode($repairCateConfig,JSON_UNESCAPED_UNICODE);
            isset($assignRecord['not_report_switch']) && $workOrderConfig['not_report_switch'] = intval($assignRecord['not_report_switch']);
            $workOrderConfig['quality_start_time'] = isset($repairCateConfig['quality_start_time']) ? intval($repairCateConfig['quality_start_time']) : 0;
            $workOrderConfig['quality_end_time'] = isset($repairCateConfig['quality_end_time']) ? intval($repairCateConfig['quality_end_time']) : 0;
        }
        $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$village_id],'property_id');
        
        $repairCateScheduling = [];
        if ($toWorkId > 0) {
            $worker_id = $toWorkId;
        } else {
            if (isset($other_village_id) && $other_village_id > 0 && isset($other_village_cate_id) && $other_village_cate_id > 0) {
                $worker_ids = $service_house_new_repair->getWorkerByTime($other_village_cate_id, $repairCateScheduling);
            } else {
                $worker_ids = $service_house_new_repair->getWorkerByTime($cat_id, $repairCateScheduling);
            }
            if(!empty($worker_ids)){
                $index = array_rand($worker_ids);
                $worker_id = $worker_ids[$index];
            }else{
                $worker_id = 0;
            }
        }
        $repairCateScheduling && $assign_record['repairCateScheduling'] = $repairCateScheduling;
        
        $postData['village_id'] = $village_id;
        $postData['property_id'] = $village_info['property_id'];
        $postData['category_id'] = 0;
        $postData['type_id'] = 0;
        $postData['cat_fid'] = $category_id;
        $postData['cat_id'] = $cat_id;
        $postData['label_txt'] = $label_txt_value;
        $postData['order_content'] = $order_content;
        $postData['order_imgs'] = $order_imgs_value;
        $postData['order_type'] = 0;
        $postData['uid'] = $bind_info['uid']?$bind_info['uid']:0;
        $postData['bind_id'] = $bind_id;
        $postData['name'] = $bind_info['name']?$bind_info['name']:'';
        $postData['phone'] = $bind_info['phone']?$bind_info['phone']:'';
        $postData['single_id'] = $bind_info['single_id']?$bind_info['single_id']:0;
        $postData['floor_id'] = $bind_info['floor_id']?$bind_info['floor_id']:0;
        $postData['layer_id'] = $bind_info['layer_id']?$bind_info['layer_id']:0;
        $postData['room_id'] = $bind_info['vacancy_id']?$bind_info['vacancy_id']:'';
        $videos && $postData['videos'] = $videos;
        $postData['go_time'] = strtotime($go_time);
        if (!empty($postData['go_time']) && $postData['go_time'] < time()) {
            throw new \InvalidArgumentException('上门时间不能小于当前时间');
        } elseif (empty($postData['go_time'])) {
            $postData['go_time'] = 0;
        }
        $nowTime = time();
        if($worker_id){
            $postData['event_status'] = 20;
            $postData['now_role'] = 3;
        } else{
            $postData['now_role'] = 2;
            $postData['event_status'] = 10;
        }
        $postData['worker_id'] = $worker_id;
        $postData['add_time'] = $nowTime;
        $postData['update_time'] = $nowTime;
        try{
            $order_id = $service_house_new_repair->addRepairOrder($postData);
            if($order_id){
                $logData['order_id'] = $order_id;
                $logData['log_name'] = 'user_submit';
                $logData['log_operator'] = $bind_info['name']?$bind_info['name']:'';
                $logData['log_phone'] = $bind_info['phone']?$bind_info['phone']:'';
                $logData['operator_type'] = 10;
                $logData['operator_id'] = $bind_id;
                $logData['log_uid'] = $bind_info['uid']?$bind_info['uid']:0;
                $logData['log_content'] = $order_content;
                $logData['log_imgs'] = $postData['order_imgs'];
                $logData['add_time'] = $nowTime;
                $logData['village_id'] = $village_id;
                $logData['property_id'] = $village_info['property_id'];
                $otherData='';
                if($worker_id<1){
                    $otherData=array('opt_type'=>'order_worker_submit','wid'=>0,'village_id'=>$village_id);
                }
                $this->addLog($service_house_new_repair, $logData, $otherData);
                if ($jz_open && $other_village_id > 0) {
                    $logData = [];
                    $logData['order_id'] = $order_id;
                    $logData['log_name'] = 'to_supply_station';
                    $logData['log_operator'] = '';
                    $logData['log_phone'] = '';
                    $logData['operator_type'] = 30;
                    $logData['operator_id'] = 0;
                    $logData['log_uid'] = 0;
                    $logData['log_content'] = '自动转单到供暖站';
                    $logData['log_imgs'] = '';
                    $logData['add_time'] = $nowTime;
                    $logData['village_id'] = $village_id;
                    $logData['property_id'] = $village_info['property_id'];
                    $logData['to_station_village_id'] = $other_village_id;
                    $service_house_new_repair->addRepairLog($logData);
                }
                if($worker_id){
                    // 查询处理人信息
                    $where_house_work = [];
                    $where_house_work[] = ['wid','=',$worker_id];
                    $house_work = $db_house_worker->get_one($where_house_work,'wid,name,phone');
                    $logData = [];
                    $logData['order_id'] = $order_id;
                    $logData['log_name'] = 'house_auto_assign';
                    $logData['log_operator'] = $house_work['name']?$house_work['name']:'';
                    $logData['log_phone'] = $house_work['phone']?$house_work['phone']:'';
                    $logData['operator_type'] = 10;
                    $logData['operator_id'] = 0;
                    $logData['log_uid'] = $house_work['wid']?$house_work['wid']:0;
                    $logData['log_content'] = '';
                    $logData['log_imgs'] = '';
                    $logData['add_time'] = $nowTime;
                    $logData['village_id'] = $village_id;
                    $logData['property_id'] = $village_info['property_id'];
                    $otherData=array('opt_type'=>'assign_to_house_worker','wid'=>$worker_id,'village_id'=>$village_id);
                    $this->addLog($service_house_new_repair, $logData, $otherData);
                }
                if ($jz_open) {
                    if (isset($communityConfig['not_timely_report_switch'])) {
                        $not_timely_report_switch = intval($communityConfig['not_timely_report_switch']);
                        $isPublic = false;
                        if (isset($postData['address_type']) && $postData['address_type'] == 'public') {
                            $isPublic = true;
                        } elseif (isset($postData['public_id']) && intval($postData['public_id']) > 0) {
                            $isPublic = true;
                        }
                        if ($not_timely_report_switch == 0 && $isPublic) {
                            $noticeArr = [
                                'village_id' => $village_id, 'works_order_id' => $order_id,
                            ];
                            $this->notify(function () use ($noticeArr) { (new HouseCommunityHandleNoticeService())->tryHandlePublicToReportBox($noticeArr); });
                        }
                    }
                    $assign_record && $workOrderConfig['assign_record'] = json_encode($assign_record,JSON_UNESCAPED_UNICODE);
                    $workOrderConfig['works_order_id'] = $order_id;
                    $workOrderConfig['add_time'] = $nowTime;
                    $serviceHouseCommunityWorkOrder = new HouseCommunityWorkOrderService();
                    $configId = $serviceHouseCommunityWorkOrder->addHouseCommunityWorkOrderConfig($workOrderConfig);
                    if($worker_id){
                        $workRecord = [
                            'worker_id' => $worker_id, 'village_id' => $village_id, 'property_id' => $village_info['property_id'],
                            'works_order_id' => $order_id, 'latest_receive_time' => $nowTime
                        ];
                        $serviceHouseCommunityWorkOrder->handleWorkOrderWorkRecord($workRecord);
                    }
                    if ($configId && !empty($communityConfig)) {
                        $workName = isset($house_work['name']) && $house_work['name'] ? trim($house_work['name']) : '';
                        $workPhone = isset($house_work['phone']) && $house_work['phone'] ? trim($house_work['phone']) : '';
                        $handleParams = [
                            'configId' => $configId, 'village_id' => $village_id, 'communityConfig' => $communityConfig,
                            'nowTime' => $nowTime, 'worker_id' => $worker_id, 'workName' => $workName, 'workPhone' => $workPhone,
                            'works_order_id' => $order_id,
                        ];
                        $this->notify(function () use ($serviceHouseCommunityWorkOrder, $handleParams) { $serviceHouseCommunityWorkOrder->handleIncreaseWorkOrderConfig($handleParams); });
                    }
                    if ($worker_id) {
                        // todo 处理延时推送问题
                    }
                }
                if($postData['worker_id'] > 0){
                    //todo 用户发起工单 发送模板消息
                    $this->notify(function () use ($service_house_new_repair, $order_id, $village_info) { $service_house_new_repair->newRepairSendMsg(10, $order_id, 0, $village_info['property_id']); });
                } else {
                    //todo 开启抢单模式
                    $this->notify(function () use ($service_house_new_repair, $village_id, $order_id, $cat_id) { $service_house_new_repair->grabOrder($village_id, $order_id, $cat_id); });
                }
            }
        }catch (\Exception $e){
            fdump_api(['执行错误'=>$e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()],'$eMessage',true);
            throw $e;
        }
        if(in_array($postData['event_status'],[40,41,42,43,70])){
            $status='todo';
        }elseif (in_array($postData['event_status'],[10,39])){
            $status='processing';
        }elseif (in_array($postData['event_status'],[50,69])){
            $status='processed';
        }else{
            $status='all';
        }
        if (!$order_id) { throw new \RuntimeException('创建工单失败'); }
        return ['order_id'=>(int)$order_id, 'status'=>$status];
    }
}
