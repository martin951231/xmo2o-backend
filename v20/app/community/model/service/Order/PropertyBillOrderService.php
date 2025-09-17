<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      物业小区账单报表
 */

namespace app\community\model\service\order;

use app\common\model\service\export\ExportService as BaseExportService;
use app\community\model\db\ExportLog;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseVillage;
use app\community\model\service\HouseListDatasExportOutService;
use app\consts\order\PropertyOrderConst;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\facade\Cache;

class PropertyBillOrderService
{
    /**
     * 小区月度已缴账单.
     */
    public function villageMonthlyBill($params)
    {
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $villageIds = isset($params['villageIds']) ? $params['villageIds'] : [];
        $db_house_village = new HouseVillage();
        $whereVillage = [];
        $whereVillage[] = ['status', '=', 1];
        if (!empty($villageIds)) {
            $whereVillage[] = ['village_id', 'in', $villageIds];
        } else {
            $whereVillage[] = ['property_id', '=', $property_id];
        }
        $village_list = $db_house_village->getList($whereVillage, 'village_id,village_name');

        $nowTime = time();
        $payDate = isset($params['payDate']) ? $params['payDate'] : [];
        $isRangeJudgment = 1;
        $defaultDay = 7; // 默认查询 7天的 由于是查询前一天的 所以默认8
        $defaultOverDays = 31; // 默认查询 跨月查询时间范围在31天 界限是31天
        $payRangeDays = 0;
        if (empty($payDate)) {
            // 默认取 7天前-1天前
            $payDate[] = date('Y-m-d', $nowTime - $defaultDay*86400);
            $payDate[] = date('Y-m-d', $nowTime - 86400);
            $isRangeJudgment = 1;
            $payRangeDays = 7;
        }
        $project_id = isset($params['project_id']) && $params['project_id'] ? $params['project_id'] : [];
        $service_start_time = isset($params['service_start_time']) && $params['service_start_time'] ? @strtotime($params['service_start_time']) : '';
        $service_end_time = isset($params['service_end_time']) && $params['service_end_time'] ? @strtotime($params['service_end_time'].' 23:59:59') : '';

        $payDateStart = isset($payDate[0]) ? @strtotime($payDate[0]) : 0;
        $payDateEnd = isset($payDate[1]) ? @strtotime($payDate[1] . ' 23:59:59') : 0;
        if ($isRangeJudgment == 1 && $payDateStart > 0 && $payDateEnd > 0) {
            $dayPayStart = date('Y-m', $payDateStart);
            $dayPayEnd = date('Y-m', $payDateEnd);
            if ($dayPayStart != $dayPayEnd) {
                $payDateStart1 = $payDateEnd + 1 - ($defaultOverDays * 86400);
                if ($payDateStart1 > $payDateStart) {
                    throw new \think\Exception("支付时间跨月查询不能超过31天！");
                }
            }
        }
        if ($isRangeJudgment == 1 && $payDateStart > 0 && $payDateEnd > 0) {
            $interval = abs($payDateEnd - $payDateStart);
            $payRangeDays = ceil($interval / 86400);
        }
        $dateList = [];
        for ($i = 0; $i < $payRangeDays; $i++) {
            $dayInterval = $payDateStart + ($i * 86400);
            $dayDate = date('Y-m-d', $dayInterval);
            $dateList[$dayDate] = [
                'order_date' => $dayDate,
                'dateText' => date('Y年m月d日', $dayInterval),
                'online_pay_total' => 0,
                'offline_pay_total' => 0,
                'pay_total' => 0,
            ];
        }
        $totalDataList = $dateList;
        $totalMoney = 0;
        if (! empty($village_list)) {
            $dbHouseNewPayOrder = new HouseNewPayOrder();
            if (!is_array($village_list)) {
                $village_list = $village_list->toArray();
            }
            //fdump_api(['dateList' => $dateList, 'village_list' => $village_list, '$payRangeDays' => $payRangeDays], '$villageMonthlyBill');
            foreach ($village_list as &$item) {
                $where = [];
                $where[] = ['village_id', '=', $item['village_id']];
                $where[] = ['is_paid', '=', 1];
                $where[] = ['is_discard', '=', 1];
                $where[] = ['order_type', '<>', 'non_motor_vehicle'];
                if ($payDateStart > 0 && $payDateEnd > 0) {
                    $where[] = ['pay_time', '>=', $payDateStart];
                    $where[] = ['pay_time', '<=', $payDateEnd];
                }
                if($service_start_time && $service_start_time > 100){
                    $where[] = ['service_start_time','>=',$service_start_time];
                }
                if($service_end_time && $service_end_time > 100){
                    $where[] = ['service_end_time','<=',$service_end_time];
                }
                if (!empty($project_id)) {
                    $where[] = ['project_id','in',$project_id];
                }
                $wherePay = '(`is_refund`=1 OR `refund_money`<`modify_money`)';
                $payField = 'order_id, pay_time, pay_money';
                $villageDateList = $dateList;

                $whereOnlinePay = $where;
                $whereOnlinePay[] = ['pay_type', 'in', [1, 4]];

                $whereOfflinePay = $where;
                $whereOfflinePay[] = ['pay_type', 'not in', [1, 4]];

                $villageTotal = 0;
                
                // 优先读取缓存的
                $redisParams = [
                    'function' => 'villageMonthlyBill', 'whereOnlinePay' => $whereOnlinePay, 'whereOfflinePay' => $whereOfflinePay,
                ];
                /*
                $result = $this->requestResult($redisParams);
                if (isset($result['villageDateList']) && !empty($result['villageDateList'])) {
                    foreach ($result['villageDateList'] as $redisKey => $redisItem) {
                        $order_date = isset($redisItem['order_date']) ? $redisItem['order_date'] : $redisKey;
                        $online_pay_total = isset($redisItem['online_pay_total']) && floatval($redisItem['online_pay_total']) > 0 ? $redisItem['online_pay_total'] : 0;
                        $offline_pay_total = isset($redisItem['offline_pay_total']) && floatval($redisItem['offline_pay_total']) > 0 ? $redisItem['offline_pay_total'] : 0;
                        if (isset($totalDataList[$order_date])) {
                            $totalDataList[$order_date]['online_pay_total'] += $online_pay_total;
                            $totalDataList[$order_date]['online_pay_total'] = formatNumber($totalDataList[$order_date]['online_pay_total'], 2, 1);
                            $totalDataList[$order_date]['pay_total'] += $online_pay_total;
                            $totalDataList[$order_date]['offline_pay_total'] += $offline_pay_total;
                            $totalDataList[$order_date]['offline_pay_total'] = formatNumber($totalDataList[$order_date]['offline_pay_total'], 2, 1);
                            $totalDataList[$order_date]['pay_total'] += $offline_pay_total;
                            $totalDataList[$order_date]['pay_total'] = formatNumber($totalDataList[$order_date]['pay_total'], 2, 1);
                            $villageTotal += ($online_pay_total + $offline_pay_total);
                        }
                    }
                    $item['redis'] = 1;
                    $item['villageDateList'] = array_values($villageDateList);
                    $item['villageTotal'] = formatNumber($villageTotal, 2, 1);
                    $totalMoney += floatval($item['villageTotal']);
                    continue;
                }
                */
                $onlinePay = $dbHouseNewPayOrder->where($whereOnlinePay)->where($wherePay)->field($payField)->select()->toArray();

                foreach ($onlinePay as $onlineItem) {
                    $order_date = isset($onlineItem['pay_time']) ? @date('Y-m-d', $onlineItem['pay_time']) : '';
                    if (! $order_date) {
                        continue;
                    }
                    $online_pay_total = isset($onlineItem['pay_money']) && floatval($onlineItem['pay_money']) > 0 ? $onlineItem['pay_money'] : 0;
                    if (isset($villageDateList[$order_date])) {
                        $villageDateList[$order_date]['online_pay_total'] += $online_pay_total;
                        $villageDateList[$order_date]['pay_total'] += $online_pay_total;
                        $villageDateList[$order_date]['online_pay_total'] = formatNumber($villageDateList[$order_date]['online_pay_total'], 2, 1);
                        $villageDateList[$order_date]['pay_total'] = formatNumber($villageDateList[$order_date]['pay_total'], 2, 1);
                        $villageTotal += $online_pay_total;
                    }
                    if (isset($totalDataList[$order_date])) {
                        $totalDataList[$order_date]['online_pay_total'] += $online_pay_total;
                        $totalDataList[$order_date]['pay_total'] += $online_pay_total;
                        $totalDataList[$order_date]['online_pay_total'] = formatNumber($totalDataList[$order_date]['online_pay_total'], 2, 1);
                        $totalDataList[$order_date]['pay_total'] = formatNumber($totalDataList[$order_date]['pay_total'], 2, 1);
                    }
                }

                $offlinePay = $dbHouseNewPayOrder->where($whereOfflinePay)->where($wherePay)->field($payField)->select()->toArray();
                foreach ($offlinePay as $offlineItem) {
                    $order_date = isset($offlineItem['pay_time']) ? @date('Y-m-d', $offlineItem['pay_time']) : '';
                    if (! $order_date) {
                        continue;
                    }
                    $offline_pay_total = isset($offlineItem['pay_money']) && floatval($offlineItem['pay_money']) > 0 ? $offlineItem['pay_money'] : 0;
                    if (isset($villageDateList[$order_date])) {
                        $villageDateList[$order_date]['offline_pay_total'] += $offline_pay_total;
                        $villageDateList[$order_date]['pay_total'] += $offline_pay_total;
                        $villageDateList[$order_date]['offline_pay_total'] = formatNumber($villageDateList[$order_date]['offline_pay_total'], 2, 1);
                        $villageDateList[$order_date]['pay_total'] = formatNumber($villageDateList[$order_date]['pay_total'], 2, 1);
                        $villageTotal += $offline_pay_total;
                    }
                    if (isset($totalDataList[$order_date])) {
                        $totalDataList[$order_date]['offline_pay_total'] += $offline_pay_total;
                        $totalDataList[$order_date]['pay_total'] += $offline_pay_total;
                        $totalDataList[$order_date]['offline_pay_total'] = formatNumber($totalDataList[$order_date]['offline_pay_total'], 2, 1);
                        $totalDataList[$order_date]['pay_total'] = formatNumber($totalDataList[$order_date]['pay_total'], 2, 1);
                    }
                }
                //$this->requestResult($params, ['villageDateList' => $villageDateList]);
                $item['villageDateList'] = array_values($villageDateList);
                $item['villageTotal'] = formatNumber($villageTotal, 2, 1);
                $totalMoney += floatval($item['villageTotal']);
                $item['redis'] = 0;
            }
        }
        return [
            'list' => $village_list,
            'totalDataList' => $totalDataList,
            'totalMoney' => formatNumber($totalMoney, 2, 1),
            'payDate' => $payDate,
            'service_start_time' => isset($params['service_start_time']) ? $params['service_start_time'] : '',
            'service_end_time' => isset($params['service_end_time']) ? $params['service_end_time'] : '',
            'project_id' => $project_id ? $project_id : [],
        ];
    }
    /**
     * 小区月度已缴报表导出-队列.
     */
    public function villageMonthlyBillExport($params) {
        $houseListDatasExportOutService = new HouseListDatasExportOutService();
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $property_name = isset($params['property_name']) ? trim($params['property_name']) : '';
        $exportParam = array();
        $exportParam['property_id'] = $property_id;
        $exportParam['exportParams'] = $params;
        $exportParam['export_type'] = 'village_monthly_bill_export';
        $exportParam['export_title'] = ($property_name ? "【{$property_name}】" : '') . '月度已缴报表';
        $exportInfo = $houseListDatasExportOutService->addExportLog(0, $exportParam);
        $site_url = cfg('site_url');
        $site_url = trim($site_url, '/');
        $exportInfo['excelExportOutFileUrl'] = $site_url . '/index.php?g=Index&c=ExportFile&a=download_house_export_file&export_from=village_monthly_bill_export&id=' . $exportInfo['export_id'];
        return $exportInfo;
    }
    /**
     * 小区年度已缴账单.
     */
    public function villageYearlyBill($params)
    {
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $villageIds = isset($params['villageIds']) ? $params['villageIds'] : [];
        $db_house_village = new HouseVillage();
        $whereVillage = [];
        $whereVillage[] = ['status', '=', 1];
        if (!empty($villageIds)) {
            $whereVillage[] = ['village_id', 'in', $villageIds];
        } else {
            $whereVillage[] = ['property_id', '=', $property_id];
        }
        $village_list = $db_house_village->getList($whereVillage, 'village_id,village_name');

        $nowTime = time();
        $payDate = isset($params['payDate']) ? $params['payDate'] : [];
        $isRangeJudgment = 1;
        $payRangeMonths = 0;
        if (empty($payDate)) {
            // 默认取 5个月前-当前月份
            $payDate[] = date('Y-m', strtotime("-5 months", $nowTime));
            $payDate[] = date('Y-m', $nowTime);
            $isRangeJudgment = 0;
            $payRangeMonths = 6;
        }
        $project_id = isset($params['project_id']) && !empty($params['project_id']) ? $params['project_id'] : [];
        $service_start_time = isset($params['service_start_time']) && $params['service_start_time'] ? @strtotime($params['service_start_time']) : '';
        $service_start_time && $service_start_time = @strtotime(date('Y-m-01', $service_start_time));
        $service_end_time = isset($params['service_end_time']) && $params['service_end_time'] ? @strtotime($params['service_end_time'].' 23:59:59') : '';
        $service_end_time && $service_end_time = @strtotime(date('Y-m-t 23:59:59', $service_end_time));

        $payDateStart = isset($payDate[0]) ? @strtotime($payDate[0]) : 0;
        // 转换为这个对应月份的1号日期最开始
        $payDateStart && $payDateStart = @strtotime(date('Y-m-01', $payDateStart));

        $payDateEnd = isset($payDate[1]) ? @strtotime($payDate[1] . ' 23:59:59') : 0;
        // 转换为这个对应月份的最后一天日期最后
        $payDateEnd && $payDateEnd = @strtotime(date('Y-m-t 23:59:59', $payDateEnd));

        fdump_api(['isRangeJudgment' => $isRangeJudgment,'payDateStart' => $payDateStart, '__LINE__' => __LINE__, 'payDateEnd' => $payDateEnd], '$villageYearlyBill');

        if ($isRangeJudgment == 1 && $payDateStart > 0 && $payDateEnd > 0) {
            $dayPayEnd = date('Y-m-d', strtotime("+12 months", $payDateStart));
            fdump_api(['$dayPayEnd' => $dayPayEnd], '$villageYearlyBill', true);
            $dayPayEnd && $dayPayEnd = strtotime($dayPayEnd);
            fdump_api(['$dayPayEnd' => $dayPayEnd, '$payDateEnd' => $payDateEnd], '$villageYearlyBill', true);
            if ($dayPayEnd <= $payDateEnd) {
                throw new \think\Exception("支付时间跨年查询不能超过12个月！");
            }
        }
        if ($isRangeJudgment == 1 && $payDateStart > 0 && $payDateEnd > 0) {
            $startTime = new \DateTime();
            $endTime = new \DateTime();
            $startTime->setTimestamp($payDateStart);
            $endTime->setTimestamp($payDateEnd);
            $payRangeMonths = $startTime->diff($endTime)->m + 1;
        }
        $dateList = [];
        for ($i = 0; $i < $payRangeMonths; $i++) {
            $dayInterval =  strtotime("+{$i} months", $payDateStart);
            $dayDate = date('Y-m', $dayInterval);
            $dateList[$dayDate] = [
                'order_date' => $dayDate,
                'dateText' => date('Y年m月', $dayInterval),
                'online_pay_total' => 0,
                'offline_pay_total' => 0,
                'pay_total' => 0,
            ];
        }
        fdump_api(['dateList' => $dateList, '$payRangeMonths' => $payRangeMonths], '$villageYearlyBill', 1);
        $totalDataList = $dateList;
        $totalMoney = 0;
        if (! empty($village_list)) {
            $dbHouseNewPayOrder = new HouseNewPayOrder();
            if (!is_array($village_list)) {
                $village_list = $village_list->toArray();
            }
            foreach ($village_list as &$item) {
                $where = [];
                $where[] = ['village_id', '=', $item['village_id']];
                $where[] = ['is_paid', '=', 1];
                $where[] = ['is_discard', '=', 1];
                $where[] = ['order_type', '<>', 'non_motor_vehicle'];
                if ($payDateStart > 0 && $payDateEnd > 0) {
                    $where[] = ['pay_time', '>=', $payDateStart];
                    $where[] = ['pay_time', '<=', $payDateEnd];
                }
                if($service_start_time && $service_start_time > 100){
                    $where[] = ['service_start_time','>=',$service_start_time];
                }
                if($service_end_time && $service_end_time > 100){
                    $where[] = ['service_end_time','<=',$service_end_time];
                }
                if (!empty($project_id)) {
                    $where[] = ['project_id','in',$project_id];
                }
                $wherePay = '(`is_refund`=1 OR `refund_money`<`modify_money`)';
                $payField = 'order_id, pay_time, pay_money';
                $villageDateList = $dateList;

                $whereOnlinePay = $where;
                $whereOnlinePay[] = ['pay_type', 'in', [1, 4]];

                $whereOfflinePay = $where;
                $whereOfflinePay[] = ['pay_type', 'not in', [1, 4]];

                $villageTotal = 0;

                // 优先读取缓存的
                $redisParams = [
                    'function' => 'villageYearlyBill', 'whereOnlinePay' => $whereOnlinePay, 'whereOfflinePay' => $whereOfflinePay,
                ];
                $result = $this->requestResult($redisParams);
                if (isset($result['villageDateList']) && !empty($result['villageDateList'])) {
                    foreach ($result['villageDateList'] as $redisKey => $redisItem) {
                        $order_date = isset($redisItem['order_date']) ? $redisItem['order_date'] : $redisKey;
                        $online_pay_total = isset($redisItem['online_pay_total']) && floatval($redisItem['online_pay_total']) > 0 ? $redisItem['online_pay_total'] : 0;
                        $offline_pay_total = isset($redisItem['offline_pay_total']) && floatval($redisItem['offline_pay_total']) > 0 ? $redisItem['offline_pay_total'] : 0;
                        if (isset($totalDataList[$order_date])) {
                            $totalDataList[$order_date]['online_pay_total'] += $online_pay_total;
                            $totalDataList[$order_date]['online_pay_total'] = formatNumber($totalDataList[$order_date]['online_pay_total'], 2, 1);
                            $totalDataList[$order_date]['pay_total'] += $online_pay_total;
                            $totalDataList[$order_date]['offline_pay_total'] += $offline_pay_total;
                            $totalDataList[$order_date]['offline_pay_total'] = formatNumber($totalDataList[$order_date]['offline_pay_total'], 2, 1);
                            $totalDataList[$order_date]['pay_total'] += $offline_pay_total;
                            $totalDataList[$order_date]['pay_total'] = formatNumber($totalDataList[$order_date]['pay_total'], 2, 1);
                            $villageTotal += ($online_pay_total + $offline_pay_total);
                        }
                    }
                    $item['redis'] = 1;
                    $item['villageDateList'] = array_values($villageDateList);
                    $item['villageTotal'] = formatNumber($villageTotal, 2, 1);
                    $totalMoney += floatval($item['villageTotal']);
                    continue;
                }

                $onlinePay = $dbHouseNewPayOrder->where($whereOnlinePay)->where($wherePay)->field($payField)->select()->toArray();

                foreach ($onlinePay as $onlineItem) {
                    $order_date = isset($onlineItem['pay_time']) ? @date('Y-m', $onlineItem['pay_time']) : '';
                    if (! $order_date) {
                        continue;
                    }
                    $online_pay_total = isset($onlineItem['pay_money']) && floatval($onlineItem['pay_money']) > 0 ? $onlineItem['pay_money'] : 0;
                    if (isset($villageDateList[$order_date])) {
                        $villageDateList[$order_date]['online_pay_total'] += $online_pay_total;
                        $villageDateList[$order_date]['pay_total'] += $online_pay_total;
                        $villageDateList[$order_date]['online_pay_total'] = formatNumber($villageDateList[$order_date]['online_pay_total'], 2, 1);
                        $villageDateList[$order_date]['pay_total'] = formatNumber($villageDateList[$order_date]['pay_total'], 2, 1);
                        $villageTotal += $online_pay_total;
                    }
                    if (isset($totalDataList[$order_date])) {
                        $totalDataList[$order_date]['online_pay_total'] += $online_pay_total;
                        $totalDataList[$order_date]['pay_total'] += $online_pay_total;
                        $totalDataList[$order_date]['online_pay_total'] = formatNumber($totalDataList[$order_date]['online_pay_total'], 2, 1);
                        $totalDataList[$order_date]['pay_total'] = formatNumber($totalDataList[$order_date]['pay_total'], 2, 1);
                    }
                }

                $offlinePay = $dbHouseNewPayOrder->where($whereOfflinePay)->where($wherePay)->field($payField)->select()->toArray();
                foreach ($offlinePay as $offlineItem) {
                    $order_date = isset($offlineItem['pay_time']) ? @date('Y-m', $offlineItem['pay_time']) : '';
                    if (! $order_date) {
                        continue;
                    }
                    $offline_pay_total = isset($offlineItem['pay_money']) && floatval($offlineItem['pay_money']) > 0 ? $offlineItem['pay_money'] : 0;
                    if (isset($villageDateList[$order_date])) {
                        $villageDateList[$order_date]['offline_pay_total'] += $offline_pay_total;
                        $villageDateList[$order_date]['pay_total'] += $offline_pay_total;
                        $villageDateList[$order_date]['offline_pay_total'] = formatNumber($villageDateList[$order_date]['offline_pay_total'], 2, 1);
                        $villageDateList[$order_date]['pay_total'] = formatNumber($villageDateList[$order_date]['pay_total'], 2, 1);
                        $villageTotal += $offline_pay_total;
                    }
                    if (isset($totalDataList[$order_date])) {
                        $totalDataList[$order_date]['offline_pay_total'] += $offline_pay_total;
                        $totalDataList[$order_date]['pay_total'] += $offline_pay_total;
                        $totalDataList[$order_date]['offline_pay_total'] = formatNumber($totalDataList[$order_date]['offline_pay_total'], 2, 1);
                        $totalDataList[$order_date]['pay_total'] = formatNumber($totalDataList[$order_date]['pay_total'], 2, 1);
                    }
                }
                $this->requestResult($params, ['villageDateList' => $villageDateList]);
                $item['villageDateList'] = array_values($villageDateList);
                $item['villageTotal'] = formatNumber($villageTotal, 2, 1);
                $totalMoney += floatval($item['villageTotal']);
                $item['redis'] = 0;
            }
        }
        return [
            'list' => $village_list,
            'totalDataList' => $totalDataList,
            'totalMoney' => formatNumber($totalMoney, 2, 1),
            'payDate' => $payDate,
            'service_start_time' => isset($params['service_start_time']) ? $params['service_start_time'] : '',
            'service_end_time' => isset($params['service_end_time']) ? $params['service_end_time'] : '',
            'project_id' => $project_id ? $project_id : [],
        ];
    }

