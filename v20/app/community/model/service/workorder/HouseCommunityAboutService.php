<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      物业和小区相关信息
 */
namespace app\community\model\service\workorder;

use app\community\model\db\HouseProperty;
use app\community\model\db\HouseVillage;
use app\community\model\db\workorder\HouseCommunityAbout;
use app\community\model\db\workorder\HouseCommunityAboutBind;
use app\community\model\db\workorder\HouseCommunityConfig;
use app\community\model\db\workorder\HouseCommunitySupervisionConfig;
use app\community\model\db\workorder\HouseCommunityWorkOrderConfig;
use app\community\model\db\workorder\HouseCommunityWorkSign;
use app\community\model\db\workorder\HouseCommunityWorkSignLog;
use app\consts\WorkOrderConst;

class HouseCommunityAboutService
{
    public function getFacilitiesTypeSwitch() {
        return [
            ['label' => '供暖站', 'value' => 'supply_station'],
            ['label' => '开发商', 'value' => 'developer'],
        ];
    }

    public function getFacilitiesTypeText($value) {
        $typeArr = array_column($this->getFacilitiesTypeSwitch(), 'label', 'value');
        return isset($typeArr[$value]) ? $typeArr[$value] : '';
    }

    /**
     * 获取对应小区设施记录.
     */
    public function getVillageFacilitiesInfo($village_id, $facilities_type = 'supply_station') {
        $where = [
            'village_id' => $village_id,
            'facilities_type' => $facilities_type,
            'delete_time' => 0,
        ];
        $db_house_community_about = new HouseCommunityAbout();
        return $db_house_community_about->getOne($where);
    }
    
