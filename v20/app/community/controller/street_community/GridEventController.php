<?php


namespace app\community\controller\street_community;

use app\community\controller\CommunityBaseController;
use app\community\model\db\AreaStreet;
use app\community\model\service\AreaStreetEventCategoryService;
use app\community\model\service\AreaStreetService;
use app\community\model\service\StreetWorksOrderService;
use app\community\model\service\AreaStreetWorkersService;
use app\community\model\service\TaskReleaseService;
use function GuzzleHttp\Psr7\str;

class GridEventController extends CommunityBaseController
{
    /**
     * 获取事件分类列表
     * @author lijie
     * @date_time 2021/02/22
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getEventCategoryList()
    {
        $street_id = $this->adminUser['area_id'];
        $cat_id = $this->request->post('cat_id',0);
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        $service_area_street_event_category = new AreaStreetEventCategoryService();
        $where[] = ['area_id','=',$street_id];
        $where[] = ['status','<>',4];
        if($cat_id){
            $where[] = ['cat_fid','=',$cat_id];
        }else{
            $where[] = ['cat_fid','=',0];
        }
        $data = $service_area_street_event_category->getCategoryList($where,'*',$page,$limit,'sort desc,cat_id DESC');
        $count = $service_area_street_event_category->getCategoryCount($where);
        $res['list'] = $data;
        $res['count'] = $count;
        $res['total_limit'] = 10;
        return api_output(0,$res);
    }

    /**
     * 获取事件分类列表（不分页）
     * @author lijie
     * @date_time 2021/03/01
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCategoryList()
    {
        $street_id = $this->adminUser['area_id'];
        $cat_id = $this->request->post('cat_id',0);
        $service_area_street_event_category = new AreaStreetEventCategoryService();
        $where[] = ['area_id','=',$street_id];
        $where[] = ['status','<>',4];
        if($cat_id){
            $where[] = ['cat_fid','=',$cat_id];
        }else{
            $where[] = ['cat_fid','=',0];
        }
        $data = $service_area_street_event_category->getCategoryList($where,'*',0,0,'cat_id DESC')->toArray();
        if($data){
            array_unshift($data,['cat_id'=>0,'cat_name'=>'全部']);
        }
        return api_output(0,$data);
    }

    /**
     * 添加事件分类
     * @author lijie
     * @date_time 2021/02/23
     * @return \json
     */
    public function addEventCategory()
    {
		$postParams = $this->request->post();
		unset($postParams['system_type']);
		
        $street_id = $this->adminUser['area_id'];
        $service_area_street = new AreaStreetService();
        $data = $service_area_street->getAreaStreet(['area_id'=>$street_id],'area_type');

        $postParams['area_id'] = $street_id;
        $postParams['area_type'] = $data['area_type'];
        $postParams['add_time'] = time();
        $service_area_street_event_category = new AreaStreetEventCategoryService();
        $res = $service_area_street_event_category->addCategory($postParams);
        return api_output(0,[]);
    }

    /**
     * 编辑事件分类
     * @author lijie
     * @date_time 2021/02/23
     * @return \json
     */
    public function editEventCategory()
    {
        $postParams = $this->request->post();
		unset($postParams['system_type']);
		
        $postParams['last_time'] = time();
        $cat_id = $this->request->post('cat_id',0);
        if(!$cat_id)
            return api_output_error(1001,'必传参数缺失');
        $where['cat_id'] = $cat_id;
        $service_area_street_event_category = new AreaStreetEventCategoryService();
        $res = $service_area_street_event_category->saveCategory($where,$postParams);
        return api_output(0,[]);
    }

