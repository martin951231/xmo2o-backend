<?php


namespace app\community\controller\village_api;


use app\common\model\service\config\ConfigCustomizationService;
use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseNewRepairService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\RepairCateService;
use app\community\model\service\workorder\HouseCommunityAboutService;
use app\consts\WorkOrderConst;

class RepairCenterController extends CommunityBaseController
{
    /**
     * 工单列表
     * @author lijie
     * @date_time 2021/08/18
     */
    public function getOrderList()
    {
        $village_id = $this->adminUser['village_id'];
        $page = $this->request->post('page',1);
        $category_id = $this->request->post('category_id',0);
        $type_id = $this->request->post('type_id',0);
        $cat_fid = $this->request->post('cat_fid',0);
        $cat_id = $this->request->post('cat_id',0);
        $type = $this->request->post('type','name');
        $con = $this->request->post('search','');
        $single_id = $this->request->post('single_id',0);
        $floor_id = $this->request->post('floor_id',0);
        $layer_id = $this->request->post('layer_id',0);
        $room_id = $this->request->post('room_id',0);
        $room_ids = $this->request->post('room_ids',0);
        $start_time = $this->request->post('start_time',0);
        $end_time = $this->request->post('end_time',0);
        $event_status = $this->request->post('event_status',0);
        $public_id = $this->request->post('public_id',0,'int');
        $village_ids = $this->request->post('village_id',0,'int');
        $limit = 8;
        if(!$village_id && $village_ids){
            $village_id= $village_ids;
        }
        if(!$village_id){
            return api_output_error(1001,'缺少必传参数');
        }
        $configCustomizationService=new ConfigCustomizationService();
        $jz_open = $configCustomizationService->getWSXCustomizedOpen();
        $where=array();
        if ($jz_open) {
            $where[] = ['o.village_id|o.now_handle_village_id','=',$village_id];
        } else {
            $where[] = ['o.village_id','=',$village_id];
        }
        if($category_id){
            //            $where[] = ['o.category_id','=',$category_id];
        }
        if($cat_id)
            $where[] = ['o.cat_id','=',$cat_id];
        if($cat_fid){
            $where[] = ['o.cat_fid','=',$cat_fid];
        }
        if($public_id>0){
            $where[] = ['o.public_id','=',$public_id];
        }
        if($con){
            if($type == 'name') {
                $where[] = ['o.name','like','%'.$con.'%'];
            }
            elseif($type == 'phone') {
                $where[] = ['o.phone','like','%'.$con.'%'];
            }
        }
        if($type == 'address'){
            if($single_id && !empty($room_ids)) {
                $where[] = ['o.single_id','=',$single_id];
            }
            if($layer_id && !empty($room_ids)) {
                $where[] = ['o.layer_id','=',$layer_id];
            }
            if($floor_id && !empty($room_ids)) {
                $where[] = ['o.floor_id','=',$floor_id];
            }
            if($room_id && !empty($room_ids)) {
                $where[] = ['o.room_id','=',$room_id];
            }
        }
        if($start_time && !$end_time) {
            $where[] = ['o.add_time','>=',strtotime($start_time)];
        }
        if(!$start_time && $end_time) {
            $where[] = ['o.add_time','<=',strtotime($end_time.' 23:59:29')];
        }
        if($start_time && $end_time) {
            $where[] = ['o.add_time','between',[strtotime($start_time),strtotime($end_time.' 23:59:29')]];
        }
        if($event_status){
            switch ($event_status) {
                case '10':
                    $where[]=[ 'o.event_status','in',[10,11,14]];
                    break;
                case '20':
                    $where[]=[ 'o.event_status','in',[20,21,22,23,24,25]];
                    break;
                case '30':
                    $where[]=[ 'o.event_status','in',[30,34]];
                    break;
                case '40':
                    $where[]=[ 'o.event_status','in',[40,41,42,43]];
                    break;
                case '16':
                    $where[]=[ 'o.event_status','in',[16,26]];
                    break;
                case '17':
                    $where[]=[ 'o.event_status','in',[17,27]];
                    break;
                case 'timely_1':
                    $where[]=[ 'o.is_status','=',1];
                    break;
                default:
                    $where[] = ['o.event_status','=',$event_status];
                    break;
            }
        }
        $islantiancom = $configCustomizationService->getFuncZhihuilantiancomSpecial();
        
        $field='o.*';
        $order='o.add_time DESC,o.order_id DESC';
        $service_house_new_repair = new HouseNewRepairService();
        try{
            $params = ['source_type' => 'village', 'village_id' => $village_id, 'jz_open' => $jz_open];
            $data = $service_house_new_repair->getOrderList($where,$field,$page,$limit,$order,0,[],$params);
            $count = $service_house_new_repair->getOrderCount($where);
            $res['list'] = $data;
            $res['total'] = $count;
            $res['limit'] = $limit;
            $res['jz_open'] = $jz_open ? 1 : 0;
            $res['uploadVideo'] = 0;
            $res['is_lantiancom']=$islantiancom;
            if ($jz_open) {
                $res['uploadVideo'] = 1;
                $res['uploadVideoMax'] = WorkOrderConst::WORK_ORDER_UPLOAD_VIDEO_MAX;
                $configInfo = (new HouseCommunityAboutService())->getHouseCommunityConfig($village_id);
                $res['is_pic_video'] = isset($configInfo['is_pic_video']) ? intval($configInfo['is_pic_video']) : 0;
            }
            $res['uploadImageMax'] = WorkOrderConst::WORK_ORDER_UPLOAD_IMAGE_MAX;
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$res);
    }

