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
        try {
            $result = (new \app\community\model\service\ResidentRepairSubmissionService())->create($this->request->post());
            return api_output(0, ['info'=>['status'=>$result['status']]]);
        } catch (\InvalidArgumentException $e) {
            return api_output_error(1001, $e->getMessage());
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
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
