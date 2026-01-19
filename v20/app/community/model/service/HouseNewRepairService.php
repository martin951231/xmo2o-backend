<?php

/**
 * 物业工单
 * Created by PhpStorm.
 * User: lijie
 * DateTime: 2021/8/12 13:00
 */

namespace app\community\model\service;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\db\HouseNewCharge;
use app\community\model\db\HouseNewChargeNumber;
use app\community\model\db\HouseNewChargeProject;
use app\community\model\db\HouseNewChargeRule;
use app\community\model\db\HouseNewOfflinePay;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseNewRepairCate;
use app\community\model\db\HouseNewRepairCateCustom;
use app\community\model\db\HouseNewRepairGraborderNoticeRecord;
use app\community\model\db\HouseNewRepairSubject;
use app\community\model\db\HouseNewRepairWorksOrder;
use app\community\model\db\HouseNewRepairWorksOrderTimely;
use app\community\model\db\HouseVillageInfo;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseWorker;
use app\community\model\db\HouseNewRepairWorksOrderLog;
use app\community\model\db\HouseNewRepairDirectorScheduling;
use app\community\model\db\HouseAdmin;
use app\community\model\db\ProcessSubPlan;
use app\community\model\db\PropertyAdmin;
use app\community\model\db\HouseVillagePublicArea;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\HouseVillage;
use app\community\model\db\PropertyGroup;
use app\community\model\db\HouseNewRepairCateGroupRelation;
use app\community\model\db\WisdomQrcode;
use app\community\model\db\workorder\HouseCommunityAbout;
use app\community\model\db\workorder\HouseCommunityConfig;
use app\community\model\db\workorder\HouseCommunityQualityBindRepairCate;
use app\community\model\db\workorder\HouseCommunityWorkOrderConfig;
use app\community\model\service\HouseVillageService;
use app\community\model\service\workorder\HouseCommunityAboutService;
use app\community\model\service\workorder\HouseCommunityHandleNoticeService;
use app\community\model\service\workorder\HouseCommunityQualityService;
use app\community\model\service\workorder\HouseCommunityTransferOrderService;
use app\community\model\service\workorder\HouseCommunityWorkOrderService;
use app\community\model\service\HouseAppJpushInfoService;
use app\consts\HouseWorkConst;
use app\consts\WorkOrderConst;
use think\facade\Db;
use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\User;
use app\common\model\service\UserService as commonUserService;
use app\traits\WorksOrderAutoEvaluateTraits;
use app\common\model\service\export\ExportService as BaseExportService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use token\Token;

class HouseNewRepairService
{
    use WorksOrderAutoEvaluateTraits;

    //工单状态值对应中文
    public $order_status_txt = [
        '10' => '待处理',
        '11' => '待指派', // 工作人员 驳回给处理中心
        '14' => '待指派', // 工作人员 拒绝
        '15' => '待指派', // 工作人员 抢单中
        '16' => '转供暖站', // 工作人员转单指派给了供暖站 供暖站工作人员 抢单中
        '17' => '转开发商', // 工作人员转单指派给了开发商 开发商工作人员 抢单中

        '20' => '已指派',
        '21' => '已指派', // 物业管理员指派
        '22' => '已指派', // 物业工作人员指派
        '23' => '已指派', // 小区管理员指派
        '24' => '已指派', // 工作人员转单指派
        '25' => '已指派', // 工作人员拒绝转单指派
        '26' => '转供暖站', // 工作人员转单指派给了供暖站
        '27' => '转开发商', // 工作人员转单指派给了开发商
        '28' => '待审核', // 工作人员转单指派给了其他工作人员
        '29' => '待审核', // 工作人员转单指派给了其他工作人员（开发商）
        '30' => '处理中',
        '34' => '处理中', // 工作人员拒绝
        '35' => '处理中', // 工作人员添加收费项
        '36' => '处理中', // 用户同意收取工单费用
        '37' => '用户拒绝', // 用户拒绝 重新提供方案

        '40' => '已办结', // 工作人员办结
        '41' => '已办结', // 物业管理员回复 办结
        '42' => '已办结', // 物业工作人员回复 办结
        '43' => '已办结', // 小区管理员回复 办结
        '44' => '已办结', // 线上支付 办结
        '45' => '已办结', // 线下已支付 办结

        '50' => '已撤回',
        '60' => '已关闭',
        '70' => '已评价'
    ];
    // 工作人员工单状态对应中文
    public $order_work_status_txt = [
        '10' => '待指派',
        '11' => '待指派(驳回)', // 工作人员 驳回给处理中心
        '14' => '待指派(拒绝)', // 工作人员 拒绝
        '15' => '抢单中', // 工作人员 抢单中
        '16' => '转供暖站', // 工作人员转单指派给了供暖站 供暖站工作人员 抢单中
        '17' => '转开发商', // 工作人员转单指派给了开发商 开发商工作人员 抢单中

        '20' => '已指派',
        '21' => '已指派', // 物业管理员指派
        '22' => '已指派', // 物业工作人员指派
        '23' => '已指派', // 小区管理员指派
        '24' => '转单', // 工作人员转单指派
        '25' => '已指派', // 工作人员拒绝转单指派
        '26' => '转供暖站', // 工作人员转单指派给了供暖站
        '27' => '转开发商', // 工作人员转单指派给了开发商
        '28' => '转单待审核', // 工作人员转单指派给了其他工作人员
        '29' => '转单待审核', // 工作人员转单指派给了其他工作人员（开发商）
        '30' => '处理中',
        '34' => '处理中', // 工作人员拒绝
        '35' => '处理中', // 工作人员添加收费项
        '36' => '处理中', // 用户同意收取工单费用
        '37' => '用户拒绝', // 用户拒绝 重新提供方案

        '40' => '已办结', // 工作人员办结
        '41' => '已办结', // 物业管理员回复 办结
        '42' => '已办结', // 物业工作人员回复 办结
        '43' => '已办结', // 小区管理员回复 办结
        '44' => '已办结', // 线上支付 办结
        '45' => '已办结', // 线下已支付 办结

        '50' => '已撤回',
        '60' => '已关闭',
        '70' => '已评价'
    ];

    // 用户端工单状态值对应中文
    public $order_user_status_txt = [
        '10' => '处理中',
        '11' => '处理中', // 工作人员 驳回给处理中心
        '14' => '处理中', // 工作人员 拒绝
        '15' => '处理中', // 工作人员 抢单中
        '16' => '处理中', // 工作人员转单指派给了供暖站 供暖站工作人员 抢单中
        '17' => '处理中', // 工作人员转单指派给了开发商 开发商工作人员 抢单中

        '20' => '处理中',
        '21' => '处理中', // 物业管理员指派
        '22' => '处理中', // 物业工作人员指派
        '23' => '处理中', // 小区管理员指派
        '24' => '处理中', // 工作人员转单指派
        '25' => '处理中', // 工作人员拒绝转单指派
        '26' => '处理中', // 工作人员转单指派给了供暖站
        '27' => '处理中', // 工作人员转单指派给了开发商
        '28' => '处理中', // 工作人员转单指派给了其他工作人员
        '29' => '处理中', // 工作人员转单指派给了其他工作人员
        '30' => '处理中',
        '34' => '处理中', // 工作人员拒绝
        '35' => '处理中', // 工作人员添加收费项
        '36' => '处理中', // 用户同意收取工单费用
        '37' => '待重新提供方案', // 用户拒绝 重新提供方案

        '40' => '已办结', // 工作人员办结
        '41' => '已办结', // 物业管理员回复 办结
        '42' => '已办结', // 物业工作人员回复 办结
        '43' => '已办结', // 小区管理员回复 办结
        '44' => '已办结', // 线上支付 办结
        '45' => '已办结', // 线下已支付 办结

        '50' => '已撤回',
        '60' => '已关闭',
        '70' => '已评价'
    ];

    // 工单状态对应颜色
    public $order_status_color = [
        '10' => '#FE3950',
        '11' => '#FE3950',
        '14' => '#FE3950',
        '15' => '#26A6FF',
        '16' => '#26A6FF', // 工作人员转单指派给了供暖站 供暖站工作人员 抢单中
        '17' => '#26A6FF', // 工作人员转单指派给了开发商 开发商工作人员 抢单中

        '20' => '#00CC00',
        '21' => '#00CC00',
        '22' => '#00CC00',
        '23' => '#00CC00',
        '24' => '#00CC00',
        '25' => '#00CC00',
        '26' => '#00CC00', // 工作人员转单指派给了供暖站
        '27' => '#00CC00', // 工作人员转单指派给了开发商
        '28' => '#00CC00', // 工作人员转单指派给了其他工作人员
        '29' => '#00CC00', // 工作人员转单指派给了其他工作人员（开发商）
        '30' => '#26A6FF',
        '34' => '#26A6FF',
        '35' => '#26A6FF',
        '36' => '#26A6FF',
        '37' => '#26A6FF', // 用户拒绝 重新提供方案

        '40' => '#787ADF',
        '41' => '#787ADF',
        '42' => '#787ADF',
        '43' => '#787ADF',
        '44' => '#787ADF',
        '45' => '#787ADF',

        '50' => '#06C1AE',
        '60' => '#CCCCCC',
        '70' => '#FFA112'
    ];
    // 对应操作动作
    public $log_name = [
        'user_submit' => '用户提交成功',
        'property_admin_submit' => '物业管理员提交成功', // 物业管理员提交成功
        'property_work_submit' => '物业工作人员提交成功', // 物业工作人员提交成功
        'house_admin_submit' => '小区管理员提交成功', // 小区管理员提交成功
        'house_work_submit' => '小区工作人员提交成功', // 小区工作人员提交成功
        'property_admin_assign' => '物业管理员指派', // 物业管理员指派
        'property_work_assign' => '物业工作人员指派', // 物业工作人员指派
        'house_admin_assign' => '小区管理员指派', // 小区管理员指派
        'house_auto_assign' => '自动指派', // 自动指派
        'property_admin_reply' => '物业管理员回复', // 物业管理员回复
        'property_work_reply' => '物业工作人员回复', // 物业工作人员回复
        'house_admin_reply' => '小区管理员回复', // 小区管理员回复
        'reopen' => '重新打开',
        'agree' => '同意',
        'reject' => '重新提供方案',
        'recall' => '撤回',
        'closed' => '关闭',
        'evaluate' => '业主已评价',
        'work_reject_center' => '驳回给处理中心',
        'constructor_reject_center' => '提供施工方案给处理中心',
        'work_follow_up' => '处理人员跟进',
        'work_completed' => '处理人员结单',
        'work_change' => '处理人员转单',
        'work_reject_change' => '被转单人员拒绝',
        'work_change_constructor' => '转单施工方',
        'constructor_reject_change' => '提供施工方案',
        'center_assign_work' => '处理中心指派',
        'center_reply_submit' => '处理中心回复',
        'work_reject' => '拒绝',
        'work_assign' => '指派工作人员',
        'work_grab' => '工作人员抢单',
        'backstage_work_submit' => '处理中心添加工单',
        'center_work_completed' => '处理中心结单',
        'center_work_closed' => '处理中心关闭',
        'to_supply_station' => '转供暖站',
        'to_developer' => '转单开发商',
        'refuse_order_nums_report' => '拒绝工单上报督查室',
        'not_over_stars_report' => '评分过低上报督查室',
        'over_timely_report' => '超时未接单上报督查室',
        'over_not_processed_report_time' => '超时未处理设置上报督查室',
        'public_report' => '公共区域工单上报督查室',
        'to_supply_station_check' => '转单供暖站待审核',
        'to_developer_check' => '转单开发商待审核',
        'refuse_to_supply_station' => '审核拒绝转单供暖站',
        'refuse_to_developer' => '审核拒绝转单开发商',
        'to_supply_station_check_pass' => '转单供暖站审核通过',
        'to_developer_check_pass' => '转单开发商审核通过',
        'work_change_check' => '处理人员转单待审核',
        'refuse_work_change_check' => '审核拒绝处理人员转单',
        'pass_work_change_check' => '审核通过处理人员转单',
        'reject_constructor_project' => '拒绝施工方案',
        'agree_constructor_project' => '同意施工方案',
        'work_change_cate' => '更改分类',
        'has_contact_supply_station' => '获取联系（供暖站）',
        'has_contact_developer' => '获取联系（开发商）',
        'reject_to_developer' => '退回（楼栋管家）',
        'reject_change_constructor' => '退回开发商',
        'reopen_by_money' => '物业维修（付费）',
        'work_push_scheme' => '推送方案',
        'refuse_project_nums_report' => '拒绝工单次数过多上报督查室',
    ];
    // 单独针对工作人员操作
    public $work_log = [
        'house_work_submit',
        'work_reject_center',
        'constructor_reject_center',
        'work_follow_up',
        'work_completed',
        'work_change',
        'work_reject_change',
        'work_change_constructor',
        'constructor_reject_change',
        'work_reject',
        'backstage_work_submit',
        'reject_constructor_project',
        'agree_constructor_project',
        'work_change_cate',
        'has_contact_supply_station',
        'has_contact_developer',
        'reject_to_developer',
        'reject_change_constructor'
    ];

    // 用户端不可见的操作记录
    public function getUserNotLookLog()
    {
        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        if (!$jz_open) {
            return [
                'refuse_order_nums_report',
                'not_over_stars_report',
                'over_timely_report',
                'public_report',
                'over_not_processed_report_time',
                'refuse_to_supply_station',
                'refuse_to_developer',
                'to_supply_station_check',
                'to_developer_check',
                'to_developer_check_pass',
                'to_supply_station_check_pass',
                'work_change_check',
                'refuse_work_change_check',
                'pass_work_change_check',
                'has_contact_supply_station',
                'has_contact_developer',
            ];
        }
        return [
            'refuse_order_nums_report',
            'not_over_stars_report',
            'over_timely_report',
            'public_report',
            'over_not_processed_report_time',
            'refuse_to_supply_station',
            'refuse_to_developer',
            'to_supply_station_check',
            'to_developer_check',
            'to_developer_check_pass',
            'to_supply_station_check_pass',
            'work_change_check',
            'refuse_work_change_check',
            'pass_work_change_check',
            'has_contact_supply_station',
            'has_contact_developer',
            'to_supply_station',
            'to_developer',
            'work_change',
            'work_reject_change',
            'work_change_constructor',
            'work_reject',
            'work_grab',
            'reject_constructor_project',
            'agree_constructor_project',
            'constructor_reject_center',
            'work_reject_center',
            'work_change_cate',
            'reject_to_developer',
            'reject_change_constructor',
        ];
    }

    /**
     * 工单类目列表
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @author lijie
     * @date_time 2021/08/12
     */
    public function getRepairSubject($where = [], $field = true, $page = 1, $limit = 10, $order = 'id DESC')
    {
        $db_house_new_repair_subject = new HouseNewRepairSubject();
        $data = $db_house_new_repair_subject->getList($where, $field, $page, $limit, $order);
        if ($data) {
            foreach ($data as $k => $v) {
                if ($v['subject_name'] == '在线报修') {
                    $data[$k]['type'] = 'online_repair';
                } else {
                    $data[$k]['type'] = '';
                }
            }
        }
        return $data;
    }

    /**
     * 工单处理数据
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return array
     * @author lijie
     * @date_time 2021/08/19
     */
    public function orderTongji($where = [], $field = true, $page = 1, $limit = 10, $order = 'id DESC', $params = [])
    {
        //        $db_house_new_repair_subject = new HouseNewRepairSubject();
        //        $cat_list = $db_house_new_repair_subject->getList($where,$field,$page,$limit,$order)->toArray();
        $cat_list = (new HouseNewRepairCate())->getList($where, $field, $page, $limit, $order)->toArray();
        $todo_arr = [];
        $processing_arr = [];
        $processed_arr = [];
        $series = [];
        $legend = [];
        $mark_report = isset($params['mark_report']) ? intval($params['mark_report']) : 0;
        if ($cat_list) {
            if (count($cat_list) > 5) {
                //$cat_list = array_slice($cat_list, 0, 5);
            }
            $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
            foreach ($cat_list as $k => $v) {
                $whereTodo = [['o.village_id', '=', $v['village_id']], ['o.cat_fid', '=', $v['category_id']], ['o.event_status', 'between', [10, 19]]];
                if ($mark_report) {
                    $whereTodo[] = ['o.mark_report', '=', 1];
                }
                $todo_count = $db_house_new_repair_works_order->getCount($whereTodo);
                $whereProcessing = [['o.village_id', '=', $v['village_id']], ['o.cat_fid', '=', $v['category_id']], ['o.event_status', 'between', [20, 39]]];
                if ($mark_report) {
                    $whereProcessing[] = ['o.mark_report', '=', 1];
                }
                $processing_count = $db_house_new_repair_works_order->getCount($whereProcessing);
                $whereProcessed = [['o.village_id', '=', $v['village_id']], ['o.cat_fid', '=', $v['category_id']], ['o.event_status', 'in', [40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 60, 70]]];
                if ($mark_report) {
                    $whereProcessed[] = ['o.mark_report', '=', 1];
                }
                $processed_count = $db_house_new_repair_works_order->getCount($whereProcessed);
                /*$todo_arr[$k]['cate_name'] = $v['subject_name'];
                $todo_arr[$k]['color'] = $v['color'];
                $todo_arr[$k]['count'] = $todo_count;
                $processing_arr[$k]['cate_name'] = $v['subject_name'];
                $processing_arr[$k]['color'] = $v['color'];
                $processing_arr[$k]['count'] = $processing_count;
                $processed_arr[$k]['cate_name'] = $v['subject_name'];
                $processed_arr[$k]['color'] = $v['color'];
                $processed_arr[$k]['count'] = $processed_count;*/
                $series[$k]['name'] = $v['subject_name'];
                $series[$k]['type'] = 'bar';
                $series[$k]['data'] = [$todo_count, $processing_count, $processed_count];
                $series[$k]['label'] = ["show" => true, "position" => "top", "distance" => 10, "fontSize" => 10, "color" => "#FFFFFF"];
                $series[$k]['itemStyle'] = ["color" => $v['color']];
                $legend[] = $v['subject_name'];
            }
        }
        return ['series' => $series, 'legend' => $legend, 'xAxis' => ['待处理', '处理中', '已处理']];
    }

    /**
     * 工单列表
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     * @author lijie
     * @date_time 2021/08/12
     */
    public function getOrderList($where = [], $field = true, $page = 1, $limit = 10, $order = 'o.order_id DESC', $pigcms_id = 0, $roomIds = [], $params = [])
    {
        $source_type = isset($params['source_type']) ? trim($params['source_type']) : '';
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        if (isset($params['jz_open'])) {
            $jz_open = $params['jz_open'];
        } else {
            $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        }
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $houseNewRepairWorksOrderLog = new HouseNewRepairWorksOrderLog();
        $db_house_new_repair_cate = new HouseNewRepairCate();
        $db_house_new_repair_subject = new HouseNewRepairSubject();
        $db_repair_cate = new RepairCateService();
        $data = $db_house_new_repair_works_order->getList($where, $field, $page, $limit, $order, $pigcms_id, $roomIds);
        if ($data && !$data->isEmpty()) {
            $data = $data->toArray();
            $houseVillageDb = new HouseVillage();
            $village_info = array();
            if ($village_id > 0) {
                $village_info = $houseVillageDb->getInfo(array('village_id' => $village_id), '*');
            }
            $villageIds = array_column($data, 'village_id');
            $villageIds = array_unique($villageIds);
            $dbHouseVillage = new HouseVillage();
            $whereVillage = [];
            $whereVillage[] = ['village_id', 'in', $villageIds];
            $villageArr = $dbHouseVillage->getColumn($whereVillage, 'village_name', 'village_id');

            $sHouseCommunityHandleNoticeService = new HouseCommunityHandleNoticeService();
            foreach ($data as $k => $v) {
                $data[$k]['add_time_txt'] = date('Y-m-d', $v['add_time']);
                $data[$k]['reopened_txt'] = '没有';  //有没有重新打开过
                $worksOrderLog = $houseNewRepairWorksOrderLog->getOne(array('order_id' => $v['order_id'], 'log_name' => 'reopen'), '*');
                if ($worksOrderLog && !$worksOrderLog->isEmpty()) {
                    $data[$k]['reopened_txt'] = '有';
                }
                if ($source_type == 'village' || $source_type == 'platform') {
                    $data[$k]['status_txt'] = $this->order_status_txt[$v['event_status']];
                } else {
                    $data[$k]['status_txt'] = $this->order_user_status_txt[$v['event_status']];
                }
                $now_handle_village_id = isset($v['now_handle_village_id']) ? intval($v['now_handle_village_id']) : 0;
                $data_village_id = isset($v['village_id']) ? intval($v['village_id']) : 0;
                $plugAddress = '';

                if ($jz_open) {
                    $mark_type = isset($v['mark_type']) && $v['mark_type'] ? trim($v['mark_type']) : '';
                    if ($mark_type) {
                        $data[$k]['mark_type_text'] = $sHouseCommunityHandleNoticeService->getMarkTypeText($v['mark_type']);
                    } else {
                        $data[$k]['mark_type_text'] = '';
                    }
                }
                $otherVillage = false;
                if ($jz_open && $source_type == 'village' && $now_handle_village_id && $now_handle_village_id != $data_village_id && $now_handle_village_id == $village_id) {
                    // 工单是其他小区转单过来的
                    $otherVillage = true;
                    if (isset($villageArr[$data_village_id]) && $villageArr[$data_village_id]) {
                        $plugAddress = trim($villageArr[$data_village_id]) . ' ';
                    }
                }
                if ($otherVillage && in_array($v['event_status'], [16, 17, 26, 27])) {
                    // 工单是其他小区转单过来的
                    $data[$k]['status_txt'] = '被转单';
                } elseif ($otherVillage) {
                    //                    $data[$k]['status_txt'] .= '(转单)';
                }
                $data[$k]['color'] = $this->order_status_color[$v['event_status']];
                if (isset($v['address_type']) && $v['address_type'] == 'room') {
                    $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
                    $service_house_village = new HouseVillageService();
                    $vacancy_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id' => $v['room_id']], 'village_id,single_id,floor_id,layer_id,pigcms_id');
                    $address = $service_house_village->getSingleFloorRoom($vacancy_info['single_id'], $vacancy_info['floor_id'], $vacancy_info['layer_id'], $vacancy_info['pigcms_id'], $vacancy_info['village_id']);
                    $data[$k]['address_txt'] = $plugAddress . $address;
                }
                if (isset($v['address_type']) && $v['address_type'] == 'public') {
                    $db_house_village_public_area = new HouseVillagePublicArea();
                    $public_info = $db_house_village_public_area->getOne(['public_area_id' => $v['public_id']], 'public_area_name');
                    if( $v['inspection_auto_add'] != 1 ){
                        $address = $public_info['public_area_name'];
                        $data[$k]['address_txt'] = $plugAddress . $address;
                    }
                }
                $cate_name = '';
                /*if(isset($v['type_id'])){
                    $cate_info = $db_house_new_repair_cate->getOne(['id'=>$v['type_id'],'status'=>1],'cate_name');
                    $cate_name = $cate_info['cate_name'];
                }
                if (isset($v['cat_fid'])) {
                    $cate_info = $db_house_new_repair_cate->getOne(['id' => $v['cat_fid'], 'status' => 1], 'cate_name');
                    if (!empty($cate_name)) {
                        $cate_name = $cate_name . '/' . $cate_info['cate_name'];
                    } else {
                        $cate_name = $cate_info['cate_name'];
                    }
                }
                if (isset($v['cat_id'])) {
                    $cate_info = $db_house_new_repair_cate->getOne(['id' => $v['cat_id'], 'status' => 1], 'cate_name');
                    if (!empty($cate_name)) {
                        $cate_name = $cate_name . '/' . $cate_info['cate_name'];
                    } else {
                        $cate_name = $cate_info['cate_name'];
                    }
                }
                if ($cate_name) {
                    $data[$k]['cate_name'] = $cate_name;
                }*/
                if (isset($v['cat_id'])) {
                    $subject_info = $db_house_new_repair_cate->getOne(['id' => $v['cat_id']], 'cate_name');
                    $cate_name = isset($subject_info['cate_name']) ? $subject_info['cate_name'] : '';
                }
                $data[$k]['cate_name'] = $cate_name;
                if (isset($v['cat_fid'])) {
                    $subject_info = $db_house_new_repair_cate->getOne(['id' => $v['cat_fid']], 'cate_name');
                    //                    $subject_info = $db_house_new_repair_subject->getOne(['id'=>$v['category_id'],'status'=>1],'subject_name');
                    $data[$k]['subject_name'] = isset($subject_info['cate_name']) ? $subject_info['cate_name'] : '';
                }
                if ($v['label_txt']) {
                    $data[$k]['order_content'] = '';
                    $label_txt_arr = explode(',', $v['label_txt']);
                    foreach ($label_txt_arr as $v1) {
                        $label_info = $db_repair_cate->getCustomInfo(['id' => $v1]);
                        $data[$k]['order_content'] .= $label_info['name'] . ';';
                    }
                    $data[$k]['order_content'] = rtrim($data[$k]['order_content'], ';');
                }
                if (empty($data[$k]['order_content'])) {
                    $data[$k]['order_content'] = '无';
                }
                if ($page > 0 && isset($v['phone']) && !empty($v['phone'])) {
                    $data[$k]['phone'] = $houseVillageDb->phone_desensitization_v($data[$k]['phone'], $v['village_id'], $village_info);
                }
            }
        }
        return $data;
    }

    //导出处理
    public function getExportOrderList($where = [], $field = true, $order = 'order_id DESC', $source_type = '')
    {
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $db_house_new_repair_works_order_log = new HouseNewRepairWorksOrderLog();
        $db_house_new_repair_cate = new HouseNewRepairCate();
        $db_repair_cate = new RepairCateService();
        $dataObj = $db_house_new_repair_works_order->getAllList($where, $field, $order);

        $data = array();
        if ($dataObj && !$dataObj->isEmpty()) {
            $data = $dataObj->toArray();
        }

        if ($data) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->getDefaultColumnDimension()->setWidth(24);
            $sheet->getDefaultRowDimension()->setRowHeight(24); //设置行高
            $sheet->getRowDimension('1')->setRowHeight(26); //设置行高
            $sheet->getRowDimension('2')->setRowHeight(25);
            $sheet->getStyle('A1:Q1')->getFont()->setBold(true)->setSize(15);
            $sheet->mergeCells('A1:Q1'); //合并单元格
            $sheet->getColumnDimension('A')->setWidth(21);
            $sheet->getColumnDimension('M')->setWidth(160);
            $sheet->setCellValue('A1', '工单列表数据');
            //设置单元格内容
            $titleArr = array('序号', '上报人员', '手机号码', '上报时间', '上报位置', '工单类目', '上报分类', '上门时间', '工单详情', '工单状态', '处理人员', '处理人员手机号码', '跟进内容', '回复内容', '回复时间', '评论内容', '评论时间');
            $titCol = 'A';
            foreach ($titleArr as $key => $value) {
                //单元格内容写入
                $sheet->setCellValue($titCol . '2', $value);
                $titCol++;
            }
            $sheet->getStyle('A2:Q2')->getFont()->setBold(true);
            //设置单元格内容
            $row = 3;
            foreach ($data as $k => $v) {
                $dataCol = 'A';
                $itemD = array('order_id' => $v['order_id'], 'name' => $v['name'], 'phone' => $v['phone']);
                $v['add_time_txt'] = date('Y-m-d H:i', $v['add_time']);
                if ($source_type == 'village') {
                    $v['status_txt'] = $this->order_status_txt[$v['event_status']];
                } else {
                    $v['status_txt'] = $this->order_user_status_txt[$v['event_status']];
                }
                $itemD['add_time_txt'] = $v['add_time_txt'];
                $v['color'] = $this->order_status_color[$v['event_status']];
                if (isset($v['address_type']) && $v['address_type'] == 'room') {
                    $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
                    $service_house_village = new HouseVillageService();
                    $vacancy_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id' => $v['room_id']], 'village_id,single_id,floor_id,layer_id,pigcms_id');
                    $address = $service_house_village->getSingleFloorRoom($vacancy_info['single_id'], $vacancy_info['floor_id'], $vacancy_info['layer_id'], $vacancy_info['pigcms_id'], $vacancy_info['village_id']);
                    $v['address_txt'] = $address;
                }
                if (isset($v['address_type']) && $v['address_type'] == 'public') {
                    $db_house_village_public_area = new HouseVillagePublicArea();
                    $public_info = $db_house_village_public_area->getOne(['public_area_id' => $v['public_id']], 'public_area_name');
                    $address = $public_info['public_area_name'];
                    $v['address_txt'] = $address;
                }
                $itemD['address_txt'] = $v['address_txt'];
                $cate_name = '';
                if (isset($v['cat_id'])) {
                    $subject_info = $db_house_new_repair_cate->getOne(['id' => $v['cat_id']], 'cate_name');
                    $cate_name = isset($subject_info['cate_name']) ? $subject_info['cate_name'] : '';
                }
                $v['cate_name'] = $cate_name;
                $v['subject_name'] = '';
                if (isset($v['cat_fid'])) {
                    $subject_info = $db_house_new_repair_cate->getOne(['id' => $v['cat_fid']], 'cate_name');
                    $v['subject_name'] = isset($subject_info['cate_name']) ? $subject_info['cate_name'] : '';
                }
                $itemD['subject_name'] = $v['subject_name'];
                $itemD['cate_name'] = $v['cate_name'];
                $itemD['go_time_txt'] = '';
                if ($v['go_time'] > 10000) {
                    $itemD['go_time_txt'] = date('Y-m-d H:i', $v['go_time']);
                }
                $itemD['order_content'] = $v['order_content'];
                $itemD['status_txt'] = $v['status_txt'];
                $itemD['handle_name'] = '';
                $itemD['handle_phone'] = '';
                $recordObj = $db_house_new_repair_works_order_log->getOne(['order_id' => $v['order_id']], 'log_id,log_operator,log_phone,log_name,add_time,log_imgs,log_content');
                if ($recordObj && !$recordObj->isEmpty()) {
                    $handle_name = $recordObj['log_operator'];
                    if ($v['order_type'] == 5 && empty($recordObj['log_operator'])) {
                        $handle_name = '处理中心管理员';
                    }
                    $itemD['handle_name'] = $handle_name;
                    $itemD['handle_phone'] = $recordObj['log_phone'];;
                }
                $itemD['log_content'] = '';
                $itemD['reply_content'] = '';
                $itemD['reply_time'] = '';
                $itemD['evaluate_content'] = '';
                $itemD['evaluate_time'] = '';
                $where_log = [];
                $where_log[] = ['order_id', '=', $v['order_id']];
                $log_list_obj = $db_house_new_repair_works_order_log->getList($where_log, true, 0, 0, 'log_id ASC');
                if ($log_list_obj && !$log_list_obj->isEmpty()) {
                    $log_list = $log_list_obj->toArray();
                    if ($log_list) {
                        $log_arr = $this->log_name;
                        $logContent = '';
                        foreach ($log_list as $val) {
                            if (isset($val['log_name']) && $val['log_name']) {
                                if (isset($val['log_num']) && $val['log_num'] > 1) {
                                    $title = "第{$val['log_num']}次" . $log_arr[$val['log_name']];
                                } else {
                                    $title = isset($log_arr[$val['log_name']]) ? $log_arr[$val['log_name']] : '';
                                }
                                if (isset($val['wisdomqrcode_id']) && $val['wisdomqrcode_id'] > 0 && strpos($val['log_name'], '_submit')) {
                                    $title = '巡检转工单';
                                }
                                $add_time_txt = '';
                                if (isset($val['add_time']) && $val['add_time']) {
                                    $add_time_txt = date('Y-m-d H:i', $val['add_time']);
                                    $logContent .= '  时间【' . $add_time_txt . '】';
                                } else {
                                    $logContent .= '  ';
                                }
                                $logContent .= '类型【' . $title . '】';
                                if ($val['log_name'] == 'evaluate') {
                                    $logContent .= '评分【' . $val['evaluate'] . '】分';
                                    $itemD['evaluate_content'] = $val['log_content'] . ' 评分：' . $val['evaluate'] . '分';
                                    $itemD['evaluate_time'] = $add_time_txt;
                                }
                                if (isset($val['log_content']) && $val['log_content'] && $val['log_name'] == 'evaluate') {
                                    $logContent .= '评论详情【' . $val['log_content'] . '】';
                                }
                                if (isset($val['log_operator']) && $val['log_operator']) {
                                    $logContent .= '操作人员【' . $val['log_operator'] . '】';
                                }
                                if (in_array($val['log_name'], array('property_admin_reply', 'property_work_reply', 'house_admin_reply', 'center_reply_submit'))) {
                                    $itemD['reply_content'] = $val['log_content'];
                                    $itemD['reply_time'] = $add_time_txt;
                                }
                                if (isset($val['log_phone']) && $val['log_phone']) {
                                    $logContent .= '手机号码【' . $val['log_phone'] . '】';
                                }
                                if (isset($val['log_content']) && $val['log_content'] && $val['log_name'] != 'evaluate') {
                                    $logContent .= '内容【' . $val['log_content'] . '】';
                                }
                                if (isset($val['log_info']) && $val['log_info'] && @unserialize($val['log_info'])) {
                                    $log_info = unserialize($val['log_info']);
                                    if ($log_info && isset($log_info['name']) && $log_info['name']) {
                                        $logContent .= '处理人员【' . $log_info['name'] . '-' . $log_info['phone'] . '】';
                                    }
                                }
                                $logContent .= "\r\n";
                            }
                        }
                        $itemD['log_content'] = $logContent;
                    }
                }
                foreach ($itemD as $kk => $cvalue) {
                    //单元格内容写入
                    //$sheet->setCellValue($dataCol . $row, $cvalue);
                    $sheet->getStyle('M' . $row)->getAlignment()->setWrapText(true);
                    $sheet->setCellValueExplicit($dataCol . $row, $cvalue, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2);
                    $dataCol++;
                }
                $row++;
            }

            //保存
            $styleArrayBody = [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ],
            ];
            $total_rows = $row + 1;
            //添加所有边框/居中
            $sheet->getStyle('A1:L' . $total_rows)->applyFromArray($styleArrayBody);

            $styleMArrayBody = [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP
                ],
            ];

            $sheet->getStyle('M1:M' . $total_rows)->applyFromArray($styleMArrayBody);

            $sheet->getStyle('N1:Q' . $total_rows)->applyFromArray($styleArrayBody);
            //下载
            $filename = '工单数据明细' . date('YmdHis') . '.xlsx';
            (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet);
            return $this->downloadExportFile($filename);
        }
        throw new \think\Exception("没有数据可以导出！");
    }

    /**
     * 下载表格
     */
    public function downloadExportFile($param)
    {
        $returnArr = [];
        if (!file_exists(request()->server('DOCUMENT_ROOT') . '/v20/runtime/' . $param)) {
            $returnArr['error'] = 1;
            $returnArr['url'] = '';
            return $returnArr;
        }
        $filename = $param;

        $ua = request()->server('HTTP_USER_AGENT');
        $ua = strtolower($ua);
        if (preg_match('/msie/', $ua) || preg_match('/edge/', $ua) || preg_match('/trident/', $ua)) { //判断是否为IE或Edge浏览器
            $filename = str_replace('+', '%20', urlencode($filename)); //使用urlencode对文件名进行重新编码
        }

        $returnArr['url'] = cfg('site_url') . '/v20/runtime/' . $param;
        $returnArr['error'] = 0;

        return $returnArr;
    }

    public function getOrderEvaluateList($where = [], $field = true, $page = 1, $limit = 10, $order = 'o.order_id DESC', $evaluate_star = 1)
    {
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $db_house_new_repair_cate = new HouseNewRepairCate();
        $db_repair_cate = new RepairCateService();
        $dataObj = $db_house_new_repair_works_order->getListByEvaluate($where, $field, $page, $limit, $order);
        if ($dataObj && !$dataObj->isEmpty()) {
            $houseNewRepairWorksOrderLog = new HouseNewRepairWorksOrderLog();
            $data = $dataObj->toArray();
            $houseVillageDb = new HouseVillage();
            $village_info = $houseVillageDb->getInfo(array('village_id' => $data['0']['village_id']), '*');
            foreach ($data as $k => $v) {
                $data[$k]['add_time_txt'] = date('Y-m-d', $v['add_time']);
                $data[$k]['status_txt'] = $this->order_status_txt[$v['event_status']];
                $data[$k]['color'] = $this->order_status_color[$v['event_status']];

                $evaluateWhere = array();
                $evaluateWhere[] = array('village_id', '=', $v['village_id']);
                $evaluateWhere[] = array('log_name', '=', 'evaluate');
                $evaluateWhere[] = array('order_id', '=', $v['order_id']);
                $evaluateWhere[] = array('evaluate	 ', '=', $evaluate_star);
                $data[$k]['evaluate_star'] = $houseNewRepairWorksOrderLog->getCount($evaluateWhere);

                if (isset($v['address_type']) && $v['address_type'] == 'room') {
                    $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
                    $service_house_village = new HouseVillageService();
                    $vacancy_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id' => $v['room_id']], 'village_id,single_id,floor_id,layer_id,pigcms_id');
                    $address = $service_house_village->getSingleFloorRoom($vacancy_info['single_id'], $vacancy_info['floor_id'], $vacancy_info['layer_id'], $vacancy_info['pigcms_id'], $vacancy_info['village_id']);
                    $data[$k]['address_txt'] = $address;
                }
                if (isset($v['address_type']) && $v['address_type'] == 'public') {
                    $db_house_village_public_area = new HouseVillagePublicArea();
                    $public_info = $db_house_village_public_area->getOne(['public_area_id' => $v['public_id']], 'public_area_name');
                    $address = $public_info['public_area_name'];
                    $data[$k]['address_txt'] = $address;
                }
                $cate_name = '';
                if (isset($v['cat_id'])) {
                    $subject_info = $db_house_new_repair_cate->getOne(['id' => $v['cat_id']], 'cate_name');
                    $cate_name = isset($subject_info['cate_name']) ? $subject_info['cate_name'] : '';
                }
                $data[$k]['cate_name'] = $cate_name;
                if (isset($v['cat_fid'])) {
                    $subject_info = $db_house_new_repair_cate->getOne(['id' => $v['cat_fid']], 'cate_name');
                    $data[$k]['subject_name'] = isset($subject_info['cate_name']) ? $subject_info['cate_name'] : '';
                }
                if ($v['label_txt']) {
                    $data[$k]['order_content'] = '';
                    $label_txt_arr = explode(',', $v['label_txt']);
                    foreach ($label_txt_arr as $v) {
                        $label_info = $db_repair_cate->getCustomInfo(['id' => $v]);
                        $data[$k]['order_content'] .= $label_info['name'] . ';';
                    }
                    $data[$k]['order_content'] = rtrim($data[$k]['order_content'], ';');
                }
                if (empty($data[$k]['order_content'])) {
                    $data[$k]['order_content'] = '无';
                }
                if (isset($v['phone']) && !empty($v['phone'])) {
                    $data[$k]['phone'] = $houseVillageDb->phone_desensitization_v($v['phone'], $v['village_id'], $village_info);
                }
            }
            return $data;
        }
        return array();
    }

    /**
     * 单数量
     * @param array $where
     * @return int
     * @author lijie
     * @date_time 2021/08/18
     */
    public function getOrderCount($where = [])
    {
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $count = $db_house_new_repair_works_order->getCount($where);
        return $count;
    }

    public function getCountByEvaluate($where = [])
    {
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $count = $db_house_new_repair_works_order->getCountByEvaluate($where);
        return $count;
    }

    /**
     * 分类选择页
     * @param array $where
     * @param bool $field
     * @return \think\Collection
     * @author lijie
     * @date_time 2021/08/12
     */
    public function getCateList($where = [], $field = true)
    {
        $db_house_new_repair_subject = new HouseNewRepairSubject();
        $db_house_new_repair_cate = new HouseNewRepairCate();
        $data = $db_house_new_repair_subject->getList($where, $field, 0, 0, 'id DESC');
        if ($data) {
            foreach ($data as $k => $v) {
                if ($v['subject_name'] == '个人报修') {
                    $data[$k]['type'] = 'personal_repair';
                } elseif ($v['subject_name'] == '公共报修') {
                    $data[$k]['type'] = 'public_repair';
                } else {
                    $data[$k]['type'] = '';
                }
                $list = $db_house_new_repair_cate->getList(['subject_id' => $v['type_id'], 'status' => 1, 'parent_id' => 0], 'id as cat_fid,cate_name', 0, 0, 'cat_fid DESC');

                $data[$k]['list'] = $list;
            }
        }
        return $data;
    }

    /**
     * 工单分类
     * @param array $where
     * @param bool $field
     * @param string $order
     * @param string $appType
     * @return array
     */
    public function getFidCateList($where = [], $field = true, $order = 'sort DESC,id desc', $appType = '')
    {
        $where_parent = $where;
        if ($appType == 'packapp') {
            $field = 'id as value,cate_name as label';
        }
        $db_house_new_repair_cate = new HouseNewRepairCate();
        $data = $db_house_new_repair_cate->getList($where_parent, $field, 0, 0, $order)->toArray();
        return $data;
    }

    /**
     * 描述标签列表
     * @param array $where
     * @param bool $field
     * @param string $order
     * @return \think\Collection
     * @author lijie
     * @date_time 2021/08/12
     */
    public function getLabel($where = [], $field = true, $order = 'id DESC')
    {
        $db_house_new_repair_cate_custom = new HouseNewRepairCateCustom();
        $data = $db_house_new_repair_cate_custom->getList($where, $field, 0, 0, $order);
        return $data;
    }

    /**
     * Notes: 获取工单列表
     * @param int $worker_id 当前登录角色id
     * @param int $login_role 登录角色
     * @param string $status 查找状态
     * @param string $search 查询信息
     * @param int $page 分页
     * @param int $page_size 分页大小
     * @param string $order 排序
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/8/16 14:52
     */
    public function workGetWorksOrderLists($worker_id, $login_role, $status = 'todo', $search = '', $page = 1, $page_size = 10, $is_count = false, $village_id = 0)
    {
        if ($search) {
            $search = str_replace("'", "\'", $search);
        }
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $dbHouseWorker = new HouseWorker();
        $where_work_str = '';
        $where_submit_str = '';
        $where_submit_change = '';
        $where_house_admin = '';
        $where_property_admin = '';
        $whereRaw = '';
        $property_id = 0;
        $work_info = [];
        $house_admin_info = [];
        $is_status = (new HouseVillageService())->checkVillageField($village_id, 'is_grab_order');
        // 工单类型 0 业主上报工单 1 物业总管理员 2 物业普通管理员  3 小区物业管理员 4小区物业工作人员
        $wid = 0;
        if (HouseWorkConst::MANAGE_APP_VILLAGE_WORK == $login_role) {
            // 小区物业工作人员
            $where_work = [];
            $where_work[] = ['wid', '=', $worker_id];
            $work_info = $dbHouseWorker->getOne($where_work);
            // 曾经处理过的订单
            $order_ids = $this->getWorkerDealOrderId($worker_id);
            $order_type = 4;
            $wid = $worker_id;
        } elseif (HouseWorkConst::MANAGE_APP_VILLAGE_ADMIN == $login_role) {
            // 小区物业管理人员
            $db_house_admin = new HouseAdmin();
            $where_admin = [];
            $where_admin[] = ['id', '=', $worker_id];
            $house_admin_info = $db_house_admin->getOne($where_admin);
            $order_type = 3;
            $wid = $house_admin_info['wid'];
        } elseif (HouseWorkConst::MANAGE_APP_PROPERTY_USER == $login_role) {

            // 物业普通管理员
            $db_property_admin = new PropertyAdmin();
            $where_admin = [];
            $where_admin[] = ['id', '=', $worker_id];
            $property_admin_info = $db_property_admin->get_one($where_admin);
            $wid = 0;
            if (!empty($property_admin_info)) {
                $wid = $property_admin_info['wid'];
                $property_id = $property_admin_info['property_id'];
            }
            $order_type = 2;
        } elseif (HouseWorkConst::MANAGE_APP_PROPERTY_ADMIN == $login_role) {

            // 物业总管理员
            $service_house_village = new HouseVillageService();
            $property_admin_info = $service_house_village->get_house_property($worker_id);
            $order_type = 1;
            $wid = 0;
            $property_id = $worker_id;
        } else {
            throw new \think\Exception("当前身份无权限");
        }
        $property_id = $property_id > 0 ? $property_id : 0;
        if (!empty($work_info)) {
            $where_work_str = !empty($order_ids) ? "((a.order_id in (" . implode(',', $order_ids) . ")  OR a.worker_id={$worker_id})" : "(a.worker_id={$worker_id}";
        }
        if ('todo' == $status) {
            // 待处理
            if ($search) {
                $search_str = "(a.name LIKE '%{$search}%' OR a.phone LIKE '%{$search}%')";
            } else {
                $search_str = '';
            }

            if (!empty($work_info)) {
                // 处理人员 已指派和处理中和被转单
                // 待处理的不包含 经过的 只能是当前工作人员处理的需要处于待指派
                $where_work_str = "(a.worker_id={$worker_id} AND a.worker_type=0 AND a.event_status>=20 AND a.event_status<40 AND a.event_status not in (28,29))";
            } elseif ($house_admin_info) {
                // 处理待指派工单
                $where_house_admin = '(a.event_status>=10 AND a.event_status<20)';
            } elseif ($property_admin_info) {
                // 处理待指派工单
                $where_property_admin = '(a.event_status>=10 AND a.event_status<20)';
            }
            // 提交人员 已办结和已评价
            $where_submit_str = "(a.uid={$worker_id} AND a.order_type={$order_type} AND ((a.event_status>=40 AND a.event_status<50) OR (a.event_status>=70 AND a.event_status<80)))";
        } elseif ('processing' == $status) {

            // 处理中
            if ($search) {
                $search_str = "(a.name LIKE '%{$search}%' OR a.phone LIKE '%{$search}%')";
            } else {
                $search_str = '';
            }
            if (!empty($work_info)) {
                // 处理人员 已办结
                // 已经转给其他人员， 待指派，处理中，已结单 都显示在处理中
                $where_work_str .= " AND a.worker_type=0 AND ((a.event_status>=40 AND a.event_status<50) OR (a.worker_id=$worker_id AND a.event_status in (28,29)) OR (a.worker_id<>{$worker_id} AND a.uid<>{$worker_id} AND a.order_type={$order_type} AND a.event_status not in (24,16,17,26,27) AND a.event_status<40)))";
            } elseif ($house_admin_info) {
                // 处理中
                $where_house_admin = '(a.event_status>=20 AND a.event_status<50)';
            } elseif ($property_admin_info) {
                // 处理中
                $where_property_admin = '(a.event_status>=20 AND a.event_status<50)';
            }
            // 提交人员 待指派 已指派 处理中
            $where_submit_str = "(a.uid={$worker_id} AND a.order_type={$order_type} AND ((a.event_status<40)))";
        } elseif ('processed' == $status) {
            // 已处理
            if ($search) {
                $search_str = "(a.name LIKE '%{$search}%' OR a.phone LIKE '%{$search}%')";
            } else {
                $search_str = '';
            }
            if (!empty($work_info)) {
                // 处理人员 已转单已评价已撤回已关闭【被转单  放在待处理中】
                $where_work_str .= " AND a.worker_type=0 AND ((a.event_status>=50 AND a.event_status<80) OR (a.worker_id<>{$worker_id} AND a.event_status in (24,16,17,26,27))))";
                // 处理过的非提交处理过人员
                //                $where_submit_change = "(b.operator_id={$worker_id} AND b.operator_type=30 AND b.log_name='work_change')";
            } elseif ($house_admin_info) {
                // 已处理
                $where_house_admin = '(a.event_status>=50 AND a.event_status<70)';
            } elseif ($property_admin_info) {
                // 已处理
                $where_property_admin = '(a.event_status>=50 AND a.event_status<70)';
            }
            // 提交人员 已撤回 已关闭
            $where_submit_str = "(a.uid={$worker_id} AND a.order_type={$order_type} AND ((a.event_status>=50 AND a.event_status<70)))";
        } elseif ('all' == $status) {
            // 已处理
            if ($search) {
                $search_str = "(a.name LIKE '%{$search}%' OR a.phone LIKE '%{$search}%')";
            } else {
                $search_str = '';
            }
            if (!empty($work_info)) {
                // 处理人员 已评价已撤回已关闭
                $where_work_str .= " AND a.worker_type=0)";
                // 提交人员 已办结和已评价
                $where_submit_str = "(a.uid={$worker_id} AND a.order_type=4)";
                // 处理过的非提交处理过人员
                //                $where_submit_change = "(b.operator_id={$worker_id} AND b.operator_type=30)";
            } elseif ($house_admin_info) {
                // 全部
                $where_house_admin = '(a.event_status>=10)';
            } elseif ($property_admin_info) {
                // 全部
                $where_property_admin = '(a.event_status>=10)';
            }
        }
        if ($where_house_admin) {
            // 小区物业管理人员
            if ($where_submit_str) {
                $whereRaw = "({$where_house_admin} OR {$where_submit_str})";
            } else {
                $whereRaw = $where_house_admin;
            }
            if (isset($house_admin_info['village_id']) && $house_admin_info['village_id']) {
                $whereRaw .= " AND a.village_id={$house_admin_info['village_id']}";
            }
        } elseif ($where_property_admin) {
            // 物业普通管理员
            if ($where_submit_str) {
                $whereRaw = "({$where_property_admin} OR {$where_submit_str})";
            } else {
                $whereRaw = $where_property_admin;
            }
            if (isset($property_admin_info['menus']) && $property_admin_info['menus']) {
                $whereRaw .= " AND a.village_id in ({$property_admin_info['menus']})";
            } elseif ($order_type != 1) {
                $whereRaw .= " AND a.village_id=0";
            }
            if ($order_type == 1 || $order_type == 2) {
                $whereRaw .= " AND a.property_id=" . $property_id;
            }
        } elseif ($where_work_str && $where_submit_str) {
            // 小区物业工作人员 提交和处理者
            $whereRaw = "({$where_work_str} OR {$where_submit_str})";
        } elseif ($where_work_str && $where_submit_str && $where_submit_change) {
            // 小区物业工作人员 提交和处理者和转单
            $whereRaw = "({$where_work_str} OR {$where_submit_str} OR {$where_submit_change})";
        }
        if (in_array($status, ['todo', 'all']) && $is_status) {
            //todo 获取当前账号所在的部门关联工单分类
            $cate_id = $this->getRepairCateId($village_id, $wid);
            if (!empty($cate_id) && $whereRaw) {
                $whereRaw = '(' . $whereRaw . ' OR (a.village_id =' . $village_id . ' AND a.event_status = 15 AND a.cat_fid in (' . (implode(',', $cate_id)) . ')))';
            } else if (!empty($cate_id) && !$whereRaw) {
                $whereRaw = '(a.village_id =' . $village_id . ' AND a.event_status = 15 AND a.cat_fid in (' . (implode(',', $cate_id)) . '))';
            }
        }
        if ($search_str) {
            $whereRaw = "{$search_str} AND " . $whereRaw;
        }
        if ($is_count) {
            $work_count = $db_house_new_repair_works_order->getWorkHandleCount([], $whereRaw);
            if (!$work_count) {
                $work_count = 0;
            }
            return $work_count;
        } else {
            $field = 'a.*';
            $order = 'a.add_time DESC';
            $work_list = $db_house_new_repair_works_order->getWorkHandleList([], $whereRaw, $field, $page, $page_size, $order);
            fdump_api(['line:' . __LINE__, 'sql' => $db_house_new_repair_works_order->getLastSql()], '$sql');
        }
        if (!empty($work_list)) {
            $work_list = $work_list->toArray();
            if ($work_list) {
                $catIdArr = array_unique(array_column($work_list, 'cat_id'));
                $catFidArr = array_unique(array_column($work_list, 'cat_fid'));
                $catIds = array_merge($catIdArr, $catFidArr);
                $db_house_new_repair_cate = new HouseNewRepairCate();
                $whereCate = [];
                $whereCate[] = ['id', 'in', $catIds];
                $catArr = $db_house_new_repair_cate->getColumn($whereCate, 'cate_name', 'id');
                fdump_api($catArr, '$catArr');
                foreach ($work_list as &$val) {
                    // 兼容非工作人员不可抢单 只可指派
                    if ($login_role != HouseWorkConst::MANAGE_APP_VILLAGE_WORK && $val['event_status'] == 15) {
                        $val['event_status'] = 10;
                    }
                    $cat_id = isset($val['cat_id']) ? intval($val['cat_id']) : 0;
                    $cat_fid = isset($val['cat_fid']) ? intval($val['cat_fid']) : 0;
                    $cat = '';
                    if (isset($catArr[$cat_fid])) {
                        $cat = trim($catArr[$cat_fid]);
                    }
                    if (isset($catArr[$cat_id])) {
                        $cat && $cat .= '/' . trim($catArr[$cat_id]);
                        !$cat && $cat = trim($catArr[$cat_id]);
                    }
                    $val['catText'] = $cat;

                    if (isset($val['add_time']) && $val['add_time']) {
                        $val['add_time_txt'] = date('Y-m-d H:i:s', $val['add_time']);
                    }
                    if (isset($val['last_time']) && $val['last_time']) {
                        $val['last_time_txt'] = date('Y-m-d H:i:s', $val['last_time']);
                    }
                    if (isset($val['event_status']) && $val['event_status']) {
                        if (24 == $val['event_status'] && $val['worker_type'] == 0 && $val['worker_id'] == $worker_id) {
                            // 当前人员处于被转单
                            $val['order_status_txt'] = '被转单';
                        } elseif (24 == $val['event_status']) {
                            $val['order_status_txt'] = '已转单';
                        } else {
                            $val['order_status_txt'] = $this->order_work_status_txt[$val['event_status']];
                        }
                        $val['order_status_color'] = $this->order_status_color[$val['event_status']];
                    }
                    if (HouseWorkConst::MANAGE_APP_VILLAGE_WORK == $login_role && isset($val['operator_id']) && $val['operator_id'] = $worker_id && isset($val['operator_type']) && $val['operator_type'] = 30 && isset($val['log_name']) && $val['log_name'] = 'work_change' && isset($val['worker_id'])) {
                        $val['order_status_txt'] = '已转单';
                        $val['order_status_color'] = '#336600';
                    }
                    if (isset($val['label_txt']) && trim($val['label_txt'], ',')) {

                        $db_house_new_repair_cate_custom = new HouseNewRepairCateCustom();
                        $label_txt = trim($val['label_txt'], ',');
                        $whereLabel = [];
                        $whereLabel[] = ['id', 'in', $label_txt];
                        $labelData = $db_house_new_repair_cate_custom->getList($whereLabel, 'id,name', 0, 0, '');
                        if (!empty($labelData)) {
                            $labelArr = [];
                            foreach ($labelData as $LVal) {
                                if ($LVal && isset($LVal['name'])) {
                                    $labelArr[] = $LVal['name'];
                                }
                            }
                            $label_txt = implode("；", $labelArr);
                        }
                        $val['order_content'] = $label_txt;
                    }
                    if (empty($val['order_content'])) {
                        $val['order_content'] = '无';
                    }
                }
            }
        } else {
            $work_list = [];
        }
        $arr = [];
        $arr['list'] = $work_list;
        return $arr;
    }

    /**
     * Notes: 移动管理端添加工单
     * @param $data
     * @return array
     * @throws \think\Exception
     * @author: wanzy
     * @date_time: 2021/8/18 11:56
     */
    public function addWorksOrder($data)
    {
        if (!isset($data['village_id']) || !$data['village_id']) {
            throw new \think\Exception('缺少必传参数');
        }
        $village_id = intval($data['village_id']);
        $db_house_village = new HouseVillage();
        $village_info = $db_house_village->getOne($village_id, 'village_id,village_name,property_id');
        if (empty($village_info)) {
            throw new \think\Exception('对应小区不存在');
        }
        $db_house_village_info = new HouseVillageInfo();
        $village_info_data = $db_house_village_info->getOne(['village_id' => $village_id], 'village_id,is_timely');
        if (empty($village_info_data)) {
            throw new \think\Exception('对应小区不存在');
        }
        if ($village_info_data['is_timely'] == 1) {
            if (empty($data['go_time'])) {
                throw new \think\Exception('上门时间不能为空');
            }
            $data['go_time'] = strtotime($data['go_time']);
            if (empty($data['go_time'])) {
                throw new \think\Exception('上门时间不能为空');
            }
            if ($data['go_time'] < time()) {
                throw new \think\Exception('上门时间不能小于当前时间');
            }
        } else {
            if (!empty($data['go_time'])) {
                $data['go_time'] = strtotime($data['go_time']);
                if (!empty($data['go_time']) && $data['go_time'] < time()) {
                    throw new \think\Exception('上门时间不能小于当前时间');
                } elseif (empty($data['go_time'])) {
                    $data['go_time'] = 0;
                }
            }
        }
        $property_id = isset($village_info['property_id']) ? intval($village_info['property_id']) : 0;
        if (!isset($data['cat_id']) || !$data['cat_id']) {
            throw new \think\Exception('缺少必传参数');
        }
        $db_house_new_repair_cate = new HouseNewRepairCate();
        $cat_id = $data['cat_id'];
        $cate_info = $db_house_new_repair_cate->getOne(['id' => $cat_id, 'village_id' => $village_id], 'id,subject_id,cate_name,parent_id,type,uid');
        if (empty($cate_info) || $cate_info->isEmpty()) {
            throw new \think\Exception('对应分类信息不存在');
        }
        if (isset($cate_info['parent_id']) && $cate_info['parent_id']) {
            $cat_fid = intval($cate_info['parent_id']);
        } else if (isset($data['cat_fid']) && $data['cat_fid']) {
            $cat_fid = intval($data['cat_fid']);
        } else {
            throw new \think\Exception('缺少主分类');
        }
        $address_type = isset($data['address_type']) ? trim($data['address_type']) : '';
        if ($address_type) {
            if (!in_array($address_type, ['public', 'room'])) {
                throw new \think\Exception('缺少必传参数');
            }
            if (!isset($data['address_id']) || !$data['address_id']) {
                throw new \think\Exception('缺少必传参数');
            }
        }
        $address_id = intval($data['address_id']);
        $public_id = 0;
        $room_id = 0;
        $single_id = 0;
        $floor_id = 0;
        $layer_id = 0;
        $address_txt = '';
        $toSingleId = 0;
        if ('public' == $address_type) {
            $public_id = intval($data['address_id']);
            // 获取下公共区域
            $db_house_village_public_area = new HouseVillagePublicArea();
            $wherePublicArea = [];
            $wherePublicArea[] = ['public_area_id', '=', $public_id];
            $publicAreaInfo = $db_house_village_public_area->getOne($wherePublicArea, 'public_area_id, public_area_name');
            if (empty($publicAreaInfo)) {
                throw new \think\Exception('所选公共区域不存在');
            }
            $address_txt = $publicAreaInfo['public_area_name'];
        } elseif ('room' == $address_type) {

            $room_id = intval($data['address_id']);
            $db_house_village_user_vacancy = new HouseVillageUserVacancy();
            $where_room = [];
            $where_room[] = ['pigcms_id', '=', $room_id];
            $field_room = 'pigcms_id, room, village_id, single_id, floor_id, layer_id';
            $room_info = $db_house_village_user_vacancy->getOne($where_room, $field_room);
            if (empty($room_info)) {
                throw new \think\Exception('所选房屋不存在');
            }
            if (isset($data['single_id']) && $data['single_id']) {
                $single_id = intval($data['single_id']);
                $toSingleId = $single_id;
            } elseif (isset($room_info['single_id']) && $room_info['single_id']) {
                $single_id = intval($room_info['single_id']);
                $toSingleId = $single_id;
            } else {
                throw new \think\Exception('缺少楼栋');
            }
            if (isset($data['floor_id']) && $data['floor_id']) {
                $floor_id = intval($data['floor_id']);
            } elseif (isset($room_info['floor_id']) && $room_info['floor_id']) {
                $floor_id = intval($room_info['floor_id']);
            } else {
                throw new \think\Exception('缺少单元');
            }
            if (isset($data['layer_id']) && $data['layer_id']) {
                $layer_id = intval($data['layer_id']);
            } elseif (isset($room_info['layer_id']) && $room_info['layer_id']) {
                $layer_id = intval($room_info['layer_id']);
            } else {
                throw new \think\Exception('缺少楼层');
            }
            $room_village_id = isset($room_info['village_id']) && $room_info['village_id'] ? intval($room_info['village_id']) : $village_id;
            $service_house_village = new HouseVillageService();
            $address_txt = $service_house_village->getSingleFloorRoom($single_id, $floor_id, $layer_id, $room_id, $room_village_id);
        }
        if ($address_txt && isset($village_info['village_name']) && $village_info['village_name']) {
            $address_txt = $village_info['village_name'] . ' ' . $address_txt;
        } elseif (!$address_txt) {
            $address_txt = '';
        }

        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();

        $toWorkId = 0;
        $workOrderConfig = [];
        $assign_record = [];
        $is_supply_station = 0;
        $communityConfig = [];
        if ($jz_open) {
            $configInfo = (new HouseCommunityAboutService())->getHouseCommunityConfig($village_id);
            $supplyInfo = (new HouseCommunityAboutService())->getVillageFacilitiesInfo($village_id);
            if (isset($supplyInfo['is_virtual']) && intval($supplyInfo['is_virtual']) == 1) {
                // 是供暖站
                $is_supply_station = 1;
            }
            // 按照配置获取下分配人员
            $workOrderParam = [
                'village_id' => $village_id,
                'is_supply_station' => $is_supply_station,
                'configInfo' => $configInfo,
                'cat_id' => $cat_id,
                'toSingleId' => $toSingleId,
            ];
            $assignRecord = (new HouseCommunityWorkOrderService())->getWorkOrderToWorker($workOrderParam);
            if (isset($assignRecord['errCode']) && intval($assignRecord['errCode']) == WorkOrderConst::WORK_ORDER_ERR_CODE_NOT_BUILDER) {
                $telephone = isset($assignRecord['telephone']) ? trim($assignRecord['telephone']) : '';
                $msg = '未设置楼栋管家，不能提交工单请先联系物业！';
                $telephone && $msg .= "\n【联系电话：{$telephone}】";
                throw new \think\Exception($msg);
            }
            $toWorkId = isset($assignRecord['toWorkId']) ? intval($assignRecord['toWorkId']) : 0;
            $communityAutoToWork = isset($assignRecord['communityAutoToWork']) ? $assignRecord['communityAutoToWork'] : [];
            $communityConfig = isset($assignRecord['communityConfig']) ? $assignRecord['communityConfig'] : [];
            $repairCateConfig = isset($assignRecord['repairCateConfig']) ? $assignRecord['repairCateConfig'] : [];
            $communityAutoToWork && $assign_record['communityAutoToWork'] = $communityAutoToWork;
            $workOrderConfig['village_id'] = $village_id;
            $communityConfig && $workOrderConfig['community_config'] = json_encode($communityConfig, JSON_UNESCAPED_UNICODE);
            $repairCateConfig && $workOrderConfig['repair_cate_config'] = json_encode($repairCateConfig, JSON_UNESCAPED_UNICODE);
            isset($assignRecord['not_report_switch']) && $workOrderConfig['not_report_switch'] = intval($assignRecord['not_report_switch']);
            $workOrderConfig['quality_start_time'] = isset($repairCateConfig['quality_start_time']) ? intval($repairCateConfig['quality_start_time']) : 0;
            $workOrderConfig['quality_end_time'] = isset($repairCateConfig['quality_end_time']) ? intval($repairCateConfig['quality_end_time']) : 0;
        }

        $repairCateScheduling = [];
        if ($toWorkId > 0) {
            $worker_id = $toWorkId;
        } else {
            $worker_ids = $this->getWorkerByTime($cat_id, $repairCateScheduling);
            if (!empty($worker_ids)) {
                $worker_id = $worker_ids[array_rand($worker_ids)];
            } else {
                $worker_id = 0;
            }
        }
        $repairCateScheduling && $assign_record['repairCateScheduling'] = $repairCateScheduling;

        if ($worker_id) {
            // 如果存在接手工作人员
            $event_status = 20;
            $now_role = 3;
        } else {
            $now_role = 2;
            $event_status = 10;
            $is_grab_order = (new HouseVillageService())->checkVillageField($village_id, 'is_grab_order');
            if ($is_grab_order > 0) {
                $event_status = 15;  //抢单
            }
            /*
            $wid = isset($data['worker_id']) ? intval($data['worker_id']) : 0;
            $cate_ids = $this->getRepairCateId($village_id,$wid);
            if($is_grab_order>0 && $cate_ids && in_array($cat_fid,$cate_ids)){
                $event_status = 15;  //抢单
            }
            */
        }

        if (isset($data['login_role']) && $data['login_role']) {
            // 工单类型 0 业主上报工单 1 物业总管理员 2 物业普通管理员  3 小区物业管理员 4小区物业工作人员
            switch ($data['login_role']) {
                case '3':
                    $log_name = 'property_admin_submit';
                    $order_type = 1;
                    $uid = isset($data['worker_id']) ? intval($data['worker_id']) : 0;
                    break;
                case '4':
                    $log_name = 'property_work_submit';
                    $order_type = 2;
                    $uid = isset($data['worker_id']) ? intval($data['worker_id']) : 0;
                    break;
                case '5':
                    $log_name = 'house_admin_submit';
                    $order_type = 3;
                    $uid = isset($data['worker_id']) ? intval($data['worker_id']) : 0;
                    break;
                case '6':
                    $log_name = 'house_work_submit';
                    $order_type = 4;
                    $uid = isset($data['worker_id']) ? intval($data['worker_id']) : 0;
                    break;
                default:
                    throw new \think\Exception("请传正确参数");
                    break;
            }
        } else {
            throw new \think\Exception('缺少登录参数');
        }
        $order_imgs = isset($data['order_imgs']) && $data['order_imgs'] ? $data['order_imgs'] : '';
        if ($order_imgs && is_array($order_imgs)) {
            $order_imgs = implode(';', $order_imgs);
        } elseif ($order_imgs && is_string($order_imgs)) {
            $order_imgs = trim($order_imgs);
        }
        $order_content = isset($data['order_content']) && $data['order_content'] ? $data['order_content'] : '';
        $label_txt = isset($data['label_txt']) ? $data['label_txt'] : '';
        if (is_array($label_txt)) {
            $label_txt = implode(',', $label_txt);
        } elseif (is_string($label_txt)) {
            $label_txt = trim($label_txt);
        }
        $videos = isset($data['videos']) && $data['videos'] ? $data['videos'] : '';
        if (is_array($videos)) {
            $videos = implode(',', $videos);
        } else {
            $videos = trim($videos);
        }
        if (empty($order_imgs) && !$order_content && empty($label_txt)) {
            throw new \think\Exception('图片或工单补充内容或标签必须上传一项');
        }
        if ($jz_open) {
            $uploadVideoMax = WorkOrderConst::WORK_ORDER_UPLOAD_VIDEO_MAX;
            $is_pic_video = isset($configInfo['is_pic_video']) ? intval($configInfo['is_pic_video']) : 0;
            if (is_array($videos) && count($videos) > $uploadVideoMax) {
                throw new \think\Exception('上传视频数量不能超过' . $uploadVideoMax . '个');
            }
            if ($is_pic_video == 1 && empty($videos) && empty($order_imgs)) {
                throw new \think\Exception('图片或者视频必须上传最少1个');
            }
        }
        $now_time = time();
        $order = [
            'village_id' => $village_id,
            'property_id' => $property_id,
            'category_id' => 0,
            'type_id' => 0,
            'cat_fid' => $cat_fid,
            'cat_id' => $cat_id,
            'label_txt' => $label_txt,
            'order_content' => $order_content,
            'order_imgs' => $order_imgs,
            'order_type' => $order_type,
            'uid' => $uid,
            'phone' => isset($data['phone']) && $data['phone'] ? $data['phone'] : '',
            'name' => isset($data['name']) && $data['name'] ? $data['name'] : '',
            'now_role' => $now_role,
            'worker_type' => 0,
            'worker_id' => $worker_id,
            'event_status' => $event_status,
            'address_type' => $address_type,
            'address_id' => $address_id,
            'public_id' => $public_id,
            'single_id' => $single_id,
            'floor_id' => $floor_id,
            'layer_id' => $layer_id,
            'room_id' => $room_id,
            'address_txt' => $address_txt,
            'add_time' => $now_time,
        ];
        $videos && $order['videos'] = $videos;
        $configCustomizationService = new ConfigCustomizationService();
        $sqAeondeLightJiangsuCustomized_judge = $configCustomizationService->getSqAeondeLightJiangsuCustomized();
        if ($sqAeondeLightJiangsuCustomized_judge && isset($data['wisdomqrcode_id']) && $data['wisdomqrcode_id'] > 0) {
            $order['qrcode_record_id'] = $data['qrcode_record_id'];
            $order['wisdomqrcode_id'] = $data['wisdomqrcode_id'];
        }
        $order['group_id'] = $this->getGroupId($worker_id);
        try {
            $order_id = $this->addRepairOrder($order);
            if ($order_id) {
                $logData = [];
                $logData['order_id'] = $order_id;
                $logData['log_name'] = $log_name;
                $logData['village_id'] = $village_id;
                $logData['property_id'] = $property_id;
                $logData['log_operator'] = $order['name'] ? $order['name'] : '';
                $logData['log_phone'] = $order['phone'] ? $order['phone'] : '';
                $logData['operator_type'] = 10;
                $logData['operator_id'] = $order['uid'] ? $order['uid'] : 0;
                $logData['log_uid'] = 0;
                $logData['log_content'] = $order_content;
                $logData['log_imgs'] = $order['order_imgs'];
                $logData['add_time'] = $now_time;
                $db_house_new_repair_works_order_log = new HouseNewRepairWorksOrderLog();
                $where_count = [];
                $where_count[] = ['order_id', '=', $order_id];
                $where_count[] = ['log_name', '=', strval($log_name)];
                $log_num = $db_house_new_repair_works_order_log->getCount($where_count);
                if (!$log_num) {
                    $log_num = 1;
                } else {
                    $log_num = intval($log_num) + 1;
                }
                $logData['log_num'] = $log_num;
                if ($sqAeondeLightJiangsuCustomized_judge && isset($data['wisdomqrcode_id']) && $data['wisdomqrcode_id'] > 0) {
                    $logData['wisdomqrcode_id'] = $data['wisdomqrcode_id'];
                }
                $otherData = '';
                if ($worker_id < 1) {
                    $otherData = array('opt_type' => 'order_worker_submit', 'wid' => 0, 'village_id' => $village_id);
                }
                $this->addRepairLog($logData, $otherData);
                if ($worker_id) {
                    // 查询处理人信息
                    $db_house_worker = new HouseWorker();
                    $where_house_work = [];
                    $where_house_work[] = ['wid', '=', $worker_id];
                    $house_work = $db_house_worker->get_one($where_house_work, 'wid,name,phone');
                    $logData = [];
                    $logData['order_id'] = $order_id;
                    $logData['log_name'] = 'house_auto_assign';
                    $logData['log_operator'] = $house_work['name'] ? $house_work['name'] : '';
                    $logData['log_phone'] = $house_work['phone'] ? $house_work['phone'] : '';
                    $logData['operator_type'] = 10;
                    $logData['operator_id'] = 0;
                    $logData['log_uid'] = $house_work['wid'] ? $house_work['wid'] : 0;
                    $logData['log_content'] = '';
                    $logData['log_imgs'] = '';
                    $logData['add_time'] = $now_time;
                    $logData['village_id'] = $village_id;
                    $logData['property_id'] = $property_id;
                    $otherData = array('opt_type' => 'assign_to_house_worker', 'wid' => $worker_id, 'village_id' => $village_id);
                    $this->addRepairLog($logData, $otherData);
                    if ($jz_open) {
                        if (isset($communityConfig['not_timely_report_switch'])) {
                            $not_timely_report_switch = intval($communityConfig['not_timely_report_switch']);
                            $isPublic = false;
                            if (isset($order['address_type']) && $order['address_type'] == 'public') {
                                $isPublic = true;
                            } elseif (isset($order['public_id']) && intval($order['public_id']) > 0) {
                                $isPublic = true;
                            }
                            if ($not_timely_report_switch == 0 && $isPublic) {
                                $noticeArr = [
                                    'village_id' => $village_id,
                                    'works_order_id' => $order_id,
                                ];
                                (new HouseCommunityHandleNoticeService())->tryHandlePublicToReportBox($noticeArr);
                            }
                        }
                        $assign_record && $workOrderConfig['assign_record'] = json_encode($assign_record, JSON_UNESCAPED_UNICODE);
                        $workOrderConfig['works_order_id'] = $order_id;
                        $workOrderConfig['add_time'] = $now_time;
                        $serviceHouseCommunityWorkOrder = new HouseCommunityWorkOrderService();
                        $configId = $serviceHouseCommunityWorkOrder->addHouseCommunityWorkOrderConfig($workOrderConfig);
                        if ($worker_id) {
                            $workRecord = [
                                'worker_id' => $worker_id,
                                'village_id' => $village_id,
                                'property_id' => $property_id,
                                'works_order_id' => $order_id,
                                'latest_receive_time' => $now_time
                            ];
                            $serviceHouseCommunityWorkOrder->handleWorkOrderWorkRecord($workRecord);
                        }
                        if ($configId && !empty($communityConfig)) {
                            $workName = isset($house_work['name']) && $house_work['name'] ? trim($house_work['name']) : '';
                            $workPhone = isset($house_work['phone']) && $house_work['phone'] ? trim($house_work['phone']) : '';
                            $handleParams = [
                                'configId' => $configId,
                                'village_id' => $village_id,
                                'communityConfig' => $communityConfig,
                                'nowTime' => $now_time,
                                'worker_id' => $worker_id,
                                'workName' => $workName,
                                'workPhone' => $workPhone,
                                'works_order_id' => $order_id,
                            ];
                            $serviceHouseCommunityWorkOrder->handleIncreaseWorkOrderConfig($handleParams);
                        }
                    }
                    //当提交的工单有指派时
                    if ($order['worker_id'] > 0) {
                        //针对工作人员提交的工单有指派时 给提交人发送通知
                        if ($order['order_type'] == 4) {
                            $this->newRepairSendMsg(11, $order_id, $uid, $property_id);
                        }
                        //给工作人员发送通知
                        $this->newRepairSendMsg(12, $order_id, 0, $property_id);
                    } else {
                        //todo 开启抢单模式
                        $this->grabOrder($village_id, $order_id, $cat_id);
                    }
                }
            }
        } catch (\Exception $e) {
            fdump_api(['file' => $e->getFile(), 'code' => $e->getCode(), 'mes' => $e->getMessage(), 'line' => $e->getLine()], '$1tryCatchErrLog', 1);
            throw new \think\Exception($e->getMessage());
        }
        $add_msg = [];
        $add_msg['order_id'] = $order_id;
        $add_msg['status'] = 'todo';
        return $add_msg;
    }


    /**
     * Notes: 移动管理端获取工单详情
     * @param $order_id
     * @param $worker_id
     * @param $login_role
     * @param bool $is_arr
     * @param bool $is_word
     * @param array $params
     * @return array|\think\Model|null
     * @throws \think\Exception
     * @author: wanzy
     * @date_time: 2021/8/18 11:57
     */
    public function workGetOrder($order_id, $worker_id, $login_role, $is_arr = false, $is_word = false, $params = [])
    {
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $db_house_new_repair_works_order_log = new HouseNewRepairWorksOrderLog();
        $nowVillageId = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $where_order = [];
        $where_order[] = ['order_id', '=', $order_id];
        $order_detail = $db_house_new_repair_works_order->getOne($where_order);
        if (empty($order_detail)) {
            throw new \think\Exception('对应工单不存在');
        } else {
            $order_detail = $order_detail->toArray();
        }
        !$nowVillageId && $nowVillageId = isset($order_detail['now_handle_village_id']) ? intval($order_detail['now_handle_village_id']) : 0;
        !$nowVillageId && $nowVillageId = isset($order_detail['village_id']) ? intval($order_detail['village_id']) : 0;
        if ($login_role != HouseWorkConst::MANAGE_APP_VILLAGE_WORK && $order_detail['event_status'] == 15) {
            $order_detail['event_status'] = 10;
        }
        $db_house_new_repair_subject = new HouseNewRepairSubject();
        $db_house_new_repair_cate = new HouseNewRepairCate();
        if (isset($order_detail['cat_id']) && $order_detail['cat_id']) {
            // 二级分类
            $whereCat = [];
            $whereCat[] = ['id', '=', $order_detail['cat_id']];
            $cat_info = $db_house_new_repair_cate->getOne($whereCat, 'id, subject_id, cate_name, parent_id');
            if ($cat_info && isset($cat_info['cate_name'])) {
                $order_detail['cat_id_txt'] = $cat_info['cate_name'];
            }
            // 一级分类
            if (isset($order_detail['cat_fid']) && $order_detail['cat_fid']) {
                $whereCatFid = [];
                $whereCatFid[] = ['id', '=', $order_detail['cat_fid']];
                $cat_parent_info = $db_house_new_repair_cate->getOne($whereCatFid, 'id, subject_id, cate_name');
            } elseif (isset($cat_info['parent_id']) && $cat_info['parent_id']) {
                $whereCatFid = [];
                $whereCatFid[] = ['id', '=', $cat_info['parent_id']];
                $cat_parent_info = $db_house_new_repair_cate->getOne($whereCatFid, 'id, subject_id, cate_name');
            }
            if ($cat_parent_info && isset($cat_parent_info['cate_name'])) {
                $order_detail['cat_fid_txt'] = $cat_parent_info['cate_name'];
            }

            $other_cate_id = isset($order_detail['other_cate_id']) ? intval($order_detail['other_cate_id']) : 0;
            if ($other_cate_id > 0) {
                // 其他小区二级分类
                $whereOtherCate = [];
                $whereOtherCate[] = ['id', '=', $other_cate_id];
                $other_cat_info = $db_house_new_repair_cate->getOne($whereOtherCate, 'id, subject_id, cate_name, parent_id');
                if ($other_cat_info && isset($other_cat_info['cate_name'])) {
                    $order_detail['other_cate_id_txt'] = $other_cat_info['cate_name'];
                }
                // 其他小区一级分类
                $other_cate_fid = isset($order_detail['other_cate_fid']) ? intval($order_detail['other_cate_fid']) : 0;
                if ($other_cate_fid > 0) {
                    $whereOtherCateFid = [];
                    $whereOtherCateFid[] = ['id', '=', $other_cate_fid];
                    $other_cat_parent_info = $db_house_new_repair_cate->getOne($whereOtherCateFid, 'id, subject_id, cate_name');
                } elseif (isset($other_cat_info['parent_id']) && $other_cat_info['parent_id']) {
                    $whereOtherCateFid = [];
                    $whereOtherCateFid[] = ['id', '=', $other_cat_info['parent_id']];
                    $other_cat_parent_info = $db_house_new_repair_cate->getOne($whereOtherCateFid, 'id, subject_id, cate_name');
                }
                if ($other_cat_parent_info && isset($other_cat_parent_info['cate_name'])) {
                    $order_detail['other_cate_fid_txt'] = $other_cat_parent_info['cate_name'];
                }
            }

            // 工单类别
            $type_info = [];
            if (isset($order_detail['type_id']) && $order_detail['type_id']) {
                $whereType = [];
                $whereType[] = ['id', '=', $order_detail['type_id']];
                $type_info = $db_house_new_repair_subject->getOne($whereType, 'id, subject_name, parent_id');
            } elseif (isset($cat_parent_info['subject_id']) && $cat_parent_info['subject_id']) {
                $whereType = [];
                $whereType[] = ['id', '=', $cat_parent_info['subject_id']];
                $type_info = $db_house_new_repair_subject->getOne($whereType, 'id, subject_name, parent_id');
            }
            if ($type_info && isset($type_info['subject_name'])) {
                $order_detail['type_id_txt'] = $type_info['subject_name'];
            }
            // 工单类目
            $category_info = [];
            if (isset($order_detail['category_id']) && $order_detail['category_id']) {
                $whereCategory = [];
                $whereCategory[] = ['id', '=', $order_detail['category_id']];
                $category_info = $db_house_new_repair_subject->getOne($whereCategory, 'id, subject_name');
            } elseif (isset($type_info['parent_id']) && $type_info['parent_id']) {
                $whereCategory = [];
                $whereCategory[] = ['id', '=', $type_info['parent_id']];
                $category_info = $db_house_new_repair_subject->getOne($whereCategory, 'id, subject_name');
            }
            if ($category_info && isset($category_info['subject_name'])) {
                $order_detail['category_id_txt'] = $category_info['subject_name'];
            }
        }
        $dbHouseWorker = new HouseWorker();
        $is_post = false; // 是否是提交工单人员
        $is_worker = false; // 是否是工单当前处理人员
        $is_through = false; //是否经过
        $is_admin = false; //管理身份
        $is_house_admin = false; //小区物业管理人员身份
        $assign_type = ''; //指派类型
        $reply_type = ''; //回复类型
        $where_log_through = [];
        $where_log_through[] = ['order_id', '=', $order_id];
        // 最后一次非本人操作记录
        $log_last = [];
        switch ($login_role) {
            case HouseWorkConst::MANAGE_APP_VILLAGE_WORK:
                $order_type = 4;
                // 小区物业工作人员
                $where_log_through[] = ['operator_type', '>=', 30];
                $where_log_through[] = ['operator_id', '=', $worker_id];
                if (isset($order_detail['order_type']) && $order_detail['order_type'] == $order_type && isset($order_detail['uid']) && $worker_id == $order_detail['uid'] && $order_detail['event_status'] != 15) {
                    $is_post = true;
                    $is_through = true;
                }
                if (isset($order_detail['worker_type']) && $order_detail['worker_type'] == 0 && isset($order_detail['worker_id']) && $worker_id == $order_detail['worker_id']) {
                    $is_worker = true;
                }
                if ($is_worker) {
                    // 如果是工作人员 查询下 上次操作记录
                    // 查询下 最后一次非本人操作
                    $where_log_last = [];
                    $where_log_last[] = ['order_id', '=', $order_id];
                    $where_log_last[] = ['operator_id', '<>', $worker_id];
                    $where_log_last[] = ['operator_type', '>=', 30];
                    $where_log_last[] = ['operator_type', '<', 40];
                    $where_log_last[] = ['log_name', 'not in', ['refuse_project_nums_report', 'refuse_order_nums_report', 'not_over_stars_report', 'over_timely_report', 'over_not_processed_report_time', 'public_report']];
                    $log_last = $db_house_new_repair_works_order_log->getOne($where_log_last, true);
                    if (empty($log_last)) {
                        $log_last = [];
                    } else {
                        $log_last = $log_last->toArray();
                    }
                }
                break;
            case HouseWorkConst::MANAGE_APP_VILLAGE_ADMIN:
                $order_type = 3;
                // 小区物业管理人员
                // 这个身份只能是提交者了 暂时
                if (isset($order_detail['order_type']) && $order_detail['order_type'] == $order_type && isset($order_detail['uid']) && $worker_id == $order_detail['uid']) {
                    $is_post = true;
                    $is_through = true;
                }
                $where_log_through[] = ['operator_type', '>=', 20];
                $where_log_through[] = ['operator_type', '<', 30];
                $where_log_through[] = ['operator_id', '=', $worker_id];

                $is_admin = true; //管理身份
                $is_house_admin = true; //小区物业管理人员身份
                $assign_type = 'house_admin_assign'; //指派类型
                $reply_type = 'house_admin_reply'; //回复类型
                break;
            case HouseWorkConst::MANAGE_APP_PROPERTY_USER:
                $order_type = 2;
                // 物业普通管理员
                // 这个身份只能是提交者了 暂时
                if (isset($order_detail['order_type']) && $order_detail['order_type'] == $order_type && isset($order_detail['uid']) && $worker_id == $order_detail['uid']) {
                    $is_post = true;
                    $is_through = true;
                }
                $where_log_through[] = ['operator_type', '>=', 20];
                $where_log_through[] = ['operator_type', '<', 30];
                $where_log_through[] = ['operator_id', '=', $worker_id];
                $is_admin = true; //管理身份
                $assign_type = 'property_work_assign'; //指派类型
                $reply_type = 'property_work_reply'; //回复类型
                break;
            case HouseWorkConst::MANAGE_APP_PROPERTY_ADMIN:
                $order_type = 1;
                // 物业总管理员
                // 这个身份只能是提交者了 暂时
                if (isset($order_detail['order_type']) && $order_detail['order_type'] == $order_type && isset($order_detail['uid']) && $worker_id == $order_detail['uid']) {
                    $is_post = true;
                    $is_through = true;
                }
                $where_log_through[] = ['operator_type', '>=', 20];
                $where_log_through[] = ['operator_type', '<', 30];
                $where_log_through[] = ['operator_id', '=', $worker_id];
                $is_admin = true; //管理身份
                $assign_type = 'property_admin_assign'; //指派类型
                $reply_type = 'property_admin_reply'; //回复类型
                break;
            default:
                throw new \think\Exception("您当前账号不能查看该工单信息");
                break;
        }
        if (!$is_through) {
            $log_through = $db_house_new_repair_works_order_log->getOne($where_log_through, 'log_id');
            if ($log_through && isset($log_through['log_id']) && $log_through['log_id']) {
                $is_through = true;
            }
        }
        if (!$is_post && !$is_worker && !$is_through && !$is_admin && !($order_detail['event_status'] == 15)) {
            throw new \think\Exception('您当前账号不能查看该工单信息');
        }

        if ($order_detail['event_status'] == 15) {
            $this->checkGrabOrder(1, $order_detail['village_id'], $worker_id, $order_detail);
        }

        if (isset($order_detail['event_status']) && isset($this->order_work_status_txt[$order_detail['event_status']])) {
            $order_detail['event_status_txt'] = $this->order_work_status_txt[$order_detail['event_status']];
        } else {
            $order_detail['event_status_txt'] = '';
        }
        if (isset($order_detail['event_status']) && isset($this->order_status_color[$order_detail['event_status']])) {
            $order_detail['event_status_color'] = $this->order_status_color[$order_detail['event_status']];
        } else {
            $order_detail['event_status_color'] = '';
        }
        $order_detail['is_post'] = $is_post;
        $order_detail['is_worker'] = $is_worker;
        $order_detail['is_through'] = $is_through;
        $order_detail['is_house_admin'] = $is_house_admin;
        $order_detail['is_admin'] = $is_admin;
        // 获取工单对应处理记录 按照时间顺序倒叙
        $log = [];
        $order_log = 'add_time DESC';
        $where_log = [];
        $where_log[] = ['order_id', '=', $order_id];
        $log_new = $db_house_new_repair_works_order_log->getOne($where_log, true);
        if (!empty($log_new)) {
            $children = [];
            if (isset($log_new['log_operator']) && $log_new['log_operator']) {
                $children[] = [
                    'title' => '当前操作人员',
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
                'title' => '操作记录',
                'type' => 2,
                'content' => '',
                'content_color' => '',
                'children' => $children,
                'imgs' => [],
                'videos' => [],
            ];
        }
        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        $modify_cate_num = isset($order_detail['modify_cate_num']) ? intval($order_detail['modify_cate_num']) : 0;
        $auth_edit_cate = 0;
        if ($jz_open && $modify_cate_num == 0) {
            $auth_edit_cate = 1;
        } else {
            $auth_edit_cate = 0;
        }
        $order_detail['jz_open'] = $jz_open ? 1 : 0;
        $order_detail['auth_edit_cate'] = $auth_edit_cate;

        $title_status = '工单状态';
        if (isset($order_detail['event_status']) && $order_detail['event_status']) {
            // 10 未指派 20 已指派 30 处理中 40已办结 50 已撤回 60已关闭 70已评价
            if ($order_detail['event_status'] >= 10 && $order_detail['event_status'] < 20) {
                // 工单待处理
                $order_detail['change_status'] = [];
                if ($is_word) {
                    $order_detail['change_status_word'] = [];
                }
                if ($is_house_admin) {
                    // 小区物业管理人员
                    $order_detail['change_status'] = [
                        ['log' => $assign_type, 'type' => 'choose_work', 'name' => '指派'],
                        ['log' => $reply_type, 'type' => '', 'name' => '回复']
                    ];
                    if ($is_word) {
                        $order_detail['change_status_word'] = [$assign_type, $reply_type];
                    }
                } elseif ($is_admin && !$is_house_admin) {
                    //  物业总管理人员及物业普通管理员
                    $order_detail['change_status'] = [
                        ['log' => $assign_type, 'type' => 'choose_work', 'name' => '指派'],
                        ['log' => $reply_type, 'type' => '', 'name' => '回复'],
                    ];
                    if ($is_word) {
                        $order_detail['change_status_word'] = [$assign_type, $reply_type];
                    }
                }
                if ($is_post) {
                    // 也是上报人员
                    $order_detail['change_status'][] = ['log' => 'recall', 'type' => '', 'name' => '撤回'];
                    $order_detail['change_status'][] = ['log' => 'closed', 'type' => '', 'name' => '关闭'];
                    if ($is_word) {
                        $order_detail['change_status_word'][] = 'recall';
                        $order_detail['change_status_word'][] = 'closed';
                    }
                }
            } elseif ($order_detail['event_status'] >= 20 && $order_detail['event_status'] < 40) {
                // 已指派 处理中
                $order_detail['change_status'] = [];
                if ($is_word) {
                    $change_status_word = [];
                }
                if ($is_worker) {
                    $is_developer = 0;
                    $is_constructor = 0;
                    $change_status = [];
                    if ($jz_open) {
                        $dbHouseWorker = new HouseWorker();
                        $whereWork = [];
                        $whereWork[] = ['wid', '=', $worker_id];
                        $workerInfo = $dbHouseWorker->get_one($whereWork, 'wid, village_id, is_constructor');
                        $worker_village_id = isset($workerInfo['village_id']) ? intval($workerInfo['village_id']) : 0;
                        $is_constructor = isset($workerInfo['is_constructor']) ? intval($workerInfo['is_constructor']) : 0;
                        $developer = (new HouseCommunityAboutService())->getVillageFacilitiesInfo($worker_village_id, 'developer');
                        if (isset($developer['is_virtual']) && intval($developer['is_virtual']) == 1) {
                            $is_developer = 1;
                        }
                    }
                    $work_completed_text = "结单";
                    $work_reject_text = "拒绝";
                    if ($jz_open && $auth_edit_cate == 1 && intval($order_detail['event_status']) <= 23) {
                        $change_status[] = ['log' => 'work_change_cate', 'type' => 'changeCate', 'name' => '更改分类'];
                        if ($is_word) {
                            $change_status_word[] = 'work_change_cate';
                        }
                    }
                    $pay_order_id = isset($order_detail['pay_order_id']) ? intval($order_detail['pay_order_id']) : 0;
                    $pay_time = isset($order_detail['pay_time']) ? intval($order_detail['pay_time']) : 0;
                    $noHasLogName = $jz_open && ($order_detail['event_status'] == 35 || ($order_detail['event_status'] == 36 && $pay_order_id && !$pay_time));
                    $hasWorkPushScheme = 0;
                    if (!$jz_open) {
                        $work_follow_up_text = '跟进';
                        $change_status[] = ['log' => 'work_follow_up', 'type' => '', 'name' => $work_follow_up_text];
                        if ($is_word) {
                            $change_status_word[] = 'work_follow_up';
                        }
                    } elseif (!$pay_order_id || $order_detail['event_status'] == 37) {
                        $subject_info = $db_house_new_repair_cate->getOne(['id' => $order_detail['cat_id']]);
                        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
                        if ($jz_open) {
                            $single_id = isset($order_detail['single_id']) && intval($order_detail['single_id']) > 0 ? intval($order_detail['single_id']) : 0;
                            $room_id = isset($order_detail['room_id']) && intval($order_detail['room_id']) > 0 ? intval($order_detail['room_id']) : 0;
                            $judgeParams = [
                                'village_id' => $order_detail['village_id'],
                                'repair_cate_id' => $order_detail['cat_id'],
                                'jz_open' => $jz_open,
                                'single_id' => $single_id,
                                'room_id' => $room_id,
                            ];
                            $hasFee = (new HouseCommunityQualityService())->judgeRepairCateQualityFee($judgeParams);
                        } else {
                            $hasFee = false;
                        }
                        fdump_api(['$order_detail' => $order_detail, '$subject_info' => $subject_info, '$hasFee' => $hasFee], '$hasFee');
                        if ($subject_info['charge_type'] == 1 && !$hasFee) {
                            $hasWorkPushScheme = 1;
                            $work_follow_up_text = '推送方案';
                            $change_status[] = ['log' => 'work_push_scheme', 'type' => '', 'name' => $work_follow_up_text];
                            if ($is_word) {
                                $change_status_word[] = 'work_push_scheme';
                            }
                        }
                    }
                    if ($order_detail['event_status'] != 37 && !$noHasLogName) {
                        // 用户拒绝工单不允许结单和拒绝
                        if (!$hasWorkPushScheme) {
                            $change_status[] = ['log' => 'work_completed', 'type' => '', 'name' => $work_completed_text];
                            if ($is_word) {
                                // 用户拒绝工单不允许结单和拒绝
                                $change_status_word[] = 'work_completed';
                            }
                        }
                        if (isset($log_new['operator_id']) && $log_new['operator_id']) {
                            $lastWorkInfo = (new HouseWorker())->getOne(['wid' => $log_new['operator_id']], 'wid, village_id');
                            if (isset($lastWorkInfo['village_id']) && $nowVillageId && $lastWorkInfo['village_id'] == $nowVillageId) {
                                $change_status[] = ['log' => 'work_reject', 'type' => '', 'name' => $work_reject_text];
                                if ($is_word) {
                                    $change_status_word[] = 'work_reject';
                                }
                            }
                        }
                    }

                    if ($is_developer == 1 && $is_constructor != 1) {
                        array_unshift($change_status, ['log' => 'work_change_constructor', 'type' => 'choose_work', 'name' => '转单施工方']);
                        $change_status = array_values($change_status);
                        if ($is_word) {
                            $change_status_word[] = 'work_change_constructor';
                        }
                    }
                    if ($is_constructor != 1 && (!$jz_open || !$noHasLogName)) {
                        $change_status[] = ['log' => 'work_change', 'type' => 'choose_work', 'name' => '转单'];
                        if ($is_word) {
                            $change_status_word[] = 'work_change';
                        }
                    }
                    if ($is_developer == 1 && $is_constructor != 1) {
                        $change_status[] = ['log' => 'reject_to_developer', 'type' => '', 'name' => '退回（楼栋管家）'];
                        if ($is_word) {
                            $change_status_word[] = 'reject_to_developer';
                        }
                    }
                    if ($jz_open && !$noHasLogName) {
                        $logJzArr = [];
                        // 未禁止转单到 供暖站或者开发商
                        $now_handle_village_id = isset($order_detail['now_handle_village_id']) ? intval($order_detail['now_handle_village_id']) : 0;
                        !$now_handle_village_id && $now_handle_village_id = isset($order_detail['village_id']) ? intval($order_detail['village_id']) : 0;
                        $facilitiesList = (new HouseCommunityAboutService())->getVillageBindFacilitiesList($now_handle_village_id, ['order_id' => $order_id]);
                        fdump_api($facilitiesList, '$facilitiesList');
                        if (!empty($facilitiesList)) {
                            foreach ($facilitiesList as $itemVal) {
                                $logKey1 = 'to_' . $itemVal['bind_facilities_type'];
                                $name = $itemVal['to_village_name'];
                                $change_status[] = ['log' => $logKey1, 'type' => '', 'name' => $name];
                                if ($is_word) {
                                    $change_status_word[] = $logKey1;
                                }
                                if (isset($itemVal['is_contact_available']) && isset($itemVal['auto_contact_available']) && intval($itemVal['is_contact_available']) == 0 && intval($itemVal['auto_contact_available']) != 1) {
                                    $villageInfo = isset($itemVal['villageInfo']) ? $itemVal['villageInfo'] : [];
                                    $village_name = isset($villageInfo['village_name']) ? trim($villageInfo['village_name']) : '';
                                    $property_phone = isset($villageInfo['property_phone']) && $villageInfo['property_phone'] ? str_replace(' ', '；', $villageInfo['property_phone']) : '';
                                    $property_address = isset($villageInfo['property_address']) ? trim($villageInfo['property_address']) : '';
                                    $fill_content = "请联系{$village_name}，联系方式：{$property_phone}";
                                    $property_address && $fill_content .= "\n地址：{$property_address}";
                                    $logJzArr[] = [
                                        'log' => 'has_contact_' . $itemVal['bind_facilities_type'],
                                        'name' => isset($itemVal['contact_available_log']) && $itemVal['contact_available_log'] ? $itemVal['contact_available_log'] : '获取联系方式',
                                        'type' => 'fill_content',
                                        'fill_content' => $fill_content,
                                    ];
                                }
                            }
                        } else {
                            $db_house_community_about = new HouseCommunityAbout();
                            $whereAbout[] = ['village_id', '=', $now_handle_village_id];
                            $whereAbout[] = ['is_virtual', '=', 1];
                            $aboutInfo = $db_house_community_about->getOne($whereAbout, 'facilities_type, is_contact_available');
                            $is_contact_available = isset($aboutInfo['is_contact_available']) ? intval($aboutInfo['is_contact_available']) : 0;
                            $facilities_type = isset($aboutInfo['facilities_type']) ? $aboutInfo['facilities_type'] : '';
                            if ($is_contact_available == 0 && !empty($aboutInfo) && $facilities_type != 'supply_station') {
                                // 供暖站不支持手动获取联系方式
                                $house_community_config = new HouseCommunityConfig();
                                $whereConfig = [];
                                $whereConfig[] = ['village_id', '=', $now_handle_village_id];
                                $configInfo = $house_community_config->getOne($whereConfig, 'id,auto_contact_available');
                                $auto_contact_available = isset($configInfo['auto_contact_available']) ? intval($configInfo['auto_contact_available']) : 0;
                                if ($auto_contact_available != 1) {
                                    $db_house_village = new HouseVillage();
                                    $villageInfo = $db_house_village->getOne($now_handle_village_id, 'village_id, village_name, property_phone, property_address');
                                    $village_name = isset($villageInfo['village_name']) ? trim($villageInfo['village_name']) : '';
                                    $property_phone = isset($villageInfo['property_phone']) && $villageInfo['property_phone'] ? str_replace(' ', '；', $villageInfo['property_phone']) : '';
                                    $property_address = isset($villageInfo['property_address']) ? trim($villageInfo['property_address']) : '';
                                    $fill_content = "请联系{$village_name}，联系方式：{$property_phone}";
                                    $property_address && $fill_content .= "\n地址：{$property_address}";
                                    $logJzArr[] = [
                                        'log' => 'has_contact_' . $aboutInfo['facilities_type'],
                                        'name' => "获取联系($village_name)",
                                        'type' => 'fill_content',
                                        'fill_content' => $fill_content,
                                    ];
                                }
                            }
                        }
                        // 判断下 是否可以获取对应联系方式
                        if (!empty($logJzArr)) {
                            foreach ($logJzArr as $itemJz) {
                                $change_status[] = ['log' => $itemJz['log'], 'type' => $itemJz['type'], 'name' => $itemJz['name'], 'fill_content' => $itemJz['fill_content']];
                                if ($is_word) {
                                    $change_status_word[] = $itemJz['log'];
                                }
                            }
                        }
                    }
                    $log_last_name = isset($log_last['log_name']) && trim($log_last['log_name']) ? trim($log_last['log_name']) : '';
                    if ($log_last_name == 'agree_constructor_project' && $is_developer == 1 && $is_constructor == 1) {
                        //                        $work_follow_up_text = "施工方跟进";
                        $work_completed_text = "施工方结单";
                        $work_reject_text = "施工方拒绝";
                        $change_status = [];
                        if ($is_word) {
                            $change_status_word = [];
                        }
                        //                        $change_status[] = ['log' => 'work_follow_up','type'=>'', 'name' => $work_follow_up_text];
                        //                        if ($is_word) {
                        //                            $change_status_word[] = 'work_follow_up';
                        //                        }
                        if ($order_detail['event_status'] != 37) {
                            // 用户拒绝工单不允许结单和拒绝
                            $change_status[] = ['log' => 'work_completed', 'type' => '', 'name' => $work_completed_text];
                            $change_status[] = ['log' => 'work_reject', 'type' => '', 'name' => $work_reject_text];
                            if ($is_word) {
                                // 用户拒绝工单不允许结单和拒绝
                                $change_status_word[] = 'work_completed';
                                $change_status_word[] = 'work_reject';
                            }
                        }
                    } elseif ($log_last_name == 'constructor_reject_change' || $log_last_name == 'constructor_reject_center') {
                        $change_status = [];
                        $change_status[] = ['log' => 'agree_constructor_project', 'type' => '', 'name' => '同意施工方案'];
                        $change_status[] = ['log' => 'reject_constructor_project', 'type' => '', 'name' => '拒绝施工方案'];
                        $change_status[] = ['log' => 'reject_to_developer', 'type' => '', 'name' => '退回（楼栋管家）'];
                        if ($is_word) {
                            $change_status_word = [];
                            $change_status_word[] = 'agree_constructor_project';
                            $change_status_word[] = 'reject_constructor_project';
                            $change_status_word[] = 'reject_to_developer';
                        }
                    } elseif (in_array($log_last_name, ['work_change', 'work_change_constructor', 'reject_constructor_project'])) {
                        // 被人转单可以拒绝回转单人员
                        if ($is_developer == 1 && $is_constructor == 1) {
                            //                            array_unshift($change_status, ['log' => 'constructor_reject_change', 'type' => '', 'name' => '提供施工方案']);
                            //                            $change_status = array_values($change_status);
                            $change_status = [];
                            $change_status[] = ['log' => 'constructor_reject_change', 'type' => '', 'name' => '提供施工方案'];
                            if ($is_word) {
                                $change_status_word = [];
                                $change_status_word[] = 'constructor_reject_change';
                            }
                            $change_status[] = ['log' => 'reject_change_constructor', 'name' => '退回开发商', 'type' => ''];
                            if ($is_word) {
                                $change_status_word[] = 'reject_change_constructor';
                            }
                        } else {
                            $change_status[] = ['log' => 'work_reject_change', 'name' => '驳回给转单人员', 'type' => ''];
                            if ($is_word) {
                                $change_status_word[] = 'work_reject_change';
                            }
                        }
                    } elseif ($log_last_name == 'center_assign_work') {
                        // 被人转单可以拒绝回转单人员
                        //                        if ($is_developer == 1 && $is_constructor == 1) {
                        //                            $change_status[] = ['log' => 'constructor_reject_center', 'name' => '提供施工方案给处理中心', 'type' => ''];
                        //                            if ($is_word) {
                        //                                $change_status_word[] = 'constructor_reject_center';
                        //                            }
                        //                        } else {
                        //                            $change_status[] = ['log' => 'work_reject_center', 'name' => '驳回给处理中心', 'type' => ''];
                        //                            if ($is_word) {
                        //                                $change_status_word[] = 'work_reject_center';
                        //                            }
                        //                        }
                        $change_status[] = ['log' => 'work_reject_center', 'name' => '驳回给处理中心', 'type' => ''];
                        if ($is_word) {
                            $change_status_word[] = 'work_reject_center';
                        }
                    }
                    if (in_array($order_detail['event_status'], [28, 29])) {
                        $change_status = [];
                        if ($is_word) {
                            $change_status_word = [];
                        }
                    }
                    $order_detail['change_status'] = $change_status;
                    if ($is_word) {
                        $order_detail['change_status_word'] = $change_status_word;
                    }
                }
                if ($is_post) {
                    // 也是上报人员
                    $order_detail['change_status'][] = ['log' => 'recall', 'type' => '', 'name' => '撤回'];
                    $order_detail['change_status'][] = ['log' => 'closed', 'type' => '', 'name' => '关闭'];
                    if ($is_word) {
                        $order_detail['change_status_word'][] = 'recall';
                        $order_detail['change_status_word'][] = 'closed';
                    }
                }
                if (!empty($order_detail['rule_id'])) {
                    $db_house_new_charge_rule = new HouseNewChargeRule();
                    $field = 'r.charge_name,p.name';
                    $rule_info = $db_house_new_charge_rule->getFind(['r.id' => $order_detail['rule_id']], $field);
                    $rule_info['pay_type'] = $order_detail['pay_type'];
                    if ($order_detail['pay_type'] == 2) {
                        $rule_info['offline_pay_type'] = $order_detail['offline_pay_type'];
                    } else {
                        $rule_info['offline_pay_type'] = 0;
                    }
                    $order_detail['rule_info'] = $rule_info;
                }
            } elseif ($order_detail['event_status'] >= 40 && $order_detail['event_status'] < 50) {
                // 已办结
                $order_detail['change_status'] = [];
                if ($is_word) {
                    $order_detail['change_status_word'] = [];
                }
                if (!$jz_open && $is_worker) {
                    $order_detail['change_status'] = [
                        ['log' => 'work_completed', 'type' => '', 'name' => '再次结单']
                    ];
                    if ($is_word) {
                        $order_detail['change_status_word'] = ['work_completed'];
                    }
                }
                if ($is_post) {
                    // 也是上报人员
                    //$order_detail['change_status'][] = ['log' => 'evaluate','type'=>'evaluate', 'name' => '评价'];
                    if (!$jz_open) {
                        $order_detail['change_status'][] = ['log' => 'reopen', 'type' => '', 'name' => '重新打开'];
                        $order_detail['change_status'][] = ['log' => 'recall', 'type' => '', 'name' => '撤回'];
                    }
                    $order_detail['change_status'][] = ['log' => 'closed', 'type' => '', 'name' => '关闭'];
                    if ($is_word) {
                        $order_detail['change_status_word'][] = 'evaluate';
                        if (!$jz_open) {
                            $order_detail['change_status_word'][] = 'reopen';
                        }
                        $order_detail['change_status_word'][] = 'recall';
                        $order_detail['change_status_word'][] = 'closed';
                    }
                } else if ($is_worker && $order_detail['order_type'] == 5) {
                    $order_detail['change_status'][] = ['log' => 'closed', 'type' => '', 'name' => '关闭'];
                    $order_detail['change_status_word'][] = 'closed';
                }
            } elseif ($order_detail['event_status'] >= 50 && $order_detail['event_status'] < 70) {
                // 已撤回 已关闭
                if ($is_post && !$jz_open) {
                    // 也是上报人员
                    $order_detail['change_status'][] = ['log' => 'reopen', 'type' => '', 'name' => '重新打开'];
                    if ($is_word) {
                        $order_detail['change_status_word'][] = 'reopen';
                    }
                } else {
                    $order_detail['change_status'] = [];
                    if ($is_word) {
                        $order_detail['change_status_word'] = [];
                    }
                }
            } elseif ($order_detail['event_status'] >= 70 && $order_detail['event_status'] < 80) {
                // 已评价
                if ($is_post && !$jz_open) {
                    $order_detail['change_status'] = [
                        ['log' => 'reopen', 'type' => '', 'name' => '重新打开'],
                        ['log' => 'recall', 'type' => '', 'name' => '撤回'],
                        ['log' => 'closed', 'type' => '', 'name' => '关闭'],
                    ];
                    if ($is_word) {
                        $order_detail['change_status_word'] = ['reopen', 'recall', 'closed'];
                    }
                } elseif ($is_post && !$jz_open) {
                    $order_detail['change_status'] = [
                        ['log' => 'closed', 'type' => '', 'name' => '关闭'],
                    ];
                    if ($is_word) {
                        $order_detail['change_status_word'] = ['closed'];
                    }
                } else {
                    $order_detail['change_status'] = [];
                    if ($is_word) {
                        $order_detail['change_status_word'] = [];
                    }
                }
            }
        }
        if (!$order_detail['change_status']) {
            $order_detail['change_status'] = [];
            if ($is_word) {
                $order_detail['change_status_word'] = [];
            }
        }
        if (isset($order_detail['add_time']) && $order_detail['add_time']) {
            $order_detail['add_time_txt'] = date('Y-m-d H:i:s', $order_detail['add_time']);
        }
        if (isset($order_detail['go_time']) && $order_detail['go_time']) {
            $order_detail['go_time_txt'] = date('Y-m-d H:i:s', $order_detail['go_time']);
        }
        $now_handle_village_id = isset($order_detail['now_handle_village_id']) ? intval($order_detail['now_handle_village_id']) : 0;
        $order_village_id = isset($order_detail['village_id']) ? intval($order_detail['village_id']) : 0;
        //        if ($jz_open && $now_handle_village_id && $order_village_id && $order_village_id != $now_handle_village_id) {
        //            $order_detail['pay_type']=[['key'=>'2','type'=>'线下缴费']];
        //        }
        if ($order_detail['bind_id'] == 0) {
            $order_detail['pay_type'] = [['key' => '2', 'type' => '线下缴费']];
        } else {
            $order_detail['pay_type'] = [['key' => '1', 'type' => '线上缴费'], ['key' => '2', 'type' => '线下缴费']];
        }
        if ($is_arr && $order_detail) {
            $order_arr = [];
            // type 为1 展示 content 为字符串  type为2  展示 children 为数组 type为3时候 展示imgs 为数组
            if (isset($order_detail['name']) && $order_detail['name']) {
                $order_arr[] = [
                    'title' => '上报人员',
                    'type' => 1,
                    'content' => $order_detail['name'],
                    'content_color' => '',
                    'children' => [],
                    'imgs' => [],
                    'videos' => [],
                ];
            }
            $address_txt = '无';
            $village_id = intval($order_detail['village_id']);
            $db_house_village = new HouseVillage();
            if ($village_id) {
                $village_info = $db_house_village->getOne($village_id, 'village_id,village_name,property_id');
            } else {
                $village_info = [];
            }
            if (isset($order_detail['address_txt']) && $order_detail['address_txt']) {
                $address_txt = $order_detail['address_txt'];
            } elseif (isset($order_detail['public_id']) && $order_detail['public_id']) {
                $public_id = intval($order_detail['public_id']);
                // 获取下公共区域
                $db_house_village_public_area = new HouseVillagePublicArea();
                $wherePublicArea = [];
                $wherePublicArea[] = ['public_area_id', '=', $public_id];
                $publicAreaInfo = $db_house_village_public_area->getOne($wherePublicArea, 'public_area_id, public_area_name');
                if ($publicAreaInfo && isset($publicAreaInfo['public_area_name']) && $publicAreaInfo['public_area_name']) {
                    $address_txt = $publicAreaInfo['public_area_name'];
                    if ($village_info && isset($village_info['village_name']) && $village_info['village_name']) {
                        $address_txt = $village_info['village_name'] . ' ' . $address_txt;
                    }
                }
            } elseif (isset($order_detail['room_id']) && $order_detail['room_id']) {
                $room_id = intval($order_detail['room_id']);
                $db_house_village_user_vacancy = new HouseVillageUserVacancy();
                $where_room = [];
                $where_room[] = ['pigcms_id', '=', $room_id];
                $field_room = 'pigcms_id, room, village_id, single_id, floor_id, layer_id';
                $room_info = $db_house_village_user_vacancy->getOne($where_room, $field_room);
                if (isset($order_detail['single_id']) && $order_detail['single_id']) {
                    $single_id = intval($order_detail['single_id']);
                } elseif (isset($room_info['single_id']) && $room_info['single_id']) {
                    $single_id = intval($room_info['single_id']);
                }
                if (isset($order_detail['floor_id']) && $order_detail['floor_id']) {
                    $floor_id = intval($order_detail['floor_id']);
                } elseif (isset($room_info['floor_id']) && $room_info['floor_id']) {
                    $floor_id = intval($room_info['floor_id']);
                }
                if (isset($order_detail['layer_id']) && $order_detail['layer_id']) {
                    $layer_id = intval($order_detail['layer_id']);
                } elseif (isset($room_info['layer_id']) && $room_info['layer_id']) {
                    $layer_id = intval($room_info['layer_id']);
                }
                $room_village_id = isset($room_info['village_id']) && $room_info['village_id'] ? intval($room_info['village_id']) : $village_id;
                if ($single_id && $floor_id && $layer_id && $room_id && $room_village_id) {
                    $service_house_village = new HouseVillageService();
                    $address_txt = $service_house_village->getSingleFloorRoom($single_id, $floor_id, $layer_id, $room_id, $room_village_id);
                    if ($address_txt && $village_info && isset($village_info['village_name']) && $village_info['village_name']) {
                        $address_txt = $village_info['village_name'] . ' ' . $address_txt;
                    }
                }
            }
            if ($village_info && isset($village_info['village_name']) && $village_info['village_name']) {
                $order_arr[] = [
                    'title' => '上报小区',
                    'type' => 1,
                    'content' => $village_info['village_name'],
                    'content_color' => '',
                    'children' => [],
                    'imgs' => [],
                    'videos' => [],
                ];
            }
            if ($address_txt) {
                $order_arr[] = [
                    'title' => '上报位置',
                    'type' => 1,
                    'content' => $address_txt,
                    'content_color' => '',
                    'children' => [],
                    'imgs' => [],
                    'videos' => [],
                ];
            }
            if (isset($order_detail['add_time_txt']) && $order_detail['add_time_txt']) {
                $order_arr[] = [
                    'title' => '上报时间',
                    'type' => 1,
                    'content' => $order_detail['add_time_txt'],
                    'content_color' => '',
                    'children' => [],
                    'imgs' => [],
                    'videos' => [],
                ];
            }
            if (isset($order_detail['phone']) && $order_detail['phone']) {
                $order_arr[] = [
                    'title' => '手机号码',
                    'type' => 1,
                    'content' => $order_detail['phone'],
                    'content_color' => '',
                    'children' => [],
                    'imgs' => [],
                    'videos' => [],
                ];
            }
            if (isset($order_detail['go_time_txt']) && $order_detail['go_time_txt']) {
                $go_time_txt = $order_detail['go_time_txt'];
            } else {
                $go_time_txt = '无';
            }
            $order_arr[] = [
                'title' => '上门时间',
                'type' => 1,
                'content' => $go_time_txt,
                'content_color' => '',
                'children' => [],
                'imgs' => [],
                'videos' => [],
            ];
            $category_txt = '无';
            if (isset($order_detail['category_id_txt']) && $order_detail['category_id_txt']) {
                $category_txt = $order_detail['category_id_txt'];
            }
            $type_id_txt = '无';
            if (isset($order_detail['type_id_txt']) && $order_detail['type_id_txt']) {
                $type_id_txt = $order_detail['type_id_txt'];
            }
            $order_arr[] = [
                'title' => '工单类目',
                'type' => 1,
                'content' => $order_detail['cat_fid_txt'],
                'content_color' => '',
                'children' => [],
                'imgs' => [],
                'videos' => [],
            ];

            $cat_txt = '无';
            if (isset($order_detail['cat_id_txt']) && $order_detail['cat_id_txt']) {
                $cat_txt = $order_detail['cat_id_txt'];
            }
            $order_arr[] = [
                'title' => '工单分类',
                'type' => 1,
                'content' => $cat_txt,
                'content_color' => '',
                'children' => [],
                'imgs' => [],
                'videos' => [],
            ];
            if (isset($order_detail['other_cate_id_txt']) && $order_detail['other_cate_id_txt']) {
                $order_arr[] = [
                    'title' => '供暖站工单类目',
                    'type' => 1,
                    'content' => $order_detail['other_cate_id_txt'],
                    'content_color' => '',
                    'children' => [],
                    'imgs' => [],
                    'videos' => [],
                ];
            }
            if (isset($order_detail['other_cate_fid_txt']) && $order_detail['other_cate_fid_txt']) {
                $order_arr[] = [
                    'title' => '供暖站工单分类',
                    'type' => 1,
                    'content' => $order_detail['other_cate_fid_txt'],
                    'content_color' => '',
                    'children' => [],
                    'imgs' => [],
                    'videos' => [],
                ];
            }

            $label_txt = '无';
            if (isset($order_detail['label_txt']) && trim($order_detail['label_txt'], ',')) {
                $db_house_new_repair_cate_custom = new HouseNewRepairCateCustom();
                $label_txt = trim($order_detail['label_txt'], ',');
                $whereLabel = [];
                $whereLabel[] = ['id', 'in', $label_txt];
                $labelData = $db_house_new_repair_cate_custom->getList($whereLabel, 'id,name', 0, 0, '');
                if (!empty($labelData)) {
                    $labelArr = [];
                    foreach ($labelData as $LVal) {
                        if ($LVal && isset($LVal['name'])) {
                            $labelArr[] = $LVal['name'];
                        }
                    }
                    $label_txt = implode("；", $labelArr);
                }
            }
            $order_arr[] = [
                'title' => '自定义字段',
                'type' => 1,
                'content' => $label_txt,
                'content_color' => '',
                'children' => [],
                'imgs' => [],
                'videos' => [],
            ];
            $order_arr[] = [
                'title' => '补充内容',
                'type' => 1,
                'content' => isset($order_detail['order_content']) && $order_detail['order_content'] ? $order_detail['order_content'] : '无',
                'content_color' => '',
                'children' => [],
                'imgs' => [],
                'videos' => [],
            ];
            if (isset($order_detail['order_imgs']) && $order_detail['order_imgs']) {
                $order_imgs = $order_detail['order_imgs'];
                if (strpos($order_imgs, ';') !== false) {
                    $order_imgs = explode(';', $order_imgs);
                } elseif ($order_imgs) {
                    $order_imgs = explode(',', $order_imgs);
                }
                if ($order_imgs) {
                    $order_imgs_arr = [];
                    foreach ($order_imgs as $val) {
                        $order_imgs_arr[] = replace_file_domain($val);
                    }
                    $order_detail['order_imgs_arr'] = $order_imgs_arr;
                }
            }
            $order_image = [
                'title' => '上报图例',
                'type' => 1,
                'content' => '无',
                'content_color' => '',
                'children' => [],
                'imgs' => [],
                'videos' => [],
            ];
            if (isset($order_detail['order_imgs_arr']) && $order_detail['order_imgs_arr']) {
                $order_image['type'] = 3;
                $order_image['content'] = '';
                $order_image['imgs'] = $order_detail['order_imgs_arr'];
            }
            $order_arr[] = $order_image;
            if (isset($order_detail['videos']) && $order_detail['videos']) {
                $order_videos = $order_detail['videos'];
                if (strpos($order_videos, ';') !== false) {
                    $order_videos = explode(';', $order_videos);
                } elseif ($order_videos) {
                    $order_videos = explode(',', $order_videos);
                }
                if ($order_videos) {
                    $order_videos_arr = [];
                    foreach ($order_videos as $val1) {
                        $video = replace_file_domain($val1);
                        $videoImage = $video . '.png';
                        $order_videos_arr[] = [
                            'video' => $video,
                            'image' => $videoImage,
                        ];
                    }
                    $order_detail['order_videos_arr'] = $order_videos_arr;
                }
            }
            $order_video = [
                'title' => '上报视频',
                'type' => 1,
                'content' => '无',
                'content_color' => '',
                'children' => [],
                'imgs' => [],
                'videos' => [],
            ];
            if (isset($order_detail['order_videos_arr']) && $order_detail['order_videos_arr']) {
                $order_video['type'] = 4;
                $order_video['content'] = '';
                $order_video['videos'] = $order_detail['order_videos_arr'];
                $order_arr[] = $order_video;
            } elseif ($jz_open) {
                $order_arr[] = $order_video;
            }

            if (isset($order_detail['event_status_txt']) && $order_detail['event_status_txt']) {
                if (intval($order_detail['event_status']) == 35) {
                    $order_detail['event_status_txt'] .= "（待用户同意）";
                }
                $order_arr[] = [
                    'title' => $title_status,
                    'type' => 1,
                    'content' => $order_detail['event_status_txt'],
                    'content_color' => $order_detail['event_status_color'],
                    'children' => [],
                    'imgs' => [],
                    'videos' => [],
                ];
            }
            if (isset($order_detail['charge_price']) && floatval($order_detail['charge_price']) > 0) {
                $order_arr[] = [
                    'title' => '应收金额',
                    'type' => 1,
                    'content' => $order_detail['charge_price'],
                    'content_color' => '',
                    'children' => [],
                    'imgs' => [],
                    'videos' => [],
                ];
            }
            if (isset($order_detail['pay_order_id']) && intval($order_detail['pay_order_id']) > 0) {
                $order_arr[] = [
                    'title' => '支付订单ID',
                    'type' => 1,
                    'content' => $order_detail['pay_order_id'],
                    'content_color' => '',
                    'children' => [],
                    'imgs' => [],
                    'videos' => [],
                ];
            }
            if (isset($order_detail['pay_order_id']) && intval($order_detail['pay_order_id']) > 0) {
                $pay_time = isset($order_detail['pay_time']) && $order_detail['pay_time'] ? $order_detail['pay_time'] : 0;
                $order_arr[] = [
                    'title' => '支付状态',
                    'type' => 1,
                    'content' => $pay_time > 100 ? '已支付' : '待支付',
                    'content_color' => $pay_time > 100 ? 'green' : 'red',
                    'children' => [],
                    'imgs' => [],
                    'videos' => [],
                ];
                if ($pay_time > 100) {
                    $order_arr[] = [
                        'title' => '支付时间',
                        'type' => 1,
                        'content' => date('Y-m-d H:i:s', $pay_time),
                        'content_color' => '',
                        'children' => [],
                        'imgs' => [],
                        'videos' => [],
                    ];
                }
            }
            if (empty($log)) {
                $log = (object)[];
            }
            $order_detail['log'] = $log;

            $order_detail['order_arr'] = $order_arr;
        }
        //        $order_detail['event_status'] = floor($order_detail['event_status']/10)*10;
        if ($order_detail['event_status'] < 20 && $order_detail['event_status'] != 15) {
            $order_detail['event_status'] = 10;
        } elseif ($order_detail['event_status'] >= 20 && $order_detail['event_status'] < 40) {
            $order_detail['event_status'] = 20;
        } elseif ($order_detail['event_status'] >= 40 && $order_detail['event_status'] < 49) {
            $order_detail['event_status'] = 40;
        }
        $order_detail['is_grab_order'] = ($order_detail['event_status'] == 15) ? true : false;
        return $order_detail;
    }

    /**
     * Notes: 获取企业通讯录
     * @param $group_id
     * @param $login_role
     * @param $worker_id
     * @param $property_id
     * @param string $search
     * @param $order_id
     * @param $type_name
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function repairWorkList($group_id, $login_role, $worker_id, $property_id, $search, $order_id, $type_name, $village_id = 0, $params = [])
    {
        $work_arr = [];
        $site_url = cfg('site_url');
        $static_resources = static_resources(true);
        $log = isset($params['log']) && trim($params['log']) ? trim($params['log']) : '';

        $dbHouseWorker = new HouseWorker();
        $db_property_group = new PropertyGroup();
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $common_works = [];
        $common_wids = [];
        $where_order = [];
        $where_order[] = ['order_id', '=', $order_id];
        $order_detail = $db_house_new_repair_works_order->getOne($where_order, 'village_id,event_status,worker_id,now_handle_village_id');
        $order_worker_id = isset($order_detail['worker_id']) ? intval($order_detail['worker_id']) : 0;
        if ($order_worker_id) {
            $common_wids[] = $order_worker_id;
        }
        if ($search) {
            // 搜索查询该物业下所有符合条件的工作人员
            $where_enterprise_works = [];
            $where_enterprise_works[] = ['property_id', '=', $property_id];
            $where_enterprise_works[] = ['status', '=', 1];
            $where_enterprise_works[] = ['is_del', '=', 0];
            $where_enterprise_works[] = ['name|phone', 'like', "%$search%"];
            if (!empty($common_wids)) {
                $where_enterprise_works[] = ['wid', 'not in', $common_wids];
            }
            if ($village_id) {
                $where_enterprise_works[] = ['village_id', '=', $village_id];
            }
            if ('work_change_constructor' == $log) {
                $where_enterprise_works[] = ['is_constructor', '=', 1];
            }
            $enterprise_works = $dbHouseWorker->getSome($where_enterprise_works, 'wid, name, avatar, is_constructor');
            if (!empty($enterprise_works)) {
                foreach ($enterprise_works as $item1) {
                    if (empty($item1['avatar'])) {
                        $item1['avatar'] = $site_url . $static_resources . 'images/avatar.png';
                    }
                    $is_constructor = isset($item1['is_constructor']) ? intval($item1['is_constructor']) : 0;
                    $name = $item1['name'];
                    $is_constructor == 1 && $name = $name . "（施工方）";
                    $enterprise_works_arr[] = [
                        'work_id' => $item1['wid'],
                        'name' => $name,
                        'avatar' => $item1['avatar'],
                        'type' => 'work'
                    ];
                }
            }
            // 查询对应 通讯录 组织部门
            $where_enterprise_group = [];
            $where_enterprise_group[] = ['fid', '=', $group_id];
            $where_enterprise_group[] = ['property_id', '=', $property_id];
            $where_enterprise_group[] = ['status', '=', 1];
            $where_enterprise_group[] = ['is_del', '=', 0];
            if ($village_id) {
                $where_enterprise_group[] = ['village_id', '=', $village_id];
            }
            $property_group_info = $db_property_group->getList($where_enterprise_group, 'id,name,sort', 0, 0, 'sort DESC,id ASC');
            if (!empty($property_group_info)) {
                foreach ($property_group_info as $item2) {
                    $item2['avatar'] = $site_url . $static_resources . 'images/Structural.png';
                    $name = $item2['name'];
                    $enterprise_works_arr[] = [
                        'group_id' => $item2['id'],
                        'name' => $name,
                        'avatar' => $item2['avatar'],
                        'type' => 'group'
                    ];
                }
            }
            if (!empty($enterprise_works_arr)) {
                $block = [
                    'title' => '联系人',
                    'children' => $enterprise_works_arr
                ];
                $work_arr[] = $block;
            }
        } elseif (!$group_id) {
            $assign = 1;
            if ($order_detail['event_status'] < 20 && $order_detail['event_status'] >= 10 && $type_name == 'choose_work') {
                $assign = 0;
            }
            $order_village_id = 0;
            if (isset($order_detail['now_handle_village_id']) && intval($order_detail['now_handle_village_id']) > 0) {
                $order_village_id = intval($order_detail['now_handle_village_id']);
                $village_id = $order_village_id;
            } elseif ($order_detail && isset($order_detail['village_id'])) {
                $order_village_id = $order_detail['village_id'];
                $village_id = $order_village_id;
            }
            // 查询下同部门的
            if (HouseWorkConst::MANAGE_APP_VILLAGE_WORK == $login_role && $assign) {
                $where[] = ['wid', '=', $worker_id];
                $work_info = $dbHouseWorker->getOne($where, 'wid, village_id, property_id, name, department_id, status');
                if (!$property_id && $work_info && isset($work_info['property_id'])) {
                    $property_id = $work_info['property_id'];
                }
                if ($order_village_id > 0 && $order_village_id != $work_info['village_id']) {
                    $work_info = array();
                }
                if ($work_info && isset($work_info['department_id'])) {
                    $department_id = intval($work_info['department_id']);
                    $where_common_group = [];
                    $where_common_group[] = ['department_id', '=', $department_id];
                    $where_common_group[] = ['property_id', '=', $property_id];
                    $where_common_group[] = ['status', '=', 1];
                    $where_common_group[] = ['is_del', '=', 0];
                    if ($village_id) {
                        $where_common_group[] = ['village_id', '=', $village_id];
                    }
                    if (!empty($common_wids)) {
                        $where_common_group[] = ['wid', 'not in', $common_wids];
                    }
                    if ('work_change_constructor' == $log) {
                        $where_common_group[] = ['is_constructor', '=', 1];
                    }
                    $commons = $dbHouseWorker->getSome($where_common_group, 'wid, name, avatar, is_constructor');
                    if (!empty($commons)) {
                        foreach ($commons as $item) {
                            if (empty($item['avatar'])) {
                                $item['avatar'] = $site_url . $static_resources . 'images/avatar.png';
                            }
                            $is_constructor = isset($item['is_constructor']) ? intval($item['is_constructor']) : 0;
                            $name = $item['name'];
                            $is_constructor && $name = $name . "（施工方）";
                            $common_works[] = [
                                'work_id' => $item['wid'],
                                'name' => $name,
                                'avatar' => $item['avatar'],
                                'type' => 'work'
                            ];
                            $common_wids[] = $item['wid'];
                        }
                        if (!empty($common_works)) {
                            $block = [
                                'title' => '同部门',
                                'children' => $common_works
                            ];
                            $work_arr[] = $block;
                        }
                    }
                }
            }
            $where_paren_group = [];

            $where_paren_group[] = ['property_id', '=', $property_id];
            $where_paren_group[] = ['status', '=', 1];
            $where_paren_group[] = ['is_del', '=', 0];
            if ($village_id) {
                $where_paren_group[] = ['type', '=', 0];
                $where_paren_group[] = ['village_id', '=', $village_id];
            } else {
                $where_paren_group[] = ['fid', '=', 0];
                $where_paren_group[] = ['type', '=', 99];
            }
            $property_paren_group = $db_property_group->getOne($where_paren_group, 'id,name');
            $enterprise_works_arr = [];
            if (!empty($property_paren_group) && isset($property_paren_group['id'])) {
                $group_id = $property_paren_group['id'];
                // 查询对应企业通讯录 对应工作人员
                $where_enterprise_works = [];
                $where_enterprise_works[] = ['department_id', '=', $group_id];
                $where_enterprise_works[] = ['property_id', '=', $property_id];
                $where_enterprise_works[] = ['status', '=', 1];
                $where_enterprise_works[] = ['is_del', '=', 0];
                if (!empty($common_wids)) {
                    $where_enterprise_works[] = ['wid', 'not in', $common_wids];
                }
                if ($village_id) {
                    $where_enterprise_works[] = ['village_id', '=', $village_id];
                }
                if ('work_change_constructor' == $log) {
                    $where_enterprise_works[] = ['is_constructor', '=', 1];
                }
                $enterprise_works = $dbHouseWorker->getSome($where_enterprise_works, 'wid, name, avatar, is_constructor');
                if (!empty($enterprise_works)) {
                    foreach ($enterprise_works as $item1) {
                        if (empty($item1['avatar'])) {
                            $item1['avatar'] = $site_url . $static_resources . 'images/avatar.png';
                        }
                        $is_constructor = isset($item1['is_constructor']) ? intval($item1['is_constructor']) : 0;
                        $name = $item1['name'];
                        $is_constructor && $name = $name . "（施工方）";
                        $enterprise_works_arr[] = [
                            'work_id' => $item1['wid'],
                            'name' => $name,
                            'avatar' => $item1['avatar'],
                            'type' => 'work'
                        ];
                    }
                }
                // 查询对应 通讯录 组织部门
                $where_enterprise_group = [];
                $where_enterprise_group[] = ['fid', '=', $group_id];
                $where_enterprise_group[] = ['property_id', '=', $property_id];
                $where_enterprise_group[] = ['status', '=', 1];
                $where_enterprise_group[] = ['is_del', '=', 0];
                if ($village_id) {
                    $where_enterprise_group[] = ['village_id', '=', $village_id];
                }
                $property_group_info = $db_property_group->getList($where_enterprise_group, 'id,name,sort', 0, 0, 'sort DESC,id ASC');
                if (!empty($property_group_info)) {
                    foreach ($property_group_info as $item2) {
                        $item2['avatar'] = $site_url . $static_resources . 'images/Structural.png';
                        $name = $item2['name'];
                        $enterprise_works_arr[] = [
                            'group_id' => $item2['id'],
                            'name' => $name,
                            'avatar' => $item2['avatar'],
                            'type' => 'group'
                        ];
                    }
                }
                if (!empty($enterprise_works_arr)) {
                    $block = [
                        'title' => '组织架构',
                        'children' => $enterprise_works_arr
                    ];
                    $work_arr[] = $block;
                }
            }
        } else {
            // 查询对应企业通讯录 对应工作人员
            $where_enterprise_works = [];
            $where_enterprise_works[] = ['department_id', '=', $group_id];
            $where_enterprise_works[] = ['property_id', '=', $property_id];
            $where_enterprise_works[] = ['status', '=', 1];
            $where_enterprise_works[] = ['is_del', '=', 0];
            if ($search) {
                $where_enterprise_works[] = ['name|phone', 'like', "%$search%"];
            }
            if (!empty($common_wids)) {
                $where_enterprise_works[] = ['wid', 'not in', $common_wids];
            }
            if ($village_id) {
                $where_enterprise_works[] = ['village_id', '=', $village_id];
            }
            if (!empty($common_wids)) {
                $where_enterprise_works[] = ['wid', 'not in', $common_wids];
            }
            if ('work_change_constructor' == $log) {
                $where_enterprise_works[] = ['is_constructor', '=', 1];
            }
            $enterprise_works = $dbHouseWorker->getSome($where_enterprise_works, 'wid, name, avatar, is_constructor');
            if (!empty($enterprise_works)) {
                foreach ($enterprise_works as $item1) {
                    if (empty($item1['avatar'])) {
                        $item1['avatar'] = $site_url . $static_resources . 'images/avatar.png';
                    }
                    $is_constructor = isset($item1['is_constructor']) ? intval($item1['is_constructor']) : 0;
                    $name = $item1['name'];
                    $is_constructor && $name = $name . "（施工方）";
                    $enterprise_works_arr[] = [
                        'work_id' => $item1['wid'],
                        'name' => $name,
                        'avatar' => $item1['avatar'],
                        'type' => 'work'
                    ];
                }
            }
            // 查询对应 通讯录 组织部门
            $where_enterprise_group = [];
            $where_enterprise_group[] = ['fid', '=', $group_id];
            $where_enterprise_group[] = ['property_id', '=', $property_id];
            $where_enterprise_group[] = ['status', '=', 1];
            $where_enterprise_group[] = ['is_del', '=', 0];
            if ($village_id) {
                $where_enterprise_group[] = ['village_id', '=', $village_id];
            }
            $property_group_info = $db_property_group->getList($where_enterprise_group, 'id,name,sort,village_id ', 0, 0, 'sort DESC,id ASC');
            if (!empty($property_group_info)) {
                foreach ($property_group_info as $item2) {
                    $item2['avatar'] = $site_url . $static_resources . 'images/Structural.png';
                    $name = $item2['name'];
                    $enterprise_works_arr[] = [
                        'group_id' => $item2['id'],
                        'village_id' => $item2['village_id'],
                        'name' => $name,
                        'avatar' => $item2['avatar'],
                        'type' => 'group'
                    ];
                }
            }
            if (!empty($enterprise_works_arr)) {
                $block = [
                    'title' => '组织架构',
                    'children' => $enterprise_works_arr
                ];
                $work_arr[] = $block;
            }
        }
        return $work_arr;
    }

    /**
     * Notes: 变更工单状态
     * @param $login_role
     * @param $data
     */
    public function filterAddWorkOrderLog($login_role = HouseWorkConst::MANAGE_APP_VILLAGE_WORK, $data)
    {
        $log_name = $data['log_name'];
        if (!$log_name) {
            throw new \think\Exception('缺少必传参数');
        }
        if (!isset($data['order_id']) || !$data['order_id']) {
            throw new \think\Exception('缺少必传参数');
        }
        if (!isset($data['worker_id']) || !$data['worker_id']) {
            throw new \think\Exception('缺少必传参数');
        }
        $order_id = $data['order_id'];
        $log_arr = [];
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $db_house_new_repair_works_order_log = new HouseNewRepairWorksOrderLog();
        $db_house_worker = new HouseWorker();
        $where_order = [];
        $where_order[] = ['order_id', '=', $order_id];
        $order_detail_info = $db_house_new_repair_works_order->getOne($where_order);
        if (empty($order_detail_info)) {
            throw new \think\Exception('对应工单不存在');
        }
        $property_id = $order_detail_info['property_id'];
        $db_house_new_repair_subject = new HouseNewRepairCate();
        $subject_info = $db_house_new_repair_subject->getOne(['id' => $order_detail_info['cat_id']]);
        if (empty($subject_info)) {
            throw new \think\Exception('工单分类不存在');
        }
        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        if ($jz_open) {
            $single_id = isset($order_detail_info['single_id']) && intval($order_detail_info['single_id']) > 0 ? intval($order_detail_info['single_id']) : 0;
            $room_id = isset($order_detail_info['room_id']) && intval($order_detail_info['room_id']) > 0 ? intval($order_detail_info['room_id']) : 0;
            $judgeParams = [
                'village_id' => $order_detail_info['village_id'],
                'repair_cate_id' => $order_detail_info['cat_id'],
                'jz_open' => $jz_open,
                'single_id' => $single_id,
                'room_id' => $room_id,
            ];
            $hasFee = (new HouseCommunityQualityService())->judgeRepairCateQualityFee($judgeParams);
        } else {
            $hasFee = false;
        }
        $scheme_charge_type = isset($data['scheme_charge_type']) && $data['scheme_charge_type'] ? intval($data['scheme_charge_type']) : 0;
        if ($subject_info['charge_type'] == 1 && !$hasFee && in_array($scheme_charge_type, [0, 2])) {
            $event_status = isset($order_detail_info['event_status']) ? intval($order_detail_info['event_status']) : 0;
            $data_project_id = isset($data['project_id']) ? intval($data['project_id']) : 0;
            $data_rule_id = isset($data['rule_id']) ? intval($data['rule_id']) : 0;
            if ($event_status > 0 && $event_status <= 37 && $event_status != 36 && $data_project_id > 0) {
                $data['project_id'] = $data_project_id;
            } elseif (!empty($order_detail_info['project_id'])) {
                $data['project_id'] = $order_detail_info['project_id'];
            }
            if ($event_status > 0 && $event_status <= 37 && $event_status != 36 && $data_rule_id > 0) {
                $data['rule_id'] = $data_rule_id;
            } elseif (!empty($order_detail_info['rule_id'])) {
                $data['rule_id'] = $order_detail_info['rule_id'];
            }

            if ($order_detail_info['event_status'] < 30 && 'work_follow_up' == $log_name) {
                if (empty($data['project_id'])) {
                    throw new \think\Exception('请先选择工单收费项目');
                }
                if (empty($data['rule_id'])) {
                    throw new \think\Exception('请先选择工单收费标准');
                }
                $db_house_new_charge_rule = new HouseNewChargeRule();
                $db_house_new_charge_project = new HouseNewChargeProject();
                $project_info = $db_house_new_charge_project->getOne(['id' => $data['project_id']]);
                if (empty($project_info)) {
                    throw new \think\Exception('工单收费项目不存在');
                }
                $rule_info = $db_house_new_charge_rule->getOne(['id' => $data['rule_id']]);
                if (empty($rule_info)) {
                    throw new \think\Exception('工单收费标准不存在');
                }
            }
            if ($jz_open && $order_detail_info['event_status'] < 30 && 'work_completed' == $log_name) {
                if (empty($data['rule_id'])) {
                    throw new \think\Exception('请先选择【推送方案】操作');
                }
            }
            if (!$jz_open && $order_detail_info['event_status'] < 30 && 'work_completed' == $log_name) {
                if (empty($data['rule_id'])) {
                    throw new \think\Exception('请先选择【跟进】操作');
                }
            }
        }
        $worker_id = $data['worker_id'];
        $nowVillageId = isset($data['village_id']) ? intval($data['village_id']) : 0;
        $params = [
            'village_id' => $nowVillageId
        ];
        try {
            $order_detail = $this->workGetOrder($order_id, $worker_id, $login_role, false, true, $params);
        } catch (\Exception $e) {
            fdump_api(['执行错误' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'data' => $data], '$repairOrderChange', true);
            throw new \think\Exception($e->getMessage());
        }
        $change_status_word = isset($order_detail['change_status_word']) ? $order_detail['change_status_word'] : [];
        if (empty($change_status_word)) {
            throw new \think\Exception('当前状态用户无法进行修改');
        }
        if (!in_array($log_name, $change_status_word)) {
            throw new \think\Exception('请上报正确处理类型');
        }
        if ($property_id <= 0 && $order_detail && $order_detail['property_id'] > 0) {
            $property_id = $order_detail['property_id'];
        }
        $order_imgs = $data['order_imgs'];
        if (is_array($order_imgs)) {
            foreach ($order_imgs as $key => $img) {
                if (!$img) {
                    unset($order_imgs[$key]);
                }
            }
            $order_imgs = array_values($order_imgs);
            $order_imgs = implode(';', $order_imgs);
        } elseif (is_string($order_imgs)) {
            $order_imgs = trim($order_imgs);
        }
        $order_content = isset($data['order_content']) && $data['order_content'] ? $data['order_content'] : '';

        $log_operator = $order_detail['name'] ? $order_detail['name'] : '';
        $log_phone = $order_detail['phone'] ? $order_detail['phone'] : '';
        $operator_id = $worker_id;
        $evaluate = isset($order_detail['evaluate']) ? intval($order_detail['evaluate']) : 0;
        $assign_worker_id = 0;
        $orderParam = [];
        // 添加记录前先行修改订单状态
        if ('property_admin_assign' == $log_name) {
            // 物业管理员指派
            if (!isset($data['choose_worker_id']) || !$data['choose_worker_id']) {
                throw new \think\Exception('缺少必传参数');
            }
            $choose_worker_id = intval($data['choose_worker_id']);
            $order_set = [
                'now_role' => 3,
                'event_status' => 21,
                'worker_type' => 0,
                'worker_id' => $choose_worker_id,
            ];
            $status = 'processing'; // 处理中
            $operator_type = 41;
            $assign_worker_id = $choose_worker_id;
        } elseif ('property_work_assign' == $log_name) {
            // 物业工作人员指派
            if (!isset($data['choose_worker_id']) || !$data['choose_worker_id']) {
                throw new \think\Exception('缺少必传参数');
            }
            $choose_worker_id = intval($data['choose_worker_id']);
            $order_set = [
                'now_role' => 3,
                'event_status' => 22,
                'worker_type' => 0,
                'worker_id' => $choose_worker_id,
            ];
            $status = 'processing'; // 处理中
            $operator_type = 42;
            $assign_worker_id = $choose_worker_id;
        } elseif ('house_admin_assign' == $log_name) {
            // 小区管理员指派
            if (!isset($data['choose_worker_id']) || !$data['choose_worker_id']) {
                throw new \think\Exception('缺少必传参数');
            }
            $choose_worker_id = intval($data['choose_worker_id']);
            $order_set = [
                'now_role' => 3,
                'event_status' => 23,
                'worker_type' => 0,
                'worker_id' => $choose_worker_id,
            ];
            $status = 'processing'; // 处理中
            $operator_type = 43;
            $assign_worker_id = $choose_worker_id;
        } elseif ('property_admin_reply' == $log_name) {
            // 物业管理员回复
            $order_set = [
                'now_role' => 1,
                'event_status' => 41,
            ];
            $status = 'processed'; // 已处理
            $operator_type = 41;
            $jz_open_title = '物业管理员回复了工单';
            $jz_open_content = '物业管理员回复了工单，去除自动结单时间';
        } elseif ('property_work_reply' == $log_name) {
            // 物业工作人员回复
            $order_set = [
                'now_role' => 1,
                'event_status' => 42,
            ];
            $status = 'processed'; // 已处理
            $operator_type = 42;
            $jz_open_title = '物业工作人员回复了工单';
            $jz_open_content = '物业工作人员回复了工单，去除自动结单时间';
        } elseif ('house_admin_reply' == $log_name) {
            // 小区管理员回复
            $order_set = [
                'now_role' => 1,
                'event_status' => 43,
            ];
            $status = 'processed'; // 已处理
            $operator_type = 43;
            $jz_open_title = '小区管理员回复了工单';
            $jz_open_content = '小区管理员回复了工单，去除自动结单时间';
        } elseif ('reopen' == $log_name) {
            //重新打开
            if (isset($order_detail['worker_id']) && $order_detail['worker_id']) {
                // 已经有工作人员了  应该是 已指派 且下个操作人员类型是工作人员
                $now_role = 3;
                $event_status = 20;
            } else {
                // 无工作人员了  应该是 待指派 且下个操作人员类型是处理中心
                $now_role = 2;
                $event_status = 10;
            }
            $order_set = [
                'now_role' => $now_role,
                'event_status' => $event_status,
            ];
            $status = 'processing'; // 处理中
            $operator_type = 10;
        } elseif ('recall' == $log_name || 'closed' == $log_name) {
            //撤回
            $txtKey = '';
            if ('recall' == $log_name) {
                $event_status = 50;
                $txtKey = '撤回';
            } else {
                $event_status = 60;
                $txtKey = '关闭';
            }
            $order_set = [
                'now_role' => 1,
                'event_status' => $event_status,
            ];
            $status = 'processed'; // 已处理
            $operator_type = 10;
            if (isset($order_detail['worker_type']) && $order_detail['worker_type'] == 0 && isset($order_detail['worker_id']) && $worker_id == $order_detail['worker_id']) {
                $operator_type = 30;
            }
            $jz_open_title = $txtKey . '了工单';
            $jz_open_content = $txtKey . '了工单，去除自动结单时间';
        } elseif ('evaluate' == $log_name) {
            //评分
            if (!isset($order_detail['evaluate'])) {
                throw new \think\Exception('缺少评分');
            }
            if ($evaluate < 0 || $evaluate > 5) {
                throw new \think\Exception('评分只支持1-5');
            }
            $order_set = [
                'now_role' => 1,
                'event_status' => 70,
                'evaluate' => $evaluate,
            ];
            $status = 'processed'; // 已处理
            $operator_type = 10;
        } elseif ('work_reject_center' == $log_name || 'constructor_reject_center' == $log_name) {
            //驳回给处理中心
            $order_set = [
                'now_role' => 2,
                'event_status' => 11,
                'worker_id' => 0,
            ];
            $status = 'processing'; // 处理中
            $operator_type = 30;
        } elseif ($jz_open && 'work_push_scheme' == $log_name) {
            if (isset($order_detail_info['pay_order_id']) && $order_detail_info['pay_order_id']) {
                throw new \think\Exception('工单已经生成了订单，请刷新后查看~');
            }
            // 收费  推送文案
            if ($subject_info['charge_type'] == 1 && !$hasFee && in_array($scheme_charge_type, [0, 2])) {
                if (empty($data['project_id'])) {
                    throw new \think\Exception('请先选择工单收费项目');
                }
                if (empty($data['rule_id'])) {
                    throw new \think\Exception('请先选择工单收费标准');
                }
                $db_house_new_charge_rule = new HouseNewChargeRule();
                $db_house_new_charge_project = new HouseNewChargeProject();
                $project_info = $db_house_new_charge_project->getOne(['id' => $data['project_id']]);
                if (empty($project_info)) {
                    throw new \think\Exception('工单收费项目不存在');
                }
                $rule_info = $db_house_new_charge_rule->getOne(['id' => $data['rule_id']]);
                if (empty($rule_info)) {
                    throw new \think\Exception('工单收费标准不存在');
                }
                //处理人员跟进 必须提交工单方 确认是线上支付（直接拉取付款）还是线下支付
                $order_set = [
                    'now_role' => 1,
                    'event_status' => 35,
                    'rule_id' => $data['rule_id'],
                    'project_id' => $data['project_id'],
                    'charge_price' => $rule_info['charge_price'],
                ];
                $orderParam = [
                    'scheme_charge_type' => $scheme_charge_type,
                    'rule_id' => $data['rule_id'],
                    'project_id' => $data['project_id'],
                    'charge_price' => $rule_info['charge_price'],
                    'property_id' => $property_id,
                    'village_id' => $nowVillageId,
                    'hasBills' => 0,
                ];
            } elseif ($subject_info['charge_type'] == 1 && !$hasFee && $scheme_charge_type == 1) {
                //处理人员跟进 必须提交工单方 确认是线上支付（直接拉取付款）还是线下支付
                $order_set = [
                    'now_role' => 1,
                    'event_status' => 35,
                    'rule_id' => 0,
                    'project_id' => 0,
                    'charge_price' => $data['charge_price'],
                ];
                $orderParam = [
                    'scheme_charge_type' => $scheme_charge_type,
                    'rule_id' => 0,
                    'project_id' => 0,
                    'charge_price' => $data['charge_price'],
                    'property_id' => $property_id,
                    'village_id' => $nowVillageId,
                    'hasBills' => 0,
                ];
            } else {
                throw new \think\Exception('工单当前分类无需收费');
            }
            $operator_type = 30;
            $status = 'todo'; // 待处理
        } elseif ('work_follow_up' == $log_name) {
            if (!$jz_open && $subject_info['charge_type'] == 1 && !$hasFee) {
                if (empty($data['project_id'])) {
                    throw new \think\Exception('请先选择工单收费项目');
                }
                if (empty($data['rule_id'])) {
                    throw new \think\Exception('请先选择工单收费标准');
                }
                $db_house_new_charge_rule = new HouseNewChargeRule();
                $db_house_new_charge_project = new HouseNewChargeProject();
                $db_house_new_offline_pay = new HouseNewOfflinePay();
                $project_info = $db_house_new_charge_project->getOne(['id' => $data['project_id']]);
                if (empty($project_info)) {
                    throw new \think\Exception('工单收费项目不存在');
                }
                $rule_info = $db_house_new_charge_rule->getOne(['id' => $data['rule_id']]);
                if (empty($rule_info)) {
                    throw new \think\Exception('工单收费标准不存在');
                }
                $data['charge_price'] = $rule_info['charge_price'];
                if ($order_detail_info['bind_id'] == 0) {
                    $offline_pay = $db_house_new_offline_pay->get_one(['property_id' => $order_detail_info['property_id'], 'status' => 1]);
                    if (!empty($offline_pay)) {
                        $offline_pay_id = $offline_pay['id'];
                    } else {
                        $offline_pay_id = 0;
                    }
                    //处理人员跟进
                    $order_set = [
                        'now_role' => 1,
                        'event_status' => 36,
                        'rule_id' => $data['rule_id'],
                        'project_id' => $data['project_id'],
                        'charge_price' => $rule_info['charge_price'],
                        'pay_type' => 2,
                        'offline_pay_type' => $offline_pay_id,
                    ];
                } else {
                    if ($order_detail_info['event_status'] == 36) {
                        //处理人员跟进
                        $order_set = [
                            'now_role' => 1,
                            'event_status' => 36,
                            'rule_id' => $data['rule_id'],
                            'project_id' => $data['project_id'],
                            'charge_price' => $rule_info['charge_price'],
                        ];
                    } else {
                        //处理人员跟进
                        $order_set = [
                            'now_role' => 1,
                            'event_status' => 35,
                            'rule_id' => $data['rule_id'],
                            'project_id' => $data['project_id'],
                            'charge_price' => $rule_info['charge_price'],
                        ];
                    }
                }
            } else {
                //处理人员跟进
                $order_set = [
                    'now_role' => 3,
                    'event_status' => 30,
                ];
            }

            $status = 'todo'; // 待处理
            $operator_type = 30;
        } elseif ('work_completed' == $log_name) {
            if ($subject_info['charge_type'] == 1 && !$hasFee) {
                if (empty($data['charge_price'])) {
                    $data['charge_price'] = $order_detail_info['charge_price'];
                }
                if (empty($data['pay_type'])) {
                    $data['pay_type'] = $order_detail_info['pay_type'];
                    if (empty($data['pay_type'])) {
                        throw new \think\Exception('请先选择工单缴费方式');
                    }
                }
                if ($data['pay_type'] == 1) {
                    $event_status = 44;
                } elseif ($data['pay_type'] == 2) {
                    $event_status = 45;
                } else {
                    $event_status = 40;
                }
                //处理人员跟进
                $order_set = [
                    'now_role' => 1,
                    'event_status' => $event_status,
                    'charge_price' => $data['charge_price'],
                    'pay_type' => $data['pay_type'],
                    'offline_pay_type' => $data['offline_pay_type'],
                    'change_reason' => $data['change_reason'],
                ];
            } else {
                //处理人员结单
                $order_set = [
                    'now_role' => 1,
                    'event_status' => 40,
                ];
            }
            $status = 'processing'; // 处理中（可再次结单）
            $operator_type = 30;
            $rr = $this->getTimelyStatus($order_detail);
            if ($rr['error']) {
                $order_set = array_merge($order_set, $rr['data']);
            }
            $jz_open_title = '处理人员结单';
            $jz_open_content = '处理人员结单，去除自动结单时间';
        } elseif (in_array($log_name, ['has_contact_supply_station', 'has_contact_developer'])) {
            // 获取联系（供暖站）+ 获取联系（开发商）
            // 处理人员提供联系方式
            $order_set = [
                'now_role' => 1,
                'event_status' => 42,
            ];
            $status = 'processed'; // 已处理
            $operator_type = 42;
            $jz_open_title = '处理人员提供联系方式';
            $jz_open_content = '处理人员提供联系方式，去除自动结单时间';
        } elseif ('work_change' == $log_name || 'work_change_constructor' == $log_name) {
            //处理人员转单
            if (!isset($data['choose_worker_id']) || !$data['choose_worker_id']) {
                throw new \think\Exception('缺少必传参数');
            }
            if ($jz_open) {
                $choose_worker_id = intval($data['choose_worker_id']);
                $to_village_id = isset($order_detail_info['now_handle_village_id']) && intval($order_detail_info['now_handle_village_id']) > 0 ? intval($order_detail_info['now_handle_village_id']) : 0;
                !$to_village_id && $to_village_id = $order_detail_info['village_id'];
                $jzParams = [
                    'operator_id' => $operator_id,
                    'order_id' => $order_id,
                    'village_id' => $to_village_id,
                    'order_info' => $order_detail_info,
                    'log_name' => $log_name,
                    'to_work_id' => $choose_worker_id,
                ];
                isset($data['login_role']) && $jzParams['login_role'] = $data['login_role'];
                isset($data['phone']) && $jzParams['phone'] = $data['phone'];
                isset($data['name']) && $jzParams['name'] = $data['name'];
                isset($data['worker_id']) && $jzParams['worker_id'] = $data['worker_id'];
                isset($data['order_content']) && $jzParams['order_content'] = $data['order_content'];
                $jzResult = (new HouseCommunityTransferOrderService())->transferOrderToOtherWork($jzParams);
                $order_set = isset($jzResult['order_set']) ? $jzResult['order_set'] : [];
                $status = isset($jzResult['status']) ? $jzResult['status'] : 'processed'; // 已处理
                $operator_type = isset($jzResult['operator_type']) ? $jzResult['operator_type'] : 30;
                if (empty($order_set)) {
                    throw new \think\Exception('转单供暖站异常');
                }
                if (isset($jzResult['log_name']) && $jzResult['log_name']) {
                    $log_name = $jzResult['log_name'];
                }
            } else {
                $choose_worker_id = intval($data['choose_worker_id']);
                $order_set = [
                    'now_role' => 3,
                    'event_status' => 24,
                    'worker_type' => 0,
                    'worker_id' => $choose_worker_id,
                ];
                $status = 'processed'; // 已处理
                $operator_type = 30;
            }
        } elseif (in_array($log_name, ['work_reject_change', 'constructor_reject_change', 'agree_constructor_project', 'reject_constructor_project'])) {
            // 被转单人员拒绝
            // 查询下 最后一次非本人操作
            $where_log_last = [];
            $where_log_last[] = ['order_id', '=', $order_id];
            $where_log_last[] = ['operator_id', '<>', $worker_id];
            if ($log_name == 'constructor_reject_change') {
                $where_log_last[] = ['log_name', 'in', 'work_change_constructor,reject_constructor_project'];
            } else {
                $where_log_last[] = ['operator_type', '>=', 30];
                $where_log_last[] = ['operator_type', '<', 40];
            }
            $log_last = $db_house_new_repair_works_order_log->getOne($where_log_last, true, 'add_time DESC');
            if (empty($log_last)) {
                $log_last = [];
            }
            $log_last_name = isset($log_last['log_name']) && trim($log_last['log_name']) ? $log_last['log_name'] : '';
            if (in_array($log_last_name, ['work_change', 'work_change_constructor', 'constructor_reject_change', 'reject_constructor_project'])) {
                $choose_worker_id = intval($log_last['operator_id']);
            } else {
                $choose_worker_id = 0;
            }
            $event_status = 25;
            if (in_array($log_name, ['constructor_reject_change', 'agree_constructor_project'])) {
                $event_status = 24;
            }
            $order_set = ['now_role' => 3, 'event_status' => $event_status, 'worker_type' => 0, 'worker_id' => $choose_worker_id];
            $operator_type = 30;
            $status = 'processed'; // 已处理
        } elseif ('reject_change_constructor' == $log_name) {
            // 退回开发商
            // 查询下 最后一次非本人操作
            $where_log_last = [];
            $where_log_last[] = ['order_id', '=', $order_id];
            $where_log_last[] = ['operator_id', '<>', $worker_id];
            $where_log_last[] = ['log_name', '=', 'work_change_constructor'];
            $log_last = $db_house_new_repair_works_order_log->getOne($where_log_last, true, 'add_time DESC');
            if (empty($log_last)) {
                $log_last = [];
            }
            if (empty($log_last)) {
                // 确保下 如果没有查到对应操作 查下最近一次其他人员操作
                $where_log_last = [];
                $where_log_last[] = ['order_id', '=', $order_id];
                $where_log_last[] = ['operator_id', '<>', $worker_id];
                $where_log_last[] = ['operator_type', '>=', 30];
                $where_log_last[] = ['operator_type', '<', 40];
                $log_last = $db_house_new_repair_works_order_log->getOne($where_log_last, true, 'add_time DESC');
                if (empty($log_last)) {
                    $log_last = [];
                }
            }
            $log_last_name = isset($log_last['log_name']) && trim($log_last['log_name']) ? $log_last['log_name'] : '';
            if (in_array($log_last_name, ['work_change', 'work_change_constructor', 'constructor_reject_change', 'reject_constructor_project'])) {
                $choose_worker_id = intval($log_last['operator_id']);
            } else {
                $choose_worker_id = 0;
            }
            $event_status = 25;
            $order_set = ['now_role' => 3, 'event_status' => $event_status, 'worker_type' => 0, 'worker_id' => $choose_worker_id];
            $status = 'processing'; // 处理中
            $operator_type = 30;
        } elseif ('work_reject' == $log_name) {
            //处理人员拒绝
            $order_set = [
                'now_role' => 2,
                'event_status' => 14,
                'worker_id' => 0,
            ];
            $status = 'todo'; // 待处理
            $operator_type = 30;
        } elseif ('to_supply_station' == $log_name && $jz_open) {
            // todo 转单到对应供暖站
            $jzParams = [
                'bind_facilities_type' => 'supply_station',
                'operator_id' => $operator_id,
                'order_id' => $order_id,
                'village_id' => $order_detail_info['village_id'],
                'order_info' => $order_detail_info,
                'log_name' => $log_name,
            ];
            isset($data['login_role']) && $jzParams['login_role'] = $data['login_role'];
            isset($data['phone']) && $jzParams['phone'] = $data['phone'];
            isset($data['name']) && $jzParams['name'] = $data['name'];
            isset($data['worker_id']) && $jzParams['worker_id'] = $data['worker_id'];
            isset($data['order_content']) && $jzParams['order_content'] = $data['order_content'];
            $jzResult = (new HouseCommunityTransferOrderService())->transferOrderToOtherVillage($jzParams);
            $order_set = isset($jzResult['order_set']) ? $jzResult['order_set'] : [];
            $status = isset($jzResult['status']) ? $jzResult['status'] : 'processed'; // 已处理
            $operator_type = isset($jzResult['operator_type']) ? $jzResult['operator_type'] : 30;
            if (empty($order_set)) {
                throw new \think\Exception('转单供暖站异常');
            }
            if (isset($jzResult['log_name']) && $jzResult['log_name']) {
                $log_name = $jzResult['log_name'];
            }
        } elseif ('to_developer' == $log_name && $jz_open) {
            // todo 转单到对应开发商
            $jzParams = [
                'bind_facilities_type' => 'developer',
                'operator_id' => $operator_id,
                'order_id' => $order_id,
                'village_id' => $order_detail_info['village_id'],
                'order_info' => $order_detail_info,
                'log_name' => $log_name,
            ];
            isset($data['login_role']) && $jzParams['login_role'] = $data['login_role'];
            isset($data['phone']) && $jzParams['phone'] = $data['phone'];
            isset($data['name']) && $jzParams['name'] = $data['name'];
            isset($data['worker_id']) && $jzParams['worker_id'] = $data['worker_id'];
            isset($data['order_content']) && $jzParams['order_content'] = $data['order_content'];
            $jzResult = (new HouseCommunityTransferOrderService())->transferOrderToOtherVillage($jzParams);
            $order_set = isset($jzResult['order_set']) ? $jzResult['order_set'] : [];
            $status = isset($jzResult['status']) ? $jzResult['status'] : 'processed'; // 已处理
            $operator_type = isset($jzResult['operator_type']) ? $jzResult['operator_type'] : 30;
            if (empty($order_set)) {
                throw new \think\Exception('转单开发商异常');
            }
            if (isset($jzResult['log_name']) && $jzResult['log_name']) {
                $log_name = $jzResult['log_name'];
            }
        } elseif ('reject_to_developer' == $log_name && $jz_open) {
            // 退回楼栋管家（就是 拒绝转单到开发商 退回原小区原工作人员）
            $jzParams = [
                'operator_facilities_type' => 'developer',
                'operator_id' => $operator_id,
                'order_id' => $order_id,
                'village_id' => $order_detail_info['village_id'],
                'order_info' => $order_detail_info,
                'log_name' => $log_name,
            ];
            $jzResult = (new HouseCommunityTransferOrderService())->transferOrderToOriginVillage($jzParams);
            $order_set = isset($jzResult['order_set']) ? $jzResult['order_set'] : [];
            $status = isset($jzResult['status']) ? $jzResult['status'] : 'processed'; // 已处理
            $operator_type = isset($jzResult['operator_type']) ? $jzResult['operator_type'] : 30;
            if (empty($order_set)) {
                throw new \think\Exception('转单开发商异常');
            }
            if (isset($jzResult['log_name']) && $jzResult['log_name']) {
                $log_name = $jzResult['log_name'];
            }
        } elseif ('work_change_cate' == $log_name && $jz_open) {
            $changeCateFid = isset($data['changeCateFid']) ? intval($data['changeCateFid']) : 0;
            $changeCateId = isset($data['changeCateId']) ? intval($data['changeCateId']) : 0;
            if (!$changeCateFid || !$changeCateId) {
                throw new \think\Exception('缺少更换的分类id');
            }
            $status = 'todo'; // 待处理
            $operator_type = 30;
            $serviceRepairCate = new RepairCateService();
            $whereQuery = [];
            $whereQuery[] = ['id', '=', $changeCateId];
            $changeCateInfo = $serviceRepairCate->queryRepairCate($whereQuery);
            if ($changeCateInfo && !is_array($changeCateInfo)) {
                $changeCateInfo = $changeCateInfo->toArray();
            }
            $other_village_cate_id = isset($changeCateInfo['other_village_cate_id']) ? intval($changeCateInfo['other_village_cate_id']) : 0;
            $other_village_cate_fid = isset($changeCateInfo['other_village_cate_fid']) ? intval($changeCateInfo['other_village_cate_fid']) : 0;

            $order_cat_fid = isset($order_detail_info['cat_fid']) ? intval($order_detail_info['cat_fid']) : 0;
            $order_cat_id = isset($order_detail_info['cat_id']) ? intval($order_detail_info['cat_id']) : 0;
            $order_other_cate_fid = isset($order_detail_info['other_cate_fid']) ? intval($order_detail_info['other_cate_fid']) : 0;
            $order_other_cate_id = isset($order_detail_info['other_cate_id']) ? intval($order_detail_info['other_cate_id']) : 0;

            $change_cat_fid = $changeCateFid;
            $change_cat_id = $changeCateId;
            $change_other_cate_fid = $other_village_cate_fid;
            $change_other_cate_id = $other_village_cate_id;
            $log_info = [
                'order_cat_fid' => $order_cat_fid,
                'order_cat_id' => $order_cat_id,
                'order_other_cate_fid' => $order_other_cate_fid,
                'order_other_cate_id' => $order_other_cate_id,
                'change_cat_fid' => $change_cat_fid,
                'change_cat_id' => $change_cat_id,
                'change_other_cate_fid' => $change_other_cate_fid,
                'change_other_cate_id' => $change_other_cate_id,
            ];
            $event_status = isset($order_detail_info['event_status']) ? intval($order_detail_info['event_status']) : 20;
            $order_set = ['now_role' => 3, 'modify_cate_num' => 1, 'event_status' => $event_status, 'cat_fid' => $change_cat_fid, 'cat_id' => $change_cat_id, 'other_cate_fid' => $change_other_cate_fid, 'other_cate_id' => $change_other_cate_id];
        } else {
            throw new \think\Exception('请上报正确处理类型');
        }
        $order_set['update_time'] = time();
        $where_update = [];
        $where_update[] = ['order_id', '=', $order_id];
        if (isset($order_set['worker_id']) && !empty($order_set['worker_id'])) {
            $order_set['group_id'] = $this->getGroupId($order_set['worker_id']);
        }

        $order_update = $db_house_new_repair_works_order->saveOne($where_update, $order_set);
        fdump_api([$order_update, $where_update, $order_set], 'addPayOrder_0414', 1);
        if ($order_update) {
            if ($jz_open && 'evaluate' == $log_name) {
                // 评分进行处理
                $toParams = ['village_id' => isset($order_detail['village_id']) ? intval($order_detail['village_id']) : 0, 'works_order_id' => $order_id];
                (new HouseCommunityHandleNoticeService())->tryHandleEvaluateLowToReportBox($toParams);
            }
            // 事件处理人员 处理
            if ($operator_type >= 30 && $operator_type < 40) {
                // 查询处理人信息
                $where_house_work = [];
                $where_house_work[] = ['wid', '=', $operator_id];
                $house_work = $db_house_worker->get_one($where_house_work, 'name,phone');
                $log_operator = $house_work['name'];
                $log_phone = $house_work['phone'];
            }
            // 更新工单成功 记录操作
            $log_data = [
                'order_id' => $order_id,
                'log_name' => $log_name,
                'village_id' => isset($order_detail['village_id']) ? intval($order_detail['village_id']) : '',
                'property_id' => isset($order_detail['property_id']) ? intval($order_detail['property_id']) : '',
                'log_operator' => $log_operator,
                'log_phone' => $log_phone,
                'operator_type' => $operator_type,
                'operator_id' => $operator_id,
                'log_uid' => $order_detail['uid'] ? $order_detail['uid'] : 0,
                'log_content' => $order_content ? $order_content : '',
                'log_imgs' => $order_imgs ? $order_imgs : '',
                'evaluate' => $evaluate,
                'add_time' => time(),
            ];
            if (isset($log_info) && !empty($log_info)) {
                $log_data['log_info'] = json_encode($log_info, JSON_UNESCAPED_UNICODE);
            }
            if (isset($order_set['to_station_village_id']) && $order_set['to_station_village_id']) {
                $log_data['to_station_village_id'] = intval($order_set['to_station_village_id']);
            }
            if (isset($order_set['to_developer_village_id']) && $order_set['to_developer_village_id']) {
                $log_data['to_developer_village_id'] = intval($order_set['to_developer_village_id']);
            }
            $db_house_new_repair_works_order_log = new HouseNewRepairWorksOrderLog();
            $where_count = [];
            $where_count[] = ['order_id', '=', $order_id];
            $where_count[] = ['log_name', '=', strval($data['log_name'])];
            $log_num = $db_house_new_repair_works_order_log->getCount($where_count);
            if (!$log_num) {
                $log_num = 1;
            } else {
                $log_num = intval($log_num) + 1;
            }
            //收费工单生成缴费账单
            fdump_api([$log_name, $subject_info['charge_type'], $order_set['event_status']], 'addPayOrder_0414', 1);
            if (!$jz_open && 'work_completed' == $log_name && $subject_info['charge_type'] == 1 && !$hasFee && in_array($order_set['event_status'], [44, 45]) && $log_num == 1) {
                $this->addPayOrder($order_id);
            }
            if ($jz_open && 'work_push_scheme' == $log_name) {
                $this->addPayOrder($order_id, $orderParam);
            }
            $log_data['log_num'] = $log_num;
            $log_data['project_id'] = $data['project_id'];
            $log_data['rule_id'] = $data['rule_id'];
            $log_data['charge_price'] = $data['charge_price'];
            $log_data['pay_type'] = $data['pay_type'];
            $log_data['change_reason'] = $data['change_reason'];
            fdump_api(['line' => __LINE__, 'log_data' => $log_data], 'addPayOrder_0414', 1);
            $otherData = array();
            if ($assign_worker_id > 0) {
                $tmp_village_id = $log_data['village_id'];
                $otherData = array('opt_type' => 'assign_to_house_worker', 'wid' => $assign_worker_id, 'village_id' => $tmp_village_id);
            }
            $log_id = $this->addRepairLog($log_data, $otherData);
            fdump_api(['line' => __LINE__, 'log_id' => $log_id], 'addPayOrder_0414', 1);
            $log_arr['log_id'] = $log_id;
            $log_arr['status'] = $status;
            $charge_type = $this->getCateInfo(['id' => $order_detail_info['cat_id']], 'charge_type');
            if ($log_name == 'work_follow_up' && ($charge_type && intval($charge_type['charge_type']) == 1) && !$hasFee && $order_detail_info['event_status'] < 30) {
                if ($order_detail_info['order_type'] == 5) {
                    //todo 后台创建的工单给工作人员发送已确认工单
                    $this->newRepairSendMsg(51, $order_id, 0, $property_id);
                } else {
                    //todo 工作人员跟进
                    $this->newRepairSendMsg(50, $order_id, 0, $property_id);
                }
                fdump_api(['line' => __LINE__, 'charge_type' => $charge_type, 'hasFee' => $hasFee, 'log_name' => $log_name], 'addPayOrder_0414', 1);
            } elseif ($log_name == 'work_completed') {
                //todo 工作人员结单
                if ($order_detail_info['order_type'] == 0) {
                    //todo 用户提交的工单
                    if ($order_detail_info['event_status'] >= 40) {
                        $this->newRepairSendMsg(32, $order_id, 0, $property_id);
                    } else {
                        $this->newRepairSendMsg(30, $order_id, 0, $property_id);
                    }
                } elseif ($order_detail_info['order_type'] == 4) {
                    //todo 工作人员提交的工单
                    if ($order_detail_info['event_status'] >= 40) {
                        $this->newRepairSendMsg(33, $order_id, $order_detail_info['uid'], $property_id);
                    } else {
                        $this->newRepairSendMsg(31, $order_id, $order_detail_info['uid'], $property_id);
                    }
                }
            } elseif ($log_name == 'reopen') {
                //todo 工作人员重新打开
                $this->newRepairSendMsg(10, $order_id, 0, $property_id);
            } elseif ($log_name == 'work_reject') {
                if ($order_detail_info['order_type'] == 0) {
                    //todo 工作人员拒绝用户工单
                    $this->newRepairSendMsg(40, $order_id, 0, $property_id);
                } elseif ($order_detail_info['order_type'] == 4) {
                    //todo 工作人员拒绝工作人员工单
                    $this->newRepairSendMsg(42, $order_id, $order_detail_info['uid'], $property_id);
                }
            } elseif ($log_name == 'closed') {
                //todo 工作人员关闭工单
                $this->newRepairSendMsg(13, $order_id, 0, $property_id);
            } elseif ($log_name == 'recall') {
                //todo 工作人员撤回工单
                $this->newRepairSendMsg(14, $order_id, 0, $property_id);
            } elseif ($log_name == 'work_change' || $log_name == 'work_change_constructor') {
                //todo 工作人员转单
                $this->newRepairSendMsg(12, $order_id, 0, $property_id);
                //todo 转单记录当前转单人信息
                $this->workOrderLogSave($log_id, $order_set['worker_id']);
            } elseif ($log_name == 'work_reject_change' || $log_name == 'reject_to_developer') {
                //todo 工作人员拒绝被转单 驳回给转单人
                $this->newRepairSendMsg(41, $order_id, $order_set['worker_id'], $property_id);
            } elseif ($log_name == 'constructor_reject_change') {
                //todo 提供施工方案
                $this->newRepairSendMsg(82, $order_id, $order_set['worker_id'], $property_id);
            } elseif ($log_name == 'agree_constructor_project') {
                //todo 同意施工方案
                $this->newRepairSendMsg(83, $order_id, $order_set['worker_id'], $property_id);
            } elseif ($log_name == 'reject_constructor_project') {
                //todo 拒绝施工方案
                $this->newRepairSendMsg(84, $order_id, $order_set['worker_id'], $property_id);
            } elseif (in_array($log_name, ['property_admin_assign', 'property_work_assign', 'house_admin_assign'])) {
                //todo 物业管理员指派、物业工作人员指派、小区管理员指派
                $this->newRepairSendMsg(15, $order_id, 0, $property_id);
            } elseif ('to_supply_station' == $log_name && $jz_open) {
                //todo 工作人员转单到供暖站小区
                $params = [];
                $params['isToOtherVillage'] = 1;
                $this->newRepairSendMsg(12, $order_id, 0, $property_id, $params);
                //todo 转单记录当前转单人信息
                $where = [];
                $where[] = ['log_id', '=', $log_id];
                $log_info = [];
                if (isset($order_set['worker_id']) && intval($order_set['worker_id'])) {
                    $worker = $this->getWorker($order_set['worker_id'], 'wid,name,phone');
                    $log_info['wid'] = isset($worker['wid']) ? intval($worker['wid']) : 0;
                    $log_info['name'] = isset($worker['name']) ? trim($worker['name']) : '';
                    $log_info['phone'] = isset($worker['phone']) ? trim($worker['phone']) : '';
                }
                if (isset($facilitiesInfo['to_village_name']) && $facilitiesInfo['to_village_name']) {
                    $log_info['to_village_name'] = isset($worker['to_village_name']) ? trim($worker['to_village_name']) : '';
                }
                (new HouseNewRepairWorksOrderLog())->saveOne($where, ['log_info' => serialize($log_info)]);
            } elseif ('to_developer' == $log_name && $jz_open) {
                //todo 工作人员转单到开发商小区
                $params = [];
                $params['isToOtherVillage'] = 1;
                $this->newRepairSendMsg(12, $order_id, 0, $property_id, $params);
                //todo 转单记录当前转单人信息
                $where = [];
                $where[] = ['log_id', '=', $log_id];
                $log_info = [];
                if (isset($order_set['worker_id']) && intval($order_set['worker_id'])) {
                    $worker = $this->getWorker($order_set['worker_id'], 'wid,name,phone');
                    $log_info['wid'] = isset($worker['wid']) ? intval($worker['wid']) : 0;
                    $log_info['name'] = isset($worker['name']) ? trim($worker['name']) : '';
                    $log_info['phone'] = isset($worker['phone']) ? trim($worker['phone']) : '';
                }
                if (isset($facilitiesInfo['to_village_name']) && $facilitiesInfo['to_village_name']) {
                    $log_info['to_village_name'] = isset($worker['to_village_name']) ? trim($worker['to_village_name']) : '';
                }
                (new HouseNewRepairWorksOrderLog())->saveOne($where, ['log_info' => serialize($log_info)]);
            }
            fdump_api(['line' => __LINE__, 'assign_worker_id' => $assign_worker_id], 'addPayOrder_0414', 1);
            if ($assign_worker_id) {
                //todo 转单记录当前转单人信息
                $this->workOrderLogSave($log_id, $assign_worker_id);
                fdump_api(['line' => __LINE__, 'log_id' => $log_id, 'assign_worker_id' => $assign_worker_id], 'addPayOrder_0414', 1);
            }
            fdump_api(['line' => __LINE__, 'jz_open' => $jz_open, 'log_name' => $log_name], 'addPayOrder_0414', 1);
            if ($jz_open && $log_name == 'reject_constructor_project') {
                // todo 用户拒绝了 走下推送配置
                $toParams = ['village_id' => $order_detail_info['village_id'], 'works_order_id' => $order_id,];
                (new HouseCommunityHandleNoticeService())->tryHandleRejectProjectOverToReportBox($toParams);
            }
            fdump_api(['line' => __LINE__, 'jz_open' => $jz_open, 'log_name' => $log_name], 'addPayOrder_0414', 1);
            if ($jz_open && isset($jz_open_title) && isset($jz_open_content)) {
                $stepArr = [
                    'title' => $jz_open_title,
                    'type' => 'text',
                    'content' => $jz_open_content,
                    'time' => time(),
                    'work_id' => $data['worker_id']
                ];
                (new HouseCommunityWorkOrderService())->clearAutoEndTime($order_id, $stepArr);
                fdump_api(['line' => __LINE__, 'order_id' => $order_id, 'stepArr' => $stepArr], 'addPayOrder_0414', 1);
            }
            fdump_api(['line' => __LINE__, 'jz_open' => $jz_open, 'log_name' => $log_name], 'addPayOrder_0414', 1);
            if ($jz_open && isset($order_set['now_handle_village_id']) && $order_set['now_handle_village_id']) {
                $params = [
                    'village_id' => isset($order_detail['village_id']) ? intval($order_detail['village_id']) : 0,
                    'works_order_id' => $order_id,
                    'now_handle_village_id' => $order_set['now_handle_village_id'],
                    'to_work_id' => isset($order_set['worker_id']) ? intval($order_set['worker_id']) : 0
                ];
                (new HouseCommunityWorkOrderService())->toOtherVillage($params);
                fdump_api(['line' => __LINE__, 'jz_open' => $jz_open, 'params' => $params], 'addPayOrder_0414', 1);
            }
            fdump_api(['line' => __LINE__, 'jz_open' => $jz_open, 'log_name' => $log_name], 'addPayOrder_0414', 1);
            if ($jz_open && isset($order_set['event_status'])) {
                $event_status = intval($order_set['event_status']);
                $to_work_id = isset($order_set['worker_id']) ? intval($order_set['worker_id']) : 0;
                $village_id = isset($order_detail['village_id']) ? intval($order_detail['village_id']) : '';
                $params = ['village_id' => $village_id, 'order_id' => $order_id, 'worker_id' => $to_work_id, 'event_status' => $event_status];
                (new HouseCommunityWorkOrderService())->overNotProcessedReport($params);
                fdump_api(['line' => __LINE__, 'jz_open' => $jz_open, 'params' => $params], 'addPayOrder_0414', 1);
            }
            fdump_api(['line' => __LINE__, 'jz_open' => $jz_open, 'log_name' => $log_name], 'addPayOrder_0414', 1);
        } else {
            throw new \think\Exception('处理失败');
        }
        return $log_arr;
    }


    public function workGetOrderLog($order_id, $login_role, $worker_id)
    {
        $order_detail = $this->workGetOrder($order_id, $worker_id, $login_role, false, true);
        if (!$order_detail) {
            throw new \think\Exception('对应工单不存在');
        }
        $is_post = isset($order_detail['is_post']) ? $order_detail['is_post'] : false;
        $is_worker = isset($order_detail['is_worker']) ? $order_detail['is_worker'] : false;
        $is_through = isset($order_detail['is_through']) ? $order_detail['is_through'] : false;
        $is_admin = isset($order_detail['is_admin']) ? $order_detail['is_admin'] : false;
        if (!$is_post && !$is_worker && !$is_through && !$is_admin && !($order_detail['event_status'] == 15)) {
            throw new \think\Exception('您当前账号不能查看该工单信息');
        }

        $db_house_new_repair_works_order_log = new HouseNewRepairWorksOrderLog();

        $where_log = [];
        $where_log[] = ['order_id', '=', $order_id];
        $log_list = $db_house_new_repair_works_order_log->getList($where_log, true, 0, 0);
        $log = [];
        if ($log_list) {
            $log_list = $log_list->toArray();
            $info = [];
            $log_arr = $this->log_name;
            $dbHouseNewRepairCate = new HouseNewRepairCate();
            foreach ($log_list as $val) {
                if (isset($val['log_name']) && $val['log_name']) {

                    if (isset($val['log_num']) && $val['log_num'] > 1) {
                        $title = "第{$val['log_num']}次" . $log_arr[$val['log_name']];
                    } else {
                        $title = $log_arr[$val['log_name']];
                    }
                    if (isset($val['wisdomqrcode_id']) && $val['wisdomqrcode_id'] > 0 && strpos($val['log_name'], '_submit')) {
                        $title = '巡检转工单';
                    }
                    if (isset($val['add_time']) && $val['add_time']) {
                        $val['add_time_txt'] = date('Y-m-d H:i:s', $val['add_time']);
                    }
                    $tip = [];
                    if (isset($val['log_operator']) && $val['log_operator']) {
                        $tip[] = [
                            'title' => '操作人员',
                            'content' => $val['log_operator'],
                            'content_color' => '',
                            'type' => 1,
                            'imgs' => [],
                            'videos' => [],
                        ];
                    }
                    if (isset($val['log_phone']) && $val['log_phone']) {
                        $tip[] = [
                            'title' => '手机号码',
                            'content' => $val['log_phone'],
                            'content_color' => '',
                            'type' => 1,
                            'imgs' => [],
                            'videos' => [],
                        ];
                    }
                    if (isset($val['log_content']) && $val['log_content']) {
                        $tip[] = [
                            'title' => '内容',
                            'content' => $val['log_content'],
                            'content_color' => '',
                            'type' => 1,
                            'imgs' => [],
                            'videos' => [],
                        ];
                    }
                    if (isset($val['log_info']) && $val['log_info'] && @unserialize($val['log_info'])) {
                        $log_info = unserialize($val['log_info']);
                        if (isset($log_info['name']) || isset($log_info['phone'])) {
                            $tip[] = [
                                'title' => '处理人员',
                                'content' => $log_info['name'] . '-' . $log_info['phone'],
                                'content_color' => '',
                                'type' => 1,
                                'imgs' => [],
                                'videos' => [],
                            ];
                        }
                        if (isset($log_info['to_village_name']) || isset($log_info['to_village_name'])) {
                            $tip[] = [
                                'title' => '转到',
                                'content' => $log_info['to_village_name'],
                                'content_color' => '',
                                'type' => 1,
                                'imgs' => [],
                                'videos' => [],
                            ];
                        }
                    }
                    if (isset($val['log_imgs']) && $val['log_imgs']) {
                        $log_imgs = $val['log_imgs'];
                        if ($log_imgs && strpos($log_imgs, ';') !== false) {
                            $log_imgs = explode(';', $log_imgs);
                        } elseif ($log_imgs) {
                            $log_imgs = explode(',', $log_imgs);
                        }
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
                            'videos' => [],
                        ];
                    }
                    if ('work_change_cate' == trim($val['log_name'])) {
                        $log_info = isset($val['log_info']) && $val['log_info'] ? json_decode($val['log_info'], 1) : [];
                        $order_cat_fid = isset($log_info['order_cat_fid']) ? intval($log_info['order_cat_fid']) : 0;
                        $order_cat_id = isset($log_info['order_cat_id']) ? intval($log_info['order_cat_id']) : 0;
                        $order_other_cate_fid = isset($log_info['order_other_cate_fid']) ? intval($log_info['order_other_cate_fid']) : 0;
                        $order_other_cate_id = isset($log_info['order_other_cate_id']) ? intval($log_info['order_other_cate_id']) : 0;

                        $change_cat_fid = isset($log_info['change_cat_fid']) ? intval($log_info['change_cat_fid']) : 0;
                        $change_cat_id = isset($log_info['change_cat_id']) ? intval($log_info['change_cat_id']) : 0;
                        $change_other_cate_fid = isset($log_info['change_other_cate_fid']) ? intval($log_info['change_other_cate_fid']) : 0;
                        $change_other_cate_id = isset($log_info['change_other_cate_id']) ? intval($log_info['change_other_cate_id']) : 0;

                        $cateIds = [];
                        $order_cat_fid > 0 && $cateIds[] = $order_cat_fid;
                        $order_cat_id > 0 && $cateIds[] = $order_cat_id;
                        $order_other_cate_fid > 0 && $cateIds[] = $order_other_cate_fid;
                        $order_other_cate_id > 0 && $cateIds[] = $order_other_cate_id;
                        $change_cat_fid > 0 && $cateIds[] = $change_cat_fid;
                        $change_cat_id > 0 && $cateIds[] = $change_cat_id;
                        $change_other_cate_fid > 0 && $cateIds[] = $change_other_cate_fid;
                        $change_other_cate_id > 0 && $cateIds[] = $change_other_cate_id;

                        $whereQuery = [];
                        $whereQuery[] = ['id', 'in', $cateIds];
                        $cateInfoArr = $dbHouseNewRepairCate->getColumn($whereQuery, 'id, cate_name, charge_type', 'id');

                        if ($order_cat_fid && $order_cat_id) {
                            $catFidName = isset($cateInfoArr[$order_cat_fid]['cate_name']) ? trim($cateInfoArr[$order_cat_fid]['cate_name']) : '';
                            $catIdName = isset($cateInfoArr[$order_cat_id]['cate_name']) ? trim($cateInfoArr[$order_cat_id]['cate_name']) : '';
                            $charge_type = isset($cateInfoArr[$order_cat_id]['charge_type']) ? intval($cateInfoArr[$order_cat_id]['charge_type']) : 0;
                            $text1 = $catFidName . '/' . $catIdName;
                            $charge_type == 1 && $text1 .= "（收费）";
                            $tip[] = [
                                'title' => '改前分类',
                                'content' => $text1,
                                'content_color' => '',
                                'type' => 1,
                                'imgs' => [],
                                'videos' => [],
                            ];
                        }
                        if ($order_other_cate_fid && $order_other_cate_id) {
                            $catFidName = isset($cateInfoArr[$order_other_cate_fid]['cate_name']) ? trim($cateInfoArr[$order_other_cate_fid]['cate_name']) : '';
                            $catIdName = isset($cateInfoArr[$order_other_cate_id]['cate_name']) ? trim($cateInfoArr[$order_other_cate_id]['cate_name']) : '';
                            $charge_type = isset($cateInfoArr[$order_other_cate_id]['charge_type']) ? intval($cateInfoArr[$order_other_cate_id]['charge_type']) : 0;
                            $text1 = $catFidName . '/' . $catIdName;
                            $charge_type == 1 && $text1 .= "（收费）";
                            $tip[] = [
                                'title' => '改前供暖分类',
                                'content' => $text1,
                                'content_color' => '',
                                'type' => 1,
                                'imgs' => [],
                                'videos' => [],
                            ];
                        }

                        if ($change_cat_fid && $change_cat_id) {
                            $catFidName = isset($cateInfoArr[$change_cat_fid]['cate_name']) ? trim($cateInfoArr[$change_cat_fid]['cate_name']) : '';
                            $catIdName = isset($cateInfoArr[$change_cat_id]['cate_name']) ? trim($cateInfoArr[$change_cat_id]['cate_name']) : '';
                            $charge_type = isset($cateInfoArr[$change_cat_id]['charge_type']) ? intval($cateInfoArr[$change_cat_id]['charge_type']) : 0;
                            $text1 = $catFidName . '/' . $catIdName;
                            $charge_type == 1 && $text1 .= "（收费）";
                            $tip[] = [
                                'title' => '改后分类',
                                'content' => $text1,
                                'content_color' => '',
                                'type' => 1,
                                'imgs' => [],
                                'videos' => [],
                            ];
                        }

                        if ($change_other_cate_fid && $change_other_cate_id) {
                            $catFidName = isset($cateInfoArr[$change_other_cate_fid]['cate_name']) ? trim($cateInfoArr[$change_other_cate_fid]['cate_name']) : '';
                            $catIdName = isset($cateInfoArr[$change_other_cate_id]['cate_name']) ? trim($cateInfoArr[$change_other_cate_id]['cate_name']) : '';
                            $charge_type = isset($cateInfoArr[$change_other_cate_id]['charge_type']) ? intval($cateInfoArr[$change_other_cate_id]['charge_type']) : 0;
                            $text1 = $catFidName . '/' . $catIdName;
                            $charge_type == 1 && $text1 .= "（收费）";
                            $tip[] = [
                                'title' => '改后供暖分类',
                                'content' => $text1,
                                'content_color' => '',
                                'type' => 1,
                                'imgs' => [],
                                'videos' => [],
                            ];
                        }
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


    public function getRepairOrderNum($login_role, $worker_id)
    {
        // 初始化 小区车辆 数据层
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $whereRaw = '';
        if (HouseWorkConst::MANAGE_APP_VILLAGE_ADMIN == $login_role) {
            $whereRaw = "(event_status<10) OR (order_type=3 AND uid={$worker_id} AND event_status>=40 AND event_status<70)";
        } elseif (HouseWorkConst::MANAGE_APP_VILLAGE_WORK == $login_role) {
            $whereRaw = "(event_status<=20 AND event_status<40 AND worker_type=0 AND worker_id={$worker_id})";
        }
        $count = $db_house_new_repair_works_order->getCount($whereRaw);
        if (!$count) {
            $count = 0;
        }
        return $count;
    }

    /**
     * 工单操作日志列表
     * @param int $order_id
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2021/08/13
     */
    public function getRepairLog($order_id = 0, $params = [])
    {
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $order_detail = $db_house_new_repair_works_order->getOne(['order_id' => $order_id], '*');
        if (!$order_detail) {
            throw new \think\Exception('对应工单不存在');
        }
        $village_id = $order_detail['village_id'];
        $houseVillageDb = new HouseVillage();
        $village_info = $houseVillageDb->getInfo(array('village_id' => $village_id), '*');
        $db_house_new_repair_works_order_log = new HouseNewRepairWorksOrderLog();
        $system_type = isset($params['system_type']) ? trim($params['system_type']) : '';
        $where_log = [];
        $show_constructor_reject_change = true;
        $constructorRejectChangeLogId = 0;
        if (!$system_type) {
            $show_constructor_reject_change = false;
            $userNot = $this->getUserNotLookLog();
            !empty($userNot) && $where_log[] = ['log_name', 'not in', $userNot];
            $whereHasAgree = [];
            $whereHasAgree[] = ['order_id', '=', $order_id];
            $whereHasAgree[] = ['log_name', '=', 'agree_constructor_project'];
            $hasAgree = $db_house_new_repair_works_order_log->getOne($whereHasAgree, 'log_id');
            if (isset($hasAgree['log_id']) && $hasAgree['log_id']) {
                $whereLastLog = [];
                $whereLastLog[] = ['order_id', '=', $order_id];
                $whereLastLog[] = ['log_name', '=', 'constructor_reject_change'];
                $constructorRejectChange = $db_house_new_repair_works_order_log->getOne($whereLastLog, 'log_id');
                $constructorRejectChangeLogId = isset($constructorRejectChange['log_id']) && $constructorRejectChange['log_id'] ? $constructorRejectChange['log_id'] : 0;
            }
        }
        $where_log[] = ['order_id', '=', $order_id];
        $log_list = $db_house_new_repair_works_order_log->getList($where_log, true, 0, 0, 'log_id DESC');
        $log = [];
        if ($log_list && !$log_list->isEmpty()) {
            $log_list = $log_list->toArray();
            $info = [];
            $log_arr = $this->log_name;
            foreach ($log_list as $val) {
                if( $order_detail['inspection_auto_add'] === 1 ){
                    $val['log_content'] = json_decode(htmlspecialchars_decode($val['log_content']), true);
                }
                if (isset($val['log_name']) && $val['log_name']) {
                    $log_name = $val['log_name'];
                    $log_id = $val['log_id'];
                    if ($log_name == 'constructor_reject_change' && !$show_constructor_reject_change && $log_id != $constructorRejectChangeLogId) {
                        continue;
                    }
                    $log_name_text = isset($log_arr[$log_name]) ? trim($log_arr[$log_name]) : '';
                    if (!$log_name_text) {
                        continue;
                    }
                    if (isset($val['log_num']) && $val['log_num'] > 1) {
                        $title = "第{$val['log_num']}次" . $log_name_text;
                    } else {
                        $title = $log_name_text;
                    }
                    if (isset($val['wisdomqrcode_id']) && $val['wisdomqrcode_id'] > 0 && strpos($val['log_name'], '_submit')) {
                        $title = '巡检转工单';
                    }
                    if (isset($val['add_time']) && $val['add_time']) {
                        $val['add_time_txt'] = date('Y-m-d H:i:s', $val['add_time']);
                    }
                    $tip = [];
                    if ($log_name == 'evaluate') {
                        $tip[] = [
                            'title' => '评分',
                            'content' => $val['evaluate'],
                            'content_color' => '',
                            'type' => 1,
                            'imgs' => [],
                            'videos' => [],
                        ];
                    }
                    if ($log_name == 'work_follow_up' && isset($val['charge_price']) && floatval($val['charge_price']) > 0) {
                        $tip[] = [
                            'title' => '待确认收费金额',
                            'content' => $val['charge_price'],
                            'content_color' => '',
                            'type' => 1,
                            'imgs' => [],
                            'videos' => [],
                        ];
                    }
                    if ($log_name == 'work_completed' && floatval($val['charge_price']) > 0) {
                        $tip[] = [
                            'title' => '收费金额',
                            'content' => $val['charge_price'],
                            'content_color' => '',
                            'type' => 1,
                            'imgs' => [],
                            'videos' => [],
                        ];
                    }
                    if ($log_name == 'not_over_stars_report') {
                        $tip[] = [
                            'title' => '评分',
                            'content' => $val['evaluate'],
                            'content_color' => '',
                            'type' => 1,
                            'imgs' => [],
                            'videos' => [],
                        ];
                    }
                    if ($log_name == 'evaluate' && isset($val['log_content']) && $val['log_content']) {
                        $tip[] = [
                            'title' => '评论详情',
                            'content' => $val['log_content'],
                            'content_color' => '',
                            'type' => 1,
                            'imgs' => [],
                            'videos' => [],
                        ];
                    }
                    if (isset($val['log_operator']) && $val['log_operator']) {
                        $tip[] = [
                            'title' => '操作人员',
                            'content' => $val['log_operator'],
                            'content_color' => '',
                            'type' => 1,
                            'imgs' => [],
                            'videos' => [],
                        ];
                    }
                    if (isset($val['log_phone']) && $val['log_phone']) {
                        $log_phone = $houseVillageDb->phone_desensitization_v($val['log_phone'], $village_id, $village_info);
                        $tip[] = [
                            'title' => '手机号码',
                            'content' => $log_phone,
                            'content_color' => '',
                            'type' => 1,
                            'imgs' => [],
                            'videos' => [],
                        ];
                    }
                    if ($log_name != 'evaluate' && isset($val['log_content']) && $val['log_content']) {
                        $tip[] = [
                            'title' => '内容',
                            'content' => $val['log_content'],
                            'content_color' => '',
                            'type' => 1,
                            'imgs' => [],
                            'videos' => [],
                        ];
                    }
                    if (isset($val['log_info']) && $val['log_info'] && @unserialize($val['log_info'])) {
                        $log_info = unserialize($val['log_info']);
                        if (isset($log_info['name']) || isset($log_info['phone'])) {
                            $tip[] = [
                                'title' => '处理人员',
                                'content' => $log_info['name'] . '-' . $log_info['phone'],
                                'content_color' => '',
                                'type' => 1,
                                'imgs' => [],
                                'videos' => [],
                            ];
                        }
                        if (isset($log_info['to_village_name']) || isset($log_info['to_village_name'])) {
                            $tip[] = [
                                'title' => '转到',
                                'content' => $log_info['to_village_name'],
                                'content_color' => '',
                                'type' => 1,
                                'imgs' => [],
                                'videos' => [],
                            ];
                        }
                    }
                    if (isset($val['log_imgs']) && $val['log_imgs']) {
                        $log_imgs = $val['log_imgs'];
                        if ($log_imgs && strpos($log_imgs, ';') !== false) {
                            $log_imgs = explode(';', $log_imgs);
                        } elseif ($log_imgs) {
                            $log_imgs = explode(',', $log_imgs);
                        }
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
                            'videos' => [],
                        ];
                    }
                    $info[] = [
                        'title' => $title,
                        'log_time' => isset($val['add_time_txt']) ? $val['add_time_txt'] : '',
                        'tip' => $tip,
                        'inspection_auto_add' => $order_detail['inspection_auto_add']
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
     * 添加工单操作日志
     * @param array $data
     * @return int|string
     * @author lijie
     * @date_time 2021/08/13
     */
    public function addRepairLog($data = [], $otherData = array())
    {
        if (empty($data)) {
            return false;
        }
        $db_house_new_repair_works_order_log = new HouseNewRepairWorksOrderLog();
        $id = $db_house_new_repair_works_order_log->addOne($data);
        $community_jpush_appkey = cfg('community_jpush_appkey');
        $community_jpush_secret = cfg('community_jpush_secret');
        if ($otherData && !empty($community_jpush_appkey) && !empty($community_jpush_secret)) {
            if (isset($otherData['opt_type']) && $otherData['opt_type'] == 'assign_to_house_worker' && isset($otherData['wid']) && $otherData['wid'] > 0) {
                //工单指派时 极光推送
                $houseAppJpushInfoService = new HouseAppJpushInfoService();
                $houseAppJpushInfoService->handleJpushInfoSend($otherData, 'repair_works_order');
            } else if (isset($otherData['opt_type']) && $otherData['opt_type'] == 'order_worker_submit') {
                //工单提交时 极光推送
                $houseAppJpushInfoService = new HouseAppJpushInfoService();
                $houseAppJpushInfoService->handleJpushInfoSend($otherData, 'order_worker_submit');
            }
        }
        return $id;
    }

    /**
     * 根据工单分类获取符合条件的工作人员
     * @param int $cate_id
     * @return array|bool
     * @author lijie
     * @date_time 2021/08/13
     */
    public function getWorkerByTime($cate_id = 0, &$repairCateScheduling = [])
    {
        $worker_ids = [];
        if (!$cate_id) {
            return false;
        }
        $nowTime = time();
        $db_house_new_repair_cate = new HouseNewRepairCate();
        $cate_info = $db_house_new_repair_cate->getOne(['id' => $cate_id], true);
        if (empty($cate_info)) {
            $repairCateScheduling = [
                'title' => '工单分类-信息不存在',
                'msg' => '获取工单信息失败',
                'cate_id' => $cate_id,
                'time' => $nowTime
            ];
            return $worker_ids;
        }
        if ($cate_info['type'] == 1) {
            $worker_ids[] = $cate_info['uid'];
            $repairCateScheduling = [
                'title' => '工单分类[负责人设置]单人',
                'msg' => '工单分类负责人设置了单人负责',
                'work_id' => $cate_info['uid'],
                'time' => $nowTime
            ];
            return $worker_ids;
        }
        $w = date("w");
        $h = date("H");
        $where = array();
        $where[] = ['cate_id', '=', $cate_id];
        $where[] = ['date_type', '=', $w];
        $where[] = ['start_time', '<=', $h];
        $where[] = ['end_time', '>', $h];
        $db_house_new_repair_director_scheduling = new HouseNewRepairDirectorScheduling();
        $data = $db_house_new_repair_director_scheduling->getList($where, '*', 0, 0, 'id DESC');
        if (empty($data) && $cate_info['parent_id']) {
            $where = array();
            $where[] = ['cate_id', '=', $cate_info['parent_id']];
            $where[] = ['date_type', '=', $w];
            $where[] = ['start_time', '<=', $h];
            $where[] = ['end_time', '>', $h];
            $data = $db_house_new_repair_director_scheduling->getList($where, '*', 0, 0, 'id DESC');
        }
        $worker24uid = array();
        if ($data) {
            foreach ($data as $v) {
                $director_uid = $v['director_uid'];
                if ($director_uid) {
                    $director_uid = array_filter(explode(',', $director_uid));
                    if ($v['start_time'] == 0 && ($v['end_time'] == 0 && $v['end_time'] == 24)) {
                        //0到24小时的
                        $worker24uid = array_merge($worker24uid, $director_uid);
                    } else {
                        $worker_ids = array_merge($worker_ids, $director_uid);
                    }
                }
            }
        }
        if (empty($worker_ids) && !empty($worker24uid)) {
            $worker_ids = $worker24uid;
        }
        if ($worker_ids) {
            $worker_ids = array_unique($worker_ids);
        }
        $repairCateScheduling = [
            'title' => '工单分类[负责人设置]多人',
            'msg' => '工单分类负责人设置了多人负责',
            'work_id' => $cate_info['uid'],
            'time' => $nowTime
        ];
        return $worker_ids;
    }

    /**
     * 添加工单
     * @param array $data
     * @return int|string
     * @author lijie
     * @date_time 2021/08/13
     */
    public function addRepairOrder($data = [])
    {
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $id = $db_house_new_repair_works_order->addOne($data);
        return $id;
    }

    /**
     * 工单详情
     * @param array $where
     * @param bool $field
     * @param string $source_type
     * @param array $params
     * @author lijie
     * @date_time 2021/08/17
     */
    public function getWorkOrderDetail($where = [], $field = '*', $source_type = '', $cfrom = '', $params = [])
    {
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $db_house_new_repair_cate = new HouseNewRepairCate();
        $db_house_new_repair_subject = new HouseNewRepairSubject();
        $orderInfo = $db_house_new_repair_works_order->getOne($where, $field);
        $order_arr = [];
        $order_detail_arr = [];
        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        $nowHandleVillageId = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $mark_report = isset($params['mark_report']) ? intval($params['mark_report']) : 0;
        $is_word = isset($params['is_word']) ? intval($params['is_word']) : 0;
        $change_status_word = [];
        $look_contact = 0;
        $contact_content = '';
        if ($orderInfo) {
            if( $orderInfo['inspection_auto_add'] === 1 ){
                $orderInfo['order_content'] = json_decode( htmlspecialchars_decode($orderInfo['order_content']) ,true);
            }
            $showTab = 1;
            $now_handle_village_id = isset($orderInfo['now_handle_village_id']) ? intval($orderInfo['now_handle_village_id']) : 0;
            $order_village_id = isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : 0;

            if ($jz_open && $now_handle_village_id && $now_handle_village_id != $order_village_id) {
                // 禁止工单中心操作
                $showTab = 4;
            }

            $houseVillageDb = new HouseVillage();
            $village_info = $houseVillageDb->getInfo(array('village_id' => $order_village_id), '*');
            if (isset($orderInfo['phone']) && !empty($orderInfo['phone'])) {
                $orderInfo['phone'] = $houseVillageDb->phone_desensitization_v($orderInfo['phone'], $order_village_id, $village_info);
            }
            if ($jz_open && $now_handle_village_id && $nowHandleVillageId && $now_handle_village_id == $nowHandleVillageId) {
                // 如果当前操作对象是被转单小区支持操作
                $showTab = 1;
            }
            if ($mark_report == 1) {
                // 禁止工单中心操作
                $showTab = 4;
            }
            $orderInfo['showTab'] = $showTab;
            $orderInfo['add_time_txt'] = date('Y-m-d H:i:s', $orderInfo['add_time']);
            $order_imgs = '';
            if ($orderInfo['order_imgs'] && strpos($orderInfo['order_imgs'], ';') !== false) {
                $order_imgs = explode(';', $orderInfo['order_imgs']);
            } elseif ($orderInfo['order_imgs']) {
                $order_imgs = explode(',', $orderInfo['order_imgs']);
            }
            if ($order_imgs) {
                $order_imgs_arr = [];
                foreach ($order_imgs as $v) {
                    $order_imgs_arr[] = replace_file_domain($v);
                }
            }
            $order_videos = '';
            if ($orderInfo['videos'] && strpos($orderInfo['videos'], ';') !== false) {
                $order_videos = explode(';', $orderInfo['videos']);
            } elseif ($orderInfo['videos']) {
                $order_videos = explode(',', $orderInfo['videos']);
            }
            if ($order_videos) {
                $videos_arr = [];
                foreach ($order_videos as $v1) {
                    $video = replace_file_domain($v1);
                    $videoImage = $video . '.png';
                    $videos_arr[] = [
                        'video' => $video,
                        'image' => $videoImage,
                    ];
                }
            }
            $orderInfo['order_imgs'] = isset($order_imgs_arr) ? $order_imgs_arr : $order_imgs;
            $orderInfo['order_videos'] = isset($videos_arr) ? $videos_arr : $order_videos;
            $orderInfo['go_time_txt'] = !empty($orderInfo['go_time']) ? date('Y-m-d H:i:s', $orderInfo['go_time']) : '无';
            $db_house_new_repair_cate_custom = new HouseNewRepairCateCustom();
            if ($orderInfo['label_txt']) {
                $tag_list = $db_house_new_repair_cate_custom->getList([['id', 'in', $orderInfo['label_txt']]], 'name')->toArray();
            } else {
                $tag_list = [];
            }
            $tags = '';
            if ($tag_list) {
                foreach ($tag_list as $value) {
                    $tags .= $value['name'] . ';';
                }
                $tags = rtrim($tags, ';');
            }
            $orderInfo['pigcms_id'] = $orderInfo['bind_id'];
            $orderInfo['tags'] = $tags;
            // 补充地址
            $plugAddress = '';
            if ($source_type == 'village' || $source_type == 'platform') {
                $orderInfo['event_status_txt'] = $orderInfo['event_status'] ? $this->order_status_txt[$orderInfo['event_status']] : '';
                $otherVillage = false;
                if ($jz_open && $now_handle_village_id && $nowHandleVillageId && $now_handle_village_id != $order_village_id && $now_handle_village_id == $nowHandleVillageId) {
                    // 其他小区转单非本小区工单
                    $otherVillage = true;
                    $villageInfo = (new HouseVillage())->getOne($orderInfo['village_id'], 'village_id, village_name');
                    $plugAddress = isset($villageInfo['village_name']) ? trim($villageInfo['village_name']) . ' ' : '';
                }
                if ($otherVillage && in_array($orderInfo['event_status'], [16, 17, 26, 27])) {
                    // 其他小区转单非本小区工单
                    $orderInfo['event_status_txt'] = '被转单';
                } elseif ($otherVillage && $plugAddress) {
                    $orderInfo['event_status_txt'] .= "({$plugAddress}转单)";
                } elseif ($jz_open && $now_handle_village_id && (!$nowHandleVillageId || $order_village_id == $nowHandleVillageId)) {
                    // 如果当前操作对象是被转单小区支持操作
                    $villageInfo = (new HouseVillage())->getOne($now_handle_village_id, 'village_id, village_name');
                    $villageAddress = isset($villageInfo['village_name']) ? trim($villageInfo['village_name']) . ' ' : '';
                    $orderInfo['event_status_txt'] .= "(转单至{$villageAddress})";
                }
            } else {
                $orderInfo['event_status_txt'] = $orderInfo['event_status'] ? $this->order_user_status_txt[$orderInfo['event_status']] : '';
            }

            $orderInfo['event_status_color'] = $orderInfo['event_status'] ? $this->order_status_color[$orderInfo['event_status']] : '';
            $db_house_new_repair_works_order_log = new HouseNewRepairWorksOrderLog();
            $record = $db_house_new_repair_works_order_log->getOne(['order_id' => $orderInfo['order_id']], 'log_id,log_operator,operator_id,log_phone,log_name,add_time,log_imgs,log_content');
            if ($record) {
                $record = $record->toArray();
                if( $orderInfo['inspection_auto_add'] === 1 ){
                    $record['log_content'] = json_decode( htmlspecialchars_decode($record['log_content']) ,true);
                }
                if ($record['log_imgs']) {
                    if (strpos($record['log_imgs'], ';') > 0) {
                        $record['log_imgs'] = explode(';', $record['log_imgs']);
                    } else {
                        $record['log_imgs'] = explode(',', $record['log_imgs']);
                    }
                    foreach ($record['log_imgs'] as $k => $v) {
                        $record['log_imgs'][$k] = dispose_url($v);
                    }
                }
                $record['log_phone'] = $houseVillageDb->phone_desensitization_v($record['log_phone'], $order_village_id, $village_info);
                $content0628 = $record['log_operator'] . ',' . $record['log_phone'];
                if (intval($orderInfo['order_type']) == 5 && empty($record['log_operator'])) {
                    $content0628 = '处理中心管理员';
                }
                $orderInfo['log_info'] = [
                    "title" => "处理记录",
                    "type" => 2,
                    "content" => "",
                    "content_color" => "",
                    "children" => [
                        [
                            "title" => "类型",
                            "content" => $this->log_name[$record['log_name']],
                            "content_color" => ""
                        ],
                        [
                            "title" => "时间",
                            "content" => date('Y-m-d H:i:s', $record['add_time']),
                            "content_color" => ""
                        ],
                        [
                            "title" => "当前处理人员",
                            "content" => $content0628,
                            "content_color" => ""
                        ],
                        [
                            "title" => "内容",
                            "content" => $record['log_content'],
                            "content_color" => ""
                        ],
                    ],
                    "imgs" => $record['log_imgs'],
                    "videos" => []
                ];
            } else {
                $orderInfo['log_info'] = [];
            }

            $orderInfo['log'] = [
                "title" => "处理记录",
                "type" => 2,
                "content" => "",
                "content_color" => "",
                "children" => [
                    [
                        "title" => "当前处理人员",
                        "content" => $record['log_operator'],
                        "content_color" => ""
                    ],
                    [
                        "title" => "手机号码",
                        "content" => $record['log_phone'],
                        "content_color" => ""
                    ]
                ],
                "imgs" => [],
                "videos" => []
            ];
            if ($cfrom == 'user') {
                $whereArr = array(['order_id', '=', $orderInfo['order_id']]);
                $user_log_name = array('user_submit', 'reopen,recall', 'closed', 'evaluate', 'agree');
                $whereArr[] = array('log_name', 'not in', $user_log_name);
                $user_record_obj = $db_house_new_repair_works_order_log->getOne($whereArr, 'log_id,log_operator,log_phone,log_name,add_time,log_imgs,log_content');
                if ($user_record_obj && !$user_record_obj->isEmpty()) {
                    $user_record_obj['log_phone'] = $houseVillageDb->phone_desensitization_v($user_record_obj['log_phone'], $order_village_id, $village_info);
                    $orderInfo['log'] = [
                        "title" => "处理记录",
                        "type" => 2,
                        "content" => "",
                        "content_color" => "",
                        "children" => [
                            [
                                "title" => "当前处理人员",
                                "content" => $user_record_obj['log_operator'],
                                "content_color" => ""
                            ],
                            [
                                "title" => "手机号码",
                                "content" => $user_record_obj['log_phone'],
                                "content_color" => ""
                            ]
                        ],
                        "imgs" => []
                    ];
                } else {
                    $orderInfo['log'] = [
                        "title" => "处理记录",
                        "type" => 2,
                        "content" => "",
                        "content_color" => "",

                        "imgs" => []
                    ];
                }
            }
            $address = '无';
            if ($orderInfo['address_type'] == 'room') {
                $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
                $service_house_village = new HouseVillageService();
                $vacancy_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id' => $orderInfo['room_id']], 'village_id,single_id,floor_id,layer_id,pigcms_id');
                $address = $service_house_village->getSingleFloorRoom($vacancy_info['single_id'], $vacancy_info['floor_id'], $vacancy_info['layer_id'], $vacancy_info['pigcms_id'], $vacancy_info['village_id']);
            }
            if ($orderInfo['address_type'] == 'public') {
                $db_house_village_public_area = new HouseVillagePublicArea();
                $db_wisdom_qrcode = new WisdomQrcode();
                $public_info = $db_house_village_public_area->getOne(['public_area_id' => $orderInfo['public_id']], 'public_area_name');
                if( $orderInfo['inspection_auto_add'] == 1 ){
                    $qrcodeInfo = $db_wisdom_qrcode->getFind(['id'=>$orderInfo['address_id']]);
                    $address = $qrcodeInfo ? $qrcodeInfo['name'] : '';
                }else{
                    $address = $public_info['public_area_name'];
                }
            }
            if ($address != '无' || !$orderInfo['address_txt']) {
                $orderInfo['address_txt'] = $plugAddress . $address;
            }
            $subject_name = '';
            /*  if(isset($orderInfo['category_id'])){
                $subject_info = $db_house_new_repair_subject->getOne(['id'=>$orderInfo['category_id']],'subject_name');
                if($subject_info){
                    $subject_name = $subject_info['subject_name'];
                }
            }*/

            if ($orderInfo['cat_fid']) {
                $subject_info = $db_house_new_repair_cate->getOne(['id' => $orderInfo['cat_fid']], 'cate_name');
                $subject_name = isset($subject_info['cate_name']) ? $subject_info['cate_name'] : '';
            }

            $cate_name = '';
            /*   if($orderInfo['cat_fid']){
                $cate_info = $db_house_new_repair_cate->getOne(['id'=>$orderInfo['cat_fid'],'status'=>1],'cate_name');
                $cate_name = $cate_info['cate_name'];
            }
            if ($orderInfo['cat_id']) {
                $cate_info = $db_house_new_repair_cate->getOne(['id' => $orderInfo['cat_id'], 'status' => 1], 'cate_name');
                if (!empty($cate_name)) {
                    $cate_name = $cate_name . '/' . $cate_info['cate_name'];
                } else {
                    $cate_name = $cate_info['cate_name'];
                }
            }*/

            if ($orderInfo['cat_id']) {
                $subject_info = $db_house_new_repair_cate->getOne(['id' => $orderInfo['cat_id']], 'cate_name');
                $cate_name = isset($subject_info['cate_name']) ? $subject_info['cate_name'] : '';
            }
            $orderInfo['cate_name'] = $cate_name;
            if ($orderInfo['event_status'] >= 40 && $orderInfo['event_status'] < 50) {
                $cate_not_timely_report_switch = 0;
                $event_status_type = [];
                if ($is_word) {
                    $change_status_word = [];
                }
                if ($jz_open) {
                    $db_house_community_quality_bind_repair_cate = new HouseCommunityQualityBindRepairCate();
                    $whereBindCate = [];
                    $order_village_id && $whereBindCate[] = ['village_id', '=', $order_village_id];
                    if (isset($orderInfo['cat_fid']) && $orderInfo['cat_fid']) {
                        $whereBindCate[] = ['repair_cate_fid', '=', $orderInfo['cat_fid']];
                    }
                    $whereBindCate[] = ['repair_cate_id', '=', $orderInfo['cat_id']];
                    $repairCateInfo = $db_house_community_quality_bind_repair_cate->getOne($whereBindCate, 'not_timely_report_switch');
                    $cate_not_timely_report_switch = isset($repairCateInfo['not_timely_report_switch']) ? intval($repairCateInfo['not_timely_report_switch']) : 0;
                    fdump_api($record, '$record');
                    $workCompletedWid = 0;
                    if (isset($record['operator_id']) && $record['operator_id']) {
                        $workCompletedWid = $record['operator_id'];
                    } else {
                        $whereLog1 = [];
                        $whereLog1[] = ['order_id', '=', $orderInfo['order_id']];
                        $whereLog1[] = ['log_name', '=', 'work_completed'];
                        $workCompletedRecord = $db_house_new_repair_works_order_log->getOne($whereLog1, 'log_id,log_operator,operator_id,log_phone,log_name,add_time,log_imgs,log_content');
                        $workCompletedWid = isset($workCompletedRecord['operator_id']) && $workCompletedRecord['operator_id'] ? $workCompletedRecord['operator_id'] : 0;
                    }
                    if ($workCompletedWid) {
                        $worker = (new HouseWorker())->getOne(['wid' => intval($workCompletedWid)], 'wid, village_id, is_constructor');
                        $is_constructor = isset($worker['is_constructor']) && $worker['is_constructor'] ? intval($worker['is_constructor']) : 0;
                        $completeWordVillageId = isset($worker['village_id']) && $worker['village_id'] ? intval($worker['village_id']) : 0;
                        if ($is_constructor == 1) {
                            $event_status_type[] = ['name' => '物业维修（付费）', 'type' => 'reopen_by_money'];
                            if ($is_word) {
                                $change_status_word[] = 'reopen_by_money';
                            }
                        } else {
                            $sHouseCommunityAboutService = new HouseCommunityAboutService();
                            $developerConfig = $sHouseCommunityAboutService->getVillageFacilitiesInfo($completeWordVillageId, 'developer');
                            if (isset($developerConfig['is_virtual']) && intval($developerConfig['is_virtual']) == 1) {
                                $event_status_type[] = ['name' => '物业维修（付费）', 'type' => 'reopen_by_money'];
                                if ($is_word) {
                                    $change_status_word[] = 'reopen_by_money';
                                }
                            }
                        }
                    }
                } else {
                    $event_status_type[] = ['name' => '重新打开', 'type' => 'reopen'];
                    if ($is_word) {
                        $change_status_word[] = 'reopen';
                    }
                }
                if (!$jz_open) {
                    $event_status_type[] = ['name' => '撤回', 'type' => 'recall'];
                    if ($is_word) {
                        $change_status_word[] = 'recall';
                    }
                    $event_status_type[] = ['name' => '关闭', 'type' => 'closed'];
                    if ($is_word) {
                        $change_status_word[] = 'closed';
                    }
                }
                if ($cate_not_timely_report_switch != 1) {
                    $event_status_type[] = ['name' => '评价', 'type' => 'evaluate'];
                    if ($is_word) {
                        $change_status_word[] = 'evaluate';
                    }
                }
                $orderInfo['event_status_type'] = $event_status_type;
            }
            if ($orderInfo['event_status'] > 9 && $orderInfo['event_status'] < 40) {
                $event_status_type = [];
                if ($is_word) {
                    $change_status_word = [];
                }
                $event_status_type[] = ['name' => '撤回', 'type' => 'recall'];
                if ($is_word) {
                    $change_status_word[] = 'recall';
                }
                $event_status_type[] = ['name' => '关闭', 'type' => 'closed'];
                if ($is_word) {
                    $change_status_word[] = 'closed';
                }
                $orderInfo['event_status_type'] = $event_status_type;
            }
            if ($orderInfo['event_status'] == 70) {
                $event_status_type = [];
                if ($is_word) {
                    $change_status_word = [];
                }
                if (!$jz_open) {
                    $event_status_type[] = ['name' => '重新打开', 'type' => 'reopen'];
                    if ($is_word) {
                        $change_status_word[] = 'reopen';
                    }
                    $event_status_type[] = ['name' => '撤回', 'type' => 'recall'];
                    if ($is_word) {
                        $change_status_word[] = 'recall';
                    }
                    $event_status_type[] = ['name' => '关闭', 'type' => 'closed'];
                    if ($is_word) {
                        $change_status_word[] = 'closed';
                    }
                }
                $orderInfo['event_status_type'] = $event_status_type;
            }
            if (!$jz_open && ($orderInfo['event_status'] == 50 || $orderInfo['event_status'] == 60)) {
                $orderInfo['event_status_type'] = [['name' => '重新打开', 'type' => 'reopen']];
                if ($is_word) {
                    $change_status_word = [];
                    $change_status_word[] = 'reopen';
                }
            }
            if ($orderInfo['event_status'] == 35) {
                $typeArr = [];
                if ($is_word) {
                    $change_status_word = [];
                }
                $typeArr[] = ['name' => '同意', 'type' => 'agree'];
                if ($is_word) {
                    $change_status_word[] = 'agree';
                }
                if ($jz_open) {
                    $typeArr[] = ['name' => '重新提供方案', 'type' => 'reject'];
                    if ($is_word) {
                        $change_status_word[] = 'reject';
                    }
                }
                $typeArr[] = ['name' => '关闭', 'type' => 'closed'];
                if ($is_word) {
                    $change_status_word[] = 'closed';
                }
                $orderInfo['event_status_type'] = $typeArr;
            }
            if ($is_word) {
                return $change_status_word;
            }
            $order_arr[] = [
                "title" => "上报位置",
                "type" => 1,
                "content" => $plugAddress . $address,
                "content_color" => "",
                "children" => [],
                "imgs" => [],
                "videos" => []
            ];
            $order_arr[] = [
                "title" => "上报时间",
                "type" => 1,
                "content" => $orderInfo['add_time_txt'],
                "content_color" => "",
                "children" => [],
                "imgs" => [],
                "videos" => []
            ];
            $order_arr[] = [
                "title" => "工单类目",
                "type" => 1,
                "content" => $subject_name,
                "content_color" => "",
                "children" => [],
                "imgs" => [],
                "videos" => []
            ];
            $order_arr[] = [
                "title" => "工单分类",
                "type" => 1,
                "content" => $orderInfo['cate_name'],
                "content_color" => "",
                "children" => [],
                "imgs" => [],
                "videos" => []
            ];
            $order_arr[] = [
                "title" => "上门时间",
                "type" => 1,
                "content" => $orderInfo['go_time_txt'],
                "content_color" => "",
                "children" => [],
                "imgs" => [],
                "videos" => []
            ];
            $order_arr[] = [
                "title" => "自定义字段",
                "type" => 1,
                "content" => $orderInfo['tags'],
                "content_color" => "",
                "children" => [],
                "imgs" => [],
                "videos" => []
            ];
            if (empty($orderInfo['order_content'])) {
                $orderInfo['order_content'] = '无';
            }
            $order_arr[] = [
                "title" => "补充内容",
                "type" => 1,
                "content" => $orderInfo['inspection_auto_add'] === 1 ? '' :$orderInfo['order_content'],
                "content_color" => "",
                "children" => [],
                "imgs" => [],
                "videos" => []
            ];
            if (!empty($orderInfo['order_imgs'])) {
                $order_arr[] = [
                    "title" => "上报图例",
                    "type" => 3,
                    "content" => "",
                    "content_color" => "",
                    "children" => [],
                    "imgs" => $orderInfo['order_imgs'],
                    "videos" => []
                ];
            } else {
                $order_arr[] = [
                    "title" => "上报图例",
                    "type" => 1,
                    "content" => "无",
                    "content_color" => "",
                    "children" => [],
                    "imgs" => [],
                    "videos" => []
                ];
            }
            if (!empty($orderInfo['order_videos'])) {
                $order_arr[] = [
                    "title" => "上报视频",
                    "type" => 4,
                    "content" => "",
                    "content_color" => "",
                    "children" => [],
                    "videos" => $orderInfo['order_videos']
                ];
            } elseif ($jz_open) {
                $order_arr[] = [
                    "title" => "上报视频",
                    "type" => 1,
                    "content" => "无",
                    "content_color" => "",
                    "children" => [],
                    "imgs" => [],
                    "videos" => []
                ];
            }
            $order_arr[] = [
                "title" => "工单状态",
                "type" => 1,
                "content" => $orderInfo['event_status_txt'],
                "content_color" => $orderInfo['event_status_color'],
                "children" => [],
                "imgs" => [],
                "videos" => []
            ];
            $orderInfo['charge_type'] = false;
            if ($orderInfo['event_status'] == 35) {
                $orderInfo['charge_type'] = true;
                $order_arr[] = [
                    "title" => "应收金额",
                    "type" => 1,
                    "content" => $orderInfo['charge_price'],
                    "content_color" => "",
                    "children" => [],
                    "imgs" => [],
                    "videos" => []
                ];
                $orderInfo['pay_type'] = [['key' => '1', 'type' => '线上缴费'], ['key' => '2', 'type' => '线下缴费']];
            }
            //=====工单中心使用=======
            $order_detail_arr[] = [
                "title" => "上报人员",
                "type" => 1,
                "content" => $orderInfo['name'],
                "content_color" => "",
                "children" => [],
                "imgs" => [],
                "videos" => []
            ];
            $order_detail_arr[] = [
                "title" => "手机号码",
                "type" => 1,
                "content" => $orderInfo['phone'],
                "content_color" => "",
                "children" => [],
                "imgs" => [],
                "videos" => []
            ];
            $order_detail_arr[] = [
                "title" => "上报时间",
                "type" => 1,
                "content" => $orderInfo['add_time_txt'],
                "content_color" => "",
                "children" => [],
                "imgs" => [],
                "videos" => []
            ];
            $order_detail_arr[] = [
                "title" => "上门时间",
                "type" => 1,
                "content" => $orderInfo['go_time_txt'],
                "content_color" => "",
                "children" => [],
                "imgs" => [],
                "videos" => []
            ];
            $order_detail_arr[] = [
                "title" => "上报位置",
                "type" => 1,
                "content" => $orderInfo['address_txt'],
                "content_color" => "",
                "children" => [],
                "imgs" => [],
                "videos" => []
            ];
            $order_detail_arr[] = [
                "title" => "工单类目",
                "type" => 1,
                "content" => $subject_name,
                "content_color" => "",
                "children" => [],
                "imgs" => [],
                "videos" => []
            ];
            $order_detail_arr[] = [
                "title" => "上报分类",
                "type" => 3,
                "content" => $orderInfo['cate_name'],
                "content_color" => "",
                "children" => [],
                "imgs" => [],
                "videos" => []
            ];
            $order_detail_arr[] = [
                "title" => "自定义字段",
                "type" => 1,
                "content" => $orderInfo['tags'],
                "content_color" => "",
                "children" => [],
                "imgs" => [],
                "videos" => []
            ];
            $order_detail_arr[] = [
                "title" => "补充内容",
                "type" => 1,
                "content" => $orderInfo['order_content'],
                "content_color" => "",
                "children" => [],
                "imgs" => [],
                "videos" => []
            ];
            if ($jz_open) {
                $now_handle_village_id = isset($orderInfo['now_handle_village_id']) ? intval($orderInfo['now_handle_village_id']) : 0;
                !$now_handle_village_id && $now_handle_village_id = isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : 0;
                // 判断是否有获取联系方式
                $house_community_work_order_config = new HouseCommunityWorkOrderConfig();
                $whereOrderConfig = ['works_order_id' => $orderInfo['order_id']];
                $workOrderConfig = $house_community_work_order_config->getOne($whereOrderConfig, 'works_order_id, look_contact, contact_content');

                $house_community_config = new HouseCommunityConfig();
                $whereConfig = [];
                $whereConfig[] = ['village_id', '=', $now_handle_village_id];
                $orderCommunityConfig = $house_community_config->getOne($whereConfig, 'id, auto_contact_available');
                $auto_contact_available = isset($orderCommunityConfig['auto_contact_available']) ? intval($orderCommunityConfig['auto_contact_available']) : 0;
                $look_contact = isset($workOrderConfig['look_contact']) ? intval($workOrderConfig['look_contact']) : 0;
                $contact_content = isset($workOrderConfig['contact_content']) ? $workOrderConfig['contact_content'] : '';

                $db_house_community_about = new HouseCommunityAbout();
                $whereAbout[] = ['village_id', '=', $now_handle_village_id];
                $whereAbout[] = ['is_virtual', '=', 1];
                $aboutInfo = $db_house_community_about->getOne($whereAbout, 'facilities_type, is_contact_available');
                $is_contact_available = isset($aboutInfo['is_contact_available']) ? intval($aboutInfo['is_contact_available']) : 0;
                $facilities_type = isset($aboutInfo['facilities_type']) ? $aboutInfo['facilities_type'] : '';
                if ($is_contact_available == 0 && !empty($aboutInfo) && $facilities_type == 'supply_station') {
                    $auto_contact_available = 1;
                }

                fdump_api(['$orderCommunityConfig' => $orderCommunityConfig, 'now_handle_village_id' => $now_handle_village_id, 'auto_contact_available' => $auto_contact_available, '$look_contact' => $look_contact, '$contact_content' => $contact_content], '$contact_content');
                if (!$contact_content && $is_contact_available == 0 && $auto_contact_available == 1) {
                    $db_house_village = new HouseVillage();
                    $villageInfo = $db_house_village->getOne($now_handle_village_id, '*');
                    $village_name = isset($villageInfo['village_name']) ? trim($villageInfo['village_name']) : '';
                    $property_phone = isset($villageInfo['property_phone']) && $villageInfo['property_phone'] ? str_replace(' ', '；', $villageInfo['property_phone']) : '';
                    $property_phone = $houseVillageDb->phone_desensitization_v($property_phone, $now_handle_village_id, $villageInfo);
                    $property_address = isset($villageInfo['property_address']) ? trim($villageInfo['property_address']) : '';
                    $contact_content = "请联系{$village_name}，联系方式：{$property_phone}";
                    $property_address && $contact_content .= "\n地址：{$property_address}";
                } elseif (isset($record['log_name']) && in_array($record['log_name'], ['has_contact_supply_station', 'has_contact_developer'])) {
                    // 上一次操作是 获取联系
                    $contact_content = $record['log_content'];
                }
                $hasLook = ($look_contact == 0 && $contact_content) ? 1 : 0;
                $orderInfo['hasLook'] = $hasLook;
                $hasLook == 1 && $orderInfo['contact_content'] = $contact_content;
                $contact_content && $order_arr[] = [
                    "title" => "联系方式",
                    "type" => 1,
                    "content" => $contact_content,
                    "content_color" => "",
                    "children" => [],
                    "imgs" => [],
                    "videos" => []
                ];
                if ($contact_content) {
                    $look_contact++;
                    $house_community_work_order_config->updateThis($whereOrderConfig, ['look_contact' => $look_contact, 'contact_content' => $contact_content]);
                }
                if ($look_contact > 0) {
                    // 自动结单
                    (new HouseCommunityWorkOrderService())->autoWorkCompleted($orderInfo);
                }
            }
        }
        return ['order_detail' => $orderInfo, 'order_arr' => $order_arr, 'order_detail_arr' => $order_detail_arr];
    }

    /**
     * 修改订单
     * @param int $order_id
     * @param string $event_status_type
     * @param string $log_content
     * @param array $log_imgs
     * @param int $evaluate
     * @param array $bind_info
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2021/08/17
     */
    public function updateWorkOrder($order_id = 0, $event_status_type = '', $log_content = '', $log_imgs = [], $evaluate = 0, $bind_info = [], $pay_type = 0, $offline_pay_type = 0, $params = [])
    {
        if (!$order_id || empty($event_status_type)) {
            throw new \think\Exception('请上报处理类型');
        }
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $db_house_new_repair_works_order_log = new HouseNewRepairWorksOrderLog();
        $orderInfo = $db_house_new_repair_works_order->getOne(['order_id' => $order_id], true);

        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        $app_type = isset($params['app_type']) && $params['app_type'] ? $params['app_type'] : '';
        if ($orderInfo['event_status'] >= 40 && $orderInfo['event_status'] < 50) {
            $cate_not_timely_report_switch = 0;
            if ($jz_open) {
                $db_house_community_quality_bind_repair_cate = new HouseCommunityQualityBindRepairCate();
                $whereBindCate = [];
                isset($orderInfo['village_id']) && $orderInfo['village_id'] && $whereBindCate[] = ['village_id', '=', $orderInfo['village_id']];
                if (isset($orderInfo['cat_fid']) && $orderInfo['cat_fid']) {
                    $whereBindCate[] = ['repair_cate_fid', '=', $orderInfo['cat_fid']];
                }
                $whereBindCate[] = ['repair_cate_id', '=', $orderInfo['cat_id']];
                $repairCateInfo = $db_house_community_quality_bind_repair_cate->getOne($whereBindCate, 'not_timely_report_switch');
                $cate_not_timely_report_switch = isset($repairCateInfo['not_timely_report_switch']) ? intval($repairCateInfo['not_timely_report_switch']) : 0;
            }
        }
        $wordParams = ['is_word' => 1];
        $change_status_word = $this->getWorkOrderDetail(['order_id' => $order_id], true, '', 'user', $wordParams);
        fdump_api($change_status_word, '$change_status_word');

        if (!in_array($event_status_type, $change_status_word)) {
            throw new \think\Exception('请上报正确处理类型');
        }
        if ($jz_open) {
            $single_id = isset($orderInfo['single_id']) && intval($orderInfo['single_id']) > 0 ? intval($orderInfo['single_id']) : 0;
            $room_id = isset($orderInfo['room_id']) && intval($orderInfo['room_id']) > 0 ? intval($orderInfo['room_id']) : 0;
            $judgeParams = [
                'village_id' => $orderInfo['village_id'],
                'repair_cate_id' => $orderInfo['cat_id'],
                'jz_open' => $jz_open,
                'single_id' => $single_id,
                'room_id' => $room_id,
            ];
            $hasFee = (new HouseCommunityQualityService())->judgeRepairCateQualityFee($judgeParams);
        } else {
            $hasFee = false;
        }
        $property_id = $orderInfo['property_id'];
        $rule_id = 0;
        $project_id = 0;
        $charge_price = 0;
        $where_log = array('order_id' => $order_id, 'operator_type' => 10, 'log_name' => 'work_follow_up');
        $work_follow_up_info = $db_house_new_repair_works_order_log->getOne($where_log);
        if (!empty($work_follow_up_info)) {
            $rule_id = $work_follow_up_info['rule_id'];
            $project_id = $work_follow_up_info['project_id'];
            $charge_price = $work_follow_up_info['charge_price'];
        }
        $integral = 0; //获得的积分
        if ($event_status_type == 'evaluate') {
            $houseVillageService = new HouseVillageService();
            $whereArrTmp = ['village_id' => $orderInfo['village_id']];
            $villageInfoExtend = $houseVillageService->getHouseVillageInfoExtend($whereArrTmp);
            if (!empty($villageInfoExtend) && isset($villageInfoExtend['work_order_extra'])) {
                $work_order_extra = json_decode($villageInfoExtend['work_order_extra'], 1);
                if ($work_order_extra && isset($work_order_extra['per_works_order_integral']) && ($work_order_extra['per_works_order_integral'] > 0)) {
                    //查询此工单可获取过积分了
                    $logWhere = array();
                    $logWhere[] = array('order_id', '=', $order_id);
                    $logWhere[] = array('get_integral', '>', 0);
                    $repair_works_order_log = $db_house_new_repair_works_order_log->getOne($logWhere);
                    if ($repair_works_order_log && !$repair_works_order_log->isEmpty()) {
                        //此工单获得过积分 不可再获得
                    } else {
                        if (!empty($bind_info) && $bind_info['uid'] && $bind_info['uid'] > 0) {
                            //查询此uid今天已经获取过的总积分
                            $logWhere = array();
                            $logWhere[] = array('village_id', '=', $orderInfo['village_id']);
                            $logWhere[] = array('log_uid', '=', $bind_info['uid']);
                            $logWhere[] = array('get_integral', '>', 0);
                            $tmpdate = date('Y-m-d') . " 00:00:00";
                            $strattime = strtotime($tmpdate);
                            $logWhere[] = array('add_time', '>=', $strattime);
                            $tmpdate = date('Y-m-d') . " 23:59:59";
                            $endtime = strtotime($tmpdate);
                            $logWhere[] = array('add_time', '<=', $endtime);
                            $all_get_integral = $db_house_new_repair_works_order_log->getSum($logWhere, 'get_integral');
                            $tmpintegral = $all_get_integral + $work_order_extra['per_works_order_integral'];
                            if ($work_order_extra['day_works_order_integral'] == 0 || ($work_order_extra['day_works_order_integral'] >= $tmpintegral)) {
                                //没超过总积分 还可以获得积分
                                $integral = $work_order_extra['per_works_order_integral'];
                                $commonUserService = new commonUserService();
                                //加积分
                                $scoreParam = array('order_type' => 'repair_works_order_evaluate', 'score_type' => 20, 'order_id' => $order_id, 'house_type' => 1, 'village_id' => $orderInfo['village_id']);
                                $ret = $commonUserService->addScore($bind_info['uid'], $integral, '社区工单评价加积分', 0, $scoreParam);
                                fdump_api(['uid' => $bind_info['uid'], 'integral' => $integral, 'ret' => $ret], '00updateWorkOrderAddintegral', 1);
                            }
                        }
                    }
                }
            }
        }
        $addRepairLogArr = [
            'order_id' => $order_id,
            'log_name' => $event_status_type,
            'operator_type' => 10,
            'log_content' => $log_content,
            'log_imgs' => !empty($log_imgs) ? implode(',', $log_imgs) : '',
            'add_time' => time(),
            'log_operator' => isset($bind_info['name']) ? $bind_info['name'] : '',
            'log_phone' => isset($bind_info['phone']) ? $bind_info['phone'] : '',
            'operator_id' => isset($bind_info['pigcms_id']) ? $bind_info['pigcms_id'] : 0,
            'log_uid' => isset($bind_info['uid']) ? $bind_info['uid'] : 0,
            'evaluate' => $evaluate,
            'rule_id' => $rule_id,
            'project_id' => $project_id,
            'charge_price' => $charge_price,
            'pay_type' => $pay_type,
            'offline_pay_type' => $offline_pay_type,
            'village_id' => $orderInfo['village_id'],
            'property_id' => $orderInfo['property_id'],
        ];
        if ($integral > 0) {
            $addRepairLogArr['get_integral'] = $integral;
        }
        $id = $this->addRepairLog($addRepairLogArr);


        $is_auto_assign = false;
        /*
         * 
        $whereArr=array('order_id'=>$order_id,'operator_type'=>10,'log_name'=>'house_auto_assign');
        $house_auto_assign=$db_house_new_repair_works_order_log->getOne($whereArr);
        if($event_status_type=='reopen' && $orderInfo['worker_id']>0 && $house_auto_assign && !$house_auto_assign->isEmpty()){
            $tmpData = $house_auto_assign->toArray();
            unset($tmpData['log_id']);
            $tmpData['add_time']=time();
            $this->addRepairLog($tmpData);
            $is_auto_assign=true;
        }
        */
        if ($event_status_type == 'reopen' && $orderInfo['worker_id'] > 0) {
            $is_auto_assign = true;
        }
        $resultLogArr = [];
        if ($id) {
            switch ($event_status_type) {
                case 'reopen':
                    if ($is_auto_assign) {
                        $event_status = 20;
                    } else {
                        $event_status = 10;
                    }
                    $type = 'processing';
                    break;
                case 'reopen_by_money':
                    $event_status = 10;
                    $type = 'processing';
                    break;
                case 'recall':
                    $event_status = 50;
                    $type = 'processed';
                    break;
                case 'closed':
                    $event_status = 60;
                    $type = 'processed';
                    break;
                case 'evaluate':
                    $event_status = 70;
                    $type = 'todo';
                    break;
                case 'agree':
                    $event_status = 36;
                    $type = 'todo';
                    $now_role = 3;
                    break;
                case 'reject':
                    $event_status = 37;
                    $type = 'todo';
                    break;
                default:
                    $event_status = 60;
                    $type = 'processed';
            }
            $updateArr = [
                'event_status' => $event_status,
                'update_time' => time(),
                'evaluate' => $evaluate,
            ];
            if ($event_status == 10) {
                $updateArr['worker_id'] = 0;
            }
            $toWorkId = 0;
            $workOrderConfig = [];
            $assign_record = [];
            fdump_api(['$updateArr' => $updateArr, 'line' => __LINE__], 'userWorkOrder/1reopen_by_money_log');
            if ($event_status_type == 'reopen_by_money') {
                // 物业维修（付费） 需要走下自动指派+配置项更新
                $village_id = $orderInfo['village_id'];
                $cat_id = $orderInfo['cat_id'];
                $toSingleId = isset($orderInfo['single_id']) ? intval($orderInfo['single_id']) : 0;
                $bind_id = isset($orderInfo['bind_id']) ? intval($orderInfo['bind_id']) : 0;
                $params = [
                    'village_id' => $village_id,
                    'works_order_id' => $order_id
                ];
                $serviceHouseCommunityWorkOrderConfig = new HouseCommunityWorkOrderService();
                $lastConfigInfo = $serviceHouseCommunityWorkOrderConfig->getHouseCommunityWorkOrderConfig($params, 'id, works_order_id, village_id, assign_record');
                $last_assign_record = isset($lastConfigInfo['assign_record']) && $lastConfigInfo['assign_record'] ? $lastConfigInfo['assign_record'] : [];
                $last_assign_record && $last_assign_record = json_decode($last_assign_record, true);
                fdump_api(['$last_assign_record' => $last_assign_record, 'line' => __LINE__], 'userWorkOrder/1reopen_by_money_log', 1);
                if ($jz_open) {
                    $is_supply_station = 0;
                    $supplyInfo = (new HouseCommunityAboutService())->getVillageFacilitiesInfo($village_id);
                    if (isset($supplyInfo['is_virtual']) && intval($supplyInfo['is_virtual']) == 1) {
                        // 是供暖站
                        $is_supply_station = 1;
                    }
                    $configInfo = (new HouseCommunityAboutService())->getHouseCommunityConfig($village_id);
                    // 按照配置获取下分配人员
                    $workOrderParam = [
                        'village_id' => $village_id,
                        'is_supply_station' => $is_supply_station,
                        'configInfo' => $configInfo,
                        'cat_id' => $cat_id,
                        'toSingleId' => $toSingleId,
                        'bind_id' => $bind_id,
                    ];
                    $assignRecord = (new HouseCommunityWorkOrderService())->getWorkOrderToWorker($workOrderParam);
                    fdump_api(['$workOrderParam' => $workOrderParam, '$assignRecord' => $assignRecord, 'line' => __LINE__], 'userWorkOrder/1reopen_by_money_log', 1);

                    $toWorkId = isset($assignRecord['toWorkId']) ? intval($assignRecord['toWorkId']) : 0;
                    $communityAutoToWork = isset($assignRecord['communityAutoToWork']) ? $assignRecord['communityAutoToWork'] : [];
                    $communityConfig = isset($assignRecord['communityConfig']) ? $assignRecord['communityConfig'] : [];
                    $repairCateConfig = isset($assignRecord['repairCateConfig']) ? $assignRecord['repairCateConfig'] : [];
                    $communityAutoToWork && $assign_record['communityAutoToWork'] = $communityAutoToWork;
                    $workOrderConfig['village_id'] = $village_id;
                    $communityConfig && $workOrderConfig['community_config'] = json_encode($communityConfig, JSON_UNESCAPED_UNICODE);
                    $repairCateConfig && $workOrderConfig['repair_cate_config'] = json_encode($repairCateConfig, JSON_UNESCAPED_UNICODE);
                    isset($assignRecord['not_report_switch']) && $workOrderConfig['not_report_switch'] = intval($assignRecord['not_report_switch']);
                    $workOrderConfig['quality_start_time'] = isset($repairCateConfig['quality_start_time']) ? intval($repairCateConfig['quality_start_time']) : 0;
                    $workOrderConfig['quality_end_time'] = isset($repairCateConfig['quality_end_time']) ? intval($repairCateConfig['quality_end_time']) : 0;

                    fdump_api(['$toWorkId' => $toWorkId, '$workOrderConfig' => $workOrderConfig, '$repairCateConfig' => $repairCateConfig, '$communityConfig' => $communityConfig, '$communityAutoToWork' => $communityAutoToWork, 'line' => __LINE__], 'userWorkOrder/1reopen_by_money_log', 1);
                }
                $last_assign_record && $assign_record['last_assign_record'] = $last_assign_record;
                $repairCateScheduling = [];
                if ($toWorkId > 0) {
                    $worker_id = $toWorkId;
                } else {
                    if (isset($other_village_id) && $other_village_id > 0 && isset($other_village_cate_id) && $other_village_cate_id > 0) {
                        $worker_ids = $this->getWorkerByTime($other_village_cate_id, $repairCateScheduling);
                    } else {
                        $worker_ids = $this->getWorkerByTime($cat_id, $repairCateScheduling);
                    }
                    if (!empty($worker_ids)) {
                        $index = array_rand($worker_ids);
                        $worker_id = $worker_ids[$index];
                    } else {
                        $worker_id = 0;
                    }
                }
                $repairCateScheduling && $assign_record['repairCateScheduling'] = $repairCateScheduling;
                $updateArr['worker_id'] = $worker_id;
                if ($worker_id > 0) {
                    $updateArr['now_role'] = 3;
                    $updateArr['event_status'] = 20;
                } else {
                    $updateArr['now_role'] = 2;
                }
                $updateArr['modify_cate_num'] = 0;
                $updateArr['now_handle_village_id'] = 0;
            }
            fdump_api(['$order_id' => $order_id, '$updateArr' => $updateArr, 'line' => __LINE__], 'userWorkOrder/1reopen_by_money_log', 1);
            $res = $db_house_new_repair_works_order->saveOne(['order_id' => $order_id], $updateArr);
            if ($res) {
                $resultLogArr['type'] = $type;
                $nowTime = time();
                if ($jz_open && $event_status_type == 'agree') {
                    // 定制 同意支付费用 直接生成相关账单 然后用户可以直接支付
                    $orderParam = [
                        'scheme_charge_type' => 1,
                        'rule_id' => isset($orderInfo['rule_id']) ? intval($orderInfo['rule_id']) : 0,
                        'project_id' => isset($orderInfo['project_id']) ? intval($orderInfo['project_id']) : 0,
                        'charge_price' => $orderInfo['charge_price'],
                        'property_id' => $orderInfo['property_id'],
                        'village_id' => $orderInfo['village_id'],
                        'hasBills' => 1,
                        'app_type' => $app_type,
                    ];
                    if ($pay_type > 0) {
                        $orderParam['pay_type'] = $pay_type;
                        $pay_type == 2 && $orderParam['pay_time'] = time();
                    }
                    if ($offline_pay_type) {
                        $orderParam['offline_pay_type'] = $offline_pay_type;
                    }
                    $addPayOrderResult = $this->addPayOrder($order_id, $orderParam);
                    $resultLogArr = array_merge($resultLogArr, $addPayOrderResult);
                }
                if ($jz_open && 'evaluate' == $event_status_type) {
                    // 评分进行处理
                    $toParams = ['village_id' => isset($order_detail['village_id']) ? intval($order_detail['village_id']) : 0, 'works_order_id' => $order_id];
                    (new HouseCommunityHandleNoticeService())->tryHandleEvaluateLowToReportBox($toParams);
                }
                if ($event_status_type == 'recall') {
                    //todo 用户撤回 发送模板消息
                    $this->newRepairSendMsg(22, $order_id, 0, $property_id);
                    if ($jz_open) {
                        $stepArr = [
                            'title' => '用户撤回了工单',
                            'type' => 'text',
                            'content' => '用户撤回了工单，去除自动结单时间',
                            'time' => time()
                        ];
                        (new HouseCommunityWorkOrderService())->setAutoEndTime($order_id, $stepArr);
                    }
                } elseif ($event_status_type == 'closed') {
                    $charge_type = $this->getCateInfo(['id' => $orderInfo['cat_id']], 'charge_type');
                    if ($charge_type && ($charge_type && intval($charge_type['charge_type']) == 1) && !$hasFee && $orderInfo['event_status'] > 30) {
                        //todo 用户关闭 不同意此收费金额，发送模板消息
                        $this->newRepairSendMsg(52, $order_id, 0, $property_id);
                    } else {
                        //todo 用户关闭 发送模板消息
                        $this->newRepairSendMsg(21, $order_id, 0, $property_id);
                    }
                    // todo 重开将自动结单时间置空
                    if ($jz_open) {
                        $stepArr = [
                            'title' => '用户关闭了工单',
                            'type' => 'text',
                            'content' => '用户关闭了工单，去除自动结单时间',
                            'time' => time()
                        ];
                        (new HouseCommunityWorkOrderService())->setAutoEndTime($order_id, $stepArr);
                    }
                } elseif ($event_status_type == 'reject') {
                    //todo 用户拒绝了此收费方案，发送模板消息
                    $this->newRepairSendMsg(53, $order_id, 0, $property_id);
                    // todo 用户拒绝了 走下推送配置
                    $toParams = ['village_id' => $orderInfo['village_id'], 'works_order_id' => $order_id,];
                    (new HouseCommunityHandleNoticeService())->tryHandleRejectOverToReportBox($toParams);
                } elseif ($event_status_type == 'reopen') {
                    //todo 用户重新打开 发送模板消息
                    $this->newRepairSendMsg(10, $order_id, 0, $property_id);
                    // todo 重开将自动结单时间置空
                    if ($jz_open) {
                        $stepArr = [
                            'title' => '用户重新打开工单',
                            'type' => 'text',
                            'content' => '用户重新打开工单，去除自动结单时间',
                            'time' => time()
                        ];
                        (new HouseCommunityWorkOrderService())->setAutoEndTime($order_id, $stepArr);
                    }
                } elseif ($event_status_type == 'agree') {
                    //todo 用户同意缴费
                    $this->newRepairSendMsg(51, $order_id, 0, $property_id);
                    // todo 处理工单自动结束时间记录
                    if ($jz_open) {
                        $stepArr = [
                            'title' => '用户同意缴费',
                            'type' => 'text',
                            'content' => '用户同意了缴费，记录自动结单时间',
                            'time' => time()
                        ];
                        (new HouseCommunityWorkOrderService())->setAutoEndTime($order_id, $stepArr);
                    }
                } elseif ($event_status_type == 'evaluate') {
                    //todo 用户评价
                    $this->newRepairSendMsg(20, $order_id, 0, $property_id);
                }

                if ($event_status_type == 'reopen_by_money') {
                    $village_id = $orderInfo['village_id'];
                    $service_house_village = new HouseVillageService();
                    $village_info = $service_house_village->getHouseVillageInfo(['village_id' => $village_id], 'village_id, property_id');
                    if ($worker_id) {
                        // 查询处理人信息
                        $where_house_work = [];
                        $where_house_work[] = ['wid', '=', $worker_id];
                        $house_work = (new HouseWorker())->get_one($where_house_work, 'wid,name,phone');
                        $logData = [];
                        $logData['order_id'] = $order_id;
                        $logData['log_name'] = 'house_auto_assign';
                        $logData['log_operator'] = $house_work['name'] ? $house_work['name'] : '';
                        $logData['log_phone'] = $house_work['phone'] ? $house_work['phone'] : '';
                        $logData['operator_type'] = 10;
                        $logData['operator_id'] = 0;
                        $logData['log_uid'] = $house_work['wid'] ? $house_work['wid'] : 0;
                        $logData['log_content'] = '';
                        $logData['log_imgs'] = '';
                        $logData['add_time'] = $nowTime;
                        $logData['village_id'] = $village_id;
                        $logData['property_id'] = $village_info['property_id'];
                        fdump_api(['$logData' => $logData, 'line' => __LINE__], 'userWorkOrder/1reopen_by_money_log', 1);
                        $otherData = array('opt_type' => 'assign_to_house_worker', 'wid' => $worker_id, 'village_id' => $village_id);
                        $this->addRepairLog($logData, $otherData);
                    }
                    if ($jz_open) {
                        if (isset($communityConfig) && isset($communityConfig['not_timely_report_switch'])) {
                            $not_timely_report_switch = intval($communityConfig['not_timely_report_switch']);
                            $isPublic = false;
                            if (isset($orderInfo['address_type']) && $orderInfo['address_type'] == 'public') {
                                $isPublic = true;
                            } elseif (isset($orderInfo['public_id']) && intval($orderInfo['public_id']) > 0) {
                                $isPublic = true;
                            }
                            fdump_api(['$not_timely_report_switch' => $not_timely_report_switch, '$isPublic' => $isPublic, 'line' => __LINE__], 'userWorkOrder/1reopen_by_money_log', 1);
                            if ($not_timely_report_switch == 0 && $isPublic) {
                                $noticeArr = [
                                    'village_id' => $village_id,
                                    'works_order_id' => $order_id,
                                ];
                                (new HouseCommunityHandleNoticeService())->tryHandlePublicToReportBox($noticeArr);
                            }
                        }
                        $serviceHouseCommunityWorkOrder = new HouseCommunityWorkOrderService();
                        $configId = isset($lastConfigInfo) && isset($lastConfigInfo['id']) && $lastConfigInfo['id'] ? intval($lastConfigInfo['id']) : 0;
                        fdump_api(['$configId' => $configId, 'line' => __LINE__], 'userWorkOrder/1reopen_by_money_log', 1);
                        if (!$configId) {
                            $assign_record && $workOrderConfig['assign_record'] = json_encode($assign_record, JSON_UNESCAPED_UNICODE);
                            $workOrderConfig['works_order_id'] = $order_id;
                            $workOrderConfig['add_time'] = $nowTime;
                            $configId = $serviceHouseCommunityWorkOrder->addHouseCommunityWorkOrderConfig($workOrderConfig);
                            fdump_api(['$configId' => $configId, '$workOrderConfig' => $workOrderConfig, 'line' => __LINE__], 'userWorkOrder/1reopen_by_money_log', 1);
                        } elseif (!empty($assign_record)) {
                            $updateConfigParams = ['works_order_id' => $order_id, 'village_id' => $village_id];
                            $fillData = [];
                            $fillData['assign_record'] = json_encode($assign_record, JSON_UNESCAPED_UNICODE);
                            $fillData['update_time'] = time();
                            $fillData['look_contact'] = 0;
                            $fillData['contact_content'] = '';
                            $serviceHouseCommunityWorkOrderConfig->updateHouseCommunityWorkOrderConfig($updateConfigParams, $fillData);
                            fdump_api(['$configId' => $configId, '$updateConfigParams' => $updateConfigParams, '$fillData' => $fillData, 'line' => __LINE__], 'userWorkOrder/1reopen_by_money_log', 1);
                        }
                        if ($worker_id) {
                            $workRecord = [
                                'worker_id' => $worker_id,
                                'village_id' => $village_id,
                                'property_id' => $village_info['property_id'],
                                'works_order_id' => $order_id,
                                'latest_receive_time' => $nowTime
                            ];
                            $serviceHouseCommunityWorkOrder->handleWorkOrderWorkRecord($workRecord);
                            fdump_api(['$worker_id' => $worker_id, '$workRecord' => $workRecord, 'line' => __LINE__], 'userWorkOrder/1reopen_by_money_log', 1);
                        }
                        if ($configId && !empty($communityConfig)) {
                            $workName = isset($house_work['name']) && $house_work['name'] ? trim($house_work['name']) : '';
                            $workPhone = isset($house_work['phone']) && $house_work['phone'] ? trim($house_work['phone']) : '';
                            $handleParams = [
                                'configId' => $configId,
                                'village_id' => $village_id,
                                'communityConfig' => $communityConfig,
                                'nowTime' => $nowTime,
                                'worker_id' => $worker_id,
                                'workName' => $workName,
                                'workPhone' => $workPhone,
                                'works_order_id' => $order_id,
                            ];
                            fdump_api(['$configId' => $configId, '$handleParams' => $handleParams, 'line' => __LINE__], 'userWorkOrder/1reopen_by_money_log', 1);
                            $serviceHouseCommunityWorkOrder->handleIncreaseWorkOrderConfig($handleParams);
                        }
                    }
                    if ($updateArr['worker_id'] > 0) {
                        //todo 用户发起工单 发送模板消息
                        $this->newRepairSendMsg(10, $order_id, 0, $village_info['property_id']);
                    } else {
                        //todo 开启抢单模式
                        $this->grabOrder($village_id, $order_id, $cat_id);
                    }
                }
                if ($jz_open && $event_status) {
                    $worker_id = isset($orderInfo['worker_id']) ? intval($orderInfo['worker_id']) : 0;
                    $village_id = isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : 0;
                    if (isset($updateArr['worker_id'])) {
                        $worker_id = intval($updateArr['worker_id']);
                    }
                    $params = ['village_id' => $village_id, 'order_id' => $order_id, 'worker_id' => $worker_id, 'event_status' => $event_status];
                    (new HouseCommunityWorkOrderService())->overNotProcessedReport($params);
                }
                return $resultLogArr;
            }
        }
        throw new \think\Exception('操作异常');
    }

    /**
     * 工单处理中心回复
     * @param int $order_id
     * @param string $event_status_type
     * @param string $log_content
     * @param int $village_id
     * @param int $property_id
     * @param int $uid
     * @param int $worker_id
     * @return bool
     * @throws \think\Exception
     * @author lijie
     * @date_time  2021/08/23
     */
    public function workOrderSave($order_id = 0, $event_status_type = '', $log_content = '', $village_id = 0, $property_id = 0, $uid = 0, $worker_id = 0)
    {
        if (!$order_id || empty($event_status_type))
            throw new \think\Exception('请上报处理类型');
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $orderInfo = $db_house_new_repair_works_order->getOne(['order_id' => $order_id], 'event_status,order_type,uid');
        if (empty($orderInfo)) {
            throw new \think\Exception('请上报正确处理类型');
        }
        $log_data = [
            'order_id' => $order_id,
            'log_name' => $event_status_type,
            'operator_type' => 20,
            'log_content' => $log_content,
            'log_imgs' => '',
            'add_time' => time(),
            'log_operator' => '处理中心管理员',
            'log_phone' => '',
            'operator_id' => 0,
            'log_uid' => $uid,
            'evaluate' => 0,
            'village_id' => $village_id,
            'property_id' => $property_id
        ];
        $otherData = array();
        if ($worker_id > 0 && strpos($event_status_type, 'assign') !== false) {
            $otherData = array('opt_type' => 'assign_to_house_worker', 'wid' => $worker_id, 'village_id' => $village_id);
        }
        $id = $this->addRepairLog($log_data, $otherData);
        if ($id) {
            if ($worker_id) {
                $data['update_time'] = time();
                $data['event_status'] = 20;
                $data['worker_id'] = $worker_id;
                $data['group_id'] = $this->getGroupId($worker_id);
                //todo 转单记录当前转单人信息
                $this->workOrderLogSave($id, $worker_id);
            } else {
                $data['update_time'] = time();
                $data['event_status'] = 40;
            }
            $res = $db_house_new_repair_works_order->saveOne(['order_id' => $order_id], $data);
            if ($res) {
                if ($worker_id) {
                    //针对工作人员提交的工单有指派时 给提交人发送通知
                    if ($orderInfo['order_type'] == 4) {
                        $this->newRepairSendMsg(11, $order_id, $orderInfo['uid'], $property_id);
                        //给工作人员发送通知
                        $this->newRepairSendMsg(12, $order_id, 0, $property_id);
                    } else {
                        $this->newRepairSendMsg(10, $order_id, 0, $property_id);
                    }
                } else {
                    //已办结
                    $this->newRepairSendMsg(70, $order_id, 0, $property_id);
                }
                $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
                if ($jz_open && isset($data['event_status'])) {
                    $params = ['village_id' => $village_id, 'order_id' => $order_id, 'worker_id' => $worker_id, 'event_status' => $data['event_status']];
                    (new HouseCommunityWorkOrderService())->overNotProcessedReport($params);
                }
                return true;
            }
        }
        throw new \think\Exception('操作异常');
    }

    /**
     * 分类详情
     * @param array $where
     * @param bool $field
     * @return array|\think\Model|null
     */
    public function getCateInfo($where = [], $field = true)
    {
        $db_house_new_repair_cate = new HouseNewRepairCate();
        $data = $db_house_new_repair_cate->getOne($where, $field);
        return $data;
    }

    /**
     * 公共区域信息
     * @param array $where
     * @param bool $field
     * @return array|\think\Model|null
     * @author lijie
     * @date_time 2021/08/17
     */
    public function getPublicAreaInfo($where = [], $field = true)
    {
        $db_house_village_public_area = new HouseVillagePublicArea();
        $data = $db_house_village_public_area->getOne($where, $field);
        return $data;
    }

    /**
     * 日志数量
     * @param array $where
     * @return int
     * @author lijie
     * @date_time 2021/08/18
     */
    public function getLogCount($where = [])
    {
        $db_house_new_repair_work_order_log = new HouseNewRepairWorksOrderLog();
        $count = $db_house_new_repair_work_order_log->getCount($where);
        return $count;
    }

    public function getLogCountByOrderId($where = [])
    {
        $db_house_new_repair_work_order_log = new HouseNewRepairWorksOrderLog();
        $count = $db_house_new_repair_work_order_log->getCountGroupBy($where, 'order_id');
        return $count;
    }

    public function getColumn($where, $field, $key = '')
    {
        $db_house_new_repair_work_order_log = new HouseNewRepairWorksOrderLog();
        $count = $db_house_new_repair_work_order_log->getColumn($where, $field, $key);
        return $count;
    }

    /**
     * 计算平均分
     * @param array $where
     * @param bool $field
     * @return mixed
     */
    public function getAvg($where = [], $field = true)
    {
        $db_house_new_repair_work_order_log = new HouseNewRepairWorksOrderLog();
        $data = $db_house_new_repair_work_order_log->getAvg($where, $field);
        if ($data) {
            foreach ($data as &$v) {
                $v['avg_evaluate'] = intval($v['avg_evaluate']);
            }
        }
        return $data;
    }

    /**
     * 获取工作人员id
     * @param array $where
     * @param bool $field
     * @return array
     */
    public function getWorkers($where = [], $field = true)
    {
        $db_house_new_repair_work_order_log = new HouseNewRepairWorksOrderLog();
        $data = $db_house_new_repair_work_order_log->getLists($where, $field);
        $worker_ids = [];
        if ($data) {
            foreach ($data as $v) {
                $worker_ids[] = $v['worker_id'];
            }
        }
        return $worker_ids;
    }

    /**
     * 根据工作人员获取符合条件的工单ID
     * @param int $worker_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getWorkerDealOrderId($worker_id = 0)
    {
        $house_new_repair_works_order_log = new HouseNewRepairWorksOrderLog();
        $work_log = $this->work_log;
        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        if ($jz_open) {
            $work_log[] = 'to_supply_station';
            $work_log[] = 'to_developer';
        }
        $whereOperator = [
            ['operator_id', '=', $worker_id],
            ['log_name', 'in', $work_log],
        ];
        $order_log = $house_new_repair_works_order_log->getList($whereOperator, 'order_id');
        if (!$order_log->isEmpty()) {
            $data = $order_log->toArray();
            return array_unique(array_column($data, 'order_id'));
        } else {
            return [];
        }
    }

    public function getFinshOrder($village_id, $params = [])
    {
        $mark_report = isset($params['mark_report']) ? intval($params['mark_report']) : 0;
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $db_house_new_repair_subject = new HouseNewRepairCate();
        $where_subject = [
            'parent_id' => 0,
            'status' => 1
        ];
        $village_id && $where_subject['village_id'] = $village_id;
        $count_list = [];
        $subject_list = $db_house_new_repair_subject->getList($where_subject);
        if (!empty($subject_list)) {
            $subject_list = $subject_list->toArray();
            if (!empty($subject_list)) {
                foreach ($subject_list as $k => $value) {
                    $where_works_order_total = [
                        ['o.village_id', '=', $village_id],
                        ['o.cat_fid', '=', $value['id']],
                        ['o.add_time', '>=', strtotime(date('Y-m-01 00:00:00', time()))],
                        ['o.add_time', '<=', strtotime(date('Y-m-t 23:23:59', time()))],
                    ];
                    if ($mark_report) {
                        $where_works_order_total[] = ['o.mark_report', '=', 1];
                    }
                    $count_total = $db_house_new_repair_works_order->getCount($where_works_order_total);
                    $where_works_order_finsh = [
                        ['o.village_id', '=', $village_id],
                        ['o.cat_fid', '=', $value['id']],
                        ['o.event_status', '=', 60],
                        ['o.add_time', '>=', strtotime(date('Y-m-01 00:00:00', time()))],
                        ['o.add_time', '<=', strtotime(date('Y-m-t 23:23:59', time()))],
                    ];
                    if ($mark_report) {
                        $where_works_order_finsh[] = ['o.mark_report', '=', 1];
                    }
                    $count_finsh = $db_house_new_repair_works_order->getCount($where_works_order_finsh);
                    $count_list[$k]['subject_id'] = $value['id'];
                    $count_list[$k]['subject_name'] = $value['cate_name'];
                    $count_list[$k]['count_total'] = $count_total;
                    $count_list[$k]['count_finsh'] = $count_finsh;
                    if (empty($count_total) || empty($count_finsh)) {
                        $count_list[$k]['rate'] = 0;
                    } else {
                        $count_list[$k]['rate'] = round_number($count_finsh / $count_total, 2);
                    }
                }
            }
        }
        return $count_list;
    }

    //获取工作人员对应的部门id
    public function getGroupId($worker_id)
    {
        $group_id = 0;
        $worker = (new HouseWorker())->getOne(['wid' => intval($worker_id)], 'department_id');
        if ($worker && !$worker->isEmpty()) {
            $group_id = $worker['department_id'];
        }
        return $group_id;
    }


    /**
     * 校验白晚班是否可提交工单
     * @param $village_id
     * @return array
     * @author: liukezhu
     * @date : 2022/3/31
     */
    public function checkWorkOrder($village_id)
    {
        $village_info = (new HouseVillageInfo())->getOne(['village_id' => $village_id], 'help_info,is_timely');
        $time = time();
        $day_start = '08:00:00'; //白班开始时间
        $day_end = '18:00:00';  //白班结束时间
        $night_start = '18:00:01'; //晚班开始时间
        $night_end = '07:59:59'; //晚班结束时间
        $phone = '';
        $is_close_help_info = 0;
        $is_go_time = 0;
        if ($village_info && !$village_info->isEmpty()) {
            $info = unserialize($village_info['help_info']);
            if ($info) {
                if (isset($info['is_close'])) {
                    $is_close_help_info = intval($info['is_close']);
                }
                if (isset($info['start'][0]) && isset($info['end'][0])) {
                    $day_start = $info['start'][0] . ':00';
                    $day_end = $info['end'][0] . ':00';
                }
                if (isset($info['start'][1]) && isset($info['end'][1])) {
                    $night_start = $info['start'][1] . ':00';
                    $night_end = $info['end'][1] . ':00';
                }
                if (isset($info['phone'][1]) && !empty($info['phone'][1])) {
                    $phone = $info['phone'][1];
                }
            }
            if (!empty($village_info['is_timely'])) {
                $is_go_time = $village_info['is_timely'];
            }
        }
        $day_start_replace = (int)str_replace(":", "", $day_start);
        $day_end_replace = (int)str_replace(":", "", $day_end);
        $night_start_replace = (int)str_replace(":", "", $night_start);
        $night_end_replace = (int)str_replace(":", "", $night_end);

        //白班开始时间
        $day_start_time = strtotime(date('Y-m-d ' . $day_start, $time));
        //晚班开始时间
        $night_start_time = strtotime(date('Y-m-d ' . $night_start, $time));

        //获取白班结束时间
        if ($day_start_replace >= $day_end_replace) {
            $day_end_time = strtotime(date("Y-m-d " . $day_end, strtotime("+1 day")));
        } else {
            $day_end_time = strtotime(date('Y-m-d ' . $day_end, $time));
        }
        //获取晚班结束时间
        if ($night_start_replace >= $night_end_replace) {
            $night_end_time = strtotime(date("Y-m-d " . $night_end, strtotime("+1 day")));
        } else {
            $night_end_time = strtotime(date('Y-m-d ' . $night_end, $time));
        }
        //当前时间是否在白班范围内
        if ($time >= $day_start_time && $time <= $day_end_time) {
            return ['code' => 1, 'msg' => 'ok', 'is_night' => 0, 'data' => ['is_go_time' => $is_go_time]];
        } else {
            if ($is_close_help_info < 1 && ($time >= $night_start_time && $time <= $night_end_time)) { //当前时间在晚班范围内
                if (empty($phone)) {
                    return ['code' => -1, 'is_night' => 1, 'msg' => '该晚班电话暂未配置，请联系工作人员', 'data' => ['is_go_time' => $is_go_time]];
                } else {
                    return ['code' => 0, 'is_night' => 1, 'msg' => '请用该电话联系', 'data' => ['phone' => $phone, 'is_go_time' => $is_go_time]];
                }
            } else {
                return ['code' => 1, 'is_night' => 0, 'msg' => 'ok', 'data' => ['is_go_time' => $is_go_time]];
            }
        }
    }

    public function getChargeSetOrder($data)
    {
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $db_house_new_repair_subject = new HouseNewRepairCate();
        $db_house_new_charge_rule = new HouseNewChargeRule();
        $work_info = $db_house_new_repair_works_order->getOne(['order_id' => $data['order_id']]);
        if (!empty($work_info)) {
            $work_info = $work_info->toArray();
        }
        if (empty($work_info)) {
            throw new \think\Exception('当前工单信息不存在');
        }

        $charge_set = $db_house_new_repair_subject->getOne(['id' => $work_info['cat_id']]);
        if (!empty($charge_set)) {
            $charge_set = $charge_set->toArray();
        }
        if (empty($charge_set)) {
            throw new \think\Exception('当前工单的分类信息不存在');
        }
        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        if ($jz_open) {
            $single_id = isset($work_info['single_id']) && intval($work_info['single_id']) > 0 ? intval($work_info['single_id']) : 0;
            $room_id = isset($work_info['room_id']) && intval($work_info['room_id']) > 0 ? intval($work_info['room_id']) : 0;
            $village_id = isset($work_info['village_id']) && intval($work_info['village_id']) > 0 ? intval($work_info['village_id']) : 0;
            $repair_cate_id = isset($work_info['cat_id']) && intval($work_info['cat_id']) > 0 ? intval($work_info['cat_id']) : 0;
            !$village_id && $village_id = isset($charge_set['village_id']) && intval($charge_set['village_id']) > 0 ? intval($charge_set['village_id']) : 0;
            !$repair_cate_id && $repair_cate_id = isset($charge_set['id']) && intval($charge_set['id']) > 0 ? intval($charge_set['id']) : 0;
            $judgeParams = [
                'village_id' => $village_id,
                'repair_cate_id' => $repair_cate_id,
                'jz_open' => $jz_open,
                'single_id' => $single_id,
                'room_id' => $room_id,
            ];
            $hasFee = (new HouseCommunityQualityService())->judgeRepairCateQualityFee($judgeParams);
        } else {
            $hasFee = false;
        }
        $charge_data = [];
        if (in_array($work_info['event_status'], [35, 36]) && $data['change_status']['log'] == 'work_follow_up') {
            $work_type = 1;
            $charge_type = false;
        } elseif (in_array($work_info['event_status'], [44, 45]) && $data['change_status']['log'] == 'work_completed') {
            $work_type = 2;
            $charge_type = false;
        } elseif ($work_info['event_status'] == 35 && $data['change_status']['log'] == 'work_completed') {
            throw new \think\Exception('用户没有对工单处理操作');
        } else {
            if ($charge_set['charge_type'] == 1 && $data['change_status']['log'] == 'work_follow_up') {
                $work_type = 1;
                $charge_type = true;
            } elseif ($charge_set['charge_type'] != 1 && $data['change_status']['log'] == 'work_follow_up') {
                $work_type = 1;
                $charge_type = false;
            } elseif ($charge_set['charge_type'] == 1 && $data['change_status']['log'] == 'work_completed') {
                $work_type = 2;
                $charge_type = true;
                $charge_data['charge_price'] = $work_info['charge_price'];
                $charge_data['pay_type'] = $work_info['pay_type'];
                $rule_info = $db_house_new_charge_rule->getFind(['r.id' => $work_info['rule_id']], 'r.charge_name,p.name');
                if (!empty($rule_info)) {
                    $charge_data['rule_name'] = $rule_info['charge_name'];
                    $charge_data['project_name'] = $rule_info['name'];
                }
            } else {
                $work_type = 2;
                $charge_type = false;
            }
        }
        if ($hasFee && $charge_type) {
            $charge_type = false;
            $charge_data = [];
        }

        if (empty($charge_data)) {
            $charge_data = null;
        }
        $list = [];
        $list['type'] = $work_type;
        $list['res'] = $charge_type;
        $list['data'] = $charge_data;
        return $list;
    }

    public function getNewRepairWorksOrderInfo($params)
    {
        $order_id = isset($params['order_id']) ? intval($params['order_id']) : 0;
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $worksOrderInfo = $db_house_new_repair_works_order->getOne(['order_id' => $order_id]);
        if ($worksOrderInfo && !is_array($worksOrderInfo)) {
            $worksOrderInfo = $worksOrderInfo->toArray();
        }
        return $worksOrderInfo;
    }

    public function getWorkChargeProject($data)
    {
        $db_house_new_charge_project = new HouseNewChargeProject();
        $where = [
            'village_id' => $data['village_id'],
            'type' => 1,
            'status' => 1,
        ];
        $project_list = $db_house_new_charge_project->getLists($where, 'id,name');
        if (!empty($project_list)) {
            $project_list = $project_list->toArray();
        }

        return $project_list;
    }

    //结单完成
    public function getTimelyStatus($order)
    {
        $param = [
            'order_id' => $order['order_id'], //订单id
            'village_id' => $order['village_id'], //小区id
            'go_time' => $order['go_time'], //上门时间
            'cat_fid' => $order['cat_fid'], //一级分类id
            'cat_id' => $order['cat_id'], //二级分类id
            'timely_time' => $order['timely_time'], //结单时间
            'group_id' => $this->getGroupId($order['worker_id']), //部门id
            'worker_id' => $order['worker_id'], //工作人员id
        ];
        if (!empty($param['timely_time'])) {
            return ['error' => false, 'data' => '已完成结单，无须操作'];
        }
        $time = time();
        $is_timely = (new HouseVillageService())->checkVillageField($param['village_id'], 'is_timely');
        $cate = (new HouseNewRepairCate())->getOne(['id' => $param['cat_id']], 'timely_time');
        $data = [
            'timely_time' => $time
        ];
        if (!$cate || $cate->isEmpty()) {
            return ['error' => false, 'data' => '该分类不存在'];
        }
        if (empty($is_timely)) { //未开启新版工单及时率
            $data['is_status'] = 0;
        } else {
            $timely_time = intval($cate['timely_time']) * 60;
            $data['cate_timely_time'] = $cate['timely_time'];
            $diff_time = $time - $order['go_time'];
            if ($timely_time <= 0) {
                $data['is_status'] = 0;
            }
            if ($diff_time <= 0) {
                $data['is_status'] = 2;
            }
            //实际用了50s  配置项60s
            if ($diff_time <= $timely_time) {
                $data['is_status'] = 2;
            } else {
                $data['is_status'] = 1;
            }
        }

        $this->addOrderTimely([
            'village_id' => $param['village_id'],
            'type' => 1,
            'order_id' => $param['order_id'],
            'cat_fid' => $param['cat_fid'],
            'cat_id' => $param['cat_id'],
            'group_id' => $param['group_id'],
            'worker_id' => $param['worker_id'],
            'is_status' => $data['is_status'],
            'add_time' => $time
        ]);
        return ['error' => true, 'data' => $data];
    }

    //todo 同步写入工单及时率
    public function addOrderTimely($data)
    {
        return (new HouseNewRepairWorksOrderTimely())->addOne($data);
    }

    public function getOrderTimelyOne($where, $field = true)
    {
        return (new HouseNewRepairWorksOrderTimely())->get_one($where, $field);
    }


    public function getWorkChargeRule($data)
    {
        $db_house_new_charge_project = new HouseNewChargeProject();
        $db_house_new_charge_rule = new HouseNewChargeRule();
        $where = [
            'village_id' => $data['village_id'],
            'id' => $data['project_id'],
            'status' => 1,
        ];
        $project_info = $db_house_new_charge_project->getOne($where);
        if (empty($project_info)) {
            throw new \think\Exception('当前收费项目信息不存在');
        }
        $where_rule = [
            'village_id' => $data['village_id'],
            'charge_project_id' => $data['project_id'],
            'status' => 1,
        ];
        $rule_list = $db_house_new_charge_rule->getList($where_rule, 'id,charge_name,charge_project_id,charge_price');
        if (!empty($rule_list) && !$rule_list->isEmpty()) {
            $rule_list = $rule_list->toArray();
            foreach ($rule_list as $kk => $vv) {
                $rule_list[$kk]['charge_price'] = $vv['charge_price'] + 0;
            }
        }
        if (empty($rule_list)) {
            throw new \think\Exception('当前收费项目没有对应的收费标准');
        }

        return ['list' => $rule_list];
    }


    /**
     * 办结工单生成缴费账单
     * @author:zhubaodi
     * @date_time: 2022/3/29 18:19
     */
    public function addPayOrder($order_id, $orderParam = [])
    {
        $scheme_charge_type = isset($orderParam['scheme_charge_type']) && $orderParam['scheme_charge_type'] ? intval($orderParam['scheme_charge_type']) : 0;
        $charge_price = isset($orderParam['charge_price']) && floatval($orderParam['charge_price']) > 0 ? $orderParam['charge_price'] : 0;
        // $hasBills 是否生成账单
        $hasBills = isset($orderParam['hasBills']) && floatval($orderParam['hasBills']) > 0 ? $orderParam['hasBills'] : 0;
        if ($scheme_charge_type == 2 && $hasBills == 0) {
            return [];
        }
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $db_house_new_charge_rule = new HouseNewChargeRule();
        $service_house_new_charge = new HouseNewChargeService();
        $db_house_admin = new HouseAdmin();
        $work_order_info = $db_house_new_repair_works_order->getOne(['order_id' => $order_id]);
        fdump_api([$work_order_info, $order_id], 'addPayOrder_0414', 1);
        $pay_order_id = 0;
        fdump_api(['order_id' => $order_id, 'orderParam' => $orderParam, 'line' => __LINE__], 'worksOrder/addPayOrderLog');
        $result = [];
        if ($scheme_charge_type == 1) {
            $pay_order_id = isset($work_order_info['pay_order_id']) && $work_order_info['pay_order_id'] ? intval($work_order_info['pay_order_id']) : 0;
            if ($pay_order_id) {
                return $pay_order_id;
            }
            $app_type = isset($orderParam['app_type']) && $orderParam['app_type'] ? trim($orderParam['app_type']) : '';
            !$charge_price && $charge_price = isset($work_order_info['charge_price']) && floatval($work_order_info['charge_price']) > 0 ? $work_order_info['charge_price'] : 0;
            $nowTime = time();
            // 计费模式 1:固定费用 2：单价计量单位 3 临时车  4 月租车  5 车位数量
            $fees_type = 1;
            // 计量单位
            $unit_gage = '';
            // 周期性设置 周期数 0:不限周期
            $cyclicity_set = 0;
            // 账单生成周期设置 1：按日生成2：按月生成3：按年生成
            $bill_create_set = 0;
            // 账单欠费模式  1:预生成  2：后生成
            $bill_arrears_set = 0;
            // 生成账单模式 1:手动生成 2：自动生成
            $bill_type = 1;
            // 是否支持预缴 1:支持 2：不支持
            $is_prepaid = 2;
            // 1:年月日 2：年月 3：年
            $charge_valid_type = 2;
            // 收费标准生效时间
            $charge_valid_time = $nowTime;
            // 收费标准来源 工单
            $from_type = 'works_order';
            $status = 1; //状态 1:开启 2：关闭 4：删除
            $projectId = 0;
            $ruleId = 0;
            // 注意 【建行收款账号】 需要单独在物业 收费科目中 单独手动配置下 才能支持该支付
            $charge_number_name = '工单收费[自定义][收费科目]';
            $charge_project_name = '工单收费[自定义][收费项目]';
            $charge_name = '工单收费[自定义][收费标准]';
            // 自定义金额 默认移动设置下收费标准
            $property_id = isset($orderParam['property_id']) ? intval($orderParam['property_id']) : 0;
            !$property_id && $property_id = isset($work_order_info['property_id']) ? intval($work_order_info['property_id']) : 0;
            if ($hasBills == 1) {
                $projectId = isset($orderParam['project_id']) && intval($orderParam['project_id']) > 0 ? $orderParam['project_id'] : 0;
                $ruleId = isset($orderParam['rule_id']) && intval($orderParam['rule_id']) > 0 ? $orderParam['rule_id'] : 0;
            }
            $dbHouseNewChargeNumber = new HouseNewChargeNumber();
            $db_house_new_pay_order = new HouseNewPayOrder();
            $village_id = isset($orderParam['village_id']) ? intval($orderParam['village_id']) : 0;
            !$village_id && $village_id = isset($work_order_info['village_id']) ? intval($work_order_info['village_id']) : 0;

            if (!$projectId || !$ruleId) {
                $whereChargeNumber = [];
                $whereChargeNumber[] = ['property_id', '=', $property_id];
                $whereChargeNumber[] = ['charge_type', '=', 'other'];
                $whereChargeNumber[] = ['status', '=', 1];
                $whereChargeNumber[] = ['charge_number_name', '=', $charge_number_name];
                $chargeNumberInfo = $dbHouseNewChargeNumber->get_one($whereChargeNumber, 'id, property_id, charge_type, status, charge_number_name');
                $charge_type = isset($chargeNumberInfo['charge_type']) && $chargeNumberInfo['charge_type'] ? $chargeNumberInfo['charge_type'] : '';
                $subjectId = isset($chargeNumberInfo['id']) && $chargeNumberInfo['id'] ? $chargeNumberInfo['id'] : '';
                fdump_api(['charge_type' => $charge_type, 'line' => __LINE__], 'worksOrder/addPayOrderLog', 1);
                if (!$subjectId) {
                    $chargeNumberData = [
                        'property_id' => $property_id,
                        'charge_type' => 'other',
                        'status' => 1,
                        'charge_number_name' => $charge_number_name,
                        'add_time' => $nowTime,
                    ];
                    $subjectId = $dbHouseNewChargeNumber->addOne($chargeNumberData);
                    fdump_api(['chargeNumberData' => $chargeNumberData, 'subjectId' => $subjectId, 'line' => __LINE__], 'worksOrder/addPayOrderLog', 1);
                }
                $db_house_new_charge_project = new HouseNewChargeProject();
                $whereChargeProject = [];
                $whereChargeProject[] = ['village_id', '=', $village_id];
                $whereChargeProject[] = ['subject_id', '=', $subjectId];
                $whereChargeProject[] = ['status', '=', 1];
                $whereChargeProject[] = ['type', '=', 1];
                $whereChargeProject[] = ['name', '=', $charge_project_name];
                $chargeProjectInfo = $db_house_new_charge_project->getOne($whereChargeProject, 'id, village_id, subject_id, status, type, name');
                $projectId = isset($chargeProjectInfo['id']) && $chargeProjectInfo['id'] ? $chargeProjectInfo['id'] : '';
                fdump_api(['projectId' => $projectId, 'line' => __LINE__], 'worksOrder/addPayOrderLog', 1);
                if (!$projectId) {
                    $chargeProjectData = [
                        'village_id' => $village_id,
                        'subject_id' => $subjectId,
                        'status' => 1,
                        'type' => 1,
                        'name' => $charge_project_name,
                        'add_time' => $nowTime,
                    ];
                    $db_house_new_charge = new HouseNewCharge();
                    $chargeInfo = $db_house_new_charge->get_one(['village_id' => $village_id]);
                    $refund_term = isset($chargeInfo['refund_term']) && $chargeInfo['refund_term'] ? intval($chargeInfo['refund_term']) : 0;
                    $chargeProjectData['refund_period'] = $refund_term;
                    $projectId = $db_house_new_charge_project->addFind($chargeProjectData);
                    fdump_api(['chargeProjectData' => $chargeProjectData, 'projectId' => $projectId, 'line' => __LINE__], 'worksOrder/addPayOrderLog', 1);
                }
                $whereRepeatRule = [];
                $whereRepeatRule[] = ['village_id', '=', $village_id];
                $whereRepeatRule[] = ['subject_id', '=', $subjectId];
                $whereRepeatRule[] = ['charge_project_id', '=', $projectId];
                $whereRepeatRule[] = ['charge_name', '=', $charge_name];
                $whereRepeatRule[] = ['unit_price', '=', $charge_price];
                $whereRepeatRule[] = ['charge_price', '=', $charge_price];
                $whereRepeatRule[] = ['fees_type', '=', $fees_type];
                $whereRepeatRule[] = ['cyclicity_set', '=', $cyclicity_set];
                $whereRepeatRule[] = ['bill_create_set', '=', $bill_create_set];
                $whereRepeatRule[] = ['bill_arrears_set', '=', $bill_arrears_set];
                $whereRepeatRule[] = ['bill_type', '=', $bill_type];
                $whereRepeatRule[] = ['is_prepaid', '=', $is_prepaid];
                $whereRepeatRule[] = ['status', '=', $status];
                $whereRepeatRule[] = ['from_type', '=', $from_type];
                $dbHouseNewChargeRule = new HouseNewChargeRule();
                $ruleInfo = $dbHouseNewChargeRule->getOne($whereRepeatRule, 'id');
                $ruleId = isset($ruleInfo['id']) && $ruleInfo['id'] ? $ruleInfo['id'] : '';
                fdump_api(['ruleId' => $ruleId, 'line' => __LINE__], 'worksOrder/addPayOrderLog', 1);
                if (!$ruleId) {
                    $ruleData = [
                        'village_id' => $village_id,
                        'subject_id' => $subjectId,
                        'charge_project_id' => $projectId,
                        'charge_name' => $charge_name,
                        'unit_price' => $charge_price,
                        'charge_price' => $charge_price,
                        'fees_type' => $fees_type,
                        'cyclicity_set' => $cyclicity_set,
                        'bill_create_set' => $bill_create_set,
                        'bill_arrears_set' => $bill_arrears_set,
                        'bill_type' => $bill_type,
                        'is_prepaid' => $is_prepaid,
                        'status' => $status,
                        'from_type' => $from_type,
                        'charge_valid_type' => $charge_valid_type,
                        'charge_valid_time' => $charge_valid_time,
                        'not_house_rate' => 100,
                        'unit_gage' => $unit_gage,
                        'add_time' => $nowTime,
                    ];
                    $ruleId = $dbHouseNewChargeRule->addFind($ruleData);
                    fdump_api(['ruleData' => $ruleData, 'ruleId' => $ruleId, 'line' => __LINE__], 'worksOrder/addPayOrderLog', 1);
                }
            } else {
                $charge_type = 'other';
                $whereRepeatRule1 = [];
                $whereRepeatRule1[] = ['village_id', '=', $village_id];
                $whereRepeatRule1[] = ['charge_project_id', '=', $projectId];
                $whereRepeatRule1[] = ['id', '=', $ruleId];
                $dbHouseNewChargeRule = new HouseNewChargeRule();
                $ruleInfo = $dbHouseNewChargeRule->getOne($whereRepeatRule1, 'id, charge_price');
                $rule_charge_price = isset($ruleInfo['charge_price']) && floatval($ruleInfo['charge_price']) > 0 ? $ruleInfo['charge_price'] : 0;
                if ($rule_charge_price != $charge_price) {
                    $ruleUpdate = ['unit_price' => $charge_price, 'charge_price' => $charge_price, 'charge_valid_time' => $charge_valid_time,];
                    $dbHouseNewChargeRule->editFind($whereRepeatRule1, $ruleUpdate);
                }
            }
            $role_id = 0;
            if (!empty($work_order_info['worker_id'])) {
                $role_info = $db_house_admin->getOne(['wid' => $work_order_info['worker_id']], 'id');
                $role_id = isset($role_info['id']) && $role_info['id'] ? $role_info['id'] : 0;
            }
            $pay_type = isset($orderParam['pay_type']) && $orderParam['pay_type'] ? $orderParam['pay_type'] : 0;
            fdump_api(['result' => $result, 'hasBills' => $hasBills, 'line' => __LINE__], 'worksOrder/addPayOrderLog', 1);
            if ($hasBills == 1) {
                $room_id = isset($work_order_info['room_id']) && $work_order_info['room_id'] ? intval($work_order_info['room_id']) : 0;
                $pigcms_id = isset($work_order_info['bind_id']) && $work_order_info['bind_id'] ? intval($work_order_info['bind_id']) : 0;
                $name = isset($work_order_info['name']) && $work_order_info['name'] ? trim($work_order_info['name']) : '';
                $phone = isset($work_order_info['phone']) && $work_order_info['phone'] ? trim($work_order_info['phone']) : '';
                $uid = isset($work_order_info['uid']) && $work_order_info['uid'] ? intval($work_order_info['uid']) : 0;
                $payOrderParam = [
                    'order_type' => $charge_type,
                    'order_name' => "工单收费【ID：{$order_id}】",
                    'village_id' => $village_id,
                    'property_id' => $property_id,
                    'total_money' => $charge_price,
                    'modify_money' => $charge_price,
                    'role_id' => $role_id,
                    'is_prepare' => 2,
                    'rule_id' => $ruleId,
                    'project_id' => $projectId,
                    'order_no' => 'works_order_' . $order_id,
                    'add_time' => $nowTime,
                    'from' => 4,
                    'service_start_time' => $nowTime,
                    'service_end_time' => $nowTime,
                    'unit_price' => $charge_price,
                    'room_id' => $room_id,
                    'pigcms_id' => $pigcms_id,
                    'uid' => $uid,
                    'name' => $name,
                    'phone' => $phone,
                    'is_paid' => 2,
                    'pay_bind_id' => $pigcms_id,
                    'pay_bind_name' => $name,
                    'pay_bind_phone' => $phone,
                ];
                if ($pay_type == 1) {
                    $payOrderParam['pay_type'] = 4;
                }
                $pay_order_id = $db_house_new_pay_order->addOne($payOrderParam);
                fdump_api(['msg' => '生成账单', 'payOrderParam' => $payOrderParam, 'pay_order_id' => $pay_order_id, 'line' => __LINE__], 'worksOrder/addPayOrderLog', 1);
                // 同时生成父级账单 组合跳转链接
                $summaryData = array();
                $summaryData['village_id'] = $village_id;
                $summaryData['property_id'] = $property_id;
                $summaryData['room_id'] = $room_id;
                $summaryData['pigcms_id'] = $pigcms_id;
                $summaryData['uid'] = $uid;
                $summaryData['pay_uid'] = $uid;
                $summaryData['pay_bind_id'] = $pigcms_id;
                $summaryData['order_no'] = build_real_orderid($summaryData['pay_uid']);
                $summaryData['total_money'] = $charge_price;
                $summaryData['is_paid'] = 2;
                $summaryData['pay_type'] = 4;
                $summaryData['is_online'] = 1;
                $ruleIdAry = $dbHouseNewChargeNumber->getColumnByProject([['p.id', '=', $projectId]], 'rule_id');
                $summaryData['rule_id'] = isset($ruleIdAry[0]) && $ruleIdAry[0] ? $ruleIdAry[0] : '';
                $service_house_new_cashier = new HouseNewCashierService();
                $summary_id = $service_house_new_cashier->addOrderSummary($summaryData);
                fdump_api(['msg' => '生成父级账单', 'payOrderParam' => $summaryData, 'summary_id' => $summary_id, 'line' => __LINE__], 'worksOrder/addPayOrderLog', 1);

                $plat_order = array(
                    'orderid' => '',
                    'business_type' => 'village_new_pay',
                    'business_id' => $summary_id,
                    'order_name' => "工单收费【ID：{$order_id}】",
                    'uid' => $summaryData['pay_uid'],
                    'total_money' => $charge_price,
                    'wx_cheap' => 0,
                    'add_time' => time(),
                );
                if ($app_type == 'packapp') {
                    $plat_order['is_mobile_pay'] = 1;
                } elseif ($app_type == 'flutter' || $app_type == 1 || $app_type == 2) {
                    $plat_order['is_mobile_pay'] = 2;
                } elseif ($app_type == 'wxapp') {
                    $plat_order['is_mobile_pay'] = 2;
                }
                $service_plat_order = new PlatOrderService();
                $plat_id = $service_plat_order->addPlatOrder($plat_order);
                fdump_api(['msg' => '生成plat_order账单', 'plat_order' => $plat_order, 'plat_id' => $plat_id, 'line' => __LINE__], 'worksOrder/addPayOrderLog', 1);
                if ($charge_price <= 0 || $pay_type == 2) {
                    $sNewPayService = new NewPayService();
                    $res = $sNewPayService->offlineAfterPay($summary_id);
                    $updatePayOrderParam = [];
                    $updatePayOrderParam['pay_type'] = $pay_type;
                    $updatePayOrderParam['pay_time'] = isset($orderParam['pay_time']) && $orderParam['pay_time'] ? $orderParam['pay_time'] : time();
                    if (isset($orderParam['offline_pay_type'])) {
                        $updatePayOrderParam['offline_pay_type'] = $orderParam['offline_pay_type'];
                    }
                    $updatePayOrderParam['is_online'] = 2;
                    $updatePayOrderParam['is_paid'] = 1;
                    $updatePayOrderParam['pay_money'] = $charge_price;
                    $updatePayOrderParam['summary_id'] = $summary_id;
                    $db_house_new_pay_order->saveOne(['order_id' => $pay_order_id], $updatePayOrderParam);
                    fdump_api(['msg' => '更新账单为线下已支付', 'updatePayOrderParam' => $updatePayOrderParam, 'pay_order_id' => $pay_order_id, 'line' => __LINE__], 'worksOrder/addPayOrderLog', 1);

                    $updateSummaryData = [];
                    $updateSummaryData['pay_type'] = $pay_type;
                    $updateSummaryData['pay_time'] = isset($orderParam['pay_time']) && $orderParam['pay_time'] ? $orderParam['pay_time'] : time();
                    if (isset($orderParam['offline_pay_type'])) {
                        $updateSummaryData['offline_pay_type'] = $orderParam['offline_pay_type'];
                    }
                    $updateSummaryData['is_online'] = 2;
                    $updateSummaryData['is_paid'] = 1;
                    $updateSummaryData['pay_money'] = $charge_price;
                    $service_house_new_cashier->saveOrder(['summary_id' => $summary_id], $updateSummaryData);
                    fdump_api(['msg' => '更新总账单为线下已支付', 'updateSummaryData' => $updateSummaryData, 'plat_id' => $summary_id, 'line' => __LINE__], 'worksOrder/addPayOrderLog', 1);

                    $platUpdateData = ['pay_time' => time(), 'paid' => 1];
                    $service_plat_order->savePlatOrder(['order_id' => $plat_id], $platUpdateData);
                    fdump_api(['msg' => '更新plat为已支付', 'platUpdateData' => $platUpdateData, 'plat_id' => $plat_id, 'line' => __LINE__], 'worksOrder/addPayOrderLog', 1);

                    $result['plat_id'] = $plat_id;
                    $result['summary_id'] = $summary_id;
                    $result['pay_order_url'] = '';
                } else {
                    $db_house_new_pay_order->saveOne(['order_id' => $pay_order_id], ['summary_id' => $summary_id]);
                    $pay_order_url = get_base_url('pages/pay/check?order_id=' . $plat_id . '&order_type=village_new_pay');
                    $result['plat_id'] = $plat_id;
                    $result['summary_id'] = $summary_id;
                    $result['pay_order_url'] = $pay_order_url;
                    $result['order_type'] = 'village_new_pay';
                }
                fdump_api(['msg' => '订单返回', 'result' => $result, 'line' => __LINE__], 'worksOrder/addPayOrderLog', 1);
            }
            $worksOrderUpdate = [
                'pay_order_id' => $pay_order_id,
                'project_id' => $projectId,
                'rule_id' => $ruleId,
            ];
            if ($pay_type > 0) {
                $worksOrderUpdate['pay_type'] = $pay_type;
                if ($pay_type == 2) {
                    $worksOrderUpdate['pay_time'] = isset($orderParam['pay_time']) && $orderParam['pay_time'] ? $orderParam['pay_time'] : time();
                    if (isset($orderParam['offline_pay_type'])) {
                        $worksOrderUpdate['offline_pay_type'] = $orderParam['offline_pay_type'];
                    }
                }
            }
            fdump_api(['result' => $result, 'worksOrderUpdate' => $worksOrderUpdate, 'line' => __LINE__], 'worksOrder/addPayOrderLog', 1);
            $db_house_new_repair_works_order->saveOne(['order_id' => $order_id], $worksOrderUpdate);
        } elseif (!empty($work_order_info)) {
            $field = 'r.*,c.charge_type,c.charge_number_name,c.property_id,p.name,p.subject_id,p.type';
            $rule_info = $db_house_new_charge_rule->getFind(['r.id' => $work_order_info['rule_id']], $field);
            fdump_api([$work_order_info, $rule_info], 'addPayOrder_0414', 1);
            if (!empty($rule_info)) {
                if (!empty($work_order_info['worker_id'])) {
                    $role_info = $db_house_admin->getOne(['wid' => $work_order_info['worker_id']], 'id');
                }
                $postData['order_type'] = $rule_info['charge_type'];
                $postData['order_name'] = $service_house_new_charge->charge_type[$rule_info['charge_type']];
                $postData['village_id'] = $rule_info['village_id'];
                $postData['property_id'] = $rule_info['property_id'];
                $postData['total_money'] = $work_order_info['charge_price'];
                $postData['modify_money'] = $postData['total_money'];
                $postData['role_id'] = isset($role_info['id']) && !empty($role_info['id']) ? $role_info['id'] : $work_order_info['worker_id'];
                $postData['is_prepare'] = 2;
                $postData['rule_id'] = $rule_info['id'];
                $postData['project_id'] = $rule_info['charge_project_id'];
                $postData['order_no'] = 'works_order_' . $order_id;
                $postData['add_time'] = time();
                $postData['from'] = 4;
                $postData['service_start_time'] = time();
                $postData['service_end_time'] = time();
                $postData['unit_price'] = $rule_info['charge_price'];
                $postData['room_id'] = $work_order_info['room_id'];
                $postData['pigcms_id'] = $work_order_info['bind_id'] ? $work_order_info['bind_id'] : 0;
                $postData['name'] = $work_order_info['name'] ? $work_order_info['name'] : '';
                $postData['phone'] = $work_order_info['phone'] ? $work_order_info['phone'] : '';
                $db_house_new_pay_order = new HouseNewPayOrder();
                $postData['is_paid'] = 2;
                fdump_api(['postData' => $postData], 'addPayOrder_0414', 1);
                $pay_order_id = $db_house_new_pay_order->addOne($postData);
                fdump_api(['pay_order_id' => $pay_order_id, 'postData' => $postData, 'line' => __LINE__], 'addPayOrder_0414', 1);
                $pay_time = 0;
                if ($work_order_info['pay_type'] == 2) {
                    $order_info = $db_house_new_pay_order->get_one(['order_id' => $pay_order_id]);
                    if ($order_info && !$order_info->isEmpty()) {
                        $order_info = $order_info->toArray();
                        $order_info['is_only_pay_this'] = 1;  //只支付当前订单
                    }
                    $order_list[] = $order_info;
                    $service_new_pay = new NewPayService();
                    $remark = '工单线下支付账单';
                    $service_new_pay->goPay($order_list, $rule_info['village_id'], 2, $work_order_info['offline_pay_type'], '', $remark);
                    $pay_time = time();
                }
                if ($pay_order_id) {
                    $db_house_new_repair_works_order->saveOne(['order_id' => $order_id], ['pay_order_id' => $pay_order_id, 'pay_time' => $pay_time]);
                }
            }
        }
        $result['pay_order_id'] = $pay_order_id;
        return $result;
    }


    //todo 开启抢单模式，订单状态同步修改 event_status：15
    public function grabOrder($village_id, $order_id, $cat_id = 0)
    {
        $time = time();
        if ($cat_id <= 0) {
            return true;
        }
        $is_status = (new HouseVillageService())->checkVillageField($village_id, 'is_grab_order');
        if (!$is_status) {
            return true;
        }
        $cate = (new HouseNewRepairCate())->getOne(['id' => $cat_id], 'id,type,uid,parent_id');
        if (!$cate || $cate->isEmpty()) {
            return true;
        }
        if ($cate['type'] == 1) { //工单二级分类负责人为单人
            if (!empty($cate['uid'])) {
                return true;
            }
        } else {  //工单二级分类负责人为多人
            $info = (new HouseNewRepairDirectorScheduling())->getOne(
                [
                    ['cate_id', '=', $cate['id']],
                    ['date_type', '=', date('w')],
                ],
                'start_time,end_time'
            );
            if ($info && !$info->isEmpty()) {
                $start = strtotime(date('Y-m-d ') . $info['start_time'] . ':00:00');
                $end = strtotime(date('Y-m-d ') . $info['end_time'] . ':59:59');
                if ($time >= $start && $time <= $end) {
                    return true;
                }
            }
        }
        $updateArr = [
            'event_status' => 15,
            'update_time' => $time,
        ];
        $res = (new HouseNewRepairWorksOrder())->saveOne([
            ['order_id', '=', $order_id],
            ['event_status', 'in', [10, 16, 17]]
        ], $updateArr);
        $this->triggerGrabOrderNoticeWork($village_id, $cate['parent_id']);
        return $res;
    }


    //todo 获取分类id 包含有无设置的关联部门
    public function getRepairCateId($village_id, $wid)
    {
        $dbRelation = new HouseNewRepairCateGroupRelation();
        $group_id = $this->getGroupId($wid);
        $cate_id = $dbRelation->getColumn([
            ['village_id', '=', $village_id],
            ['group_id', '=', $group_id]
        ], 'cate_id');
        return $cate_id ? array_values(array_unique($cate_id)) : [];
    }

    //todo 工作人员抢单
    public function grabRepairOrder($village_id, $login_role, $worker_id, $order_id)
    {
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $is_status = (new HouseVillageService())->checkVillageField($village_id, 'is_grab_order');
        if (!$is_status) {
            throw new \think\Exception('该小区暂未开启抢单,不可操作');
        }
        $time = time();
        $where_order = [];
        $where_order[] = ['village_id', '=', $village_id];
        $where_order[] = ['order_id', '=', $order_id];
        $where_order[] = ['event_status', '=', 15];
        $order_detail = $db_house_new_repair_works_order->getOne($where_order);
        if (empty($order_detail)) {
            throw new \think\Exception('该工单不存在或已被抢单');
        }
        $property_id = $order_detail['property_id'];
        $this->checkGrabOrder(2, $village_id, $worker_id, $order_detail);
        if (HouseWorkConst::MANAGE_APP_VILLAGE_WORK == $login_role) {
            // 小区物业工作人员
            $info['wid'] = $worker_id;
        } elseif (HouseWorkConst::MANAGE_APP_VILLAGE_ADMIN == $login_role) {
            // 小区物业管理人员
            $info = (new HouseAdmin())->getOne([['id', '=', $worker_id]]);
        } elseif (HouseWorkConst::MANAGE_APP_PROPERTY_USER == $login_role) {
            // 物业普通管理员
            $info = (new PropertyAdmin())->get_one([['id', '=', $worker_id]]);
        } elseif (HouseWorkConst::MANAGE_APP_PROPERTY_ADMIN == $login_role) {
            // 物业总管理员
            $info = (new PropertyAdmin())->get_one([['id', '=', $worker_id]]);
        } else {
            throw new \think\Exception("当前身份无权限");
        }
        if (empty($info)) {
            throw new \think\Exception("当前身份不存在");
        }
        $wid = $info['wid'];
        $worker = (new HouseWorker())->getOne(['wid' => $wid], 'wid,name,phone,department_id');
        if (empty($worker)) {
            throw new \think\Exception("当前用户不存在");
        }
        $log_data = [
            'order_id' => $order_id,
            'log_name' => 'work_grab',
            'operator_type' => 20,
            'log_content' => '',
            'log_imgs' => '',
            'add_time' => $time,
            'log_operator' => $worker['name'],
            'log_phone' => '',
            'operator_id' => 0,
            'log_uid' => 0,
            'evaluate' => 0,
            'village_id' => $village_id,
            'property_id' => $order_detail['property_id']
        ];
        Db::startTrans();
        try {
            $otherData = array();
            if ($wid > 0) {
                $otherData = array('opt_type' => 'assign_to_house_worker', 'wid' => $wid, 'village_id' => $village_id);
            }
            $id = $this->addRepairLog($log_data, $otherData);
            if ($id) {
                $data['update_time'] = $time;
                if ($wid) {
                    $data['event_status'] = 20;
                    $data['worker_id'] = $wid;
                    $data['group_id'] = $worker['department_id'];
                } else {
                    $data['event_status'] = 40;
                }
                $db_house_new_repair_works_order->saveOne(['order_id' => $order_id], $data);

                //当提交的工单有指派时
                if ($wid > 0) {
                    if ($order_detail['order_type'] == 4) {
                        //针对工作人员提交的工单有指派时 给提交人发送通知
                        $this->newRepairSendMsg(11, $order_id, $order_detail['uid'], $property_id);
                        //给工作人员发送通知
                        $this->newRepairSendMsg(12, $order_id, 0, $property_id);
                    } else {
                        //给用户和工作人员发通知
                        $this->newRepairSendMsg(10, $order_id, 0, $property_id);
                    }

                    $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
                    if ($jz_open) {
                        $workRecord = [
                            'worker_id' => $wid,
                            'village_id' => $village_id,
                            'property_id' => $order_detail['property_id'],
                            'works_order_id' => $order_id,
                            'latest_receive_time' => $time
                        ];
                        $serviceHouseCommunityWorkOrder = new HouseCommunityWorkOrderService();
                        $serviceHouseCommunityWorkOrder->handleWorkOrderWorkRecord($workRecord);
                        $workName = isset($worker['name']) && $worker['name'] ? trim($worker['name']) : '';
                        $workPhone = isset($worker['phone']) && $worker['phone'] ? trim($worker['phone']) : '';
                        $step = ['title' => '手动接单', 'name' => $workName, 'phone' => $workPhone, 'content' => $workName . '主动接单', 'type' => 'text', 'time' => $time, 'worker_id' => $worker_id];
                        $serviceHouseCommunityWorkOrder->receiveWorkOrderSetConfig($order_id, $step, $village_id, 0, 1);
                    }
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    //查询工作人员
    public function getWorker($worker_id, $field = 'wid,name,openid,nickname,village_id')
    {
        $worker = (new HouseWorker())->getOne(['wid' => $worker_id], $field);
        $data = [];
        if ($worker && !$worker->isEmpty()) {
            $data = $worker->toArray();
        }
        return $data;
    }

    //todo 返回提示文字
    public function getMsgTips($type)
    {
        $t1 = $t2 = $t3 = '';
        switch ($type) {
            case 10: //todo [工作人员]+[用户] 当有工单指派给自己时  针对用户提交的工单
                $t1 = '您有新的工单待处理。';
                $t2 = '您提交的工单已被指派。';
                break;
            case 11: //todo [工作人员]当自己提交的工单被指派处理人员时    针对工作人员提交的工单
                $t3 = '您提交的工单已被指派。';
                break;
            case 12: //todo [工作人员]当有工单转单给自己时 给自己发送
                $t1 = '您有新的工单待处理。';
                break;
            case 13: //todo [工作人员]当工作人员关闭指派给自己的工单时
                $t1 = '您负责的工单已被业主关闭。';
                break;
            case 14: //todo [工作人员]当工作人员撤回指派给自己的工单时
                $t1 = '您负责的工单已被业主撤回。';
                break;
            case 15: //todo [工作人员]管理指派给工作人员
                $t1 = '您有新的工单待处理。';
                break;
            case 20: //todo [工作人员]当业主评价自己结单的工单时
                $t1 = '业主评价了您负责的工单。';
                break;
            case 21: //todo [工作人员]当业主关闭指派给自己的工单时
                $t1 = '您负责的工单已被业主关闭。';
                break;
            case 22: //todo [工作人员]当业主撤回指派给自己的工单时
                $t1 = '您负责的工单已被业主撤回。';
                break;
            case 30: //todo [用户]当用户提交的工单已结单时 此操作只有工作人员结单
                $t2 = '您提交的工单已结单。';
                break;
            case 31: //todo [工作人员]当工作人员提交的工单已结单时 此操作只有工作人员结单
                $t3 = '您提交的工单已结单。';
                break;
            case 32: //todo [用户]当用户提交的工单已结单时 此操作只有工作人员结单
                $t2 = '您提交的工单已再一次结单。';
                break;
            case 33: //todo [工作人员]当工作人员提交的工单已结单时 此操作只有工作人员结单
                $t3 = '您提交的工单已再一次结单。';
                break;
            case 40: //todo [用户]当自己提交的工单拒绝时 工作人员拒绝用户的工单给用户通知
                $t2 = '您提交的工单已拒绝。';
                break;
            case 41: //todo [工作人员]当工作人员A给工作人员B转单，工作人员B驳回时 发给A
                $t3 = '您的转单已被驳回，请去处理。';
                break;
            case 42: //todo [工作人员]当自己提交的工单拒绝时 工作人员拒绝工作人员的工单给工作人员通知
                $t3 = '您提交的工单已拒绝。';
                break;
            case 50: //todo [用户]当工作人员跟进工作时，发送给业主 只有收费才有该项 且只发送一次
                $t2 = '您提交的收费工单需要确认。';
                break;
            case 51: //todo [工作人员]当业主同意收费工单时，发送给工作人员
                $t1 = '业主已同意该笔费用，请去处理。';
                break;
            case 52: //todo [工作人员]当业主拒绝收费工单时，发送给工作人员
                $t1 = '业主已拒绝该笔费用，请去处理。';
                break;
            case 53: //todo [工作人员]当业主拒绝收费工单时，发送给工作人员
                $t1 = '业主已拒绝该方案，请去处理。';
                break;
            case 60: //todo [工作人员]订单超时给指定工作人员发送
                $t1 = '您负责的工单已超时，请去处理';
                break;
            case 70: //todo [工作人员or用户]后台工单中心操作已办结 
                $t2 = '您提交的工单已办结，请去查看。';
                break;
            case 80: //todo [工作人员]系统自动同意收费工单时，发送给工作人员
                $t1 = '系统自动同意该笔费用，请去处理。';
                break;
            case 81: //todo [工作人员]系统自动拒绝收费工单时，发送给工作人员
                $t1 = '系统自动拒绝该笔费用，请去处理。';
                break;
            case 82: //todo [工作人员]施工人员提供施工方案，发送给工作人员
                $t1 = '施工方提供施工方案，请去处理。';
                break;
            case 83: //todo [工作人员]工作人员同意施工方案，发送给工作人员
                $t1 = '工作人员同意施工方案，请去处理。';
                break;
            case 84: //todo [工作人员]拒绝施工方案，发送给工作人员
                $t1 = '工作人员拒绝施工方案，请去处理。';
                break;
        }
        return ['worker' => $t1, 'user' => $t2, 'worker2' => $t3];
    }

    /**
     * 发送模板消息
     * @param integer|string $type
     * @param integer|string $order_id 工单id
     * @param integer $wid 上次工作人员id
     * @author: liukezhu
     * @date : 2022/4/18
     */
    public function newRepairSendMsg($type, $order_id, $wid = 0, $property_id = 0, $params = [])
    {
        $TemplateNewsService = new TemplateNewsService();
        $order = (new HouseNewRepairWorksOrder())->getOrderCate([['o.order_id', '=', $order_id]], 'o.order_id,o.uid,o.bind_id,o.event_status,o.worker_id,o.order_type,c1.cate_name as t1,c2.cate_name as t2,o.address_txt,o.room_id,o.single_id,o.floor_id,o.layer_id,o.village_id');
        if (!$order || $order->isEmpty()) {
            fdump_api(['$cate不存在==' . __LINE__, $type, $order_id, $wid, $order], 'newRepair/sendMsg', 1);
            return true;
        }
        $order = $order->toArray();
        $arr = [];
        $title = $order['t1'] . '/' . $order['t2'];
        if (mb_strlen($title) > 15) {
            $title = msubstr($title, 0, 15);
        }
        $msgTips = $this->getMsgTips($type);
        $isToOtherVillage = isset($params['isToOtherVillage']) ? intval($params['isToOtherVillage']) : 0;
        //        $toOtherVillageId = isset($params['toOtherVillageId']) ? intval($params['toOtherVillageId']) : 0;
        //        $toPropertyId = isset($params['toPropertyId']) ? intval($params['toPropertyId']) : 0;
        //        $toWorkId = isset($params['toWorkId']) ? intval($params['toWorkId']) : 0;
        //通知工单处理的工作人员
        $href = cfg('site_url') . '/packapp/community/pages/CommunityPages/workOrder/eventdetails?order_id=' . $order_id . '&gotoIndex=tem_msg';
        $event_status = $order['event_status'] ? $this->order_work_status_txt[$order['event_status']] : '无';


        $single_id = isset($order['single_id']) ? intval($order['single_id']) : 0;
        $floor_id = isset($order['floor_id']) ? intval($order['floor_id']) : 0;
        $layer_id = isset($order['layer_id']) ? intval($order['layer_id']) : 0;
        $room_id = isset($order['room_id']) ? intval($order['room_id']) : 0;
        $village_id = isset($order['village_id']) ? intval($order['village_id']) : 0;
        if ($single_id > 0 && $floor_id > 0 && $layer_id > 0 && $room_id > 0 && $village_id > 0) {
            $service_house_village = new HouseVillageService();
            $address = $service_house_village->getSingleFloorRoom($single_id, $floor_id, $layer_id, $room_id, $village_id);
            $address = $address ? $address : '无';
        } else {
            $address = $order['address_txt'] ?: '无';
        }
        if ($isToOtherVillage && $village_id) {
            $villageInfo = (new HouseVillage())->getOne($village_id, 'village_id, village_name');
            $village_name = isset($villageInfo['village_name']) ? trim($villageInfo['village_name']) : '';
            $address = $village_name . $address;
        }
        if (!empty($msgTips['worker']) && $order['worker_id']) {
            $worker = $this->getWorker($order['worker_id']);
            if (empty($worker)) {
                fdump_api(['通知工作人员openid不存在==' . __LINE__, $type, $order_id, $order['worker_id'], $worker], 'newRepair/sendMsg', 1);
            } else {
                $ticket = Token::createToken($worker['wid'], 6);
                $href = $href . '&ticket=' . $ticket . '&village_id=' . $worker['village_id'];
                $arr[] = [
                    'href' => $href,
                    'wecha_id' => $worker['openid'],
                    'first' => $msgTips['worker'],
                    'keyword1' => $title,
                    'keyword2' => '已发送',
                    'keyword3' => date('H时i分', $_SERVER['REQUEST_TIME']),
                    'remark' => '请点击查看详细信息！',
                    'new_info' => [ //新版本发送需要的信息
                        'tempKey' => '44716', //新模板号
                        'thing12' => $title, //服务项目
                        'phrase2' => $event_status, //订单类型
                        'time13' => date('H:i', $_SERVER['REQUEST_TIME']), //服务时间
                        'thing11' => $address, //服务地址
                    ],
                ];
            }
        }
        //通知上次操作的工作人员或工作人员自己提交的工单
        if (!empty($msgTips['worker2']) && $wid > 0) {
            $worker = $this->getWorker($wid);
            if (empty($worker)) {
                fdump_api(['通知上次操作的工作人员或工作人员自己提交的工单openid不存在2==' . __LINE__, $type, $order_id, $order['worker_id'], $worker, $arr], 'newRepair/sendMsg', 1);
            } else {
                $ticket = Token::createToken($worker['wid'], 6);
                $href = $href . '&ticket=' . $ticket . '&village_id=' . $worker['village_id'];
                $arr[] = [
                    'href' => $href,
                    'wecha_id' => $worker['openid'],
                    'first' => $msgTips['worker2'],
                    'keyword1' => $title,
                    'keyword2' => '已发送',
                    'keyword3' => date('H时i分', $_SERVER['REQUEST_TIME']),
                    'remark' => '请点击查看详细信息！',
                    'new_info' => [ //新版本发送需要的信息
                        'tempKey' => '44716', //新模板号
                        'thing12' => $title, //服务项目
                        'phrase2' => $event_status, //订单类型
                        'time13' => date('H:i', $_SERVER['REQUEST_TIME']), //服务时间
                        'thing11' => $address, //服务地址
                    ],
                ];
            }
        }
        //针对已完结 判断给工作人员发送通知
        if ($type == 70 && intval($order['order_type']) == 4 && $order['uid']) {
            $worker = $this->getWorker($order['uid']);
            if (empty($worker)) {
                fdump_api(['针对已完结，工作人员openid不存在2==' . __LINE__, $type, $order_id, $worker, $arr], 'newRepair/sendMsg', 1);
            } else {
                $ticket = Token::createToken($worker['wid'], 6);
                $href = $href . '&ticket=' . $ticket . '&village_id=' . $worker['village_id'];
                $arr[] = [
                    'href' => $href,
                    'wecha_id' => $worker['openid'],
                    'first' => $msgTips['worker2'],
                    'keyword1' => $title,
                    'keyword2' => '已发送',
                    'keyword3' => date('H时i分', $_SERVER['REQUEST_TIME']),
                    'remark' => '请点击查看详细信息！',
                    'new_info' => [ //新版本发送需要的信息
                        'tempKey' => '44716', //新模板号
                        'thing12' => $title, //服务项目
                        'phrase2' => $event_status, //订单类型
                        'time13' => date('H:i', $_SERVER['REQUEST_TIME']), //服务时间
                        'thing11' => $address, //服务地址
                    ],
                ];
            }
        }
        //通知用户
        if (!empty($msgTips['user']) && intval($order['order_type']) == 0 && $order['uid']) {
            if ($type == 10 && empty($order['worker_id'])) {
                fdump_api(['no worker_id==' . __LINE__, $type, $order_id, $wid, $order, $msgTips, $arr], 'newRepair/sendMsg', 1);
            } else {
                $user = (new User())->getOne(['uid' => $order['uid']], 'openid');
                if ($user && !$user->isEmpty()) {
                    if (empty($user['openid'])) {
                        fdump_api(['用户openid不存在==' . __LINE__, $type, $order_id, $wid, $user, $arr], 'newRepair/sendMsg', 1);
                    } else {
                        $arr[] = [
                            'href' => get_base_url('pages/houseMeter/workOrder/eventdetails?order_id=' . $order_id),
                            'wecha_id' => $user['openid'],
                            'first' => $msgTips['user'],
                            'keyword1' => $title,
                            'keyword2' => '已发送',
                            'keyword3' => date('H时i分', $_SERVER['REQUEST_TIME']),
                            'remark' => '请点击查看详细信息！',
                            'new_info' => [ //新版本发送需要的信息
                                'tempKey' => '44716', //新模板号
                                'thing12' => $title, //服务项目
                                'phrase2' => $event_status, //订单类型
                                'time13' => date('H:i', $_SERVER['REQUEST_TIME']), //服务时间
                                'thing11' => $address, //服务地址
                            ],
                        ];
                    }
                }
            }
        }
        fdump_api(['$arr==' . __LINE__, $type, $order_id, $wid, $order, $msgTips, $arr], 'newRepair/sendMsg', 1);
        if ($arr) {
            $xtype = 0;
            if ($property_id > 0) {
                $xtype = 1;
            } else {
                $property_id = 0;
            }
            foreach ($arr as $v) {
                $TemplateNewsService->sendTempMsg('OPENTM400166399', $v, 0, $property_id, $xtype); //todo 类目模板OPENTM400166399
            }
        }
        return true;
    }

    /**
     * 工单记录指派人信息
     * @param $log_id
     * @param $wid
     * @return bool
     * @author: liukezhu
     * @date : 2022/6/24
     */
    public function workOrderLogSave($log_id, $wid)
    {
        $worker = $this->getWorker($wid, 'wid,name,phone');
        if (!$worker) {
            return true;
        }
        $where[] = ['log_id', '=', $log_id];
        $log_info = [
            'wid' => $worker['wid'],
            'name' => $worker['name'],
            'phone' => $worker['phone']
        ];
        return (new HouseNewRepairWorksOrderLog())->saveOne($where, ['log_info' => serialize($log_info)]);
    }


    public function getUserAddress($village_id, $pigcms_id)
    {
        $db_house_village_user_bind = new HouseVillageUserBind();
        $service_house_village = new HouseVillageService();
        $address['address'] = '';
        $address['id_arr'] = 0;
        $address['type'] = 'room';
        $user_info = $db_house_village_user_bind->getOne(['pigcms_id' => $pigcms_id, 'village_id' => $village_id], 'pigcms_id,village_id,uid,floor_id,vacancy_id,single_id,layer_id');
        if (!empty($user_info)) {
            if (!empty($user_info['village_id']) && !empty($user_info['floor_id']) && !empty($user_info['vacancy_id']) && !empty($user_info['single_id']) && !empty($user_info['layer_id'])) {
                $address['address'] = $service_house_village->getSingleFloorRoom($user_info['single_id'], $user_info['floor_id'], $user_info['layer_id'], $user_info['vacancy_id'], $user_info['village_id']);
                $address['id_arr'] = $user_info['vacancy_id'];
                $address['type'] = 'room';
            }
        }
        return $address;
    }

    //todo 抢单模式 设置非工单分类部门人员不可操作
    public function checkGrabOrder($type = 1, $village_id, $wid, $order)
    {
        if ($type == 1) {
            $is_status = (new HouseVillageService())->checkVillageField($village_id, 'is_grab_order');
            if (!$is_status) {
                return true;
            }
        }
        $group_id = $this->getGroupId($wid);
        $relation_id = (new HouseNewRepairCateGroupRelation())->getOne([
            ['village_id', '=', $village_id],
            ['group_id', '=', $group_id],
            ['cate_id', '=', $order['cat_fid']]
        ], 'id');
        if (!$relation_id || $relation_id->isEmpty()) {
            throw new \think\Exception('当前工单您无权查看');
        }
        return true;
    }

    //todo 触发抢单通知工作人员
    public function triggerGrabOrderNoticeWork($village_id, $cate_id = 0)
    {
        $ProcessSubPlan = new ProcessSubPlan();
        $arr = array();
        $arr['param'] = serialize(array(
            'type' => 'grab_order_notice',
            'village_id' => $village_id,
            'cate_id' => $cate_id,
        ));
        $arr['plan_time'] = -100;
        $arr['space_time'] = 0;
        $arr['add_time'] = time();
        $arr['file'] = 'sub_house_village';
        $arr['time_type'] = 1;
        $arr['unique_id'] = 'grab_order_notice_' . $village_id . '_' . $cate_id;
        $arr['rand_number'] = mt_rand(1, max(cfg('sub_process_num'), 3));
        $result = $ProcessSubPlan->get_one([
            ['unique_id', '=', $arr['unique_id']]
        ], 'id');
        fdump_api(['触发抢单通知工作人员==' . __LINE__, $village_id, $cate_id, $result], 'grab_order/trigger_log', 1);
        if ($result && !$result->isEmpty()) {
            return true;
        }
        $ProcessSubPlan->add($arr);
        return true;
    }

    //todo 发送模板消息通知工作人员
    public function sendGrabOrderNoticeWork($village_id, $cate_id = 0)
    {
        fdump_api(['发送模板消息通知工作人员==' . __LINE__, $village_id, $cate_id], 'grab_order/send_log', 1);
        $TemplateNewsService = new TemplateNewsService();
        $where[] = ['o.village_id', '=', $village_id];
        $where[] = ['o.is_send_grab', '=', 0];
        $where[] = ['o.event_status', '=', 15];
        $where[] = ['r.id', 'exp', Db::raw('IS NOT NULL')];
        $where[] = ['w.is_del', '=', 0];
        $where[] = ['w.status', '<>', 4];
        $where[] = ['w.openid', '<>', ''];
        //        $where[]=['g.id', 'exp', Db::raw('IS NULL')];
        if ($cate_id) {
            $where[] = ['o.cat_fid', '=', $cate_id];
        }
        $where['_string'] = 'isNotTime';
        $field = 'o.order_id,o.village_id,o.property_id,o.cat_fid,c1.cate_name as t1,c2.cate_name as t2,w.wid,w.name,w.phone,w.openid,o.event_status,o.room_id,o.single_id,o.floor_id,o.layer_id';
        $list = (new HouseNewRepairWorksOrder())->getBindCateOrder($where, $field);
        if ($list && !$list->isEmpty()) {
            $list = $list->toArray();
            $record = [];
            $orderIdAll = [];
            fdump_api(['$list==' . __LINE__, $village_id, $cate_id, $list], 'grab_order/send_log', 1);
            foreach ($list as $v) {
                $event_status = $v['event_status'] ? $this->order_work_status_txt[$v['event_status']] : '无';
                if ($v['single_id'] && $v['floor_id'] && $v['layer_id'] && $v['room_id'] && $v['village_id']) {
                    $service_house_village = new HouseVillageService();
                    $address = $service_house_village->getSingleFloorRoom($v['single_id'], $v['floor_id'], $v['layer_id'], $v['room_id'], $v['village_id']);
                    $address = $address ? $address : '无';
                } else {
                    $address = $v['address_txt'] ?: '无';
                }
                $title = $v['t1'] . '/' . $v['t2'];
                if (mb_strlen($title) > 15) {
                    $title = msubstr($title, 0, 15);
                }
                $href = cfg('site_url') . '/packapp/community/pages/CommunityPages/workOrder/eventdetails?order_id=' . $v['order_id'] . '&gotoIndex=tem_msg';
                $ticket = Token::createToken($v['wid'], 6);
                $href = $href . '&ticket=' . $ticket . '&village_id=' . $v['village_id'];

                $param = [
                    'href' => $href,
                    'wecha_id' => $v['openid'],
                    'first' => '您有新的工单待抢单，请去处理。',
                    'keyword1' => $title,
                    'keyword2' => '已发送',
                    'keyword3' => date('H时i分', $_SERVER['REQUEST_TIME']),
                    'remark' => '请点击查看详细信息！',
                    'is_repeat' => 1,
                    'new_info' => [ //新版本发送需要的信息
                        'tempKey' => '44716', //新模板号
                        'thing12' => $title, //服务项目
                        'phrase2' => $event_status, //订单类型
                        'time13' => date('H:i', $_SERVER['REQUEST_TIME']), //服务时间
                        'thing11' => $address, //服务地址
                    ],
                ];
                $record[] = [
                    'vllage_id' => $v['village_id'],
                    'cate_id' => $v['cat_fid'],
                    'wid' => $v['wid'],
                    'order_id' => $v['order_id'],
                    'add_time' => date('Y-m-d H:i:s', time())
                ];
                $orderIdAll[$v['order_id']] = $v['order_id'];

                fdump_api(['$param==' . __LINE__, $v, $param, $record], 'grab_order/send_log', 1);

                $TemplateNewsService->sendTempMsg('OPENTM400166399', $param, 0, $v['property_id'], 1);
            }
            if ($orderIdAll) {
                //已经发生通知的订单 不可再发送
                (new HouseNewRepairWorksOrder())->saveOne([
                    ['order_id', 'in', (array_values($orderIdAll))]
                ], ['is_send_grab' => 1]);
            }
            if ($record) {
                (new HouseNewRepairGraborderNoticeRecord())->addAll($record);
            }
        }
        return true;
    }


    /**
     * 可视化工单数据统计
     * @author:zhubaodi
     * @date_time: 2022/8/26 16:45
     */
    public function getWorkorder($village_id)
    {
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $db_house_new_repair_work_order_log = new HouseNewRepairWorksOrderLog();
        $start_time = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1, date("Y")));
        $all_count = $db_house_new_repair_works_order->getCount([['add_time', '>=', strtotime($start_time)], ['village_id', '=', $village_id]]); //总共单数
        $processing_count = $db_house_new_repair_works_order->getCount([['add_time', '>=', strtotime($start_time)], ['event_status', '>=', '30'], ['event_status', '<', '40'], ['village_id', '=', $village_id]]); //处理中数
        $untreated_count = $db_house_new_repair_works_order->getCount([['add_time', '>=', strtotime($start_time)], ['event_status', '<', '30'], ['village_id', '=', $village_id]]); //未处理数
        $processed_count = $db_house_new_repair_works_order->getCount([['add_time', '>=', strtotime($start_time)], ['event_status', '>=', '40'], ['village_id', '=', $village_id]]); //已处理数
        $favorable_comments_count = $db_house_new_repair_work_order_log->getCount([['add_time', '>=', strtotime($start_time)], ['evaluate', '=', 5], ['village_id', '=', $village_id]], 'order_id'); //好评数
        $favorable_comments_rate = $all_count ? intval(round_number(($favorable_comments_count / $all_count), 2) * 100) : 100;
        $data['all_count'] = $all_count;
        $data['favorable_comments_rate'] = $favorable_comments_rate;
        $data['favorable_comments_count'] = $favorable_comments_count;
        $data['processing_count'] = $processing_count;
        $data['untreated_count'] = $untreated_count;
        $data['processed_count'] = $processed_count;

        return $data;
    }

    public function getNewRepairWorksOrderLog($whereArr, $field = '*', $page = 1, $limit = 20, $groupBy = '')
    {

        $dataArr = ['list' => array(), 'total_limit' => $limit, 'count' => 0];
        if (empty($whereArr)) {
            return $dataArr;
        }
        $houseNewRepairWorksOrderLog = new HouseNewRepairWorksOrderLog();
        $listObj = $houseNewRepairWorksOrderLog->getGroupByList($whereArr, $field, $page, $limit, $groupBy);
        if ($listObj && !$listObj->isEmpty()) {
            $list = $listObj->toArray();
            $houseVillageDb = new HouseVillage();
            $village_info = $houseVillageDb->getInfo(array('village_id' => $list['0']['village_id']), '*');
            $count = $houseNewRepairWorksOrderLog->getCountGroupBy($whereArr, $groupBy);
            $dataArr['count'] = $count > 0 ? $count : 0;
            foreach ($list as $kk => $vv) {
                $list[$kk]['evaluate1'] = 0;
                $list[$kk]['evaluate2'] = 0;
                $list[$kk]['evaluate3'] = 0;
                $list[$kk]['evaluate4'] = 0;
                $list[$kk]['evaluate5'] = 0;
                $roomArr = $this->getUserAddress($vv['village_id'], $vv['operator_id']);
                $list[$kk]['roomaddr'] = '';
                if ($roomArr && $roomArr['address']) {
                    $list[$kk]['roomaddr'] = $roomArr['address'];
                }
                $evaluateWhere = array();
                $evaluateWhere[] = array('village_id', '=', $vv['village_id']);
                $evaluateWhere[] = array('log_name', '=', 'evaluate');
                $evaluateWhere[] = array('log_operator', '=', $vv['log_operator']);
                $evaluateWhere[] = array('log_phone', '=', $vv['log_phone']);
                $evaluateWhere[] = array('operator_id	 ', '=', $vv['operator_id']);
                $evaluateWhere[] = array('log_uid', '=', $vv['log_uid']);
                $evaluate1Where = $evaluateWhere;
                $evaluate1Where[] = array('evaluate', '=', 1);
                $list[$kk]['evaluate1'] = $houseNewRepairWorksOrderLog->getCount($evaluate1Where);

                $evaluate2Where = $evaluateWhere;
                $evaluate2Where[] = array('evaluate', '=', 2);
                $list[$kk]['evaluate2'] = $houseNewRepairWorksOrderLog->getCount($evaluate2Where);

                $evaluate3Where = $evaluateWhere;
                $evaluate3Where[] = array('evaluate', '=', 3);
                $list[$kk]['evaluate3'] = $houseNewRepairWorksOrderLog->getCount($evaluate3Where);

                $evaluate4Where = $evaluateWhere;
                $evaluate4Where[] = array('evaluate', '=', 4);
                $list[$kk]['evaluate4'] = $houseNewRepairWorksOrderLog->getCount($evaluate4Where);

                $evaluate5Where = $evaluateWhere;
                $evaluate5Where[] = array('evaluate', '=', 5);
                $list[$kk]['evaluate5'] = $houseNewRepairWorksOrderLog->getCount($evaluate5Where);
                if ($vv['log_phone']) {
                    $list[$kk]['log_phone'] = $houseVillageDb->phone_desensitization_v($vv['log_phone'], $vv['village_id'], $village_info);
                }
            }
            $dataArr['list'] = $list;
        }
        return $dataArr;
    }

    /**
     * 工单自动评价 小区
     * 查那些小区开启了 工单自动评价
     **/

    public function worksOrderAutoEvaluateVillage()
    {
        $houseVillageInfo = new HouseVillageInfo();
        $whereArr = array();
        $whereArr[] = array('hvi.village_id', '>', 0);
        $whereArr[] = array('hv.status', '=', 1);
        $whereArr[] = array('hvi.is_auto_work_order_evaluate', '=', 1);
        $whereArr[] = array('hvi.work_order_auto_evaluate_time', '>', 0);
        $field = 'hvi.info_id,hvi.village_id,hvi.property_id';
        $villageInfoObj = $houseVillageInfo->getVillageInfoList($whereArr, $field);
        if ($villageInfoObj && !$villageInfoObj->isEmpty()) {
            $villageInfo = $villageInfoObj->toArray();
            fdump_api(['villageInfo' => $villageInfo], '00worksOrderAutoEvaluateVillage', 1);
            foreach ($villageInfo as $vv) {
                $param = array();
                $param['info_id'] = $vv['info_id'];
                $param['village_id'] = $vv['village_id'];
                $this->worksOrderAutoEvaluateVillageQueuePushToJob($param);
            }
        }
        return true;
    }

    public function getBindUserOrWorker($village_id = 0, $keyV = '')
    {
        $returnArr = array('userList' => array(array('is_work' => 0, 'u_idd' => '', 'xname' => '无')));
        $db_house_village_user_bind = new HouseVillageUserBind();
        $where = array(['village_id', '=', $village_id]);
        $where[] = array('type', 'in', [0, 1, 2, 3]);
        $where[] = array('status', '=', 1);
        $whereOr = 'name like "%' . $keyV . '%" or phone like "%' . $keyV . '%"';
        $listObj = $db_house_village_user_bind->getLists($where, $whereOr, 'pigcms_id,uid,name,phone', 'pigcms_id desc', 1, 20);
        if ($listObj && !$listObj->isEmpty()) {
            $listArr = $listObj->toArray();
            foreach ($listArr as $vv) {
                $returnArr['userList'][] = array('is_work' => 0, 'u_idd' => 'b_' . $vv['pigcms_id'], 'xname' => $vv['name'] . '，手机号：' . $vv['phone'] . '【住户人员】');
            }
        }
        $houseWorkerDb = new  HouseWorker();
        $where = array(['village_id', '=', $village_id]);
        $where[] = array('is_del', '<', 0);
        $where[] = array('status', '<', 4);
        $whereOr = 'name like "%' . $keyV . '%" or phone like "%' . $keyV . '%"';
        $fieldStr = 'wid,phone,name';
        $listObj = $houseWorkerDb->getWorkList($where, $whereOr, $fieldStr, 'wid desc', 1, 10);
        if ($listObj && !$listObj->isEmpty()) {
            $listArr = $listObj->toArray();
            foreach ($listArr as $vv) {
                $returnArr['userList'][] = array('is_work' => 1, 'u_idd' => 'w_' . $vv['wid'], 'xname' => $vv['name'] . '，手机号：' . $vv['phone'] . '【工作人员】');
            }
        }
        return $returnArr;
    }

    /**
     * 工单自动关闭 工单
     * 处理每个小区的 工单自动关闭
     **/
    public function worksOrderAutoClose()
    {
//        echo "<pre>";
        $houseVillageInfo = new HouseVillageInfo();
        $houseNewRepairWorksOrder = new HouseNewRepairWorksOrder();
        $where = [
            'ho.event_status' => 40
        ];
        $field = 'MAX(hv.close_order_time) AS close_order_time,ho.order_id,ho.timely_time,ho.order_content,ho.event_status';
        $villageInfo = $houseVillageInfo->getVillageListAndWorkOrder($where, $field);
        if (count($villageInfo) <= 0) {
            return true;
        }
        $filteredVillage = array_filter($villageInfo, function ($item) {
            return !empty($item['close_order_time']) && (float)$item['close_order_time'] >= 1;
        });

        $filteredVillage = array_values($filteredVillage);
        $up_order_id_list = [];
        foreach ($filteredVillage as $key => $val) {
            if (!$val['order_id']) {
                continue;
            }
            $current_time = time();
            $overtime = ($val['close_order_time'] * 24 * 3600) + $val['timely_time'];
            if ($current_time >= $overtime) {
                array_push($up_order_id_list, $val['order_id']);
            }
        }
        if (count($up_order_id_list) > 0) {
            $where2[] = array('order_id', 'in', $up_order_id_list);
            $houseNewRepairWorksOrder->updateStatus(['event_status' => 60], $where2);
        }
    }

    /**
     * 工单自动评价 工单
     * 处理每个小区的 工单自动评价
     **/
    public function worksOrderAutoEvaluate($param = array())
    {
        if (empty($param) || empty($param['info_id']) && empty($param['village_id'])) {
            return true;
        }
        fdump_api(['param' => $param], '00worksOrderAutoEvaluate', 1);
        $whereArr = array('info_id' => $param['info_id'], 'village_id' => $param['village_id']);
        $houseVillageInfo = new HouseVillageInfo();
        $field = 'village_id,property_id,is_auto_work_order_evaluate,work_order_auto_evaluate_time,work_order_extra';
        $villageInfoObj = $houseVillageInfo->getOne($whereArr, $field);
        if ($villageInfoObj && !$villageInfoObj->isEmpty()) {
            $villageInfo = $villageInfoObj->toArray();
            fdump_api(['villageInfo' => $villageInfo], '00worksOrderAutoEvaluate', 1);
            $nowtime = time();
            $work_order_extra = $villageInfo['work_order_extra'] ? json_decode($villageInfo['work_order_extra'], 1) : '';
            $auto_evaluate = array();
            if ($work_order_extra && isset($work_order_extra['auto_evaluate']) && !empty($work_order_extra['auto_evaluate'])) {
                $auto_evaluate = $work_order_extra['auto_evaluate'];
            }
            if ($auto_evaluate && $villageInfo['work_order_auto_evaluate_time'] > 0) {
                $query_time = 0;
                $stime_type = $auto_evaluate['stime_type'];  //hour 小时 day 天
                $evaluate = $auto_evaluate['star'];
                if ($stime_type == 'hour') {
                    $query_time = $nowtime - $villageInfo['work_order_auto_evaluate_time'] * 3600;
                } elseif ($stime_type == 'day') {
                    $query_time = $nowtime - ($villageInfo['work_order_auto_evaluate_time'] * 24 * 3600);
                }
                if ($query_time < 1) {
                    return true;
                }
                $whereArr = array();
                $whereArr[] = array('village_id', '=', $param['village_id']);
                $whereArr[] = array('event_status', '>=', 40);
                $whereArr[] = array('event_status', '<', 50);
                $whereArr[] = array('update_time', '<', $query_time);
                $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
                $field = 'order_id,village_id,property_id,uid,bind_id,phone,name,worker_id,event_status,project_id,rule_id';
                $works_order_obj = $db_house_new_repair_works_order->getAllList($whereArr, $field);
                if ($works_order_obj && !$works_order_obj->isEmpty()) {
                    $works_orders = $works_order_obj->toArray();
                    foreach ($works_orders as $ovv) {
                        $updateArr = [
                            'event_status' => 70,
                            'update_time' => $nowtime,
                            'evaluate' => $evaluate,
                        ];
                        $db_house_new_repair_works_order->saveOne(['order_id' => $ovv['order_id']], $updateArr);
                        $id = $this->addRepairLog([
                            'order_id' => $ovv['order_id'],
                            'log_name' => 'evaluate',
                            'operator_type' => 10,
                            'log_content' => '系统自动评价',
                            'log_imgs' => '',
                            'add_time' => $nowtime,
                            'log_operator' => '系统',
                            'log_phone' => '',
                            'operator_id' => 0,
                            'log_uid' => 0,
                            'evaluate' => $evaluate,
                            'rule_id' => $ovv['rule_id'],
                            'project_id' => $ovv['project_id'],
                            'charge_price' => 0,
                            'pay_type' => 0,
                            'offline_pay_type' => 0,
                            'village_id' => $ovv['village_id'],
                            'property_id' => $ovv['property_id'],

                        ]);
                        //发模板消息
                        $this->newRepairSendMsg(20, $ovv['order_id'], 0, $ovv['property_id']);
                    }
                }
            }
        }
    }
}