    public function getVillageBindFacilitiesList($village_id, $param = [], $field = 'id, property_id, village_id, bind_facilities_type, bind_village_id, bind_time') {
        $bind_facilities_type = isset($param['bind_facilities_type']) ? trim($param['bind_facilities_type']) : '';
        $order_id = isset($param['order_id']) ? trim($param['order_id']) : 0;
        if ($order_id > 0) {
            $dbHouseCommunityWorkOrderConfig = new HouseCommunityWorkOrderConfig();
            $whereConfig = [];
            $whereConfig['village_id'] = $village_id;
            $whereConfig['works_order_id'] = $order_id;
            $configInfo = $dbHouseCommunityWorkOrderConfig->getOne($whereConfig, 'id, works_order_id, village_id');
            if (!isset($configInfo['id']) || empty($configInfo)) {
                // 没有相关配置项记录的走原始逻辑
                return [];
            }
        }
        $where = [
            'village_id' => $village_id,
        ];
        $bind_facilities_type && $where['bind_facilities_type'] = $bind_facilities_type;
        $db_house_community_about_bind = new HouseCommunityAboutBind();
        $list =  $db_house_community_about_bind->getList($where, $field);
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        if (!empty($list)) {
            $bindVillageIds = array_column($list, 'bind_village_id');
            $dbHouseVillage = new HouseVillage();
            $whereVillage = [];
            $whereVillage[] = ['village_id', 'in', $bindVillageIds];
            $whereVillage[] = ['status', 'in', [0,1]];
            $villageInfo = $dbHouseVillage->getColumn($whereVillage, 'village_name', 'village_id');
            $data = [];
            $db_house_community_about = new HouseCommunityAbout();
            $whereAbout = [];
            $whereAbout[] = ['village_id', 'in', $bindVillageIds];
            $whereAbout[] = ['is_virtual', '=', 1];
            $aboutArr = $db_house_community_about->getColumn($whereAbout, 'facilities_type, is_contact_available', 'village_id');
            
            $house_community_config = new HouseCommunityConfig();
            $whereConfig = [];
            $whereConfig[] = ['village_id', 'in', $bindVillageIds];
            $notToArr = $house_community_config->getColumn($whereConfig, 'id,not_to_facilities,auto_contact_available', 'village_id');
            
            $db_house_village = new HouseVillage();
            $whereVillage = [];
            $whereVillage[] = ['village_id', 'in', $bindVillageIds];
            $villageArr = $db_house_village->getColumn($whereVillage, 'village_id, village_name, property_phone, property_address', 'village_id');
            foreach ($list as $item) {
                $bind_village_id = isset($item['bind_village_id']) ? intval($item['bind_village_id']) : 0;
                if (!isset($villageArr[$bind_village_id])) {
                    // 过滤已经被删除的小区
                    continue;
                }
                $notToInfo = isset($notToArr[$bind_village_id]) ? $notToArr[$bind_village_id] : [];
                if (isset($notToInfo['not_to_facilities']) && intval($notToInfo['not_to_facilities']) == 1) {
                    // 过滤禁止转单的
                    continue;
                }
                $villageInfo = isset($villageArr[$bind_village_id]) ? $villageArr[$bind_village_id] : [];
                $aboutInfo = isset($aboutArr[$bind_village_id]) ? $aboutArr[$bind_village_id] : [];
                $is_contact_available = isset($aboutInfo['is_contact_available']) ? intval($aboutInfo['is_contact_available']) : 0;
                $auto_contact_available = isset($notToInfo['auto_contact_available']) ? intval($notToInfo['auto_contact_available']) : 0;
                $bind_facilities_type = isset($item['bind_facilities_type']) ? trim($item['bind_facilities_type']) : '';
                if ($bind_facilities_type == 'supply_station') {
                    // 供暖站 默认自动 且不可改
                    $auto_contact_available = 1;
                }
                $item['is_contact_available'] = $is_contact_available;
                $item['auto_contact_available'] = $auto_contact_available;
                if ($is_contact_available == 0 && $auto_contact_available != 1) {
                    $item['villageInfo'] = $villageInfo;
                }
                $bind_village_name = isset($villageInfo['village_name']) ? trim($villageInfo['village_name']) : '';
                $bind_facilities_name = $this->getFacilitiesTypeText($bind_facilities_type);
                $item['bind_village_name'] = $bind_village_name;
                $item['bind_facilities_name'] = $bind_facilities_name;
                $item['to_village_name'] = '转'.$bind_facilities_name . "($bind_village_name)";
                $item['contact_available_log'] = '获取联系' . "($bind_village_name)";
                $data[] = $item;
            }
            $list = $data;
        }
        return $list;
    }
    
    public function getHouseCommunityAboutBind($where, $field = 'id, property_id, village_id, bind_facilities_type, bind_village_id, bind_time', $params = []) 
    {
        $db_house_community_about_bind = new HouseCommunityAboutBind();
        $list =  $db_house_community_about_bind->getList($where, $field);
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        $getVillageName = isset($params['getVillageName']) ? intval($params['getVillageName']) : 0;
        if (!empty($list) && $getVillageName == 1) {
            $villageIdArr = array_unique(array_column($list,'bind_village_id'));
            $dbHouseVillage = new HouseVillage();
            $whereVillage = [];
            $whereVillage[] = ['village_id', 'in', $villageIdArr];
            $villageIdInfo = $dbHouseVillage->getColumn($whereVillage, 'village_name', 'village_id');
            foreach ($list as &$item) {
                $village_id = isset($item['bind_village_id']) ? intval($item['bind_village_id']) : 0;
                $village_name = isset($villageIdInfo[$village_id]) ? trim($villageIdInfo[$village_id]) : '';
                $item['bind_village_name'] = $village_name;
            }
        }
        return $list;
    }

