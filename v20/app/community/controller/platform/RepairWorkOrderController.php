<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      平台小区工单接口
 */

namespace app\community\controller\platform;

use app\common\model\service\admin_user\AdminUserService;
use app\common\model\service\config\ConfigCustomizationService;
use app\community\controller\platform\AuthBaseController as BaseController;
use app\community\model\service\AdminLoginService;
use app\community\model\service\HouseMeterService;
use app\community\model\service\HouseNewRepairService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseWorkerService;
use app\community\model\service\RepairCateService;
use app\community\model\service\workorder\HouseCommunityAboutService;
use app\community\model\service\workorder\HouseCommunityHandleNoticeService;
use app\consts\WorkOrderConst;

class RepairWorkOrderController extends BaseController
{
    /**
     * 工单列表-督查室
     */
    public function getOrderList()
    {
        $village_id = $this->request->post('village_id',0);
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
        $limit = 8;
        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        $where = array();
        // 只查询督查室数据
        $where[] = ['o.mark_report','=',1];
        if ($jz_open && $village_id) {
            $where[] = ['o.village_id|o.now_handle_village_id', '=', $village_id];
        } elseif($village_id) {
            $where[] = ['o.village_id', '=', $village_id];
        }
        if($cat_id) {
            $where[] = ['o.cat_id','=',$cat_id];
        }
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
                default:
                    $where[] = ['o.event_status','=',$event_status];
                    break;
            }
        }
        $field = 'o.*';
        if ($jz_open) {
            $mark_type = $this->request->post('mark_type', '', 'trim');
            if ('请选择上报类型' == $mark_type) {
                $mark_type = '';
            }
            $mark_type && $where[] = ['o.mark_type', '=', $mark_type];
            $order = 'o.mark_time DESC, o.now_handle_village_id DESC, o.order_id DESC';
        } else {
            $order = 'o.mark_time DESC, o.order_id DESC';
        }
        $service_house_new_repair = new HouseNewRepairService();
        $result = [];
        try {
            $params = ['source_type' => 'platform', 'village_id' => $village_id, 'jz_open' => $jz_open];
            $data = $service_house_new_repair->getOrderList($where, $field, $page, $limit, $order, 0, [], $params);
            $count = $service_house_new_repair->getOrderCount($where);
            $result['list'] = $data;
            $result['total'] = $count;
            $result['limit'] = $limit;
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 今日上报/回复/处理工单
     */
    public function getTongji()
    {
        $village_id = $this->request->post('village_id', 0);
        $service_house_new_repair = new HouseNewRepairService();
        $time = strtotime(date('Y-m-d'));
        
        $whereCount = [];
        $village_id && $whereCount[] = ['village_id', '=', $village_id];
        $whereCount[] = ['log_name', 'in', ['user_submit', 'property_admin_submit', 'property_work_submit', 'house_admin_submit', 'house_work_submit', 'backstage_work_submit']];
        $whereCount[] = ['add_time', '>=', $time];
        $submitCountColumnOrders = $service_house_new_repair->getColumn($whereCount, 'order_id');
        if (empty($submitCountColumnOrders)) {
            $submit_count = 0;
        } else {
            $whereOrder = [];
            $whereOrder[] = ['mark_report', '=', 1];
            $whereOrder[] = ['order_id', 'in', $submitCountColumnOrders];
            $submit_count = $service_house_new_repair->getOrderCount($whereOrder);
        }

        $whereCount = [];
        $village_id && $whereCount[] = ['village_id', '=', $village_id];
        $whereCount[] = ['log_name', 'in', ['property_admin_reply', 'property_work_reply', 'house_admin_reply', 'center_reply_submit', 'work_reply_user']];
        $whereCount[] = ['add_time', '>=', $time];
        $replyCountColumnOrders = $service_house_new_repair->getColumn($whereCount, 'order_id');
        if (empty($replyCountColumnOrders)) {
            $reply_count = 0;
        } else {
            $whereOrder = [];
            $whereOrder[] = ['mark_report', '=', 1];
            $whereOrder[] = ['order_id', 'in', $replyCountColumnOrders];
            $reply_count = $service_house_new_repair->getOrderCount($whereOrder);
        }

        $whereCount = [];
        $village_id && $whereCount[] = ['village_id', '=', $village_id];
        $whereCount[] = ['log_name', 'in', ['submit_center', 'work_reply_user', 'work_follow_up', 'work_completed', 'center_assign_work']];
        $whereCount[] = ['add_time', '>=', $time];
        $handleCountColumnOrders = $service_house_new_repair->getColumn($whereCount, 'order_id');
        if (empty($handleCountColumnOrders)) {
            $handle_count = 0;
        } else {
            $whereOrder = [];
            $whereOrder[] = ['mark_report', '=', 1];
            $whereOrder[] = ['order_id', 'in', $handleCountColumnOrders];
            $handle_count = $service_house_new_repair->getOrderCount($whereOrder);
        }
        
        $timely_count = 0;  //超时单子
        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();

        $whereCount = [];
        $whereCount[] = ['mark_report', '=', 1];
        $whereCount[] = ['is_status', '=', 1];
        if ($jz_open && $village_id) {
            $whereCount[] = ['village_id|now_handle_village_id', '=', $village_id];
        } elseif ($village_id) {
            $whereCount[] = ['village_id', '=', $village_id];
        }
        $timely_count = $service_house_new_repair->getOrderCount($whereCount);
        $timely_count = $timely_count > 0 ? $timely_count : 0;
        return api_output(0, ['submit_count' => $submit_count, 'reply_count' => $reply_count, 'handle_count' => $handle_count, 'timely_count' => $timely_count]);
    }

    /**
     * 获取评分.
     */
    public function getPropertyRating()
    {
        $village_id = $this->request->post('village_id', 0);
        $type = $this->request->post('type', 0);
        $service_house_new_repair = new HouseNewRepairService();
        $wheres = [];
        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        if ($jz_open && $village_id) {
            $wheres[] = ['l.village_id|o.now_handle_village_id', '=', $village_id];
        } else {
            $wheres[] = ['l.village_id', '=', $village_id];
        }
        $wheres[] = ['o.mark_report', '=', 1];
        $wheres[] = ['l.log_name', '=', 'work_completed'];
        $worker_ids = $service_house_new_repair->getWorkers($wheres, 'o.worker_id');
        $where = [];
        if ($type) {
            if ($type == 1) {
                $start_time = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
                $end_time = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;
                $where[] = ['l.add_time', 'between', [$start_time, $end_time]];
            }
            if ($type == 2) {
                $date = date("Y-m-d");  //当前日期
                $first = 1; //$first =1 表示每周星期一为开始时间 0表示每周日为开始时间
                $w = date("w", strtotime($date));  //获取当前周的第几天 周日是 0 周一 到周六是 1 -6
                $d = $w ? $w - $first : 6;  //如果是周日 -6天
                $now_start = date("Y-m-d", strtotime("$date -" . $d . " days")); //本周开始时间
                $start_time = strtotime("$now_start - 7 days");  //上周开始时间
                $end_time = strtotime("$now_start - 1 days");  //上周结束时间
                $where[] = ['l.add_time', 'between', [$start_time, $end_time]];
            }
            if ($type == 3) {
                $standardTime = date('Y-m-1');
                $start_time = strtotime(date('Y-m-1 00:00:00', strtotime('-1 month', strtotime($standardTime))));
                $end_time = strtotime(date('Y-m-d H:i:s', strtotime('-1 sec', strtotime($standardTime))));
                $where[] = ['l.add_time', 'between', [$start_time, $end_time]];
            }
            if ($type == 4) {
                $start_time = strtotime(date('Y', strtotime("-1 year")));
                $end_time = strtotime(date('Y-12-31 23:59:59', strtotime("-1 year")));
                $where[] = ['l.add_time', 'between', [$start_time, $end_time]];
            }
        }
        if ($jz_open && $village_id) {
            $where[] = ['l.village_id|o.now_handle_village_id', '=', $village_id];
            $where[] = ['w.village_id', '=', $village_id];
        } else {
            $where[] = ['l.village_id', '=', $village_id];
        }
        $where[] = ['o.mark_report', '=', 1];
        $where[] = ['o.worker_id', 'in', $worker_ids];
        $where[] = ['l.evaluate', '>', 0];
        $field = 'avg(l.evaluate) as avg_evaluate,l.log_operator,l.log_phone,w.name';
        $data = $service_house_new_repair->getAvg($where, $field)->toArray();
        $info = [];
        if ($data) {
            $info[0][0]['title'] = '排名';
            $info[0][1]['title'] = '姓名';
            $info[0][2]['title'] = '服务评分';
            foreach ($data as $k => $v) {
                $optname = $v['name'];
                if (empty($optname) && !empty($v['log_operator'])) {
                    $optname = $v['log_operator'];
                }
                $info[$k + 1][0]['title'] = $k + 1;
                $info[$k + 1][1]['title'] = $optname;
                $info[$k + 1][2]['title'] = $v['avg_evaluate'];
            }
        }
        $list = [
            [
                'name' => '昨日之星',
                'type' => 1
            ],
            [
                'name' => '上周之星',
                'type' => 2
            ],
            [
                'name' => '上月之星',
                'type' => 3
            ],
            [
                'name' => '去年之星',
                'type' => 4
            ]
        ];
        $res['list'] = $list;
        $res['info'] = $info;
        return api_output(0, $res);
    }

    /**
     * 工单处理数据.
     */
    public function orderTongji()
    {
        $village_id = $this->request->post('village_id', 0);
        $where = [];
        if ($village_id) {
            $where[] = ['village_id','=',$village_id];
        }
        $where[] = ['status','=',1];
        $where[] = ['parent_id','=',0];
        $field = 'cate_name as subject_name,id as category_id,color,village_id';
        $service_house_new_repair = new HouseNewRepairService();
        try{
            $params = ['mark_report'=> 1];
            $data = $service_house_new_repair->orderTongji($where,$field,0,0,'id desc', $params);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }
    
    /**
     * 工单结案率.
     */
    public function getFinshOrder(){
        $village_id = $this->request->post('village_id', 0);
        $service_house_new_repair = new HouseNewRepairService();
        try{
            $params = ['mark_report'=> 1];
            $data = $service_house_new_repair->getFinshOrder($village_id, $params);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }
    
    /**
     * 获取对应小区工单分类.
     */
    public function getSubject()
    {
        $village_id = $this->request->param('village_id');
        if(!$village_id) {
            return api_output_error(1001,'必传参数缺失');
        }
        $parent_id = $this->request->post('subject_id',0);
        if (!$parent_id) {
            $parent_id = 0;
        }
        $where[] = ['village_id','=',$village_id];
        $where[] = ['status','=',1];
        $where[] = ['parent_id','=',$parent_id];
        $field = 'cate_name as subject_name,id as category_id,color';
        try{
            $data = (new RepairCateService())->getNewRepairCate($where,$field,0,0,'id DESC');
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }
    
    /**
     * 获取对应小区工单子分类.
     */
    public function getCate()
    {
        $village_id = $this->request->param('village_id');
        $category_id = $this->request->post('category_id',0);
        if(!$category_id || !$village_id) {
            return api_output_error(1001,'必传参数缺失');
        }
        $where[] = ['parent_id','=',$category_id];
        $where[] = ['village_id','=',$village_id];
        $where[] = ['status','=',1];
        $field = 'cate_name as name,id';
        try{
            $data = (new RepairCateService())->getNewRepairCate($where,$field,0,0,'id desc');
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }
    
    /**
     * 获取小区列表.
     */
    public function getVillageList()
    {
        try {
            $where = [];
            $where[] = ['status', '=', 1];
            $field = 'village_id, village_name';
            $list = (new HouseVillageService())->getList($where, $field);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, ['list' => $list], "成功");
    }


    /**
     * 获取小区下工作人员.
     */
    public function getVillageWorkList()
    {
        $village_id = $this->request->param('village_id');
        try {
            $where = [];
            $where[] = ['village_id', '=', $village_id];
            $where[] = ['status', '=', 1];
            $field = 'wid,type,phone,name,openid,nickname';
            $list = (new HouseWorkerService())->getWorker($where, $field);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, ['list' => $list], "成功");
    }

    /**
     * 获取小区下楼栋信息.
     */
    public function getSingleListByVillage()
    {
        $charge_type = $this->request->param('charge_type');
        $project_id = $this->request->param('project_id');
        $rule_id = $this->request->param('rule_id');
        $village_id = $this->request->param('village_id');
        $xtype = $this->request->param('xtype', '', 'trim');
        $is_public_rental = $this->request->param('is_public_rental', 0, 'int');
        $serviceHouseMeter = new HouseMeterService();
        if ($xtype == 'public_area') {
            $whereArr = array(array('village_id', '=', $village_id), array('status', '=', 1));
            $fieldStr = 'public_area_id,public_area_name,village_id';
            $publicAreaList = $serviceHouseMeter->getHouseVillagePublicAreaList($whereArr, $fieldStr);
            return api_output(0, $publicAreaList);
        }
        $param = [
            'charge_type' => $charge_type,
            'project_id' => $project_id,
            'rule_id' => $rule_id,
            'is_public_rental' => $is_public_rental
        ];
        $single_type = ''; //unitRental 公租楼栋 normal正常楼栋 不传是所有楼栋
        if ($xtype == 'unitRental') {
            //公租房
            $single_type = 'unitRental';
        }
        $measure_id = $serviceHouseMeter->getSingleList($village_id, $param, $single_type);
        return api_output(0, $measure_id);
    }
    
    /**
     * 获取楼栋下单元信息.
     */
    public function getFloorList()
    {
        $charge_type = $this->request->param('charge_type', '', 'trim');
        $project_id = $this->request->param('project_id', 0, 'intval');
        $rule_id = $this->request->param('rule_id', 0, 'intval');
        $single_id = $this->request->param('pid', 0, 'intval');
        $is_public_rental = $this->request->param('is_public_rental', 0, 'int');
        $serviceHouseMeter = new HouseMeterService();
        $param = [
            'charge_type' => $charge_type,
            'project_id' => $project_id,
            'rule_id' => $rule_id,
            'is_public_rental' => $is_public_rental
        ];
        $measure_id = $serviceHouseMeter->getFloorList($single_id, $param);
        return api_output(0, $measure_id);
    }
    
    /**
     * 获取单元下楼层信息.
     */
    public function getLayerList()
    {
        $charge_type = $this->request->param('charge_type');
        $project_id = $this->request->param('project_id');
        $rule_id = $this->request->param('rule_id');
        $floor_id = $this->request->param('pid', '', 'intval');
        $is_public_rental = $this->request->param('is_public_rental', 0, 'int');
        $serviceHouseMeter = new HouseMeterService();
        $param = [
            'charge_type' => $charge_type,
            'project_id' => $project_id,
            'rule_id' => $rule_id,
            'is_public_rental' => $is_public_rental
        ];
        $measure_id = $serviceHouseMeter->getLayerList($floor_id, $param);
        return api_output(0, $measure_id);
    }
    
    /**
     * 获取楼层下房间信息.
     */
    public function getVacancyList()
    {
        $charge_type = $this->request->param('charge_type');
        $project_id = $this->request->param('project_id');
        $rule_id = $this->request->param('rule_id');
        $layer_id = $this->request->param('pid', '', 'intval');
        $is_public_rental = $this->request->param('is_public_rental', 0, 'int');
        $serviceHouseMeter = new HouseMeterService();
        $param = [
            'charge_type' => $charge_type,
            'project_id' => $project_id,
            'rule_id' => $rule_id,
            'is_public_rental' => $is_public_rental
        ];
        $measure_id = $serviceHouseMeter->getVacancyList($layer_id, $param);
        return api_output(0, $measure_id);
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
        try {
            $params = ['mark_report' => 1];
            $data = $service_house_new_repair->getWorkOrderDetail(['order_id' => $order_id], true, $system_type, $params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 工单操作记录.
     */
    public function repairGetOrderLog() {
        $order_id = $this->request->post('order_id',0);
        if(!$order_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $arr = [];
            $house_new_repair_works_order_service = new HouseNewRepairService();
            $system_type = $this->request->post('system_type','platform', 'trim');
            !$system_type && $system_type = 'platform';
            $params = ['system_type' => $system_type];
            $log_list = $house_new_repair_works_order_service->getRepairLog($order_id, $params);
            $arr['log'] = $log_list;
            return api_output(0, $arr);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 获取管理员列表
     */
    public function getAdminList() {
        try {
            $where = [];
            $where[] = ['status', '=', 1];
            $list = (new AdminUserService())->getList($where);
            $adminUser = [];
            foreach ($list as $user) {
                $label = isset($user['realname']) ? trim($user['realname']) : '';
                !$label && $label = "账号:{$user['account']}";
                $adminUser[] = [
                    'id' => $user['id'],
                    'name' => isset($user['realname']) ? trim($user['realname']) : '',
                    'account' => trim($user['account']),
                    'label' => $label,
                    'value' => $user['id'],
                ];
            }
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, ['list' => $adminUser], "成功");
    }
    
    /**
     * 获取配置项
     */
    public function getSupervisionConfig() {
        try {
            $params = [];
            $config = (new HouseCommunityAboutService())->getSupervisionConfig($params);
            if (isset($config['from_user'])) {
                unset($config['from_user']);
            }
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, ['config' => $config], "成功");
    }
    
    /**
     * 设置配置项
     */
    public function setSupervisionConfig() {
        $notice_switch = $this->request->post('notice_switch',2);
        if (intval($notice_switch)==1 || strval($notice_switch) === 'true') {
            $notice_switch = 1;
        } else {
            $notice_switch = 2;
        }
        $notice_admin_id = $this->request->post('notice_admin_id',0);
        $notice_admin_from = $this->request->post('notice_admin_from','admin');
        !$notice_admin_from && $notice_admin_from = 'admin';
        
        try {
            $from_user = ['type' => 'system_admin', 'admin_id' => $this->systemUser['id'], 'account' => $this->systemUser['account']];
            if (isset($this->systemUser['realname']) && $this->systemUser['realname']) {
                $from_user['name'] = $this->systemUser['realname'];
            } elseif (isset($this->systemUser['show_account']) && $this->systemUser['show_account']) {
                $from_user['name'] = $this->systemUser['show_account'];
            }
            if (isset($this->systemUser['phone']) && $this->systemUser['phone']) {
                $from_user['phone'] = $this->systemUser['phone'];
            }
            if ($this->_uid) {
                $from_user['_userId'] = $this->_uid;
            }
            if (isset($this->systemUser['level']) && $this->systemUser['level']) {
                $from_user['level'] = $this->systemUser['level'];
            }
            if (isset($this->systemUser['last_ip']) && $this->systemUser['last_ip']) {
                $from_user['last_ip'] = $this->systemUser['last_ip'];
            }
            $from_user = json_encode($from_user,JSON_UNESCAPED_UNICODE);
            $params = [
                'notice_switch' => $notice_switch,
                'notice_admin_id' => $notice_admin_id,
                'notice_admin_from' => $notice_admin_from,
                'from_user' => $from_user,
            ];
            
            $config = (new HouseCommunityAboutService())->setSupervisionConfig($params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, ['config' => $config], "成功");
    }

    public function get_mark_type()
    {
        $result = [];
        try {
            $village_id = $this->request->post('village_id',0);
            $result['select'] = (new HouseCommunityHandleNoticeService())->getMarkTypeList();
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result, "成功");
    }
}