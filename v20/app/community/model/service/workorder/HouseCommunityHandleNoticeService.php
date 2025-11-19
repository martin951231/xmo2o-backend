<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      工单推送督查是相关
 */

namespace app\community\model\service\workorder;

use app\common\model\db\Admin;
use app\common\model\service\config\ConfigCustomizationService;
use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\HouseNewRepairCate;
use app\community\model\db\HouseNewRepairWorksOrder;
use app\community\model\db\HouseNewRepairWorksOrderLog;
use app\community\model\db\HouseVillage;
use app\community\model\db\workorder\HouseCommunityQualityBindRepairCate;
use app\community\model\db\workorder\HouseCommunitySupervisionConfig;
use app\community\model\db\workorder\HouseCommunityWorkOrderConfig;
use app\community\model\db\workorder\HouseCommunityWorkOrderNotice;
use app\community\model\service\HouseNewRepairService;
use app\community\model\service\HouseVillageService;
use app\consts\WorkOrderConst;
use app\traits\workorder\WorkOrderToCenterHandleTraits;
use token\Token;

class HouseCommunityHandleNoticeService
{
    use WorkOrderToCenterHandleTraits;

    /**
     * 上报类型.
     */
    public function getMarkTypeText($mark_type)
    {
        $markTypeArr = [
            'public' => '公共区域上报', 'rejectOver' => '拒绝过多上报', 'evaluateLow' => '评价过低上报',
            'notGrab' => '超时未接单上报', 'overNotProcessed' => '超时处理工单上报',
        ];
        return isset($markTypeArr[$mark_type]) ? $markTypeArr[$mark_type] : '';
    }
    
    public function getMarkTypeList()
    {
        return [
            ['label' => '公共区域上报', 'value' => 'public'], ['label' => '拒绝过多上报', 'value' => 'rejectOver'],
            ['label' => '评价过低上报', 'value' => 'evaluateLow'], ['label' => '超时未接单上报', 'value' => 'notGrab'],
            ['label' => '超时处理工单上报', 'value' => 'overNotProcessed'],
        ];
    }
    