    /**
     * 获取对应小区配置.
     */
    public function getHouseCommunityConfig($village_id, $field = '*') {
        $where = [];
        $where[] = ['village_id', '=', $village_id];
        $db_house_community_config = new HouseCommunityConfig();
        $configInfo = $db_house_community_config->getOne($where, $field);
        if ($configInfo && !is_array($configInfo)) {
            $configInfo = $configInfo->toArray();
        }
        if (empty($configInfo)) {
            $configInfo = [
                'village_id' => $village_id, 'auto_dispatch_order' => '0', 'is_build_work_order' => '0',
                'is_pic_video' =>'0', 'not_to_facilities' => '0', 'not_receive_interval' => '0',
                'work_id' => '0', 'auto_end_bill_order' => '0', 'auto_end_interval' => '0',
                'sign_in_type' => '0', 'refuse_order_nums_report' => '0', 'not_over_stars_report' => '0',
                'over_timely_report' => '0', 'over_not_processed_report' => '0', 'order_recipient_work_id' => '0',
                'over_fee_confirm_interval' => '0', 'over_fee_confirm_operate' => '0', 'refuse_project_nums_report' => '0',
                'over_not_processed_work_id'=>'0'
            ];
        }
        $configInfo['not_receive_work_id'] = isset($configInfo['work_id']) ? intval($configInfo['work_id']) : 0;
        return $configInfo;
    }

    /**
     * 小区配置项.
     */
    public function addHouseCommunityConfig($params) {
        $from = isset($params['from']) ? trim($params['from']) : '';
        $from_user = isset($params['from_user']) ? $params['from_user'] : '';
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $where = [];
        $where[] = ['village_id', '=', $village_id];
        $db_house_community_config = new HouseCommunityConfig();
        $configInfo = $db_house_community_config->getOne($where);
        $data = [
            'village_id' => $village_id
        ];
        // 查询下是否归属供暖站
        $is_supply_station = 0;
        $supplyInfo = $this->getVillageFacilitiesInfo($village_id);
        if (isset($supplyInfo['is_virtual']) && intval($supplyInfo['is_virtual']) == 1) {
            $is_supply_station = 1;
        }
        if (isset($params['auto_dispatch_order']) && $is_supply_station == 1) {
            $is_grab_order = isset($params['is_grab_order']) ? intval($params['is_grab_order']) : 0;
            $auto_dispatch_order = intval($params['auto_dispatch_order']);
            if ($is_grab_order == 1) {
                // 自动抢单开启了 自动派单自动关闭
                $auto_dispatch_order = 0;
            }
            $data['auto_dispatch_order'] = $auto_dispatch_order;
        } elseif($is_supply_station != 1) {
            // 非供暖站的 默认关闭自动派单
            $data['auto_dispatch_order'] = 0;
        }
        if (isset($params['is_build_work_order'])) {
            $data['is_build_work_order'] = intval($params['is_build_work_order']);
        }
        if (isset($params['is_pic_video'])) {
            $data['is_pic_video'] = intval($params['is_pic_video']);
        }
        if (isset($params['not_to_facilities'])) {
            $data['not_to_facilities'] = intval($params['not_to_facilities']);
        }
        if (isset($params['not_receive_interval'])) {
            $data['not_receive_interval'] = intval($params['not_receive_interval']);
        }
        if (isset($params['not_receive_work_id'])) {
            $data['work_id'] = intval($params['not_receive_work_id']);
        }
        if (isset($params['over_not_processed_work_id'])) {
            $data['over_not_processed_work_id'] = intval($params['over_not_processed_work_id']);
        }
        if (isset($params['auto_end_bill_order'])) {
            $data['auto_end_bill_order'] = intval($params['auto_end_bill_order']);
        }
        if (isset($params['auto_end_interval'])) {
            $data['auto_end_interval'] = intval($params['auto_end_interval']);
        }
        if (isset($params['sign_in_type'])) {
            $data['sign_in_type'] = intval($params['sign_in_type']);
        }
        if (isset($params['refuse_order_nums_report'])) {
            $data['refuse_order_nums_report'] = intval($params['refuse_order_nums_report']);
        }
        if (isset($params['not_over_stars_report'])) {
            $data['not_over_stars_report'] = intval($params['not_over_stars_report']);
        }
        if (isset($params['not_timely_report_switch'])) {
            if (intval($params['not_timely_report_switch'])==1 || strval($params['not_timely_report_switch']) === 'true') {
                $not_timely_report_switch = 1;
            } else {
                $not_timely_report_switch = 0;
            }
            $data['not_timely_report_switch'] = $not_timely_report_switch;
        }
        if (isset($params['over_timely_report'])) {
            $data['over_timely_report'] = intval($params['over_timely_report']);
        }
        if (isset($params['over_not_processed_report'])) {
            $data['over_not_processed_report'] = intval($params['over_not_processed_report']);
        }
        if (isset($params['order_recipient_work_id'])) {
            $data['order_recipient_work_id'] = intval($params['order_recipient_work_id']);
        }
        if (isset($params['over_fee_confirm_interval'])) {
            $data['over_fee_confirm_interval'] = intval($params['over_fee_confirm_interval']);
        }
        if (isset($params['over_fee_confirm_operate'])) {
            $data['over_fee_confirm_operate'] = intval($params['over_fee_confirm_operate']);
        }
        if (isset($params['refuse_project_nums_report'])) {
            $data['refuse_project_nums_report'] = intval($params['refuse_project_nums_report']);
        }
        if (isset($params['auto_contact_available'])) {
            $data['auto_contact_available'] = intval($params['auto_contact_available']);
        }
        if ($from) {
            $data['from'] = $from;
        }
        if ($from_user) {
            if (is_array($from_user)) {
                $from_user = json_encode($from_user,JSON_UNESCAPED_UNICODE);
            }
            $data['from_user'] = $from_user;
        }
        if (isset($configInfo['id']) && $configInfo['id']) {
            $data['update_time'] = time();
            $db_house_community_config->updateThis($where, $data);
        } else {
            $data['add_time'] = time();
            $db_house_community_config->add($data);
        }
        return $params;
    }

