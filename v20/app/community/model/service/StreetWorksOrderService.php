<?php
/**
 * 网格化事件管理
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/2/22 12:00
 */

namespace app\community\model\service;

use app\common\model\service\send_message\SmsService;
use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\AreaStreetWorkersOrder;
use app\community\model\db\AreaStreetWorkersOrderLog;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\AreaStreetEventCategory;
use app\community\model\db\HouseVillageGridRange;
use app\community\model\db\HouseVillage;
use app\community\model\db\AreaStreet;
use app\community\model\db\AreaStreetWorkers;
use app\community\model\db\HouseVillageGridMember;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\AreaStreetEventWorks;
use app\community\model\db\User;
use token\Token;

class StreetWorksOrderService{
    // 工单状态 1 待处理  2 已办结 3 处理中 4 已关闭  5 已撤回
    public $order_status_word = [
        ['status' => 'todo','name' => '待处理'],
        ['status' => 'completed','name' => '已办结'],
        ['status' => 'processing','name' => '处理中'],
        ['status' => 'closed','name' => '已关闭'],
        ['status' => 'recalled','name' => '已撤回'],
    ];
    // 工单英文状态对应状态值
    public $order_status = ['todo' => 1,'completed' => 2,'processing' => 3,'closed' => 4,'recalled' => 5];
    // 工单状态值对应中文
    public $order_status_txt = ['1'=>'待处理','2' => '已办结','3' => '处理中','4' => '已关闭','5' => '已撤回'];
    // 工单状态对应颜色
    public $order_status_color = ['1'=>'#FF6600','2' => '#787ADF','3' => '#26A6FF','4' => '#CCCCCC','5' => '#06C1AE'];

    // 工单事件处理状态 1 未指派 2 已指派 3 处理中 4已办结 5 已撤回 6已关闭
    public $event_status_word = [
        ['status' => 'unassigned','name' => '未指派'],
        ['status' => 'assigned','name' => '已指派'],
        ['status' => 'processing','name' => '处理中'],
        ['status' => 'completed','name' => '已办结'],
        ['status' => 'recalled','name' => '已撤回'],
        ['status' => 'closed','name' => '已关闭'],
    ];
    // 工单事件处理英文状态对应状态值
    public $event_status = ['unassigned' => 1,'assigned' => 2,'processing' => 3,'completed' => 4,'recalled' => 5,'closed' => 6];
    // 工单事件处理状态值对应中文
    public $event_status_txt = ['1'=>'未指派','2' => '已指派','3' => '处理中','4' => '已办结','5' => '已撤回','6' => '已关闭'];
    // 工单事件状态对应颜色
    public $event_status_color = ['1'=>'#FE3950','2' => '#00CC00','3' => '#26A6FF','4' => '#787ADF','5' => '#06C1AE','6' => '#CCCCCC'];
    // 工单事件处理数据对应颜色
    public $event_data_color = ['1'=>'#3158FF','2' => '#00BF7E','3' => '#EAA551'];

    // 住户提交  网格员回复 用户撤回
    public $user_log_name = ['user_submit' => '工单提交成功','member_reply_user' => '网格员回复','member_submit' => '处理中','user_recall' => '撤回','user_closed' => '关闭','user_reopen' => '重新打开'];
    // 网格员
    public $member_log_name = [
        'user_submit' => '工单提交成功','user_reopen' => '用户重新打开','user_recall' => '用户撤回','user_closed' => '用户关闭',
        'member_user_submit' => '工单提交成功','member_submit' => '事件提交','member_reopen' => '重新打开','member_recall' => '撤回','member_closed' => '关闭',
        'member_reject_center' => '驳回给处理中心','member_reject_work' => '驳回给处理人员','member_reply_user' => '回复用户','center_reply_member' => '处理中心回复',
        'center_assign_work' => '处理中心指派','work_reject_center' => '处理人员拒绝','work_follow_up' => '处理人员跟进','work_completed' => '处理人员结单',
    ];
    // 事件中心
    public $center_log_name = [
        'user_submit' => '工单提交成功','user_reopen' => '重新打开','user_recall' => '撤回','user_closed' => '关闭','member_reopen' => '重新打开',
        'member_user_submit' => '工单提交成功','member_submit' => '事件提交成功','member_recall' => '撤回','member_closed' => '关闭',
        'member_reject_center' => '网格员驳回','member_reject_work' => '网格员驳回给处理人员','member_reply_user' => '网格员回复用户', 'center_reply_member' => '回复',
        'center_assign_work' => '指派','work_reject_center' => '处理人员拒绝','work_follow_up' => '处理人员跟进','work_completed' => '处理人员结单',
    ];
    // 处理人员
    public $work_log_name = [
        'user_submit' => '工单提交成功','user_reopen' => '重新打开','user_recall' => '撤回','user_closed' => '关闭',
        'member_user_submit' => '工单提交成功','member_submit' => '事件提交成功', 'member_recall' => '撤回','member_closed' => '关闭',
        'member_reject_center' => '网格员驳回','member_reject_work' => '网格员驳回','member_reopen' => '重新打开',
        'member_reply_user' => '网格员回复用户','center_reply_member' => '处理中心回复','center_assign_work' => '被指派','work_reject_center' => '拒绝',
        'work_follow_up' => '跟进','work_completed' => '结单',
    ];

