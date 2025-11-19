<?php


namespace app\community\model\service;

use app\community\model\db\AreaStreetWorkers;
use app\community\model\db\HouseVillageGridMember;
use app\community\model\db\AreaStreetOrganization;
use app\community\model\db\AreaStreetWorkersOrder;
use app\community\model\db\AreaStreet;
use app\community\model\db\HouseVillageGridRange;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\AreaStreetEventCategory;

class AreaStreetWorkersService
{
    /**
     * 街道工作人员列表
     * @author lijie
     * @date_time 2021/02/25
     * @param array $where
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return \think\Collection
     */
    public function getList($where=[],$field=true,$order='worker_id DESC',$page=1,$limit=15)
    {
        $db_area_street_workers = new AreaStreetWorkers();
        $db_area_street_grid_member = new HouseVillageGridMember();
        $db_area_street_organization = new AreaStreetOrganization();
        $data = $db_area_street_workers->getSelect($where,$field,$order,$page,$limit);
        if($data){
            foreach ($data as $k=>$v){
                if($v['work_job'])
                    $data[$k]['work_name_job'] = $v['work_name'].'（'.$v['work_job'].')';
                else
                    $data[$k]['work_name_job'] = $v['work_name'];
                $organization_txt = '';
                $grid_member_info = $db_area_street_grid_member->getOne(['workers_id'=>$v['worker_id'],'area_id'=>$where['area_id']]);
                if($grid_member_info)
                    $data[$k]['is_exist'] = 1;
                else
                    $data[$k]['is_exist'] = 0;
                if($v['organization_ids']){
                    $organization_ids_arr = explode(',',$v['organization_ids']);
                    $organization_list = $db_area_street_organization->getSelectList([['id','in',$organization_ids_arr]],'name','id DESC',0,0);
                    if($organization_list){
                        foreach ($organization_list as $val){
                            $organization_txt .= $val['name'].'-';
                        }
                        $organization_txt = rtrim($organization_txt, "-");
                    }
                }
                $data[$k]['organization_txt'] = $organization_txt;
            }
        }
        return $data;
    }


    /**
     * Notes: 获取工作人员相关数量
     * @param $worker_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/2/27 13:49
     */
    public function getWorkIndexNum($worker_id) {
        // 先查询下 是不是网格员   是不是 事件处理人员
        $db_area_street_grid_member = new HouseVillageGridMember();
        $db_area_street_workers_order = new AreaStreetWorkersOrder();
        $dbAreaStreetWorkers = new AreaStreetWorkers();
        $where = [];
        $where[] = ['worker_id','=',$worker_id];
        $worker_info = $dbAreaStreetWorkers->getOne($where);

        $grid_member_info = $db_area_street_grid_member->getOne(['workers_id'=>$worker_id]);
        $arr = [];
        $arr['info'] = [];
        $is_show = false;
        if (!empty($grid_member_info) && isset($grid_member_info['id'])) {
            $is_show = true;
            // 是网格员 获取对应网格员的工单数量  自己提交的和住户提交的
            $where_member_user_submit = [];
            $where_member_user_submit[] = ['e.order_type','=',1];
            $where_member_user_submit[] = ['e.uid','=',$grid_member_info['id']];
            $where_member_user_submit[] = ['e.del_time','=',0];
            $member_user_submit_count = $db_area_street_workers_order->getCount($where_member_user_submit);
            $arr['member_user_submit_count'] = $member_user_submit_count;
            $arr['info'][] = [
                'title'   => '已提交事件',
                'count'   => $member_user_submit_count,
                'type'    => 'grid_event',
                'current' => 3,
                'url'     => cfg('site_url').'/packapp/community/pages/Community/grid/eventList?current=3&type=all'
            ];
            // 获取业主提交数量
            $where_member_user_submit = [];
            $where_member_user_submit[] = ['e.order_type','=',0];
            $where_member_user_submit[] = ['e.grid_member_id','=',$grid_member_info['id']];
            $where_member_user_submit[] = ['e.del_time','=',0];
            $user_submit_count = $db_area_street_workers_order->getCount($where_member_user_submit);
            $arr['user_submit_count'] = $user_submit_count;
            $arr['info'][] = [
                'title'   => '业主提交事件',
                'count'   => $user_submit_count,
                'type'    => 'grid_event',
                'current' => 3,
                'url'     => cfg('site_url').'/packapp/community/pages/Community/grid/eventList?current=3&type=all'
            ];
        }
        // 如果是事件处理人员 获取事件处理相关数据
        if ($worker_info && isset($worker_info['is_handle_event']) && 1==$worker_info['is_handle_event']) {
            $is_show = true;
            // 已指派 所有经过事件处理人员 的工单数
            $where_assign_work_count = [];
            $where_assign_work_count[] = ['b.worker_id','=',$worker_id];
            $where_assign_work_count[] = ['a.del_time','=',0];
            $center_assign_work_count = $db_area_street_workers_order->getWorkHandleCount($where_assign_work_count);
            $arr['center_assign_work_count'] = $center_assign_work_count;
            $arr['info'][] = [
                'title'   => '已指派事件',
                'count'   => $center_assign_work_count,
                'type'    => 'grid_event',
                'current' => 0,
                'url'     => cfg('site_url').'/packapp/community/pages/Community/grid/eventList?current=0&type=todo'
            ];
            // 处理中事件 经过事件处理人员 事件处于处理中状态的数量
            $where_processing_submit = [];
            $where_processing_submit[] = ['a.del_time','=',0];

            //为了和列表处理中查询数据统一  复制以下代码
            $where_member1_str='';
            if (!empty($work_info)) {
                $where_work_str = "(b.worker_id={$worker_id} AND a.event_status=4)";
            } else {
                $where_work_str = '';
            }
            if (!empty($grid_member_info)) {
                // 自己提交的工单
                $where_member_str = "(a.order_type=1 AND a.uid={$grid_member_info['id']} AND a.grid_member_id={$grid_member_info['id']} AND a.order_status in (1,3))";
                // 当前是自己的工单 处于处理中
                $where_member1_str = "(a.grid_member_id={$grid_member_info['id']} AND a.event_status in (1,2,3))";
            } else {
                $where_member_str = '';
            }
            if ($where_work_str && $where_member_str) {
                $whereRaw = "({$where_work_str} OR {$where_member_str} OR {$where_member1_str})";
            } elseif ($where_work_str) {
                $whereRaw = "({$where_work_str})";
            } elseif ($where_member_str) {
                $whereRaw = "({$where_member_str} OR {$where_member1_str})";
            }else{
                $whereRaw='';
            }
            if(!empty($whereRaw)){
                $processing_count = $db_area_street_workers_order->getWorkHandleCount($where_processing_submit,'a.order_id','b.bind_id DESC','a.order_id',$whereRaw);
            }else{
                $processing_count=0;
            }
            $arr['processing_count'] = $processing_count;
            $arr['info'][] = [
                'title'   => '处理中事件',
                'count'   => $processing_count,
                'type'    => 'grid_event',
                'current' => 1,
                'url'     => cfg('site_url').'/packapp/community/pages/Community/grid/eventList?current=1&type=processing'
            ];
        }
        $arr['is_show'] = $is_show;
        return $arr;
    }

