<?php

namespace app\community\model\service;

use think\facade\Db;

class HouseNewManualBillService
{
    const ORDER_TYPE_VACANCY_FEE = 'vacancy_fee';
    const LEGACY_VILLAGE_IDS = [3, 8, 10, 11, 12, 13, 14, 16];

    public function getCreateBillMeta($adminUser, $params)
    {
        $villageId = $this->getVillageId($adminUser);
        $roomId = intval($params['room_id'] ?? 0);
        $positionId = intval($params['position_id'] ?? 0);
        $billKind = trim($params['bill_kind'] ?? 'bill');
        if ($positionId > 0) {
            $billKind = 'parking_management';
        } elseif ($roomId > 0) {
            $billKind = 'bill';
        }
        $targetInfo = [];
        if ($billKind === 'parking_management' && $positionId > 0) {
            $targetInfo = $this->getPositionInfo($villageId, $positionId);
            $targetInfo['default_bill_start_date'] = '';
            $targetInfo['default_bill_end_date'] = '';
        } elseif ($roomId > 0) {
            $targetInfo = $this->getRoomInfo($villageId, $roomId);
            $targetInfo['delivery_date'] = !empty($targetInfo['delivery_time']) ? date('Y-m-d', intval($targetInfo['delivery_time'])) : '';
            $targetInfo['property_service_start_date'] = !empty($targetInfo['property_service_start_time']) ? date('Y-m-d', intval($targetInfo['property_service_start_time'])) : '';
            $targetInfo['property_service_end_date'] = !empty($targetInfo['property_service_end_time']) ? date('Y-m-d', intval($targetInfo['property_service_end_time'])) : '';
            $targetInfo['default_bill_start_date'] = '';
            $targetInfo['default_bill_end_date'] = '';
        }
        return [
            'room_info' => $billKind === 'parking_management' ? [] : $targetInfo,
            'target_info' => $targetInfo,
            'project_list' => $this->getProjectList($villageId, $billKind),
            'vacancy_fee_config' => $this->getVacancyFeeConfig($adminUser),
        ];
    }

    public function getMonthlyRuleList($adminUser, $params)
    {
        $villageId = $this->getVillageId($adminUser);
        $projectId = intval($params['charge_project_id'] ?? 0);
        $billKind = trim($params['bill_kind'] ?? 'bill');
        $roomId = intval($params['room_id'] ?? 0);
        $positionId = intval($params['position_id'] ?? 0);
        if ($positionId > 0) {
            $billKind = 'parking_management';
        } elseif ($roomId > 0) {
            $billKind = 'bill';
        }
        if ($projectId <= 0) {
            throw new \Exception('请选择收费项目');
        }
        $query = Db::name('house_new_charge_rule')->alias('r')
            ->leftJoin('house_new_charge_project p', 'p.id = r.charge_project_id')
            ->leftJoin('house_new_charge_number n', 'n.id = p.subject_id')
            ->where([
                ['r.charge_project_id', '=', $projectId],
                ['r.status', '=', 1],
                ['r.bill_create_set', '=', 2],
                ['p.village_id', '=', $villageId],
            ]);
        if ($billKind === 'parking_management') {
            $query->where('n.charge_type', '=', 'parking_management');
        } else {
            $query->where('n.charge_type', '<>', 'parking_management');
        }
        return $query->field('r.id,r.charge_name,r.charge_price,r.bill_create_set,r.charge_valid_time,r.fees_type as fees_type_status,r.fees_type,r.unit_gage_type')
            ->order('r.id DESC')->select()->toArray();
    }

    public function getManualBillRuleInfo($adminUser, $params)
    {
        $villageId = $this->getVillageId($adminUser);
        $roomId = intval($params['room_id'] ?? 0);
        $positionId = intval($params['position_id'] ?? 0);
        $targetInfo = $positionId > 0
            ? $this->getPositionInfo($villageId, $positionId)
            : $this->getRoomInfo($villageId, $roomId);
        $projectId = intval($params['project_id'] ?? 0);
        $ruleInfo = $this->getRuleInfo($villageId, $projectId, intval($params['rule_id'] ?? 0));
        $this->assertMonthlyRule($ruleInfo);
        $this->assertTargetRuleType($ruleInfo, $positionId > 0);
        if ($positionId > 0) {
            $this->assertPositionRuleBound($villageId, $positionId, $projectId, $ruleInfo);
        }
        $result = $this->formatManualRuleInfo($targetInfo, $ruleInfo);
        $result['bill_date_ranges'] = $this->getExistingBillDateRanges(
            $villageId,
            $positionId > 0 ? 'position_id' : 'room_id',
            $positionId > 0 ? $positionId : $roomId,
            $projectId
        );
        return $result;
    }

    public function calcCreateBillMoney($adminUser, $params)
    {
        $villageId = $this->getVillageId($adminUser);
        $roomId = intval($params['room_id'] ?? 0);
        $positionId = intval($params['position_id'] ?? 0);
        $targetInfo = $positionId > 0
            ? $this->getPositionInfo($villageId, $positionId)
            : $this->getRoomInfo($villageId, $roomId);
        $startDate = trim($params['service_start_time'] ?? '');
        $endDate = trim($params['service_end_time'] ?? '');
        $generateMode = trim($params['generate_mode'] ?? 'day');
        if ($generateMode !== 'day') {
            throw new \Exception('当前仅支持按天生成账单');
        }
        $ruleInfo = $this->getRuleInfo($villageId, intval($params['project_id'] ?? 0), intval($params['rule_id'] ?? 0));
        $this->assertMonthlyRule($ruleInfo);
        $this->assertTargetRuleType($ruleInfo, $positionId > 0);
        if ($positionId > 0) {
            $this->assertPositionRuleBound($villageId, $positionId, intval($params['project_id'] ?? 0), $ruleInfo);
        }
        $unitPrice = $this->money4($ruleInfo['charge_price'] ?? 0);
        $area = $this->money4($targetInfo['position_area'] ?? ($targetInfo['housesize'] ?? 0));
        $billingNumber = $this->getBillingNumber($targetInfo, $ruleInfo);
        $feesTypeStatus = $this->getFeesTypeStatus($ruleInfo);
        if ($positionId > 0 && $feesTypeStatus === 2 && $billingNumber <= 0) {
            throw new \Exception('车位面积为0，无法生成账单');
        }
        [$startDate, $endDate] = $positionId > 0
            ? $this->normalizePositionBillDates($startDate, $endDate)
            : $this->normalizeBillDates($targetInfo, $startDate, $endDate);
        $preciseMoney = $this->calcDailyMoney($unitPrice, $billingNumber, $startDate, $endDate);
        $totalMoney = round($preciseMoney, 1);
        return [
            'days' => $this->daysInclusive($startDate, $endDate),
            'service_start_time' => $startDate,
            'service_end_time' => $endDate,
            'area' => $area,
            'billing_number' => $billingNumber,
            'fees_type_status' => $feesTypeStatus,
            'unit_price' => $unitPrice,
            'total_money' => $totalMoney,
            'precise_money' => $preciseMoney,
            'owner_pay_money' => $totalMoney,
            'rule_info' => $this->formatManualRuleInfo($targetInfo, $ruleInfo),
        ];
    }