    /**
     * Notes: 住户获取街道社区工单列表
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $page_size
     * @return mixed
     * @author: wanzy
     * @date_time: 2021/2/22 19:24
     */
    public function userGetWorksOrderLists($where,$field=true,$order='order_time DESC',$page=0,$page_size=0) {
        $areaStreetWorkersOrder = new AreaStreetWorkersOrder();
        $data = $areaStreetWorkersOrder->getList($where,$field,$order,$page,$page_size);
        if (empty($data)) {
            $data = [];
        } else {
            $data = $data->toArray();
            if ($data) {
                foreach ($data as &$val) {
                    if (isset($val['order_time']) && $val['order_time']) {
                        $val['order_time_txt'] = date('Y-m-d H:i:s',$val['order_time']);
                    }
                    if (isset($val['last_time']) && $val['last_time']) {
                        $val['last_time_txt'] = date('Y-m-d H:i:s',$val['last_time']);
                    }
                    if (isset($val['order_status']) && $val['order_status']) {
                        $val['order_status_txt'] = $this->order_status_txt[$val['order_status']];
                        $val['order_status_color'] = $this->order_status_color[$val['order_status']];
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Notes:获取用户街道下所有小区的身份  仅限 业主 家属 和租客
     * @param $where
     * @param bool $field
     * @param int $street_id
     * @param string $order
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/3/1 14:41
     */
    public function userGetStreetHouseBindLists($where,$field=true,$street_id=0,$order='hvb.pigcms_id DESC') {
        $service_house_village = new HouseVillageService();
        $house_village_user_bind = new HouseVillageUserBind();
        $data = $house_village_user_bind->getStreetUserBind($where,$field,0,0,$order);
        $arr = [];
        $is_bind = false;
        if($data){
            $data = $data->toArray();
            $house_village_grid_range = new HouseVillageGridRange();
            $house_village = new HouseVillage();
            $is_bind = true;
            foreach ($data as $k=>$v){
                $where_grid = [];
                $where_grid[] = ['r.f_street_id','=',$street_id];
                $where_grid[] = ['r.f_single_id','=',$v['single_id']];
                $grid_range_info = $house_village_grid_range->getOne($where_grid,'r.id,r.grid_member_id,r.polygon_name');
                $grid_member_id = $grid_range_info['grid_member_id'] ? $grid_range_info['grid_member_id'] : 0;
                $grid_range_id = $grid_range_info['id'];
                if (!$grid_range_id || !$grid_member_id) {
                    $where_grid = [];
                    $where_grid[] = ['r.f_street_id','=',$street_id];
                    $where_grid[] = ['r.f_village_id','=',$v['village_id']];
                    $grid_range_info = $house_village_grid_range->getOne($where_grid,'r.id,r.grid_member_id,r.polygon_name');
                    $grid_member_id = $grid_range_info['grid_member_id'] ? $grid_range_info['grid_member_id'] : 0;
                    $grid_range_id = $grid_range_info['id'];
                    if (!$grid_range_id || !$grid_member_id) {
                        $village_field = 'village_id,village_name,street_id,community_id';
                        $village_info = $house_village->getOne($v['village_id'],$village_field);
                        if (!$village_info['community_id']) {
                            unset($data[$k]);
                        }
                        $where_grid = [];
                        $where_grid[] = ['r.f_street_id','=',$street_id];
                        $where_grid[] = ['r.f_area_id','=',$village_info['community_id']];
                        $grid_range_info = $house_village_grid_range->getOne($where_grid,'r.id,r.grid_member_id,r.polygon_name');
                        $grid_member_id = $grid_range_info['grid_member_id'] ? $grid_range_info['grid_member_id'] : 0;
                        $grid_range_id = $grid_range_info['id'];
                        if (!$grid_range_id || !$grid_member_id) {
                            unset($data[$k]);
                        }
                    }
                }
                if (isset($data[$k]) && $data[$k]) {
                    $data[$k]['address']=$service_house_village->getSingleFloorRoom($v['single_id'],$v['floor_id'],$v['layer_id'],$v['vacancy_id'],$v['village_id']);
                    if (isset($v['village_name']) && $v['village_name']) {
                        $data[$k]['address_detail'] = $v['village_name'] . ' '.$data[$k]['address'];
                    }
                    unset($v['single_id']);
                    unset($v['floor_id']);
                    unset($v['layer_id']);
                }
            }
            $data = array_values($data);
        }
        $arr['data'] = $data;
        if (empty($data) && $is_bind) {
            $arr['bind_msg'] = '您所在的房间没有对应网格员';
        } elseif (empty($data) && !$is_bind) {
            $arr['bind_msg'] = '您还没有房间，可前去小区申请入住房间';
        }
        return $arr;
    }

    /**
     * Notes: 住户或者网格员添加工单/
     * @param $data
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/2/24 15:34
     */
    public function addStreetWorksOrder($data) {
        if (!$data['street_id']) {
            throw new \think\Exception('缺少必传参数');
        }
        if (!$data['cat_id']) {
            throw new \think\Exception('缺少必传参数');
        }
        $street_id = $data['street_id'];
        $db_area_street = new AreaStreet();
        $where_street[] = ['area_id', '=', $street_id];
        $street_info = $db_area_street->getOne($where_street,'area_id,area_pid,area_type,area_name');
        if (!$street_info) {
            throw new \think\Exception('对应街道社区不存在');
        }
        $area_type = $street_info['area_type'];
        $community_id=0;
        if($area_type==1 && $street_info['area_pid']>0){
            $community_id=$street_id;
            $street_id=$street_info['area_pid'];
        }
        $db_house_village_user_bind = new HouseVillageUserBind();
        $house_village = new HouseVillage();
        $house_village_grid_range = new HouseVillageGridRange();
        $service_house_village = new HouseVillageService();
        $db_area_street_event_category = new AreaStreetEventCategory();
        $add_msg = [];
        if (isset($data['order_type']) && 0==intval($data['order_type'])) {
            if (!$data['bind_id']) {
                throw new \think\Exception('请选择所处位置如果没有位置请前往相应小区绑定');
            }
            $bind_where = [];
            $bind_where[] = ['pigcms_id','=',$data['bind_id']];
            $bind_user_info = $db_house_village_user_bind->getOne($bind_where,'pigcms_id,phone,name,single_id,floor_id,layer_id,vacancy_id,village_id');
            if (!$bind_user_info) {
                throw new \think\Exception('对应位置信息异常');
            }
            $village_field = 'village_id,village_name,street_id,community_id';
            $village_info = $house_village->getOne($bind_user_info['village_id'],$village_field);

            $phone = isset($bind_user_info['phone'])?$bind_user_info['phone']:'';
            $name = isset($bind_user_info['name'])?$bind_user_info['name']:'';

            $where_grid = [];
            $where_grid[] = ['r.f_street_id','=',$street_id];
            $where_grid[] = ['r.f_single_id','=',$bind_user_info['single_id']];
            $where_grid[] = ['m.id','>',0];
            $grid_range_info = $house_village_grid_range->getOne($where_grid,'r.id,r.grid_member_id,r.polygon_name');
            $grid_member_id = $grid_range_info['grid_member_id'] ? $grid_range_info['grid_member_id'] : 0;
            $grid_range_id = $grid_range_info['id'];
            if (!$grid_range_id || !$grid_member_id) {
                $where_grid = [];
                $where_grid[] = ['r.f_street_id','=',$street_id];
                $where_grid[] = ['r.f_village_id','=',$bind_user_info['village_id']];
                $where_grid[] = ['m.id','>',0];
                $grid_range_info = $house_village_grid_range->getOne($where_grid,'r.id,r.grid_member_id,r.polygon_name');
                $grid_member_id = $grid_range_info['grid_member_id'] ? $grid_range_info['grid_member_id'] : 0;
                $grid_range_id = $grid_range_info['id'];
                if (!$grid_range_id || !$grid_member_id) {
                    if (!$village_info['community_id']) {
                        throw new \think\Exception('抱歉当前区域暂无网格员负责 请联系街道');
                    }
                    $where_grid = [];
                    $where_grid[] = ['r.f_street_id','=',$street_id];
                    $where_grid[] = ['r.f_area_id','=',$village_info['community_id']];
                    $where_grid[] = ['m.id','>',0];
                    $grid_range_info = $house_village_grid_range->getOne($where_grid,'r.id,r.grid_member_id,r.polygon_name');
                    $grid_member_id = $grid_range_info['grid_member_id'] ? $grid_range_info['grid_member_id'] : 0;
                    $grid_range_id = $grid_range_info['id'];
                    if (!$grid_range_id || !$grid_member_id) {
                        throw new \think\Exception('抱歉当前区域暂无网格员负责 请联系街道');
                    }
                }
            }
            $address = $service_house_village->getSingleFloorRoom($bind_user_info['single_id'],$bind_user_info['floor_id'],$bind_user_info['layer_id'],$bind_user_info['vacancy_id'],$bind_user_info['village_id']);

            $order_address = $village_info['village_name'].' '.$address;
            $add_msg['status'] = 'processing';
            $operator_type = 0;
            $log_type = 0;
            $log_name = 'user_submit';
        } else {
            // 网格员上报处理
            if (!isset($data['grid_range_id']) || !$data['grid_range_id']) {
                throw new \think\Exception('请选择请选择对应网格');
            }
            if (!isset($data['worker_id']) || !$data['worker_id']) {
                throw new \think\Exception('请选择请选择对应网格');
            }
            $worker_id = intval($data['worker_id']);
            $dbAreaStreetWorkers = new AreaStreetWorkers();
            $where = [];
            $where[] = ['worker_id','=',$worker_id];
            $worker_info = $dbAreaStreetWorkers->getOne($where);
            if (empty($worker_info)) {
                throw new \think\Exception('您当前登录身份不具备权限添加工单');
            }
            $phone = isset($worker_info['work_phone'])?$worker_info['work_phone']:'';
            $name = isset($worker_info['work_name'])?$worker_info['work_name']:'';

            $db_area_street_grid_member = new HouseVillageGridMember();
            $grid_member_info = $db_area_street_grid_member->getOne(['workers_id'=>$worker_id]);
            if (empty($grid_member_info)) {
                throw new \think\Exception('您当前登录身份不具备权限添加工单');
            }
            $where_grid = [];
            $where_grid[] = ['r.id','=',$data['grid_range_id']];
            $grid_range_info = $house_village_grid_range->getOne($where_grid,'r.id,r.grid_member_id,r.polygon_name,r.f_street_id,r.f_area_id,r.f_village_id,r.f_single_id');
            $grid_member_id = $grid_range_info['grid_member_id'] ? $grid_range_info['grid_member_id'] : 0;
            $grid_range_id = $grid_range_info['id'];
            if (!$grid_range_id || !$grid_member_id) {
                throw new \think\Exception('抱歉当前区域暂无网格员负责 请联系街道');
            }
            if (isset($data['order_address'])) {
                $order_address = $data['order_address'];
            }
            if (!$order_address) {
                // 没有地址根据网格获取地址
                $order_address = '';
                $db_house_village_single = new HouseVillageSingle();
                if ($grid_range_info['f_street_id']) {
                    $where_street = [];
                    $where_street[] = ['area_id', '=', $grid_range_info['f_street_id']];
                    $street = $db_area_street->getOne($where_street,'area_id,area_name');
                    if ($street) {
                        $grid_range_info['street_name'] = $street['area_name'];
                        $order_address .= $street['area_name'];
                    }
                }
                if ($grid_range_info['f_area_id']) {
                    $where_street = [];
                    $where_street[] = ['area_id', '=', $grid_range_info['f_area_id']];
                    $street = $db_area_street->getOne($where_street,'area_id,area_name');
                    if ($street) {
                        $grid_range_info['community_name'] = $street['area_name'];
                        $order_address .= ' '.$street['area_name'];
                    }
                }
                if ($grid_range_info['f_village_id']) {
                    $village_field = 'village_id,village_name';
                    $village_info = $house_village->getOne($grid_range_info['f_village_id'],$village_field);
                    if ($village_info) {
                        $grid_range_info['village_name'] = $village_info['village_name'];
                        $order_address .= ' '.$village_info['village_name'];
                    }
                }
                if ($grid_range_info['f_single_id']) {
                    $where_single = [];
                    $where_single[] = ['id','=',$grid_range_info['f_single_id']];
                    $field_single = 'id,single_name';
                    $single_info = $db_house_village_single->getOne($where_single,$field_single);
                    if ($single_info) {
                        $grid_range_info['single_name'] = $single_info['single_name'];
                        $order_address .= ' '.$single_info['single_name'];
                    }
                }
            }
            $add_msg['status'] = 'processing';
            $operator_type = 1;
            $log_type = 1;
            $log_name = 'member_user_submit';
            $data['uid'] = $grid_member_info['id'];
        }

        $cat_id = $data['cat_id'];
        $where_cat = [];
        $where_cat[] = ['cat_id','=',$cat_id];
        $field = 'cat_id,cat_name,cat_fid,add_time';
        $cat_info = $db_area_street_event_category->getOne($where_cat,$field);
        if (!$cat_info) {
            throw new \think\Exception('分类不存在或者已经被删除');
        }
        if ($cat_info && $cat_info['cat_fid']) {
            $cat_fid = $cat_info['cat_fid'];
        } else {
            $cat_fid = 0;
        }
        $order_imgs = $data['order_imgs'];
        if (is_string($order_imgs)) {
            $order_imgs = trim($order_imgs);
        } elseif ($order_imgs) {
            $order_imgs = implode(';',$order_imgs);
        }
        $order_content = $data['order_content'];
        if (empty($order_imgs) && !$order_content) {
            throw new \think\Exception('缺少必传参数');
        }

        //校验绑定网格员关系表的数据合法性
        if(!empty($grid_member_id)){
            $grid_member_info = (new HouseVillageGridMember())->getOne(['id'=>$grid_member_id]);
            if(!$grid_member_info || $grid_member_info->isEmpty()){
                throw new \think\Exception('抱歉当前区域暂无网格员负责 请联系街道');
            }
        }
        $add_data = [
            'grid_range_id' => $grid_range_id,
            'order_address' => $order_address?$order_address:'',
            'order_time' => time(),
            'last_time' => time(),
            'cat_id' => $cat_id,
            'cat_fid' => $cat_fid,
            'order_content' => $order_content?$order_content:'',
            'order_imgs' => $order_imgs?$order_imgs:'',
            'order_type' => $data['order_type'] ? intval($data['order_type']):0,
            'uid' => $data['uid'] ? $data['uid'] : 0,
            'bind_id' => isset($data['bind_id']) ? $data['bind_id'] : 0,
            'phone' => $phone ? $phone : '',
            'name' => $name ? $name : '',
            'grid_member_id' => $grid_member_id ? $grid_member_id : 0,
            'now_role' => $data['now_role'] ? $data['now_role'] : 1,
            'order_status' => $data['order_status'] ? $data['order_status'] : 1,
            'event_status' => $data['event_status'] ? $data['event_status'] : 0,
            'area_id' => $street_id,
            'area_type' => $area_type ? $area_type : 0,
            'order_go_by_center' => isset($data['order_go_by_center']) ? $data['order_go_by_center'] : 0,
        ];
        if($community_id>0){
            $add_data['community_id']=$community_id;
        }
        $areaStreetWorkersOrder = new AreaStreetWorkersOrder();
        $order_id = $areaStreetWorkersOrder->add($add_data);
        if (!$order_id) {
            throw new \think\Exception('添加失败');
        }
        //触发发送通知
        $this->streetWorkersOrderSendMsg(10,$order_id);
        $log_data = [
            'order_id' => $order_id,
            'log_operator' => $name ? $name : '',
            'log_phone' => $phone ? $phone : '',
            'operator_type' => $operator_type,
            'log_type' => $log_type,
            'operator_id' => isset($data['bind_id']) ? $data['bind_id'] : 0,
            'log_uid' => $data['uid'] ? $data['uid'] : 0,
            'log_content' =>  $order_content?$order_content:'',
            'log_imgs' => $order_imgs?$order_imgs:'',
        ];
        $this->addWorksOrder($log_name,$log_data);
        $add_msg['order_id'] = $order_id;
        return $add_msg;
    }

    /**
     * Notes: 添加操作日志
     * @param $log_name
     * @param $data
     * @return bool
     * @author: wanzy
     * @date_time: 2021/2/24 17:39
     */
    public function addWorksOrder($log_name,$data) {
        if (!$log_name || !$data || !$data['order_id']) {
            return false;
        }
        $area_street_workers_order_log = new AreaStreetWorkersOrderLog();
        $data['log_name'] = $log_name;
        $data['add_time'] = time();
        $log_imgs = isset($data['log_imgs'])?$data['log_imgs']:'';
        if (is_array($log_imgs)) {
            $log_imgs = implode(';',$log_imgs);
        } else {
            $log_imgs = trim($log_imgs);
        }
        // 查询下同样工单同样操作次数
        $where_count = [];
        $where_count[] = ['l.order_id', '=', intval($data['order_id'])];
        $where_count[] = ['l.log_name', '=', strval($data['log_name'])];
        $log_num = $area_street_workers_order_log->getCount($where_count);
        if (!$log_num) {
            $log_num = 1;
        } else {
            $log_num = intval($log_num) + 1;
        }
        $add_data = [
            'order_id' => intval($data['order_id']),
            'log_name' => strval($data['log_name']),
            'log_operator' => strval($data['log_operator']),
            'log_phone' => isset($data['log_phone']) ? strval($data['log_phone']) : '',
            'operator_type' => $data['operator_type'] ? intval($data['operator_type']) : 0,
            'log_type' => $data['log_type'] ? intval($data['log_type']) : 0,
            'operator_id' => isset($data['operator_id']) ? intval($data['operator_id']) : '',
            'log_uid' => isset($data['log_uid']) ? intval($data['log_uid']) : 0,
            'log_content' => isset($data['log_content']) ? htmlspecialchars(trim(($data['log_content']))) : '',
            'log_imgs' => $log_imgs ? $log_imgs : '',
            'log_num' => $log_num ? $log_num : 1,
        ];
        $add_data['add_time'] = time();
        $log_id = $area_street_workers_order_log->add($add_data);
        if (!$log_id) {
            fdump_api('添加操作日志>>'.__LINE__,'add_works_order_err_log',1);
            fdump_api($log_name,'add_works_order_err_log',1);
            fdump_api($data,'add_works_order_err_log',1);
            fdump_api($add_data,'add_works_order_err_log',1);
            fdump_api($log_id,'add_works_order_err_log',1);
            return false;
        }
        return $log_id;
    }

    /**
     * Notes: 获取用户工单详情
     * @param $where
     * @param bool|string $field
     * @param bool $is_arr 是否转成显示结构
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/2/24 20:01
     */
    public function getUserWorksOrder($where,$field=true,$is_arr=false) {
        $areaStreetWorkersOrder = new AreaStreetWorkersOrder();
        $order_detail = $areaStreetWorkersOrder->getOne($where,$field);
        if ($order_detail) {
            $order_detail = $order_detail->toArray();
        } else {
            $order_detail = [];
        }
        if (isset($order_detail['cat_id'])) {
            $service_area_street_event_category = new AreaStreetEventCategoryService();
            $where = [];
            $where[] = ['cat_id','=',$order_detail['cat_id']];
            $field = 'cat_id,cat_name,cat_fid,add_time';
            $cat_info = $service_area_street_event_category->getCategoryDetail($where,$field);
            if ($cat_info && isset($cat_info['cat_txt'])) {
                $order_detail['cat_txt'] = $cat_info['cat_txt'];
            }
        }
        if (isset($order_detail['order_imgs']) && $order_detail['order_imgs']) {
            $order_imgs = explode(';',$order_detail['order_imgs']);
            if ($order_imgs) {
                $order_detail['order_imgs_arr'] = [];
                foreach ($order_imgs as $val) {
                    $order_detail['order_imgs_arr'][] = replace_file_domain($val);
                }
            }
        }
        if (isset($order_detail['order_status']) && $order_detail['order_status']) {
            $order_detail['order_status_txt'] = $this->order_status_txt[$order_detail['order_status']];
            $order_detail['order_status_color'] = $this->order_status_color[$order_detail['order_status']];
            // 已办结状态
            if (2==$order_detail['order_status']) {
                $order_detail['change_status'] = [
                    ['log'=>'user_recall','name'=>'撤回'],
                    ['log'=>'user_closed','name'=>'关闭'],
                    ['log'=>'user_reopen','name'=>'重新打开'],
                ];
            } elseif (1==$order_detail['order_status'] || 3==$order_detail['order_status']) {
                $order_detail['change_status'] = [
                    ['log'=>'user_recall','name'=>'撤回'],
                    ['log'=>'user_closed','name'=>'关闭'],
                ];
            } elseif (4==$order_detail['order_status'] || 5==$order_detail['order_status']) {
                $order_detail['change_status'] = [
                    ['log'=>'user_reopen','name'=>'重新打开'],
                ];
            } else {
                $order_detail['change_status'] = [];
            }
        }
        if (isset($order_detail['order_time']) && $order_detail['order_time']) {
            $order_detail['order_time_txt'] = date('Y-m-d H:i:s',$order_detail['order_time']);
        }
        if ($is_arr && $order_detail) {
            $order_arr = [];
            if (isset($order_detail['polygon_name']) && $order_detail['polygon_name']) {
                // type 为1 展示 content 为字符串  type为2  展示 children 为数组 type为3时候 展示imgs 为数组
                $order_arr[] = [
                    'title' => '上报网格',
                    'type' => 1,
                    'content' => $order_detail['polygon_name'],
                    'content_color' => '',
                    'children' => [],
                    'imgs' => [],
                ];
            }
            if (isset($order_detail['order_address']) && $order_detail['order_address']) {
                $order_arr[] = [
                    'title' => '所在位置',
                    'type' => 1,
                    'content' => $order_detail['order_address'],
                    'content_color' => '',
                    'children' => [],
                    'imgs' => [],
                ];
            }
            if (isset($order_detail['order_time_txt']) && $order_detail['order_time_txt']) {
                $order_arr[] = [
                    'title' => '上报时间',
                    'type' => 1,
                    'content' => $order_detail['order_time_txt'],
                    'content_color' => '',
                    'children' => [],
                    'imgs' => [],
                ];
            }
            if (isset($order_detail['cat_txt']) && $order_detail['cat_txt']) {
                $order_arr[] = [
                    'title' => '工单分类',
                    'type' => 1,
                    'content' => $order_detail['cat_txt'],
                    'content_color' => '',
                    'children' => [],
                    'imgs' => [],
                ];
            }
            if (isset($order_detail['order_content']) && $order_detail['order_content']) {
                $order_arr[] = [
                    'title' => '上报内容',
                    'type' => 1,
                    'content' => $order_detail['order_content'],
                    'content_color' => '',
                    'children' => [],
                    'imgs' => [],
                ];
            }
            if (isset($order_detail['order_imgs_arr']) && $order_detail['order_imgs_arr']) {
                $order_arr[] = [
                    'title' => '上报图例',
                    'type' => 3,
                    'content' => '',
                    'content_color' => '',
                    'children' => [],
                    'imgs' => $order_detail['order_imgs_arr'],
                ];
            }
            if (isset($order_detail['order_status_txt']) && $order_detail['order_status_txt']) {
                $order_arr[] = [
                    'title' => '工单状态',
                    'type' => 1,
                    'content' => $order_detail['order_status_txt'],
                    'content_color' => $order_detail['order_status_color'],
                    'children' => [],
                    'imgs' => [],
                ];
            }
            // 获取工单对应处理记录 按照时间顺序倒叙
            $log = [];
            if (isset($order_detail['order_id']) && $order_detail['order_id']) {
                $order_log = 'add_time DESC';
                $where_log = [];
                $where_log[] = ['log_type','=',0];
                $where_log[] = ['order_id','=',$order_detail['order_id']];
                $field_log = 'log_id,order_id,log_name,log_operator,log_phone,log_uid';
                $db_area_street_workers_order_log = new AreaStreetWorkersOrderLog();
                $log_new = $db_area_street_workers_order_log->getSome($where_log, $field_log,$order_log,1,1);
                if (!empty($log_new)) {
                    $children = [];
                    $log_new = $log_new->toArray();
                    if (!empty($log_new)) {
                        $log_new = $log_new[0];
                    }
                    if (isset($log_new['log_operator']) && $log_new['log_operator']) {
                        $children[] = [
                            'title' => '当前处理人员',
                            'content' => $log_new['log_operator'],
                            'content_color' => '',
                        ];
                    }
                    if (isset($log_new['log_phone']) && $log_new['log_phone']) {
                        $children[] = [
                            'title' => '手机号码',
                            'content' => $log_new['log_phone'],
                            'content_color' => '',
                        ];
                    }

                    $log = [
                        'title' => '处理记录',
                        'type' => 2,
                        'content' => $order_detail['order_status_txt'],
                        'content_color' => $order_detail['order_status_color'],
                        'children' => $children,
                        'imgs' => [],
                    ];
                }
            }
            if (empty($log)) {
                $log = (object)[];
            }
            $order_detail['log'] = $log;

            $order_detail['order_arr'] = $order_arr;
        }
        return $order_detail;
    }

    /**
     * Notes: 用户获取操作记录
     * @param $order_id
     * @param $uid
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/2/27 14:16
     */
    public function userGetOrderLog($order_id, $uid) {
        $areaStreetWorkersOrder = new AreaStreetWorkersOrder();
        $where = [];
        $where[] = ['order_id','=',$order_id];
        $where[] = ['uid','=',$uid];
        $field = 'order_id';
        $order_detail = $areaStreetWorkersOrder->getOne($where,$field);
        if (!$order_detail) {
            throw new \think\Exception('对应工单不存在');
        }
        $db_area_street_workers_order_log = new AreaStreetWorkersOrderLog();

        // 提交工单者 可以看到的操作
        $user_log = ['user_submit','member_reply_user','user_recall','user_closed','user_reopen','member_submit'];
        $where_log = [];
        $where_log[] = ['order_id','=',$order_id];
        $where_log[] = ['log_name','in',$user_log];
        $log_list = $db_area_street_workers_order_log->getSome($where_log, true,'add_time DESC',0,0);
        $log = [];
        if ($log_list) {
            $log_list = $log_list->toArray();
            $info = [];
            $log_arr = $this->user_log_name;
            foreach ($log_list as $val) {
                if (isset($val['log_name']) && $val['log_name']) {
                    if (isset($val['log_num']) && $val['log_num']>1) {
                        $title = "第{$val['log_num']}次".$log_arr[$val['log_name']];
                    } else {
                        $title = $log_arr[$val['log_name']];
                    }
                    if (isset($val['add_time']) && $val['add_time']) {
                        $val['add_time_txt'] = date('Y-m-d H:i:s',$val['add_time']);
                    }
                    $tip = [];
                    if (isset($val['log_operator']) && $val['log_operator']) {
                        $tip[] = [
                            'title' => '操作人员',
                            'content' => $val['log_operator'],
                            'content_color' => '',
                            'type' => 1,
                            'imgs' => [],
                        ];
                    }
                    if (isset($val['log_phone']) && $val['log_phone']) {
                        $tip[] = [
                            'title' => '手机号码',
                            'content' => $val['log_phone'],
                            'content_color' => '',
                            'type' => 1,
                            'imgs' => [],
                        ];
                    }
                    if (isset($val['log_content']) && $val['log_content']) {
                        $tip[] = [
                            'title' => '内容',
                            'content' => $val['log_content'],
                            'content_color' => '',
                            'type' => 1,
                            'imgs' => [],
                        ];
                    }
                    if (isset($val['log_imgs']) && $val['log_imgs']) {
                        $log_imgs = explode(';',$val['log_imgs']);
                        if ($log_imgs) {
                            $val['log_imgs_arr'] = [];
                            foreach ($log_imgs as $val1) {
                                $val['log_imgs_arr'][] = replace_file_domain($val1);
                            }
                        }
                        $tip[] = [
                            'title' => '',
                            'content' => '',
                            'content_color' => '',
                            'type' => 2,
                            'imgs' => $val['log_imgs_arr'],
                        ];
                    }
                    $info[] = [
                        'title' => $title,
                        'log_time' => isset($val['add_time_txt']) ? $val['add_time_txt'] : '',
                        'tip' => $tip
                    ];
                }
            }
            $log['info'] = $info;
        } else {
            $log_list = [];
        }
        $log['list'] = $log_list;
        return $log;
    }

    /**
     * Notes: 添加工单记录 过滤处理
     * @param int $operator_type
     * @param $data
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/2/25 14:44
     */
    public function filterAddWorkOrderLog($operator_type=0,$data) {
        $log_name = $data['log_name'];
        if (!$log_name) {
            throw new \think\Exception('缺少必传参数');
        }
        if (!isset($data['order_id']) || !$data['order_id']) {
            throw new \think\Exception('缺少必传参数');
        }
        $order_id = $data['order_id'];
        $log_arr = [];
        $status = '';
        $workerInfo=array('worker_id'=>0,'grid_member_id'=>0);
        if (0==$operator_type) {
            $where_order = [];
            $where_order[] = ['order_id','=',$order_id];
            $where_order[] = ['del_time','=',0];
            $areaStreetWorkersOrder = new AreaStreetWorkersOrder();
            $order_detail = $areaStreetWorkersOrder->getOne($where_order,'e.*');
            // 获取住户对应状态可以更改的
            if (2==$order_detail['order_status']) {
                $change_status = ['user_recall','user_closed','user_reopen'];
            } elseif (1==$order_detail['order_status'] || 3==$order_detail['order_status']) {
                $change_status = ['user_recall','user_closed'];
            } elseif (4==$order_detail['order_status'] || 5==$order_detail['order_status']) {
                $change_status = ['user_reopen'];
            } else {
                throw new \think\Exception('当前状态用户无法进行修改');
            }
            if (!in_array($log_name,$change_status)) {
                throw new \think\Exception('请上报正确处理类型');
            }

            $order_imgs = $data['order_imgs'];
            $log_type = 0;
            if (is_array($order_imgs)) {
                $order_imgs = implode(';',$order_imgs);
            } elseif (is_string($order_imgs)) {
                $order_imgs = trim($order_imgs);
            }
            $order_content = $data['order_content'];
            
            // 添加记录前先行修改订单状态
            if ('user_recall'==$log_name) {
                $order_set = [
                    'order_status' => 5,
                    'event_status' => 5,
                ];
                $status = 'processed'; // 已处理
                if (1==$order_detail['order_go_by_center']) {
                    $log_type = 1;
                }
                $this->streetWorkersOrderSendMsg('user_recall',$order_id,$workerInfo);
            } elseif ('user_closed'==$log_name) {
                $order_set = [
                    'order_status' => 4,
                    'event_status' => 6,
                ];
                $status = 'processed'; // 已处理
                if (1==$order_detail['order_go_by_center']) {
                    $log_type = 1;
                }
                $this->streetWorkersOrderSendMsg('user_closed',$order_id,$workerInfo);
            } elseif ('user_reopen'==$log_name) {
                $order_set = [
                    'order_status' => 1,
                    'event_status' => 0,
                ];
                $status = 'processing'; // 处理中
                if (1==$order_detail['order_go_by_center']) {
                    $log_type = 1;
                }
                $this->streetWorkersOrderSendMsg('user_reopen',$order_id,$workerInfo);
            } else {
                throw new \think\Exception('请上报正确处理类型');
            }
            $order_set['last_time'] = time();
            $where_update = [];
            $where_update[] = ['order_id','=',$order_id];
            $order_update = $areaStreetWorkersOrder->updateThis($where_update,$order_set);
            if ($order_update) {
                // 更新工单成功 记录操作
                $log_data = [
                    'order_id' => $order_id,
                    'log_operator' => $order_detail['name'] ? $order_detail['name'] : '',
                    'log_phone' => $order_detail['phone'] ? $order_detail['phone'] : '',
                    'operator_type' => $operator_type,
                    'log_type' => $log_type,
                    'operator_id' => $order_detail['bind_id'] ? $order_detail['bind_id'] : 0,
                    'log_uid' => $order_detail['uid'] ? $order_detail['uid'] : 0,
                    'log_content' =>  $order_content?$order_content:'',
                    'log_imgs' => $order_imgs?$order_imgs:'',
                ];
                $log_id = $this->addWorksOrder($log_name,$log_data);
                $log_arr['log_id'] = $log_id;
                $log_arr['status'] = $status;
            } else {
                throw new \think\Exception('处理失败');
            }
            return $log_arr;
        } elseif (1==$operator_type) {
            if (!isset($data['worker_id']) || !$data['worker_id']) {
                throw new \think\Exception('缺少必传参数');
            }
            $worker_id = $data['worker_id'];
            $workerInfo['worker_id']=$worker_id;
            $order_detail = $this->workGetOrder($order_id,$worker_id,false,true);

            $change_status_word = isset($order_detail['change_status_word']) ? $order_detail['change_status_word'] : [];
            if (empty($change_status_word)) {
                throw new \think\Exception('当前状态用户无法进行修改');
            }
            if (!in_array($log_name,$change_status_word)) {
                throw new \think\Exception('请上报正确处理类型');
            }
            if (isset($order_detail['worker_info'])) {
                $worker_info = $order_detail['worker_info'];
            }
            $grid_member_community_id=0;
            if (isset($order_detail['grid_member_info'])) {
                $grid_member_info = $order_detail['grid_member_info'];
                if($grid_member_info && isset($grid_member_info['community_id']) && $grid_member_info['business_type']==2 && $grid_member_info['community_id']>0){
                    $grid_member_community_id=$grid_member_info['community_id'];
                }
            }
            
            $order_imgs = $data['order_imgs'];
            if (is_array($order_imgs)) {
                $order_imgs = implode(';',$order_imgs);
            } elseif (is_string($order_imgs)) {
                $order_imgs = trim($order_imgs);
            }
            $order_content = $data['order_content'];
            // 添加记录前先行修改订单状态
            $log_type = 0;
            $log_operator = $order_detail['name'] ? $order_detail['name'] : '';
            $log_phone = $order_detail['phone'] ? $order_detail['phone'] : '';
            $operator_id=$order_detail['bind_id'] ? $order_detail['bind_id'] : 0;
            if ('member_submit'==$log_name) {
                // 网格员操作提交事件至时间处理中心
                $order_set = [
                    'now_role' => 2,
                    'order_status' => 3,
                    'event_status' => 1,
                    'order_go_by_center' => 1
                ];
                if($grid_member_community_id>0){
                    $order_set['community_id']=$grid_member_community_id;
                }
                $status = 'processing'; // 处理中
                $operator_type = 1;
                $log_type = 1;
                $log_operator = $grid_member_info['name'] ? $grid_member_info['name'] : '';
                $log_phone = $grid_member_info['phone'] ? $grid_member_info['phone'] : '';
                $operator_id = $grid_member_info['id'];
            } elseif ('member_reply_user'==$log_name) {
                // 网格员回复工单提出者   相当于工单已结单
                $order_set = [
                    'now_role' => 0,
                    'order_status' => 2,
                    'event_status' => 4,
                ];
                if($grid_member_community_id>0){
                    $order_set['community_id']=$grid_member_community_id;
                }
                $status = 'processed'; // 已处理
                $operator_type = 1;
                if (1==$order_detail['order_go_by_center']) {
                    $log_type = 1;
                }
                $log_operator = $grid_member_info['name'] ? $grid_member_info['name'] : '';
                $log_phone = $grid_member_info['phone'] ? $grid_member_info['phone'] : '';
                $operator_id = $grid_member_info['id'];
                
                $this->streetWorkersOrderSendMsgToUser('member_reply_user',$order_id,$workerInfo);
            } elseif ('member_recall'==$log_name) {
                // 网格员撤回工单
                $order_set = [
                    'now_role' => 0,
                    'order_status' => 5,
                    'event_status' => 5,
                ];
                $status = 'processed'; // 已处理
                $operator_type = 1;
                if (1==$order_detail['order_go_by_center']) {
                    $log_type = 1;
                }
                $this->streetWorkersOrderSendMsgToUser('member_recall',$order_id,$workerInfo);
            } elseif ('member_closed'==$log_name) {
                // 网格员关闭工单
                $order_set = [
                    'now_role' => 0,
                    'order_status' => 4,
                    'event_status' => 6,
                ];
                $status = 'processed'; // 已处理
                $operator_type = 1;
                if (1==$order_detail['order_go_by_center']) {
                    $log_type = 1;
                }
                $this->streetWorkersOrderSendMsgToUser('member_closed',$order_id,$workerInfo);
            } elseif ('member_reject_center'==$log_name) {
                // 网格员拒绝到事件处理中心
                $order_set = [
                    'now_role' => 2,
                    'order_status' => 3,
                    'event_status' => 1,
                ];
                $status = 'processing'; // 处理中
                $operator_type = 1;
                $log_type = 1;
                $log_operator = $grid_member_info['name'] ? $grid_member_info['name'] : '';
                $log_phone = $grid_member_info['phone'] ? $grid_member_info['phone'] : '';
                $operator_id = $grid_member_info['id'];
            } elseif ('member_reject_work'==$log_name) {
                // 网格员拒绝到事件处理人员
                $order_set = [
                    'now_role' => 3,
                    'order_status' => 3,
                    'event_status' => 3,
                ];
                $status = 'processing'; // 处理中
                $operator_type = 1;
                $log_type = 1;
                $log_operator = $grid_member_info['name'] ? $grid_member_info['name'] : '';
                $log_phone = $grid_member_info['phone'] ? $grid_member_info['phone'] : '';
                $operator_id = $grid_member_info['id'];
            } elseif ('member_reopen'==$log_name) {
                // 网格员重新打开工单
                $order_set = [
                    'now_role' => 1,
                    'order_status' => 1,
                    'event_status' => 0,
                ];
                if(isset($order_detail['is_post']) && $order_detail['is_post']){
                    $order_set['event_status']=1;
                }
                if($grid_member_community_id>0){
                    $order_set['community_id']=$grid_member_community_id;
                }
                $status = 'processing'; // 处理中
                $operator_type = 1;
                if (1==$order_detail['order_go_by_center']) {
                    $log_type = 1;
                }
                $this->streetWorkersOrderSendMsgToUser('member_reopen',$order_id,$workerInfo);
            } elseif ('work_follow_up'==$log_name) {
                // 处理人员跟进
                $order_set = [
                    'now_role' => 3,
                    'order_status' => 3,
                    'event_status' => 3,
                ];
                $status = 'todo'; // 处理中
                $operator_type = 3;
                $log_type = 1;
                $log_operator = $worker_info['work_name'] ? $worker_info['work_name'] : '';
                $log_phone = $worker_info['work_phone'] ? $worker_info['work_phone'] : '';
                $operator_id = $worker_info['worker_id'];
            } elseif ('work_completed'==$log_name) {
                // 处理人员结单
                $order_set = [
                    'now_role' => 1,
                    'order_status' => 3,
                    'event_status' => 4,
                ];
                $status = 'processing'; // 已处理
                $operator_type = 3;
                $log_type = 1;
                $log_operator = $worker_info['work_name'] ? $worker_info['work_name'] : '';
                $log_phone = $worker_info['work_phone'] ? $worker_info['work_phone'] : '';
                $operator_id = $worker_info['worker_id'];
                $workerInfo=array('worker_id'=>$operator_id,'grid_member_id'=>0,'work_name'=>$log_operator,'work_phone'=>$log_phone);
                $this->streetWorkersOrderSendMsgToUser('work_completed',$order_id,$workerInfo);
            } elseif ('work_reject_center'==$log_name) {
                // 处理人员拒绝到事件处理中心
                $order_set = [
                    'now_role' => 2,
                    'order_status' => 3,
                    'event_status' => 1,
                ];
                $status = 'processing'; // 处理中
                $operator_type = 3;
                $log_type = 1;
                $log_operator = $worker_info['work_name'] ? $worker_info['work_name'] : '';
                $log_phone = $worker_info['work_phone'] ? $worker_info['work_phone'] : '';
                $operator_id = $worker_info['worker_id'];
            } else {
                throw new \think\Exception('请上报正确处理类型');
            }
            $order_set['last_time'] = time();

            $areaStreetWorkersOrder = new AreaStreetWorkersOrder();
            $where_update = [];
            $where_update[] = ['order_id','=',$order_id];
            $order_update = $areaStreetWorkersOrder->updateThis($where_update,$order_set);
            if ($order_update) {
                // 更新工单成功 记录操作
                $log_data = [
                    'order_id' => $order_id,
                    'log_operator' => $log_operator,
                    'log_phone' => $log_phone,
                    'operator_type' => $operator_type,
                    'log_type' => $log_type,
                    'operator_id' => $operator_id,
                    'log_uid' => $order_detail['uid'] ? $order_detail['uid'] : 0,
                    'log_content' =>  $order_content?$order_content:'',
                    'log_imgs' => $order_imgs?$order_imgs:'',
                ];
                $log_id = $this->addWorksOrder($log_name,$log_data);
                $log_arr['log_id'] = $log_id;
                $log_arr['status'] = $status;
            } else {
                throw new \think\Exception('处理失败');
            }
            return $log_arr;
        } else {
            return [];
        }
    }

    /**
     * Notes: 工作人员获取工单时间 包含网格员和事件处理人员
     * @param int $worker_id 工作人员id
     * @param string $status 需要查询的状态
     * @param string $search
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/3/1 13:49
     */
    public function workGetWorksOrderLists($worker_id, $status='todo', $search='',$page=1,$page_size=10,$order='order_time DESC') {
        $dbAreaStreetWorkers = new AreaStreetWorkers();
        $db_area_street_grid_member = new HouseVillageGridMember();
        $db_area_street_workers_order = new AreaStreetWorkersOrder();
        $where_base =[];
        $where_base[] = ['worker_id','=',$worker_id];
        $where_base[] = ['is_handle_event','=',1];
        $work_info = $dbAreaStreetWorkers->getOne($where_base);
        $grid_member_info = $db_area_street_grid_member->getOne(['workers_id'=>$worker_id]);
//        $whereOr = [];
//        $where_work = [];
//        $where_member = [];
//        $where_member1 = [];
        $whereRaw = '';
        $where_work_str = '';
        $where_member_str  = '';
        if ($search) {
            $search = str_replace("'","\'",$search);
        }
        if ('todo'==$status) {
            // 待处理
            if ($search) {
                $search_str = "(a.name='{$search}' OR a.phone='{$search}')";
            } else {
                $search_str = '';
            }
            if (!empty($work_info)) {
                $where_work_str = "(b.worker_id={$worker_id} AND a.event_status in (2,3))";
            } else {
                $where_work_str = '';
            }
            if (!empty($grid_member_info)) {
                // 自己提交的工单
                $where_member_str = "(a.order_type=1 AND a.uid={$grid_member_info['id']} AND a.order_status=2)";
                // 当前是自己进行处理的工单
                $where_member1_str = "(a.grid_member_id={$grid_member_info['id']} AND ((a.event_status=0 AND a.order_status=1) OR (a.event_status=4 AND a.order_status=3)))";
            } else {
                $where_member_str = '';
            }
        } elseif ('processing'==$status) {
            //处理中
            if ($search) {
                $search_str = "(a.name='{$search}' OR a.phone='{$search}')";
            } else {
                $search_str = '';
            }
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
        } elseif ('processed'==$status) {
            // 已处理
            if ($search) {
                $search_str = "(a.name='{$search}' OR a.phone='{$search}')";
            } else {
                $search_str = '';
            }
            if (!empty($work_info)) {
                $where_work_str = "(b.worker_id={$worker_id} AND a.event_status in (5,6))";
            } else {
                $where_work_str = '';
            }
            if (!empty($grid_member_info)) {
                // 自己提交的工单
                $where_member_str = "(a.order_type=1 AND a.uid={$grid_member_info['id']} AND a.order_status in (4,5))";
                // 当前是自己的工单 处于处理中
                $where_member1_str = "((a.grid_member_id={$grid_member_info['id']} AND a.event_status in (5,6)) OR (a.grid_member_id={$grid_member_info['id']} AND a.order_status=2 AND a.event_status=4))";
            } else {
                $where_member_str = '';
            }
        } elseif ('all'==$status) {
            // 全部
            if ($search) {
                $search_str = "(a.name='{$search}' OR a.phone='{$search}')";
            } else {
                $search_str = '';
            }
            if (!empty($work_info)) {
                $where_work_str = "(b.worker_id={$worker_id})";
            } else {
                $where_work_str = '';
            }
            if (!empty($grid_member_info)) {
                // 自己提交的工单
                $where_member_str = "(a.order_type=1 AND a.uid={$grid_member_info['id']})";
                // 当前是自己的工单 处于处理中
                $where_member1_str = "(a.grid_member_id={$grid_member_info['id']})";
            } else {
                $where_member_str = '';
            }
        }

        if ($where_work_str && $where_member_str) {
            $whereRaw = "({$where_work_str} OR {$where_member_str} OR {$where_member1_str})";
        } elseif ($where_work_str) {
            $whereRaw = "({$where_work_str})";
        } elseif ($where_member_str) {
            $whereRaw = "({$where_member_str} OR {$where_member1_str})";
        }
        if ($search_str) {
            $whereRaw = "{$search_str} AND " .$whereRaw;
        }

        if (!$whereRaw) {
            $arr = [];
            $arr['is_street_worker'] = false;
            $arr['worker_id'] = 0;
            $arr['is_grid_member'] = false;
            $arr['grid_member_id'] = 0;
            $arr['list'] = [];
            return $arr;
        }
        $order = 'a.last_time DESC,a.order_time DESC,b.bind_id DESC';
        $field = 'a.order_id,a.order_time,a.cat_id,a.name,a.phone,a.cat_fid,a.order_content,a.order_type,a.uid,a.bind_id,a.last_time,a.order_status,a.event_status,a.order_go_by_center';
        $work_list = $db_area_street_workers_order->getWorkHandleList([['a.del_time','=',0]],[],$whereRaw,$field,$order,'a.order_id',$page,$page_size);
        if (!empty($work_list)) {
            $work_list = $work_list->toArray();
            if ($work_list) {
                foreach ($work_list as &$val) {
                    if (isset($val['order_time']) && $val['order_time']) {
                        $val['order_time_txt'] = date('Y-m-d H:i:s',$val['order_time']);
                    }
                    if (isset($val['last_time']) && $val['last_time']) {
                        $val['last_time_txt'] = date('Y-m-d H:i:s',$val['last_time']);
                    }
                        if (isset($val['order_go_by_center']) && 1==$val['order_go_by_center'] && (!empty($work_info) || !empty($grid_member_info))) {
                            // 转为了事件  且不仅仅是提出人员
                            if (isset($val['event_status']) && $val['event_status']) {
                                $val['order_status_txt'] = $this->event_status_txt[$val['event_status']];
                                $val['order_status_color'] = $this->event_status_color[$val['event_status']];
                            } elseif (isset($val['order_status']) && $val['order_status']) {
                                $val['order_status_txt'] = $this->order_status_txt[$val['order_status']];
                                $val['order_status_color'] = $this->order_status_color[$val['order_status']];
                            }
                        } else {
                            if (isset($val['order_status']) && $val['order_status']) {
                                $val['order_status_txt'] = $this->order_status_txt[$val['order_status']];
                                $val['order_status_color'] = $this->order_status_color[$val['order_status']];
                            } elseif (isset($val['event_status']) && $val['event_status']) {
                                $val['order_status_txt'] = $this->event_status_txt[$val['event_status']];
                                $val['order_status_color'] = $this->event_status_color[$val['event_status']];
                            }
                        }
                }
            }
        } else {
            $work_list = [];
        }
        $arr = [];
        $arr['list'] = $work_list;
        if ($grid_member_info) {
            $arr['is_grid_member'] = true;
            $arr['grid_member_id'] = $grid_member_info['id'];
        } else {
            $arr['is_grid_member'] = false;
            $arr['grid_member_id'] = 0;
        }
        if ($work_info) {
            $arr['is_street_worker'] = true;
            $arr['worker_id'] = $work_info['worker_id'];
        } else {
            $arr['is_street_worker'] = false;
            $arr['worker_id'] = 0;
        }
        return $arr;
    }

    /**
     * Notes: 工作人员获取工单操作历史记录
     * @param $order_id
     * @param $worker_id
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/3/3 17:54
     */
    public function workGetOrderLog($order_id, $worker_id) {
        $order_detail = $this->workGetOrder($order_id,$worker_id,false,true);
        if (!$order_detail) {
            throw new \think\Exception('对应工单不存在');
        }
        $is_post = isset($order_detail['is_post']) ? $order_detail['is_post'] : false;
        $is_member = isset($order_detail['is_member']) ? $order_detail['is_member'] : false;
        $is_now_worker = isset($order_detail['is_now_worker']) ? $order_detail['is_now_worker'] : false;
        $is_old_worker = isset($order_detail['is_old_worker']) ? $order_detail['is_old_worker'] : false;
        if (!$is_post && !$is_member && !$is_now_worker && !$is_old_worker) {
            throw new \think\Exception('您当前账号不能查看该工单信息');
        }

        $db_area_street_workers_order_log = new AreaStreetWorkersOrderLog();

        // 根据身份获取可以看到的信息
        $where_log = [];
        if ($is_post && !$is_member && !$is_now_worker && !$is_old_worker) {
            // 身份仅仅是工单提出这 只能看到
            $user_log = ['user_submit','member_reply_user','user_recall','user_closed','user_reopen'];
            $log_arr = $this->user_log_name;
            if (isset($order_detail['order_type']) && 1==$order_detail['order_type'] && isset($order_detail['uid']) && $worker_id = $order_detail['uid']) {
                //工作人员自行提的 还可以看到
                $user_log[] = 'member_user_submit';
                $user_log[] = 'member_submit';
                $user_log[] = 'member_reopen';
                $user_log[] = 'member_recall';
                $user_log[] = 'member_closed';
                $user_log[] = 'member_reject_work';
                $log_arr = $this->member_log_name;
            }
            $where_log[] = ['log_name','in',$user_log];
        } elseif (($is_now_worker || $is_old_worker) && !$is_post && !$is_member) {
            // 仅仅是事件处理人员或者曾经为工单事件处理人员 仅可以查看事件操作
            $where_log[] = ['log_type','=',1];
            $log_arr = $this->work_log_name;
        } elseif ($is_member) {
            $log_arr = $this->member_log_name;
        }

        $where_log[] = ['order_id','=',$order_id];
        $log_list = $db_area_street_workers_order_log->getSome($where_log, true,'add_time DESC',0,0);
        $log = [];
        if ($log_list) {
            $log_list = $log_list->toArray();
            $info = [];
            foreach ($log_list as $val) {
                if (isset($val['log_name']) && $val['log_name']) {
                    if (isset($val['log_num']) && $val['log_num']>1) {
                        $title = "第{$val['log_num']}次".$log_arr[$val['log_name']];
                    } else {
                        $title = $log_arr[$val['log_name']];
                    }
                    if (isset($val['add_time']) && $val['add_time']) {
                        $val['add_time_txt'] = date('Y-m-d H:i:s',$val['add_time']);
                    }
                    $tip = [];
                    if (isset($val['log_operator']) && $val['log_operator']) {
                        $tip[] = [
                            'title' => '操作人员',
                            'content' => $val['log_operator'],
                            'content_color' => '',
                            'type' => 1,
                            'imgs' => [],
                        ];
                    }
                    if (isset($val['log_phone']) && $val['log_phone']) {
                        $tip[] = [
                            'title' => '手机号码',
                            'content' => $val['log_phone'],
                            'content_color' => '',
                            'type' => 1,
                            'imgs' => [],
                        ];
                    }
                    if (isset($val['log_content']) && $val['log_content']) {
                        $tip[] = [
                            'title' => '内容',
                            'content' => $val['log_content'],
                            'content_color' => '',
                            'type' => 1,
                            'imgs' => [],
                        ];
                    }
                    if (isset($val['log_imgs']) && $val['log_imgs']) {
                        $log_imgs = explode(';',$val['log_imgs']);
                        if ($log_imgs) {
                            $val['log_imgs_arr'] = [];
                            foreach ($log_imgs as $val1) {
                                $val['log_imgs_arr'][] = replace_file_domain($val1);
                            }
                        }
                        $tip[] = [
                            'title' => '',
                            'content' => '',
                            'content_color' => '',
                            'type' => 2,
                            'imgs' => $val['log_imgs_arr'],
                        ];
                    }
                    $info[] = [
                        'title' => $title,
                        'log_time' => isset($val['add_time_txt']) ? $val['add_time_txt'] : '',
                        'tip' => $tip
                    ];
                }
            }
            $log['info'] = $info;
        } else {
            $log_list = [];
        }
        $log['list'] = $log_list;
        return $log;
    }

    /**
     * 获取工单事件数量
     * @author lijie
     * @date_time 2021/02/24
     * @param $where
     * @param $group
     * @return int
     */
    public function getWorkersOrderCount($where,$group='')
    {
        $db_area_street_workers_order = new AreaStreetWorkersOrder();
        $count = $db_area_street_workers_order->getCount($where,$group);
        return $count;
    }

    /**
     * 根据分组查询工单事件
     * @author lijie
     * @date_time 2021/02/24
     * @param $where
     * @param bool $field
     * @param string $group
     * @return mixed
     */
    public function getWorkersOrderListByGroup($where,$field=true,$group='e.grid_range_id')
    {
        $db_area_street_workers_order = new AreaStreetWorkersOrder();
        $data = $db_area_street_workers_order->getListByGroup($where,$field,$group);
        return $data;
    }

    /**
     * 获取工单详情
     * @author lijie
     * @date_time 2021/02/24
     * @param $where
     * @param bool $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getWorkersOrderInfo($where,$field=true)
    {
        $db_area_street_workers_order = new AreaStreetWorkersOrder();
        $db_area_street_workers_order_log = new AreaStreetWorkersOrderLog();
        $data = $db_area_street_workers_order->getOne($where,$field);
        if($data){
            $data['event_status_txt'] = $this->event_status_txt[$data['event_status']];
            $data['event_status_color'] = $this->event_status_color[$data['event_status']];
            $data['order_time_txt'] = date('Y-m-d H:i:s',$data['order_time']);
            $log_info = $db_area_street_workers_order_log->getOne(['order_id'=>$data['order_id']],'log_content','log_id ASC');
            $data['order_content'] = $log_info['log_content'];
            $db_area_street_event_category = new AreaStreetEventCategory();
            if($data['cat_id']){
                $info = $db_area_street_event_category->getOne(['cat_id'=>$data['cat_id']],'cat_name');
                $data['cat_name'] = $info['cat_name'];
            }
            if($data['cat_fid']){
                $info = $db_area_street_event_category->getOne(['cat_id'=>$data['cat_fid']],'cat_name');
                $data['cat_fname'] = $info['cat_name'];
            }
            if($data['order_imgs']){
                $img_arr = explode(';',$data['order_imgs']);
                foreach ($img_arr as $k=>$v){
                    $img_arr[$k] = dispose_url($v);
                }
                $data['order_imgs'] = $img_arr;
            }else{
                $data['order_imgs'] = [];
            }
        }
        return $data;
    }

    /**
     * 事件处理记录
     * @author lijie
     * @date_time 2021/02/24
     * @param array $where
     * @param bool $field
     * @param bool $order
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function getWorkersOrderLogList($where = [], $field = true,$order=true,$page=1,$limit=15)
    {
        $db_area_street_workers_order_log = new AreaStreetWorkersOrderLog();
        $data = $db_area_street_workers_order_log->getSome($where, $field,$order,$page,$limit);
        if($data){
            foreach ($data as $k=>$v){
                $data[$k]['center_log_name'] = $this->center_log_name[$v['log_name']];
                $data[$k]['add_time_txt'] = date('Y-m-d H:i:s',$v['add_time']);
                if($v['log_imgs']){
                    $img_arr = explode(';',$v['log_imgs']);
                    foreach ($img_arr as $k1=>$v1){
                        $img_arr[$k1] = dispose_url($v1);
                    }
                    $data[$k]['log_imgs'] = $img_arr;
                }else{
                    $data[$k]['log_imgs'] = [];
                }
            }
        }
        return $data;
    }

    /**
     * 工单列表
     * @author lijie
     * @date_time 2021/02/24
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $page_size
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getWorkerOrderLists($where,$field=true,$order='e.order_time DESC',$page=1,$page_size=10)
    {
        $db_area_street_workers_order = new AreaStreetWorkersOrder();
        $db_area_street_workers_order_log = new AreaStreetWorkersOrderLog();
        $data = $db_area_street_workers_order->getDataList($where,$field,$order,$page,$page_size);
        if($data){
            foreach ($data as $k=>&$v){
                $data[$k]['event_status_txt'] = $this->event_status_txt[$v['event_status']];
                $data[$k]['event_status_color'] = $this->event_status_color[$v['event_status']];
                $data[$k]['order_time_txt'] = date('Y-m-d H:i:s',$v['order_time']);
                $log_info = $db_area_street_workers_order_log->getOne(['order_id'=>$v['order_id']],'log_content','log_id ASC');
                $data[$k]['order_content'] =$log_info['log_content'];
                $db_area_street_event_category = new AreaStreetEventCategory();
                if($v['cat_id']){
                    $info = $db_area_street_event_category->getOne(['cat_id'=>$v['cat_id']],'cat_name');
                    $data[$k]['cat_name'] = $info['cat_name'];
                }
                if($v['cat_fid']){
                    $info = $db_area_street_event_category->getOne(['cat_id'=>$v['cat_fid']],'cat_name');
                    $data[$k]['cat_fname'] = $info['cat_name'];
                }
            }
        }
        return $data;
    }

    /**
     * 修改工单
     * @author lijie
     * @date_time 2021/03/01
     * @param $where
     * @param $data
     * @return bool
     */
    public function saveWorkOrder($where,$data)
    {
        $service_area_street_workers_order = new AreaStreetWorkersOrder();
        $res = $service_area_street_workers_order->saveOne($where,$data);
        return $res;
    }

    /**
     * Notes: 工作人员获取工单详情
     * @param $order_id
     * @param $worker_id
     * @param bool $is_arr
     * @return array|\think\Model|null
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/3/3 13:38
     */
    public function workGetOrder($order_id,$worker_id,$is_arr=false,$is_word=false) {
        $db_area_street_grid_member = new HouseVillageGridMember();
        $db_area_street_workers_order = new AreaStreetWorkersOrder();
        $dbAreaStreetWorkers = new AreaStreetWorkers();
        $where = [];
        $where[] = ['worker_id','=',$worker_id];
        $worker_info = $dbAreaStreetWorkers->getOne($where);
        if (empty($worker_info)) {
            throw new \think\Exception('对应工作人员不存在');
        }
        $grid_member_info = $db_area_street_grid_member->getOne(['workers_id'=>$worker_id]);
        $where_order = [];
        $where_order[] = ['order_id','=',$order_id];
        $where_order[] = ['del_time','=',0];
        $order_detail = $db_area_street_workers_order->getOne($where_order,'e.*,g.polygon_name');
        if (empty($order_detail)) {
            throw new \think\Exception('对应工单不存在');
        }
        if ($is_word) {
            $order_detail['worker_info'] = $worker_info;
            if (!empty($grid_member_info)) {
                $order_detail['grid_member_info'] = $grid_member_info;
            } else {
                $order_detail['grid_member_info'] = [];
            }
        }
        if (isset($order_detail['cat_id'])) {
            $service_area_street_event_category = new AreaStreetEventCategoryService();
            $where = [];
            $where[] = ['cat_id','=',$order_detail['cat_id']];
            $field = 'cat_id,cat_name,cat_fid,add_time';
            $cat_info = $service_area_street_event_category->getCategoryDetail($where,$field);
            if ($cat_info && isset($cat_info['cat_txt'])) {
                $order_detail['cat_txt'] = $cat_info['cat_txt'];
            }
        }
        if (isset($order_detail['order_imgs']) && $order_detail['order_imgs']) {
            $order_imgs = $order_detail['order_imgs'];
            $order_imgs = explode(';',$order_imgs);
            if ($order_imgs) {
                $order_imgs_arr = [];
                foreach ($order_imgs as $val) {
                    $order_imgs_arr[] = replace_file_domain($val);
                }
                $order_detail['order_imgs_arr'] = $order_imgs_arr;
            }
        }
        // 优先确认下 当前是以什么身份查看当前详情
        // 先看下是不是提出这
        $is_post = false; // 看下是不是当前网格员身份提交的
        if ($grid_member_info && 1==$order_detail['order_type'] && $order_detail['uid']==$grid_member_info['id']) {
            $is_post = true;
        }
        $is_member = false; // 是不是当前网格员处理的
        if ($grid_member_info && $order_detail['grid_member_id']==$grid_member_info['id']) {
            $is_member = true;
        }
        // 以下内容需要经历过事件中心后才可以查询
        $is_now_worker = false; // 是不是当前事件处理人员处理的
        $is_old_worker = false; // 在不是当前时间处理人员的时候查询是否为曾经事件处理人员
        if (1==$order_detail['order_go_by_center']) {
            if ($worker_info && $worker_info['worker_id']==$order_detail['order_worker_id']) {
                $is_now_worker = true;
            }
            if (!$is_now_worker) {
                $where_assign_work_count = [];
                $where_assign_work_count[] = ['a.order_id','=',$order_id];
                $where_assign_work_count[] = ['b.worker_id','=',$worker_id];
                $center_assign_work_count = $db_area_street_workers_order->getWorkHandleCount($where_assign_work_count);
                if ($center_assign_work_count && intval($center_assign_work_count)>0) {
                    $is_old_worker = true;
                }
            }
        }
        if (!$is_post && !$is_member && !$is_now_worker && !$is_old_worker) {
            throw new \think\Exception('您当前账号不能查看该工单信息');
        }
        $order_detail['is_post'] = $is_post;
        $order_detail['is_member'] = $is_member;
        $order_detail['is_now_worker'] = $is_now_worker;
        $order_detail['is_old_worker'] = $is_old_worker;


        // 获取工单对应处理记录 按照时间顺序倒叙
        $log = [];
        if (isset($order_detail['order_id']) && $order_detail['order_id']) {
            $order_log = 'add_time DESC';
            $where_log = [];
            if (!$is_member && $is_now_worker && $is_old_worker) {
                $where_log[] = ['log_type','=',0];
            }
            $where_log[] = ['order_id','=',$order_detail['order_id']];
            $field_log = 'log_id,order_id,log_name,log_operator,log_phone,log_uid';
            $db_area_street_workers_order_log = new AreaStreetWorkersOrderLog();
            $log_new = $db_area_street_workers_order_log->getSome($where_log, $field_log,$order_log,1,1);
            if (!empty($log_new)) {
                $children = [];
                $log_new = $log_new->toArray();
                if (!empty($log_new)) {
                    $log_new = $log_new[0];
                } else {
                    $log_new = [];
                }
                if (isset($log_new['log_operator']) && $log_new['log_operator']) {
                    $children[] = [
                        'title' => '当前处理人员',
                        'content' => $log_new['log_operator'],
                        'content_color' => '',
                    ];
                }
                if (isset($log_new['log_phone']) && $log_new['log_phone']) {
                    $children[] = [
                        'title' => '手机号码',
                        'content' => $log_new['log_phone'],
                        'content_color' => '',
                    ];
                }

                $log = [
                    'title' => '处理记录',
                    'type' => 2,
                    'content' => $order_detail['order_status_txt'],
                    'content_color' => $order_detail['order_status_color'],
                    'children' => $children,
                    'imgs' => [],
                ];
            }
        }

        $title_status = '工单状态';
        if (isset($order_detail['order_status']) && $order_detail['order_status']) {
            if (1==$order_detail['order_status']) {
                // 工单待处理
                if ($is_member && $is_post) {
                    $order_detail['change_status'] = [
                        ['log'=>'member_submit','name'=>'提交事件中心'],
                        ['log'=>'member_reply_user','name'=>'回复'],
                        ['log'=>'member_recall','name'=>'撤回'],
                        ['log'=>'member_closed','name'=>'关闭'],
                    ];
                    if ($is_word) {
                        $order_detail['change_status_word'] = ['member_submit','member_reply_user','member_recall','member_closed'];
                    }
                } elseif ($is_member) {
                    $order_detail['change_status'] = [
                        ['log'=>'member_submit','name'=>'提交事件中心'],
                        ['log'=>'member_reply_user','name'=>'回复'],
                    ];
                    if ($is_word) {
                        $order_detail['change_status_word'] = ['member_submit','member_reply_user'];
                    }
                }  elseif ($is_post) {
                    $order_detail['change_status'] = [
                        ['log'=>'member_recall','name'=>'撤回'],
                        ['log'=>'member_closed','name'=>'关闭'],
                    ];
                    if ($is_word) {
                        $order_detail['change_status_word'] = ['member_recall','member_closed'];
                    }
                } else {
                    $order_detail['change_status'] = [];
                    if ($is_word) {
                        $order_detail['change_status_word'] = [];
                    }
                }
                $order_detail['order_status_txt'] = $this->order_status_txt[$order_detail['order_status']];
                $order_detail['order_status_color'] = $this->order_status_color[$order_detail['order_status']];
                $title_status = '工单状态';
            } elseif (3==$order_detail['order_status']) {
                // 工单处理中
                if (1==$order_detail['event_status'] && 2==$order_detail['now_role']) {
                    // 未指派  下一个为事件处理中心处理
                    if ($is_post) {
                        $order_detail['change_status'] = [
                            ['log'=>'member_recall','name'=>'撤回'],
                            ['log'=>'member_closed','name'=>'关闭'],
                        ];
                        if ($is_word) {
                            $order_detail['change_status_word'] = ['member_recall','member_closed'];
                        }
                        $order_detail['order_status_txt'] = $this->order_status_txt[$order_detail['order_status']];
                        $order_detail['order_status_color'] = $this->order_status_color[$order_detail['order_status']];
                        $title_status = '工单状态';
                    } else {
                        $order_detail['change_status'] = [];
                        if ($is_word) {
                            $order_detail['change_status_word'] = [];
                        }
                        $order_detail['order_status_txt'] = $this->event_status_txt[$order_detail['event_status']];
                        $order_detail['order_status_color'] = $this->event_status_color[$order_detail['event_status']];
                        $title_status = '事件状态';
                    }
                } elseif (2==$order_detail['event_status'] && 3==$order_detail['now_role']) {
                    // 已指派  下一个为事件处理人员处理
                    if ($is_post && $is_now_worker) {
                        $order_detail['change_status'] = [
                            ['log'=>'work_follow_up','name'=>'跟进'],
                            ['log'=>'work_completed','name'=>'结单'],
                            ['log'=>'work_reject_center','name'=>'拒绝'],
                            ['log'=>'member_recall','name'=>'撤回'],
                            ['log'=>'member_closed','name'=>'关闭'],
                        ];
                        if ($is_word) {
                            $order_detail['change_status_word'] = ['work_follow_up','work_completed','work_reject_center','member_recall','member_closed'];
                        }
                        $order_detail['order_status_txt'] = $this->event_status_txt[$order_detail['event_status']];
                        $order_detail['order_status_color'] = $this->event_status_color[$order_detail['event_status']];
                        $title_status = '事件状态';
                    } elseif ($is_now_worker) {
                        $order_detail['change_status'] = [
                            ['log'=>'work_follow_up','name'=>'跟进'],
                            ['log'=>'work_completed','name'=>'结单'],
                            ['log'=>'work_reject_center','name'=>'拒绝'],
                        ];
                        if ($is_word) {
                            $order_detail['change_status_word'] = ['work_follow_up','work_completed','work_reject_center'];
                        }
                        $order_detail['order_status_txt'] = $this->event_status_txt[$order_detail['event_status']];
                        $order_detail['order_status_color'] = $this->event_status_color[$order_detail['event_status']];
                        $title_status = '事件状态';
                    } elseif ($is_post) {
                        $order_detail['change_status'] = [
                            ['log'=>'member_recall','name'=>'撤回'],
                            ['log'=>'member_closed','name'=>'关闭'],
                        ];
                        if ($is_word) {
                            $order_detail['change_status_word'] = ['member_recall','member_closed'];
                        }
                        $order_detail['order_status_txt'] = $this->order_status_txt[$order_detail['order_status']];
                        $order_detail['order_status_color'] = $this->order_status_color[$order_detail['order_status']];
                        $title_status = '工单状态';
                    } else {
                        $order_detail['change_status'] = [];
                        if ($is_word) {
                            $order_detail['change_status_word'] = [];
                        }
                        $order_detail['order_status_txt'] = $this->event_status_txt[$order_detail['event_status']];
                        $order_detail['order_status_color'] = $this->event_status_color[$order_detail['event_status']];
                        $title_status = '事件状态';
                    }
                } elseif (3==$order_detail['event_status'] && 3==$order_detail['now_role']) {
                    // 事件处理中  下一个还是事件处理人员处理
                    // 已指派  下一个为事件处理人员处理
                    if ($is_post && $is_now_worker) {
                        $order_detail['change_status'] = [
                            ['log'=>'work_follow_up','name'=>'跟进'],
                            ['log'=>'work_completed','name'=>'结单'],
                            ['log'=>'work_reject_center','name'=>'拒绝'],
                            ['log'=>'member_recall','name'=>'撤回'],
                            ['log'=>'member_closed','name'=>'关闭'],
                        ];
                        if ($is_word) {
                            $order_detail['change_status_word'] = ['work_follow_up','work_completed','work_reject_center','member_recall','member_closed'];
                        }
                        $order_detail['order_status_txt'] = $this->event_status_txt[$order_detail['event_status']];
                        $order_detail['order_status_color'] = $this->event_status_color[$order_detail['event_status']];
                        $title_status = '事件状态';
                    } elseif ($is_now_worker) {
                        $order_detail['change_status'] = [
                            ['log'=>'work_follow_up','name'=>'跟进'],
                            ['log'=>'work_completed','name'=>'结单'],
                            ['log'=>'work_reject_center','name'=>'拒绝'],
                        ];
                        if ($is_word) {
                            $order_detail['change_status_word'] = ['work_follow_up','work_completed','work_reject_center'];
                        }
                        $order_detail['order_status_txt'] = $this->event_status_txt[$order_detail['event_status']];
                        $order_detail['order_status_color'] = $this->event_status_color[$order_detail['event_status']];
                        $title_status = '事件状态';
                    } elseif ($is_post) {
                        $order_detail['change_status'] = [
                            ['log'=>'member_recall','name'=>'撤回'],
                            ['log'=>'member_closed','name'=>'关闭'],
                        ];
                        if ($is_word) {
                            $order_detail['change_status_word'] = ['member_recall','member_closed'];
                        }
                        $order_detail['order_status_txt'] = $this->order_status_txt[$order_detail['order_status']];
                        $order_detail['order_status_color'] = $this->order_status_color[$order_detail['order_status']];
                        $title_status = '工单状态';
                    } else {
                        $order_detail['change_status'] = [];
                        if ($is_word) {
                            $order_detail['change_status_word'] = [];
                        }
                        $order_detail['order_status_txt'] = $this->event_status_txt[$order_detail['event_status']];
                        $order_detail['order_status_color'] = $this->event_status_color[$order_detail['event_status']];
                        $title_status = '事件状态';
                    }
                } elseif (4==$order_detail['event_status']) {
                    // 事件已结单  为 网格员处理
                    if ($is_member && $is_now_worker && $is_post) {
                        $change_status = [
                            ['log'=>'member_reply_user','name'=>'回复'],
                            ['log'=>'member_reject_center','name'=>'驳回给处理中心'],
                        ];
                        if (!empty($log_new) && 'work_completed'==$log_new['log_name']) {
                            $change_status[] = ['log'=>'member_reject_work','name'=>'驳回给处理人员'];
                        }
                        $change_status[] = ['log'=>'work_completed','name'=>'再次结单'];
                        $change_status[] = ['log'=>'member_recall','name'=>'撤回'];
                        $change_status[] = ['log'=>'member_closed','name'=>'关闭'];
                        $order_detail['change_status'] = $change_status;

                        if ($is_word) {
                            $order_detail['change_status_word'] = ['member_reply_user','member_reject_center','member_reject_work','work_completed','member_recall','member_closed'];
                        }
                        $order_detail['order_status_txt'] = $this->event_status_txt[$order_detail['event_status']];
                        $order_detail['order_status_color'] = $this->event_status_color[$order_detail['event_status']];
                        $title_status = '事件状态';
                    } elseif ($is_member && $is_now_worker) {
                        $change_status = [
                            ['log'=>'member_reply_user','name'=>'回复工单'],
                            ['log'=>'member_reject_center','name'=>'驳回给处理中心'],
                        ];
                        if (!empty($log_new) && 'work_completed'==$log_new['log_name']) {
                            $change_status[] = ['log'=>'member_reject_work','name'=>'驳回给处理人员'];
                        }
                        $change_status[] = ['log'=>'work_completed','name'=>'结单'];
                        $order_detail['change_status'] = $change_status;
                        if ($is_word) {
                            $order_detail['change_status_word'] = ['member_reply_user','member_reject_center','member_reject_work','work_completed'];
                        }
                        $order_detail['order_status_txt'] = $this->event_status_txt[$order_detail['event_status']];
                        $order_detail['order_status_color'] = $this->event_status_color[$order_detail['event_status']];
                        $title_status = '事件状态';
                    } elseif ($is_member && $is_post) {
                        $change_status = [
                            ['log'=>'member_reply_user','name'=>'回复工单'],
                            ['log'=>'member_reject_center','name'=>'驳回给处理中心'],
                        ];
                        if (!empty($log_new) && 'work_completed'==$log_new['log_name']) {
                            $change_status[] = ['log'=>'member_reject_work','name'=>'驳回给处理人员'];
                        }
                        $change_status[] = ['log'=>'member_recall','name'=>'撤回'];
                        $change_status[] = ['log'=>'member_closed','name'=>'关闭'];
                        $order_detail['change_status'] = $change_status;
                        if ($is_word) {
                            $order_detail['change_status_word'] = ['member_reply_user','member_reject_center','member_reject_work','member_recall','member_closed'];
                        }
                        $order_detail['order_status_txt'] = $this->event_status_txt[$order_detail['event_status']];
                        $order_detail['order_status_color'] = $this->event_status_color[$order_detail['event_status']];
                        $title_status = '事件状态';
                    } elseif ($is_now_worker && $is_post) {
                        $order_detail['change_status'] = [
                            ['log'=>'work_completed','name'=>'再次结单'],
                            ['log'=>'member_recall','name'=>'撤回'],
                            ['log'=>'member_closed','name'=>'关闭'],
                        ];
                        if ($is_word) {
                            $order_detail['change_status_word'] = ['work_completed','member_recall','member_closed'];
                        }
                        $order_detail['order_status_txt'] = $this->event_status_txt[$order_detail['event_status']];
                        $order_detail['order_status_color'] = $this->event_status_color[$order_detail['event_status']];
                        $title_status = '事件状态';
                    } elseif ($is_now_worker) {
                        $order_detail['change_status'] = [
                            ['log'=>'work_completed','name'=>'再次结单'],
                        ];
                        if ($is_word) {
                            $order_detail['change_status_word'] = ['work_completed'];
                        }
                        $order_detail['order_status_txt'] = $this->event_status_txt[$order_detail['event_status']];
                        $order_detail['order_status_color'] = $this->event_status_color[$order_detail['event_status']];
                        $title_status = '事件状态';
                    } elseif ($is_member) {
                        $change_status = [
                            ['log'=>'member_reply_user','name'=>'回复'],
                            ['log'=>'member_reject_center','name'=>'驳回给处理中心'],
                        ];
                        if (!empty($log_new) && 'work_completed'==$log_new['log_name']) {
                            $change_status[] = ['log'=>'member_reject_work','name'=>'驳回给处理人员'];
                        }
                        $order_detail['change_status'] = $change_status;
                        if ($is_word) {
                            $order_detail['change_status_word'] = ['member_reply_user','member_reject_center','member_reject_work'];
                        }
                        $order_detail['order_status_txt'] = $this->event_status_txt[$order_detail['event_status']];
                        $order_detail['order_status_color'] = $this->event_status_color[$order_detail['event_status']];
                        $title_status = '事件状态';
                    }  elseif ($is_post) {
                        $order_detail['change_status'] = [
                            ['log'=>'member_recall','name'=>'撤回'],
                            ['log'=>'member_closed','name'=>'关闭'],
                        ];
                        if ($is_word) {
                            $order_detail['change_status_word'] = ['member_recall','member_closed'];
                        }
                        $order_detail['order_status_txt'] = $this->order_status_txt[$order_detail['order_status']];
                        $order_detail['order_status_color'] = $this->order_status_color[$order_detail['order_status']];
                        $title_status = '工单状态';
                    } else {
                        $order_detail['change_status'] = [];
                        if ($is_word) {
                            $order_detail['change_status_word'] = [];
                        }
                        $order_detail['order_status_txt'] = $this->event_status_txt[$order_detail['event_status']];
                        $order_detail['order_status_color'] = $this->event_status_color[$order_detail['event_status']];
                        $title_status = '事件状态';
                    }
                } elseif (5==$order_detail['event_status'] || 6==$order_detail['event_status']) {
                    // 工单已经撤销 工单已经关闭  为 网格员处理 需要知道上一个处理人员
                    if ($is_post) {
                        $order_detail['change_status'] = [
                            ['log'=>'member_reopen','name'=>'重新打开'],
                        ];
                        if ($is_word) {
                            $order_detail['change_status_word'] = ['member_reopen'];
                        }
                        $order_detail['order_status_txt'] = $this->order_status_txt[$order_detail['order_status']];
                        $order_detail['order_status_color'] = $this->order_status_color[$order_detail['order_status']];
                        $title_status = '工单状态';
                    } else {
                        $order_detail['change_status'] = [];
                        if ($is_word) {
                            $order_detail['change_status_word'] = [];
                        }
                        $order_detail['order_status_txt'] = $this->event_status_txt[$order_detail['event_status']];
                        $order_detail['order_status_color'] = $this->event_status_color[$order_detail['event_status']];
                        $title_status = '事件状态';
                    }
                }
            } elseif (4==$order_detail['order_status'] || 5==$order_detail['order_status']) {
                // 工单已经撤销 工单已经关闭  为 网格员处理 需要知道上一个处理人员
                if ($is_post) {
                    $order_detail['change_status'] = [
                        ['log'=>'member_reopen','name'=>'重新打开'],
                    ];
                    if ($is_word) {
                        $order_detail['change_status_word'] = ['member_reopen'];
                    }
                    $order_detail['order_status_txt'] = $this->order_status_txt[$order_detail['order_status']];
                    $order_detail['order_status_color'] = $this->order_status_color[$order_detail['order_status']];
                    $title_status = '工单状态';
                } else {
                    $order_detail['change_status'] = [];
                    if ($is_word) {
                        $order_detail['change_status_word'] = [];
                    }
                    $order_detail['order_status_txt'] = $this->event_status_txt[$order_detail['event_status']];
                    $order_detail['order_status_color'] = $this->event_status_color[$order_detail['event_status']];
                    $title_status = '事件状态';
                }
            } elseif (2==$order_detail['order_status']) {
                if ($is_post) {
                    $order_detail['change_status'] = [
                        ['log'=>'member_closed','name'=>'关闭'],
                        ['log'=>'member_recall','name'=>'撤回'],
                        ['log'=>'member_reopen','name'=>'重新打开'],
                    ];
                    if ($is_word) {
                        $order_detail['change_status_word'] = ['member_reopen'];
                    }
                    $order_detail['order_status_txt'] = $this->order_status_txt[$order_detail['order_status']];
                    $order_detail['order_status_color'] = $this->order_status_color[$order_detail['order_status']];
                    $title_status = '工单状态';
                } else {
                    $order_detail['change_status'] = [];
                    if ($is_word) {
                        $order_detail['change_status_word'] = [];
                    }
                    $order_detail['order_status_txt'] = $this->event_status_txt[$order_detail['event_status']];
                    $order_detail['order_status_color'] = $this->event_status_color[$order_detail['event_status']];
                    $title_status = '事件状态';
                }
            }
        }
        if (!$order_detail['change_status']) {
            $order_detail['change_status'] = [];
            if ($is_word) {
                $order_detail['change_status_word'] = [];
            }
        }


        if (isset($order_detail['order_time']) && $order_detail['order_time']) {
            $order_detail['order_time_txt'] = date('Y-m-d H:i:s',$order_detail['order_time']);
        }

        if ($is_arr && $order_detail) {
            $order_arr = [];
            if ($is_member || $is_now_worker || $is_old_worker) {
                if (isset($order_detail['name']) && $order_detail['name']) {
                    $order_arr[] = [
                        'title' => '上报人员',
                        'type' => 1,
                        'content' => $order_detail['name'],
                        'content_color' => '',
                        'children' => [],
                        'imgs' => [],
                    ];
                }
            }
            if (isset($order_detail['polygon_name']) && $order_detail['polygon_name']) {
                // type 为1 展示 content 为字符串  type为2  展示 children 为数组 type为3时候 展示imgs 为数组
                $order_arr[] = [
                    'title' => '上报网格',
                    'type' => 1,
                    'content' => $order_detail['polygon_name'],
                    'content_color' => '',
                    'children' => [],
                    'imgs' => [],
                ];
            }
            if (isset($order_detail['order_address']) && $order_detail['order_address']) {
                $order_arr[] = [
                    'title' => '所在位置',
                    'type' => 1,
                    'content' => $order_detail['order_address'],
                    'content_color' => '',
                    'children' => [],
                    'imgs' => [],
                ];
            }
            if (isset($order_detail['order_time_txt']) && $order_detail['order_time_txt']) {
                $order_arr[] = [
                    'title' => '上报时间',
                    'type' => 1,
                    'content' => $order_detail['order_time_txt'],
                    'content_color' => '',
                    'children' => [],
                    'imgs' => [],
                ];
            }
            $title = '工单分类';
            if ($is_member || $is_now_worker || $is_old_worker) {
                $title = '事件分类';
                if (isset($order_detail['phone']) && $order_detail['phone']) {
                    $order_arr[] = [
                        'title' => '手机号码',
                        'type' => 1,
                        'content' => $order_detail['phone'],
                        'content_color' => '',
                        'children' => [],
                        'imgs' => [],
                    ];
                }
            }
            if (isset($order_detail['cat_txt']) && $order_detail['cat_txt']) {
                $order_arr[] = [
                    'title' => $title,
                    'type' => 1,
                    'content' => $order_detail['cat_txt'],
                    'content_color' => '',
                    'children' => [],
                    'imgs' => [],
                ];
            }
            if (isset($order_detail['order_content']) && $order_detail['order_content']) {
                $order_arr[] = [
                    'title' => '上报内容',
                    'type' => 1,
                    'content' => $order_detail['order_content'],
                    'content_color' => '',
                    'children' => [],
                    'imgs' => [],
                ];
            }
            if (isset($order_detail['order_imgs_arr']) && $order_detail['order_imgs_arr']) {
                $order_arr[] = [
                    'title' => '上报图例',
                    'type' => 3,
                    'content' => '',
                    'content_color' => '',
                    'children' => [],
                    'imgs' => $order_detail['order_imgs_arr'],
                ];
            }
            if (isset($order_detail['order_status_txt']) && $order_detail['order_status_txt']) {
                $order_arr[] = [
                    'title' => $title_status,
                    'type' => 1,
                    'content' => $order_detail['order_status_txt'],
                    'content_color' => $order_detail['order_status_color'],
                    'children' => [],
                    'imgs' => [],
                ];
            }
            if (empty($log)) {
                $log = (object)[];
            }
            $order_detail['log'] = $log;

            $order_detail['order_arr'] = $order_arr;
        }


        return $order_detail;
    }

    /**
     * 时间数量统计
     * @author lijie
     * @date_time 2021/03/03
     * @param $where
     * @param string $group
     * @return mixed
     */
    public function getWorksOrderLogCount($where,$group='')
    {
        $db_area_street_works_order_log = new AreaStreetWorkersOrderLog();
        $count = $db_area_street_works_order_log->getCount($where,$group);
        return $count;
    }

    public function addEventWorkers($data)
    {
        $db_area_street_event_works = new AreaStreetEventWorks();
        $data = $db_area_street_event_works->addOne($data);
        return $data;
    }


    //todo 返回提示文字
    public function getMsgTips($type){
        $t1=$t2=$t3='';
        switch ($type) {
            case 10: //todo [工作人员]当有工单指派给自己时 用户提交或网格员提交
                $t1='您有新的工单待处理。';
                break;
            case 'assign_to_worker':
                $t1='您有新的已指派工单待处理';
                break;
            case 'user_recall':
                $t1='您处理的工单已被用户撤回';
                break;
            case 'user_closed':
                $t1='您处理的工单已被用户关闭';
                break;
            case 'user_reopen':
                $t1='您处理的工单已被用户重新打开';
                break;
            case 'work_completed':
                $t2='您提的工单网格员已办结';
                break;
            case 'member_reply_user':
                $t2='您提的工单网格员有回复';
                break;
            case 'member_recall':
                $t2='您提的工单被网格员撤回了';
                break;
            case 'member_closed':
                $t2='您提的工单被网格员关闭了';
                break;
            case 'member_reopen':
                $t2='您提的工单被网格员重新打开了';
                break;
                
        }
        return ['worker'=>$t1,'user'=>$t2,'worker2'=>$t3];
    }
    
    //给提工单的用户发信息
    public function   streetWorkersOrderSendMsgToUser($type,$order_id,$workerInfo=array()){
        $AreaStreetWorkersOrder = new AreaStreetWorkersOrder();
        $whereArr=array();
        $whereArr[]=array('order_id','=',$order_id);
        $whereArr[]=array('del_time','<',1);
        $order_info=$AreaStreetWorkersOrder->getOneData($whereArr);
        if (!$order_info || $order_info->isEmpty()){
            fdump_api(['order_info不存在=='.__LINE__,$type,$order_id,$workerInfo],'streetWorkersOrder/sendMsg_error',1);
            return true;
        }
        $areaStreetEventCategory=new AreaStreetEventCategory();
        $order_info=$order_info->toArray();
        $order_info['cat_fname']='';
        $order_info['cat_name']='';
        if ($order_info['cat_fid']>0){
            $whereTmp=array('cat_id'=>$order_info['cat_fid']);
            $eventCategoryObj=$areaStreetEventCategory->getOne($whereTmp,'cat_id,cat_name,cat_fid');
            if($eventCategoryObj && !$eventCategoryObj->isEmpty()){
                $order_info['cat_fname']=$eventCategoryObj['cat_name'];
            }
        }
        if ($order_info['cat_id']>0){
            $whereTmp=array('cat_id'=>$order_info['cat_id']);
            $eventCategoryObj=$areaStreetEventCategory->getOne($whereTmp,'cat_id,cat_name,cat_fid');
            if($eventCategoryObj && !$eventCategoryObj->isEmpty()){
                $order_info['cat_name']=$eventCategoryObj['cat_name'];
            }
        }
        $TemplateNewsService=new TemplateNewsService();
        if($order_info['order_type']==0 && $order_info['uid']>0){
            //业主提的
            $userDb=new User();
            $fieldStr='uid,openid,wxapp_openid,phone,nickname';
            $userInfoObj=$userDb->getOne(array('uid'=>$order_info['uid']),$fieldStr);
            if($userInfoObj && !$userInfoObj->isEmpty()){
                $userInfo=$userInfoObj->toArray();
                if(!empty($userInfo['openid'])){
                    $msgTips=$this->getMsgTips($type);
                    $href=cfg('site_url').'/packapp/village/pages/village/grid/eventdetails?order_id='.$order_id.'&area_street_id='.$order_info['area_id'];
                    $title=$order_info['cat_fname'].'/'.$order_info['cat_name'];
                    $arr=[
                        'href' => $href,
                        'wecha_id' => $userInfo['openid'],
                        'first' => $msgTips['user'],
                        'keyword1' => $title,
                        'keyword2' => '已发送',
                        'keyword3' => date('H时i分', $_SERVER['REQUEST_TIME']),
                        'remark' => '请点击查看详细信息！'
                    ];
                    $TemplateNewsService->sendTempMsg('OPENTM400166399', $arr);
                }
            }
        }else if($order_info['order_type']==1 && $order_info['uid']>0){
            //网格员上报提的
            $db_area_street_grid_member = new HouseVillageGridMember();
            $grid_member_info = $db_area_street_grid_member->getOne(['id'=>$order_info['uid']]);
            if($grid_member_info && !$grid_member_info->isEmpty()){
                $grid_member_info=$grid_member_info->toArray();
                if($grid_member_info['workers_id']>0 && $grid_member_info['workers_id']!=$workerInfo['worker_id']){
                    //别人处理了 自己的提的 发模板消息
                    $areaStreetWorkers= new AreaStreetWorkers();
                    $wkWhere=array('worker_id'=>$grid_member_info['workers_id'],'work_status'=>1);
                    $streetWorkersObj=$areaStreetWorkers->getOne($wkWhere);
                    if($streetWorkersObj && !$streetWorkersObj->isEmpty()){
                        $worker_info=$streetWorkersObj->toArray();
                        $msgTips=$this->getMsgTips($type);
                        $arr=[];
                        $title=$order_info['cat_fname'].'/'.$order_info['cat_name'];
                        if (mb_strlen($title)>15) {
                            $title=msubstr($title,0,15);
                        }
                        $href=cfg('site_url').'/packapp/community/pages/Community/grid/eventdetails?order_id='.$order_id.'&gotoIndex=tem_msg';
                        $ticket = Token::createToken($worker_info['worker_id'],1);
                        $href.='&ticket='.$ticket;
                        if(!empty($msgTips['user']) && !empty($worker_info['openid'])){ //通知工作人员
                            $arr=[
                                'href' => $href,
                                'wecha_id' => $worker_info['openid'],
                                'first' => $msgTips['user'],
                                'keyword1' => $title,
                                'keyword2' => '已发送',
                                'keyword3' => date('H时i分', $_SERVER['REQUEST_TIME']),
                                'remark' => '请点击查看详细信息！'
                            ];
                        }
                        $TemplateNewsService->sendTempMsg('OPENTM400166399', $arr);
                    }
                }
            }
        }
        return true;
    }
    //todo 提交工单 发送模板+短信通知
    public function streetWorkersOrderSendMsg($type,$order_id,$workerInfo=array()){
        $HouseVillageGridMember=new HouseVillageGridMember();
        $AreaStreetWorkersOrder = new AreaStreetWorkersOrder();
        $TemplateNewsService=new TemplateNewsService();
        $areaStreetEventCategory=new AreaStreetEventCategory();
        /*
        $order_info=$AreaStreetWorkersOrder->getFind([
            [ 'o.order_id','=',$order_id],
            [ 'o.del_time','=',0]
        ],'o.order_id,o.grid_member_id,o.order_worker_id,o.order_go_by_center,o.order_status,o.event_status,c1.cat_name,c2.cat_name as cat_fname');
        */
        $whereArr=array();
        $whereArr[]=array('order_id','=',$order_id);
        $whereArr[]=array('del_time','<',1);
        $order_info=$AreaStreetWorkersOrder->getOneData($whereArr);
        if (!$order_info || $order_info->isEmpty()){
            fdump_api(['order_info不存在=='.__LINE__,$type,$order_id,$workerInfo],'streetWorkersOrder/sendMsg_error',1);
            return true;
        }
        $order_info=$order_info->toArray();
        $order_info['cat_fname']='';
        $order_info['cat_name']='';
        if ($order_info['cat_fid']>0){
            $whereTmp=array('cat_id'=>$order_info['cat_fid']);
            $eventCategoryObj=$areaStreetEventCategory->getOne($whereTmp,'cat_id,cat_name,cat_fid');
            if($eventCategoryObj && !$eventCategoryObj->isEmpty()){
                $order_info['cat_fname']=$eventCategoryObj['cat_name'];
            }
        }
        if ($order_info['cat_id']>0){
            $whereTmp=array('cat_id'=>$order_info['cat_id']);
            $eventCategoryObj=$areaStreetEventCategory->getOne($whereTmp,'cat_id,cat_name,cat_fid');
            if($eventCategoryObj && !$eventCategoryObj->isEmpty()){
                $order_info['cat_name']=$eventCategoryObj['cat_name'];
            }
        }
        if($workerInfo && isset($workerInfo['worker_id']) && $workerInfo['worker_id']>0){
            $areaStreetWorkers= new AreaStreetWorkers();
            $wkWhere=array('worker_id'=>$workerInfo['worker_id'],'work_status'=>1);
            $streetWorkersObj=$areaStreetWorkers->getOne($wkWhere);
            if($streetWorkersObj && !$streetWorkersObj->isEmpty()){
                $worker_info=$streetWorkersObj->toArray();
            }else{
                fdump_api(['worker_info不存在=='.__LINE__,$type,$order_id,$workerInfo,$order_info],'streetWorkersOrder/sendMsg_error',1);
                return true;
            }
        }else{
            $memebr_id=$order_info['grid_member_id'];
            if($workerInfo && isset($workerInfo['grid_member_id']) && $workerInfo['grid_member_id'] > 0){
                $memebr_id=$workerInfo['grid_member_id'];
            }
            $worker_info=$HouseVillageGridMember->getGridMemberWorkers([
                [ 'g.id','=',$memebr_id],
                [ 'w.work_status','=',1]
            ],'w.worker_id,w.nickname,w.avatar,w.openid,w.work_name,w.work_phone');
            if (!$worker_info || $worker_info->isEmpty()){
                fdump_api(['worker_info不存在=='.__LINE__,$type,$order_id,$workerInfo,$order_info],'streetWorkersOrder/sendMsg_error',1);
                return true;
            }
            $worker_info=$worker_info->toArray();
        }
        if(in_array($type,array(10,'assign_to_worker'))){
           $this->streetWorkersOrderSendSms([
               'uid'=>0,
               'village_id'=>0,
               'name'=>$worker_info['work_name'],
               'phone'=>$worker_info['work_phone'],
            ]);
        }
        if(empty($worker_info['openid'])){
            fdump_api(['openid为空=='.__LINE__,$type,$order_id,$workerInfo,$order_info,$worker_info],'streetWorkersOrder/sendMsg_error',1);
            return true;
        }
        $msgTips=$this->getMsgTips($type);
        $arr=[];
        $title=$order_info['cat_fname'].'/'.$order_info['cat_name'];
        if (mb_strlen($title)>15) {
            $title=msubstr($title,0,15);
        }
        $href=cfg('site_url').'/packapp/community/pages/Community/grid/eventdetails?order_id='.$order_id.'&gotoIndex=tem_msg';
        $ticket = Token::createToken($worker_info['worker_id'],1);
        $href.='&ticket='.$ticket;
        if(!empty($msgTips['worker'])){ //通知工作人员
            $arr[]=[
                'href' => $href,
                'wecha_id' => $worker_info['openid'],
                'first' => $msgTips['worker'],
                'keyword1' => $title,
                'keyword2' => '已发送',
                'keyword3' => date('H时i分', $_SERVER['REQUEST_TIME']),
                'remark' => '请点击查看详细信息！'
            ];
        }
        fdump_api(['arr=='.__LINE__,$type,$order_id,$workerInfo,$order_info,$worker_info,$msgTips,$arr],'streetWorkersOrder/sendMsg',1);
        if($arr){
            foreach ($arr as $v){
                $TemplateNewsService->sendTempMsg('OPENTM400166399', $v);
            }
        }
        return true;
    }


    //todo 发送短信
    public function streetWorkersOrderSendSms($param){
        $data = array('type' => 'fee_notice');
        $data['uid'] = $param['uid'];
        $data['village_id'] = $param['village_id'];
        $data['mobile'] = $param['phone'];
        $data['sendto'] = 'user';
        $data['mer_id'] = 0;
        $data['store_id'] = 0;
        $data['content'] = L_('尊敬的网格员x1，您有新的工单待处理，请在移动管理端了解详情', array(
            'x1' => $param['name'],
        ));
        $result = (new SmsService())->sendSms($data);
        fdump_api(['发送短信==line:'.__LINE__,$param,$data,$result],'streetWorkersOrder/send_sms',1);
        return true;
    }
}