    /**
     * 今日上报、回复、处理事件
     * @author lijie
     * @date_time 2021/02/24
     * @return \json
     */
    public function todayEventCount()
    {
        $beginToday = mktime(0,0,0,date('m'),date('d'),date('Y'));
        $street_id = $this->adminUser['area_id'];
        $service_street_works_order = new StreetWorksOrderService();
        $where1[] = ['l.log_name','=','member_submit'];
        $where1[] = ['o.area_id','=',$street_id];
        $where1[] = ['l.add_time','>=',$beginToday];
        $where1[] = ['o.del_time','=',0];
        $event_up_count = $service_street_works_order->getWorksOrderLogCount($where1,'l.order_id');
        $grid_up_count = $service_street_works_order->getWorksOrderLogCount($where1,'o.grid_range_id');
        $where2[] = ['l.log_name','=','center_reply_member'];
        $where2[] = ['o.area_id','=',$street_id];
        $where2[] = ['l.add_time','>=',$beginToday];
        $where2[] = ['o.del_time','=',0];
        $event_reply_count = $service_street_works_order->getWorksOrderLogCount($where2,'l.order_id');
        $grid_reply_count = $service_street_works_order->getWorksOrderLogCount($where2,'o.grid_range_id');
        $where3[] = ['l.log_name','=','center_assign_work'];
        $where3[] = ['o.area_id','=',$street_id];
        $where3[] = ['l.add_time','>=',$beginToday];
        $where3[] = ['o.del_time','=',0];
        $event_assign_count = $service_street_works_order->getWorksOrderLogCount($where3,'l.order_id');
        $grid_assign_count = $service_street_works_order->getWorksOrderLogCount($where3,'o.grid_range_id');
        $data['up']['event_count'] = $event_up_count;
        $data['up']['grid_count'] = $grid_up_count;
        $data['reply']['event_count'] = $event_reply_count;
        $data['reply']['grid_count'] = $grid_reply_count;
        $data['assign']['event_count'] = $event_assign_count;
        $data['assign']['grid_count'] = $grid_assign_count;
        return api_output(0,$data);
    }

    /**
     * 事件处理数据
     * @author lijie
     * @date_time 2021/02/24
     * @return \json
     */
    public function eventData()
    {
        $street_id = $this->adminUser['area_id'];
        $service_street_works_order = new StreetWorksOrderService();
        $todo[] = ['e.order_go_by_center','=',1];
        $todo[] = ['e.area_id','=',$street_id];
        $todo[] = ['e.event_status','=',1];
        $todo[] = ['e.del_time','=',0];
        $todo_count = $service_street_works_order->getWorkersOrderCount($todo);
        $todo_color = $service_street_works_order->event_data_color[1];
        $processing[] = ['e.order_go_by_center','=',1];
        $processing[] = ['e.area_id','=',$street_id];
        $processing[] = ['e.event_status','in','2,3'];
        $processing[] = ['e.del_time','=',0];
        $processing_count = $service_street_works_order->getWorkersOrderCount($processing);
        $processing_color = $service_street_works_order->event_data_color[2];
        $completed[] = ['e.order_go_by_center','=',1];
        $completed[] = ['e.area_id','=',$street_id];
        $completed[] = ['e.event_status','in','4,5,6'];
        $completed[] = ['e.del_time','=',0];
        $completed_count = $service_street_works_order->getWorkersOrderCount($completed);
        $completed_color = $service_street_works_order->event_data_color[3];
        $data['top']['todo']['todo_count'] = $todo_count;
        $data['top']['todo']['todo_color'] = $todo_color;
        $data['top']['processing']['processing_count'] = $processing_count;
        $data['top']['processing']['processing_color'] = $processing_color;
        $data['top']['completed']['completed_count'] = $completed_count;
        $data['top']['completed']['completed_color'] = $completed_color;
        $where[] = ['area_id','=',$street_id];
        $where[] = ['order_go_by_center','=',1];
        $where[] = ['e.del_time','=',0];
        $grid_arr = $service_street_works_order->getWorkersOrderListByGroup($where,'e.grid_range_id,g.polygon_name','e.grid_range_id')->toArray();
        $all_count = $processing_count+$completed_count+$todo_count;
        if($all_count){
            $rate = 100/$all_count;
        }else{
            $rate = 0;
        }
        if($grid_arr){
            foreach ($grid_arr as $k=>$v){
                $data['bottom'][$k]['polygon_name'] = $v['polygon_name'];
                $todo_count = $service_street_works_order->getWorkersOrderCount([['e.grid_range_id','=',$v['grid_range_id']],['e.event_status','=',1],['e.order_go_by_center','=',1],['e.del_time','=',0]]);
                $todo_color = $service_street_works_order->event_data_color[1];
                $processing_count = $service_street_works_order->getWorkersOrderCount([['e.grid_range_id','=',$v['grid_range_id']],['e.event_status','in','2,3'],['e.order_go_by_center','=',1],['e.del_time','=',0]]);
                $processing_color = $service_street_works_order->event_data_color[2];
                $completed_count = $service_street_works_order->getWorkersOrderCount([['e.grid_range_id','=',$v['grid_range_id']],['e.event_status','in','4,5,6'],['e.order_go_by_center','=',1],['e.del_time','=',0]]);
                $completed_color = $service_street_works_order->event_data_color[3];
                $data['bottom'][$k]['tongji']['todo']['todo_count'] = $todo_count;
                $data['bottom'][$k]['tongji']['todo']['todo_color'] = $todo_color;
                $data['bottom'][$k]['tongji']['processing']['processing_count'] = $processing_count;
                $data['bottom'][$k]['tongji']['processing']['processing_color'] = $processing_color;
                $data['bottom'][$k]['tongji']['completed']['completed_count'] = $completed_count;
                $data['bottom'][$k]['tongji']['completed']['completed_color'] = $completed_color;

            }
        }else{
            $data['bottom'] = [];
        }
        $data['rate'] = $rate;
        return api_output(0,$data);
    }

