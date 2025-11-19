<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      工单相关
 */

namespace app\community\model\service\workorder;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\db\HouseNewRepairWorksOrder;
use app\community\model\db\HouseNewRepairWorksOrderLog;
use app\community\model\db\HouseServicesHousekeeper;
use app\community\model\db\HouseWorker;
use app\community\model\db\workorder\HouseCommunityQualityBindRepairCate;
use app\community\model\db\workorder\HouseCommunityWorkOrderConfig;
use app\community\model\db\workorder\HouseCommunityWorkOrderNotice;
use app\community\model\db\workorder\HouseCommunityWorkOrderWorkRecord;
use app\community\model\db\workorder\HouseCommunityWorkSign;
use app\community\model\service\HouseNewRepairService;
use app\community\model\service\HouseVillageService;
use app\consts\HouseWorkConst;
use app\consts\WorkOrderConst;
use think\facade\Cache;
use think\queue\command\Retry;

class HouseCommunityWorkOrderService
{
    public function getWorkOrderToWorker($params) {
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $configInfo = isset($params['configInfo']) ? $params['configInfo'] : [];
        if (empty($configInfo) && $village_id) {
            $configInfo = (new HouseCommunityAboutService())->getHouseCommunityConfig($village_id);
        }
        !$village_id && $village_id = isset($configInfo['village_id']) ? intval($configInfo['village_id']) : 0;
        $assign_record = [];
        $toWorkId = 0;
        $nowTime = time();
        if (!$village_id || empty($configInfo)) {
            $assign_record['toWorkId'] = $toWorkId;
            $assign_record['communityAutoToWork'] = ['title' => '小区配置进行自动指派', 'msg' => '未进行相关配置', 'time' => $nowTime];
            return $assign_record;
        }
        $assign_record['communityConfig'] = $configInfo;
        $is_supply_station = isset($params['is_supply_station']) ? intval($params['is_supply_station']) : 0;
        if (!isset($params['is_supply_station'])) {
            $supplyInfo = (new HouseCommunityAboutService())->getVillageFacilitiesInfo($village_id);
            if (isset($supplyInfo['is_virtual']) && intval($supplyInfo['is_virtual']) == 1) {
                // 是供暖站
                $is_supply_station = 1;
            }
        }
        $cat_id = isset($params['cat_id']) ? intval($params['cat_id']) : 0;
        $toSingleId = isset($params['toSingleId']) ? intval($params['toSingleId']) : 0;
        $bind_id = isset($params['bind_id']) ? intval($params['bind_id']) : 0;
//        $not_timely_report_switch = isset($configInfo['not_timely_report_switch']) ? intval($configInfo['not_timely_report_switch']) : 0;
        $not_timely_report_switch = 0;

        $bindCateConfig = $this->getQualityBindRepairCate($cat_id, $village_id);
        $assign_record['repairCateConfig'] = $bindCateConfig;

        $not_report_switch = 0;
        if (isset($bindCateConfig['not_timely_report_switch']) && intval($bindCateConfig['not_timely_report_switch']) == 1) {
            $not_report_switch = 1;
        }
        if ($not_timely_report_switch == 1) {
            $not_report_switch = 1;
        }
        $assign_record['not_report_switch'] = $not_report_switch;
        
        // 先判断下相关优先级配置是否开启
        // 自动派单 0-关闭 1-启用
        $auto_dispatch_order = isset($configInfo['auto_dispatch_order']) ? intval($configInfo['auto_dispatch_order']) : 0;
        // 如果不是供暖站 自动指派 自动处于关闭 因为不支持24小时签到
        $is_supply_station != 1 && $auto_dispatch_order = 0;
        // 楼栋管家接受工单 0-不接受 1-接受
        $is_build_work_order = isset($configInfo['is_build_work_order']) ? intval($configInfo['is_build_work_order']) : 0;
        if ($auto_dispatch_order == 0 && $is_build_work_order == 0) {
            // 定制的开关都没有开启走原来逻辑
            $assign_record['toWorkId'] = $toWorkId;
            $assign_record['communityAutoToWork'] = ['title' => '小区配置进行自动指派', 'msg' => '[自动指派]和[楼栋管家接受工单]开关均未开启', 'time' => $nowTime];
            return $assign_record;
        }

        $dbHouseCommunityWorkSign = new HouseCommunityWorkSign();
        if ($is_build_work_order == 1 && $toSingleId) {
            // 优先适配楼栋管家
            $whereButler = [
                'village_id' => $village_id, 'single_id' => $toSingleId, 'floor_id' => 0,
            ];
            $buildingButler = (new HouseServicesHousekeeper())->getOne($whereButler, true, 'housekeeper_id asc');
            if ($buildingButler && $buildingButler->work_arr) {
                $work_arr = explode(',', $buildingButler->work_arr);
            } else {
                $work_arr = [];
            }
            if (!empty($work_arr)) {
                // 过滤已经删除工作人员
                $whereWorks = [];
                $whereWorks[] = ['wid', 'in', $work_arr];
                $whereWorks[] = ['is_del', '=', 0];
                $work_arr = (new HouseWorker())->getColumn($whereWorks, 'wid');
                if (!$work_arr) {
                    $work_arr = [];
                }
            }
            if (empty($work_arr)) {
                // 无对应楼栋管家 且未开启自动派单 直接返回走原逻辑
                $assign_record['toWorkId'] = $toWorkId;
                $assign_record['communityAutoToWork'] = ['title' => '[楼栋管家接受工单]开启进行自动指派', 'msg' => '无楼栋管家且，不指派', 'time' => $nowTime];
                $assign_record['errCode'] = WorkOrderConst::WORK_ORDER_ERR_CODE_NOT_BUILDER;
                $assign_record['errMsg'] = '未设置楼栋管家，不能提交工单请先联系物业！';
                $service_house_village = new HouseVillageService();
                $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$village_id],'village_name,community_id,property_phone');
                $property_phone = isset($village_info['property_phone']) ? trim($village_info['property_phone']) : '';
                $telephone = '';
                if ($property_phone) {
                    $phoneArr = explode(' ', $property_phone);
                    if (empty($phoneArr)) {
                        $telephone = trim($property_phone);
                    }
                    foreach ($phoneArr as $phone) {
                        if ($phone) {
                            $telephone = $phone;
                        }
                    }
                }
                $assign_record['telephone'] = $telephone;
                return $assign_record;
            }
            if ($is_supply_station != 1) {
                // 非供暖站 随意指派一个楼栋管家
                if(count($work_arr) > 1) {
                    $index = 0;
//                    $index = array_rand($work_arr);
                    $toWorkId = $work_arr[$index];
                } else {
                    $index = 0;
                    $toWorkId = reset($work_arr);
                }
                $assign_record['toWorkId'] = $toWorkId;
                $assign_record['communityAutoToWork'] = ['title' => '[楼栋管家接受工单]开启进行自动指派', 'msg' => '存在符合条件的楼栋管家自动随机指派', 'rand_index' => $index, 'to_work_arr' => $work_arr, 'time' => $nowTime];
                return $assign_record;
            }
            if (!empty($work_arr)) {
                $whereSign = [];
                $whereSign[] = ['village_id', '=', $village_id];
                $whereSign[] = ['wid', 'in', $work_arr];
                $whereSign[] = ['sign_start_time', '<', $nowTime];
                $whereSign[] = ['sign_end_time', '>', $nowTime];
                $signList = $dbHouseCommunityWorkSign->getList($whereSign, 'id, village_id, wid, sign_start_time, sign_end_time', 'last_sign_time ASC');
                if ($signList && !is_array($signList)) {
                    $signList = $signList->toArray();
                }
                if (empty($signList)) {
                    // 存在楼栋管家但是 均未进行有效签到 且自动派单未开启 随意指派一个
                    if(count($work_arr) > 1) {
                        $index = 0;
//                        $index = array_rand($work_arr);
                        $toWorkId = $work_arr[$index];
                    } else {
                        $index = 0;
                        $toWorkId = reset($work_arr);
                    }
                    $assign_record['toWorkId'] = $toWorkId;
                    $assign_record['communityAutoToWork'] = [
                        'title' => '[楼栋管家接受工单]开启进行自动指派', 'msg' => '无有效签到的楼栋管家且[自动派单]关闭，随机指派一个楼栋管家',
                        'singleWorks' => $work_arr, 'randIndex' => $index, 'time' => $nowTime
                    ];
                    return $assign_record;
                }
                if (!empty($signList)) {
                    $widSignArr = array_column($signList, 'wid');
                    if (isset($bindCateConfig['not_timely_report_switch']) && intval($bindCateConfig['not_timely_report_switch']) == 1) {
                        // 如果是对应分类 上报督查室 禁止（“上报”则需要进行上报，“关闭”则不限制时间，不进行上报；）
                        $signInfo = reset($signList);
                        $toWorkId = $signInfo['wid'];
                        $assign_record['toWorkId'] = $toWorkId;
                        $assign_record['communityAutoToWork'] = [
                            'title' => '[楼栋管家接受工单]开启进行自动指派', 'msg' => '分类开启[启用维修基金]，优先分配最先签到的楼栋管家', 'singleWorks' => $work_arr,
                            'signWorks' => $widSignArr, 'bindCateConfig' => $bindCateConfig, 'time' => $nowTime
                        ];
                        return $assign_record;
                    }
                    if ($not_timely_report_switch == 1) {
                        // 如果是 限时上报督查室 关闭（“上报”按照【工单超时设置进行上报】，“关闭”则不限制时间，不进行上报；）
                        $signInfo = reset($signList);
                        $toWorkId = $signInfo['wid'];
                        $assign_record['toWorkId'] = $toWorkId;
                        $village_config = [
                            'village_id' => $village_id, 'auto_dispatch_order' => $auto_dispatch_order,
                            'is_build_work_order' => $is_build_work_order, 'not_timely_report_switch' => $not_timely_report_switch,
                        ];
                        $assign_record['communityAutoToWork'] = [
                            'title' => '[楼栋管家接受工单]开启进行自动指派', 'msg' => '小区关闭[限时上报督查室]，优先分配最先签到的楼栋管家', 'singleWorks' => $work_arr,
                            'signWorks' => $widSignArr, 'configInfo' => $village_config, 'time' => $nowTime
                        ];
                        return $assign_record;
                    }
                    $recordList = $this->getOrderAbleWorks($village_id, $widSignArr);
                    if (empty($recordList)) {
                        // 楼栋管家均无未结单工单在手 直接指派签到人员
                        $signInfo = reset($signList);
                        $toWorkId = $signInfo['wid'];
                        $assign_record['toWorkId'] = $toWorkId;
                        $assign_record['communityAutoToWork'] = [
                            'title' => '[楼栋管家接受工单]开启进行自动指派', 'msg' => '已经签到的楼栋管家均无未结单工单，优先分配最先签到的楼栋管家', 
                            'singleWorks' => $work_arr, 'signWorks' => $widSignArr, 'time' => $nowTime
                        ];
                        return $assign_record;
                    }
                    $widRecordArr = array_column($recordList, 'worker_id');
                    $notOrderArr = array_diff($widSignArr, $widRecordArr);
                    if (!empty($notOrderArr)) {
                        // 存在未结单工单在手的楼栋管家 直接指派对应按签到顺序的楼栋管家
                        $toWorkId = reset($notOrderArr);
                        $assign_record['toWorkId'] = $toWorkId;
                        $assign_record['communityAutoToWork'] = [
                            'title' => '[楼栋管家接受工单]开启进行自动指派', 'msg' => '存在已经签到的楼栋管家无未结单工单，优先分配其中最先签到的楼栋管家',
                            'singleWorks' => $work_arr, 'signWorks' => $widSignArr, 'hasWorks' => $widRecordArr, 'time' => $nowTime
                        ];
                        return $assign_record;
                    }
                    // 签到的楼栋管家都有未结单工单在手 按照拥有数量少 然后接单时间早的顺序 进行指派
                    $toWorkId = reset($widRecordArr);
                    $assign_record['toWorkId'] = $toWorkId;
                    $assign_record['communityAutoToWork'] = [
                        'title' => '[楼栋管家接受工单]开启进行自动指派', 'msg' => '均有工单，优先分配工单数量少且接单时间早的楼栋管家',
                        'singleWorks' => $work_arr, 'signWorks' => $widSignArr, 'hasWorks' => $widRecordArr, 'time' => $nowTime
                    ];
                    return $assign_record;
                }
            }
        }
        if ($auto_dispatch_order == 1) {
            // 开启了 自动派单
            $whereSign = [];
            $whereSign[] = ['village_id', '=', $village_id];
            $whereSign[] = ['sign_start_time', '<', $nowTime];
            $whereSign[] = ['sign_end_time', '>', $nowTime];
            $signList = $dbHouseCommunityWorkSign->getList($whereSign, 'id, village_id, wid, sign_start_time, sign_end_time', 'last_sign_time ASC');
            if ($signList && !is_array($signList)) {
                $signList = $signList->toArray();
            }
            if (empty($signList)) {
                // 无对进行有效签到的工作人员
                $assign_record['toWorkId'] = $toWorkId;
                $assign_record['communityAutoToWork'] = ['title' => '[自动派单]开启进行自动指派', 'msg' => '无有效签到工作人员，不指派', 'time' => $nowTime];
                return $assign_record;
            }
            if (!empty($signList)) {
                $widSignArr = array_column($signList, 'wid');
                if (isset($bindCateConfig['not_timely_report_switch']) && intval($bindCateConfig['not_timely_report_switch']) == 1) {
                    // 如果是对应分类 上报督查室 禁止（“上报”则需要进行上报，“关闭”则不限制时间，不进行上报；）
                    $signInfo = reset($signList);
                    $toWorkId = $signInfo['wid'];
                    $assign_record['toWorkId'] = $toWorkId;
                    $assign_record['communityAutoToWork'] = [
                        'title' => '[自动派单]开启进行自动指派', 'msg' => '分类开启[启用维修基金]，优先分配最先签到的工作人员',
                        'signWorks' => $widSignArr, 'bindCateConfig' => $bindCateConfig, 'time' => $nowTime
                    ];
                    return $assign_record;
                }
                if ($not_timely_report_switch == 1) {
                    // 如果是 限时上报督查室 关闭（“上报”按照【工单超时设置进行上报】，“关闭”则不限制时间，不进行上报；）
                    $signInfo = reset($signList);
                    $toWorkId = $signInfo['wid'];
                    $assign_record['toWorkId'] = $toWorkId;
                    $village_config = [
                        'village_id' => $village_id, 'auto_dispatch_order' => $auto_dispatch_order,
                        'is_build_work_order' => $is_build_work_order, 'not_timely_report_switch' => $not_timely_report_switch,
                    ];
                    $assign_record['communityAutoToWork'] = [
                        'title' => '[自动派单]开启进行自动指派', 'msg' => '小区关闭[限时上报督查室]，优先分配最先签到的工作人员',
                        'signWorks' => $widSignArr, 'configInfo' => $village_config, 'time' => $nowTime
                    ];
                    return $assign_record;
                }
                $recordList = $this->getOrderAbleWorks($village_id, $widSignArr);
                if (empty($recordList)) {
                    // 工作人员均无未结单工单在手 直接指派签到人员
                    $signInfo = reset($signList);
                    $toWorkId = $signInfo['wid'];
                    $assign_record['toWorkId'] = $toWorkId;
                    $assign_record['communityAutoToWork'] = [
                        'title' => '[自动派单]开启进行自动指派', 'msg' => '已经签到的工作人员均无未结单工单，优先分配最先签到的工作人员',
                        'signWorks' => $widSignArr, 'time' => $nowTime
                    ];
                    return $assign_record;
                }
                $widRecordArr = array_column($recordList, 'worker_id');
                $notOrderArr = array_diff($widSignArr, $widRecordArr);
                if (!empty($notOrderArr)) {
                    // 存在未结单工单在手的工作人员 直接指派对应按签到顺序的工作人员
                    $toWorkId = reset($notOrderArr);
                    $assign_record['toWorkId'] = $toWorkId;
                    $assign_record['communityAutoToWork'] = [
                        'title' => '[自动派单]开启进行自动指派', 'msg' => '存在已经签到的工作人员无未结单工单，优先分配其中最先签到的工作人员',
                        'signWorks' => $widSignArr, 'hasWorks' => $widRecordArr, 'time' => $nowTime
                    ];
                    return $assign_record;
                }
                // 签到的工作人员都有未结单工单在手 按照拥有数量少 然后接单时间早的顺序 进行指派
                $toWorkId = reset($widRecordArr);
                $assign_record['toWorkId'] = $toWorkId;
                $assign_record['communityAutoToWork'] = [
                    'title' => '[自动派单]开启进行自动指派', 'msg' => '均有工单，优先分配工单数量少且接单时间早的工作人员',
                    'signWorks' => $widSignArr, 'hasWorks' => $widRecordArr, 'time' => $nowTime
                ];
                return $assign_record;
            }
        }
        // 无符合条件指派人员
        $assign_record['toWorkId'] = $toWorkId;
        $assign_record['communityAutoToWork'] = ['title' => '小区配置进行自动指派', 'msg' => '无符合条件指派人员，不指派', 'time' => $nowTime];
        return $assign_record;
    }

    public function toOtherVillageWorker($params) {
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $operator_id = isset($params['operator_id']) ? intval($params['operator_id']) : 0;
        $configInfo = isset($params['configInfo']) ? $params['configInfo'] : [];
        if (empty($configInfo) && $village_id) {
            $configInfo = (new HouseCommunityAboutService())->getHouseCommunityConfig($village_id);
        }
        !$village_id && $village_id = isset($configInfo['village_id']) ? intval($configInfo['village_id']) : 0;
        $assign_record = [];
        $toWorkId = 0;
        $nowTime = time();
        if (!$village_id || empty($configInfo)) {
            $assign_record['toWorkId'] = $toWorkId;
            $assign_record['workToOtherVillage'] = ['title' => '指派到供暖站或开发商进行自动分配', 'msg' => '未进行相关配置', 'configInfo' => $configInfo, 'village_id' => $village_id, 'operator_id' => $operator_id, 'time' => $nowTime];
            return $assign_record;
        }
        $is_supply_station = isset($params['is_supply_station']) ? intval($params['is_supply_station']) : 0;
        if (!isset($params['is_supply_station'])) {
            $supplyInfo = (new HouseCommunityAboutService())->getVillageFacilitiesInfo($village_id);
            if (isset($supplyInfo['is_virtual']) && intval($supplyInfo['is_virtual']) == 1) {
                // 是供暖站
                $is_supply_station = 1;
            }
        }
        if ($is_supply_station == 0 && isset($configInfo['order_recipient_work_id']) && intval($configInfo['order_recipient_work_id']) > 0) {
            // 开发商 配置了[接单员] 直接分配给[接单员]
            $assign_record['toWorkId'] = intval($configInfo['order_recipient_work_id']);
            $assign_record['workToOtherVillage'] = ['title' => '指派到开发商自动分配接单员', 'msg' => '小区配置了[接单员]', 'configInfo' => $configInfo, 'village_id' => $village_id, 'operator_id' => $operator_id, 'time' => $nowTime];
            return $assign_record;
        }
        // 先判断下相关优先级配置是否开启
        // 自动派单 0-关闭 1-启用
        $auto_dispatch_order = isset($configInfo['auto_dispatch_order']) ? intval($configInfo['auto_dispatch_order']) : 0;
        // 如果不是供暖站 自动指派 自动处于关闭 因为不支持24小时签到
//        $not_timely_report_switch = isset($configInfo['not_timely_report_switch']) ? intval($configInfo['not_timely_report_switch']) : 0;
        $not_timely_report_switch = 0;// 目前开关已经弃用 变成了 公共区域维修不限制时效 所以这里默认上报 不进行拦截
        $is_supply_station != 1 && $auto_dispatch_order = 0;
        $dbHouseCommunityWorkSign = new HouseCommunityWorkSign();
        if ($auto_dispatch_order == 1) {
            // 开启了 自动派单
            $whereSign = [];
            $whereSign[] = ['village_id', '=', $village_id];
            $whereSign[] = ['sign_start_time', '<', $nowTime];
            $whereSign[] = ['sign_end_time', '>', $nowTime];
            $signList = $dbHouseCommunityWorkSign->getList($whereSign, 'id, village_id, wid, sign_start_time, sign_end_time', 'last_sign_time ASC');
            if ($signList && !is_array($signList)) {
                $signList = $signList->toArray();
            }
            if (empty($signList)) {
                // 无对进行有效签到的工作人员
                $assign_record['toWorkId'] = $toWorkId;
                $assign_record['workToOtherVillage'] = ['title' => '[自动派单]开启进行自动指派', 'msg' => '无有效签到工作人员，不指派', 'operator_id' => $operator_id, 'time' => $nowTime];
                return $assign_record;
            }
            if (!empty($signList)) {
                $widSignArr = array_column($signList, 'wid');
                if (isset($bindCateConfig['not_timely_report_switch']) && intval($bindCateConfig['not_timely_report_switch']) == 1) {
                    // 如果是对应分类 上报督查室 禁止（“上报”则需要进行上报，“关闭”则不限制时间，不进行上报；）
                    $signInfo = reset($signList);
                    $toWorkId = $signInfo['wid'];
                    $assign_record['toWorkId'] = $toWorkId;
                    $assign_record['workToOtherVillage'] = [
                        'title' => '[自动派单]开启进行自动指派', 'msg' => '分类开启[启用维修基金]，优先分配最先签到的工作人员',
                        'signWorks' => $widSignArr, 'bindCateConfig' => $bindCateConfig, 'operator_id' => $operator_id, 'time' => $nowTime
                    ];
                    return $assign_record;
                }
                if ($not_timely_report_switch == 1) {
                    // 如果是 限时上报督查室 关闭（“上报”按照【工单超时设置进行上报】，“关闭”则不限制时间，不进行上报；）
                    $signInfo = reset($signList);
                    $toWorkId = $signInfo['wid'];
                    $assign_record['toWorkId'] = $toWorkId;
                    $village_config = [
                        'village_id' => $village_id, 'auto_dispatch_order' => $auto_dispatch_order, 'not_timely_report_switch' => $not_timely_report_switch,
                    ];
                    $assign_record['workToOtherVillage'] = [
                        'title' => '[自动派单]开启进行自动指派', 'msg' => '小区关闭[限时上报督查室]，优先分配最先签到的工作人员',
                        'signWorks' => $widSignArr, 'configInfo' => $village_config, 'operator_id' => $operator_id, 'time' => $nowTime
                    ];
                    return $assign_record;
                }
                $recordList = $this->getOrderAbleWorks($village_id, $widSignArr);
                if (empty($recordList)) {
                    // 工作人员均无未结单工单在手 直接指派签到人员
                    $signInfo = reset($signList);
                    $toWorkId = $signInfo['wid'];
                    $assign_record['toWorkId'] = $toWorkId;
                    $assign_record['workToOtherVillage'] = [
                        'title' => '[自动派单]开启进行自动指派', 'msg' => '已经签到的工作人员均无未结单工单，优先分配最先签到的工作人员',
                        'signWorks' => $widSignArr, 'operator_id' => $operator_id, 'time' => $nowTime
                    ];
                    return $assign_record;
                }
                $widRecordArr = array_column($recordList, 'worker_id');
                $notOrderArr = array_diff($widSignArr, $widRecordArr);
                if (!empty($notOrderArr)) {
                    // 存在未结单工单在手的工作人员 直接指派对应按签到顺序的工作人员
                    $toWorkId = reset($notOrderArr);
                    $assign_record['toWorkId'] = $toWorkId;
                    $assign_record['workToOtherVillage'] = [
                        'title' => '[自动派单]开启进行自动指派', 'msg' => '存在已经签到的工作人员无未结单工单，优先分配其中最先签到的工作人员',
                        'signWorks' => $widSignArr, 'hasWorks' => $widRecordArr, 'operator_id' => $operator_id, 'time' => $nowTime
                    ];
                    return $assign_record;
                }
                // 签到的工作人员都有未结单工单在手 按照拥有数量少 然后接单时间早的顺序 进行指派
                $toWorkId = reset($widRecordArr);
                $assign_record['toWorkId'] = $toWorkId;
                $assign_record['workToOtherVillage'] = [
                    'title' => '[自动派单]开启进行自动指派', 'msg' => '均有工单，优先分配工单数量少且接单时间早的工作人员',
                    'signWorks' => $widSignArr, 'hasWorks' => $widRecordArr, 'operator_id' => $operator_id, 'time' => $nowTime
                ];
                return $assign_record;
            }
        }
        // 无符合条件指派人员
        $assign_record['toWorkId'] = $toWorkId;
        $assign_record['workToOtherVillage'] = ['title' => '小区配置进行自动指派', 'msg' => '无符合条件指派人员，不指派', 'operator_id' => $operator_id, 'time' => $nowTime];
        return $assign_record;
    }
    
    /**
     * 获取分类绑定的质保和配置信息.
     */
    public function getQualityBindRepairCate($cat_id, $village_id) {
        $dbHouseCommunityQualityBindRepairCate = new HouseCommunityQualityBindRepairCate();
        $whereBind = [];
        $whereBind[] = ['village_id', '=', $village_id];
        $whereBind[] = ['repair_cate_id', '=', $cat_id];
        $field = 'id, village_id, single_id, repair_cate_id, repair_cate_fid, quality_id, quality_start_time, quality_end_time, status, not_timely_report_switch';
        $bindCate = $dbHouseCommunityQualityBindRepairCate->getOne($whereBind, $field, 'delete_time ASC');
        if ($bindCate && !is_array($bindCate)) {
            $bindCate = $bindCate->toArray();
        }
        return $bindCate;
    }

    /**
     * 清除对应缓存.
     */
    public function clearRedisByCacheKey($cacheKey) {
        return Cache::store('redis')->delete($cacheKey);
    }

    /**
     * 按照不上报工单未结单总数和最新结单时间排序获取相关信息.
     */
    public function getOrderAbleWorks($village_id, $widArr) {
        $dbHouseCommunityWorkOrderWorkRecord = new HouseCommunityWorkOrderWorkRecord();
        $where = [];
        //$where[] = ['village_id', '=', $village_id];
        $where[] = ['worker_id', 'in', $widArr];
        $workList = $dbHouseCommunityWorkOrderWorkRecord->getList($where,true, 'not_completed_report_num ASC, latest_receive_time ASC');
        if ($workList) {
            $workList = $workList->toArray();
        }
        return $workList;
    }

    /**
     * 整理记录对应工作人员
     */
    public function handleWorkOrderWorkRecord($params) {
        $worker_id = isset($params['worker_id']) ? intval($params['worker_id']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $works_order_id = isset($params['works_order_id']) ? intval($params['works_order_id']) : 0;
        $worker_phone = isset($params['worker_phone']) ? trim($params['worker_phone']) : '';
        $latest_receive_time = isset($params['latest_receive_time']) ? intval($params['latest_receive_time']) : time();
        if (!$worker_id || !$village_id || !$works_order_id) {
            return false;
        }
        
        $dbHouseNewRepairWorksOrder = new HouseNewRepairWorksOrder();
        $whereWorkOrder = [];
        //$whereWorkOrder[] = ['village_id', '=', $village_id];
        $whereWorkOrder[] = ['worker_id', '=', $worker_id];
        $whereWorkOrder[] = ['event_status', '>=', 20];
        $whereWorkOrder[] = ['event_status', '<', 40];
        $orderIds = $dbHouseNewRepairWorksOrder->getColumn($whereWorkOrder, 'order_id');
        if (!empty($orderIds)) {
            $dbHouseCommunityWorkOrderConfig = new HouseCommunityWorkOrderConfig();
            $whereConfig = [];
            //$whereConfig[] = ['village_id', '=', $village_id];
            $whereConfig[] = ['works_order_id', 'in', $orderIds];
            $orderConfigs = $dbHouseCommunityWorkOrderConfig->getList($whereConfig, 'id, works_order_id, not_report_switch');
            if ($orderConfigs && !is_array($orderConfigs)) {
                $orderConfigs = $orderConfigs->toArray();
            }
            // 未结单（需要按要求上报的）工单数
            $not_completed_report_num = 0;
            // 未结单（需要按要求上报的）工单id数组集合
            $not_completed_report_order_ids = [];
            // 未结单工单数（总的）
            $not_completed_num = 0;
            // 未结单工单（总的）工单id数组集合
            $not_completed_order_ids = [];
            foreach ($orderConfigs as $config) {
                $order_id = isset($config['works_order_id']) ? intval($config['works_order_id']) : 0;
                if (!isset($config['not_report_switch']) || intval($config['not_report_switch']) == 0) {
                    // 是否上报督查室 1-不上报 0-上报
                    ++$not_completed_report_num;
                    $not_completed_report_order_ids[] = $order_id;
                } else {
                    ++$not_completed_num;
                    $not_completed_order_ids[] = $order_id;
                }
            }
        } else {
            $not_completed_report_num = 0;
            $not_completed_report_order_ids = [];
            $not_completed_num = 0;
            $not_completed_order_ids = [];
        }
        
        $recordData = [
            'worker_id' => $worker_id,
            'village_id' => $village_id,
            'property_id' => $property_id,
            'latest_receive_time' => $latest_receive_time,
            'latest_order_id' => $works_order_id,
            'not_completed_report_num' => $not_completed_report_num,
            'not_completed_num' => $not_completed_num,
        ];
        if (!empty($not_completed_report_order_ids)) {
            $recordData['not_completed_report_order_ids'] = json_encode($not_completed_report_order_ids,JSON_UNESCAPED_UNICODE);
        } else {
            $recordData['not_completed_report_order_ids'] = '';
        }
        if (!empty($not_completed_order_ids)) {
            $recordData['not_completed_order_ids'] = json_encode($not_completed_order_ids,JSON_UNESCAPED_UNICODE);
        } else {
            $recordData['not_completed_order_ids'] = '';
        }
        $worker_phone && $recordData['worker_phone'] = $worker_phone;

        $dbHouseCommunityWorkOrderWorkRecord = new HouseCommunityWorkOrderWorkRecord();
        $whereRecord = [];
        $whereRecord[] = ['village_id', '=', $village_id];
        $whereRecord[] = ['worker_id', '=', $worker_id];
        $recordInfo = $dbHouseCommunityWorkOrderWorkRecord->getOne($whereRecord, 'id');
        if (isset($recordInfo['id']) && intval($recordInfo['id']) > 0) {
            $recordData['update_time'] = time();
            $recordId = $recordInfo['id'];
            $dbHouseCommunityWorkOrderWorkRecord->updateThis(['id' => $recordId], $recordData);
        } else {
            $recordData['add_time'] = time();
            $recordId = $dbHouseCommunityWorkOrderWorkRecord->add($recordData);
        }
        return $recordId;
    }
    
    public function addHouseCommunityWorkOrderConfig($data) {
        $dbHouseCommunityWorkOrderConfig = new HouseCommunityWorkOrderConfig();
        return $dbHouseCommunityWorkOrderConfig->add($data);
    }

    /**
     * 业主同意收费订单时候自动结单时间设置.
     */
    public function setAutoEndTime($works_order_id, $stepArr, $agree_time = 0, $village_id = 0, $configId = 0) {
        $dbHouseCommunityWorkOrderConfig = new HouseCommunityWorkOrderConfig();
        $whereConfig = [];
        if ($village_id) {
            $whereConfig['village_id'] = $village_id;
        }
        if ($configId) {
            $whereConfig['id'] = $configId;
        } else {
            $whereConfig['works_order_id'] = $works_order_id;
        }
        $configInfo = $dbHouseCommunityWorkOrderConfig->getOne($whereConfig, 'id, works_order_id, village_id, auto_end_bill_order, auto_end_interval, auto_end_text');
        $fillData = [];
        $auto_end_bill_order = isset($configInfo['auto_end_bill_order']) && $configInfo['auto_end_bill_order'] ? intval($configInfo['auto_end_bill_order']) : 0;
        $auto_end_interval = isset($configInfo['auto_end_interval']) && $configInfo['auto_end_interval'] ? intval($configInfo['auto_end_interval']) : 0;
        if ($auto_end_bill_order != 1 || $auto_end_interval == 0) {
            return true;
        }
        if (!empty($stepArr)) {
            $auto_end_text = isset($configInfo['auto_end_text']) && $configInfo['auto_end_text'] ? $configInfo['auto_end_text'] : '';
            if ($auto_end_text) {
                $auto_end_text_arr = json_decode($auto_end_text, true);
            } else {
                $auto_end_text_arr = [];
            }
            $step = isset($notice_text_arr['step']) ? $notice_text_arr['step'] : [];
            $step[] = $stepArr;
            $auto_end_text_arr['step'] = $step;
            $fillData['auto_end_text'] = json_encode($auto_end_text_arr,JSON_UNESCAPED_UNICODE);
        }
        $nowTime = time();
        !$agree_time && $agree_time = $nowTime;
        if ($auto_end_bill_order == 1 && $auto_end_interval > 0 && $agree_time > 0) {
            $auto_end_time = $agree_time + $auto_end_interval * 3600;
            $fillData['auto_end_time'] = $auto_end_time;
            $fillData['update_time'] = $nowTime;
            // todo 走下记录对应推送 只要没有结单就推送
            !$village_id && $village_id = isset($configInfo['village_id']) ? intval($configInfo['village_id']) : 0;
            !$works_order_id && $works_order_id = isset($configInfo['works_order_id']) ? intval($configInfo['works_order_id']) : 0;
            $noticeArr = [
                'village_id' => $village_id, 'works_order_id' => $works_order_id, 'is_job' => 1,
                'notice_type' => WorkOrderConst::WORK_ORDER_AUTO_END, 'notice_time' => $auto_end_time,
            ];
            (new HouseCommunityHandleNoticeService())->tryCatchHandleRecordNoticeBox($noticeArr);
            return $dbHouseCommunityWorkOrderConfig->updateThis($whereConfig, $fillData);
        } else {
            return true;
        }
    }

    /**
     * 清空自动结单时间.
     */
    public function clearAutoEndTime($works_order_id, $stepArr, $village_id = 0, $configId = 0) {
        $dbHouseCommunityWorkOrderConfig = new HouseCommunityWorkOrderConfig();
        $whereConfig = [];
        if ($village_id) {
            $whereConfig[] = ['village_id', '=', $village_id];
        }
        if ($configId) {
            $whereConfig[] = ['id', '=', $configId];
        } else {
            $whereConfig[] = ['works_order_id', '=', $works_order_id];
        }
        $whereConfig[] = ['auto_end_time', '>', 0];
        $configInfo = $dbHouseCommunityWorkOrderConfig->getOne($whereConfig, 'id, works_order_id, village_id, auto_end_text, auto_end_time');
        if (!isset($configInfo['id']) || !$configInfo['id']) {
            return false;
        }
        $fillData = [];
        if (!empty($stepArr)) {
            $auto_end_text = isset($configInfo['auto_end_text']) && $configInfo['auto_end_text'] ? $configInfo['auto_end_text'] : '';
            if ($auto_end_text) {
                $auto_end_text_arr = json_decode($auto_end_text, true);
            } else {
                $auto_end_text_arr = [];
            }
            $step = isset($notice_text_arr['step']) ? $notice_text_arr['step'] : [];
            $step[] = $stepArr;
            $auto_end_text_arr['step'] = $step;
            $fillData['auto_end_text'] = json_encode($auto_end_text_arr,JSON_UNESCAPED_UNICODE);
        }
        $nowTime = time();
        $fillData['auto_end_time'] = 0;
        $fillData['update_time'] = $nowTime;
        
        // todo 清理对应未执行操作
        !$village_id && $village_id = isset($configInfo['village_id']) ? intval($configInfo['village_id']) : 0;
        !$works_order_id && $works_order_id = isset($configInfo['works_order_id']) ? intval($configInfo['works_order_id']) : 0;
        $noticeArr = [
            'village_id' => $village_id, 'works_order_id' => $works_order_id,
            'notice_type' => WorkOrderConst::WORK_ORDER_AUTO_END,
        ];
        (new HouseCommunityHandleNoticeService())->tryCatchClearHandleRecordNoticeBox($noticeArr);
        return $dbHouseCommunityWorkOrderConfig->updateThis($whereConfig, $fillData);
    }

    /**
     * 接单后将未结单通知时间置空.
     */
    public function receiveWorkOrderSetConfig($works_order_id, $stepArr, $village_id = 0, $configId = 0, $notice_type = WorkOrderConst::WORK_ORDER_NOT_GRAB_NOTICE) {
        $dbHouseCommunityWorkOrderConfig = new HouseCommunityWorkOrderConfig();
        $whereConfig = [];
        if ($village_id) {
            $whereConfig['village_id'] = $village_id;
        }
        if ($configId) {
            $whereConfig['id'] = $configId;
        } else {
            $whereConfig['works_order_id'] = $works_order_id;
        }
        $configInfo = $dbHouseCommunityWorkOrderConfig->getOne($whereConfig, 'id, works_order_id, village_id, notice_time, notice_text, over_timely_report_time, over_timely_report_text');
        if ($configInfo && !is_array($configInfo)) {
            $configInfo = $configInfo->toArray();
        }
        if (empty($configInfo)) {
            return true;
        }
        $fillData = [];
        switch ($notice_type) {
            case 1:
                if (!empty($stepArr)) {
                    $notice_text = isset($configInfo['notice_text']) && $configInfo['notice_text'] ? $configInfo['notice_text'] : '';
                    if ($notice_text) {
                        $notice_text_arr = json_decode($notice_text, true);
                    } else {
                        $notice_text_arr = [];
                    }
                    $step = isset($notice_text_arr['step']) ? $notice_text_arr['step'] : [];
                    $step[] = $stepArr;
                    $notice_text_arr['step'] = $step;
                    $fillData['notice_text'] = json_encode($notice_text_arr,JSON_UNESCAPED_UNICODE);

                    $over_timely_report_text = isset($configInfo['over_timely_report_text']) && $configInfo['over_timely_report_text'] ? $configInfo['over_timely_report_text'] : '';
                    if ($over_timely_report_text) {
                        $over_timely_report_text_arr = json_decode($over_timely_report_text, true);
                    } else {
                        $over_timely_report_text_arr = [];
                    }
                    $step = isset($over_timely_report_text_arr['step']) ? $over_timely_report_text_arr['step'] : [];
                    $step[] = $stepArr;
                    $over_timely_report_text_arr['step'] = $step;
                    $fillData['over_timely_report_text'] = json_encode($over_timely_report_text_arr,JSON_UNESCAPED_UNICODE);
                }
                $fillData['notice_time'] = 0;
                $fillData['over_timely_report_time'] = 0;
                break;
            case WorkOrderConst::WORK_ORDER_NOT_GRAB_NOTICE:
                if (!empty($stepArr)) {
                    $notice_text = isset($configInfo['notice_text']) && $configInfo['notice_text'] ? $configInfo['notice_text'] : '';
                    if ($notice_text) {
                        $notice_text_arr = json_decode($notice_text, true);
                    } else {
                        $notice_text_arr = [];
                    }
                    $step = isset($notice_text_arr['step']) ? $notice_text_arr['step'] : [];
                    $step[] = $stepArr;
                    $notice_text_arr['step'] = $step;
                    $fillData['notice_text'] = json_encode($notice_text_arr,JSON_UNESCAPED_UNICODE);
                }
                $fillData['notice_time'] = 0;
                break;
            case WorkOrderConst::WORK_ORDER_NOT_GRAB_REPORT:
                if (!empty($stepArr)) {
                    $over_timely_report_text = isset($configInfo['over_timely_report_text']) && $configInfo['over_timely_report_text'] ? $configInfo['over_timely_report_text'] : '';
                    if ($over_timely_report_text) {
                        $over_timely_report_text_arr = json_decode($over_timely_report_text, true);
                    } else {
                        $over_timely_report_text_arr = [];
                    }
                    $step = isset($over_timely_report_text_arr['step']) ? $over_timely_report_text_arr['step'] : [];
                    $step[] = $stepArr;
                    $over_timely_report_text_arr['step'] = $step;
                    $fillData['over_timely_report_text'] = json_encode($over_timely_report_text_arr,JSON_UNESCAPED_UNICODE);
                }
                $fillData['over_timely_report_time'] = 0;
                break;
            case WorkOrderConst::WORK_ORDER_OVER_NOT_PROCESSED_REPORT:
                $dbHouseCommunityWorkOrderNotice = new HouseCommunityWorkOrderNotice();
                $where = [];
                $village_id && $where[] = ['village_id', '=', $village_id];
                $where[] = ['works_order_id', '=', $works_order_id];
                $where[] = ['notice_type', '=', $notice_type];
                $where[] = ['notice_status', '=', 0];
                $where[] = ['notice_time', '>=', time()];
                $noticeHandle = $dbHouseCommunityWorkOrderNotice->getOne($where, 'id');
                if (!isset($noticeHandle['id']) || !$noticeHandle['id']) {
                    return true;
                }
                if (!empty($stepArr)) {
                    $over_not_processed_report_text = isset($configInfo['over_not_processed_report_text']) && $configInfo['over_not_processed_report_text'] ? $configInfo['over_not_processed_report_text'] : '';
                    if ($over_not_processed_report_text) {
                        $over_not_processed_report_text_arr = json_decode($over_not_processed_report_text, true);
                    } else {
                        $over_not_processed_report_text_arr = [];
                    }
                    $step = isset($over_not_processed_report_text_arr['step']) ? $over_not_processed_report_text_arr['step'] : [];
                    $step[] = $stepArr;
                    $over_not_processed_report_text_arr['step'] = $step;
                    $fillData['over_not_processed_report_text'] = json_encode($over_not_processed_report_text_arr,JSON_UNESCAPED_UNICODE);
                }
                $fillData['over_not_processed_report_time'] = 0;
                break;
            case WorkOrderConst::WORK_ORDER_OVER_FEE_CONFIRM_OPERATE:
                $dbHouseCommunityWorkOrderNotice = new HouseCommunityWorkOrderNotice();
                $where = [];
                $village_id && $where[] = ['village_id', '=', $village_id];
                $where[] = ['works_order_id', '=', $works_order_id];
                $where[] = ['notice_type', '=', $notice_type];
                $where[] = ['notice_status', '=', 0];
                $where[] = ['notice_time', '>=', time()];
                $noticeHandle = $dbHouseCommunityWorkOrderNotice->getOne($where, 'id');
                if (!isset($noticeHandle['id']) || !$noticeHandle['id']) {
                    return true;
                }
                if (!empty($stepArr)) {
                    $over_fee_confirm_operate_text = isset($configInfo['over_fee_confirm_operate_text']) && $configInfo['over_fee_confirm_operate_text'] ? $configInfo['over_fee_confirm_operate_text'] : '';
                    if ($over_fee_confirm_operate_text) {
                        $over_fee_confirm_operate_text_arr = json_decode($over_fee_confirm_operate_text, true);
                    } else {
                        $over_fee_confirm_operate_text_arr = [];
                    }
                    $step = isset($over_fee_confirm_operate_text_arr['step']) ? $over_fee_confirm_operate_text_arr['step'] : [];
                    $step[] = $stepArr;
                    $over_fee_confirm_operate_text_arr['step'] = $step;
                    $fillData['over_fee_confirm_operate_text'] = json_encode($over_fee_confirm_operate_text_arr,JSON_UNESCAPED_UNICODE);
                }
                $fillData['over_fee_confirm_operate_time'] = 0;
                break;
        }
        $fillData['update_time'] = time();
        // todo 清理对应未执行操作
        !$village_id && $village_id = isset($configInfo['village_id']) ? intval($configInfo['village_id']) : 0;
        !$works_order_id && $works_order_id = isset($configInfo['works_order_id']) ? intval($configInfo['works_order_id']) : 0;
        // 清理通知
        $noticeArr = [
            'village_id' => $village_id, 'works_order_id' => $works_order_id, 'notice_type' => $notice_type,
        ];
        (new HouseCommunityHandleNoticeService())->tryCatchClearHandleRecordNoticeBox($noticeArr);
        return $dbHouseCommunityWorkOrderConfig->updateThis($whereConfig, $fillData);
    }

    /**
     * 处理相关配置
     */
    public function handleIncreaseWorkOrderConfig($params) {
        $nowTime = isset($params['nowTime']) ? intval($params['nowTime']) : time();
        $configId = isset($params['configId']) ? intval($params['configId']) : 0;
        $communityConfig = isset($params['communityConfig']) ? $params['communityConfig'] : [];
        $agree_time = isset($params['agree_time']) ? intval($params['agree_time']) : 0;
        $worker_id = isset($params['worker_id']) ? intval($params['worker_id']) : 0;
        $workName = isset($params['workName']) ? trim($params['workName']) : '';
        $workPhone = isset($params['workPhone']) ? trim($params['workPhone']) : '';
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $works_order_id = isset($params['works_order_id']) ? intval($params['works_order_id']) : 0;
        $fillData = [];
        if (isset($communityConfig['not_to_facilities'])) {
            $fillData['not_to_facilities'] = intval($communityConfig['not_to_facilities']);
            unset($communityConfig['not_to_facilities']);
        }
        $not_receive_interval = 0;
        if (isset($communityConfig['not_receive_interval'])) {
            $not_receive_interval = $fillData['not_receive_interval'] = intval($communityConfig['not_receive_interval']);
            unset($communityConfig['not_receive_interval']);
        }
        if (isset($communityConfig['work_id'])) {
            $fillData['notice_work_id'] = intval($communityConfig['work_id']);
            unset($communityConfig['work_id']);
        }
        $dbHouseCommunityWorkOrderConfig = new HouseCommunityWorkOrderConfig();
        if ($not_receive_interval > 0 && !$worker_id && isset($fillData['notice_work_id']) && $fillData['notice_work_id'] > 0) {
            // 提交工单时候 设置了间隔通知时间 也设置了 推送对象
            $notice_time = $nowTime + $not_receive_interval * 60;
            $fillData['notice_time'] = $notice_time;
            // todo 定时队列进行推送
            $configInfo = $dbHouseCommunityWorkOrderConfig->getOne(['id' => $configId], 'id, works_order_id, village_id');
            !$village_id && $village_id = isset($configInfo['village_id']) ? intval($configInfo['village_id']) : 0;
            !$works_order_id && $works_order_id = isset($configInfo['works_order_id']) ? intval($configInfo['works_order_id']) : 0;
            $noticeArr = [
                'village_id' => $village_id, 'works_order_id' => $works_order_id, 'is_job' => 1,
                'notice_type' => WorkOrderConst::WORK_ORDER_NOT_GRAB_NOTICE, 'notice_time' => $notice_time,
            ];
            (new HouseCommunityHandleNoticeService())->tryCatchHandleRecordNoticeBox($noticeArr);
        } elseif ($worker_id > 0) {
            if (!$workName && !$workPhone) {
                $db_house_worker = new HouseWorker();
                $where_house_work = [];
                $where_house_work[] = ['wid', '=', $worker_id];
                $house_work = $db_house_worker->get_one($where_house_work, 'wid,name,phone');
                $workName = isset($house_work['name']) && $house_work['name'] ? trim($house_work['name']) : '';
                $workPhone = isset($house_work['phone']) && $house_work['phone'] ? trim($house_work['phone']) : '';
                $notice_text_arr = [
                    'step' => [
                        ['title' => '自动指派', 'name' => $workName, 'phone' => $workPhone, 'content' => '', 'type' => 'text', 'time' => $nowTime, 'worker_id' => $worker_id,]
                    ]
                ];
                $fillData['notice_text'] = json_encode($notice_text_arr,JSON_UNESCAPED_UNICODE);
            }
            
        }
        $auto_end_bill_order = 0;
        $auto_end_interval = 0;
        if (isset($communityConfig['auto_end_bill_order'])) {
            $auto_end_bill_order = $fillData['auto_end_bill_order'] = intval($communityConfig['auto_end_bill_order']);
            unset($communityConfig['auto_end_bill_order']);
        }
        if (isset($communityConfig['auto_end_interval'])) {
            $auto_end_interval = $fillData['auto_end_interval'] = intval($communityConfig['auto_end_interval']);
            unset($communityConfig['auto_end_interval']);
        }
        if ($auto_end_bill_order == 1 && $auto_end_interval > 0 && $agree_time > 0) {
            $fillData['auto_end_time'] = $nowTime + $auto_end_interval * 3600;
        }
        if (isset($communityConfig['refuse_order_nums_report'])) {
            $fillData['refuse_order_nums_report'] = intval($communityConfig['refuse_order_nums_report']);
            unset($communityConfig['refuse_order_nums_report']);
        }
        if (isset($communityConfig['not_over_stars_report'])) {
            $fillData['not_over_stars_report'] = intval($communityConfig['not_over_stars_report']);
            unset($communityConfig['not_over_stars_report']);
        }
        $not_timely_report_switch = 0;
        $over_timely_report = 0;
        $over_not_processed_report = 0;
        if (isset($communityConfig['not_timely_report_switch'])) {
            $not_timely_report_switch = $fillData['not_timely_report_switch'] = intval($communityConfig['not_timely_report_switch']);
            unset($communityConfig['not_timely_report_switch']);
        }
        if (isset($communityConfig['over_timely_report'])) {
            $over_timely_report = $fillData['over_timely_report'] = intval($communityConfig['over_timely_report']);
            unset($communityConfig['over_timely_report']);
        }
        if (isset($communityConfig['over_not_processed_report'])) {
            $over_not_processed_report = $fillData['over_not_processed_report'] = intval($communityConfig['over_not_processed_report']);
            unset($communityConfig['over_not_processed_report']);
        }
        if (isset($communityConfig['over_fee_confirm_interval'])) {
            $fillData['over_fee_confirm_interval'] = intval($communityConfig['over_fee_confirm_interval']);
            unset($communityConfig['over_fee_confirm_interval']);
        }
        if (isset($communityConfig['over_fee_confirm_operate'])) {
            $fillData['over_fee_confirm_operate'] = intval($communityConfig['over_fee_confirm_operate']);
            unset($communityConfig['over_fee_confirm_operate']);
        }
        if (isset($communityConfig['refuse_project_nums_report'])) {
            $fillData['refuse_project_nums_report'] = intval($communityConfig['refuse_project_nums_report']);
            unset($communityConfig['refuse_project_nums_report']);
        }
        if (!$worker_id && $over_timely_report > 0) {
            // 未指派工作人员 且设置了 工单超时设置 走下队列
            $fillData['over_timely_report_time'] = $nowTime + $over_timely_report * 60;
            $noticeArr = [
                'village_id' => $village_id, 'works_order_id' => $works_order_id, 'is_job' => 1,
                'notice_type' => WorkOrderConst::WORK_ORDER_NOT_GRAB_REPORT, 
                'notice_time' => $fillData['over_timely_report_time'],
            ];
            (new HouseCommunityHandleNoticeService())->tryCatchHandleRecordNoticeBox($noticeArr);
        }
        if ($worker_id && $over_not_processed_report > 0) {
            // 指派工作人员 且设置了 超时未处理设置 走下队列
            $fillData['over_not_processed_report_time'] = $nowTime + $over_not_processed_report * 60;
            $noticeArr = [
                'village_id' => $village_id, 'works_order_id' => $works_order_id, 'is_job' => 1,
                'notice_type' => WorkOrderConst::WORK_ORDER_OVER_NOT_PROCESSED_REPORT,
                'notice_time' => $fillData['over_not_processed_report_time'],
            ];
            (new HouseCommunityHandleNoticeService())->tryCatchHandleRecordNoticeBox($noticeArr);
        }
        $fillData['update_time'] = $nowTime;
        $fillData['community_config'] = json_encode($communityConfig,JSON_UNESCAPED_UNICODE);
         return $dbHouseCommunityWorkOrderConfig->updateThis(['id' => $configId], $fillData);
    }

    /**
     * 根据工单状态处理是否上报督查室.
     */
    public function overNotProcessedReport($params)
    {
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : '';
        $order_id = isset($params['order_id']) ? intval($params['order_id']) : '';
        $worker_id = isset($params['worker_id']) ? intval($params['worker_id']) : '';
        $event_status = isset($params['event_status']) ? intval($params['event_status']) : '';
        $isSet = true;
        if ($event_status && in_array($event_status, [20, 21, 22, 23, 24, 25]) && $worker_id) {
            $isSet = true;
        }  elseif ($event_status > 25) {
            $isSet = false;
            $work_id = isset($order_detail['worker_id']) ? intval($order_detail['worker_id']) : 0;
            if ($work_id) {
                $worker = (new HouseWorker())->getOne(['wid' => $work_id], 'wid,name,phone,department_id');
            } else {
                $worker = [];
            }
            $serviceHouseCommunityWorkOrder = new HouseCommunityWorkOrderService();
            $workName = isset($worker['name']) && $worker['name'] ? trim($worker['name']) : '';
            $workPhone = isset($worker['phone']) && $worker['phone'] ? trim($worker['phone']) : '';
            $step = [
                'title' => '处理工单处理', 'name' => $workName, 'phone' => $workPhone,'type' => 'text',
                'content' => $workName .'处理工单', 'time' => time(), 'worker_id' => $worker_id
            ];
            try {
                $serviceHouseCommunityWorkOrder->receiveWorkOrderSetConfig($order_id, $step, $village_id, 0, WorkOrderConst::WORK_ORDER_OVER_NOT_PROCESSED_REPORT);
            } catch (\Exception $e) {
                $data = [$order_id, $step, $village_id, 0, WorkOrderConst::WORK_ORDER_OVER_NOT_PROCESSED_REPORT];
                fdump_api(['执行错误' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'data' => $data], 'repairOrderChange', true);
                throw new \think\Exception($e->getMessage());
            } catch (\Throwable $e) {
                $data = [$order_id, $step, $village_id, 0, WorkOrderConst::WORK_ORDER_OVER_NOT_PROCESSED_REPORT];
                fdump_api(['执行错误' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'data' => $data], 'repairOrderChange', true);
                throw new \think\Exception($e->getMessage());
            } catch (\RuntimeException $e) {
                $data = [$order_id, $step, $village_id, 0, WorkOrderConst::WORK_ORDER_OVER_NOT_PROCESSED_REPORT];
                fdump_api(['执行错误' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'data' => $data], 'repairOrderChange', true);
                throw new \think\Exception($e->getMessage());
            } catch (\InvalidArgumentException $e) {
                $data = [$order_id, $step, $village_id, 0, WorkOrderConst::WORK_ORDER_OVER_NOT_PROCESSED_REPORT];
                fdump_api(['执行错误' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'data' => $data], 'repairOrderChange', true);
                throw new \think\Exception($e->getMessage());
            }
            if ($event_status == 35) {
                $dbHouseCommunityWorkOrderConfig = new HouseCommunityWorkOrderConfig();
                $whereConfig = [];
                !$village_id && $village_id = isset($orderInfo['village_id']) ? intval($orderInfo['village_id']) : 0;
                $whereConfig['village_id'] = $village_id;
                $whereConfig['works_order_id'] = $order_id;
                $configInfo = $dbHouseCommunityWorkOrderConfig->getOne($whereConfig, 'id, works_order_id, village_id, over_fee_confirm_interval, over_fee_confirm_operate');
                if ($configInfo && !is_array($configInfo)) {
                    $configInfo = $configInfo->toArray();
                }
                $nowTime = time();
                $over_fee_confirm_interval = isset($configInfo['over_fee_confirm_interval']) ? intval($configInfo['over_fee_confirm_interval']) : 0;
                $over_fee_confirm_operate = isset($configInfo['over_fee_confirm_operate']) ? intval($configInfo['over_fee_confirm_operate']) : 0;
                // 工作人员添加收费项
                $fillData = [];
                $fillData['over_fee_confirm_operate_time'] = $nowTime + $over_fee_confirm_interval * 60;
                $fillData['over_fee_confirm_operate'] = $over_fee_confirm_operate;
                $noticeArr = [
                    'village_id' => $village_id, 'works_order_id' => $order_id, 'is_job' => 1,
                    'notice_type' => WorkOrderConst::WORK_ORDER_OVER_FEE_CONFIRM_OPERATE,
                    'notice_time' => $fillData['over_fee_confirm_operate_time'],
                ];
                (new HouseCommunityHandleNoticeService())->tryCatchHandleRecordNoticeBox($noticeArr);
                $dbHouseCommunityWorkOrderConfig->updateThis($whereConfig, $fillData);
            }
        }
        if ($isSet) {
            $dbHouseCommunityWorkOrderConfig = new HouseCommunityWorkOrderConfig();
            $whereWorkOrderConfig = [];
            $whereWorkOrderConfig[] = ['works_order_id', '=', $order_id];
            $village_id && $whereWorkOrderConfig[] = ['village_id', '=', $village_id];
            $configInfo = $dbHouseCommunityWorkOrderConfig->getOne($whereWorkOrderConfig, 'id, works_order_id, village_id, over_not_processed_report');
            $over_not_processed_report = isset($configInfo['over_not_processed_report']) ? intval($configInfo['over_not_processed_report']) : 0;
            if ($worker_id && $over_not_processed_report > 0) {
                // 指派工作人员 且设置了 超时未处理设置 走下队列
                $over_not_processed_report_time = time() + $over_not_processed_report * 60;
                $noticeArr = [
                    'village_id' => $village_id, 'works_order_id' => $order_id, 'is_job' => 1,
                    'notice_type' => WorkOrderConst::WORK_ORDER_OVER_NOT_PROCESSED_REPORT,
                    'notice_time' => $over_not_processed_report_time,
                ];
                (new HouseCommunityHandleNoticeService())->tryCatchHandleRecordNoticeBox($noticeArr);
            }
        }
        return true;
    }
    

    /**
     * 获取相关工单配置信息.
     */
    public function getHouseCommunityWorkOrderConfig($params, $field = true) {
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $works_order_id = isset($params['works_order_id']) ? intval($params['works_order_id']) : 0;
        $dbHouseCommunityWorkOrderConfig = new HouseCommunityWorkOrderConfig();
        $whereConfig = [];
        $whereConfig['village_id'] = $village_id;
        $whereConfig['works_order_id'] = $works_order_id;
        return $dbHouseCommunityWorkOrderConfig->getOne($whereConfig, $field);
    }

    /**
     * 更新相关工单配置信息.
     */
    public function updateHouseCommunityWorkOrderConfig($params, $fillData) {
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $works_order_id = isset($params['works_order_id']) ? intval($params['works_order_id']) : 0;
        $dbHouseCommunityWorkOrderConfig = new HouseCommunityWorkOrderConfig();
        $whereConfig = [];
        $whereConfig['village_id'] = $village_id;
        $whereConfig['works_order_id'] = $works_order_id;
        return $dbHouseCommunityWorkOrderConfig->updateThis($whereConfig, $fillData);
    }

    /**
     * 转给其他小区的时候 记录配置和相关处理.
     */
    public function toOtherVillage($params) {
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $works_order_id = isset($params['works_order_id']) ? intval($params['works_order_id']) : 0;
        $now_handle_village_id = isset($params['now_handle_village_id']) ? intval($params['now_handle_village_id']) : 0;
        $to_work_id = isset($params['to_work_id']) ? intval($params['to_work_id']) : 0;
        $title = isset($params['title']) ? $params['title'] : '转单其他小区';
        $config = $this->getHouseCommunityWorkOrderConfig($params);
        if ($config && $now_handle_village_id > 0) {
            $now_community_config = (new HouseCommunityAboutService())->getHouseCommunityConfig($now_handle_village_id);
            $fillData = [
                'now_handle_village_id' => $now_handle_village_id,
                'now_community_config' => json_encode($now_community_config, JSON_UNESCAPED_UNICODE),
            ];
            $nowTime = time();
            $not_receive_interval = isset($now_community_config['not_receive_interval']) ? intval($now_community_config['not_receive_interval']) : 0;
            $over_timely_report = isset($now_community_config['over_timely_report']) ? intval($now_community_config['over_timely_report']) : 0;
            $refuse_project_nums_report = isset($now_community_config['refuse_project_nums_report']) ? intval($now_community_config['refuse_project_nums_report']) : 0;
            $fillData['refuse_project_nums_report'] = $refuse_project_nums_report;
            if (!$to_work_id && $not_receive_interval > 0) {
                $notice_time = $nowTime + $not_receive_interval * 60;
                $fillData['not_receive_interval'] = $not_receive_interval;
                $fillData['notice_time'] = $notice_time;
                // todo 定时队列进行推送
                $noticeArr = [
                    'village_id' => $village_id,'now_handle_village_id' => $now_handle_village_id, 'works_order_id' => $works_order_id, 'is_job' => 1,
                    'notice_type' => WorkOrderConst::WORK_ORDER_NOT_GRAB_NOTICE, 'notice_time' => $notice_time,
                ];
                (new HouseCommunityHandleNoticeService())->tryCatchHandleRecordNoticeBox($noticeArr);
            } else {
                $step = ['title' => $title, 'now_community_config' => $now_community_config, 'not_receive_interval' => $not_receive_interval, 'content' => '转单其他小区自动分配','type' => 'text', 'time' => $nowTime, 'worker_id' => $to_work_id];
                $this->receiveWorkOrderSetConfig($works_order_id, $step, $village_id);
            }
            if (!$to_work_id && $over_timely_report > 0) {
                $fillData['over_timely_report'] = $over_timely_report;
                $fillData['over_timely_report_time'] = $nowTime + $over_timely_report * 60;
                $noticeArr = [
                    'village_id' => $village_id, 'works_order_id' => $works_order_id, 'is_job' => 1,
                    'notice_type' => WorkOrderConst::WORK_ORDER_NOT_GRAB_REPORT, 'notice_time' => $fillData['over_timely_report_time'],
                ];
                (new HouseCommunityHandleNoticeService())->tryCatchHandleRecordNoticeBox($noticeArr);
            } else {
                $step = ['title' => $title, 'now_community_config' => $now_community_config, 'over_timely_report' => $over_timely_report, 'content' => '转单其他小区自动分配','type' => 'text', 'time' => $nowTime, 'worker_id' => $to_work_id];
                $this->receiveWorkOrderSetConfig($works_order_id, $step, $village_id, 0, WorkOrderConst::WORK_ORDER_NOT_GRAB_REPORT);
            }
            $this->updateHouseCommunityWorkOrderConfig($params, $fillData);
        }
        return true;
    }

    /**
     * 自动完结工单.
     */
    public function autoWorkCompleted($params)
    {
        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        $order_id = isset($params['order_id']) ? intval($params['order_id']) : 0;
        $worker_id = isset($params['worker_id']) ? intval($params['worker_id']) : 0;
        $log_name = 'work_completed';
        $sHouseNewRepairService = new HouseNewRepairService();
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        
        $where_order = [];
        $where_order[] = ['order_id', '=', $order_id];
        $order_detail = $db_house_new_repair_works_order->getOne($where_order);
        !$worker_id && $worker_id = isset($order_detail['worker_id']) ? intval($order_detail['worker_id']) : 0;
       
        $order_event_status = isset($order_detail['event_status']) ? intval($order_detail['event_status']) : 0;
        if ($order_event_status >= 40) {
            return true;
        }
        
        $order_set = [
            'now_role' => 1,
            'event_status' => 40,
        ];
        $operator_type = 30;
        $log_operator = '';
        $log_phone = '';
        if ($worker_id > 0) {
            $result = $sHouseNewRepairService->getTimelyStatus($order_detail);
            if ($result['error']) {
                $order_set = array_merge($order_set, $result['data']);
            }
            $where_house_work = [];
            $where_house_work[] = ['wid', '=', $worker_id];
            $house_work = (new HouseWorker())->get_one($where_house_work, 'name,phone');
            $log_operator = $house_work['name'];
            $log_phone = $house_work['phone'];
        }
        $where_update = [];
        $where_update[] = ['order_id', '=', $order_id];
        $order_set['update_time'] = time();
        $order_update = $db_house_new_repair_works_order->saveOne($where_update, $order_set);
        
        $jz_open_title = '工单自动结单';
        $jz_open_content = '工单自动结单，去除自动结单时间';
        if ($order_update) {
            $log_data = [
                'order_id' => $order_id, 'log_name' => $log_name,
                'village_id' => isset($order_detail['village_id']) ? intval($order_detail['village_id']) : '',
                'property_id' => isset($order_detail['property_id']) ? intval($order_detail['property_id']) : '',
                'log_operator' => $log_operator, 'log_phone' => $log_phone, 'operator_type' => $operator_type, 'operator_id' => $worker_id,
                'log_uid' => $order_detail['uid'] ? $order_detail['uid'] : 0,
                'log_content' => '提供联系方式自动结束工单',
                'log_imgs' => '', 'evaluate' => 0, 'add_time' => time(),
            ];
            $db_house_new_repair_works_order_log = new HouseNewRepairWorksOrderLog();
            $where_count = [];
            $where_count[] = ['order_id', '=', $order_id];
            $where_count[] = ['log_name', '=', $log_name];
            $log_num = $db_house_new_repair_works_order_log->getCount($where_count);
            if (!$log_num) {
                $log_num = 1;
            } else {
                $log_num = intval($log_num) + 1;
            }
            $log_data['log_num'] = $log_num;
            $log_data['project_id'] = isset($order_detail['project_id']) ? $order_detail['project_id'] : 0;
            $log_data['rule_id'] = $order_detail['rule_id'];
            $log_data['charge_price'] = $order_detail['charge_price'];
            $log_data['pay_type'] = $order_detail['pay_type'];
            $log_data['change_reason'] = '提供联系方式自动结束工单';
            $log_id = $sHouseNewRepairService->addRepairLog($log_data);

            $property_id = isset($order_detail['property_id']) ? intval($order_detail['property_id']) : 0;
            //todo 工作人员结单
            if ($order_detail['order_type'] == 0) {
                //todo 用户提交的工单
                if ($order_detail['event_status'] >= 40) {
                    $sHouseNewRepairService->newRepairSendMsg(32, $order_id, 0, $property_id);
                } else {
                    $sHouseNewRepairService->newRepairSendMsg(30, $order_id, 0, $property_id);
                }
            } elseif ($order_detail['order_type'] == 4) {
                //todo 工作人员提交的工单
                if ($order_detail['event_status'] >= 40) {
                    $sHouseNewRepairService->newRepairSendMsg(33, $order_id, $order_detail['uid'], $property_id);
                } else {
                    $sHouseNewRepairService->newRepairSendMsg(31, $order_id, $order_detail['uid'], $property_id);
                }
            }
            if ($jz_open && isset($jz_open_title) && isset($jz_open_content)) {
                $stepArr = [
                    'title' => $jz_open_title,
                    'type' => 'text',
                    'content' => $jz_open_content,
                    'time' => time(),
                    'work_id' => $worker_id
                ];
                (new HouseCommunityWorkOrderService())->clearAutoEndTime($order_id, $stepArr);
            }
            if ($jz_open && isset($order_set['event_status'])) {
                $event_status = intval($order_set['event_status']);
                $to_work_id = isset($order_set['worker_id']) ? intval($order_set['worker_id']) : 0;
                $village_id = isset($order_detail['village_id']) ? intval($order_detail['village_id']) : '';
                $reportParams = ['village_id' => $village_id, 'order_id' => $order_id, 'worker_id' => $to_work_id, 'event_status' => $event_status];
                (new HouseCommunityWorkOrderService())->overNotProcessedReport($reportParams);
            }
        }
        return true;
    }
}