    /**
     * 获取当前人员签到信息.
     */
    public function getSignInfo($params) {
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $login_role = isset($params['login_role']) ? intval($params['login_role']) : 0;
        $worker_id = isset($params['worker_id']) ? intval($params['worker_id']) : 0;
        $login_phone = isset($params['login_phone']) ? trim($params['login_phone']) : '';
        $last_sign_type = isset($params['last_sign_type']) ? intval($params['last_sign_type']) : -1;
        $nowTime = time();
        
        $dbHouseCommunityWorkSign = new HouseCommunityWorkSign();
        $whereSign = [];
        $whereSign[] = ['village_id', '=', $village_id];
        if ($worker_id) {
            $whereSign[] = ['wid', '=', $worker_id];
        } else {
            $whereSign[] = ['phone', '=', $login_phone];
        }
        if ($last_sign_type != -1) {
            $whereSign[] = ['last_sign_type', '=', $last_sign_type];
            // 获取签到记录时候 直接查询未失效的打卡签到记录
            $last_sign_type == 0 && $whereSign[] = ['sign_end_time', '>', time()];
        }
        $signField = "id, property_id, village_id, wid, last_sign_type, last_sign_time, last_sign_address, sign_start_time, sign_end_time";
        $signInfo = $dbHouseCommunityWorkSign->getOne($whereSign, $signField);
        if ($signInfo && !is_array($signInfo)) {
            $signInfo = $signInfo->toArray();
        }
        if (isset($signInfo['last_sign_time']) && $signInfo['last_sign_time']) {
            $signInfo['sign_time_text'] = date('Y-m-d H:i:s', $signInfo['last_sign_time']);
        }
        empty($signInfo) && $signInfo = [];
        return $signInfo;
    }
    