    /**
     * 小区年度已缴账单导出-队列.
     */
    public function villageYearlyBillExport($params) {
        $houseListDatasExportOutService = new HouseListDatasExportOutService();
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $property_name = isset($params['property_name']) ? trim($params['property_name']) : '';
        $exportParam = array();
        $exportParam['property_id'] = $property_id;
        $exportParam['exportParams'] = $params;
        $exportParam['export_type'] = 'village_yearly_bill_export';
        $exportParam['export_title'] = ($property_name ? "【{$property_name}】" : '') . '年度已缴账单';
        $exportInfo = $houseListDatasExportOutService->addExportLog(0, $exportParam);
        $site_url = cfg('site_url');
        $site_url = trim($site_url, '/');
        $exportInfo['excelExportOutFileUrl'] = $site_url . '/index.php?g=Index&c=ExportFile&a=download_house_export_file&export_from=village_yearly_bill_export&id=' . $exportInfo['export_id'];
        return $exportInfo;
    }

    /**
     * 小区年度应收报表.
     */
    public function villageYearlyReceivable($params,$isExport=false)
    {
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $villageIds = isset($params['villageIds']) ? $params['villageIds'] : [];
        $db_house_village = new HouseVillage();
        $whereVillage = [];
        $whereVillage[] = ['status', '=', 1];
        if (!empty($villageIds)) {
            $whereVillage[] = ['village_id', 'in', $villageIds];
        } else {
            $whereVillage[] = ['property_id', '=', $property_id];
        }
        $village_list = $db_house_village->getList($whereVillage, 'village_id,village_name');

        $nowTime = time();
        $project_id = isset($params['project_id']) && !empty($params['project_id']) ? $params['project_id'] : [];
        $service_time = isset($params['service_time']) && $params['service_time'] ? $params['service_time'] : [];
        $currentYear=date('Y');
        if (empty($service_time)) {
            // 默认取 2年前-当前年份
            $service_time[] = ($currentYear-2).'-01-01';
            $service_time[] =$currentYear.'-12-31';
        }
        $service_start_time=0;
        $start_year=0;
        if (isset($service_time[0]) && $service_time[0]) {
            $tmpArr=explode('-',$service_time[0]);
            $service_start_time= strtotime($tmpArr['0'].'-01-01 00:00:00');
            $service_time[0]=$tmpArr['0'].'-01-01';
            $start_year=$tmpArr['0'];
        }
        $service_end_time=0;
        $end_year=0;
        if (isset($service_time[1]) && $service_time[1]) {
            $tmpArr=explode('-',$service_time[1]);
            $service_end_time= strtotime($tmpArr['0'].'-12-31 23:59:59');
            $service_time[1]=$tmpArr['0'].'-12-31';
            $end_year=$tmpArr['0'];
        }
        if($start_year>$end_year){
            throw new \think\Exception("计费开始年不能大于结束年");
        }
        $year_num=$end_year-$start_year;
        if ($year_num>12) {
            throw new \think\Exception("计费时间范围查询不能超过12年！");
        }
        $dbHouseNewPayOrder = new HouseNewPayOrder();
        $year_columns=array();
        $totalOrderArr=array('village_id'=>0,'village_name'=>'合计','import_overdue_bills'=>0);
        for ($year = $start_year; $year <= $end_year; $year++) {
            $totalOrderArr['year_num']=$year;
            $totalOrderArr['year_money_'.$year]=0;
            $totalOrderArr['year_str']=$year.'年';
            $year_columns[]=array('xname'=>$year.'年','xvalue'=>'year_money_'.$year,'year_num'=>$year);
        }
        $year_columns[]=array('xname'=>'导入欠缴账单欠费','xvalue'=>'import_overdue_bills','year_num'=>'');
        $yearOrderArr=array();
        $villageOrderArr=array();
        if (! empty($village_list)) {
            
            foreach ($village_list as $item) {
                $orderArr=array('village_id'=>$item['village_id'],'village_name'=>$item['village_name'],'import_overdue_bills'=>0);
                
                $whereBase=array(['village_id', '=', $item['village_id']],['is_paid', '=', 2],['is_discard', '=', 1]);
                $whereBase[]= ['order_type', '<>', 'non_motor_vehicle'];
                if (!empty($project_id)) {
                    $whereBase[] = ['project_id','in',$project_id];
                }
                $whereBase[] = ['total_money','>',0];
                $wherePay = "(`room_id` > 0 OR `position_id` >0)";
                for ($year = $start_year; $year <= $end_year; $year++) {
                    $orderArr['year_num']=$year;
                    $orderArr['year_str']=$year.'年';
                    $orderArr['year_money_'.$year]=0;
                    $tmp_start_time=strtotime($year.'-01-01 00:00:00');
                    $tmp_start_time_end=strtotime($year.'-12-31 23:59:59');
                    $where=$whereBase;
                    $where[] = ['service_start_time','>=',$tmp_start_time];
                    $where[] = ['service_start_time','<=',$tmp_start_time_end];
                    $where[] = ['from', '<>', 3];
                    $where[] = ['service_end_time','<=',$service_end_time];
                    $where['string']=$wherePay;
                    //$total_money = $dbHouseNewPayOrder->sumMoney($where,$wherePay,'total_money');
                    $onePayOrderObj=$dbHouseNewPayOrder->get_one($where,'sum(ROUND(total_money,2)) as xtotal_money');
                    if($onePayOrderObj && isset($onePayOrderObj['xtotal_money'])){
                        $orderArr['year_money_'.$year]=$onePayOrderObj['xtotal_money']>0 ? round($onePayOrderObj['xtotal_money'],2):0;
                    }
                    if(!isset($yearOrderArr[$year])){
                        $yearOrderArr[$year]=0;
                    }
                    $yearOrderArr[$year]+=$orderArr['year_money_'.$year];
                }
                $whereImportOrder=$whereBase;
                $whereImportOrder[] = ['service_start_time','>=',$service_start_time];
                $whereImportOrder[] = ['from', '=', 3];
                $whereImportOrder[] = ['service_end_time','<=',$service_end_time];
                $whereImportOrder['string']=$wherePay;
                $onePayOrderObj=$dbHouseNewPayOrder->get_one($whereImportOrder,'sum(ROUND(total_money,2)) as xtotal_money');
                if($onePayOrderObj && isset($onePayOrderObj['xtotal_money'])){
                    $orderArr['import_overdue_bills']=$onePayOrderObj['xtotal_money']>0 ? round($onePayOrderObj['xtotal_money'],2):0;
                }
                $totalOrderArr['import_overdue_bills'] +=$orderArr['import_overdue_bills'];
                $villageOrderArr[]=$orderArr;
            }
            foreach($yearOrderArr as $kky=>$vvy){
                $totalOrderArr['year_money_'.$kky]=$vvy>0 ? round($vvy,2):0;
            }
            $villageOrderArr[]=$totalOrderArr;
        }
        return [
            'list' => $villageOrderArr,
            'year_columns'=>$year_columns,
            'service_time' => $service_time,
            'project_id' => $project_id ? $project_id : []
        ];
    }

