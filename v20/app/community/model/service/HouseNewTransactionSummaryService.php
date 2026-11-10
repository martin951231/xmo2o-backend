<?php
/**
 * Created by PhpStorm.
 * 交易汇总
 * User: wanzy
 * DateTime: 2021/11/16 14:56
 */

namespace app\community\model\service;

use app\common\model\service\export\ExportService as BaseExportService;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\service\HousePropertyDigitService;
use app\community\model\service\HouseNewChargeService;
use app\community\model\db\HouseNewChargeProject;
use app\community\model\db\HouseNewChargeRule;
use app\community\model\db\HouseNewChargeStandardBind;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\VillageUserMoneyLog;
use app\community\model\db\HouseVillageSummaryRateRecord;
use app\community\model\db\ExportLog;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageInfo;
use app\common\model\service\config\ConfigCustomizationService;
use think\db\Raw;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use app\traits\HouseListDatasExportOutTraits;
use think\Exception;

class HouseNewTransactionSummaryService
{
    use HouseListDatasExportOutTraits;

    public function HouseFeeSummaryList($where = [], $village_id = 0, $property_id = 0, $page = 1, $limit = 10, $group = 'room_id')
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $whereAll = [];
        if ($village_id) {
            $where[] = ['village_id', '=', $village_id];
            $whereAll[] = ['village_id', '=', $village_id];
        } elseif ($property_id) {
            $where[] = ['property_id', '=', $property_id];
            $whereAll[] = ['property_id', '=', $property_id];
        }
        $db_house_property_digit_service = new HousePropertyDigitService();
        /*
        $digit_info = $db_house_property_digit_service->get_one_digit(['property_id'=>$property_id]);
        if (isset($digit_info['other_digit']) && isset($digit_info['type'])) {
            $other_digit = $digit_info['other_digit'];
            $type = $digit_info['type'];
        } else {
            $other_digit = 2;
            $type = 1;
        }*/
        $other_digit = 2;
        $type = 1;
        $other_whereArr = $where;
        if ($group == 'room_id') {
            $where[] = ['is_paid', '=', 1];
            $where[] = ['is_discard', '=', 1];
            $other_whereArr[] = ['is_discard', '=', 1];
        }
        $whereAll[] = ['is_paid', '=', 1];
        $whereAll[] = ['is_discard', '=', 1];
        $field = "order_id,uid,name,phone,order_type,order_name,sum(ROUND(if(f_order_id>0,0,total_money) ,{$other_digit})) as summaryTotalMoney,sum(ROUND(pay_money,{$other_digit})) as summaryPayMoney,sum(ROUND(refund_money,{$other_digit})) as summaryRefundMoney,property_id,village_id,room_id,rule_id,project_id";
        $listCount = $db_house_new_pay_order->HouseFeeSummaryList($where, 'uid,name', $group, 0);
        $count = count($listCount);
        $list = $db_house_new_pay_order->HouseFeeSummaryList($where, $field, $group, $page, $limit);
        if ($village_id) {
            $summaryInfo = $db_house_new_pay_order->HouseFeeSummaryList($whereAll, $field, 'village_id', 0);
            $searchSummaryInfo = $db_house_new_pay_order->HouseFeeSummaryList($where, $field, 'village_id', 0);
        } elseif ($property_id) {
            $summaryInfo = $db_house_new_pay_order->HouseFeeSummaryList($whereAll, $field, 'property_id', 0);
            $searchSummaryInfo = $db_house_new_pay_order->HouseFeeSummaryList($where, $field, 'village_id', 0);
        } else {
            $summaryInfo = [];
            $searchSummaryInfo = [];
        }
        if (!empty($summaryInfo)) {
            $summaryInfo = $summaryInfo->toArray();
            $summaryInfo = reset($summaryInfo);
        }
        if (!empty($searchSummaryInfo)) {
            $searchSummaryInfo = $searchSummaryInfo->toArray();
            $searchSummaryInfo = reset($searchSummaryInfo);
        }
        if (!empty($list)) {
            $list = $list->toArray();
        }
        if (!empty($list)) {
            $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
            $service_house_village = new HouseVillageService();
            $serviceHouseNewPorperty = new HouseNewChargeService();
            $charge_type_arr = $serviceHouseNewPorperty->charge_type;
            $houseNewChargeProject = new HouseNewChargeProject();
            $houseNewChargeRule = new HouseNewChargeRule();
            $houseVillageDb = new HouseVillage();
            $village_info = '';
            if ($village_id > 0) {
                $village_info = $houseVillageDb->getInfo(array('village_id' => $village_id), '*');
            }
            foreach ($list as &$item) {
                if (isset($charge_type_arr[$item['order_type']])) {
                    $item['order_name'] = $charge_type_arr[$item['order_type']];
                }
                $item['project_name'] = '';
                if ($item['project_id'] > 0) {
                    $projectWhere = array();
                    $projectWhere[] = ['id', '=', $item['project_id']];
                    $projectWhere[] = ['village_id', '=', $village_id];
                    $oneProject = $houseNewChargeProject->getOne($projectWhere, 'name');
                    if ($oneProject && !$oneProject->isEmpty()) {
                        $item['project_name'] = $oneProject['name'];
                    }
                }
                $item['rule_name'] = '';
                if ($item['rule_id'] > 0) {
                    $projectWhere = array();
                    $projectWhere[] = ['id', '=', $item['rule_id']];
                    $projectWhere[] = ['village_id', '=', $village_id];
                    $oneProject = $houseNewChargeRule->getOne($projectWhere, 'charge_name');
                    if ($oneProject && !$oneProject->isEmpty()) {
                        $item['rule_name'] = $oneProject['charge_name'];
                    }
                }
                if (isset($item['summaryTotalMoney'])) {
                    $item['summaryTotalMoney'] = $this->formatNumberFunction($item['summaryTotalMoney'], $other_digit, $type);
                }
                if (isset($item['summaryPayMoney'])) {
                    $item['summaryPayMoney'] = $this->formatNumberFunction($item['summaryPayMoney'], $other_digit, $type);
                }
                if (isset($item['summaryRefundMoney'])) {
                    $item['summaryRefundMoney'] = $this->formatNumberFunction($item['summaryRefundMoney'], $other_digit, $type);
                }
                $summaryActualMoney = 0;
                if (isset($item['summaryPayMoney']) && isset($item['summaryRefundMoney'])) {
                    $summaryActualMoney = $this->formatNumberFunction(((float)$item['summaryPayMoney'] - (float)$item['summaryRefundMoney']), $other_digit, $type);
                }
                $item['summaryActualMoney'] = $summaryActualMoney;

                if (isset($item['summaryTotalMoney']) && $item['summaryTotalMoney'] > 0) {
                    $item['summaryMoneyRate'] = ($item['summaryActualMoney'] / $item['summaryTotalMoney']) * 100;
                    $item['summaryMoneyRate'] = $this->formatNumberFunction($item['summaryMoneyRate'], $other_digit, $type) . '%';
                }
                $item['noPayTotalMoney'] = 0;
                $item['discountTotalMoney'] = 0; //折扣掉的钱
                if ($group == 'rule_id') {
                    $tmp_other_whereArr = $other_whereArr;
                    $tmp_other_whereArr[] = ['rule_id', '=', $item['rule_id']];
                    $item['summaryTotalMoney'] = $db_house_new_pay_order->getSum($tmp_other_whereArr, 'total_money');
                    $item['summaryTotalMoney'] = $this->formatNumberFunction($item['summaryTotalMoney'], $other_digit, $type);
                    $tmp_other_whereArr = $other_whereArr;
                    $tmp_other_whereArr[] = ['rule_id', '=', $item['rule_id']];
                    $tmp_other_whereArr[] = ['is_paid', '=', 2];
                    $item['noPayTotalMoney'] = $db_house_new_pay_order->getSum($tmp_other_whereArr, 'total_money');
                    $modify_money = $db_house_new_pay_order->getSum($tmp_other_whereArr, 'modify_money');
                    $tmp_other_whereArr = $other_whereArr;
                    $tmp_other_whereArr[] = ['rule_id', '=', $item['rule_id']];
                    $tmp_other_whereArr[] = ['is_paid', '=', 1];
                    $item['summaryPayMoney'] = $db_house_new_pay_order->getSum($tmp_other_whereArr, 'pay_money');
                    $item['summaryPayMoney'] = $this->formatNumberFunction($item['summaryPayMoney'], $other_digit, $type);
                    $item['noPayTotalMoney'] = $item['noPayTotalMoney'] > 0 ? $this->formatNumberFunction($item['noPayTotalMoney'], $other_digit, $type) : 0;
                    $item['discountTotalMoney'] = $item['summaryTotalMoney'] - $modify_money - $item['summaryPayMoney'];
                    $item['discountTotalMoney'] = $item['discountTotalMoney'] > 0 ? $item['discountTotalMoney'] : 0;
                    $item['discountTotalMoney'] = $this->formatNumberFunction($item['discountTotalMoney'], $other_digit, $type);
                    $item['totalAllMoney'] = $modify_money + $item['summaryPayMoney'];
                    if ($item['totalAllMoney'] > 0) {
                        $item['summaryMoneyRate'] = ($item['summaryPayMoney'] / $item['totalAllMoney']) * 100;
                        $item['summaryMoneyRate'] = $this->formatNumberFunction($item['summaryMoneyRate'], $other_digit, $type) . '%';
                    } else {
                        $item['summaryMoneyRate'] = '';
                    }
                }
                $vacancy_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id' => $item['room_id']], 'single_id,floor_id,layer_id,village_id');
                if ($vacancy_info) {
                    $address = $service_house_village->getSingleFloorRoom($vacancy_info['single_id'], $vacancy_info['floor_id'], $vacancy_info['layer_id'], $item['room_id'], $vacancy_info['village_id']);
                } else {
                    $address = '';
                }
                if (!$item['name']) {
                    $item['name'] = '--';
                }
                if (!$item['phone']) {
                    $item['phone'] = '--';
                }
                $item['address'] = $address;
                $item['vacancy_info'] = $vacancy_info;
                if (!empty($item['phone'])) {
                    $item['phone'] = $houseVillageDb->phone_desensitization_v($item['phone'], $item['village_id'], $village_info);
                }
            }

            if (isset($searchSummaryInfo['summaryTotalMoney'])) {
                $searchSummaryInfo['summaryTotalMoney'] = $this->formatNumberFunction($searchSummaryInfo['summaryTotalMoney'], $other_digit, $type);
            }
            $summaryActualMoney = 0;
            if (isset($searchSummaryInfo['summaryPayMoney'])) {
                $searchSummaryInfo['summaryPayMoney'] = $this->formatNumberFunction($searchSummaryInfo['summaryPayMoney'], $other_digit, $type);
                $summaryActualMoney = $searchSummaryInfo['summaryPayMoney'];
            }
            if (isset($searchSummaryInfo['summaryRefundMoney'])) {
                $searchSummaryInfo['summaryRefundMoney'] = $this->formatNumberFunction($searchSummaryInfo['summaryRefundMoney'], $other_digit, $type);
                $summaryActualMoney = $summaryActualMoney - $searchSummaryInfo['summaryRefundMoney'];
            }
            if (isset($searchSummaryInfo['summaryTotalMoney']) && $searchSummaryInfo['summaryTotalMoney'] > 0) {
                $summaryActualMoney = $summaryActualMoney > 0 ? $this->formatNumberFunction($summaryActualMoney, $other_digit, $type) : 0;
                $searchSummaryInfo['summaryMoneyRate'] = ($summaryActualMoney / $searchSummaryInfo['summaryTotalMoney']) * 100;
                $searchSummaryInfo['summaryMoneyRate'] = $this->formatNumberFunction($searchSummaryInfo['summaryMoneyRate'], $other_digit, $type) . '%';
            } else {
                $searchSummaryInfo['summaryMoneyRate'] = '0%';
            }

        } else {
            $summaryInfo['summaryTotalMoney'] = $searchSummaryInfo['summaryTotalMoney'] = 0;
            $summaryInfo['summaryPayMoney'] = $searchSummaryInfo['summaryPayMoney'] = 0;
            $summaryInfo['summaryRefundMoney'] = $searchSummaryInfo['summaryRefundMoney'] = 0;
            $summaryInfo['summaryMoneyRate'] = $searchSummaryInfo['summaryMoneyRate'] = '0%';
        }
        if (!empty($summaryInfo)) {
            if (isset($summaryInfo['summaryTotalMoney'])) {
                $summaryInfo['summaryTotalMoney'] = $this->formatNumberFunction($summaryInfo['summaryTotalMoney'], $other_digit, $type);
            }
            if (isset($summaryInfo['summaryPayMoney'])) {
                $summaryInfo['summaryPayMoney'] = $this->formatNumberFunction($summaryInfo['summaryPayMoney'], $other_digit, $type);
            }
            $summaryActualMoneyTmp = $summaryInfo['summaryPayMoney'];
            if (isset($summaryInfo['summaryRefundMoney'])) {
                $summaryInfo['summaryRefundMoney'] = $this->formatNumberFunction($summaryInfo['summaryRefundMoney'], $other_digit, $type);
                $summaryActualMoneyTmp = $summaryInfo['summaryPayMoney'] - $summaryInfo['summaryRefundMoney'];
            }
            if (isset($summaryInfo['summaryTotalMoney']) && $summaryInfo['summaryTotalMoney'] > 0) {
                $summaryInfo['summaryMoneyRate'] = ($summaryActualMoneyTmp / $summaryInfo['summaryTotalMoney']) * 100;
                $summaryInfo['summaryMoneyRate'] = $this->formatNumberFunction($summaryInfo['summaryMoneyRate'], $other_digit, $type) . '%';
            } else {
                $summaryInfo['summaryMoneyRate'] = '0%';
            }
        }
        if ($group == 'rule_id') {
            $searchSummaryInfo['summaryTotalMoney'] = $db_house_new_pay_order->getSum($other_whereArr, 'total_money');
            $searchSummaryInfo['summaryTotalMoney'] = $this->formatNumberFunction($searchSummaryInfo['summaryTotalMoney'], $other_digit, $type);
            $tmp_other_whereArr = $other_whereArr;
            $tmp_other_whereArr[] = ['is_paid', '=', 1];
            $searchSummaryInfo['summaryPayMoney'] = $db_house_new_pay_order->getSum($tmp_other_whereArr, 'pay_money');
            $searchSummaryInfo['summaryPayMoney'] = $this->formatNumberFunction($searchSummaryInfo['summaryPayMoney'], $other_digit, $type);
            $tmp_other_whereArr = $other_whereArr;
            $tmp_other_whereArr[] = ['is_paid', '=', 2];
            $searchSummaryInfo['noPayTotalMoney'] = $db_house_new_pay_order->getSum($tmp_other_whereArr, 'total_money');
            $searchSummaryInfo['noPayTotalMoney'] = $this->formatNumberFunction($searchSummaryInfo['noPayTotalMoney'], $other_digit, $type);
            $modify_money = $db_house_new_pay_order->getSum($tmp_other_whereArr, 'modify_money');

            $searchSummaryInfo['discountTotalMoney'] = $searchSummaryInfo['summaryTotalMoney'] - $searchSummaryInfo['summaryPayMoney'] - $modify_money;
            $searchSummaryInfo['discountTotalMoney'] = $searchSummaryInfo['discountTotalMoney'] > 0 ? $searchSummaryInfo['discountTotalMoney'] : 0;
            $searchSummaryInfo['discountTotalMoney'] = $this->formatNumberFunction($searchSummaryInfo['discountTotalMoney'], $other_digit, $type);
            $searchSummaryInfo['moneyTotalRate'] = 0;
            if ($searchSummaryInfo['summaryTotalMoney'] && $searchSummaryInfo['summaryTotalMoney'] > 0) {
                $searchSummaryInfo['moneyTotalRate'] = ($searchSummaryInfo['summaryPayMoney'] / $searchSummaryInfo['summaryTotalMoney']) * 100;
                $searchSummaryInfo['moneyTotalRate'] = $this->formatNumberFunction($searchSummaryInfo['moneyTotalRate'], $other_digit, $type) . '%';
            }

        }
        $data = [];
        $data['summaryInfo'] = $summaryInfo;
        $data['pageSummaryInfo'] = $searchSummaryInfo;
        $data['total_limit'] = $limit;
        $data['list'] = $list;
        $data['count'] = $count ? $count : 0;
        return $data;
    }

    public function formatNumberFunction($number, $other_digit = 2, $type = 1)
    {
        if (function_exists('formatNumber')) {
            return formatNumber($number, $other_digit, $type);
        } else {
            return round_number($number);
        }
    }

    public function exportHouseVillageFeeRate($village_id = 0, $monthstart = 0, $monthend = 0)
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $houseVillageUserBind = new HouseVillageUserBind();
        $houseVillageUserVacancy = new HouseVillageUserVacancy();
        $whereArr = array();
        $whereArr[] = array('ub.village_id', '=', $village_id);
        $whereArr[] = array('ub.vacancy_id', '>', 0);
        $whereArr[] = array('ub.type', 'in', array(0, 3));
        $whereArr[] = array('ub.status', '=', 1);
        $whereArr[] = array('uv.status', 'in', array(1, 3));
        $whereArr[] = array('uv.is_del', '=', 0);
        $whereArr[] = array('uv.village_id', '=', $village_id);
        $fieldStr = 'ub.vacancy_id,ub.name,ub.uid,ub.phone,ub.usernum as user_num,uv.usernum,uv.housesize';
        $uerBindVacancy = $houseVillageUserBind->getUserBindVacancyList($whereArr, $fieldStr);
        if ($uerBindVacancy && !$uerBindVacancy->isEmpty()) {
            $uerBindVacancyList = $uerBindVacancy->toArray();
            if ($uerBindVacancyList) {
                $excelData = array();
                $titleArr = ['物业编号', '姓名', '手机号', '面积', '应收金额', '实收金额', '欠费金额', '收缴率'];
                $all_modify_money = 0;
                $all_pay_money = 0;
                foreach ($uerBindVacancyList as $vv) {
                    $whereOrderArr = array();
                    $whereOrderArr[] = array('village_id', '=', $village_id);
                    $whereOrderArr[] = array('order_type', '=', 'property');
                    $whereOrderArr[] = array('room_id', '=', $vv['vacancy_id']);
                    $whereOrderArr[] = array('is_discard', '=', 1);
                    if ($monthstart > 0) {
                        $whereOrderArr[] = array('service_start_time', '>=', $monthstart);
                    }
                    if ($monthend > 0) {
                        $whereOrderArr[] = array('service_end_time', '<=', $monthend);
                    }
                    $modify_money = $db_house_new_pay_order->getSum($whereOrderArr, 'modify_money');
                    $modify_money = round($modify_money, 2);
                    $all_modify_money += $modify_money;
                    $whereOrderArr[] = array('pay_time', '>', 100);
                    $pay_money = $db_house_new_pay_order->getSum($whereOrderArr, 'pay_money');
                    $pay_money = round($pay_money, 2);
                    $all_pay_money += $pay_money;
                    $no_pay = $modify_money - $pay_money;
                    $no_pay = $no_pay > 0 ? round($no_pay, 2) : 0;
                    $rate = 0;
                    if ($modify_money > 0) {
                        $rate = $pay_money / $modify_money;
                    }
                    $rate = round($rate, 2);
                    $tmpArr = array('usernum' => $vv['usernum'] ? $vv['usernum'] : $vv['user_num']);
                    $tmpArr['name'] = $vv['name'];
                    $tmpArr['phone'] = $vv['phone'];
                    $tmpArr['housesize'] = $vv['housesize'];
                    $tmpArr['modify_money'] = $modify_money;
                    $tmpArr['pay_money'] = $pay_money;
                    $tmpArr['no_pay'] = $no_pay;
                    $tmpArr['rate'] = $rate;
                    $excelData[] = $tmpArr;
                }
                $filename = '物业费收缴率统计' . date('YmdHis');
                $tips = '2022-12至物业费收缴率统计';
                if ($monthstart > 0) {
                    $tips = date('Y-m');
                }
                $tips .= '至';
                if ($monthend > 0) {
                    $tips .= date('Y-m');
                } else {
                    $tips .= '今';
                }
                $tips .= '物业费收缴率统计';
                $datas = array('list' => $excelData);
                $datas['all_modify_money'] = $all_modify_money;
                $datas['all_pay_money'] = $all_pay_money;
                return $this->saveExcel($titleArr, $datas, $filename, $tips, 'feeRate');
            }
        }
        throw new \think\Exception("没有查找到数据！");
    }

    public function getOrderTableRuleList($village_id = 0, $pay_status = '')
    {
        $whereArr = array();
        $whereArr[] = array('village_id', '=', $village_id);
        $whereArr[] = array('rule_id', '>', 0);
        $whereArr[] = array('order_type', '<>', 'non_motor_vehicle');
        if ($pay_status == 'no_pay') {
            $whereArr[] = array('is_paid', '=', 2);
            $whereArr[] = array('is_discard', '=', 1);
        } else if ($pay_status == 'is_pay') {
            $whereArr[] = array('is_paid', '=', 1);
        }
        $returnArr = array('lists' => array(), 'count' => 0);
        $db_house_new_pay_order = new HouseNewPayOrder();
        $order_rules = $db_house_new_pay_order->getOrderByGroup($whereArr, 'rule_id,project_id', 'rule_id');
        if ($order_rules && !$order_rules->isEmpty()) {
            $order_rules = $order_rules->toArray();
            $ruleIds = array();
            foreach ($order_rules as $rvv) {
                $ruleIds[] = $rvv['rule_id'];
            }
            $ruleIds = array_unique($ruleIds);
            $houseNewChargeRuleDb = new HouseNewChargeRule();
            $ruleWhere = array();
            //$ruleWhere[] = array('village_id', '=', $village_id);
            $ruleWhere[] = array('id', 'in', $ruleIds);
            $chargeRule = $houseNewChargeRuleDb->getList($ruleWhere, 'id,subject_id,charge_project_id,charge_name,status', 'id desc');
            if ($chargeRule && !$chargeRule->isEmpty()) {
                $chargeRule = $chargeRule->toArray();
                foreach ($chargeRule as $kkk => $crvv) {
                    if ($crvv['status'] == 4) {
                        $chargeRule[$kkk]['charge_name'] = $crvv['charge_name'] . '(已删除)';
                    }
                }
                $returnArr['count'] = count($chargeRule);
                $returnArr['lists'] = $chargeRule;
            }
            $returnArr['lists'][] = array('id' => 'qita', 'charge_name' => '其他');
        }
        return $returnArr;
    }

    public function getOrderTableProjectList($village_id = 0, $pay_status = '')
    {
        $whereArr = array();
        $whereArr[] = array('village_id', '=', $village_id);
        $whereArr[] = array('project_id', '>', 0);
        $whereArr[] = array('order_type', '<>', 'non_motor_vehicle');
        if ($pay_status == 'no_pay') {
            $whereArr[] = array('is_paid', '=', 2);
            $whereArr[] = array('is_discard', '=', 1);
        } else if ($pay_status == 'is_pay') {
            $whereArr[] = array('is_paid', '=', 1);
        }
        $returnArr = array('lists' => array(), 'count' => 0);
        $db_house_new_pay_order = new HouseNewPayOrder();
        $order_rules = $db_house_new_pay_order->getOrderByGroup($whereArr, 'project_id', 'project_id');
        if ($order_rules && !$order_rules->isEmpty()) {
            $order_rules = $order_rules->toArray();
            $projectIds = array();
            foreach ($order_rules as $rvv) {
                $projectIds[] = $rvv['project_id'];
            }
            $projectIds = array_unique($projectIds);
            $houseNewChargeProjectDb = new HouseNewChargeProject();
            $projectWhere = array();
            //$projectWhere[] = array('village_id', '=', $village_id);
            $projectWhere[] = array('id', 'in', $projectIds);
            $chargeProject = $houseNewChargeProjectDb->getLists($projectWhere, 'id,subject_id,name,status', 'id desc');
            if ($chargeProject && !$chargeProject->isEmpty()) {
                $chargeProject = $chargeProject->toArray();
                foreach ($chargeProject as $kkk => $crvv) {
                    if ($crvv['status'] == 4) {
                        $chargeProject[$kkk]['name'] = $crvv['name'] . '(已删除)';
                    }
                }
                $returnArr['count'] = count($chargeProject);
                $returnArr['lists'] = $chargeProject;
            }
            $returnArr['lists'][] = array('id' => 'qita', 'name' => '其他');
        }
        return $returnArr;
    }

    public function getCarParkingById($position_id = 0, $village_id = 0)
    {
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        $park_number = '';
        $garage_num = '';
        $position_num = '';
        if ($position_id < 1) {
            return ['park_number' => $park_number, 'garage_num' => $garage_num, 'position_num' => $position_num];
        }
        $whereArr = array('pp.position_id' => $position_id);
        if ($village_id > 0) {
            $whereArr['pp.village_id'] = $village_id;
        }
        $position_num_obj = $db_house_village_parking_position->getLists($whereArr, 'pp.position_num,pg.garage_num', 0);
        if (!empty($position_num_obj) && !$position_num_obj->isEmpty()) {
            $position_num_arr = $position_num_obj->toArray();
            if (!empty($position_num_arr)) {
                $position_num1 = $position_num_arr[0];
                if (empty($position_num1['garage_num'])) {
                    $position_num1['garage_num'] = '临时车库';
                }
                $garage_num = $position_num1['garage_num'];
                $position_num = $position_num1['position_num'];
                $park_number = $position_num1['garage_num'] . '-' . $position_num1['position_num'];
            }
        }
        return ['park_number' => $park_number, 'garage_num' => $garage_num, 'position_num' => $position_num];
    }

    // 已缴口径与账单列表一致：现金支付 + 组合支付抵扣；退款仍按报表原有规则处理。
    public function getSummaryByRoomAndRuleList($village_id = 0, $whereArr = array(), $pay_status = '', $page = 1, $limit = 10, $is_export = false)
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $returnArr = array('lists' => array(), 'count' => 0, 'total_limit' => $limit + 1, 'rule_count' => 0);
        $field = 'o.order_id,o.uid,o.pigcms_id,o.name,o.phone,o.pay_bind_id,o.pay_bind_name,o.pay_bind_phone,o.room_id,o.position_id,o.modify_money,sum(ROUND(o.modify_money,2)) as all_modify_money,sum(ROUND(o.late_payment_money,2)) as all_late_payment_money,o.pay_money,sum(ROUND(o.pay_money + COALESCE(o.discount_money,0),2)) as all_pay_money,o.pay_amount_points,o.project_id,o.rule_id,o.car_type,o.car_number,o.service_start_time,o.service_end_time';
        if ($pay_status == 'no_pay') {
            $field = 'o.order_id,o.uid,o.pigcms_id,o.name,o.phone,o.pay_bind_id,o.pay_bind_name,o.pay_bind_phone,o.room_id,o.position_id,o.total_money,sum(ROUND(o.modify_money,2)) as all_modify_money,sum(ROUND(o.late_payment_money,2)) as all_late_payment_money,o.late_payment_money,o.modify_money,o.pay_money,o.pay_amount_points,o.project_id,o.rule_id,o.car_type,o.car_number,o.service_start_time,o.service_end_time';
        } else if ($pay_status == 'is_pay') {
            $field = 'o.order_id,o.uid,o.pigcms_id,o.name,o.phone,o.pay_bind_id,o.pay_bind_name,o.pay_bind_phone,o.room_id,o.position_id,o.total_money,sum(ROUND(o.pay_money + COALESCE(o.discount_money,0),2)) as all_pay_money,o.late_payment_money,o.modify_money,o.pay_money,o.refund_money,sum(ROUND(o.refund_money,2)) as all_refund_money,o.pay_amount_points,o.project_id,o.rule_id,o.car_type,o.car_number,o.service_start_time,o.service_end_time';
        }
        $order_rules = $this->getOrderTableRuleList($village_id, $pay_status);
        $order_rules_lists = $order_rules['lists'];
        $rule_order_arr = array();
        $sumOrder = array();
        $where_rule_id = 0;
        foreach ($whereArr as $wk => $wv) {
            if ($wv['0'] == 'o.rule_id') {
                $where_rule_id = $wv['2'];
            }
        }
        $exportTitle = array();
        if ($is_export) {
            $exportTitle[] = '房间号/车位号';
            $exportTitle[] = '车位号';
            $exportTitle[] = '物业编号';
            $exportTitle[] = '业主';
            $exportTitle[] = '电话';
            $exportTitle[] = '计费开始时间';
            $exportTitle[] = '计费结束时间';
        }
        if (!empty($order_rules_lists)) {
            $returnArr['rule_count'] = count($order_rules_lists);
            foreach ($order_rules_lists as $rvv) {
                $ruleWhereArr = $whereArr;
                $order_key = 'rule_' . $rvv['id'];
                $rule_order_arr[$order_key] = '0.00';
                $sumOrder[$order_key] = '0.00';
                if ($is_export) {
                    $exportTitle[] = $rvv['charge_name'];
                }
                if ($where_rule_id > 0 && $rvv['id'] == $where_rule_id) {
                    $tmpSumField = 'sum(ROUND(modify_money,2)) as all_modify_money,sum(ROUND(late_payment_money,2)) as all_late_payment_money,sum(ROUND(pay_money + COALESCE(discount_money,0),2)) as all_pay_money,sum(ROUND(refund_money,2)) as all_refund_money';
                    $sumOrderMoney = $db_house_new_pay_order->get_one($ruleWhereArr, $tmpSumField);
                    if ($sumOrderMoney && isset($sumOrderMoney['all_modify_money'])) {
                        $all_total_money = $sumOrderMoney['all_modify_money'] + $sumOrderMoney['all_late_payment_money'];
                        if ($pay_status == 'is_pay') {
                            $all_total_money = $sumOrderMoney['all_pay_money'] - $sumOrderMoney['all_refund_money'];;
                        }
                        $all_total_money = round_number($all_total_money);
                        $sumOrder[$order_key] = $all_total_money;
                    }
                } else if ($where_rule_id < 1 && !empty($rvv['id'])) {
                    if ($rvv['id'] == 'qita') {
                        $ruleWhereArr[] = array('rule_id', '<', 1);
                    } else {
                        $ruleWhereArr[] = array('rule_id', '=', $rvv['id']);
                    }
                    $tmpSumField = 'sum(ROUND(modify_money,2)) as all_modify_money,sum(ROUND(late_payment_money,2)) as all_late_payment_money,sum(ROUND(pay_money + COALESCE(discount_money,0),2)) as all_pay_money,sum(ROUND(refund_money,2)) as all_refund_money';
                    $sumOrderMoney = $db_house_new_pay_order->get_one($ruleWhereArr, $tmpSumField);
                    if ($sumOrderMoney && isset($sumOrderMoney['all_modify_money'])) {
                        $all_total_money = $sumOrderMoney['all_modify_money'] + $sumOrderMoney['all_late_payment_money'];
                        if ($pay_status == 'is_pay') {
                            $all_total_money = $sumOrderMoney['all_pay_money'] - $sumOrderMoney['all_refund_money'];
                        }
                        $all_total_money = round_number($all_total_money);
                        $sumOrder[$order_key] = $all_total_money;
                    }
                }
            }
        }
        $is_force = false;
        if ($is_export) {
            $exportTitle[] = '合计';
            $returnArr['export_title'] = $exportTitle;
            $is_force = true;
        }
        $configCustomizationService = new ConfigCustomizationService();
        $isAihorseCustomized = $configCustomizationService->getAihorseCustomizedJudge($is_force);
        unset($order_rules_lists);
        if ($isAihorseCustomized) {
            $orders = $db_house_new_pay_order->getOrderByGroupRelateVacancy($whereArr, $field, 'o.room_id,o.position_id,o.car_type,o.car_number', 'rr.usernum desc,o.position_id desc', $page, $limit);
        } else {
            $orders = $db_house_new_pay_order->getOrderByGroup($whereArr, $field, 'room_id,position_id,car_type,car_number', 'order_id DESC', $page, $limit);
        }
        $exportData = array();
        if ($orders && !$orders->isEmpty()) {
            $houseVillageDb = new HouseVillage();
            $village_info = $houseVillageDb->getInfo(array('village_id' => $village_id), '*');
            $orders = $orders->toArray();
            $count = $db_house_new_pay_order->getCount2ByGroup($whereArr, 'room_id,position_id,car_type,car_number');
            $pageCount = $count / $limit;
            $pageCount = floor($pageCount) + $count;
            $returnArr['count'] = intval($pageCount);
            $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
            $service_house_village = new HouseVillageService();
            foreach ($orders as $kk => $vv) {
                $tmpWhereArr = $whereArr;
                $vv = array_merge($vv, $rule_order_arr);
                $tmp_rule_order_arr = $rule_order_arr;
                $orders[$kk] = $vv;
                $address = '';
                $usernum = '';
                $position_num_str = '';
                if ($vv['position_id'] > 0) {
                    $park_number_info = $this->getCarParkingById($vv['position_id']);
                    $address = $park_number_info['park_number'];
                    $position_num_str = $park_number_info['position_num'];
                } elseif ($vv['room_id'] > 0) {
                    $vacancy_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id' => $vv['room_id']], 'single_id,floor_id,layer_id,village_id,usernum');
                    if ($vacancy_info) {
                        $usernum = $vacancy_info['usernum'];
                        $address = $service_house_village->getSingleFloorRoom($vacancy_info['single_id'], $vacancy_info['floor_id'], $vacancy_info['layer_id'], $vv['room_id'], $vacancy_info['village_id']);
                    }
                }
                $tmpWhereArr[] = array('room_id', '=', $vv['room_id']);
                $tmpWhereArr[] = array('position_id', '=', $vv['position_id']);
                if ($vv['car_type'] == 'month_type' && !empty($vv['car_number'])) {
                    $address = '月租车缴费【' . $vv['car_number'] . '】';
                    $tmpWhereArr[] = array('car_number', '=', $vv['car_number']);
                } elseif ($vv['car_type'] == 'stored_type' && !empty($vv['car_number'])) {
                    $address = '储值车缴费【' . $vv['car_number'] . '】';
                    $tmpWhereArr[] = array('car_number', '=', $vv['car_number']);
                } elseif ($vv['car_type'] == 'temporary_type' && !empty($vv['car_number'])) {
                    $address = '临时车缴费【' . $vv['car_number'] . '】';
                    $tmpWhereArr[] = array('car_number', '=', $vv['car_number']);
                }
                $orders[$kk]['address'] = $address;
                $orders[$kk]['service_start_time_str'] = '';
                if ($vv['service_start_time'] > 100) {
                    $orders[$kk]['service_start_time_str'] = date('Y-m-d', $vv['service_start_time']);
                }
                $orders[$kk]['service_end_time_str'] = '';
                if ($vv['service_end_time'] > 100) {
                    $orders[$kk]['service_end_time_str'] = date('Y-m-d', $vv['service_end_time']);
                }
                if ($pay_status == 'no_pay') {
                    $all_total_money = $vv['all_modify_money'] + $vv['all_late_payment_money'];
                    $orders[$kk]['all_total_money'] = round_number($all_total_money);
                    $rule_order_tmp = $db_house_new_pay_order->getOrderByGroup($tmpWhereArr, 'project_id,rule_id,sum(ROUND(modify_money,2)) as all_total_money,sum(ROUND(late_payment_money,2)) as all_late_payment_money,car_type,car_number,service_start_time,service_end_time', 'rule_id,car_type,car_number');
                    if ($rule_order_tmp && !$rule_order_tmp->isEmpty()) {
                        $rule_order_tmp = $rule_order_tmp->toArray();
                        foreach ($rule_order_tmp as $itemR) {
                            $order_key = 'rule_' . $itemR['rule_id'];
                            if ($itemR['rule_id'] < 1) {
                                $order_key = 'rule_qita';
                            }
                            if (!isset($vv[$order_key])) {
                                $order_key = 'rule_qita';
                            }
                            $rule_all_total_money = $itemR['all_total_money'] + $itemR['all_late_payment_money'];
                            $rule_all_total_money = round_number($rule_all_total_money);
                            $orders[$kk][$order_key] = $rule_all_total_money;
                            $tmp_rule_order_arr[$order_key] = $rule_all_total_money;
                        }
                    }
                } else if ($pay_status == 'is_pay') {
                    $all_total_money = $vv['all_pay_money'] - $vv['all_refund_money'];
                    $orders[$kk]['all_total_money'] = round_number($all_total_money);
                    $rule_order_tmp = $db_house_new_pay_order->getOrderByGroup($tmpWhereArr, 'project_id,rule_id,sum(ROUND(pay_money + COALESCE(discount_money,0),2)) as all_total_money,sum(ROUND(refund_money,2)) as all_refund_money,car_type,car_number,service_start_time,service_end_time', 'rule_id,car_type,car_number');
                    if ($rule_order_tmp && !$rule_order_tmp->isEmpty()) {
                        $rule_order_tmp = $rule_order_tmp->toArray();
                        foreach ($rule_order_tmp as $itemR) {
                            $order_key = 'rule_' . $itemR['rule_id'];
                            if ($itemR['rule_id'] < 1) {
                                $order_key = 'rule_qita';
                            }
                            if (!isset($vv[$order_key])) {
                                $order_key = 'rule_qita';
                            }
                            $rule_all_total_money = $itemR['all_total_money'] - $itemR['all_refund_money'];
                            $rule_all_total_money = round_number($rule_all_total_money);
                            $orders[$kk][$order_key] = $rule_all_total_money;
                            $tmp_rule_order_arr[$order_key] = $rule_all_total_money;
                        }
                    }
                }
                $tmpstartWhereArr = $tmpWhereArr;
                $tmpstartWhereArr[] = array('service_start_time', '>', 100);
                $tmp_pay_order = $db_house_new_pay_order->get_one($tmpstartWhereArr, 'service_start_time,service_end_time', 'service_start_time ASC');
                if ($tmp_pay_order && !$tmp_pay_order->isEmpty() && isset($tmp_pay_order['service_start_time'])) {
                    $orders[$kk]['service_start_time_str'] = date('Y-m-d', $tmp_pay_order['service_start_time']);
                }
                $tmpEndWhereArr = $tmpWhereArr;
                $tmpEndWhereArr[] = array('service_end_time', '>', 100);
                $tmp_pay_order = $db_house_new_pay_order->get_one($tmpEndWhereArr, 'service_start_time,service_end_time', 'service_end_time desc');
                if ($tmp_pay_order && !$tmp_pay_order->isEmpty() && isset($tmp_pay_order['service_end_time'])) {
                    $orders[$kk]['service_end_time_str'] = date('Y-m-d', $tmp_pay_order['service_end_time']);
                } else {
                    $orders[$kk]['service_end_time_str'] = $orders[$kk]['service_start_time_str'];
                }
                if (isset($vv['phone']) && !empty($vv['phone'])) {
                    $orders[$kk]['phone'] = $houseVillageDb->phone_desensitization_v($vv['phone'], $village_id, $village_info);
                }
                if (isset($vv['pay_bind_phone']) && !empty($vv['pay_bind_phone'])) {
                    $orders[$kk]['pay_bind_phone'] = $houseVillageDb->phone_desensitization_v($vv['pay_bind_phone'], $village_id, $village_info);
                }
                if ($is_export) {
                    $tmpArr = array();
                    $tmpArr['address'] = $orders[$kk]['address'];
                    $tmpArr['position_num'] = $position_num_str;
                    $tmpArr['usernum'] = $usernum;
                    $tmpArr['name'] = $orders[$kk]['name'];
                    $tmpArr['phone'] = $orders[$kk]['phone'];
                    $tmpArr['service_start_time_str'] = $orders[$kk]['service_start_time_str'];
                    $tmpArr['service_end_time_str'] = $orders[$kk]['service_end_time_str'];
                    $tmpArr = array_merge($tmpArr, $tmp_rule_order_arr);
                    $tmpArr['all_total_money'] = $orders[$kk]['all_total_money'];
                    $exportData[] = $tmpArr;
                }
            }
            $returnArr['lists'] = $orders;
            if ($is_export) {
                $returnArr['lists'] = $exportData;
            }
            unset($orders);
            unset($exportData);

            $tmpSumField = 'sum(ROUND(modify_money,2)) as all_modify_money,sum(ROUND(late_payment_money,2)) as all_late_payment_money,sum(ROUND(pay_money + COALESCE(discount_money,0),2)) as all_pay_money,sum(ROUND(refund_money,2)) as all_refund_money';
            $sumOrderMoney = $db_house_new_pay_order->get_one($whereArr, $tmpSumField);
            $all_total_money = 0;
            if ($sumOrderMoney && isset($sumOrderMoney['all_modify_money'])) {
                $all_total_money = $sumOrderMoney['all_modify_money'] + $sumOrderMoney['all_late_payment_money'];
                if ($pay_status == 'is_pay') {
                    $all_total_money = $sumOrderMoney['all_pay_money'] - $sumOrderMoney['all_refund_money'];;
                }
                $all_total_money = round_number($all_total_money);
            }

            if ($is_export) {
                $tmpAumOrder = array();
                $tmpAumOrder['address'] = '总条数：' . $count;
                $tmpAumOrder['position_num'] = '';
                $tmpAumOrder['usernum'] = '';
                $tmpAumOrder['name'] = '';
                $tmpAumOrder['phone'] = '';
                $tmpAumOrder['service_start_time_str'] = '';
                $tmpAumOrder['service_end_time_str'] = '';
                $tmpAumOrder = array_merge($tmpAumOrder, $sumOrder);
                $tmpAumOrder['all_total_money'] = $all_total_money;
                $returnArr['lists'][] = $tmpAumOrder;
                unset($tmpAumOrder);
                $returnArr['export_title'] = $exportTitle;
            } else {
                $sumOrder['order_id'] = 0;
                $sumOrder['address'] = '总条数：' . $count;
                $sumOrder['name'] = '';
                $sumOrder['phone'] = '';
                $sumOrder['service_start_time_str'] = '';
                $sumOrder['service_end_time_str'] = '';
                $sumOrder['all_total_money'] = $all_total_money;
                $returnArr['lists'][] = $sumOrder;
            }
        }
        return $returnArr;
    }

    public function getSummaryByRoomAndProjectList($village_id = 0, $whereArr = array(), $pay_status = '', $page = 1, $limit = 10, $is_export = false)
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $returnArr = array('lists' => array(), 'count' => 0, 'total_limit' => $limit + 1, 'rule_count' => 0);
        $field = 'o.order_id,o.uid,o.pigcms_id,o.name,o.phone,o.pay_bind_id,o.pay_bind_name,o.pay_bind_phone,o.room_id,o.position_id,o.modify_money,sum(ROUND(o.modify_money,2)) as all_modify_money,sum(ROUND(o.late_payment_money,2)) as all_late_payment_money,o.pay_money,sum(ROUND(o.pay_money + COALESCE(o.discount_money,0),2)) as all_pay_money,o.pay_amount_points,o.project_id,o.rule_id,o.car_type,o.car_number,o.service_start_time,o.service_end_time';
        if ($pay_status == 'no_pay') {
            $field = 'o.order_id,o.uid,o.pigcms_id,o.name,o.phone,o.pay_bind_id,o.pay_bind_name,o.pay_bind_phone,o.room_id,o.position_id,o.total_money,sum(ROUND(o.modify_money,2)) as all_modify_money,sum(ROUND(o.late_payment_money,2)) as all_late_payment_money,o.late_payment_money,o.modify_money,o.pay_money,o.pay_amount_points,o.project_id,o.rule_id,o.car_type,o.car_number,o.service_start_time,o.service_end_time';
        } else if ($pay_status == 'is_pay') {
            $field = 'o.order_id,o.uid,o.pigcms_id,o.name,o.phone,o.pay_bind_id,o.pay_bind_name,o.pay_bind_phone,o.room_id,o.position_id,o.total_money,sum(ROUND(o.pay_money + COALESCE(o.discount_money,0),2)) as all_pay_money,o.late_payment_money,o.modify_money,o.pay_money,o.refund_money,sum(ROUND(o.refund_money,2)) as all_refund_money,o.pay_amount_points,o.project_id,o.rule_id,o.car_type,o.car_number,o.service_start_time,o.service_end_time';
        }
        $order_project = $this->getOrderTableProjectList($village_id, $pay_status);
        $order_project_lists = $order_project['lists'];
        $project_order_arr = array();
        $sumOrder = array();
        $where_project_id = 0;
        foreach ($whereArr as $wk => $wv) {
            if ($wv['0'] == 'o.project_id') {
                $where_project_id = $wv['2'];
            }
        }
        $exportTitle = array();
        if ($is_export) {
            $exportTitle[] = '房间号/车位号';
            $exportTitle[] = '车位号';
            $exportTitle[] = '物业编号';
            $exportTitle[] = '业主';
            $exportTitle[] = '电话';
            $exportTitle[] = '计费开始时间';
            $exportTitle[] = '计费结束时间';
        }
        if (!empty($order_project_lists)) {
            $returnArr['project_count'] = count($order_project_lists);
            foreach ($order_project_lists as $rvv) {
                $ruleWhereArr = $whereArr;
                $order_key = 'project_' . $rvv['id'];
                $project_order_arr[$order_key] = '0.00';
                $sumOrder[$order_key] = '0.00';
                if ($is_export) {
                    $exportTitle[] = $rvv['name'];
                }
                if ($where_project_id > 0 && $rvv['id'] == $where_project_id) {
                    $tmpSumField = 'sum(ROUND(modify_money,2)) as all_modify_money,sum(ROUND(late_payment_money,2)) as all_late_payment_money,sum(ROUND(pay_money + COALESCE(discount_money,0),2)) as all_pay_money,sum(ROUND(refund_money,2)) as all_refund_money';
                    $sumOrderMoney = $db_house_new_pay_order->get_one($ruleWhereArr, $tmpSumField);
                    if ($sumOrderMoney && isset($sumOrderMoney['all_modify_money'])) {
                        $all_total_money = $sumOrderMoney['all_modify_money'] + $sumOrderMoney['all_late_payment_money'];
                        if ($pay_status == 'is_pay') {
                            $all_total_money = $sumOrderMoney['all_pay_money'] - $sumOrderMoney['all_refund_money'];;
                        }
                        $all_total_money = round_number($all_total_money);
                        $sumOrder[$order_key] = $all_total_money;
                    }
                } else if ($where_project_id < 1 && !empty($rvv['id'])) {
                    if ($rvv['id'] == 'qita') {
                        $ruleWhereArr[] = array('project_id', '<', 1);
                    } else {
                        $ruleWhereArr[] = array('project_id', '=', $rvv['id']);
                    }
                    $tmpSumField = 'sum(ROUND(modify_money,2)) as all_modify_money,sum(ROUND(late_payment_money,2)) as all_late_payment_money,sum(ROUND(pay_money + COALESCE(discount_money,0),2)) as all_pay_money,sum(ROUND(refund_money,2)) as all_refund_money';
                    $sumOrderMoney = $db_house_new_pay_order->get_one($ruleWhereArr, $tmpSumField);
                    if ($sumOrderMoney && isset($sumOrderMoney['all_modify_money'])) {
                        $all_total_money = $sumOrderMoney['all_modify_money'] + $sumOrderMoney['all_late_payment_money'];
                        if ($pay_status == 'is_pay') {
                            $all_total_money = $sumOrderMoney['all_pay_money'] - $sumOrderMoney['all_refund_money'];
                        }
                        $all_total_money = round_number($all_total_money);
                        $sumOrder[$order_key] = $all_total_money;
                    }
                }
            }
        }
        $is_force = false;
        if ($is_export) {
            $exportTitle[] = '合计';
            $returnArr['export_title'] = $exportTitle;
            $is_force = true;
        }
        $configCustomizationService = new ConfigCustomizationService();
        $isAihorseCustomized = $configCustomizationService->getAihorseCustomizedJudge($is_force);
        unset($order_rules_lists);
        if ($isAihorseCustomized) {
            $orders = $db_house_new_pay_order->getOrderByGroupRelateVacancy($whereArr, $field, 'o.room_id,o.position_id,o.car_type,o.car_number', 'rr.usernum desc,o.position_id desc', $page, $limit);
        } else {
            $orders = $db_house_new_pay_order->getOrderByGroup($whereArr, $field, 'room_id,position_id,car_type,car_number', 'order_id DESC', $page, $limit);
        }
        $exportData = array();
        if ($orders && !$orders->isEmpty()) {
            $houseVillageDb = new HouseVillage();
            $village_info = $houseVillageDb->getInfo(array('village_id' => $village_id), '*');
            $orders = $orders->toArray();
            $count = $db_house_new_pay_order->getCount2ByGroup($whereArr, 'room_id,position_id,car_type,car_number');
            $pageCount = $count / $limit;
            $pageCount = floor($pageCount) + $count;
            $returnArr['count'] = intval($pageCount);
            $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
            $service_house_village = new HouseVillageService();
            foreach ($orders as $kk => $vv) {
                $tmpWhereArr = $whereArr;
                $vv = array_merge($vv, $project_order_arr);
                $tmp_rule_order_arr = $project_order_arr;
                $orders[$kk] = $vv;
                $address = '';
                $usernum = '';
                $position_num_str = '';
                if ($vv['position_id'] > 0) {
                    $park_number_info = $this->getCarParkingById($vv['position_id']);
                    $address = $park_number_info['park_number'];
                    $position_num_str = $park_number_info['position_num'];
                } elseif ($vv['room_id'] > 0) {
                    $vacancy_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id' => $vv['room_id']], 'single_id,floor_id,layer_id,village_id,usernum');
                    if ($vacancy_info) {
                        $usernum = $vacancy_info['usernum'];
                        $address = $service_house_village->getSingleFloorRoom($vacancy_info['single_id'], $vacancy_info['floor_id'], $vacancy_info['layer_id'], $vv['room_id'], $vacancy_info['village_id']);
                    }
                }
                $tmpWhereArr[] = array('room_id', '=', $vv['room_id']);
                $tmpWhereArr[] = array('position_id', '=', $vv['position_id']);
                if ($vv['car_type'] == 'month_type' && !empty($vv['car_number'])) {
                    $address = '月租车缴费【' . $vv['car_number'] . '】';
                    $tmpWhereArr[] = array('car_number', '=', $vv['car_number']);
                } elseif ($vv['car_type'] == 'stored_type' && !empty($vv['car_number'])) {
                    $address = '储值车缴费【' . $vv['car_number'] . '】';
                    $tmpWhereArr[] = array('car_number', '=', $vv['car_number']);
                } elseif ($vv['car_type'] == 'temporary_type' && !empty($vv['car_number'])) {
                    $address = '临时车缴费【' . $vv['car_number'] . '】';
                    $tmpWhereArr[] = array('car_number', '=', $vv['car_number']);
                }
                $orders[$kk]['address'] = $address;
                $orders[$kk]['service_start_time_str'] = '';
                if ($vv['service_start_time'] > 100) {
                    $orders[$kk]['service_start_time_str'] = date('Y-m-d', $vv['service_start_time']);
                }
                $orders[$kk]['service_end_time_str'] = '';
                if ($vv['service_end_time'] > 100) {
                    $orders[$kk]['service_end_time_str'] = date('Y-m-d', $vv['service_end_time']);
                }
                if ($pay_status == 'no_pay') {
                    $all_total_money = $vv['all_modify_money'] + $vv['all_late_payment_money'];
                    $orders[$kk]['all_total_money'] = round_number($all_total_money);
                    $rule_order_tmp = $db_house_new_pay_order->getOrderByGroup($tmpWhereArr, 'project_id,rule_id,sum(ROUND(modify_money,2)) as all_total_money,sum(ROUND(late_payment_money,2)) as all_late_payment_money,car_type,car_number,service_start_time,service_end_time', 'project_id,car_type,car_number');
                    if ($rule_order_tmp && !$rule_order_tmp->isEmpty()) {
                        $rule_order_tmp = $rule_order_tmp->toArray();
                        foreach ($rule_order_tmp as $itemR) {
                            $order_key = 'project_' . $itemR['project_id'];
                            if ($itemR['project_id'] < 1) {
                                $order_key = 'project_qita';
                            }
                            if (!isset($vv[$order_key])) {
                                $order_key = 'project_qita';
                            }
                            $rule_all_total_money = $itemR['all_total_money'] + $itemR['all_late_payment_money'];
                            $rule_all_total_money = round_number($rule_all_total_money);
                            $orders[$kk][$order_key] = $rule_all_total_money;
                            $tmp_rule_order_arr[$order_key] = $rule_all_total_money;
                        }
                    }
                } else if ($pay_status == 'is_pay') {
                    $all_total_money = $vv['all_pay_money'] - $vv['all_refund_money'];
                    $orders[$kk]['all_total_money'] = round_number($all_total_money);
                    $rule_order_tmp = $db_house_new_pay_order->getOrderByGroup($tmpWhereArr, 'project_id,rule_id,sum(ROUND(pay_money + COALESCE(discount_money,0),2)) as all_total_money,sum(ROUND(refund_money,2)) as all_refund_money,car_type,car_number,service_start_time,service_end_time', 'project_id,car_type,car_number');
                    if ($rule_order_tmp && !$rule_order_tmp->isEmpty()) {
                        $rule_order_tmp = $rule_order_tmp->toArray();
                        foreach ($rule_order_tmp as $itemR) {
                            $order_key = 'project_' . $itemR['project_id'];
                            if ($itemR['project_id'] < 1) {
                                $order_key = 'project_qita';
                            }
                            if (!isset($vv[$order_key])) {
                                $order_key = 'project_qita';
                            }
                            $rule_all_total_money = $itemR['all_total_money'] - $itemR['all_refund_money'];
                            $rule_all_total_money = round_number($rule_all_total_money);
                            $orders[$kk][$order_key] = $rule_all_total_money;
                            $tmp_rule_order_arr[$order_key] = $rule_all_total_money;
                        }
                    }
                }
                $tmpstartWhereArr = $tmpWhereArr;
                $tmpstartWhereArr[] = array('service_start_time', '>', 100);
                $tmp_pay_order = $db_house_new_pay_order->get_one($tmpstartWhereArr, 'service_start_time,service_end_time', 'service_start_time ASC');
                if ($tmp_pay_order && !$tmp_pay_order->isEmpty() && isset($tmp_pay_order['service_start_time'])) {
                    $orders[$kk]['service_start_time_str'] = date('Y-m-d', $tmp_pay_order['service_start_time']);
                }
                $tmpEndWhereArr = $tmpWhereArr;
                $tmpEndWhereArr[] = array('service_end_time', '>', 100);
                $tmp_pay_order = $db_house_new_pay_order->get_one($tmpEndWhereArr, 'service_start_time,service_end_time', 'service_end_time desc');
                if ($tmp_pay_order && !$tmp_pay_order->isEmpty() && isset($tmp_pay_order['service_end_time'])) {
                    $orders[$kk]['service_end_time_str'] = date('Y-m-d', $tmp_pay_order['service_end_time']);
                } else {
                    $orders[$kk]['service_end_time_str'] = $orders[$kk]['service_start_time_str'];
                }
                if (isset($vv['phone']) && !empty($vv['phone'])) {
                    $orders[$kk]['phone'] = $houseVillageDb->phone_desensitization_v($vv['phone'], $village_id, $village_info);
                }
                if (isset($vv['pay_bind_phone']) && !empty($vv['pay_bind_phone'])) {
                    $orders[$kk]['pay_bind_phone'] = $houseVillageDb->phone_desensitization_v($vv['pay_bind_phone'], $village_id, $village_info);
                }
                if ($is_export) {
                    $tmpArr = array();
                    $tmpArr['address'] = $orders[$kk]['address'];
                    $tmpArr['position_num'] = $position_num_str;
                    $tmpArr['usernum'] = $usernum;
                    $tmpArr['name'] = $orders[$kk]['name'];
                    $tmpArr['phone'] = $orders[$kk]['phone'];
                    $tmpArr['service_start_time_str'] = $orders[$kk]['service_start_time_str'];
                    $tmpArr['service_end_time_str'] = $orders[$kk]['service_end_time_str'];
                    $tmpArr = array_merge($tmpArr, $tmp_rule_order_arr);
                    $tmpArr['all_total_money'] = $orders[$kk]['all_total_money'];
                    $exportData[] = $tmpArr;
                }
            }
            $returnArr['lists'] = $orders;
            if ($is_export) {
                $returnArr['lists'] = $exportData;
            }
            unset($orders);
            unset($exportData);

            $tmpSumField = 'sum(ROUND(modify_money,2)) as all_modify_money,sum(ROUND(late_payment_money,2)) as all_late_payment_money,sum(ROUND(pay_money + COALESCE(discount_money,0),2)) as all_pay_money,sum(ROUND(refund_money,2)) as all_refund_money';
            $sumOrderMoney = $db_house_new_pay_order->get_one($whereArr, $tmpSumField);
            $all_total_money = 0;
            if ($sumOrderMoney && isset($sumOrderMoney['all_modify_money'])) {
                $all_total_money = $sumOrderMoney['all_modify_money'] + $sumOrderMoney['all_late_payment_money'];
                if ($pay_status == 'is_pay') {
                    $all_total_money = $sumOrderMoney['all_pay_money'] - $sumOrderMoney['all_refund_money'];;
                }
                $all_total_money = round_number($all_total_money);
            }

            if ($is_export) {
                $tmpAumOrder = array();
                $tmpAumOrder['address'] = '总条数：' . $count;
                $tmpAumOrder['position_num'] = '';
                $tmpAumOrder['usernum'] = '';
                $tmpAumOrder['name'] = '';
                $tmpAumOrder['phone'] = '';
                $tmpAumOrder['service_start_time_str'] = '';
                $tmpAumOrder['service_end_time_str'] = '';
                $tmpAumOrder = array_merge($tmpAumOrder, $sumOrder);
                $tmpAumOrder['all_total_money'] = $all_total_money;
                $returnArr['lists'][] = $tmpAumOrder;
                unset($tmpAumOrder);
                $returnArr['export_title'] = $exportTitle;
            } else {
                $sumOrder['order_id'] = 0;
                $sumOrder['address'] = '总条数：' . $count;
                $sumOrder['name'] = '';
                $sumOrder['phone'] = '';
                $sumOrder['service_start_time_str'] = '';
                $sumOrder['service_end_time_str'] = '';
                $sumOrder['all_total_money'] = $all_total_money;
                $returnArr['lists'][] = $sumOrder;
            }
        }
        return $returnArr;
    }

    public function getSummaryByYearAndRuleList($village_id = 0, $whereArr = array(), $pay_status = '', $extraArr = array(), $page = 1, $limit = 10, $is_export = false)
    {
        $year_v = date('Y');
        $order_service_type = 0;
        if (isset($extraArr['year_v']) && $extraArr['year_v'] > 1970) {
            $year_v = $extraArr['year_v'];
        }
        if (isset($extraArr['order_service_type']) && $extraArr['order_service_type'] > 0) {
            $order_service_type = $extraArr['order_service_type'];
        }
        $returnArr = array('lists' => array(), 'count' => 0, 'total_limit' => $limit + 1);
        $db_house_new_pay_order = new HouseNewPayOrder();
        $order_rules = $db_house_new_pay_order->getOrderByGroup($whereArr, 'rule_id,project_id', 'rule_id');
        $ruleIds = array();
        $sumOrder = array();
        $where_rule_id = 0;

        foreach ($whereArr as $wk => $wv) {
            if ($wv['0'] == 'rule_id') {
                $where_rule_id = $wv['2'];
            }
        }
        if ($order_rules && !$order_rules->isEmpty()) {
            $order_rules = $order_rules->toArray();
            foreach ($order_rules as $rvv) {
                $ruleIds[] = $rvv['rule_id'];
            }
            $ruleIds = array_unique($ruleIds);
        }
        $configCustomizationService = new ConfigCustomizationService();
        $funcKaidalaicnSpecial = $configCustomizationService->getfuncKaidalaicnSpecial();
        $whereArrSpecial = $whereArr;
        if ($funcKaidalaicnSpecial && $pay_status == 'is_pay' && in_array($order_service_type, [1, 2])) {
            $whereArr[] = array('third_opt_status', '<>', 100);  //不拆单
            $whereArrSpecial[] = array('third_opt_status', '=', 100);  //拆单
            $whereArrSpecial[] = array('order_type_flag', '=', 'red_reversal_or_append_later');  //拆单
        }
        $exportTitle = array();
        $monthArr = array();
        if ($pay_status == 'no_pay') {
            $monthArr = array('month01_no_pay' => '0.00', 'month02_no_pay' => '0.00', 'month03_no_pay' => '0.00', 'month04_no_pay' => '0.00', 'month05_no_pay' => '0.00');
            $monthArr['month06_no_pay'] = '0.00';
            $monthArr['month07_no_pay'] = '0.00';
            $monthArr['month08_no_pay'] = '0.00';
            $monthArr['month09_no_pay'] = '0.00';
            $monthArr['month10_no_pay'] = '0.00';
            $monthArr['month11_no_pay'] = '0.00';
            $monthArr['month12_no_pay'] = '0.00';
            $exportTitle[] = '收费标准名称';
            $exportTitle[] = '一月欠费';
            $exportTitle[] = '二月欠费';
            $exportTitle[] = '三月欠费';
            $exportTitle[] = '四月欠费';
            $exportTitle[] = '五月欠费';
            $exportTitle[] = '六月欠费';
            $exportTitle[] = '七月欠费';
            $exportTitle[] = '八月欠费';
            $exportTitle[] = '九月欠费';
            $exportTitle[] = '十月欠费';
            $exportTitle[] = '十一月欠费';
            $exportTitle[] = '十二月欠费';
            $exportTitle[] = '合计';
            $sumOrder['charge_name'] = '合计';
            $sumOrder = array_merge($sumOrder, $monthArr);
        } else if ($pay_status == 'is_pay') {
            $monthArr = array('month01_is_pay' => '0.00', 'month01_refund' => '0.00', 'month02_is_pay' => '0.00', 'month02_refund' => '0.00', 'month03_is_pay' => '0.00', 'month03_refund' => '0.00');
            $monthArr['month04_is_pay'] = '0.00';
            $monthArr['month04_refund'] = '0.00';
            $monthArr['month05_is_pay'] = '0.00';
            $monthArr['month05_refund'] = '0.00';
            $monthArr['month06_is_pay'] = '0.00';
            $monthArr['month06_refund'] = '0.00';
            $monthArr['month07_is_pay'] = '0.00';
            $monthArr['month07_refund'] = '0.00';
            $monthArr['month08_is_pay'] = '0.00';
            $monthArr['month08_refund'] = '0.00';
            $monthArr['month09_is_pay'] = '0.00';
            $monthArr['month09_refund'] = '0.00';
            $monthArr['month10_is_pay'] = '0.00';
            $monthArr['month10_refund'] = '0.00';
            $monthArr['month11_is_pay'] = '0.00';
            $monthArr['month11_refund'] = '0.00';
            $monthArr['month12_is_pay'] = '0.00';
            $monthArr['month12_refund'] = '0.00';
            $exportTitle[] = '收费标准名称';
            $exportTitle[] = '一月收入';
            $exportTitle[] = '一月退款';
            $exportTitle[] = '二月收入';
            $exportTitle[] = '二月退款';
            $exportTitle[] = '三月收入';
            $exportTitle[] = '三月退款';
            $exportTitle[] = '四月收入';
            $exportTitle[] = '四月退款';
            $exportTitle[] = '五月收入';
            $exportTitle[] = '五月退款';
            $exportTitle[] = '六月收入';
            $exportTitle[] = '六月退款';
            $exportTitle[] = '七月收入';
            $exportTitle[] = '七月退款';
            $exportTitle[] = '八月收入';
            $exportTitle[] = '八月退款';
            $exportTitle[] = '九月收入';
            $exportTitle[] = '九月退款';
            $exportTitle[] = '十月收入';
            $exportTitle[] = '十月退款';
            $exportTitle[] = '十一月收入';
            $exportTitle[] = '十一月退款';
            $exportTitle[] = '十二月收入';
            $exportTitle[] = '十二月退款';
            $exportTitle[] = '合计收入';
            $exportTitle[] = '合计退款';
            $sumOrder['charge_name'] = '合计';
            $sumOrder = array_merge($sumOrder, $monthArr);
        }
        $monthDataArr = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');
        $exportData = array();
        $this_year_start_time = strtotime($year_v . '-01-01 00:00:00');
        $year_all_total_money_special = 0;
        if ($ruleIds) {
            $houseNewChargeRuleDb = new HouseNewChargeRule();
            $ruleWhere = array();
            //$ruleWhere[] = array('village_id', '=', $village_id);
            $ruleWhere[] = array('id', 'in', $ruleIds);
            $chargeRule = $houseNewChargeRuleDb->getList($ruleWhere, 'id,subject_id,charge_project_id,charge_name,status', 'id desc', $page, $limit);
            if ($chargeRule && !$chargeRule->isEmpty()) {
                $chargeRule = $chargeRule->toArray();
                foreach ($chargeRule as $kkk => $crvv) {
                    $crvv = array_merge($crvv, $monthArr);
                    $chargeRule[$kkk] = $crvv;
                    if ($crvv['status'] == 4) {
                        $chargeRule[$kkk]['charge_name'] = $crvv['charge_name'] . '(已删除)';
                    }
                    $year_all_total_money = 0;
                    $year_all_total_refund_money = 0;
                    $tmpArr = array();
                    if ($is_export) {
                        $tmpArr['charge_name'] = $chargeRule[$kkk]['charge_name'];
                        $tmpArr = array_merge($tmpArr, $monthArr);
                    }

                    foreach ($monthDataArr as $mvv) {
                        $tmpWhereArr = $whereArr;
                        $whereArrSpecialTmp = $whereArrSpecial;
                        $_start_time = strtotime($year_v . '-' . $mvv . '-01 00:00:00');
                        $tmp_time = strtotime($year_v . '-' . $mvv . '-01 10:10:00');
                        $last_day = date('t', $tmp_time);
                        $_end_time = strtotime($year_v . '-' . $mvv . '-' . $last_day . ' 23:59:59');
                        if ($order_service_type == 0) {
                            if ($pay_status == 'no_pay') {
                                $tmpWhereArr[] = ['add_time', '>=', $_start_time];
                                $tmpWhereArr[] = ['add_time', '<=', $_end_time];
                            } else {
                                $tmpWhereArr[] = ['pay_time', '>=', $_start_time];
                                $tmpWhereArr[] = ['pay_time', '<=', $_end_time];
                            }
                        } else if ($order_service_type == 1) {
                            //按开始时间
                            $tmpWhereArr[] = ['service_start_time', '>=', $_start_time];
                            $tmpWhereArr[] = ['service_start_time', '<=', $_end_time];

                            $whereArrSpecialTmp[] = ['service_start_time', '>=', $_start_time];
                            $whereArrSpecialTmp[] = ['service_start_time', '<=', $_end_time];
                        } elseif ($order_service_type == 2) {
                            //按结束时间
                            $tmpWhereArr[] = ['service_end_time', '>=', $_start_time];
                            $tmpWhereArr[] = ['service_end_time', '<=', $_end_time];

                            $whereArrSpecialTmp[] = ['service_end_time', '>=', $_start_time];
                            $whereArrSpecialTmp[] = ['service_end_time', '<=', $_end_time];
                        } elseif ($order_service_type == 3) {
                            //按计费开始结束时间
                            $tmpWhereArr[] = ['service_start_time', '>=', $this_year_start_time];
                            $tmpWhereArr[] = ['service_end_time', '>=', $_start_time];
                            $tmpWhereArr[] = ['service_end_time', '<=', $_end_time];
                        }

                        if ($where_rule_id > 0 && $crvv['id'] == $where_rule_id) {
                            $tmpSumField = 'sum(ROUND(modify_money,2)) as all_modify_money,sum(ROUND(late_payment_money,2)) as all_late_payment_money,sum(ROUND(pay_money + COALESCE(discount_money,0),2)) as all_pay_money,sum(ROUND(refund_money,2)) as all_refund_money';
                            $sumOrderMoney = $db_house_new_pay_order->get_one($tmpWhereArr, $tmpSumField);
                            if ($sumOrderMoney && isset($sumOrderMoney['all_modify_money'])) {
                                $all_total_money = $sumOrderMoney['all_modify_money'] + $sumOrderMoney['all_late_payment_money'];
                                $order_key = 'month' . $mvv . '_no_pay';
                                if ($pay_status == 'is_pay') {
                                    $order_key = 'month' . $mvv . '_is_pay';
                                    $order_refund_key = 'month' . $mvv . '_refund';
                                    $all_total_money = $sumOrderMoney['all_pay_money'];
                                    $all_total_refund_money = $sumOrderMoney['all_refund_money'];
                                    $all_total_refund_money = round_number($all_total_refund_money);
                                    $chargeRule[$kkk][$order_refund_key] = $all_total_refund_money;
                                    if ($is_export) {
                                        $tmpArr[$order_refund_key] = $all_total_refund_money;
                                    }
                                    $year_all_total_refund_money += $all_total_refund_money;
                                }
                                $all_total_money = round_number($all_total_money);
                                $year_all_total_money += $all_total_money;
                                $chargeRule[$kkk][$order_key] += $all_total_money;
                                $chargeRule[$kkk][$order_key] = round($chargeRule[$kkk][$order_key], 2);
                                if ($is_export) {
                                    $tmpArr[$order_key] += $all_total_money;
                                    $tmpArr[$order_key] = round($tmpArr[$order_key], 2);
                                }
                            }

                            if ($funcKaidalaicnSpecial && $pay_status == 'is_pay' && in_array($order_service_type, [1, 2])) {
                                $tmpSumFieldx = 'order_id,modify_money,(pay_money + COALESCE(discount_money,0)) as pay_money,order_type,order_type_flag,red_reversal_money,append_later_money,service_start_time,service_end_time';
                                $payOrderSpecialObj = $db_house_new_pay_order->getOrder($whereArrSpecialTmp, $tmpSumFieldx);
                                $payOrderSpecials = array();
                                if ($payOrderSpecialObj && !$payOrderSpecialObj->isEmpty()) {
                                    $payOrderSpecials = $payOrderSpecialObj->toArray();
                                }
                                if ($payOrderSpecials) {
                                    foreach ($payOrderSpecials as $sov) {
                                        $start_m = date('m', $sov['service_start_time']);
                                        $start_m = intval($start_m);
                                        $end_m = date('m', $sov['service_end_time']);
                                        $end_m = intval($end_m);
                                        $len_m = $end_m - $start_m;
                                        if ($len_m > 0) {
                                            $pay_money_s = $sov['pay_money'] / ($len_m + 1);
                                            $pay_money_s = round_number($pay_money_s);
                                            $pay_money_s_t = 0;
                                            for ($sii = 0; $sii <= $len_m; $sii++) {
                                                $start_m_s = $start_m + $sii;
                                                $order_key = 'month' . str_pad($start_m_s, 2, '0', STR_PAD_LEFT) . '_is_pay';
                                                if ($sii == $len_m) {
                                                    $pay_money_s = $sov['pay_money'] - $pay_money_s_t;
                                                    $pay_money_s = round_number($pay_money_s);
                                                } else {
                                                    $pay_money_s_t += $pay_money_s;
                                                }
                                                $year_all_total_money += $pay_money_s;
                                                $year_all_total_money_special += $pay_money_s;
                                                $chargeRule[$kkk][$order_key] += $pay_money_s;
                                                $chargeRule[$kkk][$order_key] = round($chargeRule[$kkk][$order_key], 2);
                                                $sumOrder[$order_key] += $pay_money_s;
                                                $sumOrder[$order_key] = round($sumOrder[$order_key], 2);
                                                if ($is_export) {
                                                    $tmpArr[$order_key] += $pay_money_s;
                                                    $tmpArr[$order_key] = round($tmpArr[$order_key], 2);
                                                }
                                            }
                                        } else {
                                            $order_key = 'month' . str_pad($start_m, 2, '0', STR_PAD_LEFT) . '_is_pay';
                                            $pay_money_s = round_number($sov['pay_money']);
                                            $year_all_total_money += $pay_money_s;
                                            $year_all_total_money_special += $pay_money_s;
                                            $chargeRule[$kkk][$order_key] += $pay_money_s;
                                            $chargeRule[$kkk][$order_key] = round($chargeRule[$kkk][$order_key], 2);
                                            $sumOrder[$order_key] += $pay_money_s;
                                            $sumOrder[$order_key] = round($sumOrder[$order_key], 2);
                                            if ($is_export) {
                                                $tmpArr[$order_key] += $pay_money_s;
                                                $tmpArr[$order_key] = round($tmpArr[$order_key], 2);
                                            }
                                        }
                                    }
                                }
                            }
                        } else if ($where_rule_id < 1 && !empty($crvv['id'])) {
                            $tmpWhereArr[] = array('rule_id', '=', $crvv['id']);
                            $tmpSumField = 'sum(ROUND(modify_money,2)) as all_modify_money,sum(ROUND(late_payment_money,2)) as all_late_payment_money,sum(ROUND(pay_money + COALESCE(discount_money,0),2)) as all_pay_money,sum(ROUND(refund_money,2)) as all_refund_money';
                            $sumOrderMoney = $db_house_new_pay_order->get_one($tmpWhereArr, $tmpSumField);
                            if ($sumOrderMoney && isset($sumOrderMoney['all_modify_money'])) {
                                $all_total_money = $sumOrderMoney['all_modify_money'] + $sumOrderMoney['all_late_payment_money'];
                                $order_key = 'month' . $mvv . '_no_pay';
                                if ($pay_status == 'is_pay') {
                                    $order_key = 'month' . $mvv . '_is_pay';
                                    $order_refund_key = 'month' . $mvv . '_refund';
                                    $all_total_money = $sumOrderMoney['all_pay_money'];
                                    $all_total_refund_money = $sumOrderMoney['all_refund_money'];
                                    $all_total_refund_money = round_number($all_total_refund_money);
                                    $chargeRule[$kkk][$order_refund_key] = $all_total_refund_money;
                                    if ($is_export) {
                                        $tmpArr[$order_refund_key] = $all_total_refund_money;
                                    }
                                    $year_all_total_refund_money += $all_total_refund_money;
                                }
                                $all_total_money = round_number($all_total_money);
                                $year_all_total_money += $all_total_money;
                                $chargeRule[$kkk][$order_key] += $all_total_money;
                                $chargeRule[$kkk][$order_key] = round($chargeRule[$kkk][$order_key], 2);
                                if ($is_export) {
                                    $tmpArr[$order_key] += $all_total_money;
                                    $tmpArr[$order_key] = round($tmpArr[$order_key], 2);
                                }
                            }

                            if ($funcKaidalaicnSpecial && $pay_status == 'is_pay' && in_array($order_service_type, [1, 2])) {
                                $whereArrSpecialTmp[] = array('rule_id', '=', $crvv['id']);
                                $tmpSumFieldx = 'order_id,modify_money,(pay_money + COALESCE(discount_money,0)) as pay_money,order_type,order_type_flag,red_reversal_money,append_later_money,service_start_time,service_end_time';
                                $payOrderSpecialObj = $db_house_new_pay_order->getOrder($whereArrSpecialTmp, $tmpSumFieldx);
                                $payOrderSpecials = array();
                                if ($payOrderSpecialObj && !$payOrderSpecialObj->isEmpty()) {
                                    $payOrderSpecials = $payOrderSpecialObj->toArray();
                                }
                                //fdump_api([__LINE__, 'payOrderSpecials' => $payOrderSpecials], '00payOrderSpecials', 1);
                                if ($payOrderSpecials) {
                                    foreach ($payOrderSpecials as $sov) {
                                        $start_m = date('m', $sov['service_start_time']);
                                        $start_m = intval($start_m);
                                        $end_m = date('m', $sov['service_end_time']);
                                        $end_m = intval($end_m);
                                        $len_m = $end_m - $start_m;
                                        if ($len_m > 0) {
                                            $pay_money_s = $sov['pay_money'] / ($len_m + 1);
                                            $pay_money_s = round_number($pay_money_s);
                                            $pay_money_s_t = 0;
                                            for ($sii = 0; $sii <= $len_m; $sii++) {
                                                $start_m_s = $start_m + $sii;
                                                $order_key = 'month' . str_pad($start_m_s, 2, '0', STR_PAD_LEFT) . '_is_pay';
                                                if ($sii == $len_m) {
                                                    $pay_money_s = $sov['pay_money'] - $pay_money_s_t;
                                                    $pay_money_s = round_number($pay_money_s);
                                                } else {
                                                    $pay_money_s_t += $pay_money_s;
                                                }
                                                $year_all_total_money += $pay_money_s;
                                                $year_all_total_money_special += $pay_money_s;
                                                $chargeRule[$kkk][$order_key] += $pay_money_s;
                                                $chargeRule[$kkk][$order_key] = round($chargeRule[$kkk][$order_key], 2);
                                                $sumOrder[$order_key] += $pay_money_s;
                                                $sumOrder[$order_key] = round($sumOrder[$order_key], 2);
                                                //fdump_api([__LINE__,'order_key'=>$order_key, 'chargeRule[kkk]' => $chargeRule[$kkk]], '00payOrderSpecials', 1);
                                                if ($is_export) {
                                                    $tmpArr[$order_key] += $pay_money_s;
                                                    $tmpArr[$order_key] = round($tmpArr[$order_key], 2);
                                                }
                                            }
                                        } else {
                                            $order_key = 'month' . str_pad($start_m, 2, '0', STR_PAD_LEFT) . '_is_pay';
                                            $pay_money_s = round_number($sov['pay_money']);
                                            $year_all_total_money += $pay_money_s;
                                            $year_all_total_money_special += $pay_money_s;
                                            $chargeRule[$kkk][$order_key] += $pay_money_s;
                                            $chargeRule[$kkk][$order_key] = round($chargeRule[$kkk][$order_key], 2);
                                            $sumOrder[$order_key] += $pay_money_s;
                                            $sumOrder[$order_key] = round($sumOrder[$order_key], 2);
                                            if ($is_export) {
                                                $tmpArr[$order_key] += $pay_money_s;
                                                $tmpArr[$order_key] = round($tmpArr[$order_key], 2);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $year_all_total_money = round_number($year_all_total_money);
                    $chargeRule[$kkk]['all_total_money'] = $year_all_total_money;
                    $year_all_total_refund_money = round_number($year_all_total_refund_money);
                    $chargeRule[$kkk]['all_total_refund_money'] = $year_all_total_refund_money;
                    if ($is_export) {
                        $tmpArr['all_total_money'] = $year_all_total_money;
                        if ($pay_status == 'is_pay') {
                            $tmpArr['all_total_refund_money'] = $year_all_total_refund_money;
                        }
                        $exportData[] = $tmpArr;
                    }
                }
                $year_all_total_money = 0;
                $year_all_total_refund_money = 0;
                if ($where_rule_id < 1) {
                    $whereArr[] = array('rule_id', 'in', $ruleIds);
                }
                foreach ($monthDataArr as $mvv) {
                    $tmpWhereArr = $whereArr;
                    $_start_time = strtotime($year_v . '-' . $mvv . '-01 00:00:00');
                    $tmp_time = strtotime($year_v . '-' . $mvv . '-01 10:10:00');
                    $last_day = date('t', $tmp_time);
                    $_end_time = strtotime($year_v . '-' . $mvv . '-' . $last_day . ' 23:59:59');
                    if ($order_service_type == 0) {
                        if ($pay_status == 'no_pay') {
                            $tmpWhereArr[] = ['add_time', '>=', $_start_time];
                            $tmpWhereArr[] = ['add_time', '<=', $_end_time];
                        } else {
                            $tmpWhereArr[] = ['pay_time', '>=', $_start_time];
                            $tmpWhereArr[] = ['pay_time', '<=', $_end_time];
                        }
                    } else if ($order_service_type == 1) {
                        //按开始时间
                        $tmpWhereArr[] = ['service_start_time', '>=', $_start_time];
                        $tmpWhereArr[] = ['service_start_time', '<=', $_end_time];

                    } elseif ($order_service_type == 2) {
                        //按结束时间
                        $tmpWhereArr[] = ['service_end_time', '>=', $_start_time];
                        $tmpWhereArr[] = ['service_end_time', '<=', $_end_time];
                    } elseif ($order_service_type == 3) {
                        //按计费开始结束时间
                        $tmpWhereArr[] = ['service_start_time', '>=', $this_year_start_time];
                        $tmpWhereArr[] = ['service_end_time', '>=', $_start_time];
                        $tmpWhereArr[] = ['service_end_time', '<=', $_end_time];
                    }

                    $tmpSumField = 'sum(ROUND(modify_money,2)) as all_modify_money,sum(ROUND(late_payment_money,2)) as all_late_payment_money,sum(ROUND(pay_money + COALESCE(discount_money,0),2)) as all_pay_money,sum(ROUND(refund_money,2)) as all_refund_money';
                    $sumOrderMoney = $db_house_new_pay_order->get_one($tmpWhereArr, $tmpSumField);
                    if ($sumOrderMoney && isset($sumOrderMoney['all_modify_money'])) {
                        $all_total_money = $sumOrderMoney['all_modify_money'] + $sumOrderMoney['all_late_payment_money'];
                        $order_key = 'month' . $mvv . '_no_pay';
                        if ($pay_status == 'is_pay') {
                            $order_key = 'month' . $mvv . '_is_pay';
                            $order_refund_key = 'month' . $mvv . '_refund';
                            $all_total_money = $sumOrderMoney['all_pay_money'];
                            $all_total_refund_money = $sumOrderMoney['all_refund_money'];
                            $all_total_refund_money = round_number($all_total_refund_money);
                            $sumOrder[$order_refund_key] = $all_total_refund_money;
                            $year_all_total_refund_money += $all_total_refund_money;
                        }
                        $all_total_money = round_number($all_total_money);
                        $year_all_total_money += $all_total_money;
                        $sumOrder[$order_key] += $all_total_money;
                        $sumOrder[$order_key] = round($sumOrder[$order_key], 2);
                    }
                }
                $year_all_total_money = $year_all_total_money + $year_all_total_money_special;
                $year_all_total_money = round_number($year_all_total_money);
                $sumOrder['all_total_money'] = $year_all_total_money;
                $year_all_total_refund_money = round_number($year_all_total_refund_money);
                $sumOrder['all_total_refund_money'] = $year_all_total_refund_money;
                $chargeRule[] = $sumOrder;
                if ($is_export) {
                    if ($pay_status == 'no_pay') {
                        unset($sumOrder['all_total_refund_money']);
                    }
                    $exportData[] = $sumOrder;
                }
                $returnArr['count'] = $houseNewChargeRuleDb->getCount($ruleWhere);

                $pageCount = $returnArr['count'] / $limit;
                $pageCount = floor($pageCount) + $returnArr['count'];
                $returnArr['count'] = intval($pageCount);

                $returnArr['lists'] = $chargeRule;
            }
        }
        $returnArr['year_v'] = $year_v;
        if ($is_export) {
            $returnArr['lists'] = $exportData;
            $returnArr['export_title'] = $exportTitle;
        }
        return $returnArr;
    }

    public function getSummaryByYearAndProjectList($village_id = 0, $whereArr = array(), $pay_status = '', $extraArr = array(), $page = 1, $limit = 10, $is_export = false)
    {
        $year_v = date('Y');
        $order_service_type = 0;
        if (isset($extraArr['year_v']) && $extraArr['year_v'] > 1970) {
            $year_v = $extraArr['year_v'];
        }
        if (isset($extraArr['order_service_type']) && $extraArr['order_service_type'] > 0) {
            $order_service_type = $extraArr['order_service_type'];
        }
        $returnArr = array('lists' => array(), 'count' => 0, 'total_limit' => $limit + 1);
        $db_house_new_pay_order = new HouseNewPayOrder();
        $order_rules = $db_house_new_pay_order->getOrderByGroup($whereArr, 'project_id', 'project_id');
        $projectIds = array();
        $sumOrder = array();
        $where_project_id = 0;

        foreach ($whereArr as $wk => $wv) {
            if ($wv['0'] == 'project_id') {
                $where_project_id = $wv['2'];
            }
        }
        if ($order_rules && !$order_rules->isEmpty()) {
            $order_rules = $order_rules->toArray();
            foreach ($order_rules as $rvv) {
                $projectIds[] = $rvv['project_id'];
            }
            $projectIds = array_unique($projectIds);
        }
        $configCustomizationService = new ConfigCustomizationService();
        $funcKaidalaicnSpecial = $configCustomizationService->getfuncKaidalaicnSpecial();
        $whereArrSpecial = $whereArr;
        if ($funcKaidalaicnSpecial && $pay_status == 'is_pay' && in_array($order_service_type, [1, 2])) {
            $whereArr[] = array('third_opt_status', '<>', 100);  //不拆单
            $whereArrSpecial[] = array('third_opt_status', '=', 100);  //拆单
            $whereArrSpecial[] = array('order_type_flag', '=', 'red_reversal_or_append_later');  //拆单
        }
        $exportTitle = array();
        $monthArr = array();
        if ($pay_status == 'no_pay') {
            $monthArr = array('month01_no_pay' => '0.00', 'month02_no_pay' => '0.00', 'month03_no_pay' => '0.00', 'month04_no_pay' => '0.00', 'month05_no_pay' => '0.00');
            $monthArr['month06_no_pay'] = '0.00';
            $monthArr['month07_no_pay'] = '0.00';
            $monthArr['month08_no_pay'] = '0.00';
            $monthArr['month09_no_pay'] = '0.00';
            $monthArr['month10_no_pay'] = '0.00';
            $monthArr['month11_no_pay'] = '0.00';
            $monthArr['month12_no_pay'] = '0.00';
            $exportTitle[] = '收费项目名称';
            $exportTitle[] = '一月欠费';
            $exportTitle[] = '二月欠费';
            $exportTitle[] = '三月欠费';
            $exportTitle[] = '四月欠费';
            $exportTitle[] = '五月欠费';
            $exportTitle[] = '六月欠费';
            $exportTitle[] = '七月欠费';
            $exportTitle[] = '八月欠费';
            $exportTitle[] = '九月欠费';
            $exportTitle[] = '十月欠费';
            $exportTitle[] = '十一月欠费';
            $exportTitle[] = '十二月欠费';
            $exportTitle[] = '合计';
            $sumOrder['charge_name'] = '合计';
            $sumOrder = array_merge($sumOrder, $monthArr);
        } else if ($pay_status == 'is_pay') {
            $monthArr = array('month01_is_pay' => '0.00', 'month01_refund' => '0.00', 'month02_is_pay' => '0.00', 'month02_refund' => '0.00', 'month03_is_pay' => '0.00', 'month03_refund' => '0.00');
            $monthArr['month04_is_pay'] = '0.00';
            $monthArr['month04_refund'] = '0.00';
            $monthArr['month05_is_pay'] = '0.00';
            $monthArr['month05_refund'] = '0.00';
            $monthArr['month06_is_pay'] = '0.00';
            $monthArr['month06_refund'] = '0.00';
            $monthArr['month07_is_pay'] = '0.00';
            $monthArr['month07_refund'] = '0.00';
            $monthArr['month08_is_pay'] = '0.00';
            $monthArr['month08_refund'] = '0.00';
            $monthArr['month09_is_pay'] = '0.00';
            $monthArr['month09_refund'] = '0.00';
            $monthArr['month10_is_pay'] = '0.00';
            $monthArr['month10_refund'] = '0.00';
            $monthArr['month11_is_pay'] = '0.00';
            $monthArr['month11_refund'] = '0.00';
            $monthArr['month12_is_pay'] = '0.00';
            $monthArr['month12_refund'] = '0.00';
            $exportTitle[] = '收费项目名称';
            $exportTitle[] = '一月收入';
            $exportTitle[] = '一月退款';
            $exportTitle[] = '二月收入';
            $exportTitle[] = '二月退款';
            $exportTitle[] = '三月收入';
            $exportTitle[] = '三月退款';
            $exportTitle[] = '四月收入';
            $exportTitle[] = '四月退款';
            $exportTitle[] = '五月收入';
            $exportTitle[] = '五月退款';
            $exportTitle[] = '六月收入';
            $exportTitle[] = '六月退款';
            $exportTitle[] = '七月收入';
            $exportTitle[] = '七月退款';
            $exportTitle[] = '八月收入';
            $exportTitle[] = '八月退款';
            $exportTitle[] = '九月收入';
            $exportTitle[] = '九月退款';
            $exportTitle[] = '十月收入';
            $exportTitle[] = '十月退款';
            $exportTitle[] = '十一月收入';
            $exportTitle[] = '十一月退款';
            $exportTitle[] = '十二月收入';
            $exportTitle[] = '十二月退款';
            $exportTitle[] = '合计收入';
            $exportTitle[] = '合计退款';
            $sumOrder['charge_name'] = '合计';
            $sumOrder = array_merge($sumOrder, $monthArr);
        }
        $monthDataArr = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');
        $exportData = array();
        $this_year_start_time = strtotime($year_v . '-01-01 00:00:00');
        $year_all_total_money_special = 0;
        if ($projectIds) {
            $houseNewChargeProjectDb = new HouseNewChargeProject();
            $projectWhere = array();
            //$projectWhere[] = array('village_id', '=', $village_id);
            $projectWhere[] = array('id', 'in', $projectIds);
            $chargeProject = $houseNewChargeProjectDb->getLists($projectWhere, 'id,subject_id,name,status', 'id desc', $page, $limit);
            if ($chargeProject && !$chargeProject->isEmpty()) {
                $chargeProject = $chargeProject->toArray();
                foreach ($chargeProject as $kkk => $crvv) {
                    $crvv = array_merge($crvv, $monthArr);
                    $chargeProject[$kkk] = $crvv;
                    $chargeProject[$kkk]['charge_name'] = $crvv['name'];
                    if ($crvv['status'] == 4) {
                        $chargeProject[$kkk]['charge_name'] = $crvv['name'] . '(已删除)';
                    }
                    $year_all_total_money = 0;
                    $year_all_total_refund_money = 0;
                    $tmpArr = array();
                    if ($is_export) {
                        $tmpArr['charge_name'] = $chargeProject[$kkk]['charge_name'];
                        $tmpArr = array_merge($tmpArr, $monthArr);
                    }

                    foreach ($monthDataArr as $mvv) {
                        $tmpWhereArr = $whereArr;
                        $whereArrSpecialTmp = $whereArrSpecial;
                        $_start_time = strtotime($year_v . '-' . $mvv . '-01 00:00:00');
                        $tmp_time = strtotime($year_v . '-' . $mvv . '-01 10:10:00');
                        $last_day = date('t', $tmp_time);
                        $_end_time = strtotime($year_v . '-' . $mvv . '-' . $last_day . ' 23:59:59');
                        if ($order_service_type == 0) {
                            if ($pay_status == 'no_pay') {
                                $tmpWhereArr[] = ['add_time', '>=', $_start_time];
                                $tmpWhereArr[] = ['add_time', '<=', $_end_time];
                            } else {
                                $tmpWhereArr[] = ['pay_time', '>=', $_start_time];
                                $tmpWhereArr[] = ['pay_time', '<=', $_end_time];
                            }
                        } else if ($order_service_type == 1) {
                            //按开始时间
                            $tmpWhereArr[] = ['service_start_time', '>=', $_start_time];
                            $tmpWhereArr[] = ['service_start_time', '<=', $_end_time];

                            $whereArrSpecialTmp[] = ['service_start_time', '>=', $_start_time];
                            $whereArrSpecialTmp[] = ['service_start_time', '<=', $_end_time];
                        } elseif ($order_service_type == 2) {
                            //按结束时间
                            $tmpWhereArr[] = ['service_end_time', '>=', $_start_time];
                            $tmpWhereArr[] = ['service_end_time', '<=', $_end_time];

                            $whereArrSpecialTmp[] = ['service_end_time', '>=', $_start_time];
                            $whereArrSpecialTmp[] = ['service_end_time', '<=', $_end_time];
                        } elseif ($order_service_type == 3) {
                            //按计费开始结束时间
                            $tmpWhereArr[] = ['service_start_time', '>=', $this_year_start_time];
                            $tmpWhereArr[] = ['service_end_time', '>=', $_start_time];
                            $tmpWhereArr[] = ['service_end_time', '<=', $_end_time];
                        }

                        if ($where_project_id > 0 && $crvv['id'] == $where_project_id) {
                            $tmpSumField = 'sum(ROUND(modify_money,2)) as all_modify_money,sum(ROUND(late_payment_money,2)) as all_late_payment_money,sum(ROUND(pay_money + COALESCE(discount_money,0),2)) as all_pay_money,sum(ROUND(refund_money,2)) as all_refund_money';
                            $sumOrderMoney = $db_house_new_pay_order->get_one($tmpWhereArr, $tmpSumField);
                            if ($sumOrderMoney && isset($sumOrderMoney['all_modify_money'])) {
                                $all_total_money = $sumOrderMoney['all_modify_money'] + $sumOrderMoney['all_late_payment_money'];
                                $order_key = 'month' . $mvv . '_no_pay';
                                if ($pay_status == 'is_pay') {
                                    $order_key = 'month' . $mvv . '_is_pay';
                                    $order_refund_key = 'month' . $mvv . '_refund';
                                    $all_total_money = $sumOrderMoney['all_pay_money'];
                                    $all_total_refund_money = $sumOrderMoney['all_refund_money'];
                                    $all_total_refund_money = round_number($all_total_refund_money);
                                    $chargeProject[$kkk][$order_refund_key] = $all_total_refund_money;
                                    if ($is_export) {
                                        $tmpArr[$order_refund_key] = $all_total_refund_money;
                                    }
                                    $year_all_total_refund_money += $all_total_refund_money;
                                }
                                $all_total_money = round_number($all_total_money);
                                $year_all_total_money += $all_total_money;
                                $chargeProject[$kkk][$order_key] += $all_total_money;
                                $chargeProject[$kkk][$order_key] = round($chargeProject[$kkk][$order_key], 2);
                                if ($is_export) {
                                    $tmpArr[$order_key] += $all_total_money;
                                    $tmpArr[$order_key] = round($tmpArr[$order_key], 2);
                                }
                            }

                            if ($funcKaidalaicnSpecial && $pay_status == 'is_pay' && in_array($order_service_type, [1, 2])) {
                                $tmpSumFieldx = 'order_id,modify_money,(pay_money + COALESCE(discount_money,0)) as pay_money,order_type,order_type_flag,red_reversal_money,append_later_money,service_start_time,service_end_time';
                                $payOrderSpecialObj = $db_house_new_pay_order->getOrder($whereArrSpecialTmp, $tmpSumFieldx);
                                $payOrderSpecials = array();
                                if ($payOrderSpecialObj && !$payOrderSpecialObj->isEmpty()) {
                                    $payOrderSpecials = $payOrderSpecialObj->toArray();
                                }
                                if ($payOrderSpecials) {
                                    foreach ($payOrderSpecials as $sov) {
                                        $start_m = date('m', $sov['service_start_time']);
                                        $start_m = intval($start_m);
                                        $end_m = date('m', $sov['service_end_time']);
                                        $end_m = intval($end_m);
                                        $len_m = $end_m - $start_m;
                                        if ($len_m > 0) {
                                            $pay_money_s = $sov['pay_money'] / ($len_m + 1);
                                            $pay_money_s = round_number($pay_money_s);
                                            $pay_money_s_t = 0;
                                            for ($sii = 0; $sii <= $len_m; $sii++) {
                                                $start_m_s = $start_m + $sii;
                                                $order_key = 'month' . str_pad($start_m_s, 2, '0', STR_PAD_LEFT) . '_is_pay';
                                                if ($sii == $len_m) {
                                                    $pay_money_s = $sov['pay_money'] - $pay_money_s_t;
                                                    $pay_money_s = round_number($pay_money_s);
                                                } else {
                                                    $pay_money_s_t += $pay_money_s;
                                                }
                                                $year_all_total_money += $pay_money_s;
                                                $year_all_total_money_special += $pay_money_s;
                                                $chargeProject[$kkk][$order_key] += $pay_money_s;
                                                $chargeProject[$kkk][$order_key] = round($chargeProject[$kkk][$order_key], 2);
                                                $sumOrder[$order_key] += $pay_money_s;
                                                $sumOrder[$order_key] = round($sumOrder[$order_key], 2);
                                                if ($is_export) {
                                                    $tmpArr[$order_key] += $pay_money_s;
                                                    $tmpArr[$order_key] = round($tmpArr[$order_key], 2);
                                                }
                                            }
                                        } else {
                                            $order_key = 'month' . str_pad($start_m, 2, '0', STR_PAD_LEFT) . '_is_pay';
                                            $pay_money_s = round_number($sov['pay_money']);
                                            $year_all_total_money += $pay_money_s;
                                            $year_all_total_money_special += $pay_money_s;
                                            $chargeProject[$kkk][$order_key] += $pay_money_s;
                                            $chargeProject[$kkk][$order_key] = round($chargeProject[$kkk][$order_key], 2);
                                            $sumOrder[$order_key] += $pay_money_s;
                                            $sumOrder[$order_key] = round($sumOrder[$order_key], 2);
                                            if ($is_export) {
                                                $tmpArr[$order_key] += $pay_money_s;
                                                $tmpArr[$order_key] = round($tmpArr[$order_key], 2);
                                            }
                                        }
                                    }
                                }
                            }
                        } else if ($where_project_id < 1 && !empty($crvv['id'])) {
                            $tmpWhereArr[] = array('project_id', '=', $crvv['id']);
                            $tmpSumField = 'sum(ROUND(modify_money,2)) as all_modify_money,sum(ROUND(late_payment_money,2)) as all_late_payment_money,sum(ROUND(pay_money + COALESCE(discount_money,0),2)) as all_pay_money,sum(ROUND(refund_money,2)) as all_refund_money';
                            $sumOrderMoney = $db_house_new_pay_order->get_one($tmpWhereArr, $tmpSumField);
                            if ($sumOrderMoney && isset($sumOrderMoney['all_modify_money'])) {
                                $all_total_money = $sumOrderMoney['all_modify_money'] + $sumOrderMoney['all_late_payment_money'];
                                $order_key = 'month' . $mvv . '_no_pay';
                                if ($pay_status == 'is_pay') {
                                    $order_key = 'month' . $mvv . '_is_pay';
                                    $order_refund_key = 'month' . $mvv . '_refund';
                                    $all_total_money = $sumOrderMoney['all_pay_money'];
                                    $all_total_refund_money = $sumOrderMoney['all_refund_money'];
                                    $all_total_refund_money = round_number($all_total_refund_money);
                                    $chargeProject[$kkk][$order_refund_key] = $all_total_refund_money;
                                    if ($is_export) {
                                        $tmpArr[$order_refund_key] = $all_total_refund_money;
                                    }
                                    $year_all_total_refund_money += $all_total_refund_money;
                                }
                                $all_total_money = round_number($all_total_money);
                                $year_all_total_money += $all_total_money;
                                $chargeProject[$kkk][$order_key] += $all_total_money;
                                $chargeProject[$kkk][$order_key] = round($chargeProject[$kkk][$order_key], 2);
                                if ($is_export) {
                                    $tmpArr[$order_key] += $all_total_money;
                                    $tmpArr[$order_key] = round($tmpArr[$order_key], 2);
                                }
                            }

                            if ($funcKaidalaicnSpecial && $pay_status == 'is_pay' && in_array($order_service_type, [1, 2])) {
                                $whereArrSpecialTmp[] = array('project_id', '=', $crvv['id']);
                                $tmpSumFieldx = 'order_id,modify_money,(pay_money + COALESCE(discount_money,0)) as pay_money,order_type,order_type_flag,red_reversal_money,append_later_money,service_start_time,service_end_time';
                                $payOrderSpecialObj = $db_house_new_pay_order->getOrder($whereArrSpecialTmp, $tmpSumFieldx);
                                $payOrderSpecials = array();
                                if ($payOrderSpecialObj && !$payOrderSpecialObj->isEmpty()) {
                                    $payOrderSpecials = $payOrderSpecialObj->toArray();
                                }
                                //fdump_api([__LINE__, 'payOrderSpecials' => $payOrderSpecials], '00payOrderSpecials', 1);
                                if ($payOrderSpecials) {
                                    foreach ($payOrderSpecials as $sov) {
                                        $start_m = date('m', $sov['service_start_time']);
                                        $start_m = intval($start_m);
                                        $end_m = date('m', $sov['service_end_time']);
                                        $end_m = intval($end_m);
                                        $len_m = $end_m - $start_m;
                                        if ($len_m > 0) {
                                            $pay_money_s = $sov['pay_money'] / ($len_m + 1);
                                            $pay_money_s = round_number($pay_money_s);
                                            $pay_money_s_t = 0;
                                            for ($sii = 0; $sii <= $len_m; $sii++) {
                                                $start_m_s = $start_m + $sii;
                                                $order_key = 'month' . str_pad($start_m_s, 2, '0', STR_PAD_LEFT) . '_is_pay';
                                                if ($sii == $len_m) {
                                                    $pay_money_s = $sov['pay_money'] - $pay_money_s_t;
                                                    $pay_money_s = round_number($pay_money_s);
                                                } else {
                                                    $pay_money_s_t += $pay_money_s;
                                                }
                                                $year_all_total_money += $pay_money_s;
                                                $year_all_total_money_special += $pay_money_s;
                                                $chargeProject[$kkk][$order_key] += $pay_money_s;
                                                $chargeProject[$kkk][$order_key] = round($chargeProject[$kkk][$order_key], 2);
                                                $sumOrder[$order_key] += $pay_money_s;
                                                $sumOrder[$order_key] = round($sumOrder[$order_key], 2);
                                                //fdump_api([__LINE__,'order_key'=>$order_key, 'chargeRule[kkk]' => $chargeProject[$kkk]], '00payOrderSpecials', 1);
                                                if ($is_export) {
                                                    $tmpArr[$order_key] += $pay_money_s;
                                                    $tmpArr[$order_key] = round($tmpArr[$order_key], 2);
                                                }
                                            }
                                        } else {
                                            $order_key = 'month' . str_pad($start_m, 2, '0', STR_PAD_LEFT) . '_is_pay';
                                            $pay_money_s = round_number($sov['pay_money']);
                                            $year_all_total_money += $pay_money_s;
                                            $year_all_total_money_special += $pay_money_s;
                                            $chargeProject[$kkk][$order_key] += $pay_money_s;
                                            $chargeProject[$kkk][$order_key] = round($chargeProject[$kkk][$order_key], 2);
                                            $sumOrder[$order_key] += $pay_money_s;
                                            $sumOrder[$order_key] = round($sumOrder[$order_key], 2);
                                            if ($is_export) {
                                                $tmpArr[$order_key] += $pay_money_s;
                                                $tmpArr[$order_key] = round($tmpArr[$order_key], 2);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $year_all_total_money = round_number($year_all_total_money);
                    $chargeProject[$kkk]['all_total_money'] = $year_all_total_money;
                    $year_all_total_refund_money = round_number($year_all_total_refund_money);
                    $chargeProject[$kkk]['all_total_refund_money'] = $year_all_total_refund_money;
                    if ($is_export) {
                        $tmpArr['all_total_money'] = $year_all_total_money;
                        if ($pay_status == 'is_pay') {
                            $tmpArr['all_total_refund_money'] = $year_all_total_refund_money;
                        }
                        $exportData[] = $tmpArr;
                    }
                }
                $year_all_total_money = 0;
                $year_all_total_refund_money = 0;
                if ($where_project_id < 1) {
                    $whereArr[] = array('project_id', 'in', $projectIds);
                }
                foreach ($monthDataArr as $mvv) {
                    $tmpWhereArr = $whereArr;
                    $_start_time = strtotime($year_v . '-' . $mvv . '-01 00:00:00');
                    $tmp_time = strtotime($year_v . '-' . $mvv . '-01 10:10:00');
                    $last_day = date('t', $tmp_time);
                    $_end_time = strtotime($year_v . '-' . $mvv . '-' . $last_day . ' 23:59:59');
                    if ($order_service_type == 0) {
                        if ($pay_status == 'no_pay') {
                            $tmpWhereArr[] = ['add_time', '>=', $_start_time];
                            $tmpWhereArr[] = ['add_time', '<=', $_end_time];
                        } else {
                            $tmpWhereArr[] = ['pay_time', '>=', $_start_time];
                            $tmpWhereArr[] = ['pay_time', '<=', $_end_time];
                        }
                    } else if ($order_service_type == 1) {
                        //按开始时间
                        $tmpWhereArr[] = ['service_start_time', '>=', $_start_time];
                        $tmpWhereArr[] = ['service_start_time', '<=', $_end_time];

                    } elseif ($order_service_type == 2) {
                        //按结束时间
                        $tmpWhereArr[] = ['service_end_time', '>=', $_start_time];
                        $tmpWhereArr[] = ['service_end_time', '<=', $_end_time];
                    } elseif ($order_service_type == 3) {
                        //按计费开始结束时间
                        $tmpWhereArr[] = ['service_start_time', '>=', $this_year_start_time];
                        $tmpWhereArr[] = ['service_end_time', '>=', $_start_time];
                        $tmpWhereArr[] = ['service_end_time', '<=', $_end_time];
                    }

                    $tmpSumField = 'sum(ROUND(modify_money,2)) as all_modify_money,sum(ROUND(late_payment_money,2)) as all_late_payment_money,sum(ROUND(pay_money + COALESCE(discount_money,0),2)) as all_pay_money,sum(ROUND(refund_money,2)) as all_refund_money';
                    $sumOrderMoney = $db_house_new_pay_order->get_one($tmpWhereArr, $tmpSumField);
                    if ($sumOrderMoney && isset($sumOrderMoney['all_modify_money'])) {
                        $all_total_money = $sumOrderMoney['all_modify_money'] + $sumOrderMoney['all_late_payment_money'];
                        $order_key = 'month' . $mvv . '_no_pay';
                        if ($pay_status == 'is_pay') {
                            $order_key = 'month' . $mvv . '_is_pay';
                            $order_refund_key = 'month' . $mvv . '_refund';
                            $all_total_money = $sumOrderMoney['all_pay_money'];
                            $all_total_refund_money = $sumOrderMoney['all_refund_money'];
                            $all_total_refund_money = round_number($all_total_refund_money);
                            $sumOrder[$order_refund_key] = $all_total_refund_money;
                            $year_all_total_refund_money += $all_total_refund_money;
                        }
                        $all_total_money = round_number($all_total_money);
                        $year_all_total_money += $all_total_money;
                        $sumOrder[$order_key] += $all_total_money;
                        $sumOrder[$order_key] = round($sumOrder[$order_key], 2);
                    }
                }
                $year_all_total_money = $year_all_total_money + $year_all_total_money_special;
                $year_all_total_money = round_number($year_all_total_money);
                $sumOrder['all_total_money'] = $year_all_total_money;
                $year_all_total_refund_money = round_number($year_all_total_refund_money);
                $sumOrder['all_total_refund_money'] = $year_all_total_refund_money;
                $chargeProject[] = $sumOrder;
                if ($is_export) {
                    if ($pay_status == 'no_pay') {
                        unset($sumOrder['all_total_refund_money']);
                    }
                    $exportData[] = $sumOrder;
                }
                $returnArr['count'] = $houseNewChargeProjectDb->getProjectCount($projectWhere);
                $pageCount = $returnArr['count'] / $limit;
                $pageCount = floor($pageCount) + $returnArr['count'];
                $returnArr['count'] = intval($pageCount);

                $returnArr['lists'] = $chargeProject;
            }
        }
        $returnArr['year_v'] = $year_v;
        if ($is_export) {
            $returnArr['lists'] = $exportData;
            $returnArr['export_title'] = $exportTitle;
        }
        return $returnArr;
    }

    public function getSummaryByProjectList($village_id = 0, $whereArr = array(), $extraArr = array(), $page = 1, $limit = 10, $whereRefundArr = array(), $is_export = false)
    {
        $returnArr = array('lists' => array(), 'count' => 0, 'total_limit' => $limit + 2);
        $houseNewChargeProject = new HouseNewChargeProject();
        $projectWhere = array();
        $projectWhere[] = ['village_id', '=', $village_id];
        $projectWhere[] = ['status', '<', 4];
        if ($extraArr && isset($extraArr['charge_project_id']) && $extraArr['charge_project_id']) {
            $projectWhere[] = ['id', '=', $extraArr['charge_project_id']];
        }
        $db_house_new_pay_order = new HouseNewPayOrder();

        $pay_order_project = $db_house_new_pay_order->getSumByGroupOrder($whereArr, 'o.project_id', 'o.project_id,sum(o.pay_money) as t_pay_money', 1, 100, 't_pay_money desc');
        $project_id_arr = array();
        if ($pay_order_project && !$pay_order_project->isEmpty()) {
            $pay_order_project = $pay_order_project->toArray();
            foreach ($pay_order_project as $op) {
                $project_id_arr[] = $op['project_id'];
            }
        }
        $property_id = isset($extraArr['property_id']) ? $extraArr['property_id'] : 0;
        $orderStr = 'id desc';
        if (!empty($project_id_arr)) {
            $project_id_arr = array_reverse($project_id_arr);
            $project_id_str = implode(',', $project_id_arr);
            $orderStr = new Raw('field (id,' . $project_id_str . ') desc,id desc');
        }
        $houseNewCashierService = new HouseNewCashierService();
        $exportTitle = array('项目名称', '本日实收');
        $exportData = array();
        $payTypeList = $houseNewCashierService->getPayTypeList($property_id, '');
        $userBalanceArr = array('name' => '住户余额预存', 'id' => 'balance', 'all_pay_money' => 0, 'discount_money' => 0, 'modify_discount_money' => 0, 'all_refund_money' => 0, 'all_total_money' => 0, 'village_id' => $village_id);
        $villageUserMoneyLogDb = new VillageUserMoneyLog();
        $whereMoneyLog = array(['business_id', '=', $village_id], ['business_type', '=', 1], ['type', '=', 1], ['add_time', '>=', $extraArr['date_time']], ['add_time', '<=', $extraArr['date_time_end']]);
        $allTotalArr = array('name' => '总合计', 'all_pay_money' => 0);
        $pay_type_arr1 = $houseNewCashierService->pay_type_arr1;
        $userBalanceArrTmp=array('name' => '住户余额预存', 'all_pay_money' => 0);
        foreach ($payTypeList as $ptk => $tvt) {
            $pay_type_arr = explode('-', $tvt['id']);
            $pay_type_where_order = array();
            $online_pay_type = array();
            $offline_pay_type_order = 0;
            $where_summary_pay_type = 0;
            $paytype_key = 'paytype_' . $tvt['id'];
            $userBalanceArr[$paytype_key] = 0;
            if ($pay_type_arr[0] == 4 && $pay_type_arr[1] == 0) {
                //去掉线上支付-余额支付
                continue;
            }
            $exportTitle[] = $tvt['name'];
            $userBalanceArrTmp[$paytype_key] = '0';
            $allTotalArr[$paytype_key] = 0;
            $whereMoneyLogTmp = $whereMoneyLog;
            if ($pay_type_arr[0] == 1) {
                $userBalanceArr[$paytype_key] = 0;
                $pay_type_where_order[] = 1;
            } elseif ($pay_type_arr[0] == 2) {
                $offline_pay_type_order = $pay_type_arr[1];
                $pay_type_where_order[] = 2;
                //$pay_type_where_order[] = 22;
                $whereMoneyLogTmp[] = array('off_pay_type', '=', $pay_type_arr[1]);
                $userMoneyLogObj = $villageUserMoneyLogDb->getOne($whereMoneyLogTmp, 'sum(ROUND(money,2)) as t_money');
                if ($userMoneyLogObj && !$userMoneyLogObj->isEmpty()) {
                    $userBalanceArr[$paytype_key] = $userMoneyLogObj['t_money'] ? $userMoneyLogObj['t_money'] : 0;
                    $userBalanceArrTmp[$paytype_key] = $userBalanceArr[$paytype_key];
                    $userBalanceArr['all_pay_money'] += $userBalanceArr[$paytype_key];
                    $allTotalArr[$paytype_key] += $userBalanceArr[$paytype_key];
                }
            } elseif ($pay_type_arr[0] == 4) {
                $pay_type_where_order[] = 4;
                $online_pay_type[] = $pay_type_arr1[$pay_type_arr[1]];
                $where_summary_pay_type = 4;

                if ($pay_type_arr[1] == 1) {
                    $whereMoneyLogTmp[] = array('pay_type', '=', 'weixin');
                    $userMoneyLogObj = $villageUserMoneyLogDb->getOne($whereMoneyLogTmp, 'sum(ROUND(money,2)) as t_money');
                    if ($userMoneyLogObj && !$userMoneyLogObj->isEmpty()) {
                        $userBalanceArr[$paytype_key] = $userMoneyLogObj['t_money'] ? $userMoneyLogObj['t_money'] : 0;
                        $userBalanceArrTmp[$paytype_key] = $userBalanceArr[$paytype_key];
                        $userBalanceArr['all_pay_money'] += $userBalanceArr[$paytype_key];
                        $allTotalArr[$paytype_key] += $userBalanceArr[$paytype_key];
                    }
                } else if ($pay_type_arr[1] == 2) {
                    $whereMoneyLogTmp[] = array('pay_type', '=', 'alipay');
                    $userMoneyLogObj = $villageUserMoneyLogDb->getOne($whereMoneyLogTmp, 'sum(ROUND(money,2)) as t_money');
                    if ($userMoneyLogObj && !$userMoneyLogObj->isEmpty()) {
                        $userBalanceArr[$paytype_key] = $userMoneyLogObj['t_money'] ? $userMoneyLogObj['t_money'] : 0;
                        $userBalanceArrTmp[$paytype_key] = $userBalanceArr[$paytype_key];
                        $userBalanceArr['all_pay_money'] += $userBalanceArr[$paytype_key];
                        $allTotalArr[$paytype_key] += $userBalanceArr[$paytype_key];
                    }
                }
            } elseif ($pay_type_arr[0] == 5) {
                $online_pay_type[] = 'hqpay_wx';
                $online_pay_type[] = 'hqpay_al';
                $userBalanceArr[$paytype_key] = 0;
            } elseif ($pay_type_arr[0] == 6) {
                //建行支付
                $online_pay_type[] = 'constructionPay';
                $userBalanceArr[$paytype_key] = 0;
                $whereMoneyLogTmp[] = array('pay_type', '=', 'constructionPay');
                $userMoneyLogObj = $villageUserMoneyLogDb->getOne($whereMoneyLogTmp, 'sum(ROUND(money,2)) as t_money');
                if ($userMoneyLogObj && !$userMoneyLogObj->isEmpty()) {
                    $userBalanceArr[$paytype_key] = $userMoneyLogObj['t_money'] ? $userMoneyLogObj['t_money'] : 0;
                    $userBalanceArrTmp[$paytype_key] = $userBalanceArr[$paytype_key];
                    $userBalanceArr['all_pay_money'] += $userBalanceArr[$paytype_key];
                    $allTotalArr[$paytype_key] += $userBalanceArr[$paytype_key];
                }
                
            } else if ($pay_type_arr[0] == 22) {
                $pay_type_where_order[] = 22;
            } elseif ($pay_type_arr[0] == 9) {
                //混合支付
                $pay_type_where_order[] = 9;
                $where_summary_pay_type = 9;
            } else if ($pay_type_arr[0] == 3) {
                $pay_type_where_order[] = 3;
                $where_summary_pay_type = 3;
                $online_pay_type[] = '';
            }
            $whereArrTmp = $whereArr;
            if ($pay_type_where_order) {
                $pay_type_where_order = array_unique($pay_type_where_order);
                $whereArrTmp[] = ['o.pay_type', 'in', $pay_type_where_order];
            }
            if ($offline_pay_type_order) {
                $whereArrTmp[] = ['o.offline_pay_type', '=', $offline_pay_type_order];
            }
            if ($where_summary_pay_type) {
                $whereArrTmp[] = ['su.pay_type', '=', $where_summary_pay_type];
            }
            if ($online_pay_type) {
                $online_pay_type = array_unique($online_pay_type);
                $whereArrTmp[] = ['su.online_pay_type', 'in', $online_pay_type];
            }
            $whereArrTmp[] = ['su.is_paid', '=', 1];
            $whereArrTmp[] = ['su.village_id', '=', $village_id];
            $tmpSumField = 'sum(ROUND(o.modify_money,2)) as all_modify_money,sum(ROUND(o.pay_money,2)) as all_pay_money,sum(ROUND(o.refund_money,2)) as all_refund_money';
            $sumOrderMoney = $db_house_new_pay_order->get_one_join_summary($whereArrTmp, $tmpSumField);
            if ($sumOrderMoney && isset($sumOrderMoney['all_pay_money'])) {
                $all_pay_money_tmp = $sumOrderMoney['all_pay_money'] - $sumOrderMoney['all_refund_money']; //减去退款的
                $all_pay_money_tmp = $all_pay_money_tmp > 0 ? round($all_pay_money_tmp, 2) : 0;
                $allTotalArr[$paytype_key] += $all_pay_money_tmp;
            }

        }
        $allTotalArr['all_refund_money']=0;
        $allTotalArr['modify_discount_money']=0;
        $allTotalArr['discount_money']=0;
        $allTotalArr['all_total_money']=0;
        $allTotalArr['id']='allTotal';
        $allTotalArr['village_id']=$village_id;
        $whereMoneyRefundLog = array(['business_id', '=', $village_id], ['business_type', '=', 1], ['type', '=', 2],['order_id','=',0], ['add_time', '>=', $extraArr['date_time']], ['add_time', '<=', $extraArr['date_time_end']]);
        $userMoneyLogObj = $villageUserMoneyLogDb->getOne($whereMoneyRefundLog, 'sum(ROUND(money,2)) as t_money');
        if ($userMoneyLogObj && !$userMoneyLogObj->isEmpty()) {
            $moneyRefund = $userMoneyLogObj['t_money'] ? $userMoneyLogObj['t_money'] : 0;
            $userBalanceArr['all_refund_money'] = $moneyRefund > 0 ? round($moneyRefund, 2) : 0;
        }

        $userBalanceArr['all_pay_money'] = $userBalanceArr['all_pay_money'] > 0 ? round($userBalanceArr['all_pay_money'], 2) : 0;
        $userBalanceArrTmp['all_pay_money'] = $userBalanceArr['all_pay_money'];

        $allTotalArr['all_pay_money'] += $userBalanceArr['all_pay_money'];
        $tmpSumField = 'sum(ROUND(total_money,2)) as all_total_money, sum(ROUND(modify_money,2)) as all_modify_money,sum(ROUND(late_payment_money,2)) as all_late_payment_money,sum(ROUND(pay_money,2)) as all_pay_money,sum(ROUND(refund_money,2)) as all_refund_money';
        $sumOrderMoney = $db_house_new_pay_order->get_one($whereArr, $tmpSumField);
        if ($sumOrderMoney && isset($sumOrderMoney['all_modify_money'])) {
            $all_pay_money_tmp = $sumOrderMoney['all_pay_money'] - $sumOrderMoney['all_refund_money']; //减去退款的
            $all_pay_money_tmp = $all_pay_money_tmp > 0 ? round($all_pay_money_tmp, 2) : 0;
            $allTotalArr['all_pay_money'] += $all_pay_money_tmp;
            $modify_discount_money = $sumOrderMoney['all_total_money'] - $sumOrderMoney['all_modify_money'];
            $modify_discount_money = $modify_discount_money > 0 ? round($modify_discount_money, 2) : 0;
            $allTotalArr['modify_discount_money'] += $modify_discount_money;
            $discount_money = $sumOrderMoney['all_modify_money'] - $sumOrderMoney['all_pay_money'];
            $discount_money = $discount_money > 0 ? round($discount_money, 2) : 0;

            $allTotalArr['discount_money'] += $discount_money;
        }

        $tmpSumField = 'sum(ROUND(total_money,2)) as all_total_money, sum(ROUND(modify_money,2)) as all_modify_money,sum(ROUND(late_payment_money,2)) as all_late_payment_money,sum(ROUND(pay_money,2)) as all_pay_money,sum(ROUND(refund_money,2)) as all_refund_money';
        $sumOrderMoney = $db_house_new_pay_order->get_one($whereRefundArr, $tmpSumField);
        if ($sumOrderMoney && isset($sumOrderMoney['all_modify_money'])) {
            $allTotalArr['all_refund_money'] = $sumOrderMoney['all_refund_money'] > 0 ? round($sumOrderMoney['all_refund_money'], 2) : 0;  //当日退款
        }
        $allTotalArr['all_refund_money'] = $allTotalArr['all_refund_money'] + $userBalanceArr['all_refund_money'];
        $user_balance_refund = 0;
        $chargeProjectObj = $houseNewChargeProject->getLists($projectWhere, 'id,village_id,name', $orderStr, $page, $limit);
        if ($chargeProjectObj && !$chargeProjectObj->isEmpty()) {
            $chargeProject = $chargeProjectObj->toArray();
            $count = $houseNewChargeProject->getProjectCount($projectWhere);
            $returnArr['count'] = $count > 0 ? $count : 0;
            foreach ($chargeProject as $pkk => $pvv) {
                $chargeProject[$pkk]['all_total_money'] = 0;
                $chargeProject[$pkk]['all_pay_money'] = 0;
                $chargeProject[$pkk]['discount_money'] = 0;
                $chargeProject[$pkk]['modify_discount_money'] = 0;
                $chargeProject[$pkk]['all_refund_money'] = 0;
                $whereArrTmp = $whereArr;
                $whereRefundArrTmp = $whereRefundArr;
                $whereArrTmp[] = array('o.project_id', '=', $pvv['id']);

                $tmpSumField = 'sum(ROUND(total_money,2)) as all_total_money, sum(ROUND(modify_money,2)) as all_modify_money,sum(ROUND(late_payment_money,2)) as all_late_payment_money,sum(ROUND(pay_money,2)) as all_pay_money,sum(ROUND(refund_money,2)) as all_refund_money';
                $sumOrderMoney = $db_house_new_pay_order->get_one($whereArrTmp, $tmpSumField);
                if ($sumOrderMoney && isset($sumOrderMoney['all_modify_money'])) {
                    $chargeProject[$pkk]['all_total_money'] = $sumOrderMoney['all_total_money'];
                    $all_pay_money_tmp = $sumOrderMoney['all_pay_money'] - $sumOrderMoney['all_refund_money'];
                    $chargeProject[$pkk]['all_pay_money'] = $all_pay_money_tmp > 0 ? round($all_pay_money_tmp, 2) : 0;

                    $modify_discount_money = $sumOrderMoney['all_total_money'] - $sumOrderMoney['all_modify_money'];
                    $modify_discount_money = $modify_discount_money > 0 ? round($modify_discount_money, 2) : 0;
                    $chargeProject[$pkk]['modify_discount_money'] = $modify_discount_money;

                    $discount_money = $sumOrderMoney['all_modify_money'] - $sumOrderMoney['all_pay_money'];
                    $discount_money = $discount_money > 0 ? round($discount_money, 2) : 0;
                    $chargeProject[$pkk]['discount_money'] = $discount_money;
                }
                $whereRefundArrTmp[] = array('o.project_id', '=', $pvv['id']);
                $tmpSumField = 'sum(ROUND(total_money,2)) as all_total_money, sum(ROUND(modify_money,2)) as all_modify_money,sum(ROUND(late_payment_money,2)) as all_late_payment_money,sum(ROUND(pay_money,2)) as all_pay_money,sum(ROUND(refund_money,2)) as all_refund_money';
                $sumOrderMoney = $db_house_new_pay_order->get_one($whereRefundArrTmp, $tmpSumField);
                if ($sumOrderMoney && isset($sumOrderMoney['all_modify_money'])) {
                    $chargeProject[$pkk]['all_refund_money'] = $sumOrderMoney['all_refund_money'] > 0 ? round($sumOrderMoney['all_refund_money'], 2) : 0;  //当日退款
                }

                $tmpArr = array('name' => $pvv['name'], 'all_pay_money' => $chargeProject[$pkk]['all_pay_money']);
                foreach ($payTypeList as $tt) {
                    $is_user_balance = 0;
                    $pay_type_where_order = array();
                    $online_pay_type = array();
                    $offline_pay_type_order = 0;
                    $where_summary_pay_type = 0;
                    $paytype_key = 'paytype_' . $tt['id'];
                    $chargeProject[$pkk][$paytype_key] = 0;
                    $pay_type_arr = explode('-', $tt['id']);
                    $whereArrTmp = $whereArr;
                    $whereArrTmp[] = array('o.project_id', '=', $pvv['id']);
                    $whereRefundArrTmp = $whereRefundArr;
                    $whereRefundArrTmp[] = array('o.project_id', '=', $pvv['id']);
                    if ($pay_type_arr[0] == 1) {
                        $pay_type_where_order[] = 1;
                    } elseif ($pay_type_arr[0] == 2) {
                        $offline_pay_type_order = $pay_type_arr[1];
                        $pay_type_where_order[] = 2;
                        //$pay_type_where_order[] = 22;

                    } elseif ($pay_type_arr[0] == 4) {
                        $pay_type_where_order[] = 4;
                        $online_pay_type[] = $pay_type_arr1[$pay_type_arr[1]];
                        if ($pay_type_arr[1] == 0) {
                            $is_user_balance = 1;
                        }
                        $where_summary_pay_type = 4;
                        $is_online = 1;
                    } elseif ($pay_type_arr[0] == 5) {
                        $online_pay_type[] = 'hqpay_wx';
                        $online_pay_type[] = 'hqpay_al';
                    } elseif ($pay_type_arr[0] == 6) {
                        //建行支付
                        $online_pay_type[] = 'constructionPay';
                    } else if ($pay_type_arr[0] == 22) {
                        $pay_type_where_order[] = 22;
                    } elseif ($pay_type_arr[0] == 9) {
                        //混合支付
                        $pay_type_where_order[] = 9;
                        $where_summary_pay_type = 9;
                    } else if ($pay_type_arr[0] == 3) {
                        $pay_type_where_order[] = 3;
                        $where_summary_pay_type = 3;
                        $online_pay_type[] = '';
                    }
                    if($is_user_balance<1){
                        $tmpArr[$paytype_key] = '0';
                    }
                    if ($pay_type_where_order) {
                        $pay_type_where_order = array_unique($pay_type_where_order);
                        $whereArrTmp[] = ['o.pay_type', 'in', $pay_type_where_order];
                        $whereRefundArrTmp[] = ['o.pay_type', 'in', $pay_type_where_order];
                    }
                    if ($offline_pay_type_order) {
                        $whereArrTmp[] = ['o.offline_pay_type', '=', $offline_pay_type_order];
                        $whereRefundArrTmp[] = ['o.offline_pay_type', '=', $offline_pay_type_order];
                    }
                    if ($where_summary_pay_type) {
                        $whereArrTmp[] = ['su.pay_type', '=', $where_summary_pay_type];
                        $whereRefundArrTmp[] = ['su.pay_type', '=', $where_summary_pay_type];
                    }
                    if ($online_pay_type) {
                        $online_pay_type = array_unique($online_pay_type);
                        $whereArrTmp[] = ['su.online_pay_type', 'in', $online_pay_type];
                        $whereRefundArrTmp[] = ['su.online_pay_type', 'in', $online_pay_type];
                    }
                    $whereArrTmp[] = ['su.is_paid', '=', 1];
                    $whereArrTmp[] = ['su.village_id', '=', $village_id];

                    $whereRefundArrTmp[] = ['su.is_paid', '=', 1];
                    $whereRefundArrTmp[] = ['su.village_id', '=', $village_id];

                    $tmpSumField = 'sum(ROUND(o.modify_money,2)) as all_modify_money,sum(ROUND(o.pay_money,2)) as all_pay_money,sum(ROUND(o.refund_money,2)) as all_refund_money';
                    $sumOrderMoney = $db_house_new_pay_order->get_one_join_summary($whereArrTmp, $tmpSumField);
                    if ($sumOrderMoney && isset($sumOrderMoney['all_pay_money'])) {
                        $all_pay_money_tmp = $sumOrderMoney['all_pay_money'] - $sumOrderMoney['all_refund_money']; //减去退款的
                        $all_pay_money_tmp = $all_pay_money_tmp > 0 ? round($all_pay_money_tmp, 2) : 0;
                        $chargeProject[$pkk][$paytype_key] = $all_pay_money_tmp;
                        if ($is_user_balance == 1) {
                            $chargeProject[$pkk]['all_pay_money'] = $chargeProject[$pkk]['all_pay_money'] - $sumOrderMoney['all_pay_money'];
                            $chargeProject[$pkk]['all_pay_money'] = $chargeProject[$pkk]['all_pay_money'] > 0 ? round($chargeProject[$pkk]['all_pay_money'], 2) : 0;
                            $tmpArr['all_pay_money']=$chargeProject[$pkk]['all_pay_money'];
                            
                            $allTotalArr['all_pay_money'] = $allTotalArr['all_pay_money'] - $sumOrderMoney['all_pay_money'];
                        }else{
                            $tmpArr[$paytype_key] = $all_pay_money_tmp;
                        }
                    }
                    if ($is_user_balance == 1) {
                        $tmpSumField = 'sum(ROUND(o.modify_money,2)) as all_modify_money,sum(ROUND(o.pay_money,2)) as all_pay_money,sum(ROUND(o.refund_money,2)) as all_refund_money';
                        $sumOrderMoney = $db_house_new_pay_order->get_one_join_summary($whereRefundArrTmp, $tmpSumField);
                        if ($sumOrderMoney && isset($sumOrderMoney['all_refund_money'])) {
                            $user_balance_refund += $sumOrderMoney['all_refund_money']; //余额退款
                            $chargeProject[$pkk]['all_refund_money'] = $chargeProject[$pkk]['all_refund_money'] - $sumOrderMoney['all_refund_money'];
                            $chargeProject[$pkk]['all_refund_money'] = $chargeProject[$pkk]['all_refund_money'] > 0 ? round($chargeProject[$pkk]['all_refund_money'], 2) : 0;
                            $allTotalArr['all_refund_money'] = $allTotalArr['all_refund_money'] - $sumOrderMoney['all_refund_money'];
                        }
                    }
                }
                $tmpArr['all_refund_money'] = $chargeProject[$pkk]['all_refund_money'];
                $tmpArr['modify_discount_money'] = $chargeProject[$pkk]['modify_discount_money'];
                $tmpArr['discount_money'] = $chargeProject[$pkk]['discount_money'];

                $exportData[] = $tmpArr;
            }
            //$userBalanceArr['all_refund_money'] = $user_balance_refund > 0 ? round($user_balance_refund, 2) : 0;
            $userBalanceArrTmp['all_refund_money'] = $userBalanceArr['all_refund_money'];
            $returnArr['lists'] = $chargeProject;
        }
        $allTotalArr['all_pay_money'] = $allTotalArr['all_pay_money'] > 0 ? round($allTotalArr['all_pay_money'], 2) : 0;
        $allTotalArr['all_refund_money'] = $allTotalArr['all_refund_money'] > 0 ? round($allTotalArr['all_refund_money'], 2) : 0;
        $returnArr['count'] = $returnArr['count'] + 1;
        //$returnArr['allTotalArr']=$allTotalArr;
        if ($page == 1) {
            array_unshift($returnArr['lists'], $userBalanceArr);
        }
        array_push($returnArr['lists'], $allTotalArr);
        if ($is_export) {
            $userBalanceArrTmp['modify_discount_money'] = '0';
            $userBalanceArrTmp['discount_money'] = '0';
            array_unshift($exportData, $userBalanceArrTmp);
            unset($allTotalArr['id'],$allTotalArr['all_total_money'],$allTotalArr['village_id']);
            array_push($exportData, $allTotalArr);
            $returnArr['lists'] = $exportData;
            $exportTitle[] = '当日退款金额';
            $exportTitle[] = '收银台修改优惠';
            $exportTitle[] = '减免优惠';
            $returnArr['export_title'] = $exportTitle;
        }
        return $returnArr;
    }

    /*** 很特别的订单 房间数据 统计
     * @param $year_v
     * @param $village_id
     * @param $singleArr
     * @param $cfrom
     * @return array|array[]
     */
    public function getRoomFeeRateCustomizedList($year_v, $village_id = '0', $singleArr = array(), $cfrom = 'village', $property_id = 0, $createRecord = 0)
    {
        $returnArr = array('list' => array());
        $returnArr['list']['property'] = array('key' => 0, 'order_type_name' => '物业费', 'order_type' => 'property', 'have_paid_rooms' => 0, 'no_paid_rooms' => 0, 'all_rooms' => 0, 'have_paid_money' => 0, 'discount_money' => 0, 'order_money' => 0, 'v_room_rate' => 0, 'v_money_rate' => 0);
        $returnArr['list']['elevator'] = array('key' => 1, 'order_type_name' => '电梯费', 'order_type' => 'elevator', 'have_paid_rooms' => 0, 'no_paid_rooms' => 0, 'all_rooms' => 0, 'have_paid_money' => 0, 'discount_money' => 0, 'order_money' => 0, 'v_room_rate' => 0, 'v_money_rate' => 0);
        $returnArr['list']['water'] = array('key' => 2, 'order_type_name' => '水费', 'order_type' => 'water', 'have_paid_rooms' => 0, 'no_paid_rooms' => 0, 'all_rooms' => 0, 'have_paid_money' => 0, 'discount_money' => 0, 'order_money' => 0, 'v_room_rate' => 0, 'v_money_rate' => 0);
        $returnArr['list']['heating'] = array('key' => 3, 'order_type_name' => '暖气费', 'order_type' => 'heating', 'have_paid_rooms' => 0, 'no_paid_rooms' => 0, 'all_rooms' => 0, 'have_paid_money' => 0, 'discount_money' => 0, 'order_money' => 0, 'v_room_rate' => 0, 'v_money_rate' => 0);
        $order_type_arr = array('property', 'water', 'elevator', 'heating');
        //$returnArr['list']['nopay_property'] = array('key' => 4, 'order_type_name' => '陈欠物业费', 'order_type' => 'nopay_property', 'have_paid_rooms' => 0, 'no_paid_rooms' => 0, 'all_rooms' => 0, 'have_paid_money' => '-', 'order_money' => '-', 'v_room_rate' => 0, 'v_money_rate' => 0);
        $year_v_start = $year_v . '-01-01 00:00:00';
        $year_start_time = strtotime($year_v_start);  //当年开始时间
        $year_v_end = $year_v . '-12-31 23:59:59';
        $year_end_time = strtotime($year_v_end);    //当年结束时间
        //总的入住房间数
        $houseVillageUserVacancy = new HouseVillageUserVacancy();
        $water_order_price_special = 0;
        $whereRoomArr = array(['status', '<', 4], ['is_del', '<', 1]);
        if ($cfrom == 'village') {
            $whereRoomArr[] = array('village_id', '=', $village_id);
        } else if ($cfrom == 'property') {
            $whereRoomArr[] = array('village_id', 'in', $village_id);
        }
        if (!empty($singleArr)) {
            $whereRoomArr[] = array('single_id', 'in', $singleArr);
        }

        $whereRoomArr[] = array('house_in_time', '>', 100);
        $whereRoomArr[] = array('house_in_time', '<', $year_start_time);
        $all_rooms = $houseVillageUserVacancy->getCount($whereRoomArr);
        if ($all_rooms > 0) {
            $returnArr['list']['property']['all_rooms'] = $all_rooms;
            $returnArr['list']['water']['all_rooms'] = $all_rooms;
            $returnArr['list']['elevator']['all_rooms'] = $all_rooms;
            $returnArr['list']['heating']['all_rooms'] = $all_rooms;
            // $returnArr['list']['nopay_property']['all_rooms'] = $all_rooms;
            //物业费
            $db_house_new_pay_order = new HouseNewPayOrder();
            $houseVillageInfoDb = new HouseVillageInfo();
            $whereBaseArr = array();
            if ($cfrom == 'village') {
                $whereBaseArr[] = array('o.village_id', '=', $village_id);
                $whereBaseArr[] = array('rm.village_id', '=', $village_id);
                $houseVillageInfoObj = $houseVillageInfoDb->getOne(array('village_id' => $village_id), '*');
                if ($houseVillageInfoObj && !$houseVillageInfoObj->isEmpty() && isset($houseVillageInfoObj['special_set_price'])) {
                    $water_order_price_special = $houseVillageInfoObj['special_set_price'];
                }
            } else if ($cfrom == 'property') {
                $water_order_price_special = 50;
                $whereBaseArr[] = array('o.village_id', 'in', $village_id);
                $whereBaseArr[] = array('rm.village_id', 'in', $village_id);
            }
            $whereBaseArr[] = array('o.is_discard', '<>', 2);
            if (!empty($singleArr)) {
                $whereBaseArr[] = array('rm.single_id', 'in', $singleArr);
            }
            foreach ($order_type_arr as $item_type) {
                //查金额
                $whereArr = $whereBaseArr;
                $whereArr[] = array('o.order_type', '=', $item_type);
                $whereRaw = 'rm.house_in_time>100 AND rm.house_in_time<' . $year_start_time . ' AND (o.is_refund<2 OR o.refund_money<o.pay_money) AND ((o.service_start_time<' . $year_start_time . ' AND o.service_end_time>' . $year_start_time . ') OR (o.service_start_time>=' . $year_start_time . ' AND o.service_start_time<' . $year_end_time . ') )';
                $all_order_obj = $db_house_new_pay_order->getOneByRoom($whereArr, $whereRaw, 'sum(o.total_money) as xtotal_money', '');
                if ($all_order_obj && !$all_order_obj->isEmpty() && $all_order_obj['xtotal_money']) {
                    $xtotal_money = $all_order_obj['xtotal_money'];  //总的账单
                    //查跨年的订单 计算出不属于当年部分的金额 最后减去
                    $whereRaw = 'rm.house_in_time>100 AND rm.house_in_time<' . $year_start_time . ' AND (o.is_refund<2 OR o.refund_money<o.pay_money) AND ((o.service_start_time<' . $year_start_time . ' AND o.service_end_time>' . $year_start_time . ') OR (o.service_start_time<' . $year_end_time . ' AND o.service_end_time>' . $year_end_time . ') )';
                    $pay_orders_obj = $db_house_new_pay_order->getListByRoom($whereArr, $whereRaw, 'o.order_id,o.total_money,o.service_start_time,o.service_end_time');
                    if ($pay_orders_obj && !$pay_orders_obj->isEmpty()) {
                        $pay_orders = $pay_orders_obj->toArray();
                        $tmpMoney = 0;
                        foreach ($pay_orders as $ovv) {
                            $tmpTotalTime = $ovv['service_end_time'] - $ovv['service_start_time'];
                            if ($ovv['service_start_time'] < $year_start_time) {
                                $segmentTime = $year_start_time - $ovv['service_start_time'];
                                $tmpMoney += ($segmentTime / $tmpTotalTime) * $ovv['total_money'];
                            }
                            if ($ovv['service_end_time'] > $year_end_time) {
                                $segmentTime = $ovv['service_end_time'] - $year_end_time;
                                $tmpMoney += ($segmentTime / $tmpTotalTime) * $ovv['total_money'];
                            }
                        }
                        $xtotal_money = $xtotal_money - $tmpMoney;
                    }
                    $xtotal_money = round($xtotal_money, 2);
                    $returnArr['list'][$item_type]['order_money'] = $xtotal_money;
                    //已缴的处理
                    $whereArr[] = array('o.is_paid', '=', 1);
                    $whereRaw = 'rm.house_in_time>100 AND rm.house_in_time<' . $year_start_time . ' AND (o.is_refund<2 OR o.refund_money<o.pay_money) AND ((o.service_start_time<' . $year_start_time . ' AND o.service_end_time>' . $year_start_time . ') OR (o.service_start_time>=' . $year_start_time . ' AND o.service_start_time<' . $year_end_time . ') )';
                    $pay_order_obj = $db_house_new_pay_order->getOneByRoom($whereArr, $whereRaw, 'sum(o.total_money) as xtotal_money,sum(o.pay_money) as xpay_money,sum(o.refund_money) as xrefund_money', '');
                    if ($pay_order_obj && !$pay_order_obj->isEmpty() && $pay_order_obj['xtotal_money']) {
                        //$have_paid_money = $pay_order_obj['xtotal_money']; //已缴部分
                        $have_paid_money = $pay_order_obj['xpay_money']; //已缴部分
                        $discount_money = $pay_order_obj['xtotal_money'] - $pay_order_obj['xpay_money']; //优惠的
                        //查跨年的订单 计算出不属于当年部分的金额 最后减去
                        $whereRaw = 'rm.house_in_time>100 AND rm.house_in_time<' . $year_start_time . ' AND (o.is_refund<2 OR o.refund_money<o.pay_money) AND ((o.service_start_time<' . $year_start_time . ' AND o.service_end_time>' . $year_start_time . ') OR (o.service_start_time<' . $year_end_time . ' AND o.service_end_time>' . $year_end_time . ') )';
                        $pay_orders_obj = $db_house_new_pay_order->getListByRoom($whereArr, $whereRaw, 'o.order_id,o.total_money,o.modify_money,o.pay_money,o.refund_money,o.service_start_time,o.service_end_time');
                        if ($pay_orders_obj && !$pay_orders_obj->isEmpty()) {
                            $pay_orders = $pay_orders_obj->toArray();
                            $tmpPaidMoney = 0;
                            $tmpDiscountMoney = 0;
                            foreach ($pay_orders as $ovv) {
                                $tmpTotalTime = $ovv['service_end_time'] - $ovv['service_start_time'];
                                $total_money = $ovv['total_money'];
                                $pay_money = $ovv['pay_money'];
                                $tmpDiscount = $ovv['total_money'] - $ovv['pay_money'];
                                if ($ovv['service_start_time'] < $year_start_time) {
                                    $segmentTime = $year_start_time - $ovv['service_start_time'];
                                    $tmpPaidMoney += ($segmentTime / $tmpTotalTime) * $pay_money;
                                    $tmpDiscountMoney += ($segmentTime / $tmpTotalTime) * $tmpDiscount;
                                }
                                if ($ovv['service_end_time'] > $year_end_time) {
                                    $segmentTime = $ovv['service_end_time'] - $year_end_time;
                                    $tmpPaidMoney += ($segmentTime / $tmpTotalTime) * $pay_money;
                                    $tmpDiscountMoney += ($segmentTime / $tmpTotalTime) * $tmpDiscount;
                                }
                            }
                            $have_paid_money = $have_paid_money - $tmpPaidMoney;
                            $discount_money = $discount_money - $tmpDiscountMoney;
                        }

                        $have_paid_money = round($have_paid_money, 2);
                        $returnArr['list'][$item_type]['have_paid_money'] = $have_paid_money;

                        $discount_money = round($discount_money, 2);
                        $returnArr['list'][$item_type]['discount_money'] = $discount_money;
                    }
                    if ($returnArr['list'][$item_type]['order_money'] > 0) {
                        $v_rate = (($returnArr['list'][$item_type]['have_paid_money']+ $returnArr['list'][$item_type]['discount_money']) / $returnArr['list'][$item_type]['order_money']) * 100;
                        $v_rate=$v_rate>100 ? 100:$v_rate;
                        $v_rate = round($v_rate, 2) . '%';
                        $returnArr['list'][$item_type]['v_money_rate'] = $v_rate;
                    }
                }
                //查有物业费未支付的 订单房间数
                /*
                    $where2Arr = $whereBaseArr;
                    $where2Arr[] = array('o.order_type', '=', 'property');
                    $where2Arr[] = array('o.is_paid', '=', 2);
                    $whereRaw = 'rm.house_in_time>100 AND rm.house_in_time<' . $year_start_time . ' AND ((o.service_start_time<' . $year_start_time . ' AND o.service_end_time>' . $year_start_time . ') OR (o.service_start_time>=' . $year_start_time . ' AND o.service_start_time<' . $year_end_time . ') )';
                    $roomCount = $db_house_new_pay_order->getCountByGroupRoom($where2Arr, $whereRaw, 'o.room_id');
                    $roomCount = $roomCount > 0 ? $roomCount : 0;
                    $returnArr['list']['nopay_property']['no_paid_rooms'] = $roomCount;
                    $returnArr['list']['nopay_property']['have_paid_rooms'] = $all_rooms - $roomCount;
                    $v_rate = ($returnArr['list']['nopay_property']['no_paid_rooms'] / $all_rooms);
                    $v_rate = 1 - $v_rate;
                    $v_rate=$v_rate*100;
                    $v_rate = round($v_rate, 2) . '%';
                    $returnArr['list']['nopay_property']['v_rate'] = $v_rate;
                */
                //查未缴 房间
                $where3Arr = $whereBaseArr;
                $where3Arr[] = array('o.order_type', '=', $item_type);
                $where3Arr[] = array('o.is_paid', '=', 2);
                $having = '';
                if ($item_type == 'water') {
                    $having = 'xtotal_money>=' . $water_order_price_special;
                }
                $whereRaw = 'rm.house_in_time>100 AND rm.house_in_time<' . $year_start_time . ' AND ((o.service_start_time<' . $year_start_time . ' AND o.service_end_time>' . $year_start_time . ') OR (o.service_start_time>=' . $year_start_time . ' AND o.service_start_time<' . $year_end_time . ') )';
                $pay_orders_obj = $db_house_new_pay_order->getListByGroupRoom($where3Arr, $whereRaw, 'o.room_id,o.village_id,sum(o.total_money) as xtotal_money', 'o.room_id', $having);
                $no_paid_rooms = 0;
                if ($pay_orders_obj && !$pay_orders_obj->isEmpty()) {
                    $pay_orders = $pay_orders_obj->toArray();
                    $no_paid_rooms = count($pay_orders);
                    if ($item_type == 'water') {
                        //水费特殊处理
                        foreach ($pay_orders as $ovv) {

                            $where4Arr = array(['o.village_id', '=', $ovv['village_id']]);
                            $where4Arr[] = array('o.room_id', '=', $ovv['room_id']);
                            $where4Arr[] = array('o.order_type', '=', $item_type);
                            $where4Arr[] = array('o.is_paid', '=', 2);
                            $whereRaw = '(o.service_start_time<' . $year_start_time . ' AND o.service_end_time>' . $year_start_time . ') OR (o.service_start_time<' . $year_end_time . ' AND o.service_end_time>' . $year_end_time . ')';
                            //查每个房间 跨当年的订单  计算费用
                            $room_pay_order_obj = $db_house_new_pay_order->getOrderListByRoomId($where4Arr, $whereRaw, 'o.order_id,o.total_money,o.service_start_time,o.service_end_time');
                            if ($room_pay_order_obj && !$room_pay_order_obj->isEmpty()) {
                                $room_pay_orders = $room_pay_order_obj->toArray();
                                $tmpMoney = 0;
                                foreach ($room_pay_orders as $rov) {
                                    $tmpTotalTime = $rov['service_end_time'] - $rov['service_start_time'];
                                    if ($rov['service_start_time'] < $year_start_time) {
                                        $segmentTime = $year_start_time - $rov['service_start_time'];
                                        $tmpMoney += ($segmentTime / $tmpTotalTime) * $rov['total_money'];
                                    }
                                    if ($rov['service_end_time'] > $year_end_time) {
                                        $segmentTime = $rov['service_end_time'] - $year_end_time;
                                        $tmpMoney += ($segmentTime / $tmpTotalTime) * $rov['total_money'];
                                    }
                                }
                                $total_money_c = $ovv['xtotal_money'] - $tmpMoney;
                                if ($total_money_c < $water_order_price_special) {
                                    //当年房间水费 累计小于50的 不算未缴房间数
                                    $no_paid_rooms--;
                                }
                            }
                        }
                    }
                }

                $returnArr['list'][$item_type]['no_paid_rooms'] = $no_paid_rooms;
                $returnArr['list'][$item_type]['have_paid_rooms'] = $all_rooms - $no_paid_rooms;
                $v_rate = ($returnArr['list'][$item_type]['no_paid_rooms'] / $all_rooms);
                $v_rate = 1 - $v_rate;
                $v_rate = $v_rate * 100;
                $v_rate = round($v_rate, 2) . '%';
                $returnArr['list'][$item_type]['v_room_rate'] = $v_rate;
            }

        }
        $returnArr['list'] = array_values($returnArr['list']);
        if ($createRecord == 1) {
            $houseVillageSummaryRateRecord = new HouseVillageSummaryRateRecord();
            $saveArr = array('date_day' => $year_v);
            $data_info = array('tcolumns' => array(), 'tdata' => array());
            $data_info['tcolumns'] = array(
                array(
                    'title' => '费用名称',
                    'dataIndex' => 'order_type_name',
                    'key' => 'order_type_name',
                ),
                array(
                    'title' => '已缴房间数',
                    'dataIndex' => 'have_paid_rooms',
                    'key' => 'have_paid_rooms',
                ),
                array(
                    'title' => '未缴房间数',
                    'dataIndex' => 'no_paid_rooms',
                    'key' => 'no_paid_rooms',
                ),
                array(
                    'title' => '房间数',
                    'dataIndex' => 'all_rooms',
                    'key' => 'all_rooms',
                ),
                array(
                    'title' => '已缴金额',
                    'dataIndex' => 'have_paid_money',
                    'key' => 'have_paid_money',
                ),
                array(
                    'title' => '优惠金额',
                    'dataIndex' => 'discount_money',
                    'key' => 'discount_money',
                ),
                array(
                    'title' => '账单金额',
                    'dataIndex' => 'order_money',
                    'key' => 'order_money',

                ),
                array(
                    'title' => '房间收费率',
                    'dataIndex' => 'v_room_rate',
                    'key' => 'v_room_rate',
                ),
                array(
                    'title' => '金额收费率',
                    'dataIndex' => 'v_money_rate',
                    'key' => 'v_money_rate',
                ),
            );
            $data_info['tdata'] = $returnArr['list'];
            if ($cfrom == 'village') {
                $saveArr['village_id'] = $village_id;
                $saveArr['xtype'] = 0;

            } else if ($cfrom == 'property') {
                $saveArr['property_id'] = $property_id;
                $saveArr['xtype'] = 1;
            }
            $saveArr['data_info'] = json_encode($data_info, JSON_UNESCAPED_UNICODE);
            $saveArr['rate_type'] = 0;
            $saveArr['add_time'] = time();
            $houseVillageSummaryRateRecord->addOneData($saveArr);
            return true;
        } else {
            $have_paid_all_money = 0;
            $discount_all_money = 0;
            $order_all_money = 0;
            $v_money_rate = '--';
            foreach ($returnArr['list'] as $mcv) {
                $have_paid_all_money += $mcv['have_paid_money'];
                $discount_all_money += $mcv['discount_money'];
                $order_all_money += $mcv['order_money'];
            }
            $have_paid_all_money = round($have_paid_all_money, 2);
            $discount_all_money = round($discount_all_money, 2);
            $order_all_money = round($order_all_money, 2);
            if ($order_all_money > 0) {
                $v_money_rate = (($have_paid_all_money+$discount_all_money) / $order_all_money) * 100;
                $v_money_rate = round($v_money_rate, 2) . '%';
            }
            $returnArr['list'][] = array('key' => 99, 'order_type_name' => '合计', 'order_type' => 'all_total', 'have_paid_rooms' => '--', 'no_paid_rooms' => '--', 'all_rooms' => '--', 'have_paid_money' => $have_paid_all_money, 'discount_money' => $discount_all_money, 'order_money' => $order_all_money, 'v_room_rate' => '--', 'v_money_rate' => $v_money_rate);
            return $returnArr;
        }
    }

    /*** 很特别的订单 房间数据 统计
     * @param $year_v
     * @param $village_id
     * @param $singleArr
     * @param $cfrom
     * @return array|array[]
     */
    public function getVillageRoomUnPayRateList($year_v, $village_id = '0', $singleArr = array(), $cfrom = 'village', $is_split = 1, $property_id = 0, $createRecord = 0)
    {
        $returnArr = array('list' => array());
        /*
        $returnArr['list']['property'] = array('key' => 0, 'order_type_name' => '物业费', 'order_type' => 'property', 'have_paid_rooms' => 0, 'no_paid_rooms' => 0, 'all_rooms' => 0, 'have_paid_money' => 0, 'no_pay_money' => 0, 'discount_money' => 0, 'order_money' => 0, 'v_room_rate' => 0, 'v_money_rate' => 0);
        $returnArr['list']['elevator'] = array('key' => 1, 'order_type_name' => '电梯费', 'order_type' => 'elevator', 'have_paid_rooms' => 0, 'no_paid_rooms' => 0, 'all_rooms' => 0, 'have_paid_money' => 0, 'no_pay_money' => 0, 'discount_money' => 0, 'order_money' => 0, 'v_room_rate' => 0, 'v_money_rate' => 0);
        $returnArr['list']['water'] = array('key' => 2, 'order_type_name' => '水费', 'order_type' => 'water', 'have_paid_rooms' => 0, 'no_paid_rooms' => 0, 'all_rooms' => 0, 'have_paid_money' => 0, 'no_pay_money' => 0, 'discount_money' => 0, 'order_money' => 0, 'v_room_rate' => 0, 'v_money_rate' => 0);
        $returnArr['list']['heating'] = array('key' => 3, 'order_type_name' => '暖气费', 'order_type' => 'heating', 'have_paid_rooms' => 0, 'no_paid_rooms' => 0, 'all_rooms' => 0, 'have_paid_money' => 0, 'no_pay_money' => 0, 'discount_money' => 0, 'order_money' => 0, 'v_room_rate' => 0, 'v_money_rate' => 0);
        */

        $returnArr['list']['property'] = array('key' => 0, 'order_type_name' => '物业费', 'order_type' => 'property', 'qc_no_paid_rooms' => 0, 'qc_paid_rooms' => 0, 'all_rooms' => 0, 'qc_no_pay_money' => 0, 'qc_order_money' => 0, 'qc_paid_discount' => 0, 'order_money' => 0, 'v_room_rate' => 0, 'v_money_rate' => 0);
        $returnArr['list']['elevator'] = array('key' => 1, 'order_type_name' => '电梯费', 'order_type' => 'elevator', 'qc_no_paid_rooms' => 0, 'qc_paid_rooms' => 0, 'all_rooms' => 0, 'qc_no_pay_money' => 0, 'qc_order_money' => 0, 'qc_paid_discount' => 0, 'order_money' => 0, 'v_room_rate' => 0, 'v_money_rate' => 0);
        $returnArr['list']['water'] = array('key' => 2, 'order_type_name' => '水费', 'order_type' => 'water', 'qc_no_paid_rooms' => 0, 'qc_paid_rooms' => 0, 'all_rooms' => 0, 'qc_no_pay_money' => 0, 'qc_order_money' => 0, 'qc_paid_discount' => 0, 'order_money' => 0, 'v_room_rate' => 0, 'v_money_rate' => 0);
        $returnArr['list']['heating'] = array('key' => 3, 'order_type_name' => '暖气费', 'order_type' => 'heating', 'qc_no_paid_rooms' => 0, 'qc_paid_rooms' => 0, 'all_rooms' => 0, 'qc_no_pay_money' => 0, 'qc_order_money' => 0, 'qc_paid_discount' => 0, 'order_money' => 0, 'v_room_rate' => 0, 'v_money_rate' => 0);

        $order_type_arr = array('property', 'elevator', 'water', 'heating');
        $year_v_start = $year_v . '-01-01 00:00:00';
        $year_start_time = strtotime($year_v_start);  //当年开始时间
        $year_v_end = $year_v . '-12-31 23:59:59';
        $year_end_time = strtotime($year_v_end);    //当年结束时间
        //总的入住房间数
        $houseVillageUserVacancy = new HouseVillageUserVacancy();
        $whereRoomArr = array(['status', '<', 4], ['is_del', '<', 1]);
        if ($cfrom == 'village') {
            $whereRoomArr[] = array('village_id', '=', $village_id);
        } else if ($cfrom == 'property') {
            $whereRoomArr[] = array('village_id', 'in', $village_id);
        }
        if (!empty($singleArr)) {
            $whereRoomArr[] = array('single_id', 'in', $singleArr);
        }
        $water_order_price_special = 0;
        $all_rooms = $houseVillageUserVacancy->getCount($whereRoomArr);
        if ($all_rooms > 0) {
            $returnArr['list']['property']['all_rooms'] = $all_rooms;
            $returnArr['list']['elevator']['all_rooms'] = $all_rooms;
            $returnArr['list']['water']['all_rooms'] = $all_rooms;
            $returnArr['list']['heating']['all_rooms'] = $all_rooms;

            //物业费
            $db_house_new_pay_order = new HouseNewPayOrder();
            $houseVillageInfoDb = new HouseVillageInfo();
            $whereBaseArr = array();
            if ($cfrom == 'village') {
                $whereBaseArr[] = array('o.village_id', '=', $village_id);
                $whereBaseArr[] = array('rm.village_id', '=', $village_id);
                $houseVillageInfoObj = $houseVillageInfoDb->getOne(array('village_id' => $village_id), '*');
                if ($houseVillageInfoObj && !$houseVillageInfoObj->isEmpty() && isset($houseVillageInfoObj['special_set_price'])) {
                    $water_order_price_special = $houseVillageInfoObj['special_set_price'];
                }
            } else if ($cfrom == 'property') {
                $whereBaseArr[] = array('o.village_id', 'in', $village_id);
                $whereBaseArr[] = array('rm.village_id', 'in', $village_id);
                $water_order_price_special = 50;
            }
            $whereBaseArr[] = array('o.is_discard', '<>', 2);
            if (!empty($singleArr)) {
                $whereBaseArr[] = array('rm.single_id', 'in', $singleArr);
            }
            foreach ($order_type_arr as $item_type) {
                //查金额
                $whereArr = $whereBaseArr;
                $whereArr[] = array('o.order_type', '=', $item_type);
                $whereArr[] = array('o.is_paid', '=', 2); //未支付的
                $whereRaw = 'o.service_end_time<' . $year_start_time;
                $all_order_obj = $db_house_new_pay_order->getOneByRoom($whereArr, $whereRaw, 'sum(o.total_money) as xtotal_money', '');
                $xtotal_money = 0;
                if ($all_order_obj && !$all_order_obj->isEmpty() && $all_order_obj['xtotal_money']) {
                    $xtotal_money = $all_order_obj['xtotal_money'];
                }
                if ($is_split > 0) {
                    //查跨年的订单 计算出不属于当年部分的金额 最后加上
                    $whereRaw = 'o.service_end_time >' . $year_start_time . ' AND o.service_start_time< ' . $year_start_time;
                    $pay_orders_obj = $db_house_new_pay_order->getListByRoom($whereArr, $whereRaw, 'o.order_id,o.total_money,o.service_start_time,o.service_end_time');
                    if ($pay_orders_obj && !$pay_orders_obj->isEmpty()) {
                        $pay_orders = $pay_orders_obj->toArray();
                        $tmpMoney = 0;
                        foreach ($pay_orders as $ovv) {
                            $tmpTotalTime = $ovv['service_end_time'] - $ovv['service_start_time'];
                            if ($ovv['service_start_time'] < $year_start_time) {
                                $segmentTime = $year_start_time - $ovv['service_start_time'];
                                $tmpMoney += ($segmentTime / $tmpTotalTime) * $ovv['total_money'];
                            }
                        }
                        $xtotal_money = $xtotal_money + $tmpMoney;
                    }
                }
                $xtotal_money = round($xtotal_money, 2);
                $qc_no_pay_money = $xtotal_money;

                //本年追缴金额
                $whereArr = $whereBaseArr;
                $whereArr[] = array('o.order_type', '=', $item_type);
                $whereArr[] = array('o.is_paid', '=', 1); //已支付的
                $whereArr[] = array('o.pay_time', '>=', $year_start_time);

                $whereRaw = 'o.service_end_time<' . $year_start_time . ' AND (o.is_refund<2 OR o.refund_money<o.pay_money)';
                $all_order_obj = $db_house_new_pay_order->getOneByRoom($whereArr, $whereRaw, 'sum(o.total_money) as xtotal_money,sum(o.pay_money) as xpay_money', '');
                $xtotal_money = 0;
                $xpay_money = 0;
                if ($all_order_obj && !$all_order_obj->isEmpty() && $all_order_obj['xtotal_money']) {
                    $xtotal_money = $all_order_obj['xtotal_money'];
                    $xpay_money = $all_order_obj['xpay_money'];
                }

                if ($is_split > 0) {
                    //查跨年的订单 计算出不属于当年部分的金额 最后加上
                    $whereRaw = 'o.service_end_time >' . $year_start_time . ' AND o.service_start_time< ' . $year_start_time . ' AND (o.is_refund<2 OR o.refund_money<o.pay_money)';
                    $pay_orders_obj = $db_house_new_pay_order->getListByRoom($whereArr, $whereRaw, 'o.order_id,o.total_money,o.pay_money,o.service_start_time,o.service_end_time');
                    if ($pay_orders_obj && !$pay_orders_obj->isEmpty()) {
                        $pay_orders = $pay_orders_obj->toArray();
                        $tmpMoney = 0;
                        $tmpPayMoney = 0;
                        foreach ($pay_orders as $ovv) {
                            $tmpTotalTime = $ovv['service_end_time'] - $ovv['service_start_time'];
                            if ($ovv['service_start_time'] < $year_start_time) {
                                $segmentTime = $year_start_time - $ovv['service_start_time'];
                                $tmpMoney += ($segmentTime / $tmpTotalTime) * $ovv['total_money'];
                                $tmpPayMoney += ($segmentTime / $tmpTotalTime) * $ovv['pay_money'];
                            }
                        }
                        $xpay_money = $xpay_money + $tmpPayMoney;
                        $xtotal_money = $xtotal_money + $tmpMoney;
                    }
                }
                $xtotal_money = round($xtotal_money, 2);
                $returnArr['list'][$item_type]['qc_order_money'] = round($xpay_money, 2);  //本年追缴
                $qc_no_pay_money = $qc_no_pay_money + $xtotal_money;
                $qc_no_pay_money = round($qc_no_pay_money, 2);
                $returnArr['list'][$item_type]['qc_no_pay_money'] = $qc_no_pay_money; //期初陈欠金额
                $qc_paid_discount = $xtotal_money - $xpay_money;
                $returnArr['list'][$item_type]['qc_paid_discount'] = round($qc_paid_discount, 2);
                if ($qc_no_pay_money > 0) {
                    $v_money_rate = $xtotal_money / $qc_no_pay_money;
                    $v_money_rate = $v_money_rate > 100 ? 100 : $v_money_rate;
                    $v_money_rate = $v_money_rate * 100;
                    $v_money_rate = round($v_money_rate, 2) . '%';
                    $returnArr['list'][$item_type]['v_money_rate'] = $v_money_rate;
                }
                //有欠费 期初欠费房间数
                $whereArr = $whereBaseArr;
                $whereArr[] = array('o.order_type', '=', $item_type);
                $whereArr[] = array('o.is_paid', '=', 2); //未支付的
                $whereRaw = 'o.service_end_time<' . $year_start_time . ' OR (o.service_end_time >' . $year_start_time . ' AND o.service_start_time< ' . $year_start_time . ')';
                $qc_no_paid_rooms = $db_house_new_pay_order->getCountByGroupRoom($whereArr, $whereRaw, 'o.room_id');
                //追缴房间数
                $whereArr = $whereBaseArr;
                $whereArr[] = array('o.order_type', '=', $item_type);
                $whereArr[] = array('o.is_paid', '=', 1); //未支付的
                $whereArr[] = array('o.pay_time', '>=', $year_start_time);
                $whereRaw = 'o.service_end_time<' . $year_start_time . ' OR (o.service_end_time >' . $year_start_time . ' AND o.service_start_time< ' . $year_start_time . ')';
                $qc_paid_rooms = $db_house_new_pay_order->getCountByGroupRoom($whereArr, $whereRaw, 'o.room_id');
                //查是不是全部追缴清了
                $pay_orders_obj = $db_house_new_pay_order->getListByGroupRoom($whereArr, $whereRaw, 'o.order_id,o.total_money,o.room_id,o.modify_money,o.pay_money,o.refund_money,o.service_start_time,o.service_end_time', 'o.room_id');
                if ($pay_orders_obj && !$pay_orders_obj->isEmpty()) {
                    $pay_orders = $pay_orders_obj->toArray();
                    foreach ($pay_orders as $ovv) {
                        $whereArr = $whereBaseArr;
                        $whereArr[] = array('o.order_type', '=', $item_type);
                        $whereArr[] = array('o.room_id', '=', $ovv['room_id']);
                        $whereArr[] = array('o.is_paid', '=', 2); //未支付的
                        $whereRaw = 'o.service_end_time<' . $year_start_time;
                        if ($is_split > 0) {
                            $whereRaw = 'o.service_end_time<' . $year_start_time . ' OR (o.service_end_time >' . $year_start_time . ' AND o.service_start_time< ' . $year_start_time . ')';
                        }
                        if ($item_type == 'water' && $water_order_price_special > 0) {
                            //水费特殊  累计小于50的 算追缴房间数
                            $water_no_paid_room = $db_house_new_pay_order->getOneByRoom($whereArr, $whereRaw, 'sum(o.total_money) as xtotal_money');
                            if ($water_no_paid_room && !$water_no_paid_room->isEmpty() && $water_no_paid_room['xtotal_money'] > $water_order_price_special) {
                                if ($is_split > 0) {
                                    $where4Arr = $whereBaseArr;
                                    $where4Arr[] = array('o.order_type', '=', $item_type);
                                    $where4Arr[] = array('o.room_id', '=', $ovv['room_id']);
                                    $where4Arr[] = array('o.is_paid', '=', 2); //未支付的

                                    $where4Raw = 'o.service_end_time >' . $year_start_time . ' AND o.service_start_time< ' . $year_start_time;
                                    //查每个房间 跨当年的订单  计算费用
                                    $room_pay_order_obj = $db_house_new_pay_order->getOrderListByRoomId($where4Arr, $where4Raw, 'o.order_id,o.total_money,o.service_start_time,o.service_end_time');
                                    if ($room_pay_order_obj && !$room_pay_order_obj->isEmpty()) {
                                        $room_pay_orders = $room_pay_order_obj->toArray();
                                        $tmpMoney = 0;
                                        foreach ($room_pay_orders as $rov) {
                                            $tmpTotalTime = $rov['service_end_time'] - $rov['service_start_time'];
                                            if ($rov['service_start_time'] < $year_start_time) {
                                                $segmentTime = $year_start_time - $rov['service_start_time'];
                                                $tmpMoney += ($segmentTime / $tmpTotalTime) * $rov['total_money'];
                                            }
                                        }
                                        $total_money_c = $water_no_paid_room['xtotal_money'] - $tmpMoney;
                                        if ($total_money_c > $water_order_price_special) {
                                            //水费  累大于50的 不算追缴房间数
                                            $qc_paid_rooms--;
                                        }
                                    }
                                } else {
                                    $qc_paid_rooms--;
                                }
                            }
                        } else {
                            $qc_no_paid_room_tmp = $db_house_new_pay_order->getCountByGroupRoom($whereArr, $whereRaw, 'o.room_id');
                            if ($qc_no_paid_room_tmp > 0) {
                                //有符合未缴的
                                $qc_paid_rooms--;
                            }
                        }

                    }
                }
                $qc_no_paid_rooms = $qc_no_paid_rooms + $qc_paid_rooms;
                $qc_paid_rooms = $qc_paid_rooms > 0 ? $qc_paid_rooms : 0;
                $returnArr['list'][$item_type]['qc_no_paid_rooms'] = $qc_no_paid_rooms;
                $returnArr['list'][$item_type]['qc_paid_rooms'] = $qc_paid_rooms;
                if ($qc_no_paid_rooms > 0) {
                    $v_room_rate = $qc_paid_rooms / $qc_no_paid_rooms;
                    $v_room_rate = $v_room_rate * 100;
                    $v_room_rate = round($v_room_rate, 2) . '%';
                    $returnArr['list'][$item_type]['v_room_rate'] = $v_room_rate;
                }
            }
        }
        $returnArr['list'] = array_values($returnArr['list']);
        if ($createRecord == 1) {
            $houseVillageSummaryRateRecord = new HouseVillageSummaryRateRecord();
            $saveArr = array('date_day' => $year_v);
            $data_info = array('tcolumns' => array(), 'tdata' => array());
            $data_info['tcolumns'] = array(
                array(
                    'title' => '费用名称',
                    'dataIndex' => 'order_type_name',
                    'key' => 'order_type_name',
                ),
                array(
                    'title' => '期初欠费房间数',
                    'dataIndex' => 'qc_no_paid_rooms',
                    'key' => 'qc_no_paid_rooms',
                ),
                array(
                    'title' => '追缴房间数',
                    'dataIndex' => 'qc_paid_rooms',
                    'key' => 'qc_paid_rooms',
                ),
                array(
                    'title' => '追缴率（房间）',
                    'dataIndex' => 'v_room_rate',
                    'key' => 'v_room_rate',
                ),
                array(
                    'title' => '期初陈欠金额',
                    'dataIndex' => 'qc_no_pay_money',
                    'key' => 'qc_no_pay_money',

                ),
                array(
                    'title' => '本年追缴金额',
                    'dataIndex' => 'qc_order_money',
                    'key' => 'qc_order_money',
                ),
                array(
                    'title' => '追缴优惠金额',
                    'dataIndex' => 'qc_paid_discount',
                    'key' => 'qc_paid_discount',
                ),
                array(
                    'title' => '本年追缴率',
                    'dataIndex' => 'v_money_rate',
                    'key' => 'v_money_rate',
                ),
            );
            $data_info['tdata'] = $returnArr['list'];
            if ($cfrom == 'village') {
                $saveArr['village_id'] = $village_id;
                $saveArr['xtype'] = 0;

            } else if ($cfrom == 'property') {
                $saveArr['property_id'] = $property_id;
                $saveArr['xtype'] = 1;
            }
            $saveArr['data_info'] = json_encode($data_info, JSON_UNESCAPED_UNICODE);
            $saveArr['rate_type'] = 1;
            if ($is_split == 0) {
                $saveArr['rate_type'] = 2; //陈欠追缴率
            }
            $saveArr['add_time'] = time();
            $houseVillageSummaryRateRecord->addOneData($saveArr);
            return true;
        } else {
            $qc_no_pay_money = 0;
            $qc_order_money = 0;
            $qc_paid_discount = 0;
            $v_money_rate = '--';
            foreach ($returnArr['list'] as $mcv) {
                $qc_no_pay_money += $mcv['qc_no_pay_money'];
                $qc_order_money += $mcv['qc_order_money'];
                $qc_paid_discount += $mcv['qc_paid_discount'];
            }
            if ($qc_no_pay_money > 0) {
                $v_money_rate = ($qc_order_money + $qc_paid_discount) / $qc_no_pay_money;
                $v_money_rate = $v_money_rate * 100;
                $v_money_rate = round($v_money_rate, 2) . '%';
            }
            $qc_no_pay_money = round($qc_no_pay_money, 2);
            $qc_order_money = round($qc_order_money, 2);
            $qc_paid_discount = round($qc_paid_discount, 2);
            $returnArr['list'][] = array('key' => 99, 'order_type_name' => '合计', 'order_type' => 'all_total', 'qc_no_paid_rooms' => '--', 'qc_paid_rooms' => '--', 'all_rooms' => '--', 'qc_no_pay_money' => $qc_no_pay_money, 'qc_order_money' => $qc_order_money, 'qc_paid_discount' => $qc_paid_discount, 'v_room_rate' => '--', 'v_money_rate' => $v_money_rate);
            return $returnArr;
        }

    }

    /******
     **很特别的订单 房间数据 统计 历史数据
     *
     */
    public function getRoomRateSummaryRecordData($whereArr = array(), $field = '*', $orderby = 'id DESC', $page = 0, $page_size = 10)
    {
        $houseVillageSummaryRateRecord = new HouseVillageSummaryRateRecord();
        $summaryRateRecordObj = $houseVillageSummaryRateRecord->getListData($whereArr, $field, $orderby, $page, $page_size);
        $returnArr = array('list' => array(), 'count' => 0, 'page_size' => $page_size);
        $summaryRateRecord = array();
        if ($summaryRateRecordObj && !$summaryRateRecordObj->isEmpty()) {
            $summaryRateRecord = $summaryRateRecordObj->toArray();
            foreach ($summaryRateRecord as $kk => $vv) {
                $summaryRateRecord[$kk]['data_info'] = $vv['data_info'] ? json_decode($vv['data_info'], true) : array();
                $summaryRateRecord[$kk]['add_time_str'] = date('Y-m-d H:i:s', $vv['add_time']);
            }
            $returnArr['list'] = $summaryRateRecord;
        }
        return $returnArr;
    }

    public function autoCreateRoomRateSummaryRecord()
    {
        $today = date('Y-m-d');
        $yearStart = date('Y') . '-01-01';
        if ($today != $yearStart) {
            return true;
        }
        $houseVillageDb = new HouseVillage();
        $whereArr = array(['village_id', '>', 0], ['status', '=', 1]);
        $objVillage = $houseVillageDb->getVillageList($whereArr, 'village_id,property_id');
        $villageArr = array();
        if ($objVillage && !$objVillage->isEmpty()) {
            $villageArr = $objVillage->toArray();
        }
        $currentYear = date('Y');
        $beforeYear = $currentYear - 1;
        $propertyArr = array();
        if ($villageArr) {
            $houseVillageSummaryRateRecord = new HouseVillageSummaryRateRecord();
            foreach ($villageArr as $hv) {
                $property_id = $hv['property_id'] * 1;
                if ($property_id > 0) {
                    if (!isset($propertyArr[$property_id]) || !is_array($propertyArr[$property_id])) {
                        $propertyArr[$property_id] = array($hv['village_id']);
                    } else {
                        $propertyArr[$property_id][] = $hv['village_id'];
                    }
                }
                $whereArr = array(['village_id', '=', $hv['village_id']], ['date_day', '=', $beforeYear], ['xtype', '<', 1], ['rate_type', '<', 1]);
                $tmpRateRecordObj = $houseVillageSummaryRateRecord->getOneData($whereArr);
                if (empty($tmpRateRecordObj) || $tmpRateRecordObj->isEmpty()) {
                    $this->getRoomFeeRateCustomizedList($beforeYear, $hv['village_id'], [], 'village', 0, 1);
                }
                $whereArr = array(['village_id', '=', $hv['village_id']], ['date_day', '=', $currentYear], ['xtype', '<', 1], ['rate_type', '=', 1]);
                $tmpRateRecordObj = $houseVillageSummaryRateRecord->getOneData($whereArr);
                if (empty($tmpRateRecordObj) || $tmpRateRecordObj->isEmpty()) {
                    $this->getVillageRoomUnPayRateList($currentYear, $hv['village_id'], [], 'village', 1, 0, 1);
                    $this->getVillageRoomUnPayRateList($currentYear, $hv['village_id'], [], 'village', 0, 0, 1);
                }
            }
            if ($propertyArr) {
                foreach ($propertyArr as $property_id => $hpv) {
                    $village_ids = array_unique($hpv);
                    $whereArr = array(['property_id', '=', $property_id], ['date_day', '=', $beforeYear], ['xtype', '=', 1], ['rate_type', '<', 1]);
                    $tmpRateRecordObj = $houseVillageSummaryRateRecord->getOneData($whereArr);
                    if (empty($tmpRateRecordObj) || $tmpRateRecordObj->isEmpty()) {
                        $this->getRoomFeeRateCustomizedList($beforeYear, $village_ids, [], 'property', $property_id, 1);
                    }
                    $whereArr = array(['property_id', '=', $property_id], ['date_day', '=', $currentYear], ['xtype', '=', 1], ['rate_type', '=', 1]);
                    $tmpRateRecordObj = $houseVillageSummaryRateRecord->getOneData($whereArr);
                    if (empty($tmpRateRecordObj) || $tmpRateRecordObj->isEmpty()) {
                        $this->getVillageRoomUnPayRateList($currentYear, $village_ids, [], 'property', 1, $property_id, 1);
                        $this->getVillageRoomUnPayRateList($currentYear, $village_ids, [], 'property', 0, $property_id, 1);
                    }
                }
            }
        }
        return true;
    }

    public function HouseFeeOrderInfoList($whereBaseArr = [], $village_id = 0, $extraArr = array(), $page = 1, $limit = 10, $group = 'rule_id')
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $pageOrderInfo = array();
        $field = "order_id,order_type,order_name,room_id,rule_id,project_id";

        $listCount = $db_house_new_pay_order->getCount2ByGroup($whereBaseArr, $group);
        $count = $listCount > 0 ? $listCount : 0;
        $list = $db_house_new_pay_order->HouseFeeSummaryList($whereBaseArr, $field, $group, $page, $limit);

        if (!empty($list) && !$list->isEmpty()) {
            $list = $list->toArray();
        } else {
            $list = array();
        }
        $service_start_time = $extraArr['service_start_time'];
        $service_end_time = $extraArr['service_end_time'];
        $order_service_type = $extraArr['order_service_type']; //0 计费开始时间 1计费结束时间 2计费开始结束时间
        if (!empty($list)) {

            $serviceHouseNewPorperty = new HouseNewChargeService();
            $charge_type_arr = $serviceHouseNewPorperty->charge_type;
            $houseNewChargeProject = new HouseNewChargeProject();
            $houseNewChargeRule = new HouseNewChargeRule();
            $houseNewChargeStandardBind = new HouseNewChargeStandardBind();
            $projectArrInfo = array();
            $noHandleOptType = array('heating', 'new_nuanqifei');
            foreach ($list as &$item) {
                if (isset($charge_type_arr[$item['order_type']])) {
                    $item['order_name'] = $charge_type_arr[$item['order_type']];
                }
                $item['project_name'] = '';

                if (isset($projectArrInfo[$item['project_id']])) {
                    $item['project_name'] = $projectArrInfo[$item['project_id']];
                } else {
                    $projectWhere = array();
                    $projectWhere[] = ['id', '=', $item['project_id']];
                    $projectWhere[] = ['village_id', '=', $village_id];
                    $oneProject = $houseNewChargeProject->getOne($projectWhere, 'name');
                    if ($oneProject && !$oneProject->isEmpty()) {
                        $item['project_name'] = $oneProject['name'];
                    }
                    $projectArrInfo[$item['project_id']] = $item['project_name'];
                }
                $item['rule_name'] = '';
                $item['unit_price'] = 0;
                $item['housesize'] = 0;
                $item['cycl_num'] = 0;
                $item['is_show_house_price'] = 0;
                $item['room_house_price'] = 0;
                $projectWhere = array();
                $projectWhere[] = ['id', '=', $item['rule_id']];
                $projectWhere[] = ['village_id', '=', $village_id];
                $oneChargeRule = $houseNewChargeRule->getOne($projectWhere, 'bill_create_set,charge_name,unit_price,fees_type,unit_gage_type,rule_digit');
                /** bill_create_set 1：按日生成2：按月生成3：按年生成 **/
                if ($oneChargeRule && !$oneChargeRule->isEmpty()) {
                    $item['rule_name'] = $oneChargeRule['charge_name'];
                    if($oneChargeRule['fees_type']==2 && $oneChargeRule['unit_gage_type']==1){
                        $item['is_show_house_price']=1;
                        $item['unit_price'] = $oneChargeRule['unit_price']*1;
                        if($oneChargeRule['bill_create_set']==1){
                            $item['cycl_num'] =365;
                        }else if($oneChargeRule['bill_create_set']==2){
                            $item['cycl_num'] =12;
                        }else if($oneChargeRule['bill_create_set']==3){
                            $item['cycl_num'] =1;
                        }
                        $whereRArr=array(['b.rule_id','=',$item['rule_id']], ['b.village_id', '=', $village_id],['b.is_del','=',1],['v.is_del','=',0], ['v.village_id', '=', $village_id]);
                        $housesize=$houseNewChargeStandardBind->get_bind_vacancy_sum($whereRArr,'v.housesize');
                        $housesize=round($housesize,3);
                        $item['housesize']=$housesize;
                        $room_house_price=$housesize*$item['cycl_num']*$item['unit_price'];
                        if($oneChargeRule['rule_digit']>-1 && $oneChargeRule['rule_digit']<2){
                            $room_house_price=round($room_house_price,$oneChargeRule['rule_digit']);
                        }else{
                            $room_house_price=round($room_house_price,2);
                        }
                        $item['room_house_price']=$room_house_price;
                    }
                }

                $item['x_total_money'] = 0;   //总的钱
                $item['pay_total_money'] = 0;   //已缴总的钱
                $item['x_nopay_money'] = 0;   // 未支付的钱
                $item['x_modify_money'] = 0;
                $item['x_pay_money'] = 0;   //已支付的钱
                $item['x_discount_money'] = 0; //优惠的钱
                $item['x_refund_money'] = 0;

                //查未支付订单
                $whereArr = $whereBaseArr;
                $whereArr[] = array('is_paid', '=', 2);  //未支付
                $whereArr[] = array('rule_id', '=', $item['rule_id']);
                $all_total_money = 0;

                $pay_orders_obj = $db_house_new_pay_order->getOrder($whereArr, 'order_id,total_money,modify_money,pay_money,service_start_time,service_end_time');
                if ($pay_orders_obj && !$pay_orders_obj->isEmpty()) {
                    $pay_orders = $pay_orders_obj->toArray();
                    $x_total_money = 0;
                    $x_modify_money = 0;
                    $x_nopay_money = 0;
                    $x_discount_money = 0;
                    foreach ($pay_orders as $ovv) {
                        $ovv['total_money']=round($ovv['total_money'],2);
                        $ovv['modify_money']=round($ovv['modify_money'],2);
                        $tmpTotalTime = $ovv['service_end_time'] - $ovv['service_start_time'];
                        $segmentTime = 0;
                        $is_not_opt = 0;
                        if (in_array($item['order_type'], $noHandleOptType)) {
                            $is_not_opt = 1;
                        } else if ($service_start_time < $ovv['service_start_time'] && $service_end_time > $ovv['service_start_time'] && $service_end_time <= $ovv['service_end_time']) {
                            $segmentTime = $service_end_time - $ovv['service_start_time'];  //前一段
                        } elseif ($service_start_time <= $ovv['service_start_time'] && $service_end_time >= $ovv['service_end_time']) {
                            //筛选时间包含整个订单时间
                            $is_not_opt = 1;
                        } elseif ($service_start_time >= $ovv['service_start_time'] && $service_end_time <= $ovv['service_end_time']) {
                            $segmentTime = $service_end_time - $service_start_time; //订单包含整个筛选时间
                        } elseif ($service_start_time >= $ovv['service_start_time'] && $ovv['service_end_time'] >= $service_start_time && $service_end_time > $ovv['service_end_time']) {
                            $segmentTime = $ovv['service_end_time'] - $service_start_time;  //后一段
                        } else {
                            $is_not_opt = 1;
                        }
                        if ($is_not_opt == 1) {
                            $tmpMoney = $ovv['total_money'];
                            $tmpMoney = round($tmpMoney, 2);
                            $x_total_money += $tmpMoney;
                            $x_nopay_money += $tmpMoney;

                            $tmpMoney = $ovv['modify_money'];
                            $tmpMoney = round($tmpMoney, 2);
                            $x_modify_money += $tmpMoney;

                            $tmpMoney = $ovv['total_money'] - $ovv['modify_money'];
                            $tmpMoney = round($tmpMoney, 2);
                            $x_discount_money += $tmpMoney;
                        } else if ($tmpTotalTime > 0) {
                            $tmpMoney = ($segmentTime / $tmpTotalTime) * $ovv['total_money'];
                            $tmpMoney = round($tmpMoney, 2);
                            $x_total_money += $tmpMoney;
                            $x_nopay_money += $tmpMoney;

                            $tmpMoney = ($segmentTime / $tmpTotalTime) * $ovv['modify_money'];
                            $tmpMoney = round($tmpMoney, 2);
                            $x_modify_money += $tmpMoney;

                            $tmpMoney = ($segmentTime / $tmpTotalTime) * ($ovv['total_money'] - $ovv['modify_money']);
                            $tmpMoney = round($tmpMoney, 2);
                            $x_discount_money += $tmpMoney;
                        }
                    }
                    $item['x_total_money'] = round($x_total_money, 2);
                    $item['x_modify_money'] = round($x_modify_money, 2);
                    $item['x_nopay_money'] = round($x_nopay_money, 2);
                    //$item['x_discount_money']= round($x_discount_money, 2);
                }

                //查已支付订单
                $whereArr = $whereBaseArr;
                $whereArr[] = array('is_paid', '=', 1);  //未支付
                $whereArr[] = array('rule_id', '=', $item['rule_id']);
                $pay_orders_obj = $db_house_new_pay_order->getOrder($whereArr, 'order_id,total_money,modify_money,pay_money,refund_money,service_start_time,service_end_time');
                if ($pay_orders_obj && !$pay_orders_obj->isEmpty()) {
                    $pay_orders = $pay_orders_obj->toArray();
                    $x_total_money = 0;
                    $x_modify_money = 0;
                    $x_discount_money = 0;
                    $x_pay_money = 0;
                    $x_refund_money=0;
                    foreach ($pay_orders as $ovv) {
                        $ovv['total_money']=round($ovv['total_money'],2);
                        $ovv['modify_money']=round($ovv['modify_money'],2);
                        $ovv['pay_money']=round($ovv['pay_money'],2);
                        $ovv['refund_money']=$ovv['refund_money'] ? round($ovv['refund_money'],2):0;
                        $tmpTotalTime = $ovv['service_end_time'] - $ovv['service_start_time'];
                        $segmentTime = 0;
                        $is_not_opt = 0;
                        if (in_array($item['order_type'], $noHandleOptType)) {
                            $is_not_opt = 1;
                        } else if ($service_start_time < $ovv['service_start_time'] && $service_end_time > $ovv['service_start_time'] && $service_end_time <= $ovv['service_end_time']) {
                            $segmentTime = $service_end_time - $ovv['service_start_time'];  //前一段
                        } elseif ($service_start_time <= $ovv['service_start_time'] && $service_end_time >= $ovv['service_end_time']) {
                            //筛选时间包含整个订单时间
                            $is_not_opt = 1;
                        } elseif ($service_start_time >= $ovv['service_start_time'] && $service_end_time <= $ovv['service_end_time']) {
                            $segmentTime = $service_end_time - $service_start_time; //订单包含整个筛选时间
                        } elseif ($service_start_time >= $ovv['service_start_time'] && $ovv['service_end_time'] >= $service_start_time && $service_end_time > $ovv['service_end_time']) {
                            $segmentTime = $ovv['service_end_time'] - $service_start_time;  //后一段
                        } else {
                            $is_not_opt = 1;
                        }
                        if ($is_not_opt == 1) {
                            $tmpMoney = $ovv['total_money'];
                            $tmpMoney = round($tmpMoney, 2);
                            $x_total_money += $tmpMoney;

                            $tmpMoney = $ovv['pay_money'];
                            $tmpMoney = round($tmpMoney, 2);
                            $x_pay_money += $tmpMoney;

                            $tmpMoney = $ovv['modify_money'];
                            $tmpMoney = round($tmpMoney, 2);
                            $x_modify_money += $tmpMoney;

                            $tmpMoney = $ovv['total_money'] - $ovv['pay_money'];
                            $tmpMoney = round($tmpMoney, 2);
                            $x_discount_money += $tmpMoney;

                            $tmpMoney=$ovv['refund_money'];
                            $tmpMoney = round($tmpMoney, 2);
                            $x_refund_money += $tmpMoney;
                            
                        } else if ($tmpTotalTime > 0) {
                            $tmpMoney = ($segmentTime / $tmpTotalTime) * $ovv['total_money'];
                            $tmpMoney = round($tmpMoney, 2);
                            $x_total_money += $tmpMoney;

                            $tmpMoney = ($segmentTime / $tmpTotalTime) * $ovv['pay_money'];
                            $tmpMoney = round($tmpMoney, 2);
                            $x_pay_money += $tmpMoney;

                            $tmpMoney = ($segmentTime / $tmpTotalTime) * $ovv['modify_money'];
                            $tmpMoney = round($tmpMoney, 2);
                            $x_modify_money += $tmpMoney;

                            $tmpMoney = ($segmentTime / $tmpTotalTime) * ($ovv['total_money'] - $ovv['pay_money']);
                            $tmpMoney = round($tmpMoney, 2);
                            $x_discount_money += $tmpMoney;

                            $tmpMoney= ($segmentTime / $tmpTotalTime) * $ovv['refund_money'];
                            $tmpMoney = round($tmpMoney, 2);
                            $x_refund_money += $tmpMoney;
                        }
                    }
                    $item['pay_total_money'] = round($x_total_money, 2);  //已缴总的钱
                    $item['x_total_money'] += round($x_total_money, 2);  //总的钱
                    $item['x_modify_money'] += round($x_modify_money, 2); //总的修改的钱
                    $item['x_pay_money'] = round($x_pay_money, 2);
                    $item['x_discount_money'] = round($x_discount_money, 2);
                    $item['x_refund_money'] =  round($x_refund_money, 2);
                    
                    $item['x_total_money'] = round($item['x_total_money'], 2);  //总的钱
                    $item['x_modify_money'] = round($item['x_modify_money'], 2); //总的修改的钱
                }
                $item['x_rate'] = '0%';
                if ($item['x_total_money'] > 0) {
                    $x_rate = ($item['pay_total_money'] / $item['x_total_money']) * 100;
                    $x_rate = round($x_rate, 2);
                    $item['x_rate'] = $x_rate . '%';
                }
            }
            $data = [];
            $data['pageOrderInfo'] = $pageOrderInfo;
            $data['total_limit'] = $limit;
            $data['list'] = $list;
            $data['count'] = $count ? $count : 0;
            return $data;

        }
    }

    public function getOrderTotalInfoByServiceTime($whereBaseArr = [], $village_id = 0, $extraArr = array(), $group = 'rule_id')
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $pageOrderInfo = array('xTotalMoney' => 0, 'xModifyMoney' => 0, 'payTotalMoney' => 0, 'xNopayMoney' => 0, 'xPayMoney' => 0, 'xDiscountMoney' => 0,'xRefundMoney'=>0, 'xRate' => '');

        $service_start_time = $extraArr['service_start_time'];
        $service_end_time = $extraArr['service_end_time'];
        $order_service_type = $extraArr['order_service_type']; //0 计费开始时间 1计费结束时间 2计费开始结束时间
        //查未支付订单
        $whereArr = $whereBaseArr;
        $whereArr[] = array('is_paid', '=', 2);  //未支付
        $noHandleOptType = array('heating', 'new_nuanqifei');
        $pay_orders_obj = $db_house_new_pay_order->getOrder($whereArr, 'order_id,order_type,total_money,modify_money,pay_money,service_start_time,service_end_time');
        if ($pay_orders_obj && !$pay_orders_obj->isEmpty()) {
            $pay_orders = $pay_orders_obj->toArray();
            $x_total_money = 0;
            $x_modify_money = 0;
            $x_nopay_money = 0;
            $x_discount_money = 0;
            foreach ($pay_orders as $ovv) {
                $ovv['total_money']=round($ovv['total_money'],2);
                $ovv['modify_money']=round($ovv['modify_money'],2);
                $tmpTotalTime = $ovv['service_end_time'] - $ovv['service_start_time'];
                $segmentTime = 0;
                $is_not_opt = 0;
                if (in_array($ovv['order_type'], $noHandleOptType)) {
                    $is_not_opt = 1;
                } else if ($service_start_time < $ovv['service_start_time'] && $service_end_time > $ovv['service_start_time'] && $service_end_time <= $ovv['service_end_time']) {
                    $segmentTime = $service_end_time - $ovv['service_start_time'];  //前一段
                } elseif ($service_start_time <= $ovv['service_start_time'] && $service_end_time >= $ovv['service_end_time']) {
                    //筛选时间包含整个订单时间
                    $is_not_opt = 1;
                } elseif ($service_start_time >= $ovv['service_start_time'] && $service_end_time <= $ovv['service_end_time']) {
                    $segmentTime = $service_end_time - $service_start_time; //订单包含整个筛选时间
                } elseif ($service_start_time >= $ovv['service_start_time'] && $ovv['service_end_time'] >= $service_start_time && $service_end_time > $ovv['service_end_time']) {
                    $segmentTime = $ovv['service_end_time'] - $service_start_time;  //后一段
                } else {
                    $is_not_opt = 1;
                }
                if ($is_not_opt == 1) {
                    $tmpMoney = $ovv['total_money'];
                    $tmpMoney = round($tmpMoney, 2);
                    $x_total_money += $tmpMoney;
                    $x_nopay_money += $tmpMoney;

                    $tmpMoney = $ovv['modify_money'];
                    $tmpMoney = round($tmpMoney, 2);
                    $x_modify_money += $tmpMoney;

                    $tmpMoney = $ovv['total_money'] - $ovv['modify_money'];
                    $tmpMoney = round($tmpMoney, 2);
                    $x_discount_money += $tmpMoney;
                } else if ($tmpTotalTime > 0) {
                    $tmpMoney = ($segmentTime / $tmpTotalTime) * $ovv['total_money'];
                    $tmpMoney = round($tmpMoney, 2);
                    $x_total_money += $tmpMoney;
                    $x_nopay_money += $tmpMoney;

                    $tmpMoney = ($segmentTime / $tmpTotalTime) * $ovv['modify_money'];
                    $tmpMoney = round($tmpMoney, 2);
                    $x_modify_money += $tmpMoney;

                    $tmpMoney = ($segmentTime / $tmpTotalTime) * ($ovv['total_money'] - $ovv['modify_money']);
                    $tmpMoney = round($tmpMoney, 2);
                    $x_discount_money += $tmpMoney;
                }
            }
            $pageOrderInfo['xTotalMoney'] = round($x_total_money, 2);
            $pageOrderInfo['xModifyMoney'] = round($x_modify_money, 2);
            $pageOrderInfo['xNopayMoney'] = round($x_nopay_money, 2);
            //$pageOrderInfo['xDiscountMoney']= round($x_discount_money, 2);
        }

        //查已支付订单
        $whereArr = $whereBaseArr;
        $whereArr[] = array('is_paid', '=', 1);  //未支付
        $pay_orders_obj = $db_house_new_pay_order->getOrder($whereArr, 'order_id,order_type,total_money,modify_money,pay_money,refund_money,service_start_time,service_end_time');
        if ($pay_orders_obj && !$pay_orders_obj->isEmpty()) {
            $pay_orders = $pay_orders_obj->toArray();
            $x_total_money = 0;
            $x_modify_money = 0;
            $x_discount_money = 0;
            $x_pay_money = 0;
            $x_refund_money=0;
            foreach ($pay_orders as $ovv) {
                $ovv['total_money']=round($ovv['total_money'],2);
                $ovv['modify_money']=round($ovv['modify_money'],2);
                $ovv['pay_money']=round($ovv['pay_money'],2);
                $ovv['refund_money']=$ovv['refund_money'] ? round($ovv['refund_money'],2):0;
                $tmpTotalTime = $ovv['service_end_time'] - $ovv['service_start_time'];
                $segmentTime = 0;
                $is_not_opt = 0;
                if (in_array($ovv['order_type'], $noHandleOptType)) {
                    $is_not_opt = 1;
                } else if ($service_start_time < $ovv['service_start_time'] && $service_end_time > $ovv['service_start_time'] && $service_end_time <= $ovv['service_end_time']) {
                    $segmentTime = $service_end_time - $ovv['service_start_time'];  //前一段
                } elseif ($service_start_time <= $ovv['service_start_time'] && $service_end_time >= $ovv['service_end_time']) {
                    //筛选时间包含整个订单时间
                    $is_not_opt = 1;
                } elseif ($service_start_time >= $ovv['service_start_time'] && $service_end_time <= $ovv['service_end_time']) {
                    $segmentTime = $service_end_time - $service_start_time; //订单包含整个筛选时间
                } elseif ($service_start_time >= $ovv['service_start_time'] && $ovv['service_end_time'] >= $service_start_time && $service_end_time > $ovv['service_end_time']) {
                    $segmentTime = $ovv['service_end_time'] - $service_start_time;  //后一段
                } else {
                    $is_not_opt = 1;
                }
                if ($is_not_opt == 1) {
                    $tmpMoney = $ovv['total_money'];
                    $tmpMoney = round($tmpMoney, 2);
                    $x_total_money += $tmpMoney;

                    $tmpMoney = $ovv['pay_money'];
                    $tmpMoney = round($tmpMoney, 2);
                    $x_pay_money += $tmpMoney;

                    $tmpMoney = $ovv['modify_money'];
                    $tmpMoney = round($tmpMoney, 2);
                    $x_modify_money += $tmpMoney;

                    $tmpMoney = $ovv['total_money'] - $ovv['pay_money'];
                    $tmpMoney = round($tmpMoney, 2);
                    $x_discount_money += $tmpMoney;

                    $tmpMoney=$ovv['refund_money'];
                    $tmpMoney = round($tmpMoney, 2);
                    $x_refund_money += $tmpMoney;
                } else if ($tmpTotalTime > 0) {
                    $tmpMoney = ($segmentTime / $tmpTotalTime) * $ovv['total_money'];
                    $tmpMoney = round($tmpMoney, 2);
                    $x_total_money += $tmpMoney;

                    $tmpMoney = ($segmentTime / $tmpTotalTime) * $ovv['pay_money'];
                    $tmpMoney = round($tmpMoney, 2);
                    $x_pay_money += $tmpMoney;

                    $tmpMoney = ($segmentTime / $tmpTotalTime) * $ovv['modify_money'];
                    $tmpMoney = round($tmpMoney, 2);
                    $x_modify_money += $tmpMoney;

                    $tmpMoney = ($segmentTime / $tmpTotalTime) * ($ovv['total_money'] - $ovv['pay_money']);
                    $tmpMoney = round($tmpMoney, 2);
                    $x_discount_money += $tmpMoney;


                    $tmpMoney= ($segmentTime / $tmpTotalTime) * $ovv['refund_money'];
                    $tmpMoney = round($tmpMoney, 2);
                    $x_refund_money += $tmpMoney;
                }
            }
            $pageOrderInfo['payTotalMoney'] = round($x_total_money, 2);  //已缴总的钱
            $pageOrderInfo['xTotalMoney'] += round($x_total_money, 2);  //总的钱
            $pageOrderInfo['xModifyMoney'] += round($x_modify_money, 2); //总的修改的钱
            $pageOrderInfo['xPayMoney'] = round($x_pay_money, 2);
            $pageOrderInfo['xDiscountMoney'] = round($x_discount_money, 2);
            $pageOrderInfo['xRefundMoney'] = round($x_refund_money, 2);
        }
        $pageOrderInfo['xTotalMoney'] = round($pageOrderInfo['xTotalMoney'], 2);  //总的钱
        $pageOrderInfo['xModifyMoney'] = round($pageOrderInfo['xModifyMoney'], 2); //总的修改的钱
        $pageOrderInfo['xRate'] = '0%';
        if ($pageOrderInfo['xTotalMoney'] > 0) {
            $x_rate = ($pageOrderInfo['payTotalMoney'] / $pageOrderInfo['xTotalMoney']) * 100;
            $x_rate = round($x_rate, 2);
            $pageOrderInfo['xRate'] = $x_rate . '%';
        }
        $data = [];
        $data['pageOrderInfo'] = $pageOrderInfo;
        return $data;
    }

    public function addExportLog($village_id = 0, $param = array())
    {
        $exportLogDb = new ExportLog();
        $type = 'house_financial_statement';
        $title = '小区财务报表统计数据导出';
        if (isset($param['export_type']) && !empty($param['export_type'])) {
            $type = $param['export_type'];
        }
        if (isset($param['export_title']) && !empty($param['export_title'])) {
            $title = $param['export_title'];
        }
        $paramStr = json_encode($param, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $addArr = array('type' => $type, 'param' => $paramStr, 'status' => 0, 'dateline' => time(), 'file_name' => '');
        $addArr['title'] = $title;
        $export_id = $exportLogDb->addOneData($addArr);
        $retArr = array('export_id' => $export_id);
        $queueData = $param;
        $queueData['export_id'] = $export_id;
        $queueData['export_type'] = $type;
        // 少量账单流水直接生成，避免因队列未启动或旧 worker 未重启而一直等待。
        if ($type === 'house_paid_order_record') {
            $where = (new HousePaidOrderRecordService())->getExportWhere($village_id, $param);
            $records = (new \app\common\model\db\PaidOrderRecord())->getListData($where, 'id', 'id DESC', 1, 101);
            if ($records->count() <= 100) {
                try {
                    (new HouseListDatasExportOutService())->startExecExportOut($queueData);
                } catch (\Throwable $e) {
                    fdump_api(['export_id' => $export_id, 'error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()], 'errHouseListDatasExportOutJob', true);
                    throw new \think\Exception('账单流水导出失败，请检查服务器导出日志（任务编号：' . $export_id . '）');
                }
                return $retArr;
            }
        }
        $this->exportExcelOutJob($queueData);
        return $retArr;
    }

    /**
     * 导出文件
     * @author:zhubaodi
     * @date_time: 2021/5/21 14:44
     */
    public function saveExcel($title, $data, $fileName, $tips = '', $exporttype = '')
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getDefaultColumnDimension()->setWidth(24);
        $sheet->getDefaultRowDimension()->setRowHeight(22);//设置行高
        $sheet->getRowDimension('1')->setRowHeight(25);//设置行高
        $mergeCellCoordinate = 'A1:Q1';
        if ($exporttype == 'feeRate') {
            $mergeCellCoordinate = 'A1:H1';
        }
        $sheet->getStyle($mergeCellCoordinate)->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells($mergeCellCoordinate); //合并单元格
        $sheet->getColumnDimension('A')->setWidth(32);
        $sheet->setCellValue('A1', $tips);

        //设置单元格内容
        $titCol = 'A';
        foreach ($title as $key => $value) {
            //单元格内容写入
            $sheet->setCellValue($titCol . '2', $value);
            $sheet->getStyle('A2:Ak2')->getFont()->setBold(true);
            $titCol++;
        }
        //设置单元格内容
        $row = 3;
        foreach ($data['list'] as $k => $item) {
            $dataCol = 'A';
            foreach ($item as $value) {
                //单元格内容写入
                $sheet->setCellValue($dataCol . $row, $value);
                $dataCol++;
            }
            $row++;
        }

        if ($exporttype == 'feeRate') {
            $mergeCellCoordinate = 'A' . $row . ':C' . $row;
            $sheet->mergeCells($mergeCellCoordinate); //合并单元格
            $sheet->setCellValue('A' . $row, '合计=>');
            $sheet->setCellValue('E' . $row, $data['all_modify_money']);
            $sheet->setCellValue('F' . $row, $data['all_pay_money']);
            $no_pay = $data['all_modify_money'] - $data['all_pay_money'];
            $no_pay = round($no_pay, 2);
            $sheet->setCellValue('G' . $row, $no_pay);
            $rate = $data['all_pay_money'] / $data['all_modify_money'];
            $rate = round($rate, 2);
            $sheet->setCellValue('H' . $row, $rate);
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

        if ($exporttype == 'feeRate') {
            $sheet->getStyle('A1:H' . $total_rows)->applyFromArray($styleArrayBody);
        } else {
            $sheet->getStyle('A1:AA' . $total_rows)->applyFromArray($styleArrayBody);
        }
        //下载
        $filename = $fileName . '.xlsx';
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet);
        return $this->downloadExportFile($filename);
    }

    /**
     * 下载表格
     */
    public function downloadExportFile($param)
    {
        $returnArr = [];
        if (!file_exists(request()->server('DOCUMENT_ROOT') . '/v20/runtime/' . $param)) {
            $returnArr['error'] = 1;
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
}