    /**
     * 签到或者签退.
     */
    public function workSign($params) {
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $login_role = isset($params['login_role']) ? intval($params['login_role']) : 0;
        $worker_id = isset($params['worker_id']) ? intval($params['worker_id']) : 0;
        $login_phone = isset($params['login_phone']) ? trim($params['login_phone']) : '';
        $signType = isset($params['signType']) ? trim($params['signType']) : '';
        $sign_address = isset($params['sign_address']) ? trim($params['sign_address']) : '';
        $long = isset($params['long']) ? floatval($params['long']) : 0;
        $lat = isset($params['lat']) ? floatval($params['lat']) : 0;
        $uid = isset($params['uid']) ? intval($params['uid']) : 0;
        if (!$worker_id && !$login_phone) {
            throw new \think\Exception("缺少身份无法进行签到");
        }
        $params = [
            'worker_id' => $worker_id, 'login_role' => $login_role, 'village_id' => $village_id,
            'login_phone' => $login_phone, 'property_id' => $property_id, 'last_sign_type' => 0,
        ];
        $sign = $this->getSignInfo($params);
        if ($signType == WorkOrderConst::WORK_ORDER_SIGN_TYPE_IN && !empty($sign)) {
            throw new \think\Exception("已经打卡签到过，请重新进入签到页面查看");
        }
        if ($signType == WorkOrderConst::WORK_ORDER_SIGN_TYPE_OUT && empty($sign)) {
            throw new \think\Exception("未打卡签到过，请重新进入签到先行打卡签到");
        }
        $signId = isset($sign['id']) ? intval($sign['id']) : 0;
        $nowTime = time();
        $configInfo = $this->getHouseCommunityConfig($village_id);
        // 工作人员签到时效 0-24小时（0点前） 1-24小时
        $sign_in_type = isset($configInfo['sign_in_type']) ? intval($configInfo['sign_in_type']) : 0;
        if (!$signType && empty($sign)) {
            $signType = WorkOrderConst::WORK_ORDER_SIGN_TYPE_IN;
        }
        if (!$signType && !empty($sign)) {
            $signType = WorkOrderConst::WORK_ORDER_SIGN_TYPE_OUT;
        }
        $signText = '';
        $last_sign_time = 0;
        $sign_start_time = 0;
        switch ($signType) {
            case WorkOrderConst::WORK_ORDER_SIGN_TYPE_IN:
                // 最新签到类型 0-签到
                $last_sign_type = 0;
                $last_sign_time = $nowTime;
                $sign_start_time = $nowTime;
                if ($sign_in_type == 0) {
                    // 时效 24小时（0点前）
                    $sign_end_time = strtotime(date('Y-m-d 23:59:59', $nowTime));
                } else {
                    $sign_end_time = $nowTime + 86399;
                }
                $signText = '签到';
                break;
            case WorkOrderConst::WORK_ORDER_SIGN_TYPE_OUT:
                $judge_sign_start_time = isset($sign['sign_start_time']) ? intval($sign['sign_start_time']) : 0;
                $judge_times = $nowTime - $judge_sign_start_time;
                $workOrderSignTimes = WorkOrderConst::WORK_ORDER_SIGN_TIMES;
                if ($judge_times < $workOrderSignTimes) {
                    throw new \think\Exception("签退和最新签到之间时间间隔不能少于{$workOrderSignTimes}秒");
                }
                // 最新签到类型 1-签退
                $last_sign_type = 1;
                $last_sign_time = $nowTime;
                $sign_end_time = $nowTime;
                $signText = '签退';
                break;
            default:
                throw new \think\Exception("未打卡签到过，请重新进入签到先行打卡签到");
                break;
        }
        $signData = [];
        $signData['property_id'] = $property_id;
        $signData['village_id'] = $village_id;
        $signData['wid'] = $worker_id;
        $uid && $signData['uid'] = $uid;
        $login_phone && $signData['phone'] = $login_phone;
        $signData['last_sign_type'] = $last_sign_type;
        $signData['last_sign_time'] = $last_sign_time;
        $sign_address &&  $signData['last_sign_address'] = $sign_address;
        $sign_start_time &&  $signData['sign_start_time'] = $sign_start_time;
        $sign_end_time &&  $signData['sign_end_time'] = $sign_end_time;

        $dbHouseCommunityWorkSign = new HouseCommunityWorkSign();
        if ($signId > 0) {
            $signData['update_time'] = $nowTime;
            $dbHouseCommunityWorkSign->updateThis(['id' => $signId], $signData);
        } else {
            $signData['add_time'] = $nowTime;
            $signId = $dbHouseCommunityWorkSign->add($signData);
        }
        if (!$signId) {
            throw new \think\Exception("{$signText}失败");
        }
        $signLog = [];
        $signLog['sign_id'] = $signId;
        $signLog['property_id'] = $property_id;
        $signLog['village_id'] = $village_id;
        $signLog['wid'] = $worker_id;
        $uid && $signLog['uid'] = $uid;
        $login_phone && $signLog['phone'] = $login_phone;
        $signLog['sign_type'] = $last_sign_type;
        $signLog['sign_time'] = $nowTime;
        $signLog['sign_day'] = date('YmdHis', $nowTime);
        if (floatval($long) > 0 && floatval($lat) > 0) {
            $signLog['long'] = $long;
            $signLog['lat'] = $lat;
        }
        $sign_address &&  $signLog['sign_address'] = $sign_address;
        $configInfo &&  $signLog['sign_config'] = json_encode($configInfo,JSON_UNESCAPED_UNICODE);
        $dbHouseCommunityWorkSignLog = new HouseCommunityWorkSignLog();
        $whereLastTime = [];
        $whereLastTime[] = ['village_id', '=', $village_id];
        if ($worker_id) {
            $whereLastTime[] = ['wid', '=', $worker_id];
        } else {
            $whereLastTime[] = ['phone', '=', $login_phone];
        }
        $last_day_time = $nowTime - 86399;
        $last_sign_day = date('YmdHis', $last_day_time);
        $whereLastTime[] = ['sign_day', '=', $last_sign_day];
        $lastTimeRecord = $dbHouseCommunityWorkSignLog->getOne($whereLastTime, 'id, days', 'sign_time ASC');
        $lastDays = isset($lastTimeRecord['days']) ? intval($lastTimeRecord['days']) : 0;
        $signLog['days'] = $lastDays + 1;
        return $dbHouseCommunityWorkSignLog->add($signLog);
    }