    public function tryCatchHandleRecordNoticeBox($params) {
        try {
            return $this->handleRecordNotice($params);
        } catch (\Exception $e) {
            fdump_api(['err' => '异常捕捉盒子报错-处理需要定时通知或处理事件', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryCatchHandleRecordNoticeBoxLog', 1);
            return false;
        } catch (\Throwable $e) {
            fdump_api(['err' => '异常捕捉盒子报错-处理需要定时通知或处理事件', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryCatchHandleRecordNoticeBoxLog', 1);
            return false;
        } catch (\RuntimeException $e) {
            fdump_api(['err' => '异常捕捉盒子报错-处理需要定时通知或处理事件', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryCatchHandleRecordNoticeBoxLog', 1);
            return false;
        } catch (\InvalidArgumentException $e) {
            fdump_api(['err' => '异常捕捉盒子报错-处理需要定时通知或处理事件', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryCatchHandleRecordNoticeBoxLog', 1);
            return false;
        }
    }

    /**
     * 处理需要定时通知或者处理的事件.
     */
    protected function handleRecordNotice($params) {
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $works_order_id = isset($params['works_order_id']) ? intval($params['works_order_id']) : 0;
        $notice_type = isset($params['notice_type']) ? trim($params['notice_type']) : '';
        if (!$works_order_id || !$notice_type) {
            return false;
        }
        $nowTime = time();
        $notice_time = isset($params['notice_time']) ? intval($params['notice_time']) : $nowTime;
        $notice_content = isset($params['notice_content']) ? $params['notice_content'] : '';
        if ($notice_content && !is_array($notice_content)) {
            $notice_content = json_encode($notice_content,JSON_UNESCAPED_UNICODE);
        }
        $is_job = isset($params['is_job']) ? intval($params['is_job']) : 0;
        
        $spaceTime = $notice_time - $nowTime;
        if ($spaceTime < 0) {
            $spaceTime = 0;
        }
        
        $noticeData = [];
        $village_id && $noticeData['village_id'] = $village_id;
        $noticeData['works_order_id'] = $works_order_id;
        $noticeData['notice_type'] = $notice_type;
        $noticeData['notice_time'] = $notice_time;
        $noticeData['notice_status'] = 0;
        $notice_content && $noticeData['notice_content'] = $notice_content;
        $noticeData['add_time'] = $nowTime;
        
        $dbHouseCommunityWorkOrderNotice = new HouseCommunityWorkOrderNotice();
        $work_order_notice_id = $dbHouseCommunityWorkOrderNotice->add($noticeData);
        if (($is_job || $spaceTime > 0) && $work_order_notice_id) {
            // 走队列且间隔时间大于0 否则直接进行操作或通知
            $queueData = $params;
            $queueData['notice_type'] = $notice_type;
            $queueData['notice_time'] = $notice_time;
            $queueData['work_order_notice_id'] = $work_order_notice_id;
            $queueData['toJobNowTime'] = $nowTime;
            $doJobId = $this->traitCommonWorkOrderToCenterHandle($queueData, $spaceTime);
            $update = ['doJobId' => $doJobId];
            $dbHouseCommunityWorkOrderNotice->updateThis(['id' => $work_order_notice_id], $update);
        } elseif ($work_order_notice_id) {
            // 直接操作
            $queueData = $params;
            $queueData['notice_type'] = $notice_type;
            $queueData['notice_time'] = $notice_time;
            $queueData['work_order_notice_id'] = $work_order_notice_id;
            $queueData['toJobNowTime'] = $nowTime;
            $this->jobHandle($queueData);
        } else {
            fdump_api(['err' => '添加记录失败', 'params' => $params, 'noticeData' => $noticeData], 'jzOpen/handleRecordNoticeLog', 1);
            return false;
        }
    }

    public function tryCatchClearHandleRecordNoticeBox($params) {
        try {
            return $this->clearHandleRecordNotice($params);
        } catch (\Exception $e) {
            fdump_api(['err' => '异常捕捉盒子报错-清除通知或者即将进行的操作', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryCatchClearHandleRecordNoticeBoxLog', 1);
            return false;
        } catch (\Throwable $e) {
            fdump_api(['err' => '异常捕捉盒子报错-清除通知或者即将进行的操作', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryCatchClearHandleRecordNoticeBoxLog', 1);
            return false;
        } catch (\RuntimeException $e) {
            fdump_api(['err' => '异常捕捉盒子报错-清除通知或者即将进行的操作', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryCatchClearHandleRecordNoticeBoxLog', 1);
            return false;
        } catch (\InvalidArgumentException $e) {
            fdump_api(['err' => '异常捕捉盒子报错-清除通知或者即将进行的操作', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryCatchClearHandleRecordNoticeBoxLog', 1);
            return false;
        }
    }
    
    /**
     * 清除对应工单未进行的定时通知或者定时操作.
     */
    protected function clearHandleRecordNotice($params) {
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $works_order_id = isset($params['works_order_id']) ? intval($params['works_order_id']) : 0;
        $notice_type = isset($params['notice_type']) ? trim($params['notice_type']) : '';
        if (!$works_order_id || !$notice_type) {
            return false;
        }
        $dbHouseCommunityWorkOrderNotice = new HouseCommunityWorkOrderNotice();
        $where = [];
        $village_id && $where[] = ['village_id', '=', $village_id];
        $where[] = ['works_order_id', '=', $works_order_id];
        $where[] = ['notice_type', '=', $notice_type];
        $where[] = ['notice_status', '=', 0];
        $noticeHandle = $dbHouseCommunityWorkOrderNotice->getOne($where, 'id');
        if (isset($noticeHandle['id']) && $noticeHandle['id']) {
            $dbHouseCommunityWorkOrderNotice->updateThis(['id' => $noticeHandle['id']], ['notice_status' => 4]);
        }
        return true;
    }
    
    public function jobHandle($params) {
        $notice_type = isset($params['notice_type']) ? trim($params['notice_type']) : '';
        switch ($notice_type) {
            case WorkOrderConst::WORK_ORDER_NOT_GRAB_NOTICE:
                $this->tryCatchHandleToNoticeHouseAdminBox($params);
                break;
            case WorkOrderConst::WORK_ORDER_AUTO_END:
                $this->tryCatchHandleToAutoEndWorkOrderBox($params);
                break;
            case WorkOrderConst::WORK_ORDER_NOT_GRAB_REPORT:
                $this->tryHandleNotGrabToReportBox($params);
                break;
            case WorkOrderConst::WORK_ORDER_OVER_NOT_PROCESSED_REPORT:
                $this->tryHandleOverNotProcessedToReportBox($params);
                break;
            case WorkOrderConst::WORK_ORDER_OVER_FEE_CONFIRM_OPERATE:
                $this->tryHandleOverFeeConfirmBox($params);
                break;
        }
        return true;
    }

    /**
     * 无人结单推送管理员盒子.
     */
    public function tryCatchHandleToNoticeHouseAdminBox($params) {
        try {
            return $this->handleToNoticeHouseAdmin($params);
        } catch (\Exception $e) {
            fdump_api(['err' => '异常捕捉盒子报错-执行通知小区管理员', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryCatchHandleToNoticeHouseAdminBoxLog', 1);
            return false;
        } catch (\Throwable $e) {
            fdump_api(['err' => '异常捕捉盒子报错-执行通知小区管理员', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryCatchHandleToNoticeHouseAdminBoxLog', 1);
            return false;
        } catch (\RuntimeException $e) {
            fdump_api(['err' => '异常捕捉盒子报错-执行通知小区管理员', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryCatchHandleToNoticeHouseAdminBoxLog', 1);
            return false;
        } catch (\InvalidArgumentException $e) {
            fdump_api(['err' => '异常捕捉盒子报错-执行通知小区管理员', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryCatchHandleToNoticeHouseAdminBoxLog', 1);
            return false;
        }
    }

    /**
     * 无人接单推送消息给设置的管理员.
     */
    protected function handleToNoticeHouseAdmin($params) {
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $works_order_id = isset($params['works_order_id']) ? intval($params['works_order_id']) : 0;
        $notice_type = isset($params['notice_type']) ? trim($params['notice_type']) : '';
        $doJobId = isset($params['doJobId']) ? trim($params['doJobId']) : '';
        $do_notice_time = isset($params['do_notice_time']) ? intval($params['do_notice_time']) : 0;
        $dbHouseCommunityWorkOrderNotice = new HouseCommunityWorkOrderNotice();
        $whereNotice = [];
        $village_id && $whereNotice[] = ['village_id', '=', $village_id];
        if ($works_order_id && $notice_type) {
            $whereNotice[] = ['works_order_id', '=', $works_order_id];
            $whereNotice[] = ['notice_type', '=', $notice_type];
        } else {
            $whereNotice[] = ['doJobId', '=', $doJobId];
        }
        if ($do_notice_time > 0) {
            $whereNotice[] = ['notice_time', '<=', $do_notice_time];
            $order = "notice_time DESC";
        } else {
            $order = "notice_time ASC";
        }
        $noticeInfo = $dbHouseCommunityWorkOrderNotice->getOne($whereNotice, true, $order);
        if ($noticeInfo && !is_array($noticeInfo)) {
            $noticeInfo = $noticeInfo->toArray();
        }
        if (empty($noticeInfo)) {
            fdump_api(['err' => '通知信息已经不存在了', 'params' => $params], 'jzOpen/handleToNoticeHouseAdminLog', 1);
            return true;
        }
        if (isset($noticeInfo['notice_status']) && intval($noticeInfo['notice_status']) != 0) {
            fdump_api(['err' => '通知已经执行或者关闭了', 'params' => $params, 'noticeInfo' => $noticeInfo], 'jzOpen/handleToNoticeHouseAdminLog', 1);
            return true;
        }
        $dbHouseNewRepairWorksOrder = new HouseNewRepairWorksOrder();
        $orderInfo = $dbHouseNewRepairWorksOrder->getOne(['order_id' => $works_order_id]);
        if ($orderInfo && !is_array($orderInfo)) {
            $orderInfo = $orderInfo->toArray();
        }
        $noticeId = isset($noticeInfo['id']) ? intval($noticeInfo['id']) : 0;
        if (empty($orderInfo)) {
            fdump_api(['err' => '对应工单不存在', 'params' => $params], 'jzOpen/handleToNoticeHouseAdminLog', 1);
            $update = ['notice_status' => 2, 'notice_reason' => '对应工单不存在了'];
            $dbHouseCommunityWorkOrderNotice->updateThis(['id' => $noticeId], $update);
            return true;
        }

        $now_handle_village_id = isset($orderInfo['now_handle_village_id']) ? intval($orderInfo['now_handle_village_id']) : 0;
        // 检查下订单是否在当前小区
        $isOtherVillage = 0;
        if ($now_handle_village_id > 0 && $now_handle_village_id != $village_id) {
            $isOtherVillage = 1;
        }
        
        $dbHouseCommunityWorkOrderConfig = new HouseCommunityWorkOrderConfig();
        $whereConfig = [];
        !$village_id && $village_id = isset($noticeInfo['village_id']) ? intval($noticeInfo['village_id']) : 0;
        !$village_id && $village_id = isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : 0;
        !$works_order_id && $works_order_id = isset($noticeInfo['works_order_id']) ? intval($noticeInfo['works_order_id']) : 0;
        $whereConfig['village_id'] = $village_id;
        $whereConfig['works_order_id'] = $works_order_id;
        $configInfo = $dbHouseCommunityWorkOrderConfig->getOne($whereConfig, 'id, works_order_id, village_id, not_receive_interval, notice_work_id, notice_time, notice_text, now_community_config');
        if ($configInfo && !is_array($configInfo)) {
            $configInfo = $configInfo->toArray();
        }
        if ($isOtherVillage == 1) {
            $now_community_config = isset($configInfo['now_community_config']) ? $configInfo['now_community_config'] : [];
            $now_community_config && $now_community_config = json_decode($now_community_config, 1);
            $notice_work_id = isset($now_community_config['work_id']) ? intval($now_community_config['work_id']) : 0;
        } else {
            $now_community_config = [];
            $notice_work_id = isset($configInfo['notice_work_id']) ? intval($configInfo['notice_work_id']) : 0;
        }
        $notice_time = isset($configInfo['notice_time']) ? intval($configInfo['notice_time']) : 0;
        if (!$notice_work_id) {
            fdump_api(['err' => '通知对象不存在了', 'params' => $params, 'isOtherVillage' => $isOtherVillage, 'now_community_config' => isset($now_community_config) ? $now_community_config : [], 'configInfo' => $configInfo], 'jzOpen/handleToNoticeHouseAdminLog', 1);
            return true;
        }
        if (!$notice_time) {
            fdump_api(['err' => '通知时间置空了-不进行推送', 'params' => $params, 'isOtherVillage' => $isOtherVillage, 'now_community_config' => isset($now_community_config) ? $now_community_config : [], 'configInfo' => $configInfo], 'jzOpen/handleToNoticeHouseAdminLog', 1);
            $update = ['notice_status' => 2, 'notice_reason' => '通知时间置空了（不进行推送）'];
            $dbHouseCommunityWorkOrderNotice->updateThis(['id' => $noticeId], $update);
            return true;
        }
        $nowTime = time();
        if ($notice_time > $nowTime + 600) {
            // 实际通知时间在1分钟以后 拦截
            fdump_api(['err' => '通知时间延后执行了-不进行推送', 'params' => $params, 'isOtherVillage' => $isOtherVillage, 'now_community_config' => isset($now_community_config) ? $now_community_config : [], 'configInfo' => $configInfo], 'jzOpen/handleToNoticeHouseAdminLog', 1);
            $update = ['notice_status' => 2, 'notice_reason' => '通知时间延后执行了（不进行推送）'];
            $dbHouseCommunityWorkOrderNotice->updateThis(['id' => $noticeId], $update);
            return true;
        }
        $sHouseNewRepairService = new HouseNewRepairService();
        $worker = $sHouseNewRepairService->getWorker($notice_work_id);
        if (!isset($worker['openid']) || !$worker['openid']) {
            fdump_api(['err' => '通知对象没有绑定微信', 'params' => $params, 'worker' => $worker], 'jzOpen/handleToNoticeHouseAdminLog', 1);
            $step = ['title' => '未接单消息发送对象未绑定微信', 'now_community_config' => $now_community_config, 'content' => '未接单消息发送对象未绑定微信，无法发送','type' => 'text', 'time' => $nowTime, 'worker_id' => $notice_work_id];
            (new HouseCommunityWorkOrderService())->receiveWorkOrderSetConfig($works_order_id, $step, $village_id);
            return true;
        }
        $dbHouseNewRepairCate = new HouseNewRepairCate();
        $catFid = isset($orderInfo['cat_fid']) ? intval($orderInfo['cat_fid']) : 0;
        $catFidInfo = $dbHouseNewRepairCate->getOne(['id' => $catFid], 'cate_name');
        $catId = isset($orderInfo['cat_id']) ? intval($orderInfo['cat_id']) : 0;
        $catInfo = $dbHouseNewRepairCate->getOne(['id' => $catId], 'cate_name');
        $catFidCateName = isset($catFidInfo['cate_name']) ? trim($catFidInfo['cate_name']) : '';
        $catCateName = isset($catInfo['cate_name']) ? trim($catInfo['cate_name']) : '';

        $villageInfo = (new HouseVillage())->getOne($village_id, 'village_name');
        $village_name = isset($villageInfo['village_name']) ? trim($villageInfo['village_name']) : '';
        
        // 项目名称：thing22 工单类型：thing3 工单状态：thing10 处理时间：time11 工单地址：thing20 处理原因：thing14
        // thing 长度限制20
        $thing22 = $village_name;
        $thing3 = $catFidCateName . '/' . $catCateName;
        if (mb_strlen($thing3) > 15) {
            $thing3 = msubstr($thing3, 0, 15);
        }
        $thing10 = isset($orderInfo['event_status']) ? $sHouseNewRepairService->order_work_status_txt[$orderInfo['event_status']] : '无';
        $update_time = isset($orderInfo['update_time']) ? intval($orderInfo['update_time']) : 0;
        !$update_time && $update_time = isset($orderInfo['add_time']) ? intval($orderInfo['add_time']) : $nowTime;
        $time11 = date('H:i', $update_time);
        $single_id = isset($orderInfo['single_id']) ? intval($orderInfo['single_id']) : 0;
        $floor_id = isset($orderInfo['floor_id']) ? intval($orderInfo['floor_id']) : 0;
        $layer_id = isset($orderInfo['layer_id']) ? intval($orderInfo['layer_id']) : 0;
        $room_id = isset($orderInfo['room_id']) ? intval($orderInfo['room_id']) : 0;
        $village_id = isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : 0;
        if ($single_id > 0 && $floor_id > 0 && $layer_id > 0 && $room_id > 0 && $village_id > 0) {
            $service_house_village = new HouseVillageService();
            $address = $service_house_village->getSingleFloorRoom($single_id, $floor_id, $layer_id, $room_id, $village_id);
            $address = $address ? $address : '无';
        } else {
            $address = $orderInfo['address_txt'] ?: '无';
        }
        if ($isOtherVillage == 1) {
            $villageInfo = (new HouseVillage())->getOne($now_handle_village_id, 'village_id, village_name, property_id');
            $villageAddress = isset($villageInfo['village_name']) ? trim($villageInfo['village_name']) . ' ' : '';
            $address = $villageAddress . $address;
            $property_id = isset($villageInfo['property_id']) ? intval($villageInfo['property_id']) : 0;
        } else {
            $villageInfo = (new HouseVillage())->getOne($village_id, 'village_id, property_id');
            $property_id = isset($villageInfo['property_id']) ? intval($villageInfo['property_id']) : 0;
        }
        $thing20 = $address;
        $thing14 = '当前工单无人接单';
        $href = cfg('site_url') . '/packapp/community/pages/CommunityPages/workOrder/eventdetails?order_id=' . $works_order_id . '&gotoIndex=tem_msg';
        
        $ticket = Token::createToken($worker['wid'], 6);
        $href = $href . '&ticket=' . $ticket . '&village_id=' . $worker['village_id'];
        $msgArr = [
            'href' => $href,
            'wecha_id' => $worker['openid'],
            'first' => '工单无人接单处理',
            'keyword1' => $thing3,
            'keyword2' => '已发送',
            'keyword3' => date('H时i分', $_SERVER['REQUEST_TIME']),
            'remark' => '请点击查看详细信息！',
            'new_info' => [//新版本发送需要的信息
                'tempKey' => '43030',//新模板号
                'thing22' => $thing22,//项目名称
                'thing3' => $thing3,//工单类型
                'thing10' => $thing10,//工单状态
                'time11' => $time11,//处理时间
                'thing20' => $thing20,//工单地址
                'thing14' => $thing14,//处理原因
            ],
        ];
        // 项目名称：thing22 工单类型：thing3 工单状态：thing10 处理时间：time11 工单地址：thing20 处理原因：thing14
        $xtype = 0;
        if ($property_id > 0) {
            $xtype = 1;
        } else {
            $property_id = 0;
        }
        $TemplateNewsService=new TemplateNewsService();
        $TemplateNewsService->sendTempMsg('OPENTM400166399', $msgArr,0,$property_id,$xtype);//todo 类目模板OPENTM400166399

        $step = ['title' => '未接单消息已经发送', 'now_community_config' => $now_community_config, 'content' => '未接单消息已经发送管理员','type' => 'text', 'time' => $nowTime, 'worker_id' => $notice_work_id];
        (new HouseCommunityWorkOrderService())->receiveWorkOrderSetConfig($works_order_id, $step, $village_id);
        return true;
    }
    protected function sendMessageToWork($order_id=0,$xtype='over_not_processed',$orderInfo=array()){
        $dbHouseNewRepairWorksOrder = new HouseNewRepairWorksOrder();
        if((empty($orderInfo) || !isset($orderInfo['order_id'])) && $order_id>0){
            $orderInfoObj = $dbHouseNewRepairWorksOrder->getOne(['order_id' => $order_id]);
            if ($orderInfoObj && !$orderInfoObj->isEmpty()) {
                $orderInfo = $orderInfoObj->toArray();
            }
        }
        fdump_api([__LINE__,'xtype'=>$xtype, 'orderInfo' => $orderInfo, 'order_id' => $order_id], 'jzOpen/000sendMessageToWork', 1);
        if(empty($orderInfo)){
            return true;
        }
        $order_id=$orderInfo['order_id'];
        $nowTime=time();
        if ($xtype=='over_not_processed'){
            //工作人员接单后N分钟后未处理  通知当前工单的处理工作人员
            if(!($orderInfo['event_status']>=20 && $orderInfo['event_status']<30)){
                fdump_api([__LINE__,'err' => '工单状态不对','xtype'=>$xtype, 'orderInfo' => $orderInfo, 'order_id' => $order_id], 'jzOpen/000sendMessageToWork', 1);
                return true;
            }
            $sHouseNewRepairService = new HouseNewRepairService();
            $worker = $sHouseNewRepairService->getWorker($orderInfo['worker_id']);
            if (empty($worker) || !isset($worker['openid']) || empty($worker['openid'])) {
                fdump_api(['err' => '通知对象没有绑定微信', 'xtype'=>$xtype,'orderInfo' => $orderInfo,'worker' => $worker], 'jzOpen/000sendMessageToWork', 1);
                return true;
            }
            $address='';
            $single_id = isset($orderInfo['single_id']) ? intval($orderInfo['single_id']) : 0;
            $floor_id = isset($orderInfo['floor_id']) ? intval($orderInfo['floor_id']) : 0;
            $layer_id = isset($orderInfo['layer_id']) ? intval($orderInfo['layer_id']) : 0;
            $room_id = isset($orderInfo['room_id']) ? intval($orderInfo['room_id']) : 0;
            $village_id = isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : 0;
            if ($single_id > 0 && $floor_id > 0 && $layer_id > 0 && $room_id > 0 && $village_id > 0) {
                $service_house_village = new HouseVillageService();
                $address = $service_house_village->getSingleFloorRoom($single_id, $floor_id, $layer_id, $room_id, $village_id);
                $address = $address ? $address : '';
            } else {
                $address = $orderInfo['address_txt'] ?: '';
            }
            $village_name='';
            $property_id=0;
            if($village_id>0){
                $villageInfo = (new HouseVillage())->getOne($village_id, 'village_id, village_name, property_id');
                $village_name = $villageInfo && isset($villageInfo['village_name']) ? trim($villageInfo['village_name']) : '';
                $property_id= $villageInfo && isset($villageInfo['property_id']) ? trim($villageInfo['property_id']) : 0;
            }
            if($village_name){
                $address = $village_name .' '. $address;
            }
            $dbHouseNewRepairCate = new HouseNewRepairCate();
            $catFid = isset($orderInfo['cat_fid']) ? intval($orderInfo['cat_fid']) : 0;
            $catFidInfo = $dbHouseNewRepairCate->getOne(['id' => $catFid], 'cate_name');
            $catId = isset($orderInfo['cat_id']) ? intval($orderInfo['cat_id']) : 0;
            $catInfo = $dbHouseNewRepairCate->getOne(['id' => $catId], 'cate_name');
            $catFidCateName = isset($catFidInfo['cate_name']) ? trim($catFidInfo['cate_name']) : '';
            $catCateName = isset($catInfo['cate_name']) ? trim($catInfo['cate_name']) : '';
            $thing22 = $village_name ? $village_name:'小区工单';
            $thing3 = $catFidCateName . '/' . $catCateName;
            if (mb_strlen($thing3) > 15) {
                $thing3 = msubstr($thing3, 0, 15);
            }

            $thing20 = $address ? $address:'无';
            $thing14 = "接单后未及时未处理工单";
            $update_time = isset($orderInfo['update_time']) ? intval($orderInfo['update_time']) : 0;
            !$update_time && $update_time = isset($orderInfo['add_time']) ? intval($orderInfo['add_time']) : $nowTime;
            $time11 = date('m月d H:i', $update_time);
            $href = cfg('site_url') . '/packapp/community/pages/CommunityPages/workOrder/eventdetails?order_id=' . $order_id . '&gotoIndex=tem_msg';
            
            $ticket = Token::createToken($worker['wid'], 6);
            $href = $href . '&ticket=' . $ticket . '&village_id=' . $worker['village_id'];
            $msgArr = [
                'href' => $href,
                'wecha_id' => $worker['openid'],
                'first' => $thing14,
                'keyword1' => $thing3,
                'keyword2' => '已发送',
                'keyword3' => date('H时i分'),
                'remark' => '请点击查看详细信息！',
                'new_info' => [//新版本发送需要的信息
                    'tempKey' => '43030',//新模板号
                    'thing22' => $thing22,//项目名称
                    'thing3' => $thing3,//工单类型
                    'thing10' => '工单待处理',//工单状态
                    'time11' => $time11,//处理时间
                    'thing20' => $thing20,//工单地址
                    'thing14' => $thing14,//处理原因
                ],
            ];
            $xtype = 0;
            if ($property_id > 0) {
                $xtype = 1;
            } else {
                $property_id = 0;
            }
            // 项目名称：thing22 工单类型：thing3 工单状态：thing10 处理时间：time11 工单地址：thing20 处理原因：thing14
            $TemplateNewsService=new TemplateNewsService();
            $TemplateNewsService->sendTempMsg('OPENTM400166399', $msgArr, 0, $property_id, $xtype);//todo 类目模板OPENTM400166399

            //工作人员接单后N分钟后未处理  通知设置的工作人员
            $now_handle_village_id = isset($orderInfo['now_handle_village_id']) && $orderInfo['now_handle_village_id'] > 0 ? $orderInfo['now_handle_village_id'] : $village_id;
            $configInfo = (new HouseCommunityAboutService())->getHouseCommunityConfig($now_handle_village_id);
            if (isset($configInfo['over_not_processed_work_id']) && $configInfo['over_not_processed_work_id'] > 0) {
                $worker2 = $sHouseNewRepairService->getWorker($configInfo['over_not_processed_work_id']);
                if (empty($worker2) || !isset($worker2['openid']) || empty($worker2['openid'])) {
                    fdump_api([__LINE__,'err' => '设置的通知对象没有绑定微信', 'xtype' => $xtype, 'now_handle_village_id' => $now_handle_village_id, 'worker' => $worker2], 'jzOpen/000sendMessageToWork', 1);
                    return true;
                }
                $villageInfo = (new HouseVillage())->getOne($now_handle_village_id, 'village_id, village_name, property_id');
                $village_name = $villageInfo && isset($villageInfo['village_name']) ? trim($villageInfo['village_name']) : '';
                $property_id = $villageInfo && isset($villageInfo['property_id']) ? trim($villageInfo['property_id']) : 0;
                $thing22 = $village_name ? $village_name : '小区工单';
                $thing14 = "序号为" . $order_id . '的工单未被工作人员及时处理';
                $msgArr = [
                    'href' => '',
                    'wecha_id' => $worker2['openid'],
                    'first' => $thing14,
                    'keyword1' => $thing3,
                    'keyword2' => '已发送',
                    'keyword3' => date('H时i分'),
                    'remark' => '',
                    'new_info' => [//新版本发送需要的信息
                        'tempKey' => '43030',//新模板号
                        'thing22' => $thing22,//项目名称
                        'thing3' => $thing3,//工单类型
                        'thing10' => '工单未及时处理',//工单状态
                        'time11' => $time11,//处理时间
                        'thing20' => $thing20,//工单地址
                        'thing14' => $thing14,//处理原因
                    ],
                ];
                $xtype = 0;
                if ($property_id > 0) {
                    $xtype = 1;
                } else {
                    $property_id = 0;
                }
                // 项目名称：thing22 工单类型：thing3 工单状态：thing10 处理时间：time11 工单地址：thing20 处理原因：thing14
                $TemplateNewsService = new TemplateNewsService();
                $TemplateNewsService->sendTempMsg('OPENTM400166399', $msgArr, 0, $property_id, $xtype);//todo 类目模板OPENTM400166399
            }
        }
        return true;
    }
    /**
     * 自动进行结单操作.
     */
    public function tryCatchHandleToAutoEndWorkOrderBox($params) {
        try {
            return $this->handleToAutoEndWorkOrder($params);
        } catch (\Exception $e) {
            fdump_api(['err' => '异常捕捉盒子报错-自动进行结单操作', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryCatchHandleToAutoEndWorkOrderBoxLog', 1);
            return false;
        } catch (\Throwable $e) {
            fdump_api(['err' => '异常捕捉盒子报错-自动进行结单操作', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryCatchHandleToAutoEndWorkOrderBoxLog', 1);
            return false;
        } catch (\RuntimeException $e) {
            fdump_api(['err' => '异常捕捉盒子报错-自动进行结单操作', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryCatchHandleToAutoEndWorkOrderBoxLog', 1);
            return false;
        } catch (\InvalidArgumentException $e) {
            fdump_api(['err' => '异常捕捉盒子报错-自动进行结单操作', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryCatchHandleToAutoEndWorkOrderBoxLog', 1);
            return false;
        }
    }
    
    protected function handleToAutoEndWorkOrder($params) {
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $works_order_id = isset($params['works_order_id']) ? intval($params['works_order_id']) : 0;
        $notice_type = isset($params['notice_type']) ? trim($params['notice_type']) : '';
        $doJobId = isset($params['doJobId']) ? trim($params['doJobId']) : '';
        $do_notice_time = isset($params['do_notice_time']) ? intval($params['do_notice_time']) : 0;
        $dbHouseCommunityWorkOrderNotice = new HouseCommunityWorkOrderNotice();
        $whereNotice = [];
        $village_id && $whereNotice[] = ['village_id', '=', $village_id];
        if ($works_order_id && $notice_type) {
            $whereNotice[] = ['works_order_id', '=', $works_order_id];
            $whereNotice[] = ['notice_type', '=', $notice_type];
        } else {
            $whereNotice[] = ['doJobId', '=', $doJobId];
        }
        if ($do_notice_time > 0) {
            $whereNotice[] = ['notice_time', '<=', $do_notice_time];
            $order = "notice_time DESC";
        } else {
            $order = "notice_time ASC";
        }
        $noticeInfo = $dbHouseCommunityWorkOrderNotice->getOne($whereNotice, true, $order);
        if ($noticeInfo && !is_array($noticeInfo)) {
            $noticeInfo = $noticeInfo->toArray();
        }
        if (empty($noticeInfo)) {
            fdump_api(['err' => '自动结单操作已经不存在了', 'params' => $params], 'jzOpen/handleToAutoEndWorkOrderLog', 1);
            return true;
        }
        if (isset($noticeInfo['notice_status']) && intval($noticeInfo['notice_status']) != 0) {
            fdump_api(['err' => '自动结单操作已经执行或者关闭了', 'params' => $params, 'noticeInfo' => $noticeInfo], 'jzOpen/handleToAutoEndWorkOrderLog', 1);
            return true;
        }
        $dbHouseNewRepairWorksOrder = new HouseNewRepairWorksOrder();
        $orderInfo = $dbHouseNewRepairWorksOrder->getOne(['order_id' => $works_order_id]);
        if ($orderInfo && !is_array($orderInfo)) {
            $orderInfo = $orderInfo->toArray();
        }
        $noticeId = isset($noticeInfo['id']) ? intval($noticeInfo['id']) : 0;
        if (empty($orderInfo)) {
            fdump_api(['err' => '对应工单不存在', 'params' => $params], 'jzOpen/handleToNoticeHouseAdminLog', 1);
            $update = ['notice_status' => 2, 'notice_reason' => '对应工单不存在了'];
            $dbHouseCommunityWorkOrderNotice->updateThis(['id' => $noticeId], $update);
            return true;
        }
        if (intval($orderInfo['event_status']) >= 40) {
            fdump_api(['err' => '对应工单已经办结过', 'params' => $params], 'jzOpen/handleToNoticeHouseAdminLog', 1);
            $update = ['notice_status' => 2, 'notice_reason' => '对应工单不存在了'];
            $dbHouseCommunityWorkOrderNotice->updateThis(['id' => $noticeId], $update);
        }
        $dbHouseCommunityWorkOrderConfig = new HouseCommunityWorkOrderConfig();
        $whereConfig = [];
        !$village_id && $village_id = isset($noticeInfo['village_id']) ? intval($noticeInfo['village_id']) : 0;
        !$village_id && $village_id = isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : 0;
        !$works_order_id && $works_order_id = isset($noticeInfo['works_order_id']) ? intval($noticeInfo['works_order_id']) : 0;
        $whereConfig['village_id'] = $village_id;
        $whereConfig['works_order_id'] = $works_order_id;
        $configInfo = $dbHouseCommunityWorkOrderConfig->getOne($whereConfig, 'id, works_order_id, village_id, not_receive_interval, notice_work_id, notice_time, notice_text, now_community_config');
        if ($configInfo && !is_array($configInfo)) {
            $configInfo = $configInfo->toArray();
        }
        $notice_time = isset($configInfo['notice_time']) ? intval($configInfo['notice_time']) : 0;
        if (!$notice_time) {
            fdump_api(['err' => '自动结单操作时间置空了-不进行推送', 'params' => $params, 'configInfo' => $configInfo], 'jzOpen/handleToNoticeHouseAdminLog', 1);
            $update = ['notice_status' => 2, 'notice_reason' => '自动结单操作时间置空了（不进行推送）'];
            $dbHouseCommunityWorkOrderNotice->updateThis(['id' => $noticeId], $update);
            return true;
        }
        $nowTime = time();
        if ($notice_time > $nowTime + 600) {
            // 实际通知时间在1分钟以后 拦截
            fdump_api(['err' => '自动结单操作时间延后执行了-不进行推送', 'params' => $params, 'configInfo' => $configInfo], 'jzOpen/handleToNoticeHouseAdminLog', 1);
            $update = ['notice_status' => 2, 'notice_reason' => '自动结单操作时间延后执行了（不进行推送）'];
            $dbHouseCommunityWorkOrderNotice->updateThis(['id' => $noticeId], $update);
            return true;
        }
        // 进行自动结单操作  支付默认走线上
        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        if ($jz_open) {
            $single_id = isset($orderInfo['single_id']) && intval($orderInfo['single_id']) > 0 ? intval($orderInfo['single_id']) : 0;
            $room_id = isset($orderInfo['room_id']) && intval($orderInfo['room_id']) > 0 ? intval($orderInfo['room_id']) : 0;
            $judgeParams = [
                'village_id' => $village_id, 'repair_cate_id' => $orderInfo['cat_id'],
                'jz_open' => $jz_open, 'single_id' => $single_id, 'room_id' => $room_id,
            ];
            $hasFee = (new HouseCommunityQualityService())->judgeRepairCateQualityFee($judgeParams);
        } else {
            $hasFee = false;
        }
        $db_house_new_repair_subject = new HouseNewRepairCate();
        $subject_info = $db_house_new_repair_subject->getOne(['id' => $orderInfo['cat_id']]);
        $change_reason = '后台时间截止自动进行结单';
        $charge_price = 0;
        $event_status = 40;
        if (isset($subject_info['charge_type']) && $subject_info['charge_type'] == 1 && !$hasFee) {
            if (empty($charge_price)) {
                $charge_price = $orderInfo['charge_price'];
            }
            $pay_type = 1;
            $event_status = 44;
            $order_set = [
                'now_role' => 1,
                'event_status' => $event_status,
                'charge_price' => $charge_price,
                'pay_type' => $pay_type,
                'offline_pay_type' => 0,
                'change_reason' => $change_reason,
            ];
        } else {
            $order_set = [
                'now_role' => 1,
                'event_status' => 40,
                'change_reason' => $change_reason,
            ];
        }
        $sHouseNewRepairService = new HouseNewRepairService();
        $rr = $sHouseNewRepairService->getTimelyStatus($orderInfo);
        if ($rr['error']) {
            $order_set = array_merge($order_set, $rr['data']);
        }
        $order_id = $orderInfo['order_id'];
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $db_house_new_repair_works_order_log = new HouseNewRepairWorksOrderLog();
        $order_set['update_time'] = time();
        $where_update = [];
        $where_update[] = ['order_id', '=', $order_id];
        $order_update = $db_house_new_repair_works_order->saveOne($where_update, $order_set);
        if ($order_update) {
            $log_name = 'work_completed';
            $log_data = [
                'order_id' => $order_id,
                'log_name' => $log_name,
                'village_id' => isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : '',
                'property_id' => isset($orderInfo['property_id']) ? intval($orderInfo['property_id']) : '',
                'log_operator' => '系统自动结单',
                'log_phone' => '',
                'operator_type' => 30,
                'operator_id' => 0,
                'log_uid' => '',
                'log_content' => '后台设置自动结单时间截止进行自动进行结单',
                'log_imgs' => '',
                'evaluate' => 0,
                'add_time' => time(),
            ];
            $where_count = [];
            $where_count[] = ['order_id', '=', $order_id];
            $where_count[] = ['log_name', '=', $log_name];
            $log_num = $db_house_new_repair_works_order_log->getCount($where_count);
            if (!$log_num) {
                $log_num = 1;
            } else {
                $log_num = intval($log_num) + 1;
            }
            if ('work_completed' == $log_name && $subject_info['charge_type'] == 1 && !$hasFee && in_array($order_set['event_status'], [44, 45]) && $log_num == 1) {
                $sHouseNewRepairService->addPayOrder($order_id);
            }
            $log_data['log_num'] = $log_num;
            $log_data['project_id'] = $orderInfo['project_id'];
            $log_data['rule_id'] = $orderInfo['rule_id'];
            $log_data['charge_price'] = $charge_price;
            $log_data['pay_type'] = $pay_type;
            $log_data['change_reason'] = $change_reason;
            $log_id = $sHouseNewRepairService->addRepairLog($log_data);
            $property_id = isset($orderInfo['property_id']) ? intval($orderInfo['property_id']) : 0;
            if ($log_name == 'work_completed'){
                //todo 工作人员结单
                if($orderInfo['order_type'] == 0){
                    //todo 用户提交的工单
                    if($orderInfo['event_status'] >= 40){
                        $sHouseNewRepairService->newRepairSendMsg(32,$order_id,0,$property_id);
                    }else{
                        $sHouseNewRepairService->newRepairSendMsg(30,$order_id,0,$property_id);
                    }
                }elseif ($orderInfo['order_type'] == 4){
                    //todo 工作人员提交的工单
                    if($orderInfo['event_status'] >= 40){
                        $sHouseNewRepairService->newRepairSendMsg(33,$order_id,$orderInfo['uid'],$property_id);
                    }else{
                        $sHouseNewRepairService->newRepairSendMsg(31,$order_id,$orderInfo['uid'],$property_id);
                    }
                }
                $jz_open_title = '处理人员结单(系统自动结单)';
                $jz_open_content = '处理人员结单(系统自动结单)，去除自动结单时间';
                $stepArr = [
                    'title' => $jz_open_title,
                    'type' => 'text',
                    'content' => $jz_open_content,
                    'time' => time(),
                    'work_id' => $orderInfo['worker_id']
                ];
                (new HouseCommunityWorkOrderService())->clearAutoEndTime($order_id, $stepArr);
            }
        }
        return true;
    }

    /**
     * 拒绝次数超了上报box.
     */
    public function tryHandleRejectOverToReportBox($params) {
        try {
            return $this->handleRejectOverToReport($params);
        } catch (\Exception $e) {
            fdump_api(['err' => '异常捕捉盒子报错-拒绝次数超了上报', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandleRejectOverToReportBoxLog', 1);
            return false;
        } catch (\Throwable $e) {
            fdump_api(['err' => '异常捕捉盒子报错-拒绝次数超了上报', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandleRejectOverToReportBoxLog', 1);
            return false;
        } catch (\RuntimeException $e) {
            fdump_api(['err' => '异常捕捉盒子报错-拒绝次数超了上报', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandleRejectOverToReportBoxLog', 1);
            return false;
        } catch (\InvalidArgumentException $e) {
            fdump_api(['err' => '异常捕捉盒子报错-拒绝次数超了上报', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandleRejectOverToReportBoxLog', 1);
            return false;
        }
    }
    
    /**
     * 低分评价了工单box.
     */
    public function tryHandleEvaluateLowToReportBox($params) {
        try {
            return $this->handleEvaluateLowToReport($params);
        } catch (\Exception $e) {
            fdump_api(['err' => '异常捕捉盒子报错-低分评价了工单', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandleEvaluateLowToReportBoxLog', 1);
            return false;
        } catch (\Throwable $e) {
            fdump_api(['err' => '异常捕捉盒子报错-低分评价了工单', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandleEvaluateLowToReportBoxLog', 1);
            return false;
        } catch (\RuntimeException $e) {
            fdump_api(['err' => '异常捕捉盒子报错-低分评价了工单', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandleEvaluateLowToReportBoxLog', 1);
            return false;
        } catch (\InvalidArgumentException $e) {
            fdump_api(['err' => '异常捕捉盒子报错-低分评价了工单', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandleEvaluateLowToReportBoxLog', 1);
            return false;
        }
    }

    /**
     * 不接单上报督查室box.
     */
    public function tryHandleNotGrabToReportBox($params) {
        try {
            return $this->handleNotGrabToReport($params);
        } catch (\Exception $e) {
            fdump_api(['err' => '异常捕捉盒子报错-不接单上报督查室', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandleNotGrabToReportBoxLog', 1);
            return false;
        } catch (\Throwable $e) {
            fdump_api(['err' => '异常捕捉盒子报错-不接单上报督查室', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandleNotGrabToReportBoxLog', 1);
            return false;
        } catch (\RuntimeException $e) {
            fdump_api(['err' => '异常捕捉盒子报错-不接单上报督查室', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandleNotGrabToReportBoxLog', 1);
            return false;
        } catch (\InvalidArgumentException $e) {
            fdump_api(['err' => '异常捕捉盒子报错-不接单上报督查室', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandleNotGrabToReportBoxLog', 1);
            return false;
        }
    }


    /**
     * 接单不处理上报督查室box.
     */
    public function tryHandleOverNotProcessedToReportBox($params) {
        try {
            return $this->handleOverNotProcessedToReport($params);
        } catch (\Exception $e) {
            fdump_api(['err' => '异常捕捉盒子报错-接单不处理上报督查室', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandleOverNotProcessedToReportBoxLog', 1);
            return false;
        } catch (\Throwable $e) {
            fdump_api(['err' => '异常捕捉盒子报错-接单不处理上报督查室', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandleOverNotProcessedToReportBoxLog', 1);
            return false;
        } catch (\RuntimeException $e) {
            fdump_api(['err' => '异常捕捉盒子报错-接单不处理上报督查室', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandleOverNotProcessedToReportBoxLog', 1);
            return false;
        } catch (\InvalidArgumentException $e) {
            fdump_api(['err' => '异常捕捉盒子报错-接单不处理上报督查室', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandleOverNotProcessedToReportBoxLog', 1);
            return false;
        }
    }

    /**
     * 公共区域直接上报box.
     */
    public function tryHandlePublicToReportBox($params) {
        try {
            return $this->handlePublicToReport($params);
        } catch (\Exception $e) {
            fdump_api(['err' => '异常捕捉盒子报错-公共区域直接上报', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandlePublicToReportBoxLog', 1);
            return false;
        } catch (\Throwable $e) {
            fdump_api(['err' => '异常捕捉盒子报错-公共区域直接上报', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandlePublicToReportBoxLog', 1);
            return false;
        } catch (\RuntimeException $e) {
            fdump_api(['err' => '异常捕捉盒子报错-公共区域直接上报', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandlePublicToReportBoxLog', 1);
            return false;
        } catch (\InvalidArgumentException $e) {
            fdump_api(['err' => '异常捕捉盒子报错-公共区域直接上报', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandlePublicToReportBoxLog', 1);
            return false;
        }
    }
    
    /**
     * 公共区域直接上报.
     */
    protected function handlePublicToReport($params) {
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $works_order_id = isset($params['works_order_id']) ? intval($params['works_order_id']) : 0;

        $dbHouseNewRepairWorksOrder = new HouseNewRepairWorksOrder();
        $orderInfo = $dbHouseNewRepairWorksOrder->getOne(['order_id' => $works_order_id]);
        if ($orderInfo && !is_array($orderInfo)) {
            $orderInfo = $orderInfo->toArray();
        }
        if (empty($orderInfo)) {
            fdump_api(['err' => '对应工单不存在', 'params' => $params], 'jzOpen/handlePublicToReportLog', 1);
            return true;
        }
        $isPublic = false;
        if (isset($orderInfo['address_type']) && $orderInfo['address_type'] == 'public') {
            $isPublic = true;
        } elseif (isset($orderInfo['public_id']) && intval($orderInfo['public_id']) > 0) {
            $isPublic = true;
        }
        if (!$isPublic) {
            fdump_api(['err' => '对应工单非公共区域工单', 'params' => $params, 'orderInfo' => $orderInfo], 'jzOpen/handlePublicToReportLog', 1);
            return true;
        }
        !$village_id && $village_id = isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : 0;
        $configInfo = (new HouseCommunityAboutService())->getHouseCommunityConfig($village_id);
        if ($configInfo && !is_array($configInfo)) {
            $configInfo = $configInfo->toArray();
        }
        if (empty($configInfo)) {
            fdump_api(['err' => '没有相关配置不进行操作', 'params' => $params], 'jzOpen/handlePublicToReportLog', 1);
            return true;
        }
        $not_timely_report_switch = isset($configInfo['not_timely_report_switch']) ? intval($configInfo['not_timely_report_switch']) : 0;
        if ($not_timely_report_switch == 1) {
            fdump_api(['err' => '不上报督查室', 'params' => $params, 'configInfo' => $configInfo], 'jzOpen/handlePublicToReportLog', 1);
            return true;
        }
        $db_house_community_quality_bind_repair_cate = new HouseCommunityQualityBindRepairCate();
        $whereBindCate = [];
        $village_id && $whereBindCate[] = ['village_id', '=', $village_id];
        if (isset($orderInfo['cat_fid']) && $orderInfo['cat_fid']) {
            $whereBindCate[] = ['repair_cate_fid', '=', $orderInfo['cat_fid']];
        }
        $whereBindCate[] = ['repair_cate_id', '=', $orderInfo['cat_id']];
        $repairCateInfo = $db_house_community_quality_bind_repair_cate->getOne($whereBindCate, 'not_timely_report_switch');
        $cate_not_timely_report_switch = isset($repairCateInfo['not_timely_report_switch']) ? intval($repairCateInfo['not_timely_report_switch']) : 0;
        if ($cate_not_timely_report_switch == 1) {
            fdump_api(['err' => '[启用维修基金开启了]不上报督查室', 'params' => $params, 'configInfo' => $configInfo], 'jzOpen/handlePublicToReportLog', 1);
            return true;
        }
        $whereLastLog = [
            'order_id' => $works_order_id,
        ];
        $dbHouseNewRepairWorksOrderLog = new HouseNewRepairWorksOrderLog();
        $lastRecord = $dbHouseNewRepairWorksOrderLog->getOne($whereLastLog);
        $operator_id = isset($lastRecord['operator_id']) ? intval($lastRecord['operator_id']) : 0;
        $log_name = 'public_report';
        $nowTime = time();
        $log_content = "提交公共区域工单上报督查室";
        $log_data = [
            'order_id' => $works_order_id,
            'log_name' => $log_name,
            'village_id' => isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : '',
            'property_id' => isset($orderInfo['property_id']) ? intval($orderInfo['property_id']) : '',
            'log_operator' => '系统自动上报督查室',
            'log_phone' => '',
            'operator_type' => 30,
            'operator_id' => $operator_id,
            'log_uid' => '',
            'log_content' => $log_content,
            'log_imgs' => '',
            'evaluate' => 0,
            'add_time' => $nowTime,
        ];
        $log_data['log_num'] = 1;
        $log_data['project_id'] = $orderInfo['project_id'];
        $log_data['rule_id'] = $orderInfo['rule_id'];
        $log_data['charge_price'] = 0;
        $log_data['pay_type'] = 0;
        $log_data['change_reason'] = '提交公共区域工单上报督查室';
        $sHouseNewRepairService = new HouseNewRepairService();
        $log_id = $sHouseNewRepairService->addRepairLog($log_data);
        if ($log_id) {
            $updateOrder = ['mark_report' => 1, 'mark_type' => 'public', 'mark_time' => time()];
            $dbHouseNewRepairWorksOrder->saveOne(['order_id' => $works_order_id], $updateOrder);
            $dbHouseCommunitySupervisionConfig = new HouseCommunitySupervisionConfig();
            $where = [];
            $where[]  = ['notice_switch', '=', 1];
            $where[]  = ['notice_admin_id', '>', 0];
            $where[]  = ['notice_admin_from', '=', 'admin'];
            $config = $dbHouseCommunitySupervisionConfig->getOne($where);
            if ($config && !is_array($config)) {
                $config = $config->toArray();
            }
            if (empty($config)) {
                fdump_api(['err' => '督查室未设置被推送管理员-不进行推送', 'params' => $params, 'configInfo' => $configInfo, 'log_id' => $log_id], 'jzOpen/handlePublicToReportLog', 1);
                return true;
            }
            $dbAdmin = new Admin();
            $adminUser = $dbAdmin->getUserById($config['notice_admin_id']);
            if ($adminUser && !is_array($adminUser)) {
                $adminUser = $adminUser->toArray();
            }
            if (!isset($adminUser['openid']) || !$adminUser['openid']) {
                fdump_api(['err' => '督查室设置被推送管理员未绑定微信-不进行推送', 'params' => $params, 'configInfo' => $configInfo, 'log_id' => $log_id, 'adminUser' => $adminUser], 'jzOpen/handleRejectOverToReportLog', 1);
                return true;
            }
            $dbHouseNewRepairCate = new HouseNewRepairCate();
            $catFid = isset($orderInfo['cat_fid']) ? intval($orderInfo['cat_fid']) : 0;
            $catFidInfo = $dbHouseNewRepairCate->getOne(['id' => $catFid], 'cate_name');
            $catId = isset($orderInfo['cat_id']) ? intval($orderInfo['cat_id']) : 0;
            $catInfo = $dbHouseNewRepairCate->getOne(['id' => $catId], 'cate_name');
            $catFidCateName = isset($catFidInfo['cate_name']) ? trim($catFidInfo['cate_name']) : '';
            $catCateName = isset($catInfo['cate_name']) ? trim($catInfo['cate_name']) : '';

            $villageInfo = (new HouseVillage())->getOne($village_id, 'village_name');
            $village_name = isset($villageInfo['village_name']) ? trim($villageInfo['village_name']) : '';

            // 项目名称：thing22 工单类型：thing3 工单状态：thing10 处理时间：time11 工单地址：thing20 处理原因：thing14
            // thing 长度限制20
            $thing22 = $village_name;
            $thing3 = $catFidCateName . '/' . $catCateName;
            if (mb_strlen($thing3) > 15) {
                $thing3 = msubstr($thing3, 0, 15);
            }
            $sHouseNewRepairService = new HouseNewRepairService();
            $thing10 = isset($orderInfo['event_status']) ? $sHouseNewRepairService->order_work_status_txt[$orderInfo['event_status']] : '无';
            $update_time = isset($orderInfo['update_time']) ? intval($orderInfo['update_time']) : 0;
            !$update_time && $update_time = isset($orderInfo['add_time']) ? intval($orderInfo['add_time']) : $nowTime;
            $time11 = date('H:i', $update_time);
            $single_id = isset($orderInfo['single_id']) ? intval($orderInfo['single_id']) : 0;
            $floor_id = isset($orderInfo['floor_id']) ? intval($orderInfo['floor_id']) : 0;
            $layer_id = isset($orderInfo['layer_id']) ? intval($orderInfo['layer_id']) : 0;
            $room_id = isset($orderInfo['room_id']) ? intval($orderInfo['room_id']) : 0;
            $village_id = isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : 0;
            if ($single_id > 0 && $floor_id > 0 && $layer_id > 0 && $room_id > 0 && $village_id > 0) {
                $service_house_village = new HouseVillageService();
                $address = $service_house_village->getSingleFloorRoom($single_id, $floor_id, $layer_id, $room_id, $village_id);
                $address = $address ? $address : '无';
            } else {
                $address = $orderInfo['address_txt'] ?: '无';
            }
            $villageInfo = (new HouseVillage())->getOne($village_id, 'village_id, village_name, property_id');
            $villageAddress = isset($villageInfo['village_name']) ? trim($villageInfo['village_name']) . ' ' : '';
            $address = $villageAddress . $address;

            $thing20 = $address;
            $thing14 = $log_content;
            $href = '';
            $msgArr = [
                'href' => $href,
                'wecha_id' => $adminUser['openid'],
                'first' => '提交公共区域工单上报督查室',
                'keyword1' => $thing3,
                'keyword2' => '已发送',
                'keyword3' => date('H时i分', $_SERVER['REQUEST_TIME']),
                'remark' => '请点击查看详细信息！',
                'new_info' => [//新版本发送需要的信息
                    'tempKey' => '43030',//新模板号
                    'thing22' => $thing22,//项目名称
                    'thing3' => $thing3,//工单类型
                    'thing10' => $thing10,//工单状态
                    'time11' => $time11,//处理时间
                    'thing20' => $thing20,//工单地址
                    'thing14' => $thing14,//处理原因
                ],
            ];
            // 项目名称：thing22 工单类型：thing3 工单状态：thing10 处理时间：time11 工单地址：thing20 处理原因：thing14
            $TemplateNewsService=new TemplateNewsService();
            $TemplateNewsService->sendTempMsg('OPENTM400166399', $msgArr);//todo 类目模板OPENTM400166399
        }
        return true;
    }

    /**
     * 拒绝次数超了上报.
     */
    protected function handleRejectOverToReport($params) {
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $works_order_id = isset($params['works_order_id']) ? intval($params['works_order_id']) : 0;

        $dbHouseNewRepairWorksOrder = new HouseNewRepairWorksOrder();
        $orderInfo = $dbHouseNewRepairWorksOrder->getOne(['order_id' => $works_order_id]);
        if ($orderInfo && !is_array($orderInfo)) {
            $orderInfo = $orderInfo->toArray();
        }
        if (empty($orderInfo)) {
            fdump_api(['err' => '对应工单不存在', 'params' => $params], 'jzOpen/handleRejectOverToReportLog', 1);
            return true;
        }
        $dbHouseCommunityWorkOrderConfig = new HouseCommunityWorkOrderConfig();
        $whereConfig = [];
        !$village_id && $village_id = isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : 0;
        $village_id && $whereConfig['village_id'] = $village_id;
        $whereConfig['works_order_id'] = $works_order_id;
        $configInfo = $dbHouseCommunityWorkOrderConfig->getOne($whereConfig, 'id, works_order_id, village_id, refuse_order_nums, refuse_order_nums_report, not_report_switch');
        if ($configInfo && !is_array($configInfo)) {
            $configInfo = $configInfo->toArray();
        }
        if (empty($configInfo)) {
            fdump_api(['err' => '没有相关配置不进行操作', 'params' => $params], 'jzOpen/handleRejectOverToReportLog', 1);
            return true;
        }
        $not_report_switch = isset($configInfo['not_report_switch']) ? intval($configInfo['not_report_switch']) : 0;
        if ($not_report_switch == 1) {
            fdump_api(['err' => '不上报督查室', 'params' => $params, 'configInfo' => $configInfo], 'jzOpen/handleRejectOverToReportLog', 1);
            return true;
        }
        $db_house_community_quality_bind_repair_cate = new HouseCommunityQualityBindRepairCate();
        $whereBindCate = [];
        $village_id && $whereBindCate[] = ['village_id', '=', $village_id];
        if (isset($orderInfo['cat_fid']) && $orderInfo['cat_fid']) {
            $whereBindCate[] = ['repair_cate_fid', '=', $orderInfo['cat_fid']];
        }
        $whereBindCate[] = ['repair_cate_id', '=', $orderInfo['cat_id']];
        $repairCateInfo = $db_house_community_quality_bind_repair_cate->getOne($whereBindCate, 'not_timely_report_switch');
        $cate_not_timely_report_switch = isset($repairCateInfo['not_timely_report_switch']) ? intval($repairCateInfo['not_timely_report_switch']) : 0;
        if ($cate_not_timely_report_switch == 1) {
            fdump_api(['err' => '[启用维修基金开启了]不上报督查室', 'params' => $params, 'configInfo' => $configInfo], 'jzOpen/handleRejectOverToReportLog', 1);
            return true;
        }
        $refuse_order_nums_report = isset($configInfo['refuse_order_nums_report']) ? intval($configInfo['refuse_order_nums_report']) : 0;
        $dbHouseNewRepairWorksOrderLog = new HouseNewRepairWorksOrderLog();
        $whereOrderLog = [
            'order_id' => $works_order_id,
            'log_name' => 'reject',
        ];
        $refuse_order_nums = $dbHouseNewRepairWorksOrderLog->getCount($whereOrderLog);
        $nowTime = time();
        $updateConfig = ['refuse_order_nums' => $refuse_order_nums, 'update_time' => $nowTime];
        $dbHouseCommunityWorkOrderConfig->updateThis(['id' => $configInfo['id']], $updateConfig);
        if ($refuse_order_nums_report <= 0) {
            fdump_api(['err' => '未配置拒绝次数操作', 'params' => $params, 'configInfo' => $configInfo], 'jzOpen/handleRejectOverToReportLog', 1);
            return true;
        }
        if ($refuse_order_nums < $refuse_order_nums_report) {
            fdump_api(['err' => '未达到拒绝设置上限', 'refuse_order_nums' => $refuse_order_nums, 'refuse_order_nums_report' => $refuse_order_nums_report, 'params' => $params, 'configInfo' => $configInfo], 'jzOpen/handleRejectOverToReportLog', 1);
            return true;
        }
        $log_name = 'refuse_order_nums_report';
        $whereOrderLog = [
            'order_id' => $works_order_id,
            'log_name' => $log_name,
        ];
        $record = $dbHouseNewRepairWorksOrderLog->getOne($whereOrderLog, 'log_id');
        if (isset($record['log_id']) && intval($record['log_id']) > 0) {
            fdump_api(['err' => '已经上报过督查室', 'params' => $params, 'configInfo' => $configInfo, 'log_id' => $record['log_id']], 'jzOpen/handleRejectOverToReportLog', 1);
            return true;
        }
        $whereLastLog = [
            'order_id' => $works_order_id,
        ];
        $lastRecord = $dbHouseNewRepairWorksOrderLog->getOne($whereLastLog);
        $operator_id = isset($lastRecord['operator_id']) ? intval($lastRecord['operator_id']) : 0;
        $log_content = "提交工单人员拒绝了{$refuse_order_nums}次工单上报督查室";
        $log_data = [
            'order_id' => $works_order_id,
            'log_name' => $log_name,
            'village_id' => isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : '',
            'property_id' => isset($orderInfo['property_id']) ? intval($orderInfo['property_id']) : '',
            'log_operator' => '系统自动上报督查室',
            'log_phone' => '',
            'operator_type' => 30,
            'operator_id' => $operator_id,
            'log_uid' => '',
            'log_content' => $log_content,
            'log_imgs' => '',
            'evaluate' => 0,
            'add_time' => $nowTime,
        ];
        $log_data['log_num'] = 1;
        $log_data['project_id'] = $orderInfo['project_id'];
        $log_data['rule_id'] = $orderInfo['rule_id'];
        $log_data['charge_price'] = 0;
        $log_data['pay_type'] = 0;
        $log_data['change_reason'] = '提交工单人员拒绝工单';
        $sHouseNewRepairService = new HouseNewRepairService();
        $log_id = $sHouseNewRepairService->addRepairLog($log_data);
        if ($log_id) {
            $updateOrder = ['mark_report' => 1, 'mark_type' => 'rejectOver', 'mark_time' => time()];
            $dbHouseNewRepairWorksOrder->saveOne(['order_id' => $works_order_id], $updateOrder);
            $dbHouseCommunitySupervisionConfig = new HouseCommunitySupervisionConfig();
            $where = [];
            $where[]  = ['notice_switch', '=', 1];
            $where[]  = ['notice_admin_id', '>', 0];
            $where[]  = ['notice_admin_from', '=', 'admin'];
            $config = $dbHouseCommunitySupervisionConfig->getOne($where);
            if ($config && !is_array($config)) {
                $config = $config->toArray();
            }
            if (empty($config)) {
                fdump_api(['err' => '督查室未设置被推送管理员-不进行推送', 'params' => $params, 'configInfo' => $configInfo, 'log_id' => $log_id], 'jzOpen/handleRejectOverToReportLog', 1);
                return true;
            }
            $dbAdmin = new Admin();
            $adminUser = $dbAdmin->getUserById($config['notice_admin_id']);
            if ($adminUser && !is_array($adminUser)) {
                $adminUser = $adminUser->toArray();
            }
            if (!isset($adminUser['openid']) || !$adminUser['openid']) {
                fdump_api(['err' => '督查室设置被推送管理员未绑定微信-不进行推送', 'params' => $params, 'configInfo' => $configInfo, 'log_id' => $log_id, 'adminUser' => $adminUser], 'jzOpen/handleRejectOverToReportLog', 1);
                return true;
            }
            $dbHouseNewRepairCate = new HouseNewRepairCate();
            $catFid = isset($orderInfo['cat_fid']) ? intval($orderInfo['cat_fid']) : 0;
            $catFidInfo = $dbHouseNewRepairCate->getOne(['id' => $catFid], 'cate_name');
            $catId = isset($orderInfo['cat_id']) ? intval($orderInfo['cat_id']) : 0;
            $catInfo = $dbHouseNewRepairCate->getOne(['id' => $catId], 'cate_name');
            $catFidCateName = isset($catFidInfo['cate_name']) ? trim($catFidInfo['cate_name']) : '';
            $catCateName = isset($catInfo['cate_name']) ? trim($catInfo['cate_name']) : '';

            $villageInfo = (new HouseVillage())->getOne($village_id, 'village_name');
            $village_name = isset($villageInfo['village_name']) ? trim($villageInfo['village_name']) : '';

            // 项目名称：thing22 工单类型：thing3 工单状态：thing10 处理时间：time11 工单地址：thing20 处理原因：thing14
            // thing 长度限制20
            $thing22 = $village_name;
            $thing3 = $catFidCateName . '/' . $catCateName;
            if (mb_strlen($thing3) > 15) {
                $thing3 = msubstr($thing3, 0, 15);
            }
            $sHouseNewRepairService = new HouseNewRepairService();
            $thing10 = isset($orderInfo['event_status']) ? $sHouseNewRepairService->order_work_status_txt[$orderInfo['event_status']] : '无';
            $update_time = isset($orderInfo['update_time']) ? intval($orderInfo['update_time']) : 0;
            !$update_time && $update_time = isset($orderInfo['add_time']) ? intval($orderInfo['add_time']) : $nowTime;
            $time11 = date('H:i', $update_time);
            $single_id = isset($orderInfo['single_id']) ? intval($orderInfo['single_id']) : 0;
            $floor_id = isset($orderInfo['floor_id']) ? intval($orderInfo['floor_id']) : 0;
            $layer_id = isset($orderInfo['layer_id']) ? intval($orderInfo['layer_id']) : 0;
            $room_id = isset($orderInfo['room_id']) ? intval($orderInfo['room_id']) : 0;
            $village_id = isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : 0;
            if ($single_id > 0 && $floor_id > 0 && $layer_id > 0 && $room_id > 0 && $village_id > 0) {
                $service_house_village = new HouseVillageService();
                $address = $service_house_village->getSingleFloorRoom($single_id, $floor_id, $layer_id, $room_id, $village_id);
                $address = $address ? $address : '无';
            } else {
                $address = $orderInfo['address_txt'] ?: '无';
            }
            $villageInfo = (new HouseVillage())->getOne($village_id, 'village_id, village_name, property_id');
            $villageAddress = isset($villageInfo['village_name']) ? trim($villageInfo['village_name']) . ' ' : '';
            $address = $villageAddress . $address;

            $thing20 = $address;
            $thing14 = $log_content;
            $href = '';
            $msgArr = [
                'href' => $href,
                'wecha_id' => $adminUser['openid'],
                'first' => '提交工单人员拒绝工单',
                'keyword1' => $thing3,
                'keyword2' => '已发送',
                'keyword3' => date('H时i分', $_SERVER['REQUEST_TIME']),
                'remark' => '请点击查看详细信息！',
                'new_info' => [//新版本发送需要的信息
                    'tempKey' => '43030',//新模板号
                    'thing22' => $thing22,//项目名称
                    'thing3' => $thing3,//工单类型
                    'thing10' => $thing10,//工单状态
                    'time11' => $time11,//处理时间
                    'thing20' => $thing20,//工单地址
                    'thing14' => $thing14,//处理原因
                ],
            ];
            // 项目名称：thing22 工单类型：thing3 工单状态：thing10 处理时间：time11 工单地址：thing20 处理原因：thing14
            $TemplateNewsService=new TemplateNewsService();
            $TemplateNewsService->sendTempMsg('OPENTM400166399', $msgArr);//todo 类目模板OPENTM400166399
        }
        return true;
    }
    
    /**
     * 工作人员拒绝工单施工方案次数超了上报box.
     */
    public function tryHandleRejectProjectOverToReportBox($params) {
        try {
            return $this->handleRejectProjectOverToReport($params);
        } catch (\Exception $e) {
            fdump_api(['err' => '异常捕捉盒子报错-工作人员拒绝工单施工方案次数超了上报', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandleRejectProjectOverToReportBoxLog', 1);
            return false;
        } catch (\Throwable $e) {
            fdump_api(['err' => '异常捕捉盒子报错-工作人员拒绝工单施工方案次数超了上报', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandleRejectProjectOverToReportBoxLog', 1);
            return false;
        } catch (\RuntimeException $e) {
            fdump_api(['err' => '异常捕捉盒子报错-工作人员拒绝工单施工方案次数超了上报', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandleRejectProjectOverToReportBoxLog', 1);
            return false;
        } catch (\InvalidArgumentException $e) {
            fdump_api(['err' => '异常捕捉盒子报错-工作人员拒绝工单施工方案次数超了上报', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandleRejectProjectOverToReportBoxLog', 1);
            return false;
        }
    }

    protected function handleRejectProjectOverToReport($params)
    {
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $works_order_id = isset($params['works_order_id']) ? intval($params['works_order_id']) : 0;

        $dbHouseNewRepairWorksOrder = new HouseNewRepairWorksOrder();
        $orderInfo = $dbHouseNewRepairWorksOrder->getOne(['order_id' => $works_order_id]);
        if ($orderInfo && !is_array($orderInfo)) {
            $orderInfo = $orderInfo->toArray();
        }
        if (empty($orderInfo)) {
            fdump_api(['err' => '对应工单不存在', 'params' => $params], 'jzOpen/handleRejectProjectOverToReportLog', 1);
            return true;
        }
        $dbHouseCommunityWorkOrderConfig = new HouseCommunityWorkOrderConfig();
        $whereConfig = [];
        !$village_id && $village_id = isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : 0;
        $village_id && $whereConfig['village_id'] = $village_id;
        $whereConfig['works_order_id'] = $works_order_id;
        $configInfo = $dbHouseCommunityWorkOrderConfig->getOne($whereConfig, 'id, works_order_id, village_id, refuse_order_nums, refuse_project_nums_report, not_report_switch');
        if ($configInfo && !is_array($configInfo)) {
            $configInfo = $configInfo->toArray();
        }
        if (empty($configInfo)) {
            fdump_api(['err' => '没有相关配置不进行操作', 'params' => $params], 'jzOpen/handleRejectProjectOverToReportLog', 1);
            return true;
        }
        $not_report_switch = isset($configInfo['not_report_switch']) ? intval($configInfo['not_report_switch']) : 0;
        if ($not_report_switch == 1) {
            fdump_api(['err' => '不上报督查室', 'params' => $params, 'configInfo' => $configInfo], 'jzOpen/handleRejectProjectOverToReportLog', 1);
            return true;
        }
        $db_house_community_quality_bind_repair_cate = new HouseCommunityQualityBindRepairCate();
        $whereBindCate = [];
        $village_id && $whereBindCate[] = ['village_id', '=', $village_id];
        if (isset($orderInfo['cat_fid']) && $orderInfo['cat_fid']) {
            $whereBindCate[] = ['repair_cate_fid', '=', $orderInfo['cat_fid']];
        }
        $whereBindCate[] = ['repair_cate_id', '=', $orderInfo['cat_id']];
        $repairCateInfo = $db_house_community_quality_bind_repair_cate->getOne($whereBindCate, 'not_timely_report_switch');
        $cate_not_timely_report_switch = isset($repairCateInfo['not_timely_report_switch']) ? intval($repairCateInfo['not_timely_report_switch']) : 0;
        if ($cate_not_timely_report_switch == 1) {
            fdump_api(['err' => '[启用维修基金开启了]不上报督查室', 'params' => $params, 'configInfo' => $configInfo], 'jzOpen/handleRejectProjectOverToReportLog', 1);
            return true;
        }
        $refuse_project_nums_report = isset($configInfo['refuse_project_nums_report']) ? intval($configInfo['refuse_project_nums_report']) : 0;
        $dbHouseNewRepairWorksOrderLog = new HouseNewRepairWorksOrderLog();
        $whereOrderLog = [
            'order_id' => $works_order_id,
            'log_name' => 'reject_constructor_project',
        ];
        $refuse_project_nums = $dbHouseNewRepairWorksOrderLog->getCount($whereOrderLog);
        $nowTime = time();
        $updateConfig = ['refuse_project_nums' => $refuse_project_nums, 'update_time' => $nowTime];
        $dbHouseCommunityWorkOrderConfig->updateThis(['id' => $configInfo['id']], $updateConfig);
        if ($refuse_project_nums_report <= 0) {
            fdump_api(['err' => '未配置拒绝次数操作', 'params' => $params, 'configInfo' => $configInfo], 'jzOpen/handleRejectProjectOverToReportLog', 1);
            return true;
        }
        if ($refuse_project_nums < $refuse_project_nums_report) {
            fdump_api(['err' => '未达到拒绝设置上限', 'refuse_project_nums' => $refuse_project_nums, 'refuse_project_nums_report' => $refuse_project_nums_report, 'params' => $params, 'configInfo' => $configInfo], 'jzOpen/handleRejectProjectOverToReportLog', 1);
            return true;
        }
        $log_name = 'refuse_project_nums_report';
        $whereOrderLog = [
            'order_id' => $works_order_id,
            'log_name' => $log_name,
        ];
        $record = $dbHouseNewRepairWorksOrderLog->getOne($whereOrderLog, 'log_id');
        if (isset($record['log_id']) && intval($record['log_id']) > 0) {
            fdump_api(['err' => '已经上报过督查室', 'params' => $params, 'configInfo' => $configInfo, 'log_id' => $record['log_id']], 'jzOpen/handleRejectProjectOverToReportLog', 1);
            return true;
        }
        $whereLastLog = [
            'order_id' => $works_order_id,
        ];
        $lastRecord = $dbHouseNewRepairWorksOrderLog->getOne($whereLastLog);
        $operator_id = isset($lastRecord['operator_id']) ? intval($lastRecord['operator_id']) : 0;
        $log_content = "工单工作人员拒绝了{$refuse_project_nums}次工单上报督查室";
        $log_data = [
            'order_id' => $works_order_id, 'log_name' => $log_name,
            'village_id' => isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : '',
            'property_id' => isset($orderInfo['property_id']) ? intval($orderInfo['property_id']) : '',
            'log_operator' => '系统自动上报督查室', 'log_phone' => '', 'operator_type' => 30,
            'operator_id' => $operator_id, 'log_uid' => '', 'log_content' => $log_content,
            'log_imgs' => '', 'evaluate' => 0, 'add_time' => $nowTime,
        ];
        $log_data['log_num'] = 1;
        $log_data['project_id'] = $orderInfo['project_id'];
        $log_data['rule_id'] = $orderInfo['rule_id'];
        $log_data['charge_price'] = 0;
        $log_data['pay_type'] = 0;
        $log_data['change_reason'] = '工单工作人员拒绝工单';
        $sHouseNewRepairService = new HouseNewRepairService();
        $log_id = $sHouseNewRepairService->addRepairLog($log_data);
        if ($log_id) {
            $updateOrder = ['mark_report' => 1, 'mark_type' => 'rejectOver', 'mark_time' => time()];
            $dbHouseNewRepairWorksOrder->saveOne(['order_id' => $works_order_id], $updateOrder);
            $dbHouseCommunitySupervisionConfig = new HouseCommunitySupervisionConfig();
            $where = [];
            $where[] = ['notice_switch', '=', 1];
            $where[] = ['notice_admin_id', '>', 0];
            $where[] = ['notice_admin_from', '=', 'admin'];
            $config = $dbHouseCommunitySupervisionConfig->getOne($where);
            if ($config && !is_array($config)) {
                $config = $config->toArray();
            }
            if (empty($config)) {
                fdump_api(['err' => '督查室未设置被推送管理员-不进行推送', 'params' => $params, 'configInfo' => $configInfo, 'log_id' => $log_id], 'jzOpen/handleRejectProjectOverToReportLog', 1);
                return true;
            }
            $dbAdmin = new Admin();
            $adminUser = $dbAdmin->getUserById($config['notice_admin_id']);
            if ($adminUser && !is_array($adminUser)) {
                $adminUser = $adminUser->toArray();
            }
            if (!isset($adminUser['openid']) || !$adminUser['openid']) {
                fdump_api(['err' => '督查室设置被推送管理员未绑定微信-不进行推送', 'params' => $params, 'configInfo' => $configInfo, 'log_id' => $log_id, 'adminUser' => $adminUser], 'jzOpen/handleRejectProjectOverToReportLog', 1);
                return true;
            }
            $dbHouseNewRepairCate = new HouseNewRepairCate();
            $catFid = isset($orderInfo['cat_fid']) ? intval($orderInfo['cat_fid']) : 0;
            $catFidInfo = $dbHouseNewRepairCate->getOne(['id' => $catFid], 'cate_name');
            $catId = isset($orderInfo['cat_id']) ? intval($orderInfo['cat_id']) : 0;
            $catInfo = $dbHouseNewRepairCate->getOne(['id' => $catId], 'cate_name');
            $catFidCateName = isset($catFidInfo['cate_name']) ? trim($catFidInfo['cate_name']) : '';
            $catCateName = isset($catInfo['cate_name']) ? trim($catInfo['cate_name']) : '';

            $villageInfo = (new HouseVillage())->getOne($village_id, 'village_name');
            $village_name = isset($villageInfo['village_name']) ? trim($villageInfo['village_name']) : '';

            // 项目名称：thing22 工单类型：thing3 工单状态：thing10 处理时间：time11 工单地址：thing20 处理原因：thing14
            // thing 长度限制20
            $thing22 = $village_name;
            $thing3 = $catFidCateName . '/' . $catCateName;
            if (mb_strlen($thing3) > 15) {
                $thing3 = msubstr($thing3, 0, 15);
            }
            $sHouseNewRepairService = new HouseNewRepairService();
            $thing10 = isset($orderInfo['event_status']) ? $sHouseNewRepairService->order_work_status_txt[$orderInfo['event_status']] : '无';
            $update_time = isset($orderInfo['update_time']) ? intval($orderInfo['update_time']) : 0;
            !$update_time && $update_time = isset($orderInfo['add_time']) ? intval($orderInfo['add_time']) : $nowTime;
            $time11 = date('H:i', $update_time);
            $single_id = isset($orderInfo['single_id']) ? intval($orderInfo['single_id']) : 0;
            $floor_id = isset($orderInfo['floor_id']) ? intval($orderInfo['floor_id']) : 0;
            $layer_id = isset($orderInfo['layer_id']) ? intval($orderInfo['layer_id']) : 0;
            $room_id = isset($orderInfo['room_id']) ? intval($orderInfo['room_id']) : 0;
            $village_id = isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : 0;
            if ($single_id > 0 && $floor_id > 0 && $layer_id > 0 && $room_id > 0 && $village_id > 0) {
                $service_house_village = new HouseVillageService();
                $address = $service_house_village->getSingleFloorRoom($single_id, $floor_id, $layer_id, $room_id, $village_id);
                $address = $address ? $address : '无';
            } else {
                $address = $orderInfo['address_txt'] ?: '无';
            }
            $villageInfo = (new HouseVillage())->getOne($village_id, 'village_id, village_name, property_id');
            $villageAddress = isset($villageInfo['village_name']) ? trim($villageInfo['village_name']) . ' ' : '';
            $address = $villageAddress . $address;

            $thing20 = $address;
            $thing14 = $log_content;
            $href = '';
            $msgArr = [
                'href' => $href,
                'wecha_id' => $adminUser['openid'],
                'first' => '提交工单人员拒绝工单',
                'keyword1' => $thing3,
                'keyword2' => '已发送',
                'keyword3' => date('H时i分', $_SERVER['REQUEST_TIME']),
                'remark' => '请点击查看详细信息！',
                'new_info' => [//新版本发送需要的信息
                    'tempKey' => '43030',//新模板号
                    'thing22' => $thing22,//项目名称
                    'thing3' => $thing3,//工单类型
                    'thing10' => $thing10,//工单状态
                    'time11' => $time11,//处理时间
                    'thing20' => $thing20,//工单地址
                    'thing14' => $thing14,//处理原因
                ],
            ];
            // 项目名称：thing22 工单类型：thing3 工单状态：thing10 处理时间：time11 工单地址：thing20 处理原因：thing14
            $TemplateNewsService = new TemplateNewsService();
            $TemplateNewsService->sendTempMsg('OPENTM400166399', $msgArr);//todo 类目模板OPENTM400166399
        }
        return true;
    }
    
    /**
     * 低分评价了工单.
     */
    protected function handleEvaluateLowToReport($params) {
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $works_order_id = isset($params['works_order_id']) ? intval($params['works_order_id']) : 0;

        $dbHouseNewRepairWorksOrder = new HouseNewRepairWorksOrder();
        $orderInfo = $dbHouseNewRepairWorksOrder->getOne(['order_id' => $works_order_id]);
        if ($orderInfo && !is_array($orderInfo)) {
            $orderInfo = $orderInfo->toArray();
        }
        if (empty($orderInfo)) {
            fdump_api(['err' => '对应工单不存在', 'params' => $params], 'jzOpen/handleEvaluateLowToReportLog', 1);
            return true;
        }
        $evaluate = isset($orderInfo['evaluate']) ? intval($orderInfo['evaluate']) : 0;
        if ($evaluate <= 0) {
            fdump_api(['err' => '工单评分异常（最少1星）', 'params' => $params, 'orderInfo' => $orderInfo], 'jzOpen/handleEvaluateLowToReportLog', 1);
            return true;
        }
        $dbHouseCommunityWorkOrderConfig = new HouseCommunityWorkOrderConfig();
        $whereConfig = [];
        !$village_id && $village_id = isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : 0;
        $village_id && $whereConfig['village_id'] = $village_id;
        $whereConfig['works_order_id'] = $works_order_id;
        $configInfo = $dbHouseCommunityWorkOrderConfig->getOne($whereConfig, 'id, works_order_id, village_id, not_over_stars_report, not_report_switch');
        if ($configInfo && !is_array($configInfo)) {
            $configInfo = $configInfo->toArray();
        }
        if (empty($configInfo)) {
            fdump_api(['err' => '没有相关配置不进行操作', 'params' => $params], 'jzOpen/handleEvaluateLowToReportLog', 1);
            return true;
        }
        $not_report_switch = isset($configInfo['not_report_switch']) ? intval($configInfo['not_report_switch']) : 0;
        if ($not_report_switch == 1) {
            fdump_api(['err' => '不上报督查室', 'params' => $params, 'configInfo' => $configInfo], 'jzOpen/handleEvaluateLowToReportLog', 1);
            return true;
        }
        $db_house_community_quality_bind_repair_cate = new HouseCommunityQualityBindRepairCate();
        $whereBindCate = [];
        $village_id && $whereBindCate[] = ['village_id', '=', $village_id];
        if (isset($orderInfo['cat_fid']) && $orderInfo['cat_fid']) {
            $whereBindCate[] = ['repair_cate_fid', '=', $orderInfo['cat_fid']];
        }
        $whereBindCate[] = ['repair_cate_id', '=', $orderInfo['cat_id']];
        $repairCateInfo = $db_house_community_quality_bind_repair_cate->getOne($whereBindCate, 'not_timely_report_switch');
        $cate_not_timely_report_switch = isset($repairCateInfo['not_timely_report_switch']) ? intval($repairCateInfo['not_timely_report_switch']) : 0;
        if ($cate_not_timely_report_switch == 1) {
            fdump_api(['err' => '[启用维修基金开启了]不上报督查室', 'params' => $params, 'configInfo' => $configInfo], 'jzOpen/handleEvaluateLowToReportLog', 1);
            return true;
        }
        $not_over_stars_report = isset($configInfo['not_over_stars_report']) ? intval($configInfo['not_over_stars_report']) : 0;
        if ($not_over_stars_report <= 0) {
            fdump_api(['err' => '未配置等于或低于N星评价上报督查室', 'params' => $params,  'orderInfo' => $orderInfo, 'configInfo' => $configInfo], 'jzOpen/handleEvaluateLowToReportLog', 1);
            return true;
        }
        if ($evaluate > $not_over_stars_report) {
            fdump_api(['err' => '评价不低不进行上报', 'params' => $params,  'orderInfo' => $orderInfo, 'configInfo' => $configInfo], 'jzOpen/handleEvaluateLowToReportLog', 1);
            return true;
        }
        $dbHouseNewRepairWorksOrderLog = new HouseNewRepairWorksOrderLog();
        $log_name = 'not_over_stars_report';
        $whereOrderLog = [
            'order_id' => $works_order_id,
            'log_name' => $log_name,
        ];
        $record = $dbHouseNewRepairWorksOrderLog->getOne($whereOrderLog, 'log_id');
        if (isset($record['log_id']) && intval($record['log_id']) > 0) {
            fdump_api(['err' => '已经上报过督查室', 'params' => $params, 'configInfo' => $configInfo, 'log_id' => $record['log_id']], 'jzOpen/handleEvaluateLowToReportLog', 1);
            return true;
        }
        $whereLastLog = [
            'order_id' => $works_order_id,
        ];
        $lastRecord = $dbHouseNewRepairWorksOrderLog->getOne($whereLastLog);
        $operator_id = isset($lastRecord['operator_id']) ? intval($lastRecord['operator_id']) : 0;
        $nowTime = time();
        $log_content = "用户评价了工单{$evaluate}星，上报督查室";
        $log_data = [
            'order_id' => $works_order_id,
            'log_name' => $log_name,
            'village_id' => isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : '',
            'property_id' => isset($orderInfo['property_id']) ? intval($orderInfo['property_id']) : '',
            'log_operator' => '系统自动上报督查室',
            'log_phone' => '',
            'operator_type' => 30,
            'operator_id' => $operator_id,
            'log_uid' => '',
            'log_content' => $log_content,
            'log_imgs' => '',
            'evaluate' => 0,
            'add_time' => $nowTime,
        ];
        $log_data['log_num'] = 1;
        $log_data['project_id'] = $orderInfo['project_id'];
        $log_data['rule_id'] = $orderInfo['rule_id'];
        $log_data['charge_price'] = 0;
        $log_data['pay_type'] = 0;
        $log_data['change_reason'] = '提交工单人员评价了工单';
        $sHouseNewRepairService = new HouseNewRepairService();
        $log_id = $sHouseNewRepairService->addRepairLog($log_data);
        if ($log_id) {
            $updateOrder = ['mark_report' => 1, 'mark_type' => 'evaluateLow', 'mark_time' => time()];
            $dbHouseNewRepairWorksOrder->saveOne(['order_id' => $works_order_id], $updateOrder);
            $dbHouseCommunitySupervisionConfig = new HouseCommunitySupervisionConfig();
            $where = [];
            $where[]  = ['notice_switch', '=', 1];
            $where[]  = ['notice_admin_id', '>', 0];
            $where[]  = ['notice_admin_from', '=', 'admin'];
            $config = $dbHouseCommunitySupervisionConfig->getOne($where);
            if ($config && !is_array($config)) {
                $config = $config->toArray();
            }
            if (empty($config)) {
                fdump_api(['err' => '督查室未设置被推送管理员-不进行推送', 'params' => $params, 'configInfo' => $configInfo, 'log_id' => $log_id], 'jzOpen/handleEvaluateLowToReportLog', 1);
                return true;
            }
            $dbAdmin = new Admin();
            $adminUser = $dbAdmin->getUserById($config['notice_admin_id']);
            if ($adminUser && !is_array($adminUser)) {
                $adminUser = $adminUser->toArray();
            }
            if (!isset($adminUser['openid']) || !$adminUser['openid']) {
                fdump_api(['err' => '督查室设置被推送管理员未绑定微信-不进行推送', 'params' => $params, 'configInfo' => $configInfo, 'log_id' => $log_id, 'adminUser' => $adminUser], 'jzOpen/handleRejectOverToReportLog', 1);
                return true;
            }
            $dbHouseNewRepairCate = new HouseNewRepairCate();
            $catFid = isset($orderInfo['cat_fid']) ? intval($orderInfo['cat_fid']) : 0;
            $catFidInfo = $dbHouseNewRepairCate->getOne(['id' => $catFid], 'cate_name');
            $catId = isset($orderInfo['cat_id']) ? intval($orderInfo['cat_id']) : 0;
            $catInfo = $dbHouseNewRepairCate->getOne(['id' => $catId], 'cate_name');
            $catFidCateName = isset($catFidInfo['cate_name']) ? trim($catFidInfo['cate_name']) : '';
            $catCateName = isset($catInfo['cate_name']) ? trim($catInfo['cate_name']) : '';

            $villageInfo = (new HouseVillage())->getOne($village_id, 'village_name');
            $village_name = isset($villageInfo['village_name']) ? trim($villageInfo['village_name']) : '';
            // 项目名称：thing22 工单类型：thing3 工单状态：thing10 处理时间：time11 工单地址：thing20 处理原因：thing14
            // thing 长度限制20
            $thing22 = $village_name;
            $thing3 = $catFidCateName . '/' . $catCateName;
            if (mb_strlen($thing3) > 15) {
                $thing3 = msubstr($thing3, 0, 15);
            }
            $sHouseNewRepairService = new HouseNewRepairService();
            $thing10 = isset($orderInfo['event_status']) ? $sHouseNewRepairService->order_work_status_txt[$orderInfo['event_status']] : '无';
            $update_time = isset($orderInfo['update_time']) ? intval($orderInfo['update_time']) : 0;
            !$update_time && $update_time = isset($orderInfo['add_time']) ? intval($orderInfo['add_time']) : $nowTime;
            $time11 = date('H:i', $update_time);
            $single_id = isset($orderInfo['single_id']) ? intval($orderInfo['single_id']) : 0;
            $floor_id = isset($orderInfo['floor_id']) ? intval($orderInfo['floor_id']) : 0;
            $layer_id = isset($orderInfo['layer_id']) ? intval($orderInfo['layer_id']) : 0;
            $room_id = isset($orderInfo['room_id']) ? intval($orderInfo['room_id']) : 0;
            $village_id = isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : 0;
            if ($single_id > 0 && $floor_id > 0 && $layer_id > 0 && $room_id > 0 && $village_id > 0) {
                $service_house_village = new HouseVillageService();
                $address = $service_house_village->getSingleFloorRoom($single_id, $floor_id, $layer_id, $room_id, $village_id);
                $address = $address ? $address : '无';
            } else {
                $address = $orderInfo['address_txt'] ?: '无';
            }
            $villageInfo = (new HouseVillage())->getOne($village_id, 'village_id, village_name, property_id');
            $villageAddress = isset($villageInfo['village_name']) ? trim($villageInfo['village_name']) . ' ' : '';
            $address = $villageAddress . $address;

            $thing20 = $address;
            $thing14 = $log_content;
            $href = '';
            $msgArr = [
                'href' => $href,
                'wecha_id' => $adminUser['openid'],
                'first' => '提交工单人员评价了工单',
                'keyword1' => $thing3,
                'keyword2' => '已发送',
                'keyword3' => date('H时i分', $_SERVER['REQUEST_TIME']),
                'remark' => '请点击查看详细信息！',
                'new_info' => [//新版本发送需要的信息
                    'tempKey' => '43030',//新模板号
                    'thing22' => $thing22,//项目名称
                    'thing3' => $thing3,//工单类型
                    'thing10' => $thing10,//工单状态
                    'time11' => $time11,//处理时间
                    'thing20' => $thing20,//工单地址
                    'thing14' => $thing14,//处理原因
                ],
            ];
            // 项目名称：thing22 工单类型：thing3 工单状态：thing10 处理时间：time11 工单地址：thing20 处理原因：thing14
            $TemplateNewsService=new TemplateNewsService();
            $TemplateNewsService->sendTempMsg('OPENTM400166399', $msgArr);//todo 类目模板OPENTM400166399
        }
        return true;
    }
    
    /**
     * 不接单上报督查室.
     */
    protected function handleNotGrabToReport($params) 
    {
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $works_order_id = isset($params['works_order_id']) ? intval($params['works_order_id']) : 0;
        $notice_type = isset($params['notice_type']) ? trim($params['notice_type']) : '';
        $doJobId = isset($params['doJobId']) ? trim($params['doJobId']) : '';
        $do_notice_time = isset($params['do_notice_time']) ? intval($params['do_notice_time']) : 0;
        $dbHouseCommunityWorkOrderNotice = new HouseCommunityWorkOrderNotice();
        $whereNotice = [];
        $village_id && $whereNotice[] = ['village_id', '=', $village_id];
        if ($works_order_id && $notice_type) {
            $whereNotice[] = ['works_order_id', '=', $works_order_id];
            $whereNotice[] = ['notice_type', '=', $notice_type];
        } else {
            $whereNotice[] = ['doJobId', '=', $doJobId];
        }
        if ($do_notice_time > 0) {
            $whereNotice[] = ['notice_time', '<=', $do_notice_time];
            $order = "notice_time DESC";
        } else {
            $order = "notice_time ASC";
        }
        $noticeInfo = $dbHouseCommunityWorkOrderNotice->getOne($whereNotice, true, $order);
        if ($noticeInfo && !is_array($noticeInfo)) {
            $noticeInfo = $noticeInfo->toArray();
        }
        if (empty($noticeInfo)) {
            fdump_api(['err' => '通知信息已经不存在了', 'params' => $params], 'jzOpen/handleNotGrabToReportLog', 1);
            return true;
        }
        if (isset($noticeInfo['notice_status']) && intval($noticeInfo['notice_status']) != 0) {
            fdump_api(['err' => '通知已经执行或者关闭了', 'params' => $params, 'noticeInfo' => $noticeInfo], 'jzOpen/handleNotGrabToReportLog', 1);
            return true;
        }

        $dbHouseNewRepairWorksOrder = new HouseNewRepairWorksOrder();
        $orderInfo = $dbHouseNewRepairWorksOrder->getOne(['order_id' => $works_order_id]);
        if ($orderInfo && !is_array($orderInfo)) {
            $orderInfo = $orderInfo->toArray();
        }
        if (empty($orderInfo)) {
            fdump_api(['err' => '对应工单不存在', 'params' => $params], 'jzOpen/handleNotGrabToReportLog', 1);
            return true;
        }
        $dbHouseCommunityWorkOrderConfig = new HouseCommunityWorkOrderConfig();
        $whereConfig = [];
        !$village_id && $village_id = isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : 0;
        $village_id && $whereConfig['village_id'] = $village_id;
        $whereConfig['works_order_id'] = $works_order_id;
        $configInfo = $dbHouseCommunityWorkOrderConfig->getOne($whereConfig, 'id, works_order_id, village_id, over_timely_report_time, not_report_switch');
        if ($configInfo && !is_array($configInfo)) {
            $configInfo = $configInfo->toArray();
        }
        if (empty($configInfo)) {
            fdump_api(['err' => '没有相关配置不进行操作', 'params' => $params], 'jzOpen/handleNotGrabToReportLog', 1);
            return true;
        }
        $not_report_switch = isset($configInfo['not_report_switch']) ? intval($configInfo['not_report_switch']) : 0;
        if ($not_report_switch == 1) {
            fdump_api(['err' => '不上报督查室', 'params' => $params, 'configInfo' => $configInfo], 'jzOpen/handleNotGrabToReportLog', 1);
            return true;
        }
        $db_house_community_quality_bind_repair_cate = new HouseCommunityQualityBindRepairCate();
        $whereBindCate = [];
        $village_id && $whereBindCate[] = ['village_id', '=', $village_id];
        if (isset($orderInfo['cat_fid']) && $orderInfo['cat_fid']) {
            $whereBindCate[] = ['repair_cate_fid', '=', $orderInfo['cat_fid']];
        }
        $whereBindCate[] = ['repair_cate_id', '=', $orderInfo['cat_id']];
        $repairCateInfo = $db_house_community_quality_bind_repair_cate->getOne($whereBindCate, 'not_timely_report_switch');
        $cate_not_timely_report_switch = isset($repairCateInfo['not_timely_report_switch']) ? intval($repairCateInfo['not_timely_report_switch']) : 0;
        if ($cate_not_timely_report_switch == 1) {
            fdump_api(['err' => '[启用维修基金开启了]不上报督查室', 'params' => $params, 'configInfo' => $configInfo], 'jzOpen/handleNotGrabToReportLog', 1);
            return true;
        }
        
        $over_timely_report_time = isset($configInfo['over_timely_report_time']) ? intval($configInfo['over_timely_report_time']) : 0;
        $noticeId = isset($noticeInfo['id']) ? intval($noticeInfo['id']) : 0;
        if (!$over_timely_report_time) {
            fdump_api(['err' => '通知时间置空了-不进行推送', 'params' => $params, 'configInfo' => $configInfo], 'jzOpen/handleNotGrabToReportLog', 1);
            $update = ['notice_status' => 2, 'notice_reason' => '通知时间置空了（不进行推送）'];
            $dbHouseCommunityWorkOrderNotice->updateThis(['id' => $noticeId], $update);
            return true;
        }
        $nowTime = time();
        if ($over_timely_report_time > $nowTime + 600) {
            // 实际通知时间在1分钟以后 拦截
            fdump_api(['err' => '通知时间延后执行了-不进行推送', 'params' => $params, 'configInfo' => $configInfo], 'jzOpen/handleNotGrabToReportLog', 1);
            $update = ['notice_status' => 2, 'notice_reason' => '通知时间延后执行了（不进行推送）'];
            $dbHouseCommunityWorkOrderNotice->updateThis(['id' => $noticeId], $update);
            return true;
        }
        $dbHouseNewRepairWorksOrderLog = new HouseNewRepairWorksOrderLog();
        $log_name = 'over_timely_report';
        $whereOrderLog = [
            'order_id' => $works_order_id,
            'log_name' => $log_name,
        ];
        $record = $dbHouseNewRepairWorksOrderLog->getOne($whereOrderLog, 'log_id');
        if (isset($record['log_id']) && intval($record['log_id']) > 0) {
            fdump_api(['err' => '已经上报过督查室', 'params' => $params, 'configInfo' => $configInfo, 'log_id' => $record['log_id']], 'jzOpen/handleNotGrabToReportLog', 1);
            $update = ['notice_status' => 2, 'notice_reason' => '已经上报过督查室（不进行推送）'];
            $dbHouseCommunityWorkOrderNotice->updateThis(['id' => $noticeId], $update);
            return true;
        }
        $whereLastLog = [
            'order_id' => $works_order_id,
        ];
        $lastRecord = $dbHouseNewRepairWorksOrderLog->getOne($whereLastLog);
        $operator_id = isset($lastRecord['operator_id']) ? intval($lastRecord['operator_id']) : 0;
        $nowTime = time();
        $log_content = "工单未接单处理，上报督查室";
        $log_data = [
            'order_id' => $works_order_id,
            'log_name' => $log_name,
            'village_id' => isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : '',
            'property_id' => isset($orderInfo['property_id']) ? intval($orderInfo['property_id']) : '',
            'log_operator' => '系统自动上报督查室',
            'log_phone' => '',
            'operator_type' => 30,
            'operator_id' => $operator_id,
            'log_uid' => '',
            'log_content' => $log_content,
            'log_imgs' => '',
            'add_time' => $nowTime,
        ];
        $log_data['log_num'] = 1;
        $log_data['project_id'] = $orderInfo['project_id'];
        $log_data['rule_id'] = $orderInfo['rule_id'];
        $log_data['charge_price'] = 0;
        $log_data['pay_type'] = 0;
        $log_data['change_reason'] = '工单未接单处理';
        $sHouseNewRepairService = new HouseNewRepairService();
        $log_id = $sHouseNewRepairService->addRepairLog($log_data);
        if ($log_id) {
            $updateOrder = ['mark_report' => 1, 'mark_type' => 'notGrab', 'mark_time' => time()];
            $dbHouseNewRepairWorksOrder->saveOne(['order_id' => $works_order_id], $updateOrder);
            $dbHouseCommunitySupervisionConfig = new HouseCommunitySupervisionConfig();
            $where = [];
            $where[]  = ['notice_switch', '=', 1];
            $where[]  = ['notice_admin_id', '>', 0];
            $where[]  = ['notice_admin_from', '=', 'admin'];
            $config = $dbHouseCommunitySupervisionConfig->getOne($where);
            if ($config && !is_array($config)) {
                $config = $config->toArray();
            }
            if (empty($config)) {
                fdump_api(['err' => '督查室未设置被推送管理员-不进行推送', 'params' => $params, 'configInfo' => $configInfo, 'log_id' => $log_id], 'jzOpen/handleNotGrabToReportLog', 1);
                return true;
            }
            $dbAdmin = new Admin();
            $adminUser = $dbAdmin->getUserById($config['notice_admin_id']);
            if ($adminUser && !is_array($adminUser)) {
                $adminUser = $adminUser->toArray();
            }
            if (!isset($adminUser['openid']) || !$adminUser['openid']) {
                fdump_api(['err' => '督查室设置被推送管理员未绑定微信-不进行推送', 'params' => $params, 'configInfo' => $configInfo, 'log_id' => $log_id, 'adminUser' => $adminUser], 'jzOpen/handleRejectOverToReportLog', 1);
                return true;
            }
            $dbHouseNewRepairCate = new HouseNewRepairCate();
            $catFid = isset($orderInfo['cat_fid']) ? intval($orderInfo['cat_fid']) : 0;
            $catFidInfo = $dbHouseNewRepairCate->getOne(['id' => $catFid], 'cate_name');
            $catId = isset($orderInfo['cat_id']) ? intval($orderInfo['cat_id']) : 0;
            $catInfo = $dbHouseNewRepairCate->getOne(['id' => $catId], 'cate_name');
            $catFidCateName = isset($catFidInfo['cate_name']) ? trim($catFidInfo['cate_name']) : '';
            $catCateName = isset($catInfo['cate_name']) ? trim($catInfo['cate_name']) : '';

            $villageInfo = (new HouseVillage())->getOne($village_id, 'village_name');
            $village_name = isset($villageInfo['village_name']) ? trim($villageInfo['village_name']) : '';
            // 项目名称：thing22 工单类型：thing3 工单状态：thing10 处理时间：time11 工单地址：thing20 处理原因：thing14
            // thing 长度限制20
            $thing22 = $village_name;
            $thing3 = $catFidCateName . '/' . $catCateName;
            if (mb_strlen($thing3) > 15) {
                $thing3 = msubstr($thing3, 0, 15);
            }
            $sHouseNewRepairService = new HouseNewRepairService();
            $thing10 = isset($orderInfo['event_status']) ? $sHouseNewRepairService->order_work_status_txt[$orderInfo['event_status']] : '无';
            $update_time = isset($orderInfo['update_time']) ? intval($orderInfo['update_time']) : 0;
            !$update_time && $update_time = isset($orderInfo['add_time']) ? intval($orderInfo['add_time']) : $nowTime;
            $time11 = date('H:i', $update_time);
            $single_id = isset($orderInfo['single_id']) ? intval($orderInfo['single_id']) : 0;
            $floor_id = isset($orderInfo['floor_id']) ? intval($orderInfo['floor_id']) : 0;
            $layer_id = isset($orderInfo['layer_id']) ? intval($orderInfo['layer_id']) : 0;
            $room_id = isset($orderInfo['room_id']) ? intval($orderInfo['room_id']) : 0;
            $village_id = isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : 0;
            if ($single_id > 0 && $floor_id > 0 && $layer_id > 0 && $room_id > 0 && $village_id > 0) {
                $service_house_village = new HouseVillageService();
                $address = $service_house_village->getSingleFloorRoom($single_id, $floor_id, $layer_id, $room_id, $village_id);
                $address = $address ? $address : '无';
            } else {
                $address = $orderInfo['address_txt'] ?: '无';
            }
            $villageInfo = (new HouseVillage())->getOne($village_id, 'village_id, village_name, property_id');
            $villageAddress = isset($villageInfo['village_name']) ? trim($villageInfo['village_name']) . ' ' : '';
            $address = $villageAddress . $address;

            $thing20 = $address;
            $thing14 = $log_content;
            $href = '';
            $msgArr = [
                'href' => $href,
                'wecha_id' => $adminUser['openid'],
                'first' => '工单未接单处理',
                'keyword1' => $thing3,
                'keyword2' => '已发送',
                'keyword3' => date('H时i分', $_SERVER['REQUEST_TIME']),
                'remark' => '请点击查看详细信息！',
                'new_info' => [//新版本发送需要的信息
                    'tempKey' => '43030',//新模板号
                    'thing22' => $thing22,//项目名称
                    'thing3' => $thing3,//工单类型
                    'thing10' => $thing10,//工单状态
                    'time11' => $time11,//处理时间
                    'thing20' => $thing20,//工单地址
                    'thing14' => $thing14,//处理原因
                ],
            ];
            // 项目名称：thing22 工单类型：thing3 工单状态：thing10 处理时间：time11 工单地址：thing20 处理原因：thing14
            $TemplateNewsService=new TemplateNewsService();
            $TemplateNewsService->sendTempMsg('OPENTM400166399', $msgArr);//todo 类目模板OPENTM400166399
        }
        return true;
    }
    /**
     * 接单不处理上报督查室.
     */
    protected function handleOverNotProcessedToReport($params)
    {
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $works_order_id = isset($params['works_order_id']) ? intval($params['works_order_id']) : 0;
        $notice_type = isset($params['notice_type']) ? trim($params['notice_type']) : '';
        $doJobId = isset($params['doJobId']) ? trim($params['doJobId']) : '';
        $do_notice_time = isset($params['do_notice_time']) ? intval($params['do_notice_time']) : 0;
        $dbHouseCommunityWorkOrderNotice = new HouseCommunityWorkOrderNotice();
        $whereNotice = [];
        $village_id && $whereNotice[] = ['village_id', '=', $village_id];
        if ($works_order_id && $notice_type) {
            $whereNotice[] = ['works_order_id', '=', $works_order_id];
            $whereNotice[] = ['notice_type', '=', $notice_type];
        } else {
            $whereNotice[] = ['doJobId', '=', $doJobId];
        }
        if ($do_notice_time > 0) {
            $whereNotice[] = ['notice_time', '<=', $do_notice_time];
            $order = "notice_time DESC";
        } else {
            $order = "notice_time ASC";
        }
        $noticeInfo = $dbHouseCommunityWorkOrderNotice->getOne($whereNotice, true, $order);
        if ($noticeInfo && !is_array($noticeInfo)) {
            $noticeInfo = $noticeInfo->toArray();
        }
        if (empty($noticeInfo)) {
            fdump_api(['err' => '通知信息已经不存在了', 'params' => $params], 'jzOpen/handleOverNotProcessedToReportLog', 1);
            return true;
        }
        if (isset($noticeInfo['notice_status']) && intval($noticeInfo['notice_status']) != 0) {
            fdump_api(['err' => '通知已经执行或者关闭了', 'params' => $params, 'noticeInfo' => $noticeInfo], 'jzOpen/handleOverNotProcessedToReportLog', 1);
            return true;
        }
        $dbHouseNewRepairWorksOrder = new HouseNewRepairWorksOrder();
        $orderInfo = $dbHouseNewRepairWorksOrder->getOne(['order_id' => $works_order_id]);
        if ($orderInfo && !is_array($orderInfo)) {
            $orderInfo = $orderInfo->toArray();
        }
        if (empty($orderInfo)) {
            fdump_api(['err' => '对应工单不存在', 'params' => $params], 'jzOpen/handleOverNotProcessedToReportLog', 1);
            return true;
        }
        $dbHouseCommunityWorkOrderConfig = new HouseCommunityWorkOrderConfig();
        $whereConfig = [];
        !$village_id && $village_id = isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : 0;
        $village_id && $whereConfig['village_id'] = $village_id;
        $whereConfig['works_order_id'] = $works_order_id;
        $configInfo = $dbHouseCommunityWorkOrderConfig->getOne($whereConfig, 'id, works_order_id, village_id, over_not_processed_report_time, not_report_switch');
        if ($configInfo && !is_array($configInfo)) {
            $configInfo = $configInfo->toArray();
        }
        if (empty($configInfo)) {
            fdump_api(['err' => '没有相关配置不进行操作', 'params' => $params], 'jzOpen/handleOverNotProcessedToReportLog', 1);
            return true;
        }
        $over_not_processed_report_time = isset($configInfo['over_not_processed_report_time']) ? intval($configInfo['over_not_processed_report_time']) : 0;
        $noticeId = isset($noticeInfo['id']) ? intval($noticeInfo['id']) : 0;
        if (!$over_not_processed_report_time) {
            fdump_api(['err' => '通知时间置空了-不进行推送', 'params' => $params, 'configInfo' => $configInfo], 'jzOpen/handleOverNotProcessedToReportLog', 1);
            $update = ['notice_status' => 2, 'notice_reason' => '通知时间置空了（不进行推送）'];
            $dbHouseCommunityWorkOrderNotice->updateThis(['id' => $noticeId], $update);
            return true;
        }
        $nowTime = time();
        if ($over_not_processed_report_time > $nowTime + 600) {
            // 实际通知时间在1分钟以后 拦截
            fdump_api(['err' => '通知时间延后执行了-不进行推送', 'params' => $params, 'configInfo' => $configInfo], 'jzOpen/handleOverNotProcessedToReportLog', 1);
            $update = ['notice_status' => 2, 'notice_reason' => '通知时间延后执行了（不进行推送）'];
            $dbHouseCommunityWorkOrderNotice->updateThis(['id' => $noticeId], $update);
            return true;
        }
        $dbHouseNewRepairWorksOrderLog = new HouseNewRepairWorksOrderLog();
        $log_name = 'over_not_processed_report_time';
        $whereOrderLog = [
            'order_id' => $works_order_id,
            'log_name' => $log_name,
        ];
        $record = $dbHouseNewRepairWorksOrderLog->getOne($whereOrderLog, 'log_id');
        if (isset($record['log_id']) && intval($record['log_id']) > 0) {
            fdump_api(['err' => '已经上报过督查室', 'params' => $params, 'configInfo' => $configInfo, 'log_id' => $record['log_id']], 'jzOpen/handleOverNotProcessedToReportLog', 1);
            $update = ['notice_status' => 2, 'notice_reason' => '已经上报过督查室（不进行推送）'];
            $dbHouseCommunityWorkOrderNotice->updateThis(['id' => $noticeId], $update);
            return true;
        }
        //发模板消息
        $this->sendMessageToWork($works_order_id,'over_not_processed',$orderInfo);
        $not_report_switch = isset($configInfo['not_report_switch']) ? intval($configInfo['not_report_switch']) : 0;
        if ($not_report_switch == 1) {
            fdump_api(['err' => '不上报督查室', 'params' => $params, 'configInfo' => $configInfo], 'jzOpen/handleOverNotProcessedToReportLog', 1);
            return true;
        }
        $db_house_community_quality_bind_repair_cate = new HouseCommunityQualityBindRepairCate();
        $whereBindCate = [];
        $village_id && $whereBindCate[] = ['village_id', '=', $village_id];
        if (isset($orderInfo['cat_fid']) && $orderInfo['cat_fid']) {
            $whereBindCate[] = ['repair_cate_fid', '=', $orderInfo['cat_fid']];
        }
        $whereBindCate[] = ['repair_cate_id', '=', $orderInfo['cat_id']];
        $repairCateInfo = $db_house_community_quality_bind_repair_cate->getOne($whereBindCate, 'not_timely_report_switch');
        $cate_not_timely_report_switch = isset($repairCateInfo['not_timely_report_switch']) ? intval($repairCateInfo['not_timely_report_switch']) : 0;
        if ($cate_not_timely_report_switch == 1) {
            fdump_api(['err' => '[启用维修基金开启了]不上报督查室', 'params' => $params, 'configInfo' => $configInfo], 'jzOpen/handleOverNotProcessedToReportLog', 1);
            return true;
        }
        $whereLastLog = [
            'order_id' => $works_order_id,
        ];
        $lastRecord = $dbHouseNewRepairWorksOrderLog->getOne($whereLastLog);
        $operator_id = isset($lastRecord['operator_id']) ? intval($lastRecord['operator_id']) : 0;
        $nowTime = time();
        $log_content = "工单接单未及时处理，上报督查室";
        $village_id = isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : '';
        $property_id = isset($orderInfo['property_id']) ? intval($orderInfo['property_id']) : '';
        $log_data = [
            'order_id' => $works_order_id, 'log_name' => $log_name, 'village_id' => $village_id, 
            'property_id' => $property_id, 'log_operator' => '系统自动上报督查室', 'log_phone' => '', 'operator_type' => 30,
            'operator_id' => $operator_id, 'log_uid' => '', 'log_content' => $log_content, 'log_imgs' => '', 'add_time' => $nowTime,
        ];
        $log_data['log_num'] = 1;
        $log_data['project_id'] = $orderInfo['project_id'];
        $log_data['rule_id'] = $orderInfo['rule_id'];
        $log_data['charge_price'] = 0;
        $log_data['pay_type'] = 0;
        $log_data['change_reason'] = '工单接单未及时处理';
        $sHouseNewRepairService = new HouseNewRepairService();
        $log_id = $sHouseNewRepairService->addRepairLog($log_data);
        if ($log_id) {
            $updateOrder = ['mark_report' => 1, 'mark_type' => 'overNotProcessed', 'mark_time' => time()];
            $dbHouseNewRepairWorksOrder->saveOne(['order_id' => $works_order_id], $updateOrder);
        }
        return true;
    }

    /**
     * 收费工单确认超时处理box.
     */
    public function tryHandleOverFeeConfirmBox($params) {
        try {
            return $this->handleOverFeeConfirm($params);
        } catch (\Exception $e) {
            fdump_api(['err' => '异常捕捉盒子报错-接单不处理上报督查室', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandleOverFeeConfirmBoxLog', 1);
            return false;
        } catch (\Throwable $e) {
            fdump_api(['err' => '异常捕捉盒子报错-接单不处理上报督查室', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandleOverFeeConfirmBoxLog', 1);
            return false;
        } catch (\RuntimeException $e) {
            fdump_api(['err' => '异常捕捉盒子报错-接单不处理上报督查室', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandleOverFeeConfirmBoxLog', 1);
            return false;
        } catch (\InvalidArgumentException $e) {
            fdump_api(['err' => '异常捕捉盒子报错-接单不处理上报督查室', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHandleOverFeeConfirmBoxLog', 1);
            return false;
        }
    }
    
    public function handleOverFeeConfirm($params)
    {
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $works_order_id = isset($params['works_order_id']) ? intval($params['works_order_id']) : 0;
        $notice_type = isset($params['notice_type']) ? trim($params['notice_type']) : '';
        $doJobId = isset($params['doJobId']) ? trim($params['doJobId']) : '';
        $do_notice_time = isset($params['do_notice_time']) ? intval($params['do_notice_time']) : 0;
        $dbHouseCommunityWorkOrderNotice = new HouseCommunityWorkOrderNotice();
        $whereNotice = [];
        $village_id && $whereNotice[] = ['village_id', '=', $village_id];
        if ($works_order_id && $notice_type) {
            $whereNotice[] = ['works_order_id', '=', $works_order_id];
            $whereNotice[] = ['notice_type', '=', $notice_type];
        } else {
            $whereNotice[] = ['doJobId', '=', $doJobId];
        }
        if ($do_notice_time > 0) {
            $whereNotice[] = ['notice_time', '<=', $do_notice_time];
            $order = "notice_time DESC";
        } else {
            $order = "notice_time ASC";
        }
        $noticeInfo = $dbHouseCommunityWorkOrderNotice->getOne($whereNotice, true, $order);
        if ($noticeInfo && !is_array($noticeInfo)) {
            $noticeInfo = $noticeInfo->toArray();
        }
        if (empty($noticeInfo)) {
            fdump_api(['err' => '通知信息已经不存在了', 'params' => $params], 'jzOpen/handleOverFeeConfirmLog', 1);
            return true;
        }
        if (isset($noticeInfo['notice_status']) && intval($noticeInfo['notice_status']) != 0) {
            fdump_api(['err' => '通知已经执行或者关闭了', 'params' => $params, 'noticeInfo' => $noticeInfo], 'jzOpen/handleOverFeeConfirmLog', 1);
            return true;
        }
        $dbHouseNewRepairWorksOrder = new HouseNewRepairWorksOrder();
        $orderInfo = $dbHouseNewRepairWorksOrder->getOne(['order_id' => $works_order_id]);
        if ($orderInfo && !is_array($orderInfo)) {
            $orderInfo = $orderInfo->toArray();
        }
        if (empty($orderInfo)) {
            fdump_api(['err' => '对应工单不存在', 'params' => $params], 'jzOpen/handleOverFeeConfirmLog', 1);
            return true;
        }
        $dbHouseCommunityWorkOrderConfig = new HouseCommunityWorkOrderConfig();
        $whereConfig = [];
        !$village_id && $village_id = isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : 0;
        $whereConfig['village_id'] = $village_id;
        $whereConfig['works_order_id'] = $works_order_id;
        $configInfo = $dbHouseCommunityWorkOrderConfig->getOne($whereConfig, 'id, works_order_id, village_id, over_fee_confirm_operate_time, over_fee_confirm_operate');
        if ($configInfo && !is_array($configInfo)) {
            $configInfo = $configInfo->toArray();
        }
        if (empty($configInfo)) {
            fdump_api(['err' => '没有相关配置不进行操作', 'params' => $params], 'jzOpen/handleOverFeeConfirmLog', 1);
            return true;
        }
        $over_fee_confirm_operate_time = isset($configInfo['over_fee_confirm_operate_time']) ? intval($configInfo['over_fee_confirm_operate_time']) : 0;
        $noticeId = isset($noticeInfo['id']) ? intval($noticeInfo['id']) : 0;
        if (!$over_fee_confirm_operate_time) {
            fdump_api(['err' => '通知时间置空了-不进行推送', 'params' => $params, 'configInfo' => $configInfo], 'jzOpen/handleOverFeeConfirmLog', 1);
            $update = ['notice_status' => 2, 'notice_reason' => '通知时间置空了（不进行推送）'];
            $dbHouseCommunityWorkOrderNotice->updateThis(['id' => $noticeId], $update);
            return true;
        }
        $nowTime = time();
        if ($over_fee_confirm_operate_time > $nowTime + 600) {
            // 实际通知时间在1分钟以后 拦截
            fdump_api(['err' => '处理时间延后执行了-不进行推送', 'params' => $params, 'configInfo' => $configInfo], 'jzOpen/handleOverFeeConfirmLog', 1);
            $update = ['notice_status' => 2, 'notice_reason' => '通知时间延后执行了（不进行推送）'];
            $dbHouseCommunityWorkOrderNotice->updateThis(['id' => $noticeId], $update);
            return true;
        }
        if (isset($orderInfo['event_status']) && intval($orderInfo['event_status']) != 35) {
            fdump_api(['err' => '工单状态不是工单待确认-不进行推送', 'orderInfo' => $orderInfo, 'params' => $params, 'configInfo' => $configInfo], 'jzOpen/handleOverFeeConfirmLog', 1);
            $update = ['notice_status' => 2, 'notice_reason' => '工单状态不是工单待确认（不进行推送）'];
            $dbHouseCommunityWorkOrderNotice->updateThis(['id' => $noticeId], $update);
            return true;
        }
        
        $over_fee_confirm_operate = isset($configInfo['over_fee_confirm_operate']) ? intval($configInfo['over_fee_confirm_operate']) : 0;
        $text = $over_fee_confirm_operate == 0 ? "系统自动结束工单" : '系统自动同意工单';
        $log_name = 'over_fee_confirm_operate';
        $nowTime = time();
        $log_content = "工单收费业主未及时确认，" . $text;
        $village_id = isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : '';
        $property_id = isset($orderInfo['property_id']) ? intval($orderInfo['property_id']) : '';
        $log_data = [
            'order_id' => $works_order_id, 'log_name' => $log_name, 'village_id' => $village_id,
            'property_id' => $property_id, 'log_operator' => $text, 'log_phone' => '', 'operator_type' => 30,
            'operator_id' => 0, 'log_uid' => '', 'log_content' => $log_content, 'log_imgs' => '', 'add_time' => $nowTime,
        ];
        $log_data['log_num'] = 1;
        $log_data['project_id'] = $orderInfo['project_id'];
        $log_data['rule_id'] = $orderInfo['rule_id'];
        $log_data['charge_price'] = 0;
        $log_data['pay_type'] = 0;
        $log_data['change_reason'] = $log_content;
        $sHouseNewRepairService = new HouseNewRepairService();
        $log_id = $sHouseNewRepairService->addRepairLog($log_data);
        if ($log_id) {
            if ($over_fee_confirm_operate == 1) {
                // 系统自动同意工单
                $updateOrder = ['event_status' => 36, 'update_time' => $nowTime];
            } else {
                // 系统自动结束工单
                $updateOrder = ['now_role' => 1, 'event_status' => 60, 'update_time' => $nowTime];
            }
            $dbHouseNewRepairWorksOrder->saveOne(['order_id' => $works_order_id], $updateOrder);
            if ($over_fee_confirm_operate == 1) {
                $sHouseNewRepairService->newRepairSendMsg(80, $works_order_id, 0, $property_id);
                $stepArr = ['title' => '用户同意缴费', 'type' => 'text', 'content' => '用户同意了缴费，记录自动结单时间', 'time' => $nowTime];
                (new HouseCommunityWorkOrderService())->setAutoEndTime($works_order_id, $stepArr);
            } else {
                $sHouseNewRepairService->newRepairSendMsg(81, $works_order_id, 0, $property_id);
                $stepArr = ['title' => '用户关闭了工单', 'type' => 'text', 'content' => '用户关闭了工单，去除自动结单时间', 'time' => $nowTime];
                (new HouseCommunityWorkOrderService())->setAutoEndTime($works_order_id, $stepArr);
            }
        }
        return true;
    }
}