<?php


namespace app\community\controller\village_api\User;

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

class RepairApiController extends BaseController
{
    public $status_desc = [
        ['status' => 'todo', 'name' => '待处理'],
        ['status' => 'processing', 'name' => '处理中'],
        ['status' => 'processed', 'name' => '已处理'],
        ['status' => 'all', 'name' => '全部'],
    ];

    /**
     * 工单状态
     * @author lijie
     * @date_time 2021/08/13
     * @return \json
     */
    public function getType()
    {
        $type_list = $this->status_desc;
        return api_output(0,$type_list);
    }
    public function getConfigCustomization()
    {
        $village_id = $this->request->post('village_id',0);
        $datas=array();
        try{
            $configCustomizationService=new ConfigCustomizationService();
            $jz_wsxdsj_net_customized_open=$configCustomizationService->getWSXCustomizedOpen();
            $datas['jz_wsxdsj_open']=$jz_wsxdsj_net_customized_open ? 1:0;
            return api_output(0,$datas);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
    }
    /**
     * 工单类目列表
     * @author lijie
     * @date_time 2021/08/12
     * @return \json
     */
    public function getSubject()
    {
        $village_id = $this->request->post('village_id',0);
        if(!$village_id)
            return api_output_error(1001,'缺少必传参数');
        $where[] = ['village_id','=',$village_id];
        $where[] = ['status','=',1];
        $where[] = ['parent_id','=',0];
//        $field = 'subject_name,id as category_id,color';
        $field = 'cate_name as subject_name,id as category_id,color';
        $service_house_new_repair = new HouseNewRepairService();
        try{
//            $data = $service_house_new_repair->getRepairSubject($where,$field,0,0,'id DESC');
            $data = (new RepairCateService())->getNewRepairCate($where,$field,0,0,'id DESC');
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 工单列表
     * @author lijie
     * @date_time 2021/08/12
     */
    public function getOrderList()
    {
        $village_id = $this->request->post('village_id',0);
        $subject_id = $this->request->post('category_id',0);
        $type = $this->request->post('type','all');
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        $name = $this->request->post('name','');
        $phone = $this->request->post('phone','');
        $event_status = $this->request->post('event_status',0);
        $pigcms_id = $this->request->post('pigcms_id',0);
        if(!$village_id || !$subject_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $where[] = ['o.village_id','=',$village_id];
        $where[] = ['o.cat_fid','=',$subject_id];
        $roomIds = [];
        if($pigcms_id){
            // 获取房屋id
            $vacancy = new HouseVillageUserVacancy();
            $room_ids = $vacancy->getRoomUserList(array(['a.village_id', '=', $village_id],['b.pigcms_id','=', $pigcms_id]),'a.pigcms_id');
            if(!$room_ids->isEmpty()){
                $roomIds = array_column($room_ids->toArray(),'pigcms_id');
            }
        }
        if($type == 'todo') {
            $where[] = ['o.event_status','in',[35,40,41,42,43]];
        }
        elseif($type == 'processing') {
            $where[] = ['o.event_status','between',[10,39]];
        }
        elseif ($type == 'processed') {
            $where[] = ['o.event_status','between',[50,70]];
        }
        if($name) {
            $where[] = ['o.name','=',$name];
        }
        if($phone) {
            $where[] = ['o.phone','in',$phone];
        }
        if($event_status) {
            $where[] = ['o.event_status','=',$event_status];
        }
        $field='o.order_content,o.add_time,o.event_status,o.order_id,s.subject_name,o.type_id,o.cat_fid,o.cat_id,o.address_txt,o.name,o.phone,o.label_txt';
        $order='o.order_id DESC';
        $service_house_new_repair = new HouseNewRepairService();
        try{
            $data = $service_house_new_repair->getOrderList($where,$field,$page,$limit,$order,$pigcms_id,$roomIds);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 分类选择页
     * @author lijie
     * @date_time 2021/08/12
     * @return \json
     */
    public function getCateList()
    {
        $subject_id = $this->request->post('category_id',0);
        $village_id = $this->request->post('village_id',0);
        if(!$subject_id || !$village_id)
            return api_output_error(1001,'缺少必传参数');
        $where['parent_id'] = $subject_id;
        $where['status'] = 1;
        $where['village_id'] = $village_id;
        $field='subject_name,id as type_id';
        $service_house_new_repair = new HouseNewRepairService();
        try{
            $data = $service_house_new_repair->getCateList($where,$field);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 工单分类
     * @author lijie
     * @date_time 2021/08/12
     * @return \json
     */
    public function getFidCate()
    {
        $cat_fid = $this->request->post('cat_fid',0);
        if(!$cat_fid)
            return api_output_error(1001,'缺少必传参数');
        $where['parent_id'] = $cat_fid;
        $where['status'] = 1;
        $field='id as cat_id,cate_name';
        $service_house_new_repair = new HouseNewRepairService();
        try{
            $data = $service_house_new_repair->getFidCateList($where,$field);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 描述标签列表
     * @author lijie
     * @date_time 2021/08/12
     * @return \json
     */
    public function getLabel()
    {
        $cate_id = $this->request->post('cat_id',0);
        if(!$cate_id)
            return api_output_error(1001,'缺少必传参数');
        $where['cate_id'] = $cate_id;
        $where['status'] = 1;
        $field='id,name';
        $order='sort DESC';
        $service_house_new_repair = new HouseNewRepairService();
        try{
            $data = $service_house_new_repair->getLabel($where,$field,$order);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 工单历史详情
     * @author lijie
     * @date_time 2021/08/13
     */
    public function getRepairHistory()
    {
        $order_id = $this->request->post('order_id',0);
        if(!$order_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $where['order_id'] = $order_id;
        $field = 'log_name,log_operator,log_phone,operator_type,log_content,log_imgs,add_time';
        $service_house_new_repair = new HouseNewRepairService();
        try{
            $data = $service_house_new_repair->getRepairLog($where,$field);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * Notes: 获取工单操作记录
     * @author: wanzy
     * @date_time: 2021/8/19 13:24
     */
    public function repairGetOrderLog() {
        $order_id = $this->request->post('order_id',0);
        if(!$order_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $arr = [];
            $house_new_repair_works_order_service = new HouseNewRepairService();
            $system_type = $this->request->post('system_type','');
            $params = ['system_type' => $system_type];
            $log_list = $house_new_repair_works_order_service->getRepairLog($order_id, $params);
            $arr['log'] = $log_list;
            return api_output(0, $arr);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 上传图片
     * @author lijie
     * @date_time 2021/08/13
     */
    public function upload(){
        $file = $this->request->file('imgFile');
        try {
            // 验证
//            validate(['imgFile' => [
//                'fileSize' => 1024 * 1024 * 10,   //10M
//                'fileExt' => 'jpg,png,jpeg,gif,ico',
//                'fileMime' => 'image/jpeg,image/jpg,image/png,image/gif,image/x-icon', //这个一定要加上，很重要！
//            ]])->check(['imgFile' => $file]);
            // 上传到本地服务器
            $savename = \think\facade\Filesystem::disk('public_upload')->putFile('work_order', $file);
            if (strpos($savename, "\\") !== false) {
                $savename = str_replace('\\', '/', $savename);
            }
            $imgurl = '/upload/' . $savename;
            $data = [];
            $data['imageUrl_path'] = $imgurl;
//        $data['imageUrl'] = replace_file_domain($imgurl);
            $data['imageUrl'] = cfg('site_url') . $imgurl;
            $data['url'] = thumb_img($data['imageUrl'], '200', '200');
            $params = ['savepath'=>'/upload/' . $imgurl];

            $file_handle = new FileHandle();
            if($file_handle->check_open_oss()) {
                invoke_cms_model('Image/oss_upload_image', $params);
            }
            return api_output(0, $data, "成功");
        }catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
    }

    /**
     * 提交工单
     * @author lijie
     * @date_time 2021/08/13
     */
    public function addUserRepairOrder()
    {
        $service_house_village = new HouseVillageService();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_new_repair = new HouseNewRepairService();
        $db_house_worker = new HouseWorker();
        $village_id = $this->request->post('village_id',0);
        $category_id = $this->request->post('category_id',0);
        $type_id = $this->request->post('type_id',0);
        $cat_fid = $this->request->post('cat_fid',0);
        $cat_id = $this->request->post('cat_id',0);
        $label_txt = $this->request->post('label_txt','');
        $order_content = $this->request->post('order_content','');
        $order_imgs = $this->request->post('order_imgs',[]);
        $videos = $this->request->post('videos',[]);
        $bind_id = $this->request->post('pigcms_id',0);
        if (!$village_id || !$bind_id || !$category_id || !$cat_id || (empty($label_txt) && empty($order_imgs) && empty($order_content))) {
            return api_output_error(1001, '缺少必传参数');
        }
        $address_type = $this->request->post('address_type','','trim');
        $address_id = $this->request->post('address_id',0);
        $go_time = $this->request->post('go_time','');

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
                return api_output_error(1001,'上传视频数量不能超过'.$uploadVideoMax.'个');
            }
            if ($is_pic_video == 1 && empty($videos) && empty($order_imgs_value)) {
                return api_output_error(1001,'图片或者视频必须上传最少1个');
            }
        }
        
        $add_status=$service_house_new_repair->checkWorkOrder($village_id);
        if ($add_status && ($add_status['is_night']==1)){
            return api_output_error(1001,'你好，当前是晚班时间，创建工单，请拨打工作人员电话');
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
        $cate_info = $service_house_new_repair->getCateInfo(['id'=>$type_id],'cate_name');
        if($cate_info['cate_name'] == '个人报修' && !$go_time){
            return api_output_error(1001,'缺少上门时间');
        }
        $to_supply_station = 0;
        $other_village_id = 0;
        $other_village_cate_id = 0;
        $other_village_cate_fid = 0;
        if($cat_id>0){
            $field = 'id,subject_id,cate_name,parent_id,type,uid,to_supply_station,other_village_id,other_village_cate_id,other_village_cate_fid';
            $cate_info = $service_house_new_repair->getCateInfo(['id' => $cat_id,'village_id'=>$village_id], $field);
            if (!isset($cate_info['id']) || !$cate_info['id']) {
                return api_output_error(1001,'对应分类信息不存在');
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
                return api_output_error(1001,$msg);
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
        $is_timely=$service_house_village->checkVillageField($village_id,'is_timely');
        if ($is_timely==1){
            if (empty($go_time)){
                return api_output_error(1001,'上门时间不能为空');
            }else{
                $postData['go_time'] = strtotime($go_time);
                if (empty($postData['go_time'])){
                    return api_output_error(1001,'上门时间不能为空');
                }
                if ($postData['go_time']<time()){
                    return api_output_error(1001,'上门时间不能小于当前时间');
                }
            }
        }else{
            $postData['go_time'] = strtotime($go_time);
            if (!empty($postData['go_time'])&&$postData['go_time']<time()){
                return api_output_error(1001,'上门时间不能小于当前时间');
            } elseif(empty($postData['go_time'])){
                $postData['go_time']=0;
            }
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
                $service_house_new_repair->addRepairLog($logData,$otherData);
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
                    $service_house_new_repair->addRepairLog($logData,$otherData);
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
                            (new HouseCommunityHandleNoticeService())->tryHandlePublicToReportBox($noticeArr);
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
                        $serviceHouseCommunityWorkOrder->handleIncreaseWorkOrderConfig($handleParams);
                    }
                    if ($worker_id) {
                        // todo 处理延时推送问题
                    }
                }
                if($postData['worker_id'] > 0){
                    //todo 用户发起工单 发送模板消息
                    $service_house_new_repair->newRepairSendMsg(10, $order_id, 0, $village_info['property_id']);
                } else {
                    //todo 开启抢单模式
                    $service_house_new_repair->grabOrder($village_id, $order_id, $cat_id);
                }
            }
        }catch (\Exception $e){
            fdump_api(['执行错误'=>$e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()],'$eMessage',true);
            return api_output_error(-1,$e->getMessage());
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
        return api_output(0,['info'=>['status'=>$status]]);
    }

    /**
     * 查询公共区域和楼栋列表
     * @author:zhubaodi
     * @date_time: 2021/4/25 13:27
     */
    public function getHousePosition()
    {
        /*$arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }*/
        $village_id = $this->request->post('village_id',0);
        if(!$village_id) {
            return api_output_error(1001,'必传参数缺失');
        }
        // 查询楼栋
        $village_service = new HouseVillageService();
        try {
            $list = $village_service->getHousePosition($village_id);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * Notes: 获取对应子集信息
     * @return array|\json
     * @author: wanzy
     * @date_time: 2021/8/13 17:01
     */
    public function getHousePositionChidren() {
        $village_id = $this->request->post('village_id',0);
        if(!$village_id) {
            return api_output_error(1001,'必传参数缺失');
        }
        $id = $this->request->post('id',0);
        if(!$id) {
            return api_output_error(1001,'必传参数缺失');
        }
        $type = $this->request->post('type',0);
        if(!$type) {
            return api_output_error(1001,'必传参数缺失');
        }
        // 查询楼栋
        $village_service = new HouseVillageService();
        try {
            $list = $village_service->getHousePositionChildren($village_id,$id,$type);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        $arr = [];
        $arr['list'] = $list;
        return api_output(0,$list);
    }

    /**
     * 工单详情
     * @author lijie
     * @date_time 2021/08/17
     */
    public function getWorkOrderDetail()
    {
        $order_id = $this->request->post('order_id',0);
        $system_type = $this->request->post('system_type','');
        if(!$order_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $service_house_new_repair = new HouseNewRepairService();
        try{
            if ($system_type == 'village') {
                $userId = trim($this->request->log_uid);
                $login_role = trim($this->request->log_extends);
                $adminLoginService = new AdminLoginService();
                $user = $adminLoginService->handleLoginData($userId, $login_role);
                $params = [
                    'village_id' => isset($user['village_id']) ? intval($user['village_id']) : 0,
                ];
            } else {
                $params = [];
            }
            $data = $service_house_new_repair->getWorkOrderDetail(['order_id' => $order_id], '*', $system_type, 'user', $params);
        } catch (\Exception $e) {
            fdump_api(
                ['errLine' => $e->getLine(), 'errMessage' => $e->getMessage(), 'errFile' => $e->getFile()], '$getMessage'
            );
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 修改订单
     * @author lijie
     * @date_time 2021/08/17
     */
    public function updateWorkOrder()
    {
        $event_status_type = $this->request->post('event_status_type','');
        $order_id = $this->request->post('order_id',0);
        $app_type = $this->request->post('app_type','');
        $log_content = $this->request->post('log_content','');
        $log_imgs = $this->request->post('log_imgs',[]);
        $evaluate = $this->request->post('evaluate',0);
        $bind_id = $this->request->post('pigcms_id',0);
        $pay_type = $this->request->post('pay_type',0);
        $offline_pay_type = $this->request->post('offline_pay_type',0);
        if(empty($event_status_type) || !$order_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        if($event_status_type == 'evaluate' && !$evaluate){
            return api_output_error(1001,'请填写评分');
        }
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_village = new HouseVillageService();
        $bind_info = $service_house_village_user_bind->getBindInfo(['pigcms_id' => $bind_id], 'uid,name,phone,pigcms_id,village_id');
        if ($bind_info && !$bind_info->isEmpty()) {
            $village_info = $service_house_village->getHouseVillageInfo(['village_id' => $bind_info['village_id']], 'property_id');
            $bind_info['property_id'] = $village_info['property_id'];
        }

        $service_house_new_repair = new HouseNewRepairService();
        $result = [];
        try {
            $params = [
                'app_type' => $app_type
            ];
            $resultLogArr = $service_house_new_repair->updateWorkOrder($order_id, $event_status_type, $log_content, $log_imgs, $evaluate, $bind_info, $pay_type, $offline_pay_type, $params);
            $result = $resultLogArr;
            $type = isset($resultLogArr['type']) ? $resultLogArr['type'] : '';
            $result['log'] = ['status'=>$type];
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$result);
    }

    /**
     * 线下支付列表
     * @return \json
     * @author lijie
     * @date_time 2021/06/28
     */
    public function getOfflineList()
    {
        $village_id = $this->request->param('village_id',0);
        if(!$village_id) {
            return api_output_error(1001,'必传参数缺失');
        }
        $service_house_new_offline = new HouseNewPorpertyService();
        $service_house_village = new HouseVillageService();
        $village_info = $service_house_village->getHouseVillageInfo(['village_id' => $village_id], 'property_id');
        $where['property_id'] = $village_info['property_id'];
        $where['status'] = 1;
        $data = $service_house_new_offline->getOfflineList($where, 'id,name');
        return api_output(0, $data);
    }

    /**
     * 校验白晚班是否可提交工单
     * @return \json
     * @author lijie
     * @date_time 2021/06/28
     */
    public function checkWorkOrder()
    {

        $village_id = $this->request->param('village_id',0);
        if(!$village_id) {
            return api_output_error(1001,'必传参数缺失');
        }
        $service_house_new_repair = new HouseNewRepairService();
        try{
            $data = $service_house_new_repair->checkWorkOrder($village_id);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 获取住户地址
     * @author:zhubaodi
     * @date_time: 2022/4/29 9:41
     */
    public function getUserAddress(){
        $village_id = $this->request->param('village_id',0);
        if(!$village_id) {
            return api_output_error(1001,'必传参数缺失');
        }
        $pigcms_id = $this->request->param('pigcms_id',0);
        if(!$pigcms_id) {
            return api_output_error(1001,'必传参数缺失');
        }

        $service_house_new_repair = new HouseNewRepairService();
        try{
            $data = $service_house_new_repair->getUserAddress($village_id,$pigcms_id);
            $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
            $data['jz_open'] = $jz_open ? 1 : 0;
            $data['uploadVideo'] = 0;
            $data['is_pic_video']=0;
            if ($jz_open) {
                $data['uploadVideo'] = 1;
                $data['uploadVideoMax'] = WorkOrderConst::WORK_ORDER_UPLOAD_VIDEO_MAX;
                $configInfo = (new HouseCommunityAboutService())->getHouseCommunityConfig($village_id);
                $data['is_pic_video'] = isset($configInfo['is_pic_video']) ? intval($configInfo['is_pic_video']) : 0;
            }
            $data['uploadImageMax'] = WorkOrderConst::WORK_ORDER_UPLOAD_IMAGE_MAX;
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    public function getRoomCommunityQualityInfo()
    {
        $village_id = $this->request->param('village_id',0);
        if(!$village_id) {
            return api_output_error(1001,'必传参数小区ID缺失');
        }
        $pigcms_id = $this->request->param('pigcms_id',0);
        if(!$pigcms_id) {
            return api_output_error(1001,'必传参数住户身份ID缺失');
        }
        $result = [];
        try {
            $params = [
                'property_id' => 0,
                'village_id' => $village_id,
                'room_id' => 0,
                'pigcms_id' => $pigcms_id,
                'isReturnObj' => true,
                'is_show'=>1,
            ];
            $result= (new HouseCommunityQualityService())->getRoomCommunityQualityInfo($params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
}