    public function createManualBill($adminUser, $params)
    {
        if (intval($params['position_id'] ?? 0) > 0 || ($params['bill_kind'] ?? '') === 'parking_management') {
            if (intval($params['room_id'] ?? 0) > 0) {
                throw new \Exception('车位账单请仅传入车位ID');
            }
            return $this->createManualPositionBill($adminUser, $params);
        }
        $villageId = $this->getVillageId($adminUser);
        $roomId = intval($params['room_id'] ?? 0);
        $projectId = intval($params['project_id'] ?? 0);
        $ruleId = intval($params['rule_id'] ?? 0);
        $roomInfo = $this->getRoomInfo($villageId, $roomId);
        $ruleInfo = $this->getRuleInfo($villageId, $projectId, $ruleId);
        $this->assertMonthlyRule($ruleInfo);
        $calc = $this->calcCreateBillMoney($adminUser, $params);
        $allowDepositDeduct = isset($params['allow_deposit_deduct']) ? intval($params['allow_deposit_deduct']) : 1;
        $mergeBill = isset($params['merge_bill']) ? intval($params['merge_bill']) : 1;
        $ownerPayMoney = $calc['total_money'];
        if ($ownerPayMoney < 0) {
            throw new \Exception('业主应付金额不能小于0');
        }
        $segments = $mergeBill === 1
            ? [['start_date' => $calc['service_start_time'], 'end_date' => $calc['service_end_time'], 'money' => $ownerPayMoney]]
            : $this->buildMonthlyBillSegments($calc['unit_price'], $calc['billing_number'], $calc['service_start_time'], $calc['service_end_time']);
        $orderIds = [];
        Db::startTrans();
        try {
            // Lock the target even when no bill exists, so concurrent creates cannot both pass overlap checks.
            $lockedRoom = Db::name('house_village_user_vacancy')->where([
                ['village_id', '=', $villageId],
                ['pigcms_id', '=', $roomId],
                ['is_del', '=', 0],
            ])->lock(true)->find();
            if (!$lockedRoom) {
                throw new \Exception('房间不存在');
            }
            $this->assertNoOverlappingBill(
                $villageId,
                $roomId,
                $projectId,
                strtotime($calc['service_start_time'] . ' 00:00:00'),
                strtotime($calc['service_end_time'] . ' 23:59:59')
            );
            foreach ($segments as $segment) {
                $segmentDays = $this->daysInclusive($segment['start_date'], $segment['end_date']);
                $orderData = $this->buildBaseOrderData($adminUser, $roomInfo, $ruleInfo, [
                    'project_id' => $projectId,
                    'rule_id' => $ruleId,
                    'total_money' => $segment['money'],
                    'service_start_time' => strtotime($segment['start_date'] . ' 00:00:00'),
                    'service_end_time' => strtotime($segment['end_date'] . ' 23:59:59'),
                    'unit_price' => $calc['unit_price'],
                    'number' => $calc['billing_number'],
                    'allow_deposit_deduct' => $allowDepositDeduct,
                    'source_type' => 'manual_new_bill',
                    'extra_data' => [
                        'merge_bill' => $mergeBill,
                        'bill_days' => $segmentDays,
                        'calc_total_money' => $segment['money'],
                        'remark' => trim($params['remark'] ?? ''),
                    ],
                ]);
                $orderIds[] = Db::name('house_new_pay_order')->insertGetId($orderData);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
        return [
            'order_id' => $orderIds[0] ?? 0,
            'order_ids' => $orderIds,
            'order_count' => count($orderIds),
            'msg' => $mergeBill === 1 ? '账单已生成' : '已按月生成' . count($orderIds) . '笔账单',
        ];
    }

    private function createManualPositionBill($adminUser, $params)
    {
        $villageId = $this->getVillageId($adminUser);
        $positionId = intval($params['position_id'] ?? 0);
        $projectId = intval($params['project_id'] ?? 0);
        $ruleId = intval($params['rule_id'] ?? 0);
        $positionInfo = $this->getPositionInfo($villageId, $positionId);
        $ruleInfo = $this->getRuleInfo($villageId, $projectId, $ruleId);
        $this->assertMonthlyRule($ruleInfo);
        if (($ruleInfo['charge_type'] ?? '') !== 'parking_management') {
            throw new \Exception('请选择车位管理费收费项目和标准');
        }
        $calc = $this->calcCreateBillMoney($adminUser, $params);
        $allowDepositDeduct = isset($params['allow_deposit_deduct']) ? intval($params['allow_deposit_deduct']) : 1;
        $mergeBill = isset($params['merge_bill']) ? intval($params['merge_bill']) : 1;
        if ($calc['total_money'] < 0) {
            throw new \Exception('业主应付金额不能小于0');
        }
        $segments = $mergeBill === 1
            ? [['start_date' => $calc['service_start_time'], 'end_date' => $calc['service_end_time'], 'money' => $calc['total_money']]]
            : $this->buildMonthlyBillSegments($calc['unit_price'], $calc['billing_number'], $calc['service_start_time'], $calc['service_end_time']);
        $orderIds = [];
        Db::startTrans();
        try {
            // Serialize manual creation for the same position, including when no bill exists yet.
            $lockedPosition = Db::name('house_village_parking_position')->where([
                ['village_id', '=', $villageId],
                ['position_id', '=', $positionId],
            ])->lock(true)->find();
            if (!$lockedPosition) {
                throw new \Exception('车位不存在');
            }
            $this->assertPositionRuleBound($villageId, $positionId, $projectId, $ruleInfo);
            $this->assertNoOverlappingPositionBill(
                $villageId,
                $positionId,
                $projectId,
                strtotime($calc['service_start_time'] . ' 00:00:00'),
                strtotime($calc['service_end_time'] . ' 23:59:59')
            );
            foreach ($segments as $segment) {
                $segmentDays = $this->daysInclusive($segment['start_date'], $segment['end_date']);
                $orderData = $this->buildPositionOrderData($adminUser, $positionInfo, $ruleInfo, [
                    'project_id' => $projectId,
                    'rule_id' => $ruleId,
                    'total_money' => $segment['money'],
                    'service_start_time' => strtotime($segment['start_date'] . ' 00:00:00'),
                    'service_end_time' => strtotime($segment['end_date'] . ' 23:59:59'),
                    'unit_price' => $calc['unit_price'],
                    'number' => $calc['billing_number'],
                    'allow_deposit_deduct' => $allowDepositDeduct,
                    'source_type' => 'manual_parking_management_bill',
                    'extra_data' => [
                        'merge_bill' => $mergeBill,
                        'bill_days' => $segmentDays,
                        'calc_total_money' => $segment['money'],
                        'remark' => trim($params['remark'] ?? ''),
                    ],
                ]);
                $orderIds[] = Db::name('house_new_pay_order')->insertGetId($orderData);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
        return [
            'order_id' => $orderIds[0] ?? 0,
            'order_ids' => $orderIds,
            'order_count' => count($orderIds),
            'msg' => $mergeBill === 1 ? '车位管理费账单已生成' : '已按月生成' . count($orderIds) . '笔车位管理费账单',
        ];
    }

    public function splitThirdPartyBill($adminUser, $params)
    {
        $villageId = $this->getVillageId($adminUser);
        $orderId = intval($params['order_id'] ?? 0);
        $companyName = trim($params['company_name'] ?? '');
        $startDate = trim($params['service_start_time'] ?? '');
        $endDate = trim($params['service_end_time'] ?? '');
        $splitMoney = round(floatval($params['split_money'] ?? 0), 1);
        if ($orderId <= 0 || $companyName === '' || !$startDate || !$endDate || $splitMoney <= 0) {
            throw new \Exception('请完整填写第三方拆分信息');
        }
        Db::startTrans();
        try {
            $order = Db::name('house_new_pay_order')->where([
                ['order_id', '=', $orderId],
                ['village_id', '=', $villageId],
                ['source_type', '=', 'manual_new_bill'],
                ['is_paid', '=', 2],
                ['is_discard', '=', 1],
            ])->lock(true)->find();
            if (!$order) {
                throw new \Exception('当前账单不存在、已支付或不允许拆分');
            }
            $orderStart = strtotime(date('Y-m-d 00:00:00', intval($order['service_start_time'])));
            $orderEnd = strtotime(date('Y-m-d 23:59:59', intval($order['service_end_time'])));
            $splitStart = strtotime($startDate . ' 00:00:00');
            $splitEnd = strtotime($endDate . ' 23:59:59');
            if (!$splitStart || !$splitEnd || $splitEnd < $splitStart || $splitStart < $orderStart || $splitEnd > $orderEnd) {
                throw new \Exception('承担时间必须在主账单计费时间范围内');
            }
            $parentMoney = round(floatval($order['modify_money'] ?? $order['total_money']), 1);
            if ($splitMoney > $parentMoney) {
                throw new \Exception('第三方承担费用不能大于主账单剩余金额');
            }
            $remainMoney = round($parentMoney - $splitMoney, 1);
            Db::name('house_new_pay_order')->where('order_id', $orderId)->update([
                'total_money' => $remainMoney,
                'modify_money' => $remainMoney,
                'update_time' => time(),
            ]);
            $child = $order;
            unset($child['order_id']);
            $child['order_no'] = '';
            $child['order_name'] = ($order['order_name'] ?? '账单') . '-第三方承担';
            $child['room_id'] = 0;
            $child['pigcms_id'] = 0;
            $child['position_id'] = 0;
            $child['name'] = $companyName;
            $child['phone'] = '';
            $child['total_money'] = $splitMoney;
            $child['modify_money'] = $splitMoney;
            $child['pay_money'] = 0;
            $child['service_start_time'] = $splitStart;
            $child['service_end_time'] = $splitEnd;
            $child['allow_deposit_deduct'] = 0;
            $child['source_type'] = 'third_party_split';
            $child['primitive_order_id'] = $orderId;
            $child['add_time'] = time();
            $child['update_time'] = time();
            $child['extra_data'] = json_encode([
                'parent_order_id' => $orderId,
                'company_name' => $companyName,
            ], JSON_UNESCAPED_UNICODE);
            $childOrderId = Db::name('house_new_pay_order')->insertGetId($child);
            Db::name('house_new_bill_third_party_split')->insert([
                'village_id' => $villageId,
                'property_id' => intval($order['property_id'] ?? 0),
                'order_id' => $orderId,
                'child_order_id' => $childOrderId,
                'company_id' => 0,
                'company_name' => $companyName,
                'service_start_time' => $splitStart,
                'service_end_time' => $splitEnd,
                'total_money' => $parentMoney,
                'third_party_money' => $splitMoney,
                'owner_pay_money' => $remainMoney,
                'status' => 0,
                'remark' => trim($params['remark'] ?? ''),
                'add_time' => time(),
                'update_time' => time(),
            ]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
        return ['order_id' => $orderId, 'child_order_id' => $childOrderId, 'msg' => '账单拆分成功'];
    }

    public function getVacancyFeeConfig($adminUser)
    {
        $villageId = $this->getVillageId($adminUser);
        $config = Db::name('house_new_vacancy_fee_config')->where([
            ['village_id', '=', $villageId],
            ['is_del', '=', 0],
        ])->find();
        if (!$config) {
            return [
                'is_open' => 0,
                'charge_mode' => 1,
                'fixed_day_money' => '0.0000',
                'area_unit_price' => '0.0000',
                'project_id' => 0,
                'rule_id' => 0,
                'allow_deposit_deduct' => 1,
            ];
        }
        return $config;
    }

    public function saveVacancyFeeConfig($adminUser, $params)
    {
        $villageId = $this->getVillageId($adminUser);
        $propertyId = $this->getPropertyId($villageId, $adminUser);
        $now = time();
        $save = [
            'property_id' => $propertyId,
            'village_id' => $villageId,
            'is_open' => intval($params['is_open'] ?? 0),
            'charge_mode' => intval($params['charge_mode'] ?? 1),
            'fixed_day_money' => $this->money4($params['fixed_day_money'] ?? 0),
            'area_unit_price' => $this->money4($params['area_unit_price'] ?? 0),
            'project_id' => intval($params['project_id'] ?? 0),
            'rule_id' => intval($params['rule_id'] ?? 0),
            'allow_deposit_deduct' => isset($params['allow_deposit_deduct']) ? intval($params['allow_deposit_deduct']) : 1,
            'update_time' => $now,
        ];
        if (!in_array($save['charge_mode'], [1, 2], true)) {
            throw new \Exception('空置费收费模式不正确');
        }
        if ($save['is_open'] && $save['project_id'] <= 0) {
            throw new \Exception('请选择空置费收费项目');
        }
        $exists = Db::name('house_new_vacancy_fee_config')->where([
            ['village_id', '=', $villageId],
            ['is_del', '=', 0],
        ])->find();
        if ($exists) {
            Db::name('house_new_vacancy_fee_config')->where('id', $exists['id'])->update($save);
            return ['id' => $exists['id']];
        }
        $save['add_time'] = $now;
        $save['is_del'] = 0;
        return ['id' => Db::name('house_new_vacancy_fee_config')->insertGetId($save)];
    }

    public function syncRoomVacancyFeeBill($adminUser, $params)
    {
        $villageId = $this->getVillageId($adminUser);
        if ($this->isLegacyVillage($villageId)) {
            return ['order_id' => 0, 'msg' => '老项目不生成空置费'];
        }
        $roomId = intval($params['room_id'] ?? 0);
        $roomInfo = $this->getRoomInfo($villageId, $roomId);
        $config = $this->getVacancyFeeConfig($adminUser);
        if (empty($config['is_open'])) {
            return ['order_id' => 0, 'msg' => '空置费未启用'];
        }
        $deliveryTime = intval($roomInfo['delivery_time'] ?? 0);
        if ($deliveryTime <= 0) {
            return ['order_id' => 0, 'msg' => '未填写交房时间'];
        }
        $today = strtotime(date('Y-m-d 00:00:00'));
        $deliveryDay = strtotime(date('Y-m-d 00:00:00', $deliveryTime));
        if ($deliveryDay >= $today) {
            return ['order_id' => 0, 'msg' => '交房时间未早于当前日期'];
        }
        $serviceStart = intval($roomInfo['property_service_start_time'] ?? 0);
        if ($serviceStart > 0) {
            $serviceStartDay = strtotime(date('Y-m-d 00:00:00', $serviceStart));
            if ($serviceStartDay <= $deliveryDay) {
                $this->discardUnpaidVacancyFee($villageId, $roomId);
                return ['order_id' => 0, 'msg' => '没有空置区间'];
            }
            $targetEnd = strtotime('-1 day', $serviceStartDay);
        } else {
            $targetEnd = $today;
        }
        $lastPaidEnd = Db::name('house_new_pay_order')->where([
            ['village_id', '=', $villageId],
            ['room_id', '=', $roomId],
            ['order_type', '=', self::ORDER_TYPE_VACANCY_FEE],
            ['source_type', '=', 'vacancy_fee'],
            ['is_discard', '=', 1],
            ['is_paid', '=', 1],
        ])->max('service_end_time');
        $start = $deliveryDay;
        if ($lastPaidEnd && intval($lastPaidEnd) > 0) {
            $start = strtotime('+1 day', strtotime(date('Y-m-d 00:00:00', intval($lastPaidEnd))));
        }
        if ($start > $targetEnd) {
            return ['order_id' => 0, 'msg' => '空置费已生成到最新日期'];
        }
        $projectId = intval($config['project_id'] ?? 0);
        $ruleId = intval($config['rule_id'] ?? 0);
        $ruleInfo = $projectId > 0 && $ruleId > 0 ? $this->getRuleInfo($villageId, $projectId, $ruleId) : [
            'charge_type' => self::ORDER_TYPE_VACANCY_FEE,
            'order_name' => '空置费',
            'charge_name' => '空置费',
            'charge_price' => $config['charge_mode'] == 1 ? $config['fixed_day_money'] : $config['area_unit_price'],
            'charge_project_id' => $projectId,
            'id' => $ruleId,
        ];
        $money = $this->calcVacancyMoney($roomInfo, $config, date('Y-m-d', $start), date('Y-m-d', $targetEnd));
        if ($money <= 0) {
            return ['order_id' => 0, 'msg' => '空置费金额为0'];
        }
        $unpaid = Db::name('house_new_pay_order')->where([
            ['village_id', '=', $villageId],
            ['room_id', '=', $roomId],
            ['order_type', '=', self::ORDER_TYPE_VACANCY_FEE],
            ['source_type', '=', 'vacancy_fee'],
            ['is_discard', '=', 1],
            ['is_paid', '=', 2],
        ])->order('order_id DESC')->find();
        $orderData = $this->buildBaseOrderData($adminUser, $roomInfo, $ruleInfo, [
            'project_id' => $projectId,
            'rule_id' => $ruleId,
            'total_money' => $money,
            'service_start_time' => $start,
            'service_end_time' => strtotime(date('Y-m-d 23:59:59', $targetEnd)),
            'unit_price' => $config['charge_mode'] == 1 ? $config['fixed_day_money'] : $config['area_unit_price'],
            'number' => $config['charge_mode'] == 1 ? $this->daysInclusive(date('Y-m-d', $start), date('Y-m-d', $targetEnd)) : $this->money4($roomInfo['housesize'] ?? 0),
            'allow_deposit_deduct' => intval($config['allow_deposit_deduct'] ?? 1),
            'source_type' => 'vacancy_fee',
            'extra_data' => [
                'vacancy_fee_config_id' => intval($config['id'] ?? 0),
                'charge_mode' => intval($config['charge_mode'] ?? 1),
                'vacancy_days' => $this->daysInclusive(date('Y-m-d', $start), date('Y-m-d', $targetEnd)),
            ],
        ]);
        if ($unpaid) {
            Db::startTrans();
            try {
                Db::name('house_new_pay_order')->where('order_id', $unpaid['order_id'])->update($orderData);
                $this->replaceVacancyFeeDays($villageId, $roomId, intval($unpaid['order_id']), $start, $targetEnd);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
            return ['order_id' => $unpaid['order_id'], 'msg' => '空置费账单已更新'];
        }
        Db::startTrans();
        try {
            $orderId = Db::name('house_new_pay_order')->insertGetId($orderData);
            $this->replaceVacancyFeeDays($villageId, $roomId, $orderId, $start, $targetEnd);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
        return ['order_id' => $orderId, 'msg' => '空置费账单已生成'];
    }

    public function thirdPartyCompanyList($adminUser, $params)
    {
        $villageId = $this->getVillageId($adminUser);
        $where = [
            ['village_id', '=', $villageId],
            ['is_del', '=', 0],
        ];
        if (isset($params['status']) && intval($params['status']) >= 0) {
            $where[] = ['status', '=', intval($params['status'])];
        }
        $list = Db::name('house_new_bill_third_party_company')->where($where)->order('id DESC')->select()->toArray();
        return ['list' => $list];
    }

    public function thirdPartyCompanySave($adminUser, $params)
    {
        $villageId = $this->getVillageId($adminUser);
        $propertyId = $this->getPropertyId($villageId, $adminUser);
        $id = intval($params['id'] ?? 0);
        $companyName = trim($params['company_name'] ?? '');
        if ($companyName === '') {
            throw new \Exception('公司名称不能为空');
        }
        $now = time();
        $save = [
            'property_id' => $propertyId,
            'village_id' => $villageId,
            'company_name' => $companyName,
            'status' => isset($params['status']) ? intval($params['status']) : 1,
            'update_time' => $now,
        ];
        if ($id > 0) {
            Db::name('house_new_bill_third_party_company')->where([
                ['id', '=', $id],
                ['village_id', '=', $villageId],
            ])->update($save);
            return ['id' => $id];
        }
        $save['is_del'] = 0;
        $save['add_time'] = $now;
        return ['id' => Db::name('house_new_bill_third_party_company')->insertGetId($save)];
    }

    public function depositAvailable($adminUser, $params)
    {
        $villageId = $this->getVillageId($adminUser);
        $roomId = intval($params['room_id'] ?? 0);
        $this->getRoomInfo($villageId, $roomId);
        $account = Db::name('house_new_bill_deposit_account')->where([
            ['village_id', '=', $villageId],
            ['room_id', '=', $roomId],
            ['status', '=', 1],
        ])->find();
        $total = $account ? $this->money4($account['total_money']) : 0;
        $frozen = Db::name('house_new_bill_deposit_freeze')->alias('f')
            ->leftJoin('house_new_bill_deposit_order d', 'd.order_id = f.order_id AND d.village_id = f.village_id AND d.room_id = f.room_id')
            ->where([
                ['f.village_id', '=', $villageId],
                ['f.room_id', '=', $roomId],
                ['f.status', '=', 0],
            ])->whereNull('d.id')->sum('f.freeze_money');
        $used = $account ? $this->money4($account['used_money']) : 0;
        $available = $this->money4($total - $used - $frozen);
        return [
            'room_id' => $roomId,
            'total_money' => $total,
            'used_money' => $used,
            'frozen_money' => $this->money4($frozen),
            'available_money' => max($available, 0),
        ];
    }

    public function depositFreeze($adminUser, $params)
    {
        $villageId = $this->getVillageId($adminUser);
        $propertyId = $this->getPropertyId($villageId, $adminUser);
        $roomId = intval($params['room_id'] ?? 0);
        $orderId = intval($params['order_id'] ?? 0);
        $money = $this->money4($params['freeze_money'] ?? 0);
        if ($money <= 0) {
            throw new \Exception('冻结金额必须大于0');
        }
        $available = $this->depositAvailable($adminUser, ['room_id' => $roomId]);
        if ($available['available_money'] < $money) {
            throw new \Exception('可用押金不足');
        }
        $now = time();
        $id = Db::name('house_new_bill_deposit_freeze')->insertGetId([
            'property_id' => $propertyId,
            'village_id' => $villageId,
            'room_id' => $roomId,
            'order_id' => $orderId,
            'freeze_money' => $money,
            'status' => 0,
            'remark' => trim($params['remark'] ?? ''),
            'add_time' => $now,
            'update_time' => $now,
        ]);
        return ['id' => $id];
    }

    public function depositConfirm($adminUser, $params)
    {
        return $this->changeDepositFreezeStatus($adminUser, $params, 1);
    }

    public function depositRelease($adminUser, $params)
    {
        return $this->changeDepositFreezeStatus($adminUser, $params, 2);
    }

    public function freezeDepositForPay($adminUser, $params)
    {
        $money = $this->money4($params['freeze_money'] ?? 0);
        if ($money <= 0) {
            return ['id' => 0, 'freeze_money' => 0];
        }
        $villageId = $this->getVillageId($adminUser);
        $roomId = intval($params['room_id'] ?? 0);
        $orderIds = $this->normalizePayOrderIds($params['order_list'] ?? []);
        if ($roomId <= 0 || !$orderIds) {
            throw new \Exception('押金抵扣缺少房间或账单信息');
        }
        $orders = Db::name('house_new_pay_order')->where([
            ['village_id', '=', $villageId],
            ['room_id', '=', $roomId],
            ['order_id', 'in', $orderIds],
            ['is_discard', '=', 1],
        ])->field('order_id,allow_deposit_deduct')->select()->toArray();
        if (count($orders) !== count($orderIds)) {
            throw new \Exception('押金抵扣账单信息不正确');
        }
        foreach ($orders as $order) {
            if (intval($order['allow_deposit_deduct'] ?? 1) !== 1) {
                throw new \Exception('所选账单不允许使用押金抵扣');
            }
        }
        return $this->depositFreeze($adminUser, [
            'room_id' => $roomId,
            'order_id' => $orderIds[0],
            'freeze_money' => $money,
            'remark' => '收费时押金抵扣冻结',
        ]);
    }

    private function changeDepositFreezeStatus($adminUser, $params, $status)
    {
        $villageId = $this->getVillageId($adminUser);
        $id = intval($params['id'] ?? 0);
        $freeze = Db::name('house_new_bill_deposit_freeze')->where([
            ['id', '=', $id],
            ['village_id', '=', $villageId],
            ['status', '=', 0],
        ])->find();
        if (!$freeze) {
            throw new \Exception('押金冻结记录不存在或已处理');
        }
        Db::startTrans();
        try {
            Db::name('house_new_bill_deposit_freeze')->where('id', $id)->update([
                'status' => $status,
                'update_time' => time(),
            ]);
            if ($status === 1) {
                $account = Db::name('house_new_bill_deposit_account')->where([
                    ['village_id', '=', $villageId],
                    ['room_id', '=', intval($freeze['room_id'])],
                ])->lock(true)->find();
                if (!$account) {
                    throw new \Exception('押金账户不存在');
                }
                $used = $this->money4($account['used_money'] + $freeze['freeze_money']);
                if ($used > $this->money4($account['total_money'])) {
                    throw new \Exception('押金账户余额不足');
                }
                Db::name('house_new_bill_deposit_account')->where('id', $account['id'])->update([
                    'used_money' => $used,
                    'update_time' => time(),
                ]);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
        return ['id' => $id, 'status' => $status];
    }

    private function buildBaseOrderData($adminUser, $roomInfo, $ruleInfo, $params)
    {
        $villageId = intval($roomInfo['village_id']);
        $propertyId = $this->getPropertyId($villageId, $adminUser);
        $money = $this->money4($params['total_money'] ?? 0);
        $now = time();
        $orderType = $ruleInfo['charge_type'] ?? self::ORDER_TYPE_VACANCY_FEE;
        $orderName = $ruleInfo['order_name'] ?? ($ruleInfo['charge_name'] ?? '新增账单');
        return [
            'village_id' => $villageId,
            'property_id' => $propertyId,
            'room_id' => intval($roomInfo['pigcms_id']),
            'pigcms_id' => intval($roomInfo['owner_pigcms_id'] ?? 0),
            'uid' => intval($roomInfo['uid'] ?? 0),
            'name' => $roomInfo['name'] ?? '',
            'phone' => $roomInfo['phone'] ?? '',
            'position_id' => 0,
            'project_id' => intval($params['project_id'] ?? 0),
            'rule_id' => intval($params['rule_id'] ?? 0),
            'order_type' => $orderType,
            'order_name' => $orderName,
            'order_no' => '',
            'total_money' => $money,
            'modify_money' => $money,
            'pay_money' => 0,
            'is_paid' => 2,
            'is_discard' => 1,
            'is_refund' => 1,
            'is_prepare' => 2,
            'service_start_time' => intval($params['service_start_time']),
            'service_end_time' => intval($params['service_end_time']),
            'unit_price' => $this->money4($params['unit_price'] ?? 0),
            'number' => $this->money4($params['number'] ?? 1),
            'role_id' => intval($adminUser['wid'] ?? 0),
            'from' => 1,
            'add_time' => $now,
            'update_time' => $now,
            'allow_deposit_deduct' => intval($params['allow_deposit_deduct'] ?? 1),
            'source_type' => trim($params['source_type'] ?? 'manual_new_bill'),
            'extra_data' => json_encode($params['extra_data'] ?? [], JSON_UNESCAPED_UNICODE),
        ];
    }

    private function buildPositionOrderData($adminUser, $positionInfo, $ruleInfo, $params)
    {
        $villageId = intval($positionInfo['village_id']);
        $propertyId = $this->getPropertyId($villageId, $adminUser);
        $money = $this->money4($params['total_money'] ?? 0);
        $now = time();
        return [
            'village_id' => $villageId,
            'property_id' => $propertyId,
            'room_id' => intval($positionInfo['room_id'] ?? 0),
            'pigcms_id' => intval($positionInfo['pigcms_id'] ?? 0),
            'uid' => intval($positionInfo['uid'] ?? 0),
            'name' => $positionInfo['name'] ?? '',
            'phone' => $positionInfo['phone'] ?? '',
            'position_id' => intval($positionInfo['position_id']),
            'project_id' => intval($params['project_id'] ?? 0),
            'rule_id' => intval($params['rule_id'] ?? 0),
            'order_type' => $ruleInfo['charge_type'] ?? 'parking_management',
            'order_name' => $ruleInfo['order_name'] ?? ($ruleInfo['charge_name'] ?? '车位管理费'),
            'order_no' => '',
            'total_money' => $money,
            'modify_money' => $money,
            'pay_money' => 0,
            'is_paid' => 2,
            'is_discard' => 1,
            'is_refund' => 1,
            'is_prepare' => 2,
            'service_start_time' => intval($params['service_start_time']),
            'service_end_time' => intval($params['service_end_time']),
            'unit_price' => $this->money4($params['unit_price'] ?? 0),
            'number' => $this->money4($params['number'] ?? 1),
            'role_id' => intval($adminUser['wid'] ?? 0),
            'from' => 1,
            'add_time' => $now,
            'update_time' => $now,
            'allow_deposit_deduct' => intval($params['allow_deposit_deduct'] ?? 1),
            'source_type' => trim($params['source_type'] ?? 'manual_parking_management_bill'),
            'extra_data' => json_encode($params['extra_data'] ?? [], JSON_UNESCAPED_UNICODE),
        ];
    }

    private function getRuleInfo($villageId, $projectId, $ruleId)
    {
        $info = Db::name('house_new_charge_rule')->alias('r')
            ->leftJoin('house_new_charge_project p', 'p.id = r.charge_project_id')
            ->leftJoin('house_new_charge_number n', 'n.id = p.subject_id')
            ->where([
                ['r.id', '=', $ruleId],
                ['p.id', '=', $projectId],
                ['p.village_id', '=', $villageId],
            ])
            ->field('r.*,p.name as order_name,p.id as charge_project_id,p.status as project_status,n.charge_type')
            ->find();
        if (!$info) {
            throw new \Exception('收费标准不存在');
        }
        if (empty($info['charge_type'])) {
            $info['charge_type'] = self::ORDER_TYPE_VACANCY_FEE;
        }
        $info['fees_type_status'] = $this->getFeesTypeStatus($info);
        return $info;
    }

    private function assertMonthlyRule($ruleInfo)
    {
        if (intval($ruleInfo['bill_create_set'] ?? 0) !== 2) {
            throw new \Exception('新增账单只允许使用按月生成的收费标准');
        }
    }

    private function assertTargetRuleType($ruleInfo, $isPosition)
    {
        $isParkingManagement = ($ruleInfo['charge_type'] ?? '') === 'parking_management';
        if ($isPosition && !$isParkingManagement) {
            throw new \Exception('车场只能选择车位管理费收费项目和标准');
        }
        if (!$isPosition && $isParkingManagement) {
            throw new \Exception('房产不能选择车位管理费收费项目和标准');
        }
    }

    private function assertPositionRuleBound($villageId, $positionId, $projectId, $ruleInfo)
    {
        if (intval($ruleInfo['status'] ?? 0) !== 1 || intval($ruleInfo['project_status'] ?? 0) === 4) {
            throw new \Exception('收费项目或收费标准已停用');
        }
        $boundTargetMap = $this->getBatchBoundTargetIdMap(
            $villageId,
            $projectId,
            'position',
            [$positionId]
        );
        if (empty($boundTargetMap[$positionId])) {
            throw new \Exception('当前车位未绑定所选收费项目，请先通过添加收费项绑定');
        }
    }

    private function formatManualRuleInfo($roomInfo, $ruleInfo)
    {
        $feesTypeStatus = $this->getFeesTypeStatus($ruleInfo);
        $feesTypeMap = [1 => '固定费用', 2 => '单价计量'];
        $unitMap = [1 => '房屋面积', 2 => '固定单位', 3 => '车位面积', 4 => '供暖面积', 5 => '计费面积'];
        $billTypeMap = [1 => '后付费', 2 => '预生成'];
        return [
            'charge_name' => $ruleInfo['charge_name'] ?? '',
            'not_house_rate' => $this->money1($ruleInfo['not_house_rate'] ?? 100) . '%',
            'charge_valid_time' => !empty($ruleInfo['charge_valid_time']) ? date('Y-m-d', intval($ruleInfo['charge_valid_time'])) : '长期有效',
            'fees_type_status' => $feesTypeStatus,
            'fees_type_text' => $feesTypeMap[$feesTypeStatus] ?? '固定费用',
            'unit_gage_text' => $unitMap[intval($ruleInfo['unit_gage_type'] ?? 1)] ?? '',
            'charge_price' => $this->money1($ruleInfo['charge_price'] ?? 0),
            'cyclicity_text' => intval($ruleInfo['cyclicity_set'] ?? 0) > 0 ? intval($ruleInfo['cyclicity_set']) . '个月' : '无限期',
            'bill_create_set_text' => '按月生成',
            'bill_type_text' => $billTypeMap[intval($ruleInfo['bill_type'] ?? 2)] ?? '预生成',
            'create_mode_text' => '手动生成',
            'is_prepaid_text' => intval($ruleInfo['is_prepaid'] ?? 2) === 1 ? '支持' : '不支持',
            'area' => $this->money1($roomInfo['position_area'] ?? ($roomInfo['housesize'] ?? 0)),
            'position_area' => $this->money1($roomInfo['position_area'] ?? 0),
            'billing_number' => $this->money1($this->getBillingNumber($roomInfo, $ruleInfo)),
            'monthly_unit_price' => $this->money1($ruleInfo['charge_price'] ?? 0),
        ];
    }

    public function createBatchManualBill($adminUser, $params)
    {
        $villageId = $this->getVillageId($adminUser);
        $rawTargetIds = $params['target_ids'] ?? [];
        if (!is_array($rawTargetIds)) {
            $rawTargetIds = explode(',', trim((string)$rawTargetIds, ','));
        }
        $targetIds = array_values(array_unique(array_filter(array_map('intval', $rawTargetIds))));
        if (!$targetIds) {
            throw new \Exception('请选择需要创建账单的房间或车位');
        }
        if (count($targetIds) > 200) {
            throw new \Exception('单次最多选择200个房间或车位');
        }
        $billKind = trim($params['bill_kind'] ?? 'bill');
        if (!in_array($billKind, ['bill', 'parking_management', 'deposit'], true)) {
            throw new \Exception('批量账单类型不正确');
        }
        [$startDate, $endDate] = $this->getBatchRequestedDates($params);
        $projectId = intval($params['project_id'] ?? 0);
        $ruleId = intval($params['rule_id'] ?? 0);
        $isDeposit = $billKind === 'deposit';
        $targetName = $billKind === 'parking_management' ? '车位' : '房间';
        $targetType = $billKind === 'parking_management' ? 'position' : 'room';
        $boundTargetMap = [];
        if (!$isDeposit) {
            $ruleInfo = $this->getRuleInfo($villageId, $projectId, $ruleId);
            $this->assertTargetRuleType($ruleInfo, $billKind === 'parking_management');
            $boundTargetMap = $this->getBatchBoundTargetIdMap($villageId, $projectId, $targetType, $targetIds);
        }
        $successTargetIds = [];
        $orderIds = [];
        $failedList = [];
        foreach ($targetIds as $targetId) {
            try {
                if (!$isDeposit && empty($boundTargetMap[$targetId])) {
                    throw new \Exception('未绑定当前收费项目');
                }
                $targetParams = $params;
                unset($targetParams['target_ids'], $targetParams['bill_kind']);
                $targetParams['service_start_time'] = $startDate;
                $targetParams['service_end_time'] = $endDate;
                unset($targetParams['period']);
                // The selected dates are authoritative; reuse the existing daily pricing and validation.
                $targetParams['generate_mode'] = 'day';
                if ($isDeposit) {
                    $targetParams['room_id'] = $targetId;
                    $result = (new HouseNewDepositService())->createDepositBill($adminUser, $targetParams);
                    $result['order_ids'] = [$result['order_id']];
                } elseif ($billKind === 'parking_management') {
                    $targetParams['position_id'] = $targetId;
                    $targetParams['room_id'] = 0;
                    $result = $this->createManualPositionBill($adminUser, $targetParams);
                } else {
                    $targetParams['room_id'] = $targetId;
                    $targetParams['position_id'] = 0;
                    $result = $this->createManualBill($adminUser, $targetParams);
                }
                $successTargetIds[] = $targetId;
                $orderIds = array_merge($orderIds, $result['order_ids'] ?? []);
            } catch (\Throwable $e) {
                $failedList[] = [
                    'target_id' => $targetId,
                    'msg' => $e->getMessage(),
                ];
            }
        }
        if (!$successTargetIds) {
            $firstError = $failedList[0]['msg'] ?? '批量账单创建失败';
            throw new \Exception('所选' . $targetName . '均创建失败：' . $firstError);
        }
        $successCount = count($successTargetIds);
        $failedCount = count($failedList);
        $msg = '已为' . $successCount . '个' . $targetName . '生成' . count($orderIds) . '笔账单';
        if ($failedCount > 0) {
            $msg .= '，' . $failedCount . '个' . $targetName . '创建失败，首个失败原因：' . $failedList[0]['msg'];
        }
        return [
            'success_target_ids' => $successTargetIds,
            'created_target_count' => $successCount,
            'order_ids' => $orderIds,
            'order_count' => count($orderIds),
            'failed_count' => $failedCount,
            'failed_list' => $failedList,
            'msg' => $msg,
        ];
    }

    public function getBatchManualBillTargetList($adminUser, $params)
    {
        $villageId = $this->getVillageId($adminUser);
        $targetType = trim($params['target_type'] ?? 'room');
        $page = max(1, intval($params['page'] ?? 1));
        $limit = intval($params['limit'] ?? 10);
        $limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 10;
        $projectId = intval($params['project_id'] ?? 0);
        $ruleId = intval($params['rule_id'] ?? ($params['charge_rule_id'] ?? 0));
        $isDeposit = ($params['bill_kind'] ?? '') === 'deposit';
        if ($isDeposit && $targetType !== 'room') {
            throw new \Exception('押金账单请选择房间');
        }
        $ruleInfo = $isDeposit ? [] : $this->getRuleInfo($villageId, $projectId, $ruleId);
        [$startDate, $endDate] = $this->getBatchRequestedDates($params);

        if ($targetType === 'position') {
            if (($ruleInfo['charge_type'] ?? '') !== 'parking_management') {
                throw new \Exception('请选择车位管理费收费项目和标准');
            }
            $garageId = intval($params['garage_id'] ?? 0);
            $positionNum = trim($params['position_num'] ?? '');
            $where = [
                ['p.village_id', '=', $villageId],
            ];
            $parkConfig = (new HouseNewParkingService())->getParkConfigInfo(['village_id' => $villageId]);
            if (!empty($parkConfig) && intval($parkConfig['children_position_type'] ?? 0) === 1) {
                $where[] = ['p.children_type', '=', 1];
            }
            if ($garageId > 0) {
                $where[] = ['p.garage_id', '=', $garageId];
            }
            if ($positionNum !== '') {
                $where[] = ['p.position_num', 'like', '%' . $positionNum . '%'];
            }
            $query = Db::name('house_village_parking_position')->alias('p')
                ->leftJoin('house_village_parking_garage g', 'g.garage_id = p.garage_id')
                ->where($where);
            $count = (clone $query)->count('p.position_id');
            $list = $query->field('p.position_id,p.position_num,p.position_area,p.garage_id,g.garage_num')
                ->order('p.position_id DESC')->page($page, $limit)->select()->toArray();
            $positionIds = array_values(array_filter(array_map(function ($position) {
                return intval($position['position_id'] ?? 0);
            }, $list)));
            $boundPositionMap = $this->getBatchBoundTargetIdMap(
                $villageId,
                $projectId,
                'position',
                $positionIds
            );
            $positionOwnerMap = $this->getPositionOwnerMap($villageId, $positionIds);
            foreach ($list as &$position) {
                $positionId = intval($position['position_id'] ?? 0);
                $ownerInfo = $positionOwnerMap[$positionId] ?? [];
                $position['owner_name'] = $ownerInfo['name'] ?? '';
                $position['owner_phone'] = $ownerInfo['phone'] ?? '';
                $isBound = !empty($boundPositionMap[$positionId]);
                $reason = $isBound ? '' : '未绑定当前收费项目';
                $position['estimated_amount'] = 0;
                $position['estimated_amount_text'] = '-';
                if ($reason === '') {
                    try {
                        $position = array_merge($position, $this->getBatchDatePreview($villageId, $position, 'position', $projectId, $ruleInfo, $startDate, $endDate));
                    } catch (\Throwable $e) { $reason = $e->getMessage(); }
                }
                $position['can_select'] = $reason === '' ? 1 : 0;
                $position['unselectable_reason'] = $reason;
            }
            unset($position);
            return [
                'list' => $list,
                'count' => intval($count),
                'total_limit' => $limit,
            ];
        }

        if ($targetType !== 'room') {
            throw new \Exception('批量账单对象类型不正确');
        }
        $vacancy = $params['vacancy'] ?? [];
        if (!is_array($vacancy)) {
            $vacancy = array_values(array_filter(explode(',', trim((string)$vacancy, ','))));
        }
        $vacancy = array_values(array_map('intval', $vacancy));
        $isUserBind = intval($params['is_user_bind'] ?? 0);
        $where = [
            ['v.village_id', '=', $villageId],
            ['v.is_del', '=', 0],
        ];
        if (isset($vacancy[3]) && $vacancy[3] > 0) {
            $where[] = ['v.pigcms_id', '=', $vacancy[3]];
        } elseif (isset($vacancy[2]) && $vacancy[2] > 0) {
            $where[] = ['v.layer_id', '=', $vacancy[2]];
        } elseif (isset($vacancy[1]) && $vacancy[1] > 0) {
            $where[] = ['v.floor_id', '=', $vacancy[1]];
        } elseif (isset($vacancy[0]) && $vacancy[0] > 0) {
            $where[] = ['v.single_id', '=', $vacancy[0]];
        }
        $buildRoomQuery = function () use ($where, $isUserBind) {
            $query = Db::name('house_village_user_vacancy')->alias('v')
                ->leftJoin('house_village_single s', 's.id = v.single_id')
                ->leftJoin('house_village_floor f', 'f.floor_id = v.floor_id')
                ->leftJoin('house_village_layer l', 'l.id = v.layer_id')
                ->leftJoin(
                    'house_village_user_bind ub',
                    'ub.vacancy_id = v.pigcms_id AND ub.village_id = v.village_id AND ub.status = 1 AND ub.type IN (0,3)'
                )->where($where);
            if ($isUserBind === 1) {
                $query->whereNotNull('ub.pigcms_id');
            } elseif ($isUserBind === 2) {
                $query->whereNull('ub.pigcms_id');
            }
            return $query;
        };
        $count = $buildRoomQuery()->count('DISTINCT v.pigcms_id');
        $list = $buildRoomQuery()
            ->field('v.pigcms_id,v.village_id,v.delivery_time,v.room,v.housesize,v.single_id,v.floor_id,v.layer_id,v.property_service_start_time,v.property_service_end_time,s.single_name,f.floor_name,l.layer_name,IF(COUNT(ub.pigcms_id)>0,"有","无") AS user_bind_status')
            ->group('v.pigcms_id')->order('v.pigcms_id DESC')
            ->page($page, $limit)->select()->toArray();
        // Deposits use a fixed amount and do not require charge bindings or duplicate checks.
        if ($isDeposit) {
            $money = round(floatval($params['deposit_money'] ?? 0), 1);
            if ($money <= 0) {
                throw new \Exception('请输入押金金额');
            }
            foreach ($list as &$room) {
                $room['estimated_amount'] = $money;
                $room['estimated_amount_text'] = number_format($money, 1, '.', '');
                $room['can_select'] = 1;
                $room['unselectable_reason'] = '';
            }
            unset($room);
            return ['list' => $list, 'count' => intval($count), 'total_limit' => $limit];
        }
        $roomIds = array_values(array_filter(array_map(function ($room) {
            return intval($room['pigcms_id'] ?? 0);
        }, $list)));
        $boundRoomMap = $this->getBatchBoundTargetIdMap(
            $villageId,
            $projectId,
            'room',
            $roomIds
        );
        $latestBillEndMap = [];
        if ($roomIds) {
            $latestBillList = Db::name('house_new_pay_order')->where([
                ['village_id', '=', $villageId],
                ['room_id', 'in', $roomIds],
                ['project_id', '=', $projectId],
                ['is_paid', 'in', [1, 2]],
                ['is_discard', '=', 1],
                ['is_refund', '=', 1],
                ['service_end_time', '>', 0],
            ])->field('room_id,MAX(service_end_time) AS latest_bill_end_time')
                ->group('room_id')->select()->toArray();
            foreach ($latestBillList as $latestBill) {
                $latestBillEndMap[intval($latestBill['room_id'])] = intval($latestBill['latest_bill_end_time']);
            }
        }
        foreach ($list as &$room) {
            $latestBillEnd = intval($latestBillEndMap[intval($room['pigcms_id'])] ?? 0);
            $serviceStart = intval($room['property_service_start_time'] ?? 0);
            $serviceEnd = intval($room['property_service_end_time'] ?? 0);
            $reason = empty($boundRoomMap[intval($room['pigcms_id'] ?? 0)]) ? '未绑定当前收费项目' : '';
            $room['billing_number'] = $this->getBillingNumber($room, $ruleInfo);
            $room['estimated_amount'] = 0;
            $room['estimated_amount_text'] = '-';
            if ($reason === '') {
                try {
                    $room = array_merge($room, $this->getBatchDatePreview($villageId, $room, 'room', $projectId, $ruleInfo, $startDate, $endDate));
                } catch (\Throwable $e) { $reason = $e->getMessage(); }
            }
            $room['property_service_start_date'] = $serviceStart > 0 ? date('Y-m-d', $serviceStart) : '-';
            $room['property_service_end_date'] = $serviceEnd > 0 ? date('Y-m-d', $serviceEnd) : '-';
            $room['latest_bill_end_date'] = $latestBillEnd > 0 ? date('Y-m-d', $latestBillEnd) : '暂无';
            $room['can_select'] = $reason === '' ? 1 : 0;
            $room['unselectable_reason'] = $reason;
        }
        unset($room);
        return [
            'list' => $list,
            'count' => intval($count),
            'total_limit' => $limit,
        ];
    }

    private function getBatchRequestedDates($params)
    {
        $start = trim($params['service_start_time'] ?? '');
        $end = trim($params['service_end_time'] ?? '');
        if ($start === '' || $end === '') { throw new \Exception('计费开始时间和计费结束时间均不能为空'); }
        foreach ([$start, $end] as $date) {
            $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if (!$parsed || $parsed->format('Y-m-d') !== $date) { throw new \Exception('计费时间格式不正确'); }
        }
        if ($start > $end) { throw new \Exception('计费开始时间不能晚于结束时间'); }
        if ($this->daysInclusive($start, $end) > 3650) { throw new \Exception('计费时间范围不能超过3650天'); }
        return [$start, $end];
    }

    private function getBatchDatePreview($villageId, $target, $targetType, $projectId, $ruleInfo, $startDate, $endDate)
    {
        $isPosition = $targetType === 'position';
        $this->assertNoOverlappingTargetBill(
            $villageId, $isPosition ? 'position_id' : 'room_id',
            $isPosition ? $target['position_id'] : $target['pigcms_id'], $projectId,
            strtotime($startDate . ' 00:00:00'), strtotime($endDate . ' 23:59:59'),
            $isPosition ? '当前车位收费项目' : '当前收费项目', false
        );
        $this->assertMonthlyRule($ruleInfo);
        [$startDate, $endDate] = $isPosition
            ? $this->normalizePositionBillDates($startDate, $endDate)
            : $this->normalizeBillDates($target, $startDate, $endDate);
        $number = $this->getBillingNumber($target, $ruleInfo);
        if ($isPosition && $this->getFeesTypeStatus($ruleInfo) === 2 && $number <= 0) { throw new \Exception('车位面积为0，无法生成账单'); }
        $amount = round($this->calcDailyMoney($this->money4($ruleInfo['charge_price'] ?? 0), $number, $startDate, $endDate), 1);
        return ['billing_number' => $number, 'estimated_amount' => $amount, 'estimated_amount_text' => number_format($amount, 1, '.', ''),
            'bill_start_date' => $startDate, 'bill_end_date' => $endDate, 'days' => $this->daysInclusive($startDate, $endDate)];
    }

    private function getBatchBoundTargetIdMap($villageId, $projectId, $targetType, $targetIds)
    {
        $targetIds = array_values(array_unique(array_filter(array_map('intval', (array)$targetIds))));
        if (!$targetIds || $projectId <= 0) {
            return [];
        }
        $isPosition = $targetType === 'position';
        $targetField = $isPosition ? 'position_id' : 'vacancy_id';
        $bindType = $isPosition ? 2 : 1;
        $boundTargetIds = Db::name('house_new_charge_standard_bind')->where([
            ['village_id', '=', $villageId],
            ['project_id', '=', $projectId],
            ['bind_type', '=', $bindType],
            ['is_del', '=', 1],
            [$targetField, 'in', $targetIds],
        ])->column($targetField);
        $boundTargetMap = [];
        foreach ($boundTargetIds as $targetId) {
            $targetId = intval($targetId);
            if ($targetId > 0) {
                $boundTargetMap[$targetId] = true;
            }
        }
        return $boundTargetMap;
    }

    private function getBatchRoomUnselectableReason($latestBillEnd, $serviceStart, $serviceEnd)
    {
        if ($latestBillEnd <= 0) {
            if ($serviceStart <= 0 && $serviceEnd <= 0) {
                return '未生成过账单，且未设置物业服务开始、结束时间';
            }
            if ($serviceStart <= 0) {
                return '未生成过账单，且未设置物业服务开始时间';
            }
            if ($serviceEnd <= 0) {
                return '未生成过账单，且未设置物业服务结束时间';
            }
        }
        if ($serviceStart > 0 && $serviceEnd > 0 && $serviceStart > $serviceEnd) {
            return '物业服务开始时间晚于结束时间';
        }
        $start = $latestBillEnd > 0
            ? strtotime('+1 day', strtotime(date('Y-m-d 00:00:00', $latestBillEnd)))
            : strtotime(date('Y-m-d 00:00:00', $serviceStart));
        if ($serviceEnd > 0 && $start > strtotime(date('Y-m-d 23:59:59', $serviceEnd))) {
            return '最新账单已达到物业服务结束时间';
        }
        return '';
    }

    private function getBatchBillDateRange($villageId, $targetId, $billKind, $period, $generateMode, $projectId)
    {
        if ($billKind === 'parking_management') {
            $this->getPositionInfo($villageId, $targetId);
            $latestBillEnd = Db::name('house_new_pay_order')->where([
                ['village_id', '=', $villageId],
                ['position_id', '=', $targetId],
                ['project_id', '=', $projectId],
                ['is_paid', 'in', [1, 2]],
                ['is_discard', '=', 1],
                ['is_refund', '=', 1],
                ['service_end_time', '>', 0],
            ])->max('service_end_time');
            $start = $latestBillEnd && intval($latestBillEnd) > 0
                ? strtotime('+1 day', strtotime(date('Y-m-d 00:00:00', intval($latestBillEnd))))
                : strtotime(date('Y-m-d 00:00:00'));
            $end = $this->getBatchPeriodEndTime($start, $period, $generateMode);
            return ['start_date' => date('Y-m-d', $start), 'end_date' => date('Y-m-d', $end)];
        }

        $roomInfo = $this->getRoomInfo($villageId, $targetId);
        $serviceStart = intval($roomInfo['property_service_start_time'] ?? 0);
        $serviceEnd = intval($roomInfo['property_service_end_time'] ?? 0);
        $latestBillEnd = Db::name('house_new_pay_order')->where([
            ['village_id', '=', $villageId],
            ['room_id', '=', $targetId],
            ['project_id', '=', $projectId],
            ['is_paid', 'in', [1, 2]],
            ['is_discard', '=', 1],
            ['is_refund', '=', 1],
            ['service_end_time', '>', 0],
        ])->max('service_end_time');
        $latestBillEnd = intval($latestBillEnd ?: 0);
        $reason = $this->getBatchRoomUnselectableReason($latestBillEnd, $serviceStart, $serviceEnd);
        if ($reason !== '') {
            throw new \Exception($reason);
        }
        $start = $latestBillEnd > 0
            ? strtotime('+1 day', strtotime(date('Y-m-d 00:00:00', $latestBillEnd)))
            : strtotime(date('Y-m-d 00:00:00', $serviceStart));
        if ($serviceStart > 0) {
            $start = max($start, strtotime(date('Y-m-d 00:00:00', $serviceStart)));
        }
        $end = $this->getBatchPeriodEndTime($start, $period, $generateMode);
        if ($serviceEnd > 0) {
            $end = min($end, strtotime(date('Y-m-d 23:59:59', $serviceEnd)));
        }
        if ($end < $start) {
            throw new \Exception('物业服务时间范围内已无可生成账单的日期');
        }
        return ['start_date' => date('Y-m-d', $start), 'end_date' => date('Y-m-d', $end)];
    }

    private function getBatchPeriodEndTime($startTime, $period, $generateMode)
    {
        if ($generateMode === 'month') {
            $startDay = intval(date('d', $startTime));
            $boundary = new \DateTimeImmutable(date('Y-m-01 00:00:00', $startTime));
            $boundary = $boundary->modify('+' . intval($period) . ' months');
            $boundaryDay = min($startDay, intval($boundary->format('t')));
            $boundary = $boundary->setDate(
                intval($boundary->format('Y')),
                intval($boundary->format('m')),
                $boundaryDay
            );
            return $boundary->modify('-1 day')->getTimestamp();
        }
        return strtotime('+' . (intval($period) - 1) . ' days', $startTime);
    }

    private function getFeesTypeStatus($ruleInfo)
    {
        return intval($ruleInfo['fees_type_status'] ?? ($ruleInfo['fees_type'] ?? 1));
    }

    private function getBillingNumber($roomInfo, $ruleInfo)
    {
        if ($this->getFeesTypeStatus($ruleInfo) === 2) {
            return $this->money4($roomInfo['position_area'] ?? ($roomInfo['housesize'] ?? 0));
        }
        return 1;
    }

    private function getRoomInfo($villageId, $roomId)
    {
        if ($roomId <= 0) {
            throw new \Exception('请选择房间');
        }
        $room = Db::name('house_village_user_vacancy')->where([
            ['pigcms_id', '=', $roomId],
            ['village_id', '=', $villageId],
            ['is_del', '=', 0],
        ])->field('pigcms_id,village_id,housesize,name,phone,delivery_time,property_service_start_time,property_service_end_time')->find();
        if (!$room) {
            throw new \Exception('房间不存在');
        }
        $ownerInfo = $this->getCurrentRoomOwner($villageId, $roomId);
        $room['owner_pigcms_id'] = intval($ownerInfo['pigcms_id'] ?? 0);
        $room['uid'] = intval($ownerInfo['uid'] ?? 0);
        $room['name'] = $ownerInfo['name'] ?? '';
        $room['phone'] = $ownerInfo['phone'] ?? '';
        return $room;
    }

    private function getPositionInfo($villageId, $positionId)
    {
        if ($positionId <= 0) {
            throw new \Exception('请选择车位');
        }
        $position = Db::name('house_village_parking_position')->alias('p')
            ->where([
                ['p.position_id', '=', $positionId],
                ['p.village_id', '=', $villageId],
            ])->field('p.position_id,p.village_id,p.position_num,p.position_area,p.garage_id')->find();
        if (!$position) {
            throw new \Exception('车位不存在');
        }
        $ownerMap = $this->getPositionOwnerMap($villageId, [$positionId]);
        $userInfo = $ownerMap[$positionId] ?? [];
        $position['uid'] = intval($userInfo['uid'] ?? 0);
        $position['pigcms_id'] = intval($userInfo['pigcms_id'] ?? 0);
        $position['room_id'] = intval($userInfo['vacancy_id'] ?? 0);
        $position['name'] = $userInfo['name'] ?? '';
        $position['phone'] = $userInfo['phone'] ?? '';
        return $position;
    }

    private function getCurrentRoomOwner($villageId, $roomId)
    {
        $ownerMap = $this->getCurrentRoomOwnerMap($villageId, [$roomId]);
        return $ownerMap[intval($roomId)] ?? [];
    }

    private function getCurrentRoomOwnerMap($villageId, $roomIds)
    {
        $roomIds = array_values(array_unique(array_filter(array_map('intval', (array)$roomIds))));
        if (!$roomIds) {
            return [];
        }
        $ownerList = Db::name('house_village_user_bind')->where([
            ['village_id', '=', $villageId],
            ['vacancy_id', 'in', $roomIds],
            ['status', '=', 1],
            ['type', 'in', [0, 3]],
        ])->field('pigcms_id,uid,vacancy_id,name,phone,type,add_time')
            ->order('add_time DESC,pigcms_id DESC')->select()->toArray();
        $ownerMap = [];
        foreach ($ownerList as $owner) {
            $roomId = intval($owner['vacancy_id'] ?? 0);
            if ($roomId > 0 && !isset($ownerMap[$roomId])) {
                $ownerMap[$roomId] = $owner;
            }
        }
        return $ownerMap;
    }

    private function getPositionOwnerMap($villageId, $positionIds)
    {
        $positionIds = array_values(array_unique(array_filter(array_map('intval', (array)$positionIds))));
        if (!$positionIds) {
            return [];
        }
        $bindList = Db::name('house_village_bind_position')->alias('bp')
            ->leftJoin('house_village_user_bind ub', 'bp.user_id = ub.pigcms_id')
            ->where([
                ['bp.position_id', 'in', $positionIds],
                ['bp.village_id', '=', $villageId],
                ['ub.village_id', '=', $villageId],
            ])->where('ub.vacancy_id', '>', 0)
            ->field('bp.position_id,ub.vacancy_id')
            ->order('bp.bind_id DESC')->select()->toArray();
        $positionRoomMap = [];
        foreach ($bindList as $bind) {
            $positionId = intval($bind['position_id'] ?? 0);
            if ($positionId > 0 && !isset($positionRoomMap[$positionId])) {
                $positionRoomMap[$positionId] = intval($bind['vacancy_id'] ?? 0);
            }
        }
        $ownerMapByRoom = $this->getCurrentRoomOwnerMap($villageId, array_values($positionRoomMap));
        $ownerMapByPosition = [];
        foreach ($positionRoomMap as $positionId => $roomId) {
            $owner = $ownerMapByRoom[$roomId] ?? [];
            if ($owner) {
                $owner['vacancy_id'] = $roomId;
                $ownerMapByPosition[$positionId] = $owner;
            }
        }
        return $ownerMapByPosition;
    }

    private function normalizePositionBillDates($startDate, $endDate)
    {
        if (!$startDate) {
            throw new \Exception('账单开始日期不能为空');
        }
        if (!$endDate) {
            $endDate = $startDate;
        }
        $start = strtotime($startDate . ' 00:00:00');
        $end = strtotime($endDate . ' 23:59:59');
        if (!$start || !$end) {
            throw new \Exception('账单时间不正确');
        }
        if ($start > $end) {
            throw new \Exception('账单开始时间不能晚于结束时间');
        }
        return [$startDate, $endDate];
    }

    private function getDefaultPositionBillDates($villageId, $positionId)
    {
        $latestEnd = Db::name('house_new_pay_order')->where([
            ['village_id', '=', $villageId],
            ['position_id', '=', $positionId],
            ['is_paid', 'in', [1, 2]],
            ['is_discard', '=', 1],
            ['is_refund', '=', 1],
            ['service_end_time', '>', 0],
        ])->max('service_end_time');
        $start = $latestEnd && intval($latestEnd) > 0
            ? strtotime('+1 day', strtotime(date('Y-m-d 00:00:00', intval($latestEnd))))
            : strtotime(date('Y-m-d 00:00:00'));
        return [
            'start_date' => date('Y-m-d', $start),
            'end_date' => date('Y-m-d', strtotime('-1 day', strtotime('+1 year', $start))),
        ];
    }

    private function normalizeBillDates($roomInfo, $startDate, $endDate)
    {
        if (!$startDate) {
            throw new \Exception('账单开始日期不能为空');
        }
        if (!$endDate) {
            $endDate = $startDate;
        }
        $start = strtotime($startDate . ' 00:00:00');
        $end = strtotime($endDate . ' 23:59:59');
        if (!$start || !$end) {
            throw new \Exception('账单时间不正确');
        }
        if ($start > $end) {
            throw new \Exception('账单开始时间不能晚于结束时间');
        }
        if (!$this->isLegacyVillage(intval($roomInfo['village_id'] ?? 0))) {
            $deliveryTime = intval($roomInfo['delivery_time'] ?? 0);
            if ($deliveryTime <= 0) {
                throw new \Exception('当前房间未填写交房时间');
            }
            $deliveryDay = strtotime(date('Y-m-d 00:00:00', $deliveryTime));
            if ($start < $deliveryDay) {
                throw new \Exception('账单开始日期不得早于交房日期');
            }
        }
        $serviceStartTime = intval($roomInfo['property_service_start_time'] ?? 0);
        if ($serviceStartTime > 0) {
            $serviceStartDay = strtotime(date('Y-m-d 00:00:00', $serviceStartTime));
            if ($start < $serviceStartDay) {
                throw new \Exception('账单开始日期不得早于物业服务开始日期');
            }
        }
        $serviceEndTime = intval($roomInfo['property_service_end_time'] ?? 0);
        if ($serviceEndTime > 0) {
            $serviceEndDay = strtotime(date('Y-m-d 23:59:59', $serviceEndTime));
            if ($end > $serviceEndDay) {
                throw new \Exception('账单结束日期不得晚于物业服务结束日期');
            }
        }
        return [$startDate, $endDate];
    }

    private function getDefaultBillDates($villageId, $roomInfo)
    {
        $serviceStart = intval($roomInfo['property_service_start_time'] ?? 0);
        if ($serviceStart <= 0) {
            return ['start_date' => '', 'end_date' => ''];
        }
        $serviceStartDay = strtotime(date('Y-m-d 00:00:00', $serviceStart));
        $serviceEnd = intval($roomInfo['property_service_end_time'] ?? 0);
        $serviceEndDay = $serviceEnd > 0 ? strtotime(date('Y-m-d 23:59:59', $serviceEnd)) : 0;
        $latestEnd = Db::name('house_new_pay_order')->where([
            ['village_id', '=', $villageId],
            ['room_id', '=', intval($roomInfo['pigcms_id'])],
            ['is_paid', 'in', [1, 2]],
            ['is_discard', '=', 1],
            ['is_refund', '=', 1],
            ['service_end_time', '>', 0],
        ])->max('service_end_time');
        $start = $serviceStartDay;
        if ($latestEnd && intval($latestEnd) > 0) {
            $start = strtotime('+1 day', strtotime(date('Y-m-d 00:00:00', intval($latestEnd))));
            if ($start < $serviceStartDay) {
                $start = $serviceStartDay;
            }
        }
        if ($serviceEndDay > 0 && $start > $serviceEndDay) {
            return ['start_date' => '', 'end_date' => ''];
        }
        $end = strtotime('-1 day', strtotime('+1 year', $start));
        if ($serviceEndDay > 0 && $end > $serviceEndDay) {
            $end = $serviceEndDay;
        }
        return [
            'start_date' => date('Y-m-d', $start),
            'end_date' => date('Y-m-d', $end),
        ];
    }

    private function calcDailyMoney($unitPrice, $area, $startDate, $endDate)
    {
        $start = strtotime($startDate . ' 00:00:00');
        $end = strtotime($endDate . ' 00:00:00');
        $total = 0;
        $cursor = $start;
        while ($cursor <= $end) {
            $monthStart = strtotime(date('Y-m-01 00:00:00', $cursor));
            $monthEnd = strtotime(date('Y-m-t 00:00:00', $cursor));
            $segEnd = min($monthEnd, $end);
            $days = intval(($segEnd - $cursor) / 86400) + 1;
            $monthDays = intval(date('t', $cursor));
            $total += $unitPrice * $area / $monthDays * $days;
            $cursor = strtotime('+1 day', $segEnd);
        }
        return $this->money4($total);
    }

    private function buildMonthlyBillSegments($unitPrice, $area, $startDate, $endDate)
    {
        $segments = [];
        $cursor = strtotime($startDate . ' 00:00:00');
        $end = strtotime($endDate . ' 00:00:00');
        while ($cursor <= $end) {
            $monthEnd = strtotime(date('Y-m-t 00:00:00', $cursor));
            $segmentEnd = min($monthEnd, $end);
            $segmentStartDate = date('Y-m-d', $cursor);
            $segmentEndDate = date('Y-m-d', $segmentEnd);
            $preciseMoney = $this->calcDailyMoney($unitPrice, $area, $segmentStartDate, $segmentEndDate);
            $segments[] = [
                'start_date' => $segmentStartDate,
                'end_date' => $segmentEndDate,
                'precise_money' => $preciseMoney,
                'money' => round($preciseMoney, 1),
            ];
            $cursor = strtotime('+1 day', $segmentEnd);
        }
        return $segments;
    }

    private function assertNoOverlappingBill($villageId, $roomId, $projectId, $startTime, $endTime)
    {
        $this->assertNoOverlappingTargetBill(
            $villageId,
            'room_id',
            $roomId,
            $projectId,
            $startTime,
            $endTime,
            '当前收费项目'
        );
    }

    private function assertNoOverlappingPositionBill($villageId, $positionId, $projectId, $startTime, $endTime)
    {
        $this->assertNoOverlappingTargetBill(
            $villageId,
            'position_id',
            $positionId,
            $projectId,
            $startTime,
            $endTime,
            '当前车位收费项目'
        );
    }

    private function assertNoOverlappingTargetBill($villageId, $targetField, $targetId, $projectId, $startTime, $endTime, $targetLabel, $lock = true)
    {
        if ($startTime > $endTime) {
            throw new \Exception('账单开始时间不能晚于结束时间');
        }
        // Billing periods include entire calendar days, including both endpoints.
        $startTime = strtotime(date('Y-m-d 00:00:00', $startTime));
        $endTime = strtotime(date('Y-m-d 23:59:59', $endTime));
        $overlap = $this->getActiveTargetBillQuery($villageId, $targetField, $targetId, $projectId)
            ->where([
                ['service_start_time', '<=', $endTime],
                ['service_end_time', '>=', $startTime],
            ])->field('order_id,service_start_time,service_end_time')->lock($lock)->find();
        if ($overlap) {
            $existingStart = date('Y-m-d', intval($overlap['service_start_time']));
            $existingEnd = date('Y-m-d', intval($overlap['service_end_time']));
            throw new \Exception('当前时间段已有重复账单：' . $targetLabel . '在' . $existingStart . '至' . $existingEnd . '已存在应缴或已缴账单，不能重复创建');
        }
    }

    private function getExistingBillDateRanges($villageId, $targetField, $targetId, $projectId)
    {
        $rows = $this->getActiveTargetBillQuery($villageId, $targetField, $targetId, $projectId)
            ->field('order_id,project_id,rule_id,service_start_time,service_end_time')
            ->order('service_start_time ASC,order_id ASC')->select()->toArray();
        return array_map(function ($row) {
            return [
                'order_id' => intval($row['order_id'] ?? 0),
                'project_id' => intval($row['project_id'] ?? 0),
                'rule_id' => intval($row['rule_id'] ?? 0),
                'start_date' => date('Y-m-d', intval($row['service_start_time'])),
                'end_date' => date('Y-m-d', intval($row['service_end_time'])),
            ];
        }, $rows);
    }

    private function getActiveTargetBillQuery($villageId, $targetField, $targetId, $projectId)
    {
        return Db::name('house_new_pay_order')->where([
            ['village_id', '=', $villageId],
            [$targetField, '=', $targetId],
            ['project_id', '=', $projectId],
            ['is_paid', 'in', [1, 2]],
            ['is_discard', '=', 1],
            ['is_refund', '=', 1],
            ['service_start_time', '>', 0],
            ['service_end_time', '>', 0],
        ]);
    }

    private function calcVacancyMoney($roomInfo, $config, $startDate, $endDate)
    {
        $days = $this->daysInclusive($startDate, $endDate);
        if (intval($config['charge_mode'] ?? 1) === 1) {
            return $this->money4($this->money4($config['fixed_day_money'] ?? 0) * $days);
        }
        return $this->calcDailyMoney($this->money4($config['area_unit_price'] ?? 0), $this->money4($roomInfo['housesize'] ?? 0), $startDate, $endDate);
    }

    private function daysInclusive($startDate, $endDate)
    {
        $start = strtotime($startDate . ' 00:00:00');
        $end = strtotime($endDate . ' 00:00:00');
        return max(0, intval(($end - $start) / 86400) + 1);
    }

    private function getProjectList($villageId, $billKind = 'bill')
    {
        $query = Db::name('house_new_charge_project')->alias('p')
            ->leftJoin('house_new_charge_number n', 'n.id = p.subject_id')
            ->where([
                ['p.village_id', '=', $villageId],
                ['p.status', '<>', 4],
            ]);
        if ($billKind === 'parking_management') {
            $query->where('n.charge_type', 'parking_management');
        } else {
            $query->where('n.charge_type', '<>', 'parking_management');
        }
        return $query->field('p.id,p.name')->order('p.id DESC')->select()->toArray();
    }

    private function getVillageId($adminUser)
    {
        $villageId = intval($adminUser['village_id'] ?? 0);
        if ($villageId <= 0) {
            throw new \Exception('请先登录到小区后台');
        }
        return $villageId;
    }

    private function isLegacyVillage($villageId)
    {
        return in_array(intval($villageId), self::LEGACY_VILLAGE_IDS, true);
    }

    private function getPropertyId($villageId, $adminUser = [])
    {
        $propertyId = intval($adminUser['property_id'] ?? 0);
        if ($propertyId <= 0) {
            $propertyId = intval(Db::name('house_village')->where('village_id', $villageId)->value('property_id'));
        }
        return $propertyId;
    }

    private function discardUnpaidVacancyFee($villageId, $roomId)
    {
        $where = [
            ['village_id', '=', $villageId],
            ['room_id', '=', $roomId],
            ['order_type', '=', self::ORDER_TYPE_VACANCY_FEE],
            ['source_type', '=', 'vacancy_fee'],
            ['is_discard', '=', 1],
            ['is_paid', '=', 2],
        ];
        $orderIds = Db::name('house_new_pay_order')->where($where)->column('order_id');
        if ($orderIds) {
            Db::name('house_new_vacancy_fee_day')->where([
                ['village_id', '=', $villageId],
                ['room_id', '=', $roomId],
                ['order_id', 'in', $orderIds],
            ])->delete();
        }
        Db::name('house_new_pay_order')->where($where)->update([
            'is_discard' => 2,
            'update_time' => time(),
            'discard_reason' => '物业服务时间已覆盖交房时间',
        ]);
    }

    private function replaceVacancyFeeDays($villageId, $roomId, $orderId, $start, $end)
    {
        Db::name('house_new_vacancy_fee_day')->where([
            ['village_id', '=', $villageId],
            ['room_id', '=', $roomId],
            ['order_id', '=', $orderId],
        ])->delete();
        $rows = [];
        $cursor = strtotime(date('Y-m-d 00:00:00', $start));
        $endDay = strtotime(date('Y-m-d 00:00:00', $end));
        $now = time();
        while ($cursor <= $endDay) {
            $rows[] = [
                'village_id' => $villageId,
                'room_id' => $roomId,
                'order_id' => $orderId,
                'fee_date' => date('Y-m-d', $cursor),
                'add_time' => $now,
            ];
            $cursor = strtotime('+1 day', $cursor);
        }
        if ($rows) {
            Db::name('house_new_vacancy_fee_day')->insertAll($rows);
        }
    }

    private function normalizePayOrderIds($orderList)
    {
        $ids = [];
        if (!is_array($orderList)) {
            return $ids;
        }
        foreach ($orderList as $order) {
            if (!is_array($order)) {
                continue;
            }
            if (!empty($order['detail_order']) && is_array($order['detail_order'])) {
                foreach ($order['detail_order'] as $detail) {
                    $id = intval($detail['orderid'] ?? ($detail['order_id'] ?? 0));
                    if ($id > 0) {
                        $ids[] = $id;
                    }
                }
                continue;
            }
            $id = intval($order['orderid'] ?? ($order['order_id'] ?? 0));
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    private function money4($money)
    {
        return round(floatval($money), 4);
    }

    private function money1($money)
    {
        return number_format(round(floatval($money), 1), 1, '.', '');
    }
}