    /**
     * 小区年度应收报表导出-队列.
     */
    public function villageYearlyReceivableExport($params) {
        $houseListDatasExportOutService = new HouseListDatasExportOutService();
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $property_name = isset($params['property_name']) ? trim($params['property_name']) : '';
        $exportParam = array();
        $exportParam['property_id'] = $property_id;
        $exportParam['exportParams'] = $params;
        $exportParam['export_type'] = 'village_yearly_receivable_export';
        $exportParam['export_title'] = ($property_name ? "【{$property_name}】" : '') . '年度应收报表';
        $exportInfo = $houseListDatasExportOutService->addExportLog(0, $exportParam);
        $site_url = cfg('site_url');
        $site_url = trim($site_url, '/');
        $exportInfo['excelExportOutFileUrl'] = $site_url . '/index.php?g=Index&c=ExportFile&a=download_house_export_file&export_from=village_yearly_receivable_export&id=' . $exportInfo['export_id'];
        return $exportInfo;
    }

    protected function requestResult($param, $result = [], $isLong = 0, $time = 0) {
        return $result;
        $cacheTag  = PropertyOrderConst::PROPERTY_ORDER_REDIS_TAG;
        $cacheKey  = PropertyOrderConst::PROPERTY_ORDER_REQUEST_REDIS_KEY . md5(\json_encode($param));
        if ($time <= 0) {
            $time = $isLong == 1 ? PropertyOrderConst::PROPERTY_ORDER_REQUEST_LONG_REDIS_TIMES : PropertyOrderConst::PROPERTY_ORDER_REQUEST_REDIS_TIMES;
        }
        if (empty($result)) {
            try {
                $resultJson = Cache::store('redis')->get($cacheKey);
            } catch (\Exception $e) {
                fdump_api(['执行错误' => $e->getMessage(), 'line' => __LINE__, 'param' => $param, 'errline' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'PropertyBillOrderService/requestResult');
                return [];
            } catch (\Throwable $e) {
                fdump_api(['执行错误' => $e->getMessage(), 'line' => __LINE__, 'param' => $param, 'errline' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'PropertyBillOrderService/requestResult');
                return [];
            } catch (\RuntimeException $e) {
                fdump_api(['执行错误' => $e->getMessage(), 'line' => __LINE__, 'param' => $param, 'errline' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'PropertyBillOrderService/requestResult');
                return [];
            } catch (\InvalidArgumentException $e) {
                fdump_api(['执行错误' => $e->getMessage(), 'line' => __LINE__, 'param' => $param, 'errline' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'PropertyBillOrderService/requestResult');
                return [];
            }
            if ($resultJson) {
                $result  = json_decode($resultJson, true);
                return $result;
            } else {
                return [];
            }
        } else {
            try {
                $resultJson = json_encode($result, JSON_UNESCAPED_UNICODE);
                Cache::store('redis')->tag($cacheTag)->set($cacheKey,$resultJson, $time);
            } catch (\Exception $e) {
                fdump_api(['执行错误' => $e->getMessage(), 'line' => __LINE__, 'param' => $param, 'errline' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'PropertyBillOrderService/requestResult');
                return [];
            } catch (\Throwable $e) {
                fdump_api(['执行错误' => $e->getMessage(), 'line' => __LINE__, 'param' => $param, 'errline' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'PropertyBillOrderService/requestResult');
                return [];
            } catch (\RuntimeException $e) {
                fdump_api(['执行错误' => $e->getMessage(), 'line' => __LINE__, 'param' => $param, 'errline' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'PropertyBillOrderService/requestResult');
                return [];
            } catch (\InvalidArgumentException $e) {
                fdump_api(['执行错误' => $e->getMessage(), 'line' => __LINE__, 'param' => $param, 'errline' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'PropertyBillOrderService/requestResult');
                return [];
            }
        }
        return $result;
    }

    /**
     * 导出表列字母.
     */
    public function exportCells()
    {
        return [
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N',
            'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
            'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN',
            'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ',
            'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM', 'BN',
            'BO', 'BP', 'BQ', 'BR', 'BS', 'BT', 'BU', 'BV', 'BW', 'BX', 'BY', 'BZ',
            'CA', 'CB', 'CC', 'CD', 'CE', 'CF', 'CG', 'CH', 'CI', 'CJ', 'CK', 'CL', 'CM', 'CN',
            'CO', 'CP', 'CQ', 'CR', 'CS', 'CT', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ'
        ];
    }

    /**
     * 物业小区月度已缴报表导出-执行.
     */
    public function handleVillageMonthlyBillExport($params)
    {
        $exportParams = isset($params['exportParams']) && $params['exportParams'] ? $params['exportParams'] : [];
        $result = $this->villageMonthlyBill($exportParams);
        $village_list = isset($result['list']) ? $result['list'] : [];
        $totalDataList = isset($result['totalDataList']) ? $result['totalDataList'] : [];
        $totalMoney = isset($result['totalMoney']) ? $result['totalMoney'] : 0;
        $is_redis_queue = isset($params['is_redis_queue']) ? $params['is_redis_queue'] : 1;
        fdump_api(['params' => $params, '__LINE__' => __LINE__, '$result' => $result], '$handleVillageMonthlyBillExport', true);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getDefaultColumnDimension()->setWidth(20);
        $sheet->getRowDimension(1)->setRowHeight(25);//设置行高
        $sheet->getRowDimension(2)->setRowHeight(25);//设置行高

        $exportCells = $this->exportCells();
        $sheet->mergeCells("A1:A2");
        $sheet->setCellValue("A1", '小区/日期');
        $keyCell = 0;
        $recordStartWord = 'A';
        foreach ($totalDataList as $totalItem) {
            $dateText = isset($totalItem['dateText']) ? trim($totalItem['dateText']) : '';
            !$dateText && $dateText = isset($totalItem['order_date']) ? trim($totalItem['order_date']) : '';
            $start = $keyCell + 1;
            $end = $keyCell + 3;
            $wordStart = isset($exportCells[$start]) ? $exportCells[$start] : '';
            $wordMiddle = isset($exportCells[$start+1]) ? $exportCells[$start+1] : '';
            $wordEnd = isset($exportCells[$end]) ? $exportCells[$end] : '';
            // 第1行 除了 第1列 外 需要填充数据的  每3列合并单元格 并设置标题
            $sheet->mergeCells("{$wordStart}1:{$wordEnd}1");
            $sheet->setCellValue("{$wordStart}1", $dateText);
            // 第2行 除了 第一列 外 一次填充  线上 线下 合计
            $sheet->setCellValue("{$wordStart}2", '线上');
            $sheet->setCellValue("{$wordMiddle}2", '线下');
            $sheet->setCellValue("{$wordEnd}2", '合计');
            $keyCell = $end;
        }
        $recordEndWord = isset($exportCells[$keyCell+1]) ? $exportCells[$keyCell+1] : '';
        $sheet->mergeCells("{$recordEndWord}1:{$recordEndWord}2");
        $sheet->setCellValue("{$recordEndWord}1", '共计');

        fdump_api(['village_list' => $village_list,'recordEndWord' => $recordEndWord,'keyCell' => $keyCell, '__LINE__' => __LINE__, 'recordStartWord' => $recordStartWord], '$handleVillageMonthlyBillExport', true);
        // 从第3行开始写入数据
        $row = 3;
        foreach ($village_list as $listItem) {
            $villageDateList = isset($listItem['villageDateList']) ? $listItem['villageDateList'] : [];
            $village_name = isset($listItem['village_name']) ? trim($listItem['village_name']) : '';
            $villageTotal = isset($listItem['villageTotal']) ? trim($listItem['villageTotal']) : '';
            //单元格内容写入
            $sheet->setCellValue('A' . $row, $village_name);
            $keyDateCell = 0;
            foreach ($villageDateList as $dateItem) {
                $online_pay_total = isset($dateItem['online_pay_total']) ? strval($dateItem['online_pay_total']) : 0;
                $offline_pay_total = isset($dateItem['offline_pay_total']) ? strval($dateItem['offline_pay_total']) : 0;
                $pay_total = isset($dateItem['pay_total']) ? strval($dateItem['pay_total']) : 0;
                $start = $keyDateCell + 1;
                $end = $keyDateCell + 3;
                $wordStart = isset($exportCells[$start]) ? $exportCells[$start] : '';
                $wordMiddle = isset($exportCells[$start+1]) ? $exportCells[$start+1] : '';
                $wordEnd = isset($exportCells[$end]) ? $exportCells[$end] : '';
                // 第$row行 除了 第一列 外 一次填充  线上 线下 合计
                $sheet->setCellValue("{$wordStart}{$row}", $online_pay_total);
                $sheet->setCellValue("{$wordMiddle}{$row}", $offline_pay_total);
                $sheet->setCellValue("{$wordEnd}{$row}", $pay_total);
                $keyDateCell = $end;
            }
            $recordEndWordValue = isset($exportCells[$keyDateCell+1]) ? $exportCells[$keyDateCell+1] : '';
            $sheet->setCellValue("{$recordEndWordValue}{$row}", $villageTotal);
            fdump_api(['keyDateCell' => $keyDateCell,'row' => $row,'villageTotal' => $villageTotal, '__LINE__' => __LINE__], '$handleVillageMonthlyBillExport', true);
            $row++;
        }
        fdump_api(['keyDateCell' => $keyDateCell,'row' => $row, '__LINE__' => __LINE__], '$handleVillageMonthlyBillExport', true);

        $keyTotalCell = 0;
        //单元格内容写入
        $sheet->getRowDimension($row)->setRowHeight(25);//设置行高
        $sheet->setCellValue('A' . $row, '共计');
        foreach ($totalDataList as $totalItem) {
            $online_pay_total = isset($totalItem['online_pay_total']) ? strval($totalItem['online_pay_total']) : 0;
            $offline_pay_total = isset($totalItem['offline_pay_total']) ? strval($totalItem['offline_pay_total']) : 0;
            $pay_total = isset($totalItem['pay_total']) ? strval($totalItem['pay_total']) : 0;
            $start = $keyTotalCell + 1;
            $end = $keyTotalCell + 3;
            $wordStart = isset($exportCells[$start]) ? $exportCells[$start] : '';
            $wordMiddle = isset($exportCells[$start+1]) ? $exportCells[$start+1] : '';
            $wordEnd = isset($exportCells[$end]) ? $exportCells[$end] : '';
            // 第$row行 除了 第一列 外 一次填充  线上 线下 合计
            $sheet->setCellValue("{$wordStart}{$row}", $online_pay_total);
            $sheet->setCellValue("{$wordMiddle}{$row}", $offline_pay_total);
            $sheet->setCellValue("{$wordEnd}{$row}", $pay_total);
            $keyTotalCell = $end;
        }
        $recordEndWordTotal = isset($exportCells[$keyTotalCell+1]) ? $exportCells[$keyTotalCell+1] : '';
        $sheet->setCellValue("{$recordEndWordTotal}{$row}", $totalMoney);
        fdump_api(['keyTotalCell' => $keyTotalCell,'row' => $row,'totalMoney' => $totalMoney, '__LINE__' => __LINE__], '$handleVillageMonthlyBillExport', true);
        //保存
        $styleArrayBody = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        $total_rows = $row + 1;
        //添加所有边框/居中
        $sheet->getStyle("{$recordStartWord}1:{$recordEndWord}1" . $total_rows)->applyFromArray($styleArrayBody);

        $filename = date("YmdHis") . 'village_monthly_bill_export-' . rand(10000, 99999) . '.xlsx';
        fdump_api(['filename' => $filename,'row' => $row], '$handleVillageMonthlyBillExport', true);
        //下载
        $root = dirname(dirname(dirname(__DIR__)));
        $root = dirname(dirname($root));
        $document_root = rtrim($root, '/');
        fdump_api(['document_root' => $document_root], '$handleVillageMonthlyBillExport', true);
        if (!$document_root) {
            $document_root = rtrim(app()->getRootPath(), '/');
            $basePath = $document_root . '/runtime/';
        } else {
            $basePath = $document_root . '/runtime/';
        }
        fdump_api(['filename' => $filename,'basePath' => $basePath,'is_redis_queue' => $is_redis_queue], '$handleVillageMonthlyBillExport', true);

        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet, $basePath);
        if ($is_redis_queue == 1) {
            $export_id = isset($params['export_id']) ? intval($params['export_id']) : 0;
            $exportLogDb = new ExportLog();
            $whereArr = array('export_id' => $export_id);
            $saveArr = array('status' => 1, 'file_name' => $filename);
            $exportLogDb->saveOneData($whereArr,$saveArr);
            return $filename;
        }
        return $this->downloadExportFile($filename);
    }

    /**
     * 物业小区月度已缴报表导出-执行.
     */
    public function handleVillageYearlyBillExport($params)
    {
        $exportParams = isset($params['exportParams']) && $params['exportParams'] ? $params['exportParams'] : [];
        $result = $this->villageYearlyBill($exportParams);
        $village_list = isset($result['list']) ? $result['list'] : [];
        $totalDataList = isset($result['totalDataList']) ? $result['totalDataList'] : [];
        $totalMoney = isset($result['totalMoney']) ? $result['totalMoney'] : 0;
        $is_redis_queue = isset($params['is_redis_queue']) ? $params['is_redis_queue'] : 1;
        fdump_api(['params' => $params, '__LINE__' => __LINE__, '$result' => $result], '$handleVillageYearlyBillExport', true);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getDefaultColumnDimension()->setWidth(20);
        $sheet->getRowDimension(1)->setRowHeight(25);//设置行高
        $sheet->getRowDimension(2)->setRowHeight(25);//设置行高

        $exportCells = $this->exportCells();
        $sheet->mergeCells("A1:A2");
        $sheet->setCellValue("A1", '小区/日期');
        $keyCell = 0;
        $recordStartWord = 'A';
        foreach ($totalDataList as $totalItem) {
            $dateText = isset($totalItem['dateText']) ? trim($totalItem['dateText']) : '';
            !$dateText && $dateText = isset($totalItem['order_date']) ? trim($totalItem['order_date']) : '';
            $start = $keyCell + 1;
            $end = $keyCell + 3;
            $wordStart = isset($exportCells[$start]) ? $exportCells[$start] : '';
            $wordMiddle = isset($exportCells[$start+1]) ? $exportCells[$start+1] : '';
            $wordEnd = isset($exportCells[$end]) ? $exportCells[$end] : '';
            // 第1行 除了 第1列 外 需要填充数据的  每3列合并单元格 并设置标题
            $sheet->mergeCells("{$wordStart}1:{$wordEnd}1");
            $sheet->setCellValue("{$wordStart}1", $dateText);
            // 第2行 除了 第一列 外 一次填充  线上 线下 合计
            $sheet->setCellValue("{$wordStart}2", '线上');
            $sheet->setCellValue("{$wordMiddle}2", '线下');
            $sheet->setCellValue("{$wordEnd}2", '合计');
            $keyCell = $end;
        }
        $recordEndWord = isset($exportCells[$keyCell+1]) ? $exportCells[$keyCell+1] : '';
        $sheet->mergeCells("{$recordEndWord}1:{$recordEndWord}2");
        $sheet->setCellValue("{$recordEndWord}1", '共计');

        fdump_api(['village_list' => $village_list,'recordEndWord' => $recordEndWord,'keyCell' => $keyCell, '__LINE__' => __LINE__, 'recordStartWord' => $recordStartWord], '$handleVillageYearlyBillExport', true);
        // 从第3行开始写入数据
        $row = 3;
        foreach ($village_list as $listItem) {
            $villageDateList = isset($listItem['villageDateList']) ? $listItem['villageDateList'] : [];
            $village_name = isset($listItem['village_name']) ? trim($listItem['village_name']) : '';
            $villageTotal = isset($listItem['villageTotal']) ? trim($listItem['villageTotal']) : '';
            //单元格内容写入
            $sheet->setCellValue('A' . $row, $village_name);
            $keyDateCell = 0;
            foreach ($villageDateList as $dateItem) {
                $online_pay_total = isset($dateItem['online_pay_total']) ? strval($dateItem['online_pay_total']) : 0;
                $offline_pay_total = isset($dateItem['offline_pay_total']) ? strval($dateItem['offline_pay_total']) : 0;
                $pay_total = isset($dateItem['pay_total']) ? strval($dateItem['pay_total']) : 0;
                $start = $keyDateCell + 1;
                $end = $keyDateCell + 3;
                $wordStart = isset($exportCells[$start]) ? $exportCells[$start] : '';
                $wordMiddle = isset($exportCells[$start+1]) ? $exportCells[$start+1] : '';
                $wordEnd = isset($exportCells[$end]) ? $exportCells[$end] : '';
                // 第$row行 除了 第一列 外 一次填充  线上 线下 合计
                $sheet->setCellValue("{$wordStart}{$row}", $online_pay_total);
                $sheet->setCellValue("{$wordMiddle}{$row}", $offline_pay_total);
                $sheet->setCellValue("{$wordEnd}{$row}", $pay_total);
                $keyDateCell = $end;
            }
            $recordEndWordValue = isset($exportCells[$keyDateCell+1]) ? $exportCells[$keyDateCell+1] : '';
            $sheet->setCellValue("{$recordEndWordValue}{$row}", $villageTotal);
            fdump_api(['keyDateCell' => $keyDateCell,'row' => $row,'villageTotal' => $villageTotal, '__LINE__' => __LINE__], '$handleVillageYearlyBillExport', true);
            $row++;
        }
        fdump_api(['keyDateCell' => $keyDateCell,'row' => $row, '__LINE__' => __LINE__], '$handleVillageYearlyBillExport', true);

        $keyTotalCell = 0;
        //单元格内容写入
        $sheet->getRowDimension($row)->setRowHeight(25);//设置行高
        $sheet->setCellValue('A' . $row, '共计');
        foreach ($totalDataList as $totalItem) {
            $online_pay_total = isset($totalItem['online_pay_total']) ? strval($totalItem['online_pay_total']) : 0;
            $offline_pay_total = isset($totalItem['offline_pay_total']) ? strval($totalItem['offline_pay_total']) : 0;
            $pay_total = isset($totalItem['pay_total']) ? strval($totalItem['pay_total']) : 0;
            $start = $keyTotalCell + 1;
            $end = $keyTotalCell + 3;
            $wordStart = isset($exportCells[$start]) ? $exportCells[$start] : '';
            $wordMiddle = isset($exportCells[$start+1]) ? $exportCells[$start+1] : '';
            $wordEnd = isset($exportCells[$end]) ? $exportCells[$end] : '';
            // 第$row行 除了 第一列 外 一次填充  线上 线下 合计
            $sheet->setCellValue("{$wordStart}{$row}", $online_pay_total);
            $sheet->setCellValue("{$wordMiddle}{$row}", $offline_pay_total);
            $sheet->setCellValue("{$wordEnd}{$row}", $pay_total);
            $keyTotalCell = $end;
        }
        $recordEndWordTotal = isset($exportCells[$keyTotalCell+1]) ? $exportCells[$keyTotalCell+1] : '';
        $sheet->setCellValue("{$recordEndWordTotal}{$row}", $totalMoney);
        fdump_api(['keyTotalCell' => $keyTotalCell,'row' => $row,'totalMoney' => $totalMoney, '__LINE__' => __LINE__], '$handleVillageYearlyBillExport', true);
        //保存
        $styleArrayBody = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        $total_rows = $row + 1;
        //添加所有边框/居中
        $sheet->getStyle("{$recordStartWord}1:{$recordEndWord}1" . $total_rows)->applyFromArray($styleArrayBody);

        $filename = date("YmdHis") . 'village_yearly_bill_export-' . rand(10000, 99999) . '.xlsx';
        fdump_api(['filename' => $filename,'row' => $row], '$handleVillageYearlyBillExport', true);
        //下载
        $root = dirname(dirname(dirname(__DIR__)));
        $root = dirname(dirname($root));
        $document_root = rtrim($root, '/');
        fdump_api(['document_root' => $document_root], '$handleVillageYearlyBillExport', true);
        if (!$document_root) {
            $document_root = rtrim(app()->getRootPath(), '/');
            $basePath = $document_root . '/runtime/';
        } else {
            $basePath = $document_root . '/runtime/';
        }
        fdump_api(['filename' => $filename,'basePath' => $basePath,'is_redis_queue' => $is_redis_queue], '$handleVillageYearlyBillExport', true);

        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet, $basePath);
        if ($is_redis_queue == 1) {
            $export_id = isset($params['export_id']) ? intval($params['export_id']) : 0;
            $exportLogDb = new ExportLog();
            $whereArr = array('export_id' => $export_id);
            $saveArr = array('status' => 1, 'file_name' => $filename);
            $exportLogDb->saveOneData($whereArr,$saveArr);
            return $filename;
        }
        return $this->downloadExportFile($filename);
    }

    /**
     * 物业小区年度应收报表导出-执行.
     */
    public function handleVillageYearlyReceivableExport($params)
    {
        $exportParams = isset($params['exportParams']) && $params['exportParams'] ? $params['exportParams'] : [];
        $result = $this->villageYearlyReceivable($exportParams);
        $village_list = isset($result['list']) ? $result['list'] : [];
        $year_columns = isset($result['year_columns']) ? $result['year_columns'] : [];
        $is_redis_queue = isset($params['is_redis_queue']) ? $params['is_redis_queue'] : 1;
        fdump_api(['params' => $params, '__LINE__' => __LINE__, 'result' => $result], 'handleVillageYearlyReceivableExport', true);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getDefaultColumnDimension()->setWidth(20);
        $sheet->getRowDimension(1)->setRowHeight(25);//设置行高
        $sheet->getRowDimension(2)->setRowHeight(25);//设置行高

        $exportCells = $this->exportCells();
        $sheet->setCellValue("A1", '小区/日期');
        $keyCell = 0;
        $recordStartWord = 'A';
        $recordEndWord = '';
        foreach ($year_columns as $vc) {
            $end = $keyCell + 1;
            $word = isset($exportCells[$end]) ? $exportCells[$end] : '';
            $recordEndWord = $word;
            $sheet->setCellValue($word."1", $vc['xname']);
            $keyCell = $end;
        }
        // 从第2行开始写入数据
        $row = 2;
        foreach ($village_list as $listItem) {
            //单元格内容写入
            $sheet->setCellValue('A' . $row, $listItem['village_name']);
            $keyDateCell=0;
            foreach ($year_columns as $vc) {
                $keyDateCell = $keyDateCell + 1;
                $word = isset($exportCells[$keyDateCell]) ? $exportCells[$keyDateCell] : '';
                $xvalue=isset($listItem[$vc['xvalue']]) ? $listItem[$vc['xvalue']]:'';
                $sheet->setCellValue($word.$row, $xvalue);
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
        $sheet->getStyle("{$recordStartWord}1:{$recordEndWord}1" . $total_rows)->applyFromArray($styleArrayBody);

        $filename = date("YmdHis") . 'village_yearly_receivable_export-' . rand(10000, 99999) . '.xlsx';
        fdump_api(['filename' => $filename,'row' => $row], '$handleVillageYearlyReceivableExport', true);
        //下载
        $root = dirname(dirname(dirname(__DIR__)));
        $root = dirname(dirname($root));
        $document_root = rtrim($root, '/');
        fdump_api(['document_root' => $document_root], '$handleVillageYearlyReceivableExport', true);
        if (!$document_root) {
            $document_root = rtrim(app()->getRootPath(), '/');
            $basePath = $document_root . '/runtime/';
        } else {
            $basePath = $document_root . '/runtime/';
        }
        fdump_api(['filename' => $filename,'basePath' => $basePath,'is_redis_queue' => $is_redis_queue], '$handleVillageYearlyReceivableExport', true);

        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet, $basePath);
        if ($is_redis_queue == 1) {
            $export_id = isset($params['export_id']) ? intval($params['export_id']) : 0;
            $exportLogDb = new ExportLog();
            $whereArr = array('export_id' => $export_id);
            $saveArr = array('status' => 1, 'file_name' => $filename);
            $exportLogDb->saveOneData($whereArr,$saveArr);
            return $filename;
        }
        return $this->downloadExportFile($filename);
    }

    /**
     * 下载表格
     */
    public function downloadExportFile($filename)
    {
        $returnArr = [];
        if (!file_exists(request()->server('DOCUMENT_ROOT') . '/v20/runtime/' . $filename)) {
            $returnArr['error'] = 1;
            return $returnArr;
        }
        $ua = request()->server('HTTP_USER_AGENT');
        $ua = strtolower($ua);
        if (preg_match('/msie/', $ua) || preg_match('/edge/', $ua) || preg_match('/trident/', $ua)) { //判断是否为IE或Edge浏览器
            $filename = str_replace('+', '%20', urlencode($filename)); //使用urlencode对文件名进行重新编码
        }
        $returnArr['url'] = cfg('site_url') . '/v20/runtime/' . $filename;
        $returnArr['error'] = 0;
        return $returnArr;
    }
}