    public function getRepairOrderEvaluateList()
    {
        $village_id = $this->adminUser['village_id'];
        $page = $this->request->post('page', 1);
        $evaluate_star = $this->request->post('evaluate_star', 1, 'int');  // 评价星
        $operator_id = $this->request->post('operator_id', 0, 'int');  //评价者id

        $limit = 10;
        $where = array();
        $where[] = ['o.village_id', '=', $village_id];
        $where[] = ['l.log_name', '=', 'evaluate'];
        $where[] = ['l.operator_id', '=', $operator_id];
        $where[] = ['l.evaluate', '=', $evaluate_star];
        $field = 'o.*';
        $order = 'o.order_id DESC';
        $service_house_new_repair = new HouseNewRepairService();
        try {
            $data = $service_house_new_repair->getOrderEvaluateList($where, $field, $page, $limit, $order,$evaluate_star);
            $count = $service_house_new_repair->getCountByEvaluate($where);
            $res['list'] = $data;
            $res['total'] = $count;
            $res['limit'] = $limit;
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res);
    }
    
    /**
     * 今日上报/回复/处理工单
     * @author lijie
     * @date_time 2021/08/18
     */
    public function getTongji()
    {
        $village_id = $this->adminUser['village_id'];
        if(!$village_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $service_house_new_repair = new HouseNewRepairService();
        $time = strtotime(date('Y-m-d'));
        $submit_count = $service_house_new_repair->getLogCount([['village_id','=',$village_id],['log_name','in',['user_submit','property_admin_submit','property_work_submit','house_admin_submit','house_work_submit','backstage_work_submit']],['add_time','>=',$time]]);
        $reply_count = $service_house_new_repair->getLogCount([['village_id','=',$village_id],['log_name','in',['property_admin_reply','property_work_reply','house_admin_reply','center_reply_submit','work_reply_user']],['add_time','>=',$time]]);
        $handle_count = $service_house_new_repair->getLogCountByOrderId([['village_id','=',$village_id],['log_name','in',['submit_center','work_reply_user','work_follow_up','work_completed','center_assign_work']],['add_time','>=',$time]]);
        $timely_count=0;  //超时单子
        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        if ($jz_open) {
            $whereArr=array('village_id|now_handle_village_id'=>$village_id,'is_status'=>1);
        } else {
            $whereArr=array('village_id'=>$village_id,'is_status'=>1);
        }
        $timely_count=$service_house_new_repair->getOrderCount($whereArr);
        $timely_count=$timely_count>0 ? $timely_count:0;
        return api_output(0,['submit_count'=>$submit_count,'reply_count'=>$reply_count,'handle_count'=>$handle_count,'timely_count'=>$timely_count]);
    }

    /**
     * 物业评分
     * @author lijie
     * @date_time 2021/08/18
     */
    public function getPropertyRating()
    {
        $village_id = $this->adminUser['village_id'];
        $type = $this->request->post('type',0);
        if(!$village_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $service_house_new_repair = new HouseNewRepairService();
        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        if ($jz_open) {
            $where1 = ['l.village_id|o.now_handle_village_id'=>$village_id,'l.log_name'=>'work_completed'];
        } else {
            $where1 = ['l.village_id'=>$village_id,'l.log_name'=>'work_completed'];
        }
        $worker_ids = $service_house_new_repair->getWorkers($where1,'o.worker_id');
        if($type){
            if($type == 1){
                $start_time=mktime(0,0,0,date('m'),date('d')-1,date('Y'));
                $end_time=mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
                $where[] = ['l.add_time','between',[$start_time,$end_time]];
            }
            if($type == 2){
                $date = date("Y-m-d");  //当前日期
                $first=1; //$first =1 表示每周星期一为开始时间 0表示每周日为开始时间
                $w = date("w", strtotime($date));  //获取当前周的第几天 周日是 0 周一 到周六是 1 -6
                $d = $w ? $w - $first : 6;  //如果是周日 -6天
                $now_start = date("Y-m-d", strtotime("$date -".$d." days")); //本周开始时间
                $start_time = strtotime("$now_start - 7 days");  //上周开始时间
                $end_time = strtotime("$now_start - 1 days");  //上周结束时间
                $where[] = ['l.add_time','between',[$start_time,$end_time]];
            }
            if($type == 3){
                $standardTime = date('Y-m-1');
                $start_time = strtotime(date('Y-m-1 00:00:00', strtotime('-1 month', strtotime($standardTime))));
                $end_time = strtotime(date('Y-m-d H:i:s', strtotime('-1 sec', strtotime($standardTime))));
                $where[] = ['l.add_time','between',[$start_time,$end_time]];
            }
            if($type ==4){
                $start_time = strtotime(date('Y',strtotime("-1 year")));
                $end_time = strtotime(date('Y-12-31 23:59:59',strtotime("-1 year")));
                $where[] = ['l.add_time','between',[$start_time,$end_time]];
            }
        }
        if ($jz_open) {
            $where[]=['l.village_id|o.now_handle_village_id','=',$village_id];
        } else {
            $where[]=['l.village_id','=',$village_id];
        }
        $where[]=['o.worker_id','in',$worker_ids];
        $where[]=['l.evaluate','>',0];
        $field='avg(l.evaluate) as avg_evaluate,l.log_operator,l.log_phone,w.name';
        $data = $service_house_new_repair->getAvg($where,$field)->toArray();
        $info = [];
        if($data){
            $info[0][0]['title'] = '排名';
            $info[0][1]['title'] = '姓名';
            $info[0][2]['title']= '服务评分';
            foreach ($data as $k=>$v){
                $optname=$v['name'];
                if(empty($optname) && !empty($v['log_operator'])){
                    $optname=$v['log_operator'];
                }
                $info[$k+1][0]['title'] = $k+1;
                $info[$k+1][1]['title'] = $optname;
                $info[$k+1][2]['title'] = $v['avg_evaluate'];
            }
        }
        $list = [
            [
                'name'=>'昨日之星',
                'type'=>1
            ],
            [
                'name'=>'上周之星',
                'type'=>2
            ],
            [
                'name'=>'上月之星',
                'type'=>3
            ],
            [
                'name'=>'去年之星',
                'type'=>4
            ]
        ];
        $res['list'] = $list;
        $res['info'] = $info;
        return api_output(0,$res);
    }

    /**
     * 工单处理数据
     * @author lijie
     * @date_time 2021/08/19
     */
    public function orderTongji()
    {
        $village_id = $this->adminUser['village_id'];
        $where[] = ['village_id','=',$village_id];
        $where[] = ['status','=',1];
        $where[] = ['parent_id','=',0];
        $field = 'cate_name as subject_name,id as category_id,color,village_id';
        $service_house_new_repair = new HouseNewRepairService();
        try{
            $data = $service_house_new_repair->orderTongji($where,$field,0,0,'id desc');
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 工单类目列表
     * @author lijie
     * @date_time 2021/08/12
     * @return \json
     */
    public function getSubject()
    {
        $village_id = $this->adminUser['village_id'];
        $id = $this->request->post('id',0);
        if(!$village_id)
            return api_output_error(1001,'缺少必传参数');
        $where[] = ['village_id','=',$village_id];
        $where[] = ['status','=',1];
        $where[] = ['parent_id','=',0];
        $field = 'cate_name as subject_name,id as category_id,color';
        $service_house_new_repair = new HouseNewRepairService();
        try{
            $data = (new RepairCateService())->getNewRepairCate($where,$field,0,0,'id desc');
//            $data = $service_house_new_repair->getRepairSubject($where,$field,0,0,'id DESC');
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 类别列表
     * @author lijie
     * @date_time 2021/08/20
     * @return \json
     */
    public function getCate()
    {
        $village_id = $this->adminUser['village_id'];
        $category_id = $this->request->post('category_id',0);
//        if($category_id)
//            $where[] = ['parent_id','=',$category_id];
//        else
//            $where[] = ['parent_id','>',0];
        $where[] = ['parent_id','=',$category_id];
        $where[] = ['village_id','=',$village_id];
        $where[] = ['status','=',1];
        $field = 'cate_name as name,id';
//        $service_house_new_repair = new HouseNewRepairService();
        try{
            $data = (new RepairCateService())->getNewRepairCate($where,$field,0,0,'id desc');
//            $data = $service_house_new_repair->getRepairSubject($where,$field,0,0,'id DESC');
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
        $type_id = $this->request->post('type_id',0);
        $type = $this->request->post('type',0);
        if(!$type_id || !$type)
            return api_output_error(1001,'缺少必传参数');
        $where[$type] = $type_id;
        if($type == 'subject_id'){
            $where['parent_id'] = 0;
        }
        $where['status'] = 1;
        $field='id,cate_name as name';
        $service_house_new_repair = new HouseNewRepairService();
        try{
            $data = $service_house_new_repair->getFidCateList($where,$field);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 工单处理中心回复
     * @author lijie
     * @date_time  2021/08/23
     */
    public function updateWorkOrder()
    {
        $village_id = $this->adminUser['village_id'];
        $event_status_type = $this->request->post('event_status_type','center_assign_work');
        $order_id = $this->request->post('order_id',0);
        $log_content = $this->request->post('log_content','');
        $worker_id = $this->request->post('worker_id',0);
        if(empty($event_status_type) || !$order_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $service_house_village = new HouseVillageService();
        $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$village_id],'property_id');
        $service_house_new_repair = new HouseNewRepairService();
        try{
            $data = $service_house_new_repair->workOrderSave($order_id,$event_status_type,$log_content,$village_id,$village_info['property_id'],$this->_uid,$worker_id);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }


    /**
     * 工单结案率查询
     * @author:zhubaodi
     * @date_time: 2021/12/10 11:17
     */
    public function getFinshOrder(){
        $village_id = $this->adminUser['village_id'];
       // $village_id=50;
        $service_house_new_repair = new HouseNewRepairService();
        try{
            $data = $service_house_new_repair->getFinshOrder($village_id);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    public function getWorkOrderEvaluateList()
    {
        $xname = $this->request->post('xname', 'trim');
        $xphone = $this->request->post('xphone', 'trim');
        $village_id = $this->adminUser['village_id'];
        $page = $this->request->param('page', '1', 'int');
        $limit = 20;
        $page = $page > 0 ? $page : 0;
        $offset = ($page - 1) * $limit;
        $whereArr = array();
        $whereArr[] = array('village_id', '=', $village_id);
        $whereArr[] = array('log_name', '=', 'evaluate');
        $whereArr[] = array('evaluate', '>', 0);
        if (!empty($xname)) {
            $whereArr[] = array('log_operator', 'like', '%' . $xname . '%');
        }
        if (!empty($xphone)) {
            $whereArr[] = array('log_phone', 'like', '%' . $xphone . '%');
        }
        $field = 'village_id,operator_id,log_uid,log_operator,log_phone,log_id,property_id,log_name,evaluate';
        try {
            $service_house_new_repair = new HouseNewRepairService();
            $list = $service_house_new_repair->getNewRepairWorksOrderLog($whereArr, $field, $page, $limit, 'operator_id');
            return api_output(0, $list);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function getUserInfo(){
        $village_id = $this->adminUser['village_id'];
        $keyV = $this->request->param('keyV','','trim');
        if ($village_id<1 || empty($keyV)){
            return api_output(0,array('userList'=>array()));
        }
        $service_house_new_repair = new HouseNewRepairService();
        try{
            $res = $service_house_new_repair->getBindUserOrWorker($village_id,$keyV);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }
    /**
     * 工单列表导出
     */
    public function excelExportRepairWorksOrder()
    {
        set_time_limit(0);
        $village_id = $this->adminUser['village_id'];
        $page = $this->request->post('page',1);
        $category_id = $this->request->post('category_id',0);
        $type_id = $this->request->post('type_id',0);
        $cat_fid = $this->request->post('cat_fid',0);
        $cat_id = $this->request->post('cat_id',0);
        $type = $this->request->post('type','name');
        $con = $this->request->post('search','');
        $single_id = $this->request->post('single_id',0);
        $floor_id = $this->request->post('floor_id',0);
        $layer_id = $this->request->post('layer_id',0);
        $room_id = $this->request->post('room_id',0);
        $room_ids = $this->request->post('room_ids',0);
        $start_time = $this->request->post('start_time',0);
        $end_time = $this->request->post('end_time',0);
        $event_status = $this->request->post('event_status',0);
        $public_id = $this->request->post('public_id',0,'int');
        $village_ids = $this->request->post('village_id',0,'int');
        $limit = 8;
        if(!$village_id && $village_ids){
            $village_id= $village_ids;
        }
        if(!$village_id){
            return api_output_error(1001,'缺少必传参数');
        }
        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        $where=array();
        if ($jz_open) {
            $where[] = ['village_id|now_handle_village_id','=',$village_id];
        } else {
            $where[] = ['village_id','=',$village_id];
        }
        if ($cat_id) {
            $where[] = ['cat_id', '=', $cat_id];
        }
        if ($cat_fid) {
            $where[] = ['cat_fid', '=', $cat_fid];
        }
        if($public_id>0){
            $where[] = ['public_id','=',$public_id];
        }
        if($con){
            if($type == 'name') {
                $where[] = ['name','like','%'.$con.'%'];
            }
            elseif($type == 'phone') {
                $where[] = ['phone','like','%'.$con.'%'];
            }
        }
        if($type == 'address'){
            if($single_id && !empty($room_ids)) {
                $where[] = ['single_id','=',$single_id];
            }
            if($layer_id && !empty($room_ids)) {
                $where[] = ['layer_id','=',$layer_id];
            }
            if($floor_id && !empty($room_ids)) {
                $where[] = ['floor_id','=',$floor_id];
            }
            if($room_id && !empty($room_ids)) {
                $where[] = ['room_id','=',$room_id];
            }
        }
        if($start_time && !$end_time) {
            $where[] = ['add_time','>=',strtotime($start_time)];
        }
        if(!$start_time && $end_time) {
            $where[] = ['add_time','<=',strtotime($end_time.' 23:59:29')];
        }
        if($start_time && $end_time) {
            $where[] = ['add_time','between',[strtotime($start_time),strtotime($end_time.' 23:59:29')]];
        }
        if($event_status){
            switch ($event_status) {
                case '10':
                    $where[]=[ 'event_status','in',[10,11,14]];
                    break;
                case '20':
                    $where[]=[ 'event_status','in',[20,21,22,23,24,25]];
                    break;
                case '30':
                    $where[]=[ 'event_status','in',[30,34]];
                    break;
                case '40':
                    $where[]=[ 'event_status','in',[40,41,42,43]];
                    break;
                case '16':
                    $where[]=[ 'event_status','in',[16,26]];
                    break;
                case '17':
                    $where[]=[ 'event_status','in',[17,27]];
                    break;
                case 'timely_1':
                    $where[]=[ 'is_status','=',1];
                    break;
                default:
                    $where[] = ['event_status','=',$event_status];
                    break;
            }
        }

        $field='*';
        $order='order_id DESC';
        $service_house_new_repair = new HouseNewRepairService();
        try{
            $data = $service_house_new_repair->getExportOrderList($where,$field,$order,'village');
            return api_output(0, $data);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        
    }
}