    /**
     * 事件处理中心数据统计
     * @author lijie
     * @date_time 2021/02/24
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getWorkerOrderLists()
    {
        $street_id = $this->adminUser['area_id'];
        $page = $this->request->post('page',1);
        $cat_id = $this->request->post('cat_id',0);
        if($cat_id)
            $where[] = ['e.cat_id','=',$cat_id];
        $cat_fid = $this->request->post('cat_fid',0);
        if($cat_fid)
            $where[] = ['e.cat_fid','=',$cat_fid];
        $search = $this->request->post('search','');
        $type = $this->request->post('type','');
        if($search){
            if($type == 'grid_member'){
                $where[] = ['g.polygon_name','like','%'.$search.'%'];
            }elseif($type == 'name'){
                $where[] = ['e.name','like','%'.$search.'%'];
            }else{
                $where[] = ['e.phone','like','%'.$search.'%'];
            }
        }
        $event_status = $this->request->post('event_status',0);
        if($event_status)
            $where[] = ['e.event_status','=',$event_status];
        $start_time = $this->request->post('start_time',0);
        $end_time = $this->request->post('end_time',0);
        if($start_time && !$end_time)
            $where[] = ['e.order_time','>=',strtotime($start_time)];
        if(!$start_time && $end_time)
            $where[] = ['e.order_time','<=',strtotime($end_time)];
        if($start_time && $end_time)
            $where[] = ['e.order_time','between',[strtotime($start_time),strtotime($end_time)]];
        $limit = 10;
        $db_area_street_db = new AreaStreet();
        $where_street[] = ['area_id', '=', $street_id];
        $street_info = $db_area_street_db->getOne($where_street,'area_id,area_pid,area_type,area_name');
        $community_id=0;
        if($street_info && !$street_info->isEmpty()){
            $street_info=$street_info->toArray();
            if($street_info['area_type']==1 && $street_info['area_pid']>0){
                $community_id=$street_id;
                $street_id=$street_info['area_pid'];
            }
        }
        $service_street_works_order = new StreetWorksOrderService();
        $where[] = ['e.area_id','=',$street_id];
        if($community_id>0){
            $where[] = ['e.community_id','=',$community_id];
        }
        $where[] = ['e.event_status','<>',0];
        $where[] = ['e.order_go_by_center','=',1];
        $where[] = ['e.del_time','=',0];
        $data = $service_street_works_order->getWorkerOrderLists($where,'e.grid_range_id,e.order_id,e.order_address,e.order_time,e.cat_fid,e.cat_id,e.order_content,g.polygon_name,e.name,e.phone,e.event_status','e.last_time DESC,e.order_time DESC',$page,$limit);
        $count = $service_street_works_order->getWorkersOrderCount($where);
        $rtn['total'] = $count;
        $rtn['list'] = $data;
        $rtn['limit'] = $limit;
        return api_output(0,$rtn);
    }

    /**
     * 事件操作记录
     * @author lijie
     * @date_time 2021/02/24
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getWorkerEventDetail()
    {
        $order_id = $this->request->post('order_id',0);
        if(!$order_id)
            return api_output_error(1001,'必传参数缺失');
        $service_street_works_order = new StreetWorksOrderService();
        $where[] = ['order_id','=',$order_id];
        $where[] = ['del_time','=',0];
        $event_info = $service_street_works_order->getWorkersOrderInfo($where,'e.*,g.polygon_name');
        if(!$event_info){
            return api_output_error(1001,'该数据已不存在');
        }
        $event_log = $service_street_works_order->getWorkersOrderLogList(['order_id'=>$order_id],'log_name,log_operator,log_phone,log_content,log_imgs,add_time','log_id DESC',0,0)->toArray();
        $data['event_info'] = $event_info;
        $data['event_log'] = $event_log;
        $data['event_arr'][] = ['title'=>'上报人员','value'=>$event_info['name']];
        $data['event_arr'][] = ['title'=>'上报人员手机号码','value'=>$event_info['phone']];
        $data['event_arr'][] = ['title'=>'上报时间','value'=>$event_info['order_time_txt']];
        $data['event_arr'][] = ['title'=>'上报网格','value'=>$event_info['polygon_name']];
        $data['event_arr'][] = ['title'=>'所在位置','value'=>$event_info['order_address']];
        $data['event_arr'][] = ['title'=>'事件分类','value'=>$event_info['cat_fname'].'-'.$event_info['cat_name']];
        if(!empty($event_log)){
            $count = count($event_log);
            $data['event_log_arr'][] = ['title'=>'时间','value'=>$event_log[$count-1]['add_time_txt']];
            $data['event_log_arr'][] = ['title'=>'操作人员','value'=>$event_log[$count-1]['log_operator']];
            $data['event_log_arr'][] = ['title'=>'操作原因','value'=>$event_log[$count-1]['log_content']];
            $data['event_log_img'] = $event_log[$count-1]['log_imgs'];
        }else{
            $data['event_log_arr'] = [];
            $data['event_log_img'] = [];
        }
        return api_output(0,$data);
    }

    /**
     * 街道工作人员列表
     * @author lijie
     * @date_time 2021/02/25
     * @return \json
     */
    public function getWorkers()
    {
        $street_id = $this->adminUser['area_id'];
        $service_area_street_workers = new AreaStreetWorkersService();
        $select_option = $this->request->post('select_option','');
        $con = $this->request->post('con','');
        if($select_option){
            if($select_option == 'name')
                $where['work_name'] = $con;
            else
                $where['work_phone'] = $con;
        }
        $where['area_id'] = $street_id;
        $where['is_handle_event'] = 1;
        $where['work_status'] = 1;
        $field = 'work_num,work_name,work_phone,work_job,organization_ids,worker_id,work_id_card,work_addr,work_head';
        $order = 'worker_id DESC';
        $data = $service_area_street_workers->getList($where,$field,$order,0,0);
        return api_output(0,$data);
    }