    /**
     * Notes: 获取网格员提交工单必传参数
     * @param $area_id
     * @param $worker_id
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/3/1 16:55
     */
    public function workGetStreetGridOrder($area_id,$worker_id=0) {
        $street_id = $area_id;
        $db_area_street = new AreaStreet();
        $where_street[] = ['area_id', '=', $street_id];
        $street_info = $db_area_street->getOne($where_street,'area_id,area_type,area_name');
        if (!$street_info) {
            throw new \think\Exception('对应街道社区不存在');
        }
        $db_area_street_grid_member = new HouseVillageGridMember();
        $where_base =[];
        $where_base[] = ['worker_id','=',$worker_id];
        $grid_member_info = $db_area_street_grid_member->getOne(['workers_id'=>$worker_id]);
        if (!$grid_member_info) {
            throw new \think\Exception('网格员才可以事件上报');
        }
        $area_type = $street_info['area_type'];
        $where_grid = [];
        if (0==$area_type) {
            $where_grid[] = ['f_street_id','=',$area_id];
        } else {
            $where_grid[] = ['f_area_id','=',$area_id];
        }
        $where_grid[] = ['grid_member_id','<>',0];
        $db_house_village_grid_range = new HouseVillageGridRange();
        $field = 'id,type,f_street_id,f_area_id,f_village_id,f_single_id,polygon_name,grid_member_id,fid';
        $order = 'create_time DESC';
        $list = $db_house_village_grid_range->getLists($where_grid,$field,$order);
        $list = $list->toArray();
        if (!empty($list)) {
            $house_village = new HouseVillage();
            $db_house_village_single = new HouseVillageSingle();
            foreach ($list as &$val) {
                $address = '';
                if ($val['f_street_id']) {
                    $where_street = [];
                    $where_street[] = ['area_id', '=', $val['f_street_id']];
                    $street = $db_area_street->getOne($where_street,'area_id,area_name');
                    if ($street) {
                        $val['street_name'] = $street['area_name'];
                        $address .= $street['area_name'];
                    }
                }
                if ($val['f_area_id']) {
                    $where_street = [];
                    $where_street[] = ['area_id', '=', $val['f_area_id']];
                    $street = $db_area_street->getOne($where_street,'area_id,area_name');
                    if ($street) {
                        $val['community_name'] = $street['area_name'];
                        $address .= ' '.$street['area_name'];
                    }
                }
                if ($val['f_village_id']) {
                    $village_field = 'village_id,village_name';
                    $village_info = $house_village->getOne($val['f_village_id'],$village_field);
                    if ($village_info) {
                        $val['village_name'] = $village_info['village_name'];
                        $address .= ' '.$village_info['village_name'];
                    }
                }
                if ($val['f_single_id']) {
                    $where_single = [];
                    $where_single[] = ['id','=',$val['f_single_id']];
                    $field_single = 'id,single_name';
                    $single_info = $db_house_village_single->getOne($where_single,$field_single);
                    if ($single_info) {
                        $val['single_name'] = $single_info['single_name'];
                        $address .= ' '.$single_info['single_name'];
                    }
                }
                $val['address'] = $address;
            }
        } else {
            $list = [];
        }
        $arr = [];
        $arr['grid_list'] = $list;
        // 分类-默认查询第一条父级分类
        $service_area_street_event_category = new AreaStreetEventCategoryService();
        $where[] = ['status', '=', 1];
        $where[] = ['area_id', '=', $street_id];
        $field = true;
        $category_list = $service_area_street_event_category->userGetCategoryList($where,$field,'sort DESC, cat_id DESC');
        $arr['category_list'] = $category_list;
        return $arr;
    }

    public function getWorksCount($where)
    {
        $db_area_street_workers = new AreaStreetWorkers();
        $count = $db_area_street_workers->getCount($where);
        return $count;
    }
}