    /**
     * 获取督查室配置项
     */
    public function getSupervisionConfig($params) {
        $dbHouseCommunitySupervisionConfig = new HouseCommunitySupervisionConfig();
        $where = [];
        $where[]  = ['notice_admin_from', '=', 'admin'];
        $config = $dbHouseCommunitySupervisionConfig->getOne($where);
        if ($config && !is_array($config)) {
            $config = $config->toArray();
        }
        empty($config) && $config = [];
        return $config;
    }

    /**
     * 设置督查室配置项
     */
    public function setSupervisionConfig($params) {
        $config = $this->getSupervisionConfig($params);
        $notice_switch = isset($params['notice_switch']) ? intval($params['notice_switch']) : 2;
        $notice_admin_id = isset($params['notice_admin_id']) ? intval($params['notice_admin_id']) : 0;
        $notice_admin_from = isset($params['notice_admin_from']) ? trim($params['notice_admin_from']) : 'admin';
        $from_user = isset($params['from_user']) ? $params['from_user'] : '';
        $data = [
            'notice_switch' => $notice_switch,
            'notice_admin_id' => $notice_admin_id,
            'notice_admin_from' => $notice_admin_from,
        ];
        if ($from_user) {
            if (is_array($from_user)) {
                $from_user = json_encode($from_user,JSON_UNESCAPED_UNICODE);
            }
            $data['from_user'] = $from_user;
        }
        $dbHouseCommunitySupervisionConfig = new HouseCommunitySupervisionConfig();
        if (isset($config['id']) && $config['id']) {
            $data['update_time'] = time();
            $configId = $config['id'];
            $dbHouseCommunitySupervisionConfig->updateThis(['id' => $configId], $data);
        } else {
            $data['add_time'] = time();
            $configId = $dbHouseCommunitySupervisionConfig->add($data);
        }
        return $configId;
    }
}