    public function getWorkersPage()
    {
        $street_id = $this->adminUser['area_id'];
        $service_area_street_workers = new AreaStreetWorkersService();
        $select_option = $this->request->post('select_option','');
        $con = $this->request->post('con','');
        $page = $this->request->post('page',1);
        if($select_option && !empty($con)){
            if($select_option == 'name')
                $where['work_name'] = $con;
            else
                $where['work_phone'] = $con;
        }
        $where['area_id'] = $street_id;
        $where['work_status'] = 1;
        $field = 'work_num,worker_id,work_name,work_phone,work_job,organization_ids,worker_id,work_id_card,work_addr,work_head';
        $order = 'worker_id DESC';
        $data = $service_area_street_workers->getList($where,$field,$order,$page,10);
        $count = $service_area_street_workers->getWorksCount($where);
        $rtn['total'] = $count;
        $rtn['list'] = $data;
        $rtn['limit'] = 10;
        return api_output(0,$rtn);
    }

    /**
     * 处理事件
     * @author lijie
     * @date_time 2021/03/01
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function allocationWorker()
    {
        $order_id = $this->request->post('order_id',0);
        $type = $this->request->post('type',1);
        $worker_id = $this->request->post('worker_id');
        $order_content = $this->request->post('order_content','');
        if(!$order_id)
            return api_output_error(1001,'缺少必传参数');
        $service_street_works_order = new StreetWorksOrderService();
        $where['order_id'] = $order_id;
        $event_info = $service_street_works_order->getWorkersOrderInfo($where,'e.*');
        if(empty($event_info) || $event_info['event_status'] != 1)
            return api_output_error(1001,'当前事件无法进行此操作');
        if($type == 1 && !$worker_id)
            return api_output_error(1001,'缺少必传参数');
        if($type == 2 && empty($order_content))
            return api_output_error(1001,'缺少必传参数');
        if($type == 1){
            $res = $service_street_works_order->saveWorkOrder(['order_id'=>$order_id],['order_worker_id'=>$worker_id,'event_status'=>2,'now_role'=>3,'order_status'=>3,'last_time'=>time()]);
            if($res){
                $addData['order_id'] = $order_id;
                $addData['log_name'] = 'center_assign_work';
                $addData['log_operator'] = '后台工作人员';
                $addData['operator_type'] = 2;
                $addData['log_type'] = 1;
                $addData['add_time'] = time();
                $service_street_works_order->addWorksOrder($addData['log_name'],$addData);
                $service_street_works_order->addEventWorkers(['order_id'=>$order_id,'worker_id'=>$worker_id,'bind_time'=>time(),'last_time'=>time()]);
                //发模板消息
                $workerInfo=array('worker_id'=>$worker_id,'grid_member_id'=>0);
                $service_street_works_order->streetWorkersOrderSendMsg('assign_to_worker',$order_id,$workerInfo);
                return api_output(0,[]);
            }
        }else{
            $res = $service_street_works_order->saveWorkOrder(['order_id'=>$order_id],['event_status'=>4,'order_status'=>3,'now_role'=>1,'last_time'=>time()]);
            if($res){
                $addData['order_id'] = $order_id;
                $addData['log_name'] = 'center_reply_member';
                $addData['log_operator'] = '后台工作人员';
                $addData['operator_type'] = 2;
                $addData['log_type'] = 1;
                $addData['log_content'] = $order_content;
                $addData['add_time'] = time();
                $service_street_works_order->addWorksOrder($addData['log_name'],$addData);
                return api_output(0,[]);
            }
        }
    }

    /**
     * 删除工单
     * @author: liukezhu
     * @date : 2022/6/6
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delWorkerOrder(){
        $street_id = $this->adminUser['area_id'];
        $order_id = $this->request->post('order_id',0);
        if(!$order_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $where[] = ['area_id','=',$street_id];
        $where[] = ['order_id','=',$order_id];
        $where[] = ['del_time','=',0];
        $res = (new StreetWorksOrderService())->saveWorkOrder($where,['del_time'=>time()]);
        if($res){
            return api_output(0,$res);
        }else{
            return api_output_error(1001,'删除失败');
        }
    }

    /**
     * 获取街道、社区组织数据
     * @author: liukezhu
     * @date : 2022/6/13
     * @return \json
     */
    public function getGridEventOrg(){
        if ($this->adminUser['area_type'] == 1) {
            // 是社区
            $street_id=$this->adminUser['area_pid'];
            $community_id=$this->adminUser['area_id'];
        }
        else{
            //是街道
            $street_id=$this->adminUser['area_id'];
            $community_id=0;
        }
        try{
            $res = (new TaskReleaseService())->getTaskReleaseTissueNav(1,$this->adminUser['area_type'],$street_id,$community_id,1)['list'];
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if ($res){
            return api_output(0,['res' => $res]);
        }else{
            return api_output(1003,[],'暂无数据！');
        }
    }
}